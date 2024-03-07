<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

global $userRights;

$page       = $_REQUEST['page'];
$idcategory = $_REQUEST['idcat'];
$word       = str_replace( " ", "%", $_REQUEST['word'] );
$sort       = '';
$tuda       = $_REQUEST['tuda'];
$ord        = $_REQUEST['ord'];
$ftype      = $_REQUEST['ftype'];

function getFCatalog($id, $level = 0, $res = []) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	global $res;

	$sort = !$id ? " and subid = '0'" : " and subid = '$id'";

	$re = $db -> query( "SELECT idcategory FROM {$sqlname}file_cat WHERE idcategory > 0 $sort and identity = '$identity' ORDER BY idcategory" );
	while ($da = $db -> fetch( $re )) {

		$res[] = (int)$da["idcategory"];

		if ( (int)$da['idcategory'] > 0 ) {

			$level++;
			getFCatalog( $da['idcategory'], $level );
			$level--;

		}

	}

	return $res;
}

if ( $_GET['action'] == "delete" ) {

	$fid        = $_GET['fid'];
	$idcategory = $_GET['idcategory'];

	$fname = $db -> getOne( "select fname from {$sqlname}file where fid='".$fid."' and identity = '$identity'" );

	@unlink( $rootpath."/files/".$fpath.$fname );

	try {
		$db -> query( "delete from {$sqlname}file where fid = '".$fid."' and identity = '$identity'" );
	}
	catch ( Exception $e ) {
		echo $e -> getMessage();
	}

}

//если у пользователя есть доступ в Бюджет, то покажем папку бюджет
if ( !$userRights['budjet'] ) {

	$folder_ex = $db -> getOne( "SELECT idcategory FROM {$sqlname}file_cat WHERE title='Бюджет' and identity = '$identity'" );

	if ( $folder_ex > 0 ) {
		$sort .= " and {$sqlname}file.folder != '".$folder_ex."'";
	}

}

if ( $word != '' ) {
	$sort .= " AND ({$sqlname}file.ftitle LIKE '%".$word."%' OR {$sqlname}file.ftag LIKE '%".$word."%')";
}

//Найдем id категорий с общими папками и создадим массив
$farray = $db -> getCol( "SELECT idcategory FROM {$sqlname}file_cat WHERE shared='yes' and identity = '$identity' ORDER by title" );

//Сформируем запрос по папкам и подпапкам
$folders = getFCatalog( $idcategory );

if ( $idcategory > 0 )
	$folders[] = $idcategory;
//else $folders[] = "null";

if ( !empty( $farray ) ) {
	$s = " OR {$sqlname}file.folder IN (".implode( ",", $farray ).") ";
}

$sort .= " and ({$sqlname}file.iduser IN (".implode( ",", get_people( $iduser1, 'yes' ) ).") $s)";

$ss = ($idcategory < 1) ? " OR {$sqlname}file.folder IS NULL" : "";

if ( !empty( $folders ) ) {
	$sort .= " and ({$sqlname}file.folder IN (".implode( ",", $folders ).") $ss)";
}
if ( $ftype == 'img' ) {
	$sort .= " and ({$sqlname}file.fname LIKE '%.png' OR {$sqlname}file.fname LIKE '%.jpg' OR {$sqlname}file.fname LIKE '%.gif' OR {$sqlname}file.fname LIKE '%.jpeg' OR {$sqlname}file.fname LIKE '%.tiff' OR {$sqlname}file.fname LIKE '%.bmp')";
}
elseif ( $ftype == 'doc' ) {
	$sort .= " and ({$sqlname}file.fname LIKE '%.txt' OR {$sqlname}file.fname LIKE '%.doc' OR {$sqlname}file.fname LIKE '%.docx' OR {$sqlname}file.fname LIKE '%.xls' OR {$sqlname}file.fname LIKE '%.xlsx' OR {$sqlname}file.fname LIKE '%.rtf' OR {$sqlname}file.fname LIKE '%.ppt' OR {$sqlname}file.fname LIKE '%.pptx')";
}
elseif ( $ftype == 'pdf' ) {
	$sort .= " and ({$sqlname}file.fname LIKE '%.pdf')";
}
elseif ( $ftype == 'zip' ) {
	$sort .= " and ({$sqlname}file.fname LIKE '%.zip' OR {$sqlname}file.fname LIKE '%.rar' OR {$sqlname}file.fname LIKE '%.tar' OR {$sqlname}file.fname LIKE '%.7z' OR {$sqlname}file.fname LIKE '%.gz')";
}

