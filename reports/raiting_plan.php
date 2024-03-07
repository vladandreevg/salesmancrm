<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";

$action = $_REQUEST['action'];
$roles  = (array)$_REQUEST['roles'];
$year   = (int)$_REQUEST['year'];
$mon    = (int)$_REQUEST['mon'];

$sd = ($roles == 'yes') ? " and tip = 'Менеджер продаж'" : '';

$color = [
	'#d62728',
	'#1f77b4',
	'#aec7e8',
	'#ff7f0e',
	'#ffbb78',
	'#2ca02c',
	'#98df8a',
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

//$color = array('#1ABC9C','#16A085','#2ECC71','#27AE60'.'#3498DB','#2980B9','#9B59B6','#8E44AD','#34495E','#2C3E50','#F1C40F','#F39C12','#E67E22','#D35400','#E74C3C','#C0392B','#ECF0F1','#BDC3C7','#95A5A6','#7F8C8D');

$dsort = '';
if ( $otherSettings['planByClosed'] ) {
	$dsort = " and close = 'yes'";
}

if ( $year == 0 ) {
	$year = date( 'Y' );
}
if ( $mon == 0 ) {
	$mon = date( 'm' );
}

//$year = "2016";
//$mon  = "03";

$mon = ($mon < 10) ? "0".$mon : $mon;

if ( $action == 'paymentView' ) {

	$year = (int)['year'];
	$mon  = (int)$_REQUEST['mon'];
	$user = (int)$_REQUEST['iduser'];

	if ( $user == 0 ) {
		$user = $iduser1;
	}

	$sort = $ac_import[19] == 'on' ? " and {$sqlname}dogovor.iduser = '".$user."'" : get_people( $user );

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;

	//если выполнение считаем только по закрытым сделкам
	if ( !$otherSettings['credit'] ) {
		?>
		<div id="formtabse" style="overflow: auto;">
			<table id="zebra">
				<thead>
				<tr class="header_contaner">
					<TH width="20" align="center"></TH>
					<TH width="80" align="center">Дата факт.</TH>
					<TH align="center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH width="130" align="center">Сумма, <?= $valuta ?></TH>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<TH width="130" align="center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH width="80" align="center">Ответств.</TH>
				</tr>
				</thead>
				<?php
				$q = "
					SELECT
						*
					FROM
						{$sqlname}dogovor
					WHERE
						{$sqlname}dogovor.did > 0 and
						DATE_FORMAT({$sqlname}dogovor.datum_close, '%y-%m') = '".$year."-".$mon."' and
						{$sqlname}dogovor.kol_fact > 0 and
						{$sqlname}dogovor.close = 'yes' 
						".$sort." and
						identity = '$identity'
					";

				$result = $db -> getAll( $q );
				foreach ( $result as $data ) {

					$i++;

					if ( (int)$data['clid'] > 0 ) {
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client( $data['clid'] ).'">'.current_client( (int)$data['clid'] ).'</b></a>';
					}
					elseif ( (int)$data['pid'] > 0 ) {
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person( $data['pid'] ).'">'.current_person( (int)$data['pid'] ).'</b></a>';
					}

					?>
					<tr class="ha" height="35">
						<td align="center"><?= $i ?></td>
						<td align="center" class="smalltxt"><?= format_date_rus( $data['datum_close'] ) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor( $data['did'] ) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor( $data['did'] ) ?></a></span>
							<br>
							<div class="ellipsis paddtop5"><?= $client ?></div>
						</td>
						<td align="right">
							<span title="<?= num_format( $data['kol'] ) ?>"><?= num_format( $data['kol'] ) ?> <?= $valuta ?></span>
						</td>
						<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
							<td align="right">
								<span title="<?= num_format( $data['marg'] ) ?>"><?= num_format( $data['marg'] ) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td><span class="ellipsis"><?= current_user( $data['iduser'] ) ?></span></td>
					</tr>
					<?php
					$summa      += $data['kol'];
					$summa_marg += $data['marg'];
				}
				?>
				<tr class="itog" height="35">
					<td></td>
					<td align="right"><strong>Итого:</strong></td>
					<td align="right"><strong><?= $i ?></strong></td>
					<td align="right">
						<span title="<?= num_format( $summa ) ?>"><strong><?= num_format( $summa ) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<td align="right" class="nowrap">
							<span title="<?= num_format( $summa_marg ) ?>"><strong><?= num_format( $summa_marg ) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>
		</div>
		<?php
	}

	//если включена рассрочка, то считаем по оплатам
	if ( $otherSettings['credit'] ) {
		?>
		<div id="formtabse" style="overflow: auto;">
			<table id="zebra">
				<thead>
				<tr>
					<TH width="10" align="center"></TH>
					<TH width="60" align="center">Дата оплаты.</TH>
					<TH width="60" align="center">№ счета</TH>
					<TH width="150" align="center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH width="120" align="center">Оплачено, <?= $valuta ?></TH>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<TH width="120" align="center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH width="80" align="center">Ответств.</TH>
				</tr>
				</thead>
				<?php
				if ( !$otherSettings['planByClosed'] ) {
					$result = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do='on' and DATE_FORMAT(invoice_date, '%Y-%m') = '".$year."-".$mon."' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 ".$sort.") and identity = '$identity' ORDER by invoice_date DESC" );
				}
				else {
					$result = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 ".$dsort." and DATE_FORMAT(datum_close, '%Y-%m') = '".$year."-".$mon."' and identity = '$identity') and identity = '$identity' ORDER by invoice_date" );
				}

				foreach ( $result as $data ) {

					$i++;

					if ( (int)$data['clid'] > 0 ) {
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client( $data['clid'] ).'">'.current_client( $data['clid'] ).'</b></a>';
					}
					elseif ( (int)$data['pid'] > 0 ) {
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person( $data['pid'] ).'">'.current_person( $data['pid'] ).'</b></a>';
					}

					//найдем долю оплаченного счета
					$res    = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'" );
					$kol    = (float)$res["kol"];
					$marga  = (float)$res["marga"];
					$iduser = (int)$res["iduser"];
					$close  = $res["close"];

					$dolya = ($kol > 0) ? $data['summa_credit'] / $kol : 0;

					$marga_i = $marga * $dolya;

					$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
					?>
					<tr class="ha" height="35">
						<td align="center"><?= $i ?></td>
						<td align="center" class="smalltxt"><?= format_date_rus( $data['invoice_date'] ) ?></td>
						<td align="center"><?= $data['invoice'] ?></td>
						<td>
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor( $data['did'] ) ?>"><?= $ic ?><?= current_dogovor( $data['did'] ) ?></a>
							</div>
							<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
						</td>
						<td align="right">
							<span title="<?= num_format( $data['summa_credit'] ) ?>"><?= num_format( $data['summa_credit'] ) ?> <?= $valuta ?></span>
						</td>
						<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
							<td align="right">
								<span title="<?= num_format( $marga_i ) ?>"><?= num_format( $marga_i ) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td>
							<span class="ellipsis"><a href="javascript:void(0)" onClick="viewUser('<?= $data['iduser'] ?>');"><?= current_user( $iduser ) ?></a></span>
						</td>
					</tr>
					<?php
					$summa      += (float)$data['summa_credit'];
					$summa_marg += $marga_i;
				}
				?>
				<tr class="itog" height="35">
					<td align="center"></td>
					<td align="right"><strong>Итого:</strong></td>
					<td></td>
					<td align="right"><strong><?= $i ?></strong></td>
					<td align="right">
						<span title="<?= num_format( $summa ) ?>"><strong><?= num_format( $summa ) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<td align="right">
							<span title="<?= num_format( $summa_marg ) ?>"><strong><?= num_format( $summa_marg ) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>
		</div>
		<?php
		$marga_i = 0;
	}

	?>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

		if ($(window).width() > 990) {
			$('#dialog').css({'width': '800px'});
		}
		if ($(window).width() > 1200) {
			$('#dialog').css({'width': '950px'});
		}
		else {
			$('#dialog').css('width', '90vw');
		}

		$('#formtabse').css('max-height', hh2);

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}
if ( $action == 'planView' ) {

	$year = (int)$_REQUEST['year'];
	$mon  = (int)$_REQUEST['mon'];
	$user = (int)$_REQUEST['iduser'];

	if ( $user = 0 ) {
		$user = $iduser1;
	}

	$sort = $ac_import[19] == 'on' ? " and {$sqlname}dogovor.iduser = '".$user."'" : get_people( $user );

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;
	//если выполнение считаем только по закрытым сделкам
	if ( !$otherSettings['credit'] ) {
		?>
		<div id="formtabse" style="overflow: auto;">
			<table id="zebra">
				<thead>
				<tr class="header_contaner">
					<TH width="20" align="center"></TH>
					<TH width="80" align="center">Дата факт.</TH>
					<TH align="center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH width="130" align="center">Сумма, <?= $valuta ?></TH>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<TH width="130" align="center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH width="80" align="center">Ответств.</TH>
				</tr>
				</thead>
				<?php
				$q = "
				SELECT
					*
				FROM
					{$sqlname}dogovor
				WHERE
					{$sqlname}dogovor.did > 0 and
					DATE_FORMAT({$sqlname}dogovor.datum_close, '%y-%m') = '".$year."-".$mon."' and
					{$sqlname}dogovor.kol_fact > 0 and
					{$sqlname}dogovor.close = 'yes' ".$sort." and
					{$sqlname}dogovor.identity = '$identity'
				";

				$result = $db -> getAll( $q );
				foreach ( $result as $data ) {

					$i++;

					if ( (int)$data['clid'] > 0 ) {
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client( $data['clid'] ).'">'.current_client( $data['clid'] ).'</b></a>';
					}
					elseif ( (int)$data['pid'] > 0 ) {
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person( $data['pid'] ).'">'.current_person( $data['pid'] ).'</b></a>';
					}

					?>
					<tr class="ha" height="35">
						<td align="center"><?= $i ?></td>
						<td align="center" class="smalltxt"><?= format_date_rus( $data['datum_close'] ) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor( $data['did'] ) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor( $data['did'] ) ?></a></span>
							<br>
							<div class="ellipsis paddtop5"><?= $client ?></div>
						</td>
						<td align="right">
							<span title="<?= num_format( $data['kol'] ) ?>"><?= num_format( $data['kol'] ) ?> <?= $valuta ?></span>
						</td>
						<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
							<td align="right">
								<span title="<?= num_format( $data['marg'] ) ?>"><?= num_format( $data['marg'] ) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td><span class="ellipsis"><?= current_user( $data['iduser'] ) ?></span></td>
					</tr>
					<?php
					$summa      += (float)$data['kol'];
					$summa_marg += (float)$data['marg'];
				}
				?>
				<tr class="itog" height="35">
					<td></td>
					<td align="right"><strong>Итого:</strong></td>
					<td align="right"><strong><?= $i ?></strong></td>
					<td align="right">
						<span title="<?= num_format( $summa ) ?>"><strong><?= num_format( $summa ) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<td align="right" class="nowrap">
							<span title="<?= num_format( $summa_marg ) ?>"><strong><?= num_format( $summa_marg ) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>
		</div>
		<?php
	}
	//если включена рассрочка, то считаем по оплатам
	if ( $otherSettings['credit'] ) {
		?>
		<div id="formtabse" style="overflow: auto;">
			<table id="zebra">
				<thead>
				<tr>
					<TH width="10" align="center"></TH>
					<TH width="60" align="center">Дата оплаты.</TH>
					<TH width="60" align="center">№ счета</TH>
					<TH width="150" align="center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH width="120" align="center">Оплачено, <?= $valuta ?></TH>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<TH width="120" align="center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH width="80" align="center">Ответств.</TH>
				</tr>
				</thead>
				<?php

				//если расчет планов по оплатам
				if ( !$otherSettings['planByClosed'] ) {
					$q = "
					SELECT *
					FROM {$sqlname}credit
					WHERE
						{$sqlname}credit.do = 'on' and
						DATE_FORMAT({$sqlname}credit.invoice_date, '%Y-%m') = '".$year."-".$mon."' and 
						{$sqlname}credit.did IN (SELECT {$sqlname}dogovor.did FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.did > 0 ".$sort.") and
						{$sqlname}credit.identity = '$identity'
					ORDER by {$sqlname}credit.invoice_date DESC";
				}

				//расчет планов по закрытым сделкам
				else {
					$result = $q = "
					SELECT *
					FROM {$sqlname}credit
					WHERE
						{$sqlname}credit.do = 'on' and
						{$sqlname}credit.did IN (SELECT {$sqlname}dogovor.did FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.did > 0 ".$dsort." and DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and {$sqlname}dogovor.identity = '$identity')
						".$sort." and
						{$sqlname}credit.identity = '$identity'
					ORDER by {$sqlname}credit.invoice_date";
				}

				$result = $db -> getAll( $q );
				foreach ( $result as $data ) {

					$i++;

					if ( (int)$data['clid'] > 0 ) {
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient('.$data['clid'].')" title="Карточка: '.current_client( $data['clid'] ).'">'.current_client( $data['clid'] ).'</b></a>';
					}
					if ( (int)$data['pid'] > 0 ) {
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person( $data['pid'] ).'">'.current_person( $data['pid'] ).'</b></a>';
					}

					//найдем долю оплаченного счета
					$res    = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'" );
					$kol    = (float)$res["kol"];
					$marga  = (float)$res["marga"];
					$iduser = (int)$res["iduser"];
					$close  = $res["close"];

					$dolya   = ($kol > 0) ? (float)$data['summa_credit'] / $kol : 0;
					$marga_i = $marga * $dolya;

					$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
					?>
					<tr class="ha" height="35">
						<td align="center"><?= $i ?></td>
						<td align="center" class="smalltxt"><?= format_date_rus( $data['invoice_date'] ) ?></td>
						<td align="center"><?= $data['invoice'] ?></td>
						<td>
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor( $data['did'] ) ?>"><?= $ic ?><?= current_dogovor( $data['did'] ) ?></a>
							</div>
							<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
						</td>
						<td align="right">
							<span title="<?= num_format( $data['summa_credit'] ) ?>"><?= num_format( $data['summa_credit'] ) ?> <?= $valuta ?></span>
						</td>
						<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
							<td align="right">
								<span title="<?= num_format( $marga_i ) ?>"><?= num_format( $marga_i ) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td>
							<span class="ellipsis"><a href="javascript:void(0)" onClick="viewUser('<?= $data['iduser'] ?>');"><?= current_user( $iduser ) ?></a></span>
						</td>
					</tr>
					<?php
					$summa      += (float)$data['summa_credit'];
					$summa_marg += $marga_i;
				}
				?>
				<tr class="itog" height="35">
					<td align="center"></td>
					<td align="right"><strong>Итого:</strong></td>
					<td></td>
					<td align="right"><strong><?= $i ?></strong></td>
					<td align="right">
						<span title="<?= num_format( $summa ) ?>"><strong><?= num_format( $summa ) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<td align="right">
							<span title="<?= num_format( $summa_marg ) ?>"><strong><?= num_format( $summa_marg ) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
			</table>
		</div>
		<?php
		$marga_i = 0;
	}

	?>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

		if ($(window).width() > 990) {
			$('#dialog').css({'width': '800px'});
		}
		if ($(window).width() > 1200) {
			$('#dialog').css({'width': '950px'});
		}
		else {
			$('#dialog').css('width', '90vw');
		}

		$('#formtabse').css('max-height', hh2);

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

//$year = '2016'; $mon = '10';
$kfact = $mfact = $kperc = $mperc = $dataa = [];

$days = (int)date( "t", mktime( 1, 0, 0, $mon, 1, $year ) );

$result = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE iduser > 0 and acs_plan = 'on' and (secrty = 'yes' or DATE_FORMAT(CompEnd, '%Y-%m') >= '".$year."-".$mon."') and iduser in (SELECT iduser FROM {$sqlname}plan WHERE year='".$year."' and mon='".$mon."' $sd and identity = '$identity') and identity = '$identity' ORDER BY title" );
foreach ( $result as $data ) {

	$ac_importt[ (int)$data['iduser'] ] = explode( ";", $data['acs_import'] );

	$did_str = $dids = [];
	$kolfact = 0;
	$marfact = 0;
	$didss   = '';

	if ( $data['avatar'] ) {
		$avatarr[ (int)$data['iduser'] ] = "./cash/avatars/".$data['avatar'];
	}
	else {
		$avatarr[ (int)$data['iduser'] ] = "/assets/images/noavatar.png";
	}

	$users[ (int)$data['iduser'] ]['iduser'] = (int)$data['iduser'];
	$users[ (int)$data['iduser'] ]['tip']    = $data['tip'];

	//print "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM {$sqlname}plan WHERE year='".$year."' and mon='".$mon."' and iduser = '".$data['iduser']."' and identity = '$identity'<br>";

	//плановые показатели для текущего сотрудника
	$result1                  = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM {$sqlname}plan WHERE year='".$year."' and mon='".$mon."' and iduser = '".$data['iduser']."' and identity = '$identity'" );
	$kplan[ (int)$data['iduser'] ] = (float)$result1['kol'];
	$mplan[ (int)$data['iduser'] ] = (float)$result1['marga'];

	//список сделок сотрудника
	if ( $ac_importt[ (int)$data['iduser'] ][19] != 'on' ) {
		$sub = get_people( (int)$data['iduser'] );
	}
	else {
		$sub = " and iduser = '".$data['iduser']."'";
	}

	//фактические показатели
	$dolya = [];

	if ( !$otherSettings['planByClosed'] ) {
		$result3 = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' and DATE_FORMAT(invoice_date, '%Y-%m') = '".$year."-".$mon."' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 ".$sub." and identity = '$identity') and identity = '$identity' ORDER by did" );
	}

	if ( $otherSettings['planByClosed'] ) {
		$result3 = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE do = 'on' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 ".$sub." and DATE_FORMAT(datum_close, '%Y-%m') = '".$year."-".$mon."' and close = 'yes' and identity = '$identity') and identity = '$identity' ORDER by did" );
	}

	//print_r($result3);

	foreach ( $result3 as $data3 ) {

		//расчет процента размера платежа от суммы сделки
		$result4 = $db -> getRow( "SELECT kol, marga FROM {$sqlname}dogovor WHERE did = '".$data3['did']."' and identity = '$identity'" );
		$kolfact = pre_format( $result4["kol"] );//сумма всей сделки
		$marfact = pre_format( $result4["marga"] );//сумма всей сделки

		$dolya = ($kolfact > 0) ? $data3['summa_credit'] / $kolfact : 0;//% оплаченной суммы от суммы по договору

		//print $dolya."<br>";

		$kfact[ (int)$data['iduser'] ] += (float)$data3['summa_credit'];
		$mfact[ (int)$data['iduser'] ] += $marfact * $dolya;

	}

	$kperc[ (int)$data['iduser'] ] = $kplan[ (int)$data['iduser'] ] > 0 ? $kfact[ (int)$data['iduser'] ] / $kplan[ (int)$data['iduser'] ] * 100 : 0;
	$mperc[ (int)$data['iduser'] ] = $mplan[ (int)$data['iduser'] ] > 0 ? $mfact[ (int)$data['iduser'] ] / $mplan[ (int)$data['iduser'] ] * 100 : 0;

	$dataa[] = [
		"perc"   => (float)$mperc[ $data['iduser'] ],
		"iduser" => (int)$data['iduser'],
		"marga"  => (float)$mfact[ $data['iduser'] ],
		"kol"    => (float)$kfact[ $data['iduser'] ]
	];

}

