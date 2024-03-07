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

	$tblIs = $db -> getOne("select id from {$sqlname}services WHERE folder = 'sipnet' and identity = '$identity'");


	if ($tblIs < 1) {
		$db -> query("INSERT INTO `{$sqlname}services` (id,name,folder,tip,identity) VALUES (null,'Sipnet','sipnet','sip','$identity')");
	}

	$result_set = $db -> getRow("select * from {$sqlname}services WHERE folder = 'sipnet' and identity = '$identity'");
	$sipKey     = rij_decrypt($result_set["user_key"], $skey, $ivc);
	$sipUser    = rij_decrypt($result_set["user_id"], $skey, $ivc);

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к <b>Sipnet.ru</b></h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пользователь (или SIP ID):</div>
			<div class="flex-string wp80 pl10">
				<input name="sipUser" type="text" id="sipUser" value="'.$sipUser.'" class="w300">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пароль:</div>
			<div class="flex-string wp80 pl10">
				<input name="sipKey" type="text" id="sipKey" value="'.$sipKey.'" class="w300">
				<a href="https://www.sipnet.ru/cabinet/register?id=70906244" target="blank" title="Зарегистрироваться в Sipnet.ru" class="button">Получить аккаунт Sipnet.ru</a>
				<div class="fs-09 gray2 em">Уточните у провайдера</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="infodiv" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/97\')" title="Документация"><i class="icon-help"></i></div>
	';

	exit();

}

if ($action == 'save') {

	$active = $_REQUEST['active'];
	$tip    = $_REQUEST['tip'];

	$sid = $db -> getOne("SELECT id FROM {$sqlname}services WHERE folder = 'sipnet' and identity = '$identity'");

	if ($sid == 0) {

		$db -> query("INSERT INTO `{$sqlname}services` SET ?u", [
			"name"     => 'Sipnet',
			"folder"   => 'sipnet',
			"tip"      => 'sip',
			"identity" => $identity
		]);

	}

	$db -> query("UPDATE {$sqlname}services SET ?u WHERE folder = 'sipnet' and identity = '$identity'", [
		"user_key" => $sipKey,
		"user_id"  => $sipUser
	]);

	try {

		$db -> query("UPDATE {$sqlname}sip SET ?u WHERE identity = '$identity'", [
			"active" => $active,
			"tip"    => $tip
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

	require_once $rootpath."/content/pbx/sipnet/callto.php";

	$config['sipuid']   = $_REQUEST['sipUser'];
	$config['password'] = $_REQUEST['sipKey'];

	print $result = CheckBalance('balance', $config);

}

exit();