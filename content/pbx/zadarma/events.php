<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2018.x          */
/* ============================ */

/**
 * скрипт получает уведомления из сервиса Zadarma для входящего звонка
 */

//Для того, чтобы система приняла ссылку, необходимо добавить проверочный код вначале скрипта.
if (isset($_GET['zd_echo'])) {
	exit( $_GET['zd_echo'] );
}

error_reporting(E_ERROR);

header('Access-Control-Allow-Origin: 185.45.152.42');

$response = $_REQUEST;

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$ypath = $rootpath."/content/pbx/zadarma/";

//Найдем identity по настройкам
$res      = $db -> getRow("SELECT id, timezone FROM {$sqlname}settings WHERE api_key = '$_GET[crmkey]'");
$tmzone   = $res['timezone'];
$identity = (int)$res['id'];

if ($identity == 0) {

	$f = fopen($rootpath."/cash/zadarma-worker.log", "a");
	fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
	fwrite($f, "Ошибка: Не верный параметр crmkey\r");
	fwrite($f, "========================\r\r");
	fclose($f);

	exit();

}

require_once dirname( __DIR__)."/zadarma/sipparams.php";
require_once dirname( __DIR__)."/zadarma/user-api-v1-master/lib/Client.php";

/**
 * Отладочные данные
 */
/*
$response = array(
	'event' => 'NOTIFY_START',
	'caller_id' => '+79223289466',
	'called_did' => '74957773679',
	'call_start' => '2018-10-11 15:08:03'
);
$signatureTest = base64_encode(hash_hmac('sha1', $response['caller_id'].$response['called_did'].$response['call_start'], $api_secret));
*/


/**
 * @param $name
 * @return null
 */
function getHeader($name) {

	$headers = getallheaders();

	foreach ($headers as $key => $val) {
		if ($key == $name) {
			return $val;
		}
	}

	return null;

}

//для добавления в таблицу callhistory статусов из zadarma (такой же список есть в cdr.php)
$status_end = [
	"answered"           => "ANSWERED",
	"busy"               => "BUSY",
	"cancel"             => "BREAKED",
	"no answer"          => "NO ANSWER",
	"failed"             => "FAILED",
	"no money"           => "FAILED",
	"unallocated number" => 'FAILED',
	"no limit"           => "FAILED",
	"no day limit"       => "FAILED",
	"line limit"         => "FAILED",
	"no money, no limit" => "FAILED"
];

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

$f = fopen($rootpath."/cash/zadarma-worker.log", "a");
fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
fwrite($f, "========================\r\r");
fclose($f);

/*
NOTIFY_START     => начало входящего звонка в АТС
NOTIFY_INTERNAL  => начало входящего звонка на внутренний номер АТС
NOTIFY_ANSWER    => ответ при звонке на внутренний или на внешний номер
NOTIFY_END       => конец входящего звонка на внутренний номер АТС
NOTIFY_OUT_START => начало исходящего звонка с АТС
NOTIFY_OUT_END   => конец исходящего звонка с АТС
NOTIFY_RECORD    => запись звонка готова для скачивания
*/

//Начало входящего звонка в АТС. Умная переадресация
/*
Параметры, которые отправляются на ссылку для уведомлений:
event       – событие (NOTIFY_START)
callstart   – время начала звонка;
pbx_call_id – id звонка;
caller_id   – номер звонящего;
called_did  – номер, на который позвонили.
 */
if ($response['event'] == 'NOTIFY_START') {

	$phone_from = $response['caller_id'];
	$phone_to   = $response['called_did'];
	$time_start = $response['call_start'];

	// Подпись отправляется, только если у вас есть ключ API и секрет
	$signature = getHeader('Signature');

	//Составление проверочной подписи для уведомления о входящих звонках
	$signatureTest = base64_encode(hash_hmac('sha1', $phone_from.$phone_to.$time_start, $api_secret));

	file_put_contents($rootpath."/cash/zadarma.log", "$signature - $signatureTest - $api_secret\n");

	if ($signature == $signatureTest) {

		$u         = getxCallerID($phone_from, false, true);
		$extension = 0;

		//если есть ответственный
		if ( $u['phonein'] != '' ) {

			$extension = $u['phonein'];

			//отправляем на ответственного сотрудника
			print json_encode([
				'redirect'    => $extension,
				'caller_name' => $u['callerID']
			]);

			exit();

		}

		if ( $u['callerID'] != '' ) {

			//отправляем на ответственного сотрудника
			print json_encode([
				'redirect'    => '',
				'caller_name' => $u['callerID']
			]);

			exit();

		}

	}

}

