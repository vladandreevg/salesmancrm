<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2016.10       */
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
include dirname( __DIR__)."/mango/sipparams.php";
include dirname( __DIR__)."/mango/mfunc.php";

//проверим наличие буферной таблицы и создадим её, если нет
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."mango_log'" );
if ( $da == 0 ) {

	$db -> query( "
		CREATE TABLE `".$sqlname."mango_log` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` timestamp  NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, 
			`command_id` varchar(255) NOT NULL COMMENT 'Идентификатор команды', 
			`call_id` varchar(255) NOT NULL COMMENT 'Идентификатор звонка', 
			`extension` varchar(10) NOT NULL COMMENT 'Номер сотрудника', 
			`phone` varchar(16) NOT NULL COMMENT 'Номер абонента', 
			`content` text NOT NULL, 
			`call_state` varchar(255) NOT NULL COMMENT 'Статус звонка', 
			`type` varchar(255) NOT NULL COMMENT 'Тип записи (звонок или входящий)', 
			`clid` INT(20) NOT NULL, 
			`pid` INT(20) NOT NULL, 
			`identity` int(30) DEFAULT '1' NOT NULL, 
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`), 
			INDEX `command_id` (`command_id`), 
			INDEX `extension` (`extension`)
		) 
		COMMENT='Лог отправленных уведомлений' 
		COLLATE='utf8_general_ci'
	" );

}

//параметры сотрудника
$res      = $db -> getRow( "select * from ".$sqlname."user where iduser='".$iduser1."' and identity = '".$identity."'" );
$title    = $res["title"];
$phone_in = $res["phone_in"];//внутренний номер абонента
$mob      = $res["mob"];

