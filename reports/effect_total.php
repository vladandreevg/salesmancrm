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

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per ) {
	$per = 'nedelya';
}

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$sort1 = $sort2 = $sort = '';

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

if ( !empty($clients_list) && !empty($persons_list) ) {
	$sort1 .= "(deal.clid IN (".yimplode( ",", $clients_list).") OR deal.pid IN (".yimplode( ",", $persons_list ).")) AND ";
}
elseif ( !empty($clients_list) ) {
	$sort1 .= "deal.clid IN (".yimplode( ",", $clients_list).") AND ";
}
elseif ( !empty($persons_list) ) {
	$sort1 .= "deal.pid IN (".yimplode( ",", $persons_list ).") AND ";
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
		$sort2 .= " ".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort2 .= $field_query[ $i ] != 'yes' ? " COALESCE({$field}, 'no') != 'yes' AND " : " COALESCE({$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$sort .= $sort1.$sort2;

//Создание массивов данных
foreach ( $user_list as $user ) {

	$manpro = current_user( $user, "yes" );

	$hist = $db -> getOne( "
		SELECT 
		    COUNT(cid) as count 
		FROM ".$sqlname."history 
		WHERE 
			(datum between '$da1 00:00:01' and '$da2 23:59:59') and 
			iduser = '$user' and 
			tip != 'СобытиеCRM' and  
			$sort1
			identity = '$identity'
		" );

	$result4 = $db -> getRow( "
		SELECT 
		    COUNT(did) as count, 
		    SUM(kol) as kol 
		FROM ".$sqlname."dogovor 
		WHERE 
			(datum between '$da1' and '$da2') and 
			iduser = '$user' and  
			$sort
			identity = '$identity'
		" );
	$new_d   = $result4['count'];
	$kol     = $result4['kol'];

	$result5 = $db -> getRow( "
		SELECT 
		    COUNT(did) as count, 
		    SUM(kol) as kol 
		FROM ".$sqlname."dogovor 
		WHERE 
			(datum_izm between '$da1' and '$da2') and 
			iduser = '$user' and  
			$sort
			identity = '$identity'
		" );
	$izm_d   = $result5['count'];
	$kol_i   = $result5['kol'];

	$result6 = $db -> getRow( "
		SELECT 
		    COUNT(*) as count, 
		    SUM(kol_fact) as kol 
		FROM ".$sqlname."dogovor 
		WHERE 
			close = 'yes' and 
			(datum_close between '$da1' and '$da2') and 
			iduser = '$user' and  
			$sort1
			$sort2
			identity = '$identity'
		" );
	$cl_d    = $result6['count'];
	$kol_c   = $result6['kol'];

	$effect[] = [
		"manager"      => $manpro,
		"activ_client" => $hist,
		"new_dogs"     => $new_d,
		"kol_new"      => $kol,
		"izm_dogs"     => $izm_d,
		"kol_izm"      => $kol_i,
		"close_dogs"   => $cl_d,
		"kol_close"    => $kol_c
	];

	$datas[] = '{"Сотрудник":"'.current_user( $user, "yes" ).'", "Тип":"Новый","Кол-во":"'.$new_d.'"}';
	$datas[] = '{"Сотрудник":"'.current_user( $user, "yes" ).'", "Тип":"Изменен","Кол-во":"'.$izm_d.'"}';
	$datas[] = '{"Сотрудник":"'.current_user( $user, "yes" ).'", "Тип":"Закрыт","Кол-во":"'.$cl_d.'"}';

	$all_new_d += $new_d;
	$all_izm_d += $izm_d;
	$all_cl_d  += $cl_d;
	$all_kol   += pre_format( $kol );
	$all_kol_i += pre_format( $kol_i );
	$all_kol_c += pre_format( $kol_c );

	$hist   = 0;
	$hist_d = 0;
	$new_d  = 0;
	$izm_d  = 0;
	$cl_d   = 0;
	$kol    = 0;
	$kol_i  = 0;
	$kol_c  = 0;
}

//print_r($datas);

$count = count( $datas );
$datas = implode( ",", $datas );
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
</style>

<div class="zagolovok_rep" align="center">
	<b>Эффективность сотрудников за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></b>
</div>

<hr>

<?php if ( $count > 0 ) { ?>
	<div id="graf" style="display:block; height:350px">
		<div id="chart" style="padding:5px; height:100%"></div>
		<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
		<script>

			var width = $('#contentdiv').width() - 40;
			var height = 350;
			var svg = dimple.newSvg("#chart", "100%", "100%");
			var data = [<?=$datas?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(0, 0, width - 50, height - 40);

			var x = myChart.addCategoryAxis("x", ["Тип"]);
			//x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart.addMeasureAxis("y", "Кол-во");
			y.showGridlines = true;//скрываем линии
			//myChart.floatingBarWidth = 10;
			//y.ticks = 5;//шаг шкалы по оси y

			myChart.defaultColors = [
				new dimple.color("#2196F3", "#2196F3"),
				new dimple.color("#F44336", "#F44336"),
				new dimple.color("#4CAF50", "#4CAF50"),
				new dimple.color("#795548", "#795548"),
				new dimple.color("#FF9800", "#FF9800"),
				new dimple.color("#673AB7", "#673AB7"),
				new dimple.color("#3F51B5", "#3F51B5"),
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
				new dimple.color("#03A9F4", "#03A9F4"),
				new dimple.color("#607D8B", "#607D8B"),
				new dimple.color("#9E9E9E", "#9E9E9E"),
				new dimple.color("#6D4C41", "#6D4C41")
			];

			var s = myChart.addSeries(["Сотрудник"], dimple.plot.bar);
			//s.lineWeight = 2;
			//s.lineMarkers = true;
			s.stacked = true;
			//s.barGap = 5;

			//myChart.barGap = 0.5;
			var myLegend = myChart.addLegend(width - 250, 0, 200, 250, "right");
			myChart.setMargins(100, 20, 240, 35);

			//myChart.assignColor("План","green");

			myChart.draw(1000);

			myChart.legends = [];

			var filterValues = dimple.getUniqueValues(data, "Сотрудник");
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
				myChart.data = dimple.filterData(data, "Сотрудник", filterValues);
				myChart.draw(800);
			});

			y.tickFormat = ".f";
			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			$(window).bind('resizeEnd', function () {
				myChart.draw(0, true);
			});

		</script>
	</div>
	<hr>
<?php } ?>
<table class="wp95">
	<thead>
	<TR height="40">
		<th width="30" align="center">&nbsp;</th>
		<th align="center"><B>Сотрудник</B></th>
		<th width="40" align="center"><b>Активностей</b></th>
		<th width="60" align="center"><B>Сделок<BR>новых</B></th>
		<th width="100" align="center"><b>Сумма,<?= $valuta ?></b></th>
		<th width="60" align="center"><B>Сделок<BR>измен.</B></th>
		<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
		<th width="60" align="center"><b>Сделок<BR>закр.</b></th>
		<th width="100" align="center"><b>Сумма, <?= $valuta ?></b></th>
	</TR>
	</THEAD>
	<TBODY>
	<?php
	foreach ($effect as $i => $row) {
		?>
		<TR height="40" class="ha">
			<TD align="center"># <?= $i + 1 ?></TD>
			<TD>
				<DIV class="ellipsis1" title="<?= $row['manager'] ?>"><?=$row['manager'] ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $row['activ_client'] ?>"><?= $row['activ_client'] ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $row['new_dogs'] ?>"><?= $row['new_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= $row['kol_new'] ?>"><?= num_format( $row['kol_new'] ) ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $row['izm_dogs'] ?>"><?= $row['izm_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= $row['kol_izm'] ?>"><?= num_format( $row['kol_izm'] ) ?></DIV>
			</TD>
			<TD align="center">
				<DIV title="<?= $row['close_dogs'] ?>"><?= $row['close_dogs'] ?></DIV>
			</TD>
			<TD align="right" nowrap>
				<DIV title="<?= $row['kol_close'] ?>"><?= num_format( $row['kol_close'] ) ?></DIV>
			</TD>
		</TR>
		<?php
	}
	?>
	<TR height="30" bgcolor="#FC9">
		<TD align="center">&nbsp;</TD>
		<TD align="center"><B>ИТОГО</B></TD>
		<TD align="center"><B><?= $all_hist ?></B></TD>
		<TD align="center"><B><?= $all_new_d ?></B></TD>
		<TD align="right" nowrap><B><?= num_format( $all_kol ) ?></B></TD>
		<TD align="center"><B><?= $all_izm_d ?></B></TD>
		<TD align="right" nowrap><B><?= num_format( $all_kol_i ) ?></B></TD>
		<?php if ( $plan_form == 'datum_close' ) { ?>
			<TD align="center"><B><?= $all_cl_d ?></B></TD>
			<TD align="right" nowrap><B><?= num_format( $all_kol_c ) ?></B></TD>
		<?php } ?>
	</TR>
	</TBODY>
</TABLE>
<div style="height:90px"></div>