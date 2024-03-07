<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2016.10       */
/* ============================ */

/**
 * скрипт получает уведомления из сервиса Манго
 */
error_reporting( E_ERROR );

header( 'Access-Control-Allow-Origin: *' );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$ypath = $rootpath."/content/pbx/gravitel/";

$return = [];

$response = $_REQUEST;

/**
 * Принимаем в формате JSON
 */
$headers = getallheaders();
if ( $headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json" ) {

	$response = json_decode( file_get_contents( 'php://input' ), true );

	//print_r($params);

	$response = array_merge( $response, $_REQUEST );

}

/**
 * пример входщих данных, после звонка. History
 */
/*$response = [
	"cmd" => "history",//история звонка, приходит после завершения звонка
	"type" => "out",//тип звонка (in/out)
	"user",//идентификатор пользователя облачной АТС (необходим для сопоставления на стороне CRM)
	"ext" => "301",//внутренний номер пользователя облачной АТС, если есть
	"groupRealName",//название отдела, если входящий звонок прошел через отдел
	"telnum",//прямой телефонный номер пользователя облачной АТС, если есть
	"phone" => "73422545577",//номер телефона клиента, с которого или на который произошел звонок
	"diversion" => "74952302706",//ваш номер телефона, через который пришел входящий вызов
	"start",//время начала звонка в формате YYYYmmddTHHMMSSZ
	"duration",//общая длительность звонка в секундах
	"callid" => "34875897893",//уникальный id звонка
	"link",//ссылка на запись звонка, если она включена в Облачной АТС
	"status" => "Success",
	//
	//статус входящего звонка:
	//Success - успешный входящий звонок
	//missed – пропущенный входящий звонок
	//
	//статус исходящего звонка:
	//Success - успешный исходящий звонок
	//Busy - мы получили ответ Занято
	//NotAvailable - мы получили ответ Абонент недоступен
	//NotAllowed - мы получили ответ Звонки на это направление запрещены

	"crm_token" => "gCG01Q5MA8msP1jXuQUC",//ключ (token) от CRM, установленный в веб-кабинете
];*/

/**
 * Пример данных для входящего звонка. Event
 */
/*
$response = [
	"cmd" => "event",
	"type" => "ACCEPTED",

	//type - это тип события, связанного со звонком
	//INCOMING - пришел входящий звонок (в это время у менеджера должен начать звонить телефон).
	//ACCEPTED - звонок успешно принят (менеджер снял трубку). В этот момент можно убрать всплывающую карточку контакта в CRM.
	//COMPLETED - звонок успешно завершен (менеджер или клиент положили трубку после разговора).
	//CANCELLED - звонок сброшен (клиент не дождался пока менеджер снимет трубку. Либо, если это был звонок сразу на группу менеджеров, на звонок мог ответить кто-то еще).

	"phone" => "73422545577", //номер телефона клиента string / E.164 да
	"diversion" => "74952302706", //ваш номер телефона, через который пришел входящий вызов string / E.164
	"user", //идентификатор пользователя облачной АТС (необходим для сопоставления на стороне CRM)
	"groupRealName", //название отдела, если входящий звонок прошел через отдел
	"ext" => "702", //внутренний номер пользователя облачной АТС, если есть
	"telnum", //прямой телефонный номер пользователя облачной АТС, если есть
	"callid" => "34875897893", // уникальный id звонка, совпадает для всех связанных string да звонков
	"crm_token" => "t1xdeOwWSIqgDol70CkRdK3WD4N4cm",// ключ (token) от CRM, установленный в веб-кабинете string да
];
*/
/*
$response = [
	"callid"    => "bfee28e5-8d54-40b8-a1f8-b10dcde604d4",
	"cmd"       => "event",
	"crm_token" => "t1xdeOwWSIqgDol70CkRdK3WD4N4cm",
	"phone"     => "79323328683",
	"type"      => "ACCEPTED",
	"user"      => "telefon771@dupad.megapbx.ru",
	"z-flag"    => 0
];
*/
/*
$response = [
	"callid"        => "1ce30f7a-0a0a-40c7-a621-0db97a8d70ggb",
	"cmd"           => "event",
	"crm_token"     => "t1xdeOwWSIqgDol70CkRdK3WD4N4cm",
	"diversion"     => "79223562839",
	"ext"           => "702",
	"groupRealName" => "CallCenter затем Евгений",
	"phone"         => "79173996537",
	"type"          => "INCOMING",
	"user"          => "callcenter@dupad.megapbx.ru",
	"z-flag"        => "0"
];
*/


