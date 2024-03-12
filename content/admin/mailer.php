<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Imap\ImapUtf7;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];
$iduser = (int)$_REQUEST['iduser'];

function getUserCatalogg($id = 0, $level = 0, $res = []) {

	global $rootpath;

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";

	$identity = $GLOBALS['identity'];
	$sort     = $GLOBALS['sort'];
	$sqlname  = $GLOBALS['sqlname'];

	$db = $GLOBALS['db'];

	global $res;

	if (!$id) {
		$sort .= " and mid = '0'";
	}
	else {
		$sort .= " and mid = '".$id."'";
	}

	//print "SELECT iduser, mid, title, email, secrty, otdel FROM {$sqlname}user WHERE iduser > 0 $sort and identity = '$identity' ORDER BY mid, title";

	$re = $db -> getAll("SELECT iduser, mid, title, email, secrty, otdel FROM {$sqlname}user WHERE iduser > 0 $sort and identity = '$identity' ORDER BY mid, title");
	foreach ($re as $da) {

		$reso  = $db -> getRow("SELECT * FROM {$sqlname}otdel_cat WHERE idcategory='".$da['otdel']."' and identity = '$identity'");
		$otdel = $reso["title"];
		$uid   = $reso["uid"];

		$result    = json_decode($db -> getOne("SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '".$da['iduser']."' and identity = '$identity'"), true);
		$ymailUser = $result['ymailFrom'];
		$ymailPass = $result['ymailPass'];

		if ($uid != '') {
			$otdel = '<b>'.$uid.'</b>. '.$otdel;
		}

		$res[] = [
			"id"        => $da["iduser"],
			"title"     => $da["title"],
			"level"     => $level,
			"secrty"    => $da['secrty'],
			"tip"       => $da['tip'],
			"email"     => $da['email'],
			"otdel"     => $otdel,
			"mid"       => $da['mid'],
			"ymailUser" => $ymailUser,
			"ymailPass" => $ymailPass
		];

		if ($da['iduser'] > 0) {

			$level++;
			getUserCatalogg($da['iduser'], $level);
			$level--;

		}

	}

	return $res;
}

