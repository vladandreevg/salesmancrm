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

//set_time_limit(0);

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

/**
 * Используется в разделе "Дела"
 * Шаблон "tpl.tasks.html"
 */


$y = ((int)$_REQUEST['sy'] != '') ? $_REQUEST['sy'] : date( "Y", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
$m = ((int)$_REQUEST['sm'] != '') ? $_REQUEST['sm'] : date( "m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );

$action   = $_REQUEST['action'];
$tar      = $_REQUEST['tar'];
$iduser   = $_REQUEST['iduser'];
$task     = $_REQUEST['tsk'];
$priority = $_REQUEST['priority'];
$speed    = $_REQUEST['speed'];

setcookie( "task_list", json_encode_cyr( $_REQUEST ), time() + 365 * 86400, "/" );

$sort = '';

$usersa = $db -> getIndCol( "iduser", "SELECT title,iduser FROM {$sqlname}user WHERE identity = '$identity' ORDER by title" );

$y = (strlen( $y ) < 4) ? $y + 2000 : $y;
$m = str_pad( $m, 2, '0', STR_PAD_LEFT );

if ( !empty( $task ) ) {
	$sort .= "tsk.tip IN (".yimplode(",", $task, "'").") and ";
}

if ( isset( $_REQUEST['to_deal'] ) ) {
	$sort .= "tsk.did != 0 and ";
}

$showuser = 'yes';

if ( $tar == 'my' || $tar == '' ) {

	$sort     .= "(tsk.iduser = '$iduser1') and";
	$showuser = '';

	if ( isset( $_REQUEST['to_me'] ) )
		$sort .= "(tsk.autor != 0 AND tsk.autor != '$iduser1' AND tsk.autor IS NOT NULL) and ";

}
elseif ( $tar == 'other' ) {

	$sort .= " (tsk.autor = '$iduser1' and tsk.iduser != '$iduser1') and";
	if ( $iduser > 0 && $iduser != $iduser1 )
		$sort .= " tsk.iduser = '$iduser' and";

}
elseif ( $tar == 'all' ) {

	$sort .= "(tsk.iduser IN (".implode( ',', get_people( $iduser1, 'yes' ) ).") and tsk.iduser != '$iduser1') and ";
	if ( $iduser > 0 && $iduser != $iduser1 )
		$sort .= " tsk.iduser = '$iduser' and ";

}

if ( !empty( $priority ) ) {

	$sort .= " tsk.priority IN (".implode( ',', $priority ).") and ";

}

if ( !empty( $speed ) ) {

	$sort .= " tsk.speed IN (".implode( ',', $speed ).") and ";

}

//print $sort;

$list = [];

//представление Список дел

##Просроченные дела

$tm = $tzone;
$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), (int)date( 'm' ), (int)date( 'd' ), (int)date( 'Y' ) ) + $tm * 3600 );

$cquery = "
SELECT 
	COUNT(tsk.tid)
FROM {$sqlname}tasks `tsk`
WHERE 
	tsk.tid > 0 and
	date_format(tsk.datum, '%Y-%m') < '".date( 'Y' )."-".date( 'm' )."' and 
	tsk.active = 'yes' and
	$sort
	tsk.identity = '$identity'
ORDER BY tsk.cid, tsk.datum, tsk.totime
";
$cc     = $db -> getOne( $cquery );

$query   = "
SELECT 
	DISTINCT (tsk.tid),
	tsk.created as created,
	tsk.datum,
	tsk.totime,
	tsk.tip,
	tsk.clid,
	tsk.pid,
	tsk.did,
	tsk.title,
	tsk.des,
	tsk.iduser,
	tsk.autor,
	tsk.priority,
	tsk.readonly,
	tsk.des as agenda,
	tsk.day as day,
	tsk.speed,
	cc.title as client,
	dd.title as deal
FROM {$sqlname}tasks `tsk`
	LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = tsk.clid
	LEFT JOIN {$sqlname}dogovor `dd` ON dd.did = tsk.did
WHERE 
	tsk.tid > 0 and
	date_format(tsk.datum, '%Y-%m') < '".date( 'Y' )."-".date( 'm' )."' and 
	tsk.active = 'yes' and
	$sort
	tsk.identity = '$identity'
