<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.2           */
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

$thisfile = basename(__FILE__);

global $userRights;

$period = getPeriod('calendarweek');
$d1     = $period[0];
$d2     = $period[1];

$da1 = getMonth($period[0]).'-'.getDay($period[0]);
$da2 = getMonth($period[1]).'-'.getDay($period[1]);

//номер недели
$data      = new DateTime(current_datum());
$weekNum   = $data -> format('W');
$weekToday = $data -> format('w');

--$weekToday;

if ($weekToday == 7) {
	$weekToday = 0;
}

$calendar = $event = $cpoint = [];

//массив дат для текущей недели
$week[0] = $d1;
for ($i = 1; $i < 7; $i++) {

	//добавляяем один день к предыдущей дате
	$week[] = date('Y-m-d', strtotime("+1 day", strtotime($week[$i - 1])));

}

//print_r($week);

//напоминания текущего пользователя

//print "SELECT * FROM {$sqlname}tasks WHERE iduser = '$iduser1' and datum BETWEEN '$d1' and '$d2' and active != 'no' and identity = '$identity' ORDER BY datum, totime";

$result = $db -> query("SELECT * FROM {$sqlname}tasks WHERE iduser = '$iduser1' and datum BETWEEN '$d1' and '$d2' and active != 'no' /*and COALESCE(day, 'no') != 'yes'*/ and identity = '$identity' ORDER BY datum, totime");
while ($data = $db -> fetch($result)) {

	$color = $db -> getOne("SELECT color FROM {$sqlname}activities WHERE title='$data[tip]' and identity = '$identity'");
	if ($color == "") {
		$color = "#ECF0F1";
	}

	$author  = $drg = '';
	$tooltip = '&#013;';

	$diff  = diffDate2($data['datum']);
	$hours = difftime($data['created']);

	if ($data['autor'] > 0 && $data['author'] == $iduser1) {
		$author = '<i class="icon-user-1 blue" title="Назначено мной"></i>';
	}
	elseif ($data['autor'] > 0 && $data['author'] != $iduser1) {
		$author = '<i class="icon-user-1 red" title="Назначено мне"></i>';
	}

	if ($data['autor'] == 0 || $data['autor'] == $iduser1) {

		if ($hours <= $hoursControlTime) {
			$drg = 'wtodocal';
		}
		elseif ($userRights['changetask']) {
			$drg = 'wtodocal';
		}
		else {
			$drg = '';
		}

	}

	if ($drg == '') {
		$tooltip .= 'Назначил: '.current_user($data['autor']).'&#013;';
	}

	if ($data['readonly'] == 'yes') {

		if ($data['autor'] == $data['iduser'] || $data['author'] == 0 || $data['autor'] == $iduser1) {
			$drg = 'wtodocal';
		}

		if ($data['autor'] != $data['iduser'] && $data['author'] == 0) {

			$drg     = '';
			$tooltip .= '&#013;Только чтение';

		}

	}

	$hour = yexplode(":", getTime((string)$data['totime']), 0);

	if ($data['day'] != 'yes') {
		$calendar[$data['datum']][(int)$hour][] = [
			"day"     => getDay((string)$data['datum']),
			"time"    => getTime((string)$data['totime']),
			"datum"   => $data['datum'].' '.$data['totime'],
			"title"   => $data['title'],
			"tip"     => get_ticon($data['tip']),
			"color"   => $color,
			"autor"   => $author,
			"tid"     => $data['tid'],
			"iduser"  => $data['iduser'],
			"auth"    => $data['autor'],
			"allday"  => $data['day'] == 'yes' ? 1 : NULL,
			"tooltip" => $tooltip,
			"drg"     => $drg
		];
	}

	else {
		$event[$data['datum']][] = [
			"day"     => getDay($data['datum']),
			"type"    => 'event',
			"age"     => '<i class="icon-flag white fs-09" title="Весь день"></i>',
			"time"    => "",
			"title"   => $data['title'],
			"tip"     => get_ticon($data['tip']),
			"color"   => $color,
			"author"  => $author,
			"tid"     => $data['tid'],
			"allday"  => $data['day'] == 'yes' ? 1 : NULL,
			"iduser"  => '',
			"pid"     => '',
			"clid"    => '',
			"comment" => '',
			"datum"   => $data['datum'].' '.$data['totime'],
			"drg"     => $drg
		];
	}

}

//print_r($calendar);

//Находим именнинников в компании

