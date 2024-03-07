<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */
?>
<?php
error_reporting( E_ERROR );
//error_reporting(E_ALL);

header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_GET[ 'action' ];
$did    = $_GET[ 'did' ];

$today = date( 'Y-m-d', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 );
//день
$d1 = date( "Y-m-d", mktime( 0, 0, 1, date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 ).' 00:00:00';
$d2 = date( "Y-m-d", mktime( 23, 59, 59, date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 ).' 23:59:59';
//неделя
$n1 = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) - 7, date( 'Y' ) ) + $tm * 3600 ).' 00:00:00';
$n2 = date( 'Y-m-d', mktime( 0, 0, 0, date( 'm' ), date( 'd' ) + 1, date( 'Y' ) ) + $tm * 3600 ).' 23:59:59';
//месяц
$m  = date( 'm' );
$ds = intval( date( "t", mktime( 1, 0, 0, $m, 1, $y ) ) );
$m1 = date( 'Y' )."-".$m."-01 00:00:00";
$m2 = date( 'Y' )."-".$m."-".$ds." 23:59:59";
//квартал
function current_quartal( $date_orig ) {
	$new  = explode( "-", $date_orig );
	$mon  = $new[ 1 ]; //это текущий месяц
	$year = $new[ 0 ];
	$q1   = [
		1,
		2,
		3
	];
	$q2   = [
		4,
		5,
		6
	];
	$q3   = [
		7,
		8,
		9
	];
	$q4   = [
		10,
		11,
		12
	];
	if ( in_array( $mon, $q1 ) ) $quartal = 1;
	if ( in_array( $mon, $q2 ) ) $quartal = 2;
	if ( in_array( $mon, $q3 ) ) $quartal = 3;
	if ( in_array( $mon, $q4 ) ) $quartal = 4;

	return $quartal;
}

//вывод дат для квартала
function get_quartal( $quartal, $year ) {
	if ( $quartal == 1 ) {
		$q11 = $year.'-01-01';
		$q12 = $year.'-03-31';
	}
	if ( $quartal == 2 ) {
		$q11 = $year.'-04-01';
		$q12 = $year.'-06-30';
	}
	if ( $quartal == 3 ) {
		$q11 = $year.'-07-01';
		$q12 = $year.'-09-30';
	}
	if ( $quartal == 4 ) {
		$q11 = $year.'-10-01';
		$q12 = $year.'-12-31';
	}
	$dates = $q11." ".$q12;

	return $dates;
}

//квартал текущий
$qq = explode( " ", get_quartal( current_quartal( $d2 ), date( 'Y' ) ) );
$q1 = $qq[ 0 ]." 00:00:00";
$q2 = $qq[ 1 ]." 23:59:59";

$nd      = current_datum();
$nd_unix = date_to_unix( $nd );

$i = 0;

$settings              = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
$settings              = json_decode( $settings, true );
$settings[ 'mcSklad' ] = 'yes';

$DayP = $WeekP = $MounthP = $QuartalP = [];

