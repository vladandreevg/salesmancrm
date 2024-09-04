<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Project;

set_time_limit( 0 );
error_reporting( E_ERROR );

$root = dirname( __DIR__ );

//error_reporting( E_ALL );
ini_set('display_errors', 1);

ini_set( 'log_errors', 'On' );
ini_set( 'error_log', $root.'/cash/salesman_error.log' );

require_once $root."/inc/licloader.php";
require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";

$db -> query("SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_ALL_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES'");

$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modules'" );
if ( $da[0] == 0 ) {

	$db -> query( "
		CREATE TABLE `{$sqlname}modules` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`title` VARCHAR(100) NOT NULL COMMENT 'название модуля',
			`content` TEXT(65535) NOT NULL COMMENT 'какие сделаны настройки модуля',
			`mpath` VARCHAR(255) NOT NULL ,
			`icon` VARCHAR(20) NOT NULL DEFAULT 'icon-publish' COMMENT 'иконка из фонтелло для меню',
			`active` VARCHAR(5) NOT NULL DEFAULT 'on' COMMENT 'включен-отключен',
			`activateDate` VARCHAR(20) NOT NULL,
			`secret` VARCHAR(255) NOT NULL,
			`identity` INT(20) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`) USING BTREE
		)
		COMMENT='Подключенные модули'
		ENGINE=MyISAM
	" );

}

require_once $root."/inc/func.php";

//определим версию системы
function getVersion() {

	$root = realpath( __DIR__.'/../' );

	require_once $root."/inc/config.php";
	require_once $root."/inc/dbconnector.php";

	$sqlname = $GLOBALS['sqlname'];
	$db      = $GLOBALS['db'];

	global $vdatum;

	$result = $db -> getRow( "SELECT * FROM {$sqlname}ver ORDER BY id DESC LIMIT 1" );
	$ver    = $result["current"];
	$vdatum = $result["datum"];

	return $ver;

}

$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}user` LIKE 'identity'" );
if ( $field['Field'] == '' ) {
	$db -> query( "ALTER TABLE `{$sqlname}user` ADD COLUMN `identity` INT(10) NOT NULL DEFAULT '1' AFTER `subscription`" );
}

//Закрыть при обновлении со старых версий системы
if ( !in_array( getVersion(), ['7.75', '2017.3'] ) ) {

	include $root."/inc/auth.php";
	include $root."/inc/settings.php";

}
else {

	$identity = 1;

}

/**
 * Очищаем кэш настроек
 */
$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
while ($data = $db -> fetch( $result )) {

	$fpath = $isCloud ? $data['id']."/" : "";

	unlink( $root."/cash/".$fpath."settings.all.json" );

	$res = $db -> query( "SELECT * FROM {$sqlname}user WHERE identity = '".$data['id']."'" );
	while ($da = $db -> fetch( $res )) {

		unlink( $root."/cash/".$fpath."settings.ymail.".$da['iduser'].".json" );

	}

}

$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}ver` LIKE 'id'" );
if ( $field['Field'] == '' ) {
	$db -> query( "ALTER TABLE {$sqlname}ver ADD `id` INT( 30 ) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST" );
	$db -> query( "ALTER TABLE {$sqlname}ver ADD `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
}

