<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2022.x           */

/* ============================ */

use Salesman\Guides;

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__ );
include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action    = $_REQUEST['action'];
$subaction = $_REQUEST['subaction'];
$user      = $_REQUEST['user'];
$step      = $_REQUEST['step'];

$da1     = $_REQUEST['da1'];
$da2     = $_REQUEST['da2'];
$input12 = $_REQUEST['input12'];

$thisfile = basename( $_SERVER['PHP_SELF'] );

$user_list = (array)$_REQUEST['user_list'];
$fields     = array_values( (array)$_REQUEST['field'] );
$query     = (array)$_REQUEST['field_query'];

$user_list = (!empty( $user_list )) ? $user_list : get_people( $iduser1, "yes", true );

$total = $dogs = $company = $list = [];
$sort  = '';

$steps = [
	18,
	54,
	//42,
	12,
	48,
	39
];
$stepsName = Guides ::Steps();

//доп.параметры к сделкам
$exclude = [];
foreach ( $fields as $i => $field ) {

	if ( !in_array( $field, $ar ) && !in_array( $field, [
			'close',
			'mcid'
		] ) ) {
		$sort .= " deal.".$field." = '".$field_query[ $i ]."' AND ";
	}
	elseif ( $field == 'close' ) {
		$sort .= $field_query[ $i ] != 'yes' ? " COALESCE(deal.{$field}, 'no') != 'yes' AND " : " COALESCE(deal.{$field}, 'no') == 'yes' AND ";
	}
	elseif ( $field == 'mcid' ) {
		$mc = $field_query[ $i ];
	}

}

if ( !empty( $user_list ) ) {
	$sort .= " deal.iduser IN (".yimplode( ",", $user_list ).") AND ";
}

