<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: *');

$rootpath = dirname( __DIR__, 3 );
$ypath    = $rootpath."/plugins/getStatistic";

if (!file_exists($rootpath."/cash/viber_error.log")) {

	$file = fopen($rootpath."/cash/viber_error.log", 'wb' );
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $rootpath."/cash/viber_error.log");

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/class/Statistic.php";

require_once $ypath."/vendor/core.php";
require_once $ypath."/vendor/Manager.php";

$indata = json_decode(file_get_contents('php://input'), true);

if (!in_array($indata['event'], [
	"message",
	"conversation_started"
])) {
	goto ext;
}

$indata['api_key'] = $_REQUEST['apikey'];
$indata['botid']   = $_REQUEST['botid'];

//Найдем identity по настройкам
$res      = $db -> getRow("select id, api_key, timezone,valuta from ".$sqlname."settings where api_key = '$indata[api_key]'");
$tmzone   = $res['timezone'];
$api_key  = $res['api_key'];
$identity = (int)$res['id'];
$valuta   = $res['valuta'];

date_default_timezone_set($tmzone);

$f = fopen($rootpath."/cash/viber-webhooks.log", "a");
fwrite($f, current_datumtime()."::\n");
fwrite($f, json_encode_cyr($indata)."\n");
fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n");
fclose($f);

//проверяем валидность входящих запросов
if ($identity == 0 || $api_key == '') {

	print "Error: Unknown or not exist APY-key";
	exit();

}

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = $dzz / 3600;

$db -> query("SET time_zone = '+".$bdtimezone.":00'");

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
$serverhost = $scheme.$_SERVER["HTTP_HOST"];

/**
 * работаем с данными
 */
$sender = [];

$username = $indata['sender']['name'];
$userid   = $indata['sender']['id'];

$text = $indata['message']['text'];
$bot  = Manager ::BotInfo($indata['botid']);

$parameters = json_decode($text, true);

