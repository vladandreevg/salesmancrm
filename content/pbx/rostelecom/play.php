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

include $rootpath."/content/pbx/rostelecom/sipparams.php";
include $rootpath."/content/pbx/rostelecom/mfunc.php";

$id  = $_REQUEST['id'];
$uid = $_REQUEST['uid'];

$options = $db -> getOne("SELECT params FROM {$sqlname}customsettings WHERE tip = 'sip' and identity = '$identity'");
$options = json_decode($options, true);
$domain  = $options['domain'];

if ((int)$id > 0) {
	$query = "SELECT uid FROM {$sqlname}callhistory WHERE id = '$id' and identity = '$identity'";
}
elseif (!empty($uid)) {
	$query = "SELECT uid FROM {$sqlname}callhistory WHERE uid = '$uid' and identity = '$identity'";
}

$uuid = $db -> getOne($query);

$rez = doMethod("record", $s = [
	"api_key"  => $api_key,
	"api_salt" => $api_salt,
	"domain"   => $domain,
	"uid"      => $uuid
]);

$file = $rez['data']['url'];

if ($rez['data']['result'] == 0) {
	print '
	<div class="infodiv text-left">
		Файл записи: <a href="'.$file.'" title="Открыть" target="blank">'.$file.'</a>
	</div>';
}

if ($rez['data']['result'] != 0) {
	print '
	<div class="warning text-left">
		Ошибка: '.$rez['data']['resultMessage'].'
	</div>';
}

print '<audio src="'.$file.'" autoplay="autoplay" controls="controls" style="width:100%">Нет поддержки HTML5 Audio</audio>';