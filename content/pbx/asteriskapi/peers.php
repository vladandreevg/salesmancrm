<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2024.x          */
/* ============================ */

use Salesman\Leads;

error_reporting(E_ERROR);

$rootpath = dirname(__DIR__, 3);
$thisfile = basename(__FILE__);
$ypath    = __DIR__;

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];

//параметры подключения к серверу
include $ypath."/sipparams.php";
include $ypath."/mfunc.php";

//print $bdtimezone;
//print current_datumtime()."\n";

/**
 * Устанавливаем дату в БД с учетом настроек сервера и смещением для пользователя. старт
 */
$tz  = new DateTimeZone($tmzone);
$dz  = new DateTime();
$dzz = $tz -> getOffset($dz);

$bdtimezone = $dzz / 3600;// + $tzone;

//если значение не корректно (больше 12), то игнорируем смещение временной зоны
if (abs($bdtimezone) > 12) {

	$tzone      = 0;
	$bdtimezone = $dzz / 3600;

}

$bdtimezone = ( $bdtimezone > 0 ) ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '".$bdtimezone.":00'");

if ($action == 'lastcolls') {

	$calls = [];

	$rezult = [
		'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i>',
		'ANSWER'     => '<i class="icon-ok-circled green" title="Отвечен"></i>',
		'NOANSWER'   => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'MISSED'     => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'TRANSFER'   => '<i class="icon-forward-1 gray2" title="Переадресация"></i>',
		'BREAKED'    => '<i class="icon-off red" title="Прервано"></i>',
		'BUSY'       => '<i class="icon-block-1 broun" title="Занято"></i>',
		'CONGESTION' => '<i class="icon-help red" title="Перегрузка канала"></i>',
		'FAILED'     => '<i class="icon-cancel-squared red" title="Ошибка соединения"></i>'
	];

	$direct = [
		'inner'   => '<i class="icon-arrows-cw smalltxt broun" title="Внутренний"></i>',
		'income'  => '<i class="icon-down-big smalltxt green" title="Входящий"></i>',
		'outcome' => '<i class="icon-up-big smalltxt blue" title="Исходящий"></i>'
	];

	$result = $db -> getAll("SELECT * FROM {$sqlname}callhistory WHERE id > 0 and (iduser = '$iduser1' or iduser = '0') and identity = '$identity' GROUP BY uid ORDER BY datum DESC LIMIT 5");
	foreach ($result as $data) {

		$phone = $data['phone'];

		$calls[] = [
			"phone"    => formatPhone2($phone),
			"clid"     => (int)$data['clid'],
			"client"   => current_client($data['clid']),
			"pid"      => (int)$data['pid'],
			"person"   => current_person($data['pid']),
			"time"     => diffDateTime2($data['datum']),
			"icon"     => strtr($data['direct'], $direct),
			"rez"      => strtr(mb_strtoupper($data['res']), $rezult),
			"link"     => formatPhoneUrlIcon($phone, $data['clid'], $data['pid']),
			"dst"      => $data['dst'],
			"did"      => $data['did'],
			"id"       => (int)$data['id'],
			"ismobile" => ( is_mobile($phone) ) ? 1 : "",
			"entry"    => ( $setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ( (int)$data['clid'] > 0 || (int)$data['pid'] > 0 ) ) ? 1 : '',
		];

	}

	print json_encode_cyr($calls);
	exit();

}

//параметры сотрудника
$res      = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser='$iduser1' AND identity = '$identity'");
$title    = $res["title"];
$phone_in = $res["phone_in"];//внутренний номер оператора
$mob      = $res["mob"];

