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

error_reporting( E_ERROR );

//ini_set( 'display_errors', 1 );
ini_set( 'memory_limit', '512M' );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];

// форсированный режим запроса cdr
$isforce = (int)$_REQUEST['force'] == 1;

if ( $identity == '' ) {
	$identity = $db -> getOne( "SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'" );
}

if ( (int)$identity == 0 ) {

	$return = ["error" => "Не верный ключ CRM API"];
	goto toexit;

}

require_once $rootpath."/inc/func.php";

// если скрипт запускается из консоли, то переменные считываем из аргументов
if ( PHP_SAPI == 'cli' ) {

	$req = parse_argv( $argv );
	foreach ( $req as $r => $v ) {
		$$r = $v;
	}

	// форсированный режим запроса cdr
	$isforce = (int)$force == 1;

	if ( $identity == '' ) {
		$identity = $db -> getOne( "SELECT id FROM {$sqlname}settings WHERE api_key = '$apkey'" );
	}

	if ( (int)$identity == 0 ) {

		$return = ["error" => "Не верный ключ CRM API"];
		goto toexit;

	}

}


//Добавлять запись в историю
$putInHistory = false;

// отсекаем запуск процессов-дублей
$logfile  = $rootpath."/cash/pbx.log";
$isActive = false;
$lastTime = current_datumtime( 1 );
if ( file_exists( $logfile ) && !$isforce ) {

	$isActive = file_get_contents( $logfile ) == "1";
	$lastTime = unix_to_datetime( fileatime( $logfile ) );

	if ( $isActive || diffDateTimeSeq( $lastTime ) < 300 ) {

		$return = ["result" => "Запрос уже активен"];
		goto toexit;

	}

}

//параметры подключения к серверу
require_once dirname( __DIR__)."/gravitel/sipparams.php";
require_once dirname( __DIR__)."/gravitel/mfunc.php";

function UTCtoDateTimeSelf($string) {

	$dm = date_parse( $string );

	if ( $dm['errors'] != '' ) {

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
		$offset  = $d['clientOffset'] + $dm['zone'] / 60;
		$newdate = DateTimeToServerDate( $dm['year']."-".$dm['month']."-".$dm['day']." ".$dm['hour'].":".$dm['minute'].":".$dm['second'], -$offset );

	}
	else {
		$newdate = '';
	}

	return $newdate;

}

//$hours = 24;
$list = $return = [];

//массив внутренних номеров сотрудников
$users = [];

$r = $db -> getAll( "SELECT iduser, phone, phone_in, mob FROM {$sqlname}user WHERE identity = '$identity'" );
foreach ( $r as $da ) {

	if ( $da['phone'] != '' ) {
		$users[ prepareMobPhone( $da['phone'] ) ] = $da['iduser'];
	}
	if ( $da['phone_in'] != '' ) {
		$users[ prepareMobPhone( $da['phone_in'] ) ] = $da['iduser'];
	}
	if ( $da['mob'] != '' ) {
		$users[ prepareMobPhone( $da['mob'] ) ] = $da['iduser'];
	}

}

//посмотреим дату последнего звонка из истории звонков
if ( $last_datum == '' ) {
	$last_datum = $db -> getOne( "SELECT MAX(datum) FROM {$sqlname}callhistory WHERE identity = '$identity'" );
}

//если проверок не было (на старте) или была больше 30 дней назад
//то берем за месяц
if ( $last_datum == '' || (int)diffDate2( $last_datum ) > 30 || $hours > 0 ) {

	//берем статистику за месяц
	if ( !$hours ) {
		$hours = 24 * 30;
	}

	$delta     = $hours * 3600;//период времени, за который делаем запрос в часах
	$zone      = $GLOBALS['tzone'];//смещение временной зоны сервера

	//$dateStart = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) - $delta );
	$dateStart = modifyDatetime( current_datum(), [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -$hours
	] );

}
else {
	$dateStart = $last_datum;
}

//проверяем не чаще, чем раз в 5 минут
//иначе, при большом количестве пользователей
//резко возрастает нагрузка на сервер
if ( diffDateTimeSeq( $dateStart ) < 300 && !$isforce ) {

	$return = ["result" => "Данные обновлены менее 5 минут назад"];
	goto toexit;

}

// пометим процесс активным
file_put_contents( $logfile, "1" );

//$dateEnd = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) );
$dateEnd = modifyDatetime(NULL, ["minutes" => 5]);

//Делаем запрос на аккаунты пользователей, т.к. история приходит с логинами
$accounts = doMethod( 'accounts', [
	"api_key"  => $api_key,
	"api_salt" => $api_salt
] );

$users = [];
foreach ( $accounts as $i => $account ) {
	$users[ $account['name'] ] = $account['ext'];
}

//Делаем запрос на подготовку статистики и получаем key этого запроса
$data = [
	"api_key"  => $api_key,
	"api_salt" => $api_salt,
	"dstart"   => modifyDatetime( $dateStart, ["format" => "Ymd\THis"] ),
	"dend"     => modifyDatetime( $dateEnd, ["format" => "Ymd\THis"] )
];
//print_r($data);
$result = doMethod( 'history', $data );

//print $result;
//exit();

$calls = yexplode( "\n", $result );

//print_r($calls);
//exit();

$originalCalls = [];

