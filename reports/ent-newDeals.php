<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

use Salesman\Guides;

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

//todo: Добавить вывод напоминаний и записей истории активностей

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per ) {
	$per = 'nedelya';
}

$users       = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];
$period      = $_REQUEST['period'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort       = '';
$diffPeriod = abs( diffDate2( $da1, $da2 ) );

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$mycomps = Guides ::myComps();

if ( $mc > 0 ) {
	$sort .= "deal.mcid = '$mc' and ";
}

$sort .= (!empty( $users )) ? "deal.iduser IN (".yimplode( ",", $users ).") and " : "deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";

$list        = $users = $stat = $direction = $step = [];
$chartColors = '';

$users = $db -> getIndCol("iduser", "SELECT iduser, title FROM {$sqlname}user");

$q = "
	SELECT 
		DISTINCT(deal.did),
		deal.title,
		deal.datum as dcreate,
		DATE_FORMAT(deal.datum, '%Y-%m-%d') as dacreate,
		deal.autor as creator,
		deal.kol as summa,
		deal.marga,
		deal.close,
		deal.iduser,
		deal.clid,
		deal.idcategory,
		dc.title as step,
		cc.tip_cmr as relation,
		us.title as user,
		cc.title as client,
		dr.title as direction
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}user `us` ON us.iduser = deal.autor
		LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = deal.clid
		LEFT JOIN {$sqlname}direction `dr` ON dr.id = deal.direction
		LEFT JOIN {$sqlname}dogcategory `dc` ON dc.idcategory = deal.idcategory
	WHERE 
		deal.datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and 
		$sort
		deal.identity = '$identity'
	ORDER BY deal.datum
	";

$result = $db -> query( $q );
while ($data = $db -> fetch( $result )) {

	if ( $data['creator'] == NULL || $data['creator'] == "0" ) {

		$data['creator'] = 0;
		$data['user']    = 'Без ответственного';

	}

	if ( $data['direction'] == '' )
		$data['direction'] = 'Не задан';

	if ( $data['path'] == '' )
		$data['path'] = 'Не задан';


	$list[ $data['creator'] ][] = [
		"did"       => $data['did'],
		"deal"      => $data['title'],
		"clid"      => $data['clid'],
		"client"    => $data['client'],
		"dcreate"   => $data['dacreate'],
		"path"      => $data['path'],
		"direction" => $data['direction'],
		"step"      => $data['step'],
		"summa"     => $data['summa'],
		"close"     => $data['close'],
		"user"      => current_user( $data['iduser'] )
	];

	if ( $diffPeriod < 60 ) {
		$stat[$data['dacreate']]++;
	}
	else {

		$d          = getMonth( $data['dacreate'] ).".".get_year( $data['dacreate'] );
		++$stat[$d];

	}

	$direction[ $data['direction'] ]++;
	$step[ $data['step'] ]++;

}


$dday = $newClientsCount = [];
$i    = 0;

/*
 * данные для графика
 */
ksort( $stat );

