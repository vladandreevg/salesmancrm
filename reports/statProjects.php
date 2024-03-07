<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

use Salesman\User;

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
$period = $_REQUEST['period'];

$user_list = (array)$_REQUEST['user_list'];

// Статусы проектов
$status = [
	'0' => 'Новый',
	'1' => 'В работе',
	'2' => 'Выполнен',
	'3' => 'Отменен'
];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$diffPeriod = abs( diffDate2( $da1, $da2 ) );

//
//Количество Проектов по статусам
//

$sort   = '';
$sort_u = [];

$sort .= " AND {$sqlname}projects.datum BETWEEN '$da1' AND '$da2'";

if ( empty($user_list) ) {

	$users = User ::userCatalog( $iduser1 );
	foreach ( $users as $user ) {
		$sort_u[] = $user['id'];
	}
}
else {
	foreach ( $user_list as $user ) {
		$sort_u[] = $user;
	}
}

$sort .= " AND {$sqlname}projects.iduser IN (".yimplode( ",", $sort_u ).")";

$q = "
		SELECT 
			{$sqlname}projects.id,
			{$sqlname}projects.datum,
			{$sqlname}projects.name,
			{$sqlname}projects.date_start,
			{$sqlname}projects.date_end,
			{$sqlname}projects.date_fact,
			{$sqlname}projects.iduser,
			{$sqlname}projects.author,
			{$sqlname}projects.did,
			{$sqlname}projects.status,
			{$sqlname}projects.content,
			{$sqlname}user.title as user
		FROM {$sqlname}projects
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}projects.iduser
		WHERE 
			{$sqlname}projects.id > 0 and
			{$sqlname}projects.identity = '$identity'
			$sort
		GROUP BY {$sqlname}projects.iduser
		ORDER BY {$sqlname}projects.status
		";

$data = $db -> getAll( $q );

/**
 * Проекты по статусам
 */
$projects = [];
foreach ( $data as $da ) {
	$projects[] = strtr( $da['status'], $status );
}

//число записей по каждому статусу, которые попали в выборку
$projects = array_count_values( $projects );

$datas1 = [];
foreach ( $projects as $key => $value ) {

	$datas1[] = '{"Статус":"'.$key.'","Кол-во":"'.$value.'"}';

}

$datas1 = yimplode( ",", $datas1 );

?>
<div class="flex-container mt20 p20" style="margin:0 auto;">

	<div class="fs-12 Bold text-left pb20 wp90">Количество Проектов по статусам:</div>

	<?php if ( count( $projects ) > 0 ) { ?>
		<div class="flex-string wp20 p20">
			<table width="50%" border="0" cellpadding="5" cellspacing="0">
				<thead>
				<tr class="bordered header_contaner">
					<th class="blue">Статус</th>
					<th class="blue">Кол-во</th>
				</tr>
				</thead>
				<?php
				foreach ( $projects as $k => $v ) {

					?>
					<tr height="40" class="ha">
						<td><?= $k ?></td>
						<td align="right"><b><?= $v ?></b>&nbsp;</td>
					</tr>
				<?php } ?>
			</table>
		</div>

		<div class="flex-string pl20">
			<div id="graf" style="display:block; height:350px; width: 700px">

				<div id="chartq" style="padding:5px"></div>

			</div>
		</div>
		<?php
	}
	else { ?>
		<div class="p20">За этот период Проектов не найдено</div>'
	<?php } ?>
</div>

<hr>

<?php

//
//Количество новых Проектов
//

$sort     = '';
$projects = $datas2 = [];

$sort .= " AND {$sqlname}projects.datum BETWEEN '$da1' AND '$da2'";

if ( $user_list[0] == '' ) {
	$users = User ::userCatalog( $iduser1 );
	foreach ( $users as $user ) {
		$sort_u[] = $user['id'];
	}
}
else {
	foreach ( $user_list as $user ) {
		$sort_u[] = $user;
	}
}

$sort .= " AND {$sqlname}projects.author IN (".yimplode( ",", $sort_u ).")";

