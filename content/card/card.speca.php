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

global $isCatalog;

$speca = (new Speka()) ->card($did);

if (!$speca['calculate']) {

	print '
	<div class="p10 m5">
		Расчет по спецификациям не включен в параметрах сделки. 
		'.($close != 'yes' ? '<a href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите включить расчет по спецификации?\');if (cf)editSpeca(\'\',\'change.calculate\',\''.$did.'\');" title="Включить" class="button mt10">Включить?</a>' : '').'
	</div>
	';

}
else {

	$html = file_get_contents( $rootpath.'/content/tpl/card.speka.mustache' );

	Mustache_Autoloader ::register();
	$m = new Mustache_Engine();

	print $html = $m -> render( $html, $speca );

}

$speca = (new Speka()) ->card($did, 'material');

if ($isCatalog == 'on' && $speca['calculate'] && count($speca['speca']) > 0) {
	?>

	<div class="divider mt20 mb10">Материалы</div>

	<?php
	$html = file_get_contents( $rootpath.'/content/tpl/card.speka.mustache' );

	Mustache_Autoloader ::register();
	$m = new Mustache_Engine();

	print $html = $m -> render( $html, $speca );
	?>

	<div class="infodiv mt5 fs-09 em">&nbsp; *Материалы не учитываются в сумме сделки, счетах и актах. Себестоимость материалов вычитается из Прибыли</div>

	<?php
}
?>
<script>
	if (isMobile) {

		$('#spekaTable').rtResponsiveTables();

	}
</script>
