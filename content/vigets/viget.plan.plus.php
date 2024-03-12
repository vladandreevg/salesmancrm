<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
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

$action = $_REQUEST['action'];

$Width = $_COOKIE['width'];

$tipPlan = $_COOKIE['planTipRukov'];

if (is_between($Width, 1025, 1200)) {
	$Width = 160;
}
elseif (is_between($Width, 1200, 1366)) {
	$Width = 130;
}
elseif (is_between($Width, 900, 1200)) {
	$Width = 140;
}
else {
	$Width = 160;
}

if (stripos(texttosmall($tipuser), 'руководитель') === false) {

	print '<div class="red p10">Упс, виджет только для руководителей</div>';
	exit();

}

$y  = date('Y');
$m  = date('m');
$nd = date('d');

$dd = date("t", mktime(date('H'), date('i'), date('s'), $m, 1, $y) + $tzone * 3600); //кол-во дней в текущем месяце

$dm_start = $y."-".$m."-01";
$dm_end   = $y."-".$m."-".$dd;

if ( $otherSettings[ 'planByClosed'])
	$dsort = " and COALESCE(close, 'no') = 'yes'";

if ($action == 'planView') {

	$summa = $summa_marg = 0;
	?>
	<div class="zagolovok">Данные по запросу</div>
	<?php
	$i = 0;
	//если выполнение считаем только по закрытым сделкам
	if (!$otherSettings[ 'credit']) {
		?>
		<div id="formtabse" style="overflow: auto;">

			<table id="zebra">
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
				<?php
				$q = "
				SELECT
					*
				FROM
					{$sqlname}dogovor
				WHERE
					{$sqlname}dogovor.did > 0 and
					DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%m') = '".date('Y')."-".date('m')."' and
					{$sqlname}dogovor.kol_fact > 0 and
					COALESCE({$sqlname}dogovor.close, 'no') = 'yes' and 
					{$sqlname}dogovor.iduser = '".$iduser1."' and
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
							<div class="ellipsis mt5"><?= $client ?></div>
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
				<tfoot class="sticked--bottom">
				<tr class="itog th35">
					<td></td>
					<td class="text-right"><b>Итого:</b></td>
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
	}
	//если включена рассрочка, то считаем по оплатам
	if ( $otherSettings[ 'credit']) {
		?>
		<div id="formtabse" style="overflow: auto;">

			<table id="zebra">
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
				<?php

				//если расчет планов по оплатам
				if (!$otherSettings[ 'planByClosed']) $q = "
					SELECT *
					FROM {$sqlname}credit
					WHERE
						{$sqlname}credit.do = 'on' and
						DATE_FORMAT({$sqlname}credit.invoice_date, '%Y-%m') = '".date('Y')."-".date('m')."' and
						{$sqlname}credit.did IN (SELECT {$sqlname}dogovor.did FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.did > 0 and {$sqlname}dogovor.iduser = '".$iduser1."') and
						{$sqlname}credit.identity = '$identity'
					ORDER by {$sqlname}credit.invoice_date DESC";

				//расчет планов по закрытым сделкам
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
						{$sqlname}credit.iduser = '$iduser1' and 
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

	?>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - 70;

		if ($(window).width() > 990) $('#dialog').css({'width': '800px'});
		if ($(window).width() > 1200) $('#dialog').css({'width': '950px'});
		else $('#dialog').css('width', '90vw');

		$('#formtabse').css('max-height', hh2);

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php

	exit();

}

$kol_plan   = 0;
$marga_plan = 0;

$color[0] = array(
	"#E74C3C",
	"#F1C40F",
	"#1ABC9C"
); //цвета первого круга - красный-желтый-зеленый
$color[1] = array(
	"#C0392B",
	"#F1C40F",
	"#16A085"
); //цвета второго круга - красный-желтый-зеленый

//План отдела
$res = $db -> getRow("SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM {$sqlname}plan WHERE mon = '$m' AND year = '$y' AND iduser = '$iduser1' AND identity = '$identity'");

$kolOtdel   = $res['kol'];
$margaOtdel = $res['marga'];


if ($tipPlan == 'public') {

	//Расчет плановых показателей для заданного пользователя(план по отделу)
	$kol_plan   = $kolOtdel;
	$marga_plan = $margaOtdel;

}
else {

	//Расчет плановых показателей руководителя(личный план)

	$podch = $db -> getRow("SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM {$sqlname}plan WHERE mon = '$m' AND year = '$y' AND iduser IN (SELECT iduser FROM {$sqlname}user WHERE mid = '$iduser1' AND secrty='yes') AND identity = '$identity'");

	$kol_plan   = ($kolOtdel > $podch['kol']) ? $kolOtdel - $podch['kol'] : $kolOtdel;
	$marga_plan = ($margaOtdel > $podch['marga']) ? $margaOtdel - $podch['marga'] : $margaOtdel;

}


//расчет плана на текущий день
$planOborot = round(($kol_plan / $dd) * $nd);
$planMarga  = round(($marga_plan / $dd) * $nd);

$kol = $marga = 0;

//Расчет выполнения плана по заданному пользователю
//если рассрочка не включена, то считаем закрытые сделки
if (!$otherSettings[ 'credit']) {

	$result = $db -> getRow("SELECT SUM(kol_fact) as kol, SUM(marga) as marga FROM {$sqlname}dogovor WHERE datum_close between '$dm_start 00:00:01' and '$dm_end 23:59:59' and COALESCE(close, 'no')='yes' and iduser = '$iduser1' and identity = '$identity'");
	$kol    = $result['kol'];
	$marga  = $result['marga'];

}

//если включена рассрочка, то считаем по оплатам
if ( $otherSettings[ 'credit']) {

	$query = '';

	if (!$otherSettings[ 'planByClosed'])
		$query = "SELECT * FROM {$sqlname}credit WHERE do='on' and invoice_date between '$dm_start' and '$dm_end' and did IN (SELECT did FROM {$sqlname}dogovor WHERE did > 0 and iduser = '$iduser1') and identity = '$identity' ORDER by did";

	else
		$query = "
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

	$result = $db -> getAll($query);
	foreach ($result as $data) {

		//расчет процента размера платежа от суммы сделки
		$kolp = $db -> getOne("SELECT kol FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'");

		$margap = $db -> getOne("SELECT marga FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity' ORDER by did");

		$dolya = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;//% оплаченной суммы от суммы по договору
		$m     = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;

		$kol   += pre_format($data['summa_credit']);
		$marga += $margap * $dolya;

	}

}

//$kol = 0.7 * $planOborot;
//$marga = $planMarga * 0.92;

//формируем цветовую схему
if ($kol > $planOborot * 0.9) {

	$color1 = $color[0][2];
	$color2 = $color[1][2];

	$class1 = "bggreen";

}
elseif ($kol < $planOborot * 0.7) {

	$color1 = $color[0][0];
	$color2 = $color[1][0];

	$class1 = "bgred";

}
else {

	$color1 = $color[0][1];
	$color2 = $color[1][1];

	$class1 = "bgyellow";

}

if ($marga > $planMarga * 0.9) {

	$color3 = $color[0][2];
	$color4 = $color[1][2];

	$class2 = "bggreen";

}
elseif ($marga < $planMarga * 0.7) {

	$color3 = $color[0][0];
	$color4 = $color[1][0];

	$class2 = "bgred";

}
else {

	$color3 = $color[0][1];
	$color4 = $color[1][1];

	$class2 = "bgyellow";

}

//Расчет процента выполнения
if ($kol_plan > 0) $proc_kol = num_format($kol / $kol_plan * 100);
else $proc_kol = 0;
if ($marga_plan > 0) $proc_marga = num_format($marga / $marga_plan * 100);
else $proc_marga = 0;

$kol   = num_format($kol);
$marga = num_format($marga);

$kol_plan   = num_format($kol_plan);
$marga_plan = num_format($marga_plan);
?>
<style>
	#outer {
		background    : #FFFFFF;
		border-radius : 5px;
		color         : #000;
	}

	#div1p, #div2p {
		box-sizing : border-box;
	}

	/*первый оборот*/
	#planplus .arc, #planplus .arc2 {
		stroke-weight : 0.1;
	}

	#planplus .bgred .arc {
		fill : #F06292;
	}

	#planplus .bgred .arc2 {
		fill : #AD1457;
	}

	#planplus .bgyellow .arc {
		fill : #FF8A65;
	}

	#planplus .bgyellow .arc2 {
		fill : #FF8A65;
	}

	#planplus .bggreen .arc {
		fill : #81C784;
	}

	#planplus .bggreen .arc2 {
		fill : #388E3C;
	}

	.radial {
		border-radius : 3px;
		background    : #FFFFFF;
		color         : #000;

	}

	.background {
		fill         : #FFFFFF;
		fill-opacity : 0.01;
	}

	.component {
		fill : #e1e1e1;
	}

	.component .label {
		text-anchor : middle;
		fill        : #000000;
		display     : none;
	}

	.label {
		text-anchor : middle;
	}

	.radial-svg {
		display : block;
		margin  : 0 auto;
		height  : 140px;
	}

	#planplus .afoot{
		display: grid;
		grid-template-columns: 1fr 1fr;
	}
