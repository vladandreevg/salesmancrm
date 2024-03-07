<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// воспроизведение записи звонка

error_reporting(E_ERROR);

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

if ($_REQUEST['id']) {
	$result = $db -> getRow("SELECT datum,file FROM {$sqlname}callhistory WHERE id = '".$_REQUEST['id']."' and identity = '$identity'");
}
elseif ($_REQUEST['uid']) {
	$result = $db -> getRow("SELECT datum,file FROM {$sqlname}callhistory WHERE uid = '".$_REQUEST['uid']."' and identity = '$identity'");
}

$file  = $result['file'];
$datum = $result['datum'];

$result_set = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '$identity'");
$sip_path   = $result_set["sip_path"];
$active     = $result_set["active"];
$tip        = $result_set["tip"];
?>
<div class="zagolovok">Прослушивание файла</div>
<div id="music1">
	<?php
	// если интеграция имеет особый метод получения записи, то подгрузим его
	if(file_exists($rootpath."/content/pbx/$tip/play.php")) {
		include $rootpath."/content/pbx/$tip/play.php";
	}
	// для остальных просто воспроизводим файл
	else {
		print '<audio src="'.$file.'" autoplay="autoplay" controls="controls" style="width:100%">Нет поддержки HTML5 Audio</audio>';
	}
	?>
</div>
<script>
	$('#dialog').css('width', '503px');
</script>