<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2019.x          */
/* ============================ */
?>
<?php
error_reporting( E_ERROR );
//ini_set('display_errors', 1);

header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$reportName = basename( __FILE__ );

if ( $acs_analitics !== 'on' ) {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}
if ( stripos( $tipuser, 'Руководитель' ) === true ) {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

$user_list = (array)$_REQUEST[ 'user_list' ];
$action    = $_REQUEST[ 'action' ];
$year      = (int)$_REQUEST[ 'year' ];

if ( !$year ) {
	$year = (int)date( 'Y' );
}
$mm = date( 'm' );

$user_list = ( !empty( $user_list ) ) ? $user_list : (array)get_people( $iduser1, "yes", true );
$sort_u    = " and iduser IN (".yimplode( ",", $user_list ).")";

if ( $otherSettings['planByClosed'] )
	$dsort = " and close = 'yes'";


if ( $action == 'view' ) {

	$year = $_REQUEST[ 'year' ];
	$mon  = $_REQUEST[ 'mon' ];

	$days = (int)date( "t", mktime( 1, 0, 0, $mon, 1, $year ) );

	$list = [];

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 and acs_plan = 'on' and (secrty = 'yes' or (secrty = 'no' and DATE_FORMAT(CompEnd, '%Y-%m-%d') >= '$year-$mon-$days')) and identity = '$identity' ORDER BY title" );
	foreach ( $result as $data ) {

		$ac_importt = explode( ";", $data[ 'acs_import' ] );

		$kfact = 0;
		$mfact = 0;

		//плановые показатели для текущего сотрудника
		$rplan = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year = '$year' and mon = '$mon' and iduser = '".$data[ 'iduser' ]."' and identity = '$identity'" );

		//список сделок сотрудника
		$sub = ( $ac_importt[ 19 ] == 'on' ) ? " and iduser = '$data[iduser]'" : " and iduser IN (".yimplode( ",", (array)get_people( $data[ 'iduser' ], "yes" ) ).")";

		//фактические показатели
		$dolya = 0;

		if ( !$otherSettings['planByClosed'] ) {
			$result3 = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do='on' and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$mon' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sub and identity = '$identity') $sub and identity = '$identity' ORDER by did" );
		}
		else {
			$result3 = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do='on' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sub and DATE_FORMAT(datum_close, '%Y-%c') = '$year-$mon' and close = 'yes' and identity = '$identity') $sub and identity = '$identity' ORDER by did" );
		}

		foreach ( $result3 as $data3 ) {

			//расчет процента размера платежа от суммы сделки
			$result4 = $db -> getRow( "SELECT kol, marga FROM ".$sqlname."dogovor WHERE did = '".$data3[ 'did' ]."' and identity = '$identity'" );
			$kolfact = $result4[ "kol" ];//сумма всей сделки
			$marfact = $result4[ "marga" ];//сумма всей сделки

			//% оплаченной суммы от суммы по договору
			$dolya = ( $kolfact > 0 ) ? $data3[ 'summa_credit' ] / $kolfact : 0;

			$kfact += $data3[ 'summa_credit' ];
			$mfact += $marfact * $dolya;

		}

		$list[ $data[ 'iduser' ] ] = [
			"avatar"   => $data[ 'avatar' ] ? "./cash/avatars/".$data[ 'avatar' ] : "/assets/images/noavatar.png",
			"color"    => $data[ 'secrty' ] == 'yes' ? "" : "gray",
			"tip"      => $data[ 'tip' ],
			"oplan"    => $rplan[ 'kol' ],
			"ofact"    => $kfact,
			"opercent" => ( $rplan[ 'kol' ] > 0 ) ? round( $kfact / $rplan[ 'kol' ] * 100, 2 ) : 0,
			"mplan"    => $rplan[ 'marga' ],
			"mfact"    => $mfact,
			"mpercent" => ( $rplan[ 'marga' ] > 0 ) ? round( $mfact / $rplan[ 'marga' ] * 100, 2 ) : 0,
		];

	}

	//выведем табличное представление данных по сотрудникам
	$m1 = $mon - 1;
	$m2 = $mon + 1;
	?>
	<div class="zagolovok_rep">

		<?php if ( $m1 > 0 ) { ?>
		<A href="javascript:void(0)" onclick="$('#sub').load('reports/<?= $reportName ?>?action=view&mon=<?= ( $mon - 1 ) ?>&year=<?= $year ?>')"><i class="icon-angle-double-left"></i><?= ru_mon( $mon - 1 ) ?></A>
		<?php } ?>
		&nbsp;&nbsp;<SPAN class="date2"><?= ru_mon( $mon ) ?></SPAN>&nbsp;&nbsp;
		<?php if ( $m2 <= 12 ) { ?>
		<A href="javascript:void(0)" onclick="$('#sub').load('reports/<?= $reportName ?>?action=view&mon=<?= ( $mon + 1 ) ?>&year=<?= $year ?>')"><?= ru_mon( $mon + 1 ) ?><i class="icon-angle-double-right"></i></A>
		<?php } ?>

	</div>

	<br>

	<table>
		<thead>
		<tr class="header_contaner">
			<th class="text-left">
				<div class="ellipsis">Менеджер</div>
			</th>
			<th class="w120 text-right"><b>Оборот</b></th>
			<th class="w70 text-right greenbg">Выполнение</th>
			<th class="w120 text-right"><b>Маржа</b></th>
			<th class="w70 text-right greenbg">Выполнение</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $list as $iduser => $item ) {

			?>
			<tr class="ha roww th50" id="roww_<?= $iduser ?>">
				<td>
					<div>
						<div class="avatar pull-left" style="background: url(<?= $item[ 'avatar' ] ?>); background-size:cover; margin-right:5px" title="<?= current_user( $iduser ) ?>"></div>
						<b class="<?= $item[ 'color' ] ?> fs-12"><?= current_user( $iduser ) ?></b>
					</div>
					<div class="mt10 fs-09">
						<a href="javascript:void(0)" onclick="loadDogs('<?= $iduser ?>','<?= $mon ?>','<?= $year ?>')"><i class="icon-briefcase broun" title="Сделки"></i>Сделки</a>
						<a href="javascript:void(0)" onclick="loadUser('<?= $iduser ?>','<?= $year ?>')"><i class="icon-calendar-1 blue" title="Сделки"></i>Итого <?= $year ?>
						</a>
					</div>
				</td>
				<td class="text-right <?= ( $item[ 'ofact' ] > 0 ? "" : "gray" ) ?>">
					<div class="Bold"><?= num_format( $item[ 'ofact' ] ) ?></div>
					<div class="gray fs-07"><?= num_format( $item[ 'oplan' ] ) ?></div>
				</td>
				<td class="text-right greenbg-sub <?= ( $item[ 'opercent' ] > 0 ? "" : "gray" ) ?>">
					<b><?= num_format( $item[ 'opercent' ] ) ?></b>%
				</td>
				<td class="text-right <?= ( $item[ 'mfact' ] > 0 ? "" : "gray" ) ?>">
					<div class="Bold"><?= num_format( $item[ 'mfact' ] ) ?></div>
					<div class="gray fs-07"><?= num_format( $item[ 'mplan' ] ) ?></div>
				</td>
				<td class="text-right greenbg-sub <?= ( $item[ 'mpercent' ] > 0 ? "" : "gray" ) ?>">
					<b><?= num_format( $item[ 'mpercent' ] ) ?></b>%
				</td>
			</tr>
			<tr id="user_<?= $iduser ?>" class="hidden current">
				<td colspan="5">
					<div class="userdata_<?= $iduser ?>"></div>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

	<div id="sub2"></div>

	<br>
	<?php

	exit();

}
if ( $action == 'byuser' ) {

	$iduser = $_REQUEST[ 'iduser' ];

	$list   = [];
	$psumma = $fsumma = $pmarga = $fmarga = 0;

	$result     = $db -> getOne( "SELECT acs_import FROM ".$sqlname."user WHERE iduser > 0 and acs_plan = 'on' and (DATE_FORMAT(CompEnd, '%Y-%m') != '$year-$mm' or CompEnd = '0000-00-00') and identity = '$identity' ORDER BY title" );
	$ac_importt = explode( ";", (string)$result );

	$sort = ( $ac_importt[ 19 ] == 'on' ) ? " and iduser = '$iduser'" : " and iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";

	for ( $m = 1; $m <= 12; $m++ ) {

		$kfact = 0;
		$mfact = 0;

		//плановые показатели для текущего сотрудника
		$rplan = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year = '$year' and mon = '$m' and iduser = '$iduser' and identity = '$identity'" );

		//фактические показатели
		$dolya = 0;

		if ( !$otherSettings['planByClosed'] )
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$m' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sort and identity = '$identity') $sort and identity = '$identity' ORDER by did" );

		else
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sort_u $dsort and DATE_FORMAT(datum_close, '%Y-%c') = '$year-$m' and identity = '$identity') $sort and identity = '$identity' ORDER by did" );

		foreach ( $result as $data ) {

			$kfact += (int)pre_format( $data[ 'summa_credit' ] );

			//расчет процента размера платежа от суммы сделки
			$deal = $db -> getRow( "SELECT kol, marga FROM ".$sqlname."dogovor WHERE did = '".$data[ 'did' ]."' and identity = '$identity'" );

			//% оплаченной суммы от суммы по договору
			$dolya = ( $deal[ 'kol' ] > 0 ) ? $data[ 'summa_credit' ] / $deal[ 'kol' ] : 0;

			$mfact += (int)( $deal[ 'marga' ] * $dolya );

		}

		$list[ $m ] = [
			"oplan"    => $rplan[ 'kol' ],
			"ofact"    => $kfact,
			"opercent" => ( $rplan[ 'kol' ] > 0 ) ? round( $kfact / $rplan[ 'kol' ] * 100, 2 ) : 0,
			"mplan"    => $rplan[ 'marga' ],
			"mfact"    => $mfact,
			"mpercent" => ( $rplan[ 'marga' ] > 0 ) ? round( $mfact / $rplan[ 'marga' ] * 100, 2 ) : 0,
		];

	}
	?>
	<table style="border:2px solid #7B99B7">
		<thead>
		<tr class="bluebg-sub">
			<th class="w70 text-center"><b>Мес.</b></th>
			<th class="text-center">
				<div>Оборот</div>
			</th>
			<th class="w60 text-center"><b>%</b></th>
			<th class="text-center"><b>Маржа</b></th>
			<th class="w60 text-center">
				<i class="icon-up-open blue hand" onclick="hideUser('<?= $iduser ?>')" title="Скрыть"></i>
			</th>
		</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $list as $mon => $item ) {
			?>
			<tr class="th50">
				<td><?= ru_mon( $mon ) ?></td>
				<td class="text-right <?= ( $item[ 'ofact' ] > 0 ? "" : "gray" ) ?>">
					<div class="Bold"><?= num_format( $item[ 'ofact' ] ) ?></div>
					<div class="gray fs-07"><?= num_format( $item[ 'oplan' ] ) ?></div>
				</td>
				<td class="text-right greenbg-sub <?= ( $item[ 'opercent' ] > 0 ? "" : "gray" ) ?>">
					<b><?= num_format( $item[ 'opercent' ] ) ?></b>
				</td>
				<td class="text-right <?= ( $item[ 'mfact' ] > 0 ? "" : "gray" ) ?>">
					<div class="Bold"><?= num_format( $item[ 'mfact' ] ) ?></div>
					<div class="gray fs-07"><?= num_format( $item[ 'mplan' ] ) ?></div>
				</td>
				<td class="text-right greenbg-sub <?= ( $item[ 'mpercent' ] > 0 ? "" : "gray" ) ?>">
					<b><?= num_format( $item[ 'mpercent' ] ) ?></b>
				</td>
			</tr>
			<?php

			$psumma += $item[ 'oplan' ];
			$fsumma += $item[ 'ofact' ];
			$pmarga += $item[ 'mplan' ];
			$fmarga += $item[ 'mfact' ];

		}

		$obperc = ( $psumma > 0 ) ? round( $fsumma / $psumma * 100, 2 ) : 0;
		$maperc = ( $pmarga > 0 ) ? round( $fmarga / $pmarga * 100, 2 ) : 0;
		?>
		</tbody>
		<tfoot>
		<tr class="th40 graybg-sub">
			<td>Итого</td>
			<td class="text-right"><b><?= num_format( $fsumma ) ?></b></td>
			<td class="text-right"><b><?= num_format( $obperc ) ?>%</b></td>
			<td class="text-right"><b><?= num_format( $fmarga ) ?></b></td>
			<td class="text-right"><b><?= num_format( $maperc ) ?>%</b></td>
		</tr>
		</tfoot>
	</table>
	<?php

	exit();

}
if ( $action == 'dogs' ) {

	$iduser = $_REQUEST[ 'iduser' ];
	$mon    = $_REQUEST[ 'mon' ];

	$acs_importt = $db -> getOne( "SELECT acs_import FROM ".$sqlname."user WHERE iduser = '$iduser' and acs_plan = 'on' and identity = '$identity' ORDER BY title" );
	$ac_importt  = explode( ";", (string)$acs_importt );

	$sort = ( $ac_importt[ 19 ] == 'on' ) ? " and iduser = '$iduser'" : " and iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";

	?>
	<table style="border:2px solid #7B99B7">
		<thead>
		<tr class="bluebg-sub fs-07">
			<TH class="w80 text-center">Дата</TH>
			<TH class="text-center">Клиент / Сделка</TH>
			<TH class="w40 text-center">Этап</TH>
			<TH class="w120 text-center">Оплата, <?= $valuta ?></TH>
			<TH class="w40 text-center">
				<i class="icon-up-open blue hand" onclick="hideUser('<?= $iduser ?>')" title="Скрыть"></i>
			</TH>
		</tr>
		</thead>
		<tbody>
		<?php
		if ( !$otherSettings['planByClosed'] )
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$mon' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sort and identity = '$identity') $sort and identity = '$identity' ORDER by invoice_date DESC" );
		else
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sort $dsort and DATE_FORMAT(datum_close, '%Y-%c') = '$year-$mon' and identity = '$identity') $sort and identity = '$identity' ORDER by invoice_date DESC" );

		foreach ( $result as $data ) {

			if ( $data[ 'clid' ] > 0 )
				$client = '<a href="javascript:void(0)" onclick="openClient(\''.$data[ 'clid' ].'\')" title="Просмотр: '.current_client( $data[ 'clid' ] ).'"><i class="icon-commerical-building broun"></i>'.current_client( $data[ 'clid' ] ).'</b></a>';

			elseif ( $data[ 'pid' ] > 0 && $data[ 'clid' ] < 1 )
				$client = '<a href="javascript:void(0)" onclick="openPerson(\''.$data[ 'pid' ].'\')" title="Карточка: '.current_person( $data[ 'pid' ] ).'"><i class="icon-user-1 blue"></i>'.current_person( $data[ 'pid' ] ).'</b></a>';


			//найдем долю оплаченного счета
			$resultd    = $db -> getRow( "SELECT * FROM ".$sqlname."dogovor WHERE did = '".$data[ 'did' ]."' and identity = '$identity'" );
			$datum      = $resultd[ "datum" ];
			$datum_plan = $resultd[ "datum_plan" ];
			$datum_fact = $resultd[ "datum_close" ];
			$kol        = $resultd[ "kol" ];
			$marga      = $resultd[ "marga" ];
			$iduser     = $resultd[ "iduser" ];
			$close      = $resultd[ "close" ];

			$dolya   = ( $kol > 0 ) ? $data[ 'summa_credit' ] / $kol : 0;
			$marga_i = $marga * $dolya;

			$ic = ( $close == 'yes' ) ? '<i class="icon-lock green"></i>' : '<i class="icon-briefcase blue"></i>';
			?>
			<tr class="th35 middle graybg-sub">
				<td class="text-left uppercase fs-09 Bold" colspan="5" onclick="viewUser('<?= $data[ 'iduser' ] ?>');">
					<i class="icon-user-1 blue"></i><?= current_user( $iduser, "yes" ) ?>
				</td>
			</tr>
			<tr class="ha th35 middle">
				<td class="text-left fs-09">
					<div class="gray" title="Дата создания"><?= format_date_rus( $datum ) ?></div>
					<div class="Bold" title="Плановая дата"><?= format_date_rus( $datum_plan ) ?></div>
					<?= ( $close == 'yes' ? '<div class="red" title="Дата закрытия">'.format_date_rus( $datum_fact ).'</div>' : '' ) ?>
				</td>
				<td>
					<div class="ellipsis"><b><?= $client ?></b></div>
					<br>
					<div class="ellipsis">
						<a href="javascript:void(0)" onclick="openDogovor('<?= $data[ 'did' ] ?>')" title="Карточка"><?= $ic ?><?= current_dogovor( $data[ 'did' ] ) ?></a>
					</div>
				</td>
				<td class="text-center"><?= current_dogstep( $data[ 'did' ] ) ?>%</td>
				<td class="text-right">
					<div class="Bold" title="Сумма оплаты"><?= num_format( $data[ 'summa_credit' ] ) ?></div>
					<div class="gray fs-07" title="Сумма сделки">из <?= num_format( $kol ) ?></div>
				</td>
				<td></td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<?php

	exit();

}

