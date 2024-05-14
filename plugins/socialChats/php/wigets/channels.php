<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Chats\Chats;

$rootpath = dirname( __DIR__, 4 );
$ypath    = $rootpath."/plugins/socialChats";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

/**
 * Диаграмма "Каналы" - информация о том, какой самый популярный канал обращения среди клиентов  за выбранный период времени с помощью настройки "Фильтр"
 */

$d1       = $_REQUEST['periodStart'];
$d2       = $_REQUEST['periodEnd'];

$acolors = [
	"#f44336",
	"#FF9800",
	//"#FFEB3B",
	"#4CAF50",
	"#2196F3",
	"#3F51B5",
	"#E91E63",
	"#03A9F4",
	"#A1887F",
	"#673AB7",
	"#FFC107",
	"#8BC34A",
	"#00BCD4",
	"#795548",
	"#7CB342",
	"#0097A7",
	"#D500F9",
	"#76FF03",
	"#DD2C00",
	"#B0BEC5",
	"#90A4AE",
	"#78909C",
	"#607D8B",
	"#9E9E9E",
	"#6D4C41"
];

//$acolors = array_reverse($acolors);

$diffPeriod = abs(diffDate2($d1, $d2));

$chat = new Chats();
$channels = $chat -> getChannels();

$new = $archive = $total = [];
$chnls = [];
$chartData = [];

//print_r($channels);

foreach ($channels as $index => $channel){

	$chnls[ $channel['channel_id'] ] = $channel;

	// новые чаты
	$new[ $channel['channel_id'] ] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE chat_id IN (SELECT chat_id FROM {$sqlname}chats_chat WHERE channel_id = '$channel[channel_id]') AND newvalue = 'free' AND datum BETWEEN '$d1 00:00:00' AND '$d2 23:59:59'");

	// завершенные чаты
	$archive[ $channel['channel_id'] ] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE chat_id IN (SELECT chat_id FROM {$sqlname}chats_chat WHERE channel_id = '$channel[channel_id]') AND newvalue = 'archive' AND oldvalue = 'inwork' AND datum BETWEEN '$d1 00:00:00' AND '$d2 23:59:59'");

	// за всё время
	$total[ $channel['channel_id'] ] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}chats_logs WHERE chat_id IN (SELECT chat_id FROM {$sqlname}chats_chat WHERE channel_id = '$channel[channel_id]') AND newvalue = 'free'");

}

arsort($new);
arsort($total);

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

	.colorit {
		display       : block;
		height        : 25px;
		line-height   : 25px;
		padding-right : 5px;
	}

	.path {
		background    : #E5F0F9;
		padding-left  : 5px;
		padding-right : 5px;
	}

	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	-->
</style>

<div class="text-center">

	<h1 class="fs-20 mt20 mb10">Популярность каналов</h1>
	<div class="gray2">за период с <?= format_date_rus($d1) ?>&nbsp;по&nbsp;<?= format_date_rus($d2) ?></div>

</div>

<div class="space-40"></div>

<div class="datas">

	<div class="flex-container box--child">

		<div class="flex-string wp40 div-center">

			<div class="wp60 chartContainer" style="margin: 0 auto;">
				<canvas id="myChart"></canvas>
			</div>

		</div>
		<div class="flex-string wp60 fs-11 pr20">

			<?php
			$index = 0;
			foreach ($new as $key => $value){

				$color = hexToRgb($acolors[$index]);

				$chartData['new'][] = '
					{
						value: '.(array_sum($new) > 0 ? round($value / array_sum($new) * 100, 2) : 0).',
						color: "rgba('.$color['r'].','.$color['g'].','.$color['b'].',0.9)",
						highlight: "rgba('.$color['r'].','.$color['g'].','.$color['b'].',0.5)",
						label: "'.$chnls[$key]['name'].'"
					}
				';

				$index++;

				print '
				<div style="background:rgba('.$color['r'].','.$color['g'].','.$color['b'].',.2)" class="flex-container float border-bottom p10 ha">
					<div class="flex-string float flh-12">
						<div class="Bold fs-12">
							<div class="bullet" style="background:rgba('.$color['r'].','.$color['g'].','.$color['b'].',1)"></div>&nbsp;&nbsp;'.$chnls[$key]['name'].'
						</div>
						<div class="fs-09 gray2"><img src="assets/images/'.$chnls[$key]['icon'].'" width="14" style="">&nbsp;&nbsp;'.$chnls[$key]['messenger'].'</div>
					</div>
					<div class="flex-string w100">
						<div class="Bold fs-16">'.$value.'</div>
					</div>
				</div>
				';

			}
			?>

		</div>

	</div>

