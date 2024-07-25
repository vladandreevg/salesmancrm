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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$sort = '';

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

//составляем запрос по параметрам сделок
$ar = [
	'con_id',
	'partner'
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

$users = $db -> getIndCol( "iduser", "SELECT iduser, title FROM {$sqlname}user WHERE iduser IN (".yimplode( ",", $user_list ).") AND identity = '$identity'" );

$datas    = $order = $step = $list = [];
$dogs_max = $kol_max = 0;

$resultt = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
foreach ( $resultt as $data ) {

	//текущий этап
	$step[ $data['idcategory'] ] = $data['title'].'%';

	foreach ( $user_list as $iduser ) {

		//сумма сделок j-го пользователя i-го этапа
		$resultm = $db -> getRow( "
			SELECT 
				COUNT(deal.did) as count, SUM(deal.kol) as summa
			FROM ".$sqlname."dogovor `deal`
			WHERE 
				deal.did > 0 and 
				deal.datum_plan between '$da1 00:00:00' and '$da2 23:59:59' and 
				deal.idcategory = '".$data['idcategory']."' and 
				deal.iduser = '$iduser' and 
				$sort
				deal.identity = '$identity'
			" );

		$dogs = (int)$resultm['count'];
		$kol  = (double)$resultm['summa'];

		if ( $kol > 0 ) {

			$datas[] = '{Этап:"'.$data['title'].'%","Сотрудник":"'.$users[ $iduser ].'","Сумма":"'.$kol.'"}';

		}

		$list[ $data['idcategory'] ]["summa"] += $kol;
		$list[ $data['idcategory'] ]["count"] += $dogs;

		if ( $list[ $data['idcategory'] ]["count"] > $dogs_max ) {
			$dogs_max = $list[ $data['idcategory'] ]["count"];
		}
		if ( $list[ $data['idcategory'] ]["summa"] > $kol_max ) {
			$kol_max = $list[ $data['idcategory'] ]["summa"];
		}

	}

}

$count = count( $datas );
$datas = implode( ",", $datas );
$order = yimplode( ",", array_values( $step ), '"' );
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
	.td--main {
		height    : 45px;
		cursor    : pointer;
		font-size : 1.25rem;
	}
	.color1 {
		background : rgba(255, 236, 179, .9);
	}
	.color2 {
		background : rgba(255, 249, 196, .9);
	}
	.td--main:hover {
		background : rgba(197, 225, 165, 1);
	}
	.td--sub {

	}
	tfoot {
		height      : 40px;
		background  : rgba(207, 216, 220, 1);
		font-weight : 700;
		font-size   : 1.4rem;
	}
	@media print {
		.fixAddBotButton {
			display : none;
		}
	}
	-->
</style>

<DIV class="zagolovok_rep text-center">
	<b>Воронка продаж&nbsp;с&nbsp;<span class="red"><?= format_date_rus( $da1 ) ?></span>&nbsp;по&nbsp;<span class="red"><?= format_date_rus( $da2 ) ?></span></b>:
</DIV>
<hr>

<?php if ( $count > 0 ) { ?>
	<div id="graf" style="display:block; height:350px">
		<div id="chart" style="padding:5px; height: 100%;"></div>
		<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
		<script>

			var width = $('#contentdiv').width() - 40;
			var height = 350;
			var svg = dimple.newSvg("#chart", "100%", "100%");
			var data = [<?=$datas?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(0, 0, width - 50, height - 40);

			var x = myChart.addCategoryAxis("x", ["Этап"]);
			x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart.addMeasureAxis("y", "Сумма");
			y.showGridlines = true;//скрываем линии
			//myChart.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

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

			var s = myChart.addSeries(["Сотрудник"], dimple.plot.bar);
			//s.lineWeight = 2;
			//s.lineMarkers = true;
			s.stacked = true;
			//s.barGap = 5;

			//myChart.barGap = 0.5;
			var myLegend = myChart.addLegend(width - 200, 0, 200, 250, "right");
			myChart.setMargins(100, 20, 240, 35);

			//myChart.assignColor("План","green");

			myChart.draw(1000);

			myChart.legends = [];

			var filterValues = dimple.getUniqueValues(data, "Сотрудник");
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
				myChart.data = dimple.filterData(data, "Сотрудник", filterValues);
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

<div>

	<table id="zebra">
		<thead>
		<TR class="header_contaner">
			<td class="wp10 text-center"><B>Статус</B></td>
			<td class="wp10 text-center"><B>Кол-во</B></td>
			<td class="wp30 text-center"><B>Диаграмма</B></td>
			<td class="wp15 text-center"><B>Общая сумма, <?= $valuta ?></B></td>
			<td class="wp35 text-center"><B>Диаграмма</B></td>
		</TR>
		</thead>
		<?php
		$countTotal = $summaTotal = 0;
		foreach ( $list as $id => $item ) {

			$dogs_pec = $dogs_max > 0 ? round($item['count'] / $dogs_max * 100) : 0;
			$kol_pec  = $kol_max > 0 ? round($item['summa'] / $kol_max * 100) : 0;

			$countTotal += $item['count'];
			$summaTotal += $item['summa'];
			?>
			<TR class="ha">
				<TD class="text-center">
					<DIV title="<?= $step[ $id ] ?>"><?= $step[ $id ] ?></DIV>
				</TD>
				<TD class="text-center"><?= $item['count'] ?></TD>
				<TD>
					<DIV class="progressbar">
						<DIV id="test" class="progressbar-completed progress-blue" style="width:<?= $dogs_pec ?>%;">
							<DIV class="status"></DIV>
						</DIV>
					</DIV>
				</TD>
				<TD class="text-right"><?= num_format( $item['summa'] ) ?></TD>
				<TD>
					<DIV class="progressbar">
						<DIV id="test" class="progressbar-completed progress-blue" style="width:<?= $kol_pec ?>%;">
							<DIV class="status"></DIV>
						</DIV>
					</DIV>
				</TD>
			</TR>
			<?php
		}
		?>
		<TR bgcolor="#FC9">
			<TD class="text-right">&nbsp;&nbsp;<B>Итого:</B></TD>
			<TD class="text-center"><B><?= $countTotal ?></B></TD>
			<TD>&nbsp;</TD>
			<TD class="text-right"><B><?= num_format($summaTotal) ?></B>&nbsp;&nbsp;</TD>
			<TD>&nbsp;</TD>
		</TR>
	</TABLE>
</div>
<div style="height: 100px;"></div>