$q = "
		SELECT
			{$sqlname}projects.id,
			{$sqlname}projects.datum,
			{$sqlname}projects.name,
			{$sqlname}projects.date_start,
			{$sqlname}projects.date_end,
			{$sqlname}projects.date_fact,
			{$sqlname}projects.iduser,
			{$sqlname}projects.author,
			{$sqlname}projects.did,
			{$sqlname}projects.status,
			{$sqlname}projects.content,
			{$sqlname}user.title as user
		FROM {$sqlname}projects
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}projects.iduser
		WHERE
			{$sqlname}projects.id > 0 and
			{$sqlname}projects.identity = '$identity'
			$sort
		GROUP BY {$sqlname}projects.iduser
		ORDER BY {$sqlname}projects.datum
	";

$data = $db -> getAll( $q );


foreach ( $data as $da ) {
	$projects[] = $da['author'];
}

//число записей по каждой дате, которые попали в выборку
$projects = array_count_values( $projects );

foreach ( $projects as $key => $value ) {

	$datas2[] = '{"Сотрудник":"'.current_user( $key ).'","Кол-во":"'.$value.'","День":"'.date( "d.m", strtotime( $key ) ).'"}';

}

$datas2 = yimplode( ",", $datas2 );

?>
<div class="flex-container mt20 p20" style="margin:0 auto;">

	<div class="fs-12 Bold text-left pb20 wp90">Количество новых Проектов:</div>

	<?php
	if ( count( $projects ) > 0 ) {
		?>

		<div class="flex-string wp20 p20">
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<thead>
				<tr class="bordered header_contaner">
					<th class="blue">Сотрудник</th>
					<th class="blue">Кол-во</th>
				</tr>
				</thead>
				<?php
				foreach ( $projects as $k => $v ) {

					?>
					<tr height="40" class="ha">
						<td><?= current_user( $k ) ?></td>
						<td align="right"><b><?= $v ?></b>&nbsp;</td>
					</tr>
				<?php } ?>
			</table>
		</div>

		<div class="flex-string wp70">
			<div id="graf2" class="div-center" style="display:block; height:500px">

				<div id="chart2" style="padding:5px"></div>

			</div>
		</div><?php
	}
	else { ?>
		<div class="p20">За этот период Проектов не найдено</div>
	<?php } ?>
</div>

<hr>

<?php

//
//Количество новых Работ
//

$sort  = '';
$works = $datas3 = [];

$sort .= " AND {$sqlname}projects_work.datum BETWEEN '$da1' AND '$da2'";

if ( $user_list[0] == '' ) {
	$users = User ::userCatalog( $iduser1 );
	foreach ( $users as $user ) {
		$sort_u[] = $iduser1;
	}
}
else {
	foreach ( $user_list as $user ) {
		$sort_u[] = $user;
	}
}

$sort .= " AND {$sqlname}projects_work.iduser IN (".yimplode( ",", $sort_u ).")";

$q = "
		SELECT
			{$sqlname}projects_work.id,
			{$sqlname}projects_work.datum,
			{$sqlname}projects_work.name,
			{$sqlname}projects_work.date_start,
			{$sqlname}projects_work.date_end,
			{$sqlname}projects_work.date_fact,
			{$sqlname}projects_work.iduser,
			{$sqlname}projects_work.workers,
			{$sqlname}projects_work.status,
			{$sqlname}projects_work.content,
			{$sqlname}user.title as user
		FROM {$sqlname}projects_work
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}projects_work.iduser
		WHERE
			{$sqlname}projects_work.id > 0 AND
			{$sqlname}projects_work.identity = '$identity'
			$sort
		GROUP BY {$sqlname}projects_work.iduser
		ORDER BY {$sqlname}projects_work.datum
	";

$data = $db -> getAll( $q );


foreach ( $data as $da ) {
	$works[] = $da['iduser'];
}

//число записей по каждой дате, которые попали в выборку
$works = array_count_values( $works );

foreach ( $works as $key => $value ) {

	$datas3[] = '{"Сотрудник":"'.current_user( $key ).'","Кол-во":"'.$value.'"}';

}

