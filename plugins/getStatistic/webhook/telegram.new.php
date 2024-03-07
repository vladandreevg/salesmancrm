<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/*   Developer: Ivan Drachyov   */
/*   Moded by Vladislav Andreev */
/* ============================ */

/**
 * Реализация клавиатуры с использованием inline_keyboard
 * В этом случае клавиатура встроена в текст обсуждения
 * Однако этот вариант позволяет передавать параметры
 */

use pChart\pChart;
use Salesman\Metrics;

error_reporting(E_ERROR);

set_time_limit(300);
ini_set("memory_limit", "128M");

header('Access-Control-Allow-Origin: *');

$rootpath = dirname( __DIR__, 3 );
$ypath    = $rootpath."/plugins/getStatistic";

if (!file_exists($rootpath."/cash/statbot_error.log")) {

	$file = fopen($rootpath."/cash/statbot_error.log", "w");
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $rootpath.'/cash/statbot_error.log');

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/vendor/core.php";
//require_once $ypath."/vendor/pchart-master/src/pChart/pData.php";
//require_once $ypath."/vendor/pchart-master/src/pChart/pChart.php";


$indata = json_decode(file_get_contents('php://input'), true);

//запишем входящие данные
logIt(file_get_contents('php://input'), "INPUT0");
logIt($indata, "INPUT");
logIt($_REQUEST, "INPUT2");

$apikey  = $_REQUEST['api_key'];
$botname = $_REQUEST['botname'];

//Найдем identity по настройкам
$res      = $db -> getRow("SELECT id, api_key, timezone,valuta FROM {$sqlname}settings WHERE api_key = '$apikey'");
$tmzone   = $res['timezone'];
$api_key  = $res['api_key'];
$identity = $res['id'] + 0;
$valuta   = $res['valuta'];

require_once $rootpath."/inc/settings.php";

date_default_timezone_set($tmzone);

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = intval($dzz) / 3600;

$db -> query("SET time_zone = '+".$bdtimezone.":00'");

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
$serverhost = $scheme.$_SERVER["HTTP_HOST"];

$settings = json_decode(file_get_contents($ypath.'data/'.$fpath.'settings.json'), true);

$proxy = [];

if (!empty($settings['proxy'])) {

	$proxy         = $settings['proxy'];
	$proxy["type"] = CURLPROXY_SOCKS5;

}

$bot = $db -> getRow("SELECT * FROM {$sqlname}sendStatistic_bots WHERE name = '$botname'");

logIt($bot, "BOT");

//Определяем чат для отправки сообщения
$telegram = new Telegram($bot['token'], true, $proxy);

//Функция вывода общей статистики
function output($date) {

	$rootpath = dirname( __DIR__, 3 );

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/func.php";

	//file_put_contents($rootpath."/cash/bot.log", $date);

	$db      = $GLOBALS['db'];
	$sqlname = $GLOBALS['sqlname'];
	$chatid  = $GLOBALS['chatid'];
	$emoji   = $GLOBALS['emoji'];
	//$botname  = $GLOBALS['botname'];
	$valuta = $GLOBALS['valuta'];
	//$proxy    = $GLOBALS['proxy'];
	$telegram = $GLOBALS['telegram'];

	//$bot = $db -> getRow("SELECT * FROM {$sqlname}sendStatistic_bots WHERE name = '$botname'");
	//$telegram = new Telegram($bot['token'], true, $proxy);

	//file_put_contents($rootpath."/cash/bot.log", $chatid, FILE_APPEND);

	$iduser = $db -> getOne("select iduser from {$sqlname}sendstatistic_users");
	//Выводим данные в боте

	if ($date == 'yestoday' || $date == 'today') {

		$period = getPeriod($date);
		$text   = $emoji['clipboard']."Отчет \n".str_repeat(" ", 6)." за <b>".format_date_rus($period[0])."</b>\n";

	}
	else {

		$period = getPeriod($date);
		$text   = $emoji['clipboard']."Отчет \n".str_repeat(" ", 6)." за период с <b>".format_date_rus($period[0])."</b> по <b>".format_date_rus($period[1])."</b>\n";

	}

	$f = new Metrics();
	//По клиентам
	$clients = $f -> calculateFact($iduser, 'clientsNew', ['datum' => $period]);

	//По сделкам
	$deals['count']     = $f -> calculateFact($iduser, 'dealsNewAll', ['datum' => $period]);
	$deals['sum']       = $f -> calculateFact($iduser, 'dealsSumAll', ['datum' => $period]);
	$deals['close']     = $f -> calculateFact($iduser, 'dealsCloseAll', ['datum' => $period]);
	$deals['sum_close'] = $f -> calculateFact($iduser, 'dealsSumClose', ['datum' => $period]);

	//По счетам
	$invoices['count'] = $f -> calculateFact($iduser, 'invoicesNewCount', ['datum' => $period]);
	$invoices['sum']   = $f -> calculateFact($iduser, 'invoicesNewSum', ['datum' => $period]);

	//По оплатам
	$payments['count'] = $f -> calculateFact($iduser, 'invoicesDoCount', ['datum' => $period]);
	$payments['sum']   = $f -> calculateFact($iduser, 'invoicesDoSum', ['datum' => $period]);


	$replyMarkup   = [
		'inline_keyboard'   => [
			[
				[
					"text"          => $emoji['openbook']."Детализация",
					"callback_data" => json_encode([
						"action" => "detale",
						"period" => $date
					])
				],
				[
					"text"          => $emoji['calendar']."Изменить период",
					"callback_data" => json_encode(["action" => "main"])
				]
			],
			[
				[
					"text"          => $emoji['left']."Назад",
					"callback_data" => json_encode([
						"action" => "back",
						"period" => $date
					])
				],
				[
					"text"          => $emoji['home']."Главная",
					"callback_data" => json_encode(["action" => "main"])
				]
			]
		],
		'resize_keyboard'   => true,
		'one_time_keyboard' => false
	];
	$encodedMarkup = json_encode($replyMarkup);

	$text .= "
		Новые клиенты:
		 - кол-во:  <b>".$clients."</b> шт.
		
		Новые сделки:
		 - кол-во:  <b>".$deals['count']."</b> шт.
		 - сумма: <b>".num_format($deals['sum'])."</b> $valuta
		
		Новые счета:
		 - кол-во:  <b>".$invoices['count']."</b> шт.
		 - сумма: <b>".num_format($invoices['sum'])."</b> $valuta
		
		Оплаты:
		 - кол-во:  <b>".$payments['count']."</b> шт.
		 - сумма: <b>".num_format($payments['sum'])."</b> $valuta
		
		Закрытые сделки:
		 - кол-во:  <b>".$deals['close']."</b> шт.
		 - сумма: <b>".num_format($deals['sum_close'])."</b> $valuta
	";
	//file_put_contents($rootpath."/cash/bot.log", $text, FILE_APPEND);

	$r = $telegram -> SendMessage([
		"chat_id"              => $chatid,
		"text"                 => $text,
		'reply_markup'         => $encodedMarkup,
		"parse_mode"           => 'HTML',
		"disable_notification" => true
	]);

	logIt($r, 'OUT');

}

