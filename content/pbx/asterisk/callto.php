<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

//параметры подключения к серверу
include dirname( __DIR__)."/asterisk/sipparams.php";

//параметры сотрудника
$result_user = $db -> getRow( "SELECT * FROM ".$sqlname."user WHERE iduser = '$iduser1' AND identity = '$identity'" );
$title       = $result_user["title"];
$phone_in    = $result_user["phone_in"];//внутренний номер абонента
$mob         = $result_user["mob"];

/**
 * фильтр, позволяющий менять параметры вызова (О фильтрах - https://salesman.pro/api2/hooks)
 * - pfchange = замена первого символа номера
 * - numout = добавление цифры к номеру, например, если "выход в город" производится по отдельной цифре
 * - channel = канал вызова (SIP, IAX2, ...)
 * - context = контекст вызова
 * - phone_in = номер оператора
 * - phone = вызываемый номер
 * параметры следует изменить по своему усмотрению и вернуть, как в примере
 * $hooks -> add_filter( 'originate', 'pluginname_originate_filter' );
 * function pluginname_originate_filter( $data = [] ) {
 *    // дополняем данные и возвращаем
 *    $data['channel'] = 'SIP23';
 *    return $data;
 * }
 */
// добавим к параметрам внутренний номер сотрудника и вызываемый номер, для возможности манипуляции
$sip['phone_in'] = $phone_in;
$sip['phone']    = $phone;
$sip = $hooks -> apply_filters( "originate", $sip );

// устанавливаем измененные внутренний номер сотрудника и вызываемый номер
$phone_in = $sip['phone_in'];
$phone    = $sip['phone'];

// длина префикса для выхода в город
$numoutLength = strlen( $sip['numout'] );

$config['server']   = $sip['host'];
$config['port']     = $sip['port'];
$config['username'] = $sip['user'];
$config['password'] = $sip['secret'];
$config['authtype'] = 'plaintext';
$config['debug']    = false;
$config['log']      = false;
$config['logfile']  = $rootpath.'/cash/ami.log';

// начало вызова
if ( $action == 'Originate' ) {

	//setcookie("chekk", '');

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = $_REQUEST['phone'];

	//получаем данные абонента
	//$strCallerId = getCallerID($phone, true, false);

	$strCallerId2 = $title."<".$phone_in.">";

	if ( strlen( $phone ) > 5 ) {

		if ( $sip['pfchange'] != '' )
			$phone = $sip['pfchange'].substr( $phone, 1 );

		if ( $sip['numout'] != '' )
			$phone = $sip['numout'].$phone;

	}
	//else $phone = $phone;

	$ActionID = 'salesman'.time();

	$ami = new AmiLib( $config );
	if ( $ami -> connect() ) {

		$result = $ami -> sendRequest( 'Originate', [
			'Channel'  => $sip['channel'].'/'.$phone_in,
			'CallerId' => $strCallerId2,
			'Exten'    => $phone,
			'Context'  => $sip['context'],
			'Priority' => 1,
			'ActionID' => $ActionID,
			'Event'    => 'on',
			'Async'    => 'true'
		] );

		$ami -> disconnect();

	}

	//print_r($result);

	$res = $result['Message'];

	//$ami = new AmiLib($config);
	if ( $ami -> connect() ) {

		$result = $ami -> sendRequest( "Status", [
			"ActionID" => $ActionID,
			"Peer"     => $phone_in
		] );

		$ami -> disconnect();

	}

	//print_r($result);

	$Channel  = $result['data'][1]['Channel'];
	$UniqueID = $result['data'][1]['Uniqueid'];

	if ( $res == "Originate successfully queued" ) {

		print '
		<span class="hangup">
			<a href="javascript:void(0)" onClick="doTerminate()" title="Прервать звонок"><i class="icon-phone red"></i></a>&nbsp;
		</span>
		<input type="hidden" name="UniqueID" id="UniqueID" value="'.$UniqueID.'">
		<input type="hidden" name="ActionID" id="ActionID" value="'.$ActionID.'">
		';

		$status = '<b class="green">Звоним оператору</b>';

	}

	print '<input type="hidden" name="Channel" id="Channel" value="'.$Channel.'">';
	print '<input type="hidden" name="clid" id="clid" value="'.$clid.'">';
	print '<input type="hidden" name="pid" id="pid" value="'.$pid.'">';
	print '<div id="state">'.$status.'</div>';

	if ( $res == 'Originate failed' ) {

		print '<b class="red">Ошибка заказа звонка</b>';
		?>
		<SCRIPT type="text/javascript">
			var astid = getCookie('astid');
			clearInterval(astid);
		</SCRIPT>
		<?php
	}
	?>
	<SCRIPT type="text/javascript">

		astid = setInterval(getState, 1000);
		document.cookie = 'astid=' + astid;
		document.cookie = 'chekk=';

		function doTerminate() {
			hideCallWindow();
			var url = 'content/pbx/asterisk/callto.php?action=Hangup&Channel=' + $('#Channel').val() + '&ActionID=' + $('#ActionID').val();
			var astid = getCookie('astid');
			$.post(url, function (data) {
				$('#state').empty().append(data);
			});
			clearInterval(astid);
		}

		function getState() {
			url = 'content/pbx/asterisk/callto.php?action=State&ActionID=' + $('#ActionID').val() + '&UniqueID=' + $('#UniqueID').val() + '&Channel=' + $('#Channel').val() + '&clid=' + $('#clid').val() + '&pid=' + $('#pid').val() + '&phone=<?=$phone?>';
			$.post(url, function (data) {
				$('#state').html(data);
				return false;
			});
		}
	</SCRIPT>
	<?php

	exit( 0 );

}

