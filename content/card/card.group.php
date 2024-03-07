<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$pid  = (int)$_REQUEST['pid'];
$clid = (int)$_REQUEST['clid'];

$string = '';

if ( $clid > 0 ) {
	$result = $db -> query( "SELECT * FROM {$sqlname}grouplist WHERE clid = '".$clid."' and identity = '$identity'" );
}
if ( $pid > 0 ) {
	$result = $db -> query( "SELECT * FROM {$sqlname}grouplist WHERE pid = '".$pid."' and identity = '$identity'" );
}
while ($data = $db -> fetch( $result )) {

	$lnk     = '';
	$res     = $db -> getRow( "SELECT name, service FROM {$sqlname}group WHERE id = '".$data['gid']."' and identity = '$identity'" );
	$group   = $res["name"];

	if ( $userRights['group'] ) {
		$lnk = '<div class="inline p10 m0">
			<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)removeFromList(\''.$data['id'].'\',\''.$data['gid'].'\');" title="Удалить запись из группы"><i class="icon-cancel-circled red"></i></A>
		</div>';
	}

	$string .= '<div class="inline mr5 mb5 bluebg-sub">
		<div class="inline p10 m0">
			<i class="icon-users-1 blue">'.$s.'</i>&nbsp;&nbsp;'.$group.'
		</div>'.$lnk.'
	</div>';

}

if ( $string != '' ) {
	print $string;
}
else {
	print '<div class="p5 gray2">Вне групп</div>';
}