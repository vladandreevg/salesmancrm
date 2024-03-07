<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

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

$sort .= (!empty( $user_list )) ? "cc.creator IN (".yimplode( ",", $user_list ).") and " : "cc.creator IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";


$list        = $groups = $users = $stat = $relation = $path = [];
$chartColors = '';

$q = "
	SELECT 
		DISTINCT(cc.clid),
		cc.title,
		cc.date_create as dcreate,
		DATE_FORMAT(cc.date_create, '%Y-%m-%d') as dacreate,
		cc.creator,
		cc.iduser,
		cc.tip_cmr as relation,
		us.title as user,
		cat.title as category,
		cp.name as path,
		rl.color as color
	FROM {$sqlname}clientcat `cc`
		LEFT JOIN {$sqlname}user `us` ON cc.creator = us.iduser
		LEFT JOIN {$sqlname}clientpath `cp` ON cp.id = cc.clientpath
		LEFT JOIN {$sqlname}relations `rl` ON rl.title = cc.tip_cmr
		LEFT JOIN {$sqlname}category `cat` ON cat.idcategory = cc.idcategory
	WHERE 
		DATE(cc.date_create) >= '$da1' and DATE(cc.date_create) <= '$da2' and 
		$sort
		cc.identity = '$identity'
	ORDER BY cc.date_create
	";

$result = $db -> query( $q );
while ($data = $db -> fetch( $result )) {

	if ( $data['creator'] == NULL || $data['creator'] == "0" ) {
		$data['creator'] = 0;
		$data['user']    = 'Без ответственного';
	}

	//формируем справочники групп и пользователей
	$users[ $data['creator'] ] = $data['user'];

	if ( $data['relation'] == '' ) {
		$data['relation'] = 'Не задан';
		$data['color']    = 'gray';
	}
	if ( $data['path'] == '' ) {
		$data['path'] = 'Не задан';
	}

	$deals = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol) as summa FROM {$sqlname}dogovor WHERE clid = '".$data['clid']."' and did > 0 and datum BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' and identity = '$identity'" );

	$list[ $data['creator'] ][] = [
		"clid"     => $data['clid'],
		"client"   => $data['title'],
		"dcreate"  => $data['dacreate'],
		"path"     => $data['path'],
		"relation" => $data['relation'],
		"color"    => $data['color'],
		"deals"    => $deals['count'],
		"summa"    => $deals['summa'],
		"category" => $data['category'],
		"user"     => current_user( $data['iduser'] )
	];

	if ( $diffPeriod < 60 ) {

		++$stat[$data['dacreate']];
		$statd[$data['dacreate']] += $deals['count'];

	}
	else {

		$d           = getMonth( $data['dacreate'] ).".".get_year( $data['dacreate'] );
		++$stat[$d];
		$statd[$d] += $deals['count'];

	}

	++$relation[$data['relation']];
	++$path[$data['path']];

	$chartColors .= "myChart.assignColor(\"".$data['relation']."\", \"".$data['color']."\");\n";

}

$dday = $newClientsCount = [];
$i    = 0;

/*
 * данные для графика
 */
ksort( $stat );

/*
 * Формируем диаграмму Динамика
 */

$statChart = $statChart2 = [];

