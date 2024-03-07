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

$chat     = new Chats();
$channels = $chat -> getChannels();

$new       = $archive = $newdouble = $total = [];
$chnls     = [];
$chartData = [];
$duration  = [];
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

		// новые чаты
		$snew = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m-%d') = '$datum' AND event = 'dialog' AND newvalue = 'inwork'" );

		// завершенные чаты
		$sarchive = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m-%d') = '$datum' AND event = 'dialog' AND newvalue = 'archive'" );

		// повторные чаты
		$snewdouble = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m-%d') = '$datum' AND event = 'dialog' AND newvalue = 'inwork' AND oldvalue = 'archive'" );

		// время ответа
		$sduration = $db -> getOne( "SELECT COUNT(*) as count, SUM(newvalue) as summa FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m-%d') = '$datum' AND event = 'answer'" );

		// среднее время ответа на вопрос, мин.
		$mdur = (int)$sduration['count'] > 0 ? round( ($sduration['summa'] / (int)$sduration['count']) / 60, 0 ) : 0;

		$new[]       = '{"Тип":"Новые", "Дата":"'.$do.'","Кол-во":"'.($snew).'"}';
		$newdouble[] = '{"Тип":"Повторные", "Дата":"'.$do.'","Кол-во":"'.($snewdouble).'"}';
		$archive[]   = '{"Тип":"Завершенные", "Дата":"'.$do.'","Кол-во":"'.($sarchive).'"}';

		$duration[] = '{"Тип":"Задержка", "Дата":"'.$do.'","Минут":"'.($mdur + 0).'"}';

		$order[] = '"'.$do.'"';

		$nTotal  += $snew;
		$ndTotal += $snew;
		$aTotal  += $sarchive;
		$dTotal  += $mdur;

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

			// новые чаты
			$snew = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m') = '$datum' AND event = 'dialog' AND newvalue = 'inwork'" );

			// завершенные чаты
			$sarchive = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m') = '$datum' AND event = 'dialog' AND newvalue = 'archive'" );

			// повторные чаты
			$snewdouble = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m') = '$datum' AND event = 'dialog' AND newvalue = 'inwork' AND oldvalue = 'archive'" );

			// время ответа
			$sduration = $db -> getRow( "SELECT COUNT(*) as count, SUM(newvalue) as summa FROM {$sqlname}chats_logs WHERE DATE_FORMAT(datum, '%Y-%m') = '$datum' AND event = 'answer'" );

			// среднее время ответа на вопрос, мин.
			$mdur = (int)$sduration['count'] > 0 ? round( ($sduration['summa'] / (int)$sduration['count']) / 60, 0 ) : 0;

			$new[]       = '{"Тип":"Новые", "Дата":"'.$date.'","Кол-во":"'.($snew).'"}';
			$newdouble[] = '{"Тип":"Повторные", "Дата":"'.$date.'","Кол-во":"'.($snewdouble).'"}';
			$archive[]   = '{"Тип":"Завершенные", "Дата":"'.$date.'","Кол-во":"'.($sarchive).'"}';

			$duration[] = '{"Тип":"Задержка ответа", "Дата":"'.$date.'","Минут":"'.($mdur).'"}';

			$order[] = '"'.$date.'"';

			$nTotal  += $snew;
			$ndTotal += $snewdouble;
			$aTotal  += $sarchive;
			$dTotal  += $mdur;
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

// новые чаты
$newTotalTime = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE datum BETWEEN '$d1 00:00:00' AND '$d2 23:59:59' AND event = 'dialog' AND newvalue = 'inwork'" );

// завершенные чаты
$archiveTotalTime = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE datum BETWEEN '$d1 00:00:00' AND '$d2 23:59:59' AND event = 'dialog' AND newvalue = 'archive'" );

// повторные чаты
$doubleTotalTime = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE datum BETWEEN '$d1 00:00:00' AND '$d2 23:59:59' AND event = 'dialog' AND newvalue = 'inwork' AND oldvalue = 'archive'" );

$rdiff = count( $new );

$order = implode( ",", $order );

$new       = implode( ",", $new );
$newdouble = implode( ",", $newdouble );
$archive   = implode( ",", $archive );

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
		<div class="flex-string wp50 pr10 text-right">

			<div class="fs-07 uppercase gray2">За всё время</div>
			<div class="Bold fs-30 gray mt10"><?= $newTotalTime ?></div>

		</div>

	</div>
	<div id="chartNew"></div>

