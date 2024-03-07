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
include dirname( __DIR__)."/gravitel/sipparams.php";
include dirname( __DIR__)."/gravitel/mfunc.php";

//проверим наличие буферной таблицы и создадим её, если нет
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}gravitel_log'" );
if ( $da == 0 ) {

	$db -> query( "CREATE TABLE {$sqlname}gravitel_log (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
			`callid` VARCHAR(255) NOT NULL COMMENT 'Идентификатор звонка', 
			`extension` VARCHAR(10) NOT NULL COMMENT 'Номер сотрудника', 
			`phone` VARCHAR(16) NOT NULL COMMENT 'Номер абонента', 
			`content` TEXT NOT NULL, 
			`status` VARCHAR(255) NOT NULL COMMENT 'Статус звонка', 
			`type` VARCHAR(255) NOT NULL COMMENT 'Тип записи (звонок или входящий)', 
			`clid` INT(20) NOT NULL, 
			`pid` INT(20) NOT NULL, 
			`identity` INT(30) DEFAULT '1' NOT NULL, 
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`),
			INDEX `extension` (`extension`)
			) 
			COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'" );

}

//параметры сотрудника
$res      = $db -> getRow( "select * from {$sqlname}user where iduser='$iduser1' and identity = '$identity'" );
$title    = $res["title"];
$phone_in = $res["phone_in"];//внутренний номер абонента
$mob      = $res["mob"];

if ( $action == 'Originate' ) {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = $_REQUEST['phone'];

	//получаем данные абонента
	//$strCallerId = getxCallerID( $phone, true );

	$result = doMethod( 'call', [
		"api_key"  => $api_key,
		"api_salt" => $api_salt,
		"phone_in" => $phone_in,
		"phone"    => preparePhone( $phone ),
		"clid"     => $clid,
		"pid"      => $pid
	] );

	//проверим ответ
	//$status = $db -> getRow("select * from  {$sqlname}gravitel_log where callid = '$result[actionID]' and extension = '$phone_in' and identity = '$identity'");

	print '<input type="hidden" name="actionID" id="actionID" value="'.$result['uuid'].'">';
	print '<input type="hidden" name="callid" id="callid" value="'.$result['uuid'].'">';
	print '<input type="hidden" name="clid" id="clid" value="'.$clid.'">';
	print '<input type="hidden" name="pid" id="pid" value="'.$pid.'">';
	print '<input type="hidden" name="phone" id="phone" value="'.preparePhone( $phone ).'">';

	//if ($status['callid'] != '') print '<div style="float:right" class="terminate"><a href="javascript:void(0)" onClick="doTerminate()" title="Прервать звонок"><i class="icon-phone icon-2x red"></i></a>&nbsp;</div><br>';

	if ( $result['error'] != '' ) {
		$state = '<b class="red">Ошибка:</b> '.$result['error'];
	}
	else {
		$state = '<b class="green">Ожидайте звонка</b>';
	}

	print '<div class="state">'.$state.'</div>';
	?>
	<SCRIPT type="text/javascript">

		var callid = $('#callid').val();

		<?php if($result['uuid'] != ''){ ?>
		var gravitel = setInterval(getState, 5000);
		<?php } ?>

		function doTerminate() {

			var url = 'content/pbx/gravitel/callto.php?action=Hangup&callid=' + $('#callid').val();
			$.post(url, function (data) {

				$('.state').empty().append(data.message);

				if (data.state === 'disconnected') {

					clearInterval(gravitel);
					$('.state').append('<br>' + data.content);
					$('.terminate').remove();

				}

				clearInterval(gravitel);
				hideCallWindow();

			}, 'json');

		}

		function getState() {

			var url = 'content/pbx/gravitel/callto.php?action=State&actionID=<?=$result['actionID']?>&callid=' + $('#callid').val() + '&clid=' + $('#clid').val() + '&pid=' + $('#pid').val() + '&phone=<?=preparePhone( $phone )?>';

			$.post(url, function (data) {

				$('.state').html(data.message);
				//$('#callid').val(data.callid);

				if (data.state === 'disconnected') {

					clearInterval(gravitel);
					//$('.state').append('<br>' + data.content);
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

	$status = $db -> getRow( "select * from  {$sqlname}gravitel_log where type = 'out' and extension = '$phone_in' and identity = '$identity'" );

	//$resp = json_decode($status['content'], true);

	$result['state'] = '';

	//отловим событие, когда установлено соединение
	if ( $status['status'] == 'Success' ) {

		$result['message'] = '<b class="green">Звонок успешный!</b>';
		$result['state']   = 'disconnected';

	}
	elseif ( $status['status'] == 'Busy' ) {

		$result['message'] = '<b class="red">Абонент занят</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Абонент занят';

	}
	elseif ( $status['status'] == 'Cancel' ) {

		$result['message'] = '<b class="red">Отмена звонка</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Отмена звонка';

	}
	elseif ( $status['status'] == 'NotAvailable' ) {

		$result['message'] = '<b class="red">Абонент недоступен</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Абонент недоступен';

	}
	elseif ( $status['status'] == 'NotAllowed' ) {

		$result['message'] = '<b class="red">Звонки на это направление запрещены</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Звонки на это направление запрещены';

	}

	$result['callid'] = $status['callid'];

	$result['response'] = $status;

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
		"callid"   => $call_id
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

	$rez = getxCallerID( $phone );

	//$callerID = $rez['callerID'];

	if ( $pid > 0 ) {
		$callerID   = current_person( $pid );
		$rez['pid'] = $pid;
		$clid       = $rez['clid'] = (int)getPersonData( $pid, 'clid' );
	}
	elseif ( $clid > 0 ) {
		$callerID    = current_client( $clid );
		$rez['clid'] = $clid;
	}

	//найдем данные клиента по полученным $clientID и $personID
	$client = $callerID ? : 'Неизвестный';

	if ( $rez['pid'] > 0 ) {
		$person = current_person( $rez['pid'] );
	}
	if ( $rez['clid'] > 0 ) {
		$title = current_client( $rez['clid'] );
	}

	//обновляем данные о звонке в таблице gravitel
	/*$id = $db -> getOne("select id from  {$sqlname}gravitel_log where type = 'out' and extension = '$phone_in' and identity = '$identity'") + 0;

	if ($id == 0) {

		$db -> query("INSERT INTO  {$sqlname}gravitel_log SET ?u", array(
			'datum'     => current_datumtime(),
			'extension' => $phone_in,
			'phone'     => $phone,
			"content"   => "",
			"status"    => "",
			"callid"    => "",
			'type'      => 'out',
			'clid'      => $clid + 0,
			'pid'       => $pid + 0,
			'identity'  => $identity
		));
		$id = $db -> insertId();

	}
	else {

		$db -> query("UPDATE {$sqlname}gravitel_log SET ?u WHERE id = '$id'", array(
			'datum'    => current_datumtime(),
			'phone'    => $phone,
			"content"  => "",
			"status"   => "",
			"callid"   => "",
			'type'     => 'out',
			'clid'     => $clid + 0,
			'pid'      => $pid + 0,
			'identity' => $identity
		));

	}*/

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
	</div>

	<div class="paddbott100 white">
		<?php if ( $rez['clid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" target="blank" title="Карточка клиента">
					<i class="icon-commerical-building blue"></i>&nbsp;<?= $title ?>
				</a>
			</div>
		<?php } ?>
		<?php if ( $rez['pid'] > 0 ) { ?>
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
		<?php if ( $setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' ) { ?>
			<a href="javascript:void(0)" onClick="editEntry('','edit','<?= $phone ?>');" title="Добавить обращение" class="button redbtn">&nbsp;&nbsp;<i class="icon-plus-circled"></i>&nbsp;&nbsp;Обращение</a>
		<?php } ?>
	</div>

	<script>
		$('#rezult').load('content/pbx/gravitel/callto.php?action=Originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src="/assets/images/loading.gif">');
	</script>

	<?php
}
?>