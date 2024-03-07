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

	$hooks -> add_action( 'plugin_activate', 'activate_cronmanager' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_cronmanager' );
	$hooks -> add_action( 'plugin_update', 'update_cronmanager' );

	/**
	 * Активация плагина
	 *
	 * @param array $argv
	 */
	function activate_cronmanager($argv = []) {

		$database = $GLOBALS['database'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$rootpath = $GLOBALS['rootpath'];

		//$ypath = $rootpath."/plugins/cronManager";

		$mes = [];

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}cronmanager'" );
		if ( $da == 0 ) {

			try {

				$db -> query( "
					CREATE TABLE {$sqlname}cronmanager (
						`id` INT(20) NOT NULL AUTO_INCREMENT,
						`uid` VARCHAR(255) NULL DEFAULT NULL,
						`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`name` VARCHAR(200) NULL DEFAULT NULL COMMENT 'название задания',
						`parent` VARCHAR(100) NULL DEFAULT NULL COMMENT 'родитель задания',
						`bin` TEXT NULL DEFAULT NULL COMMENT 'путь до исполняемой программы',
						`script` TEXT NULL DEFAULT NULL COMMENT 'путь до скрипта',
						`period` TEXT NULL DEFAULT NULL COMMENT 'набор временных параметров',
						`task` TEXT NULL DEFAULT NULL COMMENT 'итоговая строка задания',
						`active` CHAR(5) NULL DEFAULT 'on' COMMENT 'активность',
						`identity` INT(20) NULL DEFAULT '1' COMMENT 'идентификатор',
						PRIMARY KEY (`id`)
					)
					COMMENT='Список заданий планировщика'
					COLLATE 'utf8_general_ci'
					ENGINE=InnoDB
				" );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else{

			$db -> query("ALTER TABLE `{$sqlname}cronmanager` CHANGE COLUMN `uid` `uid` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `id`;");

		}

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}cronmanager_log'" );
		if ( $da == 0 ) {

			try {

				$db -> query( "
					CREATE TABLE {$sqlname}cronmanager_log (
						`id` INT(20) NOT NULL AUTO_INCREMENT,
						`uid` INT(20) NOT NULL,
						`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
						`task` TEXT NULL DEFAULT NULL COMMENT 'строка задания',
						`response` TEXT NULL DEFAULT NULL COMMENT 'ответ',
						`identity` INT(20) NULL DEFAULT '1' COMMENT 'идентификатор',
						PRIMARY KEY (`id`)
					)
					COMMENT='Логи выполнения заданий'
					COLLATE 'utf8_general_ci'
					ENGINE=InnoDB
				" );

			}
			catch ( Exception $e ) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		$da = customSettings( 'cronManager' );
		if ( empty( $da ) ) {

			customSettings( 'cronManager', 'put', ["params" => ["php" => "/opt/php72/bin/php"]] );

		}

		$argv = array_merge( $mes, $argv );

		file_put_contents( $rootpath."/cash/actions.log", json_encode_cyr( $argv ) );

	}

	/**
	 * Деактивация плагина
	 *
	 * @param array $argv
	 */
	function deactivate_cronmanager($argv = []) {

		$rootpath = $GLOBALS['rootpath'];

		file_put_contents( $rootpath."/cash/actions.log", json_encode_cyr( $argv ) );

	}

	/**
	 * Обновление плагина
	 *
	 * @param array $argv
	 */
	function update_cronmanager($argv = []) {

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$db -> query("ALTER TABLE `{$sqlname}cronmanager` CHANGE COLUMN `uid` `uid` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `id`;");

	}

}