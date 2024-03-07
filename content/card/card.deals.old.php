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
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );


$action = untag3($_REQUEST['action']);

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];
$docSort = untag($_COOKIE['dealsSort']);

if ($clid > 0) {
	$s      = "clid = '$clid' OR payer = '$clid'";
	$client = current_client($clid);
}
if ($pid > 0) {
	$s      = "pid = '$pid'";
	$client = current_person($pid);
}

$closedDeals = $db -> getRow("SELECT COUNT(did) as count, SUM(kol_fact) as summa, SUM(marga) as marga FROM {$sqlname}dogovor WHERE clid = '$clid' AND close = 'yes' and kol_fact > 0 AND identity = '$identity'");

$activeDeals = $db -> getRow("SELECT COUNT(did) as count, SUM(kol) as summa, SUM(marga) as marga FROM {$sqlname}dogovor WHERE clid = '$clid' AND close != 'yes' AND identity = '$identity'");

$ssort = ($docSort == 'DESC') ? '' : 'DESC';
$icon  = ($docSort == 'DESC') ? 'icon-sort-alt-down' : 'icon-sort-alt-up';

print '
	<div class="inline pull-left Bold" style="position:absolute; top:10px">
	
		<a href="javascript:void(0)" onclick="setCookie(\'dealsSort\', \''.$ssort.'\', {expires:31536000}); settab(\'4\')" class="gray" title="Изменить сортировку"><i class="'.$icon.' broun"></i> Сортировка</a>
	
	</div>
	<div class="viewdiv mb10 mt10">
		<div class="mb5">
			Реализованные '.texttosmall($lang['face']['DealsName'][0]).': <b>'.$closedDeals['count'].'</b> шт., 
			Сумма: <b>'.num_format($closedDeals['summa']).' '.$valuta.'</b>'.
			($show_marga == 'yes' && $other[9] == 'yes' ? ', Прибыль: <b>'.num_format($closedDeals['marga']).' '.$valuta.'</b>' : '').'
		</div>
		<div>
			'.$lang['face']['DealsName'][0].' в работе: <b>'.$activeDeals['count'].'</b> шт., 
			Сумма: <b>'.num_format($activeDeals['summa']).' '.$valuta.'</b>'.
			($show_marga == 'yes' && $other[9] == 'yes' ? ', Прибыль: <b>'.num_format($activeDeals['marga']).' '.$valuta.'</b>' : '').'
		</div>
	</div>
';