foreach ( $calls as $call ) {

	$iduser = 0;
	$ext    = 0;

	/*
	$calldate = UTCtoDateTimeSelf( $str[ 5 ] );
	$diff = diffDate2($calldate, $dateStart);

	if( abs($diff) > 10 ){
		continue;
	}
	*/

	/*
	0 => UID, //уникальный идентификатор звонка
	1 => type, //тип вызова: in / out / missed
	2 => client, //номер клиента
	3 => account, //логин сотрудника, который разговаривал с клиентом или имя группы или код:
	     ivr / fax, если звонок не дошел до сотрудника
	4 => via, //номер телефона, через который пришел входящий звонок или АОН для исходящего вызова
	5 => start, //время начала звонка в UTC
	6 => wait, //время ожидания на линии (секунд)
	7 => duration, //длительность разговора (секунд)
	8 => record //ссылка на запись разговора
	*/

	$str = $originalCalls[] = explode( ",", $call );

	$src = $dst = $phone = '';

	//если приходит логин, то парсим сотрудника
	if ( $str[3] != '' ) {

		$ext = prepareMobPhone( yexplode( "@", $str[3], 0 ) );
		//$ext  = strtr($user, $users);

		$iduser = (int)$db -> getOne( "select iduser from  {$sqlname}user where phone_in = '$ext' and identity = '$identity'" );

	}

	//если логин не приходит, то ищем по параметру via - часто это мобильный номер
	elseif ( $str[4] != '' ) {

		$str4 = prepareMobPhone( $str[4] );

		$iuser = $db -> getRow( "SELECT iduser, phone_in FROM {$sqlname}user WHERE ({$sqlname}user.phone = '$str4' OR replace(replace(replace(replace(replace({$sqlname}user.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$str4%') AND {$sqlname}user.identity = '$identity'" );

		$iduser = (int)$iuser['iduser'];
		$ext    = $iuser['phone_in'];

	}

	if ( $str[1] == 'in' ) {

		$src = $phone = $str['2'];
		$dst = $ext;

		$direct = 'income';

		if ( $str[7] > 0 ) {
			$rezult = 'ANSWERED';
		}
		elseif ( $str[7] == 0 ) {
			$rezult = 'NOANSWER';
		}

	}
	elseif ( $str[1] == 'out' ) {

		$src = $ext;
		$dst = $phone = $str['2'];

		$direct = 'outcome';

		if ( $str[7] > 0 ) {
			$rezult = 'ANSWERED';
		}
		elseif ( $str[7] == 0 ) {
			$rezult = 'NOANSWER';
		}

	}
	elseif ( $str[1] == 'missed' ) {

		//работает на Мегафоне не верно
		/*
		$src    = $str['2'];
		$dst    = $str['4'];
		$direct = 'income';
		*/

		$src    = $phone = $str['2'];
		$dst    = ($ext != '') ? $ext : $str['4'];
		$direct = 'income';

		$rezult = 'NOANSWER';

	}

	$u = getxCallerID( $phone );

	$list[] = [
		"uid"      => ($str[0] != '') ? $str[0] : '0',
		"datum"    => UTCtoDateTimeSelf( $str[5] ),
		"res"      => $rezult,
		"sec"      => (int)$str[7],
		"file"     => $str[8],
		"src"      => $src,
		"dst"      => $dst,
		"did"      => $str[4],
		"phone"    => $phone,
		"iduser"   => $iduser,
		"direct"   => $direct,
		"clid"     => (int)$u['clid'],
		"pid"      => (int)$u['pid'],
		"identity" => $identity,
	];

}

/*
$xlsx = new Shuchkin\SimpleXLSXGen();
$xlsx -> addSheet( $list, "Data" );
$xlsx -> addSheet( $originalCalls, "originalCalls" );
$xlsx -> saveAs( $rootpath."/cash/calls.xlsx" );
*/

//file_put_contents($rootpath."/cash/calls.json", json_encode_cyr($calls));
//file_put_contents($logfile, "0");
//exit();

//print array2string($list, "<br>", "&nbsp;");
//print_r($list);
//exit();

$upd = $new = 0;

//обрабатываем запрос
foreach ( $list as $call ) {

	//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
	//$id = $db -> getOne("SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND phone = '$call[phone]' AND direct = '$call[direct]' AND identity = '$identity'");
	$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}callhistory WHERE uid = '$call[uid]' AND identity = '$identity'" );

	if ( $id == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}callhistory SET ?u", $call );

		$new++;

	}
	else {

		$data = $call;

		unset( $data['uid'], $data['callid'] );

		$db -> query( "UPDATE {$sqlname}callhistory SET ?u WHERE id = '$id'", $data );

		$upd++;

	}

	//добавим запись в историю активностей
	if ( ((int)$call['clid'] > 0 || (int)$call['pid'] > 0) && $call['direct'] != 'inner' && $putInHistory ) {

		//проверим, были ли активности по абоненту
		$all = (int)$db -> getOne( "SELECT COUNT(*) AS count FROM {$sqlname}history WHERE (clid = '$call[clid]' OR pid = '$call[pid]') AND uid = '$call[uid]' AND identity = '$identity'" ) + 0;

		if ( $all == 0 ) {

			if ( $call['direct'] == 'outcome' ) {

				$tip = 'исх.1.Звонок';
				$r = 'Исходящий успешный звонок';

			}
			elseif ( $call['direct'] == 'income' ) {

				$tip = 'вх.Звонок';
				$r   = 'Принятый входящий звонок';

			}

			//$tip = 'Запись разговора';

			//добавим запись в историю активности по абоненту
			$db -> query( "INSERT INTO {$sqlname}history SET ?u", [
				"iduser"   => (int)$call['iduser'],
				"clid"     => (int)$call['clid'],
				"pid"      => (int)$call['pid'],
				"datum"    => $call['datum'],
				"des"      => $r,
				"tip"      => $tip,
				"uid"      => $call['uid'],
				"identity" => $call['identity'],
			] );

		}

	}

}

if ( $_REQUEST['printres'] == 'yes' ) {
	$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;
}

$return = ["result" => $rez];

file_put_contents( $logfile, "0" );

toexit:

//очищаем подключение к БД
unset( $db );

print json_encode_cyr( $return );

exit();