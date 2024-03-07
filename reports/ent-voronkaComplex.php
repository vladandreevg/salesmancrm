<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

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
$users  = (array)$_REQUEST['user_list'];
$point  = $_REQUEST['point'];
$period = $_REQUEST['period'];

$color  = [
	'#AD1457',
	'#FF8A65',
	'#F9A825',
	'#2E7D32',
	'#0277BD',
	'#3F51B5',
	'#6A1B9A',
	'#546E7A',
	'#78909C',
	'#00695C',
	'#9E9D24'
];
$color2 = [
	'#EC407A',
	'#D84315',
	'#FFF176',
	'#66BB6A',
	'#42A5F5',
	'#7986CB',
	'#BA68C8',
	'#B0BEC5',
	'#CFD8DC',
	'#4DB6AC',
	'#CDDC39'
];

$period = ($period == '') ? getPeriod( 'month' ) : getPeriod( $period );

$d1 = ($da1 != '') ? $da1 : $period[0];
$d2 = ($da2 != '') ? $da2 : $period[1];

$counts = [];
$summas = [];
$list   = [];
$max    = 0;

$tips = [
	'исх.1.Звонок',
	'исх.2.Звонок',
	'вх.Звонок',
	'вх.Почта',
	'Встреча',
	'Презентация',
	'Предложение',
	'Отправка КП',
	'Входящий звонок',
	'Исходящий звонок',
	'Холодный звонок',
	'Исходящая почта'
];

$tp = "tip IN (".yimplode( ",", $tips, "'" ).") and ";
$tt = "tip NOT IN ('СобытиеCRM','ЛогCRM','Исх.Почта') and ";

$so = '';
if ( !empty( $users ) ) {
	$so .= " iduser IN (".yimplode( ",", $users ).") AND ";
}
else {
	$so .= " iduser IN (".yimplode( ",", (array)get_people( $iduser1, 'yes' ) ).") AND ";
}

//Проверем, включен ли модуль сбора лидов
//смотрим - включен ли модуль Сборщик Лидов
$mdwset       = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'" );
$leadsettings = json_decode( (string)$mdwset['content'], true );
$coordinator  = $leadsettings["leadСoordinator"];

if ( $coordinator > 0 ) {

	$leadStatuses = [
		'0' => 'Открыт',
		'1' => 'В работе',
		'2' => 'Обработан',
		'3' => 'Закрыт'
	];
	$leadRezultes = [
		'1' => 'Спам',
		'2' => 'Дубль',
		'3' => 'Другое'
	];
	$leadColors   = [
		'0' => 'red',
		'1' => 'green',
		'2' => 'blue',
		'3' => 'gray'
	];
	$leadRColors  = [
		'1' => 'red',
		'2' => 'gray',
		'3' => 'blue',
		'4' => 'gray'
	];

	//Завки с сайта пришедшие
	$counta['inLeads'] = 0;

	if ( $iduser1 == $coordinator )
		$sort1 = "";
	else $sort1 = "and (iduser = 0 ".str_replace( "and", "or", get_people( $iduser1 ) ).")";

	$result = $db -> query( "SELECT * FROM ".$sqlname."leads WHERE id > 0 and (datum between '$d1 00:00:00' and '$d2 23:59:59') $sort1 and identity = '$identity' ORDER BY datum DESC" );
	while ($daa = $db -> fetch( $result )) {

		$counta['inLeads']++;

		if ( $daa['iduser'] == 0 )
			$userl = '<em class="gray">Не назначено</em>';
		else $userl = current_user( $daa['iduser'] );

		if ( $daa['status'] < 3 ) {
			$rezz = '<b class="'.strtr( $daa['status'], $leadColors ).'">'.strtr( $daa['status'], $leadStatuses ).'</b>';
		}
		elseif ( $daa['status'] == 3 ) {
			$rezz = '<b class="'.strtr( $daa['rezult'], $leadRColors ).'">'.strtr( $daa['rezult'], $leadRezultes ).'</b>';
		}

		$lists['inLeads'][] = [
			"date"       => get_sfdate2( $daa['datum'] ),
			"id"         => $daa['id'],
			"user"       => $userl,
			"clientpath" => current_clientpathbyid( $daa['clientpath'] ),
			"clid"       => $daa['clid'],
			"pid"        => $daa['pid'],
			"did"        => $daa['did'],
			"deal"       => current_dogovor( $daa['did'] ),
			"status"     => $rezz
		];

	}

	if ( $counta['inLeads'] > $max )
		$max = $counta['inLeads'];
}

