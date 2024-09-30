<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

//отчет не закончен

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$act = $_REQUEST['act'];
$mc  = (int)$_REQUEST['mc'];

if (!$per) {
	$per = 'nedelya';
}

$period = $_REQUEST['period'];
$period = empty($period) ? getPeriod('month') : getPeriod($period);

$month = date('m');
$year  = date('Y');

// кол-во дней в месяце
$days = (int)date('t');

//$year  = 2023;
//$month = 12;
//$days  = 31;

$startMont = $month;

$nextMonth = $month < 12 ? $month + 1 : 1;
$nextYear  = $month < 12 ? $year : $year + 1;

$daysNext = (int)( date('t', mktime(1, 0, 0, $nextMonth, 1, $nextYear)) );

//printf("m: %s, y:%s, d: %s\n", $month, $year, $days);
//printf("mn: %s, yn:%s, dn: %s\n", $nextMonth, $nextYear, $daysNext);
//exit();

// массив, в котором будем хранить остатки по дням
$saldo = [];

// полное сальдо - остаток на всех счетах
$totalsaldo = $fullsaldo = 0;

// выбранный р.сч.
$selected = 'Не выбрано';

$da1 = ( $da1 != '' ) ? $da1 : $period[0];
$da2 = ( $da2 != '' ) ? $da2 : $period[1];

$dateStart = "$year-$month-01";
$dateEnd   = modifyDatetime($dateStart, ["modify" => "1 year", "format" => "Y-m-d"]);

//print $dateStart." : ".$dateEnd;
//exit();

$diffPeriod = abs(diffDate2($da1, $da2));

// статьи расхода
$outcome = $db -> getCol("SELECT id FROM {$sqlname}budjet_cat WHERE tip = 'rashod' and identity = '$identity' ORDER BY title");

// статьи дохода
$income = $db -> getCol("SELECT id FROM {$sqlname}budjet_cat WHERE tip = 'dohod' and identity = '$identity' ORDER BY title");

$company = $db -> getIndCol("id", "SELECT id, name_shot FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot");

$kassa = $ostatok = $bank = $ostatokCompany = [];

// если выбрана определенная компания
$sort = '';
if ($mc > 0) {
	$sort = "mc.id = '$mc' AND ";
}

