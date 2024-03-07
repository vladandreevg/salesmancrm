<?php
/**
 * @license  https://salesman.pro/
 * @author   Vladislav Andreev, https://salesman.pro/
 * @charset  UTF-8
 * @version  1.0
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

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];
$period      = $_REQUEST['period'];

if ( !$per )
	$per = 'nedelya';

$user_list   = (array)$_REQUEST['user_list'];
$field       = array_values( (array)$_REQUEST['field'] );
$field_query = (array)$_REQUEST['field_query'];

$mc = $_REQUEST['mc'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$da1 = ($da1 != '') ? $da1 : $period[0];
$da2 = ($da2 != '') ? $da2 : $period[1];

$sort   = '';
$susers = '';
$kolSum = 0;
$list   = $users = $summas = $order = [];

$thisfile = basename( $_SERVER['PHP_SELF'] );

//массив выбранных пользователей
if ( !empty( $user_list ) )
	$sort .= "{$sqlname}dogovor.iduser IN (".yimplode( ",", $user_list ).") AND ";
else
	$sort .= "{$sqlname}dogovor.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") AND ";

//составляем запрос по параметрам сделок
$ar = [
	'sid',
	'close',
	'mcid'
];
for ( $i = 0; $i < count( $field ); $i++ ) {

	if ( !in_array( $field[ $i ], $ar ) && $field[ $i ] != '' )
		$sort .= "{$sqlname}dogovor.".$field[ $i ]."='".$field_query[ $i ]."' AND ";

	elseif ( $field[ $i ] == 'mcid' )
		$mc = $field_query[ $i ];

}

//print_r($field);
//print_r($field_query);

$colors = [
	"total"  => "blue",
	"new"    => "green",
	"repeat" => "red",
];

// статус сделки с дублями
$doubleSID = $db -> getOne( "SELECT sid FROM {$sqlname}dogstatus WHERE title = 'Дубль сделки' AND identity = '$identity'" );

$mycomps = Guides ::myComps();

$sort .= ($mc > 0) ? "{$sqlname}dogovor.mc = '$mc' AND " : "";


$statuses = Guides ::closeStatusPlus();

//print_r($statuses);

$totalDealsCount       = $newDealsCount = $repeatDealsCount = 0;
$totalDealsSumma       = $newDealsSumma = $repeatDealsSumma = 0;
$totalDealsMarga       = $newDealsMarga = $repeatDealsMarga = 0;
$totalDealsCredit      = $newDealsCredit = $repeatDealsCredit = 0;
$totalDealsCreditSumma = $newDealsCreditSumma = $repeatDealsCreditSumma = 0;
$totalDealsCreditMarga = $newDealsCreditMarga = $repeatDealsCreditMarga = 0;

$list       = [];
$closes     = [];
$tags       = [];
$deals      = [];
$deallist   = [];
$creditlist = [];

$q = "
	SELECT
		{$sqlname}dogovor.did as did,
		{$sqlname}dogovor.clid as clid,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}dogovor.datum as datum,
		{$sqlname}dogovor.kol as summa,
		{$sqlname}dogovor.marga as marga,
		{$sqlname}dogovor.sid as sid,
		{$sqlname}dogovor.iduser as iduser,
		{$sqlname}dogovor.close as close,
		{$sqlname}dogovor.datum_close as dclose
	FROM {$sqlname}dogovor
	WHERE
		$sort
		{$sqlname}dogovor.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' AND
		-- (SELECT title FROM {$sqlname}dogstatus WHERE sid = {$sqlname}dogovor.sid) != 'Дубль сделки' AND
		{$sqlname}dogovor.identity = '$identity'
	ORDER by {$sqlname}dogovor.did";

$da = $db -> getAll( $q );

/**
 * Проходим все сделки, созданные за период
 */
