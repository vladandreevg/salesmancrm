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

$action = $params['action'];//получаем необходимое действие из запроса
$id     = (int)$params['id'];//id обращения
$uid    = $params['uid'];//uid (внешний id) обращения

//доступные методы
$aceptedActions = [
	"list",
	"info",
	"status"
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
$iduser   = $iduser1 = (int)$result['iduser'];
$username = $result['title'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

$Error    = '';
$response = [];

$astatus = [
	0 => 'Новое',
	1 => 'Обработано',
	2 => 'Отменено'
];

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
elseif (!in_array($action, $aceptedActions)) {

	$response['result']        = 'Error';
	$response['error']['code'] = 402;
	$response['error']['text'] = 'Не известный метод';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') {
	goto ext;
}

switch ($action) {

	case 'info':

		$entry = $db -> getRow("SELECT * FROM {$sqlname}entry WHERE ide = '$id' and identity = '$identity'");

		//очистим от цифровых индексов
		foreach ($entry as $k => $item) {

			if (is_int($k) || $k == 'identity') {
				unset( $entry[ $k ] );
			}

		}

		$entry['products'] = $db -> getAll("SELECT * FROM {$sqlname}entry_poz WHERE ide = '$id' and identity = '$identity'");

		//очистим от цифровых индексов
		foreach ($entry['products'] as $i => $item) {

			foreach ($item as $k => $v) {

				if (is_int($k) || $k == 'identity' || $k == 'ide' || $k == 'idp') {
					unset( $entry['products'][ $i ][ $k ] );
				}

			}

		}

		$response['data'] = $entry;

	break;

	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'datum';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and DATE_FORMAT({$sqlname}entry.datum, '%y-%m-%d') = '".$params['dateStart']."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and ({$sqlname}entry.datum BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and DATE_FORMAT({$sqlname}entry.datum, '%y-%m-%d') < '".$params['dateEnd']."'";
		}

		if ($params['status'] != '') {
			$sort .= " and {$sqlname}entry.status IN (".$params['status'].")";
		}

		if ($params['user'] != '') {
			$sort .= " and {$sqlname}entry.iduser = '".current_userbylogin( $params['user'] )."'";
		}
		else {
			$sort .= " and {$sqlname}entry.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		$lpos = $offset * $limit;

		$query = "
			SELECT
				{$sqlname}entry.ide as ide,
				{$sqlname}entry.datum as datum,
				{$sqlname}entry.datum_do as datum_do,
				{$sqlname}entry.pid as pid,
				{$sqlname}entry.clid as clid,
				{$sqlname}entry.did as did,
				{$sqlname}entry.content as content,
				{$sqlname}entry.status as status,
				{$sqlname}entry.iduser as iduser,
				{$sqlname}entry.autor as autor,
				{$sqlname}clientcat.title as client,
				{$sqlname}personcat.person as person,
				{$sqlname}dogovor.title as deal,
				{$sqlname}user.title as user
			FROM {$sqlname}entry
				LEFT JOIN {$sqlname}user ON {$sqlname}entry.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}personcat ON {$sqlname}entry.pid = {$sqlname}personcat.pid
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}entry.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}entry.did = {$sqlname}dogovor.did
			WHERE
				{$sqlname}entry.ide > 0
				".$sort."
				and {$sqlname}entry.identity = '$identity'
			ORDER BY $order $first LIMIT $lpos,$limit
		";

		$field_types = db_columns_types( "{$sqlname}entry_poz" );

		$result = $db -> query($query);
		while ($da = $db -> fetch($result)) {

			$products = $db -> getAll("SELECT * FROM {$sqlname}entry_poz WHERE ide = '$da[ide]' and identity = '$identity'");

			//print_r($products);

			//очистим от цифровых индексов
			foreach ($products as $i => $item) {

				foreach ($item as $k => $v) {

					if ( is_numeric( $k ) || $k == 'identity' || $k == 'ide' || $k == 'idp' ) {
						unset( $products[ $i ][ $k ] );
					}
					elseif($field_types[ $k ] == "int"){

						$products[ $i ][ $k ] = (int)$v;

					}
					elseif(in_array($field_types[ $k ], ["float","double"])){

						$products[ $i ][ $k ] = (float)$v;

					}
					else {

						$products[ $i ][ $k ] = $v;

					}

				}

			}

			//print_r($products);

			$response['data'][] = [
				"ide"        => (int)$da['ide'],
				"datum"      => $da['datum'],
				"datum_do"   => $da['datum_do'],
				"content"    => $da['content'],
				"clid"       => (int)$da['clid'],
				"client"     => $da['client'],
				"pid"        => (int)$da['pid'],
				"person"     => $da['person'],
				"did"        => (int)$da['did'],
				"deal"       => $da['deal'],
				"iduser"     => (int)$da['iduser'],
				"user"       => $da['user'],
				"idautor"    => (int)$da['autor'],
				"autor"      => current_user($da['autor']),
				"status"     => (int)$da['status'],
				"statusName" => strtr((int)$da['status'], $astatus),
				"products"   => $products
			];

		}

		if ($db -> affectedRows() == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	case 'status':

		$id = (int)$db -> getOne("SELECT ide FROM {$sqlname}entry WHERE (ide = '$params[id]' or uid = '$params[uid]') and identity = '$identity'");

		//проверка, что есть название клиента
		if ($id > 0) {

			if ((int)$params['uid'] > 0) {
				$db -> query( "update {$sqlname}entry set uid = '$params[uid]' where ide = '".$id."' and identity = '$identity'" );
			}
			elseif ((int)$params['status'] > 0) {
				$db -> query( "update {$sqlname}entry set status = '$params[status]' where ide = '".$id."' and identity = '$identity'" );
			}

			$response['result'] = 'Успешно';
			$response['data']   = $id;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

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

exit();