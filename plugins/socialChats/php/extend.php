<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Chats\Chats;

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

header( 'Access-Control-Allow-Origin: *' );

$rootpath = dirname( __DIR__, 3 );
$ypath    = $rootpath."/plugins/socialChats";

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

//print_r($_REQUEST);

$identity = $_REQUEST['identity'];
$chatkey  = $_REQUEST['apkey'];
$apkey    = md5( $identity.$_SERVER["HTTP_HOST"] );

require_once $rootpath."/inc/settings.php";

$icons = [];

//print $chatkey." ? ".$apkey;

if ( $chatkey == $apkey ) {

	$chat = new Chats();
	$list = $chat -> getChannels();

	//print_r($list);

	foreach ( $list as $item ) {

		if ( $item['active'] )
			$icons[] = [
				"icon" => strtolower( $item['otype'] ),
				"name" => $item['name'],
				"uri"  => $item['uri']
			];

	}

}

$wiget = customSettings( "socialChatsWiget", "get" );
if ( empty( $wiget ) ) {
	$wiget = [
		'bottom' => 50,
		'right'  => 50,
		'color'  => '#0D47A1',
		'shadow' => 'rgba(25,118,210, 0.4)'
	];
}

print json_encode_cyr( [
	"icons" => $icons,
	"wiget" => $wiget
] );