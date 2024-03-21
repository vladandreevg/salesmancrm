<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2024.x          */
/* ============================ */

set_time_limit(0);

error_reporting(E_ERROR);

ini_set('display_errors', 1);
ini_set('memory_limit', '512M');

$rootpath = dirname(__DIR__, 3);
$thisfile = basename(__FILE__);
$ypath    = __DIR__;

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/settings.php";

$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];
$printres = $_REQUEST['printres'];

// форсированный режим запроса cdr
$isforce = (int)$_REQUEST['force'] == 1;

if ($identity == '') {
	$identity = $db -> getOne("SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'");
}

if ((int)$identity == 0) {

	$return = ["error" => "Не верный ключ CRM API"];
	print json_encode_cyr($return);
	exit();

}

require_once $rootpath."/inc/func.php";

// если скрипт запускается из консоли, то переменные считываем из аргументов
if (PHP_SAPI == 'cli') {

	$req = parse_argv($argv);
	foreach ($req as $r => $v) {
		$$r = $v;
	}

	//print_r($req);

	$isforce = (int)$force == 1;

	if ($identity == '') {
		$identity = $db -> getOne("SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'");
	}

	if ((int)$identity == 0) {

		$return = ["error" => "Не верный ключ CRM API"];
		print json_encode_cyr($return);
		exit();

	}

}

//Добавлять запись в историю
$putInHistory = false;

// отсекаем запуск процессов-дублей
$logfile  = $rootpath."/cash/pbx.log";
$isActive = false;
$lastTime = current_datumtime(1);
if (!$isforce && file_exists($logfile)) {

	$isActive = file_get_contents($logfile) == "1";
	$lastTime = unix_to_datetime(fileatime($logfile));

	if ($isActive || diffDateTimeSeq($lastTime) < 300) {

		$return = [
			"result"  => "Запрос уже активен",
			"request" => $_REQUEST,
			"get"     => $_GET,
			"isforce" => $isforce,
			"argv"    => $argv
		];
		print json_encode_cyr($return);
		exit();

	}

}

//параметры подключения к серверу
include $ypath."/sipparams.php";
include $ypath."/mfunc.php";

$pbxurl  = $sip['host'];
$pbxuser = $sip['user'].":".$sip['secret'];

//$hours = 24;
$list = $return = [];

//массив внутренних номеров сотрудников
$users = [];

$r = $db -> getAll("SELECT iduser, phone, phone_in, mob FROM {$sqlname}user WHERE identity = '$identity'");
foreach ($r as $da) {

	if ($da['phone'] != '') {
		$users[prepareMobPhone($da['phone'])] = $da['iduser'];
	}
	if ($da['phone_in'] != '') {
		$users[prepareMobPhone($da['phone_in'])] = $da['iduser'];
	}
	if ($da['mob'] != '') {
		$users[prepareMobPhone($da['mob'])] = $da['iduser'];
	}

}

//посмотреим дату последнего звонка из истории звонков
if ($last_datum == '') {
	$last_datum = $db -> getOne("SELECT MAX(datum) FROM {$sqlname}callhistory WHERE identity = '$identity'");
}

//если проверок не было (на старте) или была больше 30 дней назад
//то берем за месяц
if ($hours > 0 || $last_datum == '' || (int)diffDate2($last_datum) > 30) {

	//берем статистику за месяц
	if (!$hours) {
		$hours = 24 * 30;
	}

	$delta = $hours * 3600;    //период времени, за который делаем запрос в часах
	$zone  = $GLOBALS['tzone'];//смещение временной зоны сервера

	//$dateStart = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) - $delta );
	$dateStart = modifyDatetime(current_datumtime(), [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -$hours
	]);

}
else {
	$dateStart = $last_datum;
}

//проверяем не чаще, чем раз в 5 минут
//иначе, при большом количестве пользователей
//резко возрастает нагрузка на сервер
if (!$isforce && diffDateTimeSeq($dateStart) < 300) {

	$return = ["result" => "Данные обновлены менее 5 минут назад"];
	print json_encode_cyr($return);
	exit();

}

// пометим процесс активным
file_put_contents($logfile, "1");

//$dateEnd = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) );
$dateEnd = modifyDatetime(NULL, ["minutes" => 5]);

//Делаем запрос на подготовку статистики и получаем key этого запроса
$data = [
	"from_date" => modifyDatetime($dateStart, ["format" => "Y-m-d H:i:s"]),
	"to_date"   => modifyDatetime($dateEnd, ["format" => "Y-m-d H:i:s"])
];

