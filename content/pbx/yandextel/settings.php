<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting(E_ERROR);

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 3);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];

if ($action == '') {

	//проверим наличие буферной таблицы и создадим её, если нет
	$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}yandextel_log'");
	if ($da == 0) {

		$db -> query("CREATE TABLE `{$sqlname}yandextel_log` (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`extension` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Внутренний номер сотрудника',
				`phone` VARCHAR(16) NOT NULL COMMENT 'Номер абонента',
				`status` VARCHAR(255) NOT NULL COMMENT 'Статус звонка',
				`type` VARCHAR(255) NOT NULL COMMENT 'Тип записи (исходящий или входящий)',
				`clid` INT(20) NULL DEFAULT NULL COMMENT 'Из базы клиент (clientcat.clid)',
				`pid` INT(20) NULL DEFAULT NULL COMMENT 'Из базы контакт (personcat.pid)',
				`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`),
			INDEX `extension` (`extension`)
			) 
			COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'");

	}

	$res    = $db -> getRow("select * from {$sqlname}services WHERE folder = 'yandextel' and identity = '$identity'");
	$apikey = rij_decrypt($res["user_key"], $skey, $ivc);
	//$iduser1  = $GLOBALS['iduser1'];
	$dobnumer = $db -> getOne("select phone_in from  {$sqlname}user where iduser = '$iduser1' and identity = '$identity'");
	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к <b>Яндекс.Телефония</b></h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">Ключ:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_key" type="text" id="api_key" value="'.$apikey.'" class="w400">
			<INPUT type="hidden" name="dobmomer" id="dobmomer" value="'.$dobnumer.'">
			</div>
	
		</div>
		
		<div class="viewdiv p20 mt20 mb10">
			Рекомендуем ознакомиться с <a href="https://salesman.pro/docs/128" title="Документация" target="_blank">Документацией</a> во избежание вопросов.<br>
			<b class="red">Важно:</b> интеграция с данным сервисом в статусе в "Beta", возможны неучтенные ошибки. С предложениями обращайтесь по email: support@salesman.pro
		</div>
		
		<div class="infodiv mt10" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/128\')" title="Документация"><i class="icon-help"></i></div>
	';

	exit();

}

if ($action == 'save') {

	$active      = $_REQUEST['active'];
	$tip         = $_REQUEST['tip'];

	$sipUser = rij_crypt($_REQUEST['api_salt'], $skey, $ivc);
	$sipKey  = rij_crypt($_REQUEST['api_key'], $skey, $ivc);

	$sid = $db -> getOne("select id from {$sqlname}services WHERE folder = 'yandextel' and identity = '$identity'");
	if ($sid == 0) {

		$db -> query("INSERT INTO {$sqlname}services set ?u", [
			'name'     => 'Yandextel',
			'folder'   => 'yandextel',
			'tip'      => 'sip',
			'identity' => $identity
		]);

	}

	$db -> query("
			ALTER TABLE {$sqlname}yandextel_log
			CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL COMMENT 'Внутренний номер сотрудника' AFTER `datum`,
			CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
			CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`
		");

	$db -> query("UPDATE {$sqlname}services SET ?u WHERE folder = 'yandextel' AND identity = '$identity'", [
		'user_key' => $sipKey,
		'user_id'  => $sipUser
	]);

	try {

		$db -> query("UPDATE {$sqlname}sip SET ?u WHERE identity = '$identity'", [
			'active' => $active,
			'tip'    => $tip
		]);
		print $mes = "Данные успешно сохранены";

	}
	catch (Exception $e) {

		print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	//доп.опции телефонии
	$options = [
		"autoCreateLead" => $_REQUEST['autoCreateLead'],
		"domain"         => $_REQUEST['domain']
	];

	$id = (int)$db -> getOne("SELECT id FROM {$sqlname}customsettings WHERE tip = 'sip' and identity = '$identity'");

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}customsettings SET ?u WHERE tip = 'sip' and identity = '$identity'", [
			"datum"  => current_datumtime(),
			"params" => json_encode($options)
		]);

	}
	else {
		$db -> query("INSERT INTO {$sqlname}customsettings SET ?u", [
			"tip"      => "sip",
			"params"   => json_encode($options),
			"identity" => $identity
		]);
	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if ($action == 'check') {

	$con['login']    = $_REQUEST['api_key'];
	$con['password'] = $_REQUEST['dobmomer'];
	$con             = json_encode($con);

	?>
	<script type="text/javascript" src="/assets/js/wokers/yandextel.js"></script>
	<script type="text/javascript">Connect('<?=$con?>');</script>
	<?php

}

exit();