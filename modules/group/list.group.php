<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$page = (int)$_REQUEST['page'];
$ord  = $_REQUEST['ord'] ?? "datum";
$des  = $_REQUEST['des'];
$tar  = $_REQUEST['tar'];
$gid  = (int)$_REQUEST['gid'];
$tuda  = $_REQUEST['tuda'] ?? "DESC";

$sort = '';

$word = clean( str_replace( " ", "", $_REQUEST['word'] ) );

if ( $tar == 'group' ) {

	$query = "SELECT * FROM ".$sqlname."group where id != 0 and identity = '$identity'";

	$lines_per_page = $num_client; //Стоимость записей на страницу

	$result    = $db -> query( $query );
	$all_lines = $db -> affectedRows();

	$page = empty( $page ) || $page <= 0 ? 1 : (int)$page;

	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$query .= " ORDER BY $ord $tuda LIMIT $lpos,$lines_per_page";

	$result      = $db -> query( $query );
	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages == 0 ) {
		$count_pages = 1;
	}

	//Определим сервисы, которые позволяют редактировать списки
	$approve_list  = [
		'',
		'Unisender'
	];
	$approve_list2 = [
		'',
		'Unisender',
		'JastClick'
	];

	while ($da = $db -> fetch( $result )) {

		$change  = NULL;
		$approve = 1;

		$count = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."grouplist where gid='".$da['id']."' and identity = '$identity'" );
		//$all   = 'Число записей: <b title="Всего в группе">'.$count.'</b>';
		$all = '<b title="Всего в группе">'.$count.'</b>';

		$counta      = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."grouplist where gid='".$da['id']."' and person_id > 0 and identity = '$identity'" );
		$all_approve = ' / <b title="Подтвержденных">'.$counta.'</b>';

		$change = ($isadmin == 'on') ? 1 : NULL;

		//$approve = (in_array( $da['service'], $approve_list )) ? 1 : NULL;

		$list[] = [
			"id"           => (int)$da['id'],
			"datum"        => get_sfdate( $da['datum'] ),
			"name"         => $da['name'],
			"service"      => $da['service'],
			"idservice"    => $da['idservice'],
			"count"        => $all,
			"countapprove" => $all_approve,
			"approve"      => $approve,
			"change"       => $change
		];

	}

	$lists = [
		"list"    => $list,
		"page"    => $page,
		"pageall" => $count_pages
	];

	print json_encode_cyr( $lists );

	exit();

}
if ( $tar == 'glist' ) {

	$list = [];

	if ( $gid > 0 ) {
		$sort .= " and gid = '".$gid."'";
	}
	if ( $word ) {
		$sort .= "and (user_name LIKE '%".$word."%' or user_email LIKE '%".$word."%')";
	}

	$query = "SELECT * FROM ".$sqlname."grouplist where id != 0 ".$sort." and identity = '$identity' ORDER BY ".$ord." ".$tuda;

	$lines_per_page = $num_client; //Стоимость записей на страницу

	$result    = $db -> query( $query );
	$all_lines = $db -> affectedRows( $result );
	if ( empty( $page ) || $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$query .= " LIMIT $lpos,$lines_per_page";

	$result      = $db -> query( $query );
	$count_pages = ceil( $all_lines / $lines_per_page );
	if ( $count_pages == 0 ) {
		$count_pages = 1;
	}

	while ($da = $db -> fetch( $result )) {

		$resultg      = $db -> getRow( "SELECT * FROM ".$sqlname."group where id='".$da['gid']."' and identity = '$identity'" );
		$groupName    = $resultg["name"];
		$groupService = $resultg["service"];

		$tel = [];

		if ( $da['pid'] > 0 ) {

			$tel = getPersonWPhone( $da['pid'], false );

		}
		elseif ( $da['clid'] > 0 ) {

			$tel = getClientWPhone( $da['clid'], false );

		}

		$list[] = [
			"id"           => $da['id'],
			"gid"          => $da['gid'],
			"datum"        => get_sfdate( $da['datum'] ),
			"name"         => $da['user_name'],
			"email"        => $da['user_email'],
			"groupName"    => $groupName,
			"groupService" => $groupService,
			"tags"         => $da['tags'],
			"clid"         => $da['clid'],
			"client"       => current_client( $da['clid'] ),
			"pid"          => $da['pid'],
			"person"       => current_person( $da['pid'] ),
			"service"      => $da['service'],
			"phone"        => $tel[0]
		];

	}


	$ss   = ($tuda == 'desc') ? '<i class="icon icon-angle-up"></i>' : '<i class="icon icon-angle-down"></i>';
	$ord1 = ($ord == 'datum') ? $ss : '';
	$ord2 = ($ord == 'user_name') ? $ss : '';

	$lists = [
		"list"    => $list,
		"page"    => $page,
		"pageall" => (int)$count_pages,
		"ord1"    => $ord1,
		"ord2"    => $ord2,
		"tuda"    => $tuda
	];

	print json_encode_cyr( $lists );

	exit();

}