if ($action == 'account_on') {

	$id = (int)$_REQUEST['id'];

	$param['ymailInProtocol'] = $_REQUEST['ymailInProtocol'];
	$param['ymailInHost']     = $_REQUEST['ymailInHost'];
	$param['ymailInPort']     = $_REQUEST['ymailInPort'];
	$param['ymailInSecure']   = $_REQUEST['ymailInSecure'];

	$param['ymailOutProtocol'] = $_REQUEST['ymailOutProtocol'];
	$param['ymailOutHost']     = $_REQUEST['ymailOutHost'];
	$param['ymailOutPort']     = $_REQUEST['ymailOutPort'];
	$param['ymailOutSecure']   = $_REQUEST['ymailOutSecure'];
	$param['ymailOutCharset']  = $_REQUEST['ymailOutCharset'];

	$param['ymailAuth'] = $_REQUEST['ymailAuth'];
	$param['ymailFrom'] = $_REQUEST['ymailFrom'];

	$param['ymailOnReadSeen'] = $_REQUEST['ymailOnReadSeen'];
	$param['ymailOnDelete']   = $_REQUEST['ymailOnDelete'];
	$param['ymailFolderSent'] = $_REQUEST['ymailFolderSent'];

	$param['ymailAddHistoryInbox']  = $_REQUEST['ymailAddHistoryInbox'];
	$param['ymailAddHistorySended'] = $_REQUEST['ymailAddHistorySended'];
	$param['ymailAddHistoryDeal']   = $_REQUEST['ymailAddHistoryDeal'];
	$param['ymailAutoSaveTimer']    = $_REQUEST['ymailAutoSaveTimer'];
	$param['ymailClearDay']         = $_REQUEST['ymailClearDay'];
	$param['ymailAutoCheckTimer']   = $_REQUEST['ymailAutoCheckTimer'];

	$param['ymailUser'] = rij_crypt($_REQUEST['ymailUser'], $skey, $ivc);
	$param['ymailPass'] = rij_crypt($_REQUEST['ymailPass'], $skey, $ivc);

	if ($_REQUEST['ymailFolderList'] != '') {

		$param['ymailFolderList'] = json_decode(str_replace("\\", "", $_REQUEST['ymailFolderList']), true);

	}
	else {

		//-start--проверка получения почты
		if ($param['ymailInSecure'] != '') {
			$ymailInSecure = '/'.$param['ymailInSecure'].'/novalidate-cert';
		}
		else {
			$ymailInSecure = $param['ymailInSecure'];
		}

		$imap = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$ymailInSecure.'}';

		$mailbox = $imap.'INBOX';

		$conn  = imap_open($mailbox, $param['ymailUser'], $param['ymailPass']);
		$error = imap_last_error();

		//проверим список папок
		$box = imap_list($conn, $imap, "*");

		$folders = [];
		foreach ($box as $folder) {

			$param['ymailFolderList'][] = str_replace($imap, "", ImapUtf7 ::decode($folder));

		}

		imap_close($conn);
	}

	$param['newSignature'] = $ym_param['newSignature'];
	$param['reSignature']  = $ym_param['reSignature'];
	$param['fwSignature']  = $ym_param['fwSignature'];

	$settings = json_encode_cyr($param);

	if ($id > 0) {

		try {
			$db -> query("update {$sqlname}ymail_settings set settings = '".$settings."' WHERE iduser = '".$iduser."' and identity = '$identity'");
			print "Данные успешно сохранены";
		}
		catch (Exception $e) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();
		}

	}
	else {

		try {
			$db -> query("insert into  {$sqlname}ymail_settings (id,iduser,settings,identity) values (null,'".$iduser."','".$settings."','$identity')");
			print "Данные успешно сохранены";
		}
		catch (Exception $e) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();
		}

	}

	unlink($rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json");

	exit();
}
if ($action == 'account') {

	$result_ym = $db -> getRow("select * from {$sqlname}ymail_settings WHERE iduser = '".$iduser."' and identity = '".$identity."'");
	$ym_param  = $result_ym['settings'];
	$id        = $result_ym['id'];
	$param     = json_decode($ym_param, true);

	$param['ymailUser'] = rij_decrypt($param['ymailUser'], $skey, $ivc);
	$param['ymailPass'] = rij_decrypt($param['ymailPass'], $skey, $ivc);

	if ($param['ymailInProtocol'] == '') {
		$param['ymailInProtocol'] = 'IMAP';
	}
	if ($param['ymailOutProtocol'] == '') {
		$param['ymailOutProtocol'] = 'IMAP';
	}

	$file = file_get_contents($rootpath.'/cash/imap.json');
	$fc   = json_decode($file, true);

	if (!$param['ymailAutoSaveTimer'] || $param['ymailAutoSaveTimer'] < 1) {
		$param['ymailAutoSaveTimer'] = 0;
	}
	if ($param['ymailClearDay'] == '') {
		$param['ymailClearDay'] = 10;
	}
	if ($param['ymailAutoCheckTimer'] == '') {
		$param['ymailAutoCheckTimer'] = 10;
	}

	?>
	<div class="zagolovok">Настройка почтового ящика</div>
	<FORM action="/content/admin/<?php
	echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="account_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<INPUT type="hidden" name="iduser" id="iduser" value="<?= $iduser ?>">

		<div class="flex-container box--child" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">

			<div class="flex-string wp70" style="max-height: 70vh; width:69%; overflow-y: auto; overflow-x: hidden; float:left">

				<div class="row" data-id="account">

					<div class="infodiv wp100">Настройки для пользователя <b><?= current_user($iduser) ?></b></div>

					<div class="column12 grid-12">
						<div id="divider" align="center"><b>Авторизация</b></div>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Логин:</div>
					<div class="column12 grid-9">
						<input name="ymailUser" type="text" id="ymailUser" value="<?= $param['ymailUser'] ?>" class="required" style="width:97%">
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Пароль:</div>
					<div class="column12 grid-9 relativ">
						<input name="ymailPass" type="password" id="ymailPass" value="<?= $param['ymailPass'] ?>" class="required wp97" data-type="password">
						<div class="showpass mr10 mt5" id="showpass">
							<i class="icon-eye-off hand gray" title="Посмотреть пароль"></i>
						</div>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Email:</div>
					<div class="column12 grid-9">
						<input name="ymailFrom" type="text" id="ymailFrom" value="<?= $param['ymailFrom'] ?>" class="required" style="width:97%">
						<div class="em gray2 fs-09">Email д.б. зарегистрированным и знакомым сервису. В противном случае - сервер отклонит отправку почты.</div>
					</div>

				</div>

				<div class="row" data-id="server">

					<div class="column12 grid-3 gray2 text-right mt10 mb10">Авторизация:</div>
					<div class="column12 grid-9 pt7 mt10 mb10">
						<label class="inline pr10"><input name="ymailAuth" id="ymailAuth" value="SMTP" type="radio" <?php
							if ($param['ymailAuth'] == 'true') print "checked" ?>>Да</label>
						<label class="inline"><input name="ymailAuth" id="ymailAuth" value="IMAP" type="radio" <?php
							if ($param['ymailAuth'] == 'false') print "checked" ?>>Нет</label>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Загрузить для:</div>
					<div class="column12 grid-9">
						<SELECT name="serv" id="serv" onchange="getServ()" class="w120">
							<option value="">--выбрать--</option>
							<?php
							foreach ($fc as $key => $value) {
								print '<option value="'.$key.'">'.$key.'</option>';
							}
							?>
						</SELECT>&nbsp;Загрузить настройки для почты
					</div>

					<div id="divider"><b>Отправка почты</b></div>

					<div class="column12 grid-3 gray2 text-right pt10">Кодировка:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOutCharset" id="ymailOutCharset" class="w120">
							<option value="">UTF-8</option>
							<option value="windows-1251" <?php
							if ($param['ymailOutCharset'] == 'windows-1251') print "selected" ?>>WINDOWS-1251
							</option>
							<option value="koi8r" <?php
							if ($param['ymailOutCharset'] == 'koi8-r') print "selected" ?>>KOI8-R
							</option>
						</SELECT>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Адрес сервера:</div>
					<div class="column12 grid-9">
						<input name="ymailOutHost" type="text" id="ymailOutHost" value="<?= $param['ymailOutHost'] ?>" class="required wp97">
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Порт:</div>
					<div class="column12 grid-9">
						<input name="ymailOutPort" type="text" id="ymailOutPort" value="<?= $param['ymailOutPort'] ?>" class="required w120">
						<span class="em gray2 fs-09">Например 25, 465</span>
					</div>

					<div class="column12 grid-3 gray2 text-right mt10 mb10">Тип:</div>
					<div class="column12 grid-9 pt7 mt10 mb10">
						<label class="inline pr10"><input name="ymailOutProtocol" id="ymailOutProtocol" value="SMTP" type="radio" <?php
							if ($param['ymailOutProtocol'] == 'SMTP') print "checked" ?>>SMTP</label>
						<label class="inline"><input name="ymailOutProtocol" id="ymailOutProtocol" value="IMAP" type="radio" <?php
							if ($param['ymailOutProtocol'] == 'IMAP') print "checked" ?>>IMAP</label>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Шифрование:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOutSecure" id="ymailOutSecure" class="w120">
							<option value="">Без шифрования</option>
							<option value="tls" <?php
							if ($param['ymailOutSecure'] == 'tls') print "selected" ?>>TLS
							</option>
							<option value="ssl" <?php
							if ($param['ymailOutSecure'] == 'ssl') print "selected" ?>>SSL
							</option>
							<option value="starttls" <?php
							if ($param['ymailOutSecure'] == 'starttls') print "selected" ?>>STARTTLS
							</option>
						</SELECT>
						<div class="em gray2 fs-09">Например, для Яндекс.Почта, GMail - <b>SSL</b></div>
					</div>

					<div id="divider"><b>Получение почты</b></div>

					<div class="column12 grid-3 gray2 text-right pt10">Адрес сервера:</div>
					<div class="column12 grid-9">
						<input name="ymailInHost" type="text" id="ymailInHost" value="<?= $param['ymailInHost'] ?>" class="required wp97">
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Порт:</div>
					<div class="column12 grid-9">
						<input name="ymailInPort" type="text" id="ymailInPort" value="<?= $param['ymailInPort'] ?>" class="required w120">
						<span class="em gray2 fs-09">Например 143, 993</span>
					</div>

					<div class="column12 grid-3 gray2 text-right mt10 mb10">Тип:</div>
					<div class="column12 grid-9 pt7 mt10 mb10">
						<label class="inline pr10"><input name="ymailInProtocol" id="ymailInProtocol" value="POP3" type="radio" <?php
							if ($param['ymailInProtocol'] == 'POP3') print "checked" ?>>POP3</label>
						<label class="inline pr10"><input name="ymailInProtocol" id="ymailInProtocol" value="IMAP" type="radio" <?php
							if ($param['ymailInProtocol'] == 'IMAP') print "checked" ?>>IMAP</label>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Шифрование:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailInSecure" id="ymailInSecure" class="w120">
							<option value="">Без шифрования</option>
							<option value="tls" <?php
							if ($param['ymailInSecure'] == 'tls') print "selected" ?>>TLS
							</option>
							<option value="ssl" <?php
							if ($param['ymailInSecure'] == 'ssl') print "selected" ?>>SSL
							</option>
							<option value="starttls" <?php
							if ($param['ymailInSecure'] == 'starttls') print "selected" ?>>STARTTLS
							</option>
						</SELECT>
						<div class="em gray2 fs-09">Например, для Яндекс.Почта, GMail - <b>SSL</b></div>
					</div>

				</div>

				<div class="row" data-id="other">

					<div class="column12 grid-12">
						<div id="divider" align="center"><b>Обработка почты</b></div>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Чтение:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOnReadSeen" id="ymailOnReadSeen" class="wp97">
							<option value="false" <?php
							if ($param['ymailOnReadSeen'] == 'false') print "selected" ?>>Отмечать прочитанным только в CRM
							</option>
							<option value="true" <?php
							if ($param['ymailOnReadSeen'] == 'true') print "selected" ?>>Отмечать прочитанным в CRM и почтовом сервере
							</option>
						</SELECT>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Удаление:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOnDelete" id="ymailOnDelete" class="wp97">
							<option value="false" <?php
							if ($param['ymailOnDelete'] == 'false') print "selected" ?>>Удалять только из CRM
							</option>
							<option value="true" <?php
							if ($param['ymailOnDelete'] == 'true') print "selected" ?>>Удалять из CRM и почтового сервера
							</option>
						</SELECT>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Отправленные:</div>
					<div class="column12 grid-9">
						<?php
						$ymailFolderList = $param['ymailFolderList'];
						//$param['ymailFolderList'] = json_decode($param['ymailFolderList'],true);
						//print_r($param['ymailFolderList']);
						//print $param['ymailFolderSent'];
						?>
						<SELECT name="ymailFolderSent" id="ymailFolderSent" class="wp97">
							<?php
							foreach ($param['ymailFolderList'] as $key => $value) {

								$d = $value == $param['ymailFolderSent'] ? 'selsected = "selected"' : '';

								print '<option value="'.$value.'" '.$d.'>'.$value.'&nbsp;&nbsp;</option>';
							}

							if (!empty($param['ymailFolderList'])) {
								$yempty = "hidden";
								$yfull  = "";
							}
							else {
								$yempty = "";
								$yfull  = "hidden";
							}
							?>
						</SELECT>
						<?php
						//print json_encode_cyr($param['ymailFolderList']);
						//print $flist = str_replace('"','\"',str_replace(array("\\"), "", json_encode_cyr($param['ymailFolderList'])));
						$flist = str_replace(["\\"], "", json_encode_cyr($param['ymailFolderList']));
						//print $flist = str_replace("\\", $param['ymailFolderList']);
						?>
						<INPUT type="hidden" name="ymailFolderList" id="ymailFolderList" value="">
						<div class="yempty em gray2 fs-09 <?= $yempty ?>">
							<i class="icon-attention red"></i>&nbsp;Заполните все данные и нажмите кнопку "Проверить". Папки будут загружены во время проверки.
						</div>
						<div class="yfull em gray2 fs-09 blue <?= $yfull ?> paddtop5">
							<i class="icon-info-circled blue"></i>&nbsp;С этой папкой будет производится синхронизация отправленных писем.
						</div>
					</div>

					<div id="divider"><b>Автоматизация</b></div>

					<div class="column12 grid-12 pl40">

						<div class="mb20 Bold gray2 fs-11">Автоматически добавить в историю активностей:</div>

						<div class="pl40">
							<label class="block mb10"><input type="checkbox" name="ymailAddHistoryInbox" id="ymailAddHistoryInbox" value="true" <?php
								if ($param['ymailAddHistoryInbox'] == 'true') print "checked" ?>>Входящие от известных контактов</label>
							<label class="block mb10"><input type="checkbox" name="ymailAddHistorySended" id="ymailAddHistorySended" value="true" <?php
								if ($param['ymailAddHistorySended'] == 'true') print "checked" ?>>Исходящие, отправленные вне CRM известным контактам
								<div class="em gray2 fs-09 blue paddleft20">если письмо отправлено из внешней почтовой программы</div>
							</label>
							<label class="block mb10"><input type="checkbox" name="ymailAddHistoryDeal" id="ymailAddHistoryDeal" value="true" <?php
								if ($param['ymailAddHistoryDeal'] == 'true') print "checked" ?>>Письма, по сделкам с кодом "[D#ID]" в теме (см. рекомендации)
								<div class="em gray2 fs-09 blue paddleft20">если письмо отправлено из внешней почтовой программы</div>
							</label>

						</div>

					</div>

					<hr>

					<div class="column12 grid-3 gray2 text-right pt10">Автосохранение:</div>
					<div class="column12 grid-9">

						<input name="ymailAutoSaveTimer" type="number" id="ymailAutoSaveTimer" value="<?= $param['ymailAutoSaveTimer'] ?>" style="width:100px" min="0" step="1"> мин.
						<div class="em gray2 fs-09 blue">При написании письма сохранять черновик каждые X минут, 0 - выключено</div>

					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Автоочистка:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailClearDay" id="ymailClearDay">
							<?php
							if ($isCloud == false) { ?>
								<option value="0" <?php
								if ($param['ymailClearDay'] == '0') print "selected" ?>>выключено
								</option>
							<?php
							} ?>
							<option value="5" <?php
							if ($param['ymailClearDay'] == '5') print "selected" ?>>5
							</option>
							<option value="10" <?php
							if ($param['ymailClearDay'] == '10') print "selected" ?>>10
							</option>
							<option value="15" <?php
							if ($param['ymailClearDay'] == '15') print "selected" ?>>15
							</option>
						</SELECT>&nbsp;дней.
						<div class="em gray2 fs-09 blue">Автоматическое удаление неизвестных писем (не прикреплены к истории активностей), в т.ч. вложений, и писем из корзины</div>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Период проверки:</div>
					<div class="column12 grid-9">
						<input name="ymailAutoCheckTimer" type="number" id="ymailAutoCheckTimer" value="<?= $param['ymailAutoCheckTimer'] ?>" style="width:100px" min="1" step="1"/> мин.
						<div class="em gray2 fs-09 blue">Период автоматической проверки почты. Рекомендуем не менее 3 минут.</div>
					</div>

					<div class="column12 grid-12 gray2 text-right">

					</div>

				</div>

				<div class="h40"></div>

			</div>
			<div class="flex-string wp30 infodiv bgwhite">

				<div class="Bold blue margbot10">Рекомендации по Gmail:</div>
				<ol class="list">
					<li>Для отправки используйте SMTP</li>
					<li>Для отправленных выбирайте папку "[Gmail]/Отправленные"</li>
					<li><b class="green">Разрешите</b> Ненадежные приложения ( <a href="https://www.google.com/settings/u/1/security/lesssecureapps" target="_blank">Google</a> )
					</li>
					<li>Если у вас включена Двухфакторная авторизация, то настройте
						<a href="https://support.google.com/accounts/answer/185833?hl=ru" target="_blank">Пароли приложений</a></li>
				</ol>
				<hr>
				<div class="Bold blue margbot10">Рекомендации по Yandex:</div>
				<ul class="list">
					<li><b class="green">Разрешите Портальный пароль</b> в Настройки / Почтовые программы</li>
				</ul>
				<hr>
				<div class="Bold blue margbot10">Рекомендации по Mail.ru:</div>
				<ul class="list">
					<li><b class="green">Получите пароль приложения</b> в Все настройки / Безопасность / Пароли для внешних приложений</li>
				</ul>
				<hr>
				<div class="Bold blue margbot10">Общие рекомендации:</div>
				<ul class="list">
					<li>Сбор почты происходит только из папки Входящие, без учета подпапок</li>
					<li>Для привязки писем к сделкам используйте в теме код - "<b>[D#<b class="blue">ID</b>]</b>" (ID сделки)</li>
				</ul>

			</div>

		</div>

		<hr>

		<div id="rez" class="hidden">

			<div class="infodiv" style="max-height:60px; overflow:auto"></div>
			<hr>

		</div>
		<?php
		if (!extension_loaded("imap")) {

			print '<br><div class="warning"><i class="icon-attention red"></i>&nbsp;Требуемый модуль <u><b>IMAP</b></u> <b class="red">не подключен</b></div><br>';

		}
		else {
			?>
			<DIV class="button--pane text-right">

				<div class="pull-left">
					<a href="javascript:void(0)" onclick="checkConnection()" class="button orangebtn"><i class="icon-arrows-cw white"></i>Проверить</a>
				</div>

				<a href="javascript:void(0)" class="button" onclick="$('#set').trigger('submit')"><span>Сохранить</span></a>&nbsp;
				<a href="javascript:void(0)" onclick="DClose()" class="button"><span>Отмена</span></a>

			</DIV>
		<?php
		} ?>
	</FORM>
	<script>

		$('#dialog').css('width', '902px');

		$(function () {

			$('#ymailFolderList').val('<?=$flist?>');
			$('#ymailFolderSent [value="<?=$param['ymailFolderSent']?>"]').attr('selected', 'selected');

			$('#set').ajaxForm({
				beforeSubmit: function () {

					$('#message').empty().css('display', 'block').fadeTo(1, 1).append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					return true;

				},
				success: function (dataa) {

					DClose();
					razdel('mailer');

					$('#message').fadeTo(1, 1).css('display', 'block').html(dataa);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});

			$('#dialog').center();

		});

		function getServ(serv) {

			var obj;
			var server = $('#serv option:selected').val();

			$.post('/modules/mailer/core.mailer.php?action=get.servers&tip=outcome&server=' + server, function (data) {

				obj = JSON.parse(data);

				$('#ymailOutHost').val(obj.host);
				$('#ymailOutPort').val(obj.port);
				$('#ymailAuth [value=' + obj.auth + ']').attr("selected", "selected");
				$('#ymailOutSecure [value=' + obj.secure + ']').attr("selected", "selected");
				$('#ymailOutProtocol [value=' + obj.protocol + ']').attr("selected", "selected");

			});

			$.post('/modules/mailer/core.mailer.php?action=get.servers&tip=income&server=' + server, function (data) {

				obj = JSON.parse(data);

				$('#ymailInHost').val(obj.host);
				$('#ymailInPort').val(obj.port);
				$('#ymailInSecure [value=' + obj.secure + ']').attr("selected", "selected");
				$('#ymailInProtocol [value=' + obj.protocol + ']').attr("selected", "selected");

			});

		}

		function checkConnection() {

			var url = "/modules/mailer/core.mailer.php";
			var str = $('#set').serialize() + '&action=account.check';

			var $out = $('#message');
			var em = checkRequired();

			$out.empty();

			if (!em)
				return false;

			$('#noborder').animate({bottom: 0}, 500);
			$('#rez').removeClass('hidden');
			$('#rez .infodiv').html('<div id="check">Проверяю подключение к серверу..</div>');

			$.post(url, str + '&tip=in', function (data) {

				//var st = '<div><b>Проверка получения почты:</b>&nbsp;' + data.income +'</div>';

				$('#check').remove();
				$('#rez .infodiv').html('<div id="in"><b>Проверка чтения почты:</b>&nbsp;' + data.income + '</div>');

				var opt = '';
				var d = '';
				var l = '';

				for (var i in data.folder) {

					if (data.folder[i] === 'Отправленные' || data.folder[i] === 'Sent') d = 'selected';
					else d = '';

					opt = opt + '<option value="' + data.folder[i] + '" ' + d + '>' + data.folder[i] + '&nbsp;&nbsp;</option>';

				}

				var ymailFolderList = JSON.stringify(data.folder);

				$('#ymailFolderSent').empty().append(opt);
				$('#ymailFolderList').val(ymailFolderList);
				$('.yempty').addClass('hidden');
				$('.yfull').removeClass('hidden');

				$('#dialog').center();

			}, 'json')
				.done(function () {

					$('#in').append('<div id="check">Проверяю отправку..</div>');

					$.post(url, str + '&tip=out', function (data) {

						var st = '<div><b>Проверка отправки почты:</b>&nbsp;' + data.outcome + '</div>';

						$('#check').remove();
						$('#in').append(st);
						$('#dialog').center();

					}, 'json');

				});

		}
	</script>
	<?php
	exit();
}

if ($action == '') {
	?>
	<h2>&nbsp;Раздел: "Настройка почтовых ящиков пользователей"</h2>
	<?php

	$uC = getUserCatalogg();

	//print_r( $uC );
	?>

	<TABLE id="zebra">
		<THEAD class="hidden-iphone sticked--top">
		<TR class="th40">
			<TH class="w60"></TH>
			<TH class="w350"><b>Имя пользователя</b></TH>
			<TH class="w250"><b>Email</b></TH>
			<TH class="w60"></TH>
			<TH>Отдел</TH>
		</TR>
		</THEAD>
		<TBODY>
		<?php
		$strActive = $strBlocked = '';

		foreach ($uC as $user) {

			$margin = ( $user['level'] - 1 ) * 10;

			$img = ( $user['level'] > 0 ) ? '<div class="strelka"></div>&nbsp;' : '';
			$bg  = ( $user['secrty'] != 'yes' ) ? 'graybg' : '';
			$txt = ( $user['secrty'] != 'yes' ) ? '<i class="icon-lock red" title="Заблокирован"></i>' : '';

			if ($user['secrty'] == 'yes') {
				$strActive .= '
				<TR class="ha th45 '.$bg.'">
					<TD>
						<div class="gray2 Bold mr10">ID '.$user['id'].'</div>
					</TD>
					<TD>
						<DIV class="ellipsis fs-11" title="'.$user['title'].'">
							<A href="javascript:void(0)" onclick="viewUser(\''.$user['id'].'\');"><b>'.$user['title'].'</b></A>
						</DIV>
					</TD>
					<TD title="'.$user['ymailUser'].'">
						'.( $user['ymailUser'] != '' ? '<div class="ellipsis"><i class="icon-mail-alt blue"></i>&nbsp;'.$user['ymailUser'].'</div>' : '' ).'
					</TD>
					<TD class="text-center">
					
						<a href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?action=account&iduser='.$user['id'].'\');"><i class="icon-cog gray2"></i></a>
						
					</TD>
					<TD class="gray2">'.$user['otdel'].'</TD>
				</TR>';
			}

			else {
				$strBlocked .= '
				<TR class="ha th45 '.$bg.'">
					<TD>
						<div class="gray2 Bold mr10">ID '.$user['id'].'</div>
					</TD>
					<TD>
						<DIV class="ellipsis" title="'.$user['title'].'">
							<A href="javascript:void(0)" onclick="viewUser(\''.$user['id'].'\');">'.$txt.' <b>'.$user['title'].'</b></A>
						</DIV>
					</TD>
					<TD title="'.$user['ymailUser'].'">
						'.( $user['ymailUser'] != '' ? '<div class="ellipsis"><i class="icon-mail-alt blue"></i>&nbsp;'.$user['ymailUser'].'</div>' : '' ).'
					</TD>
					<TD class="text-center">
					
						<a href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?action=account&iduser='.$user['id'].'\');"><i class="icon-cog gray2"></i></a>
						
					</TD>
					<TD class="gray2">'.$user['otdel'].'</TD>
				</TR>';
			}

		}

		print $strActive;
		print $strBlocked;
		?>
		</tbody>
	</table>

	<div style="height: 60px;"></div>
	<?php
}
?>
<script>

	$('#dialog').css('width', '502px');
	$(".multiselect").multiselect({sortable: true, searchable: true});

	$(function () {

		$(".connected-list").css('height', "150px");

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (!em)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$("#contentdiv").load('/content/admin/<?php echo $thisfile; ?>');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});

		$('#dialog').center();

	});

	function getUsersList() {

		var str = $('#uform').serialize();

		$('#contentdiv').empty().load("/content/admin/<?php echo $thisfile; ?>?" + str).append('<div id="loader" class="loader"><img src=/assets/images/loader.gif> Вычисление...</div>');

	}
</script>