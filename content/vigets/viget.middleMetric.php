<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

/**
 * - Среднемесячная выручка
 * - Средний чек
 * - Средняя маржа на сотрудника
 */

$period = getPeriod( 'month' );

$da1 = $period[0];
$da2 = $period[1];

$countInvoiceNew = $countInvoiceDo = 0;
$summaInvoiceNew = $summaInvoiceDo = 0;
$conversation    = $middleInvouice = 0;

//выборка сотрудников
$sort = ($userRights['individualplan']) ? [$iduser1] : get_people( $iduser1, "yes" );

//Дата первой закрытой сделки
$dFirst = $db -> getOne( "SELECT MIN(datum_close) FROM {$sqlname}dogovor WHERE did > 0 and close = 'yes' and iduser IN (".yimplode( ",", $sort ).") and datum_close != '0000-00-00' and identity = '$identity'" );
//Дата последней закрытой сделки
$dLast = $db -> getOne( "SELECT MAX(datum_close) FROM {$sqlname}dogovor WHERE did > 0 and close = 'yes' and iduser IN (".yimplode( ",", $sort ).") and identity = '$identity'" );

//Число месяцев между датами
$diff = !empty($dLast) ? round( diffDate2( monthData( $dLast, 'last' ), monthData( $dFirst ) ) / 30 + 0.5 ) : 0;

//если период больше 12 мес., то берем 12 и расчитываем начальную дату
if ( $diff > 12 ) {

	$diff = 12;

	$d = getDateTimeArray( current_datum() );

	$dFirst = strftime( '%Y-%m-%d', mktime( 1, 0, 0, $d['m'] - $diff, 1, $d['Y'] ) );
	$dLast  = $da2;

}
elseif ( $diff == 0 ) {

	$diff   = 1;
	$dFirst = $da1;
	$dLast  = $da2;

}

/**
 * Для учета по закрытым сделкам. Счета не учитываем
 */
if ( !$otherSettings[ 'credit'] ) {

	/*
	 * Расчет показателей за всё время
	 */
	$fullDeals = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol_fact) as summa, SUM(marga) as marga FROM {$sqlname}dogovor WHERE did > 0 and close = 'yes' and iduser IN (".yimplode( ",", $sort ).") and datum_close BETWEEN '$dFirst 00:00:00' and '$dLast 23:59:59' and identity = '$identity'" );

	$full['middleCheck'] = ((int)$fullDeals['count'] > 0) ? (float)$fullDeals['summa'] / (int)$fullDeals['count'] : 0;
	$full['middleMarga'] = ((int)$fullDeals['count'] > 0) ? (float)$fullDeals['marga'] / (int)$fullDeals['count'] : 0;
	$full['middleSumma'] = (float)$fullDeals['summa'] / $diff;


	/*
	 * Расчет показателей за текущий месяц
	 */
	$currentDeals = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol_fact) as summa, SUM(marga) as marga FROM {$sqlname}dogovor WHERE did > 0 and close = 'yes' and iduser IN (".yimplode( ",", $sort ).") and datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and identity = '$identity'" );

	$current['middleCheck'] = ((int)$currentDeals['count'] > 0) ? (float)$currentDeals['summa'] / (int)$currentDeals['count'] : 0;
	$current['middleMarga'] = ((int)$currentDeals['count'] > 0) ? (float)$currentDeals['marga'] / (int)$currentDeals['count'] : 0;
	$current['middleSumma'] = (float)$currentDeals['summa'];

}
else {

	/*
	 * Расчет показателей за всё время
	 */
	if ( !$otherSettings[ 'planByClosed'] ) {
		$result = $db -> getAll( "SELECT * FROM ${sqlname}credit WHERE do = 'on' and invoice_date BETWEEN '$dFirst 00:00:00' and '$dLast 23:59:59' and did IN (SELECT did FROM ${sqlname}dogovor WHERE did > 0 and iduser IN (".yimplode( ",", $sort ).")) OR (iduser IN (".yimplode( ",", $sort ).") and invoice_date BETWEEN '$dFirst 00:00:00' and '$dLast 23:59:59') and identity = '$identity' ORDER by did" );
	}

	if ( $otherSettings[ 'planByClosed'] ) {
		$result = $db -> getAll( "
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
			{$sqlname}dogovor.close = 'yes' and
			{$sqlname}dogovor.datum_close BETWEEN '$dFirst 00:00:00' and '$dLast 23:59:59' and
			{$sqlname}credit.iduser IN (".yimplode( ",", $sort ).") and
			{$sqlname}credit.identity = '$identity'
		ORDER by {$sqlname}credit.invoice_date" );
	}

	$fullDeals['count'] = 0;
	$fullDeals['summa'] = 0;
	$fullDeals['marga'] = 0;

	foreach ( $result as $data ) {

		//расчет процента размера платежа от суммы сделки
		$kolp   = (float)$db -> getOne( "SELECT kol FROM ${sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity'" );
		$margap = (float)$db -> getOne( "SELECT marga FROM ${sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity' ORDER by did" );

		$dolya = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;//% оплаченной суммы от суммы по договору
		$m     = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;

		$fullDeals['summa'] += pre_format( $data['summa_credit'] );
		$fullDeals['marga'] += $margap * $dolya;

		$fullDeals['count']++;

	}

	$full['middleCheck'] = ($fullDeals['count'] > 0) ? $fullDeals['summa'] / $fullDeals['count'] + 0 : 0;
	$full['middleMarga'] = ($fullDeals['count'] > 0) ? $fullDeals['marga'] / $fullDeals['count'] + 0 : 0;
	$full['middleSumma'] = $fullDeals['summa'] / $diff;


	/*
	 * Расчет показателей за текущий месяц
	 */
	if ( !$otherSettings[ 'planByClosed'] ) {
		$result = $db -> getAll( "SELECT * FROM ${sqlname}credit WHERE do = 'on' and invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and did IN (SELECT did FROM ${sqlname}dogovor WHERE did > 0 and iduser IN (".yimplode( ",", $sort ).")) OR (iduser IN (".yimplode( ",", $sort ).") and invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59') and identity = '$identity' ORDER by did" );
	}

	if ( $otherSettings[ 'planByClosed'] ) {
		$result = $db -> getAll( "
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
			{$sqlname}dogovor.close = 'yes' and
			{$sqlname}dogovor.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			{$sqlname}credit.iduser IN (".yimplode( ",", $sort ).") and
			{$sqlname}credit.identity = '$identity'
		ORDER by {$sqlname}credit.invoice_date" );
	}

	$currentDeals['count'] = 0;
	$currentDeals['summa'] = 0;
	$currentDeals['marga'] = 0;

	foreach ( $result as $data ) {

		//расчет процента размера платежа от суммы сделки
		$kolp   = $db -> getOne( "SELECT kol FROM ${sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity'" );
		$margap = $db -> getOne( "SELECT marga FROM ${sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity' ORDER by did" );

		$dolya = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;//% оплаченной суммы от суммы по договору
		$m     = ($kolp > 0) ? $data['summa_credit'] / $kolp : 0;

		$currentDeals['summa'] += pre_format( $data['summa_credit'] );
		$currentDeals['marga'] += $margap * $dolya;

		$currentDeals['count']++;

	}

	$current['middleCheck'] = ($currentDeals['count'] > 0) ? $currentDeals['summa'] / $currentDeals['count'] + 0 : 0;
	$current['middleMarga'] = ($currentDeals['count'] > 0) ? $currentDeals['marga'] / $currentDeals['count'] + 0 : 0;
	$current['middleSumma'] = $currentDeals['summa'] + 0;

}

?>

<style>

	#middleMetric ul.group {
		position              : absolute;
		z-index               : 1;
		top                   : calc(100% - 40px);
		right                 : 10px;
		display               : table;
		list-style            : none;
		background            : rgba(245, 245, 245, 0.3);
		border                : 1px solid #CCD1D9;
		margin-top            : 5px;
		padding               : 0;
		font-size             : 0.9em;
		border-radius         : 4px;
		-moz-border-radius    : 4px;
		-webkit-border-radius : 4px;
	}

	#middleMetric ul.group > li {
		margin       : 0 !important;
		padding      : 5px 10px !important;
		display      : table-cell;
		text-align   : center;
		cursor       : pointer;
		border-right : 1px solid #CCD1D9;
		box-sizing   : border-box !important;
	}

	#middleMetric ul.group > li:last-child {
		border-right : 0;
	}

	#middleMetric ul.group > li:hover,
	#middleMetric ul.group > li.active {
		color        : #fff;
		background   : #C0392B;
		border-color : #C0392B !important;
	}

