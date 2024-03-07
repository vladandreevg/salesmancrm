<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Notify;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$oldvigets = [
	"d1"  => "voronka",
	"d2"  => "analitic",
	"d3"  => "bethday",
	"d4"  => "dogs_renew",
	"d5"  => "credit",
	"d6"  => "stat",
	"d7"  => "prognoz",
	"d8"  => "payment",
	"d9"  => "dogsclosed",
	"d10" => "voronka_conus",
	"d11" => "history",
	"d12" => "voronka_classic",
	"d13" => "raiting_payment",
	"d14" => "raiting_potential"
];

$action   = $_REQUEST['action'];
$thistime = date('G:i', mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')));

function resize_image($image_from, $image_to, $width, $height): bool {

	$image_vars = getimagesize($image_from);
	$src_width  = $image_vars[0];
	$src_height = $image_vars[1];
	$src_type   = $image_vars[2];

	if ($width > $src_width) $width = $src_width;
	if ($height > $src_height) $height = $src_height;

	//if ($src_width < $width) $width = $width;
	$height = $width * ($src_height / $src_width);

	if ($height < '300') {
		$height = '300';
		$width  = $height / ($src_height / $src_width);
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

if ($action == "reset") {

	$f = $rootpath.'/cash/desktop_tabs_'.$iduser1.'.txt';
	unlink($f);

	print "Готово. Обновите окно браузера";
	exit();

}
if ($action == "avatarupload") {

	$myavatar = $db -> getOne("select avatar from {$sqlname}user where iduser='".$iduser1."' and identity = '$identity'");

	//органичение сервера по размеру файла
	$maxupload = str_replace([
		'M',
		'm'
	], '', @ini_get('upload_max_filesize'));

	$cur_ext    = texttosmall(getExtention($_FILES['file']['name']));
	$uploaddir  = $rootpath.'/cash/avatars/';
	$file       = time().'avatar-'.$iduser1.'.'.$cur_ext;
	$uploadfile = $uploaddir.$file;//новое имя файла

	$file_ext_allow = [
		'png',
		'jpg',
		'jpeg',
		'gif',
		'webp'
	];

	createDir($rootpath.'/cash/avatars');

	if (in_array($cur_ext, $file_ext_allow)) {

		if ((filesize($_FILES['file']['tmp_name']) / 1000000) > $maxupload) {

			$error = '<b class="red">Ошибка:</b> Превышает допустимые размеры!<br />';

		}
		else {

			if ($myavatar != '') {
				unlink( $rootpath.'/cash/avatars/'.$myavatar );
			}

			if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {

				$res = $file;
				//if(extension_loaded("gd"))
				//tumbimage($uploadfile);
				resize_image($uploadfile, $uploadfile, 300, 300);

				$db -> query("update {$sqlname}user set avatar = '$file' where iduser = '$iduser1' and identity = '$identity'");

			}
			else {
				$error = '<b class="red">Ошибка:</b> '.$_FILES['file']['error'].'<br />';
			}

		}
	}
	else {
		$error = 'Допустимые форматы: PNG,JPG,JPEG,GIF,WEBP';
	}

	$rez = [
		"res"   => $res,
		"error" => $error
	];
	print $rez = json_encode_cyr($rez);

	exit();

}
if ($action == "save") {

	$res     = $db -> getRow("SELECT * FROM {$sqlname}user WHERE ses='".$_COOKIE['ses']."'");
	$pwd_tek = $res["pwd"];
	$salt    = $res["sole"];
	$usersettings = json_decode($res["usersettings"], true);

	$login = untag($_POST['login']);
	$pwd   = encodePass($_POST['pwd'], $salt);

	$mes = '';

	if ($_POST['pwd2'] != '') {

		$pwd = encodePass($_POST['pwd2'], $salt);
		$db -> query("update {$sqlname}user set pwd = '$pwd' where iduser = '$iduser1' and identity = '$identity'");

	}
	else {
		$mes .= "Пароль не изменен";
	}


	$title       = untag($_POST['title']);
	$email       = untag($_POST['email']);
	$export_lock = untag($_POST['export_lock']);

	if ($GLOBALS['isCloud']) {
		$email = $login;
	}

	$phone  = untag($_POST['phone']);
	$fax    = untag($_POST['fax']);
	$mob    = untag($_POST['mob']);
	$bday   = $_POST['bday'];
	$tzonee = $_POST['tzonee'];
	$zam    = (int)$_POST['zam'];

	$subscribe = (array)$_POST['subscribe'];

	ksort($subscribe);

	for ($i = 0; $i < 18; $i++) {
		if ($subscribe[ $i ] != 'on') {
			$subscribe[ $i ] = 'off';
		}
	}

	ksort($subscribe);
	$subscribe = implode(";", $subscribe);

	$viget_on    = (array)$_REQUEST['vizzible'];
	$viget_order = (array)$_REQUEST['order'];


	$param = $userSettings;

	$param['vigets'] = [];
	foreach ($viget_on as $key => $value) {

		$param['vigets'][ $key ] = $value;

	}

	//print_r($viget_on);
	//print_r($param['vigets']);

	/*foreach ($viget_order as $key => $value) {

		$param['vigets'][ $value ] = ($viget_on[ $value ] == '') ? 'off' : 'on';

	}*/

	//настройки юзера разные
	$param['taskAlarm']      = $_REQUEST['taskAlarm'];
	$param['userTheme']      = $_REQUEST['userTheme'];
	$param['userThemeRound'] = $_REQUEST['userThemeRound'];
	$param['startTab']       = $_REQUEST['startTab'];
	$param['menuClient']     = $_REQUEST['menuClient'];
	$param['menuPerson']     = $_REQUEST['menuPerson'];
	$param['menuDeal']       = $_REQUEST['menuDeal'];
	$param['notify']         = array_keys($_REQUEST['notify']);
	$param['filterAllBy']    = $userSettings['filterAllBy'];
	$param['subscribs']    = $_REQUEST['subscribs'];
	//$usersettings       = json_encode_cyr($param);

	//проверим часовой пояс
	//если значение не корректно (больше 12), то игнорируем смещение временной зоны
	$totalTimeZone = $tzonee + (int)$dzz / 3600;
	if ($totalTimeZone > 12) {

		$tzonee = 0;
		$mes    .= '<br>Смещение часовой зоны игнорировано - не допустимое конечное значение';

	}

	$db -> query("UPDATE {$sqlname}user SET ?u WHERE iduser = '$iduser1'", arrayNullClean([
		"login"        => $login,
		"title"        => $title,
		"email"        => $email,
		"phone"        => $phone,
		"fax"          => $fax,
		"mob"          => $mob,
		"bday"         => ($bday != '') ? $bday : null,
		"tzone"        => $tzonee,
		"zam"          => $zam,
		"subscription" => $subscribe,
		"usersettings" => json_encode_cyr($param)
	]));

	$mes .= '<br>Настройки сохранены';


	if ($GLOBALS['tipuser'] == 'Руководитель организации') {

		$db -> query("update {$sqlname}settings set export_lock = '".$export_lock."' WHERE id = '$identity'");
		unlink($rootpath."/cash/".$fpath."settings.all.json");

	}

	unlink($rootpath."/cash/".$fpath."settings.user.".$iduser1.".json");

	print $mes;
	print "<br>Обновите окно браузера => клавиши F5 или Ctrl+F5";

	exit();

}

if ($action == "order") {

	$viget_order = $_REQUEST['vg'];

	$uset = $userSettings;

	$uset['vigets'] = $on = $order = array();

	foreach ($viget_order as $key => $value) {
		$on[]                                        = 'on';
		$order[]                                     = strtr($value, array_flip($oldvigets));
		$uset['vigets'][ strtr($value, $oldvigets) ] = 'on';
	}

	$on    = implode(";", $on);
	$order = implode(";", $order);

	//$db -> query("update {$sqlname}user set viget_on = '$on', viget_order = '$order' where iduser = '$iduser1' and identity = '$identity'");

	$db -> query("UPDATE {$sqlname}user SET ?u WHERE iduser = '$iduser1'", array(
		"viget_on"     => $on,
		"viget_order"  => $order,
		"usersettings" => json_encode_cyr($uset)
	));

	unlink($rootpath."/cash/".$fpath."settings.user.".$iduser1.".json");

	print 'Сохранено';

	exit();
}
if ($action == "edit_order") {

	$table1 = explode(';', implode(';', $_REQUEST[ 'table-1']));
	$count1 = count($_REQUEST['table-1']);
	$err    = 0;

	$uset = $userSettings;

	$von = $uset['vigets'];

	$uset['vigets'] = $sort = [];

	//Обновляем данные для текущей записи
	for ($i = 0; $i < $count1; $i++) {
		$sort[]                                             = $table1[ $i ];
		$uset['vigets'][ strtr($table1[ $i ], $oldvigets) ] = $von[ strtr($table1[ $i ], $oldvigets) ];
	}

	//$db -> query("update {$sqlname}user set viget_order = '$sort' where iduser = '$iduser1' and identity = '$identity'");

	$db -> query("UPDATE {$sqlname}user SET ?u WHERE iduser = '$iduser1'", [
		"viget_order"  => yimplode(";", $sort),
		"usersettings" => json_encode_cyr($uset)
	]);

	unlink($rootpath."/cash/".$fpath."settings.user.".$iduser1.".json");

	print "Обновлено.";

	exit();
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ($action == "edit") {

	$res          = $db -> getRow("select * from {$sqlname}user where iduser='$iduser1' and identity = '$identity'");
	$title        = $res["title"];
	$tip          = $res["tip"];
	$otdel        = $res["otdel"];
	$mid          = $res["mid"];
	$territory    = $res["territory"];
	$office       = $res["office"];
	$phone        = $res["phone"];
	$fax          = $res["fax"];
	$mob          = $res["mob"];
	$email        = $res["email"];
	$bday         = $res["bday"];
	$gcalendar    = $res["gcalendar"];
	$tzonee       = $res["tzone"];
	$zam          = $res["zam"];
	$viget_on     = explode(";", $res["viget_on"]);
	$order        = $res["viget_order"];
	$viget_order  = explode(";", $order);
	$subscription = $res["subscription"];
	$subscribe    = explode(";", $subscription);

	// различные настройки пользователя
	$usersettings = json_decode($res["usersettings"], true);

	// подписки на email-уведомления из модулей
	$modulesubscribs = (!empty($usersettings['subscribs'])) ? $usersettings['subscribs'] : [];

	// собираем подписки по модулям
	$moduleevents = $hooks-> apply_filters('add_custom_subscription', []);

	//print_r($moduleevents);

	if ($tip == 'Руководитель организации') {
		$export_lock = $db -> getOne("SELECT export_lock from {$sqlname}settings WHERE id = '$identity'");
	}

	$vigetsBase = $vigetsCustom = [];

	$themes     = json_decode(str_replace([
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents($rootpath.'/cash/themes.json')), true);

	$vigetsBase = json_decode(str_replace([
		"  ",
		"\t",
		"\n",
		"\r"
	], "", file_get_contents($rootpath."/cash/map.vigets.json")), true);

	if (file_exists($rootpath."/cash/map.vigets.castom.json")) {
		$vigetsCustom = json_decode( str_replace( [
			"  ",
			"\t",
			"\n",
			"\r"
		], "", file_get_contents( $rootpath."/cash/map.vigets.castom.json" ) ), true );
	}

	$vigetsBase = array_merge((array)$vigetsBase, (array)$vigetsCustom);

	$totalTimeZone = $dzz / 3600;

	/**
	 * Имена подразделов Рабочего стола
	 */
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

	?>

	<DIV class="zagolovok">Персональные настройки</DIV>

	<FORM action="/content/ajax/user.settings.php" method="post" id="uset" name="uset" enctype="multipart/form-data">
		<INPUT type="hidden" name=action id="action" value="save">
		<INPUT type="hidden" name="tip" id="tip" value="<?= $tip ?>">
		<INPUT type="hidden" name="iduser" id="iduser" value="<?= $iduser1 ?>">

		<DIV id="formtabs" class="transparent no-border relative" style="overflow-y: auto; height: 80vh">

			<ul class="hidden">
				<li><a href="#tab-form-1">Данные</a></li>
				<li><a href="#tab-form-5">Настройки</a></li>
				<li><a href="#tab-form-2">Виджеты</a></li>
				<li class="hidden"><a href="#tab-form-3">Вкладки</a></li>
				<li><a href="#tab-form-4">Подписки</a></li>
			</ul>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Данные</b></div>
			</div>
			<div id="tab-form-1" class="p10 pt10 tab">

				<div class="flex-container box--child">

					<div class="flex-string wp20">

						<div class="avatarbig div-center" style="background: url(<?= $avatar ?>); background-size:cover;"></div>
						<div class="togglerbox hand avatarchange div-center" data-id="avatarform">Изменить</div>

					</div>
					<div class="flex-string wp80">

						<div class="flex-container wp100 mb10">

							<div class="flex-string wp20 fs-11 gray2 text-right pt10 pr5">
								Логин<?php if ($isCloud) print "/Email"; ?>:
							</div>
							<div class="flex-string wp30 relativ">
								<INPUT name="login" type="text" class="required wp90" id="login" value="<?= $login ?>">
								<?php if ($isCloud) { ?>
									<div id="emailvalidate" class="hidden">&nbsp;</div>
								<?php } ?>
							</div>

							<div class="flex-string wp20 fs-12 gray2 text-right pt5 pr5">
								Пароль (новый):
							</div>
							<div class="flex-string wp30 relativ">
								<input name="pwd2" type="password" id="pwd2" autocomplete="off" class="wp90" data-type="password">
								<div class="showpass mr10" id="showpass">
									<i class="icon-eye-off hand gray" title="Посмотреть пароль"></i>
								</div>
								<i class="icon-info-circled red hidden" title="Указать только в случае смены пароля"></i>
								<div id="passstrength" style="top: unset; bottom: 120%">&nbsp;</div>
							</div>

						</div>
						<div class="flex-container wp100 mb10">

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">ФИО:</div>
							<div class="flex-string wp30 ">
								<input name="title" type="text" id="title" class="required wp90" value="<?= $title ?>"/>
							</div>

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">
								<?php if (!$isCloud) { ?>Почта:<?php } ?>
							</div>
							<div class="flex-string wp30 ">
								<?php if (!$isCloud) { ?>
									<input name="email" type="text" id="email" value="<?= $email ?>" class="required wp90">
								<?php } ?>
							</div>

						</div>
						<div class="flex-container wp100 mb10">

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">Телефон:</div>
							<div class="flex-string wp30 ">
								<input name="phone" type="text" id="phone" value="<?= $phone ?>" class="wp90">
							</div>

							<div class="flex-string wp20 fs-12 gray2 text-right pt5 pr5">День Рождения:</div>
							<div class="flex-string wp30 ">
								<input name="bday" type="text" id="bday" value="<?= $bday ?>" class="wp90">
							</div>

						</div>
						<div class="flex-container wp100 mb10">

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">Мобильный:</div>
							<div class="flex-string wp30 ">
								<input name="mob" type="text" id="mob" value="<?= $mob ?>" class="wp90">
							</div>

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">Факс:</div>
							<div class="flex-string wp30 ">
								<input name="fax" type="text" id="fax" value="<?= $fax ?>" class="wp90">
							</div>

						</div>
						<div class="flex-container wp100 mb10">

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">Часовой пояс:</div>
							<div class="flex-string wp20 ">
								<select name="tzonee" id="tzonee">
									<?php
									for ($i = -12; $i < 13; $i++) {

										$t  = abs($i);
										$dd = abs($totalTimeZone + $i);

										$znak = ($i < 0) ? "-" : "+";
										$s    = ($i == $tzone) ? "selected" : '';
										$d    = ($dd > 12) ? ' disabled' : '';

										print '<option '.$s.$d.' value="'.$znak.$t.'">'.$znak." ".$t.':00</option>';

									}
									?>
								</select>&nbsp;
							</div>

							<div class="flex-string wp60 p0 em gray2">
								Задает смещение текущего времени от времени на сервере.<br>На сервере
								<b class="blue"><?= $thistime ?></b> - временная зона
								<b class="blue"><?= $tmzone ?></b>
							</div>

						</div>
						<div class="flex-container wp100 mb10">

							<div class="flex-string wp20 fs-12 gray2 text-right pt10 pr5">
								Заместитель:
							</div>
							<div class="flex-string wp30 ">
								<select name="zam" id="zam" class="wp90">
									<option value="">--не указан--</option>
									<?php
									$users = \Salesman\User ::userCatalog();
									foreach ($users as $i => $user) {

										if ($user['id'] != $iduser1) {

											$s = ($user['id'] == $zam) ? "selected" : "";

											print '<option '.$s.' value="'.$user['id'].'">'.str_repeat("&raquo;&nbsp;&nbsp;", $user['level']).$user['title'].'</option>';

										}
									}
									?>
								</select>
							</div>

							<div class="flex-string wp20 fs-10 gray2 text-right pt10 pr5">
								<?php if ($tip == 'Руководитель организации') print '<b class="red">Защита экспорта:</b>'; ?>
							</div>
							<div class="flex-string wp30 ">
								<?php if ($tip == 'Руководитель организации') print '<input name="export_lock" type="text" id="export_lock" value="'.$export_lock.'" style="width:80%">&nbsp;<i class="icon-ok blue" title="Дополнительная защита функции экспорта - личный пароль руководителя организации"></i>'; ?>
							</div>

						</div>

					</div>

				</div>

				<div class="div-center paddtop10">
					<a href="javascript:void(0)" onclick="changeShowIntro()" class="button">Показывать тур Знакомство</a>
				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Настройки</b></div>
			</div>
			<div id="tab-form-5" class="p10 pt10 tab">

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="openCardInFrame">Открытие карточек:</label></div>
					<div class="flex-string wp70 pl10 relativ">

						<div class="radio mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="openCardInFrame" type="radio" id="openCardInFrame" value="1">
								<span class="custom-radio success"><i class="icon-radio-check"></i></span>
								Открывать во фрейме (в текущей вкладке)
							</label>
						</div>

						<div class="radio mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="openCardInFrame" type="radio" id="openCardInFrame" value="0">
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								Открывать в новом окне
							</label>
						</div>

						<div class="em gray2">Сохраняется только для вашего компьютера и браузера</div>

					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7">
						<label for="taskAlarm">Быстрый просмотр Клиента, Контакта, Сделки:</label></div>
					<div class="flex-string wp70 pl10 relativ">

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="viewAsOpen" type="checkbox" id="viewAsOpen" value="1">
								<span class="custom-checkbox success"><i class="icon-ok"></i></span>
								Открывать карточку вместо быстрого просмотра
							</label>
						</div>

						<div class="em gray2">Сохраняется только для вашего компьютера и браузера</div>

					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="taskAlarm">Отметка "Напоминать":</label></div>
					<div class="flex-string wp70 pl10 relativ">

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="taskAlarm" type="checkbox" id="taskAlarm" value="yes" <?php if ($usersettings['taskAlarm'] == 'yes') print "checked"; ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Вкл. по умолчанию в Напоминаниях
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="userTheme">Визуальная тема:</label></div>
					<div class="flex-string wp70 pl10 relativ">

						<select name="userTheme" id="userTheme" class="wp97" onchange="switchTheme()">
							<?php
							foreach ($themes as $theme => $title) {

								if (file_exists($rootpath.'/assets/css/themes/theme-'.$theme.'.css') || $theme == 'original') {

									if ($theme == 'original') {
										$theme = '';
									}
									$s = ($usersettings['userTheme'] == $theme) ? "selected" : "";

									if ($theme != 'custom') {
										echo '<option value="'.$theme.'" '.$s.'>'.$title.'&nbsp;&nbsp;</option>';
									}
									elseif (!$isCloud) {
										echo '<option value="'.$theme.'" '.$s.'>'.$title.'&nbsp;&nbsp;</option>';
									}

								}

							}
							?>
						</select>

						<div class="checkbox mt5">
							<label class="like-input wp97 pt7 pb5">
								<input name="userThemeRound" type="checkbox" id="userThemeRound" value="yes" <?php if ($usersettings['userThemeRound'] == 'yes') print "checked"; ?>>
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								Скругление темы
							</label>
						</div>

					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="startTab">Стартовая вкладка:</label></div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="startTab" id="startTab" class="wp97">
							<?php
							foreach ($namesTab as $tab => $title) {

								$s = ($usersettings['startTab'] == $tab) ? "selected" : "";

								echo '<option value="'.$tab.'" '.$s.'>'.$title.'&nbsp;&nbsp;</option>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="menuClient">Меню "Клиенты" (переход):</label>
					</div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="menuClient" id="menuClient" class="wp97">
							<option value="my" <?php if ($usersettings['menuClient'] == 'my') print "selected"; ?>>Мои Клиенты</option>
							<option value="fav" <?php if ($usersettings['menuClient'] == 'fav') print "selected"; ?>>Ключевые Клиенты</option>
							<?php if ($tipuser != "Менеджер продаж" || $userRights['alls']) { ?>
								<option value="all" <?php if ($usersettings['menuClient'] == 'all') print "selected"; ?>>Все Клиенты</option>
							<?php } ?>
							<option value="otdel" <?php if ($usersettings['menuClient'] == 'otdel') print "selected"; ?>>Клиенты подчиненных</option>
						</select>
					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="menuPerson">Меню "Контакты" (переход):</label>
					</div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="menuPerson" id="menuPerson" class="wp97">
							<option value="my" <?php if ($usersettings['menuPerson'] == 'my') print "selected"; ?>>Мои Контакты</option>
							<?php if ($tipuser != "Менеджер продаж" || $userRights['alls']) { ?>
								<option value="all" <?php if ($usersettings['menuPerson'] == 'all') print "selected"; ?>>Все Контакты</option>
							<?php } ?>
							<option value="otdel" <?php if ($usersettings['menuPerson'] == 'otdel') print "selected"; ?>>Контакты подчиненных</option>
						</select>
					</div>

				</div>

				<div class="flex-container box--child mt5 rowtable p10">

					<div class="flex-string wp30 Bold pt7"><label for="menuDeal">Меню "Продажи" (переход):</label></div>
					<div class="flex-string wp70 pl10 relativ">
						<select name="menuDeal" id="menuDeal" class="wp97">
							<option value="my" <?php if ($usersettings['menuDeal'] == 'my') print "selected"; ?>>Мои <?= $lang['face']['DealsName']['0'] ?></option>
							<?php if ($tipuser != "Менеджер продаж" || $userRights['alls']) { ?>
								<option value="all" <?php if ($usersettings['menuDeal'] == 'all') print "selected"; ?>>Все <?= $lang['face']['DealsName']['0'] ?></option>
							<?php } ?>
							<option value="otdel" <?php if ($usersettings['menuDeal'] == 'otdel') print "selected"; ?>><?= $lang['face']['DealsName']['0'] ?> подчиненных</option>
							<option value="close" <?php if ($usersettings['menuDeal'] == 'close') print "selected"; ?>>Закрытые <?= $lang['face']['DealsName']['0'] ?></option>
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

						<table id="rowtable" class="table-1 nopad top">
							<tbody>
							<?php
							//текущие настройки виджетов
							$vigets = $usersettings['vigets'];

							//print_r($vigets);

							if (count((array)$vigets) == 0) {
								$vigets = $vigetsBase;
							}

							//найдем виджеты, которые не были подключены
							$diff = array_diff(array_keys($vigetsBase), array_keys($vigets));

							//добавим неподключенные виджеты
							$vigetsAll = array_merge(array_keys($vigets), $diff);

							//print_r($vigetsAll);
							//print_r($vigetsBase);

							$k = 1;
							foreach ($vigetsAll as $i => $viget) {

								//print_r($vigetsBase[ $viget ]);
								//continue;

								$folder = stripos( (string)$vigetsBase[ $viget ]['url'], 'vigets' ) !== false ? $rootpath."/content/".$vigetsBase[ $viget ]['url'] : $rootpath."/".$vigetsBase[ $viget ]['url'];

								//print $folder."<br>";

								//$chh = ($vigets[ $viget ] == 'on' || (!isset($vigets[ $viget ]) && $vigetsBase[ $viget ]['active'] == 'on')) ? 'checked' : '';
								//$sel = ($vigets[ $viget ] == 'on' || (!isset($vigets[ $viget ]) && $vigetsBase[ $viget ]['active'] == 'on')) ? 'on' : 'off';

								$chh = $vigets[ $viget ] == 'on' ? 'checked' : '';
								$sel = $vigets[ $viget ] == 'on' ? 'on' : 'off';

								if ($vigetsBase[ $viget ]['name'] != '' && file_exists($folder)) {

									print '
									<tr id="v-'.$viget.'" class="disable--select">
										<td class="w40">
											<div class="text-center clearevents">'.$k.'.</div>
										</td>
										<td title="'.str_replace( ["{{DealsName}}"], [$lang[ 'face' ][ 'DealName' ][ 1 ]], $vigetsBase[ $viget ][ 'name' ] ).'">
											<input name="order[]" id="order[]" type="hidden" value="'.$viget.'">
											<div class="flex-container box--child clearevents">
												<div class="flex-string wp100">
													<div class="Bold">'.str_replace( ["{{DealsName}}"], [$lang[ 'face' ][ 'DealName' ][ 1 ]], $vigetsBase[ $viget ][ 'name' ] ).'</div>
													<div class="fs-09 wp95 gray2 flh-10 pb5">'.$vigetsBase[ $viget ][ 'description' ].'</div>
												</div>
											</div>
										</td>
										<td class="w80 text-center">
												
											<label for="vizzible['.$viget.']" class="switch">
												<input type="checkbox" name="vizzible['.$viget.']" id="vizzible['.$viget.']" value="on" '.$chh.'>
												<span class="slider"></span>
											</label>
											
										</td>
									</tr>
									';

									$k++;

								}

							}
							?>
							</tbody>
						</table>

					</div>

					<div class="flex-string wp40 hidden-iphone">

						<div style="position: absolute" class="pl10">

							<div class="attention m0 mb20">Здесь вы можете настроить порядок вывода виджетов рабочего стола с помощью перемещения.</div>

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
							<div class="flex-string w70 text-center">

								<label for="subscribe[0]" class="switch">
									<input type="checkbox" name="subscribe[0]" id="subscribe[0]" value="on" <?= ($subscribe[0] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[1]"><b>Изменен Ответственный за Клиента</b></label></div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[1]" class="switch">
									<input type="checkbox" name="subscribe[1]" id="subscribe[1]" value="on" <?= ($subscribe[1] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[2]"><b>Удален Клиент</b></label>
							</div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[2]" class="switch">
									<input type="checkbox" name="subscribe[2]" id="subscribe[2]" value="on" <?= ($subscribe[2] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[3]"><b>Новый Контакт</b></label>
							</div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[3]" class="switch">
									<input type="checkbox" name="subscribe[3]" id="subscribe[3]" value="on" <?= ($subscribe[3] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[4]"><b>Изменен Ответственный за Контакт</b></label></div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[4]" class="switch">
									<input type="checkbox" name="subscribe[4]" id="subscribe[4]" value="on" <?= ($subscribe[4] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[5]"><b>Новая сделка</b></label>
							</div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[5]" class="switch">
									<input type="checkbox" name="subscribe[5]" id="subscribe[5]" value="on" <?= ($subscribe[5] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[6]"><b>Изменения в сделке</b></label></div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[6]" class="switch">
									<input type="checkbox" name="subscribe[6]" id="subscribe[6]" value="on" <?= ($subscribe[6] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold"><label for="subscribe[7]"><b>Закрыта сделка</b></label>
							</div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[7]" class="switch">
									<input type="checkbox" name="subscribe[7]" id="subscribe[7]" value="on" <?= ($subscribe[7] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[11]"><b>Получена оплата</b></label>
							</div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[11]" class="switch">
									<input type="checkbox" name="subscribe[11]" id="subscribe[11]" value="on" <?= ($subscribe[11] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[8]"><b>Отправка файла Календаря</b></label>
							</div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[8]" class="switch">
									<input type="checkbox" name="subscribe[8]" id="subscribe[8]" value="on" <?= ($subscribe[8] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[9]"><b>Уведомление о Напоминании</b></label></div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[9]" class="switch">
									<input type="checkbox" name="subscribe[9]" id="subscribe[9]" value="on" <?= ($subscribe[9] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<div class="flex-container float box--child mt5 rowtable p10 ha">

							<div class="flex-string float Bold">
								<label for="subscribe[10]"><b>Уведомление о Выполнении</b></label></div>
							<div class="flex-string w70 text-center">

								<label for="subscribe[10]" class="switch">
									<input type="checkbox" name="subscribe[10]" id="subscribe[10]" value="on" <?= ($subscribe[10] == 'on' ? "checked" : "") ?>>
									<span class="slider"></span>
								</label>

							</div>

						</div>

						<?php
						foreach($moduleevents as $mod => $title){

							print '
							<div class="flex-container float box--child mt5 rowtable p10 ha">

								<div class="flex-string float Bold">
									<label for="usersettings[subscribs]['.$mod.']"><b>'.$title.'</b></label>
								</div>
								<div class="flex-string w70 text-center">
	
									<label for="subscribs['.$mod.']" class="switch">
										<input type="checkbox" name="subscribs['.$mod.']" id="subscribs['.$mod.']" value="on" '. ($usersettings['subscribs'][$mod] == 'on' ? "checked" : "").'>
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

							<div class="attention m0">Здесь вы можете настроить подписку на уведомления, которые хотите получать по email.</div>

							<h3 class="red">Важно</h3>
							<ul class="p0 pl10">
								<li class="mb10">отправка уведомлений по Клиентам, Контактам, Сделкам производится только по Вашим записям, или по записям ваших непосредственных подчиненных</li>
								<li class="mb10">отправка уведомлений по Напоминаниям производится только по вашим Напоминаниям, а также по Назначенным Вам/Вами напоминаниям</li>
								<li class="mb10">"Отправка файла Календаря" - отправляется сообщение с вложенным Напоминанием в формате iCAL, который можно импортировать в локальный календарь</li>
							</ul>

							<?php
							$mailme = $db -> getOne("select mailme from {$sqlname}settings WHERE id = '$identity'");
							if ($mailme != 'yes') {
								?>
								<hr>
								<br><b class="red miditxt">Внимание: </b><br>
								<br>Предварительно включите отправку уведомлений в разделе:
								<b>"Панель управления" / Общие настройки / Почтовые настройки / Уведомления</b> <?php if ($isadmin == 'on') print '<br><br><a href="iadmin.php#settings" target="blank" class="button">Перейти</a>'; ?>&nbsp;(или попросите руководителя).
							<?php } ?>

						</div>

					</div>

				</div>

			</div>

			<div class="wp100">
				<div id="divider" class="div-center"><b>Уведомления</b></div>
			</div>
			<div id="tab-form-3" class="p5 tab">

				<?php
				//require_once "../inc/class/Notify.php";

				$events          = Notify::events();
				$eventsSubscribe = Notify ::userSubscription($iduser1);
				?>

				<div class="row box--child">

					<div class="column grid-6 wp60 nopad">

						<?php
						foreach ($events as $event => $title) {

							print '
							<div class="flex-container float box--child mt5 rowtable p10 ha">
	
								<div class="flex-string float Bold"><label for="notify['.$event.']"><b>'.$title.'</b></label>
							</div>
								<div class="flex-string w70 text-center relativ">
								
									<label for="notify['.$event.']" class="switch">
										<input type="checkbox" name="notify['.$event.']" id="notify['.$event.']" value="on" '.(in_array($event, (array)$eventsSubscribe) || empty($eventsSubscribe) ? "checked" : "").'>
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

		<DIV class="text-right button--pane">

			<div id="cancelbutton"><A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A></div>
			<div id="fakebutton">
				<A href="javascript:void(0)" onclick="alert('Надо заполнить данные')" class="button">Сохранить</A>
			</div>
			<div id="submitbutton" class="hidden">
				<A href="javascript:void(0)" onclick="$('#uset').submit();" class="button">Сохранить</A>
			</div>&nbsp;

		</DIV>

	</FORM>

	<div id="avatarform" class="hidden">

		<b class="blue">Загрузка аватара</b>
		<br>
		<form action="/content/ajax/user.settings.php" method="post" enctype="multipart/form-data" name="avaform" id="avaform">
			<input type="hidden" name="action" id="action" value="avatarupload">
			<input name="file" type="file" class="file" id="file" onchange="$('#avaform').submit()">
		</form>
		<br>

		<span class="smalltxt">Загрузка произойдет сразу</span>
		<div class="avarez"></div>
		<div class="togglerbox hand pull-right" data-id="avatarform">
			<i class="icon-cancel-circled2 blue"></i>
		</div>

	</div>

<?php } ?>

<script>

	var hh = $('#dialog_container').actual('height') * 0.8;
	var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight');

	if (!isMobile) {

		if ($(window).width() > 990) $('#dialog').css({'width': '850px'});
		else $('#dialog').css('width', '90vw');

		$('#formtabs').css({'max-height': hh2 + 'px'});

	} else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 60;

		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

	}

	$(function () {

		//console.log(openFrame);
		//console.log(viewAsOpen);

		if (openFrame)
			$('#openCardInFrame[value="1"]').prop('checked', true);
		else
			$('#openCardInFrame[value="0"]').prop('checked', true);
		if (viewAsOpen)
			$('#viewAsOpen').prop('checked', true);

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

		/*tooltips*/
		$('.tooltips').append("<span></span>");
		$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
		$(".tooltips").mouseenter(function () {
			$(this).find('span').empty().append($(this).attr('tooltip')).css("width", "200px");
		});
		/*tooltips*/

		<?php if($isCloud == true){ ?>
		checkuser('login');
		<?php } ?>
		checkuserpass('pwd2');

		$('#dialog').center();

	});

	$('#avaform').ajaxForm({
		dataType: 'json',
		beforeSubmit: function () {
			$('#avarez').show().append('<img src="/assets/images/loading.gif">');
		},
		success: function (data) {

			var rez = data.res;
			var err = data.error;

			if (err != '') {

				$('#avarez').html(err);

			}
			if (rez != '') {

				$('#avatarform').hide();
				$('#file').val('');
				$('.avatarbig').css('background-image', 'url(/cash/avatars/' + rez + ')');
				$('.avatar').css('background-image', 'url(/cash/avatars/' + rez + ')');

			}
		}
	});

	$('#uset').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = 0;

			var oFF = oF = $('#openCardInFrame:checked').val();
			localStorage.setItem("openCardInFrame", oFF);
			openFrame = (oF == 1);


			var vOO = vO = ($('#viewAsOpen').prop("checked")) ? '1' : '0';
			localStorage.setItem("viewAsOpen", vOO);
			viewAsOpen = (vO == 1);

			$('#dialog').find(".required").removeClass("empty").css({"color": "inherit", "background": "inherit"});
			$('#dialog').find(".required").each(function () {

				if ($(this).val() == '') {

					$(this).addClass("empty").css({"color": "#ffffff", "background": "#FF8080"});
					em = em + 1;

				}

			});

			$out.empty();

			if (em > 0) {

				alert("Не заполнены обязательные поля\n\rОни выделены цветом");
				return false;

			} else if (em === 0) {

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			}

		},
		success: function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 10000);

			if ($display == 'desktop')
				razdel();

			var theme = $('#userTheme').val();

			//применяем стили
			if (theme !== '') document.getElementById('theme').href = '/assets/css/themes/theme-' + theme + '.css';
			else document.getElementById('theme').href = '/assets/css/theme.css';

		}
	});

	function switchTheme() {
		var theme = $('#userTheme option:selected').val();

		//применяем стили
		if (theme !== '') document.getElementById('theme').href = '/assets/css/themes/theme-' + theme + '.css';
		else document.getElementById('theme').href = '/assets/css/theme.css';

	}

	function resetTabs() {

		var url = 'settings.php?action=reset';
		$.post(url, function (data) {
			$('#message').fadeTo(1, 1).css('display', 'block').append('<img src="/assets/images/loader.gif" height="10">Пожалуйста подождите...');
			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 10000);
			return true;
		});
	}

	function changeShowIntro() {
		document.cookie = 'intro=';
		if ($('#startinto').is('div')) $('#startinto').show();
	}

	$("#rowtable").tableDnD({
		onDragClass: "tableDrag",
		onDrop: function (table, row) {
		}
	});
	/*$(".table-2").tableDnD({
		onDragClass: "tableDrag",
		onDrop: function (table, row) {
		}
	});*/

	<?php if($isCloud == true){ ?>
	/*
	$('#login').mouseleave(function (e) {
		checkuser('login');
	});
	$('#login').focusout(function (e) {
		checkuser('login');
	});
	$('#login').keyup(function (e) {
		checkuser('login');
	});
	*/
	$('#login').off('mouseleave focusout keyup');
	$('#login').on('mouseleave focusout keyup', function () {

		checkuser('login');

	});
	<?php } ?>

	/*
	$('#pwd2').keyup(function (e) {
		checkuserpass('pwd2');
	});
	$('#pwd2').focusout(function (e) {
		checkuserpass('pwd2');
	});
	*/
	$('#pwd2').off('focusout keyup');
	$('#pwd2').on('focusout keyup', function () {

		checkuserpass('pwd2');

	});

</script>