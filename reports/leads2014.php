<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*           ver. 7.77          */
/* ============================ */
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$da1          = $_REQUEST[ 'da1' ];
$da2          = $_REQUEST[ 'da2' ];
$act          = $_REQUEST[ 'act' ];
$period       = $_REQUEST[ 'period' ];

$per          = isset( $_REQUEST[ 'per' ] ) ? 'nedelya' : $_REQUEST[ 'per' ];

$user_list    = $_REQUEST[ 'user_list' ];
$field        = $_REQUEST[ 'field' ];
$field_query  = $_REQUEST[ 'field_query' ];

$period = ( $period == '' ) ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ( $da1 != '' ) ? $da1 : $period[ 0 ];
$da2 = ( $da2 != '' ) ? $da2 : $period[ 1 ];

$mdwset       = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'" );
$leadsettings = json_decode( $mdwset[ 'content' ], true );
$operators    = $leadsettings[ 'leadOperator' ];

if ( empty( $operators ) ) {

	print '
		<div id="dialoge">
			<div class="warning p20" align="left" style="width:600px">
				<span><i class="icon-attention red icon-3x pull-left"></i></span>
				<b class="red uppercase">Внимание:</b><br><br>
				Не задан список операторов.<br>
				Построение отчетов не возможно.
			</div>
		</div>
		';

	exit();

}

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if (!empty($user_list)) {
	$sort .= " iduser IN (".yimplode( ",", $user_list ).") AND ";
}


$da1_array = explode( "-", $da1 );
$da2_array = explode( "-", $da2 );

$dstart = mktime( 0, 0, 0, $da1_array[ 1 ], $da1_array[ 2 ], $da1_array[ 0 ] );
$dend   = mktime( 23, 59, 59, $da2_array[ 1 ], $da2_array[ 2 ], $da2_array[ 0 ] );
$step   = 86400;
$day    = intval( ( $dend - $dstart ) / $step ) + 1;

$dat = $dstart;//стартовое значение даты

$status = [
	'0' => 'Открыт',
	'1' => 'В работе',
	'2' => 'Обработан',
	'3' => 'Закрыт'
];
$colors = [
	'0' => 'red',
	'1' => 'green',
	'2' => 'blue',
	'3' => 'gray'
];
$rezult = [
	'1' => 'Спам',
	'2' => 'Дубль',
	'3' => 'Другое'
];

$i       = 0;
$perName = 'День';

$datac = $datas = [];

