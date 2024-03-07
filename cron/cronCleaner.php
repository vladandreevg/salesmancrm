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
 * Производит очистку БД от старых и не нужных записей
 * Поддерживает PHP 5.6 и выше
 * Узнать путь, где расположен PHP ( locate -b '\php' - список размещения исполняемых файлов PHP )
 */

use Salesman\Mailer;
use Salesman\User;

/**
 * Скрипт для проверки всех почтовых ящиков в фоне по расписанию CRON
 */
set_time_limit( 0 );

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );
ini_set('memory_limit', '512M');

$root = realpath( __DIR__.'/../' );

require_once $root."/inc/config.php";


/**
 * Данные для отладки
 */

//$dbusername = "salesman";
//$dbpassword = "salesman!1";
//$database   = "dupad";
//$sqlname    = "yoolla_";
//$identity   = 1;


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

const DODELETE = true;

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
function badFiles() {

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
	function getFiles($folder) {

		require_once ROOT."/inc/func.php";

		//папка, в которой ищем файлы
		//$path = str_replace( "/", "\\",$folder );
		$path = $folder;

		$last = '';//current_datum(30);
		$diff = '';
		$apx  = '';

		//дата последнего выполнения
		if ( file_exists( "data/last.txt" ) ) {

			$last = file_get_contents( "data/last.txt" );
			$diff = diffDate( $last ) + 1;

		}

		if ( PHP_OS != "Linux" ) {

			//если скрипт уже выполнялся - ограничиваем по времени
			if ( $last != '' )
				$apx = " /d ".format_date_rus( $last );

			/**
			 * Команда выводит список файлов в каталоге
			 * /m *.* - маска "все файлы"
			 * /p - задаем путь
			 */
			$cmd = "forfiles /m *.* /p $path $apx";
			exec( $cmd, $list, $exit );

			//возвращает только имена файлов, без кавычек
			$listNames = array_map( static function($var) {
				return str_replace( "\"", "", $var );
			}, $list );

		}
		else {

			//если скрипт уже выполнялся - ограничиваем по времени
			if ( $last != '' )
				$apx = "-mtime -$diff";

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
			$listNames = array_map( static function($var) {
				return basename( $var );
			}, $list );

		}

		return $listNames;

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
		$path = ($GLOBALS['isCloud'] == true) ? "files/".$data['id']."/" : "files/";

		//print "FOLDER = ".ROOT."/".$path."\n";
		//flush();

		if ( file_exists( ROOT."/".$path ) ) {

			$fileList = getFiles( ROOT."/".$path );

			//print_r($fileList);
			//flush();


			foreach ( $fileList as $file ) {

				if ( $file == '' || $file == '.gitignore' )
					goto ext1;

				//ищем в файлах
				$fid = $db -> getOne( "select fid from ".$sqlname."file WHERE fname = '$file' and identity = '".$data['id']."'" ) + 0;

				//если не нашли в файлах..
				if ( $fid == 0 ) {

					//.., то ищем в документах
					$deid = $db -> getOne( "select deid from ".$sqlname."contract WHERE fname LIKE '%$file%' and identity = '".$data['id']."'" ) + 0;

					//если не нашли, то ищем в обсуждениях
					if ( $deid == 0 ) {

						$commentid = $db -> getOne( "select id from ".$sqlname."comments WHERE FIND_IN_SET('$fid', REPLACE(".$sqlname."comments.fid, ';',',')) > 0 and identity = '".$data['id']."'" ) + 0;

						if ( $commentid == 0 ) {

							$file_size = filesize( ROOT."/".$path.$file );

							$totalSize += $file_size;
							$totalCount++;

							if ( DODELETE )
								unlink( ROOT."/".$path.$file );

						}

					}

				}

				ext1:

			}

		}


		/**
		 * Файлы почты - /files/ymail
		 */

		//получаем список файлов в папке
		$path = ($GLOBALS['isCloud'] == true) ? "files/".$data['id']."/ymail/" : "files/ymail/";

		//print "FOLDER = ".ROOT."/".$path."\n";
		//flush();

		if ( file_exists( ROOT."/".$path ) ) {

			$fileList = getFiles( ROOT."/".$path );

			foreach ( $fileList as $file ) {

				if ( $file == '' || $file == '.gitignore' )
					goto ext2;

				$yfid = $db -> getOne( "select id from ".$sqlname."ymail_files WHERE file = '$file' and identity = '".$data['id']."'" ) + 0;

				if ( $yfid == 0 ) {

					$file_size = filesize( ROOT."/".$path.$file );

					$totalSize += $file_size;
					$totalCount++;
					if ( DODELETE )
						unlink( ROOT."/".$path.$file );

				}

				ext2:

			}

		}


		if ( $GLOBALS['isCloud'] != true && $data['id'] == 1 )
			goto ext;

	}

	ext:

	/**
	 * Удаленные файлы
	 */
	$result = $db -> query( "SELECT fid, fname, identity FROM ".$sqlname."file ORDER BY fid" );
	while ($data = $db -> fetch( $result )) {

		$path = ($GLOBALS['isCloud'] == true) ? "files/".$data['identity']."/" : "files/";

		//если файла нет, то удаляем запись
		if ( !file_exists( ROOT."/".$path.$data['fname'] ) ) {

			//print $filepath.$path.$data['fname']."<br>";

			if ( DODELETE )
				$db -> query( "DELETE FROM ".$sqlname."file WHERE fid = '$data[fid]'" );

			$notexistFiles[ $data['identity'] ][] = [
				"file" => ROOT."/".$path.$data['fname'],
				"size" => 0
			];

		}

	}

	return "
		Всего \"плохих\" файлов: ".$totalCount.", занимают: ".round( ($totalSize / (1024 * 1024)), 2 )." Mb
		Удаленные файлы (отсутствующие): ".count( $notexistFiles[1] )."
	";

}

