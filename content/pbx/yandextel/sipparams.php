<?php
$action = $_REQUEST['action'];

//данные для yandetel.js
if ($action == 'js') {

	$rootpath = dirname( __DIR__, 3 );

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/auth.php";
	require_once $rootpath."/inc/settings.php";
	require_once $rootpath."/inc/func.php";

}
$iduser1  = $GLOBALS['iduser1'];
$identity = $GLOBALS['identity'];

$res             = $db -> getRow("select * from {$sqlname}services WHERE folder = 'yandextel' and identity = '$identity'");
$ytelset['api_key']  = rij_decrypt($res["user_key"], $skey, $ivc);
$ytelset['dobnumer'] = $db -> getOne("select phone_in from  {$sqlname}user where iduser = '$iduser1' and identity = '$identity'");

if ($action == 'js') {

	$dann = json_encode($ytelset);
	print $dann;

}