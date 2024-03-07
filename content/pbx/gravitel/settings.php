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
	$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}gravitel_log'");
	if ($da == 0) {

		$db -> query("CREATE TABLE {$sqlname}gravitel_log (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`callid` VARCHAR(255) NOT NULL COMMENT 'Идентификатор звонка',
			`extension` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Номер сотрудника',
			`phone` VARCHAR(16) NOT NULL COMMENT 'Номер абонента',
			`content` TEXT NOT NULL,
			`status` VARCHAR(255) NOT NULL COMMENT 'Статус звонка',
			`type` VARCHAR(255) NOT NULL COMMENT 'Тип записи (звонок или входящий)',
			`clid` INT(20) NULL DEFAULT NULL,
			`pid` INT(20) NULL DEFAULT NULL,
			`identity` INT(30) NOT NULL DEFAULT '1',
		PRIMARY KEY (`id`), 
		UNIQUE INDEX `id` (`id`),
		INDEX `extension` (`extension`)
		) 
		COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'");

	}

	$res     = $db -> getRow("select * from {$sqlname}services WHERE folder = 'gravitel' and identity = '$identity'");
	$apikey  = rij_decrypt($res["user_key"], $skey, $ivc);
	$apisalt = rij_decrypt($res["user_id"], $skey, $ivc);

	$api_key = $db -> getOne("select api_key from {$sqlname}settings WHERE id = '$identity'");

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к <b>ITooLabs, Гравител, Мегафон, Дом.ру и др.</b></h2>
		<div class="infodiv">Поддерживаются провайдеры: МегаФон, МГТС, Гравител, Дом.ру, Энфорта, ТТК, Virgin Connect, Гарс Телеком, Наука-связь, RiNet, Сибирские Сети, Авантел, Вест Колл СПб, ВестКолл Москва, Омские Кабельные Сети, Новотелеком, Деловая Сеть Иркутск.</div>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">Уникальный адрес вашей АТС:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_key" type="text" id="api_key" value="'.$apikey.'" class="w400">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Ключ для создания подписи:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_salt" type="text" id="api_salt" value="'.$apisalt.'" class="w400">
				<div class="fs-09 gray2 em">Уточните у провайдера</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">Ссылка для событий:</div>
			<div class="flex-string wp80 pl10">
				<pre class="marg0 graybg pad5 inline code hand" data-clipboard-text="'.$productInfo['crmurl'].'/content/pbx/gravitel/events.php?crmkey='.$api_key.'" style="width:auto">'.$productInfo['crmurl'].'/content/pbx/gravitel/events.php?crmkey='.$api_key.'</pre>
				<div class="fs-09 gray em">Используйте этот адрес в личном кабинете (Настройки и инструменты / Подключение по API / Адрес внешней системы) для получения уведомлений о звонках и других событиях.</div>
				<div class="fs-09 red em"><b class="">Важно:</b> этот адрес должен быть публичным, т.е. доступен из вне.</div>
			</div>
	
		</div>
		
		<div class="viewdiv p20 mt10 mb10">
			Рекомендуем ознакомиться с <a href="https://salesman.pro/docs/125" title="Документация" target="_blank">Документацией</a> во избежание вопросов.
		</div>
		
		<div class="infodiv mt10" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/125\')" title="Документация"><i class="icon-help"></i></div>
		
		<script>
		
		if(clipboard) clipboard.destroy();
		
		clipboard = new Clipboard(\'.code\');
		clipboard.on(\'success\', function(e) {

			alert("Скопировано в буфер");
			e.clearSelection();

		});
		
		</script>
	';
	exit();

}

if ($action == 'save') {

	$active      = $_REQUEST['active'];
	$tip         = $_REQUEST['tip'];

	$sipUser = rij_crypt($_REQUEST['api_salt'], $skey, $ivc);
	$sipKey  = rij_crypt($_REQUEST['api_key'], $skey, $ivc);

	$sid = $db -> getOne("select id from {$sqlname}services WHERE folder = 'gravitel' and identity = '$identity'");
	if ($sid == 0) {

		$db -> query("INSERT INTO {$sqlname}services set ?u", [
			'name'     => 'Gravitel',
			'folder'   => 'gravitel',
			'tip'      => 'sip',
			'identity' => $identity
		]);

	}

	$db -> query("
		ALTER TABLE {$sqlname}gravitel_log
		CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL COMMENT 'Номер сотрудника' AFTER `callid`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`;
	");

	$db -> query("UPDATE {$sqlname}services SET ?u WHERE folder = 'gravitel' AND identity = '$identity'", [
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

	require_once $rootpath."/content/pbx/gravitel/mfunc.php";

	$config['api_salt'] = $_REQUEST['api_salt'];
	$config['api_key']  = $_REQUEST['api_key'];

	$result = doMethod('accounts', $config);

	//print_r($result);

	if (!$result['error']) {
		print '<b class="green">Соединение установлено</b>';
	}
	if ($result['error'] != '') {
		print '<b class="red">Ошибка:</b> '.$result['error'];
	}

}

exit();