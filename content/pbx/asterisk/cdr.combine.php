<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Этот файл используется в случае, если CRM и Asterisk расположены на одном сервере
 * В этом случае переименуй этот файл в cdr.php, далее настрой подключение к БД Asterisk
 * ......................................................................................................
 * Важно!
 * Для сохранения настроек подключения к БД Asterisk переименуй файл simple.settings.json в settings.json
 * Затем пропиши все параметры подключения к базе данных и сохрани
 * Желательно использовать Notepad++ или Akelpad (для Windows)
 * Это сделано для сохранения настроек при обновлении CRM
 */

error_reporting(E_ERROR);

//преобразуем в число
$hours = (int)$_REQUEST['hours'];
$apkey = $_REQUEST['apkey'];

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";

if(isset($_REQUEST['apkey'])) {
	$identity = $db -> getOne( "SELECT id FROM ".$sqlname."settings WHERE api_key = '$apkey'" ) + 0;
}

include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$dbsettings = json_decode(str_replace([
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents('settings.json')), true);

//настройка подключения к БД Астериска
DEFINE('HOSTBD', $dbsettings['HOSTBD']);
DEFINE('USERBD', $dbsettings['USERBD']);
DEFINE('PASSBD', $dbsettings['PASSBD']);
DEFINE('DATABASE', $dbsettings['DATABASE']);

//параметры подключения к серверу
require_once dirname( __DIR__)."/asterisk/sipparams.php";

$putInHistory = false;
$sip = $GLOBALS['sip'];

$list = [];

$numoutLength = strlen($sip['numout']);

//посмотреим дату последнего звонка из истории звонков
$last_datum = $db -> getOne("SELECT datum FROM ".$sqlname."callhistory WHERE identity = '$identity' ORDER BY datum DESC LIMIT 1");

//параметры подключения к серверу
$url = $sip['cdr'];

if ($last_datum == '' || $hours > 0) {

	if (!$hours) {
		$hours = 24;
	}//по умолчанию берем статистику за сутки

	$delta     = $hours * 3600;//период времени, за который делаем запрос в часах
	$zone      = $GLOBALS['tzone'];//смещение временной зоны сервера

	//$dateStart = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) - $delta);

	$dateStart = modifyDatetime( current_datum(), [
		"format" => 'Y-m-d H:i:s',
		"hours"  => -$delta
	] );

}
else {
	$dateStart = $last_datum;
}

//проверяем не чаще, чем раз в 5 минут
//иначе, при большом количестве пользователей
//резко возрастает нагрузка на сервер
if(diffDateTimeSeq($dateStart) < 300) {
	goto toexit;
}

//$dateEnd = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')));
$param['dateEnd'] = modifyDatetime();

// Создаём POST-запрос
$params['dateStart'] = $dateStart;
$params['dateEnd']   = $dateEnd;

$optss = [
	'host'    => HOSTBD,
	'user'    => USERBD,
	'pass'    => PASSBD,
	'db'      => DATABASE,
	'errmode' => 'exception',
	'charset' => 'UTF8'
];

$asterdb = new SafeMySQL($optss);

//формируем массив данных
$res = $asterdb -> query("SELECT * FROM cdr WHERE calldate BETWEEN '$params[dateStart]' and '$params[dateEnd]' ORDER BY calldate DESC");
while ($data = $asterdb -> fetch($res)) {

	$list[] = [

		"datum" => $data['calldate'],
		//дата звонка
		"src"   => $data['src'],
		//источник вызова
		"dst"   => $data['dst'],
		//пункт назначения вызова
		"res"   => $data['disposition'],
		//результат ANSWERED, NO ANSWER, BUSY
		"dur"   => $data['duration'],
		//продолжительность вызова
		"sec"   => $data['billsec'],
		//Продолжительность вызова с момента ответа на него
		"uid"   => $data['uniqueid'],
		"file"  => $data['recordingfile'],
		//убедись, что в CDR ячейка с именем файла называется как здесь - recordingfile
		"did"   => $data['did']
		//наш номер, на который поступил звонок

	];

}

$mass = [];

$upd = 0;
$new = 0;

include $rootpath."/inc/dbconnector.php";

//обрабатываем запрос
foreach ($list as $item) {

	$clid   = 0;
	$pid    = 0;
	$iduser = 0;

	$phoneSrc = $phoneDst = '';

	//обновим в таблице callhistory уже имеющиеся записи (которые записаны в CRM)
	$idh = $db -> getRow("SELECT * FROM ".$sqlname."callhistory WHERE uid = '$item[uid]' and identity = '$identity'");

	if ((int)$idh['id'] > 0) {

		//if ($idh['direct'] == 'outcome') $iduser = getUserID(preparePhone($item['src']));
		//elseif ($idh['direct'] == 'income') $iduser = getUserID(preparePhone($item['dst']));

		if ($idh['direct'] == 'outcome') {

			$iduser = (int)getUserID( preparePhone( $item[ 'src' ] ) );

			$src = getxCallerID($item[ 'dst' ] );
			$clid   = (int)$src['clid'];
			$pid    = (int)$src['pid'];

		}
		elseif ($idh['direct'] == 'income') {

			$iduser = (int)getUserID( preparePhone( $item[ 'src' ] ) );

			$src = getxCallerID($item[ 'dst' ] );
			$clid   = (int)$src['clid'];
			$pid    = (int)$src['pid'];

		}

		$db -> query("UPDATE ".$sqlname."callhistory SET ?u WHERE id = '$idh[id]' and identity = '$identity'", arrayNullClean([
			'did'    => $item['did'],
			'datum'  => $item['datum'],
			'res'    => $item['res'],
			'sec'    => $item['sec'],
			'file'   => $item['file'],
			'dst'    => $item['dst'],
			'src'    => $item['src'],
			'clid'   => $clid,
			'pid'    => $pid,
			'iduser' => (int)$iduser
		]));

		$upd++;

	}
	else {

		if (strlen($item['src']) < 6 && strlen($item['dst']) < 6) {

			$direct = 'inner';//внутренний звонок

			$phoneSrc = preparePhone($item['src']);//инициатора вызова
			$phoneDst = preparePhone($item['dst']);//цель вызова

		}
		elseif (strlen($item['src']) < 6 && strlen($item['dst']) > 6) {

			$direct = 'outcome';//исходящий звонок

			$phoneSrc = preparePhone($item['src']);//инициатора вызова
			$phoneDst = preparePhone($item['dst']);//цель вызова

			//очищаем номер в dst, т.к. Астериск его сам подставляет
			if ($sip['numout'] && strlen($phoneDst) > 6) {
				$phoneDst = substr( $phoneDst, $numoutLength );
			}

			//$src = getCallerID($phoneDst);
			$src = getxCallerID($phoneDst );

			$phone = $phoneDst;

			$clid   = (int)$src['clid'];
			$pid    = (int)$src['pid'];
			$iduser = (int)getUserID($phoneSrc);

		}
		else {

			$direct = 'income';//входящий звонок

			$phoneSrc = preparePhone($item['src']);//инициатора вызова
			$phoneDst = preparePhone($item['dst']);//цель вызова

			//очищаем номер в dst, т.к. Астериск его сам подставляет
			//if(strlen($phoneSrc)>6) $phoneSrc = substr($phoneSrc,1);
			//if($sip_numout and strlen($phoneDst)>6) $phoneDst = substr($phoneDst,1);
			if ($sip['pfchange'] && strlen($phoneDst) > 6) {
				$phoneDst = substr( $phoneDst, 1 );
			}

			//$src = getCallerID($phoneSrc);
			$src = getxCallerID($phoneSrc );

			//print "$phoneSrc :: $phoneDst\n";
			//print getCallerID($phoneSrc, false, false, true)."\n";

			$phone = $phoneSrc;

			$clid   = (int)$src['clid'];
			$pid    = (int)$src['pid'];
			$iduser = (int)getUserID($phoneDst);

		}

		$db -> query("INSERT INTO ".$sqlname."callhistory SET ?u", arrayNullClean([
			'uid'      => $item['uid'],
			'did'      => $item['did'],
			'phone'    => $phone,
			'direct'   => $direct,
			'datum'    => $item['datum'],
			'clid'     => $clid,
			'pid'      => $pid,
			'iduser'   => $iduser,
			'res'      => $item['res'],
			'sec'      => (int)$item['sec'],
			'file'     => $item['file'],
			'src'      => $phoneSrc,
			'dst'      => $phoneDst,
			'identity' => $identity
		]));
		$new++;

		//добавим запись в историю активностей
		if (($clid > 0 || $pid > 0) && $direct != 'inner' && $putInHistory ) {

			$sort = '';

			if ($clid > 0 && $pid > 0) {
				$sort = "(clid = '$clid' OR pid = '$pid') AND ";
			}

			elseif ($clid > 0 && $pid < 1) {
				$sort = "clid = '$clid' AND ";
			}

			elseif ($clid < 1 && $pid > 0) {
				$sort = "pid = '$pid' AND ";
			}

			//проверим, были ли активности по абоненту
			$all = $db -> getOne("SELECT COUNT(*) AS count FROM ".$sqlname."history WHERE $sort identity = '$identity'");

			if ($direct == 'outcome') {

				$tip = ($all > 0) ? 'исх.2.Звонок' : 'исх.1.Звонок';
				$r = 'Исходящий успешный звонок';

			}
			elseif ($direct == 'income') {

				$tip = 'вх.Звонок';
				$r   = 'Принятый входящий звонок';

			}

			//$tip = 'Запись разговора';

			//добавим запись в историю активности по абоненту
			$db -> query("INSERT INTO ".$sqlname."history SET ?u", arrayNullClean([
				'iduser'   => $iduser,
				'clid'     => $clid,
				'pid'      => $pid,
				'datum'    => $item['datum'],
				'des'      => $r,
				'tip'      => $tip,
				'uid'      => $item['uid'],
				'identity' => $identity
			]));

		}

		/*$mass[$i] = array(
			"uid" => $list[$i]['uid'],
			"phone" => $phone,
			"direct" => $direct,
			"datum" => $list[$i]['datum'],
			"clid" => $clid,
			"pid" => $pid,
			"iduser" => $iduser,
			"res" => $list[$i]['res'],
			"sec" => $list[$i]['sec'],
			"file" => $list[$i]['file'],
			"src" => $phoneSrc,
			"dst" => $phoneDst
		);*/

	}

	$phone = '';

}

toexit:

//очищаем подключение к БД
unset($db);

if ($_REQUEST['printres'] == 'yes') {
	print json_encode_cyr( ["result" => "Успешно.<br>Обновлено записей: $upd<br>Новых записей: $new"] );
}

exit();