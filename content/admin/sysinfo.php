<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

if ( empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) != 'xmlhttprequest' ) {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

$current = $db -> getOne( "SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1" );

$os = PHP_OS_FAMILY;

exec("php -v", $php, $exit );

$xphp = getPhpInfo();

?>
<TABLE id="zebra" class="top">
	<thead>
	<TR class="th40">
		<TD colspan="5"><b class="fs-12 blue pl10">Соответствие системным требованиям</b></TD>
	</TR>
	</thead>
	<tbody>
	<TR>
		<TD class="w100 text-center"><i class="icon-cog-alt green icon-3x"></i></TD>
		<TD colspan="4" nowrap="nowrap">
			<h3 class="m0 p0 mt20">Версии ПО и модулей:</h3>
			<blockquote>
				<?php
				$ver   = $xphp['web'];
				$error = 0;

				//$xphp['web'] = "5.6";

				if ( in_array( $xphp['web'], [
					'7.2',
					'7.3',
					'7.4',
					'8.1'
				] ) ) {

					print '<i class="icon-ok-circled green"></i>&nbsp;Требуется версия <b>PHP</b> <b class="green">7.2...8.1</b>. Текущая версия <b class="green">'.$xphp['web'].'</b>.<br>';

					// Deprecated since v.2024.1
					/*
					if( function_exists( "ioncube_loader_version" ) ) {
						$loaderVer = ioncube_loader_version();
					}

					print ( $loaderVer >= 10.2 ? '<i class="icon-ok-circled green"></i>' : '<i class="icon-attention red"></i>' ).'&nbsp;Требуется версия <b>Ioncube Loader</b> <b class="green">> 10.2</b>. Текущая версия <b class="'.( $loaderVer >= 10.2 ? 'green' : 'red' ).'">'.$loaderVer.'</b>.<br>';

					if ( !extension_loaded( "Ioncube Loader" ) ) {
						$error++;
						print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>Ioncube Loader</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';
					}
					else {
						print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>Ioncube Loader</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
					}
					*/

				}
				else {

					$error++;
					print '<i class="icon-attention red"></i>&nbsp;Версия <b>PHP</b> ниже 7.2 <b class="red">не поддерживается</b>, выше 8.1 <b class="broun">не гарантируется</b>. Установлена версия <b>'.$xphp['web'].'</b><br>';

				}

				$mysql_ver = $db -> getOne( "SELECT VERSION() as ver" );

				if ( (float)$mysql_ver < 5.1 ) {
					$error++;
					print '<i class="icon-attention red"></i>&nbsp;Требуется версия <b>MySQL</b> <b>5.0</b> или выше. Текущая версия<b class="red">'.$mysql_ver.'</b>.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Версия <b>MySQL</b> <b class="green">в порядке</b>. Установлена версия <b>'.$mysql_ver.'</b>.<br>';
				}

				if ( !extension_loaded( "dom" ) ) {
					$error++;
					print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>DOM</b></u> <b class="red">не подключен</b>.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>DOM</b></u> <b class="green">подключен</b>.<br>';
				}

				if ( !extension_loaded( "xmlreader" ) ) {
					//$error++;
					print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>XMLREADER</b></u> (модуль PHP) <b class="red">не подключен</b>.<br>';

				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>XMLREADER</b></u> (модуль PHP) <b class="green">подключен</b>.<br>';
				}

				if ( !extension_loaded( "curl" ) ) {
					$error++;
					print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>cUrl</b></u> <b class="red">не подключен</b>.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>cUrl</b></u> <b class="green">подключен</b>.<br>';
				}

				if ( !extension_loaded( "mbstring" ) ) {
					$error++;
					print '<i class="icon-attention red"></i>&nbsp;Модуль <u><b>MBSTRING</b></u> <b class="red">не подключен</b>.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>MBSTRING</b></u> <b class="green">подключен</b>.<br>';
				}

				if ( !extension_loaded( "imap" ) ) {
					//$error++;
					print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>IMAP</b></u> <b class="red">не подключен</b>.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>IMAP</b></u> <b class="green">подключен</b>.<br>';
				}

				if ( extension_loaded( "domxml" ) ) {
					//$error++;
					print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>DOMXML</b></u> <b class="red">подключен</b>. Не критично, но данный модуль мешает работе класса <b>dompdf</b> для генерации PDF файлов.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>DOMXML</b></u> <b class="green">не подключен</b>.<br>';
				}

				if ( !extension_loaded( "openssl" ) ) {
					//$error++;
					print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>OPENSSL</b></u> <b class="red">не подключен</b>. Требуется для отправки почты через SMTP.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>OPENSSL</b></u> <b class="green">подключен</b>.<br>';
				}

				if ( !extension_loaded( "gd" ) ) {
					//$error++;
					print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>GD</b></u> <b class="red">не подключен</b>. Требуется для генератора PDF.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>GD</b></u> <b class="green">подключен</b>.<br>';
				}

				if ( !extension_loaded( "imagick" ) ) {
					//$error++;
					print '<i class="icon-attention broun"></i>&nbsp;Модуль <u><b>IMAGICK</b></u> <b class="red">не подключен</b>. Требуется для генератора QR-кода.<br>';
				}
				else {
					print '<i class="icon-ok-circled green"></i>&nbsp;Модуль <u><b>IMAGICK</b></u> <b class="green">подключен</b>.<br>';
				}
				?>
			</blockquote>

			<div class="blue mb5">Выполнение запроса <b>php -v</b> ( версия <b><?=$xphp['cli']?></b> ):</div>
			<div class="white p10 fs-09 wp90 enable--select" style="background: #222;">
				<code>
					<?php
					print yimplode("<br>", $php);
					?>
				</code>
			</div>

			<?php
			if($os == 'Linux') {
				if ( $xphp['web'] != $xphp['cli'] ) {
					print '<div class="warning m0 mt5 inline">Возможно установлена альтернативная версия PHP.<br>Путь до исполняемого файла определился как:<br><div class="warning bgwhite p5 enable--select">'.$xphp['bin'].'</div></div>';
				}
				else {
					print '<div class="success mt5 inline">Версия PHP соответствует версии, используемой веб-сервером.<br>Для запуска скриптов из командной строки используйте:<br><b>php</b></div>';
				}
			}
			?>

		</TD>
	</TR>
	<TR>
		<TD class="text-center"><i class="icon-folder-1 broun icon-3x"></i></TD>
		<TD colspan="4">
			<h3 class="m0 p0 mt20">Требуемые права для папок (должны быть 0777):</h3>
			<ul>
				<li>Права для папки "<b>/files/</b>": <?= getPerms( $rootpath.'/files' ) ?></li>
				<li>Права для папки "<b>/files/backup/</b>": <?= getPerms( $rootpath.'/files/backup' ); ?></li>
				<li>Права для папки "<b>/cash/logo/</b>": <?= getPerms( $rootpath.'/cash/logo' ); ?></li>
				<li>Права для папки "<b>/cash/</b>": <?= getPerms( $rootpath.'/cash' ); ?></li>
				<li>Права для папки "<b>/cash/templates/</b>": <?= getPerms( $rootpath.'/cash/templates' ); ?></li>
			</ul>

		</TD>
	</TR>
	<TR>
		<TD class="text-center"><i class="icon-file-pdf red icon-3x"></i></TD>
		<TD colspan="4">

			<h3 class="m0 p0 mt20">Генератор PDF:</h3>
			<blockquote>

				<ul>
					<li>
						<?php
						if ( !extension_loaded( "gd" ) ) {
							print 'Модуль <u><b>GD</b></u> <b class="red">не подключен</b>. Требуется для генератора PDF.&nbsp;<i class="icon-attention broun"></i><br>';
						}
						else {
							print 'Модуль <u><b>GD</b></u> <b class="green">подключен</b>&nbsp;<i class="icon-ok-circled green"></i>';
						}
						?>
					</li>
					<li>Права для папки "<b>/cash/dompdf/</b>": <?= getPerms( $rootpath.'/cash/dompdf/' ) ?></li>
					<!--<li>Файл "<b>cash/log.htm</b>": --><?php /*= is_writable( $rootpath.'/cash/log.htm' ) ? '<b class="green">Доступен для записи</b>' : '<b class="red">Защищен от записи</b>'; */?>
					</li>
				</ul>

			</blockquote>
		</TD>
	</TR>
	<TR>
		<TD class="text-center"><i class="icon-file-word deepblue icon-3x"></i></TD>
		<TD colspan="4">

			<h3 class="m0 p0 mt20">Генератор документов MS Office -> PDF:</h3>
			<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">

				<p>Конвертер документов в формате *.doc, *.docx, *.xls, *.xlsx, *.ppt, *.pptx в PDF. Используется стороннее ПО:</p>

				<?php
				if ( $os == 'Linux' ) {

					// ищем установки libreoffice
					exec( 'rpm -qa --qf "%{NAME}\n" | grep libreoffice', $officeComp, $exit );
					?>
					<h4>Для Linux</h4>

					<p>должен быть установлен Libreoffice установка:</p>

					<ul>
						<li><b>Ядро:</b>
							<span class="blue enable--select"># yum install libreoffice-headless</span> <?= ( in_array( 'libreoffice-core', $officeComp ) ? '<i class="icon-ok-circled green"></i>' : '<i class="icon-block red"></i>' ) ?>
						</li>
						<li><b>Документы:</b>
							<span class="blue enable--select"># yum install libreoffice-writer</span> <?= ( in_array( 'libreoffice-writer', $officeComp ) ? '<i class="icon-ok-circled green"></i>' : '<i class="icon-block red"></i>' ) ?>
						</li>
						<li><b>Таблицы:</b>
							<span class="blue enable--select"># yum install libreoffice-calc</span> <?= ( in_array( 'libreoffice-calc', $officeComp ) ? '<i class="icon-ok-circled green"></i>' : '<i class="icon-block red"></i>' ) ?>
						</li>
						<li><b>Презентации:</b>
							<span class="blue enable--select"># yum install libreoffice-impress</span> <?= ( in_array( 'libreoffice-impress', $officeComp ) ? '<i class="icon-ok-circled green"></i>' : '<i class="icon-block red"></i>' ) ?>
						</li>
					</ul>

				<?php } ?>

				<h4 class="mb5">Для Windows</h4>

				<p class="w0 red">Не поддерживается</p>

				<!--<p>в папке с веб-сервером должен быть файл \tools\OfficeToPdf\OfficeToPDF.exe
					[<a href="https://github.com/cognidox/OfficeToPDF" class="Bold blue" target="_blank" title="OfficeToPDF">OfficeToPDF</a> ]
					также должен быть установлен пакет Office
				</p>-->
			</div>

		</TD>
	</TR>
	<TR>
		<TD class="text-center"><i class="icon-lock blue icon-3x"></i></TD>
		<TD colspan="4">
			<h3 class="m0 p0 mt20">Безопасность:</h3>
			<ul>
				<li>Права для папку "<b>inc</b>":
					<?php
					clearstatcache();
					$chmod = getChmod( $rootpath.'/inc' );
					print ( $chmod != "0777" ) ? '<i class="icon-thumbs-up-alt green list" title="Папка не имеет прав на запись"></i>' : '<i class="icon-thumbs-down-alt red list" title="Снимите права на запись для папки inc"></i> ['.$chmod.']';
					?>
				</li>
				<!--<li>Файл установки "<b>install.php</b>":
					<?php
					clearstatcache();
					if ( file_exists( $rootpath.'/_install/install.php' ) ) print '<i class="icon-thumbs-down-alt red list" title="Файл не удален после установки."></i>';
					else print '<i class="icon-thumbs-up-alt green list" title="Файл отсутствует"></i>';
					?>
				</li>
				<li>Файл обновления "<b>_update.php</b>":
					<?php
					clearstatcache();
					if ( file_exists( $rootpath.'/_install/_update.php' ) ) print '<i class="icon-thumbs-down-alt red list" title="Файл не удален после установки."></i>';
					else print '<i class="icon-thumbs-up-alt green list" title="Файл отсутствует"></i>';
					?>
				</li>-->
			</ul>
		</TD>
	</TR>
	</tbody>
</TABLE>

<TABLE id="zebraa">
	<thead>
	<TR class="th40">
		<TD colspan="5"><b class="fs-12 blue pl10">Общая информация</b></TD>
	</TR>
	</thead>
	<tbody>
	<TR class="th40">
		<TD class="w100 text-center" rowspan="10" nowrap="nowrap"><i class="icon-info-circled icon-3x blue"></i></TD>
		<TD class="w300">
			<b>Операционная система</b>:
		</TD>
		<TD colspan="3" nowrap="nowrap">
			<?php
			//print strtr($info['os'], $os);
			//print PHP_OS . "<br>";

			print $os;
			?>
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>Путь установки CRM</b>:
		</TD>
		<TD colspan="3" nowrap="nowrap">
			<?php
			print $rootpath;
			?>
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>Часовой пояс</b>:
		</TD>
		<TD colspan="3" nowrap="nowrap">
			<?php
			if ( ini_get( 'date.timezone' ) == '' ) {

				print '<i class="icon-attention broun"></i>&nbsp;Не задан параметр <u><b>date.timezone</b></u> (см. php.ini) <b class="red">Прописать в php ini "date.timezone = Europe/Moscow"</b>. Критично.<br>';

			}
			else print ini_get( 'date.timezone' );
			?>
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>Размер базы данных</b>:
		</TD>
		<TD colspan="3" nowrap="nowrap">
			<?php
			//вывод размера базы данных
			$datasize = 0;
			$query    = "SHOW TABLE STATUS FROM ".$database."";
			if ( $tables = $db -> getAll( $query ) ) {
				foreach ( $tables as $table ) {
					$datasize += $table[ 'Data_length' ];
				}
				if ( $datasize == 0 ) {
					echo "База данных пуста";
				}
				else {
					$datasize = round( $datasize / 1024 );
					echo "<b>".num_format( $datasize )."</b> kb<br>";
				}
			}
			?>
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>Использование диска</b>:
		</TD>
		<TD colspan="3" nowrap="nowrap">
			<?php
			include $rootpath."/content/ajax/check_disk.php";

			print 'Занято: <b class="red">'.num_format( $diskUsage[ 'current' ] ).'</b> Mb, Лимит: <b>'.num_format( $diskUsage[ 'total' ] ).'</b> Мб ( <b>'.num_format( $diskUsage[ 'percent' ] ).'</b> % )';
			?>
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>Максимальный размер загр.файла</b>:
		</TD>
		<TD colspan="3" nowrap="nowrap">
			<?php
			$max = str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) );
			print $max." Mb";
			?>
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>MAX EXECUTION TIME</b>:&nbsp;
		</TD>
		<TD colspan="3">
			<?= @ini_get( 'max_execution_time' ) ?> сек.
		</TD>
	</TR>
	<TR class="th40">
		<TD>
			<b>MAX MEMORY LIMIT</b>:
		</TD>
		<TD colspan="3">
			<?= @ini_get( 'memory_limit' ) ?>
		</TD>
	</TR>
	<TR class="th40">
		<TD nowrap="nowrap"><B>Версия SalesMan CRM:</B></TD>
		<TD colspan="3" nowrap="nowrap"><b class="blue"><?= $current ?></b></TD>
	</TR>
	<TR class="th40">
		<TD nowrap="nowrap"><B>Разработчик:</B></TD>
		<TD colspan="3" nowrap="nowrap"><a href="https://salesman.pro" target="_blank"><b>SalesMan Team</b></a></TD>
	</TR>
	<TR class="th40">
		<TD nowrap="nowrap"><b>Поддержка:</b></TD>
		<TD colspan="3" nowrap="nowrap">
			<a href="https://salesman.pro" target="_blank" class="red"><b>https://salesman.pro</b></a>
		</TD>
	</TR>
	</tbody>
