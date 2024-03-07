<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Для Рыбинсккомплекс
 * На основе отчета ent-activitiesResultReportPrioritetMod.php
 * Доработки:
 * - колонка "Кол-во новых сделок"
 * - Экспорт в Excel ( + колонка "Общее количество не закрытых сделок (без учета периода)" )
 */

use Salesman\Guides;

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__ );

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
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$tips = $_REQUEST['tips'];
$tips = (!empty( $tips )) ? $tips : $db -> getCol( "SELECT title FROM ".$sqlname."activities WHERE id > 0 and filter IN ('activ','all') and identity = '$identity'" );

$sort   = '';
$kolSum = 0;

function getDateCustom($date): string {

	$d = yexplode( " ", (string)$date );

	return "<b>".format_date_rus( $d[0] )."</b> ".getTime( $d[1] )."";

}

function dateFormat($date_orig, $format = 'excel') {

	$date_new = '';

	if ( $format == 'excel' ) {

		if ( $date_orig != '0000-00-00' && $date_orig != '' && $date_orig != NULL ) {
			$date_new = $date_orig;
		}

	}
	elseif ( $format == 'date' ) {

		if ( $date_orig && $date_orig != '0000-00-00' ) {

			$date_new = explode( "-", $date_orig );
			$date_new = $date_new[1].".".$date_new[2].".".$date_new[0];

		}

	}

	return $date_new;

}

//массив выбранных пользователей
$sort .= (!empty( $user_list )) ? "deal.iduser IN (".yimplode( ",", $user_list ).") and " : "deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";
$x    = (!empty( $user_list )) ? "iduser IN (".yimplode( ",", $user_list ).") and " : "iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";

//составляем запрос по параметрам сделок
$ar = [
	'sid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != 'close' ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif($field == 'close'){
		$sort .= $query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif($field == 'mcid') {
		$mc = $field_query[ $i ];
	}

}
if($mc > 0) {
	$sort .= "deal.mcid = '$mc' and ";
}

$mycomps = Guides::myComps();

$colors = (array)$db -> getIndCol( "title", "SELECT color, LOWER(title) as title FROM ".$sqlname."activities WHERE identity = '$identity'" );

$htip = (!empty( $tips )) ? "hs.tip IN(".yimplode( ",", $tips, "'" ).") and " : "";

$list = $sublist = [];
$i    = 0;

$re = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE iduser > 0 and $x identity = '$identity' ORDER BY title" );
foreach ( $re as $daa ) {

	$q = "
	SELECT 
		deal.did as did,
		deal.title as dogovor,
		deal.datum as dcreate,
		deal.clid as clid,
		deal.iduser as iduser,
		hs.cid as cid,
		hs.datum as datum,
		hs.tip as tip,
		hs.des as content,
		hs.iduser as huser
	FROM ".$sqlname."dogovor `deal`
		LEFT JOIN ".$sqlname."history `hs` ON deal.did = hs.did
	WHERE 
		hs.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and 
		hs.iduser = '$daa[iduser]' and
		$htip
		hs.identity = '$identity' and
		deal.close != 'yes' and
		deal.identity = '$identity' 
	ORDER BY hs.datum DESC";

	$da = $db -> getAll( $q );

	foreach ( $da as $data ) {

		//сводная строка по сделке
		if ( empty( $sublist[ $daa['iduser'] ][ $data['did'] ] ) ) {

			$list[ $daa['iduser'] ][ $data['did'] ] = [
				"dcreate" => $data['dcreate'],
				"dogovor" => $data['dogovor'],
				"clid"    => $data['clid'],
				"client"  => current_client( $data['clid'] ),
				"user"    => current_user( $data['huser'] ),
				//$daa['title'],
				"duser"   => current_user( $data['iduser'] ),
				"datum"   => $data['datum']
			];

		}

		//записи активностей по сделке
		$sublist[ $daa['iduser'] ][ $data['did'] ][] = [
			"cid"     => $data['cid'],
			"datum"   => $data['datum'],
			"tip"     => $data['tip'],
			"content" => $data['content'],
			"user"    => current_user( $data['huser'] )
		];

	}

}

