<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2019.x          */
/* ============================ */

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

$da1          = $_REQUEST['da1'];
$da2          = $_REQUEST['da2'];
$act          = $_REQUEST['act'];
$per          = $_REQUEST['per'];
$period       = $_REQUEST['period'];

$user_list    = (array)$_REQUEST['user_list'];

if (!$per) $per = 'nedelya';
if (!$filter) $filter = 'All';

$period = ($period == '') ? getPeriod('month') : getPeriod($period);

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$user_list = (!empty($user_list)) ? $user_list : yimplode(",", get_people($iduser1, "yes"));

$sort .= " and ".$sqlname."callhistory.iduser IN (".yimplode(",", $user_list).")";

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
$bads  = [
	'NO ANSWER',
	'NOANSWER',
	'BUSY',
	'CANCEL',
	'BREAKED',
	'FAILED'
];

$perName = 'День';

$list = $dates = $chart = [];

$filters = [
	"All"       => "Все",
	"OutAnswer" => "Исходящие",
	"InAnswer"  => "Входящие"
];

$users = $db -> getIndCol("iduser", "SELECT title, iduser FROM ".$sqlname."user WHERE identity = '$identity'");
//print_r($users);

//если период до 3-х месяцев
if (abs(diffDate2($da1, $da2)) <= 62) {

	for ($d = 0; $d < $day; $d++) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date('Y-m-d', $dat);

		$dates[] = $date = date("d.m", strtotime($datum));

		foreach ($user_list as $iduser) {

			////--По направлениям звонка

			$list[ $iduser ][ date("d.m", strtotime($datum)) ]['All'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and date_format(datum, '%Y-%m-%d') = '$datum' and iduser = '$iduser' and identity = '$identity'");

			//$list[ $iduser ][ date("d.m", strtotime($datum)) ]['In'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and date_format(datum, '%Y-%m-%d') = '$datum' and iduser = '$iduser' and identity = '$identity'");

			//$list[ $iduser ][ date("d.m", strtotime($datum)) ]['Out'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and date_format(datum, '%Y-%m-%d') = '$datum' and iduser = '$iduser' and identity = '$identity'");

			////--По результатам звонка. Исходящие

			$list[ $iduser ][ date("d.m", strtotime($datum)) ]['OutAnswer'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and iduser = '$iduser' and res = 'ANSWERED' and direct = 'outcome' and date_format(datum, '%Y-%m-%d') = '$datum' and identity = '$identity'");

			////--По результатам звонка. Входящие

			$list[ $iduser ][ date("d.m", strtotime($datum)) ]['InAnswer'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and iduser = '$iduser' and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%Y-%m-%d') = '$datum' and identity = '$identity'");

			$chart[] = '{"Дата":"'.$datum.'","Сотрудник":"'.strtr($iduser, $users).'","Длительность":"'.(round($list[ $iduser ][ date("d.m", strtotime($datum)) ][ $filter ]['sec'] / 60) + 0).'","День":"'.$date.'"}';

		}

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

			$datum = $year."-".$mon;
			$date  = $mon1.".".$year;

			$dates[] = $date;

			foreach ($user_list as $iduser) {

				////--По направлениям звонка

				$list[ $iduser ][ $date ]['All'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and date_format(datum, '%Y-%c') = '$datum' and iduser = '$iduser' and identity = '$identity'");

				//$list[ $iduser ][ $date ]['In'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'income' and date_format(datum, '%Y-%m') = '$datum' and iduser = '$iduser' and identity = '$identity'");

				//$list[ $iduser ][ $date ]['Out'] = $db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and direct = 'outcome' and date_format(datum, '%Y-%m') = '$datum' and iduser = '$iduser' and identity = '$identity'");

				////--По результатам звонка. Исходящие

				$list[ $iduser ][ $date ]['OutAnswer'] =
					$db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and iduser = '$iduser' and res = 'ANSWERED' and direct = 'outcome' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

				////--По результатам звонка. Входящие

				$list[ $iduser ][ $date ]['InAnswer'] =
					$db -> getRow("SELECT SUM(sec) as sec, COUNT(*) as count FROM ".$sqlname."callhistory WHERE id > 0 and iduser = '$iduser' and res = 'ANSWERED' and direct = 'income' and date_format(datum, '%Y-%m') = '$datum' and identity = '$identity'");

				$chart[] = '{"Дата":"'.$datum.'","Сотрудник":"'.strtr($iduser, $users).'","Длительность":"'.(round($list[ $iduser ][ $date ][ $filter ]['sec'] / 60) + 0).'","Месяц":"'.$date.'"}';

			}

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

//print_r($list);
//print_r($dates);
//print_r($chart);

$chart = implode(",", $chart);
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

	.data table th,
	.data table tbody td {
		word-break      : keep-all !important;
		white-space     : nowrap;
		hyphens         : none;
		-webkit-hyphens : none;
	}

	.data tbody td:first-child {
		background      : #FFF !important;
		word-break      : keep-all !important;
		white-space     : nowrap;
		hyphens         : none;
		-webkit-hyphens : none;
	}

	.data table thead td.nowrap {
		vertical-align : top;
	}

	.data table thead th:last-child {
		border-right : 1px dotted #ccc !important;
	}

	.data table thead tr:first-child th {
		border-top : 1px dotted #ccc !important;
		z-index    : 16 !important;
	}

	.data table td:last-child {
		border-right : 1px dotted #ccc !important;
	}

	.data table thead {
		border : 1px dotted #ccc !important;
	}

	.data table thead tr th:first-child {
		background : #E5F0F9 !important;
	}

	.data table thead {
		border : 1px dotted #222 !important;
	}

	.data table td i {
		z-index  : 0;
		position : inherit;
	}

	.data table tbody tr:hover td {
		background : rgba(224, 247, 250, 1) !important;
	}
</style>

<DIV class="zagolovok_rep text-center mt10">

	<h1 class="uppercase fs-14 m0 mb10">Анализ продолжительности разговоров</h1>
	<div class="fs-07 gray2 mt5">с <?= format_date_rus($da1) ?> по <?= format_date_rus($da2) ?></div>

</DIV>

<hr>

<table class="noborder">
	<tr>
		<td class="wp25">

			<div class="ydropDown w250" data-id="sort">

				<span title="Сортировать по"><i class="icon-filter black"></i></span>
				<span class="yText Bold fs-09">Направление: </span>
				<span class="ydropText Bold"><?= $filters[ $filter ] ?></span>
				<i class="icon-angle-down pull-aright arrow"></i>

				<div class="yselectBox" style="max-height: 350px;">
					<div class="ydropString yRadio <?= ($filter == 'All' ? 'bluebg-sub' : '') ?>">
						<label>
							<input type="radio" name="filter" id="filter" data-title="Все" value="All" class="hidden" <?= ($filter == 'All' ? 'checked' : '') ?>>&nbsp;Все направления
						</label>
					</div>
					<div class="ydropString yRadio <?= ($filter == 'InAnswer' ? 'bluebg-sub' : '') ?>">
						<label>
							<input type="radio" name="filter" id="filter" data-title="Входящие" value="InAnswer" class="hidden" <?= ($filter == 'InAnswer' ? 'checked' : '') ?>>&nbsp;Входящие
						</label>
					</div>
					<div class="ydropString yRadio <?= ($filter == 'input5' ? 'bluebg-sub' : '') ?>">
						<label>
							<input type="radio" name="filter" id="filter" data-title="Исходящие" value="OutAnswer" class="hidden" <?= ($filter == 'OutAnswer' ? 'checked' : '') ?>>&nbsp;Исходящие
						</label>
					</div>
				</div>

			</div>

		</td>
		<td class="wp25"></td>
		<td class="wp25"></td>
		<td></td>
	</tr>
</table>

<hr>

<div style="overflow-x: auto">

	<div class="data">

		<TABLE class="middle">
			<thead class="sticked--top">
			<TR class="th35">
				<th class="w20 text-center bgwhite">Оператор</th>
				<?php
				foreach ($dates as $date) {

					print '<th class="w80 text-center">'.$date.'</th>';

				}
				?>
				<th class="w50 text-center">Итого</th>
			</TR>
			</thead>
			<tbody>
			<?php
			$num = 1;
			foreach ($list as $iduser => $items) {

				$row   = '';
				$summa = $count = 0;

				foreach ($dates as $date) {

					$row   .= '
					<td class="w80 text-left '.($items[ $date ][ $filter ]['sec'] == 0 ? 'gray' : 'bluebg-sublite').'">
						'.($items[ $date ][ $filter ]['sec'] > 0 ? '<b>'.round($items[ $date ][ $filter ]['sec'] / 60).'</b> мин. <div class="fs-09 blue">'.$items[ $date ]['All']['count'].' зв.</div>' : '-').'
					</td>';
					$summa += $items[ $date ][ $filter ]['sec'];
					$count += $items[ $date ][ $filter ]['count'];

				}

				print '
				<tr class="td--main th40 ha" data-user="'.$iduser.'">
					<td class="Bold blue">'.strtr($iduser, $users).'</td>
					'.$row.'
					<td>
					'.($summa > 0 ? '
						<div><b>'.round($summa / 60).'</b> мин.</div>
						<div class="fs-09"><b class="blue">'.round($count).'</b> зв.</div>' : '<div class="gray">-</div>').'
					</td>
				</tr>
				';

			}
			?>
			</tbody>
		</TABLE>

	</div>

</div>

<div class="fs-12 pad10 Bold mt20">Диаграмма длительности разговоров</div>

<div id="graf" class="mt20">

	<div id="chart" class="p5"></div>

</div>

<div class="space-50"></div>

<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script>

	$(document).ready(function () {

		$(".data").tableHeadFixer({
			'head': true,
			'foot': false,
			'left': 1,
			'z-index': 120
		})
			.find('th:first-child').css('z-index', '140');

		drowChart();

	});

	function drowChart() {

		var width = $('#contentdiv').width() - 40;
		if (width < 1) width = 600;
		var height = 380;
		var svg = dimple.newSvg("#chart", width, height);
		var data = [<?=$chart?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(100, 0, width - 50, height - 100);

		var x = myChart.addCategoryAxis("x", "<?=$perName?>", "%d-%m-%Y", "%d.%m");
		x.addOrderRule("Дата");//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart.addMeasureAxis("y", "Длительность");
		y.showGridlines = false;//скрываем линии
		myChart.floatingBarWidth = 10;
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

		var s = myChart.addSeries(["Сотрудник"], dimple.plot.bar);
		s.lineWeight = 1;
		s.lineMarkers = true;
		s.stacked = true;

		var myLegend = myChart.addLegend(0, 0, width - 35, 0, "right");
		myChart.setMargins(60, 50, 40, 80);
		myChart.draw(1000);

		y.tickFormat = ".f";
		s.shapes.style("opacity", function (d) {
			return (d.y === null ? 0 : 0.8);
		});

		myChart.legends = [];

		$(window).bind('resizeEnd', function () {
			myChart.draw(0, true);
		});

	}

</script>