//print_r($mplan);
//print_r($mfact);

function cmp($a, $b): bool {
	return $b['perc'] > $a['perc'];
}

usort( $dataa, 'cmp' );

?>
<STYLE type="text/css">
	<!--
	.raiting {
		width              : 170px;
		display            : inline-block;
		padding            : 10px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}
	.raiting:hover {
		-moz-box-shadow    : 0 0 5px #999;
		-webkit-box-shadow : 0 0 5px #999;
		box-shadow         : 0 0 5px #999;
	}
	.progressbar-completed,
	.status {
		height     : 0.4em;
		box-sizing : border-box;
	}
	.progressbar-completed div {
		display : inline;
	}
	#clientlist .avatarbig {
		width                 : 100px;
		height                : 100px;
		margin                : 0 auto;
		border                : 5px solid #E74B3B;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	#clientlist .avatarbig--inner {
		width                 : 96px;
		height                : 96px;
		margin                : 0 auto;
		border                : 2px solid #FFF;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	.raiting-mini {
		width              : 190px !important;
		display            : inline-block;
		padding            : 5px;
		border             : 0 dotted #ddd;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}
	#clientlist .avatar--mini {
		width                 : 50px;
		height                : 50px;
		border                : 5px solid #CCC;
		border-radius         : 200px;
		-webkit-border-radius : 200px;
		-moz-border-radius    : 200px;
	}
	.candidate .avatarbig {
		border : 5px solid #349C5A;
	}
	.flex-container.main {
		align-content   : stretch !important;
		flex-wrap       : wrap !important;
		justify-content : center;
	}
	.flex-container.main > .flex-string {
		min-width          : 200px !important;
		flex-grow          : inherit !important;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}
	.flex-container.last {
		align-content   : stretch !important;
		flex-wrap       : wrap !important;
		justify-content : center;
		min-width       : 300px !important;
	}
	.flex-container.last > .flex-string {
		flex-grow          : inherit !important;
		padding            : 5px;
		box-sizing         : border-box;
		-moz-box-sizing    : border-box;
		-webkit-box-sizing : border-box;
	}

	.dwinner {
		border-top    : 2px dotted rgba(231, 75, 59, 0.3);
		margin-bottom : 5px;
	}
	.dcandidate {
		border-top    : 2px dotted rgba(21, 157, 130, 0.3);
		margin-bottom : 5px;
	}
	.dloozer {
		border-top    : 2px dotted #DDD;
		margin-bottom : 5px;
	}
	.ryear, .rmon {
		border     : 1px solid rgba(207, 216, 220, 1) !important;
		background : rgba(207, 216, 220, .3) !important;
	}
	.ryear.active,
	.rmon.active {
		border     : 1px solid rgba(231, 75, 59, 1.3) !important;
		background : rgba(231, 75, 59, 0.3) !important;
	}

	#tagbox .tags {
		color : #222 !important;
	}
	-->
