<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);

$rootpath = realpath( __DIR__.'/../../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

//заглушка, т.к. истории звонков нет в API, а лимит запросов call_info составляет 100
//goto toexit;

//Добавлять запись в историю
$putInHistory = false;

if ($identity == '') {
	$identity = $db -> getOne( "SELECT id FROM {$sqlname}settings WHERE api_key = '$_GET[apkey]'" );
}

if ($identity == 0) {

	$return = ["error" => "Не верный ключ CRM API"];
	goto toexit;

}

$hours = pre_format($_REQUEST['hours']);//преобразуем в число

//параметры подключения к серверу
require_once "sipparams.php";
require_once "mfunc.php";

//$hours = 24;
$list = $return = [];
$count = 0;

//массив внутренних номеров сотрудников
$users = [];

$r = $db -> getAll("SELECT iduser, phone, phone_in, mob FROM {$sqlname}user WHERE identity = '$identity'");
foreach ($r as $da) {

	if ($da['phone'] != '') {
		$users[ prepareMobPhone( $da['phone'] ) ] = (int)$da['iduser'];
	}
	if ($da['phone_in'] != '') {
		$users[ prepareMobPhone( $da['phone_in'] ) ] = (int)$da['iduser'];
	}
	if ($da['mob'] != '') {
		$users[ prepareMobPhone( $da['mob'] ) ] = (int)$da['iduser'];
	}

}

$options = $db -> getOne("SELECT params FROM ".$sqlname."customsettings WHERE tip = 'sip' and identity = '$identity'");
$options = json_decode($options, true);
$domain  = $options['domain'];

$result = $db -> query("SELECT * FROM {$sqlname}callhistory WHERE DATE(datum) = CURDATE() AND file IS NULL AND identity = '$identity' ORDER BY datum DESC LIMIT 0, 10");
while ($call = $db -> fetch($result)) {

	//Делаем запрос на подготовку статистики и получаем key этого запроса
	$data = [
		"api_key"  => $api_key,
		"api_salt" => $api_salt,
		"domain"   => $options['domain'],
		"uid"      => $call['uid']
	];

	//print_r($data);

	$rezz = doMethod('call.info', $data);

	$info = $rezz['data']['info'];

	$rez[] = [
		"uid"  => $call['uid'],
		"info" => $info
	];

	//print_r($rez);

	if (!empty($info)) {

		$hcall = [
			"sec"  => $info['duration'],
			"file" => $info['is_record'] ? $call['uid'] : "0"
		];

		if($info['duration'] > 0 && $call['state'] == '') {
			$hcall['res'] = 'ANSWERED';
		}

		if($info['duration'] == 0 && $call['state'] == '') {
			$hcall['res'] = 'FAILED';
		}

		//print_r($hcall);

		$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$call[id]'", $hcall);

		$count++;

	}

}


if ($_REQUEST['printres'] == 'yes') {
	$rez = "Успешно. Обновлено $count записей";
}

$return = ["result" => $rez];


toexit:

//очищаем подключение к БД
unset($db);

print json_encode_cyr($return);

exit();