$re = $db -> getAll( "SELECT * FROM ".$sqlname."user WHERE iduser > 0 and $so acs_plan = 'on' and identity = '$identity' ORDER BY secrty DESC, title" );
foreach ( $re as $da ) {

	$user[ $da['iduser'] ] = $da['title'];

	if ( $coordinator > 0 ) {

		//Завки с сайта обработанные
		$counts['newLeads'][ $da['iduser'] ] = 0;

		$result = $db -> getAll( "SELECT * FROM ".$sqlname."leads WHERE id > 0 and status IN ('2', '3') and (datum_do between '$d1 00:00:00' and '$d2 23:59:59') and iduser = '".$da['iduser']."' and identity = '$identity' ORDER BY datum_do DESC" );
		foreach ( $result as $daa ) {

			$counts['newLeads'][ $da['iduser'] ]++;

			if ( $daa['status'] < 3 ) {
				$rezz = '<b class="'.strtr( $daa['status'], $leadColors ).'">'.strtr( $daa['status'], $leadStatuses ).'</b>';
			}
			elseif ( $daa['status'] == 3 ) {
				$rezz = '<b class="'.strtr( $daa['rezult'], $leadRColors ).'">'.strtr( $daa['rezult'], $leadRezultes ).'</b>';
			}

			$list['newLeads'][ $da['iduser'] ][] = [
				"date"       => get_sfdate2( $daa['datum_do'] ),
				"id"         => $daa['id'],
				"clientpath" => current_clientpathbyid( $daa['clientpath'] ),
				"clid"       => $daa['clid'],
				"pid"        => $daa['pid'],
				"did"        => $daa['did'],
				"deal"       => current_dogovor( $daa['did'] ),
				"rezz"       => $rezz
			];

		}

		if ( $counts['newLeads'][ $da['iduser'] ] > $max ) {
			$max = $counts['newLeads'][ $da['iduser'] ];
		}

	}

	//Обращения
	if ( $isEntry == 'on' ) {

		$counts['newEntry'][ $da['iduser'] ] = 0;
		$summas['newEntry'][ $da['iduser'] ] = 0;

		$result = $db -> getAll( "SELECT * FROM ".$sqlname."entry WHERE ide > 0 and (datum between '$d1 00:00:00' and '$d2 23:59:59') and iduser = '".$da['iduser']."' and identity = '$identity' ORDER BY datum DESC" );
		foreach ( $result as $daa ) {

			$counts['newEntry'][ $da['iduser'] ]++;
			$summae = (float)$db -> getOne( "SELECT SUM(price) as price FROM ".$sqlname."entry_poz WHERE ide = '".$daa['ide']."' and identity = '$identity'" );

			$summas['newEntry'][ $da['iduser'] ] += $summae;

			$list['newEntry'][ $da['iduser'] ][] = [
				"id"    => $daa['ide'],
				"date"  => get_sfdate2( $daa['datum'] ),
				"summa" => $summae,
				"clid"  => $daa['clid'],
				"pid"   => $daa['pid'],
				"did"   => $daa['did'],
				"deal"  => current_dogovor( $daa['did'] )
			];

		}

		if ( $counts['newEntry'][ $da['iduser'] ] > $max ) {
			$max = $counts['newEntry'][ $da['iduser'] ];
		}

	}


	//активности с/без сделок
	$counts['ActWoDeals'][ $da['iduser'] ] = 0;
	$counts['ActWiDeals'][ $da['iduser'] ] = 0;

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."history WHERE (datum between '$d1 00:00:00' and '$d2 23:59:59') and $tt iduser = '".$da['iduser']."' and identity = '$identity'" );
	foreach ( $result as $daa ) {

		if ( $daa['did'] < 1 ) {

			$counts['ActWoDeals'][ $da['iduser'] ]++;
			$list['ActWoDeals'][ $da['iduser'] ][] = [
				"hid"  => $daa['cid'],
				"tip"  => $daa['tip'],
				"date" => get_sfdate( $daa['datum'] ),
				"clid" => $daa['clid'],
				"pid"  => $daa['pid']
			];

		}
		else {

			$counts['ActWiDeals'][ $da['iduser'] ]++;
			$list['ActWiDeals'][ $da['iduser'] ][] = [
				"hid"  => $daa['cid'],
				"tip"  => $daa['tip'],
				"date" => get_sfdate( $daa['datum'] ),
				"clid" => $daa['clid'],
				"pid"  => $daa['pid'],
				"did"  => $daa['did'],
				"deal" => current_dogovor( $daa['did'] )
			];

		}

	}
	//$result->close();

	if ( $counts['ActWoDeals'][ $da['iduser'] ] > $max ) {
		$max = $counts['ActWoDeals'][ $da['iduser'] ];
	}
	if ( $counts['ActWiDeals'][ $da['iduser'] ] > $max ) {
		$max = $counts['ActWiDeals'][ $da['iduser'] ];
	}

	//Новые сделки
	$counts['newDogs'][ $da['iduser'] ] = 0;
	$summas['newDogs'][ $da['iduser'] ] = 0;

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogovor WHERE did>0 and datum between '$d1 00:00:00' and '$d2 23:59:59' and iduser = '".$da['iduser']."' and identity = '$identity'" );
	foreach ( $result as $daa ) {

		$counts['newDogs'][ $da['iduser'] ]++;
		$summas['newDogs'][ $da['iduser'] ] += (float)$daa['kol'];

		$list['newDogs'][ $da['iduser'] ][] = [
			"date"     => format_date_rus( $daa['datum'] ),
			"datePlan" => format_date_rus( $daa['datum_plan'] ),
			"clid"     => (int)$daa['clid'],
			"pid"      => (int)$daa['pid'],
			"did"      => (int)$daa['did'],
			"summa"    => (float)$daa['kol'],
			"deal"     => current_dogovor( (int)$daa['did'] )
		];

	}

	if ( $counts['newDogs'][ $da['iduser'] ] > $max ) {
		$max = $counts['newDogs'][ $da['iduser'] ];
	}

	//Новые договоры
	$counts['newContracts'][ $da['iduser'] ] = 0;

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."contract WHERE deid > 0 and datum between '$d1 00:00:00' and '$d2 23:59:59' and iduser = '".$da['iduser']."' and idtype IN (SELECT id FROM ".$sqlname."contract_type where type = 'get_dogovor' and identity = '$identity') and identity = '$identity'" );
	foreach ( $result as $daa ) {

		$sum = 0;

		if ( $daa['did'] > 0 ) {
			$sum = (float)getDogData( $daa['did'], 'kol' );
		}

		$counts['newContracts'][ $da['iduser'] ]++;
		$summas['newContracts'][ $da['iduser'] ] += $sum;

		$list['newContracts'][ $da['iduser'] ][] = [
			"date"     => get_sfdate( $daa['datum'] ),
			"datePlan" => format_date_rus( getDogData( $daa['did'], 'datum_plan' ) ),
			"clid"     => (int)$daa['clid'],
			"pid"      => (int)$daa['pid'],
			"did"      => (int)$daa['did'],
			"summa"    => $sum,
			"deal"     => current_dogovor( $daa['did'] )
		];

	}

	if ( $counts['newContracts'][ $da['iduser'] ] > $max ) {
		$max = $counts['newContracts'][ $da['iduser'] ];
	}

	//Выставленные счета
	$counts['newInvoice'][ $da['iduser'] ] = 0;

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE datum between '$d1 00:00:00' and '$d2 23:59:59' and iduser = '".$da['iduser']."' and identity = '$identity'" );
	foreach ( $result as $daa ) {

		$counts['newInvoice'][ $da['iduser'] ]++;
		$summas['newInvoice'][ $da['iduser'] ] += $daa['summa_credit'];

		if ( $daa['invoice_date'] != '0000-00-00' ) {
			$invoiceDate = format_date_rus( $daa['invoice_date'] );
		}
		else {
			$invoiceDate = '-';
		}

		$list['newInvoice'][ $da['iduser'] ][] = [
			"date"       => get_sfdate2( $daa['datum'] ),
			"datePlan"   => format_date_rus( $daa['datum_credit'] ),
			"dateFact"   => $invoiceDate,
			"clid"       => (int)$daa['payer'],
			"pid"        => (int)$daa['pid'],
			"did"        => (int)$daa['did'],
			"summa"      => (float)getDogData( $daa['did'], 'kol' ),
			"invoice"    => $daa['invoice'],
			"invoiceSum" => $daa['summa_credit'],
			"deal"       => current_dogovor( $daa['did'] )
		];

	}

	if ( $counts['newInvoice'][ $da['iduser'] ] > $max ) {
		$max = $counts['newInvoice'][ $da['iduser'] ];
	}

	//Оплаченные счета
	$counts['newInvoiceDo'][ $da['iduser'] ] = 0;

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."credit WHERE do = 'on' and invoice_date between '$d1 00:00:00' and '$d2 23:59:59' and iduser = '".$da['iduser']."' and identity = '$identity'" );
	foreach ( $result as $daa ) {

		$counts['InvoiceDo'][ $da['iduser'] ]++;
		$summas['InvoiceDo'][ $da['iduser'] ] += $daa['summa_credit'];

		$list['InvoiceDo'][ $da['iduser'] ][] = [
			"date"       => $daa['invoice_date'],
			"datePlan"   => format_date_rus( $daa['datum_credit'] ),
			"dateFact"   => format_date_rus( $daa['invoice_date'] ),
			"clid"       => $daa['clid'],
			"pid"        => $daa['pid'],
			"did"        => $daa['did'],
			"deal"       => current_dogovor( $daa['did'] ),
			"summa"      => $sum,
			"invoice"    => $daa['invoice'],
			"invoiceSum" => $daa['summa_credit']
		];

	}

	if ( $counts['InvoiceDo'][ $da['iduser'] ] > $max ) {
		$max = $counts['InvoiceDo'][ $da['iduser'] ];
	}

	//Закрытые сделки
	$counts['closeDogs'][ $da['iduser'] ] = 0;
	$summas['closeDogs'][ $da['iduser'] ] = 0;

	$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogovor WHERE did > 0 and close = 'yes' and datum_close between '$d1 00:00:00' and '$d2 23:59:59' and iduser = '".$da['iduser']."' and identity = '$identity'" );
	foreach ( $result as $daa ) {

		$counts['closeDogs'][ $da['iduser'] ]++;
		$summas['closeDogs'][ $da['iduser'] ] += $daa['kol_fact'];

		$list['closeDogs'][ $da['iduser'] ][] = [
			"date"      => format_date_rus( $daa['datum_plan'] ),
			"dateFact"  => format_date_rus( $daa['datum_close'] ),
			"clid"      => $daa['clid'],
			"pid"       => $daa['pid'],
			"did"       => $daa['did'],
			"summaPlan" => $daa['kol'],
			"summaFact" => $daa['kol_fact'],
			"deal"      => current_dogovor( $daa['did'] )
		];

	}

	if ( $counts['closeDogs'][ $da['iduser'] ] > $max ) {
		$max = $counts['closeDogs'][ $da['iduser'] ];
	}

}

