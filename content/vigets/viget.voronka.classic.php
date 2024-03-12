<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

set_time_limit(30);

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$point = $_REQUEST['point'];

$rez = $sum = [];

$m = ($_REQUEST['m'] == '') ? date('m') : $_REQUEST['m'];
$y = ($_REQUEST['y'] == '') ? date('y') : $_REQUEST['y'];

//для модуля сборщика лидов
$mdwset       = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");
$leadsettings = json_decode($mdwset['content'], true);
$coordinator  = $leadsettings["leadСoordinator"];

$sort1 = ($iduser1 == $coordinator) ? "" : "and (iduser = 0 OR iduser IN (".yimplode(",", get_people($iduser1, "yes", true))."))";

//кол-во документов с типом договор
$contracts = $db -> getOne("SELECT COUNT(id) FROM {$sqlname}contract_type WHERE type = 'get_dogovor' and identity = '$identity'");

$color = [
	'#aec7e8',
	'#ffbb78',
	'#98df8a',
	'#ff9896',
	'#c5b0d5',
	'#c49c94',
	'#f7b6d2',
	'#c7c7c7',
	'#dbdb8d'
];

$sort = " AND iduser IN (".yimplode(",", get_people($iduser1, "yes", true)).")";

if ($point) {

	switch ($point) {
		case 'LeadsIn':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$s = ($data['iduser'] == $coordinator) ? "" : "iduser IN (".yimplode(",", get_people($data['iduser'], "yes", true)).") AND ";

				//$rez[ $data['iduser'] ] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}leads WHERE DATE_FORMAT(datum, '%y-%m') = '$y-$m' $s and identity = '$identity'");
				$rez[ $data['iduser'] ] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}leads WHERE ( datum >= '$y-$m-01 00:00:00' AND datum <  ('$y-$m-01 23:59:59' + INTERVAL 1 MONTH) ) AND $s identity = '$identity'");

			}

		break;
		case 'LeadsDo':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$rez[ $data['iduser'] ] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}leads WHERE ( datum_do >= '$y-$m-01 00:00:00' AND datum_do <  ('$y-$m-01 23:59:59' + INTERVAL 1 MONTH) ) AND status = '2' and did > 0 and iduser = '".$data['iduser']."' and identity = '$identity'");

			}

		break;
		case 'ActivOut':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$rez[ $data['iduser'] ] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}history WHERE ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did < 1 and iduser = '".$data['iduser']."' and identity = '$identity'");

			}

		break;
		case 'MeetOut':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$rez[ $data['iduser'] ] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}history WHERE ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Задача','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did > 0 and iduser = '".$data['iduser']."' and identity = '$identity'");

			}

		break;
		case 'newDogs':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$res = $db -> getRow("SELECT COUNT(*) as count, SUM(kol) as kol FROM {$sqlname}dogovor WHERE did>0 and ( datum >= '$y-$m-01' AND datum < '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND iduser = '".$data['iduser']."' and identity = '$identity'");

				$rez[ $data['iduser'] ] = $res['count'];
				$sum[ $data['iduser'] ] = $res['kol'];

			}

		break;
		case 'newContract':

			$contracts = $db -> getOne("SELECT COUNT(id) FROM {$sqlname}contract_type WHERE type = 'get_dogovor' and identity = '$identity'");

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$rez[ $data['iduser'] ] = ($contracts > 0) ? $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}contract WHERE deid > 0 and did > 0 and ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND iduser = '".$data['iduser']."' and idtype IN (SELECT id FROM {$sqlname}contract_type where type = 'get_dogovor' and identity = '$identity') and identity = '$identity'") : 0;

				$sum[ $data['iduser'] ] = ($contracts > 0) ? $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}dogovor WHERE did IN (SELECT did FROM {$sqlname}contract WHERE deid > 0 and did > 0 and ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND iduser = '".$data['iduser']."' and idtype IN (SELECT id FROM {$sqlname}contract_type where type = 'get_dogovor' and identity = '$identity') and identity = '$identity') and identity = '$identity'") : 0;

			}

		break;
		case 'Invoice':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$res = $db -> getRow("SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE ( datum_credit >= '$y-$m-01 00:00:00' AND datum_credit <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND iduser = '".$data['iduser']."' and identity = '$identity'");

				$rez[ $data['iduser'] ] = $res['count'];
				$sum[ $data['iduser'] ] = $res['summa'];

			}

		break;
		case 'InvoiceDo':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$res = $db -> getRow("SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE do = 'on' AND ( invoice_date >= '$y-$m-01 00:00:00' AND invoice_date <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND iduser = '".$data['iduser']."' and identity = '$identity'");

				$rez[ $data['iduser'] ] = $res['count'];
				$sum[ $data['iduser'] ] = $res['summa'];

			}

		break;
		case 'closeDogs':

			$result = $db -> getAll("SELECT iduser FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$res = $db -> getRow("SELECT SUM(kol_fact) as kol, COUNT(*) as count FROM {$sqlname}dogovor WHERE COALESCE(close, 'no') = 'yes' and ( datum_close >= '$y-$m-01 00:00:00' AND datum_close <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND iduser = '".$data['iduser']."' and identity = '$identity'");

				$sum[ $data['iduser'] ] = $res['kol'];
				$rez[ $data['iduser'] ] = $res['count'];

			}

		break;
	}

	//print_r($rez);

	if(count($sum) == 1){

		$max   = max($sum);
		$param = max($sum) > 0 ? "summa" : "count";

	}
	else {

		$max   = !empty( $sum ) && max( $sum ) > 0 ? max( $sum ) : max( $rez );
		$param = !empty( $sum ) && max( $sum ) > 0 ? "summa" : "count";

	}

	if ($max == 0) $max = 1;

	$list = [];

	$color = [
		'#1f77b4',
		'#aec7e8',
		'#ff7f0e',
		'#ffbb78',
		'#2ca02c',
		'#98df8a',
		'#d62728',
		'#ff9896',
		'#9467bd',
		'#c5b0d5',
		'#8c564b',
		'#c49c94',
		'#e377c2',
		'#f7b6d2',
		'#7f7f7f',
		'#c7c7c7',
		'#bcbd22',
		'#dbdb8d',
		'#17becf',
		'#9edae5',
		'#393b79',
		'#5254a3',
		'#6b6ecf',
		'#9c9ede',
		'#637939',
		'#8ca252',
		'#b5cf6b',
		'#cedb9c',
		'#8c6d31',
		'#bd9e39',
		'#e7ba52',
		'#e7cb94',
		'#843c39',
		'#ad494a',
		'#d6616b',
		'#e7969c',
		'#7b4173',
		'#a55194',
		'#ce6dbd',
		'#de9ed6',
		'#FF0F00',
		'#000099',
		'#006600',
		'#CC6600',
		'#666699',
		'#990099',
		'#999900',
		'#0066CC',
		'#FF6600',
		'#996666',
		'#FF0033',
		'#0099FF',
		'#663300',
		'#666600',
		'#FF00CC',
		'#9900FF',
		'#FFCC00',
		'#003366',
		'#333333',
		'#99FF00'
	];

	$result = $db -> getAll("SELECT iduser, title FROM {$sqlname}user WHERE iduser > 0 and secrty = 'yes' $sort and identity = '$identity'");
	foreach ($result as $i => $data) {

		$bb = 'gray';

		$vall = ($sum[ $data['iduser'] ] > 0) ? ceil($sum[ $data['iduser'] ] * 100) / $max : ceil($rez[ $data['iduser'] ] * 100) / $max;

		if ($sum[ $data['iduser'] ] > 0 || $rez[ $data['iduser'] ] > 0) $bb = 'Bold';

		[$rc, $gc, $bc] = sscanf($color[ $i ], "#%02x%02x%02x");
		$colord = 'rgba('.$rc.','.$gc.','.$bc.',0.6)';

		$list[] = [
			"user"   => $data['title'],
			"iduser" => $data['iduser'],
			"count"  => $rez[ $data['iduser'] ],
			"summa"  => $sum[ $data['iduser'] ],
			"vall"   => $vall,
			"colord" => $colord,
			"color"  => $bb
		];

	}

	function cmp($a, $b) { return $b[ $GLOBALS['param'] ] > $a[ $GLOBALS['param'] ]; }

	usort($list, 'cmp');

	?>
	<div class="zagolovok">Детализация по сотрудникам</div>

	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table id="zebraTable" class="bborder bgwhite">
			<thead class="sticked--top">
			<TR class="th35">
				<th class="wp5 text-center"></th>
				<th class="wp40 text-center"><b>Сотрудник</b></th>
				<th class="wp10 text-center"><b>Кол-во</b></th>
				<?php
				if (!empty($sum)) {
					?>
					<th class="wp15 text-center"><b>Сумма, <?= $valuta ?></b></th>
				<?php } ?>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ($list as $k => $item) {
				?>
				<tr class="ha th35" data-count="<?= $item['count'] ?>">
					<td class="hidden-iphone"><?= $k + 1 ?>.</td>
					<td class="<?= $item['color'] ?>">
						<div class="ellipsis"><?= $item['user'] ?></div>
					</td>
					<td class="<?= $item['color'] ?> text-center"><?= $item['count'] ?></td>
					<?php
					if (!empty($sum)) {
						?>
						<td class="<?= $item['color'] ?> text-right"><?= num_format($item['summa']) ?>&nbsp;</td>
					<?php } ?>
					<td>
						<div style="width: <?= $item['vall'] ?>%; height: 10px; background:<?= $item['colord'] ?>;"></div>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
			<tfoot class="sticked--bottom">
			<TR class="bluebg-sub th35">
				<th></th>
				<th></th>
				<th class="text-center"><?= array_sum($rez) ?></th>
				<?php
				if (!empty($sum)) {
					?>
					<th class="text-right"><?= num_format(array_sum($sum)) ?>&nbsp;</th>
				<?php } ?>
				<th></th>
			</tr>
			</tfoot>
		</table>

	</div>
	<script>

		if (!isMobile) {

			var hh = $('#dialog_container').actual('height') * 0.85;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

			if ($(window).width() > 990) $('#dialog').css({'width': '900px'});
			else if ($(window).width() > 1200) $('#dialog').css({'width': '950px'});
			else $('#dialog').css('width', '90vw');

			$('#formtabs').css('max-height', hh2);

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - 30;
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		}

		$(function () {

			$('#zebraTable tr').each(function () {

				if (parseFloat($(this).data('count')) > 0) $('tr', this).addClass('gray');

			});

			/*if (!isMobile) $("#zebraTable").tableHeadFixer({
				'head': true,
				'foot': true,
				'z-index': 12000
			}).find('th').css('z-index', '100');*/
			if (isMobile) $('#dialog').find('table').rtResponsiveTables({id: 'table-<?=$point?>'});

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

$data['LeadsIn'] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}leads WHERE ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort1 and identity = '$identity'");

$data['LeadsDo'] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}leads WHERE ( datum_do >= '$y-$m-01 00:00:00' AND datum_do <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND did > 0 and status = '2' $sort and identity = '$identity'");

$data['ActivOut'] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}history WHERE ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did < 1 $sort and identity = '$identity'");

$data['MeetOut'] = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}history WHERE ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) AND tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Задача','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did > 0 ".$sort." and identity = '$identity'");

