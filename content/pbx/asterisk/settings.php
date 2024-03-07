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

	$result_set   = $db -> getRow("select * from {$sqlname}sip WHERE identity = '$identity'");
	$active       = $result_set["active"];
	$tip          = $result_set["tip"];
	$sip_host     = $result_set["sip_host"];
	$sip_port     = $result_set["sip_port"];
	$sip_channel  = $result_set["sip_channel"];
	$sip_context  = $result_set["sip_context"];
	$sip_user     = rij_decrypt($result_set["sip_user"], $skey, $ivc);
	$sip_secret   = rij_decrypt($result_set["sip_secret"], $skey, $ivc);
	$sip_numout   = $result_set["sip_numout"];
	$sip_pfchange = $result_set["sip_pfchange"];
	$sip_path     = $result_set["sip_path"];
	$sip_cdr      = $result_set["sip_cdr"];
	$sip_secure   = $result_set["sip_secure"];

	print '
		<h2 class="blue mt20 mb20 pl5">Настройки подключения к серверу ASTERISK (через AMI)</h2>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Адрес (sip-host):</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_host" type="text" id="sip_host" value="'.$sip_host.'" class="w300">
				<div class="fs-09 gray2 em">Уточните у провайдера или администратора телефонии</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Порт (sip-port):</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_port" type="text" id="sip_port" value="'.$sip_port.'" class="w300">
				<div class="fs-09 gray2 em">Уточните у провайдера или администратора телефонии. Обычно = 5038</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Канал:</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_channel" type="text" id="sip_channel" value="'.$sip_channel.'" class="w300">
				<div class="fs-09 gray2 em">Уточните у провайдера или администратора. SIP,  IAX2, DAHDI, etc.</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Контекст вызова:</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_context" type="text" id="sip_context" value="'.$sip_context.'" class="w300">
				<div class="fs-09 gray2 em">Контекст  из которого будет совершаться вызов (для АТС Asterisk)</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Путь к папке записей разговоров:</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_path" type="text" id="sip_path" value="'.$sip_path.'" class="w300">
				<div class="fs-09 gray2 em">URL вида <b class="red">http://asterisk/records</b><b class="red">/</b>. <b>Последний слеш обязателен</b></div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2">Адрес размещения скрипта "cdr2crm.php":</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_cdr" type="text" id="sip_cdr" value="'.$sip_cdr.'" class="w300">
				<div class="fs-09 gray2 em">URL вида <b class="red">http(s)://asterisk.my.ru:8080/cdr/cdr2crm.php</b>. <b>Включая название файла</b></b></div>
				<div class="fs-09 blue em">
					Указывается, если CRM и Asterisk находятся на разных серверах.<br>
					В другом случае необходимо переименовать файл "/content/pbx/asterisk/cdr.combine.php" в "/content/pbx/asterisk/cdr.php" с заменой
				</div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Код "выхода в город":</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_numout" type="text" id="sip_numout" value="'.$sip_numout.'" class="w300">
				<div class="fs-09 gray2 em">Например: если "выход в город" через <b class="blue">9</b></div>
			</div>
	
		</div>
	
		<hr>
		
		<div class="flex-container mt20 box--child">

			<div class="flex-string wp20 right-text fs-12 gray2 pt10">Замена префикса:</div>
			<div class="flex-string wp80 pl10">
				<input name="sip_pfchange" type="text" id="sip_pfchange" value="'.$sip_pfchange.'" class="w300">
				<div class="fs-09 gray2 em">Например: замена префикса <b class="blue">7</b> => <b class="blue">8</b></div>
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
		
		<div class="viewdiv p20 mt20 mb10">
			Рекомендуем ознакомиться с <a href="https://salesman.pro/docs/59" title="Документация" target="_blank">Документацией</a> во избежание вопросов.
		</div>
		
		<div class="infodiv" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="sipress" class="hidden pad5 marg3"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>

		<div class="pagerefresh refresh--icon admn green" onclick="openlink(\'https://salesman.pro/docs/59\')" title="Документация"><i class="icon-help"></i></div>
	';

}

if ($action == 'save') {

	$sip_host     = $_REQUEST['sip_host'];
	$sip_port     = $_REQUEST['sip_port'];
	$sip_channel  = $_REQUEST['sip_channel'];
	$sip_context  = $_REQUEST['sip_context'];
	$sip_user     = rij_crypt($_REQUEST['sip_user'], $skey, $ivc);
	$sip_secret   = rij_crypt($_REQUEST['sip_secret'], $skey, $ivc);
	$sip_cdr      = $_REQUEST['sip_cdr'];
	$active       = $_REQUEST['active'];
	$tip          = $_REQUEST['tip'];
	$sip_numout   = $_REQUEST['sip_numout'];
	$sip_pfchange = $_REQUEST['sip_pfchange'];
	$sip_path     = $_REQUEST['sip_path'];
	$sipUser      = rij_crypt($_REQUEST['sipUser'], $skey, $ivc);
	$sipKey       = rij_crypt($_REQUEST['sipKey'], $skey, $ivc);
	$sip_secure   = $_REQUEST['sip_secure'];

	try {

		$db -> query("UPDATE {$sqlname}sip SET ?u WHERE identity = '$identity'", [
			'active'       => $active,
			'sip_host'     => $sip_host,
			'sip_port'     => $sip_port,
			'sip_channel'  => $sip_channel,
			'sip_context'  => $sip_context,
			'sip_user'     => $sip_user,
			'sip_secret'   => $sip_secret,
			'tip'          => $tip,
			'sip_pfchange' => $sip_pfchange,
			'sip_numout'   => $sip_numout,
			'sip_path'     => $sip_path,
			'sip_cdr'      => $sip_cdr,
			'sip_secure'   => $sip_secure
		]);

		print "Данные успешно сохранены";

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

	$config['server']   = $_REQUEST['sip_host'];
	$config['port']     = $_REQUEST['sip_port'];
	$config['username'] = $_REQUEST['sip_user'];
	$config['password'] = $_REQUEST['sip_secret'];
	$config['authtype'] = 'plaintext';
	$config['debug']    = true;
	$config['log']      = true;
	$config['logfile']  = $rootpath.'/cash/ami.log';

	$ami = new AmiLib($config);
	if ($ami -> connect()) {

		$ActionID = 'salesman'.time();
		$result   = $ami -> sendRequest("Login", ["ActionID" => $ActionID]);
		$ami -> disconnect();

	}

	print '<br><b>Результат соединения</b>:<br><br>'.array2string($result, "<br>", "&nbsp;&nbsp;&nbsp;").'<br>';

}

exit();