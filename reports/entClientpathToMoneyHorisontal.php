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

//массив каналов
$channel      = [];
$channel[ 0 ] = 'Не указан';
$sort = $so = '';

$channel = $db -> getIndCol("id", "SELECT id, name FROM {$sqlname}clientpath WHERE identity = '$identity'" );

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if (!empty($user_list)) {
	$so .= " {$sqlname}dogovor.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

//фильтр по источнику
if ( !empty( $cPath ) ) $sort = "({$sqlname}clientpath.clientpath IN (".implode( ",", $cPath ).") $s1) and ";


$list   = [];
$order  = [];
$chart  = [];
$charts = [];

$max = 0;

if ( $action == 'view' ) {

	$path = $_REQUEST[ 'path' ];
	$date = $_REQUEST[ 'date' ];

	$format = ( abs( diffDate2( $da1, $da2 ) ) <= 62 ) ? '%d.%m' : '%m.%y';

	if ( $path == 'Не указан' ) $cp = "{$sqlname}clientcat.clientpath = '' AND ";
	else $cp = "{$sqlname}clientpath.name = '$path' AND ";

	if ( !$otherSettings['credit'] ) {

		$q = "
		SELECT
			{$sqlname}clientpath.id,
			{$sqlname}dogovor.did as did,
			{$sqlname}dogovor.datum_close as dclose,
			{$sqlname}dogovor.kol_fact as summa,
			{$sqlname}dogovor.marga as marga,
			{$sqlname}dogovor.iduser as iduser,
			{$sqlname}dogovor.title as dogovor,
			{$sqlname}dogovor.clid as clid,
			{$sqlname}user.title as user,
			{$sqlname}clientcat.title as client,
			{$sqlname}clientpath.name as path
		FROM {$sqlname}dogovor
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
			LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
		WHERE
			{$sqlname}dogovor.did > 0 and
			{$sqlname}dogovor.close = 'yes' and
			{$sqlname}dogovor.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
			DATE_FORMAT({$sqlname}dogovor.datum_close, '$format') = '$date' and
			$cp
			$so
			{$sqlname}dogovor.identity = '$identity'
		ORDER BY {$sqlname}user.title, {$sqlname}dogovor.datum_close
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

	if ($otherSettings['credit'] ) {

		//выполнение планов по оплатам
		if ( !$otherSettings['planByClosed'] ) {

			$q = "
			SELECT
				{$sqlname}clientpath.id,
				{$sqlname}credit.did as did,
				{$sqlname}credit.iduser as iduser,
				SUM({$sqlname}credit.summa_credit) as summa,
				{$sqlname}credit.invoice_date as dclose,
				{$sqlname}dogovor.title as dogovor,
				{$sqlname}dogovor.kol as dsumma,
				{$sqlname}dogovor.marga as dmarga,
				{$sqlname}dogovor.iduser as diduser,
				{$sqlname}dogovor.close as close,
				{$sqlname}dogovor.datum_close as ddclose,
				{$sqlname}dogovor.clid as clid,
				{$sqlname}user.title as user,
				{$sqlname}clientcat.title as client,
				{$sqlname}clientpath.name as path
			FROM {$sqlname}credit
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
				LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
			WHERE
				{$sqlname}credit.do = 'on' and
				{$sqlname}credit.invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
				DATE_FORMAT({$sqlname}credit.invoice_date, '$format') = '$date' and
				$cp
				".str_replace( "dogovor", "credit", $so )."
				{$sqlname}credit.identity = '$identity'
			GROUP BY {$sqlname}dogovor.did
			ORDER by {$sqlname}credit.invoice_date";
		}

		//выполнение учет только оплат по закрытым сделкам в указанном периоде
		if ( $otherSettings['planByClosed'] ) {

			$q = "
			SELECT
				{$sqlname}clientpath.id,
				{$sqlname}credit.did as did,
				{$sqlname}credit.iduser as iduser,
				SUM({$sqlname}credit.summa_credit) as summa,
				{$sqlname}dogovor.title as dogovor,
				{$sqlname}dogovor.kol as dsumma,
				{$sqlname}dogovor.marga as dmarga,
				{$sqlname}dogovor.iduser as diduser,
				{$sqlname}dogovor.close as close,
				{$sqlname}dogovor.datum_close as dclose,
				{$sqlname}dogovor.clid as clid,
				{$sqlname}user.title as user,
				{$sqlname}clientcat.title as client,
				{$sqlname}clientpath.name as path
			FROM {$sqlname}credit
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
				LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
			WHERE
				{$sqlname}credit.do = 'on' and
				{$sqlname}dogovor.close = 'yes' and
				{$sqlname}dogovor.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and
				DATE_FORMAT({$sqlname}dogovor.datum_close, '$format') = '$date' and
				$cp
				$so
				{$sqlname}credit.identity = '$identity'
			GROUP BY {$sqlname}dogovor.did
			ORDER by {$sqlname}credit.invoice_date";

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
$day       = intval( ( $dend - $dstart ) / $step ) + 1;

$dat = $dstart;//стартовое значение даты


if ( abs( diffDate2( $da1, $da2 ) ) <= 62 ) {

	$perName = 'День';

	for ( $d = 0; $d < $day; $d++ ) {

		$dat   = $dstart + $d * $step;//дата в unix-формате
		$datum = date( 'Y-m-d', $dat );

		//расчет по закрытым сделкам. график платежей не включен
		if ( !$otherSettings['credit'] ) {

			$q = "
			SELECT
				{$sqlname}clientpath.id,
				SUM({$sqlname}dogovor.kol_fact) as summa,
				SUM({$sqlname}dogovor.marga) as marga,
				COUNT({$sqlname}dogovor.did) as count,
				DATE_FORMAT({$sqlname}dogovor.datum_close, '%d.%m') as date,
				{$sqlname}clientpath.name as path
			FROM {$sqlname}dogovor
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
			WHERE
				{$sqlname}dogovor.did > 0 and
				{$sqlname}dogovor.close = 'yes' and
				{$sqlname}dogovor.datum_close = '$datum' and
				$so
				$sort
				{$sqlname}dogovor.identity = '$identity'
			GROUP BY {$sqlname}clientpath.id
			ORDER BY {$sqlname}dogovor.datum_close
			";

			$rez = $db -> query( $q );
			while ( $daz = $db -> fetch( $rez ) ) {

				$chart[ $daz[ 'id' ] ][ $daz[ 'date' ] ] += $daz[ 'summa' ];

			}

		}

		//по оплатам. график платежей включен
		if ( $otherSettings['credit'] ) {

			//выполнение планов по оплатам
			if ( !$otherSettings['planByClosed'] ) {

				$q = "
				SELECT
					{$sqlname}clientpath.id,
					{$sqlname}clientpath.name as path,
					{$sqlname}credit.did as did,
					DATE_FORMAT({$sqlname}credit.invoice_date, '%d.%m') as date,
					SUM({$sqlname}credit.summa_credit) as summa,
					{$sqlname}dogovor.kol as dsumma,
					{$sqlname}dogovor.marga as dmarga
				FROM {$sqlname}credit
					LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
					LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
				WHERE
					{$sqlname}credit.do = 'on' and
					{$sqlname}credit.invoice_date = '$datum' and
					".str_replace( "dogovor", "credit", $so )."
					$sort
					{$sqlname}credit.identity = '$identity'
				GROUP BY {$sqlname}clientpath.id
				ORDER BY {$sqlname}credit.invoice_date
				";

			}

			//выполнение учет только оплат по закрытым сделкам в указанном периоде
			if ( $otherSettings['planByClosed'] ) {

				$q = "
				SELECT
					{$sqlname}clientpath.id,
					{$sqlname}clientpath.name as path,
					{$sqlname}credit.did as did,
					{$sqlname}credit.summa_credit as summa,
					DATE_FORMAT({$sqlname}credit.invoice_date, '%d.%m') as date,
					{$sqlname}dogovor.title as dogovor,
					{$sqlname}dogovor.kol as dsumma,
					{$sqlname}dogovor.marga as dmarga
				FROM {$sqlname}credit
					LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
					LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
				WHERE
					{$sqlname}credit.do = 'on' and
					{$sqlname}dogovor.close = 'yes' and
					{$sqlname}dogovor.datum_close = '$datum' and
					$so
					$sort
					{$sqlname}credit.identity = '$identity'
				ORDER BY {$sqlname}credit.invoice_date
				";

			}

			//проходим все сделки и считаем по нима сумму оплат и маржу
			$rez = $db -> query( $q );
			while ( $daz = $db -> fetch( $rez ) ) {

				if ( $daz[ 'id' ] == NULL ) $daz[ 'id' ] = '0';

				$chart[ $daz[ 'id' ] ][ $daz[ 'date' ] ] += $daz[ 'summa' ];

				$order[ $daz[ 'date' ] ] = '"'.$daz[ 'date' ].'"';

			}

		}

	}

	foreach ( $chart as $path => $value ) {

		foreach ( $value as $date => $summa ) {

			$charts[] = '{"Источник":"'.$channel[ $path ].'", "Сумма":"'.pre_format( $summa ).'","'.$perName.'":"'.$date.'"}';

		}

	}

}
else {

	$perName = 'Месяц';

	//количество месяцев
	$monStart  = getMonth( $da1 ) + 0;
	$yearStart = get_year( $da1 ) + 0;

	$monEnd  = getMonth( $da2 ) + 1;
	$yearEnd = get_year( $da2 ) + 0;

	$mon  = $monStart;
	$year = $yearStart;

	while ( $year <= $yearEnd ) {

		while ( $mon <= 12 ) {

			$mon1 = ( $mon < 10 ) ? "0".$mon : $mon;

			$datum = $year."-".$mon1;
			$date  = $mon1.".".$year;

			//расчет по закрытым сделкам. график платежей не включен
			if ( !$otherSettings['credit'] ) {

				$q = "
				SELECT
					{$sqlname}clientpath.id,
					SUM({$sqlname}dogovor.kol_fact) as summa,
					SUM({$sqlname}dogovor.marga) as marga,
					COUNT({$sqlname}dogovor.did) as count,
					DATE_FORMAT({$sqlname}dogovor.datum_close, '%m.%y') as date,
					{$sqlname}clientpath.name as path
				FROM {$sqlname}dogovor
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
					LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
				WHERE
					{$sqlname}dogovor.did > 0 and
					{$sqlname}dogovor.close = 'yes' and
					date_format({$sqlname}dogovor.datum_close, '%Y-%m') = '".$datum."' and
					$so
					$sort
					{$sqlname}dogovor.identity = '$identity'
				GROUP BY {$sqlname}clientpath.id
				ORDER BY {$sqlname}dogovor.datum_close
				";

				$rez = $db -> query( $q );
				while ( $daz = $db -> fetch( $rez ) ) {

					$chart[ $daz[ 'id' ] ][ $daz[ 'date' ] ] += $daz[ 'summa' ];

				}

			}

			//по оплатам. график платежей включен
			if ( $otherSettings['credit'] ) {

				//выполнение планов по оплатам
				if ( !$otherSettings['planByClosed'] ) {

					$q = "
					SELECT
						{$sqlname}clientpath.id,
						{$sqlname}clientpath.name as path,
						{$sqlname}credit.did as did,
						DATE_FORMAT({$sqlname}credit.invoice_date, '%m.%y') as date,
						SUM({$sqlname}credit.summa_credit) as summa,
						{$sqlname}dogovor.kol as dsumma,
						{$sqlname}dogovor.marga as dmarga
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
						LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
						LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
					WHERE
						{$sqlname}credit.do = 'on' and
						date_format({$sqlname}credit.invoice_date, '%Y-%m') = '".$datum."' and
						".str_replace( "dogovor", "credit", $so )."
						$sort
						{$sqlname}credit.identity = '$identity'
					GROUP BY {$sqlname}clientpath.id
					ORDER BY {$sqlname}credit.invoice_date
					";

				}

				//выполнение учет только оплат по закрытым сделкам в указанном периоде
				if ( $otherSettings['planByClosed'] ) {

					$q = "
					SELECT
						{$sqlname}clientpath.id,
						{$sqlname}clientpath.name as path,
						{$sqlname}credit.did as did,
						{$sqlname}credit.summa_credit as summa,
						DATE_FORMAT({$sqlname}credit.invoice_date, '%m.%y') as date,
						{$sqlname}dogovor.title as dogovor,
						{$sqlname}dogovor.kol as dsumma,
						{$sqlname}dogovor.marga as dmarga
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
						LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
						LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientpath.id = {$sqlname}clientcat.clientpath
					WHERE
						{$sqlname}credit.do = 'on' and
						{$sqlname}dogovor.close = 'yes' and
						date_format({$sqlname}dogovor.datum_close, '%Y-%m') = '".$datum."' and
						$so
						$sort
						{$sqlname}credit.identity = '$identity'
					ORDER BY {$sqlname}dogovor.datum_close
					";

				}

				//проходим все сделки и считаем по нима сумму оплат и маржу
				$rez = $db -> query( $q );
				while ( $daz = $db -> fetch( $rez ) ) {

					if ( $daz[ 'id' ] == NULL ) $daz[ 'id' ] = '0';

					$chart[ $daz[ 'id' ] ][ $daz[ 'date' ] ] += $daz[ 'summa' ];

				}

			}

			if ( $year == $yearEnd && $mon == $monEnd ) goto endo;

			$m = ( $mon < 10 ) ? "0".$mon : $mon;

			$order[ $m.'.'.substr( $year, 2, 3 ) ] = '"'.$m.'.'.substr( $year, 2, 3 ).'"';

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


	foreach ( $chart as $path => $value ) {

		foreach ( $value as $date => $summa ) {

			$charts[] = '{"Источник":"'.$channel[ $path ].'", "Сумма":"'.pre_format( $summa ).'","'.$perName.'":"'.$date.'"}';

		}

	}

}

//print_r($chart);

$charts = implode( ",", $charts );
$orders = implode( ",", $order );

if ( !$otherSettings['credit'] ) $text = '<li>В отчет попадают ВСЕ <b>активные</b> сделки и <b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем</li>';
if ( $otherSettings['credit'] && !$otherSettings['planByClosed'] ) $text = '<li>Расчеты строятся по <b>оплаченным счетам в периоде</b> в соответствии с настройками системы</li>';
if ( $otherSettings['credit'] && $otherSettings['planByClosed'] ) $text = '<li>Расчеты строятся по <b>оплаченным счетам</b> в Сделках, <b>закрытых в отчетном периоде</b> в соответствии с настройками системы</li>';
?>

	<style type="text/css">
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
		.td--sub {

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
			display      : table;
			height       : 25px;
			line-height  : 25px;
			padding-left : 5px;
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
		-->
	</style>

	<div class="zagolovok_rep" align="center">
		<h2>Эффективность каналов в деньгах</h2>
		<div class="fs-10">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
	</div>

	<table class="noborder">
		<tr>
			<td width="20%">
				<?php
				$result = $db -> getAll( "SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER BY name" );
				if ( count( $cPath ) == 0 ) {

					$cPath   = $db -> getCol( "SELECT id FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER BY name" );
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
			<td width="20%"></td>
			<td width="20%"></td>
			<td width="20%"></td>
		</tr>
	</table>

	<hr>

	<div id="graf" style="display:block; height:350px">

		<div id="chart" style="padding:5px; height:100%"></div>

	</div>

	<div class="data hidden">

		<hr>

		<div class="fs-14 pad10 Bold">Детализация по каналу "<span class="channel red"></span>" на
			<span class="date blue"></span>:
		</div>

		<table width="99.5%" border="0" align="center" cellpadding="5" cellspacing="0">
			<thead>
			<tr>
				<th class="w30">#</th>
				<th class="yw200">Сделка / Клиент</th>
				<th class="w100">Канал</th>
				<th class="w100">Маржа</th>
				<th class="w100">Сумма</th>
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

	<div class="fs-14 mt20 pad10 Bold">Данные к графику</div>

	<div class="block mb20" style="max-height: 70vh;">

		<table border="0" cellpadding="5" cellspacing="0" id="pathdata" class="borderer">
			<thead>
			<tr class="bordered">
				<th width="200" align="left">Название канала</th>
				<?php
				foreach ( $order as $i => $date ) {
					?>
					<th width="80" align="left"><?= str_replace( '"', '', $date ) ?></th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $chart as $path => $value ) {

				print '
			<tr class="ha fs-09">
				<td width="200" class="text path" title="'.$channel[ $path ].'">
					<div class="ellipsis1 pl5"><b>'.$channel[ $path ].'</b></div>
				</td>
			';

				foreach ( $order as $key => $data ) {

					print '<td width="80" align="left" class="ha fs-09"><div class="w80 colorit">'.num_format( $value[ $key ] ).'</div></td>';

					if ( $value[ $key ] > $max ) $max = $value[ $key ];

				}

				print '</tr>';
			}
			?>
			</tbody>
		</table>

	</div>

	<hr>

	<div class="pad10 infodiv">

		<ul>
			<?= $text ?>
		</ul>

	</div>

	<div class="h35"></div>
	<div class="h35"></div>
	<div class="h35"></div>

<?php
if ( count( $chart ) > 0 ) {
	?>
	<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
	<script src="/assets/js/dimple.js/dimple.min.js"></script>
	<!--<script src="/assets/js/d3.min.js"></script>-->
	<script>

		$(document).ready(function () {

			drowChart();

			$("#pathdata").tableHeadFixer({
				'head': true,
				'foot': false,
				'z-index': 12000,
				'left': 1
			}).css('z-index', '100');

			$(".colorit").each(function () {

				var num = parseInt($(this).html());

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

			});

		});


		$('#pathdata tbody').find('td').not('.text').each(function () {

			var text = $(this).html();
			if (parseFloat(text) == 0) $(this).addClass('gray');

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
						'<tr height="40" class="ha">' +
						'   <td align="center" class="fs-09">' + number + '</td>' +
						'   <td align="left"><div class="ellipsis"><a href="javascript:void(0)" onclick="viewDogovor(\'' + data[i].did + '\')"><i class="icon-briefcase-1 blue"></i>&nbsp;' + data[i].dogovor + '</a></div><br><div class="ellipsis"><a href="javascript:void(0)" onclick="openClient(\'' + data[i].clid + '\')" title=""><i class="icon-building broun"></i>&nbsp;' + data[i].client + '</a></div></td>' +
						'   <td><div class="ellipsis">' + data[i].path + '</div></td>' +
						'   <td align="right">' + number_format(data[i].marga, 2, ',', ' ') + '</td>' +
						'   <td align="right"><b>' + number_format(data[i].summa, 2, ',', ' ') + '</b>' + dsumma + '</td>' +
						'   <td align="center">' + data[i].dclose + '</td>' +
						'   <td align="left"><div class="ellipsis">' + data[i].user + '</div></td>' +
						'   <td align="left"><span class="fs-09">' + data[i].comment + '</span></td>' +
						'</tr>';

					summa = summa + parseFloat(data[i].summa);
					marga = marga + parseFloat(data[i].marga);
				}

				f =
					'<tr height="40" class="ha bluebg-sub">' +
					'   <td align="center" class="fs-09"></td>' +
					'   <td align="left"></td>' +
					'   <td></td>' +
					'   <td align="right"><b>' + number_format(marga, 2, ',', ' ') + '</b></td>' +
					'   <td align="right"><b>' + number_format(summa, 2, ',', ' ') + '</b></td>' +
					'   <td align="center"></td>' +
					'   <td align="left"></td>' +
					'   <td align="left"></td>' +
					'</tr>';

				//console.log(s);

				$('.data').find('tbody').empty().html(s);
				$('.data').find('tfoot').empty().html(f);

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
<?php } ?>