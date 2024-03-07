<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.x          */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$y  = date( 'y' );
$m  = date( 'm' );
$nd = date( 'd' );

$sort = get_people( $iduser1, 'yes' );

function actual_date($dat_org) {
	$dat_org  = explode( "-", $dat_org );
	$date_new = date( 'Y' )."-".$dat_org[1]."-".$dat_org[2];

	return $date_new;
}

$alld  = 0;
$alldr = 0;
$allc  = 0;

$dogarray = [];

$da1 = current_datum( '10' );
$da2 = current_datum( '-10' );

$s    = '';
$dogs = '';

$allco = 0;

$stepInHold = customSettings( 'stepInHold' );

if ( $stepInHold['step'] > 0 && $stepInHold['input'] != '' ) {

	$s .= " (deal.idcategory != '$stepInHold[step]' OR (deal.idcategory = '$stepInHold[step]' AND DATE(deal.".$stepInHold['input'].") <= DATE(NOW()) )) AND ";

}


if ( $_REQUEST['action'] == "get_notifi" ) {

	//Здесь готовим список актуальных сделок
	$alld = $db -> getOne( "
	SELECT 
		COUNT(*)
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = deal.clid
		LEFT JOIN {$sqlname}dogcategory `dc` ON dc.idcategory = deal.idcategory
	WHERE 
		deal.datum_plan BETWEEN '$da1' and '$da2' AND 
		COALESCE(deal.close, 'no') != 'yes' AND 
		deal.iduser IN (".implode( ",", $sort ).") AND 
		$s
		deal.identity = '$identity' 
	ORDER BY deal.datum_plan
	" );

	$result = $db -> getAll( "
	SELECT 
		deal.did,
		deal.title,
		deal.idcategory,
		deal.datum_plan,
		dc.title as step,
		deal.clid,
		clt.title as client
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = deal.clid
		LEFT JOIN {$sqlname}dogcategory `dc` ON dc.idcategory = deal.idcategory
	WHERE 
		deal.datum_plan BETWEEN '$da1' and '$da2' AND 
		COALESCE(deal.close, 'no') != 'yes' AND 
		deal.iduser IN (".implode( ",", $sort ).") AND 
		$s
		deal.identity = '$identity' 
	ORDER BY deal.datum_plan
	LIMIT 20
	" );
	foreach ( $result as $data ) {

		$dogstatus = $db -> getOne( "SELECT title FROM {$sqlname}dogcategory WHERE idcategory = '$data[idcategory]' and identity = '$identity'" );

		$dday       = datestoday( $data['datum_plan'] );
		$dogarray[] = $data['did'];

		$znak = '';

		if ( $data['clid'] > 0 )
			$cl = $data['client'];

		if ( $dday == 0 ) {
			$color   = "green";
			$bgcolor = "background:#E74B3B";
		}
		elseif ( $dday < 0 ) {
			$color   = "red";
			$znak    = "-";
			$bgcolor = "background-color:#FFF";
		}
		else {
			$color   = "blue";
			$znak    = "+";
			$bgcolor = "background-color:#FFF";
		}

		$dogs .= '
		<tr class="ha">
			<td class="text-right w60">
				<div class="Bold '.$color.'">'.$znak.' '.abs( $dday ).' дн.</div>
			</td>
			<td>
				<div class="ellipsis Bold"><a href="javascript:void(0)" onClick="openDogovor(\''.$data['did'].'\')" class="'.$color.'">'.$data['title'].'</a></div><br>
				<div class="ellipsis mb5">'.$cl.'</div>
			</td>
		</tr>
		';

	}

	if ( (int)$alld == 0 ) {
		$dogs = '<tr><td><div class="pad5 mb5">Нет актуальных в течение &plusmn;10 дн.</div></td></tr>';
	}

	//Здесь готовим список сделок к продлению
	if ( $otherSettings['dealPeriod'] ) {

		$result = $db -> getAll( "SELECT * FROM {$sqlname}dogovor WHERE datum_end BETWEEN '$da1' and '$da2' and iduser IN (".implode( ",", $sort ).") and identity = '$identity' ORDER BY datum_end LIMIT 20" );
		$alldr  = $db -> getOne( "SELECT COUNT(did) FROM {$sqlname}dogovor WHERE datum_end BETWEEN '$da1' and '$da2' and iduser IN (".implode( ",", $sort ).") and identity = '$identity' ORDER BY datum_end" );

		foreach ( $result as $data ) {

			if ( !in_array( $data['did'], $dogarray ) ) {

				$dogstatus = $db -> getOne( "SELECT title FROM {$sqlname}dogcategory WHERE idcategory = '$data[idcategory]'" );

				$dday = datestoday( $data['datum_end'] );

				if ( $data['clid'] > 0 )
					$cl = current_client( $data['clid'] );

				if ( $dday == 0 ) {
					$color   = "green";
					$znak    = "";
					$bgcolor = "background-color:#E74B3B";
				}
				elseif ( $dday < 0 ) {
					$color   = "red";
					$znak    = "-";
					$bgcolor = "background-color:#FFF";
				}
				else {
					$color   = "blue";
					$znak    = "+";
					$bgcolor = "background-color:#FFF";
				}

				$renew_dogs .= '
				<tr class="ha">
					<td class="text-right w60">
						<div class="pad3"><b class="'.$color.'">'.$znak.' '.abs( $dday ).' дн.</b></div>
					</td>
					<td>
						<div class="ellipsis"><a href="javascript:void(0)" onClick="openDogovor(\''.$data['did'].'\')"><b>'.$data['title'].'</b></a></div><br>
						<div class="ellipsis mb5">'.$cl.'</div>
					</td>
				</tr>
				';

			}

		}

		if ( (int)$alldr == 0 ) {
			$renew_dogs = '<tr height="30"><td><div class="pad5">Не предвидится в течение &plusmn;10 дн.</div></td></tr>';
		}

	}

	//Здесь готовим список сделок с контролем по Комплектности
	if ( $complect_on == 'yes' && $tarif != 'Base' ) {

		$query  = "SELECT * FROM {$sqlname}complect WHERE doit != 'yes' and data_plan BETWEEN '$da1' and '$da2' and iduser IN (".implode( ",", $sort ).") and identity = '$identity' ORDER BY data_plan";
		$result = $db -> getAll( $query );
		foreach ( $result as $data ) {

			$title  = current_dogovor( $data['did'] );
			$ctitle = $db -> getOne( "SELECT title FROM {$sqlname}complect_cat WHERE ccid = '$data[ccid]' and identity = '$identity'" );

			$dday = datestoday( $data['data_plan'] );

			$res    = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$data[did]' and identity = '$identity'" );
			$clid   = $res["clid"];
			$dtitle = $res["title"];

			if ( $clid > 0 )
				$cl = current_client( $clid );

			if ( ($dday + $yday) < 0 )
				$dday = $yday + $dday;

			if ( $dday == 0 )
				$color = "green";
			elseif ( $dday < 0 )
				$color = "red";
			else $color = "blue";

			if ( $dday < 0 )
				$znak = "-";
			else $znak = "+";

			$complect .= '
			<tr class="ha hand" onClick="openDogovor(\''.$data['did'].'\')">
				<td width="60" valign="top" align="right"><div class="pad3"><b class="'.$color.'">'.$znak.' '.abs( $dday ).' дн.</b></div></td>
				<td>
					<div class="ellipsis '.$color.'"><b>'.$dtitle.'</b></div><br>
					<div class="ellipsis"><b>'.$ctitle.'</b></div><br>
					<div class="ellipsis">'.$cl.'</div>
				</td>
			</tr>
		';

			$allco++;
		}

		if ( $allco == 0 ) {
			$complect = '<tr height="30"><td><div class="pad5 mb5">Не предвидится в течение &plusmn;5 дн.</div></td></tr>';
		}

	}

	$html = '
	<div class="popheader blue">Актуальные сделки (<b>'.$alld.'</b>)<div class="link"><a href="/deals" title="Перейти к Сделкам" target="blank"><i class="icon-briefcase-1 pull-aright blue"></i></a></div></div>
	<div class="popcontent">
		<div class="replay" style="overflow:auto; max-height:200px; padding-left:0px; border-bottom:0px">
			<table id="bborder">'.$dogs.'</table>
		</div>
	</div>';

	if ( $otherSettings['dealPeriod'] ) {

		$html .= '
		<div class="popheader blue">Сделки к продлению (<b>'.$alldr.'</b>)<div class="link"><a href="/deals#close" title="Перейти к Сделкам" target="blank"><i class="icon-arrows-cw pull-aright blue"></i></a></div></div>
		<div class="popcontent">
			<div class="replay" style="overflow:auto; max-height:200px; padding-left:0; border-bottom:0">
			<table id="bborder">'.$renew_dogs.'</table>
			</div>
		</div>';

	}

	if ( $complect_on == 'yes' && $tarif != 'Base' ) {

		$html .= '
		<div class="popheader blue">Контроль сделок (<b>'.$allco.'</b>)<div class="link"><a href="/cpoint" title="Перейти к Контрольным точкам" target="blank"><i class="icon-check pull-aright blue"></i></a></div></div>
		<div class="popcontent" style="border:0px">
			<div class="replay" style="overflow:auto; max-height:200px; padding-left:0px; border-bottom:0px">
			<table id="bborder">'.$complect.'</table>
			</div>
		</div>';

	}

	print $html;

}

if ( $_REQUEST['action'] == "get_kol" ) {

	$allco = $alldr = 0;

	$alld = $db -> getOne( "
	SELECT 
		COUNT(*)
	FROM {$sqlname}dogovor `deal`
		LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = deal.clid
		LEFT JOIN {$sqlname}dogcategory `dc` ON dc.idcategory = deal.idcategory
	WHERE 
		deal.datum_plan BETWEEN '$da1' and '$da2' AND 
		COALESCE(deal.close, 'no') != 'yes' AND 
		deal.iduser IN (".implode( ",", $sort ).") AND 
		$s
		deal.identity = '$identity' 
	ORDER BY deal.datum_plan
	" );

	if ( $otherSettings['dealPeriod'] ) {

		$alldr = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}dogovor WHERE datum_end BETWEEN '$da1' and '$da2' and iduser IN (".implode( ",", $sort ).") and identity = '$identity' ORDER BY datum_end" );

	}

	if ( $complect_on == 'yes' && $tarif != 'Base' ) {

		$allco = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}complect WHERE doit != 'yes' and data_plan BETWEEN '$da1' and '$da2' and iduser IN (".implode( ",", $sort ).") and identity = '$identity' ORDER BY data_plan" );

	}

	$all = $alld + $alldr + $allco;

	print $all;

}

if ( $_REQUEST['action'] == "get_credit" ) {

	//Здесь готовим список платежей по графику

	$credit = '';

	$allc = $db -> getOne( "
		SELECT 
			COUNT(cr.crid)
		FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
		WHERE 
			COALESCE(deal.close, 'no') != 'yes' AND
			cr.do != 'on' AND 
			DATE(cr.datum_credit) >= DATE(NOW() - INTERVAL 10 DAY) AND 
			DATE(cr.datum_credit) <= DATE(NOW() + INTERVAL 10 DAY) AND 
			(
				cr.iduser IN (".implode( ",", $sort ).") OR 
				cr.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") and identity = '$identity')
			) AND 
			cr.identity = '$identity' 
		ORDER BY cr.datum_credit
	" );

	$query = "
		SELECT 
			cr.datum_credit,
			cr.clid,
			cr.did,
			cr.invoice,
			cr.summa_credit,
			deal.title as dtitle
		FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
		WHERE 
			COALESCE(deal.close, 'no') != 'yes' AND
			cr.do != 'on' AND 
			DATE(cr.datum_credit) >= DATE(NOW() - INTERVAL 10 DAY) AND 
			DATE(cr.datum_credit) <= DATE(NOW() + INTERVAL 10 DAY) AND 
			(
				cr.iduser IN (".implode( ",", $sort ).") OR 
				cr.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") and identity = '$identity')
			) AND 
			cr.identity = '$identity' 
		ORDER BY cr.datum_credit
		LIMIT 20
	";

	//print $query;

	$result = $db -> getAll( $query );
	foreach ( $result as $data ) {

		$dday = diffDate2( $data['datum_credit'] );

		if ( $data['clid'] > 0 )
			$cl = current_client( $data['clid'] );

		if ( $dday == 0 )
			$color = "green";
		elseif ( $dday < 0 )
			$color = "red";
		else $color = "blue";

		$znak = ($dday < 0) ? "-" : "+";

		$credit .= '
		<tr class="ha hand" onClick="openDogovor(\''.$data['did'].'\', \'7\')">
			<td class="text-right w60">
				<div class="fs-11 Bold '.$color.'">'.$znak.' '.abs( $dday ).' дн.</div>
				<div class="pt5 fs-07">Сч. №'.$data['invoice'].'</div>
			</td>
			<td>
				<div class="ellipsis Bold '.$color.'">'.num_format( $data['summa_credit'] ).' '.$valuta.'</div><br>
				<div class="ellipsis Bold">'.$data['dtitle'].'</div><br>
				<div class="ellipsis fs-09">'.$cl.'</div>
			</td>
		</tr>
		';

	}

	if ( (int)$allc == 0 ) {
		$credit = '<tr height="40"><td><div class="pad5 mb5">Не предвидится в течение &plusmn;5 дн.</div></td></tr>';
	}

	//Здесь готовим список просроченных платежей по графику
	$bad_credit = '';

	$allbc = $db -> getOne( "
	SELECT 
		COUNT(*)
	FROM {$sqlname}credit `cr`
		LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
	WHERE 
		COALESCE(deal.close, 'no') != 'yes' AND
		cr.do != 'on' AND 
		DATE(cr.datum_credit) <= DATE(NOW() ) AND 
		(
			cr.iduser IN (".implode( ",", $sort ).") OR 
			cr.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") AND identity = '$identity')
		) and 
		cr.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' and identity = '$identity') AND
		cr.identity = '$identity' 
	ORDER BY cr.datum_credit DESC
	" );

	$result = $db -> getAll( "
	SELECT 
		cr.datum_credit,
		cr.clid,
		cr.did,
		cr.invoice,
		cr.summa_credit,
		deal.title as dtitle
	FROM {$sqlname}credit `cr`
		LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
	WHERE 
		COALESCE(deal.close, 'no') != 'yes' AND
		cr.do != 'on' AND 
		DATE(cr.datum_credit) <= DATE(NOW() ) AND 
		(
			cr.iduser IN (".implode( ",", $sort ).") OR 
			cr.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") AND identity = '$identity')
		) and 
		cr.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' and identity = '$identity') AND
		cr.identity = '$identity' 
	ORDER BY cr.datum_credit DESC
	LIMIT 20
	" );

	foreach ( $result as $data ) {

		$bday = datestoday( $data['datum_credit'] );

		if ( $data['clid'] > 0 )
			$cl = current_client( $data['clid'] );
		if ( $data['pid'] > 0 )
			$cl = current_person( $data['pid'] );

		if ( $bday == 0 )
			$color = "green";
		elseif ( $bday < 0 )
			$color = "red";
		else $color = "blue";

		$znak = "-";

		$bad_credit .= '
		<tr class="ha hand" onClick="openDogovor(\''.$data['did'].'\',\'7\')">
			<td class="text-right w60">
				<div class="fs-11 Bold '.$color.'">'.$znak.' '.abs( $bday ).' дн.</div>
				<div class="pt5 fs-07">Сч. №'.$data['invoice'].'</div>
			</td>
			<td>
				<div class="ellipsis red Bold">'.num_format( $data['summa_credit'] ).' '.$valuta.'</div><br>
				<div class="ellipsis Bold">'.$data['dtitle'].'</div><br>
				<div class="ellipsis fs-09">'.$cl.'</div>
			</td>
		</tr>';

		$dd   = '';
		$bday = 0;

	}

	if ( (int)$allbc == 0 ) {
		$bad_credit = '<tr height="40"><td><div class="pad5 mb5">Просроченных счетов нет</div></td></tr>';
	}

	print '
	<div class="popheader blue">Просроченные платежи (<b>'.$allbc.'</b>)<div class="link"><a href="/contract#payment" title="Перейти к Счетам" target="blank"><i class="icon-dollar pull-aright blue"></i></a></div></div>
	<div class="popcontent no-border">
		<div class="replay" style="overflow:auto; max-height:200px; padding-left:0; border-bottom:0">
		<table id="bborder">'.$bad_credit.'</table>
		</div>
	</div>
	<div class="popheader blue">Ожидаемые оплаты (<b>'.$allc.'</b>)<div class="link"><a href="/contract#payment" title="Перейти к Счетам" target="blank"><i class="icon-dollar pull-aright blue"></i></a></div></div>
	<div class="popcontent no-border">
		<div class="replay" style="overflow:auto; max-height:200px; padding-left:0; border-bottom:0">
		<table id="bborder">'.$credit.'</table>
		</div>
	</div>';

}

if ( $_REQUEST['action'] == "get_creditkol" ) {

	$allc = $db -> getOne( "
		SELECT 
			COUNT(*)
		FROM {$sqlname}credit `cr`
			LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
		WHERE 
			COALESCE(deal.close, 'no') != 'yes' AND
			cr.do != 'on' AND 
			DATE(cr.datum_credit) >= DATE(NOW() - INTERVAL 10 DAY) AND 
			DATE(cr.datum_credit) <= DATE(NOW() + INTERVAL 10 DAY) AND 
			(
				cr.iduser IN (".implode( ",", $sort ).") OR 
				cr.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") and identity = '$identity')
			) AND 
			cr.identity = '$identity' 
		ORDER BY cr.datum_credit
	" );

	$allbc = $db -> getOne( "
	SELECT 
		COUNT(*)
	FROM {$sqlname}credit `cr`
		LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cr.did
	WHERE 
		COALESCE(deal.close, 'no') != 'yes' AND
		cr.do != 'on' AND 
		DATE(cr.datum_credit) <= DATE(NOW() ) AND 
		(
			cr.iduser IN (".implode( ",", $sort ).") OR 
			cr.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE iduser IN (".implode( ",", $sort ).") AND identity = '$identity')
		) and 
		cr.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' and identity = '$identity') AND
		cr.identity = '$identity' 
	ORDER BY cr.datum_credit DESC
	" );

	$all = $allc + $allbc;
	print $all;

}