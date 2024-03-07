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

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

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
	"addlist",
	"add"
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

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = $iduser1 = (int)$result['iduser'];
$username = $result['title'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

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

//составляем списки доступных полей
$ifields = [
	'id',
	'datum',
	'direct',
	'res',
	'sec',
	'did',
	'src',
	'dst',
	'file'
];

switch ($params['action']) {

	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? (int)$params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'date_create';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		if ($params['user'] != '') {

			$result   = $db -> getRow("SELECT phone, phone_in, mob, iduser FROM ".$sqlname."user WHERE login = '".$params['user']."' and identity = '".$identity."'");
			$operator = $result['iduser'];
			$sort     .= " and iduser = '".$operator."'";

		}
		if ($params['direct'] != '') {
			$sort .= " and direct = '".$params['direct']."'";
		}

		if ($params['phone'] != '') {
			$sort .= " and (dst LIKE '%".preparePhone( $params['phone'] )."%' or src LIKE '%".preparePhone( $params['phone'] )."%' or phone LIKE '%".preparePhone( $params['phone'] )."%')";
		}

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and datum > '".$params['dateStart']."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and (datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and datum < '".$params['dateEnd']."'";
		}

		$lpos = $offset * $limit;

		$result = $db -> query("SELECT * FROM ".$sqlname."callhistory WHERE id > 0 $sort and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		while ($da = $db -> fetch($result)) {

			$response['data'][] = [
				"id"     => (int)$da['id'],
				"datum"  => $da['datum'],
				"direct" => $da['direct'],
				"res"    => $da['res'],
				"sec"    => (int)$da['sec'],
				"src"    => $da['src'],
				"dst"    => $da['dst'],
				"file"   => $da['file']
			];

		}

	break;

	case 'add':

		foreach ($params['calls'] as $i => $call) {

			if ($call['src'] == '' && $call['dst'] == '') {

				$res['result']        = 'Error';
				$res['error']['code'] = '405';
				$res['error']['text'] = "Отсутствуют параметры - номера оператора и абонента";

			}
			else {

				//входящий
				if ($call['direct'] == 'income') {

					$src = getxCallerID($call['src']);

					$callerID = $src['callerID'];
					$clid     = (int)$src['clid'];
					$pid      = (int)$src['pid'];
					$iduser   = (int)$src['iduser'];

				}
				//исходящий
				if ($call['direct'] == 'outcome') {

					$dst = getxCallerID($call['dst']);

					$callerID = $dst['callerID'];
					$clid     = (int)$dst['clid'];
					$pid      = (int)$dst['pid'];
					$iduser   = (int)$dst['iduser'];

				}

				try {

					$arg = [
						"uid"      => $call['uid'],
						"direct"   => $call['direct'],
						"did"      => $call['did'],
						"datum"    => $call['datum'],
						"clid"     => (int)$clid,
						"pid"      => (int)$pid,
						"iduser"   => (int)$iduser,
						"res"      => $call['res'],
						"sec"      => (int)$call['sec'],
						"src"      => $call['src'],
						"dst"      => $call['dst'],
						"file"     => $call['file'],
						"identity" => $identity
					];

					$db -> query("INSERT INTO ".$sqlname."callhistory SET ?u", $arg);

					$res['result'] = 'Успешно';
					$res['text']   = 'Звонок '.$call['src'].' -> '.$call['dst'].' записан в историю звонков';

				}
				catch (Exception $e) {

					$res['result']        = 'Error';
					$res['error']['code'] = '500';
					$res['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}


			}

			$response = $res;

		}

	break;
	case 'update':

		$call = $db -> getRow("SELECT * FROM ".$sqlname."callhistory WHERE uid = '".$params['uid']."' AND identity = '".$identity."'");

		if((int)$call['id'] > 0) {

			if ($call['direct'] == 'outcome') {
				$iduser = getUserID( preparePhone( $call['src'] ) );
			}
			elseif ($call['direct'] == 'income') {
				$iduser = getUserID( preparePhone( $call['dst'] ) );
			}

			$db -> query("UPDATE ".$sqlname."callhistory SET ?u WHERE id = '".$call['id']."' and identity = '$identity'", [
				'did'    => $params['did'],
				'datum'  => $params['datum'],
				'res'    => $params['res'],
				'sec'    => (int)$params['sec'],
				'file'   => $params['file'],
				'dst'    => $params['dst'],
				'src'    => $params['src'],
				'iduser' => (int)$iduser
			]);

			$res['result'] = 'Успешно';
			$res['text']   = 'Запись '.$call['src'].' -> '.$call['dst'].' обновлена';

		}
		else{

			$res['result']        = 'Error';
			$res['error']['code'] = 404;
			$res['error']['text'] = "Запись с указанным UID не найдена";

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

print $rez = json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";