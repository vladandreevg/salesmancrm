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

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action   = $_REQUEST['action'];
$da1      = $_REQUEST['da1'];
$da2      = $_REQUEST['da2'];
$users    = (array)$_REQUEST['user_list'];
$relation = $_REQUEST['relation'];

$thisfile = basename( $_SERVER['PHP_SELF'] );
$prefix   = $rootpath."/";

//Формируем доп.параметры запроса
$sd = get_people( $iduser1, 'yes' );

$so = $sort = '';

//массив каналов
$channel    = [];
$channel[0] = 'Не указан';

$result = $db -> query( "SELECT * FROM {$sqlname}relations WHERE identity = '$identity'" );
while ($daz = $db -> fetch( $result )) {

	$channel[ $daz['id'] ] = $daz['title'];

}

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$so .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$so .= " deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

$list = $datas = [];

if ( $action == 'view' ) {

	if ( $relation == 'Unknown' ) {
		$cp = "rl.tip_cmr is null AND";
	}
	else {
		$cp = "rl.title = '$relation' AND";
	}

	if ( !$otherSettings['credit'] ) {

		$q = "
		SELECT
			deal.did as did,
			deal.datum_close as dclose,
			deal.kol_fact as summa,
			deal.marga as marga,
			deal.iduser as iduser,
			deal.title as dogovor,
			deal.clid as clid,
			us.title as user,
			cc.title as client,
			rl.title as relation
		FROM {$sqlname}dogovor `deal`
			LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
			LEFT JOIN {$sqlname}relations `rl` ON rl.title = {$sqlname}clientcat.tip_cmr
			LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
		WHERE
			deal.did > 0 and
			COALESCE(deal.close, 'no') = 'yes' and
			deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			$cp
			$so
			deal.identity = '$identity'
		ORDER BY us.title, deal.datum_close
		";

		$rez = $db -> query( $q );
		while ($daz = $db -> fetch( $rez )) {

			if ( $daz['relation'] == NULL ) {
				$daz['relation'] = 'Unknown';
			}

			$list[] = [
				"did"      => $daz['did'],
				"dogovor"  => $daz['dogovor'],
				"dclose"   => format_date_rus( $daz['dclose'] ),
				"summa"    => $daz['summa'],
				"marga"    => $daz['marga'],
				"clid"     => $daz['clid'],
				"client"   => $daz['client'],
				"user"     => $daz['user'],
				"relation" => $daz['relation'],
				"comment"  => 'Закрытая сделка'
			];

		}

	}

	if ( $otherSettings['credit'] ) {

		//выполнение планов по оплатам
		if ( !$otherSettings['planByClosed'] ) {

			$q = "
			SELECT
				cr.did as did,
				cr.iduser as iduser,
				SUM(DISTINCT cr.summa_credit) as summa,
				deal.title as dogovor,
				deal.kol as dsumma,
				deal.marga as dmarga,
				deal.iduser as diduser,
				deal.close as close,
				deal.datum_close as dclose,
				deal.clid as clid,
				us.title as user,
				cc.title as client,
				rl.title as relation
			FROM {$sqlname}credit `cr`
				LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
				LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
				LEFT JOIN {$sqlname}relations `rl` ON rl.title = cc.tip_cmr
				LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
			WHERE
				cr.do = 'on' and
				cr.invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
				$cp
				".str_replace( "deal", "cr", $so )."
				cr.identity = '$identity'
			GROUP BY deal.did
			ORDER by cr.invoice_date";
		}

		//выполнение учет только оплат по закрытым сделкам в указанном периоде
		if ( $otherSettings['planByClosed'] ) {

			$q = "
			SELECT
				cr.did as did,
				cr.iduser as iduser,
				SUM(DISTINCT cr.summa_credit) as summa,
				deal.title as dogovor,
				deal.kol as dsumma,
				deal.marga as dmarga,
				deal.iduser as diduser,
				deal.close as close,
				deal.datum_close as dclose,
				deal.clid as clid,
				us.title as user,
				cc.title as client,
				rl.title as relation
			FROM {$sqlname}credit `cr`
				LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
				LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
				LEFT JOIN {$sqlname}relations `rl` ON rl.title = cc.tip_cmr
				LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
			WHERE
				cr.do = 'on' and
				COALESCE(deal.close, 'no') = 'yes' and
				deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
				$cp
				$so
				cr.identity = '$identity'
			GROUP BY deal.did
			ORDER by cr.invoice_date";

		}

		$rez = $db -> query( $q );
		while ($daz = $db -> fetch( $rez )) {

			$marga = 0;
			$proc  = 0;

			if ( $daz['relation'] == NULL ) {
				$daz['relation'] = 'Unknown';
			}

			if ( $daz['dclose'] == '0000-00-00' ) {
				$daz['dclose'] = '-';
			}
			else {
				$daz['dclose'] = format_date_rus( $daz['dclose'] );
			}

			$marga = $daz['dmarga'] / $daz['dsumma'] * $daz['summa'];
			$proc  = $daz['summa'] / $daz['dsumma'] * 100;

			if ( $proc < 100 ) {
				$color = "red";
			}
			else {
				$color = "green";
			}

			$comment = 'Оплачено <b>'.num_format( $daz['summa'] ).'</b><br>Доля: <b class="'.$color.'">'.num_format( $proc ).'%</b>';

			$list[] = [
				"did"      => $daz['did'],
				"dogovor"  => $daz['dogovor'],
				"dclose"   => $daz['dclose'],
				"dsumma"   => $daz['dsumma'],
				"summa"    => $daz['summa'],
				"marga"    => $marga,
				"clid"     => $daz['clid'],
				"client"   => $daz['client'],
				"user"     => $daz['user'],
				"relation" => $daz['relation'],
				"comment"  => $comment
			];

		}

	}

	print json_encode_cyr( $list );

	exit();
}

