<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

use Salesman\Storage;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

//include "mcfunc.php";

$did    = $_REQUEST['did'];
$action = $_REQUEST['action'];

//настройки модуля
$settings            = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
$settings            = json_decode( $settings, true );
$settings['mcSklad'] = 'yes';

if ( $action == "getReserv" ) {

	$counts = Storage ::dealcomplete( $did );

	//print_r($counts);

	//$Compl    = mcCompleteStatus($did);
	$Complete = ($counts['speka'] > 0) ? round( ($counts['speka'] - $counts['count']) / $counts['speka'] * 100, 2 ) : 0;

	print '
	<DIV class="batton-edit pull-aright">
		<a href="javascript:void(0)" onclick="getCatalog()" title="Обновить"><i class="icon-arrows-ccw blue"></i> Обновить</a>
	</DIV>
	';

	print '<div class="pad10 fs-12 blue">Комплектность сделки: <a href="javascript:void(0)" onclick="doLoad(\'/modules/modcatalog/form.modcatalog.php?action=editzayavkacomplete&did='.$did.'\')" class="red" title="Подробнее"><b>'.$Complete.'%</b></a></div>';

	?>
	<fieldset>

		<legend><b>Позиции в резерве</b></legend>

		<br>

		<TABLE class="bgwhite">
			<thead>
			<TR>
				<TH width="60" align="center"><B>Дата</B></TH>
				<TH width="" align="center"><B>Позиция</B></TH>
				<TH width="40" align="center"><B>Заявка</B></TH>
				<TH width="40" align="center"><B>Ордер</B></TH>
				<TH width="60" align="center"><B>Кол-во</B></TH>
				<TH width="40" align="center"></TH>
			</TR>
			</thead>
			<tbody>
			<?php
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_reserv where id > 0 and did = '$did' and identity = '$identity' ORDER BY id DESC" );
			foreach ( $result as $data ) {

				$title = $db -> getOne( "select title from ".$sqlname."price where n_id='".$data['prid']."' and identity = '$identity'" );

				$zayavkaNumber = $db -> getOne( "select number from ".$sqlname."modcatalog_zayavka where id='".$data['idz']."' and identity = '$identity'" );

				$orderNumber = $db -> getOne( "select number from ".$sqlname."modcatalog_akt where id='".$data['ida']."' and identity = '$identity'" );

				$sklad = $db -> getOne( "select title from ".$sqlname."modcatalog_sklad where id='".$data['sklad']."' and identity = '$identity'" );
				?>
				<TR height="45" class="ha">
					<TD align="left"><?= get_sfdate2( $data['datum'] ) ?></TD>
					<TD>
						<span class="ellipsis"><b><A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=view&n_id=<?= $data['prid'] ?>');"><i class="icon-archive broun"></i><?= $title ?></a></b></span>
						<br><span class="ellipsis fs-09 gray2">Склад: <b><?= $sklad ?></b></span>
					</TD>
					<TD align="center"><?= $zayavkaNumber ?></TD>
					<TD align="center"><?= $orderNumber ?></TD>
					<TD align="right"><?= $data['kol'] ?></TD>
					<TD align="center" nowrap>
						<?php if ( in_array( $iduser1, $settings['mcCoordinator'] ) ) { ?>
							<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить из Резерва?');if (cf)removeReserve('<?= $data['id'] ?>');" title="Удалить"><i class="icon-cancel-circled red"></i></A>&nbsp;&nbsp;
						<?php } ?>
					</TD>
				</TR>
				<?php
			}
			if ( count( $result ) == 0 ) {
				print '<TR height="45" class="ha"><td colspan="6">Нет в резерве</td></TR>';
			}
			?>
			</tbody>
		</TABLE>

	</fieldset>
	<?php

	exit();
}
if ( $action == "getZayavka" ) {

	$status  = [
		'0' => 'Создана',
		'1' => 'В работе',
		'2' => 'Выполнена',
		'3' => 'Отменена'
	];
	$colors  = [
		'0' => 'broun',
		'1' => 'blue',
		'2' => 'green',
		'3' => 'Отменена'
	];
	$bgcolor = [
		'bgwhite',
		'bluebg-sub',
		'greenbg-sub',
		'orangebg-sub',
		'redbg-sub'
	];

	$counts = Storage ::dealcomplete( $did );

	?>
	<fieldset>

		<legend><b>Заявки</b></legend>

		<?php if ( $counts['count'] > 0 ) { ?>

			<DIV class="batton-edit">
				<a href="javascript:void(0)" onclick="doLoad('modules/modcatalog/form.modcatalog.php?action=editzayavka&did=<?= $did ?>')" title="Создать"><i class="icon-plus-circled blue"></i> Добавить</a>
			</DIV>

			<br>

		<?php } ?>

		<TABLE class="bgwhite">
			<thead>
			<TR>
				<TH width="120" align="center">Номер</TH>
				<TH width="100" align="center"><B>Срок</B></TH>
				<TH align="center"><b>Автор</B> / <B>Ответств.</b></TH>
				<TH width="60" align="center"></TH>
				<TH width="40" align="center"></TH>
				<TH width="60" align="center">&nbsp;</TH>
			</TR>
			</thead>
			<tbody>
			<?php
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavka where id > 0 and did = '$did' and identity = '$identity' ORDER BY FIELD(`status`, 0,1,2), datum DESC" );
			foreach ( $result as $data ) {

				$kol_zay = $db -> getOne( "select COUNT(*) as kol from ".$sqlname."modcatalog_zayavkapoz where idz = '".$data['id']."' and identity = '$identity'" );

				$zayavka = json_decode( $data['des'], true );

				if ( $zayavka['zTitle'] != '' )
					$tip = '<span class="red">Новая позиция</span>';
				else $tip = 'Каталог';

				$des = '';
				if ( $data['datum_start'] != '0000-00-00 00:00:00' )
					$des .= "Принята в работу - ".get_sfdate( $data['datum_start'] );
				if ( $data['datum_end'] != '0000-00-00 00:00:00' ) {
					$des .= ", Выполнена - ".get_sfdate( $data['datum_end'] );
					$de  = '<div><b class="red">'.format_date_rus( get_smdate( $data['datum_end'] ) ).'</b></div>';
				}
				else $de = '';

				$srok = "<div>".get_date( $data['datum_priority'] )."</div>";

				//$des = $data['content'];

				if ( $data['isHight'] == 'yes' and $data['datum_start'] == '0000-00-00 00:00:00' )
					$bg = '#FFFFE1';
				else $bg = strtr( $data['status'], $bgcolor );

				if ( $data['isHight'] == 'yes' and $data['status'] != 2 )
					$hi = '<i class="icon-attention red smalltxt" title="Срочно"></i>';
				else $hi = '';

				$countOrder = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE idz = '".$data['id']."' and idz > 0 and identity = '$identity') and identity = '$identity'" );

				$countOrder += 0;

				//print $da['number']." :: ".$countOrder."\n";

				//посчитаем количество позиций в заявке
				$countZayavka = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz = '".$data['id']."' and identity = '$identity'" );

				$persent = round( $countOrder / $countZayavka * 100, 1 );

				if ( $persent < 100 )
					$class = "red Bold";
				else $class = "green";
				?>
				<TR height="45" class="ha">
					<TD align="right"><b>№ <?= $data['number'] ?></b> от <?= get_sfdate2( $data['datum'] ) ?></TD>
					<TD align="right" class="tooltips" tooltip="<blue>Статус</blue><hr><?= $des ?>" tooltip-position="top">
						<b><?= $srok ?></b><?= $de ?></TD>
					<TD>
						<span class="ellipsis"><a href="javascript:void(0)" onclick="viewUser('<?= $data['iduser'] ?>');"><i class="icon-user-1 blue"></i><?= current_user( $data['iduser'] ) ?></a></span>
						<?php if ( $data['sotrudnik'] ) { ?><br>
							<span class="ellipsis"><a href="javascript:void(0)" onclick="viewUser('<?= $data['sotrudnik'] ?>');"><i class="icon-user-1 broun"></i><span class="broun"><?= current_user( $data['sotrudnik'] ) ?></span></a></span>
						<?php } else { ?>
							<br><span class="ellipsis gray"><i class="icon-user-1"></i>Не назначено</span>
						<?php } ?>
					</TD>
					<TD align="center" class="<?= $class ?> tooltips" tooltip="<green>Комплектность ордерами</green><hr><?= $persent ?>% [<?= $countOrder." / ".$countZayavka ?> позиций в ордерах]" tooltip-position="top"><?= $persent ?>%</TD>
					<TD align="center" nowrap>
						<?= $hi ?>
						<?php
						if ( in_array( $iduser1, $settings['mcCoordinator'] ) ) {
							if ( in_array( $data['status'], [
								0,
								1
							] ) ) {
								?>
								<a href="javascript:void(0)" onclick="doLoad('modules/modcatalog/form.modcatalog.php?id=<?= $data['id'] ?>&action=editzayavkastatus');" title="Изменить" class="gray"><i class="icon-ok smalltxt"></i></a>&nbsp;&nbsp;
								<?php
							}
							else print '<i class="icon-ok-circled green smalltxt"></i>&nbsp;&nbsp;';
						}
						?>
					</TD>
					<TD align="left" nowrap>
						<A href="javascript:void(0)" onclick="doLoad('modules/modcatalog/form.modcatalog.php?id=<?= $data['id'] ?>&action=viewzayavka');" title="Просмотр"><i class="icon-eye blue"></i></A>&nbsp;&nbsp;
						<?php if ( $data['status'] != '2' and $data['status'] != '1' and ($data['iduser'] == $iduser1 or in_array( $iduser1, $settings['mcCoordinator'] )) ) { ?>
							<A href="javascript:void(0)" onclick="doLoad('modules/modcatalog/form.modcatalog.php?id=<?= $data['id'] ?>&action=editzayavka');" class="gray" title="Изменить"><i class="icon-pencil"></i></A>&nbsp;&nbsp;
						<?php } ?>
					</TD>
				</TR>
				<?php
			}
			if ( count( $result ) == 0 ) {
				print '<TR height="45" class="ha"><td colspan="5">Нет в заявках</td></TR>';
			}
			?>
			</tbody>
		</TABLE>

	</fieldset>
	<script>
		/*tooltips*/
		$('.tooltips').append("<span></span>");
		$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
		$(".tooltips").mouseenter(function () {
			$(this).find('span').empty().append($(this).attr('tooltip'));
		});
		/*tooltips*/
	</script>
	<?php

	exit();
}
if ( $action == "getOrder" ) {

	//количество, в приходных ордерах по текущей заявке
	$kol_do = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE did = '".$did."' and identity = '$identity') and identity = '$identity'" );

	//количество в заявке
	$kol_zay = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz IN (SELECT id FROM ".$sqlname."modcatalog_zayavka WHERE did = '".$did."' and identity = '$identity') and identity = '$identity'" );

	$counts = Storage ::dealcomplete( $did );

	//print_r($counts);

	//вычисляем количество, которое еще не находится в расходниках
	$delta_z = $counts['zayavka'] - $counts['order'];
	$delta_o = $counts['speka'] - $counts['zayavka'] - $counts['order'];
	$delta_i = $counts['speka'] - $counts['zayavka'] - $counts['orderin'];

	$acsept = ($delta_i > 0) ? 'yes' : 'no';
	?>
	<fieldset>
		<legend><b>Ордера прихода/расхода</b></legend>

		<?php
		if ( in_array( $iduser1, $settings['mcCoordinator'] ) && $delta_o > 0 ) {
			?>
			<DIV class="batton-edit">

				<?php
				if ( $acsept == 'yes' ) {
					?>
					<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=income&did=<?= $did ?>')" title="Приходный ордер" class="green"><i class="icon-plus-circled blue"></i> Приходный ордер</a>
					<?php
				}
				?>
				<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?action=editakt&tip=outcome&odid=<?= $did ?>')" title="Расходный ордер" class="red"><i class="icon-plus-circled red"></i> Расходный ордер</a>
			</DIV>

			<br>
		<?php } ?>

		<TABLE class="bgwhite">
			<thead>
			<TR>
				<TH class="w120">Номер</TH>
				<TH class="w120 text-center"><B>Тип</B></TH>
				<TH class="text-center"><b>Принял</b>/<b>Сдал</b></TH>
				<TH class="w90 text-center">&nbsp;</TH>
			</TR>
			</thead>
			<tbody>
			<?php
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_akt where id > 0 and did = '$did' and identity = '$identity' ORDER BY datum DESC" );
			foreach ( $result as $data ) {

				if ( $data['tip'] == 'income' ) {

					$tip = '<i class="icon-down-big green"></i> Приходный';

				}
				else {

					$tip = '<i class="icon-up-big blue"></i> Расходный';

				}

				$man1 = $data['man2'];
				$man2 = $data['man1'];

				if ( $data['isdo'] == 'yes' )
					$status = '<span class="green">Проведен</span>';
				else $status = '<span class="red">Черновик</span>';

				if ( $data['number'] > 0 )
					$number = $data['number'];
				else $number = '-';

				?>
				<TR class="th45 ha">
					<TD><b>№<?= $number ?></b> от <?= get_sfdate2( $data['datum'] ) ?></TD>
					<TD class="text-right">
						<div class="Bold"><?= $tip ?></div>
						<div class="fs-09"><?= $status ?></div>
					</TD>
					<TD>
						<div title="<?= $man1 ?>">Принял: <b><?= $man1 ?></b></div>
						<div title="<?= $man2 ?>">Сдал: <b><?= $man2 ?></b></div>
					</TD>
					<TD nowrap>

						<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?id=<?= $data['id'] ?>&action=viewakt');" title="Просмотр"><i class="icon-eye blue"></i></A>&nbsp;&nbsp;
						<A href="/modules/modcatalog/printorder.php?id=<?= $data['id'] ?>&tip=order" title="Печать" target="blank"><i class="icon-print green"></i></A>&nbsp;&nbsp;

						<?php if ( $data['isdo'] != 'yes' ) { ?>
							<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?id=<?= $data['id'] ?>&action=editakt');" class="gray" title="Изменить"><i class="icon-pencil green"></i></A>&nbsp;&nbsp;
							<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить?');if (cf)deleteOrder('<?= $data['id'] ?>');" class="gray" title="Удалить"><i class="icon-cancel-circled red"></i></A>&nbsp;&nbsp;
						<?php } ?>

						<?php if ( $data['isdo'] == 'yes' ) { ?>

							<i class="icon-ok blue" title="Проведен"></i>&nbsp;&nbsp;

							<?php if ( $settings['mcSkladPoz'] == 'yes' ) { ?>
								<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?id=<?= $data['id'] ?>&action=editaktperpoz');" title="Установить серийники"><i class="icon-list broun"></i></A>&nbsp;&nbsp;
							<?php } ?>

							<?php
							if ( $settings['mcSkladPoz'] != 'yes' ){

								print '<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить проведенный ордер?\');if (cf)deleteOrder(\''.$data['id'].'\');" title="Удалить"><i class="icon-block-1 red"></i></A>&nbsp;&nbsp;';

							}
							?>

						<?php } ?>
					</TD>
				</TR>
				<?php
			}
			if ( count( $result ) == 0 ) {
				print '<TR class="th45 ha"><td colspan="4">Нет ордеров</td></TR>';
			}
			?>
			</tbody>
		</TABLE>

	</fieldset>

	<script>

		function deleteOrder(id) {

			var url = '/modules/modcatalog/core.modcatalog.php?id=' + id + '&action=removeorder';
			$('#message').css('display', 'block').append('<div id=loader><img src=/assets/images/loading.svg> Загрузка данных. Пожалуйста подождите...</div>');
			$.get(url, function (data) {

				getCatalog();

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
?>