// Массив, содержащий эмоджи для сообщений
$emoji = [
	//Смайл
	"smile"       => "\xF0\x9F\x98\x89 ",
	//Дом
	"home"        => "\xF0\x9F\x8F\xA0 ",
	//Назад
	"back"        => "\xF0\x9F\x94\x99 ",
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
	"check"       => "\xE2\x98\x91 ",
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

//получено сообщение
if ($indata['event'] == "message" || $indata['event'] == "conversation_started") {

	//Ищем сотрудника
	$userinfo = Manager ::UserInfo($userid);

	file_put_contents($ypath."/data/viber-info.json", json_encode_cyr([
		"bot" => $bot,
		"user" => $userinfo
	]));

	if ($userinfo['id'] > 0 && $userinfo['isunlock'] && $userinfo['active']) {

		// раздел Главная
		if ($parameters['action'] == "home") {

			$msg['message'] = "Я бот \"".$bot['name']."\".\nЯ могу выдать вам статистику по вашей CRM.";

			$keyboard = [
				"Type"    => "keyboard",
				"Buttons" => [
					[
						"Columns"      => 2,
						"Rows"         => 1,
						"BgColor"      => "#FFFFFF",
						"ActionType"   => "reply",
						"ActionBody"   => json_encode(["action" => "stata"]),
						"Image"        => "",
						"Text"         => $emoji['graph']."<br>Статистика",
						"TextPaddings" => [0, 0, 0, 0],
						"TextOpacity"  => 100,
						"TextSize"     => "medium"
					],
					[
						"Columns"      => 2,
						"Rows"         => 1,
						"BgColor"      => "#FFFFFF",
						"ActionType"   => "reply",
						"ActionBody"   => json_encode(["action" => "plan"]),
						"Image"        => "",
						"Text"         => $emoji['money']."<br>Мой план",
						"TextPaddings" => [0, 0, 0, 0],
						"TextOpacity"  => 100,
						"TextSize"     => "medium"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "open-url",
						"ActionBody"  => $serverhost,
						"Text"        => $emoji['earth']."<br>Перейти в CRM",
						"TextPaddings" => [0, 0, 0, 0],
						"TextOpacity" => 100,
						"TextSize"    => "small"
					]
				]
			];

		}

		elseif ($parameters['action'] == "plan") {

			$y = date('Y');
			$m = date('m');

			$metrika = new Salesman\Metrics();
			$plan = $metrika -> getPlanDo($userinfo['iduser'], date('Y'), date('m'), true);


			$msg['message'] = str_replace(["\t","\n\r"], "",
			"Текущее выполнение плана за $m.$y
			   - Оборот = ".num_format($plan['summa'])." $valuta [ ".$plan['summaPercent']."% ]
			   - Маржа  = ".num_format($plan['marga'])." $valuta [ ".$plan['margaPercent']."% ]
			   
			План на $m.$y
			   - Оборот = ".num_format($plan['summaPlan'])." $valuta
			   - Маржа  = ".num_format($plan['margaPlan'])." $valuta
			");

			$keyboard = [
				"Type"          => "keyboard",
				"DefaultHeight" => false,
				"Buttons"       => [
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "home"]),
						"Text"        => $emoji['back']."<br>Назад",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"      => 3,
						"Rows"         => 1,
						"BgColor"      => "#FFFFFF",
						"ActionType"   => "reply",
						"ActionBody"   => json_encode(["action" => "stata"]),
						"Image"        => "",
						"Text"         => $emoji['graph']."<br>Статистика",
						"TextPaddings" => [0, 0, 0, 0],
						"TextOpacity"  => 100,
						"TextSize"     => "medium"
					]
				]
			];

		}

		// раздел статистика + выбор показателя
		elseif ($parameters['action'] == "stata") {

			$msg['message'] = "Выберите параметр";

			$keyboard = [
				"Type"          => "keyboard",
				"DefaultHeight" => false,
				"Buttons"       => [
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#F5F5F5",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => "all"]),
						"Text"        => "Все показатели",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => "dealsNew"]),
						"Image"       => "",
						"Text"        => "Новые сделки",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#F5F5F5",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => "invoices"]),
						"Text"        => "Новые счета",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => "clients"]),
						"Text"        => "Новые клиенты",
						"TextOpacity" => 100,
						"TextSize"    => "regular"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#F5F5F5",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => "dealsClose"]),
						"Text"        => "Закрытые сделки",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => "payments"]),
						"Text"        => "Оплаты",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "home"]),
						"Text"        => $emoji['back']."<br>Назад",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "home"]),
						"Text"        => $emoji['home']."<br>Главная",
						"TextOpacity" => 100,
						"TextSize"    => "regular"
					]
				]
			];

		}

		// раздел выбора периода
		elseif ($parameters['action'] == "period") {

			$prm = $parameters['parameter'];

			$msg['message'] = "Выберите период";
			//Периоды
			$keyboard = [
				"Type"          => "keyboard",
				"Buttons"       => [
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#CFD8DC",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "parameter", "parameter" => $parameters['parameter'], "period" => "yestoday"]),
						"Image"       => "",
						"Text"        => "Вчера",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#66BB6A",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "parameter", "parameter" => $parameters['parameter'], "period" => "today"]),
						"Text"        => $emoji['calendar']."Сегодня",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "parameter", "parameter" => $parameters['parameter'], "period" => "calendarweek"]),
						"Image"       => "",
						"Text"        => "Неделя",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "parameter", "parameter" => $parameters['parameter'], "period" => "month"]),
						"Image"       => "",
						"Text"        => "Месяц",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "parameter", "parameter" => $parameters['parameter'], "period" => "quart"]),
						"Image"       => "",
						"Text"        => "Квартал",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "parameter", "parameter" => $parameters['parameter'], "period" => "year"]),
						"Image"       => "",
						"Text"        => "Год",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "stata"]),
						"Text"        => $emoji['back']."<br>Назад",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					],
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "home"]),
						"Text"        => $emoji['home']."<br>Главная",
						"TextOpacity" => 100,
						"TextSize"    => "regular"
					]
				]
			];

		}

		// раздел вывода показателя за период
		elseif ($parameters['action'] == "parameter") {

			$prm = $parameters['parameter'];

			$params = [
				"user"    => $userinfo['iduser'],
				"diagram" => "yes"
			];

			if ($parameters['parameter'] == 'all') {

				$data = Salesman\Statistic ::$prm($parameters['period'], $params);

				$msg['message'] = str_replace(["\t","\n\r"], "",
					"Период ".$data['period']."
					
					Новые клиенты:
					   - ".$data['details']['clients']." шт.
					
					Новые сделки:
					   - ".$data['details']['deals']['new']['count']." шт. 
					   - ".num_format($data['details']['deals']['new']['sum']).$valuta."
					
					Закрытые сделки:
					   - ".$data['details']['deals']['close']['count']." шт.
					   - ".num_format($data['details']['deals']['close']['sum']).$valuta."
					
					Новые счета:
					   - ".$data['details']['invoices']['count']." шт.
					   - ".num_format($data['details']['invoices']['sum']).$valuta."
					
					Оплаченные счета:
					   - ".$data['details']['payments']['count']." шт.
					   - ".num_format($data['details']['payments']['sum'])." ".$valuta."
					");

			}
			else {

				$data = Salesman\Statistic ::$prm($parameters['period'], $params);

				$details        = $data['details'];
				$sum            = 0;
				$kolParam       = 0;
				$msg['message'] = "Период ".$data['period']."\n";

				$txt = '';

				foreach ($details as $i => $d) {

					$txt .= "
					".$emoji['man'].$d['user'].":
					   - ".$d['count']." шт.
					".($d['summa'] != "" ? "   - ".num_format($d['summa'])." ".$valuta : "")."
					".($d['part']  != "" ? "   - ".$d['part'] : "")."";

					if($d['summa'] != "")
						$sum += $d['summa'];

					$kolParam += $d['count'];

				}

				if ($kolParam <= 0)
					$txt .= "\n".$details;

				else
					$txt .= "
					~~~~~~~~~~~~~~~~~~~~~~
					Итого: 
					   - ".$kolParam." шт.
					".($sum > 0 ? "   - ".num_format($sum)." ".$valuta : "");

				$msg['message'] .= str_replace(["\t","\n\r"], "",$txt);

			}

			$keyboard = [
				"Type"          => "keyboard",
				"DefaultHeight" => false,
				"Buttons"       => [
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "period", "parameter" => $parameters['parameter']]),
						"Text"        => $emoji['calendar']."<br>Период",
						"TextOpacity" => 100,
						"TextSize"    => "regular"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#F5F5F5",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "stata"]),
						"Text"        => $emoji['key']."<br>Показатель",
						"TextOpacity" => 100,
						"TextSize"    => "regular"
					],
					[
						"Columns"     => 2,
						"Rows"        => 1,
						"BgColor"     => "#E0E0E0",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "home"]),
						"Text"        => $emoji['home']."<br>Главная",
						"TextOpacity" => 100,
						"TextSize"    => "regular"
					]
				]
			];

			$sender = [
				"name" => $data['title']
			];

		}

		// неизвестный текст
		elseif ($text != "https://salesman.pro" && $text != $_SERVER["HTTP_HOST"]) {

			$msg['message'] = "Вы написали: '".$text."'.\nК сожалению, такой команды нет.\nДля удобства воспользуйтесь клавиатурой!";

			$keyboard = [
				"Type"          => "keyboard",
				"DefaultHeight" => false,
				"BgColor"       => "#FFFFFF",
				"Buttons"       => [
					[
						"Columns"          => 3,
						"Rows"             => 1,
						"BgColor"          => "#FFFFFF",
						"ActionType"       => "reply",
						"ActionBody"       => json_encode(["action" => "stata"]),
						"Image"            => "",
						"Text"             => $emoji['graph']."<br>Статистика",
						"TextOpacity"      => 100,
						"TextSize"         => "medium"
					],
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "open-url",
						"ActionBody"  => $serverhost,
						"Text"        => $emoji['check']."<br>Перейти в CRM",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					]
				]
			];

		}

	}
	elseif ($userinfo['id'] > 0 && (!$userinfo['isunlock'] || !$userinfo['active'])) {

		$msg['message'] = "Приветствую, $userinfo[user]!\nК сожалению, Ваш аккаунт в CRM заблокирован. Обратитесь к администратору.";

	}
	else {

		//Найдем пользователя по номеру телефона (если он прислал номер)
		$array1 = getPhoneFromText( $text );
		$phone  = substr(preg_replace("/[^0-9]/", "", array_shift( $array1 )), 1);

		$array  = getEmailFromText( $text );
		$email  = array_shift( $array );

		if ($email != '' || $phone != '') {

			$s = '';

			if ($email != '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$s .= "(login = '$email' OR email = '$email') AND";
			}

			elseif ($phone != '' && isPhoneMobile("7".$phone)) {
				$s .= "(mob LIKE '%$phone' OR phone LIKE '%$phone') AND";
			}

			$iduser = ($s != '') ? (int)$db -> getOne("SELECT iduser FROM ".$sqlname."user WHERE $s identity = '$identity'") : 0;

		}

		if ($iduser > 0) {

			$uarg = [
				"botid"    => $bot['id'],
				"iduser"   => $iduser,
				"userid"   => $userid,
				"username" => $username
			];

			$u   = new Manager();
			$res = $u -> UserSave(0, $uarg);

			$msg['message'] = current_user($iduser, "yes").", Вы успешно авторизованы в боте.";

			$keyboard = [
				"Type"          => "keyboard",
				"DefaultHeight" => false,
				"BgColor"       => "#FFFFFF",
				"Buttons"       => [
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "reply",
						"ActionBody"  => json_encode(["action" => "stata"]),
						"Image"       => "",
						"Text"        => $emoji['graph']."<br>Статистика",
						"TextOpacity" => 100,
						"TextSize"    => "medium"
					],
					[
						"Columns"     => 3,
						"Rows"        => 1,
						"BgColor"     => "#FFFFFF",
						"ActionType"  => "open-url",
						"ActionBody"  => $serverhost,
						"Text"        => $emoji['check']."<br>Перейти в CRM",
						"TextOpacity" => 100,
						"TextSize"    => "small"
					]
				]
			];

		}
		elseif (($email != '' || $phone != '') && $iduser == 0) {

			$msg['message'] = "Приветствую!\nЯ всё еще тебя не знаю.\n\nЧтобы авторизоваться, сообщи мне свой ЛОГИН, EMAIL или МОБИЛЬНЫЙ (11 цифр) от SalesMan CRM.";

		}
		else {

			$msg['message'] = "Приветствую, Незнакомец!\n\nЧтобы авторизоваться, сообщите мне свой ЛОГИН, EMAIL или МОБИЛЬНЫЙ (11 цифр) от SalesMan CRM.";

		}

	}


	// Инфо об отправителе сообщ.
	if (!$sender) {
		$sender = [
			"name" => $bot['name'],
		];
	}

	if (!$msg['type']) {
		$msg['type'] = "text";
	}

	if (!$msg['receiver']) {
		$msg['receiver'] = $userid;
	}

	$viber = new Viber($bot['token']);
	$viber -> sendMessage($msg['type'], $msg['message'], $msg['receiver'], $sender, $other, $keyboard);

	//print_r($viber);

}

ext:

print "100";