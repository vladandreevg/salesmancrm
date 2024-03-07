<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$action    = $_REQUEST['action'];
$uploaddir = '/cash/'.$fpath.'templates/';

if (file_exists($rootpath.'/cash/'.$fpath.'requisites.json')) {
	$file = file_get_contents($rootpath.'/cash/'.$fpath.'requisites.json');
}
else {
	$file = file_get_contents($rootpath.'/cash/requisites.json');
}

$recvName = json_decode($file, true);

$mcDefault = customSettings("mcDefault");

if ($action == "delete.company") {

	$id    = (int)$_REQUEST['id'];
	$newid = (int)$_REQUEST['newid'];

	//Проверим наличие счетов
	$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = '$id' and identity = '$identity'");

	if ($count == 0) {

		$mc    = $db -> getRow("SELECT logo, stamp FROM {$sqlname}mycomps WHERE id = '$id' and identity = '$identity'");
		$logo  = $mc['logo'];
		$stamp = $mc['stamp'];

		if (file_exists($uploaddir.$logo)) {
			unlink($uploaddir.$logo);
		}

		if (file_exists($uploaddir.$stamp)) {
			unlink($uploaddir.$stamp);
		}

		$db -> query("DELETE FROM {$sqlname}mycomps WHERE id = '$id' and identity = '$identity'");

		$db -> query("UPDATE {$sqlname}dogovor SET mcid = '$newid' WHERE mcid = '$id' and close != 'yes' and identity = '$identity'");

		$db -> query("UPDATE {$sqlname}dogovor SET mcid = '0' WHERE mcid = '$id' and close = 'yes' and identity = '$identity'");

		print 'Успешно';

	}
	else {
		print "Удаление компании невозможно - сначала удалите все расчетные счета";
	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();
}
if ($action == "delete.rs.do") {

	$id        = (int)$_REQUEST['id'];
	$newid     = (int)$_REQUEST['newid'];
	$dogChange = $_REQUEST['dogChange'];

	//находим компанию к которой привязан старый рс
	$cid = $db -> getOne("SELECT cid FROM {$sqlname}mycomps_recv WHERE id='$id' and identity = '$identity'");

	//находим компанию к которой привязан новый рс
	$newcid = $db -> getOne("SELECT cid FROM {$sqlname}mycomps_recv WHERE id='$newid' and identity = '$identity'");


	if ($dogChange == 'yes') {
		$db -> query("UPDATE {$sqlname}dogovor SET mcid = '$newcid' WHERE mcid = '$cid' and close != 'yes' and identity = '$identity'");
	}


	//проходим все счета и меняем на новый р.с.
	$db -> query("UPDATE {$sqlname}credit SET rs = '$newid' WHERE rs = '$id' and do != 'on' and identity = '$identity'");
	$db -> query("UPDATE {$sqlname}credit SET rs = '$newid' WHERE rs = '0' and do = 'on' and identity = '$identity'");

	//в расходах поставим 0 - удален
	$db -> query("UPDATE {$sqlname}budjet SET rs = '0' WHERE rs='$id' and do != 'on' and identity = '$identity'");
	$db -> query("UPDATE {$sqlname}budjet SET rs = '$newid' WHERE rs = '$id' and do = 'on' and identity = '$identity'");

	//удаляем рс
	$db -> query("DELETE FROM {$sqlname}mycomps_recv WHERE id = '$id' and identity = '$identity'");

	print 'Сделано';

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}
if ($action == "delete.signer.do") {

	$id    = (int)$_REQUEST['id'];
	$newid = (int)$_REQUEST['newid'];

	//проходим все счета, документы и акты и меняем на нового подписанта
	$db -> query("UPDATE {$sqlname}credit SET signer = '$newid' WHERE signer = '$id' and identity = '$identity'");
	$db -> query("UPDATE {$sqlname}contract SET signer = '$newid' WHERE signer = '$id' and identity = '$identity'");


	//удаляем рс
	$db -> query("DELETE FROM {$sqlname}mycomps_signer WHERE id = '$id' and identity = '$identity'");

	print 'Сделано';

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if ($action == "edit.on") {

	$id = (int)$_REQUEST['id'];

	$company = [];

	$company['name_ur']       = clean_all($_REQUEST['name_ur']);
	$company['name_shot']     = clean_all($_REQUEST['name_shot']);
	$company['dir_name']      = clean_all($_REQUEST['dir_name']);
	$company['address_yur']   = clean_all($_REQUEST['address_yur']);
	$company['address_post']  = clean_all($_REQUEST['address_post']);
	$company['dir_signature'] = clean_all($_REQUEST['dir_signature']);
	$company['dir_status']    = clean_all($_REQUEST['dir_status']);
	$company['dir_osnovanie'] = clean_all($_REQUEST['dir_osnovanie']);
	$company['innkpp']        = clean_all($_REQUEST['inn'].';'.$_REQUEST['kpp']);
	$company['okog']          = clean_all($_REQUEST['okpo'].';'.$_REQUEST['ogrn']);

	$logo = $stamp = '';

	if ($id > 0) {

		$mc               = $db -> getRow("SELECT logo, stamp FROM {$sqlname}mycomps WHERE id = '$id' and identity = '$identity'");
		$company['logo']  = $logo = $mc['logo'];
		$company['stamp'] = $stamp = $mc['stamp'];

	}

	$message = [];

	//Загрузка логотипа
	if ($_FILES['ilogo']['name']) {

		$ftitle = basename($_FILES['ilogo']['name']);
		$ff     = array_reverse(explode(".", $ftitle));

		$company['logo'] = 'logo'.time().'.'.$ff[0];
		$uploadfile      = $rootpath.$uploaddir.$company['logo'];

		if (move_uploaded_file($_FILES['ilogo']['tmp_name'], $uploadfile)) {

			$message[] = 'Логотип загружен';
			//$db -> query("update {$sqlname}mycomps set logo = '".$company['logo']."' WHERE id = '".$id."' and identity = '$identity'");

			if (file_exists($rootpath.$uploaddir.$logo)) {
				unlink($rootpath.$uploaddir.$logo);
			}

		}
		else {

			$message[] = 'Ошибка при загрузке файла '.$ftitle.'!<br>Ошибка: '.$_FILES['logo']['error'];

			$company['logo'] = $logo;

		}

	}

	//Загрузка печати
	if ($_FILES['stamp']['name']) {

		$ftitle = basename($_FILES['stamp']['name']);
		$ff     = array_reverse(explode(".", $ftitle));

		$company['stamp'] = 'stamp'.time().'.'.$ff[0];
		$uploadfile       = $rootpath.$uploaddir.$company['stamp'];

		if (move_uploaded_file($_FILES['stamp']['tmp_name'], $uploadfile)) {

			$message[] = 'Печать загружена';
			//$db -> query("update {$sqlname}mycomps set stamp = '".$company['stamp']."' WHERE id = '$id' and identity = '$identity'");

			if (file_exists($rootpath.$uploaddir.$stamp)) {
				unlink($rootpath.$uploaddir.$stamp);
			}

		}
		else {

			$message[]        = 'Ошибка при загрузке файла '.$ftitle.'!<br>Ошибка: '.$_FILES['signature']['error'];
			$company['stamp'] = $stamp;

		}

	}

	//print_r($company);

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}mycomps SET ?u WHERE id = '$id' and identity = '$identity'", $company);

		$message[] = 'Обновлено';

	}
	else {

		$company['identity'] = $identity;
		$db -> query("INSERT INTO {$sqlname}mycomps SET ?u", $company);

		$message[] = 'Добавлено';

	}

	if ($_REQUEST['mcDefault'] == 'yes') {

		customSettings('mcDefault', 'put', ["params" => $id]);

	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	print yimplode("<br>", $message);

	exit();

}
if ($action == "edit.rs.on") {

	$id  = (int)$_REQUEST['id'];
	$cid = (int)$_REQUEST['cid'];

	$bankr = [];
	for ($i = 0; $i < 3; $i++) {

		$bankr[] = $_REQUEST['bankr'][$i];

	}

	$title      = clean_all($_REQUEST['title']);
	$rs         = clean_all($_REQUEST['rs']);
	$tip        = clean_all($_REQUEST['tip']);
	$ostatok    = pre_format($_REQUEST['ostatok']);
	$bloc       = $_REQUEST['bloc'];
	$bankr      = clean_all(implode(";", $bankr));
	$isDefault  = clean_all($_REQUEST['isDefault']);
	$ndsDefault = (float)$_REQUEST['ndsDefault'];

	if ($id > 0) {
		$cid = $db -> getOne("SELECT cid FROM {$sqlname}mycomps_recv WHERE id = '$id' and identity = '$identity'");
	}


	//сбросим все умолчания
	if ($isDefault == 'yes') {
		$db -> query("UPDATE {$sqlname}mycomps_recv SET isDefault = 'no' WHERE isDefault = 'yes' and cid = '$cid' and identity = '$identity'");
	}


	$data = [
		"title"      => $title,
		"rs"         => $rs,
		"bankr"      => $bankr,
		"ostatok"    => (float)$ostatok,
		"bloc"       => $bloc == 'yes' ? 'yes' : 'no',
		"isDefault"  => $isDefault,
		"ndsDefault" => $ndsDefault,
		"tip"        => $tip
	];

	if ($id == 0) {

		$data['cid']      = $cid;
		$data['identity'] = $identity;

		$db -> query("INSERT INTO {$sqlname}mycomps_recv SET ?u", $data);

	}
	else {

		$db -> query("UPDATE {$sqlname}mycomps_recv SET ?u WHERE id = '$id' and identity = '$identity'", $data);

	}

	print 'Успешно';

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();
}
if ($action == "edit.signer.on") {

	$id = (int)$_REQUEST['id'];

	$company = [];

	$company['title']     = clean_all($_REQUEST['title']);
	$company['signature'] = clean_all($_REQUEST['signature']);
	$company['status']    = clean_all($_REQUEST['status']);
	$company['osnovanie'] = clean_all($_REQUEST['osnovanie']);
	$company['mcid']      = (int)$_REQUEST['mcid'];

	$stamp = '';

	if ($id > 0) {

		$company['stamp'] = $db -> getOne("SELECT stamp FROM {$sqlname}mycomps_signer WHERE id = '$id' and identity = '$identity'");

	}

	$message = [];

	//Загрузка факсимилье
	if ($_FILES['stamp']['name']) {

		$ftitle = basename($_FILES['stamp']['name']);
		$ff     = array_reverse(explode(".", $ftitle));

		$company['stamp'] = 'signer_facsimile'.time().'.'.$ff[0];
		$uploadfile       = $rootpath.$uploaddir.$company['stamp'];

		if (move_uploaded_file($_FILES['stamp']['tmp_name'], $uploadfile)) {

			$message[] = 'Факсимилье загружено';

			if (file_exists($rootpath.$uploaddir.$stamp)) {
				unlink($rootpath.$uploaddir.$stamp);
			}

		}
		else {

			$message[]        = 'Ошибка при загрузке файла '.$ftitle.'!<br>Ошибка: '.$_FILES['stamp']['error'];
			$company['stamp'] = $stamp;

		}

	}

	//print_r($company);

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}mycomps_signer SET ?u WHERE id = '$id' and identity = '$identity'", $company);

		$message[] = 'Обновлено';

	}
	else {

		$company['identity'] = $identity;
		$db -> query("INSERT INTO {$sqlname}mycomps_signer SET ?u", $company);

		$message[] = 'Добавлено';

	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	print yimplode("<br>", $message);

	exit();

}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="warning text-center"><h2>Доступ запрещен.</h2>>Обратитесь к администратору.</div>';
	exit();

}

