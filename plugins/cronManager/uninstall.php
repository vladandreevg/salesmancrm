<?php
// If uninstall not called from SM, then exit.
if ( !defined( 'SM_UNINSTALL_PLUGIN' ) )
	die;

$hooks -> add_action( 'plugin_uninstall', 'cronmanager_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function cronmanager_uninstall( $argv = [] ) {

	$isCloud  = $GLOBALS[ 'isCloud' ];
	$identity = $GLOBALS[ 'identity' ];
	$sqlname  = $GLOBALS[ 'sqlname' ];
	$db       = $GLOBALS[ 'db' ];
	$rootpath = $GLOBALS[ 'rootpath' ];

	$ypath = $rootpath."/plugins/cronManager";

	$db -> query( "DELETE FROM {$sqlname}plugins WHERE name = '$argv[name]' AND identity = '$identity'" );

	if ( $isCloud == true ) {

		$db -> query( "DELETE FROM {$sqlname}cronmanager WHERE identity = '$identity'" );
		$db -> query( "DELETE FROM {$sqlname}cronmanager_log WHERE identity = '$identity'" );

	}
	else {

		$db -> query( "DROP TABLE {$sqlname}cronmanager" );
		$db -> query( "DROP TABLE {$sqlname}cronmanager_log" );

	}

	customSettings('cronManager','delete');

	//file_put_contents("actions.log", json_encode_cyr($_REQUEST));

}