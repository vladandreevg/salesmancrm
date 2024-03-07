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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];

$loyalty = [];
$loy     = $db -> getAll("SELECT idcategory, title, color FROM ".$sqlname."loyal_cat WHERE identity = '$identity'");
foreach ($loy as $da) {

	$loyalty['title'][ $da['idcategory'] ] = $da['title'];
	$loyalty['color'][ $da['idcategory'] ] = $da['color'];

}

//Основной контакт клиента
$pidM = getClientData($clid, 'pid');

$result = $db -> getAll("select * from ".$sqlname."personcat WHERE clid = '$clid' AND identity = '$identity' ORDER BY FIELD(pid,'$pidM') DESC, person");

$count = count($result);

foreach ($result as $da) {

	$btn = '';
	$accesse = ( (get_accesse((int)$clid) == "yes" && $tipuser != 'Поддержка продаж') || (get_accesse($clid, 0, 0) == "yes" && $tipuser == 'Поддержка продаж' && $da['iduser'] == $iduser1));

	if( $accesse ){
		$btn .= '<a href="javascript:void(0)" onclick="addTask(\'\',\''.$da['clid'].'\',\''.$da['pid'].'\',\'\');" class="gray green mr5"><i class="icon-calendar-1" title="Добавить напоминание"></i></a>';
	}
	if($ac_import[12] == 'on'){
		$btn .= '<A href="javascript:void(0)" onclick="editPerson(\''.$da['pid'].'\',\'edit\');" title="Редактировать" class="gray blue mr5"><i class="icon-pencil blue"></i></A>';
	}
	if($ac_import[13] == 'on'){
		$btn .= '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)deleteCCD(\'person\',\''.$da['pid'].'\',\'\');" title="Удалить" class="gray red mr5"><i class="icon-cancel-circled red"></i></A>';
	}
	if( $accesse ){
		$btn .= '<a href="javascript:void(0)" onclick="openPerson(\''.$da['pid'].'\')" title="Карточка"><i class="icon-user-1 green"></i></a>';
	}

	?>

	<div class="mb10 fcontainer p0 focused" data-pid="<?= $da['pid'] ?>">

		<?php
		if (!$isMobile)
			print '<DIV class="panel">'.$btn.'</DIV>';
		?>

		<div class="cardBlock" style="overflow: hidden" data-height="260">

			<div class="fieldblocks p10 block pb15">

				<div class="mb20 Bold blue p10 flex-container float hand" onclick="openPerson('<?= $da['pid'] ?>')" title="Открыть карточку">

					<div class="flex-string w40 fs-12">
						<i class="icon-user-1 <?=($pidM == $da['pid'] ? 'red' : 'blue')?>"></i>
					</div>
					<div class="flex-string float">
						<div class="fs-12 Bold"><?= $da['person'] ?></div>
						<div class="fs-09 gray2 mt5 noBold"><?= $da['ptitle'] ?></div>
					</div>

				</div>

				<?php
				$extentionField = $db -> getOne("SELECT fld_name FROM ".$sqlname."field WHERE fld_tip = 'person' AND fld_on = 'yes' AND fld_title IN ('Добавочный', 'Extention','Доб.номер') AND identity = '$identity'");

				$f = ($extentionField != '') ? ",'$extentionField'" : '';

				$res = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip = 'person' AND fld_on = 'yes' AND identity = '$identity' AND fld_name IN ('tel'$f, 'mob', 'mail','clientpath','loyalty','rol') ORDER BY FIELD(fld_name, 'tel'$f, 'mob', 'mail','clientpath','loyalty'), fld_order");
				while ($data = $db -> fetch($res)) {

					if ($data['fld_name'] == 'tel' && $da['tel'] != "") {

						$phone_list = [];
						$phones      = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['tel'])));
						foreach($phones as $phone) {

							$phone_list[] = '<div class="phonec phonenumber '.(is_mobile($phone) ? 'ismob' : '').'" data-pid="'.$da['pid'].'" data-clid="'.$clid.'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone, $da['clid'], $da['pid']).'</div>';

						}
						$phone = implode("", $phone_list);
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80 Bold xpmt">
								<?= $phone ?>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_name'] == 'mob' && $da['mob'] != "") {

						$phone_list = [];
						$mobi       = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['mob'])));
						foreach($mobi as $phone) {

							$phone_list[] = '<div class="phonec phonenumber Bold '.(is_mobile($phone) ? 'ismob' : '').'" data-pid="'.$da['pid'].'" data-clid="'.$clid.'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone, $da['clid'], $da['pid']).'</div>';

						}
						$mob = implode("", $phone_list);
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80 xpmt">
								<?= $mob ?>
							</div>

						</div>
						<?php

					}
					elseif ($data['fld_name'] == 'fax' && $da['fax'] != "") {

						$phone_list = [];
						$fax        = yexplode(",", str_replace(";", ",", str_replace(" ", "", $da['fax'])));
						foreach($fax as $phone) {

							$phone_list[] = '<div class="phonec phonenumber '.(is_mobile($phone) ? 'ismob' : '').'" data-pid="'.$da['pid'].'" data-clid="'.$clid.'" data-phone="'.prepareMobPhone($phone).'">'.formatPhoneUrl($phone, $da['clid'], $da['pid']).'</div>';

						}
						$fax = implode("", $phone_list);
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80 xpmt">
								<?= $fax ?>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_name'] == 'mail' && $da['mail'] != "") {

						$emails = explode(",", str_replace(";", ",", $da['mail']));
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80">
								<?php
								foreach($emails as $email) {

									$apx = ($ymEnable == true) ? '&nbsp;(<A href="javascript:void(0)" onClick="$mailer.composeCard(\''.$clid.'\',\''.$pid.'\',\''.trim($email).'\');" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : "";

									print '<div class="inline mb5">'.link_it($email).$apx.'</div>';

									//print '<div class="inline mb5">'.link_it($email[ $j ]).($ymEnable ? '&nbsp;(<A href="javascript:void(0)" onClick="composeMailCard(\''.$clid.'\',\''.$da['pid'].'\',\''.trim($email[ $j ]).'\')" title="Написать сообщение"><i class="icon-mail blue"></i></A>)&nbsp;' : '').'</div>';

								}
								?>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_name'] == 'clientpath' && $da['clientpath'] > 0) {

						$clientpath = $db -> getOne("SELECT name FROM ".$sqlname."clientpath WHERE id = '".$da['clientpath']."' and identity = '$identity'");
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80">
								<?= $clientpath ?>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_name'] == 'rol' && $da['rol'] != "") {
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80">
								<b><?= yimplode("</b>, <b>", yexplode(";", $da['rol'])) ?></b>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_name'] == 'loyalty' && $da['loyalty'] > 0) {
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80">
								<div class="colordiv" style="background-color:<?= strtr($da['loyalty'], $loyalty['color']) ?>"></div>
								&nbsp;<?= strtr($da['loyalty'], $loyalty['title']) ?>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_name'] == 'social' && ($da['social'] != ";;;;;;;") && ($da['social'] != "")) {
						?>
						<div class="flex-container ptb5lr15">

							<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
							<div class="flex-string wp80">
								<?php
								$soc = explode(';', $da['social']);
								if ($soc[0] != '') print '<DIV class="tcn"><a href="http://'.str_replace("http://", "", $soc[0]).'" target="_blank" title="'.$soc[0].'"><b>Блог</b>&nbsp;<i class="icon-globe broun"></i></a></DIV>&nbsp;';
								if ($soc[1] != '') print '<DIV class="tcn"><a href="http://'.str_replace("http://", "", $soc[1]).'" target="_blank" title="'.$soc[1].'"><b>Сайт</b>&nbsp;<i class="icon-globe broun"></i></a></DIV>&nbsp;';
								if ($soc[2] != '') print '<DIV class="tcn"><a href="http://twitter.com/#!/'.str_replace("http://twitter.com/#!/", "", $soc[2]).'" target="_blank" title="'.$soc[2].'"><b>Twitter</b>&nbsp;<i class="icon-twitter blue"></i></a></DIV>&nbsp;';
								if ($soc[3] != '') print '<DIV class="tcn"><b>ICQ '.$soc[3].'</b>&nbsp;<i class="icon-cog-1 green"></i></DIV>&nbsp;';
								if ($soc[4] != '') print '<DIV class="tcn"><a href="skype:'.$soc[4].'" target="_blank" title="Позвонить: '.$soc[4].'">&nbsp;<i class="icon-skype green"></i>&nbsp;<b>'.$soc[4].'</b></a> или <a href="skype:'.$soc[4].'?chat" title="Начать чат: '.$soc[4].'">&nbsp;<i class="icon-skype green"></i>&nbsp;<b>'.$soc[4].'</b></a></DIV>&nbsp;';
								if ($soc[5] != '') print '<DIV class="tcn"><a href="http://'.str_replace("http://", "", $soc[5]).'" target="_blank" title="'.$soc[6].'"><b>Google+</b>&nbsp;<i class="icon-gplus-squared red"></i></a></DIV>&nbsp;';
								if ($soc[6] != '') print '<DIV class="tcn"><a href="http://'.str_replace("http://", "", $soc[6]).'" target="_blank" title="'.$soc[6].'"><b>Facebook</b>&nbsp;<i class="icon-facebook-squared blue"></i></a></DIV>&nbsp;';
								if ($soc[7] != '') print '<DIV class="tcn"><a href="http://'.str_replace("http://", "", $soc[7]).'" target="_blank" title="'.$soc[6].'"><b>VKontakte</b>&nbsp;<i class="icon-vkontakte blue"></i></a></DIV>&nbsp;';
								?>
							</div>

						</div>
						<?php
					}
					elseif ($data['fld_stat'] != 'yes') {

						if ($da[ $data['fld_name'] ] != '') {

							if ($data['fld_temp'] == "datum")
								$field = '<b class="green">'.format_date_rus_name($da[ $data['fld_name'] ]).'</b>';

							else if ($data['fld_temp'] == "adres")
								$field = '<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q='.$da[ $data['fld_name'] ].'" target="_blank">'.$da[ $data['fld_name'] ].'</a>';

							else
								$field = $da[ $data['fld_name'] ];

							?>
							<div class="flex-container ptb5lr15">

								<div class="flex-string wp20 gray2"><?= $data['fld_title'] ?></div>
								<div class="flex-string wp80 Bold">
									<?= nl2br($field) ?>
								</div>

							</div>
							<?php

						}

					}

				}
				?>

				<?= ($isMobile ? '<DIV class="wp100 mob-pull-right mb10">'.$btn.'</DIV>' : '') ?>

				<div class="Bold text-right hidden-iphone hidden">
					<a href="javascript:void(0)" onclick="openPerson('<?= $da['pid'] ?>')" class="button">
						Карточка контакта <i class="icon-angle-double-right"></i>
					</a>
				</div>

			</div>

		</div>

		<div class="div-center blue hand cardResizer fs-07 mt10 pb10 hidden" title="Развернуть" data-pozi="close">
			<i class="icon-angle-down"></i>
			<i class="icon-angle-down"></i>
			<i class="icon-angle-down"></i>
		</div>

	</div>
	<?php

}

if ( empty($result) ) {
	print '<div class="fcontainer mp10">Нет контактов</div>';
}
?>

<script>

	if (!isMobile) {

		$('#tab-2').find('.cardBlock').each(function () {

			var el = $(this).find('.fieldblocks');
			var hf = el.actual('outerHeight');
			var initHeight = $(this).data('height');

			if (hf >= 260) {

				//$(this).css({"height": initHeight + "px"});
				el.prop('data-height', hf+"px");

			}
			else $(this).closest('.fcontainer').find('.cardResizer').remove();

		});

	}
	else $('.cardResizer').remove();

</script>
