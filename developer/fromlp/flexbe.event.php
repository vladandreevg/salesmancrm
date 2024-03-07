<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

header("Access-Control-Allow-Origin: *");

/**
 * скрипт получает уведомления в результате срабатывания Webhook для сайтов на платформе Flexbie.com
 */
error_reporting(0);

header('Access-Control-Allow-Origin: *');

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/settings.php";
include "../../inc/func.php";

//include "../../opensource/class/safemysql.class.php";

$response = $_REQUEST;
$apikey   = $_REQUEST['api_key'];

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8'));

//Найдем identity по настройкам
$res      = $db -> getRow("select id, api_key, timezone from ".$sqlname."settings where api_key = '".$apikey."'");
$api_key  = $res['api_key'];
$identity = $res['id'] + 0;
//$tmzone   = $res['timezone'];

if($identity == 0 || $api_key == ''){

	print "Error: Unknown or not exist APY-key";
	exit();

}

/*$tz = new DateTimeZone($tmzone);
$dz = new DateTime();
$dzz = $tz->getOffset($dz);
$bdtimezone = intval($dzz)/3600;

$db -> query("SET time_zone = '+".$bdtimezone.":00'");*/

//загружаем настройки модуля для аакаунта
$mdwset = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'");
$leadsettings = json_decode($mdwset['content'], true);
$coordinator  = $leadsettings["leadСoordinator"];
$users        = $leadsettings['leadOperator'];

function sendRequest($url, $params){

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if($result === false) return $err = curl_error($ch);
	else return $result;
}

/*$f = fopen($ypath . "worker.log", "a");
fwrite($f, array2string($_REQUEST) . "\r");
fwrite($f, "------------------------\r");
fclose($f);*/

//тестовые данные
/*$response = array(
	"event" => "lead",
	"site" => array(
		"name" => "Промо-сайт",
	),
	"data" => array(
		"client" => array(
			"name"  => "Андрон Коллайдеров",
			"phone" => "+79001654567",
			"email" => "andron.collayder@good.moon"
		),
		"page" => array(
			"name" => "Тестовый лендинг",
			"url" => "http://skaterty.ru/lead/"
		),
		"utm" => array(
			"utm_source" => "campone",
			"utm_campaign" => "Кампания",
			"utm_medium" => "Тип трафика",
			"utm_term" => "Ключевое слово",
			"utm_content" => "Тип объявления",
			"url" => "Полный адрес страницы"
		)
	)
);*/

//читаем событие по заявке
if( $response['event'] == 'lead'){

	$data        = $response['data'];
	$utm         = "";
	$description = "";
	$olduser     = 0;

	$utm_array = $response['data']['utm'];
	foreach($utm_array as $k => $v){

		$utm .= $k.":".$v."; ";

		$params[$k] = $v;

	}

	$description .= "Страница: ".$response['data']['page']['name']."; ";
	$description .= "Форма: "   .$response['data']['form_name']."; ";
	$description .= "Данные: "  .json_encode_cyr($response['data']['form_data'])."; ";
	$description .= $utm;

	$params['action']      = "add";
	$params['path']        = $response['site']['name'];
	$params['title']       = $response['data']['client']['name'];
	$params['email']       = $response['data']['client']['email'];
	$params['phone']       = prepareMobPhone($response['data']['client']['phone']);
	$params['description'] = $description;
	$params['apikey']      = $api_key;

	$params['login']       = $db -> getOne("SELECT login FROM ".$sqlname."user WHERE iduser = '$coordinator' and identity = '".$identity."'");

	//отправляем заявку
	$result = sendRequest(isset($_SERVER['HTTP_SCHEME']) ? $_SERVER['HTTP_SCHEME'] : (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER["HTTP_HOST"]."/developer/v1/leads",$params);

	$result = json_decode($result, true);

	/*$f = fopen($ypath . "leadsworker.log", "a");
	fwrite($f, array2string($response) . "\r");
	fwrite($f, "------------------------\r");
	fwrite($f, array2string($params) . "\r");
	fwrite($f, "------------------------\r");
	fwrite($f, array2string($result) . "\r");
	fwrite($f, "========================\r\r");
	fclose($f);*/

	print 'ok';

}
else{

	print 'Empty request';

}
?>