</STYLE>

<div class="zagolovok_rep bigtxt relativ text-center mb20">

	<h1>Рейтинг "Выполнение планов"</h1>

	<div class="blue">за <?= ru_mon( $mon )." ".$year ?></div>

	<div class="pull-right hand noprint" style="top:20px;">
		<form id="customForm" name="customForm">

			<div class="pop nothide" id="params">

				<div class="gray2 mr20 fs-12"><i class="icon-cog-1 gray2"></i></div>

				<div class="popmenu-top cursor-default" style="right:10px">

					<div class="popcontent info1 w300 pad10 fs-07 box--child" style="right: 0;">

						<div class="flex-container">

							<div class="flex-string wp20 text-right fs-14 pt5 pr10">Год:</div>
							<div class="flex-string wp80">

								<div class="flex-container" id="tagbox">

									<input type="hidden" id="year" name="year" value="<?= (int)$year ?>">

									<?php
									$y = (int)date( 'Y' );
									while ($y > (int)date( 'Y' ) - 4) {

										$s = ($y == (int)$year) ? "active" : "";

										print '
										<div class="flex-string p5 fs-14 ryear tags text-center '.$s.'" data-year="'.$y.'">
										'.$y.'
										</div>
										';

										$y--;

									}
									?>

								</div>

							</div>

						</div>
						<hr>
						<div class="flex-container">

							<div class="flex-string wp20 text-right fs-14 pt5 pr10">Месяц:</div>
							<div class="flex-string wp80">

								<div class="flex-container" id="tagbox">

									<input type="hidden" id="mon" name="mon" value="<?= (int)$mon ?>">

									<?php
									$y = date( 'Y' );
									for ( $i = 1; $i <= 12; $i++ ) {

										$s = ($i == (int)$mon) ? "active" : "";

										print '
										<div class="flex-string p5 fs-14 rmon tags text-center '.$s.'" data-mon="'.$i.'">
										'.ru_mon( $i ).'
										</div>
										';

									}
									?>

								</div>

							</div>

						</div>
						<hr>
						<div class="flex-container mt10 mb10">

							<div class="flex-string wp20"></div>
							<div class="flex-string wp80">

								<div class="checkbox fs-14 clearevent">
									<label for="roles">
										<input name="roles" type="checkbox" id="roles" value="yes" <?php if ( $roles == 'yes' ) {
											print 'checked'; } ?>>
										<span class="custom-checkbox"><i class="icon-ok"></i></span>
										&nbsp;Только менеджеры
									</label>
								</div>

							</div>

						</div>
						<hr>
						<div class="flex-container mt20">

							<div class="flex-string wp20"></div>
							<div class="flex-string wp80">

								<a href="javascript:void(0)" onclick="generateReport()" class="button fs-11" title="Применить"><i class="icon-ok"></i>Применить</a>

							</div>

						</div>

					</div>

				</div>

			</div>

		</form>
	</div>

