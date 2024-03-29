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

function dateFormat( $date_orig, $format = 'excel' ) {

	$date_new = '';

	if ( $format == 'excel' ) {

		if ( $date_orig != '0000-00-00' and $date_orig != '' and $date_orig != NULL ) {
			/*
			$dstart = $date_orig;
			$dend = '1970-01-01';
			$date_new = intval((date_to_unix($dstart) - date_to_unix($dend))/86400)+25570;
			*/
			$date_new = $date_orig;
		}
		else $date_new = '';

	}
	elseif ( $format == 'date' ) {

		if ( $date_orig && $date_orig != '0000-00-00' ) {

			$date_new = explode( "-", $date_orig );
			$date_new = $date_new[ 1 ].".".$date_new[ 2 ].".".$date_new[ 0 ];

		}
		else $date_new = '';

	}
	elseif ( $date_orig != '0000-00-00' || $date_orig == '' ) $date_new = '';

	return $date_new;
}

function num2excelExt( $string, $s = 2 ) {

	$string = str_replace( ",", ".", $string );
	$string = str_replace( " ", "", $string );

	$string = number_format( $string, $s, '.', '' );

	return $string;
}

function date2mounthyear( $date ) {
	$date = yexplode( "-", $date );

	return $date[ 0 ]."-".$date[ 1 ];
}

function date2array( $date ) {
	$date = yexplode( "-", $date );

	return [
		$date[ 0 ],
		$date[ 1 ],
		$date[ 2 ]
	];
}

$action = $_REQUEST[ 'action' ];
$da1    = $_REQUEST[ 'da1' ];
$da2    = $_REQUEST[ 'da2' ];

$user_list    = (array)$_REQUEST['user_list'];

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$sort       = '';
$dogs       = [];
$dirs       = [];
$dirc       = [];
$directions = [];
$dataset1   = [];
$order      = [];

$colors = [
	"#27AE60",
	"#2980B9",
	"#F1C40F",
	"#9B59B6",
	"#95A5A6"
];

$thisfile = basename( $_SERVER[ 'PHP_SELF' ] );

//массив выбранных пользователей
if (!empty($user_list)) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid'
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

$format = ( $action == 'export' ) ? 'excel' : 'date';

//массив направлений
$directions = $db -> getIndCol( "id", "SELECT id, title FROM ".$sqlname."direction WHERE identity = '$identity'" );

if ( $action == "loaddata" ) {

	$direction = array_search( $_REQUEST[ 'direction' ], $directions );
	$date      = $_REQUEST[ 'date' ];

	$q = "
	SELECT
		deal.did as did,
		deal.title as dogovor,
		deal.datum as dcreate,
		deal.datum_plan as dplan,
		deal.datum_close as dclose,
		deal.idcategory as idstep,
		deal.tip as tip,
		deal.clid as clid,
		deal.pid as pid,
		deal.kol as kol,
		deal.marga as marga,
		deal.kol_fact as kolf,
		deal.close as close,
		deal.iduser as iduser,
		deal.adres as adres,
		deal.content as content,
		deal.direction as direction,
		us.title as user,
		cc.title as client,
		dc.title as step,
		dc.content as steptitle,
		dt.title as tips,
		dir.title as dirs,
		ds.title as dstatus
	FROM ".$sqlname."dogovor `deal`
		LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
		LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
		LEFT JOIN ".$sqlname."dogtips `dt` ON deal.tip = dt.tid
		LEFT JOIN ".$sqlname."dogstatus `ds` ON deal.sid = ds.sid
		LEFT JOIN ".$sqlname."direction `dir` ON deal.direction = dir.id
	WHERE
		DATE_FORMAT(deal.datum_close, '%Y.%m') = '$date' and
		deal.direction = '".$direction."' and
		deal.close = 'yes' and
		$sort
		deal.identity = '$identity'
	ORDER BY deal.datum_close";

	$result = $db -> query( $q );
	while ( $data = $db -> fetch( $result ) ) {

		$color = '';
		$icon  = '';
		$kolf  = '';
		$dfact = '';

		if ( $data[ 'close' ] == 'yes' ) {
			$dfact = $data[ 'dclose' ];
			$icon  = '<i class="icon-lock red"></i>';
			$kolf  = $data[ 'kolf' ];
		}
		else {
			$icon  = '<i class="icon-briefcase blue"></i>';
		}

		//цветовая схема
		if ( $data[ 'close' ] == 'yes' && $data[ 'kolf' ] > 0 ) $color = 'greenbg-sub';
		if ( $data[ 'close' ] == 'yes' && $data[ 'kolf' ] == 0 ) $color = 'redbg-sub';

		//Здоровье сделки. конец.
		$dogs[] = [
			"datum"     => dateFormat( $data[ 'dcreate' ], $format ),
			"did"       => $data[ 'did' ],
			"dogovor"   => $data[ 'dogovor' ],
			"tip"       => $data[ 'tips' ],
			"direction" => $data[ 'dirs' ],
			"step"      => $data[ 'step' ],
			"dplan"     => dateFormat( $data[ 'dplan' ], $format ),
			"dfact"     => dateFormat( $dfact, $format ),
			"client"    => $data[ 'client' ],
			"clid"      => $data[ 'clid' ],
			"summa"     => $data[ 'kol' ],
			"fsumma"    => $kolf,
			"marga"     => $data[ 'marga' ],
			"user"      => $data[ 'user' ],
			"close"     => $data[ 'close' ],
			"comment"   => $data[ 'dstatus' ],
			"color"     => $color,
			"icon"      => $icon
		];

	}

	print json_encode_cyr( $dogs );

	exit();

}

