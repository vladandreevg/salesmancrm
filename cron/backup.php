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
 * Универсальный скрипт резервного копирования БД
 * Поддерживает Linux, Windows, macOS и прочие Unix-like системы
 */

use Salesman\ZipFolder;

set_time_limit(0);
error_reporting(E_ERROR);

$root = dirname(__DIR__);

require_once $root . "/inc/config.php";
require_once $root . "/inc/dbconnector.php";

// запуск только из CLI либо администратором через веб
if (PHP_SAPI !== 'cli') {
	require_once $root . "/inc/auth.php";
	require_once $root . "/inc/settings.php";
	if ((int)$iduser1 < 1 || ($isadmin != 'on' && $tipuser != 'Администратор')) {
		http_response_code(403);
		exit('Доступ запрещен');
	}
}

if (!class_exists(ZipFolder::class)) {
    require_once $root . "/inc/class/ZipFolder.php";
}

// --- Проверка обязательных переменных из config.php ---
$required = ['database', 'dbusername', 'dbpassword', 'dbhostname', 'sqlname'];
foreach ($required as $var) {
    if (empty($$var)) {
        fwrite(STDERR, "ERROR: Variable \${$var} is not defined in config\n");
        exit(1);
    }
}

$backupDir = $root . "/files/backup/";

if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
    fwrite(STDERR, "ERROR: Cannot create directory {$backupDir}\n");
    exit(1);
}

// PID-замок: не запускаем второй экземпляр бэкапа параллельно
$lockFile = $root . "/cron/backup.lock";
$lockFp   = @fopen($lockFile, 'c');
if ($lockFp === false || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "ERROR: Another backup process is running\n");
    exit(1);
}
register_shutdown_function(static function () use ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
});

// Определяем версию для имени файла
$current = '';
try {
    $current = $db->getOne("SELECT current FROM " . $sqlname . "ver ORDER BY id DESC LIMIT 1");
} catch (Exception $e) {
    $current = 'unknown';
}

// уникальный суффикс, чтобы параллельные запуски не писали в один файл
$date     = date("Y-m-d_H-i") . "-" . substr(bin2hex(random_bytes(3)), 0, 6);
$baseName = $database . "_" . $current . "_backup_" . $date;
$sqlFile  = $baseName . ".sql";
$zipFile  = $baseName . ".zip";

$osFamily = PHP_OS_FAMILY; // 'Windows', 'Linux', 'Darwin', 'BSD', 'Solaris', 'Unknown'

// ============================================================
// Утилита: найти исполняемый файл в PATH или в типичных местах
// ============================================================
function findExecutable(string $name): ?string
{
    $paths = explode(PATH_SEPARATOR, getenv('PATH'));
    
    $suffixes = (PHP_OS_FAMILY === 'Windows') ? ['.exe', '.bat', '.cmd', ''] : [''];
    
    foreach ($paths as $path) {
        foreach ($suffixes as $suffix) {
            $full = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name . $suffix;
            if (is_executable($full)) {
                return $full;
            }
        }
    }
    return null;
}

// ============================================================
// Утилита: безопасный shell-escape для аргументов
// ============================================================
function e(string $arg): string
{
    return escapeshellarg($arg);
}

// ============================================================
// Логгер
// ============================================================
function logMsg(string $msg): void
{
    $line = date("Y-m-d H:i:s") . " " . $msg . PHP_EOL;
    echo $line;
    if (defined('STDERR') && is_resource(STDERR)) {
        fwrite(STDERR, $line);
    }
}

// ============================================================
// Удаление старых бэкапов (старше N дней)
// ============================================================
function cleanOldBackups(string $dir, int $days = 5): void
{
    if (PHP_OS_FAMILY === 'Windows') {
        // На Windows используем PHP-логику вместо find
        foreach (['*.zip', '*.sql'] as $mask) {
            $files = glob($dir . $mask);
            $now = time();
            foreach ($files as $file) {
                if (is_file($file) && ($now - filemtime($file)) > ($days * 86400)) {
                    @unlink($file);
                }
            }
        }
        return;
    }
    
    $cmd = 'find ' . e($dir) . ' -maxdepth 1 -type f \( -name "*.zip" -o -name "*.sql" \) -mtime +' . (int)$days . ' -exec rm -f {} \;';
    exec($cmd, $out, $exit);
}

