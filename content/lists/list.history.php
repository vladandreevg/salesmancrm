<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$page = (int)$_REQUEST['page'];
$task = $_REQUEST['tsk'];
$d1   = $_REQUEST['da1'];
$d2   = $_REQUEST['da2'];
$word = trim( $_REQUEST['word'] );
$user = implode( ",", (array)$_REQUEST['user'] );

setcookie( "history_list", json_encode_cyr($_REQUEST), time() + 365 * 86400, "/" );

$sort           = '';
$name           = $list = [];
$lines_per_page = 30;

if ( $user != '') {
	$sort .= " hs.iduser IN (".$user.") AND";
}
elseif ($tipuser == "Менеджер продаж" && !$userRights['showhistory']){
	$sort .= " hs.iduser = '".$iduser1."' AND";
}


if ( $word != '' ) {
	$sort .= " hs.des LIKE '%".$word."%' AND";
}

//$sort .= ($d1 != '') ? " (hs.datum BETWEEN '".$d1." 00:00:01' AND '".$d2." 23:59:59') AND" : "";
$sort .= ($d1 != '') ? " ( DATE(hs.datum) >= '$d1' AND DATE(hs.datum) <= '$d2' ) AND" : "";

if ( !empty( $task ) ) {
	$sort .= " hs.tip IN (".yimplode( ",", $task, "'" ).") AND";
}

// Только активности связанные с напоминаниями
if ( $_REQUEST['to_task'] ) {
	$sort .= " 
		(SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = hs.cid AND {$sqlname}tasks.identity = '$identity') > 0 AND 
		hs.tip NOT IN ('СобытиеCRM','ЛогCRM') AND 
	";
}

if ( $_REQUEST['status'] ) {
	$sort .= " 
		(SELECT COUNT(tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = hs.cid AND {$sqlname}tasks.status = '$_REQUEST[status]') > 0 AND 
	";
}

$query = "
	SELECT 
		COUNT(hs.cid) 
	FROM {$sqlname}history `hs`
	WHERE 
		hs.cid > 0 and
		$sort 
		hs.identity = '$identity'
";

$all_lines = $db -> getOne( $query );

$page = (empty( $page ) || $page <= 0) ? 1 : (int)$page;

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;
$count_pages    = ceil( $all_lines / $lines_per_page );

if ( $count_pages < 1 ) {
	$count_pages = 1;
}

$query  = "
	SELECT 
		hs.cid,
		hs.des,
		hs.datum,
		hs.tip,
		hs.clid,
		hs.did,
		hs.pid,
		hs.iduser,
		cc.title as client,
		deal.title as deal,
		us.title as user,
		tsk.tid as tid,
		tsk.title as task,
		tsk.status as status,
		a.color as color
	FROM {$sqlname}history `hs`
		LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = hs.clid
		LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = hs.did
		LEFT JOIN {$sqlname}user `us` ON us.iduser = hs.iduser
		LEFT JOIN {$sqlname}tasks `tsk` ON tsk.cid = hs.cid
	    LEFT JOIN {$sqlname}activities `a` ON a.title = hs.tip
	WHERE 
		hs.cid > 0 and 
		$sort 
		hs.identity = '$identity' 
	ORDER BY hs.datum DESC 
	LIMIT $lpos,$lines_per_page
";
$result = $db -> query( $query );
while ($da = $db -> fetch( $result )) {

	$tid     = '';
	$color   = $da['color'];
	$title   = '';
	$content = '';

	//$res          = $db -> getRow( "SELECT tid, title, status FROM {$sqlname}tasks WHERE cid = '".$da['cid']."' and identity = '$identity'" );
	$tid          = (int)$da["tid"];
	$title        = $da["task"];
	$tstatus      = (int)$da["status"];

	//$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title = '".$da['tip']."' and identity = '$identity'" );
	if ( $color == "" ) {
		$color = "gray";
	}

	$content = htmlspecialchars_decode( $da['des'] );
	if ( preg_match( '|<body.*?>(.*)</body>|si', $content, $arr ) ) {
		$content = $arr[1];
	}

	$content = mb_substr( untag( nl2br( $content ) ), 0, 101, 'utf-8' );

	if ( $tstatus == 1 ) {
		$status = '<i class="icon-ok green"></i>';
	}
	elseif ( $tstatus == 2 ) {
		$status = '<i class="icon-block red"></i>';
	}
	else {
		$status = '<i class="icon-ok green"></i>';
	}

	$list[] = [
		"cid"           => $da['cid'],
		"datum"         => get_sfdate( $da['datum'] ),
		"icon"          => get_ticon( $da['tip'] ),
		"tip"           => $da['tip'],
		"tasktitle"     => $title,
		"content"       => $content,
		"color"         => $color,
		"clid"          => $da['clid'],
		"client"        => $da['client'],
		"pid"           => $da['pid'],
		"person"        => current_person( $da['pid'] ),
		"did"           => $da['did'],
		"deal"          => $da['deal'],
		"user"          => $da['user'],
		"tid"           => $tid > 0 ? $tid : NULL,
		"status"        => $status,
		"tstatus"       => $tstatus,
		"statusTooltip" => ($tstatus == 1) ? "Успешно" : "Не успешно",
	];

}

$lists = [
	"list"    => $list,
	"page"    => (int)$page,
	"pageall" => (int)$count_pages
];

print json_encode_cyr( $lists );

exit();