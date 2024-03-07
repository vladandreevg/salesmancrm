<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2019.x          */
/* ============================ */

/**
 * скрипт получает уведомления из сервиса Ростелеком
 */

/**
 * Название интерфейса: call_events
 * Назначение интерфейса – отправка уведомлений о вызовах:
 * - о новом вызове (входящем, исходящем, внутреннем);
 * - о начале разговора (установка акустического соединения) – может быть несколько событий (при переводе вызова или организации конференции);
 * - о завершении разговора (разрыв акустического соединения) – может быть несколько событий (при переводе вызова или организации конференции);
 * - о завершении вызова.
 */

error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: *');

$rootpath = realpath( __DIR__.'/../../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/func.php";

$thisfile = basename( __FILE__ );

$rootpath = realpath(__DIR__.'/../..');

if (!file_exists($rootpath."/cash/salesman_error.log")) {

	$file = fopen($rootpath."/cash/salesman_error.log", "w");
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $rootpath.'/cash/salesman_error.log');

$ypath = $rootpath."/content/pbx/rostelecom/";

//заголовки
$headers = getallheaders();
//идентификатор
$xID = $headers['X-Client-ID'];

// метод отправки: GET, POST, PUT
// скорее для отладки, т.к. у сервиса следует ловить именно php://input
$method = $_SERVER['REQUEST_METHOD'];

$return = [];

//$response = (!in_array($method, ['POST','GET'])) ? json_decode(file_get_contents('php://input'), true) : $_REQUEST;
$response = json_decode(file_get_contents('php://input'), true);

$xDomain = $response['domain'];


$f = fopen($rootpath."/cash/rt-worker.log", "a");
fwrite($f, current_datumtime().":::\r");
fwrite($f, "event: call_events\r");
fwrite($f, array2string($headers)."\r");
fwrite($f, array2string($response)."\r");
fwrite($f, "========================\r\r");
fclose($f);

//сопоставление статусов
$statuses = [
	"new"          => "NOANSWER",
	"connected"    => "ACCEPTED",
	"end"          => "ANSWERED",
	"disconnected" => "BREAKED"
];

function getNumberFromSIP($string) {

	return
		stripos($string, 'sip') !== false ?
			yexplode(":", yexplode("@", $string, 0), 1) : $string;

}

//Найдем identity по настройкам
$identity = $db -> getOne("SELECT identity FROM {$sqlname}services WHERE user_key = '$xID'") + 0;
//$identity = $db -> getOne("SELECT identity FROM {$sqlname}customsettings WHERE params LIKE '%\"domain\":\"$xDomain\"%'") + 0;
$res      = $db -> getRow("SELECT id, timezone, ivc FROM {$sqlname}settings WHERE id = '$identity'");
$tmzone   = $res['timezone'];
$ivc      = $res['ivc'];
//$identity = $res['id'] + 0;

if ($identity == 0) {

	include $rootpath."/inc/func.php";

	$f = fopen($rootpath."/cash/rt-worker.log", "a");
	fwrite($f, current_datumtime().":::\r");
	fwrite($f, "event: call_events\r");
	fwrite($f, "Ошибка: Invalid token\r");
	fwrite($f, "========================\r\r");
	fclose($f);

	$return = ["error" => "Invalid token"];

	goto toexit;

}

require_once $rootpath."/inc/settings.php";

//параметры подключения к серверу
require_once "sipparams.php";
require_once "mfunc.php";


if ($tmzone == '') {
	$tmzone = 'Europe/Moscow';
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

$call = [];
$src  = $dst = $direction = '';

//определяем домен аккаунта ВАТС Ростелеком
$options = $db -> getOne("SELECT params FROM ".$sqlname."customsettings WHERE tip = 'sip' and identity = '$identity'");
$options = json_decode((string)$options, true);
$domain  = $options['domain'];

/**
 * Уведомление о новом вызове (входящем, исходящем, внутреннем):
 * {
 * "state": "new",
 * "type": "incoming",
 * "session_id": "76981273981237",
 * "timestamp": "2018-04-23 15:01:27.214",
 * "from_number": "sip:79771234567@example_domain.ru",
 * "request_number": "sip:74951234567@example_domain.ru"
 * }
 *
 * Уведомление о начале разговора (установка акустического соединения):
 * {
 * "state": "connected",
 * "type": "incoming",
 * "session_id": "76981273981237",
 * "timestamp": "2018-04-23 15:01:29.214",
 * "from_number": "sip:79771234567@example_domain.ru",
 * "request_number": "user@example_domain.ru",
 * "request_pin": "317"
 * }
 *
 * Уведомление о завершении разговора (разрыв акустического соединения):
 * {
 * "state": " disconnected ",
 * "type": "incoming",
 * "session_id": "76981273981237",
 * "timestamp": "2018-04-23 15:01:29.214",
 * "from_number": "sip:79771234567@example_domain.ru",
 * "request_number": "user@example_domain.ru",
 * "request_pin": "317",
 * "disconnect_reason": "Отбой вызывающего абонента"
 * }
 *
 * Уведомление о завершении вызова:
 * {
 * "state": "end",
 * "type": "incoming",
 * "session_id": "76981273981237",
 * "timestamp": "2018-04-23 15:01:27.214",
 * "from_number": "sip:79771234567@example_domain.ru",
 * "request_number": "user@example_domain.ru",
 * "request_pin": "317",
 * "is_record": "true"
 * }
 *
 * type
 * Тип вызова:
 * incoming – входящий
 * outbound – исходящий
 * internal – внутренний
 *
 * state
 * Тип уведомления:
 * new – о новом вызове
 * connected – о начале разговора
 * disconnected – о завершении разговора
 * end – о завершении вызова
 *
 * from_pin
 * PIN вызывающего абонента.
 * Устанавливается только для исходящих и внутренних вызовов.
 *
 * request_number
 * Номер в формате E.164 или SIP-URI вызываемого абонента.
 *
 * request_pin
 * PIN вызываемого абонента.
 * Устанавливается только для входящих и внутренних вызовов
 *
 * disconnect_reason
 * Причина завершения вызова.
 * Устанавливается только для уведомлений о завершении вызова (disconnected).
 *
 * is_record
 * Флаг, уведомляющий о наличии записи разговора.
 * Устанавливается только для уведомлений о завершении вызова (end).
 */

$call['callid']    = $response['session_id'];
$call['datum']     = current_datumtime();
$call['status']    = strtr($response['state'], $statuses);
$call['content']   = json_encode_cyr($response);
$call['extention'] = 0;
$destination       = 0;

//сопоставляем прочие параметры с параметрами, знакомыми CRM
switch ($response['type']) {

	//входящий вызов
	case "incoming":

		$call['type']      = "in";
		$call['phone']     = $src = getNumberFromSIP($response['from_number']);
		$call['extention'] = $dst = $response['request_pin'];
		$direction         = 'income';

		//трубка пока не взята. разговор не начат
		if ($response['state'] == 'new') {

			$call['status'] = 'INCOMING';
			$destination    = getNumberFromSIP($response['request_number']);

		}

	break;
	//исходящий вызов
	case "outbound":

		$call['type']      = "out";
		$call['phone']     = $dst = getNumberFromSIP($response['request_number']);
		$call['extention'] = $src = $response['from_pin'];
		$direction         = 'outcome';

	break;
	//внутренний вызов
	case "internal":

		//внутренние звонки нам не интересны
		$direction = 'inner';

	break;

}

if ($response['state'] == 'disconnected') {

	/**
	 *
	 * "disconnect_reason":""  - Нет акустического соединения
	 * "disconnect_reason":"0" - Transfer (Перевод вызова)
	 * "disconnect_reason":"3" - Busy
	 * "disconnect_reason":"4" - Отбой абонента A:
	 * 1)Абонент А отбился во время перевода вызова абонента Б на абонента В.
	 * 2)Абонент А завершил вызов при удержании абонента Б на вызове.
	 * "disconnect_reason":"5" - Успешный вызов
	 */
	switch ($response['disconnect_reason']) {

		case "0":
		case "":

			$call['status'] = 'BREAKED';

		break;
		case "3":

			$call['status'] = 'BUSY';

		break;
		case "4":

			$call['status'] = 'CANCEL';

		break;
		case "5":

			$call['status'] = 'ANSWERED';

		break;

	}

}

if ($call['extention'] == '') {
	$call['extention'] = 0;
}

//массив данных о номере из базы
$u = [];
if ($call['phone'] != '') {
	$u = getxCallerID( $call['phone'] );
}

$call['clid'] = (int)$u['clid'];
$call['pid']  = (int)$u['pid'];
$iduser       = (int)$u['iduser'];

if ($call['callid'] != '') {

	//обновляем данные о звонке в таблице rostelecom
	$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}rostelecom_log WHERE type = '$call[type]' and extention = '$call[extention]' and identity = '$identity'") + 0;
	if ($id == 0) {

		$call['identity'] = $identity;

		$db -> query("INSERT INTO  {$sqlname}rostelecom_log SET ?u", $call);
		$id = $db -> insertId();

	}
	else {

		$db -> query("UPDATE {$sqlname}rostelecom_log SET ?u WHERE id = '$id'", $call);

	}

	/**
	 * добавим звонок в историю звонков
	 * только после того, как звонок завершен и пришло уведомление об этом
	 */
	if (
		($response['state'] == 'disconnected' && $direction != 'inner')
		||
		($response['state'] == 'new' && $direction == 'income')
	) {

		if($call['status'] == 'INCOMING') {
			$call['status'] = 'ANSWERED';
		}

		//получаем информацию о звонке
		$rez = doMethod('call.info', $s = [
			"api_key"  => $api_key,
			"api_salt" => $api_salt,
			"uid"      => $call['callid'],
			"domain"   => $domain
		]);

		$info = $rez['data']['info'];

		// наличие записи в истории звонков
		$cid = (int)$db -> getOne("SELECT id FROM  {$sqlname}callhistory WHERE uid = '$call[callid]' and identity = '$identity'");

		if ($cid > 0) {

			$hcall = [
				"direct" => $direction,
				//"file"   => $info['is_record'] == "true" ? $call['callid'] : ""
			];

			if($call['status']) {
				$hcall['res'] = $call['status'];
			}

			if($info['duration'] > 0) {
				$hcall['sec'] = $info['duration'];
			}

			if ($destination != 0 && $direction == 'income') {
				$hcall['did'] = $destination;
			}

			$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $hcall);

		}
		else {

			$hcall = [
				"datum"    => current_datumtime(),
				"uid"      => $call['callid'],
				"src"      => $src,
				"dst"      => $dst,
				"phone"    => $call['phone'],
				"iduser"   => (int)$iduser,
				"direct"   => $direction,
				'clid'     => (int)$call['clid'],
				'pid'      => (int)$call['pid'],
				'identity' => $identity
			];

			if($call['status']) {
				$hcall['res'] = $call['status'];
			}

			if(isset($info['is_record'])) {
				$hcall['file'] = $info['is_record'] == "true" ? $call['callid'] : "";
			}

			if ($destination != 0 && $direction == 'income') {
				$hcall['did'] = $destination;
			}

			$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $hcall);

		}

	}

}

$return = [
	"result"        => 1,
	"resultMessage" => "ok",
	"data"          => $call
];

toexit:

print json_encode_cyr($return);
