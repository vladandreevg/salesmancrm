<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2024.x          */
/* ============================ */

error_reporting(E_ERROR);

$rootpath = dirname(__DIR__, 3);
$thisfile = basename(__FILE__);
$ypath    = __DIR__;

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];

//параметры подключения к серверу
include $ypath."/sipparams.php";
include $ypath."/mfunc.php";

$phone   = $_REQUEST['phone'];
$pbxurl  = $sip['host'];
$pbxuser = $sip['user'].":".$sip['secret'];

//параметры сотрудника
$res       = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
$title     = $res["title"];
$extention = $res["phone_in"];
$mob       = $res["mob"];

$result = callOriginate($extention, $phone);

//print_r($result);

if( !empty($result -> error) ){

	print '<div class="state p10 text-center"><b class="red">Ошибка:</b> '.$result -> error.'</div>';
	exit();

}

$res = json_decode($result -> response, true);

if ( $res['resp_status'] != 'ok' ) {
	$state = '<b class="red">Ошибка:</b> '.$res['error'];
}
else {
	$state = '<b class="green">Ожидайте звонка</b>';
}

print '
	<div class="zag paddbott10 white" id="peerhead">
		<b>Звонок абоненту</b>
	</div>
	<div class="state p10 text-center">'.$state.'</div>
	<script>
	setTimeout(function () {
		hideCallWindow(\'hand\')
	}, 10000);
	</script>
';
