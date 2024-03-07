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

setcookie("tiphistory", $_REQUEST['tiphistory'], time() + 31536000);

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$rezult  = [
	'ANSWERED'  => 'Отвеченный',
	'NO ANSWER' => 'Не отвечен',
	'BUSY'      => 'Занято'
];
$ccolors = [
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'BUSY'      => 'broun'
];

$numhist = (int)$_REQUEST['numhist'];

if ($numhist == '') {
	$numhist = 6;
}

$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$action = untag($_REQUEST['action']);
$tt     = $_REQUEST['tt'];
$hd     = $_REQUEST['hd'];
$log    = $_REQUEST['log'];
$nolog  = $_COOKIE['nolog'];
$page   = (int)$_REQUEST['page'];


if ($hd == 'show') {
	print '<DIV class="zagolovok"><B>Активности</B></DIV><hr>';
}


$allow = get_accesse( $clid, $pid, $did );
$tiphistory = yexplode(",", (string)$_COOKIE['tiphistory']);
$lines_per_page = 10;


$sort .= $log == 'yes' ? " and {$sqlname}history.tip IN ('СобытиеCRM','ЛогCRM')" : " and {$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM')";

if ($did == 0) {

	if ($pid > 0) {
		$sort .= " and FIND_IN_SET('$pid', REPLACE({$sqlname}history.pid, ';',',')) > 0";
	}

	if ($clid > 0) {

		//пройдемся по контактам
		$pids = $db -> getCol("SELECT pid FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'");

		$s = [];
		foreach ($pids as $pi) {

			if ($pi > 0) {
				$s[] = "FIND_IN_SET('$pi', REPLACE({$sqlname}history.pid, ';',',')) > 0";
			}

		}

		$so = (!empty($s)) ? " OR (".implode(" OR ", $s).")" : "";

		$sort .= " and ({$sqlname}history.clid = '$clid' $so)";

	}

}
if ($did > 0) {
	$sort .= " and {$sqlname}history.did = '$did'";
}

if (!empty($tiphistory)) {
	$sort .= " and {$sqlname}activities.id IN (".implode( ",", $tiphistory ).")";
}

$colors = $db -> getIndCol("title", "SELECT title, color FROM {$sqlname}activities WHERE identity = '$identity'");

$query = "
	SELECT
		DISTINCT({$sqlname}history.cid),
		{$sqlname}history.tip,
		{$sqlname}history.datum,
		{$sqlname}history.datum_izm,
		{$sqlname}history.clid,
		{$sqlname}history.pid,
		{$sqlname}history.did,
		{$sqlname}history.uid,
		{$sqlname}history.fid,
		{$sqlname}history.iduser,
		{$sqlname}history.iduser_izm,
		{$sqlname}history.des,
		{$sqlname}activities.id,
		{$sqlname}activities.color as color,
		{$sqlname}clientcat.title as client,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}user.title as user
	FROM {$sqlname}history
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}history.clid
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}history.did
		LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}history.iduser
		LEFT JOIN {$sqlname}activities ON {$sqlname}activities.title = {$sqlname}history.tip
	WHERE
		{$sqlname}history.cid > 0
		$sort and
		{$sqlname}history.identity = '$identity'
	GROUP BY {$sqlname}history.cid
	ORDER BY {$sqlname}history.datum DESC
";

$result    = $db -> query($query);
$all_lines = $db -> numRows($result);

if ( empty($page) || $page <= 0) {
	$page = 1;
}

//print $page;

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;
$count_pages    = ceil($all_lines / $lines_per_page);

$query .= " LIMIT $lpos,$lines_per_page";

$result = $db -> getAll($query);

$html = '';

