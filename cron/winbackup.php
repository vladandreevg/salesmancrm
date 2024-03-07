<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.25          */
/* ============================ */

/**
 * Скрипт резервного копирования для ОС Windows
 */

set_time_limit(0);

error_reporting(0);

include "../inc/config.php";
include "../inc/dbconnector.php";

$litera = $_SERVER['DOCUMENT_ROOT']{0};

//$basepath = "d:\\SalesmanServer";
$basepath = $litera.":";

$path = $basepath."\\home\\localhost\\www\\admin\\backup\\";

$current = $db -> getOne("SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1");

$file = "salesman_".$current."_backup_".date("Y-m-d_H-i").".sql";

exec($basepath.'\tools\mysqldump.exe --user='.$dbusername.' --password='.$dbpassword.' --host='.$dbhostname.' '.$database.' > '.$path.$file, $output1, $exit1);
exec($basepath.'\tools\7zip\7za.exe a -tzip '.$path.$file.'.zip '.$path.$file, $output2, $exit2);

$exit1 = ($exit1 == 0) ? "Ok" : $exit1;
$exit2 = ($exit2 == 0) ? "Ok" : $exit2;

print "Mysqldump:" . $exit1."<br>";
print "Zip:      " . $exit2."<br>";
print "File:     " . $file;

unlink($path.$file);

exit();
?>