//расчет по закрытым сделкам
if ( !$otherSettings['credit'] ) {

	$q = "
	SELECT
		rl.id,
		SUM(DISTINCT deal.kol_fact) as summa,
		SUM(DISTINCT deal.marga) as marga,
		COUNT(deal.did) as count,
		rl.title as relation
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN {$sqlname}relations `rl` ON {$sqlname}relations.title = cc.tip_cmr
	WHERE
		deal.did > 0 and
		COALESCE(deal.close, 'no') = 'yes' and
		deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
		$so
		rl.identity = '$identity' and
		deal.identity = '$identity'
	GROUP BY rl.title
	";

	$rez = $db -> query( $q );
	while ($daz = $db -> fetch( $rez )) {

		if ( $daz['relation'] == '' )
			$daz['relation'] = 'Не указан';

		$list[] = [
			"id"       => $daz['id'],
			"relation" => $daz['relation'],
			"summa"    => pre_format( $daz['summa'] ),
			"marga"    => pre_format( $daz['marga'] ),
			"count"    => $daz['count']
		];

		$datas[] = '{"Тип":"'.$daz['relation'].'", "Сумма":"'.pre_format( $daz['summa'] ).'", "id":"'.$daz['id'].'"}';

	}

	//print_r($list);

}

if ( $otherSettings['credit'] ) {

	$cpath = [];

	//выполнение планов по оплатам
	if ( !$otherSettings['planByClosed'] ) {

		$q = "
		SELECT
			cr.crid as crid,
			rl.id,
			rl.title as relation,
			cr.did as did,
			SUM(DISTINCT cr.summa_credit) as summa,
			deal.kol as dsumma,
			deal.marga as dmarga
		FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
			LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
			LEFT JOIN {$sqlname}relations `rl` ON rl.title = cc.tip_cmr
		WHERE
			cr.do = 'on' and
			cr.invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			rl.identity = '$identity' and
			".str_replace( "deal", "cr", $so )."
			cr.identity = '$identity'
		GROUP BY rl.title
		";
	}

	//выполнение учет только оплат по закрытым сделкам в указанном периоде
	if ( $otherSettings['planByClosed'] ) {
		$q = "
		SELECT
			DISTINCT cr.crid as crid,
			rl.id as id,
			rl.title as relation,
			cr.did as did,
			cr.summa_credit as summa,
			deal.title as dogovor,
			deal.kol as dsumma,
			deal.marga as dmarga
		FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
			LEFT JOIN {$sqlname}clientcat  `cc` deal.clid = cc.clid
			LEFT JOIN {$sqlname}relations `rl` ON rl.title = cc.tip_cmr
		WHERE
			cr.do = 'on' and
			COALESCE(deal.close, 'no') = 'yes' and
			deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			cr.identity = '$identity' and
			$so
			rl.identity = '$identity'
		GROUP BY cr.crid
		ORDER BY rl.title
		";
	}

	//проходим все сделки и считаем по нима сумму оплат и маржу
	$rez = $db -> getAll( $q );
	foreach ( $rez as $daz ) {

		if ( $daz['id'] == NULL )
			$daz['id'] = '0';

		$marga = $daz['dmarga'] / $daz['dsumma'] * $daz['summa'];

		$cpath[ $daz['id'] ]['summa'] += $daz['summa'];
		$cpath[ $daz['id'] ]['marga'] += $marga;

		$cpath[ $daz['id'] ]['count']++;

	}

	//print_r($cpath);

	//формируем конечный массив
	foreach ( $cpath as $key => $val ) {

		if ( $key == '' ) {
			$relation = 'Unknown';
		}
		else {
			$relation = $channel[$key];
		}

		$list[] = [
			"id"       => $key,
			"relation" => $relation,
			"summa"    => pre_format( $val['summa'] ),
			"marga"    => pre_format( $val['marga'] ),
			"count"    => $val['count']
		];

		$datas[] = '{"Тип":"'.$relation.'", "Сумма":"'.pre_format( $val['summa'] ).'", "id":"'.$key.'"}';

	}

	//print_r($datas);
	//print_r($list);

}

