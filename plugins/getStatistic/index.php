<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

set_time_limit(0);

$rootpath = dirname( __DIR__, 2 );
$ypath = $rootpath."/plugins/getStatistic/";

error_reporting(E_ERROR);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/vendor/core.php";
require_once $ypath."/vendor/Manager.php";

$action = $_REQUEST['action'];

$identity = $GLOBALS['identity'];
$iduser1  = $GLOBALS['iduser1'];

$ypath = $rootpath."/plugins/getStatistic/";

$fpath = '';

if ( $isCloud ) {

	//создаем папки хранения файлов
	createDir("data/".$identity);
	$fpath = $identity.'/';

}

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
$serverhost = $scheme.$_SERVER["HTTP_HOST"];

$periodStart = str_replace("/", "-", $_REQUEST['periodStart']);
$periodEnd   = str_replace("/", "-", $_REQUEST['periodEnd']);

if (!$periodStart) {

	$period = getPeriod('month');

	[$periodStart, $periodEnd] = $period;

	//$periodStart = $period[0];
	//$periodEnd   = $period[1];

}

$access = $proxy = [];
$bots   = [
	"telegram"  => "Telegram",
	"slack"     => "Slack",
	"viber"     => "Viber",
	"facebook"  => "Facebook Messenger",
	"vk"        => "VK.com Chat",
	"microsoft" => "Skype Bot",
	"watsapp"   => "Whatsapp"
];

//загружаем настройки доступа
//$file = $ypath.'data/'.$fpath.'access.json';
$settings = json_decode(file_get_contents($ypath.'data/'.$fpath.'settings.json'), true);

if(!empty($settings['proxy']['url'])){

	$proxy = $settings['proxy'];
	$proxy["type"] = CURLPROXY_SOCKS5;

}

$access = $settings['preusers'];

//если настройки произведены, то загружаем их
if (empty($settings) && $action != 'settings.do') {
	$access = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY title" );
}

if ($action == 'check') {

	$tip     = $_REQUEST['tip'];
	$token   = $_REQUEST['token'];
	$hookurl = $_REQUEST['hookurl'];

	$result = [];

	switch ($tip) {

		case 'telegram':

			$telegram = new Telegram($token, true, $proxy);
			$result   = $telegram -> getMe();

		break;
		case 'slack':

			$res = json_decode(outSender("https://slack.com/api/auth.test", ["token" => $token]), true);


			$result['ok']                 = $res['ok'];
			$result['result']             = $res;
			$result['result']['id']       = $res['user_id'];
			$result['result']['username'] = $res['user'];

		break;
		case 'viber':

			$viber = new Viber($token);
			$viber -> BotInfo();

			$res = json_decode($viber -> answer, true);

			$result['ok'] = $res['status_message'] == 'ok';
			//$result['result'] = $res;
			$result['result']['id']       = $res['id'];
			$result['result']['username'] = $res['name'];

		break;

	}

	print json_encode_cyr($result);

	exit();

}
if ($action == 'checkwebhook') {

	$id = (int)$_REQUEST['id'];

	$result = [];
	$bot    = $db -> getRow("select * from {$sqlname}sendstatistic_bots WHERE id = '$id'");

	switch ($bot['tip']) {

		case 'telegram':

			//require "vendor/TelegramBotPHP-master/Telegram.php";

			$telegram = new Telegram($bot['token'], true, $proxy);
			$result   = $telegram -> endpoint('getWebhookInfo', []);

			$result['result']['message'] = ($result['ok']) ? "подключен Webhook" : "ошибка соединения";

		break;
		case 'slack':

			$res = json_decode(outSender("https://slack.com/api/auth.test", ["token" => $bot['token']]), true);


			$result['ok']                 = $res['ok'];
			$result['result']             = $res;
			$result['result']['message']  = "активен";
			$result['result']['id']       = $res['user_id'];
			$result['result']['username'] = $res['user'];

		break;
		case 'viber':

			$viber = new Viber($bot['token']);
			$viber -> BotInfo();

			$res = json_decode($viber -> answer, true);

			$result['ok']                 = $res['status_message'] == 'ok';
			$result['result']             = $res;
			$result['result']['message']  = "активен";
			$result['result']['url']      = $res['webhook'];
			$result['result']['id']       = $res['id'];
			$result['result']['username'] = $res['name'];

		break;

	}

	print json_encode_cyr($result);

	exit();

}

