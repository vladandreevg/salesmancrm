<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

use Salesman\Leads;

error_reporting(E_ERROR);

header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );


$action = $_REQUEST['action'];

$ress         = $db -> getOne("select usersettings from {$sqlname}user where iduser='".$iduser1."' and identity = '$identity'");
$usersettings = json_decode((string)$ress, true);

//настройки модуля для аккаунта
$mdwset       = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");
$leadsettings = json_decode((string)$mdwset['content'], true);
$coordinator  = (int)$leadsettings["leadСoordinator"];

/**
 * Добавление/Изменение заявки
 */
if ($action == "add") {

	$params = $_REQUEST;
	$error = '';

	$lead   = new Leads();

	try {

		$result = $lead -> edit($params);

	}
	catch (Exception $e) {

		$error = $e -> getMessage();

	}

	$r = ["result" => "Сделано","error" => $error];

	print json_encode_cyr($r);

	exit();

}

/**
 * Назначение ответственного, либо закрытие заявки
 */
if ($action == "setuser") {

	$params = $_REQUEST;

	$lead   = new Leads();
	$result = $lead -> setuser($params);

	print json_encode_cyr([
		"result" => $result['result'],
		"error"  => $result['error']
	]);

	exit();

}

/**
 * Обработка заявки ответственным
 */
if ($action == "workit") {

	$params = $_REQUEST;
	$params['deal'] = $_REQUEST['dogovor'];

	//var_dump($params);

	$lead   = new Leads();
	$result = $lead -> workit($params);

	print json_encode_cyr([
		"result" => $result['result'],
		"error"  => $result['error'],
		"did"    => (int)$result['did'],
		"clid"   => (int)$result['clid']
	]);

	exit();

}

/**
 * Удаление заявки
 */
if ($action == "delete") {

	$id = $_REQUEST['id'];

	$r = Leads ::delete($id);

	print json_encode_cyr($r);

	exit();

}

/**
 * Импорт заявок из Excel
 */
