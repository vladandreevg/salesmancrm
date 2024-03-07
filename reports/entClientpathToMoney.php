<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

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

$action = $_REQUEST[ 'action' ];
$da1    = $_REQUEST[ 'da1' ];
$da2    = $_REQUEST[ 'da2' ];

$users  = (array)$_REQUEST[ 'user_list' ];
$cPath  = (array)$_REQUEST[ 'clientPath' ];

$thisfile = basename( $_SERVER[ 'PHP_SELF' ] );
$prefix   = $_SERVER[ 'DOCUMENT_ROOT' ]."/";

//Формируем доп.параметры запроса
$sd = get_people( $iduser1, 'yes' );

$sort = '';

$colors = [
	"#222",
	"#f44336",
	"#FF9800",
	"#FFEB3B",
	"#4CAF50",
	"#2196F3",
	"#3F51B5",
	"#673AB7",
	"#E91E63",
	"#A1887F",
	"#FFC107",
	"#8BC34A",
	"#00BCD4",
	"#795548",
	"#7CB342",
	"#0097A7",
	"#D500F9",
	"#76FF03",
	"#DD2C00",
	"#03A9F4",
	"#B0BEC5",
	"#90A4AE",
	"#78909C",
	"#607D8B",
	"#9E9E9E",
	"#6D4C41"
];

