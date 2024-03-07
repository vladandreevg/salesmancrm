<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Akt;
use Salesman\Speka;

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

$action = untag($_REQUEST['action']);
$did    = (int)$_REQUEST['did'];

//$isCatalog = $GLOBALS['isCatalog'];

global $isCatalog;

//print $isCatalog;

$result     = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
$calculate  = $result["calculate"];
$idcategory = (int)$result["idcategory"];
$close      = $result["close"];
$mcid       = (int)$result["mcid"];

$rassrochka = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");

$dogstatus = $db -> getOne("SELECT title FROM {$sqlname}dogcategory WHERE idcategory = '$idcategory' and identity = '$identity'");

$speka = (new Speka()) -> getSpekaData($did);
$num = count($speka['pozition']);

//$result_sp = $db -> query("SELECT * FROM {$sqlname}speca WHERE did = '$did' AND tip != '2' and identity = '$identity' ORDER BY spid");
//$num       = $db -> affectedRows($result_sp);

$nalogScheme = getNalogScheme(0, (int)$mcid);

$dallow = 0;

if ($close != 'yes') {

	if (($dogstatus < 80) && ($rassrochka > 0)) {
		$dallow = 1;
	}
	elseif (($dogstatus <= 80)) {
		$dallow = 2;
	}
	elseif ($rassrochka == 0) {
		$dallow = 3;
	}

}
if ($rassrochka > 0) {
	++$dallow;
}

$daccesse = get_accesse(0, 0, $did );

$message = [];

if ($close == 'yes') {
	$message[] = 'Сделка закрыта. Составление спецификации <b>не целесообразно</b>!';
}
if ($dallow == 1) {
	$message[] = 'Изменение спецификации <b>не целесообразно</b>, т.к. составлен график оплаты';
}
if ($dallow == 3) {
	$message[] = 'Статус договора более 80%. Составление спецификации <b>не целесообразно</b>!';
}

if ($calculate != 'yes' /*&& $close != 'yes'*/) {

	print '
	<div class="p10 m5">
		Расчет по спецификациям не включен в параметрах сделки. 
		'.($close != 'yes' ? '<a href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите включить расчет по спецификации?\');if (cf)editSpeca(\'\',\'change.calculate\',\''.$did.'\');" title="Включить" class="button mt10">Включить?</a>' : '').'
	</div>
	';

}