</div>


<div class="space-20"></div>

<div class="text-center">

	<h1 class="fs-20 mt20 mb10">Повторные диалоги</h1>

</div>

<div class="space-40"></div>

<div class="datas2">

	<div class="data p5 flex-container box--child">

		<div class="flex-string wp50 pl10">

			<div class="fs-07 uppercase gray2">За период</div>
			<div class="Bold fs-30 mt10 deepblue"><?= $ndTotal ?></div>

		</div>
		<div class="flex-string wp50 pr10 text-right">

			<div class="fs-07 uppercase gray2">За всё время</div>
			<div class="Bold fs-30 gray mt10"><?= $doubleTotalTime ?></div>

		</div>

	</div>
	<div id="chartDouble"></div>

</div>


<div class="space-20"></div>

<div class="text-center">

	<h1 class="fs-20 mt20 mb10">Завершенные диалоги</h1>

</div>

<div class="space-40"></div>

<div class="datas3">

	<div class="data p5 flex-container box--child">

		<div class="flex-string wp50 pl10">

			<div class="fs-07 uppercase gray2">За период</div>
			<div class="Bold fs-30 mt10 green"><?= $aTotal ?></div>

		</div>
		<div class="flex-string wp50 pr10 text-right">

			<div class="fs-07 uppercase gray2">За всё время</div>
			<div class="Bold fs-30 gray mt10"><?= $archiveTotalTime ?></div>

		</div>

	</div>
	<div id="chartArchive"></div>

</div>


<div class="space-60"></div>

<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>
<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
<script>

	var diff = parseInt('<?=$rdiff?>');

	(function () {

		var elm = $('#chartNew');

		elm.empty();

		var width = elm.closest('.datas').width();
		var svg = dimple.newSvg("#chartNew", width, '200');
		var data = [<?=$new?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(50, 10, width - 10, 180);

		var x = myChart.addCategoryAxis("x", "Дата");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		//x.hidden = false;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		//myChart.assignColor("Тип", "#B0BEC5", "#CFD8DC");
		myChart.assignColor("Новые", "#1E88E5", "#1E88E5");

		var s = myChart.addSeries("Тип", dimple.plot.bar);
		s.barGap = 0.40;
		s.interpolation = "step";

		//myChart.addLegend(5, 0, width, 0, "right");

		var margin = (diff > 10 && diff <= 31) ? 60 : 20;

		//console.log(margin);

		myChart.setMargins(30, 20, 20, margin);
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

		var elm = $('#chartDouble');

		elm.empty();

		var width = elm.closest('.datas2').width();
		var svg = dimple.newSvg("#chartDouble", width, '200');
		var data = [<?=$newdouble?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(50, 10, width - 10, 180);

		var x = myChart.addCategoryAxis("x", "Дата");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		//x.hidden = true;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		//myChart.assignColor("Тип", "#B0BEC5", "#CFD8DC");
		myChart.assignColor("Повторные", "#3F51B5", "#3F51B5");

		var s = myChart.addSeries("Тип", dimple.plot.bar);
		s.barGap = 0.40;
		s.interpolation = "step";

		//myChart.addLegend(5, 0, width, 0, "right");

		var margin = (diff > 10 && diff <= 31) ? 60 : 20;

		//console.log(margin);

		myChart.setMargins(30, 20, 20, margin);
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

		var width = elm.closest('.datas3').width();
		var svg = dimple.newSvg("#chartArchive", width, '200');
		var data = [<?=$archive?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(50, 10, width - 10, 180);

		var x = myChart.addCategoryAxis("x", "Дата");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		//x.hidden = true;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		//myChart.assignColor("Тип", "#B0BEC5", "#CFD8DC");
		myChart.assignColor("Завершенные", "#43A047", "#43A047");

		var s = myChart.addSeries("Тип", dimple.plot.bar);
		s.barGap = 0.40;
		s.interpolation = "step";

		//myChart.addLegend(5, 0, width, 0, "right");

		var margin = (diff > 10 && diff <= 31) ? 60 : 20;

		//console.log(margin);

		myChart.setMargins(30, 20, 20, margin);
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