$results = [
	"INCOMING"  => "NOANSWER",
	"ACCEPTED"  => "ANSWERED",
	"COMPLETED" => "ANSWERED",
	"CANCELLED" => "BREAKED",
	"Success"   => "ANSWERED",
	"Cancel"    => "BREAKED",
	"Busy"      => 'BUSY'
];

//Найдем identity по настройкам
$res      = $db -> getRow( "SELECT id, timezone FROM {$sqlname}settings WHERE api_key = '$response[crm_token]'" );
$tmzone   = $res['timezone'];
$identity = (int)$res['id'];

if ( $identity == 0 ) {

	$f = fopen( $rootpath."/cash/gravitel-worker.log", "a" );
	fwrite( $f, current_datumtime()." :::\r".array2string( $_REQUEST )."\r" );
	fwrite( $f, "Ошибка: Не верный параметр crm_token\r" );
	fwrite( $f, "========================\r\r" );
	fclose( $f );

	$return = ["error" => "Invalid token"];

	goto toexit;

}

if ( $tmzone == '' ) {
	$tmzone = 'Europe/Moscow';
}

date_default_timezone_set( $tmzone );

//установим временную зону
$tz         = new DateTimeZone( $tmzone );
$dz         = new DateTime();
$dzz        = $tz -> getOffset( $dz );
$bdtimezone = $dzz / 3600;

if ( abs( $bdtimezone ) > 12 ) {

	$tzone      = 0;
	$bdtimezone = $dzz / 3600;

}

$bdtimezone = ($bdtimezone > 0) ? "+".abs( $bdtimezone ) : "-".abs( $bdtimezone );

$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

$f = fopen( $rootpath."/cash/gravitel-worker.log", "a" );
fwrite( $f, current_datumtime()." :::\r".array2string( $_REQUEST )."\r" );
fwrite( $f, "========================\r\r" );
fclose( $f );

/**
 * История. Приходит после звонка
 */
