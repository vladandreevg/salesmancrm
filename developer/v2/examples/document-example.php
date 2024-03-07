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
$baseurl = "https://demo.isaler.ru/developer/v2/document";

//Адрес расположения CRM
$baseurl = "http://sm2018.crm/developer/v2/document";

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

// == Пример массива параметров для получения информации о типах Документов == tips
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'tips'
];

// == Пример массива параметров для получения информации о статусах документа == statuses
$params1 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'statuses'
];

// == Пример массива параметров для получения информации == info
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'info',
	"id"     => 350
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
	"dateStart" => '2018-01-01',
	"dateEnd"   => '2018-08-31',
	// фильтр по названию, описанию или номеру документа
	"word"      => '',
	// по типу документа, список idtype (id из запроса tips) через запятую
	"idtype"    => 47,
	//фильтр по клиенту
	//"clid"      => 0,
	//фильтр по сделке
	//"did"       => 784
];

// == Пример массива параметров для добавления == add
$params4 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'add',
	//название документа. Если не указан, то принимается как Название шаблона
	//"title" => "Договор через API",
	//описание
	"description" => "Описание договора 222",
	//mcid компании, от которой ведется сделка. Не обязательно, если указан did
	"mcid"        => 2,
	//тип документа
	"idtype"      => 1,
	//период действия
	"dateStart"   => "2018-01-01",
	"dateEnd"     => "2018-12-31",
	//id статуса документа
	"status"      => 1,
	//сделка
	"did"         => 918,
	//заказчик
	"clid"        => 1781,
	//плательщик, если отличается от clid
	"payer"       => 1781,
	// login пользователя
	//"user"      => 'marand@omadaru.ru',
	//id шаблона
	"template"    => 19,
	//конвертировать файл в PDF. Если указан шаблон
	//"pdf"         => "yes"
];

// == Пример массива параметров для обновления == update
$params5 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'update',
	"id"          => 276,
	//название документа. Если не указан, то принимается как Название шаблона
	//"title" => "Договор через API",
	//описание
	"description" => "Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться. Lorem Ipsum используют потому, что тот обеспечивает более или менее стандартное заполнение шаблона, а также реальное распределение букв и пробелов в абзацах, которое не получается при простой дубликации \"Здесь ваш текст.. Здесь ваш текст.. Здесь ваш текст..\"",
	//тип документа
	"idtype"      => 1,
	//период действия
	"dateStart"   => "2018-08-01",
	"dateEnd"     => "2018-12-31",
	// login пользователя
	//"user"      => 'marand@omadaru.ru',
	//id шаблона
	"template"    => 18,
	//конвертировать файл в PDF. Если указан шаблон
	"pdf"         => "yes"
];

// == Пример массива параметров для изменения статуса == status.change
$params6 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'status.change',
	//описание
	"description" => "Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться",
	"id"          => 276,
	"status"      => '4',
];

// == Пример массива параметров для удаления == delete
$params7 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'delete',
	"id"     => 276
];

// == Пример массива параметров для отправки по email == mail
$params8 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'mail',
	//id документа
	"id"      => 350,
	//id сделки
	"did"     => 921,
	//форма отправки - в оригинале или в виде PDF
	"pdf"     => "yes",
	//тема сообщения
	//"theme"   => "Документ на согласование",
	//содержание сообщения
	//"content" => "",
	//id шаблона, если документ еще не сгенерирован
	"template"    => 18
];

// Создаем подпись к параметрам
$urlparams = http_build_query($params4);

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