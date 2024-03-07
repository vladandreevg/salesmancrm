<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?
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

$timeoffset = gmtOffset( current_datumtime() ); //смещение временной зоны от GMT, т.к. сервис отдает историю звонков в GMT

$result_user = $db -> getRow( "select phone_in, mob from ".$sqlname."user where iduser='".$iduser1."' and identity = '$identity'" );
$phone_in    = $result_user["phone_in"];//внутренний номер абонента
$mob         = $result_user["mob"];

$result_sip = $db -> getRow( "select user_id, user_key from ".$sqlname."services WHERE folder = 'sipnet' and identity = '$identity'" );
$sip_user   = rij_decrypt( $result_sip["user_id"], $skey, $ivc );
$sip_secret = rij_decrypt( $result_sip["user_key"], $skey, $ivc );

function Send($url, $POST = []) {
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $POST );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt( $ch, CURLOPT_URL, $url );

	$result = curl_exec( $ch );

	if ( $result === false ) {
		print $err = curl_error( $ch );
	}

	curl_close( $ch );

	return $result;
}

function GetStatistics($paramIN): ?array {

	global $methodResult;
	global $methodError;
	global $methodUID;

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];

	$methodError = '';

	$baseurl = "https://balance.sipnet.ru/sip_balance";

	$params              = [];// Создаем массив и заполняем его параметрами
	$params["operation"] = "calls";
	$params["sipuid"]    = $sip_user;
	$params["password"]  = $sip_secret;
	$params["D1"]        = $paramIN['dateStart'];
	$params["D2"]        = $paramIN['dateEnd'];
	$params["format"]    = "2";
	$params["lang"]      = "ru";

	//print $url = $baseurl."?".http_build_query($params);

	$result = Send( $baseurl, $params );

	$result = json_decode( $result, true );

	//print_r($result);

	if ( $result['Result'] == 'true' ) {

		return (array)$result['calls'];

	}
	else {

		$methodError = "Ошибка: ".$result['ResultStr'];

		return null;

	}
}

function Call2Phone($method, $paramIN = "") {

	global $methodResult;
	global $methodError;
	global $methodUID;

	$baseurl = "https://balance.sipnet.ru/sip_balance";

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];
	$identity   = $GLOBALS['identity'];
	$sqlname    = $GLOBALS['sqlname'];
	$iduser1    = $GLOBALS['iduser1'];
	$db         = $GLOBALS['db'];

	$phone_in = $db -> getOne( "select phone_in from ".$sqlname."user where iduser='".$iduser1."' and identity = '".$identity."'" );

	$result = '';
	$params = [];

	switch ($method) {

		case 'call':

			$params["operation"] = "genCall";
			$params["sipuid"]    = $sip_user;
			$params["password"]  = $sip_secret;
			$params["SrcPhone"]  = $phone_in;
			$params["DstPhone"]  = $paramIN['phone'];
			$params["clid"]      = (int)$paramIN['clid'];
			$params["pid"]       = (int)$paramIN['pid'];
			$params["format"]    = "2";
			$params["lang"]      = "ru";

			$result = Send( $baseurl, $params );

		break;

		case 'terminate':

			//не реализовано оператором

		break;

		case 'state':

			//не реалиовано оператором

		break;
	}

	//print_r($params);
	//print $result;

	$result = json_decode( $result, true );

	if ( $result['Result'] == 'true' ) {

		if ( $method == 'call' ) {

			$methodResult = 'Ожидайте звонка..';

			$phoneDst = $params["DstPhone"];
			$phoneSrc = $params["SrcPhone"];

			$db -> query( "insert into ".$sqlname."callhistory (id,phone,direct,datum,clid,pid,src,dst,identity,iduser) values(null,'".$phoneDst."','outcome','".current_datumtime()."','".$params["clid"]."','".$params["pid"]."','".$phoneSrc."','".$phoneDst."','".$identity."','$iduser1')" );

			$db -> query( "insert into `".$sqlname."history` (cid,iduser,clid,pid,datum,des,tip,identity) values(null, '".$iduser1."', '".$params["clid"]."', '".$params["pid"]."', '".current_datumtime()."', 'Звонок ч/з Sipnet', 'Исх.2.Звонок','".$identity."')" );

		}
		//остальное не реализовано оператором
		/*
		if($method == 'terminate') {

			$methodResult = 'Звонок отменен..';
			$methodUID = $result['uid'];

		}
		if($method == 'state') {

			$states = array('Idle' => 'Готов набрать номер', 'Waiting' => 'Ожидаем', 'Calling' => 'Набираю номер', 'Answered' => 'Поднята трубка #1', 'Linked' => 'Соединено', 'Playing' => 'Подан сигнал');

			$methodResult = strtr($result['clientcallstate'], $states)."<br>";

			$methodUID = $result['uid'];

			$res = mysql_query("SELECT COUNT(uid) as cont FROM `".$sqlname."callhistory` WHERE uid = '".$methodUID."' and identity = '".$GLOBALS['identity']."'");
			$cont = mysql_result($res,0,"cont");

			if($result['clientcallstate'] == 'Linked' and $cont < 1){
				//добавим запись в историю звонков
				if(!mysql_query("INSERT INTO `".$sqlname."callhistory` (id,uid,phone,direct,datum,clid,pid,iduser,identity) VALUES(null,'".$methodUID."','".$phone."','outcome','".current_datumtime()."','".$params["clid"]."','".$params["pid"]."','".$iduser1."','".$GLOBALS['identity']."')")) print mysql_error();
				//добавим запись в историю активностей
				if(!mysql_query("insert into `".$sqlname."history` (cid,iduser,clid,pid,datum,des,tip,uid,identity) values(null, '".$iduser1."', '".$params["clid"]."', '".$params["pid"]."', '".current_datumtime()."', 'Успешный звонок ч/з Comtube', 'Исх.2.Звонок','".$methodUID."','".$GLOBALS['identity']."')")) print mysql_error();
			}
		}
		*/

	}
	else {

		$methodError = "Ошибка: ".$result['ErrorStr'];

	}
}

