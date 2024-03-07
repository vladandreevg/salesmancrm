<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');

require_once "../../../inc/config.php";
require_once "../../../inc/dbconnector.php";
require_once "../../../inc/func.php";

require_once "../vendor/core.php";

$indata  = json_decode(file_get_contents('php://input'), true);
$apikey  = $_REQUEST['api_key'];
$botname = $_REQUEST['botname'];

$indata['api_key'] = $apikey;
$indata['botname'] = $botname;

$path = "../../../plugins/sendStatistic/data/";

//Найдем identity по настройкам
$res      = $db -> getRow("select id, api_key, timezone from ".$sqlname."settings where api_key = '".$apikey."'");
$tmzone   = $res['timezone'];
$api_key  = $res['api_key'];
$identity = $res['id'] + 0;

date_default_timezone_set($tmzone);

$f = fopen($path."slack-webhooks.log", "a");
fwrite($f, current_datumtime()." :: ".json_encode_cyr($indata));
fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n");
fclose($f);

//проверяем валидность входящих запросов
if ($identity == 0 || $api_key == '') {

	print "Error: Unknown or not exist APY-key";
	exit();

}

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = intval($dzz) / 3600;

$db -> query("SET time_zone = '+".$bdtimezone.":00'");

/**
 * работаем с данными
 */

$username = $indata['message']['from']['username'];
$userid   = $indata['message']['from']['id'];
$chatid   = $indata['message']['chat']['id'];

//подписываем пользователя на обновления
if ($indata['message']['text'] == '/start') {

	$text = '';

	$user = $db -> getRow("select * from ".$sqlname."sendStatistic_users WHERE username = '$username'");

	if ($user['id'] > 0) {

		$db -> query("UPDATE ".$sqlname."sendStatistic_users SET ?u WHERE id = '$user[id]'", array(
			"chatid" => $chatid,
			"userid" => $userid
		));

		$text = 'Подписка оформлена';

	}
	else {

		$text = 'Пользователь не найден';

	}

	require "../vendor/TelegramBotPHP-master/Telegram.php";

	$bot = $db -> getRow("select * from ".$sqlname."sendStatistic_bots WHERE name = '$botname'");

	$telegram = new Telegram($bot['token']);
	$result   = $telegram -> sendMessage(array(
		"chat_id"    => $chatid,
		"text"       => $text,
		"parse_mode" => 'HTML'
	));

	$f = fopen($path."slack-webhooks.log", "a");
	fwrite($f, current_datumtime()." :: ".json_encode_cyr($result));
	fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
	fclose($f);

	//$result = json_decode(outSender('https://api.telegram.org/bot'.$bot['token'].'/sendMessage', array("chat_id" => "@".$user['username'], "text" => $text)), true);

}

//отписываем пользователя от уведомлений
elseif ($indata['message']['text'] == '/stop') {

	$text = '';

	$user = $db -> getRow("select * from ".$sqlname."sendStatistic_users WHERE username = '$username'");

	if ($user['id'] > 0) {

		$db -> query("UPDATE ".$sqlname."sendStatistic_users SET ?u WHERE id = '$user[id]'", array(
			"chatid" => '',
			"userid" => ''
		));

		$text = 'Пользователь отписан от уведомлений';

	}
	else {

		$text = 'Пользователь не найден';

	}

	require "../vendor/TelegramBotPHP-master/Telegram.php";

	$bot = $db -> getRow("select * from ".$sqlname."sendStatistic_bots WHERE name = '$botname'");

	$telegram = new Telegram($bot['token']);
	$result   = $telegram -> sendMessage(array(
		"chat_id"    => $chatid,
		"text"       => $text,
		"parse_mode" => 'HTML'
	));

	$f = fopen($path."slack-webhooks.log", "a");
	fwrite($f, current_datumtime()." :: ".json_encode_cyr($result));
	fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
	fclose($f);


}

//принимаем данные
else {

	require "../vendor/TelegramBotPHP-master/Telegram.php";

	$bot = $db -> getRow("select * from ".$sqlname."sendStatistic_bots WHERE name = '$botname'");

	$text = "Привет! Меня зовут $bot[name]. Вы сказали: <em>\"".$indata['message']['text']."\"</em>";

	$telegram = new Telegram($bot['token']);
	$result   = $telegram -> sendMessage(array(
		"chat_id"    => $chatid,
		"text"       => $text,
		"parse_mode" => 'HTML'
	));

	$f = fopen($path."slack-webhooks.log", "a");
	fwrite($f, current_datumtime()." :: ".json_encode_cyr($result));
	fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
	fclose($f);

}

print "100";

?>