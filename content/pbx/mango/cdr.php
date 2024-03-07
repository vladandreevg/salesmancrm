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

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
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

include $rootpath."/inc/func.php";

//Добавлять запись в историю
$putInHistory = false;

//параметры подключения к серверу
require_once dirname( __DIR__)."/mango/sipparams.php";
require_once dirname( __DIR__)."/mango/mfunc.php";

//результаты вызова по ответам
$ANSWERED   = [
	'1100',
	'1110',
	'1120',
	'1170'
];
$BREAKED    = [
	'1180',
	'1181',
	'1191',
	'1192'
];
$TRANSFER   = [
	'1182',
	'1183',
	'1190'
];
$NOANSWER   = [
	'1111',
	'1122'
];
$BUSY       = [
	'1121',
	'1123'
];
$CONGESTION = [
	'1134',
	'1150',
	'1162',
	'1163',
	'1164'
];
$FAILED     = [
	'1130',
	'1131',
	'1132',
	'1133',
	'1140',
	'1151',
	'1152',
	'1160',
	'1161',
	'1171'
];

//$hours = 24;
$list = [];

//массив внутренних номеров сотрудников
$users = [];

$r = $db -> getAll( "SELECT iduser, phone, phone_in, mob FROM ".$sqlname."user WHERE identity = '$identity'" );
foreach ( $r as $da ) {

	if ( $da['phone'] != '' ) {
		$users[ prepareMobPhone( $da['phone'] ) ] = (int)$da['iduser'];
	}
	if ( $da['phone_in'] != '' ) {
		$users[ prepareMobPhone( $da['phone_in'] ) ] = (int)$da['iduser'];
	}
	if ( $da['mob'] != '' ) {
		$users[ prepareMobPhone( $da['mob'] ) ] = (int)$da['iduser'];
	}

}

//посмотреим дату последнего звонка из истории звонков
$last_datum = $db -> getOne( "SELECT MAX(datum) FROM ".$sqlname."callhistory WHERE identity = '$identity'" );

