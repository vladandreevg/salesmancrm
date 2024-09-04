<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*          ver. 2024.1         */
/* ============================ */
error_reporting(E_ERROR);

$url_path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', trim($url_path, ' /'));

$script = 'client';

//print_r($uri_parts);
//exit();

if (!empty($uri_parts[2])) {
	$script = $uri_parts[2];
}
/*if (stripos($script, 'php') === false) {
	$script = "{$script}.php";
}*/

$action = $uri_parts[3];
$method = $script;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ERROR);
ini_set('display_errors', 1);

$rootpath = dirname(__DIR__, 2);
$path     = __DIR__;

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

function Cleaner($string) {

	$string = trim($string);
	return str_replace([
		'"',
		'\n\r',
		"'"
	], [
		'”',
		'',
		"&acute;"
	], $string);

}

$headers = getallheaders();

/**
 * Принимаем в формате JSON
 */
if ($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);

	$APIKEY = array_key_exists('apikey', $headers) ? $headers['apikey'] : $headers['Apikey'];
	$LOGIN  = array_key_exists('login', $headers) ? $headers['login'] : $headers['Login'];

}

/**
 * Если это GET-запрос или отправка формы
 */
else {

	$params = [];
	foreach ($_REQUEST as $key => $value) {
		$params[$key] = ( !is_array($value) ) ? Cleaner($value) : $value;
	}

	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];

}

if (is_null($APIKEY) && !is_null($params['apikey'])) {
	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];
}

$db = new SafeMysql([
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8',
	'errmode' => 'exception'
]);

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone FROM {$sqlname}settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

global $identity;

//найдем пользователя
$result     = $db -> getRow("SELECT title, iduser, isadmin, tip FROM {$sqlname}user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser     = $iduser1 = (int)$result['iduser'];
$username   = $result['title'];
$isadmin    = $result['isadmin'];
$isadminAPI = $result['isadmin'];
$tipuser    = $result['tip'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

$Error  = '';
$fields = $response = [];

//проверяем api-key
if ($identity == 0) {

	$response['result']        = 'Error';
	$response['error']['code'] = 400;
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';

}

//проверяем пользователя
elseif (empty($username)) {

	$response['result']        = 'Error';
	$response['error']['code'] = 401;
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') {

	print $rez = json_encode_cyr($response);
	include $path."/logger.php";

	exit();

}

//print json_encode_cyr($params);
//exit();

if (!empty($params['action'])) {
	$action = $params['action'];
}

//print $action;

// подключаем скрипт метода
include_once $path."/methods/$method.php";

// подключаем логгер
include_once $path."/logger.php";