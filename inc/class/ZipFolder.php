<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

/**
 * Класс для упаковки файлов в архив
 * Class ZipFolder
 *
 * Нативная реализация на ext-zip (ZipArchive): comodojo/zip — abandoned
 * (последняя версия 3.1.1, 2019) и тянет EOL-цепочку symfony/console,
 * monolog, league/event, psr/cache. Публичный API сохранён.
 *
 * @package Salesman
 */
class ZipFolder {

	public $error, $file;

	public function __construct() {

	}

	/**
	 * @param string      $name     - имя архива
	 * @param string|null $path     - путь до папки в которой будет создан архив
	 * @param string|null $folder   - путь до файла или папки с файлами
	 * @param bool        $download - нужно ли скачивать
	 * @return string
	 */
	public function zipFile(string $name = 'archive.zip', ?string $path = NULL, ?string $folder = NULL, bool $download = false): string {

		$path = (string)$path;
		$file = $path.$name;

		$this -> error = null;

		try {

			if (!class_exists('ZipArchive')) {
				throw new \RuntimeException('Расширение zip не установлено');
			}

			$zip = new \ZipArchive();

			$open = $zip -> open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

			if ($open !== true) {
				throw new \RuntimeException('Не удалось создать архив, код: '.$open);
			}

			try {

				$this -> addToArchive($zip, (string)$folder);

			}
			finally {

				$zip -> close();

			}

			$this -> file = $file;

			//Не срабатывает. Причина не понятна
			if ($download) {

				$mime = get_mimetype($name);

				header('Content-Type: '.$mime);
				header('Content-Disposition: attachment; filename="'.$name.'"');
				header('Content-Transfer-Encoding: binary');
				header('Accept-Ranges: bytes');

				readfile($file);

				//удалим не нужные файлы
				if ($dh = opendir($folder)) {

					while (($f = readdir($dh)) !== false) {

						if ($f != "." && $f != "..") {

							unlink($path."/".$f);

						}

					}

				}

				rmdir($folder);

			}

		}
		catch (\Throwable $e) {

			$this -> error = $e -> getMessage();

		}

		return $file;

	}

	/**
	 * Добавляет в архив файл или каталог (рекурсивно, с относительными путями).
	 * Каталоги добавляются записями вида "sub/" — как раньше делал comodojo.
	 *
	 * @param \ZipArchive $zip
	 * @param string      $source
	 * @return void
	 */
	private function addToArchive(\ZipArchive $zip, string $source): void {

		if ($source === '' || !file_exists($source)) {
			throw new \RuntimeException('Источник не найден: '.$source);
		}

		// одиночный файл: запись — только имя файла
		if (is_file($source)) {

			if (!$zip -> addFile($source, basename($source))) {
				throw new \RuntimeException('Не удалось добавить файл в архив: '.$source);
			}

			return;

		}

		$base = rtrim(str_replace('\\', '/', $source), '/');
		$len  = strlen($base) + 1;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item) {

			$itemPath = str_replace('\\', '/', $item -> getPathname());
			$relative = substr($itemPath, $len);

			if ($item -> isDir()) {

				$zip -> addEmptyDir($relative);

				continue;

			}

			if (!$zip -> addFile($itemPath, $relative)) {
				throw new \RuntimeException('Не удалось добавить файл в архив: '.$itemPath);
			}

		}

	}

}