if ($action == 'edit') {

	$id = (int)$_REQUEST['id'];

	$mc = [];

	$mc['logo']  = "logo.png";
	$mc['stamp'] = "signature.png";

	if ($id > 0) {

		$mc = $db -> getRow("SELECT * FROM {$sqlname}mycomps WHERE id = '$id' and identity = '$identity'");

		$mc['innkpp'] = explode(";", $mc["innkpp"]);
		$mc['okog']   = explode(";", $mc["okog"]);

	}

	?>
	<DIV class="zagolovok">Редактирование Компании</DIV>
	<FORM method="post" action="/content/admin/<?php
	echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.on">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" style="max-height: 70vh; overflow-y: auto;">

			<div class="flex-container box--child">

				<div class="flex-string flex-vertical wp60 pl10 pr20 box--child">

					<div class="flex-container">

						<div class="flex-string wp100 label">Юридическое название</div>
						<div class="flex-string wp100">
							<input name="name_ur" id="name_ur" type="text" class="wp100 required" value="<?= $mc['name_ur'] ?>">
							<div class="fs-07 gray2">Например:
								<b>Общество с ограниченной ответственностью "Рога и Копыта"</b></div>
						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Сокращенное название</div>
						<div class="flex-string wp100">
							<input name="name_shot" id="name_shot" type="text" class="wp100 required" value="<?= $mc['name_shot'] ?>">
							<div class="fs-07 gray2">Например: <b>ООО "Рога и Копыта"</b></div>
						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Юр.адрес</div>
						<div class="flex-string wp100">
							<input name="address_yur" id="address_yur" type="text" class="wp100 required" value="<?= $mc['address_yur'] ?>">
						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Почтовый адрес</div>
						<div class="flex-string wp100">
							<input name="address_post" id="address_post" type="text" class="wp100 required" value="<?= $mc['address_post'] ?>">
						</div>

					</div>

					<div class="flex-container wp50">

						<div class="flex-string wp100 label"><?= $recvName['recvInn'] ?></div>
						<div class="flex-string wp100">
							<input name="inn" id="inn" type="text" class="wp100 required" value="<?= $mc['innkpp'][0] ?>">
						</div>

					</div>
					<div class="flex-container wp50">

						<div class="flex-string wp100 label"><?= $recvName['recvKpp'] ?></div>
						<div class="flex-string wp100">
							<input name="kpp" id="kpp" type="text" class="wp100" value="<?= $mc['innkpp'][1] ?>">
						</div>

					</div>

					<div class="infodiv wp100">При настройке подключения к сервису Dadata.ru работает автозаполнение по ИНН</div>

					<div class="flex-container wp50">

						<div class="flex-string wp100 label"><?= $recvName['recvOkpo'] ?></div>
						<div class="flex-string wp100">
							<input name="okpo" id="okpo" type="text" class="wp100" value="<?= $mc['okog'][0] ?>">
						</div>

					</div>
					<div class="flex-container wp50">

						<div class="flex-string wp100 label"><?= $recvName['recvOgrn'] ?></div>
						<div class="flex-string wp100">
							<input name="ogrn" id="ogrn" type="text" class="wp100" value="<?= $mc['okog'][1] ?>">
						</div>

					</div>

					<div class="flex-container">

						<div class="flex-string wp100 label">В лице Руководителя</div>
						<div class="flex-string wp100">
							<input name="dir_name" id="dir_name" type="text" class="wp100 required" value="<?= $mc['dir_name'] ?>">
							<div class="fs-07 gray">Например: <b>Генерального директора Иванова Ивана Ивановича</b></div>
						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Должность руководителя</div>
						<div class="flex-string wp100">
							<input name="dir_status" id="dir_status" type="text" class="wp100 required" value="<?= $mc['dir_status'] ?>"><br>
							<div class="fs-07 gray">Например: <b>Генеральный директор</b></div>
						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Подпись Руководителя</div>
						<div class="flex-string wp100">
							<input name="dir_signature" id="dir_signature" type="text" class="wp100 required" value="<?= $mc['dir_signature'] ?>">
							<div class="fs-07 gray">Например: <b>Иванов И.И.</b></div>
						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Действующего на основании</div>
						<div class="flex-string wp100">
							<input name="dir_osnovanie" id="dir_osnovanie" type="text" class="wp100 required" value="<?= $mc['dir_osnovanie'] ?>"><br>
							<div class="fs-07 gray">Например: <b>Доверенности №128 от 10.01.2013 г.</b></div>
						</div>

					</div>

				</div>
				<div class="flex-string wp40 pr10">

					<div class="flex-container">

						<div class="flex-string wp100 label">Загрузить Факсимиле</div>
						<div class="flex-string wp100">

							<DIV id="uploads"><input type="file" name="stamp" id="stamp" class="file"></DIV>
							<div class="fs-07">Используйте файлы с расширением<b>gif, png с прозрачным фоном</b>.</div>

							<div style="border:2px solid #ccc; height:160px; background: url(/assets/images/transparent.png) center no-repeat;">
								<div id="dstamp" style="height:100%; background: url(<?= $uploaddir.$mc['stamp'] ?>) center no-repeat; background-size:contain;"></div>
							</div>

						</div>

					</div>
					<div class="flex-container">

						<div class="flex-string wp100 label">Загрузить Логотип</div>
						<div class="flex-string wp100">

							<DIV id="uploads"><input type="file" name="ilogo" id="ilogo" class="file"></DIV>
							<hr>
							<div class="fs-07">Используйте файлы с расширением<b>gif, png с прозрачным фоном</b>.</div>

							<div style="border:2px solid #ccc; height:40px; background: url(/assets/images/transparent.png) center no-repeat;">
								<div id="dlogo" style="height:90%; margin: auto 0; background: url(<?= $uploaddir.$mc['logo'] ?>) center no-repeat; background-size:contain;"></div>
							</div>

						</div>

					</div>

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button-pane">

			<div class="pt10 pull-left pl10">

				<div class="checkbox">

					<label for="mcDefault">
						<input name="mcDefault" type="checkbox" id="mcDefault" value="yes" <?php
						if ($mcDefault == $id) print 'checked' ?>>
						<span class="custom-checkbox"><i class="icon-ok"></i></span> &nbsp;Использовать по умолчанию
					</label>

				</div>

			</div>

			<div class="pull-aright">

				<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

			</div>

		</DIV>

	</FORM>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.8;
		var hh3 = ($('div[data-id="deals"]').is('div')) ? $('div[data-id="deals"]').actual('outerHeight') : 0;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3;

		if (!isMobile) {

			if ($(window).width() > 990)
				$('#dialog').css({'width': '800px'});
			else
				$('#dialog').css('width', '90vw');

			$('#formtabs').css({'max-height': hh2 + 'px'});

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3 - 120;

			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

			$('#dialog').css({'width': '100vw'});

		}

		$(function () {

			$("#dir_osnovanie").autocomplete("content/helpers/client.helpers.php?action=recvisites&tip=osnovanie&char=0", {
				autofill: true,
				minChars: 0,
				cacheLength: 0,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1
			});
			$("dir_status").autocomplete("content/helpers/client.helpers.php?action=recvisites&tip=appointment&char=1", {
				autofill: true,
				minChars: 0,
				cacheLength: 0,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1
			});

		});

		$('#inn').suggestions({
			token: $dadata,
			type: "PARTY",
			count: 5,
			onSelect: function (suggestion) {

				var dir, sdir, dirName;

				dir = (suggestion.data.management !== undefined) ? suggestion.data.management.name : suggestion.data.name.full;

				$('#inn').val(suggestion.data.inn);
				$('#name_ur').val(suggestion.data.name.full_with_opf);
				$('#name_shot').val(suggestion.data.name.short_with_opf);
				$('#okpo').val(suggestion.data.okpo);
				$('#ogrn').val(suggestion.data.ogrn);
				$('#address_yur').val(suggestion.data.address.data.postal_code + ', ' + suggestion.data.address.value);
				$('#address_post').val(suggestion.data.address.data.postal_code + ', ' + suggestion.data.address.value);

				//если это НЕ ИП
				if (suggestion.data.management !== undefined) {

					dir = suggestion.data.management.name;
					sdir = dir.split(' ');
					dirName = ucfirst(sdir[0]) + ' ' + sdir[1].charAt(0) + '. ' + sdir[2].charAt(0) + '.';

					$('#kpp').val(suggestion.data.kpp);
					$('#dir_name').val(ucfirst(suggestion.data.management.post) + ' ' + suggestion.data.management.name);
					$('#dir_status').val(ucfirst(suggestion.data.management.post));
					$('#dir_signature').val(dirName);

				}
				else {

					dir = suggestion.data.name.full;
					sdir = dir.split(' ');
					dirName = ucfirst(sdir[0]) + ' ' + sdir[1].charAt(0) + '. ' + sdir[2].charAt(0) + '.';

					$('#kpp').val('0');
					$('#dir_name').val(suggestion.data.name.full_with_opf);
					$('#dir_status').val(ucfirst(suggestion.data.opf.full));
					$('#dir_signature').val(dirName);
					$('#dir_osnovanie').val('Свидетельства о регистрации индивидуального предпринимателя № .. от ..');

				}

			}
		});

		$("#ilogo").off('change');
		$("#ilogo").on('change', function () {

			//Get count of selected files
			var countFiles = $(this)[0].files.length;
			var imgPath = $(this)[0].value;
			var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();

			if (extn === "gif" || extn === "png" || extn === "jpg" || extn === "jpeg") {

				if (typeof (FileReader) != "undefined") {

					//loop for each file selected for uploaded.
					for (var i = 0; i < countFiles; i++) {

						var reader = new FileReader();

						reader.onload = function (e) {

							$('#dlogo').css({'background': 'url(' + e.target.result + ') center no-repeat', 'background-size': 'contain'});

						};

						reader.readAsDataURL($(this)[0].files[i]);

					}

				}
				else
					Swal.fire("Упс, ваш браузер совсем не поддерживает технологию предпросмотра FileReader.");

			}
			else {

				$('#ilogo').val('');
				$('#dlogo').css({'background': 'none'});

				Swal.fire("Please, only Images!");

			}


		});

		$("#stamp").off('change');
		$("#stamp").on('change', function () {

			//Get count of selected files
			var countFiles = $(this)[0].files.length;
			var imgPath = $(this)[0].value;
			var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();

			if (extn === "gif" || extn === "png" || extn === "jpg" || extn === "jpeg") {

				if (typeof (FileReader) != "undefined") {

					//loop for each file selected for uploaded.
					for (var i = 0; i < countFiles; i++) {

						var reader = new FileReader();

						reader.onload = function (e) {

							$('#dstamp').css({'background': 'url(' + e.target.result + ') center no-repeat', 'background-size': 'contain'});

						};

						reader.readAsDataURL($(this)[0].files[i]);

					}

				}
				else
					Swal.fire("Упс, ваш браузер совсем не поддерживает технологию предпросмотра FileReader.");

			}
			else {

				$('#stamp').val('');
				$('#dstamp').css({'background': 'none'});

				Swal.fire("Please, only Images!");

			}


		});

	</script>
	<?php

}
if ($action == 'edit.rs') {

	$id  = (int)$_REQUEST['id'];
	$cid = (int)$_REQUEST['cid'];

	$ndsDefault = '20';
	$bloc       = 'no';

	if ($id > 0) {

		$result     = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE id = '$id' and identity = '$identity'");
		$title      = $result["title"];
		$rs         = $result["rs"];
		$bankr      = explode(";", $result["bankr"]);
		$tip        = $result["tip"];
		$ostatok    = $result["ostatok"];
		$bloc       = $result["bloc"];
		$isDefault  = $result["isDefault"];
		$ndsDefault = $result["ndsDefault"];

	}
	?>
	<DIV class="zagolovok">Изменить Расчетный счет:</DIV>
	<FORM method="post" action="content/admin/<?php
	echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.rs.on">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">
		<INPUT name="cid" id="cid" type="hidden" value="<?= $cid ?>">

		<div class="flex-container flex-vertical box--child pl10 pr10" style="max-height: 70vh; overflow-y: auto;">

			<div class="flex-container wp100">

				<div class="flex-string wp100 label">Название</div>
				<div class="flex-string wp100">

					<input name="title" id="title" type="text" value="<?= $title ?>" class="wp100 required">
					<div class="fs-07 gray">Например: <b>Основной расчетный счет</b></div>

				</div>

			</div>
			<div class="flex-container wp100">

				<div class="flex-string wp100 label"><?= $recvName['recvBankName'] ?></div>
				<div class="flex-string wp100">

					<input name="bankr[2]" id="bankr[2]" type="text" class="wp100" value="<?= $bankr[2] ?>">
					<div class="fs-07 gray">Например: <b>ФИЛИАЛ ОАО "УРАЛСИБ" В Г.ПЕРМЬ, г. ПЕРМЬ </b></div>

				</div>

			</div>

			<div class="flex-container wp50 mt10 pr5">

				<div class="flex-string wp100 label">Тип счета</div>
				<div class="flex-string wp100">

					<select name="tip" id="tip" class="wp100">
						<option value="bank" <?= ( $tip == 'bank' ? 'selected' : '' ) ?>>Банковский счет</option>
						<option value="kassa" <?= ( $tip == 'kassa' ? 'selected' : '' ) ?>>Касса (наличные)</option>
					</select>
					<div class="fs-07 gray">&nbsp;</div>

				</div>

			</div>
			<div class="flex-container wp50 mt10 pl5">

				<div class="flex-string wp100 label"><?= $recvName['recvBankBik'] ?></div>
				<div class="flex-string wp100 relativ">

					<input name="bankr[0]" id="bankr[0]" type="text" class="wp100" value="<?= $bankr[0] ?>">
					<div class="fs-07 gray">9 цифр, Например: <b>045744863</b></div>
					<span id="limit" class="idel hidden1"></span>

				</div>

			</div>

			<div class="flex-container wp50 mt10 pr5">

				<div class="flex-string wp100 label"><?= $recvName['recvBankRs'] ?></div>
				<div class="flex-string wp100">

					<input name="rs" id="rs" type="text" class="wp100" value="<?= $rs ?>">
					<div class="fs-07 gray">Номер счета компании в банке, <b>20 цифр</b></div>

				</div>

			</div>
			<div class="flex-container wp50 mt10 pl5">

				<div class="flex-string wp100 label"><?= $recvName['recvBankKs'] ?></div>
				<div class="flex-string wp100">

					<input name="bankr[1]" id="bankr[1]" type="text" class="wp100" value="<?= $bankr[1] ?>">
					<div class="fs-07 gray">20 цифр, Например: <b>30101810300000000863</b></div>

				</div>

			</div>

			<div class="flex-container wp50 mt10 pr5">

				<div class="flex-string wp100 label">Остаток средств</div>
				<div class="flex-string wp100">

					<input name="ostatok" id="ostatok" type="text" class="wp100" value="<?= $ostatok ?>">
					<div class="fs-07 gray">&nbsp;</div>

				</div>

			</div>
			<div class="flex-container wp50 mt10 pl5">

				<div class="flex-string wp100 label">НДС по умолчанию</div>
				<div class="flex-string wp100">

					<input name="ndsDefault" id="ndsDefault" type="text" class="wp100" value="<?= $ndsDefault ?>">
					<div class="fs-07 gray">&nbsp;</div>

				</div>

			</div>

			<div class="flex-container wp100">

				<div class="flex-string wp100 label">Опции</div>
				<div class="flex-string wp100 infodiv noBold">

					<div class="p5 pb5">

						<label>
							<input id="isDefault" name="isDefault" type="checkbox" value="yes" <?= ( $isDefault == 'yes' ? 'checked' : '' ) ?>>&nbsp;Использовать по-умолчанию&nbsp;
						</label>

					</div>

					<div class="p5 pb5">

						<label><input id="bloc" name="bloc" type="checkbox" value="yes" <?= ( $bloc == 'yes' ? 'checked' : '' ) ?>>&nbsp;Счет заблокирован&nbsp;</label>

					</div>

					<div class="mt10 fs-10 text-center red Bold">Заблокированный счет не отображается и не может принимать поступления</div>

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>

	</FORM>
	<script>

		$('#dialog').css('width', '808px').center();

		$('#bankr\\[0\\]').off('change keyup');
		$('#bankr\\[0\\]').on('change keyup', function () {

			var bik = $('#bankr\\[0\\]').val();
			var url = 'content/helpers/client.helpers.php?action=getBIK&bik=' + bik;

			$('#limit').append('<img src="/assets/images/loading.svg" height="10">');

			if (bik !== '') {

				$.getJSON(url, function (obj) {

					if (obj.name !== '') {

						$('#bankr\\[2\\]').val(obj.name + ', ' + obj.city);
						$('#bankr\\[1\\]').val(obj.ks);

					}
					//else Swal.fire('Проблемы соединения с сервером','','warning');

				});

			}
			else Swal.fire('Укажите БИК банка', '', 'warning');

			$('#limit').empty();

		});

	</script>
	<?php
}
if ($action == 'edit.signer') {

	$id   = $_REQUEST['id'];
	$mcid = $_REQUEST['mcid'];

	$mc = [];

	//$mc['signature'] = "signature.png";

	if ($id > 0) {

		$mc   = $db -> getRow("SELECT * FROM {$sqlname}mycomps_signer WHERE id = '$id' and identity = '$identity'");
		$mcid = $mc['mcid'];

	}

	?>
	<DIV class="zagolovok">Редактирование подписанта Компании</DIV>
	<FORM method="post" action="/content/admin/<?php
	echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.signer.on">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">
		<INPUT name="mcid" id="mcid" type="hidden" value="<?= $mcid ?>">

		<div id="formtabse" class="pl10 pr10" style="max-height: 70vh; overflow-y: auto;">

			<div class="flex-container">

				<div class="flex-string wp100 label">Подпись Руководителя</div>
				<div class="flex-string wp100">
					<input name="signature" id="signature" type="text" class="wp100 required" value="<?= $mc['signature'] ?>">
					<div class="fs-07 gray">Например: <b>Иванов И.И.</b></div>
				</div>

			</div>
			<div class="flex-container">

				<div class="flex-string wp100 label">Должность руководителя</div>
				<div class="flex-string wp100">
					<input name="status" id="status" type="text" class="wp100 required" value="<?= $mc['status'] ?>"><br>
					<div class="fs-07 gray">Например: <b>Генеральный директор</b></div>
				</div>

			</div>
			<div class="flex-container">

				<div class="flex-string wp100 label">В лице Руководителя</div>
				<div class="flex-string wp100">
					<input name="title" id="title" type="text" class="wp100 required" value="<?= $mc['title'] ?>">
					<div class="fs-07 gray">Например: <b>Генерального директора Иванова Ивана Ивановича</b></div>
				</div>

			</div>
			<div class="flex-container">

				<div class="flex-string wp100 label">Действующего на основании</div>
				<div class="flex-string wp100">
					<input name="osnovanie" id="osnovanie" type="text" class="wp100 required" value="<?= $mc['osnovanie'] ?>"><br>
					<div class="fs-07 gray">Например: <b>Доверенности №128 от 10.01.2013 г.</b></div>
				</div>

			</div>
			<div class="flex-container">

				<div class="flex-string wp100 label">Загрузить Факсимиле</div>
				<div class="flex-string wp100">

					<DIV id="uploads"><input type="file" name="stamp" id="stamp" class="file"></DIV>
					<div class="fs-07">Используйте файлы с расширением<b>gif, png с прозрачным фоном</b>.</div>

					<div style="border:2px solid #ccc; height:160px; background: url(/assets/images/transparent.png) center no-repeat;">
						<div id="dstamp" style="height:100%; background: url(<?= $uploaddir.$mc['stamp'] ?>) center no-repeat; background-size:contain;"></div>
					</div>

				</div>

			</div>

		</div>

		<hr>

		<DIV class="button-pane">

			<div class="pull-aright">

				<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

			</div>

		</DIV>

	</FORM>
	<script>

		if (!isMobile) {
			$('#dialog').css({'width': '600px'});
		}
		else {
			$('#dialog').css({'width': '100vw'});
		}

		$(function () {

			$("#osnovanie").autocomplete("content/helpers/client.helpers.php?action=recvisites&tip=osnovanie&char=0", {
				autofill: true,
				minChars: 0,
				cacheLength: 0,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1
			});
			$("#status").autocomplete("content/helpers/client.helpers.php?action=recvisites&tip=appointment&char=0", {
				autofill: true,
				minChars: 0,
				cacheLength: 0,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1
			});

		});

		$("#stamp").off('change');
		$("#stamp").on('change', function () {

			//Get count of selected files
			var countFiles = $(this)[0].files.length;
			var imgPath = $(this)[0].value;
			var extn = imgPath.substring(imgPath.lastIndexOf('.') + 1).toLowerCase();

			if (extn === "gif" || extn === "png" || extn === "jpg" || extn === "jpeg") {

				if (typeof (FileReader) != "undefined") {

					//loop for each file selected for uploaded.
					for (var i = 0; i < countFiles; i++) {

						var reader = new FileReader();

						reader.onload = function (e) {

							$('#dstamp').css({'background': 'url(' + e.target.result + ') center no-repeat', 'background-size': 'contain'});

						};

						reader.readAsDataURL($(this)[0].files[i]);

					}

				}
				else
					Swal.fire("Упс, ваш браузер совсем не поддерживает технологию предпросмотра FileReader.");

			}
			else {

				$('#stamp').val('');
				$('#dstamp').css({'background': 'none'});

				Swal.fire("Please, only Images!");

			}


		});

	</script>
	<?php

}

