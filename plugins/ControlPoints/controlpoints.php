<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// If this file is called directly, abort.
if ( defined( 'SMPLUGIN' ) ) {

	$hooks -> add_action( 'plugin_activate', 'activate_controlpoints' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_controlpoints' );

}

$hooks -> add_action( 'main__js', 'js_controlpoints' );
$hooks -> add_action( 'main__css', 'css_controlpoints' );


/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_controlpoints($argv = []) {

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_controlpoints($argv = []) {

}

function js_controlpoints(){

	if($GLOBALS['script'] != 'cpoint.php')
		print "<script type=\"text/javascript\" src=\"/plugins/ControlPoints/assets/js/controlpoints.js\"></script>\n";

}

function css_controlpoints() {

	if($GLOBALS['script'] != 'cpoint.php')
		print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/plugins/ControlPoints/assets/css/controlpoints.css\">\n";

}