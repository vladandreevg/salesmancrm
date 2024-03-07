<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );

$rootpath = realpath( __DIR__.'/../../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$phone  = preparePhone( $_REQUEST['phone'] );
$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$action = $_REQUEST['action'];

$result_user = $db -> getRow( "select phone_in, mob from ".$sqlname."user where iduser='".$iduser1."' and identity = '$identity'" );
$phone_in    = $result_user["phone_in"];//внутренний номер абонента
$mob         = $result_user["mob"];

$result_sip = $db -> getRow( "select user_id, user_key from ".$sqlname."services WHERE folder = 'comtube' and identity = '$identity'" );
$sip_user   = rij_decrypt( $result_sip["user_id"], $skey, $ivc );
$sip_secret = rij_decrypt( $result_sip["user_key"], $skey, $ivc );

function parseText($text): array {

	$result = [];

	$arr = explode( "<br>", $text );

	foreach ( $arr as $ar ) {

		if ( trim( $ar ) != '' ) {
			$rez               = explode( ":", trim( $ar ) );
			$result[ $rez[0] ] = $rez[1];
		}

	}

	return $result;

}

function getAnswer($code): string {

	$res = '';

	switch ($code) {
		case '200':
			$res = 'OK – Операция прошла успешно';
		break;
		case '204':
			$res = 'Ничего не найдено';
		break;
		case '400':
			$res = 'Ошибочные параметры';
		break;
		case '401':
			$res = 'Ошибка авторизации';
		break;
		case '402':
			$res = 'Недостаточно средств. Необходимо пополнить счет';
		break;
		case '403':
			$res = 'Учетная запись заблокирована';
		break;
		case '409':
			$res = 'Вызов уже активен';
		break;
		case '500':
			$res = 'Возникла ошибка сервера';
		break;
		case '501':
			$res = 'Пока не реализовано';
		break;
		case '503':
			$res = 'Невозможно подключиться';
		break;
	}

	return $res;

}

function BuildUrlParamsWithSignature($params, $password): string {

	$url = '';

	ksort( $params );

	if ( !is_array( $params ) ) {
		return $url;
	}

	foreach ( $params as $key => $value ) {
		$url .= $key."=".urlencode( $value )."&";
	}

	$signature = md5( $url."&password=".urlencode( $password ) );
	$url       .= "signature=".$signature;

	return $url;

}

function Send($url, $POST) {
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $POST );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
	curl_setopt( $ch, CURLOPT_URL, $url );

	$result = curl_exec( $ch );

	if ( $result === false )
		print $err = curl_error( $ch );

	return $result;
}

function GetComtubeStatistics($paramIN) {

	include "../../../inc/config.php";

	global $methodResult;
	global $methodError;
	global $methodUID;

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];

	$baseurl = "http://api.comtube.com/scripts/api/cdr.php";

	$params              = [];// Создаем массив и заполняем его параметрами
	$params["username"]  = $sip_user;
	$params["type"]      = "json";
	$params["incl"]      = "1";
	$params["service"]   = "all"; //outcalls = исходящие
	$params["count"]     = "0";
	$params["fromdttm"]  = $paramIN['dateStart'];
	$params["untildttm"] = $paramIN['dateEnd'];

	//print $url = $baseurl."?".http_build_query($params);

	$urlparams = BuildUrlParamsWithSignature( $params, $sip_secret ); // Создаем подпись к параметрам

	$result = Send( $baseurl, $urlparams );

	$result = json_decode( $result, true );

	if ( $result['code'] == '200' ) {

		$methodResult = $result['calls'];
		$methodError  = '';

	}
	else {
		$methodError = "Ошибка: ".$result['code'].". Ответ: ".getAnswer( $result['code'] );// Ошибка соединения с API-сервером
	}
}