// список остатков по расчетным счетам
$rs = $db -> query("
	SELECT 
		rs.id,
		rs.cid,
		rs.title as rs,
		rs.ostatok,
		mc.id as mcid,
		mc.name_shot as company
	FROM {$sqlname}mycomps_recv `rs`
		LEFT JOIN {$sqlname}mycomps `mc` ON mc.id = rs.cid
	WHERE 
		-- $sort
		COALESCE(rs.bloc, 'no') != 'yes' AND
		rs.identity = '$identity' 
	ORDER BY rs.title
");
while ($da = $db -> fetch($rs)) {

	$bank[(int)$da['id']] = [
		"company" => $da['company'],
		"bank"    => $da['rs'],
		"summa"   => (float)$da['ostatok']
	];

	// начальное сальдо по р/сч.
	$saldo[(int)$da['id']] = (float)$da['ostatok'];

	if ( $mc == (int)$da['mcid'] ) {
		$totalsaldo += (float)$da['ostatok'];
	}

	$fullsaldo += (float)$da['ostatok'];

	//остатки по счетам
	$ostatok[$da['id']] = (float)$da['ostatok'];

	//остатки по счетам
	$ostatokCompany[$da['cid']] += (float)$da['ostatok'];

	//массив счетов
	$kassa[$da['id']] = $da['company'];

	if ($da['cid'] == $mc) {
		$selected = $company[$da['cid']];
	}

}

//print_r($saldo);

$xtotalsaldo = $sumSaldo = $totalsaldo;

if( $mc == 0 ){
	$xtotalsaldo = $sumSaldo = $fullsaldo;
}

// остаток средств на счете с учетом расходов и приходов
$total = [];

// строки для вывода
$list = [];

$xsort = '';
if ($mc > 0) {
	$xsort = "rs.cid = '$mc' AND ";
}

// выставленные счета. добавляем
$res = $db -> getAll("
SELECT 
	cr.crid,
	cr.clid,
	cr.did,
	cr.invoice,
	cr.datum,
	cr.datum_credit as dplan,
	cr.invoice_date,
	cr.summa_credit as summa,
	cc.title as client,
	deal.title as deal,
	rs.cid as mcid,
	rs.id as rsid,
	rs.title as rs
FROM {$sqlname}credit `cr`
	LEFT JOIN {$sqlname}mycomps_recv `rs` ON rs.id = cr.rs
	LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
	LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = cr.clid
WHERE 
	DATE_FORMAT(cr.datum_credit, '%Y-%m-%d') >= '$dateStart' AND DATE_FORMAT(cr.datum_credit, '%Y-%m-%d') <= '$dateEnd' AND 
	COALESCE(cr.do, 'no') != 'on' AND 
	$xsort
	cr.identity = '$identity'
ORDER BY cr.datum_credit
");
//print $db -> lastQuery();
foreach ($res as $item) {

	$summa += (float)$item['summa'];

	$totalsaldo                  += (float)$item['summa'];
	$saldo[$item['dplan']] = $totalsaldo;

	$list[$item['dplan']][] = [
		"crid"         => (int)$item['crid'],
		"clid"         => (int)$item['clid'],
		"did"          => (int)$item['did'],
		"year"         => get_year($item['dplan']),
		"type"         => "Оплата клиента",
		"xtype"        => "Приход",
		"color"        => "green",
		"contragentid" => (int)$item['clid'],
		"contragent"   => $item['client'],
		"document"     => sprintf("Счет №%s<br><span class='gray fs-09'>от %s</span>", $item['invoice'], modifyDatetime($item['datum'], ["format" => "d.m.Y"])),
		"recv"         => 'Оплата счета',
		"deal"         => $item['deal'],
		"summa"        => NULL,
		"rsid"         => (int)$item['rsid'],
		"rs"           => $item['rs'],
		"mcid"         => (int)$item['mcid'],
		"company"      => $company[$item['mcid']],
		"dplan"        => $item['dplan'],
		"dz"           => (float)$item['summa'],
		"saldo"        => $totalsaldo,
		"xsumma"       => (float)$item['summa'],
		"direction"    => "plus"
	];

	$total[$item['dplan']] += (float)$item['summa'];

}

// приходы/расходы из журнала
$res = $db -> getAll("
SELECT 
	bj.id,
	bj.summa,
	bj.cat,
	bj.title,
	bj.des,
	bj.date_plan as dplan,
	bj.invoice,
	bj.invoice_date,
	bj.conid,
	bj.partid,
	bc.title as cattitle,
	bc.tip as cattip,
	rs.cid as mcid,
	rs.id as rsid,
	rs.title as rs,
	deal.clid,
	deal.did,
	deal.title as deal
FROM {$sqlname}budjet `bj`
	LEFT JOIN {$sqlname}mycomps_recv `rs` ON rs.id = bj.rs
	LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = bj.did
	LEFT JOIN {$sqlname}budjet_cat `bc` ON bj.cat = bc.id
WHERE 
	DATE_FORMAT(COALESCE(bj.date_plan, ''), '%Y-%c-%d') >= '$dateStart' AND DATE_FORMAT(bj.date_plan, '%Y-%c-%d') <= '$dateEnd' AND 
	bj.cat IN(".yimplode(",", array_merge($income, $outcome)).") AND 
	COALESCE(bj.do, 'no') != 'on' AND 
	$xsort
	bj.identity = '$identity'
");
foreach ($res as $item) {

	if ($item['cattip'] == 'dohod') {
		$summa      += (float)$item['summa'];
		$totalsaldo += (float)$item['summa'];
		$total[$item['dplan']] += (float)$item['summa'];
	}
	else {
		$summa      -= (float)$item['summa'];
		$totalsaldo -= (float)$item['summa'];
		$total[$item['dplan']] -= (float)$item['summa'];
	}

	$saldo[$item['dplan']] = $totalsaldo;

	$list[$item['dplan']][] = [
		"id"           => (int)$item['id'],
		"did"          => (int)$item['did'],
		"clid"         => (int)$item['clid'],
		"year"         => get_year($item['dplan']),
		"type"         => $item['cattitle'],
		"xtype"        => $item['cattip'] == 'dohod' ? "Приход" : "Расход",
		"color"        => $item['cattip'] == 'dohod' ? "green" : "red",
		"contragentid" => (int)$item['conid'] > 0 ? (int)$item['conid'] : (int)$item['partid'],
		"contragent"   => (int)$item['conid'] > 0 ? current_client($item['conid']) : current_client($item['partid']),
		"document"     => sprintf("Счет №%s<br><span class='gray fs-09'>от %s</span>", $item['invoice'], format_date_rus($item['invoice_date'])),
		"recv"         => $item['des'],
		"deal"         => $item['deal'],
		"summa"        => (float)$item['summa'],
		"rsid"         => (int)$item['rsid'],
		"rs"           => $item['rs'],
		"mcid"         => (int)$item['mcid'],
		"company"      => $company[$item['mcid']],
		"dplan"        => $item['dplan'],
		"dz"           => NULL,
		"xsumma"       => (float)$item['summa'],
		"saldo"        => $totalsaldo,
		"direction"    => $item['cattip'] == 'dohod' ? "plus" : "minus"
	];

}

ksort($list);

/*
file_put_contents($rootpath."/cash/cashflow.json", json_encode_cyr([
	"list"  => $list,
	"total" => $total,
	"saldo" => $saldo
]));
*/

?>
<div class="zagolovok_rep div-center relativ mt20 mb20">

	<span class="fs-12 uppercase">Денежный поток</span><br>
	<span class="gray2 em fs-07 noBold pt5">за период&nbsp;с&nbsp;<?= format_date_rus($dateStart) ?>&nbsp;по&nbsp;<?= format_date_rus($dateEnd) ?></span>

	<div class="pull-right hand noprint" style="top:0;">

		<div class="pop nothide" id="params">

			<div class="gray2 mr20 fs-12"><i class="icon-list-nested gray2"></i></div>
			<div class="popmenu-top cursor-default" style="right:5px">

				<div class="popcontent w3001 box--child" style="right: 0;">

					<div class="graybg-sub p10 sticked--top noborder no-shadow">Остатки по счетам</div>
					<div class="flex-vertical border--bottom fs-09">

						<?php
						foreach ($bank as $id => $item) {

							print '
							<div class="flex-container p10 ha">
								<div class="flex-string blue fs-09">'.$item['bank'].'</div>
								<div class="flex-string gray fs-07 mb5">'.$item['company'].'</div>
								<div class="flex-string fs-09 noBold text-right"><b>'.num_format($item['summa']).'</b> '.$valuta.'</div>
							</div>
							';

						}
						?>

					</div>
					<div class="graybg-sub p10 sticked--bottom noborder no-shadow text-right"><?= num_format($fullsaldo) ?> <?= $valuta ?></div>

				</div>

			</div>

		</div>

	</div>

</div>

<table class="noborder noprint">
	<tr>
		<td class="wp40">
			<div class="ydropDown wp90" data-id="mc">

				<span title="Компания"><i class="icon-town-hall black"></i></span>
				<span class="yText Bold fs-09">Компания: </span>
				<span class="ydropText Bold"><?= $selected ?></span>
				<i class="icon-angle-down pull-aright arrow"></i>

				<div class="yselectBox" style="max-height: 350px;">

					<div class="ydropString yRadio <?= ( $mc == 0 ? 'bluebg-sub' : '' ) ?>">
						<label>
							<input type="radio" name="mc" id="mc" data-title="Не выбрано" value="" class="hidden" <?= ( $mc == 0 ? 'checked' : '' ) ?>>&nbsp;Не выбрано
						</label>
					</div>
					<?php
					foreach ($company as $id => $comp) {

						print '
						<div class="ydropString yRadio '.( $id == $mc ? 'bluebg-sub' : '' ).'">
							<label>
								<input type="radio" name="mc" id="mc" data-title="'.$comp.'" value="'.$id.'" class="hidden" '.( $id == $mc ? 'checked' : '' ).'>&nbsp;'.$comp.' [ '.num_format($ostatokCompany[$id]).' '.$valuta.' ]
							</label>
						</div>
						';

					}
					?>

				</div>

			</div>
		</td>
		<td class="wp30"></td>
		<td class="wp30"></td>
	</tr>
</table>

<hr>

<div class="data">

	<table class="top">
		<thead class="sticked--top">
		<tr>
			<th class="w40">Год</th>
			<th class="w100">Вид расходов</th>
			<th class="w100">Тип</th>
			<th class="w100">Контрагент</th>
			<th class="w100">№, дата документа основания</th>
			<th class="w200">Сделка</th>
			<th>Реквизиты документа</th>
			<th class="w120">Сумма счёта</th>
			<th class="w120">Расчетный счет</th>
			<th class="w100">Ожидаемая дата оплаты</th>
			<th class="w120">Выручка (Поступление)</th>
			<th class="w120">Остаток по счетам на конец дня</th>
		</tr>
		</thead>
		<tbody>
		<tr class="bluebg-sub Bold">
			<td colspan="7">Начало расчета</td>
			<td class="text-right"></td>
			<td></td>
			<td></td>
			<td class="text-right"></td>
			<td class="text-right blue"><?= num_format($xtotalsaldo) ?></td>
		</tr>
		<?php
		$saldoFinal = $rashodFinal = $invoiceFinal = $saldoTotal = 0;
		foreach ($list as $date => $items) {

			print '
			<tr class="brounbg-sub">
				<td colspan="12" class="Bold">'.format_date_rus($date).'</td>
			</tr>';

			$xsumma = $xdz = $xsaldo = 0;

			foreach ($items as $item) {

				if ($item['direction'] == 'plus') {
					$sumSaldo += (float)$item['xsumma'];
				}
				else{
					$sumSaldo -= (float)$item['xsumma'];
				}

				print '
				<tr>
					<td>'.$item['year'].'</td>
					<td>'.$item['type'].'</td>
					<td>
						<div class="'.$item['color'].' Bold">
							'.( $item['id'] > 0 ? '<a href="javascript:void(0)" onclick="editBudjet(\''.$item['id'].'\',\'view\')" title="Просмотр расхода"><i class="icon-eye blue"></i>'.$item['xtype'].'</a>' : $item['xtype'] ).'
						</div>
					</td>
					<td class="hand Bold">
						<a href="javascript:void(0)" class="blue" onclick="openClient('.$item['contragentid'].')" title="'.$item['contragent'].'">'.$item['contragent'].'</a>
					</td>
					<td>'.$item['document'].'</td>
					<td>
						'.( $item['did'] > 0 ? '<a href="javascript:void(0)" onclick="openDogovor('.$item['did'].',\'7\')" title=""><i class="icon-briefcase-1 broun"></i>'.$item['deal'].'</a>' : $item['deal'] ).'
					</td>
					<td>
						<div class="">'.$item['recv'].'</div>
					</td>
					<td class="text-right Bold">'.num_format($item['summa']).'</td>
					<td>'.$item['rs'].'</td>
					<td>'.format_date_rus($item['dplan']).'</td>
					<td class="text-right Bold">'.num_format($item['dz']).'</td>
					<td class="text-right greenbg-sub">'.num_format($sumSaldo).'</td>
				</tr>
				';

				$xdz += (float)$item['dz'];
				if ($item['direction'] == 'plus') {
					$xsumma += (float)$item['summa'];
				}
				else {
					$xsumma -= (float)$item['summa'];
				}

			}

			print '
			<tr class="graybg-sub Bold">
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td class="text-right">'.num_format($xsumma).'</td>
				<td></td>
				<td></td>
				<td class="text-right">'.num_format($xdz).'</td>
				<td class="text-right green">'.num_format($sumSaldo).'</td>
			</tr>
			';

			$saldoFinal = $saldo[$date];

			$rashodFinal  += $xsumma;
			$invoiceFinal += $xdz;

		}

		$xtotalsaldo = $xtotalsaldo + $xdz - $xsumma;

		print '
		<tfoot class="sticked--bottom">
			<tr class="bluebg-sub Bold">
				<td class="bluebg-sub" colspan="7">Итог</td>
				<td class="bluebg-sub text-right">'.num_format($rashodFinal).'</td>
				<td></td>
				<td></td>
				<td class="bluebg-sub text-right">'.num_format($invoiceFinal).'</td>
				<td class="bluebg-sub text-right blue">'.num_format($sumSaldo).'</td>
			</tr>
		</tfoot>
		';
		?>
		</tbody>
	</table>

	<div class="flex-container box--child w500 hidden">

		<div class="flex-string wp30 p10">Сальдо начальное:</div>
		<div class="flex-string wp70 p10"><?= num_format($xtotalsaldo).' '.$valuta ?></div>

		<div class="flex-string wp30 p10">Сальдо конечное:</div>
		<div class="flex-string wp70 p10"><?= num_format($saldoFinal).' '.$valuta ?></div>

		<div class="flex-string wp30 p10">Приход/Расход:</div>
		<div class="flex-string wp70 p10"><?= num_format($saldoFinal - $xtotalsaldo).' '.$valuta ?></div>

	</div>

</div>

<div class="space-100"></div>

<script>

	$(function () {
	});

</script>