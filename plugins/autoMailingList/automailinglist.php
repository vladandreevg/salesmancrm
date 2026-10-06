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

	$hooks -> add_action( 'plugin_activate', 'activate_automailinglist' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_automailinglist' );
	$hooks -> add_action( 'plugin_update', 'update_automailinglist' );

}

/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_automailinglist(array $argv = []) {

	$isCloud  = $GLOBALS['isCloud'];
	$identity = $GLOBALS['identity'];
	$database = $GLOBALS['database'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$rootpath = $GLOBALS['rootpath'];

	$ypath = $rootpath."/plugins/autoMailingList";

	if ( $isCloud ) {

		//создаем папки хранения файлов
		if ( !file_exists( $ypath."/data/".$identity ) ) {

			mkdir( $ypath."/data/".$identity, 0777 );
			chmod( $ypath."/data/".$identity, 0777 );

		}

	}

	//если таблицы нет, то создаем её
	$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."chain_log'" );
	if ( $da[0] == 0 ) {

		try {

			$db -> query( "
				CREATE TABLE `".$sqlname."chain_log` (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					`tip` VARCHAR(16) NULL DEFAULT NULL,
					`name` VARCHAR(255) NULL DEFAULT NULL,
					`email` VARCHAR(255) NULL DEFAULT NULL,
					`clid` INT(20) NULL DEFAULT NULL,
					`pid` INT(20) NULL DEFAULT NULL,
					`did` INT(20) NULL DEFAULT NULL,
					`iduser` INT(20) NULL DEFAULT NULL,
					`content` TEXT NULL DEFAULT NULL,
					`identity` INT(30) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					UNIQUE INDEX `id` (`id`),
					INDEX `datum` (`datum`)
				) 
				COMMENT='Лог отправленных уведомлений' 
				COLLATE='utf8_general_ci' 
				ENGINE=InnoDB
			" );

		}
		catch ( Exception $e ) {

			$argv['error'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	file_put_contents( $rootpath."/cash/actions.log", json_encode_cyr( $argv ) );

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_automailinglist(array $argv = []) {

	$rootpath = $GLOBALS['rootpath'];

	file_put_contents( $rootpath."/cash/actions.log", json_encode_cyr( $argv ) );

}

function update_automailinglist() {

	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$db -> query("
		ALTER TABLE `{$sqlname}chain_log`
		CHANGE COLUMN `tip` `tip` VARCHAR(16) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `datum`,
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `tip`,
		CHANGE COLUMN `email` `email` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `name`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL AFTER `email`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `did`,
		CHANGE COLUMN `listID` `listID` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `content`,
		CHANGE COLUMN `state` `state` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `listID`;
	");

}