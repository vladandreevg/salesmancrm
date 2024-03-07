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

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];

$sort = '';
$ar   = [
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

//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

$users = $db -> getIndCol( "iduser", "SELECT iduser, title FROM {$sqlname}user WHERE iduser IN (".yimplode( ",", $user_list ).") AND identity = '$identity'" );

$datas = $order = $step = [];

$resultt = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
foreach ( $resultt as $data ) {

	//текущий этап
	$step[] = '"'.current_dogstepname( $data['idcategory'] ).'%"';

	foreach ( $user_list as $iduser ) {

		//сумма сделок j-го пользователя i-го этапа
		$kol = (float)$db -> getOne( "
			SELECT 
				SUM(kol) as kol 
			FROM ".$sqlname."dogovor 
			WHERE 
				(
					(close != 'yes' and datum_plan between '".$da1." 00:00:00' and '".$da2." 23:59:59') or 
					(close='yes' and datum_close between '".$da1." 00:00:00' and '".$da2." 23:59:59')
				) and 
				idcategory = '".$data['idcategory']."' and 
				iduser = '$iduser' and  
				$sort
				identity = '$identity'
			" );

		if ( $kol > 0 ) {
			$datas[] = '{Этап:"'.current_dogstepname( $data['idcategory'] ).'%","Сотрудник":"'.$users[ $iduser ].'","Сумма":"'.$kol.'"}';
		}

	}

	$order[] = '"'.current_dogstepname( $data['idcategory'] ).'%"';

}

$count = count( $datas );
$datas = implode( ",", $datas );
$order = implode( ",", $order );

$list = $counts = $xcounts = $kol = $sum = [];

//массив пользователей
foreach ( $user_list as $iduser ) {

	//массив типов сделок
	$resultt = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
	foreach ( $resultt as $cdata ) {

		$deals = [];

		//массив сделок i-го пользователя j-го типа
		$resultm = $db -> getAll( "
			SELECT * 
			FROM ".$sqlname."dogovor 
			WHERE 
				(
					(close != 'yes' and datum_plan between '".$da1." 00:00:00' and '".$da2." 23:59:59') or 
					(close = 'yes' and datum_close between '".$da1." 00:00:00' and '".$da2." 23:59:59')
				) and 
				idcategory = '".$cdata['idcategory']."' and 
				iduser = '$iduser' and 
				identity = '$identity' 
			ORDER BY title
		" );
		foreach ( $resultm as $data ) {

			if ( $data['close'] == 'yes' ) {
				$close = 'yes';
				$icon  = '<i class="icon-lock red"></i>';
				$datum = $data['datum_close'];
				$kolp  = $data['kol_fact'];
			}
			else {
				$close = 'no';
				$icon  = '<i class="icon-briefcase-1 broun"></i>';
				$datum = $data['datum_plan'];
				$kolp  = $data['kol'];
			}

			$deals[] = [
				"did"   => $data['did'],
				"clid"  => $data['clid'],
				"pid"   => $data['pid'],
				"step"  => $cdata['title'],
				"title" => $data['title'],
				"kolp"  => $kolp,
				"datum" => $datum,
				"type"  => current_dogtype( (int)$data['tip'] ),
				"icon"  => $icon
			];

			//сумма сделок i-го пользователя j-го типа
			$kol[ $iduser ][ $cdata['idcategory'] ] += $data['kol'];
			//число сделок i-го пользователя j-го типа
			$sum[ $iduser ][ $cdata['idcategory'] ]++;

		}

		//массив типов сделок i-го пользователя
		$list[ $iduser ][$cdata['idcategory']] = [
			"name" => $cdata['title'],
			"des"  => $cdata['content'],
			"deals" => $deals
		];

	}

	//сумма сделок i-го пользователя
	$kolu[ $iduser ] = array_sum( (array)$kol[ $iduser ] );
	//число сделок i-го пользователя
	$sumu[ $iduser ] = array_sum( (array)$sum[ $iduser ] );

}

?>
<STYLE>
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
	#salestepss .pheader {
		display     : block;
		border      : 0 solid #79b7e7;
		background  : #78909C;
		font-weight : bold;
		height      : 30px;
		line-height : 30px;
		color       : #fff;
		font-size   : 12px;
	}
	#salestepss .pstring {
		background  : #CFD8DC;
		font-weight : bold;
		overflow    : hidden !important;
	}
	#salestepss .stringg:hover {
		background : #FF6;
	}
	#salestepss .stringg {
		border-bottom : 1px dotted #78909C;
		overflow      : hidden !important;
		box-sizing    : border-box;
	}
	#salestepss .column_1 {
		width       : 75%;
		display     : inline-block;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
	}
	#salestepss .column_2 {
		width       : 15%;
		display     : inline-block;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
	}
	#salestepss .column_3 {
		width       : 7%;
		display     : inline-block;
		line-height : 30px;
		float       : left;
		overflow    : hidden !important;
	}
	#salestepss .column_4 {
		width       : 15%;
		display     : inline-block;
		line-height : 25px;
		float       : left;
		overflow    : hidden !important;
	}
	#salestepss .user {
		background : #FFF9C4;
		cursor     : pointer;
	}
	#salestepss .pad20 {
		width   : 20px;
		display : inline-block;
	}
	#salestepss .pad40 {
		width   : 40px;
		display : inline-block;
	}
	#salestepss .pad60 {
		width   : 60px;
		display : inline-block;
	}
	#salestepss .cur {
		cursor : pointer;
	}
	#salestepss .sb {
		font-size   : 1em;
		font-weight : bold;
		background  : #E6E6FA;
	}
	-->
