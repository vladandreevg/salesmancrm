<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$rezult = [
	'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i><span class="visible-iphone">Отвечен</span>',
	'CANCEL'     => '<i class="icon-minus-circled red" title="Отвечен"></i><span class="visible-iphone">Отменен</span>',
	'NOANSWER'   => '<i class="icon-minus-circled red" title="Не отвечен"></i><span class="visible-iphone">Не отвечен</span>',
	'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i><span class="visible-iphone">Не отвечен</span>',
	'TRANSFER'   => '<i class="icon-forward-1 gray2" title="Переадресация"></i><span class="visible-iphone">Переадресация</span>',
	'BREAKED'    => '<i class="icon-off red" title="Прервано"></i><span class="visible-iphone">Прервано</span>',
	'BUSY'       => '<i class="icon-block-1 broun" title="Занято"></i><span class="visible-iphone">Занято</span>',
	'CONGESTION' => '<i class="icon-help red" title="Перегрузка канала"></i><span class="visible-iphone">Перегрузка канала</span>',
	'FAILED'     => '<i class="icon-cancel-squared red" title="Ошибка соединения"></i><span class="visible-iphone">Ошибка соединения</span>'
];
$colors = [
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'BUSY'      => 'broun'
];
$direct = [
	'inner'   => '<i class="icon-arrows-cw smalltxt broun" title="Внутренний"></i>',
	'income'  => '<i class="icon-down-big smalltxt green" title="Входящий"></i>',
	'outcome' => '<i class="icon-up-big smalltxt blue" title="Исходящий"></i>'
];

$numhist = (int)$_REQUEST['numhist'];
if ($numhist == '') {
	$numhist = 20;
}

$clid = (int)$_REQUEST['clid'];
$pid  = (int)$_REQUEST['pid'];
$did  = (int)$_REQUEST['did'];

if ($hd == 'show') {
	print '<DIV class="zagolovok"><B>Звонки</B></DIV><hr>';
}

$page           = (int)$_REQUEST['page'];
$lines_per_page = 20;

if ($pid > 0) {
	$sort = " and {$sqlname}callhistory.pid = '$pid'";
}

if ($clid > 0) {

	$pids = $db -> getCol("SELECT pid FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity'");

	$s = (!empty($pids)) ? " or {$sqlname}callhistory.pid IN (".implode(",", $pids).")" : '';

	$sort = " and ({$sqlname}callhistory.clid = '$clid' $s)";

}

$query = "
SELECT
	DISTINCT({$sqlname}callhistory.uid) as uid,
	{$sqlname}clientcat.title as title,
	{$sqlname}clientcat.clid as clid,
	{$sqlname}personcat.person as person,
	{$sqlname}personcat.pid as pid,
	{$sqlname}callhistory.id as id,
	{$sqlname}callhistory.direct as direct,
	{$sqlname}callhistory.res as res,
	{$sqlname}callhistory.sec as sec,
	{$sqlname}callhistory.file as file,
	{$sqlname}callhistory.iduser as iduser,
	{$sqlname}callhistory.datum as datum,
	{$sqlname}callhistory.src as src,
	{$sqlname}callhistory.dst as dst,
	{$sqlname}user.title as user
FROM {$sqlname}callhistory
	LEFT JOIN {$sqlname}user ON {$sqlname}callhistory.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}personcat ON {$sqlname}callhistory.pid = {$sqlname}personcat.pid
	LEFT JOIN {$sqlname}clientcat ON {$sqlname}callhistory.clid = {$sqlname}clientcat.clid
WHERE
	{$sqlname}callhistory.id > 0 
	$sort and
	{$sqlname}callhistory.identity = '$identity'
ORDER BY {$sqlname}callhistory.datum DESC";

$result    = $db -> query($query);
$all_lines = $db -> numRows($result);

$page      = empty( $page ) || $page <= 0 ? 1 : $page;

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;
$count_pages    = ceil($all_lines / $lines_per_page);

$query .= " LIMIT $lpos,$lines_per_page";

$result = $db -> query($query);

$all = $pid > 0 || $clid > 0 ? $db -> numRows( $result ) : 0;

//print $all;
//print $query;

$string = '';

if ($all > 0) {

	while ($data = $db -> fetch($result)) {

		$dur    = '-';
		$play   = '<i class="icon-volume-up gray" title="Разговор не записан"></i>';
		$did    = '<span class="gray">Линия не определена</span>';
		$client = '';
		$person = '';
		$dur    = '';

		$manpro = $data['user'];

		if ($data['clid'] > 0) {
			$client = $data['title'];
		}

		if ($data['pid'] > 0) {
			$person = $data['person'];
		}

		if ($data['sec'] > 0) {

			$min = (int)($data['sec'] / 60); //число минут
			$sec = $data['sec'] - $min * 60; //число секунд

			if ($sec < 10) {
				$sec = '0'.$sec;
			}
			if (strlen($sec) > 2) {
				$sec = substr( $data['sec'], 0, -1 );
			}

			$dur = gmdate("i:s", $data['sec']);

			//$dur = $min.':'.$sec;

			if ($data['file'] != '' && $data['file'] != "0") {
				$play = '<a href="javascript:void(0)" onClick="doLoad(\'content/pbx/play.php?id='.$data['id'].'\')" title="Прослушать запись"><i class="icon-volume-up blue"></i></a>';
			}

		}

		if ((int)$data['did'] > 0) {
			$did = $data['did'];
		}

		$string .=
			'<TR class="th35 ha">
				<TD class="w80 text-center">
					<div class="smalltxt">'.str_replace(",", "", get_sfdate($data['datum'])).'</div>
				</TD>
				<TD class="w30 text-center">'.strtr($data['direct'], $direct).'</TD>
				<TD class="w30 text-center">
				<b class="'.strtr($data['res'], $colors).'">'.strtr($data['res'], $rezult).'</b></TD>
				<TD class="phone">
					<div>Источник: '.formatPhoneUrl2($data['src']).'</div>
					<div>Назначение: '.formatPhoneUrl2($data['dst']).'</div>
				</TD>
				<TD class="w100 text-right">'.$dur.'&nbsp;'.$play.'</TD>
				<TD class="w120 hidden-netbook"><span class="ellipsis" title="'.$manpro.'">'.$manpro.'</span></TD>
			</TR>';


	}

}

print '<table id="zebraTable"><tbody>'.$string.'</tbody></table>';

if ($count_pages > 1) {

	for ($i = 1; $i <= $count_pages; $i++) {

		$select .= '<option value="'.$i.'" '.($i == $page ? 'selected' : '').'>&nbsp;&nbsp;'.$i.'&nbsp;&nbsp;</option>';

	}

	$j = $page + 1;
	$k = $page - 1;

	print '<hr><div class="viewdiv" id="pages" style="z-index: 1">';
	if ($page > 1) {
		print 'Страница: <div onclick="callsload(\''.$k.'\')" data-page="'.$k.'"><</div>';
	}
	print '<span class="select inline">&nbsp;<select id="cpage" name="cpage" onchange="callsload2()">'.$select.'</select>&nbsp;</span>';
	if ($page < $count_pages) {
		print '<div onclick="callsload(\''.$j.'\')" data-page="'.$j.'">></div>';
	}

}

if ($all == 0) {
	print '<div class="fcontainer">Звонков нет</div>';
}

?>
<script>

	var stickwidthH = $("#historyMore").width();
	var $elmnt = '#callhistory';

	$(function () {

		$($elmnt).find("#pages").css({"bottom": "0px", "position": "fixed", "width": stickwidthH + "px"});

	})

</script>