//если проверок не было (на старте) или была больше 30 дней назад
if ( $last_datum == '' || diffDate2( $last_datum ) > 30 || $hours > 0 ) {

	//если часы не указаны, то берем за месяц
	if ( !$hours ) {
		$hours = 24 * 30;
	}//берем статистику за месяц

	$delta = $hours * 3600;//период времени, за который делаем запрос в часах
	$zone  = $GLOBALS['tzone'];//смещение временной зоны сервера
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);
	$dateStart = modifyDatetime( current_datumtime(), [
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
if ( diffDateTimeSeq( $dateStart ) < 300 ) {

	$return = ["result" => "Данные обновлены менее 5 минут назад"];
	goto toexit;

}

//$dateEnd = date('Y-m-d H:i:s', mktime(date('H') + 5, date('i'), date('s'), date('m'), date('d'), date('Y')));
$dateEnd = modifyDatetime(NULL, ["minutes" => 5]);

//Делаем запрос на подготовку статистики и получаем key этого запроса
$result = doMethod( 'history', [
	"api_key"  => $api_key,
	"api_salt" => $api_salt,
	"dstart"   => $dateStart,
	"dend"     => $dateEnd
] );

sleep( 5 );

//получаем статистику
if ( $result['key'] != '' ) {

	$response = doMethod( 'records', [
		"api_key"    => $api_key,
		"api_salt"   => $api_salt,
		"key"        => $result['key'],
		"request_id" => $result['actionID']
	] );

	//print_r($response);

	$csv = yexplode( "\n", $response['records'] );

	foreach ( $csv as $string ) {

		$src    = '';
		$dst    = '';
		$ext1   = '';
		$ext2   = '';
		$phone  = '';
		$iduser = 0;

		$s = explode( ";", $string );

		//определяем наличие внутренних номеров

		//extension_from
		if ( substr( $s[3], 0, 3 ) == 'sip' ) {

			$ex   = explode( "@", $s[3] );
			$ex2  = explode( ":", $ex[0] );
			$ext1 = preparePhone( $ex2[1] );

		}
		elseif ( substr( $s[4], 0, 3 ) == 'sip' ) {

			$ex   = explode( "@", $s[4] );
			$ex2  = explode( ":", $ex[0] );
			$ext1 = preparePhone( $ex2[1] );

		}

		if ( $ext1 == '' && strlen( $s[3] ) < 6 ) {
			$ext1 = $s[3];
		}
		elseif ( $ext1 == '' && strlen( $s[4] ) < 6 ) {
			$ext1 = $s[4];
		}

		if ( $ext1 != '' ) {
			$iduser = $users[ $ext1 ];
		}

		//extension_to
		if ( substr( $s[5], 0, 3 ) == 'sip' ) {

			$ex   = explode( "@", $s[3] );
			$ex2  = explode( ":", $ex[0] );
			$ext2 = preparePhone( $ex2[1] );

		}
		elseif ( substr( $s[6], 0, 3 ) == 'sip' ) {

			$ex   = explode( "@", $s[6] );
			$ex2  = explode( ":", $ex[0] );
			$ext2 = preparePhone( $ex2[1] );

		}

		if ( $ext2 == '' && strlen( $s[5] ) < 6 ) {
			$ext2 = $s[5];
		}
		elseif ( $ext2 == '' && strlen( $s[6] ) < 6 ) {
			$ext2 = $s[6];
		}

		if ( $iduser == 0 && $ext2 != '' ) {
			$iduser = $users[ $ext2 ];
		}

		//направления вызова
		if ( $ext1 != '' && $ext2 != '' ) {

			$direct = 'inner';
			$src    = $ext1;
			$dst    = $ext2;
			$phone  = $ext1;
			$did    = '';

		}
		elseif ( $ext1 != '' && $ext2 == '' ) {

			$direct = 'outcome';
			$phone  = $s[6];
			$src    = $ext1;
			$dst    = $phone;
			$did    = $s[4];

		}
		elseif ( $ext1 == '' && $ext2 != '' ) {

			$direct = 'income';
			$phone  = $s[4];
			$src    = $phone;
			$dst    = $ext2;
			$did    = $s[6];

		}

		//определяем клиента
		$u = ($phone != '') ? getCaller( $phone ) : [];

		//определяем результат вызова
		if ( in_array( $s[7], $ANSWERED ) ) {
			$result = 'ANSWERED';
		}
		if ( in_array( $s[7], $BREAKED ) ) {
			$result = 'BREAKED';
		}
		if ( in_array( $s[7], $TRANSFER ) ) {
			$result = 'TRANSFER';
		}
		if ( in_array( $s[7], $NOANSWER ) ) {
			$result = 'NOANSWER';
		}
		if ( in_array( $s[7], $BUSY ) ) {
			$result = 'BUSY';
		}
		if ( in_array( $s[7], $CONGESTION ) ) {
			$result = 'CONGESTION';
		}
		if ( in_array( $s[7], $FAILED ) ) {
			$result = 'FAILED';
		}

		$list[] = [
			"uid"    => ($s[1] != '') ? $s[1] : "0",
			"datum"  => unix_to_datetime( $s[1] ),
			"res"    => $result,
			"sec"    => ($s[2] - $s[1] + 0),
			"file"   => str_replace( [
				"[",
				"]"
			], "", $s[0] ),
			"src"    => $src,
			"dst"    => $dst,
			"did"    => $did,
			"phone"  => $phone,
			"iduser" => (int)$iduser,
			"direct" => $direct,
			"ext1"   => $ext1,
			"ext2"   => $ext2,
			"clid"   => (int)$u['clid'],
			"pid"    => (int)$u['pid']
		];

	}

}

$upd = 0;
$new = 0;

//обрабатываем запрос
foreach ( $list as $call ) {

	//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
	$id = (int)$db -> getOne( "SELECT id FROM ".$sqlname."callhistory WHERE uid = '".$call['uid']."' AND phone = '".$call['phone']."' AND direct = '".$call['direct']."' AND identity = '$identity'" );
	if ( $id == 0 ) {

		$db -> query( "INSERT INTO ".$sqlname."callhistory SET ?u", [
			'uid'      => $call['uid'],
			'did'      => $call['did'],
			'phone'    => $call['phone'],
			'direct'   => $call['direct'],
			'datum'    => $call['datum'],
			'clid'     => (int)$call['clid'],
			'pid'      => (int)$call['pid'],
			'iduser'   => $call['iduser'],
			'res'      => $call['res'],
			'sec'      => $call['sec'],
			'file'     => $call['file'],
			'src'      => $call['src'],
			'dst'      => $call['dst'],
			'identity' => $identity
		] );

		$new++;

	}
	else {

		$db -> query( "UPDATE ".$sqlname."callhistory SET ?u WHERE id = '$id'", [
			'clid' => (int)$call['clid'],
			'pid'  => (int)$call['pid']
		] );

		$upd++;

	}

	//добавим запись в историю активностей
	if ( ((int)$call['clid'] > 0 || (int)$call['pid'] > 0) && $call['direct'] != 'inner' && $putInHistory ) {

		//проверим, были ли активности по абоненту
		$all = (int)$db -> getOne( "SELECT COUNT(*) AS count FROM ".$sqlname."history WHERE (clid = '".$call['clid']."' OR pid = '".$call['pid']."') AND uid = '".$call['uid']."' AND identity = '".$identity."'" );

		if ( $all == 0 ) {

			if ( $call['direct'] == 'outcome' ) {

				$tip = 'исх.1.Звонок';
				$des = 'Исходящий успешный звонок';

			}
			elseif ( $call['direct'] == 'income' ) {

				$tip = 'вх.Звонок';
				$des = 'Принятый входящий звонок';

			}

			$tip = 'Запись разговора';

			//добавим запись в историю активности по абоненту
			$db -> query( "INSERT INTO ".$sqlname."history SET ?u", [
				'iduser'   => (int)$call['iduser'],
				'clid'     => (int)$call['clid'],
				'pid'      => (int)$call['pid'],
				'datum'    => $call['datum'],
				'des'      => $des,
				'tip'      => $tip,
				'uid'      => $call['uid'],
				'identity' => $identity
			] );

		}

	}

}

if ( $_REQUEST['printres'] == 'yes' ) {
	$rez = 'Успешно.<br>Обновлено записей: '.$upd.'<br>Новых записей: '.$new;
}

toexit:

//очищаем подключение к БД
unset( $db );

$return = ["result" => $rez];

print json_encode_cyr( $return );

exit();