if ($action == "delete.rs") {

	$id    = $_REQUEST['id'];
	$res   = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE id='$id' and identity = '$identity'");
	$title = $res["title"];
	?>
	<div class="zagolovok">Удалить счет "<?= $title ?>"</div>
	<FORM action="content/admin/<?php
	echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="delete.rs.do" id="action">

		<div class="flex-container flex-vertical box--child pl10 pr10">

			<div class="flex-container wp100">

				<div class="flex-string wp100 label">Новый р.счет</div>
				<div class="flex-string wp100">

					<select name="newid" id="newid" class="wp100 required">
						<option value="">--выбрать--</option>
						<?php
						$res = $db -> getAll("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER by name_shot");
						foreach ($res as $data) {

							print '<optgroup label="'.$data['name_shot'].'"></optgroup>';

							$re = $db -> getAll("SELECT * FROM {$sqlname}mycomps_recv WHERE id != '".$id."' and bloc != 'yes' and cid = '".$data['id']."' and identity = '$identity' ORDER by title");
							foreach ($re as $da) {

								print '<option value="'.$da['id'].'">&nbsp;&nbsp;&rarr;&nbsp;'.$da['title'].'</option>';

							}
						}
						?>
					</select>

				</div>

			</div>

			<div class="flex-container wp100">

				<div class="flex-string wp100 label"></div>
				<div class="flex-string wp100">

					<label>
						<input type="checkbox" name="dogChange" id="dogChange" value="yes" checked="checked" class="inline">&nbsp;Исправить компанию в сделках
					</label>
					<div class="fs-09 pl20 blue">В открытых (активных) сделках будет установлена новая Компания, соответствующая выбранному расч.счету.</div>


				</div>

			</div>

		</div>

		<div class="infodiv mt10">

			<b>Укажите расч.счет, на который будут перенесены привязанные не оплаченные счета и не проведенные оплаты.</b> У проведенных расходов и оплаченных счетов будет снята привязка к указанному расч.счету.

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<script>

		$('#dialog').css('width', '600px');

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (!em)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.fadeTo(10, 1).empty().css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});
	</script>
	<?php
	exit();
}
if ($action == "delete.signer") {

	$id    = $_REQUEST['id'];
	$res   = $db -> getRow("SELECT * FROM {$sqlname}mycomps_signer WHERE id='$id' and identity = '$identity'");
	$title = $res["signature"];
	?>
	<div class="zagolovok">Удалить подписанта "<?= $title ?>"</div>
	<FORM action="content/admin/<?php
	echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input type="hidden" id="mcid" name="mcid" value="<?= $res["mcid"] ?>">
		<input name="action" type="hidden" value="delete.signer.do" id="action">

		<div class="flex-container flex-vertical box--child pl10 pr10">

			<div class="flex-container wp100">

				<div class="flex-string wp100 label">Новый подписант</div>
				<div class="flex-string wp100">

					<select name="newid" id="newid" class="wp100 required">
						<option value="">--выбрать--</option>
						<?php
						$res = $db -> getAll("SELECT * FROM {$sqlname}mycomps_signer WHERE mcid = '$res[mcid]' AND identity = '$identity' ORDER by signature");
						foreach ($res as $data) {

							print '<option value="'.$data['id'].'">'.$data['signature'].': '.$data['status'].'</option>';

						}
						?>
					</select>

				</div>

			</div>

		</div>

		<div class="infodiv mt10">
			Укажите подписанта, который будет указан в привязанных документах, счетах и актах.
		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<script>

		$('#dialog').css('width', '400px');

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (!em)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.fadeTo(10, 1).empty().css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});

	</script>
	<?php
	exit();
}

