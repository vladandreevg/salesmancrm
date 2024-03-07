<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ERROR);

$baseurl = "https://demo.isaler.ru/developer/v1/history";//Адрес расположения CRM

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

//указываем метод - tips (список типов активностей), fields (список доступных полей), list (список записей), info (информация по записи), add, addlist (групповое добавление), delete
$params['action'] = 'list';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'datum'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
$params['dateStart'] = '2015-03-20';//даты
$params['dateEnd'] = '';
$params['word'] = '';//строка поиска по полям title, des
*/

//== для конкретной записи == info, update
$params['cid'] = '2939'; //указываем id записи

//== для добавления записи == add, update
$params['user'] = '';//login пользователя, которому ставим напоминание
$params['datum'] = '2015-05-11';
$params['des'] = 'Yoolla - это веб-приложение класса CRM, которое позволяет организовать эффективную работу отдела продаж без дополнительных затрат на внедрение';
$params['tip'] = 'Встреча';
$params['clid'] = '1815';
$params['pid'][] = '';
$params['did'] = '';

//== для добавления нескольких записей
$params[0]['user'] = '';//login пользователя, которому ставим напоминание
$params[0]['datum'] = '2016-05-20';
$params[0]['des'] = 'Описание активности';
$params[0]['tip'] = 'Встреча';
$params[0]['clid'] = '1815';
$params[0]['pid'][] = '';
$params[0]['did'] = '';

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