ORDER BY tsk.datum, FIELD(tsk.day, 'yes', null), tsk.totime
".($cc > 50 ? "DESC LIMIT 50" : "DESC")."
";
$resultt = $db -> query( $query );

$list[0]['id']    = 'notdo';
$list[0]['count'] = $cc;
$list[0]['title'] = 'Просрочено'.($cc > 50 ? '<i class="icon-info-circled" title="Показано последние 50 выполненных"></i>' : '');
$list[0]['icon']  = 'icon-attention';
$list[0]['bg']    = 'redbg';
$list[0]['state'] = 'hidden';

while ($data = $db -> fetch( $resultt )) {

	$rezultat = '';
	$do       = '';
	$change   = '';
	$iconuser = '';
	$users    = '';

	$hours = difftime( $data['created'] );

	$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title='".$data['tip']."' and identity = '$identity'" );

	$pid = yexplode( ";", (string)$data['pid'], 0 );

	if ( $showuser == 'yes' ) {
		$users = '<span class="em fs-09 gray2">Отв.: '.$usersa[$data['iduser']].'</span>';
	}

	if ( $data['autor'] != $data['iduser'] && $data['readonly'] == 'yes' ) {
		$data['readonly'] = 'yes';
	}

	if ( $data['autor'] == $data['iduser'] || $data['autor'] == 0 || $data['autor'] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$change = 'yes';
		}
		elseif ( $userRights['changetask'] ) {
			$change = 'yes';
		}

		$data['readonly'] = '';

	}

	if ( $data['autor'] > 0 && $data['iduser'] != $iduser1 ) {
		$iconuser = '<i class="icon-user-1 blue" title="Назначено мной"></i>';
	}
	//elseif ($data['autor'] > 0 && $data['iduser'] == $iduser1 && $data['autor'] != $iduser1) $iconuser = '<i class="icon-user-1 green" title="Назначено мне"></i>';

	$list[0]['tasks'][] = [
		"tid"      => $data['tid'],
		"date"     => ($data['day'] != 'yes' ? "<div class='fs-12 mfh-10 red Bold'>".getTime( (string)$data['totime'] )."</div>" : "").format_date_rus( $data['datum'] ),
		"priority" => get_priority( 'priority', $data['priority'] ).get_priority( 'speed', $data['speed'] ),
		"title"    => $data['title'],
		"user"     => $usersa[ $data['iduser'] ],
		"autor"    => ($data['autor'] > 0 && $data['autor'] != $data['iduser']) ? strtr( $data['autor'], $usersa ) : NULL,
		"icon"     => get_ticon( (string)$data['tip'] ),
		"tip"      => $data['tip'],
		"color"    => ($color == "") ? "#bbb" : $color,
		"readonly" => ($data['readonly'] == "yes") ? "yes" : false,
		"day"      => ($data['day'] == 'yes') ? 1 : NULL,
		"change"   => ($change == "yes") ? true : NULL,
		"do"       => NULL,
		"doit"     => $userSettings['taskCheckBlock'] == 'yes' && $data['iduser'] != $iduser1 ? NULL : 1,
		"did"      => ((int)$data['did'] > 0) ? $data['did'] : NULL,
		"deal"     => ((int)$data['did'] > 0) ? $data['deal'] : NULL,
		"clid"     => ((int)$data['clid'] > 0) ? $data['clid'] : NULL,
		"client"   => ((int)$data['clid'] > 0) ? $data['client'] : NULL,
		"pid"      => ((int)$data['clid'] < 1 && $pid > 0) ? $pid : NULL,
		"person"   => ((int)$data['clid'] < 1 && $pid > 0) ? current_person( $pid ) : NULL,
		"rezult"   => $rezultat,
		"users"    => $users,
		"agenda"   => NULL,
		//nl2br($data['agenda']),
		"iconuser" => $iconuser
	];

}

##Выполненные дела

$tm = $tzone;
$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, date( 'd' ), $y ) + $tm * 3600 );

$cquery = "
SELECT 
	COUNT(tsk.tid)
FROM {$sqlname}tasks `tsk`
WHERE 
	tsk.tid > 0 and
	(
		(
			tsk.cid > 0 and 
			(SELECT COUNT(cid) FROM {$sqlname}history WHERE cid = tsk.cid and date_format(datum, '%Y-%m')='$y-$m') > 0
		)
	) and 
	tsk.active = 'no' and
	$sort
	tsk.identity = '$identity'
