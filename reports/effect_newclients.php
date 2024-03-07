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

$da1 = $_REQUEST['da1'];
$da2 = $_REQUEST['da2'];
$da  = $_REQUEST['da'];
$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per )
	$per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];

$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );

$sort = "cc.iduser IN (".yimplode( ",", $user_list ).") AND ";

//составляем запрос по клиентам и персонам
if ( !empty($clients_list) ) {
	$sort .= "cc.clid IN (".yimplode( ",", $clients_list).") AND ";
}

//составляем запрос по параметрам сделок
/*$ar = [];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}

}*/

$clients = $counts = [];

//Создание массивов данных
foreach ($user_list as $user) {

	$kol    = $db -> getOne( "SELECT COUNT(clid) FROM ".$sqlname."clientcat `cc` WHERE cc.date_create between '$da1 00:00:01' and '$da2 23:59:59' and cc.creator='$user' and cc.type IN ('client','person') and $sort cc.identity = '$identity'" );

	$clients[] = [
		"iduser"  => $user,
		"manager" => current_user( $user ),
		"kol"     => $kol
	];

	$result = $db -> query( "SELECT cc.clid, cc.date_create FROM ".$sqlname."clientcat `cc` WHERE cc.date_create between '$da1 00:00:01' and '$da2 23:59:59' and cc.creator='$user' and cc.type IN ('client','person') and $sort cc.identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {

		$uclient[ $user ][] = $data['clid'];
		$create[ $user ][]  = $data['date_create'];

	}

	$counts[$user] = $kol;

}
?>
<div class="zagolovok_rep text-center">
	<h1>Количество новых клиентов за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></h1>:
</div>

<hr>

<table id="zebra">
	<thead class="sticked--top">
	<TR class="header_contaner text-center">
		<td class="w20">&nbsp;</td>
		<td class="text-left"><B>Сотрудник</B></td>
		<td class="w100"><b>Новых клиентов</b></td>
	</TR>
	</thead>
	<tbody>
	<?php
	foreach ($clients as $client) {
		?>
		<TR class="ha bordered">
			<TD colspan="2">
				<DIV class="ellipsis1" title="<?= $client['manager'] ?>">
					<b class="blue"><?= $client['manager'] ?></b></DIV>
			</TD>
			<TD class="text-center">
				<DIV title="<?= $client['kol'] ?>"><?= $client['kol'] ?></DIV>
			</TD>
		</TR>
		<?php
		foreach ($uclient[$client['iduser']] as $k => $u) {
			?>
			<TR class="ha bordered">
				<TD class="text-center">#<?= $k + 1 ?>&nbsp;</TD>
				<TD>
					<a href="#" onclick="openClient('<?= $u ?>')" title="Карточка"><i class="icon-building broun"></i>&nbsp;<?= current_client( $u ) ?></a></TD>
				<TD class="text-center gray"><?= get_sfdate( $create[ $client['iduser'] ][ $k ] ) ?></TD>
			</TR>
		<?php } ?>
	<?php } ?>
	<TR bgcolor="#FC9">
		<TD class="text-center">&nbsp;</TD>
		<TD class="text-center"><B>ИТОГО</B></TD>
		<TD class="text-center"><B><?= array_sum( $counts ) ?></B></TD>
	</TR>
	</tbody>
</TABLE>
<div class="space-50"></div>