if ( abs( diffDate2( $da1, $da2 ) ) <= 62 ) {

	for ( $d = 0; $d < $day; $d++ ) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date( 'Y-m-d', $dat );

		$countAll[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND date_format(datum, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countOpen[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '0' AND date_format(datum, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countDo[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort status = '2' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countClose[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort status = '3' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$datas[] = '{"Дата":"'.$datum.'","Интересов":"Поступило","Кол-во":"'.$countAll[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$datas[] = '{"Дата":"'.$datum.'","Интересов":"Обработано","Кол-во":"'.$countDo[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$datas[] = '{"Дата":"'.$datum.'","Интересов":"Закрыто","Кол-во":"'.$countClose[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';

		//////////////////////

		$countSpam[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '1' AND status = '3' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countDouble[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '2' AND status = '3' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countOther[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '3' AND status = '3' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countGoal[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '4' AND status = '3' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$countDeals[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort did > 0 AND status = '2' AND date_format(datum_do, '%Y-%m-%d') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

		$datac[] = '{"Дата":"'.$datum.'","Результат":"Сделок","Кол-во":"'.$countDeals[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$datac[] = '{"Дата":"'.$datum.'","Результат":"Спам","Кол-во":"'.$countSpam[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$datac[] = '{"Дата":"'.$datum.'","Результат":"Дубль","Кол-во":"'.$countDouble[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$datac[] = '{"Дата":"'.$datum.'","Результат":"Другое","Кол-во":"'.$countOther[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';
		$datac[] = '{"Дата":"'.$datum.'","Результат":"Не целевой","Кол-во":"'.$countGoal[ $i ].'","День":"'.date( "d.m", strtotime( $datum ) ).'"}';

		$i++;

	}

}
else {

	$perName = 'Месяц';

	//количество месяцев
	$monStart  = (int)getMonth( $da1 );
	$yearStart = (int)get_year( $da1 );

	$monEnd  = (int)getMonth( $da2 );
	$yearEnd = (int)get_year( $da2 );

	$mon  = $monStart;
	$year = $yearStart;

	while ( $year <= $yearEnd ) {

		while ( $mon <= 12 ) {

			$mon1 = ( $mon < 10 ) ? "0".$mon : $mon;

			$datum = $year."-".$mon1;
			$date  = $mon1.".".$year;

			//
			$countAll[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND date_format(datum, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countOpen[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '0' AND date_format(datum, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countDo[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort status = '2' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countClose[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort status = '3' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$datas[] = '{"Дата":"'.$datum.'","Интересов":"Поступило","Кол-во":"'.$countAll[ $i ].'","Месяц":"'.$date.'"}';
			$datas[] = '{"Дата":"'.$datum.'","Интересов":"Обработано","Кол-во":"'.$countDo[ $i ].'","Месяц":"'.$date.'"}';
			$datas[] = '{"Дата":"'.$datum.'","Интересов":"Закрыто","Кол-во":"'.$countClose[ $i ].'","Месяц":"'.$date.'"}';

			//////////////////////

			$countSpam[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '1' AND status = '3' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countDouble[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '2' AND status = '3' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countOther[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '3' AND status = '3' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countGoal[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort rezult = '3' AND status = '4' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$countDeals[ $i ] = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort did > 0 AND status = '2' AND date_format(datum_do, '%Y-%m') = '$datum' AND identity = '$identity' ORDER BY datum DESC" );

			$datac[] = '{"Дата":"'.$datum.'","Результат":"Сделок","Кол-во":"'.$countDeals[ $i ].'","Месяц":"'.$date.'"}';
			$datac[] = '{"Дата":"'.$datum.'","Результат":"Спам","Кол-во":"'.$countSpam[ $i ].'","Месяц":"'.$date.'"}';
			$datac[] = '{"Дата":"'.$datum.'","Результат":"Дубль","Кол-во":"'.$countDouble[ $i ].'","Месяц":"'.$date.'"}';
			$datac[] = '{"Дата":"'.$datum.'","Результат":"Другое","Кол-во":"'.$countOther[ $i ].'","Месяц":"'.$date.'"}';
			$datac[] = '{"Дата":"'.$datum.'","Результат":"Не целевой","Кол-во":"'.$countGoal[ $i ].'","Месяц":"'.$date.'"}';

			$i++;

			if ( $year == $yearEnd && $mon == $monEnd ) goto endo;

			$mon++;

			if ( $mon > 12 ) {
				$mon = 1;
				goto y;
			}

		}

		y:

		$year++;

	}

	endo:

}

$datas = implode( ",", $datas );
$datac = implode( ",", $datac );

$countAll = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND datum BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countOpen = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '0' AND datum BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countWork = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort status = '1' AND datum BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countDo = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND $sort status = '2' AND datum_do BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countt = $countOpen + $countWork + $countDo;

$datad = [];

if ( $countOpen > 0 ) $datad[] = '{"Отчет":"Состояние","Тип":"Открыто","Кол-во":"'.$countOpen.'"}';
if ( $countWork > 0 ) $datad[] = '{"Отчет":"Состояние","Тип":"В работе","Кол-во":"'.$countWork.'"}';
if ( $countDo > 0 ) $datad[] = '{"Отчет":"Состояние","Тип":"Обработано","Кол-во":"'.$countDo.'"}';

////

$countSpam = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '3' AND rezult = '1' AND datum_do BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countDouble = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '3' AND rezult = '2' AND datum_do BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countOther = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '3' AND rezult = '3' AND datum_do BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countGoal = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id > 0 AND status = '3' AND rezult = '4' AND datum_do BETWEEN '$da1 00:00:01' AND '$da2 23:59:59' AND identity = '$identity'" );

$countc = $countSpam + $countDouble + $countOther + $countGoal;

$datacl = [];

if ( $countSpam > 0 ) $datacl[] = '{"Отчет":"Закрыто","Тип":"Спам","Кол-во":"'.$countSpam.'"}';
if ( $countDouble > 0 ) $datacl[] = '{"Отчет":"Закрыто","Тип":"Дубль","Кол-во":"'.$countDouble.'"}';
if ( $countOther > 0 ) $datacl[] = '{"Отчет":"Закрыто","Тип":"Другое","Кол-во":"'.$countOther.'"}';
if ( $countGoal > 0 ) $datacl[] = '{"Отчет":"Закрыто","Тип":"Не целевой","Кол-во":"'.$countGoal.'"}';

$datad  = implode( ",", $datad );
$datacl = implode( ",", $datacl );

//по источникам
$path   = $datapath = [];
$result = $db -> query( "SELECT clientpath FROM ".$sqlname."leads WHERE id > 0 AND $sort datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" );
while ( $data = $db -> fetch( $result ) ) {

	if ( $data[ 'clientpath' ] + 0 == 0 ) $data[ 'clientpath' ] = 0;

	$path[ $data[ 'clientpath' ] ] = $path[ $data[ 'clientpath' ] ] + 1;

}
foreach ( $path as $k => $v ) {

	$cp = ( $k == 0 ) ? "Без источника" : current_clientpathbyid( $k );

	$datapath[] = '{"Отчет":"Источники","Источник":"'.$cp.'","Кол-во":"'.$v.'"}';

}
$datapath = implode( ",", $datapath );

//print_r($path);

//преобразованы в Клиента + Сделку
$countClientWDeal = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE id > 0 AND clid > 0 AND did > 0 AND $sort datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" );

//преобразовано только в клиента
$countClientOnly = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE id > 0 AND clid > 0 AND did < 1 AND $sort datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" );

//преобразованы в Сделку и сделка реализована
$countClientWDealCloseOpen = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE close != 'yes' AND $sort did IN (SELECT did FROM ".$sqlname."leads WHERE did > 0 AND datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity') AND identity = '$identity'" );

$countClientWDealClosePlus = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE close = 'yes' AND kol_fact > 0 AND datum_close BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND $sort did IN (SELECT did FROM ".$sqlname."leads WHERE did > 0 AND datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity') AND identity = '$identity'" );

$countClientWDealCloseMinus = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE close = 'yes' AND kol_fact = 0 AND datum_close BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND $sort did IN (SELECT did FROM ".$sqlname."leads WHERE did > 0 AND datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity') AND identity = '$identity'" );

$times  = [];
$result = $db -> query( "SELECT TIMESTAMPDIFF(MINUTE, datum, datum_do) as time, iduser FROM ".$sqlname."leads WHERE id > 0 AND $sort datum > '$da1 00:00:00' AND datum_do < '$da2 23:59:59' AND identity = '$identity'" );
while ( $data = $db -> fetch( $result ) ) {

	if ( $data[ 'time' ] != NULL ) $times[] = $data[ 'time' ];

}

//print_r($times);

$minTime = min( $times );
$maxTime = max( $times );
$midTime = round( array_sum( $times ) / count( $times ), 0 );

$conversation = round( $countDo / $countAll * 100, 1 );

//стата по сотрудникам
$urez   = [];
$auser  = [];
$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser IN (".yimplode( ",", $operators ).") and identity = '$identity'" );
while ( $data = $db -> fetch( $result ) ) {

	$auser[ $data[ 'iduser' ] ] = $data[ 'title' ];

	$urez[ $data[ 'iduser' ] ][ 'countAll' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countDo' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND status = '2' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' and identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countDoClient' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND clid > 0 AND status = '2' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countDoDeal' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND  did > 0 AND status = '2' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countDoDealPlus' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE close = 'yes' AND kol_fact > 0 AND datum_close BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND iduser = '".$data[ 'iduser' ]."' AND did IN (SELECT did FROM ".$sqlname."leads WHERE did > 0 and datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity') AND identity = '$identity'" );

	$urez[ $data[ 'iduser' ] ][ 'countDoDealMinus' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE close = 'yes' AND kol_fact = 0 AND datum_close BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND iduser = '".$data[ 'iduser' ]."' AND did IN (SELECT did FROM ".$sqlname."leads WHERE did > 0 AND datum BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity') AND identity = '$identity'" );

	$urez[ $data[ 'iduser' ] ][ 'countCloseSpam' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND status = '3' AND rezult = '1' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countCloseDouble' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND status = '3' AND rezult = '2' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countCloseOther' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND status = '3' AND rezult = '3' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'countCloseGoal' ] = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND status = '3' AND rezult = '4' AND datum_do BETWEEN '$da1 00:00:00' AND '$da2 23:59:59' AND identity = '$identity'" ) + 0;

	$urez[ $data[ 'iduser' ] ][ 'conversation' ] = $urez[ $data[ 'iduser' ] ][ 'countAll' ] > 0 ? round( $urez[ $data[ 'iduser' ] ][ 'countDo' ] / $urez[ $data[ 'iduser' ] ][ 'countAll' ] * 100, 1 ) : 0;

	$times = $db -> getCol( "SELECT TIMESTAMPDIFF(MINUTE, datum, datum_do) as time FROM ".$sqlname."leads WHERE iduser = '".$data[ 'iduser' ]."' AND datum > '$da1 00:00:00' AND datum_do < '$da2 23:59:59' AND identity = '$identity'" );

	$urez[ $data[ 'iduser' ] ][ 'time' ] = count( $times ) > 0 ? round( array_sum( $times ) / count( $times ), 0 ) : 0;

}
?>
<style>
	.dimple-custom-axis-line {
		stroke       : black !important;
		stroke-width : 1.1;
	}
	.dimple-custom-axis-label {
		font-family : Arial !important;
		font-size   : 11px !important;
		font-weight : 500;
	}
	.dimple-custom-gridline {
		stroke-width     : 1;
		stroke-dasharray : 5;
		fill             : none;
		stroke           : #CFD8DC !important;
	}
</style>
<!--<script type="text/javascript" src="/assets/js/d3.min.js"></script>-->
<script type="text/javascript" src="/assets/js/dimple.js/dimple.min.js"></script>

<div class="relativ mt20 mb20 wp95" align="center">
	<h1 class="uppercase fs-14 m0 mb10">Статистика входящих интересов</h1>
	<div class="gray2">&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<hr>

<div class="block mb20">

	<div class="flex-container wp95 div-center pb20 mt20" style="margin:0 auto;">

		<div class="fs-12 Bold text-left wp90 pb20">Сводная информация:</div>

		<div class="flex-string wp35 text-left">

			<table width="90%" border="0" cellpadding="5" cellspacing="0">
				<thead>
				<tr class="bordered header_contaner">
					<th align="left" class="blue">Параметр</th>
					<th class="blue">Кол-во</th>
				</tr>
				</thead>
				<tr height="35" class="ha">
					<td><b class="fs-11">Заявок за период</b>:</td>
					<td align="right"><b class="fs-11"><?= $countAll ?></b>&nbsp;</td>
				</tr>
				<tr height="35">
					<td><b class="blue fs-11">Текущее состояние, в т.ч.:</b></td>
					<td align="right"><b class="blue fs-11"><?= $countt ?></b>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;Открыто:</td>
					<td align="right"><?= $countOpen ?>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;Обработано:</td>
					<td align="right"><?= $countDo ?>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;В обработке:</td>
					<td align="right"><?= $countWork ?>&nbsp;</td>
				</tr>
				<tr height="35">
					<td><b class="blue fs-11">Закрыто, в т.ч.:</b></td>
					<td align="right"><b class="blue fs-11"><?= $countc ?></b>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;Спам:</td>
					<td align="right"><?= $countSpam ?>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;Дубль:</td>
					<td align="right"><?= $countDouble ?>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;Другое:</td>
					<td align="right"><?= $countOther ?>&nbsp;</td>
				</tr>
				<tr height="30" class="ha">
					<td>&nbsp;&nbsp;Не целевой:</td>
					<td align="right"><?= $countGoal ?>&nbsp;</td>
				</tr>
			</table>

		</div>
		<div class="flex-string wp30">

			<div id="chartd" class="table pad5" style="height: 100%; border:0px solid  #eee;"></div>
			<script language="javascript">

				var width = $('#chartd').actual('width');
				var height = $('#chartd').actual('height');
				var svg = dimple.newSvg("#chartd", width, height);
				var data = [<?=$datad?>];

				var myChart = new dimple.chart(svg, data);

				myChart.assignColor("Открыто", "#CFD8DC");
				myChart.assignColor("В работе", "#66BB6A");
				myChart.assignColor("Обработано", "#2196F3");

				myChart.setBounds(20, 20, width, height);

				myChart.addMeasureAxis("p", "Кол-во");
				var ring = myChart.addSeries("Тип", dimple.plot.pie);

				ring.innerRadius = "50%";

				var myLegend = myChart.addLegend(0, 0, 100, 100, "left");
				myChart.setMargins(30, 100, 30, 30);
				myChart.draw(1000);

				//y.tickFormat = ".f";
				/*s.shapes.style("opacity", function (d) {
				 return (d.y === null ? 0 : 0.8);
				 });*/

				$(window).bind('resizeEnd', function () {
					myChart.draw(0, true);
				});

			</script>

		</div>
		<div class="flex-string wp30 ml5">

			<div id="chartcl" class="table pad5" style="height: 100%; border:0px solid  #eee;"></div>
			<script language="javascript">

				var width = $('#chartcl').actual('width');
				var height = $('#chartcl').actual('height');
				var svg = dimple.newSvg("#chartcl", width, height);
				var data = [<?=$datacl?>];

				var myChartl = new dimple.chart(svg, data);

				myChartl.assignColor("Спам", "#37474F");
				myChartl.assignColor("Дубль", "#607D8B");
				myChartl.assignColor("Не целевой", "#90A4AE");
				myChartl.assignColor("Другое", "#CFD8DC");

				myChartl.setBounds(20, 20, width, height);

				myChartl.addMeasureAxis("p", "Кол-во");
				var ring2 = myChartl.addSeries("Тип", dimple.plot.pie);

				ring2.innerRadius = "50%";

				var myLegend = myChartl.addLegend(0, 0, 100, 100, "left");
				myChartl.setMargins(30, 100, 30, 30);
				myChartl.draw(100);

				//y.tickFormat = ".f";
				/*s.shapes.style("opacity", function (d) {
				 return (d.y === null ? 0 : 0.8);
				 });*/

				$(window).bind('resizeEnd', function () {
					myChartl.draw(0, true);
				});

			</script>

		</div>

	</div>

	<hr>

	<div class="flex-container wp95 div-center mt20 pt20" style="margin:0 auto; min-height: 300px">

		<div class="fs-12 Bold text-left wp90 pb20">Сводная по источникам:</div>

		<div class="flex-string wp30 text-left">

			<table width="90%" border="0" cellpadding="5" cellspacing="0">
				<thead>
				<tr class="bordered header_contaner">
					<th align="left" class="blue">Источник</th>
					<th class="blue">Кол-во</th>
				</tr>
				</thead>
				<?php
				foreach ( $path as $k => $v ) {

					$cp = ( $k == 0 ) ? "Без источника" : current_clientpathbyid( $k );
					?>
					<tr height="40" class="ha">
						<td><?= $cp ?></td>
						<td align="right"><b><?= $v ?></b>&nbsp;</td>
					</tr>
				<?php } ?>
			</table>

		</div>
		<div class="flex-string wp30">

			<table width="95%" border="0" cellpadding="5" cellspacing="0" class="ml5 text-left">
				<thead>
				<tr class="bordered header_contaner">
					<th align="left" class="blue"></th>
					<th align="right" class="blue" width="20%">Кол-во</th>
				</tr>
				</thead>
				<tr height="35">
					<td><b class="fs-11 blue">Конвертация (%):</b></td>
					<td align="right">
						<b class="fs-11"><?= round( ( $countClientOnly + $countClientWDeal ) / ( $countDo + $countc ) * 100, 1 ) ?></b>
					</td>
				</tr>
				<tr height="35" class="ha">
					<td>Создан только Клиент:</td>
					<td align="right"><b><?= $countClientOnly ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td>Созданы Клиент и Сделка:</td>
					<td align="right"><b><?= $countClientWDeal ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td>Реализованные Сделки:</td>
					<td align="right"><b><?= $countClientWDealClosePlus ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td>Отмененные Сделки:</td>
					<td align="right"><b><?= $countClientWDealCloseMinus ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td>Сделки в работе:</td>
					<td align="right"><b><?= $countClientWDealCloseOpen ?></b>&nbsp;</td>
				</tr>
				<tr height="35">
					<td><b class="fs-11 blue">Время обработки (мин.):</b></td>
					<td></td>
				</tr>
				<tr height="35" class="ha">
					<td>Минимальное:</td>
					<td align="right"><b><?= $minTime ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td>Максимальное:</td>
					<td align="right"><b><?= $maxTime ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td>Среднее:</td>
					<td align="right"><b><?= $midTime ?></b>&nbsp;</td>
				</tr>
				<tr height="35" class="ha">
					<td><b class="fs-11 blue">Конверсия (%):</b></td>
					<td align="right"><b class="fs-11"><?= $conversation ?></b>&nbsp;</td>
				</tr>
			</table>

		</div>
		<div class="flex-string wp40">

			<div id="chartpath" class="table pad5" style="height: 100%; border:0px solid #eee;"></div>
			<script language="javascript">

				var width = $('#chartpath').actual('width') - 50;
				var height = $('#chartpath').actual('height') - 50;
				var svg = dimple.newSvg("#chartpath", width, height);
				var data = [<?=$datapath?>];

				var myCharpath = new dimple.chart(svg, data);

				myCharpath.setBounds(20, 20, width, height);

				myCharpath.defaultColors = [
					new dimple.color("#F44336", "#F44336"),
					new dimple.color("#FF9800", "#FF9800"),
					new dimple.color("#FFEB3B", "#FFEB3B"),
					new dimple.color("#4CAF50", "#4CAF50"),
					new dimple.color("#2196F3", "#2196F3"),
					new dimple.color("#3F51B5", "#3F51B5"),
					new dimple.color("#673AB7", "#673AB7"),
					new dimple.color("#E91E63", "#E91E63"),
					new dimple.color("#03A9F4", "#03A9F4"),
					new dimple.color("#A1887F", "#A1887F"),
					new dimple.color("#FFC107", "#FFC107"),
					new dimple.color("#8BC34A", "#8BC34A"),
					new dimple.color("#00BCD4", "#00BCD4"),
					new dimple.color("#795548", "#795548"),
					new dimple.color("#7CB342", "#7CB342"),
					new dimple.color("#0097A7", "#0097A7"),
					new dimple.color("#D500F9", "#D500F9"),
					new dimple.color("#76FF03", "#76FF03"),
					new dimple.color("#DD2C00", "#DD2C00"),
					new dimple.color("#B0BEC5", "#B0BEC5"),
					new dimple.color("#90A4AE", "#90A4AE"),
					new dimple.color("#78909C", "#78909C"),
					new dimple.color("#607D8B", "#607D8B"),
					new dimple.color("#9E9E9E", "#9E9E9E"),
					new dimple.color("#6D4C41", "#6D4C41")
				];

				myCharpath.addMeasureAxis("p", "Кол-во");
				var ringp = myCharpath.addSeries("Источник", dimple.plot.pie);

				ringp.innerRadius = "50%";

				var myLegend = myCharpath.addLegend(0, 0, 300, 150, "left");
				myCharpath.setMargins(80, 40, 30, 30);
				myCharpath.draw(100);

				//y.tickFormat = ".f";
				/*s.shapes.style("opacity", function (d) {
				 return (d.y === null ? 0 : 0.8);
				 });*/

				$(window).bind('resizeEnd', function () {
					myCharpath.draw(0, true);
				});

			</script>

		</div>

	</div>

</div>

<hr>

<div class="block mb20">

	<table width="100%" border="0" cellpadding="5" cellspacing="0" id="userdata">
		<thead>
		<tr class="bordered black">
			<th rowspan="2">Сотрудник</th>
			<th width="100" rowspan="2" class="orangebg">Назначено</th>
			<th colspan="3" class="greenbg">Обработано</th>
			<th colspan="4" class="redbg">Закрыто</th>
			<th colspan="2" class="bluebg">Статистика</th>
		</tr>
		<tr>
			<th width="100" class="greenbg-sub">Клиенты</th>
			<th width="100" class="greenbg-sub">Сделки</th>
			<th width="100" class="greenbg-sub">Сделки.Реализ.</th>
			<th width="100" class="redbg-sub">Спам</th>
			<th width="100" class="redbg-sub">Дубль</th>
			<th width="100" class="redbg-sub">Другое</th>
			<th width="100" class="redbg-sub">Не целевой</th>
			<th width="100">Время обработки</th>
			<th width="100">Конверсия</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $urez as $user => $v ) {
			?>
			<tr height="45" class="ha fs-11">
				<td class="text"><b><?= strtr( $user, $auser ) ?></b></td>
				<td align="center"><?= $v[ 'countAll' ] ?></td>
				<td align="center"><?= $v[ 'countDoClient' ] ?></td>
				<td align="center"><?= $v[ 'countDoDeal' ] ?></td>
				<td align="center" class="greenbg-sub"><?= $v[ 'countDoDealPlus' ] ?></td>
				<td align="center"><?= $v[ 'countCloseSpam' ] ?></td>
				<td align="center"><?= $v[ 'countCloseDouble' ] ?></td>
				<td align="center"><?= $v[ 'countCloseOther' ] ?></td>
				<td align="center"><?= $v[ 'countCloseGoal' ] ?></td>
				<td align="center"><?= $v[ 'time' ] ?> мин.</td>
				<td align="center"><?= $v[ 'conversation' ] ?> %</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

</div>

<div id="graf" class="div-center pt15" style="display:block; height:400px">

	<div class="fs-12 Bold text-left wp95 pb20 pt10 mt20" style="margin:0 auto">Все интересы:</div>

	<div id="chart" style="padding:5px"></div>
	<script language="javascript">

		var width = $('#chart').actual('width') - 40;
		var height = 380;
		var svg = dimple.newSvg("#chart", width, height);
		var data1 = [<?=$datas?>];

		var myChart1 = new dimple.chart(svg, data1);

		myChart1.setBounds(100, 0, width - 50, height - 100);

		var x = myChart1.addCategoryAxis("x", "<?=$perName?>", "%d-%m-%Y", "%d.%m");
		x.addOrderRule("Дата");//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart1.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии
		myChart1.floatingBarWidth = 10;
		//y.ticks = 5;//шаг шкалы по оси y

		myChart1.assignColor("Закрыто", "#CFD8DC");
		myChart1.assignColor("Поступило", "#66BB6A");
		myChart1.assignColor("Обработано", "#2196F3");

		var s = myChart1.addSeries(["Интересов"], dimple.plot.bar);
		s.lineWeight = 1;
		s.lineMarkers = true;
		s.stacked = true;

		var myLegend1 = myChart1.addLegend(0, 0, width - 35, 0, "right");
		myChart1.setMargins(60, 50, 40, 80);
		myChart1.draw(1000);

		y.tickFormat = ".f";
		s.shapes.style("opacity", function (d) {
			return (d.y === null ? 0 : 0.8);
		});

		myChart1.legends = [];

		$(window).bind('resizeEnd', function () {
			myChart1.draw(0, true);
		});

	</script>
</div>

<div id="graf2" class="div-center" style="display:block; height:400px">

	<div class="fs-12 Bold text-left wp95 pb20 pt10 mt20" style="margin:0 auto">Результаты обработки:</div>

	<div id="chart2" style="padding:5px"></div>
	<script language="javascript">

		var width = $('#chart2').actual('width') - 40;
		if (width < 1) width = 600;
		var height = 380;
		var svg = dimple.newSvg("#chart2", width, height);
		var data2 = [<?=$datac?>];

		var myChart2 = new dimple.chart(svg, data2);

		myChart2.setBounds(100, 0, width - 50, height - 100);

		var x = myChart2.addCategoryAxis("x", "<?=$perName?>", "%d-%m-%Y", "%d.%m");
		x.addOrderRule("Дата");//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart2.addMeasureAxis("y", "Кол-во");
		y.showGridlines = false;//скрываем линии
		y.tickFormat = ",.f";
		//y.ticks = 5;//шаг шкалы по оси y

		myChart2.assignColor("Спам", "#37474F");
		myChart2.assignColor("Дубль", "#607D8B");
		myChart2.assignColor("Не целевой", "#90A4AE");
		myChart2.assignColor("Другое", "#CFD8DC");
		myChart2.assignColor("Сделок", "#66BB6A");

		var s = myChart2.addSeries(["Результат"], dimple.plot.bar);
		s.lineWeight = 1;
		s.lineMarkers = true;
		s.stacked = true;
		s.barGap = 0.2;

		var myLegend2 = myChart2.addLegend(0, 0, width - 35, 0, "right");

		myChart2.setMargins(60, 50, 40, 80);
		myChart2.floatingBarWidth = 0.5;

		myChart2.draw(1000);

		myChart2.legends = [];
		s.shapes.style("opacity", function (d) {
			return (d.y === null ? 0 : 0.8);
		});

		$(window).bind('resizeEnd', function () {
			myChart2.draw(0, true);
		});

	</script>
</div>

<div style="height:50px"></div>

<script>

	$('#userdata tbody').find('td').not('.text').each(function () {

		var text = $(this).html();
		if (parseFloat(text) == 0) $(this).addClass('gray');

	});

</script>
