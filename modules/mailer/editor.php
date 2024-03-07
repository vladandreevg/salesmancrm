<?php
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Mailer;

set_time_limit( 0 );
error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

//проверяем папку для загрузки и если нет, то создаем
createDir( $rootpath.'/files/'.$fpath.'ymail' );

//проверяем папку для загрузки и если нет, то создаем
createDir( $rootpath.'/files/'.$fpath.'ymail/inbody' );

$action = $_REQUEST['action'];

$ymailSet = $db -> getOne( "select settings from ".$sqlname."ymail_settings WHERE iduser = '$iduser1' and identity = '$identity'" );
$ymailSet = json_decode( (string)$ymailSet, true );

//print_r($ym_param);

// key: AIzaSyDYFM7-ZRx18LbiZcmGnj8_CRDN__GjJFw
// id_app: 3137464671-ps0see5m7gtikms9sqcphvq08814r20o.apps.googleusercontent.com

/**
 * Настройки почтового ящика
 */
if ( $action == 'account' ) {

	$result_ym = $db -> getRow( "SELECT * FROM ".$sqlname."ymail_settings WHERE iduser = '$iduser1' AND identity = '$identity'" );
	$ym_param  = $result_ym['settings'];
	$id        = (int)$result_ym['id'];
	$param     = json_decode( (string)$ym_param, true );

	$param['ymailUser'] = rij_decrypt( $param['ymailUser'], $skey, $ivc );
	$param['ymailPass'] = rij_decrypt( $param['ymailPass'], $skey, $ivc );

	if ( $param['ymailInProtocol'] == '' ) {
		$param['ymailInProtocol'] = 'IMAP';
	}
	if ( $param['ymailOutProtocol'] == '' ) {
		$param['ymailOutProtocol'] = 'IMAP';
	}

	$file = file_get_contents( $rootpath.'/cash/imap.json' );
	$fc   = json_decode( $file, true );

	if ( !$param['ymailAutoSaveTimer'] ) {
		$param['ymailAutoSaveTimer'] = 0;
	}
	if ( $param['ymailClearDay'] == '' ) {
		$param['ymailClearDay'] = 10;
	}
	if ( $param['ymailAutoCheckTimer'] == '' ) {
		$param['ymailAutoCheckTimer'] = 10;
	}
	
	//print_r($param);

	?>
	<div class="zagolovok">Настройка почтового ящика</div>

	<FORM action="/modules/mailer/core.mailer.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="account.on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div class="flex-container box--child" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">

			<div class="flex-string wp60" style="max-height: 70vh; width:69%; overflow-y: auto; overflow-x: hidden; float:left">

				<div class="row" data-id="account">

					<div class="column12 grid-12">
						<div id="divider"><b>Авторизация</b></div>
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
						<label class="inline pr10">
							<input name="ymailAuth" id="ymailAuth" value="true" type="radio" <?php if ( $param['ymailAuth'] == 'true' ) print "checked" ?>>Да
						</label>
						<label class="inline">
							<input name="ymailAuth" id="ymailAuth" value="false" type="radio" <?php if ( $param['ymailAuth'] == 'false' ) print "checked" ?>>Нет
						</label>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Загрузить для:</div>
					<div class="column12 grid-9">
						<SELECT name="serv" id="serv" onchange="getServ()" class="w120">
							<option value="">--выбрать--</option>
							<?php
							foreach ( $fc as $key => $value ) {
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
							<option value="windows-1251" <?php if ( $param['ymailOutCharset'] == 'windows-1251' )
								print "selected" ?>>WINDOWS-1251
							</option>
							<option value="koi8r" <?php if ( $param['ymailOutCharset'] == 'koi8-r' )
								print "selected" ?>>KOI8-R
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
						<label class="inline pr10"><input name="ymailOutProtocol" id="ymailOutProtocol" value="SMTP" type="radio" <?php if ( $param['ymailOutProtocol'] == 'SMTP' )
								print "checked" ?>>SMTP</label>
						<label class="inline"><input name="ymailOutProtocol" id="ymailOutProtocol" value="IMAP" type="radio" <?php if ( $param['ymailOutProtocol'] == 'IMAP' )
								print "checked" ?>>IMAP</label>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Шифрование:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOutSecure" id="ymailOutSecure" class="w120">
							<option value="">Без шифрования</option>
							<option value="tls" <?php if ( $param['ymailOutSecure'] == 'tls' )
								print "selected" ?>>TLS
							</option>
							<option value="ssl" <?php if ( $param['ymailOutSecure'] == 'ssl' )
								print "selected" ?>>SSL
							</option>
							<option value="starttls" <?php if ( $param['ymailOutSecure'] == 'starttls' )
								print "selected" ?>>STARTTLS
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
						<label class="inline pr10"><input name="ymailInProtocol" id="ymailInProtocol" value="POP3" type="radio" <?php if ( $param['ymailInProtocol'] == 'POP3' )
								print "checked" ?>>POP3</label>
						<label class="inline pr10"><input name="ymailInProtocol" id="ymailInProtocol" value="IMAP" type="radio" <?php if ( $param['ymailInProtocol'] == 'IMAP' )
								print "checked" ?>>IMAP</label>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Шифрование:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailInSecure" id="ymailInSecure" class="w120">
							<option value="">Без шифрования</option>
							<option value="tls" <?php if ( $param['ymailInSecure'] == 'tls' )
								print "selected" ?>>TLS
							</option>
							<option value="ssl" <?php if ( $param['ymailInSecure'] == 'ssl' )
								print "selected" ?>>SSL
							</option>
							<option value="starttls" <?php if ( $param['ymailInSecure'] == 'starttls' )
								print "selected" ?>>STARTTLS
							</option>
						</SELECT>
						<div class="em gray2 fs-09">Например, для Яндекс.Почта, GMail - <b>SSL</b></div>
					</div>

				</div>

				<div class="row" data-id="other">

					<div class="column12 grid-12">
						<div id="divider"><b>Обработка почты</b></div>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Чтение:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOnReadSeen" id="ymailOnReadSeen" class="wp97">
							<option value="false" <?php if ( !(bool)$param['ymailOnReadSeen'] )
								print "selected" ?>>Отмечать прочитанным только в CRM
							</option>
							<option value="true" <?php if ( (bool)$param['ymailOnReadSeen'] )
								print "selected" ?>>Отмечать прочитанным в CRM и почтовом сервере
							</option>
						</SELECT>
					</div>

					<div class="column12 grid-3 gray2 text-right pt10">Удаление:</div>
					<div class="column12 grid-9">
						<SELECT name="ymailOnDelete" id="ymailOnDelete" class="wp97">
							<option value="false" <?php if ( $param['ymailOnDelete'] == 'false' )
								print "selected" ?>>Удалять только из CRM
							</option>
							<option value="true" <?php if ( $param['ymailOnDelete'] == 'true' )
								print "selected" ?>>Удалять из CRM и почтового сервера
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
							foreach ( $param['ymailFolderList'] as $key => $value ) {

								if ( $value == $param['ymailFolderSent'] )
									$d = 'selsected = "selected"';
								else $d = '';

								print '<option value="'.$value.'" '.$d.'>'.$value.'&nbsp;&nbsp;</option>';
							}

							if ( !empty( $param['ymailFolderList'] ) ) {
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
						$flist = str_replace( ["\\"], "", json_encode_cyr( $param['ymailFolderList'] ) );
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
							<label class="block mb10"><input type="checkbox" name="ymailAddHistoryInbox" id="ymailAddHistoryInbox" value="true" <?php if ( $param['ymailAddHistoryInbox'] == 'true' )
									print "checked" ?>>Входящие от известных контактов</label>
							<label class="block mb10"><input type="checkbox" name="ymailAddHistorySended" id="ymailAddHistorySended" value="true" <?php if ( $param['ymailAddHistorySended'] == 'true' )
									print "checked" ?>>Исходящие, отправленные вне CRM известным контактам
								<span class="block em gray2 fs-09 blue paddleft20">если письмо отправлено из внешней почтовой программы</span>
							</label>
							<label class="block mb10"><input type="checkbox" name="ymailAddHistoryDeal" id="ymailAddHistoryDeal" value="true" <?php if ( $param['ymailAddHistoryDeal'] == 'true' )
									print "checked" ?>>Письма, по сделкам с кодом "[D#ID]" в теме (см. рекомендации)
								<span class="block em gray2 fs-09 blue paddleft20">если письмо отправлено из внешней почтовой программы</span>
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
							<?php if ( !$isCloud ) { ?>
								<option value="0" <?php if ( $param['ymailClearDay'] == '0' )
									print "selected" ?>>выключено
								</option>
							<?php } ?>
							<option value="5" <?php if ( $param['ymailClearDay'] == '5' )
								print "selected" ?>>5
							</option>
							<option value="10" <?php if ( $param['ymailClearDay'] == '10' )
								print "selected" ?>>10
							</option>
							<option value="15" <?php if ( $param['ymailClearDay'] == '15' )
								print "selected" ?>>15
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
			<div class="flex-string wp40 infodiv bgwhite">

				<div class="Bold blue margbot10">Рекомендации по Gmail:</div>
				<ol class="list">
					<li>Для отправки используйте SMTP</li>
					<li>Для отправленных выбирайте папку "[Gmail]/Отправленные"</li>
					<li><b class="green">Разрешите</b> Ненадежные приложения (
						<a href="https://www.google.com/settings/u/1/security/lesssecureapps" target="_blank">Google</a> )
					</li>
					<li>Если у вас включена Двухфакторная авторизация, то настройте
						<a href="https://support.google.com/accounts/answer/185833?hl=ru" target="_blank">Пароли приложений</a>
					</li>
				</ol>
				<hr>
				<div class="Bold blue margbot10">Рекомендации по Yandex:</div>
				<ul class="list">
					<li><b class="green">Разрешите Портальный пароль</b> в Настройки / Почтовые программы</li>
				</ul>
				<hr>
				<div class="Bold blue margbot10">Рекомендации по Mail.ru:</div>
				<ul class="list">
					<li>
						<b class="green">Получите пароль приложения</b> в Все настройки / Безопасность / Пароли для внешних приложений
					</li>
				</ul>
				<hr>
				<div class="Bold blue margbot10">Общие рекомендации:</div>
				<ul class="list">
					<li>Сбор почты происходит только из папки Входящие, без учета подпапок</li>
					<li>Для привязки писем к сделкам используйте в теме код - "<b>[D#<b class="blue">ID</b>]</b>" (ID сделки)
					</li>
				</ul>

			</div>

		</div>

		<hr>

		<div id="rez" class="hidden">

			<div class="infodiv" style="max-height:60vh; overflow:auto"></div>
			<hr>

		</div>
		<?php
		if ( !extension_loaded( "imap" ) ) {
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
		}
		?>
	</FORM>
	<script>

		$(function () {

			$('#dialog').css('width', '900px');

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

					$('#message').fadeTo(1, 1).css('display', 'block').html(dataa);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});

			$('#dialog').center();

			ShowModal.fire({
				etype: 'ymailerForm',
				action: $('#action').val()
			});

		});

		function getServ() {

			var obj;
			var server = $('#serv option:selected').val();

			$.post('modules/mailer/core.mailer.php?action=get.servers&tip=outcome&server=' + server, function (data) {

				obj = JSON.parse(data);

				$('#ymailOutHost').val(obj.host);
				$('#ymailOutPort').val(obj.port);
				$('#ymailAuth [value=' + obj.auth + ']').attr("selected", "selected");
				$('#ymailOutSecure [value=' + obj.secure + ']').attr("selected", "selected");
				$('#ymailOutProtocol [value=' + obj.protocol + ']').attr("selected", "selected");

			});
			$.post('modules/mailer/core.mailer.php?action=get.servers&tip=income&server=' + server, function (data) {

				obj = JSON.parse(data);

				$('#ymailInHost').val(obj.host);
				$('#ymailInPort').val(obj.port);
				$('#ymailInSecure [value=' + obj.secure + ']').attr("selected", "selected");
				$('#ymailInProtocol [value=' + obj.protocol + ']').attr("selected", "selected");

			});

		}

		function checkConnection() {

			var url = "modules/mailer/core.mailer.php";
			var str = $('#set').serialize() + '&action=account.check';
			var em = checkRequired();

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

/**
 * Настройка автоподписей
 */
if ( $action == 'signature' ) {

	$newSignature = htmlspecialchars_decode( $ym_param['newSignature'] );
	$reSignature  = htmlspecialchars_decode( $ym_param['reSignature'] );
	$fwSignature  = htmlspecialchars_decode( $ym_param['fwSignature'] );
	?>
	<FORM action="/modules/mailer/core.mailer.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="signature.on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<div class="zagolovok">Настройка автоподписей</div>

		<DIV id="more" class="ftabs" data-id="ycontainer" style="border:0; background: none;">

			<div id="ycontainer" class="fcontainer p0 m0 mt5">

				<div class="one cbox" style="overflow:hidden; height: 40vh">

					<textarea name="newSignature" id="newSignature" style="width:100%; height: 99%;"><?= $newSignature ?></textarea>

				</div>
				<div class="two cbox" style="overflow:hidden; height: 40vh">

					<textarea name="reSignature" id="reSignature" style="width:100%; height: 99%;"><?= $reSignature ?></textarea>

				</div>
				<div class="three cbox" style="overflow:hidden; height: 40vh">

					<textarea name="fwSignature" id="fwSignature" style="width:100%; height: 99%;"><?= $fwSignature ?></textarea>

				</div>

			</div>
			<div id="ytabs">

				<ul class="gray flex-container blue">

					<li class="flex-string" data-link="one"><i class="icon-pencil"></i>Новое сообщение</li>
					<li class="flex-string" data-link="two"><i class="icon-reply"></i>Ответ</li>
					<li class="flex-string" data-link="three"><i class="icon-forward-1"></i>Переадресация</li>

				</ul>

			</div>

		</DIV>

		<hr>

		<DIV class="button--pane text-right">

			<a href="javascript:void(0)" class="button" onclick="saveForm()">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</DIV>

	</FORM>
	<script>

		var h = $('.cbox').actual('height');
		var h2 = h - 80;
		var editor1 = CKEDITOR.replace('newSignature', {
			height: h2 + "px",
			width: '100%',
			extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget',
			filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=ymail',
			filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=ymail',
			toolbar:
				[
					['Bold', 'Italic', 'Underline', 'Strike', 'TextColor', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
					['Outdent', 'Indent', '-', 'Blockquote'], ['Font', 'FontSize'],
					/*['Format','FontSize'],['JustifyLeft','JustifyCenter','JustifyRight'],*/
					['-'/*,'PasteText','PasteFromWord'*/, 'Image'/*,'HorizontalRule'*/], ['-', 'Undo', 'Redo', 'RemoveFormat'/*,'SelectAll'*/], ['Source']
				]
		});
		var editor2 = CKEDITOR.replace('reSignature', {
			height: h2 + "px",
			width: '100%',
			extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget',
			filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=ymail',
			filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=ymail',
			toolbar:
				[
					['Bold', 'Italic', 'Underline', 'Strike', 'TextColor', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
					['Outdent', 'Indent', '-', 'Blockquote'], ['Font', 'FontSize'],
					/*['Format','FontSize'],['JustifyLeft','JustifyCenter','JustifyRight'],*/
					['-'/*,'PasteText','PasteFromWord'*/, 'Image'/*,'HorizontalRule'*/], ['-', 'Undo', 'Redo', 'RemoveFormat'/*,'SelectAll'*/], ['Source']
				]
		});
		var editor3 = CKEDITOR.replace('fwSignature', {
			height: h2 + "px",
			width: '100%',
			extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget',
			filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=ymail',
			filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=ymail',
			toolbar:
				[
					['Bold', 'Italic', 'Underline', 'Strike', 'TextColor', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
					['Outdent', 'Indent', '-', 'Blockquote'], ['Font', 'FontSize'],
					/*['Format','FontSize'],['JustifyLeft','JustifyCenter','JustifyRight'],*/
					['-'/*,'PasteText','PasteFromWord'*/, 'Image'/*,'HorizontalRule'*/], ['-', 'Undo', 'Redo', 'RemoveFormat'/*,'SelectAll'*/], ['Source']
				]
		});

		CKEDITOR.on("instanceReady", function () {

			$('.cke_bottom').addClass('hidden');

			var vh = $('#ycontainer').actual('height') - $('.cke_top').actual('height') - 10;

			$('.cke_contents').height(vh + 'px');
			$('#dialog').center();

		});

		$(function () {

			$('#dialog').css('width', '802px').center();

			ShowModal.fire({
				etype: 'ymailerForm',
				action: $('#action').val()
			});

		});

		$('.ftabs').each(function () {

			$(this).find('li').removeClass('active');
			$(this).find('li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		$('#ytabs').on('click', 'li', function () {

			var link = $(this).data('link');
			var id = $(this).closest('.ftabs').attr('id');

			$('#' + id + ' li').removeClass('active');
			$(this).addClass('active');

			$('#' + id + ' .cbox').addClass('hidden');
			$('#' + id + ' .' + link).removeClass('hidden');

		});

		function saveForm() {

			CKEDITOR.instances['newSignature'].updateElement();
			CKEDITOR.instances['reSignature'].updateElement();
			CKEDITOR.instances['fwSignature'].updateElement();

			$('#set').trigger('submit');

		}

		$('#set').ajaxForm({
			beforeSubmit: function () {

				$('#message').empty().css('display', 'block').fadeTo(1, 1).append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				if (editor1) {
					var html1 = $('#cke_editor_newSignature').html();
					$('#dialog #newSignature').val(html1);
					editor1.destroy();
					editor1 = null;
				}
				if (editor2) {
					var html2 = $('#cke_editor_reSignature').html();
					$('#dialog #reSignature').val(html2);
					editor2.destroy();
					editor2 = null;
				}
				if (editor3) {
					var html3 = $('#cke_editor_fwSignature').html();
					$('#dialog #fwSignature').val(html3);
					editor3.destroy();
					editor3 = null;
				}

				return true;

			},
			success: function (dataa) {

				DClose();

				$('#message').fadeTo(1, 1).css('display', 'block').html(dataa);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}
		});

	</script>
	<?php
}

/**
 * Привязка сообщения к Сделке
 */
if ( $action == 'todeal' ) {
	?>
	<div class="zagolovok">Привязать к сделке</div>
	<form action="/modules/mailer/core.mailer.php" method="post" enctype="multipart/form-data" name="eForm" id="eForm">
		<input name="action" id="action" type="hidden" value="todeal.do">
		<input name="id" id="id" type="hidden" value="<?= $_REQUEST['id'] ?>">

		<div class="deal div-info">
			<span class="pt10 pr10 pull-left"><i class="icon-briefcase-1 blue"></i></span>
			<span class="relativ1 cleared1">
				<INPUT name="did" type="hidden" id="did" value="<?= $did ?>">
				<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= current_dogovor( $did ) ?>" style="width: 90.0%;">
				<span class="idel clearinputs pt10" title="Очистить"><i class="icon-block-1 red"></i></span>
			</span>
		</div>

		<hr>

		<div class="em fs-09 div-center">Поиск возможен по ID, названию или UID сделки, а также по названию Клиента. Требуется выбор из найденных.</div>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#eForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<script>
		$(function () {

			$('#eForm').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					if ($display === 'mailer')
						configmpage();

					if (isCard)
						settab('mail');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});

			$("#dtitle").autocomplete("content/helpers/deal.helpers.php?action=doglist", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 1,
				formatItem: function (data, i, n, value) {
					return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			})
				.result(function (value, data) {
					$('#did').val(data[1]);
				});

		});
	</script>
	<?php
	exit();
}

/**
 * Привязка сообщения к Контакту
 */
if ( $action == 'tocontact' ) {
	?>
	<div class="zagolovok">Привязать к Контакту</div>
	<form action="/modules/mailer/core.mailer.php" method="post" enctype="multipart/form-data" name="eForm" id="eForm">
		<input name="action" id="action" type="hidden" value="tocontact.do">
		<input name="id" id="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
		<input name="email" id="email" type="hidden" value="<?= $_REQUEST['email'] ?>">

		<table>
			<tr>
				<td>
					<div class="deal div-info">
						<span class="pt10 pr10 pull-left"><i class="icon-briefcase-1 blue"></i></span>
						<span class="relativ1 cleared1">
					<INPUT name="pid" type="hidden" id="pid" value="<?= $pid ?>">
					<INPUT name="person" id="person" type="text" placeholder="Выбор контакта" value="<?= current_person( $pid ) ?>" style="width: 90.0%;">
					<span class="idel clearinputs pt10" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>
					</div>
					<div class="em fs-09 gray2 div-center">Поиск возможен по имени Контакта. Требуется выбор из найденных.</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="checkbox mt5 ml20">
						<label>
							<input name="mperson" id="mperson" type="checkbox" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Сделать основным email
						</label>
					</div>
				</td>
			</tr>
		</table>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#eForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<script>

		$(function () {

			$('#eForm').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					var em = 0;

					$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
					$(".required").each(function () {

						if ($(this).val() === '') {
							$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
							em = em + 1;
						}

					});

					$out.empty();

					if (em > 0) {

						alert("Не заполнены обязательные поля\n\rОни выделены цветом");
						return false;

					}
					if (em === 0) {
						$('#dialog').css('display', 'none');
						$('#dialog_container').css('display', 'none');
						$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
						return true;
					}
				},
				success: function (data) {

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					configmpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});

			$("#person").autocomplete("content/helpers/client.helpers.php?action=contactlist", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 1,
				formatItem: function (data, i, n, value) {
					return '<div class="relativ">' + data[0] + '&nbsp;<div class="pull-aright">[<span class="broun">' + data[2] + '</span>]</div><br><div class="blue smalltxt">' + data[3] + '</div></div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			})
				.result(function (value, data) {
					$('#pid').val(data[1]);
				});

		});

	</script>
	<?php
	exit();
}

/**
 * Привязка сообщения к Клиенту
 */
if ( $action == 'toclient' ) {
	?>
	<div class="zagolovok">Привязать к Клиенту</div>
	<form action="/modules/mailer/core.mailer.php" method="post" enctype="multipart/form-data" name="eForm" id="eForm">
		<input name="action" id="action" type="hidden" value="toclient.do">
		<input name="id" id="id" type="hidden" value="<?= $_REQUEST['id'] ?>">
		<input name="email" id="email" type="hidden" value="<?= $_REQUEST['email'] ?>">

		<table>
			<tr>
				<td>
					<div class="deal div-info">
						<span class="pt10 pr10 pull-left"><i class="icon-briefcase-1 blue"></i></span>
						<span class="relativ1 cleared1">
					<INPUT name="clid" type="hidden" id="clid" value="<?= $clid ?>">
					<INPUT name="title" id="title" type="text" placeholder="Выбор клиента" value="<?= current_client( $clid ) ?>" style="width: 90.0%;">
					<span class="idel clearinputs pt10" title="Очистить"><i class="icon-block-1 red"></i></span>
				</span>
					</div>
					<div class="em fs-09 gray2 div-center">Поиск возможен по имени Контакта. Требуется выбор из найденных.</div>
				</td>
			</tr>
			<tr>
				<td>
					<div class="checkbox mt5 ml20">
						<label>
							<input name="mperson" id="mperson" type="checkbox" value="yes"/>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Сделать основным email
						</label>
					</div>
				</td>
			</tr>
		</table>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#eForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<script>

		$(function () {

			$('#eForm').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					var em = 0;

					$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
					$(".required").each(function () {

						if ($(this).val() === '') {
							$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
							em = em + 1;
						}

					});

					$out.empty();

					if (em > 0) {

						alert("Не заполнены обязательные поля\n\rОни выделены цветом");
						return false;

					}
					if (em === 0) {
						$('#dialog').css('display', 'none');
						$('#dialog_container').css('display', 'none');
						$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
						return true;
					}
				},
				success: function (data) {

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					configmpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});

			$("#title").autocomplete("content/helpers/client.helpers.php?action=clientlist", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, i, n, value) {
					return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			})
				.result(function (value, data) {
					$('#clid').val(data[1]);
				});

		});

	</script>
	<?php
	exit();
}

/**
 * Форма составления сообщения
 */
if ( $action == 'compose' ) {

	if ( !$GLOBALS['ymEnable'] || $ymailSet['ymailUser'] == '' ) {

		print '
		<DIV class="zagolovok"><B>Предупреждение!</B></DIV>
		<div class="warning">Внимание! Сначала настройте Почтовик</div>
		<hr>
		<div class="div-center">
			<A href="javascript:void(0)" onclick="doLoad(\'modules/mailer/editor.php?action=account\')" class="button"><i class="icon-cog-alt"></i>&nbsp;Настройка</A>
		</div>
		';

		exit();

	}

	$id        = (int)$_REQUEST['id'];
	$way       = $_REQUEST['way'];
	$priority  = $_REQUEST['priority'];
	$parentmid = 0;
	$exit      = false;
	$to        = [];

	$msg = [];

	// составляем на основе существующего письма ( из черновика, ответ, пересылка )
	if ( $id > 0 ) {

		$mail       = new Mailer();
		$mail -> id = $id;
		$mail -> mailInfo();

		$msg = $mail -> Message;

		//исходящее, редактирование
		if ( $way == '' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['newSignature'] ) );

			$to        = $msg['to'];
			$msg['id'] = $id;
			//$msg[ 'content' ] = removeChild( getHtmlBody( htmlspecialchars_decode( $msg[ 'html' ] ) ), ['index' => 0] );

			//if($msg['content'] == '' && $msg['html'] != '')
			$msg['content'] = htmlspecialchars_decode( $msg['html'] );

		}

		//исходящее новое
		elseif ( $way == 'new' ) {

			$msg['id'] = 0;
			//$msg[ 'content' ] = removeChild( getHtmlBody( htmlspecialchars_decode( $msg[ 'html' ] ) ), ['index' => 0] );

			//if($msg['content'] == '' && $msg['html'] != '')
			$msg['content'] = htmlspecialchars_decode( $msg['html'] );
			$msg['files']   = [];

		}

		//ответ на сообщение
		elseif ( $way == 're' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['reSignature'] ) );

			$theme = "Re: ".str_replace( [
					"Re:",
					"Fwd:"
				], "", $msg['subject'] );

			$res         = $db -> getRow( "SELECT clid, pid FROM ".$sqlname."ymail_messagesrec WHERE mid = '$id' and identity = '$identity'" );
			$msg['clid'] = (int)$res['clid'];
			$msg['pid']  = (int)$res['pid'];

			$to = [
				"name"  => $msg['fromname'],
				"email" => $msg['from']
			];

			$append = '<div style="font-family: monospace; font-size:12px">';
			$append .= 'Дата: '.$msg['date'].'<br>';
			$append .= $msg['fromname'].' пишет: <br>';
			$append .= '</div><br>';

			$content = removeChild( getHtmlBody( htmlspecialchars_decode( $msg['html'] ) ), ['index' => 0] );

			$content = "<br>".$Signature.'<br><br><hr>'.$append.'<blockquote style="margin-left: 5px;padding-left:10px;border-left: 2px solid #0000ff;">'.$content.'</blockquote>';

			$msg['id']        = 0;
			$msg['subject']   = $theme;
			$msg['content']   = $content;
			$msg['parentmid'] = (int)$id;
			$msg['attach']    = [];
			$msg['files']     = [];


		}

		//пересылка сообщения
		elseif ( $way == 'fwd' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['fwSignature'] ) );

			$did = '';

			$append = '<div style="font-family: monospace; font-size:12px">';
			$append .= '------ Перенаправленное сообщение --------<br>';
			$append .= 'От: '.$msg['fromname'].' ['.$msg['from'].']<br>';
			$append .= 'Дата: '.$msg['date'].'<br>';
			$append .= 'Тема: '.$msg['subject'].'<br>';
			$append .= 'Кому: '.$msg['to'][0]['name'].' ['.$msg['to'][0]['email'].']<br>';
			$append .= '</div><br>';

			$content = getHtmlBody( $msg['html'] );

			$theme   = "Fwd: ".str_replace( [
					"Re",
					"Fwd"
				], "", $msg['subject'] );
			$content = "<br>".$Signature.'<br><br><hr>'.$append.'<blockquote style="margin-left: 5px;padding-left:10px;border-left: 2px solid #0000ff;">'.$content.'</blockquote>';

			$msg['subject'] = $theme;
			$msg['content'] = $content;
			$msg['id']      = 0;
			//$msg['attach']  = [];

		}

	}

	// составляем абсолютно новое сообщение
	else {

		// эти параметры могут прийти из карточки
		$pid   = (int)$_REQUEST['pid'];
		$clid  = (int)$_REQUEST['clid'];
		$email = $_REQUEST['email'];

		if ( $email == '' ) {

			if ( $pid > 0 || $clid > 0 ) {

				$to = [];

				if ( $pid > 0 ) {

					$res = $db -> getOne( "SELECT mail FROM ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity'" );
					$too = yexplode( ",", str_replace( ";", ",", $res ), 0 );

					$toname = current_person( $pid );

					$to[] = [
						"email" => $too,
						"name"  => current_person( $pid ),
						"pid"   => $pid
					];

				}
				if ( $too == '' && $clid > 0 ) {

					$res = $db -> getOne( "SELECT mail_url FROM ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
					$too = yexplode( ",", str_replace( ";", ",", $res ), 0 );

					$toname = current_client( $clid );

					$to[] = [
						"email" => $too,
						"name"  => current_client( $clid ),
						"clid"  => $clid
					];

				}

			}

		}
		else {

			$pid = (int)$db -> getOne( "SELECT pid FROM ".$sqlname."personcat WHERE mail LIKE '%$email%' and identity = '$identity'" );

			if ( $clid > 0 ) {
				$toname = current_client( $clid );
			}

			if ( $pid > 0 ) {
				$toname = current_person( $pid );
			}

			$to[] = [
				"email" => $email,
				"name"  => $toname,
				"clid"  => $clid,
				"pid"   => $pid
			];

		}

		//print_r($to);

		$exit = empty( $to ) && ($clid > 0 || $pid > 0);

		$msg['content'] = link_it( htmlspecialchars_decode( $ym_param['newSignature'] ) );

		$msg['pid']  = $pid;
		$msg['clid'] = $clid;

	}

	$msg['to'] = $to;

	//шаблоны
	$msg['tpl'] = [];
	$result     = $db -> query( "SELECT * FROM ".$sqlname."ymail_tpl WHERE (iduser = '$iduser1' or share = 'yes') and identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {

		$msg['tpl'][] = [
			"id"    => (int)$data['id'],
			"name"  => $data['name'],
			"share" => $data['share'] == "yes" ? true : NULL
		];

	}

	$maxupload = ($maxupload == '') ? str_replace( [
		'M',
		'm'
	], '', @ini_get( 'upload_max_filesize' ) ) : $maxupload;

	//$postMaxSize = FileSize2MBytes(@ini_get('post_max_filesize'));

	//$maxupload = ($maxupload < $postMaxSize) ? $postMaxSize : $maxupload;

	include $rootpath."/content/ajax/check_disk.php";

	$msg['diskLimit'] = ($diskLimit == 0 || $diskUsage['percent'] < 100) ? true : NULL;
	$msg['maxupload'] = $maxupload;
	$msg['priority']  = $msg['priority'] ?? 3;
	$msg['exit']      = $exit;

	$template = file_get_contents( "tpl/compose.mustache" );

	$m       = new Mustache_Engine();
	$message = $m -> render( $template, $msg );

	?>
	<div id="msg"><?= $message ?></div>
	<script>

		yremEditor();

		var yeditor;
		var isDraft = false;
		var ymailAutoSaveTimer = parseInt('<?=$ym_param['ymailAutoSaveTimer']?>');
		var priority = '<?=$msg['priority']?>';
		var maxSize = '<?=$maxupload?>';

		/*tooltips*/
		$('#dialog .tooltips').append("<span></span>");
		$('#dialog .tooltips span').css({"width": "200px", "top": "60px"});
		$('#dialog .tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
		$("#dialog .tooltips").on('mouseenter', function () {
			$(this).find('span').empty().append($(this).attr('tooltip'));
		});
		/*tooltips*/

		var dw = '95%';
		var dh = '95vh';

		if ($(window).width() > 1500) {

			dw = '1200px';
			dh = '80vh';

		}

		$('#dialog').css({'height': dh, 'width': dw});

		var dha = $('#dialog').actual('outerHeight');
		var mesSubLeft = dha - 34 - 80 - 80;
		var mesSubRight = dha - 34 - 80;
		var editorHeight = dha - $('.bottom').actual('outerHeight') - $('.zagolovok').actual('outerHeight') - $('#aTbl').actual('outerHeight') - 70;

		$('#mesSubLeft').css({'height': mesSubLeft + 'px', 'max-height': mesSubLeft + 'px'});
		$('#mesRight').css({'height': mesSubRight + 'px', 'max-height': mesSubRight + 'px'});

		$(function () {

			ycreateEditor();

			if ($('#dialog #did').val() === '')
				$('#dialog #did').val($('#ctitle #did').val());

			$('div[data-priority="' + priority + '"]').addClass('active');

			$("#adresTo").autocomplete("/modules/mailer/core.mailer.php?action=search", {
				autofill: true,
				minChars: 3,
				cacheLength: 1,
				maxItemsToShow: 50,
				selectFirst: false,
				multiple: false,
				width: 300,
				delay: 500,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {

					var s = '';
					var s2;

					if (data[3])
						s = '<i class="icon-user-1 blue"></i>';
					else
						s = '<i class="icon-building broun"></i>';

					s2 = (data[5]) ? '<br><div class="fs-09 gray2"><i class="icon-building broun"></i>' + data[5] + '</div>' : '';

					return '<div>' + s + data[0] + s2 + '<div class="blue fs-07">' + data[1] + '</div></div>';

				}
			})
				.result(function (value, data) {

					var str = data.join("||");

					$("#adresTo").val('');
					selItem(str);

					return false;
				});

			$("#copyTo").autocomplete("modules/mailer/core.mailer.php?action=search", {
				autofill: true,
				minChars: 3,
				cacheLength: 1,
				maxItemsToShow: 50,
				selectFirst: false,
				multiple: false,
				width: 300,
				delay: 500,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {
					return '<div>' + data[0] + '<br><div class="blue">' + data[1] + '</div></div>';
				}
			})
				.result(function (value, data) {

					var str = data.join("||");
					selItem2(str);

				});

			$('.ui-layout-north').css('height', '42px');//.css('z-index','10000');

			$('#eForm').ajaxForm({
				dataType: 'json',
				beforeSubmit: function () {

					var em = checkRequired();

					if (!em)
						return false;

					if (!isDraft) {

						$('#dialog').css('display', 'none');
						$('#dialog_container').css('display', 'none');

					}

					if (!isDraft)
						yNotifyMe("CRM. Отправка сообщения,Начинаю отправку письма,good.png");

					return true;

				},
				success: function (data) {

					var si = '';

					if (isDraft) {

						var fi = data.files;

						for (var i in fi) {

							si = si + '<div class="fileboxx ellipsis relativ wp97 block"><input name="fid[]" type="hidden" class="file" id="fid[]" value="' + fi[i].fid + '"><a href="/content/helpers/get.file.php?file=ymail/' + fi[i].file + '" target="blank" title="Открыть"><i class="' + fi[i].icon + '"></i>' + fi[i].name + '</a><div class="dfileboxx hand mini deleteFilebox" title="Удалить"><i class="icon-cancel-circled red"></i></div></div>';

						}

						if (fi.length > 0) {

							$('#fuploads').append(si);
							$('#fuploads .nofile').remove();
							$('#fuploads .xtemp').remove();

						}

						$('#duploads .xtemp').remove();
						$('#iuploads').empty().append('<div class="fileboxx relativ wp97"><input name="file[]" id="file[]" type="file" class="file wp90" multiple onchange="addfile1();"><div class="dfileboxx hand clearUploadbox" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>');

						$('.description').empty().addClass('hidden');

						//if (typeof configmpage === 'function') configmpage();

						$('#isDraft').val('');
						$('.file').empty().val('');
						isDraft = false;

						yNotifyMe("Сохранение сообщения, " + data.result + ",good.png");

					}
					else {

						$('#dialog_container').css('display', 'none');
						$('#dialog').css('display', 'none');

						yNotifyMe("Отправка письма," + data.result + ",sendmail.png");

						//if(data.messageid != '') $('#mid').val('');

						//if (typeof configmpage === 'function') configmpage();

						if (typeof yremEditor === 'function') {
							yremEditor();
						}

					}

					if ($display === 'mailer')
						configmpage();

					if (isCard)
						settab('mail');

					$('#dialog').find('#id').val(data.id);

				}

			});

			ShowModal.fire({
				etype: 'ymailerForm',
				action: $('#action').val()
			});

			if (ymailAutoSaveTimer > 0) {

				var tm = ymailAutoSaveTimer * 60 * 1000;

				setTimeout(function () {
					setInterval(saveDraft, tm);
				}, 300000);

			}

		});

		$('.adres').on('click', function () {
			$('#adresTo').trigger('focus');
		});
		$('.copy').on('click', function () {
			$('#copyTo').trigger('focus');
		});

		$('.close').on('click', function () {
			yremEditor();
			DClose();
		});

		$(document).on('click', '.clos', function () {
			$('.docSelect').remove();
		});
		$(document).on('click', '.delItem', function () {
			$(this).closest('.tags').remove();
		});
		$(document).on('click', '.clearUploadbox', function () {//удаление из блока загрузки новых файлов

			var count = $('#iuploads .fileboxx').length;

			var string = '';
			var size = '';
			var color = '';

			if (count > 1) $(this).parent('.fileboxx').remove();
			else $(this).val('');

			$('.file').each(function () {

				for (var x = 0; x < this.files.length; x++) {

					size = this.files[x].size / 1024;

					if (parseInt(size) > parseInt(<?=$max?>)) color = 'red';
					else color = 'gray';

					string = string + '<li style="word-break: break-all">' + this.files[x].name + ' <span class="' + color + '">[' + setNumFormat(size.toFixed(2)) + ' kb]</span> </li>';

				}

			});

			//console.log(string);

			if (count > 1)
				$('.description').empty().append('<b>Выбраны файлы:</b> <ul class="pad3 marg0 ml15">' + string + '</ul>').removeClass('hidden');
			else
				$('.description').empty().addClass('hidden');

		});
		$(document).on('click', '.deleteFilebox', function () {

			$(this).parent('.fileboxx').remove();

			var count = $('#fuploads .fileboxx').length;
			if (count === 0)
				$('#fuploads').empty().append('<div class="smalltxt gray nofile">Нет вложений</div>');

		});
		$(document).on('click', '.deleteDocbox', function () {

			$(this).parent('.fileboxx').remove();

		});
		$(document).on('click', '.clearFilebox', function () {

			$(this).parent('.fileboxx').remove();

		});
		$(document).on('click', '#cnopkagroup li', function () {

			//var link = $(this).data('link');

			$('#cnopkagroup li').removeClass('active');
			$(this).addClass('active');

			$('#docfile').empty().append('<div id="loader"><img src="/assets/images/loading.gif"> Загружаю список</div></div>');

			//igetFiles("docs");

		});
		$(document).on('change', '.file', function () {

			var string = '';
			var size = '';
			var color = '';

			$('.file').each(function () {

				for (var x = 0; x < this.files.length; x++) {

					size = this.files[x].size / 1024;

					if (parseInt(size) > parseInt(<?=$max?>)) color = 'red';
					else color = 'gray';

					string = string + '<li style="word-break: break-all">' + this.files[x].name + ' <span class="' + color + '">[' + setNumFormat(size.toFixed(2)) + ' kb]</span> </li>';

				}

			});

			//console.log(string);

			$('.description').empty().append('<b>Выбраны файлы:</b> <ul class="pad3 marg0 ml15">' + string + '</ul>').removeClass('hidden');
			$('#dialog').center();

		});

		// сохраняем сообщение как черновик
		function saveDraft() {

			isDraft = true;

			CKEDITOR.instances['content'].updateElement();

			$('#isDraft').val('yes');
			$('#eForm').trigger('submit');

		}

		function sendMessagef() {

			CKEDITOR.instances['content'].updateElement();

			$('#isDraft').val('no');
			$('#eForm').trigger('submit');

			isDraft = 'no';

		}

		function switchCopy() {

			var hh = $('#aTbl').height();
			var lh = $('#mesLeft').height();
			var nh;

			if ($('#copyPole').hasClass('hidden')) nh = lh - hh - 45;
			else nh = lh + hh + 45;

			$('#copyPole').toggleClass('hidden');
			$('#dialog').center();
			$('#mesSubLeft').height(nh);

		}

		function selItem(data) {

			var count = $('#tocount').val();
			var id = (Math.random() + '').slice(2, 2 + Math.max(1, Math.min(5, 15)));

			data = data.split("||");

			$('#adresTo').val('');

			if (data[2] !== '')
				$('#tagbox .tg').append('<div id="' + id + '" class="tags relativ" title="' + data[1] + '">' + data[0] + '<input type="hidden" name="email[]" id="email[]" value="' + data[1] + '"><input type="hidden" name="name[]" id="name[]" value="' + data[0] + '"><input type="hidden" name="clid[]" id="clid[]" value="' + data[2] + '"><input type="hidden" name="pid[]" id="pid[]"><div class="delete delItem"><i class="icon-cancel-circled"></i></div></div>');

			if (data[3] !== '')
				$('#tagbox .tg').append('<div id="' + id + '" class="tags relativ" title="' + data[1] + '">' + data[0] + '<input type="hidden" name="email[]" id="email[]" value="' + data[1] + '"><input type="hidden" name="name[]" id="name[]" value="' + data[0] + '"><input type="hidden" name="pid[]" id="pid[]" value="' + data[3] + '"><input type="hidden" name="clid[]" id="clid[]"><div class="delete delItem"><i class="icon-cancel-circled"></i></div></div>');

			count = parseInt(count) + 1;

			$('#tocount').val(count);

		}

		function selItem2(data) {

			var count = $('#copycount').val();
			var id = (Math.random() + '').slice(2, 2 + Math.max(1, Math.min(5, 15)));

			data = data.split("||");

			$('#copyTo').val('');

			if (data[2] !== '')
				$('#tagbox2').append('<div id="' + id + '" class="tags relativ" title="' + data[1] + '">' + data[0] + '<input type="hidden" name="cemail[]" id="cemail[]" value="' + data[1] + '"><input type="hidden" name="cname[]" id="cname[]" value="' + data[0] + '"><input type="hidden" name="cclid[]" id="cclid[]" value="' + data[2] + '"><input type="hidden" name="cpid[]" id="cpid[]"><div class="delete delItem"><i class="icon-cancel-circled"></i></div></div>');

			if (data[3] !== '')
				$('#tagbox2').append('<div id="' + id + '" class="tags relativ" title="' + data[1] + '">' + data[0] + '<input type="hidden" name="cemail[]" id="cemail[]" value="' + data[1] + '"><input type="hidden" name="cpid[]" id="cpid[]" value="' + data[3] + '"><input type="hidden" name="cclid[]" id="cclid[]"><div class="delete delItem"><i class="icon-cancel-circled"></i></div></div>');

			count = parseInt(count) + 1;

			$('#copycount').val(count);

		}

		function ycreateEditor() {

			if (!$.browser.mozilla) $('.nano').css('height', '99.2%');

			yeditor = CKEDITOR.replace('content',
				{
					height: editorHeight + 'px',
					width: '99.7%',
					extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget,autolink',
					filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=ymail',
					filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=ymail',
					toolbar:
						[
							['Bold', 'Italic', 'Underline', 'Strike', 'TextColor', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
							['Outdent', 'Indent', '-', 'Blockquote'],
							['Font', 'FontSize'], ['JustifyLeft', 'JustifyCenter', 'JustifyRight'],
							['-'/*,'PasteText','PasteFromWord'*/, 'Image'/*,'HorizontalRule'*/], ['-', 'Undo', 'Redo', 'RemoveFormat'/*,'SelectAll'*/], ['Source']
						]
				});

			$('.cke_bottom').addClass('hidden');

			CKEDITOR.on("instanceReady", function () {

				var vh = $('.dialog').actual('height') - $('.zagolovok').actual('height') - $('.button--pane').actual('height') - $('.dv1').actual('height');

				vh = vh - $('.cke_top').actual('height') - $('.cke_bottom').actual('outerHeight') - 120;
				$('.cke_contents').height(vh + 'px');

				$('#dialog').center();

			});

			/**
			 * Иначе, если окно вызывается в Прайсе, Файлах, Базе знаний, Складе
			 * Пропадает список категорий
			 */
			if (typeof changeCategoryHeight === 'function') changeCategoryHeight();
			if (typeof setHeight === 'function') setHeight();

		}

		function yremEditor() {

			var html = $('#cke_editor_content').html();

			if (yeditor) {

				CKEDITOR.instances['content'].updateElement();

				$('#dialog #content').val(html);
				yeditor.destroy();
				yeditor = null;

			}

			$('.nano').css('height', '100%');

			$('#dialog').css('width', dw).height('auto').center();

			/**
			 * Иначе, если окно вызывается в Прайсе, Файлах, Базе знаний, Складе
			 * Пропадает список категорий
			 */
			if (typeof changeCategoryHeight === 'function') changeCategoryHeight();
			if (typeof setHeight === 'function') setHeight();

			return true;

		}

		function addEmail() {

			var email = $('#adresTo').val();
			var count = $('#tocount').val();
			var name = '';

			if (email !== '') {

				var ar = email.split("<");

				if (ar.length === 2) {

					name = ar[0];
					email = ar[1].replace(/[<,>]/g, '');

				}
				else {

					email = email.replace(/[<,>]/g, '');

				}

				if (name === '' || name === 'undefined') name = email;

				//console.log(ar.length);
				//console.log(name + ':' + email);

				var id = (Math.random() + '').slice(2, 2 + Math.max(1, Math.min(5, 15)));

				$('#flexinput').prepend('<div id="' + id + '" class="tags relativ" title="' + email + '">' + name + '<input type="hidden" name="email[' + count + ']" id="email[' + count + ']" value="' + email + '"><input type="hidden" name="name[' + count + ']" id="name[' + count + ']" value="' + name + '"><input type="hidden" name="clid[' + count + ']" id="clid[' + count + ']" value="0"><div class="delete delItem" onclick="delItem(\'' + id + '\')"><i class="icon-cancel-circled"></i></div></div>');

				count = parseInt(count) + 1;

				$('#tocount').val(count);
				$('#adresTo').val('');

			}

		}

		function addfile() {

			var htmltr = '<div class="fileboxx relativ ha wp100 p5 border-box"><input name="file[]" type="file" class="file wp100" id="file[]" onchange="addfile();" multiple style="display:block"><div class="dfileboxx hand clearUploadbox mt5" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

			$('#iuploads').prepend(htmltr);
			$('#dialog').center();

		}

		function selectTpl() {

			var url = 'modules/mailer/core.mailer.php?action=tpl.get&id=' + $('#tplId option:selected').val();

			$.post(url, function (data) {

				yeditor.setData(data.content);

			}, 'json');

		}

		function DClose2() {

			yremEditor();

			$('#dialog').css('display', 'none').css('width', '500px').height('auto');
			$('#resultdiv').empty();
			$('#dialog_container').css('display', 'none');

		}

		function setPS(tip, vlu) {

			$('.' + tip + ' .but').removeClass('active');
			$('#pr' + vlu).addClass('active');
			$('#' + tip).val(vlu);

		}

		function fullScreen() {

			var w = $(window).width() - 60;
			var h = $(window).height() - 40;
			var wd = $('#dialog').width();

			var msub = h - 34 - 80 - 80;
			var msubr = h - 34 - 80;

			if (wd < w) {

				$('#dialog').removeClass('dtransition').css('width', w).css('height', h).center();
				$('#fsc').removeClass('icon-resize-full').addClass('icon-resize-small');
				$('#mesSubLeft').css('height', msub).css('max-height', msub);
				$('#mesRight').css('height', msubr).css('max-height', msubr);
				$('.cke_contents').height(msub - 60);

			}
			else {

				$('#fsc').addClass('icon-resize-full').removeClass('icon-resize-small');

				$('#dialog').css({'height': dh, 'width': dw});

				$('#mesSubLeft').css({'height': mesSubLeft + 'px', 'max-height': mesSubLeft + 'px'});
				$('#mesRight').css({'height': mesSubRight + 'px', 'max-height': mesSubRight + 'px'});
				$('.cke_contents').height(editorHeight + 'px');

				//$('#dialog').addClass('dtransition').center();

			}

			$('#dialog').center();

		}

		function selectDocFile(id) {

			var $elm = $('#b' + id);

			var fid = parseInt($elm.data('fid'));
			var icon = $elm.find('a').html();
			var file = $elm.data('file');
			var name = $elm.data('name');
			var box = '';
			var fbox = '';
			//var count = $('.fileboxx').length;

			if (fid !== 0) {

				box = '<div class="fileboxx relativ wp100 ha ellipsis" style="display:block"><input name="xfid[]" id="xfid[]" type="hidden" value="' + fid + '">' + icon + '<div class="dfileboxx hand deleteFilebox" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

				$('.filebox').append(box);

			}
			else {

				fbox = '<div class="fileboxx ellipsis wp100 ha relativ xtemp" style="display: block;"><input name="xfile[]" id="xfile[]" type="hidden" value="' + file + '"><input name="xname[]" id="xname[]" type="hidden" value="' + name + '"><a href="content/helpers/get.file.php?file=' + file + '" target="blank" title="Открыть">' + icon + '</a><div class="dfileboxx hand mini deleteDocbox" title="Удалить"><i class="icon-cancel-circled red"></i></div></div>';

				$('#duploads .nofile').remove();
				$('#duploads .filebox').append(fbox);

			}

			$elm.remove();

		}

		function appendFiles() {

			var w = $('#mesLeft').width();
			var h = $('#mailComposer').height();
			var h2 = $('#mailComposer').height() - 105;
			var s = '';

			var did = '';

			var pids = $('.adresat #pid\\[\\]').map(function () {
				return $(this).val();
			}).get();

			var clids = $('.adresat #clid\\[\\]').map(function () {
				return $(this).val();
			}).get();

			if ($('#ctitle').is('input')) did = $('#ctitle').find('#did').val();
			else did = $('#resultdiv').find('#did').val();

			if (did !== 'undefined' && did != null) {

				s = '<li class="flex-string" data-link="invoice" onclick="igetFiles(\'invoice\');"><span>Счета</span></li><li class="flex-string" data-link="akt" onclick="igetFiles(\'akt\');"><span>Акты</span></li>';

			}

			var template = '<div class="docSelect pad10" style="height:' + h + 'px; width:69%;"><div class="miditxt blue relativ Bold">Вложить файл<div class="clos blue"><i class="icon-cancel-circled"></i></div></div><div><div id="cnopkagroup"><ul class="gray flex-container"><li class="flex-string active" data-link="card" onclick="igetFiles(\'card\');"><span>Файлы карточки</span></li><li class="flex-string" data-link="file" onclick="igetFiles(\'file\');"><span>Общие файлы</span></li><li class="flex-string" data-link="docs" onclick="igetFiles(\'docs\');"><span>Документы</span></li>' + s + '</ul></div></div><hr><div id="docfile" style="height:' + h2 + 'px; max-height:' + h2 + 'px; overflow-y:auto; overflow-x:hidden"><div id="loader"><img src="/assets/images/loading.gif"> Загружаю список</div></div></div>';

			$('#mailComposer').append(template);

			igetFiles('card');

		}

		function igetFiles(link) {

			var did = '';
			var pids = $('.adresat #pid\\[\\]').map(function () {
				return $(this).val();
			}).get();
			var clids = $('.adresat #clid\\[\\]').map(function () {
				return $(this).val();
			}).get();

			if ($('#ctitle').is('input')) did = $('#ctitle').find('#did').val();
			else did = $('#resultdiv').find('#did').val();

			$.get('modules/mailer/core.mailer.php?action=getFiles&link=' + link + '&clids=' + clids + '&pids=' + pids + '&did=' + did, function (data) {

				var fbox = '';

				$('#docfile').empty();

				var file = data.files;
				var s = '';

				if (data.error == null) {

					for (var i in file) {

						if (file[i].parent === undefined) file[i].parent = '';

						if (file[i].fid != null)
							fbox = '<div class="stroka ha pb10" id="b' + i + '" data-fid="' + file[i].fid + '" data-file="' + file[i].file + '" data-name="' + file[i].name + '"><div class="pull-aright hand" onclick="selectDocFile(\'' + i + '\')" title="Выбрать"><i class="icon-ok green"></i></div><div class="ellipsis fs-12"><a href="/content/helpers/get.file.php?fid=' + file[i].fid + '" target="blank" title="Открыть" class="Bold">' + file[i].icon + file[i].name + '</a></div><br><div class="fs-09 gray pl20 mt10">' + file[i].size + 'kb&nbsp;<i class="icon-clock"></i>' + file[i].date + '&nbsp;' + file[i].parent + '</div></div>';

						if (file[i].crid != null) {

							if (file[i].exist === 'yes')
								s = '<div class="pull-aright hand sellok" onclick="selectDocFile(\'' + i + '\')" title="Выбрать"><i class="icon-ok green"></i></div>';
							else
								s = '<div class="pull-aright hand sellcre" onclick="createInvoiceFile(\'' + file[i].crid + '\',\'' + i + '\')" title="Файл PDF еще не создан. Нажмите, чтобы создать"><i class="icon-plus-circled red"></i></div>';

							fbox = '<div class="stroka ha pb10" id="b' + i + '" data-fid="0" data-crid="' + file[i].crid + '" data-file="' + file[i].file + '" data-name="' + file[i].name + '">' + s + '<div class="ellipsis fs-12"><a href="javascript:void(0)" onclick="editCredit(\'' + file[i].crid + '\',\'credit.view\')" title="Открыть" class="Bold">' + file[i].icon + file[i].name + '</a></div><br><div class="fs-09 gray pl20 mt10"><i class="icon-clock"></i>' + file[i].date + '&nbsp;' + file[i].parent + '</div></div>';

						}

						if (file[i].deid != null) {

							if (file[i].exist === 'yes')
								s = '<div class="pull-aright hand sellok" onclick="selectDocFile(\'' + i + '\')" title="Выбрать"><i class="icon-ok green"></i></div>';
							else
								s = '<div class="pull-aright hand sellcre" onclick="createAktFile(\'' + file[i].deid + '\',\'' + i + '\')" title="Файл PDF еще не создан. Нажмите, чтобы создать"><i class="icon-plus-circled red"></i></div>';

							fbox = '<div class="stroka ha pb10" id="b' + i + '" data-fid="0" data-deid="' + file[i].deid + '" data-file="' + file[i].file + '" data-name="' + file[i].name + '">' + s + '<div class="ellipsis fs-12"><a href="javascript:void(0)" onclick="editAkt(\'view\',\'' + file[i].deid + '\',\'' + did + '\')" title="Открыть" class="Bold">' + file[i].icon + file[i].name + '</a></div><br><div class="fs-09 gray pl20 mt10"><i class="icon-clock"></i>' + file[i].date + '&nbsp;' + file[i].parent + '</div></div>';

						}

						$('#docfile').append(fbox);

					}

				}
				else $('#docfile').append('<div class="pad10">' + data.error + '</div>');

			}, 'json');

		}

		function createInvoiceFile(id, i) {

			$('#docfile #b' + i + ' .sellcre').html('<span class="pad3"><img src="/assets/images/loading.gif" width="14"></span>');

			$.get('content/core/core.deals.php?action=invoice.link&crid=' + id, function (data) {

				if (data.file) {

					$('#docfile #b' + i).data("file", data.file);
					$('#docfile #b' + i + ' .sellcre').remove();
					$('#docfile #b' + i).prepend('<div class="pull-aright hand sellok" onclick="selectDocFile(\'' + i + '\')" title="Выбрать"><i class="icon-ok green"></i></div>');

				}

			}, 'json');

		}

		function createAktFile(id, i) {

			$('#docfile #b' + i + ' .sellcre').html('<span class="pad3"><img src="/assets/images/loading.gif" width="14"></span>');

			$.get('modules/contract/core.contract.php?action=akt.link&did=' + did + '&deid=' + id, function (data) {

				var da = data.files;

				if (da[0].file) {

					$('#docfile #b' + i).data("file", data.file);
					$('#docfile #b' + i + ' .sellcre').remove();
					$('#docfile #b' + i).prepend('<div class="pull-aright hand sellok" onclick="selectDocFile(\'' + i + '\')" title="Выбрать"><i class="icon-ok green"></i></div>');

				}

			}, 'json');

		}

	</script>
	<?php

}

/**
 * Просмотр сообщения. Раздел "Почтовик"
 */
if ( $action == 'view' ) {

	$id = (int)$_REQUEST['id'];

	$mail       = new Mailer();
	$mail -> id = $id;
	$mail -> mailView();

	$email = $mail -> View;

	print json_encode( $email );

	exit();

}

/**
 * Быстрый просмотр. Модальное окно
 * todo: переделать под мустаче
 */
if ( $action == 'preview' ) {

	$id = (int)$_REQUEST['id'];
	?>
	<script>

		$.Mustache.load('modules/mailer/tpl/interface.mustache');

		$(function () {

			$('#dialog').css('width', '80%').center();

			var url = 'modules/mailer/editor.php?id=<?=$id?>&action=view';

			$.getJSON(url, function (data) {

				$('#resultdiv').empty().mustache('preview', data);

			})
				.done(function () {

					$mailer.check();
					$mailer.count();
					$mailer.formatQuoteDialog();

					$('#cont a').attr("target", "_blank");

					$('#dialog').center();

				});

		});
	</script>
	<?php
	exit();
}

/**
 * Редактор шаблона
 */
if ( $action == "tpl.edit" ) {

	$id = (int)$_REQUEST['id'];

	$tpl = ($id > 0) ? $db -> getRow( "SELECT * FROM ".$sqlname."ymail_tpl WHERE id = '$id' and identity = '$identity'" ) : ["share" => "no"];
	?>
	<DIV class="zagolovok"><B>Редактор шаблона</B></DIV>
	<form action="/modules/mailer/core.mailer.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="tpl.edit.do">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="flex-container" style="height:60vh;">

			<div class="flex-string wp80">

				<div class="row">

					<div class="column grid-10">
						<input type="text" name="name" id="name" value="<?= $tpl['name'] ?>" class="wp100" placeholder="Название шаблона">
					</div>
					<div class="column grid-10 relativ" style="height: 380px">
						<div class="pull-right mt10">
							<a href="javascript:void(0)" title="Действия" class="tagsmenuToggler"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
							<div class="tagsmenu hidden" style="right: 0;">
								<ul>
									<li title="Клиент: Ф.И.О. или Название"><b>{client}</b></li>
									<li title="Тел.:мой"><b>{phone}</b></li>
									<li title="Факс:мой"><b>{fax}</b></li>
									<li title="Моб.:мой"><b>{mob}</b></li>
									<li title="Email:мой"><b>{email}</b></li>
									<li title="Подпись"><b>{manager}</b></li>
									<li title="Компания:кратко"><b>{company}</b></li>
									<li title="Компания:полное"><b>{company_full}</b></li>
									<li title="Сайт компании"><b>{company_site}</b></li>
								</ul>
							</div>
						</div>
						<textarea name="content" id="content" style="width: 99%; height: 200px; max-height: 200px; font-size: 1.2em"><?= htmlspecialchars_decode( $tpl['content'] ) ?></textarea>
					</div>

				</div>

			</div>
			<div class="flex-string wp20 tpllist" style="overflow-x: hidden; overflow-y: auto; box-sizing: border-box;">

				<div class="ha hand p10 ytpl orangebg Bold" data-id="">
					<div>Добавить новый</div>
				</div>
				<hr class="marg0 p0">

				<?php
				$r = $db -> getAll( "SELECT * FROM ".$sqlname."ymail_tpl WHERE iduser = '$iduser1' and identity = '$identity'" );
				foreach ( $r as $tpl ) {

					$s = ($tpl['id'] == $id) ? "bluebg" : "graybg-sub";

					print '
				<div class="ha hand p10 ytpl relativ '.$s.'" data-id="'.$tpl['id'].'">
					<div>'.$tpl['name'].'</div>
					<div class="pull-aright delete"><i class="idel icon-cancel-circled red"></i></div>
				</div>
				';

				}

				if ( empty( $r ) )
					print '<div class="p10">Шаблонов не найдено</div>';
				?>

			</div>

		</div>

		<hr>

		<div>

			<div class="pt10 pull-left pl10">

				<div class="checkbox mt5">
					<label>
						<input name="share" type="checkbox" id="share" value="yes" <?php if ( $tpl['share'] == 'yes' )
							print "checked"; ?>>
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						&nbsp;Общий&nbsp;<i class="icon-info-circled blue" title="Общие шаблоны могут использовать все сотрудники компании"></i>
					</label>
				</div>

			</div>

			<div class="pull-aright">
				<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>

		</div>
	</form>

	<script>

		$('#dialog').css('width', '80vw');

		$(function () {

			var h = $('#dialog').find('.flex-container').actual('height');
			var h2 = h - 130;

			editorr = CKEDITOR.replace('content',
				{
					height: h2 + "px",
					toolbar:
						[
							['Bold', 'Italic', 'Underline', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
							['Undo', 'Redo', '-', 'Replace', '-', 'RemoveFormat', '-', 'HorizontalRule'],
							['TextColor', 'FontSize'], ['Outdent', 'Indent'],
							['JustifyLeft', 'JustifyCenter', 'JustifyBlock'], ['Source']
						]
				});


			CKEDITOR.on("instanceReady", function (event) {
				$('#dialog').center();
			});

		});

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');
				$('.ytpl').not('.orangebg').removeClass('bluebg').addClass('graybg-sub');

				return true;

			},
			success: function (data) {

				var list = data.list;
				var string = '<div class="ha hand p10 ytpl orangebg Bold" data-id=""><div>Добавить новый</div></div><hr class="marg0 p0">';

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.mes);

				for (var i in list) {

					var s = 'graybg-sub';

					if (list[i].id === data.id) s = 'bluebg';

					string = string + '<div class="ha hand p10 ytpl relativ ' + s + '" data-id="' + list[i].id + '"><div>' + list[i].name + '</div><div class="pull-aright delete"><i class="idel icon-cancel-circled red"></i></div></div>';

				}

				$('.tpllist').empty().html(string);
				$('#id').val(data.id);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				$('#ytpl[data-id="' + data.id + '"]').removeClass('graybg-sub').addClass('bluebg');

			}
		});

		function saveForm() {

			CKEDITOR.instances['content'].updateElement();

			$('#Form').trigger('submit')

		}

		$('.tagsmenu li').on('click', function () {

			var t = $('b', this).html();
			addTagInEditor(t);

		});

		$(document).on('click', '.ytpl', function () {

			var id = $(this).data('id');

			$('.ytpl').not('.orangebg').removeClass('bluebg').addClass('graybg-sub');

			$.get("modules/mailer/core.mailer.php?action=tpl.get&id=" + id, function (data) {

				$('#id').val(data.id);
				$('#name').val(data.name);
				$('#content').val(data.content);

				if (data.share === "yes")
					$('#share').attr("checked", "checked");
				else
					$('#share').removeAttr("checked");

				editorr.setData(data.content);

			}, "json");

			$(this).removeClass('graybg-sub').addClass('bluebg');

		});
		$(document).on('click', '.delete', function () {

			var element = $(this).closest('.ytpl');
			var id = element.data('id');
			var current = $('#id').val();

			$('.ytpl').not('.orangebg').removeClass('bluebg').addClass('graybg-sub');

			$.get("modules/mailer/core.mailer.php?action=tpl.delete&id=" + id, function () {

				if (current === id) {

					$('#id').val("");
					$('#name').val("");
					$('#content').val("");

					$('#share').removeAttr("checked");

					editorr.setData("");

				}

			});

			element.remove();

		});


		function addTagInEditor(myitem) {

			//html = $('#cke_editor_content').html();

			var oEditor = CKEDITOR.instances.content;
			oEditor.insertHtml(myitem);

			return true;

		}

	</script>
	<?php
	exit();
}

