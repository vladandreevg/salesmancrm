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
$baseurl = "https://demo.isaler.ru/developer/v2/person";

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2/person";

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
 *   - update     (редактирование записи, обновляет только указанные поля)
 *   - delete     (удаление записи)
 *
 */

//== Пример массива параметров для вывода списка записей == fields
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'fields'
];

//== Пример массива параметров для вывода списка записей == info
$params1 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'info',
	// id контакта
	"pid"     => '2475',
	// для вывода только заданных полей из конкретной записи == list, info
	"fields"  => 'clid,person,ptitle,tel,mob,mail',
	// для включения банковских реквизитов
	"socinfo" => 'yes'
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
	"dateStart" => '2014-01-01',
	"dateEnd"   => '2018-06-01',
	// строка поиска по полям title, des, phone, mail_url, site_url
	"word"      => '',
	// фильтры
	"filter"    => [
		// string, фильтр по типу отношений (список доступен в методе guides в запросе relations)
		"loyalty"    => '',
		// string|integer, фильтр по должности
		"ptitle"     => 'Директор',
		// string, фильтр по источнику
		"clientpath" => '',
		// string, фильтр по доп.полю
		"input1"     => ''
	],
	// для вывода только заданных полей из конкретной записи == list, info
	//"fields"    => 'clid,person,tel,mob,mail',
	// для включения социальных контактов
	//"socinfo"  => 'yes'
];

//== для добавления записи == add
$params3 = [
	"login"      => LOGIN,
	"apikey"     => KEY,
	// указываем метод
	"action"     => 'add',
	"clid"       => 1781,
	"person"     => 'Апиров Апист Иванович',
	"ptitle"     => 'Технический директор',
	"tel"        => '+7(342)250-50-50, +7(342)290-50-51',
	"mob"        => '+7(922)250-50-50',
	"mail"       => 'a.i.apirov@mailio.ru',
	"clientpath" => 'Voxlink',
	"loyalty"    => '0 - Не лояльный',
	"socials"    => [
		"blog"    => 'http://api.blog.rus',
		"mysite"  => 'http://apiapi.rus',
		"twitter" => 'apiapi',
		"icq"     => '123456789',
		"skype"   => 'apiapi',
		"google"  => 'apiapi',
		"yandex"  => 'apiapi',
		"mykrug"  => 'apiapi'
	],
	// установить основным контактом
	"mperson"    => "yes"
];

//== для обновления записи == update
$params4 = [
	"login"      => LOGIN,
	"apikey"     => KEY,
	// указываем метод
	"action"     => 'update',
	"pid"        => 2750,
	"person"     => 'Апиров Апист Иванович',
	//"ptitle"     => 'Технический директор',
	//"tel"        => '+7(342)250-50-50, +7(342)290-50-51',
	"mob"        => '+7(922)250-50-54',
	//"mail"       => 'a.i.apirov@mailio.ru',
	"clientpath" => 'Voxlink',
	"loyalty"    => '1 - Пока не понятно',
	"socials"    => [
		"blog"    => 'http://api.blog2.rus',
		"mysite"  => 'http://apiapi2.rus',
		"twitter" => 'apiapi',
		"icq"     => '12345678900',
		"skype"   => 'apiapi2',
		"google"  => 'apiapi2',
		"yandex"  => 'apiapi2',
		"mykrug"  => 'apiapi2'
	]
];

//== для добавления нескольких записей == add.list
$params5 = [
	"login"      => LOGIN,
	"apikey"     => KEY,
	// указываем метод
	"action"     => 'add.list',
	"persons" => [
		[
			"clid"       => 6212,
			"person"     => 'Тест 900000',
			"ptitle"     => 'Технический директор',
			"tel"        => '+7(342)250-50-50, +7(342)290-50-51',
			"mob"        => '+7(922)250-50-54',
			"mail"       => 'test90000@mailio.ru',
			"clientpath" => 'Voxlink',
			"loyalty"    => '1 - Пока не понятно',
			"socials"    => [
				"blog"    => 'http://api.blog2.rus',
				"mysite"  => 'http://apiapi2.rus',
				"twitter" => 'apiapi',
				"icq"     => '12345678900',
				"skype"   => 'apiapi2',
				"google"  => 'apiapi2',
				"yandex"  => 'apiapi2',
				"mykrug"  => 'apiapi2'
			]
		],
		[
			"clid"       => 6213,
			"mperson"    => "yes",
			"person"     => 'Тест 100000',
			"ptitle"     => 'Генеральный директор',
			"tel"        => '+7(342)290-90-90, +7(342)290-90-91',
			"mob"        => '+7(922)290-90-94',
			"mail"       => 'test100000@mailio.ru',
			"clientpath" => 'Вконтакте',
			"loyalty"    => '2 - Нейтральный',
			"user"       => "marand@omadaru.ru",
			"socials"    => [
				"blog"    => 'http://api.blog2.rus',
				"mysite"  => 'http://apiapi2.rus',
				"twitter" => 'apiapi4',
				"icq"     => '25345678900',
				"skype"   => 'apiapi4',
				"google"  => 'apiapi4',
				"yandex"  => 'apiapi4',
				"mykrug"  => 'apiapi4'
			]
		]
	]
];

//== Пример массива параметров для удаления записи == delete
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "delete",
	"pid"    => 2750
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params5);

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