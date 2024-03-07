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

	$hooks -> add_action( 'plugin_activate', 'activate_usernotifier' );
	$hooks -> add_action( 'plugin_deactivate', 'deactivate_usernotifier' );
	$hooks -> add_action( 'plugin_update', 'update_usernotifier' );

}

$hooks -> add_action( 'main__js', 'js_main_usernotifier' );
$hooks -> add_action( 'main__css', 'css_main_usernotifier' );

$hooks -> add_action( 'card__js', 'js_card_usernotifier' );
$hooks -> add_action( 'card__css', 'css_card_usernotifier' );

/**
 * Активация плагина
 *
 * @param array $argv
 */
function activate_usernotifier(array $argv = []) {

	$isCloud  = $GLOBALS['isCloud'];
	$identity = $GLOBALS['identity'];
	$database = $GLOBALS['database'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$rootpath = $GLOBALS['rootpath'];

	$ypath    = $rootpath."/plugins/userNotifier";

	if ($isCloud) {

		createDir($ypath."/data/".$identity);

	}

	$mes = [];

	//если таблицы нет, то создаем её
	$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}usernotifier_log'");
	if ($da == 0) {

		try {

			$db -> query("
				CREATE TABLE `{$sqlname}usernotifier_log` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`uid` INT(10) NULL DEFAULT NULL,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`clid` INT(10) NULL DEFAULT NULL,
				`pid` INT(10) NULL DEFAULT NULL,
				`did` INT(10) NULL DEFAULT NULL,
				`iduser` INT(10) NULL DEFAULT NULL,
				`content` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`get` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`identity` INT(10) NOT NULL DEFAULT '1',
				`event` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				PRIMARY KEY (`id`) USING BTREE,
				UNIQUE INDEX `id` (`id`) USING BTREE,
				INDEX `datum` (`datum`) USING BTREE
			)
			COMMENT='Лог отправленных уведомлений'
			COLLATE='utf8mb3_general_ci'
			ENGINE=InnoDB");

			$db -> query("
				CREATE TABLE `{$sqlname}usernotifier_tpl` (
					`id` INT(10) NOT NULL AUTO_INCREMENT,
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
					`content` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
					`event` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
					`identity` INT(10) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`) USING BTREE,
					UNIQUE INDEX `id` (`id`) USING BTREE,
					INDEX `datum` (`datum`) USING BTREE
				)
				COMMENT='Шаблоны уведомлений'
				COLLATE='utf8mb3_general_ci'
				ENGINE=InnoDB");

			$db -> query("
				CREATE TABLE `{$sqlname}usernotifier_bots` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`tip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`botid` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`token` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`content` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`identity` INT(10) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`) USING BTREE,
				UNIQUE INDEX `id` (`id`) USING BTREE,
				INDEX `datum` (`datum`) USING BTREE
			)
			COMMENT='Параметры для ботов'
			COLLATE='utf8mb3_general_ci'
			ENGINE=InnoDB");

			$db -> query("
				CREATE TABLE `{$sqlname}usernotifier_users` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`botid` INT(10) NULL DEFAULT NULL,
				`username` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`userid` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`chatid` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`iduser` INT(10) NULL DEFAULT NULL,
				`active` INT(10) NOT NULL DEFAULT '0' COMMENT '0 - блокирован, 1 - активен',
				`content` TEXT NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci',
				`identity` INT(10) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`) USING BTREE,
				UNIQUE INDEX `id` (`id`) USING BTREE,
				INDEX `datum` (`datum`) USING BTREE
			)
			COMMENT='Параметры для пользователей'
			COLLATE='utf8mb3_general_ci'
			ENGINE=InnoDB");

		}
		catch (Exception $e) {

			$mes[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		/**
		 * Загрузим начальные шаблоны
		 */
		$tplDefault = json_decode(file_get_contents($ypath.'/data/tpl.json'), true);
		foreach ($tplDefault as $tpl) {

			$tpl['identity'] = $identity;

			try {

				$db -> query("INSERT INTO {$sqlname}usernotifier_tpl SET ?u", $tpl);
				$mes[] = 'Шаблон для события '.$tpl['event'].' установлен';

			}
			catch (Exception $e) {

				$mes[] = 'Ошибка'.$e -> getMessage().' в шаблоне '.$tpl['event'];

			}

		}

	}

	/**
	 * Добавим признак блокировки пользователя
	 */
	$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}usernotifier_users LIKE 'active'");
	if ($field['Field'] == '') {

		$db -> query("ALTER TABLE {$sqlname}usernotifier_users ADD COLUMN `active` INT(1) NOT NULL DEFAULT '1' COMMENT '0 - блокирован, 1 - активен' AFTER `iduser`");

	}

	file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}

/**
 * Деактивация плагина
 *
 * @param array $argv
 */
function deactivate_usernotifier(array $argv = []) {

	$rootpath = $GLOBALS['rootpath'];

	file_put_contents($rootpath."/cash/actions.log", json_encode_cyr($argv));

}

function update_usernotifier(array $argv = []) {

	$database = $GLOBALS['database'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}userNotifier_log'");
	if ($da > 0) {
		$db -> query("RENAME TABLE `{$sqlname}userNotifier_log` TO `{$sqlname}usernotifier_log`");
	}

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}userNotifier_tpl'");
	if ($da > 0) {
		$db -> query("RENAME TABLE `{$sqlname}userNotifier_tpl` TO `{$sqlname}usernotifier_tpl`");
	}

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}userNotifier_bots'");
	if ($da > 0) {
		$db -> query("RENAME TABLE `{$sqlname}userNotifier_bots` TO `{$sqlname}usernotifier_bots`");
	}

	$da = (int)$db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}userNotifier_users'");
	if ($da > 0) {
		$db -> query("RENAME TABLE `{$sqlname}userNotifier_users` TO `{$sqlname}usernotifier_users`");
	}

	/**
	 * Добавим признак блокировки пользователя
	 */
	$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}usernotifier_users LIKE 'active'");
	if ($field['Field'] == '') {
		$db -> query("ALTER TABLE {$sqlname}usernotifier_users ADD COLUMN `active` INT(1) NOT NULL DEFAULT '1' COMMENT '0 - блокирован, 1 - активен' AFTER `iduser`");
	}

	$db -> query("ALTER TABLE `{$sqlname}usernotifier_users` CHANGE COLUMN `botid` `botid` INT(10) NULL AFTER `datum`");
	$db -> query("ALTER TABLE `{$sqlname}usernotifier_bots`
		CHANGE COLUMN `tip` `tip` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `datum`,
		CHANGE COLUMN `token` `token` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `botid`
	");
	$db -> query("ALTER TABLE `{$sqlname}usernotifier_tpl`
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `datum`,
		CHANGE COLUMN `event` `event` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `content`
	");

	try {

		$db -> query("ALTER TABLE `{$sqlname}usernotifier_log`
			CHANGE COLUMN `uid` `uid` INT(10) NULL AFTER `id`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL AFTER `did`,
			CHANGE COLUMN `event` `event` VARCHAR(255) NULL COLLATE 'utf8mb3_general_ci' AFTER `identity`;
		");

	}
	catch (Exception $e) {

	}

}

function js_main_usernotifier(){

	//print "<script type=\"text/javascript\" src=\"/plugins/userNotifier/js/usernotifier.js\"></script>\n";

}

function css_main_usernotifier(){

	//print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/plugins/userNotifier/css/usernotifier.css\">\n";

}

function js_card_usernotifier(){

	//print "<script type=\"text/javascript\" src=\"/plugins/userNotifier/js/usernotifier.card.js\"></script>\n";

}

function css_card_usernotifier(){

	//print "<link rel=\"stylesheet\" type=\"text/css\" href=\"/plugins/userNotifier/css/usernotifier.card.css\">\n";

}