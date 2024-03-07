<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\UIDs;
use Salesman\User;

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

$action = $_REQUEST['action'];

$template = '
<div style="width:98%; max-width:600px; margin: 0 auto">

	<div align="left" class="blok head" style="height:50px; margin-top:10px; border:0px solid #DFDFDF; padding:5px; margin-bottom: 10px;">
		<div class="logo" style="float: left;"><a href="'.$productInfo['site'].'"><img border="0" height="20" src="'.$productInfo['site'].'/docs.img/logo.png" style="margin-right:20px" /></a></div>
	</div>

	<div class="blok" style="font-size: 14px; color: #000; border:1px solid #DFDFDF; line-height: 18px; padding: 10px 10px; margin-bottom: 10px;">
	
		<div style="color:black; font-size:14px; margin-top: 5px;">
			<strong>Уважаемый {castomerName}!</strong><br />
			<br />
			Благодарим Вас за обращение в нашу компанию. Ваша заявка принята в работу нашим сотрудником <strong>{UserName}</strong>.<br />
			<br />
			В ближайшее время мы с вами свяжемся по указанному телефону или email.<br />
			<br />
			Контакты сотрудника:
			
			<ul>
				<li style="color: black; font-size: 12px; margin-top: 5px;">Телефон:<strong> {UserPhone}</strong></li>
				<li style="color: black; font-size: 12px; margin-top: 5px;">Мобильный:<strong> {UserMob}</strong></li>
				<li style="color: black; font-size: 12px; margin-top: 5px;">Почта: <strong>{UserEmail}</strong></li>
			</ul>
			&nbsp;
			
			<hr /><br />
			С уважением, {compShotName}
		</div>
		
	</div>

	<div align="right" style="font-size:10px; margin-top:10px; padding: 10px 10px; margin-bottom: 10px;">
		Обработано в SalesMan CRM
	</div>
</div>
';

$base = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER["HTTP_HOST"];

$api_key = $db -> getOne("select api_key from ".$sqlname."settings WHERE id = '$identity'");

if ($_REQUEST['act'] == 'check') {

	$smtp_protocol = $_REQUEST['smtp_protocol'];
	$smtp_host     = $_REQUEST['smtp_host'];
	$smtp_port     = $_REQUEST['smtp_port'];
	$smtp_auth     = $_REQUEST['smtp_auth'];
	$smtp_secure   = $_REQUEST['smtp_secure'];
	$smtp_user     = $_REQUEST['smtp_user'];
	$smtp_pass     = $_REQUEST['smtp_pass'];

	if ($smtp_secure != '') {
		$smtp_secure = '/'.$smtp_secure.'/novalidate-cert';
	}

	$mailbox = '{'.$smtp_host.':'.$smtp_port.'/'.$smtp_protocol.$smtp_secure.'}INBOX';
	$conn    = imap_open($mailbox, $smtp_user, $smtp_pass);
	$error   = imap_last_error();

	if ($error) {
		print 'Ошибка соединения: <b class="red">'.$error.'</b>';
	}
	else {
		imap_close($conn);
		print '<b class="green">Соединение установлено</b>. Параметры корректны.';
	}

	exit();

}

if ($action == "listdelete") {

	$id = (int)$_REQUEST['id'];

	$db -> query("delete from ".$sqlname."smtp WHERE id = '$id' and identity = '$identity'");

	print "Удалено";

	exit();

}
if ($action == 'listedit_do') {

	$id            = (int)$_REQUEST['id'];
	$lead['name']          = $_REQUEST['name'];
	$lead['active']        = $_REQUEST['active'];
	$lead['deletemess']    = $_REQUEST['deletemess'];
	$lead['divider']       = $_REQUEST['dividerr'];
	$lead['smtp_protocol'] = $_REQUEST['smtp_protocol'];
	$lead['smtp_host']     = $_REQUEST['smtp_host'];
	$lead['smtp_port']     = $_REQUEST['smtp_port'];
	$lead['smtp_auth']     = $_REQUEST['smtp_auth'];
	$lead['smtp_secure']   = $_REQUEST['smtp_secure'];
	$lead['smtp_user']     = rij_crypt($_REQUEST['smtp_user'], $skey, $ivc);
	$lead['smtp_pass']     = rij_crypt($_REQUEST['smtp_pass'], $skey, $ivc);
	$lead['smtp_from']     = $_REQUEST['smtp_from'];
	$lead['filter']        = untag(texttosmall($_REQUEST['filter']));

	if ($id > 0) {

		$db -> query("UPDATE ".$sqlname."smtp SET ?u WHERE id = '$id' and identity = '$identity'", $lead);

	}
	else {

		$lead['tip'] = 'lead';
		$lead['identity'] = $identity;

		$db -> query("INSERT INTO ".$sqlname."smtp SET ?u", $lead);

	}
	print "Данные успешно сохранены";

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if ($action == "settings_do") {

	$params['leadСoordinator']           = $_REQUEST['leadСoordinator'];
	$params['leadMethod']                = $_REQUEST['leadMethod'];
	$params['leadOperator']              = $_REQUEST['leadOperator'];
	$params['leadSendCoordinatorNotify'] = $_REQUEST['leadSendCoordinatorNotify'];
	$params['leadSendOperatorNotify']    = $_REQUEST['leadSendOperatorNotify'];
	$params['leadSendClientNotify']      = $_REQUEST['leadSendClientNotify'];
	$params['leadSendClientWellcome']    = $_REQUEST['leadSendClientWellcome'];
	$params['leadCanDelete']             = $_REQUEST['leadCanDelete'];
	$params['leadCanView']               = $_REQUEST['leadCanView'];
	$params['leadHideContact']           = $_REQUEST['leadHideContact'];
	$apikey                              = $_REQUEST['leadIdentity'];

	//$params['leadClientNotifyTemp']      = str_replace("{","%%", str_replace("}","%%%", htmlspecialchars($_REQUEST['leadClientNotifyTemp'])));

	$newsettings = json_encode_cyr($params);

	$db -> query("update ".$sqlname."modules set content = '".$newsettings."', secret = '$apikey' WHERE mpath = 'leads' and identity = '$identity'");

	//todo: добавим шаблон в таблицу tpl
	$tpl  = htmlspecialchars($_REQUEST['leadClientNotifyTemp']);
	$tpl2 = htmlspecialchars($_REQUEST['leadSendWellcomeTemp']);

	$tplid = $db -> getOne("SELECT tid FROM ".$sqlname."tpl WHERE tip = 'leadClientNotifyTemp' and identity = '$identity'") + 0;

	if ($tplid == 0) $db -> query("INSERT INTO ".$sqlname."tpl (tid, tip, name, content, identity) values (null, 'leadClientNotifyTemp', 'Уведомление', '$tpl', '$identity')");
	else $db -> query("UPDATE ".$sqlname."tpl SET content = '$tpl' WHERE tip = 'leadClientNotifyTemp' and identity = '$identity'");


	$tplid2 = (int)$db -> getOne("SELECT tid FROM ".$sqlname."tpl WHERE tip = 'leadSendWellcomeTemp' and identity = '$identity'") + 0;

	if ($tplid2 == 0) {
		$db -> query( "INSERT INTO ".$sqlname."tpl (tid, tip, name, content, identity) values (null, 'leadSendWellcomeTemp', 'Уведомление', '$tpl2', '$identity')" );
	}
	else {
		$db -> query( "UPDATE ".$sqlname."tpl SET content = '$tpl2' WHERE tip = 'leadSendWellcomeTemp' and identity = '$identity'" );
	}


	unlink($rootpath."/cash/".$fpath."settings.all.json");

	print "Сделано";

	exit();
}

if ($action == 'getApiKey') {

	function genkey(): ?string {

		$keys = $GLOBALS['keys'];

		$chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		$max   = 30;
		$size  = StrLen($chars) - 1;
		$key   = null;

		while ($max--) $key .= $chars[ rand(0, $size) ];

		return $key;

	}

	print $key = genkey();
	exit();

}
if ($action == 'getserv') {

	$serv = $_REQUEST['serv'];

	$file = file_get_contents($rootpath.'/cash/imap.json');
	$fc   = json_decode($file, true);

	foreach ($fc as $key => $value) {
		if ($key == $serv) {
			$dc = json_encode_cyr( $value, true );
		}
	}

	print $dc;

	exit();
}

if ($action == 'uids.add'){

	$name = str_replace(" ", "_", $_REQUEST['name']);

	$r = UIDs::uidAdd($name);

	print "Выполнено";

}
if ($action == 'uids.delete'){

	$name = $_REQUEST['name'];

	$r = UIDs::uidDelete($name);

	print "Выполнено";

}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="warning text-center">Доступ запрещен.<br>Обратитесь к администратору.</div>';
	exit();

}