$res = $db -> getRow("SELECT COUNT(*) as count, SUM(kol) as kol FROM {$sqlname}dogovor WHERE did > 0 and ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'");

$data['newDogs'] = $res['count'];
$summaDogs       = $res['kol'];

$data['newContract'] = ($contracts > 0) ? $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}contract WHERE deid>0 and did > 0 and idtype IN (SELECT id FROM {$sqlname}contract_type where type = 'get_dogovor' and identity = '$identity') and ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'") : 0;

$summaContract = ($contracts > 0) ? $db -> getOne("SELECT SUM(kol) as kol FROM {$sqlname}dogovor WHERE did IN (SELECT did FROM {$sqlname}contract WHERE did > 0 and ( datum >= '$y-$m-01 00:00:00' AND datum <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity' and idtype IN (SELECT id FROM {$sqlname}contract_type where type = 'get_dogovor' and identity = '$identity')) and identity = '$identity'") : 0;

$data['Invoice'] = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE ( datum_credit >= '$y-$m-01 00:00:00' AND datum_credit <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'");

$summaInvoice = $db -> getOne("SELECT SUM(summa_credit) as summ FROM {$sqlname}credit WHERE ( datum_credit >= '$y-$m-01 00:00:00' AND datum_credit <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'");

$data['InvoiceDo'] = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE do = 'on' and ( invoice_date >= '$y-$m-01 00:00:00' AND invoice_date <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'");

$summaInvoiceDo = $db -> getOne("SELECT SUM(summa_credit) as summ FROM {$sqlname}credit WHERE do = 'on' and ( invoice_date >= '$y-$m-01 00:00:00' AND invoice_date <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'");

$res = $db -> getRow("SELECT SUM(kol_fact) as kol, COUNT(*) as count FROM {$sqlname}dogovor WHERE close = 'yes' and ( datum_close >= '$y-$m-01 00:00:00' AND datum_close <  '$y-$m-01 23:59:59' + INTERVAL 1 MONTH ) $sort and identity = '$identity'");

$summaDogClose     = $res['kol'];
$data['closeDogs'] = $res['count'];

$max = max($data);

if ($max == 0) $max = 1;

$vor['LeadsIn']     = ceil($data['LeadsIn'] * 100) / $max;
$vor['LeadsDo']     = ceil($data['LeadsDo'] * 100) / $max;
$vor['ActivOut']    = ceil($data['ActivOut'] * 100) / $max;
$vor['newDogs']     = ceil($data['newDogs'] * 100) / $max;
$vor['newContract'] = ceil($data['newContract'] * 100) / $max;
$vor['MeetOut']     = ceil($data['MeetOut'] * 100) / $max;
$vor['Invoice']     = ceil($data['Invoice'] * 100) / $max;
$vor['InvoiceDo']   = ceil($data['InvoiceDo'] * 100) / $max;
$vor['closeDogs']   = ceil($data['closeDogs'] * 100) / $max;

$i = 1;
?>
<table class="dataTable nobg nopad border-bottom1">
	<tbody>
	<?php if ($coordinator > 0) { ?>
		<tr class="th30 ha hand colorer" onclick="SelPoint('LeadsIn')" data-count="<?= $data['LeadsIn'] ?>">
			<td nowrap><span>Входящих интересов</span></td>
			<td class="wp10 text-center hidden-iphone"><span><?= $data['LeadsIn'] ?></span></td>
			<td class="wp40" nowrap>
				<DIV class="progressbar"><?= $data['LeadsIn'] ?>&nbsp;шт.
					<DIV id="test" class="progressbar-completed" style="width:<?= $vor['LeadsIn'] ?>%; background:<?= $color[0] ?>;">
						<DIV class="status"></DIV>
					</DIV>
				</DIV>
			</td>
		</tr>
		<tr class="th30 ha hand colorer" onclick="SelPoint('LeadsDo')" data-count="<?= $data['LeadsDo'] ?>">
			<td nowrap><span>Обработано интересов</span>
				<i class="icon-info-circled blue list" title="Обработанные интересы со сделками"></i></td>
			<td class="wp10 text-center hidden-iphone"><span><?= $data['LeadsDo'] ?></span></td>
			<td class="wp40" nowrap>
				<DIV class="progressbar"><?= $data['LeadsDo'] ?>&nbsp;шт.
					<DIV id="test" class="progressbar-completed" style="width:<?= $vor['LeadsDo'] ?>%; background:<?= $color[1] ?>;">
						<DIV class="status"></DIV>
					</DIV>
				</DIV>
			</td>
		</tr>
	<?php } ?>
	<tr class="th30 ha hand colorer" onclick="SelPoint('ActivOut')" data-count="<?= $data['ActivOut'] ?>">
		<td nowrap><span>Активности без сделок</span>
			<i class="icon-info-circled blue list" title="Вне сделок: исх.1.Звонок, исх.2.Звонок, вх.Звонок, вх.Почта, Встреча, Презентация, Предложение, Отправка КП"></i>
		</td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['ActivOut'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar"><?= $data['ActivOut'] ?>&nbsp;шт.
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['ActivOut'] ?>%; background:<?= $color[2] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	<tr class="th30 ha hand colorer" onclick="SelPoint('MeetOut')" data-count="<?= $data['MeetOut'] ?>">
		<td nowrap><span>Активности со сделками</span>
			<i class="icon-info-circled blue list" title="По сделкам: Задача, Встреча, Презентация, Предложение, Отправка КП"></i>
		</td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['MeetOut'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar"><?= $data['MeetOut'] ?>&nbsp;шт.
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['MeetOut'] ?>%; background:<?= $color[3] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	<tr class="th30 ha hand colorer" onclick="SelPoint('newDogs')" data-count="<?= $data['newDogs'] ?>">
		<td nowrap><span>Создано новых сделок</span></td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['newDogs'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar">&nbsp;<?= num_format($summaDogs) ?>&nbsp;<?= $valuta ?>
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['newDogs'] ?>%; background:<?= $color[4] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	<tr class="th30 ha hand colorer" onclick="SelPoint('newContract')" data-count="<?= $data['newContract'] ?>">
		<td nowrap><span>Создано новых договоров</span></td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['newContract'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar">&nbsp;<?= num_format($summaContract) ?>&nbsp;<?= $valuta ?>
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['newContract'] ?>%; background:<?= $color[5] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	<tr class="th30 ha hand colorer" onclick="SelPoint('Invoice')" data-count="<?= $data['Invoice'] ?>">
		<td nowrap><span>Выставлено счетов</span></td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['Invoice'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar">&nbsp;<?= num_format($summaInvoice) ?>&nbsp;<?= $valuta ?>
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['Invoice'] ?>%; background:<?= $color[6] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	<tr class="th30 ha hand" onclick="SelPoint('InvoiceDo')" data-count="<?= $data['InvoiceDo'] ?>">
		<td nowrap><span>Оплаченных счетов</span></td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['InvoiceDo'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar">&nbsp;<?= num_format($summaInvoiceDo) ?>&nbsp;<?= $valuta ?>
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['InvoiceDo'] ?>%; background:<?= $color[7] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	<tr class="th30 ha hand colorer" onclick="SelPoint('closeDogs')" data-count="<?= $data['closeDogs'] ?>">
		<td nowrap><span>Закрыто сделок</span></td>
		<td class="wp10 text-center hidden-iphone"><span><?= $data['closeDogs'] ?></span></td>
		<td class="wp40" nowrap>
			<DIV class="progressbar">&nbsp;<?= num_format($summaDogClose) ?>&nbsp;<?= $valuta ?>
				<DIV id="test" class="progressbar-completed" style="width:<?= $vor['closeDogs'] ?>%; background:<?= $color[8] ?>;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</td>
	</tr>
	</tbody>
</table>

<script>

	$('#dialog').css({'width': '800px'});

	$(function () {

		$('.dataTable tr').each(function (indx, element) {

			var count = parseFloat($(this).data('count'));

			if (count === 0) {

				$(this).find('span').addClass('gray2');

			}

		});

	});

	$('#dialog').center();

	function SelPoint(point) {

		doLoad('/content/vigets/viget.voronka.classic.php?point=' + point);

	}

</script>