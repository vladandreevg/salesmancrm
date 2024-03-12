<?php
error_reporting( 0 );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$y  = date( "Y", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 );
$m  = date( "m", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 );
$nd = date( "d", mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 );

//дела на сегодня
$q   = "
	SELECT 
		DISTINCT(".$sqlname."tasks.tid),
		".$sqlname."tasks.datum,
		".$sqlname."tasks.totime,
		".$sqlname."tasks.title,
		".$sqlname."tasks.tip,
		".$sqlname."tasks.priority,
		".$sqlname."tasks.speed,
		".$sqlname."tasks.clid,
		".$sqlname."tasks.pid,
		".$sqlname."tasks.did,
		".$sqlname."activities.color,
		".$sqlname."clientcat.title as client,
		".$sqlname."dogovor.title as dogovor
	FROM ".$sqlname."tasks 
		LEFT JOIN ".$sqlname."activities ON ".$sqlname."tasks.tip = ".$sqlname."activities.title
		LEFT JOIN ".$sqlname."personcat ON ".$sqlname."tasks.pid = ".$sqlname."personcat.pid
		LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."tasks.clid = ".$sqlname."clientcat.clid
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."tasks.did = ".$sqlname."dogovor.did
	WHERE 
		".$sqlname."tasks.iduser='".$iduser1."' and 
		".$sqlname."tasks.active != 'no' and 
		".$sqlname."tasks.datum = '".current_datum()."' and 
		".$sqlname."tasks.identity = '$identity' 
	GROUP BY ".$sqlname."tasks.tid
	ORDER BY ".$sqlname."tasks.datum, ".$sqlname."tasks.totime";
$res = $db -> query( $q );
while ( $da = $db -> fetch( $res ) ) {

	$clid = '';
	$pids = '';

	$diff = difftimefull( $da[ 'datum' ]." ".$da[ 'totime' ] );

	if ( $diff < 0 ) $tcolor = 'red';
	elseif ( $diff == 0 ) $tcolor = 'green';
	else $tcolor = 'blue';

	if ( $hours <= 1 ) $change = 'yes';

	$color = $da[ 'color' ];
	if ( $color == "" ) $color = "transparent";

	if ( $da[ 'pid' ] != '' ) $pids = count( yexplode( ";", $da[ 'pid' ] ) );
	if ( $da[ 'clid' ] > 0 ) $clid = $da[ 'clid' ];

	$today[] = [
		"tid"      => $da[ 'tid' ],
		"datum"    => $da[ 'datum' ],
		"time"     => getTime( (string)$da[ 'totime' ] ),
		"title"    => $da[ 'title' ],
		"tip"      => $da[ 'tip' ],
		"icon"     => get_ticon( (string)$da[ 'tip' ] ),
		"tcolor"   => $tcolor,
		"color"    => $color,
		"priority" => get_priority( 'priority', $da[ 'priority' ] ),
		"speed"    => get_priority( 'speed', $da[ 'speed' ] ),
		"clid"     => $clid,
		"client"   => $da[ 'client' ],
		"did"      => $da[ 'did' ],
		"dogovor"  => $da[ 'dogovor' ],
		"pids"     => $pids
	];

}

//print_r($today);

//дела на текущей неделе
$week = getPeriod( 'calendarweek' );
$w1   = $week[ 0 ];
$w2   = $week[ 1 ];

$week = [];

$q   = "
	SELECT 
		DISTINCT(".$sqlname."tasks.tid),
		".$sqlname."tasks.datum,
		".$sqlname."tasks.totime,
		".$sqlname."tasks.title,
		".$sqlname."tasks.tip,
		".$sqlname."tasks.priority,
		".$sqlname."tasks.speed,
		".$sqlname."tasks.clid,
		".$sqlname."tasks.pid,
		".$sqlname."tasks.did,
		".$sqlname."activities.color,
		".$sqlname."clientcat.title as client,
		".$sqlname."dogovor.title as dogovor
	FROM ".$sqlname."tasks 
		LEFT JOIN ".$sqlname."activities ON ".$sqlname."tasks.tip = ".$sqlname."activities.title
		LEFT JOIN ".$sqlname."personcat ON ".$sqlname."tasks.pid = ".$sqlname."personcat.pid
		LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."tasks.clid = ".$sqlname."clientcat.clid
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."tasks.did = ".$sqlname."dogovor.did
	WHERE 
		".$sqlname."tasks.iduser='".$iduser1."' and 
		".$sqlname."tasks.active != 'no' and 
		".$sqlname."tasks.datum BETWEEN '$w1 00:00:00' and '$w2 23:59:59' and 
		".$sqlname."tasks.datum != '".current_datum()."' and 
		".$sqlname."tasks.identity = '$identity' 
	GROUP BY ".$sqlname."tasks.tid
	ORDER BY ".$sqlname."tasks.datum, ".$sqlname."tasks.totime";
