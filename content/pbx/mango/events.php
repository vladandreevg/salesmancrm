<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2016.10       */
/* ============================ */

/**
 * скрипт получает уведомления из сервиса Манго
 */
error_reporting( E_ERROR );

header( 'Access-Control-Allow-Origin: *' );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$ypath = $rootpath."/content/pbx/mango/";

$response = json_decode( str_replace( "\\", "", $_POST['json'] ), true );

/**типы location
 *
 * "abonent" => действия пользователя,
 * "ivr"     => входящий вызов
 *
 * vpbx_api_key = 5f4dcc3b5fdgdfgd8327deb882cf99
 * sign = 1imldsdfsfsfur1468t5
 * json = {
 * "call_id" : "100:500:256",
 * "entry_id": "232wc3e3w3s222",
 * "timestamp" : "1399906976",
 * "seq" : "1",
 * "call_state" : "Appeared",
 * "location" : "ivr",
 * "from" : {
 * "number" : "79000000000" //внешний номер
 * },
 * "to" : {
 * "number" : "7800123456789", //наш номер, на который поступил вызов
 * "line_number" : "7800123456789"
 * }
 * }
 */

//отладочные данные
/*
$_GET['crmkey'] = 'gCG01Q5MA8msP1jXuQUC';
$response = [
	"call_id"    => "100:500:256",
	"entry_id"   => "232wc3e3w3s222",
	"timestamp"  => "1399906976",
	"seq"        => "1",
	"call_state" => "Connected",
	"command_id" => "100",
	"location"   => "ivr",
	"from"       => [
		"number" => "73422545577"
		//внешний номер
	],
	"to"         => [
		"number"      => "7800123456789",
		"line_number" => "7800123456789",
		"extension"   => "771"
		//наш номер, на который поступил вызов
	]
];
*/


/**
 * статусы
 *
 * Appeared - идет вызов
 * Connected - соединено
 * Disconnected - завершено + disconnect_reason
 * OnHold - на удержании
 */

//Найдем identity по настройкам
$res      = $db -> getRow( "SELECT id, timezone FROM {$sqlname}settings WHERE api_key = '$_GET[crmkey]'" );
$tmzone   = $res['timezone'];
$identity = (int)$res['id'];

if ( $identity == 0 ) {

	$f = fopen( $rootpath."/cash/mango-worker.log", "a" );
	fwrite( $f, current_datumtime()." :::\r".array2string( $_REQUEST )."\r" );
	fwrite( $f, "Ошибка: Не верный параметр crmkey\r" );
	fwrite( $f, "========================\r\r" );
	fclose( $f );

	exit();

}

if ( $tmzone == '' ) {
	$tmzone = 'Europe/Moscow';
}

date_default_timezone_set( $tmzone );

//установим временную зону
$tz         = new DateTimeZone( $tmzone );
$dz         = new DateTime();
$dzz        = $tz -> getOffset( $dz );
$bdtimezone = $dzz / 3600;

if ( abs( $bdtimezone ) > 12 ) {

	$tzone      = 0;
	$bdtimezone = $dzz / 3600;

}

$bdtimezone = ($bdtimezone > 0) ? "+".abs( $bdtimezone ) : "-".abs( $bdtimezone );

$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

$f = fopen( $rootpath."/cash/mango-worker.log", "a" );
fwrite( $f, current_datumtime()." :::\r".array2string( $_POST )."\r" );
fwrite( $f, "========================\r\r" );
fclose( $f );

//print_r($response);

/**
 * разберем ответ по косточкам
 *
 */

//если extension пуст, то это входящий звонок и нам нужен number
if ( $response['from']['extension'] == '' ) {

	$phone     = $response['from']['number'];
	$extension = $response['to']['extension'];
	$number_to = $response['to']['number'];

}
else {

	$phone     = $response['to']['number'];
	$extension = $response['from']['extension'];
	$number_to = $response['from']['number'];

}

$u = [];
if ( $phone != '' ) {
	$u = getxCallerID( $phone );
}

/**
 * Раздел для работы с исходящими звонками
 */
