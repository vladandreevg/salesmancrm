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

	require_once $rootpath."/content/pbx/onlinepbx/lib/onpbx_http_api.php";

	//проверим наличие буферной таблицы и создадим её, если нет
	$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}onlinepbx_log'");
	if ($da == 0) {

		$db -> query("
			CREATE TABLE `{$sqlname}onlinepbx_log` (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`callid` VARCHAR(255) NOT NULL COMMENT 'Идентификатор звонка',
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				`extension` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Внутренний номер сотрудника',
				`phone` VARCHAR(16) NOT NULL COMMENT 'Номер абонента',
				`status` INT(1) NOT NULL COMMENT 'Статус звонка',
				`type` VARCHAR(255) NOT NULL COMMENT 'Тип записи (исходящий или входящий)',
				`comment` VARCHAR(255) COMMENT 'Результат звонка',
				`clid` INT(20) NULL DEFAULT NULL COMMENT 'Из базы клиент (clientcat.clid)',
				`pid` INT(20) NULL DEFAULT NULL COMMENT 'Из базы контакт (personcat.pid)',
				`identity` INT(30) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',
			PRIMARY KEY (`id`), 
			UNIQUE INDEX `id` (`id`),
			INDEX `extension` (`extension`)
			) 
			COMMENT='Лог отправленных уведомлений' COLLATE='utf8_general_ci'
		");

	}

	$api_key = $db -> getOne("SELECT api_key FROM {$sqlname}settings WHERE id = '$identity'");

	$res = $db -> getRow("SELECT * FROM {$sqlname}services WHERE folder = 'onlinepbx' and identity = '$identity'");

	if ($res['id'] < 1) {

		$db -> query("INSERT INTO `{$sqlname}services` (id,name,folder,tip,identity) VALUES (null,'OnlinePBX','onlinepbx','sip','$identity')");

	}
	else {

		$domain_old = rij_decrypt($res["user_id"], $skey, $ivc);
		$apikey_old = rij_decrypt($res["user_key"], $skey, $ivc);
		//Проверим актуальность ключей. Если актуальны - выводим
		$keys = onpbx_get_secret_key($domain_old, $apikey_old, false);

		if ($keys['status'] == 1) {

			$sipKey = $apikey_old;

		}
		$sipUser = $domain_old;
	}

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к <b>OnlinePBX.ru</b></h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">SIP домен:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_salt" type="text" id="api_salt" value="'.$sipUser.'" class="w300">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Ключ:</div>
			<div class="flex-string wp80 pl10">
				<input name="api_key" type="text" id="api_key" value="'.$sipKey.'" class="w300">
				<a href="https://panel2.onlinepbx.ru/" target="blank" title="Зарегистрироваться в OnlinePBX" class="button">Получить аккаунт OnlinePBX</a>
				<div class="fs-09 gray em">Получите в ЛК OnlinePBX в разделе <a href="https://panel2.onlinepbx.ru/profile.php#api_settings" title="Веб-хуки" target="_blank">Настройка API</a></div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt5">Ссылка для событий:</div>
			<div class="flex-string wp80 pl10">
				<pre class="marg0 graybg pad5 inline code hand" data-clipboard-text="'.$productInfo['crmurl'].'/content/pbx/onlinepbx/events.php?crmkey='.$api_key.'" style="width:auto">'.$productInfo['crmurl'].'/api/onlinepbx/events.php?crmkey='.$api_key.'</pre>
				<div class="fs-09 gray em">Используйте этот адрес в личном кабинете <a href="https://panel2.onlinepbx.ru/profile.php#webhooks" title="Веб-хуки" target="_blank">( Профиль / Веб-хуки ) </a> для получения уведомлений о звонках и других событиях.</div>
				<div class="fs-09 red em"><b class="">Важно:</b> этот адрес должен быть публичным, т.е. доступен из вне.</div>
			</div>
	
		</div>
		
		<div class="viewdiv p20 mt20 mb10">
			Рекомендуем ознакомиться с <a href="https://salesman.pro/docs/108" title="Документация" target="_blank" class="blue">Документацией</a> во избежание вопросов.
		</div>
		
		<div class="infodiv" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/143\')" title="Документация"><i class="icon-help"></i></div>
	';

	exit();

}

if ($action == 'save') {

	$active      = $_REQUEST['active'];
	$tip         = $_REQUEST['tip'];

	require_once $rootpath."/content/pbx/onlinepbx/mfunc.php";

	$config['api_salt'] = $_REQUEST['api_salt'];
	$config['api_key']  = $_REQUEST['api_key'];

	$result = doMethod('auth', $config);

	if ($result['status'] == 1) {

		$db -> query("UPDATE {$sqlname}sip SET ?u WHERE identity = '$identity'", [
			'active' => $active,
			'tip'    => $tip
		]);

	}
	print $result['message'];

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

	require_once $rootpath."/content/pbx/onlinepbx/lib/onpbx_http_api.php";

	$keys = onpbx_get_secret_key($_REQUEST['api_salt'], $_REQUEST['api_key'], false);

	if ($keys['status'] == 1) {
		print '<b class="green">Соединение установлено</b>';
	}

	else {
		print '<b class="red">Ошибка:</b> Проверьте правильность SIP домена и API ключа';
	}

}

exit();