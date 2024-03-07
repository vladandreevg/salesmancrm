<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.25          */
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

$y  = date('y');
$m  = date('m');
$nd = date('d');
$nd = strftime('%Y-%m-%d', mktime(0, 0, 0, $m, $nd, $y));

//кастомные имена некоторых полей
$dname  = [];
$result = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_on='yes' and fld_name IN ('datum_plan','tip','kol','iduser') and identity = '$identity' ORDER BY fld_order");
while ($data = $db -> fetch($result)) {
	$dname[$data['fld_name']] = $data['fld_title'];
}

$header = [
	"tip"    => $dname['tip'],
	"kol"    => $dname['kol'],
	"plan"   => $dname['datum_plan'],
	"iduser" => $dname['iduser'],
	"valuta" => $valuta
];

//Найдем тип сделки, которая является Сервисной
$tid = $db -> getCol("SELECT tid FROM {$sqlname}dogtips WHERE (title LIKE '%месячный%' or title LIKE '%сервисн%' or title LIKE '%абонент%') and identity = '$identity'");

$service = implode(",", $tid);

//список сделок, к которым у текущего пользователя предоставлен доступ
$dostup = $db -> getCol("SELECT did FROM {$sqlname}dostup WHERE iduser = '".$iduser1."' and identity = '$identity'");

if (count($dostup) > 0) {

	$sort .= " and ({$sqlname}dogovor.did > 0 or {$sqlname}dogovor.did IN (".implode(",", $dostup)."))";
	$dos  = " and ({$sqlname}dogovor.did > 0 or {$sqlname}dogovor.did IN (".implode(",", $dostup)."))";

}

$serv = '';

