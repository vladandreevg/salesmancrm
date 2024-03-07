<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

set_time_limit( 0 );

/*
 * запускается из telfin.js
 * мониторинг звонков в истории звонков
 * в список выводятся только 'ANSWERED','NO ANSWER','BUSY'
 */
error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];

// форсированный режим запроса cdr
$isforce = (int)$_REQUEST['force'] == 1;

if ($identity == '') {
	$identity = $db -> getOne("SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'");
}

if ((int)$identity == 0) {

	$return = ["error" => "Не верный ключ CRM API"];
	goto toexit;

}

include $rootpath."/inc/func.php";

require_once dirname( __DIR__)."/telfin/sipparams.php";
require_once dirname( __DIR__)."/telfin/mfunc.php";

//для добавления в таблицу callhistory статусов (подобный список есть в events.php)
$statuses = [
	"noanswer"     => "NOANSWER",
	//звонок не отвечен (истек таймер ожидания на сервере)
	"congestion"   => "BREAKED",
	//произошла ошибка во время вызова
	"chanunavail"  => "ANSWERED",
	//у вызываемого абонента отсутствует регистрация
	"cancel"       => "BREAKED",
	//звонящий отменил вызов до истечения таймера ожидания на сервере
	"answered"     => 'ANSWERED',
	//при входящем и исходящем поговорили
	"failed"       => 'BREAKED',
	//при входящем отменили звонок мы не взяв трубку, при исходящем мы не взяли трубку на свой добавочный
	"not answered" => 'BREAKED',
	//при входящем трубку положил тот кто нам звонил не дождавшись чтобы мы сняли трубку
	"busy"         => 'BUSY',
	//"bridged" =>'ANSWERED'//при исходящем когда прошел звонок, абонет взял трубку и прошел разговор
];

$list = $return = [];

//определение смещния времени UTC (в базе храниться наше время, у телефонии в UTC) В часах
$clientTimeZone = $db -> getOne("select timezone from {$sqlname}settings WHERE id = '$identity'");
$tz             = new DateTimeZone($clientTimeZone);
$dz             = new DateTime();
$dzz            = $tz -> getOffset($dz);
$clientOffset   = $dzz / 3600;

//посмотреим дату последнего звонка из истории звонков
$last_datum = $db -> getOne("SELECT MAX(datum) FROM {$sqlname}callhistory WHERE identity = '$identity'");

// если загрузка первый раз, прошло больше 30 дней или запущена проверка вручную пользователям
// (приходит значение 24 часа)
if ($last_datum == '' || diffDate2($last_datum) > 30 || $hours > 0) {

	//берем статистику за месяц
	if ($last_datum == '' || diffDate2($last_datum) > 30) {
		$hours = 24 * 30;
	}

	$delta = $hours * 3600;//период времени, за который делаем запрос (секунды)

	//определяем начальную дату
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H') - $clientOffset, date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);
	$dateStart = modifyDatetime( current_datumtime(), [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -($clientOffset + $hours)
	] );

}
else {

	// поскольку в базе у нас наше время, а у Телфин UTC
	// делаем смещение на тайм зону.
	// $timStart[2]+1 для того чтобы не брал запись которая есть уже в базе
	//$dateStart = explode(" ", $last_datum);
	//$dataStart = explode("-", $dateStart[0]);
	//$timStart  = explode(":", $dateStart[1]);
	//$dateStart = date("Y-m-d H:i:s", mktime($timStart[0] - $clientOffset, $timStart[1], $timStart[2] + 1, $dataStart[1], $dataStart[2], $dataStart[0]));

	$dateStart = modifyDatetime( $last_datum, [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -$clientOffset,
		"minutes" => 1
	] );

}

//проверяем не чаще, чем раз в 5 минут
//иначе, при большом количестве пользователей
//резко возрастает нагрузка на сервер
if(diffDateTimeSeq($dateStart) < 300 && !$isforce) {
	goto toexit;
}

//$dateEnd = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')));
$dateEnd = modifyDatetime(NULL, ["minutes" => 5]);

// запрос на получение токина
$token = doMethod('token', [
	"api_key"    => $api_key,
	"api_secret" => $api_secret
]);

/*
$dateStart = urlencode('2017-09-19 12:38:39');
$dateEnd   = urlencode('2017-09-19 12:38:39');
*/

// получене данных по звонку.
// Необходимо для получения продолжительности и даты начала звонка, uid звонка
$params = [
	"token"          => $token['access_token'],
	"start_datetime" => $dateStart,
	"end_datetime"   => $dateEnd
];

$result = doMethod('history_osn', $params);
$calls  = $result['call_history'];

//print array2string($calls, "<br>", str_repeat("&nbsp;", 5));
//exit();

