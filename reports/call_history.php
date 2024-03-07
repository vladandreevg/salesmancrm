<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2019.x          */
/* ============================ */

set_time_limit(0);

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$da1          = $_REQUEST['da1'];
$da2          = $_REQUEST['da2'];
$act          = $_REQUEST['act'];
$per          = $_REQUEST['per'];
$period       = $_REQUEST['period'];

$user_list    = (array)$_REQUEST['user_list'];

if (!$per) $per = 'nedelya';

$period = ($period == '') ? getPeriod('month') : getPeriod($period);

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];


$sort .= (!empty($user_list)) ? " and ".$sqlname."callhistory.iduser IN (".yimplode(",", $user_list).")" : " and ".$sqlname."callhistory.iduser IN (".yimplode(",", get_people($iduser1, "yes")).")";

$da1_array = explode("-", $da1);
$da2_array = explode("-", $da2);

$dstart = mktime(0, 0, 0, $da1_array[1], $da1_array[2], $da1_array[0]);
$dend   = mktime(23, 59, 59, $da2_array[1], $da2_array[2], $da2_array[0]);

$step = 86400;
$day  = (int)(($dend - $dstart) / $step) + 1;

$dat = $dstart;//стартовое значение даты

$status = [
	'ANSWERED'  => 'Отвеченный',
	'NO ANSWER' => 'Не отвечен',
	'NOANSWER'  => 'Не отвечен',
	'BUSY'      => 'Занято',
	'CANCEL'    => 'Отменен',
	'TRANSFER'  => 'Переадресация',
	'BREAKED'   => 'Прервано',
	'FAILED'    => 'Ошибка'
];
$colors = [
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'NOANSWER'  => 'red',
	'BUSY'      => 'broun',
	'CANCEL'    => 'red',
	'TRANSFER'  => 'blue',
	'BREAKED'   => 'red',
	'FAILED'    => 'red'
];
$rezult = [
	'income'  => 'Входящий',
	'outcome' => 'Исходящий',
	'inner'   => 'Внутренний'
];

$goods = [
	'ANSWERED'
];
$bads = [
	'NO ANSWER',
	'NOANSWER',
	'BUSY',
	'CANCEL',
	'BREAKED',
	'FAILED'
];

$i       = 0;
$perName = 'День';

$datas = $datac = $datacic = [];

