<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/*
 * запускается из yandextel.js
 * мониторинг звонков в истории звонков
 * в список выводятся только 'ANSWERED','NO ANSWER','BUSY'
 */
error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

if ($identity == '') {
	$identity = $db -> getOne( "SELECT id FROM {$sqlname}settings WHERE api_key = '$_GET[apkey]'" ) + 0;
}

if ($identity == 0) {

	$return = ["error" => "Не верный ключ CRM API"];
	goto toexit;

}

require_once $rootpath."/inc/func.php";

require_once dirname( __DIR__)."/yandextel/mfunc.php";
require_once dirname( __DIR__)."/yandextel/sipparams.php";

//Добавлять запись в историю
$putInHistory = false;

//параметры подключения
$ytelset = $GLOBALS['ytelset'];

// $hours = 24; приходит из callhistory.php
// преобразуем в число
$hours = pre_format($_REQUEST['hours']);

//для добавления в таблицу callhistory статусов из Яндекс телефонии (такой же список есть в events.php)
$statuses = [
	'Missed'    => 'NO ANSWER',
	//Missed - пропущенный звонок
	'Connected' => 'ANSWERED',
	//Connected - соединённый звонок
	'VoiceMail' => 'NOANSWER',
	//VoiceMail - голосовое сообщение
	'Dropped'   => 'BREAKED'
	//Dropped - брошеный звонок
];

//определение смещния времени UTC (в базе храниться наше время, у телефонии в UTC) В часах
$clientTimeZone = $db -> getOne("select timezone from {$sqlname}settings WHERE id = '$identity'");
$tz             = new DateTimeZone($clientTimeZone);
$dz             = new DateTime();
$dzz            = $tz -> getOffset($dz);
$clientOffset   = $dzz / 3600;

$list = $return = [];

//массив внутренних номеров сотрудников
/*
$users = array();

$r = $db -> getAll("SELECT iduser, phone, phone_in, mob FROM {$sqlname}user WHERE identity = '$identity'");
foreach ($r as $da) {

	if ($da['phone'] != '')    $users[ prepareMobPhone($da['phone']) ] = $da['iduser'];
	if ($da['phone_in'] != '') $users[ prepareMobPhone($da['phone_in']) ] = $da['iduser'];
	if ($da['mob'] != '')      $users[ prepareMobPhone($da['mob']) ] = $da['iduser'];

}
*/

//посмотреим дату последнего звонка из истории звонков
$last_datum = $db -> getOne("SELECT MAX(datum) FROM {$sqlname}callhistory WHERE identity = '$identity'");

//если загрузка первый раз и прошло больше 30 дней
if ($last_datum == '' || diffDate2($last_datum) > 30) {

	//берем статистику за месяц
	$hours = 24 * 30;

	//период времени, за который делаем запрос (секунды)
	$delta = $hours * 3600;

	//определяем начальную дату
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H') - $clientOffset, date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);//2017-07-29 17:29:14
	$dateStart = modifyDatetime( current_datumtime(), [
		"format" => 'Y-m-dTH:i:s',
		"hours"  => -($clientOffset + $hours)
	] );

}
//если запущена проверка вручную пользователям (приходит значение 24 часа)
elseif ($hours != '') {

	$delta = $hours * 3600;//период времени, за который делаем запрос (секунды)

	//определяем начальную дату с учетом тайм зоны
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H') - $clientOffset, date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);//2017-07-29 17:29:14

	$dateStart = modifyDatetime( current_datumtime(), [
		"format" => 'Y-m-dTH:i:s',
		"hours"  => -($clientOffset + $hours),
		"minutes" => 1
	] );

}
//поскольку в базе у нас наше время, а у телефонии utc делаем смещение на тайм зону. $timStart[2]+1 для того чтобы не брал запись которая есть уже в базе
else {

	/*$dateStart = explode(" ", $last_datum);
	$dataStart = explode("-", $dateStart[0]);
	$timStart  = explode(":", $dateStart[1]);
	$dateStart = date("Y-m-d H:i:s", mktime($timStart[0] - $clientOffset, $timStart[1], $timStart[2] + 1, $dataStart[1], $dataStart[2], $dataStart[0]));*/

	$dateStart = modifyDatetime( $last_datum, [
		"format" => 'Y-m-dTH:i:s',
		"hours"  => -($clientOffset + $hours),
		"minutes" => 1
	] );

}

//для того чтобы правильно передать дату для телефнии у них такой формат нужен
//$dateStart = explode(" ", $dateStart);
//$dateStart = $dateStart[0].'T'.$dateStart[1];

///КОГДА БЫЛА ЗАПУЩЕНА ПОСЛЕДНЯЯ ПРОВЕРКА
$lastCheck = $db -> getRow("SELECT * FROM  {$sqlname}customsettings WHERE identity = '$identity' and tip='yandextelHistory'");

//разница между текущей датой и последней проверки данных в телефонии (каждые 10 минут будет проврека)
$timeh1 = time();
$timeh2 = strtotime($lastCheck['datum']);
$diffh  = ($timeh1 - $timeh2); // разница в секундах