if ($action == 'listedit') {

	$id = (int)$_REQUEST['id'];

	if ($id > 0) {

		$result        = $db -> getRow("SELECT * FROM ".$sqlname."smtp WHERE id = '$id' and identity = '$identity'");
		$active        = $result["active"];
		$deletemess    = $result["deletemess"];
		$divider       = $result["divider"];
		$smtp_host     = $result["smtp_host"];
		$smtp_port     = $result["smtp_port"];
		$smtp_auth     = $result["smtp_auth"];
		$smtp_secure   = $result["smtp_secure"];
		$smtp_user     = rij_decrypt($result["smtp_user"], $skey, $ivc);
		$smtp_pass     = rij_decrypt($result["smtp_pass"], $skey, $ivc);
		$smtp_from     = $result["smtp_from"];
		$smtp_protocol = $result["smtp_protocol"];
		$name          = $result["name"];
		$filter        = $result["filter"];

	}
	else {

		$active        = 'yes';
		$smtp_host     = 'imap.yandex.ru';
		$smtp_port     = '993';
		$smtp_auth     = 'true';
		$smtp_secure   = 'ssl';
		$smtp_user     = '';
		$smtp_pass     = '';
		$smtp_from     = '';
		$smtp_protocol = 'imap';
		$deletemess    = 'true';
		$divider       = ':';
		$name          = 'Landing Page';
		$filter        = 'заявка';

	}

	$file = file_get_contents($rootpath.'/cash/imap.json');

	$fc  = json_decode($file, true);
	$dat = json_encode_cyr($fc, true);

	?>
	<FORM action="/modules/leads/settings.leads.php" method="post" enctype="multipart/form-data" name="sett" id="sett">
		<INPUT type="hidden" name="action" id="action" value="listedit_do">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div class="zagolovok">Добавить / Редактировать почтовый ящик для сбора лидов</div>

		<div style="min-height: 320px; max-height: 70vh; overflow-y: auto;" class="pb10">

			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Статус:</div>
				<div class="column12 grid-9">
					<SELECT name="active" id="active">
						<option value="yes" <?php if ($active == 'yes') print "selected" ?>>Вкл.</option>
						<option value="no" <?php if ($active == 'no') print "selected" ?>>Откл.</option>
					</SELECT>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Почтовый сервис:</div>
				<div class="column12 grid-9">
					<SELECT name="serv" id="serv" onchange="getServ()">
						<?php
						foreach ($fc as $key => $value) {
							print '<option value="'.$key.'">'.$key.'</option>';
						}
						?>
					</SELECT>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Название:</div>
				<div class="column12 grid-9">
					<input name="name" type="text" id="name" value="<?= $name ?>" class="wp97"/>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Логин:</div>
				<div class="column12 grid-9">
					<input name="smtp_user" type="text" id="smtp_user" value="<?= $smtp_user ?>" class="wp97"/>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Пароль:</div>
				<div class="column12 grid-9 relativ">
					<input name="smtp_pass" type="password" id="smtp_pass" value="<?= $smtp_pass ?>" class="wp97" data-type="password">
					<div class="showpass mr10 mt5" id="showpass">
						<i class="icon-eye-off hand gray" title="Посмотреть пароль"></i>
					</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Email:</div>
				<div class="column12 grid-9">
					<input name="smtp_from" type="text" id="smtp_from" value="<?= $smtp_from ?>" class="wp97"/>
					<div class="em gray2 fs-09">( Email д.б. зарегистрированным и знакомым сервису. В противном случае - сервер отклонит получение почты. )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Адрес сервера:</div>
				<div class="column12 grid-9">
					<input name="smtp_host" type="text" id="smtp_host" value="<?= $smtp_host ?>" class="wp97"/>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Порт:</div>
				<div class="column12 grid-9">
					<input name="smtp_port" type="text" id="smtp_port" value="<?= $smtp_port ?>" class="wp30"/>
					<div class="em gray2 fs-09">( Уточните у провайдера. Порт может быть 25, 465 или 587 )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Тип:</div>
				<div class="column12 grid-9">
					<SELECT name="smtp_protocol" id="smtp_protocol" class="yw100">
						<option value="pop3" <?php if ($smtp_protocol == 'pop3') print "selected" ?>>POP3</option>
						<option value="imap" <?php if ($smtp_protocol == 'imap') print "selected" ?>>IMAP</option>
					</SELECT>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Авторизация:</div>
				<div class="column12 grid-9">
					<SELECT name="smtp_auth" id="smtp_auth" class="yw100">
						<option value="true" <?php if ($smtp_auth == 'true') print "selected" ?>>Да</option>
						<option value="false" <?php if ($smtp_auth == 'false') print "selected" ?>>Нет</option>
					</SELECT>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Шифрование:</div>
				<div class="column12 grid-9">
					<SELECT name="smtp_secure" id="smtp_secure" class="yw100">
						<option value="">Без шифрования</option>
						<option value="tls" <?php if ($smtp_secure == 'tls') print "selected" ?>>TLS</option>
						<option value="ssl" <?php if ($smtp_secure == 'ssl') print "selected" ?>>SSL</option>
						<option value="starttls" <?php if ($smtp_secure == 'starttls') print "selected" ?>>STARTTLS</option>
					</SELECT>
					<div class="em gray2 fs-09">( Например, для Яндекс - <b>tls</b>, для GMail - <b>ssl</b> )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Удалять письма:</div>
				<div class="column12 grid-9">
					<SELECT name="deletemess" id="deletemess" class="yw100">
						<option value="true" <?php if ($deletemess == 'true') print "selected" ?>>Да</option>
						<option value="false" <?php if ($deletemess == 'false') print "selected" ?>>Нет</option>
					</SELECT>
					<div class="em gray2 fs-09">( Удалять сообщения с сервера? Поможет держать ящик в порядке )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Разделитель:</div>
				<div class="column12 grid-9">
					<input name="dividerr" type="text" id="dividerr" value="<?= $divider ?>" class="wp20"/>
					<div class="em gray2 fs-09">( Разделитель для парсера/разбора содержимого сообщения )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Фильтр сообщений:</div>
				<div class="column12 grid-9">
					<input name="filter" type="text" id="filter" value="<?= $filter ?>" class="wp50"/>
					<div class="em gray2 fs-09">( Ключевое слово для выбора сообщений с заявками. Поиск происходит по заголовку / теме сообщения )</div>
				</div>

			</div>

		</div>
		<div id="res" class="hidden infodiv"></div>
		<hr>
		<?php
		if (!extension_loaded("imap")) {

			print '<br><div class="warning"><i class="icon-attention red"></i>&nbsp;Требуемый модуль <u><b>PHP-IMAP</b></u> <b class="red">не подключен</b> в настройках сервера. См. php.ini</div><br>';
		}
		else {
			?>
			<DIV class="text-right button--pane">
				<a href="javascript:void(0)" onclick="checkConnection()" class="button orangebtn"><i class="icon-arrows-cw"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
				<a href="javascript:void(0)" class="button" onClick="$('#sett').submit()">Сохранить</a>&nbsp;
				<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>
			</DIV>
		<?php } ?>
	</FORM>
	<script type="text/javascript">
		$(function () {

			$('#dialog').css('width', '602px').center();
		});

		$('#sett').ajaxForm({
			beforeSubmit: function () {
				var $out = $('#message');
				$out.empty();
				$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				return true;
			},
			success: function (data) {
				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');
				$("#tab-form-2").load('modules/leads/settings.leads.php?action=list');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}
		});

		function getServ() {
			var serv = $('#serv option:selected').val();
			$.post('modules/leads/settings.leads.php?action=getserv&serv=' + serv, function (data) {
				var obj = JSON.parse(data);
				$('#smtp_host').val(obj.host);
				$('#smtp_port').val(obj.port);
				$('#smtp_auth [value=' + obj.auth + ']').attr("selected", "selected");
				$('#smtp_secure [value=' + obj.secure + ']').attr("selected", "selected");
				$('#smtp_protocol [value=' + obj.protocol + ']').attr("selected", "selected");
			});
		}

		function checkConnection() {
			var url = "modules/leads/settings.leads.php";
			var str = $('#sett').serialize() + '&act=check';

			$('#res').removeClass('hidden').append('<img src="/assets/images/loading.gif">');

			$.post(url, str, function (data) {
				if (data) {
					$('#res').html(data);
				}
				return false;
			});
		}
	</script>
	<?php
	exit();
}

