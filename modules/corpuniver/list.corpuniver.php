<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.х         */
/* ============================ */
/*   Developer: Ivan Drachyov   */

use Salesman\CorpUniver;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$page  = (int)$_REQUEST['page'];
$idcat = (int)$_REQUEST['idcat'];
$word  = str_replace(" ", "%", $_REQUEST['word']);

$mdcset      = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'corpuniver' and identity = '$identity'");
$mdcsettings = json_decode($mdcset['content'], true);

$isEditor = $isadmin == "on" || in_array($iduser1, $mdcsettings['Editor']);

$lines_per_page = "40"; //Количество записей на страницу

$sort = '';

if ($idcat > 0) {

	$catlist = CorpUniver::getCategories($idcat);
	$c = [];

	foreach ($catlist as $cat) {
		$c[] = $cat['id'];
	}

	if(!empty($c)) {

		$sort .= "({$sqlname}corpuniver_course.cat IN (".implode(",", $c).") OR {$sqlname}corpuniver_course.cat = '$idcat') AND";

	}
	else{

		$sort .= $sqlname."corpuniver_course.cat = '$idcat' AND";

	}

	/*
	$subid = $db -> getOne("select id from {$sqlname}corpuniver_course_cat where subid = '$idcat' and identity = '$identity'");

	if ($subid > 0) {
		$sort .= "({$sqlname}corpuniver_course.cat = '$idcat' OR {$sqlname}corpuniver_course.cat = '$subid') AND ";
	}
	else {
		$sort .= $sqlname."corpuniver_course.cat = '$idcat' AND";
	}
	*/

}

if ($idcat == 0 && $word != '') {
	$sort .= $sqlname."corpuniver_course.name LIKE '%$word%' AND";
}

if ($word != '') {
	$sort .= $sqlname."corpuniver_course.name LIKE '%$word%' AND";
}

$query = "
	SELECT
		{$sqlname}corpuniver_course.id as id,
		{$sqlname}corpuniver_course.date_create as date_create,
		{$sqlname}corpuniver_course.date_edit as date_edit,
		{$sqlname}corpuniver_course.cat as idcat,
		{$sqlname}corpuniver_course.author as author,
		{$sqlname}corpuniver_course.name as name,
		{$sqlname}corpuniver_course.des as des,
		{$sqlname}corpuniver_course.moderator as moderator,
		{$sqlname}corpuniver_course_cat.title as category,
		{$sqlname}corpuniver_coursebyusers.datum as date_start,
		{$sqlname}user.title as user
	FROM {$sqlname}corpuniver_course
		LEFT JOIN {$sqlname}user ON {$sqlname}corpuniver_course.author = {$sqlname}user.iduser
		LEFT JOIN {$sqlname}corpuniver_course_cat ON {$sqlname}corpuniver_course.cat = {$sqlname}corpuniver_course_cat.id
		LEFT JOIN {$sqlname}corpuniver_coursebyusers ON {$sqlname}corpuniver_coursebyusers.idcourse = {$sqlname}corpuniver_course.id
	WHERE
		{$sqlname}corpuniver_course.id > 0 AND
		$sort
		({$sqlname}corpuniver_coursebyusers.idcourse > 0 OR {$sqlname}corpuniver_coursebyusers.idcourse IS NULL) AND
		({$sqlname}corpuniver_coursebyusers.idlecture = 0 OR {$sqlname}corpuniver_coursebyusers.idlecture IS NULL) AND
		({$sqlname}corpuniver_coursebyusers.idmaterial = 0 OR {$sqlname}corpuniver_coursebyusers.idmaterial IS NULL) AND
		({$sqlname}corpuniver_coursebyusers.idtask = 0 OR {$sqlname}corpuniver_coursebyusers.idmaterial IS NULL) AND
		{$sqlname}corpuniver_course.identity = '$identity'
	GROUP BY {$sqlname}corpuniver_course.id
	ORDER BY ".(!$isEditor ? $sqlname."corpuniver_coursebyusers.datum DESC" : $sqlname."corpuniver_course.date_edit DESC")."
";

$result    = $db -> query($query);
$all_lines = $db -> numRows($result);

if (!isset($page) or empty($page) or $page <= 0) {
	$page = 1;
}
else {
	$page = (int)$page;
}
$page_for_query = $page - 1;
$lpos           = $page_for_query * $lines_per_page;

$query .= " LIMIT $lpos,$lines_per_page";
//print $query;
$result      = $db -> getAll($query);
$count_pages = ceil($all_lines / $lines_per_page);
if ($count_pages == 0) {
	$count_pages = 1;
}

foreach ($result as $da) {

	$show = $trash = '';

	if ($da['category'] == '') {
		$da['category'] = 'Общее';
	}

	$change = ($da['author'] == $iduser1 || $isadmin == 'on' || (in_array($iduser1, $mdcsettings['Editor']) && !in_array($iduser1, $mdcsettings['EditorMy']))) ? 'yes' : '';

	$content = mb_substr(untag(htmlspecialchars_decode($da['des'])), 0, 101, 'utf-8');

	$progress = CorpUniver::progressCource($da['id']);

	$list[] = [
		"id"          => $da['id'],
		"date_create" => get_sfdate($da['date_create']),
		"date_edit"   => get_sfdate($da['date_edit']),
		"name"        => $da['name'],
		"content"     => $content,
		"category"    => $da['category'],
		"change"      => $change,
		"author"      => $da['user'],
		"progress"    => round($progress['progressTotal'] * 100, 0)
	];

}

$lists = [
	"list"    => $list,
	"page"    => $page,
	"pageall" => $count_pages
];

//print $query."\n";
//print_r($lists);

print json_encode_cyr($lists);

exit();