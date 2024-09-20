<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

//print_r($_FILES);

error_reporting(E_ERROR);

use Salesman\Upload;
use Salesman\ZipFolder;

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];

global $userRights;

function getFCatalog($id, $level = 0, $res = []) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	global $res;

	$sort = !$id ? " and subid = '0'" : " and subid = '$id'";

	$re = $db -> query("SELECT idcategory FROM {$sqlname}file_cat WHERE idcategory > 0 $sort and identity = '$identity' ORDER BY idcategory");
	while ($da = $db -> fetch($re)) {

		$res[] = $da["idcategory"];

		if ($da['idcategory'] > 0) {

			$level++;
			getFCatalog($da['idcategory'], $level);
			$level--;

		}

	}

	return $res;
}

if ($action == 'add') {

	$fver       = untag($_REQUEST['fver']);
	$ftag       = untag($_REQUEST['ftag']);
	$clid       = (int)$_REQUEST['clid'];
	$pid        = (int)$_REQUEST['pid'];
	$did        = (int)$_REQUEST['did'];
	$tskid      = (int)$_REQUEST['tskid'];
	$coid       = (int)$_REQUEST['coid'];
	$folder     = (int)$_REQUEST['idcategory'];
	$new_folder = (int)$_REQUEST['new_folder'];
	$shared     = $_REQUEST['shared'];
	$iduser     = $iduser1;

	$result = [
		"message" => "Ошибка",
		"error"   => "Вероятно загружен слишком большой файл"
	];

	if (!empty($_FILES) || !isset($_FILES)) {

		$upload = Upload ::upload();

		//print_r($upload);

		$message = $upload['message'];

		foreach ($upload['data'] as $file) {

			$arg = [
				'ftitle'   => $file['title'],
				'fname'    => $file['name'],
				'ftype'    => $file['type'],
				'ftag'     => $ftag,
				'fver'     => '1',
				'iduser'   => $iduser1,
				'clid'     => $clid,
				'pid'      => $pid,
				'did'      => $did,
				'tskid'    => $tskid,
				'coid'     => $coid,
				'folder'   => $folder,
				'shared'   => $shared,
				"size"     => $file['size'],
				"datum"    => current_datumtime(),
				'identity' => $identity
			];

			$fid[] = Upload ::edit(0, $arg);

		}

		$result = [
			"message" => "Выполнено",
			"error"   => '<br>'.yimplode('<br>', $message)
		];

	}

	print json_encode_cyr($result);

	exit();

}
if ($action == 'edit') {

	$fid        = (int)$_REQUEST['fid'];
	$fver       = $_REQUEST['fver'];
	$oldfile    = $_REQUEST['oldfile'];
	$ftag       = $_REQUEST['ftag'];
	$iduser     = (int)$_REQUEST['iduser'];
	$did        = (int)$_REQUEST['did'];
	$folder     = (int)$_REQUEST['idcategory'];
	$new_folder = untag($_REQUEST['new_folder']);
	$clid       = (int)$_REQUEST['clid'];
	$pid        = (int)$_REQUEST['pid'];
	$did        = (int)$_REQUEST['did'];
	$tskid      = (int)$_REQUEST['tskid'];

	if ($new_folder != '') {

		$folder = $db -> getOne("SELECT idcategory FROM {$sqlname}file_cat WHERE title = '$new_folder' and identity = '$identity'");
		if ($folder == 0) {

			$db -> query("INSERT INTO {$sqlname}file_cat SET ?u", [
				'title'    => $new_folder,
				'shared'   => $shared,
				'identity' => $identity
			]);
			$folder = $db -> insertId();

		}

	}

	//приходит массив с одним файлом
	$upload = Upload ::upload();

	$message = $upload['message'];

	if (!empty($upload['data'])) {

		$fver = settype($fver, "integer") + 1;

		//удалим старую версию
		unlink($rootpath.'/files/'.$oldfile);

		foreach ($upload['data'] as $file) {

			$arg = [
				'ftitle' => $file['title'],
				'fname'  => $file['name'],
				'ftype'  => $file['type'],
				'fver'   => $fver,
				'ftag'   => $ftag,
				'iduser' => $iduser,
				'folder' => $folder,
				'did'    => $did,
				'clid'   => $clid,
				'pid'    => $pid,
				'tskid'  => $tskid,
			];

			//$db -> query("UPDATE {$sqlname}file SET ?u WHERE fid = '$fid' and identity = '$identity'", arrayNullClean($arg));
			$rez       = Upload ::edit($fid, $arg);
			$message[] = "Изменения успешно внесены";

		}

	}
	else {

		$arg = [
			'fver'   => $fver,
			'ftag'   => $ftag,
			'iduser' => $iduser,
			'folder' => $folder,
			'did'    => $did
		];

		//$db -> query("UPDATE {$sqlname}file SET ?u WHERE fid = '$fid' and identity = '$identity'", arrayNullClean($arg));
		$rez       = Upload ::edit($fid, $arg);
		$message[] = "Изменения успешно внесены";

	}

	$result = [
		"message" => "Выполнено",
		"error"   => '<br>'.yimplode('<br>', $message)
	];

	print json_encode_cyr($result);

	exit();

}
if ($action == "delete") {

	$fid = $_REQUEST['id'];

	$rez = Upload ::delete($fid);

	$result = [
		"message" => ( $rez ) ? "Сделано" : "Ошибка",
		"error"   => ''
	];

	print json_encode_cyr($result);

	exit();

}

