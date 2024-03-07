<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

error_reporting( 0 );
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

$st  = mktime( 0, 0, 0, $m + 1, 0, $y ); //сформировали дату для дальнейшей обработки - первый день месяца $m года $y
$dd  = intval( date( "t", $st ) ); //получили Стоимость дней в месяце
$d11 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m, '01', $y ) );
$d12 = strftime( '%Y-%m-%d', mktime( 0, 0, 0, $m, $dd, $y ) );

$sort = get_people( $iduser1 );

$all_dogs2 = 0;
$all_kol2  = 0;
$dogs_max  = 0;
$kol_max   = 0;

//число этапов
$steps  = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title" );
$stepsH = 17 / ($steps + 2.4);

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

$res = $db -> getAll( "SELECT idcategory as id FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title" );
foreach ( $res as $data ) {

	//$res = $db -> getRow( "SELECT SUM(kol) as kol, COUNT(*) as count FROM {$sqlname}dogovor WHERE close != 'yes' and DATE_FORMAT(datum_plan, '%y-%m') = '".date( 'y' )."-".date( 'm' )."' and idcategory='".$data['id']."' ".$sort." and identity = '$identity'" );
	$res = $db -> getRow( "SELECT SUM(kol) as kol, COUNT(*) as count FROM {$sqlname}dogovor WHERE COALESCE(close, 'no') != 'yes' and idcategory='".$data['id']."' ".$sort." and identity = '$identity'" );

	$kol[ $data['id'] ]  = $res['kol'];
	$dogs[ $data['id'] ] = $res['count'];

}

$max1 = max( $kol );
$max2 = max( $dogs );

?>
<TABLE class="nopad">
	<thead class="hidden">
	<tr>
		<th>Этап</th>
		<th>Кол-во</th>
		<th></th>
		<th>Сумма</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$dall = $kall = 0;
	$i    = 0;

	$result = $db -> getAll( "SELECT idcategory as id, title as step, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title" );
	foreach ( $result as $data ) {

		$dall += $dogs[ $data['id'] ];
		$kall += $kol[ $data['id'] ];

		if ( $max2 > 0 )
			$dogs_pec = ceil( ($dogs[ $data['id'] ] * 100) / $max2 );
		else $dogs_pec = 1;
		if ( $max1 > 0 )
			$kol_pec = ceil( ($kol[ $data['id'] ] * 100) / $max1 );
		else $kol_pec = 1;
		?>
		<TR style="height:<?= $stepsH ?>em" class="ha">
			<TD class="text-right">
				<DIV title="<?= $data['content'] ?>"><b><?= $data['step'] ?></b> %&nbsp;&nbsp;</DIV>
			</TD>
			<TD class="wp40 list" onclick="showData('<?= $data['id'] ?>')" title="Показать сделки">
				<DIV class="progressbar gray2"><?= $dogs[ $data['id'] ] ?>&nbsp;шт.
					<DIV class="progressbar-completed bluebg" style="width:<?= $dogs_pec ?>%;">
						<DIV class="status"></DIV>
					</DIV>
				</DIV>
			</TD>
			<TD class="wp40 list" onclick="showData('<?= $data['id'] ?>')">
				<DIV class="progressbar gray2" style=""><?= str_replace( " ", "&nbsp;", num_format( $kol[ $data['id'] ] ) ) ?>&nbsp;<?= $valuta ?>
					<DIV class="progressbar-completed greenbg" style="width:<?= $kol_pec ?>%;">
						<DIV class="status nowrap"></DIV>
					</DIV>
				</DIV>
			</TD>
		</TR>
		<?php
	}

	$res  = $db -> getRow( "SELECT SUM(kol_fact) as summa, COUNT(*) as count FROM {$sqlname}dogovor WHERE datum_close between '".$d11." 00:00:00' and '".$d12." 23:59:59' and close = 'yes' ".$sort." and identity = '$identity'" );
	$kol  = $res["summa"];
	$dogs = $res["count"];

	if ( $max2 > 0 )
		$dogs_pec = ceil( ($dogs * 100) / $max2 );
	else $dogs_pec = 1;
	if ( $max1 > 0 )
		$kol_pec = ceil( ($kol * 100) / $max1 );
	else $kol_pec = 1;

	$dall += $dogs;
	$kall += $kol;
	?>
	<TR style="height:<?= $stepsH ?>em" class="ha">
		<TD class="text-right">
			<DIV title="Закрыто"><b>Закрыто</b>&nbsp;&nbsp;</DIV>
		</TD>
		<TD onclick="showData('closed')" title="Показать сделки" class="list">
			<DIV class="progressbar" style=""><?= $dogs ?>&nbsp;шт.
				<DIV class="progressbar-completed" style="width:<?= $dogs_pec ?>%; background:#98df8a;">
					<DIV class="status"></DIV>
				</DIV>
			</DIV>
		</TD>
		<TD class="list" onclick="showData('closed')">
			<DIV class="progressbar" style=""><?= str_replace( " ", "&nbsp;", num_format( $kol ) ) ?>&nbsp;<?= $valuta ?>
				<DIV class="progressbar-completed" style="width:<?= $kol_pec ?>%; background:#c5b0d5;">
					<DIV class="status nowrap"></DIV>
				</DIV>
			</DIV>
		</TD>
	</TR>
	<TR class="itog" style="height:<?= $stepsH ?>em">
		<TD class="text-right">&nbsp;&nbsp;<B>Итого:</B></TD>
		<TD class="text-right"><B><?= $dall ?></B> сделок</TD>
		<TD class="text-right"><B><?= num_format( $kall ) ?></B>&nbsp;<?= $valuta ?></TD>
	</TR>
	</tbody>
</TABLE>

<SCRIPT>

	function showData(step) {
		doLoad('content/vigets/viget.dataview.php?action=stepView&all=yes&step=' + step);
	}

</SCRIPT>