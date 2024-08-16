<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/**
 * Скрипт для автоматической загрузки дистрибутива на сервер и расстановки нужных прав на папки и файлы
 */
set_time_limit(0);

error_reporting(E_ERROR);

function flushPrint($string): void {

	echo $string;
	echo PHP_EOL;
	flush();
	ob_flush();

}

$file = "install.zip";
$rootpath = realpath( __DIR__ );
$br = ( PHP_SAPI != 'cli' ) ? "<br>" : "\n";

//скачиваем файл обновления через curl
$src = "https://salesman.pro/download/getfile.php";

flushPrint("Начинаю загрузку дистрибутива$br");

$res = exec("curl -L $src -o $rootpath/$file");

flushPrint("Дистрибутив скачан$br");
flushPrint("Начинаю распаковку$br");

$unzip = shell_exec("unzip $rootpath/$file -d $rootpath");

if(empty($unzip)){

	flushPrint("Проблема при распаковке. Пробуем средствами PHP...$br");

	$zip = new ZipArchive;
	$res = $zip->open($file);

	if ($res === TRUE) {

		$zip->extractTo($rootpath);
		$zip->close();

		flushPrint("Готово. Распаковка завершена.$br");
		flushPrint("Дистрибутив развернут. Можно приступить к установке с помощью браузера!$br");

		//меняем права у папок и файлов на дефолтные
		exec("find $rootpath -type d -exec chmod 755 -R {} \\");
		exec("find $rootpath -type f -exec chmod 644 -R {} \\");

		unlink(__FILE__);

		header("Location: /install/");

	}
	else {

		flushPrint("Требуется самостоятельная распаковка архива $file{$br}");

	}

}
else {

	//меняем права у папок и файлов на дефолтные
	exec("find $rootpath -type d -exec chmod 755 -R {} \\");
	exec("find $rootpath -type f -exec chmod 644 -R {} \\");

	flushPrint("Дистрибутив развернут. Можно приступить к установке с помощью браузера!$br");

	header("Location: /install/");

}