foreach ($result as $da) {

	$ttitle = $usertitle2 = $dog = $person = $task = $taskicon = $data2 = '';

	$data1 = get_hist($da['datum']);

	if ($da['datum_izm'] != null) {
		$data2 = get_hist( $da['datum_izm'] );
	}

	//сделка
	if ((int)$da['did'] > 0) {
		$dog = 'Сделка:&nbsp;<b><A href="javascript:void(0)" onClick="viewDogovor(\''.$da['did'].'\')">'.$da['dogovor'].'</a></b>&nbsp;&nbsp;<A href="javascript:void(0)" onclick="openDogovor(\''.$da['did'].'\')"><i class="icon-briefcase broun" title="Вы можете перейти к карточке данной Сделки"></i></A><br />';
	}


	//список контактов
	if ($da['pid'] != '') {

		$pers = yexplode(";", (string)$da['pid']);

		foreach ($pers as $per) {

			if((int)$per > 0) {
				$person .= 'Контакт:&nbsp;<b><A href="javascript:void(0)" onClick="viewPerson(\''.$per.'\')">'.current_person( $per ).'</a></b>&nbsp;&nbsp;<A href="javascript:void(0)" onclick="openPerson(\''.$per.'\')"<i class="icon-user-1 blue" title="Вы можете перейти к карточке Контакта"></i></A><br />';
			}

		}

	}

	if ($da['iduser_izm']) {
		$usertitle2 = current_user( $da['iduser_izm'] );
	}

	$color = ($da['color'] != "") ? $da['color'] : "#222";

	//напоминание
	$rtask = $db -> getRow("SELECT * FROM {$sqlname}tasks WHERE cid = '".$da['cid']."' and identity = '$identity' ORDER BY datum, totime");
	if ((int)$rtask['tid'] > 0) {

		$ttid     = (int)$rtask["tid"];
		$ttitle   = $rtask["title"];
		$tdes     = nl2br($rtask["des"]);
		$ttip     = $rtask["tip"];
		$tdatum   = $rtask["datum"];
		$ttotime1 = getTime((string)$rtask["totime"]);
		$status = ((int)$rtask["status"] == 2) ? '<i class="icon-block red sup"></i>' : '<i class="icon-ok green sup"></i>';

		$tcolor = ($ttip != "") ? strtr($ttip, $colors) : "#222";

		$t = ($tdes != '') ? '<div class="em fs-09 text-wrap">'.link_it($tdes).'</div>' : '';

		$task .= '
		<div id="tid_'.$ttid.'" class="hidden viewdiv mt10 p10">
			<B class="red">'.$ttotime1.'</B>&nbsp;<b>'.format_date_rus($tdatum).'</b>&nbsp;&nbsp;<span style="color:black; height:15px">Тип: </span><span style="color:'.$tcolor.';">'.$ttip.'</span>
			<div class="black mb5 mt5 Bold">'.$ttitle.'</div>
			'.$t.'
		</div>
		';

		$taskicon .= '
		<a href="javascript:void(0)" class="togglerbox gray blue" data-id="tid_'.$ttid.'" title="Связанное напоминание. Детали. Показать/Скрыть">
			<i class="icon-calendar-1 blue">'.$status.'</i>
		</a>&nbsp;
		';

	}

	$act = '';

	//кнопки действий
	if (
		( $allow == 'yes' && $hd != 'show' && (!in_array($da['tip'], ['СобытиеCRM', 'ЛогCRM'])) ) &&
		(
			( $userRights['delete'] || datestoday($da['datum']) == 0 ) &&
			( ((int)$da['iduser'] == $iduser1 || get_accesse( $clid, $pid, $did ) == 'yes') || $isadmin == 'on' )
		)
	) {

		$act = '
		<div class="panel">
			'.$taskicon.'
			<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите изменить запись?\');if (cf)editHistory(\''.$da['cid'].'\');" title="Редактировать" class="gray blue"><i class="icon-pencil green"></i></A>&nbsp;
			<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)deleteHistory(\''.$da['cid'].'\');" title="Удалить" class="gray red"><i class="icon-cancel-circled red"></i></A>
		</div>';


	}
	elseif($taskicon != '') {

		$act = '
		<div class="panel">
			'.$taskicon.'&nbsp;
		</div>';

	}

	//запись звонка
	$colls = '';
	if ($da['uid'] != '' && $da['uid'] != '0') {

		$rest = $db -> getRow("SELECT * FROM {$sqlname}callhistory WHERE uid = '$da[uid]' and identity = '$identity'");
		if ((int)$rest['id'] > 0) {

			$sec  = (int)$rest['sec'];
			$file = $rest['file'];
			$rez  = $rest['res'];

			$calltime = '';

			$min  = (int)($sec / 60); //число минут
			$sec2 = $sec - $min * 60; //число секунд

			if ($sec2 < 10) {
				$sec2 = '0'.$sec2;
			}
			if (strlen($sec2) > 2) {
				$sec2 = substr( $sec, 0, -1 );
			}

			$dur = $min.':'.$sec2;

			$play = ($file != '') ? '<a href="javascript:void(0)" onClick="doLoad(\'content/pbx/play.php?id='.$rest['id'].'\')" title="Прослушать запись"><i class="icon-volume-up blue"></i></a>' : '<i class="icon-volume-up gray list" title="Разговор не записан"></i>';

			$calltime = "&nbsp;Время: <b>".$dur."</b>;&nbsp;Прослушать: ".$play;

			$colls = '
			<div class="infodiv">
				'.$calltime.'
				Результат: <b class="'.strtr($rez, $ccolors).'">'.strtr($rez, $rezult).'</b>;
			</div>';

		}

	}

	//файлы
	$fids   = yexplode(";", (string)$da['fid']);
	$files = '';
	if (!empty($fids)) {

		foreach($fids as $fid) {

			$result2 = $db -> getRow("select ftitle, fname from {$sqlname}file WHERE fid='$fid' and identity = '$identity'");
			$ftitle  = $result2["ftitle"];
			$fname   = $result2["fname"];

			$view = '';

			if (isViewable($fname)) {
				$view = '<A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\',\'\',\'\')"><i class="icon-eye broun" title="Просмотр"></i></A>&nbsp;';
			}

			$files .= '
			<div class="pad5">
				'.$view.'
				'.get_icon2($ftitle).'&nbsp;<A href="javascript:void(0)" onclick="fileDownload(\''.$fid.'\')" title="Скачать"><B>'.$ftitle.'</B></A>&nbsp;['.num_format(filesize($rootpath."/files/".$fpath.$fname) / 1000).' kb.]
			</div>';

		}

	}

	$html .= '
	<DIV class="fcontainer bgwhite mb10 border-box box-shadow relativ focused">

		<div class="block p5">

			<div class="togglerbox hand" title="Детали. Показать/Скрыть" data-id="det_'.$da['cid'].'">

				<i class="icon-angle-down blue pull-aright1" id="mapic" title="Детали. Показать/Скрыть"></i>
				<b>'.get_sfdate($da['datum']).'</b>&nbsp;&nbsp;<span style="color:'.$color.';" title="'.$da['tip'].'"><b>'.$da['tip'].'</b>&nbsp;'.get_ticon($da['tip']).'</span>

			</div>

			<div class="fs-09 m10 pl20 pt10 hidden" id="det_'.$da['cid'].'">
				Добавил:&nbsp;<b>'.$da['user'].'</b><br>
				'.($usertitle2 != '' ? 'Изменил:&nbsp;<b>'.$usertitle2.'</b>&nbsp;<i class="green">'.get_hist($da['datum_izm']).'</i><br>' : '').$dog.$person.'
			</div>
			'.$act.'

		</div>

		'.$task.'

		<DIV class="fcontainer1 mt5 p10 bgwhite">

			<DIV class="cardBlock fs-10 flh-12" style="overflow: hidden" data-height="200">

				<div class="fieldblock block">

					<b class="fs-11">'.$ttitle.'</b>
					<div class="fs-10 text-wrap wp98 border-box">'.link_it(nl2br($da['des'])).'</div>

				</div>
				
			</DIV>
			'.($colls != '' ? '<div class="margtop10 fs-09">'.$colls.'</div>' : '').'
			'.($files != '' ? '<div class="viewdiv mt10 fs-09">'.$files.'</div>' : '').'

		</DIV>

	</DIV>';

}