</TABLE>

<TABLE id="zebra" class="top">
	<thead>
	<TR class="th40">
		<TD colspan="5"><b class="fs-12 blue pl10">Структура папок</b></TD>
	</TR>
	</thead>
	<tbody>
	<TR>
		<TD class="w100 text-center" rowspan="3" nowrap="nowrap">
			<i class="icon-sitemap broun icon-3x"></i>
		</TD>
		<TD colspan="4" nowrap="nowrap">

			<div class="fs-09 wp85 text-wrap" style="max-height: 400px; overflow-y: auto">
				<div class="mono graybg-sub enable--select p10">
					<?php
					$html = file_get_contents($rootpath."/developer/structure.md");
					$Parsedown = new ParsedownExtra();

					print $Parsedown -> text($html);
					?>
				</div>
			</div>

		</TD>
	</TR>
	</tbody>
</TABLE>

<TABLE id="zebra" class="top">
	<thead>
	<TR class="th40">
		<TD colspan="5"><b class="fs-12 blue pl10">ПО сторонних разработчиков</b></TD>
	</TR>
	</thead>
	<tbody>
	<TR>
		<TD class="w100 text-center" rowspan="3" nowrap="nowrap">
			<i class="icon-file-code red icon-3x"></i>
		</TD>
		<TD colspan="4" nowrap="nowrap">

			<div class="fs-09 wp85 text-wrap" style="max-height: 400px; overflow-y: auto">
				<div class="mono graybg-sub enable--select p10">
					<?php
					$html = file_get_contents($rootpath."/NOTICE");
					$Parsedown = new ParsedownExtra();
					print $Parsedown -> text($html);

					//$Parsedown -> text($html);
					?>
				</div>
			</div>

		</TD>
	</TR>
	</tbody>
</TABLE>

<div class="space-100"></div>