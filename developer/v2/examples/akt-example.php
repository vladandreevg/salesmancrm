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
$baseurl = "https://demo.isaler.ru/developer/v2/akt";

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2/akt";

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
	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if ($result === false) print $err = curl_error($ch);

	return $result;
}

// == Пример массива параметров для получения информации о типах активности == statuses
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'statuses'
];

// == Пример массива параметров для получения информации о типах активности == statuses
$params01 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'templates'
];

// == Пример массива параметров для получения информации == info
$params1 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'info',
	"id"     => 315
];

// == Пример массива параметров для получения информации == list
$params2 = [
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
	"dateStart" => '2019-01-01',
	"dateEnd"   => '2019-08-31',
	// фильтр по описанию или номеру документа
	"word"      => '',
	//фильтр по клиенту
	//"clid"      => 1781,
	//фильтр по сделке
	//"did"       => 913
];

// == Пример массива параметров для добавления == add
$params3 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'add',
	//дата счета
	"date"        => "2018-07-01",
	//номер акта, если не указано - будет сгенерирова по порядку
	"number"      => '',
	// шаблон Акта: имя файла или ID шаблона
	"template"    => "akt_full.tpl",
	"templateID"  => "97",
	//описание
	"description" => "Это текст - описание к акту",
	// id статуса документа
	"status"      => 1,
	// сделка
	"did"         => 920,//913,
	// login пользователя
	//"user"      => 'marand@omadaru.ru',
];

// == Пример массива параметров для обновления == update
$params4 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'update',
	//дата счета
	"date"        => "2018-07-01",
	//id документа
	"id"          => 279,
	// шаблон Акта: simple,full,prava
	"template"    => "full",
	//описание
	"description" => "Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться. Lorem Ipsum используют потому, что тот обеспечивает более или менее стандартное заполнение шаблона",
];

// == Пример массива параметров для изменения статуса == status.change
$params5 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'status.change',
	//описание
	"description" => "Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться",
	"id"          => 279,
	"status"      => 3,
];

// == Пример массива параметров для удаления == delete
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'delete',
	"id"     => 304
];

// == Пример массива параметров для отправки по email == mail
$params7 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'mail',
	//id документа
	"id"     => 315,
	//тема сообщения
	//"theme"   => "Документ на согласование",
	//содержание сообщения
	//"content" => "",
	//id статуса документа
	"status"      => 3
];

//== для получения акта в виде html == html
$params8 = [
	"login"    => LOGIN,
	"apikey"   => KEY,
	// указываем метод
	"action"   => 'html',
	//id акта
	"id"       => 315,
	//номер акта
	"number"  => "57",
	//наличие печати
	//"nosignat" => "yes"
];

//== для получения акта в виде pdf == pdf
$params9 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'pdf',
	//id акта
	"id"       => 315,
	//номер акта
	"number"  => "57"
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params2);

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

print array2string($result, "<br>", str_repeat("&nbsp;", 4))."\n";

exit();