if ($action == "mass") {

	$idcategory = $_REQUEST['idcat'];
	$word       = str_replace(" ", "%", $_REQUEST['word']);
	$sort       = '';

	$isSelect = $_REQUEST['isSelect'];
	$ids      = yexplode(",", $_REQUEST['ids']);

	$rez  = '';
	$msg  = $err = [];
	$good = 0;

	$fpath = $rootpath.'/files/'.$fpath;

	if ($isSelect == 'doAll') {

		//если у пользователя есть доступ в Бюджет, то покажем папку бюджет
		if (!$userRights['budjet']) {

			$folder_ex = $db -> getOne("SELECT idcategory FROM {$sqlname}file_cat WHERE title='Бюджет' and identity = '$identity'");

			if ($folder_ex > 0) {
				$sort .= " and {$sqlname}file.folder != '".$folder_ex."'";
			}

		}

		if ($word != '') {
			$sort .= " AND (({$sqlname}file.ftitle LIKE '%".$word."%') OR ({$sqlname}file.ftag LIKE '%".$word."%'))";
		}

		//Найдем id сатегорий с общими папками и создадим массив
		$farray = $db -> getCol("SELECT idcategory FROM {$sqlname}file_cat WHERE shared='yes' and identity = '$identity' ORDER by title");

		//Сформируем запрос по папкам и подпапкам
		$folders = getFCatalog($idcategory);
		if ($idcategory > 0) {
			$folders[] = $idcategory;
		}

		if (count($farray) > 0) {
			$s = " OR folder IN (".implode(",", $farray).") ";
		}
		$sort .= " and ({$sqlname}file.iduser IN (".implode(",", get_people($iduser1, 'yes')).") $s)";

		if (count($folders) > 0) {
			$sort .= " and folder IN (".implode(",", $folders).") ";
		}

		$query = "
		SELECT
			{$sqlname}file.fid as id,
			{$sqlname}file.pid as pid,
			{$sqlname}file.clid as clid,
			{$sqlname}file.did as did,
			{$sqlname}file.ftitle as title,
			{$sqlname}file.fname as file,
			{$sqlname}file.iduser as iduser,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}dogovor.title as deal,
			{$sqlname}user.title as user,
			{$sqlname}file_cat.title as folder
		FROM {$sqlname}file
			LEFT JOIN {$sqlname}user ON {$sqlname}file.iduser = {$sqlname}user.iduser
			LEFT JOIN {$sqlname}personcat ON {$sqlname}file.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}file.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}file.did = {$sqlname}dogovor.did
			LEFT JOIN {$sqlname}file_cat ON {$sqlname}file.folder = {$sqlname}file_cat.idcategory
		WHERE
			{$sqlname}file.fid > 0 $sort AND
			{$sqlname}file.identity = '$identity'
		ORDER BY {$sqlname}file.fname";

		$result = $db -> query($query);
		while ($data = $db -> fetch($result)) {

			try {

				$db -> query("delete from {$sqlname}file where fid = '".$data['id']."' and identity = '$identity'");

				if (file_exists($fpath.$data['file'])) {
					unlink($fpath.$data['file']);
				}

				//удаляем id удаленного файла из массива
				$res = $db -> query("SELECT * FROM {$sqlname}history WHERE FIND_IN_SET('".$data['fid']."', REPLACE(fid, ';',',')) > 0");
				while ($da = $db -> fetch($res)) {

					$f = yexplode(";", $da['fid']);
					if (( $key = array_search($data['fid'], $f) ) !== false) {
						unset($f[$key]);
					}

					//запишем новое значение
					$db -> query("update {$sqlname}history set fid = '".implode(";", $f)."' where cid = '".$da['cid']."'");

				}

				$good++;
			}
			catch (Exception $e) {

				$err[] = $e -> getMessage();

			}

		}

	}

	if ($isSelect == 'doSelected') {

		$result = $db -> query("SELECT * FROM {$sqlname}file WHERE fid IN (".implode(",", $ids).") and identity = '$identity'");
		while ($data = $db -> fetch($result)) {

			try {

				$db -> query("delete from {$sqlname}file where fid = '".$data['fid']."' and identity = '$identity'");

				if (file_exists($fpath.$data['file'])) {
					unlink($fpath.$data['file']);
				}

				//удаляем id удаленного файла из массива
				$res = $db -> query("SELECT * FROM {$sqlname}history WHERE FIND_IN_SET('".$data['fid']."', REPLACE(fid, ';',',')) > 0");
				while ($da = $db -> fetch($res)) {

					$f = yexplode(";", $da['fid']);
					if (( $key = array_search($data['fid'], $f) ) !== false) {
						unset($f[$key]);
					}

					//запишем новое значение
					$db -> query("update {$sqlname}history set fid = '".implode(";", $f)."' where cid = '".$da['cid']."'");

				}

				$good++;

			}
			catch (Exception $e) {

				$err[] = $e -> getMessage();

			}

		}

	}

	//print 'Удалено '.$good.' записей';

	$result = [
		"message" => 'Удалено '.$good.' записей',
		"error"   => yimplode('<br>', $err)
	];

	print json_encode_cyr($result);

	exit();

}

