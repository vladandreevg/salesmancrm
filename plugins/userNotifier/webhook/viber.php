<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: *');

$rootpath = dirname( __DIR__, 3 );
$ypath    = dirname( __DIR__, 3 )."/plugins/userNotifier/";

if (!file_exists($ypath."/data/viber_error.log")) {

	$file = fopen($ypath."/data/viber_error.log", "w");
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $ypath."/data/viber_error.log");

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/vendor/core.php";
require_once $ypath."/vendor/Manager.php";

$indata = json_decode(file_get_contents('php://input'), true);

if(!in_array($indata['event'], ["message", "conversation_started"])) {
	goto ext;
}


$indata['api_key'] = $_REQUEST['apikey'];
$indata['botid']   = $_REQUEST['botid'];

//$path = "../data/";
$path = $rootpath."/plugins/userNotifier/data/";

//Запись массива в файл
function logIt($array, $name) {

	global $ypath;

	$string = is_array($array) ? array2string($array) : $array;
	file_put_contents($ypath.'/data/viber-webhooks.log', current_datumtime()."$name\n$string\n\n", FILE_APPEND);

}

//print_r($indata);

//Найдем identity по настройкам
$res      = $db -> getRow("select id, api_key, timezone from ".$sqlname."settings where api_key = '$indata[api_key]'");
$tmzone   = $res['timezone'];
$api_key  = $res['api_key'];
$identity = $res['id'] + 0;

require_once $rootpath."/inc/settings.php";

date_default_timezone_set($tmzone);

logIt($indata, "INPUT");

//проверяем валидность входящих запросов
if ($identity == 0 || $api_key == '') {

	print "Error: Unknown or not exist APY-key";

	logIt(["Error" => "Unknown or not exist APY-key"], "INPUT");

	exit();

}

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = $dzz / 3600;

$db -> query("SET time_zone = '+$bdtimezone:00'");

/**
 * работаем с данными
 */

$username = $indata['sender']['name'];
$userid   = $indata['sender']['id'];
$text     = (string)$indata['message']['text'];

$bot = Manager ::BotInfo($indata['botid']);

//print_r($bot);

//получено сообщение
if ($indata['event'] == "message" || $indata['event'] == "conversation_started") {

	//Ищем сотрудника
	$userinfo = Manager ::UserInfo($userid);

	file_put_contents($ypath."/data/viber.json", json_encode_cyr($userinfo));

	//print_r($userinfo);

	$msg['type']     = 'text';
	$msg['receiver'] = $userid;

	//Если пользователь найден, но уже подписан
	if ($userinfo['id'] > 0 && $userinfo['isunlock'] && $userinfo['active']) {

		$msg['message'] = "Привет, $userinfo[user]!\nТы уже подписан на уведомления.\nКстати, вы сказали \"$text\"";

	}

	//Если пользователь найден, но заблокирован
	elseif ($userinfo['id'] > 0 && (!$userinfo['isunlock'] || !$userinfo['active'])) {

		$msg['message'] = "Привет, $userinfo[user]!\nТвой аккаунт в CRM заблокирован администратором, увы и ах!";

	}

	//Если пользователь не найден, то ищем из текста
	elseif($userinfo['id'] == 0) {

		//Найдем пользователя по номеру телефона (если он прислал номер)
		$array  = getPhoneFromText( $text );
		$phone  = substr(preg_replace("/[^0-9]/", "", array_shift( $array )), 1);

		$array1 = getEmailFromText( $text );
		$email  = array_shift( $array1 );

		if ($email != '' || $phone != '') {

			$s = '';

			if ($email != '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$s .= "(login = '$email' OR email = '$email') AND";
			}
			elseif ($phone != '' && isPhoneMobile("7".$phone)) {
				$s .= "(mob LIKE '%$phone') AND";
			}

			$iduser = ($s != '') ? (int)$db -> getOne("SELECT iduser FROM ".$sqlname."user WHERE $s identity = '$identity'") : 0;

		}

		if ($iduser > 0) {

			$uarg = [
				"botid"    => $bot['id'],
				"iduser"   => $iduser,
				"userid"   => $userid,
				"username" => $username
			];

			$u   = new Manager();
			$res = $u -> UserSave(0, $uarg);

			$msg['message'] = "Привет, ".current_user($iduser, "yes")."!\n\nТы успешно подписан на уведомления.\nЯ буду передавать тебе сообщения из SalesMan CRM.";

		}
		elseif (($email != '' || $phone != '') && $iduser == 0) {

			$msg['message'] = "Привет, Незнакомец!\n\nЯ всё еще тебя не знаю.\nЧтобы подписаться на уведомления сообщи мне свой ЛОГИН, EMAIL или МОБИЛЬНЫЙ (11 цифр) от SalesMan CRM.";

		}
		else {

			$msg['message'] = "Привет, Незнакомец!\n\nЧтобы подписаться на уведомления сообщи мне свой ЛОГИН, EMAIL или МОБИЛЬНЫЙ (11 цифр) от SalesMan CRM.";

		}

	}

	else {

		$msg['message'] = "Привет, $userinfo[user]!\nТвой аккаунт в CRM заблокирован админимстратором, увы и ах!";

	}

	$sender = [
		"name" => $bot['name'],
	];

	$viber = new Viber($bot['token']);
	$viber -> sendMessage($msg['type'], $msg['message'], $msg['receiver'], $sender);

}

ext:

print "100";