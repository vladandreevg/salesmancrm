<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$q = texttosmall( $_REQUEST[ "q" ] );

$clid = $_REQUEST[ 'clid' ];
$cat  = $_REQUEST[ 'pr_cat' ];

$current = ( (int)$clid > 0 ) ? getClientData( $clid, "priceLevel" ) : 'price_1';
$sort    = ( $cat != '' ) ? " and pr_cat IN ($cat)" : "";

$result = $db -> query( "
	SELECT
	    title, $current, price_in, edizm, nds, artikul, n_id
	FROM ".$sqlname."price
	WHERE
		(title LIKE '%$q%' or artikul LIKE '%$q%') and
		archive != 'yes'
		$sort and
		identity = '$identity'
	" );

//print $db -> lastQuery();

while ( $data = $db -> fetch( $result ) ) {

	$str   = '';
	$total = 0;

	//проверим подключение модуля Склад
	if ( $isCatalog == 'on' ) {

		//запросим информацию по наличию на складе
		$kol     = $db -> getOne( "select SUM(kol) as kol from ".$sqlname."modcatalog_skladpoz where prid='$data[n_id]' and `status` = 'in' and identity = '$identity'" ) + 0;
		$kol_res = $db -> getOne( "select SUM(kol) as kol from ".$sqlname."modcatalog_reserv where prid='$data[n_id]' and identity = '$identity'" ) + 0;

		$total += $kol - $kol_res;

		$str .= "|".$total."|".$kol."|".$kol_res;

	}

	print $data[ 'title' ]."|".num_format( $data[ $current ] )."|".num_format( $data[ 'price_in' ] )."|".$data[ 'edizm' ]."|".num_format( $data[ 'nds' ] )."|".$data[ 'artikul' ]."|".$data[ 'n_id' ].$str."\n";

}