<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ERROR);

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/settings.php";
include "../../inc/func.php";
include "../../developer/events.php";

//include "../../opensource/class/safemysql.class.php";

global $identity;

$action = $_REQUEST['action'];//получаем необходимое действие из запроса
$apikey = urldecode($_REQUEST['apikey']);
$login  = urldecode($_REQUEST['login']);
$user   = urldecode($_REQUEST['user']);

//доступные методы
$aceptedActions = array(
	"info",
	"add",
	"stat",
	"list"
);
$username       = '';
$identity       = 0;

$params['title']       = $_REQUEST['title'];
$params['email']       = $_REQUEST['email'];
$params['phone']       = $_REQUEST['phone'];
$params['site']        = $_REQUEST['site'];
$params['company']     = $_REQUEST['company'];
$params['description'] = $_REQUEST['description'];
$params['ip']          = $_REQUEST['ip'];
$params['country']     = $_REQUEST['country'];
$params['city']        = $_REQUEST['city'];
$params['clientpath']  = $_REQUEST['path'];
$params['partner']     = $_REQUEST['partner'];

$params['utm_source']   = $_REQUEST['utm_source'];
$params['utm_medium']   = $_REQUEST['utm_medium'];
$params['utm_campaign'] = $_REQUEST['utm_campaign'];
$params['utm_term']     = $_REQUEST['utm_term'];
$params['utm_content']  = $_REQUEST['utm_content'];
$params['utm_referrer'] = $_REQUEST['utm_referrer'];

$db = new SafeMysql(array(
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8'
));

if ($apikey != '') {

	$res      = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '".$apikey."'");
	$apikey   = $res['api_key'];
	$identity = $res['id'] + 0;
	$tmzone   = $res['timezone'];

	/*
	$tmzone = $db -> getOne("select timezone from " . $sqlname . "settings WHERE id = '$identity'");

	$tz = new DateTimeZone($tmzone);
	$dz = new DateTime();
	$dzz = $tz->getOffset($dz);
	$bdtimezone = intval($dzz)/3600;
	*/

	//$db -> query("SET time_zone = '+".$bdtimezone.":00'");
	date_default_timezone_set($tmzone);

	if ($user == '') $user = $login;

	//параметры проверки
	$result   = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE login = '".$login."'");
	$iduser   = $result['iduser'];
	$username = $result['title'];

}

$Error    = '';
$response = array();

//проверяем api-key
if ($identity == 0) {

	$response['result']        = 'Error';
	$response['error']['code'] = '400';
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';

}

//проверяем пользователя
elseif ($username == '') {

	$response['result']        = 'Error';
	$response['error']['code'] = '401';
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';

}

//проверяем метод
elseif (!in_array($action, $aceptedActions)) {

	$response['result']        = 'Error';
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Не известный метод';

	$Error = 'yes';

}