//получение статуса обработки события с ActionID
if ( $action == 'State' ) {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = $_REQUEST['phone'];
	$chekk = $_COOKIE['chekk'];

	$result = [];

	$ActionID = $_REQUEST['ActionID'];
	$UniqueID = $_REQUEST['UniqueID'];
	$Channel  = $_REQUEST['Channel'];
	$exten    = $_REQUEST['exten'];

	$ami = new AmiLib( $config );

	if ( $ami -> connect() ) {

		$result = $ami -> sendRequest( "Status", [
			"ActionID" => $ActionID,
			"Peer"     => $phone_in
		] );

		$ami -> disconnect();

	}

	$Response = $result['Response'];
	$Bridge   = $result['data'][1]['BridgedChannel'];
	$Line     = $result['data'][1]['ConnectedLineNum'];

	//линия оператора
	$State  = $result['data'][1]['State'];
	$Status = $result['data'][1]['ChannelStateDesc'];

	//линия абонента
	$State2  = $result['data'][2]['State'];
	$Status2 = $result['data'][2]['ChannelStateDesc'];

	$Extention = $result['data'][1]['Extension'];

	//Звонок оператору
	if ( $State == 'Ringing' ) {

		print '<b class="green">Звоним оператору</b>';

	}

	//отловим событие, когда установлено соединение
	elseif ( $Status == 'Up' && in_array( $State2, ['Ringing', 'Down'] ) ) {

		print '<b class="green">Звоним абоненту</b>';

	}

	//отловим событие, когда установлено соединение
	elseif ( $Status == 'Up' && $State2 == 'Up' ) {

		print '<b class="green">Соединение установлено!</b>';

		//проверим куки. если куки check есть, то значит запись уже добавлена, если нет, то добавляем
		if ( !$chekk ) {

			$resultu = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE uid = '$UniqueID' AND identity = '$identity'" );
			if ( $resultu == 0) {

				if ( $sip['numout'] && strlen( $phone ) > 6 ) {
					$phone = substr( $phone, $numoutLength );
				}

				//добавляем звонок в статистику
				$arg = [
					'uid'      => $UniqueID,
					'phone'    => $Line,
					'direct'   => 'outcome',
					'datum'    => current_datumtime(),
					'clid'     => $clid,
					'pid'      => $pid,
					'iduser'   => $iduser1,
					'res'      => 'ANSWERED',
					'src'      => $phone_in,
					'dst'      => $phone,
					'identity' => $identity
				];
				$db -> query( "INSERT INTO ".$sqlname."callhistory SET ?u", arrayNullClean( $arg ) );

				//проверим, были ли активности по абоненту
				$all = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."history WHERE (clid = '$clid' OR pid = '$pid') AND identity = '$identity'" );

				//$tip = ($all > 0) ? 'исх.2.Звонок' : 'исх.1.Звонок';

				$tip = 'Запись разговора';

				//добавим запись в историю активности по абоненту
				$hist = [
					'iduser'   => $iduser1,
					'clid'     => $clid,
					'pid'      => $pid,
					'datum'    => current_datumtime(),
					'des'      => 'Исходящий успешный звонок',
					'tip'      => $tip,
					'uid'      => $UniqueID,
					'identity' => $identity
				];
				addHistorty( $hist );

			}

		}

		//print "Соединение установлено";
		?>
		<SCRIPT type="text/javascript">
			document.cookie = 'chekk=<?=$ActionID?>';
			var astid = getCookie('astid');
			clearInterval(astid);
			//$('#state').hide();
		</SCRIPT>
		<?php

	}

	//отловим событие, когда соединение завершено
	elseif ( $State == '' && $Status == '' && $State2 == '' && $Status2 == '' ) {

		print '<b class="red">Соединение завершено</b>';
		?>
		<SCRIPT type="text/javascript">
			var astid = getCookie('astid');
			clearInterval(astid);
		</SCRIPT>
		<?php

	}

	//отловим событие, когда абонент занят
	elseif ( $Extention == 's-BUSY' ) {

		print '<b class="red">Абонент занят</b><br>';

		//проверим куки. если куки check есть, то значит запись уже добавлена, если нет, то добавляем
		if ( !$chekk ) {

			//что-то эта команда не работает, поэтому куку отправим ниже javascript`ом
			//setcookie("chekk", $ActionID);
			$resultu = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE uid = '$UniqueID' AND identity = '$identity'" );
			if ( $resultu == 0 ) {

				//добавляем звонок в статистику
				$arg = [
					'uid'      => $UniqueID,
					'phone'    => $Line,
					'direct'   => 'outcome',
					'datum'    => current_datumtime(),
					'clid'     => $clid,
					'pid'      => $pid,
					'iduser'   => $iduser1,
					'res'      => 'BUSY',
					'src'      => $phone_in,
					'dst'      => $phone,
					'identity' => $identity
				];
				$db -> query( "INSERT INTO ".$sqlname."callhistory SET ?u", arrayNullClean( $arg ) );

				//$db -> query("INSERT INTO `".$sqlname."callhistory` (id,uid,phone,direct,datum,clid,pid,iduser,res,src,dst,identity) VALUES(NULL,'".$UniqueID."','".$Line."','outcome','".current_datumtime()."','".$clid."','".$pid."','".$iduser1."','BUSY','".$phone_in."','".$Line."','".$identity."')");

				//проверим, были ли активности по абоненту
				$all = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."history WHERE (clid = '$clid' OR pid = '$pid') AND identity = '$identity'" );

				$tip = 'Запись разговора';

				//добавим запись в историю активности по абоненту
				$hist = [
					'iduser'   => $iduser1,
					'clid'     => $clid,
					'pid'      => $pid,
					'datum'    => current_datumtime(),
					'des'      => 'Исходящий звонок. Абонент занят',
					'tip'      => $tip,
					'uid'      => $UniqueID,
					'identity' => $identity
				];
				addHistorty( $hist );

			}

			?>
			<SCRIPT type="text/javascript">
				document.cookie = 'chekk=<?=$ActionID?>';
			</SCRIPT>
			<?php

		}

		/*$ajam = new Ajam($config);
		$ajam-> doCommand('Hangup', array('Channel' => $Channel, 'ActionID' => $ActionID));

		$ajam->doCommand("Logoff");*/

		//$ami = new AmiLib( $config );
		if ( $ami -> connect() ) {
			$result = $ami -> sendRequest( "Hangup", [
				'Channel'  => $Channel,
				'ActionID' => $ActionID
			] );

			$ami -> disconnect();
		}

	}

	//вывод ответов сервера на экран для отладки
	//print "<div class='text-left'>".array2string($result, "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;")."</div>";

	//print "Finished";

	exit();

}