foreach ( $da as $data ) {

	$first = empty( $list[ $data['clid'] ] );

	$credit = $db -> getRow( "
		SELECT 
			SUM(summa_credit) as summa, 
			COUNT(*) as count 
		FROM {$sqlname}credit 
		WHERE 
			did = '$data[did]' AND 
			-- {$sqlname}credit.invoice_date BETWEEN '$da1 00:00:00' and '$da2 23:59:59' AND 
			do = 'on'
		" );

	$list[ $data['clid'] ][] = [
		"did"         => $data['did'],
		"title"       => $data['dogovor'],
		"datum"       => $data['datum'],
		"summa"       => $data['summa'],
		"marga"       => $data['marga'],
		"credit"      => $credit['summa'],
		"creditcount" => $credit['count'],
		"sid"         => $data['sid'],
		"first"       => $first
	];

	$deals[ $data['did'] ] = [
		"title" => $data['dogovor'],
		"summa" => $data['summa'],
		"sid"   => $credit['sid'],
		"first" => $first
	];

	// статусы закрытия
	//if ( $data[ 'sid' ] + 0 > 0 )
	//$closes[ $data[ 'sid' ] ]++;

}

/**
 * Конечные расчеты
 */
$resultdata = $resultcredit = $resultcreditsumma = [];

foreach ( $list as $clid => $item ) {

	$totalDC   = $newDC = $repeatDC = 0;
	$totalDS   = $newDS = $repeatDS = 0;
	$totalDM   = $newDM = $repeatDM = 0;
	$totalDCr  = $newDCr = $repeatDCr = 0;
	$totalDCrS = $newDCrS = $repeatDCrS = 0;

	foreach ( $item as $deal ) {

		$key = 'total';

		// «Все сделки», попадают все сделки (не важно активна она или закрыта), которые созданы в выбраном периоде, это число еще можно посмотреть виджете «новые сделки», то есть это ячейка формируется по дате создания сделки
		$totalDC++;
		$totalDS += $deal['summa'];
		$totalDM += $deal['marga'];

		if ( $deal['credit'] > 0 )
			$totalDCr++;

		if ( $deal['credit'] > 0 )
			$totalDealsCreditMarga += $deal['marga'];

		if ( $deal['credit'] > 0 )
			$totalDCrS += $deal['credit'];

		// «Новые сделки», попадают все сделки (не важно активна она или закрыта), которые созданы в выбраном периоде и имеют следующие критерии:
		// 1). Создана в этом периоде
		// 2). Создана раньше всех из всего списка сделок в клиентк
		// 3). Закрыта со статусом «Дубль»
		if ( $deal['first'] /*|| $deal['sid'] == $doubleSID*/ ) {

			$newDC++;
			$newDS += $deal['summa'];
			$newDM += $deal['marga'];

			if ( $deal['credit'] > 0 )
				$newDCr++;

			if ( $deal['credit'] > 0 )
				$newDCrS += $deal['credit'];

			if ( $deal['credit'] > 0 )
				$newDealsCreditMarga += $deal['marga'];

			$key = 'new';

		}

		// «Доп. сделки», попадают все сделки (не важно активна она или закрыта), которые созданы в выбранный период и имеют следующие критерии
		// 1). Создана в этом периоде
		// 2). Создана после новой сделки
		// 3). Не имеет результат закрытия «Дубль сделки»
		else { //if ( !$deal['first'] && $deal['sid'] != $doubleSID ) {

			$repeatDC++;
			$repeatDS += $deal['summa'];
			$repeatDM += $deal['marga'];

			if ( $deal['credit'] > 0 )
				$repeatDCr++;

			if ( $deal['credit'] > 0 )
				$repeatDCrS += $deal['credit'];

			if ( $deal['credit'] > 0 )
				$repeatDealsCreditMarga += $deal['marga'];

			$key = 'repeat';

		}

		$deallist[ $key ][] = [
			"did"    => $deal['did'],
			"title"  => $deal['title'],
			"summa"  => $deal['summa'],
			"sid"    => $deal['sid'],
			"credit" => $deal['credit'],
			"first"  => $first
		];

		if ( $deal['credit'] > 0 )
			$creditlist[ $key ][] = [
				"did"    => $deal['did'],
				"title"  => $deal['title'],
				"summa"  => $deal['summa'],
				"sid"    => $deal['sid'],
				"credit" => $deal['credit'],
				"first"  => $first
			];

	}

	$tags['totalDealsCount']       += $totalDC;
	$tags['totalDealsSumma']       += $totalDS;
	$tags['totalDealsMarga']       += $totalDM;
	$tags['totalDealsCredit']      += $totalDCr;
	$tags['totalDealsCreditSumma'] += $totalDCrS;

	$tags['newDealsCount']       += $newDC;
	$tags['newDealsSumma']       += $newDS;
	$tags['newDealsMarga']       += $newDM;
	$tags['newDealsCredit']      += $newDCr;
	$tags['newDealsCreditSumma'] += $newDCrS;

	$tags['repeatDealsCount']       += $repeatDC;
	$tags['repeatDealsSumma']       += $repeatDS;
	$tags['repeatDealsMarga']       += $repeatDM;
	$tags['repeatDealsCredit']      += $repeatDCr;
	$tags['repeatDealsCreditSumma'] += $repeatDCrS;

	$tags['totalDealsCreditMarga']     = $totalDealsCreditMarga;
	$tags['newDealsCreditMarga']       = $newDealsCreditMarga;
	$tags['repeatDealsCreditMarga']    = $repeatDealsCreditMarga;

	// данные по клиенту
	$resultdata[ $clid ] = [
		"total"                  => $totalDC,
		"new"                    => $newDC,
		"repeat"                 => $repeatDC,
		"totalcredit"            => $totalDCr,
		"newcredit"              => $newDCr,
		"repeatcredit"           => $repeatDCr,
		"totalcreditsumma"       => $totalDCrS,
		"newcreditsumma"         => $newDCrS,
		"repeatcreditsumma"      => $repeatDCrS,
		"totalDealsCreditMarga"  => $totalDealsCreditMarga,
		"newDealsCreditMarga"    => $newDealsCreditMarga,
		"repeatDealsCreditMarga" => $repeatDealsCreditMarga
	];

}

/*
$tags['repeatDealsCount']       = $tags['totalDealsCount'] - $tags['newDealsCount'];
$tags['repeatDealsSumma']       = $tags['totalDealsSumma'] - $tags['newDealsSumma'];
$tags['repeatDealsMarga']       = $tags['totalDealsMarga'] - $tags['newDealsMarga'];
$tags['repeatDealsCredit']      = $tags['totalDealsCredit'] - $tags['newDealsCredit'];
$tags['repeatDealsCreditSumma'] = $tags['totalDealsCreditSumma'] - $tags['newDealsCreditSumma'];
*/


$tags['newCountPercent']    = $tags['totalDealsCount'] > 0 ? ($tags['newDealsCount'] / $tags['totalDealsCount'] * 100) : 0;
$tags['repeatCountPercent'] = $tags['totalDealsCount'] > 0 ? ($tags['repeatDealsCount'] / $tags['totalDealsCount'] * 100) : 0;

$tags['newSummaPercent']    = $tags['totalDealsSumma'] > 0 ? ($tags['newDealsSumma'] / $tags['totalDealsSumma'] * 100) : 0;
$tags['repeatSummaPercent'] = $tags['totalDealsSumma'] > 0 ? ($tags['repeatDealsSumma'] / $tags['totalDealsSumma'] * 100) : 0;

$tags['newMargaPercent']    = $tags['totalDealsMarga'] > 0 ? ($tags['newDealsMarga'] / $tags['totalDealsMarga'] * 100) : 0;
$tags['repeatMargaPercent'] = $tags['totalDealsMarga'] > 0 ? ($tags['repeatDealsMarga'] / $tags['totalDealsMarga'] * 100) : 0;

$tags['newCreditPercent']    = $tags['totalDealsCredit'] > 0 ? ($tags['newDealsCredit'] / $tags['totalDealsCredit'] * 100) : 0;
$tags['repeatCreditPercent'] = $tags['totalDealsCredit'] > 0 ? ($tags['repeatDealsCredit'] / $tags['totalDealsCredit'] * 100) : 0;

$tags['newCreditSummaPercent']    = $tags['totalDealsCreditSumma'] > 0 ? ($tags['newDealsCreditSumma'] / $tags['totalDealsCreditSumma'] * 100) : 0;
$tags['repeatCreditSummaPercent'] = $tags['totalDealsCreditSumma'] > 0 ? ($tags['repeatDealsCreditSumma'] / $tags['totalDealsCreditSumma'] * 100) : 0;

$tags['newDealsCreditMargaPercent']    = $tags['totalDealsCreditMarga'] > 0 ? ($tags['newDealsCreditMarga'] / $tags['totalDealsCreditMarga'] * 100) : 0;
$tags['repeatDealsCreditMargaPercent'] = $tags['totalDealsCreditMarga'] > 0 ? ($tags['repeatDealsCreditMarga'] / $tags['totalDealsCreditMarga'] * 100) : 0;

/**
 * Считаем закрытые сделки
 */
$closestDeals = [];
$closestCount = 0;
$q            = "
	SELECT
		{$sqlname}dogovor.did as did,
		{$sqlname}dogovor.clid as clid,
		{$sqlname}dogovor.title as dogovor,
		{$sqlname}dogovor.datum as datum,
		{$sqlname}dogovor.kol_fact as summa,
		{$sqlname}dogovor.marga as marga,
		{$sqlname}dogovor.sid as sid,
		{$sqlname}dogovor.iduser as iduser,
		{$sqlname}dogovor.close as close,
		{$sqlname}dogovor.datum_close as dclose
	FROM {$sqlname}dogovor
	WHERE
		$sort
		{$sqlname}dogovor.datum_close BETWEEN '$da1 00:00:00' and '$da2 23:59:59' AND
		{$sqlname}dogovor.identity = '$identity'
	ORDER by {$sqlname}dogovor.kol_fact DESC";

$da = $db -> getAll( $q );

/**
 * Проходим все сделки, созданные за период
 * Таблица закрытые сделки, попадают закрытые сделки, которые были закрыты в выбраном периоде и она распределяется по
 * статусам закрытия
 */
foreach ( $da as $data ) {

	$closestDeals[] = [
		"did"     => $data['did'],
		"dogovor" => $data['dogovor'],
		"sid"     => $data['sid']
	];

	$closes[ $data['sid'] ]++;

	$closestCount++;

}

$statuslist = [];
foreach ( $statuses as $sid => $item ) {

	$statuslist[] = [
		"sid"     => $sid,
		"title"   => $item['title'],
		"count"   => $closes[ $sid ] + 0,
		"percent" => $closestCount > 0 ? round( $closes[ $sid ] / $closestCount * 100 ) : 0
	];

}

//сортируем массив по новым или закрытым сделкам в зависимости от настроек
function cmp($a, $b) {
	return $b['count'] > $a['count'];
}

usort( $statuslist, 'cmp' );

$rdata = [];
foreach ( $resultdata as $clid => $item ) {

	$client = current_client( $clid );

	foreach ( $item as $key => $value ) {

		$rdata[] = [
			"key"    => $key,
			"client" => $client,
			"value"  => $value
		];

	}

}

function cmp2($a, $b) {
	return $b['value'] > $a['value'];
}

usort( $rdata, 'cmp2' );

if ( $action == "export" ) {

	$data["var"]  = $tags;
	$data["list"] = $statuslist;

	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

	$templateFile = 'templates/RepeatedDeals.xlsx';
	$outputFile   = 'reportRepeatedDeals.xlsx';

	$TBS = new clsTinyButStrong(); // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data['list'] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

if ( !$action ) {

	//print_r($deallist);
	?>

	<div class="relativ mt20 mb20 wp95 text-center">

		<h1 class="uppercase fs-14 m0 mb10">Повторные продажи</h1>
		<div class="gray2">за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?>
			<span class="hidden1 Bold">[ <a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel для Roistat" class="blue">Excel</a> ]</span>
		</div>

	</div>

	<div class="block mt10 fs-11">

		<table>
			<thead class="sticked--top">
			<tr>
				<th class="w200"></th>
				<th class="text-left">Все сделки</th>
				<th class="text-left">Новые сделки</th>
				<th class="text-left">Доп.сделки</th>
				<th class="text-left">% новых</th>
				<th class="text-left">% повторных</th>
			</tr>
			</thead>
			<tbody>
			<tr class="ha th40">
				<td class="Bold">Кол-во сделок</td>
				<td class="hand blue havegoal" data-id="total"><?php echo (0 + $tags['totalDealsCount']) ?></td>
				<td class="hand blue havegoal" data-id="new"><?php echo (0 + $tags['newDealsCount']) ?></td>
				<td class="hand blue havegoal" data-id="repeat"><?php echo (0 + $tags['repeatDealsCount']) ?></td>
				<td><?php echo round( $tags['newCountPercent'], 0 ) ?></td>
				<td><?php echo round( $tags['repeatCountPercent'], 0 ) ?></td>
			</tr>
			<tr class="ha th40">
				<td class="Bold">Кол-во оплат</td>
				<td class="hand blue havegoalcredit" data-id="totalcredit"><?php echo (0 + $tags['totalDealsCredit']) ?></td>
				<td class="hand blue havegoalcredit" data-id="newcredit"><?php echo (0 + $tags['newDealsCredit']) ?></td>
				<td class="hand blue havegoalcredit" data-id="repeatcredit"><?php echo (0 + $tags['repeatDealsCredit']) ?></td>
				<td><?php echo round( $tags['newCreditPercent'], 0 ) ?></td>
				<td><?php echo round( $tags['repeatCreditPercent'], 0 ) ?></td>
			</tr>
			<tr class="ha th40">
				<td class="Bold">Сумма оплат</td>
				<td class="hand blue havegoalcredit" data-id="totalcredit"><?php echo num_format( $tags['totalDealsCreditSumma'] ) ?></td>
				<td class="hand blue havegoalcredit" data-id="newcredit"><?php echo num_format( $tags['newDealsCreditSumma'] ) ?></td>
				<td class="hand blue havegoalcredit" data-id="repeatcredit"><?php echo num_format( $tags['repeatDealsCreditSumma'] ) ?></td>
				<td><?php echo round( $tags['newCreditSummaPercent'], 0 ) ?></td>
				<td><?php echo round( $tags['repeatCreditSummaPercent'], 0 ) ?></td>
			</tr>
			<tr class="ha th40">
				<td class="Bold">Маржа оплаченных сделок</td>
				<td class="" data-id="totalcredit"><?php echo num_format( $tags['totalDealsCreditMarga'] ) ?></td>
				<td class="" data-id="newcredit"><?php echo num_format( $tags['newDealsCreditMarga'] ) ?></td>
				<td class="" data-id="repeatcredit"><?php echo num_format( $tags['repeatDealsCreditMarga'] ) ?></td>
				<td><?php echo round( $tags['newDealsCreditMargaPercent'], 0 ) ?></td>
				<td><?php echo round( $tags['repeatDealsCreditMargaPercent'], 0 ) ?></td>
			</tr>
			<tr class="ha th40">
				<td class="Bold">Оборот</td>
				<td><?php echo num_format( $tags['totalDealsSumma'] ) ?></td>
				<td><?php echo num_format( $tags['newDealsSumma'] ) ?></td>
				<td><?php echo num_format( $tags['repeatDealsSumma'] ) ?></td>
				<td><?php echo round( $tags['newSummaPercent'], 0 ) ?></td>
				<td><?php echo round( $tags['repeatSummaPercent'], 0 ) ?></td>
			</tr>
			<tr class="ha th40">
				<td class="Bold">Прибыль</td>
				<td><?php echo num_format( $tags['totalDealsMarga'] ) ?></td>
				<td><?php echo num_format( $tags['newDealsMarga'] ) ?></td>
				<td><?php echo num_format( $tags['repeatDealsMarga'] ) ?></td>
				<td><?php echo round( $tags['newMargaPercent'], 0 ) ?></td>
				<td><?php echo round( $tags['repeatMargaPercent'], 0 ) ?></td>
			</tr>
			</tbody>
		</table>

		<div class="space-30"></div>

		<div class="blue uppercase">
			<h2>Закрытые сделки</h2>
		</div>

		<table>
			<thead class="sticked--top">
			<tr>
				<th class="wp30 text-left">Статус закрытия</th>
				<th class="w100 text-left">Кол-во</th>
				<th class="w160 text-left">% от новых</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $statuslist as $item ) {

				print '
				<tr class="th35 closest ha" data-sid="'.$item['sid'].'">
					<td class="Bold">'.$item['title'].'</td>
					<td class="blue hand">'.$item['count'].'</td>
					<td>'.$item['percent'].'</td>
					<td></td>
				</tr>
				';

			}
			?>
			</tbody>
			<tfoot class="graybg-lite th40 Bold">
			<tr>
				<td>Итого</td>
				<td><?php echo $closestCount ?></td>
				<td></td>
				<td></td>
			</tr>
			</tfoot>
		</table>

	</div>

	<div class="space-20"></div>

	<div id="data">

		<div class="blue uppercase hand togglerbox disable--select" data-id="clientdata">
			<h2>Список клиентов <i class="icon-angle-down" id="mapic"></i></h2>
		</div>

		<div id="clientdata" class="hidden">

			<table id="resultdata">
				<thead class="sticked--top">
				<tr>
					<th class="w350">Клиент</th>
					<th class="w160">Количество доп.сделок</th>
					<th></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $rdata as $item ) {

					if ( $item['key'] == 'repeat' )
						print '
						<tr class="ha th35" data-id="'.$item['key'].'">
							<td class="w350">'.$item['client'].'</td>
							<td class="w160 '.strtr( $item['key'], $colors ).'">'.(!in_array( $item['key'], [
								'totalcreditsumma',
								'newcreditsumma',
								'repeatcreditsumma'
							] ) ? $item['value'] : num_format( $item['value'] )).'</td>
							<td></td>
						</tr>
						';

				}
				?>
				</tbody>
			</table>

		</div>

	</div>

	<div class="space-20"></div>

	<div id="ddata" class="hidden">

		<div class="blue uppercase">
			<h2>Список сделок</h2>
		</div>

		<table id="dresultdata">
			<thead class="sticked--top">
			<tr>
				<th class="w350">Сделка</th>
				<th class="w200">Сумма</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $deallist as $key => $item ) {

				foreach ( $item as $value ) {

					print '
					<tr class="ha th40 hidden" data-id="'.$key.'">
						<td class="w350">
							<div class="Bold">'.$value['title'].'</div>
							<div class="fs-09 gray2">'.$key.'</div>
						</td>
						<td class="w160 '.strtr( $key, $colors ).'">'.num_format( $value['summa'] ).'</td>
						<td></td>
					</tr>
					';

				}

			}
			?>
			</tbody>
		</table>

	</div>

	<div id="cdata" class="hidden">

		<div class="blue uppercase">
			<h2>Список оплат</h2>
		</div>

		<table id="сresultdata">
			<thead class="sticked--top">
			<tr>
				<th class="w350">Сделка</th>
				<th class="w200">Сумма</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $creditlist as $key => $item ) {

				foreach ( $item as $value ) {

					if ( $value['summa'] > 0 )
						print '
						<tr class="ha th40 hidden" data-id="'.$key.'credit">
							<td class="w350">
								<div class="Bold">'.$value['title'].'</div>
								<div class="fs-09 gray2">'.$key.'</div>
							</td>
							<td class="w160 '.strtr( $key, $colors ).'">'.num_format( $value['summa'] ).'</td>
							<td></td>
						</tr>
						';

				}

			}
			?>
			</tbody>
		</table>

	</div>

	<div class="space-20"></div>

	<div id="dcdata" class="hidden">

		<div class="blue uppercase">
			<h2>Список закрытых сделок</h2>
		</div>

		<table id="dcresultdata">
			<thead class="sticked--top">
			<tr>
				<th class="w40"></th>
				<th>Сделка</th>
				<th class="w100"></th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $closestDeals as $item ) {

				print '
				<tr class="ha th35 hidden" data-id="'.$item['did'].'" data-sid="'.$item['sid'].'">
					<td class="w40"><a href="javascript:void(0)" onclick="openDogovor(\''.$item['did'].'\')" title=""><i class="icon-briefcase-1 blue"></i></a></td>
					<td>
						<span class="ellipsis">'.$item['dogovor'].'</span>
					</td>
					<td class="w100"></td>
				</tr>
				';

			}
			?>
			</tbody>
		</table>

	</div>

	<div class="space-100"></div>

	<script>

		$('.havegoal').bind('click', function () {

			$('#dcdata').addClass('hidden');
			$('#cdata').addClass('hidden');

			var key = $(this).data('id');

			if (key !== 'total') {

				$('#dresultdata tbody tr').not('[data-id="' + key + '"]').addClass('hidden');
				$('#dresultdata tbody tr[data-id="' + key + '"]').removeClass('hidden');

			}
			else {

				$('#dresultdata tbody tr').removeClass('hidden');

			}

			$('#ddata').removeClass('hidden');

			var vtop2 = $('#ddata').position();
			$(".nano").nanoScroller({scrollTop: vtop2.top});

		});

		$('.havegoalcredit').bind('click', function () {

			$('#dcdata').addClass('hidden');
			$('#ddata').addClass('hidden');

			var key = $(this).data('id');

			if (key !== 'totalcredit') {

				$('#сresultdata tbody tr').not('[data-id="' + key + '"]').addClass('hidden');
				$('#сresultdata tbody tr[data-id="' + key + '"]').removeClass('hidden');

			}
			else {

				$('#сresultdata tbody tr').removeClass('hidden');

			}

			$('#cdata').removeClass('hidden');

			var vtop2 = $('#cdata').position();
			$(".nano").nanoScroller({scrollTop: vtop2.top});

		});

		$('.closest').bind('click', function () {

			$('#ddata').addClass('hidden');
			$('#cdata').addClass('hidden');

			var sid = $(this).data('sid');

			$('#dcresultdata tbody tr').not('[data-sid="' + sid + '"]').addClass('hidden');
			$('#dcresultdata tbody tr[data-sid="' + sid + '"]').removeClass('hidden');

			$('#dcdata').removeClass('hidden');

			var vtop2 = $('#dcdata').position();
			$(".nano").nanoScroller({scrollTop: vtop2.top});

		});

		function Export() {
			var str = $('#selectreport').serialize();
			window.open('reports/<?=$thisfile?>?action=export&' + str + '&period=' + $('#swPeriod').val());
		}

	</script>
	<?php
}
?>