$result = getCallHistoryExtra($data);

file_put_contents($rootpath."/cash/callhistory.json", json_encode_cyr($result));
//exit();

if ($result['resp_status'] == 'ok') {

	$list = [];

	// массив сотрудников (будем отсекать лишние звонки)
	$extentions = $db -> getIndCol("phone_in", "SELECT phone_in, iduser FROM {$sqlname}user WHERE COALESCE(phone_in, '') != '' AND identity = '$identity'");

	foreach ($result['calls'] as $call) {

		$direction = 'income';
		$phone     = $call[2];
		$extention = $call[3];
		$iduser    = $extentions[$extention];
		$rezult    = 'ANSWERED';

		$direction = $call[11] == 'inbound' ? "income" : "outcome";

		// если ни звонящий, ни абонент не являются сотрудниками црм, то выходим
		if (!array_key_exists($call[2], $extentions) && !array_key_exists($call[3], $extentions)) {
			continue;
		}

		// если звонящий и абонент не являются сотрудниками црм, то это внутренний вызов. выходим
		if (array_key_exists($call[2], $extentions) && array_key_exists($call[3], $extentions)) {
			//continue;
		}

		if (array_key_exists($call[2], $extentions)) {
			$direction = 'outcome';
			$phone     = $call[3];
			$extention = $call[2];
			$iduser    = $extentions[$extention];
		}

		if (strlen($call[2]) < 11 && strlen($call[2]) == strlen($call[3])) {
			$direction = 'inner';
		}

		$u = getxCallerID($phone);

		if ((int)$call[7] == 0) {
			$rezult = 'NOANSWER';
		}

		$list[] = [
			"uid"      => !empty($call[1]) ? $call[1] : 0,
			"datum"    => $call[0],
			"res"      => $rezult,
			"sec"      => (int)$call[7],
			"file"     => $call[8] ?? NULL,
			"src"      => $call[2],
			"dst"      => $call[3],
			"did"      => $call[4],
			"phone"    => $phone,
			"iduser"   => (int)$iduser,
			"direct"   => $direction,
			"clid"     => (int)$u['clid'],
			"pid"      => (int)$u['pid'],
			"identity" => $identity,
		];

	}

	$xdata['data']['from_date'] = $data['from_date'];
	$xdata['data']['to_date']   = $data['to_date'];
	$xdata['list']              = $list;

	// file_put_contents($rootpath."/cash/callhistory-data.json", json_encode_cyr($xdata));

	$upd = $new = 0;

	//обрабатываем запрос
	foreach ($list as $call) {

		//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
		$id = (int)$db -> getOne("SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND identity = '$identity'");

		if ($id == 0) {

			$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);
			$new++;

		}
		else {

			$zdata = $call;

			unset($zdata['uid'], $zdata['callid']);

			$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$id'", $zdata);
			$upd++;

		}

		//добавим запись в историю активностей
		if (( (int)$call['clid'] > 0 || (int)$call['pid'] > 0 ) && $call['direct'] != 'inner' && $putInHistory) {

			//проверим, были ли активности по абоненту
			$all = (int)$db -> getOne("SELECT COUNT(*) AS count FROM {$sqlname}history WHERE (clid = '$call[clid]' OR pid = '$call[pid]') AND uid = '$call[uid]' AND identity = '$identity'");

			if ($all == 0) {

				if ($call['direct'] == 'outcome') {

					$tip = 'исх.1.Звонок';
					$r   = 'Исходящий успешный звонок';

				}
				elseif ($call['direct'] == 'income') {

					$tip = 'вх.Звонок';
					$r   = 'Принятый входящий звонок';

				}

				//$tip = 'Запись разговора';

				//добавим запись в историю активности по абоненту
				$db -> query("INSERT INTO {$sqlname}history SET ?u", [
					"iduser"   => (int)$call['iduser'],
					"clid"     => (int)$call['clid'],
					"pid"      => (int)$call['pid'],
					"datum"    => $call['datum'],
					"des"      => $r,
					"tip"      => $tip,
					"uid"      => $call['uid'],
					"identity" => $call['identity'],
				]);

			}

		}

	}

	if ($printres == 'yes') {
		$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;
	}

}
else {

	$rez = $result['error'];

}

$return = [
	"result"  => $rez,
	//"request" => $_REQUEST,
	"period"  => $data,
	//"sip"     => $sip,
	//"response" => $result
];

file_put_contents($logfile, "0");

//очищаем подключение к БД
unset($db);

print json_encode_cyr($return);
exit();