function Call2PhoneComtube($method, $paramIN = "") {

	include "../../../inc/config.php";
	require_once "../../../inc/dbconnector.php";

	$sqlname  = $GLOBALS['sqlname'];
	$iduser1  = $GLOBALS['iduser1'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];

	global $methodResult;
	global $methodError;
	global $methodUID;

	$baseurl = "http://api.comtube.com/scripts/api/callback.php";

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];

	$result_user = $db -> getOne( "select phone from ".$sqlname."user where iduser='".$iduser1."' and identity = '".$identity."'" );
	$phone_in    = preparePhone( $result_user );//номер, на который сервис будет перезванивать

	switch ($method) {

		case 'balance':

			$baseurl = 'http://api.comtube.ru/scripts/balance/balance.php';

			$params["username"] = $paramIN['username'];
			$params["type"]     = "html";//тип возвращаемого результата: xml (по умолчанию), html

			$urlparams = BuildUrlParamsWithSignature( $params, $paramIN['secret'] ); // Создаем подпись к параметрам

			$resultt = Send( $baseurl, $urlparams );

		break;

		case 'call':

			$params["action"]   = "call";
			$params["username"] = $sip_user;
			$params["number1"]  = $phone_in;
			$params["number2"]  = $paramIN['phone'];
			$params["clid"]     = $paramIN['clid'];
			$params["pid"]      = $paramIN['pid'];
			$params["attempts"] = "1";//количество попыток
			$params["type"]     = "json";//тип возвращаемого результата: xml (по умолчанию), html, csv, json. Необязательный параметр

			$urlparams = BuildUrlParamsWithSignature( $params, $sip_secret ); // Создаем подпись к параметрам

			$result = Send( $baseurl, $urlparams );

		break;

		case 'terminate':

			$params["action"]   = "terminate";
			$params["username"] = $sip_user;
			$params["uid"]      = $paramIN['uid'];
			$params["type"]     = "json";//тип возвращаемого результата: xml (по умолчанию), html, csv, json. Необязательный параметр

			$urlparams = BuildUrlParamsWithSignature( $params, $sip_secret ); // Создаем подпись к параметрам

			if ( $params["uid"] )
				$result = Send( $baseurl, $urlparams );

		break;

		case 'state':

			$params["action"]   = "state";
			$params["username"] = $sip_user;
			$params["uid"]      = $paramIN['uid'];
			$params["clid"]     = $paramIN['clid'];
			$params["pid"]      = $paramIN['pid'];
			$params["type"]     = "json";//тип возвращаемого результата: xml (по умолчанию), html, csv, json. Необязательный параметр

			$urlparams = BuildUrlParamsWithSignature( $params, $sip_secret ); // Создаем подпись к параметрам

			$result = Send( $baseurl, $urlparams );

		break;
	}

	$result = json_decode( $result, true );// Раскодируем ответ API-сервера в массив

	//print_r($result);

	if ( $result['code'] == '200' ) {

		if ( $method == 'call' ) {

			$methodResult = 'Ожидайте звонка..';
			$methodUID    = $result['uid'];

		}
		if ( $method == 'terminate' ) {

			$methodResult = 'Звонок отменен..';
			$methodUID    = $result['uid'];

		}
		if ( $method == 'state' ) {

			$states = [
				'Idle'     => 'Готов набрать номер',
				'Waiting'  => 'Ожидаем',
				'Calling'  => 'Набираю номер',
				'Answered' => 'Поднята трубка #1',
				'Linked'   => 'Соединено',
				'Playing'  => 'Подан сигнал'
			];

			$methodResult = strtr( $result['clientcallstate'], $states )."<br>";

			$methodUID = $result['uid'];

			$cont = $db -> getOne( "SELECT COUNT(uid) as cont FROM `".$sqlname."callhistory` WHERE uid = '".$methodUID."' and identity = '".$identity."'" );

			if ( $result['clientcallstate'] == 'Linked' and $cont < 1 ) {

				//добавим запись в историю звонков
				$db -> query( "INSERT INTO `".$sqlname."callhistory` (id,uid,phone,direct,datum,clid,pid,iduser,identity) VALUES(null,'".$methodUID."','".$paramIN['phone']."','outcome','".current_datumtime()."','".$params["clid"]."','".$params["pid"]."','".$iduser1."','".$GLOBALS['identity']."')" );

				//добавим запись в историю активностей
				$db -> query( "insert into `".$sqlname."history` (cid,iduser,clid,pid,datum,des,tip,uid,identity) values(null, '".$iduser1."', '".$params["clid"]."', '".$params["pid"]."', '".current_datumtime()."', 'Успешный звонок ч/з Comtube', 'Исх.2.Звонок','".$methodUID."','".$GLOBALS['identity']."')" );

			}

			/*

			Array (
				[state] => 2
				[pbxstate] => 0
				[clientcallstate] => Calling
				[clienttimesec] => 5
				[clientcallcause] => 0
				[abonentcallstate] => Idle
				[abonenttimesec] => 0
				[abonentcallcause] => 0
				[uid] => 4435591f-8e66-4540-a609-949e21062b9
				[number1] => 79223289466
				[number2] => 79223289466
				[padding] =>
				[code] => 200
				[desc] => OK
			)
			*/
		}
	}
	elseif ( $result['code'] == '409' ) {
		$methodResult = "Ответ: ".getAnswer( $result['code'] );
	}
	elseif ( $result['code'] == '410' ) {
		$methodResult = "Звонок закончен";
	}
	else {
		$methodError = "Ошибка: ".$result['code'].". Ответ: ".getAnswer( $result['code'] );// Ошибка соединения с API-сервером
	}

	if ( $method == 'balance' ) {
		$methodResult = $resultt;
	}

	return $methodResult;

	//print_r($result);

	/*
	action - Действие. Возможны следующие варианты:

		call – заказать callback-вызов,
		terminate – отменить callback-вызов (как еще не состоявшийся, так и текущий),
		state – проверить состояние callback-вызова,
		statistics – получить статистику по состоявшимся callback-вызовам,

	Папаметры запроса:

	uid - идентификатор вызова. Используется при проверке статуса, остановке, получении статистики
	number1 - номер телефона инициатора callback-вызова (то есть номер того, куда вызов придет первым)
	number2 - номер телефона вызываемой стороны
	when - Указывает дату/время начала отправки callback-вызова. Формат параметра следующий: YYYY-MM-DD HH:MM:SS. Пример: 2012–12–31 12:00:00. Если параметр не указан, то вызов создается сразу. Для action = send

	attempts - Указывает количество попыток установить соединение с номером number1. По умолчанию равен 1, диапазон: от 1 до 10. Для action = send
	timeshift - Время в секундах, через которое необходимо начать вызов. Диапазон от 0 до 1800 сек, по умолчанию 0.
	maxdur - Максимальная длительность разговора в секундах. Разрешенный диапазон от 0 до 7200 сек, по умолчанию 0. По некоторым направлениям длительность может быть меньше, из-за ограничения операторов связи.
	useivr - Указывает, надо ли проигрывать меню в случае, если вызываемый номер занят/не ответил: 1 - да (по умолчанию), 0 - нет (в этом случае при занятости вызываемой стороны вызов на стороне number1 завершится).
	who - Для action = statistics указывает номер телефона, для которого нужно получить статистику. В поиске участвуют оба номера телефона.
	fromdttm - Для action = statistics указывает дату и время, после которой необходимо получить статистику
	untildttm - Для action = statistics указывает дату и время, до которой необходимо получить статистику
	incl - Для action = getsmses указывает включать или нет дату и время для параметров fromdttm и untildttm (то есть, использовать “<"/">" или "<="/">=")
	count - Для action = statistics указывает количество записей статистики, которые нужно получить
	username – логин пользователя. Обязательный параметр.
	type – тип возвращаемого результата: xml (по умолчанию), html, csv, json. Необязательный параметр.
	signature – подпись запроса. Обязательный параметр. Как создать подпись смотрите раздел "Создание подписи (signature)"
	*/
}

