<?php
// If uninstall not called from SM, then exit.
if ( !defined( 'SM_UNINSTALL_PLUGIN' ) ) {
	die;
}

$hooks -> add_action( 'plugin_uninstall', 'getstatistic_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function getstatistic_uninstall(array $argv = [] ) {

	$isCloud  = $GLOBALS[ 'isCloud' ];
	$identity = $GLOBALS[ 'identity' ];
	$sqlname  = $GLOBALS[ 'sqlname' ];
	$db       = $GLOBALS[ 'db' ];
	$rootpath = $GLOBALS[ 'rootpath' ];

	$ypath = $rootpath."/plugins/getStatistic";

	$db -> query( "DELETE FROM {$sqlname}plugins WHERE name = '$argv[name]' AND identity = '$identity'" );
	$db -> query( "DELETE FROM ".$sqlname."webhook WHERE title = '$argv[name]' and identity = '$identity'" );

	if ( $isCloud ) {

		$ypath .= "/data/$identity";

		$db -> query( "DELETE FROM {$sqlname}getstatistic_bots WHERE identity = '$identity'" );
		$db -> query( "DELETE FROM {$sqlname}getstatistic_users WHERE identity = '$identity'" );

		unlink( $ypath."/access.json" );
		unlink( $ypath."/settings.json" );

	}
	else {

		$db -> query( "DROP TABLE {$sqlname}getstatistic_bots" );
		$db -> query( "DROP TABLE {$sqlname}getstatistic_users" );

	}

}