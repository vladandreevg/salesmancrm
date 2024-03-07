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
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$y  = date( 'Y' );
$m  = date( 'm' );
$nd = date( 'd' );
$ed = strftime( '%m-%d', mktime( 0, 0, 0, $m, $nd + 14, $y ) );
$fd = strftime( '%m-%d', mktime( 0, 0, 0, $m, $nd - 14, $y ) );

$tm  = $tzone;
$nd2 = mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600;

$period_start = $fd;
$period_end   = $ed;

$massiv = $field = $fname = [];

//Находим именнинников в компании;
$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE DATE_FORMAT(bday, '%m-%d') between '".$period_start."' and '".$period_end."' and secrty ='yes' and identity = '$identity' ORDER BY bday" );
while ($data = $db -> fetch( $result )) {

	if ( $data['bday'] != '0000-00-00' ) {

		$age = $y - get_year( $data['bday'] );
		$by  = explode( "-", $data['bday'] );
		$by1 = $by[0] + $age;
		$by  = $by1."-".$by[1]."-".$by[2];
		$day = round( (date_to_unix( $by ) - $nd2) / 86400 ) + 1;

		$massiv[] = [
			"datum" => $data['bday'],
			"name"  => $data['title'],
			"age"   => $age,
			"day"   => $day,
			"type"  => "user",
			"id"    => $data['iduser'],
			"title" => "[Сотрудник]",
			"tip"   => "День Рождения"
		];

	}

}

//Находим именнинников в компании;
$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE DATE_FORMAT(CompStart, '%m-%d') between '".$period_start."' and '".$period_end."' and secrty ='yes' and identity = '$identity' ORDER BY CompStart" );
while ($data = $db -> fetch( $result )) {

	if ( $data['CompStart'] != '0000-00-00' ) {

		$age = $y - get_year( $data['CompStart'] );
		$by  = explode( "-", $data['CompStart'] );
		$by1 = $by[0] + $age;
		$by  = $by1."-".$by[1]."-".$by[2];
		$day = round( (date_to_unix( $by ) - $nd2) / 86400 ) + 1;

		$massiv[] = [
			"datum" => $data['CompStart'],
			"name"  => $data['title'],
			"age"   => $age,
			"day"   => $day,
			"type"  => "user",
			"id"    => $data['iduser'],
			"title" => "[Сотрудник]",
			"tip"   => "Стаж в компании"
		];

	}

}

//Находим именнинников в клиентах;
//Сначала найдем поля, содержащие даты
$field = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from ".$sqlname."field where fld_tip = 'person' and fld_on = 'yes' and fld_temp = 'datum' and identity = '$identity' order by fld_order" );


//данные по персонам
foreach ( $field as $name => $title ) {

	$result = $db -> query( "SELECT * FROM ".$sqlname."personcat WHERE DATE_FORMAT(".$name.", '%m-%d') between '".$period_start."' and '".$period_end."' and identity = '$identity' ORDER BY ".$name );
	while ($data = $db -> fetch( $result )) {

		if ( $data[ $name ] != '0000-00-00' ) {

			$age = $y - get_year( $data[ $name ] );
			$by  = explode( "-", $data[ $name ] );
			$by1 = $by[0] + $age;
			$by  = $by1."-".$by[1]."-".$by[2];
			$day = round( (date_to_unix( $by ) - $nd2) / 86400 ) + 1;

			$massiv[] = [
				"datum" => $data[ $name ],
				"name"  => $data['person'],
				"age"   => $age,
				"day"   => $day,
				"type"  => "person",
				"id"    => $data['pid'],
				"title" => $data['ptitle'],
				"tip"   => $title
			];

		}

	}

}

//Находим именнинников в контактах;
//Сначала найдем поля, содержащие даты
$field = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from ".$sqlname."field where fld_tip = 'client' and fld_on = 'yes' and fld_temp = 'datum' and identity = '$identity' order by fld_order" );

foreach ( $field as $name => $title ) {

	$result = $db -> query( "SELECT * FROM ".$sqlname."clientcat WHERE DATE_FORMAT(".$name.", '%m-%d') between '".$period_start."' and '".$period_end."' and identity = '$identity' ORDER BY ".$name );
	while ($data = $db -> fetch( $result )) {

		if ( $data[ $name ] != '' ) {

			$age = $y - get_year( $data[ $name ] );
			$by  = explode( "-", $data[ $name ] );
			$by1 = (int)$by[0] + (int)$age;
			$by  = $by1."-".$by[1]."-".$by[2];
			$day = round( (date_to_unix( $by ) - $nd2) / 86400 ) + 1;

			$massiv[] = [
				"datum" => $data[ $name ],
				"name"  => $data['title'],
				"age"   => $age,
				"day"   => $day,
				"type"  => "client",
				"id"    => $data['clid'],
				"title" => $data['title'],
				"tip"   => $title
			];

		}

	}

}

//print_r($massiv);

//пересортируем массив
function cmp($a, $b) {
	return $a['day'] - $b['day'];
}

usort( $massiv, "cmp" );

if ( empty( $massiv ) ) {

	print "Событий в ближайшие ±14 дней нет";
	exit();

}
?>
<table id="bborder">
	<?php
	foreach ( $massiv as $k => $v ) {

		$link  = $znak = '';
		$color = 'gray2';

		if ( $v['day'] == 0 )
			$color = "green";
		elseif ( $v['day'] < 0 )
			$color = "red";

		if ( $v['day'] < 0 )
			$znak = "-";
		elseif ( $v['day'] > 0 )
			$znak = "+";

		if ( $v['type'] == "user" )
			$link = "viewUser('".$v['id']."')";
		elseif ( $v['type'] == "person" )
			$link = "viewPerson('".$v['id']."')";
		elseif ( $v['type'] == "client" )
			$link = "viewClient('".$v['id']."')";

		?>
		<tr class="ha" height="40">
			<td width="5%" align="center" nowrap="nowrap">
				<b class="<?= $color ?>"><?= $znak.abs( $v['day'] ) ?> дн.</b>
			</td>
			<td width="5%" align="right" nowrap="nowrap"><b><?= $v['age'] ?></b> <?= getMorph( $v['age'] ) ?></td>
			<td width="70%">
				<span class="ellipsis" title="<?= $v['title'] ?>">
					<?php
					if ( $v['type'] == 'person' )
						print '<a href="javascript:void(0)" title="'.$v['title'].'" onClick="'.$link.'"><i class="icon-user-1 broun"></i> '.$v['name'].'</a>';
					elseif ( $v['type'] == 'client' )
						print '<a href="javascript:void(0)" title="'.$v['title'].'" onClick="'.$link.'"><i class="icon-commerical-building broun"></i> '.$v['name'].'</a>';
					elseif ( $v['type'] == 'user' )
						print '<a href="javascript:void(0)" title="'.$v['title'].'" onClick="'.$link.'"><i class="icon-user-1 blue"></i> '.$v['name']."  ".$v['title'].'</a>';
					?>
				</span>
			</td>
			<td width="20%"><span class="ellipsis"><?= $v['tip'] ?></span></td>
		</tr>
		<?php
	}
	?>
</table>