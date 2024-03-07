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
 * обработка событий по добавлению данных в базу
 * запускается из yandextel.js на события
 */
error_reporting(E_ERROR);

//header('Access-Control-Allow-Origin: *');

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

require_once dirname( __DIR__)."/yandextel/mfunc.php";

$return = [];

$response = $_REQUEST;

/**
 * Адаптация под мультиаккаунт
 */
if ( $isCloud ) {

	$apikey = $_REQUEST['apikey'];

	$result   = $db -> getRow("SELECT id, api_key FROM ".$sqlname."settings WHERE api_key = '$apikey'");
	$identity = (int)$result['id'];
	$api_key  = $result['api_key'];

	if (!$identity) {

		$f = fopen($rootpath."/cash/yandextel-worker.log", "a");
		fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
		fwrite($f, "Ошибка: Не верный параметр apikey\r");
		fwrite($f, "========================\r\r");
		fclose($f);

		exit();

	}

}
else {
	$identity = $GLOBALS['identity'];
}

//получение ключа из бд
$res_services = $db -> getRow("SELECT * FROM {$sqlname}services WHERE folder = 'yandextel' and identity = '$identity'");
$api_key      = rij_decrypt($res_services["user_key"], $skey, $ivc);

//получени добавочного номера пользователя
$iduser1  = (int)$GLOBALS['iduser1'];
$extention = $db -> getOne("SELECT phone_in FROM  {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");

//для добавления в таблицу callhistory статусов из Яндекс телефонии (такой же список есть в cdr.php)
$results = [
	'Missed'    => 'NO ANSWER',
	// Missed - пропущенный звонок
	'Connected' => 'ANSWERED',
	//Connected - соединённый звонок
	'VoiceMail' => 'NOANSWER',
	//VoiceMail - голосовое сообщение
	'Dropped'   => 'BREAKED'
	//Dropped - брошеный звонок
];

//определение смещния времени UTC (в базе храниться наше время, у телефонии в UTC) В часах
$clientTimeZone = $db -> getOne("SELECT timezone FROM {$sqlname}settings WHERE id = '$identity'");
$tz             = new DateTimeZone($clientTimeZone);
$dz             = new DateTime();
$dzz            = $tz -> getOffset($dz);
$clientOffset   = $dzz / 3600;

//print $tzone;
//print current_datumtime();
//данные из таблицы settings поле tmezona
if ($tmzone == '') {
	$tmzone = 'Europe/Moscow';
}

//print $tmzone;

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

$f = fopen($rootpath."/cash/yandextel-worker.log", "a");
fwrite($f, current_datumtime()." :::\r".array2string($_REQUEST)."\r");
fwrite($f, "========================\r\r");
fclose($f);

/**
 * История. Приходит после звонка (исходящего и входящего) Обращение идет из yandextel.js
 */
