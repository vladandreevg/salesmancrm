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
$baseurl = "https://demo.isaler.ru/developer/v2/history";

//Адрес расположения CRM
$baseurl = "http://sm2020.crm/developer/v2/history";

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
 *   - tips (список типов активностей),
 *   - fields (список доступных полей),
 *   - list (список записей),
 *   - info (информация по записи),
 *   - add,
 *   - addlist (групповое добавление),
 *   - delete
 */

// == Пример массива параметров для получения информации о типах активности == tips
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'tips'
];

// == Пример массива параметров для получения информации о полях == fields
$params1 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'fields'
];

// == Пример массива параметров для получения информации == info
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'info',
	"id"     => 17947
];

// == Пример массива параметров для получения информации == list
$params3 = [
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
	"dateStart" => '2018-07-01',
	"dateEnd"   => '2018-07-31',
	// фильтр по полям des, tip
	"word"      => '',
	//фильтр по типу активности
	"tip"       => '',
	//фильтр по клиенту
	"clid"      => 0,
	//фильтр по сделке
	"did"       => 784,
	//фильтр по контакту
	"pid"       => 0,
];

// == Пример массива параметров для добавления Напоминания == add
$params4 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'add',
	// login пользователя
	"user"    => 'marand@omadaru.ru',
	"datum"   => '2018-08-11 15:30:00',
	"content" => 'Это описание',
	"tip"     => 'Задача',
	"clid"    => '1781',
	"pid"     => '2475,2723',
	"did"     => '784'
];

// == Пример массива параметров для добавления Напоминания == add.list
$params5 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'add.list',
	// login пользователя, если в записи не указано
	//"user"   => 'marand@omadaru.ru',
	//позиции спецификации
	"list"   => [
		[
			"user"    => 'marand@omadaru.ru',
			"datum"   => '2018-08-11 15:30:00',
			"content" => 'Это описание 100',
			"tip"     => 'Исх.звонок',
			"clid"    => '1781',
			"pid"     => '2475,2723',
			"did"     => '784'
		],
		[
			//"user"    => 'marand@omadaru.ru',
			"datum"   => '2018-08-11 15:30:00',
			"content" => 'Это описание 200',
			"tip"     => 'Отправка КП',
			"clid"    => '1781',
			"pid"     => '2475,2723',
			"did"     => '784'
		]
	]
];

// == Пример массива параметров для удаления Напоминания == delete
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'delete',
	"id"     => 17954
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params6);

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