foreach ($calls as $call) {

	//тип
	$type = $call['flow'];

	//ссылка на файл
	$file = '';

	//доавялем в callhistory in все, по out только отвеченые на добавочный и покоторым произошел разговор(не добавляем те которые было нажат телефон в CRM, но трубка не была снята на приходящий добавочный)
	if (($type == 'out' && $call['result'] != 'failed') || ($type == 'in')) {

		// Берем последее событие
		$cdr = $call['cdr'][0];

		// uid звонка
		$record_uuid = $cdr['record_uuid'];

		// если id записи нет, то смотрим в следующем массиве
		if ($record_uuid == ''){

			$i = 0;
			while (is_array($call['cdr'][$i++])) {

				$record_uuid = $call['cdr'][$i]['record_uuid'];

				if($record_uuid != '') {
					break;
				}

			}

		}

		// если ссылка на историю звонка не пустая
		// то получаем ссылку на файл
		if ($record_uuid != '') {

			$dan_record = [
				"api_key" => $api_key,
				"token"   => $token['access_token'],
				"record"  => $record_uuid
			];

			$link = doMethod('record', $dan_record);
			$file = $link['record_url'];

		}

		// результат звонка
		$status = $cdr['result'];

		// uid звонка.
		// привидем к нижнему регистру, поскольку при окончании звонка у нас в базу добавляется
		// с нижнем регистром (приходит такая данные), а в хистори они в верхнем регистре
		$callid = mb_strtolower($call['call_uuid']);

		// продолжительность разговора приходит в секундах
		$duration = $call['bridged_duration'];

		// дата начала звонка
		$datum = $call['init_time_gmt'];

		// дата из телефонии с учетом тайм зоны
		//$datUTC  = explode(" ", $datum);
		//$dataUTC = explode("-", $datUTC[0]);
		//$timUTC  = explode(":", $datUTC[1]);
		//$datum   = date("Y-m-d H:i:s", mktime($timUTC[0] + $clientOffset, $timUTC[1], $timUTC[2], $dataUTC[1], $dataUTC[2], $dataUTC[0]));
		$datum = modifyDatetime( $datum, [
			"format" => 'Y-m-d H:i:s',
			"hours"  => $clientOffset
		] );

		/**
		 * Входящий звонок
		 */
		if ($type == 'in') {

			// источник. номер абонента
			$src = prepareMobPhone($call['from_username']);

			// номер линии - на какой номер позвонили "did_number": "78123092854",
			$did = prepareMobPhone($call['did_number']);

			// смотрим юзера в основном массиве
			$extension = yexplode("*", $call['bridged_username'], 1);

			// находим $extension,
			// для этого необходимо вычислить cdr в котором flow=transfer
			// поскольку $extension только там указан
			if($extension == '') {
				foreach ( $call['cdr'] as $key => $value ) {
					if ( $value['flow'] == 'transfer' ) {
						$extension = yexplode( "*", $call['cdr'][ $key ]['dest_number'], 1 );
					}
				}
			}


			// номер оператора
			$dst   = $extension;

			// номер абонента
			$phone = $src;

		}

		/**
		 * Исходящий звонок
		 */
		elseif ($type == 'out') {

			// кому
			$dst = prepareMobPhone($cdr['dest_number']);

			// номер линии
			$did = prepareMobPhone($call['did_number']);

			// приходит с префиксом "3515*101",
			// нам нужна вторая часть - только 101
			$extension = yexplode("*", $call['from_username'], 1);

			// источник. номер оператора
			$src   = $extension;

			// номер абонента
			$phone = $dst;

		}

		$u = [];
		if ($phone != '') {
			$u = getxCallerID( $phone );
		}

		$iduser = ($extension != '') ? (int)$db -> getOne("select iduser from {$sqlname}user where phone_in = '$extension' and identity = '$identity'") : 0;

		// добавляем запись в историю звонков
		$list[] = [
			"res"    => strtr($status, $statuses),
			"src"    => $src,
			"dst"    => $dst,
			"did"    => $did,
			"phone"  => $phone,
			"iduser" => $iduser,
			"direct" => ($type == 'in') ? 'income' : 'outcome',
			"clid"   => $u['clid'] + 0,
			"pid"    => $u['pid'] + 0,
			"file"   => $file,
			"sec"    => $duration + 0,
			"uid"    => ($callid != '') ? $callid : "0",
			"datum"  => $datum
		];

	}

}

//var_dump($list);
//exit();

$upd = $new = 0;

//обрабатываем запрос
foreach ($list as $call) {

	//добавим в таблице callhistory или обновим уже имеющиеся записи (которые записаны в CRM)
	$id = $db -> getOne("SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND phone = '$call[phone]' AND direct = '$call[direct]' AND identity = '$identity'");

	if ($id == 0) {

		$call['identity'] = $identity;

		$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);
		$new++;

	}
	else {

		unset($call['uid']);

		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$id'", $call);
		$upd++;

	}


}

//в файле n.callhistory.php строка 56 и 281
if ($_REQUEST['printres'] == 'yes') {
	$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;
}

$return = ["result" => $rez];

toexit:

//очищаем подключение к БД
unset($db);

print json_encode_cyr($return);

exit();