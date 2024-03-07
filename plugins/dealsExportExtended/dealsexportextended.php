<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2022.x           */
/* ============================ */

// If this file is called directly, abort.
use Salesman\Deal;
use Salesman\Notify;

if (defined('SMPLUGIN')) {

	$hooks -> add_action('plugin_activate', 'activate_dealsexportextended');
	$hooks -> add_action('plugin_deactivate', 'deactivate_dealsexportextended');

}

$hooks -> add_action( 'main__js', 'js_dealsexportextended' );

/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_dealsexportextended($argv = []) {

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_dealsexportextended($argv = []) {

}

function js_dealsexportextended() {
	?>
	<script src="/plugins/dealsExportExtended/dealsexportextended.js"></script>
	<?php
}