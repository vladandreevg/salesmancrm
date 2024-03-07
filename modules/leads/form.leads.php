<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Elements;
use Salesman\Leads;

error_reporting(E_ERROR);
//error_reporting(E_ALL);

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
$id     = $_REQUEST['id'];

$ress         = $db -> getOne("select usersettings from {$sqlname}user where iduser='".$iduser1."' and identity = '$identity'");
$usersettings = json_decode($ress, true);

//настройки модуля для аккаунта
$mdwset       = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");
$leadsettings = json_decode($mdwset['content'], true);
$coordinator  = $leadsettings["leadСoordinator"];

$rezult = Leads::REZULTES;
$colors = Leads::COLORS;
$status = Leads::STATUSES;

//загружаем все возможные цепочки и конвертируем в JSON
$mFunnel = json_encode_cyr(getMultiStepList());

//file_put_contents($rootpath."/lead.json", $mFunnel);
//print_r(getMultiStepList());

//доп.напстройки
$customSettings = customSettings('settingsMore');
$timecheck      = ($customSettings['timecheck'] == 'yes') ? 'true' : 'false';

$fieldClient = $fieldsNames['client'];
$fieldPerson = $fieldsNames['person'];
$fieldDeal   = $fieldsNames['dogovor'];

if ($action == "add") {

	$phone1    = $_REQUEST['phone'];
	$messageid = (int)$_REQUEST['messageid'];

	if ($phone1 == 'undefined') {
		$phone1 = '';
	}

	$clientpath = '';
	$title      = '';

	if ($id > 0) {

		$result      = $db -> getRow("SELECT * FROM {$sqlname}leads WHERE id = '$id' and identity = '$identity'");
		$datum       = $result['datum'];
		$stat        = $result['status'];
		$title       = $result['title'];
		$email       = $result['email'];
		$site        = $result['site'];
		$phone       = $result['phone'];
		$companyy    = $result['company'];
		$description = str_replace("\t", "", $result['description']);
		$ip          = $result['ip'];
		$city        = $result['city'];
		$country     = $result['country'];
		$iduser      = $result['iduser'];
		$clientpath  = $result['clientpath'];
		$rezultd     = $result['rezult'];
		$partner     = $result['partner'];
		$clid        = $result['clid'];
		$pid         = $result['pid'];

		$utm_source   = $result['utm_source'];
		$utm_medium   = $result['utm_medium'];
		$utm_campaign = $result['utm_campaign'];
		$utm_term     = $result['utm_term'];
		$utm_content  = $result['utm_content'];
		$utm_referrer = $result['utm_referrer'];

	}

	if (!isset($companyy)) {
		$companyy = 'Новый клиент';
	}

	//print $messageid;

	// добавление из Почты
	if ($messageid > 0) {

		$result      = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'");
		$description = htmlspecialchars_decode($result['content']);
		$iduser      = $result['iduser'];
		$uid         = $result['uid'];

		$result = $db -> getRow("SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$messageid' and identity = '$identity'");
		$title  = $result['name'];
		$email  = $result['email'];
		$clid   = $result['clid'];
		$pid    = $result['pid'];

		$description = (isHTML($description)) ? html2text($description) : $description;

		$data = html2data($description);

		$parsed = Leads ::parseText($description, ":");

		if ($parsed['title'] != '') {
			$title = $parsed['title'];
		}
		if ($data['phone'] != '') {
			$phone = $data['phone'];
		}
		if ($data['site'] != '') {
			$site = $data['site'];
		}

	}
	elseif ($phone1 != '') {

		$rez = getxCallerID($phone1);

		$clid = $rez['clid'];
		$pid  = $rez['pid'];

	}

	//print $phone;
	$phone = ($phone != '' && $phone1 != '') ? $phone1.",".$phone : $phone;

	$clientReq = $personReq = 'required';
	$clientVis = $personVis = '';

	//Если клиент - Юр.лицо
	if (!$otherSettings['clientIsPerson']) {
		$personReq = '';
		$personVis = 'hidden';
	}

	?>
	<div class="zagolovok">Добавить/Изменить лид</div>

	<form method="post" action="/modules/leads/core.leads.php" enctype="multipart/form-data" name="LeadForm" id="LeadForm" autocomplete="off">
		<input name="action" id="action" type="hidden" value="add">
		<input name="id" id="id" type="hidden" value="<?= $id ?>">

		<div class="row pad10 mt5 div-info">

			<div class="client flex-container wp100 pb5 <?= $clientVis ?>">

				<span class="flex-string wp5 pt5 hidden-iphone"><i class="icon-building blue"></i></span>
				<span class="relativ cleared flex-string wp95">
				<INPUT name="clid" type="hidden" id="clid" value="<?= $clid ?>">
				<INPUT name="client" id="client" type="text" placeholder="Выбор <?= $lang['face']['ClientName']['1'] ?>" value="<?= current_client($clid) ?>" class="wp100">
				<span class="idel clearinputs pr0" title="Очистить"><i class="icon-block-1 red"></i></span>
			</span>

			</div>

			<div class="person flex-container wp100 pt5 mt10 <?= $personVis ?>">

				<span class="flex-string wp5 pt5 hidden-iphone"><i class="icon-user-1 blue"></i></span>
				<span class="relativ cleared flex-string wp95">
				<INPUT name="pid" type="hidden" id="pid" value="<?= $pid ?>">
				<INPUT name="person" id="person" type="text" placeholder="Выбор <?= $lang['face']['ContactName']['1'] ?>" value="<?= current_person($pid) ?>" class="wp100">
				<span class="idel clearinputs pr0" title="Очистить"><i class="icon-block-1 red"></i></span>
			</span>

			</div>

		</div>

		<div id="formtabs" style="overflow-y: auto; max-height: 90vh; overflow-x: hidden" class="pt10">

			<?php
			$hooks -> do_action("lead_form_before", $_REQUEST);
			?>

			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Источник:</div>
				<div class="flex-string wp80 pl10 relativ">

					<?php
					$pathDefault = ($client['clientpath'] > 0) ? $client['clientpath'] : $GLOBALS['pathDefault'];

					$element = new Elements();
					print $su = $element -> ClientpathSelect('clientpath', [
						"class" => [
							"wp95",
							$param['requered']
						],
						"sel"   => $pathDefault,
						"data"  => 'data-class="'.$param['requered'].'"'
					]);
					?>
					<?php if (!$otherSettings['guidesEdit']) { ?>
						<span class="hidden-iphone"><a href="javascript:void(0)" onclick="add_sprav('clientpath','clientpath')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>
					<?php } ?>

				</div>

			</div>

			<hr>

			<div class="flex-container togglerbox hand fs-12 Bold bluebg-sub" data-id="utmbox">

				<div class="flex-string wp100 mt10 mb10 div-center">
					&nbsp;UTM-метки&nbsp;<i class="icon-angle-down" id="mapic"></i>
				</div>

			</div>

			<div class="bluebg-sub pt10 pb10 hidden" id="utmbox">

				<div class="flex-container mb10 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип трафика:</div>
					<div class="flex-string wp80 pl10">
						<input type="text" name="utm_medium" id="utm_medium" value="<?= $utm_medium ?>" class="wp95" placeholder="utm_medium">
					</div>

				</div>
				<div class="flex-container mb10 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Рекламная кампания:</div>
					<div class="flex-string wp80 pl10">
						<input type="text" name="utm_campaign" id="utm_campaign" value="<?= $utm_campaign ?>" class="wp95" placeholder="utm_campaign">
					</div>

				</div>
				<div class="flex-container mb10 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Ключевая фраза:</div>
					<div class="flex-string wp80 pl10">
						<input type="text" name="utm_term" id="utm_term" value="<?= $utm_term ?>" class="wp95" placeholder="utm_term">
					</div>

				</div>
				<div class="flex-container mb10 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Доп.информация:</div>
					<div class="flex-string wp80 pl10">
						<input type="text" name="utm_content" id="utm_content" value="<?= $utm_content ?>" class="wp95" placeholder="utm_content">
					</div>

				</div>

			</div>

			<hr>

			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Ответственный:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$element = new Elements();
					print $element -> UsersSelect("iduser", [
						"sel"    => $iduser,
						"active" => true,
						"class"  => "wp95",
						"users"  => $leadsettings['leadOperator']
					]);
					?>
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child <?= $personVis ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Контакт:</div>
				<div class="flex-string wp80 pl10">
					<input type="text" name="title" id="title" class="<?= $personReq ?> wp95" value="<?= $title ?>">
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child <?= $clientVis ?>">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Компания:</div>
				<div class="flex-string wp80 pl10">
					<input type="text" name="company" id="company" value="<?= $companyy ?>" class="<?= $clientReq ?> wp95">
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Email:</div>
				<div class="flex-string wp80 pl10">
					<input type="text" name="email" id="email" value="<?= $email ?>" class="wp95">
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Телефон:</div>
				<div class="flex-string wp80 pl10" id="vtel">
					<?php
					$format_phone = $GLOBALS['format_phone'];
					//print $phone;
					if ($format_phone != '') {

						if ($phone != '') {

							$phonep = yexplode(",", $phone);
							//print_r($phonep);
							foreach ($phonep as $phone) {

								$adder = '';

								if ($i == (count($phonep) - 1)) {
									$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
								}

								?>
								<div class="phoneBlock paddbott5 relativv">
									<INPUT name="tel[]" type="text" class="phone w250" id="tel[]" alt="phone" autocomplete="off" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vtel" data-action="valphone" data-type="person.helpers">
									<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span><?= $adder ?>
								</div>
								<?php
							}
						}
						else {
							?>
							<div class="phoneBlock paddbott5 relativv">
								<INPUT name="tel[]" type="text" class="phone w250" id="tel[]" alt="phone" autocomplete="off" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vtel" data-action="valphone" data-type="person.helpers">
								<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>
								<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
							</div>
							<?php
						}
					}
					else {
						?>
						<div class="phoneBlock paddbott5 relativv">
							<INPUT name="tel" type="text" class="phone" style="width: 93%;" id="tel" alt="phone" autocomplete="off" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vtel" data-action="valphone" data-type="person.helpers">
							<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя</div>
						</div>
						<?php
					}
					?>
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сайт:</div>
				<div class="flex-string wp80 pl10">
					<input type="text" name="site" id="site" value="<?= $site ?>" class="wp95">
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Адрес:</div>
				<div class="flex-string wp80 pl10">
					<input type="text" name="city" id="city" value="<?= $city ?>" class="wp95" data-type="address">
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Описание:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="description" id="description" rows="5" class="wp95"><?= trim($description) ?></textarea>
				</div>

			</div>
			<div class="flex-container mt20 mb10 box--child">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Партнер:</div>
				<div class="flex-string wp80 pl10">
					<select name="partner" id="partner" class="wp95">
						<option value="">--выбор--</option>
						<?php
						$result = $db -> getAll("SELECT * FROM {$sqlname}clientcat WHERE type = 'partner' and identity = '$identity' ORDER by title");
						foreach ($result as $data) {

							$s = ($data['clid'] == $partner) ? "selected" : "";

							print '<option value="'.$data['clid'].'" '.$s.'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

		</div>

		<hr>

		<div class="pull-aright button--pane">

			<a href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onClick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<?php

	$hooks -> do_action("lead_form_after", $_REQUEST);

}

if ($action == "view") {

	$lead = Leads ::info($id);

	//print_r($lead);

	/*
	$result      = $db -> getRow("SELECT * FROM {$sqlname}leads WHERE id = '$id' and identity = '$identity'");
	$datum       = $result['datum'];
	$datumdo     = $result['datum_do'];
	$stat        = $result['status'];
	$title       = $result['title'];
	$email       = $result['email'];
	$site        = $result['site'];
	$phone       = $result['phone'];
	$company     = $result['company'];
	$description = $result['description'];
	$ip          = $result['ip'];
	$city        = $result['city'];
	$country     = $result['country'];
	if ($country != '') $city = $country.", ".$city;
	$iduser     = $result['iduser'];
	$clientpath = $result['clientpath'];
	$rezultd    = $result['rezult'];
	$partner    = $result['partner'];
	$ida        = $result['id'];
	$rezz       = $result['rezz'];
	$clid       = $result['clid'];
	$pid        = $result['pid'];
	$did        = $result['did'];
	*/

	if ((int)$lead['id'] == 0) {
		print '<div class="zagolovok">Просмотр интереса</div><span><i class="icon-attention red icon-3x pull-left"></i></span><b>К сожалению просмотр не возможен<br>Отсутствует доступ или запись не существует.</b>';
		exit;
	}

	switch ($lead['status']) {
		case "0":

			if ($coordinator == $iduser1)
				$act = '<div class="pull-left">&nbsp;<span id="greenbutton"><A href="javascript:void(0)" onClick="editLead(\''.$id.'\',\'setuser\');" title="Квалифицировать" class="button"><i class="icon-ok white"></i>Квалифицировать</A></span></div>&nbsp;<A href="javascript:void(0)" onClick="editLead(\''.$id.'\',\'workit\');" title="Обработать" class="button"><i class="icon-ok-circled white"></i>Обработать</A>';

			if (!$lead['iduser'])
				$user = '<span class="ellipsis" title="Не определен"><b class="gray"><i class="icon-help-circled"></i>Не определен</b></span>';
			if ($lead['iduser'])
				$user = current_user($lead['iduser']);

		break;
		case "1":

			if ($lead['iduser'] == $iduser1)
				$act = '<div class="pull-left">&nbsp;<span id="orangebutton"><A href="javascript:void(0)" onClick="editLead(\''.$id.'\',\'setuser\');" title="Квалифицировать" class="button"><i class="icon-ok white"></i>Квалифицировать</A></span></div>&nbsp;<A href="javascript:void(0)" onClick="editLead(\''.$id.'\',\'workit\');" title="Обработать" class="button"><i class="icon-ok-circled white"></i>Обработать</A>';

			if ($coordinator == $iduser1)
				$act = '<div class="pull-left">&nbsp;<span id="orangebutton"><A href="javascript:void(0)" onClick="editLead(\''.$id.'\',\'setuser\');" title="Квалифицировать" class="button"><i class="icon-ok white"></i>Квалифицировать</A></span></div>&nbsp;<A href="javascript:void(0)" onClick="editLead(\''.$id.'\',\'workit\');" title="Обработать" class="button"><i class="icon-ok-circled white"></i>Обработать</A>';

			if ($lead['iduser']) $user = current_user($lead['iduser']);

			$action = '';

		break;
		case "2":

			if ($lead['iduser'] == $iduser1 || $coordinator == $iduser1) $act = '';
			if ($iduser) $user = current_user($lead['iduser']);
			$action = '';

		break;
		case "3":

			$user   = '<span class="ellipsis" title="Не определен"><b class="gray"><i class="icon-help-circled"></i>Не определен</b></span>';
			$action = '';

		break;
	}

	?>
	<div class="zagolovok">Просмотр интереса</div>
	<input name="action" id="action" type="hidden" value="view">

	<div id="formtabs" style="overflow-y: auto; max-height: 80vh; overflow-x: hidden" class="box--child">

		<?php if ($user) { ?>
			<div class="flex-container mt10 mb20">

				<div class="flex-string wp20 gray2 fs-12 right-text">Ответственный:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $user ?></div>

			</div>
			<hr>
		<?php } ?>
		<div class="flex-container mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Текущий статус:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<b class="<?= strtr($lead['status'], $colors) ?>"><?= strtr($lead['status'], $status) ?></b></div>

		</div>
		<div class="flex-container mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Создан:</div>
			<div class="flex-string wp80 pl10 fs-12"><?= get_sfdate($lead['datum']) ?></div>

		</div>
		<?php if (get_sfdate($lead['datum_do']) != '') { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Обработан:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= get_sfdate($lead['datum_do']) ?></div>

			</div>
			<div class="flex-container mt10 mb10 orangebg pt10 pb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Время обработки:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= diffDateTime($lead['datum'], $lead['datum_do']) ?></div>

			</div>
		<?php } ?>

		<?php if ($lead['rezult'] > 0) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Результат:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<b class="<?= strtr($lead['rezult'], $colors) ?>"><?= strtr($lead['rezult'], $rezult) ?></b>
				</div>

			</div>
		<?php } ?>
		<?php if ($lead['clid'] > 0) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Клиент:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<a href="javascript:void(0)" onClick="openClient('<?= $lead['clid'] ?>')"><i class="icon-building blue"></i><?= current_client($lead['clid']) ?>
					</a>
				</div>

			</div>
		<?php } ?>
		<?php if ($lead['pid'] > 0) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Контакт:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<a href="javascript:void(0)" onClick="openPerson('<?= $lead['pid'] ?>')"><i class="icon-user-1 green"></i><?= current_person($lead['pid']) ?>
					</a>
				</div>

			</div>
		<?php } ?>
		<?php if ($lead['did'] > 0) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Сделка:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<a href="javascript:void(0)" onClick="openDogovor('<?= $lead['did'] ?>')"><i class="icon-briefcase broun"></i><?= current_dogovor($lead['did']) ?>
					</a></div>

			</div>
		<?php } ?>

		<?php if ($lead['rezz'] != '') { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10 fs-10">
					<div style="width: 100%; word-break: break-all; overflow-y: auto;">
						<?= nl2br($lead['rezz']) ?>
					</div>
				</div>

			</div>
		<?php } ?>

		<div class="infodiv bgwhite dotted">

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">utm_source:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['utm_source'] ?></div>

			</div>

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">utm_medium:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['utm_medium'] ?></div>

			</div>

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">utm_campaign:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['utm_campaign'] ?></div>

			</div>

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">utm_content:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['utm_content'] ?></div>

			</div>

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">utm_referrer:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['utm_referrer'] ?></div>

			</div>

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">ip:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['ip'] ?></div>

			</div>

			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text pt10">UIDS:</div>
				<div class="flex-string wp80 pl10 fs-12">

					<?php
					foreach ($lead['uids'] as $uid) {

						print '<div class="tags">'.$uid['name'].': '.$uid['value'].'</div>';

					}
					?>

				</div>

			</div>

		</div>

		<hr>

		<div class="flex-container mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Имя:</div>
			<div class="flex-string wp80 pl10 fs-12"><?= $lead['title'] ?></div>

		</div>
		<div class="flex-container mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Email:</div>
			<div class="flex-string wp80 pl10 fs-12"><?= link_it($lead['email']) ?></div>

		</div>

		<?php if ($lead['phone']) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Телефон:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= formatPhoneUrl($lead['phone']) ?></div>

			</div>
		<?php } ?>
		<?php if ($lead['site']) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Сайт:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= link_it($lead['site']) ?></div>

			</div>
		<?php } ?>
		<?php if ($lead['company']) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Компания:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['company'] ?></div>

			</div>
		<?php } ?>
		<?php if ($lead['partner']) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Партнер:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= current_client($lead['partner']) ?></div>

			</div>
		<?php } ?>
		<?php if ($lead['country'] || $lead['city']) { ?>
			<div class="flex-container mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Адрес:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $lead['city'] ?></div>

			</div>
		<?php } ?>
		<?php if ($lead['description']) { ?>
			<hr>
			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Описание:</div>
				<div class="flex-string wp80 pl10">
					<div style="word-break: break-all;">
						<?= nl2br($lead['description']) ?>
					</div>
				</div>

			</div>
		<?php } ?>

	</div>

	<hr>

	<div class="pull-aright button--pane">

		<?= $act ?>&nbsp;<a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>

	</div>
	<?php
}