if ( $action == 'data' ) {

	$title = '';

	switch ($subaction) {

		case "newDeals":

			$title = 'Новые сделки. Сотрудник: '.current_user($user);

			$thead = '
			<thead>
			<tr>
				<th class="w80">Создана</th>
				<th class="w80">План</th>
				<th>Сделка</th>
				<th class="w100">Сумма</th>
				<th class="w100">Маржа</th>
				<th class="w100">Статус</th>
			</tr>
			</thead>
			';

			$tbody = '';

			$q = "
				SELECT 
					deal.did,
					deal.datum,
					deal.datum_plan,
					deal.title,
					deal.kol,
					deal.marga,
					deal.close,
					deal.clid,
					deal.idcategory,
					cl.title as client
				FROM {$sqlname}dogovor `deal`
					LEFT JOIN {$sqlname}clientcat `cl` ON cl.clid = deal.clid
				WHERE 
					deal.autor = '$user' AND
					".($da1 != '' ? " ( deal.datum >= '$da1 00:00:00' and deal.datum <= '$da2 23:59:59' ) AND " : "")."
					".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
					deal.identity = '$identity' 
				ORDER BY deal.datum
			";
			$list = $db -> getAll($q);
			foreach ($list as $row){

				$tbody .= '
				<tr>
					<td>'.format_date_rus($row['datum']).'</td>
					<td>'.format_date_rus($row['datum_plan']).'</td>
					<td>
						<div class="ellipsis Bold fs-11">
							<A href="javascript:void(0)" onclick="openDogovor('.(int)$row['did'].')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> '.$row['title'].'</a>
						</div>
						<br>
						<div class="ellipsis fs-09">
							<A href="javascript:void(0)" onclick="openClient('.(int)$row['clid'].')" class="gray" title="Открыть в новом окне"><i class="icon-building broun"></i> '.$row['client'].'</a>
						</div>
					</td>
					<td class="text-right">'.xnum_format($row['kol'],'blue').'</td>
					<td class="text-right">'.xnum_format($row['marga'],'blue').'</td>
					<td>'.($row['close'] == 'yes' ? '<span class="red">Закрыта</span>' : '<span class="green">Активна</span>').'</td>
				</tr>
				';

			}

		break;
		case "closedDeals":

			$title = 'Закрытые сделки. Сотрудник: '.current_user($user);

			$thead = '
			<thead>
			<tr>
				<th class="w80">Создана</th>
				<th class="w80">План</th>
				<th class="w100">Закрыта</th>
				<th>Сделка</th>
				<th class="w100">Сумма</th>
				<th class="w100">Маржа</th>
				<th class="w100">Статус</th>
			</tr>
			</thead>
			';

			$tbody = '';

			$q = "
				SELECT 
					deal.did,
					deal.datum,
					deal.datum_plan,
					deal.datum_close,
					deal.title,
					deal.kol,
					deal.marga,
					deal.close,
					deal.clid,
					deal.idcategory,
					cl.title as client
				FROM {$sqlname}dogovor `deal`
					LEFT JOIN {$sqlname}clientcat `cl` ON cl.clid = deal.clid
				WHERE 
					(SELECT iduser FROM {$sqlname}history `hs` WHERE hs.tip = 'СобытиеCRM' AND hs.des LIKE 'Возможная сделка закрыта%' AND hs.did = deal.did AND hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ORDER BY hs.cid DESC LIMIT 1 ) = '$user' AND
					".($da1 != "" ? " ( deal.datum_close >= '$da1 00:00:00' and deal.datum_close <= '$da2 23:59:59' ) AND " : "")."
					".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
					deal.identity = '$identity'
			";
			$list = $db -> getAll($q);
			foreach ($list as $row){

				$tbody .= '
				<tr>
					<td>'.format_date_rus($row['datum']).'</td>
					<td>'.format_date_rus($row['datum_plan']).'</td>
					<td>'.format_date_rus($row['datum_plan']).'</td>
					<td>
						<div class="ellipsis Bold fs-11">
							<A href="javascript:void(0)" onclick="openDogovor('.$row['did'].')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> '.$row['title'].'</a>
						</div>
						<br>
						<div class="ellipsis fs-09">
							<A href="javascript:void(0)" onclick="openClient('.$row['clid'].')" class="gray" title="Открыть в новом окне"><i class="icon-building broun"></i> '.$row['client'].'</a>
						</div>
					</td>
					<td class="text-right">'.xnum_format($row['kol'],'blue').'</td>
					<td class="text-right">'.xnum_format($row['marga'],'blue').'</td>
					<td>'.($row['close'] == 'yes' ? '<span class="red">Закрыта</span>' : '<span class="green">Активна</span>').'</td>
				</tr>
				';

			}

		break;
		case "newInvoices":

			$title = 'Новые счета. Сотрудник: '.current_user($user);

			$thead = '
			<thead>
			<tr>
				<th class="w80">Счет</th>
				<th class="w80">План / Факт</th>
				<th class="w100">Сумма</th>
				<th class="w100">Статус</th>
				<th>Сделка</th>
				<th class="w60">Этап</th>
				<th class="w100">Сумма</th>
				<th class="w100">Маржа</th>
				<th class="w100">Статус</th>
			</tr>
			</thead>
			';

			$tbody = '';

			$q = "
				SELECT
					cr.did as did,
					cr.iduser as iduser,
					cr.summa_credit as summa,
					cr.do as do,
					cr.datum_credit as dplan,
					cr.invoice_date as dfact,
					DATE_FORMAT(cr.datum, '%Y-%m-%d') as idatum,
					cr.invoice as invoice,
					cr.clid as clid,
					deal.datum as datum,
					deal.title as dogovor,
					deal.kol as dsumma,
					deal.marga as dmarga,
					deal.iduser as diduser,
					deal.close as close,
					deal.idcategory as step,
					cl.title as client
				FROM ".$sqlname."credit `cr`
					LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
					LEFT JOIN ".$sqlname."clientcat `cl` ON cl.clid = cr.clid
				WHERE 
					cr.iduser = '$user' AND
					".($da1 != "" ? " ( cr.datum >= '$da1 00:00:00' and cr.datum <= '$da2 23:59:59' ) AND " : "")."
					".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
					cr.identity = '$identity'
			";
			$list = $db -> getAll($q);
			foreach ($list as $row){

				$tbody .= '
				<tr>
					<td>
						<div class="fs-09">'.format_date_rus($row['idatum']).'</div>
						<div class="fs-07 gray">№ '.$row['invoice'].'</div>
					</td>
					<td>
						<div class="fs-09">'.format_date_rus($row['dplan']).'</div>
						<div class="fs-07 gray">'.format_date_rus($row['dfact']).'</div>
					</td>
					<td class="text-right">'.xnum_format($row['summa'],'blue').'</td>
					<td>'.($row['do'] == "on" ? '<span class="green">Оплачен</span>' : '<span class="blue">Выставлен</span>').'</td>
					<td>
						<div class="ellipsis Bold fs-11">
							<A href="javascript:void(0)" onclick="openDogovor('.$row['did'].')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> '.$row['dogovor'].'</a>
						</div>
						<br>
						<div class="ellipsis fs-09">
							<A href="javascript:void(0)" onclick="openClient('.$row['clid'].')" class="gray" title="Открыть в новом окне"><i class="icon-building broun"></i> '.$row['client'].'</a>
						</div>
					</td>
					<td>'.$stepsName[$row['step']]['title'].'%</td>
					<td class="text-right">'.xnum_format($row['dsumma'],'blue').'</td>
					<td class="text-right">'.xnum_format($row['dmarga'],'blue').'</td>
					<td>'.($row['close'] == 'yes' ? '<span class="red">Закрыта</span>' : '<span class="green">Активна</span>').'</td>
				</tr>
				';

			}

		break;
		case "doInvoices":

			$title = 'Оплаченные счета. Сотрудник: '.current_user($user);

			$thead = '
			<thead>
			<tr>
				<th class="w80">Счет</th>
				<th class="w80">План / Факт</th>
				<th class="w100">Сумма</th>
				<th>Сделка</th>
				<th class="w60">Этап</th>
				<th class="w100">Сумма</th>
				<th class="w100">Маржа</th>
				<th class="w100">Статус</th>
			</tr>
			</thead>
			';

			$tbody = '';

			$q = "
				SELECT
					cr.did as did,
					cr.iduser as iduser,
					cr.summa_credit as summa,
					cr.do as do,
					cr.datum_credit as dplan,
					cr.invoice_date as dfact,
					DATE_FORMAT(cr.datum, '%Y-%m-%d') as idatum,
					cr.invoice as invoice,
					cr.clid as clid,
					deal.datum as datum,
					deal.title as dogovor,
					deal.kol as dsumma,
					deal.marga as dmarga,
					deal.iduser as diduser,
					deal.close as close,
					deal.idcategory as step,
					cl.title as client
				FROM ".$sqlname."credit `cr`
					LEFT JOIN ".$sqlname."dogovor `deal` ON cr.did = deal.did
					LEFT JOIN ".$sqlname."clientcat `cl` ON cl.clid = cr.clid
				WHERE 
					cr.iduser = '$user' AND
					cr.do = 'on' AND
					".($da1 != "" ? " ( cr.invoice_date >= '$da1 00:00:00' and cr.invoice_date <= '$da2 23:59:59' ) AND " : "")."
					".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
					cr.identity = '$identity'
			";
			$list = $db -> getAll($q);
			foreach ($list as $row){

				$tbody .= '
				<tr>
					<td>
						<div class="fs-09">'.format_date_rus($row['idatum']).'</div>
						<div class="fs-07 gray">№ '.$row['invoice'].'</div>
					</td>
					<td>
						<div class="fs-09">'.format_date_rus($row['dplan']).'</div>
						<div class="fs-07 gray">'.format_date_rus($row['dfact']).'</div>
					</td>
					<td class="text-right">'.xnum_format($row['summa'],'blue').'</td>
					<td>
						<div class="ellipsis Bold fs-11">
							<A href="javascript:void(0)" onclick="openDogovor('.$row['did'].')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> '.$row['dogovor'].'</a>
						</div>
						<br>
						<div class="ellipsis fs-09">
							<A href="javascript:void(0)" onclick="openClient('.$row['clid'].')" class="gray" title="Открыть в новом окне"><i class="icon-building broun"></i> '.$row['client'].'</a>
						</div>
					</td>
					<td>'.$stepsName[$row['step']]['title'].'%</td>
					<td class="text-right">'.xnum_format($row['dsumma'],'blue').'</td>
					<td class="text-right">'.xnum_format($row['dmarga'],'blue').'</td>
					<td>'.($row['close'] == 'yes' ? '<span class="red">Закрыта</span>' : '<span class="green">Активна</span>').'</td>
				</tr>
				';

			}

		break;
		case "newContract":

			$title = 'Новые договоры. Сотрудник: '.current_user($user);

			$thead = '
			<thead>
			<tr>
				<th class="w80">Дата</th>
				<th class="w80">Номер</th>
				<th>Сделка</th>
				<th class="w60">Этап</th>
				<th class="w100">Сумма</th>
				<th class="w100">Статус</th>
			</tr>
			</thead>
			';

			$tbody = '';

			$q = "
				SELECT 
					cnt.deid,
					cnt.datum,
					cnt.number,
					cnt.iduser,
					cnt.did,
					cnt.clid,
					deal.title as dogovor,
					deal.idcategory as step,
					deal.kol as dsumma,
					cl.title as client
				FROM {$sqlname}contract `cnt`
					LEFT JOIN {$sqlname}dogovor `deal` ON cnt.did = deal.did
					LEFT JOIN ".$sqlname."clientcat `cl` ON cl.clid = cnt.clid
				WHERE 
					cnt.iduser = '$user' AND
					".($da1 != "" ? " ( cnt.datum >= '$da1 00:00:00' and cnt.datum <= '$da2 23:59:59' ) AND " : "")."
					".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
					cnt.idtype IN (SELECT id FROM ".$sqlname."contract_type where type = 'get_dogovor' and identity = '$identity') AND
					cnt.identity = '$identity'
			";
			$list = $db -> getAll($q);
			foreach ($list as $row){

				$tbody .= '
				<tr>
					<td><div>'.get_sfdate2($row['datum']).'</div></td>
					<td><div>'.$row['number'].'</div></td>
					<td>
						<div class="ellipsis Bold fs-11">
							<A href="javascript:void(0)" onclick="openClient('.$row['clid'].')" title="Открыть в новом окне"><i class="icon-building broun"></i> '.$row['client'].'</a>
						</div>
						<br>
						<div class="ellipsis fs-09 '.($row['did'] == 0 ? 'hidden' : '').'">
							<A href="javascript:void(0)" onclick="openDogovor('.$row['did'].')" class="gray" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> '.$row['dogovor'].'</a>
						</div>
					</td>
					<td>
						'.($row['did'] == 0 ? '' : $stepsName[$row['step']]['title'].'%').'
					</td>
					<td class="text-right">
						'.($row['did'] == 0 ? '' : xnum_format($row['dsumma'],'blue')).'
					</td>
					<td>
						<div class="'.($row['did'] == 0 ? 'hidden' : '').'">'.($row['close'] == 'yes' ? '<span class="red">Закрыта</span>' : '<span class="green">Активна</span>').'</div>
					</td>
				</tr>
				';

			}

		break;
		case "funnel":

			$title = 'Воронка. Этап: '.$stepsName[$step]['title'].'%, Сотрудник: '.current_user($user);

			$thead = '
			<thead>
			<tr>
				<th class="w80">Дата</th>
				<th>Сделка</th>
				<th class="w100">Этап</th>
				<th class="w100">Этап текущий</th>
				<th class="w100">Сумма</th>
				<th class="w100">Статус</th>
			</tr>
			</thead>
			';

			$tbody = '';

			//print

			if($step != 18) {
				//print
				$q = "
					SELECT
						DISTINCT sl.did,
						sl.step,
						max(sl.datum) as datum,
						deal.title,
						deal.kol,
						deal.close,
						deal.clid,
						deal.idcategory,
						cl.title as client
					FROM {$sqlname}steplog `sl`
						LEFT JOIN {$sqlname}dogovor `deal` ON sl.did = deal.did
						LEFT JOIN {$sqlname}clientcat `cl` ON cl.clid = deal.clid
					WHERE
						sl.iduser = '$user' AND
						".($da1 != "" ? " ( sl.datum >= '$da1 00:00:00' and sl.datum <= '$da2 23:59:59' ) AND " : "")."
						".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
						sl.step = '$step'
					GROUP BY sl.did
					-- ORDER BY sl.datum DESC
				";
			}
			else{
				$q = "
					SELECT
						DISTINCT sl.did,
						sl.step,
						max(sl.datum) as datum,
						deal.title,
						deal.kol,
						deal.close,
						deal.clid,
						deal.idcategory,
						cl.title as client
					FROM {$sqlname}steplog `sl`
						LEFT JOIN {$sqlname}dogovor `deal` ON sl.did = deal.did
						LEFT JOIN {$sqlname}clientcat `cl` ON cl.clid = deal.clid
					WHERE
						sl.iduser = '$user' AND
						".($da1 != "" ? " ( sl.datum >= '$da1 00:00:00' and sl.datum <= '$da2 23:59:59' ) AND " : "")."
						".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
						(SELECT COUNT(cid) FROM {$sqlname}history WHERE tip IN ('СобытиеCRM','ЛогCRM') AND des LIKE '%Смена ответственного%' AND datum >= '$da1 00:00:00' and datum <= '$da2 23:59:59') > 0 AND
						sl.step = '$step'
					GROUP BY sl.did
					-- ORDER BY sl.datum DESC
				";
			}

			$list = $db -> getAll($q);
			foreach ($list as $row){

				$tbody .= '
				<tr>
					<td>'.get_sfdate2($row['datum']).'</td>
					<td>
						<div class="ellipsis Bold fs-11">
							<A href="javascript:void(0)" onclick="openDogovor('.$row['did'].')" title="Открыть в новом окне"><i class="icon-briefcase blue"></i> '.$row['title'].'</a>
						</div>
						<br>
						<div class="ellipsis fs-09">
							<A href="javascript:void(0)" onclick="openClient('.$row['clid'].')" class="gray" title="Открыть в новом окне"><i class="icon-building broun"></i> '.$row['client'].'</a>
						</div>
					</td>
					<td>'.$stepsName[$row['step']]['title'].'%</td>
					<td>'.$stepsName[$row['idcategory']]['title'].'%</td>
					<td class="text-right">'.xnum_format($row['kol'],'blue').'</td>
					<td>'.($row['close'] == 'yes' ? '<span class="red">Закрыта</span>' : '<span class="green">Активна</span>').'</td>
				</tr>
				';

			}

		break;
		/*
		case "historycalls":

			$q = "
				SELECT
					hs.iduser,
					SUM(hs.sec) as count
				FROM {$sqlname}callhistory `hs`
				WHERE
					hs.iduser IN (".yimplode( ",", $user_list ).") AND
					".($da1 != "" ? " ( hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ) AND " : "")."
					hs.direct IN ('income','outcome')
				GROUP BY 1
			";

		break;
		case "historywodeals":

			$q = "
				SELECT
					hs.iduser,
					COUNT(*) as count
				FROM {$sqlname}history `hs`
				WHERE
					hs.iduser IN (".yimplode( ",", $user_list ).") AND
					".($da1 != "" ? " ( hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ) AND " : "")."
					(hs.did + 0) = 0
				GROUP BY 1
			";

		break;
		case "historydeals":

			$q = "
				SELECT
					hs.iduser,
					COUNT(*) as count
				FROM {$sqlname}history `hs`
					LEFT JOIN {$sqlname}dogovor `deal` ON hs.did = deal.did
				WHERE
					hs.iduser IN (".yimplode( ",", $user_list ).") AND
					".($da1 != "" ? " ( hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ) AND " : "")."
					".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
					(hs.did + 0) > 0
				GROUP BY 1
			";

		break;
		*/

	}

	print
	$html = '
	<h2>'.$title.'</h2>
	<table class="top">
	'.$thead.'
	'.$tbody.'
	</table>
	';

	exit();

}