if ( $action == 'Originate' ) {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = $_REQUEST['phone'];

	//получаем данные абонента
	//$strCallerId = getCallerID( $phone, true, false );

	$result = doMethod( 'call', [
		"api_key"  => $api_key,
		"api_salt" => $api_salt,
		"phone_in" => $phone_in,
		"phone"    => $phone,
		"clid"     => $clid,
		"pid"      => $pid
	] );

	//проверим ответ
	$status = $db -> getRow( "select * from ".$sqlname."mango_log where command_id = '".$result['actionID']."' and extension = '$phone_in' and identity = '$identity'" );

	print '<input type="hidden" name="actionID" id="actionID" value="'.$result['actionID'].'">';
	print '<input type="hidden" name="call_id" id="call_id" value="'.$status['call_id'].'">';
	print '<input type="hidden" name="clid" id="clid" value="'.$clid.'">';
	print '<input type="hidden" name="pid" id="pid" value="'.$pid.'">';

	if ( $status['call_id'] != '' ) {
		print '<div style="float:right" class="terminate"><a href="javascript:void(0)" onClick="doTerminate()" title="Прервать звонок"><i class="icon-phone icon-2x red"></i></a>&nbsp;</div><br>';
	}

	if ( $result['code'] != '1000' ) {
		$state = '<b class="red">Ошибка:</b> '.$result['message'];
	}
	else {
		$state = '<b class="green">Ожидайте звонка</b>';
	}

	print '<div class="state">'.$state.'</div>';
	?>
	<SCRIPT type="text/javascript">

		var call_id = $('#call_id').val();

		<?php if($result['code'] == '1000'){ ?>
		var mango = setInterval(getState, 1000);
		<?php } ?>

		function doTerminate() {

			var url = 'content/pbx/mango/callto.php?action=Hangup&call_id=' + $('#call_id').val();
			$.post(url, function (data) {

				$('.state').empty().append(data.message);

				if (data.state === 'disconnected') {

					clearInterval(mango);
					$('.state').append('<br>' + data.content);
					$('.terminate').remove();

				}

				clearInterval(mango);
				hideCallWindow();

			}, 'json');

		}

		function getState() {

			var url = 'content/pbx/mango/callto.php?action=State&actionID=<?=$result['actionID']?>&call_id=' + $('#call_id').val() + '&clid=' + $('#clid').val() + '&pid=' + $('#pid').val() + '&phone=<?=$phone?>';

			$.post(url, function (data) {

				$('.state').html(data.message);
				$('#call_id').val(data.callid);

				if (data.state === 'disconnected') {

					clearInterval(mango);
					$('.state').append('<br>' + data.content);
					$('.terminate').remove();

				}

			}, "json");
		}

	</SCRIPT>
	<?php
	exit( 0 );
}
if ( $action == 'State' ) {//получение статуса обработки события с ActionID

	$clid       = (int)$_REQUEST['clid'];
	$pid        = (int)$_REQUEST['pid'];
	$phone      = $_REQUEST['phone'];
	$command_id = $_REQUEST['actionID'];
	$result     = [];

	$status = $db -> getRow( "select * from ".$sqlname."mango_log where command_id = '".$command_id."' and extension = '$phone_in' and identity = '$identity'" );

	$resp = json_decode( (string)$status['content'], true );

	$result['state'] = '';

	//отловим событие, когда установлено соединение
	if ( $resp['call_state'] == 'Appeared' ) {

		if ( strlen( $resp['from']['extention'] ) < 6 && strlen( $resp['to']['extention'] ) < 6 ) {

			$result['message'] = '<b class="green">Звонок оператору</b>';

		}

	}
	if ( $resp['call_state'] == 'Connected' ) {

		$result['message'] = '<b class="green">Соединение установлено!</b>';

	}
	if ( $resp['call_state'] == 'OnHold' ) {

		$result['message'] = '<b class="green">Соединение на удержании!</b>';

	}
	if ( $resp['call_state'] == 'Disconnected' ) {

		$result['message'] = '<b class="red">Соединение завершено</b>';
		$result['state']   = 'disconnected';
		$result['content'] = strtr( $resp['disconnect_reason'], $answers );

		$result['reason'] = $resp['disconnect_reason'];

		if ( $result['reason'] == '1100' ) {
			$result['state']   = 'resume';
			$result['message'] = '<b class="green">Звоним абоненту</b>';
		}

	}

	$result['callid'] = $resp['call_id'];

	print json_encode_cyr( $result );

	exit();
}
if ( $action == 'Hangup' ) {//завершение соединения Hangup

	$actionID = $_REQUEST['actionID'];
	$call_id  = $_REQUEST['call_id'];

	$result = doMethod( 'hangup', [
		"api_key"  => $api_key,
		"api_salt" => $api_salt,
		"actionID" => $actionID,
		"call_id"  => $call_id
	] );

	//print_r($result);

	if ( $result['code'] != '1000' ) {

		print '<b class="red">Звонок завершен</b>';

	}

	exit();

}
if ( $action == 'inicialize' ) {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = preparePhone( $_REQUEST['phone'] );

	$rez = getxCallerID( (string)$phone );

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

	if ( (int)$rez['pid'] > 0 ) {
		$person = current_person( (int)$rez['pid'] );
	}
	if ( (int)$rez['clid'] > 0 ) {
		$title = current_client( (int)$rez['clid'] );
	}

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
	</div>

	<div class="paddbott100 white">
		<?php if ( (int)$rez['clid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" target="blank" title="Карточка клиента">
					<i class="icon-commerical-building blue"></i>&nbsp;<?= $title ?>
				</a>
			</div>
		<?php } ?>
		<?php if ( (int)$rez['pid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openPerson('<?= $rez['pid'] ?>')" target="blank" title="Карточка контакта">
					<i class="icon-user-1 blue"></i>&nbsp;<?= $person ?>
				</a>
			</div>
		<?php } ?>
	</div>

	<hr>

	<div id="rezult" class="p10 relativ">Набор номера...<br></div>

	<hr>

	<div class="text-center btn small paddbott10">
		<a href="javascript:void(0)" onclick="addHistory('','<?= $rez['clid'] ?>','<?= $rez['pid'] ?>')" title="Добавить активность" class="button greenbtn"><i class="icon-clock"></i>&nbsp;Активность</a>
		<?php if ( $setEntry['enShowButtonCall'] == 'yes' and $isEntry == 'on' ) { ?>
			<a href="javascript:void(0)" onClick="editEntry('','edit','<?= $phone ?>');" title="Добавить обращение" class="button redbtn">&nbsp;&nbsp;<i class="icon-plus-circled"></i>&nbsp;&nbsp;Обращение</a>
		<?php } ?>
	</div>

	<script>
		$('#rezult').load('content/pbx/mango/callto.php?action=Originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src="/assets/images/loading.gif">');
	</script>

	<?php
}
?>