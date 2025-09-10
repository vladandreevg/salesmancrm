<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2025 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2025.1          */
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

$action = $_REQUEST['eaction'];

/**
 * Формирование модального окна
 */
if( $action == 'list' ){

	$lists = Upload::list([
		"idcategory" => $_REQUEST['efolder'],
		"page" => $_REQUEST['epage'],
		"word" => $_REQUEST['eseach'],
		"ord" => "fid",
		"tuda" => "DESC"
	]);

	print json_encode_cyr( $lists );
	exit();

}