ORDER BY tsk.cid, tsk.datum, tsk.totime
";
$cc     = $db -> getOne( $cquery );

$query = "
SELECT 
	DISTINCT (tsk.tid),
	tsk.created as created,
	tsk.datum,
	tsk.totime,
	tsk.tip,
	tsk.clid,
	tsk.pid,
	tsk.did,
	tsk.cid,
	tsk.title,
	tsk.des,
	tsk.iduser,
	tsk.active,
	tsk.autor,
	tsk.priority,
	tsk.speed,
	tsk.status,
	tsk.day,
	tsk.readonly,
	cc.title as client,
	dd.title as deal
FROM {$sqlname}tasks `tsk`
	LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = tsk.clid
	LEFT JOIN {$sqlname}dogovor `dd` ON dd.did = tsk.did
WHERE 
	tsk.tid > 0 and
	(
		-- date_format({$sqlname}tasks.datum, '%Y-%m') = '$y-$m' or 
		(
			tsk.cid > 0 and 
			(SELECT COUNT(cid) FROM {$sqlname}history WHERE cid = tsk.cid and date_format(datum, '%Y-%m')='$y-$m') > 0
		)
	) and 
	tsk.active = 'no' and
	$sort
	tsk.identity = '$identity'
ORDER BY tsk.cid, tsk.datum DESC, tsk.totime DESC
".($cc > 50 ? "LIMIT 50" : "")."
";

$resultt = $db -> query( $query );

$list[1]['id']    = 'tododo';
$list[1]['count'] = $cc;
$list[1]['title'] = 'Выполнено (в текущем месяце)'.($cc > 50 ? '<i class="icon-info-circled" title="Показано последние 50 выполненных"></i>' : '');
$list[1]['icon']  = 'icon-ok';
$list[1]['bg']    = 'greenbg';
$list[1]['state'] = 'hidden';

while ($data = $db -> fetch( $resultt )) {

	$rezultat = '';
	$do       = '';
	$change   = '';
	$users    = '';

	$hours = difftime( (string)$data['created'] );

	$change = ($hours <= $hoursControlTime || $userRights['changetask']) ? 'yes' : '';

	if ( $data['autor'] == 0 || $data['autor'] == $iduser1 || $data['iduser'] == $iduser1 ) {
		$do = 'yes';
	}
	elseif ( $userRights['changetask'] ) {
		$change = '';
	}

	$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title='".$data['tip']."' and identity = '$identity'" );

	$pid = yexplode( ";", (string)$data['pid'], 0 );

	if ( $data['cid'] > 0 ) {

		$hist = $db -> getRow( "SELECT * FROM {$sqlname}history WHERE cid='".$data['cid']."' and identity = '$identity'" );

		$txt = mb_substr( untag( html2text( $hist['des'] ) ), 0, 101, 'utf-8' );

		$rezultat = '<span class="em gray2 fs-07 mb5">Результат:</span><br><div class="ellipsis1 fs-09" title="'.get_sfdate( $hist['datum'] ).': '.$txt.'">'.get_sdate( $hist['datum'] ).' <span class="blue">'.$txt.'</span></b></div>';

	}

	if ( $showuser == 'yes' ) {
		$users = '<div class="em fs-09 gray2">Отв.: '.$usersa[$data['iduser']].'</div>';
	}

	if ( $data['autor'] != $data['iduser'] && $data['readonly'] == 'yes' ) {
		$data['readonly'] = 'yes';
	}

	if ( $data['autor'] == $data['iduser'] || $data['autor'] == 0 || $data['autor'] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$change = 'yes';
		}
		elseif ( $userRights['changetask'] ) {
			$change = 'yes';
		}

		$data['readonly'] = '';

	}

	$list[1]['tasks'][] = [
		"tid"           => $data['tid'],
		"date"          => "<div class=\"gray2 fs-09\"><i class=\"icon-clock\"></i>".getTime( (string)$data['totime'] )."&nbsp;".format_date_rus( $data['datum'] )."</div>",
		"histdate"      => get_sdate( $hist['datum'] ),
		"priority"      => get_priority( 'priority', $data['priority'] ).get_priority( 'speed', $data['speed'] ),
		"title"         => $data['title'],
		"user"          => $usersa[ $data['iduser'] ],
		"autor"         => ($data['autor'] > 0 && $data['autor'] != $data['iduser']) ? strtr( $data['autor'], $usersa ) : NULL,
		"icon"          => get_ticon( $data['tip'] ),
		"tip"           => $data['tip'],
		"color"         => ($color == "") ? "transparent" : $color,
		"did"           => ((int)$data['did'] > 0) ? $data['did'] : NULL,
		"deal"          => ((int)$data['did'] > 0) ? $data['deal'] : NULL,
		"clid"          => ((int)$data['clid'] > 0) ? $data['clid'] : NULL,
		"client"        => ((int)$data['clid'] > 0) ? $data['client'] : NULL,
		"pid"           => ((int)$data['clid'] < 1 && $pid > 0) ? $pid : NULL,
		"person"        => ((int)$data['clid'] < 1 && $pid > 0) ? current_person( $pid ) : NULL,
		"day"           => ($data['day'] == 'yes') ? 1 : NULL,
		"status"        => ($data['status'] == "2") ? 1 : NULL,
		"statusTooltip" => ($data['status'] == 1) ? "Успешно" : "Не успешно",
		"change"        => NULL,
		"rezult"        => nl2br( $rezultat ),
		"users"         => $users,
		"do"            => true,
		"doit"          => $userSettings['taskCheckBlock'] == 'yes' && $da['iduser'] != $iduser1 ? NULL : 1
	];

}