if ( $action == 'export' ) {

	$otchet = [];

	$header = [
		"Менеджер",
		"Сделка",
		"Клиент",
		"Дата",
		"Активность",
		"Содержание"
	];

	//строка заголовков
	$otchet[] = $header;

	foreach ( $list as $iduser => $v ) {

		foreach ( $v as $id => $deal ) {

			foreach ( $sublist[ $iduser ][ $id ] as $item ) {

				$otchet[] = [
					$item['user'],
					$deal['dogovor'],
					$deal['client'],
					dateFormat( $item['datum'] ),
					$item['tip'],
					$item['content']
				];

			}

		}

	}

	$from = [
		":",
		" "
	];
	$to   = [
		"-",
		"_"
	];

	Shuchkin\SimpleXLSXGen ::fromArray( $otchet ) -> downloadAs( str_replace( $from, $to, current_datumtime() ).'-activitiesByActiveDeals.xlsx' );

	exit();

}

$data = [];
foreach ( $sublist as $k0 => $v0 ) {

	foreach ( $v0 as $v ) {

		foreach ( $v as $k1 => $v1 ) {

			$data[ $v1['user'] ][ $v1['tip'] ]++;

		}

	}

}

//print_r($data);
$datas = [];
foreach ( $data as $key => $val ) {

	foreach ( $val as $k => $v ) {

		$u = yexplode( " ", $key );
		$u = $u[0]." ".substr( $u[1], 0, 2 ).".";

		$datas[] = '{"Пользователь":"'.$u.'", "Тип":"'.$k.'", "Кол-во":"'.$v.'"}';

	}

}

$datas = implode( ",", $datas );
?>

