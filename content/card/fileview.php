<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$cid    = (int)$_REQUEST['cid'];
$action = $_REQUEST['action'];

if ( $action == "delete" ) {

	$fid = $_GET['fid'];

	$fname = $db -> getOne( "select fname from ".$sqlname."file where fid='".$fid."' and identity = '$identity'" );

	@unlink( "../files/".$fpath.$fname );

	//удалим запись о файле
	$db -> query( "delete from ".$sqlname."file where fid = '".$fid."' and identity = '$identity'" );

	//удалим запись о файле в истории

	//составим массив файлов в записи
	$fid_old = $db -> getOne( "select fid from ".$sqlname."history WHERE cid='".$cid."' and identity = '$identity'" );

	//если есть файлы, то преобразуем в массив
	if ( $fid_old != '' )
		$fidd = explode( ";", $fid_old );

	//если файлов нет, то создадим пустой
	else $fidd = [];

	//соберем новый массив
	if ( count( $fidd ) > 0 ) {

		$j = 0;
		for ( $i = 0, $iMax = count( $fidd ); $i < $iMax; $i++ ) {
			//если fid не равен удаляемому файлу, то включим в новый массив
			if ( $fidd[ $i ] != $fid ) {
				$fid2[ $j ] = $fidd[ $i ];
				$j++;
			}
		}
	}

	$fid_new = implode( ";", $fid2 );

	//запишем новый массив файлов, уже без удаляемого
	$db -> query( "update ".$sqlname."history set fid = '".$fid_new."' where cid = '".$cid."' and identity = '$identity'" );

}

$fidd = $db -> getOne( "select fid from ".$sqlname."history WHERE cid = '$cid' and identity = '$identity'" );
$fids = yexplode( ";", $fidd );
//print $db -> lastQuery();

if ( !empty( $fids ) ) {

	foreach ( $fids as $fid ) {

		$result2 = $db -> getRow( "select * from ".$sqlname."file WHERE fid = '$fid' and identity = '$identity'" );
		$ftitle  = $result2["ftitle"];
		$fname   = $result2["fname"];

		print '<div class="infodiv flex-string">'.get_icon2( $ftitle ).'&nbsp;'.$ftitle.'&nbsp;<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите Удалить файл?\nФайл будет Удален из системы.\');if (cf)refresh(\'filelist\', \'content/card/fileview.php?cid='.$cid.'&fid='.$fid.'&action=delete\');" title="Удалить"><i class="icon-cancel red"></i></A>&nbsp;</div>';

	}

	print '<input name="fid_old" id="fid_old" type="hidden" value="'.yimplode( ";", $fids ).'">';

}
?>