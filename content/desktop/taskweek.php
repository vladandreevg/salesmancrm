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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

global $userRights;

$action = $_REQUEST[ 'action' ];

$tasks = [];

/**
 * Выводит дела в левом блоке Рабочего стола или в popup-блоке в других разделах
 * вызывается из "lp.taskweek.php"
 * использует шаблон "lp.taskweek.mustache"
 */

$thistime   = date( 'G:i', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + ( $tzone ) * 3600 );
$ttime      = date( "G:i", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
$ntime      = date( "G:i", mktime( date( 'H' ), (int)date( 'i' ) + 10, date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
$thismounth = date( "Y-m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );

$y   = $_REQUEST[ 'y' ];
$m   = $_REQUEST[ 'm' ];
$old = $_REQUEST[ 'old' ];

if ( !$y ) {
	$y = date("Y", mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) + $tzone * 3600);
}
if ( !$m ) {
	$m = date("m", mktime(date('H'), date('i'), date('s'), date('m'), date('d'), date('Y')) + $tzone * 3600);
}

if ( strlen( $y ) < 4 ) {
	$y += 2000;
}
if ( strlen( $m ) < 2 ) {
	$m = "0".$m;
}

$nd = date( "d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
$nm = date( "m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tzone * 3600 );
$dd = date( "t", mktime( date( 'H' ), date( 'i' ), date( 's' ), $m, date( 'd' ), $y ) + $tzone * 3600 ); //получили Стоимость дней в месяце

//формируем выборку по дате, в зависимости от необходимости
if ( $m != $nm ) {
	$s = " and DATE_FORMAT(datum, '%Y-%m') = '$y-$m'";
} //если месяц не текущий, то берем период весь месяц
else {

	//текущий месяц просроченные
	if ( $old == 'old' ) {
		$s = " and DATE_FORMAT(datum, '%Y-%m') = '$y-$m' and datum < '".current_datum()."'";
	}
	elseif ( $old == '' ) {
		$s = " and datum = '".current_datum()."'";
	}
	elseif ( $old == 'future' ) {
		$s = " and DATE_FORMAT(datum, '%Y-%m') = '$y-$m' and datum > '".current_datum()."'";
	}

}

//составляем массив событий по датам
$res = $db -> getAll( "SELECT * FROM {$sqlname}tasks WHERE iduser = '$iduser1' $s and active != 'no' and identity = '$identity' ORDER BY datum, FIELD(day, 'yes', null) DESC, totime" );
//print $db -> lastQuery();
foreach ( $res as $daa ) {

	$clid   = '';
	$pids   = '';
	$change = '';
	$do = '';

	$diff  = difftimefull( $daa[ 'datum' ]." ".$daa[ 'totime' ] );
	$hours = difftime( $daa[ 'created' ] );

	if ( $diff < 0 ) {
		$tcolor = 'red';
	}
	elseif ( $diff == 0 ) {
		$tcolor = 'green';
	}
	else {
		$tcolor = 'blue';
	}

	//if($hours <= 1) $change = 'yes';
	//$change = ($hours <= $hoursControlTime || $ac_import[7] == 'on') ? 'yes' : '';

	if ( $daa[ 'autor' ] == 0 || $daa[ 'autor' ] == $iduser1 || $daa[ 'iduser' ] == $iduser1 ) {
		$do = 'yes';
	}

	$color = $db -> getOne( "SELECT color FROM {$sqlname}activities WHERE title='".$daa[ 'tip' ]."' and identity = '$identity'" );
	if ( $color == "" ) {
		$color = "transparent";
	}

	if ( $daa[ 'pid' ] != '' ) $pids = count( yexplode( ";", $daa[ 'pid' ] ) );
	if ( $daa[ 'clid' ] > 0 ) $clid = $daa[ 'clid' ];

	if ( $daa[ 'autor' ] + 0 == 0 ) $daa[ 'autor' ] = $daa[ 'iduser' ];

	if ( $daa[ 'readonly' ] != 'yes' ) $daa[ 'readonly' ] = '';
	if ( $daa[ 'autor' ] == $daa[ 'iduser' ] && $daa[ 'readonly' ] == 'yes' ) $daa[ 'readonly' ] = '';
	if ( $daa[ 'autor' ] != $daa[ 'iduser' ] && $daa[ 'readonly' ] == 'yes' ) $daa[ 'readonly' ] = 'yes';

	//mod

	if ( $daa[ 'autor' ] == 0 || $daa[ 'autor' ] == $iduser1 ) {

		if ( $hours <= $hoursControlTime ) {
			$change = 'yes';
		}
		elseif ( $userRights['changetask'] ) {
			$change = 'yes';
		}

	}

	if ( $daa[ 'readonly' ] == 'yes' ) {

		$change = '';

		if ( $daa[ 'autor' ] == $daa[ 'iduser' ] || $daa[ 'author' ] == 0 || $daa[ 'autor' ] == $iduser1 ) {

			$daa[ 'readonly' ] = '';
			$change            = 'yes';

		}

	}

	if ( (int)$daa[ 'did' ] == 0 ) {
		$daa['did'] = '';
	}

	$tasks[ $daa[ 'datum' ] ][] = [
		"tid"      => $daa[ 'tid' ],
		"datum"    => $daa[ 'datum' ],
		"time"     => getTime( (string)$daa[ 'totime' ] ),
		"title"    => $daa[ 'title' ],
		"tip"      => $daa[ 'tip' ],
		"icon"     => get_ticon( (string)$daa[ 'tip' ] ),
		"tcolor"   => $tcolor,
		"color"    => $color,
		"priority" => get_priority( 'priority', $daa[ 'priority' ] ),
		"speed"    => get_priority( 'speed', $daa[ 'speed' ] ),
		"clid"     => $clid,
		"did"      => $daa[ 'did' ],
		"pids"     => $pids,
		"readonly" => $daa[ 'readonly' ],
		"day"      => $daa[ 'day' ],
		"change"   => $change,
		"autor"    => current_user( $daa[ 'autor' ] )
	];

}

foreach ( $tasks as $key => $values ) {

	$events = [];

	foreach ($values as $value) {

		$events[] = [
			"tid"      => $value[ 'tid' ],
			"tcolor"   => $value[ 'tcolor' ],
			"time"     => $value[ 'time' ],
			"priority" => $value[ 'priority' ],
			"speed"    => $value[ 'speed' ],
			"color"    => $value[ 'color' ],
			"icon"     => $value[ 'icon' ],
			"tip"      => $value[ 'tip' ],
			"title"    => $value[ 'title' ],
			"clid"     => $value[ 'clid' ],
			"client"   => current_client( $value[ 'clid' ] ),
			"did"      => $value[ 'did' ],
			"dogovor"  => current_dogovor( $value[ 'did' ] ),
			"pids"     => $value[ 'pids' ],
			"readonly" => $value[ 'readonly' ],
			"day"      => ( $value[ 'day' ] == 'yes' ) ? 1 : NULL,
			"change"   => $value[ 'change' ],
			"autor"    => $value[ 'autor' ]
		];

	}

	$data[ $key ] = [
		"day"    => get_dateru( $key ),
		"events" => $events
	];

}

//Составим события по КТ
$q = "
	SELECT
		{$sqlname}complect.data_plan as dplan,
		{$sqlname}complect.did as did,
		{$sqlname}complect.iduser as iduser,
		{$sqlname}complect_cat.title as title
	FROM {$sqlname}complect
		LEFT JOIN {$sqlname}complect_cat ON {$sqlname}complect.ccid = {$sqlname}complect_cat.ccid
	WHERE
		{$sqlname}complect.doit != 'yes' and
		{$sqlname}complect.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).")
		".str_replace( "datum", $sqlname."complect.data_plan", $s )." and
		{$sqlname}complect.identity = '$identity'";

$re = $db -> getAll( $q );
foreach ( $re as $daa ) {

	if ( datestoday( $daa[ 'dplan' ] ) < 0 ) $color = 'red';
	elseif ( datestoday( $daa[ 'dplan' ] ) == 0 ) $color = 'broun';
	else $color = 'blue';

	$points[ $daa[ 'dplan' ] ][] = [
		"title"   => $daa[ 'title' ],
		"dogovor" => current_dogovor( $daa[ 'did' ] ),
		"icon"    => '<i class="icon-check '.$color.'"></i>',
		"iduser"  => $daa[ 'iduser' ],
		"tip"     => "Контрольная точка",
		"did"     => $daa[ 'did' ]
	];

}

//print_r($points);

foreach ( $points as $key => $values ) {

	$cpoint = [];

	foreach ($values as $value) {

		$cpoint[] = [
			"title"   => $value[ 'title' ],
			"icon"    => $value[ 'icon' ],
			"iduser"  => $value[ 'iduser' ],
			"tip"     => "Контрольная точка",
			"did"     => $value[ 'did' ],
			"dogovor" => $value[ 'dogovor' ]
		];

	}

	if ( !$data[ $key ][ "day" ] ) $data[ $key ][ "day" ] = get_dateru( $key );

	$data[ $key ][ "cpoint" ] = $cpoint;
}

foreach ( $data as $key => $value ) {

	$da[] = $value;

}

if ( $y."-".$m != $thismounth && count( $tasks ) == 0 ) {
	$today = '';
}
if ( $y."-".$m == $thismounth && count( $tasks ) == 0 && $old == '' ) {
	$today = 'yes';
}

$istoday = ( $y."-".$m == $thismounth && $old == '' ) ? 'yes' : '';

$count = !empty($tasks) ? count( $tasks ) : '';

$events = [
	"data"    => $da,
	"count"   => $count,
	"today"   => $today,
	"istoday" => $istoday
];

print json_encode_cyr( $events );