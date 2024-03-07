<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

use Salesman\Guides;

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

if ( !$da ) {
	$da = 'datum';
}

$act = $_REQUEST['act'];
$per = $_REQUEST['per'];

if ( !$per ) {
	$per = 'nedelya';
}

$user_list    = (array)$_REQUEST['user_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];

$sort = (!empty( $user_list )) ? "deal.iduser IN (".yimplode( ",", $user_list ).") AND " : "deal.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") AND ";

//составляем запрос по клиентам и персонам
if ( !empty($clients_list) && !empty($persons_list) ) {

	$sort .= "(deal.clid IN (".yimplode( ",", $clients_list).") OR deal.pid IN (".yimplode( ",", $persons_list ).")) AND ";

}
elseif ( !empty($clients_list) ) {
	$sort .= "deal.clid IN (".yimplode( ",", $clients_list).") AND ";
}
elseif ( !empty($persons_list) ) {
	$sort .= "deal.pid IN (".yimplode( ",", $persons_list ).") AND ";
}


//составляем запрос по параметрам сделок
$ar = [];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array($field, ['close','mcid']) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif($field == 'close'){
		$sort .= $query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif($field == 'mcid') {
		$mc = $field_query[ $i ];
	}

}

$mycomps = Guides::myComps();

if($mc > 0) {
	$sort .= "deal.mcid = '$mc' and ";
}
?>

<div class="zagolovok_rep text-center">
	<h1>Сделки за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></h1>
</div>

<div class="inline noprint">&nbsp;Cделки, дате
	<span class="select">
	<SELECT name="da" id="da">
	    <OPTION value="datum" <?php if ( $da == "datum" )
		    print "selected" ?>>Создания</OPTION>
	    <OPTION value="datum_izm" <?php if ( $da == "datum_izm" )
		    print "selected" ?>>Изменения</OPTION>
	    <OPTION value="datum_plan" <?php if ( $da == "datum_plan" )
		    print "selected" ?>>Плановой</OPTION>
	    <OPTION value="datum_close" <?php if ( $da == "datum_close" )
		    print "selected" ?>>Закрытия</OPTION>
	</SELECT>
	</span>
</div>

<hr>

<table id="zebra">
	<thead>
	<TR class="header_contaner text-center">
		<td class="w70"><B>Дата</B></td>
		<td class="w250"><b>Сделка</b></td>
		<td class="w100"><B>Тип сделки</B></td>
		<td class="w120"><B>План. сумма</B></td>
		<td class="w70"><b>Статус</b></td>
		<td class="w80"><b>Ответств.</b></td>
		<td><b>Клиент</b></td>
	</TR>
	</thead>
	<?php

	$result = $db -> query( "
		SELECT 
			* 
		FROM ".$sqlname."dogovor `deal`
		WHERE 
			deal.{$da} between '$da1' and '$da2' and 
			$sort
			deal.identity = '$identity' 
		ORDER BY deal.$da DESC
	" );
	while ($data_array = $db -> fetch( $result )) {

		if ( $data_array['clid'] > '0' ) {

			$q = "clid='".$data_array['clid']."'";
			$k = "clientcat";
			$g = "title";

		}
		else {

			$q = "pid='".$data_array['pid']."'";
			$k = "personcat";
			$g = "person";

		}

		$dogstatus = $db -> getOne( "SELECT title FROM ".$sqlname."dogcategory WHERE idcategory='".$data_array['idcategory']."' and identity = '$identity'" );

		$client = $db -> getOne( "SELECT $g FROM ".$sqlname.$k." WHERE ".$q );

		$manpro = $db -> getOne( "SELECT title FROM ".$sqlname."user WHERE iduser='".$data_array['iduser']."' and identity = '$identity'" );

		$tip = $db -> getOne( "SELECT title FROM ".$sqlname."dogtips WHERE tid='".$data_array['tip']."' and identity = '$identity'" );

		if ( (int)$data_array['sid'] > 0 ) {

			$dogstatus = $db -> getOne( "SELECT title FROM ".$sqlname."dogstatus WHERE sid='".$data_array['sid']."' and identity = '$identity'" );

		}
		$all_kol += $data_array['kol'];
		?>
		<TR class="ha bordered">
			<TD class="text-center"><?= get_date( $data_array[ $da ] ) ?></TD>
			<TD>
				<SPAN class="ellipsis list"><A href="javascript.void(0)" onclick="openDogovor('<?= $data_array['did'] ?>')" title="Открыть в новом окне"><i class="icon-briefcase broun"></i></A>&nbsp;<a href="javascript.void(0)" onClick="viewDogovor('<?= $data_array['did'] ?>')" title="<?= $data_array['title'] ?>"><B><?= $data_array['title'] ?></B></a></SPAN>
			</TD>
			<TD><SPAN class="ellipsis" title="<?= $tip ?>"><?= $tip ?></SPAN></TD>
			<TD class="text-right">
				<SPAN title="<?= num_format( $data_array['kol'] ) ?>"><?= num_format( $data_array['kol'] ) ?></SPAN>
			</TD>
			<TD>
				<DIV class="progressbar">
					<DIV id="test" class="progressbar-completed" style="width:<?= $dogstatus ?>%;" title="<?= $dogstatus." - ".$dogcontent ?>">
						<DIV><?= $dogstatus ?></DIV>
					</DIV>
				</DIV>
			</TD>
			<TD><SPAN class="ellipsis" title="<?= $manpro ?>"><?= $manpro ?></SPAN></TD>
			<TD>
				<SPAN class="ellipsis" title="<?= $client ?>">
				<?php if ( $data_array['clid'] > '0' ) { ?>
					<A href="javascript.void(0)" onclick="openClient('<?= $data_array['clid'] ?>')"><i class="icon-building broun"></i>&nbsp;<?= $client ?></A>
				<?php } else { ?>
					<A href="javascript.void(0)" onclick="openPerson('<?= $data_array['pid'] ?>')"><i class="icon-user-1 blue"></i>&nbsp;<?= $client ?></A>
				<?php } ?>
				</SPAN>
			</TD>
		</TR>
		<?php
	}
	?>
	<TR bgcolor="#FC9">
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD class="text-right"><b><?= num_format( $all_kol ) ?></b></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
	</TR>
</TABLE>
<div style="height: 90px;"></div>