<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.6           */
/* ============================ */

use Salesman\Notify;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST[ 'action' ];

//вывод списка
if ( $action == 'popup' ) {

	$notifys = Notify ::items( $iduser1, ["limit" => 20] );

	$data = [
		"list" => $notifys[ 'list' ],
		"unread" => $notifys[ 'unread' ]
	];

	$list = json_encode_cyr( $data );

	print $list;

	exit();

}

if ( $action == 'mark' ) {

	$id = $_REQUEST[ 'id' ];

	$res = Notify ::readit( $id );

	print $res[ 'result' ][ 'result' ];

	exit();

}

if ( $action == 'markall' ) {

	$id = $_REQUEST[ 'id' ];

	$res = Notify ::readitAll();

	print $res;

	exit();

}

if ( $action == 'list' ) {

	$notifys = Notify ::items( $iduser1, [
		"status" => [
			0,
			1
		]
	] );

	$data = [
		"list" => $notifys[ 'list' ]
	];

	$list = json_encode_cyr( $data );

	print $list;

	exit();

}