$result = $db -> query("SELECT * FROM {$sqlname}user WHERE bday != '0000-00-00' and (DATE_FORMAT(bday, '%m-%d') BETWEEN '$da1' and '$da2' or DATE_FORMAT(CompStart, '%m-%d') BETWEEN '$da1' and '$da2') and secrty = 'yes' and identity = '$identity' ORDER BY bday");
while ($data = $db -> fetch($result)) {

	if ($data['bday'] != '0000-00-00') {

		$age = get_year(current_datum()) - get_year($data['bday']);
		$by  = explode("-", $data['bday']);
		$by1 = $by[0] + $age;
		$by  = $by1."-".$by[1]."-".$by[2];

		$event[$by][] = [
			"day"     => getDay($by),
			"type"    => 'event',
			"age"     => $age." ".getMorph($age),
			"time"    => "",
			"title"   => $data['title'],
			"tip"     => '<i class="icon-gift" title="День Рождения сотрудника"></i>',
			"color"   => "#d62728",
			"author"  => '',
			"tid"     => '',
			"iduser"  => $data['iduser'],
			"comment" => "День Рождения сотрудника"
		];

	}
	if ($data['CompStart'] != '0000-00-00') {

		$age = get_year(current_datum()) - get_year($data['CompStart']);
		$by  = explode("-", $data['CompStart']);
		$by1 = $by[0] + $age;
		$by  = $by1."-".$by[1]."-".$by[2];

		$event[$by][] = [
			"day"     => getDay($by),
			"type"    => 'event',
			"age"     => $age." ".getMorph($age),
			"time"    => "",
			"title"   => $data['title'],
			"tip"     => '<i class="icon-user-1" title="Стаж в компании"></i>',
			"color"   => "#1f77b4",
			"author"  => '',
			"tid"     => '',
			"iduser"  => $data['iduser'],
			"pid"     => '',
			"clid"    => '',
			"comment" => "Стаж в компании"
		];

	}
}

#добавим события из Клиентов, Контактов и Сотрудников

//Контакты. Сначала найдем поля, содержащие даты

$pfield = $db -> getIndCol("fld_name", "select fld_title, fld_name from {$sqlname}field where fld_tip = 'person' and fld_on = 'yes' and fld_temp = 'datum' and identity = '$identity' order by fld_order");

//Контакты. Сначала найдем поля, содержащие даты

$cfield = $db -> getIndCol("fld_name", "select fld_title, fld_name from {$sqlname}field where fld_tip = 'client' and fld_on = 'yes' and fld_temp = 'datum' and identity = '$identity' order by fld_order");

//Составим события по КТ
$q  = "
	SELECT
		{$sqlname}complect.data_plan as dplan,
		{$sqlname}complect.did as did,
		{$sqlname}complect.iduser as iduser,
		{$sqlname}complect_cat.title as title,
		{$sqlname}dogovor.title as deal,
		{$sqlname}clientcat.title as client
	FROM {$sqlname}complect
	LEFT JOIN {$sqlname}complect_cat ON {$sqlname}complect.ccid = {$sqlname}complect_cat.ccid
	LEFT JOIN {$sqlname}dogovor ON {$sqlname}complect.did = {$sqlname}dogovor.did
	LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
	WHERE
		{$sqlname}complect.doit != 'yes' and
		{$sqlname}complect.iduser IN (".implode(",", get_people($iduser1, "yes")).") and
		{$sqlname}complect.data_plan BETWEEN '$d1' and '$d2' and
		{$sqlname}complect.identity = '$identity'
";
$re = $db -> query($q);
while ($da = $db -> fetch($re)) {

	if (datestoday($da['dplan']) < 0) {
		$color = 'red';
	}
	elseif (datestoday($da['dplan']) == 0) {
		$color = 'broun';
	}
	else {
		$color = 'blue';
	}

	if ($da['data_plan'] != '') {

		$cpoint[$da['dplan']][] = [
			"day"    => getDay($da['data_plan']),
			"type"   => 'cpoint',
			"title"  => $da['title'],
			"tip"    => '<i class="icon-check '.$color.'"></i>',
			"iduser" => $da['iduser'],
			"cp"     => "1",
			"did"    => $da['did'],
			"deal"   => $da['deal'],
			"client" => $da['client'],
		];

	}

}

//print_r($cpoint);

$datas = [];

