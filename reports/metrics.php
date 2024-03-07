<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2019.2          */
/* ============================ */

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

use Salesman\Metrics;

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$periodName = [
	"day"     => "Д",
	"week"    => "Н",
	"month"   => "М",
	"quartal" => "К",
	"year"    => "Г"
];

$reportName = basename( __FILE__ );

$month = (int)$_REQUEST['month'];
$year  = (int)$_REQUEST['year'];
$users = (array)$_REQUEST['user_list'];
$ukpi  = [];

$curQuartal       = static function( $month) {

	$q2 = [
		4,
		5,
		6
	];
	$q3 = [
		7,
		8,
		9
	];
	$q4 = [
		10,
		11,
		12
	];

	$quartal = 1;

	if ( in_array( $month, $q2 ) )
		$quartal = 2;
	elseif ( in_array( $month, $q3 ) )
		$quartal = 3;
	elseif ( in_array( $month, $q4 ) )
		$quartal = 4;

	return $quartal;

};
$getQuartalPeriod = static function( $quartal, $year) {

	$dates = [
		$year.'-01-01',
		$year.'-03-31'
	];

	if ( $quartal == 2 )
		$dates = [
			$year.'-04-01',
			$year.'-06-30'
		];

	elseif ( $quartal == 3 )
		$dates = [
			$year.'-07-01',
			$year.'-09-30'
		];

	elseif ( $quartal == 4 )
		$dates = [
			$year.'-10-01',
			$year.'-12-31'
		];


	return $dates;

};

//начальный год
$startYear = $db -> getOne( "SELECT MIN(year) FROM ".$sqlname."kpi WHERE identity = '$identity'" );
if ( $startYear == '' )
	$startYear = date( 'Y' );

//отсортируем по выполнению
function cmp($a, $b): bool {
	return $b['percent'] > $a['percent'];
}

if ( $month == 0 )
	$month = (int)date( 'm' );

if ( $year == 0 )
	$year = (int)date( 'Y' );

if ( empty( $users ) )
	$users = get_people( $iduser1, "yes" );

$month = str_pad( $month, 2, '0', STR_PAD_LEFT );

$d1 = "$year-$month-01";
$d2 = "$year-$month-".date( "t", date_to_unix( $d1 ) );
$d3 = "$year-$month-".date('d');

$f = new Metrics();

// сезонные коэффициенты к плану
$kpiSeason = $f -> getSeason($year);

foreach ( $users as $user ) {

	$string = '';
	$list   = [];

	//список KPI пользователя
	$kpis = Metrics ::getUserKPI( [
		"iduser"   => $user,
		"year"     => $year,
		"as_money" => true
	] );

	//print_r($kpis);

	//обходим показатели
	foreach ( $kpis as $kpi ) {

		switch ($kpi['period']) {

			case "day":

				$calc = $f -> calculateKPI( $user, $kpi['kpi'], '', $d3 );

			break;
			case "month":
			case "week":

				$calc = $f -> calculateKPI( $user, $kpi['kpi'], "", [
					$d1,
					$d2
				] );

			break;
			case "quartal":

				$calc = $f -> calculateKPI( $user, $kpi['kpi'], "", $getQuartalPeriod( $curQuartal( $month ), $year ) );

			break;
			case "year":

				$calc = $f -> calculateKPI( $user, $kpi['kpi'], "", [
					"$year-01-01",
					"$year-12-31"
				] );

			break;
			default:

				//$calc = $f -> calculateKPI($user, $kpi['kpi'], $kpi['period']);
				$calc = $f -> calculateKPI( $user, $kpi['kpi'], "", [
					$d1,
					$d2
				] );

			break;

		}

		if( in_array($kpi['period'], ['day','week','month']) ) {

			$kpi[ 'value' ] = pre_format($kpi[ 'value' ]) * $kpiSeason[ (int)$month ];

		}

		$percent = (int)$kpi['value'] > 0 ? round( 100 * (pre_format( $calc ) / pre_format( $kpi['value'] )), 2 ) : 0;

		$list[] = [
			"id"         => $kpi['id'],
			"kpi"        => $kpi['kpi'],
			"title"      => $kpi['kpititle'],
			"period"     => $kpi['period'],
			"periodname" => $kpi['periodname'],
			"plan"       => $kpi['value'],
			"type"       => ($kpi['isPersonal'] == '1') ? "Персональный" : "Консолидированный",
			"fact"       => $calc,
			"percent"    => $percent
		];

	}

	//print_r($list);

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
		else
			$color = 'green';

		$help[] = $item['type'];
		$help[] = 'Период выполнения - '.$item['periodname'];

		$string .= '
			<div class="flex-container box--child p10 border-bottom '.$color.' ha table kpido fs-12 flh-14" data-id="'.$item['id'].'" data-kpi="'.$item['kpi'].'">
			
				<div class="flex-string wp45 hand" onclick="viewKPI(\''.$item['kpi'].'\')" title="О показателе">
					<div class="Bold ellipsis">'.strtr( $item['period'], $periodName ).": ".$item['title'].'</div>
				</div>
				<div class="flex-string wp15">
					<div class="Bold">'.$percent.'%</div>
				</div>
				<div class="flex-string wp20 hand" onclick="viewKPIfact(\''.$user.'\',\''.$item['id'].'\')" title="Детали. '.yimplode( "; ", $help ).'">
					<div class="">'.num_format( $item['fact'] ).'</div>
				</div>
				<div class="flex-string wp20">
					<div class="">'.num_format( $item['plan'] ).'</div>
				</div>
			
			</div>
		';

	}

	if ( $string != '' ) {

		$uavatar = $db -> getOne( "SELECT avatar FROM ".$sqlname."user WHERE iduser = '$user' AND identity = '$identity'" );

		$avatarr = "/assets/images/noavatar.png";
		if ( $uavatar )
			$avatarr = "/cash/avatars/".$uavatar;

		$ukpi[] = '
		<div class="metrics--item h0 focused box-shadow" style="background: #FAFAFA; border: 1px dotted #ECF0F1;">
			
			<div class="flex-container float p5 graybg no-border">
				<div class="flex-string w60">
					<div class="avatar--mini" style="background: url('.$avatarr.'); background-size:cover;" title="'.current_user( $user, 'yes' ).'"></div>
				</div>
				<div class="flex-string float Bold mt10 fs-12 uppercase">'.current_user( $user, "yes" ).'</div>
			</div>
			
			<div class="flex-container box--child p10 fs-10 graybg-sub table Bold">
			
				<div class="flex-string wp45">Показатель</div>
				<div class="flex-string wp15"></div>
				<div class="flex-string wp20">Текущее</div>
				<div class="flex-string wp20">План</div>
			
			</div>
			'.$string.'
		</div>
		';

	}

}