$statChart = [];
if ( $diffPeriod < 60 ) {

	$da1_array = explode( "-", $da1 );
	$da2_array = explode( "-", $da2 );

	$dstart = mktime( 0, 0, 0, $da1_array[1], $da1_array[2], $da1_array[0] );
	$dend   = mktime( 23, 59, 59, $da2_array[1], $da2_array[2], $da2_array[0] );

	$steps = 86400;
	$day   = (int)( ( $dend - $dstart ) / $steps ) + 1;

	$dat = $dstart;//стартовое значение даты

	for ( $d = 0; $d < $day; $d++ ) {

		$dat   = $dstart + $d * $steps;//дата в unix-формате
		$datum = date( 'Y-m-d', $dat );

		$statChart[] = '{"Дата":"'.$datum.'", "Тип":"Новые", "Кол-во":"'.($stat[ $datum ] + 0).'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$order[]     = '"'.date( "d.m", strtotime( $datum ) ).'"';

	}

}
else {

	$monStart  = (int)getMonth( $da1 );
	$yearStart = (int)get_year( $da1 );

	$monEnd  = (int)getMonth( $da2 );
	$yearEnd = (int)get_year( $da2 );

	$mon  = $monStart;
	$year = $yearStart;

	while ($year <= $yearEnd) {

		while ($mon <= 12) {

			$mon1 = ($mon < 10) ? "0".$mon : $mon;

			$datum = $year."-".$mon1;
			$date  = $mon1.".".$year;

			//
			$statChart[] = '{"Дата":"'.$datum.'", "Тип":"Новые", "Кол-во":"'.($stat[ $date ] + 0).'","День":"'.$date.'"}';
			$order[]     = '"'.$date.'"';

			if ( $year == $yearEnd && $mon == $monEnd )
				goto endo;

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

$statChart = implode( ",", $statChart );
$order     = implode( ",", $order );

/*
 * Данные для чарта по Типу отношений
 */
$dirChart = [];
foreach ( $direction as $name => $count ) {

	$dirChart[] = '{"Тип":"'.$name.'","Кол-во":"'.$count.'"}';

}
$dirChart = implode( ",", $dirChart );

/*
 * Данные для чарта по Источнику
 */
$stepChart = [];
foreach ( $step as $name => $count ) {

	$stepChart[] = '{"Этап":"'.$name.'%","Кол-во":"'.$count.'"}';

}
$stepChart = implode( ",", $stepChart );

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
	.dimple-custom-gridline {
		stroke-width     : 1;
		stroke-dasharray : 5;
		fill             : none;
		stroke           : #CFD8DC !important;
	}
	.dimple-custom-axis-line {
		stroke       : #CFD8DC !important;
		stroke-width : 1.1;
	}
</style>

<div class="relativ mt20 mb20 wp95 div-center">
	<h1 class="uppercase fs-14 m0 mb10">Новые сделки</h1>
	<div class="gray2">в период&nbsp;с&nbsp;<?= $da1 ?>&nbsp;по&nbsp;<?= $da2 ?></div>
</div>

<hr>

<div class="row">

	<div class="column12 grid-6">
		<div class="gray2 Bold fs-10">Динамика</div>
		<div id="bars" class="div-center margbot10 margtop10"><?= $newClients ?></div>
	</div>
	<div class="column12 grid-3">
		<div class="gray2 Bold fs-10 mr10">Направления</div>
		<div id="chart1" style="padding:5px; height: 100%;"></div>
	</div>
	<div class="column12 grid-3">
		<div class="gray2 Bold fs-10 mr10">Этап</div>
		<div id="chart2" style="padding:5px; height: 100%;"></div>
	</div>

</div>


<table id="zebra">
	<thead>
	<TR class="header_contaner">
		<th class="w30"></th>
		<th class=""><B>Клиент</B> / <B>Сделка</B></th>
		<th class="w100"><b>Дата (в базе)</b></th>
		<th class="w160"><b>Направления</b></th>
		<th class="w120"><b>Сумма, <?= $valuta ?></b></th>
		<th class="w100"><b>Этап</b></th>
		<th class="w200"><b>Ответственный</b></th>
		<th class="w100"></th>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	$uList  = '';
	$uCount = $allSumma = $allCount = $num = 0;
	foreach ( $list as $user => $deals ) {

		$nums   = 1;
		$clList = '';
		$summa  = 0;

		foreach ( $deals as $i => $deal ) {

			$dw = $deal['step'];

			if ( $deal['step'] < 20 ) {
				$pcolor = ' progress-gray';
			}
			elseif ( $deal['step'] < 60 ) {
				$pcolor = ' progress-green';
			}
			elseif ( $deal['step'] < 90 ) {
				$pcolor = ' progress-blue';
			}
			elseif ( $deal['step'] <= 100 ) {
				$pcolor = ' progress-red';
			}

			if( (int)$deal['step'] > 100 ){
				$dw = 100;
			}

			$clList .= '
			<TR class="ha client hidden1" data-key="'.$user.'-'.$group.'" data-date="'.$deal['dcreate'].'">
				<TD class="text-right">#'.$nums.'&nbsp;</TD>
				<TD>
					<div class="ellipsis fs-11 Bold" title="'.$deal['deal'].'"><a href="#" onclick="viewDogovor(\''.$deal['did'].'\')" title="Карточка"><i class="icon-briefcase-1 blue"></i>&nbsp;'.$deal['deal'].'</a></div><br>
					<div class="ellipsis fs-10" title="'.$deal['client'].'"><a href="#" onclick="viewClient(\''.$deal['clid'].'\')" title="Карточка" class="gray"><i class="icon-building broun"></i>&nbsp;'.$deal['client'].'</a></div>
				</TD>
				<TD class="text-center">'.get_date( $deal['dcreate'] ).'</TD>
				<TD><div class="ellipsis" title="'.$deal['direction'].'">'.$deal['direction'].'</div></TD>
				<TD class="text-right"><div title="'.num_format( $deal['summa'] ).'">'.num_format( $deal['summa'] ).'</div></TD>
				<TD>
					<DIV class="progressbarr">'.$deal['step'].'%<DIV id="test" class="progressbar-completed '.$pcolor.'" style="width:'.$dw.'%;" title="'.$deal['step'].'%"></DIV></DIV>
				</TD>
				<TD><div class="ellipsis" title="'.$deal['user'].'">'.$deal['user'].'</div></TD>
				<TD></TD>
			</TR>';

			$nums++;

			$summa += $deal['summa'];

		}

		$numbers1 = array_map( static function($details) {
			return $details['dcreate'];
		}, $deals );

		$max1 = min( $numbers1 );

		$num++;

		$allCount += count( $deals );
		$allSumma += $summa;

		print '
		<TR class="ha user greenbg-sub" data-user="'.$user.'">
			<TD colspan="2">
				<DIV title="'.$users[$user].'" class="Bold fs-11"><i class="icon-plus-circled green us hidden"></i>&nbsp;'.$users[$user].'</DIV>
			</TD>
			<TD class="text-center"><span class="Bold fs-11">'.count( $deals ).'</span></TD>
			<TD class="text-center"></TD>
			<TD class="text-right"><b>'.num_format( $summa ).'</b></TD>
			<TD class="text-center"></TD>
			<TD class="text-center"></TD>
			<TD></TD>
		</TR>';

		print $clList;

	}
	?>
	</TBODY>
	<TFOOT>
	<TR class="orangebg-sub" style="background: #ccc">
		<TD class="text-center">&nbsp;</TD>
		<TD class="text-left"><B>ИТОГО</B></TD>
		<TD class="text-center"><B><?= $allCount ?></B></TD>
		<TD></TD>
		<TD class="text-right"><B><?= $allSumma ?></B></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
	</TR>
	</TFOOT>
</TABLE>

<div style="height: 80px;"></div>

<script src="/assets/js/jquery.sparkline.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script src="/assets/js/dimple.js/dimple.min.js"></script>
<script>

	$(function () {

		drawChart0();
		drawChart1();
		drawChart2();

	});

	function drawChart0() {

		var width = $('#bars').width() - 60;
		var height = 400;
		var svg = dimple.newSvg("#bars", width, height);
		var data2 = [<?=$statChart?>];

		var myChart2 = new dimple.chart(svg, data2);

		myChart2.setBounds(100, 0, width - 50, height - 100);
		myChart2.assignColor("Новые", "#64B5F6");

		var x = myChart2.addCategoryAxis("x", "День", "%d-%m-%Y", "%d.%m");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		x.showGridlines = false;

		var y = myChart2.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии
		y.tickFormat = ".f";
		y.ticks = 2;//шаг шкалы по оси y

		var s = myChart2.addSeries(["Тип"], dimple.plot.area);
		s.lineWeight = 2;
		s.lineMarkers = false;
		s.stacked = true;

		//var myLegend2 = myChart2.addLegend(0, 0, width - 35, 0, "right");
		myChart2.setMargins(10, 10, 10, 100);
		myChart2.draw(1000);

		y.titleShape.remove();
		x.titleShape.remove();

		//y.tickFormat = ".f";
		s.shapes.style("opacity", function (d) {
			return (d.y === null ? 0 : 0.8);
		});

		$(window).bind('resizeEnd', function () {
			myChart2.draw(0, true);
		});

	}

	function drawChart1() {

		var width = $('#chart1').actual('width') - 10;
		var height = width;
		var svg = dimple.newSvg("#chart1", width, height);
		var data = [<?=$dirChart?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width, height);
		myChart.defaultColors = [
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#FFEB3B", "#FFEB3B"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#E91E63", "#E91E63"),
			new dimple.color("#03A9F4", "#03A9F4"),
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
			new dimple.color("#B0BEC5", "#B0BEC5"),
			new dimple.color("#90A4AE", "#90A4AE"),
			new dimple.color("#78909C", "#78909C"),
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		myChart.addMeasureAxis("p", "Кол-во");
		var ring = myChart.addSeries("Тип", dimple.plot.pie);

		ring.innerRadius = "50%";

		myChart.setMargins(0, 50, 50, 5);
		myChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			myChart.draw(0, true);
		});

	}

	function drawChart2() {

		var width = $('#chart2').actual('width') - 10;
		var height = width;
		var svg = dimple.newSvg("#chart2", width, height);
		var data = [<?=$stepChart?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width, height);
		myChart.defaultColors = [
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#FFEB3B", "#FFEB3B"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#E91E63", "#E91E63"),
			new dimple.color("#03A9F4", "#03A9F4"),
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
			new dimple.color("#B0BEC5", "#B0BEC5"),
			new dimple.color("#90A4AE", "#90A4AE"),
			new dimple.color("#78909C", "#78909C"),
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		myChart.addMeasureAxis("p", "Кол-во");
		var ring = myChart.addSeries("Этап", dimple.plot.pie);

		ring.innerRadius = "50%";

		myChart.setMargins(0, 50, 50, 5);
		myChart.draw(1000);

		$(window).on('resizeEnd', function () {
			myChart.draw(0, true);
		});

	}

</script>