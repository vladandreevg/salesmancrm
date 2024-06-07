<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
/* Developer: Iskopaeva Liliya  */
error_reporting(E_ERROR);
ini_set('display_errors', 1);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

function dateFormat($date_orig, $format = 'excel') {

	$date_new = '';

	if ($format == 'excel') {

		if ($date_orig != '0000-00-00' and $date_orig != '' and $date_orig != NULL) {
			/*
			$dstart = $date_orig;
			$dend = '1970-01-01';
			$date_new = intval((date_to_unix($dstart) - date_to_unix($dend))/86400)+25570;
			*/
			$date_new = $date_orig;
		}
		else {
			$date_new = '';
		}

	}
	elseif ($format == 'date') {

		if ($date_orig && $date_orig != '0000-00-00') {

			$date_new = explode("-", $date_orig);
			$date_new = $date_new[1].".".$date_new[2].".".$date_new[0];

		}
		else {
			$date_new = '';
		}

	}
	elseif ($date_orig != '0000-00-00' || $date_orig == '') {
		$date_new = '';
	}

	return $date_new;
}

function num2excelExt($string, $s = 2) {

	if (!$string) {
		$string = 0;
	}

	$string = str_replace(",", ".", $string);
	$string = str_replace(" ", " ", $string);

	$string = number_format($string, $s, '.', ' ');

	return $string;
}

function date2mounthyear($date) {
	$date = yexplode("-", $date);

	return $date[0]."-".$date[1];
}

function date2array($date) {
	$date = yexplode("-", $date);

	return [
		$date[0],
		$date[1],
		$date[2]
	];
}

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];

$clientTip       = (array)$_REQUEST['clientTip'];
$clientTerritory = (array)$_REQUEST['clientTerritory'];
$clientPath      = (array)$_REQUEST['clientPath'];

$per = getPeriod('month');

if (!$da1) {
	$da1 = $per[0];
}
if (!$da2) {
	$da2 = $per[1];
}

$user_list   = (array)$_REQUEST['user_list'];
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort       = '';
$dogs       = [];
$dirs       = [];
$dirc       = [];
$directions = [];
$dataset1   = [];
$order      = [];

$color = [
	'#AD1457',
	'#FF8A65',
	'#F9A825',
	'#2E7D32',
	'#0277BD',
	'#3F51B5',
	'#6A1B9A',
	'#546E7A',
	'#78909C',
	'#00695C',
	'#9E9D24'
];

$thisfile = basename($_SERVER['PHP_SELF']);

//массив пользователей
$userlist = ( !empty($user_list) ) ? $user_list : (array)get_people($iduser1, "yes");

//фильтр по типам клиентов
if (!empty($clientTip)) {
	$sort .= " and {$sqlname}clientcat.type IN (".yimplode(",", $clientTip, "'").")";
}

//фильтр по территории
if (!empty($clientTerritory)) {
	$sort .= " and {$sqlname}clientcat.territory IN (".yimplode(",", $clientTerritory, "'").")";
}

//фильтр по источнику
if (!empty($clientPath)) {
	$sort .= " and {$sqlname}clientcat.clientpath IN (".yimplode(",", $clientPath, "'").")";
}

//составляем запрос по параметрам сделок
$ar = [
	'close',
	'sid'
];
foreach ($fields as $i => $field) {

	if (
		!in_array($field, $ar) && !in_array($field, [
			'close',
			'mcid'
		])
	) {
		$sort .= " {$sqlname}dogovor.".$field." = '".$field_query[$i]."' AND ";
	}
	elseif ($field == 'close') {
		$sort .= $field_query[$i] != 'yes' ? " COALESCE({$sqlname}dogovor.{$field}, 'no') != 'yes' AND " : " COALESCE({$sqlname}dogovor.{$field}, 'no') == 'yes' AND ";
	}
	elseif ($field == 'mcid') {
		$mc = $field_query[$i];
	}

}

$format = ( $action == 'export' ) ? 'excel' : 'date';

//массив направлений
$da = $db -> getAll("SELECT id, title FROM {$sqlname}direction WHERE identity = '$identity'");
foreach ($da as $data) {

	$directions1[$data['id']] = $data['title'];

}
$directions2 = ["0" => "----- ВНЕ НАПРАВЛЕНИЙ -----"];

