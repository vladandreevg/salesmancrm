<?php
set_time_limit(0);
error_reporting(E_ERROR);

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$users = $db -> getCol("SELECT iduser FROM ".$sqlname."user WHERE secrty = 'no'");
foreach ($users as $user){

	Salesman\Mailer::clearOtherMessages($user);
	Salesman\Mailer::clearOldMessages($user);

}