if ( $response['cmd'] == 'history' ) {

	//если это входящий звонок и нам нужен number
	if ( $response['type'] == 'in' ) {

		$phone     = $response['phone'];
		$extension = $response['ext'];
		$number_to = $response['ext'];

	}
	elseif ( $response['type'] == 'out' ) {

		$phone     = $response['phone'];
		$extension = $response['ext'];
		$number_to = $response['phone'];

	}

	$u = [];
	if ( $phone != '' ) {
		$u = getxCallerID( $phone );
	}

	//обновляем данные о звонке в таблице gravitel
	$id = $db -> getOne( "select id from  {$sqlname}gravitel_log where type = '$response[type]' and extension = '$extension' and identity = '$identity'" ) + 0;

	if ( $id == 0 ) {

		$db -> query( "INSERT INTO  {$sqlname}gravitel_log SET ?u", [
			'datum'     => current_datumtime(),
			'callid'    => $response['callid'],
			'extension' => $extension,
			'phone'     => $phone,
			'status'    => $response['status'],
			'content'   => json_encode_cyr( $response ),
			'type'      => $response['type'],
			'clid'      => $u['clid'],
			'pid'       => $u['pid'],
			'identity'  => $identity
		] );
		$id = $db -> insertId();

	}
	else {

		$db -> query( "UPDATE {$sqlname}gravitel_log SET ?u WHERE id = '$id'", [
			'datum'    => current_datumtime(),
			'callid'   => $response['callid'],
			'phone'    => $phone,
			'status'   => $response['status'],
			'content'  => json_encode_cyr( $response ),
			'type'     => $response['type'],
			'clid'     => $u['clid'] + 0,
			'pid'      => $u['pid'] + 0,
			'identity' => $identity
		] );

	}

	$iduser = $db -> getOne( "select iduser from  {$sqlname}user where phone_in = '$response[ext]' and identity = '$identity'" );

	//добавляем запись в историю звонков
	$call = [
		"res"    => strtr( $response['status'], $results ),
		"src"    => ($response['type'] == 'out') ? $response['ext'] : $phone,
		"dst"    => ($response['type'] == 'in') ? $phone : $response['ext'],
		"did"    => 0,
		"phone"  => $phone,
		"iduser" => $iduser + 0,
		"direct" => ($response['type'] == 'in') ? 'income' : 'outcome',
		"clid"   => $u['clid'] + 0,
		"pid"    => $u['pid'] + 0
	];

	$cid = $db -> getOne( "select id from  {$sqlname}callhistory where uid = '$response[callid]' and identity = '$identity'" ) + 0;

	if ( $cid == 0 ) {

		$call['datum'] = current_datumtime();
		$call['uid']   = $response['callid'];
		$db -> query( "INSERT INTO {$sqlname}callhistory SET ?u", $call );

	}
	elseif ( $cid > 0 && $response['ext'] != '' ) {

		unset( $call['uid'], $call['phone'], $call['clid'], $call['pid'], $call['src'], $call['dst'] );

		$call['file'] = $response['link'];
		$call['sec']  = $response['duration'];
		$db -> query( "UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $call );

	}

}

/**
 * Событие. Входящий звонок
 */
elseif ( $response['cmd'] == 'event' ) {

	$phone     = $response['phone'];
	$extension = $response['ext'];
	$user      = $response['user'];
	$number_to = $response['diversion'];

	/**
	 * От АТС прилетает только параметр user, а-ля user@domain.pbx.ru
	 * Поэтому user (факт. Логин) должен иметь вид xxx701@domain.pbx.ru, где 701 - вн.номер оператора
	 */
	if ( $extension == '' ) {
		$extension = preg_replace( "/[^0-9]/", "", yexplode( "@", $user, 0 ) );
	}

	if ( $extension != '' && strlen( $extension ) < 10 ) {

		$iduser = $db -> getOne( "SELECT iduser FROM  {$sqlname}user WHERE phone_in = '$extension' AND identity = '$identity'" );

	}


	/**
	 * Для Мегафона
	 * Если звонок идет по мосту "мобильный - мобильный", то в качестве юзеа прилетает 79223374067@crm.megapbx.ru
	 * Здесь отловим такие звонки по признаку длинны номера
	 */
	if ( $extension != '' && strlen( $extension ) > 10 ) {

		$extension = (int)$db -> getOne( "SELECT iduser FROM  {$sqlname}user WHERE ( replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$extension%' or replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$extension%' ) and identity = '$identity'" );

	}

	$u = [];
	if ( $phone != '' ) {
		$u = getxCallerID( $phone );
	}

	//находим запись в логе
	$gid = $db -> getRow( "SELECT id, type FROM  {$sqlname}gravitel_log WHERE callid = '$response[callid]' and extension = '$extension' and identity = '$identity'" );

	$type = ($gid['id'] > 0) ? $gid['type'] : 'in';

	//Идентификатор записи буфера для текущего пользователя
	$id = (int)$db -> getOne( "SELECT id FROM  {$sqlname}gravitel_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'" ) + 0;

	if ( $id == 0 ) {

		//если запись не найдена, то создаем её
		$db -> query( "INSERT INTO  {$sqlname}gravitel_log SET ?u", [
			'datum'     => current_datumtime(),
			'callid'    => $response['callid'],
			'extension' => $extension,
			'phone'     => preparePhone( $phone ),
			'status'    => $response['type'],
			'content'   => json_encode_cyr( $response ),
			'type'      => $type,
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid'],
			'identity'  => $identity
		] );
		$id = $db -> insertId();

	}
	elseif ( $extension != '' ) {

		$db -> query( "UPDATE  {$sqlname}gravitel_log SET ?u WHERE id = '$id'", [
			'datum'     => current_datumtime(),
			'extension' => $extension,
			'callid'    => $response['callid'],
			'phone'     => $phone,
			'status'    => $response['type'],
			'content'   => json_encode( $response ),
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid']
		] );

	}

	/*//добавим в историю звонков
	$call = array(
		"res"    => strtr($response['status'], $results),
		"src"    => ($type == 'out') ? $extension : $phone,
		"dst"    => ($type == 'in') ? $phone : $extension,
		"did"    => $number_to,
		"phone"  => $phone,
		"iduser" => $iduser + 0,
		"direct" => ($type == 'in') ? 'income' : 'outcome',
		"clid"   => $u['clid'] + 0,
		"pid"    => $u['pid'] + 0
	);

	$cid = $db -> getOne("select id from  {$sqlname}callhistory where uid = '$response[callid]' and identity = '$identity'") + 0;

	if ($cid == 0) {

		$call['datum'] = current_datumtime();
		$call['uid'] = $response['callid'];
		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);

	}
	elseif($cid > 0 && $response['ext'] == '') {

		unset($call['uid']);
		unset($call['phone']);
		unset($call['clid']);
		unset($call['pid']);
		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $call);

	}*/

}

/**
 * Команда для получения информации о названии клиента и ответственном за него сотруднике по номеру его телефона.
 * Команда вызывается при поступлении нового входящего звонка
 */
elseif ( $response['cmd'] == 'contact' ) {

	$u         = getxCallerID( $response['phone'] );
	$extension = 0;

	if ( $u['phonein'] > 0 ) {

		$extension = $u['phonein'];

	}

	$return = [
		"contact_name" => $u['callerID'],
		"responsible"  => $extension
	];

}

toexit:

print json_encode_cyr( $return );
