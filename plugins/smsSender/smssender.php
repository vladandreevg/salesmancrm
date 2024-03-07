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

	$hooks -> add_action( 'plugin_activate', 'activate_smssender' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_smssender' );
	$hooks -> add_action( 'plugin_update', 'update_smssender' );

}

$hooks -> add_action( 'main__js', 'js_main_smssender' );
$hooks -> add_action( 'card__js', 'js_card_smssender' );

/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_smssender(array $argv = []) {

	$isCloud  = $GLOBALS['isCloud'];
	$identity = $GLOBALS['identity'];
	$database = $GLOBALS['database'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$rootpath = $GLOBALS['rootpath'];

	$ypath    = $rootpath."/plugins/smsSender";

	if ( $isCloud ) {

		//создаем папки хранения файлов
		createDir($ypath."/data/".$identity);

	}

	//если таблицы нет, то создаем её
	$da = $db -> getCol("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}logsms'");
	if ($da[0] == 0) {

		try {

			$db -> query("
				CREATE TABLE `{$sqlname}logsms` (
				    `id` INT(20) NOT NULL AUTO_INCREMENT,
				    `uid` INT(20) NULL,
				    `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				    `phone` VARCHAR(16) NULL,
				    `clid` INT(20) NULL,
				    `pid` INT(20) NULL,
				    `iduser` INT(20) NULL,
				    `content` TEXT NULL,
				    `identity` INT(30) DEFAULT '1' NOT NULL,
				    `status` VARCHAR(255) NULL,
				    PRIMARY KEY (`id`),
				    UNIQUE INDEX `id` (`id`), INDEX `datum` (`datum`),
				    INDEX `phone` (`phone`)
				) COMMENT='Лог отправленных СМС'
			");

			$db -> query("
				CREATE TABLE `{$sqlname}logsms_tpl` (
				    `id` INT(20) NOT NULL AUTO_INCREMENT,
				    `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				    `name` VARCHAR(255) NULL,
				    `content` TEXT NULL,
				    `identity` INT(30) DEFAULT '1' NOT NULL,
				    PRIMARY KEY (`id`),
				    UNIQUE INDEX `id` (`id`),
				    INDEX `datum` (`datum`)
				) COMMENT='Шаблоны СМС'
			");

		}
		catch (Exception $e) {

			$argv['error'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	//file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_smssender(array $argv = []) {

	//file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}

/**
 * Обновление плагина
 *
 * @param array $argv
 */
function update_smssender($argv = []) {
	
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	
	$db -> query("
	ALTER TABLE `{$sqlname}logsms`
		CHANGE COLUMN `uid` `uid` INT(10) NULL AFTER `id`,
		CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `uid`,
		CHANGE COLUMN `phone` `phone` VARCHAR(16) NULL COLLATE 'utf8mb3_general_ci' AFTER `datum`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL AFTER `phone`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL AFTER `clid`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL AFTER `pid`,
		CHANGE COLUMN `status` `status` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `identity`
	");
	
	$db -> query("
	ALTER TABLE `{$sqlname}logsms_tpl`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`,
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `datum`;
	");
	
}

function js_main_smssender(){

	print "<script src=\"/plugins/smsSender/js/smssender.js\"></script>\n";

}

function js_card_smssender(){

	print "<script src=\"/plugins/smsSender/js/smssender.js\"></script>\n";

}