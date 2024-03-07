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
$baseurl = "https://demo.isaler.ru/developer/v2/budget";

//Адрес расположения CRM
$baseurl = "http://sm2018/developer/v2/budget";

//существующий пользователь в системе
DEFINE("LOGIN", "zaharbor@isaler.ru");

//получаем в Панели управления CRM
DEFINE("KEY", "aMgiCQyj8bCToNc47BZZYrRICoWSIl");

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
 *   - fields         (список доступных полей)
 *   - info           (информация по расходу/доходу)
 *   - list           (список записей)
 *   - add            (добавление записи)
 *   - update         (редактирование записи, обновляет только указанные поля)
 *   - delete         (удаление записи)
 *   - doit           (проведение расхода/дохода)
 *   - undoit         (отмена проведения расхода/дохода)
 *   - addCategory    (добавление категории)
 *   - editCategory   (изменение категории, обновляет только указанные поля)
 *   - deleteCategory (удаление категории)
 *   - move           (перемещение средств между счетами)
 *   - unmove         (отмена перемещения средств между счетами)
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
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'info',
	// id записи расхода/дохода
	"id"     => '256',
];

//== Пример массива параметров для добавления записи == add
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "add",
	"title"  => "Заправка принтера",
	"cat"    => "1",
	"bmon"   => "7",
	"byear"  => "2018",
	"do"     => "on",
	"rs"     => "19"
];

//== Пример массива параметров для обновления записи == update
$params3 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "update",
	"id"     => "526",
	"title"  => "Покупка печатной бумаги",
	"cat"    => "1",
	"bmon"   => "8",
	"byear"  => "2018",
	"rs"     => "16"
];


//== Пример массива параметров для удаления записи == delete
$params4 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "delete",
	"id"     => "101"
];


//== Пример массива параметров для проведения записи бюджета == doit
$params5 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "doit",
	"id"     => "522"
];


//== Пример массива параметров для отмены проведения записи бюджета== undoit
$params6 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "undoit",
	"id"     => "522"
];


//== Пример массива параметров для добавления категории == addCategory
$params7 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "addCategory",
	"title"  => "Офисные расходы",
	"subid"  => "0",
	"tip"    => "rashod"
];


//== Пример массива параметров для редактирования категории == editCategory
$params8 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "editCategory",
	"title"  => "Реклама",
	"subid"  => "0",
	"tip"    => "dohod"
];


//== Пример массива параметров для удаления категории == deleteCategory
$params9 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "deleteCategory",
	"id"     => "14"
];


//== Пример массива параметров для перемещения средств между счетами == move
$params10 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	"action"  => "move",
	"title"   => "Перемещение_22",
	"bmon"    => "8",
	"byear"   => "2018",
	"summa"   => "15000",
	"rs"      => "4",
	"rs_move" => "11"
];


//== Пример массива параметров для отмены перемещения средств м/у счетами == unmove
$params11 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	"action" => "unmove",
	"id"     => "20",
];


// Создаем подпись к параметрам
$urlparams = http_build_query($params0);

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