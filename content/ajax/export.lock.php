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

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

if ($_REQUEST['action'] == "check") {

	$pass = $_REQUEST['pass'];

	if ($pass != $export_lock) {
		print "Неверный пароль";
	}

	exit();

}

$url      = $_REQUEST['url'];
$datatype = $_REQUEST['datatype'];
$action   = $_REQUEST['action'];

$goal = "content/helpers/$url?datatype=$datatype&action=$action";
?>
<DIV class="zagolovok">Проверка доступа</DIV>
<FORM action="" method="post" id="export" name="export" enctype="multipart/form-data">
	<INPUT type="hidden" name="action" id="action" value="save">

	<div class="warning text-center mb20">

		Руководитель организации установил персональный пароль для данной функции.<br>
		Для получения выгрузки обратитесь к нему за паролем

	</div>

	<div class="text-center">

		<input name="exp_lock" type="password" id="exp_lock" placeholder="Введите пароль">

	</div>

	<div id="check_rezz" class="red text-center"></div>

	<hr>

	<div class="text-center">

		<A href="javascript:void(0)" onclick="check_lock('<?= $goal ?>')" class="button">Подтвердить</A>
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

	</div>
</FORM>

<script>

	function check_lock(url) {

		var purl = 'content/ajax/export.lock.php?action=check&pass=' + $('#exp_lock').val();
		$.post(purl, function (data) {

			if (!data) {
				doLoad(url);
			}
			else {
				$('#check_rezz').html(data);
			}

		});

	}
</script>