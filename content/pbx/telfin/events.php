<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2016.10       */
/* ============================ */

/**
 * файл для получение данных с Телфин
 */
error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: *');

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

require_once dirname( __DIR__)."/telfin/mfunc.php";

$ypath    = $rootpath."/content/pbx/telfin/";
$return   = [];
$response = $_REQUEST;

/**
 * Адаптация под мультиаккаунт
 */
$apikey = $_REQUEST['crm_token'];

if( $isCloud ){

	$result = $db -> getRow("SELECT id, api_key FROM ".$sqlname."settings WHERE api_key = '$apikey'");
	$identity = (int)$result['id'];
	$api_key  = $result['api_key'];

	if(!$identity){

		$f = fopen($rootpath."/cash/telfin-worker.log", "a");
		fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
		fwrite($f, "Ошибка: Не верный параметр crm_token\r");
		fwrite($f, "========================\r\r");
		fclose($f);

		exit();

	}

}
else {
	$identity = $GLOBALS['identity'];
}


//определение смещния времени UTC (в базе храниться наше время, у телефонии в UTC) В часах
$clientTimeZone = $db -> getOne("select timezone from {$sqlname}settings WHERE id = '$identity'");
$tz             = new DateTimeZone($clientTimeZone);
$dz             = new DateTime();
$dzz            = $tz -> getOffset($dz);
$clientOffset   = $dzz / 3600;


$status_hist = [
	// вызов был отвечен
	"ANSWER"      => "ANSWERED",
	//звонок не отвечен (истек таймер ожидания на сервере)
	"NOANSWER"    => "NOANSWER",
	//произошла ошибка во время вызова
	"CONGESTION"  => "BREAKED",
	//у вызываемого абонента отсутствует регистрация
	"CHANUNAVAIL" => "ANSWERED",
	//звонящий отменил вызов до истечения таймера ожидания на сервере
	"CANCEL"      => "BREAKED",
	// вызов получил сигнал "занято"
	"BUSY"        => 'BUSY'
];

//получение ключей из бд
$res_services = $db -> getRow("SELECT * FROM {$sqlname}services WHERE folder = 'telfin' and identity = '$identity'");
$api_key      = rij_decrypt($res_services["user_key"], $skey, $ivc);
$api_secret   = rij_decrypt($res_services["user_id"], $skey, $ivc);

if ($tmzone == '') {
	$tmzone = 'Europe/Moscow';
}

date_default_timezone_set($tmzone);

//установим временную зону
$tz         = new DateTimeZone($tmzone);
$dz         = new DateTime();
$dzz        = $tz -> getOffset($dz);
$bdtimezone = $dzz / 3600;

if (abs($bdtimezone) > 12) {

	$tzone      = 0;
	$bdtimezone = $dzz / 3600;

}

$bdtimezone = ($bdtimezone > 0) ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '".$bdtimezone.":00'");

$f = fopen($rootpath."/cash/telfin-worker.log", "a");
fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
fwrite($f, "========================\r\r");
fclose($f);

/*
 * Для определеная ответсвенного по номеру и перевод звонка на него (для входящих звонков)
 *
 * В личном кабинете делается настройки:
1. Интерактивная обработка вызовов - http://lili.100crm.ru/api/telfin/events.php?action=contact
	Позволяет отправить HTTP/HTTPS-запрос с информацией о входящем звонке на удаленный сервер. Используется в реализации Умной маршрутизации вызовов” при интеграции с CRM системами
2.Перевести на номер  из переменной - number (это ответ на contact)
	Обрабатывает информацию, полученную с удаленного сервера, помогает перевести звонок на ответственного сотрудника

[ action ] => contact
[ CalledExtensionID ] => 124583 - Идентификатор добавочного IVR в системе
[ CalledNumber ] => 8123092854 - Номер, который набирала вызывающая сторона (может быть внешним номером: 003258422544, номером IVR в расширенном формате: 0003*001 или коротким номером IVR: 001).
[ EventType ] => call_interactive - Тип события, всегда имеет значение call_interactive
[ CallerIDNum ] => +74953730763 - Номер вызывающего абонента.
[ CalledDID ] => 78123092854 - Внешний вызываемый номер. Присутствует, если доступен.
[ CallFlow ] => IN - Поток вызовов, всегда имеет значение IN
[ CalledExtension ] => 3515*099@sipproxy.telphin.ru - Номер IVR в расширенном формате (например: yyyy*zzz).
[ CallAPIID ] => 3584709739-a312d2e3-979c-4769-b4ba-58170de52c0a - Идентификатор звонка. Автоматически генерируется и сохраняется на протяжении всего звонка вне зависимости от того, переводится ли он.
[ CallerIDName ] => +74953730763 - Имя вызывающего абонента.
[ CallStatus ] => ANSWER - Статус вызова, всегда имеет значение ANSWER
[ CallID ] => a312d2e3979c4769b4ba58170de52c0a - Уникальный идентификатор вызова.
[ identity ] => 10

 */