$res = $db -> query( $q );
while ( $da = $db -> fetch( $res ) ) {

	$clid = '';
	$pids = '';

	$diff = difftimefull( $da[ 'datum' ]." ".$da[ 'totime' ] );

	if ( $diff < 0 ) $tcolor = 'red';
	elseif ( $diff == 0 ) $tcolor = 'green';
	else $tcolor = 'blue';

	if ( $hours <= 1 ) $change = 'yes';

	$color = $da[ 'color' ];
	if ( $color == "" ) $color = "transparent";

	if ( $da[ 'pid' ] != '' ) $pids = count( yexplode( ";", $da[ 'pid' ] ) );
	if ( $da[ 'clid' ] > 0 ) $clid = $da[ 'clid' ];

	$week[ $da[ 'datum' ] ][] = [
		"tid"      => $da[ 'tid' ],
		"datum"    => format_date_rus( (string)$da[ 'datum' ] ),
		"time"     => getTime( (string)$da[ 'totime' ] ),
		"title"    => $da[ 'title' ],
		"tip"      => $da[ 'tip' ],
		"icon"     => get_ticon( (string)$da[ 'tip' ] ),
		"tcolor"   => $tcolor,
		"color"    => $color,
		"priority" => get_priority( 'priority', $da[ 'priority' ] ),
		"speed"    => get_priority( 'speed', $da[ 'speed' ] ),
		"clid"     => $clid,
		"client"   => $da[ 'client' ],
		"did"      => $da[ 'did' ],
		"dogovor"  => $da[ 'dogovor' ],
		"pids"     => $pids
	];

}

//print_r($week);

//Составим события по КТ
$q   = "
	SELECT
		".$sqlname."complect.data_plan as dplan,
		".$sqlname."complect.did as did,
		".$sqlname."complect.iduser as iduser,
		".$sqlname."complect_cat.title as title,
		".$sqlname."dogovor.title as dogovor
	FROM ".$sqlname."complect
		LEFT JOIN ".$sqlname."complect_cat ON ".$sqlname."complect.ccid = ".$sqlname."complect_cat.ccid
		LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."complect.did = ".$sqlname."dogovor.did
	WHERE
		".$sqlname."complect.doit != 'yes' and
		".$sqlname."complect.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") and
		".$sqlname."complect.data_plan BETWEEN '$w1 00:00:00' and '$w2 23:59:59' and 
		".$sqlname."complect.identity = '$identity'
	ORDER BY ".$sqlname."complect.data_plan";
$res = $db -> query( $q );
while ( $da = $db -> fetch( $res ) ) {

	if ( datestoday( $da[ 'dplan' ] ) < 0 ) $color = 'red';
	elseif ( datestoday( $da[ 'dplan' ] ) == 0 ) $color = 'broun';
	else $color = 'blue';

	$points[ $da[ 'dplan' ] ][] = [
		"title"   => $da[ 'title' ],
		"dogovor" => $da[ 'dogovor' ],
		"icon"    => '<i class="icon-check '.$color.'"></i>',
		"iduser"  => $da[ 'iduser' ],
		"tip"     => "Контрольная точка",
		"did"     => $da[ 'did' ]
	];

}
?>
<!DOCTYPE HTML>
<HTML lang="ru">
<HEAD>
	<TITLE>Расписание - SalesMan CRM</TITLE>
	<META content="text/html; charset=utf-8" http-equiv="Content-Type">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.js.css?v=345">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.css?v=345">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.menu.css?v=345">
	<link rel="stylesheet" type="text/css" href="/assets/css/mail.css?v=345">
	<link rel="stylesheet" type="text/css" href="/assets/css/nanoscroller.css?v=345">
	<link rel="stylesheet" type="text/css" href="/assets/css/ui.jquery.css">
	<link rel="stylesheet" type="text/css" href="/assets/css/animation.css">
	<link rel="stylesheet" href="/assets/css/fontello.css?v=341">
	<!--<link rel="stylesheet" type="text/css" href="css/theme.css">-->
	<?php
	if ( $userSettings[ 'userTheme' ] != '' ) print '<link rel="stylesheet" id="theme" type="text/css" href="/assets/css/themes/theme-'.$userSettings[ 'userTheme' ].'.css">';
	else print '<link rel="stylesheet" id="theme" type="text/css" href="/assets/css/theme.css">';
	?>
	<STYLE type="text/css">
		body {
			background : #FFF;
			padding    : 5px;
			overflow: auto;
		}
		html {
			background : #FFF;
		}
		.zametka {
			background : url(/assets/images/tetrad.png);
		}
	</STYLE>
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
	<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/ui.jquery.js"></script>

	<script type="text/javascript" src="/assets/js/jquery/jquery.nanoscroller.js?v=360"></script>
	<script type="text/javascript" src="/assets/js/app.js?v=360"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.ptTimeSelect.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.form.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.meio.mask.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.autocomplete.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/ui.multiselect.js"></script>
