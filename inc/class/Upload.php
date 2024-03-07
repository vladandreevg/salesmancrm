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

/**
 * Класс для загрузки файлов
 * Class Upload
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Upload {

	/**
	 * Ошибки
	 *
	 * @var array
	 */
	public const ERRORS = [
		0 => "OK",
		1 => "Размер принятого файла превысил максимально допустимый размер, который задан директивой upload_max_filesize конфигурационного файла php.ini",
		2 => "Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме",
		3 => "Загружаемый файл был получен только частично",
		4 => "Файл не был загружен",
		6 => "Отсутствует временная папка",
		7 => "Не удалось записать файл на диск"
	];

	/**
	 * Информация о файле
	 *
	 * @param $id
	 * @return array
	 */
	public static function info($id): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		$fpath    = $GLOBALS['fpath'];

		$result             = $db -> getRow("SELECT * FROM ".$sqlname."file WHERE fid = '$id' and identity = '$identity'");
		$file['title']      = $result["ftitle"];
		$file['file']       = $result["fname"];
		$file['idcategory'] = $result["folder"];
		$file['text']       = $result["ftag"];
		$file['clid']       = $result["clid"];
		$file['pid']        = $result["pid"];
		$file['did']        = $result["did"];
		$file['iduser']     = $result["iduser"];
		$file['folder']     = $db -> getOne("SELECT title FROM ".$sqlname."file_cat WHERE idcategory = '$file[idcategory]' and identity = '$identity'");
		$file['size']       = num_format(filesize($rootpath."/files/".$fpath.$file['file']) / 1000);
		$file['ext']        = getExtention($file['file']);
		$file['mime']       = get_mimetype($file['file']);
		$file['icon']       = get_icon3($file['file']);

		return $file;

	}

	/**
	 * Загрузка файлов
	 *
	 * @param string $extra
	 * @return array
	 */
	public static function upload(string $extra = ''): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$fpath = $GLOBALS['fpath'];

		if ($extra != '') {
			$fpath .= $extra."/";
		}

		$maxupload = (int)$GLOBALS['maxupload'];
		$ext_allow = $GLOBALS['ext_allow'];

		$maxupload = ( $maxupload == 0 ) ? str_replace([
			'M',
			'm'
		], '', @ini_get('upload_max_filesize')) : $maxupload;

		$uploaddir = $rootpath.'/files/'.$fpath;
		$extAllow  = yexplode(",", $ext_allow);
		$message   = [];

		//print $maxupload;
		//print_r($_FILES);

		$file = [];

		//если загружается несколько файлов
		if (is_array($_FILES['file']['name'])) {

			for ($i = 0, $iMax = count($_FILES['file']['name']); $i < $iMax; $i++) {

				if ($_FILES['file']['error'][$i] == 0) {

					if (filesize($_FILES['file']['tmp_name'][$i]) > 0) {

						$ftitle     = $_FILES['file']['name'][$i];
						$ext        = texttosmall(getExtention($ftitle));
						$fname      = md5($ftitle.filesize($_FILES['files']['tmp_name'][$i]).time()).".".$ext;
						$ftype      = $_FILES['file']['type'][$i];
						$uploadfile = $uploaddir.$fname;
						//$fsize      = filesize( $_FILES[ 'file' ][ 'tmp_name' ][ $i ] );
						$fsize = $_FILES['file']['size'][$i] / ( 1000 * 1000 );

						if (in_array($ext, $extAllow)) {

							if ($fsize > $maxupload) {
								$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Превышает допустимые размеры!';
							}
							elseif (move_uploaded_file($_FILES['file']['tmp_name'][$i], $uploadfile)) {

								$message[] = 'Файл '.$ftitle.' успешно загружен.';
								$file[]    = [
									"title" => $ftitle,
									"name"  => $fname,
									"type"  => $ftype,
									"size"  => $fsize
								];

							}
							else {
								$message[] = 'Ошибка при загрузке файла '.$ftitle.' - '.self::ERRORS[$_FILES['file']['error'][$i]];
							}

						}
						else {
							$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Файлы такого типа не разрешено загружать.';
						}

					}

				}
				else {

					$message[] = 'Ошибка при загрузке файла '.$_FILES['file']['name'][$i].' - '.self::ERRORS[$_FILES['file']['error'][$i]];

				}

			}

		}

		//если загружается один файл
		elseif (is_string($_FILES['file']['name'])) {

			if ($_FILES['file']['error'] == 0) {

				$ftitle     = $_FILES['file']['name'];
				$ext        = texttosmall(getExtention($ftitle));
				$fname      = md5($ftitle.filesize($_FILES['files']['tmp_name']).time()).".".$ext;
				$ftype      = $_FILES['file']['type'];
				$uploadfile = $uploaddir.$fname;
				//$fsize      = filesize( $_FILES[ 'file' ][ 'tmp_name' ] );
				$fsize = $_FILES['file']['size'] / ( 1000 * 1000 );

				if (in_array($ext, $extAllow)) {

					if ($fsize > $maxupload) {
						$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Превышает допустимые размеры!';
					}
					elseif (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {

						$message[] = 'Файл '.$ftitle.' успешно загружен.';
						$file[]    = [
							"title" => $ftitle,
							"name"  => $fname,
							"type"  => $ftype,
							"size"  => $fsize
						];

					}
					else {
						$message[] = 'Ошибка при загрузке файла '.$ftitle.' - '.self::ERRORS[$_FILES['file']['error']];
					}

				}
				else {
					$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Файлы такого типа не разрешено загружать.';
				}

			}
			else {

				$message[] = 'Ошибка при загрузке файла '.$_FILES['file']['name'].' - '.self::ERRORS[$_FILES['file']['error']];

			}

		}

		elseif (is_array($_FILES['files']['name'])) {

			//если загружается несколько файлов в одном поле
			for ($i = 0, $iMax = count($_FILES['files']['name']); $i < $iMax; $i++) {

				if ($_FILES['files']['error'][$i] == 0) {

					if (filesize($_FILES['files']['tmp_name'][$i]) > 0) {

						$ftitle     = $_FILES['files']['name'][$i];
						$ext        = texttosmall(getExtention($ftitle));
						$fname      = md5($ftitle.filesize($_FILES['files']['tmp_name'][$i]).time()).".".$ext;
						$ftype      = $_FILES['files']['type'][$i];
						$uploadfile = $uploaddir.$fname;
						//$fsize      = filesize( $_FILES[ 'file' ][ 'tmp_name' ][ $i ] );
						$fsize = $_FILES['files']['size'][$i] / ( 1000 * 1000 );

						if (in_array($ext, $extAllow)) {

							if ($fsize > $maxupload) {
								$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Превышает допустимые размеры!';
							}
							elseif (move_uploaded_file($_FILES['files']['tmp_name'][$i], $uploadfile)) {

								$message[] = 'Файл '.$ftitle.' успешно загружен.';
								$file[]    = [
									"title" => $ftitle,
									"name"  => $fname,
									"type"  => $ftype,
									"size"  => $fsize
								];

							}
							else {
								$message[] = 'Ошибка при загрузке файла '.$ftitle.' - '.self::ERRORS[$_FILES['files']['error'][$i]];
							}

						}
						else {
							$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Файлы такого типа не разрешено загружать.';
						}

					}

				}
				else {

					$message[] = 'Ошибка при загрузке файла '.$_FILES['files']['name'][$i].' - '.self::ERRORS[$_FILES['files']['error'][$i]];

				}

			}

		}

		//print_r($message);

		return [
			"data"    => $file,
			"message" => $message
		];

	}

	/**
	 * Редактирование записи файла
	 *
	 * @param       $id - fid
	 * @param array $params
	 * @return mixed
	 */
	public static function edit($id, array $params = []) {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];

		//поля, которые есть в таблице
		$allowed = [
			'ftitle',
			'fname',
			'ftype',
			'ftag',
			'fver',
			'iduser',
			'clid',
			'pid',
			'did',
			'tskid',
			'coid',
			'folder',
			'shared',
			"size",
			"datum",
			'identity'
		];

		//очищаем от мусора
		$params = $db -> filterArray($params, $allowed);

		//новая запись
		if ($id < 1) {

			$db -> query("INSERT INTO ".$sqlname."file SET ?u", arrayNullClean($params));
			$id = $db -> insertId();

		}
		//обновление записи
		else {

			unset($params['identity']);
			$db -> query("UPDATE ".$sqlname."file SET ?u WHERE fid = '$id' and identity = '$identity'", arrayNullClean($params));

		}

		return $id;

	}

	/**
	 * Удаление файла
	 *
	 * @param $id
	 * @return bool
	 */
	public static function delete($id): bool {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		//получаем имя айла
		$fname = $db -> getOne("SELECT fname FROM ".$sqlname."file WHERE fid = '$id' and identity = '$identity'");

		//находим дубликаты
		$doubles = $db -> getCol("SELECT fid FROM ".$sqlname."file WHERE fname = '$fname' and fid != '$id' and identity = '$identity'");

		//удаляем id удаленного файла из массива
		$res = $db -> query("SELECT * FROM ".$sqlname."history WHERE FIND_IN_SET('$id', REPLACE(fid, ';',',')) > 0");
		while ($da = $db -> fetch($res)) {

			$f = yexplode(";", $da['fid']);
			if (( $key = array_search($id, $f) ) !== false) {
				unset($f[$key]);
			}

			//запишем новое значение
			$db -> query("UPDATE ".$sqlname."history SET fid = '".yimplode(";", $f)."' where cid = '".$da['cid']."'");

		}

		if (count($doubles) == 0) {
			unlink($rootpath."/files/".$fpath.$fname);
		}

		$db -> query("DELETE FROM ".$sqlname."file WHERE fid = '$id' and identity = '$identity'");

		return true;

	}

	/**
	 * Возвращает массив с информацией по каждому файлу
	 *
	 * @param string|array $files - переданные fid файлов в виде массива или строки
	 * @return array
	 */
	public static function infoFiles($files): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$list = [];

		if (is_string($files)) {
			$files = yexplode(";", str_replace(",", ";", $files));
		}
		foreach ($files as $id) {
			$list[] = self ::info($id);
		}

		return $list;

	}

	/**
	 * Список файлов из карточек
	 * @param array $params - clid, pid, did
	 * @return array
	 */
	public static function cardFiles(array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		global $fpath, $identity, $sqlname, $db;

		$clid = (int)$params['clid'];
		$pid  = (int)$params['pid'];
		$did  = (int)$params['did'];

		$fileSort = texttosmall($params['fileSort']);

		$folders = $db -> getIndCol("idcategory", "SELECT idcategory, title FROM {$sqlname}file_cat WHERE identity = '$identity'");

		$list = [];

		if ($clid > 0) {
			$query = "SELECT * FROM {$sqlname}file WHERE (clid = '$clid' OR pid IN (SELECT pid FROM {$sqlname}personcat WHERE clid = '$clid' AND identity = '$identity') OR did IN (SELECT did FROM {$sqlname}dogovor WHERE clid = '$clid' and identity = '$identity')) and identity = '$identity' GROUP BY fid ORDER BY fid DESC";
		}
		elseif ($pid > 0) {
			$query = "SELECT * FROM {$sqlname}file WHERE pid = '$pid' and identity = '$identity' GROUP BY fname ORDER BY fid DESC";
		}
		elseif ($did > 0) {
			$query = "SELECT * FROM {$sqlname}file WHERE did = '$did' and identity = '$identity' GROUP BY fname ORDER BY fid DESC";
		}

		$result = $db -> query($query);
		$all    = $db -> affectedRows();

		while ($da = $db -> fetch($result)) {

			$dtime = filemtime($rootpath."/files/".$fpath.$da['fname']);

			$list[(int)$da['fid']] = [
				"id"       => (int)$da['fid'],
				"icon"     => get_icon2($da['ftitle']),
				"name"     => $da['ftitle'],
				"folder"   => $folders[$da['folder']],
				"folderid" => (int)$da['folder'],
				"date"     => date('H:i d-m-Y', $dtime),
				"did"      => (int)$da['did'],
				"version"  => $da['fver'] ?? NULL,
				"size"     => filesize($rootpath."/files/".$fpath.$da['fname']) / 1000,
			];

		}

		//сортируем массив документов по давности
		( $fileSort != 'desc' ) ? krsort($list) : ksort($list);

		$ssort = ( $fileSort == 'desc' ) ? '' : 'desc';
		$icon  = ( $fileSort == 'desc' ) ? 'icon-sort-alt-down' : 'icon-sort-alt-up';

		return [
			"list"  => $list,
			"total" => $all,
			"sort"  => $ssort,
			"icon"  => $icon
		];

	}

}