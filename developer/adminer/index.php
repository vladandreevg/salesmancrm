<?php
$rootpath = realpath( __DIR__ );

function adminer_object() {

	global $rootpath;

	// Required to run any plugin.
	include_once $rootpath."/plugins/plugin.php";

	// Plugins auto-loader.
	foreach (glob("plugins/*.php") as $filename) {
		include_once $rootpath."/$filename";
	}

	// Specify enabled plugins here.
	$plugins = [
		new AdminerDatabaseHide([
			"mysql",
			"information_schema",
			"performance_schema"
		]),
		/*new AdminerLoginServers([
			'localhost',
			filter_input(INPUT_SERVER, 'HTTP_HOST') => filter_input(INPUT_SERVER, 'SERVER_NAME')
		]),*/
		new AdminerTablesFilter(),
		new AdminerSimpleMenu(),
		new AdminerCollations(),
		new AdminerJsonPreview(),

		// AdminerTheme has to be the last one.
		new AdminerTheme("default-blue"),
		new AdminerSqlLog(),
		new AdminerWymeditor(),
		new AdminerDumpZip(),
		new AdminerTableStructure(),
		//new AdminerEditTextarea(),
		new AdminerStructComments(),
		new AdminerEditCalendar(),
		new FasterTablesFilter(),
		new AdminerForeignSystem(),
		new AdminerEditForeign(),
		//new AdminerDesigns()
	];

	return new AdminerPlugin($plugins);
}

// Include original Adminer or Adminer Editor.
include $rootpath."/adminer.php";
