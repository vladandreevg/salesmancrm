<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*           ver. 7.77          */
/* ============================ */
?>
<?php
error_reporting( E_ERROR );
header( "Pragma: no-cache" );

include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";

$da1 = $_REQUEST[ 'da1' ];
$da2 = $_REQUEST[ 'da2' ];
$act = $_REQUEST[ 'act' ];
$per = $_REQUEST[ 'per' ];
if ( !$per ) $per = 'nedelya';
$user_list    = $_REQUEST[ 'user_list' ];
$clients_list = $_REQUEST[ 'clients_list' ];
$persons_list = $_REQUEST[ 'persons_list' ];
$field        = $_REQUEST[ 'field' ];
$field_query  = $_REQUEST[ 'field_query' ];

if ( $user_list[ 0 ] < 1 ) $sort = "";
else {
	for ( $i = 0; $i < count( $user_list ); $i++ ) {
		$sort .= "iduser=".$user_list[ $i ]." ";
	}
	$sort = rtrim( $sort );
	$sort = str_replace( " ", " or ", $sort );
	$sort = " and (".$sort.")";
}

$da1_array = explode( "-", $da1 );
$da2_array = explode( "-", $da2 );

$dstart = mktime( 0, 0, 0, $da1_array[ 1 ], $da1_array[ 2 ], $da1_array[ 0 ] );
$dend   = mktime( 23, 59, 59, $da2_array[ 1 ], $da2_array[ 2 ], $da2_array[ 0 ] );

$step = 86400;
$day  = intval( ( $dend - $dstart ) / $step ) + 1;

$dat = $dstart;//стартовое значение даты

$status = [
	'ANSWERED'  => 'Отвеченный',
	'NO ANSWER' => 'Не отвечен',
	'BUSY'      => 'Занято'
];
$colors = [
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'BUSY'      => 'broun'
];
$rezult = [
	'income'  => 'Входящий',
	'outcome' => 'Исходящий',
	'inner'   => 'Внутренний'
];

$i = 0;