// Созданных сделок
$q = "
	SELECT 
		deal.autor,
		COUNT(*) as count
	FROM {$sqlname}dogovor `deal`
	WHERE 
		deal.autor IN (".yimplode( ",", $user_list ).") AND
		".($da1 != '' ? " ( deal.datum >= '$da1 00:00:00' and deal.datum <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
		deal.identity = '$identity' 
	GROUP BY 1
";
$d = $db -> getAll( $q );
//print_r($d);
foreach ( $d as $item ) {

	$list['newDeals'][ $item['autor'] ] = $item['count'];

}

// Закрытых сделок
$q = "
	SELECT 
		(SELECT iduser FROM {$sqlname}history `hs` WHERE hs.tip = 'СобытиеCRM' AND hs.des LIKE 'Возможная сделка закрыта%' AND hs.did = deal.did AND hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ORDER BY hs.cid DESC LIMIT 1 ) as iduser,
		COUNT(*) as count
	FROM {$sqlname}dogovor `deal`
	WHERE 
		(SELECT iduser FROM {$sqlname}history `hs` WHERE hs.tip = 'СобытиеCRM' AND hs.des LIKE 'Возможная сделка закрыта%' AND hs.did = deal.did AND hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ORDER BY hs.cid DESC LIMIT 1 ) IN (".yimplode( ",", $user_list ).") AND
		".($da1 != "" ? " ( deal.datum_close >= '$da1 00:00:00' and deal.datum_close <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
		deal.identity = '$identity' 
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['closedDeals'][ $item['iduser'] ] = $item['count'];

}

// Выставлено счетов
$q = "
	SELECT 
		cr.iduser,
		COUNT(*) as count
	FROM {$sqlname}credit `cr`
		LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
	WHERE 
		cr.iduser IN (".yimplode( ",", $user_list ).") AND
		".($da1 != "" ? " ( cr.datum >= '$da1 00:00:00' and cr.datum <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
		deal.did = cr.did AND 
		cr.identity = '$identity' 
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['newInvoices'][ $item['iduser'] ] = $item['count'];

}

// Оплачено счетов
$q = "
	SELECT 
		cr.iduser,
		COUNT(*) as count
	FROM {$sqlname}credit `cr`
		LEFT JOIN {$sqlname}dogovor `deal` ON cr.did = deal.did
	WHERE 
		cr.iduser IN (".yimplode( ",", $user_list ).") AND
		cr.do = 'on' AND
		".($da1 != "" ? " ( cr.invoice_date >= '$da1 00:00:00' and cr.invoice_date <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
		deal.did = cr.did AND 
		cr.identity = '$identity' 
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['doInvoices'][ $item['iduser'] ] = $item['count'];

}

// Новых договоров
$q = "
	SELECT 
		cnt.iduser,
		COUNT(*) as count
	FROM {$sqlname}contract `cnt`
		LEFT JOIN {$sqlname}dogovor `deal` ON cnt.did = deal.did
	WHERE 
		cnt.iduser IN (".yimplode( ",", $user_list ).") AND
		".($da1 != "" ? " ( cnt.datum >= '$da1 00:00:00' and cnt.datum <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
		deal.did = cnt.did AND 
		cnt.idtype IN (SELECT id FROM ".$sqlname."contract_type where type = 'get_dogovor' and identity = '$identity') AND
		cnt.identity = '$identity' 
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['newContract'][ $item['iduser'] ] = $item['count'];

}

// Воронка
$q = "
	SELECT
		sl.iduser,
		sl.step,
		COUNT(DISTINCT sl.did) as count
	FROM {$sqlname}steplog `sl`
		LEFT JOIN {$sqlname}dogovor `deal` ON sl.did = deal.did -- AND sl.step = deal.idcategory
	WHERE
		sl.iduser IN (".yimplode( ",", $user_list ).") AND
		".($da1 != "" ? " ( sl.datum >= '$da1 00:00:00' and sl.datum <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? "deal.input12 = '$input12' AND " : "")."
		sl.step IN (".yimplode( ",", $steps ).") AND
		sl.step != '$steps[0]'
	GROUP BY 1, 2
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['funnel'][ $item['step'] ][ $item['iduser'] ] = $item['count'];

}

