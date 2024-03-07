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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];
$did    = (int)$_REQUEST['did'];
$ccid = (int)$_REQUEST['ccid'];

if (!$per) $per = 'nedelya';

$user_list    = (array)$_REQUEST['user_list'];
$clients_list = (array)$_REQUEST['clients_list'];
$persons_list = (array)$_REQUEST['persons_list'];
$fields       = (array)$_REQUEST['field'];
$field_query  = (array)$_REQUEST['field_query'];


//массив пользователей
$user_list = (!empty( $user_list )) ? $user_list : (array)get_people( $iduser1, "yes" );
if (!empty($user_list)) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

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
$ar = [
	'close',
	'idcategory'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

$nd      = current_datum();
$nd_unix = date_to_unix($nd);
$i       = 0;
$kol_sum = 0;
$s = '';

$dogs = [];

//$s .= ($ccid) ? " and ccid = '$ccid'" : "";
$s .= (count($user_list) > 0) ? " and iduser IN (".implode(",", $user_list).")" : '';

$da = $db -> getAll("SELECT * FROM ".$sqlname."complect WHERE doit = 'yes' and data_fact between '$da1 00:00:00' and '$da2 23:59:59' $s and identity = '$identity' ORDER BY data_fact");
foreach ($da as $data) {

	$d       = $db -> getRow("SELECT * FROM ".$sqlname."dogovor WHERE did = '".$data['did']."' and identity = '$identity'");
	$clid    = $d["clid"];
	$dogname = $d["title"];
	$kol     = $d["kol"];
	$iduser  = $d["iduser"];

	$cpoint = $db -> getOne("SELECT title FROM ".$sqlname."complect_cat WHERE ccid = '".$data['ccid']."' and identity = '$identity'");

	//Здоровье сделки. конец.
	$dogs[] = array(
		"did"     => $data['did'],
		"pdatum"  => $data['data_plan'],
		"fdatum"  => $data['data_fact'],
		"client"  => current_client($clid),
		"dogname" => $dogname,
		"kol"     => $kol,
		"manager" => current_user($iduser, 'yes'),
		"ccid"    => $data['ccid'],
		"cpoint"  => $cpoint
	);

	$kol_sum += $kol;

}

function cmp($a, $b) { return $b['datum'] < $a['datum']; }

usort($dogs, 'cmp');

?>

<div class="relativ mt20 mb20 wp95" align="center">
	<h1 class="uppercase fs-14 m0 mb10">Контрольные точки</h1>
	<div class="gray2">выполненные в период&nbsp;с&nbsp;<?= $da1 ?>&nbsp;по&nbsp;<?= $da2 ?></div>
</div>

<hr>

<div class="noprint">
	&nbsp;<B>Контрольная точка:</B>&nbsp;
	<span class="select">
		<select name="ccid" id="ccid" class="jcontent">
			<option value="">Все</option>
			<?php
			$resultcc = $db -> query("SELECT * FROM ".$sqlname."complect_cat WHERE identity = '$identity' ORDER BY corder");
			while ($da = $db -> fetch($resultcc)) {

				print '<option '.($ccid == $da['ccid'] ? "selected" : "").' value="'.$da['ccid'].'">'.$da['title'].'</option>';

			}
			?>
		</select>
		</span>

</div>

<hr>

<TABLE width="100%" cellpadding="5" cellspacing="0" id="zebra">
	<thead>
	<TR class="header_contaner" height="30">
		<td width="70" align="center"><b>Дата план.</b></td>
		<td width="70" align="center"><b>Дата факт.</b></td>
		<td width="70" align="center"><b>Разница</b></td>
		<td width="250" align="center">Контрольная точка</td>
		<td width="120" align="center"><b>Выполнил</b></td>
		<td align="center"><b>Название сделки</b>/<b>Заказчик</b></td>
		<td width="120" align="center"><b>Стоимость, <?= $valuta ?></b></td>
		<td width="10"></td>
	</TR>
	</thead>
	<?php
	$summa = 0;
	foreach ($dogs as $dog) {

		$diff = round(diffDate2($dog['pdatum'], $dog['fdatum']),0);
		?>
		<TR class="ha" style="height:40px" data-id="<?=$dog['ccid']?>">
			<TD align="center"><span class=""><?= format_date_rus($dog['pdatum']) ?></span></TD>
			<TD align="center"><span class=""><?= format_date_rus($dog['fdatum']) ?></span></TD>
			<TD align="right"><span class="<?=($diff < 0 ? 'red' : 'green')?> Bold"><?= $diff ?></span></TD>
			<TD>
				<div class="ellipsis fs-12"><b><?= $dog['cpoint'] ?></b></div>
			</TD>
			<TD align="left">
				<div class="ellipsis"><?= $dog['manager'] ?></div>
			</TD>
			<TD>
				<div class="ellipsis fs-11 Bold">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $dog['did'] ?>')" title="Открыть в новом окне">
						<i class="icon-briefcase blue"></i> <?= $dog['dogname'] ?>
					</A>
				</div>
				<br>
				<div class="ellipsis gray fs-09 mt5">
					<i class="icon-building broun"></i><?= $dog['client'] ?>
				</div>
			</TD>
			<TD align="right"><?= num_format($dog['kol']) ?></TD>
			<TD align="right"></TD>
		</TR>
	<?php
		$summa += $dog['kol'];
	}
	?>
	<tfoot>
	<TR class="bluebg-sub" style="height:40px">
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD></TD>
		<TD align="right"><b><?= sizeof($dogs) ?></b></TD>
		<TD align="right"><b><?= num_format($summa) ?></b></TD>
		<TD></TD>
	</TR>
	</tfoot>
</TABLE>

<div style="height: 90px;"></div>

<script>

	$( function() {

		$('#ccid').trigger('change');

	});

	$('#ccid').off('change');
	$('#ccid').on('change', function(){

		var id = $(this).val();

		$('tr').removeClass('hidden');

		if(id != '') {

			$('#zebra tbody').find('tr').each(function () {

				if ($(this).data('id') != id) $(this).addClass('hidden');
				else if ($(this).data('id') != id) $(this).removeClass('hidden');

			});

		}

	});

</script>