</div>

<div class="space-20"></div>

<div class="text-center">

	<h1 class="fs-20 mt20 mb10">За всё время</h1>

</div>

<div class="space-40"></div>

<div class="datas2">

	<div class="flex-container box--child">

		<div class="flex-string wp40 div-center">

			<div class="wp60 chartContainer2" style="margin: 0 auto;">
				<canvas id="myChart2"></canvas>
			</div>

		</div>
		<div class="flex-string wp60 fs-11 pr20">

			<?php
			$index = 0;
			foreach ($total as $key => $value){

				$color = hexToRgb($acolors[$index]);

				$chartData['archive'][] = '
					{
						value: '.round($value/array_sum($total) * 100, 2).',
						color: "rgba('.$color['r'].','.$color['g'].','.$color['b'].',0.9)",
						highlight: "rgba('.$color['r'].','.$color['g'].','.$color['b'].',0.5)",
						label: "'.$chnls[$key]['name'].'"
					}
				';

				$index++;

				print '
				<div style="background:rgba('.$color['r'].','.$color['g'].','.$color['b'].',.2)" class="flex-container float border-bottom p10">
					<div class="flex-string float flh-12">
						<div class="Bold fs-12">
							<div class="bullet" style="background:rgba('.$color['r'].','.$color['g'].','.$color['b'].',1)"></div>&nbsp;&nbsp;'.$chnls[$key]['name'].'
						</div>
						<div class="fs-09 gray2 pl5"><img src="assets/images/'.$chnls[$key]['icon'].'" width="14">&nbsp;&nbsp;'.$chnls[$key]['messenger'].'</div>
					</div>
					<div class="flex-string w100">
						<div class="Bold fs-16">'.$value.'</div>
					</div>
				</div>
				';

			}
			?>

		</div>

	</div>

</div>

<div class="space-60"></div>

<script type="text/javascript" src="/assets/js/chartjs/Chart.js"></script>
<script>

	(function () {

		var cw = $('.chartContainer').width() - 50;

		if( cw > 300 )
			cw = 300;

		$('#myChart').css({"width":cw,"height":cw});

		var data = [<?=yimplode(",", $chartData['new'])?>];
		var ctx = document.getElementById("myChart").getContext("2d");
		var myDoughnutChart = new Chart(ctx).Doughnut(data,
			{
				segmentShowStroke : true,//Boolean - Whether we should show a stroke on each segment
				segmentStrokeColor : "#fff",//String - The colour of each segment stroke
				segmentStrokeWidth : 2,//Number - The width of each segment stroke
				percentageInnerCutout : 50, // This is 0 for Pie charts //Number - The percentage of the chart that we cut out of the middle
				animationSteps : 100,//Number - Amount of animation steps
				animationEasing : "easeOutBounce",//String - Animation easing effect
				animateRotate : true,//Boolean - Whether we animate the rotation of the Doughnut
				animateScale : false,//Boolean - Whether we animate scaling the Doughnut from the centre
				responsive: true,
				tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>%"
			}
		);

	}());

	(function () {

		var cw = $('.chartContainer2').width() - 50;

		$('#myChart2').css({"width":cw,"height":cw});

		var data = [<?=yimplode(",", $chartData['archive'])?>];
		var ctx = document.getElementById("myChart2").getContext("2d");
		var myDoughnutChart = new Chart(ctx).Doughnut(data,
			{
				segmentShowStroke : true,//Boolean - Whether we should show a stroke on each segment
				segmentStrokeColor : "#fff",//String - The colour of each segment stroke
				segmentStrokeWidth : 2,//Number - The width of each segment stroke
				percentageInnerCutout : 50, // This is 0 for Pie charts //Number - The percentage of the chart that we cut out of the middle
				animationSteps : 100,//Number - Amount of animation steps
				animationEasing : "easeOutBounce",//String - Animation easing effect
				animateRotate : true,//Boolean - Whether we animate the rotation of the Doughnut
				animateScale : false,//Boolean - Whether we animate scaling the Doughnut from the centre
				responsive: true,
				tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>%"
			}
		);

	}());

</script>