//Вывод отчета по сотрудникам
function per_users($text, $period) {

	$chatid   = $GLOBALS['chatid'];
	$emoji    = $GLOBALS['emoji'];
	$telegram = $GLOBALS['telegram'];

	//Формируем детализацию по сотрудникам

	$replyMarkup   = [
		'inline_keyboard'   => [
			[
				"text"          => $emoji['left']."Назад",
				"callback_data" => json_encode([
					"action" => "perperiod",
					"period" => $period
				])
			],
			[
				"text"          => $emoji['home']."Главная",
				"callback_data" => json_encode(["action" => "main"])
			]
		],
		'resize_keyboard'   => true,
		'one_time_keyboard' => false
	];
	$encodedMarkup = json_encode($replyMarkup);

	$telegram -> sendMessage([
		"chat_id"              => $chatid,
		"text"                 => $text,
		"reply_markup"         => $encodedMarkup,
		"parse_mode"           => 'HTML',
		"disable_notification" => true
	]);

}

//Создание диаграммы
function create_graph($cur_date) {
	
	global $ypath;

	require_once $ypath."/vendor/pchart-master/src/pChart/pData.php";
	require_once $ypath."/vendor/pchart-master/src/pChart/pChart.php";

	$chatid   = $GLOBALS['chatid'];
	$telegram = $GLOBALS['telegram'];
	$emoji    = $GLOBALS['emoji'];

	$img = time().".png";

	$DataSet = new pData();

	$date    = file_get_contents($ypath.'/data/date.log');
	$param   = file_get_contents($ypath.'/data/parameter.log');
	$content = file_get_contents($ypath.'/data/data.log');

	$period = getPeriod($date);

	if ($content != "" && $content != null) {


		// Initialise the graph
		$Test = new pChart(850, 350);

		//Перебираем все элементы массива в цикле
		foreach ($content as $i => $str) {

			$data = yexplode("-", $str);
			$DataSet -> AddPoint($data[1], $data[0]." / ".$data[2]." %");
			$Test -> setColorPalette($i, rand(0, 255), rand(0, 255), rand(0, 255));

		}
		$DataSet -> AddAllSeries();

		$DataSet -> SetYAxisName("Количество");
		$DataSet -> SetXAxisUnit("тчет по сотрудникам");
		$DataSet -> SetXAxisName(str_repeat(" ", 30)."Данные актуальны на $cur_date");

		$Test -> setFontProperties("../vendor/pChart/Fonts/tahoma.ttf", 8);
		$Test -> setGraphArea(50, 30, 680, 310);

		//Фон изображения
		$Test -> drawFilledRoundedRectangle(7, 7, 840, 330, 5, 240, 240, 240);

		//Рамка изображения
		$Test -> drawRoundedRectangle(5, 5, 695, 225, 5, 240, 240, 240);

		//Фон за графиком
		$Test -> drawGraphArea(255, 255, 255, true);
		$Test -> drawScale($DataSet -> GetData(), $DataSet -> GetDataDescription(), SCALE_START0, 150, 150, 150, true, 0, 2, true);
		$Test -> drawGrid(4, true, 230, 230, 230, 50);

		// Draw the bar graph
		$Test -> drawBarGraph($DataSet -> GetData(), $DataSet -> GetDataDescription(), true, 100);

		// Finish the graph
		$Test -> setFontProperties($ypath."/vendor/pChart/Fonts/tahoma.ttf", 8);
		$Test -> drawLegend(690, 10, $DataSet -> GetDataDescription(), 255, 255, 255);
		$Test -> setFontProperties($ypath."/vendor/pChart/Fonts/tahoma.ttf", 10);
		$Test -> drawTitle(70, 22, "Новые $param за период с ".format_date_rus($period[0])." по ".format_date_rus($period[1])."", 50, 50, 50, 700);

		$Test -> Render("../images/$img");

		$telegram -> sendPhoto([
			"chat_id"              => $chatid,
			"photo"                => $_SERVER['SERVER_NAME']."/plugins/sendStatistic/images/$img",
			"caption"              => $emoji['diagram']."Диаграмма \"Новые $param\" \n".str_repeat(" ", 6)." на $cur_date",
			"disable_notification" => true
		]);

		unlink($ypath."/images/$img");

		unset($Test);
	}
	else {

		$telegram -> sendMessage([
			"chat_id"              => $chatid,
			"text"                 => $emoji['!']."Нет данных для построения диаграммы!",
			"parse_mode"           => 'HTML',
			"disable_notification" => true
		]);

	}

}