if ( $action == "blacklist.add" ) {

	$email = $_REQUEST['email'];
	//$user     = ($_REQUEST['user'] > 0) ? $_REQUEST['user'] : $GLOBALS['iduser1'];
	$identity = $GLOBALS['identity'];

	$db -> query( "INSERT INTO ".$sqlname."ymail_blacklist (email,identity) VALUES ('".$email."','".$identity."')" );

	$count = $db -> affectedRows();

	if ( $count > 0 ) {
		print "Сделано";
	}
	else {
		print "Ошибка";
	}

	exit();

}
if ( $action == "blacklist.delete" ) {

	$email = $_REQUEST['email'];
	//$user     = ($_REQUEST['user'] > 0) ? $_REQUEST['user'] : $GLOBALS['iduser1'];
	$identity = $GLOBALS['identity'];

	$db -> query( "DELETE FROM ".$sqlname."ymail_blacklist WHERE email='".$email."' AND identity='".$identity."'" );

	$count = $db -> affectedRows();

	if ( $count > 0 ) {
		print "Сделано";
	}
	else {
		print "Ошибка";
	}

	exit();
}
if ( $action == "blacklist.view" ) {

	$blacklist = Blacklist();

	?>
	<div class="zagolovok">Черный список</div>

	<?php
	if ( !empty( $blacklist ) ) {
		?>


		<input type="text" id="search_email" title="Поиск" class="m10 wp97" placeholder="Поиск по e-mail">

		<div id="emails" style="overflow-y: auto !important; overflow-x: hidden;max-height: 50vh">
			<?php
			foreach ( $blacklist as $key => $email ) {
				?>
				<div class="data mr5 ml5 ha h40" id="email-<?= $key ?>">
					<span class="ellipsis wp80"><?= $email ?></span>
					<span class="pull-aright" style="cursor: pointer;"><a href="javascript:void(0)" class="text-right red" onclick="change_blacklist('delete','<?= $email ?>','yes', '<?= $key ?>')">Удалить</a></span>
				</div>
				<?php
			}
			?>

		</div>

		<script>

			(function ($) {

				jQuery.expr[':'].Contains = function (a, i, m) {
					return (a.textContent || a.innerText || "").toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
				};

				function filterList(list) {

					$('#search_email').on('change', function () {
						var filter = $(this).val();
						if (filter) {

							$matches = $(list).find('span:Contains(' + filter + ')').parent();
							$('div', list).not($matches).slideUp();
							$matches.slideDown();

						}
						else {
							$(list).find("div").slideDown();
						}
						return false;
					})
						.on('keyup', function () {
							$(this).trigger('change');
						});

				}

				$(function () {
					filterList($("#emails"));
				});

			}(jQuery));
		</script>

		<?php
	}
	else {
		?>

		<div class="infodiv">
			В Черном списке контактов нет!
		</div>
		<?php
	}
	?>

	<div class="text-right">
		<a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>
	</div>
	<?php
	exit();

}