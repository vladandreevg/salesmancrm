<?php
// If uninstall not called from SM, then exit.
if ( !defined( 'SM_UNINSTALL_PLUGIN' ) )
	die;

$hooks -> add_action( 'plugin_uninstall', 'usernotifier_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function usernotifier_uninstall( $argv = [] ) {

	$isCloud  = $GLOBALS[ 'isCloud' ];
	$identity = $GLOBALS[ 'identity' ];
	$sqlname  = $GLOBALS[ 'sqlname' ];
	$db       = $GLOBALS[ 'db' ];
	$rootpath = $GLOBALS[ 'rootpath' ];

	$ypath = $rootpath."/plugins/userNotifier";

	$db -> query( "DELETE FROM {$sqlname}plugins WHERE name = '$argv[name]' AND identity = '$identity'" );
	$db -> query( "DELETE FROM ".$sqlname."webhook WHERE title = '$argv[name]' and identity = '$identity'" );

	if ( $isCloud ) {

		$ypath .= "/data/$identity";

		$db -> query( "DELETE FROM {$sqlname}usernotifier_bots WHERE identity = '$identity'" );
		$db -> query( "DELETE FROM {$sqlname}usernotifier_log WHERE identity = '$identity'" );
		$db -> query( "DELETE FROM {$sqlname}usernotifier_tpl WHERE identity = '$identity'" );
		$db -> query( "DELETE FROM {$sqlname}usernotifier_users WHERE identity = '$identity'" );

		unlink( $ypath."/access.json" );
		unlink( $ypath."/settings.json" );

	}
	else {

		$db -> query( "DROP TABLE {$sqlname}usernotifier_bots" );
		$db -> query( "DROP TABLE {$sqlname}usernotifier_log" );
		$db -> query( "DROP TABLE {$sqlname}usernotifier_tpl" );
		$db -> query( "DROP TABLE {$sqlname}usernotifier_users" );

	}

	//file_put_contents("actions.log", json_encode_cyr($_REQUEST));

}