//настройка доступа
if ($action == 'settings.do') {

	$settings = $_REQUEST['settings'];
	$settings['preusers'] = $_REQUEST['preusers'];

	$params = json_encode_cyr($settings);

	$f = $ypath.'data/'.$fpath.'settings.json';
	$file = fopen($f, 'wb' );

	if (!$file) {
		$rez = 'Не могу открыть файл';
	}
	else {

		$rez = (fwrite($file, $params) === false) ? 'Ошибка записи' : 'Записано';
		fclose($file);

	}

	print $rez;

	exit();

}
if ($action == "settings") {

	?>
	<DIV class="zagolovok"><B>Настройка</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="settings.do">

		<div class="divider mb20 mt20">Настройки доступа</div>

		<div class="row" style="overflow-y: auto; max-height: 350px">
			<?php
			$da = $db -> getAll("SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title");
			foreach ($da as $data) {
				?>
				<label style="display: inline-block; width: 50%; box-sizing: border-box; float: left; padding-left: 20px">
					<div class="column grid-1">
						<input name="preusers[]" type="checkbox" id="preusers[]" value="<?= $data['iduser'] ?>" <?php if (in_array($data['iduser'], $access)) print 'checked'; ?>>
					</div>
					<div class="column grid-9">
						<?= $data['title'] ?>
					</div>
				</label>
				<?php
			}
			?>
		</div>

		<div class="divider mb20 mt20">Настройки прокси SOCKS5 (для Телеграмм)</div>

		<div class="row">

			<div class="column grid-7 relative">

				<span class="label">URL-адрес:</span>
				<input type="text" name="settings[proxy][url]" id="settings[proxy][url]" class="wp100" value="<?= $proxy['url'] ?>">

			</div>

			<div class="column grid-3 relative">

				<span class="label">Port:</span>
				<input type="text" name="settings[proxy][port]" id="settings[proxy][port]" class="wp100" value="<?= $proxy['port'] ?>">

			</div>

			<div class="column grid-10 relative">

				<span class="label">Авторизация:</span>
				<input type="text" name="settings[proxy][auth]" id="settings[proxy][auth]" class="wp100" value="<?= $proxy['auth'] ?>">
				<div class="fs-09 blue text-center">В формате "User:Password"</div>

			</div>

		</div>

		<hr>

		<div class="text-right">
			<A href="javascript:void(0)" onclick="saveAccess()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="new DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		$('#dialog').css('width', '700px');

		function saveAccess() {

			var str = $('#Form').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("index.php", str, function (data) {

				yNotifyMe("CRM. Результат," + data + ",signal.png");

				DClose();

			});
		}
	</script>
	<?php

	exit();

}

