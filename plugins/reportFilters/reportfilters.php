<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */

// If this file is called directly, abort.
if ( defined( 'SMPLUGIN' ) ) {

	$hooks -> add_action( 'plugin_activate', 'activate_reportfilters' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_reportfilters' );

}

$hooks -> add_action( 'main__js', 'js_main_reportfilters' );
//$hooks -> add_action( 'main__css', 'css_main_reportfilters' );

//$hooks -> add_action( 'card__js', 'js_card_reportfilters' );
//$hooks -> add_action( 'card__css', 'css_card_reportfilters' );

/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_reportfilters($argv = []) {

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_reportfilters($argv = []) {

}

function js_main_reportfilters(){

	// признак того, что мы в отчетах
	if( $GLOBALS['script'] === 'report.php') {

		$db = $GLOBALS['db'];
		$sqlname = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$string = [];

		// обходим отделы
		$result = $db -> getAll("SELECT * FROM ".$sqlname."otdel_cat WHERE identity = '$identity' ORDER BY title");
		foreach ($result as $da) {

			// сотрудники, относящиеся к этому отделу
			$us = $db -> getCol("SELECT iduser FROM ".$sqlname."user WHERE otdel = '$da[idcategory]' AND identity = '$identity'");

			// если в отделе есть сотрудники, то добавляем его
			if(!empty($us)) {

				$us[] = 0;
				$string[] = '<option value="'.$da[ 'idcategory' ].'" data-users="'.yimplode( ",", $us ).'">'.$da[ 'title' ].'</option>';

			}

		}

		// конечный селект со списком отделов и перечнем id сотрудников
		$select = ( !empty($string) && count($string) > 1 ) ? '<select id="otdelSelect" name="otdelSelect" class="wp100"><option value="" data-users="">-- выбор --</option>'.yimplode("",$string).'</select>' : '';

		?>
		<script>

			// передаем селект в js
			var $otdelSelect = '<?=$select?>';

		</script>
		<script type="text/javascript" src="/plugins/reportFilters/js/reportfilters.js"></script>
		<?php

	}

}