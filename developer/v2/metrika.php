<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
use Salesman\Statistic;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

set_time_limit(300);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

function Cleaner($string) {

	$string = trim($string);
	$string = str_replace( [
		'"',
		'\n\r',
		"'"
	], [
		'”',
		'',
		"&acute;"
	], $string );

	return $string;

}

$headers = getallheaders();

/**
 * Принимаем в формате JSON
 */
if($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);

	$APIKEY = array_key_exists( 'apikey', $headers) ? $headers['apikey'] : $headers['Apikey'];
	$LOGIN  = array_key_exists( 'login', $headers) ? $headers['login'] : $headers['Login'];

}

/**
 * Если это GET-запрос или отправка формы
 */
else {

	$params = [];
	foreach ($_REQUEST as $key => $value) {
		$params[ $key ] = (!is_array( $value )) ? Cleaner( $value ) : $value;
	}

	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];

}

if( is_null($APIKEY) && !is_null($params['apikey'])){
	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];
}

//доступные методы
$aceptedActions = [
	"list",
	"clients",
	"dealsNew",
	"dealsClose",
	"invoices",
	"payments"
];

$db = new SafeMysql([
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8',
	'errmode' => 'exception'
]);

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone, valuta FROM ".$sqlname."settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];
$valuta   = $result['valuta'];

global $identity;

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = (int)$result['iduser'];
$username = $result['title'];

require_once  $rootpath."/inc/settings.php";
require_once  $rootpath."/inc/func.php";
require_once  $rootpath."/developer/events.php";

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

//проверяем метод
elseif (!in_array($params['action'], $aceptedActions)) {

	$response['error']['code'] = 402;
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') {
	goto ext;
}

/**
 * Основные обработчики
 */

$ilogin = $params['user'];

if ($params['user'] != '') {
	$params['user'] = current_userbylogin( $params['user'] );
}

switch ($params['action']) {

	//Выврд общей статистики
	case 'list':

		$response = Statistic ::all($params['period'], $params);

	break;

	// Вывод кол-ва новых клиентов
	case 'clients':

		$response = Statistic ::clients($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $productInfo['crmurl']."/".$response['url'];
		}

	break;

	// Вывод статистики по новым сделкам
	case 'dealsNew':

		$response = Statistic ::dealsNew($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $productInfo['crmurl']."/".$response['url'];
		}

	break;

	// Вывод статистики по закрытым сделкам
	case 'dealsClose':

		$response = Statistic ::dealsClose($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $productInfo['crmurl']."/".$response['url'];
		}

	break;

	// Вывод статистики по новым счетам
	case 'invoices':

		$response = Statistic ::invoices($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $productInfo['crmurl']."/".$response['url'];
		}

	break;

	// Отмена платежа
	case 'payments':

		$response = Statistic ::payments($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $productInfo['crmurl']."/".$response['url'];
		}

	break;

}

if($ilogin == ''){

	$response['comment'] = 'Данные для пользователя с логином '.$params['login'];

	goto ext;

}
elseif((int)$params['user'] == 0){

	$response['comment'] = 'Пользователь с логином '.$ilogin.' не найден. Данные для пользователя с логином '.$params['login'];

	goto ext;

}

ext:

print $rez = json_encode_cyr($response);

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;
//HTTPStatus($code);

include dirname( __DIR__)."/v2/logger.php";

exit();