if ($action == '') {

	$helper = json_decode(file_get_contents($rootpath.'/cash/helper.json'), true)
	?>

	<h2>&nbsp;Раздел: "<?php
		echo $fieldsNames['dogovor']['mcid']; ?>"</h2>	<h3 class="gray-dark fs-09 pl10">Стандарт: Компании и Счета</h3>

	<?php
	$result = $db -> getAll("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER by name_ur");
	foreach ($result as $comp) {

		print '
		<div class="flex-container box--child p10 fs-11 no-border graybg Bold hidden-iphone1 sticked--top p10">
	
			<div class="flex-string wp70">
				<span class="gray2">ID '.$comp['id'].':</span>
				'.$comp['name_shot'].'&nbsp;
				<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit&id='.$comp['id'].'\');" class="blue" title="Изменить"><i class="icon-pencil blue"></i></a>
				&nbsp;'.( $mcDefault == $comp['id'] ? "<span class='red'>По умолчанию</span>" : "" ).'
			</div>
			<div class="flex-string wp30 hidden-iphone hidden">
				'.( count($result) > 1 ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)deleteCompany(\''.$comp['id'].'\')" class="gray" title="Удалить"><i class="icon-cancel-circled"></i>Удалить</a>' : '' ).'
			</div>
	
		</div>
		';

		$res = $db -> getAll("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$comp['id']."' and identity = '$identity' ORDER by id");
		foreach ($res as $rs) {

			$color  = '#CCFF99';
			$bloc   = '<i class="icon-lock-open green" title="Активный"></i>';
			$class  = 'blue';
			$tip    = '<i class="icon-town-hall broun fs-20" title="Банк"></i>';
			$df     = '';
			$snalog = ' <span class="green">Не облагается</span> ';

			if ($rs['tip'] == 'kassa') {
				$tip = '<i class="icon-briefcase-1 blue fs-20" title="Касса"></i>';
			}

			if ($rs['bloc'] == 'yes') {

				$color = '#FFCCCC';
				$bloc  = '<i class="icon-lock red" title="Блокирован"></i>';
				$class = 'gray';

			}

			if ($rs['isDefault'] == 'yes') {
				$df = ' <sup class="red">По-умолчанию</sup> ';
			}

			if ((float)$rs['ndsDefault'] > 0) {
				$snalog = ' <span class="blue">'.$rs['ndsDefault'].'%</span> ';
			}

			$bankr = explode(";", $rs['bankr']);

			print '
			<div class="flex-container box--child p10 border-bottom relativ ha '.( $rs['bloc'] == 'yes' ? 'gray graybg-sub' : '' ).' '.( $rs['isDefault'] == 'yes' ? 'greenbg-sub' : '' ).'">
				
				<div class="pull-right visible-iphone mr10">
					<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit.rs&id='.$rs['id'].'\');" class="gray blue"><i class="icon-pencil"></i></a>
				</div>
		
				<div class="flex-string wp10">'.$tip.$bloc.'</div>
				<div class="flex-string wp60">
					<div class="fs-12 Bold">
						<span class="gray2">ID '.$rs['id'].':</span>
						'.$rs['title'].$df.'
					</div>
					<div class="mt10">
						Остаток: <span class="Bold">'.num_format($rs['ostatok']).'</span> '.$valuta.', Налоги: <span>'.$snalog.'</span>
					</div>
				</div>
				<div class="flex-string wp30 hidden-iphone">
				
					<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit.rs&id='.$rs['id'].'\');" class="button bluebtn dotted" title="Редактировать"><i class="icon-pencil"></i> Редактировать</a>
				
					<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись? Действие нельзя отменить.\');if (cf)doLoad(\'content/admin/'.$thisfile.'?action=delete.rs&id='.$rs['id'].'\');" class="button redbtn dotted"><i class="icon-cancel-circled"></i>Удалить</a>
					
				</div>
		
			</div>
			';

		}

		if (empty($res)) {
			print '<div class="attention mt10">Расчетных счетов нет</div>';
		}

		print '
		<div class="mt10 mb20 infodiv graybg-sub p10">
		
			<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit.rs&cid='.$comp['id'].'\');" class="button greenbtn dotted"><i class="icon-plus-circled"></i>Расчетный счет</a>
			
			'.( count($result) > 1 ? '<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)deleteCompany(\''.$comp['id'].'\')" class="button redbtn dotted pull-aright" title="Удалить"><i class="icon-cancel-circled"></i>Удалить<span class="hidden-iphone"> компанию</span></a>' : '' ).'
			<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit&id='.$comp['id'].'\');" class="button bluebtn dotted pull-aright" title="Изменить"><i class="icon-pencil"></i>Изменить<span class="hidden-iphone"> компанию</span></a>
			
		</div>
		';

		?>
		<!--Signers-->
		<div class="fs-12 Bold wp100 mb10">Подписанты</div>

		<div class="xgrid-col-4 wp100">

			<?php
			$res = $db -> getAll("SELECT * FROM {$sqlname}mycomps_signer WHERE mcid = '".$comp['id']."' and identity = '$identity' ORDER by id");
			foreach ($res as $signer) {

				print '
				<div class="infodiv dotted bgwhite ha relativ">
					<div class="pull-aright">
						<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit.signer&id='.$signer['id'].'\');" class="blue" title="Изменить"><i class="icon-pencil"></i></a>
						<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=delete.signer&id='.$signer['id'].'\');" class="red" title="Удалить"><i class="icon-cancel-circled"></i></a>
					</div>
					<div class="fs-11 Bold">'.$signer['signature'].'</div>
					<div class="fs-09">'.$signer['status'].'</div>
					<div class="fs-09 gray-dark">'.$signer['osnovanie'].'</div>
				</div>
				';

			}

			if (empty($res)) {
				print '<div class="gray-dark">Дополнительные подписанты не заданы</div>';
			}
			?>

			<div class="space-20"></div>

		</div>

		<div class="wp100 box--child mt10">

			<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php
			echo $thisfile; ?>?action=edit.signer&mcid=<?= $comp['id'] ?>');" class="button bluebtn dotted" title="Добавить"><i class="icon-plus-circled"></i>Добавить Подписанта</a>

		</div>

		<div class="space-20"></div>
		<?php

	}
	?>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php
		echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить Компанию</a>

	</div>

	<div class="pagerefresh refresh--icon admn green" onclick="doLoad('content/admin/<?php
	echo $thisfile; ?>?action=edit');" title="Добавить Компанию"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/8')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<?php
}
?>

<script>

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

			//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');

			razdel(hash);

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			DClose();

		}
	});

	function deleteCompany(id) {

		var url = 'content/admin/<?php echo $thisfile; ?>?action=delete.company&id=' + id;

		$.post(url, function (data) {

			$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		});

	}

</script>