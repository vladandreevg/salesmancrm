<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Person;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];

$persons = (new Person()) -> card([
	"clid"   => $clid,
	"fields" => ['rol','tel','mob','mail','clientpath','loyalty']
]);

//print_r($persons);

$html = file_get_contents( $rootpath.'/content/tpl/card.persons.mustache' );

Mustache_Autoloader ::register();
$m = new Mustache_Engine();

print $html = $m -> render( $html, $persons );
?>

<script>

	if (!isMobile) {

		$('#tab-2').find('.cardBlock').each(function () {

			var el = $(this).find('.fieldblocks');
			var hf = el.actual('outerHeight');
			var initHeight = $(this).data('height');

			if (hf >= 260) {

				//$(this).css({"height": initHeight + "px"});
				el.prop('data-height', hf+"px");

			}
			else $(this).closest('.fcontainer').find('.cardResizer').remove();

		});

	}
	else $('.cardResizer').remove();

</script>
