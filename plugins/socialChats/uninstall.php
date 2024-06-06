<?php
// If uninstall not called from SM, then exit.
if (!defined('SM_UNINSTALL_PLUGIN')) {
	die;
}

$hooks -> add_action('plugin_uninstall', 'socialchats_uninstall');

/**
 * Удаление таблиц БД при деинсталляции
 *
 * @param array $argv
 */
function socialchats_uninstall(array $argv = []) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$rootpath = $GLOBALS['rootpath'];

	$ypath = $rootpath."/plugins/socialChats";

	$db -> query("DELETE FROM {$sqlname}plugins WHERE name = '$argv[name]' AND identity = '$identity'");

	$ypath .= "/settings";

	$db -> query("DROP TABLE {$sqlname}chats_chat");
	$db -> query("DROP TABLE {$sqlname}chats_dialogs");
	$db -> query("DROP TABLE {$sqlname}chats_channels");

	unlink($ypath."/access.json");
	unlink($ypath."/settings.json");

	removeDir($rootpath."/files/chatcash");

	customSettings('socialChats', 'delete');

	//file_put_contents("actions.log", json_encode_cyr($_REQUEST));

}