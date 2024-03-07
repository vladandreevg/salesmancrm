<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * скрипт получает уведомления из сервиса OnlinePBX
 */

//Для того, чтобы система приняла ссылку, необходимо добавить проверочный код вначале скрипта.
//if (isset($_GET['zd_echo'])) exit($_GET['zd_echo']);

error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: 185.45.152.42');

$response = $_REQUEST;

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$ypath = $rootpath."/content/pbx/onlinepbx/";

//Найдем identity по настройкам
$res      = $db -> getRow("SELECT id, timezone FROM {$sqlname}settings WHERE api_key = '$_GET[crmkey]'");
$tmzone   = $res['timezone'];
$identity = (int)$res['id'];

$iduser1 = $GLOBALS['iduser1'];

//параметры сотрудника
$user     = $db -> getRow("SELECT title,phone_in FROM {$sqlname}user WHERE iduser='$iduser1' AND identity = '$identity'");
$title    = $user["title"];
$phone_in = $user["phone_in"];//внутренний номер оператора

if ($identity == 0) {

	$f = fopen($rootpath."/cash/onlinepbx-worker.log", "a");
	fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
	fwrite($f, "Ошибка: Не верный параметр crmkey\r");
	fwrite($f, "========================\r\r");
	fclose($f);

	exit();

}

require_once dirname( __DIR__)."/onlinepbx/sipparams.php";

/**
 * Отладочные данные
 *
 *
 * $response = array(
 * 'event'       => 'call_answered',
 * 'crmkey'      => 'qXTdkK9Fds9K9KpH3Ol7rP0sNqvPiB',
 * 'domain'      => 'becos.onpbx.ru',
 * 'direction'   => 'inbound',
 * 'uuid'        => 'fceffd9c - e9e8 - 4136 - b789 - ed3a131522a9',
 * 'caller'      => '79194417950',
 * 'callee'      => '101',
 * 'from_domain' => '',
 * 'to_domain'   => 'becos.onpbx.ru',
 * 'gateway'     => '0041773172',
 * 'date'        => '1540980264',
 * );
 */


/**
 * @param $name
 * @return null
 */
function getHeader($name) {

	$headers = getallheaders();

	foreach ($headers as $key => $val) {
		if ($key == $name) {
			return $val;
		}
	}

	return null;

}

//для добавления в таблицу callhistory статусов из onlinepbx (такой же список есть в cdr.php)
$status_end = [
	'UNSPECIFIED'                    => "FAILED",
	'UNALLOCATED_NUMBER'             => "BREAKED",
	'NO_ROUTE_TRANSIT_NET'           => "BREAKED",
	'NO_ROUTE_DESTINATION'           => "BREAKED",
	'CHANNEL_UNACCEPTABLE'           => "BREAKED",
	'CALL_AWARDED_DELIVERED'         => "BREAKED",
	'NORMAL_CLEARING'                => "ANSWER",
	'USER_BUSY'                      => "BUSY",
	'NO_USER_RESPONSE'               => "NO ANSWER",
	'NO_ANSWER'                      => "NO ANSWER",
	'SUBSCRIBER_ABSENT'              => "BREAKED",
	'CALL_REJECTED'                  => "BREAKED",
	'NUMBER_CHANGED'                 => "BREAKED",
	'REDIRECTION_TO_NEW_DESTINATION' => "TRANSFER",
	'EXCHANGE_ROUTING_ERROR'         => "FAILED",
	'DESTINATION_OUT_OF_ORDER'       => "BREAKED",
	'INVALID_NUMBER_FORMAT'          => "BREAKED",
	'FACILITY_REJECTED'              => "BREAKED",
	'RESPONSE_TO_STATUS_ENQUIRY'     => "BREAKED",
	'NORMAL_UNSPECIFIED'             => "BREAKED",
	'NORMAL_CIRCUIT_CONGESTION'      => "BREAKED",
	'NETWORK_OUT_OF_ORDER'           => "BREAKED",
	'NORMAL_TEMPORARY_FAILURE'       => "FAILED",
	'SWITCH_CONGESTION'              => "COINGESTION",
	'ACCESS_INFO_DISCARDED'          => "FAILED",
	'REQUESTED_CHAN_UNAVAIL'         => "BREAKED",
	'PRE_EMPTED'                     => "BREAKED",
	'FACILITY_NOT_SUBSCRIBED'        => "FAILED",
	'OUTGOING_CALL_BARRED'           => "FAILED",
	'INCOMING_CALL_BARRED'           => "BREAKED",
	'BEARERCAPABILITY_NOTAUTH'       => "BREAKED",
	'BEARERCAPABILITY_NOTAVAIL'      => "BREAKED",
	'SERVICE_UNAVAILABLE'            => "FAILED",
	'BEARERCAPABILITY_NOTIMPL'       => "FAILED",
	'CHAN_NOT_IMPLEMENTED'           => "BREAKED",
	'FACILITY_NOT_IMPLEMENTED'       => "BREAKED",
	'SERVICE_NOT_IMPLEMENTED'        => "BREAKED",
	'INVALID_CALL_REFERENCE'         => "BREAKED",
	'INCOMPATIBLE_DESTINATION'       => "BREAKED",
	'INVALID_MSG_UNSPECIFIED'        => "FAILED",
	'MANDATORY_IE_MISSING'           => "BREAKED",
	'MESSAGE_TYPE_NONEXIST'          => "BREAKED",
	'WRONG_MESSAGE'                  => "FAILED",
	'IE_NONEXIST'                    => "BREAKED",
	'INVALID_IE_CONTENTS'            => "BREAKED",
	'WRONG_CALL_STATE'               => "BREAKED",
	'RECOVERY_ON_TIMER_EXPIRE'       => "BREAKED",
	'MANDATORY_IE_LENGTH_ERROR'      => "BREAKED",
	'PROTOCOL_ERROR'                 => "BREAKED",
	'INTERWORKING'                   => "BREAKED",
	'ORIGINATOR_CANCEL'              => "BREAKED",
	'CRASH'                          => "FAILED",
	'SYSTEM_SHUTDOWN'                => "CONGESTION",
	'LOSE_RACE'                      => "FAILED",
	'MANAGER_REQUEST'                => "BREAKED",
	'BLIND_TRANSFER'                 => "TRANSFER",
	'ATTENDED_TRANSFER'              => "TRANSFER",
	'ALLOTTED_TIMEOUT'               => "BREAKED",
	'USER_CHALLENGE'                 => "BREAKED",
	'MEDIA_TIMEOUT'                  => "BREAKED",
	'PICKED_OFF'                     => "TRANSFER",
	'USER_NOT_REGISTERED'            => "BREAKED",
	'PROGRESS_TIMEOUT'               => "BREAKED",
];

