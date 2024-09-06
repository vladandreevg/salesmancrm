<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
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

		$result             = $db -> getRow("SELECT * FROM {$sqlname}file WHERE fid = '$id' and identity = '$identity'");
		$file['title']      = $result["ftitle"];
		$file['file']       = $result["fname"];
		$file['idcategory'] = (int)$result["folder"];
		$file['text']       = $result["ftag"];
		$file['clid']       = (int)$result["clid"];
		$file['pid']        = (int)$result["pid"];
		$file['did']        = (int)$result["did"];
		$file['iduser']     = (int)$result["iduser"];
		$file['folder']     = $db -> getOne("SELECT title FROM {$sqlname}file_cat WHERE idcategory = '$file[idcategory]' and identity = '$identity'");
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
		if ((int)$id == 0) {

			$db -> query("INSERT INTO {$sqlname}file SET ?u", arrayNullClean($params));
			$id = $db -> insertId();

		}
		//обновление записи
		else {

			unset($params['identity']);
			$db -> query("UPDATE {$sqlname}file SET ?u WHERE fid = '$id' and identity = '$identity'", arrayNullClean($params));

		}

		return $id;

	}

	/**
	 * Список файлов с фильтрацией
	 * @param array $params
	 * @return array
	 */
	public static function list(array $params = []): array {

		global $userRights, $iduser1;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];

		$page       = (int)$params['page'];
		$idcategory = (int)$params['idcategory'];
		$word       = str_replace( " ", "%", $params['word'] );
		$sort       = '';
		$tuda       = $params['tuda'];
		$ord        = $params['ord'];
		$ftype      = $params['ftype'];

		if ( $ord == '' ) {
			$ord = 'fname';
		}
		if ( $tuda == '' ) {
			$tuda = '';
		}

		$list = [];

		//если у пользователя есть доступ в Бюджет, то покажем папку бюджет
		if ( !$userRights['budjet'] ) {

			$folder_ex = $db -> getOne( "SELECT idcategory FROM {$sqlname}file_cat WHERE title = 'Бюджет' and identity = '$identity'" );

			if ( (int)$folder_ex > 0 ) {
				$sort .= " and COALESCE(file.folder, 0) != '$folder_ex'";
			}

		}

		if ( $word != '' ) {
			$sort .= " AND (file.ftitle LIKE '%$word%' OR file.ftag LIKE '%$word%')";
		}

		//Найдем id категорий с общими папками и создадим массив
		$farray = $db -> getCol( "SELECT idcategory FROM {$sqlname}file_cat WHERE shared = 'yes' and identity = '$identity' ORDER by title" );

		if ($idcategory > 0) {

			$folders = self ::getFCatalog($idcategory);
			$folders[] = $idcategory;

			/*foreach ($catalog as $value) {
				$folders[] = $value['id'];
			}*/

			$ss = ( !empty($folders) ) ? " or file.folder IN (".yimplode(",", $folders).")" : '';

			$sort .= " and (file.folder = '$idcategory' $ss)";

		}

		$s = '';
		if ( !empty( $farray ) ) {
			$s = " OR file.folder IN (".yimplode( ",", $farray ).") ";
		}

		$sort .= " and (file.iduser IN (".yimplode( ",", get_people( $iduser1, 'yes' ) ).") $s)";

		$ss = ($idcategory == 0) ? " OR file.folder IS NULL" : "";

		if ( !empty( $folders ) ) {
			$sort .= " and (file.folder IN (".yimplode( ",", $folders ).") $ss)";
		}

		if ( $ftype == 'img' ) {
			$sort .= " and (file.fname LIKE '%.png' OR file.fname LIKE '%.jpg' OR file.fname LIKE '%.gif' OR file.fname LIKE '%.jpeg' OR file.fname LIKE '%.tiff' OR file.fname LIKE '%.bmp')";
		}
		elseif ( $ftype == 'doc' ) {
			$sort .= " and (file.fname LIKE '%.txt' OR file.fname LIKE '%.doc' OR file.fname LIKE '%.docx' OR file.fname LIKE '%.xls' OR file.fname LIKE '%.xlsx' OR file.fname LIKE '%.rtf' OR file.fname LIKE '%.ppt' OR file.fname LIKE '%.pptx')";
		}
		elseif ( $ftype == 'pdf' ) {
			$sort .= " and (file.fname LIKE '%.pdf')";
		}
		elseif ( $ftype == 'zip' ) {
			$sort .= " and (file.fname LIKE '%.zip' OR file.fname LIKE '%.rar' OR file.fname LIKE '%.tar' OR file.fname LIKE '%.7z' OR file.fname LIKE '%.gz')";
		}

		//print
		$query = "
		SELECT
			file.fid as id,
			file.pid as pid,
			file.clid as clid,
			file.did as did,
			file.ftitle as title,
			file.fname as file,
			file.iduser as iduser,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}dogovor.title as deal,
			{$sqlname}user.title as user,
			{$sqlname}file_cat.title as xfolder
		FROM {$sqlname}file `file`
			LEFT JOIN {$sqlname}user ON file.iduser = {$sqlname}user.iduser
			LEFT JOIN {$sqlname}personcat ON file.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat ON file.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}dogovor ON file.did = {$sqlname}dogovor.did
			LEFT JOIN {$sqlname}file_cat ON file.folder = {$sqlname}file_cat.idcategory
		WHERE
			file.fid > 0 $sort AND
			file.identity = '$identity'
		";

		$lines_per_page = 100; //Стоимость записей на страницу
		$result         = $db -> query( $query );
		$all_lines      = $db -> affectedRows( $result );

		$count_pages = ceil( $all_lines / $lines_per_page );

		if ( $page > $count_pages ) {
			$page = 1;
		}

		if ( empty( $page ) || $page <= 0 ) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query .= " ORDER BY file.$ord $tuda LIMIT $lpos,$lines_per_page";

		$result = $db -> query( $query );

		if ( $count_pages == 0 ) {
			$count_pages = 1;
		}

		while ($da = $db -> fetch( $result )) {

			$change = '';

			$icon = get_icon2( $da['title'] );
			$size = num_format( filesize( $rootpath."/files/".$fpath.$da['file'] ) / 1024 );

			if ( $userRights['delete'] && get_accesse_other( (int)$da['iduser'] ) == 'yes' ) {
				$change = 'yes';
			}

			$dtime = filemtime( $rootpath."/files/".$fpath.$da['file'] );//current(explode(".", $da['file']));
			$ddate = date( 'H:i d.m.Y', $dtime );

			$isView = isViewable( $da['file'] ) ? '1' : '';

			$list[] = [
				"id"     => (int)$da['id'],
				"name"   => $da['file'],
				"icon"   => $icon,
				"title"  => $da['title'],
				"datum"  => str_replace( " ", "&nbsp;<br>", $ddate ),
				"size"   => $size,
				"clid"   => (int)$da['clid'],
				"client" => $da['client'],
				"pid"    => (int)$da['pid'],
				"person" => $da['person'],
				"did"    => (int)$da['did'],
				"deal"   => $da['deal'],
				"user"   => $da['user'],
				"change" => $change,
				"view"   => $isView,
				"folder" => $da['xfolder']
			];

		}

		return [
			"list"    => $list,
			"page"    => $page,
			"pageall" => $count_pages,
			"ord"     => $ord,
			"desc"    => $tuda,
			"all"     => $all_lines
		];

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
		$fname = $db -> getOne("SELECT fname FROM {$sqlname}file WHERE fid = '$id' and identity = '$identity'");

		//находим дубликаты
		$doubles = $db -> getCol("SELECT fid FROM {$sqlname}file WHERE fname = '$fname' and fid != '$id' and identity = '$identity'");

		//удаляем id удаленного файла из массива
		$res = $db -> query("SELECT * FROM {$sqlname}history WHERE FIND_IN_SET('$id', REPLACE(fid, ';',',')) > 0");
		while ($da = $db -> fetch($res)) {

			$f = yexplode(";", $da['fid']);
			if (( $key = array_search($id, $f) ) !== false) {
				unset($f[$key]);
			}

			//запишем новое значение
			$db -> query("UPDATE {$sqlname}history SET fid = '".yimplode(";", $f)."' where cid = '".$da['cid']."'");

		}

		if (count($doubles) == 0) {
			unlink($rootpath."/files/".$fpath.$fname);
		}

		$db -> query("DELETE FROM {$sqlname}file WHERE fid = '$id' and identity = '$identity'");

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

	/**
	 * Рекрсивно возвращает массив со всеми категориями и подкатегориями.
	 * Можно задать стартовый id категории. Тогда будет возвращена только эта ветка
	 *
	 * @param int $id
	 * @param int $level
	 *
	 * @return array
	 */
	public static function getCatalog(int $id = 0, int $level = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$category = [];

		$sort = ( $id > 0 ) ? "subid = '$id' AND" : "subid = 0 AND";

		$re = $db -> query("SELECT * FROM {$sqlname}file_cat WHERE $sort identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			//найдем категории, в которых данная категория является главной
			$count  = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}file_cat WHERE idcategory = '$da[idcategory]' AND identity = '$identity'");
			$xcount = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}file WHERE folder = '$da[idcategory]' AND identity = '$identity'");

			$subcat = ( $count > 0 ) ? self ::getCatalog($da['idcategory'], $level + 1) : [];

			$category[(int)$da["idcategory"]] = [
				"id"     => (int)$da["idcategory"],
				"title"  => $da["title"],
				"shared" => $da["shared"],
				"level"  => $level,
				"count"  => $xcount
			];

			//если есть подкатегории, то добавим их рекурсивно
			if (!empty($subcat)) {
				$category[$da["idcategory"]]["subcat"] = $subcat;
			}

		}

		return $category;

	}

	/**
	 * Возвращает структуру каталога, но без вложения подкаталогов в основной каталог
	 *
	 * @param int $id
	 * @param int $level
	 * @param array $ures
	 *
	 * @return array
	 */
	public static function getCatalogLine(int $id = 0, int $level = 0, array $ures = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$sort     = $GLOBALS['sort'];
		$maxlevel = preg_replace("/[^0-9]/", "", $GLOBALS['maxlevel']);
		//$maxlevel = 5;

		global $ures;

		//$sort .= ( $id > 0 ) ? " and subid = '$id'" : " and subid = '0'";
		$sort = !$id ? " and subid = '0'" : " and subid = '$id'";

		if($id > 0 && empty($ures)){
			$sort = " and idcategory = '$id'";
		}

		if ($maxlevel != '' && $level > $maxlevel) {
			return (array)$ures;
		}

		$re = $db -> query("SELECT * FROM {$sqlname}file_cat WHERE idcategory > 0 $sort and identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			$xcount = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}file WHERE folder = '$da[idcategory]' AND identity = '$identity'");

			$ures[] = [
				"id"       => (int)$da["idcategory"],
				"title"    => $da["title"],
				"shared" => $da["shared"],
				"count"  => $xcount,
				"level"    => $level,
				"sub"      => (int)$da["sub"]
			];

			if ((int)$da['idcategory'] > 0) {

				$level++;
				self ::getCatalogLine((int)$da['idcategory'], $level);
				$level--;

			}

		}

		return (array)$ures;

	}

	public static function getFCatalog($id, $level = 0, $xres = []) {

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		global $xres;

		$sort = !$id ? " and subid = '0'" : " and subid = '$id'";

		if($id > 0 && empty($xres)){
			$sort = " and idcategory = '$id'";
		}

		//print "$sort\n";

		$re = $db -> query( "SELECT idcategory FROM {$sqlname}file_cat WHERE idcategory > 0 $sort and identity = '$identity' ORDER BY idcategory" );
		while ($da = $db -> fetch( $re )) {

			$xres[] = (int)$da["idcategory"];

			if ( (int)$da['idcategory'] > 0 ) {

				$level++;
				self::getFCatalog( (int)$da['idcategory'], $level );
				$level--;

			}

		}

		return $xres;
	}

}