if ($action == "import") {

	$iduser = $_REQUEST['iduser'];

	$status = ($iduser > 0) ? 1 : 0;

	$mess     = '';
	$err      = [];
	$leadData = [];

	//если загружается файл
	if (filesize($_FILES['file']['tmp_name']) > 0) {

		//разбираем запрос из файла
		$ftitle = basename($_FILES['file']['name']);
		$fname  = time().".".getExtention($ftitle);//переименуем файл
		$ftype  = $_FILES['file']['type'];

		if ($GLOBALS['maxupload'] == '') {
			$maxupload = str_replace([
				'M',
				'm'
			], '', @ini_get('upload_max_filesize'));
		}

		$uploaddir      = $rootpath.'/files/'.$fpath;
		$uploadfile     = $uploaddir.$fname;
		$file_ext_allow = ['xls'];
		$cur_ext        = texttosmall(getExtention($ftitle));

		//проверим тип файла на поддерживаемые типы
		if (in_array($cur_ext, $file_ext_allow)) {

			if ((filesize($_FILES['file']['tmp_name']) / 1000000) > $maxupload) {
				$err[] = 'Ошибка при загрузке файла: Превышает допустимые размеры!';
			}
			else {

				if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {

					//обрабатываем данные из файла
					/*
					if ($cur_ext == 'csv') {

						$uploadfile = fopen($url, 'rb' );
						while (($data = fgetcsv($uploadfile, 1000, ";")) !== false) {
							$lead[] = implode(";", $data);
						}

					}
					if ($cur_ext == 'xls') {

						$data = new Spreadsheet_Excel_Reader();
						$data -> setOutputEncoding('UTF-8');
						$data -> read($uploadfile, false);
						$lead = $data -> dumptoarray();//получили двумерный массив с данными

						$k = 0;
						for ( $i = 2, $iMax = count( $lead ); $i <= $iMax; $i++) {

							if ($lead[ $i ][1] != '') {
								$g = 0;
								for ( $j = 1, $jMax = count( $lead[ $i ] ); $j < $jMax; $j++) {

									$leadData[ $k ][ $g ] = $lead[ $i ][ $j ];
									$g++;
								}
								$k++;
							}

						}

					}
					*/

					$lead = parceExcel($uploadfile);

					//конец загрузки спеки из поля
					unlink($uploadfile);

				}
				else {
					$err[] = 'Ошибка при загрузке файла: '.$_FILES['file']['error'];
				}

			}

		}
		else {
			$err[] = 'Ошибка при загрузке файла: Файлы такого типа не разрешено загружать.';
		}


		//print_r($leadData);
		//exit();

		$plus = 0;

		if (!empty($leadData)) {

			$l = new Leads();

			foreach ($leadData as $lead) {

				//найдем ущетвующий контакт в базе
				if ($lead[1] != '') {
					$result = $db -> getRow("SELECT * FROM {$sqlname}personcat WHERE mail LIKE '%".$lead[1]."%' and identity = '$identity'");
				}
				elseif ($lead[2] != '') {
					$result = $db -> getRow("SELECT * FROM {$sqlname}personcat WHERE (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($lead[2])."%') or (replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($lead[2])."%') and identity = '$identity'");
				}

				$pid     = $result['pid'];
				$clid    = $result['clid'];
				$iduserr = $result['iduser'];

				if ($clid < 1 && $pid < 1) {//если контакт не найден поищем в компаниях

					if ($lead[1] != '') {
						$result = $db -> getRow("SELECT * FROM {$sqlname}clientcat WHERE mail_url LIKE '%".$lead[1]."%' and identity = '$identity'");
					}
					elseif ($lead[2] != '') {
						$result = $db -> getRow("SELECT * FROM {$sqlname}clientcat WHERE (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($lead[2])."%') or (replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($lead[2])."%') and identity = '$identity'");
					}

					$clid    = $result['clid'];
					$iduserr = $result['iduser'];

				}

				if ($iduserr > 0 && $iduser < 1) {

					$user   = $iduserr;
					$status = 1;

				}
				elseif ($iduserr > 0 && $iduser > 0) {

					$user   = $iduser;
					$status = 1;

				}
				else {

					$user   = 0;
					$status = 0;

				}

				$param = [
					"datum"       => current_datumtime(),
					"title"       => untag($lead[0]),
					"email"       => untag($lead[1]),
					"phone"       => untag($lead[2]),
					"site"        => untag($lead[3]),
					"company"     => untag($lead[5]),
					"city"        => untag($lead[4]),
					"description" => untag($lead[6]),
					"iduser"      => $user + 0,
					"clientpath"  => $l::getPath($lead[7]),
					"status"      => $status,
					"clid"        => $clid + 0,
					"pid"         => $pid + 0,
					"partner"     => (int)get_partnerbysite($lead[8]) + 0,
					"identity"    => $identity
				];
				$le    = $l -> edit($param);
				$id    = $le['id'];

				$plus++;

			}

		}

	}

	$err[] = 'Всего обработано: <b>'.count($leadData).'</b> записей.<br>Добавлено: <b>'.$plus.' записей</b>';

	print '{"result":"Сделано","error":"'.yimplode("<br>", $err).'"}';

	exit();

}
if ($action == 'export') {

	$d1      = $_REQUEST['da1'];
	$d2      = $_REQUEST['da2'];
	$user    = implode(",", $_REQUEST['user']);
	$statuss = implode(",", $_REQUEST['statuss']);

	if ($user != '') {
		$sort .= " and iduser IN (".$user.")";
	}

	if ($statuss != '') {
		$sort .= " and status IN (".$statuss.")";
	}

	if ($d1 != '') {
		$sort .= "and (datum BETWEEN '".$d1." 00:00:01' and '".$d2." 23:59:59')";
	}

	$status = Leads::STATUSES;
	$colors = Leads::COLORS;
	$rezult = Leads::REZULTES;
	$colorr = Leads::COLORREZULT;

	$otchet[] = [
		"Дата",
		"Статус",
		"Статус обработки",
		"Имя",
		"Email",
		"Телефон",
		"Источник",
		"Описание",
		"Описание закрытия",
		"Ответственный",
		"Клиент",
		"Контакт"
	];

	$result = $db -> getAll("SELECT * FROM {$sqlname}leads WHERE id > 0 ".$sort." and identity = '$identity' ORDER BY datum DESC");
	foreach ($result as $data) {

		$userr = '';

		$clientpath = $db -> getOne("SELECT name FROM {$sqlname}clientpath WHERE id = '".$data['clientpath']."' and identity = '$identity'");

		if ($data['iduser'] > 0)
			$userr = current_user($data['iduser']);

		$otchet[] = [
			$data['datum'],
			strtr($data['status'], $status),
			strtr($data['rezult'], $rezult),
			$data['title'],
			$data['email'],
			$data['phone'],
			$clientpath,
			str_replace("\n", "\r\n", $data['description']),
			str_replace("\n", "\r\n", $data['rezz']),
			$userr,
			current_client($data['clid']),
			current_person($data['pid'])
		];

	}

	/*
	$xls = new Excel_XML('UTF-8', false, 'Leads');
	$xls -> addArray($otchet);
	$xls -> generateXML('Leads');
	*/

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.leads.xlsx');

	exit();

}

/**
 * Действия с группой заявок
 */
