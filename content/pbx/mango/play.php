<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

// формируем html-код для воспроизведения записи звонка

global $rootpath;

include $rootpath."/content/pbx/mango/sipparams.php";
include $rootpath."/content/pbx/mango/mfunc.php";

$id  = (int)$_REQUEST['id'];
$uid = $_REQUEST['uid'];

if ($id > 0) {
	$f = $db -> getRow("SELECT datum, file, uid FROM {$sqlname}callhistory WHERE id = '$id' and identity = '$identity'");
}
if (!empty($uid)) {
	$f = $db -> getRow("SELECT datum, file, uid FROM {$sqlname}callhistory WHERE uid = '$uid' and identity = '$identity'");
}

$file = doMethod("play", [
	"api_key"      => $api_key,
	"api_salt"     => $api_salt,
	"recording_id" => $f['file']
])['file'];

print '<audio src="'.$file.'" autoplay="autoplay" controls="controls" style="width:100%">Нет поддержки HTML5 Audio</audio>';