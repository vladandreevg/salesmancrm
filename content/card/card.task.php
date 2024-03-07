<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.25          */
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

$thisfile = basename( __FILE__ );

global $userRights;

$task = [];
if ( $_COOKIE['task'] ) {
	$task = explode( ",", $_COOKIE['task'] );
}

//print "onlymy = $onlymy";

/**
 * Используется на вкладке Напоминания Рабочего стола (dt.tasklist.php), в Карточках с параметром "noclient"
 * Параметр "myotdel" выводит напоминания подчиненных
 * Применяется шаблон "tasklist.html"
 */

$clid     = (int)$_REQUEST['clid'];
$pid      = (int)$_REQUEST['pid'];
$did      = (int)$_REQUEST['did'];

//print_r($userSettings);

setcookie( "onlymy", $onlymy, time() + 365 * 86400, "/" );

$sort = '';

$res = $db -> getAll( "SELECT iduser, title FROM {$sqlname}user WHERE identity = '$identity' ORDER by title" );
foreach ( $res as $da ) {
	$users[ $da['iduser'] ] = $da['title'];
}

//2 недели следующего месяца для ограничения запроса (вкладка Дела)
$first = date( "Y-m-d", mktime( (int)date( 'H' ), (int)date( 'i' ), (int)date( 's' ), (int)date( 'm' ) + 1, 1, (int)date( 'Y' ) ) + (int)$tzone * 3600 );
$next  = date( "Y-m-d", mktime( (int)date( 'H' ), (int)date( 'i' ), (int)date( 's' ), (int)date( 'm' ) + 1, 5, (int)date( 'Y' ) ) + (int)$tzone * 3600 );

//текущий день недели
$dayofweek = date( 'w' );

$toffset = ($dayofweek < 5) ? 2 : 4;

//фильтр для карточек Клиента, Контакта, Сделки
if ( $did == 0 ) {

	if ( $pid > 0 ) {
		$sort .= " and FIND_IN_SET('$pid', REPLACE({$sqlname}tasks.pid, ';',',')) > 0";
	}
	if ( $clid > 0 ) {
		$sort .= " and {$sqlname}tasks.clid = '$clid'";
	}

}
else {
	$sort .= " and {$sqlname}tasks.did = '$did'";
}

//фильтр по куратору, чтобы не давать доступа к чужим напоминаниям
if($settingsMore['viewNotSelfTasks'] == 'yes' && $clid > 0){

	if( (int)$userSet['filterAllBy'] > 0 ){
		$sort .= " and {$sqlname}tasks.iduser IN (".yimplode(",", (array)get_people((int)$userSet['filterAllBy'], "yes")).")";
	}
	else {
		$sort .= " and {$sqlname}tasks.iduser IN (".yimplode(",", (array)get_people($iduser1, "yes")).")";
	}

}

$q = "
SELECT
	DISTINCT({$sqlname}tasks.tid) as tid,
	{$sqlname}tasks.maintid as maintid,
	{$sqlname}tasks.created as created,
	{$sqlname}tasks.datum as datum,
	{$sqlname}tasks.totime as totime,
	{$sqlname}tasks.tip as tip,
	{$sqlname}tasks.title as title,
	{$sqlname}tasks.speed as speed,
	{$sqlname}tasks.priority as priority,
	{$sqlname}tasks.autor as autor,
	{$sqlname}tasks.iduser as iduser,
	{$sqlname}tasks.clid as clid,
	{$sqlname}tasks.pid as pid,
	{$sqlname}tasks.did as did,
	{$sqlname}tasks.readonly as readonly,
	{$sqlname}tasks.day as day,
	{$sqlname}tasks.des as agenda,
	{$sqlname}activities.color as color
FROM {$sqlname}tasks
	LEFT JOIN {$sqlname}activities ON {$sqlname}activities.title = {$sqlname}tasks.tip
WHERE
	{$sqlname}tasks.tid > 0
	$sort and
	{$sqlname}tasks.active = 'yes' and
	{$sqlname}tasks.identity = '$identity'
-- GROUP BY {$sqlname}tasks.tid
ORDER BY {$sqlname}tasks.datum, {$sqlname}tasks.totime, FIELD({$sqlname}tasks.day, 'yes', null) DESC";

