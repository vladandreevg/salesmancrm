<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
/* Developer: Iskopaeva Liliya  */


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

$user_list = (array)$_REQUEST['user_list'];
$period    = $_REQUEST['period'];

$mc = $_REQUEST['mc'];

$clientTip       = (array)$_REQUEST['clientTip'];
$clientTerritory = (array)$_REQUEST['clientTerritory'];
$clientPath      = (array)$_REQUEST['clientPath'];
$clientStatus    = (array)$_REQUEST['clientStatus'];

$fields       = (array)$_REQUEST['field'];
$field_query = (array)$_REQUEST['field_query'];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );
$da1    = ($da1 != '') ? $da1 : $period[0];
$da2    = ($da2 != '') ? $da2 : $period[1];


$sort   = '';
$susers = '';

$itogo = $list = $users = [];

//фильтр выбранных пользователей
if ( !empty( $user_list ) ) {
	$sort .= "cr.iduser IN (".yimplode( ",", $user_list ).") and ";
}
else {
	$sort .= "cr.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and ";
}

//фильтр по типам клиентов
if ( !empty( $clientTip ) ) {
	$sort .= "cc.type IN (".yimplode( ",", $clientTip, "'" ).") and ";
}

//фильтр по территории
if ( !empty( $clientTerritory ) ) {
	$sort .= "cc.territory IN (".implode( ",", $clientTerritory ).") and ";
}

//фильтр по источнику
if ( !empty( $clientPath ) ) {
	$sort .= "cc.clientpath IN (".implode( ",", $clientPath ).") and ";
}


//фильтр по статусу
$status = [
	'on' => 'Оплачен',
	'no' => 'Выставлен'
];
if ( !empty( $clientStatus ) ) {
	$sort .= "cr.do IN (".yimplode( ",", $clientStatus, "'" ).") and ";
}

//составляем запрос по параметрам сделок (не используется)
$ar = [
	'sid',
	'close',
	'mcid'
];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && $field != '' ) {
		$sort .= " deal.{$field} = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

//print $mc;
$mycomps = Guides ::myComps();

//Этапы, соответствующие 60,80,90,100
$steps = $db -> getCol( "SELECT idcategory FROM ".$sqlname."dogcategory WHERE CAST(title AS UNSIGNED) >= '60' and identity = '$identity' ORDER BY title" );

$sort .= ($mc > 0) ? "cr.rs IN (SELECT id FROM ".$sqlname."mycomps_recv WHERE ".$sqlname."mycomps_recv.cid = '$mc') and " : "";
$sort .= $da1 != "" ? "cr.datum BETWEEN '$da1 00:00:00' and '$da2 23:59:59' and" : "";

$q = "
	SELECT
		cr.did as did,
		cr.iduser as iduser,
		cr.summa_credit as summa,
		cr.datum_credit as dplan,
		cr.invoice_date as dfact,
		cr.do as do,
		DATE_FORMAT(cr.datum, '%Y-%m-%d') as idatum,
		cr.invoice as invoice,
		cr.clid as clid,
		deal.title as dogovor,
		deal.datum as datum,
		deal.kol as dsumma,
		COALESCE(deal.close, 'no') as close,
		dc.title as step,
		dr.title as direction,
		cc.title as client,
		(SELECT cid FROM ".$sqlname."mycomps_recv WHERE ".$sqlname."mycomps_recv.id = cr.rs) as mc,
		us.title as user
	FROM ".$sqlname."credit `cr`
		LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
		LEFT JOIN ".$sqlname."dogcategory `dc` ON deal.idcategory = dc.idcategory
		LEFT JOIN ".$sqlname."clientcat `cc` ON cc.clid = cr.clid
		LEFT JOIN ".$sqlname."user `us` ON us.iduser = cr.iduser
		LEFT JOIN ".$sqlname."direction `dr` ON deal.direction = dr.id
	WHERE
		cr.crid > 0 and
		$sort
		deal.close != 'yes' and
		deal.idcategory IN (".implode( ",", $steps ).") and
		cr.identity = '$identity'
	ORDER by cr.datum_credit";

$da = $db -> getAll( $q );

if ( $action == "export" ) {

	$dlist = [];

	foreach ( $da as $data ) {

		$dlist[] = [
			"id"        => $data['did'],
			"invoice"   => $data['invoice'],
			"idatum"    => $data['idatum']." 12:00:00",
			"datum"     => $data['datum']." 12:00:00",
			"summa"     => $data['summa'],
			"dsumma"    => $data['dsumma'],
			"dmarga"    => $data['dmarga'],
			"deal"      => $data['dogovor'],
			"direction" => $data['direction'],
			"client"    => $data['client'],
			"user"      => $data['user'],
			"dfact"     => $data['dfact'] != '' && $data['dfact'] != '0000-00-00' ? $data['dfact']." 12:00:00" : '',
			"dplan"     => $data['dplan']." 12:00:00",
			"mc"        => $mycomps[ $data['mc'] ]
		];

	}

	$data = ["list" => $dlist];

	//include_once '../opensource/tbs_us/tbs_class.php';
	//include_once '../opensource/tbs_us/plugins/tbs_plugin_opentbs.php';
	
	require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

	$templateFile = 'templates/paymentTemp.xlsx';
	$outputFile   = 'exportPayments.xlsx';

	$TBS = new clsTinyButStrong(); // new instance of TBS
	$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin

	$TBS -> SetOption( 'noerr', true );
	$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );

	$TBS -> MergeBlock( 'list', $data['list'] );
	$TBS -> Show( OPENTBS_DOWNLOAD, $outputFile );

	exit();

}

