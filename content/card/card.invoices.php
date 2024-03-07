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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );;

$clid   = (int)$_REQUEST['clid'];
$action = untag($_REQUEST['action']);

$invoices = [];

//print "SELECT invoice, crid, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE do != 'on' and clid='".$clid."' and identity = '".$identity."' GROUP BY invoice";

$res = $db -> query( "SELECT invoice, crid, SUM(summa_credit) as summa, did FROM {$sqlname}credit WHERE do != 'on' AND clid = '$clid' AND identity = '$identity' GROUP BY invoice ORDER BY datum DESC" );
while ($data = $db -> fetch( $res )) {

	$sfx = $data['invoice'] != '' ? "invoice='".$data['invoice']."' and " : "";

	$r = $db -> query( "SELECT * FROM {$sqlname}credit WHERE $sfx did = '$data[did]' and identity = '$identity'" );
	while ($da = $db -> fetch( $r )) {

		$invoices[] = [
			"crid"       => (int)$da['crid'],
			"crid_main"  => (int)$data['crid'],
			"invoice"    => $da['invoice'],
			"summa"      => $da['summa_credit'],
			"summaTotal" => $data['summa'],
			"nds"        => $da['nds_credit'],
			"do"         => $da['do'],
			"dcreate"    => $da['datum'],
			"dplan"      => $da['datum_credit'],
			"dfact"      => $da['invoice_date'],
			"iduser"     => $da['iduser'],
			"clid"       => (int)$da['clid'],
			"pid"        => (int)$da['pid'],
			"did"        => (int)$da['did'],
			"rs"         => (int)$da['rs'],
			"tip"        => $da['tip'],
			"suffix"     => $da['suffix'],
			"contract"   => $da['invoice_chek'],
			"day"        => $day = round( diffDate2( $da['datum_credit'] ) )
		];

	}

}

if ( empty( $invoices ) ) {

	print '<div class="pad5 gray">Не оплаченных счетов нет</div>';
	exit();

}
?>
<div class="fcontainer p0">

	<?php
	foreach ( $invoices as $invoice ) {

		$day = round( diffDate2( $invoice['dplan'] ) );

		if ( $day < 0 && $invoice['do'] != "on" ) {
			$day = '<div class="fs-12"><b class="red">'.$day.'</b> дн.</div><span class="fs-09">Просрочено</span>';
		}
		elseif ( $day >= 0 && $invoice['do'] != "on" ) {
			$day = '<div class="fs-12"><b class="green">+'.$day.' дн</b>.</div><span class="fs-09">Ожидается</span>';
		}
		else {
			$day = '';
		}

		if ( $invoice['do'] != "on" ) {
			$doo = '<div class="fs-12"><b class="red">'.get_date( $invoice['dplan'] ).'</b></div><div><span class="fs-09">Ожидаемая дата</span></div>';
		}
		if ( $invoice['do'] == "on" ) {
			$doo = '<div class="fs-12"><b class="green">Оплачен</b></div><div><span class="fs-09">'.format_date_rus( $invoice['dfact'] ).'</span></div>';
		}

		print '
		<div class="flex-container p10 graybg-sub">
			<div class="flex-string wp100 hand" onclick="openDogovor(\''.$invoice['did'].'\')">
				<i class="icon-briefcase-1 broun"></i>'.current_dogovor( $invoice['did'] ).'
			</div>
		</div>
		<div class="flex-container p10" data-id="invoice" data-crid="'.$invoice['crid'].'">
			<div class="flex-string wp20">
				<div class="fs-12"><b>№ '.$invoice['invoice'].'</b></div>
				<div class="fs-09 gray2">от '.get_date( $invoice['dplan'] ).'</div>
			</div>
			<div class="flex-string wp30">
				'.$day.'
			</div>
			<div class="flex-string wp30">
				'.$doo.'
			</div>
			<div class="flex-string wp20 fs-12"><b>'.num_format( $invoice['summa'] ).'</b> '.$valuta.'</div>
		</div>
		';

	}
	?>

</div>