$res = $db -> getAll( $q );
foreach ( $res as $da ) {

	$useri  = [];
	$usera  = '';
	$uicon  = '';
	$autor  = '';
	$change = '';
	$person = '';

	$diff2 = difftimefull( $da['datum']." ".$da['totime'] );

	$diff  = diffDate2( (string)$da['datum'] );
	$hours = difftime( (string)$da['created'] );

	if ( $diff < 0 ) {

		$tcolor = 'redbg';
		$old    = 'old';

		$d    = explode( "-", $da['datum'] );
		$year = $d[0];

	}
	elseif ( $diff >= 0 ) {

		$tcolor = 'bluebg';
		if ( $diff2 < 0 ) {
			$scolor = 'red';
		}
		$old  = 'today';
		$year = '';

	}

	if ( $da['datum'] >= $first ) {

		$tcolor = 'bluebg-sub';
		$old    = 'future';

	}

	//$change = ($hours <= $hoursControlTime || $ac_import[7] == 'on') ? 'yes' : '';

	$do = ( $da['autor'] == 0 && $da['iduser'] == $iduser1 ) || $da['autor'] == $iduser1 || $da['iduser'] == $iduser1 ? 'yes' : '';

	$color = $da['color'];
	if ( $color == "" ) {
		$color = "transparent";
	}

	$address = stripos( texttosmall( $da['tip'] ), 'встреча' ) !== false ? getClientData( $da['clid'], 'address' ) : '';

	//$count = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}tasks WHERE maintid = '".$da['tid']."' and identity = '$identity'" );

	$result1 = $db -> getAll( "SELECT iduser FROM {$sqlname}tasks WHERE maintid = '".$da['tid']."' and identity = '$identity'" );
	$count   = count( $result1 );
	foreach ( $result1 as $dat ) {
		$useri[] = strtr( $dat['iduser'], $users );
	}

	if ( !empty( $useri ) ) {
		//$usera = '<i class="icon-user-1 fs-09 flh-10"></i> '.yimplode( ", ", $useri );
	}

	if ( $da['autor'] > 0 ) {

		if ( $da['autor'] == $iduser1 && $da['iduser'] != $iduser1 && $da['iduser'] > 0 ) {
			//$usera = '<i class="icon-user-1 fs-09 flh-10" title="'.$lang['face']['AuthorI'].'"></i> '.strtr( $da['iduser'], $users );
		}

	}


	//$uicon = '';

	$pidd = yexplode( ";", (string)$da['pid'] );

	$day = $old != 'old' ? get_dateru( $da['datum'] ) : format_date_rus_name( $da['datum'] );

	if ( $da['clid'] < 1 && $pidd[0] > 0 ) {
		$person = current_person( $pidd[0] );
	}

	if ( $da['did'] == 0 ) {
		$da['did'] = '';
	}

	if ( $da['autor'] + 0 == 0 ) {
		$da['autor'] = $da['iduser'];
	}

	if ( $da['autor'] > 0 ) {
		$autor = strtr( $da['autor'], $users );
	}

	if ( $da['readonly'] != 'yes' ) {
		$da['readonly'] = '';
	}

	if ( $da['autor'] == $da['iduser'] && $da['readonly'] == 'yes' ) {
		$da['readonly'] = '';
	}
	if ( $da['autor'] != $da['iduser'] && $da['readonly'] == 'yes' ) {
		$da['readonly'] = 'yes';
	}
	if ( $da['autor'] == $iduser1 && $da['readonly'] == 'yes' ) {
		$da['readonly'] = '';
	}

	//mod

	if ( ( $da['autor'] == 0 && $da['iduser'] == $iduser1 ) || $da['autor'] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$change = 'yes';
		}
		elseif ( $userRights['changetask'] ) {
			$change = 'yes';
		}

	}

	if ( $da['readonly'] == 'yes' ) {

		$change = '';

		if ( $da['autor'] == $da['iduser'] || $da['author'] == 0 || $da['autor'] == $iduser1 ) {

			$da['readonly'] = '';
			$change         = 'yes';

		}

	}

	if ( $userSettings['taskCheckBlock'] == 'yes' && $da['iduser'] != $iduser1 ) {

		$do = '';

	}

	$txt = mb_substr( untag( html2text( $da['agenda'] ) ), 0, 101, 'utf-8' );


	$tasks['data'][ $old ]['event'][ $da['datum'] ][] = [
		"tid"       => $da['tid'],
		"mainid"    => $da['maintid'],
		"datum"     => $da['datum'],
		"time"      => ($da['day'] != 'yes') ? getTime( (string)$da['totime'] ) : '',
		"title"     => $da['title'],
		"tip"       => $da['tip'],
		"icon"      => get_ticon( $da['tip'] ),
		"tcolor"    => $tcolor,
		"color"     => $color,
		"priority"  => get_priority( 'priority', $da['priority'] ),
		"speed"     => get_priority( 'speed', $da['speed'] ),
		"did"       => $da['did'],
		"deal"      => current_dogovor( $da['did'] ),
		"step"      => current_dogstep( $da['did'] ),
		"clid"      => $da['clid'],
		"client"    => current_client( $da['clid'] ),
		"pid"       => $pidd[0],
		"person"    => $person,
		"readonly"  => $da['readonly'],
		"day"       => ($da['day'] == 'yes') ? 1 : NULL,
		"agenda"    => ($old != 'old' || is_between( diffDate2( $da['datum'], current_datum() ), 0, 2 )) ? $txt : NULL,
		"do"        => $do,
		"change"    => $change,
		"old"       => $old,
		"address"   => $address,
		"uicon"     => $uicon,
		"scolor"    => $scolor,
		"date"      => $day,
		"year"      => $year,
		"autor"     => $autor,
		"user"      => ($noclient == 'yes') ? '' : current_user( $da['iduser'], 'yes' ),
		"users"     => $usera,
		"usercount" => !empty($useri ) ? count( $useri ) : NULL,
	];

	if ( $old == 'old' ) {

		$title = $lang['face']['NotDoTask'];
		$state = '';
		$icon  = "icon-attention";

	}
	elseif ( $old == 'today' ) {

		$title = $lang['face']['ActiveTask'];
		$state = '';
		$icon  = "icon-clock";

	}
	else {

		$title = $lang['face']['NextMounthTask'];
		$state = '';
		$icon  = "icon-calendar-1";

	}

	$tasks['data'][ $old ]['text'] = [
		"old"   => $old,
		"bg"    => $tcolor,
		"title" => $title,
		"state" => $state,
		"icon"  => $icon
	];

	//print_r($useri);

}

