<?php
set_time_limit(0);

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__ );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

/**
 * Фикс default-значений для таблиц
 */

$br = PHP_SAPI === 'cli' ? "\n" : "<br>";

$result = '';
$tofile = '';

$db -> query("SET sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
$tofile .= $db -> lastQuery().";\n";

$tables = ['clientcat','dogovor','personcat','history','speca','contract','contract_temp','tasks','dogprovider','modcatalog_akt','leads','credit','mycomps','mycomps_recv','modcatalog_zayavka','modcatalog_sklad','modcatalog_skladmovepoz','modcatalog_zayavkapoz','complect_cat','projects','projects_work_types','projects_work','projects_statuslog'];

foreach ($tables as $table) {
	
	$dap = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}$table'" );
	if ( (int)$dap == 0 ) {
		
		continue;
		
	}

	$fields = $db -> getAll("SHOW FIELDS FROM {$sqlname}$table");

	$str = [];

	foreach ($fields as $i => $field){

		$fcurrent = $field['Field'];
		$fprev    = $fields[$i-1]['Field'];

		$ftype    = $field['Type'];

		if($field['Extra'] != 'auto_increment') {

			if (stripos($fcurrent, 'input') !== false) {

				$db -> query("ALTER TABLE {$sqlname}$table CHANGE COLUMN `$fcurrent` `$fcurrent` $ftype NULL DEFAULT NULL AFTER `$fprev`");
				$tofile .= $db -> lastQuery().";$br";

				print "Таблица *$table* исправлена\n";

			}
			else {

				$def   = !is_null($field['Default']) ? "'".$field['Default']."'" : 'NULL';
				$null  = $field['Null'] == 'YES' ? 'NULL' : '';
				$extra = $field['Extra'] != 'DEFAULT_GENERATED' ? $field['Extra'] : '';

				if(stripos($field['Extra'], 'DEFAULT_GENERATED') !== false){
					$extra = '';
				}

				if ($ftype == 'int' && $fcurrent != 'identity') {
					$def = '0';
				}
				elseif ($ftype == 'timestamp' && $field['Extra'] == '') {
					$def = 'NULL';
					$null = 'NULL';
				}

				$null = $field['Null'] == 'NO' ? '' : 'NULL';

				if($def == "'CURRENT_TIMESTAMP'"){
					$def = "CURRENT_TIMESTAMP";
					$null = $field['Null'] == 'NO' ? 'NOT NULL' : 'NULL';
				}

				if( $fcurrent == 'date_create' ){
					$def = "CURRENT_TIMESTAMP";
				}
				if( $fcurrent == 'datum' && $table == 'speca' ){
					$def = "CURRENT_TIMESTAMP";
				}
				if( $fcurrent == 'datum' && $table == 'contract' ){
					$def = "NULL";
					$null = 'NULL';
					$ftype = 'DATETIME';
				}
				if( $fcurrent == 'datum' && in_array($table,['modcatalog_akt','leads']) ){
					$def = "CURRENT_TIMESTAMP";
					$null = 'NOT NULL';
					$ftype = 'timestamp';
				}

				$db -> query("ALTER TABLE {$sqlname}$table CHANGE COLUMN `$fcurrent` `$fcurrent` $ftype $null DEFAULT $def $extra AFTER `$fprev`");
				$tofile .= $db -> lastQuery().";$br";

				print "Таблица *$table* исправлена\n";

			}

		}

	}

}

$db -> query("ALTER TABLE {$sqlname}speca CHANGE COLUMN `datum` `datum` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `edizm`");
$tofile .= $db -> lastQuery().";$br";

$db -> query("UPDATE {$sqlname}dogovor SET close = 'no' WHERE close IS NULL");
$tofile .= $db -> lastQuery().";$br";

file_put_contents($rootpath."/cash/fix_default_values_log.sql", $tofile);

if( PHP_SAPI === 'cli' ){
	
	print "\nПрограмма выполнена успешно!\n";
	
}
else {
	
	print "<hr>";
	print "Программа выполнена успешно!<br>";
	
}

print "Лог выполненных запросов сохранен в файл: "."/cash/fix_default_values_log.sql";