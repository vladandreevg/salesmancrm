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
$word = str_replace( " ", "%", $_REQUEST[ 'word' ] );
$sort = '';

if ( $word != "" ) $sort .= " and ((name_tpl LIKE '%".$word."%') or (content_tpl LIKE '%".$word."%'))";

$query = "SELECT * FROM ".$sqlname."mail_tpl where tpl_id > 0 ".$sort." and identity = '$identity'";

$lines_per_page = $num_client; //Стоимость записей на страницу

$result    = $db -> query( $query );
$all_lines = $db -> numRows( $result );

if ( !isset( $page ) or empty( $page ) or $page <= 0 ) $page = 1;
else $page = (int)$page;
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query = $query." ORDER BY name_tpl DESC LIMIT $lpos,$lines_per_page";

$result      = $db -> query( $query );
$count_pages = ceil( $all_lines / $lines_per_page );
if ( $count_pages == 0 ) $count_pages = 1;

while ( $da = $db -> fetch( $result ) ) {

	$content = '';

	$content = html2text( $da[ 'content_tpl' ] );
	$content = mb_substr( $content, 0, 101, 'utf-8' );

	$list[] = [
		"id"      => $da[ 'tpl_id' ],
		"title"   => $da[ 'name_tpl' ],
		"content" => $content
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