print '<div style="overflow-y:auto;">'.$html.'</div>';

$ur = '';
if ($clid > 0) {
	$ur = 'clid='.$clid.'&tt=org';
}
if ($pid > 0) {
	$ur = 'pid='.$pid.'&tt=pers';
}
if ($did > 0) {
	$ur = 'did='.$did.'&tt=dog';
}

$select = '';

if ($count_pages > 1) {

	for ($i = 1; $i <= $count_pages; $i++) {
		$select .= '<option value="'.$i.'" '.($i == $page ? 'selected' : '').'>&nbsp;&nbsp;'.$i.'&nbsp;&nbsp;</option>';
	}


	$j = $page + 1;
	$k = $page - 1;

	if ($log != 'yes') {
		print '
		<div class="viewdiv sticked--bottom" id="pages" style="z-index: 1">'.($page > 1 ? 'Страница: <div onclick="cardloadHist(\''.$k.'\')" data-page="'.$k.'"><</div>&nbsp;' : '').'<span class="select inline">&nbsp;<select id="hpage" name="hpage" onchange="cardloadHist()">'.$select.'</select>&nbsp;</span>'.($page < $count_pages ? '<div onclick="cardloadHist(\''.$j.'\')" data-page="'.$j.'">></div>' : '');
	}
	else {
		print '
		<div class="viewdiv sticked--bottom" id="pages" style="z-index: 1">'.($page > 1 ? 'Страница: <div onclick="cardloadlog(\''.$k.'\')" data-page="'.$k.'"><</div>&nbsp;' : '').'<span class="select inline">&nbsp;<select id="hlpage" name="hlpage" onchange="cardloadlog()">'.$select.'</select>&nbsp;</span>'.($page < $count_pages ? '<div onclick="cardloadlog(\''.$j.'\')" data-page="'.$j.'">></div>' : '');
	}

}

if ($all_lines == 0) {
	print '<div class="fcontainer">Активностей нет</div>';
}

$element = ($log == 'yes') ? '#log' : '#history';
?>
<script>

	var stickwidthH = $("#historyMore").width();
	var $elmnt = '<?=$element?>';

	$(function () {

		$($elmnt).find('.cardBlock').each(function () {

			var hi = $(this).actual('height') + 0;

			if (hi > 60) {

				$(this).css('height', '100px').attr('data-height', 100);
				$(this).after('<div class="div-center blue hand cardResizer fs-07 paddtop10" title="Развернуть" data-pozi="close"><i class="icon-angle-down"></i>развернуть / свернуть<i class="icon-angle-down"></i></div>');

			}

		});

		$($elmnt).find("#pages").css({"bottom": "0px", "position": "fixed", "width": stickwidthH + "px"});

	})

</script>
