<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/*
 * запускается из js/workers/zadarma.js
 * мониторинг звонков в истории звонков
 */

use PHPMailer\PHPMailer\Exception;

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 ).'/';

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];

// форсированный режим запроса cdr
$isforce = (int)$_REQUEST['force'] == 1;

//Добавлять запись в историю
$putInHistory = false;

//параметры подключения к серверу
require_once dirname( __DIR__)."/zadarma/sipparams.php";
require_once dirname( __DIR__)."/zadarma/mfunc.php";

if ($identity == '') {
	$identity = $db -> getOne("SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'");
}

if ((int)$identity == 0) {

	$return = ["error" => "Не верный ключ CRM API"];
	goto toexit;

}

$hours = pre_format($_REQUEST['hours']);//преобразуем в число

/**
 * Дата со смещением
 *
 * @param     $string
 * @param int $tzz
 * @return string
 * @throws Exception
 */
function UTCtoDateTimeSelf($string, int $tzz = 0): string {

	//$dm = getDateTimeArray($string);

	//тут корректируем смещение часового пояса
	$d = getServerTimeOffset();
	/*
	[offset] => 0 -- разница м/у настройками временной зоны в CRM и на сервере
	[serverTimeZone] => Asia/Yekaterinburg -- часовая зона сервера
	[serverOffset] => 5 -- смещение часовой зоны сервера от +0
	[clientTimeZone] => Asia/Yekaterinburg  -- часовая зона, настроенная в CRM
	[clientOffset] => 5 -- смещение часовой зоны, настроенной в CRM от +0
	*/

	//если время приходит в правильном UTC формате, т.е. +0, то
	$offset = $d['clientOffset'] - $tzz;

	//return date("Y-m-d H:i:s", mktime( $dm['H'] + $offset, $dm['i'], $dm['s'], $dm['m'], $dm['d'], $dm['Y']));

	return  modifyDatetime( $string, [
		"format" => 'Y-m-d H:i:s',
		"hours"  => $offset
	] );

}

//для добавления в таблицу callhistory статусов из zadarma (такой же список есть в events.php)
$status_end = [
	"answered"           => "ANSWERED",
	"busy"               => "BUSY",
	"cancel"             => "BREAKED",
	"no answer"          => "NO ANSWER",
	"failed"             => "FAILED",
	"call failed"        => "FAILED",
	"no money"           => "CONGESTION",
	"unallocated number" => 'BREAKED',
	"no limit"           => "BREAKED",
	"no day limit"       => "BREAKED",
	"line limit"         => "BREAKED",
	"no money, no limit" => "BREAKED"
];

$list = $return = [];

//посмотреим дату последнего звонка из истории звонков
$last_datum = $db -> getOne("SELECT MAX(datum) FROM {$sqlname}callhistory WHERE identity = '$identity'");

//если проверок не было (на старте) или была больше 30 дней назад
if ($last_datum == '' || diffDate2($last_datum) > 30) {

	//если часы не указаны, то берем за месяц
	if (!$hours) {
		$hours = 24 * 30;
	}//берем статистику за месяц

	$delta     = $hours * 3600;//период времени, за который делаем запрос в часах
	$zone      = $GLOBALS['tzone'];//смещение временной зоны сервера
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);
	$dateStart = modifyDatetime( current_datumtime(), [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -$hours
	] );

}
else {
	//UTCtoDateTimeSelf($last_datum, "-10");
	$dateStart = $last_datum;
}

//проверяем не чаще, чем раз в 5 минут
//иначе, при большом количестве пользователей
//резко возрастает нагрузка на сервер
if(diffDateTimeSeq($dateStart) < 300 && !$isforce) {

	$return = ["result" => "Данные обновлены менее 5 минут назад"];
	goto toexit;

}

//print $dateStart."\n";
//print diffDateTimeSeq($dateStart);

//$dateEnd = date('Y-m-d H:i:s', mktime(date('H') + 10, date('i'), date('s'), date('m'), date('d'), date('Y')));
$dateEnd = modifyDatetime(NULL, ["hours" => 10]);

//Делаем запрос на подготовку статистики и получаем
$data = [
	"api_key"    => $api_key,
	"api_secret" => $api_secret,
	"dstart"     => UTCtoDateTimeSelf($dateStart, "10"),//$dateStart,
	"dend"       => $dateEnd
];

