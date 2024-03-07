<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2016.10       */
/* ============================ */

/*
 * Совершение исходящего звонка через телефонию
 * Для правильного отображения ссылки на прослушивание записанного разговора в "Истории звонков" необходимо сделать настройки в файле api/asterisk/play.php
 */

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

include dirname( __DIR__)."/telfin/sipparams.php";
include dirname( __DIR__)."/telfin/mfunc.php";

$action = $_REQUEST['action'];
$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$phone  = preparePhone( $_REQUEST['phone'] );

//проверим наличие буферной таблицы и создадим её, если нет
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}telfin_log'" );
if ( $da == 0 ) {

	$db -> query( "CREATE TABLE `{$sqlname}telfin_log` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`callid` varchar(255) NOT NULL COMMENT 'Идентификатор звонка', 
			`datum` timestamp  NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP, 
			`extension` varchar(10) NOT NULL COMMENT 'Внутренний номер сотрудника', 
			`phone` varchar(16) NOT NULL COMMENT 'Номер абонента', 
			`status` varchar(255) NOT NULL COMMENT 'Статус звонка', 
			`type` varchar(255) NOT NULL COMMENT 'Тип записи (исходящий или входящий)', 
			`clid` INT(20) NOT NULL COMMENT 'Из базы клиент (clientcat.clid)', 
			`pid` INT(20) NOT NULL COMMENT 'Из базы контакт (personcat.pid)', 
			`identity` int(30) DEFAULT '1' NOT NULL COMMENT 'идентификатор аккаунта (id записи в таблице settings)', 
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`),
			INDEX `extension` (`extension`)
			) 
			COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'" );

}

//параметры сотрудника
$res       = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'" );
$title     = $res["title"];
$extension = $res["phone_in"];//внутренний номер абонента

//приходит ссылка
if ( $action == 'inicialize' ) {

	$rez = getxCallerID( (string)$phone );

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
	$client = ($callerID) ? : 'Неизвестный';

	if ( $rez['pid'] > 0 ) {
		$person = current_person( $rez['pid'] );
	}
	if ( $rez['clid'] > 0 ) {
		$title = current_client( $rez['clid'] );
	}

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
	</div>

	<div class="paddbott100 white">
		<?php if ( $rez['clid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" title="Карточка клиента"><i class="icon-building blue"></i>&nbsp;<?= $title ?>
				</a>
			</div>
		<?php } ?>
		<?php if ( $rez['pid'] > 0 ) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openPerson('<?= $rez['pid'] ?>')" title="Карточка контакта"><i class="icon-user-1 blue"></i>&nbsp;<?= $person ?>
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
		$('#rezult').load('content/pbx/telfin/callto.php?action=Originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src="/assets/images/loading.gif">');
	</script>

	<?php

	exit();

}

//осуществление вызова исходящего
if ( $action == 'Originate' ) {

	$id = (int)$db -> getOne( "SELECT id FROM  {$sqlname}telfin_log WHERE type = 'out' and extension = '$extension' and identity = '$identity'" );

	//чистим лог при out нового звонка
	if ( $id > 0 ) {
		$db -> query( "DELETE FROM {$sqlname}telfin_log WHERE id = '$id'" );
	}

	$phone  = $extension;
	$phone2 = "+".$_REQUEST['phone'];

	//запрос на получение токена
	$token = doMethod( 'token', [
		"api_key"    => $api_key,
		"api_secret" => $api_secret
	] );

	//print_r($token);
	//var_dump($token);

	//запрос на получение extension_id, поскольку необходим для того что бы осуществить звонок с добавочного (пример 124575)
	$apar        = [
		"api_key"    => $api_key,
		"api_secret" => $api_secret,
		"token"      => $token['access_token'],
		"extension"  => $extension
	];
	$extensionID = doMethod( 'extension_id', $apar );

	//print $extensionID;
	//var_dump($extensionID);
	//exit();

	//получение id звонка и осуществление самого звонка
	$apar   = [
		"api_key"        => $api_key,
		"api_secret"     => $api_secret,
		"extension_id"   => $extensionID,
		"src_num"        => [$phone],
		"dst_num"        => $phone2,
		"token"          => $token['access_token'],
		"caller_id_name" => $title
	];
	$callID = doMethod( 'call_id', $apar );

	//print_r($apar);
	//print_r($callID);
	//var_dump($callID);

	$state = ($callID['status']) ? '<b class="red">Ошибка:</b> '.$callID['message'] : '<b class="green">Ожидайте звонка</b>';

	print '<div class="state">'.$state.'</div>';
	?>
	<SCRIPT type="text/javascript">

		<?php if($callID['call_api_id']){ ?>
		var telfin = setInterval(getState, 5000);
		//console.log(telfin);
		<?php } ?>

		//статус
		function getState() {

			var url = 'content/pbx/telfin/callto.php?action=State';

			$.post(url, function (data) {

				$('.state').html(data.message);

				if (data.state === 'disconnected') {

					clearInterval(telfin);
					$('.terminate').remove();

				}

			}, "json");
		}

	</SCRIPT>
	<?php

	exit();

}

//статусы при исходящем берется из telfin_log. Данные попадают туда из events.php
if ( $action == 'State' ) {

	$result = [];

	$status = $db -> getRow( "SELECT * FROM  {$sqlname}telfin_log WHERE type = 'out' and extension = '$extension' and identity = '$identity'" );

	$result['state'] = '';

	//отловим событие, когда установлено соединение
	if ( $status['status'] == 'ANSWER' ) {

		$result['message'] = '<b class="green">Идет разговор</b>';
		$result['content'] = 'Идет разговор';

	}
	elseif ( $status['status'] == 'BUSY' ) {

		$result['message'] = '<b class="red">Абонент занят</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Абонент занят';

	}
	elseif ( $status['status'] == 'CANCEL' ) {

		$result['message'] = '<b class="red">Отмена звонка</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Отмена звонка';

	}
	elseif ( $status['status'] == 'NOANSWER' ) {

		$result['message'] = '<b class="red">Истек таймер ожидания</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Истек таймер ожидания';

	}
	elseif ( $status['status'] == 'CONGESTION' ) {

		$result['message'] = '<b class="red">Произошла ошибка во время вызова</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Произошла ошибка во время вызова';

	}
	elseif ( $status['status'] == 'CHANUNAVAIL' ) {

		$result['message'] = '<b class="red">У вызываемого абонента отсутствует регистрация</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'У вызываемого абонента отсутствует регистрация';

	}
	elseif ( $status['status'] == 'END' ) {

		$result['message'] = '<b class="red">Звонок завершен</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Звонок завершен';

	}

	$result['callid'] = $status['callid'];

	$result['response'] = $status;

	print json_encode_cyr( $result );

	exit();

}