$xdatas = implode( ",", $datas );

if ( !$otherSettings['credit'] ) {
	$text = '<li>В отчет попадают ВСЕ <b>активные</b> сделки и <b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем</li>';
}
if ( $otherSettings['credit'] && !$otherSettings['planByClosed'] ) {
	$text = '<li>Расчеты строятся по <b>оплаченным счетам в периоде</b> в соответствии с настройками системы</li>';
}
if ( $otherSettings['credit'] && $otherSettings['planByClosed'] ) {
	$text = '<li>Расчеты строятся по <b>оплаченным счетам</b> в Сделках, <b>закрытых в отчетном периоде</b> в соответствии с настройками системы</li>';
}
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

	<div class="relativ mt20 mb20 wp95 text-center">
		<h1 class="uppercase fs-14 m0 mb10">Анализ продаж по Типам отношений</h1>
		<div class="gray2">за период&nbsp;с&nbsp;<?= format_date_rus($da1) ?>&nbsp;по&nbsp;<?= format_date_rus($da2) ?></div>
	</div>

	<hr>

	<div id="graf" style="display:block; height:350px">

		<div id="chart" style="padding:5px; height:100%"></div>

	</div>

	<div class="data hidden">

		<hr>

		<table>
			<thead>
			<tr class="header_contaner">
				<th class="w30">#</th>
				<th class="yw200">Сделка / Клиент</th>
				<th class="w100">Тип</th>
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

	<hr>
	<div class="pad10 infodiv">
		<ul>
			<?= $text ?>
		</ul>

	</div>

	<div class="h35"></div>
	<div class="h35"></div>
	<div class="h35"></div>

