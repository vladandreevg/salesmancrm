<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use PHPMailer\PHPMailer\PHPMailer;

error_reporting(E_ERROR);
ini_set('display_errors', 1);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

if ($_REQUEST['action'] == 'save') {

	$smtp_host   = $_REQUEST['smtp_host'];
	$smtp_port   = $_REQUEST['smtp_port'];
	$smtp_auth   = $_REQUEST['smtp_auth'];
	$smtp_secure = $_REQUEST['smtp_secure'];
	$smtp_user   = rij_crypt($_REQUEST['smtp_user'], $skey, $ivc);
	$smtp_pass   = rij_crypt($_REQUEST['smtp_pass'], $skey, $ivc);
	$smtp_from   = $_REQUEST['smtp_from'];
	$active      = $_REQUEST['active'];
	$charset     = $_REQUEST['charset'];

	if (!$charset) {
		$charset = 'utf-8';
	}

	try {

		$db -> query("update {$sqlname}smtp set active = '".$active."', smtp_host = '".$smtp_host."', smtp_port = '".$smtp_port."', smtp_auth = '".$smtp_auth."', smtp_secure = '".$smtp_secure."', smtp_user = '".$smtp_user."', smtp_pass = '".$smtp_pass."', smtp_from = '".$smtp_from."', name = '$charset' WHERE identity = '$identity' and tip = 'send'");

		print "Данные успешно сохранены";

		unlink($rootpath."/cash/".$fpath."settings.all.json");

	}
	catch (Exception $e) {

		print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	exit();
}

if ($_REQUEST['action'] == 'check') {

	$smtp_host   = $_REQUEST['smtp_host'];
	$smtp_port   = $_REQUEST['smtp_port'];
	$smtp_auth   = $_REQUEST['smtp_auth'];
	$smtp_secure = $_REQUEST['smtp_secure'];
	$smtp_user   = $_REQUEST['smtp_user'];
	$smtp_pass   = $_REQUEST['smtp_pass'];
	$smtp_from   = $_REQUEST['smtp_from'];
	$charset     = $_REQUEST['charset'];

	if (!$charset) {
		$charset = 'utf-8';
	}

	//получим данные сервера smtp для подключения
	$mail = new PHPMailer();

	$mail -> IsSMTP();
	$mail -> SMTPAuth    = $smtp_auth;
	$mail -> SMTPSecure  = $smtp_secure;
	$mail -> Host        = $smtp_host;
	$mail -> Port        = $smtp_port;
	$mail -> Username    = $smtp_user;
	$mail -> Password    = $smtp_pass;
	$mail -> SMTPDebug   = 2;
	$mail -> SMTPOptions = [
		'ssl' => [
			'verify_peer'       => false,
			'verify_peer_name'  => false,
			'allow_self_signed' => true
		]
	];

	$mail -> CharSet = $charset;
	$mail -> setLanguage('ru', $rootpath.'/vendor/phpmailer/phpmailer/language/');
	$mail -> IsHTML(false);
	$mail -> SetFrom($smtp_user, iconv("utf-8", $charset, "Тест CRM"));
	$mail -> AddAddress($smtp_from, current_user($iduser1));
	$mail -> Subject = iconv("utf-8", $charset, "Проверка отправки сообщений из CRM");
	$mail -> Body    = iconv("utf-8", $charset, "Это тест");

	if (!$mail -> Send()) {
		print $mailsender_rez = '<hr>Ошибка: <b class="red">'.$mail -> ErrorInfo.'</b>';
	}
	else {
		print $mailsender_rez = '<hr><b class="green">Проверочное письмо отправлено</b>';
	}

	//$error = $mail->ErrorInfo;

	//if(!$error) print

	exit();
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	print '<div class="bad" align="center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

$set         = $db -> getRow("select * from {$sqlname}smtp WHERE identity = '$identity' and tip = 'send'");
$active      = $set["active"];
$smtp_host   = $set["smtp_host"];
$smtp_port   = $set["smtp_port"];
$smtp_auth   = $set["smtp_auth"];
$smtp_secure = $set["smtp_secure"];
$smtp_user   = rij_decrypt($set["smtp_user"], $skey, $ivc);
$smtp_pass   = rij_decrypt($set["smtp_pass"], $skey, $ivc);
$smtp_from   = $set["smtp_from"];
$charset     = $set["name"];

$file = str_replace([
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents($rootpath.'/cash/smtp.json'));
$fc   = json_decode($file, true);
$dat  = json_encode_cyr($fc, true);
?>
<div class="infodiv">
	<p>Для отправки почтовых уведомлений из CRM требуется наличие почтового сервера или утилиты Sendmail, которая позволяет отправлять email через свой или внешний почтовый сервер.</p>
	<p>В этом разделе вы можете настроить подключение к своему почтовому ящику (например, на Яндекс.Почта) и отправка сообщений будет производится через него. Такой способ также повысит вероятность доставки сообщения и не попадания его в спам.</p>
</div>
<FORM action="/content/admin/<?php
echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="set" id="set">
	<INPUT type="hidden" name="action" id="action" value="save">

	<h2 class="blue mt20 mb20 pl5">Настройки подключения к SMTP-серверу:</h2>

	<div class="flex-container mt10 box--child">

		<div class="flex-string wp20 right-text fs-12 Bold red pt10">Рекомендуемые для:</div>
		<div class="flex-string wp80 pl10">
			<span class="select">
			<SELECT name="serv" id="serv" onchange="getServ()" class="w160">
				<option value="">-- выбор --</option>
				<?php
				foreach ($fc as $key => $value) {
					print '<option value="'.$key.'">'.$key.'</option>';
				}
				?>
			</SELECT>
			</span>
			<div class="fs-09 gray2 em">Укажите сервис и мы загрузим настройки, типичные для него</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 black Bold">Активен:</div>
		<div class="flex-string wp80 pl10">

			<div class="inline paddright15 margleft5 mb10">

				<div class="radio">
					<label>
						<input name="active" type="radio" id="active" <?php
						if ($active == 'yes') print "checked" ?> value="yes">
						<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
						<span class="title Bold">Да</span>
					</label>
				</div>

			</div>
			<div class="inline paddright15 margleft5 mb10">

				<div class="radio">
					<label>
						<input name="active" type="radio" id="active" <?php
						if ($active != 'yes') print "checked" ?> value="no">
						<span class="custom-radio alert"><i class="icon-radio-check"></i></span>
						<span class="title Bold">Нет</span>
					</label>
				</div>

			</div>
			<div class="fs-09 gray2 em">Если выключено, то система попытается использовать внутренний сервер, если он есть (Sendmail)</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Адрес (smtp host):</div>
		<div class="flex-string wp80 pl10">
			<input name="smtp_host" type="text" id="smtp_host" value="<?= $smtp_host ?>" class="w300">
			<div class="fs-09 gray2 em">Уточните у провайдера почтового сервиса или Установите рекомендуемые</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Порт (smtp port):</div>
		<div class="flex-string wp80 pl10">
			<input name="smtp_port" type="text" id="smtp_port" value="<?= $smtp_port ?>" class="w300">
			<div class="fs-09 gray2 em">Уточните у провайдера почтового сервиса или Установите рекомендуемые</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Авторизация:</div>
		<div class="flex-string wp80 pl10">
			<span class="select">
				<SELECT name="smtp_auth" id="smtp_auth" class="w160">
					<option value="true" <?php
					if ($smtp_auth == 'true') print "selected" ?>>Да</option>
					<option value="false" <?php
					if ($smtp_auth == 'false') print "selected" ?>>Нет</option>
				</SELECT>
			</span>
			<div class="fs-09 gray2 em">Уточните у провайдера почтового сервиса или Установите рекомендуемые</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Тип шифрования:</div>
		<div class="flex-string wp80 pl10">
			<span class="select">
				<SELECT name="smtp_secure" id="smtp_secure" class="w160">
					<option value="">Без шифрования</option>
					<option value="tls" <?php
					if ($smtp_secure == 'tls') print "selected" ?>>TLS</option>
					<option value="ssl" <?php
					if ($smtp_secure == 'ssl') print "selected" ?>>SSL</option>
				</SELECT>
			</span>
			<div class="fs-09 gray2 em">Уточните у провайдера почтового сервиса или Установите рекомендуемые</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Кодировка:</div>
		<div class="flex-string wp80 pl10">
			<span class="select">
				<SELECT name="charset" id="charset" class="w160">
					<option value="">UTF-8</option>
					<option value="windows-1251" <?php
					if ($charset == 'windows-1251') print "selected" ?>>WINDOWS-1251</option>
					<option value="koi8r" <?php
					if ($charset == 'koi8-r') print "selected" ?>>KOI8-R</option>
				</SELECT>
			</span>
			<div class="fs-09 gray2 em">Кодировка, в которой будут отправляться сообщения и уведомления.</div>
		</div>

	</div>

	<h2 class="blue mt20 mb20 pl5">Настройки пользователя:</h2>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Логин:</div>
		<div class="flex-string wp80 pl10">
			<input name="smtp_user" type="text" id="smtp_user" value="<?= $smtp_user ?>" class="w300">
			<div class="fs-09 gray2 em">Некоторые провайдеры используют адрес электронной почты</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Пароль:</div>
		<div class="flex-string wp80 pl10">
			<div class="w300 relativ">
				<input name="smtp_pass" type="password" id="smtp_pass" value="<?= $smtp_pass ?>" class="w300" data-type="password">
				<div class="showpass" id="showpass">
					<i class="icon-eye-off hand gray" title="Посмотреть пароль"></i>
				</div>
			</div>
			<div class="fs-09 gray2 em">Укажите верный пароль</div>
		</div>

	</div>

	<hr>

	<div class="flex-container mt20 mb20 box--child">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Email пользователя:</div>
		<div class="flex-string wp80 pl10">
			<input name="smtp_from" type="text" id="smtp_from" value="<?= $smtp_from ?>" class="w300">
			<div class="fs-09 gray2 em">Email д.б. зарегистрированным и знакомым сервису. В противном случае - сервер отклонит отправку почты.</div>
		</div>

	</div>

	<?php
	if (!extension_loaded("imap")) {
		print '<i class="icon-attention red"></i>&nbsp;Требуемый модуль <u><b>IMAP</b></u> <b class="red">не подключен</b>.<br>';
	}
	else {
		?>
		<div class="infodiv" style="padding-left: 30px;">
			<b>Проверка соединения с сервером:</b><br>
			<div id="res" class="hidden pad5"></div>
			<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
		</div>
	<?php
	}
	?>

	<hr>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
		<a href="javascript:void(0)" class="button" onclick="$('#set').trigger('submit')">Сохранить</a>
	</DIV>

	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/64')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

</FORM>
<script>

	$(function () {

		$('#set').ajaxForm({
			beforeSubmit: function () {
				var $out = $('#message');
				$out.empty();
				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;
			},
			success: function (data) {

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}
		});

	});

	function checkConnection() {

		var url = "/content/admin/<?php echo $thisfile; ?>";
		var str = $('#set').serialize() + '&action=check';

		$('#res').removeClass('hidden').append('<img src="/assets/images/loading.gif">');

		$.post(url, str, function (data) {
			if (data) {
				$('#res').html(data.replace('\n\r', '<br>'));
			}
			return false;
		});
	}

	function getServ() {

		var serv = $('#serv option:selected').val();
		var smtp = JSON.parse('<?=$file?>');

		$('#smtp_host').val(smtp[serv].host);
		$('#smtp_port').val(smtp[serv].port);
		$('#smtp_auth [value=' + smtp[serv].auth + ']').prop("selected", true);
		$('#smtp_secure [value=' + smtp[serv].secure + ']').prop("selected", true);
		$('#smtp_protocol [value=' + smtp[serv].protocol + ']').prop("selected", true);

	}
</script>