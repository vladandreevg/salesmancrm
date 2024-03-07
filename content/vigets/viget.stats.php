<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.x          */
/* ============================ */

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

$sort = get_people( $iduser1 );

$d01 = mktime( 0, 0, 0, date( 'm' ), 1, date( 'Y' ) );
$dd  = 14;//intval(date("t",$d01));
$d1  = strftime( '%Y-%m-%d', $d01 ); //начало месяца
$d2  = strftime( '%Y-%m-%d', mktime( 23, 59, 59, date( 'm' ), $dd, date( 'Y' ) ) );//конец месяца
$d3  = strftime( '%Y-%m-%d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ), date( 'Y' ) ) );//текущая дата

$clAdd = $coAdd = $dAdd = $dday = [];

$i = 0;
for ( $day = -$dd; $day <= 0; $day++ ) {

	$cl = $clAdd[] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."clientcat WHERE clid > 0 $sort and (date_create >= '".current_datum( -$day )."' AND date_create < '".current_datum( -$day )."' + INTERVAL 1 DAY ) and identity = '$identity'" );

	$coAdd[] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."personcat WHERE pid > 0 $sort and (date_create >= '".current_datum( -$day )."' AND date_create < '".current_datum( -$day )."' + INTERVAL 1 DAY ) and identity = '$identity'" );

	$dAdd[] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE did > 0 $sort and (datum >= '".current_datum( -$day )."' AND datum < '".current_datum( -$day )."' + INTERVAL 1 DAY ) and identity = '$identity'" );

	$dday[] = $i.": '".format_date_rus( current_datum( -$day ) )."'";

	$i++;

}

$clAdd = yimplode( ",", $clAdd );
$coAdd = yimplode( ",", $coAdd );
$dAdd  = yimplode( ",", $dAdd );
$dday  = yimplode( ",", $dday );

//Посчитаем количество заказчиков
$clients_all = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."clientcat WHERE clid > 0 $sort and identity = '$identity'" );

//Количество новых за месяц
$clients_new = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."clientcat WHERE clid > 0 and (date_create >= '".current_datum()."' AND date_create < '".current_datum()."' + INTERVAL 1 DAY ) $sort and identity = '$identity'" );

//Посчитаем количество персон
$person_all = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."personcat WHERE pid > 0 $sort and identity = '$identity'" );

//Посчитаем количество новых персон
$person_lo = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."personcat WHERE pid > 0 and (date_create >= '".current_datum()."' AND date_create < '".current_datum()."' + INTERVAL 1 DAY ) $sort and identity = '$identity'" );

//Количество новых сделок за месяц
$dog_m = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE did > 0 and (datum >= '".current_datum()."' AND datum < '".current_datum()."' + INTERVAL 1 DAY ) $sort and identity = '$identity'" );

//Количество закрытых сделок за месяц
$dog_all = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE did > 0 and COALESCE(close, 'no') != 'yes' $sort and identity = '$identity'" );

?>

<div class="mt20 pt20 box--child">

	<div class="flex-container last fs-12 mb20">

		<div class="flex-string wp30 flh-14 Bold right-text gray2 pr10 hidden-iphone">

			<a href="javascript:void(0)" onclick="getSwindow('reports/ent-newClients.php', 'Новые клиенты')" title="Статистика" class="gray"><i class="icon-chart-line blue"></i></a>&nbsp;<?= $lang['face']['ClientsName'][1] ?>

		</div>

		<div class="flex-string wp40 bar">

			<div class="bar-clients block wp90"><?= $clAdd ?></div>

		</div>

		<div class="flex-string wp30 flh-14">

			<span title="Сегодня" class="Bold">+ <?= number_format( $clients_new, 0, ',', '`' ); ?>&nbsp;</span>
			<sup title="Всего <?= $lang['face']['ClientsName'][1] ?>" class="gray fs-05">&nbsp;<?= number_format( $clients_all, 0, ',', '`' ); ?></sup>

		</div>

	</div>

	<div class="flex-container last fs-12 mb20">

		<div class="flex-string wp30 flh-14 Bold right-text gray2 pr10 hidden-iphone">

			<?= $lang['face']['ContactsName'][1] ?>

		</div>

		<div class="flex-string wp40 bar">

			<div class="bar-contacts inline wp90"><?= $coAdd ?></div>

		</div>

		<div class="flex-string wp30 flh-14">

			<span title="Сегодня" class="Bold">+ <?= number_format( $person_lo, 0, ',', '`' ); ?>&nbsp;</span>
			<sup title="Всего <?= $lang['face']['ContactsName'][1] ?>" class="gray fs-05">&nbsp;<?= number_format( $person_all, 0, ',', '`' ); ?></sup>

		</div>

	</div>

	<div class="flex-container last fs-12 mb20">

		<div class="flex-string wp30 flh-14 Bold right-text gray2 pr10 hidden-iphone">

			<a href="javascript:void(0)" onclick="getSwindow('reports/ent-newDeals.php', 'Новые сделки')" title="Статистика" class="gray"><i class="icon-chart-line blue"></i></a>&nbsp;<?= $lang['face']['DealControl'][1] ?>

		</div>

		<div class="flex-string wp40 bar">

			<div class="bar-deals inline wp90"><?= $dAdd ?></div>

		</div>

		<div class="flex-string wp30 flh-14">

			<span title="Сегодня" class="Bold">+ <?= number_format( $dog_m, 0, ',', '`' ); ?>&nbsp;</span>
			<sup title="Активных <?= $lang['face']['DealControl'][1] ?>" class="gray fs-05">&nbsp;<?= number_format( $dog_all, 0, ',', '`' ); ?></sup>

		</div>

	</div>

</div>

<div class="div-center pt10 hidden">

	<a href="javascript:void(0)" onClick="doLoad('reports/summary_report2.php'); this.blur()" class="button"><i class="icon-chart-line"></i>&nbsp;Сводный отчет</a>

</div>

<div class="pull-aright gray2 em fs-09 pt10"><b class="red">*</b>&nbsp;Учитываются подчиненные, если они есть.</div>

<script>
	includeJS("/assets/js/jquery.sparkline.min.js");

	$(function () {

		$(".bar-clients").sparkline('html', {
			type: 'line',
			lineColor: '#2980B9',
			width: '95%',
			tooltipFormat: 'Добавлено: {{offset:levels}} - {{y}}',
			tooltipValueLookups: {
				levels: {<?=$dday?>}
			}
		});
		$(".bar-contacts").sparkline('html', {
			type: 'line',
			lineColor: '#16A085',
			width: '95%',
			tooltipFormat: 'Добавлено: {{offset:levels}} - {{y}}',
			tooltipValueLookups: {
				levels: {<?=$dday?>}
			}
		});
		$(".bar-deals").sparkline('html', {
			type: 'line',
			lineColor: '#E67E22',
			width: '95%',
			tooltipFormat: 'Добавлено: {{offset:levels}} - {{y}}',
			tooltipValueLookups: {
				levels: {<?=$dday?>}
			}
		});

		//$('.bar').find('canvas').css({"width":"95%"});

	});

</script>
