<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

require_once "../../../inc/config.php";
require_once "../../../inc/dbconnector.php";
require_once "../../../inc/func.php";
require_once "../../../inc/licloader.php";

/**
 * Отправка данных в API в формате JSON
 */

error_reporting(E_ERROR);

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2";

//существующий пользователь в системе
DEFINE("LOGIN", "vladislav@isaler.ru");

//получаем в Панели управления CRM
DEFINE("KEY", "t1xdeOwWSIqgDol70CkRdK3WD4N4cm");

$params = [
	"action" => 'info',
	"id"     => 315
];

function SendRequest($url, $params, $header = []) {

	$result = new stdClass();

	$headers = [];

	if (!empty($header)) {

		$headers[] = "Content-Type: application/json";

		foreach ($header as $key => $head)
			$headers[] = $key.": ".$head;

	}

	$POST = (is_array($params)) ? json_encode($params) : $params;

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_HEADER, 0);
	if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result -> response = curl_exec($ch);
	$result -> info     = curl_getinfo($ch);
	$result -> error    = curl_error($ch);

	return $result;

}

/**
 * Отправляем запрос с параметрами
 */
$r = SendRequest($baseurl."/akt", $params, [
	'login'  => LOGIN,
	'apikey' => KEY
]);

/**
 * Выводим ответ из CRM
 */
print_r($r -> response);