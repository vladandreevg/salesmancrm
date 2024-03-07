<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Price;
use Salesman\Upload;

error_reporting(E_ERROR);

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action     = $_REQUEST['action'];
$idcategory = (int)$_REQUEST['idcategory'];

//изменим поле artikul - разрешим Null, если это еще не сделано
$dbfields = $db -> getRow("SHOW FIELDS FROM {$sqlname}price WHERE Field = 'artikul'");
if (texttosmall($dbfields['Null']) != 'yes') {
	$db -> query("ALTER TABLE {$sqlname}price CHANGE COLUMN `artikul` `artikul` VARCHAR(255) NULL AFTER `n_id`");
}

if ($action == "edit") {

	$id = (int)$_REQUEST['n_id'];

	$price = new Price();
	$rez   = $price -> edit($id, $_REQUEST);

	print $rez['text'];

	exit();

}

if ($action == "delete") {

	$id = (int)$_REQUEST['id'];

	$rez = Price ::delete($id);

	print $rez['text'];

	exit();

}

if ($action == "export") {

	$dname = [];

	$result = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='price' and identity = '$identity' ORDER BY fld_order");
	while ($data = $db -> fetch($result)) {
		$dname[$data['fld_name']] = $data['fld_title'];
		$dvar[$data['fld_name']]  = $data['fld_var'];
		$don[]                    = $data['fld_name'];
	}

	$idcategory = (int)$_REQUEST['idcat'];
	$word       = $_REQUEST['word'];
	$sort       = '';

	$otchet[] = [
		"ID",
		"Артикул",
		"Категория",
		"ID категории",
		"Наименование",
		"Ед.изм.",
		$dname['price_in'],
		$dname['price_1'],
		$dname['price_2'],
		$dname['price_3'],
		$dname['price_4'],
		$dname['price_5'],
		"Примечание",
		"НДС"
	];

	if ($idcategory > 0) {

		$sort .= " and ({$sqlname}price.pr_cat='".$idcategory."' or {$sqlname}price.pr_cat IN (SELECT idcategory FROM {$sqlname}price_cat WHERE {$sqlname}price_cat.sub='".$idcategory."' and {$sqlname}price_cat.identity = '".$identity."'))";

	}

	if ($word != '') {
		$sort .= " and (({$sqlname}price.artikul LIKE '%".$word."%') or ({$sqlname}price.title LIKE '%".$word."%') or ({$sqlname}price.descr LIKE '%".$word."%'))";
	}

	$result = $db -> query("SELECT * FROM {$sqlname}price WHERE n_id > 0 ".$sort." and identity = '$identity' ORDER BY pr_cat");
	while ($data = $db -> fetch($result)) {

		$result1 = $db -> getRow("select * from {$sqlname}price_cat where idcategory='".$data['pr_cat']."' and identity = '$identity'");
		$cats    = $result1["title"];
		$idsub   = $result1["sub"];

		$sub2 = $db -> getOne("select title from {$sqlname}price_cat where idcategory='".$idsub."' and identity = '$identity'");

		$otchet[] = [
			$data['n_id'],
			$data['artikul'],
			$cats,
			$data['pr_cat'],
			$data['title'],
			$data['edizm'],
			$data['price_in'],
			$data['price_1'],
			$data['price_2'],
			( $data['price_3'] ),
			( $data['price_4'] ),
			( $data['price_5'] ),
			untag($data['descr']),
			( $data['nds'] )
		];

	}

	/*
	$xls = new Excel_XML( 'UTF-8', true, 'Price' );
	$xls -> addArray( $otchet );
	$xls -> generateXML( 'export_price' );
	*/

	Shuchkin\SimpleXLSXGen ::fromArray($otchet) -> downloadAs('export.price.xlsx');

	exit();

}