foreach ($week as $i => $day) {

	$bg    = ( $i == 5 || $i == 6 ) ? 'bgray' : '';
	$add   = ( diffDate2($day) >= 0 ) ? 'yes' : '';
	$scope = '';

	if (diffDate2($day) == 0) {
		$bg = 'today';
	}

	$datas[$i] = [
		"day"  => getDay($day),
		"date" => $day,
		"add"  => $add,
		"bg"   => $bg,
	];

	$cal = $calbyhour = [];
	for ($h = 0; $h < 24; $h++) {

		$class = ( $h == date('H') && diffDate2($day) == 0 ) ? "today" : "";

		if ($h < 8 || $h > 19) {
			$class = ' wgray nowork';
			$class .= ( $h == date('H') ) ? " today" : "";
		}

		$datum = $day.' '.sprintf("%02d", $h).":00:00";

		if (diffDate2($datum) >= 0) {
			$scope = ' adtask';
			$add   = 'yes';
		}
		else {
			$scope = '';
		}

		$calbyhour[] = [
			"time"  => sprintf("%02d", $h).":00",
			"hour"  => $calendar[$day][$h],
			"class" => $class,
			"scope" => $scope,
			"add"   => $add,
			"datum" => $datum
		];

	}

	$datas[$i]['cal'] = ["events" => $calbyhour];

	/**
	 * События на весь день
	 */

	$wday = date('m-d', strtotime($week[$i]));

	//данные по Контактам

	foreach ($pfield as $field => $name) {

		$result = $db -> query("SELECT * FROM {$sqlname}personcat WHERE $field != '' and DATE_FORMAT($field, '%m-%d') = '$wday' and identity = '$identity' ORDER BY $field");
		while ($data = $db -> fetch($result)) {

			if ($data[$field] != '0000-00-00') {

				$age = get_year(current_datum()) - get_year($data[$field]);
				$by  = explode("-", $data[$field]);
				$by1 = $by[0] + $age;
				$by  = $by1."-".$by[1]."-".$by[2];

				$event[$by][] = [
					"day"     => getDay($by),
					"type"    => 'event',
					"age"     => $age." ".getMorph($age),
					"time"    => "",
					"title"   => $data['person'],
					"tip"     => '<i class="icon-user-1" title="'.$name.'"></i>',
					"color"   => "#ff7f0e",
					"author"  => '',
					"tid"     => '',
					"iduser"  => '',
					"pid"     => $data['pid'],
					"clid"    => '',
					"comment" => $name
				];

			}

		}

	}

	foreach ($cfield as $field => $name) {

		$result = $db -> query("SELECT * FROM {$sqlname}clientcat WHERE $field != '' and DATE_FORMAT($field, '%m-%d') = '$wday' and identity = '$identity' ORDER BY $field");
		while ($data = $db -> fetch($result)) {

			if ($data[$field] != '') {

				$age = get_year(current_datum()) - get_year($data[$field]);
				$by  = explode("-", $data[$field]);
				$by1 = $by[0] + $age;
				$by  = $by1."-".$by[1]."-".$by[2];

				$event[$by][] = [
					"day"     => getDay($by),
					"type"    => 'event',
					"age"     => $age." ".getMorph($age),
					"time"    => "",
					"title"   => $data['title'],
					"tip"     => '<i class="icon-building" title="'.$name.'"></i>',
					"color"   => "#2ca02c",
					"author"  => '',
					"tid"     => '',
					"iduser"  => '',
					"pid"     => '',
					"clid"    => $data['clid'],
					"comment" => $name
				];
			}

		}

	}

	$datas[$i]['event']  = $event[$day];
	$datas[$i]['cpoint'] = $cpoint[$day];

}

//часовая панель
$hour = [];
for ($h = 0; $h < 24; $h++) {

	$class = ( $h == date('H') ) ? "today" : "";
	$today = ( $h == date('H') ) ? "today" : "";

	if ($h < 8 || $h > 19) {
		$class .= ' nowork';
	}

	$hour[] = [
		"h"     => sprintf("%02d", $h).":00",
		"class" => $class,
		"today" => $today
	];

}

//заголовки дней
$header = [];
foreach ($lang['face']['WeekName'] as $i => $name) {

	$class = ( $i == 5 || $i == 6 ) ? "wcStart" : "wcEnd";

	if ($weekToday == $i) {
		$class = 'orangebg-dark';
	}

	$header[] = [
		"name"   => $name,
		"date"   => get_dateru($week[$i]),
		"class"  => $class,
		"events" => $event[$week[$i]],
		"cpoint" => $cpoint[$week[$i]],
	];

}

$data = [
	"header"   => $header,
	"hours"    => $hour,
	"calendar" => $datas,
	"now"      => sprintf("%02d", date('H')).":00"
];

//print_r($data);
print json_encode_cyr($data);