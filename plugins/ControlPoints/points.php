<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */

error_reporting( E_ERROR );
//ini_set('display_errors', 1);

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth_main.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

$action = $_REQUEST['action'];

$list = [];

// список всех Контрольных точек
$cpoints = $db -> getIndCol( "ccid", "SELECT title, ccid FROM {$sqlname}complect_cat WHERE identity = '$identity'" );

if($action == 'list') {

	$query  = "
		SELECT 
			* 
		FROM {$sqlname}complect 
		WHERE 
			{$sqlname}complect.id > 0 AND 
			{$sqlname}complect.doit != 'yes' AND 
			{$sqlname}complect.data_plan <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND
			{$sqlname}complect.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
			{$sqlname}complect.identity = '$identity'
		ORDER BY {$sqlname}complect.data_plan
	";

	$result = $db -> query( $query );
	while ( $da = $db -> fetch( $result ) ) {

		$days = round( diffDate2( $da[ 'data_plan' ] ) );

		$list[] = [
			"id"     => $da[ 'id' ],
			"point"  => $cpoints[ $da[ 'ccid' ] ],
			"did"    => $da[ 'did' ],
			"deal"   => current_dogovor( $da[ 'did' ] ),
			"date"   => $da[ 'data_plan' ],
			"rudate" => format_date_rus( $da[ 'data_plan' ] ),
			"days"   => $days,
			"color"  => $days > 0 ? 'greenbg-dark' : 'redbg-dark',
			"user"   => $da['iduser'] == $iduser1 ? NULL : current_user($da['iduser'], "yes")
		];

	}

	print json_encode_cyr( ["list" => $list] );

}
elseif($action == 'count'){

	$query  = "
		SELECT 
			COUNT(*) 
		FROM {$sqlname}complect 
		WHERE 
			{$sqlname}complect.id > 0 AND 
			{$sqlname}complect.doit != 'yes' AND 
			{$sqlname}complect.data_plan <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND
			{$sqlname}complect.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND 
			{$sqlname}complect.identity = '$identity'
	";

	print $db -> getOne($query) + 0;

}