function GetTariffsComtube($method, $paramIN = "") {

	include "../../../inc/config.php";
	require_once "../../../inc/dbconnector.php";

	$sqlname  = $GLOBALS['sqlname'];
	$iduser1  = $GLOBALS['iduser1'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];

	global $methodResult;
	global $methodError;
	global $methodUID;

	$baseurl = "http://api.comtube.com/scripts/api/tariffs.php";

	//$result_sip = $db -> getRow("select user_id, user_key from ".$sqlname."services WHERE folder = 'comtube' and identity = '$identity'");
	//$sip_user     = $result_sip["user_id"];
	//$sip_secret   = $result_sip["user_key"];

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];

	switch ($method) {

		case 'getprice':

			$params["action"]   = "getprice";
			$params["username"] = $sip_user;
			$params["number"]   = $paramIN['phone'];
			$params["type"]     = "json";//тип возвращаемого результата: xml (по умолчанию), html, csv, json. Необязательный параметр

			$urlparams = BuildUrlParamsWithSignature( $params, $sip_secret ); // Создаем подпись к параметрам

			$result = Send( $baseurl, $urlparams );

		break;
	}

	$result = json_decode( $result, true );// Раскодируем ответ API-сервера в массив

	/*
	Array (
		[destinations] => Array (
			[0] => Array (
				[number] => 79223289466
				[place] => Россия, Пермский край, мобильный
				[country_code] => RU
				[price] => 1.4400
				[currency] => RUB
				[validity] => 200
				[freetime] => -1
				[max_calltime] => -1 )
			)
		[total_price] => 1.44
		[total_currency] => RUB
		[enough_money] => 1
		[code] => 200
		[desc] => OK
	)
	*/

	if ( $result['code'] == '200' ) {
		$methodResult = '
		<b>Направление:</b> '.str_replace( " ", "&nbsp;", $result['destinations']['0']['place'] ).'<br>
		<b>Стоимость:</b> '.$result['total_price']." ".$result['total_currency'];
	}
	else {
		$methodError = "<b>Ошибка:</b> ".$result['code'].". <b>Ответ: </b>".getAnswer( $result['code'] );// Ошибка соединения с API-сервером
	}

}