//Входящий, Ответ, Исходящий
/*
 * Общие параметры, которые отправляются на ссылку для уведомлений:
 *   pbx_call_id – id звонка;
 *   internal – (опциональный) внутренний номер.
     caller_id – номер звонящего;
 *
 *	event – событие (NOTIFY_INTERNAL) - начало входящего звонка на внутренний номер АТС.
		отличается параметр:
	    callstart – время начала звонка;
	    called_did – номер, на который позвонили;
	    сама установила он не приходит 'status'    => 'INCOMING'

	event – событие (NOTIFY_ANSWER)- ответ при звонке на внутренний или на внешний номер.
		отличается параметр:
		destination – номер, на который позвонили;
	    call_start – время начала звонка;
	    сама установила он не приходит 'status'    => 'ACCEPTED'
 */
elseif (in_array($response['event'], [
	'NOTIFY_INTERNAL',
	'NOTIFY_ANSWER',
	'NOTIFY_OUT_START'
])) {

	$call_id = $response['pbx_call_id'];
	$type = (stripos($call_id, 'in_') !== false) ? 'in' : 'out';

	$phone_from = $response['caller_id'];

	//поскольку во время входящего и разгова приходят разные наименование один и тех же переменных
	if ($response['event'] == 'NOTIFY_INTERNAL') {

		$phone  = $response['internal'];//$response['called_did'];
		$status = 'INCOMING';

	}
	elseif ($response['event'] == 'NOTIFY_ANSWER') {

		$phone  = ($type == 'in') ? $response['caller_id'] : $response['destination'];
		$status = 'ANSWERED';

	}
	elseif ($response['event'] == 'NOTIFY_OUT_START') {

		$phone      = $response['destination'];
		$status     = 'CALLING';
		$phone_from = $response['internal'];

	}

	if (strlen($response['internal']) < 6 && $type == 'in') {

		$extension = $response['internal'];
		$phone     = $response['caller_id'];//$response['destination'];

	}
	elseif (strlen($response['internal']) < 6 && $type == 'out') {

		$extension = $response['caller_id'];
		$phone     = $response['destination'];

	}
	else {

		$extension = $response['destination'];
		$phone     = $response['internal'];

	}


	$iduser = (int)$db -> getOne("SELECT iduser FROM {$sqlname}user WHERE phone_in = '$extension' and identity = '$identity'");

	$u = [];
	if ($phone != '') {
		$u = getxCallerID( $phone );
	}

	if($extension == '') {
		goto ext;
	}

	//Идентификатор записи буфера для текущего пользователя
	$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}zadarma_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'");

	if ($id == 0) {

		//если запись не найдена, то создаем её
		$db -> query("INSERT INTO  {$sqlname}zadarma_log SET ?u", [
			'datum'     => current_datumtime(),
			'callid'    => $call_id,
			'extension' => $extension,
			'phone'     => preparePhone($phone),
			'status'    => strtoupper($status),
			//'content'   => json_encode_cyr($response),
			'type'      => $type,
			'clid'      => (int)$u['clid'],
			'pid'       => (int)$u['pid'],
			'identity'  => $identity
		]);
		$id = $db -> insertId();

	}
	else {

		$db -> query("UPDATE  {$sqlname}zadarma_log SET ?u WHERE id = '$id'", [
			'datum'  => current_datumtime(),
			'callid' => $call_id,
			'phone'  => preparePhone($phone),
			'status' => strtoupper($status),
			//'content' => json_encode($response),
			'clid'   => (int)$u['clid'],
			'pid'    => (int)$u['pid']
		]);

	}

}