if ($action == "setuser") {

	$iduser = $db -> getOne("SELECT iduser FROM {$sqlname}leads WHERE id = '".$id."' and identity = '$identity'");

	?>
	<div class="zagolovok">Предварительная обработка лида</div>

	<form method="post" action="/modules/leads/core.leads.php" enctype="multipart/form-data" name="LeadForm" id="LeadForm" autocomplete="off">
		<input name="action" id="action" type="hidden" value="setuser"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>

		<div id="formtabs" style="overflow-y: auto; max-height: 90vh; overflow-x: hidden" class="box--child">

			<?php
			$hooks -> do_action("lead_setuserform_before", $_REQUEST);
			?>

			<div class="flex-container mt10 mb20">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Назначить:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$element = new Elements();
					print $element -> UsersSelect("iduser", [
						"sel"    => $iduser1,
						"active" => true,
						"class"  => "wp97",
						"users"  => $leadsettings['leadOperator']
					]);
					?>
				</div>

			</div>

			<hr>

			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Закрыть:</div>
				<div class="flex-string wp80 pl10">
					<select name="rezult" id="rezult" class="wp97">
						<option value="">--выбор--</option>
						<option value="1">Спам</option>
						<option value="2">Дубль</option>
						<option value="4">Не целевой</option>
						<option value="3">Другое</option>
					</select>
					<div class="em fs-09 gray2">Если Интерес не является качественным, то можно закрыть его с указанным результатом.</div>
				</div>

			</div>
			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="rezz" id="rezz" style="height:100px" class="wp97"></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="pull-aright button--pane">

			<div class="pull-left pr10">
				&nbsp;<span id="orangebutton"><A href="javascript:void(0)" onClick="editLead('<?= $id ?>','workit');" title="Обработать" class="button"><i class="icon-ok-circled white"></i>Обработать</A></span>
			</div>

			<a href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>
	<?php

	$hooks -> do_action("lead_setuserform_after", $_REQUEST);

}

