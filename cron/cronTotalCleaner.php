<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2021 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2021.x           */
/* ============================ */

/**
 * Скрипт-демон для работы в фоне
 * Производит очистку БД от старых и не нужных записей
 * Поддерживает PHP 5.6 и выше
 * Узнать путь, где расположен PHP ( locate -b '\php' - список размещения исполняемых файлов PHP )
 * По умолчанию работает в тестовом режиме - расчитывает количество мусора, но не удаляет его
 * Поддерживается передача аргументов:
 * - work - рабочий режим
 * @example php cronTotalCleaner.php work
 */

set_time_limit( 0 );

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
ini_set('memory_limit', '512M');

$work = $argv[1];

$root = dirname( __DIR__ );

require_once $root."/inc/config.php";

function showProgressBar($percentage, int $numDecimalPlaces, $value = 0) {

	$percentageStringLength = 4;
	if ($numDecimalPlaces > 0) {
		$percentageStringLength += ($numDecimalPlaces + 1);
	}

	$percentageString = number_format($percentage, $numDecimalPlaces).'%';
	$percentageString = str_pad($percentageString, $percentageStringLength, " ", STR_PAD_LEFT);

	$percentageStringLength += 3; // add 2 for () and a space before bar starts.

	$terminalWidth = 100;//`tput cols`;
	$barWidth      = $terminalWidth - ($percentageStringLength) - 2; // subtract 2 for [] around bar
	$numBars       = round(($percentage) / 100 * ($barWidth));
	$numEmptyBars  = $barWidth - $numBars;

	$barsString = '['.str_repeat("=", ($numBars)).str_repeat(" ", ($numEmptyBars)).'] '.$value;

	echo "($percentageString) ".$barsString."\r";

}

// тестовый режим
$isTest = true;

if($work == 'work'){
	$isTest = false;
}

print "\n\033[31mAbout\033[0m: Скрипт производит очистку БД и диска от старых/ненужных записей и файлов\n";

if($isTest){

	print "\n\033[31mВНИМАНИЕ\033[0m: скрипт работает в тестовом режиме - \033[31mданные не удаляются!\033[0m\n";
	print "\nДля выполнения в полном режиме передайте параметр \033[32mwork\033[0m\n\n";

}
else{

	print "\n\033[32mВНИМАНИЕ:\033[0m скрипт работает в полном режиме - \033[32mданные удаляются!\033[0m\n\n";

}

/**
 * Данные для отладки
 */

$opts = [
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'errmode' => 'exception',
	'charset' => 'UTF8'
];

define('ROOT', $root);
define('OPTS', $opts);

$day   = 90;
$count = 0;
$fcount = 0;
$total = 0;
$identity = 1;

require_once $root."/inc/dbconnector.php";
require_once $root."/inc/func.php";
require_once $root."/inc/settings.php";

$ym_fpath = $root.'/files/ymail/';

$settingsFile = $root."/cash/settings.all.json";
$settings = json_decode( file_get_contents( $settingsFile ), true );
$tmzone       = $settings["tmzone"];

// отключаем ключи
$db -> query("SET FOREIGN_KEY_CHECKS = 0");

$time_start = current_datumtime();

$timeStart = modifyDatetime("", [
	"format" => "Y-m-d_H-i"
]);

print "Start at ".modifyDatetime("", [
		//"hours"  => $bdtimezone,
		"format" => "d.m H:i"
	])."\n";

//print "\nНачинаем удаление старых писем\n";
//flush();

/**
 * Удаление старых писем
 */