#Актуальные дела

$days = [];

$cquery = "
	SELECT 
		COUNT(tsk.tid)
	FROM {$sqlname}tasks `tsk`
	WHERE 
		tsk.tid > 0 and
		DATE_FORMAT(tsk.datum, '%Y-%m') = '$y-$m' and 
		tsk.active = 'yes' and
		$sort
		tsk.identity = '$identity'
	ORDER BY tsk.datum, FIELD(tsk.day, 'yes', null) DESC, tsk.totime
";
$cc     = $db -> getOne( $cquery );

$query = "
	SELECT 
		DISTINCT (tsk.tid),
		tsk.created,
		tsk.datum,
		tsk.totime,
		tsk.tip,
		tsk.clid,
		tsk.pid,
		tsk.did,
		tsk.cid,
		tsk.title,
		tsk.des,
		tsk.iduser,
		tsk.autor,
		tsk.priority,
		tsk.readonly,
		tsk.day,
		tsk.des as agenda,
		tsk.speed,
		cc.title as client,
	dd.title as deal
	FROM {$sqlname}tasks `tsk`
		LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = tsk.clid
		LEFT JOIN {$sqlname}dogovor `dd` ON dd.did = tsk.did
	WHERE 
		tsk.tid > 0 and
		DATE_FORMAT(tsk.datum, '%Y-%m') = '$y-$m' and 
		tsk.active = 'yes' and
		$sort
		".($cc > 50 ? "CONCAT(tsk.datum, ' ', tsk.totime) <= (NOW() + INTERVAL 5 DAY) AND" : "")."
		tsk.identity = '$identity'
	ORDER BY tsk.datum, FIELD(tsk.day, 'yes', null) DESC, tsk.totime
	".($cc > 50 ? "LIMIT 50" : "")."
";

$resultt = $db -> query( $query );

$list[2]['id']    = 'todaydo';
$list[2]['count'] = $cc;
$list[2]['title'] = 'Активно (текущий месяц)'.($cc > 50 ? '<i class="icon-info-circled" title="Показано за 5 дней максимум 50 записей"></i>' : '');
$list[2]['icon']  = 'icon-clock';
$list[2]['bg']    = 'bluebg';
$list[2]['state'] = '';