if ( $identity > 0 && $response['location'] == 'abonent' ) {

	//Идентификатор записи буфера для текущего пользователя
	$id = (int)$db -> getOne( "select id from ".$sqlname."mango_log where extension = '$extension' and type = 'abonent' and identity = '$identity'" );

	if ( $id == 0 ) {

		//если запись не найдена, то создаем её
		$db -> query( "INSERT INTO ".$sqlname."mango_log SET ?u", [
			'datum'      => current_datumtime(),
			'command_id' => $response['command_id'],
			'call_id'    => $response['call_id'],
			'extension'  => $extension,
			'phone'      => $phone,
			'call_state' => $response['call_state'],
			'content'    => json_encode_cyr( $response ),
			'type'       => 'abonent',
			'clid'       => (int)$u['clid'],
			'pid'        => (int)$u['pid'],
			'identity'   => $identity
		] );
		$id = $db -> insertId();

	}
	else {

		$db -> query( "UPDATE ".$sqlname."mango_log SET ?u WHERE id = '$id'", [
			'datum'      => current_datumtime(),
			'command_id' => $response['command_id'],
			'call_id'    => $response['call_id'],
			'extension'  => $extension,
			'phone'      => $phone,
			'call_state' => $response['call_state'],
			'content'    => json_encode( $response ),
			'clid'       => (int)$u['clid'],
			'pid'        => (int)$u['pid']
		] );

	}

}

/**
 * Этот раздел для работы с входящими звонками и умной переадресацией
 */
if ( $identity > 0 && $response['location'] == 'ivr' ) {

	if ( $u['phonein'] != '' ) {

		$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
		$set          = json_decode( (string)file_get_contents( $settingsFile ), true );

		if ( !$skey && $set['skey'] ) {
			$skey = $set['skey'];
		}
		if ( !$ivc && $set['ivc'] ) {
			$ivc = $set['ivc'];
		}

		include "sipparams.php";
		include "mfunc.php";

		$result = doMethod( "route", [
			"api_key"   => $api_key,
			"api_salt"  => $api_salt,
			"call_id"   => $response['call_id'],
			"extension" => $u['phonein']
		] );

		//Если сотрудник не доступен, то переадресуем на мобильный
		if ( $result['name'] == "Service Unavailable" ) {

			$result = doMethod( "route", [
				"api_key"   => $api_key,
				"api_salt"  => $api_salt,
				"call_id"   => $response['call_id'],
				"extension" => preparePhone( $u['mob'] )
			] );

		}

		/*$f = fopen($ypath . "worker.log", "a");
		fwrite($f, array2string(array("api_key" => $api_key, "api_salt" => $api_salt, "call_id" => $response['call_id'], "extension" => $u['extension'], "response" => $result)) . "\r\r");
		fwrite($f, "========================\r\r");
		fclose($f);*/

	}

	//Идентификатор записи буфера для текущего пользователя

	$id = (int)$db -> getOne( "select id from ".$sqlname."mango_log where type = 'ivr' and identity = '$identity'" );
	if ( $id == 0 ) {

		//если запись не найдена, то создаем её
		$db -> query( "INSERT INTO ".$sqlname."mango_log SET ?u", [
			'datum'      => current_datumtime(),
			'command_id' => $response['command_id'],
			'call_id'    => $response['call_id'],
			'extension'  => $extension,
			'phone'      => $phone,
			'call_state' => $response['call_state'],
			'content'    => json_encode_cyr( $response ),
			'type'       => 'ivr',
			'clid'       => (int)$u['clid'],
			'pid'        => (int)$u['pid'],
			'identity'   => $identity
		] );
		$id = $db -> insertId();

	}
	else {

		$db -> query( "UPDATE `".$sqlname."mango_log` SET ?u WHERE id = '$id'", [
			'datum'      => current_datumtime(),
			'command_id' => $response['command_id'],
			'call_id'    => $response['call_id'],
			'phone'      => $phone,
			'call_state' => $response['call_state'],
			'content'    => json_encode( $response ),
			'clid'       => (int)$u['clid'],
			'pid'        => (int)$u['pid']
		] );

	}

}