$result = $db -> query( "
	SELECT id, datum 
	FROM {$sqlname}ymail_messages 
	WHERE 
		id > 0 AND 
		DATE(datum) < DATE( NOW() - INTERVAL $day DAY)
	ORDER BY datum
" );

$total = $db -> affectedRows();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

$fsize = 0;

while ($da = $db -> fetch( $result )) {

	//unset( $db2 );
	//$db2 = new SafeMySQL( $opts );

	$res = $db2 -> getAll( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$da[id]'" );
	foreach ($res as $data) {

		//unset( $db2 );
		//$db2 = new SafeMySQL( $opts );

		//удалим файлы
		if(!$isTest) {

			$db2 -> query( "DELETE FROM {$sqlname}ymail_files WHERE id = '$data[id]'" );

			if ( file_exists( $ym_fpath.$data['file'] ) ) {
				$fsize += filesize($ym_fpath.$data['file']);
				unlink( $ym_fpath.$data['file'] );
				$fcount++;
			}

		}
		elseif ( file_exists( $ym_fpath.$data['file'] ) ) {
			$fsize += filesize($ym_fpath.$data['file']);
			$fcount++;
		}

	}

	$count++;

	//unset( $db2 );
	//$db2 = new SafeMySQL( $opts );

	// удаляем даресатов
	if(!$isTest) {

		$db -> query( "DELETE FROM {$sqlname}ymail_messages WHERE id = '$da[id]'" );
		$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE mid = '$da[id]'" );

	}

	$percent = ($count / $total) * 100;
	showProgressBar($percent, 2, $count);

}

print PHP_EOL;

print "Удалено: \033[32m$count\033[0m старых писем (старше $day дней) и \033[32m$fcount\033[0m файлов из почты (размер ".(float)FileSize2Human($fsize).")\n";
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

// удаляем "потерянные" файлы, у которых письма уже удалены
//$res = $db2 -> query( "SELECT id, mid FROM {$sqlname}ymail_files WHERE (SELECT id FROM {$sqlname}ymail_messages WHERE id = mid) IS NULL" );
//print "удаляем \"потерянные\" файлы, у которых письма уже удалены\n";
if(!$isTest) {
	$res = $db2 -> query( "DELETE FROM {$sqlname}ymail_files WHERE (SELECT id FROM {$sqlname}ymail_messages WHERE id = {$sqlname}ymail_files.mid) IS NULL" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m \"потерянных\" файлов, у которых письма уже удалены\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}ymail_files WHERE (SELECT id FROM {$sqlname}ymail_messages WHERE id = {$sqlname}ymail_files.mid) IS NULL" );
	print "Удалено: \033[32m".$res."\033[0m \"потерянных\" файлов, у которых письма уже удалены\n";
}

flush();

// удаляем письма, не привязанные к клиентам
// print "удаляем письма, не привязанные к клиентам\n";
if(!$isTest) {

	$db2 -> query( "
		DELETE ym
		FROM {$sqlname}ymail_messages `ym`
		LEFT JOIN {$sqlname}ymail_messagesrec `yr` ON yr.mid = ym.id
		WHERE 
		    yr.clid = '0' AND 
		    yr.pid = '0' AND 
		    DATE(ym.datum) < DATE( NOW() - INTERVAL $day DAY)
		" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m писем, не привязанных к клиентам старше $day дней\n";

	// удаляем отправителей
	$db2 -> query("
		DELETE yr
		FROM {$sqlname}ymail_messagesrec `yr` 
		LEFT JOIN {$sqlname}ymail_messages `ym` ON ym.id = yr.mid
		WHERE 
		    yr.clid = '0' AND 
		    yr.pid = '0' AND 
		    DATE(ym.datum) < DATE( NOW() - INTERVAL $day DAY)
		");

}
else{

	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}ymail_messages WHERE (SELECT DISTINCT(mid) FROM {$sqlname}ymail_messagesrec WHERE mid = {$sqlname}ymail_messages.id AND clid = '0' AND pid = '0') > 0 AND DATE(datum) < DATE( NOW() - INTERVAL $day DAY)" );
	print "Удалено: \033[32m".$res."\033[0m писем, не привязанных к клиентам старше $day дней\n";

}

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Очистка истории звонков
 */

// старые записи внутренних звонков
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}callhistory WHERE DATE(datum) < DATE( NOW() - INTERVAL 30 DAY ) AND direct = 'inner'" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей внутренних звонков старше 30 дней\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}callhistory WHERE DATE(datum) < DATE( NOW() - INTERVAL 30 DAY) AND direct = 'inner'" );
	print "Удалено: \033[32m".$res."\033[0m записей внутренних звонков старше 30 дней\n";
}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

// старые звонки, не привязанные к клиентам/контактам
if(!$isTest) {
	//$db -> query("DELETE FROM {$sqlname}callhistory WHERE DATEDIFF(NOW() - INTERVAL 30 DAY, datum) > 0 AND clid = 0 AND pid = 0");
	$db2 -> query("DELETE FROM {$sqlname}callhistory WHERE DATE(datum) < DATE( NOW() - INTERVAL 30 DAY) AND clid = 0 AND pid = 0");
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m не привязанных звонков старше 30 дней\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}callhistory WHERE DATE(datum) < DATE( NOW() - INTERVAL 30 DAY) AND clid = 0 AND pid = 0" );
	print "Удалено: \033[32m".$res."\033[0m не привязанных звонков старше 30 дней\n";
}
flush();

// старые записи разговоров
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}callhistory WHERE DATE(datum) < DATE(NOW() - INTERVAL 6 MONTH)" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m старых звонков, старше 6 месяцев\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}callhistory WHERE DATE(datum) < DATE(NOW() - INTERVAL 6 MONTH)" );
	print "Удалено: \033[32m".$res."\033[0m старых звонков, старше 6 месяцев\n";
}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Чистим логи Webhook
 */
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}webhooklog WHERE DATE(datum) < DATE(NOW() - INTERVAL 10 DAY)" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей лога веб-хуков\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhooklog WHERE DATE(datum) < DATE(NOW() - INTERVAL 10 DAY)" );
	print "Удалено: \033[32m".$res."\033[0m записей лога веб-хуков\n";
}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Чистим логи API
 */