if ($tmzone == '') {
	$tmzone = 'Europe / Moscow';
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

$bdtimezone = ($bdtimezone > 0) ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '".$bdtimezone.":00'");

$f = fopen($rootpath."/cash/onlinepbx-worker.log", "a");
fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
fwrite($f, "========================\r\r");
fclose($f);

// События ВАТС
/*
call_start     => начало звонка
call_answered  => ответили на звонок
call_end       => завершился звонок
call_missed    => пропущенный звонок
*/

// Параметры ответа при событиях
/*
event       – событие (call_start)
date   – дата в формате UNIX;
uuid – id звонка;
direction - тип звонка(inbound - входящий, outbound - исходящий)
caller   – номер звонящего;
callee  – номер, на который позвонили.
from_domain - домен звонящего
gateway - транк, на/с которого звонили
dialog_duration - длительность разговора
hangup_cause - причина завершения звонка
hangup_by - кто завершил звонок(caller/callee)
 */

//Начало входящего звонка, ответ на звонок

if (in_array($response['event'], [
	'call_start',
	'call_answered'
])) {

	$call_id    = $response['uuid'];
	$type       = ($response['direction'] == 'inbound') ? 'in' : 'out';
	$phone_from = $response['caller']; //Кто звонит;
	$phone_to   = $response['callee']; //Кому звонят;

	//поскольку во время входящего и разгова приходят разные наименование один и тех же переменных
	if ($response['event'] == 'call_start') {

		//Получаем внутренний номер сотрудника и номер клиента
		if ($type == 'in') {

			$status    = 'INCOMING';
			$phone     = $phone_from;
			$extension = (strlen($phone_to) > 5) ? $phone_in : $phone_to;

		}
		else {

			$status    = 'CALLING';
			$phone     = $phone_to;
			$extension = (strlen($phone_from) > 5) ? $phone_in : $phone_from;

		}

	}
	elseif ($response['event'] == 'call_answered') {

		//Получаем внутренний номер сотрудника и номер клиента
		if ($type == 'in') {

			$phone     = $phone_from;
			$extension = $phone_to;

		}
		else {

			$phone     = $phone_to;
			$extension = $phone_from;

		}

		$status = 'ANSWERED';

	}

	if ($extension == '') {
		goto ext;
	}

	$iduser = (int)$db -> getOne("SELECT iduser FROM {$sqlname}user WHERE phone_in = '$extension' and identity = '$identity'");

	$u = [];
	if ($phone != '') {
		$u = getxCallerID( $phone );
	}

	//Идентификатор записи буфера для текущего пользователя
	$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}onlinepbx_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'");

	if ($id == 0) {

		//если запись не найдена, то создаем её
		$db -> query("INSERT INTO  {$sqlname}onlinepbx_log SET ?u", [
			'datum'     => current_datumtime(),
			'callid'    => $call_id,
			'extension' => $extension,
			'phone'     => preparePhone($phone),
			'status'    => $status,
			'type'      => $type,
			'comment'   => $response['hangup_cause'],
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid'],
			'identity'  => $identity
		]);
		$id = $db -> insertId();

	}
	else {

		$db -> query("UPDATE  {$sqlname}onlinepbx_log SET ?u WHERE id = '$id'", [
			'datum'     => current_datumtime(),
			'callid'    => $call_id,
			'extension' => $extension,
			'phone'     => preparePhone($phone),
			'status'    => $status,
			'type'      => $type,
			'comment'   => $response['hangup_cause'],
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid'],
		]);

	}
}