for ( $d = 0; $d < $day; $d++ ) {

	$dat   = $dstart + $d * $step;//дата в unix-формате
	$datum = date( 'Y-m-d', $dat );

	$countAll[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and date_format(datum, '%Y-%m-%d') = '".$datum."' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countIncome[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'income' and date_format(datum, '%Y-%m-%d') = '".$datum."' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countOutcome[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and direct = 'outcome' and date_format(datum, '%Y-%m-%d') = '".$datum."' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countInner[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and direct = 'inner' and date_format(datum, '%Y-%m-%d') = '".$datum."' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	/*if($countAll[$i] > 0)*/
	$datas[] = '{"Дата":"'.$datum.'","Звонков":"Всего","Кол-во":"'.$countAll[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
	/*if($countIncome[$i] > 0)*/
	$datas[] = '{"Дата":"'.$datum.'","Звонков":"Входящих","Кол-во":"'.$countIncome[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
	/*if($countOutcome[$i] > 0)*/
	$datas[] = '{"Дата":"'.$datum.'","Звонков":"Исходящих","Кол-во":"'.$countOutcome[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';

	//////////////////////

	$countOutAnswer[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'ANSWERED' and direct = 'outcome' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countOutNoAnswer[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'NO ANSWER' and direct = 'outcome' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countOutBusy[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'BUSY' and direct = 'outcome' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	/*if($countOutAnswer[$i] > 0)*/
	$datac[] = '{"Дата":"'.$datum.'","Результат":"Отвеченный","Кол-во":"'.$countOutAnswer[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
	/*if($countOutNoAnswer[$i] > 0)*/
	$datac[] = '{"Дата":"'.$datum.'","Результат":"Не отвеченный","Кол-во":"'.$countOutNoAnswer[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
	/*if($countOutBusy[$i] > 0)*/
	$datac[] = '{"Дата":"'.$datum.'","Результат":"Занято","Кол-во":"'.$countOutBusy[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';

	//////////////////////

	//print "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z";

	$countInAnswer[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countInNoAnswer[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'NO ANSWER' and direct = 'income' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$countInBusy[ $i ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'BUSY' and direct = 'income' and date_format(datum, '%Y-%m-%d') = '".$datum."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	/*if($countInAnswer[$i] > 0)*/
	$datacic[] = '{"Дата":"'.$datum.'","Результат":"Отвеченный","Кол-во":"'.$countInAnswer[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
	/*if($countInNoAnswer[$i] > 0)*/
	$datacic[] = '{"Дата":"'.$datum.'","Результат":"Не отвеченный","Кол-во":"'.$countInNoAnswer[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
	/*if($countInBusy[$i] > 0)*/
	$datacic[] = '{"Дата":"'.$datum.'","Результат":"Занято","Кол-во":"'.$countInBusy[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';

	$i++;

}

$countAlltel = count( $datas );
$countOuttel = count( $datac );
$countIntel  = count( $datacic );

$datas   = implode( ",", $datas );
$datac   = implode( ",", $datac );
$datacic = implode( ",", $datacic );

//print $datacic;

//кол-во внутренних звонков
$countInne = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and direct = 'inner' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

//длительность внутренних звонков
$countInneS = $db -> getOne( "SELECT SUM(sec) as summa FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and direct = 'inner' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity'" );

//$countt = $countIn + $countOut + $countInne;

$datad[] = '{"Отчет":"Направление","Тип":"Входящие","Кол-во":"'.$countIn.'"}';
$datad[] = '{"Отчет":"Направление","Тип":"Исходящие","Кол-во":"'.$countOut.'"}';
$datad[] = '{"Отчет":"Направление","Тип":"Внутренние","Кол-во":"'.$countInne.'"}';

//// Исходящие

//коли-во исходящих отвеченных
$countOanswer = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'outcome' and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

//продолжительность исходящих отвеченных
$countOanswerS = $db -> getOne( "SELECT SUM(sec) as summa FROM ".$sqlname."callhistory WHERE id>0 and direct = 'outcome' and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity'" );

//кол-во исходящих неотвеченных
$countOnoanswer = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'outcome' and res = 'NO ANSWER' and phone NOT IN (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity') and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );


$countObusy = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'outcome' and res = 'BUSY' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

$countc = $countOanswer + $countOnoanswer + $countObusy;

$datacl[] = '{"Отчет":"Результат","Тип":"Отвечен","Кол-во":"'.$countOanswer.'"}';
$datacl[] = '{"Отчет":"Результат","Тип":"Не отвечен","Кол-во":"'.$countOnoanswer.'"}';
$datacl[] = '{"Отчет":"Результат","Тип":"Занято","Кол-во":"'.$countObusy.'"}';

//// Входящие

$countIanswer = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'income' and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity') t) z" );

$countIanswerS = $db -> getOne( "SELECT SUM(sec) as summa FROM ".$sqlname."callhistory WHERE id>0 and direct = 'income' and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity'" );

//Входящие отвеченные
$countInoanswer = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'income' and res = 'NO ANSWER' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and phone NOT IN (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and (direct = 'outcome' or direct = 'income') and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity') and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

//продолжительность входящих отвеченных
$countInoanswerS = $db -> getOne( "SELECT SUM(sec) as summa FROM ".$sqlname."callhistory WHERE id>0 and direct = 'income' and res = 'ANSWERED' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity'" );

$countIbusy = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 and direct = 'income' and res = 'BUSY' and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' ".$sort." and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

$countci = $countIanswer + $countInoanswer + $countIbusy;

$dataci[] = '{"Отчет":"Результат","Тип":"Отвечен","Кол-во":"'.$countIanswer.'"}';
$dataci[] = '{"Отчет":"Результат","Тип":"Не отвечен","Кол-во":"'.$countInoanswer.'"}';
$dataci[] = '{"Отчет":"Результат","Тип":"Занято","Кол-во":"'.$countIbusy.'"}';

$datad  = implode( ",", $datad );
$datacl = implode( ",", $datacl );
$dataci = implode( ",", $dataci );

$countAll  = $countci + $countc + $countInne;
$countAllS = $countInoanswerS + $countOanswerS;

/**
 * Загрузка по часам
 */
for ( $h = 0; $h < 24; $h++ ) {

	$countA[ 'in' ][ $h ]  = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%H') = '".$h."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );
	$countA[ 'out' ][ $h ] = $db -> getOne( "SELECT SUM(z.count2) as summa FROM (SELECT COUNT(t.phone) as count2 FROM (SELECT phone FROM ".$sqlname."callhistory WHERE id>0 ".$sort." and res = 'ANSWERED' and direct = 'outcome' and date_format(datum, '%H') = '".$h."' and identity = '$identity' GROUP BY phone, datum ORDER BY id DESC) t) z" );

	$dati[] = '{"Тип":"Входящий","Кол-во":"'.$countA[ 'in' ][ $h ].'","Час":"'.$h.'"}';
	$dati[] = '{"Тип":"Исходящий","Кол-во":"'.$countA[ 'out' ][ $h ].'","Час":"'.$h.'"}';

}
$dati = implode( ",", $dati );

?>
<br>
<DIV class="zagolovok_rep" align="center">
	<b>Анализ звонков&nbsp;с&nbsp;<span class="red"><?= format_date_rus( $da1 ) ?></span>&nbsp;по&nbsp;<span class="red"><?= format_date_rus( $da2 ) ?></span></b>:
</DIV>
<hr>
<div class="text-2x" style="padding-left: 10px;"><b>Сводная информация:</b></div>
<br><br>
<div style="padding-left: 30px; padding-bottom: 20px; display: block;">
	<div style="width:340px; float: left; display: inline-block;">
		<table width="300" border="0" cellpadding="5" cellspacing="0">
			<thead>
			<tr class="bordered header_contaner">
				<th align="left" class="blue">Параметр</th>
				<th class="blue">Кол-во</th>
				<th class="blue">Длит-ть</th>
			</tr>
			</thead>
			<tr height="30" class="ha">
				<td><b class="blue">Звонков за период, в т.ч.</b>:</td>
				<td align="right"><b><?= $countAll ?></b>&nbsp;</td>
				<td align="right"><b><?= gmdate( "H:i:s", $countAllS ) ?></b>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Входящие</b>:</td>
				<td align="right"><?= $countci ?>&nbsp;</td>
				<td align="right"><?= gmdate( "H:i:s", $countInoanswerS ) ?>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Исходящие</b>:</td>
				<td align="right"><?= $countc ?>&nbsp;</td>
				<td align="right"><?= gmdate( "H:i:s", $countOanswerS ) ?>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Внутренние</b>:</td>
				<td align="right"><?= $countInne ?>&nbsp;</td>
				<td align="right">-&nbsp;</td>
			</tr>
			<tr height="30">
				<td><b class="blue">Входящие, в т.ч.:</b></td>
				<td align="right"><b class="blue"><?= $countci ?></b>&nbsp;</td>
				<td align="right"><b class="blue"><?= gmdate( "H:i:s", $countInoanswerS ) ?></b>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Отвеченные</b>:</td>
				<td align="right"><?= $countIanswer ?>&nbsp;</td>
				<td align="right"><?= gmdate( "H:i:s", $countInoanswerS ) ?>&nbsp;</td>
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
				<td align="right"><b class="blue"><?= gmdate( "H:i:s", $countOanswerS ) ?></b>&nbsp;</td>
			</tr>
			<tr height="30" class="ha">
				<td>&nbsp;&nbsp;<b>Отвеченные</b>:</td>
				<td align="right"><?= $countOanswer ?>&nbsp;</td>
				<td align="right"><?= gmdate( "H:i:s", $countOanswerS ) ?>&nbsp;</td>
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
	<div style="width:300px; display: inline-block; ">
		<div class="paddbott20"><b>Входящие звонки</b></div>
		<div id="chartd" style="padding:5px; height: 100%;"></div>
		<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>
		<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
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
	<div style="width:300px; display: inline-block; ">
		<div class="paddbott20"><b>Исходящие звонки</b></div>
		<div id="chartcl" style="padding:5px; height: 100%;"></div>
		<script language="javascript">

			var width = 300;
			var height = 300;
			var svg = dimple.newSvg("#chartcl", width, height);
			var data = [<?=$datacl?>];

			var myChartl = new dimple.chart(svg, data);

			myChartl.setBounds(20, 20, 300, 300);

			myChartl.addMeasureAxis("p", "Кол-во");
			var ring2 = myChartl.addSeries("Тип", dimple.plot.pie);

			ring2.innerRadius = "50%";

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
<br><br>
<div class="text-2x" style="padding-left: 10px;"><b>Все звонки:</b></div>
<?php
if ( $countAlltel > 0 ) {
	?>
	<div id="graf" style="display:block; height:400px">
		<div id="chart" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart", width, height);
			var data1 = [<?=$datas?>];

			var myChart1 = new dimple.chart(svg, data1);

			myChart1.setBounds(100, 0, width - 50, height - 100);

			var x = myChart1.addCategoryAxis("x", "День", "%d-%m-%Y", "%d.%m");
			x.addOrderRule("Дата");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart1.addMeasureAxis("y", "Кол-во");
			y.showGridlines = true;//скрываем линии
			myChart1.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			var s = myChart1.addSeries(["Звонков"], dimple.plot.bar);
			s.lineWeight = 1;
			s.lineMarkers = true;
			s.stacked = true;

			var myLegend1 = myChart1.addLegend(0, 0, width - 35, 0, "right");
			myChart1.setMargins(60, 50, 40, 80);
			myChart1.draw(1000);

			y.tickFormat = ".f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			myChart1.legends = [];

			var filterValues1 = dimple.getUniqueValues(data1, "Результат");
			myLegend1.shapes.selectAll("rect").on("click", function (e) {
				var hide = false;
				var newFilters = [];
				filterValues1.forEach(function (f) {
					if (f === e.aggField.slice(-1)[0]) {
						hide = true;
					}
					else {
						newFilters.push(f);
					}
				});
				if (hide) {
					d3.select(this).style("opacity", 0.2);
				}
				else {
					newFilters.push(e.aggField.slice(-1)[0]);
					d3.select(this).style("opacity", 0.8);
				}
				y.tickFormat = ".f";
				filterValues1 = newFilters;
				myChart1.data = dimple.filterData(data1, "Результат", filterValues1);
				myChart1.draw(800);
			});

			$(window).bind('resizeEnd', function () {
				myChart1.draw(0, true);
			});

		</script>
	</div>
	<?php
}
else print '<div style="padding-left: 10px; padding-top: 20px; display: block;">Звонков нет</div>';
?>
<hr><br><br>
<div class="text-2x" style="padding-left: 10px;"><b>Результаты входящих звонков:</b></div>
<?php
if ( $countIntel > 0 ) {
	?>
	<div id="graf3" style="display:block; height:400px">
		<div id="chart3" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart3", width, height);
			var data3 = [<?=$datacic?>];

			var myChart3 = new dimple.chart(svg, data3);

			myChart3.setBounds(100, 0, width - 50, height - 100);

			var x = myChart3.addCategoryAxis("x", "День", "%d-%m-%Y", "%d.%m");
			x.addOrderRule("Дата");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart3.addMeasureAxis("y", "Кол-во");
			y.showGridlines = true;//скрываем линии
			myChart3.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			var s = myChart3.addSeries(["Результат"], dimple.plot.bar);
			s.lineWeight = 1;
			s.lineMarkers = true;
			s.stacked = true;

			var myLegend2 = myChart3.addLegend(0, 0, width - 35, 0, "right");
			myChart3.setMargins(60, 50, 40, 80);
			myChart3.draw(1000);

			myChart3.legends = [];

			var filterValues2 = dimple.getUniqueValues(data3, "Результат");
			myLegend2.shapes.selectAll("rect").on("click", function (e) {
				var hide = false;
				var newFilters = [];
				filterValues2.forEach(function (f) {
					if (f === e.aggField.slice(-1)[0]) {
						hide = true;
					}
					else {
						newFilters.push(f);
					}
				});
				if (hide) {
					d3.select(this).style("opacity", 0.2);
				}
				else {
					newFilters.push(e.aggField.slice(-1)[0]);
					d3.select(this).style("opacity", 0.8);
				}
				y.tickFormat = ".f";
				filterValues2 = newFilters;
				myChart3.data = dimple.filterData(data3, "Результат", filterValues2);
				myChart3.draw(800);
			});

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
<hr><br><br>
<div class="text-2x" style="padding-left: 10px;"><b>Результаты исходящих звонков:</b></div>
<?php
if ( $countOuttel > 0 ) {
	?>
	<div id="graf2" style="display:block; height:400px">
		<div id="chart2" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart2", width, height);
			var data2 = [<?=$datac?>];

			var myChart2 = new dimple.chart(svg, data2);

			myChart2.setBounds(100, 0, width - 50, height - 100);

			var x = myChart2.addCategoryAxis("x", "День", "%d-%m-%Y", "%d.%m");
			x.addOrderRule("Дата");//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart2.addMeasureAxis("y", "Кол-во");
			y.showGridlines = true;//скрываем линии
			myChart2.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			var s = myChart2.addSeries(["Результат"], dimple.plot.bar);
			s.lineWeight = 1;
			s.lineMarkers = true;
			s.stacked = true;

			var myLegend2 = myChart2.addLegend(0, 0, width - 35, 0, "right");
			myChart2.setMargins(60, 50, 40, 80);
			myChart2.draw(1000);

			myChart2.legends = [];

			var filterValues2 = dimple.getUniqueValues(data2, "Результат");
			myLegend2.shapes.selectAll("rect").on("click", function (e) {
				var hide = false;
				var newFilters = [];
				filterValues2.forEach(function (f) {
					if (f === e.aggField.slice(-1)[0]) {
						hide = true;
					}
					else {
						newFilters.push(f);
					}
				});
				if (hide) {
					d3.select(this).style("opacity", 0.2);
				}
				else {
					newFilters.push(e.aggField.slice(-1)[0]);
					d3.select(this).style("opacity", 0.8);
				}
				y.tickFormat = ".f";
				filterValues2 = newFilters;
				myChart2.data = dimple.filterData(data2, "Результат", filterValues2);
				myChart2.draw(800);
			});

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
<hr><br><br>
<div class="text-2x" style="padding-left: 10px;"><b>Нагрузка по часам:</b></div>
<?php
if ( ( count( $countA[ 'in' ] ) + count( $countA[ 'in' ] ) ) > 0 ) {
	?>
	<div id="graf4" style="display:block; height:400px">
		<div id="chart4" style="padding:5px"></div>
		<script language="javascript">

			var width = $('#contentdiv').width() - 40;
			if (width < 1) width = 600;
			var height = 380;
			var svg = dimple.newSvg("#chart4", width, height);
			var data4 = [<?=$dati?>];

			var myChart4 = new dimple.chart(svg, data4);

			myChart4.setBounds(100, 0, width - 50, height - 100);

			var x = myChart4.addCategoryAxis("x", "Час");
			x.addOrderRule("Час");//порядок вывода, иначе группирует
			x.showGridlines = false;

			var y = myChart4.addMeasureAxis("y", "Кол-во");
			y.showGridlines = true;//скрываем линии

			var s = myChart4.addSeries(["Тип"], dimple.plot.area);
			s.lineWeight = 2;
			s.lineMarkers = false;
			s.stacked = true;
			s.interpolation = "cardinal";

			var myLegend4 = myChart4.addLegend(0, 0, width - 35, 0, "right");
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