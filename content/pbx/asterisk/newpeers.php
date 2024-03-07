<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 * @deprecated
 * See peers.php
 */

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

if ( $iduser1 < 1 ) {
	exit();
}

if ( $action == 'lastcolls' ) {

	$calls = [];

	$rezult = [
		'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i>',
		'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'BUSY'       => '<i class="icon-block-1 broun" title="Занято"></i>',
		'CONGESTION' => '<i class="icon-help red" title="Перегрузка канала"></i>',
		'FAILED'     => '<i class="icon-cancel-squared red" title="Ошибка соединения"></i>'
	];

	$direct = [
		'inner'   => '<i class="icon-arrows-cw smalltxt broun" title="Внутренний"></i>',
		'income'  => '<i class="icon-down-big smalltxt green" title="Входящий"></i>',
		'outcome' => '<i class="icon-up-big smalltxt blue" title="Исходящий"></i>'
	];

	/**
	 * Для показа пользователю только его звонков уберите " or iduser = 0" из запроса
	 */
	$result = $db -> query( "SELECT * FROM {$sqlname}callhistory WHERE id > 0 and direct != 'inner' and (iduser = '$iduser1' /*or iduser = 0*/) and identity = '$identity' GROUP BY SUBSTRING_INDEX(uid, '.', 1) ORDER BY datum DESC LIMIT 5" );
	while ($data = $db -> fetch( $result )) {

		if ( $data['direct'] == 'income' ) {
			$phone = $data['src'];
		}
		elseif ( $data['direct'] == 'outcome' ) {
			$phone = $data['dst'];
		}
		else {
			$phone = $data['dst'];
		}

		$calls[] = [
			"phone"    => formatPhone2( $phone ),
			"clid"     => (int)$data['clid'],
			"client"   => current_client( (int)$data['clid'] ),
			"pid"      => (int)$data['pid'],
			"person"   => current_person( (int)$data['pid'] ),
			"time"     => diffDateTime2( $data['datum'] ),
			"icon"     => strtr( $data['direct'], $direct ),
			"rez"      => strtr( $data['res'], $rezult ),
			"link"     => formatPhoneUrlIcon( $phone, $data['clid'], $data['pid'] ),
			"dst"      => $data['dst'],
			"did"      => $data['did'],
			"ismobile" => (is_mobile( $phone )) ? 1 : "",
			"entry"    => ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ((int)$data['clid'] > 0 || (int)$data['pid'] > 0)) ? 1 : '',
		];

	}

	//print_r($calls);

	print json_encode_cyr( $calls );

	exit();

}

//параметры подключения к серверу
include dirname( __DIR__)."/asterisk/sipparams.php";

/**
 * Важно!
 * Для сохранения настроек переименуй файл simple.settings.json в settings.json
 * Затем пропиши все параметры подключения к базе данных и сохрани
 * Желательно использовать Notepad++ или Akelpad (для Windows)
 * Это сделано для сохранения настроек при обновлении CRM
 */
$allsettings = json_decode( str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( 'settings.json' ) ), true );

$numoutLength = strlen( $sip_numout );

/**
 * Как часто будет происходить запрос к Астериску
 * задаем интервал, через который будем делать новый запрос к Астериску, в противном случае берем ответ из БД -
 * актуально, если в CRM работает несколько пользователей для снижения нагрузки на Астериск
 */
$timeOut = ($allsettings['timeOut'] != '') ? $allsettings['timeOut'] : 2.5;

/**
 * Режим работы Asterisk или версия:
 * simple - для обычного режима и версии ниже 12
 * a12 - для Asterisk v.12, (есть прецендент нормальной работы в версии 13)
 * device - для FreePBX в режиме "device and users"
 */
$mode = ($allsettings['mode'] != '') ? $allsettings['mode'] : 'simple';