$da = $db2 -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."logapi'" );
if ( $da[0] > 0 ) {

	if ( !$isTest ) {
		$db2 -> query( "DELETE FROM {$sqlname}logapi WHERE DATE(datum) < DATE(NOW() - INTERVAL 10 DAY)" );
		print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m запросов к АПИ\n";
	}
	else {
		$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}logapi WHERE DATE(datum) < DATE(NOW() - INTERVAL 10 DAY)" );
		print "Удалено: \033[32m".$res."\033[0m запросов к АПИ\n";
	}

}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Удаление старых активностей > 3 лет
 */
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}history WHERE DATE(datum) < DATE(NOW() - INTERVAL 3 YEAR)" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей активностей более 3 лет\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}history WHERE DATE(datum) < DATE(NOW() - INTERVAL 3 YEAR)" );
	print "Удалено: \033[32m".$res."\033[0m записей активностей более 3 лет\n";
}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Удаление старых напоминаний > 1 года
 */
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}tasks WHERE DATE(datum) < DATE(NOW() - INTERVAL 1 YEAR)" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей напоминаний более 1 года\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}tasks WHERE DATE(datum) < DATE(NOW() - INTERVAL 1 YEAR)" );
	print "Удалено: \033[32m".$res."\033[0m записей напоминаний более 1 года\n";
}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Удаление лога записей уведомления пользователей > 1 месяца
 */
$dap = $db2 -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}usernotifier_log'" );
if ( (int)$dap > 0 ) {

	if(!$isTest) {
		$db2 -> query( "DELETE FROM {$sqlname}usernotifier_log WHERE DATE(datum) < DATE(NOW() - INTERVAL 1 WEEK)" );
		print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей лога плагина Уведомление пользователей\n";
	}
	else{
		$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}usernotifier_log WHERE DATE(datum) < DATE(NOW() - INTERVAL 1 WEEK)" );
		print "Удалено: \033[32m".$res."\033[0m записей лога плагина Уведомление пользователей\n";
	}
	flush();

}

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Удаление истории
 * Записи старше 180 дней
 */
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}history WHERE DATE(datum) < DATE(NOW() - INTERVAL 180 DAY) AND tip IN ('ЛогCRM','СобытиеCRM')" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей активностей старше 180 дней с типом 'ЛогCRM','СобытиеCRM'\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}history WHERE DATE(datum) < DATE(NOW() - INTERVAL 180 DAY) AND tip IN ('ЛогCRM','СобытиеCRM')" );
	print "Удалено: \033[32m".$res."\033[0m записей активностей старше 180 дней с типом 'ЛогCRM','СобытиеCRM'\n";
}
flush();

unset( $db2 );
$db2 = new SafeMySQL( $opts );

/**
 * Удаление активностей с неизвестным типом
 */
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}history WHERE tip NOT IN (SELECT title FROM {$sqlname}activities) AND tip NOT IN ('ЛогCRM','СобытиеCRM')" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей активностей с неизвестным типом\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}history WHERE tip NOT IN (SELECT title FROM {$sqlname}activities) AND tip NOT IN ('ЛогCRM','СобытиеCRM')" );
	print "Удалено: \033[32m".$res."\033[0m записей активностей с неизвестным типом\n";
}
flush();

/**
 * Удаление активностей с пустым содержимым
 */
if(!$isTest) {
	$db2 -> query( "DELETE FROM {$sqlname}history WHERE COALESCE(des, '') = ''" );
	print "Удалено: \033[32m".$db2 -> affectedRows()."\033[0m записей активностей с пустым содержимым\n";
}
else{
	$res = $db2 -> getOne( "SELECT COUNT(*) FROM {$sqlname}history WHERE COALESCE(des, '') = ''" );
	print "Удалено: \033[32m".$res."\033[0m записей активностей с пустым содержимым\n";
}
flush();

unset( $db );
$db = new SafeMySQL( $opts );

// включаем ключи
$db -> query("SET FOREIGN_KEY_CHECKS = 1");

print "\nFinished at ".modifyDatetime("", [
		//"hours"  => $bdtimezone,
		"format" => "d.m H:i"
	])."\n";

$time_finish = current_datumtime();
print "Total time: ".untag(diffDateTime2($time_start, $time_finish))."\n";

$memory = FileSize2Human(memory_get_peak_usage(true));
print "Memory usage: $memory\n";

exit();