</div>
<?php
$loozers = $winners = $candidates = '';

foreach ($dataa as $row) {

	$mwidth = ($mperc[ $row['iduser'] ] <= 100) ? $mperc[ $row['iduser'] ] : "100";
	$kwidth = ($kperc[ $row['iduser'] ] <= 100) ? $kperc[ $row['iduser'] ] : "100";

	if ( $mperc[ $row['iduser'] ] + $kperc[ $row['iduser'] ] > 0 ) {

		if ( $mperc[ $row['iduser'] ] >= 100 ) {
			$star = str_repeat( '<i class="icon-star red"></i>', 6 );
		}
		elseif ( is_between( $mperc[ $row['iduser'] ], 80, 100 ) ) {
			$star = str_repeat( '<i class="icon-star red"></i>', 5 ).str_repeat( '<i class="icon-star-empty gray"></i>', 0 );
		}
		elseif ( is_between( $mperc[ $row['iduser'] ], 60, 80 ) ) {
			$star = str_repeat( '<i class="icon-star red"></i>', 4 ).str_repeat( '<i class="icon-star-empty gray"></i>', 1 );
		}
		elseif ( is_between( $mperc[ $row['iduser'] ], 40, 60 ) ) {
			$star = str_repeat( '<i class="icon-star red"></i>', 3 ).str_repeat( '<i class="icon-star-empty gray"></i>', 2 );
		}
		elseif ( is_between( $mperc[ $row['iduser'] ], 20, 40 ) ) {
			$star = str_repeat( '<i class="icon-star red"></i>', 2 ).str_repeat( '<i class="icon-star-empty gray"></i>', 3 );
		}
		elseif ( is_between( $mperc[ $row['iduser'] ], 0, 20 ) ) {
			$star = str_repeat( '<i class="icon-star red"></i>', 1 ).str_repeat( '<i class="icon-star-empty gray"></i>', 4 );
		}
		else {
			$star = str_repeat( '<i class="icon-star orange"></i>', 0 ).str_repeat( '<i class="icon-star-empty gray"></i>', 5 );
		}

		$string = '
		<div class="marg3 flex-string raiting hand" onClick="doLoad(\'reports/raiting_plan.php?action=planView&iduser='.$row['iduser'].'&mon='.$mon.'&year='.$year.'\')">
	
			<div class="div-center">
				<div class="Bold mb10 fs-12">'.current_user( $row['iduser'], "yes" ).'</div>
				<div class="avatarbig" style="background: url('.$avatarr[ $row['iduser'] ].'); background-size:cover;" title="'.current_user( $users[ $row['iduser'] ]['iduser'] ).'">
					<div class="avatarbig--inner"></div>
				</div>
				<br>'.$star.'<br>
			</div>
			<div class="mt20">
				<div class="paddbott5">
					<span class="smalltxt">М: '.str_replace( " ", "`", num_format( $mfact[ $row['iduser'] ] ) ).'&nbsp;/&nbsp;'.str_replace( " ", "`", num_format( $mperc[ $row['iduser'] ] ) ).'%</span>
					<DIV class="progressbar" style="border:1px solid '.$color[ $i ].' !important;">
						<DIV class="progressbar-completed m0" style="width:'.$mwidth.'%; background:'.$color[ $i ].';">
							<DIV class="status black"></DIV>
						</DIV>
					</DIV>
				</div>
				<div>
					<span class="smalltxt">О: '.str_replace( " ", "`", num_format( $kfact[ $row['iduser'] ] + 0 ) ).'&nbsp;/&nbsp;'.str_replace( " ", "`", num_format( $kperc[ $row['iduser'] ] + 0 ) ).'%</span>
					<DIV class="progressbar" style="border:1px solid '.$color[ $i ].' !important;">
						<DIV class="progressbar-completed m0" style="width:'.$kwidth.'%; background:'.$color[ $i ].';">
							<DIV class="status black"></DIV>
						</DIV>
					</DIV>
				</div>
			</div>
	
		</div>';

		if ( $mperc[ $row['iduser'] ] > 50 ) {
			$winners .= $string;
		}
		else {
			$candidates .= $string;
		}

	}
	else {

		$loozers .= '
		<div class="marg3 flex-string last flex-container raiting-mini hand" onClick="doLoad(\'reports/raiting_plan.php?action=planView&iduser='.$row['iduser'].'&mon='.$mon.'&year='.$year.'\')">
			
			<div class="flex-string wp30">
				<div class="avatar--mini" style="background: url('.$avatarr[ $row['iduser'] ].'); background-size:cover;" title="'.current_user( $users[ $row['iduser'] ]['iduser'] ).'"></div>
			</div>
			<div class="flex-string wp70">
				<div class="Bold mb10 fs-12">'.current_user( $row['iduser'], "yes" ).'</div>
				Маржа: '.str_replace( " ", "`", num_format( $mperc[ $row['iduser'] ] + 0 ) ).'% <br>
				Оборот: '.str_replace( " ", "`", num_format( $kperc[ $row['iduser'] ] + 0 ) ).'%
			</div>
			
		</div>
		';

	}

}