//настройки бота
if ($action == "bot.save") {

	$id = (int)$_REQUEST['id'];

	$data['tip']     = $_REQUEST['tip'];
	$data['name']    = trim($_REQUEST['name']);
	$data['content'] = $_REQUEST['content'];
	$data['botid']   = trim($_REQUEST['botid']);
	$data['token']   = trim($_REQUEST['token']);
	$data['datum']   = current_datumtime();

	$bot   = new Manager();
	$mes[] = $bot -> BotSave($id, $data);

	/**
	 * Регистрируем Webhook
	 */

	$api_key = $db -> getOne("select api_key from {$sqlname}settings WHERE id = '$identity'");

	$baseURL = $serverhost.'/plugins/getStatistic/webhook';

	switch ($data['tip']) {

		case 'telegram':

			// регистрируем хуку в Телеграм
			$urlk = $serverhost.'/plugins/getStatistic/webhook/telegram.php?botname='.$data['name'].'&api_key='.$api_key;

			$telegram = new Telegram($data['token'], true, $proxy);
			$res   = $telegram -> setWebhook($urlk);

			//$res = json_decode($result, true);

			$mes[] = ($res['ok'] == 1) ? "Вебхук установлен" : $res['description'];

		break;
		case 'viber':

			$urlk = $baseURL.'/viber.php';

			/**
			 * адрес для веб-хук сгенерирован под Mod Rewrite
			 */
			$urlk = $baseURL."/viber/$api_key/".$data['botid']."/";

			$viber = new Viber($data['token']);
			$viber -> setWebhook($urlk);

			$res = json_decode($viber -> answer, true);

			$mes[] = ($res['status_message'] == 'ok') ? "Вебхук установлен" : $res['status_message'];

		break;

	}

	print yimplode("\n", $mes);

	exit();

}
if ($action == "bot.delete") {

	$id = $_REQUEST['id'];

	$botinfo = Manager ::BotInfo( $id );

	$bot = new Manager();
	$mes = $bot -> BotDelete($id);

	switch ( $botinfo['tip'] ) {

		case 'telegram':

			$telegram = new Telegram( $data[ 'token' ], true, $proxy );
			$result   = $telegram -> deleteWebhook();

			$res = json_decode( $result, true );

			$mes[] = ( $res[ 'ok' ] == 1 ) ? "Вебхук удален" : $res[ 'description' ];

		break;
		case 'viber':

			$viber = new Viber( $data[ 'token' ] );
			$viber -> deleteWebhook();

			$res = json_decode( $viber -> answer, true );

			$mes[] = ( $res[ 'status_message' ] === 'ok' ) ? "Вебхук удален" : $res[ 'status_message' ];

		break;

	}

	print yimplode("\n", $mes);

	exit();

}
if ($action == "bot.get") {

	$id  = (int)$_REQUEST['id'];
	$bot = [];

	if ($id > 0) {
		$bot = Manager ::BotInfo( $id );
	}

	print json_encode_cyr($bot);

	exit();

}
if ($action == "bot.info") {

	$id  = (int)$_REQUEST['id'];
	$bot = [];

	if ($id > 0) {

		$bot = $db -> getRow("select * from {$sqlname}sendstatistic_bots WHERE id = '$id'");

	}

	?>
	<DIV class="zagolovok"><B>Информация</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="bot.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="rezult pad10 row fs-12"></div>

	</form>

	<script>

		$('#dialog').css('width', '500px');
		checkConnection();

		function checkConnection() {

			$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

			var str = 'action=checkwebhook&id=' + $('#id').val();

			$.post("index.php", str, function (data) {

				var string = '';

				if (data.ok === true) {

					string += '<div class="column grid-10"><b class="gray2 fs-09">Ответ</b></div>';
					string += '<div class="column grid-10 infodiv fs-09">' + data.result.message + '</div>';
					string += '<div class="column grid-10 mt15"><b class="gray2 fs-09">Адрес</b></div>';
					string += '<div class="column grid-10 infodiv fs-09">' + data.result.url + '</div>';

					if (data.result.has_custom_certificate) {

						string += '<hr>';
						string += '<div class="column grid-10"><b class="gray2">Самоподписанный:</b> ' + data.result.has_custom_certificate + '</div>';
						string += '<div class="column grid-10"><b class="gray2">Max подключений:</b> ' + data.result.max_connections + '</div>';

					}

				}
				if (data.error_code === 404) {

					string += '<div class="column grid-10"><b class="gray2">Ответ:</b> ' + data.description + '</div>';

				}

				$('.rezult').html(string);

				$('#dialog').center();

			}, 'json');

		}

	</script>
	<?php

	exit();

}
if ($action == "bot.form") {

	$id  = (int)$_REQUEST['id'];
	$bot = [];

	if ($id > 0) {

		$bot = $db -> getRow("select * from {$sqlname}sendstatistic_bots WHERE id = '$id'");

	}

	$botExists = $db -> getRow("select tip from {$sqlname}sendstatistic_bots WHERE identity = '$identity'");

	?>
	<DIV class="zagolovok"><B>Редактировать бота</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="bot.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="row">

			<div class="column grid-10 relative">
				<span class="label">Бот для:</span>
				<span class="select">
				<select id="tip" name="tip" class="wp100 required">
					<option value="">--Укажите тип бота--</option>
					<?php
					foreach ($bots as $tip => $name) {

						$s = ($tip == $bot['tip']) ? "selected" : "";
						$t = (!in_array($tip, array(
							"telegram",
							//"slack",
							"viber"
						))) ? "disabled" : "";

						//if($id < 1) $s = (in_array($tip, $botExists)) ? "disabled" : "";

						print '<option value="'.$tip.'" '.$s.' '.$t.'>'.$name.'</option>';

					}
					?>
				</select>
				</span>
			</div>

			<div class="column grid-10 relative">
				<span class="label">Secret Key:</span>
				<input type="text" name="token" id="token" class="wp100" value="<?= $bot['token'] ?>"/>
			</div>

			<div class="column grid-10 relative">
				<span class="label">ID бота:</span>
				<input type="text" name="botid" id="botid" class="wp100" value="<?= $bot['botid'] ?>"/>
			</div>

			<div class="column grid-10 relative">
				<span class="label">Имя бота:</span>
				<input type="text" name="name" id="name" class="wp100" value="<?= $bot['name'] ?>"/>
			</div>

			<!--
			<div class="column grid-10 relative">
				<span class="label mt5">Сертификат:</span>
				<input type="file" name="sertificate" id="sertificate" class="wp100">
			</div>

			<div class="gray2 em fs-09 pl10">В случае самоподписанного сертификата</div>
			-->

		</div>

		<hr>

		<div class="div-center">
			<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button">Проверить</a>
		</div>

		<div class="rezult pad10 div-center"></div>

		<hr>

		<div align="right">
			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="new DClose()" class="button">Отмена</A>
		</div>
	</form>

	<script>

		$('#dialog').css('width', '500px');

		function checkConnection() {

			$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

			var str = 'action=check&tip=' + $('#tip').val() + '&token=' + $('#token').val();

			$.post("index.php", str, function (data) {

				if (data.ok === true) {

					$('.rezult').html('Ответ: <b>Соединение установлено</b>');
					$('#name').val(data.result.username);
					$('#botid').val(data.result.id);

				} else $('.rezult').html('Ошибка: <b>' + data.error_code + data.description + '</b>');

			}, 'json');

		}

	</script>
	<?php

	exit();

}

