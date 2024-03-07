<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2022.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$tar      = $_REQUEST['tar'];
$page     = $_REQUEST['page'];

?>

<div class="zagolovok">Выполненные дела</div>
<form action="/content/lists/list.tasks.do.php" method="post" enctype="multipart/form-data" name="form" id="form">
	<input type="hidden" id="page" name="page" value="<?= $page ?>">
	<input type="hidden" id="tar" name="tar" value="<?= $tar ?>">

	<div style="overflow-y:auto !important; overflow-x:hidden" id="formtabse">



	</div>

	<div class="pagecontainer">
		<div class="page pbottom mainbg" id="pagediv"></div>
	</div>

</form>

<script>

	$(function(){

		$.Mustache.load('content/tpl/tpl.tasks.html');

		$('#dialog').css({'width': '80vw'});
		$('#formtabse').css({'max-height': '80vh'});

	});

</script>