//print $loozers;
?>
<?php if ( $winners != '' ) { ?>

	<div class="dwinner">

		<div class="zagolovok_rep fs-14" align="center">

			<h2 class="red m0 mt10"><i class="icon-trophy"></i>Фавориты</h2>

		</div>

		<div class="flex-container main block p10 wp97">
			<?= $winners ?>
		</div>

	</div>

<?php } ?>
<?php if ( $candidates != '' ) { ?>

	<div class="dcandidate">

		<div class="zagolovok_rep fs-14" align="center">

			<h2 class="green m0 mt10"><i class="icon-graduation-cap-1"></i>&nbsp;Кандидаты</h2>

		</div>

		<div class="flex-container main block p10 wp97 candidate">
			<?= $candidates ?>
		</div>

	</div>

<?php } ?>
<?php if ( $loozers != '' ) { ?>

	<div class="dloozer">

		<div class="zagolovok_rep fs-14" align="center">

			<h2 class="gray2 m0 mt10"><i class="icon-thumbs-down-alt"></i>Аутсайдеры</h2>

		</div>

		<div class="flex-container last block p10 wp97">
			<?= $loozers ?>
		</div>

	</div>

<?php } ?>

<div style="height:60px; display:block"></div>

<script>

	$('.ryear').on('click', function () {

		var y = $(this).data('year');
		$('#year').val(y);
		$('.ryear').not(this).removeClass('active');
		$(this).addClass('active');

	});
	$('.rmon').on('click', function () {

		var m = $(this).data('mon');
		$('#mon').val(m);
		$('.rmon').not(this).removeClass('active');
		$(this).addClass('active');

	});
	$('.checkbox label').on('click', function () {

		//$('.popmenu-top').css({'display':'block'});
		$('.popmenu-top').show();

	});

</script>