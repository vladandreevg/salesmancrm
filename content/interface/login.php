<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);

global $rootpath;

if (!file_exists($rootpath."/inc/config.php")) {
	print '
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" href="/assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
		<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
		<SCRIPT type="text/javascript" src="/assets/js/jquery/ui.jquery.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
		<div class="warning text-left" style="width:600px">
			<br>
			<span><i class="icon-attention red icon-3x pull-left"></i></span>
			<b class="red uppercase">Ошибка:</b><br><br>
			<b>Конфигурационный файл отсутствует.</b> Необходимо провести <a href="/_install/install.php"><b class="red">установку</b></a>.
			<br><br>
		</div>
		<script type="text/javascript">
			$(".warning").center();
		</script>
	';
	exit;
}

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

if($istimeout) {
	header("Location: techtimeout");
}

if($isremoval) {
	header("Location: removal");
}

setcookie("ses", '', time() - 3600, "/");
setcookie("old", '', time() - 3600, "/");
setcookie("asuser", '', time() - 3600, "/");

/**
 * Добавление индексов
 */
$count = (int)$db -> getOne("SELECT DISTINCT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '$database' and TABLE_NAME = '".$sqlname."dogovor' and INDEX_NAME = 'note'");
if ($count == 0) {
	$db -> query( "ALTER TABLE ".$sqlname."dogovor ADD INDEX `note` (`iduser`, `identity`)" );
}

$rurl   = $_REQUEST['rurl'];
$action = $_REQUEST['action'];

$scheme = $_SERVER['HTTP_SCHEME'] ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

//Авторизация для демо-пользователя
$demo = [
	"login" => "demo@isaler.ru",
	"pass"  => "Demouser!1"
];

