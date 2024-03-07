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

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list = (array)$_REQUEST['user_list'];

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if ( !empty( $user_list ) ) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

$all_d       = 0;
$all_kol     = 0;
$all_kol_f   = 0;
$all_marga_f = 0;
$i           = 0;

$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogstatus WHERE identity = '$identity' ORDER BY title" );
foreach ( $result as $data ) {

	$tip = $data['title'];

	$s = ( $data['sid'] > 0 ) ? " deal.sid = '".$data['sid']."' and " : '';

	$result6 = $db -> getRow( "
		SELECT 
		    COUNT(deal.did) as count, 
		    SUM(deal.kol) as kol, 
		    SUM(deal.kol_fact) as kol_fact, 
		    SUM(deal.marga) as marga 
		FROM ".$sqlname."dogovor `deal`
		WHERE 
			deal.datum_close between '".$da1."' and '".$da2."' and  
			$s
			$sort
			deal.identity = '$identity'" );
	$cl_d    = $result6['count'];
	$kol     = $result6['kol'];
	$kol_f   = $result6['kol_fact'];
	$marga_f = $result6['marga'];

	$all_d       = $all_d + $cl_d;
	$all_kol     = $all_kol + $kol;
	$all_kol_f   = $all_kol_f + $kol_f;
	$all_marga_f = $all_marga_f + $marga_f;

	$effect[ $i ] = [
		"tip"        => $tip,
		"close_dogs" => $cl_d,
		"kol_plan"   => $kol,
		"kol_fact"   => $kol_f,
		"marga"      => $marga_f
	];

	$j = $i + 1;

	$datas[] = '{"Статус":"Статус #'.$j.'", "Кол-во":"'.$cl_d.'"}';

	$order[] = "'Статус #".$j."'";

	$i       = $i + 1;
	$cl_d    = 0;
	$kol     = 0;
	$kol_f   = 0;
	$marga_f = 0;
}
$count = count( $datas );
$datas = implode( ",", $datas );
$order = implode( ",", $order );
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
<br/>
<div class="zagolovok_rep" align="center">
	<b>Анализ закрытых сделок за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b>:
</div>
<hr>
<?php if ( $count > 0 ) { ?>
	<div id="graf" style="display:block; height:350px">
		<div id="chart" style="padding:5px; height:100%"></div>
		<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
		<script>

			var width = $('#contentdiv').width() - 40;
			var height = 350;
			var svg = dimple.newSvg("#chart", "100%", "100%");
			var data = [<?=$datas?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(0, 0, width - 50, height - 40);

			var x = myChart.addCategoryAxis("x", ["Статус"]);
			x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart.addMeasureAxis("y", "Кол-во");
			y.showGridlines = true;//скрываем линии
			//myChart.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			myChart.defaultColors = [
				new dimple.color("#2196F3", "#2196F3"),
				new dimple.color("#F44336", "#F44336"),
				new dimple.color("#4CAF50", "#4CAF50"),
				new dimple.color("#795548", "#795548"),
				new dimple.color("#FF9800", "#FF9800"),
				new dimple.color("#673AB7", "#673AB7"),
				new dimple.color("#3F51B5", "#3F51B5"),
				new dimple.color("#FFEB3B", "#FFEB3B"),
				new dimple.color("#E91E63", "#E91E63"),
				new dimple.color("#A1887F", "#A1887F"),
				new dimple.color("#FFC107", "#FFC107"),
				new dimple.color("#8BC34A", "#8BC34A"),
				new dimple.color("#00BCD4", "#00BCD4"),
				new dimple.color("#7CB342", "#7CB342"),
				new dimple.color("#0097A7", "#0097A7"),
				new dimple.color("#D500F9", "#D500F9"),
				new dimple.color("#76FF03", "#76FF03"),
				new dimple.color("#DD2C00", "#DD2C00"),
				new dimple.color("#B0BEC5", "#B0BEC5"),
				new dimple.color("#90A4AE", "#90A4AE"),
				new dimple.color("#78909C", "#78909C"),
				new dimple.color("#03A9F4", "#03A9F4"),
				new dimple.color("#607D8B", "#607D8B"),
				new dimple.color("#9E9E9E", "#9E9E9E"),
				new dimple.color("#6D4C41", "#6D4C41")
			];

			var s = myChart.addSeries(["Статус"], dimple.plot.bar);
			//s.lineWeight = 2;
			//s.lineMarkers = true;
			s.stacked = true;
			//s.barGap = 5;

			//myChart.barGap = 0.5;
			var myLegend = myChart.addLegend(width - 100, 0, 100, 250, "right");
			myChart.setMargins(100, 20, 140, 35);

			//myChart.assignColor("План","green");

			myChart.draw(1000);

			/*s.shapes.each(function(d) {

				var shape = d3.select(this),

				height = myChart.y + myChart.height - y._scale(d.height);
				width = x._scale(d.width);

				svg.append("text")

				.attr("x", parseFloat(shape.attr("x")) + parseFloat(shape.attr("width"))/2)
				.attr("y", y._scale(d.height)+20)

				.style("text-anchor", "middle")
				.style("font-size", "12px")
				.style("font-weight", "bold")
				.style("font-family", "sans-serif")

				.style("opacity", 0.7)

				.text(d3.format(",")(d.yValue));
			});*/

			myChart.legends = [];

			var filterValues = dimple.getUniqueValues(data, "Статус");
			myLegend.shapes.selectAll("rect").on("click", function (e) {
				var hide = false;
				var newFilters = [];
				filterValues.forEach(function (f) {
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
				y.tickFormat = ".2f";
				filterValues = newFilters;
				myChart.data = dimple.filterData(data, "Статус", filterValues);
				myChart.draw(800);
			});

			y.tickFormat = ".2f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			$(window).bind('resizeEnd', function () {
				myChart.draw(0, true);
			});

		</script>
	</div>
	<hr>
<?php } ?>
<table width="90%" border="0" align="center" cellpadding="5" cellspacing="0">
	<thead>
	<TR height="40">
		<th width="90" align="center"><b>#</b></th>
		<th><B>Причины закрытия</B></th>
		<th width="60" align="center"><B>Кол-во</B></th>
		<th width="150" align="center"><b>Сумма, <?= $valuta ?></b></th>
		<th width="100" align="center"><b>Маржа, <?= $valuta ?></b></th>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	for ( $j = 0; $j < $i; $j++ ) {
		?>
		<TR height="40" class="ha">
			<TD align="center">Статус #<?= $j + 1 ?></TD>
			<TD>
				<DIV class="ellipsis" title="<?= $effect[ $j ]['tip'] ?>"><?= $effect[ $j ]['tip'] ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $effect[ $j ]['close_dogs'] ?>"><?= $effect[ $j ]['close_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= num_format( $effect[ $j ]['kol_fact'] ) ?>"><?= num_format( $effect[ $j ]['kol_fact'] ) ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= num_format( $effect[ $j ]['marga'] ) ?>"><?= num_format( $effect[ $j ]['marga'] ) ?></DIV>
			</TD>
		</TR>
		<?php
	}

	$all_kol     = num_format( $all_kol );
	$all_kol_f   = num_format( $all_kol_f );
	$all_marga_f = num_format( $all_marga_f );
	?>
	<TR bgcolor="#FC9" height="35">
		<TD align="center">&nbsp;</TD>
		<TD align="right"><B>ИТОГО</B></TD>
		<TD align="center"><B><?= $all_d ?></B></TD>
		<TD align="right" nowrap><B><?= $all_kol_f ?></B></TD>
		<TD align="right" nowrap><B><?= $all_marga_f ?></B></TD>
	</TR>
	</TBODY>
</TABLE>
<div style="height:90px"></div>