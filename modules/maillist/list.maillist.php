<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$page = $_REQUEST[ 'page' ];
$tip  = $_REQUEST[ 'tip' ];
$word = str_replace( " ", "%", $_REQUEST[ 'word' ] );
$sort = '';

if ( $word != "" ) $sort .= " and ((title LIKE '%".$word."%') or (descr LIKE '%".$word."%'))";

$query = "SELECT * FROM ".$sqlname."mail WHERE mid > 0 ".$sort." and identity = '$identity'";

$lines_per_page = $num_client; //Стоимость записей на страницу

$result    = $db -> query( $query );
$all_lines = $db -> numRows( $result );
if ( !isset( $page ) or empty( $page ) or $page <= 0 ) $page = 1;
else $page = (int)$page;
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query = $query." ORDER BY datum DESC LIMIT $lpos,$lines_per_page";

$result      = $db -> query( $query );
$count_pages = ceil( $all_lines / $lines_per_page );
if ( $count_pages == 0 ) $count_pages = 1;

while ( $da = $db -> fetch( $result ) ) {

	$change = '';
	$do     = '';
	$descr  = '';

	if ( get_accesse_other( (int)$da[ 'iduser' ] ) == 'yes' ) $change = 'yes';
	if ( $da[ 'do' ] == '' ) $do = '1';

	$descr = html2text( $da[ 'descr' ] );

	$list[] = [
		"id"      => $da[ 'mid' ],
		"title"   => $da[ 'title' ],
		"datum"   => get_sfdate( $da[ 'datum' ] ),
		"content" => $descr,
		"do"      => $do,
		"change"  => $change
	];

}

$lists = [
	"list"    => $list,
	"page"    => $page,
	"pageall" => $count_pages
];

print json_encode_cyr( $lists );

exit();
?>