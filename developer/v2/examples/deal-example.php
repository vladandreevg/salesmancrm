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
$baseurl = "https://demo.isaler.ru/developer/v2/deal";

//Адрес расположения CRM
$baseurl = "http://sm2021.crm/developer/v2/deal";

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
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if ($result === false) print $err = curl_error($ch);

	return $result;
}

/**
 *
 * action - Поддерживаемые методы
 *   - fields         (список доступных полей)
 *   - steplist       (список этапов)
 *   - direction      (список направлений)
 *   - statusclose    (список статусов закрытия)
 *   - funnel         (список воронок)
 *   - info           (информация по клиенту)
 *   - list           (список записей)
 *   - add            (добавление записи)
 *   - update         (редактирование записи, обновляет только указанные поля)
 *   - change.close   (закрытие сделки)
 *   - change.step    (изменить этап)
 *   - addinvoice     (добавление счета)
 *   - addpaiment     (отметка существующего счета оплаченным)
 *   - addpaimentpart (отметка существующего счета оплаченным частично)
 *   - delete         (удаление записи)
 *
 */

//== Пример массива параметров для вывода списка записей == fields
$params0 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'fields'
];

//== Пример массива параметров для вывода списка записей == steplist
$params1 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'steplist'
];

//== Пример массива параметров для вывода списка направлений == direction
$params2 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'direction'
];

//== Пример массива параметров для вывода списка направлений == type
$params3 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'type'
];

//== Пример массива параметров для вывода списка записей == statusclose
$params4 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'statusclose'
];

//== Пример массива параметров для вывода списка записей == funnel
$params5 = [
	"login"     => LOGIN,
	"apikey"    => KEY,
	// указываем метод
	"action"    => 'funnel',
	"did"       => 913,
	"direction" => '',
	"tip"       => ''
];

//== Пример массива параметров для вывода списка записей == list
$params6 = [
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
	// ограничение по login пользователя, пользователь должен быть в подчинении у текущего
	//"user"      => 'marand@omadaru.ru',
	// даты создания
	//"dateStart" => '2018-01-01',
	//"dateEnd"   => '2019-06-01',
	// строка поиска по полям title, des, phone, mail_url, site_url
	"word"      => '',
	// string, фильтр по UID сделки
	"uid"       => '',
	// string, фильтр по этапам с перечислением названий этапов через запятую (Например – 10,20,40)
	//"steps"     => '10,20,40',
	// string, фильтр по активности. yes - активная сделка (по умолчанию) или no - закрытая
	"active"    => 'yes',
	// для вывода только заданных полей из конкретной записи == list, info
	//"fields"    => 'did,title,clid,kol,marga',
	// для включения банковских реквизитов
	//"bankinfo"  => 'yes',
	// string, наличие счетов. если равен "no", то ответ не будет содержать счетов (yes - по умолчанию)
	//"invoice"   => 'yes',
	// фильтры
	"filter"    => [
		// string, фильтр по идентификатору клиента-заказчика
		"clid"      => '',
		// string, фильтр по идентификатору клиента-плательщика
		"payer"     => '',
		// string, фильтр по направлению деятельности в текстовом виде или по ID (напроимер, основное)
		//"direction" => '2',
		// string, фильтр по типу сделки в текстовом виде
		//"tip"       => '2',
		// string, фильтр по доп.полю
		"input1"    => '',
		"phone"     => '7(342)254-55-77'
	]
];

//== Пример массива параметров для инфонмации о сделке == info
$params7 = [
	"login"    => LOGIN,
	"apikey"   => KEY,
	// указываем метод
	"action"   => 'info',
	"did"      => 902,
	// string, вывод счетов. если равен "no", то ответ не будет содержать счетов (yes, по - умолчанию)
	"invoice"  => 'yes',
	// string, вывод спецификации. если равен "no", то ответ не будет содержать счетов (yes, по - умолчанию)
	"speka"    => 'yes',
	// string, если равен "yes", то ответ будет содержать информацию о Заказчике (yes, no - по умолчанию)
	//"client"     => 'yes',
	// string, если равен "yes", то ответ будет содержать информацию о Плательщике (yes, no - по умолчанию)
	"payer"    => 'yes',
	// string, если равен "yes", то ответ будет содержать информацию о Контактах по сделке (yes, no - по умолчанию)
	"person "  => 'yes',
	// string, если равен "yes", то ответ будет содержать информацию о Договоре (yes, no - по умолчанию)
	"contract" => 'yes',
	// string, если равен "yes", то ответ будет содержать информацию о Нашей компании (yes, no - по умолчанию)
	"company"  => 'yes',
];

