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

$mdwset       = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
$leadsettings = json_decode( $mdwset['content'], true );
$coordinator  = $leadsettings["leadСoordinator"];
$operators    = $leadsettings["leadOperator"];

$total = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and DATE_FORMAT(datum, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );
$total = round( $total / 20 ) * 20;

if ( $total < 100 ) {
	$total = 100;
}

$shkala = [];
$t      = 0;

while ($t <= $total) {

	$shkala[] = $t;

	$t += 20;

}

$shkala = implode( ",", $shkala );

$l1 = $l2 = 0;

//заявки для операторов
if ( in_array( $iduser1, $operators ) ) {

	$l1 = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '1' and DATE_FORMAT(datum, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

	$l2 = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and iduser = '$iduser1' and status = '2' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

}

//заявки для координатора
if ( $iduser1 == $coordinator ) {

	$l1 = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and DATE_FORMAT(datum, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );
	$l2 = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and status = '2' and DATE_FORMAT(datum_do, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and identity = '$identity'" );

}

?>

<style>

	ul.group {
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

	ul.group > li {
		margin       : 0 !important;
		padding      : 5px 10px !important;
		display      : table-cell;
		text-align   : center;
		cursor       : pointer;
		border-right : 1px solid #CCD1D9;
		box-sizing   : border-box !important;
	}

	ul.group > li:last-child {
		border-right : 0;
	}

	ul.group > li:hover,
	ul.group > li.active {
		color      : #fff;
		background : #C0392B;
	}

</style>

<div class="flex-container box--child" style="justify-content: space-between;">

	<div class="flex-string div-center wp30 flx-2 p10">

		<canvas id="gauge"
		        data-type="radial-gauge"
		        data-width="160"
		        data-height="160"
		        data-units="шт."
		        data-title="Число заявок"

		        data-value="<?= $l1 ?>"
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
			        {"from": <?= ($total - 0.4 * $total) ?>, "to": <?= $total ?>, "color": "rgba(200, 50, 50, .75)"}
			    ]'

		        data-color-plate="var(--black)"
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

	</div>
	<div class="flex-string wp30 div-center flx-2 p10">

		<canvas id="gauge"
		        data-type="radial-gauge"
		        data-width="160"
		        data-height="160"
		        data-units="%"
		        data-title="Обработано"

		        data-value="<?= $l2 ?>"
		        data-value-box-border-radius="3"
		        data-value-box-stroke="0"
		        data-color-value-box-background="#fff"
		        data-value-box="true"
		        data-color-value-box-shadow="0"

		        data-min-value="0"
		        data-max-value="120"
		        data-major-ticks="0,20,40,60,80,100,120"
		        data-minor-ticks="2"
		        data-stroke-ticks="true"
		        data-highlights='[
		            { "from": 0, "to": 40, "color": "rgba(255,255,255 ,.8)" },
		            { "from": 40, "to": 60, "color": "rgba(174,213,129 ,1)" },
		            { "from": 60, "to": 80, "color": "rgba(251,140,0 ,.8)" },
		            { "from": 80, "to": 100, "color": "rgba(251,140,0 ,1)" },
		            { "from": 100, "to": 120, "color": "rgba(198,40,40 ,1)" }
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

	</div>
	<div class="flex-string wp30 div-center flx-2 p10">

		<canvas id="gauge"
		        data-type="radial-gauge"
		        data-width="160"
		        data-height="160"
		        data-units="%"
		        data-title="Конверсия"

		        data-value="<?= round( ($l2 / $l1 * 100), 0 ) ?>"
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
		            { "from": 0, "to": 40, "color": "rgba(255,255,255 ,.8)" },
		            { "from": 40, "to": 60, "color": "rgba(174,213,129 ,1)" },
		            { "from": 60, "to": 80, "color": "rgba(251,140,0 ,.8)" },
		            { "from": 80, "to": 100, "color": "rgba(198,40,40 ,1)" }
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

	</div>

	<div class="div-center wp100 mt20 gray2 Bold">
		Показатели обработки заявок и обращений по компании
	</div>

</div>

<script src="/assets/js/gauge.min.js"></script>
<script>

	$(document).ready(function () {

		var gauge = new RadialGauge({
			renderTo: 'gauge'
		}).draw();

	});

</script>