<style type="text/css">
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

	<h1 class="uppercase fs-14 m0 mb10">Активности по активным сделкам</h1>

	<div class="gray2">за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?>
		<span class="hidden1 Bold">[ <a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel для Roistat" class="blue">Excel</a> ]</span>
	</div>

</div>

<hr>

<table class="noborder">
	<tr>
		<td class="wp25">
			<div class="ydropDown margtop5">
				<span>Только Активности</span><span class="ydropCount"><?= count( $tips ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
						</div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
						</div>
					</div>
					<?php
					$result = $db -> query( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('activ','all') and identity = '$identity' ORDER BY title" );
					while ($data = $db -> fetch( $result )) {

						print
							'<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="'.$data['title'].'" '.(in_array( $data['title'], $tips ) ? 'checked' : '').'>
								<span class="bullet-mini" style="background: '.$data['color'].'"></span>&nbsp;'.$data['title'].'
							</label>
						</div>';

					}
					?>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="СобытиеCRM" <?php if ( in_array( 'СобытиеCRM', $tips ) )
								print 'checked'; ?>>
							<span class="bullet-mini" style="background: #9E9E9E"></span>&nbsp;СобытиеCRM
						</label>
					</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="ЛогCRM" <?php if ( in_array( 'ЛогCRM', $tips ) )
								print 'checked'; ?>>
							<span class="bullet-mini" style="background: #607D8B"></span>&nbsp;ЛогCRM
						</label>
					</div>
				</div>
			</div>
		</td>
		<td class="wp25"></td>
		<td class="wp25"></td>
		<td></td>
	</tr>
</table>

<hr>

<div id="graf" style="display:block; height:350px">

	<div id="chart" style="padding:5px; height:100%"></div>

</div>

<hr>

<div class="block">

	<TABLE>
		<thead class="sticked--top">
		<TR class="th40">
			<th class="w20 text-center"></th>
			<th class="w20 text-center"><b>#</b></th>
			<th class="w120 text-center"><b>Дата<br>создан.</b></th>
			<th class="w120 text-center"><b>Дата<br>активности.</b></th>
			<th class="w250"><b>Сделка</b></th>
			<th><b>Заказчик</b></th>
			<th class="w120 text-center"><b>Ответств.</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$num = 1;

		foreach ( $list as $user => $v0 ) {

			print '
			<tr class="th50 bluebg-sub">
				<td colspan="7">
					<div class="ellipsis fs-11 Bold uppercase"><i class="icon-user-1 blue"></i>&nbsp;'.current_user( $user ).'</div>
				</td>
			</tr>
			';

			foreach ( $v0 as $did => $val ) {

				print '
				<tr class="datetoggle td--main '.($num & 1 ? 'color1' : 'color2').'" data-key="'.$did.'">
					<td class="text-right"><i class="icon-plus-circled gray2"></i></td>
					<td class="text-right"><b>'.$num.'.</b></td>
					<td>'.format_date_rus( $val['dcreate'] ).'</td>
					<td>
						<div class="bullet redbg fs-07 flh-11 pull-aright">'.count( $sublist[ $user ][ $did ] ).'</div>
						'.getDateCustom( $val['datum'] ).'
					</td>
					<td>
						<div class="ellipsis">
							<A href="javascript:void(0)" onclick="openDogovor(\''.$did.'\')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i>'.$val['dogovor'].'</A>
						</div>
					</td>
					<td>
						<div class="ellipsis">
							<A href="javascript:void(0)" onclick="openClient(\''.$val['clid'].'\')"><i class="icon-building broun"></i>&nbsp;'.$val['client'].'</A>
						</div>
					</td>
					<td><div class="ellipsis"><i class="icon-user-1 blue"></i>&nbsp;'.$val['duser'].'</div></td>
				</tr>
				';

				$number = 1;

				foreach ( $sublist[ $user ][ $did ] as $k => $v ) {

					print
						'<TR class="ha hidden sub th40 top" data-date="'.$did.'">
							<TD></TD>
							<TD class="text-right">'.$number.'.</TD>
							<TD>
								<div class="ellipsis">
									<span style="color:'.strtr( mb_strtolower( $v['tip'] ), $colors ).'">'.get_ticon( $v['tip'] ).'</span>'.$v['tip'].'
								</div>
							</TD>
							<TD>'.getDateCustom( $v['datum'] ).'</TD>
							<TD colspan="2" title="'.html2text( str_replace( "<br>", "\n", $v['content'] ) ).'">
								<div class="dot-ellipsis hand" onclick="viewHistory(\''.$v['cid'].'\')">'.str_replace( "\n", "<br>", $v['content'] ).'</div>
							</TD>
							<TD>
								<div class="ellipsis"><i class="icon-user-1 gray2"></i>&nbsp;'.$v['user'].'</div>
							</TD>
						</TR>';

					$number++;

				}

				$num++;

			}

		}
		?>
		</tbody>
	</TABLE>

</div>

<div style="height:150px;" class="block"></div>

<DIV class="fixAddBotButton" style="left:auto; right: 50px" onclick="ToggleAll()" data-state="collapse">
	<i class="icon-plus"></i> <span>Развернуть всё</span>
</div>

<script src="/assets/js/jquery.liTextLength.js"></script>
<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script>

	drowChart();

	$(".dot-ellipsis").liTextLength({
		length: 200,
		afterLength: '...',
		fullText: false
	});

	$('.datetoggle').on('click', function () {

		$('tr.sub').addClass('hidden');
		$('.datetoggle td:first-child').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');

		var key = $(this).data('key');
		$('tr.sub[data-date="' + key + '"]').toggleClass('hidden');

		$(this).find('i:first').toggleClass('icon-plus-circled icon-minus-circled');

	});

	function drowChart() {

		var width = $('#chart').width() - 200;
		var height = 400;
		var svg = dimple.newSvg("#chart", "100%", "100%");
		var data = [<?=$datas?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width - 50, height - 40);

		var x = myChart.addCategoryAxis("x", ["Пользователь"]);
		//x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		x.showGridlines = false;

		var y = myChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии
		y.tickFormat = ".f";

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

		//var z = myChart.addMeasureAxis("z", "Тип");

		var s = myChart.addSeries(["Пользователь", "Тип"], dimple.plot.bar);

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

		s.addEventHandler("click", function (e) {
			showData(e.xValue);
		});

		//x.shapes.selectAll("text").css("word-wrap","wrap-all");

		myChart.draw(1000);

		//y.titleShape.remove();
		x.titleShape.remove();

		myChart.legends = [];

		var filterValues = dimple.getUniqueValues(data, "Тип");
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
			myChart.data = dimple.filterData(data, "Тип", filterValues);
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

		var elm = $('.fixAddBotButton');
		var state = elm.data('state');

		//console.log(state);

		if (state === 'collapse') {

			elm.data('state', 'expand');
			elm.find('span').html('Свернуть всё');
			elm.find('i:first').removeClass('icon-plus').addClass('icon-minus');

			$('.datetoggle td:first-child').find('i:first').removeClass('icon-plus-circled').addClass('icon-minus-circled');
			$('tr.sub').removeClass('hidden');

		}
		if (state === 'expand') {

			elm.data('state', 'collapse');
			elm.find('span').html('Развернуть всё');
			elm.find('i:first').addClass('icon-plus').removeClass('icon-minus');

			$('.datetoggle td:first-child').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');
			$('tr.sub').addClass('hidden');

		}

	}

	function Export() {

		var str = $('#selectreport').serialize();
		window.open('reports/' + $('#report option:selected').val() + '?action=export&' + str);

	}

</script>