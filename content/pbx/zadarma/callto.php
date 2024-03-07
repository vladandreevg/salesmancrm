<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2018.x          */
/* ============================ */

/*
 * Совершение звонка через телефонию
 * Для правильного отображения ссылки на прослушивание записанного разговора в "Истории звонков" необходимо сделать настройки в файле api/asterisk/play.php
 */

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

//параметры подключения к серверу
require_once dirname( __DIR__)."/zadarma/sipparams.php";
require_once dirname( __DIR__)."/zadarma/mfunc.php";

//проверим наличие буферной таблицы и создадим её, если нет
$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}zadarma_log'");
if ($da == 0) {

	$db -> query("CREATE TABLE `{$sqlname}zadarma_log` (
		`id` INT(20) NOT NULL AUTO_INCREMENT,
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
		COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'
	");

}

$field = $db -> getRow("SHOW COLUMNS FROM ".$sqlname."zadarma_log LIKE 'callid'");
if ($field['Field'] == '') {

	$db -> query("ALTER TABLE {$sqlname}zadarma_log ADD COLUMN `callid` VARCHAR(255) NULL DEFAULT NULL COMMENT 'uid звонка' AFTER `pid`");

}


//параметры сотрудника
$res       = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
$title     = $res["title"];
$extension = $res["phone_in"];//внутренний номер абонента

//осуществление вызова исходящего
if ($action == 'Originate') {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = $_REQUEST['phone'];

	//если номер начинается на 8 нам надо 7
	$phone = preg_replace('/^8/', '7', $phone);

	//$phone='74953730763';
	//$phone='89523326838';

	//получаем данные абонента
	//$strCallerId = getxCallerID($phone, true);

	$result = doMethod('call', [
		"api_key"    => $api_key,
		"api_secret" => $api_secret,
		"from"       => $extension,
		"to"         => "+".preparePhone($phone),
		"clid"       => $clid,
		"pid"        => $pid
	]);

	//print_r($result);

	$state = $result -> status == 'success' ? '<b class="green">Ожидайте звонка</b>' : '<b class="red">Ошибка:</b> '.$result -> message;

	print '<div class="state">'.$state.'</div>';
	?>
	<SCRIPT>

		<?php if ($result -> status == 'success'){ ?>
		//var zadarma = setInterval(getState, 5000);
		if(!isCard)
			setTimeout(function () {
			$('#callto').empty();
		}, 15000);
		<?php } ?>

		function getState() {

			var url = 'content/pbx/zadarma/callto.php?action=State';

			$.post(url, function (data) {

				$('.state').html(data.message);
				//$('#callid').val(data.callid);

				if (data.state === 'disconnected') {

					//clearInterval(zadarma);
					//$('.state').append('<br>' + data.content);
					$('.terminate').remove();

				}

			}, "json");
		}

	</SCRIPT>
	<?php

	exit(0);

}

//статусы при исходящем берется из zadarma_log
if ($action == 'State') {

	$result = [];

	$status = $db -> getRow("SELECT * FROM  {$sqlname}zadarma_log WHERE type = 'out' and extension = '$extension' and identity = '$identity'");

	//$resp = json_decode($status['content'], true);

	$result['state'] = '';

	//отловим событие, когда установлено соединение
	if ($status['status'] == 'answered') {

		$result['message'] = '<b class="green">Звонок успешный!</b>';
		$result['state']   = 'disconnected';

	}
	elseif ($status['status'] == 'busy') {

		$result['message'] = '<b class="red">Абонент занят</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Абонент занят';

	}
	elseif ($status['status'] == 'cancel') {

		$result['message'] = '<b class="red">Отмена звонка</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Отмена звонка';

	}
	elseif ($status['status'] == 'no answer') {

		$result['message'] = '<b class="red">Абонент недоступен</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Абонент недоступен';

	}
	elseif ($status['status'] == 'failed') {

		$result['message'] = '<b class="red">Не удался</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Не удался';

	}
	elseif ($status['status'] == 'no money') {

		$result['message'] = '<b class="red">Нет средств, превышен лимит</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Нет средств, превышен лимит';

	}
	elseif ($status['status'] == 'unallocated number') {

		$result['message'] = '<b class="red">Номер не существует</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Номер не существует';

	}
	elseif ($status['status'] == 'no limit' || $status['status'] == 'no money, no limit') {

		$result['message'] = '<b class="red">Превышен лимит</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Превышен лимит';

	}
	elseif ($status['status'] == 'no day limit') {

		$result['message'] = '<b class="red">Превышен дневной лимит</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Превышен дневной лимит';

	}
	elseif ($status['status'] == 'line limit') {

		$result['message'] = '<b class="red">Превышен лимит линий</b>';
		$result['state']   = 'disconnected';
		$result['content'] = 'Превышен лимит линий';

	}

	$result['callid'] = $status['callid'];

	$result['response'] = $status;

	print json_encode_cyr($result);

	exit();

}

//приходит ссылка
if ($action == 'inicialize') {

	$clid  = (int)$_REQUEST['clid'];
	$pid   = (int)$_REQUEST['pid'];
	$phone = preparePhone($_REQUEST['phone']);

	$rez = getxCallerID($phone);

	//$callerID = $rez['callerID'];

	if ($pid > 0) {

		$callerID   = current_person($pid);
		$rez['pid'] = $pid;
		$clid       = $rez['clid'] = (int)getPersonData($pid, 'clid');

	}
	elseif ($clid > 0) {

		$callerID    = current_client($clid);
		$rez['clid'] = $clid;

	}

	//найдем данные клиента по полученным $clientID и $personID
	$client = $callerID ? : 'Неизвестный';

	if ($rez['pid'] > 0) {
		$person = current_person( $rez['pid'] );
	}
	if ($rez['clid'] > 0) {
		$title = current_client( $rez['clid'] );
	}

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
	</div>

	<div class="paddbott100 white">
		<?php if ($rez['clid'] > 0) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" target="blank" title="Карточка клиента">
					<i class="icon-commerical-building blue"></i>&nbsp;<?= $title ?>
				</a>
			</div>
		<?php } ?>
		<?php if ($rez['pid'] > 0) { ?>
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
		<?php if ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on') { ?>
			<a href="javascript:void(0)" onClick="editEntry('','edit','<?= $phone ?>');" title="Добавить обращение" class="button redbtn">&nbsp;&nbsp;<i class="icon-plus-circled"></i>&nbsp;&nbsp;Обращение</a>
		<?php } ?>
	</div>

	<script>
		$('#rezult').load('content/pbx/zadarma/callto.php?action=Originate&phone=<?=$phone?>&clid=<?=$rez['clid']?>&pid=<?=$rez['pid']?>').append('<img src="/assets/images/loading.gif">');
	</script>

	<?php
}