<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
ini_set('display_errors', 1);

$rootpath = dirname(__DIR__);

if (!file_exists($rootpath."/cash/salesman_error.log")) {

	$file = fopen($rootpath."/cash/salesman_error.log", 'wb');
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $rootpath.'/cash/salesman_error.log');

error_reporting(E_ERROR);

$step        = (int)$_REQUEST['step'];
$isInstaller = true;

//print $step;
//exit();

$sysVersion = json_decode(str_replace([
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents($rootpath."/_whatsnew/version.json")), true);

$root = realpath(__DIR__.'/');

if (!isset($step)) {

	$filename = $rootpath."/inc/config.php";

	if (file_exists($filename)) {
		print '
			<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
			<LINK rel="stylesheet" href="/assets/css/fontello.css">
			<div class="warning mt20 p20 flex-container" style="width:600px; margin: 0 auto;">
				<div class="flex-string wp15">
					<i class="icon-attention red icon-3x"></i>
				</div>
				<div class="flex-string wp85">
					<p class="red uppercase Bold mb20">Ошибка:</p>
					<p>Имеется Конфигурационный файл (<b class="red">/inc/config.php</b>).</p> 
					<p>Сначала удалите его (не забывайте сделать резервную копию файла).</p>
					<p><a href="/" class="button" title="На Рабочий стол">На Рабочий стол</a></p>
				</div>
			</div>
		';
		exit();
	}

}

if ($step == 3) {

	$fp = fopen($rootpath."/inc/config.php", 'wb');
	flock($fp, LOCK_EX);
	fwrite($fp, '<?php'."\n");
	fwrite($fp, '$dbhostname = '.'"'.$_POST['host'].'";'."\n");
	fwrite($fp, '$dbusername = '.'"'.$_POST['username'].'";'."\n");
	fwrite($fp, '$dbpassword = '.'"'.$_POST['password'].'";'."\n");
	fwrite($fp, '$database = '.'"'.$_POST['dbname'].'";'."\n");
	fwrite($fp, '$sqlname = '.'"'.$_POST['prefix'].'";'."\n");

	flock($fp, LOCK_UN);
	fclose($fp);

	$message = '<br><DIV class="success"><b>Шаг 2:</b> Конфигурационный файл создан. <i class="icon-ok-circled green"></i></DIV><br>';

}
if ($step == 4) {

	error_reporting(E_ERROR);
	ini_set('display_errors', 1);

	set_time_limit(0);

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/vendor/autoload.php";

	global $dbhostname, $dbusername, $dbpassword, $database, $sqlname;

	$g1 = $g2 = '';

	/**
	 * выдает текущую дату+время
	 * если заданы параметры, то со смещением минус Х часов, Х минут
	 *
	 * @param int $hours
	 * @param int $minutes
	 *
	 * @return false|string
	 * @category Core
	 * @package  Func
	 */
	function current_datumtime(int $hours = 0, int $minutes = 0) {

		$tzone = $GLOBALS['tzone'];

		if (!$hours) {
			$hours = 0;
		}
		if (!$minutes) {
			$minutes = 0;
		}

		return date('Y-m-d H:i:s', mktime(date('H') - $hours, date('i') - $minutes, date('s'), date('m'), date('d'), date('Y')) + $tzone * 3600);

	}

	$opts = [
		'host'    => $dbhostname,
		'user'    => $dbusername,
		'pass'    => $dbpassword,
		'db'      => $database,
		'errmode' => 'exception',
		'charset' => 'UTF8'
	];

	$db = new SafeMySQL($opts);


	//$db -> query( "SET NAMES 'utf8', collation_connection='utf8_general_ci', character_set_client='utf8', character_set_database='utf8', character_set_server='utf8', character_set_results='utf8'" );
	$db -> query("SET sql_mode = 'NO_ENGINE_SUBSTITUTION'");

	$dbVersionFull = $db -> getOne("SELECT VERSION()");
	$dbVersionA    = explode(".", $dbVersionFull);
	$dbVersion     = (float)( $dbVersionA[0].".".$dbVersionA[1] );

	$path   = $rootpath."/_install/db/";
	$errmes = [];

	flush();

	$err  = 0;
	$file = "setup.sql";

	$file = file_get_contents($path.$file);

	$querys = explode(";#%%", $file);

	//print_r($querys);
	//exit();

	foreach ($querys as $query) {

		if (!empty(trim($query))) {

			if ($dbVersion >= 5.5) {
				$query = str_replace("utf8_general_ci", "utf8", $query);
			}

			if ($sqlname != 'salesman_') {
				$query = str_replace('salesman_', $sqlname, $query);
			}

			try {

				$db -> query(trim($query));
				$good = 1;

			}
			catch (Exception $e) {

				$err++;
				$errmes[] = "Запрос: ".$query;
				$errmes[] = $e -> getTraceAsString();

			}

		}

	}

	//print_r($errmes);

	//$nivc = rij_iv();
	//$db -> query("update {$sqlname}settings set timezone = '".$_REQUEST['time_zone']."', ivc = '".$nivc."' where id = '1'");

	$db -> query("DROP TABLE IF EXISTS {$sqlname}ver");
	$db -> query("CREATE TABLE {$sqlname}ver (`id` int(11) NOT NULL auto_increment,`current` varchar(10) NOT NULL,`datum` timestamp  NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8");

	$db -> query("INSERT INTO {$sqlname}ver VALUES (null, $sysVersion[version],'".current_datumtime()."')");

	$message = empty($errmes) ? '<br><DIV class="success"><b>Шаг 3:</b> Таблицы в БД созданы. <i class="icon-ok-circled green"></i></DIV><br>' : '<br><DIV class="warning"><b>Шаг 3:</b> <b class="red">Внимание!</b> Ошибка при создании таблиц <i class="icon-attention red"></i></DIV><br>'.nl2br(implode("<br>", $errmes));

}
if ($step == 5) {

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/vendor/autoload.php";

	global $dbhostname, $dbusername, $dbpassword, $database, $sqlname;

	$opts = [
		'host'    => $dbhostname,
		'user'    => $dbusername,
		'pass'    => $dbpassword,
		'db'      => $database,
		'errmode' => 'exception',
		'charset' => 'UTF8'
	];
	$db   = new SafeMySQL($opts);

	/**
	 * Генератор "соли" для кодирования паролей
	 *
	 * @param int $max
	 *
	 * @return null|string
	 * @throws Exception
	 */
	function xgenerateSalt(int $max = 32): ?string {

		if (!$max) {
			$max = 32;
		}

		$chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		$size  = StrLen($chars) - 1;
		$salt  = NULL;
		while ($max--) {
			$salt .= $chars[random_int(0, $size)];
		}

		return $salt;

	}

	/**
	 * Расшифровка пароля на основе "соли"
	 *
	 * @param $pass
	 * @param $salt
	 *
	 * @return string
	 */
	function xencodePass($pass, $salt): string {

		return hash('sha512', ( hash('sha512', $salt.$pass).$salt.strlen($salt) * 37 )).substr(hash('sha512', $pass.$salt), 0, 42);

	}

	$login2 = $_REQUEST['admin_login'];
	$pwd    = $_REQUEST['pass1'];
	$pwd1   = $_REQUEST['pass2'];

	$salt    = xgenerateSalt();
	$newpass = xencodePass($pwd, $salt);

	if ($pwd == $pwd1) {

		$title   = $_REQUEST['admin_name'];
		$usertip = $_REQUEST['usertip'];

		$db -> query("UPDATE {$sqlname}settings SET timezone = '".$_REQUEST['time_zone']."' WHERE id = '1'");

		try {

			$arg = [
				"login"         => $login2,
				"pwd"           => $newpass,
				"sole"          => $salt,
				"email"         => $login2,
				"title"         => $title,
				"user_post"     => 'Руководитель',
				"mid"           => 0,
				"bid"           => 0,
				"acs_analitics" => 'on',
				"acs_maillist"  => 'on',
				"acs_files"     => 'on',
				"acs_price"     => 'on',
				"acs_credit"    => 'on',
				"acs_prava"     => 'on',
				"tzone"         => "0",
				"viget_on"      => 'on;on;on;on;on;on;on;on;on;on;on;on',
				"viget_order"   => 'd1;d2;d5;d7;d4;d6;d3;d8;d9;d10;d11;d12',
				"secrty"        => 'yes',
				"isadmin"       => 'on',
				"tip"           => $usertip,
				"acs_import"    => 'on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on;on',
				"show_marga"    => 'yes',
				"acs_plan"      => 'on',
				"subscription"  => 'on;off;off;on;off;on;on;on;on;on;on;on;off;off;off;off;off;off',
				"usersettings"  => '{\"vigets\":{\"parameters\":\"on\",\"voronka\":\"on\",\"analitic\":\"on\",\"dogs_renew\":\"on\",\"credit\":\"on\",\"stat\":\"on\"},\"taskAlarm\":null,\"userTheme\":\"\",\"userThemeRound\":null,\"startTab\":\"vigets\",\"menuClient\":\"my\",\"menuPerson\":\"my\",\"menuDeal\":\"my\",\"notify\":[\"client.add\",\"client.edit\",\"client.userchange\",\"client.delete\",\"client.double\",\"person.send\",\"deal.add\",\"deal.edit\",\"deal.userchange\",\"deal.step\",\"deal.close\",\"invoice.doit\",\"lead.add\",\"lead.setuser\",\"lead.do\",\"comment.new\",\"comment.close\",\"task.add\",\"task.edit\",\"task.doit\",\"self\"],\"filterAllBy\":null,\"subscribs\":null}',
				"identity"      => '1'
			];

			$db -> query("INSERT INTO {$sqlname}user SET ?u", $arg);

			$message = '<br><DIV class="success"><b>Шаг 3:</b> Аккаунт администратора создан. <i class="icon-ok-circled green"></i></DIV><br>';

		}
		catch (Exception $e) {

			echo $message = '<br><DIV class="warning"><b>Шаг 3:</b> <b class="red">Внимание!</b> Аккаунт администратора НЕ создан. Ошибка:'.$e -> getMessage().'<i class="icon-attention red"></i></DIV><br>';

		}

	}
	else {

		$message = '<br><DIV class="warning"><b>Шаг 3:</b> <b class="red">Внимание!</b> Аккаунт администратора НЕ создан. Ошибка: пароли не совпадают <i class="icon-attention red"></i></DIV><br>';
		$error   = 1;

	}

}
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Менеджер установки</title>
	<style>
		<!--
		@import url("/assets/css/app.css");
		@import url("/assets/css/fontello.css");

		body {
			padding: 10px;
			font-size: 12px;
			background: #FFF;
		}

		body {
			overflow: auto !important;
		}

		input, select {
			width: 100%;
		}

		#license {
			overflow: auto;
			background: #FFF;
			font-size: 14px;
			line-height: 18px;
			max-height: 300px;
			padding: 20px;
			margin: 20px;
		}

		.blok {
			overflow: auto;
			background: #FFF;
			font-size: 14px;
			line-height: 18px;
			padding: 20px;
			margin: 20px;
		}

		legend {
			font-size: 18px;
			font-weight: 700;
			padding: 0 10px;
		}

		fieldset {
			width: 100%;
			margin: 0 auto;
		}

		.main_div {
			width: 80%;
			padding: 5px 40px;
			margin: 0 auto;
		}

		.button, a.button, .button a {
			padding: 10px;
		}

		.hidden {
			display: none;
		}

		img{
			max-width: 60%;
		}

		p {
			overflow-wrap   : break-word !important;
			word-wrap       : break-word !important;
			-webkit-hyphens : auto !important;
			-ms-hyphens     : auto !important;
			-moz-hyphens    : auto !important;
			hyphens         : auto !important;
			word-break      : break-all !important;
		}

		-->
	</style>
	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script src="/assets/js/jquery/jquery.form.js"></script>
	<script src="/assets/js/app.extended.js"></script>
