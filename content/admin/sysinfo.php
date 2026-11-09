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
if (!file_exists($rootpath."/inc/requirements.php")) {
	print 'Не найден <b>inc/requirements.php</b> — проверка системных требований недоступна. Обновите дистрибутив.';
	exit();
}

require_once $rootpath."/inc/requirements.php";
include $rootpath."/inc/settings.php";
// Доступ только для администратора
if ($isadmin != 'on' && $tipuser != 'Администратор') {
	print 'Доступ запрещен';
	exit();
}


$thisfile = basename( __FILE__ );

if ( empty( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) && strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) != 'xmlhttprequest' ) {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

$current = $db -> getOne( "SELECT current FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1" );

$os      = PHP_OS_FAMILY;
$req     = sm_requirements_check( $rootpath, $db, 'runtime' );
$cli     = $req[ 'cli' ];

$distro = '';

if ( $os == 'Linux' ) {

	sm_shell_exec("cat /etc/*-release", $osa, $exit);
	$distro = !empty( $osa[0] ) ? yexplode("=", $osa[0], 1) : '';

}

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
			<?php print sm_requirements_verdict( $req ); ?>
			<blockquote>
				<?php print sm_requirements_render( $req, [ 'versions', 'extensions', 'php_ini', 'runtime' ] ); ?>
			</blockquote>

			<?php if ( $cli[ 'available' ] ) { ?>
				<div class="blue mb5">Выполнение запроса <b>php -v</b><?php if ( $cli[ 'version' ] !== '' ) { print ' ( версия <b>'.$cli[ 'version' ].'</b> )'; } ?>:</div>
				<div class="white p10 fs-09 wp90 enable--select" style="background: #222;">
					<code>
						<?php print yimplode( "<br>", $cli[ 'output' ] ); ?>
					</code>
				</div>
			<?php } ?>

		</TD>
	</TR>
	<TR>
		<TD class="text-center"><i class="icon-folder-1 broun icon-3x"></i></TD>
		<TD colspan="4">
			<h3 class="m0 p0 mt20">Требуемые права для папок (запись для веб-сервера, 0777):</h3>
			<blockquote>
				<?php print sm_requirements_render( $req, [ 'folders' ] ); ?>
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
					// centos
					if($distro != 'Ubuntu') {
						sm_shell_exec('rpm -qa --qf "%{NAME}\n" | grep libreoffice', $officeComp, $exit);
					}
					// ubuntu
					else {
						sm_shell_exec('dpkg -l | grep libreoffice | awk \'{print $2}\'', $officeComp, $exit);
					}

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

				<!--
				<p class="w0">Должен быть установлен <a href="https://www.libreoffice.org/download/download-libreoffice/" title="Скачать" target="_blank">LibreOffice</a></p>

				<p>Для корректной работы необходимо добавить исполняемый файл в PATH:</p>
				<ol>
					<li>Параметры / Дополнительные параметры системы / Переменные среды</li>
					<li>Выбрать параметр Path / Изменить</li>
					<li>В открывшемся списке нажимаем "Создать" -> "Обзор"</li>
					<li>Выбираем путь установки. Например "C:\Program Files\LibreOffice\program\"</li>
					<li>Нажимаем Ок + Ок + Ок во всех открытых окнах</li>
				</ol>
				-->

				<p>в папке с веб-сервером должен быть файл \SalesmanServer\tools\OfficeToPdf\OfficeToPDF.exe
					[<a href="https://github.com/cognidox/OfficeToPDF" class="Bold blue" target="_blank" title="OfficeToPDF">OfficeToPDF</a> ]
					также должен быть установлен пакет Office
				</p>
			</div>

		</TD>
	</TR>
	<TR>
		<TD class="text-center"><i class="icon-lock blue icon-3x"></i></TD>
		<TD colspan="4">
			<h3 class="m0 p0 mt20">Безопасность:</h3>
			<blockquote>
				<?php print sm_requirements_render( $req, [ 'security' ] ); ?>
			</blockquote>
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
			print $os.' '.(!empty($distro) ? "( $distro )" : "");
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
			print( ini_get( 'date.timezone' ) !== '' ? ini_get( 'date.timezone' ) : 'не задан — см. проверку выше' );
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