if ($response['action'] == 'contact') {

	$u         = getxCallerID($response['CallerIDNum']);
	$extension = '';

	//если нашли ответственного, то отдаем его номер
	if ($u['phonein'] > 0) {
		$extension = $u['phonein'];
	}

	//SetVar - первод на ответственного и SetCaller - указание что за компания звонит на софтфоне
	$xml = '<?xml version="1.0" encoding="UTF-8"?><Response><SetVar name="number">'.$extension.'</SetVar><SetCaller name="'.$u['callerID'].'" ></Response>';

	$return = $xml;

}

/*
 Делаються настройки в личном кабинете по добавочному номеру. Закладка "События"
необходимо прописать для всех событий http://lili.100crm.ru/api/telfin/events.php?action=event
При входящем добавление telfin_log со статусом CALLING. Пр разговоре -ANSWER. После завершения разговора данные из telfin_log удаляються
При завершение разговора данные беруься из данных которые приходят за исключением продолжительности и даты начала звонка, uid звонка

 EventType (string) - Тип события. Может принимать значния dial-in, dial-out, hangup или answer в зависимости от типа события.

CallID (string) - Уникальный идентификатор вызова. Не меняется при переадресациях. Можно использовать для идентификации принадлежности различных событий одному вызову.

CallerIDNum (string) - Номер вызывающего абонента

CallerIDName (string) - Имя вызывающего абонента (если есть).

CalledDID (string) - Публичный номер вызываемого абонента (если есть)

CalledExtension (string) - Имя вызываемого добавочного (в виде xxx*yyy@domain)

CalledExtensionID (int) - Идентификатор добавочного CalledExtension. Удобен для последующих вызовов API, ожидающих идентификатор

CallStatus (string) - Статус вызова.
Для event_type 'dial-in' и 'dial-out' :
    CALLING
Для event_type 'answer' :
    ANSWER
Для event_type 'hangup':
    ANSWER вызов был отвечен
    BUSY вызов получил сигнал "занято"
    NOANSWER звонок не отвечен (истек таймер ожидания на сервере)
    CANCEL звонящий отменил вызов до истечения таймера ожидания на сервере
    CONGESTION произошла ошибка во время вызова
    CHANUNAVAIL у вызываемого абонента отсутствует регистрация

CallFlow (string) - Направление вызова:
    in - входящий
    out - исходящий

CallerExtension (string) - добавочный, с которого произведен вызов (в виде xxx*yyy@domain)

CallerExtensionID (int) - Идентификатор добавочного CallerExtension. Удобен для последующих вызовов API, ожидающих идентификатор
CalledNumber (string) - вызываемый номер

RecID (string) - Если на добавочном включена запись разговоров, то тут содержится ее идентификатор. Эквивалентно record_uuid в REST API. По нему можно получить файл записи. Имеет смысл только в событии "hangup"

CallAPIID (string) - Уникальный идентификатор вызова для управления им (например, обрыв, перевод, парковка).

Diversion (string) - номер из одноименного поля протокола SIP при поступлении звонка на АТС (rfc5806). Опционально (присутствуют только при наличии в заголовках SIP). Обычно показывает номер, с которого была сделана переадресация до прихода вызова в АТС.

EventTime (int) - время генерации события: микросекунды c  1 января 1970 года.

Duration (int) - Для события "hangup" содержит время разговора в микросекундах

Transfered (string)- Поле может отсутствовать. При налиичии в событиях CallFlow=out позволяет отличать вызовы, инициированные добавочным, от вызовов, переадресованных с добавочного:
    yes - исходящий вызов произошел в результате переадресации

Bridged (string) - Присутствует в событиях очередей (CallFlow='in') и голосовых меню (IVR):
    yes - вызов был отвечен после попадания в очередь/IVR
    no - вызов не был отвечен после попадания в очередь/IVR

CallBackID (string) -Присутствует при инициации вызова. Может быть полезен для определения принадлежности множества событий к одной инициации вызова.

SubCallID (string) - В отличии от параметра "CallID", одинакового для всего вызова, позволяет выделить в звонке составную часть. Например, если в пределах одного вызова звонок приходил на один добавочный несколько раз (например, несколько раз по кругу, как агент очереди), то этот параметр будет отличаться. Полезен для группировки dial-in, dial-out, answer, hungup составной части вызова.

 */