</style>

<div class="flex-container box--child" style="justify-content: space-between;">

	<div class="flex-string wp50 mobile flx-2">

		<div class="blue fs-10 Bold uppercase mobile p10 cherta">Текущий месяц</div>

		<div class="mobile p10">

			<div class="gray fs-10 Bold uppercase">Выручка:</div>
			<div class="fs-16 Bold pt10 blue"><?= num_format( $current['middleSumma'] ) ?> <?= $valuta ?></div>

		</div>
		<div class="mobile p10">

			<div class="gray fs-10 Bold uppercase">Маржа:</div>
			<div class="fs-16 Bold pt10 broun"><?= num_format( $current['middleMarga'] ) ?> <?= $valuta ?></div>

		</div>
		<div class="mobile p10">

			<div class="gray fs-10 Bold uppercase">Средний чек: <?= $currentDeals['count'] ?> шт.</div>
			<div class="fs-16 Bold pt10 green"><?= num_format( $current['middleCheck'] ) ?> <?= $valuta ?></div>

		</div>

	</div>
	<div class="flex-string wp50 mobile flx-2">

		<div class="gray fs-10 Bold uppercase mobile p10 cherta">За всё время</div>

		<div class="mobile p10">

			<div class="gray fs-10 Bold uppercase">Средняя выручка: <?= $diff ?> мес.</div>
			<div class="fs-16 Bold pt10 blue"><?= num_format( $full['middleSumma'] ) ?> <?= $valuta ?></div>

		</div>
		<div class="mobile p10">

			<div class="gray fs-10 Bold uppercase">Средняя маржа:</div>
			<div class="fs-16 Bold pt10 broun"><?= num_format( $full['middleMarga'] ) ?> <?= $valuta ?></div>

		</div>
		<div class="mobile p10">

			<div class="gray fs-10 Bold uppercase">Средний чек: <?= $fullDeals['count'] ?> шт.</div>
			<div class="fs-16 Bold pt10 green"><?= num_format( $full['middleCheck'] ) ?> <?= $valuta ?></div>

		</div>

	</div>

</div>

<div class="pull-aright mt5 hidden">

	<ul class="group">
		<li data-id="calendarweek">Неделя</li>
		<li data-id="month">Месяц</li>
		<li data-id="quart">Квартал</li>
	</ul>

</div>

<script>

</script>