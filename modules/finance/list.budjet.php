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
use Salesman\Budget;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

global $userRights, $userSettings;

$thisfile = basename(__FILE__);

$year = $_REQUEST['year'];
$mon2 = $_REQUEST['mon'];
$tar  = $_REQUEST['tar'];
$rs   = $_REQUEST['rs'];

$isMac = $detect -> is('Mac');

$isApple = $isPad || $isMac;

if ($year == '') {
	$year = date('Y');
}

if ($tar == 'budjet') {

	$data = Budget ::getBudjetStat([
		'year' => $year,
		'rs'   => $rs
	]);
	?>
	<style>
		.dimple-custom-axis-line {
			stroke: black !important;
			stroke-width: 1.1;
		}

		.dimple-custom-axis-label {
			font-family: Arial !important;
			font-size: 11px !important;
			font-weight: 500;
		}

		.dimple-custom-gridline {
			stroke-width: 1;
			stroke-dasharray: 5;
			fill: none;
			stroke: #CFD8DC !important;
		}

		.bjpop {
			position: fixed;
			top: 150px;
			right: 0;
			z-index: 100;
		}

		.bjicon {
			display: block;
			width: 40px;
			height: 40px;
			line-height: 38px;
			text-align: center;
			border-radius: 40px;
			color: var(--white);
			background: var(--gray-darkblue);
		}
	</style>

	<div class="bjpop pop nothide1 pull-right">

		<div class="bjicon gray2 mr20 fs-12 hand">
			<i class="icon-cog-1"></i>
		</div>
		<div class="popmenu-top cursor-default" style="right:15px; top: 40px; display: none;">

			<div class="popcontent w300 fs-09" style="right: 0;">

				<div class="graybg-black white p10 sticked--top">Выбор статьи</div>

				<div class="xcategory border-bottom ha hand" data-id="0">
					<div class="p5 pt7 pl10 blue">Сбросить</div>
				</div>

				<?php
				$categories = Budget ::getCategory();
				$sub        = [];
				foreach ($categories as $type => $category) {
					foreach ($category['main'] as $cat) {
						foreach ($cat as $item) {
							foreach ($item as $s) {
								$sub[] = $s;
							}
						}
					}
				}

				foreach ($sub as $row) {

					print '
					<div class="xcategory border-bottom ha hand" data-id="'.$row['id'].'">
						<div class="p5 pt7 pl10">'.$row['title'].'</div>
					</div>
					';

				}
				?>

			</div>

		</div>

	</div>

	<TABLE class="budjet">

		<thead class="sticked--top">
		<TR class="header_contaner th30">
			<?php
			for ($m = 1; $m <= 12; $m++) { ?>
				<TH class="text-left mounth">
					<DIV class="ellipsis"><b><?= ru_month($m) ?>.</b></DIV>
				</TH>
			<?php
			} ?>
			<TH class="text-left w100">
				<DIV class="ellipsis"><b>Итого, <?= $valuta ?></b></DIV>
			</TH>
			<TH class="w5"></TH>
		</TR>
		</thead>

		<tbody>
		<tr class="th40 toggler graybg-dark hand white <?php
		echo( !$isApple ? 'sticked--top1' : '' ) ?>" onclick="$('#warndiv').toggleClass('hidden'); drowChart();">
			<td colspan="14" class="text-center">
				<span class="Bold"><i class="icon-chart-line"></i>&nbsp;ГРАФИК</span>
			</td>
		</tr>
		<TR id="warndiv" class="hidden" data-step="8" data-intro="<h1>Финансовый график</h1>Отображает график расходов и доходов, а так же показывает Финансовый результат" data-position="top">
			<td colspan="14">
				<?php
				for ($m = 1; $m <= 12; $m++) {

					$graf[] = '{Тип:"Поступления","Месяц":"'.ru_month($m).'","Сумма, '.$valuta.'":"'.( $data['journal']['itog']['dohod'][$m]['on'] + 0 ).'"}';
					$graf[] = '{Тип:"Расходы","Месяц":"'.ru_month($m).'","Сумма, '.$valuta.'":"-'.( $data['journal']['itog']['rashod'][$m]['on'] + 0 ).'"}';

					$dataf[] = '{Тип:"Фин.результат","Месяц":"'.ru_month($m).'","Сумма, '.$valuta.'":"'.( $data['finResult'][$m] + 0 ).'"}';

					$order[] = '"'.ru_month($m).'"';

				}

				$datas = implode(",", $graf);
				$dataf = implode(",", $dataf);
				$order = implode(",", $order);
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

		<TR class="th40">
			<TD colspan="14" class="Bold bluebg toggler <?php
			echo( !$isApple ? 'sticked--top--second' : '' ) ?>">
				<div class="text-center">ДОХОДЫ</div>
			</TD>
		</TR>
		<!--/Оплаты счетов/-->
		<tr class="bluebg-sublite th35">
			<td colspan="14">
				<div class="fs-11 gray2">Раздел: <b class="black">Поступления от продаж</b></div>
			</td>
		</tr>
		<tr class="graybg-sub th35">
			<td colspan="14" class="p3">
				<div class="fs-10 Bold">Статья <span class="gray-dark">Оплата счетов</span></div>
			</td>
		</tr>
		<TR class="th35 ha">
			<?php
			for ($m = 1; $m <= 12; $m++) {
				?>
				<TD class="mounth">
					<DIV class="ellipsis" title="Проведено: <?= num_format($data['payments']['data'][$m]['on']) ?>">
						<?php
						if ($data['payments']['data'][$m]['on'] > 0) {
							print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewpayment\',\''.$m.'\',\''.$year.'\',\'on\')" class="white blue1 ptb5 Bold">'.num_format($data['payments']['data'][$m]['on']).'&nbsp;</a>';
						}
						else {
							print '<span class="gray ptb5">'.num_format($data['payments']['data'][$m]['on']).'</span>&nbsp;';
						}
						?>
					</DIV>
					<br>
					<DIV class="ellipsis mt5" title="Не проведено: <?= num_format($data['payments']['data'][$m]['off']) ?>">
						<?php
						if ($data['payments']['data'][$m]['off'] > 0) {
							print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewpayment\',\''.$m.'\',\''.$year.'\',\'\')" class="white gray ptb5 Bold">'.num_format($data['payments']['data'][$m]['off']).'&nbsp;</a>';
						}
						else {
							print '<span class="gray ptb5">'.num_format($data['payments']['data'][$m]['off']).'</span>&nbsp;';
						}
						?>
					</DIV>
				</TD>
				<?php
			}
			?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="<?= num_format($data['payments']['summa']['on']) ?>"><?= num_format($data['payments']['summa']['on']) ?></DIV>
				<br>
				<DIV class="ellipsis Bold gray" title="<?= num_format($data['payments']['summa']['off']) ?>"><?= num_format($data['payments']['summa']['off']) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Оплаты счетов/-->
		<!--/Доходы/-->
		<?php
		foreach ($data['journal']['dohod'] as $k => $da) {
			?>
			<tr class="bluebg-sublite th35">
				<td colspan="14">
					<div>
						<span class="gray2">Раздел: <b class="black"><?= $da['title'] ?></b></span>
						<?php
						if($userSettings['dostup']['budjet']['action'] == 'yes'){
						?>
						<div class="pull-aright mr10 fs-09 hidden-iphone">
							<a href="javascript:void(0)" onclick="editBudjet('<?= $da['id'] ?>','cat.edit')" title="Изменить раздел" class="button bluebtn dotted small"><i class="icon-pencil"></i> Изменить</a>
						</div>
						<?php } ?>
					</div>
				</td>
			</tr>
			<?php
			foreach ($da['sub'] as $subid => $xdata) {
				?>
				<TR class="ha graybg-lite subcat" data-block="<?= $xdata['id'] ?>">
					<TD colspan="14" class="w100 text-left p3">
						<DIV title="<?= $xdata['title'] ?>" class="ellipsis Bold">
							Статья <span class="gray-dark"><?= $xdata['title'] ?></span>
						</DIV>
					</TD>
				</TR>
				<TR class="th55 ha subcat" data-block="<?= $xdata['id'] ?>">
					<?php
					for ($m = 1; $m <= 12; $m++) {
						?>
						<TD class="mounth relativ">
							<DIV class="ellipsis" title="Проведено: <?= num_format($xdata['journal'][$m]['on']) ?>">
								<?php
								if ($xdata['journal'][$m]['on'] > 0) {
									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'on\',\''.$xdata['id'].'\')" class="blue1 ptb5 pl5 Bold">'.num_format($xdata['journal'][$m]['on']).'&nbsp;</a>';
								}
								else {
									print '<span class="gray ptb5">'.num_format($xdata['journal'][$m]['on']).'</span>&nbsp;';
								}
								?>
							</DIV>
							<br>
							<DIV class="ellipsis" title="Не проведено: <?= num_format($xdata['journal'][$m]['off']) ?>">
								<?php
								if ($xdata['journal'][$m]['off'] > 0) {
									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'\',\''.$xdata['id'].'\')" class="gray ptb5 pl5 Bold">'.num_format($xdata['journal'][$m]['off']).'&nbsp;</a>';
								}
								else {
									print '<span class="gray ptb5">'.num_format($xdata['journal'][$m]['off']).'</span>&nbsp;';
								}
								?>
							</DIV>
							<?php
							if($userSettings['dostup']['budjet']['action'] == 'yes'){
							?>
							<div class="saction" title="Добавить расход" onclick="viewBudjet('edit','<?= $m ?>','<?= $year ?>','on','<?= $xdata['id'] ?>')">
								<i class="icon-plus-circled"></i>
							</div>
							<?php } ?>
						</TD>
					<?php
					} ?>
					<TD>
						<DIV class="ellipsis" title="Проведено: <?= num_format($xdata['catitog']['on']) ?>">
							<b><?= num_format($xdata['catitog']['on']) ?></b>
						</DIV>
						<br>
						<DIV class="ellipsis gray" title="Не проведено: <?= num_format($xdata['catitog']['off']) ?>">
							<b><?= num_format($xdata['catitog']['off']) ?></b>
						</DIV>
					</TD>
					<TD class="hidden-iphone">
						<div class="action--container">
							<div class="action--block">
							<?php
							if($userSettings['dostup']['budjet']['action'] == 'yes'){
							?>
								<a href="javascript:void(0)" onclick="editBudjet('<?= $xdata['id'] ?>','cat.edit')" title="Изменить статью" class="gray brounbg2"><i class="icon-pencil broun"></i></a>
							<?php } ?>
							</div>
						</div>
					</TD>
				</TR>
				<?php
			}
			?>
			<TR class="graybg-sub hidden">
				<TD colspan="14">
					<span class="Bold text-left">Итого: <?= $da['title'] ?></span>
				</TD>
			</TR>
			<TR class="th40 bluebg-sublite">
				<?php
				for ($m = 1; $m <= 12; $m++) {
					?>
					<TD class="mounth">
						<DIV class="ellipsis" title="Проведено: <?= num_format($da['itog'][$m]['on']) ?>">
							<b><?= num_format($da['itog'][$m]['on']) ?></b>
						</DIV>
					</TD>
				<?php
				} ?>
				<TD class="yw1001">
					<DIV class="ellipsis" title="Проведено: <?= num_format($da['total']['on']) ?>">
						<b><?= num_format($da['total']['on']) ?></b>
					</DIV>
				</TD>
				<TD class="hidden-iphone"></TD>
			</TR>
			<?php
		}
		?>
		<tr class="bluebg-sub hidden" style="border-top:1px dotted #000">
			<td colspan="14">
				<div class="fs-11"><b class="black">Доходы, ИТОГО</b></div>
			</td>
		</tr>
		<TR class="th30 bluebg border-bottom">
			<?php
			$plus_all = $pluso_all = 0;
			for ($m = 1; $m <= 12; $m++) {

				$plus_all  += $data['journal']['itog']['dohod'][$m]['on'];
				$pluso_all += $data['journal']['itog']['dohod'][$m]['off'];
				?>
				<TD class="mounth">
					<DIV class="ellipsis Bold" title="Проведено: <?= num_format($data['journal']['itog']['dohod'][$m]['on']) ?>"><?= num_format($data['journal']['itog']['dohod'][$m]['on']) ?></DIV>
					<br>
					<DIV class="ellipsis Bold mt5 blue" title="Не проведено: <?= num_format($data['journal']['itog']['dohod'][$m]['off']) ?>"><?= num_format($data['journal']['itog']['dohod'][$m]['off']) ?></DIV>
				</TD>
			<?php
			} ?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="Проведено: <?= num_format($plus_all) ?>"><?= num_format($plus_all) ?></DIV>
				<br>
				<DIV class="ellipsis Bold mt5 gray-dark" title="Не проведено: <?= num_format($pluso_all) ?>"><?= num_format($pluso_all) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Доходы/-->
		<!--/Расходы/-->
		<TR class="th40" style="border-top:2px solid #000 !important;">
			<TD colspan="14" class="redbg toggler <?php
			echo( !$isApple ? 'sticked--top--second' : '' ) ?>">
				<div class="Bold text-center">РАСХОДЫ</div>
			</TD>
		</TR>
		<?php
		foreach ($data['journal']['rashod'] as $k => $da) {

			$sumRashod = [];
			?>
			<tr class="redbg-sub th35">
				<td colspan="14">
					<div>
						<span class="gray2">Раздел: <b class="black"><?= $da['title'] ?></b></span>
						<?php
						if($userSettings['dostup']['budjet']['action'] == 'yes'){
						?>
						<div class="pull-aright mr10 fs-09 hidden-iphone">
							<a href="javascript:void(0)" onclick="editBudjet('<?= $da['id'] ?>','cat.edit')" title="Изменить раздел" class="button redbtn dotted small"><i class="icon-pencil"></i> Изменить</a>
						</div>
						<?php } ?>
					</div>
				</td>
			</tr>
			<?php
			foreach ($da['sub'] as $subid => $xdata) {
				?>
				<tr class="redbg-sublite th35 subcat" data-block="<?= $xdata['id'] ?>">
					<td colspan="14" class="p3">
						<DIV title="<?= $xdata['title'] ?>" class="ellipsis Bold">
							Статья <span class="gray-dark"><?= $xdata['title'] ?></span>
						</DIV>
						<?= $clientpath ?>
					</td>
				</tr>
				<TR class="th55 ha subcat" data-block="<?= $xdata['id'] ?>">
					<?php
					for ($m = 1; $m <= 12; $m++) {
						?>
						<TD class="mounth relativ" height="35">
							<DIV class="ellipsis" title="Проведено: <?= num_format($xdata['journal'][$m]['on']) ?>">
								<?php
								if ($xdata['journal'][$m]['on'] > 0) {
									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'on\',\''.$xdata['id'].'\')" class="white blue1 ptb5 Bold">'.num_format($xdata['journal'][$m]['on']).'&nbsp;</a>';
								}
								else {
									print '<span class="gray ptb5">'.num_format($xdata['journal'][$m]['on']).'</span>&nbsp;';
								}
								?>
							</DIV>
							<br>
							<DIV class="ellipsis mt5" title="Не проведено: <?= num_format($xdata['journal'][$m]['off']) ?>">
								<?php
								if ($xdata['journal'][$m]['off'] > 0) {
									print '<a href="javascript:void(0)" onclick="viewBudjet(\'viewlist\',\''.$m.'\',\''.$year.'\',\'\',\''.$xdata['id'].'\')" class="white gray ptb5 Bold">'.num_format($xdata['journal'][$m]['off']).'</a>';
								}
								else {
									print '<span class="gray">'.num_format($xdata['journal'][$m]['off']).'</span>';
								}
								?>
							</DIV>
							<?php
							if($userSettings['dostup']['budjet']['action'] == 'yes'){
							?>
							<div class="saction" title="Добавить расход" onclick="viewBudjet('edit','<?= $m ?>','<?= $year ?>','on','<?= $xdata['id'] ?>')">
								<i class="icon-plus-circled"></i>
							</div>
							<?php } ?>
						</TD>
					<?php
					} ?>
					<TD class="yw1001">
						<DIV class="ellipsis Bold" title="Проведено: <?= num_format($xdata['catitog']['on']) ?>">
							<?= num_format($xdata['catitog']['on']) ?>
						</DIV>
						<br>
						<DIV class="ellipsis gray" title="Не проведено: <?= num_format($xdata['catitog']['off']) ?>">
							<b><?= num_format($xdata['catitog']['off']) ?></b>
						</DIV>
					</TD>
					<TD class="hidden-iphone">
						<div class="action--container">
							<?php
							if($userSettings['dostup']['budjet']['action'] == 'yes'){
							?>
							<div class="action--block">
								<a href="javascript:void(0)" onclick="editBudjet('<?= $xdata['id'] ?>','cat.edit')" title="Изменить статью" class="gray brounbg2"><i class="icon-pencil broun"></i></a>
							</div>
							<?php } ?>
						</div>
					</TD>
				</TR>
				<?php
			}
			?>
			<TR class="redbg hidden" data-block="<?= $da['sub'] ?>">
				<TD colspan="14">
					<span class="Bold text-left">Итого: <?= $da['title'] ?></span>
				</TD>
			</TR>
			<TR class="th40 cherta redbg-sub tooltips" tooltip-type="success" tooltip="Итого: <?= $da['title'] ?>" tooltip-position="top">
				<?php
				for ($m = 1; $m <= 12; $m++) {
					?>
					<TD class="mounth">
						<DIV class="ellipsis Bold" title="Проведено: <?= num_format($da['itog'][$m]['on']) ?>"><?= num_format($da['itog'][$m]['on']) ?></DIV>
					</TD>
				<?php
				} ?>
				<TD class="yw1001">
					<DIV class="ellipsis" title="Проведено: <?= num_format($da['total']['on']) ?>">
						<b><?= num_format($da['total']['on']) ?></b>
					</DIV>
				</TD>
				<TD class="hidden-iphone"></TD>
			</TR>
			<?php
		}
		?>
		<tr class="graybg th35 hidden">
			<td colspan="14">
				<div class="fs-11 gray-dark"><b class="black">Расходы, ИТОГО</b></div>
			</td>
		</tr>
		<TR class="th35 redbg">
			<?php
			$plus_all = $pluso_all = 0;
			for ($m = 1; $m <= 12; $m++) {

				$plus_all  += $data['journal']['total']['rashod'][$m]['on'];
				$pluso_all += $data['journal']['total']['rashod'][$m]['off'];
				?>
				<TD class="mounth">
					<DIV class="ellipsis Bold" title="Проведено: <?= num_format($data['journal']['total']['rashod'][$m]['on']) ?>"><?= num_format($data['journal']['total']['rashod'][$m]['on']) ?></DIV>
					<br>
					<DIV class="ellipsis Bold mt5 gray3" title="Не проведено: <?= num_format($data['journal']['total']['rashod'][$m]['off']) ?>"><?= num_format($data['journal']['total']['rashod'][$m]['off']) ?></DIV>
				</TD>
			<?php
			} ?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="Проведено: <?= num_format($plus_all) ?>"><?= num_format($plus_all) ?></DIV>
				<br>
				<DIV class="ellipsis Bold mt5 gray3" title="Не проведено: <?= num_format($pluso_all) ?>"><?= num_format($pluso_all) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Расходы/-->
		</tbody>
		<tfoot class="sticked--bottom">
		<!--/Фин.результат/-->
		<TR class="th40 greenbg-sub hidden" style="border-top:2px solid #000">
			<TD colspan="14" class="text-left" style="border-top:2px solid #000">
				<span class="Bold">Фин. результат:</span>
			</TD>
		</TR>
		<TR class="th40 greenbg-dark">
			<?php
			for ($m = 1; $m <= 12; $m++) {
				?>
				<TD class="">
					<DIV class="ellipsis Bold" title="<?= num_format($data['finResult'][$m]) ?>"><?= num_format($data['finResult'][$m]) ?></DIV>
				</TD>
			<?php
			} ?>
			<TD class="yw1001">
				<DIV class="ellipsis Bold" title="<?= num_format(array_sum($data['finResult'])) ?>"><?= num_format(array_sum($data['finResult'])) ?></DIV>
			</TD>
			<TD class="hidden-iphone"></TD>
		</TR>
		<!--/Фин.результат/-->
		</tfoot>
	</TABLE>

	<div class="space-40"></div>
	<?php

	exit();

}