//настройки пользователя
if ($action == "user.save") {

	$id = (int)$_REQUEST['id'];

	$data['botid']    = $_REQUEST['botid'];
	$data['iduser']   = trim($_REQUEST['iduser']);
	$data['userid']   = trim($_REQUEST['userid']);
	$data['username'] = str_replace("@", "", trim($_REQUEST['username']));
	$data['datum']    = current_datumtime();

	$usr = new Manager();
	$mes = $usr -> UserSave($id, $data);

	print $mes;

	exit();

}
if ($action == "user.delete") {

	$id = (int)$_REQUEST['id'];

	$usr = new Manager();
	$mes = $usr -> UserDelete($id);

	print $mes;

	exit();

}
if ($action == "user.activate") {

	$id = (int)$_REQUEST['id'];

	$usr = new Manager();
	$mes = $usr -> UserActiveChange($id);

	print $mes;

	exit();

}
if ($action == "user.get") {

	$id   = (int)$_REQUEST['id'];
	$user = [];

	if ($id > 0) {

		$user = $db -> getRow("select * from {$sqlname}sendstatistic_users WHERE id = '$id'");

	}

	print json_encode_cyr($user);

	exit();

}
if ($action == "user.form") {

	$id   = (int)$_REQUEST['id'];
	$user = [];

	if ($id > 0) {

		$user = $db -> getRow("select * from {$sqlname}sendstatistic_users WHERE id = '$id'");

	}

	?>
	<DIV class="zagolovok"><B>Редактировать пользователя</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="user.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="row">

			<div class="column grid-10 relative">
				<span class="label">Бот:</span>
				<span class="select">
				<select id="botid" name="botid" class="wp100 required">
					<option value="">--Укажите бота--</option>
					<?php
					$da = $db -> getAll("SELECT id, tip, name FROM {$sqlname}sendstatistic_bots WHERE identity = '$identity'");
					foreach ($da as $bot) {

						$s = ($bot['id'] == $user['botid']) ? "selected" : "";

						print '<option value="'.$bot['id'].'" '.$s.' '.($bot['tip'] == 'viber' ? 'disabled' : '').'>'.$bot['name'].' ['.strtr($bot['tip'], $bots).']</option>';

					}
					?>
				</select>
				</span>
			</div>

			<div class="column grid-10 relative">
				<span class="label">Сотрудник:</span>
				<span class="select">
				<select id="iduser" name="iduser" class="wp100 required">
					<option value="">--Укажите сотрудника--</option>
					<?php
					$da = $db -> getAll("SELECT iduser, title FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title");
					foreach ($da as $us) {

						$s = ($us['iduser'] == $user['iduser']) ? "selected" : "";

						print '<option value="'.$us['iduser'].'" '.$s.'>'.$us['title'].'</option>';

					}
					?>
				</select>
				</span>
			</div>

			<div class="column grid-10 relative">
				<span class="label">Username:</span>
				<input type="text" name="username" id="username" class="wp100" value="<?= $user['username'] ?>"/>
			</div>

			<div class="column grid-10 relative">
				<span class="label">UserID:</span>
				<input type="text" name="userid" id="userid" class="wp100" value="<?= $user['userid'] ?>"/>
			</div>

		</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="new DClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		$('#dialog').css('width', '500px');

	</script>
	<?php

	exit();
}

