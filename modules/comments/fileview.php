<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$id     = $_REQUEST['id'];
$action = $_REQUEST['action'];

if($action == "delete"){

	$fid = $_GET['fid'];

	Salesman\Comments::deleteFile($id, $fid);

}

$fids = yexplode(";", $db -> getOne("select fid from ".$sqlname."comments WHERE id = '$id' and identity = '$identity'"));

$string = '';

if(!empty($fids)){

	foreach ($fids as $fid){

		$ftitle    = $db -> getOne("select ftitle from ".$sqlname."file WHERE fid = '$fid' and identity = '$identity'");

		$a = (isViewable($ftitle)) ? '<A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\',\'\',\'\',\''.$ftitle.'\')" title="Просмотр"><i class="icon-eye broun"></i></A>&nbsp;' : '';

		$string .= '
		<div class="p10 inline infodiv mr5 mb5">
			'.get_icon2($ftitle).'&nbsp;'.$ftitle.'&nbsp;'.$a.'<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите Удалить файл?\\nФайл будет Удален из системы.\'); if (cf)$(\'#filelist\').load(\'modules/comments/fileview.php?id='.$id.'&fid='.$fid.'&action=delete\');" title="Удалить"><i class="icon-cancel-circled red"></i></A>
		</div>
		';

	}

	print '<div class="block">'.$string.'<input name="fid_old" id="fid_old" type="hidden" value="'.yimplode(";",$fids).'"></div>';

}