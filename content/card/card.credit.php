<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Currency;
use Salesman\Invoice;
use Salesman\Speka;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$did    = (int)$_REQUEST['did'];
$crid   = (int)$_REQUEST['crid'];
$action = untag( $_REQUEST['action'] );

$isper = 'no';

//Найдем тип сделки, которая является Сервисной
if ( isServices( (int)$did ) ) {
	$isper = 'yes';
}

//Проверим реквизит для счета, если включено выставление счетов
$result = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );
$clid   = (int)$result["clid"];
$payer  = (int)$result["payer"];
$kol    = pre_format( $result["kol"] );
$mcid   = (int)$result["mcid"];

$deal = get_dog_info( $did, 'yes' );

$json  = get_client_recv( $payer );
$recvz = json_decode( $json, true );

if ( ($recvz['castName'] == '' || $recvz['castInn'] == '') && $otherSettings[ 'printInvoice'] ) {

	print '
	<div class="warning m0 mb10">
		<b class="red">Внимание!</b> Не заполнены реквизиты плательщика. 
		<a href="javascript:void(0)" onclick="editClient(\''.$payer.'\',\'change.recvisites\');" class="button0 Bold"><i class="icon-edit"></i>Заполнить?</a>
	</div>
	';

}

print '
<style>
	.buttons a{
		border-right: 1px dotted #95A5A6;
		padding: 0 10px 0 10px;
	}
	.buttons a:last-child{
		border-right: 0;
	}
</style>
';

$credit = (new Invoice()) -> card($did);

$html = file_get_contents( $rootpath.'/content/tpl/card.credit.mustache' );

Mustache_Autoloader ::register();
$m = new Mustache_Engine();

print $html = $m -> render( $html, $credit );

ext:

/**
 * Выводим блок кнопок, если оплата не на всю спеку или для сервисных сделок
 */
if ( (pre_format( $kol ) != pre_format( $summa ) && $close != 'yes') || $isper == 'yes' ) {
	print '
	<hr>
	<div class="mp10">
		<a href="javascript:void(0)" onclick="editCredit(\''.$did.'\',\'credit.add\');" class="button"><i class="icon-plus-circled-1"></i>Добавить счет</a>&nbsp;
		'.($isper != 'yes' ? '<a href="javascript:void(0)" onclick="editCredit(\''.$did.'\',\'credit.express\');" class="button orangebtn"><i class="icon-ok-circled"></i>Внести оплату</a>' : '').'
	</div>
	';
}
else {
	print '
	<div class="attention m0 mt10">Выставлены все возможные счета по сделке</div>
	<script>
		$(\'.button-credit-add\').addClass(\'hidden\');
	</script>
	';
}