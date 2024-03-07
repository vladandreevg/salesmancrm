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

$action    = $_REQUEST['action'];
$da1       = $_REQUEST['da1'];
$da2       = $_REQUEST['da2'];
$user_list = (array)$_REQUEST['user_list'];
$point     = $_REQUEST['point'];

$color = [
	'#1f77b4',
	'#ff7f0e',
	'#2ca02c',
	'#d62728',
	'#9467bd',
	'#8c564b',
	'#c49c94'
];

$dlt = (int)date( "t", mktime( 0, 0, 0, (int)date( 'm' ), 1, (int)date( 'Y' ) ) );

if ( $da1 ) {
	$d1 = $da1;
}
else {
	$d1 = date( "Y-m-d", mktime( 0, 0, 1, (int)date( 'm' ), 1, (int)date( 'Y' ) ) + $tm * 3600 );
}

if ( $da2 ) {
	$d2 = $da2;
}
else {
	$d2 = date( "Y-m-d", mktime( 23, 59, 59, (int)date( 'm' ), $dlt, (int)date( 'Y' ) ) + $tm * 3600 );
}

$sort .= (!empty( $user_list )) ? "iduser IN (".yimplode( ",", $user_list ).") and " : "iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).") and ";

$sum = [];

if ( $action == 'point' ) {

	switch ($point) {
		case 'ActivOut':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {
				$result2                = $db -> query( "SELECT clid, pid, datum, iduser, tip FROM ".$sqlname."history WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did < 1 and iduser = '".$data['iduser']."' and identity = '$identity'" );
				$rez[ $data['iduser'] ] = $db -> numRows( $result2 );
			}
		break;
		case 'MeetOut':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {
				$result2                = $db -> query( "SELECT clid, pid, datum, iduser, tip FROM ".$sqlname."history WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Задача','Встреча','Презентация','Предложение','Отправка КП') and did > 0 and iduser = '".$data['iduser']."' and identity = '$identity'" );
				$rez[ $data['iduser'] ] = $db -> numRows( $result2 );
			}
		break;
		case 'newDogs':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {
				$result2                = $db -> query( "SELECT did, datum, iduser FROM ".$sqlname."dogovor WHERE did>0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );
				$rez[ $data['iduser'] ] = $db -> numRows( $result2 );

				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(kol) FROM ".$sqlname."dogovor WHERE did>0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );
			}
		break;
		case 'newContract':

			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$sum[ $data['iduser'] ] = 0;

				$rez[ $data['iduser'] ] = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."contract WHERE deid>0 and did > 0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" ) + 0;

				$sum[ $data['iduser'] ] = (float)$db -> query( "SELECT SUM(kol) as kol FROM ".$sqlname."dogovor WHERE did IN (SELECT did FROM ".$sqlname."contract WHERE deid>0 and did > 0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity') and identity = '$identity'" );

				$dida = '';

			}

		break;
		case 'Invoice':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {
				$result2                = $db -> query( "SELECT * FROM ".$sqlname."credit WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and iduser = '".$data['iduser']."' and identity = '$identity'" );
				$rez[ $data['iduser'] ] = $db -> numRows( $result2 );

				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(summa_credit) as summ FROM ".$sqlname."credit WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );
			}
		break;
		case 'InvoiceDo':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {
				$result2                = $db -> query( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and invoice_date between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );
				$rez[ $data['iduser'] ] = $db -> numRows( $result2 );

				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(summa_credit) as summ FROM ".$sqlname."credit WHERE do = 'on' and invoice_date between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );
			}
		break;
	}

	?>
	<table class="bgwhite">
		<thead>
		<TR class="header_contaner bordered text-center">
			<th class="w30"></th>
			<th class="w200"><b>Сотрудник</b></th>
			<th class="w100"><b>Кол-во</b></th>
			<?php
			if ( count( $sum ) > 0 ) {
				?>
				<th class="w150"><b>Сумма, <?= $valuta ?></b></th>
			<?php } ?>
			<th></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$max = max( $rez );
		$i   = 0;

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

		$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and $sort identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {
			$vall = ceil( $rez[ $data['iduser'] ] * 100 ) / $max;

			if ( $rez[ $data['iduser'] ] > 0 )
				$rez[ $data['iduser'] ] = '<b>'.$rez[ $data['iduser'] ].'</b>';
			else $rez[ $data['iduser'] ] = '<span class="gray">'.$rez[ $data['iduser'] ].'</span>';
			?>
			<tr class="ha bordered">
				<td nowrap><?= $i + 1 ?>.</td>
				<td nowrap><b><?= $data['title'] ?></b></td>
				<td class="text-center"><?= $rez[ $data['iduser'] ] ?></td>
				<?php
				if ( count( $sum ) > 0 ) {
					?>
					<td class="text-right"><b><?= num_format( $sum[ $data['iduser'] ] ) ?></b>&nbsp;</td>
				<?php } ?>
				<td>
					<div style="width: <?= $vall ?>%; height: 5px; background:<?= $color[ $i ] ?>;"></div>
				</td>
			</tr>
			<?php
			$i++;
		}
		?>
		</tbody>
	</table>
	<br>
	<?php
	exit();
}
if ( $action == 'detale' ) {

	$user = $_REQUEST['user'];
	$html = '';

	switch ($point) {
		/*case 'ActivOut':
			$result2 = $db -> query( "SELECT clid, pid, datum, iduser, tip FROM ".$sqlname."history WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did < 1 and iduser = '".$user."' and identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$html .= '';

			}
		break;*/
		case 'MeetOut':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and ".$sort." identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$rez[ $data['iduser'] ] = (int)$db -> getOne( "SELECT COUNT(cid) FROM ".$sqlname."history WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Задача','Встреча','Презентация','Предложение','Отправка КП') and did > 0 and iduser = '".$user."' and identity = '$identity'" );

			}
		break;
		case 'newDogs':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and ".$sort." identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$rez[ $data['iduser'] ] = (int)$db -> getOne( "SELECT COUNT(did) FROM ".$sqlname."dogovor WHERE did>0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$user."' and identity = '$identity'" );
				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."dogovor WHERE did>0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );

			}
		break;
		case 'newContract':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and ".$sort." identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."dogovor WHERE did IN (SELECT did FROM ".$sqlname."contract WHERE deid>0 and did > 0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$user."' and identity = '$identity') and identity = '$identity'" );

			}
		break;
		case 'Invoice':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and ".$sort." identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$rez[ $data['iduser'] ] = (int)$db -> getOne( "SELECT COUNT(crid) FROM ".$sqlname."credit WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and iduser = '".$data['iduser']."' and identity = '$identity'" );
				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(summa_credit) as summ FROM ".$sqlname."credit WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );

			}
		break;
		case 'InvoiceDo':
			$result = $db -> query( "SELECT iduser, title FROM ".$sqlname."user WHERE iduser > 0 and ".$sort." identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$rez[ $data['iduser'] ] = (int)$db -> getOne( "SELECT COUNT(crid) FROM ".$sqlname."credit WHERE do = 'on' and invoice_date between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );

				$sum[ $data['iduser'] ] = (float)$db -> getOne( "SELECT SUM(summa_credit) as summ FROM ".$sqlname."credit WHERE do = 'on' and invoice_date between '".$d1." 00:00:00' and '".$d2." 23:59:59' and iduser = '".$data['iduser']."' and identity = '$identity'" );

			}
		break;
	}

	?>
	<table class="bgwhite">
		<?= $html ?>
	</table>
	<br>
	<?php
	exit();
}