if ($calculate == 'yes') {

	$complect = round(Akt::getAktComplect($did), 0);

	if($complect > 0) {
		$message[] = "<b>Актами закрыто $complect% позиций спецификации</b>";
	}

	print '
	<DIV class="batton-edit mb20">
	
		<span class="hidden-iphone">
			<A href="javascript:void(0)" onClick="editSpeca(\'\',\'export\',\''.$did.'\');"><i class="icon-upload broun"></i>Экспорт</A>
			'.($close != 'yes' && ($daccesse == 'yes' || $isadmin == 'on') ? '&nbsp;&nbsp;<A href="javascript:void(0)" onClick="editSpeca(\'\',\'import\',\''.$did.'\');"><i class="icon-download-1 broun"></i>Импорт</A>&nbsp;&nbsp;' : '').'
		</span>
		'.($close != 'yes' && ($daccesse == 'yes' || $isadmin == 'on') ? '<A href="javascript:void(0)" onClick="editSpeca(\'\',\'add\',\''.$did.'\');"><i class="icon-plus-circled green"></i>Добавить</A>' : '').'
		
	</DIV>
	';

	if (!empty($message)) {
		print '
		<div class="attention mt10 mb15">
			<div><i class="icon-attention broun icon-1x dleft"></i></div>
			'.yimplode( "<br>", $message ).'
		</div>
		';
	}
	?>

	<div style="overflow-y:auto; max-height: 70vh;">

		<table class="bgwhite top" id="spekaTable">
			<thead class="sticked--top">
			<tr class="header_contaner">
				<th class="w10 text-center">№ п.п.</th>
				<th class="text-center">Номенклатура</th>
				<th class="w30 text-center">Ед.изм.</th>
				<th class="w80 text-center">Кол-во</th>
				<?php if ( $otherSettings[ 'dop']) { ?>
				<th class="w40 text-left">
					<div class="ellipsis" title="<?= $otherSettings[ 'dopName'] ?>"><?= $otherSettings[ 'dopName'] ?></div>
				</th>
				<?php } ?>
				<th class="w100 text-right">Цена <br>за ед., <?= $valuta ?></th>
				<th class="w120 text-right">Цена <br>итого, <?= $valuta ?></th>
				<th class="w60">&nbsp;</th>
			</tr>
			</thead>
			<?php
			$i            = 1;
			$err          = 0;
			$num          = 0;
			$ndsTotal     = 0;
			$summaInTotal = 0;
			$summaTotal   = 0;
			$summa        = 0;

			foreach ($speka['pozition'] as $da){
			//while ($da = $db -> fetch($result_sp)) {

				$delta = 0;
				$all   = 0;
				$s     = '';
				$msg = '';

				if ((int)$da['tip'] == 0) {
					$tip = 'Товар';
				}
				elseif ((int)$da['tip'] == 1) {
					$tip = 'Услуга';
				}
				elseif ((int)$da['tip'] == 2) {
					$tip = 'Материал';
				}

				if ((int)$da['prid'] > 0) {
					$s = " and n_id = '".$da['prid']."'";
				}
				elseif ($da['artikul'] != '' && $da['artikul'] != 'undefined') {
					$s = " and artikul = '".$da['artikul']."'";
				}

				$pia = (float)$db -> getOne("SELECT price_in FROM {$sqlname}price WHERE n_id > 0 $s and identity = '$identity'");

				$summaTotal   += (float)$da[ 'summa' ];
				$summaInTotal += (float)$da[ 'summaZakup' ];
				$ndsTotal     += (float)$da[ 'nds' ];

				if ((int)$da['prid'] > 0) {

					if ($pia == 0) {
						$msg = '<i class="icon-attention red list fs-07" title="Позиция удалена из прайса"></i>';
					}

					if ($da['price_in'] != $pia) {

						$delta = pre_format($pia) - pre_format($da['price_in']);

						$msg = '<i class="icon-attention red list fs-07" title="Закупочная цена по прайсу отличается на '.($delta < 0 ? "" : "+").num_format($delta).' '.$valuta.'"></i>';

					}
					else {
						$msg = '<i class="icon-ok green list fs-07" title="Закуп в порядке"></i>';
					}

				}
				else {
					$msg = '<i class="icon-help-circled broun fs-07" title="Не прайсовая позиция"></i>';
				}

				$da['artikul'] = ($da['artikul'] != '' && $da['artikul'] != 'undefined') ? $da['artikul'].":" : '???:';
				$a = $da['title'];

				if((int)$da['prid'] > 0){

					if ($GLOBALS['isCatalog'] == 'on') {
						$a = '<A href="javascript:void(0)" onclick="doLoad(\'/modules/modcatalog/form.modcatalog.php?action=view&n_id='.$da['prid'].'\');">'.$da['title'].'</A>';
					}
					else {
						$a = '<A href="javascript:void(0)" onclick="editPrice(\''.$da['prid'].'\',\'view\');">'.$da['title'].'</A>';
					}

				}

				// поищем позиции в актах
				$deid = $db -> getCol("SELECT DISTINCT(deid) FROM {$sqlname}contract_poz WHERE did = '$did' AND spid = '$da[spid]'");
				$akts = (!empty($deid)) ? yimplode(", ", $db -> getCol("SELECT number FROM {$sqlname}contract WHERE deid IN (".yimplode(",", $deid).")")) : "";

				print '
				<tr class="ha">
					<td class="text-center" class="hidden-iphone"><span>'.$i.'</span></td>
					<td>
						<span>
							<div class="Bold uppercase fs-07 mb5">'.$da['artikul'].' <span class="deepblue">'.$tip.'</span></div>
							<div class="Bold fs-11">'.$a.($show_marga == 'yes' && $otherSettings[ 'marga'] ? $msg : '').'</div>
							'.($da['comments'] != '' ? '<div class="smalltxt blue"><em>'.$da['comments'].'</em></div>' : '').'
							'.($akts != '' ? '<div class="Bold deepblue fs-09">В актах №: '.$akts.'</div>' : '').'
						</span>
					</td>
					<td class="text-center"><span>'.$da['edizm'].'</span></td>
					<td class="text-right"><span>'.num_format($da['kol']).'</span></td>
					'.( $otherSettings[ 'dop'] ? '<td class="text-right"><span>'.num_format( $da[ 'dop']).'</span></td>' : '').'
					<td class="text-right">
						<span>
							<b>'.num_format($da['price']).'</b>
							'.($show_marga == 'yes' && $otherSettings[ 'marga'] ? '<br>
							<span class="gray smalltxt" title="Себестоимость"><i>'.num_format($da['price_in']).'</i></span>' : '').'
						</span>
					</td>
					<td class="text-right">
						<span>
							<b>'.num_format($da['summa']).'</b>
							'.($show_marga == 'yes' && $otherSettings[ 'marga'] ? '<br><span class="gray smalltxt" title="Себестоимость"><i>'.num_format( $da[ 'summaZakup']).'</i>
							</span>' : '').'
						</span>
					</td>
					<td class="text-center">
						<div class="mob-pull-right">
	
						'.(($dallow == 2 || $isadmin == 'on') && ($daccesse == 'yes' || $isadmin == 'on') ? '<a href="javascript:void(0)" onclick="editSpeca(\''.$da['spid'].'\',\'edit\',\''.$da['did'].'\');" title="Редактировать"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editSpeca(\''.$da['spid'].'\',\'delete\',\''.$da['did'].'\');" title="Удалить позицию"><i class="icon-cancel-circled red"></i></a>' : '').'
	
						</div>
					</td>
				</tr>';

				$i++;
				$kol_sum = 0;
				$num+= $da['kol'];

			}

			$marga = $summaTotal - $summaInTotal;

			if ($speka['summaItog'] > 0) {
			?>
			<tfoot class="graybg-sub sticked--bottom">
			<tr>
				<td class="text-center hidden-iphone"></td>
				<td class="text-right"><span class="Bold">ИТОГО:</span></td>
				<td class="text-center"></td>
				<td class="text-right"><span class="Bold"><?= num_format($num) ?></span></td>
				<td class="text-center"></td>
				<?php if ( $otherSettings[ 'dop']) { ?>
					<td class="text-right"></td>
				<?php } ?>
				<td class="text-right">
					<div class="Bold"><?= num_format($summaTotal) ?></div>
					<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
						<div><i class="gray smalltxt"><?= num_format($summaInTotal) ?></i></div>
					<?php } ?>
				</td>
				<td class="text-right">&nbsp;</td>
			</tr>
			</tfoot>
		<?php } ?>
		</table>
		<?php
		if (empty($speka['pozition']))
			print '<div class="fcontainer mp10 mt10">Позиции спецификации отсутствуют</div>';

		?>

	</div>

	<!--</div>-->

	<?php
	if ($summaTotal > 0) {

		if ($ndsRaschet == 'yes' && $nalogScheme['nalog'] > 0)
			$s = ' (без учета налога)';
		elseif ($nalogScheme['nalog'] == 0)
			$s = ' (налогом не облагается)';
		else
			$s = ' (с учетом налога)';

		?>
		<hr>
		<div class="infodiv">
			<b class="blue">Итоговая информация:</b><br>
			<ul class="simple paddleft10">
				<li>Оборот по счету: <b><?= num_format($summaTotal) ?></b> <?= $valuta ?> <?= $s ?></li>
				<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
					<li>Прибыль по счету: <b><?= num_format($marga) ?></b> <?= $valuta ?></li>
					<li>Прибыльность: <b><?= num_format($marga / $summaTotal * 100) ?></b> %</li>
				<?php } ?>
				<li>Сумма налога (НДС): <b><?= num_format($speka['summaNalog']) ?></b> <?= $valuta ?>
					<i class="icon-info-circled blue" title="Расчет произведен в соответствии с настройками компании, от которой ведется продажа"></i>
				</li>
			</ul>
		</div>
		<?php
	}

}

