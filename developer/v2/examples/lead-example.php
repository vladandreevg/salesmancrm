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
$baseurl = "https://demo.isaler.ru/developer/v2/lead";

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2/lead";

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

// == Пример массива параметров для получения информации == info
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'info',
	"id"     => 1666
];

// == Пример массива параметров для получения списка заявок == list
$params1 = [
	"login"     => LOGIN,
	"apikey"    => KEY,
	// указываем метод
	"action"    => 'list',
	// страница, с учетом вывода 200 записей на страницу
	"offset"    => 0,
	// поле для упорядочивания записей
	"order"     => 'datum',
	// направление сортировки; new - по-умолчанию, old - сначала более старые
	"first"     => '',
	// ограничение по login пользователя
	//"user"      => 'marand@omadaru.ru',
	// даты создания
	"dateStart" => '2019-02-01',
	"dateEnd"   => '2019-09-28',
	// фильтр по статусам заявки: '0' => 'Открыт', '1' => 'В работе', '2' => 'Обработан', '3' => 'Закрыт'
	//"status"    => '0',
];

// == Пример массива параметров для получения информации == stat
$params2 = [
	"login"     => LOGIN,
	"apikey"    => KEY,
	// указываем метод
	"action"    => 'stat',
	// даты создания
	"dateStart" => '2019-01-01',
	"dateEnd"   => '2019-09-31',
];

// == Пример массива параметров для добавления заявки == add
$params3 = [
	"login"        => LOGIN,
	"apikey"       => KEY,
	// указываем метод
	"action"       => 'add',
	"title"        => 'Маркер Цукер',
	"email"        => 'marker.tcuker@perm.io',
	"phone"        => "+76006608877",
	"company"      => "Facepalmer Co",
	"description"  => 'The best crm practice',
	"country"      => "Russia",
	"city"         => "Perm",
	"path"         => "Заявка с сайта",
	"partner"      => "https://yandex.ru",
	"utm_source"   => "facebook",
	"utm_medium"   => "cpc",
	"utm_campaign" => "First Campaign",
	"utm_term"     => "crm для малого бизнеса",
	"utm_content"  => "",
	"utm_referrer" => "yandex.ru",
	"uids"         => [
		"roistat_id" => "SFS4567REWRER",
		"system_id" => "LK78JTW43J5KLEWJ90G",
	]
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params3);

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