?>
<div class="zagolovok">Сводный отчет</div>
<div style="max-height:400px; overflow: auto;">
	<table cellspacing="0" cellpadding="5" width="100%" align="center" id="bborder">
		<thead>
		<TR height="30" class="bordered header_contaner">
			<th height="30" align="center"><b></b></th>
			<th width="15%" align="center"><b>День</b></th>
			<th width="15%" align="center"><b>Неделя</b></th>
			<th width="15%" align="center"><b>Месяц</b></th>
			<th width="15%" align="center"><b>Квартал</b></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_offer WHERE datum between '".$d1."' and '".$d2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$DayP[ $data[ 'iduser' ] ] = $DayP[ $data[ 'iduser' ] ] + 1;
		}

		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_offer WHERE datum between '".$n1."' and '".$n2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$WeekP[ $data[ 'iduser' ] ] = $WeekP[ $data[ 'iduser' ] ] + 1;
		}

		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_offer WHERE datum between '".$m1."' and '".$m2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$MounthP[ $data[ 'iduser' ] ] = $MounthP[ $data[ 'iduser' ] ] + 1;
		}

		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_offer WHERE datum between '".$q1."' and '".$q2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$QuartalP[ $data[ 'iduser' ] ] = $QuartalP[ $data[ 'iduser' ] ] + 1;
		}

		?>
		<tr height="30" class="ha bordered bluebg2">
			<td><b>Добавлено предложений</b></td>
			<td align="center"><b><?= intval( array_sum( $DayP ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $WeekP ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $MounthP ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $QuartalP ) ) ?></b></td>
		</tr>
		<?php
		for ( $i = 0; $i < count( $settings[ 'mcCoordinator' ] ); $i++ ) {
			?>
			<tr height="30" class="ha bordered">
				<td><?= current_user( $settings[ 'mcCoordinator' ][ $i ] ) ?></td>
				<td align="center"><?= intval( $DayP[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $WeekP[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $MounthP[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $QuartalP[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
			</tr>
		<?php } ?>
		<?php
		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE status != '2' and datum between '".$d1."' and '".$d2."' ".$sort2." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$DayZ[ $data[ 'iduser' ] ] = $DayZ[ $data[ 'iduser' ] ] + 1;
		}

		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE status != '2' and datum between '".$n1."' and '".$n2."' ".$sort2." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$WeekZ[ $data[ 'iduser' ] ] = $WeekZ[ $data[ 'iduser' ] ] + 1;
		}

		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE status != '2' and datum between '".$m1."' and '".$m2."' ".$sort2." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$MounthZ[ $data[ 'iduser' ] ] = $MounthZ[ $data[ 'iduser' ] ] + 1;
		}

		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE status != '2' and datum between '".$q1."' and '".$q2."' ".$sort2." and identity = '$identity'" );
		foreach ( $result as $data ) {
			$QuartalZ[ $data[ 'iduser' ] ] = $QuartalZ[ $data[ 'iduser' ] ] + 1;
		}
		?>
		<tr height="30" class="ha bordered bluebg2">
			<td><b>Требуется позиций (заявок)</b></td>
			<td align="center"><b><?= intval( array_sum( $DayZ ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $WeekZ ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $MounthZ ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $QuartalZ ) ) ?></b></td>
		</tr>
		<?php
		for ( $i = 0; $i < count( $settings[ 'mcSpecialist' ] ); $i++ ) {
			?>
			<tr height="30" class="ha bordered">
				<td><?= current_user( $settings[ 'mcSpecialist' ][ $i ] ) ?></td>
				<td align="center"><?= intval( $DayZ[ $settings[ 'mcSpecialist' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $WeekZ[ $settings[ 'mcSpecialist' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $MounthZ[ $settings[ 'mcSpecialist' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $QuartalZ[ $settings[ 'mcSpecialist' ][ $i ] ] ) ?></td>
			</tr>
		<?php } ?>
		<?php
		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE datum_end between '".$d1."' and '".$d2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {

			$DayZdo[ $data[ 'iduser' ] ] = $DayZdo[ $data[ 'iduser' ] ] + 1;

		}
		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE datum_end between '".$n1."' and '".$n2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {

			$WeekZdo[ $data[ 'iduser' ] ] = $WeekZdo[ $data[ 'iduser' ] ] + 1;

		}
		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE datum_end between '".$m1."' and '".$m2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {

			$MounthZdo[ $data[ 'iduser' ] ] = $MounthZdo[ $data[ 'iduser' ] ] + 1;

		}
		$result = $db -> getAll( "SELECT iduser FROM ".$sqlname."modcatalog_zayavka WHERE datum_end between '".$q1."' and '".$q2."' ".$sort." and identity = '$identity'" );
		foreach ( $result as $data ) {

			$QuartalZdo[ $data[ 'iduser' ] ] = $QuartalZdo[ $data[ 'iduser' ] ] + 1;

		}

		$result = $db -> getAll( "SELECT prid FROM ".$sqlname."speca WHERE did IN (SELECT did FROM ".$sqlname."dogovor WHERE datum_close between '".$d1."' and '".$d2."' and identity = '$identity') and identity = '$identity'" );
		foreach ( $result as $data ) {

			$iduser             = $db -> getOne( "SELECT iduser FROM ".$sqlname."modcatalog WHERE prid = '".$data[ 'prid' ]."' and identity = '$identity'" );
			$DayZdop[ $iduser ] = $DayZdop[ $iduser ] + 1;

		}

		$result = $db -> getAll( "SELECT prid FROM ".$sqlname."speca WHERE did IN (SELECT did FROM ".$sqlname."dogovor WHERE datum_close between '".$n1."' and '".$n2."' and identity = '$identity') and identity = '$identity'" );
		foreach ( $result as $data ) {

			$iduser              = $db -> getOne( "SELECT iduser FROM ".$sqlname."modcatalog WHERE prid = '".$data[ 'prid' ]."' and identity = '$identity'" );
			$WeekZdop[ $iduser ] = $WeekZdop[ $iduser ] + 1;

		}

		$result = $db -> getAll( "SELECT prid FROM ".$sqlname."speca WHERE did IN (SELECT did FROM ".$sqlname."dogovor WHERE datum_close between '".$m1."' and '".$m2."' and identity = '$identity') and identity = '$identity'" );
		foreach ( $result as $data ) {

			$iduser                = $db -> getOne( "SELECT iduser FROM ".$sqlname."modcatalog WHERE prid = '".$data[ 'prid' ]."' and identity = '$identity'" );
			$MounthZdop[ $iduser ] = $MounthZdop[ $iduser ] + 1;

		}

		$result = $db -> getAll( "SELECT prid FROM ".$sqlname."speca WHERE did IN (SELECT did FROM ".$sqlname."dogovor WHERE datum_close between '".$q1."' and '".$q2."' and identity = '$identity') and identity = '$identity'" );
		foreach ( $result as $data ) {

			$iduser                 = $db -> getOne( "SELECT iduser FROM ".$sqlname."modcatalog WHERE prid = '".$data[ 'prid' ]."' and identity = '$identity'" );
			$QuartalZdop[ $iduser ] = $QuartalZdop[ $iduser ] + 1;

		}
		?>
		<tr height="30" class="ha bordered bluebg2">
			<td><b>Закуплено / Продано</b></td>
			<td align="center"><b><?= intval( array_sum( $DayZdo ) ) ?></b> /
				<b><?= intval( array_sum( $DayZdop ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $WeekZdo ) ) ?></b> /
				<b><?= intval( array_sum( $WeekZdop ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $MounthZdo ) ) ?></b> /
				<b><?= intval( array_sum( $MounthZdop ) ) ?></b></td>
			<td align="center"><b><?= intval( array_sum( $QuartalZdo ) ) ?></b> /
				<b><?= intval( array_sum( $QuartalZdop ) ) ?></b></td>
		</tr>
		<?php
		for ( $i = 0; $i < count( $settings[ 'mcCoordinator' ] ); $i++ ) {
			?>
			<tr height="30" class="ha bordered">
				<td><?= current_user( $settings[ 'mcCoordinator' ][ $i ] ) ?></td>
				<td align="center"><?= intval( $DayZdo[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?> / <?= intval( $DayZdop[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $WeekZdo[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?> / <?= intval( $WeekZdop[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $MounthZdo[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?> / <?= intval( $MounthZdop[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
				<td align="center"><?= intval( $QuartalZdo[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?> / <?= intval( $QuartalZdop[ $settings[ 'mcCoordinator' ][ $i ] ] ) ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</div>
<SCRIPT type="text/javascript">
	$(document).ready(function () {
		$('#dialog').css('width', '700px');
	});
</script>