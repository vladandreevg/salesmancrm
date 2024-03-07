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
$baseurl = "https://demo.isaler.ru/developer/v2/guides";

//Адрес расположения CRM
$baseurl = "http://sm2020.crm/developer/v2/guides";

//существующий пользователь в системе
DEFINE("LOGIN", "vladislav@isaler.ru");

//получаем в Панели управления CRM
DEFINE("KEY", "t1xdeOwWSIqgDol70CkRdK3WD4N4cm");

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

//== Пример массива параметров для получения информации из справочника Отрасли == category
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'category'
];

//== Пример массива параметров для получения информации из справочника Территории == territory
$params1 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'territory'
];

//== Пример массива параметров для получения информации из справочника Тип отношений == relations
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'relations'
];

//== Пример массива параметров для получения информации из справочника Источник клиента == clientpath
$params3 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'clientpath'
];

//== Пример массива параметров для получения информации из справочника Мои компании == company.list
$params4 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'company.list'
];

//== Пример массива параметров для получения информации из справочника Мои компании == company.listfull
$params5 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'company.listfull'
];

//== Пример массива параметров для получения информации из справочника Мои компании == company.bank
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'company.bank'
];

//== Пример массива параметров для получения информации из справочника Мои компании == company.signers
$params7 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'company.signers'
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params7);

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

//print array2string($result, "<br>", str_repeat("&nbsp;", 4));
print_r($result);

exit();