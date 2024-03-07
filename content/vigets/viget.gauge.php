<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
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

$Interval = $_COOKIE['gaugeInterval'];
$Width    = $_COOKIE['width'];

if ( is_between( $Width, 1025, 1200 ) ) {
	$Width = 160;
}
elseif ( is_between( $Width, 1200, 1366 ) ) {
	$Width = 130;
}
elseif ( is_between( $Width, 900, 1200 ) ) {
	$Width = 140;
}
else {
	$Width = 160;
}

$setperiod = (strlen( $Interval ) > 1 && $Interval != 'undefined') ? $Interval : 'month';

$period = getPeriod( $setperiod );

//print_r($period);

$da1 = $period[0];
$da2 = $period[1];

$l1 = $l2 = $l3 = $total = 0;

$mdwset       = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
$leadsettings = json_decode( $mdwset['content'], true );
$coordinator  = (int)$leadsettings["leadСoordinator"];
$operators    = (array)$leadsettings["leadOperator"];

$total = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

//заявки для операторов
if ( in_array( $iduser1, $operators ) ) {

	$l1 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '1' and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

	$l2 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '2' and datum_do BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

	$l3 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '2' and did > 0 and datum_do BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

}

//заявки для координатора
if ( $iduser1 == $coordinator || in_array( $tipuser, [
		"Руководитель организации",
		"Поддержка продаж",
		"Руководитель с доступом"
	] ) ) {

	$l1 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

	$l2 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and status = '2' and datum_do BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

	$l3 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and status = '2' and did > 0 and datum_do BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

}

//print "<br>";
//print $total;

//обращения
if ( $isEntry == 'on' ) {

	$le1 = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}entry WHERE ide > 0 and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and status = '0' and identity = '$identity'" );

	$l1    += $le1;
	$total += $le1;

	$l2 += (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}entry WHERE ide > 0 and status = '1' ".get_people( $iduser1 )." and datum_do BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

	$l3 += (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}entry WHERE ide > 0 and status = '1' and did > 0 ".get_people( $iduser1 )." and datum_do BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

}

if ( $total > 20 ) {
	$total = round( $total / 20 + 0.49 ) * 20;
}
else {
	$total = 20;
}

$shkala = [];
$t      = 0;

while ($t <= $total) {

	$shkala[] = $t;

	$t += 20;

}

$shkala = implode( ",", $shkala );

?>

<style>

	#gauge ul.group {
		position              : absolute;
		z-index               : 1;
		top                   : calc(100% - 40px);
		right                 : 10px;
		display               : table;
		list-style            : none;
		background            : rgba(245, 245, 245, 0.3);
		border                : 1px solid #CCD1D9;
		margin-top            : 5px;
		padding               : 0;
		font-size             : 0.9em;
		border-radius         : 4px;
		-moz-border-radius    : 4px;
		-webkit-border-radius : 4px;
	}

	#gauge ul.group > li {
		margin       : 0 !important;
		padding      : 5px 10px !important;
		display      : table-cell;
		text-align   : center;
		cursor       : pointer;
		border-right : 1px solid #CCD1D9;
		box-sizing   : border-box !important;
	}

	#gauge ul.group > li:last-child {
		border-right : 0;
	}

	#gauge ul.group > li:hover,
	#gauge ul.group > li.active {
		color        : #fff;
		background   : #C0392B;
		border-color : #C0392B !important;
	}

</style>

