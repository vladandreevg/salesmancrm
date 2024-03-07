<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Leads;

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

//параметры подключения к серверу
require_once dirname( __DIR__)."/onlinepbx/sipparams.php";
require_once dirname( __DIR__)."/onlinepbx/mfunc.php";

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

$bdtimezone = ($bdtimezone > 0) ? "+".abs($bdtimezone) : "-".abs($bdtimezone);

$db -> query("SET time_zone = '".$bdtimezone.":00'");

if ($action == 'lastcolls') {

	$calls = [];

	$rezult = [
		'ANSWERED'   => '<i class="icon-ok-circled green" title="Отвечен"></i>',
		'NOANSWER'   => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'NO ANSWER'  => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
		'missed'     => '<i class="icon-minus-circled red" title="Не отвечен"></i>',
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
			"rez"      => strtr($data['res'], $rezult),
			"link"     => formatPhoneUrlIcon($phone, $data['clid'], $data['pid']),
			"dst"      => $data['dst'],
			"did"      => $data['did'],
			"id"       => (int)$data['id'],
			"ismobile" => (is_mobile($phone)) ? 1 : "",
			"entry"    => ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ($data['clid'] > 0 || $data['pid'] > 0)) ? 1 : '',
		];

	}

	print json_encode_cyr($calls);

	exit();

}

//$numoutLength = strlen($sip_numout);

//параметры сотрудника
$res      = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser='$iduser1' AND identity = '$identity'");
$title    = $res["title"];
$phone_in = $res["phone_in"];//внутренний номер оператора
$mob      = $res["mob"];


function parseRespp($num) {
	$chan  = explode("-", $num);
	$chan1 = explode("/", $chan[0]);
	//$chan1 = preparePhone($chan[0]);
	return preparePhone( $chan1[1]);
}

