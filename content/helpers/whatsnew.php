<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$current = json_decode(file_get_contents($rootpath."/_whatsnew/version.json"), true);

$text = file_get_contents($rootpath."/_whatsnew/whatsnew-".$current['version'].".md");

$Parsedown = new Parsedown();
$html = $Parsedown -> text( $text );

?>
<div class="zagolovok"><b>Что нового в версии</b></div>
<DIV id="formtabs" class="p20 bgwhite mono enable--select" style="overflow-y: auto; overflow-x:hidden; max-height:80vh;">

	<?php print $html; ?>

</DIV>

<hr>

<div class="button--pane text-right">

	<a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>

</div>
<script>

	$(function () {

		$('#dialog').css('width', '70vw').center();

	});

</script>