</head>
<body>

<div class="main_div flex-container">

	<div class="flex-string"><img src="/assets/images/logo.png" height="20"></div>
	<div class="flex-string"><h1>Менеджер установки</h1></div>

</div>

<?php
if (!isset($_REQUEST['step'])) {

	$error = 0;
	?>
	<div class="main_div">

		<fieldset>

			<legend class="blue"><b>Подготовка</b></legend>

			<div id="license" style="max-height: 80vh">

				<div class="errorfont">Проверка системных требований:</div>
				<hr>
				<blockquote>
					<?php
					$ver = substr(PHP_VERSION, 0, 3);
					if ($ver == '5.3') {

						print '<i class="icon-attention green"></i>&nbsp;Требуется версия <b>PHP</b> <b class="red">7.2...8.1</b>. Текущая версия <b class="red">'.$ver.'</b>.<br>';
						$error++;

					}
					elseif (
						in_array($ver, [
							'7.2',
							'7.3',
							'7.4',
							'8.1'
						])
					) {

						print '<i class="icon-ok-circled green"></i>&nbsp;Требуется версия <b>PHP</b> <b class="blue">7.2...8.1</b>. Текущая версия <b class="green">'.$ver.'</b>.<br>';

					}
					else {

						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Версия <b>PHP</b> <b class="red">не поддерживается</b>. Установлена версия <b>'.$ver.'</b><br>';

					}

					if (!extension_loaded("dom")) {

						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>DOM</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>DOM</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("curl")) {

						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>cURL</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>cURL</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("mbstring")) {

						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>MBSTRING</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>MBSTRING</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("zlib")) {
						//$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>ZLIB</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>ZLIB</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("xmlreader")) {
						//$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>XMLREADER</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>XMLREADER</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (extension_loaded("domxml")) {

						//$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>DOMXML</b></u> (модуль PHP) <b class="red">подключен</b>. Не критично, но данный модуль мешает работе класса <b>dompdf</b> для генерации PDF файлов.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>DOMXML</b></u> (модуль PHP) <b class="green">не подключен</b>.<br>';
					}

					/*
					if (!extension_loaded("mcrypt")) {

						//$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>MCRYPT</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>MCRYPT</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					*/

					if (!extension_loaded("openssl")) {

						$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>OPEN SSL</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>OPEN SSL</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("imap")) {

						//$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>IMAP</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>IMAP</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("imagick")) {

						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>IMAGICK</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>IMAGICK</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (!extension_loaded("gd")) {

						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>GD</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>GD</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}

					if (ini_get('short_open_tag') == 'off') {

						$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Директива <u><b>short_open_tag</b></u> (см. php.ini) <b class="red">off</b>. Критично.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Директива <u><b>short_open_tag</b></u> <b class="green">on</b>.<br>';
					}

					if (ini_get('date.timezone') == '') {

						$error++;
						print '<i class="icon-attention broun"></i>&nbsp;Не задан параметр <u><b>date.timezone</b></u> (см. php.ini) <b class="red">Прописать в php ini "date.timezone = Europe/Moscow"</b>. Критично.<br>';

					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Параметр <u><b>date.timezone</b></u> (см. php.ini) <b class="green">задан</b>. Установлен часовой пояс: '.ini_get('date.timezone').'<br>';
					}
					?>
				</blockquote>
				<B>Требуемые права для папок (должны быть 0777):</B>
				<hr>
				<blockquote>
					<?php
					$files = [
						'/files',
						'/cash',
						'/cash/templates',
						'/cash/logo',
						'/inc',
					];

					foreach ($files as $file) {

						clearstatcache();

						if (!is_writable($rootpath.$file)) {

							print '<i class="icon-attention red"></i>&nbsp;Папка <u><b>'.$file.'</b>&nbsp;[ <b class="red">'.substr(decoct(fileperms($rootpath.'/'.$file)), -4).'</b> ]</u> '.( $file == '/inc' ? "Требуется для создания конфигурационного файла config.php" : "" ).'.<br>';

						}
						else {

							print '<i class="icon-ok-circled green"></i>&nbsp;Папка <u><b>'.$file.'</b>&nbsp;[ <b class="green">'.substr(decoct(fileperms($rootpath.'/'.$file)), 1).'</b> ]</u><br>';

						}

					}

					if (!is_writable($rootpath.'/inc')) {
						$error++;
					}
					?>
				</blockquote>

			</div>

			<?php
			if ($error == 0) {

				print '<div class="main_div text-center"><a href="?step=0" class="button">Продолжить установку</a></div>';

			}
			else {

				print '
				<div id="license" class="text-center">
					<div class="warning text-center">
						<b class="red">Имеются ошибки.</b> Продолжение установки не целессобразно.
					</div>
				</div>';

			}
			?>
		</fieldset>

	</div>
	<?php
	exit();
}
if ($step == 0) {

	require_once $rootpath."/vendor/autoload.php";

	?>
	<div class="main_div">

		<fieldset>

			<legend class="blue"><b>Шаг 1:</b> Лицензионное соглашение</legend>

			<div id="license" class="warning">
				<span class="pull-left"><i class="icon-attention red icon-2x"></i></span>
				<b>Факт продолжения установки приложения означает ваше полное согласие с условиями данной
					лицензии.</b><br>
				Обновление приложения означает ваше согласие с обновлённой лицензией, если она изменилась.
			</div>
			<div id="license" style="max-height: 60vh">
				<?php
				$html      = file_get_contents($rootpath."/README.md");
				$Parsedown = new ParsedownExtra();
				print $Parsedown -> text($html);
				?>
			</div>

			<div class="main_div fs-12">

				<a href="?step=2" class="button greenbtn"><span><b>ДА</b>, я Принимаю Условия</span></a>&nbsp; <a href="https://salesman.pro" class="button redbtn"><span><b>НЕТ</b>, я хочу подумать</span></a>

			</div>

		</fieldset>

	</div>
	<?php
}
if ($step == 2) {
	?>
	<div class="main_div">

		<form action="install.php" name="stepp2" id="stepp2" enctype="application/x-www-form-urlencoded" method="post">
			<input name="step" type="hidden" id="step" value="3">

			<fieldset>

				<legend><b>Шаг 2:</b> Создание конфигурационного файла</legend>

				<br>
				<div class="blok">

					<div class="success"><b>Шаг 1:</b> Лицензионное соглашение Принято.
						<i class="icon-ok-circled green"></i>
					</div>

					<hr>

					<table class="wp80">
						<tr>
							<td colspan="2">
								<div class="errorfont">Укажите данные для подключения к базе mySQL:</div>
								<hr>
							</td>
						</tr>
						<tr>
							<td class="cherta w250"> Сервер баз данных:</td>
							<td class="cherta">
								<input name="host" type="text" class="required" id="host" value="localhost"></td>
						</tr>
						<tr>
							<td class="cherta"> Название базы данных:</td>
							<td class="cherta">
								<input name="dbname" type="text" class="required" id="dbname" value="salesman"></td>
						</tr>
						<tr>
							<td class="cherta"> Имя пользователя базы данных:</td>
							<td class="cherta">
								<input name="username" type="text" class="required" id="username" value="salesman"></td>
						</tr>
						<tr>
							<td class="cherta"> Пароль для доступа к базе данных:</td>
							<td class="cherta">
								<input type="text" name="password" id="password" class="required" value="salesman!1">
							</td>
						</tr>
						<tr>
							<td class="cherta">Префикс для таблиц (<span class="green">рекомендуем</span>):&nbsp;</td>
							<td class="cherta"><input name="prefix" type="text" id="prefix" value="app_"></td>
						</tr>
					</table>
					<hr>
					<div class="warning"><b class="red">Внимание!</b>
						<b>Скрипт установки не создает базу данных.</b> Убедитесь, что база данных существует или
						создайте новую.
					</div>
				</div>
				<br>
				<div class="main_div text-center">
					<div class="pull-left">
						<a onClick="history.back(-1);" style="cursor:pointer"><i class="icon-left-big blue"></i>Назад</a>
					</div>
					<a href="#" onClick="cf=formCheck('stepp2');if (cf)document.stepp2.submit();" class="button"><i class="icon-ok-circled white"></i><b>Продолжить</b> установку</a>
				</div>
			</fieldset>
		</form>
	</div>
	<?php
}
if ($step == 3) {
	?>
	<div class="main_div">

		<form action="install.php?step=4" name="step3" id="step3" enctype="application/x-www-form-urlencoded" method="post">
			<fieldset>

				<legend>Шаг 3: Загрузка таблиц в БД<br></legend>

				<div class="blok">

					<div class="success"><b>Шаг 1:</b> Лицензионное соглашение Принято.
						<i class="icon-ok-circled green"></i>
					</div>

					<?php

					if (!empty($message)) {
						print $message;
					}

					include $rootpath."/inc/config.php";
					require_once $rootpath."/vendor/colshrapnel/safemysql/safemysql.class.php";

					global $dbhostname, $dbusername, $dbpassword, $database, $sqlname;

					$g1 = $g2 = '';

					$opts = [
						'host'    => $dbhostname,
						'user'    => $dbusername,
						'pass'    => $dbpassword,
						'db'      => $database,
						'errmode' => 'exception',
						'charset' => 'UTF8'
					];

					$db = new SafeMySQL($opts);

					try {

						$test2 = '<B class="green">Пользователь/Пароль валидны</B> <i class="icon-ok-circled green"></i>';
						$g2    = "good";

					}
					catch (Exception $e) {

						$test2 = '<b class="red">'.$e -> getMessage().'</b>';

					}

					$tables = $db -> getCol("SHOW DATABASES");

					if (in_array($database, $tables)) {

						$test1 = '<B class="green">База данных существует</B> <i class="icon-ok-circled green"></i>';
						$g1    = "good";

					}
					else {
						$test1 = '<b class="red">Ошибка: база данных "'.$database.'" не найдена</b>';
					}

					$r = $db -> getRow("SHOW VARIABLES LIKE '%sql_mode%'");

					$vname  = $r['Variable_name'];
					$vvalue = $r['Value'];

					$warntxt = '';
					if ($vname == 'sql_mode') {

						if ($vvalue == '') {

							$warn = 'Не задан параметр <b>sql_mode</b> - это значит, что настроен строгий режим работы MySQL и вероятны ошибки.';

						}
						elseif (stripos($vvalue, 'NO_ENGINE_SUBSTITUTION') === false) {

							$warn = 'Задан параметр <b>sql_mode</b> без диррективы NO_ENGINE_SUBSTITUTION - это значит, что настроен строгий режим работы MySQL и вероятны ошибки.';

						}

						$warntxt = '<pre>sql_mode = "'.$vvalue.'"</pre>';

					}

					if (isset($warn)) {

						print '
						<div class="warning">
							<div class="fs-14 Bold red mb20">Предупреждение!</div>
							<div>'.$warn.'</div>
							<div>'.$warntxt.'</div>
							<div>
								Рекомендуем задать этот параметр в файле настроек БД ( my.cnf ):
								<pre class="attention bgwhite">sql-mode="NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION"</pre>
							</div>
						</div>
						';

					}

					if ( $g1 == "good" && $g2 == "good") {
						?>
						<div>На данном этапе в указанной Вами базе данных будут созданы необходимые для работы <span class="red">CRM</span> таблицы. Нажмите &quot;<b class="blue">Продолжить установку</b>&quot; для продолжения работы установочного скрипта.
						</div><br>
						<div class="hidden">
							<b>Выбор конфигурации:</b>
							<?php
							$file = "database_base.sql";
							?>
							<input type="hidden1" name="file" id="file" class="required" value="<?= $file ?>">
						</div>
					<?php
					}
					?>
					<div>
						<ul>
							<li>Проверка существования базы данных <b><?= $_POST['dbname']; ?></b>:&nbsp;<?= $test1 ?>
							</li>
							<li>Проверка пользователя <b><?= $_POST['username']; ?></b>:&nbsp;<?= $test2 ?></li>
						</ul>
					</div>
					<?php
					if (( $g1 != "good" ) or ( $g2 != "good" )) {

						print '
			<div class="warning"><b class="red">Данные подключения к БД указаны не верно.</b> Вернитесь назад и укажите верные данные</div><hr>
			<div align="center"><a href="#" onClick="history.back(-1);" class="button"><i class="icon-left-big white"></i>Назад</a></div>
			';

					}
					?>
				</div>
				<div class="main_div text-center">
					<?php
					if ( $g1 == "good" && $g2 == "good") {
						?>
						<div class="pull-left">
							<a onclick="history.back(-1);" style="cursor:pointer"><i class="icon-left-big blue"></i>Назад</a>
						</div><A href="install.php?step=4" class="button"><b>Продолжить</b> установку</A>
					<?php
					} ?>
				</div>
			</fieldset>
		</form>
	</div>
	<?php
}
if ($step == 4) {
	?>
	<div class="main_div mb20 pb20">

		<form action="install.php?step=5" name="step4" id="step4" enctype="application/x-www-form-urlencoded" method="post">
			<input name="step" type="hidden" id="step" value="5">
			<fieldset>

				<legend><b>Шаг 4:</b> Создание Администратора</legend>

				<div class="blok">
					<div class="success"><b>Шаг 1:</b> Лицензионное соглашение Принято.
						<i class="icon-ok-circled green"></i>
					</div>
					<br>
					<div class="success"><b>Шаг 2:</b> Конфигурационный файл создан.
						<i class="icon-ok-circled green"></i>
					</div>
					<?php
					print $message;
					?>
					<table class="infodiv">
						<tr>
							<td colspan="2">
								<div class="blue Bold">Укажите данные для создания Вашего аккаунта:</div>
								<hr>
							</td>
						</tr>
						<tr>
							<td width="150" class="cherta"><b>Логин:</b></td>
							<td class="cherta">
								<input name="admin_login" type="email" class="required" id="admin_login" placeholder="Используйте email в качестве логина">
							</td>
						</tr>
						<tr>
							<td class="cherta"><b>Пароль:</b></td>
							<td class="cherta">
								<input type="password" name="pass1" id="pass1" class="required" placeholder="Только латинские буквы, символы !@# и цифры">
							</td>
						</tr>
						<tr>
							<td class="cherta"><b>Повтор пароля:</b></td>
							<td class="cherta">
								<input type="password" name="pass2" id="pass2" class="required" placeholder="Только латинские буквы, символы !@# и цифры">
							</td>
						</tr>
						<tr>
							<td class="cherta"><b>Имя Фамилия:</b></td>
							<td class="cherta">
								<input type="text" name="admin_name" id="admin_name" class="required" placeholder="Так Ваше имя будет отображаться в системе">
							</td>
						</tr>
						<tr>
							<td class="cherta"><b>Роль в системе:</b></td>
							<td class="cherta">
								<SELECT name="usertip" id="usertip" style="width: 100%;" class="required">
									<OPTION value="">--Выбор--</OPTION>
									<OPTION value="Руководитель организации" selected>Руководитель организации</OPTION>
									<OPTION value="Руководитель с доступом">Руководитель с доступом</OPTION>
									<OPTION value="Руководитель подразделения">Руководитель подразделения</OPTION>
									<OPTION value="Руководитель отдела">Руководитель отдела</OPTION>
									<OPTION value="Менеджер продаж">Менеджер продаж</OPTION>
									<OPTION value="Поддержка продаж">Поддержка продаж</OPTION>
									<OPTION value="Администратор">Администратор</OPTION>
								</SELECT>
							</td>
						</tr>
						<tr>
							<td>Временная зона для сервера:</td>
							<td>
								<?php
								$file = $rootpath.'/cash/tzone.json';
								$tmz  = json_decode(file_get_contents($file), true);
								//print_r($tmz);
								?>
								<select name="time_zone" id="time_zone">
									<?php
									foreach ($tmz as $key => $val) {
										print '<option value="'.$key.'" '.( $key == 'Europe/Moscow' ? 'selected' : '' ).'>'.$val.'</option>';
									}
									?>
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div class="main_div text-center">
					<div class="pull-left">
						<a onClick="history.back(-1);" style="cursor:pointer"><i class="icon-left-big blue"></i>Назад</a>
					</div>
					<a onClick="cf=formCheck('step4');if (cf)$('#step4').submit();" class="button"><b>Продолжить</b> установку</a>
				</div>
			</fieldset>
		</form>

	</div>

	<?php
}
if ($step == 5) {
	?>
	<div class="main_div mb20 pb20">

		<fieldset>

			<legend>УСПЕХ<br></legend>

			<div class="blok">

				<div class="success"><b>Шаг 1:</b> Лицензионное соглашение Принято.<i class="icon-ok-circled green"></i></div>
				<br>
				<div class="success"><b>Шаг 2:</b> Конфигурационный файл создан. <i class="icon-ok-circled green"></i></div>
				<br>
				<div class="success"><b>Шаг 3:</b> Таблицы в БД созданы. <i class="icon-ok-circled green"></i></div>
				<?= $message; ?>
				<?php
				if (!$error) {

					chmod($rootpath."/inc", 0755);
					//unlink("_update.php");
					//unlink("install.php");

					?>
					<div class="success mt20">

						<b>Установка приложения успешно закончена</b>. Вы можете приступить к работе после прохождения авторизации.<br><br>
						<b class="red">Внимание!</b> Убедитесь, что файл ключа помещен в каталог со скриптами программы - <?php
						echo $rootpath; ?>.<br>
						<br>
						В запросе на ключ также укажите следующие данные:
						<ul>
							<li>Название организации</li>
							<li>Контактные данные</li>
							<li>Количество рабочих мест (по числу сотрудников, которые будут работать в CRM)</li>
						</ul>

					</div>

					<div class="infodiv mt20">В состав дистрибутива включен бесплатный однопользовательский ключ. Вы
						можете приступить к работе сразу.
					</div>

					<hr>

					<div class="main_div text-center">

						<a href="/login" class="button">Авторизация</a>&nbsp;&nbsp;
						<a href="https://salesman.pro" target="_blank" class="blue">Сайт производителя<i class="icon-right-open"></i></a>

					</div>
					<?php
				}
				else {

					print '<div class="pull-left"><a onclick="history.back(-1);" style="cursor:pointer"><i class="icon-left-big blue"></i>Назад</a></div>';

				}
				?>

			</div>
		</fieldset>
	</div>
	<?php
}
?>

<script>

	function formCheck(form) {

		var em = 0;
		var $form = $('#' + form);

		$form.find(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
		$form.find(".required").each(function () {

			if ($(this).val() === '') {

				$(this).addClass("empty").css({"color": "#FFF", "background": "#FF8080"});
				em = em + 1;

			}

		});

		if (em > 0) {

			alert("Не заполнены обязательные поля\n\rОни выделены цветом");
			return false;

		}
		else if (em === 0) {

			return true;

		}

	}

</script>
</body>
</html>