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

//error_reporting( E_ALL );
ini_set('display_errors', 1);

$root = dirname(__DIR__);


ini_set( 'log_errors', 'On' );
ini_set( 'error_log', $root.'/cash/salesman_error.log' );

require_once $root."/inc/licloader.php";
require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";

$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modules'" );
if ( $da[0] == 0 ) {

	$db -> query( "
		CREATE TABLE `{$sqlname}modules` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`title` VARCHAR(100) NOT NULL COMMENT 'название модуля' COLLATE 'utf8_general_ci',
			`content` TEXT(65535) NOT NULL COMMENT 'какие сделаны настройки модуля' COLLATE 'utf8_general_ci',
			`mpath` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci',
			`icon` VARCHAR(20) NOT NULL DEFAULT 'icon-publish' COMMENT 'иконка из фонтелло для меню' COLLATE 'utf8_general_ci',
			`active` VARCHAR(5) NOT NULL DEFAULT 'on' COMMENT 'включен-отключен' COLLATE 'utf8_general_ci',
			`activateDate` VARCHAR(20) NOT NULL COLLATE 'utf8_general_ci',
			`secret` VARCHAR(255) NOT NULL COLLATE 'utf8_general_ci',
			`identity` INT(20) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`) USING BTREE
		)
		COMMENT='Подключенные модули'
		COLLATE='utf8_general_ci'
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
if ( !in_array( getVersion(), [
	'7.75',
	'2017.3'
] ) ) {

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
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
$lastVer = '2022.3';
$step    = (int)$_REQUEST['step'];
$currentVersion = getVersion();

$seria = [
	'7.6',
	'7.65',
	'7.67',
	'7.70',
	'7.72',
	'7.73',
	'7.74',
	'7.75',
	'7.77',
	'7.78',
	'8.0',
	'8.01',
	'8.05',
	'8.10',
	'8.11',
	'8.12',
	'8.13',
	'8.14',
	'8.20',
	'8.21',
	'8.30',
	'8.35',
	'2016.01',
	'2016.10',
	'2016.15',
	'2016.16',
	'2016.17',
	'2016.20',
	'2016.21',
	'2016.25',
	'2017.00',
	'2017.3',
	'2017.6',
	'2017.9',
	'2017.10',
	'2018.1',
	'2018.3',
	'2018.6',
	'2018.9',
	'2019.1',
	'2019.2',
	'2019.3',
	'2019.4',
	'2020.1',
	'2020.3',
	'2021.4',
	'2022.2',
	'2022.3'
];

if ( $step == 1 || PHP_SAPI == 'cli' ) {

	$time_start = current_datumtime();

	if(PHP_SAPI == 'cli') {

		print "Start at ".modifyDatetime( "", [
				//"hours"  => $bdtimezone,
				"format" => "d.m H:i"
			] )."\n";

	}

	if ( getVersion() == '7.6' ) {

		$db -> query( "ALTER TABLE `{$sqlname}ver` CHANGE `current` `current` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
		$db -> query( "ALTER TABLE `{$sqlname}budjet` ADD `rs2` VARCHAR( 20 ) NOT NULL AFTER `rs`" );
		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.65alfa1')" );
		$db -> query( "ALTER TABLE `{$sqlname}user` ADD `acs_plan` VARCHAR( 60 ) NOT NULL DEFAULT 'on'" );

		//сотрудники, которым планы не будем включать
		$exclude = [
			"Администратор",
			"Руководитель с доступом",
			"Поддержка продаж"
		];
		//переберем сотрудников
		$result = $db -> query( "SELECT * FROM {$sqlname}user ORDER BY iduser" );
		while ($data = $db -> fetch( $result )) {

			if ( !in_array( $data['tip'], $exclude ) )
				$plan = 'on';
			else $plan = 'off';

			$db -> query( "update {$sqlname}user set acs_plan = '".$plan."' where iduser = '".$data['iduser']."'" );
		}

		$db -> query( "ALTER TABLE `{$sqlname}speca` ADD `dop` INT( 10 ) NOT NULL DEFAULT '1'" );
		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.65')" );

		$currentVer = '7.65';

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '7.65' ) {

		try {
			$db -> query( "ALTER TABLE `{$sqlname}personcat` ADD `clientpath` INT( 10 ) NOT NULL AFTER `iduser`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$all = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}field WHERE fld_tip = 'person' AND fld_name = 'clientpath'" );
			if ( $all == 0 ) {

				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id` ,`fld_tip` ,`fld_name` ,`fld_title` ,`fld_required` ,`fld_on` ,`fld_order` ,`fld_stat` ,`fld_temp`) VALUES (NULL , 'person', 'clientpath', 'Источник клиента', 'required', 'yes', '5', 'yes', '')" );

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.67')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '7.67' ) {

		try {
			//добавим метку привязки документов к сделкам
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD `did` INT( 30 ) NOT NULL AFTER `pid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD `title` TEXT NOT NULL AFTER `iduser` , ADD `idtype` INT( 30 ) NOT NULL AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			//добавим таблицу типов документов
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}contract_type` (`id` INT(30) NOT NULL AUTO_INCREMENT, `title` VARCHAR(255) NOT NULL, `type` VARCHAR(255) NOT NULL, `role` TEXT NOT NULL, `users` TEXT NOT NULL, `num` INT( 10 ) NOT NULL, `format` VARCHAR( 255 ) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			//добавим поля доступа к КТ
			$db -> query( "ALTER TABLE `{$sqlname}complect_cat` ADD `role` TEXT NOT NULL AFTER `dstep`, ADD `users` TEXT NOT NULL AFTER `role`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//свяжем сделки и договоры
		$result = $db -> query( "SELECT * FROM {$sqlname}dogovor WHERE dog_num > 0" );
		while ($data = $db -> fetch( $result )) {
			$db -> query( "update `{$sqlname}contract` set did = '".$data['did']."' WHERE deid = '".$data['dog_num']."'" );
		}

		$db -> query( "INSERT INTO `{$sqlname}contract_type` (`id`, `title`, `type`, `role`, `users`) VALUES (1, 'Договор', 'get_dogovor', '', ''),(2, 'Акт приема-передачи', 'get_akt', '', '')" );

		try {
			$db -> query( "ALTER TABLE `{$sqlname}profile` ADD FULLTEXT (`value`)" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'zayavka', 'Номер заявки', '', 'no', '0', 'no', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'mcid', 'Наша компания', 'required', 'yes', '2', 'yes', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'iduser', 'Ответственный', 'required', 'yes', '3', 'yes', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'datum_plan', 'Дата план.', 'required', 'yes', '4', 'yes', 'datum')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'period', 'Период действия', '', '".$other[4]."', '5', 'no', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'idcategory', 'Этап', 'required', 'yes', '6', 'yes', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'dog_num', 'Договор', '', '".$other[5]."', '7', 'no', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'tip', 'Тип сделки', '', 'yes', '8', 'no', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'direction', 'Направление', 'required', 'yes', '9', 'yes', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'adres', 'Адрес', '', 'yes', '10', 'no', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'money', 'Деньги', '', 'yes', '11', 'yes', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'content', 'Описание', '', 'yes', '12', 'no', '')" );
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id,fld_tip,fld_name,fld_title,fld_required,fld_on,fld_order,fld_stat,fld_temp) VALUES (NULL, 'dogovor', 'pid_list', 'Персоны', '', 'yes', '13', 'no', '')" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}clientcat` CHANGE `last_dog` `last_dog` DATE NOT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}clientcat` ADD `type` VARCHAR( 100 ) NOT NULL DEFAULT 'client' AFTER `last_hist`" );
			$db -> query( "ALTER TABLE `{$sqlname}dogprovider` ADD `partid` INT( 20 ) NOT NULL AFTER `conid`" );
			$db -> query( "ALTER TABLE `{$sqlname}budjet` ADD `partid` INT( 20 ) NOT NULL AFTER `conid`" );
			$db -> query( "ALTER TABLE `{$sqlname}dogovor` CHANGE `coid1` `coid1` TEXT NULL DEFAULT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$idcat_conc = $db -> getOne( "SELECT idcategory FROM {$sqlname}category WHERE title='Конкуренты'" );
			if ( $idcat_conc == 0 ) {
				$db -> query( "INSERT INTO {$sqlname}category VALUES(NULL, 'Конкуренты')" );
				$idcat_conc = $db -> insertId();
			}

			$idcat_partn = $db -> getOne( "SELECT idcategory FROM {$sqlname}category WHERE title='Партнеры'" );
			if ( $db -> affectedRows( $result ) == 0 ) {
				$db -> query( "INSERT INTO {$sqlname}category VALUES(NULL, 'Партнеры')" );
				$idcat_partn = $db -> insertId();
			}

			$idcat_contr = $db -> getOne( "SELECT idcategory FROM {$sqlname}category WHERE title='Поставщики'" );
			if ( $idcat_contr == 0 ) {
				$db -> query( "INSERT INTO {$sqlname}category VALUES(NULL, 'Поставщики')" );
				$idcat_contr = $db -> insertId();
			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//перенесем данные по Конкурентам
		try {
			$result = $db -> query( "SELECT * FROM {$sqlname}concurents" );
			while ($data = $db -> fetch( $result )) {

				$db -> query( "INSERT INTO {$sqlname}clientcat (clid, title, idcategory, des, address, phone, mail_url, date_create, creator, site_url,type) VALUES(NULL, '".$data['title']."', '".$idcat_conc."', '".$data['des']."', '".$data['address']."', '".$data['phone']."', '".$data['mail_url']."', NULL, '".$iduser1."', '".$data['site_url']."','concurent')" );
				$clid = $db -> insertId();

				//массив сопоставления конкурентов из старой и новой таблиц
				$concurent[ $data['coid'] ] = $clid;

				$resultp = $db -> query( "SELECT * FROM {$sqlname}personcon WHERE coid = '".$data['coid']."'" );
				while ($datap = $db -> fetch( $resultp )) {

					$db -> query( "INSERT INTO {$sqlname}personcat SET ?u", [
						'clid'    => $datap['clid'],
						'ptitle'  => $datap['ptitle'],
						'person'  => $datap['person'],
						'tel'     => $datap['tel'],
						'mail'    => $datap['mail'],
						'creator' => $iduser1
					] );
					$pid = $db -> insertId();

					$db -> query( "update {$sqlname}clientcat set pid = '".$pid."' where clid = '".$clid."'" );
				}

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//перенесем данные по Партнерам и Поставщикам
		try {
			$result = $db -> query( "SELECT * FROM {$sqlname}contractor" );
			while ($data = $db -> fetch( $result )) {

				if ( $data['tip'] == 'partner' )
					$idcat = $idcat_partn;
				if ( $data['tip'] == 'contractor' )
					$idcat = $idcat_contr;

				$db -> query( "INSERT INTO {$sqlname}clientcat (clid, title, idcategory, des, phone, date_create, creator, type,recv) VALUES(NULL, '".$data['title']."', '".$idcat."', '".$data['des']."', '".$data['phone']."', NULL, '".$iduser1."','".$data['tip']."','".$data['ur_naz']."')" );
				$clid = $db -> insertId();

				//массив сопоставления поставщиков/партнеров из старой и новой таблиц
				$contractor[ $data['con_id'] ] = $clid;

				$db -> query( "INSERT INTO {$sqlname}personcat SET ?u", [
					'clid'    => $data['clid'],
					'ptitle'  => $data['ptitle'],
					'person'  => $data['person'],
					'creator' => $iduser1
				] );
				$pid = $db -> insertId();

				$db -> query( "update {$sqlname}clientcat set pid = '".$pid."' where clid = '".$clid."'" );

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//обновим данные по поставщикам из старой таблицы к новой
		try {
			$result = $db -> query( "SELECT * FROM {$sqlname}dogprovider" );
			while ($data = $db -> fetch( $result )) {

				$db -> query( "update {$sqlname}dogprovider set conid = '".$contractor[ $data['conid'] ]."' where id = '".$data['id']."'" );

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//обновим данные сделок по конкурентам из старой таблицы к новой для активных сделок
		try {

			$result = $db -> query( "SELECT * FROM {$sqlname}dogovor" );
			while ($data = $db -> fetch( $result )) {

				//Перенос партнера
				$prtn = explode( ":", $data['partner'] );//0 - id партнера, 1 - сумма партнера

				//если в сделке указан партнер, то перенесем данные в таблицу dogprovider
				if ( $prtn[0] > 0 ) {

					$db -> query( "INSERT INTO {$sqlname}dogprovider (id, partid, did, summa) VALUES (NULL, '".$contractor[ $prtn[0] ]."', '".$data['did']."', '".pre_format( $prtn[1] )."')" );
					$db -> query( "update {$sqlname}dogovor set partner = '".$contractor[ $prtn[0] ]."' where did = '".$data['did']."'" );

				}

				//Перенос списка конкурентов
				$cncr_new = [];
				$cncra    = explode( ";", $data['coid1'] );

				//если в сделке указаны конкуренты, то переопределим их id на новую таблицу
				$concurent = [];
				foreach ( $cncra as $cncr ) {

					$cncr_new[] = $concurent[ $cncr ];

				}
				$cncr_n = implode( ";", $cncr_new );

				$db -> query( "update {$sqlname}dogovor set coid1 = '$cncr_n' where did = '".$data['did']."'" );

				//Перенос данных выигравшего конкурента
				if ( $data['coid'] > 0 ) {
					$db -> query( "update {$sqlname}dogovor set coid = '".$concurent[ $data['coid'] ]."' where did = '".$data['did']."'" );
				}

				$cncr_n = '';
				$prtn   = '';

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//обновим данные бюджета по поставщикам
		try {
			$result = $db -> query( "SELECT * FROM {$sqlname}budjet WHERE conid > 0" );
			while ($data = $db -> fetch( $result )) {

				$db -> query( "update {$sqlname}budjet set conid = '".$contractor[ $data['conid'] ]."' where id = '".$data['id']."'" );

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//удалим таблицу конкурентов
		$db -> query( "DROP TABLE `{$sqlname}concurents`" );
		$db -> query( "DROP TABLE `{$sqlname}contractor`" );
		$db -> query( "DROP TABLE `{$sqlname}personcon`" );

		//добавим поле плательщика по сделке
		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogovor` ADD `payer` INT( 20 ) NOT NULL AFTER `clid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//добавим плательщика по сделке, по умолчанию это заказчик
		try {

			$result = $db -> query( "SELECT * FROM {$sqlname}dogovor" );
			while ($data = $db -> fetch( $result )) {

				$db -> query( "update {$sqlname}dogovor set payer = '".$data['clid']."' where did = '".$data['did']."'" );

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//добавим плательщика по сделке, по умолчанию это заказчик
		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD `payer` INT( 20 ) NOT NULL AFTER `clid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {

			$result = $db -> query( "SELECT * FROM {$sqlname}contract" );
			while ($data = $db -> fetch( $result )) {

				$db -> query( "update {$sqlname}contract set payer = '".$data['clid']."' where deid = '".$data['deid']."'" );

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//добавим поле плательщика
		try {
			$db -> query( "INSERT INTO `{$sqlname}field` (fld_id, fld_tip, fld_name, fld_title, fld_required, fld_on, fld_order, fld_stat, fld_temp ) VALUES (NULL , 'dogovor', 'payer', 'Плательщик', '', 'yes', '14', 'yes', '')" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//создадим таблицу для шаблонов документов
		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}contract_temp` ( `id` INT(20) NOT NULL AUTO_INCREMENT, `typeid` INT(20) NOT NULL, `title` VARCHAR(255) NOT NULL, `file` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.70')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '7.70' ) {

		try {
			$result = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}services WHERE name = 'MailChimp'" );
			if ( $result < 1 ) {

				$db -> query( "INSERT INTO `{$sqlname}services` (id,name,folder,tip) VALUES (NULL,'MailChimp','mailchimp','mail')" );

			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.72')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '7.72' ) {

		try {
			$db -> query( "ALTER TABLE `{$sqlname}mycomps` ADD `stamp` VARCHAR(255) NOT NULL AFTER `okog`, ADD `logo` VARCHAR(255) NOT NULL AFTER `stamp`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$result = $db -> query( "SELECT * FROM {$sqlname}user" );
			while ($data_array = $db -> fetch( $result )) {

				$viget_on    = $data_array['viget_on'];
				$viget_order = $data_array['viget_order'];
				$data        = explode( ";", $viget_order );
				if ( !in_array( "d12", $data ) ) {
					$viget_on    = $viget_on.";on";
					$viget_order = $viget_order.";d12";

					$db -> query( "update {$sqlname}user set viget_on = '$viget_on', viget_order = '$viget_order' where iduser = '".$data_array['iduser']."'" );

				}

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			//Получим список задействованных отчетов
			$result = $db -> query( "SELECT * FROM {$sqlname}reports ORDER BY title" );
			while ($data = $db -> fetch( $result )) {
				$rep[] = $data['file'];
			}
			//Добавим из шаблона
			if ( !in_array( 'voronka_classic.php', $rep ) )
				$db -> query( "INSERT INTO `{$sqlname}reports` VALUES (NULL,'Воронка по активностям','voronka_classic.php','yes','Активности')" );
			if ( !in_array( 'planfact2014.php', $rep ) )
				$db -> query( "INSERT INTO `{$sqlname}reports` VALUES (NULL,'Выполнение планов 2014','planfact2015.php','yes','Планирование')" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.73')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '7.73' ) {

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD `crid` INT(20) NOT NULL AFTER `idtype`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}search` ADD `share` VARCHAR(5) NOT NULL AFTER `iduser`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//почикаем старые отчеты
		try {
			$db -> query( "DELETE FROM {$sqlname}reports WHERE file = 'plan_do.php'" );
			$db -> query( "DELETE FROM {$sqlname}reports WHERE file = 'plan_do_new.php'" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `zam` INT( 20 ) NOT NULL AFTER `acs_plan`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.74')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '7.74' ) {

		try {
			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `zam` INT( 20 ) NOT NULL AFTER `acs_plan`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `CompStart` DATE NOT NULL AFTER `zam`, ADD `CompEnd` DATE NOT NULL AFTER `CompStart`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}settings` ADD `api_key` VARCHAR(255) NOT NULL AFTER `akt_step`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}settings` ADD `coordinator` INT( 20 ) NOT NULL AFTER `api_key`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogovor` ADD `lid` INT(20) NOT NULL AFTER `akt_temp`;" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {

			$tpl = $db -> getCol( "SELECT tip FROM {$sqlname}tpl" );

			if ( !in_array( 'lead_add', $tpl ) )
				$db -> query( "INSERT INTO `{$sqlname}tpl` (`tid`, `tip`, `name`, `content`) VALUES (NULL, 'lead_add', 'Новый интерес', 'Новый входящий интерес - {link}')" );
			if ( !in_array( 'lead_setuser', $tpl ) )
				$db -> query( "INSERT INTO `{$sqlname}tpl` (`tid`, `tip`, `name`, `content`) VALUES (NULL, 'lead_setuser', 'Назначенный интерес', 'Вы назначены Ответственным за обработку входящего интереса - {link}')" );
			if ( !in_array( 'lead_do', $tpl ) )
				$db -> query( "INSERT INTO `{$sqlname}tpl` (`tid`, `tip`, `name`, `content`) VALUES (NULL, 'lead_do', 'Обработанный интерес', 'Я обработал интерес - {link}')" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}settings` ADD `iplist` TEXT NOT NULL AFTER `ipend`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "UPDATE `{$sqlname}smtp` SET tip = 'send' WHERE id = 1" );
			$db -> query( "ALTER TABLE `{$sqlname}smtp` ADD `smtp_protocol` VARCHAR(5) NOT NULL AFTER `smtp_from`, ADD `tip` VARCHAR(10) NOT NULL AFTER `smtp_protocol`, ADD `name` VARCHAR(255) NOT NULL AFTER `tip`, ADD `iduser` INT(20) NOT NULL AFTER `name`, ADD `divider` VARCHAR(3) NOT NULL DEFAULT ':' AFTER `iduser`, ADD `deletemess` VARCHAR(5) NOT NULL DEFAULT 'false' AFTER `divider`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}mycomps_recv` ADD `isDefault` VARCHAR(5) NOT NULL DEFAULT 'no' AFTER `bloc`, ADD `ndsDefault` VARCHAR(5) NOT NULL DEFAULT '0' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}direction` CHANGE `content` `isDefault` VARCHAR(5) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
			$db -> query( "ALTER TABLE `{$sqlname}dogtips` ADD `isDefault` VARCHAR(5) NOT NULL AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}leads` (`id` INT(20) NOT NULL AUTO_INCREMENT, `datum` TIMESTAMP NOT NULL, `status` INT(3) NOT NULL, `rezult` INT(5) NOT NULL, `title` VARCHAR(255) NOT NULL, `email` VARCHAR(255) NOT NULL, `phone` VARCHAR(255) NOT NULL, `site` VARCHAR(255) NOT NULL, `company` VARCHAR(255) NOT NULL, `description` TEXT NOT NULL, `ip` VARCHAR(16) NOT NULL, `city` VARCHAR(100) NOT NULL, `country` VARCHAR(255) NOT NULL, `timezone` VARCHAR(5) NOT NULL, `iduser` INT(11) NOT NULL, `clientpath` INT(20) NOT NULL, `pid` INT(20) NOT NULL, `clid` INT(20) NOT NULL, `did` INT(20) NOT NULL, `partner` INT(20) NOT NULL, `muid` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}leads` ADD `muid` VARCHAR(255) NOT NULL AFTER `partner`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}field` ADD `fld_var` TEXT NOT NULL AFTER `fld_temp`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {

			$flds = $db -> getCol( "SELECT fld_name FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_on='yes' ORDER BY fld_order" );

			if ( !in_array( 'kol', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'dogovor', 'kol', 'Сумма план.', '', 'yes', NULL, 'yes', '', '')" );
			if ( !in_array( 'kol_fact', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'dogovor', 'kol_fact', 'Сумма факт.', '', 'yes', NULL, 'yes', '', '')" );
			if ( !in_array( 'marg', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'dogovor', 'marg', 'Прибыль', '', 'yes', NULL, 'yes', '', '')" );
			if ( !in_array( 'oborot', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'dogovor', 'oborot', 'Оборот', '', 'yes', NULL, 'yes', '', '')" );

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}price` ADD `price_4` DOUBLE(20,2) NOT NULL DEFAULT '0' AFTER `price_3`, ADD `price_5` DOUBLE(20,2) NOT NULL DEFAULT '0' AFTER `price_4`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$result = $db -> query( "SELECT * FROM {$sqlname}price" );
			while ($data = $db -> fetch( $result )) {
				$price_1 = $data['price_1'];
				$price_3 = $data['price_3'];

				if ( $price_1 < $price_3 )
					$db -> query( "update {$sqlname}price set price_1 = '$price_3', price_3 = '$price_1' where n_id = '".$data['n_id']."'" );
			}
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {

			$flds = $db -> getCol( "SELECT fld_name FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' ORDER BY fld_order" );

			if ( !in_array( 'price_in', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'price', 'price_in', 'Закуп', 'required', 'yes', NULL, '', '', '')" );
			if ( !in_array( 'price_1', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'price', 'price_1', 'Розница', 'required', 'yes', NULL, '', '', '30')" );
			if ( !in_array( 'price_2', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'price', 'price_2', 'Уровень 1', '', 'yes', NULL, '', '', '25')" );
			if ( !in_array( 'price_3', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'price', 'price_3', 'Уровень 2', 'required', 'yes', NULL, '', '', '20')" );
			if ( !in_array( 'price_4', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'price', 'price_4', 'Уровень 3', '', '', NULL, '', '', '15')" );
			if ( !in_array( 'price_5', $flds ) )
				$db -> query( "INSERT INTO `{$sqlname}field` (`fld_id`, `fld_tip`, `fld_name`, `fld_title`, `fld_required`, `fld_on`, `fld_order`, `fld_stat`, `fld_temp`, `fld_var`) VALUES (NULL, 'price', 'price_5', 'Уровень 4', '', '', NULL, '', '', '10')" );

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}relations` ADD `isDefault` VARCHAR(6) NOT NULL AFTER `color`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}activities` ADD `isDefault` VARCHAR(6) NOT NULL AFTER `resultat`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}clientpath` ADD `isDefault` VARCHAR(6) NOT NULL AFTER `name`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}loyal_cat` ADD `isDefault` VARCHAR(6) NOT NULL AFTER `color`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		try {
			$db -> query( "ALTER TABLE `{$sqlname}leads` ADD `datum_do` DATETIME NOT NULL AFTER `datum`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `subscription` TEXT NOT NULL AFTER `CompEnd`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}category` ADD `tip` VARCHAR(10) NOT NULL DEFAULT 'client' AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.75')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '7.75' ) {

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract` CHANGE `datum` `datum` DATETIME NOT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}kb` (`idcat` INT(20) NOT NULL AUTO_INCREMENT, `subid` INT(20) NOT NULL, `title` VARCHAR(255) NOT NULL, `share` VARCHAR(5) NOT NULL, PRIMARY KEY (`idcat`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}knowledgebase` (`id` INT(20) NOT NULL AUTO_INCREMENT, `idcat` INT(20) NOT NULL, `datum` DATETIME NOT NULL, `title` VARCHAR(255) NOT NULL, `content` TEXT NOT NULL, `count` INT(20) NOT NULL, `active` VARCHAR(5) NOT NULL, `keywords` TEXT NOT NULL, `author` INT NOT NULL, PRIMARY KEY (`id`), FULLTEXT KEY `content` (`content`)) ENGINE=MyISAM DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}kbtags` (`id` INT(20) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.77')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '7.77' ) {
		try {
			$db -> query( "ALTER TABLE `{$sqlname}tasks` ADD `maintid` INT(20) NOT NULL AFTER `tid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}knowledgebase` CHANGE `content` `content` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}settings` CHANGE `num_client` `num_client` INT(3) NULL DEFAULT '30'" );
			$db -> query( "ALTER TABLE `{$sqlname}settings` CHANGE `num_con` `num_con` INT(3) NULL DEFAULT '30'" );
			$db -> query( "ALTER TABLE `{$sqlname}settings` CHANGE `num_person` `num_person` INT(3) NULL DEFAULT '30'" );
			$db -> query( "ALTER TABLE `{$sqlname}settings` CHANGE `num_dogs` `num_dogs` INT(3) NULL DEFAULT '30'" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}tasks` ADD `created` DATETIME NOT NULL AFTER `speed`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}tasks` ADD `readonly` VARCHAR(3) NOT NULL DEFAULT 'no' AFTER `created`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}tpl` CHANGE `content` `content` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
			$db -> query( "ALTER TABLE `{$sqlname}mail_tpl` CHANGE `content_tpl` `content_tpl` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL" );
			$db -> query( "ALTER TABLE `{$sqlname}mail` CHANGE `template` `template` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//установим индексы в таблицах
		try {
			$db -> query( "ALTER TABLE `{$sqlname}tpl` CHANGE `content` `content` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
			//$db -> query("ALTER TABLE `{$sqlname}tpl` CHANGE `content` `content` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
			//$db -> query("ALTER TABLE `{$sqlname}tpl` CHANGE `content` `content` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
			//$db -> query("ALTER TABLE `{$sqlname}tpl` CHANGE `content` `content` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `avatar` VARCHAR(100) NOT NULL AFTER `subscription`" );
			//ALTER TABLE `{$sqlname}user` ADD `avatar` VARCHAR(100) NOT NULL AFTER `subscription`
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'7.78')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '7.78' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}user` LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `avatar`" );

		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}activities` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}budjet` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `partid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}budjet_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `tip`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}capacity_client` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `sumfact`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}category` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `tip`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}clientcat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `type`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}clientpath` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}comments` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `fid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}comments_subscribe` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `iduser`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}complect` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `iduser`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}complect_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `users`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `crid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contractor` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `datum`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract_temp` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `file`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}contract_type` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `format`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}credit` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `tip`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}direction` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogcategory` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `content`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogovor` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `lid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogprovider` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `status`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogstatus` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `content`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}dogtips` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}dostup` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `iduser`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}field` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `fld_var`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}file` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `folder`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}file_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `shared`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}group` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `idservice`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}grouplist` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `availability`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$res = $db -> getRow( "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$database' AND TABLE_NAME='{$sqlname}history' AND COLUMN_NAME='uid'" );
		if ( $res['COLUMN_NAME'] == "" )
			$db -> query( "ALTER TABLE `{$sqlname}history` ADD `uid` VARCHAR(15) NOT NULL AFTER fid" );

		try {
			$db -> query( "ALTER TABLE `{$sqlname}history` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `uid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}kb` LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}kb` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `share`" );

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}kbtags` LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}kbtags` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `name`" );

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}knowledgebase` LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}knowledgebase` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `author`" );

		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}leads` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `muid`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}logs` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `content`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}loyal_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}mail` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `plist_do`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}mail_tpl` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `content_tpl`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}mycomps` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `logo`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}mycomps_recv` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `ndsDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}office_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}otdel_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}personcat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `editor`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}personcon` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `mail`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}plan` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `marga`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}price` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `nds`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}price_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}profile` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `value`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}profile_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `pwidth`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}relations` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `isDefault`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}reports` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `category`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}search` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `share`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}services` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `user_key`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}sip` LIKE 'sip_path'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}sip ADD sip_path VARCHAR(255) DEFAULT NULL AFTER `sip_pfchange`" );
			$db -> query( "ALTER TABLE {$sqlname}sip ADD sip_cdr VARCHAR(255) DEFAULT NULL AFTER `sip_path`" );
			$db -> query( "ALTER TABLE {$sqlname}sip ADD sip_secure VARCHAR(255) DEFAULT NULL AFTER `sip_cdr`" );
			$db -> query( "ALTER TABLE {$sqlname}sip ADD identity INT(30) NOT NULL DEFAULT '1' AFTER `sip_secure`" );

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}smtp LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}smtp ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `deletemess`" );

		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}speca` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `dop`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}tasks` ADD `identity` VARCHAR(30) NOT NULL DEFAULT '1' AFTER `readonly`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}territory_cat` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `title`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}tpl` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `content`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}activate` (`id` INT(30) NOT NULL AUTO_INCREMENT,`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `activate` VARCHAR(5) NOT NULL DEFAULT 'false', `code` VARCHAR(255) NOT NULL, `identity` INT(30) NOT NULL, UNIQUE KEY `id` (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}sip` LIKE 'sip_cdr'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}sip` ADD `sip_cdr` VARCHAR(255) NOT NULL AFTER `sip_path`" );

		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}settings` ADD `timezone` VARCHAR(255) NOT NULL DEFAULT 'Asia/Yekaterinburg' AFTER `coordinator`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}user` ADD `sole` VARCHAR(250) NOT NULL AFTER `avatar`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//пройдемся по пользователям и обновим пароли
		$result = $db -> query( "SELECT * FROM {$sqlname}user ORDER BY iduser" );
		while ($data = $db -> fetch( $result )) {

			$salt    = generateSalt();
			$newpass = encodePass( $data['pwd'], $salt );

			$db -> query( "update {$sqlname}user set pwd = '".$newpass."', sole = '".$salt."' where iduser = '".$data['iduser']."'" );
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS {$sqlname}changepass (`id` INT(20) NOT NULL AUTO_INCREMENT, `useremail` VARCHAR(255) NOT NULL, `code` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`), KEY `id` (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		//Шифруем данные в БД
		try {
			$db -> query( "ALTER TABLE `{$sqlname}settings` ADD `ivc` VARCHAR(255) NOT NULL AFTER `timezone`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}sip` LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}sip` ADD `identity` INT(30) NOT NULL DEFAULT '1' AFTER `sip_pfchange`" );

		}

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			//генерируем вектор и ключ
			$vector = rij_iv();
			$skey   = 'vanilla'.(($data['id'] + 7) ** 3).'round'.(($data['id'] + 3) ** 2).'robin';
			$apikey = generateSalt( 20 );

			//запишем в настройки
			$db -> query( "update {$sqlname}settings set ivc = '".$vector."' where id = '".$data['id']."'" );

			if ( $data['api_key'] == '' )
				$db -> query( "update {$sqlname}settings set api_key = '".$apikey."' where id = '".$data['id']."'" );

			//закодируем данные в таблице sip
			$res1       = $db -> getRow( "SELECT * FROM {$sqlname}sip WHERE identity = '".$data['id']."'" );
			$sip_user   = rij_crypt( $res1["sip_user"], $skey, $vector );
			$sip_secret = rij_crypt( $res1["sip_secret"], $skey, $vector );

			$db -> query( "ALTER TABLE `{$sqlname}sip` CHANGE `sip_secret` `sip_secret` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
			$db -> query( "update {$sqlname}sip set sip_user = '".$sip_user."', sip_secret = '".$sip_secret."' where identity = '".$data['id']."'" );

			//закодируем данные в таблице smtp
			$db -> query( "ALTER TABLE `{$sqlname}smtp` CHANGE `smtp_pass` `smtp_pass` VARCHAR(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
			$res2 = $db -> query( "SELECT * FROM {$sqlname}smtp WHERE identity = '".$data['id']."'" );
			while ($data2 = $db -> fetch( $res2 )) {

				$smtp_user = rij_crypt( $data2['smtp_user'], $skey, $vector );
				$smtp_pass = rij_crypt( $data2['smtp_pass'], $skey, $vector );

				$db -> query( "update {$sqlname}smtp set smtp_user = '".$smtp_user."', smtp_pass = '".$smtp_pass."' where id = '".$data2['id']."'" );

			}

			//закодируем данные в таблице services
			$res3 = $db -> query( "SELECT * FROM {$sqlname}services WHERE identity = '".$data['id']."'" );
			while ($data3 = $db -> fetch( $res3 )) {

				$user_id  = rij_crypt( $data3['user_id'], $skey, $vector );
				$user_key = rij_crypt( $data3['user_key'], $skey, $vector );

				$db -> query( "update {$sqlname}services set user_id = '".$user_id."', user_key = '".$user_key."' where id = '".$data3['id']."'" );

			}

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}sip` LIKE 'sip_secure'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE `{$sqlname}sip` ADD `sip_secure` VARCHAR(5) NOT NULL AFTER `sip_cdr`" );

		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'8.0')" );

		$currentVer = '8.0';

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}

	/*Yoolla start*/

	if ( getVersion() == '8.0' ) {

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}changepass` (`id` INT(20) NOT NULL AUTO_INCREMENT, `useremail` VARCHAR(255) NOT NULL, `code` VARCHAR(255) NOT NULL, PRIMARY KEY (`id`), KEY `id` (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}history` CHANGE `uid` `uid` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'8.01')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}
	}
	if ( getVersion() == '8.01' ) {

		try {
			$db -> query( "ALTER TABLE `{$sqlname}price_cat` ADD `sub` INT(20) NOT NULL AFTER `idcategory`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}personcat` CHANGE `loyalty` `loyalty` INT(10) NOT NULL DEFAULT '0'" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "ALTER TABLE `{$sqlname}clientcat` CHANGE `territory` `territory` INT(10) NOT NULL DEFAULT '0'" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}incoming` (`p_identity` INT(5) NOT NULL,`p_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,`p_text` TEXT NOT NULL,UNIQUE KEY `p_identity` (`p_identity`)) ENGINE=InnoDB DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}incoming_channels` (`p_identity` INT(5) NOT NULL, `p_time` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00', `p_text` TEXT NOT NULL, UNIQUE KEY `p_identity` (`p_identity`)) ENGINE=InnoDB DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		/*
		try {

			$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
			while ( $data = $db -> fetch( $result ) ) {

				$db -> query("INSERT INTO {$sqlname}incoming (p_identity) VALUES ('".$data['id']."')");

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}
		*/

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'8.05')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.05' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}comments LIKE 'did'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE `{$sqlname}comments` ADD `did` VARCHAR(50) NOT NULL AFTER `uid`" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}speca LIKE 'prid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE `{$sqlname}speca` ADD `prid` INT(20) NOT NULL AFTER `spid`" );


		try {

			$result = $db -> query( "SELECT * FROM {$sqlname}speca ORDER BY spid" );
			while ($data = $db -> fetch( $result )) {

				$prid = $db -> getOne( "SELECT n_id FROM {$sqlname}price WHERE n_id > 0 AND (artikul = '".$data['artikul']."' OR title = '".$data['title']."') AND identity = '".$data['identity']."'" );

				$db -> query( "update {$sqlname}speca set prid = '$prid' WHERE spid = '".$data['spid']."' and identity = '".$data['identity']."'" );

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}entry` (`ide` INT(20) NOT NULL AUTO_INCREMENT, `clid` INT(20) NOT NULL, `pid` INT(20) NOT NULL, `did` INT(20) NOT NULL, `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `iduser` INT(20) NOT NULL, `content` TEXT NOT NULL, `identity` INT(20) NOT NULL, PRIMARY KEY (`ide`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}entry_poz` (`idp` INT(20) NOT NULL AUTO_INCREMENT, `ide` INT(20) NOT NULL, `prid` INT(20) NOT NULL COMMENT 'n_id прайса', `title` VARCHAR(255) NOT NULL, `kol` INT(10) NOT NULL, `price` DOUBLE NOT NULL DEFAULT '0', `identity` INT(20) NOT NULL, PRIMARY KEY (`idp`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='Позиции прайса в запросе'" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modules` (`id` INT(20) NOT NULL AUTO_INCREMENT, `title` VARCHAR(100) NOT NULL, `content` TEXT NOT NULL, `mpath` VARCHAR(255) NOT NULL, `icon` VARCHAR(20) NOT NULL DEFAULT 'icon-publish', `active` VARCHAR(5) NOT NULL DEFAULT 'on', `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			try {
				$db -> query( "INSERT INTO `{$sqlname}modules` (`id`, `title`, `content`, `mpath`, `icon`, `active`, `identity`) VALUES (NULL, 'Каталог-склад', '', 'modcatalog', 'icon-archive', 'off', '".$data['id']."'),(NULL, 'Обращения', '{\"enShowButtonLeft\":\"yes\",\"enShowButtonCall\":\"yes\"}', 'entry', 'icon-phone-squared', 'off', '".$data['id']."'),(NULL, 'Веб-консультант', '', 'wcdialog', 'icon-chat-1', 'off', '".$data['id']."')" );
			}
			catch ( Exception $e ) {
				print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
			}

		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}wcdialogs` (`id` INT(20) NOT NULL AUTO_INCREMENT, `datum` DATETIME NOT NULL, `dialog_id` INT(20) NOT NULL, `user_id` INT(20) NOT NULL, `site_id` INT(20) NOT NULL, `client_id` INT(20) NOT NULL, `client_name` INT(20) NOT NULL, `dialog` MEDIUMTEXT NOT NULL, `info` TEXT NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}wcdialogs_sites` (`id` INT(20) NOT NULL AUTO_INCREMENT, `site_id` INT(20) NOT NULL, `site_name` VARCHAR(255) NOT NULL, `site_url` VARCHAR(100) NOT NULL, `status` VARCHAR(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), KEY `id` (`id`,`site_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}wcdialogs_users` (`id` INT(20) NOT NULL AUTO_INCREMENT, `user_id` INT(20) NOT NULL, `user_name` VARCHAR(255) NOT NULL, `user_login` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), KEY `id` (`id`,`user_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}wcdialogs_users` (`id` INT(20) NOT NULL AUTO_INCREMENT, `user_id` INT(20) NOT NULL, `user_name` VARCHAR(255) NOT NULL, `user_login` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), KEY `id` (`id`,`user_id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog` (`id` INT(20) NOT NULL AUTO_INCREMENT, `prid` INT(20) NOT NULL, `idz` INT(20) NOT NULL COMMENT 'id заявки', `content` MEDIUMTEXT NOT NULL, `datum` DATETIME NOT NULL, `price_plus` DOUBLE NOT NULL, `status` INT(11) NOT NULL DEFAULT '0', `kol` DOUBLE NOT NULL, `files` TEXT NOT NULL, `sklad` INT(255) NOT NULL, `iduser` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), KEY `prid` (`prid`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );

			$res = $db -> query( "SELECT * FROM {$sqlname}price ORDER BY n_id" );
			while ($data = $db -> fetch( $res )) {

				$db -> query( "INSERT INTO {$sqlname}modcatalog (id,prid,status,kol,identity) VALUES(NULL,'".$data['n_id']."','0','0','".$data['identity']."')" );

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_akt` (`id` INT(20) NOT NULL AUTO_INCREMENT, `did` INT(20) NOT NULL, `tip` VARCHAR(100) NOT NULL, `number` INT(20) NOT NULL, `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `clid` INT(20) NOT NULL, `posid` INT(20) NOT NULL COMMENT 'id поставщика', `man1` VARCHAR(255) NOT NULL, `man2` VARCHAR(255) NOT NULL, `isdo` VARCHAR(5) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_aktpoz` (`id` INT(20) NOT NULL AUTO_INCREMENT, `ida` INT(20) NOT NULL COMMENT 'id акта в таблице modcatalog_akt', `prid` INT(20) NOT NULL, `price_in` DOUBLE NOT NULL DEFAULT '0', `kol` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_dop` (`id` INT(20) NOT NULL AUTO_INCREMENT, `prid` INT(20) NOT NULL, `bid` INT(20) NOT NULL, `datum` DATE NOT NULL, `content` TEXT NOT NULL, `summa` DOUBLE NOT NULL, `clid` INT(20) NOT NULL, `iduser` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_field` (`id` INT(11) NOT NULL AUTO_INCREMENT, `pfid` INT(10) NOT NULL, `n_id` INT(11) NOT NULL, `value` VARCHAR(255) NOT NULL, `identity` INT(30) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), FULLTEXT KEY `value` (`value`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_fieldcat` (`id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NOT NULL, `tip` VARCHAR(10) NOT NULL, `value` TEXT NOT NULL, `ord` INT(5) NOT NULL, `pole` VARCHAR(10) NOT NULL, `pwidth` INT(3) NOT NULL DEFAULT '50', `identity` INT(30) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_log` (`id` INT(20) NOT NULL AUTO_INCREMENT, `dopzid` INT(20) NOT NULL, `datum` DATETIME NOT NULL, `tip` VARCHAR(255) NOT NULL, `new` TEXT NOT NULL, `old` TEXT NOT NULL, `prid` INT(20) NOT NULL, `iduser` INT(20) NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_offer` (`id` INT(20) NOT NULL AUTO_INCREMENT, `datum` DATETIME NOT NULL, `datum_end` DATETIME NOT NULL, `status` INT(20) NOT NULL DEFAULT '0', `iduser` INT(20) NOT NULL, `content` TEXT NOT NULL, `des` TEXT NOT NULL, `users` TEXT NOT NULL, `prid` INT(20) NOT NULL COMMENT 'id созданной позиции', `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_reserv` (`id` INT(20) NOT NULL AUTO_INCREMENT, `did` INT(20) NOT NULL, `prid` INT(20) NOT NULL, `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `kol` DOUBLE NOT NULL, `status` VARCHAR(30) NOT NULL, `idz` INT(20) NOT NULL COMMENT 'id заявки', `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`), KEY `did` (`did`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {
			$db -> query( "CREATE TABLE IF NOT EXISTS `{$sqlname}modcatalog_set` (`id` INT(20) NOT NULL AUTO_INCREMENT, `settings` TEXT NOT NULL, `ftp` TEXT NOT NULL, `identity` INT(20) NOT NULL DEFAULT '1', PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			try {

				$db -> query( "INSERT INTO `{$sqlname}modcatalog_set` (`id`, `settings`, `identity`) VALUES(NULL, '{\"mcArtikul\":\"yes\",\"mcStep\":\"6\",\"mcStepPers\":\"80\",\"mcKolEdit\":\"yes\",\"mcStatusEdit\":\"yes\",\"mcUseOrder\":\"yes\",\"mcCoordinator\":[],\"mcSpecialist\":[],\"mcAutoRezerv\":null,\"mcAutoWork\":null,\"mcAutoStatus\":null,\"mcSklad\":\"yes\",\"mcDBoardSkladName\":\"\",\"mcDBoardSklad\":\"yes\",\"mcDBoardZayavkaName\":\"Заявки\",\"mcDBoardZayavka\":\"yes\",\"mcDBoardOfferName\":\"Предложения\",\"mcDBoardOffer\":\"yes\",\"mcMenuTip\":\"inMain\",\"mcMenuPlace\":\"\",\"mcOfferName1\":\"Год\",\"mcOfferName2\":\"Состояние\"}', '".$data['id']."')" );
			}
			catch ( Exception $e ) {
				print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
			}

		}

		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}modcatalog_sklad'" );
		if ( $da == 0 )
			$db -> query( "
		CREATE TABLE {$sqlname}modcatalog_sklad (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`title` VARCHAR(255) NOT NULL, 
			`identity` INT(20) NOT NULL DEFAULT '1', 
			PRIMARY KEY (`id`), 
			UNIQUE KEY `id` (`id`)
		) 
		ENGINE=MyISAM  DEFAULT CHARSET=utf8" );

		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}modcatalog_zayavka'" );
		if ( $da == 0 )
			$db -> query( "
		CREATE TABLE `{$sqlname}modcatalog_zayavka` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`did` INT(20) NOT NULL, 
			`datum` DATETIME NOT NULL, 
			`datum_priority` DATE NOT NULL, 
			`datum_start` DATETIME NOT NULL, 
			`datum_end` DATETIME NOT NULL, 
			`status` INT(20) NOT NULL DEFAULT '0', 
			`iduser` INT(20) NOT NULL, 
			`sotrudnik` INT(20) NOT NULL, 
			`content` TEXT NOT NULL, 
			`rezult` TEXT NOT NULL, 
			`des` TEXT NOT NULL, 
			`isHight` VARCHAR(3) NOT NULL DEFAULT 'no', 
			`identity` INT(20) NOT NULL DEFAULT '1', 
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8" );

		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}modcatalog_zayavkapoz'" );
		if ( $da == 0 )
			$db -> query( "
		CREATE TABLE {$sqlname}modcatalog_zayavkapoz (
			`id` INT(20) NOT NULL AUTO_INCREMENT, 
			`idz` INT(20) NOT NULL COMMENT 'id заявки', 
			`prid` INT(20) NOT NULL, 
			`kol` DOUBLE NOT NULL, 
			`identity` INT(20) NOT NULL DEFAULT '1', 
		PRIMARY KEY (`id`)
		) 
		ENGINE=MyISAM DEFAULT CHARSET=utf8" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}comments LIKE 'datum'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}comments ADD `datum` DATETIME NOT NULL AFTER `mid`" );

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.10')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.10' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}settings LIKE 'dFormat'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}settings ADD `dFormat` VARCHAR(255) NOT NULL AFTER `ivc`, ADD `dNum` VARCHAR(255) NOT NULL AFTER `dFormat`" );

		$db -> query( "INSERT INTO `{$sqlname}ver` (id,current) VALUES (NULL,'8.11')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.11' ) {

		$db -> query( "ALTER TABLE {$sqlname}credit CHANGE `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
		$db -> query( "UPDATE {$sqlname}user set mid = '0' WHERE mid is null" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}tasks LIKE 'alert'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE `{$sqlname}tasks` ADD `alert` VARCHAR(3) NOT NULL DEFAULT 'yes' AFTER `readonly`" );

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}callhistory` LIKE 'identity'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "
			ALTER TABLE `{$sqlname}callhistory`
			ADD COLUMN `identity` INT(20) NOT NULL DEFAULT '1' AFTER `dst`
		" );

		}

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.12')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.12' ) {

		$result = $db -> query( "SELECT * FROM {$sqlname}dogovor WHERE akt_num != '' ORDER BY did" );
		while ($data = $db -> fetch( $result )) {

			$idtype = $db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'get_akt' AND identity = '".$data['identity']."'" );

			$db -> query( "INSERT INTO {$sqlname}contract (deid, datum, number, clid, payer, did, iduser, title, idtype, identity) VALUES (NULL, '".$data['akt_date']." 12:00:00', '".$data['akt_num']."', '".$data['clid']."', '".$data['payer']."', '".$data['did']."', '".$data['iduser']."', '".$data['akt_temp']."', '".$idtype."', '".$data['identity']."')" );

		}

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.13')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.13' ) {


		$db -> query( "ALTER TABLE `{$sqlname}leads` CHANGE `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}mail LIKE 'clist_do'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}mail ADD `clist_do` TEXT NOT NULL AFTER `template`" );

		$db -> query( "ALTER TABLE {$sqlname}mail CHANGE `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.14')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.14' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}user LIKE 'adate'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}user ADD `adate` DATE NOT NULL AFTER `sole`" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}user LIKE 'usersettings'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}user ADD `usersettings` TEXT NOT NULL AFTER `adate`" );

		$db -> query( "ALTER TABLE {$sqlname}clientcat ADD UNIQUE(`clid`)" );

		try {
			$db -> query( "ALTER TABLE `{$sqlname}relations` CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}activities LIKE 'aorder'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}activities ADD `aorder` INT(5) NOT NULL AFTER `isDefault`" );
			$db -> query( "ALTER TABLE {$sqlname}activities ADD `filter` VARCHAR(255) NOT NULL DEFAULT 'all' AFTER `aorder`" );

		}

		try {
			$db -> query( "ALTER TABLE {$sqlname}budjet CHANGE `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}otdel_cat LIKE 'uid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}otdel_cat ADD `uid` VARCHAR(30) NOT NULL AFTER `idcategory`" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}user LIKE 'uid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}user ADD `uid` VARCHAR(30) NOT NULL AFTER `usersettings`" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'uid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD `uid` VARCHAR(30) NOT NULL AFTER `did`" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}clientcat LIKE 'uid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD `uid` VARCHAR(30) NOT NULL AFTER `clid`" );

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			$order = 0;
			$res   = $db -> query( "SELECT * FROM {$sqlname}activities ORDER BY id" );
			while ($da = $db -> fetch( $res )) {

				$db -> query( "update {$sqlname}activities set aorder = '$order' WHERE id = '$da[id]' and identity = '$data[id]'" );
				$order++;

			}

		}

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.20')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.20' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}mycomps LIKE 'address_yur'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}mycomps ADD COLUMN `address_yur` TEXT NOT NULL AFTER `name_shot`, ADD COLUMN `address_post` TEXT NOT NULL AFTER `address_yur`;" );

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.21')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.21' ) {

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'input1'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}dogovor
		ADD `input1` VARCHAR(512) NOT NULL AFTER `lid`, 
		ADD `input2` VARCHAR(512) NOT NULL AFTER `input1`, 
		ADD `input3` VARCHAR(512) NOT NULL AFTER `input2`, 
		ADD `input4` VARCHAR(512) NOT NULL AFTER `input3`, 
		ADD `input5` VARCHAR(512) NOT NULL AFTER `input4`, 
		ADD `input6` VARCHAR(512) NOT NULL AFTER `input5`, 
		ADD `input7` VARCHAR(512) NOT NULL AFTER `input6`, 
		ADD `input8` VARCHAR(512) NOT NULL AFTER `input7`, 
		ADD `input9` VARCHAR(512) NOT NULL AFTER `input8`, 
		ADD `input10` VARCHAR(512) NOT NULL AFTER `input9`
	" );

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			$isInput = $db -> getOne( "SELECT fld_name FROM {$sqlname}field WHERE fld_tip = 'dogovor' AND fld_name = 'input1' AND identity = '$data[id]'" );

			if ( $isInput == '' ) {

				$fld = [
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input1',
						'fld_title' => 'доп.поле',
						'fld_order' => '15',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input2',
						'fld_title' => 'доп.поле',
						'fld_order' => '16',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input3',
						'fld_title' => 'доп.поле',
						'fld_order' => '17',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input4',
						'fld_title' => 'доп.поле',
						'fld_order' => '18',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input5',
						'fld_title' => 'доп.поле',
						'fld_order' => '19',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input6',
						'fld_title' => 'доп.поле',
						'fld_order' => '20',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input7',
						'fld_title' => 'доп.поле',
						'fld_order' => '21',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input8',
						'fld_title' => 'доп.поле',
						'fld_order' => '22',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input9',
						'fld_title' => 'доп.поле',
						'fld_order' => '23',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input10',
						'fld_title' => 'доп.поле',
						'fld_order' => '24',
						'identity'  => $data['id']
					]
				];

				foreach ( $fld as $i => $val ) {

					$db -> query( "INSERT INTO {$sqlname}field SET ?u", $val );

				}

			}

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}credit LIKE 'suffix'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}credit ADD `suffix` TEXT NOT NULL COMMENT 'суффикс счета' AFTER `tip`" );

		$count = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}steplog'" );
		if ( $count == 0 )
			$db -> query( "
		CREATE TABLE IF NOT EXISTS `{$sqlname}steplog` (
			`id` INT(11) NOT NULL AUTO_INCREMENT, 
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
			`step` INT(5) NOT NULL COMMENT 'idcategory в dogcategory', 
			`did` INT(20) NOT NULL COMMENT 'did в dogovor', 
			`iduser` INT(20) NOT NULL, 
			`identity` INT(20) NOT NULL, 
			PRIMARY KEY (`id`), 
			KEY `step` (`step`), 
			KEY `did` (`did`)
		) 
		ENGINE=MyISAM 
		DEFAULT CHARSET=utf8 
		COMMENT='Лог смены этапа сделок'
	" );

		//попробуем восстановить последнее изменение этапа в сделке
		$result = $db -> query( "SELECT * FROM {$sqlname}dogovor WHERE close != 'yes' ORDER BY did" );
		while ($data = $db -> fetch( $result )) {

			$r      = $db -> getRow( "SELECT did, MAX(datum) AS datum, iduser FROM {$sqlname}history WHERE did = '$data[did]' AND des LIKE '%Этап сделки изменен%' AND tip IN ('ЛогCRM','СобытиеCRM') GROUP BY did ORDER BY datum DESC" );
			$datum  = $r['datum'];
			$didd   = $r['did'];
			$iduser = $r['iduser'];

			//если дата найдена - добавим в лог последнее изменение статуса
			if ( $didd > 0 )
				$db -> query( "INSERT INTO {$sqlname}steplog SET ?u", [
					'datum'    => $datum,
					'did'      => $data['did'] + 0,
					'step'     => $data['idcategory'] + 0,
					'iduser'   => $iduser + 0,
					'identity' => $data['identity']
				] );

			//в противном случае берем дату создания сделки
			else $db -> query( "INSERT INTO {$sqlname}steplog SET ?u", [
				'datum'    => $data['datum']." 12:00:00",
				'did'      => $data['did'] + 0,
				'step'     => $data['idcategory'] + 0,
				'iduser'   => $data['iduser'] + 0,
				'identity' => $data['identity']
			] );

		}

		$result = $db -> query( "SELECT did, iduser FROM {$sqlname}dogovor WHERE autor = '0' ORDER BY did" );
		while ($data = $db -> fetch( $result )) {

			$db -> query( "update {$sqlname}dogovor set autor ='$data[iduser]' where did = '$data[did]'" );

		}

		/*добавим время последнего коммента в базу*/
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}comments LIKE 'lastCommentDate'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}comments ADD `lastCommentDate` DATETIME NOT NULL AFTER `fid`" );

		$result = $db -> query( "SELECT * FROM {$sqlname}comments WHERE idparent = '0' ORDER BY id" );
		while ($da = $db -> fetch( $result )) {

			$lastDatum = $db -> getOne( "SELECT MAX(datum) AS datum FROM {$sqlname}comments WHERE idparent = '$da[id]'" );

			if ( $lastDatum != '0000-00-00 00:00:00' ) {

				$db -> query( "update {$sqlname}comments set lastCommentDate = '$lastDatum' WHERE id = '$da[id]' and identity = '$da[identity]'" );

			}

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}settings LIKE 'outClientUrl'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}settings CHANGE `my_dir_name` `outClientUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );
			$db -> query( "ALTER TABLE {$sqlname}settings CHANGE `my_dir_shot` `outDealUrl` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );

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

		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'8.30')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '8.30' ) {

		/**
		 * Доп.поля для сделок
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogovor LIKE 'input1'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}dogovor
		ADD `input1` VARCHAR(512) NOT NULL AFTER `lid`, 
		ADD `input2` VARCHAR(512) NOT NULL AFTER `input1`, 
		ADD `input3` VARCHAR(512) NOT NULL AFTER `input2`, 
		ADD `input4` VARCHAR(512) NOT NULL AFTER `input3`, 
		ADD `input5` VARCHAR(512) NOT NULL AFTER `input4`, 
		ADD `input6` VARCHAR(512) NOT NULL AFTER `input5`, 
		ADD `input7` VARCHAR(512) NOT NULL AFTER `input6`, 
		ADD `input8` VARCHAR(512) NOT NULL AFTER `input7`, 
		ADD `input9` VARCHAR(512) NOT NULL AFTER `input8`, 
		ADD `input10` VARCHAR(512) NOT NULL AFTER `input9`
	" );

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			$isInput = $db -> getOne( "SELECT fld_name FROM {$sqlname}field WHERE fld_tip = 'dogovor' AND fld_name = 'input1' AND identity = '$data[id]'" );

			if ( $isInput == '' ) {

				$fld = [
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input1',
						'fld_title' => 'доп.поле',
						'fld_order' => '15',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input2',
						'fld_title' => 'доп.поле',
						'fld_order' => '16',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input3',
						'fld_title' => 'доп.поле',
						'fld_order' => '17',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input4',
						'fld_title' => 'доп.поле',
						'fld_order' => '18',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input5',
						'fld_title' => 'доп.поле',
						'fld_order' => '19',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input6',
						'fld_title' => 'доп.поле',
						'fld_order' => '20',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input7',
						'fld_title' => 'доп.поле',
						'fld_order' => '21',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input8',
						'fld_title' => 'доп.поле',
						'fld_order' => '22',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input9',
						'fld_title' => 'доп.поле',
						'fld_order' => '23',
						'identity'  => $data['id']
					],
					[
						'fld_tip'   => 'dogovor',
						'fld_name'  => 'input10',
						'fld_title' => 'доп.поле',
						'fld_order' => '24',
						'identity'  => $data['id']
					]
				];

				foreach ( $fld as $i => $val ) {

					$db -> query( "INSERT INTO {$sqlname}field SET ?u", $val );

				}

			}

		}
		//Доп.поля для сделок

		/**
		 * Увеличим длину названия доп.полей
		 */
		$db -> query( "ALTER TABLE {$sqlname}field CHANGE `fld_title` `fld_title` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL" );

		/**
		 * Доп.поле для спецификаций
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}speca LIKE 'comments'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}speca ADD `comments` VARCHAR(250) NOT NULL AFTER `dop`" );

		/**
		 * Привязка затрат на поставщиков с бюджетом
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogprovider LIKE 'bid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}dogprovider ADD `bid` INT(20) NOT NULL AFTER `status`" );

		$result = $db -> query( "SELECT * FROM {$sqlname}dogprovider" );
		while ($da = $db -> fetch( $result )) {

			if ( $da['conid'] > 0 )
				$s = "and conid = '$da[conid]'";
			elseif ( $da['partid'] > 0 )
				$s = "and partid = '$da[partid]'";

			$bjid = $db -> getOne( "SELECT id FROM {$sqlname}budjet WHERE did = '$data[did]' and summa = '$da[summa]' $s and identity = '$data[identity]'" );
			if ( $bjid > 0 ) {

				$db -> query( "update {$sqlname}dogprovider set bid = '$bjid' where id = '$da[id]' and identity = '$da[identity]'" );

			}

		}

		/**
		 * Доп.настройка для названия новых сделок
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}settings LIKE 'my_dir_status'" );
		if ( $field['Field'] != '' )
			$db -> query( "ALTER TABLE {$sqlname}settings CHANGE `my_dir_status` `defaultDealName` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL" );

		/**
		 * Расставим индексы по таблицам
		 */
		$index  = [];
		$result = $db -> query( "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database'" );
		while ($da = $db -> fetch( $result )) {
			$index[ $da['TABLE_NAME'] ][] = $da['COLUMN_NAME'];
		}

		if ( !in_array( "iduser", $index[ $sqlname."clientcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD INDEX(`iduser`)" );

		if ( !in_array( "identity", $index[ $sqlname."clientcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD INDEX(`identity`)" );

		if ( !in_array( "trash", $index[ $sqlname."clientcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD INDEX(`trash`)" );

		if ( !in_array( "phone", $index[ $sqlname."clientcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD INDEX(`phone`)" );

		if ( !in_array( "fax", $index[ $sqlname."clientcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD INDEX(`fax`)" );

		if ( !in_array( "mail_url", $index[ $sqlname."clientcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}clientcat ADD INDEX(`mail_url`)" );

		if ( !in_array( "clid", $index[ $sqlname."profile" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}profile ADD INDEX(`clid`)" );

		if ( !in_array( "value", $index[ $sqlname."profile" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}profile ADD INDEX `value` (`value`)" );

		if ( !in_array( "identity", $index[ $sqlname."profile" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}profile ADD INDEX(`identity`)" );

		if ( !in_array( "tip", $index[ $sqlname."profile_cat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}profile_cat ADD INDEX `tip` (`tip`)" );

		if ( !in_array( "person", $index[ $sqlname."personcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}personcat ADD INDEX `person` (`person`)" );

		if ( !in_array( "ptitle", $index[ $sqlname."personcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}personcat ADD INDEX `ptitle` (`ptitle`)" );

		if ( !in_array( "tel", $index[ $sqlname."personcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}personcat ADD INDEX `tel` (`tel`)" );

		if ( !in_array( "mob", $index[ $sqlname."personcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}personcat ADD INDEX `mob` (`mob`)" );

		if ( !in_array( "mail", $index[ $sqlname."personcat" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}personcat ADD INDEX `mail` (`mail`)" );

		if ( !in_array( "title", $index[ $sqlname."relations" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}relations ADD INDEX `title` (`title`)" );

		if ( !in_array( "title", $index[ $sqlname."user" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}user ADD INDEX `title` (`title`)" );

		if ( !in_array( "clid", $index[ $sqlname."history" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}history ADD INDEX(`clid`)" );

		if ( !in_array( "pid", $index[ $sqlname."history" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}history ADD INDEX(`pid`)" );

		if ( !in_array( "did", $index[ $sqlname."history" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}history ADD INDEX(`did`)" );

		if ( !in_array( "iduser", $index[ $sqlname."history" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}history ADD INDEX(`iduser`)" );

		if ( !in_array( "identity", $index[ $sqlname."history" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}history ADD INDEX(`identity`)" );

		if ( !in_array( "tip", $index[ $sqlname."history" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}history ADD INDEX(`tip`)" );

		if ( !in_array( "title", $index[ $sqlname."activities" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}activities ADD INDEX(`title`)" );

		if ( !in_array( "identity", $index[ $sqlname."activities" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}activities ADD INDEX(`identity`)" );

		if ( !in_array( "iduser", $index[ $sqlname."tasks" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX(`iduser`)" );

		if ( !in_array( "identity", $index[ $sqlname."tasks" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX(`identity`)" );

		if ( !in_array( "tip", $index[ $sqlname."tasks" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX(`tip`)" );

		if ( !in_array( "clid", $index[ $sqlname."tasks" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX(`clid`)" );

		if ( !in_array( "did", $index[ $sqlname."tasks" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX(`did`)" );

		if ( !in_array( "identity", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`identity`)" );

		if ( !in_array( "iduser", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`iduser`)" );

		if ( !in_array( "idcategory", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`idcategory`)" );

		if ( !in_array( "clid", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`clid`)" );

		if ( !in_array( "tip", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`tip`)" );

		if ( !in_array( "direction", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`direction`)" );

		if ( !in_array( "datum_plan", $index[ $sqlname."dogovor" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX(`datum_plan`)" );

		if ( !in_array( "identity", $index[ $sqlname."dogcategory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogcategory ADD INDEX(`identity`)" );

		if ( !in_array( "title", $index[ $sqlname."dogcategory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}dogcategory ADD INDEX(`title`)" );

		if ( !in_array( "uid", $index[ $sqlname."callhistory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD INDEX(`uid`)" );

		if ( !in_array( "src", $index[ $sqlname."callhistory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD INDEX(`src`)" );

		if ( !in_array( "dst", $index[ $sqlname."callhistory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD INDEX(`dst`)" );

		if ( !in_array( "clid", $index[ $sqlname."callhistory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD INDEX(`clid`)" );

		if ( !in_array( "pid", $index[ $sqlname."callhistory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD INDEX(`pid`)" );

		if ( !in_array( "identity", $index[ $sqlname."callhistory" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD INDEX(`identity`)" );

		if ( !in_array( "id", $index[ $sqlname."ymail_messages" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messages ADD INDEX(`id`)" );

		if ( !in_array( "uid", $index[ $sqlname."ymail_messages" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messages ADD INDEX(`uid`)" );

		if ( !in_array( "messageid", $index[ $sqlname."ymail_messages" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messages ADD INDEX(`messageid`)" );

		if ( !in_array( "iduser", $index[ $sqlname."ymail_messages" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messages ADD INDEX(`iduser`)" );

		if ( !in_array( "id", $index[ $sqlname."ymail_messagesrec" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messagesrec ADD INDEX(`id`)" );

		if ( !in_array( "mid", $index[ $sqlname."ymail_messagesrec" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messagesrec ADD INDEX(`mid`)" );

		if ( !in_array( "tip", $index[ $sqlname."ymail_messagesrec" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messagesrec ADD INDEX(`tip`)" );

		if ( !in_array( "email", $index[ $sqlname."ymail_messagesrec" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messagesrec ADD INDEX `email` (`email`)" );

		if ( !in_array( "clid", $index[ $sqlname."ymail_messagesrec" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messagesrec ADD INDEX(`clid`)" );

		if ( !in_array( "pid", $index[ $sqlname."ymail_messagesrec" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}ymail_messagesrec ADD INDEX(`pid`)" );

		if ( !in_array( "did", $index[ $sqlname."steplog" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}steplog ADD INDEX(`did`)" );

		if ( !in_array( "step", $index[ $sqlname."steplog" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}steplog ADD INDEX(`step`)" );

		/**
		 * Обсуждения. Добавляем признак Закрыта/Активна
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}comments LIKE 'dateClose'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}comments 
		ADD `isClose` VARCHAR(10) NOT NULL DEFAULT 'no' AFTER `lastCommentDate`,
		ADD `dateClose` DATETIME NOT NULL AFTER `isClose`
	" );

		/**
		 * Фоматирование номеров телефона
		 */
		$db -> query( "update {$sqlname}settings set format_phone = '9(999)999-99-99', format_tel = '8(342)200-55-66'" );

		/**
		 * Добавим поле в таблицу доступов для подписок
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dostup LIKE 'subscribe'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}dostup ADD `subscribe` VARCHAR(3) NOT NULL DEFAULT 'off' AFTER `iduser`" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.01')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}

	/*Yoolla end*/

	/*2016*/

	if ( getVersion() == '2016.01' ) {

		/**
		 * Дата добавления активности
		 */
		try {
			$db -> query( "ALTER TABLE {$sqlname}history CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT NULL AFTER `did`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		/**
		 * Дата добавления почты
		 */
		try {
			$db -> query( "ALTER TABLE {$sqlname}ymail_messages CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL AFTER `id`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}


		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.10')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2016.10' ) {

		/**
		 * Новые поля к Заявкам. каталог-склад
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_zayavka LIKE 'cInvoice'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}modcatalog_zayavka 
		ADD COLUMN `cInvoice` VARCHAR(20) NOT NULL COMMENT '№ счета поставщика' AFTER `isHight`, 
		ADD COLUMN `cDate` DATE NULL DEFAULT NULL COMMENT 'Дата счета поставщика' AFTER `cInvoice`, 
		ADD COLUMN `cSumma` DOUBLE(20,2) NULL DEFAULT '0' COMMENT 'сумма счета поставщика' AFTER `cDate`, 
		ADD COLUMN `bid` INT(20) NULL DEFAULT '0' COMMENT 'Связка с записью в Расходах' AFTER `cSumma`, 
		ADD COLUMN `providerid` INT(20) NULL DEFAULT '0' COMMENT 'id записи в таблице dogprovider' AFTER `bid`, 
		ADD COLUMN `conid` INT(20) NULL DEFAULT '0' COMMENT 'id поставщика' AFTER `providerid`, 
		ADD COLUMN `sklad` INT(20) NULL DEFAULT '0' COMMENT 'id склада' AFTER `conid`
	" );

		/**
		 * Новые поля к Ордерам. каталог-склад
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_akt LIKE 'cFactura'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}modcatalog_akt 
		ADD COLUMN `cFactura` VARCHAR(20) NOT NULL COMMENT '№ счета-фактуры поставщика' AFTER `isdo`, 
		ADD COLUMN `cDate` DATE NULL COMMENT 'Дата счета-фактуры' AFTER `cFactura`, 
		ADD COLUMN `sklad` INT(20) NULL DEFAULT '0' COMMENT 'id склада' AFTER `cDate`,
		ADD COLUMN `idz` INT(20) NULL COMMENT 'id заявки' AFTER `sklad`
	" );

		/**
		 * Поштучный учет товара по складам
		 */
		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}modcatalog_skladpoz'" );
		if ( $da == 0 )
			$db -> query( "
		CREATE TABLE {$sqlname}modcatalog_skladpoz (
			`id` INT(40) NOT NULL AUTO_INCREMENT,
			`prid` INT(10) NOT NULL DEFAULT '0' COMMENT 'id товара',
			`sklad` INT(10) NOT NULL DEFAULT '0' COMMENT 'id склада',
			`status` VARCHAR(5) NOT NULL DEFAULT 'out',
			`date_in` DATE NULL DEFAULT NULL COMMENT 'дата поступления',
			`date_out` DATE NULL DEFAULT NULL COMMENT 'дата выбытия',
			`serial` VARCHAR(255) NULL DEFAULT NULL COMMENT 'серийный номер',
			`date_create` DATE NULL DEFAULT NULL COMMENT 'дата производства',
			`date_period` DATE NULL DEFAULT NULL COMMENT 'дата (например поверки)',
			`kol` DOUBLE(20,4) NULL DEFAULT '0.0000' COMMENT 'количество (если учет не поштучный)',
			`did` INT(20) NULL DEFAULT NULL COMMENT 'id сделки, на которую позиция списана (поштучный учет)',
			`idorder_in` INT(20) NULL DEFAULT '0' COMMENT 'id приходного ордера',
			`idorder_out` INT(20) NULL DEFAULT '0' COMMENT 'id расходного ордера',
			`summa` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'стоимость для расх.ордера',
			`identity` INT(11) NOT NULL DEFAULT '1',
			PRIMARY KEY (`id`),
			INDEX `prid` (`prid`),
			INDEX `sklad` (`sklad`),
			INDEX `did` (`did`),
			INDEX `identity` (`identity`)
		)
		COMMENT='Каталог-склад. Позиции на складах'
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM
	" );

		/**
		 * Раскидаем позиции по складам. Пока без поштучного учета
		 */
		$res = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog ORDER BY id" );
		foreach ( $res as $data ) {

			$db -> query( "INSERT INTO {$sqlname}modcatalog_skladpoz SET ?u", [
				'prid'     => $data['prid'],
				'status'   => 'in',
				'sklad'    => $data['sklad'],
				'kol'      => $data['kol'],
				'identity' => $data['identity']
			] );

		}

		/**
		 * Резерв
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_reserv LIKE 'sklad'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}modcatalog_reserv ADD COLUMN `sklad` INT(20) NOT NULL DEFAULT '0' COMMENT 'id склада' AFTER `idz`" );
			$db -> query( "
			ALTER TABLE {$sqlname}modcatalog_reserv 
			CHANGE COLUMN `status` `status` VARCHAR(30) NOT NULL COMMENT 'статус резерва (действует/снят)' AFTER `kol`, 
			CHANGE COLUMN `idz` `idz` INT(20) NOT NULL DEFAULT '0' COMMENT 'id заявки, по которой ставили резерв' AFTER `status`, 
			ADD COLUMN `ida` INT(20) NOT NULL DEFAULT '0' AFTER `idz`
		" );

		}


		/**
		 * Привязка склада к компании (через сделку будут доступны позиции только этого склада)
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_sklad LIKE 'mcid'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}modcatalog_sklad 
		ADD COLUMN `mcid` INT(20) NOT NULL COMMENT 'привязка к компании' AFTER `title`, 
		ADD COLUMN `isDefault` VARCHAR(5) NOT NULL DEFAULT 'no' COMMENT 'склад по умолчанию для каждой компании' AFTER `mcid`
	" );

		$res = $db -> getAll( "SELECT id FROM {$sqlname}settings ORDER BY id" );
		foreach ( $res as $data ) {

			$mcid = $db -> getOne( "SELECT min(id) FROM {$sqlname}mycomps WHERE identity = '".$data['id']."'" );
			$db -> query( "update `{$sqlname}modcatalog_sklad` set mcid = '$mcid' where identity = '".$data['id']."'" );

			if ( $isCloud == true )
				unlink( $root."/cash/".$data['id']."/settings.all.json" );
			else unlink( $root."/cash/settings.all.json" );

		}

		/**
		 * Переходим на номера заявок
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_zayavka LIKE 'number'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}modcatalog_zayavka ADD COLUMN `number` VARCHAR(50) NOT NULL DEFAULT '0' COMMENT 'номер заявки' AFTER `id`" );

		$res = $db -> getAll( "SELECT id FROM {$sqlname}settings ORDER BY id" );
		foreach ( $res as $data ) {

			$count = 1;

			$r = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_zayavka WHERE identity = '$data[id]'" );
			foreach ( $r as $da ) {

				if ( $da['number'] < 1 ) {

					$db -> query( "update {$sqlname}modcatalog_zayavka set number = '$count' WHERE id = '$da[id]' and identity = '$identity'" );

					$count++;

				}

			}

		}

		$db -> query( "ALTER TABLE {$sqlname}modcatalog_zayavka CHANGE COLUMN `datum_priority` `datum_priority` DATE NULL DEFAULT NULL COMMENT 'желаемая дата' AFTER `datum`" );
		$db -> query( "update {$sqlname}modcatalog_zayavka set datum_priority = null WHERE datum_priority = '0000-00-00' and identity = '$identity'" );

		/**
		 * Лог перемещения между складами
		 */
		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}modcatalog_skladmove'" );
		if ( $da == 0 )
			$db -> query( "CREATE TABLE {$sqlname}modcatalog_skladmove (
		`id` INT(20) NOT NULL AUTO_INCREMENT,
		`datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
		`skladfrom` INT(20) NULL DEFAULT '0' COMMENT 'id склада с которого перемещаем',
		`skladto` INT(20) NULL DEFAULT '0' COMMENT 'id склада на который перемещаем',
		`iduser` INT(20) NULL DEFAULT '0' COMMENT 'id сотрудника, сделавшего перемещение',
		`identity` INT(20) NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	)
	COMMENT='Лог перемещения позиций меду склдами'
	ENGINE=InnoDB" );

		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}modcatalog_skladmovepoz'" );
		if ( $da == 0 )
			$db -> query( "CREATE TABLE {$sqlname}modcatalog_skladmovepoz (
		`id` INT(20) NOT NULL AUTO_INCREMENT,
		`idm` INT(20) NOT NULL DEFAULT '0' COMMENT 'id группы перемещения',
		`idp` INT(20) NOT NULL DEFAULT '0' COMMENT 'id позиции из таблицы modcatalog_skladpoz',
		`prid` INT(20) NOT NULL DEFAULT '0' COMMENT 'id позиции прайса',
		`kol` DOUBLE(20,4) NOT NULL DEFAULT '1.0000' COMMENT 'количество для общего учета',
		`identity` INT(20) NOT NULL DEFAULT '1',
		PRIMARY KEY (`id`)
	)
	COMMENT='Позиции перемещения между складами'
	ENGINE=InnoDB" );

		//общие исправления
		$db -> query( "
		ALTER TABLE {$sqlname}price 
		CHANGE COLUMN `artikul` `artikul` VARCHAR(255) NOT NULL AFTER `n_id`, 
		CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL AFTER `artikul`, 
		CHANGE COLUMN `price_3` `price_3` DOUBLE(20,2) NOT NULL DEFAULT '0' AFTER `price_2`, 
		CHANGE COLUMN `price_4` `price_4` DOUBLE(20,2) NOT NULL DEFAULT '0' AFTER `price_3`, 
		CHANGE COLUMN `price_5` `price_5` DOUBLE(20,2) NOT NULL AFTER `price_4`
	" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.15')" );

		//$currentVer = getVersion();
		$currentVer = $db -> getOne( "SELECT current FROM {$sqlname}ver ORDER BY id DESC LIMIT 1" );

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2016.15' ) {

		/**
		 * Доп.поля для хранения utm-меток в лидах
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}leads LIKE 'rezz'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}leads
		ADD COLUMN `rezz` TEXT NULL AFTER `muid`,
		ADD COLUMN `utm_source` VARCHAR(255) NOT NULL AFTER `rezz`,
		ADD COLUMN `utm_medium` VARCHAR(255) NOT NULL AFTER `utm_source`,
		ADD COLUMN `utm_campaign` VARCHAR(255) NOT NULL AFTER `utm_medium`,
		ADD COLUMN `utm_term` VARCHAR(255) NOT NULL AFTER `utm_campaign`,
		ADD COLUMN `utm_content` VARCHAR(255) NOT NULL AFTER `utm_term`,
		ADD COLUMN `utm_referrer` VARCHAR(255) NOT NULL AFTER `utm_content`
	" );

		/**
		 * Доп.поля для источников клиента
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}clientpath LIKE 'utm_source'" );
		if ( $field['Field'] == '' )
			$db -> query( "
		ALTER TABLE {$sqlname}clientpath
		CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL COMMENT 'Название источника' AFTER `id`,
		CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NOT NULL COMMENT 'Дефолтный признак' AFTER `name`,
		ADD COLUMN `utm_source` VARCHAR(255) NOT NULL COMMENT 'Связка с источником' AFTER `isDefault`,
		ADD COLUMN `destination` VARCHAR(12) NOT NULL COMMENT 'Связка с номером телефона' AFTER `utm_source`
	" );

		/**
		 * Доп.поле фильтра для сообщений
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}smtp LIKE 'filter'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}smtp ADD COLUMN `filter` VARCHAR(255) NOT NULL DEFAULT '' AFTER `divider`" );

		/**
		 * Добавим модуль Лиды в общий список модулей
		 */
		$res = $db -> getCol( "SELECT id FROM {$sqlname}settings ORDER BY id" );
		foreach ( $res as $identity ) {

			$coordinator = $db -> getOne( "SELECT coordinator FROM {$sqlname}settings WHERE id = '$identity'" );

			$isOn = ($coordinator > 0) ? "on" : "off";

			$db -> query( "INSERT INTO {$sqlname}modules SET ?u", [
				'title'    => 'Сборщик заявок',
				'content'  => '{\"leadСoordinator\":\"$coordinator\",\"leadMethod\":\"unknown\",\"leadOperator\":null,\"leadSendCoordinatorNotify\":\"yes\",\"leadSendOperatorNotify\":\"yes\"}',
				'mpath'    => 'leads',
				'icon'     => 'icon-mail-alt',
				'active'   => $isOn,
				'identity' => $identity
			] );

		}

		/**
		 * Добавим новый справочник - UTM-ссылки
		 */
		$da = $db -> getOne( "SELECT COUNT(*) AS count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' AND TABLE_NAME = '{$sqlname}leads_utm'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}leads_utm (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`clientpath` INT(20) NOT NULL DEFAULT '0' COMMENT 'id Источника из clientpath',
				`utm_source` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Название источника',
				`utm_url` VARCHAR(500) NULL DEFAULT NULL COMMENT 'Адрес целевой страницы',
				`utm_medium` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Канал кампании',
				`utm_campaign` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Название кампании',
				`utm_term` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Ключевые слова, фраза',
				`utm_content` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Доп.описание кампании',
				`site` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Адрес сайта',
				`identity` INT(20) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			COMMENT='Каталог UTM-ссылок'
			ENGINE=InnoDB
		" );

			$res = $db -> getAll( "SELECT * FROM {$sqlname}clientpath ORDER BY identity" );
			foreach ( $res as $path ) {

				$db -> query( "INSERT INTO {$sqlname}leads_utm SET ?u", [
					'clientpath' => $path['id'] + 0,
					'utm_source' => $path['utm_source'],
					'identity'   => $path['identity']
				] );

			}

		}

		/**
		 * Фикс таблицы leads
		 */
		try {

			$db -> query( "
			ALTER TABLE {$sqlname}leads
			CHANGE COLUMN `title` `title` VARCHAR(255) NULL DEFAULT '' AFTER `rezult`,
			CHANGE COLUMN `email` `email` VARCHAR(255) NULL DEFAULT '' AFTER `title`,
			CHANGE COLUMN `phone` `phone` VARCHAR(255) NULL DEFAULT '' AFTER `email`,
			CHANGE COLUMN `site` `site` VARCHAR(255) NULL DEFAULT '' AFTER `phone`,
			CHANGE COLUMN `company` `company` VARCHAR(255) NULL DEFAULT '' AFTER `site`,
			CHANGE COLUMN `description` `description` TEXT NULL DEFAULT '' AFTER `company`,
			CHANGE COLUMN `ip` `ip` VARCHAR(16) NULL DEFAULT '' AFTER `description`,
			CHANGE COLUMN `city` `city` VARCHAR(100) NULL DEFAULT '' AFTER `ip`,
			CHANGE COLUMN `country` `country` VARCHAR(255) NULL DEFAULT '' AFTER `city`,
			CHANGE COLUMN `timezone` `timezone` VARCHAR(5) NULL DEFAULT '' AFTER `country`,
			CHANGE COLUMN `iduser` `iduser` INT(11) NULL DEFAULT '0' AFTER `timezone`
		" );

			$db -> query( "
			ALTER TABLE {$sqlname}leads
			CHANGE COLUMN `clientpath` `clientpath` INT(20) NOT NULL DEFAULT '0' AFTER `iduser`,
			CHANGE COLUMN `pid` `pid` INT(20) NOT NULL DEFAULT '0' AFTER `clientpath`,
			CHANGE COLUMN `clid` `clid` INT(20) NOT NULL DEFAULT '0' AFTER `pid`,
			CHANGE COLUMN `did` `did` INT(20) NOT NULL DEFAULT '0' AFTER `clid`,
			CHANGE COLUMN `partner` `partner` INT(20) NOT NULL DEFAULT '0' AFTER `did`,
			CHANGE COLUMN `muid` `muid` VARCHAR(255) NOT NULL DEFAULT '' AFTER `partner`,
			CHANGE COLUMN `rezz` `rezz` TEXT NOT NULL DEFAULT '' AFTER `muid`,
			CHANGE COLUMN `utm_source` `utm_source` VARCHAR(255) NULL DEFAULT '' AFTER `rezz`,
			CHANGE COLUMN `utm_medium` `utm_medium` VARCHAR(255) NULL DEFAULT '' AFTER `utm_source`,
			CHANGE COLUMN `utm_campaign` `utm_campaign` VARCHAR(255) NULL DEFAULT '' AFTER `utm_medium`,
			CHANGE COLUMN `utm_term` `utm_term` VARCHAR(255) NULL DEFAULT '' AFTER `utm_campaign`,
			CHANGE COLUMN `utm_content` `utm_content` VARCHAR(255) NULL DEFAULT '' AFTER `utm_term`,
			CHANGE COLUMN `utm_referrer` `utm_referrer` VARCHAR(255) NULL DEFAULT ''
		" );

			$res = $db -> getAll( "SELECT * FROM {$sqlname}clientpath ORDER BY identity" );
			foreach ( $res as $path ) {

				$db -> query( "INSERT INTO {$sqlname}leads_utm (id,clientpath,utm_source,identity) VALUES(NULL, '".$path['id']."', '".$path['utm_source']."', '".$path['identity']."')" );

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		/**
		 * Фикс бага со сделками, у которых нет привязки к Клиенту
		 */
		$res = $db -> getAll( "SELECT did, clid, payer FROM {$sqlname}dogovor WHERE clid < 1" );
		foreach ( $res as $da ) {

			$db -> query( "UPDATE {$sqlname}dogovor SET clid = '$da[payer]' WHERE did = '$da[did]'" );

		}

		/**
		 * Доп.поле в модули
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modules LIKE 'secret'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}modules ADD COLUMN `secret` VARCHAR(255) NOT NULL AFTER `active`" );

		/**
		 * Корректировка в таблице заявок
		 */
		$db -> query( "ALTER TABLE {$sqlname}leads CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`" );


		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.16')" );

		//$currentVer = getVersion();
		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2016.16' ) {

		/**
		 * Доп.поле обращений
		 */
		try {
			$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}entry LIKE 'autor'" );
			if ( $field['Field'] == '' )
				$db -> query( "ALTER TABLE {$sqlname}entry ADD COLUMN `autor` INT(20) NOT NULL AFTER `iduser`" );
		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		try {

			$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}entry LIKE 'datum_do'" );
			if ( $field['Field'] == '' ) {

				$db -> query( "ALTER TABLE {$sqlname}entry CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `did`, ADD COLUMN `datum_do` TIMESTAMP NOT NULL AFTER `datum`" );
				$db -> query( "ALTER TABLE {$sqlname}entry ADD COLUMN `status` INT(3) NOT NULL DEFAULT '0' AFTER `content`" );

			}

		}
		catch ( Exception $e ) {
			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.__LINE__."<br>";
		}

		$res = $db -> getAll( "SELECT ide, iduser, datum, did FROM {$sqlname}entry" );
		foreach ( $res as $da ) {

			if ( $da['did'] > 0 ) {
				$status   = 1;
				$datum_do = $da['datum'];
			}
			elseif ( abs( difftimefull( $da['datum'] ) / 24 ) > 5 && $da['did'] == 0 ) {
				$status   = 2;
				$datum_do = $da['datum'];
			}
			else {
				$status   = 0;
				$datum_do = '';
			}

			$db -> query( "UPDATE {$sqlname}entry SET autor = '$da[iduser]', status = '$status', datum_do = '$datum_do' WHERE ide = '$da[ide]'" );

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.17')" );

		//$currentVer = getVersion();
		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2016.17' ) {

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.20')" );

		//$currentVer = getVersion();
		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2016.20' ) {

		/**
		 * Доп.таблицу плагинов
		 */
		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}plugins'" );
		if ( $da[0] == 0 ) {

			$db -> query( "CREATE TABLE `{$sqlname}plugins` (`id` INT(20) NOT NULL AUTO_INCREMENT,`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,`name` VARCHAR(50) NOT NULL DEFAULT '0',`active` VARCHAR(5) NOT NULL DEFAULT 'off',`identity` INT(20) NOT NULL DEFAULT '1',PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=InnoDB" );

		}

		/**
		 * Добавляем таблицу хуков
		 */
		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}webhook'" );
		if ( $da[0] == 0 ) {

			$db -> query( "CREATE TABLE `{$sqlname}webhook` (`id` INT(20) NOT NULL AUTO_INCREMENT,`title` VARCHAR(255) NULL DEFAULT 'event',`event` VARCHAR(255) NULL DEFAULT NULL,`url` TINYTEXT NULL,`identity` INT(20) NOT NULL DEFAULT '1',PRIMARY KEY (`id`)) COLLATE ='utf8_general_ci' ENGINE=InnoDB" );

		}

		/**
		 * Добавляем таблицу логов по хукам
		 */
		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}webhooklog'" );
		if ( $da[0] == 0 ) {

			$db -> query( "CREATE TABLE {$sqlname}webhooklog (`id` INT(30) NOT NULL AUTO_INCREMENT,`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `event` VARCHAR(50) NOT NULL, `query` TEXT NOT NULL,`response` TEXT NOT NULL,`identity` INT(20) NOT NULL DEFAULT '1',PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=InnoDB" );

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.21')" );

		//$currentVer = getVersion();
		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2016.21' ) {

		/**
		 * Добавляем таблицу шаблонов для почтовика
		 */
		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}ymail_tpl'" );
		if ( $da[0] == 0 ) {

			$db -> query( "CREATE TABLE {$sqlname}ymail_tpl (`id` INT(20) NOT NULL AUTO_INCREMENT, `name` VARCHAR(255) NULL DEFAULT 'Template', `content` TEXT NULL, `share` VARCHAR(5) NULL DEFAULT 'no', `iduser` INT(20) NULL DEFAULT NULL, `identity` INT(20) NULL DEFAULT '1', PRIMARY KEY (`id`), INDEX `identity` (`identity`)) ENGINE=InnoDB" );

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2016.25')" );

		//$currentVer = getVersion();
		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}

	/*2017*/

	if ( getVersion() == '2016.25' ) {

		/**
		 * mcrypt_decrypt Не будет работать в PHP >= 7.1.0
		 * Не работает в PHP 5.6
		 *
		 * @param $text
		 * @param $key
		 * @param $iv
		 * @return string
		 */
		function rij_decrypt_ext($text, $key, $iv) {

			$decrypttext = openssl_decrypt( base64_decode( $text ), 'AES-256-CTR', $key, OPENSSL_RAW_DATA, base64_decode( $iv ) );

			return $decrypttext;
		}

		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}callhistory` LIKE 'res'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE `{$sqlname}callhistory` ADD `res` VARCHAR(100) NOT NULL AFTER `iduser`, ADD `sec` INT(20) NOT NULL AFTER `res`, ADD `file` TEXT NOT NULL AFTER `sec`, ADD `src` VARCHAR(20) NOT NULL AFTER `file`, ADD `dst` VARCHAR(20) NOT NULL AFTER `src`" );

		/**
		 * Добавляем индексы в историю звонков
		 */
		$r = $db -> getALL( "SHOW INDEX FROM {$sqlname}callhistory WHERE Key_name = 'statistica'" );
		if ( empty( $r ) ) {

			$db -> query( "
			ALTER TABLE {$sqlname}callhistory
			ADD INDEX `statistica` (`phone`, `datum`, `iduser`, `res`, `direct`, `identity`),
			ADD INDEX `statistica2` (`direct`, `datum`, `iduser`, `identity`)
		" );

		}

		/**
		 * Меняем движок для истории звонков
		 */
		$db -> query( "ALTER TABLE `{$sqlname}callhistory` ENGINE=InnoDB" );

		/**
		 * Перекодируем все пароли для каждого аккаунта
		 */
		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			$nivc  = rij_iv();
			$oivc  = $data['ivc'];
			$fpath = $data['id']."/";

			$db -> query( "update {$sqlname}settings set ivc = '$nivc' where id = '$data[id]'" );

			unlink( $root."/cash/".$fpath."settings.all.json" );

			//перекодируем данные в таблице sip
			$res1       = $db -> getRow( "SELECT * FROM {$sqlname}sip WHERE identity = '$data[id]'" );
			$sip_user   = rij_crypt( rij_decrypt_ext( $res1["sip_user"], $skey, $oivc ), $skey, $nivc );
			$sip_secret = rij_crypt( rij_decrypt_ext( $res1["sip_secret"], $skey, $oivc ), $skey, $nivc );

			$db -> query( "update {$sqlname}sip set sip_user = '$sip_user', sip_secret = '$sip_secret' where identity = '$data[id]'" );

			//перекодируем данные в таблице smtp
			$res2 = $db -> query( "SELECT * FROM {$sqlname}smtp WHERE identity = '$data[id]'" );
			while ($data2 = $db -> fetch( $res2 )) {

				$smtp_user = rij_crypt( rij_decrypt_ext( $data2['smtp_user'], $skey, $oivc ), $skey, $nivc );
				$smtp_pass = rij_crypt( rij_decrypt_ext( $data2['smtp_pass'], $skey, $oivc ), $skey, $nivc );

				$db -> query( "update {$sqlname}smtp set smtp_user = '$smtp_user', smtp_pass = '$smtp_pass' where id = '$data2[id]'" );

			}

			//перекодируем данные в таблице services
			$res3 = $db -> query( "SELECT * FROM {$sqlname}services WHERE identity = '$data[id]'" );
			while ($data3 = $db -> fetch( $res3 )) {

				$user_id  = rij_crypt( rij_decrypt_ext( $data3['user_id'], $skey, $oivc ), $skey, $nivc );
				$user_key = rij_crypt( rij_decrypt_ext( $data3['user_key'], $skey, $oivc ), $skey, $nivc );

				$db -> query( "update {$sqlname}services set user_id = '$user_id', user_key = '$user_key' where id = '$data3[id]'" );

			}

			//перекодируем данные в таблице ymail_settings
			$res3 = $db -> query( "SELECT * FROM {$sqlname}ymail_settings WHERE identity = '".$data['id']."'" );
			while ($data3 = $db -> fetch( $res3 )) {

				$set = json_decode( $data3['settings'], true );

				$set['ymailUser'] = rij_crypt( rij_decrypt_ext( $set['ymailUser'], $skey, $oivc ), $skey, $nivc );
				$set['ymailPass'] = rij_crypt( rij_decrypt_ext( $set['ymailPass'], $skey, $oivc ), $skey, $nivc );

				$set = json_encode_cyr( $set );

				$db -> query( "update {$sqlname}ymail_settings set settings = '$set' where id = '$data3[id]'" );

				unlink( $root."/cash/".$fpath."settings.ymail.".$data3['iduser'].".json" );

			}

		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2017.00')" );

		//$currentVer = getVersion();
		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2017.00' ) {

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}multisteps'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
			CREATE TABLE `{$sqlname}multisteps` (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Название цепочки',
				`direction` INT(10) NULL DEFAULT NULL COMMENT 'id from _direction Направление',
				`tip` INT(10) NULL DEFAULT NULL COMMENT 'tid from _dogtips Тип сделки',
				`steps` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Набор этапов',
				`isdefault` VARCHAR(5) NULL DEFAULT NULL COMMENT 'id этапа по умолчанию',
				`identity` INT(10) NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB
		" );

		}

		/**
		 * добавим поле для привязки договора к компании (при создании не из сделки)
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}contract` LIKE 'mcid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE `{$sqlname}contract` ADD COLUMN `mcid` INT(20) NOT NULL AFTER `crid`" );

		/**
		 * добавим поле для обращений
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}entry` LIKE 'uid'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}entry ADD COLUMN `uid` INT(20) NULL AFTER `ide`" );

		/**
		 * исправляем кодировку таблицы
		 */
		$db -> query( "ALTER TABLE {$sqlname}ymail_tpl COLLATE='utf8_general_ci'" );

		/**
		 * исправляем таблицы почтовика
		 */
		$db -> query( "ALTER TABLE {$sqlname}ymail_settings ENGINE=InnoDB" );
		$db -> query( "ALTER TABLE {$sqlname}ymail_messages
		CHANGE COLUMN `subbolder` `subbolder` VARCHAR(255) NULL DEFAULT NULL AFTER `state`,
		CHANGE COLUMN `hid` `hid` INT(30) NULL DEFAULT NULL AFTER `uid`,
		CHANGE COLUMN `parentmid` `parentmid` VARCHAR(255) NULL DEFAULT NULL AFTER `hid`,
		CHANGE COLUMN `fid` `fid` TEXT NULL DEFAULT NULL AFTER `iduser`;
	" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2017.3')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2017.3' ) {

		/**
		 * Добавим таблицу хранения различных настроек
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}customsettings'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE `{$sqlname}customsettings` (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

		/**
		 * Добавим запись для базовой конфигурации экспресс-формы
		 */
		$result = $db -> query( "SELECT id FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			$params = $db -> getOne( "select params from {$sqlname}customsettings where tip='eform' and identity = '$data[id]'" );

			if ( $params == '' ) {

				/**
				 * Настройки экспресс-формы по умолчанию
				 */
				$edef = [
					"client" => [
						"title"      => [
							"active"   => "yes",
							"requered" => "yes",
							"more"     => "no",
							"comment"  => "Должно быть всегда включено и видимо",
						],
						"phone"      => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"fax"        => [
							"active"   => "no",
							"requered" => "no",
							"more"     => "no",
						],
						"mail_url"   => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"clientpath" => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"tip_cmr"    => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"idcategory" => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"des"        => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "yes",
						],
						"territory"  => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "yes",
						],
						"address"    => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "yes",
						],
						"site_url"   => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "yes",
						],
						"head_clid"  => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "yes",
						]
					],
					"person" => [
						"person"  => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
							"comment"  => "Должно быть всегда включено и видимо",
						],
						"ptitle"  => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"tel"     => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"mob"     => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"mail"    => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"loyalty" => [
							"active"   => "yes",
							"requered" => "no",
							"more"     => "no",
						],
						"rol"     => [
							"active"   => "no",
							"requered" => "no",
							"more"     => "no",
						]
					]
				];

				$a['client'] = array_keys( $edef['client'] );
				$a['person'] = array_keys( $edef['person'] );

				//массив из имен всех полей, доступных для формы
				$fdef = $params = [];

				$fclient = array_map( function($element) {
					return "'".$element."'";
				}, $a['client'] );
				$fperson = array_map( function($element) {
					return "'".$element."'";
				}, $a['person'] );

				$fdef = array_merge( $fclient, $fperson );

				//доп.поля формы
				$re = $db -> query( "select * from {$sqlname}field where fld_tip IN ('client', 'person') and fld_on='yes' and (fld_name LIKE '%input%' or fld_name IN (".implode( ",", $fdef ).")) and identity = '$data[id]' order by fld_order" );
				while ($da = $db -> fetch( $re )) {

					$active = $req = 'no';
					$more   = 'yes';

					if ( in_array( $da['fld_name'], $a[ $da['fld_tip'] ] ) ) {

						$active = $edef[ $da['fld_tip'] ][ $da['fld_name'] ]['active'];
						$req    = $edef[ $da['fld_tip'] ][ $da['fld_name'] ]['requered'];
						$more   = $edef[ $da['fld_tip'] ][ $da['fld_name'] ]['more'];

					}

					if ( $da['fld_tip'] == 'person' && in_array( $da['fld_name'], [
							"fax",
							"clientpath"
						] ) )
						goto a;

					$params[ $da['fld_tip'] ][ $da['fld_name'] ] = [
						"active"   => $active,
						"requered" => $req,
						"more"     => $more,
					];

					a:

				}

				$db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", [
					"tip"      => "eform",
					"params"   => json_encode( $params ),
					"identity" => $data['id']
				] );

				/**
				 * Добавим настройки для полей сделок, требуемых при смене этапа
				 */
				$db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", [
					"tip"      => "dfieldsstep",
					"identity" => $data['id']
				] );

			}

		}

		/**
		 * Базовые настройки виджетов рабочего стола
		 */
		$oldvigets = [
			"d1"  => "voronka",
			"d2"  => "analitic",
			"d3"  => "bethday",
			"d4"  => "dogs_renew",
			"d5"  => "credit",
			"d6"  => "stat",
			"d7"  => "prognoz",
			"d8"  => "payment",
			"d9"  => "dogsclosed",
			"d10" => "voronka_conus",
			"d11" => "history",
			"d12" => "voronka_classic",
			"d13" => "raiting_payment",
			"d14" => "raiting_potential"
		];

		$result = $db -> query( "SELECT iduser, viget_on, viget_order, usersettings FROM {$sqlname}user ORDER BY iduser" );
		while ($data = $db -> fetch( $result )) {

			$newvigets = [];

			$uset = json_decode( $data['usersettings'], true );

			if ( empty( $uset['vigets'] ) ) {

				$vgt   = yexplode( ";", $data['viget_order'] );
				$vgtOn = yexplode( ";", $data['viget_on'] );

				foreach ( $vgt as $key => $viget ) {

					$newvigets[ strtr( $viget, $oldvigets ) ] = $vgtOn[ $key ];

				}

				$uset['vigets'] = $newvigets;

				$db -> query( "UPDATE {$sqlname}user SET ?u WHERE iduser = '$data[iduser]'", ["usersettings" => json_encode_cyr( $uset )] );

				unlink( $root."/cash/".$fpath."settings.user.".$data['iduser'].".json" );

			}

		}

		/**
		 * Доступы к отчетам
		 */

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}reports LIKE 'roles'" );
		if ( $field['Field'] == '' ) {

			$db -> query( "ALTER TABLE {$sqlname}reports ALTER `title` DROP DEFAULT, ALTER `file` DROP DEFAULT, ALTER `ron` DROP DEFAULT, ALTER `category` DROP DEFAULT" );
			$db -> query( "
			ALTER TABLE `{$sqlname}reports`
			CHANGE COLUMN `title` `title` VARCHAR(100) NOT NULL COMMENT 'название отчета' AFTER `rid`,
			CHANGE COLUMN `file` `file` VARCHAR(100) NOT NULL COMMENT 'файл отчета' AFTER `title`,
			CHANGE COLUMN `ron` `ron` VARCHAR(5) NOT NULL COMMENT 'активность отчета' AFTER `file`,
			CHANGE COLUMN `category` `category` VARCHAR(20) NOT NULL COMMENT 'раздел' AFTER `ron`,
			ADD COLUMN `roles` TEXT NULL COMMENT 'Роли сотрудников с доступом к отчету' AFTER `category`,
			ADD COLUMN `users` VARCHAR(255) NULL DEFAULT NULL COMMENT 'id сотрудников, у которых есть доступ к отчету' AFTER `roles`
		" );

		}

		/**
		 * Закрепление статьи Базы знаний
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM `{$sqlname}knowledgebase` LIKE 'pin'" );
		if ( $field['Field'] == '' ) {
			$db -> query( "
			ALTER TABLE {$sqlname}knowledgebase
			ADD COLUMN `pin` VARCHAR(5) NULL DEFAULT 'no' COMMENT 'Закрепление статьи' AFTER `active`,
			ADD COLUMN `pindate` DATETIME NULL DEFAULT NULL COMMENT 'Дата закрепления статьи' AFTER `pin`
		" );
		}

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2017.6')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><strong class="red">главную страницу</strong></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.
		<br><div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2017.6' ) {

		/**
		 * Удаляем модуль Онлайн-консультант
		 */
		$db -> query( "DELETE FROM {$sqlname}modules WHERE mpath = 'wcdialog'" );

		$db -> query( "DROP TABLE IF EXISTS {$sqlname}wcdialogs" );
		$db -> query( "DROP TABLE IF EXISTS {$sqlname}wcdialogs_set" );
		$db -> query( "DROP TABLE IF EXISTS {$sqlname}wcdialogs_sites" );
		$db -> query( "DROP TABLE IF EXISTS {$sqlname}wcdialogs_users" );

		/**
		 * Добавляем архивный признак в прайс
		 * ALTER TABLE `app_price` ADD COLUMN `archive` VARCHAR(3) NOT NULL DEFAULT 'no' AFTER `nds`
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}price LIKE 'archive'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}price ADD COLUMN `archive` VARCHAR(3) NOT NULL DEFAULT 'no' AFTER `nds`" );

		/**
		 * Разные исправления
		 */
		$db -> query( "ALTER TABLE {$sqlname}tasks CHANGE `alert` `alert` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'yes';" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2017.9')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
		<div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2017.9' ) {

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2017.10')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
		<div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}

	/*2018*/

	if ( getVersion() == '2017.10' ) {

		/**
		 * Добавим таблицу для статусов документов
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}contract_status'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}contract_status (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата изменения',
				`datumdo` TIMESTAMP NULL DEFAULT NULL,
				`tip` TEXT NULL DEFAULT NULL COMMENT 'типы документов',
				`title` VARCHAR(100) NULL COMMENT 'название статуса',
				`color` VARCHAR(7) NULL COMMENT 'цвет статуса',
				`ord` INT(20) NULL COMMENT 'порядок вывода статуса',
				`identity` INT(20) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			DEFAULT CHARSET='utf8'
			COMMENT='Статусы документов по типам'
			ENGINE=InnoDB
		" );

		}

		/**
		 * Начальные статусы для аккаунтов
		 */
		/*
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:00:08', '2;9;1;46;47', 'Создан', '#9edae5', 1, 1);
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:00:46', '1;46;47', 'Утвержден руководителем', '#17becf', 2, 1);
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:08:46', '2;9;1;47;46', 'Отправлен клиенту (электронно)', '#008000', 3, 1);
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:12:39', '2;9;1;46;47', 'Распечатан', '#95b3d7', 4, 1);
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:13:18', '2;9;1;46;47', 'Отправлен клиенту (оригиналы)', '#548dd4', 5, 1);
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:13:55', '2;9;1;46;47', 'Получен от клиента (оригиналы)', '#ff6600', 6, 1);
	INSERT INTO `app_contract_status` VALUES ('2017-12-13 10:46:08', '47;2;9;1;46', 'В архиве', '#999999', 7, 1);
	*/

		$app_contract_status = [
			[
				'datum'    => '2017-12-13 10:00:08',
				'tip'      => '2;9;1;46;47',
				'title'    => 'Создан',
				'color'    => '#9edae5',
				'ord'      => 1,
				'identity' => 1,
			],
			[
				'datum'    => '2017-12-13 10:00:46',
				'tip'      => '1;46;47',
				'title'    => 'Утвержден руководителем',
				'color'    => '#17becf',
				'ord'      => 2,
				'identity' => 1,
			],
			[
				'datum'    => '2017-12-13 10:08:46',
				'tip'      => '2;9;1;47;46',
				'title'    => 'Отправлен клиенту (электронно)',
				'color'    => '#008000',
				'ord'      => 3,
				'identity' => 1,
			],
			[
				'datum'    => '2017-12-13 10:12:39',
				'tip'      => '2;9;1;46;47',
				'title'    => 'Распечатан',
				'color'    => '#95b3d7',
				'ord'      => 4,
				'identity' => 1,
			],
			[
				'datum'    => '2017-12-13 10:13:18',
				'tip'      => '2;9;1;46;47',
				'title'    => 'Отправлен клиенту (оригиналы)',
				'color'    => '#548dd4',
				'ord'      => 5,
				'identity' => 1,
			],
			[
				'datum'    => '2017-12-13 10:13:55',
				'tip'      => '2;9;1;46;47',
				'title'    => 'Получен от клиента (оригиналы)',
				'color'    => '#ff6600',
				'ord'      => 6,
				'identity' => 1,
			],
			[
				'datum'    => '2017-12-13 10:46:08',
				'tip'      => '47;2;9;1;46',
				'title'    => 'В архиве',
				'color'    => '#999999',
				'ord'      => 7,
				'identity' => 1,
			]
		];


		/**
		 * Добавим таблицу для лога статусов документов
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}contract_statuslog'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}contract_statuslog (
				`id` INT(10) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`deid` INT(10) NULL DEFAULT NULL COMMENT 'id документа',
				`status` INT(10) NULL DEFAULT NULL COMMENT 'новый статус',
				`oldstatus` INT(10) NULL DEFAULT NULL COMMENT 'старый статус',
				`iduser` INT(10) NULL DEFAULT NULL COMMENT 'id сотрудника',
				`des` TEXT NULL COMMENT 'комментарий',
				`identity` INT(10) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			DEFAULT CHARSET='utf8'
			COMMENT='Лог изменения статуса документов'
			ENGINE=InnoDB
		" );

		}

		/**
		 * Добавим колонку статуса для документов
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}contract LIKE 'status'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}contract ADD COLUMN `status` INT(10) NULL DEFAULT NULL AFTER `mcid`" );

		/**
		 * Добавим колонку Канала продаж для Бюджета
		 * ALTER TABLE `app_budjet_cat` ADD COLUMN `clientpath` INT(10) NULL AFTER `tip`;
		 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}budjet_cat LIKE 'clientpath'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}budjet_cat ADD COLUMN `clientpath` INT(10) NULL AFTER `tip`" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2018.1')" );

		$currentVer = getVersion();

		if ( $currentVer == $lastVer ) {

			$message = 'Обновление до версии '.$currentVer.' установлено. Вернитесь на <a href="/"><b class="red">главную страницу</b></a> или Перезагрузите её. Подробности об обновлении смотрите в новостях на сайте проекта - www.isaler.ru';

		}
		else {

			$message = 'Обновление до версии '.$currentVer.' установлено.<br>
		<div class="main_div div-center">
			<A href="update.php?step=1" class="button"><b>Продолжить</b> установку</A>
		</div>
		';

		}

	}
	if ( getVersion() == '2018.1' ) {

		/**
		 * Добавим таблицу для лога поиска дублей
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}doubles'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}doubles (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
				`tip` TEXT NULL DEFAULT NULL COMMENT 'типы дубля',
				`idmain` INT(10) NULL DEFAULT NULL COMMENT 'id проверяемой записи',
				`list` VARCHAR(500) NULL COMMENT 'json-массив найденных дублей',
				`ids` VARCHAR(100) NULL DEFAULT NULL COMMENT 'список всех id, упомятутых в list',
				`status` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'статус',
				`datumdo` TIMESTAMP NULL DEFAULT NULL COMMENT 'дата обработки',
				`des` TEXT NULL COMMENT 'комментарий',
				`iduser` VARCHAR(10) NULL DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
				`identity` INT(20) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`),
				INDEX `filter` (`id`, `tip`(10), `idmain`, `ids`)
			)
			DEFAULT CHARSET='utf8'
			COMMENT='Лог поиска дублей'
			ENGINE=InnoDB
		" );

		}

		/**
		 * Добавляем NULL для некоторых таблиц
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}modworkplan'" );
		if ( $da != 0 )
			$db -> query( "ALTER TABLE {$sqlname}modworkplan CHANGE `date_end` `date_end` DATE NULL COMMENT 'Дата завершения плановая'" );

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}mango_log'" );
		if ( $da != 0 )
			$db -> query( "
		ALTER TABLE {$sqlname}mango_log
		CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL AFTER `call_id`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`
	" );

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}telfin_log'" );
		if ( $da != 0 )
			$db -> query( "
		ALTER TABLE {$sqlname}telfin_log
		CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL COMMENT 'Внутренний номер сотрудника' AFTER `datum`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`
	" );

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}yandextel_log'" );
		if ( $da != 0 )
			$db -> query( "
		ALTER TABLE {$sqlname}yandextel_log
		CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL COMMENT 'Внутренний номер сотрудника' AFTER `datum`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`
	" );

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}gravitel_log'" );
		if ( $da != 0 )
			$db -> query( "
		ALTER TABLE {$sqlname}gravitel_log
		CHANGE COLUMN `extension` `extension` VARCHAR(10) NULL COMMENT 'Номер сотрудника' AFTER `callid`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL COMMENT 'Из базы клиент (clientcat.clid)' AFTER `type`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL COMMENT 'Из базы контакт (personcat.pid)' AFTER `clid`
	" );

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}callhistory LIKE 'did'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}callhistory ADD COLUMN `did` VARCHAR(50) NULL COMMENT 'номер телефона наш если в src добавочный' AFTER `uid`" );

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}callhistory'" );
		if ( $da != 0 )
			$db -> query( "
		ALTER TABLE {$sqlname}callhistory
		CHANGE COLUMN `did` `did` VARCHAR(50) NULL AFTER `uid`,
		CHANGE COLUMN `clid` `clid` INT(20) NULL AFTER `datum`,
		CHANGE COLUMN `pid` `pid` INT(20) NULL AFTER `clid`,
		CHANGE COLUMN `iduser` `iduser` INT(20) NULL AFTER `pid`
	" );

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}entry'" );
		if ( $da != 0 )
			$db -> query( "ALTER TABLE {$sqlname}entry CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `did`" );

		/**
		 * Таблицы, для хранения базовых KPI
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}kpibase'" );
		if ( $da == 0 ) {

			$db -> query( "
		CREATE TABLE `{$sqlname}kpibase` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Название показателя',
			`tip` VARCHAR(20) NULL DEFAULT NULL COMMENT 'Тип показателя',
			`values` TEXT NULL COMMENT 'Список значений показателя для расчетов',
			`subvalues` TEXT NULL COMMENT 'Список дополнительных значений',
			`identity` INT(10) NULL DEFAULT NULL COMMENT 'ID аккаунта',
			PRIMARY KEY (`id`)
		)
		DEFAULT CHARSET='utf8'
		COMMENT='Базовые показатели KPI'
		ENGINE=InnoDB
		" );

		}

		//ALTER TABLE `app_kpibase` ADD COLUMN `subvalues` TEXT NULL COMMENT 'Список дополнительных значений' AFTER `values`;

		/**
		 * Хранилище KPI для сотрудников
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}kpi'" );
		if ( $da == 0 ) {

			$db -> query( "
		CREATE TABLE `{$sqlname}kpi` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`kpi` INT(5) NULL DEFAULT NULL COMMENT 'ID показателя',
			`year` INT(4) NULL DEFAULT NULL COMMENT 'Год',
			`period` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Период расчета',
			`iduser` INT(10) NULL DEFAULT NULL COMMENT 'ID сотрудника (iduser)',
			`val` INT(10) NULL DEFAULT NULL COMMENT 'Значение показателя',
			`isPersonal` TINYINT(1) NULL DEFAULT '0' COMMENT 'Признок персонального показателя',
			`identity` INT(10) NULL DEFAULT NULL COMMENT 'ID аккаунта',
			PRIMARY KEY (`id`)
		)
		DEFAULT CHARSET='utf8'
		COMMENT='База KPI сотрудников'
		ENGINE=MyISAM
		" );

		}

		//ALTER TABLE `app_kpi` ADD COLUMN `period` VARCHAR(10) NULL DEFAULT NULL COMMENT 'Период расчета' AFTER `year`
		//ALTER TABLE `app_kpi` ADD COLUMN `isPersonal` TINYINT(1) NULL DEFAULT '0' COMMENT 'Признок персонального показателя' AFTER `val`;

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2018.3')" );

		$currentVer = getVersion();

		$sapi = php_sapi_name();

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
	if ( getVersion() == '2018.3' ) {

		/**
		 * Добавим таблицу для списка базовых анкет
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}deal_anketa_list'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}deal_anketa_list (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`active` INT(1) NOT NULL DEFAULT '1' COMMENT 'Активность анкеты',
				`datum` DATETIME NOT NULL COMMENT 'Дата создания',
				`datum_edit` DATETIME NOT NULL COMMENT 'Дата изменения',
				`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Название анкеты',
				`content` TEXT NULL COMMENT 'Описание анкеты',
				`iduser` INT(10) NULL DEFAULT NULL COMMENT 'id Сотрудника-автора',
				`identity` INT(10) NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			DEFAULT CHARSET='utf8'
			COMMENT='Список базовых анкет для сделок'
			ENGINE=InnoDB
		" );

		}

		/**
		 * Добавим таблицу для списка базовых анкет
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}deal_anketa_base'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}deal_anketa_base (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`block` INT(11) NOT NULL DEFAULT '0' COMMENT 'id блока',
				`ida` INT(11) NOT NULL COMMENT 'id анкеты',
				`name` VARCHAR(255) NOT NULL COMMENT 'Название поля',
				`tip` VARCHAR(10) NOT NULL COMMENT 'Тип поля',
				`value` TEXT NULL COMMENT 'Возможные значения',
				`ord` INT(5) NULL DEFAULT NULL COMMENT 'Порядок вывода',
				`pole` VARCHAR(10) NULL DEFAULT NULL COMMENT 'id поля',
				`pwidth` INT(3) NULL DEFAULT '50' COMMENT 'ширина поля',
				`identity` INT(30) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			DEFAULT CHARSET='utf8'
			COMMENT='База полей для анкеты'
			ENGINE=InnoDB
		" );

		}

		/**
		 * Добавим таблицу для списка базовых анкет
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}deal_anketa'" );
		if ( $da == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}deal_anketa (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`idbase` INT(11) NOT NULL DEFAULT '0' COMMENT 'id поля анкеты',
				`ida` INT(10) NOT NULL COMMENT 'id анкеты',
				`did` INT(11) NULL DEFAULT NULL COMMENT 'id сделки',
				`clid` INT(11) NULL DEFAULT NULL COMMENT 'id клиента',
				`value` VARCHAR(255) NULL,
				`identity` INT(30) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			DEFAULT CHARSET='utf8'
			COMMENT='Значения для анкет по сделкам'
			ENGINE=InnoDB
		" );

		}

		$db -> query( "ALTER TABLE {$sqlname}credit CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL COMMENT 'дата счета' AFTER `pid`;" );

		//ALTER TABLE `app_deal_anketa_base` ADD COLUMN `block` INT(11) NOT NULL DEFAULT '0' COMMENT 'id блока' AFTER `id`;
		//ALTER TABLE `app_file` ADD COLUMN `datum` DATETIME NULL AFTER `folder`, ADD COLUMN `size` INT(7) NULL AFTER `datum`;

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}file LIKE 'size'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}file ADD COLUMN `datum` DATETIME NULL AFTER `folder`, ADD COLUMN `size` INT(7) NULL AFTER `datum`" );

		/**
		 * Исправление кодировок в таблицах InnoDB
		 */

		$db -> query( "ALTER TABLE {$sqlname}doubles CHANGE `tip` `tip` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'типы дубля'" );
		$db -> query( "ALTER TABLE {$sqlname}doubles CHANGE `list` `list` VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'json-массив найденных дублей'" );
		$db -> query( "ALTER TABLE {$sqlname}doubles CHANGE `ids` `ids` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'список всех id, упомятутых в list'" );
		$db -> query( "ALTER TABLE {$sqlname}doubles CHANGE `status` `status` VARCHAR(3) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'no' COMMENT 'статус'" );
		$db -> query( "ALTER TABLE {$sqlname}doubles CHANGE `des` `des` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'комментарий'" );

		$db -> query( "ALTER TABLE {$sqlname}kpi CHANGE `period` `period` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Период расчета'" );

		$db -> query( "ALTER TABLE {$sqlname}deal_anketa_list CHANGE `title` `title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Название анкеты'" );
		$db -> query( "ALTER TABLE {$sqlname}deal_anketa_list CHANGE `content` `content` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'Описание анкеты'" );

		$db -> query( "ALTER TABLE {$sqlname}deal_anketa_base CHANGE `name` `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Название поля'" );
		$db -> query( "ALTER TABLE {$sqlname}deal_anketa_base CHANGE `tip` `tip` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'Тип поля'" );
		$db -> query( "ALTER TABLE {$sqlname}deal_anketa_base CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL COMMENT 'Возможные значения'" );
		$db -> query( "ALTER TABLE {$sqlname}deal_anketa_base CHANGE `pole` `pole` VARCHAR(10) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'id поля'" );

		$db -> query( "ALTER TABLE {$sqlname}deal_anketa CHANGE `value` `value` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL" );


		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2018.6')" );

		$currentVer = getVersion();

		$sapi = php_sapi_name();

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
	if ( getVersion() == '2018.6' ) {

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2018.9')" );

		$currentVer = getVersion();

		$sapi = php_sapi_name();

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

	/*2019*/

	if ( getVersion() == '2018.9' ) {

		/*
	 * Добавим в статусы закрытия сделки их интерпретацию
	 */
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}dogstatus LIKE 'result_close'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}dogstatus ADD COLUMN `result_close` VARCHAR(5) NULL COMMENT 'Результат закрытия: lose - Проигрыш; win - Победа' AFTER `title`" );

		//попробуем расставить признаки у существующих статусов
		$result = $db -> query( "SELECT * FROM {$sqlname}dogstatus ORDER BY sid" );
		while ($data = $db -> fetch( $result )) {

			if ( $data['result_close'] == NULL ) {

				//$status = 'win';
				$p = mb_strtolower( $data['title'] );

				if ( arrayFindInSet( $p, [
						'проигр',
						'отмен',
						'отказ'
					] ) == true )
					$status = 'lose';

				elseif ( arrayFindInSet( $p, [
						'побед',
						'выигр'
					] ) == true )
					$status = 'win';

				else
					$status = 'NULL';

				$db -> query( "UPDATE {$sqlname}dogstatus SET result_close = '$status' WHERE sid = ?i", $data['sid'] );

			}

		}

		/**
		 * Добавим таблицу "Черный список" для почты
		 */
		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}ymail_blacklist'" );
		if ( $da[0] == 0 ) {

			$db -> query( "CREATE TABLE `{$sqlname}ymail_blacklist` (`id` INT(20) NOT NULL AUTO_INCREMENT COMMENT 'id записи',`email` VARCHAR(50) NULL DEFAULT NULL COMMENT 'e-mail ',`identity` INT(20) NOT NULL DEFAULT '1' COMMENT 'идентификатор аккаунта (id записи в таблице settings)',PRIMARY KEY (`id`)) COLLATE='utf8_general_ci' ENGINE=InnoDB" );

		}

		/**
		 * Расставим комментарии в таблице _dogprovider и добавим новый признак "recal"
		 */
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

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}uids'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}uids (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`name` VARCHAR(100) NOT NULL COMMENT 'название параметра',
				`value` VARCHAR(100) NOT NULL COMMENT 'знаение параметра',
				`lid` INT(20) NULL DEFAULT '0' COMMENT 'id заявки',
				`eid` INT(20) NULL DEFAULT '0' COMMENT 'id обращения',
				`clid` INT(20) NULL DEFAULT '0' COMMENT 'id записи клиента',
				`did` INT(20) NULL DEFAULT '0' COMMENT 'id записи сделки',
				`identity` INT(20) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			COMMENT='База связки id сторонних систем с записями CRM'
			ENGINE=InnoDB
		" );

		}


		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2019.1')" );

		$currentVer = getVersion();

		$sapi = php_sapi_name();

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
	if ( getVersion() == '2019.1' ) {

		//к модулю "Проекты"
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}comments LIKE 'project'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}comments ADD COLUMN `project` INT(20) NULL AFTER `prid`" );

		//добавляем новый признак - шаблон счета
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}credit LIKE 'template'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}credit ADD COLUMN `template` INT(10) NULL DEFAULT NULL COMMENT 'Шаблон счета' AFTER `tip`" );

		$result = $db -> query( "SELECT * FROM {$sqlname}settings ORDER BY id" );
		while ($data = $db -> fetch( $result )) {

			//добавляем шаблон счета (базовый)
			$count = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}contract_type WHERE type = 'invoice' AND identity = '$data[id]'" ) + 0;
			if ( $count == 0 ) {

				$result_set = $db -> getRow( "SELECT inum, iformat FROM {$sqlname}settings WHERE id = '$data[id]'" );
				$num        = $settings["inum"];
				$format     = $settings["iformat"];

				//добавляем тип документа - Счет
				$db -> query( "INSERT INTO {$sqlname}contract_type SET ?u", [
					"title"    => "Счет",
					"type"     => "invoice",
					//"num"   => $num,
					//"format" => $format,
					"identity" => $data['id']
				] );
				$typeID = $db -> insertId();

				//добавляем шаблон документа - Счет
				$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
					"title"    => "Базовый шаблон",
					"typeid"   => $typeID,
					"file"     => "invoice.tpl",
					"identity" => $data['id']
				] );

				//привязываем все счета к базовому шаблону
				//пока не будем этого делать
				//просто - если шаблон не указан, значит он базовый

			}

			$akttypeid = $db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'get_akt' AND identity = '$data[id]'" ) + 0;

			//добавляем шаблон акта akt_full
			$aktid = $db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE file = 'akt_full.tpl' AND identity = '$data[id]'" ) + 0;
			if ( $aktid == 0 ) {

				//добавляем шаблон документа - Счет
				$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
					"title"    => "Приёма-передачи. Услуги (расширенный)",
					"typeid"   => $akttypeid,
					"file"     => "akt_full.tpl",
					"identity" => $data['id']
				] );

			}

			//добавляем шаблон акта akt_simple
			$aktid = $db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE file = 'akt_simple.tpl' AND identity = '$data[id]'" ) + 0;
			if ( $aktid == 0 ) {

				//добавляем шаблон документа - Счет
				$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
					"title"    => "Приёма-передачи. Услуги",
					"typeid"   => $akttypeid,
					"file"     => "akt_simple.tpl",
					"identity" => $data['id']
				] );

			}

			//добавляем шаблон акта akt_simple
			$aktid = $db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE file = 'akt_prava.tpl' AND identity = '$data[id]'" ) + 0;
			if ( $aktid == 0 ) {

				//добавляем шаблон документа - Счет
				$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
					"title"    => "Приёма-передачи. Права",
					"typeid"   => $akttypeid,
					"file"     => "akt_prava.tpl",
					"identity" => $data['id']
				] );

			}

		}

		//признак для напоминаний
		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}tasks LIKE 'day'" );
		if ( $field['Field'] == '' )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD COLUMN `day` VARCHAR(3) NULL DEFAULT 'no' COMMENT 'Признак - напоминание на весь день' AFTER `alert`" );

		// изменения модуля ЦИЗ
		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}callcenter'" );
		if ( $da[0] > 0 ) {

			$db -> query( "ALTER TABLE {$sqlname}callcenter CHANGE COLUMN `iduser` `iduser` VARCHAR(255) NOT NULL COMMENT 'id оператора (user.iduser)' AFTER `status`" );
			$db -> query( "ALTER TABLE {$sqlname}callcenter_list CHANGE COLUMN `datum_do` `datum_do` TIMESTAMP NULL DEFAULT NULL COMMENT 'время обработки' AFTER `isdo`" );

		}

		$db -> query( "ALTER TABLE {$sqlname}ymail_settings CHANGE COLUMN `lasttime` `lasttime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'дата и время последнего события' AFTER `settings`" );

		/*
	ALTER TABLE `app_ymail_settings` CHANGE COLUMN `lasttime` `lasttime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'дата и время последнего события' AFTER `settings`
	*/


		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2019.2')" );

		$currentVer = getVersion();

		$sapi = php_sapi_name();

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
	if ( getVersion() == '2019.2' && in_array( '2019.3', $seria ) ) {

		/**
		 * Расставим индексы по таблицам
		 */
		$index  = [];
		$result = $db -> query( "SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database'" );
		while ($da = $db -> fetch( $result )) {
			$index[ $da['TABLE_NAME'] ][] = $da['COLUMN_NAME'];
		}

		//ключи для напоминаний по сделкам
		if ( !in_array( "did", $index[ $sqlname."tasks" ] ) )
			$db -> query( "ALTER TABLE {$sqlname}tasks ADD INDEX `did` ( `did` )" );


		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2019.3')" );

		$currentVer = getVersion();

		$sapi = php_sapi_name();

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
	if ( getVersion() == '2019.3' && in_array( '2019.4', $seria ) ) {

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}contract_poz'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
			CREATE TABLE {$sqlname}contract_poz (
				`id` INT(20) NOT NULL AUTO_INCREMENT,
				`deid` INT(20) NOT NULL DEFAULT '0' COMMENT 'id документа (_contract.deid)',
				`did` INT(20) NULL DEFAULT '0' COMMENT 'id сделки',
				`spid` INT(20) NOT NULL DEFAULT '0' COMMENT 'id позиции спецификации',
				`prid` INT(20) NULL DEFAULT '0' COMMENT 'id позиции прайса',
				`kol` DOUBLE(20,4) NULL DEFAULT '0.0000' COMMENT 'количество товара',
				`identity` INT(11) NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			COMMENT='Позиции спецификации для Актов'
			COLLATE='utf8_general_ci'
			ENGINE=InnoDB
		" );

		}

		$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}tasks LIKE 'status'" );
		if ( $field['Field'] == '' )
			$db -> query( "
			ALTER TABLE {$sqlname}tasks
			ADD COLUMN `status` INT(1) NULL DEFAULT NULL COMMENT 'Статус выполнения (1-успешно,2-не успешно)' AFTER `day`,
			ADD COLUMN `alertTime` INT(3) NULL DEFAULT NULL COMMENT 'Часы до напоминания, для предварительного уведомления' AFTER `status`
		" );

		$db -> query( "ALTER TABLE {$sqlname}history CHANGE COLUMN `fid` `fid` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Список файлов _files.fid в виде списка с разделением (;)' AFTER `tip`" );

		/**
		 * Обновим версию
		 */
		$db -> query( "INSERT INTO {$sqlname}ver (id,current) VALUES (NULL,'2019.4')" );

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
			CHANGE COLUMN `company_full` `company_full` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `company`,
			CHANGE COLUMN `company_site` `company_site` VARCHAR(250) NULL COLLATE 'utf8_general_ci' AFTER `company_full`,
			CHANGE COLUMN `company_mail` `company_mail` VARCHAR(250) NULL COLLATE 'utf8_general_ci' AFTER `company_site`,
			CHANGE COLUMN `company_phone` `company_phone` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `company_mail`,
			CHANGE COLUMN `company_fax` `company_fax` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `company_phone`,
			CHANGE COLUMN `outClientUrl` `outClientUrl` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `company_fax`,
			CHANGE COLUMN `outDealUrl` `outDealUrl` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `outClientUrl`,
			CHANGE COLUMN `defaultDealName` `defaultDealName` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `outDealUrl`,
			CHANGE COLUMN `dir_prava` `dir_prava` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `defaultDealName`,
			CHANGE COLUMN `recv` `recv` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `dir_prava`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}settings`
			CHANGE COLUMN `export_lock` `export_lock` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `session`,
			CHANGE COLUMN `valuta` `valuta` VARCHAR(10) NULL COLLATE 'utf8_general_ci' AFTER `export_lock`,
			CHANGE COLUMN `ipaccesse` `ipaccesse` VARCHAR(5) NULL COLLATE 'utf8_general_ci' AFTER `valuta`,
			CHANGE COLUMN `ipstart` `ipstart` VARCHAR(15) NULL COLLATE 'utf8_general_ci' AFTER `ipaccesse`,
			CHANGE COLUMN `ipend` `ipend` VARCHAR(15) NULL COLLATE 'utf8_general_ci' AFTER `ipstart`,
			CHANGE COLUMN `iplist` `iplist` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `ipend`,
			CHANGE COLUMN `maxupload` `maxupload` VARCHAR(3) NULL COLLATE 'utf8_general_ci' AFTER `iplist`,
			CHANGE COLUMN `ipmask` `ipmask` VARCHAR(20) NULL COLLATE 'utf8_general_ci' AFTER `maxupload`,
			CHANGE COLUMN `ext_allow` `ext_allow` TEXT(65535) NOT NULL COLLATE 'utf8_general_ci' AFTER `ipmask`,
			CHANGE COLUMN `mailme` `mailme` VARCHAR(5) NULL COLLATE 'utf8_general_ci' AFTER `ext_allow`,
			CHANGE COLUMN `mailout` `mailout` VARCHAR(10) NULL COLLATE 'utf8_general_ci' AFTER `mailme`,
			CHANGE COLUMN `other` `other` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `mailout`,
			CHANGE COLUMN `logo` `logo` VARCHAR(100) NULL COLLATE 'utf8_general_ci' AFTER `other`
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
			CHANGE COLUMN `company_full` `company_full` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `company`,
			CHANGE COLUMN `company_site` `company_site` VARCHAR(250) NULL COLLATE 'utf8_general_ci' AFTER `company_full`,
			CHANGE COLUMN `company_mail` `company_mail` VARCHAR(250) NULL COLLATE 'utf8_general_ci' AFTER `company_site`,
			CHANGE COLUMN `company_phone` `company_phone` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `company_mail`,
			CHANGE COLUMN `company_fax` `company_fax` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `company_phone`,
			CHANGE COLUMN `outClientUrl` `outClientUrl` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `company_fax`,
			CHANGE COLUMN `outDealUrl` `outDealUrl` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `outClientUrl`,
			CHANGE COLUMN `defaultDealName` `defaultDealName` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `outDealUrl`,
			CHANGE COLUMN `dir_prava` `dir_prava` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `defaultDealName`,
			CHANGE COLUMN `recv` `recv` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `dir_prava`
		" );

		$db -> query( "
			ALTER TABLE `{$sqlname}settings`
			CHANGE COLUMN `export_lock` `export_lock` VARCHAR(255) NULL COLLATE 'utf8_general_ci' AFTER `session`,
			CHANGE COLUMN `valuta` `valuta` VARCHAR(10) NULL COLLATE 'utf8_general_ci' AFTER `export_lock`,
			CHANGE COLUMN `ipaccesse` `ipaccesse` VARCHAR(5) NULL COLLATE 'utf8_general_ci' AFTER `valuta`,
			CHANGE COLUMN `ipstart` `ipstart` VARCHAR(15) NULL COLLATE 'utf8_general_ci' AFTER `ipaccesse`,
			CHANGE COLUMN `ipend` `ipend` VARCHAR(15) NULL COLLATE 'utf8_general_ci' AFTER `ipstart`,
			CHANGE COLUMN `iplist` `iplist` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `ipend`,
			CHANGE COLUMN `maxupload` `maxupload` VARCHAR(3) NULL COLLATE 'utf8_general_ci' AFTER `iplist`,
			CHANGE COLUMN `ipmask` `ipmask` VARCHAR(20) NULL COLLATE 'utf8_general_ci' AFTER `maxupload`,
			CHANGE COLUMN `ext_allow` `ext_allow` TEXT(65535) NOT NULL COLLATE 'utf8_general_ci' AFTER `ipmask`,
			CHANGE COLUMN `mailme` `mailme` VARCHAR(5) NULL COLLATE 'utf8_general_ci' AFTER `ext_allow`,
			CHANGE COLUMN `mailout` `mailout` VARCHAR(10) NULL COLLATE 'utf8_general_ci' AFTER `mailme`,
			CHANGE COLUMN `other` `other` TEXT(65535) NULL COLLATE 'utf8_general_ci' AFTER `mailout`,
			CHANGE COLUMN `logo` `logo` VARCHAR(100) NULL COLLATE 'utf8_general_ci' AFTER `other`
		" );

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}mycomps_signer'" );
		if ( $da[0] == 0 ) {

			$db -> query( "
				CREATE TABLE `{$sqlname}mycomps_signer` (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`mcid` INT(20) NULL DEFAULT NULL COMMENT 'Привязка к компании',
					`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Имя подписанта' COLLATE 'utf8_general_ci',
					`status` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Должность' COLLATE 'utf8_general_ci',
					`signature` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Подпись' COLLATE 'utf8_general_ci',
					`osnovanie` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Действующий на основании' COLLATE 'utf8_general_ci',
					`stamp` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Файл факсимилье' COLLATE 'utf8_general_ci',
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

		$count = $db -> getOne( "SELECT DISTINCT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '$database' and TABLE_NAME = '{$sqlname}tasks' and INDEX_NAME = 'cid'" );
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
					CHANGE COLUMN `tip` `tip` VARCHAR(100) NOT NULL COMMENT 'приходный или расходный' COLLATE 'utf8_general_ci' AFTER `did`,
					CHANGE COLUMN `number` `number` INT(10) NULL COMMENT 'номер ордера' AFTER `tip`,
					CHANGE COLUMN `clid` `clid` INT(10) NULL COMMENT 'clientcat.clid' AFTER `datum`,
					CHANGE COLUMN `posid` `posid` INT(10) NULL COMMENT 'clientcat.clid (id поставщика)' AFTER `clid`,
					CHANGE COLUMN `man1` `man1` VARCHAR(255) NULL COMMENT ' для расходного сдал, для приходного принял' COLLATE 'utf8_general_ci' AFTER `posid`,
					CHANGE COLUMN `man2` `man2` VARCHAR(255) NULL COMMENT ' для расходного принял, для приходного сдал' COLLATE 'utf8_general_ci' AFTER `man1`,
					CHANGE COLUMN `isdo` `isdo` VARCHAR(5) NULL COMMENT 'проведен или нет' COLLATE 'utf8_general_ci' AFTER `man2`,
					CHANGE COLUMN `cFactura` `cFactura` VARCHAR(20) NULL COMMENT '№ счета-фактуры поставщика' COLLATE 'utf8_general_ci' AFTER `isdo`,
					CHANGE COLUMN `idz` `idz` INT(10) NULL DEFAULT NULL AFTER `sklad`;
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
				CHANGE COLUMN `content` `content` MEDIUMTEXT NULL COMMENT 'описание позиции' COLLATE 'utf8_general_ci' AFTER `idz`,
				CHANGE COLUMN `price_plus` `price_plus` DOUBLE NULL AFTER `datum`,
				CHANGE COLUMN `status` `status` INT(10) NULL DEFAULT '0' COMMENT 'статус (в наличии и тд.)' AFTER `price_plus`,
				CHANGE COLUMN `kol` `kol` DOUBLE NULL DEFAULT '0' COMMENT 'количество' AFTER `status`,
				CHANGE COLUMN `files` `files` TEXT NULL COMMENT 'прикрепленные файлы в формате json' COLLATE 'utf8_general_ci' AFTER `kol`,
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
				CHANGE COLUMN `content` `content` TEXT NULL COMMENT 'Описание проекта' COLLATE 'utf8_general_ci' AFTER `name`,
				CHANGE COLUMN `date_fact` `date_fact` DATE NULL COMMENT 'Дата завершения' AFTER `date_end`,
				CHANGE COLUMN `pid_list` `pid_list` VARCHAR(255) NULL COMMENT 'Список контактов' COLLATE 'utf8_general_ci' AFTER `clid`
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
				CHANGE COLUMN `recv` `recv` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `editor`,
				CHANGE COLUMN `dostup` `dostup` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `recv`,
				CHANGE COLUMN `last_dog` `last_dog` DATE NULL DEFAULT NULL AFTER `dostup`,
				CHANGE COLUMN `last_hist` `last_hist` DATETIME NULL DEFAULT NULL AFTER `last_dog`
			");

			/*
			$db -> query("
				ALTER TABLE `{$sqlname}personcat`
				CHANGE COLUMN `input1` `input1` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `loyalty`,
				CHANGE COLUMN `input2` `input2` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input1`,
				CHANGE COLUMN `input3` `input3` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input2`,
				CHANGE COLUMN `input4` `input4` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input3`,
				CHANGE COLUMN `input5` `input5` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input4`,
				CHANGE COLUMN `input6` `input6` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input5`,
				CHANGE COLUMN `input7` `input7` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input6`,
				CHANGE COLUMN `input8` `input8` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input7`,
				CHANGE COLUMN `input9` `input9` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input8`,
				CHANGE COLUMN `input10` `input10` VARCHAR(512) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `input9`,
			");
			*/

		}

		$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}incoming_channels'" );
		if ( $da[0] > 0 ) {

			$db -> query("
				ALTER TABLE `{$sqlname}incoming_channels`
				CHANGE COLUMN `p_time` `p_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `p_identity`
			");

		}

		$db -> query("
		ALTER TABLE `{$sqlname}user`
			CHANGE COLUMN `territory` `territory` INT(10) NULL DEFAULT '0' AFTER `gcalendar`,
			CHANGE COLUMN `phone` `phone` TEXT NULL COMMENT 'номер телефона' COLLATE 'utf8_general_ci' AFTER `office`,
			CHANGE COLUMN `phone_in` `phone_in` VARCHAR(20) NULL COMMENT 'добавочный номер' COLLATE 'utf8_general_ci' AFTER `phone`,
			CHANGE COLUMN `fax` `fax` TEXT NULL COLLATE 'utf8_general_ci' AFTER `phone_in`,
			CHANGE COLUMN `avatar` `avatar` VARCHAR(100) NULL DEFAULT NULL COMMENT 'аватарка' COLLATE 'utf8_general_ci' AFTER `subscription`,
			CHANGE COLUMN `usersettings` `usersettings` TEXT NULL COMMENT 'различные настройки' COLLATE 'utf8_general_ci' AFTER `adate`,
			CHANGE COLUMN `email` `email` TEXT NULL DEFAULT NULL COMMENT 'Email' COLLATE 'utf8_general_ci' AFTER `otdel`
		");

		$db -> query("
		ALTER TABLE `{$sqlname}group`
			CHANGE COLUMN `type` `type` INT(10) NULL DEFAULT NULL COMMENT 'DEPRECATED' AFTER `datum`,
			CHANGE COLUMN `service` `service` VARCHAR(60) NULL DEFAULT NULL COMMENT 'Связка с сервисом _services.name' COLLATE 'utf8_general_ci' AFTER `type`,
			CHANGE COLUMN `idservice` `idservice` VARCHAR(100) NULL DEFAULT NULL COMMENT 'id группы во внешнем сервисе' COLLATE 'utf8_general_ci' AFTER `service`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}grouplist`
			CHANGE COLUMN `clid` `clid` INT(10) ZEROFILL NULL COMMENT 'Клиент _clientcat.clid' AFTER `gid`,
			CHANGE COLUMN `pid` `pid` INT(10) ZEROFILL NULL COMMENT 'Контакт _personcat.pid' AFTER `clid`,
			CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата подписки' AFTER `pid`,
			CHANGE COLUMN `person_id` `person_id` INT(10) ZEROFILL NULL COMMENT 'не используется' AFTER `datum`,
			CHANGE COLUMN `service` `service` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Имя сервиса _services.name' COLLATE 'utf8_general_ci' AFTER `person_id`,
			CHANGE COLUMN `user_name` `user_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'имя подписчика' COLLATE 'utf8_general_ci' AFTER `service`,
			CHANGE COLUMN `user_email` `user_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'email подписчика' COLLATE 'utf8_general_ci' AFTER `user_name`,
			CHANGE COLUMN `user_phone` `user_phone` VARCHAR(15) NULL DEFAULT NULL COMMENT 'телефон подписчика' COLLATE 'utf8_general_ci' AFTER `user_email`,
			CHANGE COLUMN `tags` `tags` TEXT NULL DEFAULT NULL COMMENT 'тэги' COLLATE 'utf8_general_ci' AFTER `user_phone`,
			CHANGE COLUMN `status` `status` VARCHAR(100) NULL DEFAULT NULL COMMENT 'статус подписчика' COLLATE 'utf8_general_ci' AFTER `tags`,
			CHANGE COLUMN `availability` `availability` VARCHAR(100) NULL DEFAULT NULL COMMENT 'доступность подписчика' COLLATE 'utf8_general_ci' AFTER `status`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}clientpath`
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NULL DEFAULT NULL COMMENT 'Дефолтный признак' COLLATE 'utf8_general_ci' AFTER `name`,
			CHANGE COLUMN `utm_source` `utm_source` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Связка с источником' COLLATE 'utf8_general_ci' AFTER `isDefault`,
			CHANGE COLUMN `destination` `destination` VARCHAR(12) NULL DEFAULT NULL COMMENT 'Связка с номером телефона' COLLATE 'utf8_general_ci' AFTER `utm_source`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}contract`
			CHANGE COLUMN `idtype` `idtype` INT(10) NULL DEFAULT NULL COMMENT 'тип документа _contract_type.id' AFTER `title`,
			CHANGE COLUMN `crid` `crid` INT(10) NULL DEFAULT NULL COMMENT 'связанный счет credit.crid (для актов сервисных сделок)' AFTER `idtype`,
			CHANGE COLUMN `mcid` `mcid` INT(10) NULL DEFAULT NULL AFTER `crid`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}activities`
			CHANGE COLUMN `color` `color` VARCHAR(7) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `title`,
			CHANGE COLUMN `resultat` `resultat` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `icon`,
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(6) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `resultat`,
			CHANGE COLUMN `aorder` `aorder` INT(10) NULL DEFAULT NULL AFTER `isDefault`,
			CHANGE COLUMN `filter` `filter` VARCHAR(255) NULL DEFAULT 'all' COLLATE 'utf8_general_ci' AFTER `aorder`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}user`
			CHANGE COLUMN `email` `email` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `otdel`,
			CHANGE COLUMN `acs_analitics` `acs_analitics` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `bday`,
			CHANGE COLUMN `acs_maillist` `acs_maillist` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `acs_analitics`,
			CHANGE COLUMN `acs_files` `acs_files` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `acs_maillist`,
			CHANGE COLUMN `acs_price` `acs_price` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `acs_files`,
			CHANGE COLUMN `acs_credit` `acs_credit` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `acs_price`,
			CHANGE COLUMN `acs_prava` `acs_prava` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `acs_credit`,
			CHANGE COLUMN `tzone` `tzone` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `acs_prava`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}comments`
			CHANGE COLUMN `mid` `mid` INT(10) NULL DEFAULT NULL AFTER `idparent`,
			CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL AFTER `datum`,
			CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL AFTER `clid`,
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
			CHANGE COLUMN `prid` `prid` INT(10) NULL DEFAULT NULL AFTER `did`,
			CHANGE COLUMN `iduser` `iduser` INT(10) ZEROFILL NULL DEFAULT NULL AFTER `project`,
			CHANGE COLUMN `title` `title` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `iduser`,
			CHANGE COLUMN `content` `content` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `title`,
			CHANGE COLUMN `fid` `fid` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `content`,
			CHANGE COLUMN `lastCommentDate` `lastCommentDate` DATETIME NULL DEFAULT NULL AFTER `fid`,
			CHANGE COLUMN `dateClose` `dateClose` DATETIME NULL DEFAULT NULL AFTER `isClose`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}complect`
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `id`,
			CHANGE COLUMN `doit` `doit` VARCHAR(5) NOT NULL DEFAULT 'no' COLLATE 'utf8_general_ci' AFTER `data_fact`,
			CHANGE COLUMN `iduser` `iduser` INT(10) ZEROFILL NULL DEFAULT NULL AFTER `doit`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}credit`
			CHANGE COLUMN `nds_credit` `nds_credit` DOUBLE(20,2) NOT NULL DEFAULT '0.00' AFTER `summa_credit`,
			CHANGE COLUMN `invoice` `invoice` VARCHAR(20) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `do`,
			CHANGE COLUMN `invoice_chek` `invoice_chek` VARCHAR(40) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `invoice`,
			CHANGE COLUMN `invoice_date` `invoice_date` DATE NULL DEFAULT NULL AFTER `invoice_chek`,
			CHANGE COLUMN `rs` `rs` INT(10) NULL DEFAULT NULL AFTER `invoice_date`,
			CHANGE COLUMN `tip` `tip` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `rs`,
			CHANGE COLUMN `suffix` `suffix` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `template`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}direction`
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `title`;
		");

		//создадим таблицу, если надо
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."currency'" );
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

		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."currency_log'" );
		if ( $da == 0 ) {

			$db -> query( "
				CREATE TABLE {$sqlname}currency_log (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id записи валюты',
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
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

		$field = $db -> getRow( "SHOW COLUMNS FROM ".$sqlname."dogovor LIKE 'idcurrency'" );
		if ( $field[ 'Field' ] == '' ) {

			$db -> query( "
				ALTER TABLE ".$sqlname."dogovor
				CHANGE COLUMN `provider` `idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id валюты' AFTER `direction`,
				CHANGE COLUMN `akt_num` `idcourse` INT(20) NULL DEFAULT NULL COMMENT 'id курса по сделке' AFTER `idcurrency`;
			" );

			$db -> query( "UPDATE ".$sqlname."dogovor SET idcurrency = '0'" );
			$db -> query( "UPDATE ".$sqlname."dogovor SET idcourse = '0'" );

		}

		$db -> query("
		ALTER TABLE `{$sqlname}dogovor`
			CHANGE COLUMN `datum_start` `datum_start` DATE NULL DEFAULT NULL AFTER `con_id`,
			CHANGE COLUMN `datum_end` `datum_end` DATE NULL DEFAULT NULL AFTER `datum_start`,
			CHANGE COLUMN `pid_list` `pid_list` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `datum_end`,
			CHANGE COLUMN `partner` `partner` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `pid_list`,
			CHANGE COLUMN `zayavka` `zayavka` VARCHAR(200) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `partner`,
			CHANGE COLUMN `ztitle` `ztitle` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `zayavka`,
			CHANGE COLUMN `mcid` `mcid` INT(10) NULL DEFAULT NULL AFTER `ztitle`,
			CHANGE COLUMN `direction` `direction` INT(10) NULL DEFAULT NULL AFTER `mcid`,
			CHANGE COLUMN `akt_date` `akt_date` DATE NULL DEFAULT NULL AFTER `idcourse`,
			CHANGE COLUMN `akt_temp` `akt_temp` VARCHAR(200) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `akt_date`,
			CHANGE COLUMN `lid` `lid` INT(10) NULL DEFAULT NULL AFTER `akt_temp`,
			CHANGE COLUMN `payer` `payer` INT(10) NULL DEFAULT NULL AFTER `clid`,
			CHANGE COLUMN `autor` `autor` INT(10) NULL DEFAULT NULL AFTER `datum`,
			CHANGE COLUMN `calculate` `calculate` VARCHAR(4) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `marga`
		");

		$db -> query("
		ALTER TABLE `{$sqlname}dogprovider`
			CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL COMMENT 'id сделки' AFTER `id`,
			CHANGE COLUMN `conid` `conid` INT(10) NULL DEFAULT NULL COMMENT 'id поставщика' AFTER `did`,
			CHANGE COLUMN `partid` `partid` INT(10) NULL DEFAULT NULL COMMENT 'id партнера' AFTER `conid`,
			CHANGE COLUMN `summa` `summa` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'сумма расхода' AFTER `partid`,
			CHANGE COLUMN `status` `status` VARCHAR(20) NULL DEFAULT NULL COMMENT 'статус проведения' COLLATE 'utf8_general_ci' AFTER `summa`,
			CHANGE COLUMN `bid` `bid` INT(10) NULL DEFAULT NULL COMMENT 'id записи расхода в бюджете' AFTER `status`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}dogtips`
			CHANGE COLUMN `isDefault` `isDefault` VARCHAR(5) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `title`;
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
			CHANGE COLUMN `content` `content` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `autor`;
		");

		$db -> query("
		ALTER TABLE `{$sqlname}field`
			CHANGE COLUMN `fld_tip` `fld_tip` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `fld_id`,
			CHANGE COLUMN `fld_name` `fld_name` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `fld_tip`,
			CHANGE COLUMN `fld_stat` `fld_stat` VARCHAR(10) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `fld_order`,
			CHANGE COLUMN `fld_temp` `fld_temp` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `fld_stat`,
			CHANGE COLUMN `fld_var` `fld_var` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `fld_temp`;
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
			$db -> query("ALTER TABLE {$sqlname}dogovor CHANGE COLUMN `con_id` `isFrozen` INT(1) NULL DEFAULT 0 COMMENT 'признак заморозки' COLLATE 'utf8_general_ci' AFTER `calculate`");

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

	if ( PHP_SAPI === 'cli' ) {

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

//исправления для текущих версий, если ранее что-то пошло не так
if ( $currentVersion == $lastVer ) {

	/**
	 * убираем автоматическое изменение даты счета на текущее значение
	 */
	$db -> query( "ALTER TABLE {$sqlname}credit CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата счета' AFTER `pid`" );

	/**
	 * Если ячейки нет, то добавляем
	 */
	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_akt LIKE 'cFactura'" );
	if ( $field['Field'] == '' )
		$db -> query( "ALTER TABLE {$sqlname}modcatalog_akt ADD COLUMN `cFactura` VARCHAR(20) NOT NULL AFTER `isdo`" );

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_akt LIKE 'cDate'" );
	if ( $field['Field'] == '' )
		$db -> query( "ALTER TABLE {$sqlname}modcatalog_akt ADD COLUMN `cDate` DATE NULL DEFAULT NULL AFTER `cFactura`" );

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_akt LIKE 'sklad'" );
	if ( $field['Field'] == '' )
		$db -> query( "ALTER TABLE `{$sqlname}modcatalog_akt` ADD COLUMN `sklad` INT(20) NULL DEFAULT '0' AFTER `cDate`" );

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}modcatalog_akt LIKE 'idz'" );
	if ( $field['Field'] == '' )
		$db -> query( "ALTER TABLE {$sqlname}modcatalog_akt ADD COLUMN `idz` INT(20) NULL DEFAULT NULL AFTER `sklad`" );

	/**
	 * Если ячейки нет, то добавляем (у Dupad небыло)
	 */
	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}clientcat LIKE 'priceLevel'" );
	if ( $field['Field'] == '' )
		$db -> query( "ALTER TABLE {$sqlname}clientcat ADD COLUMN `priceLevel` VARCHAR(10) NOT NULL DEFAULT 'price_1' AFTER `type`" );

	$db -> query( "
		ALTER TABLE {$sqlname}modcatalog_zayavka
		CHANGE COLUMN `isHight` `isHight` VARCHAR(3) NULL DEFAULT 'no' AFTER `des`,
		CHANGE COLUMN `cInvoice` `cInvoice` VARCHAR(20) NULL AFTER `isHight`
	" );

	$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}contract LIKE 'mcid'" );
	if ( $field['Field'] == '' ) {
		$db -> query( "ALTER TABLE {$sqlname}contract ADD COLUMN `mcid` INT(20) NULL DEFAULT NULL AFTER `crid`" );
	}

	//проверяем, имеет ли price.artikul разрешение на Null
	$dbfields = $db -> getRow( "SHOW FIELDS FROM {$sqlname}price WHERE Field = 'artikul'" );
	if ( texttosmall( $dbfields['Null'] ) != 'yes' ) {
		$db -> query( "ALTER TABLE {$sqlname}price CHANGE COLUMN `artikul` `artikul` VARCHAR(255) NULL AFTER `n_id`" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM {$sqlname}dostup WHERE Key_name = 'yindex'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE {$sqlname}dostup ADD INDEX `yindex` (`clid`, `pid`, `did`, `iduser`)" );
	}

	$dbfields = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}callhistory WHERE Field = 'file'" );
	if ( texttosmall( $dbfields['Null'] ) != 'yes' ) {
		$db -> query( "ALTER TABLE {$sqlname}callhistory CHANGE `file` `file` TEXT NULL" );
	}

	$count = $db -> getOne( "SELECT DISTINCT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '$database' and TABLE_NAME = '{$sqlname}dogovor' and INDEX_NAME = 'note'" );
	if ( $count == 0 ) {
		$db -> query( "ALTER TABLE {$sqlname}dogovor ADD INDEX `note` (`iduser`, `identity`)" );
	}

	$db -> query( "ALTER TABLE {$sqlname}modcatalog_aktpoz CHANGE COLUMN `kol` `kol` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'количество по приходному/расходному ордеру' AFTER `price_in`" );
	$db -> query( "ALTER TABLE {$sqlname}speca CHANGE COLUMN `kol` `kol` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'кол-во' AFTER `price_in`" );
	$db -> query( "ALTER TABLE {$sqlname}modcatalog_zayavkapoz CHANGE COLUMN `kol` `kol` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'кол-во на складе' AFTER `prid`" );
	$db -> query( "ALTER TABLE {$sqlname}modcatalog_reserv CHANGE COLUMN `kol` `kol` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'кол-во резерва' AFTER `datum`" );
	$db -> query( "ALTER TABLE {$sqlname}modcatalog_skladpoz CHANGE COLUMN `kol` `kol` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'кол-во' AFTER `date_period`" );
	$db -> query( "ALTER TABLE {$sqlname}modcatalog_aktpoz CHANGE COLUMN `kol` `kol` DOUBLE(20,2) NULL DEFAULT '0.00' COMMENT 'количество по приходному/расходному ордеру' AFTER `price_in`;" );

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

	$db -> query( "ALTER TABLE {$sqlname}budjet CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `summa`" );

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
			`datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'время уведомления',
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
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
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
	if ( count( $keys ) == 0 ) {
		$da = $db -> query( "ALTER TABLE `{$sqlname}contract` ADD INDEX `did_iduser` (`did`, `iduser`)" );
	}

	//наличие таблицы
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}tasks` WHERE Key_name = 'clid'" );
	if ( count( $keys ) == 0 ) {
		$da = $db -> query( "ALTER TABLE `{$sqlname}tasks` ADD INDEX ( `clid` )" );
	}
	// индекс связи напоминаний и активностей
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}tasks` WHERE Key_name = 'cid'" );
	if ( count( $keys ) == 0 ) {
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
	if ( count( $keys ) == 0 ) {
		$da = $db -> getOne( "ALTER TABLE `{$sqlname}grouplist` ADD INDEX `gid_clid_identity` (`gid`, `clid`, `identity`)" );
	}

	$db -> query( "ALTER TABLE `{$sqlname}comments` CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT NULL AFTER `mid`;" );

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}ymail_messages` WHERE Key_name = 'complex'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}ymail_messages` ADD INDEX `complex` (`folder`, `state`, `iduser`, `identity`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}ymail_messages` WHERE Key_name = 'did'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}ymail_messages` ADD INDEX `did` (`did`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}ymail_messages` WHERE Key_name = 'theme'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}ymail_messages` ADD INDEX `theme` (`theme`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'do'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `do` (`do`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'did'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `did` (`did`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'clid'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `clid` (`clid`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'iduser'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `iduser` (`iduser`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}credit` WHERE Key_name = 'datum_credit'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}credit` ADD INDEX `datum_credit` (`datum_credit`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}dogovor` WHERE Key_name = 'sid'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}dogovor` ADD INDEX `sid` (`sid`), ADD INDEX `close` (`close`), ADD INDEX `datum` (`datum`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}grouplist` WHERE Key_name = 'clid'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}grouplist` ADD INDEX `clid` (`clid`), ADD INDEX `pid` (`pid`)" );
	}

	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}user` WHERE Key_name = 'mid'" );
	if ( count( $keys ) == 0 ) {
		$db -> query( "ALTER TABLE `{$sqlname}user` ADD INDEX `mid` (`mid`), ADD INDEX `secrty` (`secrty`)" );
	}

	$db -> query( "ALTER TABLE `{$sqlname}tasks` CHANGE COLUMN `pid` `pid` VARCHAR(255) NULL COMMENT 'personcat.pid (может быть несколько с разделением ;)' COLLATE 'utf8_general_ci' AFTER `clid`" );

	// оптимизация комментариев
	$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}comments` WHERE Key_name = 'mid'" );
	if ( count( $keys ) == 0 ) {

		$db -> query( "ALTER TABLE `{$sqlname}comments` ADD INDEX `mid` (`mid`), ADD INDEX `idparent` (`idparent`), ADD INDEX `isClose` (`isClose`)" );
		$db -> query( "ALTER TABLE `{$sqlname}comments_subscribe` ADD INDEX `idcomment` (`idcomment`), ADD INDEX `iduser` (`iduser`)" );

	}

	$dap = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects'" );
	if ( (int)$dap > 0 ) {

		$keys = $db -> getRow( "SHOW KEYS FROM `{$sqlname}projects` WHERE Key_name = 'did'" );
		if ( count( $keys ) == 0 ) {

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
			CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `did`,
			CHANGE COLUMN `con_id` `con_id` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `calculate`
		");

	}
	else{

		$db -> query("
		ALTER TABLE `{$sqlname}dogovor`
			CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `did`
		");

	}

	$db -> query( "
	ALTER TABLE `{$sqlname}history`
		CHANGE COLUMN `uid` `uid` VARCHAR(100) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `fid`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}clientcat`
		CHANGE COLUMN `uid` `uid` VARCHAR(30) NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `clid`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}speca`
		CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `edizm`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}contract`
		CHANGE COLUMN `datum` `datum` DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `deid`,
		CHANGE COLUMN `payer` `payer` INT(10) NULL DEFAULT NULL AFTER `clid`,
		CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL AFTER `pid`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT NULL AFTER `ftype`,
		CHANGE COLUMN `title` `title` TEXT NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `iduser`
	");

	$db -> query( "
	ALTER TABLE `{$sqlname}leads`
		CHANGE COLUMN `datum_do` `datum_do` DATETIME NULL DEFAULT NULL COMMENT 'дата обработки' AFTER `datum`,
		CHANGE COLUMN `status` `status` INT(10) NULL DEFAULT NULL COMMENT 'статус 0 => Открыт, 1 => В работе, 2 => Обработан, 3 => Закрыт' AFTER `datum_do`,
		CHANGE COLUMN `rezult` `rezult` INT(10) NULL DEFAULT NULL COMMENT 'результат обработки 1 => Спам, 2 => Дубль, 3 => Другое, 4 => Не целевой' AFTER `status`,
		CHANGE COLUMN `description` `description` TEXT NULL DEFAULT NULL COMMENT 'описание заявки' COLLATE 'utf8_general_ci' AFTER `company`,
		CHANGE COLUMN `iduser` `iduser` INT(10) NULL DEFAULT '0' COMMENT '_user.iduser' AFTER `timezone`,
		CHANGE COLUMN `clientpath` `clientpath` INT(10) NULL DEFAULT NULL COMMENT '_clientpath.id' AFTER `iduser`,
		CHANGE COLUMN `pid` `pid` INT(10) NULL DEFAULT NULL COMMENT '_personcat.pid' AFTER `clientpath`,
		CHANGE COLUMN `clid` `clid` INT(10) NULL DEFAULT NULL COMMENT '_clientcat.clid' AFTER `pid`,
		CHANGE COLUMN `did` `did` INT(10) NULL DEFAULT NULL COMMENT '_dogovor.did' AFTER `clid`,
		CHANGE COLUMN `partner` `partner` INT(10) NULL DEFAULT NULL COMMENT '_clientcat.clid' AFTER `did`,
		CHANGE COLUMN `rezz` `rezz` TEXT NULL DEFAULT NULL COMMENT 'комментарий при дисквалификации заявки' COLLATE 'utf8_general_ci' AFTER `muid`
	");

	/**
	 * Обновим структуру таблиц
	 */
	$tables = ['clientcat','dogovor','personcat'];

	foreach ($tables as $table) {

		$fields = $db -> getAll("SHOW FIELDS FROM {$sqlname}$table");

		foreach ($fields as $i => $field){

			$fcurrent = $field['Field'];
			$fprev    = $fields[$i-1]['Field'];
			$ftype    = $field['Type'];

			if (stripos($fcurrent, 'input') !== false) {

				$db -> query("ALTER TABLE {$sqlname}$table CHANGE COLUMN `$fcurrent` `$fcurrent` $ftype NULL DEFAULT NULL COLLATE 'utf8_general_ci' AFTER `$fprev`");

			}

		}

	}

	$db -> query("ALTER TABLE {$sqlname}speca CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `edizm`");
	$db -> query("UPDATE {$sqlname}dogovor SET close = 'no' WHERE close IS NULL");

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
						<div class="wp40 div-center" style="display:inline-grid">
							<div class="Bold red">Внимание!</div>
							Крайне рекомендуется выполнять обновление через терминал, если размер базы данных значителен, т.к. вносятся изменения в её структуру и может потребоваться больше времени, чем разрешено веб-сервером.
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
						Для обновления до версии 7.6 воспользуйтесь <a href="_oldupdate.php" class="red"><b>старой версией</b></a> скрипта.<br>После обновления до версии 7.6 запустите текущий скрипт еще раз.<br><br>
					</DIV>
				</div>';

			}
			elseif ( $ver == $lastVer ) {
				print '
				<div class="blok infodiv div-center">
					<DIV class="green">
						<br><i class="icon-ok-circled icon-5x green"></i><h1>Обновление не требуется.</h1><br>
						<div class="main_div div-center">
							<A href="/" class="button"><b>К рабочему столу</b></A>
						</div>
						<br><br>
					</DIV>
					<DIV class="infodiv">
						<br><i class="icon-bitbucket red"></i>Не забывайте удалять файлы <b>install.php</b> и <b>update.php</b><br><br>
					</DIV>
				</div>';
			}
			else {
				?>
				<br>
				<div class="main_div div-center">
					<A href="update.older.php?step=1" class="button">Продолжить установку</A>
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
			<div class="blok infodiv">
				<h1>Текущая версия - <b class="red"><?= getVersion() ?></b></h1><br>
				<b>Результат: </b><?= $message ?>
			</div>
			<?php
			if ( $ver == $lastVer ) {

				print '
				<div class="blok infodiv div-center">
					<DIV class="green">
						<br><i class="icon-ok-circled icon-5x green"></i><h1>Обновление не требуется.</h1><br>
						<div class="main_div div-center">
							<A href="/" class="button"><b>К рабочему столу</b></A>
						</div>
						<br><br>
					</DIV>
					<DIV class="infodiv">
						<br><i class="icon-bitbucket red"></i>Не забывайте удалять файлы <b>install.php</b> и <b>update.php</b><br><br>
					</DIV>
				</div>';

			}
			?>
		</fieldset>
	</div>
<?php }
?>
</body>
</html>