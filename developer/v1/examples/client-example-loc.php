<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ERROR);

$baseurl = "http://sm.crm/developer/v1/client";//Адрес расположения CRM

$params['login'] = "zaharbor@isaler.ru";//существующий пользователь в системе
$params['apikey'] = 'gCG01Q5MA8msP1jXuQUC';//'aMgiCQyj8bCToNc47BZZYrRICoWSIl'; //получаем в Панели управления CRM

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

$params['word'] = 'сейлзмен';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'date_create'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
$params['user'] = '';//ограничение по login пользователя, пользователь должен быть в подчинении у текущего
$params['dateStart'] = '2015-01-01';//даты создания
$params['dateEnd'] = '';
$params['word'] = '';//строка поиска по полям title, des, phone, mail_url, site_url

//доп.фильтры по некоторым признакам. В работе - пока не работает:
//todo:отладить работу доп.фильтров
$params['filter']['relations'] = '';//string, фильтр по типу отношений (список доступен в методе guides в запросе relations)
$params['filter']['idcategory'] = '';//string, фильтр по отрасли
$params['filter']['territory'] = '';//string, фильтр по территории
$params['filter']['type'] = '';//string, фильтр по типу записи (client,person,partner,contractor,concurent)
$params['filter']['clientpath'] = '';//string, фильтр по источнику клиента
$params['filter']['trash'] = 'no';//string, yes/no, фильтр по признаку "в корзине"
$params['filter']['input1'] = '';//string, фильтр по доп.полю
//..
$params['filter']['input10'] = '';//string, фильтр по доп.полю
*/

/*пример
$params['filter']['trash'] = 'no';
$params['filter']['relations'] = '1 - Холодный клиент';
*/

//== для вывода только заданных полей из конкретной записи == list, info
//$params['fields'] = 'clid,title,phone,mail_url';

//== для вывода реквизитов == list, info
//$params['bankinfo'] = 'yes';

//== для конкретной записи == info, update
//$params['clid'] = '1781'; //указываем id записи
//$params['uid'] = 'dsfsETSDFSsdf345345'; //указываем uid записи

//== для добавления записи == add
/*
$params['type'] = 'client'; //тип клиента - client(юр.лицо) - по-умолчанию,person(физ.лицо),concurent,contractor,parnter
$params['user'] = '';
$params['uid'] = 'dsfsETSDFSsdf345345';
$params['title'] = 'Пробный клиент API plus 10';
$params['phone'] = '+7(342)2602020, +7(342)2602021';
$params['mail_url'] = 'test.api10@smapi.ru';
$params['site_url'] = '10-smapi.ru';
$params['territory'] = 'Пермь';
$params['clientpath'] = 'Сайт SalesManAPI.ru';
$params['tip_cmr'] = 'Потенциальный клиент';
$params['idcategory'] = 'Не определена';//отрасль
$params['date_create'] = '2015-05-30 23:05:30';//в часовом поясе аккаунта

$params['recv']['castUrName'] = 'ООО "Пробный клиент API plus 10"';
$params['recv']['castInn'] = '5904567834506';
$params['recv']['castKpp'] = '590404210';
$params['recv']['castBank'] = 'Филиал ОАО «УРАЛСИБ» в г. Уфа';
$params['recv']['castBankKs'] = '30101810600000000770';
$params['recv']['castBankRs'] = '40702810301220001991';
$params['recv']['castBankBik'] = '048073770';
$params['recv']['castOkpo'] = '01234567898';
$params['recv']['castOgrn'] = '1145958056260';
$params['recv']['castDirName'] = 'Иванова Михаила Петровича';
$params['recv']['castDirSignature'] = 'Иванов М.П.';
$params['recv']['castDirStatus'] = 'Генерального директора';
$params['recv']['castDirStatusSig'] = 'Генеральный директор';
$params['recv']['castDirOsnovanie'] = 'Устава';
$params['recv']['castUrAddr'] = 'г. Пермь, ул. Пушкина 106';
*/