<?php
if ( count( $datas ) > 0 ) {
	?>
	<script src="/assets/js/dimple.js/dimple.min.js"></script>
	<!--<script src="/assets/js/d3.min.js"></script>-->
	<script>

		drowChart();

		function drowChart() {

			var width = $('#chart').width() - 200;
			var height = 250;
			var svg = dimple.newSvg("#chart", "100%", "100%");
			var data = [<?=$xdatas?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(0, 0, width - 50, height - 40);

			var x = myChart.addCategoryAxis("x", ["Тип"]);
			x.showGridlines = false;

			var y = myChart.addMeasureAxis("y", "Сумма");
			y.showGridlines = true;//скрываем линии
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

			var s = myChart.addSeries(["Тип"], dimple.plot.bar);

			s.stacked = true;
			//s.lineWeight = 1;
			//s.lineMarkers = true;

			//myChart.clamp = true;
			//myChart.floatingBarWidth = 10;

			myChart.ease = "bounce";
			myChart.staggerDraw = true;

			//x.shapes.selectAll("text").attr("fill", "#FF0000");

			//myChart.barGap = 0.5;
			var myLegend = myChart.addLegend(width + 100, 0, 100, 250, "right");
			myChart.setMargins(100, 20, 140, 50);

			s.addEventHandler("click", function (e) {
				showData(e.xValue);
				console.log(e);
			});

			myChart.draw(1000);

			y.titleShape.remove();
			x.titleShape.remove();

			myChart.legends = [];

			x.shapes.selectAll("text").attr("transform",
				function (d) {
					return d3.select(this).attr("transform") + " translate(50, 100) rotate(-90)";
				}
			);

			x.shapes.selectAll("text").remove();
			//x.shapes.selectAll("text").css("word-wrap","wrap-all");

			//y.tickFormat = ".f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			s.afterDraw = function (shape, data) {
				// Get the shape as a d3 selection
				var s = d3.select(shape),
					rect = {
						x: parseFloat(s.attr("x")),
						y: parseFloat(s.attr("y") - 20),
						width: parseFloat(s.attr("width")),
						height: parseFloat(s.attr("height"))
					};
				// Only label bars where the text can fit
				if (rect.height >= 8) {
					// Add a text label for the value
					svg.append("text")
						// Position in the centre of the shape (vertical position is
						// manually set due to cross-browser problems with baseline)
						.attr("x", rect.x + rect.width / 2)
						.attr("y", rect.y + 0 + 15)
						// Centre align
						.style("text-anchor", "middle")
						.style("font-size", "12px")
						.style("font-weight", "700")
						// Make it a little transparent to tone down the black
						.style("opacity", 0.9)
						// Format the number
						.text(data.yValue);
				}
			};

			$(window).bind('resizeEnd', function () {
				myChart.draw(0, true);
			});

		}

		function showData(relation) {

			$('.data').removeClass('hidden').find('table tbody').empty().append('<img src="/assets/images/loading.gif">');
			$('.data').find('tfoot').empty();

			var str = $('#selectreport').serialize();

			$.get('reports/entRelationsToMoney.php?action=view&relation=' + relation, str, function (data) {

				var s = '';
				var f = '';
				var number = 0;
				var summa = 0;
				var marga = 0;
				var dsumma;

				for (var i in data) {

					number = parseInt(i) + 1;
					dsumma = '';

					if (data[i].dsumma != '') dsumma = '<br><em class="fs-09 gray" title="Сумма по сделке">' + number_format(data[i].dsumma, 2, ',', ' ') + '<em>';

					s = s +
						'<tr class="ha">' +
						'   <td align="center" class="fs-09">' + number + '</td>' +
						'   <td align="left"><div class="ellipsis Bold fs-12"><a href="javascript:void(0)" onclick="viewDogovor(\'' + data[i].did + '\')"><i class="icon-briefcase-1 blue"></i>&nbsp;' + data[i].dogovor + '</a></div><br><div class="ellipsis mt5"><a href="javascript:void(0)" onclick="openClient(\'' + data[i].clid + '\')" title=""><i class="icon-building broun"></i>&nbsp;' + data[i].client + '</a></div></td>' +
						'   <td><div class="ellipsis">' + data[i].relation + '</div></td>' +
						'   <td align="right">' + number_format(data[i].marga, 2, ',', ' ') + '</td>' +
						'   <td align="right">' + number_format(data[i].summa, 2, ',', ' ') + dsumma + '</td>' +
						'   <td align="center">' + data[i].dclose + '</td>' +
						'   <td align="left"><div class="ellipsis">' + data[i].user + '</div></td>' +
						'   <td align="left"><span class="fs-09">' + data[i].comment + '</span></td>' +
						'</tr>';

					summa = summa + parseFloat(data[i].summa);
					marga = marga + parseFloat(data[i].marga);
				}

				f =
					'<tr class="ha bluebg-sub">' +
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

			}, 'json');

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
<?php }
?>