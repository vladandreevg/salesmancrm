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

/*if(!file_exists("./yoolla.log")) {
	$file = fopen("./yoolla.log", "w");
	fclose($file);
}
ini_set('log_errors', 'On');
ini_set('error_log', './yoolla.log');*/

function Cleaner($string){
	$string = trim($string);
	$string = str_replace('"','”',$string);
	$string = str_replace('\n\r','',$string);
	$string = str_replace("'","&acute;",$string);
	return $string;
}

foreach($_REQUEST as $key => $value){
	$params[$key] = Cleaner($value);
}

//доступные методы
$aceptedActions = array("tips","fields","list","info","add","delete");

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

//поля
$fieldsname = array("сid","datum","des","title","des","tip","iduser","clid","pid","did");
$fields = array("cid" => "Идентификатор записи","datum" => "Дата","des" => "Содержание","tip" => "Тип напоминания","iduser" => "Ответственный", "clid" => "ID клиента", "pid" => "ID контакта (массив)", "did" => "ID сделки");

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

//проверяем api-key
if(!$identity){
	$response['result'] = 'Error';
	$response['error']['code'] = '400';
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';
}
//проверяем пользователя
elseif(!$username){
	$response['result'] = 'Error';
	$response['error']['code'] = '401';
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';
}
//проверяем метод
elseif(!in_array($params['action'], $aceptedActions)){
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';
}