$datas3 = yimplode( ",", $datas3 );


?>

<div class="flex-container mt20 p20" style="margin:0 auto;">

	<div class="fs-12 Bold text-left pb20 wp90">Количество созданных Работ:</div>

	<?php
	if ( count( $works ) > 0 ) {
		?>

		<div class="flex-string wp20 p20">
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<thead>
				<tr class="bordered header_contaner">
					<th class="blue">Сотрудник</th>
					<th class="blue">Кол-во</th>
				</tr>
				</thead>
				<?php
				foreach ( $works as $k => $v ) {

					?>
					<tr height="40" class="ha">
						<td><?= current_user( $k ) ?></td>
						<td align="right"><b><?= $v ?></b>&nbsp;</td>
					</tr>
				<?php } ?>
			</table>
		</div>
		<div class="flex-string wp70">
			<div id="graf3" class="div-center" style="display:block; height:500px">
				<div id="chart3" style="padding:5px"></div>
			</div>
		</div><?php
	}

	else { ?>
		<div class="p20">За этот период Работ не найдено</div>
	<?php } ?>
</div>

<hr>

<?php

//
// Средняя продолжительность выполнения работ
//

$sort  = '';
$works = $datas4 = [];

$sort .= " AND {$sqlname}projects_work.datum BETWEEN '$da1' AND '$da2' AND {$sqlname}projects_work.date_fact BETWEEN '$da1' AND '$da2'";

if ( $user_list[0] == '' ) {
	$users = User ::userCatalog( $iduser1 );
	foreach ( $users as $user ) {
		$sort_u[] = $iduser1;
	}
}
else {
	foreach ( $user_list as $user ) {
		$sort_u[] = $user;
	}
}

$sort .= " AND {$sqlname}projects_work.workers IN (".yimplode( ",", $sort_u ).")";

$q = "
		SELECT
			{$sqlname}projects_work.id,
			{$sqlname}projects_work.datum,
			{$sqlname}projects_work.name,
			{$sqlname}projects_work.date_start,
			{$sqlname}projects_work.date_end,
			{$sqlname}projects_work.date_fact,
			{$sqlname}projects_work.iduser,
			{$sqlname}projects_work.workers,
			{$sqlname}projects_work.status,
			{$sqlname}projects_work.content,
			{$sqlname}user.title as user
		FROM {$sqlname}projects_work
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}projects_work.iduser
		WHERE
			{$sqlname}projects_work.id > 0 AND
			{$sqlname}projects_work.status = '4' AND
			{$sqlname}projects_work.date_fact > '0000-00-00' AND
			{$sqlname}projects_work.identity = '$identity'
			$sort
		GROUP BY {$sqlname}projects_work.iduser
		ORDER BY {$sqlname}projects_work.datum
	";

$data = $db -> getAll( $q );

foreach ( $data as $da ) {
	$works[ $da['workers'] ] += abs( diffDate2( $da['datum'], $da['date_fact'] ) );
}

//число записей по каждой дате, которые попали в выборку
foreach ( $works as $key => $value ) {

	$kol[ $key ] = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}projects_work WHERE id > 0 AND {$sqlname}projects_work.status = '4' AND find_in_set($key,{$sqlname}projects_work.workers)>0 AND identity = '$identity' ORDER BY datum DESC" );

	$datas4[] = '{"Сотрудник":"'.current_user( $key ).'","Время":"'.$value / $kol[ $key ].'"}';

}
$datas4 = yimplode( ",", $datas4 );