//== Пример массива параметров для добавления сделки == add
$params8 = [
	"login"      => LOGIN,
	"apikey"     => KEY,
	// указываем метод
	"action"     => 'add',
	//название
	"title"      => 'Тестовая сделка 6465464',
	//UID
	"uid"        => '7989825655',
	//заказчик
	"clid"       => 1781,
	//плательщик, если отличается от clid
	"payer"      => 1781,
	//направление
	//"direction"  => 'Оборудование',
	//тип сделки
	"tip"        => 'Продажа услуг',
	//id компании
	//"mcid"       => 2,
	//этап сделки. если пусто, то будет взято значение по-умолчанию
	"step"       => '40',
	//плановая дата
	"datum_plan" => '2019-09-01',
	//сумма сделки, если нет спецификации
	"kol"        => '100000.00',
	//маржа сделки, если нет спецификации
	"marga"      => '20000.00',
	"user"       => 'marand@omadaru.ru',
	"content"    => 'Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться. Lorem Ipsum используют потому, что тот обеспечивает более или менее стандартное заполнение шаблона, а также реальное распределение букв и пробелов в абзацах, которое не получается при простой дубликации "Здесь ваш текст.. Здесь ваш текст.. Здесь ваш текст.."',
	//позиции спецификации
	"speka"      => [
		[
			"artikul"  => "7414",
			"title"    => "BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
			"tip"      => "1",
			"kol"      => "5",
			//доп.множитель, если не используется = 1
			"dop"      => "1",
			//не обязательно, если корректно указан artikul или title
			"price"    => "18831,15",
			//не обязательно, если корректно указан artikul или title
			"price_in" => "13 949,00",
			//не обязательно, если корректно указан artikul или title
			"edizm"    => "шт.",
			//НДС в % если есть
			"nds"      => "18"
		],
		[
			"artikul"  => "7722",
			"title"    => "SIP-T12P SIP-телефон, 2 линии, PoE",
			"tip"      => '2',
			"kol"      => "10",
			"dop"      => "1",
			"price"    => "3821,85",
			"price_in" => "2 831,00",
			"edizm"    => "шт.",
			"nds"      => "18"
		]
	]
];

//== Пример массива параметров для изменение сделки == update
$params9 = [
	"login"      => LOGIN,
	"apikey"     => KEY,
	// указываем метод
	"action"     => 'update',
	"did"        => '921',
	"uid"        => 'FGR34556900',
	//название
	"title"      => 'Тестовая сделка',
	//заказчик
	//"clid"       => 1781,
	//плательщик, если отличается от clid
	//"payer"      => 1781,
	//направление
	//"direction"  => 'Оборудование',
	//тип сделки
	//"tip"        => 'Продажа услуг',
	//id компании
	//"mcid"       => 2,
	//плановая дата
	"datum_plan" => '2018-08-11',
	//сумма сделки, если нет спецификации
	"kol"        => '100000.00',
	//маржа сделки, если нет спецификации
	"marga"      => '20000.00',
	"pid_list"   => '2475,2513',
	"content"    => 'Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться. Lorem Ipsum используют потому, что тот обеспечивает более или менее стандартное заполнение шаблона, а также реальное распределение букв и пробелов в абзацах, которое не получается при простой дубликации "Здесь ваш текст.. Здесь ваш текст.. Здесь ваш текст.."',
	//позиции спецификации
	"speka"      => [
		[
			"artikul"  => "7414",
			"title"    => "BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
			"kol"      => "5",
			//доп.множитель, если не используется = 1
			"dop"      => "1",
			//не обязательно, если корректно указан artikul или title
			"price"    => "19831,15",
			//не обязательно, если корректно указан artikul или title
			"price_in" => "13949,00",
			//не обязательно, если корректно указан artikul или title
			"edizm"    => "шт.",
			//НДС в % если есть
			"nds"      => "18"
		],
		[
			"artikul"  => "7722",
			"title"    => "SIP-T12P SIP-телефон, 2 линии, PoE",
			"kol"      => "5",
			"dop"      => "1",
			"price"    => "4821,85",
			"price_in" => "2831,00",
			"edizm"    => "шт.",
			"nds"      => "18"
		]
	]
];

//== для смены этапа == change.step
$params10 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'change.step',
	"did"         => 921,
	"step"        => '40',
	"description" => 'Согласовали спецификацию. Готовим договор.'
];

//== для закрытия сделки == change.close
$params11 = [
	"login"        => LOGIN,
	"apikey"       => KEY,
	// указываем метод
	"action"       => 'change.close',
	"did"          => 247,
	"status_close" => 'Победа полная',
	"kol_fact"     => '100000.00',
	"marga"        => '30000.00',
	"description"  => 'Комментарий к закрытию сделки'
];

//== для передачи сделки == change.user
$params12 = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	// указываем метод
	"action"      => 'change.user',
	"did"         => 917,
	"user"        => 'marand@omadaru.ru',
	"client.send" => 'yes',
	"person.send" => 'yes'
];

