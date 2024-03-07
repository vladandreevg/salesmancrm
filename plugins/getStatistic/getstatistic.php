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

	$hooks -> add_action( 'plugin_activate', 'activate_getstatistic' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_getstatistic' );
	$hooks -> add_action( 'plugin_update', 'update_getstatistic' );

}

/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_getstatistic(array $argv = []) {

	$isCloud  = $GLOBALS['isCloud'];
	$identity = $GLOBALS['identity'];
	$database = $GLOBALS['database'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$rootpath = $GLOBALS['rootpath'];

	$ypath    = $rootpath."/plugins/getStatistic";
	$mes = [];

	if ( $isCloud ) {

		//создаем папки хранения файлов
		createDir($ypath."/data/".$identity);

	}

	//если таблицы нет, то создаем её
	$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}sendstatistic_bots'");
	if ($da == 0) {

		try {

			$db -> query("
			CREATE TABLE `{$sqlname}sendstatistic_bots` (
				`id` INT(20) NOT NULL AUTO_INCREMENT, 
				`datum` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
				`tip` VARCHAR(255) NOT NULL, 
				`name` VARCHAR(255) NULL DEFAULT NULL, 
				`botid` VARCHAR(255) NULL DEFAULT NULL,
				`token` VARCHAR(255) NOT NULL,
				`content` TEXT, 
				`identity` INT(30) DEFAULT '1' NOT NULL, 
				PRIMARY KEY (`id`), 
				UNIQUE INDEX `id` (`id`), 
				INDEX `datum` (`datum`)
			) 
			COMMENT='Параметры для ботов' COLLATE='utf8_general_ci'
		");

			$db -> query("
			CREATE TABLE `{$sqlname}sendstatistic_users` (
				`id` INT(20) NOT NULL AUTO_INCREMENT, 
				`datum` TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 
				`botid` INT(11) NOT NULL, 
				`username` VARCHAR(255) NULL DEFAULT NULL, 
				`userid` VARCHAR(255) NULL DEFAULT NULL, 
				`chatid` VARCHAR(255) NULL DEFAULT NULL, 
				`iduser` INT(20) DEFAULT NULL, 
				`active` INT(1) NOT NULL DEFAULT '1' COMMENT '0 - блокирован, 1 - активен',
				`content` TEXT, 
				`identity` INT(30) DEFAULT '1' NOT NULL, 
				PRIMARY KEY (`id`), 
				UNIQUE INDEX `id` (`id`), 
				INDEX `datum` (`datum`)
			) 
			COMMENT='Параметры для пользователей' COLLATE='utf8_general_ci'");

		}
		catch (Exception $e) {

			$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	/**
	 * Добавим признак блокировки пользователя
	 */
	$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}sendstatistic_users LIKE 'active'");
	if ($field['Field'] == '') {

		$db -> query("ALTER TABLE {$sqlname}sendstatistic_users ADD COLUMN `active` INT(1) NOT NULL DEFAULT '1' COMMENT '0 - блокирован, 1 - активен' AFTER `iduser`");

	}

	//file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_getstatistic(array $argv = []) {

	$rootpath = $GLOBALS['rootpath'];

	file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}

function update_getstatistic(){

	$database = $GLOBALS['database'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}sendStatistic_bots'");
	if ($da > 0) {
		
		$db -> query("RENAME TABLE `{$sqlname}sendStatistic_bots` TO `{$sqlname}sendstatistic_bots`");
		
	}

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}sendStatistic_users'");
	if ($da > 0) {

		$db -> query("RENAME TABLE `{$sqlname}sendStatistic_users` TO `{$sqlname}sendstatistic_users`");

	}

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}sendStatistic_users'");
	if ($da > 0) {

		$db -> query("RENAME TABLE `{$sqlname}sendStatistic_users` TO `{$sqlname}sendstatistic_users`");

	}

	/**
	 * Добавим признак блокировки пользователя
	 */
	$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}sendstatistic_users LIKE 'active'");
	if ($field['Field'] == '') {

		$db -> query("ALTER TABLE {$sqlname}sendstatistic_users ADD COLUMN `active` INT(1) NOT NULL DEFAULT '1' COMMENT '0 - блокирован, 1 - активен' AFTER `iduser`");

	}

}