$events = [];

$i = 0;
foreach ( $tasks['data'] as $k => $val ) {

	$data  = [];
	$count = 0;

	$j = 0;
	foreach ( $val['event'] as $key => $value ) {

		$year = ($k == 'old') ? $value[0]['year'] : '';

		$event = [];

		foreach ($value as $row) {

			$event[] = [
				"tid"       => $row['tid'],
				"ismain"    => (int)$row['mainid'] > 0 ? 1 : NULL,
				"time"      => $row['time'],
				"title"     => $row['title'],
				"tip"       => $row['tip'],
				"icon"      => $row['icon'],
				"tcolor"    => $row['tcolor'],
				"color"     => $row['color'],
				"priority"  => $row['priority'],
				"speed"     => $row['speed'],
				"did"       => $row['did'],
				"deal"      => $row['deal'],
				"step"      => $row['step'],
				"clid"      => $row['clid'],
				"client"    => $row['client'],
				"pid"       => $row['pid'],
				"person"    => $row['person'],
				"readonly"  => $row['readonly'],
				"day"       => $row['day'],
				"agenda"    => $row['agenda'],
				"do"        => $row['do'],
				"change"    => $row['change'],
				"address"   => $row['address'],
				"uicon"     => $row['uicon'],
				"scolor"    => $row['scolor'],
				"autor"     => $row['autor'],
				"user"      => $row['user'],
				"users"     => $row['users'],
				"usercount" => $row['usercount']
			];

			$count++;

		}

		//массив событий по дням
		$data[ $j ] = [
			"date"  => $day = get_dateru( $key ).', <span class="gray2">'.$lang['face']['WeekNameFull'][ (date( 'w', date_to_unix( $key.' 00:00:00' ) ) - 1) ].'</span>',
			"year"  => $year,
			"bg"    => $val['text']['bg']."-sub",
			"event" => $event
		];
		$j++;

	}

	$events[ $i ] = [
		"title" => $val['text']['title'],
		"bg"    => $val['text']['bg'],
		"id"    => $val['text']['old'],
		"state" => $val['text']['state'],
		"icon"  => $val['text']['icon'],
		"count" => $count,
		"data"  => $data
	];
	$i++;

}

$data = [
	"events"   => $events,
	"noclient" => $noclient,
	"myotdel"  => $myotdel
];

print json_encode_cyr( $data );

//file_put_contents($rootpath."/cash/task.json", json_encode_cyr( $data ));