//== для удаления сделки == delete
$params13 = [
	"login"  => LOGIN,
	"apikey" => KEY,
	// указываем метод
	"action" => 'delete',
	"did"    => 919
];

//== для выставления счета клиенту == invoice.add
$params14 = [
	"login"     => LOGIN,
	"apikey"    => KEY,
	// указываем метод
	"action"    => 'invoice.add',
	"did"       => 921,
	"uid"       => '',
	//Ответственный, которому надо назначить счет
	"user"      => 'marand@omadaru.ru',
	//номер счета, если "auto", то будем генерировать очередной из системы
	"invoice"   => 'auto',
	//дата счета, если пусто - текущая дата
	"date"      => '',
	//ожидаемая дата оплаты счета, если не указано, то устанавливается как +5 дней от текущей даты
	"date.plan" => '',
	//сумма счета, если не указано, то берем сумму из сделки или спецификации (если есть)
	"summa"     => '',
	//номер договора, если пусто, то смотрим Договор, прикрепленный к сделке
	"contract"  => '',
	//идентификатор расчетного счета (список можно получить из справочника) - если пусто, то берем первый по списку с признаком "по-умолчанию" для компании, от которой ведется сделка
	"rs"        => '',
	//размер НДС в абсолютных цифрах, если пусто, то будет расчитан автоматически
	"nds"       => '',
	//тип счета - Предварительная оплата, Окончательная оплата, По спецификации, По договору, Счет-договор
	"tip"       => 'Счет-договор',
	//признак оплаченного счета - yes
	//"do" => '',
	//дата оплаты
	//"date.do" => '',
];

//== для отметки счета оплаченным == invoice.do
$params15 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'invoice.do',
	"did"     => 921,
	//id счета
	"id"      => 915,
	//номер счета, не обязательно если указан id счета
	"invoice" => '169',
	//дата оплаты, если пусто, то принимается текущая дата
	"date.do" => '',
	//сумма оплаты, если пусто, то принимается полная сумма счета
	"summa"   => '100000',
];

//== для выставления счета клиенту == invoice.express
$params16 = [
	"login"     => LOGIN,
	"apikey"    => KEY,
	// указываем метод
	"action"    => 'invoice.express',
	"did"       => 921,
	"uid"       => '',
	//Ответственный, которому надо назначить счет
	"user"      => 'marand@omadaru.ru',
	//номер счета, если "auto", то будем генерировать очередной из системы
	"invoice"   => 'auto',
	//дата счета, если пусто - текущая дата
	"date"      => '',
	//ожидаемая дата оплаты счета, если не указано, то устанавливается как +5 дней от текущей даты
	"date.plan" => '',
	//сумма счета, если не указано, то берем сумму из сделки или спецификации (если есть)
	"summa"     => '150000',
	//номер договора, если пусто, то смотрим Договор, прикрепленный к сделке
	"contract"  => '',
	//идентификатор расчетного счета (список можно получить из справочника) - если пусто, то берем первый по списку с признаком "по-умолчанию" для компании, от которой ведется сделка
	"rs"        => '',
	//размер НДС в абсолютных цифрах, если пусто, то будет расчитан автоматически
	"nds"       => '',
	//тип счета - Предварительная оплата, Окончательная оплата, По спецификации, По договору, Счет-договор
	"tip"       => 'Счет-договор',
	//дата оплаты
	"date.do"   => '',
];

//== для получения счета в виде html == invoice.html
$params17 = [
	"login"    => LOGIN,
	"apikey"   => KEY,
	// указываем метод
	"action"   => 'invoice.html',
	//id счета
	"id"       => 925,
	//номер счета
	"invoice"  => "158",
	//наличие печати
	"nosignat" => "yes",
];

//== для получения счета в виде pdf == invoice.pdf
$params18 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'invoice.pdf',
	//id счета
	"id"      => 925,
	//номер счета
	"invoice" => "158",
];

//== для отправки счета в виде pdf == invoice.mail
$params19 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'invoice.mail',
	//id счета
	"id"      => 898,
	//номер счета
	"invoice" => "158",
	//тема сообщения
	"theme"   => "Счет на оплату",
	//содержание сообщения
	"content" => ""
];

//== для отправки счета в виде pdf == invoice.template
$params20 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'invoice.templates'
];

//== для отправки счета в виде pdf == invoice.info
$params21 = [
	"login"   => LOGIN,
	"apikey"  => KEY,
	// указываем метод
	"action"  => 'invoice.info',
	"id"      => 958
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

file_put_contents("deals.json", json_encode_cyr(json_decode($res, true)));

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if ($result['result'] == 'Error') {

	print "Ошибка: ".$result['error']['text'];
	exit();

}

//print array2string($result, "<br>", str_repeat("&nbsp;", 4));

exit();