</style>

<div id="chartdiv" style="position: relative;">

	<div class="flex-container box--child nopad" style="flex-wrap: nowrap !important;">

		<div class="flex-string6 table nopad div-center wp50">

			<div class="mb5 text-center no--overflow">

				<div id="div1p" class="<?= $class1 ?> text-center"></div>

			</div>

		</div>

		<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
			<div class="flex-string6 table nopad div-center wp50">

				<div class="mb5 text-center no--overflow">

					<div id="div2p" class="<?= $class2 ?> text-center"></div>

				</div>

			</div>
		<?php } ?>

	</div>

</div>
<div class="afoot">

	<div class="oborot">

		<div class="mb10 div-center">

			<a href="javascript:void(0)" onclick="showPlanPlus()" title="Текущее выполнение. Показать данные" class="list"><b class="bigtxt" style="color:<?= $color2 ?>"><?= num_format($kol) ?>&nbsp;<?= $valuta ?></b></a>

		</div>
		<div class="table div-center">

			<div class="tooltips p3 wp100 text-center" tooltip="<blue>Расчетный план продаж на текущий день</blue><hr>(План продаж в день * Кол-во прошедших дней месяца)" tooltip-position="top">

				<div class="inline wp25 border-box mwp100 nopad pl5 gray2 text-right"><?= $fieldsNames['dogovor']['oborot'] ?>&nbsp;</div>
				<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
					<b class="underline broun"><?= num_format($planOborot) ?></b>&nbsp;<?= $valuta ?> ( Р )
				</div>

			</div>

			<div title="План на месяц" class="p3 wp100 text-center">

				<div class="inline wp25 border-box mwp100 nopad pl5 text-right"></div>
				<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
					<b><?= $kol_plan ?></b>&nbsp;<?= $valuta ?>&nbsp;( П )
				</div>

			</div>

		</div>

	</div>
	<div class="marga">

		<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']) { ?>
			<div class="mb10 div-center">

				<a href="javascript:void(0)" onclick="showPlan()" title="Текущее выполнение. Показать данные" class="list"><b class="bigtxt" style="color:<?= $color4 ?>"><?= num_format($marga) ?>&nbsp;<?= $valuta ?></b></a>

			</div>
			<div class="table div-center">

				<div class="tooltips p3 text-center" tooltip="<blue>Расчетный план продаж на текущий день</blue><hr>(План продаж в день * Кол-во прошедших дней месяца)" tooltip-position="top">

					<div class="inline wp25 border-box mwp100 nopad pl5 gray2 text-right"><?= $fieldsNames['dogovor']['marg'] ?>&nbsp;</div>
					<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
						<b class="underline broun"><?= num_format($planMarga) ?></b>&nbsp;<?= $valuta ?>&nbsp;( Р )
					</div>

				</div>

				<div title="План на месяц" class="p3 text-center">

					<div class="inline wp25 border-box mwp100 nopad pl5 text-right"></div>
					<div class="inline wp70 border-box mwp100 nopad pl5 text-right">
						<b><?= $marga_plan ?></b>&nbsp;<?= $valuta ?>&nbsp;( П )
					</div>

				</div>

			</div>
		<?php } ?>

	</div>

