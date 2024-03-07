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

	$result_set  = $db -> getRow("select * from {$sqlname}sip WHERE identity = '$identity'");
	$active      = $result_set["active"];
	$tip         = $result_set["tip"];
	$sip_host    = $result_set["sip_host"];
	$sip_port    = $result_set["sip_port"];
	$sip_context = $result_set["sip_context"];
	$sip_user    = rij_decrypt($result_set["sip_user"], $skey, $ivc);
	$sip_secret  = rij_decrypt($result_set["sip_secret"], $skey, $ivc);

	//проверим наличие буферной таблицы и создадим её, если нет
	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}asteriskapi'");
	if ($da == 0) {

		$db -> query("
			CREATE TABLE `{$sqlname}asteriskapi` (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`callid` VARCHAR(255) NOT NULL COMMENT 'Идентификатор звонка',
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`extention` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Внутренний номер сотрудника',
				`iduser` INT(5) NULL DEFAULT NULL COMMENT 'ID сотрудника',
				`phone` VARCHAR(16) NOT NULL COMMENT 'Номер абонента',
				`status` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Статус звонка',
				`type` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Тип записи (исходящий или входящий)',
				`comment` TEXT NULL DEFAULT NULL COMMENT 'Результат звонка',
				`clid` INT(20) NULL DEFAULT NULL COMMENT 'Из базы клиент (clientcat.clid)',
				`pid` INT(20) NULL DEFAULT NULL COMMENT 'Из базы контакт (personcat.pid)',
				`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`),
			INDEX `extension` (`extention`)
			) 
			COMMENT='Буфер для Asterisk API' COLLATE='utf8_general_ci'
		");

	}

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к серверу ASTERISK (через API)</h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Адрес (host:port):</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_host" type="text" id="sip_host" value="'.$sip_host.'" class="w300">
				<div class="fs-09 gray2 em">Уточните у провайдера или администратора телефонии</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пользователь:</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_user" type="text" id="sip_user" value="'.$sip_user.'" class="w300">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пароль:</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_secret" type="text" id="sip_secret" value="'.$sip_secret.'" class="w300">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Собственный номер:</div>
			<div class="flex-string wp80 pl10">
				<!--<input name="sip_context" type="text" id="sip_context" value="'.$sip_context.'" class="w300">-->
				<textarea name="sip_context" id="sip_context" rows="4" class="w300">'.str_replace(",", "\n", $sip_context).'</textarea>
				<div class="fs-09 gray">Каждый номер с новой строки, должен начинаться на 7*. Например: 74952223344, 7800200600</div>
				<div class="fs-09 gray">служит для фильтрации звонков, не принадлежащих компании (АТС в режиме общаги)</div>
			</div>
	
		</div>
		
		<hr>
		
		<div class="infodiv p20 mt20 mb10">
			Asterisk API является разработкой компании <a href="https://voxlink.ru" title="Вокс Линк" target="_blank" class="Bold red">Вокс Линк</a>. Для внедрения Asterisk API свяжитесь с компанией-разработчиком.
		</div>
		
		<div class="infodiv hidden" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green hidden" onclick="openlink(\'https://salesman.pro/docs/59\')" title="Документация"><i class="icon-help"></i></div>
	';

}

if ($action == 'save') {

	$active      = $_REQUEST['active'];
	$tip         = $_REQUEST['tip'];
	$sip_context = $_REQUEST['sip_context'];
	$sipUser     = rij_crypt($_REQUEST['api_salt'], $skey, $ivc);
	$sipKey      = rij_crypt($_REQUEST['api_key'], $skey, $ivc);
	$sip_secret  = rij_crypt($_REQUEST['sip_secret'], $skey, $ivc);

	$sid = $db -> getOne("select id from {$sqlname}services WHERE folder = 'asteriskapi' and identity = '$identity'");
	if ($sid == 0) {

		$db -> query("INSERT INTO {$sqlname}services set ?u", [
			'name'     => 'AsteriskAPI',
			'folder'   => 'asteriskapi',
			'tip'      => 'sip',
			'identity' => $identity
		]);

	}

	try {

		$db -> query("UPDATE {$sqlname}sip SET ?u WHERE identity = '$identity'", [
			'sip_host'    => $sip_host,
			'sip_user'    => $sip_user,
			'sip_secret'  => $sip_secret,
			'sip_context' => str_replace("\n", ",", $sip_context),
			'active'      => $active,
			'tip'         => $tip
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

	require_once $rootpath."/content/pbx/asteriskapi/mfunc.php";

	// todo: проверка соединения через получение истории
	print '<b>Результат соединения</b>:<br>'.array2string($result, "<br>", " ").'<br>';

}

exit();