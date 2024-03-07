<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

set_time_limit( 0 );

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

/**
 * Внимание: используйте этот файл в случае, если Астериск и CRM находятся на разных серверах. В противном случае см.
 * cdr.combine.php
 */
$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];

// форсированный режим запроса cdr
$isforce = (int)$_REQUEST['force'] == 1;

//по умолчанию берем статистику за сутки
if ( !$hours ) {
	$hours = 24;
}

$upd = 0;
$new = 0;

//параметры подключения к серверу
require_once dirname( __DIR__)."/asterisk/sipparams.php";

//Добавлять запись в историю
$putInHistory = false;

// отсекаем запуск процессов-дублей
$logfile  = $rootpath."/cash/pbx.log";
$isActive = false;
$lastTime = current_datumtime( 1 );

if ( file_exists( $logfile ) && !$isforce ) {

	$isActive = file_get_contents( $logfile ) == 1;
	$lastTime = unix_to_datetime( fileatime( $logfile ) );

	if ( $isActive || diffDateTimeSeq( $lastTime ) < 300 ) {

		$return = ["result" => "Запрос уже активен"];
		goto toexit;

	}

}

//Параметры подключения
$sip = $GLOBALS['sip'];

$numoutLength = strlen( $sip['numout'] );

if ( !isset( $_REQUEST['apkey'] ) ) {
	$api_key = $db -> getOne( "SELECT api_key FROM ".$sqlname."settings WHERE id = '$identity'" );
}
else {
	$api_key = $apkey;
}

//посмотреим дату последнего звонка из истории звонков
$last_datum = $db -> getOne( "SELECT datum FROM ".$sqlname."callhistory WHERE identity = '$identity' ORDER BY datum DESC LIMIT 1" );

//параметры подключения к серверу
$url              = $sip['cdr'];
$config['secret'] = $api_key;

