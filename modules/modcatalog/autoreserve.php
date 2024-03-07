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

use Salesman\Storage;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

//include "mcfunc.php";

$print = $_REQUEST['print'];
$action = $_REQUEST['action'];

//if($action == 'reserv') print mcSyncReserv($print);
if($action == 'reserv') {

	$sklad = new Storage();
	//$result = $sklad -> SyncPoz($print);
	$result = $sklad -> SyncReserv($print);

	print $result;

}
//if($action == 'status') print mcCheckStatus($print);
?>