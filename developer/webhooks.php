<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2018.x          */
/* ============================ */

/**
 *  Обработчик для событий
 *
 *  Отвечает вызывающему СРАЗУ (fire-and-forget), и только затем выполняет
 *  рассылку подписчикам. События CRM вызываются синхронно внутри бизнес-
 *  операции (content/core/*.php -> event::fire() -> outSender() в
 *  developer/events.php), а внешние подписчики могут отвечать секундами
 *  (sendRequestStream: таймаут 10 с на каждого). Без раннего ответа основной
 *  процесс CRM ждал бы завершения всей рассылки.
 */
error_reporting(E_ERROR);

/**
 * Немедленная отдача ответа вызывающему.
 *
 * Вызывается ДО include-ов и до выборки вебхуков: ответ от них не зависит.
 * После отдачи ответа PHP-процесс продолжает работу (ignore_user_abort) и
 * досылает вебхуки в фоне.
 */
function webhookAcceptAndContinue(): void {

	// дожить до конца рассылки, даже если вызывающий уже отключился
	ignore_user_abort(true);
	@set_time_limit(0);

	// не даём сжатию/буферизации придержать ответ до конца скрипта
	@ini_set('zlib.output_compression', '0');
	if (function_exists('apache_setenv')) {
		@apache_setenv('no-gzip', '1');
	}

	while (ob_get_level() > 0) {
		@ob_end_clean();
	}

	$body = '{"status":"accepted"}';

	if (!headers_sent()) {
		http_response_code(200);
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Length: '.strlen($body));
		header('Connection: close');
		header('X-Accel-Buffering: no');
	}

	echo $body;

	// php-fpm: ответ отдан, соединение закрыто, скрипт продолжает работу
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
		return;
	}

	// mod_php / php-cgi: выталкиваем ответ клиенту
	@flush();

}

/**
 * Может ли веб-сервер отдать ответ раньше конца скрипта.
 *
 * php-fpm завершает запрос через fastcgi_finish_request(), mod_php отдаёт
 * ответ по flush(). php-cgi под mod_proxy_fcgi/mod_fcgid копит ответ до конца
 * скрипта — для такого окружения рассылка выносится в фоновый воркер.
 */
function webhookCanFinishEarly(): bool {

	if (function_exists('fastcgi_finish_request')) {
		return true;
	}

	return PHP_SAPI === 'apache2handler';

}

/**
 * Базовый адрес CRM (схема + хост).
 *
 * Нужен для подстановки {HOME} в url вебхуков и для фонового самозапроса.
 * $_SERVER['HTTP_SCHEME'] в этом проекте встречается в разных видах
 * ('https://', полный базовый адрес), поэтому нормализуем его, а не доверяем
 * как есть.
 */
function webhookBaseURL(): string {

	$host = (string)($_SERVER['HTTP_HOST'] ?? '');

	$scheme = trim((string)($_SERVER['HTTP_SCHEME'] ?? ''));

	if ($scheme !== '') {

		$base = rtrim($scheme, '/');

		// полный базовый адрес (уже с хостом) — используем как есть
		if (parse_url($base, PHP_URL_HOST) !== NULL) {
			return $base;
		}

		$scheme = rtrim($base, ':');

	}

	if ($scheme === '') {
		$scheme = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 443 == (int)($_SERVER['SERVER_PORT'] ?? 0)) ? 'https' : 'http';
	}

	return ($host !== '') ? $scheme.'://'.$host : '';

}

/**
 * Адрес этого же скрипта на текущем хосте — для фонового самозапроса.
 */
function webhookSelfURL(): string {

	$base = webhookBaseURL();

	if ($base === '') {
		return '';
	}

	// из базового адреса берём только origin: путь берём из текущего запроса
	$parts = parse_url($base);

	if (empty($parts['host'])) {
		return '';
	}

	$origin = ($parts['scheme'] ?? 'http').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');

	return $origin.($_SERVER['SCRIPT_NAME'] ?? '/developer/webhooks.php');

}

/**
 * Фоновая рассылка отдельным запросом к этому же скрипту (worker).
 *
 * Нужна там, где веб-сервер физически не умеет отдать ответ раньше конца
 * скрипта: обработчик отвечает вызывающему и завершается, а рассылку
 * выполняет отдельный worker-запрос. Возвращает true, если сервер принял
 * запрос (ответ воркера не ждём — он не влияет на основной процесс).
 */
