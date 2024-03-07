<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.6          */
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

$status = [
	'0' => 'Открыт',
	'1' => 'В работе',
	'2' => 'Обработан',
	'3' => 'Закрыт'
];
$colors = [
	'0' => 'red',
	'1' => 'green',
	'2' => 'blue',
	'3' => 'gray'
];
$rezult = [
	'1' => 'Спам',
	'2' => 'Дубль',
	'3' => 'Другое'
];

$mleadset      = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'" );
$mleadsettings = json_decode( $mleadset['content'], true );

if ( $iduser1 != $mleadsettings['leadСoordinator'] )
	$sort1 = $sort2 = get_people( $iduser1 );

if ( $mleadsettings['leadMethod'] == 'free' )
	$sort1 = " and iduser IN (".implode( ",", get_userarray() ).") ";

$all       = 0;
$opencount = 0;
$workcount = 0;
$open      = '';
$work      = '';

$allopen = $db -> getOne( "SELECT COUNT(*) as allopen FROM ".$sqlname."leads WHERE status = '0' and iduser = 0 and identity = '$identity' ORDER BY datum" );

$allwork = $db -> getOne( "SELECT COUNT(*) as allwork FROM ".$sqlname."leads WHERE status = '1' ".$sort1." and identity = '$identity' ORDER BY datum" );

$q = ($mleadsettings['leadMethod'] != 'free') ? "SELECT * FROM ".$sqlname."leads WHERE status IN ('0','1') ".$sort1." and identity = '$identity' ORDER BY datum" : "SELECT * FROM ".$sqlname."leads WHERE (status = 0 or (status = 1 $sort2)) and identity = '$identity' ORDER BY datum";

$result = $db -> query( $q );
while ($data = $db -> fetch( $result )) {

	if ( $data['status'] == 0 ) {

		if ( $opencount < 30 ) {

			$color  = 'red';
			$status = 'Открыт';

			$open .= '
			<tr class="ha hand" height="30" onClick="editLead(\''.$data['id'].'\',\'view\')" title="Назначить">
				<td width="80" class="'.$color.'" valign="top">'.get_sdate( $data['datum'] ).'</td>
				<td>
					<div class="ellipsis fs-11 Bold">'.$data['title'].'</div><br>
					'.($data['phone'] != '' ? '<div class="ellipsis fs-09">'.$data['phone'].'</div>' : '<div class="ellipsis fs-09">'.$data['email'].'</div>').'
				</td>
			</tr>
			';
			$opencount++;

		}

	}
	else {

		if ( $workcount < 30 ) {

			$color  = 'green';
			$status = 'В работе';

			$work .= '
				<tr class="ha hand" onclick="editLead(\''.$data['id'].'\',\'view\')" title="Обработать">
					<td class="w80 '.$color.'">'.get_sdate( $data['datum'] ).'</td>
					<td>
						<div class="Bold ellipsis">'.$data['title'].'</div><br>
						<div class="blue ellipsis fs-09">'.current_user( $data['iduser'] ).'</div>
					</td>
				</tr>
				';

			$workcount++;

		}

	}

}

if ( $allopen == 0 )
	$open = '<tr><td>Открытых нет</td></tr>';
if ( $allwork == 0 )
	$work = '<tr><td>В работе нет</td></tr>';

if ( $_REQUEST['action'] == "get_leads" ) {

	if ( $iduser1 != $mleadsettings['leadСoordinator'] && $mleadsettings['leadMethod'] != 'free' ) {
		$hi0 = '200';
		$hi  = '400';
	}
	else {
		$hi = '200';
	}

	$html = '';
	if ( $iduser1 == $mleadsettings['leadСoordinator'] || $mleadsettings['leadMethod'] == 'free' ) {
		$html .= '
		<div class="popheader blue">Открытые интересы (<b>'.$opencount.'</b> из '.$allopen.')<div class="link"><a href="leads.php" title="Сборщик заявок" target="blank"><i class="icon-sort-alt-down pull-aright"></i></a></div></div>
		<div class="popcontent">
			<div class="replay" style="overflow:auto; max-height:160px; padding-left:0px">
			<table id="bborder">'.$open.'</table>
			</div>
		</div>
		';
	}

	$html .= '
	<div class="popheader blue">Интересы в работе (<b>'.$allwork.'</b>)<div class="link"><a href="leads.php" title="Сборщик заявок" target="blank"><i class="icon-sort-alt-down pull-aright"></i></a></div></div>
	<div class="popcontent">
		<div class="replay" style="overflow:auto; max-height:'.$hi.'px; padding-left:0; border-bottom:0">
		<table id="bborder">'.$work.'</table>
		</div>
	</div>
	';

	print $html;

}

if ( $_REQUEST['action'] == "get_leadskol" ) {
	$all = $allopen + $allwork;
	print $all;
}