if ($action == "settings") {

	$mdwset      = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'");
	$mdwsettings = json_decode((string)$mdwset['content'], true);
	$apikey      = $mdwset['secret'];

	$tpl = htmlspecialchars_decode($db -> getOne("SELECT content FROM ".$sqlname."tpl WHERE tip = 'leadClientNotifyTemp' and identity = '$identity'"));

	$template = ($tpl != '') ? $tpl : $template;

	$tpl2 = htmlspecialchars_decode($db -> getOne("SELECT content FROM ".$sqlname."tpl WHERE tip = 'leadSendWellcomeTemp' and identity = '$identity'"));

	$template2 = ($tpl2 != '') ? $tpl2 : $template;

	?>
	<br>
	<FORM action="/modules/leads/settings.leads.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="settings_do">

		<div class="row hidden">

			<div class="column12 grid-2 right-text fs-12 gray2 pt10">API key:</div>
			<div class="column12 grid-10">
				<input name="leadIdentity" type="text" id="leadIdentity" class="wp80" value="<?= $apikey ?>">
				<a href="javascript:void(0)" onclick="getKey()" class="sbutton"><i class="icon-arrows-ccw"></i>Получить</a>
				<div class="fs-09 gray2">Ключ, который будет работать только для заявок из форм</div>
			</div>
			<hr>

		</div>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2 pt10">Координатор:</div>
			<div class="column12 grid-10">
			<span class="select">
				<SELECT name="leadСoordinator" id="leadСoordinator">
					<?php
					$result = $db -> getAll("SELECT * FROM ".$sqlname."user where secrty = 'yes' and identity = '$identity' ORDER by title");
					foreach ($result as $data) {
						?>
						<option <?php if ($data['iduser'] == $mdwsettings['leadСoordinator']) print "selected"; ?> value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
					<?php } ?>
				</SELECT>
			</span>
				<div class="fs-09 gray2">Сотрудник, являющийся координатором обработки заявок</div>
			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2 pt10">Удаление:</div>
			<div class="column12 grid-10">
			<span class="select">
				<SELECT name="leadCanDelete" id="leadCanDelete" class="yw250">
					<option <?php if ($mdwsettings['leadCanDelete'] == "all") print "selected"; ?> value="all">Разрешить (любые)</option>
					<option <?php if ($mdwsettings['leadCanDelete'] == "unknown") print "selected"; ?> value="unknown">Разрешить (только без источника)</option>
					<option <?php if ($mdwsettings['leadCanDelete'] == "nophone") print "selected"; ?> value="nophone">Разрешить (только без телефона)</option>
					<option <?php if ($mdwsettings['leadCanDelete'] == "noemail") print "selected"; ?> value="noemail">Разрешить (только без email)</option>
					<option <?php if ($mdwsettings['leadCanDelete'] == "nodelete") print "selected"; ?> value="nodelete">Запретить</option>
				</SELECT>
			</span>
				<div class="fs-09 gray2">Разрешить/Запретить удаление лидов координатором</div>
			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2">Результаты:</div>
			<div class="column12 grid-10">
				<label><input id="leadSendCoordinatorNotify" name="leadSendCoordinatorNotify" type="checkbox" value="yes" <?php if ($mdwsettings['leadSendCoordinatorNotify'] == 'yes') print 'checked' ?> />&nbsp;Уведомлять Координатора по результатам обработки&nbsp;</label>
			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2">Форма:</div>
			<div class="column12 grid-10">
				<label><input id="leadHideContact" name="leadHideContact" type="checkbox" value="yes" <?php if ($mdwsettings['leadHideContact'] == 'yes') print 'checked' ?> />&nbsp;Скрыть блок Контакт&nbsp;</label>
			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 blue pt10">Алгоритм:</div>
			<div class="column12 grid-10">
			<span class="select">
				<SELECT name="leadMethod" id="leadMethod" class="yw250">
					<option <?php if ($mdwsettings['leadMethod'] == "") print "selected"; ?> value="">Через координатора (все)</option>
					<option <?php if ($mdwsettings['leadMethod'] == "unknown") print "selected"; ?> value="unknown">Через координатора (только неизвестные)</option>
					<option <?php if ($mdwsettings['leadMethod'] == "randome") print "selected"; ?> value="randome">Рулетка</option>
					<option <?php if ($mdwsettings['leadMethod'] == "equal") print "selected"; ?> value="equal">Равномерно</option>
					<option <?php if ($mdwsettings['leadMethod'] == "free") print "selected"; ?> value="free">Свободная касса</option>
					<option <?php if ($mdwsettings['leadMethod'] == "effective") print "selected"; ?> value="effective" disabled>Эффективно [ в разработке ]</option>
				</SELECT>
			</span>
				<div class="fs-09 gray2">Каким образом модуль будет распределять заявки</div>
			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2 pt10">Операторы:</div>
			<div class="column12 grid-10">
				<div style="overflow-y: auto; overflow-x: hidden; max-height: 350px">
					<?php
					$maxlevel = 1;

					$users = User::userCatalog();
					foreach ($users as $key => $value) {

						$s = '';

						if ($value['active'] != 'yes') {
							goto b;
						}

						if ((int)$value['level'] > 0) {
							$s = str_repeat( '&nbsp;&nbsp;&nbsp;&nbsp;', $value['level'] ).'<div class="strelka mr10"></div>&nbsp;';
						}

						$ss = (in_array($value['id'], (array)$mdwsettings['leadOperator'])) ? "checked" : "";
						?>

						<label class="block ha usercat" data-id="<?= $value['id'] ?>" data-sub="<?= $value['mid'] ?>">
							<div class="row wp100">
								<div class="column grid-4">
									<div class="ellipsis"><?= $s ?>&nbsp;<?= $value['title'] ?>&nbsp;</div>
								</div>
								<div class="column grid-6">
									<input type="checkbox" name="leadOperator[]" id="leadOperator[]" value="<?= $value['id'] ?>" <?= $ss ?>>&nbsp
									<span class="gray2"><?= $value['tip'] ?></span>
								</div>
							</div>
						</label>

						<?php
						b:
					}
					?>
				</div>
				<div class="smalltxt gray2 mt10">Укажите сотрудников, которым будут распределяться заявки</div>
			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2">Отображение:</div>
			<div class="column12 grid-10">
				<label><input id="leadCanView" name="leadCanView" type="checkbox" value="yes" <?php if ($mdwsettings['leadCanView'] == 'yes') print 'checked' ?> />&nbsp;Показывать операторам чужие лиды (только просмотр)&nbsp;</label>
			</div>

		</div>

		<div id="divider" class="div-center mt20 mb20 fs-12"><b>Уведомления оператору</b></div>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2"></div>
			<div class="column12 grid-10">
				<label><input id="leadSendOperatorNotify" name="leadSendOperatorNotify" type="checkbox" value="yes" <?php if ($mdwsettings['leadSendOperatorNotify'] == 'yes') print 'checked' ?> />&nbsp;Отправлять Оператору уведомления по email&nbsp;</label>
			</div>

		</div>

		<div id="divider" class="div-center mt20 mb20 fs-12"><b>Уведомления клиенту</b></div>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2"></div>
			<div class="column12 grid-10">

				<div>
					<label><input id="leadSendClientWellcome" name="leadSendClientWellcome" type="checkbox" value="yes" <?php if ($mdwsettings['leadSendClientWellcome'] == 'yes') print 'checked' ?> />&nbsp;Отправлять Клиенту уведомления по email
						<u>при назначении ответственного</u>&nbsp;</label>
				</div>

				<div class="mt10">
					<label><input id="leadSendClientNotify" name="leadSendClientNotify" type="checkbox" value="yes" <?php if ($mdwsettings['leadSendClientNotify'] == 'yes') print 'checked' ?> />&nbsp;Отправлять Клиенту уведомления по email
						<u>после обработки заявки</u>&nbsp;</label>
				</div>

				<div class="smalltxt gray2 mt10">Уведомления отправляются через настроенный SMTP-сервер либо через утилиту Sendmail (в зависимости от настроек сервера)</div>

			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2"></div>
			<div class="column12 grid-10">

				<div class="p5 fs-11 blue">Шаблон сообщения при назначении ответственного</div>

				<div class="wp90 relativ">

					<div class="pull-right mt10">
						<a href="javascript:void(0)" class="tagsmenuToggler" title="Действия"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
						<div class="tagsmenu hidden" style="right: 0;" data-id="leadSendWellcomeTemp">
							<ul>
								<li title="Текущая дата (в формате: 29 февраля 2014 года)">
									<b class="broun">{currentDatum}</b></li>
								<li title="Текущая дата (в формате: 29.02.2014)">
									<b class="broun">{currentDatumShort}</b></li>
								<li title="Ответственный. ФИО"><b class="broun">{UserName}</b></li>
								<li title="Ответственный. Должность"><b class="broun">{UserStatus}</b></li>
								<li title="Ответственный. Телефон"><b class="broun">{UserPhone}</b></li>
								<li title="Ответственный. Мобильный"><b class="broun">{UserMob}</b></li>
								<li title="Ответственный. Email"><b class="broun">{UserEmail}</b></li>
								<li title="Название компании (общие настройки)"><b class="red">{compName}</b></li>
								<li title="Юридическое название нашей компании"><b class="red">{compUrName}</b></li>
								<li title="Краткое юр. название нашей компании"><b class="red">{compShotName}</b></li>
								<li title="Наш юр.адрес"><b class="red">{compUrAddr}</b></li>
								<li title="Наш почтовый адрес"><b class="red">{compFacAddr}</b></li>
								<li title="ИНН нашей компании"><b class="red">{compInn}</b></li>
								<li title="КПП нашей компании"><b class="red">{compKpp}</b></li>
								<li title="ОГРН нашей компании"><b class="red">{compOgrn}</b></li>
								<li title="ОКПО нашей компании"><b class="red">{compOkpo}</b></li>
								<li title="Наш банк"><b class="red">{compBankName}</b></li>
								<li title="БИК нашего банка"><b class="red">{compBankBik}</b></li>
								<li title="Наш Расчетный счет"><b class="red">{compBankRs}</b></li>
								<li title="Корр.счет нашего банка"><b class="red">{compBankKs}</b></li>
								<li title="Подпись руководителя (например, Директора Андреева Владислава Германовича)">
									<b class="red">{compDirName}</b></li>
								<li title="ФИО руководителя (Андреев В.Г.)"><b class="red">{compDirSignature}</b></li>
								<li title="Должность руководителя (Директор, Генеральный директор)">
									<b class="red">{compDirStatus}</b></li>
								<li title="На основании чего действует руководитель (Устава, Доверенности..)">
									<b class="red">{compDirOsnovanie}</b></li>

								<!--
								<li title="Плательщик. Название (Как отображается в CRM)"><b class="blue">{castName}</b></li>
								<li title="Плательщик. Юридическое название (из реквизитов)"><b class="blue">{castUrName}</b></li>
								<li title="Плательщик. ИНН (из реквизитов)"><b class="blue">{castInn}</b></li>
								<li title="Плательщик. КПП (из реквизитов)"><b class="blue">{castKpp}</b></li>
								<li title="Плательщик. Банк (из реквизитов)"><b class="blue">{castBank}</b></li>
								<li title="Плательщик. Кор.счет (из реквизитов)"><b class="blue">{castBankKs}</b></li>
								<li title="Плательщик. Расч.счет (из реквизитов)"><b class="blue">{castBankRs}</b></li>
								<li title="Плательщик. БИК банка (из реквизитов)"><b class="blue">{castBankBik}</b></li>
								<li title="Плательщик. ОКПО (из реквизитов)"><b class="blue">{castOkpo}</b></li>
								<li title="Плательщик. ОГРН (из реквизитов)"><b class="blue">{castOgrn}</b> </li>
								<li title="Плательщик. ФИО руководителя, в родительном падеже (в лице кого) - Иванова Ивана Ивановича (из реквизитов)"><b class="blue">{castDirName}</b></li>
								<li title="Плательщик. ФИО руководителя, например Иванов И.И. (из реквизитов)"><b class="blue">{castDirSignature}</b></li>
								<li title="Плательщик. Должность руководителя, в род.падеже, например: Директора (из реквизитов)"><b class="blue">{castDirStatus}</b></li>
								<li title="Плательщик. Должность руководителя, например: Директор (из реквизитов)"><b class="blue">{castDirStatusSig}</b></li>
								<li title="Плательщик. Основание прав Руководителя, в родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г. (из реквизитов)"><b class="blue">{castDirOsnovanie}</b></li>
								<li title="Плательщик. Юр.адрес (из реквизитов)"><b class="blue">{castUrAddr}</b></li>
								<li title="Плательщик. Фактич.адрес (из реквизитов)"><b class="blue">{castFacAddr}</b></li>
								-->

								<li title="Заказчик. Название (тип - Физ.лиц) или ФИО контакта (тип - Юр.лица)">
									<b class="blue">{castomerName}</b></li>
								<li title="Заказчик. Email"><b class="blue">{castomerEmail}</b></li>
								<li title="Заказчик. Телефон"><b class="blue">{castomerPhone}</b></li>
								<li title="Заказчик. Мобильный"><b class="blue">{castomerMobile}</b></li>

								<!--
								<li title="Заказчик. Название (Как отображается в CRM)"><b class="blue">{clientFname}</b></li>
								<li title="Заказчик. Контакт (Как отображается в CRM)"><b class="blue">{personName}</b></li>
								<li title="Заказчик. Адрес"><b class="blue">{clientFaddress}</b></li>
								<li title="Заказчик. Телефон"><b class="blue">{clientFphone}</b></li>
								<li title="Заказчик. Факс"><b class="blue">{clientFfax}</b></li>
								<li title="Заказчик. Email"><b class="blue">{clientFmail_url}</b></li>
								<li title="Заказчик. Сайт"><b class="blue">{clientFsite_url}</b></li>
								-->

								<?php
								$re = $db -> getAll("select fld_title, fld_name from ".$sqlname."field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '".$identity."' order by fld_order");
								foreach ($re as $d) {

									print '<li title="Заказчик. '.$d['fld_title'].'"><b class="blue">{clientF'.$d['fld_name'].'}</b></li>';

								}
								?>

								<li title="ID контакта"><b class="green">{personID}</b></li>
								<li title="ID клиента"><b class="green">{clientID}</b></li>
								<li title="ID сделки"><b class="green">{dogID}</b></li>

								<li title="Название сделки"><b class="green">{dogTitle}</b></li>
								<li title="Описание сделки"><b class="green">{dogContent}</b></li>
								<li title="Сумма сделки"><b class="green">{summaDogovor}</b></li>
								<li title="Сумма сделки прописью"><b class="green">{summaDogovorPropis}</b></li>
								<li title="Адрес (из сделки)"><b class="green">{dogAdres}</b></li>

								<li title="Период. Начало (из сделки)"><b class="green">{dogDataStart}</b></li>
								<li title="Период. Конец (из сделки)"><b class="green">{dogDataEnd}</b></li>
								<?php
								$res = $db -> getAll("select * from ".$sqlname."field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '".$GLOBALS['identity']."' order by fld_order");
								foreach ($res as $data) {

									print '<li title="'.$data['fld_title'].'"><b class="green">{dogF'.$data['fld_name'].'}</b></li>';

								}
								?>
								<!--
								<li title="Номер договора (из сделки)"><b class="green">{doсNum}</b></li>
								<li title="Дата договора (из сделки)"><b class="green">{doсDate}</b></li>
								<li title="Номер счета (из сделки)"><b class="green">{invoiceNum}</b></li>
								<li title="Сумма счета"><b class="green">{summaCredit}</b></li>
								<li title="Дата счета (в формате: 29 февраля 2014 года)"><b class="green">{invoiceDatum}</b></li>
								<li title="Дата счета (в формате: 29.02.2014)"><b class="green">{invoiceDatumShort}</b></li>
								-->
							</ul>
						</div>
					</div>
					<textarea id="leadSendWellcomeTemp" name="leadSendWellcomeTemp" style="width: 90%"><?= $template2 ?></textarea>
					<div class="smalltxt gray2 mt10">Шаблон письма, которое будет отправлено клиенту при назначении Ответственного за обработку заявки. Отправляется также в случае автоматического распределения заявок</div>

				</div>

			</div>

		</div>
		<hr>

		<div class="row">

			<div class="column12 grid-2 right-text fs-12 gray2"></div>
			<div class="column12 grid-10">

				<div class="p5 fs-11 blue">Шаблон сообщения после обработки заявки</div>

				<div class="wp90 relativ">

					<div class="pull-right mt10">
						<a href="javascript:void(0)" class="tagsmenuToggler" title="Действия"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
						<div class="tagsmenu hidden" style="right: 0;" data-id="leadClientNotifyTemp">
							<ul>
								<li title="Текущая дата (в формате: 29 февраля 2014 года)"><b class="broun">{currentDatum}</b></li>
								<li title="Текущая дата (в формате: 29.02.2014)"><b class="broun">{currentDatumShort}</b></li>
								<li title="Ответственный. ФИО"><b class="broun">{UserName}</b></li>
								<li title="Ответственный. Должность"><b class="broun">{UserStatus}</b></li>
								<li title="Ответственный. Телефон"><b class="broun">{UserPhone}</b></li>
								<li title="Ответственный. Мобильный"><b class="broun">{UserMob}</b></li>
								<li title="Ответственный. Email"><b class="broun">{UserEmail}</b></li>
								<li title="Название компании (общие настройки)"><b class="red">{compName}</b></li>
								<li title="Юридическое название нашей компании"><b class="red">{compUrName}</b></li>
								<li title="Краткое юр. название нашей компании"><b class="red">{compShotName}</b></li>
								<li title="Наш юр.адрес"><b class="red">{compUrAddr}</b></li>
								<li title="Наш почтовый адрес"><b class="red">{compFacAddr}</b></li>
								<li title="ИНН нашей компании"><b class="red">{compInn}</b></li>
								<li title="КПП нашей компании"><b class="red">{compKpp}</b></li>
								<li title="ОГРН нашей компании"><b class="red">{compOgrn}</b></li>
								<li title="ОКПО нашей компании"><b class="red">{compOkpo}</b></li>
								<li title="Наш банк"><b class="red">{compBankName}</b></li>
								<li title="БИК нашего банка"><b class="red">{compBankBik}</b></li>
								<li title="Наш Расчетный счет"><b class="red">{compBankRs}</b></li>
								<li title="Корр.счет нашего банка"><b class="red">{compBankKs}</b></li>
								<li title="Подпись руководителя (например, Директора Андреева Владислава Германовича)"><b class="red">{compDirName}</b></li>
								<li title="ФИО руководителя (Андреев В.Г.)"><b class="red">{compDirSignature}</b></li>
								<li title="Должность руководителя (Директор, Генеральный директор)"><b class="red">{compDirStatus}</b></li>
								<li title="На основании чего действует руководитель (Устава, Доверенности..)"><b class="red">{compDirOsnovanie}</b></li>

								<!--
								<li title="Плательщик. Название (Как отображается в CRM)"><b class="blue">{castName}</b></li>
								<li title="Плательщик. Юридическое название (из реквизитов)"><b class="blue">{castUrName}</b></li>
								<li title="Плательщик. ИНН (из реквизитов)"><b class="blue">{castInn}</b></li>
								<li title="Плательщик. КПП (из реквизитов)"><b class="blue">{castKpp}</b></li>
								<li title="Плательщик. Банк (из реквизитов)"><b class="blue">{castBank}</b></li>
								<li title="Плательщик. Кор.счет (из реквизитов)"><b class="blue">{castBankKs}</b></li>
								<li title="Плательщик. Расч.счет (из реквизитов)"><b class="blue">{castBankRs}</b></li>
								<li title="Плательщик. БИК банка (из реквизитов)"><b class="blue">{castBankBik}</b></li>
								<li title="Плательщик. ОКПО (из реквизитов)"><b class="blue">{castOkpo}</b></li>
								<li title="Плательщик. ОГРН (из реквизитов)"><b class="blue">{castOgrn}</b> </li>
								<li title="Плательщик. ФИО руководителя, в родительном падеже (в лице кого) - Иванова Ивана Ивановича (из реквизитов)"><b class="blue">{castDirName}</b></li>
								<li title="Плательщик. ФИО руководителя, например Иванов И.И. (из реквизитов)"><b class="blue">{castDirSignature}</b></li>
								<li title="Плательщик. Должность руководителя, в род.падеже, например: Директора (из реквизитов)"><b class="blue">{castDirStatus}</b></li>
								<li title="Плательщик. Должность руководителя, например: Директор (из реквизитов)"><b class="blue">{castDirStatusSig}</b></li>
								<li title="Плательщик. Основание прав Руководителя, в родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г. (из реквизитов)"><b class="blue">{castDirOsnovanie}</b></li>
								<li title="Плательщик. Юр.адрес (из реквизитов)"><b class="blue">{castUrAddr}</b></li>
								<li title="Плательщик. Фактич.адрес (из реквизитов)"><b class="blue">{castFacAddr}</b></li>
								-->

								<li title="Заказчик. Название (тип - Физ.лиц) или ФИО контакта (тип - Юр.лица)"><b class="blue">{castomerName}</b></li>
								<li title="Заказчик. Email"><b class="blue">{castomerEmail}</b></li>
								<li title="Заказчик. Телефон"><b class="blue">{castomerPhone}</b></li>
								<li title="Заказчик. Мобильный"><b class="blue">{castomerMobile}</b></li>

								<!--
								<li title="Заказчик. Название (Как отображается в CRM)"><b class="blue">{clientFname}</b></li>
								<li title="Заказчик. Контакт (Как отображается в CRM)"><b class="blue">{personName}</b></li>
								<li title="Заказчик. Адрес"><b class="blue">{clientFaddress}</b></li>
								<li title="Заказчик. Телефон"><b class="blue">{clientFphone}</b></li>
								<li title="Заказчик. Факс"><b class="blue">{clientFfax}</b></li>
								<li title="Заказчик. Email"><b class="blue">{clientFmail_url}</b></li>
								<li title="Заказчик. Сайт"><b class="blue">{clientFsite_url}</b></li>
								-->

								<?php
								$re = $db -> getAll("select fld_title, fld_name from ".$sqlname."field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '".$identity."' order by fld_order");
								foreach ($re as $d) {

									print '<li title="Заказчик. '.$d['fld_title'].'"><b class="blue">{clientF'.$d['fld_name'].'}</b></li>';

								}
								?>

								<li title="ID контакта"><b class="green">{personID}</b></li>
								<li title="ID клиента"><b class="green">{clientID}</b></li>
								<li title="ID сделки"><b class="green">{dogID}</b></li>

								<li title="Название сделки"><b class="green">{dogTitle}</b></li>
								<li title="Описание сделки"><b class="green">{dogContent}</b></li>
								<li title="Сумма сделки"><b class="green">{summaDogovor}</b></li>
								<li title="Сумма сделки прописью"><b class="green">{summaDogovorPropis}</b></li>
								<li title="Адрес (из сделки)"><b class="green">{dogAdres}</b></li>

								<li title="Период. Начало (из сделки)"><b class="green">{dogDataStart}</b></li>
								<li title="Период. Конец (из сделки)"><b class="green">{dogDataEnd}</b></li>
								<?php
								$res = $db -> getAll("select * from ".$sqlname."field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '".$GLOBALS['identity']."' order by fld_order");
								foreach ($res as $data) {

									print '<li title="'.$data['fld_title'].'"><b class="green">{dogF'.$data['fld_name'].'}</b></li>';

								}
								?>
								<!--
								<li title="Номер договора (из сделки)"><b class="green">{doсNum}</b></li>
								<li title="Дата договора (из сделки)"><b class="green">{doсDate}</b></li>
								<li title="Номер счета (из сделки)"><b class="green">{invoiceNum}</b></li>
								<li title="Сумма счета"><b class="green">{summaCredit}</b></li>
								<li title="Дата счета (в формате: 29 февраля 2014 года)"><b class="green">{invoiceDatum}</b></li>
								<li title="Дата счета (в формате: 29.02.2014)"><b class="green">{invoiceDatumShort}</b></li>
								-->
							</ul>
						</div>
					</div>
					<textarea id="leadClientNotifyTemp" name="leadClientNotifyTemp" style="width: 90%"><?= $template ?></textarea>
					<div class="smalltxt gray2 mt10">Шаблон письма, которое будет отправлено клиенту после обработки заявки. Сообщение отправляется только для качественных заявок - создан Клиент и/или Контакт и/или Сделка</div>

				</div>

			</div>

		</div>
		<hr>

		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
			<a href="javascript:void(0)" class="button" onClick="saveForm()">Сохранить</a>
		</DIV>

	</FORM>

	<script>

		var editor, editor2;

		$(function () {

			$(".multiselect").multiselect({sortable: true, searchable: true});
			$(".connected-list").css('height', "120px");

			var blok = localStorage.getItem("settingsModDayControl");
			if (blok != null) {
				$('tbody').addClass('hidden');
				$('#' + blok).toggleClass('hidden');
				$('thead[data-id="' + blok + '"]').find('i').toggleClass('icon-angle-down icon-angle-up');
			} else {
				$('#tab-form-1').find('thead:first-child').find('i').removeClass('icon-angle-down').addClass('icon-angle-up');
			}

			editor = CKEDITOR.replace('leadClientNotifyTemp',
				{
					height: '350px',
					width: '100.0%',
					toolbar:
						[
							['Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-'],
							['Undo', 'Redo', '-', 'Replace', '-', 'RemoveFormat', '-', 'HorizontalRule'],
							['TextColor', 'FontSize'],
							['JustifyLeft', 'JustifyCenter', 'JustifyBlock', 'Source']
						]
				});

			editor2 = CKEDITOR.replace('leadSendWellcomeTemp',
				{
					height: '350px',
					width: '100.0%',
					toolbar:
						[
							['Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-'],
							['Undo', 'Redo', '-', 'Replace', '-', 'RemoveFormat', '-', 'HorizontalRule'],
							['TextColor', 'FontSize'],
							['JustifyLeft', 'JustifyCenter', 'JustifyBlock', 'Source']
						]
				});

		});

		$('#set').ajaxForm({
			beforeSubmit: function () {

				CKEDITOR.instances['leadClientNotifyTemp'].updateElement();
				CKEDITOR.instances['leadSendWellcomeTemp'].updateElement();

				var $out = $('#message');
				$out.empty();
				$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				if (editor) {

					editor.destroy();
					editor = null;

				}

				if (editor2) {

					editor2.destroy();
					editor2 = null;

				}

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('modules/leads/settings.leads.php').append('<img src="/assets/images/loading.gif">');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}
		});

		function saveForm(){

			CKEDITOR.instances['leadClientNotifyTemp'].updateElement();
			CKEDITOR.instances['leadSendWellcomeTemp'].updateElement();

			$('#set').trigger('submit');

		}

		$('thead').on('click', function () {

			var id = $(this).data('id');

			$('#contentdiv').find('tbody:not(#' + id + ')').addClass('hidden');
			$('#contentdiv').not('thead[data-id="' + id + '"]').find('i').removeClass('icon-angle-up').addClass('icon-angle-down');

			if ($('#contentdiv #' + id).hasClass('hidden')) {
				$('#contentdiv #' + id).removeClass('hidden');
				$('#contentdiv').find('thead[data-id="' + id + '"]').find('i').toggleClass('icon-angle-down icon-angle-up');

				localStorage.setItem("settingsModDayControl", id);
			} else {
				$('#contentdiv #' + id).addClass('hidden');
				$('#contentdiv').find('thead[data-id="' + id + '"]').find('i').removeClass('icon-angle-up').addClass('icon-angle-down');
				localStorage.removeItem("settingsModDayControl");
			}
		});

		$('.usercat').on('click', function () {

			var id = $(this).data('id');
			var sub = $(this).data('sub');

			if (sub == "0") {

				if ($(this).find('input').attr('checked')) {

					$('.usercat[data-sub="' + id + '"]').find('input').attr("checked", "checked");

				} else $('.usercat[data-sub="' + id + '"]').find('input').removeAttr("checked");

			}

		});
		$('.coordcat').on('click', function () {

			var id = $(this).data('id');
			var sub = $(this).data('sub');

			if ($(this).find('input').attr('checked')) {

				$('.usercat[data-id="' + id + '"]').find('input').attr("checked", "checked");

			} else $('.usercat[data-id="' + id + '"]').find('input').removeAttr("checked");

		});
		$('.tagsmenu li').on('click',function () {

			var t = $('b', this).html();
			var id = $(this).closest('.tagsmenu').data('id');

			if (id === 'leadClientNotifyTemp') addTagInEditor(t);
			else addTagInEditor2(t);

		});

		function addTagInEditor(myitem) {

			var oEditor = CKEDITOR.instances.leadClientNotifyTemp;
			oEditor.insertHtml(myitem);

			return true;
		}

		function addTagInEditor2(myitem) {

			var oEditor = CKEDITOR.instances.leadSendWellcomeTemp;
			oEditor.insertHtml(myitem);

			return true;
		}

	</script>
	<?php

	exit();
}
if ($action == 'list') {
	?>
	<div class="viewdiv marg10">

		<p>Для автоматической работы Сборщика заявок (проверки email), надо добавить задание для планировщика.</p>

		<p>Ссылка для планировщика:</p>

		<code style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box; margin: 20px 0 !important; font-weight: 700" class="bgray pad10 block enable--select">
			/usr/bin/wget -O - -q -t 1 --no-check-certificate <?= $base ?>/cron/cronLeadsChecker.php
		</code>

		<p>За одно выполнение скриптом обрабатывается до 20 заявок. Если скрипт уже работал, то проверка писем будет ограничена датой последней загруженной заявки</p>

		<div class="attention">

			<ul>
				<li>Рекомендуется использовать плагин <a href="https://salesman.pro/docs/155" target="_blank" title="Планировщик заданий" class="Bold blue">Планировщик заданий</a></li>
				<li>Возможна интеграция с любым сайтом с помощью <a href="https://salesman.pro/api2/lead" target="_blank" title="API" class="Bold blue">API</a></li>
				<li>Можно использовать плагин для Wordpress - <a href="https://salesman.pro/docs/151" target="_blank" title="SalesMan CF7 to CRM Connector" class="Bold blue">SalesMan CF7 to CRM Connector</a></li>
			</ul>

		</div>

	</div>

	<hr>

	<table >
		<thead>
		<tr class="th40">
			<TH class="w20">ID</TH>
			<TH class="w250">Название</TH>
			<TH class="w250">Email</TH>
			<TH class="w250">Сервер</TH>
			<TH class="w50">Вкл.</TH>
			<TH class="text-left">Действие</TH>
		</tr>
		</thead>
		<tbody>
		<?php
		$active = [
			'yes' => '<i class="icon-ok-circled green"></i>',
			'no'  => '<i class="icon-ok-circled gray"></i>'
		];
		$i      = 1;

		$result = $db -> getAll("SELECT * FROM ".$sqlname."smtp WHERE tip = 'lead' and identity = '$identity'");
		foreach ($result as $data) {
			$on = strtr($data['active'], $active);
			$gg = $data['active'] == 'yes' ? '' : 'gray';
			?>
			<TR class="th40 ha <?= $gg ?>">
				<TD class="text-center"><B><?= $i ?></B></TD>
				<TD><SPAN class="ellipsis" title="<?= $data['name'] ?>"><B><?= $data['name'] ?></B></SPAN>
				</TD>
				<TD>
					<SPAN class="ellipsis" title="<?= rij_decrypt($data['smtp_user'], $skey, $ivc) ?>"><B><?= rij_decrypt($data['smtp_user'], $skey, $ivc) ?></B></SPAN>
				</TD>
				<TD>
					<SPAN class="ellipsis" title="<?= $data['smtp_host'] ?>"><B><?= $data['smtp_host'] ?></B></SPAN>
				</TD>
				<TD class="text-center"><?= $on ?></TD>
				<TD nowrap>&nbsp;<A href="javascript:void(0)" onClick="doLoad('modules/leads/settings.leads.php?id=<?= $data['id'] ?>&action=listedit');" title="Изменить"><i class="icon-pencil blue"></i></A>&nbsp;&nbsp;&nbsp;<A href="javascript:void(0)" onClick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)doDeleteList('<?= $data['id'] ?>');" title="Удалить"><i class="icon-cancel-circled red"></i></A>
				</TD>
			</TR>
			<?php
			$i++;
		}
		?>
		</tbody>
	</table>

	<hr>

	<div class="infodiv">
		<?php
		if (!extension_loaded("imap")) {
			print '<i class="icon-attention red"></i>&nbsp;Требуемый модуль <u><b>IMAP</b></u> <b class="red">не подключен</b>.<br>';
		}
		else print '<i class="icon-ok-circled green"></i>&nbsp;Требуемый модуль <u><b>PHP-IMAP</b></u> <b class="green">подключен</b>.<br>';
		?>
	</div>

	<hr>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
		<a href="javascript:void(0)" onclick="doLoad('modules/leads/settings.leads.php?action=listedit');" class="button"><i class="icon-plus-circled"></i>Добавить</a>
	</div>

	<script type="text/javascript">
	</script>
	<?php
}
if ($action == 'services') {

	//настройки модуля для аккаунта
	$mdwset       = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'");
	$leadsettings = json_decode((string)$mdwset['content'], true);
	$users        = (array)$leadsettings['leadOperator'];
	$coordinator  = (int)$leadsettings["leadСoordinator"];

	$login = $db -> getOne("select login from ".$sqlname."user WHERE iduser = '".$coordinator."'");

	?>
	<div class="viewdiv marg10">

		<p>Вы можете настроить автоматический прием заявок из сторонних сервисов и констуркторов сайтов. Ниже приведены готовые коды для использования.</p>

	</div>

	<hr>

	<div class="row">

		<div class="column12 grid-2 fs-12 Bold gray2 right-text">Flexbe.com</div>
		<div class="column12 grid-10">

			<div>
				Обработчик Webhook для констурктора сайтов Flexbe.com (<a href="http://flexbe.com/api/?php#event_lead" target="_blank" title="Документация" class="blue">описание API</a>), который срабатывает во время отправки формы
			</div>

			<code style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box; margin: 20px 0 !important; font-weight: 700" class="bgray pad10 block"><?= $base ?>/developer/fromlp/flexbe.event.php?api_key=<?= $api_key ?></code>

			<div class="url button hand" data-clipboard-text="<?= $base ?>/developer/fromlp/flexbe.event.php?api_key=<?= $api_key ?>" title="Скопировать в буфер">
				<i class="icon-paste white"></i> Скопировать в буфер
			</div>

		</div>

	</div>
	<hr>

	<div class="row">

		<div class="column12 grid-2 fs-12 Bold gray2 right-text">LpMotor,<br>LpGenerator,<br>etc.</div>
		<div class="column12 grid-10">

			<div>
				Обработчик Webhook для констуркторов посадочных страниц (<a href="<?= $productInfo['site'] ?>/docs/62" target="_blank" title="Документация" class="blue">описание</a>), который срабатывает во время отправки формы
			</div>

			<code style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box; margin: 20px 0 !important; font-weight: 700" class="bgray pad10 block"><?= $base ?>/developer/v2/lead?apikey=<?= $api_key ?>&login=<?= $login ?>&name={name}&phone={phone}&email={email}&id_lead={id_lead}&description={extra}&utm_source={utm_source}</code>

			<div class="url button hand" data-clipboard-text="<?= $base ?>/developer/v2/lead?apikey=<?= $api_key ?>&login=<?= $login ?>&action=add&name={name}&phone={phone}&email={email}&id_lead={id_lead}&description={extra}&utm_source={utm_source}" title="Скопировать в буфер">
				<i class="icon-paste white"></i> Скопировать в буфер
			</div>

			<div class="em">
				<b class="red">Важно:</b> Метод работает для любых конструкторов сайтов, которые позволяют отправить данные POST, GET запросом по ссылкам. Мы не гарантируем передачу UTM-меток (это делается на стороне конструктора), но гарантируем их обработку в случае передачи
			</div>

		</div>

	</div>
	<hr>

	<script>

		var clipboard = new Clipboard('.url');

		clipboard.on('success', function (e) {

			alert("Скопировано в буфер");
			e.clearSelection();

		});

	</script>

	<?php

	exit();

}
if ($action == 'forma') {

	//настройки модуля для аккаунта
	$mdwset       = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'");
	$leadsettings = json_decode((string)$mdwset['content'], true);
	$leadIdentity = (array)$leadsettings['leadIdentity'];
	$apikey       = (int)$mdwset['secret'];

	?>
	<div class="viewdiv marg10">

		<p>С помощью данного кода CRM может перехватывать информацию, переданную через формы на вашем сайте. Для этого достаточно добавить его в код своего сайта</p>

	</div>

	<hr>

	<div class="row">

		<div class="column12 grid-2 fs-12 Bold gray2 right-text">Код для вставки:</div>
		<div class="column12 grid-10">

			<textarea style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box; height: 300px" class="bgray" id="lcode" spellcheck="false"></textarea>

			<ul class="fs-10">
				<li>Вместо <b class="red">"FormID"</b> укажите ID вашей формы на сайте.</li>
				<li>Скопируйте указанный код и разместите на странице перед закрывающим тэгом <b>BODY</b>.</li>
			</ul>

			<hr>

			<a href="javascript:void(0)" class="button" onclick="getCode()">Получить код</a>
			<a href="javascript:void(0)" data-clipboard-target="#lcode" class="code button" title="Скопировать в буфер"><i class="icon-paste white"></i> Скопировать в буфер</a>

		</div>

	</div>

	<div class="block" style="height: 60px"></div>

	<script>

		var clipboard = new Clipboard('.code');
		clipboard.on('success', function (e) {

			alert("Скопировано в буфер");
			e.clearSelection();

		});

		$(function () {
			getCode();
		});

		function getCode() {

			var str =
				'<script type="text/javascript">\n\
					(function(){\n\
						if (typeof salesmanleads === "undefined") {\n\
								var s = document.createElement("script");\n\
									s.type = "text/javascript";\n\
									s.async = true;\n\
									s.src = "<?=$base?>/api/leads/js/leads.js";\n\
									var x = document.getElementsByTagName("head")[0];\n\
										x.appendChild(s);\n\
									salesmanleads = {};\n\
									salesmanleads.settings = {\n\
										"identity":"<?=$apikey?>",\n\
										"forma":["FormID1","FormID2"],//!Заменить FormID\n\
										"baseurl":"<?=$base?>"\n\
									};\n\
								}\n\
							})();\n' +
				'<' + '/script>';

			$('#lcode').val(str);

		}

	</script>
	<?php
	exit();
}