if ( $last_datum == '' || $hours > 0 ) {

	$delta = $hours * 3600;//период времени, за который делаем запрос в часах
	$zone  = $GLOBALS['tzone'];//смещение временной зоны сервера
	//$dateStart = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);

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
if ( diffDateTimeSeq( $dateStart ) < 300 ) {
	goto toexit;
}

// пометим процесс активным
file_put_contents( $logfile, true );

//$dateEnd = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')));
$dateEnd = modifyDatetime(NULL, ["minutes" => 5]);

// Создаём POST-запрос
$params['dateStart'] = urlencode( $dateStart );
$params['dateEnd']   = urlencode( $dateEnd );
$params['hash']      = urlencode( $config['secret'] );

// Устанавливаем соединение
$ch = curl_init();
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
curl_setopt( $ch, CURLOPT_URL, $url );

$res = curl_exec( $ch );

if ( curl_errno( $ch ) > 0 ) {
	$err = curl_error( $ch );
}

curl_close( $ch );

if ( $err ) {

	print 'Ошибка curl: '.$err;
	exit();

}

//раскодируем данные из JSON-формата
$rez = json_decode( $res, true );

if ( $rez['error']['code'] == 1 ) {

	print "Ошибка: ".$rez['error']['text'];

}

$mass = [];

//обрабатываем запрос
foreach ( $rez['data'] as $call ) {

	$clid   = 0;
	$pid    = 0;
	$iduser = 0;
	$src    = [];

	//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
	$rezy = $db -> getRow( "SELECT * FROM ".$sqlname."callhistory WHERE uid = '".$call['uid']."' AND identity = '".$identity."'" );
	if ( (int)$rezy['id'] > 0 ) {

		if ( $rezy['direct'] == 'outcome' ) {

			$iduser = (int)getUserID( preparePhone( $call['src'] ) );

			$src  = getxCallerID( $call['dst'] );
			$clid = (int)$src['clid'];
			$pid  = (int)$src['pid'];

		}
		elseif ( $rezy['direct'] == 'income' ) {

			$iduser = (int)getUserID( preparePhone( $call['src'] ) );

			$src  = getxCallerID( $call['dst']);
			$clid = (int)$src['clid'];
			$pid  = (int)$src['pid'];

		}

		if( strlen($phone) > 20 || strlen($call['src']) > 20 || strlen($call['dst']) > 20 ){
			continue;
		}

		$db -> query( "UPDATE ".$sqlname."callhistory SET ?u WHERE id = '".$rezy['id']."' and identity = '$identity'", [
			'did'    => $call['did'],
			'datum'  => $call['datum'],
			'res'    => $call['res'],
			'sec'    => $call['sec'],
			'file'   => $call['file'],
			'dst'    => $call['dst'],
			'src'    => $call['src'],
			'clid'   => $clid,
			'pid'    => $pid,
			'iduser' => (int)$iduser
		] );
		$upd++;

	}
	else {

		if ( strlen( $call['src'] ) < 6 && strlen( $call['dst'] ) < 6 ) {

			$direct = 'inner';//внутренний звонок

			$phoneSrc = preparePhone( $call['src'] );//инициатора вызова
			$phoneDst = preparePhone( $call['dst'] );//цель вызова

		}
		elseif ( strlen( $call['src'] ) < 6 && strlen( $call['dst'] ) > 6 ) {

			$direct = 'outcome';//исходящий звонок

			$phoneSrc = preparePhone( $call['src'] );//инициатора вызова
			$phoneDst = preparePhone( $call['dst'] );//цель вызова

			//очищаем номер в dst, т.к. Астериск его сам подставляет
			if ( $sip['numout'] && strlen( $phoneDst ) > 6 ) {
				$phoneDst = substr( $phoneDst, $numoutLength );
			}

			//$src = getCallerID($phoneDst);
			$src = getxCallerID( $phoneDst );

			$phone = $phoneDst;

			$clid   = (int)$src['clid'];
			$pid    = (int)$src['pid'];
			$iduser = (int)getUserID( $phoneSrc );

		}
		else {

			$direct = 'income';//входящий звонок

			$phoneSrc = preparePhone( $call['src'] );//инициатора вызова
			$phoneDst = preparePhone( $call['dst'] );//цель вызова

			//очищаем номер в dst, т.к. Астериск его сам подставляет
			//if(strlen($phoneSrc)>6) $phoneSrc = substr($phoneSrc,1);
			//if($sip_numout and strlen($phoneDst)>6) $phoneDst = substr($phoneDst,1);
			if ( $sip['pfchange'] && strlen( $phoneDst ) > 6 ) {
				$phoneDst = substr( $phoneDst, 1 );
			}

			$src = getxCallerID( $phoneSrc );

			$phone = $phoneSrc;

			$clid   = (int)$src['clid'];
			$pid    = (int)$src['pid'];
			$iduser = (int)getUserID( $phoneDst );

		}

		$file = $call['file'];

		if( strlen($phone) > 20 || strlen($phoneSrc) > 20 || strlen($phoneDst) > 20 ){
			continue;
		}

		if ( $phone != '' ) {

			$db -> query( "INSERT INTO ".$sqlname."callhistory SET ?u", [
				'uid'      => $call['uid'],
				'did'      => $call['did'],
				'phone'    => $phone,
				'direct'   => $direct,
				'datum'    => $call['datum'],
				'clid'     => $clid,
				'pid'      => $pid,
				'iduser'   => (int)$iduser,
				'res'      => $call['res'],
				'sec'      => $call['sec'],
				'file'     => $file,
				'src'      => $phoneSrc,
				'dst'      => $phoneDst,
				'identity' => $identity
			] );
		}

		$new++;

		//добавим запись в историю активностей
		if ( ($clid > 0 || $pid > 0) && $direct != 'inner' && $putInHistory ) {

			$sort = '';

			if ( $clid > 0 && $pid > 0 ) {
				$sort = "(clid = '$clid' OR pid = '$pid') AND ";
			}
			elseif ( $clid > 0 && $pid < 1 ) {
				$sort = "clid = '$clid' AND ";
			}
			elseif ( $clid < 1 && $pid > 0 ) {
				$sort = "pid = '$pid' AND ";
			}

			//проверим, были ли активности по абоненту
			$all = (int)$db -> getOne( "SELECT COUNT(*) AS count FROM ".$sqlname."history WHERE $sort identity = '$identity'" );

			if ( $direct == 'outcome' ) {

				if ( $all > 0 ) {
					$tip = 'исх.2.Звонок';
				}
				else {
					$tip = 'исх.1.Звонок';
				}

				$r = 'Исходящий успешный звонок';

			}
			elseif ( $direct == 'income' ) {

				$tip = 'вх.Звонок';
				$r   = 'Принятый входящий звонок';

			}

			//$tip = 'Запись разговора';

			//добавим запись в историю активности по абоненту
			$db -> query( "INSERT INTO ".$sqlname."history SET ?u", [
				'iduser'   => $iduser,
				'clid'     => $clid,
				'pid'      => $pid,
				'datum'    => $call['datum'],
				'des'      => $r,
				'tip'      => $tip,
				'uid'      => $call['uid'],
				'identity' => $identity
			] );

		}

	}

	$phone = '';

}

file_put_contents( $logfile, false );

toexit:

//очищаем подключение к БД
unset( $db );

if ( $_REQUEST['printres'] == 'yes' ) {
	print json_encode_cyr( ["result" => "Успешно.<br>Обновлено записей: $upd<br>Новых записей: $new"] );
}

exit();