function CheckBalance($method, $paramIN = ""): string {

	global $rootpath;

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."./inc/dbconnector.php";

	$sqlname  = $GLOBALS['sqlname'];
	$iduser1  = $GLOBALS['iduser1'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];

	global $methodResult;
	global $methodError;
	global $methodUID;

	$baseurl = "https://api.comtube.com/scripts/api/callback.php";

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];

	$result_user = $db -> getOne( "select phone from ".$sqlname."user where iduser='".$iduser1."' and identity = '".$identity."'" );
	$phone_in    = preparePhone( $result_user );//номер, на который сервис будет перезванивать

	switch ($method) {

		case 'balance':

			$baseurl = 'http://api.comtube.ru/scripts/balance/balance.php';

			$params["username"] = $paramIN['username'];
			$params["type"]     = "html";//тип возвращаемого результата: xml (по умолчанию), html

			$urlparams = BuildUrlParamsWithSignature( $params, $paramIN['secret'] ); // Создаем подпись к параметрам

			$result = Send( $baseurl, $urlparams );

		break;

	}

	$r = parseText( $result );

	//print_r($r);

	$methodResult = "Баланс: ".$r['balance']." ".$r['crcy'];

	return $methodResult;
}

if ( $action == 'gethistory' ) {

	$zone = $GLOBALS['tzone'];

	//$res = mysql_query("select datum from ".$sqlname."callhistory WHERE identity = '".$identity."' ORDER BY datum DESC LIMIT 1");
	//$last_datum       = mysql_result($res, 0 , "datum");

	if ( $last_datum == '' || $hours > 0 ) {

		if ( !$hours )
			$hours = 48;

		$delta = $hours * 3600;

		//$dateStart = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) - $delta + $zone );
		$dateStart = modifyDatetime( current_datum(), [
			"format" => 'Y-m-d H:i:s',
			"hours"  => -$delta
		] );

	}
	else {

		//$last_datum = date( "Y-m-d H:i:s", date2unix( $last_datum ) );
		$last_datum = modifyDatetime( $last_datum, ["format" => 'Y-m-d H:i:s'] );
		$dateStart  = $last_datum;

	}

	$param['dateStart'] = $dateStart;
	//$param[ 'dateEnd' ]   = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $zone );
	$param['dateEnd'] = modifyDatetime();

	GetComtubeStatistics( $param );

	//обрабатываем данные
	if ( $methodError ) {
		print $methodError;
	}
	else {

		$data = $methodResult;

		$upd = 0;
		$new = 0;

		//exit();

		foreach ( $data as $row ) {

			$phoneSrc = preparePhone( $row['ani'] );//инициатора вызова
			$phoneDst = preparePhone( $row['dnis'] );//цель вызова

			if ( $row['call_type'] == 'OUT' ) {
				$direct    = 'outcome';
				$phoneGoal = $phoneDst;
			}
			else {
				$direct    = 'income';
				$phoneGoal = $phoneSrc;
			}

			$d1 = $row['beg_call']; //начало набора
			$d2 = $row['hangup_on']; //положена трубка
			//$d3 = date("Y-m-d H:i", date_to_unix($data[$i]['answered_on'])); //отвечен

			$d3 = $data[ $i ]['answered_on'];

			if ( (int)$row['duration'] > 0 ) {
				$ress = 'ANSWERED';
			}
			else $ress = 'NO ANSWERED';

			//найдем звонок в базе в указанных пределах звонок с таким же номером и добавим длительность звонка по Билингу оператора
			$id = (int)$db -> getOne( "SELECT id FROM ".$sqlname."callhistory WHERE datum = '$d3' and replace(replace(replace(replace(replace(dst, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') = '".$phoneGoal."' and identity = '".$identity."' ORDER BY datum DESC LIMIT 1" );

			if ( $id > 0 ) {

				$db -> query( "UPDATE ".$sqlname."callhistory SET ?u WHERE id = '".$id."' and identity = '".$identity."'", [
					"datum" => $row['answered_on'],
					"res"   => $ress,
					"sec"   => $row['duration']
				] );

				$upd++;

			}
			else {

				//сначала проверим номер, на который пришел звонок
				//$dst = getCallerID($phoneDst);
				$rez = getxCallerID( $phoneDst );

				if ( (int)$rez['clid'] > 0 || (int)$rez['pid'] > 0 ) {

					$clid   = (int)$rez['clid'];
					$pid    = (int)$rez['pid'];
					$iduser = (int)$rez['iduser'];

				}
				//если в предыдущем действии не найдены данные клиента/контакта, то проверим инициатора звонка
				else {

					//$src = getCallerID($phoneSrc);
					$rez = getxCallerID( $phoneSrc );

					$clid   = (int)$rez['clid'];
					$pid    = (int)$rez['pid'];
					$iduser = (int)$rez['iduser'];

				}

				$db -> query( "INSERT INTO ".$sqlname."callhistory SET ?u", [
					"phone"    => $phoneDst,
					"direct"   => $direct,
					"datum"    => $row['answered_on'],
					"clid"     => $clid,
					"pid"      => $pid,
					"res"      => $ress,
					"sec"      => $row['duration'],
					"src"      => $phoneSrc,
					"dst"      => $phoneDst,
					"identity" => $identity,
					"iduser"   => $iduser
				] );

				$ddr = ($direct == 'income') ? "Вх.Звонок" : "Исх.2.Звонок";

				//$db -> query( "insert into `".$sqlname."history` (cid,iduser,clid,pid,datum,des,tip,identity) values(null, '".$iduser."', '".$clid."', '".$pid."', '".$row['answered_on']."', 'Звонок ч/з Comtube', '$ddr','".$identity."')" );

				addHistorty( [
					"iduser"   => $iduser,
					"clid"     => $clid,
					"pid"      => $pid,
					"datum"    => $row['answered_on'],
					"des"      => 'Звонок ч/з Comtube',
					"tip"      => $ddr,
					"identity" => $identity
				] );

				$new++;

			}

		}

	}

	print 'Выполнено.<br>Обновлено записей: '.$upd.'<br>Добавлено записей: '.$new;

}
if ( $action == 'originate' ) {

	$param['phone'] = $phone;
	$param['clid']  = (int)$_REQUEST['clid'];
	$param['pid']   = (int)$_REQUEST['pid'];

	Call2PhoneComtube( "call", $param );

	if ( $methodError ) {
		print $methodError.'<br>';
	}
	if ( $methodResult ) {
		print '<div style="float:right"><a href="javascript:void(0)" onClick="doTerminate()" title="Прервать звонок"><i class="icon-phone icon-2x red"></i></a>&nbsp;</div><br>';
		print '<div id="state">'.$methodResult.'</div>';
		print '<input type="hidden" name="uid" id="uid" value="'.$methodUID.'">';
	}
	?>
	<script type="text/javascript">

		function doTerminate() {
			url = '/content/pbx/comtube/callto.php?action=terminate&uid=' + $('#uid').val();
			$.post(url, function (data) {
				$('#rezult').html(data);
				$('#state').html('');
				clearInterval(id);
				return false;
			});
		}

		function getState() {
			url = '/content/pbx/comtube/callto.php?action=state&clid=<?=$_REQUEST['clid']?>&pid=<?=$_REQUEST['pid']?>&uid=' + $('#uid').val();
			$.post(url, function (data) {
				$('#state').html(data);
				if (data == 'Звонок закончен<br>') clearInterval(id);
				return false;
			});
		}

		id = setInterval(getState, 1000);

	</script>
	<?php
}
if ( $action == 'getprice' ) {

	$param['phone'] = $phone;

	GetTariffsComtube( "getprice", $param );

	if ( $methodError ) {
		print $methodError.'<br>';
	}
	if ( $methodResult ) {
		print $methodResult.'<br>';
	}

}
if ( $action == 'state' ) {

	$param['uid']  = $_REQUEST['uid'];
	$param['clid'] = (int)$_REQUEST['clid'];
	$param['pid']  = (int)$_REQUEST['pid'];

	Call2PhoneComtube( "state", $param );

	if ( $methodError ) {
		print $methodError.'<br>';
	}
	if ( $methodResult ) {
		print $methodResult.'<br>';
	}

	//запишем в историю звонков
	//mysql_query("INSERT INTO `".$sqlname."callhistory` (id,phone,direct,datum,clid,pid,iduser,identity) VALUES(null,'".$phone."','outcome','".current_datumtime()."','".$clientID."','".$personID."','".$userID."','$identity')");

	if ( $methodError != '' ) {
		?>
		<script type="text/javascript">

			clearInterval(id);

		</script>
		<?php
	}
}
if ( $action == 'terminate' ) {

	$param['uid'] = $_REQUEST['uid'];

	Call2PhoneComtube( "terminate", $param );

	//print "uid=".$_REQUEST['uid']."<br>";

	if ( $methodError ) {
		print $methodError.'<br>';
	}
	if ( $methodResult ) {
		print $methodResult.'<br>';
	}

}
if ( $action == 'inicialize' ) {

	$rez = getxCallerID( (string)$_REQUEST['phone'] );

	//print_r($rez);
	//$callerID = $rez['callerID'];

	if ( $pid > 0 ) {
		$callerID    = current_person( $pid );
		$rez['pid']  = $pid;
		$rez['clid'] = (int)getPersonData( $pid, 'clid' );
	}
	elseif ( $clid > 0 ) {
		$callerID    = current_client( $clid );
		$rez['clid'] = $clid;
	}

	//найдем данные клиента по полученным $clientID и $personID
	if ( $callerID ) {
		$client = $callerID;
	}
	else {
		$client = 'Неизвестный';
	}

	?>
	<div class="zag paddbott10">
		<strong class="white">Набор номера</strong>
		<div class="hid"><i class="icon-cancel-circled white" onclick="hideCallWindow()"></i></div>
	</div>

	<div class="paddbott101">
		<div class="carda"><i class="icon-phone blue"></i> <b><?= $phone ?></b></div>
		<div class="carda"><i class="icon-user-1 blue"></i> <b><?= $client ?></b></div>
	</div>

	<hr>

	<div id="rezult" class="text-center"></div>
	<div id="rezult2" class="text-center"></div>

	<hr>

	<div class="text-center mb10 p5">

		<a href="javascript:void(0)" onclick="addHistory('','<?= $rez['clid'] ?>','<?= $rez['pid'] ?>')" class="button" title="Добавить активность">+ активность</a>

	</div>

	<script>

		$('#rezult').load('content/pbx/comtube/callto.php?action=originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src=/assets/images/loading.gif>');
		$('#rezult2').load('content/pbx/comtube/callto.php?action=getprice&phone=<?=$phone?>').append('<img src=/assets/images/loading.gif>');

	</script>
	<?php
}
?>