?>
<style>

	.metrics {

		display               : grid;
		grid-template-columns : 1fr 1fr;
		grid-template-rows    : 1fr;
		grid-gap              : 15px 15px;
		justify-items         : stretch;

	}

</style>

<div class="zagolovok_rep fs-30 mt20 text-center">Metrika. Выполнение KPI</div>

<table class="noborder">
	<tr>
		<td class="w180">

			<div class="ydropDown w160" data-id="month">

				<span title="Месяц"><i class="icon-calendar-1 black"></i></span>
				<span class="yText Bold fs-09">Месяц: </span>
				<span class="ydropText Bold"><?= $lang['face']['MounthName'][ ($month - 1) ] ?></span>
				<i class="icon-angle-down pull-aright arrow"></i>

				<div class="yselectBox" style="max-height: 350px;">

					<?php
					$m = 1;
					while ($m <= 12) {

						print '
						<div class="ydropString yRadio '.($m == $month ? 'bluebg-sub' : '').' text-center">
							<label>
								<input type="radio" name="month" id="month" data-title="'.$lang['face']['MounthName'][ ($m - 1) ].'" value="'.$m.'" class="hidden" '.($m == $month ? 'checked' : '').'>&nbsp;'.$lang['face']['MounthName'][ ($m - 1) ].'
							</label>
						</div>
						';

						$m++;

					}
					?>

				</div>

			</div>

		</td>
		<td class="w120">

			<div class="ydropDown w120" data-id="year">

				<span title="Год"><i class="icon-calendar-1 black"></i></span>
				<span class="yText Bold fs-09">Год: </span>
				<span class="ydropText Bold"><?= $year ?></span>
				<i class="icon-angle-down pull-aright arrow"></i>

				<div class="yselectBox" style="max-height: 350px;">

					<?php
					while ($startYear <= date( 'Y' )) {

						print '
						<div class="ydropString yRadio '.($startYear == $year ? 'bluebg-sub' : '').' text-center">
							<label>
								<input type="radio" name="year" id="year" data-title="'.$startYear.'" value="'.$startYear.'" class="hidden" '.($startYear == $year ? 'checked' : '').'>&nbsp;'.$startYear.'
							</label>
						</div>
						';

						$startYear++;

					}
					?>

				</div>

			</div>

		</td>
		<td></td>
		<td></td>
	</tr>
</table>

<div class="metrics box--child mt20">
	<?= yimplode( "", $ukpi ) ?>
</div>

<div class="space-100"></div>


<script>

	$(document).ready(function () {

		$.Mustache.load('modules/metrics/tpl.metrics.html');

	});

	function viewKPI(id) {

		var url = 'modules/metrics/list.metrics.php';
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

		var url = 'modules/metrics/list.metrics.php';
		var str = 'action=user.kpido&id=' + id + '&iduser=' + iduser + '&year=' + <?=$year?> +'&month=' + <?=$month?>;

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