// ============================================================
// Чистый PHP-дамп (fallback, если нет mysqldump)
// ============================================================
function dumpViaPhp($db, string $database, string $sqlname): string
{
    $output = "-- SalesMan CRM Backup\n";
    $output .= "-- Generated: " . date("Y-m-d H:i:s") . "\n";
    $output .= "-- Server: " . $db->getOne("SELECT @@hostname") . "\n";
    $output .= "SET NAMES utf8mb4;\n";
    $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    
    $tables = $db->getCol('SHOW TABLES');
    
    foreach ($tables as $table) {
        // Фильтруем только таблицы с префиксом текущей БД
        if (strpos($table, $sqlname) === false) {
            continue;
        }
        
        $output .= "-- -------------------------------\n";
        $output .= "-- Table structure for `{$table}`\n";
        $output .= "-- -------------------------------\n";
        
        $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
        
        $show = $db->getRow("SHOW CREATE TABLE `{$table}`");
        $create = (is_array($show) && !empty($show['Create Table'])) ? $show['Create Table'] : '';
        // Исправляем устаревший charset/collation если нужно
        $create = str_replace("utf8_general_ci", "utf8mb4_general_ci", $create);
        $create = str_replace("DEFAULT CHARSET=utf8", "DEFAULT CHARSET=utf8mb4", $create);
        $output .= $create . ";\n\n";
        
        // Данные
        $result = $db->query("SELECT * FROM `{$table}`");
        $hasRows = false;
        
        while ($row = $db->fetch($result, MYSQLI_NUM)) {
            if (!$hasRows) {
                $output .= "-- Dumping data for `{$table}`\n";
                $hasRows = true;
            }
            
            $values = [];
            foreach ($row as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $db->parse('?s', (string)$val);
                }
            }
            $output .= "INSERT INTO `{$table}` VALUES (" . implode(", ", $values) . ");\n";
        }
        
        if ($hasRows) {
            $output .= "\n";
        }
    }
    
    $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    return $output;
}

// ============================================================
// MAIN
// ============================================================

// Удаляем старые бэкапы
cleanOldBackups($backupDir, 5);

$useNative = false;
$nativeDumpPath = null;

// Пытаемся найти нативный mysqldump
if ($osFamily !== 'Windows') {
    $nativeDumpPath = findExecutable('mysqldump');
    if ($nativeDumpPath) {
        $useNative = true;
    }
} else {
    // На Windows ищем .exe
    $nativeDumpPath = findExecutable('mysqldump');
    
    // Fallback на старые пути SalesMan/OpenServer
    if (!$nativeDumpPath) {
        $drive = strtoupper(substr($root, 0, 1));
        $candidates = [
            $drive . ":\\OpenServer\\tools\\mysqldump.exe",
            $drive . ":\\SalesmanServer\\tools\\mysqldump.exe",
            $drive . ":\\tools\\mysqldump.exe",
        ];
        foreach ($candidates as $c) {
            if (file_exists($c) && is_executable($c)) {
                $nativeDumpPath = $c;
                break;
            }
        }
    }
    
    if ($nativeDumpPath) {
        $useNative = true;
    }
}