$q = "
SELECT
	deal.did as did,
	deal.datum_close as dclose,
	deal.kol as kol,
	deal.marga as marga,
	deal.kol_fact as kolf,
	deal.direction as direction
FROM ".$sqlname."dogovor `deal`
	LEFT JOIN ".$sqlname."direction `dir` ON deal.direction = dir.id
WHERE
	deal.datum_close BETWEEN '".$da1." 00:00:01' and '".$da2." 23:59:59' and
	deal.close = 'yes' and
	$sort
	deal.identity = '$identity'
ORDER BY deal.datum_close";

$result = $db -> query( $q );
while ( $data = $db -> fetch( $result ) ) {

	$dirs[ date2mounthyear( $data[ 'dclose' ] ) ][ $data[ 'direction' ] ] += $data[ 'kolf' ];
	$dirc[ $data[ 'direction' ] ]                                         += $data[ 'kolf' ];

}

//разобъем по месяцам
$start = date2array( $da1 );
$end   = date2array( $da2 );
$today = date2array( current_date() );

$datas = $order = [];

//проходим годы и формируем основной массив для Chart1
for ( $year = $start[ 0 ]; $year <= $end[ 0 ]; $year++ ) {

	//проходим месяцы
	for ( $mon = 1; $mon <= 12; $mon++ ) {

		if ( $mon < 10 ) $mon1 = "0".$mon;
		else $mon1 = $mon;

		//print $mon . '-' . $year."\r";

		foreach ( $directions as $k => $val ) {

			if ( $year == $today[ 0 ] and $mon > $today[ 1 ] ) goto a1;
			if ( $year == $today[ 0 ] and $mon < $start[ 1 ] ) goto a1;
			if ( $year == $today[ 0 ] and $mon > $end[ 1 ] ) goto a1;

			if ( $dirs[ $year.'-'.$mon1 ][ $k ] == '' ) $dirs[ $year.'-'.$mon1 ][ $k ] = "0.00";

			$datas[] = '{Дата:"'.$year.'.'.$mon1.'","Направление":"'.$val.'","Сумма":"'.pre_format( $dirs[ $year.'-'.$mon1 ][ $k ] ).'"}';

			$data[ $mon1.'.'.$year ][] = [
				"date"      => $mon1.'.'.$year,
				"datee"     => $year.'.'.$mon1,
				"direction" => $val,
				"summa"     => $dirs[ $year.'-'.$mon1 ][ $k ]
			];

			$order[] = $year.'.'.$mon1;

			a1:

		}

	}

}

$datas = implode( ",", $datas );
$order = implode( ",", $order );

$datac = [];

//проходим направления и формируем данные для Chart2
$i = 0;
foreach ( $directions as $key => $val ) {

	if ( $dirc[ $key ] > 0 ) {

		$datac[] = '{"Отчет":"Результат","Направление":"'.$val.'","Сумма":"'.$dirc[ $key ].'"}';

		$dataset1[] = '{value: '.$dirc[ $key ].', color: "'.$colors[ $i ].'", highlight: "rgba(41,128,185,0.5)", label: "'.$val.'"}';

		$i++;

	}

}