function GetTariffs($method, $paramIN = "") {

	global $methodResult;
	global $methodError;
	global $methodUID;

	$result = '';

	$sip_user   = $GLOBALS['sip_user'];
	$sip_secret = $GLOBALS['sip_secret'];

	$baseurl = "https://balance.sipnet.ru/sip_balance";

	switch ($method) {

		case 'getprice':

			$params["operation"] = "getphoneprice";
			$params["sipuid"]    = $sip_user;
			$params["password"]  = $sip_secret;
			$params["Phone"]     = $paramIN['phone'];
			$params["format"]    = "2";
			$params["lang"]      = "ru";

			$result = Send( $baseurl, $params );

		break;
	}

	$result = json_decode( $result, true );// Раскодируем ответ API-сервера в массив

	if ( $result['Result'] == 'true' ) {

		$methodResult = '<b>Стоимость:</b> '.$result['maxprice']." ".$result['currency'];

	}
	else {

		$methodError = "<b>Ошибка:</b> ".$result['ResultStr'];

	}

}

function CheckBalance($method, $paramIN = ""): string {

	global $methodResult;
	global $methodError;

	$baseurl = "https://balance.sipnet.ru/sip_balance";

	$result = '';

	switch ($method) {

		case 'balance':

			$params["operation"] = "balance";
			$params["sipuid"]    = $paramIN['sipuid'];
			$params["password"]  = $paramIN['password'];
			$params["format"]    = "2";
			$params["lang"]      = "ru";

			//$url = $baseurl."?".http_build_query($params);

			$result = Send( $baseurl, $params );

		break;
	}

	//print $result;

	$result = json_decode( $result, true );

	if ( $result['Result'] == 'true' ) {
		$methodResult = '<b>Баланс:</b> '.$result['balance']." ".$result['currency'];
	}
	else {
		$methodError = "<b>Ошибка:</b> ".$result['ResultStr'];
	}

	return $methodResult;
}

