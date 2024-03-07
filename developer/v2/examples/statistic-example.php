<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2018.6         */
/* ============================ */

error_reporting(E_ERROR);

require_once "../../../inc/config.php";
require_once "../../../inc/dbconnector.php";
require_once "../../../inc/func.php";
require_once "../../../inc/licloader.php";

//Адрес расположения CRM
$baseurl = "https://demo.isaler.ru/developer/v2/statistic";

//Адрес расположения CRM
$baseurl = "http://sm2020.crm/developer/v2/statistic";

//существующий пользователь в системе
DEFINE("LOGIN", "zaharbor@isaler.ru");

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
 *   - list           (вывод  общей статистики)
 *   - clients        (статистика по новым клиентам)
 *   - dealsNew       (статистика по новым сделкам)
 *   - dealsClose     (статистика по закрытым сделкам)
 *   - invoices       (статистика по новым счетам)
 *   - payments       (статистика по оплаченным счетам)
 *
 */

//== Пример массива параметров для вывода общей статистики == list
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => "list",
	// период отчетности
	"period" => "quart",
	//"personal" => "yes",
	"user"   => "vladislav@isaler.ru"
];

//== Пример массива параметров для вывода статистики по новым клиентам == clients
$params1 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => "clients",
	// период отчетности
	"period"  => "year",
	//"personal" => "yes",
	"diagram" => 'yes',
	"user"    => "vladislav@isaler.ru"
];

//== Пример массива параметров для вывода статистики по новым сделкам == dealsNew
$params2 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => "dealsNew",
	// период отчетности
	"period"  => "year",
	"diagram" => "yes",
	//"user"    => "marand@isaler.ru"
];

//== Пример массива параметров для вывода статистики по закрытым сделкам == dealsClose
$params3 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => "dealsClose",
	// период отчетности
	"period"  => "year",
	"diagram" => "yes",
	"user"    => "marand@omadaru.ru",
	//"personal" => "yes"
];

//== Пример массива параметров для вывода статистики по новым счетам == invoices
$params4 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => "invoices",
	// период отчетности
	"period" => "month",
	"user"   => "marand@omadaru.ru"
];

//== Пример массива параметров для вывода статистики по оплаченным счетам == payments
$params5 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => "payments",
	// период отчетности
	"period" => "yearprev",
	//"user"   => "marand2@omadaru.ru",
	"personal" => "yes",
	"diagram" => "yes",
];

//== Пример массива параметров для вывода статистики по оплаченным счетам == payments
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => "diagram",
	"title"=>"Ghjdthrf построения",

];


// Создаем подпись к параметрам
$urlparams = http_build_query($params5);

// Устанавливаем соединение
$res = Send($baseurl, $urlparams);

/**
 * Для примера выводим на печать
 */

//запрос
//print "<code>".$baseurl.'?'.$urlparams."</code><br><br>";

//ответ
print $res;

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if ($result['result'] == 'Error') {

	print "Ошибка: ".$result['error']['text'];
	exit();

}

//print array2string($result, "<br>", str_repeat("&nbsp;", 4));


exit();