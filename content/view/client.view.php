<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
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

$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

//print $acs_view.":".$acs_prava.":".get_accesse($clid, $pid, $did);

if ($acs_prava != 'on' && get_accesse($clid, $pid, $did) != 'yes') {

	print '<div class="zagolovok">Запрет просмотра</div>
	<div class="warning">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>
		К сожалению Вы не можете просматривать данную информацию<br>У Вас отсутствует разрешение.<br>
	</div>';
	exit;

}

// Проверка на доступность редактирования
$isAccess = get_accesse($clid, $pid) == "yes" || $isadmin == 'on';

$data = get_client_info($clid, "yes");

$phone_list = [];
$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $data['phone'])));
foreach ($phones as $phone) {

	$ismob        = isPhoneMobile($phone) ? 'ismob' : '';
	$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$data['pid'].'" data-clid="'.$clid.'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone, $clid).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

}
$phone = implode(", ", $phone_list);

$phone_list = [];
$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $data['fax'])));
foreach ($phones as $phone) {

	$ismob        = isPhoneMobile($phone) ? 'ismob' : '';
	$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$data['pid'].'" data-clid="'.$clid.'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone, $clid).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

}
$fax = implode(", ", $phone_list);

$email_list = '';
$emails     = yexplode(",", str_replace(";", ",", $data['mail_url']));

foreach ($emails as $email) {

	$apx = $ymEnable && $email != '' ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\''.$clid.'\',\'\',\''.trim($email).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : '';

	$email_list .= ( ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? link_it($email) : hideEmail($email) ).$apx;

}

?>
<DIV class="zagolovok"><i class="icon-building"></i>&nbsp;<b><?= $data['title'] ?></b></DIV>