// для этапа 0% считаем по-другому
$q = "
	SELECT
		sl.iduser,
		sl.step,
		COUNT(DISTINCT sl.did) as count
	FROM {$sqlname}steplog `sl`
		LEFT JOIN {$sqlname}dogovor `deal` ON sl.did = deal.did AND sl.step = '$steps[0]'
	WHERE
		sl.iduser IN (".yimplode( ",", $user_list ).") AND
		".($da1 != "" ? " ( sl.datum >= '$da1 00:00:00' and sl.datum <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? "deal.input12 = '$input12' > 0 AND " : "")."
		(SELECT COUNT(cid) FROM {$sqlname}history WHERE tip IN ('СобытиеCRM','ЛогCRM') AND des LIKE '%Смена ответственного%' AND datum >= '$da1 00:00:00' and datum <= '$da2 23:59:59') > 0 AND
		-- deal.idcategory = '$steps[0]' AND
		sl.step = '$steps[0]'
	GROUP BY 1, 2
";
$d = $db -> getAll( $q );
//print $db -> lastQuery();
foreach ( $d as $item ) {

	$list['funnel'][ 18 ][ $item['iduser'] ] = $item['count'];

}

// Телефонные звонки
$q = "
	SELECT
		hs.iduser,
		SUM(hs.sec) as count
	FROM {$sqlname}callhistory `hs`
	WHERE
		hs.iduser IN (".yimplode( ",", $user_list ).") AND
		".($da1 != "" ? " ( hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ) AND " : "")."
		hs.direct IN ('income','outcome')
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['history']['call'][ $item['iduser'] ] = round( $item['count'] / 60 );

}

