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

set_time_limit(6000);
ini_set("memory_limit", "512M");

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];
$file   = $_REQUEST['file'];


$path = $rootpath."/files/backup/";

createDir($path);

if ($action === "restore" && $file != "") {

	//распакуем архив, если это архив
	if (strpos($file, '.zip') !== false) {

		$arc = new ZipArchive;
		$arc -> open($path.$file);
		$arc -> extractTo($path);
		$arc -> close();

		$file = str_replace(".zip", "", $file);

	}

	$filee = fread(fopen($path.$file, "r"), filesize($path.$file));
	$query = explode(";#%%\n", $filee);

	for ($i = 0; $i < count($query) - 1; $i++) {

		try {

			$db -> query($query[ $i ]);

		}
		catch (Exception $e) {

			print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	fclose($path.$file);

	$file = unlink($path.$file);

	print $rez = 'Восстановление базы данных прошло успешно';

	exit();

}
if ($action === "delete" && $file != "") {

	flush();

	$file = unlink($path.$file);

	print $rez = 'Файл успешно удален';

	exit();
}

if ($action === "bfile") {

	$current = $db -> getOne("SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1");

	$file = $database."_".$current."_backup_".date("Y-m-d_H-i").".sql";

	if (PHP_OS_FAMILY != "Linux") {

		//поищем утилиту mysqldump.exe
		$dumper   = '';
		$litera   = str_split($_SERVER['DOCUMENT_ROOT'])[0];
		$basepath = $litera.":\\OpenServer";
		$path     = $basepath."\\domains\\localhost\\files\\backup\\";

		if (file_exists($litera.":\\OpenServer\\tools\\mysqldump.exe")) {
			$dumper = $litera.":\\OpenServer\\tools";
		}
		elseif (file_exists($litera.":\\SalesmanServer\\tools\\mysqldump.exe")) {
			$dumper = $litera.":\\SalesmanServer\\tools";
		}
		elseif (file_exists($litera.":\\tools\\mysqldump.exe")) {
			$dumper = $litera.":\\tools";
		}

		//$dumper = '';

		if ($dumper == '') {

			$path = $rootpath."/files/backup/";

			if ( !is_dir( $path ) && !mkdir( $path, 0766 ) && !is_dir( $path ) ) {
				throw new RuntimeException( sprintf( 'Directory "%s" was not created', $path ) );
			}
			chmod($path, 0777);

			if (!file_exists($path.$file)) {

				$fp2 = fopen($path.$file, 'wb' );
				fwrite($fp2, "");
				fclose($fp2);

				chmod($path.$file, 0777);

			}

			function get_structure($table): string {

				global $rootpath;

				require_once $rootpath."/inc/config.php";
				require_once $rootpath."/inc/dbconnector.php";

				$db  = $GLOBALS['db'];
				$database  = $GLOBALS['database'];

				$def = "DROP TABLE IF EXISTS `$table`;#%%\n";
				$def .= "CREATE TABLE `$table` (\n";

				$result = $db -> getRow("SHOW TABLE STATUS WHERE Name = '".$table."'");
				$engine = $result["Engine"];

				$defa = [];

				$result = $db -> query("SHOW FIELDS FROM $table");
				while ($row = $db -> fetch($result)) {

					$defa[] = "    `$row[Field]` ".strtoupper($row['Type']);

					if ($row["Null"] == "YES") {
						$defa[] = " NULL ";
					}

					elseif ($row["Null"] != "YES") {
						$defa[] = " NOT NULL";
					}

					if (strtoupper($row["Default"]) == "CURRENT_TIMESTAMP") {
						$defa[] = " DEFAULT CURRENT_TIMESTAMP";
					}

					elseif (strtoupper($row["Default"]) == "CURRENT_TIMESTAMP()") {
						$defa[] = " DEFAULT CURRENT_TIMESTAMP";
					}

					elseif ($row["Default"] != "") {
						$defa[] = " DEFAULT '$row[Default]'";
					}

					elseif ($row["Extra"] != "") {
						$defa[] = " $row[Extra]";
					}

					elseif (stripos($row["Field"], 'input') !== false || ( stripos($row["Type"], 'varchar') !== false && $row["Null"] != "NO")) {
						$defa[] = " DEFAULT  NULL";
					}

					$comm = $db -> getOne("SELECT a.COLUMN_COMMENT FROM information_schema.COLUMNS a WHERE a.TABLE_SCHEMA = '$database' AND a.TABLE_NAME = '$table' and a.COLUMN_NAME = '$row[Field]'");

					if ($comm != '') {
						$defa[] = " COMMENT '$comm'";
					}

					$defa[] = ",\n";

				}

				//убираем последниэ элемент массива, т.к. он равен ",\n"
				$x = array_pop($defa);

				$def .= implode("", $defa);

				$index = [];

				$result = $db -> query("SHOW KEYS FROM $table");
				while ($row = $db -> fetch($result)) {

					$kname = $row["Key_name"];
					if ($row['Index_type'] == "FULLTEXT") {
						$kname = "FULLTEXT $kname";
					}
					if (($kname != "PRIMARY") && ($row["Non_unique"] == 0)) {
						$kname = "UNIQUE $kname";
					}
					if (!isset($index[ $kname ])) {
						$index[ $kname ] = [];
					}

					$sub = (($row['Sub_part'] + 0) > 0) ? '('.$row['Sub_part'].')' : '';

					$index[ $kname ][] = "`".$row["Column_name"]."`".$sub;

				}

				foreach ($index as $x => $columns) {

					$def .= ",\n";
					if ($x == "PRIMARY") {
						$def .= "   PRIMARY KEY (".implode( ", ", $columns ).")";
					}
					elseif ( strpos( $x, "FULLTEXT" ) === 0 ) {
						$def .= "   FULLTEXT INDEX `".substr( $x, 9 )."` (".implode( ", ", $columns ).")";
					}
					elseif ( strpos( $x, "UNIQUE" ) === 0 ) {
						$def .= "   UNIQUE INDEX `".substr( $x, 7 )."` (".implode( ", ", $columns ).")";
					}
					else {
						$def .= "   INDEX `$x` (".implode( ", ", $columns ).")";
					}

				}

				$comm = $db -> getOne("SELECT table_comment FROM INFORMATION_SCHEMA.TABLES WHERE table_name='$table'");

				$cmnt = ($comm != '') ? " COMMENT='$comm'" : '';

				$def .= "\n) $cmnt  ENGINE=$engine DEFAULT CHARSET='utf8';#%%";

				$def = str_replace("KEY FULLTEXT", "FULLTEXT INDEX", $def);

				return (stripslashes($def));

			}

			function get_content($table): string {

				global $rootpath;

				require_once $rootpath."/inc/config.php";
				require_once $rootpath."/inc/dbconnector.php";

				$db      = $GLOBALS['db'];
				$content = "";

				//$fieds = $db -> getAll("SHOW FIELDS FROM $table");

				$result = $db -> query("SELECT * FROM $table");
				while ($row = $db -> fetch($result)) {

					$insert = "INSERT INTO `$table` VALUES (";

					$defa = [];

					foreach($row as $j => $v) {

						if (!is_numeric($j)) {

							if ( is_null( $v ) ) {
								$defa[] = "NULL,";
							}
							elseif ( $v != "" ) {
								$defa[] = "'".addslashes( $v )."',";
							}
							else {
								$defa[] = "'',";
							}

						}

					}

					//что-то не срабатывает
					//array_pop($defa);

					$insert .= substr(implode("", $defa), 0, -1);
					$insert .= ");#%%\n";

					$content .= $insert;

				}

				return $content;
			}

			$filetype = "sql";

			$cur_time = date("Y-m-d H:i");
			$i        = 0;
			$newfile  = '';

			$tables = $db -> getCol('SHOW TABLES');
			$count  = count($tables);

			$fp = fopen($path.$file, 'wb' );

			foreach ($tables as $key => $table) {

				if (strstr($table, $sqlname) > '') {

					$newfile = '';

					//получаем структуру
					$newfile .= get_structure($table);
					$newfile .= "\n\n";

					fwrite($fp, $newfile);

					unset($db);
					$db = new SafeMySQL($opts);

					$newfile = '';

					//получаем данные
					$newfile .= get_content($table);
					$newfile .= "\n\n";

					fwrite($fp, $newfile);

				}

				$i++;

			}

			fclose($fp);

			$zipfile = $file.".zip";
			$zip = new ZipFolder();
			$zip -> zipFile($zipfile, $path, $path.$file);

			$file = unlink($path.$file);

		}
		else {

			$current = $db -> getOne("SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1");

			$file = $database."_".$current."_backup_".date("Y-m-d_H-i").".sql";
			$path = $rootpath."\\files\\backup\\";

			exec($dumper.'\\mysqldump.exe --user='.$dbusername.' --password='.$dbpassword.' --host='.$dbhostname.' --add-drop-table --disable-keys --comments '.$database.' > '.$path.$file, $output1, $exit1);
			exec($dumper.'\\7zip\\7za.exe a -tzip '.$path.$file.'.zip '.$path.$file, $output2, $exit2);

			$exit1 = ($exit1 == 0) ? "Ok" : $exit1;
			$exit2 = ($exit2 == 0) ? "Ok" : $exit2;

			print "Mysqldump:".$exit1."<br>";
			print "Zip:      ".$exit2."<br>";
			//print "File:     " . $file;

			unlink($path.$file);

		}

	}
	else {

		// очистим старые
		$cmd0 = 'find '.$path.' -maxdepth 1 -type f -name "*.zip" -mtime +5 -exec rm -f {} \;';
		exec($cmd0, $list, $exit2 );

		exec('mysqldump --user=\''.$dbusername.'\' --password=\''.$dbpassword.'\' --host='.$dbhostname.' --add-drop-table --disable-keys --comments --routines --triggers '.$database.' > '.$path.$file, $output1, $exit1);
		exec("zip -9 -m -j ".$path.$file.".zip ".$path.$file, $output2, $exit2);

		$exit1 = ($exit1 == 0) ? "Ok" : "Ошибка - ".$exit1;
		$exit2 = ($exit2 == 0) ? "Ok" : "Ошибка - ".$exit2;

		print "Результ Mysqldump:".$exit1."<br>";
		print "Результат Zip:      ".$exit2."<br>";

	}

	print $rez = 'Задание завершено';

	exit();

}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}
?>
<h2>Раздел: "Резервное копирование"</h2>
<hr>
<DIV class="infodiv enable--select">

	<div>При помощи этой опции вы можете выполнять резервное копирование БД.</div>
	<br>
	<A href="javascript:void(0)" onClick="bfile('bfile')" class="button">Создать дамп БД</A>
	<hr>
	<?php
	if (PHP_OS != "Linux") {

		$litera   = str_split($_SERVER['DOCUMENT_ROOT'])[0];
		//$basepath = $litera.":\\OpenServer";
		//$bpath = $basepath."\\home\\localhost\\www\\admin\\backup\\";

		$txt = $txt2 = '';

		if (file_exists($litera.":\\OpenServer\\tools\\mysqldump.exe")) {
			$txt = $litera.":\\OpenServer\\tools\\mysqldump.exe";
		}

		elseif (file_exists($litera.":\\SalesmanServer\\tools\\mysqldump.exe")) {
			$txt = $litera.":\\SalesmanServer\\tools\\mysqldump.exe";
		}

		elseif (file_exists($litera.":\\tools\\mysqldump.exe")) {
			$txt = $litera.":\\tools\\mysqldump.exe";
		}


		if (file_exists($litera.":\\OpenServer\\tools\\7zip\\7za.exe")) {
			$txt2 = $litera.":\\OpenServer\\tools\\7zip\\7za.exe";
		}

		elseif (file_exists($litera.":\\SalesmanServer\\tools\\7zip\\7za.exe")) {
			$txt2 = $litera.":\\SalesmanServer\\7zip\\7za.exe";
		}

		elseif (file_exists($litera.":\\tools\\7zip\\7za.exe")) {
			$txt2 = $litera.":\\tools\\7zip\\7za.exe";
		}

		print '
		<div class="mb10">
			Восстановление данных рекомендуется производить через: 
			<ul>
				<li>
					Консольную команду (пример):
					<pre>mysql -p -u '.$database.' --default-character-set=utf8 < E:\\'.$database.'.sql</pre>
				</li>
				<li>Утилиту <a href="/developer/adminer/" title="">Adminer</a></li>
			</ul>
		</div>
		<div>
			<div>Расположите в корне диска "'.$litera.':" папку "tools" (<a href="'.$productInfo['site'].'/download/repo/tools.zip" target="_blank" class="red Bold">скачать</a>) содержащую утилиты, необходимые для создания резервной копии и её упаковки в архив:</div>
			<ul>
				<li>Утилиту "<b>\tools\mysqldump.exe</b>" - '.($txt != '' ? '<b class="green">Найдено</b> ('.$txt.')' : '<b class="red">Не найдено</b>').'</li>
				<li>Утилиту "<b>\tools\7zip\7za.exe</b>" - '.($txt2 != '' ? '<b class="green">Найдено</b> ('.$txt2.')' : '<b class="red">Не найдено</b>').'</li>
			</ul>
		</div>
		';

	}
	else {

		print '<div>Восстановление данных рекомендуется производить из консоли, либо через утилиту phpMyAdmin</div>';
		print '
			<div>Убедитесь, что установлены утилиты:
			<ul>
				<li>mysqldump</li>
				<li>zip</li>
			</ul>
			</div>';

	}
	?>

</DIV>
<hr>

<div class="zagolovok_rep">Резервные копии</div>
<hr>

<TABLE id="zebra">
	<thead class="hidden-iphone sticked--top">
	<TR height="40">
		<th align="center">Имя файла</th>
		<th align="center">Размер</th>
		<th align="center">Дата создания</th>
		<th align="center" colspan="3">Действие</th>
	</TR>
	</thead>
	<?php
	$dir   = $rootpath."/files/backup/";
	$files = [];

	clearstatcache();

	$files = scandir($path, 1);

	$list = [];
	foreach ( $files as $file ) {

		if (strpos($file, 'sql.zip') !== false) {

			$list[] = [
				"name" => $file,
				"time" => filemtime($path.'/'.$file),
				"date" => date("d-m-Y", filemtime($path.'/'.$file)),
				"size" => num_format(round(filesize($path.'/'.$file) / 1024, 2))
			];

		}

	}

	function ccmp($a, $b): bool {
		return $b['time'] > $a['time'];
	}

	usort($list, 'ccmp');

	foreach ($list as $key => $file) {
		?>
		<TR class="ha th40">
			<TD nowrap class="text-left"><?= $file['name'] ?></TD>
			<TD nowrap class="text-center"><?= $file['size'] ?>&nbsp;Kb</TD>
			<TD nowrap class="text-center"><?= $file['date'] ?></TD>
			<TD class="text-center" nowrap>
				<!--<A href="javascript:void(0)" onClick="bfile('restore','<?= $file['name'] ?>')" title="Восстановить"><i class="icon icon-ccw icon-1x green"></i></A>-->
			</TD>
			<TD class="text-center" nowrap>
				<A href="?action=download&file=<?= $file['name'] ?>" target="_blank" title="Скачать"><i class="icon icon-download icon-1x blue"></i></A>
			</TD>
			<TD class="text-center" nowrap>
				<A href="javascript:void(0)" onClick="cf=confirm('Вы действительно хотите удалить эту копию?');if (cf)bfile('delete','<?= $file['name'] ?>')" title="Удалить"><i class="icon icon-cancel icon-1x red"></i></A>
			</TD>
		</TR>
		<?php
	}
	//closedir($path);
	?>
</TABLE>

<div style="height:90px"></div>

<script>
	function bfile(action, file) {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю. Пожалуйста подождите...</div>');

		$.ajax({
			url: '/content/admin/backup.php?action=' + action + '&file=' + file,
			success: function(data){

				$('#contentdiv').load('/content/admin/backup.php');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			},
			timeout: 6000000,
			dataType: "html",
			statusCode: {
				404: function () {
					new DClose();
					Swal.fire({
						title: "Ошибка 404: Страница не найдена!",
						type: "warning"
					});
				},
				500: function () {
					new DClose();
					Swal.fire({
						title: "Ошибка 500: Ошибка сервера!",
						type: "error"
					});
				},
				503: function () {
					new DClose();
					Swal.fire({
						title: "Ошибка 503: Превышено время ожидания",
						type: "error"
					});
				}
			}
		})
			.fail(function() {

				Swal.fire({
					title: "Ошибка выполнения!",
					type: "error"
				});

			})

		/*$.get('content/admin/backup.php?action=' + action + '&file=' + file, function (data) {


			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		})
			.done(function(){
				$('#contentdiv').load('content/admin/backup.php');
			})*/

	}
</script>