if ( $diffPeriod < 60 ) {

	$da1_array = explode( "-", $da1 );
	$da2_array = explode( "-", $da2 );

	$dstart = mktime( 0, 0, 0, $da1_array[1], $da1_array[2], $da1_array[0] );
	$dend   = mktime( 23, 59, 59, $da2_array[1], $da2_array[2], $da2_array[0] );

	$step = 86400;
	$day  = (int)( ( $dend - $dstart ) / $step ) + 1;

	$dat = $dstart;//стартовое значение даты

	for ( $d = 0; $d < $day; $d++ ) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date( 'Y-m-d', $dat );

		$statChart[]  = '{"Дата":"'.$datum.'", "Тип":"Клиенты", "Кол-во":"'.($stat[ $datum ] + 0).'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$statChart2[] = '{"Дата":"'.$datum.'", "Тип":"Сделки", "Кол-во":"'.($statd[ $datum ] + 0).'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$order[]      = '"'.date( "d.m", strtotime( $datum ) ).'"';

	}

}
else {

	$monStart  = (int)getMonth( $da1 );
	$yearStart = (int)get_year( $da1 ) ;

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
			$statChart[]  = '{"Дата":"'.$datum.'", "Тип":"Клиенты", "Кол-во":"'.($stat[ $date ] + 0).'","День":"'.$date.'"}';
			$statChart2[] = '{"Дата":"'.$datum.'", "Тип":"Сделки", "Кол-во":"'.($statd[ $date ] + 0).'","День":"'.$date.'"}';
			$order[]      = '"'.$date.'"';

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

$statChart  = implode( ",", $statChart );
$statChart2 = implode( ",", $statChart2 );
$order      = implode( ",", $order );

/*
 * Данные для чарта по Типу отношений
 */
$relChart = [];
foreach ( $relation as $name => $count ) {

	$relChart[] = '{"Тип":"'.$name.'","Кол-во":"'.$count.'"}';

}
$relChart = implode( ",", $relChart );

/*
 * Данные для чарта по Источнику
 */
$pathChart = [];
foreach ( $path as $name => $count ) {

	$pathChart[] = '{"Источник":"'.$name.'","Кол-во":"'.$count.'"}';

}
$pathChart = implode( ",", $pathChart );
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

<br>
<div class="zagolovok_rep div-center">
	<span class="fs-12 uppercase">Новые клиенты</span><br>
	<span class="gray2 em fs-07 noBold pt5">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></span>
</div>
<hr>

<div class="row">

	<div class="column12 grid-6">
		<div class="gray2 Bold fs-10">Динамика</div>
		<div id="bars" class="div-center margbot10 margtop10"></div>
	</div>
	<div class="column12 grid-3">
		<div class="gray2 Bold fs-10 mr10">Тип отношений</div>
		<div id="chart1" style="padding:5px; height: 100%;"></div>
	</div>
	<div class="column12 grid-3">
		<div class="gray2 Bold fs-10 mr10">Источники</div>
		<div id="chart2" style="padding:5px; height: 100%;"></div>
	</div>

</div>

<table id="zebra">
	<thead>
	<TR class="header_contaner">
		<th class="w30"></th>
		<th class="w350"><B>Автор</B></th>
		<th class="w100"><b>Дата (в базе)</b></th>
		<th class="w160"><b><?=$fieldsNames['client']['tip_cmr']  ?? "Отключено"?></b></th>
		<th class="w120"><b><?=$fieldsNames['client']['clientpath']  ?? "Отключено"?></b></th>
		<th class="w120"><b><?=$fieldsNames['client']['idcategory'] ?? "Отключено"?></b></th>
		<th class="w60"><b>Сделки, шт.</b></th>
		<th class="w120"><b>Сделки, <?= $valuta ?></b></th>
		<th class="w100"><b>Ответственный</b></th>
		<th></th>
	</TR>
	</thead>
	<tbody>
	<?php
	$uList    = '';
	$uCount   = 0;
	$allCount = 0;
	$num      = 0;
	foreach ( $list as $user => $clients ) {

		$nums   = 1;
		$clList = '';

		foreach ( $clients as $i => $client ) {

			$class  = ($client['deals'] == 0) ? "gray" : "";
			$class2 = ($client['summa'] == 0) ? "gray" : "";

			$clList .= '
			<TR class="ha client" data-key="'.$user.'-'.$group.'" data-date="'.$client['dcreate'].'">
				<TD class="text-right">#'.$nums.'&nbsp;</TD>
				<TD>
					<div class="ellipsis fs-11 Bold" title="'.$client['client'].'"><a href="#" onclick="viewClient(\''.$client['clid'].'\')" title="Просмотр"><i class="icon-building broun"></i>&nbsp;'.$client['client'].'</a></div>
				</TD>
				<TD class="text-center">'.get_date( $client['dcreate'] ).'</TD>
				<TD><div class="ellipsis" title="'.$client['relation'].'"><div class="bullet-mini mr5" style="background: '.$client['color'].'"></div>'.$client['relation'].'</div></TD>
				<TD><div class="ellipsis" title="'.$client['path'].'">'.$client['path'].'</div></TD>
				<TD><div class="ellipsis" title="'.$client['category'].'">'.$client['category'].'</div></TD>
				<TD class="text-center"><div class="'.$class.'" title="'.$client['deals'].'">'.$client['deals'].'</div></TD>
				<TD class="text-right"><div class="'.$class2.'" title="'.num_format( $client['summa'] ).'">'.num_format( $client['summa'] ).'</div></TD>
				<TD><div class="ellipsis" title="'.$client['user'].'">'.$client['user'].'</div></TD>
				<TD></TD>
			</TR>';

			$nums++;

		}

		$numbers1 = array_map( static function($details) {
			return $details['dcreate'];
		}, $clients );

		$max1 = min( $numbers1 );

		$num++;

		$allCount += count( $clients );

		print '
		<TR class="ha user greenbg-sub" data-user="'.$user.'">
			<TD colspan="2">
				<DIV title="'.strtr( $user, $users ).'" class="Bold fs-11"><i class="icon-plus-circled green us hidden"></i>&nbsp;'.strtr( $user, $users ).'</DIV>
			</TD>
			<TD class="text-center"><span class="Bold fs-11">'.count( $clients ).'</span></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
		</TR>';

		print $clList;

	}
	?>
	</tbody>
	<TFOOT>
	<TR class="orangebg-sub" style="background: #ccc">
		<TD class="text-center">&nbsp;</TD>
		<TD class="text-left"><B>ИТОГО</B></TD>
		<TD class="text-center"><B><?= $allCount ?></B></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
	</TR>
	</TFOOT>
</TABLE>

<div style="height: 80px;"></div>

<!--<script src="/assets/js/d3.min.js"></script>-->
<script src="/assets/js/dimple.js/dimple.min.js"></script>
<script>

	$(function () {

		drawChart0();
		drawChart1();
		drawChart2();

		$('#bars').on('sparklineClick', function (ev) {

			var sparkline = ev.sparklines[0];
			var region = sparkline.getCurrentRegionFields();
			var day = days[region[0].offset];

			$('.client').addClass('hidden');
			$('.group').addClass('hidden');

			$('.client[data-date="' + day + '"]').each(function () {

				var key = $(this).data('key');

				$(this).removeClass('hidden');
				$('.group[data-key="' + key + '"]').removeClass('hidden');

			});

		});

	});

	function drawChart0() {

		var width = $('#bars').width() - 40;
		var height = 400;
		var svg = dimple.newSvg("#bars", width, height);
		var data2 = [<?=$statChart?>];

		var myChart2 = new dimple.chart(svg, data2);

		myChart2.setBounds(100, 0, width - 50, height - 100);
		myChart2.assignColor("Клиенты", "#EF5350");
		myChart2.assignColor("Сделки", "#64B5F6");

		var x = myChart2.addCategoryAxis("x", "День", "%d-%m-%Y", "%d.%m");
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		x.showGridlines = false;

		var y = myChart2.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии
		y.tickFormat = ".f";
		//y.ticks = 2;//шаг шкалы по оси y

		var s = myChart2.addSeries(["Тип"], dimple.plot.area);
		s.lineWeight = 0;
		s.lineMarkers = false;
		s.stacked = false;
		var s1 = myChart2.addSeries(["Тип"], dimple.plot.area);
		s1.data = [<?=$statChart2?>];
		s1.lineWeight = 0;

		var myLegend2 = myChart2.addLegend(0, 0, width - 35, 0, "right");
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
		var data = [<?=$relChart?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width, height);

		<?=$chartColors?>

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
		var data = [<?=$pathChart?>];

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
		var ring = myChart.addSeries("Источник", dimple.plot.pie);

		ring.innerRadius = "50%";

		myChart.setMargins(0, 50, 50, 5);
		myChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			myChart.draw(0, true);
		});

	}


</script>