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
$baseurl = "https://demo.isaler.ru/developer/v2/user";

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2/user";

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

//== Пример массива параметров для получения информации о пользователе == user
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'user',
	//uid пользователя, если известен
	"uid"    => '',
	// ограничение по login пользователя, пользователь должен быть в подчинении у текущего
	"user"      => 'vladislav@isaler.ru',
];

//== Пример массива параметров для получения информации обо всех пользователях организации == user.list
$params1 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'user.list',
];

//== Пример массива параметров для получения информации обо всех пользователях организации == user.add
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'user.add',
	//uid пользователя, если известен
	"uid"    => '',
	// ограничение по login пользователя, пользователь должен быть в подчинении у текущего
	"user"   => 'ivan.petrov@isaler.ru',
	//пароль в явном виде
	"password" => 'PassW0Rd!2',
	//ФИО
	"title" => 'Иван Петров',
	//роль в системе (Руководитель организации, Руководитель с доступом, Руководитель подразделения, Руководитель отдела, Менеджер продаж, Поддержка продаж, Специалист, Администратор)
	"tip" => "",
	//должность
	"user_post" => "Менеджер продаж",
	//день рождения
	"bday" => "1965-02-02",
	//телефон
	"phone" => "",
	//добавочный номер
	"phone_in" => "",
	//мобильный номер
	"mob" => "",
	//email пользоваптеля
	"email" => 'ivan.petrov@isaler.ru',
	//логин руководителя
	"boss" => "vladislav@isaler.ru",
	//отдел
	"otdel" => "OAP"
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

print array2string($result, "<br>", str_repeat("&nbsp;", 4));

exit();