<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$deid   = $_REQUEST['deid'];
$action = $_REQUEST['action'];

if ( $action == "delete" ) {

	$fname_del = $_REQUEST['fname'];
	$result    = $db -> getRow( "select * from ".$sqlname."contract WHERE deid='".$deid."' and identity = '$identity'" );
	$fname     = yexplode( ";", $result["fname"] );
	$ftitle    = yexplode( ";", $result["ftitle"] );
	$ftype     = yexplode( ";", $result["ftype"] );

	$j = 0;

	$fname1 = $ftitle1 = $ftype1 = [];

	//Формируем новый список файлов
	for ( $i = 0, $iMax = count( $fname ); $i < $iMax; $i++ ) {

		if ( $fname[ $i ] != $fname_del ) {

			$fname1[ $j ]  = $fname[ $i ];
			$ftitle1[ $j ] = $ftitle[ $i ];
			$ftype1[ $j ]  = $ftype[ $i ];
			$j++;
		}
	}

	//запишем новые файлы в базу
	$fname  = implode( ";", $fname1 );
	$ftitle = implode( ";", $ftitle1 );
	$ftype  = implode( ";", $ftype1 );

	$db -> query( "UPDATE ".$sqlname."contract SET ?u WHERE deid = '$deid' and identity = '$identity'", [
		'ftitle' => $ftitle,
		'fname'  => $fname,
		'ftype'  => $ftype
	] );

	//удалим указанный файл
	@unlink( $rootpath.'/files/'.$fpath.$fname_del );

	exit();
}

if ( $deid > 0 ) {

	$result = $db -> getRow( "select * from ".$sqlname."contract WHERE deid='".$deid."' and identity = '$identity'" );
	$ftitle = $result["ftitle"];
	$fname  = $result["fname"];

	$string = '';

	if ( $ftitle != '' ) {

		$ftitle = yexplode( ";", $ftitle );
		$fname  = yexplode( ";", $fname );

		for ( $i = 0, $iMax = count( $ftitle ); $i < $iMax; $i++ ) {

			$string .= '<div class="tags ha p5 fs-09">&nbsp;'.get_icon2( $ftitle[ $i ] ).'&nbsp;'.$ftitle[ $i ].'&nbsp;<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите Удалить файл?\\nФайл будет Удален из системы.\');if (cf)filedelete(\''.$deid.'\',\''.$fname[ $i ].'\');" title="Удалить"><i class="icon-cancel-circled red"></i></A>&nbsp;&nbsp;</div>';

		}

	}
	else {

		$string .= '<div class="ha p5 fs-09">Файлов нет</div>';

	}

	print "<div>$string</div>";

}