if($Error != 'yes'){

	//задаем лимиты по-умолчанию
	$offset = 0; if($params['offset'] > 0) $offset = $params['offset'];
	$order = 'datum'; if($params['order'] != '') $order = $params['order'];
	$first = 'DESC'; if($params['first'] == 'old') $order = '';
	$active = 'yes'; if($params['active'] != '') $active = $params['active'];
	$limit = 200;
	$sort = '';

	switch($params['action']){

		case 'fields':

			foreach($fields as $key => $val){
				$response['data'][$key] = $val;
			}

		break;

		case 'tips':

			$re = $db -> query("SELECT * FROM ".$sqlname."activities WHERE identity = '$identity'");
			while ($do = $db -> fetch($re)){
				$response['data'][] = array("title" => $do['title'],"color" => $do['color']);
			}

		break;
		case 'list':

			if($params['dateStart'] == '') $params['dateStart'] = current_date();
			if($params['dateEnd'] == '') $params['dateEnd'] = current_date();

			$sort.= get_people($iduser);

			if($params['word'] != '') $sort.= " and (title LIKE '%".Cleaner($params['word'])."%' or des LIKE '%".Cleaner($params['word'])."%' or tip LIKE '%".Cleaner($params['word'])."%')";

			if($params['tip'] != '' and in_array($params['tip'],$fieldsname)) $sort.= " and tip = '".Cleaner($params['tip'])."'";

			if($params['dateStart'] != '' and $params['dateEnd'] == '') $sort.= " and datum = '".$params['dateStart']."'";
			if($params['dateStart'] != '' and $params['dateEnd'] != '') $sort.= " and (datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
			if($params['dateStart'] == '' and $params['dateEnd'] != '') $sort.= " and datum < '".$params['dateEnd']."'";

			$lpos = $offset * $limit;
			$j = 0;

			$result = $db -> query("SELECT * FROM ".$sqlname."history WHERE cid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)){

				for($i=0;$i < count($fieldsname);$i++){

					switch($fieldsname[$i]){

						case 'clid':
							$response['data'][$j][$fieldsname[$i]] = $da[$fieldsname[$i]];
							$response['data'][$j]['client'] = current_client($da[$fieldsname[$i]]);
						break;
						case 'pid':
							$response['data'][$j][$fieldsname[$i]] = $da[$fieldsname[$i]];
							$response['data'][$j]['person'] = current_person($da[$fieldsname[$i]]);
						break;
						case 'did':
							$response['data'][$j][$fieldsname[$i]] = $da[$fieldsname[$i]];
							$response['data'][$j]['dogovor'] = current_dogovor($da[$fieldsname[$i]]);
						break;
						case 'iduser':
							$response['data'][$j][$fieldsname[$i]] = current_userlogin($da[$fieldsname[$i]]);
						break;
						default:
							$response['data'][$j][$fieldsname[$i]] = $da[$fieldsname[$i]];
						break;

					}

				}

				$j++;

			}

		break;
		case 'info':

			$cid = $db -> getOne("SELECT cid FROM ".$sqlname."history WHERE cid = '".intval($params['cid'])."' ".get_people($iduser)." and identity = '$identity'");

			if($cid < 1 and $params['cid'] != ''){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Запись не найдена в пределах аккаунта указанного пользователя.";

			}
			elseif($cid > 0 and $params['cid'] != ''){

				$cdata = get_historyinfo($params['cid']);
				if(count($cdata) > 0){
					for($i=0;$i < count($fieldsname);$i++){

						switch($fieldsname[$i]){
							case 'clid':
								$response['data'][$fieldsname[$i]] = $cdata[$fieldsname[$i]];
								$response['data']['client'] = current_client($cdata[$fieldsname[$i]]);
							break;
							case 'pid':
								$response['data'][$fieldsname[$i]] = $cdata[$fieldsname[$i]];
								$response['data']['person'] = current_person($cdata[$fieldsname[$i]]);
							break;
							case 'did':
								$response['data'][$fieldsname[$i]] = $cdata[$fieldsname[$i]];
								$response['data']['dogovor'] = current_dogovor($cdata[$fieldsname[$i]]);
							break;
							case 'iduser':
								$response['data'][$fieldsname[$i]] = current_userlogin($cdata[$fieldsname[$i]]);
							break;
							default:
								$response['data'][$fieldsname[$i]] = $cdata[$fieldsname[$i]];
							break;

						}

					}

				}
				else{
					$response['result'] = 'Error';
					$response['error']['code'] = '404';
					$response['error']['text'] = "Не найдено";
				}
			}
			elseif($cid < 1 and $params['cid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

			}
			else{

				$response['result'] = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Не найдено";

			}

		break;
		case 'add':

			if($params['user'] == '') {
				$params['iduser'] = $iduser;
			}
			else {
				$params['iduser'] = current_userbylogin($params['user']);
			}

			//проверка, что есть название клиента
			if($params['des'] != ''){

				if($params['datum'] == '') $params['datum'] = current_datumtime();

				$i = 0; $data = array();
				foreach($params as $key => $value){

					if(in_array($key, $fieldsname)) {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case "cid": break;
							case "clid":
							case "did":
								$data[$i] = "'".intval($data[$i])."'";
							break;
							case "pid":
								if(!is_array($data[$i])) $data[$i] = "'".intval($data[$i])."'";
								else $data[$i] = yimplode(";", $data[$i]);
							break;
							case "tip":
								$data[$i] = "'".getTipTask(untag($data[$i]))."'";
							break;
							break;
							case "des":
								$data[$i] = untag(str_replace("\\r\\n", "\r\n", $data['des']));
							break;
							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				//формируем запрос для добавления клиента
				$names = "cid,".implode(",", $name).",identity";
				$datas = "null,".implode(",", $data).",'".$identity."'";

				if(count($name) > 0){

					try {

						$db -> query("insert into ".$sqlname."history (".$names.") values(".$datas.")");

						$cid = $db -> insertId();

						$response['result'] = 'Успешно';
						$response['data'] = $cid;

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

					}

				}
				else{

					$response['result'] = 'Error';
					$response['error']['code'] = '405';
					$response['error']['text'] = "Отсутствуют параметры";

				}

			}
			else{

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - Заголовок";

			}

		break;
		//todo: Надо доработать добавление нескольких записей
		case 'addlist':

			if($params['user'] == '') $params['iduser'] = $iduser;
			else $params['iduser'] = current_userbylogin($params['user']);

			//проверка, что есть название клиента
			if($params['des'] != ''){

				if($params['datum'] == '') $params['datum'] = current_datumtime();

				$i = 0; $data = array();
				foreach($params as $key => $value){

					if(in_array($key, $fieldsname)) {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case "cid": break;
							case "clid":
							case "did":
								$data[$i] = "'".intval($data[$i])."'";
							break;
							case "pid":
								if(!is_array($data[$i])) $data[$i] = "'".intval($data[$i])."'";
								else $data[$i] = yimplode(";", $data[$i]);
							break;
							case "tip":
								$data[$i] = "'".getTipTask(untag($data[$i]))."'";
							break;
							break;
							case "des":
								$data[$i] = untag(str_replace("\\r\\n", "\r\n", $data['des']));
							break;
							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				//формируем запрос для добавления клиента
				$names = "cid,".implode(",", $name).",identity";
				$datas = "null,".implode(",", $data).",'".$identity."'";

				if(count($name) > 0){

					try {

						$db -> query("insert into ".$sqlname."history (".$names.") values(".$datas.")");

						$cid = $db -> insertId();

						$response['result'] = 'Успешно';
						$response['data'] = $cid;

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

					}

				}
				else{

					$response['result'] = 'Error';
					$response['error']['code'] = '405';
					$response['error']['text'] = "Отсутствуют параметры";

				}

			}
			else{

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - Заголовок";

			}

		break;
		case 'delete':

			$cid = 0;

			//проверка принадлежности cid к данному аккаунту
			$cid = $db -> getOne("SELECT tid FROM ".$sqlname."history WHERE cid = '".$params['cid']."' ".get_people($iduser)." and identity = '$identity'");

			if($cid < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Запись не найдена.";

			}
			else{

				try {

					$db -> query("delete from ".$sqlname."history where сid = '".$сid."' and identity = '$identity'");

					$response['result'] = 'Успешно';
					$response['data'] = $tid;

				}
				catch (Exception $e){

					$response['result'] = 'Error';
					$response['error']['code'] = '500';
					$response['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

				}

			}

			if($params['cid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - cid записи";

			}

		break;
		default:
			$response['error']['code'] = '404';
			$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
		break;
	}

}

print $rez = json_encode_cyr($response);

include "logger.php";

exit();
?>