if ($action == "mass") {

	$word     = $_REQUEST['word'];
	$ids      = explode(";", $_REQUEST['ids']);
	$doAction = $_REQUEST['doAction'];
	$newcat   = (int)$_REQUEST['newcat'];
	$isSelect = $_REQUEST['isSelect'];

	$good = 0;
	$err  = [];
	$ss   = '';
	$sort = '';

	if ($newcat > 0 && $doAction == 'pMove') {//если пеоемещаем

		if ($isSelect == 'doAll') {//все

			if ($idcategory > 0) {

				$sort .= " and ({$sqlname}price.pr_cat='".$idcategory."' or {$sqlname}price.pr_cat IN (SELECT idcategory FROM {$sqlname}price_cat WHERE {$sqlname}price_cat.sub='".$idcategory."' and {$sqlname}price_cat.identity = '".$identity."'))";

			}

			if ($word != '') {
				$sort .= " and (({$sqlname}price.artikul LIKE '%".$word."%') or ({$sqlname}price.title LIKE '%".$word."%') or ({$sqlname}price.descr LIKE '%".$word."%'))";
			}

			$results = $db -> query("SELECT * FROM {$sqlname}price WHERE n_id > 0 $sort and identity = '$identity' ORDER BY title");
			while ($datas = $db -> fetch($results)) {

				try {

					$db -> query("UPDATE {$sqlname}price SET pr_cat = '$newcat' WHERE n_id = '".$datas['n_id']."' and identity = '$identity'");
					$good++;

				}
				catch (Exception $e) {
					$err[] = $e -> getMessage();
				}

			}
		}
		if ($isSelect == 'doSel' && !empty($ids)) {//выбранные

			foreach ($ids as $id) {

				if ($id > 0) {

					try {

						$db -> query("update {$sqlname}price set pr_cat='$newcat' WHERE n_id = '$id' and identity = '$identity'");
						$good++;

					}
					catch (Exception $e) {
						$err[] = $e -> getMessage();
					}
				}
			}
		}

	}
	if ($doAction == 'pDele') {//если удаляем

		if ($isSelect == 'doAll') {//все

			if ($idcategory > 0) {

				$sort .= " and ({$sqlname}price.pr_cat='".$idcategory."' or {$sqlname}price.pr_cat IN (SELECT idcategory FROM {$sqlname}price_cat WHERE {$sqlname}price_cat.sub='".$idcategory."' and {$sqlname}price_cat.identity = '".$identity."'))";

			}

			if ($word != '') {
				$sort .= " and (({$sqlname}price.artikul LIKE '%".$word."%') or ({$sqlname}price.title LIKE '%".$word."%') or ({$sqlname}price.descr LIKE '%".$word."%'))";
			}

			$results = $db -> query("SELECT * FROM {$sqlname}price WHERE n_id > 0 ".$sort." and identity = '$identity'");
			while ($datas = $db -> fetch($results)) {

				try {
					$db -> query("delete from {$sqlname}price where n_id = '".$datas['n_id']."' and identity = '$identity'");
					$good++;
				}
				catch (Exception $e) {
					$err[] = $e -> getMessage();
				}

			}
		}
		if ($isSelect == 'doSel' && !empty($ids)) {//выбранные

			foreach ($ids as $id) {

				if ($id > 0) {

					try {
						$db -> query("delete from {$sqlname}price where n_id = '$id' and identity = '$identity'");
						$good++;
					}
					catch (Exception $e) {
						$err[] = $e -> getMessage();
					}
				}
			}
		}

	}
	if ($doAction == 'pArchive') {//если удаляем

		if ($isSelect == 'doAll') {//все

			if ($idcategory > 0) {

				$sort .= " and ({$sqlname}price.pr_cat = '$idcategory' or {$sqlname}price.pr_cat IN (SELECT idcategory FROM {$sqlname}price_cat WHERE {$sqlname}price_cat.sub = '$idcategory' and {$sqlname}price_cat.identity = '$identity'))";

			}

			if ($word != '') {
				$sort .= " and (({$sqlname}price.artikul LIKE '%$word%') or ({$sqlname}price.title LIKE '%$word%') or ({$sqlname}price.descr LIKE '%$word%'))";
			}

			$results = $db -> query("SELECT * FROM {$sqlname}price WHERE n_id > 0 $sort and identity = '$identity'");
			while ($datas = $db -> fetch($results)) {

				try {
					$db -> query("UPDATE {$sqlname}price SET archive = 'yes' WHERE n_id = '$datas[n_id]' and identity = '$identity'");
					$good++;
				}
				catch (Exception $e) {
					$err[] = $e -> getMessage();
				}

			}
		}
		if ($isSelect == 'doSel' && !empty($ids)) {//выбранные

			foreach ($ids as $id) {

				if ($id > 0) {

					try {
						$db -> query("UPDATE {$sqlname}price SET archive = 'yes' WHERE n_id = '$id' and identity = '$identity'");
						$good++;
					}
					catch (Exception $e) {
						$err[] = $e -> getMessage();
					}

				}

			}

		}

	}
	if ($doAction == 'pArchiveOut') {//если удаляем

		if ($isSelect == 'doAll') {//все

			if ($idcategory > 0) {

				$sort .= " and ({$sqlname}price.pr_cat = '$idcategory' or {$sqlname}price.pr_cat IN (SELECT idcategory FROM {$sqlname}price_cat WHERE {$sqlname}price_cat.sub = '$idcategory' and {$sqlname}price_cat.identity = '$identity'))";

			}

			if ($word != '') {
				$sort .= " and (({$sqlname}price.artikul LIKE '%$word%') or ({$sqlname}price.title LIKE '%$word%') or ({$sqlname}price.descr LIKE '%$word%'))";
			}

			$results = $db -> query("SELECT * FROM {$sqlname}price WHERE n_id > 0 $sort and identity = '$identity'");
			while ($datas = $db -> fetch($results)) {

				try {
					$db -> query("UPDATE {$sqlname}price SET archive = 'no' WHERE n_id = '$datas[n_id]' and identity = '$identity'");
					$good++;
				}
				catch (Exception $e) {
					$err[] = $e -> getMessage();
				}

			}
		}
		if ($isSelect == 'doSel' && !empty($ids)) {//выбранные

			foreach ($ids as $id) {

				if ($id > 0) {

					try {
						$db -> query("UPDATE {$sqlname}price SET archive = 'no' WHERE n_id = '$id' and identity = '$identity'");
						$good++;
					}
					catch (Exception $e) {
						$err[] = $e -> getMessage();
					}

				}

			}

		}

	}

	print "Выполнено для ".$good." записей. Ошибок:".count($err);

	exit();

}