</STYLE>

<div class="zagolovok_rep">&nbsp;&nbsp;Pipeline. Сделки по Сотрудникам</div>

<hr>

<div>
	<?php if ( $count > 0 ) { ?>
		<div id="graf" style="display:block; height:400px">
			<div id="chart" style="padding:5px; height: 100%;"></div>
			<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>
			<script>

				var width = $('#contentdiv').width() - 40;
				var height = 400;
				var svg = dimple.newSvg("#chart", "100%", "100%");
				var data = [<?=$datas?>];

				var myChart = new dimple.chart(svg, data);

				myChart.setBounds(0, 0, width - 50, height - 40);

				var x = myChart.addCategoryAxis("x", ["Сотрудник"]);
				x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
				x.showGridlines = true;

				var y = myChart.addMeasureAxis("y", "Сумма");
				y.showGridlines = true;//скрываем линии
				//myChart.floatingBarWidth = 10;
				//y.ticks = 5;//шаг шкалы по оси y

				myChart.defaultColors = [
					new dimple.color("#03A9F4", "#03A9F4"),
					new dimple.color("#3F51B5", "#3F51B5"),
					new dimple.color("#4CAF50", "#4CAF50"),
					new dimple.color("#FFEB3B", "#FFEB3B"),
					new dimple.color("#FF9800", "#FF9800"),
					new dimple.color("#E91E63", "#E91E63"),
					new dimple.color("#F44336", "#F44336"),
					new dimple.color("#DD2C00", "#DD2C00"),
					new dimple.color("#673AB7", "#673AB7"),
					new dimple.color("#2196F3", "#2196F3"),
					new dimple.color("#A1887F", "#A1887F"),
					new dimple.color("#FFC107", "#FFC107"),
					new dimple.color("#8BC34A", "#8BC34A"),
					new dimple.color("#00BCD4", "#00BCD4"),
					new dimple.color("#795548", "#795548"),
					new dimple.color("#7CB342", "#7CB342"),
					new dimple.color("#0097A7", "#0097A7"),
					new dimple.color("#D500F9", "#D500F9"),
					new dimple.color("#76FF03", "#76FF03"),
					new dimple.color("#B0BEC5", "#B0BEC5"),
					new dimple.color("#90A4AE", "#90A4AE"),
					new dimple.color("#78909C", "#78909C"),
					new dimple.color("#607D8B", "#607D8B"),
					new dimple.color("#9E9E9E", "#9E9E9E"),
					new dimple.color("#6D4C41", "#6D4C41")
				];

				var s = myChart.addSeries(["Этап"], dimple.plot.bar);
				s.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
				//s.lineWeight = 2;
				//s.lineMarkers = true;
				s.stacked = true;
				//s.barGap = 5;

				//myChart.barGap = 0.5;
				var myLegend = myChart.addLegend(width - 100, 0, 100, 250, "right");
				myChart.setMargins(80, 20, 140, 150);

				//myChart.assignColor("План","green");

				myChart.draw(1000);

				myChart.legends = [];

				var filterValues = dimple.getUniqueValues(data, "Этап");
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
					myChart.data = dimple.filterData(data, "Этап", filterValues);
					myChart.draw(800);
				});

				y.tickFormat = ".2f";
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
</div>
<div id="salestepss" style="margin:10px 30px 10px 30px">

	<div class="pheader">
		<div class="column_1 text-center">[Текущая стадия]</div>
		<div class="column_2 text-right">[Сумма, <?= $valuta ?>]</div>
		<div class="column_3 text-right">[Кол-во]</div>
	</div>
	<?php
	foreach ($list as $iduser => $items) {

		if( $sumu[ $iduser ] == 0 ){
			continue;
		}
		?>
		<div class="stringg pstring">
			<div class="column_1 pl5"><?= $users[$iduser] ?></div>
			<div class="column_2 text-right"><?= num_format( $kolu[ $iduser ] ) ?></div>
			<div class="column_3 text-right"><?= number_format( $sumu[ $iduser ], 0, '.', ' ' ); ?></div>
		</div>
		<?php
		foreach ($items as $id => $item) {

			//не выводим этапы сделок, у которых количество = 0
			if ( !empty($sum[ $iduser ][ $id ]) ) {
				?>
				<div class="togglerbox hand stringg user stepname" data-id="block_<?= $iduser ?>_<?= $id ?>" title="Показать/Скрыть">
					<div class="column_1">
						<div class="inline w20">&nbsp;</div>&nbsp;<i class="icon-angle-down" id="mapic"></i>&nbsp;<b><?= $item['name'] ?>%</b> - <?= $item['des'] ?>
					</div>
					<div class="column_2 text-right">&nbsp;<?= num_format( $kol[ $iduser ][ $id ] ) ?></div>
					<div class="column_3 text-right">&nbsp;<?= number_format( $sum[ $iduser ][ $id ], 0, ',', ' ' ); ?></div>
				</div>
				<div id="block_<?= $iduser ?>_<?= $id ?>" class="hidden">
					<?php
					foreach ($item['deals'] as $deal) {

						if ( $deal['clid'] > 0 ) {
							$client = '<a href="javascript:void(0)" onclick="viewClient(\''.$deal['clid'].'\')">'.current_client( $deal['clid'] ).'</a>';
						}
						if ( $deal['pid'] > 0 ) {
							$client = '<a href="javascript:void(0)" onclick="viewPerson(\''.$deal['pid'].'\')">'.current_person( $deal['pid'] ).'</a>';
						}
						?>
						<div class="stringg cur">
							<div class="column_1">
								<div class="inline w40">&nbsp;</div>
								<?= $deal['icon'] ?>&nbsp;<span class="hand" title="Просмотр сделки" onClick="viewDogovor('<?= $deal['did'] ?>')"><?= $deal['title'] ?></span>&nbsp;[<b class="green"><?= format_date_rus( $deal['datum'] ) ?></b>]&nbsp;[<span class="blue"><?= $deal['type'] ?></span>]
							</div>
							<div class="column_2 text-right"><?= num_format( $deal['kolp'] ) ?></div>
							<div class="column_3 text-right">
								&nbsp;<a href="javascript:void(0)" onclick="viewDogovor('<?= $deal['did'] ?>')" title="Просмотр сделки"><i class="icon-briefcase broun"></i></a>
							</div>
						</div>
						<?php
					}
					?>
				</div>
				<?php
			}
		}
		?>
		<?php
	}
	?>
</div>

<hr>

<div class="formdiv">Вы можете использовать параметры: Период, Сотрудники, Сделки. В отчете выводятся
	<b>Активные</b> (по плановой дате) и <b>Закрытые</b> (по дате закрытия) сделки
</div>

<div style="height: 65px;"></div>