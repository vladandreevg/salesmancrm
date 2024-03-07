<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */
?>
<?php
error_reporting( 0 );
header( "Pragma: no-cache" );

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/auth.php";
include "../../inc/func.php";
include "../../inc/settings.php";

$prid = $_REQUEST['n_id'];

$tbl = '';

$status = [
	'0' => 'Новое',
	'1' => 'Обработано',
	'2' => 'Отменено'
];
$colors = [
	'0' => 'broun',
	'1' => 'green',
	'2' => 'gray'
];

$rest = $db -> getAll( "SELECT * FROM ".$sqlname."entry_poz WHERE prid = '$prid' and identity = '$identity' ORDER BY idp DESC" );
foreach ( $rest as $data ) {

	$entry = $db -> getRow( "SELECT * FROM ".$sqlname."entry WHERE ide = '".$data['ide']."' and identity = '$identity'" );
	$clid = $entry['clid'];
	$pid = $entry['pid'];
	$did = $entry['did'];
	$datum = $entry['datum'];
	$content = $entry['content'];
	$iduser = $entry['iduser'];
	$statuss = $entry['status'];

	$content = '';
	$apdx    = '';

	//if($content) $content = "<hr>".$content;

	if ( $clid )
		$content .= '<a href="javascript:void(0)" onclick="openClient(\''.$clid.'\')" title="Карточка" class="gray"><i class="icon-building broun"></i>'.current_client( $clid ).'</a>';

	if ( !$clid && $pid )
		$content .= '';
	else $content .= '; ';

	if ( $pid )
		$content .= '<a href="javascript:void(0)" onclick="openPerson(\''.$pid.'\')" title="Карточка" class="gray"><i class="icon-user-1 blue"></i>'.current_person( $pid ).'</a>';

	$apdx .= ($isadmin == 'yes' && $statuss == 0) ? '<a href="javascript:void(0)" onclick="editEntry(\''.$data['ide'].'\',\'edit\')" title="Редактировать" class="gray blue"><i class="icon-pencil blue"></i></a>&nbsp;' : '';

	$apdx .= ($iduser == $iduser1 && $statuss == 0) ? '<A href="javascript:void(0)" onClick="editDogovor(\''.$data['ide'].'\',\'fromentry\');" class="gray orange"><i class="icon-briefcase-1" title="Преобразовать в Сделку"></i></A>&nbsp;<a href="javascript:void(0)" onclick="editEntry(\''.$data['ide'].'\',\'status\')" title="Закрыть" class="gray blue"><i class="icon-block blue"></i></a>&nbsp;' : '';

	$apdx .= ($statuss == 1 && $did > 0) ? '<span title="Карточка сделки" class="hand" onclick="openDogovor(\''.$did.'\')"><i class="icon-briefcase-1 blue"></i></span>&nbsp;<span title="Обработано" class="green"><i class="icon-ok green"></i></span>&nbsp;' : '';
	$apdx .= ($statuss == 1 && $did == 0) ? '<A href="javascript:void(0)" onClick="editDogovor(\''.$data['ide'].'\',\'fromentry\');" class="gray orange"><i class="icon-briefcase-1" title="Преобразовать в Сделку"></i></A>&nbsp;<span title="Обработано" class="green"><i class="icon-ok green"></i></span>&nbsp;' : '';
	$apdx .= ($status == 2) ? '<A href="javascript:void(0)" onClick="editDogovor(\''.$data['ide'].'\',\'fromentry\');" class="gray orange"><i class="icon-briefcase-1" title="Преобразовать в Сделку"></i></A>&nbsp;<span title="Отменено" class="gray2"><i class="icon-cancel-circled gray2"></i></span>&nbsp;' : '';

	$tbl .= '
	<tr class="ha">
		<td class="w60"><span>'.get_hist( $datum ).'</span></td>
		<td>
			<div class="Bold fs-12 '.strtr( $statuss, $colors ).' mb10">'.$data['title'].'</div>
			<div class="em fs-10">'.current_user( $iduser ).'</div>
			<div class="fs-09 mt5">'.$content.'</div>
		</td>
		<td class="w40">'.$data['kol'].'</td>
		<td class="w120 '.strtr( $statuss, $colors ).'">
			'.strtr( $statuss, $status ).'
		</td>
		<td class="w80">
			<a href="javascript:void(0)" onclick="editEntry(\''.$data['ide'].'\',\'view\')" title="Просмотр" class="gray green"><i class="icon-eye green"></i></a>&nbsp;
			'.$apdx.'
		</td>
	</tr>
	';

}

if ( $tbl == '' )
	$tbl .= '
	<tr class="th40">
		<td class="gray">Обращения отсутствуют</td>
	</tr>
	';

print '
	<div class="viewdiv bgwhite">
	
		<table id="bborder" class="top">
		'.$tbl.'
		</table>
	
	</div>
';
?>