//логгирование
function logIt($array, $name) { //Запись массива в файл

	$string = is_array($array) ? array2string($array) : $array;
	file_put_contents($GLOBALS['rootpath']."/cash/stat-webhooks.log", current_datumtime().":: $name\n$string\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n", FILE_APPEND);

}

// Массив, содержащий эмоджи для сообщений
$emoji = [
	//Смайл
	"smile"       => "\xF0\x9F\x98\x89 ",
	//Дом
	"home"        => "\xF0\x9F\x8F\xA0 ",
	//Дверь
	"door"        => "\xF0\x9F\x9A\xAA ",
	//Книга
	"book"        => "\xF0\x9F\x93\x95 ",
	//Будильник
	"alarm_clock" => "\xE2\x8F\xB0 ",
	//Информация
	"info"        => "\xE2\x84\xB9 ",
	//Человек
	"man"         => "\xF0\x9F\x91\xA8 ",
	//Грустный смайл
	"grust"       => "\xF0\x9F\x98\x94 ",
	//Символ проверено(Галочка)
	"check"       => "\xE2\x9C\x85 ",
	//Кнопка с Крестиком
	"X"           => "\xE2\x9D\x8C ",
	//Восклицание
	"!"           => "\xE2\x9D\x97 ",
	//Мигалка
	"light"       => "\xF0\x9F\x9A\xA8 ",
	//Знак запрещено
	"no"          => "\xF0\x9F\x9A\xA8 ",
	//Квадрат с надписью New
	"new"         => "\xF0\x9F\x86\x95 ",
	//Стрелка вправо
	"right"       => "\xE2\x9E\xA1 ",
	//Стрелка влево
	"left"        => "\xE2\xAC\x85 ",
	//Стрелка Вверх
	"up"          => "\xE2\xAC\x86 ",
	//Стрелка Вниз
	"down"        => "\xE2\xAC\x87 ",
	//Телефон
	"tel"         => "\xE2\x98\x8E ",
	//Музыкальная нота
	"music"       => "\xF0\x9F\x8E\xB5 ",
	//Деньги
	"money"       => "\xF0\x9F\x92\xB5 ",
	//Сохранение
	"save"        => "\xF0\x9F\x92\xBE ",
	//Лист с текстом
	"list"        => "\xF0\x9F\x93\x84 ",
	//Календарь
	"calendar"    => "\xF0\x9F\x93\x85 ",
	//График
	"graph"       => "\xF0\x9F\x93\x88 ",
	//Диаграмма
	"diagram"     => "\xF0\x9F\x93\x8A ",
	//Открытая книга
	"openbook"    => "\xF0\x9F\x93\x96 ",
	//Динамик
	"speaker"     => "\xF0\x9F\x94\x8A ",
	//Лупа
	"glass"       => "\xF0\x9F\x94\x8D ",
	//Ключ
	"key"         => "\xF0\x9F\x94\x91 ",
	//Замок
	"lock"        => "\xF0\x9F\x94\x92 ",
	//Колокольчик
	"bell"        => "\xF0\x9F\x94\x94 ",
	//Часы
	"clock"       => "\xF0\x9F\x95\x99 ",
	//Земной шар
	"earth"       => "\xF0\x9F\x8C\x8D ",
	//Люди
	"people"      => "\xF0\x9F\x91\xAC ",
	//Мешок денег
	"money_bag"   => "\xF0\x9F\x92\xB0 ",
	//Счет(чек)
	"invoice"     => "\xF0\x9F\x93\x83 ",
	//Рукопожатие
	"handshake"   => "\xF0\x9F\xA4\x9D ",
	//Список
	"clipboard"   => "\xF0\x9F\x93\x8B ",
	//Книги
	"books"       => "\xF0\x9F\x93\x9A ",
	//Звездочка
	"star"        => "\xE2\xAD\x90 ",
];

//Ник пользователя
$username = $indata['message']['from']['username'];

//Имя пользователя
$name_user = $telegram -> FirstName();

//id пользователя
$userid = $indata['message']['from']['id'];

//id чата
$chatid   = $indata['message']['chat']['id'];
$chatuser = $indata['message']['chat']['id'];

//id сообщения
$msg_id = $indata['message']['message_id'];

//Текст сообщения
$message = $indata['message']['text'];

if (!empty($indata['callback_query'])) {

	$indata['message'] = $indata['callback_query']['message'];

	$userid   = $indata['callback_query']['from']['id'];
	$username = $indata['callback_query']['from']['username'];

	$chatid  = $indata['callback_query']['message']['chat']['id'];
	$message = $indata['callback_query']['message']['text'];
	$msg_id  = $indata['callback_query']['message']['message_id'];

	$dataquery = json_decode($indata['callback_query']['data'], true);

	logIt($dataquery, 'IN');

}

//проверяем валидность входящих запросов
if ($identity == 0 || $api_key == '') {

	print 'Error: Unknown or not exist APY-key';

	$d = [
		"chat_id"                  => $chatid,
		"text"                     => 'Error: Unknown or not exist APY-key',
		"parse_mode"               => 'HTML',
		'disable_web_page_preview' => true,
		"disable_notification"     => true
	];

	//запишем ответ об ошибке авторизации
	logIt($d, "AUTH_ERR");

	$telegram -> sendMessage($d);

	exit();

}

