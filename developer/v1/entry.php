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

error_reporting(0);

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/settings.php";
include "../../inc/func.php";

function Cleaner($string){
	$string = trim($string);
	$string = str_replace('"','”',$string);
	$string = str_replace('\n\r','',$string);
	$string = str_replace("'","&acute;",$string);
	return $string;
}

//include "../../opensource/class/safemysql.class.php";

global $identity;

$action = $_REQUEST['action'];//получаем необходимое действие из запроса
$id     = $_REQUEST['id'];//id обращения
$uid    = $_REQUEST['uid'];//uid (внешний id) обращения
$apikey = urldecode($_REQUEST['apikey']);
$login  = urldecode($_REQUEST['login']);
$user   = urldecode($_REQUEST['user']);

$params = array();

foreach($_REQUEST as $key => $value){
	$params[$key] = Cleaner($value);
}

//доступные методы
$aceptedActions = array("list","info","update");
$username = '';
$identity = 0;

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

if($apikey != ''){

	$res = $db -> getRow("select id, api_key, timezone from ".$sqlname."settings where api_key = '".$apikey."'");
	$apikey  = $res['api_key'];
	$identity = $res['id'] + 0;
	$tmzone   = $res['timezone'];

	//$db -> query("SET time_zone = '+".$bdtimezone.":00'");
	date_default_timezone_set($tmzone);

	if($user == '') $user = $login;

	//параметры проверки
	$result   = $db -> getRow("select * from ".$sqlname."user WHERE login = '".$login."'");
	$iduser   = $result['iduser'];
	$username = $result['title'];

}

$Error = ''; $response = array();
$astatus = array('0' => 'Новое', '1' => 'Обработано', '2' => 'Отменено');

//проверяем api-key
if ($identity == 0){

	$response['result'] = 'Error';
	$response['error']['code'] = '400';
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';

}
//проверяем пользователя
elseif($username == ''){

	$response['result'] = 'Error';
	$response['error']['code'] = '401';
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';

}
//проверяем метод
elseif(!in_array($action, $aceptedActions)){

	$response['result'] = 'Error';
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Не известный метод';

	$Error = 'yes';

}

if($Error != 'yes'){


	switch($action){

		case 'info':

			$entry = $db -> getRow("SELECT * FROM ".$sqlname."entry WHERE ide = '$id' and identity = '$identity'");
			//очистим от цифровых индексов
			foreach ($entry as $k => $item){

				if(is_int($k) || $k == 'identity') unset($entry[$k]);

			}

			$entry['products'] = $db -> getAll("SELECT * FROM ".$sqlname."entry_poz WHERE ide = '$id' and identity = '$identity'");
			//очистим от цифровых индексов
			foreach($entry['products'] as $i => $item){

				foreach ($item as $k => $v) {

					if (is_int($k) || $k == 'identity' || $k == 'ide' || $k == 'idp') unset($entry['products'][$i][$k]);

				}

			}

			$response['data'] = $entry;

		break;
		case 'list':

			//задаем лимиты по-умолчанию
			$offset = 0;      if($params['offset'] > 0)     $offset = $params['offset'];
			$order = 'datum'; if($params['order'] != '')    $order = $params['order'];
			$first = 'DESC';  if($params['first'] == 'old') $order = '';
			$limit = 200;
			$sort = '';

			if($params['dateStart'] != '' and $params['dateEnd'] == '') $sort.= " and DATE_FORMAT(".$sqlname."entry.datum, '%y-%m-%d') = '".$params['dateStart']."'";
			if($params['dateStart'] != '' and $params['dateEnd'] != '') $sort.= " and (".$sqlname."entry.datum BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";
			if($params['dateStart'] == '' and $params['dateEnd'] != '') $sort.= " and DATE_FORMAT(".$sqlname."entry.datum, '%y-%m-%d') < '".$params['dateEnd']."'";

			if($params['status'] != '') {

				$sort.= " and ".$sqlname."entry.status IN (".$params['status'].")";

			}

			$lpos = $offset * $limit;
			$j = 0;

			$query = "
				SELECT
					".$sqlname."entry.ide as ide,
					".$sqlname."entry.datum as datum,
					".$sqlname."entry.datum_do as datum_do,
					".$sqlname."entry.pid as pid,
					".$sqlname."entry.clid as clid,
					".$sqlname."entry.did as did,
					".$sqlname."entry.content as content,
					".$sqlname."entry.status as status,
					".$sqlname."entry.iduser as iduser,
					".$sqlname."entry.autor as autor,
					".$sqlname."clientcat.title as client,
					".$sqlname."personcat.person as person,
					".$sqlname."dogovor.title as deal,
					".$sqlname."user.title as user
				FROM ".$sqlname."entry
					LEFT JOIN ".$sqlname."user ON ".$sqlname."entry.iduser = ".$sqlname."user.iduser
					LEFT JOIN ".$sqlname."personcat ON ".$sqlname."entry.pid = ".$sqlname."personcat.pid
					LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."entry.clid = ".$sqlname."clientcat.clid
					LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."entry.did = ".$sqlname."dogovor.did
				WHERE
					".$sqlname."entry.ide > 0
					".$sort."
					and ".$sqlname."entry.identity = '$identity'";

			$result = $db -> query($query." ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)){

				$response['data'][] = array("ide" => $da['ide'], "datum" => $da['datum'], "datum_do" => $da['datum_do'], "content" => $da['content'], "clid" => $da['clid'], "client" => $da['client'], "pid" => $da['pid'], "person" => $da['person'], "did" => $da['did'], "deal" => $da['deal'], "user" => $da['user'], "autor" => current_user($da['autor']), "status" => $da['status'], "statusName" => strtr($da['status'], $astatus));

			}

			if($db -> affectedRows() == 0){
				$response['result'] = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Не найдено";
			}

		break;
		case 'update':

			$id = $db -> getOne("SELECT ide FROM ".$sqlname."entry WHERE (ide = '$params[id]' or uid = '$params[uid]') and identity = '$identity'");

			//проверка, что есть название клиента
			if($id > 0){

				if($params['uid'] > 0) $db -> query("update ".$sqlname."entry set uid = '$params[uid]' where ide = '".$id."' and identity = '$identity'");
				elseif($params['status'] > 0) $db -> query("update ".$sqlname."entry set status = '$params[status]' where ide = '".$id."' and identity = '$identity'");

				$response['result'] = 'Успешно';
				$response['data'] = $id;

			}
			else{

				$response['result'] = 'Error';
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