<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
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

$voronkaInterval = $_COOKIE['voronkaInterval'];

$y   = date( 'Y' );
$m   = date( 'm' );
$nd  = date( 'd' );
$st  = mktime( 0, 0, 0, $m + 1, 0, $y ); //сформировали дату для дальнейшей обработки - первый день месяца $m года $y
$dd  = (int)date( "t", $st ); //получили Стоимость дней в месяце
$d11 = strftime( '%d.%m', mktime( 0, 0, 0, $m, '01', $y ) );
$d12 = strftime( '%d.%m', mktime( 0, 0, 0, $m, $dd, $y ) );

$d1 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m, '01', $y ) );
$d2 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m, $dd, $y ) );

if ( $voronkaInterval == '' ) {
	$per    = "datum_plan BETWEEN '".$d1." 00:00:00' AND '".$d2." 23:59:59' AND ";
	$vorPer = "Период: <b>".$d11." &divide; ".$d12."<b>";
}


$voronka = [];
$sort    = get_people( $iduser1 );

$result = $db -> getAll( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
foreach ( $result as $data ) {

	$summa = $db -> getOne( "SELECT SUM(kol) as summa FROM {$sqlname}dogovor WHERE $per COALESCE(close, 'no') != 'yes' AND idcategory='".$data['idcategory']."' ".$sort." AND identity = '$identity'" );

	if ( $summa < 0.01 )
		$summa = 0;

	$ves = $data['title'] * $summa / 100;

	$voronka[]  = '{"Тип":"Аболютная","Этап":"'.$data['title'].'%","Сумма, '.$valuta.'":"'.$summa.'"}';
	$voronkaf[] = '{"Тип":"Взвешенная","Этап":"'.$data['title'].'%","Сумма, '.$valuta.'":"'.$ves.'"}';

	$order[] = '"'.$data['title'].'%"';
}

$summa = $db -> getOne( "SELECT SUM(kol_fact) as summa FROM {$sqlname}dogovor WHERE datum_close BETWEEN '".$d1." 00:00:00' AND '".$d2." 23:59:59' AND COALESCE(close, 'no') = 'yes' ".$sort." and identity = '$identity'" );

if ( $summa < 0.01 )
	$summa = 0;
$ves = $summa;

$voronka[]  = '{"Тип":"Аболютная","Этап":"Закрыто","Сумма, '.$valuta.'":"'.$summa.'"}';
$voronkaf[] = '{"Тип":"Взвешенная","Этап":"Закрыто","Сумма, '.$valuta.'":"'.$summa.'"}';

$order[] = '"Закрыто"';

//print_r($voronka);

$datas  = implode( ",", $voronka );
$datasf = implode( ",", $voronkaf );
$order  = implode( ",", $order );

//print $datas;
?>
<style>
	.dimple-custom-series-line {
		stroke-width     : 1;
		stroke-dasharray : 5;
	}
	.dimple-custom-axis-line {
		stroke       : #CFD8DC !important;
		stroke-width : 1.1;
	}
	.dimple-custom-gridline {
		stroke-width     : 1;
		stroke-dasharray : 2;
		fill             : none;
		stroke           : #CFD8DC !important;
	}
</style>
<div class="pull-left"><?= $vorPer ?>&nbsp;</div>
<div id="chartVoronkaDesktop"></div>
<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
<script>

	drowVoronkaChart();

	function drowVoronkaChart() {

		$('#chartVoronkaDesktop').empty();

		var width = $('#voronka').closest('.viget').width() - 0;
		var height = $('#voronka').closest('.viget').height() - 35;
		var svg = dimple.newSvg("#chartVoronkaDesktop", width, height);
		var data = [<?=$datas?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(30, 40, width - 40, height - 50);

		var x = myChart.addCategoryAxis("x", ["Этап"]);
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует

		var y = myChart.addMeasureAxis("y", "Сумма, <?=$valuta?>", null);
		y.showGridlines = true;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		myChart.assignColor("Аболютная", "#B0BEC5", "#CFD8DC"); // <------- ASSIGN COLOR HERE
		myChart.assignColor("Взвешенная", "#B71C1C", "rgba(173,20,87 ,1.1)"); // <------- ASSIGN COLOR HERE

		var s = myChart.addSeries(["Тип"], dimple.plot.bar, null);
		var s1 = myChart.addSeries(["Тип"], dimple.plot.line);
		s1.data = [<?=$datasf?>];
		s.barGap = 0.40;//

		myChart.addLegend(5, 0, width, 0, "right");
		myChart.setMargins(40, 20, 20, 70);
		myChart.floatingBarWidth = 10;

		/*myChart.customClassList = {
			axisLine: "dotted-line"
		};*/

		s.addEventHandler("click", function (e) {
			showDataa(e.xValue);
			//console.log(e.xValue); // Log the brand of the clicked bubble
		});

		myChart.draw(1000);
		y.tickFormat = ",.2f";

		svg.selectAll(".dimple-marker,.dimple-marker-back").attr("r", 2);

		y.titleShape.remove();
		x.titleShape.remove();

	}

	function changeVorPer() {

		var current = getCookie('voronkaInterval');
		var newparam = (current === 'all') ? '' : 'all';

		setCookie('voronkaInterval', newparam, {expires: 31536000});

		$("#voronka").load("/content/vigets/viget.voronka.php").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');
		$('#vperiod').append('<?=$vorPer?>');

	}

	$(window).on('resizeEnd', function () {

		drowVoronkaChart();

	});

	function showDataa(step) {

		doLoad('/content/vigets/viget.dataview.php?action=stepView&stepName=' + step);

	}
</script>