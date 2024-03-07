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

$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$action = $_REQUEST['action'];
$per    = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];
$period       = $_REQUEST['period'];
$thread       = (int)$_REQUEST['thread'];

$valuta = $GLOBALS['valuta'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort   = $stepsort = $stepsort2 = '';
$list   = [];
$counts = [];
$total  = 0;

if ( $thread > 0 ) {

	$s = getMultiStepList( ["id" => (int)$thread] );

	if(!empty($s)) {

		$sort      .= "deal.direction = '$s[direction]' and deal.tip = '$s[tip]' and ";
		$stepsort  = !empty( $s['thread'] ) ? "idcategory IN (".yimplode( ",", (array)$s['thread'] ).") and " : "";
		$stepsort2 = !empty( $s['thread'] ) ? "deal.idcategory IN (".yimplode( ",", (array)$s['thread'] ).") and " : "";

	}

}

if ( empty( $user_list ) ) {
	$sort .= "deal.iduser IN (".implode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";
}
else {
	$sort .= "deal.iduser IN (".implode( ",", $user_list ).") and ";
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
	'#AD1457',
	'#FF8A65',
	'#F9A825',
	'#2E7D32',
	'#0277BD',
	'#3F51B5',
	'#6A1B9A',
	'#546E7A',
	'#78909C',
	'#00695C',
	'#9E9D24'
];

if ( $action == 'view' ) {

	$user = (int)$_REQUEST['user'];
	$step = (int)$_REQUEST['step'];

	if ( $_REQUEST['step'] != 'Закрытые' && $_REQUEST['step'] != 'Closed' ) {

		$stepp = $db -> getOne( "SELECT idcategory FROM ".$sqlname."dogcategory WHERE title = '(int)$step' and identity = '$identity'" );

	}

	$us = ($user > 0) ? "deal.iduser = '$user' and" : $sort;

	if ( $_REQUEST['step'] != 'Закрытые' && $_REQUEST['step'] != 'Closed' ) {

		$q = "
		SELECT
			deal.did as did,
			deal.datum_plan as datum,
			deal.kol as summa,
			deal.marga as marga,
			deal.iduser as iduser,
			deal.title as dogovor,
			deal.clid as clid,
			deal.close,
			us.title as user,
			cc.title as client,
			dc.title as step
		FROM ".$sqlname."dogovor `deal`
			LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
			LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
			LEFT JOIN ".$sqlname."dogcategory `dc` ON dc.idcategory = deal.idcategory
		WHERE
			deal.did > 0 and
			deal.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			CAST(dc.title AS UNSIGNED) >= '$step' and 
			$stepsort2
			$us
			deal.identity = '$identity'
		ORDER BY deal.datum_plan
		";

	}
	else {

		$q = "
		SELECT
			deal.did as did,
			deal.datum_close as datum,
			deal.kol_fact as summa,
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
			deal.kol_fact > 0 and 
			deal.close = 'yes' and
			deal.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			$us
			deal.identity = '$identity'
		ORDER BY deal.datum_close
		";

	}

	//print $q;

	$rez = $db -> query( $q );
	while ($daz = $db -> fetch( $rez )) {

		$list[] = [
			"did"     => (int)$daz['did'],
			"dogovor" => $daz['dogovor'],
			"datum"   => format_date_rus( $daz['datum'] ),
			"summa"   => $daz['summa'],
			"marga"   => $daz['marga'],
			"clid"    => (int)$daz['clid'],
			"client"  => $daz['client'],
			"user"    => $daz['user'],
			"step"    => (int)$daz['step'],
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

//подружаемый блок с данными по этапу
if ( $action == 'getstepdata' ) {

	$list = $li = [];

	$users = !empty($user_list) ? get_people( $iduser1, true ) : $user_list;

	$step = (int)$_REQUEST['step'];
	$i    = 0;

	if ( $_REQUEST['step'] != 'Закрытые' && $_REQUEST['step'] != 'Closed' ) {
		$stepp = $db -> getOne( "SELECT idcategory FROM ".$sqlname."dogcategory WHERE $stepsort title = '$step' and identity = '$identity'" );
	}

	foreach ( $users as $user ) {

		if ( $_REQUEST['step'] != 'Закрытые' && $_REQUEST['step'] != 'Closed' ) {

			$q = "
				SELECT 
					SUM(deal.kol) as kol, 
					COUNT(*) as count,
					dc.title as step
				FROM ".$sqlname."dogovor `deal`
				LEFT JOIN ".$sqlname."dogcategory `dc` ON dc.idcategory = deal.idcategory
				WHERE 
					deal.did > 0 and 
					deal.datum between '$da1 00:00:00' and '$da2 23:59:59' and 
					CAST(dc.title AS UNSIGNED) >= '$step' and 
					$stepsort2
					deal.iduser = '$user' and 
					deal.identity = '$identity'
				";

		}
		else {

			$q = "
				SELECT 
					SUM(deal.kol_fact) as kol, 
					COUNT(*) as count,
					dc.title as step
				FROM ".$sqlname."dogovor `deal`
				LEFT JOIN ".$sqlname."dogcategory `dc` ON dc.idcategory = deal.idcategory
				WHERE 
					deal.did > 0 and 
					deal.kol_fact > 0 and 
					deal.datum between '$da1 00:00:00' and '$da2 23:59:59' and
					deal.datum_close between '$da1 00:00:00' and '$da2 23:59:59' and 
					deal.close = 'yes' and
					deal.iduser = '$user' and 
					deal.identity = '$identity'";

		}

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

	foreach ( $list as $da ) {

		$li[] = [
			"value"     => num_format( $da['value'] ),
			"percent"   => ($total > 0) ? round( $da['value'] / $total * 100, 2 ) : 0,
			"color"     => $da['color'],
			"highlight" => $da['highlight'],
			"label"     => $da['label'],
			"user"      => $da['user'],
			"count"     => $da['count']
		];

	}

	function cmp($a, $b) {
		return $b['count'] > $a['count'];
	}

	usort( $li, 'cmp' );

	print json_encode_cyr( $li );

	exit();

}

//вывод основной воронки и таблицы
if ( $action == '' ) {

	$prev = 0;

	//расчет сумм и количества сдлок по каждому этапу
	$resultt = $db -> getAll( "SELECT idcategory, title, content FROM ".$sqlname."dogcategory WHERE $stepsort identity = '$identity' ORDER BY title DESC" );
	foreach ( $resultt as $data ) {

		$da = $db -> getRow( "
			SELECT SUM(deal.kol) as kol, COUNT(deal.did) as count 
			FROM ".$sqlname."dogovor `deal`
			WHERE 
				deal.did > 0 AND 
				deal.datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
				deal.idcategory = '".$data['idcategory']."' AND 
				$sort 
				deal.identity = '$identity'
			" );

		$total += $da['kol'];
		$prev  += (int)$da['count'];

		$list[] = [
			"step"  => $data['title'],
			"name"  => $data['content'],
			"summa" => (int)$da['kol'],
			"count" => $prev
		];

	}

	//пересортируем по значению этапа
	function cmp($a, $b) {
		return $b['step'] < $a['step'];
	}

	usort( $list, 'cmp' );

	//данные по закрытым сделкам
	$da = $db -> getRow( "
		SELECT SUM(deal.kol) as kol, COUNT(deal.did) as count 
		FROM ".$sqlname."dogovor `deal`
		WHERE 
			deal.did > 0 AND 
			deal.datum between '$da1 00:00:00' AND '$da2 23:59:59' AND 
			deal.datum_close BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND 
			deal.close = 'yes' AND 
			deal.kol_fact > 0 AND 
			$sort 
			deal.identity = '$identity'
		" );

	$total += $da['kol'];

	$list[] = [
		"step"  => "closed",
		"name"  => "Закрытые сделки",
		"summa" => (float)($da['kol']),
		"count" => (int)$da['count']
	];

	//print_r($list);

	$d = [];

	//формируем данные для воронки
	foreach ( $list as $i => $string ) {

		if ( $string['step'] != 'closed' ) {
			$d[] = "['".$string['step']."%',[".$string['count'].",'".num_format( $string['summa'] )." ".$valuta."'],'".$color[ $i ]."']";
		}
		else {
			$d[] = "['Закрытые',[".$string['count'].",'".num_format( $string['summa'] )." ".$valuta."'],'".$color[ $i ]."']";
		}

	}

	$data = yimplode( ",", $d );

	//вызов из Экспресс-окна
	if ( $_REQUEST['period'] == '' ) {
		?>
		<DIV class="zagolovok_rep fs-12 bgwhite border-box flex-container">

			<div class="flex-string wp60 fs-14 mt10">
				Воронка продаж&nbsp;с&nbsp;<span class="blue"><?= format_date_rus( $da1 ) ?></span>&nbsp;по&nbsp;<span class="blue"><?= format_date_rus( $da2 ) ?></span>
			</div>

			<?php
			$res = $db -> query( "SELECT * FROM ".$sqlname."multisteps WHERE identity = '$identity'" );
			if ( $db -> affectedRows() > 0 ) {
				?>
				<div class="flex-string wp40 tex-right noBold">
					Воронка:
					<span class="select wp80">
					<select name="thread" id="thread" class="required wp100 fs-09">
						<option value="">--все--</option>
						<?php
						while ($da = $db -> fetch( $res )) {

							$s = ($da['id'] == $thread) ? "selected" : "";
							print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].'</option>';

						}
						?>
					</select>
					</span>&nbsp;
				</div>
			<?php } ?>
		</DIV>
		<hr class="marg0 p0">
		<?php
		$top = '10px';
	}
	else {
		?>
		<DIV class="zagolovok_rep fs-12 bgwhite border-box text-center" style="">

			<div class="wp100">
				<h2 class="uppercase wp100">
					Воронка продаж&nbsp;с&nbsp;<span class="blue"><?= format_date_rus( $da1 ) ?></span>&nbsp;по&nbsp;<span class="blue"><?= format_date_rus( $da2 ) ?></span>
				</h2>
			</div>
			<input type="hidden" id="period" name="period" value="<?= $_REQUEST['period'] ?>">

			<?php
			$res = $db -> query( "SELECT * FROM ".$sqlname."multisteps WHERE identity = '$identity'" );
			if ( $db -> affectedRows() > 0 ) {
				?>
				<div class="flex-container box--child" style="position: absolute; top: 80px; right: 20px; width:400px">
					<div class="flex-string select w300">
						<select name="thread" id="thread" class="required wp100 fs-09" onchange="setThread()">
							<option value="">Общая</option>
							<?php
							while ($da = $db -> fetch( $res )) {

								$s = ($da['id'] == $thread) ? "selected" : "";
								print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].'</option>';

							}
							?>
						</select>
					</div>&nbsp;
				</div>
			<?php } ?>

			<hr class="marg0 p0">
		</DIV>
		<?php
		$top = '40px';
	}
	?>

	<div class="block" style="margin-top: <?= $top ?>;">

		<div id="graf" class="flex-container pt10 relativ">

			<div class="flex-string wp30 sticked--top no-shadow pt30" style="height: 50vh; box-shadow: 0 0 0 0 rgba(50, 50, 50, 0);">

				<div id="funnel" style="width: 90%;" class="clearevents1"></div>

			</div>
			<div class="flex-string wp70">

				<div id="donut" class="wp100 pl20" style="max-height: 50vh; overflow-y: auto;"></div>
				<div class="data hidden">

					<table class="wp95">
						<thead class="sticked--top">
						<tr>
							<th class="w30">#</th>
							<th class="w80">Дата</th>
							<th class="">Сделка / Клиент</th>
							<th class="w80">Этап</th>
							<th class="w160">Сумма / Маржа</th>
						</tr>
						</thead>
						<tbody></tbody>
						<tfoot class="sticked--bottom"></tfoot>
					</table>

				</div>
			</div>

		</div>

	</div>

	<div style="height: 100px; display:table; width: 100%"></div>


	<!--<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>-->
	<script type="text/javascript" src="/assets/js/d3-funnel/d3-funnel.min.js"></script>

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
					bottomWidth: 1 / 3,
					curve: {
						enabled: true
					},
					bottomPinch: 3,
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

			<?php
			if($_REQUEST['period'] != ''){
			?>
			$('#funnel').css({'position': 'fixed', 'width': w + 'px'});
			<?php }?>

		});

		<?php if($_REQUEST['period'] != ''){?>
		$(document).bind('change', '#thread', function () {

			//setThread();

		});
		<?php } ?>

		function setThread() {

			$('#swindow').find('.body').load('reports/ent-SalesFunnel.php?period=' + $('#period').val() + '&thread=' + $('#thread option:selected').val());

		}

		function getStepData(step, user) {

			$('.data').removeClass('hidden').find('table tbody').empty().append('<img src="/assets/images/loading.gif">');
			$('.data').find('tfoot').empty();

			var str = $('#selectreport').serialize();

			$.get('/reports/ent-SalesFunnel.php?action=view&thread=' + $('#thread option:selected').val() + '&user=' + user + '&step=' + step + '&period=' + $('#period').val(), str, function (data) {

				var s = '';
				var f;
				var summa = 0;
				var number;
				var marga = 0;
				var icon;
				var step;

				for (var i in data) {

					number = parseInt(i) + 1;

					if (data[i].close === 'yes') {
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
						'   <td class="text-right"><div class="Bold fs-11">' + number_format(data[i].summa, 2, ',', ' ') + '</div><div class="mt5 gray2">' + number_format(data[i].marga, 2, ',', ' ') + '</div></td>' +
						'</tr>';

					summa = summa + parseFloat(data[i].summa);
					marga = marga + parseFloat(data[i].marga);

				}

				f =
					'<tr class="ha bluebg-sub">' +
					'   <td class="text-center" class="fs-09"></td>' +
					'   <td class="text-left"></td>' +
					'   <td class="text-right"><div class="Bold fs-11">Итого, Оборот:</div><div class="mt5">Маржа:</div></td>' +
					'   <td class="text-left"></td>' +
					'   <td class="text-right"><div class="Bold fs-11">' + number_format(summa, 2, ',', ' ') + '</div><div class="mt5">' + number_format(marga, 2, ',', ' ') + '</div></td>' +
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

			var url = '/reports/ent-SalesFunnel.php?action=getstepdata&thread=' + $('#thread option:selected').val() + '&step=' + step + '&period=' + $('#period').val();
			var str = $('#selectreport').serialize();

			$('#donut').empty().append('<div id="loader" class="loader"><img src=/assets/images/loading.gif> Вычисление...</div>');

			$.get(url, str, function (datas) {

				var string = '';
				var string2 = '';
				var hid = '';

				$('#donut').empty();

				for (var i in datas) {

					var cls = 'hand';
					var cls2 = 'blue';

					if (datas[i].count === '0') {

						//cls = 'gray';
						//cls2 = 'gray';

						string2 = string2 + '' +
							'<tr class="ha gray" data-user="' + datas[i].user + '" data-step="' + step + '" height="40">' +
							'   <td class="wp50"><b>' + datas[i].label + '</b></td>' +
							'   <td class="w40"><b>' + datas[i].count + '</b></td>' +
							'   <td class="w140 text-right">' + datas[i].value + '</td>' +
							'   <td class="w80 text-right"><b class="gray">' + datas[i].percent + '%</b></td>' +
							'   <td></td>' +
							'</tr>';

					}
					else
						string = string + '' +
							'<tr class="ha ' + cls + ' step" height="40" onclick="getStepData(\'' + step + '\',\'' + datas[i].user + '\')">' +
							'   <td class="wp50"><b>' + datas[i].label + '</b></td>' +
							'   <td class="w40"><b>' + datas[i].count + '</b></td>' +
							'   <td class="w140 text-right">' + datas[i].value + '</td>' +
							'   <td class="w80 text-right"><b class="' + cls2 + '">' + datas[i].percent + '%</b></td>' +
							'   <td></td>' +
							'</tr>';

				}

				if (string2 !== '')
					hid = '<tr class="zerotoggle ha hand" onclick="zerotoggle()" height="40"><td colspan="5" align="center"><i class="icon-plus-circled gray2"></i> Нулевые</td></tr></tbody><tbody class="zero hidden">' + string2 + '</tbody>';
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