<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
set_time_limit(0);

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__ );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/vendor/autoload.php";

$opts = [
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'errmode' => 'exception',
	'charset' => 'UTF8'
];
$db = new SafeMySQL($opts);

//папка для резервной копии БД
//$rootpath = __DIR__;

//createDir($rootpath . "/files/backup/");

$current = $db -> getOne("SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1");

$qfile = "salesman_".$current."_backup_".date("Y-m-d_H-i").".sql";

//меняем права у папок на запись
exec("chmod -R 777 $rootpath/", $output, $exit);

//делаем резервную копию БД и папки cash
/*
exec( 'mysqldump --user='.$dbusername.' --password='.$dbpassword.' --host='.$dbhostname.' --add-drop-table --disable-keys --comments --routines --triggers '.$database.' > '.$rootpath.$qfile, $output1, $exit1 );
exec("zip -9 -m -j $rootpath/files/backup/$qfile.zip $rootpath/files/backup/$qfile", $output, $exit);
exec("zip -9 -r $rootpath/cash/cash.zip $rootpath/cash", $output, $exit);

unlink($rootpath.$qfile);
*/


//скачиваем файл обновления через curl
$file = "files/update.zip";
$src  = "https://salesman.pro/download/getfile.php?v=update";

$res = exec('curl -k -# "'.$src.'" -o '.$rootpath.'/'.$file, $output, $exit);

//распакуем скачанный архив
//трабла - не заменяет файлы новыми
exec("unzip -o $rootpath/$file -d $rootpath/", $output, $exit);

//вернем права на папки и файлы
exec("find $rootpath/ -type d -exec chmod 755 -R {} \\", $output, $exit);
exec("find $rootpath/ -type f -exec chmod 644 -R {} \\", $output, $exit);

//для указанных будем изменять
exec("chmod -R 777 $rootpath/files/backup", $output, $exit);
//print_r($output)."\n";
exec("chmod -R 777 $rootpath/files", $output, $exit);
//print_r($output)."\n";
exec("chmod -R 777 $rootpath/cash", $output, $exit);
//print_r($output)."\n";
exec("chmod -R 777 $rootpath/cash/logo", $output, $exit);
//print_r($output)."\n";
exec("chmod -R 777 $rootpath/cash/dompdf/", $output, $exit);
//print_r($output)."\n";

//удаляем скачанный файл
unlink($file);

/**
 * Примеры для работы из консоли:
 * curl -k -# "https://salesman.pro/download/getfile.php?v=update&p=php5.3" -o G:\update.zip
 * wget "https://salesman.pro/download/getfile.php?v=update&p=php5.3" -O G:\update.zip
 */

$sapi = PHP_SAPI;

if ($sapi == 'cli') {

	/*include $rootpath."/inc/func.php";

	$cmd = "php $rootpath/_install/update.php?step=1";
	exec($cmd, $output, $exit);

	json_encode_cyr($output);*/

	print "Дистрибутив загружен. Перейдите в браузер и выполните скрипт: https://адрес_црм/update.php\n";

}
else{

	//откроем в браузере
	header("Location: update.php");

}
