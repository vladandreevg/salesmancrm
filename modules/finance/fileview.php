<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

error_reporting(0);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

$id     = (int)$_REQUEST['id'];
$action = $_REQUEST['action'];
$flist  = $_REQUEST['flist'];

if ($action == "delete") {

	$fid = (int)$_REQUEST['fid'];

	$new_file = [];

	$file = $db -> getOne("SELECT fid FROM {$sqlname}budjet WHERE id='".$id."' and identity = '$identity'");

	$files = yexplode(';', $file);

	//Формируем новый список файлов
	foreach ($files as $file) {

		if ((int)$file != $fid) {
			$new_file[] = $file;
		}

	}

	$flist = yimplode(";", $new_file);

	//удалим файл
	$fname = $db -> getOne("select fname from {$sqlname}file where fid='".$fid."' and identity = '$identity'");

	@unlink($rootpath."/files/".$fpath.$fname);

	$db -> query("delete from {$sqlname}file where fid='$fid' and identity = '$identity'");
	$db -> query("update {$sqlname}budjet set fid = '$flist' where id='".$id."' and identity = '$identity'");

	print 'Готово';

	exit();

}

//если список не пуст, то выводим файлы из списка
if ($action == "") {

	$file = $db -> getOne("SELECT fid FROM {$sqlname}budjet WHERE id='".$id."' and identity = '$identity'");

	$files = yexplode(';', $file);

	foreach ($files as $file) {

		if ((int)$file > 0) {

			$ftitle = $db -> getOne("select ftitle from {$sqlname}file where fid='$file' and identity = '$identity'");

			print '
			<span class="infodiv bgwhite dotted p10 mb5 ha wp97">
				'.get_icon2($ftitle).$ftitle.'
				<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите Удалить файл?\\nФайл будет Удален из системы.\');if (cf)filedelete(\''.$id.'\', \''.$file.'\');" title="Удалить"><i class="icon-cancel-circled red"></i></A>
			</span>';

		}

	}
}