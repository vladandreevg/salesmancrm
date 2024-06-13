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
$noclient = $_REQUEST['noclient'];
$myotdel  = $_REQUEST['myotdel'];
$onlymy   = $_REQUEST['onlymy'];
$priority = $_REQUEST['priority'];
$speed    = $_REQUEST['speed'];

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

//Фильтр для вкладки Дела
if ( $noclient != 'yes' ) {

	$task = $_REQUEST['tsk'];

	$tsk = [];
	$res = $db -> getAll( "SELECT id, title FROM {$sqlname}activities WHERE identity = '$identity' ORDER by title" );
	foreach ( $res as $datay ) {
		if ( !empty( (array)$task ) ) {
			if ( in_array( $datay['id'], $task ) ) {
				$tsk[] = "'".$datay['title']."'";
			}
		}
		else {
			$tsk[] = "'".$datay['title']."'";
		}
	}

	$tsk = implode( ",", $tsk );
	if ( $tsk != '' ) {
		$sort .= " and tsk.tip IN(".$tsk.")";
	}

	if ( !$myotdel ) {

		if ( $onlymy != 'yes' ) {
			$sort .= " and (tsk.iduser = '$iduser1' or tsk.autor = '$iduser1')";
		}
		else {
			$sort .= " and tsk.iduser = '$iduser1'";
		}

	}
	else {
		$sort .= " and (tsk.iduser IN (".implode( ",", get_people( $iduser1, 'yes' ) ).") and tsk.iduser != '$iduser1')";
	}

	$sort .= " and tsk.datum < '$next'";

	//ограничим вывод по дате
	$sort .= " and tsk.datum BETWEEN DATE_ADD(CURDATE(), INTERVAL -14 DAY) and DATE_ADD(CURDATE(), INTERVAL $toffset DAY)";

	if (!empty($priority)) {
		$sort .= " and tsk.priority IN (".implode(',', (array)$priority).") ";
	}

	if (!empty($speed)) {
		$sort .= " and tsk.speed IN (".implode(',', (array)$speed).") ";
	}

}

//фильтр для карточек Клиента, Контакта, Сделки
if ( $noclient == 'yes' ) {

	if ( $did < 1 ) {

		if ( $pid > 0 ) {
			$sort .= " and FIND_IN_SET('$pid', REPLACE(tsk.pid, ';',',')) > 0";
		}
		if ( $clid > 0 ) {
			$sort .= " and tsk.clid = '$clid'";
		}

	}

	if ( $did > 0 ) {
		$sort .= " and tsk.did = '$did'";
	}

}

//print
$q = "
SELECT
	DISTINCT(tsk.tid) as tid,
	tsk.maintid as maintid,
	tsk.created as created,
	tsk.datum as datum,
	tsk.totime as totime,
	tsk.tip as tip,
	tsk.title as title,
	tsk.speed as speed,
	tsk.priority as priority,
	tsk.autor as autor,
	tsk.iduser as iduser,
	us.title as user,
	tsk.clid as clid,
	cc.title as client,
	tsk.pid as pid,
	tsk.did as did,
	deal.title as deal,
	step.title as step,
	tsk.readonly as readonly,
	tsk.day as day,
	tsk.des as agenda,
	as.color as color
FROM {$sqlname}tasks `tsk`
	LEFT JOIN {$sqlname}activities `as` ON as.title = tsk.tip
    LEFT JOIN {$sqlname}dogovor `deal` ON deal.did =  tsk.did
    LEFT JOIN {$sqlname}dogcategory `step` ON step.idcategory =  deal.idcategory
    LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid =  tsk.clid
    LEFT JOIN {$sqlname}user `us` ON us.iduser =  tsk.iduser
WHERE
	tsk.tid > 0
	$sort and
	tsk.active = 'yes' and
	tsk.identity = '$identity'