</div>

<script src="/assets/js/radialProgress.js"></script>
<script>

	/*tooltips*/
	$('#planplus').closest('.viget').find('.tooltips').append("<span></span>");
	$('#planplus').closest('.viget').find('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
	$('#planplus').closest('.viget').find('.tooltips').mouseenter(function () {
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/

	var div1p = d3.select(document.getElementById('div1p'));
	<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']){?>
	var div2p = d3.select(document.getElementById('div2p'));
	<?php } ?>

	startplus();

	function showPlanPlus() {
		doLoad('/content/vigets/viget.plan.plus.php?action=planView');
	}

	function deselect() {
		div1p.attr("class", "radial");
		<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']){?>
		div2p.attr("class", "radial");
		<?php } ?>
	}

	function startplus() {

		var diameter = $('#planplus').find('.flex-container').width() / 2 - 5;

		if (diameter >= 160) {

			diameter = 160;
			//$('.radial-svg').css({'width': diameter + 'px', 'height': diameter + 'px'});

		}

		else if (diameter < 160) {

			diameter = diameter - 5;
			//$('.radial-svg').css({'width': diameter + 'px', 'height': diameter + 'px'});

		}

		var rp1 = radialProgress(document.getElementById('div1p'))
			.label("<?=$kol_plan?>")
			.onClick(showPlanPlus)
			.diameter(diameter)
			.value(<?=$proc_kol?>)
			.render();

		<?php if ($show_marga == 'yes' && $otherSettings[ 'marga']){?>
		var rp2 = radialProgress(document.getElementById('div2p'))
			.label("<?=$marga_plan?>")
			.onClick(showPlanPlus)
			.diameter(diameter)
			.value(<?=$proc_marga?>)
			.render();
		<?php }?>
	}

	function changeTipPlan() {

		var current = getCookie('planTipRukov');
		var newparam = (current === 'public') ? 'private' : 'public';

		setCookie('planTipRukov', newparam, {expires: 31536000});

		$("#planplus").load("/content/vigets/viget.plan.plus.php").append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

	}

	$(window).bind('resizeEnd', function () {

		drowVoronkaChart();

	});

</script>
