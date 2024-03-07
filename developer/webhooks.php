<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2018.x          */
/* ============================ */

/**
 *  Обработчик для событий
 */
error_reporting(E_ERROR);

include "../inc/licloader.php";
include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";

$path = realpath(__DIR__.'/../');

function hookSenderNew($url, $data = []){

	$data['identity'] = $GLOBALS['identity'];
	$data['iduser1']  = $GLOBALS['iduser1'];

	$rootpath = realpath(__DIR__.'/../');
	$ses = $data['ses'];

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
function hookSender($url, $params) {

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);
	$err = curl_error($ch);

	if ($result === false)
		return $err;
	else
		return $result;

}

$params   = $_REQUEST;
$event    = $_REQUEST['event'];
$identity = $_REQUEST['identity'];
$iduser1  = $_REQUEST['iduser1'];

//echo json_encode_cyr($_REQUEST);

/*
$f = fopen($path."/cash/webhooks-log.log", "a");
fwrite($f, current_datumtime()." :: ".json_encode_cyr($params));
fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
fclose($f);
*/

ob_flush();
flush();

$Home = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER["HTTP_HOST"];

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

$eventlist = $db -> getAll("SELECT * FROM ".$sqlname."webhook WHERE event = '$event' and identity = '$identity' ORDER BY title");
foreach ($eventlist as $item) {

	$params['identity'] = $identity;

	//проверяем плагин на активацию
	$pluginName = $item['title'];

	//находим плагин по заголовку
	$plugin = $db -> getOne("SELECT name FROM {$sqlname}plugins WHERE name = '$pluginName' and identity = '$identity' LIMIT 1");

	//если вебхук связан с плагином, то проверим его на активность и в случае неактивности - выходим
	if ($plugin != '') {

		$pluginActive = $db -> getOne("SELECT active FROM {$sqlname}plugins WHERE name = '$plugin' and identity = '$identity' ORDER BY name");
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
	$db -> query("INSERT INTO ".$sqlname."webhooklog SET ?u", $data);

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

/*
$f = fopen($_SERVER["DOCUMENT_ROOT"]."/developer/webhooks.log", "a");
fwrite($f, current_datumtime()." :: \"SELECT * FROM ".$sqlname."webhook WHERE event = '".$event."' and identity = '$identity' ORDER BY title\"");
fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
fclose($f);
*/

exit();