-- GROUP BY {$sqlname}tasks.tid
ORDER BY tsk.datum, tsk.totime, FIELD(tsk.day, 'yes', null) DESC";

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

	$do = $da['autor'] == 0 || $da['autor'] == $iduser1 || $da['iduser'] == $iduser1 ? 'yes' : '';

	$color = $da['color'];
	if ( $color == "" ) {
		$color = "transparent";
	}

	$address = stripos( texttosmall( $da['tip'] ), 'встреча' ) !== false ? getClientData( $da['clid'], 'address' ) : '';

	// тормозящий блок. поиск связанных напоминаний, при групповом напоминании
	/*
	$count = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}tasks WHERE maintid = '".$da['tid']."' and identity = '$identity'" );

	$result1 = $db -> getAll( "SELECT iduser FROM {$sqlname}tasks WHERE maintid = '".$da['tid']."' and identity = '$identity'" );
	$count   = count( $result1 );
	foreach ( $result1 as $dat ) {
		$useri[] = strtr( $dat['iduser'], $users );
	}

	if ( !empty( $useri ) ) {
		$usera = '<i class="icon-user-1 fs-09 flh-10"></i> '.yimplode( ", ", $useri );
	}
	*/

	if ( $da['autor'] > 0 ) {

		if ( $da['autor'] == $iduser1 && $da['iduser'] != $iduser1 && $da['iduser'] > 0 ) {
			$usera = '<i class="icon-user-1 fs-09 flh-10" title="'.$lang['face']['AuthorI'].'"></i> '.strtr( $da['iduser'], $users );
		}

	}


	//$uicon = '';

	$pidd = yexplode( ";", (string)$da['pid'] );

	$day = $old != 'old' ? get_dateru( $da['datum'] ) : format_date_rus_name( $da['datum'] );

	if ( (int)$da['clid'] < 1 && (int)$pidd[0] > 0 ) {
		$person = current_person( (int)$pidd[0] );
	}

	if ( (int)$da['did'] == 0 ) {
		$da['did'] = '';
	}

	if ( (int)$da['autor'] == 0 ) {
		$da['autor'] = (int)$da['iduser'];
	}

	if ( (int)$da['autor'] > 0 ) {
		$autor = strtr( $da['autor'], $users );
	}

	if ( $da['readonly'] != 'yes' ) {
		$da['readonly'] = '';
	}

	if ( (int)$da['autor'] == (int)$da['iduser'] && $da['readonly'] == 'yes' ) {
		$da['readonly'] = '';
	}
	if ( (int)$da['autor'] != (int)$da['iduser'] && $da['readonly'] == 'yes' ) {
		$da['readonly'] = 'yes';
	}
	if ( (int)$da['autor'] == $iduser1 && $da['readonly'] == 'yes' ) {
		$da['readonly'] = '';
	}

	//mod

	if ( (int)$da['autor'] == 0 || (int)$da['autor'] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$change = 'yes';
		}
		elseif ( $userRights['changetask'] ) {
			$change = 'yes';
		}

	}

	if ( $da['readonly'] == 'yes' ) {

		$change = '';

		if ( (int)$da['autor'] == (int)$da['iduser'] || (int)$da['author'] == 0 || (int)$da['autor'] == $iduser1 ) {

			$da['readonly'] = '';
			$change         = 'yes';

		}

	}

	if ( $userSettings['taskCheckBlock'] == 'yes' && (int)$da['iduser'] != $iduser1 ) {

		$do = '';

	}

	$txt = mb_substr( untag( html2text( $da['agenda'] ) ), 0, 101, 'utf-8' );


	$tasks['data'][ $old ]['event'][ $da['datum'] ][] = [
		"tid"       => (int)$da['tid'],
		"mainid"    => (int)$da['maintid'],
		"datum"     => $da['datum'],
		"time"      => ($da['day'] != 'yes') ? getTime( (string)$da['totime'] ) : '',
		"title"     => $da['title'],
		"tip"       => $da['tip'],
		"icon"      => get_ticon( $da['tip'] ),
		"tcolor"    => $tcolor,
		"color"     => $color,
		"priority"  => get_priority( 'priority', $da['priority'] ),
		"speed"     => get_priority( 'speed', $da['speed'] ),
		"did"       => (int)$da['did'],
		"deal"      => $da['deal'],
		"step"      => $da['step'],
		"clid"      => (int)$da['clid'],
		"client"    => $da['client'],
		"pid"       => (int)$pidd[0],
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
		"user"      => ($noclient == 'yes') ? '' : $da['user'],
		//"users"     => $usera,
		//"usercount" => !empty( $useri ) ? count( $useri ) : NULL,
		//"ll"        => $da['autor'].":".$iduser1.":".$ac_import[7]
	];

	if ( $old == 'old' ) {

		if ( $noclient != 'yes' ) {

			$title = $lang['face']['NotDoTask']." (за 30 дней)";
			$state = 'hidden';
			$icon  = "icon-attention";

		}
		else {

			$title = $lang['face']['NotDoTask'];
			$state = '';
			$icon  = "icon-attention";

		}

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
				"usercount" => $row['usercount'],
				//"ll"        => $row['ll'],
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