<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\ZipFolder;

set_time_limit( 0 );
ini_set( "memory_limit", "512M" );

error_reporting( 0 );
//ini_set('display_errors', 1);

header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];
$path   = $_REQUEST['path'];
$file   = $_REQUEST['file'];

$path = $rootpath."/files/backup/";

createDir($path);

?>
	<DIV class="zagolovok">Создание резервной копии</DIV>
<?php
if ( $action == "bfile" ) {

	$current = $db -> getOne( "SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1" );

	$file = $database."_".$current."_backup_".date( "Y-m-d_H-i" ).".sql";

	if ( PHP_OS_FAMILY != "Linux" ) {

		//поищем утилиту mysqldump.exe
		$dumper   = '';
		$litera   = str_split($_SERVER['DOCUMENT_ROOT'])[0];
		$basepath = $litera.":\\OpenServer";
		$path     = $basepath."\\domains\\localhost\\files\\backup\\";

		if ( file_exists( $litera.":\\OpenServer\\tools\\mysqldump.exe" ) ) {
			$dumper = $litera.":\\OpenServer\\tools";
		}
		elseif ( file_exists( $litera.":\\SalesmanServer\\tools\\mysqldump.exe" ) ) {
			$dumper = $litera.":\\SalesmanServer\\tools";
		}
		elseif ( file_exists( $litera.":\\tools\\mysqldump.exe" ) ) {
			$dumper = $litera.":\\tools";
		}

		//$dumper   = '';

		if ( $dumper == '' ) {

			$path = $rootpath."/files/backup/";

			if ( !is_dir( $path ) && !mkdir( $path, 0766 ) && !is_dir( $path ) ) {
				throw new RuntimeException( sprintf( 'Directory "%s" was not created', $path ) );
			}
			chmod( $path, 0777 );

			if ( !file_exists( $path.$file ) ) {
				$fp2 = fopen( $path.$file, 'wb' );
				fwrite( $fp2, "" );
				fclose( $fp2 );

				chmod( $path.$file, 0777 );
			}

			function get_structure($table): string {

				require_once "../../inc/config.php";
				require_once "../../inc/dbconnector.php";

				$db  = $GLOBALS['db'];
				$def = "";

				$def .= "DROP TABLE IF EXISTS `$table`;#%%\n";
				$def .= "CREATE TABLE `$table` (\n";

				$result = $db -> getRow( "SHOW TABLE STATUS WHERE Name = '".$table."'" );
				$engine = $result["Engine"];

				$defa = [];

				$result = $db -> query( "SHOW FIELDS FROM $table" );
				while ($row = $db -> fetch( $result )) {

					$defa[] = "    `$row[Field]` ".strtoupper( $row['Type'] );

					if ( $row["Null"] == "YES" )
						$defa[] = " NULL ";

					elseif ( $row["Null"] != "YES" )
						$defa[] = " NOT NULL";

					if ( strtoupper( $row["Default"] ) == "CURRENT_TIMESTAMP" )
						$defa[] = " DEFAULT CURRENT_TIMESTAMP";

					elseif ( strtoupper( $row["Default"] ) == "CURRENT_TIMESTAMP()" )
						$defa[] = " DEFAULT CURRENT_TIMESTAMP";

					elseif ( $row["Default"] != "" )
						$defa[] = " DEFAULT '$row[Default]'";

					elseif ( $row["Extra"] != "" )
						$defa[] = " $row[Extra]";

					$comm = $db -> getOne( "SELECT a.COLUMN_COMMENT FROM information_schema.COLUMNS a WHERE a.TABLE_NAME = '$table' and a.COLUMN_NAME = '$row[Field]'" );

					if ( $comm != '' )
						$defa[] = " COMMENT '$comm'";

					$defa[] = ",\n";

				}

				//убираем последниэ элемент массива, т.к. он равен ",\n"
				array_pop( $defa );

				$def .= implode( "", $defa );

				$index = [];

				$result = $db -> query( "SHOW KEYS FROM $table" );
				while ($row = $db -> fetch( $result )) {

					$kname = $row["Key_name"];
					if ( $row['Index_type'] == "FULLTEXT" )
						$kname = "FULLTEXT $kname";
					if ( ($kname != "PRIMARY") && ($row["Non_unique"] == 0) )
						$kname = "UNIQUE $kname";
					if ( !isset( $index[ $kname ] ) )
						$index[ $kname ] = [];

					$sub = (($row['Sub_part'] + 0) > 0) ? '('.$row['Sub_part'].')' : '';

					$index[ $kname ][] = "`".$row["Column_name"]."`".$sub;

				}

				foreach ( $index as $x => $columns ) {

					$def .= ",\n";
					if ( $x == "PRIMARY" )
						$def .= "   PRIMARY KEY (".implode( $columns, ", " ).")";
					elseif ( substr( $x, 0, 8 ) == "FULLTEXT" )
						$def .= "   FULLTEXT INDEX `".substr( $x, 9 )."` (".implode( $columns, ", " ).")";
					elseif ( substr( $x, 0, 6 ) == "UNIQUE" )
						$def .= "   UNIQUE INDEX `".substr( $x, 7 )."` (".implode( $columns, ", " ).")";
					else $def .= "   INDEX `$x` (".implode( $columns, ", " ).")";

				}

				$comm = $db -> getOne( "SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_name='$table'" );

				$cmnt = ($comm != '') ? " COMMENT='$comm'" : '';

				$def .= "\n) $cmnt  ENGINE=$engine DEFAULT CHARSET='utf8';#%%";

				$def = str_replace( "KEY FULLTEXT", "FULLTEXT INDEX", $def );

				return (stripslashes( $def ));

			}

			function get_content($table): string {

				require_once "../../inc/config.php";
				require_once "../../inc/dbconnector.php";

				$db      = $GLOBALS['db'];
				$content = "";

				//$fieds = $db -> getAll("SHOW FIELDS FROM $table");

				$result = $db -> query( "SELECT * FROM $table" );
				while ($row = $db -> fetch( $result )) {

					$insert = "INSERT INTO `$table` VALUES (";

					$defa = [];

					foreach ( $row as $j => $v ) {

						if (!is_numeric($j)) {

							if ( is_null( $v ) )
								$defa[] = "NULL,";
							elseif ( $v != "" )
								$defa[] = "'".addslashes( $row[ $j ] )."',";
							else                $defa[] = "'',";

						}

					}

					//что-то не срабатывает
					//array_pop($defa);

					$insert .= substr( implode( "", $defa ), 0, -1 );
					$insert .= ");#%%\n";

					$content .= $insert;

				}

				return $content;

			}

			$filetype = "sql";

			$cur_time = date( "Y-m-d H:i" );
			$i        = 0;
			$newfile  = '';

			$tables = $db -> getCol( 'SHOW TABLES' );
			$count  = count( $tables );

			$fp = fopen( $path.$file, 'wb' );

			foreach ( $tables as $key => $table ) {

				if ( strstr( $table, $sqlname ) > '' ) {

					$newfile = '';

					//получаем структуру
					$newfile .= get_structure( $table );
					$newfile .= "\n\n";

					fwrite( $fp, $newfile );

					$newfile = '';

					unset($db);
					$db = new SafeMySQL($opts);

					//получаем данные
					$newfile .= get_content( $table );
					$newfile .= "\n\n";

					fwrite( $fp, $newfile );

				}

				$i++;

			}

			fclose( $fp );

			$zipfile = $file.".zip";
			/*
			$arc     = new zip_file( $path.$zipfile );
			$arc -> set_options( [
				'overwrite'  => 1,
				'level'      => 9,
				'storepaths' => 0
			] );
			$arc -> add_files( $rootpath."/files/backup/".$file );
			$arc -> create_archive();
			*/
			$zip = new ZipFolder();
			$zip -> zipFile($zipfile, $path, $path.$file);

			$file = unlink( $path.$file );

		}
		else {

			$current = $db -> getOne( "SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1" );

			$file = $database."_".$current."_backup_".date( "Y-m-d_H-i" ).".sql";
			$path = $rootpath."\\files\\backup\\";

			exec( $dumper.'\\mysqldump.exe --user='.$dbusername.' --password='.$dbpassword.' --host='.$dbhostname.' --add-drop-table --disable-keys --comments '.$database.' > '.$path.$file, $output1, $exit1 );
			exec( $dumper.'\\7zip\\7za.exe a -tzip '.$path.$file.'.zip '.$path.$file, $output2, $exit2 );

			$exit1 = ($exit1 == 0) ? "Ok" : $exit1;
			$exit2 = ($exit2 == 0) ? "Ok" : $exit2;

			print "Mysqldump:".$exit1."<br>";
			print "Zip:      ".$exit2."<br>";
			//print "File:     " . $file;

			unlink( $path.$file );

		}

	}
	else {

		$rez  = '';

		// очистим старые
		$cmd0 = 'find '.$path.' -maxdepth 1 -type f -name "*.zip" -mtime +5 -exec rm -f {} \;';
		exec($cmd0, $list, $exit2 );

		exec( 'mysqldump --user=\''.$dbusername.'\' --password=\''.$dbpassword.'\' --host='.$dbhostname.' --add-drop-table --disable-keys --comments --routines --triggers '.$database.' > '.$path.$file, $output1, $exit1 );
		exec( "zip -9 -m -j ".$path.$file.".zip ".$path.$file, $output2, $exit2 );

		$exit1 = ($exit1 == 0) ? "Ok" : $exit1;
		$exit2 = ($exit2 == 0) ? "Ok" : $exit2;

		$rez .= "Результ Mysqldump:".$exit1."<br>";
		$rez .= "Результат Zip:      ".$exit2."<br><br>";

	}
	?>
	<br>
	<div class="red bigtext text-center"><B>Резервная копия создана</B></div>
	<hr>
	<div class="smalltxt text-center"><?= $rez ?></div>
	<div class="smalltxt text-center">
		<B>Доступ к резервным копиям:</B> Управление &rarr; Администрирование &rarr; Обслуживание &rarr; Резервные копии
	</div>
	<br>
	<hr/>
	<div class="text-center">
		<A href="javascript:void(0)" onClick="DClose()" class="button">Закрыть</A>
	</div>
	<?php
	exit();
}
?>