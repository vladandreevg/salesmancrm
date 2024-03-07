<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\User;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$Interval = $_COOKIE['historyInterval'];

$setperiod = (strlen($Interval) > 1 && $Interval != 'undefined') ? $Interval : 'month';

$period = getPeriod($setperiod);

//print_r($period);

$da1 = $period[0];
$da2 = $period[1];

$users = User::userColleagues($iduser1);

//print_r($users);

$list = $datalist = $total = [];
$max = 0;

$act = $db->getCol("SELECT title FROM ".$sqlname."activities WHERE identity = '$identity'");

function cmp($a, $b): bool { return $b['value'] < $a['value']; }

$data = $db -> getAll("
	SELECT 
		iduser, tip, COUNT(cid) as count 
	FROM {$sqlname}history 
	WHERE 
		DATE(datum) >= '$da1' and 
		DATE(datum) <= '$da2' and 
		tip NOT IN ('СобытиеCRM','ЛогCRM') AND
		identity = '$identity'
	GROUP BY 1, 2
");

foreach ($data as $row){

	$list[$row['iduser']]['data'][$row['tip']] += $row['count'];
	$list[$row['iduser']]['total'] += $row['count'];

}

foreach ($users as $user) {

	$li = [];

	$total[$user['id']] = $list[$user['id']]['total'];

	foreach ($act as $tip) {

		$count = (int)$list[$user['id']]['data'][$tip];

		if($count > 0) {

			$li[] = ["Активность:" => $tip, "parent" => $user['title'], "value" => $count];
			$max = ($count > $max) ? $count : $max;

		}

	}

	//отсортируем активности по значению
	usort($li, 'cmp');

	$list[$user['id']] = $li;

}

$keynames = array_map( static function($str){
	return $str;
},$act);

//расчитаем шаги для раскраски блоков
$step = round($max/10 + 0.5);
$steps = [];

for($i = 1; $i <= 10; $i++){
	$steps[] = $i * $step;
}

$maxChildCount = count($keynames);
$steps = implode(",", $steps);
$keynames = implode(",", $keynames);

/**
 * Сортировка массива по общему количеству активностей у пользователя
 */
//arsort($total, SORT_NUMERIC);

foreach ($total as $id => $v){

	foreach ($list[$id] as $item) {

		$item['parent'] = "[".$v."] ".$item['parent'];
		$item['total'] = $v;

		$datalist[] = $item;

	}

}

//print_r($datalist);

$json = json_encode_cyr($datalist);
?>
<link rel="stylesheet" type="text/css" href="/assets/js/d3-relationshipgraph/d3.relationshipgraph.min.css">
<style>

	#activehitmap ul.group {
		position: absolute;
		z-index: 1;
		top: calc(100% - 40px);
		right: 10px;
		display: table;
		list-style: none;
		background: rgba(245,245,245 ,0.3);
		border: 1px solid #CCD1D9;
		margin-top: 5px;
		padding: 0;
		font-size: 0.9em;
		border-radius: 4px;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
	}

	#activehitmap ul.group > li {
		margin: 0 !important;
		padding: 5px 10px !important;
		display: table-cell;
		text-align: center;
		cursor: pointer;
		border-right: 1px solid #CCD1D9;
		box-sizing: border-box !important;
	}

	#activehitmap ul.group > li:last-child {
		border-right: 0;
	}

	#activehitmap ul.group > li:hover,
	#activehitmap ul.group > li.active{
		color: #fff;
		background: #C0392B;
		border-color: #C0392B !important;
	}

	#activehitmap svg{
		width: 100% !important;
	}

	.relationshipGraph-block {
		stroke: #222;
		stroke-width: 0;
	}
	.relationshipGraph-Text {
		fill: #9E9E9E !important;
	}

</style>

<div id="graph"></div>

<div class="pull-aright">
	<ul class="group">
		<li data-id="today">Сегодня</li>
		<li data-id="calendarweek">Неделя</li>
		<li data-id="month">Месяц</li>
		<li data-id="quart">Квартал</li>
		<li data-id="year">Год</li>
	</ul>
</div>

<script src="/assets/js/d3-relationshipgraph/d3.relationshipgraph.min.js"></script>
<script>

	$('#activehitmap').find('ul.group').find('li[data-id="<?=$setperiod?>"]').addClass('active');

	$('#activehitmap').find('li').bind('click', function(){

		var id = $(this).data('id');

		setCookie('historyInterval', id, {"expires":1000000});

		$('#activehitmap').load('content/vigets/viget.activities.php');

	});

	$(document).ready( function() {

		getChartActivities();

	});

	function getChartActivities() {

		var w = $('#activehitmap').actual('innerWidth');

		$('#activehitmap').css({"max-width": w + "px"});

		var json = <?=$json?>;
		var graph = d3.select('#graph').relationshipGraph({
			'maxChildCount': <?=$maxChildCount?>,
			'showKeys': true,
			'valueKeyName': 'Значение',
			'truncate': 18,
			'thresholds': [0,<?=$steps?>],
			'colors': ['#EEEEEE','#F5F5F5','#CFD8DC', '#B0BEC5', '#E6EE9C', '#D4E157', '#AED581', '#7CB342', '#FF8A65', '#f44336', '#b71c1c'],
			'showTooltips': true,
			'blockSize': 20,
			sortFunction: sortJson
		});

		graph.data(json);

	}


	function sortJson(data){

		data.sort(function(a, b) {
			return parseFloat(b.total) - parseFloat(a.total);
		});

	}

</script>