// сложная сортировка, т.к. в базу в разное время записывались значения no, '', NULL, поэтому приводим к одному виду
$res = $db -> getAll("
	SELECT * 
	FROM {$sqlname}dogovor 
	WHERE 
		$s and 
		identity = '$identity' 
	ORDER by FIELD( IF( IFNULL(close, 'no') = '', 'no', IFNULL(close, 'no')), NULL, 'no', '', 'yes'), datum_plan 
	$ssort
");
$all = count($res);

foreach ($res as $da) {

	if ((int)$da['payer'] > 0) {
		$payer = '<a href="javascript:void(0)" onclick="openClient(\''.$da['payer'].'\')" target="blank" title="Плательщик">'.current_client($da['payer']).'</a>';
	}

	$dogstatus  = current_dogstepname((int)$da['idcategory']);
	$dogcontent = current_dogstepcontent((int)$da['idcategory']);

	$tip = current_dogtype((int)$da['tip']);

	$contract = $db -> getRow("select number, datum_start from {$sqlname}contract where deid='".$da['dog_num']."' and identity = '$identity'");

	$mycomp = $db -> getOne("select name_shot from {$sqlname}mycomps where id='".$da['mcid']."' and identity = '$identity'");

	$dati1 = format_date_rus_name($da['datum']);

	$direction = $db -> getOne("SELECT title FROM {$sqlname}direction WHERE id='".$da['direction']."' and identity = '$identity'");

	$extlink = '';
	if ($outDealUrl != '' && $da['uid'] != '') {

		$outDealUrl = str_replace( [
			"{uid}",
			"{login}"
		], [
			$da['uid'],
			current_userlogin( $iduser1 )
		], $outDealUrl );

		$extlink = '&nbsp;&nbsp;<a href="'.$outDealUrl.'" target="_blank" title="Переход в ИС"><i class="icon-forward green"></i></a>';

	}

	$btn = (get_accesse(0, 0, (int)$da['did']) == "yes" || $da['iduser'] == $iduser1) ? '
	<a href="javascript:void(0)" onclick="viewDogovor(\''.$da['did'].'\')" title="Просмотр '.$lang['face']['DealName'][1].'" class="gray green"><i class="icon-eye green"></i></a>&nbsp;&nbsp;
	<a href="javascript:void(0)" onClick="addTask(\'add\',\''.$clid.'\',\'\',\''.$da['did'].'\');" title="Добавить напоминание" class="gray blue"><i class="icon-clock blue"></i></a>&nbsp;&nbsp;
	<a href="javascript:void(0)" onclick="openDogovor(\''.$da['did'].'\')" title="Карточка '.$lang['face']['DealName'][1].'" class="gray broun"><i class="icon-briefcase broun"></i></a>'.$extlink : '';

	if ($da['close'] != 'yes') {

		?>
		<div class="fcontainer focused" data-did="<?=$da['did']?>">

			<?php
			if (!$isMobile && (get_accesse(0, 0, (int)$da['did']) == "yes" || $da['iduser'] == $iduser1)) {
				print '<div class="panel">'.$btn.'</div>';
			}
			?>

			<div class="fs-12 mb20 Bold blue">

				<div>
					<A href="javascript:void(0);" onclick="openDogovor('<?= $da['did'] ?>')"><i class="icon-briefcase-1 blue"></i><?= $da['title'] ?></A>
					<?php if ($da['uid'] != '') { ?>,&nbsp;
					<span class="Bold gray">UID <?= $da['uid'] ?></span>&nbsp;
					<?php } ?>
				</div>
				<div class="fs-09 gray2 mt15 noBold">
					<?php if ($dogstatus != '') { ?>Этап:&nbsp;<b class="blue list" title="<?= $dogcontent ?>"><?= $dogstatus ?>%</b>, <?php } ?>Открыта:
					<b><?= $dati1 ?></b>, Плановая дата:
					<b class="red"><?= format_date_rus_name($da['datum_plan']) ?></b>
				</div>

			</div>

			<div class="cardBlock" style="overflow: hidden" data-height="90">

				<div class="fieldblocks block">

					<div class="divider mb10">Детали</div>

					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray"><?= $fieldsNames['dogovor']['kol'] ?></div>
						</div>
						<div class="flex-string wp80">
							<div class="Bold blue"><?= num_format($da['kol']) ?>&nbsp;<?= $valuta ?></div>
						</div>

					</div>
					<?php if ((float)$da['marga'] > 0 && $show_marga == 'yes' && $other[9] == 'yes') { ?>
						<div class="flex-container p5">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['marg'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold blue"><?= num_format($da['marga']) ?>&nbsp;<?= $valuta ?></div>
							</div>

						</div>
					<?php } ?>
					<?php if ((int)$da['payer'] != (int)$da['clid']) { ?>
					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray">Плательщик</div>
						</div>
						<div class="flex-string wp80">
							<div class="blue"><?= $payer ?></div>
						</div>

					</div>
					<?php } ?>
					<?php if ($da['adres'] != '' && in_array( 'adres', $fieldsOn['dogovor'], true ) ) { ?>
					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray"><?= $fieldsNames['dogovor']['adres'] ?></div>
						</div>
						<div class="flex-string wp80">
							<div class="blue"><i class="icon-location blue"></i>&nbsp;<a href="https://maps.google.ru/maps?hl=ru&tab=wl&q=<?= $da['adres'] ?>" target="_blank"><?= $da['adres'] ?></a></div>
						</div>

					</div>
					<?php } ?>
					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray"><?= $fieldsNames['dogovor']['iduser'] ?></div>
						</div>
						<div class="flex-string wp80">
							<div class="">
								<B class="green"><?= current_user($da['iduser']) ?></B>
								<?php if ((int)$da['autor'] > 0) { ?>&nbsp;(
									<b>Создана:</b>&nbsp;<B class="green"><?= current_user($da['autor']) ?>&nbsp;</B>)
								<?php } ?>
							</div>
						</div>

					</div>
					<?php if ((int)$da['provider'] > 0) { ?>
					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray">Поставщики</div>
						</div>
						<div class="flex-string wp80">
							<div class="">
								<?= num_format($da['provider']) ?>&nbsp;<?= $valuta ?>&nbsp;
							</div>
						</div>

					</div>
					<?php } ?>
					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray">Компания</div>
						</div>
						<div class="flex-string wp80">
							<div class="">
								<?= $mycomp ?>
							</div>
						</div>

					</div>
					<?php if (!empty($contract['number'])) { ?>
					<div class="flex-container p5">

						<div class="flex-string wp20">
							<div class="gray">Номер договора</div>
						</div>
						<div class="flex-string wp80">
							<div class="">
								<?= $contract['number'] ?> от <?= format_date_rus_name($contract['datum_start']) ?>
							</div>
						</div>

					</div>
					<?php } ?>
					<?php if (!empty($da['content']) && in_array( 'content', $fieldsOn['dogovor'] ) ) { ?>
						<div class="flex-container p5">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['content'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="height--250">
									<?= nl2br($da['content']) ?>
								</div>
							</div>

						</div>
					<?php } ?>
					<?php if ($da['datum_start'] != "0000-00-00" && in_array( 'period', $fieldsOn['dogovor'] ) ) { ?>
						<div class="flex-container p5">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['period'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="">
									<?= format_date_rus_name($da['datum_start'])." - ".format_date_rus_name($da['datum_end']) ?>
								</div>
							</div>

						</div>
					<?php } ?>
					<?php if ($tip != '' && in_array( 'tip', $fieldsOn['dogovor'] ) ) { ?>
						<div class="flex-container p5">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['tip'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="">
									<?= $tip ?>
								</div>
							</div>

						</div>
					<?php } ?>
					<?php if ($direction != '' && in_array( 'direction', $fieldsOn['dogovor'] ) ) { ?>
						<div class="flex-container p5">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['direction'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="">
									<?= $direction ?>
								</div>
							</div>

						</div>
					<?php } ?>

					<?php
					$res = $db -> query("select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");
					while ($fld = $db -> fetch($res)) {

						$field = '';

						if ($da[ $fld['fld_name'] ] != '') {

							if ($fld['fld_temp'] == "datum") {
								$field = '<b class="green">'.format_date_rus_name($da[$fld['fld_name']]).'</b>';
							}
							elseif ($fld['fld_temp'] == "adres") {
								$field = '<i class="icon-location blue"></i>&nbsp;<a href="https://maps.google.ru/maps?hl=ru&tab=wl&q='.$da[$fld['fld_name']].'" target="_blank">'.$da[$fld['fld_name']].'</a>';
							}
							else {
								$field = $da[$fld['fld_name']];
							}

							print '
							<div class="flex-container p5">
	
								<div class="flex-string wp20">
									<div class="gray">'.$fld['fld_title'].'</div>
								</div>
								<div class="flex-string wp80">
									<div class="">'.nl2br($field).'</div>
								</div>
	
							</div>';

						}

					}
					?>

				</div>

				<?= ($isMobile ? '<DIV class="wp100 mob-pull-right mb10">'.$btn.'</DIV>' : '') ?>

			</div>

			<div class="div-center blue hand cardResizer fs-09 mt10" title="Развернуть" data-pozi="close">
				<i class="icon-angle-down"></i>
				<i class="icon-angle-down"></i>
				<i class="icon-angle-down"></i>
			</div>

		</div>
		<?php
	}
	if ($da['close'] == 'yes') {

		$status = $db -> getRow("SELECT title, content FROM {$sqlname}dogstatus WHERE sid='".$da['sid']."' and identity = '$identity'");

		$btn = '
			<a href="javascript:void(0)" onclick="viewDogovor(\''.$da['did'].'\')" title="Просмотр '.$lang['face']['DealName'][1].'" class="gray blue"><i class="icon-eye blue"></i></a>&nbsp;&nbsp;
			<A href="javascript:void(0)" onclick="cloneDogovor(\''.$da['did'].'\');" title="Клонировать: Создать новую на основе текущей" class="gray green"><i class="icon-paste green"></i></A>
		';

		?>
		<div class="fcontainer focused" data-did="<?=$da['did']?>">

			<?php
			if (!$isMobile && (get_accesse(0, 0, (int)$da['did']) == "yes" || $da['iduser'] == $iduser1)) print '<div class="panel">'.$btn.'</div>';
			?>

			<div class="fs-12 mb20 Bold gray2">

				<div class="fs-09 pt10 pb10 mb20 red">
					<i class="icon-lock red"></i>Закрыта <?= get_date($da['datum_close']) ?></div>
				<div>
					<A href="javascript:void(0);" onclick="openDogovor('<?= $da['did'] ?>')" class="gray2"><?= $da['title'] ?>
						<?php if ($da['uid'] != '') {?>
							,&nbsp;<span class="Bold blue">UID <?= $da['uid'] ?></span>&nbsp;
						<?php } ?>
					</A>
				</div>
				<div class="fs-09 gray2 mt15 noBold">
					<?php if ($dogstatus != '') { ?>Этап:&nbsp;<b class="blue list" title="<?= $dogcontent ?>"><?= $dogstatus ?>%</b>, <?php } ?>
					Открыта:<b><?= $dati1 ?></b>,
					Плановая дата:<b class="red"><?= format_date_rus_name($da['datum_plan']) ?></b>
				</div>

			</div>

			<div class="cardBlock" style="overflow: hidden" data-height="50">

				<div class="fieldblocks block">

					<div class="pt5 pb5">

						<b>Оборот:</b> <b class="blue fs-12"><?= num_format($da['kol_fact']).' '.$valuta ?></b>;
						<?php if ($show_marga == 'yes' && $other[9] == 'yes') { ?> <b>Прибыль:</b>
							<b class="blue fs-12"><?= num_format($da['marga']).' &nbsp;'.$valuta ?></b>
						<?php } ?>

					</div>

					<div class="pt5 pb5">
						Результат: <B class="red"><?= $status['title'] ?></B> - <?= $status['content'] ?>&nbsp;
					</div>

					<?php if ($da['des_fact'] != '') { ?>
					<div class="viewdiv"><b>Комментарий:</b> <?= $da['des_fact'] ?></div>
					<?php } ?>

					<div class="fcontainer1 mt15">

						<div class="divider mb10">Детали</div>

						<?php if ($da['payer'] != $da['clid']) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray">Плательщик</div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold blue"><?= $payer ?></div>
							</div>

						</div>
						<?php } ?>
						<?php if ($da['adres'] != '' && in_array( 'adres', $fieldsOn['dogovor'], true ) ) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['adres'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold blue"><i class="icon-location blue"></i>&nbsp;<a href="https://maps.google.ru/maps?hl=ru&tab=wl&q=<?= $da['adres'] ?>" target="_blank"><?= $da['adres'] ?></a></div>
							</div>

						</div>
						<?php } ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['iduser'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="">
									<B class="green"><?= current_user($da['iduser']) ?></B>
									<?php if ($da['autor']) { ?>&nbsp;(
									<b>Создана:</b>&nbsp;<B class="green"><?= current_user($da['autor']) ?>&nbsp;</B>)
									<?php } ?>
								</div>
							</div>

						</div>
						<?php if ($da['provider'] > 0) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray">Поставщики</div>
							</div>
							<div class="flex-string wp80">
								<div class="">
									<b class="blue"><?= num_format($da['provider']) ?></b>&nbsp;<?= $valuta ?>&nbsp;
								</div>
							</div>

						</div>
						<?php } ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray">Компания</div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold">
									<?= $mycomp ?>
								</div>
							</div>

						</div>
						<?php if ($contract['number']) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray">Номер договора</div>
							</div>
							<div class="flex-string wp80">
								<div class="">
									<b><?= $contract['number'] ?></b> от <?= format_date_rus_name($contract['datum_start']) ?>
								</div>
							</div>

						</div>
						<?php } ?>
						<?php if ($da['datum_start'] != "0000-00-00" && in_array( 'period', $fieldsOn['dogovor'], true ) ) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['period'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold">
									<?= format_date_rus_name($da['datum_start'])." - ".format_date_rus_name($da['datum_end']) ?>
								</div>
							</div>

						</div>
						<?php } ?>
						<?php if ($tip != '' && in_array( 'tip', $fieldsOn['dogovor'], true ) ) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['tip'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold">
									<?= $tip ?>
								</div>
							</div>

						</div>
						<?php } ?>
						<?php if ($direction != '' && in_array( 'direction', $fieldsOn['dogovor'], true ) ) { ?>
						<div class="flex-container p10">

							<div class="flex-string wp20">
								<div class="gray"><?= $fieldsNames['dogovor']['direction'] ?></div>
							</div>
							<div class="flex-string wp80">
								<div class="Bold">
									<?= $direction ?>
								</div>
							</div>

						</div>
						<?php } ?>

						<?php
						$res = $db -> query("select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");
						while ($fld = $db -> fetch($res)) {

							$field = '';

							if ($da[ $fld['fld_name'] ] != '') {

								if ($fld['fld_temp'] == "datum")
									$field = '<b class="green">'.format_date_rus_name($da[ $fld['fld_name'] ]).'</b>';

								elseif ($fld['fld_temp'] == "adres")
									$field = '<i class="icon-location blue"></i>&nbsp;<a href="http://maps.google.ru/maps?hl=ru&tab=wl&q='.$da[ $fld['fld_name'] ].'" target="_blank">'.$da[ $fld['fld_name'] ].'</a>';

								else
									$field = $da[ $fld['fld_name'] ];

								print '
								<div class="flex-container p10">
		
									<div class="flex-string wp20">
										<div class="gray">'.$fld['fld_title'].'</div>
									</div>
									<div class="flex-string wp80">
										<div class="Bold">'.nl2br($field).'</div>
									</div>
		
								</div>';

							}

						}
						?>

					</div>

				</div>

				<?= ($isMobile ? '<DIV class="wp100 mob-pull-right mb10">'.$btn.'</DIV>' : '') ?>

			</div>

			<div class="div-center blue hand cardResizer fs-07 mt10" title="Развернуть" data-pozi="close">
				<i class="icon-angle-down"></i>
				<i class="icon-angle-down"></i>
				<i class="icon-angle-down"></i>
			</div>

		</div>
		<?php

	}

}

if ($all == 0) {
	print '<div class="fcontainer mp10">'.$lang[ 'face' ][ 'DealsName' ][ 1 ].' пока нет</div>';
}
?>


<script>

	if (!isMobile) {

		$('#tab-4').find('.cardBlock').each(function () {

			var el = $(this).find('.fieldblocks');
			var hf = el.actual('outerHeight');
			var initHeight = $(this).data('height');

			if (hf > 100) {
				$(this).css({"height": initHeight + "px"});
				el.prop('data-height', initHeight + "px");
			}

		});

	}
	else $('.cardResizer').remove();

</script>