//сервисные сделки
if (count($tid) > 0) {

	$data   = [];
	$tcolor = 'bluebg';
	$old    = 'services';

	$q = "
	SELECT
		{$sqlname}dogovor.did as did,
		{$sqlname}dogovor.title as title,
		{$sqlname}dogovor.datum as datum,
		{$sqlname}dogovor.datum_end as plan,
		{$sqlname}dogovor.idcategory as idstep,
		{$sqlname}dogovor.tip as tip,
		{$sqlname}dogovor.clid as clid,
		{$sqlname}dogovor.pid as pid,
		{$sqlname}dogovor.kol as kol,
		{$sqlname}dogovor.close as close,
		{$sqlname}dogovor.iduser as iduser,
		{$sqlname}personcat.person as person,
		{$sqlname}user.title as user,
		{$sqlname}clientcat.title as client,
		{$sqlname}dogcategory.title as step,
		{$sqlname}dogcategory.content as stepdes,
		{$sqlname}dogtips.title as tips
	FROM {$sqlname}dogovor
		LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
		LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
		LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
		LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
	WHERE
		{$sqlname}dogovor.close!='yes' and
		{$sqlname}dogovor.tip IN ($service) and
		{$sqlname}dogovor.iduser IN (".implode(",", get_people($iduser1, 'yes')).")
		".$dos." and
		{$sqlname}dogovor.identity = '$identity'
	ORDER BY {$sqlname}dogovor.datum_end LIMIT 30";

	$result = $db -> query($q);
	while ($da = $db -> fetch($result)) {

		$sup    = '';
		$datum  = $da['plan'];
		$change = '';
		$sum    = 0;


		if (diffDate2($da['plan']) < 0) {
			$dcolor = 'red';
		}
		elseif (diffDate2($da['plan']) == 0) {
			$dcolor = 'green';
		}
		else {
			$dcolor = 'blue';
		}


		if ((int)$da['step'] < 20) {
			$progress = ' progress-gray';
		}
		elseif ((int)$da['step'] >= 20 && $da['step'] < 60) {
			$progress = ' progress-green';
		}
		elseif ((int)$da['step'] >= 60 && $da['step'] < 90) {
			$progress = ' progress-blue';
		}
		elseif ((int)$da['step'] >= 90 && $da['step'] <= 100) {
			$progress = ' progress-red';
		}

		if (get_accesse(0, 0, (int)$da['did']) == 'yes') {
			$change = 'yes';
		}

		if (in_array($da['did'], $dostup)) {
			$sup = '<i class="icon-lock-open green smalltxt sup" title="Вам предоставлен доступ"></i>';
		}

		$icon = '<i class="icon-arrows-cw '.$dcolor.'" title="Открыть в новом окне. Сервисная сделка"></i>';

		$sum = $db -> getOne("SELECT summa_credit FROM {$sqlname}credit WHERE did='".$da['did']."'");

		$stepDay = abs(round(diffDate2($db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did='".$da['did']."'"))));

		$data[] = [
			"did"      => $da['did'],
			"icon"     => $icon,
			"plan"     => $da['plan'],
			"plantext" => format_date_rus($da['plan']),
			"date"     => format_date_rus($da['datum']),
			"title"    => $da['title'],
			"step"     => $da['step'],
			"stepdes"  => $da['stepdes'],
			"progress" => $progress,
			"summa"    => num_format($sum),
			"tip"      => $da['tips'],
			"clid"     => $da['clid'],
			"client"   => $da['client'],
			"iduser"   => $da['iduser'],
			"user"     => $da['user'],
			"change"   => $change,
			"dostup"   => $sup,
			"stepday"  => $stepDay
		];

	}

	$title = 'Сервисные '.$lang['face']['DealsName'][0];
	$state = 'hidden';

	$deals[] = [
		"id"    => $old,
		"bg"    => $tcolor,
		"title" => $title,
		"state" => $state,
		"data"  => $data,
		"count" => count($data)
	];

	//аппендикс для запросов ниже
	$serv = " and {$sqlname}dogovor.tip NOT IN (".$service.")";

}

$stepInHold = customSettings('stepInHold');

if ($stepInHold['step'] > 0 && $stepInHold['input'] != '') {

	$sort .= " AND ({$sqlname}dogovor.idcategory != '$stepInHold[step]' OR ({$sqlname}dogovor.idcategory = '$stepInHold[step]' AND DATE({$sqlname}dogovor.".$stepInHold['input'].") <= DATE(NOW()) )) ";

}

//просроченные сделки
$data   = [];
$old    = 'old';
$tcolor = 'redbg';

$q = "
SELECT
	{$sqlname}dogovor.did as did,
	{$sqlname}dogovor.title as title,
	{$sqlname}dogovor.datum as datum,
	{$sqlname}dogovor.datum_plan as plan,
	{$sqlname}dogovor.idcategory as idstep,
	{$sqlname}dogovor.tip as tip,
	{$sqlname}dogovor.clid as clid,
	{$sqlname}dogovor.pid as pid,
	{$sqlname}dogovor.kol as kol,
	{$sqlname}dogovor.iduser as iduser,
	{$sqlname}personcat.person as person,
	{$sqlname}user.title as user,
	{$sqlname}clientcat.title as client,
	{$sqlname}dogcategory.title as step,
	{$sqlname}dogcategory.content as stepdes,
	{$sqlname}dogtips.title as tips
FROM {$sqlname}dogovor
	LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
	LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
	LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
	LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
WHERE
	{$sqlname}dogovor.close != 'yes' and
	{$sqlname}dogovor.iduser IN (".implode(",", get_people($iduser1, 'yes')).")
	$sort and
	{$sqlname}dogovor.identity = '$identity' and
	{$sqlname}dogovor.datum_plan < '".current_datum()."'
	".$serv."
ORDER BY {$sqlname}dogovor.datum_plan LIMIT 30";

$result = $db -> query($q);
while ($da = $db -> fetch($result)) {

	$sup    = '';
	$datum  = $da['plan'];
	$change = '';

	if ($da['step'] < 20) {
		$progress = ' progress-gray';
	}
	elseif ($da['step'] >= 20 && $da['step'] < 60) {
		$progress = ' progress-green';
	}
	elseif ($da['step'] >= 60 && $da['step'] < 90) {
		$progress = ' progress-blue';
	}
	elseif ($da['step'] >= 90 && $da['step'] <= 100) {
		$progress = ' progress-red';
	}

	if (get_accesse(0, 0, (int)$da['did']) == 'yes') {
		$change = 'yes';
	}

	$icon = '<i class="icon-briefcase broun" title="Открыть в новом окне"></i>';

	// для замороженных сделок
	if ($da['idstep'] == $stepInHold['step']) {
		$icon = '<i class="icon-snowflake-o bluemint" title="Открыть в новом окне"></i>';
	}

	if (in_array($da['did'], $dostup)) {
		$sup = '<i class="icon-lock-open green smalltxt sup" title="Вам предоставлен доступ"></i>';
	}

	$stepDay = abs(round(diffDate2($db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did='".$da['did']."'"))));

	$data[] = [
		"did"      => $da['did'],
		"icon"     => $icon,
		"plan"     => $da['plan'],
		"plantext" => format_date_rus($datum),
		"date"     => format_date_rus($da['datum']),
		"title"    => $da['title'],
		"step"     => $da['step'],
		"stepdes"  => $da['stepdes'],
		"progress" => $progress,
		"summa"    => num_format($da['kol']),
		"tip"      => $da['tips'],
		"clid"     => $da['clid'],
		"client"   => $da['client'],
		"iduser"   => $da['iduser'],
		"user"     => $da['user'],
		"change"   => $change,
		"dostup"   => $sup,
		"stepday"  => $stepDay
	];


}

$title = 'Просроченные '.$lang['face']['DealsName'][0];
$state = '';

$deals[] = [
	"id"    => $old,
	"bg"    => $tcolor,
	"title" => $title,
	"state" => $state,
	"data"  => $data,
	"count" => count($data)
];

//актуальные сделки
$data   = [];
$old    = 'future';
$tcolor = 'greenbg';

$q = "
SELECT
	{$sqlname}dogovor.did as did,
	{$sqlname}dogovor.title as title,
	{$sqlname}dogovor.datum as datum,
	{$sqlname}dogovor.datum_plan as plan,
	{$sqlname}dogovor.idcategory as idstep,
	{$sqlname}dogovor.tip as tip,
	{$sqlname}dogovor.clid as clid,
	{$sqlname}dogovor.pid as pid,
	{$sqlname}dogovor.kol as kol,
	{$sqlname}dogovor.iduser as iduser,
	{$sqlname}personcat.person as person,
	{$sqlname}user.title as user,
	{$sqlname}clientcat.title as client,
	{$sqlname}dogcategory.title as step,
	{$sqlname}dogcategory.content as stepdes,
	{$sqlname}dogtips.title as tips
FROM {$sqlname}dogovor
	LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
	LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
	LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
	LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
WHERE
	{$sqlname}dogovor.close!='yes' and
	{$sqlname}dogovor.iduser IN (".implode(",", get_people($iduser1, 'yes')).")
	$sort and
	{$sqlname}dogovor.identity = '$identity' and
	{$sqlname}dogovor.datum_plan >= '".current_datum()."'
	".$serv."
ORDER BY {$sqlname}dogovor.datum_plan LIMIT 30";

$result = $db -> query($q);
while ($da = $db -> fetch($result)) {

	$sup    = '';
	$datum  = $da['plan'];
	$change = '';

	if ($da['step'] < 20) {
		$progress = ' progress-gray';
	}
	elseif ($da['step'] >= 20 && $da['step'] < 60) {
		$progress = ' progress-green';
	}
	elseif ($da['step'] >= 60 && $da['step'] < 90) {
		$progress = ' progress-blue';
	}
	elseif ($da['step'] >= 90 && $da['step'] <= 100) {
		$progress = ' progress-red';
	}

	if (get_accesse(0, 0, (int)$da['did']) == 'yes') {
		$change = 'yes';
	}

	$icon = '<i class="icon-briefcase broun" title="Открыть в новом окне"></i>';

	// для замороженных сделок
	if ($da['idstep'] == $stepInHold['step']) {
		$icon = '<i class="icon-snowflake-o bluemint" title="Открыть в новом окне"></i>';
	}

	if (in_array($da['did'], $dostup)) {
		$sup = '<i class="icon-lock-open green smalltxt sup" title="Вам предоставлен доступ"></i>';
	}

	$stepDay = abs(round(diffDate2($db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did='".$da['did']."'"))));

	$data[] = [
		"did"      => $da['did'],
		"icon"     => $icon,
		"plan"     => $da['plan'],
		"plantext" => format_date_rus($datum),
		"date"     => format_date_rus($da['datum']),
		"title"    => $da['title'],
		"step"     => $da['step'],
		"stepdes"  => $da['stepdes'],
		"progress" => $progress,
		"summa"    => num_format($da['kol']),
		"tip"      => $da['tips'],
		"clid"     => $da['clid'],
		"client"   => $da['client'],
		"iduser"   => $da['iduser'],
		"user"     => $da['user'],
		"change"   => $change,
		"dostup"   => $sup,
		"stepday"  => $stepDay
	];

}

$title = 'Актуальные '.$lang['face']['DealsName'][0];
$state = '';

$deals[] = [
	"id"    => $old,
	"bg"    => $tcolor,
	"title" => $title,
	"state" => $state,
	"data"  => $data,
	"count" => count($data)
];

$data = [
	"deals"  => $deals,
	"header" => $header
];

print json_encode_cyr($data);