$result     = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did='".$did."' and identity = '$identity'");
$calculate  = $result["calculate"];
$idcategory = (int)$result["idcategory"];
$close      = $result["close"];
$mcid       = (int)$result["mcid"];

$rassrochka = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}credit WHERE did='".$did."' and identity = '$identity'");

$dogstatus = $db -> getOne("SELECT title FROM {$sqlname}dogcategory WHERE idcategory='".$idcategory."' and identity = '$identity'");


//$result_mat = $db -> query("SELECT * FROM {$sqlname}speca WHERE did='".$did."' AND tip='2' and identity = '$identity' ORDER BY spid");
//$countSp   = $db -> affectedRows($result_mat);
$countSp = count((array)$speka['material']);

$nalogScheme = getNalogScheme(0, $mcid );

$dallow = 0;

if ($close != 'yes') {

	if (($dogstatus < 80) && ($rassrochka > 0))
		$dallow = 1;
	elseif ($dogstatus <= 80)
		$dallow = 2;
	elseif ($rassrochka == 0)
		$dallow = 3;

}

if ($rassrochka > 0) {
	$dallow++;
}

$daccesse = get_accesse(0, 0, $did );

$message = [];

if ($close == 'yes') $message[] = 'Сделка закрыта. Составление спецификации <b>не целесообразно</b>!';
if ($dallow == 1) $message[] = 'Изменение спецификации <b>не целесообразно</b>, т.к. составлен график оплаты';
if ($dallow == 3) $message[] = 'Статус договора более 80%. Составление спецификации <b>не целесообразно</b>!';

