<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

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

global $userRights;

$tm = $GLOBALS['tzone'];
$y  = date('Y');
$m  = date('m');
$ds = (int)date( "t", mktime( 1, 0, 0, $m, 1, $y ) );

$m1 = date('Y')."-".$m."-01 00:00:00";
$m2 = date('Y')."-".$m."-".$ds." 23:59:59";

$m3 = date('Y')."-".$m."-01";


$action    = $_REQUEST['action'];
$user      = $_REQUEST['iduser'];
$onlyuser  = $_REQUEST['onlyuser'];
$vInterval = '';

if (!$user) {
	$user = $iduser1;
}

$sort = ($userRights['individualplan']) ? " and {$sqlname}dogovor.iduser = '$user'" : get_people($user);

if ($onlyuser == 'yes') {
	$sort = " and {$sqlname}dogovor.iduser = '$user'";
}

if ( $otherSettings[ 'planByClosed']) {
	$dsort = " and COALESCE({$sqlname}dogovor.close, 'no') = 'yes'";
}

if ($action == 'stepView') {

	$summa = $summa_marg = 0;

	$step     = $_REQUEST['step'];
	$stepName = str_replace("%", "", $_REQUEST['stepName']);
	$i        = 0;

	if ($stepName != '') {

		if ($stepName != 'Закрыто') {
			$step = getStep($stepName);

			$vInterval = $_COOKIE['voronkaInterval'];

		}
		else $step = 'closed';

	}
	?>
	<div class="zagolovok">Данные по запросу</div>

	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table class="bborder top" id="zebraTable">
			<thead class="sticked--top">
			<tr class="header_contaner1">
				<TH class="w20 text-center"></TH>
				<TH class="w80 text-center">Дата план.</TH>
				<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
				<TH class="w80 text-center">Этап</TH>
				<TH class="w130 text-center">Сумма, <?= $valuta ?></TH>
				<TH class="w100 text-center">Ответств.</TH>
			</tr>
			</thead>
			<tbody>
			<?php

			if ($step != 'closed') {

				if ($vInterval != 'all' && $_REQUEST['all'] != 'yes') {
					//$ss = "AND datum_plan BETWEEN '".$m1."' AND '".$m2."'";
					$ss = "AND ({$sqlname}dogovor.datum_plan >= '$m3' AND {$sqlname}dogovor.datum_plan < '$m3' + INTERVAL 1 MONTH )";
				}

				$q = "SELECT * FROM {$sqlname}dogovor WHERE did > 0 and COALESCE(close, 'no') != 'yes' $ss and idcategory = '$step' $sort and identity = '$identity'";

			}
			else {

				$q = "SELECT *, kol_fact as kol FROM {$sqlname}dogovor WHERE did > 0 and COALESCE(close, 'no') = 'yes' and ({$sqlname}dogovor.datum_close >= '$m3' AND {$sqlname}dogovor.datum_close < '$m3' + INTERVAL 1 MONTH ) $sort and identity = '$identity'";

			}

			//print $q;

			$result = $db -> getAll($q);

			foreach ($result as $data) {

				$i++;

				if ($data['clid'] > 0)
					$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

				elseif ($data['pid'] > 0)
					$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

				$summa += $data['kol'];
				?>
				<tr class="ha bgwhite th35">
					<td class="text-center"><?= $i ?></td>
					<td class="text-center"><?= format_date_rus($data['datum_plan']) ?></td>
					<td>
						<span class="ellipsis Bold fs-11" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
						<br>
						<div class="ellipsis paddtop5 gray fs-09"><?= $client ?></div>
					</td>
					<td class="text-center"><b><?= current_dogstepname($data['idcategory']) ?></b>%</td>
					<td class="text-right">
						<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?></span></td>
					<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
				</tr>
				<?php
			}
			?>
			</tbody>
			<tfoot class="sticked--bottom">
				<tr class="bluebg-sub th35">
					<td></td>
					<td class="text-right" colspan="2"><b>Абсолютная сумма:</b></td>
					<td class="text-center"><b><?= $i ?></b> шт.</td>
					<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span></td>
					<td></td>
				</tr>
				<?php
				if ($stepName && $step != 'closed') {

					$ves = $summa * (int)$stepName / 100;
				?>
				<tr class="redbg-sub th35">
					<td></td>
					<td class="text-right" colspan="2"><b>Вес сделок:</b></td>
					<td></td>
					<td class="text-right"><span title="<?= num_format($ves) ?>"><b><?= num_format($ves) ?></b></span></td>
					<td></td>
				</tr>
				<?php } ?>
			</tfoot>
		</table>

	</div>
	<?php

}
if ($action == 'planView') {

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;
	//если выполнение считаем только по закрытым сделкам
	if (!$otherSettings[ 'credit']) {
		?>
		<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

			<table id="zebraTable" class="top" style="z-index: 100">
				<thead class="sticked--top">
				<tr class="header_contaner">
					<TH class="w20 text-center"></TH>
					<TH class="w80 text-center">Дата факт.</TH>
					<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w130 text-center">Сумма, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<TH class="w130 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<tbody>
				<?php
				$q = "
				SELECT
					*
				FROM
					{$sqlname}dogovor
				WHERE
					{$sqlname}dogovor.did > 0 and
					-- ({$sqlname}dogovor.datum_close between '$m1' and '$m2') and
					-- ({$sqlname}dogovor.datum_close >= '$m3' AND {$sqlname}dogovor.datum_close < '$m3' + INTERVAL 1 MONTH ) AND
					DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%c') = '$y-$m' and 
					{$sqlname}dogovor.kol_fact > 0 and
					COALESCE({$sqlname}dogovor.close, 'no') = 'yes' $sort and
					{$sqlname}dogovor.identity = '$identity'
				";

				$result = $db -> getAll($q);
				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

					elseif ($data['pid'] > 0)
						$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

					?>
					<tr class="ha bgwhite th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><?= format_date_rus($data['datum_close']) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
							<br>
							<div class="ellipsis paddtop5"><?= $client ?></div>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?></span></td>
						<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($data['marga']) ?>"><?= num_format($data['marga']) ?></span>
							</td>
						<?php } ?>
						<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
					</tr>
					<?php
					$summa      += $data['kol'];
					$summa_marg += $data['marga'];
				}
				?>
				</tbody>
				<tfoot class="sticked--bottom">
				<tr class="itog th35">
					<td></td>
					<td class="text-right"><b>Итого:</b></td>
					<td class="text-right"><b><?= $i ?></b></td>
					<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<td class="text-right nowrap">
							<span title="<?= num_format($summa_marg) ?>"><b><?= num_format($summa_marg) ?></b></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
				</tfoot>
			</table>

		</div>
		<?php
	}
	//если включена рассрочка, то считаем по оплатам
	if ( $otherSettings[ 'credit']) {
		?>
		<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

			<table id="zebraTable" class="top" style="z-index: 100">
				<thead class="sticked--top">
				<tr class="header_contaner">
					<TH class="w10 text-center"></TH>
					<TH class="w60 text-center">Дата оплаты.</TH>
					<TH class="w60 text-center">№ счета</TH>
					<TH class="w150 text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w120 text-center">Оплачено, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<TH class="w120 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<tbody>
				<?php

				//если расчет планов по оплатам
				if (!$otherSettings[ 'planByClosed']) $q = "
					SELECT *
					FROM {$sqlname}credit
					WHERE
						{$sqlname}credit.do = 'on' and
						({$sqlname}credit.invoice_date >= '$m3' AND {$sqlname}credit.invoice_date < '$m3' + INTERVAL 1 MONTH) and
						{$sqlname}credit.did IN (SELECT {$sqlname}dogovor.did FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.did > 0 ".$sort.") and
						{$sqlname}credit.identity = '$identity'
					ORDER by {$sqlname}credit.invoice_date DESC";

				//расчет планов по оплаченным счетам в закрытых сделках текущего месяца
				else $q = "
					SELECT 
						{$sqlname}credit.crid,
						{$sqlname}credit.do,
						{$sqlname}credit.did,
						{$sqlname}credit.clid,
						{$sqlname}credit.pid,
						{$sqlname}credit.invoice,
						{$sqlname}credit.invoice_date,
						{$sqlname}credit.summa_credit,
						{$sqlname}credit.iduser,
						{$sqlname}dogovor.title as deal,
						{$sqlname}dogovor.kol,
						{$sqlname}dogovor.marga,
						{$sqlname}dogovor.iduser as diduser,
						{$sqlname}dogovor.close,
						{$sqlname}dogovor.datum_close,
						{$sqlname}clientcat.title as client
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
						LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
					WHERE
						{$sqlname}credit.do = 'on' and
						COALESCE({$sqlname}dogovor.close, 'no') = 'yes' and
						DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%m') = '".date('Y')."-".date('m')."' and 
						{$sqlname}credit.iduser IN (".implode(",", get_people($iduser1, "yes")).") and 
						{$sqlname}credit.identity = '$identity'
					ORDER by {$sqlname}credit.invoice_date";

				$result = $db -> getAll($q);
				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

					elseif ($data['pid'] > 0)
						$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

					//найдем долю оплаченного счета
					$res    = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'");
					$kol    = $res["kol"];
					$marga  = $res["marga"];
					$iduser = $res["iduser"];
					$close  = $res["close"];

					$dolya   = ($kol > 0) ? $data['summa_credit'] / $kol : 0;
					$marga_i = $marga * $dolya;

					$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
					?>
					<tr class="ha bgwhite th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><?= format_date_rus($data['invoice_date']) ?></td>
						<td class="text-center"><?= $data['invoice'] ?></td>
						<td>
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><?= $ic ?><?= current_dogovor($data['did']) ?></a>
							</div>
							<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['summa_credit']) ?>"><?= num_format($data['summa_credit']) ?></span>
						</td>
						<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($marga_i) ?>"><?= num_format($marga_i) ?></span></td>
						<?php } ?>
						<td>
							<span class="ellipsis"><a href="javascript:void(0)" onClick="viewUser('<?= $data['iduser'] ?>');"><?= current_user($iduser) ?></a></span>
						</td>
					</tr>
					<?php
					$summa      += $data['summa_credit'];
					$summa_marg += $marga_i;
				}
				?>
				</tbody>
				<tfoot class="sticked--bottom">
				<tr class="itog th35">
					<td class="text-center"></td>
					<td class="text-right"><b>Итого:</b></td>
					<td></td>
					<td class="text-right"><b><?= $i ?></b></td>
					<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<td class="text-right">
							<span title="<?= num_format($summa_marg) ?>"><b><?= num_format($summa_marg) ?></b></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
				</tfoot>
			</table>

		</div>
		<?php
		$marga_i = 0;
	}

}
if ($action == 'paymentView') {

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;

	//если выполнение считаем только по закрытым сделкам
	if (!$otherSettings[ 'credit']) {
		?>
		<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

			<table id="zebraTable" style="z-index: 100">
				<thead class="sticked--top">
				<tr class="header_contaner">
					<TH class="w20 text-center"></TH>
					<TH class="w80 text-center">Дата факт.</TH>
					<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w120 text-center">Сумма, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<TH class="w120 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<tbody>
				<?php
				$q = "
					SELECT
						*
					FROM
						{$sqlname}dogovor
					WHERE
						{$sqlname}dogovor.did > 0 and
						-- ({$sqlname}dogovor.datum_close between '$m1' and '$m2') and
						({$sqlname}dogovor.datum_close >= '$m3' AND {$sqlname}dogovor.datum_close < '$m3' + INTERVAL 1 MONTH ) AND
						{$sqlname}dogovor.kol_fact > 0 and
						COALESCE({$sqlname}dogovor.close, 'no') = 'yes' 
						$sort and
						identity = '$identity'
					";

				$result = $db -> getAll($q);
				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

					elseif ($data['pid'] > 0)
						$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

					?>
					<tr class="ha bgwhite th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><?= format_date_rus($data['datum_close']) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
							<br>
							<div class="ellipsis paddtop5"><?= $client ?></div>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?></span></td>
						<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($data['marg']) ?>"><?= num_format($data['marg']) ?></span>
							</td>
						<?php } ?>
						<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
					</tr>
					<?php
					$summa      += $data['kol'];
					$summa_marg += $data['marg'];
				}
				?>
				</tbody>
				<tfoot class="sticked--bottom">
				<tr class="itog th35">
					<td></td>
					<td class="text-right"><b>Итого:</b></td>
					<td class="text-right"><b><?= $i ?></b></td>
					<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<td class="text-right nowrap">
							<span title="<?= num_format($summa_marg) ?>"><b><?= num_format($summa_marg) ?></b></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
				</tfoot>
			</table>

		</div>
		<?php
	}

	//если включена рассрочка, то считаем по оплатам
	if ( $otherSettings[ 'credit']) {
		?>
		<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

			<table id="zebra" style="z-index: 100">
				<thead class="sticked--top">
				<tr>
					<TH class="w10 text-center"></TH>
					<TH class="w60 text-center">Дата оплаты.</TH>
					<TH class="w60 text-center">№ счета</TH>
					<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
					<TH class="w120 text-center">Оплачено, <?= $valuta ?></TH>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<TH class="w120 text-center">Маржа, <?= $valuta ?></TH>
					<?php } ?>
					<TH class="w80 text-center">Ответств.</TH>
				</tr>
				</thead>
				<tbody>
				<?php

				if (!$otherSettings[ 'planByClosed'])
					$result = $db -> getAll("
						SELECT * 
						FROM {$sqlname}credit 
						WHERE 
							{$sqlname}credit.do = 'on' and 
							-- {$sqlname}credit.invoice_date between '$m1' and '$m2' and
							({$sqlname}credit.invoice_date >= '$m3' AND {$sqlname}credit.invoice_date < '$m3' + INTERVAL 1 MONTH ) AND
							{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 $sort) and 
							{$sqlname}credit.identity = '$identity' 
						ORDER by {$sqlname}credit.invoice_date DESC");

				else
					$result = $db -> getAll("
						SELECT * 
						FROM {$sqlname}credit 
						WHERE 
							do = 'on' and 
							did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 $dsort and DATE_FORMAT(datum_close, '%Y-%m') = '".date('Y')."-".date('m')."' and identity = '$identity') and 
							identity = '$identity' 
						ORDER by invoice_date");

				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<i class="icon-building broun"></i><a href="javascript:void(0)" onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'">'.current_client($data['clid']).'</b></a>';

					elseif ($data['pid'] > 0)
						$client = '<i class="icon-user-1 blue"></i><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'">'.current_person($data['pid']).'</b></a>';

					//найдем долю оплаченного счета
					$res    = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'");
					$kol    = $res["kol"];
					$marga  = $res["marga"];
					$iduser = $res["iduser"];
					$close  = $res["close"];

					$dolya = ($kol > 0) ? $data['summa_credit'] / $kol : 0;

					$marga_i = $marga * $dolya;

					$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
					?>
					<tr class="ha bgwhite th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><?= format_date_rus($data['invoice_date']) ?></td>
						<td class="text-center"><?= $data['invoice'] ?></td>
						<td>
							<div class="ellipsis">
								<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><?= $ic ?><?= current_dogovor($data['did']) ?></a>
							</div>
							<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['summa_credit']) ?>"><?= num_format($data['summa_credit']) ?> <?= $valuta ?></span>
						</td>
						<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
							<td class="text-right">
								<span title="<?= num_format($marga_i) ?>"><?= num_format($marga_i) ?> <?= $valuta ?></span>
							</td>
						<?php } ?>
						<td>
							<span class="ellipsis"><a href="javascript:void(0)" onClick="viewUser('<?= $data['iduser'] ?>');"><?= current_user($iduser) ?></a></span>
						</td>
					</tr>
					<?php
					$summa      += $data['summa_credit'];
					$summa_marg += $marga_i;
				}
				?>
				</tbody>
				<tfoot class="sticked--bottom">
				<tr class="itog th35">
					<td></td>
					<td class="text-right"><b>Итого:</b></td>
					<td></td>
					<td class="text-right"><b><?= $i ?></b></td>
					<td class="text-right">
						<span title="<?= num_format($summa) ?>"><strong><?= num_format($summa) ?></strong> <?= $valuta ?></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<td class="text-right">
							<span title="<?= num_format($summa_marg) ?>"><strong><?= num_format($summa_marg) ?></strong> <?= $valuta ?></span>
						</td>
					<?php } ?>
					<td></td>
				</tr>
				</tfoot>
			</table>

		</div>
		<?php
		$marga_i = 0;
	}

}
if ($action == 'paymentViewNew') {

	$summa = $summa_marg = 0;
	$i     = 0;
	?>

	<div class="zagolovok">Оплаты по сотруднику</div>

	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table id="zebraTable" style="z-index: 100">
			<thead class="sticked--top">
			<tr>
				<TH class="w10 text-center"></TH>
				<TH class="w60 text-center">Дата оплаты.</TH>
				<TH class="w60 text-center">№ счета</TH>
				<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
				<TH class="w120 text-center">Оплачено, <?= $valuta ?></TH>
				<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
					<TH class="w120 text-center">Маржа, <?= $valuta ?></TH>
				<?php } ?>
				<TH class="w80 text-center">Ответств.</TH>
			</tr>
			</thead>
			<tbody>
			<?php

			$q = "
			SELECT
				*
			FROM {$sqlname}credit
			WHERE
				{$sqlname}credit.did > 0 and
				{$sqlname}credit.do = 'on' and
				DATE_FORMAT({$sqlname}credit.invoice_date, '%y-%m') = '".date('y')."-".date('m')."' and
				{$sqlname}credit.iduser = '".$user."' and
				{$sqlname}credit.identity = '$identity'
			";

			$result = $db -> getAll($q);

			foreach ($result as $data) {

				$i++;

				if ($data['clid'] > 0)
					$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

				elseif ($data['pid'] > 0)
					$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

				//найдем долю оплаченного счета
				$res    = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'");
				$kol    = $res["kol"];
				$marga  = $res["marga"];
				$iduser = $res["iduser"];
				$close  = $res["close"];

				$dolya = ($kol > 0) ? $data['summa_credit'] / $kol : 0;

				$marga_i = $marga * $dolya;

				$ic = ($close == 'yes') ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
				?>
				<tr class="ha bgwhite th35">
					<td class="text-center"><?= $i ?></td>
					<td class="text-center"><?= format_date_rus($data['invoice_date']) ?></td>
					<td class="text-center"><?= $data['invoice'] ?></td>
					<td>
						<div class="ellipsis">
							<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><?= $ic ?><?= current_dogovor($data['did']) ?></a>
						</div>
						<br><span class="ellipsis paddtop5" title="Карточка клиента"><?= $client ?></span>
					</td>
					<td class="text-right">
						<span title="<?= num_format($data['summa_credit']) ?>"><?= num_format($data['summa_credit']) ?></span>
					</td>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<td class="text-right"><span title="<?= num_format($marga_i) ?>"><?= num_format($marga_i) ?></span>
						</td>
					<?php } ?>
					<td>
						<span class="ellipsis"><a href="javascript:void(0)" onClick="viewUser('<?= $data['iduser'] ?>');"><?= current_user($iduser) ?></a></span>
					</td>
				</tr>
				<?php
				$summa      += $data['summa_credit'];
				$summa_marg += $marga_i;
			}
			?>
			</tbody>
			<tfoot class="sticked--bottom">
			<tr class="itog th35">
				<td></td>
				<td class="text-right"><b>Итого:</b></td>
				<td></td>
				<td class="text-right"><b><?= $i ?></b></td>
				<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span></td>
				<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
					<td class="text-right"><span title="<?= num_format($summa_marg) ?>"><b><?= num_format($summa_marg) ?></b></span>
					</td>
				<?php } ?>
				<td></td>
			</tr>
			</tfoot>
		</table>

	</div>

	<?php
	$marga_i = 0;

}
if ($action == 'prognozView') {

	$datum  = $_GET['datum'];
	$iduser = $_GET['iduser'];
	$i      = 0;

	$summa = $prognoz_total = 0;

	?>
	<div class="zagolovok">Данные по запросу</div>
	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table id="zebraTable" style="z-index: 100">
			<thead class="sticked--top">
			<tr class="header_contaner1">
				<TH class="w20 text-center"></TH>
				<TH class="w40 text-center">Этап</TH>
				<TH class="w80 text-center">Дата план.</TH>
				<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
				<TH class="w120 text-center">Вес, <?= $valuta ?></TH>
				<TH class="w120 text-center">Сумма, <?= $valuta ?></TH>
				<TH class="w80 text-center">Ответств.</TH>
			</tr>
			<tr class="bluebg toggler th30">
				<th colspan="7"><b>Активные <?= $lang['face']['DealsName'][0] ?></b></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE did > 0 and COALESCE(close, 'no') != 'yes' and DATE_FORMAT(datum_plan, '%y-%m') = '$datum' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$i++;

				if ($data['clid'] > 0)
					$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

				elseif ($data['pid'] > 0)
					$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

				$summa         += $data['kol'];
				$prognoz       = $data['kol'] * current_dogstepname($data['idcategory']) / 100;
				$prognoz_total += $prognoz;
				?>
				<tr class="ha bgwhite th35">
					<td class="text-center"><?= $i ?></td>
					<td class="text-center"><b><?= current_dogstepname($data['idcategory']) ?></b>%</td>
					<td class="text-center"><?= format_date_rus($data['datum_plan']) ?></td>
					<td>
						<div class="ellipsis fs-10 Bold" title="<?= $data['title'] ?>">
							<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?>
							</a>
						</div>
						<br>
						<div class="ellipsis mt5"><?= $client ?></div>
					</td>
					<td class="text-right"><span title="<?= num_format($prognoz) ?>"><?= num_format($prognoz) ?></span></td>
					<td class="text-right">
						<span title="<?= num_format($data['kol']) ?>" class="Bold blue"><?= num_format($data['kol']) ?></span></td>
					<td>
						<span class="ellipsis"><?= current_user($data['iduser']) ?></span>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
			<?php
			if ($onlyuser != 'yes') {
				?>
			<thead class="sticked--top">
			<tr class="redbg toggler th30">
				<th colspan="7"><b>Закрытые <?= $lang['face']['DealsName'][0] ?></b></th>
			</tr>
			</thead>
			<tbody>
				<?php
				$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE did > 0 and COALESCE(close, 'no') = 'yes' and DATE_FORMAT(datum_close, '%y-%m') = '$datum' $sort and identity = '$identity'");
				foreach ($result as $data) {

					$i++;

					if ($data['clid'] > 0)
						$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

					elseif ($data['pid'] > 0)
						$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

					$summa         += $data['kol_fact'];
					$prognoz       = $data['kol_fact'];
					$prognoz_total += $prognoz;
					?>
					<tr class="ha bgwhite th35">
						<td class="text-center"><?= $i ?></td>
						<td class="text-center"><b><?= current_dogstepname($data['idcategory']) ?></b>%</td>
						<td class="text-center" class="smalltxt"><?= format_date_rus($data['datum_plan']) ?></td>
						<td>
							<span class="ellipsis" title="<?= $data['title'] ?>"><i class="icon-briefcase blue"></i><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')"><?= current_dogovor($data['did']) ?></a></span>
							<br>
							<div class="ellipsis paddtop5"><?= $client ?></div>
						</td>
						<td class="text-right"><span title="<?= num_format($prognoz) ?>"><?= num_format($prognoz) ?></span>
						</td>
						<td class="text-right">
							<span title="<?= num_format($data['kol_fact']) ?>"><?= num_format($data['kol_fact']) ?></span>
						</td>
						<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
					</tr>
					<?php
				}
				?>
			</tbody>
			<?php
			}
			?>
			<tfoot class="sticked--bottom">
			<tr class="itog th35">
				<td></td>
				<td class="text-right"><b>Итого:</b></td>
				<td class="text-right"><b><?= $i ?></b></td>
				<td></td>
				<td class="text-right">
					<span title="<?= num_format($prognoz_total) ?>"><b><?= num_format($prognoz_total) ?></b></span></td>
				<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span></td>
				<td></td>
			</tr>
			</tfoot>
		</table>

	</div>
	<?php

}
if ($action == 'dogsView') {

	$clid  = $_GET['clid'];
	$i     = 0;
	$summa = $prognoz_total = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>

	<div id="formtabs" style="overflow-x: hidden; overflow-y: auto !important;">

		<table id="zebraTable" style="z-index: 100">
			<thead class="sticked--top">
			<tr>
				<TH class="w20 text-center">#</TH>
				<TH class="w40 text-center">Этап</TH>
				<TH class="w80 text-center">Дата план.</TH>
				<TH class="text-center"><?= $lang['face']['DealName'][0] ?> / Клиент</TH>
				<TH class="w120 text-center">Вес, <?= $valuta ?></TH>
				<TH class="w120 text-center">Сумма, <?= $valuta ?></TH>
				<TH class="w80 text-center">Ответств.</TH>
			</tr>
			</thead>
			<tbody>
			<tr class="bluebg toggler sticked--top th35">
				<td colspan="7"><b>Активные <?= $lang['face']['DealsName'][0] ?></b></td>
			</tr>
			<?php
			$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE did > 0 and COALESCE(close, 'no') != 'yes' and clid = '$clid' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$i++;

				if ($data['clid'] > 0)
					$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

				elseif ($data['pid'] > 0)
					$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

				$summa         += $data['kol'];
				$prognoz       = $data['kol'] * current_dogstepname($data['idcategory']) / 100;
				$prognoz_total += $prognoz;

				?>
				<tr class="ha bgwhite th35">
					<td class="text-center"><?= $i ?></td>
					<td class="text-center"><b><?= current_dogstepname($data['idcategory']) ?></b>%</td>
					<td class="text-center"><?= format_date_rus($data['datum_plan']) ?></td>
					<td>
						<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
						<br>
						<div class="ellipsis mt5"><?= $client ?></div>
					</td>
					<td class="text-right"><span title="<?= num_format($prognoz) ?>"><?= num_format($prognoz) ?></span></td>
					<td class="text-right">
						<span title="<?= num_format($data['kol']) ?>"><?= num_format($data['kol']) ?></span></td>
					<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
				</tr>
				<?php
			}
			?>
			<tr class="redbg toggler th35">
				<td colspan="7"><b>Закрытые <?= $lang['face']['DealsName'][0] ?></b></td>
			</tr>
			<?php
			$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE did > 0 and COALESCE(close, 'no') = 'yes' and clid = '$clid' $sort and identity = '$identity'");
			foreach ($result as $data) {

				$i++;

				if ($data['clid'] > 0)
					$client = '<div onclick="openClient(\''.$data['clid'].'\')" title="Карточка: '.current_client($data['clid']).'" class="hand gray blue fs-09"><i class="icon-building broun"></i>'.current_client($data['clid']).'</div>';

				elseif ($data['pid'] > 0)
					$client = '<div onclick="openPerson(\''.$data['pid'].'\')" title="Карточка: '.current_person($data['pid']).'" class="hand gray blue fs-09"><i class="icon-user-1 blue"></i>'.current_person($data['pid']).'</div>';

				$summa         += $data['kol_fact'];
				$prognoz       = $data['kol_fact'];
				$prognoz_total += $prognoz;
				?>
				<tr class="ha bgwhite th35">
					<td class="text-center"><?= $i ?></td>
					<td class="text-center"><b><?= current_dogstepname($data['idcategory']) ?></b>%</td>
					<td class="text-center"><?= format_date_rus($data['datum_plan']) ?></td>
					<td>
						<span class="ellipsis" title="<?= $data['title'] ?>"><a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Карточка: <?= current_dogovor($data['did']) ?>"><i class="icon-briefcase blue"></i><?= current_dogovor($data['did']) ?></a></span>
						<br>
						<div class="ellipsis mt5"><?= $client ?></div>
					</td>
					<td class="text-right"><span title="<?= num_format($prognoz) ?>"><?= num_format($prognoz) ?></span></td>
					<td class="text-right">
						<span title="<?= num_format($data['kol_fact']) ?>"><?= num_format($data['kol_fact']) ?></span>
					</td>
					<td><span class="ellipsis"><?= current_user($data['iduser']) ?></span></td>
				</tr>
				<?php
			}
			?>
			</tbody>
			<tfoot class="sticked--bottom">
			<tr class="itog th35">
				<td></td>
				<td class="text-right"><b>Итого:</b></td>
				<td class="text-right"><b><?= $i ?></b></td>
				<td></td>
				<td class="text-right">
					<span title="<?= num_format($prognoz_total) ?>"><b><?= num_format($prognoz_total) ?></b></span></td>
				<td class="text-right"><span title="<?= num_format($summa) ?>"><b><?= num_format($summa) ?></b></span></td>
				<td></td>
			</tr>
			</tfoot>
		</table>

	</div>
	<?php
}
?>

<script>

	if (!isMobile) {

		let hh = $('#dialog_container').actual('height') * 0.85;
		let hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

		if ($(window).width() > 990) $('#dialog').css({'width': '900px'});
		else if ($(window).width() > 1200) $('#dialog').css({'width': '950px'});
		else $('#dialog').css('width', '90vw');

		$('#formtabs').css('max-height', hh2);

	}
	else {

		let h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

	}

	$(document).ready(function () {

		$('#dialog').center();

		if (isMobile) $('#dialog').find('table').rtResponsiveTables({id: 'table-<?=$action?>'});

	});

</script>