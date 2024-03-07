<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$page    = (int)$_REQUEST['page'];
$iduser  = (int)$_REQUEST['iduser'];
$d1      = $_REQUEST['da1'];
$d2      = $_REQUEST['da2'];
$doo     = $_REQUEST['doo'];
$statuss = (array)$_REQUEST['status'];

$sort = '';

$status = [
	'0' => 'Новое',
	'1' => 'Обработано',
	'2' => 'Отменено'
];
$colors = [
	'0' => 'broun',
	'1' => 'green',
	'2' => 'gray'
];

$lines_per_page = $num_client; //Стоимость записей на страницу

if ( !empty( $statuss ) ) {
	$sort .= " and {$sqlname}entry.status IN (".implode( ",", $statuss ).")";
}

if ( $iduser < 1 )
	$sort .= str_replace( "iduser", $sqlname."entry.iduser", get_people( $iduser1 ) );
else $sort .= " and {$sqlname}entry.iduser = '$iduser'";

if ( $d1 != '' )
	$sort .= " and ({$sqlname}entry.datum BETWEEN '".$d1." 00:00:01' and '".$d2." 23:59:59')";

if ( $doo == 'do' )
	$sort .= " and {$sqlname}entry.did > 0";
if ( $doo == 'nodo' )
	$sort .= " and {$sqlname}entry.did = 0";

$query = "
SELECT
	{$sqlname}entry.ide as ide,
	{$sqlname}entry.datum as datum,
	{$sqlname}entry.datum_do as datum_do,
	{$sqlname}entry.pid as pid,
	{$sqlname}entry.clid as clid,
	{$sqlname}entry.did as did,
	{$sqlname}entry.content as content,
	{$sqlname}entry.status as status,
	{$sqlname}entry.iduser as iduser,
	{$sqlname}entry.autor as autor,
	{$sqlname}clientcat.title as client,
	{$sqlname}personcat.person as person,
	{$sqlname}dogovor.title as deal,
	{$sqlname}user.title as user
FROM {$sqlname}entry
	LEFT JOIN {$sqlname}user ON {$sqlname}entry.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}personcat ON {$sqlname}entry.pid = {$sqlname}personcat.pid
	LEFT JOIN {$sqlname}clientcat ON {$sqlname}entry.clid = {$sqlname}clientcat.clid
	LEFT JOIN {$sqlname}dogovor ON {$sqlname}entry.did = {$sqlname}dogovor.did
WHERE
	{$sqlname}entry.ide > 0
	".$sort."
	and {$sqlname}entry.identity = '$identity'
ORDER BY {$sqlname}entry.datum";

$result    = $db -> getAll( $query );
$all_lines = count( $result );
if ( !isset( $page ) or empty( $page ) or $page <= 0 )
	$page = 1;
else $page = (int)$page;
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query       .= " DESC LIMIT $lpos,$lines_per_page";
$result      = $db -> getAll( $query );
$count_pages = ceil( $all_lines / $lines_per_page );
if ( $count_pages == 0 )
	$count_pages = 1;

foreach ( $result as $da ) {

	$change = ($isadmin == 'on') ? '1' : '';

	$re           = $db -> getRow( "SELECT cid, tip as tip, datum, substring(des, 1, 100) as des FROM {$sqlname}history WHERE clid = '".$da['clid']."' and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ORDER BY cid DESC LIMIT 1" );
	$lastHistDesc = html2text( $re['des'] );
	$lastHistTip  = get_ticon( $re['tip'] );

	$estatus = ($da['status'] == 0) ? "1" : "";

	$list[] = [
		"ide"         => $da['ide'],
		"datum"       => get_sfdate( $da['datum'] ),
		"datum_do"    => get_sfdate( $da['datum_do'] ),
		"content"     => nl2br( $da['content'] ),
		"clid"        => $da['clid'],
		"client"      => $da['client'],
		"pid"         => $da['pid'],
		"person"      => $da['person'],
		"did"         => $da['did'],
		"deal"        => $da['deal'],
		"user"        => $da['user'],
		"autor"       => current_user( $da['autor'] ),
		"change"      => $change,
		"history"     => nl2br( $lastHistDesc ),
		"statusName"  => strtr( $da['status'], $status ),
		"statusColor" => strtr( $da['status'], $colors ),
		"status"      => $estatus
	];

}

$lists = [
	"list"    => $list,
	"page"    => (int)$page,
	"pageall" => (int)$count_pages
];

print json_encode_cyr( $lists );

exit();