while ($data = $db -> fetch( $resultt )) {

	$rezultat = '';
	$do       = '';
	$change   = '';
	$iconuser = '';
	$useri    = [];
	$usera    = '';

	$pid = yexplode( ";", (string)$data['pid'], 0 );

	$hours = difftime( $data['created'] );

	$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title='".$data['tip']."' and identity = '$identity'" );

	$users = ($showuser == 'yes') ? '<span class="smalltxt gray2">Отв.: <b>'.$usersa[ $data['iduser'] ].'</b></span>' : "";

	// отключено: грузит систему
	/*
	$result1   = $db -> getAll( "SELECT iduser FROM {$sqlname}tasks WHERE maintid = '".$data['tid']."' and identity = '$identity'" );
	$usercount = count( $result1 );
	foreach ( $result1 as $dat ) {
		$useri[] = strtr( $dat['iduser'], $usersa );
	}
	*/

	if ( $usercount > 0 ) {
		$usera = '<i class="icon-user-1 fs-09 flh-10"></i> '.yimplode( ", ", $useri );
	}

	if ( $data['autor'] != $data['iduser'] && $data['readonly'] == 'yes' ) {
		$data['readonly'] = 'yes';
	}

	if ( $data['autor'] == $data['iduser'] || $data['autor'] == 0 || $data['autor'] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$change = 'yes';
		}
		elseif ( $userRights['changetask'] ) {
			$change = 'yes';
		}

		$data['readonly'] = '';

	}

	$diff = difftimefull( $data['datum']." ".$data['totime'] );

	if ( $diff > 0 && $diff <= 0.5 ) {
		$icn = '<i class="icon-ok green" title="Порядок"></i>';
	}
	elseif ( $diff < 0 ) {
		$icn = '<i class="icon-attention red" title="! Не выполнено"></i>';
	}
	elseif ( $diff > 0.5 ) {
		$icn = '<i class="icon-clock blue" title="Порядок"></i>';
	}

	if ( $data['autor'] > 0 && $data['iduser'] != $iduser1 ) {
		$iconuser = '<i class="icon-user-1 blue" title="Назначено мной"></i>';
	}

	$txt = mb_substr( untag( html2text( $data['agenda'] ) ), 0, 101, 'utf-8' );

	$days[ $data['datum'] ][] = [
		"tid"       => $data['tid'],
		"date"      => ($data['day'] != 'yes' ? "<div class=\"fs-12\"><span class=\"Bold\">".getTime( (string)$data['totime'] )." ".$icn."</span></div>" : $icn),
		"priority"  => get_priority( 'priority', $data['priority'] ).get_priority( 'speed', $data['speed'] ),
		"title"     => $data['title'],
		"user"      => $usersa[ $data['iduser'] ],
		"autor"     => ($data['autor'] > 0 && $data['autor'] != $data['iduser']) ? strtr( $data['autor'], $usersa ) : NULL,
		"icon"      => get_ticon( $data['tip'] ),
		"tip"       => $data['tip'],
		"color"     => ($color == "") ? "gray" : $color,
		"did"       => ($data['did'] > 0) ? $data['did'] : NULL,
		"deal"      => ($data['did'] > 0) ? $data['deal'] : NULL,
		"clid"      => ($data['clid'] > 0) ? $data['clid'] : NULL,
		"client"    => ($data['clid'] > 0) ? $data['client'] : NULL,
		"pid"       => ($data['clid'] < 1 && $pid > 0) ? $pid : NULL,
		"person"    => ($data['clid'] < 1 && $pid > 0) ? current_person( $pid ) : NULL,
		"change"    => ($change == 'yes') ? true : NULL,
		"readonly"  => ($data['readonly'] == "yes") ? true : NULL,
		"day"       => ($data['day'] == 'yes') ? 1 : NULL,
		"do"        => NULL,
		"doit"      => $userSettings['taskCheckBlock'] == 'yes' && $da['iduser'] != $iduser1 ? NULL : 1,
		"rezult"    => $rezultat,
		"users"     => $usera,
		"agenda"    => (is_between( diffDate2( $data['datum'], current_datum() ), 0, 5 )) ? $txt : NULL,
		"usercount" => ($usercount > 0) ? $usercount : NULL,
		"iconuser"  => $iconuser
	];

}

foreach ( $days as $day => $val ) {

	$list[2]['tasks'][] = ["name" => getDay( $day )." ".ru_mon2( getMonth( $day ) ).', <span class="gray2">'.$lang['face']['WeekNameFull'][ (date( 'w', date_to_unix( $day.' 00:00:00' ) ) - 1) ].'</span>'];

	foreach ( $val as $item ) {
		$list[2]['tasks'][] = $item;
	}

}

$lists = ["list" => $list];

print json_encode_cyr( $lists );

exit();