/* Пример
//входящий звонок на добавочный
 *$response['action'] = 'event';
	$response['CalledExtensionID'] = '124575';
	$response['SubCallID'] = '124575-79a2b6069d3711e7845f79424bc8e8c8';
	$response['EventTime'] = '1505824724746286';
	$response['CalledNumber'] = '3515*101';
	$response['EventType'] = 'dial-in';
	$response['CallerIDName'] = '+78123351127';
	$response['CallerIDNum'] = '+78123351127';
	$response['CallerExtensionID'] = '124583';
	$response['CallerExtension'] = '3515*099@sipproxy.telphin.ru';
	$response['CallFlow'] = 'in';
	$response['CalledExtension'] = '3515*101@sipproxy.telphin.ru';
	$response['CallAPIID'] = '3584709733-76c8491e-9d37-11e7-bd23-79424bc8e8c8';
	$response['CallID'] = '76c8491e9d3711e7bd2379424bc8e8c8';
	$response['CalledDID'] = '78123092854';
	$response['CallStatus'] = 'CALLING';

========================
//ответ и разговор
$response['action'] = 'event';
	$response['CalledExtensionID'] = '124575';
	$response['SubCallID'] = '124575-79a2b6069d3711e7845f79424bc8e8c8';
	$response['EventTime'] = '1505824734866310';
	$response['CalledNumber'] = '3515*101';
	$response['EventType'] = 'answer';
	$response['CallerIDName'] = '+78123351127';
	$response['CallerIDNum'] = '101';
	$response['CallerExtensionID'] = '124583';
	$response['CallerExtension'] = '3515*099@sipproxy.telphin.ru';
	$response['CallFlow'] = 'in';
	$response['CalledExtension'] = '3515*101@sipproxy.telphin.ru';
	$response['CallAPIID'] = '3584709733-76c8491e-9d37-11e7-bd23-79424bc8e8c8';
	$response['CallID'] = '76c8491e9d3711e7bd2379424bc8e8c8';
	$response['CalledDID'] = '78123092854';
	$response['CallStatus'] = 'ANSWER';

//идет исходящий звонок
	$response['SubCallID'] = '124575-6d5229729eae11e7b0329bafbda23c9a';
	$response['EventTime'] = '1505985765358033';
	$response['CalledNumber'] = '+74953730763';
	$response['EventType'] = 'dial-out';
	$response['CallerIDNum'] = '3515*101';
	$response['CallerExtensionID'] = '124575';
	$response['CallerExtension'] = '3515*101@sipproxy.telphin.ru';
	$response['CallFlow'] = 'out';
	$response['CallAPIID'] = '3584709739-6d522972-9eae-11e7-b032-9bafbda23c9a';
	$response['CallerIDName'] = 'Иванов Иван Иванович';
	$response['CallID'] = '6d5229729eae11e7b0329bafbda23c9a';
	$response['CallStatus'] = 'CALLING';
========================

при входящем окончание звонка
 * $response['CalledExtensionID'] = '124575';
$response['SubCallID'] = '124575-79a2b6069d3711e7845f79424bc8e8c8';
$response['EventTime'] = '1505824818346287';
$response['CalledNumber'] = '3515*101';
$response['EventType'] = 'hangup';
$response['CallerIDName'] = '+78123351127';
$response['CallerIDNum'] = '+78123351127';
$response['CallerExtensionID'] = '124583';
$response['CallerExtension'] = '3515*099@sipproxy.telphin.ru';
$response['CallFlow'] = 'in';
$response['CalledExtension'] = '3515*101@sipproxy.telphin.ru';
$response['CallAPIID'] = '3584709733-76c8491e-9d37-11e7-bd23-79424bc8e8c8';
$response['CallID'] = '76c8491e9d3711e7bd2379424bc8e8c8';
$response['Duration'] = '83479977';
$response['CalledDID'] = '78123092854';
$response['CallStatus'] = 'ANSWER';

при исходящем окончание звонка
$response['SubCallID'] = '124575-f7cbc78aa41111e79a952b3394a79396';
$response['EventTime'] = '1506578305340989';
$response['CalledNumber'] = '89523326838';
$response['EventType'] = 'hangup';
$response['CallerIDName'] = 'Иванов Иван Иванович';
$response['CallerIDNum'] = '3515*101';
$response['CallerExtensionID'] = '124575';
$response['CallerExtension'] = '3515*101@sipproxy.telphin.ru';
$response['CallFlow'] = 'out';
$response['CallAPIID'] = 'cb-3584709740-ed7007fbd6404b9691150d649e0c253d';
$response['CallID'] = 'ed7007fbd6404b9691150d649e0c253d';
$response['Duration'] = '6400005';
$response['CallStatus'] = 'ANSWER';
$response['CallBackID'] = 'ed7007fbd6404b9691150d649e0c253d';
$response['RecID'] = '124575-f7cbc78aa41111e79a952b3394a79396';

 */