if ($Error != 'yes') {

	$mdwset       = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'");
	$leadsettings = json_decode($mdwset['content'], true);
	$coordinator  = $leadsettings["leadСoordinator"];
	$users        = $leadsettings['leadOperator'];

	switch ($action) {
		//добавление клиента
		case 'info':

			require_once "../../modules/leads/lsfunc.php";

			$params['identity'] = $identity + 0;
			$id = $_REQUEST['id'];

			//добавляем заявку
			$lead = LeadWorker::info($id, $params);

			$response['result']['data']       = $lead;
			$response['result']['id']         = $id;

		break;
		case 'add':

			require_once "../../modules/leads/lsfunc.php";

			//получим источник и обработаем его
			$params['clientpath'] = getClientpath($params['clientpath'], $params['utm_source']);

			$box      = new LeadWorker();

			//назначам пользователя
			$params['iduser'] = $box -> getUser(array("phone" => $params['phone'], "email" => $params['email'], "identity" => $identity));

			$params['partner']  = (int)get_partnerbysite(untag($params['partner'])) + 0;
			$params['iduser']   = $params['iduser'] + 0;
			$params['datum']    = current_datumtime();
			$params['identity'] = $identity + 0;
			$params['status']   = ($params['iduser'] == 0 || $params['iduser'] == $coordinator) ? 0 : 1;

			//добавляем заявку
			$rez = $box -> edit($params);

			/*if($db -> query("INSERT INTO ".$sqlname."leads (id,datum,status,title,email,phone,company,description,ip,city,country,timezone,iduser,clientpath,pid,clid,partner,utm_source,utm_medium,utm_campaign,utm_term,utm_content,utm_referrer,identity) VALUES (null,'".$params['datum']."', '".$params['status']."','".untag($params['title'])."','".untag($params['email'])."','".untag($params['phone'])."','".untag($params['company'])."','".untag($params['description'])."','".untag($params['ip'])."','".untag($params['city'])."','".$params['country']."','0','".$params['iduser']."','".$params['path']."','".$params['pid']."','".$params['clid']."','".$params['partner']."','".$params['utm_source']."','".$params['utm_medium']."','".$params['utm_campaign']."','".$params['utm_term']."','".$params['utm_content']."','".$params['utm_referrer']."','$identity')")){*/

			if ($rez['result'] == 'Сделано') {

				$response['result']['text']       = 'Success';
				$response['result']['id']         = $rez['id'];
				$response['result']['mailresult'] = $rez['error'];

			}
			else {

				$response['error']['code'] = '1';
				$response['error']['text'] = 'Ошибка добавления лида. '.$rez['error'];
				$response['Error'] = 'Ошибка добавления лида. '.$rez['error'];

			}

		break;
		case 'stat':

			$re = $db -> getAll("SELECT * FROM ".$sqlname."user WHERE secrty = 'yes' and identity = '$identity'");
			foreach ($re as $do) {

				$open = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id>0 and status = '1' and DATE_FORMAT(datum, '%Y-%m') = '".date('Y')."-".date('m')."' and iduser = '".$do['iduser']."' and identity = '$identity'");

				$work = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."leads WHERE id>0 and status IN ('2','3') and DATE_FORMAT(datum_do, '%Y-%m') = '".date('Y')."-".date('m')."' and iduser = '".$do['iduser']."' and identity = '$identity'");

				$response['data'][] = array(
					"title"     => $do['login'],
					"open"      => $open,
					"processed" => $work
				);

			}

		break;
		case 'list':

			$statuses = array(
				'0' => 'Открыт',
				'1' => 'В работе',
				'2' => 'Обработан',
				'3' => 'Закрыт'
			);
			$rezultes = array(
				'1' => 'Спам',
				'2' => 'Дубль',
				'3' => 'Другое',
				'4' => 'Не целевой'
			);

			//задаем лимиты по-умолчанию
			$offset = 0;
			if ($params['offset'] > 0) $offset = $params['offset'];
			$order = 'datum';
			if ($params['order'] != '') $order = $params['order'];
			$first = 'DESC';
			if ($params['first'] == 'old') $order = '';
			$limit = 200;
			$sort  = '';

			if ($params['dateStart'] != '' and $params['dateEnd'] == '') $sort .= " and DATE_FORMAT(".$sqlname."leads.datum, '%y-%m-%d') = '".$params['dateStart']."'";
			if ($params['dateStart'] != '' and $params['dateEnd'] != '') $sort .= " and (".$sqlname."leads.datum BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";
			if ($params['dateStart'] == '' and $params['dateEnd'] != '') $sort .= " and DATE_FORMAT(".$sqlname."leads.datum, '%y-%m-%d') < '".$params['dateEnd']."'";

			if ($params['status'] != '') {

				$sort .= " and ".$sqlname."leads.status IN (".$params['status'].")";

			}

			$lpos = $offset * $limit;
			$j    = 0;

			$query = "
				SELECT
					".$sqlname."leads.id as id,
					".$sqlname."leads.datum as datum,
					".$sqlname."leads.datum_do as datum_do,
					".$sqlname."leads.pid as pid,
					".$sqlname."leads.clid as clid,
					".$sqlname."leads.did as did,
					".$sqlname."leads.description as content,
					".$sqlname."leads.status as status,
					".$sqlname."leads.rezult as rezult,
					".$sqlname."leads.iduser as iduser,
					".$sqlname."leads.clientpath as clientpath,
					".$sqlname."leads.email,
					".$sqlname."leads.phone,
					".$sqlname."leads.site,
					".$sqlname."leads.company,
					".$sqlname."leads.city,
					".$sqlname."leads.country,
					".$sqlname."clientcat.title as client,
					".$sqlname."personcat.person as person,
					".$sqlname."dogovor.title as deal,
					".$sqlname."clientpath.name as clientpathName,
					".$sqlname."user.title as user
				FROM ".$sqlname."leads
					LEFT JOIN ".$sqlname."user ON ".$sqlname."leads.iduser = ".$sqlname."user.iduser
					LEFT JOIN ".$sqlname."personcat ON ".$sqlname."leads.pid = ".$sqlname."personcat.pid
					LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."leads.clid = ".$sqlname."clientcat.clid
					LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."leads.did = ".$sqlname."dogovor.did
					LEFT JOIN ".$sqlname."clientpath ON ".$sqlname."leads.clientpath = ".$sqlname."clientpath.id
				WHERE
					".$sqlname."leads.id > 0
					".$sort."
					and ".$sqlname."leads.identity = '$identity'";

			$result = $db -> query($query." ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)) {

				$response['data'][] = array(
					"id"             => $da['id'],
					"datum"          => $da['datum'],
					"datum_do"       => $da['datum_do'],
					"email"          => $da['email'],
					"phone"          => $da['phone'],
					"site"           => $da['site'],
					"company"        => $da['company'],
					"city"           => $da['city'],
					"country"        => $da['country'],
					"content"        => $da['content'],
					"clid"           => $da['clid'],
					"client"         => $da['client'],
					"pid"            => $da['pid'],
					"person"         => $da['person'],
					"did"            => $da['did'],
					"deal"           => $da['deal'],
					"user"           => $da['user'],
					"userName"       => current_user($da['user']),
					"status"         => $da['status'],
					"statusName"     => strtr($da['status'], $statuses),
					"rezult"         => $da['rezult'],
					"rezultName"     => strtr($da['rezult'], $rezultes),
					"clientpath"     => $da['clientpath'],
					"clientpathName" => $da['clientpathName'],
				);

			}

			//print_r($response);

			if ($db -> affectedRows() == 0) {
				$response['result']        = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Не найдено";
			}

		break;
		default:
			$response['error']['code'] = '404';
			$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
		break;

	}

}

include "logger.php";

print json_encode_cyr($response);
?>