function webhookDispatchWorker(array $params): bool {

	if (!function_exists('curl_init')) {
		return false;
	}

	$url = webhookSelfURL();

	if ($url === '') {
		return false;
	}

	$params['__webhook_worker'] = '1';

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER    => true,
		CURLOPT_POST              => true,
		CURLOPT_POSTFIELDS        => http_build_query($params),
		CURLOPT_CONNECTTIMEOUT_MS => 2000,
		CURLOPT_TIMEOUT_MS        => 50, // ждём только постановки запроса на сервер
		CURLOPT_NOSIGNAL          => true,
		CURLOPT_SSL_VERIFYPEER    => false,
		CURLOPT_SSL_VERIFYHOST    => false,
	]);
	curl_exec($ch);
	$errno     = curl_errno($ch);
	$connected = (float)curl_getinfo($ch, CURLINFO_CONNECT_TIME) > 0;
	curl_close($ch);

	// 0  — воркер успел отработать целиком (например, подписчиков нет)
	// 28 — таймаут: запрос уже передан серверу, воркер выполняется в фоне
	return $errno === 0 || ($errno === 28 && $connected);

}

$rootpath = dirname(__DIR__);

$params   = $_REQUEST;
$event    = (string)($params['event'] ?? '');
$identity = (int)($params['identity'] ?? 0);
$iduser1  = (int)($params['iduser1'] ?? 0);
$isWorker = (($params['__webhook_worker'] ?? '') === '1');

if ($isWorker) {

	// фоновый воркер: вызывающему ответ уже отдан, здесь только рассылка
	ignore_user_abort(true);
	@set_time_limit(0);

	unset($params['__webhook_worker'], $_REQUEST['__webhook_worker']);

}
else {

	// 1. Ответ вызывающему — немедленно и до любой работы
	webhookAcceptAndContinue();

	// 2. Сервер не умеет отдать ответ раньше конца скрипта — рассылаем в фоне.
	//    Если фоновый запрос не удался, ниже отработает обычная синхронная рассылка.
	$__dispatched = !webhookCanFinishEarly() && webhookDispatchWorker($params);
	if ($__dispatched) {
		exit;
	}

}

include $rootpath."/inc/licloader.php";
include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

/**
 * inc/auth.php и inc/settings.php сами выставляют глобальные $identity/$iduser1
 * (по сессии или принудительно = 1), поэтому параметры события перечитываем из
 * запроса уже после include-ов — иначе мультитенантная установка разошлёт
 * вебхуки не по той identity. Так было и до раннего ответа.
 */
$params   = $_REQUEST;
$event    = (string)($params['event'] ?? '');
$identity = (int)($params['identity'] ?? 0);
$iduser1  = (int)($params['iduser1'] ?? 0);

function hookSenderNew($url, $data = []): false|string {

	$data['identity'] = $GLOBALS['identity'];
	$data['iduser1']  = $GLOBALS['iduser1'];

	$rootpath = dirname(__DIR__);

	// сессию наружу не передаём (и не логируем), даже если она пришла в данных
	unset($data['ses']);

	/*
	$HTTP = [
		"http" => // Обертка, которая будет использоваться
			[
				"method"  => "POST", // Request Method
				// Ниже задаются заголовки запроса
				"header"  =>
					"Content-type: application/x-www-form-urlencoded;".
					"Cookie: ses={$ses}; path=/; domain=".$_SERVER["HTTP_HOST"].";",
				"content" => http_build_query($data)
			]
	];

	/*
	$context = stream_context_create($HTTP);
	$contents = file_get_contents($url, false, $context);
	*/

	$contents = sendRequestStream($url, $data);

	$logfile = $rootpath."/cash/webhook-events.log";
	$text =
		current_datumtime()."
		TYPE: webhooks
		URL:
		$url
		Отправленные данные:
		".json_encode_cyr($data)."
		Ответ:
		$contents
		~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n
		";

	file_put_contents($logfile, str_replace("\t", "", $text), FILE_APPEND);

	return $contents;

}
function hookSender($url, $params): bool|string {

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);
	$err = curl_error($ch);

	if ($result === false) {
		return $err;
	}

	return $result;

}

// $params / $event / $identity / $iduser1 прочитаны в начале скрипта, а ответ
// вызывающему уже отдан (webhookAcceptAndContinue) — дальше только рассылка.

