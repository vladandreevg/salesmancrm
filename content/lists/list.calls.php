<?php
/* ============================ */
/*         SalesMan CRM         */
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

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$sort = '';

function convertcsv($string) {
	//$string = iconv("UTF-8","CP1251", $string);
	return $string;
}

$va = [
	0 => 'ANSWERED',
	1 => 'NO ANSWER',
	2 => 'BUSY'
];
$di = [
	0 => 'income',
	1 => 'outcome',
	2 => 'inner'
];

$rezult = [
	'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i><span class="visible-iphone">Отвечен</span>',
	'ANSWER'     => '<i class="icon-ok-circled green" title="Отвечен"></i><span class="visible-iphone">Отвечен</span>',
	'CANCEL'     => '<i class="icon-minus-circled red" title="Отвечен"></i><span class="visible-iphone">Отменен</span>',
	'NOANSWER'   => '<i class="icon-minus-circled red" title="Не отвечен"></i><span class="visible-iphone">Не отвечен</span>',
	'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i><span class="visible-iphone">Не отвечен</span>',
	'TRANSFER'   => '<i class="icon-forward-1 gray2" title="Переадресация"></i><span class="visible-iphone">Переадресация</span>',
	'BREAKED'    => '<i class="icon-off red" title="Прервано"></i><span class="visible-iphone">Прервано</span>',
	'BUSY'       => '<i class="icon-block-1 broun" title="Занято"></i><span class="visible-iphone">Занято</span>',
	'CONGESTION' => '<i class="icon-help red" title="Перегрузка канала"></i><span class="visible-iphone">Перегрузка канала</span>',
	'FAILED'     => '<i class="icon-cancel-squared red" title="Ошибка соединения"></i><span class="visible-iphone">Ошибка соединения</span>'
];

$colors  = [
	'ANSWER'    => 'green',
	'ANSWERED'  => 'green',
	'NO ANSWER' => 'red',
	'CANCEL'    => 'red',
	'BUSY'      => 'broun'
];
$directt = [
	'inner'   => '<i class="icon-arrows-cw smalltxt broun" title="Внутренний"></i><span class="visible-iphone">Внутренний</span>',
	'income'  => '<i class="icon-down-big smalltxt green" title="Входящий"></i><span class="visible-iphone">Входящий</span>',
	'outcome' => '<i class="icon-up-big smalltxt blue" title="Исходящий"></i><span class="visible-iphone">Исходящий</span>'
];

if ($action == 'export.on') {

	$direct2 = [
		'inner'   => 'Внутренний',
		'income'  => 'Входящий',
		'outcome' => 'Исходящий'
	];

	$dstart = $_REQUEST['dstart'];
	$dend   = $_REQUEST['dend'];

	if ($dstart == '' && $dend != '') {
		$sort .= "ch.datum < '$dend' AND ";
	}
	elseif ($dstart != '' && $dend != '') {
		$sort .= "DATE(ch.datum) >= '$dstart' and DATE(ch.datum) <= '$dend' AND ";
	}

	$otchet[] = explode(";", "Дата;Наш номер;Направление;Источник;Назначение;Результат;Продолжительность;Сотрудник;Клиент;Контакт");

	$result = $db -> query("
		SELECT 
			ch.datum,
			ch.did,
			ch.direct,
			ch.src,
			ch.dst,
			ch.res,
			ch.sec,
			ch.iduser,
			pc.person as person,
			cl.title as client,
			us.title as user
		FROM {$sqlname}callhistory `ch`
			LEFT JOIN {$sqlname}clientcat `cl` ON cl.clid = ch.clid
			LEFT JOIN {$sqlname}personcat `pc` ON pc.pid = ch.pid
			LEFT JOIN {$sqlname}user `us` ON us.iduser = ch.iduser
		WHERE 
			ch.id > 0 AND 
			$sort
			ch.identity = '$identity'
		ORDER BY ch.datum DESC
	");
	while ($data = $db -> fetch($result)) {

		$otchet[] = [
			$data['datum'],
			$data['did'],
			strtr($data['direct'], $direct2),
			formatPhone($data['src']),
			formatPhone($data['dst']),
			$data['res'],
			$data['sec'].",00",
			$data['user'],
			$data['client'],
			$data['person']
		];

	}

	/*
	$xls = new Excel_XML('UTF-8', false, 'CallHisroty');
	$xls -> addArray($otchet);
	$xls -> generateXML('export_calls');
	*/

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.calls.xlsx');

	exit();

}
if ($action == 'export') {

	?>
	<DIV class="zagolovok"><B>Экспорт истории звонков</B></DIV>
	<FORM method="post" action="/content/lists/list.calls.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="export.on">

		<div id="formtabs" class="wp100 box--child flex-vertical">

			<div class="flex-container wp50 pl10 text-center">

				<div class="flex-string label">Начало периода</div>
				<div class="flex-string">
					<input type="text" name="dstart" class="dstart required w160" id="dstart" value="<?= $dstart ?>">
				</div>

			</div>
			<div class="flex-container wp50 pr10 text-center">

				<div class="flex-string label">Конец периода</div>
				<div class="flex-string">
					<input type="text" name="dend" class="dend required w160" id="dend" value="<?= $dend ?>">
				</div>

			</div>

			<div class="flex-container1 wp100 mt10 mb10 text-center">

				<select name="period" id="period" class="w160" data-goal="formtabs" data-action="period">
					<option selected="selected">-за всё время-</option>
					<option data-period="today">Сегодня</option>
					<option data-period="yestoday">Вчера</option>

					<option data-period="calendarweekprev">Неделя прошлая</option>
					<option data-period="calendarweek">Неделя текущая</option>

					<option data-period="prevmonth">Месяц прошлый</option>
					<option data-period="month">Месяц текущий</option>

					<option data-period="prevquart">Квартал прошлый</option>
					<option data-period="quart">Квартал текущий</option>

					<option data-period="year">Год</option>
				</select>

			</div>

		</div>

		<div class="infodiv mt10">Дата экспортируется в формате Excel. Для нормализации следует выделить столбец и применить к нему форматирование</div>

		<hr>

		<DIV class="button--pane text-right">

			<A href="javascript:void(0)" onclick="exportDo()" class="button">Получить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>

	</FORM>
	<script>

		$(function () {

			$("#dstart").datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});
			$("#dend").datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});

		});

		function exportDo() {

			var url = 'content/lists/list.calls.php?action=export.on&dstart=' + $('#dstart').val() + '&dend=' + $('#dend').val();
			window.open(url);

			return false;
		}
	</script>
	<?php

	exit();

}

