<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

header('Access-Control-Allow-Origin: *');// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Content-Type: text/html; charset=utf-8');

error_reporting(0);

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/settings.php";
include "../../inc/func.php";

/*if(!file_exists("./yoolla.log")) {
	$file = fopen("./yoolla.log", "w");
	fclose($file);
}
ini_set('log_errors', 'On');
ini_set('error_log', './yoolla.log');*/

function Cleaner($string){
	$string = trim($string);
	$string = str_replace('"','”',$string);
	$string = str_replace('\n\r','',$string);
	$string = str_replace("'","&acute;",$string);
	return $string;
}

foreach($_REQUEST as $key => $value){
	$params[$key] = Cleaner($value);
}

//доступные методы
$aceptedActions = array("user","users","useradd","category","territory","relations","mycompany");

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

//ищем аккаунт по apikey
$result = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '".$params['apikey']."'");
$identity = $result['id'];
$api_key = $result['api_key'];
$timezone= $result['timezone'];

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//найдем пользователя
$result = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '".$params['login']."' and identity = '".$identity."'");
$iduser = $result['iduser'];
$username = $result['title'];

$Error = ''; $response = array();

//проверяем api-key
if(!$identity){
	$response['result'] = 'Error';
	$response['error']['code'] = '400';
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';
}
//проверяем пользователя
elseif(!$username){
	$response['result'] = 'Error';
	$response['error']['code'] = '401';
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';
}
//проверяем метод
elseif(!in_array($params['action'], $aceptedActions)){
	$response['result'] = 'Error';
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';
}

if($Error != 'yes'){

	switch($params['action']){

	case 'user':

		if($params['uid'] != '') $s = "uid = '".$params['uid']."'";
		elseif($params['login'] != '') $s = "login = '".$params['login']."'";
		elseif($params['iduser'] != '') $s = "iduser = '".$params['iduser']."'";

		$re = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE $s and identity = '$identity'");
		$title     = $re["title"];
		$login     = $re["login"];
		$otdel     = $re["otdel"];
		$phone     = $re["phone"];
		$phone_in  = $re["phone_in"];
		$mob       = $re["mob"];
		$mail_url  = $re["email"];
		$uid       = $re["uid"];
		$iduser    = $re["iduser"];
		$tip       = $re["tip"];
		$user_post = $re["user_post"];
		$secrty    = $re["secrty"];

		$oid = $db -> getOne("select uid from ".$sqlname."otdel_cat where idcategory='".$otdel."' and identity = '$identity'");

		if($login != '') $response['data'] = array("iduser" => $iduser, "uid" => $uid, "oid" => $oid, "title" => $title, "login" => $login, "active" => $secrty, "tip" => $tip, "user_post" => $user_post, "email" => $mail_url, "phone" => $phone, "phone_in" => $phone_in, "mob" => $mob);
		else {
			$response['error']['code'] = '404';
			$response['error']['text'] = 'Пользователь не найден';
		}

	break;

	case 'users':

		$re = $db -> query("SELECT * FROM ".$sqlname."user WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){

			$otdel = $db -> getOne("SELECT uid FROM ".$sqlname."otdel_cat WHERE idcategory = '".$do['otdel']."' and identity = '$identity'");

			$response['data'][] = array("iduser" => $do['iduser'],"uid" => $do['uid'],"oid" => $otdel,"title" => $do['title'],"login" => $do['login'],"email" => $do['email'],"active" => $do['secrty']);

		}

	break;

	case 'useradd': //work

		$login = untag($params['login']);
		$pwd = $params['password'];

		$title = untag($params['title']);
		$tip = $params['tip'];
		$mid = $params['mid'];
		$mail = untag($params['email']);

		if($isCloud == true) $mail = $login;

		$otdel = $params['otdel'];
		$territory = $params['territory'];

		$phone = untag($params['phone']);
		$phone_in = untag($params['phone_in']);
		$fax = untag($params['fax']);
		$mob = untag($params['mob']);
		$bday = $params['bday'];
		$user_post = untag($params['user_post']);
		$uid = untag($params['uid']);

		$CompStart = $params['CompStart'];
		$CompEnd = $params['CompEnd'];

		$acs_analitics = 'on';
		$acs_maillist = '';
		$acs_files = 'on';
		$acs_price = 'on';
		$acs_credit = '';
		$acs_prava = '';
		$acs_plan = 'on';
		$show_marga = 'yes';
		$ac_import = 'off;on;off;off;off;off;off;off;on;on;on;on;on;on;on;on;on;off;off';
		$secrty = '';
		$isadmin = '';

		$salt = generateSalt();
		$newpass = encodePass($pwd,$salt);

		//проверка на существующий емейл
		$count = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."user WHERE login='".trim($login)."'");

		if($count > 0) {

			$response['result'] = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = 'Ошибка: Такой логин уже существует';

		}
		else{

			try {

				$db -> query("insert into ".$sqlname."user (iduser, login, pwd, sole, title, tip, mid, otdel, email, phone, fax, mob, bday, acs_analitics, acs_maillist, acs_files, acs_price, acs_credit, acs_prava, tzone, viget_on, viget_order, secrty, isadmin, acs_import, show_marga, user_post, acs_plan, CompStart, CompEnd, subscription, uid, identity) values(null, '$login', '$newpass', '$salt', '$title', '$tip','$mid','$otdel','$mail','$phone','$fax','$mob','$bday', '$acs_analitics', '$acs_maillist', '$acs_files', '$acs_price', '$acs_credit', '$acs_prava', '0:00', 'on;on;on;on;on;on;on;on;on;on;on;on;on;on', 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11;d12;d13;d14', '$secrty', '$isadmin','$ac_import','$show_marga', '$user_post', '$acs_plan', '$CompStart', '$CompEnd','on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on', '$uid', '$identity')");

				$response['result'] = 'Success';
				$response['result']['data'] = $db -> insertId();

			}
			catch (Exception $e){

				$response['result'] = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

			}

		}

	break;

	case 'category':

		$re = $db -> query("SELECT * FROM ".$sqlname."category WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){
			$response['data'][] = array("title" => $do['title'],"tip" => $do['tip']);
		}

	break;

	case 'relations':

		$re = $db -> query("SELECT * FROM ".$sqlname."relations WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){
			$response['data'][] = array("title" => $do['title'],"color" => $do['color']);
		}

	break;

	case 'mycompany':

		$re = $db -> query("SELECT * FROM ".$sqlname."mycomps WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){
			$response['data'][] = array("mcid" => $do['id'],"title" => $do['name_shot']);
		}

	break;

	case 'mybank':

		$re = $db -> query("SELECT * FROM ".$sqlname."mycomps_recv WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){
			$response['data'][] = array("id" => $do['id'],"mcid" => $do['cid'],"title" => $do['title']);
		}

	break;

	//todo:вывести список источников
	case 'clientpath':

		$re = $db -> query("SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){
			$response['data'][] = array("id" => $do['id'],"title" => $do['name'],"utm_source" => $do['utm_source'],"destination" => $do['destination']);
		}

	break;

	default:
		$response['error']['code'] = '404';
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
	break;

}

}

print $rez = json_encode_cyr($response);

include "logger.php";
?>