<div class="flex-container box--child p0" style="justify-content: space-between; margin-top:-15px">

	<div class="flex-string div-center wp30 flx-2 p10">

		<canvas id="gauge"
		        data-type="radial-gauge"
		        data-width="<?= (!$isMobile ? $Width : 120) ?>"
		        data-height="<?= (!$isMobile ? 160 : 120) ?>"
		        data-units="шт."
		        data-title="Добавлено"

		        data-value="<?= (int)$l1 ?>"
		        data-value-box-border-radius="3"
		        data-value-box-stroke="0"
		        data-color-value-box-background="#fff"
		        data-value-box="true"
		        data-color-value-box-shadow="0"

		        data-min-value="0"
		        data-max-value="<?= $total ?>"
		        data-major-ticks="<?= $shkala ?>"
		        data-minor-ticks="1"
		        data-stroke-ticks="true"

		        data-highlights='[
		            {"from": <?= ($total - 0.8 * $total) ?>, "to": <?= ($total - 0.6 * $total) ?>, "color": "rgba(67,160,71 ,.5)"},
			        {"from": <?= ($total - 0.6 * $total) ?>, "to": <?= ($total - 0.4 * $total) ?>, "color": "rgba(67,160,71 ,.75)"},
			        {"from": <?= ($total - 0.4 * $total) ?>, "to": <?= $total ?>, "color": "rgba(67,160,71 ,1)"}
			    ]'

		        data-color-plate="#fff"
		        data-color-major-ticks="#222"
		        data-color-minor-ticks="#ddd"
		        data-color-title="#222"
		        data-color-units="#222"
		        data-color-numbers="#222"
		        data-color-needle-start="rgba(240, 128, 128, 5)"
		        data-color-needle-end="#AD1457"
		        data-animation-rule="bounce"
		        data-animation-duration="500"
		        data-font-value="Led"
		        data-animated-value="true"
		        data-border-shadow-width="2"
		        data-borders="false"
		        data-needle-type="arrow"
		        data-needle-width="3"
		        data-needle-circle-size="7"
		        data-needle-circle-outer="true"
		        data-needle-circle-inner="false"
		        data-color-needle-circle-outer="#222"
		        data-color-needle-circle-outer-end="#ccc"
		></canvas>
		<div><?= $l1 ?> шт.</div>

	</div>
	<div class="flex-string wp30 div-center flx-2 p10">

		<canvas id="gauge"
		        data-type="radial-gauge"
		        data-width="<?= (!$isMobile ? $Width : 120) ?>"
		        data-height="<?= (!$isMobile ? 160 : 120) ?>"
		        data-units="%"
		        data-title="Обработано"

		        data-value="<?= $l1 > 0 ? (int)round( ($l2 / $l1 * 100), 0 ) : 0 ?>"
		        data-value-box-border-radius="3"
		        data-value-box-stroke="0"
		        data-color-value-box-background="#fff"
		        data-value-box="true"
		        data-color-value-box-shadow="0"

		        data-min-value="0"
		        data-max-value="100"
		        data-major-ticks="0,20,40,60,80,100"
		        data-minor-ticks="2"
		        data-stroke-ticks="true"
		        data-highlights='[
		            { "from": 0, "to": 20, "color": "rgba(198,40,40 ,1)" },
		            { "from": 20, "to": 40, "color": "rgba(198,40,40 ,.5)" },
		            { "from": 40, "to": 60, "color": "rgba(251,140,0 ,.6)" },
		            { "from": 60, "to": 80, "color": "rgba(67,160,71 ,.6)" },
		            { "from": 80, "to": 100, "color": "rgba(67,160,71 ,1)" }
		        ]'
		        data-color-plate="#fff"
		        data-color-major-ticks="#222"
		        data-color-minor-ticks="#ddd"
		        data-color-title="#222"
		        data-color-units="#222"
		        data-color-numbers="#222"
		        data-color-needle-start="rgba(240, 128, 128, 1)"
		        data-color-needle-end="#AD1457"
		        data-animation-rule="bounce"
		        data-animation-duration="500"
		        data-font-value="Led"
		        data-animated-value="true"
		        data-border-shadow-width="2"
		        data-borders="false"
		        data-needle-type="arrow"
		        data-needle-width="3"
		        data-needle-circle-size="7"
		        data-needle-circle-outer="true"
		        data-needle-circle-inner="false"
		        data-color-needle-circle-outer="#222"
		        data-color-needle-circle-outer-end="#ccc"

		></canvas>
		<div><?= $l2 ?> шт.</div>

	</div>
	<div class="flex-string wp30 div-center flx-2 p10">

		<canvas id="gauge"
		        data-type="radial-gauge"
		        data-width="<?= (!$isMobile ? $Width : 120) ?>"
		        data-height="<?= (!$isMobile ? 160 : 120) ?>"
		        data-units="%"
		        data-title="Конверсия"

		        data-value="<?= $l1 > 0 ? round( ($l3 / $l1 * 100), 0 ) : 0 ?>"
		        data-value-box-border-radius="3"
		        data-value-box-stroke="0"
		        data-color-value-box-background="#fff"
		        data-value-box="true"
		        data-color-value-box-shadow="0"

		        data-min-value="0"
		        data-max-value="100"
		        data-major-ticks="0,20,40,60,80,100"
		        data-minor-ticks="2"
		        data-stroke-ticks="true"
		        data-highlights='[
		            { "from": 0, "to": 20, "color": "rgba(198,40,40 ,1)" },
		            { "from": 20, "to": 40, "color": "rgba(198,40,40 ,.5)" },
		            { "from": 40, "to": 60, "color": "rgba(251,140,0 ,.6)" },
		            { "from": 60, "to": 80, "color": "rgba(67,160,71 ,.6)" },
		            { "from": 80, "to": 100, "color": "rgba(67,160,71 ,1)" }
		        ]'
		        data-color-plate="#fff"
		        data-color-major-ticks="#222"
		        data-color-minor-ticks="#ddd"
		        data-color-title="#222"
		        data-color-units="#222"
		        data-color-numbers="#222"
		        data-color-needle-start="rgba(240, 128, 128, 1)"
		        data-color-needle-end="#AD1457"
		        data-animation-rule="bounce"
		        data-animation-duration="500"
		        data-font-value="Led"
		        data-animated-value="true"
		        data-border-shadow-width="2"
		        data-borders="false"
		        data-needle-type="arrow"
		        data-needle-width="3"
		        data-needle-circle-size="7"
		        data-needle-circle-outer="true"
		        data-needle-circle-inner="false"
		        data-color-needle-circle-outer="#222"
		        data-color-needle-circle-outer-end="#ccc"

		></canvas>
		<div><?= $l3 ?> шт.</div>

	</div>

	<div class="space-40 visible-iphone"></div>

</div>

<div class="pull-aright">
	<ul class="group">
		<li data-id="calendarweek">Неделя</li>
		<li data-id="month">Месяц</li>
		<li data-id="quart">Квартал</li>
	</ul>
</div>

<script src="/assets/js/gauge.min.js"></script>
<script>

	$('#gauge').find('ul.group').find('li[data-id="<?=$setperiod?>"]').addClass('active');

	$('#gauge').find('li').bind('click', function () {

		var id = $(this).data('id');

		setCookie('gaugeInterval', id, {"expires": 1000000});

		$('#gauge').load('content/vigets/viget.gauge.php');

	});

	$(document).ready(function () {

		var gauge = new RadialGauge({
			renderTo: 'gauge'
		}).draw();

	});

</script>