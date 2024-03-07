<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$Interval = $_COOKIE['parameterInterval'];

$setperiod = (strlen($Interval) > 1 && $Interval != 'undefined') ? $Interval : 'month';

$period = getPeriod( $setperiod );

//print_r($period);

$da1 = $period[0];
$da2 = $period[1];

$countInvoiceNew = $countInvoiceDo = 0;
$summaInvoiceNew = $summaInvoiceDo = 0;
$conversation    = $middleInvouice = 0;

$newDeals = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol) as summa FROM {$sqlname}dogovor WHERE did > 0 ".get_people( $iduser1 )." and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

$closeDeals = $db -> getRow( "SELECT COUNT(*) as count, SUM(kol_fact) as summa FROM {$sqlname}dogovor WHERE did > 0 ".get_people( $iduser1 )." and datum_close BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

$InvoiceNew = $db -> getRow( "SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE crid > 0 ".get_people( $iduser1 )." and datum BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

$InvoiceDo = $db -> getRow( "SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE crid > 0 and do = 'on' ".get_people( $iduser1 )." and invoice_date BETWEEN '".$da1." 00:00:00' and '".$da2." 23:59:59' and identity = '$identity'" );

$konversation = ($newDeals['count'] > 0) ? (int)(round( $InvoiceNew['count'] / $newDeals['count'], 1 ) * 100) : 0;

$middleCheck = ($InvoiceDo['count'] > 0) ? $InvoiceDo['summa'] / $InvoiceDo['count'] : 0;
?>

<style>

	#parameters ul.group {
		position: absolute;
		z-index: 1;
		top: calc(100% - 40px);
		right: 10px;
		display: table;
		list-style: none;
		background: rgba(245,245,245 ,0.3);
		border: 1px solid #CCD1D9;
		margin-top: 5px;
		padding: 0;
		font-size: 0.9em;
		border-radius: 4px;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
	}

	#parameters ul.group > li {
		margin: 0 !important;
		padding: 5px 10px !important;
		display: table-cell;
		text-align: center;
		cursor: pointer;
		border-right: 1px solid #CCD1D9;
		box-sizing: border-box !important;
	}

	#parameters ul.group > li:last-child {
		border-right: 0;
	}

	#parameters ul.group > li:hover,
	#parameters ul.group > li.active{
		color: #fff;
		background: #C0392B;
		border-color: #C0392B !important;
	}

</style>

<div class="flex-container box--child" style="justify-content: space-between;">

	<div class="flex-string wp50 mobile flx-2 p10">

		<div class="gray fs-10 Bold uppercase">Новых сделок: <?= $newDeals['count'] ?> шт.</div>
		<div class="fs-16 Bold pt10 blue"><?= num_format( $newDeals['summa'] ) ?> <?= $valuta ?></div>

	</div>
	<div class="flex-string wp50 mobile flx-2 p10">

		<div class="gray fs-10 Bold uppercase">Закрыто сделок: <?= $closeDeals['count'] ?> шт.</div>
		<div class="fs-16 Bold pt10 blue"><?= num_format( $closeDeals['summa'] ) ?> <?= $valuta ?></div>

	</div>

	<div class="flex-string wp50 mobile flx-2 p10">

		<div class="gray fs-10 Bold uppercase">Выставлено счетов: <?= $InvoiceNew['count'] ?> шт.</div>
		<div class="fs-16 Bold pt10 broun"><?= num_format( $InvoiceNew['summa'] ) ?> <?= $valuta ?></div>

	</div>
	<div class="flex-string wp50 mobile flx-2 p10">

		<div class="gray fs-10 Bold uppercase">Оплачено счетов: <?= $InvoiceDo['count'] ?> шт.</div>
		<div class="fs-16 Bold pt10 broun"><?= num_format( $InvoiceDo['summa'] ) ?> <?= $valuta ?></div>

	</div>

	<div class="flex-string wp50 mobile flx-2 p10 tooltips" tooltip="<yellow>Конверсия</yellow><hr>Кол-во новых счетов / Кол-во новых сделок" tooltip-position="top">

		<div class="gray fs-10 Bold uppercase">Конверсия</div>
		<div class="fs-16 Bold pt10 green"><?= ($konversation) ?>%</div>

	</div>
	<div class="flex-string wp50 mobile flx-2 p10">

		<div class="gray fs-10 Bold uppercase">Средний чек</div>
		<div class="fs-16 Bold pt10 green"><?= num_format( $middleCheck ) ?> <?= $valuta ?></div>

	</div>

</div>

<div class="pull-aright mt5">

	<ul class="group">
		<li data-id="calendarweek">Неделя</li>
		<li data-id="month">Месяц</li>
		<li data-id="quart">Квартал</li>
	</ul>

</div>

<script>

	$('#parameters').find('ul.group').find('li[data-id="<?=$setperiod?>"]').addClass('active');

	$('#parameters').find('li').bind('click', function(){

		var id = $(this).data('id');

		setCookie('parameterInterval', id, {"expires":1000000});

		$('#parameters').load('content/vigets/viget.parameters.php');

	});

	/*tooltips*/
	$('#parameters').closest('.viget').find('.tooltips').append("<span></span>");
	$('#parameters').closest('.viget').find('.tooltips:not([tooltip-position])').attr('tooltip-position','bottom');
	$('#parameters').closest('.viget').find('.tooltips').mouseenter(function(){
		$(this).find('span').empty().append($(this).attr('tooltip'));
	});
	/*tooltips*/

</script>