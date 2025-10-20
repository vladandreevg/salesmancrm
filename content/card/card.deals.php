<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Deal;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );


$action = untag3($_REQUEST['action']);

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];
$docSort = untag($_COOKIE['dealsSort']);

if ($clid > 0) {
	
	$clids = $db -> getCol("SELECT clid FROM {$sqlname}clientcat WHERE head_clid = '$clid'");
	
	$apx = !empty($clids) ? " OR clid IN ('".implode("','", $clids)."')" : '';
	
	$s      = "clid = '$clid' OR payer = '$clid' $apx";
	$client = current_client($clid);
	
}
if ($pid > 0) {
	$s      = "pid = '$pid'";
	$client = current_person($pid);
}

$closedDeals = $db -> getRow("SELECT COUNT(did) as count, SUM(kol_fact) as summa, SUM(marga) as marga FROM {$sqlname}dogovor WHERE (clid = '$clid' OR payer = '$clid' $apx) AND close = 'yes' and kol_fact > 0 AND identity = '$identity'");

$activeDeals = $db -> getRow("SELECT COUNT(did) as count, SUM(kol) as summa, SUM(marga) as marga FROM {$sqlname}dogovor WHERE (clid = '$clid' OR payer = '$clid' $apx) AND close != 'yes' AND identity = '$identity'");

$ssort = ($docSort == 'DESC') ? '' : 'DESC';
$icon  = ($docSort == 'DESC') ? 'icon-sort-alt-down' : 'icon-sort-alt-up';

print '
	<div class="inline pull-left Bold" style="position:absolute; top:10px">
	
		<a href="javascript:void(0)" onclick="setCookie(\'dealsSort\', \''.$ssort.'\', {expires:31536000}); settab(\'4\')" class="gray" title="Изменить сортировку"><i class="'.$icon.' broun"></i> Сортировка</a>
	
	</div>
	<div class="viewdiv mb10 mt10">
		<div class="mb5">
			Реализованные '.texttosmall($lang['face']['DealsName'][0]).': <b>'.$closedDeals['count'].'</b> шт., 
			Сумма: <b>'.num_format($closedDeals['summa']).' '.$valuta.'</b>'.
			($show_marga == 'yes' && $other[9] == 'yes' ? ', Прибыль: <b>'.num_format($closedDeals['marga']).' '.$valuta.'</b>' : '').'
		</div>
		<div>
			'.$lang['face']['DealsName'][0].' в работе: <b>'.$activeDeals['count'].'</b> шт., 
			Сумма: <b>'.num_format($activeDeals['summa']).' '.$valuta.'</b>'.
			($show_marga == 'yes' && $other[9] == 'yes' ? ', Прибыль: <b>'.num_format($activeDeals['marga']).' '.$valuta.'</b>' : '').'
		</div>
	</div>
';

$deals = (new Deal()) -> card([
	"clid" => $clid,
	"dealsSort" => $docSort
]);

$html = file_get_contents( $rootpath.'/content/tpl/card.deals.mustache' );

Mustache_Autoloader ::register();
$m = new Mustache_Engine();

print $html = $m -> render( $html, $deals );
?>
<script>

	if (!isMobile) {

		$('#tab-4').find('.cardBlock').each(function () {

			var el = $(this).find('.fieldblocks');
			var hf = el.actual('outerHeight');
			var initHeight = $(this).data('height');

			if (hf > 100) {
				$(this).css({"height": initHeight + "px"});
				el.prop('data-height', initHeight + "px");
			}

		});

	}
	else $('.cardResizer').remove();

</script>
