<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
use Salesman\Price;

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

$params['pr_cat'] = $params['category'];

//доступные методы
$aceptedActions = [
	"info",
	"fields",
	"list",
	"add",
	"update",
	"delete",
	"category.list",
	"category.add",
	"category.update",
	"category.delete",
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
$iduser1  = (int)$result['iduser'];
$username = $result['title'];

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

//составляем списки доступных полей для прайса
$ifields[] = 'n_id';
$ifields[] = 'artikul';
$ifields[] = 'title';
$ifields[] = 'descr';
$ifields[] = 'edizm';
$ifields[] = 'datum';
$ifields[] = 'pr_cat';
$ifields[] = 'nds';

$fields = $isfields;

//фильтр вывода по полям из запроса или все доступные
if ($params['fields'] != '') {

	$fis = explode(",", $params['fields']);
	foreach ($fis as $fi) {

		if (in_array($fi, $ifields)) {
			$fields[] = $fi;
		}

	}

}

switch ($params['action']) {

	//Вывод списка имен полей таблицы Прайс
	case 'fields':

		$response   = Price::fields();

	break;

	//Информация о Контакте
	case 'info':

		$response   = Price::info((int)$params['id'], $params['artikul']);

		if($response['result'] != 'Error') {

			unset($response['data']['sklad']['images']);

			$files = json_decode($response['data']['sklad']['file'], true);

			foreach ($files as $i => $file) {

				$response['data']['sklad']['images'][ $i ] = [
					"name" => $file['name'],
					"file" => $productInfo['crmurl']."/files/".$fpath."modcatalog/".$file['file'],
				];

			}

			unset( $response['data']['sklad']['file'], $response['result'] );

		}
		else{

			$error = $response['error'];

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = $error;

		}

	break;

	//Вывод списка
	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'title';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		if ($params['word'] != '') {
			$sort .= " and (artikul LIKE '%".Cleaner( $params['word'] )."%' or title LIKE '%".Cleaner( $params['word'] )."%' or descr LIKE '%".Cleaner( $params['word'] )."%')";
		}

		if ($params['archive'] == 'yes') {
			$sort .= " and ".$sqlname."price.archive = 'yes'";
		}
		elseif ($params['archive'] == 'no') {
			$sort .= " and ".$sqlname."price.archive != 'yes'";
		}

		if($params['category'] > 0) {
			$sort .= " and (".$sqlname."price.pr_cat = '$params[category]')";
		}

		$lpos = $offset * $limit;

		$result = $db -> query("SELECT * FROM ".$sqlname."price WHERE n_id > 0 $sort and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		while ($da = $db -> fetch($result)) {

			$response['data'][] = [
				"prid"     => (int)$da['n_id'],
				"artikul"  => $da['artikul'],
				"title"    => $da['title'],
				"content"  => $da['content'],
				"edizm"    => $da['edizm'],
				"category" => (int)$da['pr_cat'],
				"price_in" => (float)$da['price_in'],
				"price_1"  => (float)$da['price_1'],
				"price_2"  => (float)$da['price_2'],
				"price_3"  => (float)$da['price_3'],
				"price_4"  => (float)$da['price_4'],
				"price_5"  => (float)$da['price_5'],
				"archive"  => $da['archive']
			];

		}

		$response['count'] = (int)$db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."price WHERE n_id > 0 ".$sort." and identity = '$identity'");

	break;

	// Добавление прайсовой позиции
	case 'add':

		$price = new Price();
		$response = $price -> edit(0, $params);

	break;

	// Изменение прайсовой позиции
	case 'update':

		$response   = Price::info((int)$params['id'], $params['artikul']);

		if($response['result'] != 'Error') {

			//$prid = (isset($response['price'])) ? $response['price']['prid'] : $response['prid'];
			$prid = (int)$response['prid'];

			if(isset($params['newartikul'])) {
				$params['artikul'] = $params['newartikul'];
			}
			if(isset($params['description'])) {
				$params['descr'] = $params['description'];
			}

			$price    = new Price();
			$response = $price -> edit($prid, $params);

		}
		else{

			$error = $response['error'];

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = $error;

		}

	break;

	// Удаление прайсовой позиции
	case 'delete':

		$response   = Price::info((int)$params['id'], $params['artikul']);

		if($response['result'] != 'Error') {

			$prid = (int)$response['prid'];

			$response = Price ::delete($prid);

		}
		else{

			$error = $response['error'];

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = $error;

		}

	break;

	// Добавление категории
	case 'category.list':

		$response = Price::listCategory();

	break;

	// Добавление категории
	case 'category.add':

		$price = new Price();
		$response = $price -> editCategory(0, $params);

	break;

	// Изменение категории
	case 'category.update':

		$id = (int)$db -> getOne("SELECT idcategory FROM ".$sqlname."price_cat WHERE idcategory = '$params[id]' AND identity = '$identity'");

		if($id > 0) {

			$price    = new Price();
			$response = $price -> editCategory($id, $params);

		}
		else{

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Позиция не найдена";

		}

	break;

	// Удаление категории
	case 'category.delete':

		$id = (int)$db -> getOne("SELECT idcategory FROM ".$sqlname."price_cat WHERE idcategory = '$params[id]' AND identity = '$identity'");

		if($id > 0) {

			$text = Price ::deleteCategory((int)$params['id']);

			$response = [
				"result" => "Success",
				"text"   => $text,
				"data"   => $id
			];

		}
		else{

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Позиция не найдена";

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