//file_put_contents($rootpath."/cash/bot.log", json_encode($dataquery));
//file_put_contents($rootpath."/cash/bot.log", "action = $dataquery[action], period = $dataquery[period], username = $username, message = $message", FILE_APPEND);

//Получаем сообщение и выдаем соответствующий ответ
if (($dataquery['action'] != 'back') || $message == '/start' || $message == $emoji['home']."Главная") {

	if (!empty($dataquery['action']))
		$message = $dataquery['action'];

	switch ($message) {

		// главная страница
		case '/start':
		case $emoji['home']."Главная":
		case 'main':

			$urlCRM = "https://salesman.pro";

			$user = $db -> getRow("SELECT * FROM {$sqlname}sendStatistic_users WHERE username = '$username'");

			//logIt($user, "USER");

			if ($user['id'] > 0) {

				if ($message == '/start') {

					$db -> query("UPDATE {$sqlname}sendStatistic_users SET ?u WHERE id = '$user[id]'", [
						"chatid" => $chatid,
						"userid" => $userid
					]);

				}

				$bot = $db -> getRow("SELECT * FROM {$sqlname}sendStatistic_bots WHERE name = '$botname'");

				//Выводим данные в боте
				if ($message == '/start') {
					
					$telegram -> sendMessage( [
						"chat_id"                  => $chatid,
						"text"                     => "Приветствую, $name_user! Я Бот <b>$bot[name]</b>, вывожу статистику из CRM\nРазработан для <b>SalesMan CRM</b>\n".$emoji['star'].$urlCRM." \n",
						"parse_mode"               => 'HTML',
						'disable_web_page_preview' => true,
						"disable_notification"     => true
					] );
					
				}

				$text = "Выберите действие";

				/*$replyMarkup   = [
					'inline_keyboard'   => [
						[
							[
								"text"          => "Вчера",
								"callback_data" => json_encode(["action" => "perperiod", "period" => "yestoday"])
							],
							[
								"text"          => "Сегодня",
								"callback_data" => json_encode(["action" => "perperiod", "period" => "today"])
							],
							[
								"text"          => "Неделя",
								"callback_data" => json_encode(["action" => "perperiod", "period" => "calendarweek"])
							]
						],
						[
							[
								"text"          => "Месяц",
								"callback_data" => json_encode(["action" => "perperiod", "period" => "month"])
							],
							[
								"text"          => "Квартал",
								"callback_data" => json_encode(["action" => "perperiod", "period" => "quart"])
							],
							[
								"text"          => "Год",
								"callback_data" => json_encode(["action" => "perperiod", "period" => "year"])
							]
						]
					],
					'resize_keyboard'   => true,
					'one_time_keyboard' => false
				];*/

				$replyMarkup   = [
					'inline_keyboard'   => [
						[
							[
								"text"          => $emoji['graph']."Статистика",
								"callback_data" => json_encode([
									"action" => "period"
								])
							],
							[
								"text"          => $emoji['money']."Мой план",
								"callback_data" => json_encode(["action" => "plan"])
							],
							[
								"text" => $emoji['earth']."Перейти в CRM",
								"url"  => $serverhost
							]
						]
					],
					'resize_keyboard'   => true,
					'one_time_keyboard' => false
				];
				$encodedMarkup = json_encode($replyMarkup);

				$telegram -> sendMessage([
					"chat_id"              => $chatid,
					"text"                 => $text,
					"reply_markup"         => $encodedMarkup,
					"parse_mode"           => 'HTML',
					"disable_notification" => true
				]);

			}
			else {

				$d = [
					"chat_id"                  => $chatid,
					"text"                     => 'Пользователь не найден',
					"parse_mode"               => 'HTML',
					'disable_web_page_preview' => true,
					"disable_notification"     => true
				];

				$telegram -> sendMessage($d);

				//запишем ответ об ошибке авторизации
				logIt($d, "USER_NOT_FOUND");

			}

		break;

		case 'period':

			$text = $emoji['calendar']."Выберите период";

			$replyMarkup   = [
				'inline_keyboard'   => [
					[
						[
							"text"          => "Вчера",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => "yestoday"
							])
						],
						[
							"text"          => "Сегодня",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => "today"
							])
						],
						[
							"text"          => "Неделя",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => "calendarweek"
							])
						]
					],
					[
						[
							"text"          => "Месяц",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => "month"
							])
						],
						[
							"text"          => "Квартал",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => "quart"
							])
						],
						[
							"text"          => "Год",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => "year"
							])
						]
					]
				],
				'resize_keyboard'   => true,
				'one_time_keyboard' => false
			];
			$encodedMarkup = json_encode($replyMarkup);


			//Выводим данные в боте
			$telegram -> sendMessage([
				"chat_id"              => $chatid,
				"text"                 => $text,
				"reply_markup"         => $encodedMarkup,
				"parse_mode"           => 'HTML',
				"disable_notification" => true
			]);

		break;

		case 'detale':

			if (is_null($dataquery['parameter'])) {
				$dataquery['parameter'] = '';
			}

			$replyMarkup = [
				'inline_keyboard'   => [
					[
						[
							"text"          => $emoji['people']."Клиенты",
							"callback_data" => json_encode([
								"action"    => "parameter",
								"period"    => $dataquery['period'],
								"parameter" => "clients"
							])
						],
						[
							"text"          => $emoji['handshake']."Новые сделки",
							"callback_data" => json_encode([
								"action"    => "parameter",
								"period"    => $dataquery['period'],
								"parameter" => "dealsNew"
							])
						],
						[
							"text"          => $emoji['invoice']."Новые счета",
							"callback_data" => json_encode([
								"action"    => "parameter",
								"period"    => $dataquery['period'],
								"parameter" => "invoices"
							])
						]
					],
					[
						[
							"text"          => $emoji['money_bag']."Оплаты",
							"callback_data" => json_encode([
								"action"    => "parameter",
								"period"    => $dataquery['period'],
								"parameter" => "payments"
							])
						],
						[
							"text"          => $emoji['lock']."Закрытые сделки",
							"callback_data" => json_encode([
								"action"    => "parameter",
								"period"    => $dataquery['period'],
								"parameter" => "dealsClose"
							])
						]
					],
					[
						[
							"text"          => $emoji['left']."Назад",
							"callback_data" => json_encode([
								"action"    => "parameter",
								"parameter" => $dataquery['parameter']
							])
						],
						[
							"text"          => $emoji['home']."Главная",
							"callback_data" => json_encode(["action" => "main"])
						]
					]
				],
				'resize_keyboard'   => true,
				'one_time_keyboard' => false
			];

			$encodedMarkup = json_encode($replyMarkup);

			/*$telegram -> sendMessage([
				"chat_id"              => $chatid,
				"text"                 => $emoji['books']."Отчеты в CRM: \n".$_SERVER['SERVER_NAME']."/reports.php",
				"reply_markup"         => $encodedMarkup,
				"parse_mode"           => 'HTML',
				"disable_notification" => true
			]);*/

			$telegram -> sendMessage([
				"chat_id"              => $chatid,
				"text"                 => "Выберите показатель",
				"reply_markup"         => $encodedMarkup,
				"parse_mode"           => 'HTML',
				"disable_notification" => true
			]);

			$k = fopen($ypath."/data/actions.log", "w");
			fwrite($k, 'parameters');
			fclose($k);

		break;

		case 'perperiod':

			//output($dataquery['period']);

			$date = $dataquery['period'];

			$iduser = $db -> getOne("SELECT iduser FROM {$sqlname}sendStatistic_users WHERE username = '$username'");
			//Выводим данные в боте

			if ($date == 'yestoday' || $date == 'today') {

				$period = getPeriod($date);
				$text   = $emoji['clipboard']."Отчет \n".str_repeat(" ", 6)." за <b>".format_date_rus($period[0])."</b>\n";

			}
			else {

				$period = getPeriod($date);
				$text   = $emoji['clipboard']."Отчет \n".str_repeat(" ", 6)." за период с <b>".format_date_rus($period[0])."</b> по <b>".format_date_rus($period[1])."</b>\n";

			}

			$f = new Metrics();
			//По клиентам
			$clients = $f -> calculateFact($iduser, 'clientsNew', ['datum' => $period]);

			//По сделкам
			$deals['count']     = $f -> calculateFact($iduser, 'dealsNewAll', ['datum' => $period]);
			$deals['sum']       = $f -> calculateFact($iduser, 'dealsSumAll', ['datum' => $period]);
			$deals['close']     = $f -> calculateFact($iduser, 'dealsCloseAll', ['datum' => $period]);
			$deals['sum_close'] = $f -> calculateFact($iduser, 'dealsSumClose', ['datum' => $period]);

			//По счетам
			$invoices['count'] = $f -> calculateFact($iduser, 'invoicesNewCount', ['datum' => $period]);
			$invoices['sum']   = $f -> calculateFact($iduser, 'invoicesNewSum', ['datum' => $period]);

			//По оплатам
			$payments['count'] = $f -> calculateFact($iduser, 'invoicesDoCount', ['datum' => $period]);
			$payments['sum']   = $f -> calculateFact($iduser, 'invoicesDoSum', ['datum' => $period]);


			$replyMarkup   = [
				'inline_keyboard'   => [
					[
						[
							"text"          => $emoji['openbook']."Детализация",
							"callback_data" => json_encode([
								"action" => "detale",
								"period" => $date
							])
						],
						[
							"text"          => $emoji['calendar']."Изменить период",
							"callback_data" => json_encode(["action" => "main"])
						]
					],
					[
						[
							"text"          => $emoji['left']."Назад",
							"callback_data" => json_encode([
								"action" => "period",
								"period" => $date
							])
						],
						[
							"text"          => $emoji['home']."Главная",
							"callback_data" => json_encode(["action" => "main"])
						]
					]
				],
				'resize_keyboard'   => true,
				'one_time_keyboard' => false
			];
			$encodedMarkup = json_encode($replyMarkup);

			$text .= "
				Новые клиенты:
				 - кол-во:  <b>".$clients."</b> шт.
				
				Новые сделки:
				 - кол-во:  <b>".$deals['count']."</b> шт.
				 - сумма: <b>".num_format($deals['sum'])."</b> $valuta
				
				Новые счета:
				 - кол-во:  <b>".$invoices['count']."</b> шт.
				 - сумма: <b>".num_format($invoices['sum'])."</b> $valuta
				
				Оплаты:
				 - кол-во:  <b>".$payments['count']."</b> шт.
				 - сумма: <b>".num_format($payments['sum'])."</b> $valuta
				
				Закрытые сделки:
				 - кол-во:  <b>".$deals['close']."</b> шт.
				 - сумма: <b>".num_format($deals['sum_close'])."</b> $valuta
			";
			//file_put_contents($rootpath."/cash/bot.log", $text, FILE_APPEND);

			$r = $telegram -> SendMessage([
				"chat_id"              => $chatid,
				"text"                 => str_replace("\t", "", $text),
				'reply_markup'         => $encodedMarkup,
				"parse_mode"           => 'HTML',
				"disable_notification" => true
			]);

			logIt($r, 'OUT');

		break;

		case 'parameter':

			$date   = $dataquery['period'];
			$period = getPeriod($date);

			switch ($dataquery['parameter']) {

				case "clients":

					//$date   = file_get_contents('../data/date.log');
					//$period = getPeriod($date);

					$q = "
						SELECT {$sqlname}user.title as user,
							COUNT({$sqlname}clientcat.clid) as count
						FROM {$sqlname}clientcat
							LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.creator={$sqlname}user.iduser
						WHERE 
							{$sqlname}clientcat.date_create BETWEEN '$period[0]' AND '$period[1]' AND 
							{$sqlname}clientcat.identity = '$identity'
						GROUP BY {$sqlname}clientcat.creator
						ORDER BY count DESC
					";

					$re = $db -> getAll($q);

					$sum = 0;

					foreach ($re as $da) {

						$name_val = yexplode(" ", $da['user']);
						$name_ln  = (int)mb_strlen($name_val[0]);
						$name_min = substr($da['user'], 0, $name_ln * 2 + 3).".";


						$list[] = [
							"user"  => $name_min,
							"count" => $da['count']
						];

						$sum += $da['count'];

					}

					if ($date == 'today' || $date == 'yestoday')
						$text = "Количество новых клиентов\n за <b>".format_date_rus($period[0])."</b>\n\n";

					else
						$text = "Количество новых клиентов \n за период с <b>".format_date_rus($period[0])."</b> по <b>".format_date_rus($period[1])."</b>\n\n";

					//$n = fopen("../data/data.log", "w");

					if (count($list) == 0)
						$text .= "Данные отсутствуют";

					else {

						foreach ($list as $d) {

							$text .= $d['user']."\n".str_repeat(" ", 40)."кол-во:  <b>".$d['count']."</b> шт.\n".str_repeat(" ", 50)."<b>".round(($d['count'] / $sum * 100), 2)."</b>"." %\n";

							//fwrite($n, $d['user']."-".$d['count']."-".round(($d['count'] / $sum * 100), 2)."\n");

						}

					}

					//per_users($text);

					//fclose($n);
					//$k = fopen("../data/parameter.log", "w");
					//fwrite($k, 'клиенты');
					//fclose($k);

					//$k = fopen("../data/actions.log", "w");
					//fwrite($k, 'param');
					//fclose($k);

				break;

				case "dealsNew":

					//$date   = file_get_contents('../data/date.log');
					//$period = getPeriod($date);

					$q = "
						SELECT {$sqlname}user.title as user,
							COUNT({$sqlname}dogovor.did) as count,
							SUM({$sqlname}dogovor.kol) as sum
						FROM {$sqlname}dogovor
							LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.autor={$sqlname}user.iduser
						WHERE 
							{$sqlname}dogovor.datum BETWEEN '$period[0]' AND '$period[1]' AND 
							{$sqlname}dogovor.identity = '$identity'
						GROUP BY {$sqlname}dogovor.autor
						ORDER BY count DESC
					";

					$re  = $db -> getAll($q);
					$sum = 0;
					foreach ($re as $da) {

						$name_val = yexplode(" ", $da['user']);
						$name_ln  = (int)mb_strlen($name_val[0]);
						$name_min = substr($da['user'], 0, $name_ln * 2 + 3).".";

						$list[] = [
							"user"  => $name_min,
							"count" => $da['count'],
							"sum"   => $da['sum']
						];

						$sum += $da['count'];

					}

					if ($date == 'today' || $date == 'yestoday') {
						$text = "Количество новых сделок\n за <b>".format_date_rus( $period[0] )."</b>\n\n";
					}
					else {
						$text = "Количество новых сделок \n за период с <b>".format_date_rus( $period[0] )."</b> по <b>".format_date_rus( $period[1] )."</b>\n\n";
					}

					//$n = fopen("../data/data.log", "w");

					if (empty($list)) {
						$text .= "Данные отсутствуют";
					}
					else {

						foreach ($list as $d) {

							$text .= $d['user']."\n".str_repeat(" ", 40)."кол-во:  <b>".$d['count']."</b> шт.\n".str_repeat(" ", 39)." сумма: <b>".num_format($d['sum'])." </b>$valuta\n".str_repeat(" ", 47)."<b>".round(($d['count'] / $sum * 100), 2)."</b>"." %\n";

							//fwrite($n, $d['user']."-".$d['count']."-".round(($d['count'] / $sum * 100), 2)."\n");

						}

					}

					//per_users($text);

					/*fclose($n);
					$k = fopen("../data/parameter.log", "w");
					fwrite($k, 'сделки');
					fclose($k);

					$k = fopen("../data/actions.log", "w");
					fwrite($k, 'param');
					fclose($k);*/

				break;

				case "invoices":

					//$date   = file_get_contents('../data/date.log');
					//$period = getPeriod($date);

					$q  = "
						SELECT {$sqlname}user.title as user,
							COUNT({$sqlname}credit.clid) as count,
							SUM({$sqlname}credit.summa_credit) as sum
						FROM {$sqlname}credit
							LEFT JOIN {$sqlname}user ON {$sqlname}credit.iduser={$sqlname}user.iduser
						WHERE {$sqlname}credit.datum BETWEEN '$period[0]' AND '$period[1]'
							AND {$sqlname}credit.do != 'on'
							AND {$sqlname}credit.identity = '$identity'
						GROUP BY {$sqlname}credit.iduser
						ORDER BY count DESC
					";
					$re = $db -> getAll($q);

					$sum = 0;

					foreach ($re as $da) {

						$name_val = yexplode(" ", $da['user']);
						$name_ln  = (int)mb_strlen($name_val[0]);
						$name_min = substr($da['user'], 0, $name_ln * 2 + 3).".";

						$list[] = [
							"user"  => $name_min,
							"count" => $da['count'],
							"sum"   => $da['sum']
						];

						$sum += $da['count'];

					}

					if ($date == 'today' || $date == 'yestoday') {
						$text = "Количество выставленных счетов\n за <b>".format_date_rus( $period[0] )."</b>\n\n";
					}
					else {
						$text = "Количество выставленных счетов \n за период с <b>".format_date_rus( $period[0] )."</b> по <b>".format_date_rus( $period[1] )."</b>\n\n";
					}


					//$n = fopen("../data/data.log", "w");

					if (empty($list)) {
						$text .= "Данные отсутствуют";
					}
					else {

						foreach ($list as $d) {

							$text .= $d['user']."\n".str_repeat(" ", 40)."кол-во:  <b>".$d['count']."</b> шт.\n".str_repeat(" ", 39)." сумма: <b>".num_format($d['sum'])." </b>$valuta\n".str_repeat(" ", 47)."<b>".round(($d['count'] / $sum * 100), 2)."</b>"." %\n";

							//fwrite($n, $d['user']."-".$d['count']."-".round(($d['count'] / $sum * 100), 2)."\n");

						}

					}

					//per_users($text);

					/*fclose($n);
					$k = fopen("../data/parameter.log", "w");
					fwrite($k, 'счета');
					fclose($k);

					$k = fopen("../data/actions.log", "w");
					fwrite($k, 'param');
					fclose($k);*/

				break;

				case "payments":

					$q = "
						SELECT {$sqlname}user.title as user,
							COUNT({$sqlname}credit.clid) as count,
							SUM({$sqlname}credit.summa_credit) as sum
						FROM {$sqlname}credit
							LEFT JOIN {$sqlname}user ON {$sqlname}credit.iduser={$sqlname}user.iduser
						WHERE {$sqlname}credit.invoice_date BETWEEN '$period[0]' AND '$period[1]'
							AND {$sqlname}credit.do = 'on'
							AND {$sqlname}credit.identity = '$identity'
						GROUP BY {$sqlname}credit.iduser
						ORDER BY count DESC
					";

					$re = $db -> getAll($q);

					$sum = 0;

					foreach ($re as $da) {

						$name_val = yexplode(" ", $da['user']);
						$name_ln  = (int)mb_strlen($name_val[0]);
						$name_min = substr($da['user'], 0, $name_ln * 2 + 3).".";

						$list[] = [
							"user"  => $name_min,
							"count" => $da['count'],
							"sum"   => $da['sum']
						];

						$sum += $da['count'];

					}

					if ($date == 'today' || $date == 'yestoday') {
						$text = "Количество оплаченных счетов \n за <b>".format_date_rus( $period[0] )."</b>\n\n";
					}
					else {
						$text = "Количество оплаченных счетов\n за период с <b>".format_date_rus( $period[0] )."</b> по <b>".format_date_rus( $period[1] )."</b>\n\n";
					}

					//$n = fopen("../data/data.log", "w");

					if (empty($list)) {
						$text .= "Данные отсутствуют";
					}
					else {

						foreach ($list as $d) {

							$text .= $emoji['man']."Сотрудник: ".$d['user']."
							     - кол-во:  <b>".$d['count']."</b> шт.
							     - сумма: <b>".num_format($d['sum'])." </b>$valuta
							     - доля оплаты: <b>".round(($d['count'] / $sum * 100), 2)."</b>%
							
							";

							//fwrite($n, $d['user']."-".$d['count']."-".round(($d['count'] / $sum * 100), 2)."\n");

						}

					}

					//per_users(str_replace("\t", "", $text));

					/*fclose($n);
					$k = fopen("../data/parameter.log", "w");
					fwrite($k, 'оплаченные счета');
					fclose($k);

					$k = fopen("../data/actions.log", "w");
					fwrite($k, 'param');
					fclose($k);*/

				break;

				case "dealsClose":

					//$date   = file_get_contents('../data/date.log');
					//$period = getPeriod($date);

					$q  = "
						SELECT {$sqlname}user.title as user,
							COUNT({$sqlname}dogovor.did) as count,
							SUM({$sqlname}dogovor.kol) as sum
						FROM {$sqlname}dogovor
							LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.autor={$sqlname}user.iduser
						WHERE {$sqlname}dogovor.datum BETWEEN '$period[0]' AND '$period[1]'
							AND {$sqlname}dogovor.close != 'no'
							AND {$sqlname}dogovor.identity = '$identity'
						GROUP BY {$sqlname}dogovor.autor
						ORDER BY count DESC
					";
					$re = $db -> getAll($q);

					$sum = 0;

					foreach ($re as $da) {

						$name_val = yexplode(" ", $da['user']);
						$name_ln  = (int)mb_strlen($name_val[0]);
						$name_min = substr($da['user'], 0, $name_ln * 2 + 3).".";

						$list[] = [
							"user"  => $name_min,
							"count" => $da['count'],
							"sum"   => $da['sum']
						];

						$sum += $da['count'];

					}

					if ($date == 'today' || $date == 'yestoday') {
						$text = "Количество закрытых сделок\n за <b>".format_date_rus( $period[0] )."</b>\n\n";
					}
					else {
						$text = "Количество закрытых сделок\n за период с <b>".format_date_rus( $period[0] )."</b> по <b>".format_date_rus( $period[0] )."</b>\n\n";
					}

					//$n = fopen("../data/data.log", "w");

					if (empty($list)) {
						$text .= "Данные отсутствуют";
					}
					else {

						foreach ($list as $d) {

							$text .= $d['user']."\n".str_repeat(" ", 40)."кол-во:  <b>".$d['count']."</b> шт.\n".str_repeat(" ", 39)." сумма: <b>".num_format($d['sum'])." </b>$valuta\n".str_repeat(" ", 47)."<b>".round(($d['count'] / $sum * 100), 2)."</b>"." %\n";

							//fwrite($n, $d['user']."-".$d['count']."-".round(($d['count'] / $sum * 100), 2)."\n");

						}

					}

					/*fclose($n);
					$k = fopen("../data/parameter.log", "w");
					fwrite($k, 'закрытые сделки');
					fclose($k);

					$k = fopen("../data/actions.log", "w");
					fwrite($k, 'param');
					fclose($k);*/

				break;

			}

			//per_users(str_replace("\t", "", $text), $date);

			file_put_contents($rootpath."/cash/bot.log", $text, FILE_APPEND);

			$replyMarkup   = [
				'inline_keyboard'   => [
					[
						[
							"text"          => $emoji['left']."Назад",
							"callback_data" => json_encode([
								"action" => "perperiod",
								"period" => $date
							])
						],
						[
							"text"          => $emoji['home']."Главная",
							"callback_data" => json_encode(["action" => "main"])
						]
					]
				],
				'resize_keyboard'   => true,
				'one_time_keyboard' => false
			];
			$encodedMarkup = json_encode($replyMarkup);

			$r = $telegram -> sendMessage($r1 = [
				"chat_id"              => $chatid,
				"text"                 => str_replace("\t", "", $text),
				"reply_markup"         => $encodedMarkup,
				"parse_mode"           => 'HTML',
				"disable_notification" => true
			]);

			logIt($r1, 'OUT1');
			logIt($r, 'OUT2');

		break;

		case "plan":

			$y = date('Y');
			$m = date('m');

			$metrika = new Metrics();
			$plan = $metrika -> getPlanDo($userinfo['iduser'], date('Y'), date('m'), true);


			$text = str_replace(["\t","\n\r"], "",
				"Текущее выполнение плана за $m.$y
			   - Оборот = ".num_format($plan['summa'])." $valuta [ ".$plan['summaPercent']."% ]
			   - Маржа  = ".num_format($plan['marga'])." $valuta [ ".$plan['margaPercent']."% ]
			   
				План на $m.$y
			   - Оборот = ".num_format($plan['summaPlan'])." $valuta
			   - Маржа  = ".num_format($plan['margaPlan'])." $valuta
			");

			$replyMarkup   = [
				'inline_keyboard'   => [
					[
						[
							"text"          => $emoji['left']."Назад",
							"callback_data" => json_encode([
								"action"    => "main"
							])
						],
						[
							"text"          => $emoji['graph']."Статистика",
							"callback_data" => json_encode([
								"action" => "period"
							])
						],
						[
							"text" => $emoji['earth']."Перейти в CRM",
							"url"  => $serverhost
						]
					]
				],
				'resize_keyboard'   => true,
				'one_time_keyboard' => false
			];
			$encodedMarkup = json_encode($replyMarkup);

			$telegram -> sendMessage([
				"chat_id"              => $chatid,
				"text"                 => $text,
				"reply_markup"         => $encodedMarkup,
				"parse_mode"           => 'HTML',
				"disable_notification" => true
			]);

		break;

		case $emoji['diagram']."Построить график":

			$cur_date = date('d.m.Y H:i:s');
			create_graph($cur_date);

		break;

		//Вывод сообщения в случае неверной команды
		default:

			$text = $emoji['grust']."К сожалению, такой команды нет.\n Вы сказали: <em>\"".$indata['message']['text']."\"</em> ";

			$telegram -> sendMessage([
				"chat_id"    => $chatid,
				"text"       => $text,
				"parse_mode" => 'HTML'
			]);

		break;

	}

}

