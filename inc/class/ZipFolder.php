<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use Comodojo\Exception\ZipException;
use Comodojo\Zip\Zip;

/**
 * Класс для упаковки файлов в архив
 * Class ZipFolder
 * https://docs.comodojo.org/projects/zip/en/latest/zip_usage.html
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
	 * @param string|null $folder   - путь до папки с файлами
	 * @param bool        $download - нужно ли скачивать
	 * @return string
	 */
	public function zipFile(string $name = 'archive.zip', string $path = NULL, string $folder = NULL, bool $download = false): string {

		try {

			$zip = Zip ::create( $path.$name );

			try {

				//$zip -> add( $folder, true, Zip::CM_DEFLATE );
				$zip -> add( $folder, true );

				//Не срабатывает. Причина не понятна
				if($download){

					$mime = get_mimetype($name);

					header('Content-Type: '.$mime);
					header('Content-Disposition: attachment; filename="'.$name.'"');
					header('Content-Transfer-Encoding: binary');
					header('Accept-Ranges: bytes');

					readfile($path.$name);

					//удалим не нужные файлы
					if ( $dh = opendir( $folder ) ) {

						while ( ( $file = readdir( $dh ) ) !== false ) {

							if ( $file != "." && $file != ".." ) {

								unlink( $path."/".$file );

							}

						}

					}

					rmdir( $folder );

					$this -> file = $path.$name;

				}
				else{

					$this -> file = $path.$name;

				}

			}
			catch ( ZipException $e ) {

				$this -> error = $e -> getMessage();

			}

		}
		catch ( ZipException $e ) {

			$this -> error = $e -> getMessage();

		}

		/*
		$zip = new zip_file( $path.$name );

		$zip -> set_options( [
			'basedir'    => $path,
			'inmemory'   => 0,
			'level'      => 9,
			'storepaths' => 0
		] );
		$zip -> add_files( $folder );
		$zip -> create_archive();
		*/

		return $path.$name;

	}

}