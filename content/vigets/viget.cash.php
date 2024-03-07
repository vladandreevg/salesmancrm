<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$result = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER by name_ur");
while ($data = $db -> fetch($result)){

	print '
	<div class="flex-container p10 graybg-sub red Bold uppercase">

		<div class="flex-string">
			'.$data['name_shot'].'
		</div>

	</div>';

	$res = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' and identity = '$identity' ORDER by id");
	$all = $db -> affectedRows($res);
	while ($da = $db -> fetch($res)){

		$bloc = '';

		if($da['tip'] == 'bank')
			$tip = '<i class="icon-town-hall green" title="Банк"></i>';

		elseif($da['tip'] == 'kassa')
			$tip = '<i class="icon-rouble blue" title="Наличные"></i>';

		$bankr = explode(";", $da['bankr']);

		if($da['bloc'] == 'yes')
			$bloc = '<i class="icon-lock"></i>';

		print '
		<div class="flex-container float border-bottom p10 ha">
			
			<div class="flex-string float">
				<span class="ellipsis fs-12 Bold">'.$tip.$da['title'].'</span>
			</div>
			<div class="flex-string w100 text-right">
				<div class="fs-11 Bold">'.num_format($da['ostatok']).'</div>
			</div>
			
		</div>
		';

	}


	if($all == 0)
		print '
		<div class="row mt0 mb10">
			<div class="column12 grid-12">
				<b class="red">Нет счетов</b>
			</div>
		</div>
		';

}