if ($response['action'] == 'event') {

	//окончание разговора
	if ($response['EventType'] == 'hangup') {

		$link = '';

		$callid = $response['CallID'];//для идентификации

		//запрос на получение токина
		$token = doMethod('token', [
			"api_key"    => $api_key,
			"api_secret" => $api_secret
		]);

		//получене данных по звонку. Необходимо для получения продолжительности и даты начала звонка, uid звонка
		$dan = [
			"api_key" => $api_key,
			"token"   => $token['access_token'],
			"callid"  => $callid
		];
		$result = doMethod('history', $dan);

		//вычисляем uid звонка приходит в cdr для того что бы получить ссылку на файл с разговорм. Берем последее событие
		$spisoc      = $result['cdr'];
		$spisocCount = count($spisoc);
		$record_uuid = $spisoc[ $spisocCount - 1 ]['record_uuid'];

		//если ссылка на историю звонка не пустая
		if ($record_uuid != '') {

			$dan_record = [
				"api_key" => $api_key,
				"token"   => $token['access_token'],
				"record"  => $record_uuid
			];

			$link = doMethod('record', $dan_record);
			$link = $link['record_url'];

		}

		$status = $response['CallStatus'];///статус
		$type   = $response['CallFlow'];//тип

		$duration = $result['bridged_duration'];//продолжительность разговора приходит в секундах
		$datum    = $result['init_time_gmt'];//дата начала звонка

		//дата из телефонии с учетом тайм зоны
		/*$datUTC  = explode(" ", $datum);
		$dataUTC = explode("-", $datUTC[0]);
		$timUTC  = explode(":", $datUTC[1]);
		$datum   = date("Y-m-d H:i:s", mktime($timUTC[0] + $clientOffset, $timUTC[1], $timUTC[2], $dataUTC[1], $dataUTC[2], $dataUTC[0]));*/

		$datum = modifyDatetime( $datum, [
			"format" => 'Y-m-d H:i:s',
			"hours"  => $clientOffset
		] );

		if ($type == 'in') {

			$phone_from = prepareMobPhone($response['CallerIDNum']);//От
			$phone_to   = prepareMobPhone($response['CalledDID']);//кому
			$extension  = $response['CalledNumber'];//приходит с префиксом   "3515*101", нам надо только 101
			$extension  = explode("*", $extension);
			$extension  = $extension[1];

			$dst   = $phone_from;
			$src   = $extension;
			$did   = $phone_to;
			$phone = $phone_from;

		}

		elseif ($type == 'out') {

			$phone_to = prepareMobPhone($response['CalledNumber']);//кому

			$extension = $response['CallerIDNum'];//приходит с префиксом   "3515*101", нам надо только 101
			$extension = explode("*", $extension);
			$extension = $extension[1];

			$dst   = $extension;
			$src   = $phone_to;
			$did   = '';
			$phone = $phone_to;

		}

		$u = [];
		if ($phone != '') {
			$u = getxCallerID( $phone );
		}

		$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}telfin_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'") + 0;

		// удаляем только при In поскольку необходимо чтобы статус о завершение исходящего звонка отвеченного отобразилось
		// "Звонок завершен" удаляем есть при Out только при другом условии
		if ($id > 0 && $type == 'in') {
			$db -> query( "DELETE FROM  {$sqlname}telfin_log WHERE id = '$id'" );
		}

		// исходящий был отвечен и прошел разговор обновляем статус в telfin_log.
		// Это необходимо чтобы правильно отображался статус в окне CRM "Звонок на номер"
		if ($id > 0 && $type == 'out' && $status == 'ANSWER') {
			$db -> query( "UPDATE {$sqlname}telfin_log SET status = 'END' WHERE id = '$id'" );
		}

		// исходящий не был принят обновляем статус в telfin_log.
		// Это необходимо чтобы правильно отображался статус в окне CRM "Звонок на номер"
		if ($id > 0 && $type == 'out' && $status != 'ANSWER') {
			$db -> query( "UPDATE {$sqlname}telfin_log SET status = '$status' WHERE id = '$id'" );
		}


		$iduser = (int)$db -> getOne("SELECT iduser FROM {$sqlname}user WHERE phone_in = '$extension' and identity = '$identity'");

		//добавляем запись в историю звонков
		$call = [
			"res"    => strtr($status, $status_hist),
			"src"    => $src,
			"dst"    => $dst,
			"did"    => $did,
			"phone"  => $phone,
			"iduser" => $iduser,
			"direct" => ($type == 'in') ? 'income' : 'outcome',
			"clid"   => (int)$u['clid'],
			"pid"    => (int)$u['pid'],
			"file"   => $link,
			"sec"    => (int)$duration,
			"uid"    => $callid,
			"datum"  => $datum
		];

		$cid = (int)$db -> getOne("select id from {$sqlname}callhistory where uid = '$callid' and identity = '$identity'") + 0;

		if ($cid == 0) {
			$db -> query( "INSERT INTO {$sqlname}callhistory SET ?u", $call );
		}

	}

	//идет дозвон или разговор
	else {

		$type   = $response['CallFlow'];//тип звонка (in/out)
		$status = $response['CallStatus'];///статус
		$callid = ($response['CallID'] != '') ? $response['CallID'] : "0";//для идентификации

		if ($type == 'in') {

			$phone     = $response['CallerIDName'];//Номер вызывающего абонента (кто нам звонит) ЭТО ЖОЖЕТ БЫТЬ НЕ ПРАВИЛЬНО ПОСКОЛЬКО НАМЕ
			$extension = $response['CalledNumber'];//внутренний номер пользователя облачной АТС

		}
		elseif ($type == 'out') {

			$phone     = $response['CalledNumber'];//вызываемый номер (Номер кому звоним
			$extension = $response['CallerIDNum'];//внутренний номер пользователя облачной АТС(приходит в формате 3515*101 переделываю в 101)

		}

		//общее для in и out приходит в формате 3515*101 переделываю в 101
		$extension = explode("*", $extension);
		$extension = $extension[1];

		$iduser = (int)$db -> getOne("SELECT iduser FROM {$sqlname}user WHERE phone_in = '$extension' and identity = '$identity'");

		$u = [];
		if ($phone != '') {
			$u = getxCallerID( $phone );
		}


		//Идентификатор записи буфера для текущего пользователя
		$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}telfin_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'") + 0;

		if ($id == 0) {

			//если запись не найдена, то создаем её
			$db -> query("INSERT INTO  {$sqlname}telfin_log SET ?u", [
				'datum'     => current_datumtime(),
				'callid'    => $callid,
				'extension' => $extension,
				'phone'     => preparePhone($phone),
				'status'    => $status,
				'type'      => $type,
				'clid'      => (int)$u['clid'],
				'pid'       => (int)$u['pid'],
				'identity'  => $identity
			]);
			$id = $db -> insertId();

		}
		else {

			$db -> query("UPDATE {$sqlname}telfin_log SET ?u WHERE id = '$id'", [
				'datum'  => current_datumtime(),
				'callid' => $callid,
				'phone'  => preparePhone($phone),
				'status' => $status,
				'clid'   => (int)$u['clid'],
				'pid'    => (int)$u['pid']
			]);

		}

	}

}


toexit:

print $return;
