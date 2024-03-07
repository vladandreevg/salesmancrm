<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Chats\Chats;

$rootpath = dirname( __DIR__, 4 );
$ypath    = $rootpath."/plugins/socialChats";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

/**
 * Диаграмма "Диалоги" - информация о диалогах
 */

$d1 = $_REQUEST['periodStart'];
$d2 = $_REQUEST['periodEnd'];


$diffPeriod = abs( diffDate2( $d1, $d2 ) );

$chat      = new Chats();
$channels  = $chat -> getChannels();
$operators = $chat -> getOperatorsFull();

//print_r($operators);

$new       = $archive = $newdouble = $total = [];
$chnls     = [];
$chartData = [];
$order     = [];

if ( $diffPeriod < 60 ) {

	$da1_array = explode( "-", $d1 );
	$da2_array = explode( "-", $d2 );

	$dstart = mktime( 0, 0, 0, $da1_array[1], $da1_array[2], $da1_array[0] );
	$dend   = mktime( 23, 59, 59, $da2_array[1], $da2_array[2], $da2_array[0] );

	$step = 86400;
	$day  = (int)(($dend - $dstart) / $step) + 1;

	$dat = $dstart;//стартовое значение даты

	$nTotal = $ndTotal = $aTotal = $dTotal = 0;

	for ( $d = 0; $d < $day; $d++ ) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date( 'Y-m-d', $dat );
		$do    = date( 'd.m.y', $dat );

		foreach ( $operators as $iduser => $operator ) {

			// новые чаты
			$snew = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m-%d') = '$datum' AND event = 'operator' AND newvalue = '$iduser'" );

			// завершенные чаты
			$sarchive = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m-%d') = '$datum' AND event = 'dialog' AND newvalue = 'archive' and iduser = '$iduser'" );

			$new[]     = '{"Оператор":"'.$operator['title'].'","Дата":"'.$do.'","Кол-во":"'.($snew ).'"}';
			$archive[] = '{"Оператор":"'.$operator['title'].'","Дата":"'.$do.'","Кол-во":"'.($sarchive).'"}';

			$nTotal += $snew;
			$aTotal += $sarchive;

		}

		$order[] = '"'.$do.'"';

	}

}
else {

	$monStart  = (int)getMonth( $d1 );
	$yearStart = (int)get_year( $d1 );

	$monEnd  = (int)getMonth( $d2 );
	$yearEnd = (int)get_year( $d2 );

	$mon  = $monStart;
	$year = $yearStart;

	while ($year <= $yearEnd) {

		while ($mon <= 12) {

			$mon1 = ($mon < 10) ? "0".$mon : $mon;

			$datum = $year."-".$mon1;
			$date  = ru_month( $mon1 ).".".($year - 2000);

			foreach ( $operators as $iduser => $operator ) {

				// новые чаты
				$snew = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m') = '$datum' AND event = 'operator' AND newvalue = '$iduser'" );

				// завершенные чаты
				$sarchive = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m') = '$datum' AND event = 'dialog' AND newvalue = 'archive' and iduser = '$iduser'" );

				$new[]     = '{"Оператор":"'.$operator['title'].'","Дата":"'.$date.'","Кол-во":"'.($snew).'"}';
				$archive[] = '{"Оператор":"'.$operator['title'].'","Дата":"'.$date.'","Кол-во":"'.($sarchive).'"}';

				$nTotal += $snew;
				$aTotal += $sarchive;

			}

			$order[] = '"'.$date.'"';
			//

			if ( $year == $yearEnd && $mon == $monEnd ) {
				goto endo;
			}

			$mon++;

			if ( $mon > 12 ) {
				$mon = 1;
				goto y;
			}

		}

		y:

		$year++;

	}

	endo:

}

$rdiff = count( $order );

$order = implode( ",", $order );

$new     = implode( ",", $new );
$archive = implode( ",", $archive );

?>
<style>
	<!--
	.dimple-custom-axis-line {
		stroke       : black !important;
		stroke-width : 1.1;
	}

	.dimple-custom-axis-label {
		font-family : Arial !important;
		font-size   : 11px !important;
		font-weight : 500;
	}

	.dimple-custom-gridline {
		stroke-width     : 1;
		stroke-dasharray : 5;
		fill             : none;
		stroke           : #CFD8DC !important;
	}

	.colorit {
		display       : block;
		height        : 25px;
		line-height   : 25px;
		padding-right : 5px;
	}

	.path {
		background    : #E5F0F9;
		padding-left  : 5px;
		padding-right : 5px;
	}

	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	-->
</style>

<div class="text-center">

	<h1 class="fs-20 mt20 mb10">Новые диалоги</h1>
	<div class="gray2">за период с <?= format_date_rus( $d1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $d2 ) ?></div>

</div>

<div class="space-40"></div>

<div class="datas">

	<div class="data p5 flex-container box--child">

		<div class="flex-string wp50 pl10">

			<div class="fs-07 uppercase gray2">За период</div>
			<div class="Bold fs-30 mt10 blue"><?= $nTotal ?></div>

		</div>
		<div class="flex-string wp50 pr10 text-right hidden">

			<div class="fs-07 uppercase gray2">За всё время</div>
			<div class="Bold fs-30 gray mt10"><?= $newTotalTime ?></div>

		</div>

	</div>
	<div id="chartNew" style="display:block; height:300px"></div>

