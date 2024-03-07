<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.x          */
/* ============================ */

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$users = get_people( $iduser1, 'yes' );

$count = [];

//для модуля сборщика лидов
$mdwset       = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
$leadsettings = json_decode( $mdwset['content'], true );
$coordinator  = $leadsettings["leadСoordinator"];
$operators    = $leadsettings["leadOperator"];

//print_r($leadsettings);

$sort1 = $sort2 = '';

if ( $mdwset['active'] == 'on' ) {

	if ( $iduser1 != $coordinator ) {
		$sort1 = $sort2 = (!empty( $users )) ? "and iduser IN (".yimplode( ",", $users ).")" : "";
	}
	if ( $leadsettings['leadMethod'] == 'free' ) {
		$sort1 = '';
	}

	$allopen = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE status = '0' $sort1 and identity = '$identity'" );

	$allwork = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE status = '1' $sort2 and identity = '$identity'" );

	$count['leads'] = $allopen + $allwork;

}

$d0   = current_datum();
$d5   = current_datum( '-5' );
$d5p  = current_datum( '5' );
$d10p = current_datum( '10' );
$d10m = current_datum( '-10' );

//считаем количество счетов к оплате не просроченных и просроченных

//не просроченные
$c = $db -> getOne( "
	SELECT 
		COUNT(*) as count 
	FROM {$sqlname}credit 
	WHERE 
		{$sqlname}credit.do != 'on' and 
		({$sqlname}credit.datum_credit BETWEEN '$d10p 00:00:00' and '$d10m 23:59:59') and 
		{$sqlname}credit.idowner IN (".implode( ",", $users ).") and 
		{$sqlname}credit.identity = '$identity'
	" );

//просроченные
$d = $db -> getOne( "
	SELECT
		COUNT(*) as count
	FROM {$sqlname}credit
	WHERE
		{$sqlname}credit.do != 'on' and
		{$sqlname}credit.datum_credit < '$d0' and
		{$sqlname}credit.idowner IN (".implode( ",", $users ).") and
		{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' and identity = '$identity') and
		{$sqlname}credit.identity = '$identity'
	" );

$count['payments'] = $c + $d;

//считаем сделки
$c = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE (datum_plan BETWEEN '$d10p 00:00:00' and '$d10m 23:59:59') and close != 'yes' and {$sqlname}dogovor.iduser IN (".implode( ",", $users ).") and identity = '$identity'" );

$d = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE (datum_end BETWEEN '$d10p 00:00:00' and '$d10m 23:59:59') and {$sqlname}dogovor.iduser IN (".implode( ",", $users ).") and identity = '$identity'" );

$e = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}complect WHERE doit != 'yes' and (data_plan BETWEEN '$d10p 00:00:00' and '$d10m 23:59:59') and iduser IN (".implode( ",", $users ).") and identity = '$identity'" );

$count['deals'] = $c + $d + $e;

//счетчик комментариев
$com = 0;

$c = $db -> getCol( "
	SELECT 
		{$sqlname}comments.id 
	FROM {$sqlname}comments 
	WHERE 
		{$sqlname}comments.idparent = '0' AND 
		{$sqlname}comments.id IN (SELECT idcomment FROM {$sqlname}comments_subscribe WHERE iduser = '$iduser1') AND 
		{$sqlname}comments.isClose != 'yes' AND 
		DATEDIFF(NOW(), {$sqlname}comments.lastCommentDate) < 5 AND
		{$sqlname}comments.identity = '$identity' 
	ORDER BY {$sqlname}comments.lastCommentDate DESC
" );

if ( !empty( $c ) ) {

	$c    = implode( ",", $c );
	$coms = [];

	$results = $db -> query( "SELECT id, idparent FROM {$sqlname}comments WHERE idparent IN ($c) and identity = '$identity' ORDER BY datum DESC LIMIT 10" );
	while ($datas = $db -> fetch( $results )) {

		if ( !in_array( $datas['idparent'], $coms ) ) {

			$coms[] = $datas['idparent'];
			$com++;

		}

	}

}

$count['comments'] = $com + 0;

print $result = json_encode_cyr( $count );