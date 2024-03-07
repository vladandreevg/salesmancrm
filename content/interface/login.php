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
if ($action == "activate") {

	$code = $_REQUEST['code'];

	$res      = $db -> getRow("SELECT * FROM ".$sqlname."activate WHERE code = '$code'");
	$identity = $res["identity"];
	$activate = $res["activate"];

	if ($identity > 0 && $activate != 'true') {

		$db -> query("update ".$sqlname."activate set activate='true' WHERE identity = '$identity'");

		//внесем данные в биллинг
		$db -> query("INSERT INTO ".$sqlname."payments_balance SET ?u", [
			'id'       => $identity,
			'identity' => $identity
		]);

		/**
		 * Партнерка. НЕ АКТИВНО
		 */

		//если промокод есть и правильный начислим 300 бб
		/*
		$opts = [
			'host'    => PDB_HOST,
			'user'    => PDB_USER,
			'pass'    => PDB_PASS,
			'db'      => PDB_NAME,
			'errmode' => 'exception',
			'charset' => 'UTF8'
		];

		try {

			$db_connection = new SafeMySQL($opts);

			$insert_promo = $db_connection -> getOne("SELECT promo FROM partner_identity_promo WHERE identity='$identity'");

			if ($insert_promo != '') {

				$col_promo = $db_connection -> getOne("SELECT COUNT(user_code) FROM partner_code WHERE user_code = '$insert_promo'");

				if ($col_promo > 0) {

					//начисляем входные 300 бонусов для пришедших от партнеров
					$db_connection -> query("UPDATE ".$sqlname."payments_balance SET balance_bonuses='300' WHERE id = '$identity'");

				}

			}


		}
		catch (Exception $e) {

			$err = '<b class="red">'.$e -> getMessage().'</b>';

		}
		*/

		/*
		 * Добавлем стартовые данные
		 */

		//Типы активности
		$app_activities = [
			[
				'title'     => 'Первичный звонок',
				'color'     => '#009900',
				'resultat'  => 'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП',
				'isDefault' => 'no',
				'aorder'    => 4,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'Факс',
				'color'     => '#cc00cc',
				'resultat'  => 'Отправлен и получен;Отправлен;Не отвечает;Не принимают',
				'isDefault' => 'no',
				'aorder'    => 12,
				'filter'    => 'activ',
				'identity'  => $identity
			],
			[
				'title'     => 'Встреча',
				'color'     => '#ffcc00',
				'resultat'  => 'Состоялась;Перенос сроков;Отменена;Отпала необходимость',
				'isDefault' => 'no',
				'aorder'    => 1,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'Задача',
				'color'     => '#ff9900',
				'resultat'  => 'Не выполнено;Перенос сроков;Отложено;Выполнено',
				'isDefault' => 'yes',
				'aorder'    => 2,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'Предложение',
				'color'     => '#66ccff',
				'resultat'  => 'Перенос;Отправлено КП;Отменено',
				'isDefault' => 'no',
				'aorder'    => 10,
				'filter'    => 'activ',
				'identity'  => $identity
			],
			[
				'title'     => 'Событие',
				'color'     => '#666699',
				'resultat'  => 'Выполнено;Перенос;Отложено',
				'isDefault' => 'no',
				'aorder'    => 11,
				'filter'    => 'activ',
				'identity'  => $identity
			],
			[
				'title'     => 'исх.Почта',
				'color'     => '#cccc00',
				'resultat'  => 'Отправлено КП;Отправлен Договор;Отправлена Презентация;Отправлена информация',
				'isDefault' => 'no',
				'aorder'    => 7,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'вх.Звонок',
				'color'     => '#99cc00',
				'resultat'  => 'Новое обращение;Запрос счета;Запрос КП;Приглашение;Договорились о встрече',
				'isDefault' => 'no',
				'aorder'    => 3,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'вх.Почта',
				'color'     => '#cc3300',
				'resultat'  => 'Отправлено;Не верный адрес;Отложено;Отменено',
				'isDefault' => 'no',
				'aorder'    => 6,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'Поздравление',
				'color'     => '#009999',
				'resultat'  => 'Новый год;День Рождения;Праздник',
				'isDefault' => 'no',
				'aorder'    => 9,
				'filter'    => 'task',
				'identity'  => $identity
			],
			[
				'title'     => 'исх.2.Звонок',
				'color'     => '#339966',
				'resultat'  => 'Не дозвонился;Нет на месте;Отказ;Переговорили;Запрос КП',
				'isDefault' => 'no',
				'aorder'    => 5,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'Отправка КП',
				'color'     => '#ff0000',
				'resultat'  => 'Отправлено;Перенесено;Отложено;Отменено',
				'isDefault' => 'no',
				'aorder'    => 8,
				'filter'    => 'all',
				'identity'  => $identity
			],
			[
				'title'     => 'Холодный звонок',
				'color'     => '#99ccff',
				'resultat'  => 'Интересно;Отказ;Жесткий отказ',
				'isDefault' => 'no',
				'aorder'    => 0,
				'filter'    => 'activ',
				'identity'  => $identity
			]
		];

		foreach ($app_activities as $data) {

			$db -> query("INSERT INTO ".$sqlname."activities SET ?u", $data);

		}

		//категории бюджета
		$app_budjet_cat = [
			[
				"title"    => "Расходы на офис",
				"tip"      => "rashod",
				'identity' => $identity,
				'sub'      => [
					[
						"title"    => "Аренда офиса",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Телефония",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Продукты питания",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Оборудование",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Почтовые отправления",
						"tip"      => "rashod",
						'identity' => $identity
					]
				]
			],
			[
				"title"    => "Прочие поступления",
				"tip"      => "dohod",
				'identity' => $identity,
				'sub'      => [
					[
						"title"    => "Инвестиции",
						"tip"      => "dohod",
						'identity' => $identity
					],
					[
						"title"    => "Наличка",
						"tip"      => "dohod",
						'identity' => $identity
					]
				]
			],
			[
				"title"    => "Сотрудники",
				"tip"      => "rashod",
				'identity' => $identity,
				'sub'      => [
					[
						"title"    => "Оклад",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Премия",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Командировочные",
						"tip"      => "rashod",
						'identity' => $identity
					]
				]
			],
			[
				"title"    => "Реклама",
				"tip"      => "rashod",
				'identity' => $identity,
				'sub'      => [
					[
						"title"    => "Интернет-реклама",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Вебинары",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Direct Mail",
						"tip"      => "rashod",
						'identity' => $identity
					]
				]
			],
			[
				"title"    => "Расчеты с контрагентами",
				"tip"      => "rashod",
				'identity' => $identity,
				'sub'      => [
					[
						"title"    => "Поставщики",
						"tip"      => "rashod",
						'identity' => $identity
					],
					[
						"title"    => "Партнеры",
						"tip"      => "rashod",
						'identity' => $identity
					]
				]
			]
		];

		foreach ($app_budjet_cat as $bj) {

			$sub = $bj['sub'];
			unset($bj['sub']);

			$db -> query("INSERT INTO ".$sqlname."budjet_cat SET ?u", $bj);
			$id = $db -> insertId();

			foreach ($sub as $item) {

				$item['subid'] = $id;

				$db -> query("INSERT INTO ".$sqlname."budjet_cat SET ?u", $item);

			}

		}

		//Отрасли клиента
		$app_category = [
			[
				"title"    => "Промышленность",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Информационные технологии",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Строительство",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Торговля. Опт",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Телекоммуникации",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Торговля. Розница",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Энергетика",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Государственные организации",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Физические лица",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Академические организации",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Политические организации",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Медицина",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Агропром",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Частные предприниматели",
				"tip"      => "client",
				'identity' => $identity
			],
			[
				"title"    => "Розница",
				"tip"      => "client",
				'identity' => $identity
			],
		];

		foreach ($app_category as $cat) {

			$db -> query("INSERT INTO ".$sqlname."category SET ?u", $cat);

		}

		//Источники клиента
		$app_clientpath = [
			[
				"name"        => "Личные связи",
				"isDefault"   => "",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Маркетинг",
				"isDefault"   => "",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Справочник",
				"isDefault"   => "yes",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Входящий контакт",
				"isDefault"   => "",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Рассылка с сайта",
				"isDefault"   => "",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Заказ с сайта",
				"isDefault"   => "yes",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Рекомендации клиентов",
				"isDefault"   => "",
				"utm_source"  => "fromfriend",
				"destination" => "",
				'identity'    => $identity
			],
			[
				"name"        => "Интернет",
				"isDefault"   => "",
				"utm_source"  => "",
				"destination" => "",
				'identity'    => $identity
			]
		];

		foreach ($app_clientpath as $path) {

			$db -> query("INSERT INTO ".$sqlname."clientpath SET ?u", $path);

		}

		//Этапы сделок
		$app_dogcategory = [
			[
				"title"    => "0",
				"content"  => "Проявлен/Выявлен интерес",
				"identity" => $identity
			],
			[
				"title"    => "20",
				"content"  => "Подтвержден интерес",
				"identity" => $identity
			],
			[
				"title"    => "40",
				"content"  => "Отправлено КП",
				"identity" => $identity
			],
			[
				"title"    => "60",
				"content"  => "Обсуждение деталей - продукты, услуги, оплата",
				"identity" => $identity
			],
			[
				"title"    => "80",
				"content"  => "Согласован договор, Выставлен счет",
				"identity" => $identity
			],
			[
				"title"    => "90",
				"content"  => "Получена предоплата, Выполнение договора",
				"identity" => $identity
			],
			[
				"title"    => "100",
				"content"  => "Закрытие сделки, Подписание документов",
				"identity" => $identity
			]
		];

		foreach ($app_dogcategory as $step) {

			$db -> query("INSERT INTO ".$sqlname."dogcategory SET ?u", $step);

		}

		//Контрольные точки
		$app_complect_cat = [
			[
				"title"    => "Подготовка и отправка КП",
				"corder"   => 1,
				"dstep"    => "40",
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			],
			[
				"title"    => "Согласование спецификации",
				"corder"   => 2,
				"dstep"    => "60",
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			],
			[
				"title"    => "Подписать договор, Выставить счет",
				"corder"   => 3,
				"dstep"    => "80",
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			],
			[
				"title"    => "Получение оплаты",
				"corder"   => 4,
				"dstep"    => "90",
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			],
			[
				"title"    => "Начать работы",
				"corder"   => 5,
				"dstep"    => "0",
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			],
			[
				"title"    => "Получить документы",
				"corder"   => 6,
				"dstep"    => 8,
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			],
			[
				"title"    => "Работы выполнены",
				"corder"   => 7,
				"dstep"    => "",
				"role"     => "",
				"users"    => "",
				"identity" => $identity
			]
		];

		foreach ($app_complect_cat as $cp) {

			$cp['dstep'] = ($cp['dstep'] != '') ? $db -> getOne("SELECT idcategory FROM ".$sqlname."dogcategory WHERE identity = '$identity'") : 0;

			$db -> query("INSERT INTO ".$sqlname."complect_cat SET ?u", $cp);

		}

		//Типы документов
		$app_contract_type = [
			[
				"title"    => "Договор",
				"type"     => "get_dogovor",
				"num"      => 0,
				"identity" => $identity
			],
			[
				"title"    => "Акт приема-передачи",
				"type"     => "get_akt",
				"num"      => 0,
				"identity" => $identity
			],
			[
				"title"    => "Холодное предложение",
				"num"      => 0,
				"identity" => $identity
			],
			[
				"title"    => "Квитанция в банк",
				"num"      => 0,
				"format"   => "{cnum}",
				"identity" => $identity
			],
			[
				"title"    => "Акт сервисный",
				"type"     => "get_aktper",
				"num"      => 0,
				"identity" => $identity
			],
			[
				"title"    => "Заказ-наряд",
				"num"      => 0,
				"format"   => "{cnum}",
				"identity" => $identity
			],
			[
				"title"    => "Дополнительное соглашение",
				"num"      => 0,
				"format"   => "{cnum}",
				"identity" => $identity
			],
			[
				"title"    => "Товарная накладная",
				"num"      => 0,
				"format"   => "{cnum}",
				"identity" => $identity
			],
			[
				"title"    => "Счет-фактура",
				"num"      => 0,
				"format"   => "{cnum}",
				"identity" => $identity
			]
		];

		foreach ($app_contract_type as $doc) {

			$db -> query("INSERT INTO ".$sqlname."contract_type SET ?u", $doc);

		}

		//Направления деятельности
		$db -> query("INSERT INTO ".$sqlname."direction SET ?u", [
			'title'     => 'Основное',
			'isDefault' => 'yes',
			'identity'  => $identity
		]);

		//Статусы закрытых сделок
		$app_dogstatus = [
			[
				"title"        => "Победа полная",
				"content"      => "Обозначает выигрыш, Договор выполнен и получена прибыль",
				"result_close" => "win",
				"identity"     => $identity
			],
			[
				"title"        => "Победа, договорились с конкурентами",
				"content"      => "Сделка выиграна, заключен и исполнен договор, получена прибыль",
				"result_close" => "win",
				"identity"     => $identity
			],
			[
				"title"        => "Проигрыш по цене",
				"content"      => "Договор не заключен, проиграли по цене",
				"result_close" => "lose",
				"identity"     => $identity
			],
			[
				"title"        => "Проигрыш, договорились с конкурентами",
				"content"      => "Сделка проиграна, но удалось договориться с конкурентами.",
				"result_close" => "lose",
				"identity"     => $identity
			],
			[
				"title"        => "Отменена Заказчиком",
				"content"      => "Сделка отменена Заказчиком",
				"result_close" => "lose",
				"identity"     => $identity
			],
			[
				"title"        => "Отказ от участия",
				"content"      => "Мы отказались от участия в сделке",
				"result_close" => "lose",
				"identity"     => $identity
			],
		];

		foreach ($app_dogstatus as $status) {

			$db -> query("INSERT INTO ".$sqlname."dogstatus SET ?u", $status);

		}

		//Типы сделок
		$app_dogtips = [
			[
				"title"     => "Продажа простая",
				"isDefault" => "yes",
				"identity"  => $identity
			],
			[
				"title"    => "Продажа с разработкой",
				"identity" => $identity
			],
			[
				"title"    => "Услуги",
				"identity" => $identity
			],
			[
				"title"    => "Продажа услуг",
				"identity" => $identity
			],
			[
				"title"    => "Тендер",
				"identity" => $identity
			],
			[
				"title"    => "Ежемесячный",
				"identity" => $identity
			],
			[
				"title"    => "Сервисная",
				"identity" => $identity
			],
			[
				"title"    => "Продажа быстрая",
				"identity" => $identity
			],
		];

		foreach ($app_dogtips as $tip) {

			$db -> query("INSERT INTO ".$sqlname."dogtips SET ?u", $tip);

		}

		//Набор полей
		$app_field_client = [
			[
				"fld_tip"      => "client",
				"fld_name"     => "title",
				"fld_title"    => "Название",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 1,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "iduser",
				"fld_title"    => "Ответственный",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 2,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "idcategory",
				"fld_title"    => "Отрасль",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 3,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "head_clid",
				"fld_title"    => "Головн. орг-ия",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 4,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "phone",
				"fld_title"    => "Телефон",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 5,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "mail_url",
				"fld_title"    => "Почта",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 6,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "site_url",
				"fld_title"    => "Сайт",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 7,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "address",
				"fld_title"    => "Адрес",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 8,
				"fld_stat"     => "yes",
				"fld_temp"     => "adres",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "pid",
				"fld_title"    => "Осн. контакт",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 9,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "fax",
				"fld_title"    => "Факс",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 10,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "clientpath",
				"fld_title"    => "Источник клиента",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 11,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "territory",
				"fld_title"    => "Территория",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 12,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "tip_cmr",
				"fld_title"    => "Тип отношений",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 13,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "scheme",
				"fld_title"    => "Принятие решений",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 14,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "des",
				"fld_title"    => "Описание",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 15,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "recv",
				"fld_title"    => "Реквизиты",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 16,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input1",
				"fld_title"    => "Перспективность",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 17,
				"fld_stat"     => "no",
				"fld_temp"     => "select",
				"fld_var"      => "0 - без перспектив,1 - разовые закупки,2 - постоянные небольшие закупки,3 - постоянные закупки,4 - большие партии,5 - дистрибьютор",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input2",
				"fld_title"    => "День рождения",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 18,
				"fld_stat"     => "no",
				"fld_temp"     => "datum",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input3",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 19,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input4",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 20,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input5",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 21,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input6",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 22,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input7",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 23,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input8",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 24,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input9",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 25,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input10",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 26,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input11",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 27,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input12",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 28,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input13",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 29,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input14",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 30,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input15",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 31,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input16",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 32,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input17",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_order"    => 33,
				"fld_on"       => "no",
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input18",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 34,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input19",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 35,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "client",
				"fld_name"     => "input20",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 36,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			]
		];

		foreach ($app_field_client as $fields) {

			$db -> query("INSERT INTO ".$sqlname."field SET ?u", $fields);

		}

		$app_field_person = [
			[
				"fld_tip"      => "person",
				"fld_name"     => "person",
				"fld_title"    => "Ф.И.О.",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 1,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "ptitle",
				"fld_title"    => "Должность",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 2,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "clid",
				"fld_title"    => "Клиент",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 3,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "iduser",
				"fld_title"    => "Куратор",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 4,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "tel",
				"fld_title"    => "Тел.",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 5,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "fax",
				"fld_title"    => "Факс",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 6,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "mob",
				"fld_title"    => "Моб.",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 7,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "clientpath",
				"fld_title"    => "Источник клиента",
				"fld_required" => "required",
				"fld_order"    => 8,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "mail",
				"fld_title"    => "Почта",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 9,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "loyalty",
				"fld_title"    => "Лояльность",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 10,
				"fld_stat"     => "yes",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input1",
				"fld_title"    => "Дата рождения",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 11,
				"fld_stat"     => "no",
				"fld_temp"     => "datum",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "rol",
				"fld_title"    => "Роль",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 12,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "social",
				"fld_title"    => "Прочее",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 13,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input2",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_order"    => 14,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input3",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 15,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input4",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 16,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input5",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 17,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input6",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 18,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input7",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 19,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input8",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 20,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input9",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 21,
				"fld_stat"     => "no",
				"fld_temp"     => "--Обычное--",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input10",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 21,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input11",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 22,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input12",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 23,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input13",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 24,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input14",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 25,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "person",
				"fld_name"     => "input15",
				"fld_title"    => "доп.поле",
				"fld_required" => null,
				"fld_on"       => "no",
				"fld_order"    => 26,
				"fld_stat"     => "no",
				"identity"     => $identity
			]
		];

		foreach ($app_field_person as $fields) {

			$db -> query("INSERT INTO ".$sqlname."field SET ?u", $fields);

		}

		$app_field_dogovor = [
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "des",
				"fld_title" => "Примечание",
				"fld_on"    => "yes",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "kol",
				"fld_title" => "Сумма план.",
				"fld_on"    => "yes",
				"fld_stat"  => "yes",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "kol_fact",
				"fld_title" => "Сумма факт.",
				"fld_on"    => "yes",
				"fld_stat"  => "yes",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "marg",
				"fld_title" => "Прибыль",
				"fld_on"    => "yes",
				"fld_stat"  => "yes",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "oborot",
				"fld_title" => "Бюджет",
				"fld_on"    => "yes",
				"fld_stat"  => "yes",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "zayavka",
				"fld_title" => "Номер заявки",
				"fld_on"    => "no",
				"fld_order" => 1,
				"fld_stat"  => "no",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "ztitle",
				"fld_title" => "Основание",
				"fld_on"    => "no",
				"fld_order" => 1,
				"fld_stat"  => "no",
				"identity"  => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "mcid",
				"fld_title"    => "Компания",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 2,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "iduser",
				"fld_title"    => "Куратор",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 3,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "datum_plan",
				"fld_title"    => "Дата план.",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 4,
				"fld_stat"     => "yes",
				"fld_temp"     => "datum",
				"identity"     => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "period",
				"fld_title" => "Период действия",
				"fld_on"    => "yes",
				"fld_order" => 5,
				"fld_stat"  => "no",
				"identity"  => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "idcategory",
				"fld_title"    => "Этап",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 6,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"   => "dogovor",
				"fld_name"  => "dog_num",
				"fld_title" => "Договор",
				"fld_on"    => "yes",
				"fld_order" => 7,
				"fld_stat"  => "no",
				"identity"  => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "tip",
				"fld_title"    => "Тип сделки",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 8,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "direction",
				"fld_title"    => "Направление",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_order"    => 9,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "adres",
				"fld_title"    => "Адрес",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 10,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "money",
				"fld_title"    => "Деньги",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 11,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "content",
				"fld_title"    => "Описание",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 12,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "pid_list",
				"fld_title"    => "Персоны",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 13,
				"fld_stat"     => "no",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "payer",
				"fld_title"    => "Плательщик",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 14,
				"fld_stat"     => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input1",
				"fld_title"    => "Адрес Доставки",
				"fld_required" => null,
				"fld_on"       => "yes",
				"fld_order"    => 21,
				"fld_temp"     => "adres",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input2",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 23,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input3",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 24,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input4",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 25,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input5",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 26,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input6",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 22,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input7",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 20,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input8",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 27,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input9",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 28,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input10",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 29,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input11",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 30,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input12",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 31,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input13",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 32,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input14",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 33,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input15",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 34,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input16",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 35,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input17",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 36,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input18",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 37,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input19",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 38,
				"identity"     => $identity
			],
			[
				"fld_tip"      => "dogovor",
				"fld_name"     => "input20",
				"fld_title"    => "доп.поле",
				"fld_on"       => "no",
				"fld_required" => null,
				"fld_order"    => 39,
				"identity"     => $identity
			]
		];

		foreach ($app_field_dogovor as $fields) {

			$db -> query("INSERT INTO ".$sqlname."field SET ?u", $fields);

		}

		$app_field_price = [
			[
				"fld_tip"      => "price",
				"fld_name"     => "price_in",
				"fld_title"    => "Закуп",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"identity"     => $identity
			],
			[
				"fld_tip"      => "price",
				"fld_name"     => "price_1",
				"fld_title"    => "Розница",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_var"      => "35",
				"identity"     => $identity
			],
			[
				"fld_tip"   => "price",
				"fld_name"  => "price_2",
				"fld_title" => "Уровень 1",
				"fld_on"    => "yes",
				"identity"  => $identity
			],
			[
				"fld_tip"      => "price",
				"fld_name"     => "price_3",
				"fld_title"    => "Уровень 2",
				"fld_required" => "required",
				"fld_on"       => "yes",
				"fld_var"      => "20",
				"identity"     => $identity
			],
			[
				"fld_tip"   => "price",
				"fld_name"  => "price_4",
				"fld_title" => "Уровень 3",
				"fld_var"   => "15",
				"identity"  => $identity
			],
			[
				"fld_tip"   => "price",
				"fld_name"  => "price_5",
				"fld_title" => "Уровень 4",
				"fld_var"   => "10",
				"identity"  => $identity
			]
		];

		foreach ($app_field_price as $fields) {

			$db -> query("INSERT INTO ".$sqlname."field SET ?u", $fields);

		}

		//Папки файлов
		$app_file_cat = [
			[
				"title"    => "Спецификации",
				"shared"   => "yes",
				"identity" => $identity
			],
			[
				"title"    => "Презентации",
				"shared"   => "yes",
				"identity" => $identity
			],
			[
				"title"    => "Изображения",
				"shared"   => "yes",
				"identity" => $identity
			],
			[
				"title"    => "Разное",
				"shared"   => "yes",
				"identity" => $identity
			],
			[
				"title"    => "Документы",
				"shared"   => "yes",
				"identity" => $identity
			]
		];

		foreach ($app_file_cat as $data) {

			$db -> query("INSERT INTO ".$sqlname."file_cat SET ?u", $data);

		}

		//Типы лояльности
		$app_loyal_cat = [
			[
				"title"    => "0 - Не лояльный",
				"color"    => "#333333",
				"identity" => $identity
			],
			[
				"title"    => "4 - Очень Лояльный",
				"color"    => "#ff0000",
				"identity" => $identity
			],
			[
				"title"    => "2 - Нейтральный",
				"color"    => "#99ccff",
				"identity" => $identity
			],
			[
				"title"    => "3 - Лояльный",
				"color"    => "#ff00ff",
				"identity" => $identity
			],
			[
				"title"     => "1 - Пока не понятно",
				"color"     => "#CCCCCC",
				"isDefault" => "yes",
				"identity"  => $identity
			],
			[
				"title"    => "5 - ВиП",
				"color"    => "#cedb9c",
				"identity" => $identity
			],
		];

		foreach ($app_loyal_cat as $data) {

			$db -> query("INSERT INTO ".$sqlname."loyal_cat SET ?u", $data);

		}

		$company = $db -> getOne("SELECT company FROM ".$sqlname."settings WHERE id = '$identity'");

		//Свои компании
		$mycomp = [
			'name_ur'       => 'Общество с ограниченной ответственностью ”'.$company.'”',
			'name_shot'     => 'ООО ”'.$company.'”',
			'dir_name'      => 'Директора Иванова Ивана Ивановича',
			'dir_signature' => 'Иванов И.И.',
			'dir_status'    => 'Директор',
			'dir_osnovanie' => 'Устава',
			'identity'      => $identity
		];
		$db -> query("INSERT INTO ".$sqlname."mycomps SET ?u", $mycomp);
		$id = $db -> insertId();

		//Расчетный счет
		$recv = [
			'cid'        => $id,
			'title'      => 'Основной счет',
			'rs'         => '00000',
			'bankr'      => '045744863;30101810300000000863;Филиал ОАО «Банк»',
			'tip'        => 'bank',
			'ostatok'    => 0.00,
			'isDefault'  => 'yes',
			'ndsDefault' => '18',
			'identity'   => $identity
		];
		$db -> query("INSERT INTO ".$sqlname."mycomps_recv SET ?u", $recv);

		//Офис компании
		$db -> query("INSERT INTO ".$sqlname."office_cat SET ?u", [
			'title'    => 'Основной',
			'identity' => $identity
		]);

		//Отделы
		$db -> query("INSERT INTO ".$sqlname."otdel_cat SET ?u", [
			'title'    => 'Отдел активных продаж',
			'identity' => $identity
		]);

		//Профиль
		$app_profile_cat = [
			[
				"name"     => "Количество сотрудников в отделе снабжения",
				"tip"      => "select",
				"value"    => "1-3;3-5;больше 5",
				"ord"      => 16,
				"pole"     => "pole1",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Как часто проводят закупки",
				"tip"      => "select",
				"value"    => "1 раз в мес.; 2 раза в мес.;больше 2-х раз в мес.",
				"ord"      => 3,
				"pole"     => "pole2",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Тендерный отдел",
				"tip"      => "radio",
				"value"    => "Нет;Есть",
				"ord"      => 13,
				"pole"     => "pole3",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Проводят тендеры",
				"tip"      => "radio",
				"value"    => "Электронные площадки;Самостоятельно;Оба варианта;Не проводят",
				"ord"      => 15,
				"pole"     => "pole4",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Примечание",
				"tip"      => "text",
				"ord"      => 18,
				"pole"     => "pole5",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Какие продукты можем предложить?",
				"tip"      => "checkbox",
				"value"    => "Зап.части;Шины;Диски;Элементы кузова;Внедрение телефонии;Внедрение серверов;1С в облаке;Настройка VPN",
				"ord"      => 4,
				"pole"     => "pole8",
				"pwidth"   => 100,
				'identity' => $identity
			],
			[
				"name"     => "Объем закупок в месяц",
				"tip"      => "radio",
				"value"    => "<100т.р.;100-200 т.р.;200-300 т.р.;300-500 т.р.;>500 т.р.",
				"ord"      => 5,
				"pole"     => "pole9",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Тип клиента для нас",
				"tip"      => "radio",
				"value"    => "Не работаем;Ведем переговоры;С нами не будут работать;Работают только с нами",
				"ord"      => 12,
				"pole"     => "pole10",
				"pwidth"   => 100,
				'identity' => $identity
			],
			[
				"name"     => "Что покупают постоянно",
				"tip"      => "checkbox",
				"value"    => "ГСМ;Автохимия;Зап.части;Диски",
				"ord"      => 8,
				"pole"     => "pole11",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Годовой оборот",
				"tip"      => "radio",
				"value"    => "до 1млн.;свыше 1млн. до 20млн.;свыше 20млн. до 100млн.",
				"ord"      => 11,
				"pole"     => "pole12",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Специализация",
				"tip"      => "input",
				"ord"      => 17,
				"pole"     => "pole19",
				"pwidth"   => 100,
				'identity' => $identity
			],
			[
				"name"     => "Возможности по продаже",
				"tip"      => "divider",
				"ord"      => 1,
				"pole"     => "pole15",
				"pwidth"   => 100,
				'identity' => $identity
			],
			[
				"name"     => "Интересы клиента",
				"tip"      => "divider",
				"ord"      => 7,
				"pole"     => "pole16",
				"pwidth"   => 100,
				'identity' => $identity
			],
			[
				"name"     => "Количество филиалов",
				"tip"      => "radio",
				"value"    => "нет филиалов;1 - 3;3 - 10;10 - 20;20 -50;50 - 100",
				"ord"      => 10,
				"pole"     => "pole18",
				"pwidth"   => 50,
				'identity' => $identity
			],
			[
				"name"     => "Информация о клиенте",
				"tip"      => "divider",
				"ord"      => 9,
				"pole"     => "pole90",
				"pwidth"   => 100,
				'identity' => $identity
			],
			[
				"name"     => "С кем работают",
				"tip"      => "checkbox",
				"value"    => "B2B;B2C",
				"ord"      => 2,
				"pole"     => "pole175",
				"pwidth"   => 30,
				'identity' => $identity
			],
		];

		foreach ($app_profile_cat as $data) {

			$db -> query("INSERT INTO ".$sqlname."profile_cat SET ?u", $data);

		}

		//Типы отношений
		$app_relations = [
			[
				"title"    => "0 - Не работаем",
				"color"    => "#333333",
				'identity' => $identity
			],
			[
				"title"     => "1 - Холодный клиент",
				"color"     => "#99ccff",
				"isDefault" => "yes",
				'identity'  => $identity
			],
			[
				"title"    => "2 - Потенциальный клиент",
				"color"    => "#99ff66",
				'identity' => $identity
			],
			[
				"title"    => "3 - Текущий клиент",
				"color"    => "#3366ff",
				'identity' => $identity
			],
			[
				"title"    => "4 - Постоянный клиент",
				"color"    => "#ff6600",
				'identity' => $identity
			]
		];

		foreach ($app_relations as $data) {

			$db -> query("INSERT INTO ".$sqlname."relations SET ?u", $data);

		}

		//Отчеты
		$app_reports = [
			[
				"title"    => "Активности по сделкам",
				"file"     => "work.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Активность по клиентам",
				"file"     => "week.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Действия по сделкам",
				"file"     => "newdogs.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Активности. Сводная",
				"file"     => "pipeline_activities.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Выполнение дел",
				"file"     => "activities_results.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Результаты исх.звонков",
				"file"     => "kpi_activities.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Результаты вход.активностей",
				"file"     => "kpi_activities_in.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Сводный по событиям",
				"file"     => "summary_report.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Новые клиенты",
				"file"     => "effect_newclients.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Анализ активностей №1",
				"file"     => "activities_r1.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Анализ активностей №2",
				"file"     => "activities_r2.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Анализ активностей №3",
				"file"     => "activities_r3.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Клиенты без активности",
				"file"     => "activities_r4.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Воронка по активностям",
				"file"     => "voronka_classic.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Статистика по Интересам",
				"file"     => "leads2014.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "История звонков",
				"file"     => "call_history.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности Vega",
				"file"     => "ent-activitiesResultReportVega.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Клиенты без активности 2",
				"file"     => "activitiesClientsNdays.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности PVN",
				"file"     => "ent-activitiesClientsPVN.php",
				"ron"      => "no",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Смена Куратора",
				"file"     => "ent-userChangeReport.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности Приоритет",
				"file"     => "ent-activitiesResultReportPrioritet.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности Приоритет Мод",
				"file"     => "ent-activitiesResultReportPrioritetMod.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "История. Дока",
				"file"     => "ent-activitiesDoka.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "История звонков 2",
				"file"     => "call_history2.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности по сделкам (не верный)",
				"file"     => "ent-HistoryPerDeals.php",
				"ron"      => "",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности Сотрудников по сделкам",
				"file"     => "ent-ActivitiesByUserByDeals.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности по времени",
				"file"     => "ent-activitiesByTime.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. История звонков с фильтрами",
				"file"     => "ent-CallHistoryPlus.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Активности по этапам",
				"file"     => "ent-ActivitiesByStepByDeals.php",
				"ron"      => "yes",
				"category" => "Активности",
				'identity' => $identity
			],
			[
				"title"    => "Прогноз по продуктам",
				"file"     => "dogs_productprognoz.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Прогноз по продуктам (большой)",
				"file"     => "dogs_productprognoz_hor.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Выполнение планов 2014",
				"file"     => "planfact2014.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Выполнение планов 2015",
				"file"     => "planfact2015.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Прогноз по продуктам (краткий)",
				"file"     => "dogs_productprognoz_short.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Выполнение плана по месяцам",
				"file"     => "ent-planDoByPayment.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Выполнение планов по закрытым сделкам",
				"file"     => "ent-planDoPrioritet.php",
				"ron"      => "yes",
				"category" => "Планирование",
				'identity' => $identity
			],
			[
				"title"    => "Pipeline Продажи Сотрудников",
				"file"     => "pipeline_users.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Pipeline Ожидаемый приход",
				"file"     => "pipeline_prognoz.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Pipeline Продажи по этапам",
				"file"     => "pipeline_dogs.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Здоровье сделок",
				"file"     => "dogs_health.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Здоровье сделок [большой]",
				"file"     => "dogs_health_big.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Здоровье сделок (дни)",
				"file"     => "dogs_health_big_day.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки. Анализ",
				"file"     => "dogs_monitor.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки. В работе",
				"file"     => "dogs_inwork.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки. Зависшие",
				"file"     => "dogs_inhold.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки. Утвержденные",
				"file"     => "dogs_approved.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки. Отказные",
				"file"     => "dogs_disapproved.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки. Здоровье (все сделки)",
				"file"     => "dogs_health_all.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Контроль сделок (по КТ)",
				"file"     => "dogs_complect.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Закрытые успешные сделки",
				"file"     => "dealResultReport.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. RFM анализ клиентов",
				"file"     => "ent-RFM-clients.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. RFM анализ продуктов",
				"file"     => "ent-RFM-products.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. ABC анализ клиентов",
				"file"     => "ent-ABC-clients.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. RFM анализ продуктов (mini)",
				"file"     => "ent-RFM-products-mini.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. ABC анализ продуктов",
				"file"     => "ent-ABC-products.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Список продуктов",
				"file"     => "ent-productAnalyseDupad.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Монитор сделок. мод",
				"file"     => "dogs_monitor_mod.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Комплексная воронка",
				"file"     => "ent-voronkaComplex.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Сделки в работе. По дням",
				"file"     => "ent-dealsPerDay.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Воронка продаж",
				"file"     => "ent-SalesFunnel.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "ent. Анализ закрытых сделок по этапам",
				"file"     => "ent-ClosedDealAnalyseByStep.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Сделки в работе. По дням по этапам",
				"file"     => "ent-dealsPerDayPerStep.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Оплаты по сотрудникам",
				"file"     => "ent-PaymentsByUser.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Новые клиенты",
				"file"     => "ent-newClients.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Новые сделки",
				"file"     => "ent-newDeals.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Оплаты для Roistat",
				"file"     => "ent-PaymentsForRoistat.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. АнтиВоронка продаж",
				"file"     => "ent-antiSalesFunnel.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Оплаты по дням",
				"file"     => "ent-PaymentsByDay.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Статусы счетов",
				"file"     => "ent-InvoiceStateByUser.php",
				"ron"      => "yes",
				"category" => "Продажи",
				'identity' => $identity
			],
			[
				"title"    => "Доска выполнения плана",
				"file"     => "raiting_plan.php",
				"ron"      => "yes",
				"category" => "Рейтинг",
				'identity' => $identity
			],
			[
				"title"    => "Топ клиентов",
				"file"     => "top_clients.php",
				"ron"      => "yes",
				"category" => "Рейтинг",
				'identity' => $identity
			],
			[
				"title"    => "Топ сотрудников",
				"file"     => "top_managers.php",
				"ron"      => "yes",
				"category" => "Рейтинг",
				'identity' => $identity
			],
			[
				"title"    => "Анализ конкурентов",
				"file"     => "effect_concurent.php",
				"ron"      => "yes",
				"category" => "Связи",
				'identity' => $identity
			],
			[
				"title"    => "Анализ поставщиков",
				"file"     => "effect_contractor.php",
				"ron"      => "yes",
				"category" => "Связи",
				'identity' => $identity
			],
			[
				"title"    => "Анализ партнеров",
				"file"     => "effect_partner.php",
				"ron"      => "yes",
				"category" => "Связи",
				'identity' => $identity
			],
			[
				"title"    => "Сделки по сотрудникам",
				"file"     => "effect_total.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "По отделам",
				"file"     => "effect_otdel.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Сделки по типам",
				"file"     => "effect_dogovor.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "По реализ. сделкам",
				"file"     => "effect_closed.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Эффективность сотрудников",
				"file"     => "effect.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Эффективность активностей Сотрудников",
				"file"     => "kpi_activities_byuser.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Сделки по направлениям",
				"file"     => "effect_direction.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "По источникам",
				"file"     => "effect_clientpath.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Активности Vega",
				"file"     => "activitiesResultReportVega.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Dashboard",
				"file"     => "ent-dashboardDuray.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Эффективность каналов",
				"file"     => "entClientpathToMoney.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Анализ направлений",
				"file"     => "entDirectionAnaliseChart.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Анализ направлений",
				"file"     => "entDirectionAnaliseDupad.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Анализ клиентов по типам отношений",
				"file"     => "entRelationsToMoney.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Новые клиенты по группам",
				"file"     => "ent-newClientsByGroup.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Анализ направлений по сотрудникам",
				"file"     => "entDirectionAnaliseByUserDupad.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Анализ потерянных сделок",
				"file"     => "ent-ClosedBadDealAnalyse.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
			[
				"title"    => "Ent. Показатели по направлениям",
				"file"     => "ent-DirectionAnalysePlus.php",
				"ron"      => "yes",
				"category" => "Эффективность",
				'identity' => $identity
			],
		];

		foreach ($app_reports as $data) {

			$db -> query("INSERT INTO ".$sqlname."reports SET ?u", $data);

		}

		//Различные сервисы
		$app_services = [
			[
				"name"     => "JastClick",
				"folder"   => "jastclick",
				"tip"      => "mail",
				'identity' => $identity
			],
			[
				"name"     => "Unisender",
				"folder"   => "unisender",
				"tip"      => "mail",
				'identity' => $identity
			],
			[
				"name"     => "ComTube.ru",
				"folder"   => "comtube",
				"tip"      => "sip",
				'identity' => $identity
			],
		];

		foreach ($app_services as $data) {

			$db -> query("INSERT INTO ".$sqlname."services SET ?u", $data);

		}

		//Запись для подключения к телефонии
		$app_sip = [
			"active"   => "no",
			"identity" => $identity
		];

		$db -> query("INSERT INTO ".$sqlname."sip SET ?u", $app_sip);

		//Запись почтового сервера
		$app_smtp = [
			"active"      => "no",
			"smtp_host"   => "smtp.yandex.ru",
			"smtp_port"   => 465,
			"smtp_auth"   => "true",
			"smtp_secure" => "ssl",
			"tip"         => "send",
			"name"        => "utf-8",
			"divider"     => ":",
			"deletemess"  => "yes",
			"identity"    => $identity
		];

		$db -> query("INSERT INTO ".$sqlname."smtp SET ?u", $app_smtp);

		//Территории
		$app_territory_cat = [
			[
				"title"    => "Пермь",
				"identity" => $identity
			],
			[
				"title"    => "Москва",
				"identity" => $identity
			],
			[
				"title"    => "Санкт-Петербург",
				"identity" => $identity
			],
			[
				"title"    => "Екатеринбург",
				"identity" => $identity
			],
			[
				"title"    => "Новосибирск",
				"identity" => $identity
			],
			[
				"title"    => "Нижний Новгород",
				"identity" => $identity
			],
			[
				"title"    => "Казань",
				"identity" => $identity
			],
		];

		foreach ($app_territory_cat as $data) {

			$db -> query("INSERT INTO ".$sqlname."territory_cat SET ?u", $data);

		}

		//Шаблоны уведомлений
		$app_tpl = [
			[
				"tip"      => "new_client",
				"name"     => "Новая организация",
				"content"  => "Создана новая Организация - <strong>{link}</strong>",
				"identity" => $identity
			],
			[
				"tip"      => "new_person",
				"name"     => "Новая персона",
				"content"  => "Создана новая персона - {link}",
				"identity" => $identity
			],
			[
				"tip"      => "new_dog",
				"name"     => "Новая сделка",
				"content"  => "Я создал сделку&nbsp;{link}",
				"identity" => $identity
			],
			[
				"tip"      => "edit_dog",
				"name"     => "Изменение в сделке",
				"content"  => "Я изменил статус сделки&nbsp;{link}",
				"identity" => $identity
			],
			[
				"tip"      => "close_dog",
				"name"     => "Закрытие сделки",
				"content"  => "Я закрыл сделку -&nbsp;{link}",
				"identity" => $identity
			],
			[
				"tip"      => "send_client",
				"name"     => "Вам назначена организация",
				"content"  => "Вы назначены ответственным за Организацию - {link}",
				"identity" => $identity
			],
			[
				"tip"      => "send_person",
				"name"     => "Вам назначена персона",
				"content"  => "Вы назначены ответственным за Персону - {link}",
				"identity" => $identity
			],
			[
				"tip"      => "trash_client",
				"name"     => "Изменение Ответственного",
				"content"  => "Ваша Организация перемещена в корзину - {link}",
				"identity" => $identity
			],
			[
				"tip"      => "lead_add",
				"name"     => "Новый интерес",
				"content"  => "Новый входящий интерес - {link}",
				"identity" => $identity
			],
			[
				"tip"      => "lead_setuser",
				"name"     => "Назначенный интерес",
				"content"  => "Вы назначены Ответственным за обработку входящего интереса - {link}",
				"identity" => $identity
			],
			[
				"tip"      => "lead_do",
				"name"     => "Обработанный интерес",
				"content"  => "Я обработал интерес - {link}",
				"identity" => $identity
			]
		];

		foreach ($app_tpl as $data) {

			$db -> query("INSERT INTO ".$sqlname."tpl SET ?u", $data);

		}

		//версия 8.10 - добавляем предустановки модулей
		try {

			$app_modules = [
				[
					"title"    => "Каталог-склад",
					"content"  => "",
					"mpath"    => "modcatalog",
					"icon"     => "icon-archive",
					"active"   => "off",
					"identity" => $identity
				],
				[
					"title"    => "Обращения",
					"content"  => "{\"enShowButtonLeft\":\"yes\",\"enShowButtonCall\":\"yes\"}",
					"mpath"    => "entry",
					"icon"     => "icon-phone-squared",
					"active"   => "on",
					"identity" => $identity
				],
				[
					'title'        => 'Сборщик заявок',
					'mpath'        => 'leads',
					'icon'         => 'icon-mail-alt',
					'active'       => 'off',
					'content'      => '{"leadСoordinator":"","leadMethod":"randome","leadOperator":[],"leadSendCoordinatorNotify":"yes","leadSendOperatorNotify":"yes","leadSendClientNotify":"yes","leadSendClientWellcome":"yes","leadCanDelete":"all","leadCanView":"yes"}',
					'activateDate' => current_datumtime(),
					'identity'     => $identity
				]
			];

			foreach ($app_modules as $data) {

				$db -> query("INSERT INTO ".$sqlname."modules SET ?u", $data);

			}

		}
		catch (Exception $e) {
			echo $e -> getMessage();
		}

		try {

			$app_modcatalog_set = [
				"settings" => "{\"mcArtikul\":\"yes\",\"mcStep\":\"6\",\"mcStepPers\":\"80\",\"mcKolEdit\":null,\"mcStatusEdit\":null,\"mcUseOrder\":\"yes\",\"mcCoordinator\":[\"1\",\"20\",\"22\",\"14\"],\"mcSpecialist\":[\"1\",\"23\",\"22\",\"3\"],\"mcAutoRezerv\":\"yes\",\"mcAutoWork\":\"yes\",\"mcAutoStatus\":null,\"mcSklad\":\"yes\",\"mcSkladPoz\":\"yes\",\"mcAutoProvider\":\"yes\",\"mcDBoardSkladName\":\"Наличие\",\"mcDBoardSklad\":\"yes\",\"mcDBoardZayavkaName\":\"Заявки\",\"mcDBoardZayavka\":\"yes\",\"mcDBoardOfferName\":\"Предложения\",\"mcDBoardOffer\":\"yes\",\"mcMenuTip\":\"inSub\",\"mcMenuPlace\":\"\",\"mcOfferName1\":\"\",\"mcOfferName2\":\"\",\"mcPriceCat\":[\"245\",\"247\",\"246\",\"1\",\"156\",\"154\",\"4\",\"158\",\"153\",\"180\",\"177\",\"176\",\"173\",\"172\",\"171\",\"170\",\"174\",\"175\",\"178\"]}",
				"ftp"      => "{\"mcFtpServer\":\"\",\"mcFtpUser\":\"\",\"mcFtpPass\":\"\",\"mcFtpPath\":\"\"}",
				"identity" => $identity
			];

			$db -> query("INSERT INTO ".$sqlname."modcatalog_set SET ?u", $app_modcatalog_set);

		}
		catch (Exception $e) {
			echo $e -> getMessage();
		}

		/**
		 * 2017.6
		 */

		$params = [
			"client" => [
				"title"      => [
					"active"   => "yes",
					"requered" => "yes",
					"more"     => "no",
					"comment"  => "Должно быть всегда включено и видимо",
				],
				"phone"      => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"fax"        => [
					"active"   => "no",
					"requered" => "no",
					"more"     => "no",
				],
				"mail_url"   => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"clientpath" => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"tip_cmr"    => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"idcategory" => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"des"        => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "yes",
				],
				"territory"  => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "yes",
				],
				"address"    => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "yes",
				],
				"site_url"   => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "yes",
				],
				"head_clid"  => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "yes",
				]
			],
			"person" => [
				"person"  => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
					"comment"  => "Должно быть всегда включено и видимо",
				],
				"ptitle"  => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"tel"     => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"mob"     => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"mail"    => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"loyalty" => [
					"active"   => "yes",
					"requered" => "no",
					"more"     => "no",
				],
				"rol"     => [
					"active"   => "no",
					"requered" => "no",
					"more"     => "no",
				]
			]
		];

		$db -> query("INSERT INTO ".$sqlname."customsettings SET ?u", [
			"tip"      => "eform",
			"params"   => json_encode($params),
			"identity" => $identity
		]);

		//Добавим настройки для полей сделок, требуемых при смене этапа
		$db -> query("INSERT INTO ".$sqlname."customsettings SET ?u", [
			"tip"      => "dfieldsstep",
			"identity" => $identity
		]);

		/**
		 * создание папок
		 */

		//создаем папки хранения файлов
		if (!file_exists("./files/".$identity)) {
			mkdir("./files/".$identity, 0777);
			chmod("./files/".$identity, 0777);
		}

		//создаем папки с настройками
		if (!file_exists("../cash/".$identity)) {
			mkdir("./cash/".$identity, 0777);
			chmod("./cash/".$identity, 0777);
			mkdir("./cash/".$identity."/templates", 0777);
			chmod("./cash/".$identity."/templates", 0777);
		}

		//загрузим пакет шаблонов
		//include_once "./opensource/Archive/archive.php";
		$arc = new ZipArchive;
		$arc -> open("cash/templates.zip");
		$arc -> extractTo("cash/".$identity."/templates");
		$arc -> close();

		//зашифруем пароль
		$resultu  = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE identity = '$identity' LIMIT 1");
		$iduser   = $resultu["iduser"];
		$pwd      = $resultu["pwd"];
		$username = $resultu["title"];
		$usermail = $resultu["email"];

		//добавим в список рассылки Mailerlite
		require_once "./register/scripts/mailerlite-api-v2-php-sdk-master/src/MailerLite.php";

		$api_key = '78b39015b704ab172cc000703dcc62d7';
		$listID  = '10353754';

		if ($listID) {

			$ML_Subscribers = (new MailerLiteApi\MailerLite($api_key)) -> groups();

			$subscriber = [
				'email'  => $usermail,
				'name'   => $username,
				'fields' => [
					[
						'name'  => 'date_start',
						'value' => current_date()
					]
				]
			];
			$subscriber = $ML_Subscribers -> addSubscriber($listID, $subscriber);

		}

		$resultat = '<div class="green div-center"><i class="icon-ok-circled icon-2x green"></i>&nbsp;<b>УРА-А-А! Ваш аккаунт активирован.</b><br>Добро пожаловать в Salesman24. Успехов в работе!</div>';

	}
	if ($identity > 0 && $activate == 'true') {

		$resultat = '<div class="green div-center"><i class="icon-ok-circled icon-2x green"></i>&nbsp;<b>Ваш аккаунт УЖЕ активирован.</b><br>Можете Авторизоваться</div>';

	}

	if ($identity < 1) {
		$resultat = '<div class="red div-center"><i class="icon-attention icon-2x red"></i>&nbsp;Упс. Указаны неверные данные</div>';
	}

	$action = '';
	//перенаправляем на авторизацию

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

$logo = ($isCloud) ? "https://salesman24.ru/register/images/logo-white.png" : "/assets/images/logo-white.png";
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