<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

use Salesman\Guides;

?>
<?php
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

$user_list   = (array)$_REQUEST['user_list'];
$field       = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];
$period      = $_REQUEST['period'];

$mc = $_REQUEST['mc'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort   = '';
$susers = '';
$kolSum = 0;
$list   = $users = $summas = $order = [];
$datas = [];

$thisfile = basename( $_SERVER['PHP_SELF'] );

function getDateCustom($date): string {
	$d = yexplode( " ", $date );

	return "<b>".format_date_rus( $d[0] )."</b> (".getTime( $d[1] ).")";
}

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= "cr.iduser IN (".yimplode( ",", $user_list ).") and ";
}
else {
	$sort .= "cr.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and ";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close',
	'mcid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.{$field} = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$mycomps = Guides ::myComps();

$sort .= ($mc > 0) ? "cr.rs IN (SELECT id FROM ".$sqlname."mycomps_recv WHERE ".$sqlname."mycomps_recv.cid = '$mc') and " : "";
$sort .= $da1 != "" ? "cr.invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and" : "";

if ( $action == "export" ) {

	$q = "
	SELECT
		cr.did as did,
		cr.iduser as iduser,
		cr.summa_credit as summa,
		cr.datum_credit as dplan,
		cr.invoice_date as dfact,
		DATE_FORMAT(cr.datum, '%Y-%m-%d') as idatum,
		cr.invoice as invoice,
		cr.clid as clid,
		deal.datum as datum,
		deal.title as dogovor,
		deal.kol as dsumma,
		deal.marga as dmarga,
		deal.iduser as diduser,
		deal.close as close,
		dr.title as direction,
		cc.title as client,
		(SELECT cid FROM ".$sqlname."mycomps_recv WHERE ".$sqlname."mycomps_recv.id = cr.rs) as mc,
		us.title as user
	FROM ".$sqlname."credit `cr`
		LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
		LEFT JOIN ".$sqlname."clientcat `cc` ON cc.clid = cr.clid
		LEFT JOIN ".$sqlname."user `us` ON us.iduser = cr.iduser
		LEFT JOIN ".$sqlname."direction `dr` ON deal.direction = dr.id
	WHERE
		cr.do = 'on' and
		$sort
		cr.identity = '$identity'
	ORDER by cr.invoice_date";

	$da = $db -> getAll( $q );

	foreach ( $da as $data ) {

		$list[] = [
			"id"        => $data['did'],
			"invoice"   => $data['invoice'],
			"idatum"    => $data['idatum']." 12:00:00",
			"datum"     => $data['datum']." 12:00:00",
			"summa"     => $data['summa'],
			"dsumma"    => $data['dsumma'],
			"dmarga"    => $data['dmarga'],
			"deal"      => $data['dogovor'],
			"direction" => $data['direction'],
			"client"    => $data['client'],
			"user"      => $data['user'],
			"dfact"     => $data['dfact']." 12:00:00",
			"dplan"     => $data['dplan']." 12:00:00",
			"mc"        => $mycomps[ $data['mc'] ]
		];

	}

	$data = ["list" => $list];

	//print_r($data);

	//include_once '../opensource/tbs_us/tbs_class.php';
	//include_once '../opensource/tbs_us/plugins/tbs_plugin_opentbs.php';
	
	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

	$templateFile = 'templates/paymentTemp.xlsx';
	$outputFile   = 'exportPayments.xlsx';

	$TBS = new clsTinyButStrong(); // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data['list'] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

if ( !$action ) {

	$q = "
	SELECT
		cr.did as did,
		cr.iduser as iduser,
		cr.summa_credit as summa,
		cr.datum_credit as dplan,
		cr.invoice_date as dfact,
		DATE_FORMAT(cr.invoice_date, '%m.%y') as datum,
		cr.invoice as invoice,
		cr.clid as clid,
		deal.title as dogovor,
		deal.kol as dsumma,
		deal.marga as dmarga,
		deal.iduser as diduser,
		deal.close as close,
		cc.title as client,
		(SELECT cid FROM ".$sqlname."mycomps_recv WHERE ".$sqlname."mycomps_recv.id = cr.rs) as mc,
		us.title as user
	FROM ".$sqlname."credit `cr`
		LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
		LEFT JOIN ".$sqlname."clientcat `cc` ON cc.clid = cr.clid
		LEFT JOIN ".$sqlname."user `us` ON us.iduser = cr.iduser
	WHERE
		cr.do = 'on' and
		$sort
		cr.identity = '$identity'
	ORDER by cr.invoice_date";

	$da = $db -> getAll( $q );

	foreach ( $da as $data ) {

		$list[ $data['iduser'] ][] = [
			"invoice" => $data['invoice'],
			"summa"   => $data['summa'],
			"dogovor" => $data['dogovor'],
			"clid"    => $data['clid'],
			"client"  => $data['client'],
			"user"    => $data['user'],
			"dfact"   => $data['dfact'],
			"dplan"   => $data['dplan'],
			"did"     => $data['did'],
			"mc"      => $mycomps[ $data['mc'] ]
		];

		$dd[ $data['iduser'] ][ $data['datum'] ] += $data['summa'];

		$summas[ $data['iduser'] ] += $data['summa'];

		$users[ $data['iduser'] ] = $data['user'];

		$order[] = '"'.$data['datum'].'"';

	}

	foreach ( $dd as $user => $date ) {

		foreach ( $date as $datum => $value ) {

			$u = yexplode( " ", $users[ $user ] );
			$u = $u[0]." ".substr( $u[1], 0, 2 ).".";

			$datas[] = '{"Куратор":"'.$u.'", "Дата":"'.$datum.'", "Сумма":"'.$value.'"}';

		}

	}

	$datas = implode( ",", $datas );
	$order = implode( ",", $order );

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
			height : 45px;
			cursor : pointer;
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

		@media print {
			.fixAddBotButton {
				display : none;
			}
		}

		-->
	</style>

	<div class="relativ mt20 mb20 wp95 text-center">
		<h1 class="uppercase fs-14 m0 mb10">Поступившие оплаты по сотрудникам [ по сотрудникам ]</h1>
		<div class="gray2">за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?>
			<span class="hidden1 Bold">[ <a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel для Roistat" class="blue">Excel</a> ]</span>
		</div>
	</div>

	<hr>

	<div id="graf" style="display:block; height:350px">

		<div id="chart" style="padding:5px; height:100%"></div>

	</div>

	<hr>

	<div class="block mt10">

		<TABLE width="100%" cellpadding="5" cellspacing="0">
			<thead class="sticked--top text-center">
			<TR>
				<th class="w20"><b>#</b></th>
				<th class="w120"><b>Счет</b></th>
				<th class="w120"><b>Дата план</b></th>
				<th class="w120"><b>Дата факт</b></th>
				<th class="w120"><b>Сумма</b></th>
				<th><b>Сделка</b></th>
				<th><b>Заказчик</b></th>
			</TR>
			</thead>
			<tbody>
			<?php
			foreach ( $list as $user => $udata ) {

				print '
				<tr class="td--main bluebg-sub">
					<td colspan="7">
						<div class="Bold blue fs-11 inline">'.$users[ $user ].'</div> [ Сумма: <span class="Bold gray2">'.num_format( $summas[ $user ] ).'</span> ]
					</td>
				</tr>
				';

				$num = 1;
				foreach ( $udata as $datum => $val ) {

					//if ($num & 1) $color = 'color1';
					//else $color = 'color2';

					print '
					<tr class="datetoggle td--main '.$color.'" data-key="'.$user.'">
						<td class="text-right">'.$num.'</td>
						<td class="text-center"><b>'.$val['invoice'].'</b></td>
						<td>'.get_date( $val['dplan'] ).'</td>
						<td>'.get_date( $val['dfact'] ).'</td>
						<td class="text-right">'.num_format( $val['summa'] ).'</td>
						<td><div class="ellipsis"><A href="javascript:void(0)" onclick="openDogovor(\''.$val['did'].'\')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>&nbsp;'.$val['dogovor'].'</A></div></td>
						<td><div class="ellipsis"><A href="javascript:void(0)" onclick="openClient(\''.$val['clid'].'\')"><i class="icon-building broun"></i>&nbsp;'.$val['client'].'</A></div></td>
					</tr>
					';

					$num++;
				}

			}
			?>
			</tbody>
		</TABLE>

	</div>

	<div style="height: 100px"></div>

	<script src="/assets/js/dimple.js/dimple.min.js"></script>
	<!--<script src="/assets/js/d3.min.js"></script>-->
	<script>

		drowChart();

		$('.datetoggle').on('click', function () {

			var key = $(this).data('key');

			$('tr.sub').not('[data-date="' + key + '"]').addClass('hidden');
			$('tr.sub[data-date="' + key + '"]').toggleClass('hidden');

			$(this).find('i:first').toggleClass('icon-plus-circled icon-minus-circled');

		});

		function drowChart() {

			$('#chart').empty();

			var width = $('#chart').width() - 200;
			var height = 400;
			var svg = dimple.newSvg("#chart", "100%", "100%");
			var data = [<?=$datas?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(0, 0, width - 50, height - 40);

			var x = myChart.addCategoryAxis("x", ["Дата"]);
			x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart.addMeasureAxis("y", "Сумма");
			y.showGridlines = false;//скрываем линии
			y.tickFormat = ",.2f";

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

			var s = myChart.addSeries(["Дата", "Куратор"], dimple.plot.bar);

			s.barGap = 0.7;
			s.stacked = true;
			//s.lineWeight = 1;
			//s.lineMarkers = true;

			myChart.clamp = true;
			myChart.floatingBarWidth = 2;

			myChart.ease = "bounce";
			myChart.staggerDraw = true;

			//x.shapes.selectAll("text").attr("fill", "#FF0000");

			//myChart.barGap = 0.5;
			var myLegend = myChart.addLegend(width + 10, 0, 100, 250, "right");
			myChart.setMargins(100, 20, 200, 50);
			//myChart.setMargins(80, 20, 200, 60);

			/*s.addEventHandler("click", function (e) {
			 showData(e.xValue);
			 });*/

			//x.shapes.selectAll("text").css("word-wrap","wrap-all");

			myChart.draw(1000);

			//y.titleShape.remove();
			x.titleShape.remove();

			myChart.legends = [];

			var filterValues = dimple.getUniqueValues(data, "Куратор");
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
				y.tickFormat = ".f";
				filterValues = newFilters;
				myChart.data = dimple.filterData(data, "Куратор", filterValues);
				myChart.draw(800);
			});

			window.addEventListener('resize', function () {
				//myChart.draw(0, true);
			});

		}

		function Toggle(date) {

			$('tr.sub').addClass('hidden');
			$('tr[data-date="' + date + '"]').toggleClass('hidden show');

		}

		function ToggleAll() {

			var state = $('.fixAddBotButton').data('state');

			//console.log(state);

			if (state == 'collapse') {
				$('.fixAddBotButton').data('state', 'expand');
				$('.fixAddBotButton').find('span').html('Свернуть всё');
				$('.fixAddBotButton').find('i:first').removeClass('icon-plus').addClass('icon-minus');
				$('tr.sub').removeClass('hidden');
			}
			if (state == 'expand') {
				$('.fixAddBotButton').data('state', 'collapse');
				$('.fixAddBotButton').find('span').html('Развернуть всё');
				$('.fixAddBotButton').find('i:first').addClass('icon-plus').removeClass('icon-minus');
				$('tr.sub').addClass('hidden');
			}
		}

		function Export() {
			var str = $('#selectreport').serialize();
			window.open('reports/<?=$thisfile?>?action=export&' + str + '&da1=<?=$da1?>&da2=<?=$da2?>&period=' + $('#swPeriod').val());
		}

	</script>
<?php } ?>