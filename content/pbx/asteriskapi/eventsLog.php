<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2024.1          */
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
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $ypath."/mfunc.php";

$headers = getallheaders();

$tmzone = '';
$crmkey = $_GET['crmkey'];
$params = [];

/**
 * Принимаем в формате JSON
 */
if($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {
	$params = json_decode(file_get_contents('php://input'), true);
}

/**
 * Если это GET-запрос или отправка формы
 */
if( !empty($_GET) ) {

	foreach ($_GET as $key => $value) {
		$params[ $key ] = (!is_array( $value )) ? eventCleaner( $value ) : $value;
	}

}

//Найдем identity по настройкам
$res      = $db -> getRow("SELECT id, timezone FROM {$sqlname}settings WHERE api_key = '$crmkey'");
$tmzone   = $res['timezone'];
$identity = (int)$res['id'];

if ($tmzone == '') {
	$tmzone = 'Europe/Moscow';
}

// база внутренних номеров, которые будем обрабатывать
$extentions = $db -> getCol("SELECT phone_in FROM {$sqlname}user WHERE identity = '$identity'");

$paramsString = json_encode_cyr($params);
$number = "4959898533";

// логгируем только данные ВЛ
if( str_contains($paramsString, $number) || ( $params['call_direction'] == 'outbound' && in_array($params['cid_num'], $extentions) ) ) {

	LogIt($params);

}

if ($identity == 0) {

	echo json_encode_cyr([
		"code"        => 401,
		"resp_status" => "error",
		"error"       => "Unauthorized. Check your crmkey",
	]);
	exit();

}

/**
 * Отправляем ответ и данные для переадресации
 */
echo json_encode_cyr([
	"code"        => 200,
	"resp_status" => "ok",
	"error"       => ""
]);

exit();