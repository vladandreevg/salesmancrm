<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ERROR);
ini_set( 'display_errors', 1 );

set_time_limit(300);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

function Cleaner($string) {

	$string = trim($string);
	$string = str_replace( [
		'\n\r',
		"'",
		'"'
	], [
		'',
		"&acute;",
		'”'
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

	//print_r($headers);

	//file_put_contents("res.log", json_encode(["params" => $params, "header" => $headers]), FILE_APPEND);

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
	"category",
	"territory",
	"relations",
	"clientpath",
	"company.list",
	"company.listfull",
	"company.bank",
	"company.signers"
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
$result   = $db -> getRow("SELECT id, api_key, timezone FROM {$sqlname}settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

global $identity;

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM {$sqlname}user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = (int)$result['iduser'];
$username = $result['title'];
$iduser1  = (int)$result['iduser'];

include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

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

	$response['result']        = 'Error';
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


switch ($params['action']) {

	case 'category':

		$re = $db -> query("SELECT * FROM {$sqlname}category WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"id"    => (int)$do['idcategory'],
				"title" => $do['title'],
				"tip"   => $do['tip']
			];

		}

	break;

	case 'territory':

		$re = $db -> query("SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"id"    => (int)$do['idcategory'],
				"title" => $do['title']
			];

		}

	break;

	case 'relations':

		$re = $db -> query("SELECT * FROM {$sqlname}relations WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"id"    => (int)$do['id'],
				"title" => $do['title'],
				"color" => $do['color']
			];
		}

	break;

	case 'clientpath':

		$re = $db -> query("SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"id"          => (int)$do['id'],
				"title"       => $do['name'],
				"utm_source"  => $do['utm_source'],
				"destination" => $do['destination']
			];

		}

	break;

	case 'company.list':

		$re = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){

			// подписанты
			$signers = getSigner(0, $do['id']);

			$d1 = explode(";", $do['innkpp']);
			$d2 = explode(";", $do['okog']);

			$response['data'][] = [
				"mcid"             => (int)$do['id'],
				"compUrName"       => $do['name_ur'],
				"compShotName"     => $do['name_shot'],
				"compUrAddr"       => $do['address_yur'],
				"compFacAddr"      => $do['address_post'],
				"compDirName"      => $do['dir_name'],
				"compDirSignature" => $do['dir_signature'],
				"compDirStatus"    => $do['dir_status'],
				"compDirOsnovanie" => $do['dir_osnovanie'],
				"compInn"          => $d1[0],
				"compKpp"          => $d1[1],
				"compOgrn"         => $d2[1],
				"signers"          => array_values(array_values($signers)[0])
			];

		}

	break;

	case 'company.listfull':

		$re = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$d1 = explode(";", $do['innkpp']);
			$d2 = explode(";", $do['okog']);

			// подписанты
			$signers = getSigner(0, $do['id']);

			$rs = [];

			$res = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$do[id]' AND identity = '$identity'");
			while ($da = $db -> fetch($res)) {

				$bankr = explode(";", $da['bankr']);

				$rs[] = [
					"id"           => (int)$da['id'],
					"tip"          => $da['tip'],
					"isDefault"    => $da['isDefault'],
					"ndsDefault"   => $da['ndsDefault'],
					"compNameRs"   => $da['title'],
					"compBankBik"  => $bankr[0],
					"compBankRs"   => $da['rs'],
					"compBankKs"   => $bankr[1],
					"compBankName" => $bankr[2],
				];

			}

			$response['data'][] = [
				"mcid"             => (int)$do['id'],
				"compUrName"       => $do['name_ur'],
				"compShotName"     => $do['name_shot'],
				"compUrAddr"       => $do['address_yur'],
				"compFacAddr"      => $do['address_post'],
				"compDirName"      => $do['dir_name'],
				"compDirSignature" => $do['dir_signature'],
				"compDirStatus"    => $do['dir_status'],
				"compDirOsnovanie" => $do['dir_osnovanie'],
				"compInn"          => $d1[0],
				"compKpp"          => $d1[1],
				"compOgrn"         => $d2[1],
				"bank"             => $rs,
				"signers"          => array_values(array_values($signers)[0])
			];

		}

	break;

	case 'company.bank':

		$re = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE identity = '$identity'");
		while ($da = $db -> fetch($re)) {

			$bankr = explode(";", $da['bankr']);

			$response['data'][] = [
				"id"           => (int)$da['id'],
				"mcid"         => (int)$da['cid'],
				"tip"          => $da['tip'],
				"isDefault"    => $da['isDefault'],
				"ndsDefault"   => $da['ndsDefault'],
				"compNameRs"   => $da['title'],
				"compBankBik"  => $bankr[0],
				"compBankRs"   => $da['rs'],
				"compBankKs"   => $bankr[1],
				"compBankName" => $bankr[2]
			];

		}

	break;

	case 'company.signers':

		$response['data'][] = getSigner();

	break;

	default:
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
	break;

}


ext:

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;
//HTTPStatus($code);

print $rez = json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";