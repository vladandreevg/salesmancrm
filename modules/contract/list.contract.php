<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Akt;
use Salesman\Document;
use Salesman\Guides;
use Salesman\Invoice;

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

$tar       = $_REQUEST['tar'];
$page      = (int)$_REQUEST['page'];
$tuda      = $_REQUEST['tuda'];
$oldonly   = $_REQUEST['oldonly'];
$pay1      = $_REQUEST['pay1'];
$pay2      = $_REQUEST['pay2'];
$iduser    = $_REQUEST['iduser'];
$idusera   = $_REQUEST['idusera'];
$type      = (array)$_REQUEST['type'];
$ord       = $_REQUEST['ord'];
$isService = $_REQUEST['isService'];
$status    = (array)$_REQUEST['status'];
$mc        = (int)$_REQUEST['mc'];

$worda = str_replace(" ", "%", $_REQUEST['worda']);
$wordc = str_replace(" ", "%", $_REQUEST['wordc']);
$wordp = str_replace(" ", "%", $_REQUEST['wordp']);

$sort = '';

$ordd = $ord;

$mycomps = Guides ::myComps();

/**
 * Документы
 */
if ($tar == "contract") {

	$lists = ( new Document() ) -> list([
		"word"    => $wordp,
		"page"    => $page,
		"tuda"    => $tuda,
		"ord"     => $ord,
		"oldonly" => $oldonly,
		"status"  => $status,
		"type"    => $type,
		"iduser"  => $iduser,
		"mc"      => $mc
	]);

	print json_encode_cyr($lists);

	exit();

}