if ( $action == '' ) {

	$data['ActivOut'] = (int)$db -> getOne( "SELECT COUNT(cid) FROM ".$sqlname."history WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did < 1 and ".$sort." identity = '$identity'" );

	$data['MeetOut'] = (int)$db -> getOne( "SELECT COUNT(cid) FROM ".$sqlname."history WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and tip IN ('исх.1.Звонок','исх.2.Звонок','вх.Звонок','вх.Почта','Задача','Встреча','Презентация','Предложение','Отправка КП','Входящий звонок','Исходящий звонок','Холодный звонок','Исходящая почта') and did > 0 and ".$sort." identity = '$identity'" );

	$data['newDogs'] = (int)$db -> getOne( "SELECT COUNT(did) FROM ".$sqlname."dogovor WHERE did > 0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and ".$sort." identity = '$identity'" );

	$data['newContract'] = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."contract WHERE deid>0 and did > 0 and datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and ".$sort." identity = '$identity'" );

	$data['Invoice'] = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."credit WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and ".$sort." identity = '$identity'" );

	$summaInvoice = (float)$db -> getOne( "SELECT SUM(summa_credit) as summ FROM ".$sqlname."credit WHERE datum between '".$d1." 00:00:00' and '".$d2." 23:59:59' and ".$sort." identity = '$identity'" );

	$data['InvoiceDo'] = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."credit WHERE do = 'on' and invoice_date between '".$d1." 00:00:00' and '".$d2." 23:59:59' and ".$sort." identity = '$identity'" );

	$max = max( $data );

	$vor['ActivOut']    = $max > 0 ? ceil( $data['ActivOut'] * 100 ) / $max : 0;
	$vor['MeetOut']     = $max > 0 ? ceil( $data['MeetOut'] * 100 ) / $max : 0;
	$vor['newDogs']     = $max > 0 ? ceil( $data['newDogs'] * 100 ) / $max : 0;
	$vor['newContract'] = $max > 0 ? ceil( $data['newContract'] * 100 ) / $max : 0;
	$vor['Invoice']     = $max > 0 ? ceil( $data['Invoice'] * 100 ) / $max : 0;
	$vor['InvoiceDo']   = $max > 0 ? ceil( $data['InvoiceDo'] * 100 ) / $max : 0;
	?>
	<div class="zagolovok_rep">Воронка по активностям за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>

	<hr>

	<table>
		<thead>
		<TR class="header_contaner bordered">
			<th width="250" height="30" align="center"><b>Показатель</b></th>
			<th width="150" align="center"><b>Кол-во/Сумма</b></th>
			<th align="center"><b></b></th>
		</tr>
		</thead>
		<tbody>
		<tr class="ha bordered hand" onclick="SelPoint('ActivOut')">
			<td height="22">&nbsp;<span class="miditxt Bold">Активности без сделок</span>
				<i class="icon-info-circled blue list" title="Вне сделок: исх.1.Звонок, исх.2.Звонок, вх.Звонок, вх.Почта, Встреча, Презентация, Предложение, Отправка КП"></i>
			</td>
			<td align="center"><b class="blue bigtxt"><?= $data['ActivOut'] ?></b></td>
			<td align="left">
				<div style="width: <?= $vor['ActivOut'] ?>%; height: 5px; background:<?= $color[0] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden ActivOut pole">
			<td colspan="3">
				<div class="data infodiv"></div>
			</td>
		</tr>
		<tr class="ha bordered hand" onclick="SelPoint('MeetOut')">
			<td height="22">&nbsp;<span class="miditxt Bold">Активности со сделками</span>
				<i class="icon-info-circled blue list" title="По сделкам: Задача, Встреча, Презентация, Предложение, Отправка КП"></i>
			</td>
			<td align="center"><b class="blue bigtxt"><?= $data['MeetOut'] ?></b></td>
			<td align="left">
				<div style="width: <?= $vor['MeetOut'] ?>%; height: 5px; background:<?= $color[1] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden MeetOut pole">
			<td colspan="3">
				<div class="data infodiv"></div>
			</td>
		</tr>
		<tr class="ha bordered hand" onclick="SelPoint('newDogs')">
			<td height="22">&nbsp;<span class="miditxt Bold">Создано новых сделок</span></td>
			<td align="center"><b class="blue bigtxt"><?= $data['newDogs'] ?></b></td>
			<td align="left">
				<div style="width: <?= $vor['newDogs'] ?>%; height: 5px; background:<?= $color[2] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden newDogs pole">
			<td colspan="3">
				<div class="data infodiv"></div>
			</td>
		</tr>
		<tr class="ha bordered hand" onclick="SelPoint('newContract')">
			<td height="22">&nbsp;<span class="miditxt Bold">Создано новых договоров</span></td>
			<td align="center"><b class="blue bigtxt"><?= $data['newContract'] ?></b></td>
			<td align="left">
				<div style="width: <?= $vor['newContract'] ?>%; height: 5px; background:<?= $color[3] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden newContract pole">
			<td colspan="3">
				<div class="data infodiv"></div>
			</td>
		</tr>
		<tr class="ha bordered hand" onclick="SelPoint('Invoice')">
			<td height="22">&nbsp;<span class="miditxt Bold">Выставлено счетов</span></td>
			<td align="center"><b class="blue bigtxt"><?= $data['Invoice'] ?></b></td>
			<td align="left">
				<div style="width: <?= $vor['Invoice'] ?>%; height: 5px; background:<?= $color[4] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden Invoice pole">
			<td colspan="3">
				<div class="data infodiv"></div>
			</td>
		</tr>
		<tr class="ha bordered hand" onclick="SelPoint('InvoiceDo')">
			<td height="22">&nbsp;<span class="miditxt Bold">Оплаченных счетов</span></td>
			<td align="center"><b class="blue bigtxt"><?= $data['InvoiceDo'] ?></b></td>
			<td align="left">
				<div style="width: <?= $vor['InvoiceDo'] ?>%; height: 5px; background:<?= $color[5] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden InvoiceDo pole">
			<td colspan="3">
				<div class="data infodiv"></div>
			</td>
		</tr>
		</tbody>
	</table>

	<div id="pointdata"></div>
	<div class="space-50"></div>

	<?php
}
?>
<script>

	function SelPoint(point) {
		var str = $('#selectreport').serialize();
		var url = 'reports/voronka_classic.php?da1=<?=$d1?>&da2=<?=$d2?>&action=point&point=' + point + '&' + str;

		if ($('.' + point).hasClass('hidden') === true) {

			$('.pole').addClass('hidden');
			$('.' + point).removeClass('hidden');
			$('.' + point + ' .data').append('<img src="/assets/images/loading.gif">');

			$.post(url, str, function (data) {
				if (data) {
					$('.' + point + ' .data').html(data);
				}
				return false;
			});

		}
		else $('.' + point).addClass('hidden');

	}

</script>