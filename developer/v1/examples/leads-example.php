<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(0);

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

$baseurl = "https://demo.isaler.ru/developer/v1/leads";//Адрес расположения CRM

$params['login']  = "zaharbor@isaler.ru"; //существующий пользователь в системе
$params['apikey'] = 'eY8VqURqbsHLtB4SOmXDjdkoz2pL5k'; //получаем в Панели управления CRM

// Создаём POST-запрос
/*
$params['title']       = "Гарик Мартиросян"; //ФИО контакта
$params['email']       = "garic.martirosyan@comedy.club";
$params['phone']       = "+75005007777";
$params['company']     = "Comedy Club"; //название клиента - организации
$params['description'] = "The best crm practice"; //описание от клиента, текст
$params['ip']          = $_SERVER['REMOTE_ADDR']; //ip
$params['country']     = "Ruusia"; //страна
$params['city']        = "Perm"; //город
$params['path']        = "Test Path"; //источник клиента, канал
$params['partner']     = ""; //сайт партнера
*/

//utm-метки
/*
$params['utm_source']    = "facebook";
$params['utm_medium']    = "cpc";
$params['utm_campaign']  = "First Campaign";
$params['utm_term']      = "crm для малого бизнеса";
$params['utm_content']   = "";
$params['utm_referrer']  = "yandex.ru";
*/

//указываем метод - add, info, stat, list
$params['action']        = 'list';

//== для записи == info
//$params['id'] = 12;

//== для списка записей == list

$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'datum'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
$params['dateStart'] = '2016-03-20';//даты
$params['dateEnd'] = '2017-03-29';


// Устанавливаем соединение
$urlparams = http_build_query($params);

print "<code>".$urlparams."</code><br><br>";

$res = Send($baseurl, $urlparams);

print $res;

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);
print_r($result);

/*
Пример положительного ответа
$result['result']['text'] = 'Success';
$result['result']['id'] = id;

+ координатору отправляется email-уведомление о лиде
*/

if($result['result'] == 'Error') {

	print "Ошибка: ".$result['error']['text'];
	exit();
}

exit();
?>