//print_r($data);

//Часовой пояс на АТС
$result = doMethod('tzone', $data);
$ztimezone = str_replace("UTC", "", $result -> timezone);

$result = doMethod('history', $data);
$calls  = $result -> stats;

$bdtimezone = ($ztimezone > 0) ? "+".abs($ztimezone) : "-".abs($ztimezone);

if($ztimezone != '') {
	$db -> query( "SET time_zone = '$bdtimezone:00'" );
}

//print_r($result);
//print_r($calls);
//print array2string($calls, "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;");
//exit();

//$result2 = doMethod('statistic', $data);
//$calls2  = $result2 -> stats;

foreach ($calls as $call) {

	/*
    start – дата начала отображения статистики;
    end – дата окончания отображения статистики;
    version - формат вывода статистики (2 - новый, 1 - старый);
    call_id – уникальный id звонка, этот id указан в названии файла с записью разговора (уникален для каждой записи в статистике);
    sip – SIP-номер;
    callstart – время начала звонка;
    clid – CallerID;
    destination – куда звонили;
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
    seconds – количество секунд звонка;
    is_recorded – (true, false) записан или нет разговор;
    pbx_call_id – постоянный ID внешнего звонка в АТС (не меняется при прохождении сценариев, голосового меню, transfer и т.д., отображается в статистике и уведомлениях);

	*/

	$callid     = $call -> call_id;
	$datum      = $call -> callstart;//дата начала вызова
	$status     = $call -> disposition;//статус звонка
	$duration   = $call -> seconds;//время разговора в секундах
	$phone_from = prepareMobPhone($call -> sip);//От
	$phone_to   = prepareMobPhone($call -> destination);//кому
	$extension  = prepareMobPhone($call -> sip);
	$fileid     = $call -> pbx_call_id;//id файла записи
	$isRecorded = $call -> is_recorded;//записан ли разговор

	$datum = UTCtoDateTimeSelf($datum, $ztimezone);

	//поскольку по исходящим звонка приходят две строки уберем ненужную строку(потому что сперва идет надор sip номера, а затем уже вывывается клиент)
	if ($phone_from != $phone_to) {

		$iduser = (int)$db -> getOne("SELECT iduser FROM  {$sqlname}user WHERE phone_in = '$extension' and identity = '$identity'");

		if (stripos($fileid, 'in_') !== false) {

			preg_match('#\<(.+?)\>#is', $call -> clid, $arr);
			$from = $arr[1];

			$src    = prepareMobPhone($from);
			$dst    = $extension;
			$did    = $phone_to;
			$phone  = preparePhone($from);
			$direct = "income";

		}
		elseif (stripos($fileid, 'out_') !== false) {

			$src    = $extension;
			$dst    = $phone_to;
			$did    = '';
			$phone  = $phone_to;
			$direct = "outcome";

		}

		if(strlen($src) == strlen($dst)) {
			$direct = 'inner';
		}

		$u = getxCallerID($phone );

		$list[] = [
			"uid"      => $callid,
			"datum"    => $datum,
			//"odatum"    => UTCtoDateTimeSelf($datum, $ztimezone),
			"res"      => strtr($status, $status_end),
			"sec"      => $duration,
			"file"     => ($isRecorded == "true") ? $fileid : '',
			"src"      => $src,
			"dst"      => $dst,
			"did"      => $did,
			"phone"    => $phone,
			"iduser"   => $iduser,
			"direct"   => $direct,
			"clid"     => (int)$u['clid'],
			"pid"      => (int)$u['pid'],
			"identity" => $identity
		];

	}

}

//print_r($list);
//print array2string($list, "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;");
//exit();

$upd = $new = 0;

//обрабатываем запрос
foreach ($list as $call) {

	//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
	$id = (int)$db -> getOne("SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND phone = '$call[phone]' AND direct = '$call[direct]' AND identity = '$identity'");

	if ($id == 0) {

		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", arrayNullClean($call));
		$new++;

	}
	else {

		//unset($call['uid']);
		unset($call['callid']);

		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$id'", arrayNullClean($call));
		$upd++;

	}

}

//if ($_REQUEST['printres'] == 'yes')
$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;

$return = ["result" => $rez];


toexit:

print json_encode_cyr($return);

exit();