</div>

<div class="text-center">

	<h1 class="fs-20 mt20 mb10">Архивные диалоги</h1>
	<div class="gray2">за период с <?= format_date_rus( $d1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $d2 ) ?></div>

</div>

<div class="space-40"></div>

<div class="datas2">

	<div class="data p5 flex-container box--child">

		<div class="flex-string wp50 pl10">

			<div class="fs-07 uppercase gray2">За период</div>
			<div class="Bold fs-30 mt10 blue"><?= $aTotal ?></div>

		</div>
		<div class="flex-string wp50 pr10 text-right hidden">

			<div class="fs-07 uppercase gray2">За всё время</div>
			<div class="Bold fs-30 gray mt10"><?= $newTotalTime ?></div>

		</div>

	</div>
	<div id="chartArchive" style="display:block; height:300px"></div>

</div>


<div class="space-60"></div>

<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>
<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
<script>

	var diff = parseInt('<?=$rdiff?>');

	(function () {

		var elm = $('#chartNew');

		elm.empty();

		var width = elm.closest('.datas').width() - 200;
		var height = 300;
		var svg = dimple.newSvg("#chartNew", "100%", "100%");
		var data = [<?=$new?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width - 10, height - 40);

		var x = myChart.addCategoryAxis("x", "Дата");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		//x.hidden = true;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y

		myChart.defaultColors = [
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#FF9800", "#FF9800"),
			//new dimple.color("#FFEB3B", "#FFEB3B"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#E91E63", "#E91E63"),
			new dimple.color("#A1887F", "#A1887F"),
			new dimple.color("#FFC107", "#FFC107"),
			new dimple.color("#8BC34A", "#8BC34A"),
			new dimple.color("#00BCD4", "#00BCD4"),
			new dimple.color("#795548", "#795548"),
			new dimple.color("#7CB342", "#7CB342"),
			new dimple.color("#0097A7", "#0097A7"),
			new dimple.color("#D500F9", "#D500F9"),
			new dimple.color("#76FF03", "#76FF03"),
			new dimple.color("#DD2C00", "#DD2C00"),
			new dimple.color("#03A9F4", "#03A9F4"),
			new dimple.color("#B0BEC5", "#B0BEC5"),
			new dimple.color("#90A4AE", "#90A4AE"),
			new dimple.color("#78909C", "#78909C"),
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		var s = myChart.addSeries("Оператор", dimple.plot.bar);
		s.barGap = 0.40;
		s.interpolation = "step";

		//myChart.addLegend(5, 0, width, 0, "right");

		var margin = (diff > 10 && diff <= 31) ? 60 : 20;

		//console.log(margin);

		myChart.addLegend(width + 30, 0, 100, 250, "right");
		myChart.setMargins(50, 20, 270, margin);
		myChart.floatingBarWidth = 10;

		myChart.draw(1000);

		y.tickFormat = ",.0f";

		svg.selectAll(".dimple-marker,.dimple-marker-back").attr("r", 2);

		y.titleShape.remove();
		x.titleShape.remove();

		if (diff > 31)
			elm.find('.dimple-axis-x').find('.dimple-custom-axis-label').remove();

	}());

	(function () {

		var elm = $('#chartArchive');

		elm.empty();

		var width = elm.closest('.datas2').width() - 200;
		var height = 300;
		var svg = dimple.newSvg("#chartArchive", "100%", "100%");
		var data = [<?=$archive?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width - 50, height - 40);

		var x = myChart.addCategoryAxis("x", "Дата");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		//x.hidden = true;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y

		myChart.defaultColors = [
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#FF9800", "#FF9800"),
			//new dimple.color("#FFEB3B", "#FFEB3B"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#E91E63", "#E91E63"),
			new dimple.color("#A1887F", "#A1887F"),
			new dimple.color("#FFC107", "#FFC107"),
			new dimple.color("#8BC34A", "#8BC34A"),
			new dimple.color("#00BCD4", "#00BCD4"),
			new dimple.color("#795548", "#795548"),
			new dimple.color("#7CB342", "#7CB342"),
			new dimple.color("#0097A7", "#0097A7"),
			new dimple.color("#D500F9", "#D500F9"),
			new dimple.color("#76FF03", "#76FF03"),
			new dimple.color("#DD2C00", "#DD2C00"),
			new dimple.color("#03A9F4", "#03A9F4"),
			new dimple.color("#B0BEC5", "#B0BEC5"),
			new dimple.color("#90A4AE", "#90A4AE"),
			new dimple.color("#78909C", "#78909C"),
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		var s = myChart.addSeries("Оператор", dimple.plot.bar);
		s.barGap = 0.40;
		s.interpolation = "step";

		//myChart.addLegend(5, 0, width, 0, "right");

		var margin = (diff > 10 && diff <= 31) ? 60 : 20;

		//console.log(margin);

		myChart.addLegend(width + 30, 0, 100, 250, "right");
		myChart.setMargins(50, 20, 270, margin);
		myChart.floatingBarWidth = 10;

		myChart.draw(1000);

		y.tickFormat = ",.0f";

		svg.selectAll(".dimple-marker,.dimple-marker-back").attr("r", 2);

		y.titleShape.remove();
		x.titleShape.remove();

		if (diff > 31)
			elm.find('.dimple-axis-x').find('.dimple-custom-axis-label').remove();

	}());

</script>