if ($response['cmd'] == 'history') {

	//запрос на получение токина
	$token = doMethod('token', [
		"api_key"  => $api_key,
		"dobnomer" => $extention,
		"iduser"   => $iduser1
	]);

	//var_dump($token);

	//получене данных по звонку
	$dan = [
		"api_key" => $api_key,
		"token"   => $token['access_token']
	];

	$result = doMethod('history', $dan);

	//var_dump($result);

	$phone_from = prepareMobPhone($result['data']['calls']['0']['from']);//От
	$phone_to   = prepareMobPhone($result['data']['calls']['0']['to']);//кому

	$callid   = $result['data']['calls']['0']['id'];//ЗДЕСЬ УЖЕ уникальный id ЗВОНКА
	$status   = $result['data']['calls']['0']['callStatus'];//статус звонка
	$type     = $result['data']['calls']['0']['direction'];//тип (Incoming - входящий звонок или Outgoing - исходящий звонок)
	$type     = ($type == 'Incoming') ? 'in' : 'out';
	$link     = $result['data']['calls']['0']['callRecord']['uri'];//ссылка на файл с записью
	$duration = $result['data']['calls']['0']['duration'] / 1000;//время разговора в секундах приходит в милисекундах
	$datum    = $result['data']['calls']['0']['dateTimeUtc'];//дата

	//дата из телефонии с учетом тайм зоны
	/*$datUTC  = explode("T", $datum);
	$dataUTC = explode("-", $datUTC[0]);
	$timUTC  = explode(":", $datUTC[1]);
	$datum   = date("Y-m-d H:i:s", mktime($timUTC[0] + $clientOffset, $timUTC[1], $timUTC[2], $dataUTC[1], $dataUTC[2], $dataUTC[0]));*/

	$datum = modifyDatetime( $datum, [
		"format" => 'Y-m-d H:i:s',
		"hours"  => $clientOffset
	] );

	if ($type == 'in') {
		
		$dst   = $phone_from;
		$src   = $extention;
		$did   = $phone_to;
		$phone = $phone_from;
		
	}
	elseif ($type == 'out') {
		
		$dst   = $extention;
		$src   = $phone_to;
		$did   = $phone_from;
		$phone = $phone_to;
		
	}

	$u = [];
	if ($phone != '') {
		$u = getxCallerID( $phone );
	}

	$id = (int)$db -> getOne("SELECT id FROM  {$sqlname}yandextel_log WHERE type = '$type' and extension = '$extention' and identity = '$identity'") + 0;

	if ($id > 0) {
		$db -> query( "DELETE FROM  {$sqlname}yandextel_log WHERE id = '$id'" );
	}


	//print current_datumtime();

	//добавляем запись в историю звонков
	$call = [
		"res"    => strtr($status, $results),
		"src"    => $src,
		"dst"    => $dst,
		"did"    => $did,
		"phone"  => $phone,
		"iduser" => $iduser1,
		"direct" => ($type == 'in') ? 'income' : 'outcome',
		"clid"   => $u['clid'] + 0,
		"pid"    => $u['pid'] + 0,
		"file"   => $link,
		"sec"    => $duration,
		"uid"    => $callid,
		"datum"  => $datum
	];

	//var_dump($call);

	$cid = $db -> getOne("SELECT id FROM  {$sqlname}callhistory WHERE uid = '$callid' and identity = '$identity'") + 0;

	if ($cid == 0)
		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);

}


/**
 * Событие. Входящий звонок и идет разговор (приходит cmd2=call) Обращение идет из yandextel.js
 *
 */
if ($response['cmd'] == 'event') {

	//var_dump($response);
	$call      = $response['cmd2'];//для идентификации что идет разговор
	$phone     = $response['phone'];//номер телефона клиента string
	$extension = $response['extension'];//внутренний номер пользователя облачной АТС
	$number_to = $response['number_to'];//ваш номер телефона, через который пришел входящий вызов
	$type      = $response['type'];//тип звонка (in/out)
	$status    = $response['status'];///статус входящего звонка:

	$u = [];
	if ($phone != '') {
		$u = getxCallerID( $phone );
	}

	//print_r($u);

	//Идентификатор записи буфера для текущего пользователя
	$id = (int)$db -> getOne("SELECT id FROM {$sqlname}yandextel_log WHERE extension = '$extension' and identity = '$identity'") + 0;

	//идет входящий звонок или исходящий, но трубка еще не поднята
	if ($id == 0 && $call == '') {

		//если запись не найдена, то создаем её
		$db -> query("INSERT INTO  {$sqlname}yandextel_log SET ?u", [
			'datum'     => current_datumtime(),
			'extension' => $extension,
			'phone'     => preparePhone($phone),
			'status'    => $status,
			'type'      => $type,
			'clid'      => $u['clid'] + 0,
			'pid'       => $u['pid'] + 0,
			'identity'  => $identity
		]);
		$id = $db -> insertId();

	}

	//идет разговор
	if ($call != '')
		$db -> query("UPDATE  {$sqlname}yandextel_log SET ?u WHERE id = '$id'", [
			'datum'  => current_datumtime(),
			'status' => $status
		]);

}


toexit:

print json_encode_cyr($return);