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

$period = $_REQUEST['period'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort   = '';
$kolSum = 0;

$acolors = [
	"#f44336",
	"#FF9800",
	"#FFEB3B",
	"#4CAF50",
	"#2196F3",
	"#3F51B5",
	"#673AB7",
	"#E91E63",
	"#03A9F4",
	"#A1887F",
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

function getDateCustom($date) {
	$d = yexplode( " ", $date );

	return "<b>".format_date_rus( $d[0] )."</b> (".getTime( $d[1] ).")";
}

$res = $db -> getAll( "SELECT idcategory, CAST(title AS UNSIGNED) as step, content FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
foreach ( $res as $da ) {
	$tsteps[ $da['step'] ] = $da['content'];
}

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

$i = 0;

$q = "
	SELECT 
		deal.did as did,
		deal.title as dogovor,
		deal.datum as dcreate,
		deal.datum_close as dclose,
		deal.clid as clid,
		cc.title as client,
		deal.iduser as iduser,
		deal.kol as kol,
		deal.kol_fact as kolf,
		deal.des_fact as statuscontent,
		us.title as user,
		(dc.title + 0) as step,
		dc.content as steptitle,
		dt.title as tips,
		ds.title as dstatus,
		dir.title as direction
	FROM ".$sqlname."dogovor `deal`
		LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
		LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
		LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
		LEFT JOIN ".$sqlname."dogtips `dt` ON deal.tip = dt.tid
		LEFT JOIN ".$sqlname."dogstatus `ds` ON deal.sid = ds.sid
		LEFT JOIN ".$sqlname."direction `dir` ON deal.direction = dir.id
	WHERE 
		deal.datum_close BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and 
		deal.idcategory > 0 and
		deal.kol_fact = 0 and 
		$sort
		deal.identity = '$identity' 
	ORDER BY CAST(dc.title as DECIMAL)";

$da = $db -> getAll( $q );

$count = $list = $astat = $asumma = $datas = $chartPre = $chartPre2 = $ucount = $bsumma = $sscount = $steps = $dircount = $ssteps = [];

foreach ( $da as $data ) {

	if ( $data['step'] != '' ) {

		$dfact = '';
		$prim  = '';
		$color = '';

		$list[ $data['step'] ][] = [
			"dcreate"       => format_date_rus( $data['dcreate'] ),
			"dclose"        => format_date_rus( $data['dclose'] ),
			"did"           => $data['did'],
			"dogovor"       => $data['dogovor'],
			"clid"          => $data['clid'],
			"client"        => $data['client'],
			"user"          => $data['user'],
			"kolf"          => $data['kolf'],
			"kol"           => $data['kol'],
			"status"        => $data['dstatus'],
			"statuscontent" => $data['statuscontent'],
			"direction"     => $data['direction'],
		];

		$ssteps[] = $data['step'];

		$summa[ $data['step'] ]  += $data['kolf'];
		$bsumma[ $data['step'] ] += $data['kol'];
		$count[ $data['step'] ]++;
		$astat[ $data['dstatus'] ]++;
		$asumma[ $data['dstatus'] ] += $data['kol'];
		$ucount[ $data['step'] ][ $data['user'] ]++;
		$sscount[ $data['user'] ][ $data['step'] ]++;
		$dircount[ $data['direction'] ]++;

	}

	$ausers[ $data['user'] ] = $data['user'];

}

$ssteps = array_unique( $ssteps );

/**
 * для диаграммы "Потери по этапам"
 */
$orders = [];
foreach ( $ucount as $step => $val ) {

	foreach ( $val as $user => $num ) {

		$datas[] = '{"Этап":"'.$step.'%", "Кол-во":"'.pre_format( $num ).'", "Сотрудник":"'.$user.'"}';

	}

	$orders[] = '"'.$step.'%"';

}

$datas = implode( ",", $datas );
$order = implode( ",", $orders );

foreach ( $acolors as $i => $color ) {
	$colors[] = "'".$color."'";
}
foreach ( $ausers as $i => $user ) {
	$users[]  = "'".$user."'";
	$colors[] = "'".$acolors[ $i ]."'";
}

/**
 * для диаграммы "Причины потери"
 */

$i = 0;

foreach ( $astat as $status => $scount ) {

	$persent = round( $scount / array_sum( $count ) * 100, 0 );

	$chartPre[] = '{"Причина":"'.$status.' ( '.$persent.'% )","Кол-во":"'.$scount.'"}';

}

$chartData = implode( ",", $chartPre );

/**
 * для диаграммы "Процент потерь"
 */

$q2        = "
	SELECT 
		COUNT(deal.did) as count,
		SUM(deal.kol) as summa
	FROM ".$sqlname."dogovor `deal`
	WHERE 
		deal.datum_close BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and 
		deal.idcategory > 0 and
		deal.kol_fact > 0 and 
		$sort
		deal.identity = '$identity' 
	";
$goodCount = $db -> getRow( $q2 );

$persGood = ($goodCount['count'] + array_sum( $count )) > 0 ? round( $goodCount['count'] / ($goodCount['count'] + array_sum( $count )) * 100, 0 ) : 0;
$persBad  = ($goodCount['count'] + array_sum( $count )) > 0 ? round( array_sum( $count ) / ($goodCount['count'] + array_sum( $count )) * 100, 0 ) : 0;

$sumGood = ($goodCount['summa'] + array_sum( $bsumma )) > 0 ? round( $goodCount['summa'] / ($goodCount['summa'] + array_sum( $bsumma )) * 100, 0 ) : 0;
$sumBad  = ($goodCount['summa'] + array_sum( $bsumma )) > 0 ? round( array_sum( $bsumma ) / ($goodCount['summa'] + array_sum( $bsumma )) * 100, 0 ) : 0;

$chartDataB = '{"Результат":"Победа","Доля":"'.$sumGood.'%","Кол-во":"'.$goodCount['count'].'","Сумма":"'.$goodCount['summa'].'"},{"Результат":"Проигрыш","Доля":"'.$sumBad.'%","Кол-во":"'.array_sum( $count ).'","Сумма":"'.pre_format( array_sum( $bsumma ) ).'"}';

$chartDataD = '{"Результат":"Победа","Доля":"'.$sumGood.'%","Кол-во":"'.$goodCount['count'].'","Сумма":"'.$goodCount['summa'].'"},{"Результат":"Проигрыш","Доля":"'.$sumBad.'%","Кол-во":"'.array_sum( $count ).'","Сумма":"'.pre_format( array_sum( $bsumma ) ).'"}';

/**
 * для диаграммы "Потери по направлениям"
 */

$i = 0;

foreach ( $dircount as $direction => $scount ) {

	$persent = round( $scount / array_sum( $dircount ) * 100, 0 );

	if ( $direction == '' ) {
		$direction = 'Не задано';
	}

	$chartPre2[] = '{"Направление":"'.$direction.' ( '.$persent.'% )","Кол-во":"'.$scount.'"}';

}

$chartDataC = implode( ",", $chartPre2 );

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
		height    : 40px;
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

	/*table.borderer {
		border    : 1px solid #666;
		padding   : 2px 3px 2px 3px;
		font-size : 0.95em;
	}*/

	table.borderer thead tr th,
	table.borderer tr,
	table.borderer td {
		border-left   : 1px dotted #ccc !important;
		border-bottom : 1px dotted #ccc !important;
		padding       : 2px 3px 2px 3px;
		height        : 20px;
	}

	table.borderer thead th:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead tr:first-child th {
		border-top : 1px dotted #ccc !important;
	}

	table.borderer td:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead {
		border : 1px dotted #ccc !important;
	}

	table.borderer thead td,
	table.borderer thead th {
		background : #E5F0F9;
	}

	table.borderer thead th {
		border-bottom : 1px dotted #666 !important;
	}

	table.borderer thead {
		border : 1px dotted #222 !important;
	}

	-->
</style>

<div class="zagolovok_rep fs-12" align="center">
	<h1>Анализ потерянных сделок</h1>
	<b>за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?></b><span class="hidden"> (<a href="#" onClick="generate_csv()" style="color:blue">Скачать CSV</a>):</span>
</div>

<hr>

<div id="graf2" class="flex-container box--child mt20 mb10" style="">

	<div id="jschart" class="flex-string wp25">
		<div class="Bold gray2 fs-12 pl10 mb10">Причины потери</div>
		<div id="charta" class="block"></div>
	</div>

	<div id="jschart2" class="flex-string wp25 pr10">
		<div class="Bold gray2 fs-12 mb10">Доля потерянных (по кол-ву)</div>
		<div id="chartb" class="block"></div>
	</div>

	<div id="jschart4" class="flex-string wp25 pr10">
		<div class="Bold gray2 fs-12 mb10">Доля потерянных (по сумме)</div>
		<div id="chartd" class="block"></div>
	</div>

	<div id="jschart3" class="flex-string wp25 pr10">
		<div class="Bold gray2 fs-12 mb10">Потери по направлениям</div>
		<div id="chartc" class="block"></div>
	</div>

</div>

<hr>

<div id="graf" class="flex-container box--child mt20 mb20" style="">

	<div class="flex-string wp50 pr5">
		<div class="Bold gray2 fs-12 pl10 mb10">Потери по этапам</div>
		<div id="chart" class="block" style="height:300px;"></div>
	</div>

	<div class="flex-string wp50 pr15">
		<div class="Bold gray2 fs-12 pl10 mb10">Расшифровка (Диаграмма "Потери по этапам")</div>
		<div class="wp100">
			<TABLE cellpadding="5" cellspacing="0" align="center" class="wp97 fs-09 borderer">
				<thead>
				<TR>
					<th class="text-center" rowspan="2">Сотрудник</th>
					<th colspan="<?= count( $ucount ) ?>"><b>Этап</b></th>
				</TR>
				<TR>
					<?php
					foreach ( $ucount as $step => $val ) {
						print '<th class="w25"><b>'.$step.'</b></th>';
					}
					?>
				</TR>
				</thead>
				<tbody>
				<?php
				$i = 0;
				foreach ( $sscount as $user => $val ) {

					print '<tr class="ha">';
					print '<td title="'.$user.'"><div class="Bold ellipsis"><div class="bullet-mini mr5" style="background: '.$acolors[ $i ].'"></div>'.$user.'</div></td>';

					foreach ( $ssteps as $step ) {

						print '<td class="text-center">'.$val[ $step ].'</td>';

					}

					print '</tr>';

					$i++;

				}
				?>
				</tbody>
			</TABLE>
		</div>
	</div>

</div>

<hr>

<div class="block div-center mt0">

	<h3 class="text-left">Данные для анализа</h3>

	<TABLE class="wp100">
		<thead>
		<TR>
			<th class="w20 text-center"></th>
			<th class="w20 text-center"><b>#</b></th>
			<th colspan="4"><b>Этап</b></th>
			<th class="w250 text-center"><b>Количество</b></th>
			<th class="w120 text-center"><b>Сумма, план</b></th>
			<th class="w100 text-center"></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$num = 1;
		foreach ( $list as $key => $val ) {

			if ( $num & 1 )
				$color = 'color1';
			else $color = 'color2';

			print '
			<tr class="datetoggle td--main '.$color.'" data-key="'.$key.'">
				<td class="text-right"><i class="icon-plus-circled gray2"></i></td>
				<td></td>
				<td colspan="4" class="text-left">'.$key.'% - '.strtr( $key, $tsteps ).'</td>
				<td>'.$count[ $key ].'</td>
				<td class="text-right">'.num_format( $bsumma[ $key ] ).'</td>
				<td></td>
			</tr>
			<TR class="hidden sub graybg fs-09" data-date="'.$key.'">
				<th class="w20 text-center"></th>
				<th class="w20 text-center"><b>#</b></th>
				<th class="w90 text-center"><b>Создана</b></th>
				<th class="w90 text-center"><b>Закрыта</b></th>
				<th class="text-center"><b>Сделка</b></th>
				<th class="w120"><b>Направление</b></th>
				<th class="w250 text-center"><b>Результат: причина</b></th>
				<th class="w120 text-center"><b>Сумма, план</b></th>
				<th class="w100 text-center"><b>Ответств.</b></th>
			</TR>
			';

			$number = 1;

			//print_r($val);

			foreach ( $val as $da ) {

				if ( $da['statuscontent'] != '' )
					$status = $da['status'].': '.$da['statuscontent'];
				else $status = $da['status'];

				?>
				<TR class="ha hidden sub" data-date="<?= $key ?>">
					<TD></TD>
					<TD class="text-right"><?= $number ?>.</TD>
					<TD><?= $da['dcreate'] ?></TD>
					<TD><?= $da['dclose'] ?></TD>
					<TD class="text-left">
						<div class="ellipsis">
							<A href="javascript:void(0)" onclick="openDogovor('<?= $da['did'] ?>')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i><?= $da['dogovor'] ?>
							</A></div>
					</TD>
					<TD class="text-left">
						<div class="ellipsis" title="<?= $da['direction'] ?>">
							<?= $da['direction'] ?>
						</div>
					</TD>
					<TD class="text-left">
						<div class="ellipsis" title="<?= ($da['statuscontent'] != '') ? $da['status'].": ".$da['statuscontent'] : $da['status'] ?>">
							<?= ($da['statuscontent'] != '') ? $da['status'].": ".$da['statuscontent'] : $da['status'] ?>
						</div>
					</TD>
					<TD class="text-right"><?= num_format( $da['kol'] ) ?></TD>
					<TD class="text-left">
						<div class="ellipsis"><i class="icon-user-1 gray2"></i>&nbsp;<?= $da['user'] ?></div>
					</TD>
				</TR>
				<?php
				$number++;
			}
			$num++;
		}
		?>
		</tbody>
		<tfoot>
		<tr>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD></TD>
			<TD class="text-right">Итого:</TD>
			<TD><?= array_sum( $count ) ?></TD>
			<TD class="text-right"><?= num_format( array_sum( $bsumma ) ) ?></TD>
			<TD></TD>
		</tr>
		</tfoot>
	</TABLE>

</div>

<div style="height:100px;" class="block"></div>

<DIV class="fixAddBotButton noprint" style="left:auto; right: 50px" onclick="ToggleAll()" data-state="collapse">
	<i class="icon-plus"></i> <span>Развернуть всё</span>
</div>

<script src="/assets/js/chartjs/Chart.js"></script>
<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script>

	var wi = $(window).width();

	$(function () {

		$('#myChart').width(160);

		zdrawBarChart();

		adrawDonutChart();
		bdrawDonutChart();
		cdrawDonutChart();
		ddrawDonutChart();

		<?php
		if ( basename( $_SERVER['PHP_SELF'] ) == 'report_print.php' ) {

			print "ToggleAll();";

		}
		?>

	});

	$('.datetoggle').on('click', function () {

		var key = $(this).data('key');

		$('tr.sub').not('[data-date="' + key + '"]').addClass('hidden');
		$('tr.sub[data-date="' + key + '"]').toggleClass('hidden');

		$(this).find('i:first').toggleClass('icon-plus-circled icon-minus-circled');

	});

	function adrawDonutChart() {

		var data = [<?=$chartData?>];
		var width = $('#charta').width() - 30;
		if (wi > 1300) width = 300;
		var height = width * 0.9 + data.length * 20;
		var svg = dimple.newSvg("#charta", width, height);

		var amyChart = new dimple.chart(svg, data);

		amyChart.setBounds(20, 20, width, height);

		amyChart.addMeasureAxis("p", "Кол-во");
		var ring = amyChart.addSeries("Причина", dimple.plot.pie);

		ring.innerRadius = "50%";

		amyChart.defaultColors = [
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#E91E63", "#E91E63"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#03A9F4", "#03A9F4"),
			new dimple.color("#A1887F", "#A1887F"),
			new dimple.color("#FFEB3B", "#FFEB3B"),
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

		var myLegend = amyChart.addLegend(10, height - data.length * 25, 100, 100, "left");
		amyChart.setMargins(10, 20, 50, 50 + data.length * 20);
		amyChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			amyChart.draw(0, true);
		});

	}

	function bdrawDonutChart() {

		var data = [<?=$chartDataB?>];
		var width = $('#chartb').width() - 30;
		if (wi > 1300) width = 300;
		var height = width * 0.9 + data.length * 20;
		var svg = dimple.newSvg("#chartb", width, height);

		var amyChart = new dimple.chart(svg, data);

		amyChart.setBounds(20, 20, width, height);

		amyChart.addMeasureAxis("p", "Кол-во", "Сумма");
		var ring = amyChart.addSeries("Результат", dimple.plot.pie);

		ring.innerRadius = "50%";

		/*amyChart.defaultColors = [
		 new dimple.color("#8BC34A", "#8BC34A"),
		 new dimple.color("#DD2C00", "#DD2C00")
		 ];*/

		amyChart.assignColor("Победа", "#8BC34A");
		amyChart.assignColor("Проигрыш", "#DD2C00");

		var myLegend = amyChart.addLegend(10, height - data.length * 25, 100, 100, "left");
		amyChart.setMargins(10, 20, 50, 50 + data.length * 20);

		amyChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			amyChart.draw(0, true);
		});

	}

	function ddrawDonutChart() {

		var data = [<?=$chartDataD?>];
		var width = $('#chartd').width() - 30;
		if (wi > 1300) width = 300;
		var height = width * 0.9 + data.length * 20;
		var svg = dimple.newSvg("#chartd", width, height);

		var amyChart = new dimple.chart(svg, data);

		amyChart.setBounds(20, 20, width, height);

		amyChart.addMeasureAxis("p", "Сумма");
		var ring = amyChart.addSeries("Результат", dimple.plot.pie);

		ring.innerRadius = "50%";

		/*amyChart.defaultColors = [
		 new dimple.color("#DD2C00", "#DD2C00"),
		 new dimple.color("#8BC34A", "#8BC34A")
		 ];*/

		amyChart.assignColor("Победа", "#8BC34A");
		amyChart.assignColor("Проигрыш", "#DD2C00");

		var myLegend = amyChart.addLegend(10, height - data.length * 25, 100, 100, "left");
		amyChart.setMargins(10, 20, 50, 50 + data.length * 20);

		amyChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			amyChart.draw(0, true);
		});

	}

	function cdrawDonutChart() {

		var data = [<?=$chartDataC?>];
		var width = $('#chartc').width() - 30;
		if (wi > 1300) width = 300;
		var height = width * 0.9 + data.length * 20;
		var svg = dimple.newSvg("#chartc", width, height);

		var amyChart = new dimple.chart(svg, data);

		amyChart.setBounds(20, 20, width, height);

		amyChart.addMeasureAxis("p", "Кол-во", "Сумма");
		var ring = amyChart.addSeries("Направление", dimple.plot.pie);

		ring.innerRadius = "50%";

		amyChart.defaultColors = [
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#E91E63", "#E91E63"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#03A9F4", "#03A9F4"),
			new dimple.color("#A1887F", "#A1887F"),
			new dimple.color("#FFEB3B", "#FFEB3B"),
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

		var myLegend = amyChart.addLegend(10, height - data.length * 25, 100, 100, "left");
		amyChart.setMargins(10, 20, 50, 50 + data.length * 20);
		amyChart.draw(1000);

		$(window).bind('resizeEnd', function () {
			amyChart.draw(0, true);
		});

	}

	function zdrawBarChart() {

		var zdata = [<?=$datas?>];
		var width = $('#chart').width() - 20;
		var height = 250;
		var svg = dimple.newSvg("#chart", "100%", "100%");

		var zmyChart = new dimple.chart(svg, zdata);

		zmyChart.setBounds(0, 0, width - 20, height - 40);

		var x = zmyChart.addCategoryAxis("x", ["Этап"]);
		//todo: исправить сортировку
		x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
		x.showGridlines = false;

		var y = zmyChart.addMeasureAxis("y", "Кол-во");
		y.showGridlines = true;//скрываем линии

		var z = zmyChart.addMeasureAxis("z", "Сотрудник");
		//s.stacked = true;
		//s.lineWeight = 1;
		//s.lineMarkers = true;

		zmyChart.clamp = true;
		zmyChart.floatingBarWidth = 10;

		zmyChart.ease = "bounce";
		zmyChart.staggerDraw = true;

		zmyChart.setMargins(40, 20, 20, 40);

		/*zmyChart.defaultColors = [
		 new dimple.color("#f44336", "#f44336"),
		 new dimple.color("#FF9800", "#FF9800"),
		 new dimple.color("#FFEB3B", "#FFEB3B"),
		 new dimple.color("#4CAF50", "#4CAF50"),
		 new dimple.color("#673AB7", "#673AB7"),
		 new dimple.color("#2196F3", "#2196F3"),
		 new dimple.color("#3F51B5", "#3F51B5"),
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
		 ];*/

		<?php
		foreach ( $users as $i => $user ) {

			print "zmyChart.assignColor(".$user.", ".$colors[ $i ].");\n";

		}
		?>

		var s = zmyChart.addSeries(["Сотрудник"], dimple.plot.bar);

		s.barGap = 0.7;

		s.addEventHandler("click", function (e) {
			Toggle(e.xValue);
			//console.log(e);
		});

		zmyChart.draw(1000);

		//y.titleShape.remove();
		x.titleShape.remove();

		window.addEventListener('resize', function () {
			zmyChart.draw(0, true);
		});

	}

	function Toggle(date) {

		$('tr.sub').addClass('hidden');
		$('tr[data-date="' + parseInt(date) + '"]').toggleClass('hidden show');

		$('.td--main').find('i:first').addClass('icon-plus-circled').removeClass('icon-minus-circled');
		$('tr[data-key="' + parseInt(date) + '"]').find('i:first').removeClass('icon-plus-circled').addClass('icon-minus-circled');

		var $top = $('.td--main[data-key="' + parseInt(date) + '"]').offset();
		var ttop = $top.top - 150;
		$('#clientlist.nano').nanoScroller({scrollTop: ttop});

		//console.log($top);

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

</script>