//массив каналов
$channel = $db -> getIndCol("id", "SELECT id, name FROM ".$sqlname."clientpath WHERE identity = '$identity'" );
$channel[ 0 ] = 'Не указан';

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if (!empty($user_list)) {
	$so .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//фильтр по источнику
if ( in_array( "0", $cPath ) ) $s1 = " OR cc.clientpath = ''";
if ( !empty( $cPath ) ) $sort = "(cc.clientpath IN (".implode( ",", $cPath ).") $s1) and ";

$list       = [];
$order      = [];
$chart      = [];
$charts     = [];
$channelSum = $channelBudjet = $channelCount = [];
$totalSum   = 0;

$max = 0;

if ( $action == 'view' ) {

	$path = $_REQUEST[ 'path' ];
	$date = $_REQUEST[ 'date' ];

	$format = ( abs( diffDate2( $da1, $da2 ) ) <= 62 ) ? '%d.%m' : '%m.%y';

	if ( $path == 'Не указан' ) $cp = "cc.clientpath = '' AND ";
	else $cp = "cp.name = '$path' AND";

	if ( !$otherSettings['credit'] ) {

		$q = "
		SELECT
			cp.id,
			deal.did as did,
			deal.datum_close as dclose,
			deal.kol_fact as summa,
			deal.marga as marga,
			deal.iduser as iduser,
			deal.title as dogovor,
			deal.clid as clid,
			us.title as user,
			cc.title as client,
			cp.name as path
		FROM ".$sqlname."dogovor `deal`
			LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
			LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
			LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
		WHERE
			deal.did > 0 and
			COALESCE(deal.close, 'no') = 'yes' and
			deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			DATE_FORMAT(deal.datum_close, '$format') = '$date' and
			$cp
			$so
			deal.identity = '$identity'
		ORDER BY us.title, deal.datum_close
		";

		$rez = $db -> query( $q );
		while ( $daz = $db -> fetch( $rez ) ) {

			if ( $daz[ 'path' ] == NULL ) $daz[ 'path' ] = 'Не указан';

			$list[] = [
				"did"     => $daz[ 'did' ],
				"dogovor" => $daz[ 'dogovor' ],
				"dclose"  => format_date_rus( $daz[ 'dclose' ] ),
				"summa"   => $daz[ 'summa' ],
				"marga"   => $daz[ 'marga' ],
				"clid"    => $daz[ 'clid' ],
				"client"  => $daz[ 'client' ],
				"user"    => $daz[ 'user' ],
				"path"    => $daz[ 'path' ],
				"comment" => 'Закрытая сделка'
			];

		}

	}

	if ( $otherSettings['credit'] ) {

		//выполнение планов по оплатам
		if ( !$otherSettings['planByClosed'] ) {

			$q = "
			SELECT
				cp.id,
				cr.did as did,
				cr.iduser as iduser,
				SUM(cr.summa_credit) as summa,
				cr.invoice_date as dclose,
				deal.title as dogovor,
				deal.kol as dsumma,
				deal.marga as dmarga,
				deal.iduser as diduser,
				COALESCE(deal.close, 'no') as close,
				deal.datum_close as ddclose,
				deal.clid as clid,
				us.title as user,
				cc.title as client,
				cp.name as path
			FROM ".$sqlname."credit `cr`
				LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
				LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
				LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
				LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
			WHERE
				cr.do = 'on' and
				cr.invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
				DATE_FORMAT(cr.invoice_date, '$format') = '$date' and
				$cp
				".str_replace( "deal", "cr", $so )."
				cr.identity = '$identity'
			GROUP BY deal.did
			ORDER by cr.invoice_date";
		}

		//выполнение учет только оплат по закрытым сделкам в указанном периоде
		if ( $otherSettings['planByClosed'] ) {

			$q = "
			SELECT
				cp.id,
				cr.did as did,
				cr.iduser as iduser,
				SUM(cr.summa_credit) as summa,
				deal.title as dogovor,
				deal.kol as dsumma,
				deal.marga as dmarga,
				deal.iduser as diduser,
				COALESCE(deal.close, 'no') as close,
				deal.datum_close as dclose,
				deal.clid as clid,
				us.title as user,
				cc.title as client,
				cp.name as path
			FROM ".$sqlname."credit `cr`
				LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
				LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
				LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
				LEFT JOIN ".$sqlname."user `us` ON deal.iduser = us.iduser
			WHERE
				cr.do = 'on' and
				COALESCE(deal.close, 'no') = 'yes' and
				deal.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
				DATE_FORMAT(deal.datum_close, '$format') = '$date' and
				$cp
				$so
				cr.identity = '$identity'
			GROUP BY deal.did
			ORDER by cr.invoice_date";

		}

		$rez = $db -> query( $q );
		while ( $daz = $db -> fetch( $rez ) ) {

			if ( $daz[ 'path' ] == NULL ) $daz[ 'path' ] = 'Не указан';

			if ( $daz[ 'dclose' ] == '0000-00-00' ) $daz[ 'dclose' ] = '-';
			else $daz[ 'dclose' ] = format_date_rus( $daz[ 'dclose' ] );

			$marga = $daz[ 'dmarga' ] / $daz[ 'dsumma' ] * $daz[ 'summa' ];
			$proc  = $daz[ 'summa' ] / $daz[ 'dsumma' ] * 100;

			if ( $proc < 100 ) $color = "red";
			else $color = "green";

			$comment = 'Оплачено <b>'.num_format( $daz[ 'summa' ] ).'</b><br>Оплачено: <b class="'.$color.'">'.num_format( $proc ).'%</b>';

			$list[] = [
				"did"     => $daz[ 'did' ],
				"dogovor" => $daz[ 'dogovor' ],
				"dclose"  => $daz[ 'dclose' ],
				"dsumma"  => $daz[ 'dsumma' ],
				"summa"   => $daz[ 'summa' ],
				"marga"   => $marga,
				"clid"    => $daz[ 'clid' ],
				"client"  => $daz[ 'client' ],
				"user"    => $daz[ 'user' ],
				"path"    => $daz[ 'path' ],
				"comment" => $comment
			];

		}

	}

	print json_encode_cyr( $list );

	exit();

}

$da1_array = explode( "-", $da1 );
$da2_array = explode( "-", $da2 );
$dstart    = mktime( 0, 0, 0, $da1_array[ 1 ], $da1_array[ 2 ], $da1_array[ 0 ] );
$dend      = mktime( 23, 59, 59, $da2_array[ 1 ], $da2_array[ 2 ], $da2_array[ 0 ] );
$step      = 86400;
$day       = (int)( ( $dend - $dstart ) / $step ) + 1;

$dat = $dstart;//стартовое значение даты


if ( abs( diffDate2( $da1, $da2 ) ) <= 62 ) {

	$perName = 'День';

	for ( $d = 0; $d < $day; $d++ ) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date( 'Y-m-d', $dat );
		$date  = date( 'd.m', $dat );

		foreach ( $channel as $path => $name ) {

			$chart[ $date ][ $path ] = 0;

			$budjet = $db -> getOne( "SELECT SUM(summa) FROM ".$sqlname."budjet WHERE cat IN (SELECT id FROM ".$sqlname."budjet_cat WHERE clientpath = '$path' and identity = '$identity') and year = '".get_year( $datum )."' and mon = '".getMonth( $datum )."' and `do` = 'on' and identity = '$identity'" );

			$channelBudjet[ $path ][ $date ] += $budjet;

		}

		//расчет по закрытым сделкам. график платежей не включен
		if ( !$otherSettings['credit'] ) {

			$q = "
			SELECT
				IFNULL(cp.id, 0) as id,
				IFNULL(cp.name, 'Не указано') as path,
				deal.did,
				deal.kol_fact as summa,
				deal.marga as marga,
				DATE_FORMAT(deal.datum_close, '%d.%m') as date
			FROM ".$sqlname."dogovor `deal`
				LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
				LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
			WHERE
				deal.did > 0 and
				COALESCE(deal.close, 'no') = 'yes' and
				deal.datum_close = '$datum' and
				$so
				$sort
				deal.identity = '$identity'
			-- GROUP BY ".$sqlname."clientpath.id
			ORDER BY deal.datum_close
			";

			//print "<br><br>".$q."<br><br>";

			$rez = $db -> query( $q );
			while ( $daz = $db -> fetch( $rez ) ) {

				//if ($daz['id'] == null) $daz['id'] = '0';

				$chart[ $daz[ 'date' ] ][ $daz[ 'id' ] ] += $daz[ 'summa' ];

				$order[ $daz[ 'date' ] ] = '"'.$daz[ 'date' ].'"';

				$channelSum[ $daz[ 'id' ] ][ 'summa' ] += $daz[ 'summa' ];
				$channelSum[ $daz[ 'id' ] ][ 'count' ] += 1;
				$channelCount[ $daz[ 'id' ] ][ $daz[ 'date' ] ]++;

				$totalSum += $daz[ 'summa' ];

				//print "datum = ".$datum."<br>";
				//print "sum = ".$daz['summa']."<br>===============<br>";

			}

		}

		//по оплатам. график платежей включен
		if ( $otherSettings['credit'] ) {

			//выполнение планов по оплатам
			if ( !$otherSettings['planByClosed'] ) {

				$q = "
				SELECT
					IFNULL(cp.id, 0) as id,
					IFNULL(cp.name, 'Не указано') as path,
					cr.did as did,
					DATE_FORMAT(cr.invoice_date, '%d.%m') as date,
					cr.summa_credit as summa,
					deal.kol as dsumma,
					deal.marga as dmarga
				FROM ".$sqlname."credit `cr`
					LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
					LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
					LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
				WHERE
					cr.do = 'on' and
					cr.invoice_date = '$datum' and
					".str_replace( "deal", "cr", $so )."
					$sort
					cr.identity = '$identity'
				ORDER BY cr.invoice_date
				";

			}

			//выполнение учет только оплат по закрытым сделкам в указанном периоде
			if ( $otherSettings['planByClosed'] ) {

				$q = "
				SELECT
					IFNULL(cp.id, 0) as id,
					IFNULL(cp.name, 'Не указано') as path,
					cr.did as did,
					cr.summa_credit as summa,
					DATE_FORMAT(deal.datum_close, '%d.%m') as date,
					deal.title as dogovor,
					deal.kol as dsumma,
					deal.marga as dmarga
				FROM ".$sqlname."credit `cr`
					LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
					LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
					LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
				WHERE
					cr.do = 'on' and
					COALESCE(deal.close, 'no') = 'yes' and
					deal.kol_fact > 0 and
					deal.datum_close = '$datum' and
					$so
					$sort
					cr.identity = '$identity'
				ORDER BY cr.invoice_date
				";

			}

			//print "<br><br>".$q."<br><br>";

			//проходим все сделки и считаем по нима сумму оплат и маржу
			$dids = [];
			$rez  = $db -> query( $q );
			while ( $daz = $db -> fetch( $rez ) ) {

				$budjet = 0;

				$chart[ $daz[ 'date' ] ][ $daz[ 'id' ] ] += $daz[ 'summa' ];

				$order[ $daz[ 'date' ] ] = '"'.$daz[ 'date' ].'"';

				$channelSum[ $daz[ 'id' ] ][ 'summa' ] += $daz[ 'summa' ];
				$channelSum[ $daz[ 'id' ] ][ 'count' ] += 1;

				$totalSum += $daz[ 'summa' ];

				if ( !in_array( $daz[ 'did' ], $dids, true ) ) $channelCount[ $daz[ 'id' ] ][ $daz[ 'date' ] ]++;

				$dids[] = $daz[ 'did' ];

			}

		}

	}

	foreach ( $chart as $date => $value ) {

		foreach ( $value as $path => $summa ) {

			$charts[] = '{"Источник":"'.$channel[ $path ].'", "Сумма":"'.pre_format( $summa ).'","'.$perName.'":"'.$date.'"}';

		}

	}

}
else {

	$perName = 'Месяц';

	//количество месяцев
	$monStart  = (int)getMonth( $da1 );
	$yearStart = (int)get_year( $da1 );

	$monEnd  = (int)getMonth( $da2 ) + 1;
	$yearEnd = (int)get_year( $da2 );

	$mon  = $monStart;
	$year = $yearStart;

	$dataYear = [];

	while ( $year <= $yearEnd ) {

		while ( $mon <= 12 ) {

			$mon1 = ( $mon < 10 ) ? "0".$mon : $mon;

			$datum = $year."-".$mon1;
			$date  = $mon1.".".$year;

			foreach ( $channel as $path => $name ) {

				$chart[ $mon1.'.'.substr( $year, 2, 3 ) ][ $path ] = 0;

				$budjet = $db -> getOne( "SELECT SUM(summa) FROM ".$sqlname."budjet WHERE cat IN (SELECT id FROM ".$sqlname."budjet_cat WHERE clientpath = '$path' and identity = '$identity') and year = '".get_year( $datum )."' and mon = '".getMonth( $datum )."' and do = 'on' and identity = '$identity'" ) + 0;

				$channelBudjet[ $path ][ $mon1.'.'.substr( $year, 2, 3 ) ] += $budjet;

			}

			//расчет по закрытым сделкам. график платежей не включен
			if ( !$otherSettings['credit'] ) {

				$q = "
				SELECT
					IFNULL(cp.id, 0) as id,
					IFNULL(cp.name, 'Не указано') as path,
					deal.kol_fact as summa,
					deal.marga as marga,
					DATE_FORMAT(deal.datum_close, '%m.%y') as date
				FROM ".$sqlname."dogovor `deal`
					LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
					LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
				WHERE
					deal.did > 0 and
					COALESCE(deal.close, 'no') = 'yes' and
					date_format(deal.datum_close, '%Y-%m') = '".$datum."' and
					$so
					$sort
					deal.identity = '$identity'
				-- GROUP BY ".$sqlname."clientpath.id
				ORDER BY deal.datum_close
				";

				$rez = $db -> query( $q );
				while ( $daz = $db -> fetch( $rez ) ) {

					$chart[ $daz[ 'date' ] ][ $daz[ 'id' ] ] += $daz[ 'summa' ];

					$channelSum[ $daz[ 'id' ] ][ 'summa' ] += $daz[ 'summa' ];
					$channelSum[ $daz[ 'id' ] ][ 'count' ] += 1;
					$channelCount[ $daz[ 'id' ] ][ $daz[ 'date' ] ]++;

					$dataYear[ $year ][ $daz[ 'id' ] ] += $daz[ 'summa' ];

				}

			}

			//по оплатам. график платежей включен
			if ( $otherSettings['credit'] ) {

				//выполнение планов по оплатам
				if ( !$otherSettings['planByClosed'] ) {

					$q = "
					SELECT
						IFNULL(cp.id, 0) as id,
						IFNULL(cp.name, 'Не указано') as path,
						cr.did as did,
						DATE_FORMAT(cr.invoice_date, '%m.%y') as date,
						SUM(cr.summa_credit) as summa,
						deal.kol as dsumma,
						deal.marga as dmarga
					FROM ".$sqlname."credit `cr`
						LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
						LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
						LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
					WHERE
						cr.do = 'on' and
						date_format(cr.invoice_date, '%Y-%m') = '".$datum."' and
						".str_replace( "deal", "cr", $so )."
						$sort
						cr.identity = '$identity'
					GROUP BY cr.did
					ORDER BY cr.invoice_date
					";

				}

				//выполнение учет только оплат по закрытым сделкам в указанном периоде
				if ( $otherSettings['planByClosed'] ) {

					$q = "
					SELECT
						IFNULL(cp.id, 0) as id,
						IFNULL(cp.name, 'Не указано') as path,
						cr.did as did,
						cr.summa_credit as summa,
						DATE_FORMAT(deal.datum_close, '%m.%y') as date,
						deal.title as dogovor,
						deal.kol as dsumma,
						deal.marga as dmarga
					FROM ".$sqlname."credit `cr`
						LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
						LEFT JOIN ".$sqlname."clientcat `cc` ON deal.clid = cc.clid
						LEFT JOIN ".$sqlname."clientpath `cp` ON cp.id = cc.clientpath
					WHERE
						cr.do = 'on' and
						deal.close = 'yes' and
						cr.did > 0 and
						date_format(deal.datum_close, '%Y-%m') = '".$datum."' and
						$so
						$sort
						cr.identity = '$identity'
					ORDER BY deal.datum_close
					";

				}

				//print "<br><br>".$q."<br><br>";

				//проходим все сделки и считаем по нима сумму оплат и маржу
				$dids = [];
				$rez  = $db -> query( $q );
				while ( $daz = $db -> fetch( $rez ) ) {

					$chart[ $daz[ 'date' ] ][ $daz[ 'id' ] ] += $daz[ 'summa' ];

					$channelSum[ $daz[ 'id' ] ][ 'summa' ] += $daz[ 'summa' ];
					$channelSum[ $daz[ 'id' ] ][ 'count' ] += $daz[ 'count' ];

					if ( !in_array( $daz[ 'did' ], $dids ) ) $channelCount[ $daz[ 'id' ] ][ $daz[ 'date' ] ]++;

					$dids[] = $daz[ 'did' ];

					$dataYear[ $year ][ $daz[ 'id' ] ] += $daz[ 'summa' ];

				}

				//print "datum = ".$datum."<br>===============<br>";

			}

			if ( $year == $yearEnd && $mon == $monEnd ) goto endo;

			//$m = ($mon < 10) ? "0".$mon : $mon;

			$order[ $mon1.'.'.substr( $year, 2, 3 ) ] = '"'.$mon1.'.'.substr( $year, 2, 3 ).'"';

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

	foreach ( $chart as $date => $value ) {

		foreach ( $value as $path => $summa ) {

			$charts[] = '{"Источник":"'.$channel[ $path ].'", "Сумма":"'.pre_format( $summa ).'","'.$perName.'":"'.$date.'"}';

		}

	}

}

/**
 * Сумма по всем каналам
 */
$total = arraysum( $channelSum, 'summa' )."<br>";

/**
 * Наиболее прибыльный канал (объект)
 */
$maxChannelVal = arrayMax( $channelSum, 'summa' );

/**
 * Самый слабый канал
 */
$minChannelVal = arrayMin( $channelSum, 'summa' );

//max значение
//$maxChannelVal -> max;

//канал с максимальным значением
$indexMax = $maxChannelVal -> index;
$indexMin = $minChannelVal -> index;

$charts = implode( ",", $charts );
$orders = implode( ",", $order );

//print_r($channelBudjet);

if ( !$otherSettings['credit'] ) $text = '<li>В отчет попадают ВСЕ <b>активные</b> сделки и <b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем</li>';
if ( $otherSettings['credit'] && !$otherSettings['planByClosed'] ) $text = '<li>Расчеты строятся по <b>оплаченным счетам в периоде</b> в соответствии с настройками системы</li>';
if ( $otherSettings['credit'] && $otherSettings['planByClosed'] ) $text = '<li>Расчеты строятся по <b>оплаченным счетам</b> в Сделках, <b>закрытых в отчетном периоде</b> в соответствии с настройками системы</li>';
?>

<style>
	<!--
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

	.td--main {
		height : 45px;
		cursor : pointer;
	}

	.td--main:hover {
		background : rgba(197, 225, 165, 1);
	}

	.fs-16 {
		font-size : 1.6em
	}

	table.borderer thead tr th,
	table.borderer tr,
	table.borderer td {
		border-left   : 1px dotted #ccc !important;
		border-bottom : 1px dotted #ccc !important;
		padding       : 2px 3px 2px 3px;
		height        : 30px;
		white-space   : nowrap;
	}

	table.borderer thead th:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead tr:first-child th {
		border-top : 1px dotted #ccc !important;
	}

	table.borderer td:last-child {
		border-right : 1px dotted #ccc !important;
	}

	table.borderer thead {
		border : 1px dotted #ccc !important;
	}

	table.borderer thead td,
	table.borderer thead th {
		background : #E5F0F9;
	}

	table.borderer thead th {
		border-bottom : 1px dotted #666 !important;
	}

	table.borderer thead {
		border : 1px dotted #222 !important;
	}

	table.borderer td i {
		z-index  : 0;
		position : inherit;
	}

	.colorit {
		display       : block;
		height        : 25px;
		line-height   : 25px;
		padding-right : 5px;
	}

	.path {
		background    : #E5F0F9;
		padding-left  : 5px;
		padding-right : 5px;
	}

	tr.ha:hover > .path {
		background : rgba(197, 225, 165, 1) !important;
	}

	.colorit.color1 {
		background : rgba(41, 128, 185, 0.1);
		color      : #222;
	}

	.colorit.color2 {
		background : rgba(41, 128, 185, 0.2);
		color      : #222;
	}

	.colorit.color3 {
		background : rgba(41, 128, 185, 0.3);
		color      : #222;
	}

	.colorit.color4 {
		background : rgba(41, 128, 185, 0.4);
		color      : #222;
	}

	.colorit.color5 {
		background : rgba(41, 128, 185, 0.5);
		color      : #FFF;
	}

	.colorit.color6 {
		background : rgba(41, 128, 185, 0.6);
		color      : #FFF;
	}

	.colorit.color7 {
		background : rgba(41, 128, 185, 0.7);
		color      : #FFF;
	}

	.colorit.color8 {
		background : rgba(41, 128, 185, 0.8);
		color      : #FFF;
	}

	.colorit.color9 {
		background : rgba(41, 128, 185, 0.9);
		color      : #FFF;
	}

	.colorit.color10 {
		background : rgba(41, 128, 185, 1.0);
		color      : #FFF;
	}

	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	.greenbgg {
		/*background : rgba(197,225,165 ,1);*/
		background : rgba(245, 245, 245, 1);
		border     : 0;
	}

	.redbgg {
		/*background : rgba(239,154,154 ,1);*/
		background : rgba(245, 245, 245, 1);
		border     : 0;
	}

	.viget-micros {
		position              : relative;
		margin                : 10px 5px 5px;
		padding               : 5px 10px 5px 5px;
		border-radius         : 2px;
		-moz-border-radius    : 2px;
		-webkit-border-radius : 2px;
		box-sizing            : border-box;
		-moz-box-sizing       : border-box;
		-webkit-box-sizing    : border-box;
		box-shadow            : 0 1px 2px 1px rgba(144, 164, 174, 1);
		-webkit-box-shadow    : 0 1px 2px 1px rgba(144, 164, 174, 1);
		-moz-box-shadow       : 0 1px 2px 1px rgba(144, 164, 174, 1);
	}

	-->
</style>

<div class="zagolovok_rep text-center">
	<h2>Эффективность каналов в деньгах</h2>
	<div class="fs-10">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<table class="noborder">
	<tr>
		<td class="wp20">
			<?php
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name" );
			if ( count( $cPath ) == 0 ) {

				$cPath   = $db -> getCol( "SELECT id FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name" );
				$cPath[] = "0";

			}
			?>
			<div class="ydropDown">
				<span>По Источнику</span><span class="ydropCount"><?= count( $cPath ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
				<div class="yselectBox" style="max-height: 300px;">
					<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
					<div class="ydropString ellipsis">
						<label>
							<input class="taskss" name="clientPath[]" type="checkbox" id="clientPath[]" value="0" <?php if ( in_array( "0", $cPath ) ) print "checked"; ?>>&nbsp;Не указан
						</label>
					</div>
					<?php
					foreach ( $result as $data ) {

						$s = ( in_array( $data[ 'id' ], $cPath ) ) ? 'checked' : '';
						?>
						<div class="ydropString ellipsis">
							<label>
								<input class="taskss" name="clientPath[]" type="checkbox" id="clientPath[]" value="<?= $data[ 'id' ] ?>" <?= $s ?>>&nbsp;<?= $data[ 'name' ] ?>
							</label>
						</div>
					<?php } ?>
				</div>
			</div>
		</td>
		<td class="wp20"></td>
		<td class="wp20"></td>
		<td class="wp20"></td>
	</tr>
</table>

<hr>

<div class="flex-container box--child">

	<div class="flex-string wp35 viget-micros greenbgg div-center">

		<div class="gray fs-11 Bold pt5 uppercase">Эффективный канал</div>
		<div class="green fs-20 mt15 Bold mb20"><?= $channel[ $indexMax ] ?>
			<sup class="fs-05 gray2">[<?= ( (float)$total > 0 ? round( ( $channelSum[ $indexMax ][ 'summa' ] / $total * 100 ), 1 ) : 0 ) ?>%]</sup>
		</div>

		<hr>

		<div class="row">

			<div class="column grid-5 border-box">

				<div class="fs-16 Bold mb5"><?= num_format( $maxChannelVal -> max ).' '.$valuta ?></div>
				<div class="gray fs-09 Bold uppercase">Сумма канала</div>

			</div>

			<div class="column grid-5 border-box">

				<div class="fs-16 Bold mb5"><?= num_format( arraySumma( $channelBudjet[ $indexMax ] ) ).' '.$valuta ?>
					<sup class="fs-05 gray2">[<?= ( (float)arraySumma( $channelBudjet ) > 0 ? round( ( arraySumma( $channelBudjet[ $indexMax ] ) / arraySumma( $channelBudjet ) * 100 ), 1 ) : 0 ) ?>%]</sup>
				</div>
				<div class="gray fs-09 Bold uppercase">Затраты на канал</div>

			</div>

		</div>

	</div>

	<div class="flex-string wp35 viget-micros redbgg div-center">

		<div class="gray fs-11 Bold pt5 uppercase">Слабый канал</div>
		<div class="red fs-20 mt15 Bold mb20"><?= $channel[ $minChannelVal -> index ] ?>
			<sup class="fs-05 gray2">[<?= ( (float)$total > 0 ? round( ( $channelSum[ $indexMin ][ 'summa' ] / $total * 100 ), 1 ) : 0 ) ?>%]</sup>
		</div>

		<hr>

		<div class="row">

			<div class="column grid-5 border-box">

				<div class="fs-16 Bold mb5"><?= num_format( $minChannelVal -> min ).' '.$valuta ?></div>
				<div class="gray fs-09 Bold uppercase">Сумма канала</div>

			</div>

			<div class="column grid-5 border-box">

				<div class="fs-16 Bold mb5"><?= num_format( arraySumma( $channelBudjet[ $indexMin ] ) ).' '.$valuta ?>
					<sup class="fs-05 gray2">[<?= ( arraySumma( $channelBudjet ) > 0 ? round( ( arraySumma( $channelBudjet[ $indexMin ] ) / arraySumma( $channelBudjet ) * 100 ), 1 ) : 0 ) ?>%]</sup>
				</div>
				<div class="gray fs-09 Bold uppercase">Затраты на канал</div>

			</div>

		</div>

	</div>

</div>

<div id="graf" style="display:block; height:300px">

	<div id="chart" style="padding:5px; height:100%"></div>

</div>

<div class="fs-14 mt20 pad10 Bold">Данные к графику</div>

<div class="block mb20 wp99" style="max-height: 70vh;">

	<table id="pathdata" class="borderer wp100">
		<thead>
		<tr class="bordered">
			<th class="w100 text-center" rowspan="2">Период</th>
			<?php
			$i = 0;
			foreach ( $channel as $path => $name ) {
				?>
				<th class="w80 text-left" data-path="<?= $path ?>">
					<div class="div-center fs-20 black mt10 mb10"><?= ( (float)$total > 0 ? round( $channelSum[ $path ][ 'summa' ] / $total * 100, 1 ) : 0 ) ?>%</div>
					<div class="div-center fs-10" title="Сумма"><?= num_format( $channelSum[ $path ][ 'summa' ] ) ?></div>
					<div class="div-center fs-10" title="Количество сделок">
						<i class="icon-briefcase"></i>&nbsp;<?= array_sum( (array)$channelCount[ $path ] ) ?>
					</div>
					<?php
					//print_r($channelSum[ $path ]);
					?>
				</th>
				<?php
				$i++;
			}
			?>
		</tr>
		<tr class="bordered">
			<?php
			$i = 0;
			foreach ( $channel as $path => $name ) {
				?>
				<th class="w80 text-left" data-path="<?= $path ?>">
					<div class="div-center">
						<div class="bullet" style="background: <?= $colors[ $i ] ?>"></div>
					</div>
					<div class="div-center" title="<?= $name ?>"><?= $name ?></div>
				</th>
				<?php
				$i++;
			}
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $chart as $date => $value ) {

			print '
			<tr class="ha fs-09">
				<td class="w100 text path" title="'.$date.'">
					<div class="ellipsis1 pl5"><b>'.$date.'</b></div>
				</td>
			';

			foreach ( $channel as $path => $name ) {

				$act  = ( $chart[ $date ][ $path ] > 0 ) ? ' onclick="showData(\''.$name.'\', \''.$date.'\')"' : '';
				$hand = ( $chart[ $date ][ $path ] > 0 ) ? 'hand' : '';

				print '
				<td class="w80 text-right ha '.$hand.'" '.$act.' data-path="'.$path.'">
					<div class="colorit fs-11 Bold">
						'.num_format( $chart[ $date ][ $path ] ).'
					</div>
					<div class="fs-09 pr5" title="Число сделок">
						<i class="icon-briefcase gray fs-05"></i>'.( $channelCount[ $path ][ $date ] + 0 ).'
					</div>
					<div class="fs-07 pr5 gray2" title="Расходы на канал"><i class="icon-rouble gray fs-05"></i>'.num_format( $channelBudjet[ $path ][ $date ] ).'</div>
				</td>
				';

				if ( $chart[ $date ][ $path ] > $max ) $max = $chart[ $date ][ $path ];

			}

			print '</tr>';

		}
		?>
		</tbody>
	</table>

</div>

<div class="data hidden">

	<hr>

	<div class="fs-14 pad10 Bold">Детализация по каналу "<span class="channel red"></span>" на
		<span class="date blue"></span>:
	</div>

	<table>
		<thead>
		<tr>
			<th class="w30">#</th>
			<th class="yw200">Сделка / Клиент</th>
			<th class="w100">Канал</th>
			<th class="w100">Маржа</th>
			<th class="w140">Сумма</th>
			<th class="w80">Дата</th>
			<th class="w100">Ответственный</th>
			<th class="">Примечание</th>
		</tr>
		</thead>
		<tbody></tbody>
		<tfoot></tfoot>
	</table>

</div>

<hr>

<div class="pad10 infodiv">

	<h2>Об отчете</h2>

	<div>
		<p>Усовершенствованный отчет по Каналам продаж (Источник клиента) позволяет провести анализ дохода, полученного с каждого Канала.
			Однако следует учитывать, что средства, вложенные в рекламу дают отдачу не сразу. Т.е. если рекламная компания была оплачена в январе 2017 года, то продажи не всегда могут быть произведены именно в этом периоде, а позже.</p>
		<p>Поэтому следует анализировать более широкие периоды времени и смотреть на валовый доход в целом по каналу, а не по конкретной дате.</p>
	</div>

	<h3>Примечания</h3>

	<ul>
		<?= $text ?>
		<li>Если выбранный период меньше 62 дней (2 месяца), то данные будут выданы <b>на каждый день</b></li>
		<li>Если выбранный период больше 62 дней (2 месяца), то данные будут выданы <b>помесячно</b></li>
		<li>Если Канал связан с расходами (Модуль "Бюджет"), то в третьей строке буде указана сумма расходов на канал</li>
		<li>Получить список сделок по конкретному Каналу на определенную дату можно кликнув по нужной ячейке таблицы</li>
	</ul>

</div>

<div class="h35"></div>

<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script src="/assets/js/dimple.js/dimple.min.js"></script>
<!--<script src="/assets/js/d3.min.js"></script>-->
<script>

	max = '<?=$max?>';

	$(function () {

		drowChart();

		$("#pathdata").tableHeadFixer({
			'head': true,
			'foot': false,
			'z-index': 12000,
			'left': 1
		}).css('z-index', '100');

		$(".colorit").each(function () {

			var num = $(this).html().replace(/ /g, '').replace(",", ".");

			if (num == 0) $(this).css({'color': '#CCC'});
			else if (num <= 0.1 * max) $(this).addClass('color1');
			else if (num <= 0.2 * max) $(this).addClass('color2');
			else if (num <= 0.3 * max) $(this).addClass('color3');
			else if (num <= 0.4 * max) $(this).addClass('color4');
			else if (num <= 0.5 * max) $(this).addClass('color5');
			else if (num <= 0.6 * max) $(this).addClass('color6');
			else if (num <= 0.7 * max) $(this).addClass('color7');
			else if (num <= 0.8 * max) $(this).addClass('color8');
			else if (num <= 0.9 * max) $(this).addClass('color9');
			else $(this).addClass('color10');

			$('td[data-path="<?=$maxChannelVal -> index?>"]').css({'background-color': 'rgba(255,249,196 ,.8)'});
			$('th[data-path="<?=$maxChannelVal -> index?>"]').css({'background-color': 'rgba(255,236,179 ,1)'});

		});

	});


	$('#pathdata tbody').find('td').not('.text').each(function () {

		var text = $(this).html();

		if (parseFloat(text) === 0)
			$(this).addClass('gray');

	});

	function drowChart() {

		$('#chart').empty();

		var width = $('#chart').width() - 200;
		var height = 400;
		var svg = dimple.newSvg("#chart", "100%", "100%");
		var data = [<?=$charts?>];

		var myChart = new dimple.chart(svg, data);

		myChart.setBounds(0, 0, width - 50, height - 40);

		var x = myChart.addCategoryAxis("x", ["<?=$perName?>"]);
		x.addOrderRule([<?=$orders?>]);//порядок вывода, иначе группирует
		x.showGridlines = true;

		var y = myChart.addMeasureAxis("y", "Сумма");
		y.showGridlines = false;//скрываем линии
		y.tickFormat = ",.2f";

		var z = myChart.addMeasureAxis("z", "Источник");

		myChart.defaultColors = [
			new dimple.color("#F44336", "#F44336"),
			new dimple.color("#FF9800", "#FF9800"),
			new dimple.color("#FFEB3B", "#FFEB3B"),
			new dimple.color("#4CAF50", "#4CAF50"),
			new dimple.color("#2196F3", "#2196F3"),
			new dimple.color("#3F51B5", "#3F51B5"),
			new dimple.color("#673AB7", "#673AB7"),
			new dimple.color("#E91E63", "#E91E63"),
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
			new dimple.color("#03A9F4", "#03A9F4"),
			new dimple.color("#B0BEC5", "#B0BEC5"),
			new dimple.color("#90A4AE", "#90A4AE"),
			new dimple.color("#78909C", "#78909C"),
			new dimple.color("#607D8B", "#607D8B"),
			new dimple.color("#9E9E9E", "#9E9E9E"),
			new dimple.color("#6D4C41", "#6D4C41")
		];

		myChart.assignColor("Не указан", "#333");

		var s = myChart.addSeries("Источник", dimple.plot.bar);

		s.barGap = 0.7;
		s.stacked = true;
		//s.lineWeight = 1;
		//s.lineMarkers = true;

		myChart.clamp = true;
		myChart.floatingBarWidth = 2;

		myChart.ease = "bounce";
		myChart.staggerDraw = true;

		var myLegend = myChart.addLegend(width + 10, 0, 100, 250, "right");
		myChart.setMargins(100, 20, 270, 50);

		s.addEventHandler("click", function (e) {
			showData(e.seriesValue[0], e.xValue);
			console.log(e);
		});

		myChart.draw(1000);

		//y.titleShape.remove();
		x.titleShape.remove();

	}

	$(window).bind('resizeEnd', function () {
		myChart1.draw(0, true);
	});

	function showData(path, date) {

		$('.data').removeClass('hidden').find('table tbody').empty().append('<img src="/assets/images/loading.gif">');
		$('.data').find('tfoot').empty();

		$('.channel').html(path);
		$('.date').html(date);

		var str = $('#selectreport').serialize();

		$.get('reports/<?=$thisfile?>?action=view&path=' + path + '&date=' + date, str, function (data) {

			var s = '';
			var f = '';
			var number = 0;
			var summa = 0;
			var marga = 0;
			var dsumma;

			for (var i in data) {

				number = parseInt(i) + 1;
				dsumma = '';

				if (data[i].dsumma != '') dsumma = '<br><em class="fs-07 gray" title="Сумма по сделке">' + number_format(data[i].dsumma, 2, ',', ' ') + '<em>';

				s = s +
					'<tr class="ha">' +
					'   <td class="text-center" class="fs-09">' + number + '</td>' +
					'   <td class="text-left"><div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor(\'' + data[i].did + '\')"><i class="icon-briefcase-1 blue"></i>&nbsp;' + data[i].dogovor + '</a></div><br><div class="ellipsis"><a href="javascript:void(0)" onclick="openClient(\'' + data[i].clid + '\')" title=""><i class="icon-building broun"></i>&nbsp;' + data[i].client + '</a></div></td>' +
					'   <td><div class="ellipsis">' + data[i].path + '</div></td>' +
					'   <td class="text-right">' + number_format(data[i].marga, 2, ',', ' ') + '</td>' +
					'   <td class="text-right"><b>' + number_format(data[i].summa, 2, ',', ' ') + '</b>' + dsumma + '</td>' +
					'   <td class="text-center">' + data[i].dclose + '</td>' +
					'   <td class="text-left"><div class="ellipsis">' + data[i].user + '</div></td>' +
					'   <td class="text-left"><span class="fs-09">' + data[i].comment + '</span></td>' +
					'</tr>';

				summa = summa + parseFloat(data[i].summa);
				marga = marga + parseFloat(data[i].marga);
			}

			f =
				'<tr class="ha bluebg-sub">' +
				'   <td class="text-center" class="fs-09"></td>' +
				'   <td class="text-left"></td>' +
				'   <td></td>' +
				'   <td class="text-right"><b>' + number_format(marga, 2, ',', ' ') + '</b></td>' +
				'   <td class="text-right"><b>' + number_format(summa, 2, ',', ' ') + '</b></td>' +
				'   <td class="text-center"></td>' +
				'   <td class="text-left"></td>' +
				'   <td class="text-left"></td>' +
				'</tr>';

			//console.log(s);

			$('.data').find('tbody').empty().html(s);
			$('.data').find('tfoot').empty().html(f);

			var $top = $('.data').offset();
			var ttop = $top.top + 50;

			$("#clientlist.nano").nanoScroller({scrollTop: ttop});

		}, 'json');

	}

	function number_format(number, decimals, dec_point, thousands_sep) {
		// Format a number with grouped thousands
		//
		// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +	 bugfix by: Michael White (http://crestidg.com)

		var i, j, kw, kd, km;

		// input sanitation & defaults
		if (isNaN(decimals = Math.abs(decimals))) {
			decimals = 2;
		}
		if (dec_point == undefined) {
			dec_point = ",";
		}
		if (thousands_sep == undefined) {
			thousands_sep = ".";
		}

		i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

		if ((j = i.length) > 3) {
			j = j % 3;
		}
		else {
			j = 0;
		}

		km = (j ? i.substr(0, j) + thousands_sep : "");
		kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
		//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
		kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


		return km + kw + kd;
	}

</script>