if ( $action == 'export' ) {

	$list = $u = [];

	$re = $db -> query( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 $sort and secrty = 'yes' and acs_plan = 'on' and identity = '$identity' ORDER BY title" );
	while ( $da = $db -> fetch( $re ) ) {

		$ac_importt = explode( ";", (string)$db -> getOne( "SELECT acs_import FROM ".$sqlname."user WHERE iduser = '$da[iduser]' and identity = '$identity' ORDER BY title" ) );
		$sort       = ( $ac_importt[ 19 ] == 'on' ) ? " and iduser = '$da[iduser]'" : " and iduser IN (".yimplode( ",", (array)get_people( $da[ 'iduser' ], "yes" ) ).")";

		for ( $m = 1; $m <= 12; $m++ ) {

			$kfact = 0;
			$mfact = 0;

			//плановые показатели для текущего сотрудника
			$rplan = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year = '$year' and mon = '$m' and iduser = '$da[iduser]' and identity = '$identity'" );

			//фактические показатели
			$dolya = 0;

			if ( !$otherSettings['planByClosed'] )
				$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$m' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sort and identity = '$identity') $sort and identity = '$identity' ORDER by did" );

			else
				$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $sort_u $dsort and DATE_FORMAT(datum_close, '%Y-%c') = '$year-$m' and identity = '$identity') $sort and identity = '$identity' ORDER by did" );

			foreach ( $result as $data ) {

				$kfact += (float)pre_format( $data['summa_credit'] );

				//расчет процента размера платежа от суммы сделки
				$deal = $db -> getRow( "SELECT kol, marga FROM ".$sqlname."dogovor WHERE did = '".$data[ 'did' ]."' and identity = '$identity'" );

				//% оплаченной суммы от суммы по договору
				$dolya = ( $deal[ 'kol' ] > 0 ) ? $data[ 'summa_credit' ] / $deal[ 'kol' ] : 0;

				$mfact += (float)($deal['marga'] * $dolya);

			}

			$list[ $da[ 'iduser' ] ][ $m ] = [
				"oborot" => [
					"oplan"    => $rplan[ 'kol' ],
					"ofact"    => $kfact,
					"opercent" => ( $rplan[ 'kol' ] > 0 ) ? round( $kfact / $rplan[ 'kol' ] * 100, 2 ) : 0
				],
				"marga"  => [
					"mplan"    => $rplan[ 'marga' ],
					"mfact"    => $mfact,
					"mpercent" => ( $rplan[ 'marga' ] > 0 ) ? round( $mfact / $rplan[ 'marga' ] * 100, 2 ) : 0
				]
			];

		}

		$u[ $da[ 'iduser' ] ] = $da[ 'title' ];

	}

	$plist = [];
	foreach ( $list as $iduser => $items ) {

		$p = [
			"user" => $u[ $iduser ]
		];

		// проходим по месяцам
		foreach ( $items as $month => $item ) {

			$p[ $month.'_oplan' ]    = pre_format( $item[ 'oborot' ][ 'oplan' ] + 0 );
			$p[ $month.'_ofact' ]    = pre_format( $item[ 'oborot' ][ 'ofact' ] + 0 );
			$p[ $month.'_opercent' ] = pre_format( $item[ 'oborot' ][ 'opercent' ] + 0 );
			$p[ $month.'_mplan' ]    = pre_format( $item[ 'marga' ][ 'mplan' ] + 0 );
			$p[ $month.'_mfact' ]    = pre_format( $item[ 'marga' ][ 'mfact' ] + 0 );
			$p[ $month.'_mpercent' ] = pre_format( $item[ 'marga' ][ 'mpercent' ] + 0 );

		}

		$plist[] = $p;

	}

	//print array2string($plist, "<br>", str_repeat("&nbsp;", 5));
	
	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

	$data = ["list" => $plist];

	$templateFile = 'templates/planYearTemp.xlsx';
	$outputFile   = 'planYearTemp'.$year.'.xlsx';

	$TBS = new clsTinyButStrong(); // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data[ 'list' ] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

}

