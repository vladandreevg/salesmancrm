<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ERROR);

$baseurl = "https://demo.isaler.ru/developer/v1/entry";//Адрес расположения CRM

$params['login'] = "zaharbor@isaler.ru"; //существующий пользователь в системе
$params['apikey'] = 'eY8VqURqbsHLtB4SOmXDjdkoz2pL5k'; //получаем в Панели управления CRM

function Send($url, $POST){
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if($result === false) print $err = curl_error($ch);

	return $result;
}

//указываем метод - list (список записей), info (информация по клиенту), add, update (обновление записи)
$params['action'] = 'update';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'datum'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
$params['dateStart'] = '2015-03-20';//даты
$params['dateEnd'] = '';
*/

//== для конкретной записи == info, update
$params['id'] = '56'; //указываем id записи

//== для обновления записи == update
$params['uid'] = '125';//установка uid внешней системы


// Создаем подпись к параметрам
$urlparams = http_build_query($params);

print "<code>".$urlparams."</code><br><br>";

//print_r($params);

// Устанавливаем соединение
$res = Send($baseurl, $urlparams);

print $res;

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if($result['result'] == 'Error') {
	print "Ошибка: ".$result['error']['text'];
	exit();
}

print_r($result);

exit();
?>