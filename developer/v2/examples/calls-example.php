<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ERROR);

require_once "../../../inc/config.php";
require_once "../../../inc/dbconnector.php";
require_once "../../../inc/func.php";
require_once "../../../inc/licloader.php";

$baseurl = "https://demo.isaler.ru/developer/v1/calls";//Адрес расположения CRM

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

/*
 * указываем метод - list (список записей), add (добавление нескольких записей)
 */
$params['action'] = 'add';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['first'] = ''; //направление сортировки; new - по-умолчанию сначала новые, old - сначала более старые
$params['dateStart'] = '2015-01-01';//даты создания
$params['dateEnd'] = '';
$params['phone'] = '';//строка поиска по номеру телефона
$params['operator'] = '';//логин оператора - ограничение по оператору
$params['direct'] = '';//направление вызова: income - входящий, outcome - исходящий
*/

//== для добавления записи == add

$params['calls'][0]['uid'] = '100001';//уникальный идентификатор звонка, string(255)
$params['calls'][0]['did'] = '73422545577';//номер телефона для кол-трекинга
$params['calls'][0]['datum'] = '2015-07-23 13:10:23';//время вызова
$params['calls'][0]['direct'] = 'income';//направление вызова income, outcome, inner
$params['calls'][0]['res'] = 'ANSWERED';//результат вызова ANSWERED, NO ANSWER, BUSY
$params['calls'][0]['sec'] = '78';//продолжительность в секундах
$params['calls'][0]['src'] = '89208468782';//инициатор вызова
$params['calls'][0]['dst'] = '1005';//цель вызова
$params['calls'][0]['file'] = 'http://sip-operator/records/100001.mp3';//цель вызова

$params['calls'][1]['uid'] = '100002';
$params['calls'][1]['did'] = '73422545577';
$params['calls'][1]['datum'] = '2015-07-23 14:22:32';
$params['calls'][1]['direct'] = 'outcome';
$params['calls'][1]['res'] = 'ANSWERED';
$params['calls'][1]['sec'] = '123';
$params['calls'][1]['src'] = '89223289466';
$params['calls'][1]['dst'] = '1006';
$params['calls'][1]['file'] = 'http://sip-operator/records/100002.mp3';

// Создаем подпись к параметрам
$urlparams = http_build_query($params);

print "<code>".$urlparams."</code><br><br>";

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