if ($tar == "contract.old") {

	$statuses = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE identity = '$identity'");

	$ord = "ct.".$_REQUEST['ord'];

	$color = [
		'#FFC0CB',
		'#F0E68C'
	];

	$lines_per_page = 100; //Стоимость записей на страницу

	if ($wordc != "") {
		$sort .= " and (ct.des LIKE '%".$wordc."%' or ct.number LIKE '%".$wordc."%' OR clt.title LIKE '%".$wordc."%')";
	}

	if ($oldonly == 'old') {
		$sort .= " and ct.datum_end != 'NULL' and DATE_FORMAT(ct.datum_end, '%Y-%m-%d') < '".current_datum(30)."'";
	}

	if ($oldonly == 'old30') {
		$sort .= " and ct.datum_end != 'NULL' and (DATE_FORMAT(ct.datum_end, '%Y-%m-%d') BETWEEN '".current_datum(30)."' AND '".current_datum(14)."')";
	}

	if ($oldonly == 'old14') {
		$sort .= " and ct.datum_end != 'NULL' and (DATE_FORMAT(ct.datum_end, '%Y-%m-%d') BETWEEN '".current_datum(14)."' AND '".current_datum()."')";
	}

	if (!empty($status)) {
		$sort .= " and ct.status IN (".implode(",", $status).")";
	}

	if (!empty($type)) {
		$sort .= " and ct.idtype IN (".implode(",", $type).")";
	}
	else {
		$sort .= " and (ct.idtype IN (SELECT id FROM {$sqlname}contract_type WHERE COALESCE(type, '') NOT IN ('get_akt','get_aktper') and identity = '$identity') or ct.idtype = 0)";
	}

	if ($tipuser == "Менеджер продаж" && $acs_prava != "on") {

		$sort .= " AND ct.iduser = '".$iduser1."'";

	}

	if ($mc > 0) {
		$sort .= " and dg.mcid = '$mc'";
	}

	$sub[] = 'client';
	$sub[] = 'person';
	if ($userSettings['dostup']['partner'] == 'on') {
		$sub[] = 'partner';
	}
	if ($userSettings['dostup']['contractor'] == 'on') {
		$sub[] = 'contractor';
	}
	if ($userSettings['dostup']['concurent'] == 'on') {
		$sub[] = 'concurent';
	}

	$sort .= " AND clt.type IN (".yimplode(",", $sub, "'").") ";

	$query     = "
		SELECT 
			COUNT(*)
		FROM {$sqlname}contract `ct`
			LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
			LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
			LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = ct.clid
		WHERE 
			ct.deid > 0 
			$sort and 
			ct.identity = '$identity'
	";
	$all_lines = $db -> getOne($query);

	$page           = ( empty($page) || $page <= 0 ) ? 1 : (int)$page;
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	//print
	$query = "
		SELECT 
			ct.deid,
			ct.datum_end,
			ct.datum_start,
			ct.number,
			ct.title,
			ct.clid,
			ct.pid,
			ct.did,
			ct.payer,
			ct.idtype,
			clt.title as client,
			dg.title as deal,
			{$sqlname}contract_status.title as tstatus,
			{$sqlname}contract_status.color as color,
			dg.mcid as mc,
			ct.mcid
		FROM {$sqlname}contract `ct`
			LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
			LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
			LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = ct.clid
		WHERE 
			ct.deid > 0 
			$sort and 
			ct.identity = '$identity'
	";

	$query .= " ORDER BY $ord $tuda LIMIT $lpos,$lines_per_page";

	$result      = $db -> query($query);
	$count_pages = ceil($all_lines / $lines_per_page);
	if ($count_pages == 0) {
		$count_pages = 1;
	}

	while ($da = $db -> fetch($result)) {

		$payer = '';
		$color = '';

		if ($da['datum_end'] == "0000-00-00") {
			$da['datum_end'] = "";
		}
		else {

			$day = datestoday($da['datum_end']); //дней до окончания действия

			if (is_between($day, 0, 7)) {
				$color = 'orangebg-sub';
			}
			elseif (is_between($day, 0, 30)) {
				$color = 'bluebg-sub';
			}
			elseif (is_between($day, -14, 0)) {
				$color = 'yellowbg-sub';
			}
			elseif (is_between($day, -30, -14)) {
				$color = 'redbg-sub';
			}
			elseif ($day < -30) {
				$color = 'graybg gray2';
			}

		}

		if ((int)$da['payer'] > 0 && (int)$da['payer'] != (int)$da['clid']) {
			$payer = current_client($da['payer']);
		}

		//статусы, применимые к текущему типу документоа
		$stat = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity'");

		$list[] = [
			"id"          => $da['deid'],
			"datum_start" => format_date_rus($da['datum_start']),
			"datum_end"   => format_date_rus($da['datum_end']),
			"color"       => $color,
			"number"      => $da['number'],
			"title"       => $da['title'],
			"clid"        => $da['clid'],
			"client"      => $da['client'],
			"pid"         => $da['pid'],
			"person"      => current_person($da['pid']),
			"did"         => $da['did'],
			"deal"        => $da['deal'],
			"payerid"     => $da['payer'],
			"payer"       => $payer,
			"statuson"    => ( $stat > 0 ) ? "1" : "",
			"status"      => ( $da['tstatus'] != '' ) ? $da['tstatus'] : "--",
			"statuscolor" => ( $da['tstatus'] != '' ) ? $da['color'] : "#fff",
			"mc"          => $da['mc'] > 0 ? $mycomps[$da['mc']] : $mycomps[$da['mcid']]
		];

	}

	$lists = [
		"list"     => $list,
		"page"     => (int)$page,
		"pageall"  => (int)$count_pages,
		"ord"      => $ord,
		"desc"     => $tuda,
		"isstatus" => ( $statuses > 0 ) ? "1" : ""
	];

	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr($lists);

	exit();

}

/**
 * Акты
 */
if ($tar == 'akt') {

	$lists = (new Akt()) -> list([
		"word"   => $worda,
		"page"   => $page,
		"tuda"   => $tuda,
		"ord"    => $ord,
		"status" => $status,
		"iduser" => $idusera,
		"mc"     => $mc
	]);

	print json_encode_cyr($lists);

	exit();

}

