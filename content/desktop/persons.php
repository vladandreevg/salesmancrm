<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.25          */
/* ============================ */

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


$header = [
	"tip"    => $fieldsNames[ 'person' ][ 'loyalty' ],
	"title"  => $fieldsNames[ 'person' ][ 'person' ],
	"client" => $fieldsNames[ 'person' ][ 'clid' ],
	"phone"  => $fieldsNames[ 'person' ][ 'tel' ],
	"mob"    => $fieldsNames[ 'person' ][ 'mob' ],
	"email"  => $fieldsNames[ 'person' ][ 'mail' ],
	"user"   => $fieldsNames[ 'person' ][ 'iduser' ],
	"act"    => "Действия"
];

$sort = get_people( $iduser1 );

$q = "
SELECT
	{$sqlname}personcat.pid as pid,
	{$sqlname}personcat.person as person,
	{$sqlname}personcat.ptitle as ptitle,
	{$sqlname}personcat.clid as clid,
	{$sqlname}personcat.tel as phone,
	{$sqlname}personcat.mail as email,
	{$sqlname}personcat.mob as mob,
	{$sqlname}personcat.iduser as iduser,
	{$sqlname}user.title as user,
	{$sqlname}loyal_cat.title as loyalty,
	{$sqlname}loyal_cat.color as color,
	{$sqlname}clientcat.title as client
FROM {$sqlname}personcat
LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
LEFT JOIN {$sqlname}loyal_cat ON {$sqlname}personcat.loyalty = {$sqlname}loyal_cat.idcategory
LEFT JOIN {$sqlname}clientcat ON {$sqlname}personcat.clid = {$sqlname}clientcat.clid
WHERE {$sqlname}personcat.pid > '0' ".str_replace( "iduser", $sqlname."personcat.iduser", get_people( $iduser1 ) )." and {$sqlname}personcat.identity = '$identity' ORDER BY pid DESC LIMIT 0, ".$num_client;

$result = $db -> query( $q );
while ( $da = $db -> fetch( $result ) ) {

	$phone   = '';
	$mob     = '';
	$history = '';
	$color   = '';
	$rcolor  = '';
	$sup     = '';
	$task    = '';
	$deal    = '';

	if ( !$da[ 'color' ] ) $rcolor = 'transparent';
	else $rcolor = $da[ 'color' ];

	$isaccess = get_accesse( 0, (int)$da[ 'clid' ] );

	$array  = explode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $da['phone'] ) ) );
	$phone  = formatPhoneUrl2( array_shift( $array ), $da['clid' ], $da['pid' ] );

	$array1 = explode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $da['mob'] ) ) );
	$mob    = formatPhoneUrl2( array_shift( $array1 ), $da['clid' ], $da['pid' ] );

	$persons[] = [
		"pid"      => $da[ 'pid' ],
		"person"   => $da[ 'person' ],
		"ptitle"   => $da[ 'ptitle' ],
		"clid"     => $da[ 'clid' ],
		"client"   => $da[ 'client' ],
		"relation" => $da[ 'loyalty' ],
		"rcolor"   => $rcolor,
		"phone"    => $phone,
		"mob"      => $mob,
		"email"    => link_it( $da[ 'email' ] ),
		"iduser"   => $da[ 'iduser' ],
		"user"     => $da[ 'user' ],
		"change"   => $isaccess,
		"color"    => $color
	];

}

$data = [
	"header" => $header,
	"person" => $persons
];

//print_r($data);
print json_encode_cyr( $data );