//Завершение соединения
/*
Общие параметры, которые отправляются на ссылку для уведомлений:
callstart – время начала звонка;
pbx_call_id – id звонка;
caller_id – номер звонящего;
internal – (опциональный) внутренний номер;
duration – длительность в секундах;
disposition – состояние звонка:
	'answered' – разговор,
	'busy' – занято,
	'cancel' - отменен,
	'no answer' - без ответа,
	'failed' - не удался,
	'no money' - нет средств, превышен лимит,
	'unallocated number' - номер не существует,
	'no limit' - превышен лимит,
	'no day limit' - превышен дневной лимит,
	'line limit' - превышен лимит линий,
	'no money, no limit' - превышен лимит;
status_code – код статуса звонка Q.931;
is_recorded – 1 - есть запись звонка, 0 - нет записи;
call_id_with_rec – id звонка с записью (рекомендуем загружать файл записи не ранее чем через 40 секунд после уведомления т.к. для сохранения файла записи нужно время).


event – событие (NOTIFY_END) - конец входящего звонка на внутренний номер АТС.
отличается параметр:
called_did – номер, на который позвонили;
сама установила он не приходит type    => in


event – событие (NOTIFY_OUT_END) - конец исходящего звонка с АТС.
отличается параметр:
destination – номер, на который позвонили;
 сама установила он не приходит type    => out

 */
elseif (in_array($response['event'], [
	'NOTIFY_END',
	'NOTIFY_OUT_END'
])) {

	$call_id   = $response['pbx_call_id'];
	$extension = $response['internal'];
	$status    = 'ENDING';//$response['disposition'];
	$duration  = $response['duration'];

	//если это входящий звонок и нам нужен number

	if ($response['event'] == 'NOTIFY_END') {

		$dst   = $response['caller_id'];
		$src   = $response['internal'];
		$did   = $response['called_did'];
		$phone = $response['caller_id'];

	}
	else {

		if (strlen($response['internal']) < 6) {

			$phone = $response['destination'];

		}
		else {

			$phone = $response['internal'];
			$extension = $response['destination'];

		}

	}

	$type = (stripos($call_id, 'in_') !== false) ? 'in' : 'out';

	$u = [];
	if ($phone != '') {
		$u = getxCallerID( $phone );
	}

	if($extension == '') {
		goto ext;
	}

	//обновляем данные о звонке в таблице zadarma
	$id = $db -> getOne("SELECT id FROM  {$sqlname}zadarma_log WHERE type = '$type' and extension = '$extension' and identity = '$identity'");

	if ($id == 0) {

		$db -> query("INSERT INTO {$sqlname}zadarma_log SET ?u", [
			'datum'     => current_datumtime(),
			'callid'    => $call_id,
			'extension' => $extension,
			'phone'     => $phone,
			'status'    => strtoupper($status),
			'type'      => $type,
			"clid"      => (int)$u['clid'],
			"pid"       => (int)$u['pid'],
			'identity'  => $identity
		]);
		$id = $db -> insertId();

	}
	else {

		$db -> query("UPDATE {$sqlname}zadarma_log SET ?u WHERE id = '$id'", [
			'datum'    => current_datumtime(),
			'callid'   => $call_id,
			'phone'    => $phone,
			'status'   => strtoupper($status),
			//'content'   => json_encode_cyr($response),
			'type'     => $type,
			'clid'     => (int)$u['clid'],
			'pid'      => (int)$u['pid'],
			'identity' => $identity
		]);

	}

	/*

	//добавляем запись в историю звонков
	$call = array(
		"res"    => strtr($status, $status_end),
		"src"    => $src,
		"dst"    => $dst,
		"did"    => $did,
		"phone"  => $phone,
		"iduser" => $iduser,
		"direct" => ($type == 'in') ? 'income' : 'outcome',
		"clid"   => ($u['clid'] != '') ? $u['clid'] : '',
		"pid"    => ($u['pid'] != '') ? $u['pid'] : '',
	);

	$cid = $db -> getOne("SELECT id FROM  {$sqlname}callhistory WHERE uid = '$call_id' and identity = '$identity'");

	if ($cid == 0) {

		$call['datum'] = current_datumtime();
		$call['uid']   = $call_id;
		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);

	}
	elseif ($cid > 0 && $extension != '') {

		unset($call['uid']);
		unset($call['phone']);
		unset($call['clid']);
		unset($call['pid']);
		unset($call['src']);
		unset($call['dst']);
		//$call['file'] = $response['link'];
		$call['sec'] = $duration;
		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $call);

	}

	*/

}

ext:

print json_encode_cyr($return);