if ($tar == 'journal') {

	$xlist = Budget ::getJournal([
		'do'       => $_REQUEST['doo'],
		'category' => $_REQUEST['category'],
		'word'     => $_REQUEST['word'],
		'month'    => (int)$_REQUEST['mon'],
		'year'     => $year,
		'rs'       => $rs
	]);

	$list = [];
	foreach ($xlist as $x) {
		$list[] = $x;
	}

	print json_encode_cyr(['list' => $list]);

	exit();

}

if ($tar == 'statement') {

	$lists = BankStatement ::getStatement([
		"do"       => $_REQUEST['doo'],
		"category" => $_REQUEST['category'],
		"year"     => $_REQUEST['year'],
		"mon"      => $_REQUEST['mon'],
		"page"     => $_REQUEST['page']
	]);

	print json_encode_cyr($lists);

	exit();

}

//новое представление
if ($tar == 'agents') {

	$params['do']      = (array)$_REQUEST['pdoo'];
	$params['partid'] = $_REQUEST['partid'];
	$params['conid']  = $_REQUEST['conid'];
	$params['word']   = $_REQUEST['aword'];
	$params['year']   = $year;
	$params['user']   = (int)$_REQUEST['xuser'];

	$list = Budget::getAgentsJournal($params);

	print json_encode_cyr($list);

	exit();

}

