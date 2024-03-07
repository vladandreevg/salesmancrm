<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

use Salesman\Guides;

set_time_limit( 0 );
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

if ( !$per ) {
	$per = 'nedelya';
}

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];
$atips       = (array)$_REQUEST['tips'];

$taskonly    = $_REQUEST['taskonly'];

if ( $taskonly == 'taskonlyGood' ) {
	$toTitle = '<i class="icon-ok green"></i>&nbsp;Успешное';
}
elseif ( $taskonly == 'taskonlyBad' ) {
	$toTitle = '<i class="icon-block red"></i>&nbsp;Не успешное';
}
elseif ( $taskonly == 'notaskonly' ) {
	$toTitle = 'Без напоминаний';
}
else {
	$toTitle = 'Не учитывать';
}

$sort   = '';
$kolSum = 0;
$dogs   = $steps = $counts = $ucounts = $dealexist = [];

function getDateCustom($date): string {

	$d = yexplode( " ", (string)$date );

	return ($date != '') ? "<b>".format_date_rus( $d[0] )."</b> ".getTime( $d[1] )."" : "";

}

if ( empty( $atips ) ) {
	$atips = (array)$db -> getCol( "SELECT title FROM {$sqlname}activities WHERE id > 0 and identity = '$identity'" );
}

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= " hs.iduser IN (".yimplode( ",", $user_list ).") AND ";
}
else {
	$sort .= " hs.iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close',
	'mcid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$mycomps = Guides ::myComps();
if ( $mc > 0 ) {
	$sort .= "deal.mcid = '$mc' AND ";
}

if ( $taskonly == 'taskonlyGood' ) {
	$sort .= " 
		(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.identity = '$identity') > 0 AND  
		(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.status = '1') > 0 AND 
	";
}
elseif ( $taskonly == 'taskonlyBad' ) {
	$sort .= " 
		(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.identity = '$identity') > 0 AND 
		(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.status = '2') > 0 AND 
	";
}
elseif ( $taskonly == 'notaskonly' ) {
	$sort .= " 
		(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.identity = '$identity') = 0 AND 
	";
}

$i = 0;

$q = "
	SELECT 
		deal.did as did,
		deal.title as dogovor,
		deal.datum as dcreate,
		deal.clid as clid,
		deal.idcategory as stepid,
		deal.close as close,
		deal.kol as summa,
		deal.marga as marga,
		cc.title as client,
		deal.iduser as iduser,
		us.title as user,
		hs.datum as datum,
		hs.tip as tip,
		hs.des as content,
		hs.iduser as huser,
		dc.title as step
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}user `us` ON deal.iduser = us.iduser
		LEFT JOIN {$sqlname}clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN {$sqlname}history `hs` ON deal.did = hs.did
		LEFT JOIN {$sqlname}dogcategory `dc` ON deal.idcategory = dc.idcategory
	WHERE 
		hs.datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and 
		$sort
		hs.tip IN (".yimplode(",", $atips, "'").") AND
		hs.identity = '$identity' and
		deal.identity = '$identity' 
	ORDER BY hs.datum";

$da = $db -> getAll( $q );

foreach ( $da as $data ) {

	$dfact = '';
	$prim  = '';
	$color = '';

	$steps[ $data['stepid'] ] = $data['step'];

	//сводная строка по сделке
	if ( count( (array)$dogs[ $data['step'] ][ $data['did'] ] ) == 0 && !in_array( $data['did'], (array)$dealexist ) ) {

		$lastStepChange = $db -> getOne( "SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did = '".$data['did']."' AND step = '".$data['stepid']."'" );

		$dogs[ $data['step'] ][ $data['did'] ] = [
			"dcreate"  => format_date_rus( $data['dcreate'] ),
			"dogovor"  => $data['dogovor'],
			"close"    => $data['close'],
			"summa"    => $data['summa'],
			"marga"    => $data['marga'],
			"clid"     => (int)$data['clid'],
			"client"   => $data['client'],
			"user"     => $data['user'],
			"datum"    => getDateCustom( $data['datum'] ),
			"dateStep" => getDateCustom( $lastStepChange )
		];

		$dealexist[] = $data['did'];

		$counts[ $data['step'] ]++;
		$ucounts[ (int)$data['huser'] ][ $data['step'] ]++;

	}

	//записи активностей по пользователям
	$ulist[ $data['step'] ][] = [
		"datum"   => getDateCustom( $data['datum'] ),
		"tip"     => $data['tip'],
		"content" => $data['content'],
		"user"    => current_user( (int)$data['huser'] ),
		"did"     => (int)$data['did']
	];

	//записи активностей по сделке
	$list[ $data['step'] ][ (int)$data['did'] ][] = [
		"datum"   => getDateCustom( $data['datum'] ),
		"did"     => (int)$data['did'],
		"tip"     => $data['tip'],
		"content" => $data['content'],
		"user"    => current_user( (int)$data['huser'] )
	];

	if ( in_array( $data['tip'], $atips ) ) {
		$tips[ (int)$data['huser'] ][ $data['tip'] ]++;
	}

}

$steps = array_values( $steps );

sort( $steps );

$datas = [];
foreach ( $tips as $key => $val ) {

	foreach ( $val as $k => $v ) {

		$u = yexplode( " ", (string)current_user( $key ) );
		$u = $u[0]." ".substr( $u[1], 0, 2 ).".";

		$datas[] = '{"Куратор":"'.$u.'", "Тип":"'.$k.'", "Кол-во":"'.$v.'"}';

	}

}

$datas = implode( ",", $datas );

//print_r($datas);

if ( $action == "get_csv" ) {

	$otchet = [
		"#",
		"Дата создан.",
		"Дата план.",
		"Этап сделки",
		"Сделка",
		"Заказчик",
		"Ответств.",
		"Описание",
		"Сумма сделки, р."
	];
	$j      = 1;

	foreach ( $dogs as $key => $val ) {

		foreach ( $val as $k => $v ) {

			$otchet[] = [
				$j,
				$v['dcreate'],
				$key,
				$v['step'].'%',
				$v['dogovor'],
				$v['client'],
				$v['user'],
				preg_replace( "/\r\n|\r|\n/u", "", untag( $v['des'] ) ),
				$v['kol']
			];

			$j++;

		}

	}

	//создаем файл csv
	$filename = 'export_doganaliz.xlsx';

	Shuchkin\SimpleXLSXGen ::fromArray( $otchet ) -> downloadAs( $filename );

	exit();

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
		height : 45px;
		/*cursor: pointer;*/
	}

	.color1 {
		background : rgba(255, 236, 179, .9);
	}

	.color2 {
		background : rgba(255, 249, 196, .9);
	}

	.td--main:hover {
		/*background: rgba(197,225,165 ,1);*/
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

	<h1 class="uppercase fs-14 m0 mb10">Активности по этапам [по сделкам]</h1>
	<div class="gray2">
		в период&nbsp;с&nbsp;<?= $da1 ?>&nbsp;по&nbsp;<?= $da2 ?>
		<span class="hidden"> (<a href="javascript:void(0)" onClick="generate_csv()" style="color:blue"> Экспорт </a>)</span>
	</div>

</div>

<hr>

<table class="noborder">
	<tr>
		<td class="wp25">
			<div class="ydropDown margtop5">
				<span>Только Активности</span><span class="ydropCount"><?= count( $atips ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox">
					<div class="right-text">
						<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
						</div>
						<div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего
						</div>
					</div>
					<?php
					$result = $db -> query( "SELECT * FROM {$sqlname}activities WHERE identity = '$identity' ORDER BY title" );
					while ($data = $db -> fetch( $result )) {
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="tips[]" type="checkbox" id="tips[]" value="<?= $data['title'] ?>" <?php if ( in_array( $data['title'], $atips ) )
									print 'checked'; ?>>&nbsp;<?= $data['title'] ?>
							</label>
						</div>
						<?php
					}
					?>
				</div>
			</div>
		</td>
		<td class="wp25">
			<div class="ydropDown margtop5" data-id="sort">

				<span class="yText Bold fs-09">Напоминания</span>
				<span class="ydropText Bold"><?= $toTitle ?></span>
				<i class="icon-angle-down pull-aright"></i>

				<div class="yselectBox">

					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Не учитывать" class="hidden" value="" <?= (!isset( $taskonly ) || $taskonly == '' ? 'checked' : '') ?>>&nbsp;Не учитывать
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Успешное" class="hidden" value="taskonlyGood" <?= ($taskonly == 'taskonlyGood' ? 'checked' : '') ?>>&nbsp;<i class="icon-ok green"></i>&nbsp;Успешное выполнение
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Не успешное" class="hidden" value="taskonlyBad" <?= ($taskonly == 'taskonlyBad' ? 'checked' : '') ?>>&nbsp;<i class="icon-block red"></i>&nbsp;Не успешное выполнение
						</label>
					</div>
					<div class="ydropString yRadio ellipsis">
						<label>
							<input name="taskonly" type="radio" id="taskonly" data-title="Нет" class="hidden" value="notaskonly" <?= ($taskonly == 'notaskonly' ? 'checked' : '') ?>>&nbsp;Только без напоминаний
						</label>
					</div>

				</div>
			</div>
		</td>
		<td class="wp25"></td>
		<td></td>
	</tr>
</table>

<hr>

<div id="graf" style="display:block; height:350px">

	<div id="chart" style="padding:5px; height:100%"></div>

</div>

<hr>

<div class="p10 border-box">

	<div class="flex-container wp801 div-center">

		<div class="flex-string bluebg-sub m3 p5 pt10 pb10">Кол-во сделок: <b><?= array_sum( $counts ) ?></b></div>

		<?php
		foreach ( $steps as $step ) {

			print '<div class="flex-string graybg-sub m3 p5 pt10 pb10 div-center">Этап '.$step.'%: <b>'.$counts[ $step ].'</b></div>';

		}
		?>

	</div>

</div>

<hr>

<div class="block">

	<TABLE>
		<thead class="sticked--top top">
		<TR class="th35">
			<th class="w20 text-center"></th>
			<th class="w20 text-center"><b>#</b></th>
			<th class="w120 text-center"><b>Создана</b></th>
			<th class="w120 text-center"><b>Дата этапа</b></th>
			<th class="w120 text-center"><b>Активность</b></th>
			<th class="text-center"><b>Сделка</b></th>
			<th class="w120 text-center hidden-ipad"><b>Сумма</b></th>
			<th class="text-center"><b>Заказчик</b></th>
			<th class="w120 text-center"><b>Ответств.</b></th>
		</TR>
		</thead>
		<tbody>
		<?php

		foreach ( $steps as $step ) {

			if ( count( $dogs[ $step ] ) == 0 ) {
				continue;
			}

			print '
			<tr class="td--stepmain bluebg-sub p10 steptoggle hand" data-step="'.$step.'">
				<td class="text-right"><i class="icon-plus-circled blue"></i></td>
				<td colspan="8"><div class="p10 fs-11">Этап: <span class="Bold fs-11">'.$step.'%</span> [ '.count( $dogs[ $step ] ).' сделок ]</div></td>
			</tr>
			';

			$num = 1;
			foreach ( $dogs[ $step ] as $key => $val ) {

				$color  = ($num & 1) ? 'color1' : 'color2';
				$color2 = ($val['close'] == 'yes') ? 'red' : 'blue';
				$icon   = ($val['close'] == 'yes') ? 'icon-lock' : 'icon-briefcase';

				print '
				<tr class="datetoggle hidden td--main hand '.$color.'" data-key="'.$key.'" data-step="'.$step.'">
					<td class="text-right"><i class="icon-plus-circled gray2"></i></td>
					<td class="text-right"><b>'.$num.'.</b></td>
					<td><div class="fs-09">'.$val['dcreate'].'</div></td>
					<td><div class="fs-09">'.$val['dateStep'].'</div></td>
					<td><div class="fs-09">'.$val['datum'].'</div></td>
					<td>
						<div class="ellipsis">
							<A href="javascript:void(0)" onclick="openDogovor(\''.$key.'\')" title="Открыть в новом окне"><i class="'.$icon.' '.$color2.'"></i>&nbsp;'.$val['dogovor'].'</A>
						</div>
					</td>
					<td class="hidden-ipad"><div class="fs-09">'.num_format( $val['summa'] ).'</div></td>
					<td><div class="ellipsis"><A href="javascript:void(0)" onclick="openClient(\''.$val['clid'].'\')"><i class="icon-building broun"></i>&nbsp;'.$val['client'].'</A></div></td>
					<td><div class="ellipsis"><i class="icon-user-1 blue"></i>&nbsp;'.$val['user'].'</div></td>
				</tr>
				';

				$number = 1;

				foreach ( $list[ $step ][ $key ] as $k => $v ) {
					?>
					<TR class="ha hidden sub gray2 th40" data-date="<?= $key ?>">
						<TD></TD>
						<TD class="text-right"><?= $number ?>.</TD>
						<TD>
							<div class="ellipsis"></div>
						</TD>
						<TD class="fs-09"><?= $v['tip'] ?></TD>
						<TD class="fs-09"><?= $v['datum'] ?></TD>
						<TD colspan="3" class="fs-09"><?= str_replace( "\n", "<br>", $v['content'] ) ?></TD>
						<TD>
							<div class="ellipsis"><i class="icon-user-1 gray2"></i>&nbsp;<?= $v['user'] ?></div>
						</TD>
					</TR>
					<?php
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

<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script>

	drowChart();

	$('.datetoggle').bind('click', function () {

		var key = $(this).data('key');

		$('tr.sub').not('[data-date="' + key + '"]').addClass('hidden');
		$('tr.sub[data-date="' + key + '"]').toggleClass('hidden');

		$(this).find('i:first').toggleClass('icon-plus-circled icon-minus-circled');

	});

	$('.steptoggle').bind('click', function () {

		var step = $(this).data('step');
		var key = $(this).data('key');

		$('tr.datetoggle').not('[data-step="' + step + '"]').addClass('hidden');
		$('tr.datetoggle[data-step="' + step + '"]').toggleClass('hidden');

		$(this).toggleClass('bluebg bluebg-sub').find('i:first').toggleClass('icon-plus-circled icon-minus-circled');
		$('.steptoggle').not(this).addClass('bluebg-sub').removeClass('bluebg').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');

	});

	function drowChart() {

		var width = $('#chart').width() - 200;
		var height = 400;
		var svg = dimple.newSvg("#chart", "100%", "100%");
		var data = [<?=$datas?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width - 50, height - 40);

		var x = myChart.addCategoryAxis("x", ["Куратор"]);
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

		var s = myChart.addSeries(["Куратор", "Тип"], dimple.plot.bar);

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

		var state = $('.fixAddBotButton').data('state');

		//console.log(state);

		if (state === 'collapse') {

			$('.fixAddBotButton').data('state', 'expand');
			$('.fixAddBotButton').find('span').html('Свернуть всё');
			$('.fixAddBotButton').find('i:first').removeClass('icon-plus').addClass('icon-minus');
			$('tr.sub').removeClass('hidden').find('i:first').removeClass('icon-plus-circled').addClass('icon-minus-circled');
			$('tr.steptoggle').find('i:first').removeClass('icon-plus-circled').addClass('icon-minus-circled');
			$('tr.datetoggle').removeClass('hidden').find('i:first').removeClass('icon-plus-circled').addClass('icon-minus-circled');

		}
		if (state === 'expand') {

			$('.fixAddBotButton').data('state', 'collapse');
			$('.fixAddBotButton').find('span').html('Развернуть всё');
			$('.fixAddBotButton').find('i:first').addClass('icon-plus').removeClass('icon-minus');
			$('tr.sub').addClass('hidden').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');
			$('tr.steptoggle').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');
			$('tr.datetoggle').addClass('hidden').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');

		}

	}

</script>