if ( !$action ) {

	foreach ( $da as $data ) {

		$data['status'] = ($data['do'] == "on") ? '<span class="green">Оплачен</span>' : '<span class="red">Выставлен</span>';

		$list[ $data['dplan'] ][]              = [
			"invoice" => $data['invoice'],
			"summa"   => $data['summa'],
			"step"    => $data['step'],
			"clid"    => $data['clid'],
			"client"  => $data['client'],
			"datum"   => $data['idatum'],
			"did"     => $data['did'],
			"deal"    => $data['dogovor'],
			"status"  => $data['status'],
			"close  " => $data['close'],
			"dfact"   => $data['dfact']." 12:00:00",
			"dplan"   => $data['dplan']." 12:00:00",
			"mc"      => $mycomps[ $data['mc'] ]
		];
		$itogo[ $data['dplan'] ]['summaItogo'] += $data['summa'];

	}
	?>
	<style>
		.td--main {
			height : 45px;
			cursor : pointer;
		}
		.td--main:hover {
			background : rgba(197, 225, 165, 1);
		}
	</style>

	<div class="relativ mt20 mb20 wp95 text-center">

		<h1 class="uppercase fs-14 m0 mb10">Выставленные счета [ по дате ]</h1>
		<div class="gray2">за период &nbsp;<?= format_date_rus( $da1 ) ?> &divide; <?= format_date_rus( $da2 ) ?>
			<span class="hidden1 Bold">[ <a href="javascript:void(0)" onclick="Export()" title="Выгрузить в Excel для Roistat" class="blue">Excel</a> ]</span>
		</div>

	</div>

	<hr>

	<div class="noprint">

		<div class="pad5 mt20 gray2 Bold">Фильтры по клиентам:</div>
		<!--дополнителный фильтры-->
		<table class="noborder">
			<tr>
				<td class="wp20">
					<div class="ydropDown">
						<span>По Типу клиента</span><span class="ydropCount"><?= count( $clientTip ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">

							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="client" <?php if ( in_array( "client", $clientTip ) )
										print 'checked'; ?>>&nbsp;Клиент: юр.лицо
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="person" <?php if ( in_array( "person", $clientTip ) )
										print 'checked'; ?>>&nbsp;Клиент: физ.лицо
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="partner" <?php if ( in_array( "partner", $clientTip ) )
										print 'checked'; ?>>&nbsp;Партнер
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="contractor" <?php if ( in_array( "contractor", $clientTip ) )
										print 'checked'; ?>>&nbsp;Поставщик
								</label>
							</div>

							<div class="ydropString ellipsis">
								<label>
									<input class="taskss" name="clientTip[]" type="checkbox" id="clientTip[]" value="concurent" <?php if ( in_array( "concurent", $clientTip ) )
										print 'checked'; ?>>&nbsp;Конкурент
								</label>
							</div>

						</div>
					</div>
				</td>
				<td class="wp20">
					<div class="ydropDown">
						<span>По Территории</span><span class="ydropCount"><?= count( $clientTerritory ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">
							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>
							<?php
							$result = $db -> getAll( "SELECT * FROM ".$sqlname."territory_cat WHERE identity = '$identity' ORDER BY title" );
							foreach ( $result as $data ) {
								?>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="clientTerritory[]" type="checkbox" id="clientTerritory[]" value="<?= $data['idcategory'] ?>" <?php if ( in_array( $data['idcategory'], $clientTerritory ) )
											print 'checked'; ?>>&nbsp;<?= $data['title'] ?>
									</label>
								</div>
							<?php } ?>
						</div>
					</div>
				</td>
				<td class="wp20">
					<div class="ydropDown">
						<span>По Источнику</span><span class="ydropCount"><?= count( $clientPath ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">
							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>
							<?php
							$result = $db -> getAll( "SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name" );
							foreach ( $result as $data ) {
								?>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="clientPath[]" type="checkbox" id="clientPath[]" value="<?= $data['id'] ?>" <?php if ( in_array( $data['id'], $clientPath ) )
											print 'checked'; ?>>&nbsp;<?= $data['name'] ?>
									</label>
								</div>
							<?php } ?>
						</div>
					</div>
				</td>
				<td class="wp20">
					<div class="ydropDown">
						<span>По Статусу</span><span class="ydropCount"><?= count( $clientStatus ) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yselectBox" style="max-height: 300px;">
							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>
							<?php

							foreach ( $status as $key => $val ) {
								?>
								<div class="ydropString ellipsis">
									<label>
										<input class="taskss" name="clientStatus[]" type="checkbox" id="clientStatus[]" value="<?= $key ?>" <?php if ( in_array( $key, $clientStatus ) )
											print 'checked'; ?>>&nbsp;<?= $val ?>
									</label>
								</div>
							<?php } ?>
						</div>
					</div>
				</td>
				<td class="wp20"></td>
			</tr>
		</table>

	</div>

	<div class="block mt10">

		<TABLE>
			<thead class="sticked--top">
			<TR class="th35">
				<th class="w20 text-center"><b></b></th>
				<th class="w20 text-center"><b>#</b></th>
				<th class="w120 text-center"><b>Счет</b></th>
				<th class="w120 text-center"><b>Статус</b></th>
				<th class="w120 text-center"><b>Сумма</b></th>
				<th class="text-center"><b>Сделка</b></th>
				<th class="w120 text-center"><b>Компания</b></th>
				<th class="text-center"><b>Заказчик</b></th>
			</TR>
			</thead>
			<tbody>
			<?php
			$n = 1;
			foreach ( $list as $key => $val ) {

				print '
				<tr class="td--main bluebg-sub" data-key='.$n.'>
					<td class="openprj" colspan="10">
						<i class="icon-plus-circled"></i>
						<div class="Bold blue fs-11 inline">Дата оплаты: '.format_date_rus( $key ).' г.</div> [ Сумма: <span class="Bold gray2">'.num_format( $itogo[ $key ]['summaItogo'] ).'</span> ]
					</td>
				</tr>
				';

				$num = 1;
				foreach ( $val as $key2 => $val2 ) {
					$icon = ($val2['close'] == 'yes') ? 'icon-lock red' : 'icon-briefcase-1 blue';
					print '
					<tr class="datetoggle td--main hidden sub" data-date='.$n.' >
						<td></td>
						<td class="text-right">'.$num.'</td>
						<td class="text-center">
							<b>'.$val2['invoice'].'</b>
							<div class="em gray2 fs-07">от '.get_date( $val2['datum'] ).' г.</div>
						</td>
						<td class="text-center"><b>'.$val2['status'].'</b></td>
						<td class="text-right">'.num_format( $val2['summa'] ).'</td>
						<td>
							<div class="ellipsis"><b>[ '.$val2['step'].'% ]</b> <A href="javascript:void(0)" onclick="openDogovor(\''.$val2['did'].'\')" title="Открыть в новом окне"><i class="'.$icon.'"></i>&nbsp;'.$val2['deal'].'</A></div>
						</td>
						<td><div class="ellipsis" title="'.($val2['mc']).'">'.($val2['mc']).'</div></td>
						<td>
							<div class="ellipsis"><A href="javascript:void(0)" onclick="openClient(\''.$val2['clid'].'\')"><i class="icon-building broun"></i>&nbsp;'.$val2['client'].'</A></div>
						</td>
					</tr>
				';
					$num++;
				}
				$n++;
			}
			?>
			</tbody>
		</TABLE>

	</div>
	<div style="height: 100px"></div>

	<DIV class="fixAddBotButton" style="left:auto; right: 50px" onclick="ToggleAll()" data-state="collapse">
		<i class="icon-plus"></i> <span>Развернуть всё</span>
	</div>
	<script>

		$('.openprj').on('click', function () {

			var key = $(this).closest('tr').data('key');
			$('tr.datetoggle[data-date="' + key + '"]').toggleClass('hidden');
			$(this).find('i').toggleClass('icon-plus-circled icon-minus-circled');

		});

		function ToggleAll() {

			var state = $('.fixAddBotButton').data('state');

			if (state === 'collapse') {

				$('.fixAddBotButton').data('state', 'expand');
				$('.fixAddBotButton').find('span').html('Свернуть всё');
				$('.fixAddBotButton').find('i').removeClass('icon-plus').addClass('icon-minus');
				$('.datetoggle').removeClass('hidden');
				$('.openprj').find('i').removeClass('icon-plus-circled').addClass('icon-minus-circled');

			}
			if (state === 'expand') {

				$('.fixAddBotButton').data('state', 'collapse');
				$('.fixAddBotButton').find('span').html('Развернуть всё');
				$('.fixAddBotButton').find('i').addClass('icon-plus').removeClass('icon-minus');
				$('.datetoggle').addClass('hidden');
				$('.openprj').find('i').addClass('icon-plus-circled').removeClass('icon-minus-circled');

			}

		}

		function Export() {
			var str = $('#selectreport').serialize();
			window.open('/reports/<?=$thisfile?>?action=export&' + str + '&da1=<?=$da1?>&da2=<?=$da2?>&period=' + $('#swPeriod').val());
		}
	</script>
<?php } ?>