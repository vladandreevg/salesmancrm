<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

use Salesman\Document;

header('Access-Control-Allow-Origin: *');// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ERROR);
ini_set( 'display_errors', 1 );

set_time_limit(300);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

function Cleaner($string) {

	$string = trim($string);
	$string = str_replace( [
		'"',
		'\n\r',
		"'"
	], [
		'”',
		'',
		"&acute;"
	], $string );

	return $string;

}

$headers = getallheaders();

/**
 * Принимаем в формате JSON
 */
if($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);

	$APIKEY = array_key_exists( 'apikey', $headers) ? $headers['apikey'] : $headers['Apikey'];
	$LOGIN  = array_key_exists( 'login', $headers) ? $headers['login'] : $headers['Login'];

}

/**
 * Если это GET-запрос или отправка формы
 */
else {

	$params = [];
	foreach ($_REQUEST as $key => $value) {
		$params[ $key ] = (!is_array( $value )) ? Cleaner( $value ) : $value;
	}

	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];

}

if( is_null($APIKEY) && !is_null($params['apikey'])){
	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];
}

//доступные методы
$aceptedActions = [
	"tips",
	"statuses",
	"list",
	"info",
	"add",
	"update",
	"delete",
	"status.change",
	"mail"
];

$typea = [
	"get_dogovor" => "Договор",
	"get_akt"     => "Акт приема-передачи",
	"get_aktper"  => "Акт ежемесячный"
];

$db = new SafeMysql([
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8',
	'errmode' => 'exception'
]);

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone FROM {$sqlname}settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