//завершение соединения Hangup
if ( $action == 'Hangup' ) {

	$ActionID = $_REQUEST['ActionID'];
	$Channel  = $_REQUEST['Channel'];

	$result = [];

	$ami = new AmiLib( $config );

	if ( $ami -> connect() ) {
		$result = $ami -> sendRequest( "Hangup", [
			'Channel'  => $Channel,
			'ActionID' => $ActionID
		] );

		$ami -> disconnect();
	}

	$res = $result['Message'];

	if ( $res == 'Channel Hungup' ) {

		print '<b class="red">Звонок завершен</b>';

		?>
		<SCRIPT type="text/javascript">
			var astid = getCookie('astid');
			clearInterval(astid);
		</SCRIPT>
		<?php

	}

	exit();
}

if ( $action == 'inicialize' ) {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = preparePhone( $_REQUEST['phone'] );

	if ( strlen( $phone ) < 6 ) {
		$sipa = true;
	}

	$rez = getxCallerID( $phone );

	$callerID = $rez['callerID'];

	if ( $pid > 0 ) {

		$rez['person'] = $callerID = current_person( $pid );
		$rez['pid']    = $pid;
		$rez['clid']   = $clid = (int)getPersonData( $pid, 'clid' );

	}
	if ( $clid > 0 ) {

		$rez['client'] = $callerID = current_client( $clid );
		$rez['clid']   = $clid;

	}

	//найдем данные клиента по полученным $clientID и $personID
	$client = ( $callerID ) ? : 'Неизвестный';

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
		<?php if ( $sipa ) { ?>
			, <i class="icon-user-1 white"></i><b class="white"><?= current_user( $rez['iduser'] ) ?></b>
		<?php } ?>
	</div>

	<div class="paddbott100 white">

		<?php if ( (int)$rez['clid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" target="blank" title="Карточка клиента"><i class="icon-commerical-building blue"></i>&nbsp;<?= $rez['client'] ?>
				</a></div>
		<?php } ?>
		<?php if ( (int)$rez['pid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openPerson('<?= $rez['pid'] ?>')" target="blank" title="Карточка контакта"><i class="icon-user-1 blue"></i>&nbsp;<?= $rez['person'] ?>
				</a></div>
		<?php } ?>


	</div>

	<div id="rezult" class="marg3 mt10 mb10 p10 relativ viewdiv">Набор номера...<br></div>

	<div class="text-center btn small paddbott10">

		<a href="javascript:void(0)" onclick="addHistory('','<?= $rez['clid'] ?>','<?= $rez['pid'] ?>')" title="Добавить активность" class="button greenbtn"><i class="icon-clock"></i>&nbsp;Активность</a>
		<?php if ( $setEntry['enShowButtonCall'] == 'yes' and $isEntry == 'on' ) { ?>
			<a href="javascript:void(0)" onClick="editEntry('','edit','<?= $phone ?>');" title="Добавить обращение" class="button redbtn">&nbsp;&nbsp;<i class="icon-plus-circled"></i>&nbsp;&nbsp;Обращение</a>
		<?php } ?>

	</div>

	<script>

		$('#rezult').load('content/pbx/asterisk/callto.php?action=Originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src=/assets/images/loading.gif>');

	</script>
	<?php
}