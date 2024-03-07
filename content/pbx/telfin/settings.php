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
	$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}telfin_log'");
	if ($da == 0) {

		$db -> query("CREATE TABLE `{$sqlname}telfin_log` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`callid` VARCHAR(255) NOT NULL COMMENT 'Идентификатор звонка',
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

	$res        = $db -> getRow("select * from {$sqlname}services WHERE folder = 'telfin' and identity = '$identity'");
	$api_key    = rij_decrypt($res["user_key"], $skey, $ivc);
	$api_secret = rij_decrypt($res["user_id"], $skey, $ivc);

	$apikey = $db -> getOne("select api_key from {$sqlname}settings WHERE id = '$identity'");

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к <b>Телфин</b></h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">App ID:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_key" type="text" id="api_key" value="'.$api_key.'" class="w400">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">App Secret:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_secret" type="text" id="api_secret" value="'.$api_secret.'" class="w400">
				<div class="fs-09 gray2 em">Уточните у провайдера</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">Ссылка для событий:</div>
			<div class="flex-string wp80 pl10">
				<pre class="marg0 graybg pad5 inline code hand" data-clipboard-text="'.$productInfo['crmurl'].'/content/pbx/telfin/events.php?action=event&crmkey='.$apikey.'" style="width:auto">'.$productInfo['crmurl'].'/content/pbx/telfin/events.php?action=event&crmkey='.$apikey.'</pre>
				<div class="fs-09 gray em">Используйте этот адрес в личном кабинете (Настройки / Показать всех / Выбрать добавочный / События) для каждого сотрудника для получения уведомлений о звонках и других событиях.</div>
				<div class="fs-09 red em"><b class="">Важно:</b> этот адрес должен быть публичным, т.е. доступен из вне.</div>
			</div>
	
		</div>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">Ссылка для умной переадресации:</div>
			<div class="flex-string wp80 pl10">
				<pre class="marg0 graybg pad5 inline code hand" data-clipboard-text="'.$productInfo['crmurl'].'/content/pbx/telfin/events.php?action=contact&crmkey='.$apikey.'" style="width:auto">'.$productInfo['crmurl'].'/content/pbx/telfin/events.php?action=contact&crmkey='.$apikey.'</pre>
				<div class="fs-09 gray em">Используйте этот адрес в личном кабинете (Настройки / Показать всех / Выбрать добавочный / События) для каждого сотрудника для получения уведомлений о звонках и других событиях.</div>
				<div class="fs-09 red em"><b class="">Важно:</b> этот адрес должен быть публичным, т.е. доступен из вне.</div>
			</div>
	
		</div>
		
		<div class="viewdiv p20 mt20 mb10">
			Рекомендуем ознакомиться с <a href="https://salesman.pro/docs/127" title="Документация" target="_blank">Документацией</a> во избежание вопросов.<br>
			<b class="red">Важно:</b> интеграция с данным сервисом в статусе в "Beta", возможны неучтенные ошибки. С предложениями обращайтесь по email: support@salesman.pro
		</div>
		
		<div class="infodiv mt10" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/127\')" title="Документация"><i class="icon-help"></i></div>
		
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

	$sipUser = rij_crypt($_REQUEST['api_secret'], $skey, $ivc);
	$sipKey  = rij_crypt($_REQUEST['api_key'], $skey, $ivc);

	$sid = $db -> getOne("SELECT id FROM {$sqlname}services WHERE folder = 'telfin' and identity = '$identity'");
	if ($sid == 0) {

		$db -> query("INSERT INTO {$sqlname}services set ?u", [
			'name'     => 'Telfin',
			'folder'   => 'telfin',
			'tip'      => 'sip',
			'identity' => $identity
		]);

	}

	$db -> query("
		ALTER TABLE {$sqlname}telfin_log
		CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL COMMENT 'Внутренний номер сотрудника' AFTER `datum`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`
	");

	$db -> query("UPDATE {$sqlname}services SET ?u WHERE folder = 'telfin' AND identity = '$identity'", [
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

	require_once $rootpath."/content/pbx/telfin/mfunc.php";

	$config['api_secret'] = $_REQUEST['api_secret'];
	$config['api_key']    = $_REQUEST['api_key'];

	//var_dump($config);

	$token = doMethod('token', $config);

	//var_dump($token);

	//var_dump($result);
	if ($token['access_token'] != '') {
		print '<b class="green">Соединение установлено</b>';
	}
	else {
		print '<b class="red">Ошибка:</b> '.$token['error'];
	}

}

exit();