global $identity;

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM {$sqlname}user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = (int)$result['iduser'];
$username = $result['title'];
$iduser1  = (int)$result['iduser'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//require_once "../../modules/contract/Docgen.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

$Error    = '';
$response = [];

//проверяем api-key
if ($identity == 0) {

	$response['result']        = 'Error';
	$response['error']['code'] = 400;
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';

}

//проверяем пользователя
elseif (empty($username)) {

	$response['result']        = 'Error';
	$response['error']['code'] = 401;
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';

}

//проверяем метод
elseif (!in_array($params['action'], $aceptedActions)) {

	$response['result']        = 'Error';
	$response['error']['code'] = 402;
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') {
	goto ext;
}


$set     = $db -> getRow("select * from {$sqlname}settings WHERE id = '$identity'");
$inum    = $set["contract_num"];
$iformat = $set["contract_format"];

switch ($params['action']) {

	case 'tips':

		$re = $db -> query("SELECT * FROM {$sqlname}contract_type WHERE type NOT IN ('get_akt','get_aktper') AND identity = '$identity' ORDER by title");
		while ($do = $db -> fetch($re)) {

			$templates = [];

			$res = $db -> query("SELECT * FROM {$sqlname}contract_temp WHERE typeid = '$do[id]' AND identity = '$identity'");
			while ($da = $db -> fetch($res)) {

				$templates[] = [
					"id"    => (int)$da['id'],
					"title" => $da['title'],
					"file"  => $da['file']
				];

			}

			$response['data'][] = [
				"id"        => (int)$do['id'],
				"title"     => $do['title'],
				"type"      => ($do['type'] != '') ? strtr($do['type'], $typea) : 'Собственный',
				"role"      => $do['role'],
				"users"     => $do['users'],
				"number"    => ($do['type'] == 'get_dogovor') ? $inum : $do['num'],
				"format"    => ($do['type'] == 'get_dogovor') ? $iformat : $do['format'],
				"templates" => $templates
			];

		}

	break;

	case 'statuses':

		$tips = $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}contract_type WHERE identity = '$identity' ORDER BY title");

		$result = $db -> getAll("SELECT * FROM {$sqlname}contract_status WHERE identity = '$identity' ORDER by ord");
		foreach ($result as $da) {

			$idtype = [];

			$t = yexplode(";", $da['tip']);
			foreach ($t as $tip) {
				$idtype[ $tip ] = strtr( $tip, $tips );
			}

			$response['data'][] = [
				"id"    => (int)$da['id'],
				"title" => $da['title'],
				"color" => $da['color'],
				"type"  => $idtype
			];

		}

	break;

	case 'info':

		$deid = (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'");

		if ($deid < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = '404';
			$response['error']['text'] = "Документ не найден";

		}
		elseif ($deid > 0) {

			$res = Document ::info($deid);

			$files = [];
			foreach ($res['files'] as $file) {

				$files[] = [
					"name" => $file['name'],
					"file" => $productInfo['crmurl']."/files/".$fpath.$file['file']
				];

			}

			$document = [
				"deid"        => (int)$res['deid'],
				"datum"       => $res['datum'],
				"number"      => $res['number'],
				"title"       => $res['title'],
				"date.start"  => $res['datum_start'],
				"date.end"    => $res['datum_end'],
				"typeDoc"     => $db -> getOne("SELECT title FROM {$sqlname}contract_temp WHERE typeid = '$res[idtype]' AND identity = '$identity'"),
				"idtype"      => (int)$res['idtype'],
				"description" => $res['des'],
				"clid"        => (int)$res['clid'],
				"clientTitle" => current_client($res['clid']),
				"payer"       => (int)$res['payer'],
				"payerTitle"  => current_client($res['payer']),
				"did"         => (int)$res['did'],
				"dealTitle"   => current_dogovor($res['did']),
				"mcid"        => (int)$res['mcid'],
				"signer"      => (int)$res['signer'],
				"status"      => (int)$res['status'],
				"statusTitle" => $db -> getOne("SELECT title FROM {$sqlname}contract_status WHERE id = '$res[status]' and identity = '$identity'"),
				"files"       => $files
			];

			$response['data'] = $document;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Документ не найден";

		}

	break;

	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ((int)$params['offset'] > 0) ? (int)$params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'datum';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		$type = yexplode(",", $params['idtype']);

		if ($params['user'] != '') {
			$iduser = current_userbylogin( $params['user'] );
		}

		if ($params['word'] != '') {
			$sort .= " and ({$sqlname}contract.des LIKE '%".Cleaner( $params['word'] )."%' or {$sqlname}contract.number LIKE '%".Cleaner( $params['word'] )."%' or {$sqlname}contract.title LIKE '%".Cleaner( $params['word'] )."%')";
		}

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and {$sqlname}contract.datum > '".$params['dateStart']."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and ({$sqlname}contract.datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and {$sqlname}contract.datum < '".$params['dateEnd']."'";
		}

		if ($params['user'] != '') {
			$sort .= " and {$sqlname}contract.iduser = '".current_userbylogin( $params['user'] )."'";
		}
		else {
			$sort .= " and {$sqlname}contract.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		if ($params['clid'] > 0) {
			$sort .= " and {$sqlname}contract.clid = '".$params['clid']."'";
		}
		if ($params['did'] > 0) {
			$sort .= " and {$sqlname}contract.did = '".$params['did']."'";
		}

		if (!empty($type)) {
			$sort .= " and {$sqlname}contract.idtype IN (".yimplode( ",", $type ).")";
		}
		else {
			$sort .= " and ({$sqlname}contract.idtype IN (SELECT id FROM {$sqlname}contract_type WHERE type NOT IN ('get_akt','get_aktper') and identity = '$identity') or {$sqlname}contract.idtype = 0)";
		}

		$lpos = $offset * $limit;

		$query = "
			SELECT 
				{$sqlname}contract.deid,
				{$sqlname}contract.datum,
				{$sqlname}contract.datum_end,
				{$sqlname}contract.datum_start,
				{$sqlname}contract.number,
				{$sqlname}contract.title,
				{$sqlname}contract.clid,
				{$sqlname}contract.pid,
				{$sqlname}contract.did,
				{$sqlname}contract.payer,
				{$sqlname}contract.idtype,
				{$sqlname}contract.status as status,
				{$sqlname}contract_status.title as tstatus,
				{$sqlname}contract_status.color as color
			FROM {$sqlname}contract 
				LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = {$sqlname}contract.status
			WHERE 
				{$sqlname}contract.deid > 0 
				$sort and 
				{$sqlname}contract.identity = '$identity' 
			ORDER BY $order $first LIMIT $lpos,$limit
		";

		$result = $db -> query($query);
		while ($da = $db -> fetch($result)) {

			//статусы, применимые к текущему типу документоа
			$stat = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity'");

			$response['data'][] = [
				"id"          => (int)$da['deid'],
				"datum"       => format_date_rus(get_smdate($da['datum'])),
				"date.start"  => format_date_rus($da['datum_start']),
				"date.end"    => format_date_rus($da['datum_end']),
				"number"      => $da['number'],
				"title"       => $da['title'],
				"clid"        => (int)$da['clid'],
				"client"      => current_client($da['clid']),
				"did"         => (int)$da['did'],
				"deal"        => current_dogovor($da['did']),
				"payerid"     => (int)$da['payer'],
				"payer"       => current_client($da['payer']),
				"mcid"        => (int)$da['mcid'],
				"signer"      => (int)$res['signer'],
				"idtype"      => (int)$da['idtype'],
				"typeDoc"     => $db -> getOne("SELECT title FROM {$sqlname}contract_temp WHERE typeid = '$da[idtype]' AND identity = '$identity'"),
				"status"      => $da['status'],
				"statusTitle" => ($da['tstatus'] != '') ? $da['tstatus'] : "--",
				"statusColor" => ($da['tstatus'] != '') ? $da['color'] : "#fff",
			];

		}

		$query = "
			SELECT 
				COUNT(*)
			FROM {$sqlname}contract 
				LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = {$sqlname}contract.status
			WHERE 
				{$sqlname}contract.deid > 0 
				$sort and 
				{$sqlname}contract.identity = '$identity'
		";
		$count = (int)$db -> getOne($query);

		$response['count'] = $count;

	break;

	case 'add':

		$params['iduser'] = ($params['user'] == '') ? (int)$iduser : current_userbylogin($params['user']);

		$params['clid']  = (isset($params['clid'])) ? (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE clid = '".$params['clid']."' and identity = '$identity'") : 0;
		$params['payer'] = (isset($params['payer'])) ? (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE clid = '".$params['payer']."' and identity = '$identity'") : 0;
		$params['did']   = (isset($params['did'])) ? (int)$db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE did = '".$params['did']."' and identity = '$identity'") : 0;

		if (!isset($params['clid']) && !isset($params['payer']) && !isset($params['did'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют или не найдены записи - clid, payer или did, они должны быть указаны в запросе";

		}
		elseif ($params['clid'] == 0 && $params['payer'] == 0 && $params['did'] == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Не найдено записей Клиента или Сделки";

		}
		elseif ($params['did'] == 0 && !isset($params['mcid'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Если не указан did сделки, то должен быть указан mcid - идентификатор компании";

		}
		else {

			$params['des']         = $params['description'];
			$params['datum_start'] = $params['dateStart'];
			$params['datum_end']   = $params['dateEnd'];

			$document = new Document();

			//print_r($params);

			/**
			 * добавляем документ
			 */
			$res  = $document -> edit(0, $params);
			$deid = (int)$res['id'];

			if ($deid > 0) {

				if ((int)$params['template'] > 0) {

					/**
					 * генерируем файл по шаблону
					 */
					$arg  = [
						"template" => (int)$params['template'],
						"append"   => true,
						"getPDF"   => ($params['pdf']) ? "yes" : "no"
					];
					$temp = $document -> generate($res['id'], $arg);

				}

				$f = Document ::info($deid);

				$files = [];
				foreach ($f['files'] as $file) {

					$files[] = [
						"name" => $file['name'],
						"file" => $productInfo['crmurl']."/files/".$fpath.$file['file']
					];

				}

				$response['result'] = "Успешно: ".yimplode(";", $res['message']);
				if ($temp != '') {
					$response['text'] = $temp;
				}
				$response['data'] = $deid;
				if (!empty($files)) {
					$response['files'] = $files;
				}
				$response['number'] = $f['number'];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 405;
				$response['error']['text'] = $res['error'];

			}

		}

	break;

	case 'update':

		$params['iduser'] = ($params['user'] == '') ? (int)$iduser : current_userbylogin($params['user']);

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			if (isset($params['description'])) {
				$params['des'] = $params['description'];
			}
			if (isset($params['dateStart'])) {
				$params['datum_start'] = $params['dateStart'];
			}
			if (isset($params['dateEnd'])) {
				$params['datum_end'] = $params['dateEnd'];
			}

			$document = new Document();

			//print_r($params);

			/**
			 * добавляем документ
			 */
			$res = $document -> edit($deid, $params);

			if ($deid > 0) {

				$of = Document ::info($deid);

				if ((int)$params['template'] > 0) {

					/**
					 * Удалим старые файлы
					 */
					foreach ($of['files'] as $file) {

						unlink("../../files/".$fpath.$file['file']);

					}
					$db -> query("UPDATE {$sqlname}contract SET ftitle = '', fname = '', ftype = '' WHERE deid = '$deid' and identity = '$identity'");

					/**
					 * генерируем файл по шаблону
					 */
					$arg  = [
						"template" => $params['template'],
						"append"   => true,
						"getPDF"   => ($params['pdf']) ? "yes" : "no"
					];
					$temp = $document -> generate((int)$res['id'], $arg);

				}

				$f = Document ::info($deid);

				$files = [];
				foreach ($f['files'] as $file) {

					$files[] = [
						"name" => $file['name'],
						"file" => $productInfo['crmurl']."/files/".$fpath.$file['file']
					];

				}

				$response['result'] = "Успешно: ".yimplode(";", $res['message']);
				if ($temp != '') {
					$response['text'] = $temp;
				}
				$response['data'] = $deid;
				if (!empty($files)) {
					$response['files'] = $files;
				}
				$response['number'] = $f['number'];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 405;
				$response['error']['text'] = $res['error'];

			}

		}

	break;

	case 'status.change':

		$params['iduser'] = ($params['user'] == '') ? (int)$iduser : (int)current_userbylogin($params['user']);

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			$oldstatus = (int)$db -> getOne("select status from {$sqlname}contract WHERE deid = '$deid' and identity = '$identity'");

			$document = new Document();

			$arg = [
				"status"    => (int)$params['status'],
				'oldstatus' => (int)$oldstatus,
				"user"      => (int)$params['iduser'],
				"statusdes" => $params['description'],
			];

			/**
			 * обновляем статус документа
			 */
			$res = $document -> edit($deid, $arg);

			if ((int)$res['id'] > 0) {

				$response['result'] = "Успешно: ".yimplode(";", $res['message']);
				$response['data'] = $deid;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $res['error'];

			}

		}

	break;

	case 'delete':

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			if (isset($params['description'])) {
				$params['des'] = $params['description'];
			}
			if (isset($params['dateStart'])) {
				$params['datum_start'] = $params['dateStart'];
			}
			if (isset($params['dateEnd'])) {
				$params['datum_end'] = $params['dateEnd'];
			}

			$document = new Document();

			/**
			 * удаляем документ
			 */
			$res = $document -> delete($deid);

			$response['result'] = yimplode(";", $res['message']);
			$response['data']   = $deid;
			if ($res['error'] != '') {
				$response['error'] = $res['error'];
			}

		}

	break;

	case 'mail':

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			$document = new Document();

			/**
			 * удаляем документ
			 */
			$res = $document -> mail($deid, $params, true);

			//print_r($res);

			$response['result'] = ($res['text'] != '') ? $res['result'].": ".$res['text'] : $res['result'];
			$response['data']   = $deid;
			if ($res['error'] != '') {
				$response['error'] = $res['error'];
			}

		}

	break;

	default:

		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';

	break;

}


ext:

$code = $response['error']['code'] ?? 200;
//HTTPStatus($code);

print $rez = json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";