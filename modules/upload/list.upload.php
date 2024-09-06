<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

use Salesman\Upload;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

global $userRights;

if ( $_GET['action'] == "delete" ) {

	$fid        = $_GET['fid'];
	$idcategory = $_GET['idcategory'];

	$fname = $db -> getOne( "select fname from {$sqlname}file where fid='".$fid."' and identity = '$identity'" );

	@unlink( $rootpath."/files/".$fpath.$fname );

	try {
		$db -> query( "delete from {$sqlname}file where fid = '".$fid."' and identity = '$identity'" );
	}
	catch ( Exception $e ) {
		echo $e -> getMessage();
	}

}

$lists = Upload::list($_REQUEST);

print json_encode_cyr( $lists );

exit();
