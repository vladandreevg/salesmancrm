<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Elements;
use Salesman\Notify;
use Salesman\User;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];
$iduser = (int)$_REQUEST['iduser'];

$port = '';

$protocol = $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://';

/**
 * Подсчет количества записей у указанного сотрудника
 *
 * @param int $id
 * @return number
 * @$
 */
function candelete(int $id = 0): int {

	global $rootpath;

	include_once $rootpath."/inc/config.php";

	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$identity = $GLOBALS['identity'];

	$all = 0;

	//проверим наличие клиентов
	$result = $db -> getRow("select COUNT(*) as count from {$sqlname}clientcat where iduser = '$id' and identity = '$identity'");
	$all    += (int)$result['count'];

	//проверим наличие персон
	$result = $db -> getRow("select COUNT(*) as count from {$sqlname}personcat where iduser = '$id' and identity = '$identity'");
	$all    += (int)$result['count'];

	//проверим наличие сделок
	$result = $db -> getRow("select COUNT(*) as count from {$sqlname}dogovor where iduser = '$id' and identity = '$identity'");
	$all    += (int)$result['count'];

	//проверим наличие подчиненных
	$result = $db -> getRow("select COUNT(*) as count from {$sqlname}user where mid = '$id' and identity = '$identity'");
	$all    += (int)$result['count'];

	return $all;

}

function resize_image($image_from, $image_to, $width, $height): bool {

	$image_vars = getimagesize($image_from);

	[
		$src_width,
		$src_height,
		$src_type
	] = $image_vars;

	if ($width > $src_width) {
		$width = $src_width;
	}

	if ($height > $src_height) {
		$height = $src_height;
	}
	else {
		$height = $width * ( $src_height / $src_width );
	}

	if ( (int)$height < 300) {

		$height = 300;
		$width  = $height / ( $src_height / $src_width );

	}

	switch ($src_type) {
		case IMAGETYPE_JPEG:
			$src_image = imagecreatefromjpeg($image_from);
			break;
		case IMAGETYPE_GIF:
			$src_image = imagecreatefromgif($image_from);
			break;
		case IMAGETYPE_PNG:
			$src_image = imagecreatefrompng($image_from);
			break;
		default:
			return false;
			break;
	}

	$dest_image = imagecreatetruecolor($width, $height);
	imagecopyresized($dest_image, $src_image, 0, 0, 0, 0, $width, $height, $src_width, $src_height);
	imagegif($dest_image, $image_to);

	return true;

}