// Активности без сделок
$q = "
	SELECT
		hs.iduser,
		COUNT(*) as count
	FROM {$sqlname}history `hs`
	WHERE
		hs.iduser IN (".yimplode( ",", $user_list ).") AND
		hs.tip NOT IN ('СобытиеCRM','ЛогCRM') AND
		".($da1 != "" ? " ( hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ) AND " : "")."
		(hs.did + 0) = 0
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['history']['wodeals'][ $item['iduser'] ] = $item['count'];

}

// Активности со сделками
$q = "
	SELECT
		hs.iduser,
		COUNT(*) as count
	FROM {$sqlname}history `hs`
		LEFT JOIN {$sqlname}dogovor `deal` ON hs.did = deal.did
	WHERE
		hs.iduser IN (".yimplode( ",", $user_list ).") AND
		hs.tip NOT IN ('СобытиеCRM','ЛогCRM') AND
		".($da1 != "" ? " ( hs.datum >= '$da1 00:00:00' and hs.datum <= '$da2 23:59:59' ) AND " : "")."
		".($input12 != '' ? " deal.input12 = '$input12' AND " : "")."
		(hs.did + 0) > 0
	GROUP BY 1
";
$d = $db -> getAll( $q );
foreach ( $d as $item ) {

	$list['history']['deals'][ $item['iduser'] ] = $item['count'];

}