/**
 * Параметры для работы скрипта
 * $alert - выводить результат на экран
 *
 */
$alert  = true;
$counts = 0;

/**
 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
 * В противном случае получим ошибку "safemysql MySQL server has gone away"
 */
unset( $db );
$db = new SafeMySQL( $opts );

/**
 * Проверка истории
 * Проходим по 100 записей
 * Записи старше 365 дней
 */
//$db -> query("DELETE FROM {$sqlname}history WHERE DATEDIFF(NOW() - INTERVAL 365 DAY, datum) > 0 AND ( tip IN ('ЛогCRM','СобытиеCRM') OR des = '' ) ORDER BY datum DESC");
$db -> query("DELETE FROM {$sqlname}history WHERE (datum < '".current_datum(365)."') AND ( tip IN ('ЛогCRM','СобытиеCRM') OR des = '' ) ORDER BY datum DESC");
$counts = $db -> affectedRows();

if ( $alert ) {

	print "Удалено $counts записей активностей\n";
	flush();

}

/**
 * Удаление активностей с неизвестным типом
 */
$db -> query( "DELETE FROM {$sqlname}history WHERE tip NOT IN (SELECT title FROM {$sqlname}activities) AND tip NOT IN ('ЛогCRM','СобытиеCRM')" );
$c = $db -> affectedRows();

if ( $alert ) {

	print "Удалено $c записей активностей с неизвестным типом\n";
	flush();

}

/**
 * Чистим логи Webhook
 */
$db -> query( "DELETE FROM {$sqlname}webhooklog WHERE datum < (NOW() - INTERVAL 10 DAY)" );
$c = $db -> affectedRows();

if ( $alert ) {

	print "Удалено $c записей лога webhook\n";
	flush();

}

/**
 * Чистим логи API
 */
$db -> query( "DELETE FROM {$sqlname}logapi WHERE datum < (NOW() - INTERVAL 30 DAY)" );
$c = $db -> affectedRows();

if ( $alert ) {

	print "Удалено $c записей лога API\n";
	flush();

}

/**
 * Очистка истории звонков
 */

// старые записи внутренних звонков
//$db -> query("DELETE FROM {$sqlname}callhistory WHERE DATEDIFF(NOW() - INTERVAL 30 DAY, datum) > 0 AND direct = 'inner'");
$db -> query("DELETE FROM {$sqlname}callhistory WHERE (datum < '".current_datum(30)."') AND direct = 'inner'");

// старые звонки, не привязанные к клиентам/контактам
//$db -> query("DELETE FROM {$sqlname}callhistory WHERE DATEDIFF(NOW() - INTERVAL 30 DAY, datum) > 0 AND clid = 0 AND pid = 0");
$db -> query("DELETE FROM {$sqlname}callhistory WHERE (datum < '".current_datum(30)."') AND clid = 0 AND pid = 0");


/**
 * Удаление "плохих" фацлов почтовика
 */
$result = $db -> query( "SELECT id FROM {$sqlname}settings" );
while ($da = $db -> fetch( $result )) {

	$identity = $da['id'];

	// очищаем почту по сотрудникам
	$users = User ::userCatalog();

	//print_r($users);

	foreach ( $users as $user ) {

		unset( $db );
		$db = new SafeMySQL( OPTS );

		Mailer ::clearOtherMessages( $user['id'], 30 );
		Mailer ::clearOldMessages( $user['id'] );

	}

}

/**
 * Проверка и очистка записей о файлах в БД, которые не существуют на сервере
 */
print badFiles();
flush();

exit();