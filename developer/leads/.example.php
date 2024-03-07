<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 7.75          */
/* ============================ */

error_reporting(0);

$config['url'] = "https://crm.yoolla.ru/api/leads/connector.php";//Адрес расположения CRM
$config['api_key'] = 'aMgiCQyj8bCToNc47BZZYrRICoWSIl';//получаем в Панели управления CRM

// Создаём POST-запрос
$params['title'] = $_REQUEST['title'];
$params['email'] = $_REQUEST['email'];
$params['phone'] = $_REQUEST['phone'];
$params['company'] = $_REQUEST['company'];
$params['description'] = $_REQUEST['description'];
$params['ip'] = $_SERVER['REMOTE_ADDR'];
$params['country'] = $_REQUEST['country'];
$params['city'] = $_REQUEST['city'];
$params['path'] = $_SERVER['SERVER_NAME'];
$params['pertner'] = $_REQUEST['pertner'];

$params['hash'] = $config['api_key'];

// Устанавливаем соединение
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_URL, $config['url']);

$res = curl_exec($ch);

if(curl_errno($ch)) $err = curl_error($ch);
curl_close($ch);

if($err) {
	print 'Ошибка curl: '.$err;
	exit();
}

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);
print_r($result);

if($result['error']['code'] == 1) {
	print "Ошибка: ".$result['error']['text'];

	exit();
}

exit();
?>