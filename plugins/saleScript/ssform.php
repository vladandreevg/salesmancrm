<?php
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2019.x          */
/* ============================ */

error_reporting(E_ERROR);

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$fpath = '';

if($isCloud == true) {

	//создаем папки хранения файлов
	if (!file_exists("data/".$identity)) {

		mkdir("data/".$identity, 0777);
		chmod("data/".$identity, 0777);

	}

	$fpath = $identity.'/';

}

//загружаем настройки
$settings = json_decode(file_get_contents($ypath.'data/'.$fpath.'settings.json'),true);
$settings['iduser'] = $iduser1;

$settings['foruser'] = (in_array($iduser1, $settings['forusers'])) ? true : false;

print json_encode_cyr($settings);

exit();