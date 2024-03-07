<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2018.6         */
/* ============================ */

error_reporting(E_ERROR);

require_once "../../../inc/licloader.php";
require_once "../../../inc/config.php";
require_once "../../../inc/dbconnector.php";
require_once "../../../inc/func.php";

//Адрес расположения CRM
$baseurl = "https://demo.isaler.ru/developer/v2/client";

//Адрес расположения CRM
$baseurl = "http://sm2020.crm/developer/v2/client";

//существующий пользователь в системе
DEFINE("LOGIN", "vladislav@isaler.ru");

//получаем в Панели управления CRM
DEFINE("KEY", "t1xdeOwWSIqgDol70CkRdK3WD4N4cm");

function Send($url, $POST) {
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if ($result === false) print $err = curl_error($ch);

	return $result;
}

/**
 *
 * action - Поддерживаемые методы
 *   - fields     (список доступных полей)
 *   - info       (информация по клиенту)
 *   - list       (список записей)
 *   - add        (добавление записи)
 *   - addlist    (групповое добавление)
 *   - update     (редактирование записи, обновляет только указанные поля)
 *   - delete     (удаление записи)
 *
 */

//== Пример массива параметров для вывода списка записей == fields
$params0 = [
	"login"    => LOGIN,
	"apikey"   => KEY,
	// указываем метод
	"action"   => 'fields'
];

//== Пример массива параметров для вывода списка записей == info
$params1 = [
	"login"    => LOGIN,
	"apikey"   => KEY,
	// указываем метод
	"action"   => 'info',
	// id клиента
	"clid"     => '1781',
	// или uid клиента
	//"uid"      => '1001',
	// или uid клиента
	"inn"      => '5904338272',
	// для вывода только заданных полей из конкретной записи == list, info
	"fields"   => 'clid,title,phone,mail_url',
	// для включения банковских реквизитов
	"bankinfo" => 'yes',
	// включить все контакты
	"contacts" => 'yes'
];

//== Пример массива параметров для вывода списка записей == list
$params2 = [
	"login"     => LOGIN,
	"apikey"    => KEY,
	// указываем метод
	"action"    => 'list',
	// страница, с учетом вывода 200 записей на страницу
	"offset"    => 0,
	// поле для упорядочивания записей
	"order"     => 'date_create',
	// направление сортировки; new - по-умолчанию, old - сначала более старые
	"first"     => '',
	// ограничение по login пользователя, пользователь должен быть в подчинении у текущего
	"user"      => '',
	// даты создания
	"dateStart" => '2016-01-01',
	"dateEnd"   => '2018-06-01',
	// строка поиска по полям title, des, phone, mail_url, site_url
	"word"      => '',
	// фильтры
	"filter"    => [
		// string, фильтр по типу отношений (список доступен в методе guides в запросе relations)
		"relations"  => '1 - Холодный клиент',
		// string|integer, фильтр по отрасли
		"idcategory" => '',
		// string|integer, фильтр по территории
		"territory"  => '',
		// string, фильтр по типу записи (client,person,partner,contractor,concurent)
		"type"       => '',
		// string, фильтр по источнику клиента
		"clientpath" => '',
		// string, yes/no, фильтр по признаку "в корзине"
		"trash"      => 'no',
		// string, фильтр по доп.полю
		"input1"     => ''
	],
	// для вывода только заданных полей из конкретной записи == list, info
	//"fields"    => 'clid,title,phone,mail_url',
	// для включения банковских реквизитов
	//"bankinfo"  => 'yes'
];

//== Пример массива параметров для добавления записи == add
$params3 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	"action"      => "add",
	// тип клиента - client(юр.лицо) - по-умолчанию,person(физ.лицо),concurent,contractor,parnter
	"type"        => "client",
	"uid"         => "123400090",
	// логин ответственного
	"user"        => "marand@omadaru.ru",
	"title"       => "Пробный клиент 5050",
	"phone"       => "+7(342)260-10-10, +7(342)260-10-11",
	"mail_url"    => "info@testplus5050.ru",
	"site_url"    => "testplus5050.ru",
	"territory"   => "Пермь",
	"clientpath"  => "100crm",
	"tip_cmr"     => "Потенциальный клиент",
	"idcategory"  => "Агропром",
	//в часовом поясе аккаунта
	"date_create" => "2015-05-30 23:05:30",
	"recv"        => [
		"castUrName"  => "ООО \"Пробный клиент 5050\"",
		"castInn"     => "590456789006",
		"castKpp"     => "590404210",
		"castBank"    => "Филиал ОАО «УРАЛСИБ» в г. Уфа",
		"castBankKs"  => "30101810600000000770",
		"castBankRs"  => "40702810301220001991",
		"castBankBik" => "048073770",
		"castOgrn"    => "1145958040260",
	]
];