//Завершение соединения

elseif ($response['event'] == 'call_end') {

	$call_id    = $response['uuid'];
	$extension  = $response['caller'];
	$status     = 'ENDING';
	$duration   = $response['call_duration'] + 0;
	$type       = (stripos($response['direction'], 'in') !== false) ? 'in' : 'out';
	$phone_from = $response['caller']; //Кто звонит;
	$phone_to   = $response['callee']; //Кто звонит;

	//Получаем внутренний номер сотрудника и номер клиента
	if ($type == 'out') {

		$phone     = $phone_to;
		$extension = (strlen($phone_from) < 5) ? $phone_from : '';

	}
	else {

		$phone     = $phone_from;
		$extension = (strlen($phone_to) < 5) ? $phone_to : '';

	}

	$u = [];
	if ($phone != '') {
		$u = getxCallerID( $phone );
	}

	if ($extension == '') {
		goto ext;
	}

	//обновляем данные о звонке в таблице zadarma
	$id = $db -> getOne("SELECT id FROM  {$sqlname}onlinepbx_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'");

	if ($id == 0) {

		$db -> query("INSERT INTO {$sqlname}onlinepbx_log SET ?u", [
			'datum'     => current_datumtime(),
			'callid'    => $call_id,
			'extension' => $extension,
			'phone'     => preparePhone($phone),
			'status'    => $status,
			'type'      => $type,
			'comment'   => $response['hangup_cause'],
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid'],
			'identity'  => $identity
		]);
		$id = $db -> insertId();

	}
	else {

		$db -> query("UPDATE {$sqlname}onlinepbx_log SET ?u WHERE id = '$id'", [
			'datum'     => current_datumtime(),
			'callid'    => $call_id,
			'extension' => $extension,
			'phone'     => preparePhone($phone),
			'status'    => $status,
			'type'      => $type,
			'comment'   => $response['hangup_cause'],
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid'],
		]);

	}

}

// Пропущенный звонок
elseif ($response['event'] == 'call_missed') {

	$call_id = $response['uuid'];
	$phone   = $response['caller'];
	$status  = 'NOANSWERED';
	$type    = 'in';

	//обновляем данные о звонке в таблице onlinepbx

	$db -> query("UPDATE {$sqlname}onlinepbx_log SET ?u WHERE callid = 'call_id'", [
		'datum'  => current_datumtime(),
		'callid' => $call_id,
		'phone'  => preparePhone($phone),
		'status' => $status,
		'type'   => $type
	]);

	/*

	//добавляем запись в историю звонков
	$call = array(
		"res"    => strtr($status, $status_end),
		"src"    => $src,
		"dst"    => $dst,
		"did"    => $did,
		"phone"  => $phone,
		"iduser" => $iduser,
		"direct" => ($type == 'in') ? 'income' : 'outcome',
		"clid"   => ($u['clid'] != '') ? $u['clid'] : '',
		"pid"    => ($u['pid'] != '') ? $u['pid'] : '',
	);

	$cid = $db -> getOne("SELECT id FROM  {$sqlname}callhistory WHERE uid = '$call_id' and identity = '$identity'");

	if ($cid == 0) {

		$call['datum'] = current_datumtime();
		$call['uid']   = $call_id;
		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);

	}
	elseif ($cid > 0 && $extension != '') {

		unset($call['uid']);
		unset($call['phone']);
		unset($call['clid']);
		unset($call['pid']);
		unset($call['src']);
		unset($call['dst']);
		//$call['file'] = $response['link'];
		$call['sec'] = $duration;
		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $call);

	}

	*/

}

ext:

print json_encode_cyr($return);