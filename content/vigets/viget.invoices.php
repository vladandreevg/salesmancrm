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
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$tip = $_REQUEST['tip'];

$pays = $credit = [];

$hide = ($tip != '') ? 'hidden' : '';

$sort = get_people( $iduser1, "yes" );

function cmp($a, $b) {
	return $a['day'] - $b['day'];
}

if ( $tip != 'creditonly' ) {

	//не оплаченные счета
	$query = "
		SELECT
			{$sqlname}credit.crid as crid,
			{$sqlname}credit.did as did,
			{$sqlname}credit.clid as clid,
			{$sqlname}credit.pid as pid,
			{$sqlname}credit.do as do,
			{$sqlname}credit.invoice as invoice,
			{$sqlname}credit.summa_credit as summa,
			{$sqlname}credit.datum_credit as pdatum,
			{$sqlname}credit.invoice_date as idatum,
			{$sqlname}credit.iduser as iduser,
			{$sqlname}dogovor.title as dogovor,
			{$sqlname}dogovor.close as close,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person
		FROM {$sqlname}credit
			LEFT JOIN {$sqlname}personcat ON {$sqlname}credit.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
		WHERE
			{$sqlname}credit.crid > 0 and
			{$sqlname}credit.do != 'on' and 
			{$sqlname}dogovor.close != 'yes' and
			{$sqlname}credit.iduser IN (".implode( ",", $sort ).") and
			{$sqlname}credit.identity = '$identity'
		ORDER BY {$sqlname}credit.datum_credit
	";

	$result = $db -> getAll( $query );
	foreach ( $result as $data ) {

		if ( $data['clid'] > 0 ) {

			$client = $data['client'];
			$id     = (int)$data['clid'];

		}
		elseif ( $data['pid'] > 0 ) {

			$client = $data['person'];
			$id     = (int)$data['pid'];

		}

		$invoice = ($data['invoice'] == '') ? "б/н" : $data['invoice'];

		$credit[] = [
			"day"     => round( diffDate2( $data['pdatum'] ), 0 ),
			"summa"   => num_format( $data['summa'] ),
			"client"  => $client,
			"id"      => $id,
			"invoice" => $invoice,
			"did"     => (int)$data['did'],
			"crid"    => (int)$data['crid']
		];

	}

	//пересортируем массив
	usort( $credit, "cmp" );

}

