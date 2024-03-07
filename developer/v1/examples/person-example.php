<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ERROR);

$baseurl = "https://demo.isaler.ru/developer/v1/person";//Адрес расположения CRM

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

//указываем метод - fields (список доступных полей), list (список записей), info (информация по клиенту), add, addlist (групповое добавление), update, delete
$params['action'] = 'list';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'date_create'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
$params['user'] = '';//ограничение по login пользователя, пользователь должен быть в подчинении у текущего
$params['dateStart'] = '2015-03-20';//даты создания
$params['dateEnd'] = '';
$params['word'] = '';//строка поиска по полям title, des, phone, mail
//todo:отладить работу доп.фильтров
$params['filter']['clid'] = '';//integer, идентификатор клиента
$params['filter']['clientpath'] = '';//string, фильтр по источнику клиента
$params['filter']['input1'] = '';//string, фильтр по доп.полю
//..
$params['filter']['input10'] = '';//string, фильтр по доп.полю
*/
$params['filter']['clid'] = '1781';

//== для вывода только заданных полей из конкретной записи == list, info
//$params['fields'] = 'pid,person,ptitle,tel,mail';

//== для вывода социальных контактов == list, info
//$params['socinfo'] = 'yes';

//== для конкретной записи == info, update
//$params['pid'] = '2475'; //указываем id записи

//== для добавления записи == add
/*
$params['user'] = '';

$params['person'] = 'Апиров Апист Иванович';
$params['ptitle'] = 'Технический директор';
$params['tel'] = '+7(342)250-50-50, +7(342)290-50-51';
$params['mob'] = '+7(922)250-50-50';
$params['mail'] = 'test.api100@yoolla-api.ru';
$params['clientpath'] = 'Сайт YoollaAPI.ru';
$params['loyalty'] = 'Пока не понятно';

$params['socials']['blog'] = 'http://api.blog.rus';
$params['socials']['mysite'] = 'http://apiapi.rus';
$params['socials']['twitter'] = 'apiapi';
$params['socials']['icq'] = '123456789';
$params['socials']['skype'] = 'apiapi';
$params['socials']['google'] = 'apiapi';
$params['socials']['yandex'] = 'apiapi';
$params['socials']['mykrug'] = 'apiapi';
*/

//== для добавления нескольких записей == addlist
/*
$params['persons'][0]['person'] = 'Амиров Вахит';
$params['persons'][0]['ptitle'] = 'Крутой перец';
$params['persons'][0]['tel'] = '+7(342)280-20-10, +7(342)280-30-11';
$params['persons'][0]['mob'] = '+7(922)200-30-20';
$params['persons'][0]['mail'] = 'test.api200@yoolla-api.ru';
$params['persons'][0]['clientpath'] = 'Сайт YoollaAPI.ru';
$params['persons'][0]['loyalty'] = 'Пока не понятно';
//$params['date_create'] = '2015-05-30 23:05:30';//в часовом поясе аккаунта

$params['persons'][0]['socials']['blog'] = 'http://amirovv.blog.rus';
$params['persons'][0]['socials']['mysite'] = 'http://amirovv.rus';
$params['persons'][0]['socials']['twitter'] = 'amirovv';
$params['persons'][0]['socials']['icq'] = '098765432';
$params['persons'][0]['socials']['skype'] = 'amirovv';
*/

// Создаем подпись к параметрам
$urlparams = http_build_query($params);

print "<code>".$urlparams."</code><br><br>";

print_r($params);

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