else {

	if (is_null($dataquery['parameter'])) {
		$dataquery['parameter'] = '';
	}

	$replyMarkup   = [
		'inline_keyboard'   => [
			[
				[
					"text"          => "Вчера",
					"callback_data" => json_encode([
						"period"    => "yestoday",
						"parameter" => $dataquery['parameter']
					])
				],
				[
					"text"          => "Сегодня",
					"callback_data" => json_encode([
						"period"    => "today",
						"parameter" => $dataquery['parameter']
					])
				],
				[
					"text"          => "Неделя",
					"callback_data" => json_encode([
						"period"    => "calendarweek",
						"parameter" => $dataquery['parameter']
					])
				]
			],
			[
				[
					"text"          => "Месяц",
					"callback_data" => json_encode([
						"period"    => "month",
						"parameter" => $dataquery['parameter']
					])
				],
				[
					"text"          => "Квартал",
					"callback_data" => json_encode([
						"period"    => "quart",
						"parameter" => $dataquery['parameter']
					])
				],
				[
					"text"          => "Год",
					"callback_data" => json_encode([
						"period"    => "year",
						"parameter" => $dataquery['parameter']
					])
				]
			]
		],
		'resize_keyboard'   => true,
		'one_time_keyboard' => false
	];
	$encodedMarkup = json_encode($replyMarkup);

	$telegram -> sendMessage([
		"chat_id"              => $chatid,
		"text"                 => $emoji['calendar']."Выберите период",
		"reply_markup"         => $encodedMarkup,
		"parse_mode"           => 'HTML',
		"disable_notification" => true
	]);

}

unset($telegram);

print "100";

flush();