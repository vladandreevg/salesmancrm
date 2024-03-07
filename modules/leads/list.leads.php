<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Leads;

error_reporting(E_ERROR);

header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$tar     = $_REQUEST['tar'];

//добавим поле, если его нет
$res = $db -> getRow("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='".$sqlname."leads' AND COLUMN_NAME='rezz'");
if ($res['COLUMN_NAME'] == "") {
	$db -> query( "ALTER TABLE `".$sqlname."leads` ADD `rezz` TEXT NOT NULL AFTER muid" );
}

if ($tar == 'lists') {

	$params = [
		"page"   => $_REQUEST['page'],
		"user"   => $_REQUEST['user'],
		"status" => $_REQUEST['statuss'],
		"da1"    => $_REQUEST['da1'],
		"da2"    => $_REQUEST['da2'],
		"email"  => $_REQUEST['email'],
		"ord"    => $_REQUEST['ord'],
		"tuda"   => $_REQUEST['tuda'],
		"word"   => $_REQUEST['word'],
	];

	$l = new Leads();
	$l -> listLeads($params);

	$lists = $l -> lists;

	print json_encode_cyr($lists);

	exit();

}

if ($tar == 'source') {

	$params = [
		"page"   => $_REQUEST['page'],
		"ord"    => $_REQUEST['ord'],
		"tuda"   => $_REQUEST['tuda'],
		"word"   => $_REQUEST['words'],
	];

	$l = new Leads();
	$l -> listSources($params);

	$lists = $l -> lists;

	print json_encode_cyr($lists);

	exit();

}

if ($tar == 'utms') {

	$params = [
		"page"   => $_REQUEST['page'],
		"ord"    => $_REQUEST['ord'],
		"tuda"   => $_REQUEST['tuda'],
		"word"   => $_REQUEST['wordu'],
	];

	$l = new Leads();
	$l -> listUTM($params);

	$lists = $l -> lists;

	print json_encode_cyr($lists);

	exit();

}
