<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
<?php
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
$fields      = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];
$number      = (int)$_REQUEST['number'];//количество дней, для поиска

if ( $number == '' ) {
	$number = 62;
}

$sort = $clist = $plist = '';

//массив выбранных пользователей
if ( !empty( $user_list ) ) {
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
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != 'close' ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif($field == 'close'){
		$sort .= $query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}

}

//Формируем массив данных по активным сделкам
$result = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE did > 0 and close != 'yes' $sort and identity = '$identity' ORDER BY title" );
while ($data = $db -> fetch( $result )) {

	//проходим спецификации по сделкам
	$res = $db -> query( "SELECT * FROM ".$sqlname."speca WHERE did = '".$data['did']."' and artikul != '' and identity = '$identity' ORDER BY title" );
	while ($datas = $db -> fetch( $res )) {

		//составляем массив по артикулам с указанием количества
		$speca[ $datas['artikul'] ]   += (float)$datas['kol']; //количество
		$product[ $datas['artikul'] ] = $datas['title']; //количество
		$summa[ $datas['artikul'] ]   += (float)$datas['price'] * (float)$datas['kol']; //сумма
		$ves[ $datas['artikul'] ]     += (float)$datas['kol'] * (float)current_dogstepname( $data['idcategory'] ) / 100; //сумма

	}

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
	<!--

	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	-->
</style>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Прогноз по количеству продуктов [краткий]</h1>
	<div class="gray2">по спецификациям в открытых сделках</div>
</div>

<hr>

<div style="width: 100%; overflow-x: auto;">

	<div class="data mb20" style="max-height: 65vh;">

		<table class="top" id="zebra">
			<thead>
			<tr height="40" class="header_contaner text-center">
				<th class="w100"><B>Артикул</B></th>
				<th><B>Наименование</B></th>
				<th class="120"><B>Кол-во</B></th>
				<th class="100" title="Количество с учетом этапа сделки"><B>Вес</B></th>
				<th class="120"><B>Сумма, <?= $valuta ?></B></th>
			</tr>
			</thead>
			<tbody>
			<?php
			arsort( $speca );

			foreach ( $speca as $key => $value ) {
				?>
				<tr class="ha">
					<td><?= $key ?></td>
					<td><B><?= $product[ $key ] ?></B></td>
					<td class="text-right"><?= $value ?></td>
					<td class="text-right"><?= num_format( $ves[ $key ] ) ?></td>
					<td class="text-right"><?= num_format( $summa[ $key ] ) ?></td>
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
			'left': 0
		}).css('z-index', '100');

	});

</script>