//параметры сотрудника
$result_user = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser='".$iduser1."' AND identity = '".$identity."'" );
$title       = $result_user["title"];
$phone_in    = $result_user["phone_in"];//внутренний номер оператора
$mob         = $result_user["mob"];

$config['server']   = $sip_host;
$config['port']     = $sip_port;
$config['username'] = $sip_user;
$config['password'] = $sip_secret;
$config['authtype'] = 'plaintext';
//$config['debug'] = true;
//$config['log'] = true;
$config['logfile'] = $rootpath.'/cash/ami.log';

function parseRespp($num) {

	if ( stripos( $num, '@' ) === false ) {

		//для поиска extention в строке такого вида
		//SIP\/301-00000040
		$chan = explode( "-", $num );

	}
	else {

		//для поиска extention в строке такого вида
		//Local/301@from-queue-00001166;
		$chan = explode( "@", $num );

	}

	$chan1 = explode( "/", $chan[0] );
	$chan  = preparePhone( $chan1[1] );

	return $chan;
}

function getCallerID2($phone, $shownum = false, $translit = false) {

	include "../../../inc/config.php";
	include "../../../inc/dbconnector.php";
	include "../../../inc/settings.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];

	global $clientID;
	global $clientTitle;
	global $personID;
	global $personTitle;
	global $userID;
	global $userTitle;
	global $phoneIN;
	//global $sqlname;
	//global $identity;

	$result_sip   = $db -> getRow( "SELECT * FROM {$sqlname}sip WHERE identity = '".$identity."'" );
	$sip_channel  = $result_sip["sip_channel"];
	$sip_numout   = $result_sip["sip_numout"];
	$sip_pfchange = $result_sip["sip_pfchange"];

	$numoutLength = strlen( $sip_numout );

	if ( strlen( $phone ) < 6 ) {//для внутренних номеров

		$phone = str_replace( $sip_channel, "", $phone );

		$result    = $db -> getRow( "SELECT iduser,title,phone_in FROM {$sqlname}user WHERE ({$sqlname}user.phone_in = '".$phone."' OR replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$phone."%') AND {$sqlname}user.identity = '".$identity."'" );
		$callerID  = $result["title"];
		$userTitle = $result["title"];
		$userID    = $result["iduser"];
		$phoneIN   = $result["phone_in"];

		if ( !$callerID ) {
			$callerID = 'Unknown';
		}
		if ( $shownum != false ) {
			$callerID = ''.$callerID;
		}
		if ( $translit != false ) {
			$callerID = translit( $callerID );
		}

	}
	else {

		if ( strlen( $phone ) == 11 or strlen( $phone ) == 8 ) {
			$phone1 = substr( $phone, 1 );
		}
		else {
			$phone1 = $phone;
		}

		$num = prepareMobPhone( $phone1 );

		//ищем оператора
		$result = $db -> getRow( "SELECT iduser,title,phone_in, phone, mob FROM {$sqlname}user WHERE ({$sqlname}user.phone_in = '".$phone."' OR replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$phone."%' OR replace(replace(replace(replace(replace({$sqlname}user.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$phone."%') AND {$sqlname}user.identity = '".$identity."'" );

		$callerID  = $result["title"];
		$userTitle = $result["title"];
		$userID    = $result["iduser"];
		$userTitle = $result["user"];
		$phoneIN   = $result["phone_in"];

		if ( $callerID != '' ) {
			goto res;
		}

		//ищем контакт
		$res = $db -> getRow( "
			SELECT
				{$sqlname}personcat.person as person,
				{$sqlname}personcat.pid as pid,
				{$sqlname}clientcat.clid as clid,
				{$sqlname}clientcat.title as title,
				{$sqlname}user.iduser as iduser,
				{$sqlname}user.title as user,
				{$sqlname}user.phone_in as phone_in
			FROM {$sqlname}personcat
				LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}personcat.clid = {$sqlname}clientcat.clid
			WHERE ((replace(replace(replace(replace(replace({$sqlname}personcat.tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$num."%') or (replace(replace(replace(replace(replace({$sqlname}personcat.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$num."%')) and {$sqlname}personcat.identity = '".$identity."'
			ORDER by {$sqlname}personcat.pid DESC LIMIT 1
		" );

		$callerID    = $res["person"];
		$personID    = $res["pid"];
		$personTitle = $callerID;
		$clientID    = $res["clid"];
		$clientTitle = $res["title"];
		$userID      = $res["iduser"];
		$userTitle   = $res["user"];
		$phoneIN     = $res["phone_in"];

		if ( $callerID != '' ) {
			goto res;
		}

		//ищем в клиентах
		$res = $db -> getRow( "
			SELECT
			{$sqlname}clientcat.clid as clid,
			{$sqlname}clientcat.pid as pid,
			{$sqlname}clientcat.title as title,
			{$sqlname}user.iduser as iduser,
			{$sqlname}user.title as user,
			{$sqlname}user.phone_in as phone_in
			FROM {$sqlname}clientcat
			LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
			WHERE (replace(replace(replace(replace(replace({$sqlname}clientcat.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$num."%' OR replace(replace(replace(replace(replace({$sqlname}clientcat.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$num."%') and {$sqlname}clientcat.identity = '".$identity."'
			ORDER by {$sqlname}clientcat.clid DESC LIMIT 1
		" );

		$callerID    = $res["title"];
		$clientID    = $res["clid"];
		$personID    = $res["pid"];
		$personTitle = current_person( $personID );
		$clientTitle = $callerID;
		$userID      = $res["iduser"];
		$userTitle   = $res["user"];
		$phoneIN     = $res["phone_in"];

		res:

		if ( !$callerID ) {
			$callerID = "Not found";
		}
		if ( $shownum != false ) {
			$callerID = ''.$callerID.' <'.preparePhone( $phone ).'>';
		}
		if ( $translit != false ) {
			$callerID = translit( $callerID );
		}

	}

	return $callerID;
}

function arrayKeyUpper(&$array) {
	foreach ( $array as &$v ) {
		if ( is_array( $v ) ) {
			arrayKeyUpper( $v );
		}
	}
	$array = array_change_key_case( $array, CASE_UPPER );
}

/**
 * просмотрим все подключенные устройства и выведем список сотрудников и их статус. отключено
 */
if ( $action == 'getPeers' ) {

	$ActionID = 'salesman'.time();

	$ami = new AmiLib( $config );
	if ( $ami -> connect() ) {

		$result = $ami -> sendRequest( "SIPpeers", ["ActionID" => $ActionID] );
		$ami -> disconnect();

		$peers = $result['data'];
		for ( $i = 1; $i < count( $result['data'] ) - 1; $i++ ) {
			$peers[] = $result['data'][ $i ];
		}

	}

	//print_r($peers);

	$ami = new AmiLib( $config );
	if ( $ami -> connect() ) {

		$result = $ami -> sendRequest( "Status" );
		$ami -> disconnect();

	}

	//print_r($result);

	$calls = [];
	foreach ($result['data'] as $row) {

		$sip = [];

		// and $result['data'][$i]['ConnectedLineNum'] == $phone_in) {
		if ( stripos( $row['Channel'], $sip_channel ) !== false ) {

			$sip            = explode( "-", $row['Channel'] );
			$sip2           = explode( "/", $sip[0] );
			$sipp           = $sip2[1];
			$calls[ $sipp ] = $row; //для каждого SIP-канала формируем массив с данными

		}

	}

	//print_r($calls);

	//Выводим панель
	print '<div class="zag paddbott10 white" id="callheader"><b>Список операторов</b></div><div id="peerlist"><ul>';

	foreach ($peers as $peer) {

		if ( strlen( $peer['ObjectName'] ) < 5 ) {

			$xpeer = getxCallerID($peer['ObjectName'])['callerID'];

			switch ($peer['Status']) {
				case 'UNREACHABLE':
				case 'UNKNOWN':
					$title = 'Не доступен';
					$stat  = '&nbsp;<div class="cub bgray rounded" title="'.$title.'"></div>';
					$url   = '<b class="gray" title="Не доступен">'.$xpeer.'</b>';
				break;
				case 'Unmonitored':
					$title = 'Не известно';
					$stat  = '&nbsp;<div class="cub bluebg rounded" title="'.$title.'"></div>';
					$url   = '<b class="gray" title="Не доступен">'.$xpeer.'</b>';
				break;
				default:
					$title = 'Доступен';
					$stat  = '&nbsp;<div class="cub greenbg rounded" title="'.$title.'"></div>';
					if ( $peer['ObjectName'] != $phone_in ) {
						$url = '<a href="#" onClick="showCallWindow(\'api/asterisk/callto.php?action=inicialize&phone=SIP/'.$peer['ObjectName'].'\')" title="Позвонить сотруднику"><span class="green"><b>'.$xpeer.'</b></a>';
					}
					else {
						$url = '<b class="blue" title="За номером закреплены Вы">'.$xpeer.' [ Я ]</b>';
					}
				break;
			}

			if ( $calls[ $peer['ObjectName'] ]['State'] != '' ) {
				switch ($calls[ $peer['ObjectName'] ]['State']) {
					case 'Ringing':
						$stat  = '<i class="icon-phone blue"></i>';
						$ring  = '<i class="icon-down red" title="Вход.звонок"></i>';
						$incom = ' <b class="red">'.$calls[ $peer['ObjectName'] ]['ConnectedLineNum'].'</b>';
					break;
					case 'Up':
						$stat  = '<i class="icon-phone red"></i>';
						$ring  = '<i class="icon-down red" title="Разговор"></i>';
						$incom = ' <b class="red">'.$calls[ $peer['ObjectName'] ]['ConnectedLineNum'].'</b>';
					break;
				}
			}

			if ( $calls[ $peer['ObjectName'] ]['ChannelStateDesc'] != '' ) {
				switch ($calls[ $peer['ObjectName'] ]['ChannelStateDesc']) {
					case 'Ring':
						$stat  = '<i class="icon-phone blue"></i>';
						$ring  = '<i class="icon-up green" title="Исх.звонок"></i>';
						$incom = ' [<b class="red">'.$calls[ $peer['ObjectName'] ]['ConnectedLineNum'].'</b>]';
					break;
					case 'Up':
						$stat  = '<i class="icon-phone red"></i>';
						$ring  = '<i class="icon-up green" title="Разговор"></i>';
						$incom = ' [<b class="red">'.$calls[ $peer['ObjectName'] ]['ConnectedLineNum'].'</b>]';
					break;
				}
			}

			print '
			<li>
				<span style="width:30px;">&nbsp;&nbsp;'.$stat.'</span>&nbsp;&nbsp;<span style="float:none; width:50px;" class="sdiv"><b>'.$peer['ObjectName'].'</b></span>&nbsp;|&nbsp;'.$ring.$url.$incom.'</span>
			</li>';

			$incom = '';
			$url   = '';
			$ring  = '';

		}
		//else $trank[] = $peers[$i]['ObjectName'];
	}

	print '</ul></div><br>';
}

/**
 * мониторим входящие звонки
 */
if ( $action == 'getIncoming' ) {

	$peersa     = [];
	$inpeers    = [];
	$peers      = [];
	$debager    = [];
	$start_time = microtime( true );

	$result = [];

	//goto ext;

	/**
	 * Входящие звонки
	 */

	//берем ответ сервера из кэша или делаем новый запрос в Астериск
	$z = $db -> getRow( "SELECT *,(TIMESTAMPDIFF(SECOND, p_time, CURRENT_TIMESTAMP) ) AS c_diff FROM {$sqlname}incoming WHERE p_identity = '$identity'" );

	if ( (int)$z['p_identity'] > 0 ) {

		if ( (int)$z['c_diff'] < $timeOut ) {

			$result = json_decode( $z['p_text'], true );

		}
		else {

			$start_time2 = microtime( true );

			$ami = new AmiLib( $config );
			if ( $ami -> connect() ) {

				$result = $ami -> sendRequest( "Status" );
				$ami -> disconnect();

				$db -> query( "UPDATE {$sqlname}incoming SET ?u WHERE p_identity = '$identity'", ["p_text" => json_encode( $result )] );

			}

			$end_time2 = microtime( true );

		}

	}
	else {

		$ami = new AmiLib( $config );
		if ( $ami -> connect() ) {

			$result = $ami -> sendRequest( "Status" );
			$ami -> disconnect();

			$db -> query( "INSERT INTO {$sqlname}incoming SET ?u", [
				"p_identity" => $identity,
				"p_text"     => json_encode( $result )
			] );

		}

	}

	if ( !is_array( $result ) ) {
		$result = (array)$result;
	}

	//Переводим ключи массива в верхний регистр
	arrayKeyUpper( $result );
	$result = $result['DATA'];

	//передаем в браузер ответ сервера
	$debager['ringing'] = array2string( $result, '<br>', '&nbsp;&nbsp;' );

	//массив только по входящим звонкам (Ringing) для текущего оператора
	$calls     = [];
	$callexist = [];

	foreach ( $result as $data ) {

		if ( $mode == 'simple' || $mode == 'device' ) {

			//выводим только если идет звонок и линия текущего пользовател
			if ( $data['STATE'] == 'ringing' && parseRespp( $data['CHANNEL'] ) == $phone_in ) {

				//для каждого SIP-канала формируем массив с данными
				$calls[]     = $data;
				$callexist[] = parseRespp( $data['CHANNEL'] );

			}

		}
		/**
		 * Для Asterisk 12
		 */
		elseif ( $mode == 'a12' ) {

			//выводим только если идет звонок и линия текущего пользовател
			if ( $data['CHANNELSTATEDESC'] == 'Ringing' ) {// and $result['data'][ $i ]['CallerIDNum'] == $phone_in ) {

				//для каждого SIP-канала формируем массив с данными
				$calls[] = $data;

			}

		}

	}

	foreach ( $calls as $i => $call ) {

		$clientTitle = '';
		$personTitle = '';
		$callerID    = [];

		$phone  = preparePhone( $call['CONNECTEDLINENUM'] );
		$phone1 = $phone;

		//если внутренний звонок - выходим
		if ( strlen( $phone ) < 9 ) {
			goto s1;
		}

		if ( $sip_numout ) {
			$phone = substr( $phone, $numoutLength );
		}

		/**
		 * Новый запрос
		 */
		$callerID = getxCallerID( $phone );

		if ( !in_array( $phone1, $callexist ) ) {

			$inpeers[] = [
				"clid"     => (int)$callerID['clid'],
				"client"   => $callerID['client'],
				"pid"      => (int)$callerID['pid'],
				"person"   => $callerID['person'],
				"iduser"   => (int)$callerID['iduser'],
				"user"     => $callerID['user'],
				"phone"    => $phone1,
				"unknown"  => ((int)$callerID['clid'] == 0 && (int)$callerID['pid'] == 0 && (int)$callerID['iduser'] == 0) ? 1 : '',
				"ismobile" => (is_mobile( $phone1 )) ? 1 : "",
				"entry"    => ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ((int)$callerID['clid'] > 0 || (int)$callerID['pid'] > 0)) ? 1 : '',
				"isuser"   => ((int)$callerID['clid'] == 0 && (int)$callerID['pid'] == 0 && (int)$callerID['iduser'] > 0) ? 1 : ''
			];

			$callexist[] = $phone1;

		}

		$personID = '';
		$clientID = '';
		$url      = '';

		s1:

	}

	$result = [];

	/**
	 * Текущие разговоры
	 */

	//проверка на существование записи в бд
	$z = $db -> getRow( "SELECT *,(TIMESTAMPDIFF(SECOND, p_time, CURRENT_TIMESTAMP) ) AS c_diff FROM {$sqlname}incoming_channels WHERE p_identity = '$identity'" );

	if ( (int)$z['p_identity'] > 0 ) {

		if ( (int)$z['c_diff'] < $timeOut ) {

			$result = json_decode( (string)$z['p_text'], true );

		}
		else {

			$ami = new AmiLib( $config );

			if ( $ami -> connect() ) {

				$result = $ami -> sendRequest( "CoreShowChannels" );
				$ami -> disconnect();

				$db -> query( "UPDATE {$sqlname}incoming_channels SET ?u WHERE p_identity = '$identity'", ["p_text" => json_encode( $result )] );

			}

		}

	}
	else {

		$start_time3 = microtime( true );

		$ami = new AmiLib( $config );
		if ( $ami -> connect() ) {

			$result = $ami -> sendRequest( "CoreShowChannels" );
			$ami -> disconnect();

			$db -> query( "INSERT INTO {$sqlname}incoming_channels SET ?u", [
				"p_identity" => $identity,
				"p_text"     => json_encode( (array)$result )
			] );

		}

		$end_time3 = microtime( true );

	};

	if ( !is_array( $result ) ) {
		$result = (array)$result;
	}

	//Переводим ключи массива в верхний регистр
	arrayKeyUpper( $result );
	$result = $result['DATA'];

	//передаем в браузер ответ сервера по текущим разговорам
	$debager['talk'] = array2string( $result, '<br>', '&nbsp;&nbsp;' );

	$calls = [];
	foreach ( $result as $data ) {

		/**
		 * для чистого Астериск
		 */
		if ( $mode == 'simple' ) {

			if ( ($data['CONNECTEDLINENUM'] == $phone_in || parseRespp( $data['BRIDGEDCHANNEL'] ) == $phone_in) && $data['CHANNELSTATEDESC'] == 'Up' ) {

				$calls[] = $data; //для каждого SIP-канала формируем массив с данными

			}

		}

		/**
		 * для FreePBX в режиме "device and users" свои условия фильтрации
		 */
		elseif ( $mode == 'device' ) {

			//if( strlen( $result['data'][ $i ]['ConnectedLineNum'] ) > 4 or parseRespp( $result['data'][ $i ]['BridgedChannel'] ) == $phone_in and $result['data'][ $i ]['ChannelStateDesc'] == 'Up' ) {

			if ( parseRespp( $data['CHANNEL'] ) == $phone_in || parseRespp( $data['BRIDGEDCHANNEL'] ) == $phone_in && $data['CHANNELSTATEDESC'] == 'Up' ) {

				$calls[] = $data; //для каждого SIP-канала формируем массив с данными

			}

		}

		/**
		 * Для Asterisk 12, 13 выборка отличается
		 */
		elseif ( $mode == 'a12' ) {

			if ( $data['CHANNELSTATEDESC'] == 'Up' && $data['CALLERIDNUM'] == $phone_in ) {

				$calls[] = $data;//для каждого SIP-канала формируем массив с данными

			}

		}

	}

	$numexist = [];

	foreach ( $calls as $i => $call ) {

		$callerID = [];

		//На разных версиях Астериска
		//приходит либо "CallerIDnum", либо "CallerIDNum"
		$phone  = preparePhone( $call['CALLERIDNUM'] );
		$phone1 = $phone;

		//если внутренний звонок - выходим
		if ( strlen( $phone ) < 9 ) {
			goto s2;
		}

		if ( $sip_numout ) {
			$phone = substr( $phone, $numoutLength );
		}

		$co = (int)$db -> getOne( "SELECT COUNT(*) AS count FROM {$sqlname}callhistory WHERE uid = '$call[UNIQUEID]'" );

		/**
		 * Новый запрос
		 */
		$callerID = getxCallerID( $phone );

		if ( $co < 1 && strlen( $phone_in ) > 7 ) {

			$d = [
				"uid"      => $call['UNIQUEID'],
				"phone"    => $phone,
				"direct"   => 'income',
				"datum"    => current_datumtime(),
				"clid"     => (int)$callerID['clid'],
				"pid"      => (int)$callerID['pid'],
				"iduser"   => (int)$callerID['iduser'],
				"user"     => $callerID['user'],
				"src"      => $phone,
				"dst"      => $phone_in,
				"identity" => $identity
			];

			/**
			 * добавляем звонок в статистику
			 */
			$db -> query( "INSERT INTO {$sqlname}callhistory SET ?u", $d );

			/**
			 * Если надо добавить запись в историю активности по абоненту. Возможно дублирование записей!!!
			 */
			/*
			$h = array("iduser" => $iduser1, "clid" => $callerID['clid'], "pid" => $callerID['pid'], "datum" => current_datumtime(), "des" => 'Входящий звонок. Абонент '.$call['CALLERIDNUM'], 'tip' => 'Вх.Звонок', "uid" => $call['UNIQUEID'], "identity" => $identity);

			$db -> query("insert into {$sqlname}history SET ?u", $h);
			*/

		}

		//исключаем дубли
		if ( !in_array( $phone1, (array)$numexist ) ) {

			/**
			 * посмотрим в заявках, и если есть, то передадим id заявки
			 */
			$lead = $db -> getRow( "SELECT id, title FROM {$sqlname}leads WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' and status NOT IN ('3')" );

			//print "SELECT id FROM {$sqlname}leads WHERE phone = '$phone1' and status NOT IN ('3')";

			$peers[] = [
				"clid"      => (int)$callerID['clid'],
				"client"    => $callerID['client'],
				"pid"       => (int)$callerID['pid'],
				"person"    => $callerID['person'],
				"iduser"    => (int)$callerID['iduser'],
				"user"      => $callerID['user'],
				"phone"     => $phone1,
				"lead"      => (int)$lead['id'],
				"leadtitle" => $lead['title'],
				"unknown"   => ((int)$callerID['clid'] == 0 && (int)$callerID['pid'] == 0 && (int)$callerID['iduser'] == 0 && (int)$lead['id'] == 0) ? 1 : '',
				"ismobile"  => (is_mobile( $phone1 )) ? 1 : "",
				"entry"     => ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && (int)$lead['id'] == 0) ? 1 : '',
				"isuser"    => ((int)$callerID['clid'] == 0 && (int)$callerID['pid'] == 0 && (int)$callerID['iduser'] > 0) ? 1 : ''
			];

			$numexist[] = $phone1;

		}


		s2:

	}

	//смотрим - включен ли модуль Сборщик Лидов
	$coordinator = $db -> getOne( "SELECT coordinator FROM {$sqlname}settings WHERE id = '$identity'" );

	//установка вывода кнопок добавления обращения или лида
	if ( $setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' ) {
		$peersa['entry'] = 1;
	}
	if ( $coordinator > 0 ) {
		$peersa['lead'] = 1;
	}

	$peersa['inpeers'] = $inpeers;
	$peersa['peers']   = $peers;

	ext:

	/*
	 * передаем в браузер ответ сервера о звонках и разговорах
	 * отображаются в окне телефонии в разделе "Ответ сервера"
	 * включаем только на время отладки
	 */
	//$peersa['debager'] = $debager;

	$end_time = microtime( true );

	$peersa['time.end']   = round( ($end_time - $start_time), 3 );
	$peersa['time.end.2'] = round( ($end_time2 - $start_time2), 3 );
	$peersa['time.end.3'] = round( ($end_time3 - $start_time3), 3 );

	print json_encode_cyr( $peersa );

}