$datac    = implode( ",", $datac );
$dataset1 = implode( ",", $dataset1 );
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

	.cherta-top:not(:first-child) td {
		border-top : 1px dotted #222;
	}
</style>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Анализ направлений</h1>
	<div class="gray2">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<hr>

<div class="row">
	<div id="chart1" style="height: 300px" class="column12 grid-8"></div>
	<div id="chart2" style="height: 300px" class="column12 grid-3"></div>
</div>

<div class="mt10 mb20">

	<div class="cardBlock" style="height: 160px; overflow: hidden" data-height="160">

		<table>
			<thead class="sticked--top1">
			<tr class="">
				<th class="w60">Период</th>
				<th class="w160 text-right">Сумма</th>
				<th class="text-left">Направление</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $data as $period => $list ) {

				$tr  = '';
				$sum = 0;

				foreach ( $list as $da ) {

					if ( $da[ 'summa' ] > 0 ) {

						$tr .= '
						<tr class="ha hand" onclick="showData(\''.$da[ 'direction' ].'\',\''.$da[ 'datee' ].'\')">
							<td class="Bold fs-11"></td>
							<td class="text-right '.( $da[ 'summa' ] == 0 ? 'gray' : 'Bold' ).'">'.num_format( $da[ 'summa' ] ).'</td>
							<td>'.$da[ 'direction' ].'</td>
						</tr>';

						$sum += $da[ 'summa' ];

					}

				}

				if($sum > 0)
				print '
				<tr class="'.( $sum == 0 ? 'graybg' : 'bluebg' ).' cherta-top">
					<td class="Bold fs-12">'.$period.'</td>
					<td class="text-right Bold fs-12 '.( $sum == 0 ? 'gray2' : 'white' ).'">'.num_format( $sum ).'</td>
					<td></td>
				</tr>';

				print $tr;

			}
			?>
			</tbody>
		</table>

	</div>
	<div class="div-center blue hand cardResizer fs-07 hidden6" title="Развернуть" data-pozi="close">
		<i class="icon-angle-down"></i>развернуть / свернуть<i class="icon-angle-down"></i></div>
</div>

<div class="data hidden" id="adata1">

	<a id="adata"></a>

	<table>
		<thead>
		<tr>
			<th class="w30">#</th>
			<th class="yw200">Сделка / Клиент</th>
			<th class="w100">Направление</th>
			<th class="w100">Маржа</th>
			<th class="w100">Сумма</th>
			<th class="w80">Дата факт</th>
			<th class="w100">Ответственный</th>
			<th class="">Примечание</th>
		</tr>
		</thead>
		<tbody></tbody>
		<tfoot></tfoot>
	</table>
</div>

<div style="height:80px"></div>

<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->

<!--<script src="js/Chart.js"></script>-->

