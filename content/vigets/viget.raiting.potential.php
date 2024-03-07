<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

use Salesman\User;

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

/*
$color = [
	'#aec7e8',
	'#ffbb78',
	'#98df8a',
	'#ff9896',
	'#c5b0d5',
	'#c49c94',
	'#f7b6d2',
	'#c7c7c7',
	'#dbdb8d'
];*/

$color = [
	'#1f77b4',
	'#aec7e8',
	'#ff7f0e',
	'#ffbb78',
	'#2ca02c',
	'#98df8a',
	'#d62728',
	'#ff9896',
	'#9467bd',
	'#c5b0d5',
	'#8c564b',
	'#c49c94',
	'#e377c2',
	'#f7b6d2',
	'#7f7f7f',
	'#c7c7c7',
	'#bcbd22',
	'#dbdb8d',
	'#17becf',
	'#9edae5',
	'#393b79',
	'#5254a3',
	'#6b6ecf',
	'#9c9ede',
	'#637939',
	'#8ca252',
	'#b5cf6b',
	'#cedb9c',
	'#8c6d31',
	'#bd9e39',
	'#e7ba52',
	'#e7cb94',
	'#843c39',
	'#ad494a',
	'#d6616b',
	'#e7969c',
	'#7b4173',
	'#a55194',
	'#ce6dbd',
	'#de9ed6',
	'#FF0F00',
	'#000099',
	'#006600',
	'#CC6600',
	'#666699',
	'#990099',
	'#999900',
	'#0066CC',
	'#FF6600',
	'#996666',
	'#FF0033',
	'#0099FF',
	'#663300',
	'#666600',
	'#FF00CC',
	'#9900FF',
	'#FFCC00',
	'#003366',
	'#333333',
	'#99FF00'
];

//если это менеджер, то выведем всех менеджеров, которые ему подчиняются
if ( $tipuser == 'Менеджер продаж' ) {

	$collegues = User ::userColleagues( $iduser1 );

	$users = array_keys( $collegues );

}
else {//если это руководитель, то выведем всех его сотрудников

	$users = get_people( $iduser1, "yes" );

}

$sort = (count( $users ) > 0) ? "and iduser IN (".yimplode( ",", $users ).")" : "iduser = '$iduser1'";

//print $sort;

$max = 0;

$result = $db -> getAll( "SELECT iduser FROM {$sqlname}user WHERE acs_plan = 'on' and secrty = 'yes' $sort and identity = '$identity'" );
foreach ( $result as $data ) {

	$q = "
		SELECT
			SUM(kol) as summa
		FROM {$sqlname}dogovor
		WHERE
			did > 0 and
			COALESCE(close, 'no') != 'yes' and
			DATE_FORMAT(datum_plan, '%y-%m') = '".date( 'y' )."-".date( 'm' )."' and
			iduser = '".$data['iduser']."' and
			identity = '$identity'
	";

	$summa = $db -> getOne( $q );

	$rez[] = $summa;
	$usr[] = $data['iduser'];

	$rating[] = [
		"rez" => $summa,
		"usr" => $data['iduser']
	];

}

function cmp($a, $b) {
	return $b['rez'] > $a['rez'];
}

usort( $rating, 'cmp' );

$max = max( $rez );
if ( $max == 0 )
	$max = 1;

?>
<table class="nobg nopad">
	<?php
	foreach ($rating as $i => $r) {

		$per = ceil( $r['rez'] * 100 ) / $max;

		if ( $i < 3 && $r['rez'] > 0 ) {
			$bb = ' class = "green"';
		}
		elseif ( $i >= 3 && $i <= 5 && $r['rez'] > 0 ) {
			$bb = ' class = "blue"';
		}
		else {
			$bb = ' class = "gray"';
		}
		?>
		<tr class="th30 ha hand" <?php if ( stripos( $tipuser, 'Руководитель' ) !== false ) { ?> onclick="doLoad('content/vigets/viget.dataview.php?action=prognozView&datum=<?= date( 'y' )."-".date( 'm' ) ?>&iduser=<?= $r['usr'] ?>&onlyuser=yes')" <?php } ?>>
			<td class="wp40">
				<div class="ellipsis"><b<?= $bb ?>><?= current_user( $r['usr'] ) ?></b></div>
			</td>
			<td nowrap>
				<DIV class="progressbar"><?= num_format( $r['rez'] ) ?>&nbsp;<?= $valuta ?>
					<DIV id="test" class="progressbar-completed" style="width:<?= $per ?>%; background:<?= $color[ $i ] ?>;">
						<DIV class="status"></DIV>
					</DIV>
				</DIV>
			</td>
		</tr>
	<?php } ?>
</table>