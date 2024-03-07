<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ALL);

$baseurl = "http://sm20179.crm/developer/v1/task";//Адрес расположения CRM

$params['login'] = "vladislav@isaler.ru"; //существующий пользователь в системе
$params['apikey'] = 'gCG01Q5MA8msP1jXuQUC'; //получаем в Панели управления CRM

function Send($url, $POST){
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	//print_r($result);

	if($result === false) print $err = curl_error($ch);

	return $result;
}

//указываем метод - tips (список типов напоминаний), fields (список доступных полей), list (список записей), info (информация по клиенту), add, addlist (групповое добавление), update, delete, addhist (добавление записи в историю)
$params['action'] = 'add';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'datum'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
//$params['dateStart'] = '2015-03-20';//даты
$params['dateEnd'] = '';
$params['word'] = '';//строка поиска по полям title, des
*/

//== для конкретной записи == info, update
//$params['tid'] = '2939'; //указываем id записи

//== для добавления записи == add, update

$params['mailalert'] = 'no';//отправлять уведомление по email
$params['user'] = '';//login пользователя, которому ставим напоминание
$params['datum'] = '2018-03-03';
$params['totime'] = '15:35';
$params['title'] = 'Перезвонить клиенту с сайта 52';
$params['des'] = 'Yoolla - это веб-приложение класса CRM, которое позволяет организовать эффективную работу отдела продаж без дополнительных затрат на внедрение';
$params['tip'] = 'Задача';
$params['priority'] = '2'; //важность (1 – не важно, 0 – нормально*, 2 – важно)
$params['speed'] = '0'; //срочность (1 – не срочно, 0 – нормально*, 2 – срочно)
$params['clid'] = '';
$params['pid'] = '';
$params['did'] = '';

//== для конкретной записи == doit
$params['description'] = 'Выполнено напоминание 4';

// Создаем подпись к параметрам
$urlparams = http_build_query($params);

print "<code>".$urlparams."</code><br><br>";

//print_r($params);

// Устанавливаем соединение
$res = Send($baseurl, $urlparams);

//print $res;

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if($result['result'] == 'Error') {
	print "Ошибка: ".$result['error']['text'];
	exit();
}

print_r($result);

exit();
?>