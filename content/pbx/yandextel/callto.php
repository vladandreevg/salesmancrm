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
 * Совершение звонка через телефонию
 * Для правильного отображения ссылки на прослушивание записанного разговора в "Истории звонков" необходимо сделать настройки в файле api/asterisk/play.php
 */
error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

require_once dirname( __DIR__)."/yandextel/mfunc.php";
require_once dirname( __DIR__)."/yandextel/sipparams.php";

//параметры подключения
$ytelset = $GLOBALS['ytelset'];

//для осуществления звонка. Данные для подключение WebPhone если он не запущен. данные берутся из sipparams.php
$con['login']    = $ytelset['api_key'];
$con['password'] = $ytelset['dobnumer'];
$con             = json_encode($con);

$action = $_REQUEST['action'];

//получени добавочного номера пользователя
$iduser1  = $GLOBALS['iduser1'];
$res      = $db -> getRow("select * from  {$sqlname}user where iduser = '$iduser1' and identity = '$identity'");
$dobnumer = $res['phone_in'];
$title    = $res["title"];

//проверим наличие буферной таблицы и создадим её, если нет
$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}yandextel_log'");
if ($da == 0) {

	$db -> query("CREATE TABLE `{$sqlname}yandextel_log` (
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

//позвонить
if ($action == 'inicialize') {

	$clid   = (int)$_REQUEST['clid'];
	$pid    = (int)$_REQUEST['pid'];
	$phone = preparePhone($_REQUEST['phone']);

	$rez = getxCallerID($phone);
	?>
	<!--Отправка данныз в WebPhone-->
	<script type="text/javascript" src="/assets/js/wokers/yandextel.js"></script>
	<script type="text/javascript">
		var status = MightyCallWebPhone.Phone.Status();
		if (status === 'inactive') Connect('<?=$con?>');
		MightyCallWebPhone.Phone.Focus();
		MightyCallWebPhone.Phone.Call('<?=$phone?>');
	</script>
	<?php

	if ($pid > 0) {
		$callerID   = current_person($pid);
		$rez['pid'] = $pid;
		$clid       = $rez['clid'] = getPersonData($pid, 'clid');
	}
	elseif ($clid > 0) {
		$callerID    = current_client($clid);
		$rez['clid'] = $clid;
	}

	//найдем данные клиента по полученным $clientID и $personID
	if ($callerID) $client = $callerID;
	else $client = 'Неизвестный';

	if ($rez['pid'] > 0) $person = current_person($rez['pid']);
	if ($rez['clid'] > 0) $title = current_client($rez['clid']);

	?>
	<div id="caller-header" class="zag paddbott10 white">
		<b>Звонок на номер:</b> <i class="icon-phone white"></i><?= $phone ?>
	</div>

	<div class="paddbott100 white">
		<?php if ((int)$rez['clid'] > 0) { ?>
			<div class="carda">
				<a href="javascript:void(0)" onclick="openClient('<?= $rez['clid'] ?>')" target="blank" title="Карточка клиента">
					<i class="icon-commerical-building blue"></i>&nbsp;<?= $title ?>
				</a></div>
		<?php } ?>
		<?php if ((int)$rez['pid'] > 0) { ?>
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
	<?php
}
?>