?>
<div class="flex-container mt20 p20" style="margin:0 auto;">

	<div class="fs-12 Bold text-left pb20 wp90">Средняя продолжительность выполнения работ:</div>

	<?php
	if ( count( $works ) > 0 ) {
		?>

		<div class="flex-string wp20 p20">
			<table width="100%" border="0" cellpadding="5" cellspacing="0">
				<thead>
				<tr class="bordered header_contaner">
					<th class="blue">Сотрудник</th>
					<th class="blue">Время</th>
				</tr>
				</thead>
				<?php
				foreach ( $works as $k => $v ) {

					?>
					<tr height="40" class="ha">
						<td><?= current_user( $k ) ?></td>
						<td align="right"><b><?= $v / $kol[ $key ] ?> дн.</b>&nbsp;</td>
					</tr>
				<?php } ?>
			</table>
		</div>
		<div class="flex-string wp70">
			<div id="graf4" class="div-center" style="display:block; height:500px">
				<div id="chart4" style="padding:5px"></div>
			</div>
		</div>
		<?php
	}
	else { ?>
		<div class="p20">За этот период Работ не найдено</div>'
	<?php } ?>
</div>

<hr>

<script src="/assets/js/d3.min.js"></script>
<script src="/assets/js/dimple.min.js"></script>
<script>
	$(document).ready(function () {

		graph1();
		graph2();
		graph3();
		graph4();

	});

	function graph1() {

		var width = $('#graf').width();

		var height = 350;
		var svg = dimple.newSvg("#chartq", width, height);
		var data = [<?=$datas1?>];
		var myChartq = new dimple.chart(svg, data);

		myChartq.setBounds(100, 0, width - 100, height - 100);
		var y = myChartq.addMeasureAxis("p", "Кол-во");

		var ring = myChartq.addSeries("Статус", dimple.plot.pie);
		ring.innerRadius = "50%";

		myChartq.addLegend(0, 10, 50, 400, "left");
		myChartq.setMargins(60, 20, 40, 30);
		myChartq.draw(1000);

		y.tickFormat = ".f";
		ring.shapes.style("opacity", function (d) {
			return (d.y === null ? 0 : 0.8);
		});

		$(window).bind('resizeEnd', function () {
			myChartq.draw(0, true);
		});

	}

	function graph2() {

		var width = $('#contentdiv').width() - 400;
		if (width < 1) width = 1000;
		var height = 450;
		var svg = dimple.newSvg("#chart2", width, height);
		var data2 = [<?=$datas2?>];

		var myChart2 = new dimple.chart(svg, data2);

		myChart2.setBounds(100, 0, width - 50, height - 100);

		var x = myChart2.addCategoryAxis("x", "Сотрудник", "%d-%m-%Y", "%d.%m");
		x.addOrderRule("Сотрудник");//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart2.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии
		myChart2.floatingBarWidth = 10;
		//y.ticks = 5;//шаг шкалы по оси y

		var s = myChart2.addSeries(["Сотрудник"], dimple.plot.bar);
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

	}

	function graph3() {

		var width = $('#contentdiv').width() - 400;
		if (width < 1) width = 600;
		var height = 450;
		var svg = dimple.newSvg("#chart3", width, height);
		var data2 = [<?=$datas3?>];

		var myChart2 = new dimple.chart(svg, data2);

		myChart2.setBounds(100, 0, width - 50, height - 100);

		var x = myChart2.addCategoryAxis("x", "Сотрудник", "%d-%m-%Y", "%d.%m");
		x.addOrderRule("Сотрудник");//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart2.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии
		myChart2.floatingBarWidth = 10;
		//y.ticks = 5;//шаг шкалы по оси y

		var s = myChart2.addSeries(["Сотрудник"], dimple.plot.bar);
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

	}

	function graph4() {
		var width = $('#contentdiv').width() - 400;
		if (width < 1) width = 600;
		var height = 450;
		var svg = dimple.newSvg("#chart4", width, height);
		var data2 = [<?=$datas4?>];

		var myChart2 = new dimple.chart(svg, data2);

		myChart2.setBounds(100, 0, width - 50, height - 100);

		var x = myChart2.addCategoryAxis("x", "Сотрудник", "%d-%m-%Y", "%d.%m");
		x.addOrderRule("Сотрудник");//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart2.addMeasureAxis("y", "Время");
		y.showGridlines = false;//скрываем линии
		myChart2.floatingBarWidth = 10;
		//y.ticks = 5;//шаг шкалы по оси y

		var s = myChart2.addSeries(["Сотрудник"], dimple.plot.bar);
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
	}
</script>