//базовый адрес CRM для подстановки {HOME} в url вебхука
$Home = webhookBaseURL();

//список событий, на которые можем поставить хук
$eventsRegistered = [
	"client.import"      => "Импорт клиентов",
	"client.expressadd"  => "Добавлен клиент,Контакт. Экспресс-форма",
	"client.add"         => "Добавлен клиент",
	"client.edit"        => "Изменен клиент",
	"client.delete"      => "Удален клиент",
	"client.change.recv" => "Изменены реквизиты клиента",
	"client.change.user" => "Изменен ответственный клиента",
	"person.add"         => "Добавлен контакт",
	"person.edit"        => "Изменен контакт",
	"person.delete"      => "Удален контакт",
	"person.change.user" => "Изменен ответственный контакта",
	"deal.import"        => "Импорт сделок",
	"deal.add"           => "Добавлена сделка",
	"deal.edit"          => "Изменена сделка",
	"deal.delete"        => "Удалена сделка",
	"deal.change.user"   => "Изменен ответственный за сделку",
	"deal.change.step"   => "Изменен этап сделки",
	"deal.close"         => "Закрыта сделка",
	"invoice.add"        => "Добавлен счет",
	"invoice.edit"       => "Изменен счет",
	"invoice.doit"       => "Оплачен счет",
	"invoice.expressadd" => "Внесена оплата по сделке",
	"task.add"           => "Напоминание добавлено",
	"task.edit"          => "Напоминание изменено",
	"task.doit"          => "Напоминание выполнено",
	"history.add"        => "Добавлена активность",
	"history.edit"       => "Изменена активность",
	"lead.add"           => "Добавлена заявка",
	"lead.setuser"       => "Назначен ответственный по заявке",
	"lead.do"            => "Обработана заявка",
	"entry.add"          => "Добавлено обращение",
	"entry.status"       => "Обработано обращение",
	"contract.add"       => "Добавлен документ",
	"contract.edit"      => "Изменен документ",
	"contract.delete"    => "Удален документ",
	"akt.add"            => "Добавлен акт",
	"akt.edit"           => "Изменен акт",
	"akt.delete"         => "Удален акт",
];

$eventlist = $db -> getAll("SELECT * FROM {$sqlname}webhook WHERE event = ?s and identity = ?i ORDER BY title", $event, $identity);
foreach ($eventlist as $item) {

	$params['identity'] = $identity;

	//проверяем плагин на активацию
	$pluginName = $item['title'];

	//находим плагин по заголовку
	$plugin = $db -> getOne("SELECT name FROM {$sqlname}plugins WHERE name = ?s and identity = ?i LIMIT 1", $pluginName, $identity);

	//если вебхук связан с плагином, то проверим его на активность и в случае неактивности - выходим
	if ($plugin != '') {

		$pluginActive = $db -> getOne("SELECT active FROM {$sqlname}plugins WHERE name = ?s and identity = ?i ORDER BY name", $plugin, $identity);
		if ($pluginActive == 'off'){
			continue;
		}

	}

	//выполняем хук, если задан url
	$response = ($item['url'] != '') ? hookSenderNew(str_replace("{HOME}", $Home, $item['url']), $params) : "Не задан URL";
	//$response = ($item['url'] != '') ? hookSender(str_replace("{HOME}", $Home, $item['url']), http_build_query($params)) : "Не задан URL";

	unset($db);
	$db = new SafeMySQL($opts);

	//формируем массив данных для внесения в лог
	$data = [
		"event"    => $event,
		"query"    => json_encode_cyr($params),
		"response" => $response,
		"identity" => $identity
	];

	//логгируем запрос-ответ
	$db -> query("INSERT INTO {$sqlname}webhooklog SET ?u", $data);

	$logfile = $rootpath."/cash/webhooks.log";
	$text =
		current_datumtime()."
		Входящие данные:
		".json_encode_cyr($params)."
		
		Ответ:
		".$response."
		~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n
		";
	file_put_contents($logfile, str_replace("\t", "", $text), FILE_APPEND);

}

// очистим старые логи (старше 7 дней)
$db -> query("DELETE FROM {$sqlname}webhooklog WHERE DATE(datum) > DATE(NOW() - INTERVAL 7 DAY)");

exit();