<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2022 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2021.x           */
/* ============================ */

/**
 * Автоматическая расстановка контрольных точек
 */

use Salesman\ControlPoints;

// If this file is called directly, abort.
if ( defined( 'SMPLUGIN' ) ) {

	$hooks -> add_action( 'plugin_activate', 'activate_controlpointsautoset' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_controlpointsautoset' );

}

$hooks -> add_action('deal_add', 'cp_dealadd');


/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_controlpointsautoset($argv = []) {

	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$db -> query("
		CREATE TABLE IF NOT EXISTS `{$sqlname}complect_auto` (
			`id` INT(10) NOT NULL AUTO_INCREMENT,
			`cpid` INT(10) NULL DEFAULT NULL COMMENT 'complect_cat.ccid',
			`iduser` INT(10) NULL DEFAULT NULL COMMENT 'user.iduser',
			`days` INT(10) NULL DEFAULT NULL COMMENT 'количество дней',
			`identity` INT(10) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`) USING BTREE,
			INDEX `cpid` (`cpid`) USING BTREE
		)
		COMMENT='Плагин ControlPointsAutoset'
		COLLATE='utf8_general_ci'
		ENGINE=InnoDB
	");

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_controlpointsautoset($argv = []) {

}

/**
 * Основная функция работы плагина
 * @param $post
 * @param $dealdata
 * @return void
 * @throws Exception
 */
function cp_dealadd($post, $dealdata) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$rootpath = realpath( __DIR__.'/../../' );
	createDir($rootpath."/cash/hooks/");

	file_put_contents($rootpath."/cash/hooks/deal_".$dealdata['did'].".json", json_encode_cyr([
		"post" => $post,
		"data" => $dealdata
	]));

	/**
	 * На основе мультиворонки формируем массив дат по этапам
	 */
	$msFunnel  = getMultiStepList( [
		"direction" => $dealdata['direction'],
		"tip"       => $dealdata['tip']
	] );

	$icount = 0;
	$dates = [];

	foreach ( $msFunnel[ 'steps' ] as $step => $count ) {

		$icount += $count;
		$dates[ $step ] = modifyDatetime( current_datum(), ["format" => "Y-m-d", "hours" => $icount * 24] ); //addDateRange( current_datum(), $icount );

	}

	$point = new ControlPoints();

	// опорная дата
	$lastDate = current_datum();

	$resultct = $db -> query("SELECT * FROM ".$sqlname."complect_cat WHERE identity = '$identity' ORDER BY corder");
	while ($data = $db -> fetch($resultct)) {

		// уточненный отступ в днях
		$d = $db -> getRow("SELECT iduser, days FROM {$sqlname}complect_auto WHERE cpid = '$data[ccid]'");
		$days = (int)$d['days'];
		$iduser = (int)$d['iduser'];

		// если его нет, то задаем как 5 дней
		if( $days == 0 && empty($dates[ $data['dstep'] ]) ){
			$days = 5;
		}

		// здесь добавляем КТ
		$point -> edit(0, [
			"did"       => (int)$dealdata['did'],
			"data_plan" => $days == 0 ? $dates[ $data['dstep'] ] : modifyDatetime( $lastDate, ["format" => "Y-m-d", "hours" => $days * 24] ),
			"ccid"      => (int)$data['ccid'],
			"iduser"    => $iduser > 0 ? $iduser : (int)$dealdata['iduser']
		]);

		// расчет следующей опорной даты
		$lastDate = $days == 0 ? $dates[ $data['dstep'] ] : modifyDatetime( $lastDate, ["format" => "Y-m-d", "hours" => $days * 24] );

	}

}