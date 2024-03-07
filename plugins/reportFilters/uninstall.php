<?php
// If uninstall not called from SM, then exit.
if ( !defined( 'SM_UNINSTALL_PLUGIN' ) )
	die;

$hooks -> add_action( 'plugin_uninstall', 'reportfilters_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function reportfilters_uninstall( $argv = [] ) {

}