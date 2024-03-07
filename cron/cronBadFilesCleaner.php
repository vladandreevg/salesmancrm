<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Скрипт-демон для работы в фоне
 * Производит поиск мусорных файлов
 * Поддерживает PHP 5.6 и выше
 * Узнать путь, где расположен PHP ( locate -b '\php' - список размещения исполняемых файлов PHP )
 * По умолчанию работает в тестовом режиме - расчитывает количество мусора, но не удаляет его
 * Поддерживается передача аргументов:
 * - work - рабочий режим
 * @example php cronBadFilesCleaner.php work
 */

use Salesman\Mailer;
use Salesman\User;

set_time_limit( 0 );

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
ini_set('memory_limit', '512M');

$work = $argv[1];

$root = dirname(__DIR__);

require_once $root."/inc/config.php";

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

// тестовый режим
$isTest = true;

if($work == 'work'){
	$isTest = false;
}

define( "isTest", $isTest );

print "\n\033[31mAbout\033[0m: Скрипт производит очистку диска от старых/ненужных файлов\n";

if(isTest){

	print "\n\033[31mВНИМАНИЕ\033[0m: скрипт работает в тестовом режиме - \033[31mданные не удаляются!\033[0m\n";
	print "\nДля выполнения в полном режиме передайте параметр \033[32mwork\033[0m\n\n";

}
else{

	print "\n\033[32mВНИМАНИЕ:\033[0m скрипт работает в полном режиме - \033[32mданные удаляются!\033[0m\n";

}

require_once $root."/inc/dbconnector.php";
require_once $root."/inc/func.php";

/**
 * Скрипт сверяет существующие файлы в папках files/ и files/ymail/
 * и проверяет их наличие в базе данных (наличие в БД file и contract)
 * если в БД не находит, то удаляет
 * считаем, что такие файлы "мусорные"
 * в конце проверяет файлы в таблице file и их реальное наличие
 * если не находит, то удаляет запись из БД
 */
