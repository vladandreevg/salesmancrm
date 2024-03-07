<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Leads;

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

// создадим папку кэша для данных о звонящих
$cacheDir = $rootpath."/cash/pbx";
createDir($cacheDir);

$action = $_REQUEST['action'];

if ( $iduser1 < 1 ) {
	exit();
}

/**
 * Выдает 5 последних звонков для текущего пользователя
 */
if ( $action == 'lastcolls' ) {

	//include "../../inc/func.php";

	$calls = [];

	$rezult = [
		'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i>',
		'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'NOANSWER'   => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
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
	$result = $db -> query( "SELECT * FROM {$sqlname}callhistory WHERE id > 0 AND direct != 'inner' AND (iduser = '$iduser1' /*or iduser = 0*/) AND identity = '$identity' GROUP BY SUBSTRING_INDEX(uid, '.', 1) ORDER BY datum DESC LIMIT 10" );
	while ($data = $db -> fetch( $result )) {

		if ( $data['direct'] == 'income' )
			$phone = $data['src'];
		elseif ( $data['direct'] == 'outcome' )
			$phone = $data['dst'];
		else $phone = $data['dst'];

		$calls[] = [
			"phone"    => formatPhone2( $phone ),
			"clid"     => (int)$data['clid'],
			"client"   => current_client( (int)$data['clid'] ),
			"pid"      => (int)$data['pid'],
			"person"   => current_person( (int)$data['pid'] ),
			"time"     => diffDateTime2( $data['datum'] ),
			"icon"     => strtr( $data['direct'], $direct ),
			"rez"      => strtr( $data['res'], $rezult ),
			"link"     => formatPhoneUrlIcon( $phone, (int)$data['clid'], (int)$data['pid'] ),
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

//include "../../inc/func.php";

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
$result_user = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'" );
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
$config['logfile'] = '../../cash/ami.log';

/**
 * разбор строки для поиска параметра CHANNEL
 *
 * @param $num
 * @return array|string|string[]|null
 */
function getChannel($num) {

	if ( strpos( $num, '@' ) === false ) {

		//для поиска extention в строке такого вида
		//SIP\/301-00000040
		$chan = yexplode( "-", $num, 0 );

		if ( preg_replace( "/[^0-9]/", "", $chan ) === '' ) {
			$chan = yexplode( "-", $num, 1 );
		}

	}
	else {

		//для поиска extention в строке такого вида
		//Local/301@from-queue-00001166;
		$chan = yexplode( "@", $num, 0 );

	}

	return preg_replace( "/[^0-9]/", "", yexplode( "/", $chan, 1 ) );

}

/**
 * Функция обработки ответа от Asterisk
 * Выдает массив состояний линий
 *
 * @param $a
 * @return array
 *   - ring - линии в статусе звонка
 *   - talk - линии в статусе разговора
 */
function parseCall($a): array {

	$calls = [];

	foreach ( $a as $data ) {

		if ( $data['UNIQUEID'] != '' || $data['BRIDGEDUNIQUEID'] != '' ) {

			$to = $from = $did = $direction = $status = '';

			$uid         = yexplode( ".", $data['UNIQUEID'], 0 );
			$channel     = getChannel( $data['CHANNEL'] );
			$bridge      = getChannel( $data['BRIDGEDCHANNEL'] );
			$channelType = yexplode( "/", $data['BRIDGEDCHANNEL'], 0 );
			$app         = $data['APPLICATION'];

			if ( $bridge == "" ) {
				$bridge = $channel;
			}

			$channel = (strlen( $channel ) < strlen( $bridge ) && ($channel != '' || $bridge != '')) ? $channel : $bridge;

			$to   = $data['CALLERIDNUM'];
			$from = $data['CONNECTEDLINENUM'];

			//Звонящий
			if ( $app == "Dial" ) {

				//первое условие - для звонков, которые идут через переадресацию
				//второе условие - для внутренних звонков
				$to = (strlen( $data['CONNECTEDLINENUM'] ) > 6 || strlen( $data['CONNECTEDLINENUM'] ) == strlen( $data['CALLERIDNUM'] )) ? $data['CONNECTEDLINENUM'] : $data['CALLERIDNUM'];

				$from = $channel;

				//для внутренних звонков
				if ( strlen( $data['CONNECTEDLINENUM'] ) < 6 && strlen( $data['CONNECTEDLINENUM'] ) == strlen( $data['CALLERIDNUM'] ) ) {

					$to      = $data['CONNECTEDLINENUM'];
					$from    = $data['CALLERIDNUM'];
					$channel = $from;

				}

				$direction = "outcome";

				$did = $data['CALLERIDNUM'];
				//$status    = ($channelType != '' && $data['CHANNELSTATEDESC'] != '') ? $data['CHANNELSTATEDESC'] : "Ring";
				$status = $data['CHANNELSTATEDESC'];

				if ( $did != '' && $to == '' )
					$to = $did;

			}
			//Принимающий
			elseif ( $app == "AppDial" ) {

				$to = ((strlen( $channel ) < strlen( $bridge ) && $channel != '') || $bridge != '') ? $channel : $bridge;

				if ( (strlen( $data['CONNECTEDLINENUM'] ) < strlen( $to ) && $data['CONNECTEDLINENUM'] != '') || $to != '' ) {

					//закрыто, т.к. почему-то при входящем с мобильного
					//на конкретный внутренний номер не правильно работает
					//$to      = $data['CONNECTEDLINENUM'];
					$channel = $to;

				}

				//первое условие - для звонков, которые идут через переадресацию
				//второе условие - для внутренних звонков
				$from = (strlen( $data['CONNECTEDLINENUM'] ) > 6 || strlen( $data['CONNECTEDLINENUM'] ) == strlen( $data['CALLERIDNUM'] )) ? $data['CONNECTEDLINENUM'] : $data['CALLERIDNUM'];

				//для внутренних звонков
				if ( strlen( $data['CONNECTEDLINENUM'] ) < 6 && strlen( $data['CONNECTEDLINENUM'] ) == strlen( $data['CALLERIDNUM'] ) ) {

					$to      = $data['CALLERIDNUM'];
					$from    = $data['CONNECTEDLINENUM'];
					$channel = $to;

				}

				$direction = "income";
				$did       = $data['CONNECTEDLINENUM'];
				$status    = $data['CHANNELSTATEDESC'];

			}
			//Переадресация
			elseif ( $app == "Transferred Call" ) {

				$to = $bridge;

				if ( (strlen( $data['CONNECTEDLINENUM'] ) < strlen( $to ) && $data['CONNECTEDLINENUM'] != '') || $to != '' ) {

					$to      = $data['CONNECTEDLINENUM'];
					$channel = $to;

				}

				//первое условие - для звонков, которые идут через переадресацию
				//второе условие - для внутренних звонков
				$from = (strlen( $data['CONNECTEDLINENUM'] ) > 6 || strlen( $data['CONNECTEDLINENUM'] ) == strlen( $data['CALLERIDNUM'] )) ? $data['CONNECTEDLINENUM'] : $data['CALLERIDNUM'];

				$direction = "income";
				$did       = $data['CONNECTEDLINENUM'];
				$status    = $data['CHANNELSTATEDESC'];

			}
			//else goto ext;

			//для каждого SIP-канала формируем массив с данными
			//если он не пуст и не замкнут сам на себя (это Астериск, Карл!)
			if ( $channel != "" /*&& $to != $from*/ ) {
				$calls[ $channel ][] = [
					"CHANNEL"          => $channel,
					"TYPE"             => $channelType,
					"FROM"             => $from,
					"TO"               => $to,
					"DID"              => $did,
					"DIRECTION"        => $direction,
					"STATUS"           => $status,
					"CALLERIDNUM"      => $data['CALLERIDNUM'],
					"CONNECTEDLINENUM" => $data['CONNECTEDLINENUM'],
					"BRIDGEDCHANNEL"   => $bridge,
					"CHANNELSTATEDESC" => $data['CHANNELSTATEDESC'],
					"STATE"            => $data['STATE'],
					"APP"              => $app,
					"UNIQUEID"         => $data['UNIQUEID']
				];
			}

			ext:

		}

	}

	//print array2string($calls, "<br>", str_repeat("&nbsp;", 2));

	//массив с обработанными данными
	$exist = [
		'ring' => [],
		'talk' => []
	];

	//массив по типам направлений
	$peers = [
		'ring' => [],
		'talk' => []
	];

	foreach ( $calls as $channel => $call ) {

		foreach ( $call as $item ) {

			if(!empty($item['CHANNELSTATEDESC'])){
				$item['STATUS'] = $item['CHANNELSTATEDESC'];
			}

			$operator = ($item['DIRECTION'] == 'outcome') ? $item['FROM'] : $item['TO'];
			$abonent  = ($item['DIRECTION'] == 'outcome') ? $item['TO'] : $item['FROM'];

			if ( !in_array( $operator, (array)$exist['ring'] ) && in_array( $item['STATUS'], [
					'Ring',
					'Ringing',
					'Down'
				] ) ) {
				//if (!in_array($operator, $exist['ring']) && $item['STATUS'] != 'Up' && $item['STATUS'] != '') {

				$peers['ring'][] = [
					"CHANNEL"   => $item['CHANNEL'],
					"OPERATOR"  => $operator,
					"ABONENT"   => $abonent,
					"DIRECTION" => $item['DIRECTION'],
					"UNIQUEID"  => $item['UNIQUEID']
				];

				$exist['ring'][] = $operator;

				//print $item['DIRECTION'].": ".$item['FROM']." => ".$item['TO']." [ ".$item['STATUS']." ]<br>";

			}
			elseif ( !in_array( $operator, (array)$exist['talk'] ) && $item['STATUS'] == 'Up' ) {

				$peers['talk'][] = [
					"CHANNEL"   => $item['CHANNEL'],
					"OPERATOR"  => $operator,
					"ABONENT"   => $abonent,
					"DIRECTION" => $item['DIRECTION'],
					"UNIQUEID"  => $item['UNIQUEID']
				];

				$exist['talk'][] = $channel;

				//print $item['DIRECTION'].": ".$item['FROM']." => ".$item['TO']." [ ".$item['STATUS']." ]<br>";

			}

		}

	}

	if ( empty( $peers['ring'] ) ) {
		unset( $peers['ring'] );
	}
	if ( empty( $peers['talk'] ) ) {
		unset( $peers['talk'] );
	}

	return $peers;

}

/**
 * Функция обработки ответа от Asterisk v.13++ ( тестировалось на v.20 )
 * Выдает массив состояний линий
 *
 * @param $a
 * @return array
 *   - ring - линии в статусе звонка
 *   - talk - линии в статусе разговора
 */
function parseCallMod($a): array {

	$list = [];

	// распределим данные по парам по полю Uniqueid
	foreach ( $a as $item ) {

		// отсекаем мусор
		if ( empty( $item['UNIQUEID'] ) && empty( $item['BRIDGEID'] ) ){
			continue;
		}

		// не соединенные каналы
		if ( !empty( $item['UNIQUEID'] ) && empty( $item['BRIDGEID'] ) ) {

			$uid            = yexplode( ".", $item['UNIQUEID'], 0 );
			$list[ $uid ][] = $item;

		}
		else{

			$list[ $item['BRIDGEID'] ][] = $item;

		}

	}

	$peers = [];

	foreach ( $list as $item ) {

		$direction = "inner";
		$channel = 'NONAME';

		// отбираем только пары
		if ( count( $item ) == 2 ) {

			$from = getChannel( $item[0]['CHANNEL'] );
			$to   = getChannel( $item[1]['CHANNEL'] );

			if( !empty($item[0]['CALLERIDNUM']) ){
				$from = $item[0]['CALLERIDNUM'];
			}
			if( !empty($item[1]['CONNECTEDLINENUM']) && $item[1]['CONNECTEDLINENUM'] != '<unknown>' ){
				$to = $item[1]['CONNECTEDLINENUM'];
			}

			// для каналов типа pbx1, smg1016
			if( strlen( $from ) == 1 || strlen( $to ) == 1 ){
				continue;
			}

			if( strlen( $from ) < 7 && strlen( $to ) > 7 ){
				$direction = "outcome";
				$channel = $from;
			}
			elseif( strlen( $from ) > 7 && strlen( $to ) < 7 ){
				$direction = "income";
				$channel = $to;
			}

			$status    = $item[0]['CHANNELSTATEDESC'] == "Up" && $item[1]['CHANNELSTATEDESC'] == "Up" ? "talk" : "ring";

			if( !empty($item['BRIDGEID']) ){
				$status = "talk";
			}

			/*$calls[] = [
				"from"      => $from,
				"to"        => $to,
				"direction" => $direction,
				"status"    => $status
			];*/

			$operator = ($direction == 'outcome') ? $from : $to;
			$abonent  = ($direction == 'outcome') ? $to : $from;

			$peers[$status][] = [
				"CHANNEL"     => $channel,
				"OPERATOR"    => $operator,
				"ABONENT"     => $abonent,
				"DIRECTION"   => $direction,
				"UNIQUEID"    => $item[0]['UNIQUEID'],
				//"STATUS"      => $item[0]['STATUS'],
				//"APPLICATION" => $item[0]['APPLICATION'],
			];

		}

	}

	return $peers;

}

/**
 * перевод всех ключей массива в верхний регистр
 *
 * @param $array
 */
function arrayKeyUpper(&$array) {
	foreach ( $array as &$v ) {
		if ( is_array( $v ) ) {
			arrayKeyUpper( $v );
		}
	}
	$array = array_change_key_case( $array, CASE_UPPER );
}

/**
 * мониторим входящие звонки
 */
if ( $action == 'getIncoming' ) {

	$peersa = [
		"inpeers" => [],
		"peers"   => []
	];
	$callExist = [
		"inpeers" => [],
		"peers"   => []
	];
	$result = [];
	$istest = false;

	$start_time = microtime( true );

	/**
	 * Звонки
	 */

	//если для отладки есть выносной массив
	//то подключим его
	if ( file_exists( $rootpath."/developer/asterisk.debugger/test.php" ) ) {

		include $rootpath."/developer/asterisk.debugger/test.php";
		$result['data'] = $res['income'];

		goto a1;

	}

	//берем ответ сервера из кэша или делаем новый запрос в Астериск
	$z = $db -> getRow( "SELECT *,(TIMESTAMPDIFF(SECOND, p_time, CURRENT_TIMESTAMP) ) AS c_diff FROM {$sqlname}incoming WHERE p_identity = '$identity'" );

	$ami = new AmiLib( $config );
	if ( (int)$z['p_identity'] > 0 ) {

		if ( (int)$z['c_diff'] < $timeOut ) {

			$result = json_decode( $z['p_text'], true );

		}
		else {

			$start_time2 = microtime( true );

			if ( $ami -> connect() ) {

				$result = $ami -> sendRequest( "CoreShowChannels" );
				$ami -> disconnect();

			}

			$end_time2 = microtime( true );

			$db -> query( "UPDATE {$sqlname}incoming SET ?u WHERE p_identity = '$identity'", ["p_text" => json_encode( $result )] );

		}

	}
	else {

		//$ami = new AmiLib( $config );
		if ( $ami -> connect() ) {

			$result = $ami -> sendRequest( "CoreShowChannels" );
			$ami -> disconnect();

		}

		$db -> query( "INSERT INTO {$sqlname}incoming SET ?u", [
			"p_identity" => $identity,
			"p_text"     => json_encode( (array)$result )
		] );

	}

	a1:

	if ( !is_array( $result ) ) {
		$result = (array)$result;
	}

	//Переводим ключи массива в верхний регистр
	arrayKeyUpper( $result );

	//выбираем массив с данными
	$result = $result['DATA'];

	//обрабатываем результат запроса
	$calls = $mode != 'a20' ? parseCall( $result ) : parseCallMod($result);

	//передаем в браузер ответ сервера по текущим разговорам
	$debager = array2string( $result, "<br>", str_repeat( "&nbsp;", 5 ) );

	//обходим ответ АТС
	//и формируем данные
	foreach ( $calls as $tip => $item ) {

		foreach ( $item as $call ) {

			//if ($call['DIRECTION'] == 'outcome') goto s2;

			$callerID = [];

			$phone_abonent  = preparePhone( $call['ABONENT'] );
			$phone_operator = (strlen( $call['OPERATOR'] ) > 6) ? substr( $call['OPERATOR'], 1 ) : $call['OPERATOR'];

			$operator = $db -> getRow( "SELECT iduser, title, avatar FROM {$sqlname}user WHERE phone_in = '$phone_operator' OR mob = '$phone_operator' AND identity = '$identity'" );

			//если внутренний звонок - выходим
			/*if ( strlen( $phone_abonent ) < 9 ) {
				continue;
			}*/
			if ( $call['DIRECTION'] == 'inner' ) {
				continue;
			}

			//если звонок НЕ текущему пользователю, то выходим
			$isCurrent = !(($phone_operator != $phone_in && $phone_operator != preparePhone( $mob )));
			$isDevice  = !(($mode == 'device' && $call['DIRECTION'] == 'income'));

			if ( //отсекаем чужие звонки
				(int)$iduser1 != 21 && !$isCurrent &&
				(
					//для режима device&users показываем всем только входящие и в режиме звонка
					$mode != 'device' || ($isDevice && $tip != 'ring')
				)
			) {
				continue;
			}

			if ( $sip_numout ) {
				$phone_abonent = substr( $phone_abonent, $numoutLength );
			}

			/**
			 * Работаем с кэшем абонентов
			 * если файл кэша не существует или существует, но старше 5 минут
			 * то запрашиваем из базы
			 */
			$casheFile = $cacheDir."/$phone_abonent.json";
			if(!file_exists($casheFile) || diffDateTimeSeq( date( 'Y-m-d H:i:s', filemtime($casheFile))) > 150 ) {

				/**
				 * Получение данных абонента
				 */
				$callerID = getxCallerID( $phone_abonent );
				file_put_contents($casheFile, json_encode_cyr($callerID));

			}
			else{

				$callerID = json_decode(file_get_contents($casheFile), true);

			}

			/**
			 * Добавление в историю звонков
			 */
			if ( $call['UNIQUEID'] != '' && $call['DIRECTION'] == 'income' ) {

				$co = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}callhistory WHERE uid = '$call[UNIQUEID]' AND identity = '$identity'" );

				if ( $co < 1 && strlen( $phone_in ) > 7 ) {

					$d = [
						"uid"      => $call['UNIQUEID'],
						"phone"    => $phone_abonent,
						"direct"   => 'income',
						"datum"    => current_datumtime(),
						"clid"     => (int)$callerID['clid'],
						"pid"      => (int)$callerID['pid'],
						"iduser"   => (int)$callerID['iduser'],
						"user"     => $callerID['user'],
						"src"      => $phone_abonent,
						"dst"      => $phone_in,
						"identity" => $identity
					];

					/**
					 * добавляем звонок в статистику
					 */
					//$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $d);

					/**
					 * Если надо добавить запись в историю активности по абоненту, то активируйте блок
					 * Возможно дублирование записей!!!
					 */
					$h = [
						"iduser"   => $iduser1,
						"clid"     => (int)$callerID['clid'],
						"pid"      => (int)$callerID['pid'],
						"datum"    => current_datumtime(),
						"des"      => 'Входящий звонок. Абонент '.$call['CALLERIDNUM'],
						'tip'      => 'Вх.Звонок',
						"uid"      => $call['UNIQUEID'],
						"identity" => $identity
					];

					//$db -> query("insert into {$sqlname}history SET ?u", $h);

				}

			}

			//разделяем на входящие и разговоры
			$peerTip = ($tip == 'ring') ? 'inpeers' : 'peers';

			$icon      = ($call['DIRECTION'] == 'outcome') ? '<i class="icon-up-outline green"></i>' : '<i class="icon-down-outline red"></i>';
			$direction = ($call['DIRECTION'] == 'outcome') ? 'исходящий' : 'входящий';

			//Если модуль "Сборщик заявок" активен
			if ( $modLeadActive == 'on' ) {

				/**
				 * ВНИМАНИЕ!!! Эта опция требует времени на выполнение, поэтому возможна задержка всплытия окна телефонии
				 */

				if ( $settingsApp['sipOptions']['autoCreateLead'] == 'yes' ) {

					/**
					 * Создадим заявку, если опция активна
					 */
					$ilead = new Leads();
					$rez   = $ilead -> autoLeadCreate( $phone_abonent );

				}

				/**
				 * посмотрим в заявках, и если есть, то передадим id заявки
				 */
				$lead = ($tip != 'ring') ? $db -> getRow( "SELECT id, title FROM {$sqlname}leads WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone_abonent%' and status NOT IN ('3')" ) : '';

			}

			if ( !in_array( $phone_abonent, $callExist[ $peerTip ] ) ) {

				$peersa[ $peerTip ][] = [
					"clid"             => (int)$callerID['clid'],
					"client"           => $callerID['client'],
					"pid"              => (int)$callerID['pid'],
					"person"           => $callerID['person'],
					"iduser"           => (int)$callerID['iduser'],
					"ophone"           => $phone_operator,
					"operator"         => $operator['title'],
					"avatar"           => ($operator['avatar'] != '') ? "/cash/avatars/".$operator['avatar'] : "/assets/images/noavatar.png",
					"user"             => $callerID['user'],
					"phone"            => $phone_abonent.((int)$iduser1 == 21 ? " [ ext ".$call['CHANNEL']." ]" : ""),
					"ConnectedLineNum" => $call['CONNECTEDLINENUM'],
					"Channel"          => $call['CHANNEL'],
					"icon"             => $icon,
					"direction"        => $direction,
					"lead"             => $lead['id'] + 0,
					"leadtitle"        => $lead['title'],
					"unknown"          => ((int)$callerID['clid'] == 0 && (int)$callerID['pid'] == 0 && (int)$callerID['iduser'] == 0 && (int)$lead['id'] == 0) ? 1 : '',
					"ismobile"         => (is_mobile( $phone_abonent )) ? 1 : "",
					"isuser"           => ((int)$callerID['clid'] == 0 && (int)$callerID['pid'] == 0 && (int)$callerID['iduser'] > 0) ? 1 : ''
				];

				$callExist[ $peerTip ][] = $phone_abonent;

			}

		}

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

	/*
	 * передаем в браузер ответ сервера о звонках и разговорах
	 * отображаются в окне телефонии в разделе "Ответ сервера"
	 * включаем только на время отладки
	 */
	//$peersa['debager'] = $debager;

	$end_time = microtime( true );

	$peersa['time.end']   = round( ($end_time - $start_time), 3 );
	$peersa['time.end.2'] = round( ($end_time2 - $start_time2), 3 );
	//$peersa['time.end.3'] = round(($end_time3 - $start_time3), 3);

	print json_encode_cyr( $peersa );

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
		for ( $i = 1; $i < count( (array)$result['data'] ) - 1; $i++ ) {
			$peers[] = $result['data'][ $i ];
		}

		$result = $ami -> sendRequest( "Status" );
		$ami -> disconnect();

	}

	//print_r($peers);
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

			switch ($peers[ $i ]['Status']) {
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
						$url = '<a href="javascript:void(0)" onclick="showCallWindow(\'api/asterisk/callto.php?action=inicialize&phone=SIP/'.$peer['ObjectName'].'\')" title="Позвонить сотруднику"><span class="green"><b>'.$xpeer.'</b></a>';
					}
					else {
						$url = '<b class="blue" title="За номером закреплены Вы">'.$xpeer.' [ Я ]</b>';
					}
				break;
			}

			if ( $calls[ $peer['ObjectName'] ]['State'] != '' ) {

				switch ($calls[ $peer['ObjectName'] ]['State']) {
					case 'Ringing':
					case 'Ring':
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