if ($tar == 'invoices') {

	$page = $_REQUEST['page'];
	$word = $_REQUEST['iword'];
	$word = str_replace(" ", "%", $word);

	if ($word == 'undefined') {
		$word = '';
	}

	//параметр сортировки
	$ord = $_REQUEST['ord'];
	if ($ord == '') {
		$ord = "datum_credit";
	}

	$tuda   = $_REQUEST['tuda'];
	$pay1   = $_REQUEST['pay1'];
	$pay2   = $_REQUEST['pay2'];
	$iduser = $_REQUEST['iduser'];

	$sort = ( $mon2 != '' ) ? " and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$mon2'" : " and DATE_FORMAT(invoice_date, '%Y') = '$year'";
	$sort .= ( $iduser != '' ) ? " and iduser = '$iduser'" : get_people($iduser1);

	if (!empty($rs)) {
		$sort .= " and rs IN (".implode(",", $rs).")";
	}

	$lines_per_page = $num_client; //Стоимость записей на страницу

	if ($word != "") {
		$sort .= " and (invoice LIKE '%$word%' OR invoice_chek LIKE '%".$word."%' OR did IN (SELECT did FROM {$sqlname}dogovor WHERE title LIKE '%$word%') OR clid IN (SELECT clid FROM {$sqlname}clientcat WHERE title LIKE '%$word%'))";
	}

	$query     = "SELECT * FROM {$sqlname}credit WHERE crid != '' $sort and do = 'on' and identity = '$identity' ORDER BY invoice_date DESC";
	$result    = $db -> getAll($query);
	$all_lines = count($result);

	if (!isset($page) or empty($page) or $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$query .= " LIMIT $lpos,$lines_per_page";

	$res         = $db -> getAll($query);
	$count_pages = ceil($all_lines / $lines_per_page);
	if ($count_pages == 0) {
		$count_pages = 1;
	}

	foreach ($res as $data) {

		$re    = $db -> getRow("SELECT * FROM {$sqlname}dogovor where did = '".$data['did']."' and identity = '$identity'");
		$payer = $re["payer"];
		$clid  = $re["clid"];
		$pid   = $re["pid"];

		$re  = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE id = '".$data['rs']."' and identity = '$identity' ORDER by id");
		$rs  = $re["title"];
		$cid = $re["cid"];

		$mcid = $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps WHERE id = '$cid' and identity = '$identity' ORDER by id");

		$view = ( $otherSettings['printInvoice'] ) ? 'yes' : '';

		if ($data['invoice_chek'] == '') {
			$data['invoice_chek'] = 'Без договора';
		}

		$list[] = [
			"id"       => $data['crid'],
			"datum"    => format_date_rus($data['invoice_date']),
			"contract" => $data['invoice_chek'],
			"invoice"  => $data['invoice'],
			"summa"    => num_format($data['summa_credit']),
			"clid"     => $clid,
			"client"   => current_client($clid),
			"payerid"  => $payer,
			"payer"    => current_client($payer),
			"did"      => $data['did'],
			"deal"     => current_dogovor($data['did']),
			"user"     => current_user($data['iduser']),
			"change"   => $change,
			"rs"       => $rs,
			"mcid"     => $mcid,
			"view"     => $view,
			"month"    => (int)getMonth($data['invoice_date'])
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => (int)$page,
		"pageall" => (int)$count_pages,
		"valuta"  => $valuta
	];

	print json_encode_cyr($lists);

	exit();
}