$template = '<html lang="ru">
<head>
<STYLE type="text/css">
<!--
BODY {
	color:#000;
	font-size: 14px;
	font-family: arial, tahoma;
	background:#eee;
	padding:0;
	margin:0;
}
div{
	margin:0;
}
hr{
	width:102%;
	border:0px none;
	border-top: #ccc 1px dotted;
	padding:0px; height:1px;
	margin:5px -5px;
	clear:both;
}
.green { color: #349C5A;}
.blue, .blue a, a { color:#00548C;}
.blok{
	background:#FFF;
}
.todo{
	float:left;
	color: #000;
	padding:5px 15px;
}
.letsgo{
	background: #ddd;
	border: 1px solid #ccc;
	padding: 5px 15px;
	font-size:1.2em;
	display: inline-block;
	text-align: center;
	text-decoration: none;
	-moz-border-radius: 2px;
	-webkit-border-radius: 2px;
	border-radius: 2px;
	color:#000;
}
.letsgo:hover{
	background: #c0392b;
	border: 1px solid #C00;
	color:#FFF;
}
.logo img{
	height: 40px;
}
@media (max-width: 989px) {
	.head{
		margin-top:0px;
	}
	.todo{
		font-size: 16px;
	}
	.logo img{
		height: 40px;
		margin-top: -0px;
	}
}
-->
</STYLE>
</head>
<body>
<DIV style="width:98%; max-width:600px; margin: 0 auto">
	<div class="blok head" style="height:50px; margin-top:10px; border:1px solid #DFDFDF; padding:5px; margin-bottom: 10px;" align="left">
		<div class="todo">
			<div class="logo" style="float: left;">
				<a href="'.$productInfo['site'].'"><img src="'.($isCloud ? $productInfo['site']."/register/images/logo.png" : $productInfo['site']."/assets/images/logo.png").'" height="15" style="margin-right:15px" border="0" /></a>
			</div>
		</div>
	</div>
	<div class="blok" style="font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;">
		<div style="color:black; font-size:12px; margin-top: 5px;">
		{html}
		</div>
	</div>

	<div style="font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;" align="right">
		<div>'.$productInfo['name'].' Team</div>
	</div>

</DIV>
</body>
</html>';

if ($action == "enter") {

	//print_r($_POST);

	$account  = false;
	$activate = false;

	$res      = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE login = '$_POST[logi]'");
	$iduser2  = (int)$res["iduser"];
	$pwd2     = $res["pwd"];
	$salt     = $res["sole"];
	$title2   = $res["title"];
	$sec      = $res["secrty"];
	$iduser   = (int)$res["iduser"];
	$identity = $res["identity"];

	//print encodePass($_POST['pwd'], $salt);
	//exit();

	//print_r($res);

	//Составим список всех активных сотрудников
	$users_acsept = [];
	if ( !$isCloud ) {

		$users_acsept = $db -> getCol("SELECT iduser FROM ".$sqlname."user WHERE secrty = 'yes' ORDER by iduser $userlim");

		//print $db -> lastQuery();

	}

	$session = (int)$db -> getOne("select session * 86400 from ".$sqlname."settings WHERE id = '$identity'");
	if ($session < 1) {
		$session = 10 * 86400;
	}

	if ($isCloud) {

		$rest     = $db -> getRow("SELECT * FROM ".$sqlname."activate WHERE identity = '$identity'");
		$activate = $rest["activate"];
		$code     = $rest["code"];

	}

	if ($sec == 'yes') {//если пользователь активен

		if ($_POST['logi'] == '') {
			$reslogin = "Не указан Логин";
		}

		//Если это не облако
		if ($iduser2 > 0 && !$isCloud) {

			if (encodePass($_POST['pwd'], $salt) == $pwd2) {

				if (in_array($iduser2, $users_acsept)) {

					$session = (int)$db -> getOne("select session * 86400 from ".$sqlname."settings WHERE id = '$identity'");
					if ((int)$session == 0) {
						$session = 10 * 86400;
					}

					$sess = preg_replace("#[^a-zA-Z0-9]#i", "s", crypt($_POST['logi'].time(), "$2y$05$".$salt));
					//$sess =

					$db -> query("update ".$sqlname."user set ses='$sess' WHERE iduser='$iduser2'");

					setcookie("ses", $sess, time() + (int)$session, "/");
					setcookie("sess", $sess, time() + (int)$session, "/");
					setcookie("old", '', time() - 3600, "/");
					setcookie("asuser", '', time() - 3600, "/");

					//print "ok";
					//exit();

					logger('0', 'Пользователь авторизовался в системе', $iduser2);

					if (!$rurl) {
						header("Location: /");
					}
					else {
						header("Location: ".$rurl);
					}

				}
				else {

					$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Превышен лимит пользователей</div>';
					logger('0', 'Неудачная авторизация (Превышен лимит пользователей) с параметрами: Логин = '.$_POST['logi'], (int)$iduser1);

				}

			}
			else {

				$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Не правильный логин/пароль</div>';
				logger('0', 'Неудачная авторизация (неверный Логин/Пароль) с параметрами: Логин = '.$_POST['logi'], (int)$iduser1);

			}
		}
		elseif ($iduser2 == 0 && !$isCloud) {

			$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Пользователь <b>'.$_POST['logi'].'</b> не существует</div>';
			logger('0', 'Неудачная авторизация (логин не уществует) с параметрами: Логин = '.$_POST['logi'], (int)$iduser1);

		}

		//для облака
		if ($iduser2 > 0 && $isCloud) {

			if (encodePass($_POST['pwd'], $salt) == $pwd2) {

				if ($activate == 'true') {

					$sess = preg_replace("#[^a-zA-Z0-9]#i", "s", crypt($_POST['logi'] + time(), $pwd2));

					$db -> query("update ".$sqlname."user set ses='$sess' WHERE iduser='$iduser2'");

					setcookie("ses", $sess, time() + $session, "/");
					setcookie("old", '', time() - 3600, "/");
					setcookie("asuser", '', time() - 3600, "/");
					setcookie("rurl", '', time() - 3600, "/");

					logger('0', 'Пользователь авторизовался в системе', $iduser2);

					if (!$rurl || $rurl = 'billing.php') {
						header( "Location: /" );
					}

					else {
						header( "Location: ".$rurl );
					}

				}
				else {
					$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Ваш аккаунт еще не активирован.</div>';
					$action   = 'getcode';
				}
			}
			else {
				$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Не правильный логин/пароль</div>';
				logger('0', 'Неудачная авторизация (неверный Логин/Пароль) с параметрами: Логин = '.$_POST['logi'], $iduser1);
			}

		}
		elseif ($iduser2 == 0 && $isCloud) {

			$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Пользователь <b>'.$_POST['logi'].'</b> не существует</div>';
			logger('0', 'Неудачная авторизация (логин не уществует) с параметрами: Логин = '.$_POST['logi'], (int)$iduser1);

		}

	}
	else {
		logger('0', 'Неудачная авторизация (Аккаунт заблокирован) с параметрами: Логин = '.$_POST['logi'], (int)$iduser1);
		$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Ваш аккаунт заблокирован администратором или не существует!</div>';
	}

}
if ($action == "logout") {

	$iduser1 = $db -> getOne("SELECT iduser FROM ".$sqlname."user WHERE ses='$_COOKIE[ses]'");

	$db -> query("update ".$sqlname."user set ses='' WHERE iduser='$iduser1'");

	logger('1', 'Пользователь вышел из системы', (int)$iduser1);

	setcookie("ses", '', time() - 3600, "/");
	setcookie("old", '', time() - 3600, "/");
	setcookie("asuser", '', time() - 3600, "/");

	$reslogin = '<div class="green div-center mt15"><i class="icon-ok-circled icon-2x green"></i>&nbsp;Вы вышли из системы!</div>';

}
if ($action == "fogot" && $_REQUEST['code'] != '') {

	$res = (int)$db -> getOne("SELECT COUNT(*) FROM ".$sqlname."changepass WHERE code = '$_REQUEST[code]'");

	if ($res == 0) {

		$action   = "";
		$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Не верно указан код</div>';

	}

}
if ($action == "fogot_get") {

	$email = untag($_POST['email']);
	$title = $db -> getOne("SELECT title FROM ".$sqlname."user WHERE email='$email'");

	$code = generateSalt(35);

	if ($title != '') {

		$db -> query("INSERT INTO ".$sqlname."changepass SET ?u", [
			'useremail' => $email,
			'code'      => $code
		]);

		$html = '
		<div style="color:black; font-size:12px; margin-top: 5px;">
		<p><b>Уважаемый '.$title.',</b><br><br>Вы или кто-то другой запросили восстановление пароля в корпоративную CRM-систему <b>'.$productInfo['name'].'</b>. Восстановление пароля возможно только с помощью установки нового пароля.</p>
		<p>
			<div style="width:100%; text-align:center;">
				<a href="'.$productInfo['crmurl'].'/login?action=fogot&code='.$code.'" target="_blank" class="letsgo">Смена пароля</a>
			</div>
		</p>
		<hr><br>
		<p>Перейдите по адресу <b><a href="'.$productInfo['crmurl'].'/login?action=fogot&code='.$code.'" target="_blank">'.$productInfo['crmurl'].'/login.php?action=fogot&code='.$code.'</a></b>, чтобы изменить пароль.</p>
		<p>Если указанная выше ссылка не работает, просто скопируйте её в браузер и нажмите Enter.</p>
		</div>
		';

		//отправляем данные
		$tpl = str_replace('{html}', $html, $template);

		$to       = $email;
		$toname   = $title;
		$from     = "no-replay@".$_SERVER["HTTP_HOST"];
		$fromname = $productInfo['name'];
		$subject  = "Напоминание пароля в ".$productInfo['name'];
		$html     = $tpl;

		//$msgRez = mailer($to, $toname, $from, $fromname, $subject, $html);
		$msgRez = mailto([$to, $toname, $from, $fromname, $subject, $html]);

		if ($msgRez == "") {
			$resultat = '<div class="success black m0 text-center"><i class="icon-ok-circled icon-2x green"></i>&nbsp;Данные для восстановления отправлены на указанный email</div>';
		}
		else {
			$resultat = '<div class="warning black m0 text-center"><i class="icon-attention icon-2x red"></i>&nbsp;Упс. Возникли проблемы - возможно у Вас не настроен почтовый сервер!<br>Попробуйте позже.</div>';
		}

	}
	else {

		$resultat = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;<b class="red">WTF</b> Походу вы ломитесь в чужой огород :).<br>Мы уже настучали администратору.</div>';
		logger('15', 'Пользователь запросил пароль на email: '.$_POST['email'], $iduser1);

	}

}
if ($action == "changepass") {

	$code = $_REQUEST['code'];
	$pwd  = $_REQUEST['pwd'];
	$logi = $_REQUEST['logi'];

	$useremail = $db -> getOne("SELECT useremail FROM ".$sqlname."changepass WHERE code = '$code'");

	if ($isCloud == true) {

		$res      = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE login = '$useremail'");
		$iduser   = $res["iduser"];
		$login    = $res["login"];
		$title    = $res["title"];
		$salt     = $res["sole"];
		$sec      = $res["sec"];
		$identity = $res["identity"];

	}
	if ($isCloud == false) {

		$res    = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE email = '$useremail'");
		$iduser = $res["iduser"];
		$login  = $res["login"];
		$title  = $res["title"];
		$salt   = $res["sole"];
		$sec    = $res["sec"];

	}

	if (!$useremail) {

		$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Не указан логин</div>';

	}
	elseif (!$title) {

		$reslogin = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Код восстановления не верный или пользователь не найден</div>';
		logger('0', 'Неудачная смена пароля с параметрами: Логин = '.$login, $iduser1);

	}
	else {

		$salt    = generateSalt();
		$newpass = encodePass($pwd, $salt);

		$db -> query("UPDATE ".$sqlname."user SET ?u WHERE iduser = '$iduser' and identity = '$identity'", [
			'pwd'  => $newpass,
			'sole' => $salt
		]);

		$db -> query("DELETE FROM ".$sqlname."changepass WHERE code = '$code'");

		if ($iduser and $isCloud != true) {

			$sess = preg_replace("#[^a-zA-Z0-9]#i", ",", crypt($login + time(), $pwd));

			$db -> query("UPDATE ".$sqlname."user SET ses='$sess' WHERE iduser = '$iduser'");

			setcookie("ses", $sess, time() + $session, "/; samesite=strict");

			logger('0', 'Пользователь авторизовался в системе', $iduser);

			if (!$rurl) {
				header( "Location: index.php" );
			}
			else {
				header( "Location: ".$rurl );
			}

		}
		if ($iduser and $isCloud == true) {

			$sess = preg_replace("#[^a-zA-Z0-9]#i", ",", crypt($_POST['logi'] + time(), $newpass));

			$db -> query("UPDATE ".$sqlname."user SET ses='$sess' WHERE iduser = '$iduser'");

			setcookie("ses", $sess, time() + $session, "/; samesite=strict");

			logger('0', 'Пользователь авторизовался в системе', $iduser);

			if (!$rurl) {
				header( "Location: index.php" );
			}
			else {
				header( "Location: ".$rurl );
			}

		}

	}

}
if ($action == "getcode_on") {

	$res      = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE email = '$_POST[email]'");
	$identity = $res["identity"];
	$title    = $res["title"];
	$to       = $res["email"];

	$rest     = $db -> getRow("SELECT * FROM ".$sqlname."activate WHERE identity = '$identity'");
	$activate = $rest["activate"];
	$code     = $rest["code"];

	if ($title != '') {

		$html = '
		<h1 style="color: #222; font: bold 23px arial; margin: 5px 0;">Приветствуем Вас в '.$productInfo['name'].'!</h1>
		<p>Вы зарегистрированы в on-line CRM-системе '.$productInfo['name'].', но еще не активировали свою учетную запись.</p>
		<p>Перейдите по ссылке - <strong><a href="'.$productInfo['crmurl'].'/login?action=activate&code='.$code.'" target="_blank">Активировать</a></strong>, чтобы подтвердить электронный адрес.</p>
		<p>Если указанная выше ссылка не работает, в браузер можно вставить следующую строку: <b>'.$productInfo['crmurl'].'/login.php?action=activate&code='.$code.'</b></p>
		<p>Далее Вас попросят войти в свою учетную запись.</p>
		<hr>
		<p>Спасибо за использование '.$productInfo['name'].'!</p>
		';

		//отправляем данные
		$tpl = str_replace('{html}', $html, $template);

		//$msgRez = mailer($to, $title, $productInfo['info'], 'Уведомления '.$productInfo['name'], 'Код активации в '.$productInfo['name'], $tpl);
		$msgRez = mailto([$to, $title, $productInfo['info'], 'Уведомления '.$productInfo['name'], 'Код активации в '.$productInfo['name'], $tpl]);

		if ($msgRez == "") {
			$resultat = '<div class="blue div-center mt15"><i class="icon-ok-circled icon-2x green"></i>&nbsp;Код активации отправлен на указанный email</div>';
		}

		else {
			$resultat = '<div class="red div-center mt15"><i class="icon-attention icon-2x red"></i>&nbsp;Упс. Возникли проблемы!<br>Попробуйте позже.</div>';
		}

	}

	$action = '';

}
if ($action == "wallpaper"){

	print $bg = display_daily_bing_wallpaper();
	exit();

}

$current = $db -> getOne("SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1");

function display_daily_bing_wallpaper(): string {
	
	global $rootpath;
	global $wallpaper;
	
	if( isset($wallpaper) && file_exists($rootpath."/".$wallpaper) ){
		
		return "/".$wallpaper;
		
	}

	$bing_daily_image   = json_decode(file_get_contents('https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=en-US'), true);
	
	return 'https://www.bing.com'.$bing_daily_image['images'][0]['urlbase'].'_1920x1080.jpg';

}

// $bg = "images/monitor20186.png";
// $bg = display_daily_bing_wallpaper();

$logo = "/assets/images/logo-white.png";
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
	<title>Авторизация</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<link rel="stylesheet" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/app.js.css">
	<link rel="stylesheet" href="/assets/css/ui.jquery.css">
	<?php
	if (file_exists("/assets/css/login.css")) {

		print '<LINK rel="stylesheet" href="/assets/css/login.css">';

	}
	?>
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<SCRIPT src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
	<script src="/assets/js/jquery/ui.jquery.js"></script>
	<script src="/assets/js/moment.js/moment.min.js"></script>
	<script src="/assets/js/app.extended.js"></script>
</head>
<body>

<DIV class="login--container" style="background: url(); background-repeat: no-repeat; background-size: cover; background-color: var(--gray-darkblue);">

	<div class="login--block disable--select">

		<div class="zagolovok"><img src="<?= $logo ?>" height="30"></div>

		<form action="/login" method="post" id="loginform" name="loginform" enctype="multipart/form-data">
			<?php
			if (in_array($action, [
				"fogot_on",
				"",
				"enter",
				"logout",
				"view",
				"fogot_get"
			])) {
				?>
				<input type="hidden" id="action" name="action" value="enter">
				<input type="hidden" id="rurl" name="rurl" value="<?= $rurl ?>">

				<div class="div-center marg3 blue">
					<h2>Авторизация</h2>
				</div>

				<div class="flex-container">

					<div class="flex-string wp100 div-center">
						<input name="logi" type="text" placeholder="Логин" id="logi" width="100%" autocomplete="on" <? if (isset($_GET['demo'])) {
							echo ' value="'.$demo['login'].'"';
						}; ?>>
					</div>
					<div class="flex-string wp100 div-center relativ mt10">
						<input name="pwd" type="password" placeholder="Пароль" id="pwd" width="100%" <? if (isset($_GET['demo'])) {echo ' value="'.$demo['pass'].'"';} ?>>
						<div class="showpass">
							<i class="icon-eye-off hand gray" title="Посмотреть пароль" id="showpass"></i>
						</div>
					</div>

				</div>

				<?php

				if (isset($reslogin)) {
					print $reslogin;
				}
				if (isset($resultat)) {
					print $resultat;
				}

				?>

				<div class="row margtop10 pt10">

					<div class="column grid-5 pt35">
						<a href="/login?action=fogot" class="blue"><i class="icon-arrows-cw"></i>Восстановить пароль</a>
						<?php if ( $isCloud ) { ?>
							<div class="paddtop5">
								<a href="<?= $productInfo['register'] ?>" class="blue"><i class="icon-doc-text"></i>&nbsp;Регистрация</a>&nbsp;<span class="gray3">|</span>&nbsp;<a href="/login?demo" class="blue"><i class="icon-upload-cloud"></i>&nbsp;Демо</a>
							</div>
						<?php } ?>
					</div>
					<div class="column grid-5">
						<a href="javascript:void(0)" onClick="$('#loginform').submit()" class="loginbutton">Войти</a>
					</div>

				</div>

				<div class="hidden"><input name="smit" type="submit"></div>
				<?php
			}
			if ($action == "fogot" && $_REQUEST['code'] == '') {
				?>
				<input name="action" type="hidden" id="action" value="fogot_get">

				<div class="div-center marg3 blue"><h2>Восстановление пароля</h2></div>

				<div class="flex-container">

					<div class="flex-string wp100 div-center">
						<input name="email" type="text" id="email" placeholder="Укажите свой email адрес" width="100%" autocomplete="off" value="<?= $_POST['email'] ?>">
					</div>

				</div>

				<?php

				if (isset($resultat)) {
					print $resultat;
				}

				?>

				<div class="row margtop10 pt10">

					<div class="column grid-5 pt35">
						<a href="/login" class="blue"><i class="icon-lock"></i>Войти</a>
						<?php if ( $isCloud ) { ?>
							<div class="paddtop5">
								<a href="<?= $productInfo['register'] ?>" class="blue"><i class="icon-doc-text"></i>&nbsp;Регистрация</a>&nbsp;<span class="gray3">|</span>&nbsp;<a href="/login.php?demo" class="blue"><i class="icon-upload-cloud"></i>&nbsp;Демо</a>
							</div>
						<?php } ?>
					</div>
					<div class="column grid-5">
						<a href="javascript:void(0)" onClick="$('#loginform').submit()" class="loginbutton">Напомнить</a>
					</div>

				</div>

				<div class="hidden"><input name="smit" type="submit"></div>
				<?php
			}
			if ($action == "fogot" && $_REQUEST['code'] != '') {

				$useremail = $db -> getOne("SELECT useremail FROM ".$sqlname."changepass WHERE code='".$_REQUEST['code']."'");
				?>
				<input type="hidden" id="action" name="action" value="changepass">
				<input type="hidden" id="code" name="code" value="<?= $_REQUEST['code'] ?>">

				<div class="div-center marg3 blue"><h2>Смена пароля</h2></div>

				<div class="flex-container">

					<div class="flex-string wp100 relativ mt10">
						<input name="pwd" type="password" placeholder="Задайте новый пароль" id="pwd" width="100%" <? if (isset($_GET['demo'])) {
							echo ' value="'.$demo['pass'].'"';
						}; ?> >
						<div class="showpass">
							<i class="icon-eye-off hand gray" title="Посмотреть пароль" id="showpass"></i></div>
						<div id="passstrength">Пароль не задан</div>
					</div>

				</div>

				<?php

				if (isset($reslogin)) {
					print $reslogin;
				}
				if (isset($resultat)) {
					print $resultat;
				}

				?>

				<div class="row margtop10 pt10">

					<div class="column grid-5 pt35">
						<a href="/login" class="blue"><i class="icon-lock"></i>Войти</a>
					</div>
					<div class="column grid-5">
						<a href="javascript:void(0)" onClick="$('#loginform').submit()" class="loginbutton">Сохранить</a>
					</div>

				</div>

				<div class="hidden"><input name="smit" type="submit"></div>
				<?php
			}
			if ($action == "getcode") {
				?>
				<input name="action" type="hidden" id="action" value="getcode_on">

				<div class="div-center marg3 blue"><h2>Активация аккаунта</h2></div>

				<div class="flex-container">

					<div class="flex-string wp100 div-center">
						<input name="email" type="text" id="email" placeholder="Укажите свой email адрес" width="100%" autocomplete="off" value="<?= $_POST['email'] ?>">
					</div>
					<div class="flex-string wp100 warning mt10 black text-center">
						Ваш аккаунт еще не активирован. Чтобы <b>повторно</b> получить код активации укажите свой email.
					</div>

				</div>

				<div class="row margtop10 paddtop10">

					<div class="column grid-4 pt35">
						<a href="/login" class="blue"><i class="icon-lock"></i>Войти</a>
						<?php if ( $isCloud ) { ?>
							<div class="paddtop5">
								<a href="<?= $productInfo['register'] ?>" class="blue"><i class="icon-doc-text"></i>&nbsp;Регистрация</a>&nbsp;<span class="gray3">|</span>&nbsp;<a href="/login.php?demo" class="blue"><i class="icon-upload-cloud"></i>&nbsp;Демо</a>
							</div>
						<?php } ?>
					</div>
					<div class="column grid-6">
						<a href="javascript:void(0)" onClick="$('#loginform').submit()" class="loginbutton w140">Получить&nbsp;код</a>
					</div>

				</div>

				<div class="hidden"><input name="smit" type="submit"></div>
				<?php
			}
			?>
		</form>

		<div class="copy flex-container wp100">
			<div class="flex-string wp50">Сделано в России.</div>
			<div class="flex-string text-right wp50">v. <b><?= $bdVersion ?></b>, build <?= $sysVersion['build'] ?>
			</div>
		</div>

		<?php
		if ($current == '') {

			print '<div class="warning"><i class="icon-attention red"></i> Проблемы с подключением к Базе данных</div>';

		}
		?>

	</div>

</DIV>

<script>

	$(function () {

		//var img = "<?=$bg?>";
		//if (img !== '') $('.login--container').css({"background": "url(" + img + ")"});
		//else $('.login--container').css({"background": "url(/assets/images/monitor20186.png)"});

		display_daily_bing_wallpaper()

	});

	$(document).on('click','#showpass',function () {

		var prop = $('#pwd').prop('type');

		console.log(prop);

		if (prop === 'password') $('#pwd').prop('type', 'text');
		else $('#pwd').prop('type', 'password');

		$('.showpass').find('i').toggleClass('icon-eye icon-eye-off');

	});

	$(document).on('mouseleave','#showpass',function () {

		var prop = $('#pwd').prop('type');
		if (prop === 'text') {

			$('#pwd').prop('type', 'password');
			$('.showpass').find('i').toggleClass('icon-eye-off icon-eye');

		}

	});

	$(document).on('keyup','#pwd',function () {
		checkuserpass();
	});

	function checkuserpass() {

		var strongRegex = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
		var mediumRegex = new RegExp("^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
		var enoughRegex = new RegExp("(?=.{6,}).*", "g");
		var userpass = $('#pwd').val();

		if (false == enoughRegex.test(userpass)) {
			$('#passstrength').html('<i class="icon-thumbs-down-alt red"></i>&nbsp;<span class="red">Должно быть больше 6 символов</span>');
			//$('#pwd').removeClass('good').addClass('bad');
		} else if (strongRegex.test(userpass)) {
			$('#passstrength').removeClass().addClass('green');
			$('#passstrength').html('<i class="icon-ok-circled"></i>&nbsp;<b>Сложный</b>. Великолепно!');
			//$('#pwd').removeClass('bad').addClass('good');
		} else if (mediumRegex.test(userpass)) {
			$('#passstrength').removeClass().addClass('blue');
			$('#passstrength').html('<i class="icon-ok-circled"></i>&nbsp;<b>Средний</b>. Еще немного!');
			//$('#pwd').removeClass('bad').addClass('good');
		} else {
			$('#passstrength').removeClass().addClass('red');
			$('#passstrength').html('<i class="icon-thumbs-down-alt"></i>&nbsp;<b>Проверь раскладку клавиатуры</b>. Подумай еще!');
			//$('#pwd').removeClass('good').addClass('bad');
		}
		return true;
	}

	function display_daily_bing_wallpaper(){

		$.get('/login?action=wallpaper', function(data){

			if (data !== '') $('.login--container').css({"background": "url(" + data + ")"});
			else $('.login--container').css({"background": "url(/assets/images/monitor20186.png)"});

		})

	}

</script>
</body>
</html>