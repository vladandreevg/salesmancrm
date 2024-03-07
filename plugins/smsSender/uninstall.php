<?php
// If uninstall not called from SM, then exit.
if ( ! defined( 'SM_UNINSTALL_PLUGIN' ) )
	die;

$hooks -> add_action( 'plugin_uninstall', 'smssender_uninstall' );

/**
 * Удаление таблиц БД при деинсталляции
 * @param array $argv
 */
function smssender_uninstall($argv = []) {

	$isCloud  = $GLOBALS['isCloud'];
	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$rootpath = $GLOBALS['rootpath'];

	$ypath    = $rootpath."/plugins/smsSender";

	$db -> query("DELETE FROM {$sqlname}plugins WHERE name = '$argv[name]' AND identity = '$identity'");

	if ($isCloud == true) {

		$ypath = $ypath."/data/$identity";

		$db -> query("DELETE FROM {$sqlname}logsms WHERE identity = '$identity'");
		$db -> query("DELETE FROM {$sqlname}logsms_log WHERE identity = '$identity'");

		unlink($ypath."/settings.json");

	}
	else{

		$db -> query("DROP TABLE {$sqlname}logsms");
		$db -> query("DROP TABLE {$sqlname}logsms_log");

	}

}