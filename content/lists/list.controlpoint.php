<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        salesman.pro          */
/*        ver. 2018.x           */
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

$page = $_REQUEST['page'];
$ord  = $_REQUEST['ord'];
$tuda = $_REQUEST['tuda'];
$pay1 = $_REQUEST['pay1'];
$pay2 = $_REQUEST['pay2'];
$d1   = $_REQUEST['da1'];
$d2   = $_REQUEST['da2'];
$point = (array)$_REQUEST['point'];

$iduser = $_REQUEST['iduser'];
$action = $_REQUEST['action'];

$ord = ( $ord == '' ) ? "data_plan" : $ord; //параметр сортировки

$lines_per_page = 100; //Стоимость записей на страницу

$sort = '';

if ($pay1 == 'yes' && $pay2 != 'yes') {
	$sort .= " and doit = 'yes'";
}
elseif ($pay1 != 'yes' && $pay2 == 'yes') {
	$sort .= " and doit != 'yes'";
}

if ($d1 != '' && $d2 == '') {
	$sort .= " and data_plan >= '$d1 23:'";
}
elseif ($d1 == '' && $d2 != '') {
	$sort .= " and data_plan <= '$d2'";
}
elseif ($d1 != '' && $d2 != '') {
	$sort .= " and (data_plan BETWEEN '$d1' and '$d2')";
}

if( !empty($point) ){
	$sort .= " and ccid IN (".yimplode(",", $point).")";
}

$sort .= ( $iduser != '' ) ? " and iduser= '$iduser'" : get_people($iduser1);

$query     = "
	SELECT * 
	FROM {$sqlname}complect 
	WHERE 
		id > 0 
		$sort and 
	identity = '$identity'
";
$result    = $db -> query($query);
$all_lines = $db -> affectedRows($result);

$page           = ( empty($page) || $page <= 0 ) ? 1 : (int)$page;
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query .= " ORDER BY $ord $tuda LIMIT $lpos,$lines_per_page";

$result      = $db -> query($query);
$count_pages = ceil($all_lines / $lines_per_page);
if ($count_pages == 0) {
	$count_pages = 1;
}

while ($da = $db -> fetch($result)) {

	$do        = '';
	$color     = '';
	$data_fact = '';
	$ctitle    = '';

	$do = ( $da['doit'] == 'yes' ) ? '<i class="icon-ok blue" title="Сделано"></i>' : '<a href="javascript:void(0)" onclick="openDogovor(\''.$da['did'].'\')" title="Открыть сделку"><i class="icon-attention broun"></i></a>';

	$color = ( $da['doit'] != 'yes' ) ? 'redbg-sub' : '';

	$data_fact = ( $da['data_fact'] == '0000-00-00' ) ? '--' : format_date_rus($da['data_fact']);

	$ctitle = $db -> getOne("SELECT title FROM {$sqlname}complect_cat WHERE ccid = '".$da['ccid']."' and identity = '$identity'");

	//Выявим клиента
	$clid = getDogData($da['did'], 'clid');
	$pid  = getDogData($da['did'], 'pid');

	$list[] = [
		"id"        => $da['ccid'],
		"data_plan" => format_date_rus($da['data_plan']),
		"data_fact" => $data_fact,
		"title"     => $ctitle,
		"color"     => $color,
		"ddo"       => $do,
		"clid"      => $clid,
		"client"    => current_client($clid),
		"pid"       => $pid,
		"person"    => current_person($pid),
		"did"       => $da['did'],
		"deal"      => current_dogovor($da['did']),
		"iduser"    => $da['iduser'],
		"user"      => current_user($da['iduser'])
	];

}

$ss = ( $tuda == 'desc' ) ? '<i class="icon icon-angle-up"></i>' : '<i class="icon icon-angle-down"></i>';

$ord1 = ( $ord == 'data_plan' ) ? $ss : '';
$ord2 = ( $ord == 'data_fact' ) ? $ss : '';
$ord3 = ( $ord == 'ccid' ) ? $ss : '';

$lists = [
	"list"    => $list,
	"page"    => (int)$page,
	"pageall" => (int)$count_pages,
	"ord1"    => $ord1,
	"ord2"    => $ord2,
	"ord3"    => $ord3,
	"tuda"    => $tuda,
	"valuta"  => $valuta
];

//print $query."\n";
//print_r($lists);

print json_encode_cyr($lists);

exit();