$vor[0] = ceil( array_sum( (array)$counts['ActWoDeals'] ) );
$vor[1] = ceil( array_sum( (array)$counts['ActWiDeals'] ) );
$vor[2] = ceil( array_sum( (array)$counts['newDogs'] ) );
$vor[3] = ceil( array_sum( (array)$counts['newContracts'] ) );
$vor[4] = ceil( array_sum( (array)$counts['newInvoice'] ) );
$vor[5] = ceil( array_sum( (array)$counts['InvoiceDo'] ) );
$vor[6] = ceil( array_sum( (array)$counts['closeDogs'] ) );
$vor[7] = ceil( array_sum( (array)$counts['newLeads'] ) );
$vor[8] = ceil( $counta['inLeads'] );
$vor[9] = ceil( array_sum( (array)$counts['newEntry'] ) );

$max = max( $vor );

if ( $vor[0] > 0 ) {
	$vor['ActWoDeals'] = abs( ceil( array_sum( $counts['ActWoDeals'] ) * 100 ) / $max );
}
else {
	$vor['ActWoDeals'] = 0;
}
if ( $vor[1] > 0 ) {
	$vor['ActWiDeals'] = abs( ceil( array_sum( $counts['ActWiDeals'] ) * 100 ) / $max );
}
else {
	$vor['ActWiDeals'] = 0;
}
if ( $vor[2] > 0 ) {
	$vor['newDogs'] = abs( ceil( array_sum( $counts['newDogs'] ) * 100 ) / $max );
}
else {
	$vor['newDogs'] = 0;
}
if ( $vor[3] > 0 ) {
	$vor['newContracts'] = abs( ceil( array_sum( $counts['newContracts'] ) * 100 ) / $max );
}
else {
	$vor['newContracts'] = 0;
}
if ( $vor[4] > 0 ) {
	$vor['newInvoice'] = abs( ceil( array_sum( $counts['newInvoice'] ) * 100 ) / $max );
}
else {
	$vor['newInvoice'] = 0;
}
if ( $vor[5] > 0 ) {
	$vor['InvoiceDo'] = abs( ceil( array_sum( $counts['InvoiceDo'] ) * 100 ) / $max );
}
else {
	$vor['InvoiceDo'] = 0;
}
if ( $vor[6] > 0 ) {
	$vor['closeDogs'] = abs( ceil( array_sum( $counts['closeDogs'] ) * 100 ) / $max );
}
else {
	$vor['closeDogs'] = 0;
}
if ( $vor[7] > 0 ) {
	$vor['newLeads'] = abs( ceil( array_sum( $counts['newLeads'] ) * 100 ) / $max );
}
else {
	$vor['newLeads'] = 0;
}
if ( $vor[8] > 0 ) {
	$vor['inLeads'] = abs( ceil( $counta['inLeads'] * 100 ) / $max );
}
else {
	$vor['inLeads'] = 0;
}
if ( $vor[9] > 0 ) {
	$vor['newEntry'] = abs( ceil( array_sum( $counts['newEntry'] ) * 100 ) / $max );
}
else {
	$vor['newEntry'] = 0;
}