if ($action == "mass") {

	$user     = $_REQUEST['iduser'];
	$ids      = explode(";", $_REQUEST['ids']);
	$doAction = $_REQUEST['doAction'];
	$rezult   = $_REQUEST['rezult'];
	$rezz     = $_REQUEST['rezz'];

	$good = 0;
	$err  = [];
	$ss   = '';

	if ($user > 0 && $doAction == 'pDelegate') {//если пеоемещаем

		foreach ($ids as $id) {

			if ($id > 0) {

				$db -> query("UPDATE {$sqlname}leads SET iduser = '$user', status = '1' WHERE id = '$id' and identity = '$identity'");
				$good++;

			}

		}

	}

	if ($rezult != '' && $doAction == 'pClose') {//если удаляем

		foreach ($ids as $id) {

			if ($id > 0) {

				//интерес дисквалифицирован (отсеян координатором) и закрыт с результатом
				//1 = Спам, 2 - Дубль, 3 - Другое
				//Установлен статус 3 - Закрыт
				$db -> query("UPDATE {$sqlname}leads SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean([
					"datum_do" => current_datumtime(),
					"rezult"   => $rezult,
					"status"   => '3',
					"rezz"     => $rezz
				]));

				addHistorty([
					"datum"    => current_datumtime(),
					"iduser"   => $iduser1,
					"des"      => "Обработан лид #$id: $rezz",
					"tip"      => 'СобытиеCRM',
					"identity" => $identity
				]);

				$good++;

			}

		}

	}

	$error = "Выполнено для ".$good." записей. Ошибок: ".count($err);

	print '{"result":"Сделано","error":"'.$error.'"}';

	exit();

}

if ($action == "source.edit") {

	$id          = $_REQUEST['id'];
	$name        = $_REQUEST['name'];
	$isDefault   = $_REQUEST['isDefault'];
	$utm_source  = $_REQUEST['utm_source'];
	$destination = prepareMobPhone($_REQUEST['destination']);

	//снимем умолчания
	if ($isDefault == 'yes') {

		$db -> query("UPDATE {$sqlname}clientpath SET isDefault = '' WHERE identity = '$identity'");

	}

	if ($id < 1) {

		$db -> query("INSERT INTO {$sqlname}clientpath SET ?u", arrayNullClean([
			"name"        => $name,
			"isDefault"   => $isDefault,
			"utm_source"  => $utm_source,
			"destination" => $destination,
			"identity"    => $identity
		]));

		echo 'Сделано';

	}

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}clientpath SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean([
			"name"        => $name,
			"isDefault"   => $isDefault,
			"utm_source"  => $utm_source,
			"destination" => $destination
		]));

		echo 'Сделано';

	}

}
if ($action == "source.delete") {

	$id = $_REQUEST['id'];
	$multi = $_REQUEST['multi'];

	if ($multi == '') {

		$db -> query("UPDATE {$sqlname}clientcat SET clientpath = '".$_REQUEST['newid']."' WHERE clientpath = '".$id."' and identity = '$identity'");
		$db -> query("UPDATE {$sqlname}personcat SET clientpath = '".$_REQUEST['newid']."' WHERE clientpath = '".$id."' and identity = '$identity'");
		$db -> query("DELETE FROM {$sqlname}clientpath where id = '".$id."' and identity = '$identity'");

		print "Сделано";

	}
	else {

		$db -> query("UPDATE {$sqlname}clientcat SET clientpath = '".$_REQUEST['newid']."' WHERE clientpath IN (".$multi.") and identity = '$identity'");

		$db -> query("UPDATE {$sqlname}personcat set clientpath = '".$_REQUEST['newid']."' WHERE clientpath IN (".$multi.") and identity = '$identity'");

		$db -> query("DELETE FROM {$sqlname}clientpath WHERE id IN (".$multi.") and identity = '$identity'");

		print "Сделано";

	}

	exit();

}

if ($action == "utms.edit") {

	$id                   = $_REQUEST['id'];
	$data['utm_source']   = $_REQUEST["utm_source"];
	$data['utm_url']      = htmlspecialchars($_REQUEST["utm_url"]);
	$data['utm_medium']   = $_REQUEST["utm_medium"];
	$data['utm_campaign'] = $_REQUEST["utm_campaign"];
	$data['utm_term']     = $_REQUEST["utm_term"];
	$data['utm_content']  = $_REQUEST["utm_content"];
	$data['clientpath']   = $_REQUEST["clientpath"];
	$data['site']         = htmlspecialchars($_REQUEST["site"]);
	$data['identity']     = $identity;

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}leads_utm SET ?u WHERE id = '$id'", $data);

		print '{"result":"Сделано","error":""}';

	}
	else {

		$db -> query("INSERT INTO {$sqlname}leads_utm SET ?u", $data);

		print '{"result":"Сделано","error":""}';

	}

	exit();

}
if ($action == "utms.delete") {

	$id = $_REQUEST['id'];

	$db -> query("DELETE FROM {$sqlname}leads_utm WHERE id = '$id' and identity = '$identity'");

	print "Сделано";

	exit();

}
