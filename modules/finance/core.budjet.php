<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */

/* ============================ */

use Salesman\BankStatement;
use Salesman\Budget;
use Salesman\Client;
use Salesman\Upload;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

function Cleaner($string) {

	$string = trim($string);
	$string = str_replace('"', '”', $string);
	$string = str_replace('\n\r', '', $string);
	$string = str_replace("'", "&acute;", $string);

	return $string;

}

//print_r($_REQUEST);

foreach ($_REQUEST as $key => $value) {

	$params[ $key ] = (is_numeric($value)) ? pre_format($value) : (is_string($value) ? Cleaner($value) : $value);

}

$action = $params['action'];

/*
 * Добавление/изменение записи расхода/дохода
 */
if ($action == "edit") {

	// если в карточке сделки добавляем расход в бюджет
	// то сначала добавим обычный расход и свяжем его с записью из бюджета
	/*if( $params['providerplus'] == 'yes' && $settingsMore['budjetProviderPlus'] == 'yes' ){

		$arg = [
			"conid"    => (int)$params['conid'],
			"partid"   => (int)$params['partid'],
			"did"      => (int)$params['did'],
			"summa"    => (float)$params['summa'],
			"recal"    => 0,
			"identity" => $identity
		];
		$db -> query( "INSERT INTO {$sqlname}dogprovider SET ?u", $arg );
		$params['dogproviderid'] = $db -> insertId();

	}
	else{

	}*/

	$response = Budget ::edit((int)$params['id'], $params);

	//print_r($params);
	//print_r($response);

	reCalculate( (int)$params['did'] );

	if ($response['result'] == 'Error') {

		$message[] = 'Ошибка - Расход не добавлен!';
		$message[] = $response['error']['text'];

	}
	else {

		$message[] = $response['result'];

	}

	print $message = implode('<br>', $message);

}

/*
 * Перемещение средств между счетами
 */
if ($action == "move") {

	$response = Budget ::move($params);

	if ($response['result'] == 'Error') {
		$message[] = 'Ошибка - средства не перемещены!';
		$message[] = $response['error']['text'];
	}
	else {
		$message[] = $response['result'];
	}
	print $message = implode('<br>', $message);

}

/*
 * Отмена перемещения средств между счетами
 */
if ($action == "unmove") {

	$response = Budget ::unmove((int)$params['id']);

	if ($response['result'] == 'Error') {
		$message[] = 'Ошибка!';
		$message[] = $response['error']['text'];
	}
	else {
		$message[] = $response['result'];
	}

	print $message = implode('<br>', $message);

}

/**
 * Выгрузка журнала расходов
 */