</HEAD>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="row">

	<div class="column12 grid-3">
		<div class="graybg fs-12 pad10 center-text Bold margbot10 sticked--top">Текущая неделя</div>
		<?php
		foreach ( $week as $key => $val ) {

			print '<div class="cherta Bold pad5 fs-12 margbot5 gray2">'.format_date_rus_name( $key ).'</div>';

			foreach ( $val as $k => $v ) {

				$client  = '';
				$dogovor = '';

				if ( $v[ 'clid' ] > 0 ) $client = '<div class="fs-09"><a href="javascript:void(0)" onclick="openClient(\''.$v[ 'clid' ].'\')" title=""><i class="icon-building blue"></i>&nbsp;'.$v[ 'client' ].'</a></div>';

				if ( $v[ 'did' ] > 0 ) $dogovor = '<div class="fs-09"><a href="javascript:void(0)" onclick="openDogovor(\''.$v[ 'did' ].'\')" title=""><i class="icon-briefcase blue"></i>&nbsp;'.$v[ 'dogovor' ].'</a></div>';

				print '
				<div class="row margbot10 ha" style="width: 100%">
					
					<div class="column12 grid-2 '.$v[ 'tcolor' ].'">'.$v[ 'time' ].' <br>'.$v[ 'priority' ].' '.$v[ 'speed' ].'</div>
					<div class="column12 grid-10">
					<span class="pull-aright infodiv fs-07 flh-10 pad5" title="'.$v[ 'tip' ].'"><b>'.$v[ 'tip' ].'</b></span>
						<div onclick="viewTask(\''.$v[ 'tid' ].'\')" class="black hand"><b>'.$v[ 'title' ].'</b></div>
						'.$client.'
						'.$dogovor.'
					</div>
					<hr class="pad0 mrg0">
					
				</div>
				';
			}
		}
		?>
	</div>
	<div class="column12 grid-3">
		<div class="redbg fs-12 pad10 center-text Bold margbot10 sticked--top">Сегодня - <?= format_date_rus_name( current_date() ) ?></div>
		<?php
		foreach ( $today as $key => $val ) {

			$client  = $dogovor = '';

			if ( $val[ 'clid' ] > 0 )
				$client = '<div class="fs-09"><a href="javascript:void(0)" onclick="openClient(\''.$val[ 'clid' ].'\')" title=""><i class="icon-building blue"></i>&nbsp;'.$val[ 'client' ].'</a></div>';

			if ( $val[ 'did' ] > 0 )
				$dogovor = '<div class="fs-09"><a href="javascript:void(0)" onclick="openDogovor(\''.$val[ 'did' ].'\')" title=""><i class="icon-briefcase blue"></i>&nbsp;'.$val[ 'dogovor' ].'</a></div>';

			print '
			<div class="row ha" style="width: 100%">
				
				<div class="column12 grid-2 '.$val[ 'tcolor' ].'">
					'.$val[ 'time' ].' <br>'.$val[ 'priority' ].' '.$val[ 'speed' ].'
				</div>
				<div class="column12 grid-10 relativ">
					<span class="pull-aright infodiv fs-07 flh-10 pad5 text-center" title="'.$val[ 'tip' ].'"><b>'.$val[ 'tip' ].'</b></span>
					<div onclick="viewTask(\''.$val[ 'tid' ].'\')" class="black hand"><b>'.$val[ 'title' ].'</b></div>
					'.$client.'
					'.$dogovor.'
				</div>
				
			</div>
			<hr class="pad0 mrg0">
			';
		}
		?>
	</div>
	<div class="column12 grid-3">
		<div class="graybg fs-12 pad10 center-text Bold margbot10 sticked--top">Контрольные точки</div>
		<?php
		foreach ( $points as $key => $val ) {

			print '<div class="cherta Bold pad5 fs-12 margbot5 gray2">'.format_date_rus_name( $key ).'</div>';

			foreach ( $val as $k => $v ) {

				$dogovor = '';

				if ( $v[ 'did' ] > 0 ) $dogovor = '<div class="fs-09"><a href="javascript:void(0)" onclick="openDogovor(\''.$v[ 'did' ].'\')" title=""><i class="icon-briefcase blue"></i>&nbsp;'.$v[ 'dogovor' ].'</a></div>';

				print '
				<div class="row margbot10 ha paddleft10" style="width: 100%">
					
					<div class="column12 grid-1"></div>
					<div class="column12 grid-11">
						<span class="hand" onClick="doLoad(\'content/lists/dt.health.php?did='.$v[ 'did' ].'&action=view\')">'.$v[ 'icon' ].'<b>'.$v[ 'title' ].'</b></span>
							'.$dogovor.'
					</div>
					<hr class="pad0 mrg0">
					
				</div>
				';
			}
		}
		?>
	</div>
	<div class="column12 grid-3">
		<div class="bgbezh fs-12 pad10 center-text Bold margbot10 sticked--top">Заметки</div>
		<div class="zametka" style="height: 85vh"></div>
	</div>

</div>

</body>
</html>