if ($action == "workit") {

	$result     = $db -> getRow("SELECT * FROM {$sqlname}leads WHERE id = '$id' and identity = '$identity'");
	$datum      = $result['datum'];
	$stat       = $result['status'];
	$clientpath = $result['clientpath'];
	$city       = $result['city'];
	$country    = $result['country'];

	if ($country != '') $city = $country.", ".$city;

	$description = str_replace([
		"\t",
		"\n\r\n\r"
	], [
		"",
		"\n"
	], $result['description']);

	$ip     = $result['ip'];
	$iduser = $result['iduser'];

	$clientReq = $personReq = 'required';

	//Если клиент - Юр.лицо
	if (!$otherSettings['clientIsPerson']) {

		$person = $result['title'];
		$mail   = $result['email'];
		$tel    = $result['phone'];

		$company = $result['company'];
		$data    = [
			"title"   => $person,
			"email"   => $mail,
			"phone"   => $tel,
			"company" => $company
		];

	}

	//Если клиент - Физ.лицо
	if ($otherSettings['clientIsPerson'] || $leadsettings['leadHideContact']) {

		$mail_url = $result['email'];
		$site_url = $result['site'];
		$phone    = $result['phone'];
		$company  = $result['title'];
		$data     = [
			"title"   => $company,
			"email"   => $mail_url,
			"phone"   => $phone,
			"company" => $company
		];

		$personReq = '';

	}

	$pid  = $result['pid'];
	$clid = $result['clid'];

	if ($company == '') $company = 'Новый клиент Лид №'.$id;

	$json = json_encode_cyr($data, true);

	//Найдем в сделках тип по умолчанию или содержащий ключ "Вход"
	$tid = $db -> getOne("SELECT tid FROM {$sqlname}dogtips WHERE LCASE(title) LIKE '%вход%' or LCASE(title) LIKE '%интерес%' and identity = '$identity' ORDER BY title");

	$thistime   = date('H:00', mktime(date('H') + 2, date('i'), date('s'), date('m'), date('d'), date('Y')) + ($tzone) * 3600);
	$datum_plan = current_datum(-$perDay);
	$datum_task = current_datum();

	if (date('H') > 20)
		$thistime = current_datum(-1)." 09:00";

	else
		$thistime = current_datum()." ".$thistime;

	$dNum = generate_num('dogovor');

	if ($dNum) $dnum = '<span class="smalltxt green">Номер сделки: <b>'.$dNum.'</b> (предварительно)</span>';

	$showperson = (!$otherSettings['clientIsPerson']) ? "" : "hidden";

	if ($leadsettings['leadHideContact']) {
		$showperson = 'hidden';
	}

	if ($clid > 0) {

		$cclient = get_client_info($clid, "yes");
		$address = ($cclient['address'] != '') ? "Адрес в базе: ".$cclient['address'] : "";

	}
	if ($pid > 0) {

		$cperson = get_person_info($pid, "yes");

	}

	$clientpath = ($clientpath > 0) ? $clientpath : $pathDefault;
	?>

	<div class="zagolovok">Обработка лида</div>

	<?php
	$tcount = getOldTaskCount((int)$iduser1);
	if ((int)$otherSettings['taskControl'] > 0 && (int)$otherSettings['taskControlClientAdd'] && (int)$tcount >= (int)$otherSettings['taskControl']) {

		print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания и добавлять Клиентов и Контакты, пока не закроете старые напоминания.</div>';
		exit();

	}
	?>
	<form method="post" action="/modules/leads/core.leads.php" enctype="multipart/form-data" name="LeadForm" id="LeadForm" autocomplete="off">
		<input name="action" id="action" type="hidden" value="workit">
		<input name="id" id="id" type="hidden" value="<?= $id ?>">
		<input name="deal[lid]" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" style="overflow-y: auto; max-height: 90vh; overflow-x: hidden" class="box--child">

			<?php
			$hooks -> do_action("lead_workitform_before", $_REQUEST);
			?>

			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип записи:</div>
				<div class="flex-string wp80 pl10">
					<SELECT name="type" id="type" class="required wp95 typeselect" onchange="switchclient()">
						<OPTION value="client" <?php if (!$otherSettings['clientIsPerson']) print "selected" ?>>Клиент. Юр.лицо</OPTION>
						<OPTION value="person" <?php if ($otherSettings['clientIsPerson']) print "selected" ?>>Клиент. Физ.лицо</OPTION>
					</SELECT>
				</div>

			</div>

			<hr>

			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['clientpath'] ?>:</div>
				<div class="flex-string wp80 pl10">
					<?php
					$element = new Elements();
					print $element -> ClientpathSelect("clientpath", [
						"sel"   => $clientpath,
						"class" => "wp95"
					]);
					?>
				</div>

			</div>

			<div class="wp100 box--child <?= $showperson ?>" id="contactBoxLead">

				<div class="flex-container mt10">

					<div class="flex-string">
						<div id="divider"><b class="green">Контакт</b></div>
					</div>

				</div>
				<div class="flex-container mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['person']['person'] ?>
						<sup class="noBold fs-07">(из заявки)</sup></div>
					<div class="flex-string wp80 pl10 relativ">
						<input type="text" name="personname" id="personname" class="<?= $personReq ?> wp95" value="<?= $person ?>">
					</div>

				</div>

				<?php if ($pid > 0) { ?>
					<div class="flex-container mt10 viewdiv p0 pt10 pb10 mfh-09">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['person']['person'] ?>
							<sup class="noBold fs-07">(из базы)</sup></div>
						<div class="flex-string wp80 pl10 relativ cleared">

							<INPUT name="pid" type="hidden" id="pid" value="<?= $pid ?>">
							<INPUT name="person" id="person" type="text" placeholder="Выбор <?= $lang['face']['ContactName']['1'] ?>" value="<?= current_person($pid) ?>" class="wp95">
							<span class="idel clearinputs pr15 mr15" title="Очистить"><i class="icon-block-1 red"></i></span>

						</div>

					</div>
					<div class="flex-container bgwhite p10 border-bottom">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">&nbsp;&nbsp;</div>
						<div class="flex-string wp80 pl10 pull-aright">
							<div class="Bold fs-11"><b class="red">Внимание!</b> В базе найден похожий Контакт.</div>
							<ul>
								<li>Чтобы добавить
									<u>Новый контакт</u> из заявки очистите поле "из базы" с помощью иконки
									<i class="icon-block-1 red"></i></li>
								<li>Иначе в базу к указанному Контакту будут добавлены <?= $fieldsNames['person']['mail'] ?>, <?= $fieldsNames['person']['tel'] ?> и <?= $fieldsNames['person']['ptitle'] ?> (к существующим) с сохранением имеющегося Имени</li>
							</ul>
						</div>

					</div>
				<?php } ?>

				<?php
				if ($fieldsNames['person']['ptitle']) {

					$fld_var = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_name = 'ptitle' and identity = '$identity'");
					$vars = str_replace(" \n", ",", $fld_var);

					$x = '<div class="smalltxt">Например: Генеральный директор</div>';
					$dx = !empty($vars) ? '' : 'suggestion';

					if( !empty($vars) ) {

						$x = '
						<div class="fs-09 em blue"><em>Двойной клик мышкой для показа вариантов</em></div>
						<script>
							var str = "'.$vars.'";
							var data = str.split(",");
							$(".ptitle").autocomplete(data, {
								autofill: true,
								minLength: 0,
								minChars: 0,
								cacheLength: 5,
								maxItemsToShow: 20,
								selectFirst: true,
								multiple: false,
								delay: 0,
								matchSubset: 2
							})
						</script>';

					}
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['person']['ptitle'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input type="text" name="ptitle" id="ptitle" value="" class="wp95 ptitle <?=$dx?>">
							<?=$x?>
						</div>

					</div>
				<?php } ?>

				<?php
				if ($fieldsNames['person']['mail']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['person']['mail'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input type="text" name="mail" id="mail" value="<?= $mail ?>" class="wp95">
						</div>

					</div>
				<?php } ?>

				<div class="flex-container mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['person']['tel'] ?>:</div>
					<div class="flex-string wp80 pl10">
						<div id="vtel">
							<?php
							if ($format_phone != '') {

								if ($tel != '') {

									$phonep = yexplode(",", $tel);
									//print_r($phonep);
									$nphonep = [];
									foreach ($phonep as $item) {
										$nphonep[] = prepareMobPhone($item);
									}
									//print_r($nphonep);
									$phonep = array_unique($nphonep, SORT_STRING);
									sort($phonep);
									//print_r($phonep);

									for ($i = 0, $iMax = count($phonep); $i < $iMax; $i++) {

										if ($i == (count($phonep) - 1)) $adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
										else $adder = '';
										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="tel[]" type="text" class="phone w250" id="tel[]" alt="phone" autocomplete="off" value="<?= $phonep[ $i ] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vtel" data-action="valphone" data-type="person.helpers">
											<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span><?= $adder ?>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="tel[]" type="text" class="phone w250" id="tel[]" alt="phone" autocomplete="off" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vtel" data-action="valphone" data-type="person.helpers">
										<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>
										<span class="adder hand" title="" data-block="phoneBlock" data-main="vtel" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
									</div>
									<?php
								}

							}
							else {
								?>
								<div class="phoneBlock paddbott5 relativv">
									<INPUT name="tel" type="text" class="phone" style="width: 93%;" id="tel" alt="phone" autocomplete="off" value="<?= $tel ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vtel" data-action="valphone" data-type="person.helpers">
									<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя
									</div>
								</div>
								<?php
							}
							?>
						</div>
					</div>

				</div>

				<?php
				if ($fieldsNames['person']['loyalty']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['person']['loyalty'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<?php
							$element = new Elements();
							print $su = $element -> LoyaltySelect('loyalty', [
								"class" => [
									"wp95",
									$param['requered']
								],
								"sel"   => $GLOBALS['loyalDefault'],
								"data"  => 'data-class="'.$param['requered'].'"'
							]);
							?>
						</div>

					</div>
				<?php } ?>

			</div>

			<div class="wp100 box--child" id="clientBoxLead">

				<div class="flex-container mt10">

					<div class="flex-string">
						<div id="divider"><b class="blue">Клиент</b></div>
					</div>

				</div>

				<div class="flex-container mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['title'] ?>
						<sup class="noBold fs-07">(из заявки)</sup></div>
					<div class="flex-string wp80 pl10 relativ">
						<input type="text" name="title" id="title" value="<?= $company ?>" class="<?= $clientReq ?> wp95">
						<div class="em fs-09 gray2 hidden">Оставьте поле пустым, чтобы <b>не создавать</b> Клиента</div>
					</div>

				</div>

				<?php if ($clid > 0) { ?>
					<div class="flex-container viewdiv p0 pt10 pb10 mfh-09 mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['title'] ?>
							<sup class="noBold fs-07">(из базы)</sup>
						</div>
						<div class="flex-string wp80 pl10 cleared relativ">
							<INPUT name="clid" type="hidden" id="clid" value="<?= $clid ?>">
							<INPUT name="client" id="client" type="text" placeholder="Выбор <?= $lang['face']['ClientName']['1'] ?>" value="<?= current_client($clid) ?>" class="wp95">
							<span class="idel clearinputs pr15 mr15" title="Очистить"><i class="icon-block-1 red"></i></span>
						</div>

					</div>
					<div class="flex-container bgwhite p10 border-bottom">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">&nbsp;&nbsp;</div>
						<div class="flex-string wp80 pl10">
							<div class="Bold fs-11"><b class="red">Внимание!</b> В базе найден похожий Клиент.</div>
							<ul>
								<li>Чтобы добавить
									<u>Нового клиента</u> из заявки очистите поле "из базы" с помощью иконки
									<i class="icon-block-1 red"></i></li>
								<li>Иначе в базу к указанному Клиенту будут добавлены <?= $fieldsNames['client']['mail_url'] ?>, <?= $fieldsNames['client']['phone'] ?> и <?= $fieldsNames['client']['site_url'] ?> (к существующим) с сохранением имеющегося Названия</li>
							</ul>
						</div>

					</div>
				<?php } ?>

				<?php
				if ($fieldsNames['client']['mail_url']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['mail_url'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input type="text" name="mail_url" id="mail_url" value="<?= $mail_url ?>" class="wp95">
						</div>

					</div>
				<?php } ?>

				<div class="flex-container mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['phone'] ?>:</div>
					<div class="flex-string wp80 pl10">
						<div id="vphone">
							<?php
							if ($format_phone != '') {
								if ($phone != '') {
									$phonep = yexplode(",", $phone);
									for ($i = 0, $iMax = count($phonep); $i < $iMax; $i++) {

										if ($i == (count($phonep) - 1)) $adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
										else $adder = '';

										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="phone[]" type="text" class="phone w250" id="phone[]" value="<?= $phonep[ $i ] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
											<span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span><?= $adder ?>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="phone[]" type="text" class="phone w250" id="phone[]" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vphone" data-action="valphone" data-type="client.helpers" autocomplete="off">
										<span class="remover hand" data-parent="vphone"><i class="icon-minus-circled red"></i></span>
										<span class="adder hand" title="" data-block="phoneBlock" data-main="vphone" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
									</div>
									<?php
								}
							}
							else {
								?>
								<div class="phoneBlock paddbott5 relativv">
									<INPUT name="phone" type="text" class="phone" style="width:98%" id="phone" value="<?= $phone ?>" placeholder="Формат: <?= $format_tel ?>" data-id="vphone" data-action="valphone" data-type="client.helpers">
									<div class="em blue smalltxt">Используйте <b>запятую</b> в качестве разделителя
									</div>
								</div>
								<?php
							}
							?>
						</div>
					</div>

				</div>
				<?php
				if ($fieldsNames['client']['site_url']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['site_url'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input type="text" name="site_url" id="site_url" value="<?= $site_url ?>" class="wp95">
						</div>

					</div>
				<?php } ?>
				<?php
				if ($fieldsNames['client']['address']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['address'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<input type="text" name="address" id="address" value="<?= $city ?>" class="wp95">
							<div class="em fs-09 gray2"><?= $address ?></div>
						</div>

					</div>
				<?php } ?>
				<?php
				if ($fieldsNames['client']['tip_cmr']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['tip_cmr'] ?>:&nbsp;</div>
						<div class="flex-string wp80 pl10">
							<?php
							$relDefault = ($client['tip_cmr'] > 0) ? $client['tip_cmr'] : $GLOBALS['relTitleDefault'];

							$element = new Elements();
							print $su = $element -> RelationSelect('tip_cmr', [
								"class" => ["wp95"],
								"sel"   => $relDefault,
								"data"  => 'data-class="'.$param['requered'].'"'
							]);
							?>
						</div>

					</div>
					<?php
				}
				?>

				<?php
				if ($fieldsNames['client']['territory']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['territory'] ?>:&nbsp;</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							$element = new Salesman\Elements();
							print $su = $element -> TerritorySelect('territory', [
								"class" => ["wp95"],
								"sel"   => $territory,
								"data"  => 'data-class="'.$param['requered'].'"'
							]);
							?>
							<?php if (!$otherSettings['guidesEdit']) { ?>
								<span class="hidden-iphone">&nbsp;<a href="javascript:void(0)" onclick="add_sprav('territory','territory')" title="Добавить"><i class="icon-plus-circled blue"></i></a></span>
							<?php } ?>

						</div>

					</div>
					<?php
				}
				?>

				<?php
				if ($fieldsNames['client']['idcategory']) {
					?>
					<div class="flex-container mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['client']['idcategory'] ?>:&nbsp;</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							$element = new Elements();
							print $su = $element -> IndustrySelect('idcategory', [
								"class" => ["wp95"],
								"tip"   => 'client',
								"data"  => 'data-class="'.$param['requered'].'"'
							]);
							?>
							<?php if (!$otherSettings['guidesEdit']) { ?>
								<span class="hidden-iphone">
									&nbsp;<a href="javascript:void(0)" onclick="add_sprav('category','idcategory')" title="Добавить"><i class="icon-plus-circled blue"></i></a>&nbsp;
								</span>
							<?php } ?>

						</div>

					</div>
					<?php
				}
				?>

			</div>

			<div class="flex-container mt10">

				<div class="flex-string">
					<div id="divider"><b class="red">Активность</b></div>
				</div>

			</div>

			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип активности:</div>
				<div class="flex-string wp80 pl10">
					<select name="tiphist" id="tiphist" class="required wp95" data-change="activities" data-id="content">
						<?php
						$res = $db -> getAll("SELECT * FROM {$sqlname}activities WHERE filter IN ('all','activ') and identity = '$identity' ORDER by aorder");
						foreach ($res as $data) {

							$s = ($data['id'] == $GLOBALS['actDefault']) ? "selected" : "";
							print '<option value="'.$data['title'].'" '.$s.' style="color:'.$data['color'].'">'.$data['title'].'</option>';

						}
						?>
					</select>&nbsp;<i class="icon-info-circled blue hidden-iphone" title="В описание активности и обращения будет добавлен комментарий"></i>
				</div>

			</div>

			<div class="flex-container mt10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="content" id="content" class="wp95" style="height:200px;"><?= $description ?></textarea>
					<div id="tagbox" class="gray1 fs-09 mt5" data-id="content"></div>
				</div>

			</div>

			<div class="flex-container mt10 pb10">

				<div class="flex-string">
					<div id="divider">Создать <?= $lang['face']['DealName'][3] ?></div>
				</div>

			</div>

			<div class="flex-container mt10 pb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10">

					<div class="checkbox mt10 ">
						<label class="dogblockToggler">
							<input name="dodog" type="checkbox" id="dodog" value="yes">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Создать <?= $lang['face']['DealName'][3] ?>
						</label>
					</div>

				</div>

			</div>

			<div class="wp100 hidden" id="dogblock">

				<div class="flex-container mb10 mt10 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $lang['face']['DealName'][0] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<input type="text" name="dogovor[title]" id="dogovor" value="Входящий лид №<?= $_REQUEST['id'] ?>" placeholder="Название сделки" class="wp93" data-req="required">
						<div class="idel paddright15">
							<i title="Очистить" onClick="$('#dogovor\\[title\\]').val('');" class="icon-block red hand"></i>
						</div>
						<div class="smalltxt gray2"><?= $lang['face']['DealName'][3] ?>. <?= $dnum ?></div>
					</div>

				</div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['datum_plan'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<input name="dogovor[datum_plan]" type="date" id="datum_plan" class="wp30" value="<?= $datum_plan ?>" maxlength="10" placeholder="Дата реализации" autocomplete="off" data-req="required">
					</div>

				</div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['direction'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="dogovor[direction]" id="direction" class="wp93" data-req="<?= $fieldsRequire['dogovor']['direction'] ?>">
							<?php
							$resulttip = $db -> getAll("SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title");
							foreach ($resulttip as $data) {

								$s = ($data['id'] == $dirDefault || $data['id'] == $direction) ? "selected" : "";
								print '<OPTION '.$s.' value="'.$data['id'].'">'.$data['title'].'</OPTION>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['tip'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="dogovor[tip]" id="tip" class="wp93" data-req="<?= $fieldsRequire['dogovor']['tip'] ?>">
							<?php
							$resulttip = $db -> getAll("SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER BY title");
							foreach ($resulttip as $data) {

								$s = ($data['tid'] == $tipDefault) ? "selected" : "";
								print '<OPTION '.$s.' value="'.$data['tid'].'">'.$data['title'].'</OPTION>';

							}
							?>
						</select>
					</div>

				</div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['idcategory'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<?php
						$dfs     = $db -> getOne("SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '".$otherSettings['dealStepDefault']."' and identity = '$identity' ORDER BY title");
						$resultt = $db -> getAll("SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title");
						?>
						<select name="dogovor[idcategory]" id="step" class="wp93" data-req="<?= $fieldsRequire['dogovor']['idcategory'] ?>">
							<?php
							foreach ($resultt as $data) {
								$firstStep = ($otherSettings['dealStepDefault'] != '') ? $otherSettings['dealStepDefault'] : $dfs;
								$s         = ($data['idcategory'] == $firstStep) ? 'selected' : '';
								echo '<option value="'.$data['idcategory'].'" '.$s.'>'.$data['title'].'% - '.$data['content'].'</option>';
							}
							?>
						</select>
					</div>

				</div>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldsNames['dogovor']['mcid'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="dogovor[mcid]" id="dogovor[mcid]" class="<?= $fieldsRequire['dogovor']['mcid'] ?> wp93" title="Укажите, от какой Вашей компании совершается сделка">
							<?php
							$result = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot");
							while ($data = $db -> fetch($result)) {

								$s = ($data['id'] == $mcDefault) ? "selected" : "";
								print '<option value="'.$data['id'].'" '.$s.'>'.$data['name_shot'].'</option>';

							}
							?>
						</select>
					</div>

				</div>

				<?php
				if ($fieldDeal['adres']) {
					?>
					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['adres'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">
							<input name="dogovor[adres]" type="text" id="dogovor[adres]" class="wp93" value="" placeholder="<?= $fieldDeal['adres'] ?>" autocomplete="on" data-req="<?= $fieldsRequire['dogovor']['adres'] ?>" data-type="address">
						</div>

					</div>

				<?php } ?>

				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $fieldDeal['content'] ?>:</div>
					<div class="flex-string wp80 pl10 relativ">
						<textarea name="dogovor[content]" id="dogovor[content]" style="height: 100px;" class="wp93" data-req="<?= $fieldsRequire['dogovor']['content'] ?>"><?= trim($description) ?></textarea>
					</div>

				</div>

				<div align="center" class="togglerbox smalltxt gray2 hand mb20" data-id="fullFilter" title="Показать/скрыть доп.фильтры">

					ещё поля...&nbsp;<i class="icon-angle-down" id="mapic"></i>

				</div>

				<div id="fullFilter" class="hidden box--child">

					<?php
					$res = $db -> getAll("select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");
					foreach ($res as $da) {

						if ($da['fld_temp'] == "--Обычное--") {
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" value="" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>">
								</div>

							</div>

							<?php
						}
						elseif ($da['fld_temp'] == "adres") {
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93 yaddress" value="" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>" data-type="address">
								</div>

							</div>

							<?php
						}
						elseif ($da['fld_temp'] == "hidden") {
							?>
							<input id="dogovor[<?= $da['fld_name'] ?>]" name="dogovor[<?= $da['fld_name'] ?>]" type="hidden" value="">
							<?php
						}
						elseif ($da['fld_temp'] == "textarea") {

							$fieldData = $da['fld_var'];
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<textarea name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" style="height: 150px;" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>"><?= str_replace("<br>", "\n", $fieldData) ?></textarea>
								</div>

							</div>

							<?php
						}
						elseif ($da['fld_temp'] == "select") {

							$vars = explode(",", $da['fld_var']);
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<select name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" data-req="<?= $da['fld_required'] ?>">
										<option value="">--Выбор--</option>
										<?php
										foreach ($vars as $var) {
											?>
											<option value="<?= $var ?>"><?= $var ?></option>
										<?php } ?>
									</select>
								</div>

							</div>

							<?php
						}
						elseif ($da['fld_temp'] == "multiselect") {

							$vars = explode(",", $da['fld_var']);
							?>

							<div id="divider" align="center"><b><?= $da['fld_title'] ?></b></div>

							<div class="flex-container mb10 mt20 box--child" data-req="<?= ($da['fld_required'] == 'required' ? 'multireq' : '') ?>">

								<div class="flex-string wp100 pl10">
									<select name="dogovor[<?= $da['fld_name'] ?>][]" id="dogovor[<?= $da['fld_name'] ?>][]" multiple="multiple" class="multiselect" style="width: 98.5%;">
										<?php
										foreach ($vars as $var) {
											?>
											<option value="<?= $var ?>"><?= $var ?></option>
										<?php } ?>
									</select>
								</div>

							</div>

							<hr>
							<?php
						}
						elseif ($da['fld_temp'] == "inputlist") {

							$vars = $da['fld_var'];
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<input type="text" name="dogovor[<?= $da['fld_name'] ?>]" id="dogovor[<?= $da['fld_name'] ?>]" class="wp93" value="<?= $fieldData ?>" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>"/>
									<div class="smalltxt blue"><em>Двойной клик мышкой для показа вариантов</em>
									</div>
									<script>
										var str = '<?=$vars?>';
										var data = str.split(',');
										$("#dogovor\\[<?=$da['fld_name']?>\\]").autocomplete(data, {
											autofFll: true,
											minLength: 0,
											minChars: 0,
											cacheLength: 5,
											max: 50,
											selectFirst: true,
											multiple: false,
											delay: 0,
											matchSubset: 2
										});
									</script>
								</div>

							</div>
							<?php
						}
						elseif ($da['fld_temp'] == "radio") {

							$vars = explode(",", $da['fld_var']);
							?>

							<div class="flex-container mb10 mt20 box--child" data-req="<?= ($da['fld_required'] == 'required' ? 'req' : '') ?>">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">

									<div class="flex-container box--child wp93--5">

										<?php
										foreach ($vars as $var) {
											?>
											<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

												<div class="radio">
													<label>
														<input name="dogovor[<?= $da['fld_name'] ?>]" type="radio" id="dogovor[<?= $da['fld_name'] ?>]" <?= $s ?> value="<?= $var ?>"/>
														<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
														<span class="title"><?= $var ?></span>
													</label>
												</div>

											</div>
										<?php } ?>
										<?php if ($da['fld_required'] != 'required') { ?>
											<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">

												<div class="radio">
													<label>
														<input name="<?= $da['fld_name'] ?>" type="radio" id="<?= $da['fld_name'] ?>" checked value="">
														<span class="custom-radio secondary"><i class="icon-radio-check"></i></span>
														<span class="title gray">Не выбрано</span>
													</label>
												</div>

											</div>
										<?php } ?>

									</div>

								</div>

							</div>
							<?php
						}
						elseif ($da['fld_temp'] == "datum") {
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<INPUT name="dogovor[<?= $da['fld_name'] ?>]" type="text" id="dogovor[<?= $da['fld_name'] ?>]" class="datum wp30" data-req="<?= $da['fld_required'] ?>" value="<?= $dogovor[ $da['fld_name'] ] ?>" autocomplete="off">
								</div>

							</div>
							<?php
						}
						elseif ($da['fld_temp'] == "datetime") {
							?>

							<div class="flex-container mb10 mt20 box--child">

								<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $da['fld_title'] ?>:</div>
								<div class="flex-string wp80 pl10 relativ">
									<INPUT name="dogovor[<?= $da['fld_name'] ?>]" type="date" id="dogovor[<?= $da['fld_name'] ?>]" class="inputdatetime" style="width: 30%;" value="<?= $dogovor[ $da['fld_name'] ] ?>" autocomplete="off" placeholder="<?= $da['fld_title'] ?>" data-req="<?= $da['fld_required'] ?>">
								</div>

							</div>
							<?php
						}

					}
					?>

				</div>

			</div>

			<div class="flex-container mt10 pb10">

				<div class="flex-string">
					<div id="divider"><b>Добавить напоминание</b></div>
				</div>

			</div>

			<?php
			$tcount = getOldTaskCount((int)$iduser1);
			if ((int)$otherSettings['taskControl'] > 0 && (int)$tcount >= (int)$otherSettings['taskControl']) {

				print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания, пока не закроете старые.</div>';

			}
			else {
				?>
				<div id="todoBoxExpress">

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тема:</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="todo[theme]" id="todo[theme]" type="text" value="<?= $theme ?>" placeholder="Укажите тему напоминания" class="wp95">
							<div class="em gray2 fs-09">Например: <b>Договориться о встрече</b></div>
						</div>

					</div>

					<hr>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">К исполнению:</div>
						<div class="flex-string wp80 pl10 relativ">

							<input name="todo[datumtime]" type="text" class="inputdatetime required" id="todo[datumtime]" value="<?= $thistime ?>" onclick="$('.datumTasksView').empty().hide()" onchange="getDateTasksNew('todo\\[datumtime\\]')" autocomplete="off">

							<div class="datumTasks hand tagsmenuToggler p10">
								Число дел: <span class="taskcount Bold">0</span>
								<div class="tagsmenu left hidden">
									<div class="blok"></div>
								</div>
							</div>
							<div class="datumTasksView" onblur="$('.datumTasksView').hide()"></div>

						</div>

					</div>

					<div class="flex-container box--child mt10 infodiv bgwhite">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Опции:</div>
						<div class="flex-string wp80 pt7 pl10 fs-11">

							<div class="mb10 pl10">

								<label for="todo[day]" class="switch">
									<input type="checkbox" name="todo[day]" id="todo[day]" value="yes">
									<span class="slider empty"></span>
								</label>
								<label for="todo[day]" class="inline">&nbsp;Весь день&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы напоминание не было привязано к времени"></i></label>

							</div>

							<div class="mb10 pl10">

								<label for="todo[readonly]" class="switch">
									<input type="checkbox" name="todo[readonly]" id="todo[readonly]" value="yes">
									<span class="slider empty"></span>
								</label>
								<label for="todo[readonly]" class="inline">&nbsp;Только чтение&nbsp;<i class="icon-info-circled blue" title="Включите, чтобы не ставить отметку о выполнении"></i></label>

							</div>

							<div class="mb10 pl10">

								<label for="todo[alert]" class="switch">
									<input type="checkbox" name="todo[alert]" id="todo[alert]" value="yes" <?php if ($alert == 'no' || $usersettings['taskAlarm'] == 'yes') print "checked"; ?>>
									<span class="slider empty"></span>
								</label>
								<label for="todo[alert]" class="inline">&nbsp;Напоминать&nbsp;<i class="icon-info-circled blue" title="Если включено, то будет показано всплывающее окно"></i></label>

							</div>

						</div>

					</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип напоминания:</div>
						<div class="flex-string wp80 pl10">

							<select name="todo[tip]" id="todo[tip]" class="wp95 required" data-change="activities" data-id="todo[des]">
								<?php
								$res = $db -> getAll("SELECT * FROM {$sqlname}activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder");
								foreach ($res as $data) {

									$s = ($data['id'] == $GLOBALS['actDefault']) ? "selected" : "";

									print '<option value="'.$data['title'].'" '.$s.' style="color:'.$data['color'].'">'.$data['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Исполнитель</div>
						<div class="flex-string wp80 pl10">

							<?php
							$element = new Elements();
							print $element -> UsersSelect("todo[touser]", [
								"class"   => ['wp95'],
								"active"  => true,
								"sel"     => $iduser1,
								"noempty" => true
							]);
							?>

						</div>

					</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срочность:</div>
						<div class="flex-string wp80 pl10">

							<div class="like-input wp95">

								<div id="psdiv" class="speed">

									<input type="hidden" id="todo[speed]" name="todo[speed]" value="0" data-id="speed">
									<div class="but black w100 text-center" id="sp1" title="Не срочно" onClick="setPS('speed','1')">
										<i class="icon-down-big"></i>&nbsp;Не срочно
									</div>
									<div class="but black active w100 text-center" id="sp0" title="Обычно" onClick="setPS('speed','0')">
										<i class="icon-check-empty"></i>&nbsp;Обычно
									</div>
									<div class="but black w100 text-center" id="sp2" title="Срочно" onClick="setPS('speed','2')">
										<i class="icon-up-big"></i>&nbsp;Срочно
									</div>

								</div>

							</div>

						</div>

					</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Важность:</div>
						<div class="flex-string wp80 pl10">

							<div class="like-input wp95">

								<div id="psdiv" class="priority">

									<input type="hidden" id="todo[priority]" name="todo[priority]" value="0" data-id="priority">
									<div class="but black w100 text-center" id="pr1" title="Не важно" onClick="setPS('priority','1')">
										<i class="icon-down-big"></i>&nbsp;Не важно
									</div>
									<div class="but black active w100 text-center" id="pr0" title="Обычно" onClick="setPS('priority','0')">
										<i class="icon-check-empty"></i>&nbsp;Обычно
									</div>
									<div class="but black w100 text-center" id="pr2" title="Важно" onClick="setPS('priority','2')">
										<i class="icon-up-big"></i>&nbsp;Важно
									</div>

								</div>

							</div>

						</div>

					</div>

					<hr>

					<div class="flex-container box--child mt10 mb20">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Агенда:</div>
						<div class="flex-string wp80 pl10 relativ">
							<a href="javascript:void(0)" onClick="copydes();" title="скопировать из активности" class="blue pull-right mr20 pr20 mt5"><i class="icon-docs"></i></a>
							<textarea name="todo[des]" id="todo[des]" rows="4" class="required1 wp95 pr20" style="height:120px;" placeholder="Здесь можно указать детали напоминания - что именно надо сделать?"><?= $description ?></textarea>
							<div id="tagbox" class="gray1 fs-09 mt5" data-id="todo[des]"></div>
						</div>

					</div>

				</div>
				<?php
			}
			?>

		</div>

		<hr>

		<div class="button--pane">

			&nbsp;<span class="">
				<a href="javascript:void(0)" onclick="editLead('<?= $id ?>','setuser');" title="Назначить Ответственного или Закрыть" class="button orangebtn"><i class="icon-ok white"></i>Квалифицировать</a>
			</span>

			<div class="pull-aright">

				<a href="javascript:void(0)" onclick="checkTask()" class="button">Выполнить</a>&nbsp;
				<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

			</div>

		</div>

	</form>
	<?php
	$hooks -> do_action("lead_workitform_after", $_REQUEST);

}

if ($action == "mass") {

	$id  = $_REQUEST['ch'];
	$sel = implode(";", $id);
	$kol = count($id);

	$sort = '';

	$word    = $_REQUEST['word'];
	$user    = implode(",", $_REQUEST['user']);
	$statuss = implode(",", $_REQUEST['statuss']);

	$d1 = $_REQUEST['da1'];
	$d2 = $_REQUEST['da2'];

	if ($iduser1 != $coordinator) $sort .= get_people($iduser1);
	if ($user != '') $sort .= " and iduser IN (".$user.")";
	if ($statuss != '') $sort .= " and status IN (".$statuss.")";

	?>
	<div class="zagolovok"><b>Групповое действие</b></div>
	<form method="post" action="/modules/leads/core.leads.php" enctype="multipart/form-data" name="LeadForm" id="LeadForm" autocomplete="off">
		<input name="ids" id="ids" type="hidden" value="<?= $sel ?>"/>
		<input name="action" id="action" type="hidden" value="mass"/>

		<div id="profile">

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>
				<div class="flex-string wp80 pl10">

					<select name="doAction" id="doAction" class="wp95" onchange="showd()">
						<option value="">--выбор--</option>
						<option value="pDelegate">Назначить</option>
						<option value="pClose">Закрыть без обработки</option>
					</select>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden catt" id="catt">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Назначить:</div>
				<div class="flex-string wp80 pl10">

					<?php
					$element = new Elements();
					print $element -> UsersSelect("iduser", [
						"sel"   => null,
						"class" => "wp95",
						"users" => $leadsettings['leadOperator']
					]);
					?>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden catp" id="catp">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Закрыть:</div>
				<div class="flex-string wp80 pl10">

					<select name="rezult" id="rezult" class="wp95">
						<option value="">--выбор--</option>
						<option value="1">Это спам</option>
						<option value="2">Это дубль</option>
						<option value="3">Это другое</option>
						<option value="4">Не целевой</option>
					</select><br>
					<span class="smalltxt">Если Интерес не является качественным, то можно закрыть его с указанным результатом.</span>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10 hidden catp" id="catp">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10">

					<textarea name="rezz" id="rezz" class="wp95" style="height:100px"></textarea>

				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Выполнить для:</div>
				<div class="flex-string wp80 pl10">

					<label>
						<input name="isSelect" id="isSelect" value="doSel" type="radio" <?php if ($kol > 0) print "checked"; ?>>&nbsp;Выбранное (<b class="blue"><?= $kol ?></b>)
					</label>

				</div>

			</div>

		</div>

		<?php if ($kol == 0) { ?>
			<div class="warning">Не выбрано ниодной записи</div><?php } ?>
		<?php if ($kol > 0) { ?>
			<div class="infodiv">Уведомления по email не отправляются</div><?php } ?>

		<hr>

		<div class="text-right">

			<?php if ($kol > 0) { ?>
				<a href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Выполнить</a>&nbsp;<?php } ?>
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<?php
}

if ($action == "import") {
	?>
	<DIV class="zagolovok">Импорт</DIV>
	<form method="post" action="/modules/leads/core.leads.php" enctype="multipart/form-data" name="LeadForm" id="LeadForm" autocomplete="off">
		<INPUT type="hidden" name="action" id="action" value="import">

		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Из файла:</div>
			<div class="flex-string wp80 pl10">

				<input name="file" type="file" class="wp97" id="file">

			</div>

		</div>
		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сотруднику:</div>
			<div class="flex-string wp80 pl10">

				<?php
				$users = new Elements();
				print $users -> UsersSelect("iduser", [
					"class"    => "wp90",
					"active"   => true,
					"haveplan" => true,
					"self"     => false
				]);
				?>
				<i class="icon-user-1 green"></i>

			</div>

		</div>

		<hr>

		<div class="infodiv">
			Поддерживаются форматы CSV, XLS. Вы можете загрузить
			<a href="/developer/example/leads.xls" class="red"><b>пример</b></a><br>
		</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Выполнить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

		</div>
	</FORM>
	<?php
}

if ($action == "source.edit") {

	$name = ' ';

	if ($id > 0) {

		$result      = $db -> getRow("SELECT * FROM {$sqlname}clientpath where id = '$id' and identity = '$identity'");
		$name        = $result["name"];
		$isDefault   = $result["isDefault"];
		$utm_source  = $result["utm_source"];
		$destination = $result["destination"];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить источник</b></div>
	<FORM action="/modules/leads/core.leads.php" method="POST" name="LeadForm" id="LeadForm">
		<input name="action" type="hidden" value="source.edit" id="action"/>
		<input name="id" type="hidden" value="<?= $id ?>" id="<?= $id ?>"/>

		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Название:</div>
			<div class="column12 grid-9">
				<input name="name" type="text" id="name" class="wp97 required" value="<?= $name ?>">
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2">По-умолчанию:</div>
			<div class="column12 grid-9">
				<label><input id="isDefault" name="isDefault" type="checkbox" value="yes" <?php if ($isDefault == 'yes') print 'checked' ?> />&nbsp;Использовать по-умолчанию&nbsp;</label>
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Источник:</div>
			<div class="column12 grid-9">
				<input name="utm_source" type="text" id="utm_source" class="wp97" value="<?= $utm_source ?>">
				<div class="em gray2 fs-09">( utm_source )</div>
			</div>

		</div>
		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2 pt10">Телефон:</div>
			<div class="column12 grid-9">
				<input name="destination" type="text" id="destination" class="wp60" value="<?= $destination ?>">
				<div class="em gray2 fs-09">( Номер входящей линии )</div>
			</div>

		</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>

	<script>

		$('#dialog').css('width', '600px');

		$('#LeadForm').ajaxForm({

			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$out.fadeTo(10, 1).empty();
				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				configpage();

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
if ($action == "source.delete") {

	$result = $db -> getRow("SELECT * FROM {$sqlname}clientpath WHERE id = '$id' and identity = '$identity'");
	$tip    = $result['name'];
	$multi  = $_REQUEST['multi'];

	$count = count($multi);
	$multi = implode(",", $multi);
	?>

	<div class="zagolovok">Удалить "<?= $tip ?>"</div>

	<FORM action="/modules/leads/core.leads.php" method="POST" name="LeadForm" id="LeadForm">
		<input id="id" name="id" type="hidden" value="<?= $id ?>">
		<input id="multi" name="multi" type="hidden" value="<?= $multi ?>">
		<input id="action" name="action" type="hidden" value="source.delete"/>

		<div class="infodiv">
			В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.
		</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 right-text fs-12 gray2">Новый источник:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" style="width: 100%;" class="required">
					<option value="">--выбрать--</option>
					<?php
					if ($multi != '') $m = ' and id NOT IN ('.$multi.')';
					$result_a = $db -> getAll("SELECT * FROM {$sqlname}clientpath WHERE (id != '".$id."' $m) and identity = '$identity' ORDER by name");
					foreach ($result_a as $data_arraya) {
						?>
						<option value="<?= $data_arraya['id'] ?>"><?= $data_arraya['name'] ?></option>
					<?php } ?>
				</select>
			</div>

		</div>

		<div class="infodiv div-center">Будет затронуто <b><?= $count ?></b> записей.</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<script>

		$('#dialog').css('width', '600px');
		$('#LeadForm').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var newd = $('#newid option:selected').val();
				var em = checkRequired();

				if (em === false || newd < 1)
					return false;

				if (em !== false && newd > 0) {

					$out.fadeTo(10, 1).empty();
					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				}

			},
			success: function (data) {

				configpage();

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

if ($action == "utms.edit") {

	$id = $_REQUEST['id'];

	if ($id > 0) {

		$result       = $db -> getRow("SELECT * FROM {$sqlname}leads_utm where id = '$id' and identity = '$identity'");
		$utm_source   = $result["utm_source"];
		$utm_url      = $result["utm_url"];
		$utm_medium   = $result["utm_medium"];
		$utm_campaign = $result["utm_campaign"];
		$utm_term     = $result["utm_term"];
		$utm_content  = $result["utm_content"];
		$clientpath   = $result["clientpath"];
		$site         = $result["site"];

	}
	$clientpath = ($clientpath != '') ? $clientpath : "0";

	//справочник по источникам
	$path   = [];
	$result = $db -> getAll("SELECT id, name, utm_source FROM {$sqlname}clientpath WHERE utm_source != '' and identity = '$identity' ORDER by name");
	foreach ($result as $data) {

		$path[ $data['id'] ] = $data['utm_source'];

	}
	$path = json_encode_cyr($path);

	//Варианты типа трафика из базы
	$medium = $db -> getCol("SELECT DISTINCT(utm_medium) FROM {$sqlname}leads_utm WHERE utm_medium != '' and identity = '$identity'");
	$medium = (array_unique(array_merge($medium, [
		'cpc',
		'ppc',
		'banner',
		'рассылка'
	])));
	sort($medium);
	$medium = implode(",", $medium);

	$source = $db -> getCol("SELECT DISTINCT(utm_source) FROM {$sqlname}leads_utm WHERE utm_source != '' and identity = '$identity'");
	$source = (array_unique(array_merge($source, [
		'google',
		'yandex',
		'vk',
		'facebook',
		'forum',
		'news',
		'partner'
	])));
	sort($source);
	$source = implode(",", $source);

	$campaign = $db -> getCol("SELECT DISTINCT(utm_campaign) FROM {$sqlname}leads_utm WHERE utm_campaign != '' and identity = '$identity'");
	sort($campaign);
	$campaign = implode(",", $campaign);

	$term = $db -> getCol("SELECT DISTINCT(utm_term) FROM {$sqlname}leads_utm WHERE utm_term != '' and identity = '$identity'");
	sort($term);
	$term = implode(",", $term);

	?>
	<div class="zagolovok"><b>Изменить / Добавить Ссылку</b></div>
	<FORM action="/modules/leads/core.leads.php" method="POST" enctype="multipart/form-data" name="LeadForm" id="LeadForm">
		<input id="action" name="action" type="hidden" value="utms.edit"/>
		<input id="id" name="id" type="hidden" value="<?= $id ?>"/>

		<div id="formtabse" style="overflow-y: auto; max-height: 90vh; overflow-x: hidden" class="">

			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Сайт:</div>
				<div class="column12 grid-9">
					<input name="site" type="text" id="site" class="wp97 required" value="<?= $site ?>" placeholder="http://site.ru/">
					<div class="em gray2 fs-09 gray2">Формат: http(s)://site.ru/</div>
				</div>

			</div>

			<hr>

			<div class="row">

				<div class="column12 grid-3 fs-12 pt10 right-text gray2">Источник:</div>
				<div class="column12 grid-9">
					<?php
					$element = new Elements();
					print $element -> ClientpathSelect("clientpath", [
						"sel"   => $clientpath,
						"class" => "yw250"
					]);
					?>
				</div>

			</div>

			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Источник трафика:</div>
				<div class="column12 grid-9">
					<input name="utm_source" type="text" id="utm_source" class="wp97 required" value="<?= $utm_source ?>">
					<div class="em gray2 fs-09">( utm_source, двойной клик для вызова вариантов )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt10">Кампания:</div>
				<div class="column12 grid-9">
					<input name="utm_campaign" type="text" id="utm_campaign" class="wp97" value="<?= $utm_campaign ?>">
					<div class="em gray2 fs-09">( utm_campaign, двойной клик для вызова вариантов )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt7">Тип трафика:</div>
				<div class="column12 grid-9">
					<input name="utm_medium" type="text" id="utm_medium" class="wp97" value="<?= $utm_medium ?>">
					<div class="em gray2 fs-09">( utm_medium, двойной клик для вызова вариантов )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt7">Ключевая фраза:</div>
				<div class="column12 grid-9">
					<input name="utm_term" type="text" id="utm_term" class="wp97" value="<?= $utm_term ?>">
					<div class="em gray2 fs-09">( utm_term )</div>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt7">Содержание:</div>
				<div class="column12 grid-9">
					<input name="utm_content" type="text" id="utm_content" class="wp97" value="<?= $utm_content ?>">
					<div class="em gray2 fs-09">( utm_content )</div>
				</div>

			</div>

			<hr>

			<div class="row">

				<div class="column12 grid-3 right-text fs-12 gray2 pt7">Ссылка ( utm_url ):</div>
				<div class="column12 grid-9 flex-container">
					<div class="flex-string" style="flex-grow: 15;">
						<textarea name="utm_url" id="utm_url" class="wp97"><?= $utm_url ?></textarea>
					</div>
					<div class="flex-string">
						<span data-clipboard-target="#utm_url" class="copy hand" title="Скопировать в буфер"><i class="icon-paste blue"></i></span>
					</div>
				</div>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="getUrl()" class="button greenbtn pull-left"><i class="icon-link-1"></i>Получить ссылку</A>&nbsp;
			<A href="javascript:void(0)" onclick="$('#LeadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>

	<script>

		var clipboard = new Clipboard('.copy');
		clipboard.on('success', function (e) {

			alert("Скопировано в буфер");
			e.clearSelection();

		});

		var path = JSON.parse('<?=$path?>');

		var medium = '<?=$medium?>'.split(",");
		var source = '<?=$source?>'.split(",");
		var campaign = '<?=$campaign?>'.split(",");
		var term = '<?=$term?>'.split(",");

		$('#clientpath').on('change', function () {

			var item = $(this).val();
			var cpath = path[item];

			$('#utm_source').val(cpath);

		});

		$("#utm_source").autocomplete(source, {
			autoFill: true,
			minLength: 0,
			minChars: 0,
			cacheLength: 5,
			max: 20,
			selectFirst: false,
			multiple: false,
			delay: 300,
			matchSubset: 2
		});

		$("#utm_medium").autocomplete(medium, {
			autoFill: true,
			minLength: 0,
			minChars: 0,
			cacheLength: 5,
			max: 20,
			selectFirst: false,
			multiple: false,
			delay: 300,
			matchSubset: 2
		});

		$("#utm_campaign").autocomplete(campaign, {
			autoFill: true,
			minLength: 0,
			minChars: 0,
			cacheLength: 5,
			max: 20,
			selectFirst: false,
			multiple: false,
			delay: 300,
			matchSubset: 2
		});

		$("#utm_term").autocomplete(term, {
			autoFill: true,
			minLength: 0,
			minChars: 0,
			cacheLength: 5,
			max: 20,
			selectFirst: false,
			multiple: false,
			delay: 300,
			matchSubset: 2
		});

		function getUrl() {

			var string = $('#site').val();

			if (string.indexOf('?') + 1 == false) string = string + '?';

			if (string !== '') {

				if ($('#utm_source').val() != '') string = string + 'utm_source=' + $('#utm_source').val();
				if ($('#utm_medium').val() != '') string = string + '&utm_medium=' + $('#utm_medium').val();
				if ($('#utm_term').val() != '') string = string + '&utm_term=' + $('#utm_term').val();
				if ($('#utm_content').val() != '') string = string + '&utm_content=' + $('#utm_content').val();
				if ($('#utm_campaign').val() != '') string = string + '&utm_campaign=' + $('#utm_campaign').val();

				string = string.replace(' ', '_');

				$('#utm_url').val(string);

			} else alert('Не заполнен адрес сайта');

		}

	</script>
	<?php
}
?>
<script type="text/javascript" src="/assets/js/app.form.js"></script>
<script>

	var action = $('#action').val();
	var origphone = '<?=$phone?>';
	var formatPhone = '<?=$format_phone?>';
	var $timecheck = <?=$timecheck?>;

	var origDateTime = $('#todo\\[datumtime\\]').val();
	var origTip = $('#todo\\[tip\\] option:selected').text();

	if (!isMobile) {

		var hh = $('#dialog_container').actual('height') * 0.9;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight');// - $('.client').actual('outerHeight') - $('.person').actual('outerHeight') - 100;


		if ($('.client').is('div')) hh2 = hh2 - $('.client').actual('outerHeight');
		if ($('.person').is('div')) hh2 = hh2 - $('.person').actual('outerHeight');


		if ($(window).width() > 990) {

			$('#dialog').css({'width': '800px'});
			$('#formtabs').css({'max-height': hh2 + 'px'});

		} else {

			$('#dialog').css('width', '80%');
			$('#formtabs').css('max-height', hh2 + 'px');

		}

		if (in_array(action, ['view', 'setuser'])) $('#dialog').css('width', '800px');

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 40;

		if ($('.client').is('div')) h2 = h2 - $('.client').actual('outerHeight') - 20;
		if ($('.person').is('div')) h2 = h2 - $('.person').actual('outerHeight') - 20;

		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});
		$(".multiselect").addClass('wp97 h0');

	}

	if (in_array(action, ['workit'])) {

		var mFunnel = JSON.parse('<?=$mFunnel?>');

		console.log(mFunnel)

		if (Object.keys(mFunnel).length > 0) {

			$('#tip').off('change')
			$('#tip').on('change', function () {

				var tip = $('option:selected', this).val();
				var direction = $('#direction option:selected').val();
				var steps = mFunnel[direction][tip]['nsteps'];
				var def = mFunnel[direction][tip]['default'];
				var str = '';
				var $s;

				for (var i in steps) {

					$s = (steps[i].id === def) ? "selected" : "";

					str += '<option value="' + steps[i].id + '" ' + $s + '>' + steps[i].name + '% - ' + steps[i].content + '</option>';

				}

				$('#step').html(str);

			});

		}

	}

	$(function () {

		$('#tip').trigger('change');

		$(".nano").nanoScroller();

		if ($('#todo\\[datumtime\\]').is('input')) getDateTasksNew('todo\\[datumtime\\]');
		$(".multiselect").multiselect({sortable: true, searchable: true});

		//Формат номеров телефонов
		if (formatPhone !== '')
			reloadMasks();

		if (!isMobile) $('.inputdatetime').each(function () {

			var date = new Date();

			$(this).datetimepicker({
				timeInput: false,
				timeFormat: 'HH:mm',
				oneLine: true,
				showSecond: false,
				showMillisec: false,
				showButtonPanel: true,
				timeOnlyTitle: 'Выберите время',
				timeText: 'Время',
				hourText: 'Часы',
				minuteText: 'Минуты',
				secondText: 'Секунды',
				millisecText: 'Миллисекунды',
				timezoneText: 'Часовой пояс',
				currentText: 'Текущее',
				stepMinute: 5,
				closeText: '<i class="icon-ok-circled"></i>',
				dateFormat: 'yy-mm-dd',
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: date.getFullYear() + ':' + (date.getFullYear() + 5),
				minDate: new Date(date.getFullYear(), date.getMonth(), date.getDate())
			});
			$('.datum').datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});

		});

		if (!isMobile) $("#datum").datepicker({
			dateFormat: "yy-mm-dd",
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 2
		});
		if (!isMobile) $("#datum_plan").datepicker({
			dateFormat: "yy-mm-dd",
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 2
		});
		if (!isMobile) $("#datum_task").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		});
		if (!isMobile) $('#totime_task').ptTimeSelect();

		$("#todo\\[theme\\]").autocomplete("content/core/core.tasks.php?action=theme", {
			autoFill: false,
			minChars: 0,
			cacheLength: 1,
			max: 100,
			selectFirst: false,
			multiple: false,
			delay: 400,
			matchSubset: 3,
			matchContains: true
		});

		$("#client").autocomplete("content/helpers/client.helpers.php?action=clientlist", {
			autofill: true,
			minChars: 2,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, i, n, value) {
				return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {

				selItem('client', data[1]);

				$('#clid').val(data[1]);
				$('#client').val(data[0]);

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

				selItem('person', data[1]);

				$('#pid').val(data[1]);
				$('#person').val(data[0]);

			});

		$('#dialog #tagbox').load('content/core/core.tasks.php?action=tags&tip=' + urlEncodeData('<?=$GLOBALS['actTitleDefault']?>'));

		if( $("#ptitle").hasClass('suggestion') ) {
			$("#ptitle").autocomplete("content/helpers/person.helpers.php?action=get.status", {
				autofill: true,
				minChars: 3,
				cacheLength: 1,
				maxItemsToShow: 20,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 1
			});
		}

		$("#dogovor").autocomplete("content/helpers/deal.helpers.php?action=get.list", {
			autofill: true,
			minChars: 3,
			cacheLength: 1,
			maxItemsToShow: 20,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1
		});

		$('#dialog').center();

		$('#LeadForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				var action = $('#action').val();
				var errors = '';

				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');

				//alert( action );
				//alert( data.did );

				if (action === 'workit') {

					if (data.did > 0) openDogovor(data.did);
					else if (data.clid > 0) openClient(data.clid);

				}

				if ($('#isLead').val() === 'yes' && typeof configpage == 'function') configpage();

				if (typeof leads == 'function') leads();//обновляем счетчик на нижней панели

				if (data.error !== 'undefined' && data.error !== '' && data.error != null) errors = '<br>Note: ' + data.error;

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}
		});

		if (action === 'add') {
			doLoadCallback('editLead');
		}
		else if (action === 'workit') {
			doLoadCallback('workitLead');
		}

		if (action === 'add') {
			ShowModal.fire({
				etype: 'editLead',
				action: action
			});
		}
		else if (action === 'workit') {
			ShowModal.fire({
				etype: 'workitLead',
				action: action
			});
		}

	});

	$(document).off('click', '#dodog');
	$(document).on('click', '#dodog', function () {

		$('#dogblock').toggleClass('hidden');

		//если сделка не создается, то все обязательные поля делаем не обязательными
		if ($('#dodog').prop('checked')) {

			$('#dogblock').find('[data-req="required"]').addClass('required');
			$('#dogblock').find('[data-req="multireq"]').addClass('multireq');
			$('#dogblock').find('[data-req="req"]').addClass('req');

		}
		else {
			$('#dogblock').find('[data-req="required"]').removeClass('required');
			$('#dogblock').find('[data-req="multireq"]').removeClass('multireq');
			$('#dogblock').find('[data-req="req"]').removeClass('req');
		}

	});

	/**
	 * Управление тэгами
	 */
	$('select[data-change="activities"]').each(function () {

		var $el = $(this).data('id');
		$('#tagbox[data-id="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));

	});

	$(document).off('change', 'select[data-change="activities"]');
	$(document).on('change', 'select[data-change="activities"]', function () {
		var $el = $(this).data('id');
		$('#tagbox[data-id="' + $el + '"]').empty().load('content/core/core.tasks.php?action=itags&tip=' + urlEncodeData($('option:selected', this).val()));
	});

	$(document).off('click', '.tags');
	$(document).on('click', '.tags', function () {

		var $tag = $(this).text();
		var $el = $(this).parent('#tagbox').data('id');
		insTextAtCursor($el, $tag + '; ');

	});

	function checkTask() {

		if ($('#todo\\[theme\\]').val() !== '' && action === 'workit' && $timecheck) {

			if (origDateTime === $('#todo\\[datumtime\\]').val() || origTip === $('#todo\\[tip\\] option:selected').text()) {

				Swal.fire(
					{
						title: 'Вы ничего не забыли?',
						text: "Не изменена Дата и/или Тип напоминания!",
						type: 'question',
						showCancelButton: true,
						confirmButtonText: 'Продолжить',
						cancelButtonText: 'Упс, реально забыл',
						customClass: {
							confirmButton: 'button greenbtn',
							cancelButton: 'button redbtn'
						},
					}
				).then((result) => {

					if (result.value) {

						$('#LeadForm').trigger('submit');

					}

				});

			} else $('#LeadForm').trigger('submit');

		} else $('#LeadForm').trigger('submit');

	}

	function selItem(tip, id) {

		var cphone = $("#phone").val();
		var pphone = $("#tel").val();

		if (tip === 'client') {

			url = 'content/helpers/client.helpers.php?action=clientinfomore&clid=' + id;
			$("#clid").val(id);

			$.getJSON(url, function (data) {

				$('#clid').val(data.client.clid);
				$('#client').val(data.client.title);
				$('#company').val(data.client.title);
				$('#site').val(data.client.site_url);
				$('#city').val(data.client.address);
				$('#iduser [value="' + data.client.iduser + '"]').attr("selected", "selected");

				$("#clientpath [value='" + data.client.clientpath2 + "']").attr("selected", "selected");

				//Формат номеров телефонов
				if (formatPhone !== '') $(this).phoneFormater(formatPhone);

			});

		}
		if (tip === 'person') {

			url = 'content/helpers/client.helpers.php?action=clientinfomore&pid=' + id;

			$.getJSON(url, function (data) {

				if (data.client.title != '') {

					$('#clid').val(data.client.clid);
					$('#client').val(data.client.title);
					$('#company').val(data.client.title);
					$('#site').val(data.client.site_url);
					$('#city').val(data.client.address);

					$("#clientpath [value='" + data.client.clientpath2 + "']").attr("selected", "selected");

				}

				if ($("#title").val() == '') {

					$("#title").val(data.contact.person);
					$('#pid').val(data.contact.pid);
					$('#person').val(data.contact.person);
					$('#email').val(data.contact.mail);

				}


				if (formatPhone === '') {

					$('#vtel').find('.phoneBlock').not(':last').remove();

					if (pphone == '') $("#tel").val(data.contact.tel);
					else $("#tel").val(pphone + ', ' + data.contact.tel);

				}

				if (formatPhone !== '') {

					var tel = data.contact.tel.replace('+', '').replace(' ', '').split(",");

					for (var i in tel) {

						$('#vtel').prepend('<div class="phoneBlock paddbott5 relativ">' +
							'<INPUT name="tel[]" type="text" class="phone w250" id="tel[]" alt="phone" autocomplete="off" value="' + tel[i] + '" placeholder="Формат: <?=$format_tel?>" data-id="vtel" data-action="valphone" data-type="person.helpers">&nbsp;' +
							'<span class="remover hand" data-parent="vtel"><i class="icon-minus-circled red"></i></span>' +
							'</div>');
					}

					//Формат номеров телефонов
					reloadMasks();

				}

				if ($("#mail").val() == '') $("#mail").val(data.contact.mail);

			});

		}

	}

	function switchclient() {

		var data = '<?=$json?>';
		var type = $('#type option:selected').val();

		var obj = JSON.parse(data);

		if (type === 'person') {

			$('#per').hide();
			$('#mail').val('');
			$('#tel').val('');
			$('#title').val(obj.title);
			$('#mail_url').val(obj.email);
			$('#phone').val(obj.phone);
			$('#person').val('').removeClass('required');
			$('#personpath').removeClass('required');

		}
		if (type === 'client') {

			$('#per').show();
			$('#mail').val(obj.email);
			$('#tel').val(obj.phone);
			$('#title').val('<?=$company?>');
			$('#mail_url').val('');
			$('#phone').val('');
			$('#person').val(obj.title).addClass('required');
			$('#personpath').addClass('required');

		}

	}

	function gettags() {

		var tip = urlEncodeData($('#tiphist option:selected').val());
		$('#tagbox').load('content/core/core.tasks.php?action=tags&tip=' + tip);

	}

	function tagit(id) {

		var html = $('#tag_' + id).html();
		insTextAtCursor('content', html + '; ');

	}

	function copydes() {

		var tt = $('#content').val();
		$('#todo\\[des\\]').val(tt);

	}

	function showd() {

		var cel = $('#doAction option:selected').val();

		if (cel === 'pDelegate') {

			$('.catt').removeClass('hidden');
			$('.catp').addClass('hidden');

		} else if (cel === 'pClose') {

			$('.catt').addClass('hidden');
			$('.catp').removeClass('hidden');

		} else {

			$('.catt').addClass('hidden');
			$('.catp').addClass('hidden');

		}

		$('#dialog').center();

	}

	function reloadMasks() {

		//Формат номеров телефонов
		if (formatPhone !== '') {

			$('.phone').each(function () {

				$(this).phoneFormater(formatPhone);

			});

		}

	}

</script>