// Конверсия
foreach ( $user_list as $iduser ) {

	$list['conversation'][ $iduser ] = [
		(int)$list['funnel'][18][ $iduser ] > 0 ? round( (int)$list['funnel'][54][ $iduser ] / (int)$list['funnel'][18][ $iduser ] * 100 ) : 0,
		(int)$list['funnel'][54][ $iduser ] > 0 ? round( (int)$list['funnel'][12][ $iduser ] / (int)$list['funnel'][54][ $iduser ] * 100 ) : 0,
		(int)$list['funnel'][12][ $iduser ] > 0 ? round( (int)$list['funnel'][48][ $iduser ] / (int)$list['funnel'][12][ $iduser ] * 100 ) : 0,
		(int)$list['funnel'][48][ $iduser ] > 0 ? round( (int)$list['funnel'][39][ $iduser ] / (int)$list['funnel'][48][ $iduser ] * 100 ) : 0,
		(int)$list['funnel'][39][ $iduser ] > 0 ? round( $list['doInvoices'][ $iduser ] / (int)$list['funnel'][39][ $iduser ] * 100 ) : 0,
		$list['newInvoices'][ $iduser ] > 0 ? round( $list['doInvoices'][ $iduser ] * 100 / $list['newInvoices'][ $iduser ] ) : 0
	];

}


// Среднее
$list['middle']['deal'] = [
	round( array_sum( array_values( (array)$list['newDeals'] ) ) / count( (array)$user_list ), 0 ),
	round( array_sum( array_values( (array)$list['closedDeals'] ) ) / count( (array)$user_list ), 0 ),
	round( array_sum( array_values( (array)$list['newInvoices'] ) ) / count( (array)$user_list ), 0 ),
	round( array_sum( array_values( (array)$list['newContract'] ) ) / count( (array)$user_list ), 0 ),
	round( array_sum( array_values( (array)$list['doInvoices'] ) ) / count( (array)$user_list ), 0 )
];

foreach ( $steps as $step ) {
	$list['middle']['funnel'][] = round( array_sum( array_values( (array)$list['funnel'][ $step ] ) ), 0 );
}

$list['middle']['conversation'] = [
	$list['middle']['funnel'][0] > 0 ? round( $list['middle']['funnel'][1] / $list['middle']['funnel'][0] * 100 ) : 0,
	$list['middle']['funnel'][1] > 0 ? round( $list['middle']['funnel'][2] / $list['middle']['funnel'][1] * 100 ) : 0,
	$list['middle']['funnel'][2] > 0 ? round( $list['middle']['funnel'][3] / $list['middle']['funnel'][2] * 100 ) : 0,
	$list['middle']['funnel'][3] > 0 ? round( $list['middle']['funnel'][4] / $list['middle']['funnel'][3] * 100 ) : 0,
	$list['middle']['funnel'][5] > 0 ? round( $list['middle']['deal'][4] / $list['middle']['funnel'][5] * 100 ) : 0,
	$list['middle']['deal'][2] > 0 ? round( $list['middle']['deal'][4] / $list['middle']['deal'][2] * 100 ) : 0,
	/*$list['middle']['funnel'][4] > 0 ? round( $list['middle']['deal'][4] / $list['middle']['funnel'][4] * 100 ) : 0,
	$list['middle']['deal'][4] > 0 ? round( $list['middle']['deal'][4] / $list['middle']['deal'][2] * 100 ) : 0,*/
];

