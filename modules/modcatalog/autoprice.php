<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */
?>
<?php
error_reporting(0);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$q = str_replace(" ", "%", texttosmall($_REQUEST["q"]));
if ($q == '') print 'error';

$result = $db -> query("SELECT n_id as prid, LOWER(title) as title2, title, price_1, price_in FROM ".$sqlname."price WHERE (title LIKE '%".$q."%' or artikul LIKE '%".$q."%') and identity = '$identity'");
while ($data = $db -> fetch($result)) {

	print $data['title']."|".num_format($data['price_1'])."|".$data['prid']."|".num_format($data['price_in'])."\n";

}

exit();
?>