if ($action == "uids") {

	$uids = [];

	$s = $db -> getRow("SELECT * FROM ".$sqlname."customsettings WHERE tip = 'uids' AND identity = '$identity'");
	if ((int)$s['id'] < 1) {

		$a = [
			"tip"      => "uids",
			"params"   => "{}",
			"identity" => $identity
		];
		$db -> query("INSERT INTO  ".$sqlname."customsettings SET ?u", $a);

	}
	else{

		$uids = json_decode($s['params'], true);

	}

	?>
	<div class="infodiv marg10">

		<p>
			Вы можете указать названия идентификаторов внешних систем. Это поможет Сборщику заявок распознавать нужные данные в запросах и связывать Заявки, Клиентов и Сделки с этими системами.
		</p>

		<div class="Bold">Например:</div>
		<p>

			Идентификатор
			<u class="Bold">roistat_id</u> - позволит принимать и привязвать идентификатор системы Роистат к записям Заявок, а затем и к записям Клиентов и Сделок, что позволит интегрировать эту систему с SalesMan CRM
		</p>

	</div>

	<div class="divider">Добавленные</div>

	<div class="flex-container wp100 mt20 mb20">

		<?php
		foreach($uids as $uid){

			print '
			<div class="tags focused">
				'.$uid.'
				<div class="txt hand uiddelete enable--select" data-uid="'.$uid.'" title="Удалить"><i class="icon-cancel-circled red"></i></div>
			</div>
			';

		}
		?>

	</div>

	<div class="divider">Добавить новый</div>

	<div class="flex-container mt20">

		<div class="flex-string wp100">

			<input type="text" id="name" name="name" class="w200">
			<a href="javascript:void(0)" onclick="addUID()" class="button stick pt5 pb5" title="Добавить">Добавить</a>

		</div>

	</div>

	<div class="block" style="height: 60px"></div>

	<script>

		$(function () {

			$(document).off('click','.uiddelete');
			$(document).on('click','.uiddelete', function(){

				var str = $(this).data('uid');

				$('#message').empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				$.get("modules/leads/settings.leads.php?action=uids.delete&name="+str, function(data){

					$('#tab-form-4').load('modules/leads/settings.leads.php?action=uids').append('<img src="/assets/images/loading.gif">');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				});

			})

		});

		function addUID(){

			var str = $('#name').val();

			$('#message').empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

			$.get("modules/leads/settings.leads.php?action=uids.add&name="+str, function(data){

				$('#tab-form-4').load('modules/leads/settings.leads.php?action=uids').append('<img src="/assets/images/loading.gif">');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}

	</script>
	<?php
	exit();

}

if ($action == "") {
	?>
	<DIV id="formtabse" style="border:0">
		<UL>
			<LI><A href="#tab-form-1">Настройки модуля</A></LI>
			<LI><A href="#tab-form-2">Список Email</A></LI>
			<LI><A href="#tab-form-3">Обработчики</A></LI>
			<LI><A href="#tab-form-4">Внешние системы</A></LI>
			<!--<LI><A href="#tab-form-5">Форма</A></LI>-->
		</UL>
		<div id="tab-form-1"></div>
		<div id="tab-form-2"></div>
		<div id="tab-form-3"></div>
		<div id="tab-form-4"></div>
		<!--<div id="tab-form-5"></div>-->
	</DIV>

	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/62')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script src="/assets/js/clipboard.js/clipboard.min.js"></script>
	<script>

		//includeJS("/assets/js/clipboard.min.js");

		$('#formtabse').tabs();
		$('#tab-form-1').load('modules/leads/settings.leads.php?action=settings').append('<img src="/assets/images/loading.gif">');
		$('#tab-form-2').load('modules/leads/settings.leads.php?action=list').append('<img src="/assets/images/loading.gif">');
		$('#tab-form-3').load('modules/leads/settings.leads.php?action=services').append('<img src="/assets/images/loading.gif">');
		$('#tab-form-4').load('modules/leads/settings.leads.php?action=uids').append('<img src="/assets/images/loading.gif">');

		//$('#tab-form-5').load('modules/leads/settings.php?action=forma').append('<img src="images/loading.gif">');

		function doDeleteList(id) {

			var url = 'modules/leads/settings.leads.php?action=listdelete&id=' + id;
			$.post(url, function (data) {

				$('#tab-form-2').load('modules/leads/settings.leads.php').append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				return true;

			});

		}

		function getKey() {
			var url = 'modules/leads/settings.leads.php?action=getApiKey';
			$.post(url, function (data) {
				$('#leadIdentity').val(data);
			});
		}

	<?php
}
?>