$template = '<html lang="ru">
<head>
<title>Уведомление</title>
<STYLE type="text/css">
<!--
BODY {
	color:#000;
	font-size: 14px;
	font-family: arial, tahoma,serif;
	background:#eee;
	padding:0;
	margin:0;
}
div{
	margin:0;
}
hr{
	width:102%;
	border:0 none;
	border-top: #ccc 1px dotted;
	padding:0; height:1px;
	margin:5px -5px;
	clear:both;
}
.green { color: #349C5A;}
.blue, .blue a, a {
	color:#00548C;
}
.red, .red a{
	color:#CC2424;
}
.blok{
	background:#FFF;
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
.todo{
	float:left;
	color: #000;
	padding:5px 15px;
}
.logo img{
	height: 40px;
}
@media (max-width: 989px) {
	.head{
		margin-top:0;
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
	<div class="blok1 head" style="height:50px; margin-top:10px; border:1px solid transparent; padding:5px; margin-bottom: 10px;" align="left">
		<div class="todo">
			<div class="logo" style="float: left;">
				<a href="'.$productInfo['site'].'"><img src="'.$productInfo['site'].'/images/logo.png" height="30" style="margin-right:20px" border="0" /></a>
			</div>
		</div>
	</div>
	<div class="blok" style="font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;">
		<div style="color:black; font-size:12px; margin-top: 5px;">
		<p><b>Уважаемый {user},</b><br><br>Вам предоставлен доступ в корпоративную CRM-систему <b>'.$productInfo['name'].'</b></p>
		<p>Ваш логин: <b>{login}</b><br>Ваш пароль: <b>{password}</b></p>
		<p>
			<br><div style="width:100%; text-align:center;">
				<a href="'.$protocol.$_SERVER["HTTP_HOST"].'/login.php" target="_blank" class="letsgo">Перейти в '.$productInfo['name'].'</a>
			</div>
		</p>
		<hr><br>
		<p>Перейдите по адресу <b><a href="'.$protocol.$_SERVER["HTTP_HOST"].'/login.php" target="_blank">'.$protocol.$_SERVER["HTTP_HOST"].'/login.php</a></b>, чтобы начать работать.<br>После авторизации в системе Вы сможете изменить пароль в меню "Аватар" &rarr; "Мои настройки"</p>
		<p>Если указанная выше ссылка не работает, просто скопируйте её в браузер и нажмите Enter.</p>
		</div>
	</div>

	<div style="font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;" align="right">
		<div>'.$productInfo['name'].' Team</div>
	</div>

</DIV>
</body>
</html>';

if ($action == "checkuser") {

	$email = untag($_REQUEST['email']);

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

		print "Укажите корректный email";
		exit();

	}

	if (!$email || strlen($email) < 5) {
		$error = "Слишком короткий email";
	}
	else {

		$res     = $db -> getRow("SELECT COUNT(*) as count, iduser FROM {$sqlname}user WHERE login='$email'");
		$count   = (int)$res["count"];
		$iduser2 = (int)$res["iduser"];

		if ($iduser != $iduser2 && $count > 0) {
			$error = "Такой пользователь существует";
		}

	}

	print $error;

	exit();

}
if ($action == "delete") {

	//проверим наличие клиентов
	$all_c = (int)$db -> getOne("select COUNT(*) as count from {$sqlname}clientcat where iduser = '$iduser' and identity = '$identity'");

	//проверим наличие персон
	$all_p = (int)$db -> getOne("select COUNT(*) as count from {$sqlname}personcat where iduser = '$iduser' and identity = '$identity'");

	//проверим наличие сделок
	$all_d = (int)$db -> getOne("select COUNT(*) as count from {$sqlname}dogovor where iduser = '$iduser' and identity = '$identity'");

	//проверим наличие подчиненных
	$all_u = (int)$db -> getOne("select COUNT(*) as count from {$sqlname}user where mid = '$iduser' and identity = '$identity'");

	$all = $all_c + $all_p + $all_d + $all_u;

	if ($all == 0) {

		$db -> query("delete from {$sqlname}user where iduser = '$iduser' and identity = '$identity'");
		print '<div id="message">Сотрудник удален</div>';

	}
	else {
		print '
		За сотрудником закреплены данные:<br>
		Клиентов: <b>'.$all_c.'</b><br>
		Контактов: <b>'.$all_p.'</b><br>
		Сделок: <b>'.$all_d.'</b><br>
		Подчиненных: <b>'.$all_u.'</b><br>
		<br>
		Удаление сотрудника невозможно!
	';
	}

}
if ($action == "activate") {

	$res    = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'");
	$secrty = $res['secrty'];
	$adate  = $res['adate'];

	$dd = ( $adate != '0000-00-00' && $adate != "" ) ? diffDate($adate) : 10;

	if ($dd >= 3) {

		//если пользователь активен, то делаем неактивным
		$secrty = ( $secrty == 'yes' ) ? 'no' : 'yes';

		$db -> query("UPDATE {$sqlname}user SET ?u WHERE iduser = '$iduser' and identity = '$identity'", [
			'ses'    => '',
			'secrty' => $secrty,
			'adate'  => current_datum()
		]);

		print "Выполнено";

	}
	else {

		print "Действие не доступно.<br>С момента деактивации прошло менее 3 дней";

	}

	exit();

}

if ($action == "edit.do") {

	//file_put_contents($rootpath."/cash/request.json", json_encode_cyr($_REQUEST));

	$login2   = untag($_REQUEST['login']);
	$pwd      = untag($_REQUEST['pwd']);
	$title    = untag($_REQUEST['title']);
	$tip      = untag($_REQUEST['tip']);
	$mid2     = (int)$_REQUEST['mid2'];
	$mail_url = $isCloud ? $login2 : untag($_REQUEST['email']);

	$otdel     = (int)$_REQUEST['otdel'];
	$territory = (int)$_REQUEST['territory'];
	$office    = (int)$_REQUEST['office'];
	$phone     = untag($_REQUEST['phone']);
	$phone_in  = untag($_REQUEST['phone_in']);
	$fax       = untag($_REQUEST['fax']);
	$mob       = untag($_REQUEST['mob']);
	$bday      = $_REQUEST['bday'];
	$user_post = untag($_REQUEST['user_post']);
	$uid       = untag($_REQUEST['uid']);
	$zam       = (int)$_REQUEST['zam'];
	$tzonee    = $_REQUEST['tzonee'];

	$CompStart = untag($_REQUEST['CompStart']);
	$CompEnd   = untag($_REQUEST['CompEnd']);

	$acs_analitics = ( $_REQUEST['acs_analitics'] != 'on' ) ? 'off' : 'on';
	$acs_maillist  = ( $_REQUEST['acs_maillist'] != 'on' ) ? 'off' : 'on';
	$acs_files     = ( $_REQUEST['acs_files'] != 'on' ) ? 'off' : 'on';
	$acs_price     = ( $_REQUEST['acs_price'] != 'on' ) ? 'off' : 'on';
	$acs_credit    = ( $_REQUEST['acs_credit'] != 'on' ) ? 'off' : 'on';
	$acs_prava     = ( $_REQUEST['acs_prava'] != 'on' ) ? 'off' : 'on';
	$acs_plan      = ( $_REQUEST['acs_plan'] != 'on' ) ? 'off' : 'on';
	$show_marga    = ( $_REQUEST['show_marga'] != 'yes' ) ? 'no' : 'yes';
	$acs_import    = ( $_REQUEST['acs_import'] != 'on' ) ? 'off' : 'on';

	$secrty    = $_REQUEST['secrty'];
	$ac_import = (array)$_REQUEST['ac_import'];

	$aci = [];

	for ($i = 0; $i < 25; $i++) {
		$aci[$i] = ( $ac_import[$i] != 'on' ) ? 'off' : 'on';
	}

	$ac_import = implode(";", $aci);

	$CompEnd   = ( $secrty != 'yes' || $CompEnd != '' ) ? $CompEnd : NULL;
	$CompStart = ( $secrty != 'yes' || $CompStart != '' ) ? $CompStart : current_datum();

	$isadmin = ( $_REQUEST['isadmin'] != 'on' ) ? 'off' : 'on';

	$subscribe = (array)$_REQUEST['subscribe'];

	ksort($subscribe);

	for ($i = 0; $i < 30; $i++) {
		if ($subscribe[$i] != 'on') {
			$subscribe[$i] = 'off';
		}
	}

	ksort($subscribe);
	$subscribe = implode(";", $subscribe);

	$viget_on    = (array)$_REQUEST['vizzible'];
	$viget_order = $_REQUEST['order'];
	$param       = [];

	/*foreach ( $viget_order as $key => $value ) {

		$param['vigets'][ $value ] = ($viget_on[ $value ] == '') ? 'off' : 'on';

	}*/

	$param['vigets'] = [];
	foreach ($viget_on as $key => $value) {

		$param['vigets'][$key] = $value;

	}

	//настройки юзера разные
	$param['taskAlarm']             = untag($_REQUEST['taskAlarm']);
	$param['userTheme']             = untag($_REQUEST['userTheme']);
	$param['userThemeRound']        = untag($_REQUEST['userThemeRound']);
	$param['startTab']              = untag($_REQUEST['startTab']);
	$param['menuClient']            = untag($_REQUEST['menuClient']);
	$param['menuPerson']            = untag($_REQUEST['menuPerson']);
	$param['menuDeal']              = untag($_REQUEST['menuDeal']);
	$param['notify']                = array_keys((array)$_REQUEST['notify']);
	$param['filterAllBy']           = untag($_REQUEST['filterAllBy']);
	$param['hideAllContacts']       = untag($_REQUEST['hideAllContacts']);
	$param['filterAllByPersonEdit'] = untag($_REQUEST['filterAllByPersonEdit']);
	$param['filterAllByPersonCard'] = untag($_REQUEST['filterAllByPersonCard']);
	$param['filterAllByClientEdit'] = untag($_REQUEST['filterAllByClientEdit']);
	$param['filterAllByClientCard'] = untag($_REQUEST['filterAllByClientCard']);
	$param['filterAllByDealEdit']   = untag($_REQUEST['filterAllByDealEdit']);
	$param['filterAllByDealCard']   = untag($_REQUEST['filterAllByDealCard']);
	$param['kpiEditor']             = untag($_REQUEST['kpiEditor']);
	$param['historyAddBlock']       = untag($_REQUEST['historyAddBlock']);
	$param['taskCheckBlock']        = untag($_REQUEST['taskCheckBlock']);
	$param['subscribs']             = $_REQUEST['subscribs'];
	$param['dostup']                = (array)$_REQUEST['dostup'];
	$param['subscribs']             = $_REQUEST['subscribs'];
	//$usersettings       = json_encode_cyr($param);

	/*
	$param['dostup']['rc'] = array_map(static function($x){
		return (int)$x;
	}, $param['dostup']['rc']);
	*/

	//проверим часовой пояс
	//если значение не корректно (больше 12), то игнорируем смещение временной зоны
	$totalTimeZone = $tzonee + $dzz / 3600;
	if ($totalTimeZone > 12) {

		$tzonee = 0;
		$mes    .= '<br>Смещение часовой зоны игнорировано - не допустимое конечное значение';

	}

	//Найдем руководителя отдела
	$result = $db -> getRow("select * from {$sqlname}user where iduser = '$mid2' and identity = '$identity'");

	if ($tip == 'Руководитель организации') {
		$bid = 0;
	}
	elseif ($tip == "Руководитель подразделения") {
		$bid = $iduser;
	}
	elseif ($tip == "Менеджер продаж") {
		$bid = $result["mid"];
	}
	elseif ($tip == "Руководитель отдела") {
		$bid = $mid2;
	}

	//проверка на существующий емейл
	$iduser2 = (int)$db -> getOne("SELECT iduser FROM {$sqlname}user WHERE login = '$login2'");

	if ($iduser2 > 0 && $iduser != $iduser2) {

		print '<b class="yelw">Ошибка:</b> Такой логин уже существует';
		exit();

	}

	/**
	 * Загрузка аватара
	 */

	if ($_FILES['file']['name'] != '') {

		//органичение сервера по размеру файла
		$maxupload = str_replace([
			'M',
			'm'
		], '', @ini_get('upload_max_filesize'));

		$ext        = texttosmall(getExtention($_FILES['file']['name']));
		$uploaddir  = $rootpath.'/cash/avatars/';
		$file       = time().'avatar-'.$iduser.'.'.$ext;
		$uploadfile = $uploaddir.$file;//новое имя файла

		createDir($uploaddir);

		$file_ext_allow = [
			'png',
			'jpg',
			'jpeg',
			'gif',
			'webp'
		];

		/*if ( !file_exists( $rootpath.'/cash/avatars' ) ) {
			mkdir( $uploaddir, 0777 );
			chmod( $uploaddir, 0777 );
		}*/

		if (in_array($ext, $file_ext_allow)) {

			if (( filesize($_FILES['file']['tmp_name']) / 1000000 ) > $maxupload) {

				print 'Ошибка: Изображение превышает допустимые размеры!';

			}
			else {

				if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {

					resize_image($uploadfile, $uploadfile, 300, 300);

				}
				else {
					print 'Ошибка: '.$_FILES['file']['error'];
				}

			}

		}
		else {
			print 'Ошибка: Изображение не загружено - Допустимые форматы: PNG, JPG, JPEG, GIF, WEBP';
		}

	}

	/**
	 * Загрузка аватара
	 */

	if ($iduser == 0) {

		$mes = '';

		$tpl = str_replace([
			'{login}',
			'{password}',
			'{user}'
		], [
			$login2,
			$pwd,
			$title
		], $template);

		$subj = "Приглашение в ".$productInfo['name'];

		$salt    = generateSalt();
		$newpass = encodePass($pwd, $salt);

		$avatar = ( $file != '' ) ? $file : '';

		$arg = [
			'login'         => $login2,
			'pwd'           => ( $pwd != '' ) ? $newpass : '',
			'title'         => $title,
			'tip'           => $tip,
			'mid'           => $mid2,
			'bid'           => ( $bid > 0 ) ? $bid : 0,
			'zam'           => $zam,
			'otdel'         => $otdel,
			'email'         => $mail_url,
			'avatar'        => $avatar,
			'territory'     => $territory,
			'office'        => $office,
			'phone'         => $phone,
			'phone_in'      => $phone_in,
			'fax'           => $fax,
			'mob'           => $mob,
			'bday'          => ( $bday != '' ) ? $bday : NULL,
			'acs_analitics' => $acs_analitics,
			'acs_maillist'  => $acs_maillist,
			'acs_files'     => $acs_files,
			'acs_price'     => $acs_price,
			'acs_credit'    => $acs_credit,
			'acs_prava'     => $acs_prava,
			'tzone'         => $tzonee,
			//'viget_on'      => $vion,
			//'viget_order'   => $viorder,
			'secrty'        => $secrty,
			'isadmin'       => $isadmin,
			'acs_import'    => $ac_import,
			'show_marga'    => $show_marga,
			'user_post'     => $user_post,
			'acs_plan'      => $acs_plan,
			'CompStart'     => $CompStart,
			'CompEnd'       => $CompEnd,
			'subscription'  => $subscribe,
			'uid'           => $uid,
			"usersettings"  => json_encode_cyr($param),
			'sole'          => $salt,
			'identity'      => $identity
		];

		$db -> query("INSERT INTO {$sqlname}user SET ?u", $arg);
		$iduser = $db -> insertId();

		print 'Пользователь добавлен';

		try {

			$rez = mailto([
				$mail_url,
				$title,
				$productInfo['email'],
				$productInfo['name'],
				$subj,
				$tpl
			]);

		}
		catch (Exception $e) {

			$rez = $e -> getMessage();

		}

		if ($rez == '') {
			print $mes = '<br>Сотруднику выслано приглашение по указанному e-mail';
		}
		else {
			print $mes = '<br>Ошибка отправки уведомления.<br><b>Ответ: </b>'.$rez;
		}


	}
	else {

		$myavatar = $db -> getOne("SELECT avatar FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'");

		$avatar = ( $file != '' ) ? $file : $myavatar;

		$salt = $db -> getOne("SELECT sole FROM {$sqlname}user WHERE iduser = '$iduser' AND identity = '$identity'");

		$arg = [
			'login'         => $login2,
			'title'         => $title,
			'tip'           => $tip,
			'mid'           => $mid2,
			'bid'           => ( $bid > 0 ) ? $bid : 0,
			'zam'           => $zam,
			'otdel'         => $otdel,
			'email'         => $mail_url,
			'avatar'        => $avatar,
			'territory'     => $territory,
			'office'        => $office,
			'phone'         => $phone,
			'phone_in'      => $phone_in,
			'fax'           => $fax,
			'mob'           => $mob,
			'bday'          => ( $bday != '' ) ? $bday : NULL,
			'acs_analitics' => $acs_analitics,
			'acs_maillist'  => $acs_maillist,
			'acs_files'     => $acs_files,
			'acs_price'     => $acs_price,
			'acs_credit'    => $acs_credit,
			'acs_prava'     => $acs_prava,
			'tzone'         => $tzonee,
			'secrty'        => $secrty,
			'isadmin'       => $isadmin,
			'acs_import'    => $ac_import,
			'show_marga'    => $show_marga,
			'user_post'     => $user_post,
			'acs_plan'      => $acs_plan,
			'CompStart'     => $CompStart,
			'CompEnd'       => $CompEnd,
			'subscription'  => $subscribe,
			'uid'           => $uid,
			"usersettings"  => json_encode_cyr($param)
		];
		$db -> query("UPDATE {$sqlname}user SET ?u WHERE iduser = '$iduser' and identity = '$identity'", $arg);

		//если пароль меняется
		if ($pwd != '') {

			$salt    = $db -> getOne("SELECT sole FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'");
			$newpass = encodePass($pwd, $salt);

			$db -> query("UPDATE {$sqlname}user SET pwd = '$newpass' WHERE iduser = '$iduser' and identity = '$identity'");

		}

	}

	unlink($rootpath."/cash/".$fpath."settings.user.".$iduser.".json");

	print 'Сделано';

	exit();

}

//блокируем прямое подключение к формам
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ($action == "edit") {

	$u = [];

	$xuser = $iduser;

	if ($iduser > 0) {

		$result = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'");

		if (!isset($_REQUEST['clone'])) {

			$title    = $result["title"];
			$login2   = $result["login"];
			$pwd      = $result["pwd"];
			$phone    = $result["phone"];
			$phone_in = $result["phone_in"];
			$fax      = $result["fax"];
			$mob      = $result["mob"];
			$email    = $result["email"];
			$bday     = $result["bday"];
			$uavatar  = $result["avatar"];

		}
		else {

			$xuser = 0;

		}

		$zam       = $result["zam"];
		$tip       = $result["tip"];
		$mid2      = $result["mid"];
		$otdel     = $result["otdel"];
		$tzone     = $result["tzone"];
		$territory = $result["territory"];
		$office    = $result["office"];

		if ($bday == '0000-00-00') {
			$bday = '';
		}

		$acs_analitics = $result["acs_analitics"];
		$acs_maillist  = $result["acs_maillist"];
		$acs_files     = $result["acs_files"];
		$acs_price     = $result["acs_price"];
		$acs_credit    = $result["acs_credit"];
		$acs_prava     = $result["acs_prava"];
		$acs_plan      = $result["acs_plan"];
		$acs_import    = $result["acs_import"];
		$ac_import     = explode(";", $result["acs_import"]);
		$isadmin       = $result["isadmin"];
		$secrty        = $result["secrty"];
		$show_marga    = $result["show_marga"];
		$user_post     = $result["user_post"];
		$uid           = $result["uid"];
		$CompStart     = $result["CompStart"];
		$CompEnd       = $result["CompEnd"];

		$usersettings = json_decode($result["usersettings"], true);

		//print_r( $usersettings );

		$subscription = $result["subscription"];
		$subscribe    = explode(";", $subscription);

		$totalTimeZone = $GLOBALS['tzonee'] + $dzz / 3600;

		// список сотрудников, чтобы исключить выбор их в качестве руководителя текущего сотрудника
		$u = User ::userList($iduser, ["as" => "id"]);

	}
	else {

		$otdel                  = (int)$_REQUEST['otdel'];
		$mid2                   = (int)$_REQUEST['ruk'];
		$uavatar                = '';
		$isadmin                = '';
		$tzone                  = '+0:00';
		$ac_import              = explode(";", "off;off;off;off;off;on;off;off;on;on;off;on;on;off;on;on;off;off;off;off;on;off;off;off;off");
		$subscribe              = explode(";", "off;on;off;off;on;off;on;off;on;off;on;off;off;off;off;off;off;off");
		$usersettings['vigets'] = json_decode("{\"metrics\":\"on\",\"analitic\":\"on\",\"gauge\":\"on\",\"parameters\":\"on\",\"planplus\":\"on\",\"credit\":\"on\",\"activehitmap\":\"on\",\"payment\":\"on\",\"voronka\":\"on\",\"bethday\":\"off\",\"dogs_renew\":\"on\",\"stat\":\"on\",\"prognoz\":\"off\",\"dogsclosed\":\"off\",\"voronka_conus\":\"on\",\"history\":\"off\",\"voronka_classic\":\"on\",\"raiting_payment\":\"on\",\"raiting_potential\":\"on\",\"workplanalert\":\"off\",\"channels\":\"off\",\"notifications_count\":\"off\"}", true);

		$chars    = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		$max      = 10;
		$size     = StrLen($chars) - 1;
		$password = NULL;
		while ($max--) {
			$password .= $chars[random_int(0, $size)];
		}
		$password .= '!sm';

	}

	$vigetsBase = $vigetsCustom = [];

	$themes = json_decode(str_replace([
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents($rootpath.'/cash/themes.json')), true);

	//print_r($themes);

	$vigetsBase = json_decode(str_replace([
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents($rootpath."/cash/map.vigets.json")), true);

	if (file_exists($rootpath."/cash/map.vigets.castom.json")) {
		$vigetsCustom = json_decode(str_replace([
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents($rootpath."/cash/map.vigets.castom.json")), true);
	}

	$vigetsBase = array_merge($vigetsBase, $vigetsCustom);

	$namesTab = [
		'vigets'   => 'Индикаторы',
		'clients'  => 'Клиенты',
		'contacts' => 'Контакты',
		'deals'    => 'Сделки',
		'pipeline' => 'Pipeline',
		'todo'     => 'Дела',
		'health'   => 'Здоровье',
		'bigcal'   => 'Календарь на месяц',
		'weekcal'  => 'Недельный календарь'
	];

	$uavatar = $uavatar == '' ? '/assets/images/noavatar.png' : "cash/avatars/".$uavatar;

	if (!file_exists($rootpath.'/'.$avatar)) {
		$uavatar = '/assets/images/noavatar.png';
	}

	?>
	<DIV class="zagolovok">Редактирование пользователя</DIV>

	<FORM method="post" action="/content/admin/<?php
	echo $thisfile; ?>" enctype="multipart/form-data" name="userForm" id="userForm">
		<INPUT name="iduser" type="hidden" id="iduser" value="<?= $xuser ?>">
		<INPUT type="hidden" name="action" id="action" value="edit.do">

		<?php
		if (!$acs_useradd && $iduser < 1) {

			$resultu = $db -> getRow("select COUNT(*) as count from {$sqlname}user WHERE identity = '$identity'");
			$alluser = $resultu['count'];

			$delta = (int)$user_count - (int)$userlimTitle + 1;

			print '
			<div class="warning m0 fs-11 text-left">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase bigtxt">Внимание:</b>
				<br><br>
				Превышен лимит пользователей. <br><br>
				Лимит составляет: <b class="red">'.$userlimTitle.'</b> активных пользователей.
				<br>
				Активных пользователей <b>'.$user_count.'</b>, всего <b>'.$alluser.'</b>.
				<br><br>
				Для добавления нового сотрудника необходимо деактивировать <b>'.$delta.'</b> существующих.<br>
			</div>';

			exit();

		}
		?>

		<DIV id="formtabse" class="input--gray" style="overflow-x: hidden; overflow-y: auto; height: 80vh">

			<div class="flex-container box--child mb20">

				<div class="flex-string wp100">

					<div class="avatarbig div-center" style="background: url(<?= $uavatar ?>); background-size:cover;"></div>
					<div class="togglerbox hand avatarchange div-center" data-id="uavatar">Изменить</div>

				</div>

				<div id="uavatar" class="flex-string wp100 hidden div-center mt20">

					<div class="wp10 inline"></div>
					<div class="wp60 inline text-left">

						<input name="file" type="file" class="wp97" id="file">
						<div class="fs-09 gray2">Загрузка аватара</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Данные</b></div>
			</div>
			<div id="tab-form-1" class="p10 tab box--child">

				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">ФИО:</div>
					<div class="flex-string wp80 ">
						<input name="title" type="text" id="title" class="required wp97" value="<?= $title ?>"/>
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Логин<?php
						if ($isCloud) {
							print "/Email";
						} ?>:
					</div>
					<div class="flex-string wp80 relativ">
						<INPUT name="login" type="text" class="required wp97" id="login" value="<?= $login2 ?>">
						<?php
						if ($isCloud) { ?>
							<div id="emailvalidate" class="hidden">&nbsp;</div>
							<?php
						} ?>
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Пароль:</div>
					<div class="flex-string wp80 relativ">
						<input name="pwd" type="text" id="pwd" autocomplete="off" value="<?= $password ?>" class="<?= ( $iduser > 0 ? '' : 'required' ) ?> wp97">
						<?= ( $iduser > 0 ? '<div class="fs-09 gray2">Указать только в случае смены пароля</div>' : '' ) ?>
						<div id="passstrength">&nbsp;</div>
					</div>

				</div>
				<?php
				if (!$isCloud) { ?>
					<div class="flex-container mb10 mt5 rowtable p10">

						<div class="flex-string wp20 fs-12 text-right pt10 pr5">Почта:</div>
						<div class="flex-string wp80 relativ">
							<input name="email" type="text" id="email" value="<?= $email ?>" class="required wp97">
							<?php
							if (!$isCloud) { ?>
								<div id="emailvalidate" class="hidden">&nbsp;</div>
								<?php
							} ?>
						</div>

					</div>
					<?php
				} ?>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Роль в CRM:</div>
					<div class="flex-string wp80 relativ">

						<select name="tip" id="tip" class="required wp97">
							<option value="">--Выбор--</option>
							<option <?php print ($tip == "Руководитель организации") ? "selected" : ""; ?> value="Руководитель организации">Руководитель организации</option>
							<option <?php print ($tip == "Руководитель с доступом") ? "selected" : ""; ?> value="Руководитель с доступом">Руководитель с доступом</option>
							<option <?php print ($tip == "Руководитель подразделения") ? "selected" : ""; ?> value="Руководитель подразделения">Руководитель подразделения</option>
							<option <?php print ($tip == "Руководитель отдела") ? "selected" : ""; ?> value="Руководитель отдела">Руководитель отдела</option>
							<option <?php print ($tip == "Менеджер продаж") ? "selected" : ""; ?> value="Менеджер продаж">Менеджер продаж</option>
							<option <?php print ($tip == "Поддержка продаж") ? "selected" : ""; ?> value="Поддержка продаж">Поддержка продаж</option>
							<option <?php print ($tip == "Специалист") ? "selected" : ""; ?> value="Специалист">Специалист</option>
							<option <?php print ($tip == "Администратор") ? "selected" : ""; ?> value="Администратор">Администратор</option>
						</select>

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Руководитель:</div>
					<div class="flex-string wp80 ">

						<SELECT name="mid2" id="mid2" class="wp97">
							<OPTION value="0">нет</OPTION>
							<?php
							$resulth = $db -> getAll("SELECT * FROM {$sqlname}user WHERE tip LIKE '%Руководитель%' and iduser != '$iduser' and secrty = 'yes' and identity = '$identity' ORDER BY title");
							foreach ($resulth as $users) {

								$reso   = $db -> getRow("SELECT * FROM {$sqlname}otdel_cat WHERE idcategory='".$users['otdel']."' and identity = '$identity'");
								$otdelk = " - ".$reso["title"];

								if (!in_array($users['iduser'], $u)) {
									print '<OPTION value="'.$users['iduser'].'" '.( $users['iduser'] == $mid2 ? "selected" : "" ).'>'.$users['title'].': '.$users['tip'].$otdelk.'</OPTION>';
								}


							}
							?>
						</SELECT>

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Телефон:</div>
					<div class="flex-string wp80 ">
						<input name="phone" type="text" id="phone" value="<?= $phone ?>" class="wp97">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Внутренний:</div>
					<div class="flex-string wp80 ">
						<input name="phone_in" type="text" id="phone_in" value="<?= $phone_in ?>" class="wp97">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Мобильный:</div>
					<div class="flex-string wp80 ">
						<input name="mob" type="text" id="mob" value="<?= $mob ?>" class="wp97">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Факс:</div>
					<div class="flex-string wp80 ">
						<input name="fax" type="text" id="fax" value="<?= $fax ?>" class="wp97">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">День Рождения:</div>
					<div class="flex-string wp80 ">
						<input name="bday" type="text" id="bday" value="<?= $bday ?>" class="wp97">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Часовой пояс:</div>
					<div class="flex-string wp20 ">
						<select name="tzonee" id="tzonee">
							<?php
							for ($i = -12; $i < 13; $i++) {

								$t  = abs($i);
								$dd = abs($totalTimeZone + $i);

								$znak = ( $i < 0 ) ? "-" : "+";
								$s    = ( $i == $tzone ) ? "selected" : '';
								$d    = ( $dd > 12 ) ? ' disabled' : '';

								print '<option '.$s.$d.' value="'.$znak.$t.'">'.$znak." ".$t.':00</option>';


							}
							?>
						</select>&nbsp;
					</div>
					<div class="flex-string wp60 p0 em">
						Задает смещение текущего времени от времени на сервере.<br>На сервере
						<b class="blue"><?= $thistime ?></b> - временная зона
						<b class="blue"><?= $tmzone ?></b>
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Заместитель:</div>
					<div class="flex-string wp80 ">
						<select name="zam" id="zam" class="wp97">
							<option value="0">--не указан--</option>
							<?php
							$users = User ::userCatalog();
							foreach ($users as $i => $user) {

								if ($user['id'] != $iduser) {

									$s = ( $user['id'] == $zam ) ? "selected" : "";

									print '<option '.$s.' value="'.$user['id'].'">'.str_repeat("&raquo;&nbsp;&nbsp;", $user['level']).$user['title'].'</option>';

								}
							}
							?>
						</select>
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Должность:</div>
					<div class="flex-string wp80 ">

						<input name="user_post" type="text" id="user_post" class="wp97" value="<?= $user_post ?>">

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Офис:</div>
					<div class="flex-string wp80 ">

						<SELECT name="office" id="office" class="wp97">

							<OPTION value="0">--Выбор--</OPTION>
							<?php
							$query  = "SELECT * FROM {$sqlname}office_cat WHERE identity = '$identity' ORDER BY title";
							$result = $db -> getAll($query);
							foreach ($result as $data_array) {
								?>
								<OPTION <?php
								if ($data_array['idcategory'] == $office) {
									print "selected";
								} ?> value="<?= $data_array['idcategory'] ?>"><?= $data_array['title'] ?></OPTION>
								<?php
							} ?>
						</SELECT>

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Территория:</div>
					<div class="flex-string wp80 ">

						<SELECT name="territory" id="territory" class="wp97">
							<OPTION value="0">--Выбор--</OPTION>
							<?php
							$query  = "SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' ORDER BY title";
							$result = $db -> getAll($query);
							foreach ($result as $data_array) {
								?>
								<OPTION <?php
								if ($data_array['idcategory'] == $territory) {
									print "selected";
								} ?> value="<?= $data_array['idcategory'] ?>"><?= $data_array['title'] ?></OPTION>
								<?php
							}
							?>
						</SELECT>

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Отдел:</div>
					<div class="flex-string wp80 ">

						<select name="otdel" id="otdel" class="wp97">
							<option value="0">--Выбор--</option>
							<?php
							$query  = "SELECT * FROM {$sqlname}otdel_cat WHERE identity = '$identity' ORDER BY title";
							$result = $db -> getAll($query);
							foreach ($result as $data_array) {
								?>
								<option <?php
								if ($data_array['idcategory'] == $otdel) {
									print "selected";
								} ?> value="<?= $data_array['idcategory'] ?>"><?= $data_array['UID'] ?> <?= $data_array['title'] ?></option>
								<?php
							}
							?>
						</select>

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Дата приема:</div>
					<div class="flex-string wp80 ">
						<input name="CompStart" type="text" id="CompStart" class="wp97" value="<?= $CompStart ?>">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Дата увольнения:</div>
					<div class="flex-string wp80 ">
						<input name="CompEnd" type="text" id="CompEnd" class="wp97" value="<?= $CompEnd ?>">
					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10 <?= ( $iduser > 0 ? 'hidden' : '' ) ?>">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">Доступ в систему:</div>
					<div class="flex-string wp80 ">

						<select name="secrty" id="secrty">
							<option value="yes" <?php
							if ($secrty == 'yes') {
								print "selected";
							} ?>>Активен
							</option>
							<option value="no" <?php
							if ($secrty == 'no') {
								print "selected";
							} ?>>Не активен
							</option>
						</select>

					</div>

				</div>
				<div class="flex-container mb10 mt5 rowtable p10">

					<div class="flex-string wp20 fs-12 text-right pt10 pr5">
						<i class="icon-info-circled blue" title="Идентификатор для внешних систем. До 30 знаков"></i>UID:
					</div>
					<div class="flex-string wp80 ">

						<input name="uid" type="text" id="uid" class="wp97" value="<?= $uid ?>">

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Права общие</b></div>
			</div>
			<div id="tab-form-11" class="p10 tab box--child">

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="isadmin" class="wp100 tooltips" tooltip="Регулирует доступ сотрудника в панель управления" tooltip-position="top" tooltip-type="success"><b class="red">Администратор</b> (доступ в панель управления)</label>
						<div class="fs-09 blue">Регулирует доступ сотрудника в панель управления, а также предоставляет расширенные права</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="isadmin" type="checkbox" id="isadmin" value="on" <?php
								if ($isadmin == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_maillist" class="wp100 tooltips" tooltip="Регулирует доступ сотрудника в раздел Рассылки (внутр.)" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Рассылки"</b></label>
						<div class="fs-09 blue">Доступ сотрудника в раздел Рассылки (внутр.)</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_maillist" type="checkbox" id="acs_maillist" value="on" <?php
								if ($acs_maillist == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_files" class="wp100 tooltips" tooltip="Регулирует доступ сотрудника в раздел Файлы. Не запрещает загружать файлы" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Файлы"</b></label>
						<div class="fs-09 blue">Доступ сотрудника в раздел Файлы. Не запрещает загружать файлы</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_files" type="checkbox" id="acs_files" value="on" <?php
								if ($acs_files == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_price" class="wp100 tooltips" tooltip="Сотрудник сможет редактировать позиции Прайса." tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Прайсы"</b></label>
						<div class="fs-09 blue">Сотрудник сможет редактировать позиции Прайса</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_price" type="checkbox" id="acs_price" value="on" <?php
								if ($acs_price == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[6]" class="wp100 tooltips" tooltip="Разрешает доступ в раздел Бюджет + добавляет виджеты из раздела на рабочий стол" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Бюджет"</b></label>
						<div class="fs-09 blue">Разрешает доступ в раздел Бюджет + добавляет виджеты из раздела на рабочий стол</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[6]" type="checkbox" id="ac_import[6]" value="on" <?php
								if ($ac_import[6] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

					<div class="flex-string wp60 flh-12"></div>
					<div class="flex-string wp40 flh-12">

						<div>так же предоставить только:</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][journal]" type="checkbox" id="dostup[budjet][journal]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['journal'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Журнал расходов (доступ)</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][statement]" type="checkbox" id="dostup[budjet][statement]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['statement'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Банковские выписки (доступ)</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][payment]" type="checkbox" id="dostup[budjet][payment]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['payment'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Журнал оплат (доступ)</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][agents]" type="checkbox" id="dostup[budjet][agents]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['agents'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Расчеты с поставщиками (доступ)</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][money]" type="checkbox" id="dostup[budjet][money]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['money'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Средства на счетах (+ на Раб.столе)</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][onlyself]" type="checkbox" id="dostup[budjet][onlyself]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['onlyself'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Только свои расходы (+ подчиненных)</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="dostup[budjet][action]" type="checkbox" id="dostup[budjet][action]" value="yes" <?php
								print ( $usersettings['dostup']['budjet']['action'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								<span class="title flh-07 fs-09">Действия с расходами (действия)</span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[4]" class="wp100 tooltips" tooltip="Разрешает производить групповые действия" tooltip-position="top" tooltip-type="success"><b>Доступ к массовым операциям</b></label>
						<div class="fs-09 blue">Разрешает производить групповые действия</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[4]" type="checkbox" id="ac_import[4]" value="on" <?php
								if ($ac_import[4] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[17]" class="wp100 tooltips" tooltip="Разрешает доступ в раздел Группы с правами редактора" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Группы"</b></label>
						<div class="fs-09 blue">Разрешает доступ в раздел Группы с правами редактора</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[17]" type="checkbox" id="ac_import[17]" value="on" <?php
								if ($ac_import[17] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_analitics" class="wp100 tooltips" tooltip="Разрешает доступ в раздел Отчеты. Доступ к конкретному отчету регулируется в настройках отчета" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Отчеты"</b></label>
						<div class="fs-09 blue">Разрешает доступ в раздел Отчеты. Доступ к конкретному отчету регулируется в настройках отчета</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_analitics" type="checkbox" id="acs_analitics" value="on" <?php
								if ($acs_analitics == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover hidden">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[18]" class="wp100 tooltips" tooltip="Разрешает доступ к записям Поставщиков, Партнеров, Конкурентов" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Связи"</b></label>
						<div class="fs-09 blue">Разрешает доступ к записям Поставщиков, Партнеров, Конкурентов</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[18]" type="checkbox" id="ac_import[18]" value="on" <?php
								if ($ac_import[18] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="dostup[partner]" class="wp100 tooltips" tooltip="Разрешает доступ к записям Партнеров" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Партнеры"</b></label>
						<div class="fs-09 blue">Разрешает доступ к записям Партнеров</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="dostup[partner]" type="checkbox" id="dostup[partner]" value="on" <?php
								if ($usersettings['dostup']['partner'] == 'on') {
									print "checked";
								} ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>
				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="dostup[contractor]" class="wp100 tooltips" tooltip="Разрешает доступ к записям Поставщиков" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Поставщики"</b></label>
						<div class="fs-09 blue">Разрешает доступ к записям Поставщиков</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="dostup[contractor]" type="checkbox" id="dostup[contractor]" value="on" <?php
								if ($usersettings['dostup']['contractor'] == 'on') {
									print "checked";
								} ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>
				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="dostup[concurent]" class="wp100 tooltips" tooltip="Разрешает доступ к записям Конкурентов" tooltip-position="top" tooltip-type="success"><b>Доступ в раздел "Конкуренты"</b></label>
						<div class="fs-09 blue">Разрешает доступ к записям Конкурентов</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="dostup[concurent]" type="checkbox" id="dostup[concurent]" value="on" <?php
								if ($usersettings['dostup']['concurent'] == 'on') {
									print "checked";
								} ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="taskAlarm" class="wp100 Bold">Может ставить KPI</label>
						<div class="fs-09 blue">Разрешает редактировать KPI подчиненных в разделе Метрика</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="kpiEditor" type="checkbox" id="kpiEditor" value="yes" <?php
								if ($usersettings['kpiEditor'] == 'yes') {
									print "checked";
								} ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[5]" class="wp100 tooltips" tooltip="Сможет делать выборки по всем записям Клиентов, Контактов, Сделок" tooltip-position="top" tooltip-type="success"><b>Меню &quot;Все Сделки, Клиенты, Контакты&quot;</b></label>
						<div class="fs-09 blue">Сможет делать выборки по всем записям Клиентов, Контактов, Сделок</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[5]" type="checkbox" id="ac_import[5]" value="on" <?php
								if ($ac_import[5] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[5]" class="wp100 tooltips Bold" tooltip="Сможет делать выборки по всем записям Клиентов, Контактов, Сделок только в пределах выбранного руководителя" tooltip-position="top" tooltip-type="warning">
							<b class="red">Ограничить</b> "Все записи" в пределах руководителя
						</label>
						<div class="fs-09">Внимательно выбирайте руководителя. Сотрудник будет видеть все записи указанного руководителя и его подчиненных, если активна опция "Меню Все Сделки,..."</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<select name="filterAllBy" id="filterAllBy" class="wp97">
							<option value="">Все</option>
							<?php
							$u = User ::userBoss($iduser);
							foreach ($u as $user) {

								if ($user['id'] != $iduser) {
									print '<option value="'.$user['id'].'" '.( $user['id'] == $usersettings['filterAllBy'] ? "selected" : "" ).'>'.$user['title'].': '.$user['tip'].'</OPTION>';
								}

							}
							?>
						</select>

					</div>

					<div class="flex-string wp60 flh-12"></div>
					<div class="flex-string wp40 flh-12">

						<div>так же:</div>

						<div class="checkbox mt5">
							<label>
								<input name="hideAllContacts" type="checkbox" id="hideAllContacts" value="yes" <?php
								print ( $usersettings['hideAllContacts'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox alert"><i class="icon-ok"></i></span>
								<span class="title Bold red">Скрывать контактную информацию (телефоны/email, звездочками, кроме своих)</span>
							</label>
						</div>

						<div class="checkbox mt5 hidden">
							<label>
								<input name="filterAllByPersonCard" type="checkbox" id="filterAllByPersonCard" value="yes" <?php
								print ( $usersettings['filterAllByPersonCard'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title"><b>Контакт</b> - доступ к карточке</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="filterAllByPersonEdit" type="checkbox" id="filterAllByPersonEdit" value="yes" <?php
								print ( $usersettings['filterAllByPersonEdit'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title"><b>Контакт</b> - доступ и редактирование</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="filterAllByClientCard" type="checkbox" id="filterAllByClientCard" value="yes" <?php
								print ( $usersettings['filterAllByClientCard'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title"><b>Клиент</b> - доступ к карточке</span>
							</label>
						</div>

						<div class="checkbox mt5 hidden">
							<label>
								<input name="filterAllByClientEdit" type="checkbox" id="filterAllByClientEdit" value="yes" <?php
								print ( $usersettings['filterAllByClientEdit'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title"><b>Клиент</b> - доступ и редактирование</span>
							</label>
						</div>

						<div class="checkbox mt5">
							<label>
								<input name="filterAllByDealCard" type="checkbox" id="filterAllByDealCard" value="yes" <?php
								print ( $usersettings['filterAllByDealCard'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title"><b>Сделка</b> - доступ к карточке</span>
							</label>
						</div><b></b>

						<div class="checkbox mt5">
							<label>
								<input name="filterAllByDealEdit" type="checkbox" id="filterAllByDealEdit" value="yes" <?php
								print ( $usersettings['filterAllByDealEdit'] == 'yes' ? "checked" : "" ); ?>>
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								<span class="title"><b>Сделка</b> - доступ и редактирование</span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[20]" class="wp100 tooltips Bold" tooltip="Не сможет менять Ответственных в доступных записях Клиентов, Контактов, Сделок" tooltip-position="top" tooltip-type="success"><b class="red">Запретить</b> менять Ответственных
							<i class="icon-block-1 red"></i></label>
						<div class="fs-09">Не сможет менять Ответственных в доступных записях Клиентов, Контактов, Сделок</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[20]" type="checkbox" id="ac_import[20]" value="on" <?php
								if ($ac_import[20] == 'on') print "checked" ?>>
								<span class="custom-checkbox alert"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="taskAlarm" class="wp100 Bold"><b class="red">Запрет</b> добавлять активности
							<i class="icon-block-1 red"></i></label>
						<div class="fs-09">Запрещает добавлять любые активности в системе</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="historyAddBlock" type="checkbox" id="historyAddBlock" value="yes" <?php
								if ($usersettings['historyAddBlock'] == 'yes') {
									print "checked";
								} ?>>
								<span class="custom-checkbox alert"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="taskAlarm" class="wp100 Bold"><b class="red">Запрет</b> выполнения дел
							<i class="icon-block-1 red"></i></label>
						<div class="fs-09">Запрещает выполнять чужие напоминания ( актуально для руководителей )</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="taskCheckBlock" type="checkbox" id="taskCheckBlock" value="yes" <?php
								if ($usersettings['taskCheckBlock'] == 'yes') {
									print "checked";
								} ?>>
								<span class="custom-checkbox alert"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_prava" class="wp100 tooltips" tooltip="Сможет заходить в карточки без права редактора" tooltip-position="top" tooltip-type="success"><b>Может просматривать чужие записи</b></label>
						<div class="fs-09 blue">Сможет заходить в карточки без права редактора, также видеть номера телефонов и email из чужих записей в разделе Клиенты, Контакты, при поиске</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_prava" type="checkbox" id="acs_prava" value="on" <?php
								if ($acs_prava == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[0]" class="wp100 tooltips" tooltip="Доступ к функциям Экспорта информации" tooltip-position="top" tooltip-type="success"><b>Может Экспортировать</b></label>
						<div class="fs-09 blue">Доступ к функциям Экспорта информации</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[0]" type="checkbox" id="ac_import[0]" value="on" <?php
								if ($ac_import[0] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[1]" class="wp100 tooltips" tooltip="Доступ к функциям Импорта информации" tooltip-position="top" tooltip-type="success"><b>Может Импортировать</b></label>
						<div class="fs-09 blue">Доступ к функциям Импорта информации</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[1]" type="checkbox" id="ac_import[1]" value="on" <?php
								if ($ac_import[1] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[2]" class="wp100 tooltips" tooltip="Разрешает удаление" tooltip-position="top" tooltip-type="success"><b>Может удалять Файлы, Активности</b></label>
						<div class="fs-09 blue">Разрешает удаление файлов и активностей, Редактирование активностей</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[2]" type="checkbox" id="ac_import[2]" value="on" <?php
								if ($ac_import[2] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[3]" class="wp100 tooltips" tooltip="В списках Клиентов, Контактов, Сделок сможет видеть содержание активности" tooltip-position="top" tooltip-type="success"><b>Может видеть чужие Активности</b></label>
						<div class="fs-09 blue">В списках Клиентов, Контактов, Сделок сможет видеть содержание активности</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[3]" type="checkbox" id="ac_import[3]" value="on" <?php
								if ($ac_import[3] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[7]" class="wp100"><b>Может редактировать/удалять Напоминания</b></label>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[7]" type="checkbox" id="ac_import[7]" value="on" <?php
								if ($ac_import[7] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="show_marga" class="wp100 tooltips" tooltip="Разрешает отображение маржи, прибыли. Если используется учет маржи" tooltip-position="top" tooltip-type="success"><b>Может видеть Маржу в сделках</b></label>
						<div class="fs-09 blue">Разрешает отображение маржи, прибыли. Если используется учет маржи</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="show_marga" type="checkbox" id="show_marga" value="yes" <?php
								if ($show_marga == 'yes') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_credit" class="wp100 tooltips" tooltip="В противном случае сможет только выставлять/редактировать/удалять счета" tooltip-position="top" tooltip-type="success"><b>Может ставить оплаты</b></label>
						<div class="fs-09 blue">В противном случае сможет только выставлять/редактировать/удалять счета</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_credit" type="checkbox" id="acs_credit" value="on" <?php
								if ($acs_credit == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="acs_plan" class="wp100 tooltips" tooltip="Требуется, для учета планов по продажам" tooltip-position="top" tooltip-type="success"><b>Имеет план продаж</b></label>
						<div class="fs-09 blue">Требуется, для учета планов по продажам</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="acs_plan" type="checkbox" id="acs_plan" value="on" <?php
								if ($acs_plan == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container rowtable mb5 p10 hover">

					<div class="flex-string wp60 flh-12 pl5">
						<label for="ac_import[19]" class="wp100 tooltips" tooltip="Требуется, если в выполнении планов учитываются только личные продажи. Актуально для руководителей и учитывает только личные продажи" tooltip-position="top" tooltip-type="success"><b>План продаж индивидуальный</b></label>
						<div class="fs-09 blue">Требуется, если в выполнении планов учитываются только личные продажи. Актуально для руководителей и учитывает только личные продажи</div>
					</div>
					<div class="flex-string wp40 flh-12">

						<div class="checkbox mt5">
							<label>
								<input name="ac_import[19]" type="checkbox" id="ac_import[19]" value="on" <?php
								if ($ac_import[19] == 'on') print "checked" ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
							</label>
						</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Права по базе</b></div>
			</div>
			<div id="tab-form-12" class="p10 tab box--child">

				<div class="success mb10 green">
					Указанные настройки не действуют на администраторов
				</div>

				<div class="flex-container rowtable mb5 p10">

					<div class="flex-string wp50 fs-12 pl5">

						<b>Действия над Клиентами</b>

					</div>
					<div class="flex-string wp50 flh-12">

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[8]" type="checkbox" id="ac_import[8]" value="on" <?php
								if ($ac_import[8] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Создавать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[9]" type="checkbox" id="ac_import[9]" value="on" <?php
								if ($ac_import[9] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Редактировать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[10]" type="checkbox" id="ac_import[10]" value="on" <?php
								if ($ac_import[10] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Удалять
							</label>
						</div>

					</div>

				</div>
				<div class="flex-container rowtable mb5 p10">

					<div class="flex-string wp50 fs-12 pl5">

						<b>Действия над Контактами</b>

					</div>
					<div class="flex-string wp50 flh-12">

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[11]" type="checkbox" id="ac_import[11]" value="on" <?php
								if ($ac_import[11] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Создавать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[12]" type="checkbox" id="ac_import[12]" value="on" <?php
								if ($ac_import[12] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Редактировать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[13]" type="checkbox" id="ac_import[13]" value="on" <?php
								if ($ac_import[13] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Удалять
							</label>
						</div>

					</div>

				</div>
				<div class="flex-container rowtable mb5 p10">

					<div class="flex-string wp50 fs-12 pl5">

						<b>Действия над Сделками</b>

					</div>
					<div class="flex-string wp50 flh-12">

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[14]" type="checkbox" id="ac_import[14]" value="on" <?php
								if ($ac_import[14] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Создавать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[15]" type="checkbox" id="ac_import[15]" value="on" <?php
								if ($ac_import[15] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Редактировать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[23]" type="checkbox" id="ac_import[23]" value="on" <?php
								if ($ac_import[23] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Редактировать закрытые сделки
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[22]" type="checkbox" id="ac_import[22]" value="on" <?php
								if ($ac_import[22] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Закрывать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[21]" type="checkbox" id="ac_import[21]" value="on" <?php
								if ($ac_import[21] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Восстанавливать
							</label>
						</div>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="ac_import[16]" type="checkbox" id="ac_import[16]" value="on" <?php
								if ($ac_import[16] == 'on') print "checked" ?>>
								<span class="custom-checkbox mt5"><i class="icon-ok"></i></span>
								Удалять
							</label>
						</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Счета компаний</b></div>
			</div>
			<div id="tab-form-13" class="p10 tab box--child">

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp100 pl10 relativ">

						<div class="checkbox mt5">

							<?php
							$element = new Elements();
							print $element -> rsSelect('dostup[rc][]', [
								"class"    => "wp97 multiselect",
								"sel"      => $usersettings['dostup']['rc'],
								"multiple" => true,
								"active"   => true
							]);
							?>

						</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Настройки</b></div>
			</div>
			<div id="tab-form-5" class="p10 tab">

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="taskAlarm">Отметка "Напоминать"</label></div>
					<div class="flex-string wp70 pl10 relativ">

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="taskAlarm" type="checkbox" id="taskAlarm" value="yes" <?php
								if ($usersettings['taskAlarm'] == 'yes') {
									print "checked";
								} ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Вкл. по умолчанию в Напоминаниях
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="userTheme">Визуальная тема</label></div>
					<div class="flex-string wp70 pl10 relativ">

						<select name="userTheme" id="userTheme" class="wp97">
							<?php
							foreach ($themes as $theme => $title) {

								if (file_exists($rootpath.'/assets/css/themes/theme-'.$theme.'.css') || $theme == 'original') {

									if ($theme == 'original') {
										$theme = '';
									}
									$s = ( $usersettings['userTheme'] == $theme ) ? "selected" : "";

									if ($theme != 'custom') {
										echo '<option value="'.$theme.'" '.$s.'>'.$title.'&nbsp;&nbsp;</option>';
									}
									elseif (!$isCloud && $theme == 'custom') {
										echo '<option value="'.$theme.'" '.$s.'>'.$title.'&nbsp;&nbsp;</option>';
									}

								}

							}
							?>
						</select>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="userThemeRound" type="checkbox" id="userThemeRound" value="yes" <?php
								if ($usersettings['userThemeRound'] == 'yes') {
									print "checked";
								} ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Скругление темы
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="startTab">Стартовая вкладка</label></div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="startTab" id="startTab" class="wp97">
							<?php
							foreach ($namesTab as $tab => $title) {

								$s = ( $usersettings['startTab'] == $tab ) ? "selected" : "";

								echo '<option value="'.$tab.'" '.$s.'>'.$title.'&nbsp;&nbsp;</option>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="menuClient">Меню "Клиенты" (переход)</label>
					</div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="menuClient" id="menuClient" class="wp97">
							<option value="my" <?php
							if ($usersettings['menuClient'] == 'my') {
								print "selected";
							} ?>>Мои Клиенты
							</option>
							<option value="fav" <?php
							if ($usersettings['menuClient'] == 'fav') {
								print "selected";
							} ?>>Ключевые Клиенты
							</option>
							<?php
							if ($tipuser != "Менеджер продаж" || $ac_import[5] == 'on') { ?>
								<option value="all" <?php
								if ($usersettings['menuClient'] == 'all') {
									print "selected";
								} ?>>Все Клиенты
								</option>
								<?php
							} ?>
							<option value="otdel" <?php
							if ($usersettings['menuClient'] == 'otdel') {
								print "selected";
							} ?>>Клиенты подчиненных
							</option>
						</select>
					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="menuPerson">Меню "Контакты" (переход)</label>
					</div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="menuPerson" id="menuPerson" class="wp97">
							<option value="my" <?php
							if ($usersettings['menuPerson'] == 'my') {
								print "selected";
							} ?>>Мои Контакты
							</option>
							<?php
							if ($tipuser != "Менеджер продаж" || $ac_import[5] == 'on') { ?>
								<option value="all" <?php
								if ($usersettings['menuPerson'] == 'all') {
									print "selected";
								} ?>>Все Контакты
								</option>
								<?php
							} ?>
							<option value="otdel" <?php
							if ($usersettings['menuPerson'] == 'otdel') {
								print "selected";
							} ?>>Контакты подчиненных
							</option>
						</select>
					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="menuDeal">Меню "Продажи" (переход)</label></div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="menuDeal" id="menuDeal" class="wp97">
							<option value="my" <?php
							if ($usersettings['menuDeal'] == 'my') {
								print "selected";
							} ?>>Мои <?= $lang['face']['DealsName']['0'] ?></option>
							<?php
							if ($tipuser != "Менеджер продаж" || $ac_import[5] == 'on') { ?>
								<option value="all" <?php
								if ($usersettings['menuDeal'] == 'all') {
									print "selected";
								} ?>>Все <?= $lang['face']['DealsName']['0'] ?></option>
								<?php
							} ?>
							<option value="otdel" <?php
							if ($usersettings['menuDeal'] == 'otdel') {
								print "selected";
							} ?>><?= $lang['face']['DealsName']['0'] ?> подчиненных
							</option>
							<option value="close" <?php
							if ($usersettings['menuDeal'] == 'close') {
								print "selected";
							} ?>>Закрытые <?= $lang['face']['DealsName']['0'] ?></option>
						</select>
					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Виджеты</b></div>
			</div>
			<div id="tab-form-2" class="p10 relativ tab">

				<div class="flex-container">

					<div class="flex-string wp60 nopad">

						<table id="rowtable" class="table-1 nopad">
							<tbody>
							<?php
							//todo: переделать под новый
							//$vigets = array('d1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14');
							//$names = array('d1' => 'Воронка продаж','d2' => 'Выполнение плана','d3' => 'Дни рождения, события','d4' => 'Сделки к исполнению, продлению','d5' => 'Контроль платежей','d6' => 'Статистика','d7' => 'Прогноз продаж','d8' => 'Последние оплаты','d9' => 'Закрытые сделки','d10' => 'Воронка продаж (классик)','d11' => 'Последние активности','d12' => 'Воронка активности','d13' => 'Рейтинг по оплатам','d14' => 'Рейтинг по потенциалу');

							//текущие настройки виджетов
							$vigets = (array)$usersettings['vigets'];

							//print_r($vigets);

							if (count($vigets) == 0) {
								$vigets = $vigetsBase;
							}

							//найдем виджеты, которые не были подключены
							$diff = array_diff(array_keys((array)$vigetsBase), array_keys($vigets));

							//добавим неподключенные виджеты
							$vigetsAll = array_merge(array_keys($vigets), (array)$diff);

							$k = 1;
							foreach ($vigetsAll as $i => $viget) {

								//$chh = ($vigets[ $viget ] == 'on' || (!isset( $vigets[ $viget ] ) && $vigetsBase[ $viget ]['active'] == 'on')) ? 'checked' : '';
								$chh = ( $vigets[$viget] == 'on' ) ? 'checked' : '';

								if ($vigetsBase[$viget]['name'] != '') {

									print '
									<tr id="v-'.$viget.'" class="disable--select">
										<td width="40" valign="top">
										
											<div class="text-center clearevents">'.$k.'.</div>
											
										</td>
										<td title="'.str_replace(["{{DealsName}}"], [$lang['face']['DealName'][1]], $vigetsBase[$viget]['name']).'">
										
											<input name="order[]" id="order[]" type="hidden" value="'.$viget.'">
											<div class="flex-container box--child clearevents">
												<div class="flex-string wp100">
													<div class="Bold">'.str_replace(["{{DealsName}}"], [$lang['face']['DealName'][1]], $vigetsBase[$viget]['name']).'</div>
													<div class="fs-09 wp95 gray2 flh-10 pb5">'.$vigetsBase[$viget]['description'].'</div>
												</div>
											</div>
											
										</td>
										<td width="80">
												
											<label for="vizzible['.$viget.']" class="switch">
												<input type="checkbox" name="vizzible['.$viget.']" id="vizzible['.$viget.']" value="on" '.$chh.'>
												<span class="slider"></span>
											</label>
											
										</td>
									</tr>
									';

								}

								$k++;

							}
							?>
							</tbody>
						</table>

					</div>

					<div class="flex-string wp40 hidden-iphone">

						<div style="position: absolute" class="pl10">

							<div class="attention m0 mb20">Настройте порядок вывода виджетов рабочего стола с помощью перемещения.</div>

							<div style="background: url(/assets/images/vigetsMove.gif); background-size:cover; width: 95%; height:180px"></div>

						</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Подписки</b></div>
			</div>
			<div id="tab-form-4" class="p5 tab">

				<div class="row box--child">

					<div class="column grid-6 wp60 nopad">

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[0]"><b>Новый Клиент</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[0]" class="switch">
									<input type="checkbox" name="subscribe[0]" id="subscribe[0]" value="on" <?= ( $subscribe[0] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[1]"><b>Изменен Ответственный за Клиента</b></label></div>
							<div class="flex-string w70">

								<label for="subscribe[1]" class="switch">
									<input type="checkbox" name="subscribe[1]" id="subscribe[1]" value="on" <?= ( $subscribe[1] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[2]"><b>Удален Клиент</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[2]" class="switch">
									<input type="checkbox" name="subscribe[2]" id="subscribe[2]" value="on" <?= ( $subscribe[2] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[3]"><b>Новый Контакт</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[3]" class="switch">
									<input type="checkbox" name="subscribe[3]" id="subscribe[3]" value="on" <?= ( $subscribe[3] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[4]"><b>Изменен Ответственный за Контакт</b></label></div>
							<div class="flex-string w70">

								<label for="subscribe[4]" class="switch">
									<input type="checkbox" name="subscribe[4]" id="subscribe[4]" value="on" <?= ( $subscribe[4] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[5]"><b>Новая сделка</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[5]" class="switch">
									<input type="checkbox" name="subscribe[5]" id="subscribe[5]" value="on" <?= ( $subscribe[5] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[6]"><b>Изменения в сделке</b></label></div>
							<div class="flex-string w70">

								<label for="subscribe[6]" class="switch">
									<input type="checkbox" name="subscribe[6]" id="subscribe[6]" value="on" <?= ( $subscribe[6] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[7]"><b>Закрыта сделка</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[7]" class="switch">
									<input type="checkbox" name="subscribe[7]" id="subscribe[7]" value="on" <?= ( $subscribe[7] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[11]"><b>Получена оплата</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[11]" class="switch">
									<input type="checkbox" name="subscribe[11]" id="subscribe[11]" value="on" <?= ( $subscribe[11] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[8]"><b>Отправка файла Календаря</b></label>
							</div>
							<div class="flex-string w70">

								<label for="subscribe[8]" class="switch">
									<input type="checkbox" name="subscribe[8]" id="subscribe[8]" value="on" <?= ( $subscribe[8] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[9]"><b>Уведомление о Напоминании</b></label></div>
							<div class="flex-string w70">

								<label for="subscribe[9]" class="switch">
									<input type="checkbox" name="subscribe[9]" id="subscribe[9]" value="on" <?= ( $subscribe[9] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[10]"><b>Уведомление о Выполнении</b></label></div>
							<div class="flex-string w70">

								<label for="subscribe[10]" class="switch">
									<input type="checkbox" name="subscribe[10]" id="subscribe[10]" value="on" <?= ( $subscribe[10] == 'on' ? "checked" : "" ) ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<?php
						// собираем подписки по модулям
						$moduleevents = $hooks -> apply_filters('add_custom_subscription', []);

						foreach ($moduleevents as $mod => $title) {

							print '
							<div class="flex-container float box--child mt5 rowtable p10 ha">

								<div class="flex-string float Bold">
									<label for="usersettings[subscribs]['.$mod.']"><b>'.$title.'</b></label>
								</div>
								<div class="flex-string w70">
	
									<label for="subscribs['.$mod.']" class="switch">
										<input type="checkbox" name="subscribs['.$mod.']" id="subscribs['.$mod.']" value="on" '.( $usersettings['subscribs'][$mod] == 'on' ? "checked" : "" ).'>
										<span class="slider"></span>
									</label>
	
								</div>
	
							</div>
							';

						}
						?>

					</div>
					<div class="column grid-4 p5 hidden-iphone">

						<div class="formdiv wp95 border-box">

							<div class="attention m0 mb20">Настройте подписку на уведомления по email</div>

							<h3 class="red">Важно</h3>
							<ul>
								<li class="mb10">отправка уведомлений по Клиентам, Контактам, Сделкам производится только по Вашим записям, или по записям ваших непосредственных подчиненных</li>
								<li class="mb10">отправка уведомлений по Напоминаниям производится только по вашим Напоминаниям, а также по Назначенны Вам/Вами напоминаниям</li>
								<li class="mb10">"Отправка файла Календаря" - отправляется сообщение с вложенным Напоминанием в формате iCAL, который можно импортировать в локальный календарь</li>
							</ul>

							<?php
							$mailme = $db -> getOne("select mailme from {$sqlname}settings WHERE id = '$identity'");
							if ($mailme != 'yes') {
								?>
								<hr>                                <br><b class="red miditxt">Внимание: </b><br>
								<br>Предварительно включите отправку уведомлений в разделе:
								<b>"Панель управления" / Общие настройки / Почтовые настройки / Уведомления</b> <?php
								if ($isadmin == 'on') {
									print '<br><br><a href="iadmin.php#settings" target="blank" class="button">Перейти</a>';
								} ?>&nbsp;(или попросите руководителя).
								<?php
							} ?>

						</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Уведомления</b></div>
			</div>
			<div id="tab-form-3" class="p5 tab">

				<?php
				require_once $rootpath."/inc/class/Notify.php";

				$events          = Notify ::events();
				$eventsSubscribe = Notify ::userSubscription($iduser);
				?>

				<div class="row box--child">

					<div class="column grid-6 wp60 nopad">

						<?php
						foreach ($events as $event => $title) {

							print '
							<div class="flex-container float box--child mt5 rowtable p10 ha">
	
								<div class="flex-string float Bold"><label for="notify['.$event.']"><b>'.$title.'</b></label>
								</div>
								<div class="flex-string w70 pl10 relativ">
								
									<label for="notify['.$event.']" class="switch">
										<input type="checkbox" name="notify['.$event.']" id="notify['.$event.']" value="on" '.( in_array($event, $eventsSubscribe) || ( (int)$xuser == 0 && empty($eventsSubscribe)) ? "checked" : "" ).'>
										<span class="slider"></span>
									</label>
	
								</div>
	
							</div>';

						}
						?>

					</div>
					<div class="column grid-4 p5 hidden-iphone">

						<div class="formdiv wp95 border-box">

							<div class="attention m0">Здесь вы можете выбрать те уведомления, которые хотите получать внутри системы.</div>

							<h3 class="red">Важно</h3>

							<ul class="p0 pl10">
								<li class="mb10">по-умолчанию вы будете получать все уведомления (если не выбраны конкретные)</li>
								<li class="mb10">уведомления по Клиентам, Контактам, Сделкам производится только по Вашим записям, или по записям ваших непосредственных подчиненных</li>
								<li class="mb10">отправка уведомлений по Напоминаниям производится только по вашим Напоминаниям, а также по Назначенным Вам/Вами напоминаниям</li>
							</ul>

						</div>

					</div>

				</div>

			</div>

		</DIV>

		<hr>

		<div class="text-right button--pane">

			<div id="cancelbutton"><A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A></div>
			<div id="fakebutton">
				<A href="javascript:void(0)" onclick="Swal.fire('Надо заполнить данные')" class="button">Сохранить</A>
			</div>
			<div id="submitbutton" class="hidden">
				<A href="javascript:void(0)" onclick="$('#userForm').trigger('submit')" class="button">Сохранить</A>
			</div>&nbsp;

		</div>

	</FORM>
	<?php
}

?>
<script>

	/*tooltips*/
	$('.tooltips').append("<span></span>");
	$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
	$(".tooltips").on('mouseenter', function () {
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/

	var $isCloud = '<?=$isCloud?>';
	var hh = $('#dialog_container').actual('height') * 0.8;
	var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight');

	if (!isMobile) {

		if ($(window).width() > 990) $('#dialog').css({'width': '850px'});
		else $('#dialog').css('width', '90vw');

		$('#formtabse').css({'max-height': hh2 + 'px'});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 60;

		$('#formtabse').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

	}

	$(function () {

		$(".multiselect").multiselect({sortable: true, searchable: true});

		$(".table-1").tableDnD({
			onDragClass: "tableDrag",
			onDrop: function (table, row) {
			}
		});
		$(".table-2").tableDnD({
			onDragClass: "tableDrag",
			onDrop: function (table, row) {
			}
		});

		checkuserpass('pwd');
		checkuser('email');

		$("#bday").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true,
			yearRange: "1940:2020",
			minDate: new Date(1940, 1 - 1, 1)
		});
		$("#CompStart").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true,
			yearRange: "1990:2020",
			minDate: new Date(1940, 1 - 1, 1)
		});
		$("#CompEnd").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true,
			yearRange: "1990:2020",
			minDate: new Date(1940, 1 - 1, 1)
		});

		if ($isCloud === 'true')
			checkuser('login2');

		$('#tip').trigger('change');

		$('#dialog').center();

	});

	$(document).off('change', '#tip');
	$(document).on('change', '#tip', function () {

		var tip = $(this).val();

		//console.log(tip);

		if (tip === 'Руководитель организации') {

			$('#mid2 option').attr("disabled", "disabled");
			$('#mid2 option[value="0"]').removeAttr("disabled");

		}
		else
			$('#mid2 option').removeAttr("disabled");

	});

	$('#userForm').ajaxForm({
		beforeSubmit: function () {

			var tip = $('#tip option:selected').val();
			var mid2 = $('#mid2 option:selected').val();
			var $out = $('#message');
			var em = 0;

			$(".required").closest('.flex-container').removeClass("warning");
			$(".required").each(function () {

				if ($(this).val() === '') {

					$(this).closest('.flex-container').addClass("warning");
					em = em + 1;

				}

			});

			$out.empty();

			if (tip !== 'Руководитель организации' && mid2 === '') {

				$('#mid2').css({color: "#FFF", background: "#FF8080"});

				Swal.fire("Укажите руководителя", "", "info");

				return false;

			}
			if (em > 0) {

				Swal.fire("Не заполнены обязательные поля", "Они выделены цветом", "warning");

				return false;

			}
			else if (em === 0) {

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$('#message').css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			}
		},
		success: function (data) {

			getUsersList();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}
	});

	if ($isCloud === 'true') {

		$('#login').off('mouseleave focusout keyup');
		$('#login').on('mouseleave focusout keyup', function () {

			checkuser('login');

		});

	}
	else {

		$('#email').off('mouseleave focusout keyup');
		$('#email').on('mouseleave focusout keyup', function () {

			checkuser('email');

		});

	}

	$('#pwd').off('mouseleave focusout keyup');
	$('#pwd').on('mouseleave focusout keyup', function () {

		checkuserpass('pwd');

	});

</script>