$list['middle']['history'] = [
	round( array_sum( array_values( (array)$list['history']['call'] ) ) / count( (array)$user_list ), 0 ),
	round( array_sum( array_values( (array)$list['history']['wodeals'] ) ) / count( (array)$user_list ), 0 ),
	round( array_sum( array_values( (array)$list['history']['deals'] ) ) / count( (array)$user_list ), 0 )
];

//print_r($list);
//exit();
?>
<style>
	.click{
		cursor: pointer;
	}
	.click:hover{
		background: var(--mint);
	}

	.wt{
		width: 60px !important;
	}
	table{
		width: max-content;
	}
	table td {
		padding-top: 10px;
		padding-bottom: 10px;
	}
	.data th,
	.data tfoot td{
		border-right: 0;
		font-size: 1.0em;
		height: 2.05em;
		border-bottom: 0 !important;
		box-shadow: rgba(255, 255, 255, .5) 0px 1px 1px !important;
	}
	#dataview table{
		width: 99%;
	}
	#dataview th{
		font-size: 0.8rem;
	}
	@media (min-width : 1500px){

		.wt{
			width: calc( calc(100% - 200px) / 17) !important;
		}
		table{
			width: initial;
		}

	}

</style>

<div class="relativ mt20 mb20 wp95 text-center">
	<h1 class="uppercase fs-14 m0 mb10">Комплексный анализ работы по сделкам</h1>
	<div class="gray2">за период&nbsp;с&nbsp;<?= format_date_rus( $da1 ) ?>&nbsp;по&nbsp;<?= format_date_rus( $da2 ) ?></div>
</div>

<div class="infodiv w400">

	<div class="flex-container">

		<div class="flex-string wp30">
			<div class="radio">
				<label>
					<input name="input12" type="radio" id="input12" value="Первичная" <?php echo($input12 == 'Первичная' ? 'checked' : ''); ?> />
					<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
					<span class="title">Первичная</span>
				</label>
			</div>
		</div>

		<div class="flex-string wp30">
			<div class="radio">
				<label>
					<input name="input12" type="radio" id="input12" value="Вторичная" <?php echo($input12 == 'Вторичная' ? 'checked' : ''); ?> />
					<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
					<span class="title">Вторичная</span>
				</label>
			</div>
		</div>

		<div class="flex-string wp30">
			<div class="radio">
				<label>
					<input name="input12" type="radio" id="input12" value="" <?php echo($input12 == '' ? 'checked' : ''); ?> />
					<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
					<span class="title">Не указано</span>
				</label>
			</div>
		</div>

	</div>

</div>

<hr>