//== для добавления нескольких записей == addlist
/*
$params['client'][0]['type'] = 'client'; //тип клиента - client(юр.лицо) - по-умолчанию,person(физ.лицо),concurent,contractor,parnter
$params['client'][0]['title'] = 'Пробный клиент API 01';
$params['client'][0]['phone'] = '+7(342)260-20-10, +7(342)260-20-11';
$params['client'][0]['mail_url'] = 'test.api01@yoolla-api.ru';
$params['client'][0]['site_url'] = '01.yoolla-api.ru';
$params['client'][0]['territory'] = 'Пермь';
$params['client'][0]['clientpath'] = 'Сайт YoollaAPI.ru';
$params['client'][0]['tip_cmr'] = 'Потенциальный клиент';
$params['client'][0]['idcategory'] = 'Не определена';//отрасль

$params['client'][0]['recv']['castUrName'] = 'ООО "Пробный клиент API 001"';
$params['client'][0]['recv']['castInn'] = '590456789006';
$params['client'][0]['recv']['castKpp'] = '590404210';
$params['client'][0]['recv']['castBank'] = 'Филиал ОАО «УРАЛСИБ» в г. Уфа';
$params['client'][0]['recv']['castBankKs'] = '30101810600000000770';
$params['client'][0]['recv']['castBankRs'] = '40702810301220001991';
$params['client'][0]['recv']['castBankBik'] = '048073770';
$params['client'][0]['recv']['castOkpo'] = '';
$params['client'][0]['recv']['castOgrn'] = '1145958040260';
$params['client'][0]['recv']['castDirName'] = 'Косых Виталия Владимировича';
$params['client'][0]['recv']['castDirSignature'] = 'Косых В.В.';
$params['client'][0]['recv']['castDirStatus'] = 'Генерального директора';
$params['client'][0]['recv']['castDirStatusSig'] = 'Генеральный директор';
$params['client'][0]['recv']['castDirOsnovanie'] = 'Устава';
$params['client'][0]['recv']['castUrAddr'] = 'г. Пермь, ул. Пушкина 66';

$params['client'][1]['type'] = 'client'; //тип клиента - client(юр.лицо) - по-умолчанию,person(физ.лицо),concurent,contractor,parnter
$params['client'][1]['title'] = 'Пробный клиент API 02';
$params['client'][1]['phone'] = '+7(342)260-30-10, +7(342)260-30-11';
$params['client'][1]['mail_url'] = 'test.api02@yoolla-api.ru';
$params['client'][1]['site_url'] = '02.yoolla-api.ru';
$params['client'][1]['territory'] = 'Пермь';
$params['client'][1]['clientpath'] = 'Сайт YoollaAPI.ru';
$params['client'][1]['tip_cmr'] = 'Перспективный клиент';
$params['client'][1]['idcategory'] = 'Не определена';//отрасль

$params['client'][1]['recv']['castUrName'] = 'ООО "Пробный клиент API 002"';
$params['client'][1]['recv']['castInn'] = '590456789006';
$params['client'][1]['recv']['castKpp'] = '590404210';
$params['client'][1]['recv']['castBank'] = 'Филиал ОАО «УРАЛСИБ» в г. Уфа';
$params['client'][1]['recv']['castBankKs'] = '30101810600000000770';
$params['client'][1]['recv']['castBankRs'] = '40702810301220001991';
$params['client'][1]['recv']['castBankBik'] = '048073770';
$params['client'][1]['recv']['castOkpo'] = '';
$params['client'][1]['recv']['castOgrn'] = '1145958040260';
$params['client'][1]['recv']['castDirName'] = 'Косых Виталия Владимировича';
$params['client'][1]['recv']['castDirSignature'] = 'Косых В.В.';
$params['client'][1]['recv']['castDirStatus'] = 'Генерального директора';
$params['client'][1]['recv']['castDirStatusSig'] = 'Генеральный директор';
$params['client'][1]['recv']['castDirOsnovanie'] = 'Устава';
$params['client'][1]['recv']['castUrAddr'] = 'г. Пермь, ул. Пушкина 66';

$params['client'][2]['type'] = 'client'; //тип клиента - client(юр.лицо) - по-умолчанию,person(физ.лицо),concurent,contractor,parnter
$params['client'][2]['title'] = 'Пробный клиент API 03';
$params['client'][2]['phone'] = '+7(342)260-40-10, +7(342)260-40-11';
$params['client'][2]['mail_url'] = 'test.api03@yoolla-api.ru';
$params['client'][2]['site_url'] = '03.yoolla-api.ru';
$params['client'][2]['territory'] = 'Пермь';
$params['client'][2]['clientpath'] = 'Сайт YoollaAPI.ru';
$params['client'][2]['tip_cmr'] = 'Потенциальный клиент';
$params['client'][2]['idcategory'] = 'Не определена';//отрасль

$params['client'][2]['recv']['castUrName'] = 'ООО "Пробный клиент API 003"';
$params['client'][2]['recv']['castInn'] = '590456789006';
$params['client'][2]['recv']['castKpp'] = '590404210';
$params['client'][2]['recv']['castBank'] = 'Филиал ОАО «УРАЛСИБ» в г. Уфа';
$params['client'][2]['recv']['castBankKs'] = '30101810600000000770';
$params['client'][2]['recv']['castBankRs'] = '40702810301220001991';
$params['client'][2]['recv']['castBankBik'] = '048073770';
$params['client'][2]['recv']['castOkpo'] = '';
$params['client'][2]['recv']['castOgrn'] = '1145958040260';
$params['client'][2]['recv']['castDirName'] = 'Косых Виталия Владимировича';
$params['client'][2]['recv']['castDirSignature'] = 'Косых В.В.';
$params['client'][2]['recv']['castDirStatus'] = 'Генерального директора';
$params['client'][2]['recv']['castDirStatusSig'] = 'Генеральный директор';
$params['client'][2]['recv']['castDirOsnovanie'] = 'Устава';
$params['client'][2]['recv']['castUrAddr'] = 'г. Пермь, ул. Пушкина 66';
*/

// Создаем подпись к параметрам
$urlparams = http_build_query($params);

print "<code>".$baseurl.'?'.$urlparams."</code><br><br>";

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