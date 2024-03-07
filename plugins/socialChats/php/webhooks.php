<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );

header( 'Access-Control-Allow-Origin: *' );

$rootpath = realpath( __DIR__.'/../../../' );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";

require_once "../php/autoload.php";
require_once "../vendor/autoload.php";

logIt($_REQUEST);

$channel    = $_REQUEST[ 'channel' ];
$apikey     = $_REQUEST[ 'api_key' ];
$channel_id = $_REQUEST[ 'botid' ];

if(empty($_REQUEST)){

	$url = yexplode("/", $_SERVER['REQUEST_URI']);

	$channel    = $_REQUEST['channel'] = $url[4];
	$apikey     = $_REQUEST['api_key'] = $url[5];
	$channel_id = $_REQUEST['botid'] = $url[6];

}

/**
 * Вызов функции подтверждения сервера, если нужно
 */
$type = "Chats\\{$channel}Provider";

$provider = new $type();
$provider -> callbackServerConfirmation();

/**
 * объединяе данные, пришедшие разными путями
 */
$params   = $_REQUEST;

$inparams = json_decode( file_get_contents( 'php://input' ), true );

if(!empty($inparams))
	$params   = array_merge( $params, $inparams );

$params[ 'channel_id' ] = $channel_id;

// записываем входящие параметры
logIt( $params, "income_parameters" );


//Запись массива в файл
function logIt( $array = [], $name = '' ) {

	$string = is_array( $array ) ? array2string( $array ) : $array;
	file_put_contents( $GLOBALS[ 'rootpath' ].'/cash/sch-webhooks.log', current_datumtime()."\n$name\n$string\n\n", FILE_APPEND );

}

//Найдем identity по настройкам
$res      = $db -> getRow( "select id, api_key, timezone from ".$sqlname."settings where api_key = '$apikey'" );
$tmzone   = $res[ 'timezone' ];
$api_key  = $res[ 'api_key' ];
$identity = $res[ 'id' ] + 0;

require_once $rootpath."/inc/settings.php";

date_default_timezone_set( $tmzone );

//проверяем валидность входящих запросов
if ( $identity == 0 || $api_key == '' ) {

	print "ok";

	logIt( ["Error" => "Unknown or not exist APY-key"], "INPUT" );

	exit();

}

//установим временную зону
$tz         = new DateTimeZone( $tmzone );
$dz         = new DateTime();
$dzz        = $tz -> getOffset( $dz );
$bdtimezone = $dzz / 3600;

$db -> query( "SET time_zone = '+".$bdtimezone.":00'" );

/**
 * Начало обработки
 */
print 'ok';

//print_r($params);

use Chats\Chats;
$chat = new Chats();

$chat -> newWebhookEvent( $params[ 'channel_id' ], $params );