if ($GLOBALS['isCatalog'] == 'on' && $calculate == 'yes' && $countSp > 0) {
	?>

	<div class="divider mt20 mb10">Материалы</div>

	<div style="overflow-y:auto; max-height: 70vh;">

		<table class="bgwhite" id="spekaTable">
			<thead class="sticked--top">
			<tr class="header_contaner">
				<th class="w10 text-center">№ п.п.</th>
				<th class="text-center">Номенклатура</th>
				<th class="w30 text-center">Ед.изм.</th>
				<th class="w60 text-center">Кол-во</th>
				<?php if ( $otherSettings[ 'dop']) { ?>
					<th class="w40 text-left">
						<div class="ellipsis" title="<?= $otherSettings[ 'dopName'] ?>"><?= $otherSettings[ 'dopName'] ?></div>
					</th>
				<?php } ?>
				<th class="w100 text-right">Цена <br>за ед., <?= $valuta ?></th>
				<th class="w100 text-right">Цена <br>итого, <?= $valuta ?></th>
				<th class="w60">&nbsp;</th>
			</tr>
			</thead>
			<?php
			$i            = 1;
			$err          = 0;
			$num          = 0;
			$ndsTotal     = 0;
			$summaInTotal = 0;
			$summaTotal   = 0;
			$summa        = 0;

			foreach ($speka['material'] as $da){
			//while ($da = $db -> fetch($result_mat)) {

				$delta = 0;
				$pia   = 0;
				$all   = 0;
				$s     = '';
				$msg = '';

				$summaInTotal += $da['summaZakup'];
				$summaTotal   += $da['summa'];

				$da['artikul'] = ($da['artikul'] != '' && $da['artikul'] != 'undefined') ? $da['artikul']."" : '???';

				if ($da['prid'] > 0) {
					$a = '<A href="javascript:void(0)" onClick="doLoad(\'modules/modcatalog/form.modcatalog.php?action=view&n_id='.$da['prid'].'\');">'.$da['title'].'</A>';
				}
				else {
					$a = $da['title'];
				}

				if ((int)$da['prid'] > 0) {
					$s = " and n_id = '".$da['prid']."'";
				}
				elseif ($da['artikul'] != '' && $da['artikul'] != 'undefined') {
					$s = " and artikul = '".$da['artikul']."'";
				}

				$pia = $db -> getOne("SELECT price_in FROM {$sqlname}price WHERE n_id > 0 $s and identity = '$identity'");

				if ((int)$da['prid'] > 0) {

					if ($all == 0) {
						$msg = '<i class="icon-attention red list fs-07" title="Позиция удалена из прайса"></i>';
					}

					if ($da['price_in'] != $pia) {

						$delta = pre_format($pia) - pre_format($da['price_in']);

						$msg = '<i class="icon-attention red list fs-07" title="Закупочная цена по прайсу отличается на '.($delta < 0 ? "" : "+").num_format($delta).' '.$valuta.'"></i>';

					}
					else {
						$msg = '<i class="icon-ok green list fs-07" title="Закуп в порядке"></i>';
					}

				}
				else {
					$msg = '<i class="icon-help-circled broun fs-07" title="Не прайсовая позиция"></i>';
				}

				print
					'<tr class="ha">
						<td class="text-center" class="hidden-iphone"><span>'.$i.'</span></td>
						<td>
						<span>
							<div class="Bold fs-07">'.$da['artikul'].'</div>
							<div class="Bold fs-11">'.$a.''.($show_marga == 'yes' && $otherSettings[ 'marga'] ? $msg : '').'</div>
							'.($da['comments'] != '' ? '<div class="smalltxt blue"><em>'.$da['comments'].'</em></div>' : '').'
						</span>
						</td>
						<td class="text-center"><span>'.$da['edizm'].'</span></td>
						<td class="text-right"><span>'.num_format($da['kol']).'</span></td>
						'.( $otherSettings[ 'dop'] ? '<td class="text-right"><span>'.num_format( $da[ 'dop']).'</span></td>' : '').'
						<td class="text-right">
							<span>
								<b>'.num_format($da['price']).'</b>
								'.($show_marga == 'yes' && $otherSettings[ 'marga'] ? '<br><span class="gray smalltxt" title="Себестоимость"><i>'.num_format( $da[ 'price_in']).'</i></span>' : '').'
							</span>
						</td>
						<td class="text-right">
							<span>
								<b>'.num_format($da['summa']).'</b>
								'.($show_marga == 'yes' && $otherSettings[ 'marga'] ? '<br><span class="gray smalltxt" title="Себестоимость"><i>'.num_format( $da[ 'summaZakup']).'</i>
								</span>' : '').'
							</span>
						</td>
						<td class="text-center">
							<div class="mob-pull-right">
		
							'.(($dallow == 2 || $isadmin == 'on') && ($daccesse == 'yes' || $isadmin == 'on') ? '<a href="javascript:void(0)" onclick="editSpeca(\''.$da['spid'].'\',\'edit\',\''.$da['did'].'\');" title="Редактировать"><i class="icon-pencil blue"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editSpeca(\''.$da['spid'].'\',\'delete\',\''.$da['did'].'\');" title="Удалить позицию"><i class="icon-cancel-circled red"></i></a>' : '').'
		
							</div>
						</td>
					</tr>';

				$i++;
				$kol_sum = 0;

			}

			$marga = $summaTotal - $summaInTotal;

			if ($summaTotal > 0) {
				?>
				<tfoot class="graybg-sub sticked--bottom">
				<tr>
					<td class="text-center hidden-iphone"></td>
					<td class="text-right"><span class="Bold">ИТОГО:</span></td>
					<td class="text-center"></td>
					<td class="text-right"><span class="Bold"><?= num_format($num) ?></span></td>
					<?php if ( $otherSettings[ 'dop']) { ?>
						<td class="text-right"></td>
					<?php } ?>
					<td class="text-right"></td>
					<td class="text-right">
						<div class="Bold"><?= num_format($summaTotal) ?></div>
						<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
							<div><i class="gray smalltxt"><?= num_format($summaInTotal) ?></i></div>
						<?php } ?>
					</td>
					<td class="text-right">&nbsp;</td>
				</tr>
				</tfoot>
			<?php } ?>
		</table>

	</div>

	<div class="infodiv mt5 fs-09 em">&nbsp; *Материалы не учитываются в сумме сделки, счетах и актах. Себестоимость материалов вычитается из Прибыли</div>

	<?php
}
?>
<script>
	if (isMobile) {

		$('#spekaTable').rtResponsiveTables();

	}
</script>
