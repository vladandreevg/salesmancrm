<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

set_time_limit( 0 );

/*
 * запускается из js/workers/zadarma.js
 * мониторинг звонков в истории звонков
 */
error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];

// форсированный режим запроса cdr
$isforce = (int)$_REQUEST['force'] == 1;

if ($identity == '') {
	$identity = $db -> getOne("SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'");
}

if ((int)$identity == 0) {

	$return = ["error" => "Не верный ключ CRM API"];
	goto toexit;

}

include $rootpath."/inc/func.php";

//параметры подключения к серверу
require_once dirname( __DIR__)."/onlinepbx/sipparams.php";
require_once dirname( __DIR__)."/onlinepbx/mfunc.php";

//для добавления в таблицу callhistory статусов из onlinepbx (такой же список есть в cdr.php)
$status_end = [
	'UNSPECIFIED'                    => "FAILED",
	'UNALLOCATED_NUMBER'             => "BREAKED",
	'NO_ROUTE_TRANSIT_NET'           => "BREAKED",
	'NO_ROUTE_DESTINATION'           => "BREAKED",
	'CHANNEL_UNACCEPTABLE'           => "BREAKED",
	'CALL_AWARDED_DELIVERED'         => "BREAKED",
	'NORMAL_CLEARING'                => "ANSWERED",
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

//определение смещния времени UTC (в базе храниться наше время, у телефонии в UTC) В часах
$clientTimeZone = $db -> getOne("select timezone from {$sqlname}settings WHERE id = '$identity'");
$tz             = new DateTimeZone($clientTimeZone);
$dz             = new DateTime();
$dzz            = $tz -> getOffset($dz);
$clientOffset   = $dzz / 3600;

/**
 * Дата со смещением
 *
 * @param $string
 * @param int $tzz
 * @return false|string
 */
function UTCtoDateTimeSelf($string, int $tzz = 0) {

	$dm = getDateTimeArray($string);

	//тут корректируем смещение часового пояса
	$d = getServerTimeOffset();
	/*
	[offset] => 0 -- разница м/у настройками временной зоны в CRM и на сервере
	[serverTimeZone] => Asia/Yekaterinburg -- часовая зона сервера
	[serverOffset] => 5 -- смещение часовой зоны сервера от +0
	[clientTimeZone] => Asia/Yekaterinburg  -- часовая зона, настроенная в CRM
	[clientOffset] => 5 -- смещение часовой зоны, настроенной в CRM от +0
	*/

	//если время приходит в правильном UTC формате, т.е. +0, то
	$offset = $d['clientOffset'] - $tzz;

	return date("Y-m-d H:i:s", mktime( $dm['H'] + $offset, $dm['i'], $dm['s'], $dm['m'], $dm['d'], $dm['Y']));

}

//посмотрим дату последнего звонка из истории звонков
$last_datum = $db -> getOne("SELECT MAX(datum) FROM {$sqlname}callhistory WHERE identity = '$identity'");

//если проверок не было (на старте) или была больше 30 дней назад
if ($last_datum == '' || (int)diffDate2($last_datum) > 30) {

	//если часы не указаны, то берем за месяц
	if (!$hours) {
		$hours = 24 * 30;
	}//берем статистику за месяц

	$delta     = $hours * 3600;//период времени, за который делаем запрос в часах
	$zone      = $GLOBALS['tzone'];//смещение временной зоны сервера
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);
	$dateStart = modifyDatetime( current_datumtime(), [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -$hours,
		"minutes" => -1
	] );

}
else {
	$dateStart = $last_datum;//UTCtoDateTimeSelf($last_datum, "-10");
}

//проверяем не чаще, чем раз в 5 минут
//иначе, при большом количестве пользователей
//резко возрастает нагрузка на сервер
if (diffDateTimeSeq($dateStart) < 300 && !$isforce) {

	$return = ["result" => "Данные обновлены менее 5 минут назад"];
	goto toexit;

}

//$dateEnd = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')));
$dateEnd = modifyDatetime(NULL, ["minutes" => 5]);

