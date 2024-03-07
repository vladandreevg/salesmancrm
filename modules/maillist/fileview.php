<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
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

$mid    = $_REQUEST['mid'];
$action = $_REQUEST['action'];
$fid    = $_REQUEST['fid'];

$string = '';

if ($mid > 0){

	$flist = $db -> getOne("select file from ".$sqlname."mail where mid = '".$mid."' and identity = '$identity'");
	$files = yexplode(";", $flist);

	if ($action == "delete"){

		$files = arraydel($files, $fid);
		$nfiles = implode(";", $files);

		$db -> query("update ".$sqlname."mail set file = '".$nfiles."' where mid = '".$mid."'");

	}

	foreach ($files as $file){

		$res = $db -> getRow("select * from ".$sqlname."file where fid='".$file."' and identity = '$identity'");

		if ($res['ftitle'] != ''){

			$string .= '<input type="hidden" name="efile[]" id="efile[]" value="'.$file.'">[&nbsp;'.get_icon2($res['ftitle']).'&nbsp;'.$res['ftitle'].'&nbsp;<A href="#" onClick="cf=confirm(\'Вы действительно хотите Удалить файл из рассылки?\nФайл Не будет Удален из системы.\');if (cf)$(\'#filelist\').load(\'modules/maillist/fileview.php?mid='.$mid.'&fid='.$file.'&action=delete\');" title="Удалить"><i class="icon-cancel-circled red"></i></A>&nbsp;]&nbsp;';

		}

	}

	print $string;

}
?>