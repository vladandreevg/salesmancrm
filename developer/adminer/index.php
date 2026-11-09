<?php
/**
 * Adminer 6.x bootstrap.
 *
 * С Adminer 5 весь код перенесён в namespace Adminer, а класс AdminerPlugin
 * заменён контейнером Adminer\Plugins. Плагины официального набора — глобальные
 * классы, наследующие Adminer\Plugin (складываются в admin-plugins/).
 *
 * Старые файлы из developer/adminer/plugins/ написаны под Adminer 4.x
 * (AdminerPlugin/Adminer в глобальном namespace) и на 5+/6+ падают с
 * "Class Adminer not found", поэтому здесь они не подключаются.
 *
 * @link https://www.adminer.org/plugins/#use
 */
function adminer_object() {
	$dir = __DIR__;

	foreach (glob($dir . '/adminer-plugins/*.php') as $file) {
		include_once $file;
	}

	$plugins = array(
		new AdminerDatabaseHide(array('mysql', 'information_schema', 'performance_schema')),
		new AdminerTablesFilter(),
		new AdminerTableStructure(),
		new AdminerDumpZip(),
		new AdminerEditForeign(),
		new AdminerForeignSystem(),
	);

	return new \Adminer\Plugins($plugins);
}

include __DIR__ . '/adminer.php';