//Делаем запрос на подготовку статистики и получаем
$data = [
	"api_salt"  => $api_user,
	"api_key"   => $api_secret,
	"date_from" => UTCtoDateTimeSelf($dateStart, "10"),
	"date_to"   => $dateEnd
];
//print_r($data);

//print $dateStart."\n";
//print diffDateTimeSeq($dateStart);

$calls = doMethod('history', $data);
//print_r($calls);
//print array2string($calls, "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;");
//exit();

$list = [];

foreach ($calls as $call) {

	$callid      = $call['uuid'];//Уникальный id звонка
	$datum       = gmdate("Y-m-d H:i:s ", $call['date']);//Дата начала вызова
	$type        = $call['type'];//Тип звонка
	$phone_from  = $call['caller'];//Номер звонящего
	$domain_from = $call['from_domain'];//Домен того, кому звонили
	$phone_to    = $call['to'];//Кому звонили
	$domain_to   = $call['to_domain'];//Домен того, кому звонили
	$status      = $call['hangup_cause'];//Причина завершения звонка
	$duration    = $call['duration'];//Общая длительность звонка
	$sec         = $call['billsec'];//Длительность разговора
	$gateway     = $call['gateway'];//Транк
	//$fileid     = $call -> pbx_call_id;//id файла записи
	//$isRecorded = $call -> is_recorded;//записан ли разговор

	// дата из телефонии с учетом тайм зоны
	$datUTC  = explode(" ", $datum);
	$dataUTC = explode("-", $datUTC[0]);
	$timUTC  = explode(":", $datUTC[1]);
	$datum   = date("Y-m-d H:i:s", mktime($timUTC[0] + $clientOffset, $timUTC[1], $timUTC[2], $dataUTC[1], $dataUTC[2], $dataUTC[0]));

	//поскольку по исходящим звонка приходят две строки уберем ненужную строку(потому что сперва идет надор sip номера, а затем уже вывывается клиент)
	if ($phone_from != $phone_to) {

		$src = $phone_from;
		$dst = $phone_to;

		if (stripos($type, 'in') !== false) {

			$did       = $gateway;
			$phone     = prepareMobPhone($phone_from);
			$direct    = "income";
			$extension = $dst;

		}
		elseif (stripos($type, 'out') !== false) {

			$did       = $gateway;
			$phone     = prepareMobPhone($phone_to);
			$direct    = "outcome";
			$extension = $src;

		}
		else {

			$direct = $type;
			$phone  = '';
			$did    = $gateway;

		}

		if (strlen($src) == strlen($dst)) {
			$direct = 'inner';
		}

		$u = getxCallerID((string)$phone);

		$iduser = $db -> getOne("SELECT iduser FROM  {$sqlname}user WHERE phone_in = '$extension' and identity = '$identity'");

		$list[] = [
			"uid"      => $callid,
			"datum"    => $datum,
			"res"      => strtr($status, $status_end),
			"sec"      => $sec,
			"file"     => ($sec > 5) ? 'yes' : '',
			"src"      => $src,
			"dst"      => $dst,
			"did"      => $did,
			"phone"    => $phone,
			"iduser"   => (int)$iduser,
			"direct"   => $direct,
			"clid"     => (int)$u['clid'],
			"pid"      => (int)$u['pid'],
			"identity" => $identity
		];

	}

}

//print_r($list);
//print array2string($list, "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;");
//exit();

$upd = $new = 0;

//обрабатываем запрос
foreach ($list as $call) {

	//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
	$id = (int)$db -> getOne("SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND phone = '$call[phone]' AND direct = '$call[direct]' AND identity = '$identity'");

	//print_r($call);
	if ($id == 0) {

		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", arrayNullClean($call));

		$new++;

	}
	else {

		unset($call['uid']);

		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$id'", arrayNullClean($call));

		$upd++;

	}

}

//if ($_REQUEST['printres'] == 'yes')
$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;

$return = ["result" => $rez];


toexit:

print json_encode_cyr($return);

exit();