<div>

	<div class="flex-container">

		<div class="flex-string wp10 text-center pt10">

			<i class="icon-building icon-5x broun mt20"></i>

		</div>
		<div class="flex-string wp90" style="max-height: 30vh; overflow-y: auto">

			<?php
			if ($data['tip_cmr'] != '') {
				?>
				<div class="row wp100">

					<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['tip_cmr'] ?>:</div>
					<div class="column12 grid-8 fs-12"><?= $data['tip_cmr'] ?></div>

				</div>
			<?php
			} ?>
			<div class="row wp100">

				<div class="column12 grid-3 gray2 fs-12 text-right">Ответственный:</div>
				<div class="column12 grid-8 fs-12"><?= current_user($data['iduser']) ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $phone == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['phone'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= $phone ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $fax == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['fax'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= $fax ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $data['site_url'] == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['site_url'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= link_it($data['site_url']) ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $email_list == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['mail_url'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= $email_list ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $data['category'] == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['idcategory'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= $data['category'] ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $data['address'] == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['address'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= $data['address'] ?></div>

			</div>
			<div class="row wp100 <?php
			print ( $data['territoryname'] == '' ) ? "hidden" : ""; ?>">

				<div class="column12 grid-3 gray2 fs-12 text-right"><?= $fieldsNames['client']['territory'] ?>:</div>
				<div class="column12 grid-8 fs-12"><?= $data['territoryname'] ?></div>

			</div>

			<?php
			//Сначала найдем поля, содержащие даты
			$res = $db -> getAll("select fld_name, fld_title from ".$sqlname."field where fld_tip='client' and fld_on='yes' and (fld_temp = 'datum' or fld_title LIKE '%рожден%') and identity = '$identity' order by fld_order");
			foreach ($res as $da) {

				$field[$da['fld_name']] = $da['fld_title'];

			}

			foreach ($field as $name => $value) {

				if ($data[$name] != '') {
					print '
					<div class="row wp100">
		
						<div class="column12 grid-3 gray2 fs-12 text-right">'.$value.':</div>
						<div class="column12 grid-8 fs-12"><i class="icon-gift red"></i>'.format_date_rus_name($data[$name]).'</div>
		
					</div>
					';
				}

			}
			?>

		</div>

	</div>

</div>

<hr>

<DIV id="more" class="ftabs" data-id="ccontainer" style="border:0; background: none;">

	<div id="ytabs">

		<ul class="gray flex-container blue">

			<li class="flex-string" data-link="bperson">Контакты</li>
			<li class="flex-string" data-link="bhistory">История</li>
			<li class="flex-string" data-link="bdeals"><?= $lang['face']['DealsName'][0] ?></li>

		</ul>

	</div>
	<div id="ccontainer" style="overflow-y: auto; overflow-x: hidden; max-height: 250px">

		<div class="bperson  cbox pt10" style="height: 200px">

			<?php
			$res = $db -> getAll("select * from ".$sqlname."personcat WHERE clid='".$data['clid']."' and identity = '$identity'");

			if (empty($res)) {
				print '<div class="row ha pad10">Нет контактов</div>';
			}

			foreach ($res as $da) {
				?>
				<div class="row ha">
					<div class="column grid-4">
						<b class="fs-12"><?= $da['person'] ?></b><br>
						<em class="gray2"><?= $da['ptitle'] ?></em>
					</div>
					<div class="column grid-6">
						<?php
						if ($da['tel'] != "") {

							$phone_list = [];
							$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['tel'])));
							foreach ($phones as $phone) {

								$ismob = '';
								if (substr(prepareMobPhone($phone), 1, 1) == '9') {
									$ismob = 'ismob';
								}

								$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$da['pid'].'" data-clid="'.$da['clid'].'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

							}
							$phone = implode(", ", $phone_list);
							print '<div>'.$phone.'</div>';
						}
						if ($da['fax'] != "") {

							$phone_list = [];
							$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['fax'])));
							foreach ($phones as $phone) {

								$ismob = '';
								if (substr(prepareMobPhone($phone), 1, 1) == '9') {
									$ismob = 'ismob';
								}

								$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$da['pid'].'" data-clid="'.$da['clid'].'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

							}
							$phone = implode(", ", $phone_list);
							print '<div>'.$phone.'</div>';

						}
						if ($da['mob'] != "") {

							$phone_list = [];
							$phones     = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['mob'])));
							foreach ($phones as $phone) {

								$ismob = '';
								if (substr(prepareMobPhone($phone), 1, 1) == '9') {
									$ismob = 'ismob';
								}

								$phone_list[] = ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? '<span class="phonec phonenumber '.$ismob.'" data-pid="'.$da['pid'].'" data-clid="'.$da['clid'].'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone).'</span>' : '<span class="phonec phonenumber">'.hidePhone($phone).'</span>';

							}
							$phone = implode(", ", $phone_list);
							print '<div>'.$phone.'</div>';

						}
						if ($da['mail'] != "") {
							$apx    = '';
							$emails = yexplode(",", $da['mail']);
							foreach ($emails as $email) {

								if ($ymEnable && $email != '') {
									$apx = '&nbsp;(<A href="javascript:void(0)" onClick="$mailer.composeCard(\'\',\''.$da['pid'].'\',\''.trim($email).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;';
								}
								print '<div>'.( ( $isAccess && $userSettings['hideAllContacts'] != 'yes' ) ? link_it($email) : hideEmail($email) ).$apx.'</div>';

							}
						}
						?>
					</div>
					<hr class="marg0 pad0">
				</div>
				<?php
			}
			?>

		</div>
		<div class="bhistory cbox pt10" style="height: 200px">

			<?php
			$res = $db -> getAll("select * from ".$sqlname."history WHERE clid='".$clid."' and identity = '$identity' ORDER BY datum DESC LIMIT 3");

			if (empty($res)) {
				print '<div class="row ha pad10">Нет записей</div>';
			}

			foreach ($res as $da) {
				?>
				<div class="row ha pad5">

					<div class="column grid-1">
						<b><?= get_sfdate($da['datum']) ?></b>
					</div>
					<div class="column grid-8 fs-11">
						<em class="gray2 fs-09"><?= $da['tip'] ?></em><br>
						<?= mb_substr(clean($da['des']), 0, 101, 'utf-8') ?>
					</div>

				</div>
				<hr>
			<?php
			} ?>

		</div>
		<div class="bdeals   cbox pt10" style="height: 200px">

			<?php
			$res = $db -> getAll("SELECT * FROM ".$sqlname."dogovor WHERE clid='".$data['clid']."' and identity = '$identity' ORDER BY field(close, 'no', 'yes')");

			if (empty($res)) {
				print '<div class="row ha pad10">Нет сделок</div>';
			}

			foreach ($res as $da) {

				$icon = ( $da['close'] == 'yes' ) ? "icon-lock red" : "icon-briefcase broun";

				$stepDay = abs(round(diffDate2($db -> getOne("SELECT MAX(datum) as datum FROM ".$sqlname."steplog WHERE did='".$da['did']."'"))));
				?>
				<div class="row ha">

					<div class="column12 grid-2"><?= get_date($da['datum']) ?></div>
					<div class="column12 grid-8">
						<b class="fs-12"><?= $da['title'] ?></b>&nbsp; <a href="javascript:void(0)" onclick="openDogovor('<?= $da['did'] ?>')"><i class="<?= $icon ?>"></i></a>
						<div class="em gray2 fs-09 mt5">
							<i class="icon-user-1"></i><?= current_user($da['iduser']) ?>,
							<i class="icon-rouble"></i><?= num_format($da['kol']) ?>
						</div>
					</div>
					<div class="column12 grid-2">
						<?= current_dogstepname($da['idcategory']) ?>% <sup class="gray2"><?= $stepDay ?> дн.</sup>
					</div>

				</div>
				<hr>
			<?php
			} ?>

		</div>

	</div>

</DIV>

<hr>

<div class="button--pane text-right">

	<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')" class="button"><i class="icon-building"></i>Карточка</a>

</div>

<?php
$hooks -> do_action("client_view", $_REQUEST);
?>
<script>

	$(function () {

		$('#dialog').css('width', '703px');

		$('.ftabs').each(function () {

			$(this).find('li').removeClass('active');
			$(this).find('li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		$('#dialog').center();

		ShowModal.fire({
			etype: 'cleintView'
		});

	});

	$('#ytabs li').on('click', function () {

		var link = $(this).data('link');
		var id = $(this).closest('.ftabs').attr('id');

		$('#' + id + ' li').removeClass('active');
		$(this).addClass('active');

		$('#' + id + ' .cbox').addClass('hidden');
		$('#' + id + ' .' + link).removeClass('hidden');

	});

	$('.phonec div').removeClass('ellipsis');

</script>