<div style="width: 99%; overflow-x: auto" id="dataContainer">

	<div class="data" style="max-height: 65vh;">

		<table class="top fs-09" id="main">
			<thead class="flh-09 fs-09">
			<tr>
				<th class="w160 bgray" rowspan="2"><B>Сотрудники</B></th>
				<th colspan="5" class="bluebg-dark white">Сделки</th>
				<th colspan="<?=count($steps)?>" class="graybg-dark white">Воронка</th>
				<th colspan="6" class="brounbg-dark white">Конверсия</th>
				<th colspan="3" class="greenbg-dark white">Активность</th>
			</tr>
			<tr>
				<!-- Сделка -->
				<th class="wt bluebg-lite">Новые</th>
				<th class="wt bluebg-lite">Закрыто</th>
				<th class="wt bluebg-lite">Новые счета</th>
				<th class="wt bluebg-lite">Новые договоры</th>
				<th class="wt bluebg-lite">Оплачено счетов</th>

				<!-- Воронка -->
				<?php
				foreach ( $steps as $step ) {
					print '<th class="wt bgray">'.$stepsName[ $step ]['content'].'<br>'.$stepsName[ $step ]['title'].'%</th>';
				}
				?>

				<!-- Конверсия -->
				<th class="wt brounbg-sub">20% / 0%</th>
				<th class="wt brounbg-sub">40% / 20%</th>
				<th class="wt brounbg-sub">50% / 40%</th>
				<th class="wt brounbg-sub">70% / 50%</th>
				<th class="wt brounbg-sub">Оплата / 70%</th>
				<th class="wt brounbg-sub">Выставлено / Оплата</th>

				<!-- Активности -->
				<th class="wt greenbg-lite">Звонки</th>
				<th class="wt greenbg-lite">Без сделок</th>
				<th class="wt greenbg-lite">По сделкам</th>
			</tr>
			</thead>
			<tbody>
			<?php
			foreach ( $user_list as $iduser ) {

				print '<tr data-user="'.$iduser.'">';

				// сделка
				print '
					<td class="flh-101">'.current_user( $iduser, 'yes' ).'</td>
					<td class="text-right click" data-subaction="newDeals">'.(int)$list['newDeals'][ $iduser ].'</td>
					<td class="text-right click" data-subaction="closedDeals">'.(int)$list['closedDeals'][ $iduser ].'</td>
					<td class="text-right click" data-subaction="newInvoices">'.(int)$list['newInvoices'][ $iduser ].'</td>
					<td class="text-right click" data-subaction="newContract">'.(int)$list['newContract'][ $iduser ].'</td>
					<td class="text-right click" data-subaction="doInvoices">'.(int)$list['doInvoices'][ $iduser ].'</td>
				';

				// воронка
				foreach ( $steps as $step ) {
					print '<td class="text-right click" data-subaction="funnel" data-step="'.$step.'">'.(int)$list['funnel'][ $step ][ $iduser ].'</td>';
				}

				// конверсия
				print '
					<td class="text-right">'.$list['conversation'][ $iduser ][0].'</td>
					<td class="text-right">'.$list['conversation'][ $iduser ][1].'</td>
					<td class="text-right">'.$list['conversation'][ $iduser ][2].'</td>
					<td class="text-right">'.$list['conversation'][ $iduser ][3].'</td>
					<td class="text-right">'.$list['conversation'][ $iduser ][4].'</td>
					<td class="text-right">'.$list['conversation'][ $iduser ][5].'</td>
				';

				// активность
				print '
					<td class="text-right" data-subaction="historycall">'.(int)$list['history']['call'][ $iduser ].'</td>
					<td class="text-right" data-subaction="historywodeals">'.(int)$list['history']['wodeals'][ $iduser ].'</td>
					<td class="text-right" data-subaction="historydeals">'.(int)$list['history']['deals'][ $iduser ].'</td>
				';

				print '</tr>';

			}
			?>
			</tbody>
			<tfoot>
			<tr class="Bold">
				<td class="bgray">Итого (среднее)</td>

				<!-- Сделка -->
				<td class="text-right bluebg-lite"><?= $list['middle']['deal'][0] ?></td>
				<td class="text-right bluebg-lite"><?= $list['middle']['deal'][1] ?></td>
				<td class="text-right bluebg-lite"><?= $list['middle']['deal'][2] ?></td>
				<td class="text-right bluebg-lite"><?= $list['middle']['deal'][3] ?></td>
				<td class="text-right bluebg-lite"><?= $list['middle']['deal'][4] ?></td>

				<!-- Воронка -->
				<?php
				foreach ( $steps as $i => $step ) {
					print '<td class="text-right bgray">'.$list['middle']['funnel'][ $i ].'</td>';
				}
				?>

				<!-- Конверсия -->
				<td class="text-right brounbg-sub"><?= $list['middle']['conversation'][0] ?></td>
				<td class="text-right brounbg-sub"><?= $list['middle']['conversation'][1] ?></td>
				<td class="text-right brounbg-sub"><?= $list['middle']['conversation'][2] ?></td>
				<td class="text-right brounbg-sub"><?= $list['middle']['conversation'][3] ?></td>
				<td class="text-right brounbg-sub"><?= $list['middle']['conversation'][4] ?></td>
				<td class="text-right brounbg-sub"><?= $list['middle']['conversation'][5] ?></td>

				<!-- Активности -->
				<td class="text-right greenbg-lite"><?= $list['middle']['history'][0] ?></td>
				<td class="text-right greenbg-lite"><?= $list['middle']['history'][1] ?></td>
				<td class="text-right greenbg-lite"><?= $list['middle']['history'][2] ?></td>
			</tr>
			</tfoot>
		</table>

	</div>

</div>

<div class="mt20 hidden wp99" id="dataview"></div>
<div class="space-100"></div>

<script src="/assets/js/tableHeadFixer/tableHeadFixer.js"></script>
<script>

	$(function(){

		$("#main").tableHeadFixer({
			'head': true,
			'foot': false,
			'z-index': 12000,
			'left': 1
		}).css('z-index', '100');

		$("#main").find('td:nth-child(1)').css('z-index', '110');

		$('#main').find('td').each(function(){

			var count = parseInt( $(this).html() )

			if(count === 0){
				$(this).addClass('gray').removeClass('click')
			}

		})

	})

	$('.click').off('click')
	$('.click').on('click', function (){

		var user = $(this).closest('tr').data('user')
		var subaction = $(this).data('subaction')
		var step = parseInt( $(this).data('step') )
		var input12 = $('#input12:checked').val()
		var d1 = $('#da1').val()
		var d2 = $('#da2').val()

		$('#dataview').removeClass('hidden').empty().append('<img src="/assets/images/loading.gif">')

		$.get("/reports/<?=$thisfile?>?action=data&subaction="+subaction+"&user="+user+"&step="+step+"&input12="+input12+"&da1="+d1+"&da2="+d2, function (data){

			$('#dataview').empty().html(data)

		})

	})

</script>