if ($action == 'loadbots') {

	$bots = [];

	$data = $db -> getAll("SELECT * FROM {$sqlname}sendstatistic_bots WHERE identity = '$identity'");
	foreach ($data as $da) {

		$bots[] = [
			"id"      => (int)$da['id'],
			"botid"   => $da['botid'],
			"date"    => $da['datum'],
			"tip"     => strtr($da['tip'], $bots),
			"name"    => $da['name'],
			"content" => $da['content'],
		];

	}

	print json_encode_cyr($bots);

	exit();
}
if ($action == 'loadusers') {

	$users = [];

	$data = $db -> getAll("SELECT * FROM {$sqlname}sendstatistic_users WHERE identity = '$identity'");
	foreach ($data as $da) {

		$bot = $db -> getRow("select * from {$sqlname}sendstatistic_bots WHERE id = '$da[botid]'");

		$isunlock = $db -> getOne("SELECT secrty FROM {$sqlname}user WHERE iduser = '$da[iduser]' AND identity = '$identity'");

		$users[] = [
			"id"       => (int)$da['id'],
			"date"     => $da['datum'],
			"botid"    => $bot['name'],
			"bottip"   => strtr($bot['tip'], $bots),
			"userid"   => $da['userid'],
			"chatid"   => $da['chatid'],
			"username" => $da['username'],
			"user"     => current_user($da['iduser']),
			"content"  => $da['content'],
			"active"   => $da['active'] == 1,
			"isunlock" => $isunlock == "yes",
		];

	}

	print json_encode_cyr($users);

	exit();
}

if (!in_array($iduser1, $access) && $isadmin != 'on') {

	print '
	<TITLE>Предупреждение - CRM</TITLE>
	<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
	<LINK rel="stylesheet" href="/assets/css/fontello.css">
	<div class="warning text-eft" style="width:600px; margin:0 auto;">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Предупреждение:</b>
		<br><br>
		У вас нет доступа<br><br><br>
	</div>
	';

	exit();

}
?>
<!DOCTYPE html>
<html lang="ru-RU">
<head>

	<meta charset="utf-8">

	<title>getStatistic - Статистика работы</title>

	<link rel="stylesheet" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/app.card.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<link rel="stylesheet" href="./plugins/tablesorter/theme.default.css">
	<link rel="stylesheet" href="./plugins/daterangepicker/daterangepicker.css">
	<link rel="stylesheet" href="./plugins/periodpicker/jquery.periodpicker.min.css">
	<link rel="stylesheet" href="./plugins/autocomplete/jquery.autocomplete.css">
	<link rel="stylesheet" href="css/app.css">

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="fixx">
	<DIV id="head">
		<DIV id="ctitle">
			<b>SendStatistic: Предоставление статистики в мессенджеры</b>
			<DIV id="close" onclick="window.close();">Закрыть</DIV>
		</DIV>
	</DIV>
	<DIV id="dtabs">
		<UL>
			<LI class="ytab" id="tb3" data-id="3"><A href="#3">Боты, Пользователи</A></LI>
			<LI class="ytab hidden"><A href="javascript:void(0)" onclick="setSettings()">Настройка</A></LI>
			<LI class="ytab" id="tb1" data-id="1" style="float:right"><A href="#1" onclick="checkWebhook()">Справка</A>
			</LI>
			<LI class="ytab" data-id="100"><A href="javascript:void(0)" onclick="setSettings()">Настройка</A></LI>
		</UL>
	</DIV>