$page   = (int)$_REQUEST['page'];
$iduser = (int)$_REQUEST['iduser'];
$task   = (array)$_REQUEST['task'];
$direct = (array)$_REQUEST['direct'];
$d1     = $_REQUEST['da1'];
$d2     = $_REQUEST['da2'];
$word   = untag($_REQUEST['word']);

if ($iduser < 1) {

	if ($isadmin == 'on') {
		$sort .= '';
	}
	elseif ( strpos( $tipuser, "Руководитель" ) === false ) {
		$sort .= "AND calls.iduser = '$iduser1'";
	}
	else {
		$sort .= " AND calls.iduser iN (".yimplode( ",", get_people( $iduser1, "yes" ) ).")";
	}

}
else {
	$sort .= "AND calls.iduser = '$iduser'";
}

$stg = [];
foreach ($task as $item) {
	$stg[] = strtr( (int)$item, $va );
}

if ( !empty($task) && in_array( 1, $task, true ) ) {
	array_push( $stg, 'FAILED', 'NO ANSWER', 'NOANSWER' );
}
if ( !empty($task) && in_array( 2, $task, true ) ) {
	array_push( $stg, 'NOANSWER', 'BREAKED', 'CONGESTION' );
}

if (!empty($stg)) {
	$sort .= " AND calls.res IN (".yimplode( ",", $stg, "'" ).",'')";
}

$dir = [];
foreach ($direct as $item) {
	$dir[] = strtr( (int)$item, $di );
}

if (!empty($dir)) {
	$sort .= " AND calls.direct IN (".yimplode( ",", $dir, "'" ).")";
}

if ($word != '') {

	$w  = '';
	$ph = preg_replace("/\D/", "", $word);

	$pp = ($ph != '') ? " OR calls.src LIKE '%$ph%' OR calls.dst LIKE '%$ph%'" : "";

	$sort .=
		" 
		AND 
		(
			(
				calls.clid IN (SELECT clid FROM {$sqlname}clientcat WHERE {$sqlname}clientcat.title LIKE '%$word%' AND {$sqlname}clientcat.identity = '$identity') OR 
				calls.pid IN (SELECT pid FROM {$sqlname}personcat WHERE {$sqlname}personcat.person LIKE '%$word%' AND {$sqlname}personcat.identity = '$identity')
			) 
			$pp
		)
	";

}

if ($d1 != '') {
	$sort .= " AND calls.datum BETWEEN '$d1 00:00:00' and '$d2 23:59:59'";
}