if ( $action == '' ) {

	$delta  = $datam = [];
	$list   = [];
	$psumma = $fsumma = $pmarga = $fmarga = 0;

	//Находим руководителя организации
	$director = $db -> getOne( "SELECT iduser FROM ".$sqlname."user WHERE tip = 'Руководитель организации' and identity = '$identity' LIMIT 1" );

	if ( $director < 1 ) {

		print '<div class="warning">Внимание! Не найден руководитель организации</div>';
		exit();

	}

	for ( $m = 1; $m <= 12; $m++ ) {

		$mon = $m;

		$kfact = 0;
		$mfact = 0;
		$dolya = 0;

		//плановые показатели для текущего сотрудника
		$rplan = $db -> getRow( "SELECT SUM(kol_plan) as kol, SUM(marga) as marga FROM ".$sqlname."plan WHERE year = '$year' and mon = '$mon' and iduser = '$director' and identity = '$identity'" );

		if ( !$otherSettings['planByClosed'] )
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and DATE_FORMAT(invoice_date, '%Y-%c') = '$year-$mon' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 and identity = '$identity') $sort_u and identity = '$identity' ORDER by did" );
		else
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and did IN (SELECT did FROM ".$sqlname."dogovor WHERE did > 0 and DATE_FORMAT(datum_close, '%Y-%c') = '$year-$mon' $dsort and identity = '$identity') $sort_u and identity = '$identity' ORDER by did" );

		foreach ( $result as $data ) {

			$kfact += (int)pre_format( $data[ 'summa_credit' ] );

			//расчет процента размера платежа от суммы сделки
			$deal = $db -> getRow( "SELECT kol, marga FROM ".$sqlname."dogovor WHERE did = '".$data[ 'did' ]."' and identity = '$identity'" );

			//% оплаченной суммы от суммы по договору
			$dolya = ( $deal[ 'kol' ] > 0 ) ? $data[ 'summa_credit' ] / $deal[ 'kol' ] : 0;

			$mfact += (int)( $deal[ 'marga' ] * $dolya );

		}

		$list[ $m ] = [
			"oplan"    => $rplan[ 'kol' ],
			"ofact"    => $kfact,
			"opercent" => ( $rplan[ 'kol' ] > 0 ) ? round( $kfact / $rplan[ 'kol' ] * 100, 2 ) : 0,
			"mplan"    => $rplan[ 'marga' ],
			"mfact"    => $mfact,
			"mpercent" => ( $rplan[ 'marga' ] > 0 ) ? round( $mfact / $rplan[ 'marga' ], 2 ) * 100 : 0,
		];

		$datas[] = '{Тип:"Оборот план","Месяц":"'.ru_month( $mon ).'","Сумма":"'.$rplan[ 'kol' ].'"}';
		$datam[] = '{Тип:"Маржа план","Месяц":"'.ru_month( $mon ).'","Сумма":"'.$rplan[ 'marga' ].'"}';

		$datas[] = '{Тип:"Оборот","Месяц":"'.ru_month( $mon ).'","Сумма":"'.$kfact.'"}';
		$datam[] = '{Тип:"Маржа","Месяц":"'.ru_month( $mon ).'","Сумма":"'.$mfact.'"}';

		$order[] = '"'.ru_month( $mon ).'"';

	}

	$datas = implode( ",", $datas );
	$datam = implode( ",", $datam );
	$order = implode( ",", $order );

	$datas .= ",".$datam;

	$y1 = $year + 1;
	$y2 = $year - 1;
	?>

	<style>
		.dimple-custom-series-line {
			stroke-width     : 1;
			stroke-dasharray : 2;
		}
		.dimple-custom-axis-line {
			stroke       : #CFD8DC !important;
			stroke-width : 1.1;
		}
		.dimple-custom-gridline {
			stroke-width     : 1;
			stroke-dasharray : 2;
			fill             : none;
			stroke           : #CFD8DC !important;
		}
	</style>

	<DIV class="zagolovok_rep text-center mt10">

		<h1 class="uppercase fs-14 m0 mb10">Выполнение планов</h1>
		<div class="fs-07 gray2 mt5">Комплексный годовой [
			<a href="javascript:void(0)" onclick="Export()" style="color:blue">Экспорт в Excel</a> ]
		</div>

	</DIV>

	<div id="graf" class="mt20 mb20 block" style="height:250px">

		<div id="chart" class="p5"></div>
		<script src="/assets/js/dimple.js/dimple.min.js"></script>
		<script>

			var width = $('#contentdiv').width() - 40;
			var height = 250;
			var svg = dimple.newSvg("#chart", width, height);
			var data = [<?=$datas?>];

			var myChart = new dimple.chart(svg, data);

			myChart.setBounds(100, 0, width - 50, height - 40);

			var x = myChart.addCategoryAxis("x", ["Месяц", "Тип"]);
			x.addOrderRule([<?=$order?>]);//порядок вывода, иначе группирует
			x.showGridlines = true;

			var y = myChart.addMeasureAxis("y", "Сумма");
			y.showGridlines = false;//скрываем линии
			y.ticks = 5;//шаг шкалы по оси y

			var s = myChart.addSeries(["Тип"], dimple.plot.line);
			s.lineWeight = 2;
			s.lineMarkers = true;
			s.stacked = false;
			s.barGap = .5;

			myChart.floatingBarWidth = 10;
			myChart.ease = "bounce";
			myChart.staggerDraw = true;

			myChart.barGap = 0.5;
			myChart.addLegend(0, 0, width - 35, 0, "right");
			myChart.setMargins(60, 20, 40, 35);

			myChart.assignColor("План", "green");

			myChart.assignColor("Оборот", "#1565C0", "#1565C0");
			myChart.assignColor("Оборот план", "#90CAF9", "#90CAF9");
			myChart.assignColor("Маржа", "#B71C1C", "#B71C1C");
			myChart.assignColor("Маржа план", "#EF9A9A", "#EF9A9A");

			myChart.draw(1000);

			y.tickFormat = ",.2f";

			s.shapes.style("opacity", function (d) {
				return (d.y === null ? 0 : 0.8);
			});

			y.titleShape.remove();
			x.titleShape.remove();

			$(window).bind('resizeEnd', function () {
				myChart.draw(0, true);
			});

		</script>

	</div>

	<div class="flex-container box--child mt20 pt20">

		<div class="flex-string wp45">

			<div class="zagolovok_rep">
				<b>Выполнение плана за
					<A href="javascript:void(0)" onclick="refresh('contentdiv','reports/<?= $reportName ?>?year=<?= $y2 ?>');"><i class="icon-angle-double-left"></i></A><SPAN class="date2"><?= $year ?></SPAN><A href="javascript:void(0)" onclick="refresh('contentdiv','reports/<?= $reportName ?>?year=<?= $y1 ?>');"><i class="icon-angle-double-right"></i></A>год</b>
			</div>

			<table id="planfact" class="middle">
				<thead>
				<tr class="header_contaner">
					<th class="w70 text-right"><b>Мес.</b></th>
					<th class="text-right"><b>Оборот</b></th>
					<th class="w60 text-right greenbg">%</th>
					<th class="text-right"><b>Маржа</b></th>
					<th class="w60 text-right greenbg">%</th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $list as $m => $item ) {
					?>
					<tr class="ha hand th50" onclick="loadSum('<?= $m ?>','<?= $year ?>')" id="mon_<?= $m ?>">
						<td><?= ru_mon( $m ) ?></td>
						<td class="text-right hand">
							<div class="Bold"><?= num_format( $item[ 'ofact' ] ) ?></div>
							<div class="gray2 fs-07"><?= num_format( $item[ 'oplan' ] ) ?></div>
						</td>
						<td class="text-right greenbg-sub"><b><?= num_format( $item[ 'opercent' ] ) ?></b></td>
						<td class="text-right">
							<div class="Bold"><?= num_format( $item[ 'mfact' ] ) ?></div>
							<div class="gray2 fs-07"><?= num_format( $item[ 'mplan' ] ) ?></div>
						</td>
						<td class="text-right greenbg-sub"><b><?= num_format( $item[ 'mpercent' ] ) ?></b></td>
					</tr>
					<?php

					$psumma += $item[ 'oplan' ];
					$fsumma += $item[ 'ofact' ];
					$pmarga += $item[ 'mplan' ];
					$fmarga += $item[ 'mfact' ];

				}

				$obperc = ( $psumma > 0 ) ? round( $fsumma / $psumma * 100, 2 ) : 0;
				$maperc = ( $pmarga > 0 ) ? round( $fmarga / $pmarga * 100, 2 ) : 0;
				?>
				</tbody>
				<tfoot>
				<tr class="th40 graybg-sub">
					<td>Итого</td>
					<td class="text-right"><b><?= num_format( $fsumma ) ?></b></td>
					<td class="text-right"><b><?= num_format( $obperc ) ?></b></td>
					<td class="text-right"><b><?= num_format( $fmarga ) ?></b></td>
					<td class="text-right"><b><?= num_format( $maperc ) ?></b></td>
				</tr>
				</tfoot>
			</table>

		</div>
		<div class="flex-string wp55 pl10">

			<div id="sub"></div>

		</div>

	</div>

	<div class="infodiv">

		<h2>Методика расчетов</h2>

		<?php
		if ( !$otherSettings['credit'] )
			$text = 'В отчет попадают ВСЕ <b>активные</b> сделки и <b>закрытые</b> сделки, Дата.Закрытия которых совпадают с указанным месяцем';
		else
			$text = ( !$otherSettings['planByClosed'] ) ? 'Расчеты строятся по <b>оплаченным счетам в периоде</b> в соответствии с настройками системы' : 'Расчеты строятся по <b>оплаченным счетам</b> в Сделках, <b>закрытых в отчетном периоде</b> в соответствии с настройками системы';
		?>

		<ul>
			<li>Учитываются сотрудники, работающие в отчетном периоде</li>
			<li>Учитываются сотрудники, имеющие план продаж в настройках</li>
			<li>Учитываются результаты работы подчиненных, если не указано, что сотрудник имеет Индивидуальный план продаж</li>
			<li><?= $text ?></li>
		</ul>
	</div>

	<div class="space-60"></div>
	<?php
}
?>

