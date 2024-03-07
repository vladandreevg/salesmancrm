<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
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
	0 => 'Открыт',
	1 => 'В работе',
	2 => 'Обработан',
	3 => 'Закрыт'
];
$colors = [
	0 => 'red',
	1 => 'green',
	2 => 'blue',
	3 => 'gray'
];
$rezult = [
	1 => 'Спам',
	2 => 'Дубль',
	3 => 'Другое'
];

$mleadset      = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
$mleadsettings = json_decode( (string)$mleadset['content'], true );

//if($iduser1 != $mleadsettings['leadСoordinator']) $sort = get_people($iduser1);

$all       = 0;
$opencount = 0;
$open      = '';
$work      = '';

$allopen = (int)$db -> getOne( "SELECT COUNT(*) as allopen FROM {$sqlname}leads WHERE status = '0' and iduser = '$iduser1' and identity = '$identity' ORDER BY datum" );

$allwork = (int)$db -> getOne( "SELECT COUNT(*) as allwork FROM {$sqlname}leads WHERE status = '1' and iduser = '$iduser1' and identity = '$identity' ORDER BY datum" );

$result = $db -> query( "SELECT * FROM {$sqlname}leads WHERE status IN ('0','1') and iduser = '$iduser1' and identity = '$identity' ORDER BY datum" );
while ($data = $db -> fetch( $result )) {

	if ( (int)$data['status'] == 0 ) {

		if ( (int)$opencount < 30 ) {

			$color  = 'red';
			$status = 'Открыт';

			$open .= '
			<tr class="ha hand th30" onclick="editLead(\''.$data['id'].'\',\'view\')" title="Назначить"><td width="80" class="'.$color.'">'.get_sdate( $data['datum'] ).'</td><td><div class="ellipsis"><b>'.$data['title'].'</a></div></td></tr>
			';
			$opencount++;

		}

	}
	else {

		$color  = 'green';
		$status = 'В работе';

		$work .= '
		<tr class="ha hand th30" onclick="editLead(\''.$data['id'].'\',\'view\')" title="Обработать"><td width="80" class="'.$color.'">'.get_sdate( $data['datum'] ).'</td><td><div class="ellipsis"><b>'.$data['title'].'</b></div><br><div class="blue ellipsis">'.current_user( $data['iduser'] ).'</div></td></tr>
		';

	}

}

if ( $allopen == 0 ) {
	$open = '<tr height="30"><td>Открытых нет</td></tr>';
}
if ( $allwork == 0 ) {
	$work = '<tr height="30"><td>В работе нет</td></tr>';
}


if ( $iduser1 != $mleadsettings['leadСoordinator'] ) {
	$hi0 = '200';
	$hi  = '400';
}
else {
	$hi = '200';
}

$html = '';
if ( $iduser1 == $mleadsettings['leadСoordinator'] ) {
	$html .= '
	<div class="pad10 graybg-sub ">Открытые интересы (<b>'.$opencount.'</b> из '.$allopen.')</div>
	<div class="">
		<table id="bborder">'.$open.'</table>
	</div>
	';
}
$html .= '
	<div class="pad10 bluebg-sub blue">Интересы в работе (<b>'.$allwork.'</b>)</div>
	<div class="">
		<table id="bborder">'.$work.'</table>
	</div>
	';

print $html;