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

	$result_set = $db -> getRow("select * from {$sqlname}services WHERE folder = 'comtube' and identity = '$identity'");
	$sipKey     = rij_decrypt($result_set["user_key"], $skey, $ivc);
	$sipUser    = rij_decrypt($result_set["user_id"], $skey, $ivc);

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к <b>Comtube.com</b></h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пользователь:</div>
			<div class="flex-string wp80 pl10">
				<input name="sipUser" type="text" id="sipUser" value="'.$sipUser.'" class="w300">
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пароль:</div>
			<div class="flex-string wp80 pl10">
				<input name="sipKey" type="text" id="sipKey" value="'.$sipKey.'" class="w300">
				<a href="http://www.comtube.com/?agent_id=4f355d7538dad8dcecee55c5ce8ced17" target="blank" title="Зарегистрироваться в Comtube" class="button">Получить аккаунт Comtube</a>
				<div class="fs-09 gray2 em">Уточните у провайдера</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="infodiv" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/60\')" title="Документация"><i class="icon-help"></i></div>
	';

	exit();

}

if ($action == 'save') {

	$active      = $_REQUEST['active'];
	$tip         = $_REQUEST['tip'];

	$sid = $db -> getOne("SELECT id FROM {$sqlname}services WHERE folder = 'comtube' and identity = '$identity'");

	if ($sid == 0) {

		$db -> query("INSERT INTO `{$sqlname}services` SET ?u", [
			"name"     => 'Comtube',
			"folder"   => 'comtube',
			"tip"      => 'sip',
			"identity" => $identity
		]);

	}

	$db -> query("UPDATE {$sqlname}services SET ?u WHERE folder = 'comtube' and identity = '$identity'", [
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

	require_once $rootpath."/content/pbx/comtube/callto.php";

	$config['username'] = $_REQUEST['sipUser'];
	$config['secret']   = $_REQUEST['sipKey'];

	$result = CheckBalance('balance', $config);

	if ($result != '') {
		print $result;
	}
	else {
		print '<b class="red">Ошибка. Не корректные данные</b>';
	}

}

exit();