if ($action == "import.upload") {

	//проверяем расширение файла. Оно д.б. только csv
	$cur_ext = getExtention($_FILES['file']['name']);

	if (
		!in_array($cur_ext, [
			"csv",
			"xls",
			"xlsx"
		])
	) {

		print 'Ошибка при загрузке файла - Недопустимый формат файла. <br>Допускаются только файлы в формате <b>CSV</b>, <b>XLS</b> или <b>XLSX</b>';

	}
	else {

		$url = $rootpath.'/files/'.$fpath.basename($_FILES['file']['name']);

		//загружаем файлы
		//$upload = Upload ::upload();

		//Сначала загрузим файл на сервер
		if (move_uploaded_file($_FILES['file']['tmp_name'], $url)) {

			setcookie("url_catalog", basename($_FILES['file']['name']), time() + 86400);
			print 'Файл загружен';

		}
		else {
			print 'Ошибка при загрузке файла <b>"'.$_FILES['file']['name'].'"</b>!<br /><b class="yelw">Ошибка:</b> '.$_FILES['file']['error'].'<br />';
		}

	}

	exit();

}
if ($action == "import.on") {

	$isPrice = [
		"price_in",
		"price_1",
		"price_2",
		"price_3",
		"price_4",
		"price_5",
		"nds"
	];

	$url    = $rootpath.'/files/'.$fpath.$_COOKIE['url_catalog'];//файл для расшифровки
	$fields = $_REQUEST['field'];                                //порядок полей

	$date_create = current_datumtime();

	$cc      = $pp = $z = 0;
	$data    = $indexs = $names = [];
	$headers = $categorys = [];
	$list    = [];

	/**
	 * Проходим заголовки и смотрим индексы
	 */
	foreach ($fields as $i => $field) {

		$tip = yexplode(":", $field, 0);
		$val = yexplode(":", $field, 1);

		//если это не категория
		if ($tip == 'price') {

			$headers[$i] = $val;

			if ($val == 'n_id') {
				$idx = $i;
			} //индекс id позиции
			if ($val == 'title') {
				$clx = $i;
			} //индекс названия позиции
			if ($val == 'artikul') {
				$alx = $i;
			} //индекс артикула позиции

			$indexs['price'][]  = $val;//массив ключ поля -> номер столбца
			$names['price'][$i] = $val;//массив номер столбца -> индекс поля

		}
		elseif ($tip == 'category') {

			//название категории
			if ($val == 'title') {

				$cTitleIdx = $i;//массив ключ поля -> номер столбца

				$headers[$i] = "cattitle";

			}

			//id категории
			elseif ($val == 'idcat') {

				$cIDIdx = $i;//массив ключ поля -> номер столбца

				$headers[$i] = "catid";

			}

		}

	}

	//считываем данные из файла в массив
	$cur_ext = getExtention($_COOKIE['url_catalog']);

	/*
	if ( $cur_ext == 'xls' ) {

		$datas = new Spreadsheet_Excel_Reader();
		$datas -> setOutputEncoding( 'UTF-8' );
		$datas -> read( $url, false );
		$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

		foreach ($data1 as $j => $row) {

			for ( $g = 0; $g < count( $data1[ $j + 1 ] ); $g++ ) {

				$list[ $j ][] = untag( $data1[ $j + 1 ][ $g + 1 ] );

			}

		}

		//удалим заголовки
		unset( $list[ 1 ] );

	}
	if ( $cur_ext == 'csv' || $cur_ext == 'xlsx' ) {

		try {

			$datas = new SpreadsheetReader( $url );
			$datas -> ChangeSheet( 0 );

		}
		catch ( Exception $e ) {
		}

		foreach ( $datas as $k => $Row ) {

			foreach ( $Row as $key => $value ) {

				$list[ $k ][] = ( $cur_ext == 'csv' ) ? enc_detect( untag( $value ) ) : untag( $value );

			}

		}

		$list = array_values( $list );

		//удалим заголовки
		unset( $list[ 0 ] );

	}
	*/

	try {

		$list = parceExcel($url);//переиндексируем массив
		$list = array_values($list);

		//переиндексируем все строки
		//раскодируем надписи
		//получим массив со строками, в которых ключи именованы
		foreach ($list as $key => $row) {

			$row = array_values($row);

			foreach ($row as $i => $v) {

				if ($headers[$i] != '') {

					//обработаем пришедшие значения
					if ($headers[$i] == 'descr') {
						$row[$headers[$i]] = htmlspecialchars($v);
					}
					elseif (in_array($headers[$i], $isPrice)) {
						$row[$headers[$i]] = pre_format($v);
					}
					else {
						$row[$headers[$i]] = $v;
					}

				}
				unset($row[$i]);

			}

			$list[$key] = $row;

		}

		//удалим файл
		unlink($url);

		//print_r($list);

	}
	catch (Exception $e) {

		//удалим файл
		unlink($url);

		print $e -> getMessage();

		exit();

	}

	$new1 = 0;
	$upd1 = 0;
	$new2 = 0;
	$upd2 = 0;
	$new3 = 0;
	$upd3 = 0;

	$err    = [];
	$params = [];

	//print_r($data);
	//exit();

	//имеющиеся категории
	$cats = $db -> getIndCol("idcategory", "SELECT title, idcategory FROM {$sqlname}price_cat WHERE identity = '$identity' ORDER BY title");

	//print_r($prcat);
	//exit();

	/**
	 * импортируем данные
	 */
	foreach ($list as $row) {

		$catId    = $row['catid'];
		$catTitle = $row['cattitle'];
		$prid     = $row['n_id'];

		unset($row['catid'], $row['cattitle'], $row['n_id']);

		//если idcat пришел и он есть в базе
		if ($catId > 0 && array_key_exists($catId, $cats)) {
			$row['pr_cat'] = $catId;
		}

		if ($catId < 1 && $catTitle != '') {

			//сопоставляем id раздела текущей позиции, если нет создаем.
			if (in_array($catTitle, array_values($cats))) {

				//если такое название уже сужествует, то сопоставляем id
				$row['pr_cat'] = strtr($catTitle, array_flip($cats));

			}
			else {

				$db -> query("INSERT INTO {$sqlname}price_cat SET ?u", [
					'title'    => $catTitle,
					'identity' => $identity
				]);
				$row['pr_cat'] = $db -> insertId();

				$cats[$row['pr_cat']] = $catTitle;

			}

		}
		elseif ($catId < 1 && $catTitle == '') {
			$row['pr_cat'] = 0;
		}

		//поищем запись в базе
		if ($row['n_id'] > 0) {
			$prid = $db -> getOne("select n_id from {$sqlname}price where n_id = '".$row['n_id']."' and identity = '$identity'") + 0;
		}
		elseif ($row['artikul'] != '') {
			$prid = $db -> getOne("select n_id from {$sqlname}price where artikul = '".$row['artikul']."' and identity = '$identity'") + 0;
		}
		elseif ($row['title'] != '') {
			$prid = $db -> getOne("select n_id from {$sqlname}price where title = '".$row['title']."' and identity = '$identity'") + 0;
		}

		/**
		 * Обрабатываем позиции
		 */
		if ($prid > 0) {

			try {

				$db -> query("UPDATE {$sqlname}price SET ?u WHERE n_id = '$prid' AND identity = '$identity'", $row);
				$upd1++;

			}
			catch (Exception $e) {

				$err[] = "price-update: ".$e -> getMessage();

			}

		}
		else {

			$row['identity'] = $identity;

			try {

				$db -> query("INSERT INTO {$sqlname}price SET ?u", $row);
				$new1++;

			}
			catch (Exception $e) {

				$err[] = "price-insert: ".$e -> getMessage();

			}

		}

	}

	$mesg = '';

	if (count($err) == 0) {
		$mesg .= "Ошибок: нет<br>";
	}
	else {
		$mesg .= "Есть ошибки. Ошибои: ".yimplode("<br>", $err)."<br>";
	}


	if ($new1 > 0) {
		$mesg .= "Добавлено записей: ".$new1."<br>";
	}
	if ($upd1 > 0) {
		$mesg .= "Обновлено записей: ".$upd1."<br>";
	}

	logger('6', 'Импорт прайса', $iduser1);

	print $mesg;

	exit();

}
if ($action == "import.discard") {

	if ($_COOKIE['url_catalog'] != '') {

		$url = $rootpath.'/files/'.$fpath.$_COOKIE['url_catalog'];
		setcookie("url_catalog", '');
		unlink($url);

	}

	exit();

}