//мониторим входящие
if ($action == 'getIncoming') {

	$peersa     = [];
	$inpeers    = [];
	$peers      = [];
	$start_time = microtime(true);

	//получаем список входящих звонков
	$list = $db -> getAll("
		SELECT
			api.extention,
			api.callid,
			api.datum,
			api.iduser,
			api.phone,
			api.status,
			api.type,
			api.clid,
			cc.title as client,
			api.pid,
			pc.person as person
		FROM {$sqlname}asteriskapi `api`
			LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = api.clid
			LEFT JOIN {$sqlname}personcat `pc` ON pc.pid = api.pid
		WHERE 
		    -- (TIMESTAMPDIFF(SECOND, api.datum, '".current_datumtime()."') ) < 10 AND 
		    api.extention = '$phone_in' AND 
		    api.identity = '$identity' 
		ORDER BY api.datum DESC
	");

	//print $db -> lastQuery();
	//print_r($list);

	foreach ($list as $row) {

		//звонки
		if ($row['status'] == 'INCOMING' || $row['status'] == 'DIALING') {

			//Если модуль "Сборщик заявок" активен
			if ($settingsApp['sipOptions']['autoCreateLead'] == 'yes' && $modLeadActive == 'on') {

				/**
				 * ВНИМАНИЕ!!! Эта опция требует времени на выполнение, поэтому возможна задержка всплытия окна телефонии
				 */

				/**
				 * Создадим заявку, если опция активна
				 */
				$ilead = new Leads();
				$rez   = $ilead -> autoLeadCreate($row['phone']);

			}

			$inpeers[] = [
				"clid"      => (int)$row['clid'],
				"client"    => $row['client'],
				"pid"       => (int)$row['pid'],
				"person"    => $row['person'],
				"phone"     => $row['phone'],
				"extention" => $phone_in,
				//"operator" => $operator['title'],
				"state"     => $row['status'],
				"unknown"   => ( (int)$row['clid'] == 0 && (int)$row['pid'] == 0 && (int)$row['iduser'] == 0 ) ? 1 : '',
				"ismobile"  => ( is_mobile($row['phone']) ) ? 1 : "",
				"entry"     => ( $setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ( (int)$row['clid'] > 0 || (int)$row['pid'] > 0 ) ) ? 1 : '',
				"isuser"    => ( (int)$row['clid'] == 0 && (int)$row['pid'] == 0 && (int)$row['iduser'] > 0 ) ? 1 : ''
			];

		}

		//разговоры
		if ($row['status'] == 'CONNECTED' || $row['status'] == 'ANSWERED') {

			/**
			 * посмотрим в заявках, и если есть, то передадим id заявки
			 */
			//if($modLeadActive == 'on') $lead = $db -> getRow("SELECT id, title FROM {$sqlname}leads WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$status[phone]%' and status NOT IN ('3')");

			$peers[] = [
				"clid"      => (int)$row['clid'],
				"client"    => $row['client'],
				"pid"       => (int)$row['pid'],
				"person"    => $row['person'],
				"phone"     => $row['phone'],
				"state"     => $row['status'],
				"lead"      => (int)$lead['id'],
				"leadtitle" => $lead['title'],
				"unknown"   => ( (int)$row['clid'] == 0 && (int)$row['pid'] == 0 && (int)$row['iduser'] == 0 && (int)$lead['id'] == 0 ) ? 1 : '',
				"ismobile"  => ( is_mobile($status['phone']) ) ? 1 : "",
				"entry"     => ( $setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ( (int)$row['clid'] > 0 || (int)$row['pid'] > 0 ) ) ? 1 : '',
				"isuser"    => ( (int)$row['clid'] == 0 && (int)$row['pid'] == 0 && (int)$row['iduser'] > 0 ) ? 1 : ''
			];

		}

	}

	//смотрим - включен ли модуль Сборщик Лидов
	$coordinator = $db -> getOne("SELECT coordinator FROM {$sqlname}settings WHERE id = '$identity'");

	//установка вывода кнопок добавления обращения или лида
	if ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on') {
		$peersa['entry'] = 1;
	}
	if ($coordinator > 0) {
		$peersa['lead'] = 1;
	}

	$peersa['inpeers'] = $inpeers;
	$peersa['peers']   = $peers;
	//$peersa['debager'] = $resp['call_state'];

	/*
	 * передаем в браузер ответ сервера о звонках и разговорах
	 * отображаются в окне телефонии в разделе "Ответ сервера"
	 * включаем только на время отладки
	 */
	//$peersa['debager'] = $debager;

	$end_time = microtime(true);

	$peersa['time.end'] = round(( $end_time - $start_time ), 3);

	print json_encode_cyr($peersa);

}