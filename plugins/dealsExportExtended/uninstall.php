<?php
// If uninstall not called from SM, then exit.
if ( !defined( 'SM_UNINSTALL_PLUGIN' ) )
	die;

$hooks -> add_action( 'plugin_uninstall', 'dealsexportextended_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function dealsexportextended_uninstall( $argv = [] ) {



}