<script>

	loadSum('<?=date( 'n' )?>', '<?=$year?>');

	function loadSum(mon, year) {

		$('#planfact tr').removeClass('bluebg2');
		$('#planfact #mon_' + mon).addClass('bluebg2');
		$('#sub').load('reports/<?=$reportName?>?action=view&mon=' + mon + '&year=' + year).append('<img src=/assets/images/loading.gif>');

	}

	function loadUser(id, year) {

		var url = 'reports/<?=$reportName?>?action=byuser&iduser=' + id + '&year=' + year;

		$('.current').addClass('hidden');
		$('#user_' + id).removeClass('hidden');
		$('.roww').removeClass('bluebg2');
		$('#roww_' + id).addClass('bluebg2');
		$('.userdata_' + id).empty().append('<img src="/assets/images/loading.gif">');

		$.post(url, function (data) {
			$('.userdata_' + id).html(data);
		});
	}

	function hideUser(id) {
		$('.userdata_' + id).empty();
		$('#user_' + id).addClass('hidden');
	}

	function loadDogs(id, mon, year) {

		var url = 'reports/<?=$reportName?>?action=dogs&iduser=' + id + '&mon=' + mon + '&year=' + year;

		$('.current').addClass('hidden');
		$('#user_' + id).removeClass('hidden');
		$('.row').removeClass('bluebg2');
		$('#row_' + id).addClass('bluebg2');
		$('.userdata_' + id).empty().append('<img src="/assets/images/loading.gif">');

		$.post(url, function (data) {
			$('.userdata_' + id).html(data);
		});
	}

	function Export() {

		var str = $('#selectreport').serialize();
		window.open('reports/<?=$reportName?>?action=export&' + str + '&year=' + <?=$year?>);

	}

</script>