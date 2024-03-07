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

include $rootpath."/content/pbx/onlinepbx/sipparams.php";
include $rootpath."/content/pbx/onlinepbx/mfunc.php";

$id  = $_REQUEST['id'];
$uid = $_REQUEST['uid'];

if ($id) $f = $db -> getRow("SELECT datum, file, uid FROM {$sqlname}callhistory WHERE id = '$id' and identity = '$identity'");
if ($uid) $f = $db -> getRow("SELECT datum, file, uid FROM {$sqlname}callhistory WHERE uid = '$uid' and identity = '$identity'");

$res = doMethod("record", $s = [
	"api_salt" => $api_user,
	"api_key"  => $api_secret,
	"call_id"  => $f['uid']
]);

$file = $res['data'];

print '<audio src="'.$file.'" autoplay="autoplay" controls="controls" style="width:100%">Нет поддержки HTML5 Audio</audio>';