//== Пример массива параметров для добавления записи == update
$params4 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	"action"      => "update",
	"clid"        => "6183",
	"uid"         => "123404590",
	"title"       => "ООО Сибирская консалтинговая группа",
	//"iduser"      => 22,
	"phone"       => "8(343)256-55-55" ,//"+7(342)260-52-10, +7(342)260-52-11",
	//"mail_url"    => "info@testplus5051.ru",
	"site_url"    => "testplus5052.ru",
	//"territory"   => "Екатеринбург",
	"clientpath"  => "Сайт isaler.com",
	"tip_cmr"     => "Текущий клиент",
	//"idcategory"  => "Консалтинг",
	//"creator"     => 1,
	"recv"        => [
		"castUrName"  => "ООО \"Сибирская консалтинговая группа \"",
		"castInn"     => "590456789052",
		"castKpp"     => "590404300",
		"castBank"    => "Филиал ОАО «УРАЛСИБ» в г. Уфа",
		"castBankKs"  => "30101810600000000770",
		"castBankRs"  => "40702810301220001991",
		"castBankBik" => "048073770",
		"castOkpo"    => "58040260",
		"castOgrn"    => "1145958040260",
	]
];

//== Пример массива параметров для добавления нескольких записей == add.list
$params5 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "add.list",
	"client" => [
		[
			"uid"         => "1234",
			"user"        => "marand@omadaru.ru",
			"type"        => "client",
			"title"       => "Пробный клиент t1000",
			"phone"       => "+7(343)260-10-10",
			"mail_url"    => "info@test100001.ru",
			"site_url"    => "test10001.ru",
			"territory"   => "Пермь",
			"clientpath"  => "Сайт",
			"tip_cmr"     => "Потенциальный клиент",
			"idcategory"  => "Торговля",
			"date_create" => "2015-05-30 23:05:30",
			//в часовом поясе аккаунта
			"recv"        => [
				"castUrName"  => "ООО \"Пробный клиент 01\"",
				"castInn"     => "590456789006",
				"castKpp"     => "590404210",
				"castBank"    => "Филиал ОАО «УРАЛСИБ» в г. Уфа",
				"castBankKs"  => "30101810600000000770",
				"castBankRs"  => "40702810301220001991",
				"castBankBik" => "048073770",
				"castOgrn"    => "1145958040260",
			]
		],
		[
			"uid"         => "1234",
			"user"        => "marand@omadaru.ru",
			"type"        => "client",
			"title"       => "Пробный клиент t2000",
			"phone"       => "+7(343)260-10-11",
			"mail_url"    => "info@test200001.ru",
			"site_url"    => "test200002.ru",
			"territory"   => "Пермь",
			"clientpath"  => "Сайт",
			"tip_cmr"     => "Потенциальный клиент",
			"idcategory"  => "Промышленность",
			"date_create" => "2015-05-30 23:05:30",
			//в часовом поясе аккаунта
			"recv"        => [
				"castUrName"  => "ООО \"Пробный клиент 02\"",
				"castInn"     => "590456789006",
				"castKpp"     => "590404210",
				"castBank"    => "Филиал ОАО «УРАЛСИБ» в г. Уфа",
				"castBankKs"  => "30101810600000000770",
				"castBankRs"  => "40702810301220001991",
				"castBankBik" => "048073770",
				"castOgrn"    => "1145958040260",
			]
		]
	]
];

//== Пример массива параметров для удаления записи == delete
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "delete",
	"clid"   => 6214
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params1);

// Устанавливаем соединение
$res = Send($baseurl, $urlparams);

/**
 * Для примера выводим на печать
 */

//запрос
print "<code>".$baseurl.'?'.$urlparams."</code><br><br>";

//ответ
//print $res."<br><br>";

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if ($result['result'] == 'Error') {

	print "Ошибка: ".$result['error']['text'];
	exit();

}

//print array2string($result, "<br>", str_repeat("&nbsp;", 4));
print "\n\n".array2string($result);

exit();