</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<?php
	if ( !is_writable( 'data' ) ) {
		print '
		<div class="warning margbot10">
			<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения настроек доступа "<b>data</b>".</p>
		</div>';
	}
	?>

	<div id="tab-1" class="tabbody hidden">

		<fieldset class="pad10" style="overflow: auto; height: 450px">

			<legend>Справка по плагину</legend>

			<div class="margbot10">

				<pre id="copyright">
##################################################
#                                                #
#  Плагин разработан для SalesMan CRM v.2018.x   #
#                                                #
#  Разработчик: Владислав Андреев                #
#  Контакты:                                     #
#     - Сайт:  http://isaler.ru                  #
#     - Email: vladislav@isaler.ru               #
#     - Скайп: andreev.v.g                       #
#                                                #
##################################################
				</pre>

				<hr>

				<div class="mb20 text fs-12 pl20">

					<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">
						<?php
						//include_once "../../opensource/parsedown-master/Parsedown.php";

						$api_key = $db -> getOne("select api_key from {$sqlname}settings WHERE id = '$identity'");
						$url     = $productInfo['crmurl'].'/plugins/sendStatistic/webhook/telegram.php?botname=BOTNAME&api_key='.$api_key;
						$url2    = $productInfo['crmurl'].'/plugins/sendStatistic/webhook/'.$api_key.'/BOTNAME';

						$html = str_replace( [
							"{{telegramHookUrl}}",
							"{{viberHookUrl}}"
						], [
							$url,
							$url2
						], file_get_contents( "readme.md" ) );

						$Parsedown = new Parsedown();
						print $help = $Parsedown -> text($html);
						?>
					</div>

				</div>

			</div>

		</fieldset>

	</div>

	<div id="tab-3" class="tabbody hidden">

		<fieldset class="pad10 notoverflow">

			<legend>Боты и шлюзы</legend>

			<div class="viewdiv">
				<span id="orangebutton">
					<a href="javascript:void(0)" onclick="doLoad('?action=bot.form')" class="marg0 button"><i class="icon-plus-circled"></i>Добавить бота</a>&nbsp;
				</span>
			</div>

			<div class="margbot10">

				<div class="wrapper3">

					<table class="bborder bgwhite" id="botTable">
						<thead>
						<tr>
							<th class="w20">№</th>
							<th class="w200">ID</th>
							<th class="w200">Имя бота</th>
							<th class="w200">Тип бота</th>
							<th class="w120">Дата обновления</th>
							<th class="w180 {sorter: 'false'} text-center">Действие</th>
						</tr>
						</thead>
						<tbody></tbody>

					</table>

				</div>

			</div>

		</fieldset>

		<fieldset class="pad10 notoverflow">

			<legend>Пользователи</legend>

			<div class="viewdiv">
				<span id="greenbutton">
					<a href="javascript:void(0)" onclick="doLoad('?action=user.form')" class="marg0 button"><i class="icon-plus-circled"></i>Добавить пользователя</a>&nbsp;
				</span>
			</div>

			<div class="margbot10">

				<div class="wrapper3">

					<table class="bborder bgwhite" id="userTable">
						<thead>
						<tr>
							<th class="w20">№</th>
							<th class="w200">Имя/тип бота</th>
							<th>ID пользователя</th>
							<th>ID чата</th>
							<th class="w200">Имя пользователя</th>
							<th class="w200">Сотрудник</th>
							<th class="w150">Дата обновления</th>
							<th class="w180 {sorter: 'false'}">Действие</th>
						</tr>
						</thead>
						<tbody></tbody>

					</table>

				</div>

			</div>

		</fieldset>

	</div>

</DIV>

<hr>

<div class="gray center-text">Сделано для SalesMan CRM</div>

<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js?v=2019.1"></script>
<script type="text/javascript" src="/assets/js/moment.js/moment.min.js"></script>

<script src="js/app.js"></script>

<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>

<script src="plugins/tablesorter/jquery.tablesorter.js"></script>
<script src="plugins/tablesorter/jquery.tablesorter.widgets.js"></script>
<script src="plugins/tablesorter/widgets/widget-cssStickyHeaders.min.js"></script>

<script src="plugins/daterangepicker/jquery.daterangepicker.js"></script>
<script src="plugins/periodpicker/jquery.periodpicker.full.min.js"></script>

</body>
</html>