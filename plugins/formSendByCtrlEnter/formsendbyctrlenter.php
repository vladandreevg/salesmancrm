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

	$hooks -> add_action( 'plugin_activate', 'activate_formsendbyctrlenter' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_formsendbyctrlenter' );

}

$hooks -> add_action( 'task_form_after', 'js_formsendbyctrlenter' );
$hooks -> add_action( 'task_form_doit_after', 'js_formsendbyctrlenter' );


/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_formsendbyctrlenter($argv = []) {

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_formsendbyctrlenter($argv = []) {

}

function js_formsendbyctrlenter(){

	?>
	<!--Плагин formSendByCtrlEnter-->
	<script>

		console.log('formSendByCtrlEnter ready');

		$(document).ready(function () {

			$(document).on('keydown');
			$(document).on('keydown', function () {

				var form = $('#dialog').find('form').attr('id');

				if (event.keyCode == 13 && event.ctrlKey)
					$('#' + form).submit();

			});

		});

	</script>
	<!--Плагин formSendByCtrlEnter-->
	<?php

}