<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        salesman.pro          */
/*        ver. 2018.x           */
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

$action  = $_REQUEST['action'];
$onlymyp = $_REQUEST['onlymyp'];
$users   = explode( ",", $_REQUEST['user'] );

$steps = [];

if ( $onlymyp != 'yes' ) {

	if ( count( $users ) == 0 ) {

		$sort = "deal.iduser IN(".implode( ",", get_people( $iduser1, "yes" ) ).") and ";
		$viz  = 'block';

	}
	else {

		//print_r($users);

		if ( $users[0] != '' ) {
			$sort = "deal.iduser IN (".implode( ",", $users ).") and ";
		}
		$viz = 'none';

	}

}
else {
	$sort = "deal.iduser = '$iduser1' and ";
}

$sort = ($sort != '') ? $sort : "deal.iduser IN(".implode( ",", get_people( $iduser1, "yes" ) ).") and ";

$result = $db -> getAll( "SELECT idcategory, CAST(title AS UNSIGNED) as step, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
foreach ( $result as $da ) {

	$bg   = 'graybg';
	$step = (int)$da['step'];

	if ( (int)$da['step'] >= 20 && (int)$da['step'] < 60 ) {
		$bg = 'greenbg bordered';
	}
	elseif ( (int)$da['step'] >= 60 && (int)$da['step'] < 90 ) {
		$bg = 'bluebg bordered';
	}
	elseif ( (int)$da['step'] >= 90 && (int)$da['step'] <= 100 ) {
		$bg = 'redbg bordered';
	}

	if ( is_between( $step, 0, 20 ) ) {
		$bg = 'step-0-20 bordered';
	}
	elseif ( is_between( $step, 20, 40 ) ) {
		$bg = 'step-20-40 bordered';
	}
	elseif ( is_between( $step, 40, 60 ) ) {
		$bg = 'step-40-60 bordered';
	}
	elseif ( is_between( $step, 60, 80 ) ) {
		$bg = 'step-60-80 bordered';
	}
	elseif ( is_between( $step, 80, 99 ) ) {
		$bg = 'step-80-100 bordered';
	}
	elseif ( $step == 100 ) {
		$bg = 'step-100 bordered';
	}

	$nxt = ((int)$da['step'] < 100) ? "yes" : "";

	//массив этапов с названием и расшифровкой
	$steps[ (int)$da['idcategory'] ] = [
		"stepid"      => $da['idcategory'],
		"step"        => $da['step'],
		"stepcontent" => $da['content'],
		"stepcolor"   => $bg,
		"next"        => $nxt
	];

}

$stepcount = count( array_keys($steps) );
$stepwidth = 99 / $stepcount;//ширина слоя на этап

//массив сделок
$q = "
SELECT
	deal.did as did,
	deal.title as title,
	deal.datum_plan as plan,
	deal.idcategory as idstep,
	deal.kol as kol,
	deal.tip as tip,
	deal.iduser as iduser,
	deal.direction as dir,
	{$sqlname}user.title as user,
	{$sqlname}clientcat.clid as clid,
	{$sqlname}clientcat.title as client,
	{$sqlname}direction.title as direction
FROM {$sqlname}dogovor `deal`
	LEFT JOIN {$sqlname}user ON deal.iduser = {$sqlname}user.iduser
	LEFT JOIN {$sqlname}clientcat ON deal.clid = {$sqlname}clientcat.clid
	LEFT JOIN {$sqlname}dogcategory ON deal.idcategory = {$sqlname}dogcategory.idcategory
	LEFT JOIN {$sqlname}direction ON deal.direction = {$sqlname}direction.id
WHERE
	deal.did > 0 and
	$sort
	deal.close != 'yes' and
	deal.identity = '$identity'
ORDER BY deal.datum_plan";

$res = $db -> getAll( $q );
foreach ( $res as $d ) {

	$color   = 'blue';
	$bgcolor = 'bluebg-lite';
	$state   = 'good1';

	if ( diffDate2( $d['plan'] ) <= -15 ) {

		$color   = 'red';
		$bgcolor = 'redbg-dark';
		$state   = 'bad1';

	}
	elseif ( is_between( diffDate2( $d['plan'] ), -15, 0 ) ) {

		$color   = 'red';
		$bgcolor = 'redbg';
		$state   = 'bad1';

	}
	elseif ( is_between( diffDate2( $d['plan'] ), 0, 15 ) ) {

		$color   = 'green';
		$bgcolor = 'greenbg-lite';
		$state   = 'month';

	}
	elseif ( is_between( diffDate2( $d['plan'] ), 15, 30 ) ) {

		$color   = 'green';
		$bgcolor = 'greenbg';
		$state   = 'month';

	}
	elseif ( diffDate2( $d['plan'] ) > 30 ) {

		$color   = 'blue';
		$bgcolor = 'bluebg';
		$state   = 'month';

	}

	$stepDay = abs( round( diffDate2( $db -> getOne( "SELECT MAX(datum) as datum FROM {$sqlname}steplog WHERE did='".$d['did']."'" ) ) ) );

	$steps[ $d['idstep'] ]['deal'][] = [
		"did"       => $d['did'],
		"tip"       => $d['tip'],
		"title"     => $d['title'],
		"datum"     => format_date_rus( $d['plan'] ),
		"summa"     => num_format( $d['kol'] ),
		"user"      => $d['user'],
		"iduser"    => $d['iduser'],
		"color"     => $color,
		"bgcolor"   => $bgcolor,
		"stepday"   => $stepDay,
		"clid"      => $d['clid'],
		"client"    => $d['client'],
		"direction" => $d['dir'],
		"state"     => $state
	];

	$steps[ $d['idstep'] ]['summa'] += $d['kol'];

}

$stepss = [];

foreach ( $steps as $key => $value ) {

	$stepss[] = $value;

}

$step = [
	"steps"     => $stepss,
	"stepwidth" => $stepwidth,
	"valuta"    => $valuta
];

print json_encode_cyr( $step );