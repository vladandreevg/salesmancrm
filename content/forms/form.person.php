<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

use Salesman\Elements;

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

// перенаправляем на новую форму
if ( $action == 'add' ) {
	$action = 'edit';
}

if ($action == "edit") {

	$person = $soc = [];
	$messageid = $rid = 0;

	if($pid > 0) {

		$person = $db -> getRow( "select * from {$sqlname}personcat where pid='".$_GET['pid']."' and identity = '$identity'" );
		$soc    = explode( ';', $person['social'] );

	}
	else{

		$pid = 0;

		$rid       = (int)$_REQUEST['rid'];
		$messageid = (int)$_REQUEST['messageid'];

		if ($messageid > 0) {//подгрузим данные из письма

			$result           = $db -> getRow("SELECT * FROM {$sqlname}ymail_messages WHERE id = '$messageid' and identity = '$identity'");
			$datum            = $result['datum'];
			$theme            = $result['theme'];
			$person['mail']   = $result['fromm'];
			$person['person'] = $result['fromname'];
			$content          = $result['content'];

			//print $db -> lastQuery();

			$content = html2text($content);

			$data = html2data($content);

			//print_r($data);

			if ($data['phone'] != '') {

				$phones = preparePhoneSmart($data['phone'], false, true);

				$phone = $mob = [];

				foreach ($phones as $tel) {

					if (is_mobile($tel)) {
						$mob[] = $tel;
					}
					else {
						$phone[] = $tel;
					}

				}

				$person['mob'] = yimplode(",", $mob );
				$person['tel'] = yimplode(",", $phone );

			}
			if ($data['email'] != '') {
				$email2 = $data['email'];
			}

			if ($email2 != '') {

				$e = explode(", ", $email2);

				if (!in_array($person['mail'], (array)$e)) {
					$e[] = $person['mail'];
				}

				$e = array_unique($e);

				$person['mail'] = yimplode(", ", $e);

			}
			//else $person['mail'] = $email2;

		}
		elseif ($rid > 0) {//подгрузим данные из письма

			$result           = $db -> getRow("SELECT * FROM {$sqlname}ymail_messagesrec WHERE id = '$rid' and identity = '$identity'");
			$messageid        = (int)$result['mid'];
			$person['mail']   = $result['email'];
			$person['person'] = $result['name'];

			if ($person['person'] == '0') {
				$person['person'] = '';
			}

		}

		if ($clid > 0) {
			$person['clid'] = $clid;
		}

		$person['iduser'] = $iduser1;

	}
	?>
	<DIV class="zagolovok"><B>Редактирование Контакта</B></DIV>
	<?php
	if((int)$pid == 0) {

		$tcount = getOldTaskCount( (int)$iduser1 );
		if ( (int)$otherSettings[ 'taskControl'] > 0 && (int)$otherSettings[ 'taskControlClientAdd'] == 'yes' && (int)$tcount >= (int)$otherSettings[ 'taskControl'] ) {

			print '<div class="warning"><b class="red">Включен режим контроля выполненения дел.</b><br>У вас '.$tcount.' не выполненных дел - вы не можете создавать новые напоминания и добавлять Клиентов и Контакты, пока не закроете старые напоминания.</div>';
			exit();

		}

	}
	?>
	<FORM action="/content/core/core.person.php" method="post" enctype="multipart/form-data" name="personForm" id="personForm">
		<INPUT name="pid" id="pid" type="hidden" value="<?= $pid ?>">
		<INPUT name="action" id="action" type="hidden" value="person.edit">
		<INPUT type="hidden" id="messageid" name="messageid" value="<?= $messageid ?>">
		<INPUT type="hidden" id="rid" name="rid" value="<?= $rid ?>">
		<INPUT type="hidden" id="did" name="did" value="<?= $did ?>">

		<DIV id="formtabs" class="box--child mt20" style="max-height:75vh; overflow-x: hidden; overflow-y: auto !important;">

			<?php
			$hooks -> do_action("person_form_before", $_REQUEST);

			$resk = $db -> query("select * from {$sqlname}field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order");
			while ($dak = $db -> fetch($resk)) {

				if ($dak['fld_name'] == 'person') {
					?>
					<div class="flex-container mt10" id="prsn" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="<?= $dak['fld_name'] ?>" type="text" id="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95 validate" value="<?= $person['person'] ?>" autocomplete="off" onMouseOut="$('#ospisok').remove();" onblur="$('#ospisok').remove();" data-url="/content/helpers/person.helpers.php" data-action="validate">
							<div class="smalltxt">Начните с Фамилии. Например: Иванов Семен Петрович</div>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'ptitle') {

					$vars = str_replace(" \n", ",", $dak['fld_var']);
					$dx = !empty($vars) ? '' : 'suggestion';
					?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="<?= $dak['fld_name'] ?>" type="text" id="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95 <?=$dx?>" value="<?= $person['ptitle'] ?>">
							<?php
							if( !empty($vars) ){
								?>
								<div class="fs-09 em blue"><em>Двойной клик мышкой для показа вариантов</em></div>
								<script>
									var str = '<?=$vars?>';
									var data = str.split(',');
									$("#<?=$dak['fld_name']?>").autocomplete(data, {
										autofill: true,
										minLength: 0,
										minChars: 0,
										cacheLength: 5,
										maxItemsToShow: 20,
										selectFirst: true,
										multiple: false,
										delay: 0,
										matchSubset: 2
									});
								</script>
								<?php
							}
							else{

								print '<div class="smalltxt">Например: Генеральный директор</div>';

							}
							?>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'clientpath') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ">

							<?php
							$element = new Elements();
							print $su = $element -> ClientpathSelect( $dak['fld_name'], [
								"class" => [
									"wp95",
									$dak['requered']
								],
								"sel"   => $person['clientpath'],
								"data"  => 'data-class="'.$dak['requered'].'"'
							] );
							?>
							<?php if (!$otherSettings[ 'guidesEdit']) { ?>
								<span class="hidden-iphone">&nbsp;<a href="javascript:void(0)" onclick="add_sprav('clientpath','<?= $dak['fld_name'] ?>')" title="Добавить"><i class="icon-plus-circled-1 blue"></i></a></span>
							<?php } ?>

						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'iduser') {

					if($pid == 0) {
						?>
						<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10">

								<?php
								$element = new Elements();
								print $element -> UsersSelect( $dak['fld_name'], [
									"class"  => [
										"wp95",
										$dak['fld_required']
									],
									"active" => true,
									"sel"    => $person['iduser']
								] );
								?>

							</div>

						</div>
						<?php
					}
					else {
						print '<INPUT type="hidden" name="iduser" id="iduser" value="'.$person['iduser'].'">';
					}

				}
				elseif ($dak['fld_name'] == 'tel') { ?>
					<div class="flex-container mt10" id="vtel1" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<div id="v<?= $dak['fld_name'] ?>">
								<?php
								if ($format_phone != '') {

									if ($person['tel'] != '') {
										$phonep = yexplode(",", (string)$person['tel']);
										for ($i = 0, $iMax = count($phonep); $i < $iMax; $i++) {

											if ($i == (count($phonep) - 1)) {
												$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="v'.$dak['fld_name'].'" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
											}
											else {
												$adder = '';
											}

											?>
											<div class="phoneBlock paddbott5 relativv">
												<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $phonep[ $i ] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="person.helpers" autocomplete="off">
												<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span><?= $adder ?>
											</div>
											<?php
										}
									}
									else {
										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $person['tel'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="person.helpers" autocomplete="off">
											<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span>
											<span class="adder hand" title="" data-block="phoneBlock" data-main="v<?= $dak['fld_name'] ?>" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="phone <?= $dak['fld_required'] ?>" style="width:98%" id="<?= $dak['fld_name'] ?>" value="<?= $person['tel'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
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
				}
				elseif ($dak['fld_name'] == 'fax') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<div id="v<?= $dak['fld_name'] ?>">
								<?php
								if ($format_phone != '') {
									if ($person['fax'] != '') {
										$phonep = yexplode(",", (string)$person['fax']);
										for ($i = 0, $iMax = count($phonep); $i < $iMax; $i++) {

											if ($i == (count($phonep) - 1)) {
												$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="v'.$dak['fld_name'].'" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
											}
											else {
												$adder = '';
											}

											?>
											<div class="phoneBlock paddbott5 relativv">
												<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $phonep[ $i ] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="person.helpers" autocomplete="off">
												<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span><?= $adder ?>
											</div>
											<?php
										}
									}
									else {
										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $person['fax'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="person.helpers" autocomplete="off">
											<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span>
											<span class="adder hand" title="" data-block="phoneBlock" data-main="v<?= $dak['fld_name'] ?>" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="phone <?= $dak['fld_required'] ?>" style="width:98%" id="<?= $dak['fld_name'] ?>" value="<?= $person['fax'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
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
				}
				elseif ($dak['fld_name'] == 'mob') { ?>
					<div class="flex-container mt10" id="vmob1" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<div id="v<?= $dak['fld_name'] ?>">
								<?php
								if ($format_phone != '') {
									if ($person['mob'] != '') {
										$phonep = yexplode(",", (string)$person['mob']);
										for ($i = 0, $iMax = count($phonep); $i < $iMax; $i++) {

											if ($i == (count($phonep) - 1)) {
												$adder = '<span class="adder hand" title="" data-block="phoneBlock" data-main="v'.$dak['fld_name'].'" data-mask="'.$format_phone.'"><i class="icon-plus-circled green"></i></span>';
											}
											else {
												$adder = '';
											}

											?>
											<div class="phoneBlock paddbott5 relativv">
												<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $phonep[ $i ] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="person.helpers" autocomplete="off">
												<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span><?= $adder ?>
											</div>
											<?php
										}
									}
									else {
										?>
										<div class="phoneBlock paddbott5 relativv">
											<INPUT name="<?= $dak['fld_name'] ?>[]" type="text" class="phone w250 <?= $dak['fld_required'] ?>" id="<?= $dak['fld_name'] ?>[]" value="<?= $person['mob'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="person.helpers" autocomplete="off">
											<span class="remover hand" data-parent="v<?= $dak['fld_name'] ?>"><i class="icon-minus-circled red"></i></span>
											<span class="adder hand" title="" data-block="phoneBlock" data-main="v<?= $dak['fld_name'] ?>" data-mask="<?= $format_phone ?>"><i class="icon-plus-circled green"></i></span>
										</div>
										<?php
									}
								}
								else {
									?>
									<div class="phoneBlock paddbott5 relativv">
										<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="phone <?= $dak['fld_required'] ?> wp98" id="<?= $dak['fld_name'] ?>" value="<?= $person['mob'] ?>" placeholder="Формат: <?= $format_tel ?>" data-id="v<?= $dak['fld_name'] ?>" data-action="valphone" data-type="client.helpers" autocomplete="off">
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
				}
				elseif ($dak['fld_name'] == 'mail') { ?>
					<div class="flex-container mt10" id="vmail" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 norelativ">
							<INPUT name="<?= $dak['fld_name'] ?>" type="text" class="<?= $dak['fld_required'] ?> wp95 validate" id="<?= $dak['fld_name'] ?>" autocomplete="off" value="<?= $person['mail'] ?>" onMouseOut="$('#ospisok').remove();" onblur="$('#ospisok').remove();" data-url="/content/helpers/person.helpers.php" data-action="valmail">
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'clid') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10 relativ" id="org">
							<INPUT type="hidden" id="<?= $dak['fld_name'] ?>" name="<?= $dak['fld_name'] ?>" value="<?= $person['clid'] ?>"><INPUT id="lst_spisok" type="text" class="<?= $dak['fld_required'] ?> wp95" value="<?= current_client($person['clid']) ?>" readonly onclick="get_orgspisok('lst_spisok','org','/content/helpers/client.helpers.php?action=get_orgselector','<?= $dak['fld_name'] ?>')" placeholder="Нажмите для выбора">
							<span class="idel">&nbsp;&nbsp;<i title="Очистить" onclick="$('input#<?= $dak['fld_name'] ?>').val(0); $('#lst_spisok').val('');" class="icon-block red" style="cursor:pointer"></i></span>
							<div class="smalltxt">К какому Клиенту прикрепить Контакт</div>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'rol') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="<?= $dak['fld_name'] ?>" id="<?= $dak['fld_name'] ?>" type="text" class="ac_input <?= $dak['fld_required'] ?> wp95" autocomplete="on" value="<?= $person['rol'] ?>">
							<div class="smalltxt">Например: Принимает решение</div>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'social') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Блог:</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="blog" type="text" id="blog" class="wp95" value="<?= $soc[0] ?>" autocomplete="off">
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сайт:</div>
						<div class="flex-string wp80 pl10">
							<INPUT name="mysite" type="text" id="mysite" class="wp95" value="<?= $soc[1] ?>" autocomplete="off">
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Twitter:</div>
						<div class="flex-string wp80 pl10">
							<input name="twitter" type="text" id="twitter" class="wp95" value="<?= $soc[2] ?>" autocomplete="off"/>
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">ICQ:</div>
						<div class="flex-string wp80 pl10">
							<input name="icq" type="text" id="icq" class="wp95" value="<?= $soc[3] ?>" autocomplete="off"/>
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Skype:</div>
						<div class="flex-string wp80 pl10">
							<input name="skype" type="text" id="skype" class="wp95" value="<?= $soc[4] ?>" autocomplete="off"/>
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Google+:</div>
						<div class="flex-string wp80 pl10">
							<input name="google" type="text" id="google" class="wp95" value="<?= $soc[5] ?>" autocomplete="off"/>
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Facebook:</div>
						<div class="flex-string wp80 pl10">
							<input name="yandex" type="text" id="yandex" class="wp95" value="<?= $soc[6] ?>" autocomplete="off"/>
						</div>

					</div>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">VKontakte:</div>
						<div class="flex-string wp80 pl10">
							<input name="mykrug" type="text" id="mykrug" class="wp95" value="<?= $soc[7] ?>" autocomplete="off"/>
						</div>

					</div>
					<?php
				}
				elseif ($dak['fld_name'] == 'loyalty') { ?>
					<div class="flex-container mt10" data-block="<?php echo $dak['fld_name'] ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
						<div class="flex-string wp80 pl10">
							<?php
							$element = new Elements();
							print $element -> LoyaltySelect( $dak['fld_name'], [
								"class" => [
									"wp95",
									$dak['requered']
								],
								"sel"   => $person['loyalty'],
								"data"  => 'data-class="'.$dak['requered'].'"'
							] );
							?>
						</div>

					</div>
					<?php
				}
				elseif (stripos($dak['fld_name'], 'input') !== false) {

					if ($dak['fld_temp'] == "textarea") {
						?>
						<div id="divider"><b><?= $dak['fld_title'] ?></b></div>

						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp100 relativ div-center">
								<textarea name="<?= $dak['fld_name'] ?>" rows="4" class="<?= $dak['fld_required'] ?> p5 wp95" id="<?= $dak['fld_name'] ?>"><?= $person[ $dak['fld_name'] ] ?></textarea>
							</div>

						</div>
						<?php
					}
					elseif ($dak['fld_temp'] == "--Обычное--" || $dak['fld_temp'] == "") {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<INPUT name="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95" id="<?= $dak['fld_name'] ?>" value="<?= $person[ $dak['fld_name'] ] ?>" autocomplete="off">
							</div>

						</div>
						<?php
					}
					elseif ( $dak[ 'fld_temp' ] == "hidden" ) {
						?>
						<input id="<?= $dak[ 'fld_name' ] ?>" name="<?= $dak[ 'fld_name' ] ?>" type="hidden" value="<?= $person[ $dak['fld_name'] ] ?>">
						<?php
					}
					elseif ($dak['fld_temp'] == "adres") {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<INPUT name="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95" id="<?= $dak['fld_name'] ?>" value="<?= $person[ $dak['fld_name'] ] ?>" data-type="address">
							</div>

						</div>
						<?php
					}
					elseif ($dak['fld_temp'] == "select") {

						$vars = explode(",", $dak['fld_var']);
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">

								<?php
								$datas = [];
								foreach($vars as $var) {
									$datas[] = ["id"    => $var, "title" => $var];
								}

								print $su = Elements::Select( $dak['fld_name'], $datas, [
									"class" => [
										"wp95",
										$dak['fld_required']
									],
									"nowrapper" => true,
									"sel"   => $person[ $dak['fld_name'] ],
									"data"  => 'data-class="'.$dak['fld_required'].'"'
								] );
								?>

							</div>

						</div>
						<?php
					}
					elseif ($dak['fld_temp'] == "multiselect") {

						$vars = explode(",", $dak['fld_var']);
						?>
						<div id="divider"><b><?= $dak['fld_title'] ?></b></div>

						<div class="flex-container box--child mt10 <?= ($dak['fld_required'] == 'required' ? 'multireq' : '') ?>" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp100 relativ">

								<?php
								$datas = [];
								foreach($vars as $var) {
									$datas[] = ["id"    => $var,
									            "title" => $var
									];
								}
								print $su = Elements::Select( $dak['fld_name']."[]", $datas, [
									"class" => [
										"wp95",
										$dak['fld_required']
									],
									"nowrapper" => true,
									"multiple" => true,
									"multipleInit" => false,
									"sel"   => yexplode(",", (string)$person[ $dak['fld_name'] ]),
									"data"  => 'data-class="'.$dak['fld_required'].'"'
								] );
								?>

							</div>

						</div>
						<?php
					}
					elseif ($dak['fld_temp'] == "inputlist") {

						$vars = $dak['fld_var'];
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<input type="text" name="<?= $dak['fld_name'] ?>" id="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> wp95" value="<?= $person[ $dak['fld_name'] ] ?>" placeholder="<?= $dak['fld_title'] ?>"/>
								<div class="fs-09 em blue"><em>Двойной клик мышкой для показа вариантов</em></div>
								<script>
									var str = '<?=$vars?>';
									var data = str.split(',');
									$("#<?=$dak['fld_name']?>").autocomplete(data, {
										autofill: true,
										minLength: 0,
										minChars: 0,
										cacheLength: 5,
										maxItemsToShow: 20,
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
					elseif ($dak['fld_temp'] == "radio") {

						$vars = explode(",", $dak['fld_var']);
						?>
						<div class="flex-container box--child mt20 mb20 <?= ($dak['fld_required'] == 'required' ? 'req' : '') ?>" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">

								<div class="flex-container box--child wp95--5">

									<?php
									for ($j = 0, $jMax = count($vars); $j < $jMax; $j++) {

										$s = ($vars[ $j ] == $person[ $dak['fld_name'] ]) ? 'checked' : '';
										?>
										<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
											<div class="radio">
												<label>
													<input name="<?= $dak['fld_name'] ?>" type="radio" id="<?= $dak['fld_name'] ?>" <?= $s ?> value="<?= $vars[ $j ] ?>"/>
													<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
													<span class="title"><?= $vars[ $j ] ?></span>
												</label>
											</div>
										</div>
									<?php } ?>
									<?php if($dak['fld_required'] != 'required'){ ?>
										<div class="flex-string p10 mr5 mb5 flx-basis-20 viewdiv bgwhite inset">
											<div class="radio">
												<label>
													<input name="<?= $dak['fld_name'] ?>" type="radio" id="<?= $dak['fld_name'] ?>" <?=($person[ $dak['fld_name'] ] == '' ? 'checked' : '')?> value="">
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
					elseif ($dak['fld_temp'] == "datum") {
						?>
						<div class="flex-container box--child mt10" data-block="<?php echo $dak['fld_name'] ?>">

							<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><?= $dak['fld_title'] ?>:</div>
							<div class="flex-string wp80 pl10 relativ">
								<INPUT name="<?= $dak['fld_name'] ?>" type="text" id="<?= $dak['fld_name'] ?>" class="<?= $dak['fld_required'] ?> yw120 inputdate" value="<?= $person[ $dak['fld_name'] ] ?>" autocomplete="off">
							</div>

						</div>
						<?php
					}

				}

			}
			?>

		</DIV>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#personForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php

	$hooks -> do_action("person_form_after", $_REQUEST);

}

if ($action == "mass") {

	$id  = (array)$_REQUEST['ch'];
	$ids = implode(",", $id);
	$kol = count($id);
	?>
	<div class="zagolovok"><b>Групповое действие</b></div>
	<form action="/content/core/core.person.php" id="personForm" name="personForm" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $ids ?>">
		<input name="action" id="action" type="hidden" value="person.mass">

		<DIV id="formtabs" class="box--child mt20" style="overflow-x: hidden; overflow-y: auto !important;">

			<div class="infodiv mb10">
				<b class="red">Важная инфрмация:</b>
				<ul>
					<li class="Bold blue">При нажатой клавише Ctrl можно мышкой выбрать нужные записи</li>
					<li>Отмена групповых действий не возможна</li>
					<li>Действия будут применены только для записей, к которым у вас есть доступ</li>
					<li>Ограничение для действия составляет 1000 записей</li>
				</ul>
			</div>

			<!--Старая реализация-->
			<div class="flex-container mb10 hidden">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>
				<div class="flex-string wp80 pl10">
					<select name="doAction1" id="doAction1" onchange="showd()">
						<option value="">--выбор действия--</option>
						<option value="userChange">Смена ответственного</option>
						<option value="cmrChange">Установить тип лояльности</option>
						<?php if ($userRights['group']) { ?>
						<option value="groupChange">Добавить в группу</option>
						<?php } ?>
						<?php if ($isadmin == 'on' || $tipuser == 'Администратор' || ($userRights['groupactions'] || $userRights['delete'])) { ?>
						<option value="clientDelete">Удалить навсегда</option>
						<?php } ?>
					</select>
				</div>

			</div>
			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действие:</div>
				<div class="flex-string wp80 pl10">

					<div class="flex-container box--child wp95--5">

						<?php
						if (!$userRights['nouserchange']) {
							?>
							<div class="flex-string p10 mr5 mb5 flx-basis-30 viewdiv bgwhite inset bluebg-sub" data-type="check">

								<div class="radio">
									<label>
										<span class="hidden">
											<input name="doAction" type="radio" id="doAction" value="userChange" checked onchange="showd()">
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										</span>
										<span class="title"><i class="icon-user-1 blue"></i>&nbsp;Ответственный</span>
									</label>
								</div>

							</div>
						<?php } ?>

						<div class="flex-string p10 mr5 mb5 flx-basis-30 viewdiv bgwhite inset" data-type="check">

							<div class="radio">
								<label>
									<span class="hidden">
										<input name="doAction" type="radio" id="doAction" value="cmrChange" onchange="showd()">
										<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									</span>
									<span class="title"><i class="icon-handshake-o green"></i>&nbsp;Тип&nbsp;лояльности</span>
								</label>
							</div>

						</div>

						<?php
						if ($userRights['group']) {
							?>
							<div class="flex-string p10 mr5 mb5 flx-basis-30 viewdiv bgwhite inset" data-type="check">

								<div class="radio">
									<label>
										<span class="hidden">
											<input name="doAction" type="radio" id="doAction" value="groupChange" onchange="showd()">
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										</span>
										<span class="title"><i class="icon-sitemap fiolet"></i>&nbsp;В&nbsp;группу</span>
									</label>
								</div>

							</div>
						<?php } ?>

						<?php
						if ($isadmin == 'on' || $tipuser == 'Администратор' || ($userRights['groupactions'] && $userRights['delete'])) {
							?>
							<div class="flex-string p10 mr5 mb5 flx-basis-30 viewdiv bgwhite inset" data-type="check">

								<div class="radio">
									<label>
										<span class="hidden">
											<input name="doAction" type="radio" id="doAction" value="clientDelete" onchange="showd()">
											<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
										</span>
										<span class="title"><i class="icon-cancel-circled red"></i>&nbsp;Удалить&nbsp;навсегда</span>
									</label>
								</div>

							</div>
						<?php } ?>

					</div>

				</div>

			</div>

			<div class="flex-container mb10" id="userdiv">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый:</div>
				<div class="flex-string wp80 pl10">
					<select name="newuser" id="newuser" class="wp95">
						<option value="">--выбор--</option>
						<?php
						$result = $db -> query("SELECT * FROM {$sqlname}user WHERE secrty='yes' and identity = '$identity' ORDER by title");
						while ($data = $db -> fetch($result)) {

							$s = ($data['iduser'] == $iduser1) ? "selected" : "";
							print '<option value="'.$data['iduser'].'" '.$s.'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>
			<div class="flex-container mb10 hidden" id="cmrdiv">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип лояльности:</div>
				<div class="flex-string wp80 pl10">
					<select name="tipcmr" id="tipcmr" class="wp95">
						<?php
						$result = $db -> query("SELECT * FROM {$sqlname}loyal_cat WHERE identity = '$identity' ORDER by title");
						while ($data = $db -> fetch($result)) {

							print '<option value="'.$data['idcategory'].'">'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>
			<div class="flex-container mb10 hidden" id="grpt">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Группа:</div>
				<div class="flex-string wp80 pl10">
					<select name="newgid" id="newgid" class="wp95">
						<option value="">--выбор--</option>
						<?php
						print '<optgroup label="Группа CRM"></optgroup>';

						$result = $db -> query("SELECT * FROM {$sqlname}group WHERE service = '' and identity = '$identity'");
						while ($data_array = $db -> fetch($result)) {
							print '<option value="'.$data_array['id'].'">&nbsp;&nbsp;'.$data_array['name'].'</option>';
						}

						$result = $db -> query("SELECT * FROM {$sqlname}services WHERE user_key != '' and tip = 'mail' and identity = '$identity'");
						while ($data = $db -> fetch($result)) {

							print '<optgroup label="'.$data['name'].'"></optgroup>';

							$re = $db -> query("SELECT * FROM {$sqlname}group WHERE service = '".$data['name']."' and identity = '$identity'");
							while ($da = $db -> fetch($re)) {
								print '<option value="'.$da['id'].'">&nbsp;&nbsp;'.$da['name'].'</option>';
							}

						}
						?>
					</select>
					<div class="infodiv bgwhite wp95 mt5">Позиции будут добавлены в выбранную группу. Если запись относится к сервису рассылок, то подписчик будет добавлен в список на стороне сервиса.</div>
				</div>

			</div>

			<div class="flex-container mb10 mb10 pt15 warning bgwhite">

				<div class="flex-string wp20 gray2 fs-12 right-text">Выполнить для:</div>
				<div class="flex-string wp40 pl10">
					<div class="radio">
						<label>
							<input name="isSelect" id="isSelect" value="doSelected" type="radio" <?php if ($kol > 0) {
								print "checked";
							} ?>>
							<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
							<span class="title">Выбранное (<b class="blue"><?= $kol ?></b>)</span>
						</label>
					</div>
				</div>
				<div class="flex-string wp40 pl10">
					<div class="radio">
						<label>
							<input name="isSelect" id="isSelect" value="doAll" type="radio" <?php if ($kol == 0) {
								print "checked";
							} ?>>
							<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
							<span class="title">Со всех страниц (<b class="blue"><span id="alls"></span></b>)</span>
						</label>
					</div>
				</div>

			</div>
			<div class="flex-container mb10" id="reazon">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Причина:</div>
				<div class="flex-string wp80 pl10">
					<textarea name="reazon" id="reazon" class="wp95"></textarea>
				</div>

			</div>
			<div class="flex-container mb10 hidden" id="dltt">

				<div class="flex-string wp100 pl10">

					<div class="infodiv"><b>Удаление контактов со Сделками не поддерживается</b>.
						<b class="red">Отмена действия не возможна.</b><br><br>
						Также будут удалены связанные с Контактом записи:<br>
						<ul>
							<li>Истории активностей</li>
							<li>Напоминания</li>
							<li>Файлы</li>
						</ul>
					</div>

				</div>

			</div>

		</DIV>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="massSubmit()" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<?php
}
?>
<script type="text/javascript" src="/assets/js/app.form.js"></script>
<script>

	var action = $('#action').val();
	var pid = $('#pid').val();
	var formatPhone = '<?=$format_phone?>';

	if (!isMobile) {

		$('#dialog').css('width', '800px');

		if (in_array(action, ['person.add', 'person.edit'])) {

			var dwidth = $(document).width();
			var dialogWidth;
			var dialogHeight;

			if (dwidth < 945) {
				dialogWidth = '90%';
				dialogHeight = '95vh';
			}
			else {
				dialogWidth = '80%';
			}

			//var hh = $('#dialog_container').actual('height') * 0.8;
			//var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 20;

			//$('.fmain').css({'width': '100%'});

			if (dwidth < 945) {
				$('#dialog').css({'width': dialogWidth, 'height': dialogHeight});
				//$('.fmain').css({'height': 'unset', 'max-height': hh2 + 30});
			}
			else {
				//$('.fmain').css({'max-height': hh2});
			}

		}
		$(".multiselect").multiselect({sortable: true, searchable: true});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

		if (isMobile) $('table').rtResponsiveTables();

	}

	$(function () {

		$("#person").trigger('focus');
		//$('input[type="radio"]:checked').trigger('change');

		$('.inputdate').each(function () {

			if (!isMobile) $(this).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

		});

		//Формат номеров телефонов
		if (formatPhone !== '') reloadMasks();

		if ($('#allSelected').is('input')) $('#alls').html($('#allSelected').val());

		<?php //print $dat; ?>

		$('#personForm').ajaxForm({
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
				var ipid = parseInt($('#pid').val());
				var iclid = parseInt($('#clid').val());

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (isCard) {

					var card = $('#card').val();
					var clid = $('#ctitle #clid').val();
					var pid = $('#pid').val();
					var did = $('#did').val();

					if (card === 'client')
						settab('2', false);
					if (card === 'person')
						settab('0', false);
					if (card === 'dogovor')
						settab('0', false);

					cardload();

				}
				else {

					if (data.pid > 0 && action === 'person.edit' && ipid === 0 && iclid === 0) {

						window.open('card.person?pid=' + data.pid);
						configpage();

					}
					else {
						configpage();
					}

					if ($display === 'desktop') {
						$desktop.contacts();
					}

					<?php if($messageid > 0){?>
					if ($display === 'mailer') {
						loadMes('<?=$messageid?>');
					}
					<?php } ?>

				}

			}
		});

		$('#dialog').center();

		$("#rol").autocomplete("/content/helpers/person.helpers.php?action=get.role", {
			autofill: false,
			minChars: 3,
			cacheLength: 1,
			maxItemsToShow: 20,
			selectFirst: true,
			multiple: true,
			multipleSeparator: "; ",
			delay: 500
		});

		if( $("#ptitle").hasClass('suggestion') ) {
			$("#ptitle").autocomplete("/content/helpers/person.helpers.php?action=get.status", {
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

		$("#theme").autocomplete("/content/core/core.tasks.php", {
			autofill: true,
			minChars: 0,
			cacheLength: 20,
			maxItemsToShow: 20,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1
		});

		if (!isMobile) $("#datum_task").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true,
			yearRange: "1940:2020",
			minDate: new Date(1940, 1 - 1, 1)
		});
		if (!isMobile) $('#totime_task').ptTimeSelect();

		doLoadCallback('personForm');

		ShowModal.fire({
			etype: 'personForm',
			action: action
		});

	});

	$('input[type="radio"]')
		.off('change')
		.on('change', function (){

			var xprop = $(this).prop('checked');

			$('div[data-type="check"]').removeClass('bluebg-sub')

			if(xprop){
				$(this).closest('div[data-type="check"').addClass('bluebg-sub')
			}

		})

	function reloadMasks() {

		//Формат номеров телефонов
		if (formatPhone !== '') {

			$('.phone').each(function () {

				$(this).phoneFormater(formatPhone);

			});

		}

	}

	function gettags() {
		var tip = urlEncodeData($('#tip option:selected').val());
		$('#tagbox').load('/content/ajax/tags.php?tip=' + tip);
	}

	function tagit(id) {
		var html = $('#tag_' + id).html();
		insTextAtCursor('des', html + '; ');
	}

	function showd() {

		var cel = $('#doAction:checked').val();

		if (cel === 'userChange') {
			$('#userdiv').removeClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#reazon').removeClass('hidden');
			$('#grpt').addClass('hidden');
			$('#dltt').addClass('hidden');
		}
		else if (cel === 'dostupChange') {
			$('#dostupdiv').removeClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#reazon').removeClass('hidden');
			$('#grpt').addClass('hidden');
			$('#dltt').addClass('hidden');
		}
		else if (cel === 'cmrChange') {
			$('#cmrdiv').removeClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#dltt').addClass('hidden');
		}
		else if (cel === 'groupChange') {
			$('#grpt').removeClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#dltt').addClass('hidden');
		}
		else if (cel === 'clientDelete') {
			$('#grpt').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#dltt').removeClass('hidden');
		}
		else {
			$('#userdiv').addClass('hidden');
			$('#dostupdiv').addClass('hidden');
			$('#cmrdiv').addClass('hidden');
			$('#reazon').addClass('hidden');
			$('#grpt').addClass('hidden');
			$('#dltt').addClass('hidden');
		}
		$('#dialog').center();
	}

	function massSubmit() {

		var empty = $(".required").removeClass("empty").filter('[value=""]').addClass("empty");

		if (empty.size()) {

			empty.css({color: "#ffffff", background: "#FF8080"});
			alert("Не заполнены обязательные поля\n\rОни выделены цветом");

		}
		if (!empty.size()) {

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			var str = $('#personForm').serialize() + '&' + $('#pageform').serialize();
			var url = "/content/core/core.person.php";

			$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных...</div>');

			$.post(url, str, function (data) {

				$('#resultdiv').empty();

				configpage();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}, 'json');
			
		}

	}

</script>