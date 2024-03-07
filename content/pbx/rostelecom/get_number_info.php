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
 * Назначение интерфейса:
 * - Получение информации о вызывающем абоненте для формирования параметра Display Name (отображения ФИО клиентов на устройствах операторов).
 * - Получение информации о пользователе (группе), на которого необходимо сделать маршрутизацию вызова.
 * - Запрос должен выполняться при поступлении входящего вызова на входящую линию до маршрутизации вызовов в соответствии с правилами маршрутизации.
 *
 * Если ответ на запрос не получен в установленный таймаут (настраивается администратором системы – настройка уровня Платформы, действуют для всех доменов), то вызов маршрутизируется в соответствии с установленными в домене правилами маршрутизации.
 */

error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: *');

$rootpath = realpath( __DIR__.'/../../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$ypath = $rootpath."/content/pbx/rostelecom/";

//заголовки
$headers = getallheaders();
//идентификатор
$xID     = $headers['X-Client-ID'];

//метод отправки: GET, POST, PUT
// скорее для отладки, т.к. у сервиса следует ловить именно php://input
$method = $_SERVER['REQUEST_METHOD'];

$return = [];

//$response = (!in_array($method, ['POST','GET'])) ? json_decode(file_get_contents('php://input'), true) : json_decode($_REQUEST, true);
$response = json_decode(file_get_contents('php://input'), true);

$xDomain = $response['domain'];

if(empty($response))
	$response = $_REQUEST;

$f = fopen($rootpath."/cash/rt-worker.log", "a");
fwrite($f, current_datumtime().":::\r");
fwrite($f, "event: get_number_info\r");
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
$res      = $db -> getRow("SELECT id, timezone FROM {$sqlname}settings WHERE id = '$identity'");
$tmzone   = $res['timezone'];
$identity = $res['id'] + 0;

if ($identity == 0) {

	$f = fopen($rootpath."/cash/rt-worker.log", "a");
	fwrite($f, current_datumtime()."\r");
	fwrite($f, "event: get_number_info\r");
	fwrite($f, array2string($response)."\r");
	fwrite($f, "Ошибка: Invalid token\r");
	fwrite($f, "========================\r\r");
	fclose($f);

	$return = ["error" => "Invalid token"];

	goto toexit;

}

if ($tmzone == '')
	$tmzone = 'Europe/Moscow';

date_default_timezone_set($tmzone);

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = intval($dzz) / 3600;

if (abs($bdtimezone) > 12) {

	$tzone      = 0;
	$bdtimezone = intval($dzz) / 3600;

}

$bdtimezone = ($bdtimezone > 0) ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '".$bdtimezone.":00'");

$call = [];

//определяем домен аккаунта ВАТС Ростелеком
$options = $db -> getOne("SELECT params FROM ".$sqlname."customsettings WHERE tip = 'sip' and identity = '$identity'");
$options = json_decode($options, true);
$domain  = $options['domain'];


/**
 * Пример запроса на получение информации о номере
 * Запрос:
 * {
 * "domain":"test_domain.14.rt.ru",
 * "from_number":"74959561111",
 * "request_number":"74992222222"
 * }
 * Ответ:
 * {
 * "result": "0",
 * "resultMessage": "Операция выполнена успешно",
 * "displayName ": "Иванов Сергей Петрович",
 * "PIN ": "765"
 * }
 *
 * Входящие параметры (JSON)
 * - domain Название домена, на который пришел вызов
 * - from_number Номер в формате E.164
 * - request_number Номер в формате E.164 (номер входящей линии).
 *
 * Возвращаемые параметры (JSON)
 * - result Код выполнения операции: 0 – Операция выполнена успешно (код проверен, номер свободен)
 * - resultMessage Описание результата выполнения запроса
 *   если result = 0 displayName Отображаемое имя для добавления информации о вызове.
 *   если result > 0 PIN Внутренний номер пользователя, на который необходимо маршрутизировать вызов.
 *
 * Если поле пустое или ответ на запрос не получен в установленный таймаут, то вызов маршрутизируется в соответствии с установленными правилами маршрутизации.
 */

if ($response['domain'] == $domain) {

	$call['phone'] = $response['from_number'];
	//источник вызова
	$call['did'] = $response['request_number'];

	$u         = getxCallerID($call['phone'], false, true);
	$extension = 0;

	if ($u['phonein'] > 0) {
		$extension = $u['phonein'];
	}

	$return = [
		"result"        => 0,
		"resultMessage" => "Операция выполнена успешно",
		"displayName"   => $u['callerID'],
		"PIN"           => max( $extension, 0 )
	];

}
else
	$return = [
		"result"        => -1,
		"resultMessage" => "Unknown domain"
	];

//для отладки можно посмотреть запрос
//$return['method'] = $method;
//$return['request'] = $response;

toexit:

$f = fopen($rootpath."/cash/rt-worker.log", "a");
fwrite($f, current_datumtime().":::\r");
fwrite($f, "event: get_number_info\r");
fwrite($f, array2string($response)."\r");
fwrite($f, "~~~~~~~~~~~~~~~~~~~~~~~~\r\r");
fwrite($f, array2string($return)."\r");
fwrite($f, "========================\r\r");
fclose($f);

print json_encode_cyr($return);
