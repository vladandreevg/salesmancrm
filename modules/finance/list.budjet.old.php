<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */

/* ============================ */

use Salesman\BankStatement;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$year = $_REQUEST[ 'year' ];
$mon2 = $_REQUEST[ 'mon' ];
$tar  = $_REQUEST[ 'tar' ];
$rs   = $_REQUEST[ 'rs' ];

$isMac = $detect -> is( 'Mac' );

$isApple = $isPad || $isMac;

if ( $year == '' ) $year = date( 'Y' );

if ( $tar == 'budjet' ) {

	if ( count( $rs ) != "" ) $sort .= " and rs IN (".implode( ",", $rs ).")";
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

	<TABLE id="list_header1" class="budjet">
		<thead class="sticked--top">
		<TR class="header_contaner th30">
			<TH class="w100 text-left">
				<DIV class="ellipsis">Статья</DIV>
			</TH>
			<?php for ( $m = 1; $m <= 12; $m++ ) { ?>
				<TH class="text-left mounth">
					<DIV class="ellipsis"><b><?= ru_month( $m ) ?>.</b></DIV>
				</TH>
			<?php } ?>
			<TH class="text-left yw1001">
				<DIV class="ellipsis"><b>Итого, <?= $valuta ?></b></DIV>
			</TH>
			<TH class="w5"></TH>
		</TR>
		</thead>
		<tbody>

		<tr class="th40 toggler graybg-dark hand white <?php echo( !$isApple ? 'sticked--top' : '' ) ?>" onClick="$('#warndiv').toggleClass('hidden'); drowChart();">
			<td colspan="15" class="text-center">
				<span class="Bold"><i class="icon-chart-line"></i>&nbsp;ГРАФИК</span>
			</td>
		</tr>
		<TR id="warndiv" class="hidden" data-step="8" data-intro="<h1>Финансовый график</h1>Отображает график расходов и доходов, а так же показывает Финансовый результат" data-position="top">
			<td colspan="15">
				<?php
				//массив статей расхода
				$ras = $db -> getCol( "SELECT id FROM ".$sqlname."budjet_cat WHERE tip='rashod' and identity = '$identity' ORDER BY title" );
				$ras = implode( ",", $ras );

				$doh = $db -> getCol( "SELECT id FROM ".$sqlname."budjet_cat WHERE tip='dohod' and identity = '$identity' ORDER BY title" );
				$doh = implode( ",", $doh );

				$delta = [];

				for ( $m = 1; $m <= 12; $m++ ) {

					//массив поступивших денег от клиентов
					$oplata[ $n ] = $db -> getOne( "SELECT SUM(summa_credit) as oplata FROM ".$sqlname."credit WHERE DATE_FORMAT(invoice_date, '%Y-%c') = '".$year."-".$m."' and do = 'on' $sort and identity = '$identity'" ) + 0;

					$oplata[ $n ] += $db -> getOne( "SELECT SUM(summa) as rashod FROM ".$sqlname."budjet WHERE year='".$year."' and mon='".$m."' and cat IN(".$doh.") and do = 'on' $sort and identity = '$identity'" ) + 0;

					$datas[] = '{Тип:"Поступления","Месяц":"'.ru_month( $m ).'","Сумма, '.$valuta.'":"'.$oplata[ $n ].'"}';

					//проведенные расходы
					$rashod[ $n ] = $db -> getOne( "SELECT SUM(summa) as rashod FROM ".$sqlname."budjet WHERE year='".$year."' and mon='".$m."' and cat IN(".$ras.") and do = 'on' $sort and identity = '$identity'" ) + 0;

					$datas[] = '{Тип:"Расходы","Месяц":"'.ru_month( $m ).'","Сумма, '.$valuta.'":"-'.$rashod[ $n ].'"}';

					$delta[ $n ] = $oplata[ $n ] - $rashod[ $n ];
					if ( !$delta[ $n ] ) $delta[ $n ] = 0;

					$dataf[] = '{Тип:"Фин.результат","Месяц":"'.ru_month( $m ).'","Сумма, '.$valuta.'":"'.pre_format( $delta[ $n ] ).'"}';

					$order[] = '"'.ru_month( $m ).'"';
				}

				$datas = implode( ",", $datas );
				$dataf = implode( ",", $dataf );
				$order = implode( ",", $order );
				?>
				<div id="graf" style="display:block; height:410px; width: 100%;">
					<div id="chartBudjet" style="padding:5px"></div>
					<script src="/assets/js/dimple.js/dimple.min.js"></script>
					<script>

						function drowChart() {

							$('#chartBudjet').empty();

							var width = $('#contentdiv').width() - 40;
							var height = $('#graf').actual('height') - 40;
							var svg = dimple.newSvg("#chartBudjet", width, height);
							var data = [<?=$datas?>];

							var myChart = new dimple.chart(svg, data);

							myChart.setBounds(100, 0, width - 50, height - 40);

							var x = myChart.addCategoryAxis("x", ["Месяц"]);
							x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
							x.showGridlines = true;

							var y = myChart.addMeasureAxis("y", "Сумма, <?=$valuta?>");
							y.showGridlines = false;//скрываем линии
							myChart.floatingBarWidth = 10;
							y.ticks = 5;//шаг шкалы по оси y

							y.tickFormat = ",.2f";

							//first color is fill, second is stroke
							/*myChart.defaultColors = [
							 new dimple.color("#B0BEC5", "#B0BEC5"),
							 new dimple.color("#2196F3", "#2196F3"),
							 new dimple.color("#b71c1c", "#b71c1c")
							 ];*/

							myChart.assignColor("Поступления", "#2196F3"); // <------- ASSIGN COLOR HERE
							myChart.assignColor("Расходы", "#B71C1C"); // <------- ASSIGN COLOR HERE
							myChart.assignColor("Фин.результат", "#CFD8DC", "#CFD8DC"); // <------- ASSIGN COLOR HERE

							myChart.ease = "bounce";
							myChart.staggerDraw = true;

							var s1 = myChart.addSeries(["Фин.результат"], dimple.plot.bar);
							var s = myChart.addSeries(["Тип"], dimple.plot.line);

							s.stacked = false; //делает блоки слипшимися
							s1.barGap = 0.3;//
							s.lineWeight = 2;//толщина линии
							s.lineMarkers = true;//маркеры
							//s.interpolation = "cardinal";//делает линии плавными

							myChart.barGap = 0.3;
							myChart.addLegend(0, 0, width - 35, 0, "right");
							myChart.setMargins(90, 50, 40, 40);

							myChart.draw(1000);

							s.shapes.style("opacity", function (d) {
								return (d.y === null ? 0 : 0.8);
							});

							y.titleShape.remove();
							x.titleShape.remove();

						}

						$(window).on('resizeEnd', function () {
							drowChart();
						});

					</script>
				</div>
			</td>
		</TR>

		<TR class="th40 bluebg toggler <?php echo( !$isApple ? 'sticked--top' : '' ) ?>">
			<TD colspan="15" class="text-center"><span class="Bold">ДОХОДЫ</span></TD>
		</TR>
		<!--/Оплаты счетов/-->
		<tr class="graybg th35">
			<td colspan="15">
				<div class="fs-11 gray2">Раздел: <b class="black">Поступления от продаж</b></div>
			</td>
		</tr>
		<TR class="th35 ha">
			<TD class="w100 text-left">
				<DIV title="Оплата счетов" class="ellipsis gray-dark Bold">Оплата счетов</DIV>
			</TD>
			<?php
			$oplata[ 0 ] = $oplata_no[ 0 ] = 0;
			for ( $m = 1; $m <= 12; $m++ ) {

				//оплаченные счета
				$oplata[ $m ] = $db -> getOne( "SELECT SUM(summa_credit) as suma FROM ".$sqlname."credit WHERE DATE_FORMAT(invoice_date, '%Y-%c') = '".$year."-".$m."' and do = 'on' $sort and identity = '$identity'" );

				//выставленные не оплаченные счета
				$oplata_no[ $m ] = $db -> getOne( "SELECT SUM(summa_credit) as suma1 FROM ".$sqlname."credit WHERE DATE_FORMAT(datum_credit, '%Y-%c') = '".$year."-".$m."' and do != 'on' $sort and identity = '$identity'" );

				$cla = ( $oplata[ $m ] > 0 ) ? "white blue ptb5 Bold" : "";
				$cls = ( $oplata_no[ $m ] > 0 ) ? "white red ptb5 Bold" : "";
				?>
				<TD class="mounth">
					<DIV class="ellipsis" title="Проведено: <?= num_format( $oplata[ $m ] ) ?>">
						<?php
						if ( $oplata[ $m ] > 0 ) {

							print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewpayment\',\''.$m.'\',\''.$year.'\',\'on\')" class="'.$cla.'">'.num_format( $oplata[ $m ] ).'&nbsp;</a>';

						}
						else {

							print '<span class="gray ptb5">'.num_format( $oplata[ $m ] ).'</span>&nbsp;';
						}
						?>
					</DIV>
					<br>
					<DIV class="ellipsis mt5" title="Не проведено: <?= num_format( $oplata_no[ $m ] ) ?>">
						<?php
						if ( $oplata_no[ $m ] > 0 ) {

							print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewpayment\',\''.$m.'\',\''.$year.'\',\'\')" class="'.$cls.'">'.num_format( $oplata_no[ $m ] ).'&nbsp;</a>';

						}
						else {

							print '<span class="gray ptb5">'.num_format( $oplata_no[ $m ] ).'</span>&nbsp;';

						}
						?>
					</DIV>
				</TD>
				<?php
			}
			?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="<?= num_format( array_sum( $oplata ) ) ?>"><?= num_format( array_sum( $oplata ) ) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Оплаты счетов/-->
		<!--/Доходы/-->
		<?php
		$summo_all = [];
		$result    = $db -> getAll( "SELECT * FROM ".$sqlname."budjet_cat WHERE tip='dohod' and (subid='0' or subid = id) and identity = '$identity' ORDER BY title" );
		foreach ( $result as $da ) {

			$sumDohod = [];
			?>
			<tr class="graybg th35">
				<td colspan="15">
					<div class="fs-11 gray2">
						Раздел: <b class="black"><?= $da[ 'title' ] ?></b>
						<div class="pull-aright mr10 fs-09 hidden-iphone">
							<a href="javascript:void(0)" onclick="editBudjet('<?= $da[ 'id' ] ?>','cat.edit')" title="Изменить раздел" class="gray"><i class="icon-pencil smalltxt broun"></i> Изменить</a>
						</div>
					</div>
				</td>
			</tr>
			<?php
			$resultt = $db -> getAll( "SELECT id,title FROM ".$sqlname."budjet_cat WHERE tip='dohod' and subid='".$da[ 'id' ]."' and identity = '$identity' ORDER BY title" );
			foreach ( $resultt as $data ) {
				?>
				<TR class="th55 ha">
					<TD class="w100 text-left">
						<DIV title="<?= $data[ 'title' ] ?>" class="ellipsis gray-dark Bold"><?= $data[ 'title' ] ?></DIV>
					</TD>
					<?php
					for ( $m = 1; $m <= 12; $m++ ) {

						$dat = $year."-".$m."-01";

						$summa[ $m ]     = $db -> getOne( "SELECT SUM(summa) as summ FROM ".$sqlname."budjet WHERE year='".$year."' and mon='".$m."' and cat='".$data[ 'id' ]."' and do='on' $sort and identity = '$identity'" ) + 0;
						$summa_all[ $m ] = $summa_all[ $m ] + $summa[ $m ];

						$summo[ $m ]     = $db -> getOne( "SELECT SUM(summa) as summ FROM ".$sqlname."budjet WHERE year='".$year."' and mon='".$m."' and cat='".$data[ 'id' ]."' and do!='on' $sort and identity = '$identity'" ) + 0;
						$summo_all[ $m ] = $summo_all[ $m ] + $summo[ $m ];

						$sumDohod[ $m ] = $sumDohod[ $m ] + $summa[ $m ];

						$cla = ( $summa[ $m ] > 0 ) ? "white bluebg ptb5 pl5" : "";
						$cls = ( $summo[ $m ] > 0 ) ? "white redbg ptb5 pl5" : "";
						?>
						<TD class="mounth relativ">
							<DIV class="ellipsis" title="Проведено: <?= num_format( $summa[ $m ] ) ?>">
								<?php
								if ( $summa[ $m ] > 0 ) {

									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'on\',\''.$data[ 'id' ].'\')" class="'.$cla.'">'.num_format( $summa[ $m ] ).'&nbsp;</a>';

								}
								else {

									print '<span class="gray ptb5">'.num_format( $summa[ $m ] ).'</span>&nbsp;';

								}
								?>
							</DIV>
							<br>
							<DIV class="ellipsis" title="Не проведено: <?= num_format( $summo[ $m ] ) ?>">
								<?php
								if ( $summo[ $m ] > 0 ) {

									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'\',\''.$data[ 'id' ].'\')" class="'.$cls.'">'.num_format( $summo[ $m ] ).'&nbsp;</a>';

								}
								else {

									print '<span class="gray ptb5">'.num_format( $summo[ $m ] ).'</span>&nbsp;';

								}
								?>
							</DIV>
							<div class="saction" title="Добавить расход" onclick="viewBudjet('edit','<?= $m ?>','<?= $year ?>','on','<?= $data[ 'id' ] ?>')">
								<i class="icon-plus-circled"></i>
							</div>
						</TD>
					<?php } ?>
					<TD>
						<DIV class="ellipsis" title="Проведено: <?= num_format( array_sum( $summa ) ) ?>">
							<b><?= num_format( array_sum( $summa ) ) ?></b></DIV>
						<br>
						<DIV class="ellipsis" style="color:red" title="Не проведено: <?= num_format( array_sum( $summo ) ) ?>">
							<b><?= num_format( array_sum( $summo ) ) ?></b></DIV>
					</TD>
					<TD class="hidden-iphone">
						<div class="action--container">
							<div class="action--block">
								<a href="javascript:void(0)" onclick="editBudjet('<?= $data[ 'id' ] ?>','cat.edit')" title="Изменить статью" class="gray brounbg2"><i class="icon-pencil broun"></i></a>
							</div>
						</div>
					</TD>
				</TR>
				<?php
			}
			?>
			<TR class="th40 graybg">
				<TD class="w100 text-left"><span class="Bold">Итого:</span></TD>
				<?php
				for ( $m = 1; $m <= 12; $m++ ) {
					?>
					<TD class="mounth">
						<DIV class="ellipsis" title="Проведено: <?= num_format( $sumDohod[ $m ] ) ?>">
							<b><?= num_format( $sumDohod[ $m ] ) ?></b>
						</DIV>
					</TD>
				<?php } ?>
				<TD class="yw1001">
					<DIV class="ellipsis" title="Проведено: <?= num_format( array_sum( $sumDohod ) ) ?>">
						<b><?= num_format( array_sum( $sumDohod ) ) ?></b>
					</DIV>
				</TD>
				<TD class="hidden-iphone"></TD>
			</TR>
			<?php
		}
		?>
		<TR class="th30 bluebg-sub border-bottom">
			<TD class="w100 text-left"><span class="Bold">Доходы, ИТОГО:</span></TD>
			<?php
			for ( $m = 1; $m <= 12; $m++ ) {

				$plus_all[ $m ]  = $oplata[ $m ] + $summa_all[ $m ];
				$pluso_all[ $m ] = $oplata_no[ $m ] + $summo_all[ $m ];

				?>
				<TD class="mounth">
					<DIV class="ellipsis Bold" title="Проведено: <?= num_format( $plus_all[ $m ] ) ?>"><?= num_format( $plus_all[ $m ] ) ?></DIV>
					<br>
					<DIV class="ellipsis Bold mt5 red" title="Не проведено: <?= num_format( $pluso_all[ $m ] ) ?>"><?= num_format( $pluso_all[ $m ] ) ?></DIV>
				</TD>
			<?php } ?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="Проведено: <?= num_format( array_sum( $plus_all ) ) ?>"><?= num_format( array_sum( $plus_all ) ) ?></DIV>
				<br>
				<DIV class="ellipsis Bold mt5 red" title="Не проведено: <?= num_format( array_sum( $pluso_all ) ) ?>"><?= num_format( array_sum( $pluso_all ) ) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Доходы/-->
		<!--/Расходы/-->
		<TR class="th40 redbg toggler <?php echo( !$isApple ? 'sticked--top' : '' ) ?>">
			<TD colspan="15" style="border-top:2px solid #000 !important;" class="text-center">
				<span class="Bold">РАСХОДЫ</span></TD>
		</TR>
		<?php
		$sum_all = $sumo_all = [];
		$result  = $db -> getAll( "SELECT * FROM ".$sqlname."budjet_cat WHERE tip='rashod' and (subid='0' or subid = id) and identity = '$identity' ORDER BY title" );
		foreach ( $result as $da ) {

			$sumRashod = [];
			?>
			<tr class="graybg th35">
				<td colspan="15">
					<div class="fs-11 gray2">
						Раздел: <b class="black"><?= $da[ 'title' ] ?></b>
						<div class="pull-aright mr10 fs-09">
							<a href="javascript:void(0)" onclick="editBudjet('<?= $da[ 'id' ] ?>','cat.edit')" title="Изменить раздел" class="gray"><i class="icon-pencil smalltxt broun"></i>
								<span class="hidden-iphone">Изменить</span></a>
						</div>
					</div>
				</td>
			</tr>
			<?php
			$resultt = $db -> getAll( "SELECT * FROM ".$sqlname."budjet_cat WHERE tip='rashod' AND subid='".$da[ 'id' ]."' ORDER BY title" );
			foreach ( $resultt as $data ) {

				$clientpath = '';

				if ( $data[ 'clientpath' ] > 0 ) $clientpath = $db -> getOne( "SELECT name FROM ".$sqlname."clientpath WHERE id = '".$data[ 'clientpath' ]."'" );

				$clientpath = ( $clientpath != '' ) ? '<br><div class="ellipsis fs-09 broun" title="Связано с каналом: '.$clientpath.'">'.$clientpath.'</div> ' : "";

				?>
				<TR class="th55 ha">
					<TD class="w100 text-left">
						<DIV title="<?= $data[ 'title' ] ?>" class="ellipsis gray-dark Bold">
							<?= $data[ 'title' ] ?>
						</DIV>
						<?= $clientpath ?>
					</TD>
					<?php
					for ( $m = 1; $m <= 12; $m++ ) {

						$dat = $year."-".$m."-01";

						$suma[ $m ]    = $db -> getOne( "SELECT SUM(summa) as suma FROM ".$sqlname."budjet WHERE year='".$year."' and mon='".$m."' and cat='".$data[ 'id' ]."' and do='on' $sort and identity = '$identity'" ) + 0;
						$sum_all[ $m ] += $suma[ $m ];

						$sumo[ $m ]     = $db -> getOne( "SELECT SUM(summa) as summ FROM ".$sqlname."budjet WHERE year='".$year."' and mon='".$m."' and cat='".$data[ 'id' ]."' and do!='on' $sort and identity = '$identity'" ) + 0;
						$sumo_all[ $m ] += $sumo[ $m ];

						$sumRashod[ $m ] += $suma[ $m ];

						$cla = ( $suma[ $m ] > 0 ) ? "white blue ptb5 Bold" : "";
						$cls = ( $sumo[ $m ] > 0 ) ? "white red ptb5 Bold" : "";

						?>
						<TD class="mounth relativ" height="35">
							<DIV class="ellipsis" title="Проведено: <?= num_format( $suma[ $m ] ) ?>">
								<?php
								if ( $suma[ $m ] > 0 ) {

									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'on\',\''.$data[ 'id' ].'\')" class="'.$cla.'">'.num_format( $suma[ $m ] ).'&nbsp;</a>';

								}
								else {

									print '<span class="gray ptb5">'.num_format( $suma[ $m ] ).'</span>&nbsp;';

								}
								?>
							</DIV>
							<br>
							<DIV class="ellipsis mt5" title="Не проведено: <?= num_format( $sumo[ $m ] ) ?>">
								<?php
								if ( $sumo[ $m ] > 0 ) print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'\',\''.$data[ 'id' ].'\')" class="'.$cls.'">'.num_format( $sumo[ $m ] ).'</a>';
								else print '<span class="gray">'.num_format( $sumo[ $m ] ).'</span>';
								?>
							</DIV>
							<div class="saction" title="Добавить расход" onclick="viewBudjet('edit','<?= $m ?>','<?= $year ?>','on','<?= $data[ 'id' ] ?>')">
								<i class="icon-plus-circled"></i>
							</div>
						</TD>
					<?php } ?>
					<TD class="yw1001">
						<DIV class="ellipsis Bold" title="Проведено: <?= num_format( array_sum( $suma ) ) ?>"><?= num_format( array_sum( $suma ) ) ?></DIV>
					</TD>
					<TD class="hidden-iphone">
						<div class="action--container">
							<div class="action--block">
								<a href="javascript:void(0)" onclick="editBudjet('<?= $data[ 'id' ] ?>','cat.edit')" title="Изменить статью" class="gray brounbg2"><i class="icon-pencil broun"></i></a>
							</div>
						</div>
					</TD>
				</TR>
				<?php
			}
			?>
			<TR class="th40 graybg-sub cherta gray2">
				<TD class="w100 text-left"><span class="Bold">Итого:</span></TD>
				<?php
				for ( $m = 1; $m <= 12; $m++ ) {
					?>
					<TD class="mounth">
						<DIV class="ellipsis Bold" title="Проведено: <?= num_format( $sumRashod[ $m ] ) ?>"><?= num_format( $sumRashod[ $m ] ) ?></DIV>
					</TD>
				<?php } ?>
				<TD class="yw1001">
					<DIV class="ellipsis Bold mt5" title="Проведено: <?= num_format( array_sum( $sumRashod ) ) ?>"><?= num_format( array_sum( $sumRashod ) ) ?></DIV>
				</TD>
				<TD class="hidden-iphone"></TD>
			</TR>
			<?php
		}
		?>
		<TR class="th35 redbg-sub">
			<TD class="w100 text-left"><span class="Bold">Расходы, ИТОГО:</span></TD>
			<?php
			for ( $m = 1; $m <= 12; $m++ ) {
				?>
				<TD class="mounth">
					<DIV class="ellipsis Bold" title="Проведено: <?= num_format( $sum_all[ $m ] ) ?>"><?= num_format( $sum_all[ $m ] ) ?></DIV>
					<br>
					<DIV class="ellipsis Bold mt5 red" title="Не проведено: <?= num_format( $sumo_all[ $m ] ) ?>"><?= num_format( $sumo_all[ $m ] ) ?></DIV>
				</TD>
			<?php } ?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="Проведено: <?= num_format( array_sum( $sum_all ) ) ?>"><?= num_format( array_sum( $sum_all ) ) ?></DIV>
				<br>
				<DIV class="ellipsis Bold mt5 red" title="Не проведено: <?= num_format( array_sum( $sumo_all ) ) ?>"><?= num_format( array_sum( $sumo_all ) ) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Расходы/-->
		</tbody>
		<tfoot>
		<!--/Фин.результат/-->
		<TR class="th40 greenbg-dark" style="border-top:2px solid #000">
			<TD class="w100 text-left" style="border-top:2px solid #000"><span class="Bold">Фин. результат:</span></TD>
			<?php
			for ( $m = 1; $m <= 12; $m++ ) {
				$deltaa[ $m ] = $plus_all[ $m ] - $sum_all[ $m ];
				?>
				<TD class="" style="border-top:2px solid #000">
					<DIV class="ellipsis" title="<?= num_format( $deltaa[ $m ] ) ?>"><?= num_format( $deltaa[ $m ] ) ?></DIV>
				</TD>
			<?php } ?>
			<TD class="yw1001" style="border-top:2px solid #000">
				<DIV class="ellipsis" title="<?= num_format( array_sum( $deltaa ) ) ?>"><?= num_format( array_sum( $deltaa ) ) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Фин.результат/-->
		</tfoot>
	</TABLE>

	<div style="height:40px;"></div>
	<?php

	exit();

}
if ( $tar == 'journal' ) {

	$sort     = '';
	$doo      = $_REQUEST[ 'doo' ];
	$rs       = $_REQUEST[ 'rs' ];
	$category = $_REQUEST[ 'category' ];

	if ( $doo == 'do' ) $sort .= " and do = 'on'";
	if ( $doo == 'nodo' ) $sort .= " and do != 'on'";
	//else $sort .= '';

	$word = urldecode( $_REQUEST[ 'word' ] );
	if ( $word != '' ) $sort .= " and (title LIKE '%".$word."%' or des LIKE '%".$word."%')";
	if ( count( $rs ) > 0 ) $sort .= " and rs IN (".implode( ",", $rs ).")";
	if ( count( $category ) > 0 ) $sort .= " and cat IN (".implode( ",", $category ).")";


	$res = $db -> getAll( "SELECT * FROM ".$sqlname."budjet WHERE year = '".$year."' ".$sort." and identity = '$identity' ORDER by mon DESC, datum DESC" );
	foreach ( $res as $data ) {

		$cat   = $tip = $change = $move = $isdo = $smove = '';
		$clone = 1;

		$resultt = $db -> getRow( "SELECT * FROM ".$sqlname."budjet_cat WHERE id='".$data[ 'cat' ]."' and identity = '$identity'" );
		$cat     = $resultt[ "title" ];
		$tip     = $resultt[ "tip" ];
		$subid   = $resultt[ "subid" ];

		if ( $data[ 'cat' ] == '0' ) $cat = 'Внетреннее';

		$razdel = $db -> getOne( "SELECT title FROM ".$sqlname."budjet_cat WHERE id='".$subid."' and identity = '$identity'" );

		if ( $tip == 'dohod' ) $tip = '<b class="green" title="Поступление"><i class="icon-up-big green"></i></b>';
		if ( $tip == 'rashod' ) $tip = '<b class="red" title="Расход"><i class="icon-down-big red"></i></b>';

		if ( $data[ 'cat' ] == '0' ) $tip = '<b class="blue" title="Перемещение"><i class="icon-shuffle blue"></i></b>';

		if ( $data[ 'do' ] == 'on' && $data[ 'cat' ] != '0' ) {

			$do    = '<a href="javascript:void(0)" onClick="editBudjet(\''.$data[ 'id' ].'\',\'undoit\');" title="Отменить" class="gray gray2"><i class="icon-ccw blue"></i></a>';
			$color = '';

		}
		if ( $data[ 'do' ] == 'on' && $data[ 'cat' ] == '0' ) {

			$do    = '<a href="javascript:void(0)" onClick="editBudjet(\''.$data[ 'id' ].'\',\'unmove\');" title="Отменить" class="gray gray2"><i class="icon-ccw blue"></i></a>';
			$color = '';

		}
		if ( $data[ 'do' ] != 'on' && $data[ 'cat' ] != '0' ) {

			$do    = '<a href="javascript:void(0)" onClick="editBudjet(\''.$data[ 'id' ].'\',\'edit\')" title="Провести" class="gray orange"><i class="icon-plus-circled broun"></i></a>';
			$color = 'graybg-sub';

		}
		if ( $data[ 'cat' ] != '0' && $data[ 'do' ] != 'on' ) $change = 'yes';

		if ( $data[ 'cat' ] == '0' ) {

			//$do    = '';//'<a href="javascript:void(0)" title="" class="gray green"><i class="icon-ok green"></i></a>';
			$color = '';
			$clone = '';
			$move  = 1;

		}
		if ( $data[ 'do' ] == 'on' ) {

			$isdo = '1';

		}

		if ( $data[ 'rs2' ] > 0 ) {

			$move = 1;

			$rsfrom = $db -> getOne( "SELECT title FROM ".$sqlname."mycomps_recv WHERE id = '$data[rs]' and identity = '$identity'" );
			$rsto   = $db -> getOne( "SELECT title FROM ".$sqlname."mycomps_recv WHERE id = '$data[rs2]' and identity = '$identity'" );

			$smove = "Со счета $rsfrom на счет $rsto";

		}

		$ist = $db -> getOne( "SELECT tip FROM ".$sqlname."mycomps_recv WHERE id = '".$data[ 'rs' ]."' and identity = '$identity'" );

		if ( $ist == 'bank' ) $istochnik = 'р/сч.';
		elseif ( $ist == 'kassa' ) $istochnik = 'касса';
		else                      $istochnik = '-/-';

		$provider = $db -> getOne( "SELECT title FROM ".$sqlname."clientcat WHERE clid = '".$data[ 'conid' ]."' and identity = '$identity'" );
		$partner  = $db -> getOne( "SELECT title FROM ".$sqlname."clientcat WHERE clid = '".$data[ 'partid' ]."' and identity = '$identity'" );

		$list[] = [
			"id"        => $data[ 'id' ],
			"datum"     => get_sfdate2( $data[ 'datum' ] ),
			"period"    => $data[ 'mon' ].".".$data[ 'year' ],
			"ddo"       => $do,
			"title"     => $data[ 'title' ],
			"content"   => $data[ 'des' ],
			"summa"     => num_format( $data[ 'summa' ] ),
			"tip"       => $tip,
			"category"  => $cat,
			"istochnik" => $istochnik,
			"conid"     => $data[ 'conid' ],
			"provider"  => $provider,
			"partid"    => $data[ 'partid' ],
			"partner"   => $partner,
			"did"       => $data[ 'did' ],
			"deal"      => current_dogovor( $data[ 'did' ] ),
			"user"      => current_user( $data[ 'iduser' ] ),
			"change"    => $change,
			"color"     => $color,
			"mon"       => $data[ 'mon' ],
			"clone"     => $clone,
			"move"      => $move,
			"smove"     => $smove,
			"isdo"      => $isdo
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => (int)$page,
		"pageall" => (int)$count_pages,
		"valuta"  => $valuta
	];

	print json_encode_cyr( $lists );

	exit();

}
if ( $tar == 'statement' ) {

	/**
	 * Добавим таблицу журнала, если нужно
	 */
	BankStatement ::checkDB();

	$sort     = '';
	$doo      = $_REQUEST[ 'doo' ];
	$rs       = $_REQUEST[ 'rs' ];
	$category = $_REQUEST[ 'category' ];

	if ( $doo == 'do' ) $sort .= " and bid > 0";
	if ( $doo == 'nodo' ) $sort .= " and bid = 0";

	$word = urldecode( $_REQUEST[ 'word' ] );
	if ( $word != '' )
		$sort .= " and (title LIKE '%$word%' or content LIKE '%$word%')";

	if ( !empty( $rs ) )
		$sort .= " and rs IN (".implode( ",", $rs ).")";

	if ( !empty( $category ) )
		$sort .= " and category IN (".implode( ",", $category ).")";


	// находим имеющиеся у нас расчетные счета
	$myRS = $db -> getIndCol( "id", "SELECT title, id FROM ".$sqlname."mycomps_recv WHERE rs != '' and rs != '0' and identity = '$identity' ORDER by id" );


	$res = $db -> getAll( "SELECT * FROM ".$sqlname."budjet_bank WHERE year = '$year' $sort and identity = '$identity' ORDER by mon DESC, datum DESC" );
	//print $db -> lastQuery();
	foreach ( $res as $data ) {

		$cat          = $tip = $change = $move = $isdo = $smove = '';
		$data[ 'do' ] = '';
		$budjet       = $invoice = $client = [];
		$isnoclient   = false;
		$color        = '';

		if ( $data[ 'clid' ] > 0 ) {

			//поищем клиента
			$client = $db -> getRow( "SELECT type, title FROM ".$sqlname."clientcat WHERE clid = '$data[clid]' AND identity = '$identity'" );

			//обработаем клиента
			if ( in_array( $client[ 'type' ], [
				'client',
				'person'
			] ) ) {

				// найдем счет клиенту по ИНН
				$invoice = $db -> getRow( "SELECT * FROM ".$sqlname."credit WHERE clid = '$data[clid]' AND summa_credit = '$data[summa]' and DATE_FORMAT(datum_credit, '%Y-%c') = '$data[year]-$data[mon]'" );

			}

		}

		if ( $data[ 'category' ] > 0 ) {

			$resultt = $db -> getRow( "SELECT * FROM ".$sqlname."budjet_cat WHERE id = '".$data[ 'category' ]."' and identity = '$identity'" );
			$cat     = $resultt[ "title" ];
			$tip     = $resultt[ "tip" ];
			$subid   = $resultt[ "subid" ];

			$razdel = $db -> getOne( "SELECT title FROM ".$sqlname."budjet_cat WHERE id = '$subid' and identity = '$identity'" );

		}

		if ( $data[ 'bid' ] > 0 ) {

			$budjet = $db -> getRow( "SELECT * FROM ".$sqlname."budjet WHERE id = '$data[bid]' and identity = '$identity'" );

		}

		if ( $data[ 'tip' ] == 'dohod' )
			$tip = '<b class="green" title="Поступление"><i class="icon-up-big green"></i></b>';
		elseif ( $data[ 'tip' ] == 'rashod' )
			$tip = '<b class="red" title="Расход"><i class="icon-down-big red"></i></b>';

		if ( $data[ 'bid' ] == 0 && !in_array( $client[ 'type' ], [
				'client',
				'person'
			] ) && $data[ 'title' ] != 'Переводы внутренние' ) {

			$tip        .= '<i class="icon-block-1 red" title="Не добавлен в расходы"></i>';
			$isnoclient = true;
			$color      = 'redbg-sub';

		}

		elseif ( $data[ 'bid' ] == 0 && in_array( $client[ 'type' ], [
				'client',
				'person'
			] ) )
			$tip .= '<i class="icon-block-1 gray" title="Платежи клиентов не обрабатываются"></i>';

		elseif ( $data[ 'bid' ] == 0 && $data[ 'title' ] == 'Переводы внутренние' ) {

			$tip        .= '<i class="icon-block-1 gray" title="Данный вид расходов не обрабатывается"></i>';
			$isnoclient = false;

		}

		if ( $budjet[ 'do' ] == 'on' )
			$isdo = '1';

		$contragent = ( $data[ 'tip' ] == 'dohod' ) ? $data[ 'from' ] : $data[ 'to' ];

		$ist = $db -> getOne( "SELECT tip FROM ".$sqlname."mycomps_recv WHERE id = '".$data[ 'rs' ]."' and identity = '$identity'" );

		$list[] = [
			"id"         => $data[ 'id' ],
			"bid"        => ( $data[ 'bid' ] > 0 ) ? $data[ 'bid' ] : NULL,
			"datum"      => format_date_rus( $data[ 'datum' ] ),
			"period"     => $data[ 'mon' ].".".$data[ 'year' ],
			"title"      => $data[ 'title' ],
			"content"    => $data[ 'content' ],
			"summa"      => num_format( $data[ 'summa' ] ),
			"tip"        => $tip,
			"category"   => $cat,
			"rs"         => strtr( $data[ 'rs' ], $myRS ),
			"clid"       => ( $data[ 'clid' ] > 0 ) ? $data[ 'clid' ] : NULL,
			"client"     => current_client( $data[ 'clid' ] ),
			"did"        => $data[ 'did' ],
			"deal"       => current_dogovor( $data[ 'did' ] ),
			"user"       => current_user( $data[ 'iduser' ] ),
			"color"      => ( $data[ 'title' ] == 'Платеж от клиента' ) ? "graybg-sub" : $color,
			"mon"        => $data[ 'mon' ],
			"contragent" => $contragent,
			"crid"       => $invoice[ 'crid' ],
			"invoice"    => $invoice[ 'invoice' ],
			"isdo"       => $isdo,
			"isnoclient" => $isnoclient,
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => (int)$page,
		"pageall" => (int)$count_pages,
		"valuta"  => $valuta
	];

	print json_encode_cyr( $lists );

	exit();

}

//новое представление
if ( $tar == 'provider' ) {

	$filter = $_REQUEST[ 'pdoo' ];
	$partid = $_REQUEST[ 'partid' ];
	$conid  = $_REQUEST[ 'conid' ];
	$sort   = '';

	$list  = [];
	$total = 0;

	$count = count( $filter );
	$fsort = [];
	$psort = [];

	//выполненные
	if ( in_array( 'do', $filter ) )
		$fsort[] = $sqlname."budjet.do = 'on'";

	//не запланированные
	if ( in_array( 'noadd', $filter ) )
		$fsort[] = $sqlname."budjet.id is NULL";

	//запланированные
	if ( in_array( 'plan', $filter ) )
		$fsort[] = "(".$sqlname."budjet.do != 'on' and ".$sqlname."budjet.id is NOT NULL)";

	if ( count( $fsort ) > 0 )
		$sort .= " and (".implode( ' or ', $fsort ).")";

	//if (strlen($mon) < 2) $mon = "0".$mon;

	if ( count( $partid ) > 0 )
		$psort[] = $sqlname."dogprovider.partid IN (".yimplode( ",", $partid ).")";

	if ( count( $conid ) > 0 )
		$psort[] = $sqlname."dogprovider.conid IN (".yimplode( ",", $conid ).")";

	if ( count( $psort ) > 0 )
		$sort .= " and (".implode( ' or ', $psort ).")";

	$q = "
		SELECT
			".$sqlname."dogprovider.id as id,
			".$sqlname."dogprovider.did as did,
			".$sqlname."dogprovider.conid as conid,
			".$sqlname."dogprovider.partid as partid,
			".$sqlname."dogprovider.summa as summa,
			".$sqlname."dogprovider.bid as bid,
			".$sqlname."dogovor.title as dogovor,
			".$sqlname."dogovor.datum_plan as dplan,
			".$sqlname."budjet.id as bjid,
			".$sqlname."budjet.do as do,
			".$sqlname."budjet.mon as mon,
			".$sqlname."budjet.year as year,
			".$sqlname."budjet.datum as datum
		FROM ".$sqlname."dogprovider
			LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."dogprovider.did = ".$sqlname."dogovor.did
			LEFT JOIN ".$sqlname."budjet ON ".$sqlname."dogprovider.bid = ".$sqlname."budjet.id
		WHERE
			".$sqlname."dogprovider.id > 0
			$sort
			and (
				( (".$sqlname."budjet.year = '' OR ".$sqlname."budjet.year IS NULL) AND DATE_FORMAT(".$sqlname."dogovor.datum_plan, '%Y') = '$year') or 
				-- DATE_FORMAT(".$sqlname."dogovor.datum_plan, '%Y') = '$year' or
				".$sqlname."budjet.year = '$year' or 
				".$sqlname."dogprovider.did = '0'
			)
			and ".$sqlname."dogprovider.identity = '$identity'
		-- ORDER BY ".$sqlname."dogovor.datum_plan DESC, ".$sqlname."dogprovider.id DESC
		ORDER BY ".$sqlname."dogprovider.id DESC
	";

	$result = $db -> getAll( $q );
	foreach ( $result as $da ) {

		$provid    = 0;
		$dogstatus = '';

		//$dogstatus  = 0;
		$progressbg = ' progress-gray';
		$prcolor    = 'black';

		//получим данные по сделке
		$deal = json_decode( get_dog_info( $da[ 'did' ] ), true );

		$dogstatus  = current_dogstepname( $deal[ 'idcategory' ] );
		$dogcontent = current_dogstepcontent( $deal[ 'idcategory' ] );

		if ( $dogstatus != '' ) {

			if ( is_between( $dogstatus, 0, 40 ) ) {
				$progressbg = ' progress-gray';
				$prcolor    = 'black';
			}
			elseif ( is_between( $dogstatus, 40, 60 ) ) {
				$progressbg = ' progress-green';
				$prcolor    = 'white';
			}
			elseif ( is_between( $dogstatus, 60, 90 ) ) {
				$progressbg = ' progress-red';
				$prcolor    = 'white';
			}
			elseif ( is_between( $dogstatus, 90, 100 ) ) {
				$progressbg = ' progress-blue';
				$prcolor    = 'white';
			}

		}

		$period = ( $da[ 'mon' ] > 0 ) ? $da[ 'mon' ].'.'.$da[ 'year' ] : NULL;

		$icn = ( getDogData( $da[ 'did' ], 'close' ) == 'yes' ) ? '<i class="icon-lock red"></i>' : '';

		if ( $da[ 'conid' ] > 0 ) {
			$contragent = current_client( $da[ 'conid' ] );
			$tip        = 'contractor';
			$tipname    = 'Поставщик';
			$provid     = $da[ 'conid' ];
			$s          = "and conid = '".$da[ 'conid' ]."'";
		}
		elseif ( $da[ 'partid' ] > 0 ) {
			$contragent = current_client( $da[ 'partid' ] );
			$tip        = 'partner';
			$tipname    = 'Партнер';
			$provid     = $da[ 'partid' ];
			$s          = "and partid = '".$da[ 'partid' ]."'";
		}

		if ( $da[ 'did' ] < 1 ) $da[ 'dplan' ] = $da[ 'year' ].'-'.$da[ 'mon' ].'-01';

		$list[] = [
			"id"              => $da[ 'id' ],
			"month"          => getMonth( $da[ 'dplan' ] ),
			"bid"             => ( $da[ 'bid' ] == 0 ) ? NULL : $da[ 'bid' ],
			"do"              => ( $da[ 'do' ] == 'on' ) ? true : NULL,
			"dotext"          => $da[ 'do' ],
			"period"          => $period,
			"providerId"      => $provid,
			"providerTitle"   => $contragent,
			"providerTip"     => $tip,
			"providerTipName" => $tipname,
			"summa"           => num_format( $da[ 'summa' ] ),
			"progressbar"     => ( $deal[ 'did' ] > 0 ) ? '<DIV class="progressbarr">'.$dogstatus.'%<DIV id="test" class="progressbar-completed '.$progressbg.'" style="width:'.$dogstatus.'%" title="'.$dogstatus." - ".$dogcontent.'"><DIV class="status '.$prcolor.'"></DIV></DIV></DIV>' : '',
			"datePlan"        => format_date_rus( $deal[ 'datum_plan' ] ),
			"clid"            => ( $deal[ 'clid' ] > 0 ) ? $deal[ 'clid' ] : NULL,
			"client"          => ( $deal[ 'clid' ] > 0 ) ? current_client( $deal[ 'clid' ] ) : NULL,
			"pid"             => ( $deal[ 'pid' ] > 0 ) ? $deal[ 'pid' ] : NULL,
			"person"          => ( $deal[ 'pid' ] > 0 ) ? current_person( $deal[ 'pid' ] ) : NULL,
			"did"             => ( $deal[ 'did' ] > 0 ) ? $deal[ 'did' ] : NULL,
			"deal"            => ( $deal[ 'did' ] > 0 ) ? current_dogovor( $deal[ 'did' ] ) : NULL,
			"icon"            => $icn,
			"bgcolor"         => ( $da[ 'do' ] == 'on' ) ? 'bgwhite' : 'graybg-sub gray2',
		];

		$total += $da[ 'summa' ];

	}

	$lists = [
		"list"     => $list,
		"page"     => (int)$page,
		"pageall"  => (int)$count_pages,
		"valuta"   => $valuta,
		"total"    => num_format( $total ),
		"dealname" => $lang[ 'face' ][ 'DealName' ][ 0 ]
	];

	print json_encode_cyr( $lists );

	exit();

}

//устаревшее представление
if ( $tar == 'partner' ) {
	?>
	<TABLE id="list_header">
		<thead>
		<TR class="header_contaner">
			<TH class="yw40"><B>№ п/п</B></TH>
			<TH class="yw40"></TH>
			<TH class="yw120"><B>Сумма, <?= $valuta ?></B></TH>
			<TH><B>Сделка</B></TH>
			<TH class="yw60"><b>Этап</b></TH>
			<TH class="yw80"><B>Дата</B></TH>
			<TH class="yw250 text-left"><B>Заказчик</B></TH>
		</TR>
		</thead>
		<?php
		$i       = 1;
		$kol_all = 0;
		$res     = $db -> query( "SELECT * FROM ".$sqlname."clientcat WHERE type = 'partner' and identity = '$identity' ORDER by title" );
		while ( $data = $db -> fetch( $res ) ) {
			?>
			<TR height="35" bgcolor="#ECECFB">
				<TD colspan="7" bgcolor="#ECECFB">
					<div class="paddleft10 uppercase">
						<A href="javascript:void(0)" onclick="openClient('<?= $data[ 'clid' ] ?>')" title="В новом окне"><b><i class="icon-flag broun"></i>&nbsp;<?= $data[ 'title' ] ?>
							</b></A></div>
				</TD>
			</TR>
			<?php
			$result = $db -> query( "SELECT * FROM ".$sqlname."dogprovider WHERE partid = '".$data[ 'clid' ]."' and identity = '$identity'" );
			while ( $datad = $db -> fetch( $result ) ) {

				//получим данные по сделке
				$json   = get_dog_info( $datad[ 'did' ] );
				$dataar = json_decode( $json, true );

				$dogstatus  = current_dogstepname( $dataar[ 'idcategory' ] );
				$dogcontent = current_dogstepcontent( $dataar[ 'idcategory' ] );

				if ( $dogstatus ) {
					if ( $dogstatus < 40 ) {
						$progressbg = ' progress-gray';
						$prcolor    = 'black';
					}
					elseif ( $dogstatus >= 40 and $dogstatus < 60 ) {
						$progressbg = ' progress-green';
						$prcolor    = 'white';
					}
					elseif ( $dogstatus >= 60 and $dogstatus <= 90 ) {
						$progressbg = ' progress-red';
						$prcolor    = 'white';
					}
					elseif ( $dogstatus > 90 and $dogstatus <= 100 ) {
						$progressbg = ' progress-blue';
						$prcolor    = 'white';
					}
				}
				else {
					$dogstatus  = 0;
					$progressbg = ' progress-gray';
					$prcolor    = 'black';
				}

				if ( getDogData( $datad[ 'did' ], 'close' ) == 'yes' ) $icn = '<i class="icon-lock red"></i>';
				else $icn = '';
				?>
				<TR height="35" class="ha">
					<TD class="yw40" align="center"><?= $i ?></TD>
					<TD class="yw40" align="center">
						<?php
						$bjid     = 0;
						$result_f = $db -> getRow( "SELECT id, do FROM ".$sqlname."budjet WHERE did = '".$dataar[ 'did' ]."' AND partid = '".$datad[ 'partid' ]."'" );
						$bjid     = $result_f[ "id" ];
						$bjdo     = $result_f[ "do" ];

						if ( $bjid == 0 ) {
							?>
							<a href="javascript:void(0)" onclick="editProvider('<?= $datad[ 'id' ] ?>','addprovider','<?= $dataar[ 'did' ] ?>','<?= $datad[ 'partid' ] ?>')" title="Добавить расход в бюджет"><i class="icon-attention gray"></i></a>
							<?php
						}
						else {
							if ( $bjdo == 'on' ) print '<i class="icon-ok green list" title="Расход проведен"></i>';
							else print '<i class="icon-clock blue list" title="Расход занесен в бюджет, но не проведен"></i>';
						}
						?>
					</TD>
					<TD class="yw120" align="right"><?= num_format( $datad[ 'summa' ] ) ?></TD>
					<TD>
						<div class="ellipsis" title="<?= $dataar[ 'title' ] ?>">
							<?= $icn ?>
							<A href="javascript:void(0)" onclick="openDogovor('<?= $datad[ 'did' ] ?>')" title="Открыть"><i class="icon-briefcase broun"></i>&nbsp;<?= $dataar[ 'title' ] ?>
							</a>
						</div>
					</TD>
					<TD class="yw60">
						<DIV class="progressbarr"><?= $dogstatus ?>%
							<DIV id="test" class="progressbar-completed <?= $progressbg ?>" style="width:<?= $dogstatus ?>%" title="<?= $dogstatus." - ".$dogcontent ?>">
								<DIV class="status <?= $prcolor ?>"></DIV>
							</DIV>
						</DIV>
					</TD>
					<TD class="yw80" align="center"><?= format_date_rus( $dataar[ 'datum_plan' ] ) ?></TD>
					<TD class="yw250">
						<DIV class="ellipsis">
							<?php if ( $dataar[ 'clid' ] > 0 ) { ?>
								<a href="javascript:void(0)" onClick="viewClient('<?= $dataar[ 'clid' ] ?>')" title="Просмотр: <?= current_client( $dataar[ 'clid' ] ) ?>"><i class="icon-building broun"></i>&nbsp;<?= current_client( $dataar[ 'clid' ] ) ?>
								</a>
							<?php } ?>
							<?php if ( $dataar[ 'pid' ] > 0 ) { ?>
								<a href="javascript:void(0)" onClick="viewPerson('<?= $dataar[ 'pid' ] ?>')" title="Просмотр: <?= current_person( $dataar[ 'pid' ] ) ?>"><i class="icon-user-1 blue"></i>&nbsp;<?= current_person( $dataar[ 'pid' ] ) ?>
								</a>
							<?php } ?>
						</DIV>
					</TD>
				</TR>
				<?php
				$kol_all   = $kol_all + $datad[ 'summa' ];
				$dataar    = [];
				$dogstatus = '';
				$i++;
			}
		}
		$kol_all = num_format( $kol_all );
		?>
		<TR height="40" class="greenbg-dark">
			<TD align="right" nowrap>&nbsp;</TD>
			<TD align="right" nowrap>&nbsp;</TD>
			<TD align="right" nowrap><b>ВСЕГО:</b></TD>
			<TD align="right" nowrap><B>&nbsp;<?= $kol_all ?></B></TD>
			<TD align="right" nowrap>&nbsp;</TD>
			<TD align="right" nowrap>&nbsp;</TD>
			<TD align="right" nowrap>&nbsp;</TD>
		</TR>
	</TABLE>
	<div style="height:40px;"></div>
	<?php
}

if ( $tar == 'invoices' ) {

	$page = $_REQUEST[ 'page' ];
	$word = $_REQUEST[ 'iword' ];
	$word = str_replace( " ", "%", $word );
	if ( $word == 'undefined' ) $word = '';

	$ord = $_REQUEST[ 'ord' ];
	if ( $ord == '' ) $ord = "datum_credit"; //параметр сортировки

	$tuda   = $_REQUEST[ 'tuda' ];
	$pay1   = $_REQUEST[ 'pay1' ];
	$pay2   = $_REQUEST[ 'pay2' ];
	$iduser = $_REQUEST[ 'iduser' ];
	$rs     = $_REQUEST[ 'rs' ];
	$sort   = '';

	$sort .= ( $mon2 != '' ) ? " and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$mon2'" : " and DATE_FORMAT(invoice_date, '%Y') = '$year'";
	$sort .= ( $iduser != '' ) ? " and iduser = '$iduser'" : get_people( $iduser1 );

	if ( count( $rs ) != "" )
		$sort .= " and rs IN (".implode( ",", $rs ).")";

	$lines_per_page = $num_client; //Стоимость записей на страницу

	if ( $word != "" )
		$sort .= " and (invoice LIKE '%$word%' OR invoice_chek LIKE '%".$word."%' OR did IN (SELECT did FROM ".$sqlname."dogovor WHERE title LIKE '%$word%') OR clid IN (SELECT clid FROM ".$sqlname."clientcat WHERE title LIKE '%$word%'))";

	$query     = "SELECT * FROM ".$sqlname."credit WHERE crid != '' $sort and do = 'on' and identity = '$identity' ORDER BY invoice_date DESC";
	$result    = $db -> getAll( $query );
	$all_lines = count( $result );

	if ( !isset( $page ) or empty( $page ) or $page <= 0 ) $page = 1;
	else $page = (int)$page;
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$query .= " LIMIT $lpos,$lines_per_page";

	$res         = $db -> getAll( $query );
	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages == 0 ) $count_pages = 1;

	foreach ( $res as $data ) {

		$re    = $db -> getRow( "SELECT * FROM ".$sqlname."dogovor where did = '".$data[ 'did' ]."' and identity = '$identity'" );
		$payer = $re[ "payer" ];
		$clid  = $re[ "clid" ];
		$pid   = $re[ "pid" ];

		$re  = $db -> getRow( "SELECT * FROM ".$sqlname."mycomps_recv WHERE id = '".$data[ 'rs' ]."' and identity = '$identity' ORDER by id" );
		$rs  = $re[ "title" ];
		$cid = $re[ "cid" ];

		$mcid = $db -> getOne( "SELECT name_shot FROM ".$sqlname."mycomps WHERE id = '$cid' and identity = '$identity' ORDER by id" );

		$view = ( $otherSettings['printInvoice'] ) ? 'yes' : '';

		if ( $data[ 'invoice_chek' ] == '' )
			$data[ 'invoice_chek' ] = 'Без договора';

		$list[] = [
			"id"       => $data[ 'crid' ],
			"datum"    => format_date_rus( $data[ 'invoice_date' ] ),
			"contract" => $data[ 'invoice_chek' ],
			"invoice"  => $data[ 'invoice' ],
			"summa"    => num_format( $data[ 'summa_credit' ] ),
			"clid"     => $clid,
			"client"   => current_client( $clid ),
			"payerid"  => $payer,
			"payer"    => current_client( $payer ),
			"did"      => $data[ 'did' ],
			"deal"     => current_dogovor( $data[ 'did' ] ),
			"user"     => current_user( $data[ 'iduser' ] ),
			"change"   => $change,
			"rs"       => $rs,
			"mcid"     => $mcid,
			"view"     => $view,
			"month"    => (int)getMonth( $data[ 'invoice_date' ] )
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => (int)$page,
		"pageall" => (int)$count_pages,
		"valuta"  => $valuta
	];

	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr( $lists );

	exit();
}

/**
 * DEPRECATED
 */
if ( $tar == 'invoices2' ) {
	$page = $_GET[ 'page' ];
	$word = $_GET[ 'word' ];
	$word = str_replace( " ", "%", $word );
	$ord  = $_GET[ 'ord' ];
	if ( $ord == '' ) $ord = "datum_credit"; //параметр сортировки
	$tuda   = $_GET[ 'tuda' ]; //if($tuda=='') $tuda = ' DESC';
	$pay1   = $_GET[ 'pay1' ];
	$pay2   = $_GET[ 'pay2' ];
	$iduser = $_GET[ 'iduser' ];

	if ( $pay1 == 'yes' and $pay2 != 'yes' ) $sort1 = " and do = 'on'";
	elseif ( $pay1 != 'yes' and $pay2 == 'yes' ) $sort1 = " and do != 'on'";
	else $sort1 = '';

	if ( $iduser != '' ) $sort1 .= " and iduser= '".$iduser."'";
	else $sort1 .= get_people( $iduser1 );

	//восстановим поля формы
	$result = $db -> query( "select * from ".$sqlname."field where fld_tip='dogovor' and fld_on='yes' and identity = '$identity'" );
	while ( $data = $db -> fetch( $result ) ) {
		$fields[]                     = $data[ 'fld_name' ];
		$fName[ $data[ 'fld_name' ] ] = $data[ 'fld_title' ];
	}

	if ( $acs_credit != 'on' ) {
		print '<div class="bad" align="center"><br />Доступ запрещен.<br />Обратитесь к администратору.<br /><br /></div>';
		exit;
	}

	$lines_per_page = $num_client; //Стоимость записей на страницу

	if ( $word != "" ) $sort1 .= " and (invoice LIKE '%".$word."%' or invoice_chek LIKE '%".$word."%')";

	$query = "SELECT * FROM ".$sqlname."credit WHERE crid!='' ".$sort1." and identity = '$identity'";

	$result    = $db -> query( $query );
	$all_lines = $db -> affectedRows( $result );
	if ( !isset( $page ) or empty( $page ) or $page <= 0 ) $page = 1;
	else $page = (int)$page;
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$query = $query." ORDER BY ".$ord." ".$tuda." LIMIT $lpos,$lines_per_page";

	$result      = $db -> query( $query );
	$count_pages = ceil( $all_lines / $lines_per_page );

	if ( $tuda == 'desc' ) $ss = '<i class="icon icon-angle-up"></i>';
	else $ss = '<i class="icon icon-angle-down"></i>';
	?>
	<TABLE width="100%" cellpadding="5" cellspacing="0" border="0" class="ui-border2" height="35">
		<thead>
		<TR class="header_contaner">
			<TH width="85" align="left">
				<div class="ellipsis" id="x-datum_credit">
					<a href="javascript:void(0)" onclick="changesort('datum_credit')" title="Изменить порядок вывода"><?php if ( $ord == 'datum_credit' ) print $ss ?>
						<B>Дата план.</B></a></div>
			</TH>
			<TH width="85" align="left">
				<div class="ellipsis" id="x-datum_credit">
					<a href="javascript:void(0)" onclick="changesort('invoice_date')" title="Изменить порядок вывода"><?php if ( $ord == 'invoice_date' ) print $ss ?>
						<B>Дата факт.</B></a></div>
			</TH>
			<TH width="80" align="left">
				<div class="ellipsis" id="x-invoice">
					<a href="javascript:void(0)" onclick="changesort('invoice')" title="Изменить порядок вывода"><?php if ( $ord == 'invoice' ) print $ss ?>
						<b>№ счета</b></div>
				</a></TH>
			<TH width="120" align="left">
				<div class="ellipsis" id="x-invoice_chek">
					<a href="javascript:void(0)" onclick="changesort('invoice_chek')" title="Изменить порядок вывода"><?php if ( $ord == 'invoice_chek' ) print $ss ?>
						<B>№ договора</B></a></div>
			</TH>
			<TH width="120" align="left">
				<div class="ellipsis" id="x-summa_credit">
					<a href="javascript:void(0)" onclick="changesort('summa_credit')" title="Изменить порядок вывода"><?php if ( $ord == 'summa_credit' ) print $ss ?>
						<B>Сумма платежа</B></a></div>
			</TH>
			<TH width="60" align="left">
				<div class="ellipsis" id="x-do">
					<a href="javascript:void(0)" onclick="changesort('do')" title="Изменить порядок вывода"><?php if ( $ord == 'do' ) print $ss ?>
						<B>Оплата</B></div>
				</a></TH>
			<TH align="left"><STRONG class="ellipsis">Заказчик / Сделка</STRONG></TH>
		</TR>
		</thead>
		<tbody>
		<?php
		//print "<br>".$query."<br>";
		while ( $data_array = $db -> fetch( $result ) ) {
			if ( $data_array[ 'clid' ] > 0 ) {
				$roditel = '<SPAN title="'.current_client( $data_array[ 'clid' ] ).'" class="ellipsis"><A href="card.php?clid='.$data_array[ 'clid' ].'" target="_blank" title="Открыть в новом окне"><i class="icon-commerical-building broun"></i></A>&nbsp;<A href="javascript:void(0)" onClick="doLoad(\'card/card_view.php?clid='.$data_array[ 'clid' ].'\')" title="Заказчик">'.current_client( $data_array[ 'clid' ] ).'</A></SPAN>';
			}
			if ( $data_array[ 'pid' ] > 0 ) {
				$roditel = '<SPAN title="'.current_person( $data_array[ 'pid' ] ).'" class="ellipsis"><A href="card_person.php?pid='.$data_array[ 'pid' ].'" target="_blank" title="Открыть в новом окне"><i class="icon-commerical-building broun"></i></A>&nbsp;<A href="javascript:void(0)" onClick="doLoad(\'card/card_person_view.php?pid='.$data_array[ 'pid' ].'\')" title="Заказчик">'.current_person( $data_array[ 'pid' ] ).'</A></SPAN>';
			}

			$resultt = $db -> getRow( "SELECT payer, clid, pid FROM ".$sqlname."dogovor where did = '".$data_array[ 'did' ]."' and identity = '$identity'" );
			$payer   = $resultt[ "payer" ];
			if ( !isset( $clid ) ) $clid = $resultt[ "clid" ];
			if ( !isset( $pid ) ) $pid = $resultt[ "pid" ];

			if ( $payer > 0 and in_array( 'payer', $fields ) ) {
				$payerr = '<br><span class="ellipsis"><a href="card.php?clid='.$data_array[ 'payer' ].'" target="blank" title="Плательщик"><i class="icon-commerical-building blue"></i>&nbsp;'.current_client( $payer ).'</a></span>';
			}

			if ( $data_array[ 'clid' ] > 0 and $payer < 1 ) $payerr = '<br><span class="ellipsis"><a href="card.php?clid='.$data_array[ 'clid' ].'" target="blank" title="Плательщик"><i class="icon-commerical-building blue"></i>&nbsp;'.current_client( $data_array[ 'clid' ] ).'</a></span>';

			if ( $data_array[ 'do' ] == 'on' ) {
				$do    = '<i class="icon-ok blue"></i>';
				$color = '';
				$warn  = '';
				$dc    = '';
			}
			else {
				$do    = '<a href="javascript:void(0)" onClick="doLoad(\'dogs/dogs_change.php?crid='.$data_array[ 'crid' ].'&did='.$data_array[ 'did' ].'&action=doit&tt=list\');" title="Поставить отметку об оплате"><i class="icon-plus-circled red"></i></a>';
				$color = '#FFD7D7';

				//проверим - закрыта сделка или нет
				$doclose = $db -> getOne( "select close from ".$sqlname."dogovor WHERE did='".$data_array[ 'did' ]."' and identity = '$identity'" );

				if ( $doclose == 'yes' ) {
					$warn  = '<i class="icon-bitbucket red" title="Скорее всего счет не будет оплачен"></i>';
					$dc    = '<i class="icon-lock" title="Закрыта"></i>';
					$color = '#E0E0E0';
				}
				else {
					$warn = '';
					$dc   = '';
				}
			}
			?>
			<TR height="35" class="ha" bgcolor="<?= $color ?>">
				<TD width="85" align="center"><SPAN><?= format_date_rus( $data_array[ 'datum_credit' ] ) ?></SPAN></TD>
				<TD width="85" align="center"><SPAN><?= format_date_rus( $data_array[ 'invoice_date' ] ) ?></SPAN></TD>
				<TD width="80">
					<SPAN title="<?= $data_array[ 'invoice' ] ?>" class="ellipsis"><?php if ( $otherSettings['printInvoice'] ){ ?>
						<a href="javascript:void(0)" onClick="window.open('get_invoice.php?crid=<?= $data_array[ 'crid' ] ?>&view=yes',this.target,'width=850,height=500,'+'location=no,toolbar=no,menubar=yes,status=no,resizeable=yes,scrollbars=yes')" title="Просмотр"><i class="icon-eye broun"></i></a><B>&nbsp;<?php } ?>
							<B><?= $data_array[ 'invoice' ] ?></B></SPAN></TD>
				<TD width="120">
					<SPAN title="<?= $data_array[ 'invoice_chek' ] ?>" class="ellipsis"><B><?= $data_array[ 'invoice_chek' ] ?></B></SPAN>
				</TD>
				<TD width="120" align="right"><SPAN><?= num_format( $data_array[ 'summa_credit' ] ) ?></SPAN></TD>
				<TD width="60" align="center" nowrap><?= $do." ".$warn ?></TD>
				<TD>
					<?= $roditel ?><?= $payerr ?><?= $payer ?>
					<br>
					<span class="ellipsis"><?= $dc ?>
						<A href="card_dog.php?did=<?= $data_array[ 'did' ] ?>" target="_blank" title="Открыть в новом окне"><i class="icon-briefcase broun"></i></A> <span title="Быстрый просмотр: <?= current_dogovor( $data_array[ 'did' ] ) ?>" onClick="doLoad('dogs/dogs_view.php?did=<?= $data_array[ 'did' ] ?>')" class="list"><B><?= current_dogovor( $data_array[ 'did' ] ) ?></B></span></span>
				</TD>
			</TR>
			<?php
			$img = '';
		}
		?>
		</TBODY>
	</TABLE>
	<div style="height:65px;"></div>
<?php }