//если период до 3-х месяцев
if (abs(diffDate2($da1, $da2)) <= 62) {

	for ($d = 0; $d < $day; $d++) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date('Y-m-d', $dat);

		////--По направлениям звонка

		$countAll[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) $sort and identity = '$identity'");

		$countIncome[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) $sort and identity = '$identity'");

		$countOutcome[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) $sort and identity = '$identity'");

		$countInner[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'inner' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) $sort and identity = '$identity'");

		//$datas[] = '{"Дата":"' . $datum . '","Звонков":"Всего","Кол-во":"' . $countAll[ $i ] . '","День":"' . date("d.m", strtotime($datum)) . '"}';
		$datas[] = '{"Дата":"'.$datum.'","Звонков":"Входящие","Кол-во":"'.$countIncome[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';
		$datas[] = '{"Дата":"'.$datum.'","Звонков":"Исходящие","Кол-во":"'.$countOutcome[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';

		////--По результатам звонка. Исходящие

		$countOutAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'ANSWERED' and direct = 'outcome' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) and identity = '$identity'");

		$countOutNoAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'NO ANSWER' and direct = 'outcome' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) and identity = '$identity'");

		$countOutBusy[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'BUSY' and direct = 'outcome' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) and identity = '$identity'");

		$datac[] = '{"Дата":"'.$datum.'","Результат":"Отвеченный","Кол-во":"'.$countOutAnswer[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';
		$datac[] = '{"Дата":"'.$datum.'","Результат":"Не отвеченный","Кол-во":"'.$countOutNoAnswer[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';
		$datac[] = '{"Дата":"'.$datum.'","Результат":"Занято","Кол-во":"'.$countOutBusy[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';

		////--По результатам звонка. Входящие

		$countInAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'ANSWERED' and direct = 'income' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) and identity = '$identity'");

		$countInNoAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'NO ANSWER' and direct = 'income' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) and identity = '$identity'");

		$countInBusy[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'BUSY' and direct = 'income' and (datum >= '$datum' AND datum < '$datum' + INTERVAL 1 DAY ) and identity = '$identity'");

		$datacic[] = '{"Дата":"'.$datum.'","Результат":"Отвеченный","Кол-во":"'.$countInAnswer[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';
		$datacic[] = '{"Дата":"'.$datum.'","Результат":"Не отвеченный","Кол-во":"'.$countInNoAnswer[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';
		$datacic[] = '{"Дата":"'.$datum.'","Результат":"Занято","Кол-во":"'.$countInBusy[ $i ].'","День":"'.date("d.m", strtotime($datum)).'"}';

		$i++;

	}

}
else {

	$perName = 'Месяц';

	//количество месяцев
	$monStart  = (int)getMonth($da1);
	$yearStart = (int)get_year($da1);

	$monEnd  = (int)getMonth($da2);
	$yearEnd = (int)get_year($da2);

	$mon  = $monStart;
	$year = $yearStart;

	while ($year <= $yearEnd) {

		while ($mon <= 12) {

			$mon1 = ($mon < 10) ? "0".$mon : $mon;

			$datum = $year."-".$mon1;
			$date  = $mon1.".".$year;

			////--По направлениям звонка

			$countAll[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and date_format(datum, '%Y-%c') = '$datum' $sort and identity = '$identity'");

			$countIncome[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and date_format(datum, '%Y-%m') = '$datum' $sort and identity = '$identity'");

			$countOutcome[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and date_format(datum, '%Y-%m') = '$datum' $sort and identity = '$identity'");

			$countInner[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'inner' and date_format(datum, '%Y-%m') = '$datum' $sort and identity = '$identity'");

			//$datas[] = '{"Дата":"' . $datum . '","Звонков":"Всего","Кол-во":"' . $countAll[ $i ] . '","Месяц":"' . $date . '"}';
			$datas[] = '{"Дата":"'.$datum.'","Звонков":"Входящие","Кол-во":"'.$countIncome[ $i ].'","Месяц":"'.$date.'"}';
			$datas[] = '{"Дата":"'.$datum.'","Звонков":"Исходящие","Кол-во":"'.$countOutcome[ $i ].'","Месяц":"'.$date.'"}';

			////--По результатам звонка. Исходящие

			$countOutAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'ANSWERED' and direct = 'outcome' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

			$countOutNoAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'NO ANSWER' and direct = 'outcome' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

			$countOutBusy[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'BUSY' and direct = 'outcome' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

			$datac[] = '{"Дата":"'.$datum.'","Результат":"Отвечен","Кол-во":"'.$countOutAnswer[ $i ].'","Месяц":"'.$date.'"}';
			$datac[] = '{"Дата":"'.$datum.'","Результат":"Не отвечен","Кол-во":"'.$countOutNoAnswer[ $i ].'","Месяц":"'.$date.'"}';
			$datac[] = '{"Дата":"'.$datum.'","Результат":"Занято","Кол-во":"'.$countOutBusy[ $i ].'","Месяц":"'.$date.'"}';

			////--По результатам звонка. Входящие

			$countInAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

			$countInNoAnswer[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'NO ANSWER' and direct = 'income' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

			$countInBusy[ $i ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and res = 'BUSY' and direct = 'income' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

			$datacic[] = '{"Дата":"'.$datum.'","Результат":"Отвечен","Кол-во":"'.$countInAnswer[ $i ].'","Месяц":"'.$date.'"}';
			$datacic[] = '{"Дата":"'.$datum.'","Результат":"Не отвечен","Кол-во":"'.$countInNoAnswer[ $i ].'","Месяц":"'.$date.'"}';
			$datacic[] = '{"Дата":"'.$datum.'","Результат":"Занято","Кол-во":"'.$countInBusy[ $i ].'","Месяц":"'.$date.'"}';

			$i++;

			if ($year == $yearEnd && $mon == $monEnd) goto endo;

			$mon++;

			if ($mon > 12) {
				$mon = 1;
				goto y;
			}

		}

		y:

		$year++;

	}

	endo:

}

$countAlltel = count($datas);
$countOuttel = count($datac);
$countIntel  = count($datacic);

$datas   = implode(",", $datas);
$datac   = implode(",", $datac);
$datacic = implode(",", $datacic);

//print $datacic;

//кол-во внутренних звонков
$countInne = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 $sort and direct = 'inner' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

//длительность внутренних звонков
$countInneS = $db -> getOne("SELECT SUM(sec) as summa FROM ".$sqlname."callhistory WHERE id > 0 $sort and direct = 'inner' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

$datad = [];

$datad[] = '{"Отчет":"Направление","Тип":"Входящие","Кол-во":"'.$countIntel.'"}';
$datad[] = '{"Отчет":"Направление","Тип":"Исходящие","Кол-во":"'.$countOuttel.'"}';
$datad[] = '{"Отчет":"Направление","Тип":"Внутренние","Кол-во":"'.$countInne.'"}';

//// Исходящие

//коли-во исходящих отвеченных
$countOanswer = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and res = 'ANSWERED' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

//продолжительность исходящих отвеченных
$countOanswerS = $db -> getOne("SELECT SUM(sec) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and res = 'ANSWERED' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

//кол-во исходящих неотвеченных
$countOnoanswer = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and res = 'NO ANSWER' and phone NOT IN (SELECT phone FROM ".$sqlname."callhistory WHERE id > 0 and res = 'ANSWERED' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity') and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");


$countObusy = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and res = 'BUSY' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

$countc = $countOanswer + $countOnoanswer + $countObusy;

$datacl = [];

$datacl[] = '{"Отчет":"Результат","Тип":"Отвечен","Кол-во":"'.$countOanswer.'"}';
$datacl[] = '{"Отчет":"Результат","Тип":"Не отвечен","Кол-во":"'.$countOnoanswer.'"}';
$datacl[] = '{"Отчет":"Результат","Тип":"Занято","Кол-во":"'.$countObusy.'"}';

//// Входящие

$countIanswer = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and res = 'ANSWERED' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

$countIanswerS = $db -> getOne("SELECT SUM(sec) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and res = 'ANSWERED' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

//Входящие отвеченные
$countInoanswer = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and res = 'NO ANSWER' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

//продолжительность входящих отвеченных
$countInoanswerS = $db -> getOne("SELECT SUM(sec) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and res = 'ANSWERED' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

$countIbusy = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and res = 'BUSY' and datum BETWEEN '$da1 00:00:01' and '$da2 23:59:59' $sort and identity = '$identity'");

$countci = $countIanswer + $countInoanswer + $countIbusy;

$dataci = [];

$dataci[] = '{"Отчет":"Результат","Тип":"Отвечен","Кол-во":"'.$countIanswer.'"}';
$dataci[] = '{"Отчет":"Результат","Тип":"Не отвечен","Кол-во":"'.$countInoanswer.'"}';
$dataci[] = '{"Отчет":"Результат","Тип":"Занято","Кол-во":"'.$countIbusy.'"}';

$datad  = implode(",", $datad);
$datacl = implode(",", $datacl);
$dataci = implode(",", $dataci);

$countAll  = $countci + $countc + $countInne;
$countAllS = $countInoanswerS + $countOanswerS;

/**
 * Загрузка по часам
 */
$dati = [];
for ($h = 0; $h < 24; $h++) {

	$countA['in'][ $h ]  = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 ".$sort." and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%H') = '".$h."' and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'");
	$countA['out'][ $h ] = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."callhistory WHERE id > 0 ".$sort." and res = 'ANSWERED' and direct = 'outcome' and date_format(datum, '%H') = '".$h."' and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'");

	$dati[] = '{"Тип":"Входящий","Кол-во":"'.$countA['in'][ $h ].'","Час":"'.$h.'"}';
	$dati[] = '{"Тип":"Исходящий","Кол-во":"'.$countA['out'][ $h ].'","Час":"'.$h.'"}';

}
$dati = implode(",", $dati);

?>
<style>
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
</style>

<DIV class="zagolovok_rep text-center">

	<h3>
		Анализ звонков
		<div class="fs-07 gray2 mt5">с <?= format_date_rus($da1) ?> по <?= format_date_rus($da2) ?></div>
	</h3>

</DIV>

<hr>

<div class="fs-12 pad10 Bold">Сводная информация:</div>

<div class="flex-container" style="margin:0 auto;">

	<div class="flex-string wp40">

		<table class="wp90">
			<thead>
			<tr class="bordered header_contaner">
				<th class="blue">Параметр</th>
				<th class="blue">Кол-во</th>
				<th class="blue">Длит-ть</th>
			</tr>
			</thead>
			<tr height="30" class="ha">
				<td><b class="blue">Звонков за период, в т.ч.</b>:</td>
				<td align="right"><b><?= $countAll ?></b>&nbsp;</td>
				<td align="right"><b><?= gmdate("H:i:s", $countAllS) ?></b>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Входящие</b>:</td>
				<td align="right"><?= $countci ?>&nbsp;</td>
				<td align="right"><?= gmdate("H:i:s", $countInoanswerS) ?>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Исходящие</b>:</td>
				<td align="right"><?= $countc ?>&nbsp;</td>
				<td align="right"><?= gmdate("H:i:s", $countOanswerS) ?>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Внутренние</b>:</td>
				<td align="right"><?= $countInne ?>&nbsp;</td>
				<td align="right">-&nbsp;</td>
			</tr>
			<tr height="30">
				<td><b class="blue">Входящие, в т.ч.:</b></td>
				<td align="right"><b class="blue"><?= $countci ?></b>&nbsp;</td>
				<td align="right"><b class="blue"><?= gmdate("H:i:s", $countInoanswerS) ?></b>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Отвеченные</b>:</td>
				<td align="right"><?= $countIanswer ?>&nbsp;</td>
				<td align="right"><?= gmdate("H:i:s", $countInoanswerS) ?>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Не отвеченные</b>:</td>
				<td align="right"><?= $countInoanswer ?>&nbsp;</td>
				<td align="right">-&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Занято</b>:</td>
				<td align="right"><?= $countIbusy ?>&nbsp;</td>
				<td align="right">-&nbsp;</td>
			</tr>
			<tr height="30">
				<td><b class="blue">Исходящие, в т.ч.:</b></td>
				<td align="right"><b class="blue"><?= $countc ?></b>&nbsp;</td>
				<td align="right"><b class="blue"><?= gmdate("H:i:s", $countOanswerS) ?></b>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Отвеченные</b>:</td>
				<td align="right"><?= $countOanswer ?>&nbsp;</td>
				<td align="right"><?= gmdate("H:i:s", $countOanswerS) ?>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Не отвеченные</b>:</td>
				<td align="right"><?= $countOnoanswer ?>&nbsp;</td>
				<td align="right">-&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Занято</b>:</td>
				<td align="right"><?= $countObusy ?>&nbsp;</td>
				<td align="right">-&nbsp;</td>
			</tr>
		</table>

	</div>
	<div class="flex-string wp30">

		<div class="paddbott20"><b>Входящие звонки</b></div>

		<div id="chartd" style="padding:5px; height: 100%;"></div>
		<!--<script src="/assets/js/d3.min.js"></script>-->
		<script src="/assets/js/dimple.js/dimple.min.js"></script>
		<script>

			var width = 300;
			var height = 300;
			var svg = dimple.newSvg("#chartd", width, height);
			var data = [<?=$dataci?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(20, 20, 300, 300);

			myChart.addMeasureAxis("p", "Кол-во");
			var ring = myChart.addSeries("Тип", dimple.plot.pie);

			ring.innerRadius = "50%";

			myChart.assignColor("Занято", "#B71C1C");
			myChart.assignColor("Не отвечен", "#B0BEC5");
			myChart.assignColor("Отвечен", "#01579B");

			var myLegend = myChart.addLegend(0, 0, 100, 100, "left");
			myChart.setMargins(60, 50, 40, 80);
			myChart.draw(1000);

			//y.tickFormat = ".f";
			/*s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});*/

			$(window).bind('resizeEnd', function () {
				myChart.draw(0, true);
			});

		</script>

	</div>
	<div class="flex-string wp30">

		<div class="paddbott20"><b>Исходящие звонки</b></div>
		<div id="chartcl" style="padding:5px; height: 100%;"></div>
		<script>

			var width = 300;
			var height = 300;
			var svg = dimple.newSvg("#chartcl", width, height);
			var data = [<?=$datacl?>];

			var myChartl = new dimple.chart(svg, data);

			myChartl.setBounds(20, 20, 300, 300);

			myChartl.addMeasureAxis("p", "Кол-во");
			var ring2 = myChartl.addSeries("Тип", dimple.plot.pie);

			ring2.innerRadius = "50%";

			myChartl.assignColor("Занято", "#B71C1C");
			myChartl.assignColor("Не отвечен", "#B0BEC5");
			myChartl.assignColor("Отвечен", "#01579B");

			var myLegend = myChartl.addLegend(0, 0, 100, 100, "left");
			myChartl.setMargins(60, 50, 40, 80);
			myChartl.draw(100);

			//y.tickFormat = ".f";
			/*s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});*/

			$(window).bind('resizeEnd', function () {
				myChartl.draw(0, true);
			});

		</script>

	</div>

</div>

<hr>

<div class="fs-12 pad10 Bold">Все звонки:</div>

<?php
if ($countAlltel > 0) {
	?>
	<div id="graf" class="div-center" style="display:block; height:400px">
		<div id="chart" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart", width, height);
			var data1 = [<?=$datas?>];

			var myChart1 = new dimple.chart(svg, data1);

			myChart1.setBounds(100, 0, width - 50, height - 100);

			var x = myChart1.addCategoryAxis("x", "<?=$perName?>", "%d-%m-%Y", "%d.%m");
			x.addOrderRule("Дата");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart1.addMeasureAxis("y", "Кол-во");
			y.showGridlines = false;//скрываем линии
			myChart1.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			myChart1.assignColor("Занято", "#B71C1C");
			myChart1.assignColor("Не отвечен", "#B0BEC5");
			myChart1.assignColor("Отвечен", "#01579B");

			var s = myChart1.addSeries(["Звонков"], dimple.plot.bar);
			s.lineWeight = 1;
			s.lineMarkers = true;
			s.stacked = true;

			myChart1.assignColor("Входящие", "#B71C1C");
			myChart1.assignColor("Исходящие", "#01579B");

			var myLegend1 = myChart1.addLegend(0, 0, width - 35, 0, "right");
			myChart1.setMargins(60, 50, 40, 80);
			myChart1.draw(1000);

			y.tickFormat = ".f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			myChart1.legends = [];

			$(window).bind('resizeEnd', function () {
				myChart1.draw(0, true);
			});

		</script>
	</div>
	<?php
}
else print '<div style="padding-left: 10px; padding-top: 20px; display: block;">Звонков нет</div>';
?>

<hr>

<div class="fs-12 pad10 Bold">Результаты входящих звонков:</div>

<?php
if ($countIntel > 0) {
	?>
	<div id="graf3" class="div-center" style="display:block; height:400px">
		<div id="chart3" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart3", width, height);
			var data3 = [<?=$datacic?>];

			var myChart3 = new dimple.chart(svg, data3);

			myChart3.setBounds(100, 0, width - 50, height - 100);

			var x = myChart3.addCategoryAxis("x", "<?=$perName?>", "%d-%m-%Y", "%d.%m");
			x.addOrderRule("Дата");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart3.addMeasureAxis("y", "Кол-во");
			y.showGridlines = false;//скрываем линии
			myChart3.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			myChart3.assignColor("Занято", "#B71C1C");
			myChart3.assignColor("Не отвечен", "#B0BEC5");
			myChart3.assignColor("Отвечен", "#01579B");

			var s = myChart3.addSeries(["Результат"], dimple.plot.bar);
			s.lineWeight = 1;
			s.lineMarkers = true;
			s.stacked = true;

			var myLegend2 = myChart3.addLegend(0, 0, width - 35, 0, "right");
			myChart3.setMargins(60, 50, 40, 80);
			myChart3.draw(1000);

			myChart3.legends = [];

			y.tickFormat = ".f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			$(window).bind('resizeEnd', function () {
				myChart3.draw(0, true);
			});

		</script>
	</div>
	<?php
}
else print '<div style="padding-left: 10px; padding-top: 20px; display: block;">Звонков нет</div>';
?>

<hr>

<div class="fs-12 pad10 Bold">Результаты исходящих звонков:</div>

<?php
if ($countOuttel > 0) {
	?>
	<div id="graf2" class="div-center" style="display:block; height:400px">
		<div id="chart2" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart2", width, height);
			var data2 = [<?=$datac?>];

			var myChart2 = new dimple.chart(svg, data2);

			myChart2.setBounds(100, 0, width - 50, height - 100);

			var x = myChart2.addCategoryAxis("x", "<?=$perName?>", "%d-%m-%Y", "%d.%m");
			x.addOrderRule("Дата");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart2.addMeasureAxis("y", "Кол-во");
			y.showGridlines = false;//скрываем линии
			myChart2.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			myChart2.assignColor("Занято", "#B71C1C");
			myChart2.assignColor("Не отвечен", "#B0BEC5");
			myChart2.assignColor("Отвечен", "#01579B");

			var s = myChart2.addSeries(["Результат"], dimple.plot.bar);
			s.lineWeight = 1;
			s.lineMarkers = true;
			s.stacked = true;

			var myLegend2 = myChart2.addLegend(0, 0, width - 35, 0, "right");
			myChart2.setMargins(60, 50, 40, 80);
			myChart2.draw(1000);

			myChart2.legends = [];

			y.tickFormat = ".f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			$(window).bind('resizeEnd', function () {
				myChart2.draw(0, true);
			});

		</script>
	</div>
	<?php
}
else print '<div style="padding-left: 10px; padding-top: 20px; display: block;">Звонков нет</div>';
?>

<hr>

<div class="fs-12 pad10 Bold">Нагрузка по часам:</div>

<?php
if ((count($countA['in']) + count($countA['in'])) > 0) {
	?>
	<div id="graf4" class="div-center" style="display:block; height:400px">
		<div id="chart4" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart4", width, height);
			var data4 = [<?=$dati?>];

			//добавляет прямоугольник под диаграмму
			/*svg.append("rect")
				.attr("x", 0)
				.attr("y", 0)
				.attr("width", width * 1.2)
				.attr("height", height * 1.2)
				.style("fill", "#456");*/

			var myChart4 = new dimple.chart(svg, data4);

			myChart4.setBounds(100, 0, width - 50, height - 100);

			var x = myChart4.addCategoryAxis("x", "Час");
			x.addOrderRule("Час");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart4.addMeasureAxis("y", "Кол-во");
			y.showGridlines = false;//скрываем линии

			//myChart4.assignColor("Входящий", "rgba(183,28,28 ,0.8)", "#b71c1c");
			//myChart4.assignColor("Исходящий", "rgba(33,150,243,0.8)", "#01579B");

			myChart4.assignColor("Входящий", "#B71C1C");
			myChart4.assignColor("Исходящий", "#01579B");

			var s = myChart4.addSeries(["Тип"], dimple.plot.line);
			s.lineWeight = 1;
			//s.lineMarkers = true;
			s.stacked = false;
			//s.interpolation = "cardinal";

			var myLegend4 = myChart4.addLegend(0, 15, width - 35, 0, "right");
			myChart4.setMargins(60, 50, 40, 80);
			myChart4.draw(1000);

			$(window).bind('resizeEnd', function () {
				myChart4.draw(0, true);
			});

		</script>
	</div>
	<?php
}
else print '<div style="padding-left: 10px; padding-top: 20px; display: block;">Звонков нет</div>';
?>

<div style="height:50px"></div>