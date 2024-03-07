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

//print_r($_REQUEST);

foreach($_REQUEST as $key => $value){
	$params[$key] = Cleaner($value);
}

//для приема массива клиентов для добавления
$persons = $_REQUEST['persons'];
$params['filter'] = $_REQUEST['filter'];

//доступные методы
$aceptedActions = array("fields","list","info","add","update","addlist","delete");

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

//ищем аккаунт по apikey
$result = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '".$params['apikey']."'");
$identity = $result['id'];
$api_key = $result['api_key'];
$timezone= $result['timezone'];

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//порядок для реквизитов
$socInfoField = array("blog","mysite","twitter","icq","skype","google","yandex","mykrug");
$socInfoName = array('Блог','Сайт','Twitter','ICQ','Skype','Google','Я.ru','Мой круг');

//найдем пользователя
$result = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '".$params['login']."' and identity = '".$identity."'");
$iduser = $result['iduser'];
$username = $result['title'];

$Error = '';

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

	//составляем списки доступных полей для клиентов
	$ifields[] = 'pid';
	$ifields[] = 'date_create';
	$ifields[] = 'date_edit';
	$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='person' and fld_on='yes' and fld_name != 'social' and identity = '$identity'");
	while ($do = $db -> fetch($resf)){
		$ifields[] = $do['fld_name'];
	}

	//фильтр вывода по полям из запроса или все доступные
	if($params['fields'] != ''){

		$fi = explode(",", $params['fields']);

		for($i=0;$i<count($fi);$i++){
			if(in_array($fi[$i], $ifields)) $fields[] = $fi[$i];
		}

	}
	else $fields = $ifields;

	//задаем лимиты по-умолчанию
	$offset = 0; if($params['offset'] > 0) $offset = $params['offset'];
	$order = 'date_create'; if($params['order'] != '') $order = $params['order'];
	$first = 'DESC'; if($params['first'] == 'old') $first = '';
	$limit = 200;
	$sort = '';

	switch($params['action']){

		case 'fields':

			$response['data']['pid'] = "Уникальный идентификатор записи контакта";
			$response['data']['date_create'] = "Дата создания. Timestamp";
			$response['data']['date_edit'] = "Дата последнего изменения. Timestamp";

			$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='person' and fld_on='yes' and fld_name != 'social' and identity = '$identity'");
			while ($do = $db -> fetch($resf)){
				$response['data'][$do['fld_name']] = $do['fld_title'];
			}

			foreach($socInfoField as $key => $value){
				$response['data']['soc'][$value] = $socInfoName[$key];
			}

		break;
		case 'list':

			$sort.= get_people($iduser);

			if($params['word'] != '') {

				$sort.= " and (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".Cleaner($params['word'])."%' or person LIKE '%".Cleaner($params['word'])."%' or ptitle LIKE '%".Cleaner($params['word'])."%' or mail LIKE '%".Cleaner($params['word'])."%')";

			}

			if($params['dateStart'] != '' and $params['dateEnd'] == '') $sort.= " and date_create > '".$params['dateStart']."'";
			if($params['dateStart'] != '' and $params['dateEnd'] != '') $sort.= " and (date_create BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
			if($params['dateStart'] == '' and $params['dateEnd'] != '') $sort.= " and date_create < '".$params['dateEnd']."'";

			//todo: проверить работу доп.фильтров
			foreach($params['filter'] as $k => $v){

				switch($k) {
					case 'clid':
						if(intval($v) > 0) $sort.= " and clid = '".intval($v)."'";
						break;
					case 'loyalty':
						if($v != '') $sort.= " and loyalty = '".current_loyalty("", untag($v))."'";
						break;
					case 'uid':
						if($v != '') $sort.= " and uid = '".untag($v)."'";
						break;
					case 'clientpath':
						if(intval($v) > 0) $sort.= " and clientpath = '".intval($v)."'";
						break;
					default:
						$sort.= " and ".$k." LIKE '%".untag($v)."%'";
						break;
				}

			}

			$lpos = $offset * $limit;
			$j = 0;

			$result = $db -> query("SELECT * FROM ".$sqlname."personcat WHERE pid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)){

				for($i=0;$i < count($fields);$i++){

					switch($fields[$i]){

						case 'clid':
							$response['data'][$j][$fields[$i]] = $da[$fields[$i]];
							$response['data'][$j]['client'] = current_client($da[$fields[$i]]);
						break;
						case 'loyalty':
							$response['data'][$j][$fields[$i]] = current_loyalty($da[$fields[$i]]);
						break;
						case 'iduser':
							$response['data'][$j][$fields[$i]] = current_userlogin($da[$fields[$i]]);
						break;
						case 'clientpath':
							$response['data'][$j][$fields[$i]] = current_clientpathbyid($da[$fields[$i]]);
						break;
						default:
							$response['data'][$j][$fields[$i]] = $da[$fields[$i]];
						break;

					}

				}

				if($params['socinfo'] == 'yes'){
					$socinfo[$j] = explode(";", $da['social']);
					foreach($socInfoField as $key => $value){
						$response['data'][$j]['socials'][$value] = $socinfo[$j][$key];
					}
					$socinfo[$j] = array();
				}

				$j++;

			}

			$response['count'] = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 ".$sort." and identity = '$identity'");

		break;
		case 'info':

			$pid = $db -> getOne("SELECT pid FROM ".$sqlname."personcat WHERE pid = '".$params['pid']."' ".get_people($iduser)." and identity = '$identity'");

			if($pid < 1 and $params['pid'] != ''){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Контакт с указанным pid не найден в пределах аккаунта указанного пользователя.";

			}
			elseif($pid > 0 and $params['pid'] != ''){

				$cdata = get_person_info($params['pid'],'yes');
				if(count($cdata) > 0){
					for($i=0;$i < count($fields);$i++){

						switch($fields[$i]){
							case 'clid':
								$response['data'][$fields[$i]] = $cdata[$fields[$i]];
								$response['data']['client'] = current_client($cdata[$fields[$i]]);
							break;
							case 'iduser':
								$response['data'][$fields[$i]] = current_userlogin($cdata[$fields[$i]]);
							break;
							case 'loyalty':
								$response['data'][$fields[$i]] = current_loyalty($da[$fields[$i]]);
							break;
							case 'clientpath':
								$response['data'][$fields[$i]] = current_clientpathbyid($cdata['clientpath2']);
							break;
							default:
								$response['data'][$fields[$i]] = $cdata[$fields[$i]];
							break;

						}

					}

					if($params['socinfo'] == 'yes'){
						$info = get_person_info($params['pid'],'yes');
						$socinfo = explode(";", $info['social']);
						foreach($socInfoField as $key => $value){
							$response['data']['socials'][$value] = $socinfo[$key];
						}
					}

				}
				else{
					$response['result'] = 'Error';
					$response['error']['code'] = '404';
					$response['error']['text'] = "Не найдено";
				}
			}
			elseif($pid < 1 and $params['pid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - pid клиента";

			}
			else{

				$response['result'] = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Не найдено";

			}

		break;
		case 'add':

			//проверка на дубли
			$q = '';

			if($params['user'] == '') $params['iduser'] = $iduser;
			else $params['iduser'] = current_userbylogin($params['user']);

			if($params['tel'] != '') $q.= "and tel LIKE '%".$params['tel']."%'";
			if($params['mail'] != '') $q.= " and mail LIKE '%".Cleaner($params['mail'])."%'";

			$pid = $db -> getOne("SELECT pid FROM ".$sqlname."personcat WHERE person = '".untag($params['person'])."' $q and identity = '$identity'");

			if($pid > 0){

				$response['result'] = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Найден существующий контакт - ".current_person($pid)." (pid = $pid). Запрос отклонен.";

			}

			//проверка, что есть название клиента
			elseif($params['person'] != '' and $pid < 1){

				if(isset($params['date_create']) and strtotime($params['date_create']) != '') $params['date_create'] = date('Y-m-d H:i:s', strtotime($params['date_create']));
				else $params['date_create'] = current_datumtime();

				$i = 0;
				foreach($params as $key => $value){
					if(in_array($key, $fields)) {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case "clientpath":
								$data[$i] = "'".getClientpath(untag($data[$i]))."'";
							break;
							case "loyalty":
								$data[$i] = "'".getPersonLoyalty(untag($data[$i]))."'";
							break;

							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				//формируем реквизиты
				$cinfo = array();
				$socinfo = $_REQUEST['socials'];
				foreach($socInfoField as $key => $value){
					$socinfo[] = Cleaner($socinfo[$value]);
				}
				if(count($socinfo) > 0) {
					$social = ",'".implode(";", $socinfo)."'";
					$sname = ",social";
				}

				//формируем запрос для добавления клиента
				$names = "pid,".implode(",", $name).$sname.",creator,identity";
				$datas = "null,".implode(",", $data).$social.",'".$iduser."','".$identity."'";

				//print "insert into ".$sqlname."personcat (".$names.") values(".$datas.")";

				if(count($name) > 0){

					try {

						$db -> query("insert into ".$sqlname."personcat (".$names.") values(".$datas.")");

						$pid = $db -> insertId();

						$response['result'] = 'Успешно';
						$response['data'] = $pid;

						//запись в историю активности
						$db -> query("insert into ".$sqlname."history (cid,iduser,pid,datum,des,tip,identity) values(null, '".$iduser."', '".$pid."', '".current_datumtime()."', 'Добавлен контакт через API', 'СобытиеCRM','$identity')");

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
				$response['error']['text'] = "Отсутствуют параметры - Имя контакта";

			}

		break;
		case 'addlist':

			if($params['user'] == '') $user = $iduser;
			else $user = current_userbylogin($params['user']);

			for($j=0;$j<count($persons);$j++){

				//проверка на дубли
				$q = '';

				//if($params['phone'] != '') $q.= "and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($params['phone'])."%'";

				if($persons[$j]['tel'] != '') $q.= "and tel LIKE '%".Cleaner($persons[$j]['tel'])."%'";
				if($persons[$j]['mob'] != '') $q.= "and mob LIKE '%".Cleaner($persons[$j]['mob'])."%'";
				if($persons[$j]['mail'] != '') $q.= " and mail LIKE '%".Cleaner($persons[$j]['mail'])."%'";

				$pid[$j] = $db -> getOne("SELECT pid FROM ".$sqlname."personcat WHERE person = '".Cleaner($persons[$j]['person'])."' $q and identity = '$identity'");

				if($pid[$j] > 0){

					$response[$j]['result'] = 'Error';
					$response[$j]['error']['code'] = '406';
					$response[$j]['error']['text'] = "Найден существующий контакт - ".current_person($pid[$j])." (pid = ".$pid[$j]."). Запрос отклонен.";

				}

				//проверка, что есть название клиента
				elseif($persons[$j]['person'] != '' and $pid[$j] < 1){

					if(isset($persons[$j]['date_create']) and strtotime($persons[$j]['date_create']) != '') $persons[$j]['date_create'] = date('Y-m-d H:i:s', strtotime($persons[$j]['date_create']));
					else $persons[$j]['date_create'] = current_datumtime();

					$clients[$j]['iduser'] = $user;

					$i = 0; $name = array(); $data = array();
					foreach($persons[$j] as $key => $value){
						if(in_array($key, $fields)) {

							$name[$i] = $key;
							$data[$i] = $value;

							switch($name[$i]){
								case "clientpath":
									$data[$i] = "'".getClientpath(Cleaner($data[$i]))."'";
								break;
								case "loyalty":
									$data[$i] = "'".getPersonLoyalty(untag($data[$i]))."'";
								break;

								default: $data[$i] = "'".Cleaner($data[$i])."'"; break;
							}
							$i++;
						}
					}

					//формируем реквизиты
					$cinfo = array();
					$socinfo = $persons[$j]['socials'];
					foreach($socInfoField as $key => $value){
						$socinfo[] = Cleaner($socinfo[$value]);
					}
					if(count($socinfo) > 0) {
						$social = ",'".implode(";", $socinfo)."'";
						$sname = ",social";
					}

					$names = "pid,".implode(",", $name).$sname.",iduser,creator,identity";
					$datas = "null,".implode(",", $data).$social.",'".$user."','".$iduser."','".$identity."'";

					if(count($name) > 0){

						try {

							$db -> query("insert into ".$sqlname."personcat (".$names.") values(".$datas.")");

							$pid[$j] = $db -> insertId();

							$response[$j]['result'] = 'Успешно';
							$response[$j]['data'] = $pid[$j];

							$db -> query("insert into ".$sqlname."history (cid,iduser,pid,datum,des,tip,identity) values(null, '".$iduser."', '".$pid[$j]."', '".current_datumtime()."', 'Добавлен контакт через API', 'СобытиеCRM','$identity')");

						}
						catch (Exception $e){

							$response[$j]['result'] = 'Error';
							$response[$j]['error']['code'] = '500';
							$response[$j]['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

						}

					}
					else{

						$response['result'][$j] = 'Error';
						$response['error'][$j]['code'] = '405';
						$response['error'][$j]['text'] = "Отсутствуют параметры";

					}

				}
				else{

					$response[$j]['result'] = 'Error';
					$response[$j]['error']['code'] = '405';
					$response[$j]['error']['text'] = "Отсутствуют параметры - Имя контакта";

				}

			}

		break;
		case 'update':

			//проверка принадлежности clid к данному аккаунту
			$pid = $db -> getOne("SELECT pid FROM ".$sqlname."personcat WHERE pid = '".$params['pid']."' ".get_people($iduser)." and identity = '$identity'");

			if($pid < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Контакт с указанным pid не найден в пределах аккаунта указанного пользователя.";

			}
			else{

				if(isset($params['date_create']) and strtotime($params['date_create']) != '') $params['date_create'] = date('Y-m-d H:i:s', strtotime($params['date_create']));

				//print_r($params);

				$i = 0;
				foreach($params as $key => $value){
					if(in_array($key, $fields)) {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case "loyalty":
								$data[$i] = "'".getPersonLoyalty(untag($data[$i]))."'";
							break;
							case "clientpath":
								$data[$i] = "'".getClientpath($data[$i])."'";
							break;

							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				$datad = get_person_info($pid, 'yes');
				$datad['clientpath'] = $datad['clientpath2'];
				$datad['date_create'] = get_unhist($datad['date_create']);

				$datas = '';
				for($i=0;$i<count($data);$i++){

					$datas.= " ".$name[$i]." = ".$data[$i].",";

					$newParams[$name[$i]] = substr($data[$i],1,-1);//массив новых параметров
					$oldParams[$name[$i]] = $datad[$name[$i]];//массив старых параметров

				}
				$datas.= " date_edit = '".current_datumtime()."', editor = '".$iduser."'";

				$log = doLogger('pid', $pid, $newParams, $oldParams);

				if($log != 'none'){

					try {

						$db -> query("update ".$sqlname."personcat set ".$datas." where pid = '".$pid."' and identity = '$identity'");

						$response['result'] = 'Успешно'.$apdx;
						$response['data'] = $pid;

						$db -> query("insert into ".$sqlname."history (cid,iduser,pid,datum,des,tip,identity) values(null, '".$iduser."', '".$pid."', '".current_datumtime()."', '".$log."', 'ЛогCRM','$identity')");

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = $e-> getMessage(). ' в строке '. $e->getCode();

					}

				}
				else {
					$response['result'] = 'Данные корректны, но идентичны имеющимся.';
					$response['data'] = $pid;
				}

				$cinfo = array();
				$socinfo = $_REQUEST['socials'];
				$pOldInfo = get_person_info($pid, 'yes');
				$personOldInfo = explode(";", $pOldInfo['social']);
				foreach($socInfoField as $key => $value){

					if($socinfo[$value] != $personOldInfo[$value] and $socinfo[$value] != '') $cinfo[] = Cleaner($socinfo[$value]);
					else $cinfo[] = $personOldInfo[$value];

				}
				$soc = implode(";", $cinfo);
				//если указаны реквизиты - добавляем
				if($soc != '') {
					$db -> query("update ".$sqlname."personcat set social = '".$soc."' where pid = '".$pid."' and identity = '$identity'");
					$response['social'] = 'Соц.контакты обновлены';
				}

			}

			if($params['pid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - pid контакта";

			}

		break;
		case 'delete':

			//проверка принадлежности clid к данному аккаунту
			$pid = $db -> getOne("SELECT pid FROM ".$sqlname."personcat WHERE pid = '".$params['pid']."' ".get_people($iduser)." and identity = '$identity'");

			if($pid < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Контакт с указанным pid не найден в пределах аккаунта указанного пользователя.";

			}
			else{

				$cdogs = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."dogovor WHERE pid='".$pid."' and identity = '$identity'");

				if($cdogs==0){

					//Удалим всю историю переговоров
					$db -> query("delete from ".$sqlname."history where pid = '".$pid."' and identity = '$identity'");

					//Удалим все напоминания
					$db -> query("delete from ".$sqlname."tasks where pid = '".$pid."' and identity = '$identity'");

					//Удалим всю связанные файлы
					$result4 = $db -> query("select * from ".$sqlname."file WHERE pid='".$pid."' and identity = '$identity'");
					while ($d4 = $db -> fetch($result4)) {

						@unlink("../files/".$d4['fname']);
						$db -> query("delete from ".$sqlname."file where fid = '".$d4['fid']."' and identity = '$identity'");
						$f++;

					}

					logger('10','Удален контакт '.current_person($pid),$iduser);

					//удалим саму организацию
					if($db -> query("delete from ".$sqlname."personcat where pid = '".$pid."' and identity = '$identity'")){
						$response['result'] = 'Успешно';
						$response['data'] = $pid;
					}

				}
				else{

					$response['result'] = 'Error';
					$response['error']['code'] = '406';
					$response['error']['text'] = "Удаление клиента не возможно - есть сделки";

				}
			}

			if($params['pid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - pid контакта";

			}

		break;
		default:
			$response['error']['code'] = '404';
			$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
		break;

	}

}

//print_r($response);
print $rez = json_encode_cyr($response);

include "logger.php";

exit();
?>