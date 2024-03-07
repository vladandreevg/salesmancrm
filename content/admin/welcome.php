<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
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
?>
<style>
	.mono img{
		max-width: 80%;
	}
</style>
<div class="p5">

	<h1>Приветствуем Вас в &quot;<b>Панели Управления</b>&quot;!</h1>
	<p>Здесь вы можете настроить свою систему и управлять ею.</p>

	<p><b class="errorfont">ВНИМАНИЕ: </b>в этом разделе происходят <b>глобальные настройки и управление</b> CRM-системой.Поэтому прежде чем что-либо менять сделайте <b>РЕЗЕРВНУЮ КОПИЮ</b> базы данных.</p>

	<h1>О продукте</h1>

	<div class="mono enable--select">

		<?php
		$html = file_get_contents($rootpath."/README.md");
		$Parsedown = new ParsedownExtra();
		print $Parsedown -> text($html);
		?>

		<div class="space-100"></div>

	</div>

</div>