if ( $action == 'gethistory' ) {

	$hours = (int)$_REQUEST['hours'];
	$zone  = $GLOBALS['tzone'];

	//$res = mysql_query("select datum from ".$sqlname."callhistory WHERE res != '' and identity = '".$identity."' ORDER BY datum DESC LIMIT 1");
	//$last_datum       = mysql_result($res, 0 , "datum");

	if ( $last_datum == '' || $hours > 0 ) {

		if ( !$hours )
			$hours = 48;

		$delta     = $hours * 3600;
		$dateStart = date( 'd/m/Y', gmmktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) - $delta + $zone );

	}
	else {

		$last_datum = date( "d/m/Y", date2unix( $last_datum ) );
		$dateStart  = $last_datum;
	}


	$param['dateStart'] = $dateStart;
	$param['dateEnd']   = date( 'd/m/Y', gmmktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $zone );

	$data = (array)GetStatistics( $param );

	foreach ($data as $i => $row) {

		$date  = explode( " ", $row['gmt'] );
		$datum = explode( "/", $date[0] );
		$time  = explode( ":", $date[1] );

		$d = date( 'Y-m-d H:i:s', mktime( $time[0], $time[1], $time[2], $datum[1], $datum[0], $datum[2] ) + $timeoffset * 3600 );

		$data[ $i ]['gmt'] = $d;

	}

	//print_r($data);
	//exit();

	if ( $methodError ) {
		print $methodError;
	}
	else {

		$upd = 0;
		$new = 0;

		foreach ($data as $row) {

			//$phoneSrc = preparePhone($data[$i]['ani']);//инициатора вызова, sipnet не поддерживает
			$phoneDst = preparePhone( $row['phone'] );//цель вызова

			if ( $row['direction'] == 'SIP ID (OUT)' ) {
				$direct    = 'outcome';
			}
			else {
				$direct    = 'income';
			}
			$phoneGoal = $phoneDst;

			//$d1 = $data[$i]['beg_call']; //начало набора
			//$d2 = $data[$i]['hangup_on']; //положена трубка

			$d3 = $row['gmt'];

			if ( (int)$row['duration'] > 0 ) {
				$ress = 'ANSWERED';
			}
			else {
				$ress = 'NO ANSWERED';
			}

			//найдем звонок в базе в указанных пределах звонок с таким же номером и добавим длительность звонка по Билингу оператора
			$id = (int)$db -> getOne( "SELECT id FROM ".$sqlname."callhistory WHERE datum = '$d3' and replace(replace(replace(replace(replace(dst, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '".$phoneGoal."'  and identity = '".$identity."' ORDER BY datum DESC LIMIT 1" );

			if ( $id > 0 ) {

				//$db -> query( "update ".$sqlname."callhistory set datum = '".$d3."', res = '$ress', sec = '".$row['duration']."', file = '".$row['url']."' WHERE id = '".$id."' and identity = '".$identity."'" );

				$db -> query( "UPDATE ".$sqlname."callhistory SET ?u WHERE id = '$id' and identity = '$identity'", [
					"datum" => $d3,
					"res"   => $ress,
					"sec"   => (int)$row['duration']
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
					"datum"    => $d3,
					"clid"     => $clid,
					"pid"      => $pid,
					"res"      => $ress,
					"sec"      => $row['duration'],
					"src"      => $phoneSrc,
					"dst"      => $phoneDst,
					"identity" => $identity,
					"iduser"   => $iduser
				] );

				if ( $direct == 'income' ) {
					$ddr = "Вх.Звонок";
				}
				else {
					$ddr = "Исх.2.Звонок";
				}

				//$db -> query( "insert into `".$sqlname."history` (cid,iduser,clid,pid,datum,des,tip,identity) values(null, '".$iduser."', '".$clid."', '".$pid."', '".$row['gmt']."', 'Звонок ч/з Sipnet', '$ddr','".$identity."')" );

				addHistorty( [
					"iduser"   => $iduser,
					"clid"     => $clid,
					"pid"      => $pid,
					"datum"    => $row['gmt'],
					"des"      => 'Звонок ч/з Sipnet',
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

	Call2Phone( "call", $param );

	if ( $methodError ) {
		print $methodError.'<br>';
	}
	if ( $methodResult ) {
		print '<div id="state">'.$methodResult.'</div>';
		print '<input type="hidden" name="uid" id="uid" value="'.$methodUID.'">';
	}
	?>
	<script type="text/javascript">

	</script>
	<?php
}
if ( $action == 'getprice' ) {

	$param['phone'] = $phone;

	GetTariffs( "getprice", $param );

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

	$rez = getxCallerID( $_REQUEST['phone'] );

	if ( $pid > 0 ) {
		$callerID    = current_person( $pid );
		$rez['pid']  = $pid;
		$rez['clid'] = (int)getPersonData( $pid, 'clid' );
	}
	elseif ( $clid > 0 ) {
		$callerID    = current_client( $clid );
		$rez['clid'] = (int)$clid;
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
		<b class="white">Набор номера</b>
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

	<div class="text-center p5">

		<a href="javascript:void(0)" onclick="addHistory('','<?= $rez['clid'] ?>','<?= $rez['pid'] ?>')" class="button" title="Добавить активность">+ активность</a>

	</div>

	<script>

		$('#rezult').load('content/pbx/sipnet/callto.php?action=originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src=/assets/images/loading.gif>');
		$('#rezult2').load('content/pbx/sipnet/callto.php?action=getprice&phone=<?=$phone?>').append('<img src=/assets/images/loading.gif>');

	</script>
	<?php
}
?>
