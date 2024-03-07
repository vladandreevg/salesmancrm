<?php
// If uninstall not called from SM, then exit.
if ( !defined( 'SM_UNINSTALL_PLUGIN' ) )
	die;

$hooks -> add_action( 'plugin_uninstall', 'controlpointsautoset_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function controlpointsautoset_uninstall( $argv = [] ) {

	$sqlname  = $GLOBALS[ 'sqlname' ];
	$db       = $GLOBALS[ 'db' ];
	$rootpath = $GLOBALS[ 'rootpath' ];

	$db -> query( "DROP TABLE {$sqlname}complect_auto" );

	file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}