if ( $ord == '' ) {
	$ord = 'fname';
}
if ( $tuda == '' ) {
	$tuda = '';
}

//print_r($folders);
//exit();

$query = "
SELECT
	{$sqlname}file.fid as id,
	-- DATE_FORMAT({$sqlname}file.datum, '%d.%m.%y %H:%s') as datum,
	{$sqlname}file.pid as pid,
	{$sqlname}file.clid as clid,
	{$sqlname}file.did as did,
	{$sqlname}file.ftitle as title,
	{$sqlname}file.fname as file,
	{$sqlname}file.iduser as iduser,
	{$sqlname}clientcat.title as client,
	{$sqlname}personcat.person as person,
	{$sqlname}dogovor.title as deal,
	{$sqlname}user.title as user,
	{$sqlname}file_cat.title as folder
FROM {$sqlname}file
	LEFT JOIN {$sqlname}user ON {$sqlname}file.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}personcat ON {$sqlname}file.pid = {$sqlname}personcat.pid
	LEFT JOIN {$sqlname}clientcat ON {$sqlname}file.clid = {$sqlname}clientcat.clid
	LEFT JOIN {$sqlname}dogovor ON {$sqlname}file.did = {$sqlname}dogovor.did
	LEFT JOIN {$sqlname}file_cat ON {$sqlname}file.folder = {$sqlname}file_cat.idcategory
WHERE
	{$sqlname}file.fid > 0 $sort AND
	{$sqlname}file.identity = '$identity'
";

$lines_per_page = 100; //Стоимость записей на страницу
$result         = $db -> query( $query );
$all_lines      = $db -> affectedRows( $result );

$count_pages = ceil( $all_lines / $lines_per_page );

if ( $page > $count_pages ) {
	$page = 1;
}

if ( empty( $page ) || $page <= 0 ) {
	$page = 1;
}
else {
	$page = (int)$page;
}

$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query = "$query ORDER BY {$sqlname}file.$ord $tuda LIMIT $lpos,$lines_per_page";

$result = $db -> query( $query );

if ( $count_pages == 0 ) {
	$count_pages = 1;
}

while ($da = $db -> fetch( $result )) {

	$icon   = '';
	$size   = '';
	$change = '';
	$ddate  = '';

	$icon = get_icon2( $da['title'] );
	$size = num_format( filesize( $rootpath."/files/".$fpath.$da['file'] ) / 1024 );

	if ( get_accesse_other( (int)$da['iduser'] ) == 'yes' && $userRights['delete'] ) {
		$change = 'yes';
	}

	$dtime = filemtime( $rootpath."/files/".$fpath.$da['file'] );//current(explode(".", $da['file']));
	$ddate = date( 'H:i d.m.Y', $dtime );

	$isView = isViewable( $da['file'] ) ? '1' : '';

	$list[] = [
		"id"     => (int)$da['id'],
		"name"   => $da['file'],
		"icon"   => $icon,
		"title"  => $da['title'],
		"datum"  => str_replace( " ", "&nbsp;<br>", $ddate ),
		"size"   => $size,
		"clid"   => (int)$da['clid'],
		"client" => $da['client'],
		"pid"    => (int)$da['pid'],
		"person" => $da['person'],
		"did"    => (int)$da['did'],
		"deal"   => $da['deal'],
		"user"   => $da['user'],
		"change" => $change,
		"view"   => $isView,
		"folder" => $da['folder']
	];

}

$lists = [
	"list"    => $list,
	"page"    => $page,
	"pageall" => $count_pages,
	"ord"     => $ord,
	"desc"    => $tuda,
	"all"     => $all_lines
];

//print $query."<br>";
print json_encode_cyr( $lists );

exit();