if ( $tip != 'invoiceonly' ) {

	//оплаченные счета
	$query = "
		SELECT
			{$sqlname}credit.crid as crid,
			{$sqlname}credit.did as did,
			{$sqlname}credit.clid as clid,
			{$sqlname}credit.pid as pid,
			{$sqlname}credit.do as do,
			{$sqlname}credit.invoice as invoice,
			{$sqlname}credit.summa_credit as summa,
			{$sqlname}credit.datum_credit as pdatum,
			{$sqlname}credit.invoice_date as idatum,
			{$sqlname}credit.iduser as iduser,
			{$sqlname}dogovor.title as dogovor,
			{$sqlname}dogovor.close as close,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person
		FROM {$sqlname}credit
			LEFT JOIN {$sqlname}personcat ON {$sqlname}credit.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
		WHERE
			{$sqlname}credit.crid > 0 and
			{$sqlname}credit.do = 'on' and
			DATE_FORMAT({$sqlname}credit.invoice_date, '%Y-%m') = '".date( 'Y' )."-".date( 'm' )."' and
			({$sqlname}credit.iduser IN (".implode( ",", $sort ).") or {$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") and identity = '$identity')) and
			{$sqlname}credit.identity = '$identity'
		ORDER BY {$sqlname}credit.invoice_date
	";

	$result = $db -> getAll( $query );
	foreach ( $result as $data ) {

		if ( $data['clid'] > 0 ) {

			$client = $data['client'];
			$id     = $data['clid'];

		}
		elseif ( $data['pid'] > 0 ) {

			$client = $data['person'];
			$id     = $data['pid'];

		}

		$invoice = ($data['invoice'] == '') ? "б/н" : $data['invoice'];

		$pays[] = [
			"day"     => round( diffDate2( $data['idatum'] ), 0 ),
			"summa"   => num_format( $data['summa'] ),
			"client"  => $client,
			"id"      => $id,
			"invoice" => $invoice,
			"did"     => $data['did'],
			"crid"    => $data['crid']
		];

	}

	//пересортируем массив
	usort( $pays, "cmp" );

}

if ( $tip != 'invoiceonly' ) {

	print '
		<div class="flex-container p10 graybg-sub green Bold uppercase '.$hide.' sticked--top">
	
			<div class="flex-string">
				Оплаченные счета
			</div>
	
		</div>';

	foreach ( $pays as $k => $pay ) {

		$color = "green";
		$znak  = ($pay['day'] < 0) ? "- " : "";

		print '
		<div class="flex-container float border-bottom p10 ha">
			
			<div class="flex-string float">
				
				<div class="mb5">
					<span class="ellipsis Bold fs-12" title="В карточку">
						<a href="javascript:void(0)" onclick="openDogovor(\''.$pay['did'].'\',\'7\')"><i class="icon-briefcase-1 broun"></i>&nbsp;<b class="blue">'.current_dogovor( $pay['did'] ).'</b></a>
					</span>
				</div>
				<div class="">
					<span class="ellipsis" title="'.$pay['client'].'">
						<i class="icon-building gray"></i>'.$pay['client'].'
					</span>
				</div>
				
			</div>
			<div class="flex-string w100">
			
				<div class="fs-12 Bold mb5" title="Сумма к оплате">'.$pay['summa'].'</div>
				<div class="fs-09 Bold blue">Сч. №'.$pay['invoice'].'</div>
				<div class="'.$color.'"><b>'.$znak.abs( $pay['day'] ).'</b> дн.</div>
				
			</div>
			
		</div>
		';

	}

	if ( empty( $pays ) )
		print '
		<div class="flex-container p10 gray">
	
			<div class="flex-string">
				Нет оплат
			</div>
	
		</div>
		';

	print '
		<div class="flex-container p10 graybg-sub red Bold uppercase '.$hide.' sticked--top">
	
			<div class="flex-string">
				Ожидаемые оплаты
			</div>
	
		</div>';

}

if ( $tip != 'creditonly' ) {

	foreach ( $credit as $k => $pay ) {

		if ( $pay['day'] == 0 )
			$color = "blue";

		elseif ( $pay['day'] < 0 )
			$color = "red";

		else
			$color = "green";

		$znak = ($pay['day'] < 0) ? "- " : "";

		print '
		<div class="flex-container float border-bottom p10 ha">
			
			<div class="flex-string float">
			
				<div class="mb5">
					<span class="ellipsis Bold fs-12" title="В карточку">
						<a href="javascript:void(0)" onclick="openDogovor(\''.$pay['did'].'\',\'7\')"><i class="icon-briefcase-1 broun"></i>&nbsp;<b class="blue">'.current_dogovor( $pay['did'] ).'</b></a>
					</span>
				</div>
				<div class="">
					<span class="ellipsis" title="'.$pay['client'].'">
						<i class="icon-building gray"></i>'.$pay['client'].'
					</span>
				</div>
				
			</div>
			<div class="flex-string w100">
			
				<div class="fs-12 Bold mb5" title="Сумма к оплате">'.$pay['summa'].'</div>
				<div class="fs-09 Bold blue">Сч. №'.$pay['invoice'].'</div>
				<div class="'.$color.'"><b>'.$znak.abs( $pay['day'] ).'</b> дн.</div>
				
			</div>
			
		</div>
		';

	}

	if ( empty( $credit ) ) {
		print '
		<div class="flex-container p10 gray">
	
			<div class="flex-string">
				Нет счетов
			</div>
	
		</div>
		';
	}

}
