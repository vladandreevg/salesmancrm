<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

error_reporting(0);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
$res = $db -> getAll("SELECT * FROM {$sqlname}mycomps `mc` WHERE $x mc.identity = '$identity' ORDER by mc.name_ur");
foreach ($res as $data){

	$string = '';

	$z = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";
	$re = $db -> getAll("SELECT * FROM {$sqlname}mycomps_recv WHERE $z cid = '$data[id]' and COALESCE(bloc, 'no') != 'yes' and identity = '$identity' ORDER by id");
	foreach ($re as $da){

		$tip = ($da['tip'] == 'bank') ? '<i class="icon-town-hall green" title="Банк"></i>' : '<i class="icon-rouble blue" title="Наличные"></i>';

		$bankr = explode(";", $da['bankr']);

		$bloc = ($da['bloc'] == 'yes') ? '<i class="icon-lock red"></i>' : '';

		$string .= '
		<div class="flex-container box--child mb10 mfh-12">
			<div class="flex-string wp50" title="'.$da['title'].'">
				<div class="ellipsis Bold">'.$tip.$da['title'].'</div>
			</div>
			<div class="flex-string wp50">
				<div class="ellipsis">'.num_format($da['ostatok']).' '.$valuta.'</div>
			</div>
		</div>
		';

	}

	if(empty($re)) {
		$string = '<b class="red smalltxt">Нет счетов</b><br>';
	}

	print '
	<div class="mb10">
	
		<div class="ellipsis cherta p5 blue mb10 Bold mfh-12" title="'.$data['name_shot'].'">
			'.$data['name_shot'].'
		</div>
		'.$string.'
		
	</div>
	';

}
