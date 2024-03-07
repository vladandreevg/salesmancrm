<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
use Salesman\Currency;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

set_time_limit(100);

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
	"info",
	"add",
	"update",
	"delete",
	"history"
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
$result   = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

global $identity;

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' AND identity = '$identity'");
$iduser   = (int)$result['iduser'];
$username = $result['title'];
$iduser1  = (int)$result['iduser'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

$Error    = '';
$response = [];

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

//print_r($params);

switch ($params['action']) {

	case 'info':

		$cur = new Currency();
		$cdata = $cur -> currencyInfo($params['id']);

		if ((int)$cdata['id'] == 0 && (int)$params['id'] > 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись с указанным id не найдена в пределах аккаунта указанного пользователя.";

		}
		elseif ((int)$cdata['id'] > 0 && (int)$params['id'] > 0) {

			if ($cdata['id'] > 0) {

				$response['data'] = $cdata;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		elseif ((int)$cdata['id'] < 1 && $params['id'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id записи";

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	case 'list':

		$cur = new Currency();
		$response['data'] = $cur -> currencyList();

	break;

	case 'add':

		$Data = [];

		$Data['identity'] = $identity;

		//проверка, что есть название клиента
		if ($params['name'] != '') {

			$Data['datum'] = ($params['datum'] == '') ? current_datum() : $params['datum'];
			$Data['name'] = ($params['name'] == '') ? "Без названия" : $params['name'];
			$Data['code'] = ($params['code'] == '') ? '' : $params['code'];
			$Data['view'] = ($params['view'] == '') ? '' : $params['view'];
			$Data['course'] = ( pre_format($params['course']) > 0 ) ? pre_format($params['course']) : 0;


			if (!empty($Data)) {

				try {

					$cur = new Currency();
					$currency = $cur -> edit(0, $Data);

					if ($currency > 0) {

						$response['result'] = 'Успешно';
						$response['data']   = $currency;

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = 409;
						$response['error']['text'] = "Не удалось выполнить";

					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 405;
				$response['error']['text'] = "Отсутствуют параметры";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - Название";

		}

	break;

	case 'update':

		$cur = new Currency();
		$cdata = $cur -> currencyInfo($params['id']);

		if ( $cdata['id'] < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись не найдена";

		}
		else {

			$Data['datum'] = ($params['datum'] == '') ? current_datum() : $params['datum'];
			$Data['name'] = ($params['name'] == '') ? "Без названия" : $params['name'];
			$Data['code'] = ($params['code'] == '') ? '' : $params['code'];
			$Data['view'] = ($params['view'] == '') ? '' : $params['view'];
			$Data['course'] = ( pre_format($params['course']) > 0 ) ? pre_format($params['course']) : 0;

			if (!empty($Data)) {

				try {

					$currency = $cur -> edit((int)$cdata['id'], $Data);

					if ($currency > 0) {

						$response['result'] = 'Успешно';
						$response['data']   = $currency;

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = 409;
						$response['error']['text'] = "Не удалось выполнить";

					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}

		}

		if ($params['id'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

		}

	break;

	case 'delete':

		//проверка принадлежности clid к данному аккаунту
		$cur = new Currency();
		$cdata = $cur -> currencyInfo((int)$params['id']);

		if ( (int)$cdata['id'] < 1 ) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись не найдена";

		}
		else {

			try {

				$rez  = $cur -> delete( (int)$cdata['id'] );

				if ($rez['result'] == 'successe') {

					$response['result']  = 'Успешно';
					$response['data']    = (int)$cdata['id'];
					$response['message'] = $rez['message'];

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = 409;
					$response['error']['text'] = $rez['error']['text'];

				}

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		if ($params['id'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id записи";

		}

	break;

	case 'history':

		if ($params['user'] == '') $params['iduser'] = $iduser;
		else $params['iduser'] = current_userbylogin($params['user']);

		$params['tip'] = getTipTask(untag($params['tip']));

		//проверка, что есть название клиента
		if (($params['clid'] != '' || $params['pid'] != '' || $params['did'] != '') && $params['content'] != '') {

			$params['identity'] = $identity;

			try {

				$hid = addHistorty([
					'iduser'   => $params['iduser'],
					'clid'     => $params['clid'],
					'pid'      => yimplode(";", yexplode(";", str_replace(",", ";", $params['pid']))),//для очистки от пробелов и пустот
					'did'      => $params['did'],
					'datum'    => $params['datum']." ".getTime(current_datumtime()),
					'des'      => $params['content'],
					'tip'      => $params['tip'],
					'identity' => $identity
				]);

				$response['result'] = 'Успешно';
				$response['data']   = $hid;

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры";

		}

	break;

	default:
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
	break;

}


ext:

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;
//HTTPStatus($code);

print json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";