function badFiles(): string {

	$db      = $GLOBALS['db'];
	$sqlname = $GLOBALS['sqlname'];

	require_once ROOT."/inc/config.php";
	require_once ROOT."/inc/dbconnector.php";

	$notexistFiles = [];
	$totalSize   = 0;
	$totalCount  = 0;

	/**
	 * Получение списка файлов для синхронизации
	 *
	 * @param $folder
	 * @return array
	 */
	function getFiles($folder): array {

		require_once ROOT."/inc/func.php";

		//папка, в которой ищем файлы
		//$path = str_replace( "/", "\\",$folder );
		$path = $folder;

		$last = '';//current_datum(30);
		$diff = 180;
		$apx  = '';

		//дата последнего выполнения
		if ( file_exists( ROOT."/cash/BadFilesCleaner.txt" ) ) {

			$last = file_get_contents( ROOT."/cash/BadFilesCleaner.txt" );
			$diff = diffDate( $last ) + 1;

		}

		//если скрипт уже выполнялся - ограничиваем по времени
		if ( $last != '' ) {
			$apx = "-mtime -$diff";
		}

		/**
		 * команда для вывода списка файлов в каталоге, созданные за последние 2 дня
		 * -maxdepth - глубина поиска (1 - текущий каталог)
		 * -mtime - фильтр по времени
		 * -type f - учитывать только файлы
		 * $output - массив файлов
		 */
		$cmd = "find $path -maxdepth 1 -type f $apx";

		exec( $cmd, $list, $exit );

		//print_r($list);
		//flush();

		//возвращает только имена файлов, без пути
		return array_map( static function($var) {
			return basename( $var );
		}, $list );

	}

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

	/**
	 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
	 * В противном случае получим ошибку "safemysql MySQL server has gone away"
	 */
	unset( $db );
	$db = new SafeMySQL( OPTS );

	$result = $db -> query( "SELECT id FROM ".$sqlname."settings ORDER BY id" );
	while ($data = $db -> fetch( $result )) {

		/**
		 * Файлы - /files
		 */

		//получаем список файлов в папке
		$path = "files/";

		print "\nFOLDER = ".ROOT."/".$path."\n";
		flush();

		if ( file_exists( ROOT."/".$path ) ) {

			$fileList = getFiles( ROOT."/".$path );

			//print_r($fileList);
			print "Всего файлов в папке = ".count($fileList)."\n";
			flush();

			$xcount = 0;
			$xtotalSize = 0;
			$xtotalCount = 0;
			$total = count($fileList);

			foreach ( $fileList as $file ) {

				if ( $file == '' || $file == '.gitignore' ) {
					continue;
				}

				unset( $db2 );
				$db2 = new SafeMySQL( OPTS );

				//ищем в файлах
				$fid = $db2 -> getOne( "SELECT fid FROM ".$sqlname."file WHERE fname = '$file' AND identity = '".$data['id']."'" );

				//если не нашли в файлах..
				if ( (int)$fid == 0 ) {

					//.., то ищем в документах
					$deid = $db2 -> getOne( "SELECT deid FROM ".$sqlname."contract WHERE fname LIKE '%$file%' AND identity = '".$data['id']."'" );

					//если не нашли, то ищем в обсуждениях
					if ( (int)$deid == 0 ) {

						$commentid = $db2 -> getOne( "SELECT id FROM ".$sqlname."comments WHERE FIND_IN_SET('$fid', REPLACE(".$sqlname."comments.fid, ';',',')) > 0 and identity = '".$data['id']."'" );

						if ( (int)$commentid == 0 ) {

							$file_size = filesize( ROOT."/".$path.$file );

							$xtotalSize += $file_size;
							$xtotalCount++;

							if ( !isTest ) {
								unlink( ROOT."/".$path.$file );
							}

						}

					}

				}

				$xcount++;

				$percent = ($xcount / $total) * 100;
				showProgressBar($percent, 2, $xcount);

				//ext1:

			}

		}

		$totalSize += $xtotalSize;
		$totalCount += $xtotalCount;

		print PHP_EOL;

		printf("Найдено плохих файлов %s размером %s\n", $xtotalCount, FileSize2Human($xtotalSize));

		/**
		 * Файлы почты - /files/ymail
		 */

		//получаем список файлов в папке
		$path = "files/ymail/";

		print "\nFOLDER = ".ROOT."/".$path."\n";
		flush();

		if ( file_exists( ROOT."/".$path ) ) {

			$fileList = getFiles( ROOT."/".$path );

			print "Всего файлов в папке = ".count($fileList)."\n";
			flush();

			$xcount = 0;
			$xtotalSize = 0;
			$xtotalCount = 0;
			$total = count($fileList);

			foreach ( $fileList as $file ) {

				unset( $db2 );
				$db2 = new SafeMySQL( OPTS );

				if ( $file == '' || $file == '.gitignore' ) {
					continue;
				}

				$yfid = $db2 -> getOne( "select id from ".$sqlname."ymail_files WHERE file = '$file' and identity = '".$data['id']."'" );

				if ( (int)$yfid == 0 ) {

					$file_size = filesize( ROOT."/".$path.$file );

					$xtotalSize += $file_size;
					$xtotalCount++;

					if ( !isTest ) {
						unlink( ROOT."/".$path.$file );
					}

				}

				$xcount++;

				$percent = ($xcount / $total) * 100;
				showProgressBar($percent, 2, $xcount);

				//ext2:

			}

		}

		$totalSize += $xtotalSize;
		$totalCount += $xtotalCount;

		print PHP_EOL;

		printf("Найдено плохих файлов %s размером %s\n", $xtotalCount, FileSize2Human($xtotalSize));
		flush();


		if ( !$GLOBALS['isCloud'] && $data['id'] == 1 ) {
			goto ext;
		}

	}

	ext:

	/**
	 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
	 * В противном случае получим ошибку "safemysql MySQL server has gone away"
	 */
	unset( $db );
	$db = new SafeMySQL( OPTS );

	/**
	 * Удаленные файлы
	 */
	$result = $db -> query( "SELECT fid, fname, identity FROM ".$sqlname."file ORDER BY fid" );

	$xcount = 0;
	$xtotalSize = 0;
	$xtotalCount = 0;
	$ftotal = $db -> affectedRows();

	print "\nВсего файлов в папке /files/ = $ftotal\n";
	flush();

	while ($data = $db -> fetch( $result )) {

		//unset( $db );
		//$db = new SafeMySQL( OPTS );

		$path = $GLOBALS['isCloud'] ? "files/".$data['identity']."/" : "files/";

		//если файла нет, то удаляем запись
		if ( !file_exists( ROOT."/".$path.$data['fname'] ) ) {

			//print $filepath.$path.$data['fname']."<br>";

			if ( !isTest ) {
				$db -> query( "DELETE FROM ".$sqlname."file WHERE fid = '$data[fid]'" );
			}

			$notexistFiles[ $data['identity'] ][] = [
				"file" => ROOT."/".$path.$data['fname'],
				"size" => 0
			];

			$file_size = filesize( ROOT."/".$path.$data['fname'] );

			$xtotalSize += $file_size;
			$xtotalCount++;

		}

		$xcount++;

		$percent = ($xcount / $ftotal) * 100;
		showProgressBar($percent, 2, $xcount);

	}

	print PHP_EOL;

	$totalSize += $xtotalSize;
	$totalCount += $xtotalCount;

	printf("\nНайдено плохих файлов %s размером %s\n", $xtotalCount, $xtotalSize);

	return "\nВсего \"плохих\" (не привязанных к БД) файлов: ".$totalCount.", занимают: ". (float)FileSize2Human($totalSize)."\nУдаленные ранее файлы (отсутствующие на диске): ".count( $notexistFiles[1] )."\n";

}

/**
 * Проверка и очистка записей о файлах в БД, которые не существуют на сервере
 */
print badFiles();
flush();

exit();