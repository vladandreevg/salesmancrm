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

$str = explode(" ", $datum);
$str = explode("-", $str[0]);

$year = $str[0];
$mon  = $str[1];
$day  = $str[2];

//указываем путь к папке записей + имя файла
$file = $sip_path.$year."/".$mon."/".$day."/".$file;

stream_context_set_default( [
	'ssl' => [
		'verify_peer' => false,
		'verify_peer_name' => false,
	],
]);
$Headers = @get_headers($file);
if (strpos($Headers[0], '200') === false) {
	$file = str_replace("wav", "mp3", $file);
}

if (strpos($Headers[0], '200')) {

	if (!stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE') || !stristr($_SERVER['HTTP_USER_AGENT'], 'Trident/')) {
		print '<audio src="'.$file.'" autoplay="autoplay" controls="controls" style="width:100%">Нет поддержки HTML5 Audio</audio>';
	}
	else {
		print '<embed src="'.$file.'" autostart="true" loop="false"></embed>';
	}

}
else {
	print '
	<div class="warning text-left">
		<span><i class="icon-attention red icon-3x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>
		Файл отсутствует или к нему нет доступа.<br>
		Файл записи: <a href="'.$file.'" title="Открыть" target="blank">'.$file.'</a>
	</div>';
}