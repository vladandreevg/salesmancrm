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
];
*/

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
else {

	$users = get_people( $iduser1, "yes" );

}

$sort = (count( $users ) > 0) ? "and iduser IN (".yimplode( ",", $users ).")" : "iduser = '$iduser1'";

$max = 0;

$result = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE acs_plan = 'on' and secrty = 'yes' $sort and identity = '$identity'" );
foreach ( $result as $data ) {

	$q = "
		SELECT
			{$sqlname}dogovor.iduser as user,
			COUNT({$sqlname}credit.summa_credit) as count,
			SUM({$sqlname}credit.summa_credit) as summa
		FROM {$sqlname}credit
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
		WHERE
			{$sqlname}credit.did > 0 and
			{$sqlname}credit.do = 'on' and
			DATE_FORMAT({$sqlname}credit.invoice_date, '%y-%m') = '".date( 'y' )."-".date( 'm' )."' and
			{$sqlname}credit.iduser = '".$data['iduser']."' and
			{$sqlname}credit.identity = '$identity'
		GROUP BY 1
	";

	$resc = $db -> getRow( $q );

	$rez[] = $resc['summa'];
	$usr[] = $data['iduser'];

	$rating[] = [
		"rez"   => $resc['summa'],
		"count" => $resc['count'],
		"usr"   => $data['iduser']
	];

}

function cmp($a, $b) {
	return $b['rez'] > $a['rez'];
}

usort( $rating, 'cmp' );

//rsort($rez);

$max = max( $rez );
if ( $max == 0 ) {
	$max = 1;
}

?>
<table class="nobg nopad">
	<thead class="hidden">
	<tr>
		<th>Сотрудник</th>
		<th>Средний чек</th>
		<th>Сумма</th>
		<th></th>
	</tr>
	</thead>
	<?php
	foreach ($rating as $i => $xrating) {

		$per = ceil( $xrating['rez'] * 100 ) / $max;

		$middleCheck = $xrating['count'] > 0 ? $xrating['rez'] / $xrating['count'] : 0;

		if ( $i < 3 && $xrating['rez'] > 0 ) {
			$bb = ' class = "green"';
		}
		elseif ( $i >= 3 && $i <= 5 && $xrating['rez'] > 0 ) {
			$bb = ' class = "blue"';
		}
		else {
			$bb = ' class = "gray"';
		}

		?>
		<tr class="th30 ha hand" <?php if ( stripos( $tipuser, 'Руководитель' ) !== false ) { ?> onClick="doLoad('/content/vigets/viget.dataview.php?action=paymentViewNew&iduser=<?= $xrating['usr'] ?>&onlyuser=yes')" <?php } ?>>
			<td class="wp40">
				<div class="ellipsis"><b<?= $bb ?>><?= current_user( $xrating['usr'] ) ?></b></div>
			</td>
			<td class="wp25 hidden-iphone" title="Средний чек">
				<span class="fs-09"><?= num_format( $middleCheck ) ?>&nbsp;<?= $valuta ?></span>
			</td>
			<td nowrap>
				<DIV class="progressbar"><?= num_format( $xrating['rez'] ) ?>&nbsp;<?= $valuta ?>
					<DIV id="test" class="progressbar-completed" style="width:<?= $per ?>%; background:<?= $color[ $i ] ?>;">
						<DIV class="status" style="padding-top: 8px"></DIV>
					</DIV>
				</DIV>
			</td>
		</tr>
	<?php } ?>
</table>