//если проверки небыло или прошло больше 10 минут с момента последней проверки
if ($lastCheck == 0 || $diffh > 600) {

	$users = $db -> getIndCol("iduser", "SELECT DISTINCT phone_in, iduser FROM  {$sqlname}user WHERE identity = '$identity' AND phone_in != ''");
	$upd   = $new = 0;

	/**
	 * Для каждого сотрудника получаем историю звонков
	 * Вот такое API у Яндекса, блин
	 */
	foreach ($users as $iduser => $extention) {

		//для проверки есть ли запись в customsettings с tip='yandextelHistory'
		$isSettingsExist = (int)$db -> getOne("SELECT COUNT(*) as count FROM  {$sqlname}customsettings WHERE identity = '$identity' and tip = 'yandextelHistory'");

		//если в базе есть запись о проверки, но прошло больше 10 минут запускаем проверку
		if ($isSettingsExist > 0) {

			//для обновлении в базе
			$data = [
				"datum"  => current_datumtime(),
				"iduser" => $iduser
			];
			$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'yandextelHistory' and identity = '$identity'", $data);

		}
		else {

			//для добавлении в базу
			$data = [
				"datum"    => current_datumtime(),
				"tip"      => 'yandextelHistory',
				"iduser"   => $iduser,
				"identity" => $identity
			];
			$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", $data);

		}

		//запрос на получение токена
		$token = doMethod('token', [
			"api_key"  => $ytelset['api_key'],
			"dobnomer" => $extention,
			"iduser"   => $iduser
		]);

		//запрос на получене данных по сотруднику
		$danZv = [
			"api_key"    => $ytelset['api_key'],
			"token"      => $token['access_token'],
			"data_filtr" => $dateStart,
			"iduser"     => $iduser
		];

		//запрос CDR по API
		$result = doMethod('history_osn', $danZv);

		//список звонков
		$cdr = $result['data']['calls'];

		/**
		 * Готовим данные из ответа сервера
		 */
		foreach ($cdr as $key => $call) {

			//тип (Incoming - входящий звонок или Outgoing - исходящий звонок)
			$type = $call['direction'];

			//От - При out выдает имя а не телефон
			$phone_from = prepareMobPhone($call['from']);

			//кому
			$phone_to = prepareMobPhone($call['to']);

			$callid    = $call['id'];//ЗДЕСЬ УЖЕ уникальный id ЗВОНКА
			$datum     = $call['dateTimeUtc'];//дата
			$status    = $call['callStatus'];//статус звонка
			$direction = ($type == 'Incoming') ? 'in' : 'out';
			$link      = $call['callRecord']['uri'];//ссылка на файл с записью
			$duration  = $call['duration'] / 1000;//время разговора в секундах приходит в милисекундах

			if ($direction == 'in') {
				
				$dst   = $phone_from;
				$src   = $extention;
				$did   = $phone_to;
				$phone = $phone_from;
				
			}
			else {
				
				$dst   = $extention;
				$src   = $phone_to;
				$did   = '';
				$phone = $phone_to;
				
			}

			$u = getxCallerID($phone);

			//дата из телефонии с учетом тайм зоны
			/*$datUTC  = explode("T", $datum);
			$dataUTC = explode("-", $datUTC[0]);
			$timUTC  = explode(":", $datUTC[1]);
			$datum   = date("Y-m-d H:i:s", mktime($timUTC[0] + $clientOffset, $timUTC[1], $timUTC[2], $dataUTC[1], $dataUTC[2], $dataUTC[0]));*/

			$datum = modifyDatetime( $datum, [
				"format" => 'Y-m-d H:i:s',
				"hours"  => $clientOffset
			] );

			$list[] = [
				"uid"      => ($callid != '') ? $callid : "0",
				"datum"    => $datum,
				"res"      => strtr($status, $statuses),
				"sec"      => $duration,
				"file"     => ($link != '') ? $link : '',
				"src"      => $src,
				"dst"      => $dst,
				"did"      => $did,
				"phone"    => $phone,
				"iduser"   => $iduser + 0,
				"direct"   => ($type == 'in') ? 'income' : 'outcome',
				"clid"     => ($u['clid'] != '') ? $u['clid'] : '0',
				"pid"      => ($u['pid'] != '') ? $u['pid'] : '0',
				"identity" => $identity,
			];

		}

		/**
		 * обрабатываем запрос
		 */
		foreach ($list as $call) {

			//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
			$id = (int)$db -> getOne("SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND phone = '$call[phone]' AND direct = '$call[direct]' AND identity = '$identity'");

			if ($id == 0) {

				$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);
				$new++;

			}
			else {

				unset( $call['uid'], $call['callid'] );

				$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$id'", $call);
				$upd++;

			}


		}

	}

	$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;

}
else {
	$rez = 'Проверка была менее 10 минут назад';
}

toexit:

//очищаем подключение к БД
unset($db);

$return = ["result" => $rez];

print json_encode_cyr($return);

exit();