if ($action == "cat.delete") {

	$idcategory = $_REQUEST['id'];

	$db -> query("delete from {$sqlname}file_cat where idcategory = '$idcategory' and identity = '$identity'");

	$result = [
		"message" => 'Запись удачно удалена',
		"error"   => ''
	];

	print json_encode_cyr($result);

	exit();

}
if ($action == "cat.edit") {

	$idcategory = (int)$_REQUEST['idcategory'];
	$subid      = (int)$_REQUEST['subid'];
	$title      = $_REQUEST['title'];
	$shared     = $_REQUEST['shared'];

	if ($idcategory > 0) {

		$db -> query("UPDATE {$sqlname}file_cat SET ?u where idcategory = '$idcategory' and identity = '$identity'", [
			"title"  => $title,
			"subid"  => $subid,
			"shared" => isset($shared) ? "yes" : "no",
		]);

	}
	else {

		$db -> query("INSERT INTO {$sqlname}file_cat SET ?u", [
			"subid"    => $subid,
			"title"    => $title,
			"shared"   => !empty($shared) ? "yes" : "no",
			"identity" => $identity
		]);

	}

	$result = [
		"message" => 'Выполнено',
		"error"   => ''
	];

	print json_encode_cyr($result);

	exit();

}

if ($action == "catlist") {

	$id        = (int)$_REQUEST['id'];
	$folder_ex = 0;

	if (!$userRights['budjet']) {

		$folder_ex = $db -> getOne("SELECT idcategory FROM {$sqlname}file_cat WHERE title='Бюджет' and identity = '$identity'");
		$fff       = " and idcategory != '$folder_ex'";

	}

	print '<div data-id="" data-title="" class="xfolder fol_it block hand Bold"><i class="icon-folder blue"></i>&nbsp;[все]</div>';

	$catalog = Upload ::getCatalogLine();
	foreach ($catalog as $key => $value) {

		if ($folder_ex > 0 && $value['id'] == $folder_ex) {
			continue;
		}

		$padding = 'mt5 Bold';

		if ((int)$value['level'] == 1) {
			$padding = 'pl20';
		}
		elseif ((int)$value['level'] > 1) {
			$x       = 20 + (int)$value['level'] * 10;
			$padding = "pl{$x} ml15 fs-09";
		}

		$folder = ( $value['level'] == 0 ? 'icon-folder-open deepblue' : ( $value['level'] == 1 ? 'icon-folder-open blue' : 'icon-folder broun' ) );

		print '
		<div class="pt5">
			<div class="xfolder fol '.( $value['id'] == $id ? 'fol_it' : '' ).' block ellipsis hand '.$padding.'" data-id="'.$value['id'].'" data-title="'.$value['title'].'">
				<div class="strelka w5 ml10 mr10"></div><i class="'.$folder.'"></i>'.( $value['shared'] == 'yes' ? '&nbsp;<i class="icon-users-1 sup green" title="Общая папка"></i> ' : '' ).'&nbsp;'.$value['title'].'
			</div>
		</div>
		';

	}

	exit();

}

