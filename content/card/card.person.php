<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(0);
ini_set( 'display_errors', 1 );
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$clid = (int)$_REQUEST['clid'];
$pid = (int)$_REQUEST['pid'];
$did = (int)$_REQUEST['did'];

$person = get_person_info($pid, "yes");

$creator = ((int)$person['creator'] > 0) ? get_user($person['creator']) : "Не определено";
$editor  = ((int)$person['editor'] > 0) ? get_user($person['editor']) : "Не определено";

$date_create = (get_sfdate($person['date_create']) != '') ? get_sfdate($person['date_create']) : "??";
$date_edit   = (get_sfdate($person['date_edit'])   != '') ? get_sfdate($person['date_edit']) : "??";
?>
<DIV class="fcontainer relativ p0 border--bottom">

	<div class="divider mt15 mb15">Информация</div>

	<div id="cInfo" class="flex-vertical bgwhite p0 border--bottom box--child">

		<?php
		if (stripos($tipuser, 'Руководитель') !== false && $clid > 0) {
			?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10">ID записи</div>
				<div class="flex-string wp75">
					<b class="Bold"><?= $person['pid'] ?></b>
				</div>

			</div>
			<?php
		}
		?>
		<div class="flex-container p10">

			<div class="flex-string wp25 gray2 pr10">Автор</div>
			<div class="flex-string wp75 relativ">
				<b><?=$creator?></b>
				<div class="pull-aright noBold fs-09 blue"><?=$date_create?></div>
			</div>

		</div>
		<?php if ($editor != '') { ?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10">Редактор</div>
				<div class="flex-string wp75 relativ">
					<b><?=$editor?></b>
					<div class="dright noBold fs-09 blue"><?=$date_edit?></div>
				</div>

			</div>
		<?php } ?>
		<div class="flex-container p10">

			<div class="flex-string wp25 gray2 pr10"><?= $fieldsNames['person']['iduser'] ?></div>
			<div class="flex-string wp75">
				<B class="red"><?= current_user($person['iduser']); ?></B>&nbsp;
				<?php
				if ( !$userRights['nouserchange'] && get_accesse(0, (int)$pid) == 'yes' ) {

					?>
					<a href="javascript:void(0)" onclick="editPerson('<?= $pid ?>','change.user');" title="Изменить ответственного" class="dright gray"><i class="icon-pencil blue"></i></a>
					<?php

				}
				?>
			</div>

		</div>

	</div>

	<div class="divider mt15 mb15">Детали</div>

	<div id="cMain" class="flex-vertical bgwhite border--bottom p0 box--child">

	<?php
	$re = $db -> query("select fld_name, fld_title, fld_temp, fld_stat from {$sqlname}field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order");
	while ($da = $db -> fetch($re)){

		if ($da['fld_name'] == 'person' && $person['person'] != "") {?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75 relativ">
					<B><?=$person['person']?></B>&nbsp;
					<A href="javascript:void(0)" onclick="openPerson('<?=$person['pid']?>')" class="dright gray"><i class="icon-user-1 blue"></i></A>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'ptitle' && $person['ptitle'] != "") {?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75">
					<?=$person['ptitle']?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'clid' && $person['clid'] > 0) {
		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75">
					<A href="javascript:void(0)" onclick="viewClient('<?=$person['clid']?>');" title="Просмотр"><?=current_client($person['clid'])?></A>&nbsp;
					<a href="javascript:void(0)" onclick="openClient('<?=$person['clid']?>');" title="Открыть карточку" class="dright gray"><i class="icon-building blue"></i></a>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'clientpath' && $person['clientpath'] > 0) {
		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75">
					<?=$person['clientpath2']?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'tel' && $person['tel'] != "") {

			$phone_list = array();
			$phones = yexplode(",", str_replace(";", ",", str_replace(" ", "", $person['tel'])));
			foreach($phones as $phone){

				$phone_list[] = '<div class="disable--select phonec phonenumber '.(is_mobile($phone) ? 'ismob' : '').'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone,$person['clid'],$pid).'</div>';

			}
			$phone = implode("", $phone_list);
		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75 xpmt">
					<?=$phone?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'mob' && $person['mob'] != "") {

			$phone_list = array();
			$mobi = yexplode(",", str_replace(";", ",", str_replace(" ", "", $person['mob'])));

			foreach($mobi as $phone){

				$phone_list[] = '
					<div class="phonec phonenumber '.(is_mobile($phone) ? 'ismob' : '').'" data-phone="'.prepareMobPhone($phone).'">
					'.formatPhoneUrl($phone,$person['clid'],$pid).'
					</div>
				';

			}
			$mob = implode("", $phone_list);

		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75 xpmt">
					<?=$mob?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'fax' && $person['fax'] != "") {

			$phone_list = array();
			$fax = yexplode(",", str_replace(";", ",", str_replace(" ", "", $person['fax'])));

			foreach($fax as $phone){

				$phone_list[] = '
				<div class="phonec phonenumber '.(is_mobile($phone) ? 'ismob' : '').'" data-phone="'.prepareMobPhone($phone).'">
					'.formatPhoneUrl($phone,$person['clid'],$pid).'
				</div>
				';

			}
			$fax = implode("", $phone_list);

		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75 xpmt">
					<?=$fax?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'mail' && $person['mail'] != "") {
		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75">
					<?php
					$emails = explode(",", str_replace(";",",",$person['mail']));
					foreach($emails as $email) {

						$apx = ($ymEnable == true) ? '&nbsp;(<A href="javascript:void(0)" onclick="$mailer.composeCard(\'\',\''.$pid.'\',\''.trim($email).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : "";

						print link_it($email).$apx;

					}
					?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'rol' && $person['rol'] != "") {?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75">
					<?=$person['rol']?>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'loyalty' && $person['loyalty'] > 0) {
		?>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
				<div class="flex-string wp75">
					<B><?=current_loyalty($person['loyalty'])?></B>
				</div>

			</div>
		<?php
		}
		elseif ($da['fld_name'] == 'social') {

			$soc = yexplode(';',$person['social']);

			if (count($soc) > 0 && $person['social'] != "") {
		?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
					<div class="flex-string wp75">
						<?php
						if ($soc[0]!='') print '<DIV class="tcn"><a href="http://'.str_replace("http://","", $soc[0]).'" target="_blank" title="'.$soc[0].'"><b>Блог</b>&nbsp;<i class="icon-globe broun"></i></a></DIV>&nbsp;';
						if ($soc[1]!='') print '<DIV class="tcn"><a href="http://'.str_replace("http://","", $soc[1]).'" target="_blank" title="'.$soc[1].'"><b>Сайт</b>&nbsp;<i class="icon-globe broun"></i></a></DIV>&nbsp;';
						if ($soc[2]!='') print '<DIV class="tcn"><a href="http://twitter.com/#!/'.str_replace("http://twitter.com/#!/","", $soc[2]).'" target="_blank" title="'.$soc[2].'"><b>Twitter</b>&nbsp;<i class="icon-twitter blue"></i></a></DIV>&nbsp;';
						if ($soc[3]!='') print '<DIV class="tcn"><b>ICQ '.$soc[3].'</b>&nbsp;<i class="icon-cog-1 green"></i></DIV>&nbsp;';
						if ($soc[4]!='') print '<DIV class="tcn"><a href="skype:'.$soc[4].'" target="_blank" title="Позвонить: '.$soc[4].'">&nbsp;<i class="icon-skype green"></i>&nbsp;<b>'.$soc[4].'</b></a> или <a href="skype:'.$soc[4].'?chat" title="Начать чат: '.$soc[4].'">&nbsp;<i class="icon-skype green"></i>&nbsp;<b>'.$soc[4].'</b></a></DIV>&nbsp;';
						if ($soc[5]!='') print '<DIV class="tcn"><a href="http://'.str_replace("http://","", $soc[5]).'" target="_blank" title="'.$soc[6].'"><b>Google+</b>&nbsp;<i class="icon-gplus-squared red"></i></a></DIV>&nbsp;';
						if ($soc[6]!='') print '<DIV class="tcn"><a href="http://'.str_replace("http://","", $soc[6]).'" target="_blank" title="'.$soc[6].'"><b>Facebook</b>&nbsp;<i class="icon-facebook-squared blue"></i></a></DIV>&nbsp;';
						if ($soc[7]!='') print '<DIV class="tcn"><a href="http://'.str_replace("http://","", $soc[7]).'" target="_blank" title="'.$soc[6].'"><b>VKontakte</b>&nbsp;<i class="icon-vkontakte blue"></i></a></DIV>&nbsp;';
						?>
					</div>

				</div>
		<?php
			}

		}
		elseif ($da['fld_stat'] != 'yes') {

			if ($person[$da['fld_name']] != '' && $da['fld_temp'] !== 'textarea'){

				if ($da['fld_temp']=="datum")
					$field = '<b class="green">'.format_date_rus_name($person[$da['fld_name']]).'</b>';

				else if ($da['fld_temp']=="adres")
					$field = '<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q='.$person[$da['fld_name']].'" target="_blank">'.$person[$da['fld_name']].'</a>';

				else
					$field = $person[$da['fld_name']];
		?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
					<div class="flex-string wp75" style="max-height:250px">
						<?=nl2br($field)?>
					</div>

				</div>
		<?php
			}
			elseif($person[$da['fld_name']]!='' && $da['fld_temp'] == 'textarea'){

				$field = $person[$da['fld_name']];
		?>
				<div class="flex-container p10">

					<div class="flex-string wp25 gray2 pr10"><?= $da['fld_title'] ?></div>
					<div class="flex-string wp75" style="min-height:30px; max-height:300px; overflow:auto !important;">
						<?=nl2br($field)?>
					</div>

				</div>
		<?php
			}

		}

	}
	?>

	</div>

</DIV>