<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

header('Access-Control-Allow-Origin: *');// Устанавливаем возможность отправлять ответ для любого домена или для указанных
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

$params = unserialize(urldecode(serialize($_REQUEST)));

//print_r($params);
//exit();

//доступные методы
$aceptedActions = array("list","addlist","add");

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

//ищем аккаунт по apikey
$result = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '".$params['apikey']."'");
$identity = $result['id'];
$api_key = $result['api_key'];
$timezone= $result['timezone'];

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//найдем пользователя
$result = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '".$params['login']."' and identity = '".$identity."'");
$iduser = $result['iduser'];
$username = $result['title'];

$Error = ''; $response = array();


if(!$identity){//проверяем api-key
	$response['result'] = 'Error';
	$response['error']['code'] = '400';
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';
}
elseif(!$username){//проверяем пользователя
	$response['result'] = 'Error';
	$response['error']['code'] = '401';
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';
}
elseif(!in_array($params['action'], $aceptedActions)){//проверяем метод
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';
}

if($Error != 'yes'){

	//составляем списки доступных полей
	$ifields = array('id', 'datum', 'direct', 'res', 'sec', 'did', 'src', 'dst', 'file');

	//задаем лимиты по-умолчанию
	$offset = 0; if($params['offset'] > 0) $offset = $params['offset'];
	$order = 'datum';
	$first = 'DESC'; if($params['first'] == 'old') $first = '';
	$limit = 200;
	$sort = '';

	switch($params['action']){

		case 'list':

			if($params['operator'] != '') {

				$result = $db -> getRow("SELECT phone, phone_in, mob, iduser FROM ".$sqlname."user WHERE login = '".$params['operator']."' and identity = '".$identity."'");
				$operator = $result['iduser'];
				$sort.= " and iduser = '".$operator."'";

			}
			if($params['direct'] != '') $sort.= " and direct = '".$params['direct']."'";

			if($params['phone'] != '') $sort.= " and (dst LIKE '%".preparePhone($params['phone'])."%' or src LIKE '%".preparePhone($params['phone'])."%' or phone LIKE '%".preparePhone($params['phone'])."%')";

			if($params['dateStart'] != '' and $params['dateEnd'] == '') $sort.= " and datum > '".$params['dateStart']."'";
			if($params['dateStart'] != '' and $params['dateEnd'] != '') $sort.= " and (datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
			if($params['dateStart'] == '' and $params['dateEnd'] != '') $sort.= " and datum < '".$params['dateEnd']."'";

			$lpos = $offset * $limit;

			$result = $db -> query("SELECT * FROM ".$sqlname."callhistory WHERE id > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)){

				$response['data'][] = array("id" => $da['id'], "datum" => $da['datum'], "direct" => $da['direct'], "res" => $da['res'], "sec" => $da['sec'], "src" => $da['src'], "dst" => $da['dst'], "file" => $da['file']);

				$j++;

			}

		break;
		case 'add':

			for($i=0; $i < count($params['calls']); $i++){

				if($params['calls'][$i]['src'] == '' and $params['calls'][$i]['dst'] == '') {

					$response[$i]['result'] = 'Error';
					$response[$i]['error']['code'] = '405';
					$response[$i]['error']['text'] = "Отсутствуют параметры - номера оператора и абонента";

				}
				else{

					if($params['calls'][$i]['direct'] == 'income') {//входящий

						$callerID = getCallerID($params['calls'][$i]['src']);
						$clid 	= $clientID;
						$pid 	= $personID;
						$iduser = $userID;

					}
					if($params['calls'][$i]['direct'] == 'outcome') {//исходящий

						$callerID = getCallerID($params['calls'][$i]['dst']);
						$clid 	= $clientID;
						$pid 	= $personID;
						$iduser = $userID;

					}

					try {

						$db -> query("insert into ".$sqlname."callhistory (id,uid,direct,did,datum,clid,pid,iduser,res,sec,src,dst,file,identity) values(null,'".$params['calls'][$i]['uid']."','".$params['calls'][$i]['direct']."','".$params['calls'][$i]['did']."','".$params['calls'][$i]['datum']."','".$clid."','".$pid."','".$iduser."','".$params['calls'][$i]['res']."','".$params['calls'][$i]['sec']."','".$params['calls'][$i]['src']."','".$params['calls'][$i]['dst']."','".$params['calls'][$i]['file']."','".$identity."')");

						$response[$i]['result'] = 'Успешно';
						$response[$i]['text'] = 'Звонок '.$params['calls'][$i]['src'].' -> '.$params['calls'][$i]['dst'].' записан в историю звонков';

					}
					catch (Exception $e){

						$response[$j]['result'] = 'Error';
						$response[$j]['error']['code'] = '500';
						$response[$j]['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

					}


				}

			}

		break;
		case 'update':

		break;
		default:

				$response['error']['code'] = '404';
				$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';

		break;
	}

}

print $rez = json_encode_cyr($response);

include "logger.php";
?>