if ($action == "zip") {

	//print_r($_REQUEST);

	$fid  = $_REQUEST['fid'];
	$card = $_REQUEST['card'];
	$clid = $_REQUEST['clid'];
	$pid  = $_REQUEST['pid'];
	$did  = $_REQUEST['did'];

	$uploaddir = $rootpath.'/files/'.$fpath;
	$tmp       = "tmp".time();

	$path = $uploaddir.$tmp."/";

	if (!is_dir($path) && !mkdir($path, 0766) && !is_dir($path)) {
		throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
	}
	chmod($path, 0777);

	if (empty($fid)) {

		if ($card == 'client' && $clid > 0) {
			$q1 = "(clid='".$clid."' or pid IN (select pid from {$sqlname}personcat WHERE clid='".$clid."' and identity = '$identity') or did IN (select did from {$sqlname}dogovor WHERE clid='".$clid."' and identity = '$identity'))";
		}
		elseif ($card == 'person' && $pid > 0) {
			$q1 = "pid = '".$pid."'";
		}
		elseif ($card == 'dogovor' && $did > 0) {
			$q1 = "did = '".$did."'";
		}

		$res = $db -> query("select * from {$sqlname}file WHERE identity = '$identity' and $q1 GROUP BY fname ORDER BY fname DESC");
		while ($da = $db -> fetch($res)) {

			if (file_exists($uploaddir.$da['fname'])) {
				copy($uploaddir.$da['fname'], $path.translit($da['ftitle']));
			}

		}

	}
	else {

		//$fid = yexplode(";", $fid);

		foreach ($fid as $fi) {

			$result = $db -> getRow("SELECT * FROM {$sqlname}file WHERE fid = '".$fi."' and identity = '$identity'");
			$file   = $result['fname'];
			$name   = str_replace(" ", "-", translit($result['ftitle']));

			if (file_exists($uploaddir.$file)) {
				copy($uploaddir.$file, $path.$name);
			}

		}

	}

	$zfile = 'attach'.time().'.zip';

	/*$zip = new zip_file( $uploaddir.$zfile );

	$zip -> set_options( [
		'basedir'    => $uploaddir,
		'inmemory'   => 0,
		'level'      => 9,
		'storepaths' => 0
	] );
	$zip -> add_files( $path );
	$zip -> create_archive();
	$zip -> download_file();*/

	$folder = $uploaddir.$tmp;

	$zip = new ZipFolder();
	$zip -> zipFile($zfile, $uploaddir, $path);

	//удалим не нужные файлы
	if ($dh = opendir($path)) {

		while (( $file = readdir($dh) ) !== false) {

			if ($file != "." && $file != "..") {

				unlink($path."/".$file);

			}

		}

	}

	rmdir($path);

	header('Content-Type: '.get_mimetype($zfile));
	header('Content-Disposition: attachment; filename="'.$zfile.'"');
	header('Content-Transfer-Encoding: binary');
	header('Accept-Ranges: bytes');

	readfile($uploaddir.$zfile);

	unlink($uploaddir.$zfile);

	exit();

}