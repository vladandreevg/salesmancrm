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
$ypath    = $rootpath."/plugins/userNotifier/";

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/vendor/core.php";

$indata  = json_decode(file_get_contents('php://input'), true);
$apikey  = $_REQUEST['api_key'];
$botname = $_REQUEST['botname'];

$indata['api_key'] = $apikey;
$indata['botname'] = $botname;

$path = $rootpath."/plugins/userNotifier/data/";

//Запись массива в файл
function logIt($array, $name) {

	$string = is_array($array) ? array2string($array) : $array;
	file_put_contents('../data/telegram-webhooks.log', current_datumtime()."$name\n$string\n\n", FILE_APPEND);

}

//Найдем identity по настройкам
$res      = $db -> getRow("select id, api_key, timezone from ".$sqlname."settings where api_key = '$apikey'");
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

$db -> query("SET time_zone = '+".$bdtimezone.":00'");

$settings = json_decode(file_get_contents($ypath.'data/'.$fpath.'settings.json'), true);

$proxy = [];

if(!empty($settings['proxy'])){

	$proxy = $settings['proxy'];
	$proxy["type"] = CURLPROXY_SOCKS5;

}

/**
 * работаем с данными
 */

$username = $indata['message']['from']['username'];
$userid   = $indata['message']['from']['id'];
$chatid   = $indata['message']['chat']['id'];

//подписываем пользователя на обновления
if ($indata['message']['text'] == '/start') {

	$text = '';

	$user = $db -> getRow("select * from ".$sqlname."usernotifier_users WHERE username = '$username'");

	if ((int)$user['id'] > 0) {

		$db -> query("UPDATE ".$sqlname."usernotifier_users SET ?u WHERE id = '$user[id]'", [
			"chatid" => $chatid,
			"userid" => $userid
		]);

		$text = 'Подписка оформлена';

	}
	else {

		$text = 'Пользователь не найден';

	}

	$bot = $db -> getRow("select * from ".$sqlname."usernotifier_bots WHERE name = '$botname'");

	$telegram = new Telegram($bot['token'], true, $proxy);
	$result   = $telegram -> sendMessage([
		"chat_id"    => $chatid,
		"text"       => $text,
		"parse_mode" => 'HTML'
	]);

	logIt($result, "RESULT");

	//$result = json_decode(outSender('https://api.telegram.org/bot'.$bot['token'].'/sendMessage', array("chat_id" => "@".$user['username'], "text" => $text)), true);

}

//отписываем пользователя от уведомлений
elseif ($indata['message']['text'] == '/stop') {

	$text = '';

	$user = $db -> getRow("select * from ".$sqlname."usernotifier_users WHERE username = '$username'");

	if ((int)$user['id'] > 0) {

		$db -> query("UPDATE ".$sqlname."usernotifier_users SET ?u WHERE id = '$user[id]'", [
			"chatid" => '',
			"userid" => ''
		]);

		$text = 'Пользователь отписан от уведомлений';

	}
	else {

		$text = 'Пользователь не найден';

	}

	$bot = $db -> getRow("select * from ".$sqlname."usernotifier_bots WHERE name = '$botname'");

	$telegram = new Telegram($bot['token'], true, $proxy);
	$result   = $telegram -> sendMessage([
		"chat_id"    => $chatid,
		"text"       => $text,
		"parse_mode" => 'HTML'
	]);

	logIt($result, "RESULT");

}

//принимаем данные
else {

	$bot = $db -> getRow("select * from ".$sqlname."usernotifier_bots WHERE name = '$botname'");

	$text = "Привет! Меня зовут $bot[name]. Вы сказали: <em>\"".$indata['message']['text']."\"</em>";

	$telegram = new Telegram($bot['token'], true, $proxy);
	$result   = $telegram -> sendMessage([
		"chat_id"    => $chatid,
		"text"       => $text,
		"parse_mode" => 'HTML'
	]);

	logIt($result, "RESULT");

}

print "100";