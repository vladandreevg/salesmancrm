<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2018.6         */
/* ============================ */

error_reporting(E_ERROR);

set_time_limit(300);

require_once "../../../inc/config.php";
require_once "../../../inc/dbconnector.php";
require_once "../../../inc/func.php";
require_once "../../../inc/licloader.php";

//Адрес расположения CRM
$baseurl = "https://demo.isaler.ru/developer/v2/price";

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2/price";

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

// == Пример массива параметров для получения информации о полях == fields
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'fields'
];

// == Пример массива параметров для получения информации == info
$params1 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'info',
	"id"      => 2353,
	"artikul" => "7414"
];

// == Пример массива параметров для получения информации == list
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'list',
	// страница, с учетом вывода 200 записей на страницу
	"offset" => 0,
	// поле для упорядочивания записей: title, artikul
	"order"  => 'title',
	// направление сортировки; new - по-умолчанию, old - сначала более старые
	"first"  => '',
	// фильтр по полям title, des, tip
	"word"   => 'bizfax',
	//фильтр по статусу: yes - только архивные позиции, no - только актуальные
	//"archive"    => 'yes'
];

// == Пример массива параметров для добавления == add
$params3 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'add',
	"artikul"     => '1258900',
	"title"       => 'Дополнительная лицензия MyPBX Client MyPBX U500/U510/U520',
	"description" => 'MyPBX Client – это софтфон, предназначенный для целевого применения со следующими IР-АТС компании Yeаstar серии "U": Yeаstar МyРВХ U500, U510 и U520',
	"price_in"    => '1320,50',
	"price_1"     => '1456,20',
	"price_2"     => '1390,40',
	"price_3"     => '',
	"price_4"     => '',
	"price_5"     => '',
	"edizm"       => 'шт.',
	"nds"         => '18',
	"archive"     => 'no',
	"category"    => 0
];

// == Пример массива параметров для обновления == update
$params4 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'update',
	"id"          => 4176,
	"artikul"     => '1258900',
	"newartikul"  => '1258950',
	"title"       => 'Дополнительная лицензия MyPBX Client MyPBX U500/U510/U520',
	"description" => 'MyPBX Client – это софтфон, предназначенный для целевого применения со следующими IР-АТС компании Yeаstar серии "U": Yeаstar МyРВХ U500, U510 и U520',
	"price_in"    => '1320,50',
	"price_1"     => '1456,20',
	"price_2"     => '1390,40',
	"price_3"     => '',
	"price_4"     => '',
	"price_5"     => '',
	"edizm"       => 'шт.',
	"nds"         => '18',
	"archive"     => 'no',
	"category"    => 0
];

// == Пример массива параметров для удаления == delete
$params5 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'delete',
	"id"      => 4176,
	"artikul" => '1258950',
];

// == Пример массива параметров для получения списка категорий == category.list
$params6 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'category.list'
];

// == Пример массива параметров для добавления категории == category.add
$params7 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'category.add',
	"title"   => "TEST 6000",
	"sub"     => 4
];

// == Пример массива параметров для добавления категории == category.update
$params8 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'category.update',
	"id"      => 263,
	"title"   => "TEST 6100",
	"sub"     => 156
];

// == Пример массива параметров для добавления категории == category.delete
$params8 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'category.delete',
	"id"      => 249
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params8);

// Устанавливаем соединение
$res = Send($baseurl, $urlparams);

/**
 * Для примера выводим на печать
 */

//запрос
print "<code>".$baseurl.'?'.$urlparams."</code><br><br>";

//ответ
print $res."<br><br>";

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if ($result['result'] == 'Error') {

	print "Ошибка: ".$result['error']['text'];
	exit();

}

print array2string($result, "<br>", str_repeat("&nbsp;", 4));

exit();