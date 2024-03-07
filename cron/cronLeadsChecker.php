<?php

/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// php d:\OpenServer\domains\sm2021.crm\cron\cronLeadsChecker.php dupad.100crm.ru 443 on

/**
 * Можно запустить командой в терминале:
 * php D:\OpenServer\domains\sm2018.crm\cron\cliLeadsChecker.php
 */

/**
 * Скрипт для фоновой проверки заявок с сайта
 */

use Salesman\Leads;

set_time_limit( 0 );
ini_set( 'memory_limit', '32M' );

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header( 'Access-Control-Allow-Origin: *' );

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

$root = dirname( __DIR__ );

require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";
require_once $root."/inc/func.php";

$logfile = $root."/cash/LeadDaemon.log";

/**
 * Параметры для работы скрипта
 */
$alert      = "yes";
$console    = true;
$limit_step = 5; //количество проверяемых ящиков за один проход

if ( !empty( $argv ) ) {

	$_SERVER['HTTP_HOST']   = ($argv[1] != '') ? $argv[1] : '';
	$_SERVER['SERVER_PORT'] = ($argv[2] != '') ? $argv[2] : '80';
	$_SERVER['HTTPS']       = ($argv[3] != '') ? $argv[3] : 'off';

}

/**
 * Пришедшие аргументы
 */

//если нет таблицы со счетчиком
//если нет таблицы со счетчиком
$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."cronwork'" );
if ( $da[0] == 0 ) {

	$db -> query( "
		CREATE TABLE ".$sqlname."cronwork (
			`id` INT(20) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(50) NOT NULL,
			`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`box` INT(10) NOT NULL,
			PRIMARY KEY (`id`)
		)
		ENGINE=MyISAM
	" );

}

$row = $db -> getOne( "SELECT id FROM ".$sqlname."cronwork WHERE name = 'cronleadsemail'" ) + 0;
if ( $row == 0 ) {

	$db -> query( "INSERT INTO ".$sqlname."cronwork SET ?u", [
		"name" => 'cronleadsemail',
		"box"  => 0
	] );

}

//id последнего проверенного ящика
$lastBOX = $db -> getOne( "SELECT box FROM ".$sqlname."cronwork WHERE name = 'cronleadsemail'" );

$count = 0;

/**
 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
 * В противном случае получим ошибку "safemysql MySQL server has gone away"
 */
unset( $db );
$db = new SafeMySQL( $opts );

//максимальный id всех ящиков
$maxBOX = $db -> getOne( "SELECT MAX(id) FROM ".$sqlname."smtp WHERE active = 'yes' and tip = 'lead'" );

if ( $lastBOX == $maxBOX ) {
	$lastBOX = 0;
}

$bx = new Leads();

// список ящиков
$boxes = $bx -> boxList( [
	"active"  => "yes",
	"lastBOX" => $lastBOX,
	"limit"   => 5
] );

unset( $bx );

$total     = 0;
$totalNote = '';

foreach ( $boxes as $id => $ebox ) {

	$identity = $ebox['identity'];

	$count++;

	unset( $db );
	$db = new SafeMySQL( $opts );

	// устанавливаем зону к зоне клиента
	$tmzone = $db -> getOne( "SELECT timezone FROM ".$sqlname."settings WHERE id = '$ebox[identity]'" );

	// устанавливаем временную зону для ящика
	date_default_timezone_set( $tmzone );

	/**
	 * Устанавливаем дату в БД с учетом настроек сервера и смещением для пользователя. старт
	 */
	$tz  = new DateTimeZone( $tmzone );
	$dz  = new DateTime();
	$dzz = $tz -> getOffset( $dz );

	//print $tzone;
	$bdtimezone = $dzz / 3600 + $tzone;

	//если значение не корректно (больше 12), то игнорируем смещение временной зоны
	if ( abs( $bdtimezone ) > 12 ) {

		$tzone      = 0;
		$bdtimezone = $dzz / 3600;

	}

	$bdtimezone = ($bdtimezone > 0) ? "+".abs( $bdtimezone ) : "-".abs( $bdtimezone );
	$db -> query( "SET time_zone = '".$bdtimezone.":00'" );
	/**
	 * Установили временную зону. Финиш
	 */

	unset( $lead );

	try {

		$params = [
			"id"       => $id,
			"days"     => 7,
			"process"  => true,
			"identity" => $ebox['identity']
		];

		//print_r($params);

		$lead     = new Leads();
		$messages = $lead -> getMessages( $params );
		$ignored  = $lead -> ignored;
		$note     = $lead -> note;
		$error    = $lead -> error;

		$countLeads   = count( $note );
		$countBox     = count( $messages );
		$countIgnored = count( $ignored );

		$total += $countLeads;

	}
	catch ( Exception $e ) {

		print $error[] = $e -> getMessage();

	}

	$error = !empty( $error ) ? "\n".implode( "\n", $error ) : "Нет";

	$text = current_datumtime()."
		Ящик ".$ebox['name'].": ".$ebox['smtp_user']." (ID ".$ebox['id']."):
		- Загружено писем: $countBox
		- Добавлено в заявки: $countLeads
		- Игнорировано писем (загружены ранее): $countIgnored
		- Ошибки: ".$error."
		==================================
		
	";

	file_put_contents( $logfile, str_replace( "\t", "", $text ), FILE_APPEND );

	$totalNote .= $text;
	$lastBOX   = $ebox['id'];

	if ( $lastBOX == $maxBOX ) {
		$lastBOX = 0;
	} //если проверяемый ящик последний в списке, то обнуляем значение счетчика

	//записываем новое значение счетчика - сколько ящиков мы уже прошли
	$arg = ["box" => $lastBOX];
	$db -> query( "UPDATE ".$sqlname."cronwork SET ?u WHERE name = 'cronleadsemail'", $arg );

	//print str_replace("\t", "", $text);
	//flush();
	//ob_flush();

	print str_replace( "\t", "", $text );
	flush();
	ob_flush();

}

print str_replace( "\t", "", "Загружено $total записей из $count ящиков" );

flush();
ob_flush();

exit();