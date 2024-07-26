<?php
if ( $ivc == '' ) {

	$skey = 'vanilla'.pow( $identity + 7, 3 ).'round'.pow( $identity + 3, 2 ).'robin';
	$ivc  = $db -> getOne( "SELECT ivc FROM ".$sqlname."settings WHERE id = '$identity'" );

}

$res        = $db -> getRow( "SELECT * FROM {$sqlname}services WHERE folder = 'novofon' and identity = '$identity'" );
$api_key    = rij_decrypt( $res["user_key"], $skey, $ivc );
$api_secret = rij_decrypt( $res["user_id"], $skey, $ivc );