if ($action == "export.budjet") {

	$year   = $params['year'];
	$sort   = '';
	$otchet = [];

	if (in_array($params['tar'], [
		'journal',
		'budjet'
	])) {

		$doo = $params['doo'];

		if ($doo == 'do') {
			$sort .= " and do = 'on'";
		}
		if ($doo == 'nodo') {
			$sort .= " and do != 'on'";
		}

		$otchet[] = [
			"ID",
			"Период",
			"Раздел",
			"Статья",
			"Дата расхода",
			"Дата план",
			"Тип",
			"Проведено",
			"Счет, №",
			"Счет, Дата",
			"Срок оплаты",
			"Дата оплаты",
			"Сумма",
			"Поставщик/Партнер",
			"Компания",
			"Расч.счет",
			"Ответственный",
			"Примечание"
		];

		$result = $db -> getAll("SELECT * FROM {$sqlname}budjet WHERE year = '$year' $sort and identity = '$identity' ORDER by mon DESC, datum DESC");
		foreach ($result as $data) {

			$cat = $tip = '';

			$res   = $db -> getRow("SELECT * FROM {$sqlname}budjet_cat WHERE id='".$data['cat']."' and identity = '$identity'");
			$cat   = $res["title"];
			$tip   = $res["tip"];
			$subid = $res["subid"];

			$razdel = $db -> getOne("SELECT title FROM {$sqlname}budjet_cat WHERE id = '$subid' and identity = '$identity'");

			if ($tip == 'dohod') {
				$tip = 'Поступление';
			}
			if ($tip == 'rashod') {
				$tip = 'Расход';
			}

			if ($data['cat'] == '0') {
				$tip = 'Перемещение';
			}

			$do = ($data['do'] == 'on') ? 'Проведено' : '';

			$res = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE id = '".$data['rs']."' and identity = '$identity'");
			$ist = $res["tip"];
			$rs  = $res["title"];
			$cid = $res["cid"];

			$mcid = $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps WHERE id = '$cid' and identity = '$identity' ORDER by id");

			if ($ist == 'bank') {
				$istochnik = 'р/сч.';
			}
			elseif ($ist == 'kassa') {
				$istochnik = 'касса';
			}
			else {
				$istochnik = '-/-';
			}

			$provider = $db -> getOne("SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['conid']."' and identity = '$identity'");
			$partner  = $db -> getOne("SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['partid']."' and identity = '$identity'");

			$otchet[] = [
				$data['id'],
				$data['mon']."/".$data['year'],
				$razdel,
				$cat,
				$data['datum'],
				$data['date_plan'],
				$tip,
				$do,
				$data['invoice'],
				$data['invoice_date'],
				$data['invoice_paydate'],
				pre_format($data['summa']),
				$provider.$partner,
				$mcid,
				$istochnik.': '.$rs,
				current_user($data['iduser']),
				$data['des']
			];

		}

	}
	elseif ( $params['tar'] == 'agents' ) {

		$filter = $params['pdoo'];

		$otchet[] = [
			"Период",
			"Дата расхода",
			"Дата план",
			"Тип контрагента",
			"Контрагент",
			"Статус",
			"Сумма",
			"Счет, №",
			"Счет, Дата",
			"Срок оплаты",
			"Дата оплаты",
			"Этап",
			"Статус сделки",
			"Название"
		];

		$count = count($filter);

		if ($count == 1) {

			//выполненные
			if (in_array('do', $filter)) {
				$sort = " and bj.do == 'on'";
			}

			//не запланированные
			if (in_array('noadd', $filter)) {
				$sort = " and bj.id is NULL";
			}

			//запланированные
			if (in_array('plan', $filter)) {
				$sort = " and bj.do != 'on' and bj.id is NOT NULL";
			}

		}
		if ($count == 2) {

			//проведенные и запланированные
			if (in_array('do', $filter) && in_array('plan', $filter)) {
				$sort = " and (bj.do != 'on' or bj.id is NOT NULL)";
			}

			//запланированные и не добавленные
			if (in_array('noadd', $filter) && in_array('plan', $filter)) {
				$sort = " and (bj.do != 'on' or bj.id is NULL)";
			}

			//запланированные и добавленные
			if (in_array('do', $filter) && in_array('noadd', $filter)) {
				$sort = " and (bj.do == 'on' or bj.id is NULL)";
			}

		}

		$q = "
		SELECT
			dp.id as id,
			dp.did as did,
			dp.conid as conid,
			dp.partid as partid,
			dp.summa as summa,
			dp.bid as bid,
			deal.title as dogovor,
			deal.datum_plan as dplan,
			bj.id as bjid,
			bj.do as do,
			bj.mon as mon,
			bj.year as year,
			bj.datum as datum,
			bj.date_plan as date_plan,
			bj.invoice as invoice,
			bj.invoice_date as invoice_date,
			bj.invoice_paydate as invoice_paydate
		FROM {$sqlname}dogprovider `dp`
			LEFT JOIN {$sqlname}dogovor `deal` ON dp.did = deal.did
			LEFT JOIN {$sqlname}budjet `bj` ON dp.bid = bj.id
		WHERE
			dp.id > 0
			$sort
			and (DATE_FORMAT(deal.datum_plan, '%Y') = '$year' or (bj.year = '$year'))
			and dp.identity = '$identity'
		ORDER BY deal.datum_plan DESC";

		//print $q;
		//exit();

		$result = $db -> getAll($q);
		foreach ($result as $da) {

			$do = "Не добавлен";

			//получим данные по сделке
			$json = get_dog_info($da['did']);
			$data = json_decode($json, true);

			$dogstatus = current_dogstepname($data['idcategory']);

			$period = (int)$da['mon'] > 0 ? str_pad($da['mon'], 2, "0", STR_PAD_LEFT).'/'.$da['year'] : NULL;

			$dstatus = ($data['close'] == 'yes') ? 'Закрыта' : 'Активна';

			if ($da['conid'] > 0) {
				$contragent = current_client($da['conid']);
				$tipname    = 'Поставщик';
			}
			elseif ($da['partid'] > 0) {
				$contragent = current_client($da['partid']);
				$tipname    = 'Партнер';
			}

			if ($da['bid'] > 0 && $da['do'] == 'on') {
				$do = "Проведен";
			}
			elseif ($da['bid'] < 1 && $da['do'] == 'on') {
				$do = "Запланирован";
			}

			$otchet[] = [
				$data['id'],
				$period ?? modifyDatetime($data['date_plan'], ["format" => "m/Y"]),
				$data['datum'],
				$data['date_plan'],
				$tipname,
				$contragent,
				$do,
				pre_format($da['summa']),
				$data['invoice'],
				$data['invoice_date'],
				$data['invoice_paydate'],
				$dogstatus.'%',
				$dstatus,
				$data['title']
			];

		}

	}

	/*
	$xls = new Excel_XML( 'UTF-8', true, 'Finance' );
	$xls -> addArray( $otchet );
	$xls -> generateXML( 'Finance' );
	*/

	Shuchkin\SimpleXLSXGen ::fromArray($otchet) -> downloadAs('export.finance.xlsx');

	exit();
}

/*
 * Проведение платежа
 */
if ($action == "doit") {

	$response = Budget ::doit((int)$params['id']);

	if ($response['result'] == 'Error') {

		$message[] = 'Ошибка - Невозможно провести платеж!';
		$message[] = $response['error']['text'];

	}
	else {
		$message[] = $response['result'];
	}

	print $message = implode('<br>', $message);

}

/*
 * Отмена платежа
 */
if ($action == "undoit") {

	$response = Budget ::undoit((int)$params['id']);

	if ($response['result'] == 'Error') {
		$message[] = 'Ошибка - невозможно отменить расход!';
		$message[] = $response['error']['text'];
	}
	else {
		$message[] = $response['result'];
	}

	print $message = implode('<br>', $message);
}

/*
 * Удаление записи расхода/дохода
 */
if ($action == "delete") {

	$response = Budget ::delete((int)$params['id']);

	if ($response['result'] == 'Error') {
		$message[] = 'Ошибка - невозможно удалить запись';
		$message[] = $response['error']['text'];
	}
	else {
		$message[] = $response['result'];
	}
	print $message = implode('<br>', $message);
}

/*
 * Изменение категории расхода/дохода
 */
if ($action == "cat.edit") {

	$response = Budget ::editCategory((int)$params['id'], $params);

	if ($response['result'] == 'Error') {
		$message[] = 'Ошибка - невозможно добавить категорию!';
		$message[] = $response['error']['text'];
	}
	else {
		$message[] = $response['result'];
	}
	print $message = implode('<br>', $message);
}

/*
 * Удаление категории расхода/дохода
 */
if ($action == "cat.delete") {

	$response = Budget ::deleteCategory((int)$params['id'], (int)$params['newcat']);

	if ($response['result'] == 'Error') {

		$message[] = 'Ошибка - невозможно удалить категорию!';
		$message[] = $response['error']['text'];

	}
	else {

		$message[] = $response['result'];

	}

	print $message = implode('<br>', $message);

}

/**
 * Выгрузка счетов
 */
if ($action == 'export.invoices') {

	$dstart = $_REQUEST['dstart'];
	$dend   = $_REQUEST['dend'];

	$otchet[] = [
		"Номер счета",
		"Дата счета",
		"Дата план.",
		"Дата факт.",
		"Сумма",
		"Плательщик",
		"ИНН",
		"Ответственный по сделке",
		"Автор счета",
		"Расч.счет",
		"Компания"
	];

	if ($dstart) {
		$per = " and (datum BETWEEN '".$dstart." 00:00:01' and '".$dend." 23:59:59')";
	}

	$result = $db -> getAll("SELECT * FROM {$sqlname}credit WHERE crid > 0 $per ".$sort1." and identity = '$identity' ORDER BY datum DESC");
	foreach ($result as $data) {

		$iduser = getDogData($data['did'], 'iduser');
		$payer  = getDogData($data['did'], 'payer');

		$re  = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE id = '".$data['rs']."' and identity = '$identity' ORDER by id");
		$rs  = $re["title"];
		$cid = $re["cid"];

		$mcid = $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps WHERE id = '".$cid."' and identity = '$identity' ORDER by id");

		$recv = get_client_recv($payer, 'yes');

		if ($data['datum_credit'] != '0000-00-00') {
			$dcredit = $data['datum_credit'];
		}
		else {
			$dcredit = '';
		}

		$otchet[] = [
			$data['invoice'],
			$data['datum'],
			$dcredit,
			$data['invoice_date'],
			pre_format($data['summa_credit']),
			current_client($payer),
			$recv['castInn'],
			current_user($iduser),
			current_user($data['iduser']),
			$rs,
			$mcid
		];
	}

	/*
	$xls = new Excel_XML('UTF-8', true, 'Payments');
	$xls -> addArray($otchet);
	$xls -> generateXML('Payments');
	*/

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.payments.xlsx');

	exit();
}

//устаревшая обработка
if ($action == 'export.invoices2') {

	$dstart = $_REQUEST['dstart'];
	$dend   = $_REQUEST['dend'];

	$otchet[] = [
		"Номер счета",
		"Дата счета",
		"Дата план.",
		"Дата факт.",
		"Сумма",
		"Ответственный по сделке",
		"Автор счета"
	];

	//print "SELECT * FROM {$sqlname}credit WHERE crid > 0 and (datum BETWEEN '".$dstart." 00:00:01' and '".$dend." 23:59:59') ".$sort1." and identity = '$identity' ORDER BY datum DESC";

	$result = $db -> query("SELECT * FROM {$sqlname}credit WHERE crid > 0 and (datum BETWEEN '".$dstart." 00:00:01' and '".$dend." 23:59:59') ".$sort1." and identity = '$identity' ORDER BY datum DESC");
	while ($data = $db -> fetch($result)) {

		$iduser = $db -> getOne("SELECT iduser FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'");

		$otchet[] = [
			$data['invoice'],
			$data['datum'],
			$data['datum_credit'],
			$data['invoice_date'],
			num_format($data['summa_credit']),
			current_user($iduser),
			current_user($data['iduser'])
		];

	}

	$xls = new Excel_XML('UTF-8', false, 'Payments');
	$xls -> addArray($otchet);
	$xls -> generateXML('Payments');

	exit();
}

//импорт выписки из банка
if ($action == 'import.statement.on') {

	$data          = !empty($_REQUEST['row']) ? array_values($_REQUEST['row']) : [];
	$addContragent = $_REQUEST['addContragent'];
	$setBalance    = $_REQUEST['setBalance'];
	$balance       = (float)pre_format($_REQUEST['balance']);
	$rs            = (int)$_REQUEST['rs'];

	//print_r($data);
	//exit();

	$good = $plus = $minus = 0;
	$err  = $rez = [];

	foreach ($data as $item) {

		$bank = BankStatement ::info($item['id']);

		$clid = (int)$item['clid'];

		//параметры для обновления
		$barg['category'] = (int)$item['category'];

		// добавим контрагента
		if ($addContragent == "yes" && $bank['clid'] < 1) {

			$t    = ($bank['tip'] == 'dohod') ? $bank['from'] : $bank['to'];
			$cinn = ($bank['tip'] == 'dohod') ? $bank['fromINN'] : $bank['toINN'];
			$crs  = ($bank['tip'] == 'dohod') ? $bank['fromRS'] : $bank['toRS'];

			$arg = [
				"title" => $t,
				"type"  => "contractor",
				"recv"  => [
					"castUrName"      => $t,
					"castUrNameShort" => $t,
					"castInn"         => $cinn,
					"castBankRs"      => $crs,
				]
			];

			$client = new Client();
			$a      = $client -> add($arg);

			//привяжем к записи выписки
			if ($a['data'] > 0) {
				$clid = $a['data'];
			}

			elseif ($a['exists'] > 0) {
				$clid = $a['exists'];
			}

			else {
				$err[] = $a['error']['text'];
			}

			if ($clid > 0) {
				$barg['clid'] = $clid;
			}

		}

		// обновим запись в журнале выписок
		BankStatement ::edit($item['id'], $barg);

		// добавим расход в бюджет и проведем его
		$r = BankStatement ::toBudjet($item['id']);

		if ($r['data'] > 0) {

			$rez[] = $r['result'];

			if ($r['tip'] == 'dohod') {
				$plus += $r['summa'];
			}
			else {
				$minus += $r['summa'];
			}

			$good++;

		}
		else {
			$err[] = "Ошибка: ".$r['error']['text'];
		}

		unset($bank);

	}

	//обновим баланс по РС
	if ($setBalance == 'yes' && $rs > 0) {

		$db -> query("UPDATE {$sqlname}mycomps_recv SET ostatok = '$balance' WHERE id = '$rs'");

	}

	print json_encode_cyr([
		"count"  => $good,
		"error"  => !empty($err) ? yimplode("<br>", $err) : 'Ошибок нет',
		"result" => !empty($rez) ? 'Расходы проведены' : 'Расходы Не проведены',
		"plus"   => num_format($plus),
		"minus"  => num_format($minus),
	]);

}

// ручное проведение расхода
if ($action == 'statement.edit') {

	$id = $_REQUEST['id'];

	$statement = BankStatement ::info($id);

	$statement['clid'] = (int)$_REQUEST['clid'] > 0 ? (int)$_REQUEST['clid'] : $statement['clid'];

	// добавим запись в журнал расходов
	$arg = [
		"cat"      => $_REQUEST['cat'],
		"title"    => $_REQUEST['title'],
		"des"      => $_REQUEST['des'],
		"byear"    => $statement['year'],
		"bmon"     => $statement['mon'],
		"summa"    => $statement['summa'],
		"datum"    => $_REQUEST['datum'],
		"do"       => $_REQUEST['do'] == 'on' ? "on" : NULL,
		"rs"       => $statement['rs'],
		"iduser"   => $iduser1,
		"identity" => $identity
	];

	if ($statement['clid'] > 0) {

		$clientTip = getClientData($statement['clid'], 'type');

		if ($clientTip == 'contractor') {
			$arg['conid'] = $statement['clid'];
		}

		elseif ($clientTip == 'partner') {
			$arg['partid'] = $statement['clid'];
		}

	}

	if( $_REQUEST['do'] == 'on' ) {

		$response = Budget ::edit(0, $arg);

		//свяжем расход
		if ($response['data'] > 0) {

			BankStatement ::edit($id, ["bid" => $response['data']]);

			$prvdr = [
				'bid'      => $response['data'],
				"summa"    => $statement['summa'],
				'conid'    => (int)$arg['conid'],
				'partid'   => (int)$arg['partid'],
				"identity" => $identity
			];

			//добавим расход в таблицу dogprovider
			$db -> query("INSERT INTO {$sqlname}dogprovider SET ?u", $prvdr);

		}

	}
	else{

		$arg['clid'] = $statement['clid'];

		$res = BankStatement ::edit($id, $arg);
		if($res > 0){
			$response['result'] = "Изменено";
		}

	}

	//print json_encode_cyr($response);

	print "Результат: ".(!isset($response['error']) ? $response['result'] : $response['error']['text']);

	exit();

}

if ($action == 'getFiles'){

	$type = $_REQUEST['type'];

	$clid = (int)$_REQUEST['clid'];
	$pid  = (int)$_REQUEST['pid'];
	$did  = (int)$_REQUEST['did'];

	$fileSort = $_COOKIE['fileSort'];

	$x = Upload ::cardFiles([
		"clid"     => $clid,
		"pid"      => $pid,
		"did"      => $did,
		"fileSort" => 'desc'
	]);

	$x['list'] = array_values( $x['list'] );

	print json_encode_cyr($x);
	exit();

}