$directions = $directions1 + $directions2;

if ($action == "newDogs") {

	$direction = $_REQUEST['direction'];

	$q = "
	SELECT
		{$sqlname}dogovor.did as did,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}dogovor.datum as dcreate,
		{$sqlname}dogovor.datum_plan as dplan,
		{$sqlname}dogovor.datum_close as dclose,
		{$sqlname}dogovor.idcategory as idstep,
		{$sqlname}dogovor.tip as tip,
		{$sqlname}dogovor.clid as clid,
		{$sqlname}dogovor.pid as pid,
		{$sqlname}dogovor.kol as kol,
		{$sqlname}dogovor.marga as marga,
		{$sqlname}dogovor.kol_fact as kolf,
		{$sqlname}dogovor.close as close,
		{$sqlname}dogovor.iduser as iduser,
		{$sqlname}dogovor.adres as adres,
		{$sqlname}dogovor.content as content,
		{$sqlname}dogovor.direction as direction,
		{$sqlname}user.title as user,
		{$sqlname}clientcat.title as client,
		{$sqlname}dogcategory.title as step,
		{$sqlname}dogcategory.content as steptitle,
		{$sqlname}dogtips.title as tips,
		{$sqlname}dogstatus.title as dstatus
	FROM {$sqlname}dogovor
		LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
		LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
		LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
		LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
		LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
		LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
	WHERE
		{$sqlname}dogovor.datum between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
		{$sqlname}dogovor.direction = '".$direction."' and
		{$sqlname}dogovor.iduser in (".implode(",", $userlist).") and
		{$sqlname}dogovor.identity = '$identity'
		$sort
	ORDER BY {$sqlname}dogovor.datum";

	$result = $db -> query($q);

	while ($data = $db -> fetch($result)) {

		$color = '';
		$icon  = '';
		$kolf  = '';

		if ($data['close'] == 'yes') {
			$dfact = $data['dclose'];
			$icon  = '<i class="icon-lock red"></i>';
			$kolf  = $data['kolf'];
		}
		else {
			$dfact = '';
			$icon  = '<i class="icon-briefcase blue"></i>';
		}

		//цветовая схема
		if ($data['close'] == 'yes' and $data['kolf'] > 0) {
			$color = 'greenbg-sub';
		}
		if ($data['close'] == 'yes' and $data['kolf'] == 0) {
			$color = 'redbg-sub';
		}

		//Здоровье сделки. конец.
		$dogs[$data['user']][] = [
			"datum"   => dateFormat($data['dcreate'], $format),
			"did"     => $data['did'],
			"dogovor" => $data['dogovor'],
			"tip"     => $data['tips'],
			"step"    => $data['step'],
			"dplan"   => dateFormat($data['dplan'], $format),
			"dfact"   => dateFormat($dfact, $format),
			"client"  => $data['client'],
			"clid"    => $data['clid'],
			"summa"   => $data['kol'],
			"fsumma"  => $kolf,
			"marga"   => $data['marga'],
			"close"   => $data['close'],
			"comment" => $data['dstatus'],
			"color"   => $color,
			"icon"    => $icon
		];

	}

	$string = '';

	foreach ($dogs as $user => $val) {

		$string .= '
			<tr height="35" class="bluebg">
				<td colspan="5">'.$user.'</td>
			</tr>
		';

		foreach ($val as $k => $v) {

			$string .= '
			<tr height="40" class="ha">
				<td width="80">'.$v['datum'].'</td>
				<td width="350">
					<div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor(\''.$v['did'].'\')">'.$v['icon'].'&nbsp;'.$v['dogovor'].'</a></div><br>
					<div class="ellipsis"><a href="javascript:void(0)" onclick="openClient(\''.$v['clid'].'\')" title=""><i class="icon-building broun"></i>&nbsp;'.$v['client'].'</a></div>
				</td>
				<td>'.$v['step'].'%</td>
				<td align="right">'.num_format($v['summa']).'</td>
				<td></td>
			</tr>
			';

		}

	}

	if (count($dogs) > 0) {
		print '
	<div class="pad10">
		<div class="pull-aright fs-14"><i class="icon-cancel-circled gray hand cancel"></i></div>
		<div class="Bold fs-14 blue margbot5">Новые сделки</div>
		<div class="margbot5">Направление: <b>'.strtr($direction, $directions).'</b></div>
		<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" class="bgwhite">
		<thead>
		<tr>
			<th class="w80">Дата</th>
			<th class="w350">Сделка / Клиент</th>
			<th class="w60">Этап</th>
			<th class="w100">Сумма</th>
			<th class=""></th>
		</tr>
		</thead>
		<tbody>'.$string.'</tbody>
		</table>
	</div>
	';
	}
	else {
		print '<div class="warning w250">Нет данных</div>';
	}

	exit();

}
if ($action == "vistChet") {

	$direction = $_REQUEST['direction'];

	$q = "
	SELECT
		{$sqlname}credit.did as did,
		DATE_FORMAT({$sqlname}credit.invoice_date, '%Y-%m-%d') as datum,
		{$sqlname}credit.datum_credit as dplan,
		{$sqlname}credit.summa_credit as summa,
		{$sqlname}credit.invoice as invoice,
		{$sqlname}credit.do as do,
		{$sqlname}credit.iduser as iduser,
		{$sqlname}credit.clid as clid,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}user.title as user,
		{$sqlname}clientcat.title as client
	FROM {$sqlname}credit
		LEFT JOIN {$sqlname}user ON {$sqlname}credit.iduser = {$sqlname}user.iduser
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
		LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
		LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
	WHERE
		{$sqlname}credit.datum between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
		{$sqlname}dogovor.direction = '".$direction."' and
		{$sqlname}credit.iduser in (".implode(",", $userlist).") and
		{$sqlname}credit.identity = '$identity'
		$sort
	ORDER BY {$sqlname}credit.datum";

	$result = $db -> query($q);
	while ($data = $db -> fetch($result)) {

		if ($data['do'] == 'on') {
			$icon = '<i class="icon-ok green"></i>';
		}
		else {
			$icon = '<i class="icon-minus-1 gray"></i>';
		}

		//Здоровье сделки. конец.
		$dogs[$data['user']][] = [
			"datum"   => dateFormat($data['datum'], $format),
			"dplan"   => dateFormat($data['dplan'], $format),
			"invoice" => $data['invoice'],
			"did"     => $data['did'],
			"dogovor" => $data['dogovor'],
			"client"  => $data['client'],
			"clid"    => $data['clid'],
			"summa"   => $data['summa'],
			"icon"    => $icon
		];

	}

	$string = '';

	foreach ($dogs as $user => $val) {

		$string .= '
			<tr height="35" class="bluebg">
				<td colspan="7">'.$user.'</td>
			</tr>
		';

		foreach ($val as $k => $v) {

			$string .= '
			<tr height="35" class="ha">
				<td width="80">'.$v['datum'].'</td>
				<td width="100">'.$v['dplan'].'</td>
				<td width="100" align="right">'.$v['invoice'].'</td>
				<td width="30">'.$v['icon'].'</td>
				<td width="100" align="right">'.num2excelExt($v['summa']).'</td>
				<td width="350">
					<div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor(\''.$v['did'].'\')"><i class="icon-briefcase blue"></i>&nbsp;'.$v['dogovor'].'</a></div><br>
					<div class="ellipsis"><a href="javascript:void(0)" onclick="openClient(\''.$v['clid'].'\')" title=""><i class="icon-building broun"></i>&nbsp;'.$v['client'].'</a></div>
				</td>
				<td></td>
			</tr>
			';

		}

	}

	if (count($dogs) > 0) {
		print '
	<div class="pad10">
		<div class="pull-aright fs-14"><i class="icon-cancel-circled gray hand cancel"></i></div>
		<div class="Bold fs-14 blue margbot5">Выставленные счета</div>
		<div class="margbot5">Направление: <b>'.strtr($direction, $directions).'</b></div>
		<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" class="bgwhite">
		<thead>
		<tr>
			<th class="w100">Дата</th>
			<th class="w100">Факт</th>
			<th class="w100">Номер</th>
			<th class="w30"></th>
			<th class="w120">Сумма</th>
			<th class="w350">Сделка / Клиент</th>
			<th class=""></th>
		</tr>
		</thead>
		<tbody>'.$string.'</tbody>
		</table>
	</div>
	';
	}
	else {
		print '<div class="warning w250">Нет данных</div>';
	}

	exit();

}
if ($action == "doInvoice") {

	$direction = $_REQUEST['direction'];

	$q = "
	SELECT
		{$sqlname}credit.did as did,
		DATE_FORMAT({$sqlname}credit.invoice_date, '%Y-%m-%d') as datum,
		{$sqlname}credit.datum_credit as dplan,
		{$sqlname}credit.summa_credit as summa,
		{$sqlname}credit.invoice as invoice,
		{$sqlname}credit.do as do,
		{$sqlname}credit.iduser as iduser,
		{$sqlname}credit.clid as clid,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}user.title as user,
		{$sqlname}clientcat.title as client
	FROM {$sqlname}credit
		LEFT JOIN {$sqlname}user ON {$sqlname}credit.iduser = {$sqlname}user.iduser
		LEFT JOIN {$sqlname}clientcat ON {$sqlname}credit.clid = {$sqlname}clientcat.clid
		LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
		LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
		LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
	WHERE
		{$sqlname}credit.invoice_date between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
		{$sqlname}dogovor.direction = '".$direction."' and
		{$sqlname}credit.iduser in (".implode(",", $userlist).") and
		{$sqlname}credit.identity = '$identity'
		$sort
	ORDER BY {$sqlname}credit.invoice_date";

	$result = $db -> query($q);
	while ($data = $db -> fetch($result)) {

		if ($data['do'] == 'on') {
			$icon = '<i class="icon-ok green"></i>';
		}
		else {
			$icon = '<i class="icon-minus-1 gray"></i>';
		}

		//Здоровье сделки. конец.
		$dogs[$data['user']][] = [
			"datum"   => dateFormat($data['datum'], $format),
			"dplan"   => dateFormat($data['dplan'], $format),
			"invoice" => $data['invoice'],
			"did"     => $data['did'],
			"dogovor" => $data['dogovor'],
			"client"  => $data['client'],
			"clid"    => $data['clid'],
			"summa"   => $data['summa'],
			"icon"    => $icon
		];

	}

	$string = '';

	foreach ($dogs as $user => $val) {

		$string .= '
			<tr height="35" class="bluebg">
				<td colspan="7">'.$user.'</td>
			</tr>
		';

		foreach ($val as $k => $v) {

			$string .= '
			<tr height="35" class="ha">
				<td width="80">'.$v['datum'].'</td>
				<td width="100">'.$v['dplan'].'</td>
				<td width="100" align="right">'.$v['invoice'].'</td>
				<td width="30">'.$v['icon'].'</td>
				<td width="100" align="right">'.num2excelExt($v['summa']).'</td>
				<td width="350">
					<div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor(\''.$v['did'].'\')"><i class="icon-briefcase blue"></i>&nbsp;'.$v['dogovor'].'</a></div><br>
					<div class="ellipsis"><a href="javascript:void(0)" onclick="openClient(\''.$v['clid'].'\')" title=""><i class="icon-building broun"></i>&nbsp;'.$v['client'].'</a></div>
				</td>
				<td></td>
			</tr>
			';

		}

	}

	if (count($dogs) > 0) {
		print '
	<div class="pad10">
		<div class="pull-aright fs-14"><i class="icon-cancel-circled gray hand cancel"></i></div>
		<div class="Bold fs-14 blue margbot5">Оплаченные счета</div>
		<div class="margbot5">Направление: <b>'.strtr($direction, $directions).'</b></div>
		<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0" class="bgwhite">
		<thead>
		<tr>
			<th class="w100">Дата</th>
			<th class="w100">Факт</th>
			<th class="w100">Номер</th>
			<th class="w30"></th>
			<th class="w120">Сумма</th>
			<th class="w350">Сделка / Клиент</th>
			<th class=""></th>
		</tr>
		</thead>
		<tbody>'.$string.'</tbody>
		</table>
	</div>
	';
	}
	else {
		print '<div class="warning w250">Нет данных</div>';
	}

	exit();

}
if ($action == "") {

	$dogs = $total = [];

	//все сделки (оставила фильтр по дате и по сотруднику)
	$vseDogs = $db -> getRow("
			SELECT 
				COUNT(*) as count
			FROM {$sqlname}dogovor 
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}dogovor.clid 
				LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
				LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
			WHERE 
				{$sqlname}dogovor.did > 0 and 
				{$sqlname}dogovor.datum between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
				{$sqlname}dogovor.iduser in (".implode(",", $userlist).") and 
				{$sqlname}dogovor.identity = '$identity'
				$sort
			");

	//формирование данных
	foreach ($directions as $key => $val) {

		//прибыль
		$q = "
		SELECT 
			{$sqlname}credit.summa_credit as credit,
			{$sqlname}dogovor.direction,
			{$sqlname}dogovor.kol as summa,
			{$sqlname}dogovor.marga as marga
		FROM {$sqlname}credit 
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}credit.clid 
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
			LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
		WHERE 
			{$sqlname}credit.do = 'on' and 
			{$sqlname}credit.invoice_date between '".$da1." 00:00:00' and '".$da2." 23:59:59' and
			{$sqlname}dogovor.iduser in (".implode(",", $userlist).") and 
			{$sqlname}dogovor.direction = '".$key."' and 
			{$sqlname}credit.identity = '$identity'
			$sort
		";

		$result = $db -> query($q);
		while ($da = $db -> fetch($result)) {

			//% оплаченной суммы от суммы по договору
			$dolya                = ( $da['summa'] > 0 ) ? $da['marga'] / $da['summa'] : 0;
			$dogs[$key]['pribil'] += $da['credit'] * $dolya;

		}

		//Оплачено счетов (сумма и кол-во)
		// Ср. прибыль = Прибыль / кол-во ОП
		$data = $db -> getRow("
		SELECT 
			COUNT(*) as count, 
			SUM({$sqlname}credit.summa_credit) as summa 
		FROM {$sqlname}credit 
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}credit.clid 
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
			LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
		WHERE 
			{$sqlname}credit.did > 0 and 
			{$sqlname}credit.do = 'on' and 
			{$sqlname}credit.invoice_date between '$da1 00:00:00' and '$da2 23:59:59' and
			{$sqlname}credit.iduser in (".implode(",", $userlist).") and 
			{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE direction = '$key' and identity = '$identity') and 
			{$sqlname}credit.identity = '$identity'
			$sort
		");

		$dogs[$key]['doInvoice']      = $data['count'];
		$dogs[$key]['doInvoiceSumma'] = $data['summa'];

		//новые сделки
		$data                  = $db -> getRow("
			SELECT 
				COUNT(*) as count
			FROM {$sqlname}dogovor 
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}dogovor.clid 
				LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
				LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory
			WHERE 
				{$sqlname}dogovor.did > 0 and 
				{$sqlname}dogovor.datum between '$da1 00:00:00' and '$da2 23:59:59' and
				{$sqlname}dogovor.iduser in (".implode(",", $userlist).") and 
				{$sqlname}dogovor.direction = '$key' and 
				{$sqlname}dogovor.identity = '$identity'
				$sort
			");
		$dogs[$key]['newDogs'] = $data['count'];

		//% - процент созданных сделок от общего числа
		$dogs[$key]['prozDogs'] = $vseDogs['count'] > 0 ? ( $dogs[$key]['newDogs'] * 100 ) / $vseDogs['count'] : 0;

		//Конверсия - ОП / НС
		$dogs[$key]['konversia'] = $dogs[$key]['newDogs'] > 0 ? $dogs[$key]['doInvoice'] / $dogs[$key]['newDogs'] : 0;

		//средний чек
		$dogs[$key]['ratio'] = $dogs[$key]['newDogs'] > 0 ? $dogs[$key]['doInvoice'] / $dogs[$key]['newDogs'] : 0;

		//Выставлено счетов (сумма и кол-во)
		//Ср. чек = оборот в оплаченных счетах / ОП (кол-во оплаченные счета)

		$data = $db -> getRow("
		SELECT 
			COUNT(*) as count, 
			SUM({$sqlname}credit.summa_credit) as summa 
		FROM {$sqlname}credit 
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}credit.clid 
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath 
			LEFT JOIN {$sqlname}territory_cat ON {$sqlname}territory_cat.idcategory = {$sqlname}clientcat.territory 
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
		WHERE 
			{$sqlname}credit.did > 0 and 
			{$sqlname}credit.datum between '$da1 00:00:00' and '$da2 23:59:59' and
			{$sqlname}credit.iduser in (".implode(",", $userlist).") and 
			{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE direction = '$key' and identity = '$identity') and 
			{$sqlname}credit.identity = '$identity'
			$sort
		");

		$dogs[$key]['vistChetSumma'] = $data['summa'];
		$dogs[$key]['vistChet']      = $data['count'];
		$dogs[$key]['oborot']        = $dogs[$key]['doInvoice'] > 0 ? $dogs[$key]['doInvoiceSumma'] / $dogs[$key]['doInvoice'] : 0;

	}
	?>
	<!--стиль для сортировки таблицы-->
	<link rel="stylesheet" href="/assets/js/tablesorter/style.css"/>
	<script type="text/javascript" src="/assets/js/tablesorter/script.js"></script>
	<div class="zagolovok_rep" align="center">

		<h2>Анализ показателей по направлениям</h2>

	</div>
	<div class="noprint">

		<hr>
		<div class="pad5 mt20 gray2 Bold">Фильтры по клиентам:</div>
		<!--дополнителный фильтры-->
		<table width="100%" border="0" cellspacing="0" cellpadding="5" class="noborder">
			<tr>
				<td width="20%">
					<div class="ydropDown">
						<span>По Типу клиента</span><span class="ydropCount"><?= count($clientTip) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">

							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="client" <?php
									if (in_array("client", $clientTip)) {
										print 'checked';
									} ?>>&nbsp;Клиент: юр.лицо
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="person" <?php
									if (in_array("person", $clientTip)) {
										print 'checked';
									} ?>>&nbsp;Клиент: физ.лицо
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="partner" <?php
									if (in_array("partner", $clientTip)) {
										print 'checked';
									} ?>>&nbsp;Партнер
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="contractor" <?php
									if (in_array("contractor", $clientTip)) {
										print 'checked';
									} ?>>&nbsp;Поставщик
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="concurent" <?php
									if (in_array("concurent", $clientTip)) {
										print 'checked';
									} ?>>&nbsp;Конкурент
								</label>
							</div>

						</div>
					</div>
				</td>
				<td width="20%">
					<div class="ydropDown">
						<span>По Территории</span><span class="ydropCount"><?= count($clientTerritory) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">
							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>
							<?php
							$result = $db -> getAll("SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' ORDER BY title");
							foreach ($result as $data) {
								?>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="clientTerritory[]" type="checkbox" id="clientTerritory[]" value="<?= $data['idcategory'] ?>" <?php
										if (in_array($data['idcategory'], $clientTerritory)) {
											print 'checked';
										} ?>>&nbsp;<?= $data['title'] ?>
									</label>
								</div>
							<?php
							} ?>
						</div>
					</div>
				</td>
				<td width="20%">
					<div class="ydropDown">
						<span>По Источнику</span><span class="ydropCount"><?= count($clientPath) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">
							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>
							<?php
							$result = $db -> getAll("SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER BY name");
							foreach ($result as $data) {
								?>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="clientPath[]" type="checkbox" id="clientPath[]" value="<?= $data['id'] ?>" <?php
										if (in_array($data['id'], $clientPath)) {
											print 'checked';
										} ?>>&nbsp;<?= $data['name'] ?>
									</label>
								</div>
							<?php
							} ?>
						</div>
					</div>
				</td>
				<td width="20%"></td>
			</tr>
		</table>

	</div><br>
	<table id="table" class="sortable">
		<thead>
		<tr height="45">
			<th width="20%">Направление</th>
			<?php
			print '
			<th title="Общая прибыль в сделках">Прибыль</th>
			<th align="center" title="Средняя прибыль по сделкам"><div>Ср. приб.</div></th>
			<th align="center" title="Процент созданых сделок от общего числа"><div>%</div></th>
			<th align="center" title="Конверсия"><div>Конверсия</div></th>
			<th align="center" title="Новые сделки"><div>НС</div></th>
			<th align="center" title="Выставленные счета"><div>ВС</div></th>
			<th align="center" title="Оплаченные счета"><div>ОП</div></th>
			<th class="redbg-sub" align="center" title="Средний чек в сделке"><div>Ср. чек</div></th>
			
			';
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		//сортировка по прибыли таблицы
		function cmp($a, $b) {
			return $b['pribil'] > $a['pribil'];
		}
		uasort($dogs, 'cmp');

		$i = 0;
		foreach ($dogs as $key => $val) {
			?>
			<tr height="45" class="ha" data-dir="<?= $key ?>">
				<td><span><?= strtr($key, $directions) ?></span></td>
				<?php
				print '
				<td align="center">'.number_format($val['pribil'], 2, ',', '').'</td>
				<td align="center">'.( $val['doInvoice'] > 0 ? number_format($val['pribil'] / $val['doInvoice'], 2, ',', '') : 0 ).'</td>
				<td align="center">'.number_format($val['prozDogs'], 0, ',', '').'</td>
				<td align="center">'.number_format($val['konversia'], 2, ',', '').'</td>
				<td align="center" title ="Загрузить список" onclick="showData(\''.$key.'\',\'newDogs\')">'.$val['newDogs'].'</td>
				<td align="center" onclick="showData(\''.$key.'\',\'vistChet\')" title ="Загрузить список">'.$val['vistChet'].'</td>
				<td align="center" onclick="showData(\''.$key.'\',\'doInvoice\')" title ="Загрузить список">'.$val['doInvoice'].'</td>
				<td align="center">'.number_format($val['oborot'], 2, ',', '').'</td>
				
				';
				$total['pribil']         += $val['pribil'];
				$total['doInvoiceSumma'] += $val['doInvoiceSumma'];
				$total['prozDogs']       += $val['prozDogs'];
				$total['newDogs']        += $val['newDogs'];
				$total['vistChet']       += $val['vistChet'];
				$total['doInvoice']      += $val['doInvoice'];

				?>
			</tr>
			<?php
			$i++;
			if ($i > count($color)) {
				$i = 0;
			}
		}
		?>
		</tbody>
		<tfoot>
		<tr height="45" class="graybg">
			<td>
				<div class="Bold fs-11">Итого:</div>
			</td>
			<?php
			print '
			<td align="center">'.number_format($total['pribil'], 2, ',', '').'</td>
			<td align="center">'.( $total['doInvoice'] > 0 ? number_format($total['pribil'] / $total['doInvoice'], 2, ',', '') : '0' ).'</td>
			<td align="center">'.$total['prozDogs'].'</td>
			<td align="center">'.( $total['newDogs'] > 0 ? number_format($total['doInvoice'] / $total['newDogs'], 2, ',', '') : '0' ).'</td>
			<td align="center">'.$total['newDogs'].'</td>
			<td align="center">'.$total['vistChet'].'</td>
			<td align="center">'.$total['doInvoice'].'</td>
			<td align="center">'.( $total['doInvoice'] > 0 ? number_format($total['doInvoiceSumma'] / $total['doInvoice'], 2, ',', '') : '0' ).'</td>
			';
			?>
		</tr>
		</tfoot>
	</table>
	<div id="datas" class="mt10"></div>
	<div style="height:80px"></div>
	<script>
		$(function () {

		});

		function showData(direction, tip) {

			var str = $('#selectreport').serialize();

			var da1 = $('#DA1').val();
			var da2 = $('#DA2').val();
			var url = 'reports/<?=$thisfile?>?action=' + tip + '&da1=' + da1 + '&da2=' + da2 + '&direction=' + direction;

			$('#datas').empty().append('<img src="/assets/images/loading.gif">');

			$.get(url, str, function (data) {

				$('#datas').html('<hr>' + data);
				$(".nano").nanoScroller();

				$('.cancel')
					.off('click')
					.on('click', function () {
						$('#datas').empty();
					});

			});

		}

		//для сортировки таблицы
		var sorter = new TINY.table.sorter("sorter");
		sorter.head = "head";
		sorter.asc = "asc";
		sorter.desc = "desc";
		sorter.even = "evenrow";
		sorter.odd = "oddrow";
		sorter.evensel = "evenselected";
		sorter.oddsel = "oddselected";
		//sorter.paginate = true;
		sorter.currentid = "currentpage";
		sorter.limitid = "pagelimit";
		sorter.init("table", 1);
	</script>
<?php
} ?>