if ($useNative && $nativeDumpPath) {
    logMsg("Using native mysqldump: {$nativeDumpPath}");
    
    // Проверяем, поддерживает ли конкретный бинарник mysqldump параметр
    // --set-gtid-purged (в MariaDB его нет, mysqldump завершится с ошибкой)
    exec(e($nativeDumpPath) . ' --no-defaults --set-gtid-purged=OFF --help 2>&1', $probeOut, $probeCode);
    $useGtidPurged = ($probeCode === 0);
    
    $sqlPath = $backupDir . $sqlFile;
    
    // Безопасный способ передачи пароля — через временный конфиг-файл,
    // чтобы пароль не светился в ps/top
    $cnfFile = sys_get_temp_dir() . '/mysqldump_' . uniqid() . '.cnf';
    $cnfContent = "[mysqldump]\n" .
                  "user=" . $dbusername . "\n" .
                  "password=" . $dbpassword . "\n" .
                  "host=" . $dbhostname . "\n";
    
    file_put_contents($cnfFile, $cnfContent);
    chmod($cnfFile, 0600);
    
    $cmd = e($nativeDumpPath) .
           ' --defaults-extra-file=' . e($cnfFile) .
           ' --add-drop-table' .
           ' --disable-keys' .
           ' --comments' .
           ' --routines' .
           ' --triggers' .
           ' --single-transaction' .
           ($useGtidPurged ? ' --set-gtid-purged=OFF' : '') .
           ' ' . e($database) .
           ' > ' . e($sqlPath);
    
    exec($cmd, $output, $exitCode);
    @unlink($cnfFile);
    
    if ($exitCode !== 0 || !file_exists($sqlPath) || filesize($sqlPath) === 0) {
        logMsg("ERROR: mysqldump failed with code {$exitCode}");
        @unlink($sqlPath);
        exit(1);
    }
    
    logMsg("mysqldump: OK (" . number_format(filesize($sqlPath)) . " bytes)");
    
    // Архивация
    if ($osFamily === 'Windows') {
        $zipper = findExecutable('7z');
        if (!$zipper) {
            $zipper = findExecutable('7za');
        }
        if (!$zipper) {
            $drive = strtoupper(substr($root, 0, 1));
            $candidates = [
                $drive . ":\OpenServer\tools\7zip\7za.exe",
                $drive . ":\SalesmanServer\tools\7zip\7za.exe",
                $drive . ":\tools\7zip\7za.exe",
            ];
            foreach ($candidates as $c) {
                if (file_exists($c) && is_executable($c)) {
                    $zipper = $c;
                    break;
                }
            }
        }
        if ($zipper) {
            $cmd = e($zipper) . ' a -tzip ' . e($sqlPath . '.zip') . ' ' . e($sqlPath);
            exec($cmd, $output, $exitCode);
        } else {
            // Fallback на PHP ZipArchive
            $zip = new ZipArchive();
            if ($zip->open($sqlPath . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($sqlPath, basename($sqlFile));
                $zip->close();
                $exitCode = 0;
            } else {
                $exitCode = 1;
            }
        }
    } else {
        $zipper = findExecutable('zip');
        if ($zipper) {
            $cmd = e($zipper) . ' -9 -m -j ' . e($sqlPath . '.zip') . ' ' . e($sqlPath);
            exec($cmd, $output, $exitCode);
        } else {
            $zip = new ZipArchive();
            if ($zip->open($sqlPath . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $zip->addFile($sqlPath, basename($sqlFile));
                $zip->close();
                @unlink($sqlPath);
                $exitCode = 0;
            } else {
                $exitCode = 1;
            }
        }
    }
    
    if ($exitCode !== 0 || !file_exists($sqlPath . '.zip')) {
        logMsg("ERROR: ZIP creation failed");
        @unlink($sqlPath);
        exit(1);
    }
    
    @unlink($sqlPath);
    
    logMsg("ZIP: OK — {$zipFile}");
    
} else {
    // Fallback: чистый PHP-дамп
    logMsg("mysqldump not found, using PHP fallback");
    
    $sqlPath = $backupDir . $sqlFile;
    $content = dumpViaPhp($db, $database, $sqlname);
    
    if (file_put_contents($sqlPath, $content) === false) {
        logMsg("ERROR: Cannot write SQL file");
        exit(1);
    }
    
    logMsg("PHP-dump: OK (" . number_format(strlen($content)) . " bytes)");
    
    // Архивация через ZipFolder (класс проекта)
    if (class_exists(ZipFolder::class)) {
        $zip = new ZipFolder();
        $zip->zipFile(basename($zipFile), $backupDir, $sqlPath);
    } else {
        $zip = new ZipArchive();
        if ($zip->open($sqlPath . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFile($sqlPath, basename($sqlFile));
            $zip->close();
        }
    }
    
    @unlink($sqlPath);
    
    if (!file_exists($sqlPath . '.zip')) {
        logMsg("ERROR: ZIP creation failed");
        exit(1);
    }
    
    logMsg("ZIP: OK — {$zipFile}");
}

exit(0);
