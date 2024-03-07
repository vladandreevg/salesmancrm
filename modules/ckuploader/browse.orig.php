<?php
/**
 * Загрузчик изображений
 */

error_reporting(E_ERROR);

include "../../inc/config.php";
include '../../inc/dbconnector.php';
include '../../inc/auth.php';
include "../../inc/settings.php";
include "../../inc/func.php";

require_once "../../inc/class/Upload.php";

$type    = $_REQUEST['type'];
$action  = $_REQUEST['action'];
$funcNum = $_GET['CKEditorFuncNum'];

$path = '../../files/';

if ($type == 'ymail') {//для почтовика

	$path .= $fpath.'ymail/inbody/';
	$url  = 'files/'.$fpath.'ymail/inbody/';

}
elseif ($type == 'kb') {//для базы знаний

	$path .= $fpath.'kb/';
	$url  = 'files/'.$fpath.'kb/';

}

//проверяем папку для загрузки и если её нет, то создаем
if (!file_exists($path)) {

	mkdir($path, 0777);
	chmod($path, 0777);

}

if ($action == 'upload') {

	if (filesize($_FILES['upload']['tmp_name']) > 0 and $_FILES['upload']['name'] != '') {

		$ftitle = basename($_FILES['upload']['name']);
		$tim    = time();
		$fname  = $tim.".".end(explode(".", $ftitle));

		$ext_allow = array(
			'PNG',
			'JPEG',
			'JPG',
			'GIF'
		);

		$uploadfile = $uploaddir.$fname;

		$cur_ext = strtoupper(end(explode(".", $ftitle)));

		if (in_array($cur_ext, $ext_allow)) {

			if ((filesize($_FILES['upload']['tmp_name']) / 1000000) > 1000000) {

				$message = 'Ошибка: Превышает размеры!';
				$url     = '';

			}
			else {
				if (move_uploaded_file($_FILES['upload']['tmp_name'], $uploadfile)) {

					$url = $url.$fname;

				}
				else {

					$message = 'Ошибка:'.$_FILES['upload']['error'];
					$url     = '';

				}
			}

		}
		else {

			$message = 'Ошибка: Загружайте только изображения PNG, JPEG, JPG, GIF!';
			$url     = '';

		}

	}

	$funcNum = $_GET['CKEditorFuncNum'];

	echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', '$message');</script>";

}
else {

	$files = scandir($path, 1);
	$list  = array();

	foreach ($files as $file) {

		$ext = texttosmall(array_pop(yexplode(".", $file)));

		if (in_array($ext, array(
			"png",
			"gif",
			"jpg",
			"jpeg"
		))) {

			$size = getimagesize($path.$file);

			if ($size[0] > 100) $list[] = array(
				"name"  => $file,
				"time"  => filemtime($path.'/'.$file),
				"date"  => date("d-m-Y", filemtime($path.'/'.$file)),
				"size"  => num_format(round(filesize($path.'/'.$file) / 1024, 2)),
				"width" => $size[0]
			);

		}

	}

	function ccmp($a, $b) {
		return $b['time'] > $a['time'];
	}

	usort($list, 'ccmp');

	?>
	<!DOCTYPE html>
	<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Выбор изображения SalesMan CRM</title>
		<link rel="stylesheet" href="../../css/style.crm.css">
		<link rel="stylesheet" href="../../css/fontello.css">
		<script src="../../js/cash.min.js"></script>
		<style>

			.ymImagePreview {
				display    : block;
				overflow-x : hidden;
			}

			.ymImagePreview .picpreview {
				display         : inline-table;
				box-sizing      : border-box;
				margin          : 3px;
				padding         : 3px;
				width           : 100%;
				height          : 150px;
				background-size : cover !important;
				border          : 1px dotted #37474F;
			}

			.ymImagePreview .picpreview span {
				word-break : break-all !important;
				background : rgba(0, 0, 0, 0.5);
				color      : #fff;
				padding    : 3px;
			}

			.wp33 {
				width      : 25%;
				box-sizing : border-box;
			}

		</style>
	</head>
	<body>

	<div class="ymImagePreview flex-container">
	<?php

	foreach ($list as $img) {

		print '
		<div class="flex-string p5 wp33">
			<div class="picpreview hand relativ" style="background: url(/'.$url.$img['name'].') no-repeat center center;" onclick="selectImage(\'/'.$url.$img['name'].'\')" title="Выбрать">
				<span class="fs - 09 bottom">'.$img['date'].' [ '.$img['size'].' kb ]</span>
			</div>
		</div>
		';

	}

}

?>
</div>
	<script>

		function selectImage(url) {

			window.opener.CKEDITOR.tools.callFunction(<?=$funcNum?>, url, '');
			self.close();

		}
	</script>
<?php
exit();
?>