<script>
	$(function () {
		drawChart1();
		drawChart2();
		//drawChart3();
	});

	$(window).bind('resizeEnd', function () {
		drawChart1();
		drawChart2();
		//drawChart3();
	});

	function drawChart1() {

		var width = $('#chart').width() - 40;
		var height = 350;
		var svg = dimple.newSvg("#chart1", "100%", "350px");
		var data = [<?=$datas?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width - 50, height - 40);

		var x = myChart.addCategoryAxis("x", "Дата", "%m-%Y", "%m.%Y");
		x.addOrderRule("Дата");//порядок вывода, иначе группирует
		x.showGridlines = false;

		var y = myChart.addMeasureAxis("y", "Сумма");
		y.showGridlines = true;//скрываем линии

		myChart.defaultColors = [
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#795548", "#795548"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#03A9F4", "#03A9F4"),
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
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		var s = myChart.addSeries(["Направление"], dimple.plot.bar);
		s.stacked = true;

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		var myLegend = myChart.addLegend(width - 200, 40, 200, 250, "right");
		myChart.setMargins(60, 20, 40, 70);

		s.addEventHandler("click", function (e) {
			showData(e.seriesValue[0], e.xValue);
		});

		myChart.draw(1000);

		y.titleShape.remove();
		x.titleShape.remove();

		var filterValues = dimple.getUniqueValues(data, "Направление");
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
			myChart.data = dimple.filterData(data, "Направление", filterValues);
			myChart.draw(800);
		});

		y.tickFormat = ".2f";
		s.shapes.style("opacity", function (d) {
			return (d.y === null ? 0 : 0.8);
		});

	}

	function drawChart2() {

		var width = $('#chart2').width();
		var height = 300;
		var svg = dimple.newSvg("#chart2", width, height);
		var data = [<?=$datac?>];

		var myChart2 = new dimple.chart(svg, data);

		myChart2.defaultColors = [
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#795548", "#795548"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#03A9F4", "#03A9F4"),
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
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		myChart2.setBounds(20, 50, 300, 300);

		myChart2.addMeasureAxis("p", "Сумма");
		var ring = myChart2.addSeries("Направление", dimple.plot.pie);

		ring.innerRadius = "50%";

		//var myLegend = myChart2.addLegend(0, 0, 100, 100, "left");
		myChart2.setMargins(20, 20, 60, 20);
		myChart2.draw(1000);

	}

	function showData(direction, date) {

		//$(".nano").nanoScroller({ scrollTo: $('#adata') });

		$('.data').removeClass('hidden').find('table tbody').empty().append('<img src="/assets/images/loading.gif">');
		$('.data').find('tfoot').empty();

		$.get('reports/<?=$thisfile?>?action=loaddata&date=' + date + '&direction=' + direction, function (data) {

			var s = '';
			var f = '';
			var number = 0;
			var summa = 0;
			var marga = 0;
			var dsumma;

			for (var i in data) {

				number = parseInt(i) + 1;

				s = s +
					'<tr height="40" class="ha">' +
					'   <td align="center" class="fs-09">' + number + '</td>' +
					'   <td align="left"><div class="ellipsis Bold fs-12"><a href="javascript:void(0)" onclick="viewDogovor(\'' + data[i].did + '\')"><i class="icon-briefcase-1 blue"></i>&nbsp;' + data[i].dogovor + '</a></div><br><div class="ellipsis mt5 gray2"><a href="javascript:void(0)" onclick="openClient(\'' + data[i].clid + '\')" title=""><i class="icon-building broun"></i>&nbsp;' + data[i].client + '</a></div></td>' +
					'   <td><div class="ellipsis">' + data[i].direction + '</div></td>' +
					'   <td align="right">' + number_format(data[i].marga, 2, ',', ' ') + '</td>' +
					'   <td align="right">' + number_format(data[i].fsumma, 2, ',', ' ') + '</td>' +
					'   <td align="center">' + data[i].dfact + '</td>' +
					'   <td align="left"><div class="ellipsis">' + data[i].user + '</div></td>' +
					'   <td align="left"><span class="fs-09">' + data[i].comment + '</span></td>' +
					'</tr>';

				summa = summa + parseFloat(data[i].fsumma);
				marga = marga + parseFloat(data[i].marga);
			}

			f =
				'<tr height="40" class="ha bluebg-sub">' +
				'   <td align="center" class="fs-09"></td>' +
				'   <td align="left"></td>' +
				'   <td></td>' +
				'   <td align="right"><b>' + number_format(marga, 2, ',', ' ') + '</b></td>' +
				'   <td align="right"><b>' + number_format(summa, 2, ',', ' ') + '</b></td>' +
				'   <td align="center"></td>' +
				'   <td align="left"></td>' +
				'   <td align="left"></td>' +
				'</tr>';

			//console.log(s);

			$('.data').find('tbody').empty().html(s);
			$('.data').find('tfoot').empty().html(f);

			//$(".nano").nanoScroller();

			var wcoffset = $('#adata1').offset();
			$(".nano").nanoScroller({scrollTop: (wcoffset.top + 50)});

		}, 'json');

	}

	function exportDeal() {

		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);
	}

	function number_format(number, decimals, dec_point, thousands_sep) {
		// Format a number with grouped thousands
		//
		// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +	 bugfix by: Michael White (http://crestidg.com)

		var i, j, kw, kd, km;

		// input sanitation & defaults
		if (isNaN(decimals = Math.abs(decimals))) {
			decimals = 2;
		}
		if (dec_point == undefined) {
			dec_point = ",";
		}
		if (thousands_sep == undefined) {
			thousands_sep = ".";
		}

		i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

		if ((j = i.length) > 3) {
			j = j % 3;
		}
		else {
			j = 0;
		}

		km = (j ? i.substr(0, j) + thousands_sep : "");
		kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
		//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
		kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


		return km + kw + kd;
	}
</script>