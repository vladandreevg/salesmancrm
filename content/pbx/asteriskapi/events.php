<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2024.x          */
/* ============================ */

/**
 * скрипт получает уведомления из сервиса Манго
 */
error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json;');

$rootpath = dirname(__DIR__, 3);
$thisfile = basename(__FILE__);
$ypath    = __DIR__;

include $rootpath."/inc/licloader.php";
include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $ypath."/mfunc.php";

$headers = getallheaders();

$tmzone       = '';
$crmkey       = $_GET['crmkey'];
$params       = [];

/**
 * Принимаем в формате JSON
 */
if ($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {
	$params = json_decode(file_get_contents('php://input'), true);
}

/**
 * Если это GET-запрос или отправка формы
 */
if (!empty($_GET)) {

	foreach ($_GET as $key => $value) {
		$params[$key] = ( !is_array($value) ) ? eventCleaner($value) : $value;
	}

}

//Найдем identity по настройкам
$res      = $db -> getRow("SELECT id, timezone FROM {$sqlname}settings WHERE api_key = '$crmkey'");
$tmzone   = $res['timezone'];
$identity = (int)$res['id'];

include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $ypath."/sipparams.php";

/**
 * Подключим хуки, которые можно помещать в папку hooks
 */
$files = getDirFiles($ypath."/hooks/");
foreach ($files as $file) {

	if ( !empty($file) && file_exists($ypath."/hooks/{$file}") ) {
		require_once $ypath."/hooks/{$file}";
	}

}

global $hooks;

// todo: добавить в настройки номер(а) для фильтрации, чтобы отсекать ненужные события (не относящиеся к компании)
$numberFilter = yexplode(",", $sip['context']);
// игнорируем обработку (т.е. считаем, что событие мусорное)
$ignore       = true;

if ($tmzone == '') {
	$tmzone = 'Europe/Moscow';
}

if ($identity == 0) {

	echo json_encode_cyr([
		"code"        => 401,
		"resp_status" => "error",
		"error"       => "Unauthorized. Check your crmkey",
	]);
	exit();

}

date_default_timezone_set($tmzone);

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = $dzz / 3600;

if (abs($bdtimezone) > 12) {
	$tzone      = 0;
	$bdtimezone = $dzz / 3600;
}

$bdtimezone = $bdtimezone > 0 ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '$bdtimezone:00'");

// база внутренних номеров, которые будем обрабатывать
$extentions   = $db -> getCol("SELECT phone_in FROM {$sqlname}user WHERE COALESCE(phone_in, '') != '' AND identity = '$identity'");
$paramsString = json_encode_cyr($params);

/*
print $params['cid_num'];
print "\n";
print $params['call_direction'];
var_dump($extentions);
*/

// логгируем только данные ВЛ
if (
	!empty($numberFilter) &&
	(
		in_array(normalizePhone($params['did']), $numberFilter) ||
		// str_contains($paramsString, $numberFilter) ||
		( $params['call_direction'] == 'outbound' && in_array($params['cid_num'], $extentions) )
	)
) {

	// логгируем (на тесте)
	LogIt($params);

	$ignore = false;

}

// если фильр по номеру не указан, то пропускаем все
if( empty($numberFilter) ){

	// логгируем (на тесте)
	LogIt($params);

	$ignore = false;

}

// если событие игнорируем, то просто возвращаем ок
if( $ignore ){

	echo json_encode_cyr([
		"code"        => 200,
		"resp_status" => "ok",
		"error"       => "",
		"ignore"      => true
	]);

	exit();

}

// формируем данные вызова (на основе первоначального значения)
$call = getCallParams($params);

$udata = [];

if (!empty($call -> phone)) {

	// ищем номер
	$call -> data = getxCallerID($call -> phone);

}

/**
 * Расставим хуки
 */
if( $call -> type == 'record' && $hooks ){

	$hooks -> do_action( "asteriskapi_record", $call );

}

// выходим без обработки при:
// - поступлении входящего вызова на АТС (можно использовать для перенаправления вызова на оператора)
// - поступлении записей
// - завершении вызова на АТС
if ($call -> state == 'INCOMING' || $call -> type == 'record' || $params['event'] == 'inbound_end') {

	$extention = NULL;

	if( $call -> data['clid'] > 0 || $call -> data['pid'] > 0 ){
		$extention = $call -> data['phonein'];
	}

	/**
	 * Отправляем ответ и данные для переадресации
	 */
	echo json_encode_cyr([
		"code"        => 200,
		"resp_status" => "ok",
		"error"       => "",
		"to_ext"      => $extention,
		"payload"     => $call
	]);

	exit();

}

/**
 * Отправляем ответ и освобождаем канал
 */
echo json_encode_cyr([
	"code"        => 200,
	"resp_status" => "ok",
	"error"       => "",
	"payload"     => $call
]);

ob_flush();
flush();

/**
 * Обрабатываем входящие данные, если это звонок и есть номер
 */

$type      = $call -> type;
$extention = $call -> extention;

//Идентификатор записи буфера для текущего пользователя
$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}asteriskapi WHERE type = '$type' and extention = '$extention' and identity = '$identity'");

//если запись не найдена, то создаем её
if ($id == 0) {

	$db -> query("INSERT INTO  {$sqlname}asteriskapi SET ?u", [
		'datum'     => current_datumtime(),
		'callid'    => $call -> callid,
		'extention' => $extention,
		"iduser"    => getUserID($extention),
		'phone'     => preparePhone($call -> phone),
		'status'    => $call -> state,
		'comment'   => json_encode_cyr($call),
		'type'      => $call -> type,
		'clid'      => (int)$call -> data['clid'],
		'pid'       => (int)$call -> data['pid'],
		'identity'  => $identity
	]);
	$id = $db -> insertId();

}
else {

	$db -> query("UPDATE {$sqlname}asteriskapi SET ?u WHERE id = '$id'", [
		'datum'     => current_datumtime(),
		'extention' => $extention,
		"iduser"    => getUserID($extention),
		'callid'    => $call -> callid,
		'phone'     => preparePhone($call -> phone),
		'status'    => $call -> state,
		'comment'   => json_encode_cyr($call),
		'type'      => $call -> type,
		'clid'      => (int)$call -> data['clid'],
		'pid'       => (int)$call -> data['pid'],
	]);

}

// echo json_encode_cyr($call);