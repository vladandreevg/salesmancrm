<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

error_reporting( E_ERROR );
ini_set('display_errors', 1);

header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__);

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

$user_list = (array)$_REQUEST[ 'user_list' ];
$action    = $_REQUEST[ 'action' ];
$number    = (int)$_REQUEST[ 'number' ];//количество дней, для поиска

if ( $number == 0 ) {
	$number = 62;
}

//массив выбранных пользователей
if ( !empty($user_list) ) {
	$sort = " and iduser IN (".yimplode( ",", $user_list ).")";
}
else {
	$sort = " and iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).")";
}

//Формируем массив данных по активным сделкам
$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogovor WHERE did > 0 and close != 'yes' $sort and identity = '$identity' ORDER BY title" );
foreach ( $result as $data ) {

	//проходим спецификации по сделкам
	$results = $db -> getAll( "SELECT * FROM ".$sqlname."speca WHERE did = '".$data[ 'did' ]."' and artikul != '' and identity = '$identity' ORDER BY title" );
	foreach ( $results as $datas ) {

		//составляем массив по артикулам с указанием количества
		$speca[ $datas['artikul'] ]     += (float)$datas['kol']; //количество
		$product[ $datas[ 'artikul' ] ] = $datas[ 'title' ]; //количество
		$summa[ $datas[ 'artikul' ] ]   = $summa[ $datas[ 'artikul' ] ] + (float)$datas[ 'price' ] * (float)$datas[ 'kol' ]; //сумма
		$ves[ $datas[ 'artikul' ] ]     = $ves[ $datas[ 'artikul' ] ] + (float)$datas[ 'kol' ] * current_dogstepname( $data[ 'idcategory' ] ) / 100; //сумма

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
	@media print {
		.fixAddBotButton {
			display : none;
		}
	}

	#zebra thead th {
		z-index : 10 !important;
	}

	#zebra .ellipsis {
		z-index : 0 !important;
	}
</style>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Прогноз по количеству продуктов</h1>
	<div class="gray2">по спецификациям в открытых сделках</div>
</div>

<hr>

<div style="width: 99%;">

	<div class="data mb10">

		<table id="zebra">
			<thead class="sticked--top">
			<tr class="header_contaner text-center">
				<th class="w100"><B>Артикул</B></th>
				<th><B>Наименование</B></th>
				<th class="w100"><B>Кол-во</B></th>
				<th class="w100" title="Количество с учетом этапа сделки"><B>Взвешенное кол-во</B></th>
				<th class="w140"><B>Сумма, <?= $valuta ?></B></th>
			</tr>
			</thead>
			<tbody>
			<?php
			arsort( $speca );
			foreach ( $speca as $key => $value ) {
				?>
				<tr class="bluebg-sub fs-12">
					<td><B><?= $key ?></B></td>
					<td><B class=""><?= $product[ $key ] ?></B></td>
					<td class="text-right"><B><?= $value ?></B></td>
					<td class="text-right"><B><?= num_format( $ves[ $key ] ) ?></B></td>
					<td class="text-right"><B><?= num_format( $summa[ $key ] ) ?></B></td>
				</tr>
				<?php
				$results = $db -> getAll( "SELECT * FROM ".$sqlname."speca WHERE artikul = '$key' and identity = '$identity' ORDER BY kol DESC" );
				foreach ( $results as $datas ) {

					$scol = 0;
					$clid = 0;
					$sves = 0;

					$res    = $db -> getRow( "SELECT * FROM ".$sqlname."dogovor WHERE did = '".$datas[ 'did' ]."' and identity = '$identity'" );
					$close  = $res[ "close" ];
					$clid   = $res[ "clid" ];
					$pid    = $res[ "pid" ];
					$pdatum = $res[ "datum_plan" ];

					if ( $close != 'yes' ) {

						$scol = (float)$datas[ 'kol' ] * (float)$datas[ 'price' ];
						$sves = (float)$datas[ 'kol' ] * (int)current_dogstep( $datas[ 'did' ] ) / 100;
						?>
						<tr class="ha bgwhite">
							<td class="text-left gray2"><?= format_date_rus( $pdatum ) ?></td>
							<td>
								<div class="ellipsis fs-10">
									[<b><?= current_dogstep( $datas[ 'did' ] ) ?>%</b> ]&nbsp;<a href="javascript:void(0)" onclick="openDogovor('<?= $datas[ 'did' ] ?>','7')" title="Карточка сделки"><i class="icon-briefcase broun"></i></a><a href="javascript:void(0)" title="Быстрый просмотр" onClick="viewDogovor('<?= $datas[ 'did' ] ?>')"><B><?= current_dogovor( $datas[ 'did' ] ) ?></B></a>
								</div>
								<?php if ( $clid > 0 ) { ?>
									<br>
									<div class="ellipsis mt5 fs-09">
										<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')" title="Открыть в новом окне" class="gray"><i class="icon-commerical-building broun"></i>&nbsp;<?= current_client( $clid ) ?>
										</a>
									</div>
								<?php } else { ?>
									<br>
									<div class="ellipsis mt5 fs-09">
										<a href="javascript:void(0)" onclick="openPerson('<?= $pid ?>')" class="gray"><i class="icon-user-1 broun"></i>&nbsp;<?= current_person( $pid ) ?>
										</a>
									</div>
								<?php } ?>
							</td>
							<td class="text-right"><B><?= $datas[ 'kol' ] ?></B></td>
							<td class="text-right"><?= num_format( $sves ) ?></td>
							<td class="text-right"><?= num_format( $scol ) ?></td>
						</tr>
						<?php
					}

				}

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