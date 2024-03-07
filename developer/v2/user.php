<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

header('Access-Control-Allow-Origin: *');// Устанавливаем возможность отправлять ответ для любого домена или для указанных
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
	"user",
	"user.list",
	"user.add"
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
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' and identity = '$identity'");
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

	$response['result']        = 'Error';
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') goto ext;


switch ($params['action']) {

	//информация о сотруднике
	case 'user':

		if ($params['uid'] != '') $s = "uid = '".$params['uid']."'";
		elseif ($params['user'] != '') $s = "login = '".$params['user']."'";
		elseif ($params['iduser'] != '') $s = "iduser = '".$params['iduser']."'";

		$re        = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE $s and identity = '$identity'");
		$title     = $re["title"];
		$login     = $re["login"];
		$otdel     = $re["otdel"];
		$phone     = $re["phone"];
		$phone_in  = $re["phone_in"];
		$mob       = $re["mob"];
		$mail_url  = $re["email"];
		$uid       = $re["uid"];
		$iduser    = (int)$re["iduser"];
		$tip       = $re["tip"];
		$user_post = $re["user_post"];
		$secrty    = $re["secrty"];

		$oid = $db -> getOne("select uid from ".$sqlname."otdel_cat where idcategory = '$otdel' and identity = '$identity'");

		if ($login != '') {

			$response['data'] = [
				"iduser"    => $iduser,
				"uid"       => $uid,
				"oid"       => $oid,
				"title"     => $title,
				"login"     => $login,
				"active"    => $secrty,
				"tip"       => $tip,
				"user_post" => $user_post,
				"email"     => $mail_url,
				"phone"     => $phone,
				"phone_in"  => $phone_in,
				"mob"       => $mob
			];

		}
		else {

			$response['error']['code'] = '404';
			$response['error']['text'] = 'Пользователь не найден';

		}

	break;

	case 'user.list':

		$re = $db -> query("SELECT * FROM ".$sqlname."user WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$otdel = $db -> getOne("SELECT uid FROM ".$sqlname."otdel_cat WHERE idcategory = '".$do['otdel']."' and identity = '$identity'");

			$response['data'][] = [
				"iduser" => (int)$do['iduser'],
				"uid"    => $do['uid'],
				"oid"    => $otdel,
				"title"  => $do['title'],
				"login"  => $do['login'],
				"email"  => $do['email'],
				"active" => $do['secrty']
			];

		}

	break;

	case 'user.add': //work

		$login = untag($params['user']);
		$pwd   = $params['password'];

		$title = untag($params['title']);

		$boss = $db -> getOne("SELECT iduser FROM ".$sqlname."user WHERE tip = 'Руководитель организации' and identity = '$identity' LIMIT 1");

		$tip = (isset($params['tip']) && $params['tip'] != '') ? $params['tip'] : "Менеджер продаж";
		$mid = (isset($params['boss']) && $params['boss'] != '') ? current_userbylogin($params['boss']) : $boss;

		$mail = untag($params['email']);

		if ( $isCloud ) $mail = $login;

		$otdel     = $db -> getOne("SELECT idcategory FROM ".$sqlname."otdel_cat WHERE uid = '$params[otdel]' and identity = '$identity'");
		$territory = $params['territory'];

		$phone     = untag($params['phone']);
		$phone_in  = untag($params['phone_in']);
		$fax       = untag($params['fax']);
		$mob       = untag($params['mob']);
		$bday      = $params['bday'];
		$user_post = untag($params['user_post']);
		$uid       = untag($params['uid']);

		$CompStart = $params['CompStart'];
		$CompEnd   = $params['CompEnd'];

		$acs_analitics = 'on';
		$acs_maillist  = '';
		$acs_files     = 'on';
		$acs_price     = 'on';
		$acs_credit    = '';
		$acs_prava     = '';
		$acs_plan      = 'on';
		$show_marga    = 'yes';
		$ac_import     = 'off;on;off;off;off;off;off;off;on;on;on;on;on;on;on;on;on;off;off';
		$secrty        = '';
		$isadmin       = '';

		$salt    = generateSalt();
		$newpass = encodePass($pwd, $salt);

		//проверка на существующий емейл
		$count = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."user WHERE login='".trim($login)."'");

		if ($count > 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = 'Ошибка: Такой логин уже существует';

		}
		else {

			try {

				$arg = [
					"login"         => $login,
					"pwd"           => $newpass,
					"sole"          => $salt,
					"title"         => $title,
					"tip"           => $tip,
					"mid"           => $mid,
					"otdel"         => $otdel,
					"email"         => $email,
					"phone"         => $phone,
					"fax"           => $fax,
					"mob"           => $mob,
					"bday"          => $bday,
					"acs_analitics" => $acs_analitics,
					"acs_maillist"  => $acs_maillist,
					"acs_files"     => $acs_files,
					"acs_price"     => $acs_price,
					"acs_credit"    => $acs_credit,
					"acs_prava"     => $acs_prava,
					"tzone"         => '0:00',
					"viget_on"      => 'on;on;on;on;on;on;on;on;on;on;on;on;on;on',
					"viget_order"   => 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11;d12;d13;d14',
					"secrty"        => 'yes',
					"acs_import"    => $ac_import,
					"show_marga"    => $show_marga,
					"user_post"     => $user_post,
					"acs_plan"      => $acs_plan,
					"uid"           => $uid,
					"identity"      => $identity
				];

				//$db -> query("INSERT INTO ".$sqlname."USER (iduser, login, pwd, sole, title, tip, mid, otdel, email, phone, fax, mob, bday, acs_analitics, acs_maillist, acs_files, acs_price, acs_credit, acs_prava, tzone, viget_on, viget_order, secrty, isadmin, acs_import, show_marga, user_post, acs_plan, CompStart, CompEnd, subscription, uid, identity) values(null, '$login', '$newpass', '$salt', '$title', '$tip','$mid','$otdel','$mail','$phone','$fax','$mob','$bday', '$acs_analitics', '$acs_maillist', '$acs_files', '$acs_price', '$acs_credit', '$acs_prava', '0:00', 'on;on;on;on;on;on;on;on;on;on;on;on;on;on', 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11;d12;d13;d14', '$secrty', '$isadmin','$ac_import','$show_marga', '$user_post', '$acs_plan', '$CompStart', '$CompEnd','on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on', '$uid', '$identity')");

				$db -> query("INSERT INTO ".$sqlname."USER SET ?u", arrayNullClean($arg));

				$response['result']         = 'Success';
				$response['data'] = $db -> insertId();

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

			}

		}

	break;

	case 'category':

		$re = $db -> query("SELECT * FROM ".$sqlname."category WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"title" => $do['title'],
				"tip"   => $do['tip']
			];
		}

	break;

	case 'relations':

		$re = $db -> query("SELECT * FROM ".$sqlname."relations WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"title" => $do['title'],
				"color" => $do['color']
			];
		}

	break;

	case 'mycompany':

		$re = $db -> query("SELECT * FROM ".$sqlname."mycomps WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"mcid"  => (int)$do['id'],
				"title" => $do['name_shot']
			];
		}

	break;

	case 'mybank':

		$re = $db -> query("SELECT * FROM ".$sqlname."mycomps_recv WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"id"    => (int)$do['id'],
				"mcid"  => $do['cid'],
				"title" => $do['title']
			];
		}

	break;

	//todo:вывести список источников
	case 'clientpath':

		$re = $db -> query("SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"id"          => (int)$do['id'],
				"title"       => $do['name'],
				"utm_source"  => $do['utm_source'],
				"destination" => $do['destination']
			];
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