if ($action == "cat.edit") {

	$id = $_REQUEST['idcategory'];

	if ($id > 0) {

		$price = new Price();
		$rez   = $price -> editCategory($id, $_REQUEST);

	}
	else {

		$price = new Price();
		$rez   = $price -> editCategory(0, $_REQUEST);

	}

	print $rez['text'];

	exit();
}

if ($action == "cat.delete") {

	$id = $_REQUEST['id'];

	$mes = Price ::deleteCategory($id);

	print $mes;

	exit();

}

if ($action == "catlist") {

	$idcat = $_REQUEST['id'];

	$ss = ( $idcat == '' ) ? 'fol_it' : 'fol';

	print '
		<div class="pt5">
			<div class="fol_it block ellipsis hand" data-id="" data-title="">
				<i class="icon-folder blue"></i>&nbsp;[все]
			</div>
		</div>
	';

	$catalog = Price ::getPriceCatalog(0);
	foreach ($catalog as $key => $value) {

		$s  = ( $value['level'] > 0 ) ? str_repeat('&nbsp;&nbsp;', $value['level']).'<div class="strelka w5 ml10 mr10"></div>&nbsp;' : '';
		$ss = ( $value['id'] == $idcat ) ? 'fol_it' : 'fol';

		print '
			<div class="pt5">
				<div class="'.$ss.' block ellipsis hand" data-id="'.$value['id'].'" data-title="'.$value['title'].'">
					'.$s.'<i class="icon-folder blue"></i>&nbsp;'.$value['title'].'
				</div>
			</div>
		';

	}

	exit();

}

if ($action == "deleteEmpty") {

	$catalog  = Price ::getCatalog();
	$xcatalog = Price ::getCatalogCounts($catalog);
	$simple   = Price ::simplifyCatalog($xcatalog);

	$count = 0;

	if (!empty($simple['empty'])) {

		$query = "
		DELETE 
		FROM {$sqlname}price_cat
		WHERE
			idcategory IN (".yimplode(",", $simple['empty']).") AND
			identity = '$identity'
		";

		$db -> query($query);
		$count = $db -> affectedRows();

	}

	print "Удалено $count записей";

	exit();

}