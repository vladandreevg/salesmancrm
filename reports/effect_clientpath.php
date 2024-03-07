<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if (!$per) {
	$per = 'nedelya';
}

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort = $clist = $plist = '';

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= " cc.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$sort .= " cc.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

$year = $_REQUEST['year'];
if ($year == "") $year = date('Y');

$y1 = $year + 1;
$y2 = $year - 1;

$datas = $order = [];

$result = $db -> getAll("SELECT id, name FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name");
foreach ($result as $data) {

	for ($m = 1; $m <= 12; $m++) {

		if ($m < 10) $mon = '0'.$m;
		else $mon = $m;

		$num[ $m ] = $db -> getOne("
			SELECT COUNT(cc.clid) as count 
			FROM ".$sqlname."clientcat `cc`
			WHERE 
				DATE_FORMAT(cc.date_create, '%Y-%c') = '".$year."-".$m."' and 
				cc.clientpath = '".$data['id']."' and 
				$sort
				cc.identity = '$identity'
			");

		if ($num[ $m ] > 0) {
			$datas[] = '{"Источник":"'.$data['name'].'","Месяц":"'.ru_month( $m ).'","Число":"'.$num[ $m ].'"}';
		}

	}

}

for ($m = 1; $m <= 12; $m++) {
	if ($m < 10) $mon = '0'.$m;
	else $mon = $m;
	$order[] = '"'.ru_month($mon).'"';
}

//print_r($datas);
//print_r($path);
$count = count($datas);
$datas = implode(",", $datas);
$order = implode(",", $order);
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

	table.borderer thead tr th,
	table.borderer tr,
	table.borderer td {
		border-left   : 1px dotted #ccc !important;
		border-bottom : 1px dotted #ccc !important;
		padding       : 2px 3px 2px 3px;
	}

	table.borderer thead th:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead tr:first-child th {
		border-top : 1px dotted #ccc !important;
	}

	table.borderer td:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead {
		border : 1px dotted #ccc !important;
	}

	table.borderer thead td,
	table.borderer thead th {
		background : #E5F0F9;
	}

	table.borderer thead th {
		border-bottom : 1px dotted #666 !important;
	}

	table.borderer thead {
		border : 1px dotted #222 !important;
	}
</style>

<DIV style="float:right; position:absolute;top:10px;right:5px; z-index:1001" class="noprint">
	[<A href="javascript:void(0)" onClick="refresh('contentdiv','reports/effect_clientpath.php?year=<?= $y2 ?>');">&#8249;&#8212;<?= $y2 ?></A>&nbsp;|&nbsp;<SPAN class="date2"><?= $year ?></SPAN>&nbsp;|&nbsp;<A href="javascript:void(0)" onClick="refresh('contentdiv','reports/effect_clientpath.php?year=<?= $y1 ?>');"><?= $y1 ?>&#8212;&#8250;</A>]&nbsp;&nbsp;
</DIV>

<div class="zagolovok_rep" align="center"><h1>Анализ <b class="red">источников клиентов</b> за <b><?= $year ?></b> год
	</h1></div>

<hr>

<?php if ($count > 0) { ?>
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

			var x = myChart.addCategoryAxis("x", ["Месяц"]);
			x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart.addMeasureAxis("y", "Число");
			y.showGridlines = true;//скрываем линии
			//myChart.floatingBarWidth = 10;
			y.ticks = 10;//шаг шкалы по оси y

			myChart.defaultColors = [
				new dimple.color("#2196F3", "#2196F3"),
				new dimple.color("#f44336", "#f44336"),
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

			var s = myChart.addSeries(["Источник"], dimple.plot.bar);
			//s.lineWeight = 2;
			//s.lineMarkers = true;
			s.stacked = true;
			//s.barGap = 5;

			//myChart.barGap = 0.5;
			var myLegend = myChart.addLegend(width - 200, 0, 200, 250, "right");
			myChart.setMargins(50, 20, 240, 35);

			//myChart.assignColor("План","green");

			myChart.draw(1000);

			myChart.legends = [];

			var filterValues = dimple.getUniqueValues(data, "Источник");
			myLegend.shapes.selectAll("rect").on("click", function (e) {
				var hide = false;
				var newFilters = [];
				filterValues.forEach(function (f) {
					if (f === e.aggField.slice(-1)[0]) {
						hide = true;
					} else {
						newFilters.push(f);
					}
				});
				if (hide) {
					d3.select(this).style("opacity", 0.2);
				} else {
					newFilters.push(e.aggField.slice(-1)[0]);
					d3.select(this).style("opacity", 0.8);
				}
				y.tickFormat = ".f";
				filterValues = newFilters;
				myChart.data = dimple.filterData(data, "Источник", filterValues);
				myChart.draw(800);
			});

			y.tickFormat = ".f";
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
<table width="99%" border="0" align="center" cellpadding="5" cellspacing="0" id="zebra" class="borderer">
	<thead>
	<TR height="40">
		<th width="25" align="center"><b>#</b></th>
		<th align="center"><B>Источник клиента</B></th>
		<?php
		for ($m = 1; $m <= 12; $m++) {
			if ($m < 10) $mon = '0'.$m;
			?>
			<th width="60" align="center"><b><?= ru_month($m) ?>.</b></th>
		<?php } ?>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	$k      = 0;
	$result = $db -> getAll("SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name");
	foreach ($result as $data) {

		$k++;
		?>
		<TR class="ha">
			<TD align="left"> #<?= $k ?></TD>
			<TD>
				<DIV class="ellipsis" title="<?= $data['name'] ?>"><?= $data['name'] ?></DIV>
			</TD>
			<?php
			for ($m = 1; $m <= 12; $m++) {

				if ($m < 10) $mon = '0'.$m;
				else $mon = $m;

				$num[ $m ] = $db -> getOne("
					SELECT COUNT(cc.clid) as count 
					FROM ".$sqlname."clientcat `cc`
					WHERE 
						DATE_FORMAT(cc.date_create, '%Y-%m') = '".$year."-".$mon."' and 
						cc.clientpath = '".$data['id']."' and 
						$sort
						cc.identity = '$identity'
					");

				$res2      = $db -> getOne("
					SELECT COUNT(pp.pid) as count 
					FROM ".$sqlname."personcat `pp`
					WHERE 
						DATE_FORMAT(pp.date_create, '%Y-%m') = '".$year."-".$mon."' and 
						pp.clientpath = '".$data['id']."' and  
						".str_replace("cc", "pp", $sort)."
						pp.identity = '$identity'
					");
				$num[ $m ] += $res2;

				$total[ $m ] += $num[ $m ];

				if ($num[ $m ] == 0) $num[ $m ] = '';
				?>
				<TD align="center"><?= $num[ $m ] ?></TD>
			<?php } ?>
		</TR>
		<?php
	}
	?>
	<TR bgcolor="#FC9">
		<TD align="center">&nbsp;</TD>
		<TD align="right"><B>ИТОГО</B></TD>
		<?php
		for ($m = 1; $m <= 12; $m++) {
			?>
			<TD align="center"><B><?= $total[ $m ] ?></B></TD>
		<?php } ?>
	</TR>
	</TBODY>
</TABLE>

<div style="height: 80px;"></div>