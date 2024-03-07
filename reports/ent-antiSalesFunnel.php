<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
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

$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$action = $_REQUEST['action'];
$per    = $_REQUEST['per'];
$period = $_REQUEST['period'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort   = '';
$list   = [];
$counts = [];
$total  = 0;

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$sort .= " deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}

}

$color = [
	'#90A4AE',
	'#78909C',
	'#607D8B',
	'#546E7A',
	'#455A64',
	'#37474F',
	'#263238',
	'#212121',
	'#222'
];

if ( $action == 'view' ) {

	$user = (int)$_REQUEST['user'];
	$step = (int)$_REQUEST['step'];

	$stepp = $db -> getOne( "SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '$step' and identity = '$identity'" );

	$us = ($user > 0) ? "deal.iduser = '$user' and" : $sort;

	$q = "
		SELECT
			deal.did as did,
			deal.datum_close as datum,
			deal.kol as summa,
			deal.marga as marga,
			deal.iduser as iduser,
			deal.title as dogovor,
			deal.close,
			deal.clid as clid,
			us.title as user,
			cc.title as client,
			dc.title as step
		FROM ".$sqlname."dogovor `deal`
			LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
			LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
			LEFT JOIN ".$sqlname."dogcategory `dc` ON dc.idcategory = deal.idcategory
		WHERE
			deal.did > 0 and
			deal.close = 'yes' and
			deal.kol_fact = 0 and
			CAST(dc.title AS UNSIGNED) <= '$step' and 
			deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			$us
			deal.identity = '$identity'
		ORDER BY deal.datum_close
		";

	//print $q;

	$rez = $db -> query( $q );
	while ($daz = $db -> fetch( $rez )) {

		$list[] = [
			"did"     => $daz['did'],
			"dogovor" => $daz['dogovor'],
			"datum"   => format_date_rus( $daz['datum'] ),
			"summa"   => $daz['summa'],
			"marga"   => $daz['marga'],
			"clid"    => $daz['clid'],
			"client"  => $daz['client'],
			"user"    => $daz['user'],
			"step"    => $daz['step'],
			"close"   => $daz['close']
		];

	}

	function cmp($a, $b) {
		return $b['step'] > $a['step'];
	}

	usort( $list, 'cmp' );

	print json_encode_cyr( $list );

	exit();

}
if ( $action == 'getstepdata' ) {

	$users = (empty( $user_list )) ? get_people( $iduser1, true ) : $user_list;

	$step = (int)$_REQUEST['step'];
	$i    = 0;

	$stepp = $db -> getOne( "SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '$step' and identity = '$identity'" );

	foreach ( $users as $user ) {

		$q = "
			SELECT 
				SUM(deal.kol) as kol, 
				COUNT(*) as count,
				dc.title as step
			FROM ".$sqlname."dogovor `deal`
			LEFT JOIN ".$sqlname."dogcategory `dc` ON dc.idcategory = deal.idcategory
			WHERE 
				deal.did > 0 and 
				deal.kol_fact = 0 and 
				deal.close = 'yes' and 
				deal.datum_close between '$da1 00:00:00' and '$da2 23:59:59' and 
				CAST(dc.title AS UNSIGNED) <= '$step' and 
				deal.iduser = '$user' and 
				deal.identity = '$identity'";

		$da = $db -> getRow( $q );

		[
			$r,
			$g,
			$b
		] = sscanf( $color[ $i ], "#%02x%02x%02x" );

		$list[] = [
			"value"     => pre_format( $da['kol'] ),
			"color"     => "rgba($r,$g,$b,0.8)",
			"highlight" => "rgba($r,$g,$b,1.0)",
			"label"     => current_user( $user, "yes" ),
			"user"      => $user,
			"count"     => (int)$da['count']
		];

		$total += pre_format( $da['kol'] + 0 );

		$i++;

		if ( $i >= count( $color ) ) {
			$i = 0;
		}

	}

	$li = [];
	foreach ( $list as $da ) {

		$li[] = [
			"value"     => num_format( $da['value'] ),
			"percent"   => ($total > 0) ? round( $da['value'] / $total * 100, 2 ) : 0,
			"color"     => $da['color'],
			"highlight" => $da['highlight'],
			"label"     => $da['label'],
			"user"      => $da['user'],
			"count"     => (int)$da['count']
		];

	}

	function cmp($a, $b) {
		return $b['count'] > $a['count'];
	}

	usort( $li, 'cmp' );

	print json_encode_cyr( $li );
	exit();

}
if ( $action == '' ) {

	$prev = 0;

	$resultt = $db -> getAll( "SELECT idcategory, title, content FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
	foreach ( $resultt as $data ) {

		$da = $db -> getRow( "
			SELECT SUM(deal.kol) as kol, COUNT(did) as count 
			FROM ".$sqlname."dogovor `deal`
			WHERE 
				deal.did > 0 and 
				deal.kol_fact = 0 and 
				deal.close = 'yes' and 
				deal.datum_close between '$da1 00:00:00' and '$da2 23:59:59' and 
				deal.idcategory = '".$data['idcategory']."' and 
				$sort 
				deal.identity = '$identity'
			" );

		$total += (float)$da['kol'];
		$prev += (int)$da['count'];

		$list[] = [
			"step"  => $data['title'],
			"name"  => $data['content'],
			"summa" => (float)$da['kol'],
			"count" => $prev
		];

	}

	function cmp($a, $b) {
		return $b['step'] < $a['step'];
	}

	usort( $list, 'cmp' );

	//print_r($list);

	$d = [];
	$i = 0;

	foreach ( $list as $string ) {

		$d[] = "['".$string['step']."%',[".$string['count'].",'".num_format( $string['summa'] )." ".$valuta."'],'".$color[ $i ]."']";
		$i++;

	}

	$data = yimplode( ",", $d );

	?>
	<DIV class="zagolovok_rep fs-12 bgwhite border-box" align="center" style="position: fixed; z-index:100; margin-top:-10px">
		<h2>
			антиВоронка продаж&nbsp;с&nbsp;<span class="blue"><?= format_date_rus( $da1 ) ?></span>&nbsp;по&nbsp;<span class="blue"><?= format_date_rus( $da2 ) ?></span>
		</h2>
		<input type="hidden" id="period" name="period" value="<?= $_REQUEST['period'] ?>">

		<hr class="marg0 p0">
	</DIV>

	<div class="block" style="margin-top: 50px;">

		<div id="graf" class="flex-container pt10">

			<div class="flex-string wp30" style="height: 50vh;">
				<div id="funnel" style="width: 90%;" class="clearevents1"></div>
			</div>
			<div class="flex-string wp70">
				<div id="donut" style="max-height: 50vh; overflow-y: auto; width: 100%; padding-left:20px"></div>
				<div class="data hidden">

					<table class="wp95">
						<thead>
						<tr>
							<th class="w30">#</th>
							<th class="w80">Дата</th>
							<th class="">Сделка / Клиент</th>
							<th class="w80">Этап</th>
							<th class="w100">Сумма / Маржа</th>
						</tr>
						</thead>
						<tbody></tbody>
						<tfoot></tfoot>
					</table>

				</div>
			</div>

		</div>

	</div>

	<div style="height: 100px; display:table; width: 100%"></div>

	<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
	<script type="text/javascript" src="/assets/js/d3-funnel/d3-funnel.min.js"></script>
	<script type="text/javascript" src="/assets/js/chartjs/Chart.js"></script>
	<script>

		$(function () {

			var data = [<?=$data?>];
			var chart = new D3Funnel("#funnel");
			var w = $('#funnel').actual('width');

			const options = {
				label: {
					format: '{l}: {v} шт.'
				},
				chart: {
					inverted: true,
					curve: {
						enabled: true
					},
					width: w
				},
				block: {
					dynamicHeight: true,
					minHeight: 20,
					barOverlay: true,
					fill: {
						type: 'gradient'
					},
					highlight: true
				},
				events: {
					click: {
						block: (d) => {
							console.log(d);
							getStep(d.label.raw);
						}
					}
				}
			}

			chart.draw(data, options);
			getStep('0%');
			getStepData('0%', '0');

			$('#funnel').css({'position': 'fixed', 'width': w + 'px'});

		});


		function getStepData(step, user) {

			$('.data').removeClass('hidden').find('table tbody').empty().append('<img src="/assets/images/loading.gif">');
			$('.data').find('tfoot').empty();

			var str = $('#selectreport').serialize();

			$.get('/reports/ent-antiSalesFunnel.php?action=view&user=' + user + '&step=' + step + '&period=' + $('#period').val(), str, function (data) {

				var s = '';
				var f = '';
				var summa = 0;
				var number = 0;
				var marga = 0;
				var icon;
				var step;

				for (var i in data) {

					number = parseInt(i) + 1;

					if (data[i].close == 'yes') {
						icon = 'icon-lock red';
						step = '<span class="fs-09 red">Закрыта</span><br><span class="fs-07 gray2">Этап: ' + data[i].step + '%</span>';
					}
					else {
						icon = 'icon-briefcase-1 blue';
						step = data[i].step + '%';
					}

					s = s +
						'<tr class="ha">' +
						'   <td class="text-center" class="fs-09">' + number + '</td>' +
						'   <td class="text-center">' + data[i].datum + '</td>' +
						'   <td class="text-left"><div class="ellipsis fs-11 Bold"><a href="javascript:void(0)" onclick="viewDogovor(\'' + data[i].did + '\')"><i class="' + icon + '"></i>&nbsp;' + data[i].dogovor + '</a></div><br><div class="ellipsis fs-10"><a href="javascript:void(0)" onclick="openClient(\'' + data[i].clid + '\')" title=""><i class="icon-building broun"></i>&nbsp;' + data[i].client + '</a></div><br><div class="ellipsis fs-09 gray2"><i class="icon-user-1"></i>' + data[i].user + '</div></td>' +
						'   <td class="text-center">' + step + '</td>' +
						'   <td class="text-right"><b>' + number_format(data[i].summa, 2, ',', ' ') + '</b><br><span class="gray2">' + number_format(data[i].marga, 2, ',', ' ') + '</span></td>' +
						'</tr>';

					summa = summa + parseFloat(data[i].summa);
					marga = marga + parseFloat(data[i].marga);
				}

				f =
					'<tr class="ha bluebg-sub">' +
					'   <td class="text-center fs-09"></td>' +
					'   <td class="text-left"></td>' +
					'   <td class="text-left"></td>' +
					'   <td class="text-left"></td>' +
					'   <td class="text-right"><b class="fs-11">' + number_format(summa, 2, ',', ' ') + '</b><br><span>' + number_format(marga, 2, ',', ' ') + '</span></td>' +
					'</tr>';

				//console.log(s);

				$('.data').find('tbody').empty().html(s);
				$('.data').find('tfoot').empty().html(f);

			}, 'json');

		}

		function zerotoggle() {

			$('.zero').toggleClass('hidden');
			$('.zerotoggle').find('i').toggleClass('icon-plus-circled icon-minus-circled');

		}

		function getStep(step) {

			$('.data').addClass('hidden').find('table tbody').empty();
			$('.data').find('tfoot').empty();

			getStepData(step, '0');

			var url = '/reports/ent-antiSalesFunnel.php?action=getstepdata&step=' + step + '&period=' + $('#period').val();
			var str = $('#selectreport').serialize();

			$('#donut').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

			$.get(url, str, function (datas) {

				$('#donut').empty();
				var string = '';
				var string2 = '';
				var hid = '';

				for (var i in datas) {

					cls = 'hand';
					cls2 = 'blue';

					if (datas[i].count === 0) {

						cls = 'gray';
						cls2 = 'gray';

						string2 = string2 + '' +
							'<tr class="ha ' + cls + '" data-user="' + datas[i].user + '" data-step="' + step + '">' +
							'   <td><b>' + datas[i].label + '</b></td>' +
							'   <td class="text-right"><b>' + datas[i].count + '</b></td>' +
							'   <td>' + datas[i].value + '</td>' +
							'   <td><b class="' + cls2 + '">' + datas[i].percent + '%</b></td>' +
							'</tr>';

					}
					else {

						string = string + '' +
							'<tr class="ha ' + cls + ' step" height="35" onclick="getStepData(\'' + step + '\',\'' + datas[i].user + '\')">' +
							'   <td><b>' + datas[i].label + '</b></td>' +
							'   <td align="right" width="40"><b>' + datas[i].count + '</b></td>' +
							'   <td>' + datas[i].value + '</td>' +
							'   <td><b class="' + cls2 + '">' + datas[i].percent + '%</b></td>' +
							'</tr>';

					}
				}

				if (string2 !== '') hid = '<tr class="zerotoggle hand" onclick="zerotoggle()" height="35"><td colspan="4" align="center"><i class="icon-plus-circled gray2"></i> Нулевые</td></tr></tbody><tbody class="zero hidden">' + string2 + '</tbody>';
				else hid = '</tbody>';

				$('#donut').html('<div class="togglerbox hand" data-id="dusers"><h3 class="fs-12">Данные на этапе: <b class="blue">' + step + '</b> [ <a href="javascript:void(0)" onclick="getStepData(\'' + step + '\', \'0\')" title="Показать все"><i class="icon-users-1 green"></i></a> ] <a href="javascript:void(0)" onclick="" title="" class="gray2"><i class="icon-angle-down" id="mapic"></i></a> </h3></div><div id="dusers" class="hidden"><hr><table class="wp95"><tbody>' + string + '' + hid + '</table></div>');

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
			if (dec_point === undefined) {
				dec_point = ",";
			}
			if (thousands_sep === undefined) {
				thousands_sep = ".";
			}

			i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

			if ((j = i.length) > 3) {
				j = j % 3;
			}
			else {
				j = 0;
			}

			km = (j ? i.substring(0, j) + thousands_sep : "");
			kw = i.substring(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
			//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
			kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


			return km + kw + kd;
		}

	</script>
	<?php
}
?>