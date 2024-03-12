<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2018.3          */

/* ============================ */

use Salesman\Metrics;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$year  = (int)date( 'Y' );
$month = (int)date( 'n' );

//$month = 10;

//единицы измерения
$edizms = Metrics ::metricEdizm();

//список KPI пользователя
$kpis = Metrics ::getUserKPI( [
	"iduser"   => $iduser1,
	"year"     => $year,
	"as_money" => true
] );

$list = [];

$f = new Metrics();

// сезонные коэффициенты к плану
$kpiSeason = $f -> getSeason( $year );

//print_r($kpiSeason);

$periodName = [
	"day"     => "Д",
	"week"    => "Н",
	"month"   => "М",
	"quartal" => "К",
	"year"    => "Г"
];

$tips = Metrics ::MetricList();

// print_r($kpis);

//обходим показатели
foreach ( $kpis as $kpi ) {

	switch ($kpi['period']) {

		case "day":

			$calc = $f -> calculateKPI( $iduser1, $kpi['kpi'], '', current_datum() );

		break;
		/*case "week":

			$calc = $f -> calculateKPI($iduser1, $kpi['kpi'], 'week', current_datum());

		break;*/
		case "month":

			$calc = $f -> calculateKPI( $iduser1, $kpi['kpi'], 'month' );

		break;
		default:

			$calc = $f -> calculateKPI( $iduser1, $kpi['kpi'], $kpi['period'] );

		break;

	}

	//print $calc;

	//$help = $f -> getKPIs($kpi['kpi']);

	//print $kpi['period'];
	//print $kpiSeason[ $month ]."<br>";

	if ( in_array( $kpi['period'], [
		'day',
		'week',
		'month'
	] ) ) {

		//print $kpi['value'].": ".pre_format( $kpi['value'] )."; ";
		//print $month.": ".$kpiSeason[ $month ]."; ";

		$kpi['value'] = pre_format( $kpi['value'] ) * $kpiSeason[ $month ];

	}

	$percent = $kpi['value'] > 0 ? round( 100 * (pre_format( $calc ) / pre_format( $kpi['value'] )), 2 ) : 0;

	$list[] = [
		"id"         => $kpi['id'],
		"kpi"        => $kpi['kpi'],
		"title"      => $kpi['kpititle'],
		"tipTitle"   => $kpi['tipTitle'],
		"edizm"      => $edizms[ $kpi['tip'] ],
		"period"     => $kpi['period'],
		"periodname" => $kpi['periodname'],
		"plan"       => $kpi['value'],
		"type"       => ($kpi['isPersonal'] == '1') ? "Персональный" : "Консолидированный",
		"fact"       => $calc,
		"percent"    => $percent
	];

}

//print_r($list);

//отсортируем по выполнению
function cmp($a, $b) {
	return $b['percent'] > $a['percent'];
}

usort( $list, 'cmp' );

$string = '';

foreach ( $list as $item ) {

	$help = [];

	$percent = $item['percent'];

	if ( $percent == 0 )
		$color = 'gray';
	elseif ( is_between( $percent, 0, 50 ) )
		$color = 'red';
	elseif ( is_between( $percent, 50, 70 ) )
		$color = 'blue';
	elseif ( is_between( $percent, 70, 90 ) )
		$color = 'green-lite';
	else $color = 'green';

	$help[] = $item['type'];
	$help[] = 'Период выполнения - '.$item['periodname'];

	$string .= '
	<div class="flex-container box--child p10 border-bottom '.$color.' ha table kpido fs-12 flh-14 mob--card" data-id="'.$item['id'].'" data-kpi="'.$item['kpi'].'">
	
		<div class="flex-string wp50 hand nopad" onclick="viewKPI(\''.$item['kpi'].'\')" title="О показателе">
			<div class="Bold ellipsis">'.strtr( $item['period'], $periodName ).": ".$item['title'].'</div>
			<div class="em gray fs-07 flh-07">'.$item['tipTitle'].'</div>
		</div>
		<div class="flex-string wp20">
			<div class="Bold">'.$percent.'%</div>
		</div>
		<div class="flex-string wp30 hand text-right" onclick="viewKPIfact(\''.$iduser1.'\',\''.$item['id'].'\')" title="Детали. '.yimplode( "; ", $help ).'">
			<div class="Bold">'.num_format( $item['fact'] ).' '.$item['edizm'].'</div>
			<div class="fs-07 flh-07 gray2 mmt5">из '.num_format( $item['plan'] ).'</div>
		</div>
	
	</div>
	';

}
?>

<div class="flex-container box--child p10 fs12 graybg-sub table Bold hidden">

	<div class="flex-string wp50 hidden-iphone">Показатель</div>
	<div class="flex-string wp20"></div>
	<div class="flex-string wp30">Текущее</div>

</div>
<?php
if ( $string == '' )
	print '
		<div class="warning flex-container">
			<div class="flex-string wp30">
				<span class="pull-left"><i class="icon-attention red icon-5x pull-left"></i></span>
			</div>
			<div class="flex-string wp70 pb20">
				<h1 class="red uppercase mt5">Внимание</h1>
				Параметры KPI для вас не заданы. Увы и ах ;)
			</div>
		</div>
	';
?>
<?= $string ?>

<script>

	$(document).ready(function () {

		$.Mustache.load('/modules/metrics/tpl.metrics.html');

	});

	function viewKPI(id) {

		var url = '/modules/metrics/list.metrics.php';
		var str = 'action=kpi&id=' + id;

		var $dialog = $('#dialog');
		var $resultdiv = $('#resultdiv');
		var $container = $('#dialog_container');
		var $preloader = $('.dialog-preloader');

		$container.css('height', $(window).height());
		$dialog.css('width', '700px').css('height', 'unset').css('display', 'none');
		$container.css('display', 'block');
		$preloader.center().css('display', 'block');

		$.getJSON(url, str, function (viewData) {

			$resultdiv.empty().mustache('kpiDialogTpl', viewData);

		})
			.complete(function () {

				$preloader.css('display', 'none');
				$dialog.css('display', 'block').center();

			});

	}

	function viewKPIfact(iduser, id) {

		var url = '/modules/metrics/list.metrics.php';
		var str = 'action=user.kpido&id=' + id + '&iduser=' + iduser;

		var $dialog = $('#dialog');
		var $resultdiv = $('#resultdiv');
		var $container = $('#dialog_container');
		var $preloader = $('.dialog-preloader');

		$container.css('height', $(window).height());
		$dialog.css('width', '700px').css('height', 'unset').css('display', 'none');
		$container.css('display', 'block');
		$preloader.center().css('display', 'block');

		$.getJSON(url, str, function (viewData) {

			$resultdiv.empty().mustache('userkpisDialogTpl', viewData);

		})
			.complete(function () {

				$preloader.css('display', 'none');
				$dialog.css('display', 'block').center();

				$resultdiv.find(".percent").each(function () {

					var num = parseInt($(this).find('div').html());
					var parent = $(this).closest('.flex-container');

					if (num === 0) parent.addClass('gray');
					else if (num <= 50) parent.addClass('red');
					else if (num <= 70) parent.addClass('blue');
					else if (num <= 90) parent.addClass('green-lite');
					else parent.addClass('green');

				});

			});

	}

</script>
