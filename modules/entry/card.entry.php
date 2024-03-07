<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */
?>
<?php
error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$clid = (int)$_REQUEST['clid'];

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

$rest = $db -> getAll( "SELECT * FROM ".$sqlname."entry WHERE clid = '$clid' and identity = '$identity' ORDER BY datum DESC" );
foreach ( $rest as $data ) {

	$content = $apdx = '';

	if ( $data['clid'] )
		$content .= '<span><a href="javascript:void(0)" onclick="openClient(\''.$data['clid'].'\')" title="Карточка"><i class="icon-building broun"></i>'.current_client( $data['clid'] ).'</a></span>';

	if ( $data['pid'] )
		$content .= '<span><a href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Карточка"><i class="icon-user-1 blue"></i>'.current_person( $data['pid'] ).'</a></span>';

	$apdx .= ($isadmin == 'yes' && $data['status'] == 0) ? '<a href="javascript:void(0)" onclick="editEntry(\''.$data['ide'].'\',\'edit\')" title="Редактировать" class="gray blue"><i class="icon-pencil blue"></i></a>&nbsp;' : '';
	$apdx .= ($data['iduser'] == $iduser1 && $data['status'] == 0) ? '<A href="#" onClick="editDogovor(\''.$data['ide'].'\',\'fromentry\');" class="gray orange"><i class="icon-briefcase-1" title="Преобразовать в Сделку"></i></A>&nbsp;<a href="javascript:void(0)" onclick="editEntry(\''.$data['ide'].'\',\'status\')" title="Закрыть" class="gray blue"><i class="icon-block blue"></i></a>&nbsp;' : '';
	$apdx .= ($data['status'] == 1 && $data['did'] > 0) ? '<span title="Карточка сделки" class="hand" onclick="openDogovor(\''.$data['did'].'\')"><i class="icon-briefcase-1 blue"></i></span>&nbsp;<span title="Обработано" class="green"><i class="icon-ok green"></i></span>&nbsp;' : '';
	$apdx .= ($data['status'] == 1 && $data['did'] == 0) ? '<A href="#" onClick="editDogovor(\''.$data['ide'].'\',\'fromentry\');" class="gray orange"><i class="icon-briefcase-1" title="Преобразовать в Сделку"></i></A>&nbsp;<span title="Обработано" class="green"><i class="icon-ok green"></i></span>&nbsp;' : '';
	$apdx .= ($data['status'] == 2) ? '<A href="#" onClick="editDogovor(\''.$data['ide'].'\',\'fromentry\');" class="gray orange"><i class="icon-briefcase-1" title="Преобразовать в Сделку"></i></A>&nbsp;<span title="Отменено" class="gray2"><i class="icon-cancel-circled gray2"></i></span>&nbsp;' : '';

	$bg = (in_array( $data['status'], [
		1,
		2
	] )) ? "graybg-sub" : "bgwhite";
	$isnew = (in_array( $data['status'], [0] )) ? "new" : "";

	$tbl .= '
	<div class="'.$bg.' '.$isnew.'  ha mb10 box-shadow">
	
		<div class="flex-container float p10 border-bottom">
		
			<div class="flex-string w80">
				<div class="fs-10">'.get_sfdate( $data['datum'] ).'</div>
			</div>
			<div class="flex-string float">
				<div class="paddbott5 fs-12 Bold hand" onclick="editEntry(\''.$data['ide'].'\',\'view\');" title="Просмотр">Обращение №'.$data['ide'].'</div>
				<div class="gray2 fs-07">
					<i class="icon-user-1"></i>'.current_user( $data['iduser'] ).'
				</div>
			</div>
			<div class="flex-string yw120">
				<div class="'.strtr( $data['status'], $colors ).' Bold">'.strtr( $data['status'], $status ).'</div>
				<div class="blue fs-09">'.get_sfdate( $data['datum_do'] ).'</div>
			</div>
			<div class="flex-string yw100 text-center">
				<a href="javascript:void(0)" onclick="editEntry(\''.$data['ide'].'\',\'view\')" title="Просмотр" class="gray green"><i class="icon-eye green"></i></a>&nbsp;
				'.$apdx.'
			</div>
			
		</div>
		
		<div class="p10 hidden">
			'.$content.'
		</div>
		
	</div>
	';

}

if ( $tbl == '' ) {

	print '<div class="fcontainer">Обращения отсутствуют</div>';
	exit();

}

print '
	<div class="graybg-lite">
	'.$tbl.'
	</div>
';
?>