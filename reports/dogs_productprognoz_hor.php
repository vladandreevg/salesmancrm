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

if ( $acs_analitics != 'on' ) {
	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit;
}

$user_list   = (array)$_REQUEST['user_list'];
$action      = $_REQUEST['action'];
$fields       = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$sort = $clist = $plist = '';

//массив выбранных пользователей
if ( !empty($user_list) ) {
	$sort = " and iduser IN (".yimplode( ",", $user_list ).")";
}
else {
	$sort = " and iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).")";
}

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close'
];
foreach ($fields as $i => $field) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " and ".$field." = '".$field_query[ $i ]."'";
	}

}

$userss = [];
$sdelka = [];

$i   = 0;
$max = 0;// max - число столбцов с номенклатурой

//Формируем массив данных по активным сделкам
$result = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE did > 0 and close != 'yes' $sort and identity = '$identity' ORDER BY title" );
while ($data = $db -> fetch( $result )) {

	$sdelka[ $i ]['id']        = (int)$data['did'];
	$sdelka[ $i ]['iduser']    = (int)$data['iduser'];
	$sdelka[ $i ]['title']     = $data['title'];
	$sdelka[ $i ]['step']      = current_dogstep( $data['did'] );
	$sdelka[ $i ]['clid']      = (int)$data['clid'];
	$sdelka[ $i ]['pid']       = (int)$data['pid'];
	$sdelka[ $i ]['plan_date'] = $data['datum_plan'];
	$sdelka[ $i ]['kol']       = $data['kol'];
	$sdelka[ $i ]['marga']     = $data['marga'];
	$sdelka[ $i ]['ves']       = $data['kol'] * current_dogstep( $data['did'] ) / 100;
	$sdelka[ $i ]['vesm']      = $data['marga'] * current_dogstep( $data['did'] ) / 100;

	//проходим спецификации по сделкам
	$j       = 0;
	$results = $db -> getAll( "SELECT * FROM ".$sqlname."speca WHERE did = '".$data['did']."' and identity = '$identity' ORDER BY title" );
	foreach ( $results as $datas ) {

		$sdelka[ $i ][ 'prod'.$j ] = $datas['title'];
		$sdelka[ $i ][ 'kol'.$j ]  = $datas['kol'];

		$j++;

	}

	if ( $j > $max ) {
		$max = $j;
	}

	$i++;

}

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


?>
<style>
	@media print {
		.fixAddBotButton {
			display : none;
		}
	}
</style>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Прогноз по количеству продуктов [расширенный]</h1>
	<div class="gray2">по спецификациям в открытых сделках</div>
</div>

<hr>

<div style="width: 100%; overflow-x: auto;">

	<div class="data" style="max-height: 65vh;">

		<table class="top" id="zebra">
			<thead>
			<tr class="header_contaner text-center">
				<th class="w20"><B>ID</B></th>
				<th class="w350"><B>Сделка</B></th>
				<th class="w50"><B>Этап, %</B></th>
				<th class="w80"><B>Ответственный</B></th>
				<th class="w80"><B>Дата.план</B></th>
				<th class="w100"><B>Сумма, <?= $valuta ?></B></th>
				<th class="w100"><B>Маржа, <?= $valuta ?></B></th>
				<th class="w100"><B>Взвеш.сумма, <?= $valuta ?></B></th>
				<th class="w100"><B>Взвеш.маржа, <?= $valuta ?></B></th>
				<?php
				for ( $i = 0; $i < $max; $i++ ) {
					print '
					<th class="w350"><B>Продукт</B></th>
					<th class="w50"><B>Кол.</B></th>
					';
				}
				?>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $sdelka as $deal ) {

				if ( $deal['clid'] > 0 ) {
					$client = current_client( $deal['clid'] );
				}
				if ( $deal['pid'] > 0 ) {
					$client = current_person( $deal['pid'] );
				}
				?>
				<tr class="ha">
					<td class="text-center"><?= $deal['id'] ?></td>
					<td>
						<div class="fs-12 blue"><B><?= $deal['title'] ?></B></div>
						<div class="gray2 fs-09 mt5"><?= $client ?></div>
					</td>
					<td class="text-center"><B><?= $deal['step'] ?></B></td>
					<td><B><?= current_user( $deal['iduser'] ) ?></B></td>
					<td class="text-center"><?= format_date_rus( $deal['plan_date'] ) ?></td>
					<td class="text-right" nowrap><?= num_format( $deal['kol'] ) ?></td>
					<td class="text-right" nowrap><?= num_format( $deal['marga'] ) ?></td>
					<td class="text-right" nowrap><?= num_format( $deal['ves'] ) ?></td>
					<td class="text-right" nowrap><?= num_format( $deal['vesm'] ) ?></td>
					<?php
					for ( $j = 0; $j < $max; $j++ ) {
						print '
						<td>
							<div class="ellipsis1"><B>'.$deal[ 'prod'.$j ].'</B></div>
						</td>
						<td class="text-right">'.$deal[ 'kol'.$j ].'</td>
						';
					}
					?>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>

	</div>

</div>
<div class="noprint text-center">

	<div class="infodiv marg3 div-center" style="width: 60%; text-align: left;">
		<span><i class="icon-info-circled blue icon-5x pull-left"></i></span>

		<ul>
			<li>Отчет производит анализ спецификаций в активных сделках и рассчитывает количество позиций по Номенклатуре.</li>
			<li>Отчет не учитывает позиции, не содержащиеся в прайс листе и не имеющие Артикул</li>
		</ul>
	</div>
</div>

<div style="height: 90px;"></div>

<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script>
	$(function () {

		$("#zebra").tableHeadFixer({
			'head': true,
			'foot': false,
			'z-index': 12000,
			'left': 2
		}).css('z-index', '100');

		$("#zebra").find('td:nth-child(1)').css('z-index', '110');
		$("#zebra").find('td:nth-child(2)').css('z-index', '110');

	});

</script>