$query = "
SELECT
	COUNT(calls.id)
FROM {$sqlname}callhistory `calls`
WHERE
	calls.id > 0 
	$sort AND
	calls.identity = '$identity'
ORDER BY calls.datum DESC";

//$start_time = microtime(true);

$all_lines = $db -> getOne($query);

//$end_time    = microtime(true);

$lines_per_page = 100;
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query = "
SELECT
	calls.id as id,
	calls.datum as datum,
	calls.src as src,
	calls.dst as dst,
	calls.did as did,
	calls.direct as direct,
	calls.res as res,
	calls.sec as sec,
	calls.file as file,
	calls.uid as uid,
	calls.iduser as iduser,
	calls.clid as clid,
	calls.pid as pid,
	cl.title as client,
	pr.person as person,
	us.title as user
FROM {$sqlname}callhistory `calls`
	LEFT JOIN {$sqlname}clientcat `cl` ON cl.clid = calls.clid
	LEFT JOIN {$sqlname}personcat `pr` ON pr.pid = calls.pid
	LEFT JOIN {$sqlname}user `us` ON us.iduser = calls.iduser
WHERE
	calls.id > 0 
	$sort AND
	calls.identity = '$identity'
ORDER BY calls.datum DESC";

//$start_time2 = microtime(true);

/* Долго работает
$res = $db -> query($query);
$all_lines = $db -> affectedRows($res);
*/

//$end_time2    = microtime(true);
//$start_time3 = microtime(true);

$page           = empty( $page ) || $page <= 0 ? 1 : (int)$page;

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query .= " LIMIT $lpos,$lines_per_page";

$count_pages = ceil($all_lines / $lines_per_page);
if ($count_pages < 1) {
	$count_pages = 1;
}

//print $query;

$result = $db -> query($query);
while ($da = $db -> fetch($result)) {

	$did = '<span class="gray">Линия не определена</span>';
	$add = '';

	if ($da['sec'] > 0) {

		$min = (int)( $da[ 'sec' ] / 60 ); //число минут
		$sec = $da['sec'] - $min * 60; //число секунд

		if ($sec < 10) $sec = '0'.$sec;
		if (strlen($sec) > 2) $sec = substr($da['sec'], 0, -1);

		$dur = gmdate("i:s", $da['sec']);

		$play = ($da['file'] != '' && $da['file'] != '0') ? '<a href="javascript:void(0)" onclick="doLoad(\'/content/pbx/play.php?id='.$da['id'].'\')" title="Прослушать запись"><i class="icon-volume-up blue"></i></a>' : '<i class="icon-volume-up gray" title="Разговор не записан"></i>';

	}
	else {
		$dur  = '-';
		$play = '<i class="icon-volume-up gray" title="Разговор не записан"></i>';
	}

	if ($da['direct'] == 'income') $phone = $da['src'];
	if ($da['direct'] == 'outcome') $phone = $da['dst'];

	//$clientpath = current_clientpathbyid(getClientpath('', '', $da['did']));

	if ($da['did'] != '' && $da['did'] != 0) {
		$did = ($clientpath != '') ? $clientpath : $da['did'];
	}

	if ($da['clid'] < 1 && $da['pid'] < 1) {
		$add = '1';
	}

	$list[] = [
		"id"     => $da['id'],
		"datum"  => str_replace(",", "", get_sfdate($da['datum'])),
		"direct" => strtr($da['direct'], $directt),
		"color"  => strtr($da['res'], $colors),
		"rezult" => strtr($da['res'], $rezult),
		"src"    => formatPhoneUrl2($da['src'], $da['clid'], $da['pid']),
		"dst"    => formatPhoneUrl2($da['dst'], $da['clid'], $da['pid']),
		"phone"  => $phone,
		"dur"    => $dur,
		"play"   => $play,
		"did"    => $did,
		"clid"   => $da['clid'],
		"client" => $da['client'],
		"pid"    => $da['pid'],
		"person" => $da['person'],
		"user"   => $da['user'],
		"add"    => $add
	];

}

//$end_time3 = microtime(true);

//$peersa['time.end.1'] = round(($end_time - $start_time), 3);
//$peersa['time.end.2'] = round(($end_time2 - $start_time2), 3);
//$peersa['time.end.3'] = round(($end_time3 - $start_time3), 3);
//$peersa['query']      = $query;

$lists = [
	"list"    => $list,
	"page"    => (int)$page,
	"pageall" => (int)$count_pages,
	//"timers"  => $peersa
];

//print $query."\n";
//print_r($lists);

print json_encode_cyr($lists);

exit();