//print_r($list);
?>

<STYLE type="text/css">
	<!--
	#contentdiv thead {
		font-size          : 90%;
		color              : #485B60;
		background         : #FFF;
		border-bottom      : 1px solid #E6E6F0;
		box-shadow         : 0 1px 1px #ccc;
		-webkit-box-shadow : 0 1px 1px #ccc;
		-moz-box-shadow    : 0 1px 1px #ccc;
	}
	tr.ha1 {
		/*background: rgba(231,76,60,0.1);*/
		background : rgba(236, 239, 241, 0.5);
	}
	tr.ha2 {
		background : rgba(241, 196, 15, 0.1);
	}
	tr.ha3 {
		background : rgba(26, 188, 150, 0.1);
	}
	-->
</STYLE>

<div class="mt10 mb20 div-center">

	<h2 class="m0 mb5 fs-20 uppercase">Комплексная воронка</h2>
	<div class="gray2">за период&nbsp;с&nbsp;<?= format_date_rus( $d1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $d2 ) ?></div>

</div>

<hr>

<div id="formtabse">

	<table id="zebraTable">
		<thead>
		<tr class="header_contaner bordered text-center">
			<th class="w350"><b>Показатель</b></th>
			<th class="w100"><b>Кол-во</b></th>
			<th class="w150"><b></b></th>
			<th><b></b></th>
		</tr>
		</thead>
		<tbody>
		<?php if ( $coordinator > 0 ) { ?>
			<tr class="rating ha bordered hand" data-id="inLeads">
				<td>
					<?php
					if ( $counta['inLeads'] > 0 )
						print '<i class="icon-angle-down angle gray"></i>&nbsp;';
					else print '<i class="icon-angle-down white"></i>&nbsp;';
					?>
					&nbsp;<span class="Bold fs-12" style="color:<?= $color[8] ?>">Поступило заявок (Лидов)</span>
				</td>
				<td class="text-center"><b class="fs-12"><?= $counta['inLeads'] ?></b></td>
				<td class="text-right">-&nbsp;</td>
				<td class="text-left" class="pr10">
					<div style="width: <?= $vor['inLeads'] ?>%; height: 5px; background:<?= $color[8] ?>;"></div>
				</td>
			</tr>
			<tr class="hidden inLeads ha1">
				<td colspan="4">

					<div class="infodiv1">

						<table class="fs-10">
							<thead>
							<tr>
								<th class="w60"">№</th>
								<th class="w100">Дата</th>
								<th class="w30"></th>
								<th class="w160">Ответственный</th>
								<th class="w100">Статус</th>
								<th class="w250 text-left">Сделка</th>
								<th class="text-left">Клиент</th>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($lists['inLeads'] as $lead) {

								$num = $i + 1;
								?>
								<tr class="ha">
									<td class="text-center"><span class="fs-10"><?= $num ?></span></td>
									<td class="text-center"><span class="fs-10"><?= $lead['date'] ?></span></td>
									<td class="text-center">
										<span class="fs-10"><a href="javascript:void(0)" onclick="editLead('<?= $lead['id'] ?>','view');" title=""><i class="icon-eye blue"></i></a></span>
									</td>
									<td class="text-left"><span class="fs-10"><?= $lead['user'] ?></span></td>
									<td class="text-left"><span class="fs-10"><?= $lead['status'] ?></span></td>
									<td class="text-left">
										<?php
										if ( $lead['did'] > 0 ) {
											?>
											<a href="javascript:void(0)" onclick="openDogovor('<?= $lead['did'] ?>')" title=""><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lists['inLeads'][ $i ]['deal'] ?></a>
										<?php } ?>
									</td>
									<td class="text-left">
										<?php
										if ( $lead['clid'] > 0 ) {
											?>
											<div class="ellipsis">
												<a href="javascript:void(0)" onclick="openClient('<?= $lead['clid'] ?>')" title=""><i class="icon-building broun"></i>&nbsp;<?= current_client( $lead['clid'] ) ?></a>
											</div>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
				</td>
			</tr>

			<tr class="rating ha bordered hand" data-id="newLeads">
				<td>
					<?php
					if ( array_sum( (array)$counts['newLeads'] ) > 0 ) {
						print '<i class="icon-angle-down angle gray"></i>&nbsp;';
					}
					else {
						print '<i class="icon-angle-down white"></i>&nbsp;';
					}
					?>
					&nbsp;<span class="Bold fs-12" style="color:<?= $color[7] ?>">Обработано заявок (Лидов)</span>
				</td>
				<td class="text-center"><b class="fs-12"><?= array_sum( $counts['newLeads'] ) ?></b></td>
				<td class="text-right">-&nbsp;</td>
				<td class="text-left pr10">
					<div style="width: <?= $vor['newLeads'] ?>%; height: 5px; background:<?= $color[7] ?>;"></div>
				</td>
			</tr>
			<tr class="hidden newLeads ha1">
				<td colspan="4">

					<table>
						<tbody>
						<?php
						foreach ( $user as $key => $value ) {

							if ( $counts['newLeads'][ $key ] > 0 ) {
								?>
								<tr class="subrating hand" data-tid="subnewLeads<?= $key ?>">
									<td class="w350">
										<div class="ellipsis paddleft20 Bold"><i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span></div>
									</td>
									<td class="w100 text-center">
										<b class="fs-11"><?= $counts['newLeads'][ $key ] ?></b></td>
									<td class="w150 text-right fs-11">-&nbsp;</td>
									<td class="text-left">
										<div style="width: <?= $counts['newLeads'][ $key ] / array_sum( $counts['newLeads'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[7] ?>;"></div>
									</td>
								</tr>
								<tr class="hidden subnewLeads<?= $key ?> ha2">
									<td colspan="4">
										<div class="infodiv1 fs-10">

											<table class="fs-10">
												<thead>
												<tr>
													<th class="w100">Дата обработки</th>
													<th class="w30"></th>
													<th class="w100">Статус / Комментарий</th>
													<th class="w250">Сделка</th>
													<th class="text-left">Клиент</th>
												</tr>
												</thead>
												<tbody>
												<?php
												foreach ($list['newLeads'][ $key ] as $lead) {
													?>
													<tr>
														<td class="text-center">
															<span class="fs-09"><?= $lead['date'] ?></span>
														</td>
														<td class="text-center">
															<span class="fs-09"><a href="javascript:void(0)" onclick="editLead('<?= $lead['id'] ?>','view');" title=""><i class="icon-eye blue"></i></a></span>
														</td>
														<td>
															<span class="fs-09"><?= $lead['rezz'] ?></span>
														</td>
														<td>
															<?php
															if ( $lead['did'] > 0 ) {
																?>
																<a href="javascript:void(0)" onclick="openDogovor('<?= $lead['did'] ?>')" title=""><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lead['deal'] ?></a>
															<?php } ?>
														</td>
														<td>
															<?php
															if ( $lead['clid'] > 0 ) {
																?>
																<div class="ellipsis">
																	<a href="javascript:void(0)" onclick="openClient('<?= $lead['clid'] ?>')" title=""><i class="icon-building broun"></i>&nbsp;<?= current_client( $lead['clid'] ) ?></a>
																</div>
															<?php } ?>
														</td>
													</tr>
												<?php } ?>
												</tbody>
											</table>
										</div>
									</td>
								</tr>
								<?php
							}
						}
						?>
						</tbody>
					</table>
				</td>
			</tr>
		<?php } ?>

		<?php if ( $isEntry == 'on' ) { ?>
			<tr class="rating ha bordered hand" data-id="newEntry">
				<td>
					<?php
					if ( array_sum( (array)$counts['newEntry'] ) > 0 ) {
						print '<i class="icon-angle-down angle gray"></i>&nbsp;';
					}
					else {
						print '<i class="icon-angle-down white"></i>&nbsp;';
					}
					?>
					&nbsp;<span class="Bold fs-12" style="color:<?= $color[9] ?>">Поступило обращений</span>
				</td>
				<td class="text-center"><b class="fs-12"><?= array_sum( $counts['newEntry'] ) ?></b></td>
				<td class="text-right"><?= num_format( array_sum( $summas['newEntry'] ) ) ?>&nbsp;</td>
				<td class="text-left pr10">
					<div style="width: <?= $vor['newEntry'] ?>%; height: 5px; background:<?= $color[9] ?>;"></div>
				</td>
			</tr>
			<tr class="hidden newEntry ha1">
				<td colspan="4">
					<table>
						<tbody>
						<?php
						foreach ( $user as $key => $value ) {
							if ( $counts['newEntry'][ $key ] > 0 ) {
								?>
								<tr class="subrating hand" data-tid="subnewEntry<?= $key ?>">
									<td class="w350">
										<div class="paddleft20 Bold">
											<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
										</div>
									</td>
									<td class="w100 text-center">
										<b class="fs-10"><?= $counts['newEntry'][ $key ] ?></b></td>
									<td class="w150 text-right">
										<span class="fs-10"><?= num_format( $summas['newEntry'][ $key ] ) ?></span>&nbsp;
									</td>
									<td class="text-left">
										<div style="width: <?= $counts['newEntry'][ $key ] / array_sum( $counts['newEntry'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[9] ?>;"></div>
									</td>
								</tr>
								<tr class="hidden subnewEntry<?= $key ?> ha2">
									<td colspan="4">
										<div class="infodiv1">

											<table class="fs-10">
												<thead>
												<tr>
													<th class="w100">Дата</th>
													<th class="w50"></th>
													<th class="w120 text-right">Сумма</th>
													<th class="w250">Сделка</th>
													<th class="text-left">Клиент</th>
												</tr>
												</thead>
												<tbody>
												<?php
												foreach ($list['newEntry'][ $key ] as $lead) {
													?>
													<tr class="ha">
														<td class="text-center">
															<span class="fs-10"><?= $lead['date'] ?></span>
														</td>
														<td class="text-center">
															<span class="fs-10"><a href="javascript:void(0)" onclick="editEntry('<?= $lead['id'] ?>','view');" title=""><i class="icon-eye blue"></i></a></span>
														</td>
														<td class="text-right">
															<b><?= num_format( $lead['summa'] ) ?></b>
														</td>
														<td title="<?= $lead['deal'] ?>">
															<?php
															if ( $lead['did'] > 0 ) {
																?>
																<div class="ellipsis">
																	<a href="javascript:void(0)" onclick="openDogovor('<?= $lead['did'] ?>')"><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lead['deal'] ?></a>
																</div>
															<?php } ?>
														</td>
														<td class="text-left">
															<?php
															if ( $lead['clid'] > 0 ) {
																?>
																<a href="javascript:void(0)" onclick="openClient('<?= $lead['clid'] ?>')" title=""><i class="icon-building broun"></i>&nbsp;<?= current_client( $lead['clid'] ) ?></a>
															<?php } ?>
														</td>
													</tr>
													<?php
												}
												?>
												</tbody>
											</table>
										</div>
									</td>
								</tr>
								<?php
							}
						}
						?>
						</tbody>
					</table>
				</td>
			</tr>
		<?php } ?>

		<tr class="rating ha bordered hand" data-id="ActWoDeals">
			<td>
				<?php
				if ( array_sum( $counts['ActWoDeals'] ) > 0 ) {
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				}
				else {
					print '<i class="icon-angle-down white"></i>&nbsp;';
				}
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[0] ?>">Активности без сделок</span>
				<i class="icon-info-circled blue list" title="Вне сделок: исх.1.Звонок, исх.2.Звонок, вх.Звонок, вх.Почта, Встреча, Презентация, Предложение, Отправка КП"></i>
			</td>
			<td class="text-center"><b class="fs-12"><?= array_sum( $counts['ActWoDeals'] ) ?></b></td>
			<td class="text-right">-&nbsp;</td>
			<td class="text-left" class="pr10">
				<div style="width: <?= $vor['ActWoDeals'] ?>%; height: 5px; background:<?= $color[0] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden ActWoDeals ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {
						if ( $counts['ActWoDeals'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subActWoDeals<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center">
									<b class="fs-10"><?= $counts['ActWoDeals'][ $key ] ?></b></td>
								<td class="w150 text-right fs-10">-&nbsp;</td>
								<td class="text-left">
									<div style="width: <?= $counts['ActWoDeals'][ $key ] / array_sum( $counts['ActWoDeals'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[0] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subActWoDeals<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table class="fs-10">
											<thead>
											<tr>
												<th class="w100">Дата</th>
												<th class="w100">Тип</th>
												<th class="w350">Клиент</th>
												<th class="text-left"></th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['ActWoDeals'][ $key ] as $lead) {
												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $lead['date'] ?></span>
													</td>
													<td>
														<a href="javascript:void(0)" onclick="viewHistory('<?= $lead['hid'] ?>')" title=""><?= $lead['tip'] ?></a>
													</td>
													<td>
														<?php
														if ( $lead['clid'] > 0 ) {
															?>
															<a href="javascript:void(0)" onclick="openClient('<?= $lead['clid'] ?>')" title=""><i class="icon-building broun"></i>&nbsp;<?= current_client( $lead['clid'] ) ?></a>
														<?php } ?>
													</td>
													<td></td>
												</tr>
											<?php } ?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>

		<tr class="rating ha bordered hand" data-id="ActWiDeals">
			<td>
				<?php
				if ( array_sum( $counts['ActWiDeals'] ) > 0 ) {
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				}
				else {
					print '<i class="icon-angle-down white"></i>&nbsp;';
				}
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[1] ?>">Активности со сделками</span>
				<i class="icon-info-circled blue list" title="По сделкам: Задача, Встреча, Презентация, Предложение, Отправка КП"></i>
			</td>
			<td class="text-center"><b class="fs-12"><?= array_sum( $counts['ActWiDeals'] ) ?></b></td>
			<td class="text-right">-&nbsp;</td>
			<td class="text-left pr10">
				<div style="width: <?= $vor['ActWiDeals'] ?>%; height: 5px; background:<?= $color2[1] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden ActWiDeals ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {

						if ( $counts['ActWiDeals'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subActWiDeals<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center">
									<b class="fs-10"><?= $counts['ActWiDeals'][ $key ] ?></b></td>
								<td class="w150 text-center">-&nbsp;</td>
								<td class="text-left">
									<div style="width: <?= $counts['ActWiDeals'][ $key ] / array_sum( $counts['ActWiDeals'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color[1] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subActWiDeals<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table class="fs-09">
											<thead>
											<tr>
												<th class="w100">Дата</th>
												<th class="w100 text-left">Тип</th>
												<th class="w350 text-left">Клиент</th>
												<th class="text-left">Сделка</th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['ActWiDeals'][ $key ] as $lead) {
												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $lead['date'] ?></span>
													</td>
													<td class="text-left">
														<a href="javascript:void(0)" onclick="viewHistory('<?= $lead['hid'] ?>')" title=""><?= $lead['tip'] ?></a>
													</td>
													<td class="text-left">
														<?php
														if ( $lead['clid'] > 0 ) {
															?>
															<a href="javascript:void(0)" onclick="openClient('<?= $lead['clid'] ?>')" title=""><i class="icon-building broun"></i>&nbsp;<?= current_client( $lead['clid'] ) ?></a>
														<?php } ?>
													</td>
													<td title="<?= $lead['deal'] ?>">
														<?php
														if ( $lead['did'] > 0 ) {
															?>
															<div class="ellipsis">
																<a href="javascript:void(0)" onclick="openDogovor('<?= $lead['did'] ?>')"><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lead['deal'] ?></a>
															</div>
														<?php } ?>
													</td>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>

		<tr class="rating ha bordered hand" data-id="newDogs">
			<td>
				<?php
				if ( array_sum( $counts['newDogs'] ) > 0 )
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				else print '<i class="icon-angle-down white"></i>&nbsp;';
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[2] ?>">Создано новых сделок</span>
			</td>
			<td class="text-center"><b class="fs-12"><?= array_sum( $counts['newDogs'] ) ?></b></td>
			<td class="text-right"><?= num_format( array_sum( $summas['newDogs'] ) ) ?>&nbsp;</td>
			<td class="text-left pr10">
				<div style="width: <?= $vor['newDogs'] ?>%; height: 5px; background:<?= $color[2] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden newDogs ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {
						if ( $counts['newDogs'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subnewDogs<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center"><b class="fs-10"><?= $counts['newDogs'][ $key ] ?></b>
								</td>
								<td class="w150 text-right">
									<span class="fs-10"><?= num_format( $summas['newDogs'][ $key ] ) ?></span>&nbsp;
								</td>
								<td class="text-left">
									<div style="width: <?= $counts['ActWiDeals'][ $key ] / array_sum( $counts['newDogs'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[2] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subnewDogs<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table class="fs-10">
											<thead>
											<tr>
												<th class="w100">Дата</th>
												<th class="w100">Дата план.</th>
												<th class="w250">Сделка</th>
												<th class="w120">Сумма</th>
												<th">Клиент</th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['newDogs'][ $key ] as $lead) {
												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $lead['date'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $lead['datePlan'] ?></span>
													</td>
													<td title="<?= $lead['deal'] ?>">
														<?php
														if ( $lead['did'] > 0 ) {
															?>
															<div class="ellipsis">
																<a href="javascript:void(0)" onclick="openDogovor('<?= $lead['did'] ?>')"><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lead['deal'] ?></a>
															</div>
														<?php } ?>
													</td>
													<td class="text-right"><?= num_format( $lead['summa'] ) ?></td>
													<td class="text-left"><?= current_client( $lead['clid'] ) ?></td>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>

		<tr class="rating ha bordered hand" data-id="newContracts">
			<td height="22">
				<?php
				if ( array_sum( $counts['newContracts'] ) > 0 ) {
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				}
				else {
					print '<i class="icon-angle-down white"></i>&nbsp;';
				}
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[3] ?>">Создано новых договоров</span></td>
			<td class="text-center"><b class="fs-12"><?= array_sum( $counts['newContracts'] ) ?></b></td>
			<td class="text-right"><?= num_format( array_sum( (array)$summas['newContracts'] ) ) ?>&nbsp;</td>
			<td class="pr10">
				<div style="width: <?= $vor['newContracts'] ?>%; height: 5px; background:<?= $color[3] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden newContracts ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {
						if ( $counts['newContracts'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subnewContracts<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-09"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center">
									<b class="fs-10"><?= $counts['newContracts'][ $key ] ?></b></td>
								<td class="w150 text-right">
									<span class="fs-10"><?= num_format( $summas['newContracts'][ $key ] ) ?></span>&nbsp;
								</td>
								<td>
									<div style="width: <?= $counts['newContracts'][ $key ] / array_sum( $counts['newContracts'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[3] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subnewContracts<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table>
											<thead>
											<tr>
												<th class="w100">Дата</th>
												<th class="w100">Дата план.</th>
												<th class="w250">Сделка</th>
												<th class="w120 text-right">Сумма</th>
												<th>Клиент</th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['newContracts'][ $key ] as $lead) {
												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $lead['date'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $lead['datePlan'] ?></span>
													</td>
													<td title="<?= $lead['deal'] ?>">
														<?php
														if ( $lead['did'] > 0 ) {
															?>
															<div class="ellipsis">
																<a href="javascript:void(0)" onclick="openDogovor('<?= $lead['did'] ?>')"><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lead['deal'] ?></a>
															</div>
														<?php } ?>
													</td>
													<td class="text-right"><?= num_format( $lead['summa'] ) ?></td>
													<td class="text-left"><?= current_client( $lead['clid'] ) ?></td>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>

		<tr class="rating ha bordered hand" data-id="newInvoice">
			<td height="22">
				<?php
				if ( array_sum( $counts['newInvoice'] ) > 0 ) {
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				}
				else {
					print '<i class="icon-angle-down white"></i>&nbsp;';
				}
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[4] ?>">Выставлено счетов</span>
			</td>
			<td class="text-center"><b class="fs-12"><?= array_sum( $counts['newInvoice'] ) ?></b></td>
			<td class="text-right"><?= num_format( array_sum( (array)$summas['newInvoice'] ) ) ?>&nbsp;</td>
			<td class="text-left pr10">
				<div style="width: <?= $vor['newInvoice'] ?>%; height: 5px; background:<?= $color[4] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden newInvoice ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {
						if ( $counts['newInvoice'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subnewInvoice<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center">
									<b class="fs-10"><?= $counts['newInvoice'][ $key ] ?></b>&nbsp;
								</td>
								<td class="w150 text-center">
									<span class="fs-10"><?= num_format( $summas['newInvoice'][ $key ] ) ?></span>&nbsp;
								</td>
								<td>
									<div style="width: <?= $counts['newInvoice'][ $key ] / array_sum( $counts['newInvoice'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[4] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subnewInvoice<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table class="fs-10">
											<thead>
											<tr>
												<th class="w80">Номер</th>
												<th class="w100">Дата</th>
												<th class="w100">Дата план.</th>
												<th class="w100">Дата факт.</th>
												<th class="w120">Сумма</th>
												<th class="w250">Сделка</th>
												<th align="left">Клиент</th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['newInvoice'][ $key ] as $lead) {
												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $lead['invoice'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $lead['date'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $lead['datePlan'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $lead['dateFact'] ?></span>
													</td>
													<td class="text-right"><?= num_format( $lead['invoiceSum'] ) ?></td>
													<td title="<?= $lead['deal'] ?>">
														<div class="ellipsis">
															<a href="javascript:void(0)" onclick="viewDogovor('<?= $lead['did'] ?>')"><i class="icon-briefcase-1 blue"></i>&nbsp;<?= $lead['deal'] ?></a>
														</div>
													</td>
													<td class="text-left"><?= current_client( $lead['clid'] ) ?></td>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>

		<tr class="rating ha bordered hand" data-id="InvoiceDo">
			<td>
				<?php
				if ( array_sum( (array)$counts['InvoiceDo'] ) > 0 ) {
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				}
				else {
					print '<i class="icon-angle-down white"></i>&nbsp;';
				}
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[5] ?>">Оплаченных счетов</span>
			</td>
			<td class="text-center"><b class="fs-12"><?= array_sum( (array)$counts['InvoiceDo'] ) ?></b></td>
			<td class="text-right"><?= num_format( array_sum( (array)$summas['InvoiceDo'] ) ) ?>&nbsp;</td>
			<td class="pr10">
				<div style="width: <?= $vor['InvoiceDo'] ?>%; height: 5px; background:<?= $color[5] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden InvoiceDo ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {
						if ( $counts['InvoiceDo'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subInvoiceDo<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center">
									<b class="fs-10"><?= $counts['InvoiceDo'][ $key ] ?></b>&nbsp;
								</td>
								<td class="w150 text-center">
									<span class="fs-10"><?= num_format( $summas['InvoiceDo'][ $key ] ) ?></span>&nbsp;
								</td>
								<td class="text-left">
									<div style="width: <?= $counts['InvoiceDo'][ $key ] / array_sum( $counts['InvoiceDo'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[5] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subInvoiceDo<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table class="fs-10">
											<thead>
											<tr>
												<th class="w80">Номер</th>
												<th class="w100">Дата</th>
												<th class="w100">Дата план.</th>
												<th class="w100">Дата факт.</th>
												<th class="w120">Сумма</th>
												<th class="w250">Сделка</th>
												<th>Клиент</th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['InvoiceDo'][ $key ] as $row) {
												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $row['invoice'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $row['date'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $row['datePlan'] ?></span>
													</td>
													<td class="text-center">
														<span class="fs-10"><?= $row['dateFact'] ?></span>
													</td>
													<td class="text-right"><?= num_format( $row['invoiceSum'] ) ?></td>
													<td title="<?= $row['deal'] ?>">
														<div class="ellipsis">
															<a href="javascript:void(0)" onclick="viewDogovor('<?= $row['did'] ?>')"><i class="icon-briefcase-1 broun"></i>&nbsp;<?= $row['deal'] ?></a>
														</div>
													</td>
													<td class="text-left"><?= current_client( $row['clid'] ) ?></td>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>

		<tr class="rating ha bordered hand" data-id="closeDogs">
			<td>
				<?php
				if ( array_sum( $counts['closeDogs'] ) > 0 ) {
					print '<i class="icon-angle-down angle gray"></i>&nbsp;';
				}
				else {
					print '<i class="icon-angle-down white"></i>&nbsp;';
				}
				?>
				&nbsp;<span class="Bold fs-12" style="color:<?= $color[6] ?>">Закрыто сделок</span>
			</td>
			<td class="text-center"><b class="fs-12"><?= array_sum( $counts['closeDogs'] ) ?></b></td>
			<td class="text-right"><?= num_format( array_sum( $summas['closeDogs'] ) ) ?>&nbsp;</td>
			<td class="text-left" class="pr10">
				<div style="width: <?= $vor['closeDogs'] ?>%; height: 5px; background:<?= $color[6] ?>;"></div>
			</td>
		</tr>
		<tr class="hidden closeDogs ha1">
			<td colspan="4">
				<table>
					<tbody>
					<?php
					foreach ( $user as $key => $value ) {
						if ( $counts['closeDogs'][ $key ] > 0 ) {
							?>
							<tr class="subrating hand" data-tid="subcloseDogs<?= $key ?>">
								<td class="w350">
									<div class="paddleft20 Bold">
										<i class="icon-angle-down angle gray"></i>&nbsp;<span class="fs-10"><?= $value ?></span>
									</div>
								</td>
								<td class="w100 text-center">
									<b class="fs-10"><?= $counts['closeDogs'][ $key ] ?></b>&nbsp;
								</td>
								<td class="w150 text-center">
									<span class="fs-10"><?= num_format( $summas['closeDogs'][ $key ] ) ?></span>&nbsp;
								</td>
								<td>
									<div style="width: <?= $counts['closeDogs'][ $key ] / array_sum( $counts['closeDogs'] ) * 100 - 1 ?>%; height: 5px; background:<?= $color2[6] ?>;"></div>
								</td>
							</tr>
							<tr class="hidden subcloseDogs<?= $key ?> ha2">
								<td colspan="4">
									<div class="infodiv1">
										<table class="fs-10">
											<thead>
											<tr>
												<th class="w100">Дата план.</th>
												<th class="w120">Сумма план.</th>
												<th class="w100">Дата факт.</th>
												<th class="w120">Сумма факт.</th>
												<th class="w250">Сделка</th>
												<th>Клиент</th>
											</tr>
											</thead>
											<tbody>
											<?php
											foreach ($list['closeDogs'][ $key ] as $row) {

												if ( $row['summaFact'] <= 0 ) {
													$class = 'redbg-sub';
												}
												else {
													$class = 'greenbg-sub';
												}

												?>
												<tr>
													<td class="text-center">
														<span class="fs-10"><?= $row['date'] ?></span>
													</td>
													<td class="text-right"><?= num_format( $row['summaPlan'] ) ?></td>
													<td class="text-center">
														<span class="fs-10"><?= $row['dateFact'] ?></span>
													</td>
													<td class="text-right Bold <?= $class ?>"><?= num_format( $row['summaFact'] ) ?></td>
													<td title="<?= $row['deal'] ?>">
														<div class="ellipsis">
															<a href="javascript:void(0)" onclick="viewDogovor('<?= $row['did'] ?>')"><i class="icon-briefcase-1 broun"></i>&nbsp;<?= $row['deal'] ?></a>
														</div>
													</td>
													<td class="text-left"><?= current_client( $row['clid'] ) ?></td>
												</tr>
												<?php
											}
											?>
											</tbody>
										</table>
									</div>
								</td>
							</tr>
							<?php
						}
					}
					?>
					</tbody>
				</table>
			</td>
		</tr>
		</tbody>
	</table>

</div>

<div style="height:60px"></div>

<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script type="text/javascript">

	$('.rating').on('click', function () {
		var id = $(this).data('id');
		$('.' + id).toggleClass('hidden');
		$(this).find('td:first').find('i.angle').toggleClass('icon-angle-down icon-angle-up');
	});

	$('.subrating').on('click', function () {
		var id = $(this).data('tid');
		$('.' + id).toggleClass('hidden');
		$(this).find('td:first').find('i.angle').toggleClass('icon-angle-down icon-angle-up');
	});

</script>