/**
 * Добавим таблицу хранения различных настроек
 */
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}customsettings'" );
if ( $da == 0 ) {

	$db -> query( "
			CREATE TABLE `{$sqlname}customsettings` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
			`tip` VARCHAR(50) NULL DEFAULT NULL COMMENT 'тип параметра',
			`params` TEXT NULL COMMENT 'параметры',
			`iduser` INT(20) NULL DEFAULT NULL,
			`identity` INT(20) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`)
		)
		COMMENT='Хранилище различных настроек'
		ENGINE=InnoDB
		" );

}

/*YMailer*/
$count = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}ymail_messages'" );
if ( $count == 0 ) {

	$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}ymail_files` (`id` INT(20) NOT NULL AUTO_INCREMENT, `mid` INT(20) DEFAULT NULL, `datum` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, `name` VARCHAR(255) DEFAULT NULL, `file` VARCHAR(255) DEFAULT NULL, `identity` INT(20) UNSIGNED DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );

	$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}ymail_messages` (`id` INT(30) NOT NULL AUTO_INCREMENT, `datum` DATETIME NOT NULL, `folder` VARCHAR(30) NOT NULL DEFAULT 'draft', `trash` VARCHAR(30) NOT NULL DEFAULT 'no', `priority` INT(3) NOT NULL DEFAULT '3', `state` VARCHAR(50) NOT NULL DEFAULT '', `subbolder` VARCHAR(255) NOT NULL, `messageid` VARCHAR(255) NOT NULL, `uid` INT(30) NOT NULL, `hid` INT(30) NOT NULL, `parentmid` VARCHAR(255) NOT NULL, `fromm` MEDIUMTEXT NOT NULL, `fromname` MEDIUMTEXT NOT NULL, `theme` VARCHAR(255) NOT NULL, `content` LONGTEXT NOT NULL,  `iduser` INT(20) NOT NULL, `fid` TEXT NOT NULL COMMENT 'список fid из таблицы _files', `did` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8" );

	$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}ymail_messagesrec` (`id` INT(20) NOT NULL AUTO_INCREMENT, `mid` INT(20) DEFAULT NULL COMMENT 'id записи из _messages', `tip` VARCHAR(100) DEFAULT 'to', `email` VARCHAR(100) DEFAULT '', `name` VARCHAR(200) DEFAULT '', `clid` INT(20) DEFAULT NULL, `pid` INT(20) DEFAULT NULL, `identity` INT(20) DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8" );

	$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}ymail_settings` (`id` INT(20) NOT NULL AUTO_INCREMENT, `iduser` INT(20) NOT NULL DEFAULT '0', `settings` TEXT NOT NULL, `lasttime` DATETIME NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );

}
/*YMailer*/

//версия, на которую будем обновлять БД
$lastVer = '2024.3';
$step    = (int)$_REQUEST['step'];
$currentVersion = getVersion();

$seria = [
	'2020.1',
	'2020.3',
	'2021.4',
	'2022.2',
	'2022.3',
	'2023.1',
	'2024.1',
	'2024.2',
	'2024.3'
];

//printf("Step: %s; Sapi: %s; Ver: %s; LastVer: %s; IsLast: %s\n", $step, PHP_SAPI, getVersion(), $lastVer, getVersion() == $lastVer);

if ( $step == 1 || PHP_SAPI == 'cli' ) {

	$time_start = current_datumtime();

	if(PHP_SAPI == 'cli') {

		print "Start at ".modifyDatetime( "", [
				//"hours"  => $bdtimezone,
				"format" => "d.m H:i"
			] )."\n";

	}

	/*2020*/

	if ( getVersion() == '2019.4' ) {

		//добавляем новый признак - шаблон счета
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}plugins LIKE 'version'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}plugins ADD COLUMN `version` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Установленная версия плагина' AFTER `name`" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2020.1')" );

		$currentVer = getVersion();

		$sapi = PHP_SAPI;

		if ( $currentVer == $lastVer ) {

			$message = ($sapi == 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
		<div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
		if ( $sapi == 'cli' ) {

			print $message;

		}

	}
	if ( getVersion() == '2020.1' ) {

		//добавляем новый признак - шаблон счета
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}plugins LIKE 'version'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}plugins ADD COLUMN `version` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Установленная версия плагина' AFTER `name`" );


		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}kpiseason'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
				CREATE TABLE `{$sqlname}kpiseason` (
					`id` INT(10) NOT NULL AUTO_INCREMENT,
					`kpi` INT(10) NOT NULL DEFAULT '0' COMMENT 'id показателя',
					`rate` TEXT(65535) NULL DEFAULT NULL COMMENT 'значения сезонного коэффициента в json',
					`year` INT(2) NULL DEFAULT NULL,
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`) USING BTREE
				)
				COMMENT='Сезонные коэффициенты для показателей KPI'
				COLLATE='utf8_general_ci'
				ENGINE=InnoDB
			" );

		}

		$db -> query( "
			ALTER TABLE `{$sqlname}complect_cat`
			CHANGE COLUMN `role` `role` TEXT NULL COMMENT 'список должностей, которым доступно изменение контр.точки в виде списка с разделением ,' AFTER `dstep`,
			CHANGE COLUMN `users` `users` TEXT NULL COMMENT 'список сотрудников, которым доступно изменение контр.точки usser.iduser в виде списка с разделением ,' AFTER `role`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}settings`
			CHANGE COLUMN `company_full` `company_full` TEXT(65535) NULL AFTER `company`,
			CHANGE COLUMN `company_site` `company_site` VARCHAR(250) NULL AFTER `company_full`,
			CHANGE COLUMN `company_mail` `company_mail` VARCHAR(250) NULL AFTER `company_site`,
			CHANGE COLUMN `company_phone` `company_phone` VARCHAR(255) NULL AFTER `company_mail`,
			CHANGE COLUMN `company_fax` `company_fax` VARCHAR(255) NULL  AFTER `company_phone`,
			CHANGE COLUMN `outClientUrl` `outClientUrl` VARCHAR(255) NULL  AFTER `company_fax`,
			CHANGE COLUMN `outDealUrl` `outDealUrl` VARCHAR(255) NULL  AFTER `outClientUrl`,
			CHANGE COLUMN `defaultDealName` `defaultDealName` VARCHAR(255) NULL  AFTER `outDealUrl`,
			CHANGE COLUMN `dir_prava` `dir_prava` VARCHAR(255) NULL  AFTER `defaultDealName`,
			CHANGE COLUMN `recv` `recv` TEXT(65535) NULL  AFTER `dir_prava`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}settings`
			CHANGE COLUMN `export_lock` `export_lock` VARCHAR(255) NULL  AFTER `session`,
			CHANGE COLUMN `valuta` `valuta` VARCHAR(10) NULL  AFTER `export_lock`,
			CHANGE COLUMN `ipaccesse` `ipaccesse` VARCHAR(5) NULL  AFTER `valuta`,
			CHANGE COLUMN `ipstart` `ipstart` VARCHAR(15) NULL  AFTER `ipaccesse`,
			CHANGE COLUMN `ipend` `ipend` VARCHAR(15) NULL  AFTER `ipstart`,
			CHANGE COLUMN `iplist` `iplist` TEXT(65535) NULL  AFTER `ipend`,
			CHANGE COLUMN `maxupload` `maxupload` VARCHAR(3) NULL  AFTER `iplist`,
			CHANGE COLUMN `ipmask` `ipmask` VARCHAR(20) NULL  AFTER `maxupload`,
			CHANGE COLUMN `ext_allow` `ext_allow` TEXT(65535) NOT NULL  AFTER `ipmask`,
			CHANGE COLUMN `mailme` `mailme` VARCHAR(5) NULL  AFTER `ext_allow`,
			CHANGE COLUMN `mailout` `mailout` VARCHAR(10) NULL  AFTER `mailme`,
			CHANGE COLUMN `other` `other` TEXT(65535) NULL  AFTER `mailout`,
			CHANGE COLUMN `logo` `logo` VARCHAR(100) NULL  AFTER `other`
		" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2020.3')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message;

		}

	}

	/*2021*/

	if ( getVersion() == '2020.3' ) {

		//добавляем новый признак - шаблон счета
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}plugins LIKE 'version'" );
		if ( $field['Field'] == '' ) {
			$db -> query( "ALTER TABLE {$sqlname}plugins ADD COLUMN `version` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Установленная версия плагина' AFTER `name`" );
		}


		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}kpiseason'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
				CREATE TABLE `{$sqlname}kpiseason` (
					`id` INT(10) NOT NULL AUTO_INCREMENT,
					`kpi` INT(10) NOT NULL DEFAULT '0' COMMENT 'id показателя',
					`rate` TEXT(65535) NULL DEFAULT NULL COMMENT 'значения сезонного коэффициента в json',
					`year` INT(2) NULL DEFAULT NULL,
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`) USING BTREE
				)
				COMMENT='Сезонные коэффициенты для показателей KPI'
				COLLATE='utf8_general_ci'
				ENGINE=InnoDB
			" );

		}

		$db -> query( "
			ALTER TABLE `{$sqlname}complect_cat`
			CHANGE COLUMN `role` `role` TEXT NULL COMMENT 'список должностей, которым доступно изменение контр.точки в виде списка с разделением ,' AFTER `dstep`,
			CHANGE COLUMN `users` `users` TEXT NULL COMMENT 'список сотрудников, которым доступно изменение контр.точки usser.iduser в виде списка с разделением ,' AFTER `role`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}settings`
			CHANGE COLUMN `company_full` `company_full` TEXT(65535) NULL  AFTER `company`,
			CHANGE COLUMN `company_site` `company_site` VARCHAR(250) NULL  AFTER `company_full`,
			CHANGE COLUMN `company_mail` `company_mail` VARCHAR(250) NULL  AFTER `company_site`,
			CHANGE COLUMN `company_phone` `company_phone` VARCHAR(255) NULL  AFTER `company_mail`,
			CHANGE COLUMN `company_fax` `company_fax` VARCHAR(255) NULL  AFTER `company_phone`,
			CHANGE COLUMN `outClientUrl` `outClientUrl` VARCHAR(255) NULL  AFTER `company_fax`,
			CHANGE COLUMN `outDealUrl` `outDealUrl` VARCHAR(255) NULL  AFTER `outClientUrl`,
			CHANGE COLUMN `defaultDealName` `defaultDealName` VARCHAR(255) NULL  AFTER `outDealUrl`,
			CHANGE COLUMN `dir_prava` `dir_prava` VARCHAR(255) NULL  AFTER `defaultDealName`,
			CHANGE COLUMN `recv` `recv` TEXT(65535) NULL  AFTER `dir_prava`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}settings`
			CHANGE COLUMN `export_lock` `export_lock` VARCHAR(255) NULL  AFTER `session`,
			CHANGE COLUMN `valuta` `valuta` VARCHAR(10) NULL  AFTER `export_lock`,
			CHANGE COLUMN `ipaccesse` `ipaccesse` VARCHAR(5) NULL  AFTER `valuta`,
			CHANGE COLUMN `ipstart` `ipstart` VARCHAR(15) NULL  AFTER `ipaccesse`,
			CHANGE COLUMN `ipend` `ipend` VARCHAR(15) NULL  AFTER `ipstart`,
			CHANGE COLUMN `iplist` `iplist` TEXT(65535) NULL  AFTER `ipend`,
			CHANGE COLUMN `maxupload` `maxupload` VARCHAR(3) NULL  AFTER `iplist`,
			CHANGE COLUMN `ipmask` `ipmask` VARCHAR(20) NULL  AFTER `maxupload`,
			CHANGE COLUMN `ext_allow` `ext_allow` TEXT(65535) NOT NULL  AFTER `ipmask`,
			CHANGE COLUMN `mailme` `mailme` VARCHAR(5) NULL  AFTER `ext_allow`,
			CHANGE COLUMN `mailout` `mailout` VARCHAR(10) NULL  AFTER `mailme`,
			CHANGE COLUMN `other` `other` TEXT(65535) NULL  AFTER `mailout`,
			CHANGE COLUMN `logo` `logo` VARCHAR(100) NULL  AFTER `other`
		" );

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}mycomps_signer'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
				CREATE TABLE `{$sqlname}mycomps_signer` (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`mcid` INT(20) NULL DEFAULT NULL COMMENT 'Привязка к компании',
					`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Имя подписанта' ,
					`status` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Должность' ,
					`signature` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Подпись' ,
					`osnovanie` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Действующий на основании' ,
					`stamp` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Файл факсимилье' ,
					`identity` INT(10) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`) USING BTREE,
					INDEX `mcid` (`mcid`) USING BTREE
				)
				COMMENT='Дополнительные подписанты для документов'
				COLLATE='utf8_general_ci'
				ENGINE=InnoDB
			" );

		}

		// ALTER TABLE `app_contract` ADD COLUMN `signer` INT(20) NULL COMMENT 'id подписанта' AFTER `mcid`;
		// ALTER TABLE `app_credit` ADD COLUMN `signer` INT(20) NULL DEFAULT NULL COMMENT 'id подписанта' AFTER `suffix`;

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}contract LIKE 'signer'" );
		if ( $field['Field'] == '' ) {
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD COLUMN `signer` INT(20) NULL COMMENT 'id подписанта' AFTER `mcid`" );
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}credit LIKE 'signer'" );
		if ( $field['Field'] == '' ) {
			$db -> query( "ALTER TABLE `{$sqlname}credit` ADD COLUMN `signer` INT(20) NULL COMMENT 'id подписанта' AFTER `suffix`" );
		}

		$count = $db -> getOne( "SELECT DISTINCT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '$database' and TABLE_NAME = '{$sqlname}tasks' and INDEX_NAME = 'autor'" );
		if ( $count == 0 ) {

			$db -> query( "ALTER TABLE `{$sqlname}tasks` ADD INDEX `autor` (`autor`)" );

		}

		/**
		 * Добавляем шаблон счета с QR-кодом
		 */

		// найдем тип документа - счет
		$result = $db -> query( "SELECT id FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			$typeidx = $db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'invoice' AND identity = '$data[id]'" );

			// добавляем шаблон счета
			$tempid = $db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE typeid = '$typeidx' AND file = 'invoice_qr.tpl' AND identity = '$data[id]'" );

			if( (int)$tempid == 0 ) {

				$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
					"typeid"   => $typeidx,
					"title"    => "Счет с QRcode",
					"file"     => "invoice_qr.tpl",
					"identity" => $data['id']
				] );

			}

			// добавляем шаблон квитанции
			/*$tempid = $db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE typeid = '$typeidx' AND file = 'pko_invoice_qr.tpl' AND identity = '$data[id]'" );

			if( (int)$tempid == 0 ) {

				$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
					"typeid"   => $typeidx,
					"title"    => "Квитанция с QRcode",
					"file"     => "pko_invoice_qr.tpl",
					"identity" => $data['id']
				] );

			}*/

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2021.4')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message;

		}

	}

	/*2022*/

	if ( getVersion() == '2021.4' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}activities LIKE 'icon'" );
		if ( $field['Field'] == '' ) {

			$db -> query("ALTER TABLE `{$sqlname}activities` ADD `icon` VARCHAR(100) NULL DEFAULT NULL COMMENT 'иконка' AFTER `color`");

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modcatalog_akt'" );
		if ( $da[0] > 0 ) {

			$db -> query("
				ALTER TABLE `{$sqlname}modcatalog_akt`
					CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'dogovor.did' AFTER `id`,
					CHANGE COLUMN `tip` `tip` VARCHAR(100) NOT NULL COMMENT 'приходный или расходный'  AFTER `did`,
					CHANGE COLUMN `number` `number` INT(10) NULL COMMENT 'номер ордера' AFTER `tip`,
					CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clientcat.clid' AFTER `datum`,
					CHANGE COLUMN `posid` `posid` INT(10) NULL COMMENT 'clientcat.clid (id поставщика)' AFTER `clid`,
					CHANGE COLUMN `man1` `man1` VARCHAR(255) NULL COMMENT ' для расходного сдал, для приходного принял'  AFTER `posid`,
					CHANGE COLUMN `man2` `man2` VARCHAR(255) NULL COMMENT ' для расходного принял, для приходного сдал'  AFTER `man1`,
					CHANGE COLUMN `isdo` `isdo` VARCHAR(5) NULL COMMENT 'проведен или нет'  AFTER `man2`,
					CHANGE COLUMN `cFactura` `cFactura` VARCHAR(20) NULL COMMENT '№ счета-фактуры поставщика'  AFTER `isdo`,
					CHANGE COLUMN `idz` `idz` INT(10) NULL DEFAULT NULL AFTER `sklad`
				");

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modworkplan_task'" );
		if ( $da[0] > 0 ) {

			$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modworkplan_task LIKE 'address'" );
			if ( $field['Field'] == '' ) {

				$db -> query("ALTER TABLE `{$sqlname}modworkplan_task` ADD COLUMN `address` VARCHAR(255) NULL DEFAULT NULL AFTER `workers`");

			}

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modcatalog'" );
		if ( $da[0] > 0 ) {

			$db -> query("
				ALTER TABLE `{$sqlname}modcatalog`
				CHANGE COLUMN `idz` `idz` INT(10) NULL COMMENT 'modcatalog_zayavka.id' AFTER `prid`,
				CHANGE COLUMN `content` `content` MEDIUMTEXT NULL COMMENT 'описание позиции'  AFTER `idz`,
				CHANGE COLUMN `price_plus` `price_plus` DOUBLE NULL AFTER `datum`,
				CHANGE COLUMN `status` `status` INT(10) NULL DEFAULT '0' COMMENT 'статус (в наличии и тд.)' AFTER `price_plus`,
				CHANGE COLUMN `kol` `kol` DOUBLE NULL DEFAULT '0' COMMENT 'количество' AFTER `status`,
				CHANGE COLUMN `files` `files` TEXT NULL COMMENT 'прикрепленные файлы в формате json'  AFTER `kol`,
				CHANGE COLUMN `identity` `identity` INT(10) NOT NULL DEFAULT '1' AFTER `iduser`
			");

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}price'" );
		if ( $da[0] > 0 ) {

			$db -> query("
				ALTER TABLE `{$sqlname}price`
				CHANGE COLUMN `price_2` `price_2` DOUBLE(20,2) NULL DEFAULT '0.00' AFTER `price_1`,
				CHANGE COLUMN `price_3` `price_3` DOUBLE(20,2) NULL DEFAULT '0.00' AFTER `price_2`,
				CHANGE COLUMN `price_4` `price_4` DOUBLE(20,2) NULL DEFAULT '0.00' AFTER `price_3`,
				CHANGE COLUMN `price_5` `price_5` DOUBLE(20,2) NULL AFTER `price_4`,
				CHANGE COLUMN `nds` `nds` INT(10) NOT NULL DEFAULT '18' COMMENT 'ндс' AFTER `pr_cat`
			");

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects'" );
		if ( $da[0] > 0 ) {

			$db -> query("
				ALTER TABLE `{$sqlname}projects`
				CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'Описание проекта'  AFTER `name`,
				CHANGE COLUMN `date_fact` `date_fact` DATE NULL COMMENT 'Дата завершения' AFTER `date_end`,
				CHANGE COLUMN `pid_list` `pid_list` VARCHAR(255) NULL COMMENT 'Список контактов'  AFTER `clid`
			");

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}personcat'" );
		if ( $da[0] > 0 ) {

			/*$db -> query("
				ALTER TABLE `{$sqlname}personcat`
				CHANGE COLUMN `date_create` `date_create` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления клиента' AFTER `input12`,
				CHANGE COLUMN `date_edit` `date_edit` TIMESTAMP NULL DEFAULT NULL COMMENT 'дата изменения контакта' AFTER `date_create`
			");*/

			$db -> query("
				ALTER TABLE `{$sqlname}personcat`
				CHANGE COLUMN `clientpath` `clientpath` INT(10) NULL COMMENT 'clientpath.id' AFTER `iduser`,
				CHANGE COLUMN `loyalty` `loyalty` INT(10) NULL DEFAULT '0' COMMENT 'loyal_cat.idcategory' AFTER `clientpath`,
				CHANGE COLUMN `creator` `creator` INT(10) NULL DEFAULT NULL COMMENT 'сотрудник добавивший клиента user.iduser' AFTER `date_edit`,
				CHANGE COLUMN `editor` `editor` INT(10) NULL DEFAULT NULL COMMENT 'сотрудник который внес изменение user.iduser' AFTER `creator`
			");

			$db -> query("
				ALTER TABLE `{$sqlname}personcat`
				CHANGE COLUMN `date_edit` `date_edit` TIMESTAMP NULL DEFAULT NULL AFTER `date_create`;
			");

			$db -> query("
				ALTER TABLE `{$sqlname}clientcat`
				CHANGE COLUMN `clientpath` `clientpath` INT(10) NULL DEFAULT NULL AFTER `iduser`,
				CHANGE COLUMN `territory` `territory` INT(10) NULL DEFAULT NULL AFTER `tip_cmr`,
				CHANGE COLUMN `date_edit` `date_edit` TIMESTAMP NULL DEFAULT NULL AFTER `date_create`,
				CHANGE COLUMN `creator` `creator` INT(10) NULL DEFAULT NULL AFTER `date_edit`,
				CHANGE COLUMN `editor` `editor` INT(10) NULL DEFAULT NULL AFTER `creator`,
				CHANGE COLUMN `recv` `recv` TEXT NULL DEFAULT NULL  AFTER `editor`,
				CHANGE COLUMN `dostup` `dostup` VARCHAR(255) NULL DEFAULT NULL  AFTER `recv`,
				CHANGE COLUMN `last_dog` `last_dog` DATE NULL DEFAULT NULL AFTER `dostup`,
				CHANGE COLUMN `last_hist` `last_hist` DATETIME NULL DEFAULT NULL AFTER `last_dog`
			");

			/*
			$db -> query("
				ALTER TABLE `{$sqlname}personcat`
				CHANGE COLUMN `input1` `input1` VARCHAR(255) NULL DEFAULT NULL  AFTER `loyalty`,
				CHANGE COLUMN `input2` `input2` VARCHAR(255) NULL DEFAULT NULL  AFTER `input1`,
				CHANGE COLUMN `input3` `input3` VARCHAR(255) NULL DEFAULT NULL  AFTER `input2`,
				CHANGE COLUMN `input4` `input4` VARCHAR(255) NULL DEFAULT NULL  AFTER `input3`,
				CHANGE COLUMN `input5` `input5` VARCHAR(255) NULL DEFAULT NULL  AFTER `input4`,
				CHANGE COLUMN `input6` `input6` VARCHAR(255) NULL DEFAULT NULL  AFTER `input5`,
				CHANGE COLUMN `input7` `input7` VARCHAR(255) NULL DEFAULT NULL  AFTER `input6`,
				CHANGE COLUMN `input8` `input8` VARCHAR(255) NULL DEFAULT NULL  AFTER `input7`,
				CHANGE COLUMN `input9` `input9` VARCHAR(255) NULL DEFAULT NULL  AFTER `input8`,
				CHANGE COLUMN `input10` `input10` VARCHAR(512) NULL DEFAULT NULL  AFTER `input9`,
			");
			*/

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}incoming_channels'" );
		if ( $da[0] > 0 ) {

			$db -> query("
				ALTER TABLE `{$sqlname}incoming_channels`
				CHANGE COLUMN `p_time` `p_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() AFTER `p_identity`
			");

		}

		$db -> query("
		ALTER TABLE `{$sqlname}user`
			CHANGE COLUMN `territory` `territory` INT(10) NULL DEFAULT '0' AFTER `gcalendar`,
			CHANGE COLUMN `phone` `phone` TEXT NULL COMMENT 'номер телефона'  AFTER `office`,
			CHANGE COLUMN `phone_in` `phone_in` VARCHAR(20) NULL COMMENT 'добавочный номер'  AFTER `phone`,
			CHANGE COLUMN `fax` `fax` TEXT NULL  AFTER `phone_in`,
			CHANGE COLUMN `avatar` `avatar` VARCHAR(100) NULL DEFAULT NULL COMMENT 'аватарка'  AFTER `subscription`,
			CHANGE COLUMN `usersettings` `usersettings` TEXT NULL COMMENT 'различные настройки'  AFTER `adate`,
			CHANGE COLUMN `email` `email` TEXT NULL DEFAULT NULL COMMENT 'Email'  AFTER `otdel`
		");

		$db -> query("
		ALTER TABLE `{$sqlname}group`
			CHANGE COLUMN `type` `type` INT(10) NULL DEFAULT NULL COMMENT 'DEPRECATED' AFTER `datum`,
			CHANGE COLUMN `service` `service` VARCHAR(60) NULL DEFAULT NULL COMMENT 'Связка с сервисом _services.name'  AFTER `type`,
			CHANGE COLUMN `idservice` `idservice` VARCHAR(100) NULL DEFAULT NULL COMMENT 'id группы во внешнем сервисе'  AFTER `service`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}grouplist`
			CHANGE COLUMN `clid` `clid` INT(10) ZEROFILL NULL COMMENT 'Клиент _clientcat.clid' AFTER `gid`,
			CHANGE COLUMN `pid` `pid` INT(10) ZEROFILL NULL COMMENT 'Контакт _personcat.pid' AFTER `clid`,
			CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'дата подписки' AFTER `pid`,
			CHANGE COLUMN `person_id` `person_id` INT(10) ZEROFILL NULL COMMENT 'не используется' AFTER `datum`,
			CHANGE COLUMN `service` `service` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Имя сервиса _services.name'  AFTER `person_id`,
			CHANGE COLUMN `user_name` `user_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'имя подписчика'  AFTER `service`,
			CHANGE COLUMN `user_email` `user_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'email подписчика'  AFTER `user_name`,
			CHANGE COLUMN `user_phone` `user_phone` VARCHAR(15) NULL DEFAULT NULL COMMENT 'телефон подписчика'  AFTER `user_email`,
			CHANGE COLUMN `tags` `tags` TEXT NULL DEFAULT NULL COMMENT 'тэги'  AFTER `user_phone`,
			CHANGE COLUMN `status` `status` VARCHAR(100) NULL DEFAULT NULL COMMENT 'статус подписчика'  AFTER `tags`,
			CHANGE COLUMN `availability` `availability` VARCHAR(100) NULL DEFAULT NULL COMMENT 'доступность подписчика'  AFTER `status`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}clientpath`
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NULL DEFAULT NULL COMMENT 'Дефолтный признак'  AFTER `name`,
			CHANGE COLUMN `utm_source` `utm_source` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Связка с источником'  AFTER `isDefault`,
			CHANGE COLUMN `destination` `destination` VARCHAR(12) NULL DEFAULT NULL COMMENT 'Связка с номером телефона'  AFTER `utm_source`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}contract`
			CHANGE COLUMN `idtype` `idtype` INT(10) NULL DEFAULT NULL COMMENT 'тип документа _contract_type.id' AFTER `title`,
			CHANGE COLUMN `crid` `crid` INT(10) NULL DEFAULT NULL COMMENT 'связанный счет credit.crid (для актов сервисных сделок)' AFTER `idtype`,
			CHANGE COLUMN `mcid` `mcid` INT(10) NULL DEFAULT NULL AFTER `crid`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}activities`
			CHANGE COLUMN `color` `color` VARCHAR(7) NULL DEFAULT NULL  AFTER `title`,
			CHANGE COLUMN `resultat` `resultat` TEXT NULL DEFAULT NULL  AFTER `icon`,
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NULL DEFAULT NULL  AFTER `resultat`,
			CHANGE COLUMN `aorder` `aorder` INT(10) NULL DEFAULT NULL AFTER `isDefault`,
			CHANGE COLUMN `filter` `filter` VARCHAR(255) NULL DEFAULT 'all'  AFTER `aorder`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}user`
			CHANGE COLUMN `email` `email` TEXT NULL DEFAULT NULL  AFTER `otdel`,
			CHANGE COLUMN `acs_analitics` `acs_analitics` VARCHAR(5) NULL DEFAULT NULL  AFTER `bday`,
			CHANGE COLUMN `acs_maillist` `acs_maillist` VARCHAR(5) NULL DEFAULT NULL  AFTER `acs_analitics`,
			CHANGE COLUMN `acs_files` `acs_files` VARCHAR(5) NULL DEFAULT NULL  AFTER `acs_maillist`,
			CHANGE COLUMN `acs_price` `acs_price` VARCHAR(5) NULL DEFAULT NULL  AFTER `acs_files`,
			CHANGE COLUMN `acs_credit` `acs_credit` VARCHAR(5) NULL DEFAULT NULL  AFTER `acs_price`,
			CHANGE COLUMN `acs_prava` `acs_prava` VARCHAR(5) NULL DEFAULT NULL  AFTER `acs_credit`,
			CHANGE COLUMN `tzone` `tzone` VARCHAR(5) NULL DEFAULT NULL  AFTER `acs_prava`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}comments`
			CHANGE COLUMN `mid` `mid` INT(10) NULL DEFAULT NULL AFTER `idparent`,
			CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL AFTER `datum`,
			CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL AFTER `clid`,
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
			CHANGE COLUMN `prid` `prid` INT(10) NULL DEFAULT NULL AFTER `did`,
			CHANGE COLUMN `iduser` `iduser` INT(10) ZEROFILL NULL DEFAULT NULL AFTER `project`,
			CHANGE COLUMN `title` `title` VARCHAR(255) NULL DEFAULT NULL  AFTER `iduser`,
			CHANGE COLUMN `content` `content` TEXT NULL DEFAULT NULL  AFTER `title`,
			CHANGE COLUMN `fid` `fid` TEXT NULL DEFAULT NULL  AFTER `content`,
			CHANGE COLUMN `lastCommentDate` `lastCommentDate` DATETIME NULL DEFAULT NULL AFTER `fid`,
			CHANGE COLUMN `dateClose` `dateClose` DATETIME NULL DEFAULT NULL AFTER `isClose`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}complect`
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `id`,
			CHANGE COLUMN `doit` `doit` VARCHAR(5) NOT NULL DEFAULT 'no'  AFTER `data_fact`,
			CHANGE COLUMN `iduser` `iduser` INT(10) ZEROFILL NULL DEFAULT NULL AFTER `doit`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}credit`
			CHANGE COLUMN `nds_credit` `nds_credit` DOUBLE(20,2) NOT NULL DEFAULT '0.00' AFTER `summa_credit`,
			CHANGE COLUMN `invoice` `invoice` VARCHAR(20) NULL DEFAULT NULL  AFTER `do`,
			CHANGE COLUMN `invoice_chek` `invoice_chek` VARCHAR(40) NULL DEFAULT NULL  AFTER `invoice`,
			CHANGE COLUMN `invoice_date` `invoice_date` DATE NULL DEFAULT NULL AFTER `invoice_chek`,
			CHANGE COLUMN `rs` `rs` INT(10) NULL DEFAULT NULL AFTER `invoice_date`,
			CHANGE COLUMN `tip` `tip` VARCHAR(255) NULL DEFAULT NULL  AFTER `rs`,
			CHANGE COLUMN `suffix` `suffix` TEXT NULL DEFAULT NULL  AFTER `template`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}direction`
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL DEFAULT NULL  AFTER `title`;
		");

		//создадим таблицу, если надо
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}currency'" );
		if ( $da == 0 ) {

			$db -> query( "
				CREATE TABLE {$sqlname}currency (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`datum` DATE NULL DEFAULT NULL COMMENT 'дата добавления',
					`name` VARCHAR(50) NULL DEFAULT NULL COMMENT 'название валюты',
					`view` VARCHAR(10) NULL DEFAULT NULL COMMENT 'отображаемое название валюты',
					`code` VARCHAR(10) NULL COMMENT 'код валюты',
					`course` DOUBLE(20,4) NOT NULL DEFAULT '1.00' COMMENT 'текущий курс',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					INDEX `id` (`id`)
				)
				COMMENT='Таблица курсов валют'
				ENGINE=InnoDB
			" );

		}

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}currency_log'" );
		if ( $da == 0 ) {

			$db -> query( "
				CREATE TABLE {$sqlname}currency_log (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id записи валюты',
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'дата добавления',
					`course` DOUBLE(20,4) NOT NULL DEFAULT '1.00' COMMENT 'курс на дату',
					`iduser` VARCHAR(10) NULL DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					INDEX `id` (`id`)
				)
				COMMENT='Таблица изменения курсов валют'
				ENGINE=InnoDB
			" );

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'idcurrency'" );
		if ( $field[ 'Field' ] == '' ) {

			$db -> query( "
				ALTER TABLE {$sqlname}dogovor
				CHANGE COLUMN `provider` `idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id валюты' AFTER `direction`,
				CHANGE COLUMN `akt_num` `idcourse` INT(20) NULL DEFAULT NULL COMMENT 'id курса по сделке' AFTER `idcurrency`;
			" );

			$db -> query( "UPDATE {$sqlname}dogovor SET idcurrency = '0'" );
			$db -> query( "UPDATE {$sqlname}dogovor SET idcourse = '0'" );

		}

		$db -> query("
		ALTER TABLE `{$sqlname}dogovor`
			CHANGE COLUMN `datum_start` `datum_start` DATE NULL DEFAULT NULL AFTER `con_id`,
			CHANGE COLUMN `datum_end` `datum_end` DATE NULL DEFAULT NULL AFTER `datum_start`,
			CHANGE COLUMN `pid_list` `pid_list` VARCHAR(255) NULL DEFAULT NULL  AFTER `datum_end`,
			CHANGE COLUMN `partner` `partner` VARCHAR(100) NULL DEFAULT NULL  AFTER `pid_list`,
			CHANGE COLUMN `zayavka` `zayavka` VARCHAR(200) NULL DEFAULT NULL  AFTER `partner`,
			CHANGE COLUMN `ztitle` `ztitle` VARCHAR(255) NULL DEFAULT NULL  AFTER `zayavka`,
			CHANGE COLUMN `mcid` `mcid` INT(10) NULL DEFAULT NULL AFTER `ztitle`,
			CHANGE COLUMN `direction` `direction` INT(10) NULL DEFAULT NULL AFTER `mcid`,
			CHANGE COLUMN `akt_date` `akt_date` DATE NULL DEFAULT NULL AFTER `idcourse`,
			CHANGE COLUMN `akt_temp` `akt_temp` VARCHAR(200) NULL DEFAULT NULL  AFTER `akt_date`,
			CHANGE COLUMN `lid` `lid` INT(10) NULL DEFAULT NULL AFTER `akt_temp`,
			CHANGE COLUMN `payer` `payer` INT(10) NULL DEFAULT NULL AFTER `clid`,
			CHANGE COLUMN `autor` `autor` INT(10) NULL DEFAULT NULL AFTER `datum`,
			CHANGE COLUMN `calculate` `calculate` VARCHAR(4) NULL DEFAULT NULL  AFTER `marga`
		");

		$db -> query("
		ALTER TABLE `{$sqlname}dogprovider`
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL COMMENT 'id сделки' AFTER `id`,
			CHANGE COLUMN `conid` `conid` INT(10) NULL DEFAULT NULL COMMENT 'id поставщика' AFTER `did`,
			CHANGE COLUMN `partid` `partid` INT(10) NULL DEFAULT NULL COMMENT 'id партнера' AFTER `conid`,
			CHANGE COLUMN `summa` `summa` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'сумма расхода' AFTER `partid`,
			CHANGE COLUMN `status` `status` VARCHAR(20) NULL DEFAULT NULL COMMENT 'статус проведения'  AFTER `summa`,
			CHANGE COLUMN `bid` `bid` INT(10) NULL DEFAULT NULL COMMENT 'id записи расхода в бюджете' AFTER `status`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}dogtips`
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL DEFAULT NULL  AFTER `title`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}dostup`
			CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL AFTER `id`,
			CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL AFTER `clid`,
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `did`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}entry`
			CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL AFTER `uid`,
			CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL AFTER `clid`,
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
			CHANGE COLUMN `datum_do` `datum_do` TIMESTAMP NULL DEFAULT NULL AFTER `datum`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `datum_do`,
			CHANGE COLUMN `autor` `autor` INT(10) NULL DEFAULT NULL AFTER `iduser`,
			CHANGE COLUMN `content` `content` TEXT NULL DEFAULT NULL  AFTER `autor`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}field`
			CHANGE COLUMN `fld_tip` `fld_tip` VARCHAR(10) NULL DEFAULT NULL  AFTER `fld_id`,
			CHANGE COLUMN `fld_name` `fld_name` VARCHAR(10) NULL DEFAULT NULL  AFTER `fld_tip`,
			CHANGE COLUMN `fld_stat` `fld_stat` VARCHAR(10) NULL DEFAULT NULL  AFTER `fld_order`,
			CHANGE COLUMN `fld_temp` `fld_temp` VARCHAR(255) NULL DEFAULT NULL  AFTER `fld_stat`,
			CHANGE COLUMN `fld_var` `fld_var` TEXT NULL DEFAULT NULL  AFTER `fld_temp`;
		");

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2022.2')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message;

		}

	}
	if ( getVersion() == '2022.2' ) {

		$db -> query("SET FOREIGN_KEY_CHECKS=0;");

		/**
		 * Заморозка
		 */
		$db -> query( "UPDATE {$sqlname}dogovor SET datum_start = NULL WHERE datum_start = '0000-00-00'");
		$db -> query( "UPDATE {$sqlname}dogovor SET datum_end = NULL WHERE datum_end = '0000-00-00'");

		$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'isFrozen'" );
		if ( $fi['Field'] == '' ) {

			$db -> query("UPDATE {$sqlname}dogovor SET con_id = 0");
			$db -> query("ALTER TABLE {$sqlname}dogovor CHANGE COLUMN `con_id` `isFrozen` INT(1) NULL DEFAULT 0 COMMENT 'признак заморозки'  AFTER `calculate`");

		}

		$stepInHold = customSettings('stepInHold');
		if( (int)$stepInHold['step'] > 0) {

			$db -> query("UPDATE {$sqlname}dogovor SET isFrozen = 1 WHERE idcategory = '".(int)$stepInHold['step']."'");

			// проходим замороженные сделки и возвращаем на предыдущий этап
			$list = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE idcategory = '".(int)$stepInHold['step']."' AND close != 'yes'");
			foreach($list as $item){

				// последняя запись о смене этапа
				$last = $db -> getRow("SELECT id, step FROM {$sqlname}steplog WHERE did = '".$item['did']."' and step != '".(int)$stepInHold['step']."' ORDER BY datum DESC LIMIT 1");
				$db -> query("UPDATE {$sqlname}dogovor SET idcategory = '$last[step]' WHERE did = '".$item['did']."'");

				$db -> query("DELETE FROM {$sqlname}steplog WHERE did = '".$item['did']."' AND step = '".(int)$stepInHold['step']."'");

			}

			$db -> query("DELETE FROM {$sqlname}customsettings WHERE tip = 'stepInHold'");

			// обновим настройки
			$other = $db -> getOne("SELECT other FROM {$sqlname}settings WHERE id = '$identity'");
			$other = explode(";", $other);
			$other['45'] = $stepInHold['input'];

			$db -> query("UPDATE {$sqlname}settings SET other = '".implode(";", $other)."'");

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2022.3')" );

		$currentVer = getVersion();

		$db -> query("SET FOREIGN_KEY_CHECKS=1;");

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro<div class="main_div div-center"><A href="/" class="button"><b>К рабочему столу</b></A></div>';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message."\n";

		}

	}

	/*2023*/

	if ( getVersion() == '2022.3' ) {

		$db -> query("SET FOREIGN_KEY_CHECKS=0;");

		$db -> query("ALTER TABLE `{$sqlname}history` CHANGE COLUMN `uid` `uid` VARCHAR(100) NULL  AFTER `fid`");
		$db -> query("ALTER TABLE `{$sqlname}ymail_messages`
			CHANGE COLUMN `messageid` `messageid` VARCHAR(255) NULL DEFAULT NULL  AFTER `subbolder`,
			CHANGE COLUMN `uid` `uid` INT(10) NULL DEFAULT NULL AFTER `messageid`,
			CHANGE COLUMN `fromm` `fromm` MEDIUMTEXT NULL DEFAULT NULL  AFTER `parentmid`,
			CHANGE COLUMN `fromname` `fromname` MEDIUMTEXT NULL DEFAULT NULL  AFTER `fromm`,
			CHANGE COLUMN `theme` `theme` VARCHAR(255) NULL DEFAULT NULL  AFTER `fromname`,
			CHANGE COLUMN `content` `content` LONGTEXT NULL DEFAULT NULL  AFTER `theme`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `content`,
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `fid`
		");

		$db ->query("UPDATE `{$sqlname}dogovor` SET pid = 0 WHERE pid = ''");
		$db ->query("ALTER TABLE `{$sqlname}dogovor`
			CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL COLLATE 'utf8mb3_general_ci' AFTER `payer`
		");

		$db -> query("UPDATE {$sqlname}clientcat SET pid = NULL WHERE pid = '' OR pid = 0");
		$db -> query("ALTER TABLE `{$sqlname}clientcat`
			CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL COMMENT 'основной контакт (pid в таблице _personcat.pid)' COLLATE 'utf8mb3_general_ci' AFTER `fav`;
		");

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2023.1')" );

		$currentVer = getVersion();

		$db -> query("SET FOREIGN_KEY_CHECKS=1;");

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro<div class="main_div div-center"><A href="/" class="button"><b>К рабочему столу</b></A></div>';

		}

		if (PHP_SAPI != 'cli') {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message."\n";

		}

	}

	/*2024*/

	if ( getVersion() == '2023.1' ) {

		$db -> query("SET FOREIGN_KEY_CHECKS=0;");

		$db -> query("SET sql_mode='NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES';");
		$db -> query("UPDATE `{$sqlname}budjet` SET datum = NULL WHERE datum = '0000-00-00 00:00:00';");

		$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}field LIKE 'fld_sub'" );
		if ( $fi['Field'] == '' ) {

			$db -> query("ALTER TABLE `{$sqlname}field` ADD COLUMN `fld_sub` VARCHAR(10) NULL DEFAULT NULL COMMENT 'доп.разделение для карточек клиентов - клиент, поставщик, партнер..' AFTER `fld_var`");

		}

		$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}budjet LIKE 'date_plan'" );
		if ( $fi['Field'] == '' ) {

			$db -> query("ALTER TABLE `{$sqlname}budjet`
				ADD COLUMN `date_plan` DATE NULL DEFAULT NULL COMMENT 'плановая дата' AFTER `partid`,
				ADD COLUMN `invoice` VARCHAR(255) NULL DEFAULT NULL COMMENT 'номер счета' AFTER `date_plan`,
				ADD COLUMN `invoice_date` DATE NULL DEFAULT NULL COMMENT 'дата счета' AFTER `invoice`,
				ADD COLUMN `invoice_paydate` DATE NULL DEFAULT NULL COMMENT 'дата оплаты счета' AFTER `invoice_date`
			");

		}

		//создадим таблицу для шаблонов проектов, если надо
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_templates'" );
		if ( $da == 0 ) {

			$db -> query( "CREATE TABLE `{$sqlname}projects_templates` (
				    `id`       INT(10)      NOT NULL AUTO_INCREMENT,
				    `title`    VARCHAR(255) NULL DEFAULT 'untitled' COMMENT 'Название шаблона',
				    `autor`    INT(10)      NULL DEFAULT NULL COMMENT 'iduser автора',
				    `datum`    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
				    `content`  TEXT         NULL DEFAULT NULL COMMENT 'Содержание работ в json',
				    `state`    INT(10)      NULL DEFAULT '1' COMMENT 'Статус: 1 - активен, 0 - не активен',
				    `identity` INT(10)      NULL DEFAULT '1',
				    PRIMARY KEY (`id`) USING BTREE
				)
				COMMENT ='Шаблоны проектов'
				ENGINE = InnoDB
			" );

		}

		//создадим таблицу для лога статусов
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}budjetlog'" );
		if ( $da == 0 ) {

			$db -> query( "CREATE TABLE `{$sqlname}budjetlog` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
				`status` VARCHAR(10) NULL DEFAULT NULL COMMENT 'статус расхода',
				`bjid` INT(10) NULL DEFAULT NULL COMMENT 'id расхода',
				`iduser` INT(10) NULL DEFAULT NULL COMMENT 'id пользователя user.iduser внес изменение',
				`comment` VARCHAR(255) NULL DEFAULT NULL COMMENT 'комментарий',
				`identity` INT(10) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`) USING BTREE,
				INDEX `status` (`status`) USING BTREE,
				INDEX `bjid` (`bjid`) USING BTREE
			)
			COMMENT='Лог изменений статуса расходов'
			ENGINE=InnoDB" );

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver SET ?u", ["current" => '2024.1']);

		$currentVer = getVersion();

		$db -> query("SET FOREIGN_KEY_CHECKS=1;");

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro<div class="main_div div-center"><A href="/" class="button"><b>К рабочему столу</b></A></div>';

		}

		if (PHP_SAPI != 'cli') {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message."\n";

		}

	}
	if ( getVersion() == '2024.1' ) {

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver SET ?u", ["current" => '2024.2']);

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro<div class="main_div div-center"><A href="/" class="button"><b>К рабочему столу</b></A></div>';

		}

		if (PHP_SAPI != 'cli') {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message."\n";

		}

	}

	if ( getVersion() == '2024.2' && in_array( '2024.3', $seria)) {

		$db -> query("
			UPDATE {$sqlname}entry SET datum_do = NULL WHERE datum_do = '0000-00-00 00:00:00'
		");

		$db -> query("
		ALTER TABLE `{$sqlname}entry`
			CHANGE COLUMN `datum` `datum` DATETIME NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата создания' AFTER `did`,
			CHANGE COLUMN `datum_do` `datum_do` DATETIME NULL DEFAULT NULL COMMENT 'дата обработки обращения' AFTER `datum`
		");

		$db -> query("
		ALTER TABLE `{$sqlname}entry_poz`
			CHANGE COLUMN `kol` `kol` DOUBLE NULL DEFAULT NULL COMMENT 'количество' AFTER `title`
		");

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver SET ?u", ["current" => '2024.3']);

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = (PHP_SAPI === 'cli') ? 'Обновление до версии '.$currentVer.' установлено' : 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - salesman.pro<div class="main_div div-center"><A href="/" class="button"><b>К рабочему столу</b></A></div>';

		}

		if (PHP_SAPI != 'cli') {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
			<div class="main_div div-center">
				<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
			</div>
			';

		}

		if ( PHP_SAPI === 'cli' ) {

			print $message."\n";

		}

	}

}

//printf("Step: %s; Sapi: %s; Ver: %s; LastVer: %s; IsLast: %s\n", $step, PHP_SAPI, getVersion(), $lastVer, getVersion() == $lastVer);

//исправления для текущих версий, если ранее что-то пошло не так
if (  ($step == 1 || PHP_SAPI == 'cli') && getVersion() == $lastVer ) {
	
	//print "WoW\n";

	//в каждом счете укажем ответственного за сделку
	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}credit LIKE 'idowner'" );
	if ( $field['Field'] == '' ) {

		$db -> query( "ALTER TABLE {$sqlname}credit ADD COLUMN `idowner` INT(100) NULL DEFAULT NULL COMMENT 'Ответственный за сделку' AFTER `iduser`" );

		$result = $db -> query( "SELECT did, iduser FROM {$sqlname}dogovor WHERE close != 'yes'" );
		while ($data = $db -> fetch( $result )) {

			$db -> query( "UPDATE {$sqlname}credit set idowner = '$data[iduser]' WHERE did = '$data[did]' and do != 'on'" );

		}

	}

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}speca LIKE 'tip'" );
	if ( $field['Field'] == '' ) {

		$db -> query( "ALTER TABLE {$sqlname}speca ADD COLUMN `tip` INT(1) NOT NULL DEFAULT '0' COMMENT '0 - товар, 1 - услуга, 2 - материал' AFTER `title`" );

	}

	$db -> query( "ALTER TABLE {$sqlname}contract_status CHANGE `title` `title` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'название статуса'" );
	$db -> query( "ALTER TABLE {$sqlname}contract_statuslog CHANGE `des` `des` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'комментарий'" );

	$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modworkplan'" );
	if ( $da != 0 ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modworkplan LIKE 'clid'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}modworkplan ADD COLUMN `clid` INT(20) NULL DEFAULT NULL AFTER `did`" );

			$res = $db -> query( "SELECT did, id FROM {$sqlname}modworkplan" );
			while ($da = $db -> fetch( $res )) {

				$clid = $db -> getOne( "SELECT clid FROM {$sqlname}dogovor WHERE did = '".$da['did']."'" );
				$db -> query( "UPDATE {$sqlname}modworkplan SET clid = '$clid' WHERE id = ?i", $da['id'] );

			}

		}

	}

	$db -> query( "ALTER TABLE {$sqlname}budjet CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `summa`" );

	$db -> query( "
		ALTER TABLE {$sqlname}speca 
		CHANGE COLUMN `artikul` `artikul` VARCHAR(100) NULL AFTER `did`, 
		CHANGE COLUMN `edizm` `edizm` VARCHAR(10) NULL AFTER `kol`, 
		CHANGE COLUMN `dop` `dop` INT(10) NULL DEFAULT '1' AFTER `nds`, 
		CHANGE COLUMN `comments` `comments` VARCHAR(250) NULL AFTER `dop`
	" );

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogprovider LIKE 'recal'" );
	if ( $field['Field'] == '' ) {

		$db -> query( "
			ALTER TABLE `{$sqlname}dogprovider`
			CHANGE COLUMN `did` `did` INT(20) NOT null COMMENT 'id сделки' AFTER `id`,
			CHANGE COLUMN `conid` `conid` INT(20) NOT null COMMENT 'id поставщика' AFTER `did`,
			CHANGE COLUMN `partid` `partid` INT(20) NOT null COMMENT 'id партнера' AFTER `conid`,
			CHANGE COLUMN `summa` `summa` DOUBLE(20, 2) NOT null COMMENT 'сумма расхода' AFTER `partid`,
			CHANGE COLUMN `status` `status` VARCHAR(20) NOT null COMMENT 'статус проведения' AFTER `summa`,
			CHANGE COLUMN `bid` `bid` INT(20) NOT null COMMENT 'id записи расхода в бюджете' AFTER `status`,
			ADD COLUMN `recal` INT(2) null DEFAULT '0' COMMENT '0 - учитывать в расчете маржи, 1 - не учитывать' AFTER `bid`
		" );

	}

	/*$db -> query("
		ALTER TABLE {$sqlname}user
		ALTER `user_post` DROP DEFAULT,
		ALTER `CompStart` DROP DEFAULT,
		ALTER `CompEnd` DROP DEFAULT,
		ALTER `adate` DROP DEFAULT,
		ALTER `uid` DROP DEFAULT
	");*/
	$db -> query( "
		ALTER TABLE {$sqlname}user
		CHANGE COLUMN `tip` `tip` VARCHAR(250) NULL DEFAULT 'Менеджер продаж' AFTER `title`,
		CHANGE COLUMN `user_post` `user_post` VARCHAR(255) NULL AFTER `tip`,
		CHANGE COLUMN `mid` `mid` INT(10) NULL DEFAULT '0' AFTER `user_post`,
		CHANGE COLUMN `bid` `bid` INT(10) NOT NULL DEFAULT '0' AFTER `mid`,
		CHANGE COLUMN `otdel` `otdel` TEXT NULL AFTER `bid`,
		CHANGE COLUMN `gcalendar` `gcalendar` TEXT NULL AFTER `email`,
		CHANGE COLUMN `territory` `territory` INT(20) NOT NULL DEFAULT '0' AFTER `gcalendar`,
		CHANGE COLUMN `office` `office` INT(10) NULL DEFAULT '0' AFTER `territory`,
		CHANGE COLUMN `zam` `zam` INT(20) NULL DEFAULT '0' AFTER `acs_plan`,
		CHANGE COLUMN `CompStart` `CompStart` DATE NULL AFTER `zam`,
		CHANGE COLUMN `CompEnd` `CompEnd` DATE NULL AFTER `CompStart`,
		CHANGE COLUMN `adate` `adate` DATE NULL AFTER `sole`,
		CHANGE COLUMN `usersettings` `usersettings` TEXT NULL AFTER `adate`,
		CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL AFTER `usersettings`
	" );

	$db -> query( "
		ALTER TABLE {$sqlname}user
		CHANGE COLUMN `bid` `bid` INT(10) NULL DEFAULT '0' AFTER `mid`,
		CHANGE COLUMN `bday` `bday` DATE NULL AFTER `mob`;
	" );

	$db -> query( "
		ALTER TABLE {$sqlname}user
		CHANGE COLUMN `viget_on` `viget_on` VARCHAR(500) NOT NULL DEFAULT 'on;on;on;on;on;on;on;on;on;on;on' AFTER `tzone`,
		CHANGE COLUMN `viget_order` `viget_order` VARCHAR(500) NOT NULL DEFAULT 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11' AFTER `viget_on`
	" );

	$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}notify'" );
	if ( $da[0] == 0 ) {

		$db -> query( "
		CREATE TABLE {$sqlname}notify (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'время уведомления',
			`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'заголовок уведомления',
			`content` TEXT NULL DEFAULT NULL COMMENT 'содержимое уведомления',
			`url` TEXT NULL DEFAULT NULL COMMENT 'ссылка на сущность',
			`tip` VARCHAR(50) NULL DEFAULT NULL COMMENT 'тип связанной записи',
			`uid` INT(10) NULL DEFAULT NULL COMMENT 'id связанной записи',
			`status` VARCHAR(2) NULL DEFAULT '0' COMMENT 'Статус прочтения - 0 Не прочитано, 1 Прочитано',
			`autor` INT(10) NULL DEFAULT NULL COMMENT 'автор события',
			`iduser` INT(10) NULL DEFAULT NULL COMMENT 'цель - сотрудник',
			`identity` INT(30) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`)
		)
		COMMENT='База уведомлений' 
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM" );

	}

	//создадим таблицу, если надо
	$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}currency'" );
	if ( $da == 0 ) {

		$db -> query( "
				CREATE TABLE {$sqlname}currency (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`datum` DATE NULL DEFAULT NULL COMMENT 'дата добавления',
					`name` VARCHAR(50) NULL DEFAULT NULL COMMENT 'название валюты',
					`view` VARCHAR(10) NULL DEFAULT NULL COMMENT 'отображаемое название валюты',
					`code` VARCHAR(10) NULL COMMENT 'код валюты',
					`course` DOUBLE(20,4) NOT NULL DEFAULT '1.00' COMMENT 'текущий курс',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					INDEX `id` (`id`)
				)
				COMMENT='Таблица курсов валют'
				ENGINE=InnoDB
			" );

		$db -> query( "
				CREATE TABLE {$sqlname}currency_log (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id записи валюты',
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'дата добавления',
					`course` DOUBLE(20,4) NOT NULL DEFAULT '1.00' COMMENT 'курс на дату',
					`iduser` VARCHAR(10) NULL DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					INDEX `id` (`id`)
				)
				COMMENT='Таблица изменения курсов валют'
				ENGINE=InnoDB
			" );

	}

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'idcurrency'" );
	if ( $field['Field'] == '' ) {

		$db -> query( "
			ALTER TABLE {$sqlname}dogovor
			CHANGE COLUMN `provider` `idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id валюты' AFTER `direction`,
			CHANGE COLUMN `akt_num` `idcourse` INT(20) NULL DEFAULT NULL COMMENT 'id курса по сделке' AFTER `idcurrency`;
		" );

		$db -> query( "UPDATE {$sqlname}dogovor SET idcurrency = '0'" );
		$db -> query( "UPDATE {$sqlname}dogovor SET idcourse = '0'" );

	}

	//наличие таблицы
	$keys = $db -> getRow( "SHOW KEYS FROM {$sqlname}contract WHERE Key_name = 'did_iduser'" );
	if ( empty( $keys ) ) {
		$da = $db -> query( "ALTER TABLE `{$sqlname}contract` ADD INDEX `did_iduser` (`did`, `iduser`)" );
	}

	//наличие таблицы
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}tasks` WHERE Key_name = 'clid'" );
	if ( empty( $keys ) ) {
		$da = $db -> query( "ALTER TABLE `{$sqlname}tasks` ADD INDEX ( `clid` )" );
	}

	// индекс связи напоминаний и активностей
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}tasks` WHERE Key_name = 'cid'" );
	if ( empty( $keys ) ) {
		$da = $db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX `cid` (`cid`)" );
	}

	/**
	 * Добавляем доп.таблицу статусов. Если модуль Проекты установлен
	 */
	$dap = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects'" );
	if ( (int)$dap > 0 ) {

		//$message .= "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_status'<br>";
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_status'" );
		if ( (int)$da == 0 ) {

			$db -> query( "
					CREATE TABLE `{$sqlname}projects_status` (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`type` CHAR(10) NOT NULL DEFAULT 'prj' COMMENT 'Тип статуса. prj - проекты, wrk - работы',
					`name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Имя статуса',
					`color` VARCHAR(10) NULL DEFAULT 'blue' COMMENT 'Цвет статуса',
					`icon` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Иконка статуса',
					`sort` INT(10) NULL DEFAULT NULL COMMENT 'Порядок вывода',
					`control` CHAR(5) NULL DEFAULT 'false' COMMENT 'Только для кураторов',
					`isfinal` CHAR(5) NULL DEFAULT 'false' COMMENT 'Признак финального этапа',
					`iscancel` CHAR(5) NULL DEFAULT 'false' COMMENT 'Признак отмены',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`) USING BTREE
				)
				COMMENT='Модуль проекты. Статусы'
				COLLATE='utf8_general_ci'
				ENGINE=InnoDB
			" );

			/**
			 * Переведем существующие статусы в БД
			 */
			$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
			while ($data = $db -> fetch( $result )) {

				$sort   = 0;
				$exists = [];
				foreach ( Project::STATUSPROJECT as $index => $status ) {

					$db -> query( "INSERT INTO {$sqlname}projects_status SET ?u", [
						"type"     => "prj",
						"name"     => $status,
						"color"    => Project::COLORSPROJECT[ $index ],
						"icon"     => Project::ICONSPROJECT[ $index ],
						"sort"     => $sort,
						"control"  => in_array( $index, [
							2,
							3
						] ) ? "true" : "false",
						"isfinal"  => in_array( $index, [
							2,
							3
						] ) ? "true" : "false",
						"iscancel" => $index == 3 ? "true" : "false",
						"identity" => $data['id']
					] );
					$prjID = $db -> insertId();

					$s = (!empty( $exists )) ? "id NOT IN (".yimplode( ",", $exists ).") AND" : "";

					$ids = $db -> getCol( "SELECT id FROM {$sqlname}projects WHERE $s status = '$index' AND identity = '$data[id]'" );

					foreach ( $ids as $ida ) {
						$exists[] = $ida;
					}

					/**
					 * Обновим статусы всех проектов
					 */
					if ( !empty( $ids ) ) {

						$db -> query( "UPDATE {$sqlname}projects SET status = '$prjID' WHERE id IN (".yimplode( ",", $ids ).") AND status = '$index' AND identity = '$data[id]'" );

						//print "UPDATE {$sqlname}projects SET status = '$prjID' WHERE id IN (".yimplode(",", $ids).") AND status = '$index' AND identity = '$data[id]'<br>";

					}

					/**
					 * Обновим записи в логе статусов
					 */
					$db -> query( "UPDATE {$sqlname}projects_statuslog SET status = '$prjID' WHERE status = '$index' AND work = '0' AND project > 0 AND identity = '$data[id]'" );

					$sort++;

				}

				$sort   = 0;
				$exists = [];
				foreach ( Project::STATUSWORK as $index => $status ) {

					$db -> query( "INSERT INTO {$sqlname}projects_status SET ?u", [
						"type"     => "wrk",
						"name"     => $status,
						"color"    => Project::COLORSWORK[ $index ],
						"icon"     => Project::ICONSWORK[ $index ],
						"sort"     => $sort,
						"control"  => in_array( $index, [
							4,
							5
						] ) ? "true" : "false",
						"isfinal"  => in_array( $index, [
							4,
							5
						] ) ? "true" : "false",
						"iscancel" => $index == 5 ? "true" : "false",
						"identity" => $data['id']
					] );
					$wrkID = $db -> insertId();

					$s = (!empty( $exists )) ? "id NOT IN (".yimplode( ",", $exists ).") AND" : "";

					$ids = $db -> getCol( "SELECT id FROM {$sqlname}projects_work WHERE $s status = '$index' AND identity = '$data[id]'" );

					foreach ( $ids as $ida ) {
						$exists[] = $ida;
					}

					/**
					 * Обновим статусы всех проектов
					 */
					if ( !empty( $ids ) ) {

						$db -> query( "UPDATE {$sqlname}projects_work SET status = '$wrkID' WHERE status = '$index' AND identity = '$data[id]'" );

					}

					/**
					 * Обновим записи в логе статусов
					 */
					$db -> query( "UPDATE {$sqlname}projects_statuslog SET status = '$wrkID' WHERE status = '$index' AND work > 0 AND project > 0 AND identity = '$data[id]'" );

					$sort++;

				}

			}

		}

	}

	//ALTER TABLE `app_projects_status` ADD COLUMN `isfinal` CHAR(5) NULL DEFAULT 'false' COMMENT 'Признак финального этапа' AFTER `control`;
	//ALTER TABLE `app_projects_status` ADD COLUMN `iscancel` CHAR(5) NULL DEFAULT 'false' COMMENT 'Признак отмены' AFTER `isfinal`;

	/**
	 * Индекс для групп
	 */
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}grouplist` WHERE Key_name = 'gid_clid_identity'" );
	if ( empty( $keys ) ) {
		$da = $db -> query( "ALTER TABLE `{$sqlname}grouplist` ADD INDEX `gid_clid_identity` (`gid`, `clid`, `identity`)" );
	}

	$db -> query( "ALTER TABLE `{$sqlname}comments` CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT NULL AFTER `mid`;" );

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}ymail_messages` WHERE Key_name = 'complex'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}ymail_messages` ADD INDEX `complex` (`folder`, `state`, `iduser`, `identity`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}ymail_messages` WHERE Key_name = 'did'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}ymail_messages` ADD INDEX `did` (`did`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}ymail_messages` WHERE Key_name = 'theme'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}ymail_messages` ADD INDEX `theme` (`theme`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'do'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `do` (`do`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'did'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `did` (`did`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'clid'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `clid` (`clid`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'iduser'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `iduser` (`iduser`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'datum_credit'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `datum_credit` (`datum_credit`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}dogovor` WHERE Key_name = 'sid'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}dogovor` ADD INDEX `sid` (`sid`), ADD INDEX `close` (`close`), ADD INDEX `datum` (`datum`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}grouplist` WHERE Key_name = 'clid'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}grouplist` ADD INDEX `clid` (`clid`), ADD INDEX `pid` (`pid`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}user` WHERE Key_name = 'mid'" );
	if ( empty( $keys ) ) {
		$db -> query( "ALTER TABLE `{$sqlname}user` ADD INDEX `mid` (`mid`), ADD INDEX `secrty` (`secrty`)" );
	}

	$db -> query( "ALTER TABLE `{$sqlname}tasks` CHANGE COLUMN `pid` `pid` VARCHAR(255) NULL COMMENT 'personcat.pid (может быть несколько с разделением ;)'  AFTER `clid`" );

	// оптимизация комментариев
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}comments` WHERE Key_name = 'mid'" );
	if ( empty( $keys ) ) {

		$db -> query( "ALTER TABLE `{$sqlname}comments` ADD INDEX `mid` (`mid`), ADD INDEX `idparent` (`idparent`), ADD INDEX `isClose` (`isClose`)" );
		$db -> query( "ALTER TABLE `{$sqlname}comments_subscribe` ADD INDEX `idcomment` (`idcomment`), ADD INDEX `iduser` (`iduser`)" );

	}

	$dap = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects'" );
	if ( (int)$dap > 0 ) {

		$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}projects` WHERE Key_name = 'did'" );
		if ( empty( $keys ) ) {

			$db -> query( "ALTER TABLE `{$sqlname}projects` ADD INDEX `did` (`did`), ADD INDEX `clid` (`clid`), ADD INDEX `iduser` (`iduser`), ADD INDEX `status` (`status`)" );
			$db -> query( "ALTER TABLE `{$sqlname}projects_status` ADD INDEX `type` (`type`)" );
			$db -> query( "ALTER TABLE `{$sqlname}projects_statuslog` ADD INDEX `project` (`project`), ADD INDEX `work` (`work`)" );
			$db -> query( "ALTER TABLE `{$sqlname}projects_task` ADD INDEX `tid` (`tid`), ADD INDEX `idwork` (`idwork`)" );
			$db -> query( "ALTER TABLE `{$sqlname}projects_work` ADD INDEX `idproject` (`idproject`), ADD INDEX `type` (`type`), ADD INDEX `iduser` (`iduser`), ADD INDEX `date_fact` (`date_fact`)" );

		}

	}

	$db -> query("SET FOREIGN_KEY_CHECKS=0;");

	$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'con_id'" );
	if ( $fi['Field'] != '' ) {

		$db -> query("
		ALTER TABLE `{$sqlname}dogovor`
			CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL DEFAULT NULL AFTER `did`,
			CHANGE COLUMN `con_id` `con_id` VARCHAR(255) NULL DEFAULT NULL AFTER `calculate`
		");

	}
	else{

		$db -> query("
		ALTER TABLE `{$sqlname}dogovor`
			CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL DEFAULT NULL AFTER `did`
		");

	}

	$db -> query( "
	ALTER TABLE `{$sqlname}history`
		CHANGE COLUMN `uid` `uid` VARCHAR(100) NULL DEFAULT NULL AFTER `fid`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}clientcat`
		CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL DEFAULT NULL AFTER `clid`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}speca`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `edizm`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}contract`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() AFTER `deid`,
		CHANGE COLUMN `payer` `payer` INT(10) NULL DEFAULT NULL AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `ftype`,
		CHANGE COLUMN `title` `title` TEXT NULL DEFAULT NULL  AFTER `iduser`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}leads`
		CHANGE COLUMN `datum_do` `datum_do` DATETIME NULL DEFAULT NULL COMMENT 'дата обработки' AFTER `datum`,
		CHANGE COLUMN `status` `status` INT(10) NULL DEFAULT NULL COMMENT 'статус 0 => Открыт, 1 => В работе, 2 => Обработан, 3 => Закрыт' AFTER `datum_do`,
		CHANGE COLUMN `rezult` `rezult` INT(10) NULL DEFAULT NULL COMMENT 'результат обработки 1 => Спам, 2 => Дубль, 3 => Другое, 4 => Не целевой' AFTER `status`,
		CHANGE COLUMN `description` `description` TEXT NULL DEFAULT NULL COMMENT 'описание заявки'  AFTER `company`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT '0' COMMENT '_user.iduser' AFTER `timezone`,
		CHANGE COLUMN `clientpath` `clientpath` INT(10) NULL DEFAULT NULL COMMENT '_clientpath.id' AFTER `iduser`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL COMMENT '_personcat.pid' AFTER `clientpath`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL COMMENT '_clientcat.clid' AFTER `pid`,
		CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL COMMENT '_dogovor.did' AFTER `clid`,
		CHANGE COLUMN `partner` `partner` INT(10) NULL DEFAULT NULL COMMENT '_clientcat.clid' AFTER `did`,
		CHANGE COLUMN `rezz` `rezz` TEXT NULL DEFAULT NULL COMMENT 'комментарий при дисквалификации заявки' AFTER `muid`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}smtp`
			CHANGE COLUMN `smtp_host` `smtp_host` VARCHAR(255) NULL AFTER `active`,
			CHANGE COLUMN `smtp_port` `smtp_port` INT(10) NULL AFTER `smtp_host`,
			CHANGE COLUMN `smtp_auth` `smtp_auth` VARCHAR(5) NULL AFTER `smtp_port`,
			CHANGE COLUMN `smtp_secure` `smtp_secure` VARCHAR(5) NULL AFTER `smtp_auth`,
			CHANGE COLUMN `smtp_user` `smtp_user` VARCHAR(100) NULL AFTER `smtp_secure`,
			CHANGE COLUMN `smtp_pass` `smtp_pass` VARCHAR(200) NULL AFTER `smtp_user`,
			CHANGE COLUMN `smtp_from` `smtp_from` VARCHAR(255) NULL AFTER `smtp_pass`,
			CHANGE COLUMN `smtp_protocol` `smtp_protocol` VARCHAR(5) NULL AFTER `smtp_from`,
			CHANGE COLUMN `tip` `tip` VARCHAR(10) NULL AFTER `smtp_protocol`,
			CHANGE COLUMN `name` `name` VARCHAR(255) NULL AFTER `tip`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'id пользователя user.iduser' AFTER `name`
		");

	$db -> query("
		ALTER TABLE `{$sqlname}mycomps`
			CHANGE COLUMN `name_ur` `name_ur` TEXT NULL DEFAULT NULL COMMENT 'полное наименование' AFTER `id`,
			CHANGE COLUMN `name_shot` `name_shot` TEXT NULL DEFAULT NULL COMMENT 'сокращенное наименование' AFTER `name_ur`,
			CHANGE COLUMN `address_yur` `address_yur` TEXT NULL DEFAULT NULL COMMENT 'юридические адрес' AFTER `name_shot`,
			CHANGE COLUMN `address_post` `address_post` TEXT NULL DEFAULT NULL COMMENT 'почтовый адрес' AFTER `address_yur`,
			CHANGE COLUMN `dir_name` `dir_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'в лице руководителя' AFTER `address_post`,
			CHANGE COLUMN `dir_signature` `dir_signature` VARCHAR(255) NULL DEFAULT NULL COMMENT 'подпись руководителя' AFTER `dir_name`,
			CHANGE COLUMN `dir_status` `dir_status` TEXT NULL DEFAULT NULL COMMENT 'должность руководителя' AFTER `dir_signature`,
			CHANGE COLUMN `dir_osnovanie` `dir_osnovanie` TEXT NULL DEFAULT NULL COMMENT 'действующего на основаии' AFTER `dir_status`,
			CHANGE COLUMN `innkpp` `innkpp` VARCHAR(255) NULL DEFAULT NULL COMMENT 'инн-кпп' AFTER `dir_osnovanie`,
			CHANGE COLUMN `okog` `okog` VARCHAR(255) NULL DEFAULT NULL COMMENT 'окпо-огрн' AFTER `innkpp`,
			CHANGE COLUMN `stamp` `stamp` VARCHAR(255) NULL DEFAULT NULL COMMENT 'файл с факсимилией' AFTER `okog`,
			CHANGE COLUMN `logo` `logo` VARCHAR(255) NULL DEFAULT NULL COMMENT 'файл с логотипом' AFTER `stamp`
		");

	/**
	 * Обновим структуру таблиц
	 */
	/*$tables = ['clientcat','dogovor','personcat'];

	foreach ($tables as $table) {

		$fields = $db -> getAll("SHOW FIELDS FROM {$sqlname}$table");

		foreach ($fields as $i => $field){

			$fcurrent = $field['Field'];
			$fprev    = $fields[$i-1]['Field'];
			$ftype    = $field['Type'];

			if (stripos($fcurrent, 'input') !== false) {

				$db -> query("ALTER TABLE {$sqlname}$table CHANGE COLUMN `$fcurrent` `$fcurrent` $ftype NULL DEFAULT NULL  AFTER `$fprev`");

			}

		}

	}*/

	$db -> query("ALTER TABLE {$sqlname}speca CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() AFTER `edizm`");
	$db -> query("UPDATE {$sqlname}dogovor SET close = 'no' WHERE close IS NULL");

	$db -> query("
	ALTER TABLE {$sqlname}comments
		CHANGE COLUMN `mid` `mid` INT(10) NULL COMMENT 'DEPRECATED' AFTER `idparent`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clientcat.clid' AFTER `datum`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL COMMENT 'personcat.pid' AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'dogovor.did' AFTER `pid`,
		CHANGE COLUMN `prid` `prid` INT(10) NULL COMMENT 'price.n_id' AFTER `did`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'user.iduser' AFTER `project`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'заголовок' AFTER `iduser`,
		CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'текст' AFTER `title`,
		CHANGE COLUMN `fid` `fid` TEXT NULL COMMENT '_files.fid в виде списка с разделением ;' AFTER `content`,
		CHANGE COLUMN `lastCommentDate` `lastCommentDate` DATETIME NULL COMMENT 'дата последнего коментария' AFTER `fid`,
		CHANGE COLUMN `isClose` `isClose` VARCHAR(10) NULL DEFAULT 'no' COMMENT 'закрыто или открыты обсуждение' AFTER `lastCommentDate`,
		CHANGE COLUMN `dateClose` `dateClose` DATETIME NULL COMMENT 'дата закрытия обсуждения' AFTER `isClose`
	");

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}comments` WHERE Key_name = 'clid'" );
	if ( empty( $keys ) ) {

		$db -> query( "
		ALTER TABLE {$sqlname}comments
			ADD INDEX `clid` (`clid`),
			ADD INDEX `pid` (`pid`),
			ADD INDEX `did` (`did`),
			ADD INDEX `project` (`project`),
			ADD INDEX `iduser` (`iduser`)
		" );

	}

	$db -> query("UPDATE {$sqlname}comments SET lastCommentDate = NULL WHERE lastCommentDate = '0000-00-00 00:00:00'");
	$db -> query("UPDATE {$sqlname}comments SET dateClose = NULL WHERE dateClose = '0000-00-00 00:00:00'");

	$db -> query("
		ALTER TABLE `{$sqlname}settings`
		CHANGE COLUMN `acs_view` `acs_view` VARCHAR(3) NULL DEFAULT 'on' AFTER `logo`,
		CHANGE COLUMN `complect_on` `complect_on` VARCHAR(3) NULL DEFAULT 'no' AFTER `acs_view`,
		CHANGE COLUMN `zayavka_on` `zayavka_on` VARCHAR(3) NULL DEFAULT 'no' AFTER `complect_on`,
		CHANGE COLUMN `contract_format` `contract_format` VARCHAR(255) NULL AFTER `zayavka_on`,
		CHANGE COLUMN `contract_num` `contract_num` INT(10) NULL AFTER `contract_format`,
		CHANGE COLUMN `inum` `inum` INT(10) NULL AFTER `contract_num`,
		CHANGE COLUMN `iformat` `iformat` VARCHAR(255) NULL AFTER `inum`,
		CHANGE COLUMN `akt_num` `akt_num` VARCHAR(20) NULL DEFAULT '0' AFTER `iformat`,
		CHANGE COLUMN `akt_step` `akt_step` INT(10) NULL AFTER `akt_num`,
		CHANGE COLUMN `api_key` `api_key` VARCHAR(255) NULL AFTER `akt_step`,
		CHANGE COLUMN `coordinator` `coordinator` INT(10) NULL AFTER `api_key`,
		CHANGE COLUMN `timezone` `timezone` VARCHAR(255) NULL DEFAULT 'Asia/Yekaterinburg' COMMENT 'Временная зона' AFTER `coordinator`,
		CHANGE COLUMN `ivc` `ivc` VARCHAR(255) NULL AFTER `timezone`,
		CHANGE COLUMN `dFormat` `dFormat` VARCHAR(255) NULL AFTER `ivc`,
		CHANGE COLUMN `dNum` `dNum` VARCHAR(255) NULL AFTER `dFormat`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}mycomps_recv`
		CHANGE COLUMN `title` `title` TEXT NULL COMMENT 'назваине р.с' AFTER `cid`,
		CHANGE COLUMN `rs` `rs` VARCHAR(50) NULL COMMENT 'р.с' AFTER `title`,
		CHANGE COLUMN `bankr` `bankr` TEXT NULL COMMENT 'бик, кур. счет и название банка' AFTER `rs`,
		CHANGE COLUMN `tip` `tip` VARCHAR(6) NULL DEFAULT 'bank' COMMENT 'bank-kassa' AFTER `bankr`,
		CHANGE COLUMN `ostatok` `ostatok` DOUBLE(20,2) NULL COMMENT 'остаток средств' AFTER `tip`,
		CHANGE COLUMN `bloc` `bloc` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'заблокирован или нет счет' AFTER `ostatok`,
		CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL DEFAULT 'no' COMMENT 'использутся по умолчанию или нет' AFTER `bloc`,
		CHANGE COLUMN `ndsDefault` `ndsDefault` VARCHAR(5) NULL DEFAULT '0' COMMENT 'размер ндс по умолчанию' AFTER `isDefault`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}mycomps`
		CHANGE COLUMN `name_ur` `name_ur` TEXT NULL COMMENT 'полное наименование' AFTER `id`,
		CHANGE COLUMN `name_shot` `name_shot` TEXT NULL COMMENT 'сокращенное наименование' AFTER `name_ur`,
		CHANGE COLUMN `address_yur` `address_yur` TEXT NULL COMMENT 'юридические адрес' AFTER `name_shot`,
		CHANGE COLUMN `address_post` `address_post` TEXT NULL COMMENT 'почтовый адрес' AFTER `address_yur`,
		CHANGE COLUMN `dir_name` `dir_name` VARCHAR(255) NULL COMMENT 'в лице руководителя' AFTER `address_post`,
		CHANGE COLUMN `dir_signature` `dir_signature` VARCHAR(255) NULL COMMENT 'подпись руководителя' AFTER `dir_name`,
		CHANGE COLUMN `dir_status` `dir_status` TEXT NULL COMMENT 'должность руководителя' AFTER `dir_signature`,
		CHANGE COLUMN `dir_osnovanie` `dir_osnovanie` TEXT NULL COMMENT 'действующего на основаии' AFTER `dir_status`,
		CHANGE COLUMN `innkpp` `innkpp` VARCHAR(255) NULL COMMENT 'инн-кпп' AFTER `dir_osnovanie`,
		CHANGE COLUMN `okog` `okog` VARCHAR(255) NULL COMMENT 'окпо-огрн' AFTER `innkpp`,
		CHANGE COLUMN `stamp` `stamp` VARCHAR(255) NULL COMMENT 'файл с факсимилией' AFTER `okog`,
		CHANGE COLUMN `logo` `logo` VARCHAR(255) NULL COMMENT 'файл с логотипом' AFTER `stamp`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}comments_subscribe`
		CHANGE COLUMN `idcomment` `idcomment` INT(10) NULL AFTER `id`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL AFTER `idcomment`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}contract_temp`
		CHANGE COLUMN `typeid` `typeid` INT(10) NULL AFTER `id`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL AFTER `typeid`,
		CHANGE COLUMN `file` `file` VARCHAR(255) NULL AFTER `title`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}contract_type`
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL AFTER `id`,
		CHANGE COLUMN `type` `type` VARCHAR(255) NULL AFTER `title`,
		CHANGE COLUMN `role` `role` TEXT NULL AFTER `type`,
		CHANGE COLUMN `users` `users` TEXT NULL AFTER `role`,
		CHANGE COLUMN `num` `num` INT(10) NULL AFTER `users`,
		CHANGE COLUMN `format` `format` VARCHAR(255) NULL AFTER `num`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}budjet`
		CHANGE COLUMN `cat` `cat` INT(10) NULL COMMENT 'категория записи, ссылается на id в таблице _budjet_cat' AFTER `id`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название расхода-дохода' AFTER `cat`,
		CHANGE COLUMN `des` `des` TEXT NULL COMMENT 'описание' AFTER `title`,
		CHANGE COLUMN `year` `year` INT(10) NULL COMMENT 'год' AFTER `des`,
		CHANGE COLUMN `mon` `mon` INT(10) NULL COMMENT 'месяц' AFTER `year`,
		CHANGE COLUMN `summa` `summa` DOUBLE(20,2) NULL COMMENT 'сумма' AFTER `mon`,
		CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() AFTER `summa`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'id пользователя _user.iduser' AFTER `datum`,
		CHANGE COLUMN `do` `do` VARCHAR(3) NULL COMMENT 'признак того, что расход проведен' AFTER `iduser`,
		CHANGE COLUMN `rs` `rs` VARCHAR(20) NULL COMMENT 'id расчетного счета из таблицы _mycomps_recv.id' AFTER `do`,
		CHANGE COLUMN `rs2` `rs2` VARCHAR(20) NULL COMMENT 'id расчетного счета (используется при перемещении средств)' AFTER `rs`,
		CHANGE COLUMN `fid` `fid` TEXT NULL COMMENT 'id файлов из таблицы _files.fid разделенного запятой' AFTER `rs2`,
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'id сделки из таблицы _dogovor.did' AFTER `fid`,
		CHANGE COLUMN `conid` `conid` INT(10) NULL COMMENT '_clientcat.clid для поставщиков' AFTER `did`,
		CHANGE COLUMN `partid` `partid` INT(10) NULL COMMENT '_clientcat.clid для партнеров' AFTER `conid`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}budjet_cat`
		CHANGE COLUMN `subid` `subid` INT(10) NULL COMMENT 'ид основной записи budjet_cat.id' AFTER `id`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название' AFTER `subid`,
		CHANGE COLUMN `tip` `tip` VARCHAR(10) NULL COMMENT 'тип (расход-доход)' AFTER `title`
	");

	$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}callcenter'" );
	if ( (int)$da[0] > 0 ) {

		$db -> query( "
			ALTER TABLE `{$sqlname}callcenter`
			CHANGE COLUMN `gid` `gid` INT(10) NULL COMMENT 'Ссылка на группу (group.id)' AFTER `id`,
			CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'Дата создания' AFTER `gid`,
			CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'Название задания' AFTER `datum`,
			CHANGE COLUMN `content` `content` VARCHAR(600) NULL COMMENT 'Описание задания' AFTER `title`,
			CHANGE COLUMN `status` `status` CHAR(10) NULL DEFAULT 'active' COMMENT 'Текущий статус' AFTER `dend`,
			CHANGE COLUMN `iduser` `iduser` VARCHAR(255) NULL COMMENT 'id оператора (user.iduser)' AFTER `status`,
			CHANGE COLUMN `script` `script` VARCHAR(100) NULL COMMENT 'id скрипта' AFTER `iduser`,
			CHANGE COLUMN `scriptTitle` `scriptTitle` VARCHAR(100) NULL COMMENT 'Название Скрипта' AFTER `script`,
			CHANGE COLUMN `Method` `Method` CHAR(10) NULL DEFAULT 'randome' COMMENT 'метод назначения заданий' AFTER `operators`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}callcenter_list`
			CHANGE COLUMN `task` `task` INT(10) NOT NULL DEFAULT '0' COMMENT 'id задания' AFTER `id`,
			CHANGE COLUMN `phone` `phone` VARCHAR(15) NULL COMMENT 'Номер телефона' AFTER `pid`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'id оператора (user.iduser)' AFTER `phone`,
			CHANGE COLUMN `userResult` `userResult` INT(10) NULL COMMENT 'id сотрудника для дальнейшей работы' AFTER `iduser`,
			CHANGE COLUMN `taskUser` `taskUser` INT(10) NULL COMMENT 'id сотрудника, которому назначено напоминание' AFTER `userResult`,
			CHANGE COLUMN `isdo` `isdo` CHAR(5) NULL COMMENT 'статус' AFTER `taskUser`,
			CHANGE COLUMN `rezult` `rezult` VARCHAR(10) NULL COMMENT 'результат обработки' AFTER `datum_do`,
			CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'запасное поле' AFTER `rezult`
		" );

	}

	$db -> query("
		ALTER TABLE `{$sqlname}capacity_client`
		CHANGE COLUMN `capid` `capid` INT(10) NULL COMMENT 'не используется' AFTER `id`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clid в таблице _clientcat.clid' AFTER `capid`,
		CHANGE COLUMN `direction` `direction` INT(10) NULL COMMENT 'направление деятельности из таблицы _direction.id' AFTER `clid`,
		CHANGE COLUMN `year` `year` INT(10) NULL COMMENT 'план на какой год' AFTER `direction`,
		CHANGE COLUMN `mon` `mon` INT(10) NULL COMMENT 'план на какой месяц' AFTER `year`,
		CHANGE COLUMN `sumplan` `sumplan` DOUBLE(20,2) NULL COMMENT 'план продаж в указанном периоде данному клиенту по данному направлению' AFTER `mon`,
		CHANGE COLUMN `sumfact` `sumfact` DOUBLE(20,2) NULL COMMENT 'факт продаж, при закрытии сделки суммируются' AFTER `sumplan`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}comments`
		CHANGE COLUMN `idparent` `idparent` INT(10) NULL DEFAULT '0' COMMENT 'comments.id -- ссылка на тему обсуждения' AFTER `id`,
		CHANGE COLUMN `mid` `mid` INT(10) NULL COMMENT 'DEPRECATED' AFTER `idparent`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clientcat.clid' AFTER `datum`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL COMMENT 'personcat.pid' AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'dogovor.did' AFTER `pid`,
		CHANGE COLUMN `prid` `prid` INT(10) NULL COMMENT 'price.n_id' AFTER `did`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'user.iduser' AFTER `project`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'заголовок' AFTER `iduser`,
		CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'текст' AFTER `title`,
		CHANGE COLUMN `fid` `fid` TEXT NULL COMMENT '_files.fid в виде списка с разделением ;' AFTER `content`,
		CHANGE COLUMN `lastCommentDate` `lastCommentDate` DATETIME NULL COMMENT 'дата последнего коментария' AFTER `fid`,
		CHANGE COLUMN `isClose` `isClose` VARCHAR(10) NULL DEFAULT 'no' COMMENT 'закрыто или открыты обсуждение' AFTER `lastCommentDate`,
		CHANGE COLUMN `dateClose` `dateClose` DATETIME NULL COMMENT 'дата закрытия обсуждения' AFTER `isClose`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}comments_subscribe`
		CHANGE COLUMN `idcomment` `idcomment` INT(10) NULL COMMENT 'тема обсуждения _comments.id' AFTER `id`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'пользователь _user.iduser' AFTER `idcomment`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}complect`
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'сделка _dogovor.id' AFTER `id`,
		CHANGE COLUMN `ccid` `ccid` INT(10) NULL COMMENT 'тип контрольной точки _complect_cat.ccid' AFTER `did`,
		CHANGE COLUMN `doit` `doit` VARCHAR(5) NOT NULL DEFAULT 'no' COMMENT 'признак выполнения' AFTER `data_fact`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'пользователь, выполнивший КТ _user.iduser' AFTER `doit`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}complect_cat`
		CHANGE COLUMN `title` `title` VARCHAR(200) NULL COMMENT 'название контрольной точки' AFTER `ccid`,
		CHANGE COLUMN `corder` `corder` INT(10) NULL COMMENT 'порядок вывода' AFTER `title`,
		CHANGE COLUMN `dstep` `dstep` INT(10) NULL COMMENT 'привязка к этапу сделки _dogcategory.idcategory' AFTER `corder`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}contract_type`
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название документа' AFTER `id`,
		CHANGE COLUMN `type` `type` VARCHAR(255) NULL COMMENT 'внутренний тип get_akt, get_aktper, get_dogovor, invoice' AFTER `title`,
		CHANGE COLUMN `role` `role` TEXT NULL COMMENT 'список ролей, которым доступно изменение' AFTER `type`,
		CHANGE COLUMN `users` `users` TEXT NULL COMMENT 'список пользователей, которые могут добавлять такие документы -- user.iduser с разделением ,' AFTER `role`,
		CHANGE COLUMN `num` `num` INT(10) NULL COMMENT 'счетчик нумерации' AFTER `users`,
		CHANGE COLUMN `format` `format` VARCHAR(255) NULL COMMENT 'шаблон формата номера' AFTER `num`
	");

	$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}corpuniver_coursebyusers'" );
	if ( (int)$da[0] > 0 ) {

		$db -> query( "
			ALTER TABLE `{$sqlname}corpuniver_coursebyusers`
			CHANGE COLUMN `idcourse` `idcourse` INT(10) NULL DEFAULT '0' COMMENT 'id курса' AFTER `datum_end`,
			CHANGE COLUMN `idlecture` `idlecture` INT(10) NULL DEFAULT '0' COMMENT 'id лекции' AFTER `idcourse`,
			CHANGE COLUMN `idmaterial` `idmaterial` INT(10) NULL DEFAULT '0' COMMENT 'id материала' AFTER `idlecture`,
			CHANGE COLUMN `idtask` `idtask` INT(10) NULL DEFAULT '0' COMMENT 'id теста' AFTER `idmaterial`,
			CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT '0' COMMENT 'id сотрудника' AFTER `idtask`
		" );

	}

	$db -> query("
		ALTER TABLE `{$sqlname}dogtips`
		CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL COMMENT 'признак дефолтности' AFTER `title`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}dostup`
		CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'Запись клиента _clientcat.clid' AFTER `id`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL COMMENT 'Запись контакта _personcat.pid' AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'Запись сделки _dogovor.did' AFTER `pid`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'Сотрудник, которому дан доступ _user.iduser' AFTER `did`,
		CHANGE COLUMN `subscribe` `subscribe` VARCHAR(3) NULL DEFAULT 'off' COMMENT 'отправлять уведомления (on-off) по сделкам' AFTER `iduser`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}entry`
		CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'Клиент _clientcat.clid' AFTER `uid`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL COMMENT 'Контакт _personcat.pid' AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'Созданная сделка _dogovor.did' AFTER `pid`,
		CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'дата создания' AFTER `did`,
		CHANGE COLUMN `datum_do` `datum_do` TIMESTAMP NULL DEFAULT NULL COMMENT 'дата обработки обращения' AFTER `datum`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'ответственный user.iduser' AFTER `datum_do`,
		CHANGE COLUMN `autor` `autor` INT(10) NULL COMMENT 'автор user.iduser' AFTER `iduser`,
		CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'коментарий' AFTER `autor`,
		CHANGE COLUMN `status` `status` INT(10) NULL DEFAULT '0' COMMENT 'Статус обработки: 0-новое, 1-обработано, 2 - отмена' AFTER `content`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}entry_poz`
		CHANGE COLUMN `ide` `ide` INT(10) NULL COMMENT 'Обращение _entry.ide' AFTER `idp`,
		CHANGE COLUMN `prid` `prid` INT(10) NULL COMMENT 'Связь с прайсом _price.n_id, не обязательный' AFTER `ide`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название позиции' AFTER `prid`,
		CHANGE COLUMN `kol` `kol` INT(10) NULL COMMENT 'количество' AFTER `title`,
		CHANGE COLUMN `identity` `identity` INT(10) NOT NULL DEFAULT '1' AFTER `price`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}field`
		CHANGE COLUMN `fld_stat` `fld_stat` VARCHAR(10) NULL COMMENT 'можно ли поле выключить' AFTER `fld_order`,
		CHANGE COLUMN `fld_temp` `fld_temp` VARCHAR(255) NULL COMMENT 'тип поля - input, select...' AFTER `fld_stat`,
		CHANGE COLUMN `fld_var` `fld_var` TEXT NULL COMMENT 'вариант готовых ответов' AFTER `fld_temp`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}kb`
		CHANGE COLUMN `subid` `subid` INT(10) NULL COMMENT 'ссылка на головную папку' AFTER `idcat`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название папки' AFTER `subid`,
		CHANGE COLUMN `share` `share` VARCHAR(5) NULL COMMENT 'DEPRECATED' AFTER `title`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}kbtags`
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL COMMENT 'тэг' AFTER `id`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}knowledgebase`
		CHANGE COLUMN `idcat` `idcat` INT(10) NULL COMMENT 'Папка _kb.idcat' AFTER `id`,
		CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() COMMENT 'дата публикации' AFTER `idcat`,
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название статьи' AFTER `datum`,
		CHANGE COLUMN `content` `content` MEDIUMTEXT NULL COMMENT 'содержание статьи' AFTER `title`,
		CHANGE COLUMN `count` `count` INT(10) NULL COMMENT 'число просмотров' AFTER `content`,
		CHANGE COLUMN `active` `active` VARCHAR(5) NULL COMMENT 'признак черновика' AFTER `count`,
		CHANGE COLUMN `keywords` `keywords` TEXT NULL COMMENT 'тэги' AFTER `pindate`,
		CHANGE COLUMN `author` `author` INT(10) NULL COMMENT 'Автор _user.iduser' AFTER `keywords`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}loyal_cat`
		CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NULL COMMENT 'признак дефолтности' AFTER `color`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}modules`
		CHANGE COLUMN `title` `title` VARCHAR(100) NULL COMMENT 'название модуля' AFTER `id`,
		CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'какие сделаны настройки модуля' AFTER `title`,
		CHANGE COLUMN `mpath` `mpath` VARCHAR(255) NULL  AFTER `content`,
		CHANGE COLUMN `icon` `icon` VARCHAR(20) NOT NULL DEFAULT 'icon-publish' COMMENT 'иконка из фонтелло для меню' AFTER `mpath`,
		CHANGE COLUMN `activateDate` `activateDate` VARCHAR(20) NULL AFTER `active`,
		CHANGE COLUMN `secret` `secret` VARCHAR(255) NULL AFTER `activateDate`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}price`
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название позиции' AFTER `artikul`,
		CHANGE COLUMN `descr` `descr` TEXT NULL COMMENT 'описание' AFTER `title`,
		CHANGE COLUMN `edizm` `edizm` VARCHAR(10) NULL AFTER `descr`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}profile`
		CHANGE COLUMN `id` `id` INT(10) NULL COMMENT 'profile_cat.id' AFTER `pfid`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clientcat.clid' AFTER `id`,
		CHANGE COLUMN `value` `value` VARCHAR(255) NULL COMMENT 'начение поля' AFTER `clid`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}profile_cat`
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL COMMENT 'название поля' AFTER `id`,
		CHANGE COLUMN `tip` `tip` VARCHAR(10) NULL COMMENT 'тип вывода поля' AFTER `name`,
		CHANGE COLUMN `value` `value` TEXT NULL COMMENT 'значение поля' AFTER `tip`,
		CHANGE COLUMN `ord` `ord` INT(10) NULL COMMENT 'порядок вывода' AFTER `value`,
		CHANGE COLUMN `pole` `pole` VARCHAR(10) NULL COMMENT 'название поля для идентификации' AFTER `ord`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}relations`
		CHANGE COLUMN `title` `title` VARCHAR(255) NULL COMMENT 'название' AFTER `id`,
		CHANGE COLUMN `color` `color` VARCHAR(10) NULL COMMENT 'цвет' AFTER `title`,
		CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NULL COMMENT 'признак по умолчанию' AFTER `color`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}reports`
		CHANGE COLUMN `title` `title` VARCHAR(100) NULL COMMENT 'название отчета' AFTER `rid`,
		CHANGE COLUMN `file` `file` VARCHAR(100) NULL COMMENT 'файл отчета' AFTER `title`,
		CHANGE COLUMN `ron` `ron` VARCHAR(5) NULL COMMENT 'активность отчета' AFTER `file`,
		CHANGE COLUMN `category` `category` VARCHAR(20) NULL COMMENT 'раздел' AFTER `ron`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}search`
		CHANGE COLUMN `tip` `tip` VARCHAR(100) NULL COMMENT 'Привязка к person, client, dog' AFTER `seid`,
		CHANGE COLUMN `title` `title` VARCHAR(250) NULL COMMENT 'Название представления' AFTER `tip`,
		CHANGE COLUMN `squery` `squery` TEXT NULL COMMENT 'Поисковой запрос' AFTER `title`,
		CHANGE COLUMN `sorder` `sorder` INT(10) NULL COMMENT 'Порядок вывода' AFTER `squery`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'user.iduser' AFTER `sorder`,
		CHANGE COLUMN `share` `share` VARCHAR(5) NULL COMMENT 'Общий доступ' AFTER `iduser`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}sip`
		CHANGE COLUMN `active` `active` VARCHAR(3) NOT NULL DEFAULT 'no' AFTER `id`,
		CHANGE COLUMN `tip` `tip` VARCHAR(20) NULL AFTER `active`,
		CHANGE COLUMN `sip_host` `sip_host` VARCHAR(255) NULL AFTER `tip`,
		CHANGE COLUMN `sip_port` `sip_port` INT(10) NULL AFTER `sip_host`,
		CHANGE COLUMN `sip_channel` `sip_channel` VARCHAR(30) NULL AFTER `sip_port`,
		CHANGE COLUMN `sip_context` `sip_context` VARCHAR(255) NULL AFTER `sip_channel`,
		CHANGE COLUMN `sip_user` `sip_user` VARCHAR(100) NULL AFTER `sip_context`,
		CHANGE COLUMN `sip_secret` `sip_secret` VARCHAR(200) NULL AFTER `sip_user`,
		CHANGE COLUMN `sip_numout` `sip_numout` VARCHAR(3) NULL AFTER `sip_secret`,
		CHANGE COLUMN `sip_pfchange` `sip_pfchange` VARCHAR(3) NULL AFTER `sip_numout`,
		CHANGE COLUMN `sip_path` `sip_path` VARCHAR(255) NULL AFTER `sip_pfchange`,
		CHANGE COLUMN `sip_cdr` `sip_cdr` VARCHAR(255) NULL AFTER `sip_path`,
		CHANGE COLUMN `sip_secure` `sip_secure` VARCHAR(5) NULL AFTER `sip_cdr`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}smtp`
		CHANGE COLUMN `active` `active` VARCHAR(3) NOT NULL DEFAULT 'no' AFTER `id`,
		CHANGE COLUMN `smtp_host` `smtp_host` VARCHAR(255) NULL AFTER `active`,
		CHANGE COLUMN `smtp_port` `smtp_port` INT(10) NULL AFTER `smtp_host`,
		CHANGE COLUMN `smtp_auth` `smtp_auth` VARCHAR(5) NULL AFTER `smtp_port`,
		CHANGE COLUMN `smtp_secure` `smtp_secure` VARCHAR(5) NULL AFTER `smtp_auth`,
		CHANGE COLUMN `smtp_user` `smtp_user` VARCHAR(100) NULL AFTER `smtp_secure`,
		CHANGE COLUMN `smtp_pass` `smtp_pass` VARCHAR(200) NULL AFTER `smtp_user`,
		CHANGE COLUMN `smtp_from` `smtp_from` VARCHAR(255) NULL AFTER `smtp_pass`,
		CHANGE COLUMN `smtp_protocol` `smtp_protocol` VARCHAR(5) NULL AFTER `smtp_from`,
		CHANGE COLUMN `tip` `tip` VARCHAR(10) NULL AFTER `smtp_protocol`,
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL AFTER `tip`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'id пользователя user.iduser' AFTER `name`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}steplog`
		CHANGE COLUMN `step` `step` INT(10) NULL COMMENT 'id этапа dogcategory.idcategory' AFTER `datum`,
		CHANGE COLUMN `did` `did` INT(10) NULL COMMENT 'id сделки dogovor.did' AFTER `step`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL COMMENT 'id пользователя user.iduser внес изменение' AFTER `did`,
		CHANGE COLUMN `identity` `identity` INT(10) NOT NULL DEFAULT '1' AFTER `iduser`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}tpl`
		CHANGE COLUMN `tip` `tip` VARCHAR(20) NULL COMMENT 'тип' AFTER `tid`,
		CHANGE COLUMN `name` `name` VARCHAR(255) NULL COMMENT 'название' AFTER `tip`,
		CHANGE COLUMN `content` `content` MEDIUMTEXT NULL COMMENT 'сообщение' AFTER `name`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}uids`
		CHANGE COLUMN `name` `name` VARCHAR(100) NULL COMMENT 'название параметра' AFTER `datum`,
		CHANGE COLUMN `value` `value` VARCHAR(100) NULL COMMENT 'знаение параметра' AFTER `name`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}user`
		CHANGE COLUMN `email` `email` TEXT NULL COMMENT 'Email' AFTER `otdel`,
		CHANGE COLUMN `office` `office` INT(10) NOT NULL DEFAULT '0' AFTER `territory`,
		CHANGE COLUMN `acs_analitics` `acs_analitics` VARCHAR(5) NULL COMMENT 'Доступ к отчетам' AFTER `bday`,
		CHANGE COLUMN `acs_maillist` `acs_maillist` VARCHAR(5) NULL COMMENT 'Доступ к рассылкам' AFTER `acs_analitics`,
		CHANGE COLUMN `acs_files` `acs_files` VARCHAR(5) NULL COMMENT 'Доступ к разделу Файлы' AFTER `acs_maillist`,
		CHANGE COLUMN `acs_price` `acs_price` VARCHAR(5) NULL COMMENT 'Доступ к разделу Прайс' AFTER `acs_files`,
		CHANGE COLUMN `acs_credit` `acs_credit` VARCHAR(5) NULL COMMENT 'Может ставить оплаты' AFTER `acs_price`,
		CHANGE COLUMN `acs_prava` `acs_prava` VARCHAR(5) NULL COMMENT 'Может просматривать чужие записи' AFTER `acs_credit`,
		CHANGE COLUMN `tzone` `tzone` VARCHAR(5) NULL COMMENT 'Временная зона' AFTER `acs_prava`,
		CHANGE COLUMN `viget_on` `viget_on` VARCHAR(500) NULL DEFAULT 'on;on;on;on;on;on;on;on;on;on;on' AFTER `tzone`,
		CHANGE COLUMN `viget_order` `viget_order` VARCHAR(500) NULL DEFAULT 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11' AFTER `viget_on`,
		CHANGE COLUMN `acs_import` `acs_import` VARCHAR(255) NULL COMMENT 'разные права' AFTER `isadmin`,
		CHANGE COLUMN `subscription` `subscription` TEXT NULL COMMENT 'подписки на email-уведомления' AFTER `CompEnd`,
		CHANGE COLUMN `avatar` `avatar` VARCHAR(100) NULL COMMENT 'аватар' AFTER `subscription`,
		CHANGE COLUMN `sole` `sole` VARCHAR(250) NULL AFTER `avatar`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}ymail_settings`
		CHANGE COLUMN `iduser` `iduser` INT(10) NOT NULL DEFAULT '0' COMMENT 'id пользователя user.iduser' AFTER `id`,
		CHANGE COLUMN `settings` `settings` TEXT NULL COMMENT 'настройки' AFTER `iduser`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}price`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `price_5`,
		CHANGE COLUMN `nds` `nds` DOUBLE(20,2) NOT NULL DEFAULT 0 COMMENT 'ндс' AFTER `pr_cat`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}steplog`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `id`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}price_cat`
		CHANGE COLUMN `sub` `sub` INT(10) NULL DEFAULT NULL COMMENT 'Головная категория - _price_cat.idcategory' AFTER `idcategory`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}file_cat`
		CHANGE COLUMN `subid` `subid` INT(10) NULL DEFAULT '0' COMMENT 'родительская папка idcategory' AFTER `idcategory`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}ymail_messages`
		CHANGE COLUMN `state` `state` VARCHAR(50) NULL DEFAULT 'unread' COMMENT 'deleted - удаленные, read - прочитанные, unread - не прочинанны' AFTER `priority`
	");

	$db -> query("
		ALTER TABLE `{$sqlname}file_cat`
		CHANGE COLUMN `subid` `subid` INT(10) NULL DEFAULT '0' AFTER `idcategory`,
		CHANGE COLUMN `title` `title` VARCHAR(250) NULL DEFAULT NULL AFTER `subid`,
		CHANGE COLUMN `shared` `shared` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'общая папка (yes)' AFTER `title`
	");

	$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}budjet_bank'" );
	if ( (int)$da[0] > 0 ) {

		$db -> query( "
			ALTER TABLE `{$sqlname}budjet_bank`
			CHANGE COLUMN `fromINN` `fromINN` VARCHAR(12) NULL DEFAULT NULL COMMENT 'инн плательщика' AFTER `fromRS`,
			CHANGE COLUMN `toINN` `toINN` VARCHAR(12) NULL DEFAULT NULL COMMENT 'инн получателя' AFTER `toRS`
		" );

	}
	else {

		$db -> query( "
			CREATE TABLE `{$sqlname}budjet_bank` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'метка времени',
				`number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'номер документа',
				`datum` DATE NULL DEFAULT NULL COMMENT 'дата проводки',
				`mon` VARCHAR(2) NULL DEFAULT NULL COMMENT 'месяц',
				`year` VARCHAR(4) NULL DEFAULT NULL COMMENT 'год',
				`tip` VARCHAR(10) NULL DEFAULT NULL COMMENT 'направление расхода - dohod, rashod',
				`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'название расхода',
				`content` TEXT NULL DEFAULT NULL COMMENT 'описание расхода',
				`rs` INT(10) NULL DEFAULT NULL COMMENT 'id расчетного счета',
				`from` TEXT NULL DEFAULT NULL COMMENT 'название плательщика',
				`fromRS` VARCHAR(20) NULL DEFAULT NULL COMMENT 'р.с. плательщика',
				`fromINN` VARCHAR(12) NULL DEFAULT NULL COMMENT 'инн плательщика',
				`to` TEXT NULL DEFAULT NULL COMMENT 'название получателя',
				`toRS` VARCHAR(20) NULL DEFAULT NULL COMMENT 'р.с. получателя',
				`toINN` VARCHAR(12) NULL DEFAULT NULL COMMENT 'инн получателя',
				`summa` FLOAT(20,2) NULL DEFAULT NULL COMMENT 'сумма расхода',
				`clid` INT(10) NULL DEFAULT NULL COMMENT 'id связанного клиента',
				`bid` INT(10) NULL DEFAULT NULL COMMENT 'id связанной записи в бюджете',
				`category` INT(10) NULL DEFAULT NULL COMMENT 'id статьи расхода',
				`identity` INT(10) NULL DEFAULT '1',
				PRIMARY KEY (`id`) USING BTREE
			)
			COMMENT='Журнал банковской выписки'
			ENGINE=InnoDB
		" );

	}

	$db -> query("
		ALTER TABLE `{$sqlname}contract_statuslog`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `id`;
	");

	$db -> query("
		ALTER TABLE `{$sqlname}complect_cat`
		CHANGE COLUMN `title` `title` VARCHAR(200) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `ccid`,
		CHANGE COLUMN `corder` `corder` INT(10) NULL DEFAULT NULL AFTER `title`,
		CHANGE COLUMN `dstep` `dstep` INT(10) NULL DEFAULT NULL AFTER `corder`;
	");

	$dap = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects'" );
	if ( (int)$dap > 0 ) {

		$dap = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_templates'" );
		if ( (int)$dap == 0 ) {

			$db -> query("
				CREATE TABLE `{$sqlname}projects_templates` (
					`id` INT(10) NOT NULL AUTO_INCREMENT,
					`title` VARCHAR(255) NULL DEFAULT 'untitled' COMMENT 'Название шаблона',
					`autor` INT(10) NULL DEFAULT NULL COMMENT 'iduser автора',
					`datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
					`content` TEXT NULL DEFAULT NULL COMMENT 'Содержание работ в json',
					`state` INT(10) NULL DEFAULT '1' COMMENT 'Статус: 1 - активен, 0 - не активен',
					`identity` INT(10) NULL DEFAULT '1',
					PRIMARY KEY (`id`) USING BTREE
				)
				COMMENT='Шаблоны проектов'
				ENGINE=InnoDB
			");

		}
		
	}

	$db -> query("SET FOREIGN_KEY_CHECKS=0;");

	$db -> query("SET sql_mode='NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES';");
	$db -> query("UPDATE `{$sqlname}budjet` SET datum = NULL WHERE datum = '0000-00-00 00:00:00';");

	$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}field LIKE 'fld_sub'" );
	if ( $fi['Field'] == '' ) {

		$db -> query("ALTER TABLE `{$sqlname}field` ADD COLUMN `fld_sub` VARCHAR(10) NULL DEFAULT NULL COMMENT 'доп.разделение для карточек клиентов - клиент, поставщик, партнер..' AFTER `fld_var`");

	}

	$fi = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}budjet LIKE 'date_plan'" );
	if ( $fi['Field'] == '' ) {

		$db -> query("ALTER TABLE `{$sqlname}budjet`
				ADD COLUMN `date_plan` DATE NULL DEFAULT NULL COMMENT 'плановая дата' AFTER `partid`,
				ADD COLUMN `invoice` VARCHAR(255) NULL DEFAULT NULL COMMENT 'номер счета' AFTER `date_plan`,
				ADD COLUMN `invoice_date` DATE NULL DEFAULT NULL COMMENT 'дата счета' AFTER `invoice`,
				ADD COLUMN `invoice_paydate` DATE NULL DEFAULT NULL COMMENT 'дата оплаты счета' AFTER `invoice_date`
			");

	}

	//создадим таблицу для шаблонов проектов, если надо
	$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_templates'" );
	if ( $da == 0 ) {

		$db -> query( "CREATE TABLE `{$sqlname}projects_templates` (
				    `id`       INT(10)      NOT NULL AUTO_INCREMENT,
				    `title`    VARCHAR(255) NULL DEFAULT 'untitled' COMMENT 'Название шаблона',
				    `autor`    INT(10)      NULL DEFAULT NULL COMMENT 'iduser автора',
				    `datum`    TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,
				    `content`  TEXT         NULL DEFAULT NULL COMMENT 'Содержание работ в json',
				    `state`    INT(10)      NULL DEFAULT '1' COMMENT 'Статус: 1 - активен, 0 - не активен',
				    `identity` INT(10)      NULL DEFAULT '1',
				    PRIMARY KEY (`id`) USING BTREE
				)
				COMMENT ='Шаблоны проектов'
				ENGINE = InnoDB
			" );

	}

	//создадим таблицу для лога статусов
	$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}budjetlog'" );
	if ( $da == 0 ) {

		$db -> query( "CREATE TABLE `{$sqlname}budjetlog` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
				`status` VARCHAR(10) NULL DEFAULT NULL COMMENT 'статус расхода',
				`bjid` INT(10) NULL DEFAULT NULL COMMENT 'id расхода',
				`iduser` INT(10) NULL DEFAULT NULL COMMENT 'id пользователя user.iduser внес изменение',
				`comment` VARCHAR(255) NULL DEFAULT NULL COMMENT 'комментарий',
				`identity` INT(10) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`) USING BTREE,
				INDEX `status` (`status`) USING BTREE,
				INDEX `bjid` (`bjid`) USING BTREE
			)
			COMMENT='Лог изменений статуса расходов'
			ENGINE=InnoDB" );

	}

	$message = 'Необходимые изменения в БД внесены';
	
	if ( PHP_SAPI === 'cli' ) {

		print "Необходимые изменения в БД внесены\n";

		$db -> query("SET FOREIGN_KEY_CHECKS=1;");
		
		print "Finished at ".modifyDatetime("", [
				//"hours"  => $bdtimezone,
				"format" => "d.m H:i"
			])."\n";
		
		$time_finish = current_datumtime();
		print "Total time: ".untag(diffDateTime2($time_start, $time_finish))."\n";
		
		$memory = FileSize2Human(memory_get_peak_usage(true));
		print "Memory usage: $memory\n";
		
		exit();
		
	}

}
elseif ( PHP_SAPI === 'cli' ) {

	$db -> query("SET FOREIGN_KEY_CHECKS=1;");
	
	print "Finished at ".modifyDatetime("", [
			//"hours"  => $bdtimezone,
			"format" => "d.m H:i"
		])."\n";
	
	$time_finish = current_datumtime();
	print "Total time: ".untag(diffDateTime2($time_start, $time_finish))."\n";
	
	$memory = FileSize2Human(memory_get_peak_usage(true));
	print "Memory usage: $memory\n";
	
	exit();
	
}

$db -> query("SET FOREIGN_KEY_CHECKS=1;");

unlink( $root."/cash/".$fpath."settings.all.json" );
unlink( $root."/cash/".$fpath."settings.ymail.".$iduser1.".json" );

?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Менеджер установки</title>
	<style>
		<!--
		@import url("/assets/css/app.css");
		@import url("/assets/css/fontello.css");

		body {
			padding   : 10px;
			font-size : 12px;
		}

		input, select {
			width : 100%;
		}

		#license {
			overflow    : auto;
			background  : #FFF;
			font-size   : 14px;
			line-height : 18px;
			max-height  : 300px;
			padding     : 20px;
			margin      : 20px;
		}

		.blok {
			overflow    : auto;
			background  : #FFF;
			font-size   : 14px;
			line-height : 18px;
			padding     : 20px;
			margin      : 20px;
		}

		legend {
			font-size   : 18px;
			font-weight : 700;
			padding     : 0 10px;
		}

		fieldset {
			width  : 100%;
			margin : 0 auto;
		}

		.main_div {
			width   : 80%;
			padding : 5px 40px;
			margin  : 0 auto;
		}

		.button, a.button, .button a {
			padding : 10px;
		}

		.hidden {
			display : none;
		}

		-->
	</style>
	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script src="/assets/js/jquery/jquery.form.js"></script>
	<script src="/assets/js/app.extended.js"></script>
	<style>
		code{
			font-family : monospace, serif;
			font-size: 1.0rem;
		}
	</style>
</head>
<body>
<div class="main_div div-center"><img src="/assets/images/logo.png" height="40"></div>
<div class="main_div div-center"><h1>Менеджер обновления</h1></div>
<?php
if ( !$step ) {

	$ver = getVersion();
	?>
	<div class="main_div">
		<fieldset>
			<!--<legend>Обновление до версии <?= $lastVer ?></legend>-->
			<div class="blok infodiv div-center">
				Менеджер обновлений <?= $productInfo['name'] ?> может обновить текущую Базу данных с версии
				<b class="blue"><?= $seria[0] ?></b> до версии <b class="red bigtxt"><?= $lastVer ?></b>.<br><br>
				<h1>Текущая версия - <b class="red"><?= getVersion() ?></b></h1>

				<?php
				if(in_array( $ver, $seria ) && $currentVersion != $lastVer){

					print '
					<div class="text-center">
						<div class="wp60 warning dotted" style="display:inline-grid">
							<div class="Bold red uppercase fs-14">Внимание!</div>
							<div class="mt10">Крайне рекомендуется выполнять обновление через терминал, если размер базы данных значителен, т.к. вносятся изменения в её структуру и может потребоваться больше времени, чем разрешено веб-сервером.</div>
							
							<div class="mt10 Bold">Выполните команду в консоли:</div>
							
							<div class="mt10 viewdiv">
								<code class="black">php '.$root.'/_install/update.php</code>
							</div>
							
						</div>
					</div>
					';

				}
				?>

			</div>
			<?php
			if ( $ver < $seria[0] || !in_array( $ver, $seria ) ) {

				print '
				<div class="blok infodiv div-center">
					<DIV class="warning">
						<h3><i class="icon-attention icon-5x red pull-left"></i><b class="red">Внимание!</b> Данный скрипт не сможет обновить Вашу версию '.$productInfo['name'].'.</h3><br>
						Для продолжения обновления требуется обновить Вашу версию до 2022.3 <a href="update.older.php" class="button redbtn"><b>этой версией</b></a> скрипта.<br>После обновления запустите текущий скрипт еще раз.<br><br>
					</DIV>
				</div>';

			}
			elseif ( $ver == $lastVer ) {

				$php = getPhpInfo();
				$os = PHP_OS;

				$phpPath = $os == 'Linux' && !empty($php['bin']) ? $php['bin'] : "php";

				print '
				<div class="blok infodiv div-center">
					<DIV>
						<div class="green"><i class="icon-ok-circled icon-5x green"></i><h1>Обновление не требуется.</h1></div>
						<div class="Bold blue">Но, вы можете запустить установку, чтобы поправить структуру БД по необходимости</div>
						
						<div class="space-20"></div>
						
						<div class="text-center mt20">
							<div class="infodiv inline dotted p10" style="display:inline-grid">
								
								<div class="Bold">А лучше выполните команду в консоли:</div>
								
								<div class="mt10 bgwhite p10">
									<code class="black">'.$phpPath.' '.$root.'/_install/update.php</code>
								</div>
								
							</div>
						</div>
						
						<div class="main_div div-center mt20">
							<A href="/" class="button"><b>К рабочему столу</b></A>
							<A href="update.php?step=1" class="button greenbtn"><b>Продолжить установку</b></A>
						</div>
						<br><br>
					</DIV>
				</div>';
			}
			else {
				?>
				<br>
				<div class="main_div div-center">
					<A href="update.php?step=1" class="button">Продолжить установку</A>
				</div>
			<?php
			}
			?>
		</fieldset>
	</div>
	<?php
}
if ( $step == 1 ) {
	?>
	<div class="main_div">
		<fieldset>
			<legend>Результат</legend>
			<div class="blok infodiv text-center">
				<h1>Текущая версия - <b class="red"><?= getVersion() ?></b></h1>
				<div class="green">
					<?= $message ?>
				</div>
			</div>
			<?php
			if ( getVersion() == $lastVer ) {

				print '
				<div class="blok infodiv div-center">
					<DIV class="green mb20">
						<br><i class="icon-ok-circled icon-5x green"></i><h1>Обновление не требуется.</h1><br>
						<div class="main_div div-center">
							<A href="/" class="button"><b>К рабочему столу</b></A>
						</div>
					</DIV>
				</div>';

			}
			?>
		</fieldset>
	</div>
<?php
}
?>
</body>
</html>