function getCallerID2($phone, $shownum = false, $translit = false) {

	$rootpath = dirname( __DIR__, 3 );

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";
	include $rootpath."/inc/func.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];

	global $clientID;
	global $clientTitle;
	global $personID;
	global $personTitle;
	global $userID;
	global $userTitle;
	global $phoneIN;
	//global $sqlname;
	//global $identity;

	$result_sip   = $db -> getRow("SELECT * FROM {$sqlname}sip WHERE identity = '".$identity."'");
	$sip_channel  = $result_sip["sip_channel"];
	$sip_numout   = $result_sip["sip_numout"];
	$sip_pfchange = $result_sip["sip_pfchange"];

	$numoutLength = strlen($sip_numout);

	if (strlen($phone) < 6) {//для внутренних номеров

		$phone = str_replace($sip_channel, "", $phone);

		$result    = $db -> getRow("SELECT iduser,title,phone_in FROM {$sqlname}user WHERE ({$sqlname}user.phone_in = '$phone' OR replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%') AND {$sqlname}user.identity = '$identity'");
		$callerID  = $result["title"];
		$userTitle = $result["title"];
		$userID    = (int)$result["iduser"];
		$phoneIN   = $result["phone_in"];

		if (!$callerID) $callerID = 'Unknown';
		if ( $shownum ) $callerID = ''.$callerID;
		if ( $translit ) $callerID = translit($callerID);

	}
	else {

		if (strlen($phone) == 11 || strlen($phone) == 8) $phone1 = substr($phone, 1);
		else $phone1 = $phone;

		$num = prepareMobPhone($phone1);

		//ищем оператора
		$result = $db -> getRow("
			SELECT 
				iduser,title,phone_in, phone, mob 
			FROM {$sqlname}user 
			WHERE 
				(
					{$sqlname}user.phone_in = '$phone' OR 
					replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' OR 
					replace(replace(replace(replace(replace({$sqlname}user.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%'
				) AND 
				{$sqlname}user.identity = '$identity'
			");

		$callerID  = $result["title"];
		$userTitle = $result["title"];
		$userID    = (int)$result["iduser"];
		$userTitle = $result["user"];
		$phoneIN   = $result["phone_in"];

		if ($callerID != '') goto res;

		//ищем контакт
		$res = $db -> getRow("
			SELECT
				{$sqlname}personcat.person as person,
				{$sqlname}personcat.pid as pid,
				{$sqlname}clientcat.clid as clid,
				{$sqlname}clientcat.title as title,
				{$sqlname}user.iduser as iduser,
				{$sqlname}user.title as user,
				{$sqlname}user.phone_in as phone_in
			FROM {$sqlname}personcat
				LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}personcat.clid = {$sqlname}clientcat.clid
			WHERE 
				(
					(replace(replace(replace(replace(replace({$sqlname}personcat.tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%') or 
					(replace(replace(replace(replace(replace({$sqlname}personcat.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%')
				) and 
				{$sqlname}personcat.identity = '$identity'
			ORDER by {$sqlname}personcat.pid DESC LIMIT 1
		");

		$callerID    = $res["person"];
		$personID    = (int)$res["pid"];
		$personTitle = $callerID;
		$clientID    = (int)$res["clid"];
		$clientTitle = $res["title"];
		$userID      = (int)$res["iduser"];
		$userTitle   = $res["user"];
		$phoneIN     = $res["phone_in"];

		if ($callerID != '') goto res;

		//ищем в клиентах
		$res = $db -> getRow("
			SELECT
			{$sqlname}clientcat.clid as clid,
			{$sqlname}clientcat.pid as pid,
			{$sqlname}clientcat.title as title,
			{$sqlname}user.iduser as iduser,
			{$sqlname}user.title as user,
			{$sqlname}user.phone_in as phone_in
			FROM {$sqlname}clientcat
				LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
			WHERE 
				(
					replace(replace(replace(replace(replace({$sqlname}clientcat.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%' OR 
					replace(replace(replace(replace(replace({$sqlname}clientcat.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%'
				) and 
				{$sqlname}clientcat.identity = '$identity'
			ORDER by {$sqlname}clientcat.clid DESC LIMIT 1
		");

		$callerID    = $res["title"];
		$clientID    = (int)$res["clid"];
		$personID    = (int)$res["pid"];
		$personTitle = current_person($personID);
		$clientTitle = $callerID;
		$userID      = (int)$res["iduser"];
		$userTitle   = $res["user"];
		$phoneIN     = $res["phone_in"];

		res:

		if (!$callerID) $callerID = "Not found";
		if ( $shownum ) $callerID = ''.$callerID.' <'.preparePhone($phone).'>';
		if ( $translit ) $callerID = translit($callerID);

	}

	return $callerID;
}

//мониторим входящие
if ($action == 'getIncoming') {

	$peersa     = [];
	$inpeers    = [];
	$peers      = [];
	$start_time = microtime(true);

	//$statuses = $db -> getAll("SELECT * FROM {$sqlname}onlinepbx_log WHERE (TIMESTAMPDIFF(SECOND, datum, CURRENT_TIMESTAMP) ) < 10 AND extension = '$phone_in' AND identity = '$identity' ORDER BY datum");

	$statuses = $db -> getAll("SELECT * FROM {$sqlname}onlinepbx_log WHERE (TIMESTAMPDIFF(SECOND, datum, CURRENT_TIMESTAMP) ) < 100 AND status IN ('INCOMING','CALLING','ACCEPTED','COMPLETED','ANSWERED') AND LENGTH(phone) > 5 AND extension = '$phone_in' AND identity = '$identity' ORDER BY datum");


	foreach ($statuses as $status) {

		$icon      = ($status['type'] == 'out') ? '<i class="icon-up-outline green"></i>' : '<i class="icon-down-outline red"></i>';
		$direction = ($status['type'] == 'out') ? 'исходящий' : 'входящий';

		//звонки
		if (in_array(strtoupper($status['status']), [
			'INCOMING',
			'CALLING'
		])) {

			//Если модуль "Сборщик заявок" активен
			if ($modLeadActive == 'on') {

				/**
				 * ВНИМАНИЕ!!! Эта опция требует времени на выполнение, поэтому возможна задержка всплытия окна телефонии
				 */
				if ($settingsApp['sipOptions']['autoCreateLead'] == 'yes') {

					/**
					 * Создадим заявку, если опция активна
					 */
					//require_once "../../modules/leads/lsfunc.php";

					$ilead = new Leads();
					$rez   = $ilead -> autoLeadCreate($status['phone']);

				}

				/**
				 * посмотрим в заявках, и если есть, то передадим id заявки
				 */
				if ($modLeadActive == 'on') {
					$lead = ($tip != 'ring') ? $db -> getRow( "SELECT id, title FROM {$sqlname}leads WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$status[phone]%' and status NOT IN ('3')" ) : '';
				}

			}

			$inpeers[] = [
				"clid"      => (int)$status['clid'],
				"client"    => current_client($status['clid']),
				"pid"       => (int)$status['pid'],
				"person"    => current_person($status['pid']),
				"phone"     => $status['phone'],
				"state"     => $status['status'],
				"icon"      => $icon,
				"direction" => $direction,
				"unknown"   => ((int)$status['clid'] == 0 && (int)$status['pid'] == 0 && (int)$status['iduser'] == 0) ? 1 : '',
				"ismobile"  => (is_mobile($status['phone'])) ? 1 : "",
				"entry"     => ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ((int)$status['clid'] > 0 || (int)$status['pid'] > 0)) ? 1 : '',
				"isuser"    => ((int)$status['clid'] == 0 && (int)$status['pid'] == 0 && (int)$status['iduser'] > 0) ? 1 : '',

			];

		}

		//разговоры
		elseif (in_array(strtoupper($status['status']), [
			'ACCEPTED',
			'COMPLETED',
			'ANSWERED'
		])) {

			/**
			 * посмотрим в заявках, и если есть, то передадим id заявки
			 */
			$lead = $db -> getRow("SELECT id, title FROM {$sqlname}leads WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$status[phone]%' and status NOT IN ('3')");

			$peers[] = [
				"clid"      => (int)$status['clid'],
				"client"    => current_client($status['clid']),
				"pid"       => (int)$status['pid'],
				"person"    => current_person($status['pid']),
				"phone"     => $status['phone'],
				"state"     => $status['status'],
				"icon"      => $icon,
				"direction" => $direction,
				"lead"      => (int)$lead['id'],
				"leadtitle" => $lead['title'],
				"unknown"   => ((int)$status['clid'] == 0 && (int)$status['pid'] == 0 && (int)$status['iduser'] == 0 && (int)$lead['id'] == 0) ? 1 : '',
				"ismobile"  => (is_mobile($status['phone'])) ? 1 : "",
				"entry"     => ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on' && ((int)$status['clid'] > 0 || (int)$status['pid'] > 0)) ? 1 : '',
				"isuser"    => ((int)$status['clid'] == 0 && (int)$status['pid'] == 0 && (int)$status['iduser'] > 0) ? 1 : ''
			];

		}

	}

	//смотрим - включен ли модуль Сборщик Лидов
	$coordinator = $db -> getOne("SELECT coordinator FROM {$sqlname}settings WHERE id = '$identity'");

	//установка вывода кнопок добавления обращения или лида
	if ($setEntry['enShowButtonCall'] == 'yes' && $isEntry == 'on') $peersa['entry'] = 1;
	if ($coordinator > 0) $peersa['lead'] = 1;

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

	$peersa['time.end'] = round(($end_time - $start_time), 3);

	print json_encode_cyr($peersa);

}