if ($tar == 'akt.old') {

	$list = [];

	$statuses = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE identity = '$identity'");

	$sort .= " and ct.iduser IN (".yimplode(",", get_people($iduser1, "yes")).")";

	if (!empty($status)) {
		$sort .= " and ct.status IN (".implode(",", $status).")";
	}

	if ($worda != "") {
		$sort .= " and (ct.number = '$worda' OR crd.invoice = '$worda' OR clt.title LIKE '%".$worda."%' OR dg.title LIKE '%".$worda."%')";
	}

	if ($idusera != '') {
		$sort .= " and ct.iduser= '$idusera'";
	}

	if ($isService == 'yes') {
		$sort .= " and ct.crid > 0";
	}
	elseif ($isService == 'no') {
		$sort .= " and ct.crid = 0";
	}

	if ($mc > 0) {
		$sort .= " and dg.mcid = '$mc'";
	}

	$lines_per_page = 100; //Стоимость записей на страницу

	$query = "
		SELECT 
			COUNT(*)
		FROM {$sqlname}contract `ct`
			LEFT JOIN {$sqlname}personcat ON ct.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat `clt` ON ct.clid = clt.clid
			LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
			LEFT JOIN {$sqlname}contract_type ON ct.idtype = {$sqlname}contract_type.id
			LEFT JOIN {$sqlname}credit `crd` ON ct.crid = crd.crid
			LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
		WHERE 
			{$sqlname}contract_type.type IN ('get_akt','get_aktper') 
			$sort and 
			ct.identity = '$identity'
		";

	$all_lines = $db -> getOne($query);

	if (empty($page) || $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	if ($ord == 'number') {
		$ordd = " (ct.number -1)";
	}
	elseif ($ord == 'invoice') {
		$ordd = " (crd.invoice)";
	}
	elseif ($ord == 'summa') {
		$ordd = " summa";
	}
	else {
		$ordd = "ct.$ord";
	}

	$query = "
		SELECT 
			ct.deid,
			DATE_FORMAT(ct.datum, '%d.%m.%Y') as datum,
			DATE_FORMAT(ct.datum_start, '%d.%m.%Y') as datum_start,
			DATE_FORMAT(ct.datum_end, '%d.%m.%Y') as datum_end,
			ct.number,
			ct.clid,
			ct.pid,
			ct.did,
			ct.payer,
			ct.iduser,
			ct.idtype,
			{$sqlname}contract_type.type,
			crd.summa_credit,
			crd.invoice,
			clt.title as client,
			{$sqlname}personcat.person as person,
			dg.title as deal,
			dg.kol as kol,
			dg.mcid as mc,
			{$sqlname}user.title as user,
			IF({$sqlname}contract_type.type = 'get_akt', dg.kol, crd.summa_credit) as summa,
			{$sqlname}contract_status.title as status,
			{$sqlname}contract_status.color as color
		FROM {$sqlname}contract `ct`
			LEFT JOIN {$sqlname}user ON ct.iduser = {$sqlname}user.iduser
			LEFT JOIN {$sqlname}personcat ON ct.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat `clt` ON ct.clid = clt.clid
			LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
			LEFT JOIN {$sqlname}contract_type ON ct.idtype = {$sqlname}contract_type.id
			LEFT JOIN {$sqlname}credit `crd` ON ct.crid = crd.crid
			LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
		WHERE 
			{$sqlname}contract_type.type IN ('get_akt','get_aktper') 
			$sort and 
			ct.identity = '$identity'
		";

	$query = "$query ORDER BY $ordd $tuda LIMIT $lpos,$lines_per_page";

	$result      = $db -> query($query);
	$count_pages = ceil($all_lines / $lines_per_page);

	while ($da = $db -> fetch($result)) {

		//$type = $db -> getOne("SELECT type FROM {$sqlname}contract_type where id = '".$da['idtype']."' and identity = '$identity'");
		//if ($type == 'get_akt') $kol = getDogData($da['did'], 'kol');
		//if ($type == 'get_aktper') $kol = $db -> getOne("SELECT summa_credit FROM {$sqlname}credit where crid = '".$da['crid']."' and identity = '$identity'");

		//статусы, применимые к текущему типу документоа
		$stat = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity'");

		$aktComplect = Akt ::getComplect($da['deid']);

		$isper = (bool)isServices($da['did']);

		$list[] = [
			"id"          => $da['deid'],
			"datum"       => $da['datum'],
			"datum_start" => $da['datum_start'],
			"datum_end"   => $da['datum_end'],
			"number"      => $da['number'],
			"summa"       => num_format($da['summa']),
			"title"       => $da['title'],
			"clid"        => $da['clid'],
			"client"      => $da['client'],
			"pid"         => $da['pid'],
			"person"      => $da['person'],
			"did"         => $da['did'],
			"deal"        => $da['deal'],
			"payerid"     => $da['payer'],
			"payer"       => current_client($da['payer']),
			"crid"        => $da['crid'],
			"invoice"     => $da['invoice'],
			"user"        => $da['user'],
			"statuson"    => ( $stat > 0 ) ? "1" : "",
			"status"      => ( $da['status'] != '' ) ? $da['status'] : "--",
			"statuscolor" => ( $da['status'] != '' ) ? $da['color'] : "#fff",
			"mc"          => $mycomps[$da['mc']],
			"complect"    => !$isper ? round($aktComplect + 0.1, 0) : 100,
		];

	}

	$lists = [
		"list"     => $list,
		"page"     => (int)$page,
		"pageall"  => (int)$count_pages,
		"ord"      => $ord,
		"tuda"     => $tuda,
		"valuta"   => $valuta,
		"isstatus" => ( $statuses > 0 ) ? "1" : "",
		"count"    => count($list),
		"isMobile" => $isMobile
	];

	print json_encode_cyr($lists);

	exit();

}

/**
 * Счета
 */
if ($tar == 'payment') {

	$pay['on']  = $pay1 == 'yes';
	$pay['off'] = $pay2 == 'yes';

	$result = ( new Invoice() ) -> list([
		"word"   => $wordp,
		"page"   => $page,
		"tuda"   => $tuda,
		"ord"    => $ord,
		"pay"    => $pay,
		"iduser" => $iduser,
		"mc"     => $mc
	]);

	print json_encode_cyr($result);

	exit();

}

if ($tar == 'payment.old') {

	$ordd = "crd.".$_REQUEST['ord'];

	if ($pay1 == 'yes' && $pay2 != 'yes') {
		$sort .= " and crd.do = 'on'";
	}
	elseif ($pay1 != 'yes' && $pay2 == 'yes') {
		$sort .= " and crd.do != 'on'";
	}

	if ($iduser != '') {
		$sort .= " and crd.iduser= '".$iduser."'";
	}
	else {
		$sort .= " and crd.iduser IN (".yimplode(",", (array)get_people($iduser1, "yes")).")";
	}

	if ($wordp != "") {
		$sort .= " and (crd.invoice LIKE '%".$wordp."%' or crd.invoice_chek LIKE '%".$wordp."%' or dg.title LIKE '%".$wordp."%' or clt.title LIKE '%".$wordp."%')";
	}

	if ($mc > 0) {
		$sort .= " and crd.rs IN (SELECT id FROM {$sqlname}mycomps_recv WHERE {$sqlname}mycomps_recv.cid = '$mc')";
	}

	$lines_per_page = $num_client; //Стоимость записей на страницу

	$query = "
	SELECT
		crd.crid,
		crd.invoice,
		crd.datum,
		crd.datum_credit,
		crd.invoice_date,
		crd.invoice_chek,
		crd.summa_credit,
		crd.do,
		crd.pid,
		crd.clid,
		crd.did,
		clt.title as client,
		{$sqlname}personcat.person as person,
		dg.title as dogovor,
		dg.kol as summa,
		dg.close as close,
		(SELECT cid FROM {$sqlname}mycomps_recv WHERE {$sqlname}mycomps_recv.id = crd.rs) as mc
	FROM {$sqlname}credit `crd`
		LEFT JOIN {$sqlname}clientcat `clt` ON crd.clid = clt.clid
		LEFT JOIN {$sqlname}dogovor `dg` ON crd.did = dg.did
		LEFT JOIN {$sqlname}personcat ON crd.pid = {$sqlname}personcat.pid
	WHERE
		crd.crid > 0
		$sort and
		crd.identity = '$identity'
	";

	$result    = $db -> query($query);
	$all_lines = $db -> affectedRows($result);
	if (empty($page) || $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	if ($ord == 'invoice') {
		$ordd = ' (crd.invoice -1)';
	}
	elseif ($ord == 'invoice_chek') {
		$ordd = ' (crd.invoice_chek -1)';
	}

	$query .= " ORDER BY $ordd $tuda LIMIT $lpos,$lines_per_page";

	$result      = $db -> query($query);
	$count_pages = ceil($all_lines / $lines_per_page);

	//print $acs_credit;

	while ($da = $db -> fetch($result)) {

		$color   = '';
		$warning = '';
		$close   = '';
		$view    = '';
		$dole    = '';
		$cando   = '';
		$do      = '';
		$isclose = '';

		if ($da['do'] == 'on') {
			$do = 1;
		}
		else {

			if ($acs_credit == 'on') {
				$cando = 1;
			}
			else {
				$do = 1;
				//$cando = 1;
			}

			$color = 'redbg-sublite';

			if ($da['close'] == 'yes') {
				$warning = 1;
				$close   = '<i class="icon-lock" title="Закрыта"></i>';
				$color   = 'graybg';
				$isclose = 1;
			}


		}

		//найдем дублирующие счета (не полная оплата одного счета)
		$r     = $db -> getRow("SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE invoice ='".$da['invoice']."' and did ='".$da['did']."' and identity = '$identity' GROUP BY invoice");
		$count = (int)$r["count"];
		$summa = $r["summa"];

		$delta = $da['summa'] > 0 ? number_format(( $da['summa_credit'] / $da['summa'] ) * 100, 2, ",", " ") : 0;

		if ($count > 1) {

			$dole = $summa > 0 ? number_format($da['summa_credit'] / $summa * 100, 2, ",", " ") : 0;

		}

		if ($otherSettings['printInvoice']) {
			$view = '1';
		}

		$list[] = [
			"id"           => $da['crid'],
			"datum"        => get_sfdate2($da['datum']),
			"datum_credit" => format_date_rus($da['datum_credit']),
			"invoice_date" => format_date_rus($da['invoice_date']),
			"contract"     => $da['invoice_chek'],
			"invoice"      => $da['invoice'],
			"summa"        => num_format($da['summa_credit']),
			"color"        => $color,
			"ddo"          => $do,
			"warning"      => $warning,
			"do"           => $do,
			"cando"        => $cando,
			"view"         => $view,
			"clid"         => $da['clid'],
			"client"       => $da['client'],
			"pid"          => $da['pid'],
			"person"       => $da['person'],
			"did"          => $da['did'],
			"deal"         => $da['dogovor'],
			"isclose"      => $isclose,
			"count"        => $count.' '.morph($count, "часть", "части", "частей"),
			"dole"         => $dole,
			"company"      => $mycomps[$da['mc']],
			"mc"           => $da['mc'],
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => (int)$page,
		"pageall" => (int)$count_pages,
		"ord"     => $ord,
		"tuda"    => $tuda,
		"valuta"  => $valuta
	];

	print json_encode_cyr($lists);

	exit();
}

/**
 * Покрытие счетов актами. На перспективу
 */
if ($tar == 'creditakt') {

	$ordd = $sqlname."credit.".$_REQUEST['ord'];

	if ($pay1 == 'yes' && $pay2 != 'yes') {
		$sort .= " and {$sqlname}credit.do = 'on'";
	}
	elseif ($pay1 != 'yes' && $pay2 == 'yes') {
		$sort .= " and {$sqlname}credit.do != 'on'";
	}

	if ($iduser != '') {
		$sort .= " and {$sqlname}credit.iduser= '".$iduser."'";
	}
	else {
		$sort .= " and {$sqlname}credit.iduser IN (".implode(",", get_people($iduser1, true)).")";
	}

	if ($wordp != "") {
		$sort .= " and ({$sqlname}credit.invoice LIKE '%".$wordp."%' or {$sqlname}credit.invoice_chek LIKE '%".$wordp."%' or {$sqlname}dogovor.title LIKE '%".$wordp."%' or {$sqlname}clientcat.title LIKE '%".$wordp."%')";
	}

	if ($mc > 0) {
		$sort .= " and {$sqlname}credit.rs IN (SELECT id FROM {$sqlname}mycomps_recv WHERE {$sqlname}mycomps_recv.cid = '$mc')";
	}

	$lines_per_page = $num_client; //Стоимость записей на страницу

	$query = "
	SELECT
		{$sqlname}credit.crid,
		{$sqlname}credit.invoice,
		{$sqlname}credit.datum_credit,
		{$sqlname}credit.invoice_date,
		{$sqlname}credit.invoice_chek,
		{$sqlname}credit.summa_credit,
		{$sqlname}credit.do,
		{$sqlname}credit.pid,
		{$sqlname}credit.clid,
		{$sqlname}credit.did,
		{$sqlname}clientcat.title as client,
		{$sqlname}personcat.person as person,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}dogovor.kol as summa,
		{$sqlname}dogovor.close as close,
		(SELECT cid FROM {$sqlname}mycomps_recv WHERE {$sqlname}mycomps_recv.id = {$sqlname}credit.rs) as mc
	FROM {$sqlname}credit
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
		LEFT JOIN {$sqlname}personcat ON {$sqlname}credit.pid = {$sqlname}personcat.pid
	WHERE
		{$sqlname}credit.crid!=''
		".$sort." and
		{$sqlname}credit.identity = '$identity'
	";

	$result    = $db -> query($query);
	$all_lines = $db -> affectedRows($result);
	if (empty($page) || $page <= 0) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	if ($ord == 'invoice') {
		$ordd = ' ('.$sqlname.'credit.invoice -1)';
	}
	elseif ($ord == 'invoice_chek') {
		$ordd = ' ('.$sqlname.'credit.invoice_chek -1)';
	}

	$query .= " ORDER BY $ordd $tuda LIMIT $lpos,$lines_per_page";

	$result      = $db -> query($query);
	$count_pages = ceil($all_lines / $lines_per_page);

	//print $acs_credit;

	while ($da = $db -> fetch($result)) {

		$color   = '';
		$warning = '';
		$close   = '';
		$view    = '';
		$dole    = '';

		if ($da['do'] == 'on') {
			$do = '<i class="icon-ok blue"></i>';
		}
		else {

			if ($acs_credit == 'on') {
				$do = '<a href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите поставить отметку о поступлении платежа?\');if (cf)editCredit(\''.$da['crid'].'\',\'credit.doit\');" title="Поставить отметку об оплате"><i class="icon-plus-circled red"></i></a>';
			}
			else {
				$do = '<a href="javascript:void(0)" title="Вы не можете ставить оплаты"><i class="icon-minus-circled gray"></i></a>';
			}

			$color = 'redbg-sub';

			if ($da['close'] == 'yes') {
				$warning = '<i class="icon-bitbucket red" title="Скорее всего счет не будет оплачен"></i>';
				$close   = '<i class="icon-lock" title="Закрыта"></i>';
				$color   = 'graybg';
			}


		}

		//найдем дублирующие счета (не полная оплата одного счета)
		$r     = $db -> getRow("SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE invoice ='".$da['invoice']."' and did ='".$da['did']."' and identity = '$identity' GROUP BY invoice");
		$count = (int)$r["count"];
		$summa = $r["summa"];

		$delta = $da['summa'] > 0 ? number_format(( $da['summa_credit'] / $da['summa'] ) * 100, 2, ",", " ") : 0;

		if ($count > 1) {

			$dole = $summa > 0 ? '<b>'.number_format($da['summa_credit'] / $summa * 100, 2, ",", " ").'%</b>' : 0;

		}

		if ($otherSettings['printInvoice']) {
			$view = '1';
		}

		$list[] = [
			"id"           => $da['crid'],
			"datum_credit" => format_date_rus($da['datum_credit']),
			"invoice_date" => format_date_rus($da['invoice_date']),
			"contract"     => $da['invoice_chek'],
			"invoice"      => $da['invoice'],
			"summa"        => num_format($da['summa_credit']),
			"color"        => $color,
			"ddo"          => $do,
			"warning"      => $warning,
			"view"         => $view,
			"clid"         => $da['clid'],
			"client"       => $da['client'],
			"pid"          => $da['pid'],
			"person"       => $da['person'],
			"did"          => $da['did'],
			"deal"         => $da['dogovor'],
			"count"        => $count.' '.morph($count, "часть", "части", "частей"),
			"dole"         => $dole,
			"mc"           => $mycomps[$da['mc']]
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => (int)$page,
		"pageall" => (int)$count_pages,
		"ord"     => $ord,
		"tuda"    => $tuda,
		"valuta"  => $valuta
	];

	//print $query."\n";
	//print_r($lists);

	print json_encode_cyr($lists);

	exit();
}