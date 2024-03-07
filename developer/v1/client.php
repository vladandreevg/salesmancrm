<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

header('Access-Control-Allow-Origin: *');// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ERROR);

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

//$params = unserialize(urldecode(serialize($_REQUEST)));

//для приема массива клиентов для добавления
$clients = $_REQUEST['client'];
$params['filter'] = $_REQUEST['filter'];

//доступные методы
$aceptedActions = array("fields","list","info","add","update","addlist","delete");

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '".$params['apikey']."'");
$identity = $result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//порядок для реквизитов
$bankInfoField = array('castUrName','castInn','castKpp','castBank','castBankKs','castBankRs','castBankBik','castOkpo','castOgrn','castDirName','castDirSignature','castDirStatus','castDirStatusSig','castDirOsnovanie','castUrAddr');

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '".$params['login']."' and identity = '".$identity."'");
$iduser   = $result['iduser'];
$username = $result['title'];

$Error = ''; $fields = $response = array();

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

	$isfields = $db -> getCol("SELECT fld_name FROM ".$sqlname."field WHERE fld_tip='client' and fld_on='yes' and fld_name != 'recv' and identity = '$identity'");

	array_unshift($isfields, 'clid', 'uid', 'type', 'date_create', 'date_edit');

	//составляем списки доступных полей для клиентов
	$ifields[] = 'clid';
	$ifields[] = 'uid';
	$ifields[] = 'type';
	$ifields[] = 'date_create';
	$ifields[] = 'date_edit';

	//фильтр вывода по полям из запроса или все доступные
	if($params['fields'] != ''){

		$fi = explode(",", $params['fields']);
		for($i=0;$i<count($fi);$i++){

			if(in_array($fi[$i], $ifields)) $fields[] = $fi[$i];

		}

	}
	else $fields = $isfields;

	//задаем лимиты по-умолчанию
	$offset = 0; if($params['offset'] > 0) $offset = $params['offset'];
	$order = 'date_create'; if($params['order'] != '') $order = $params['order'];
	$first = 'DESC'; if($params['first'] == 'old') $first = '';
	$limit = 200;
	$sort = '';

	switch($params['action']){

		case 'fields':

			$response['data']['clid'] = "Уникальный идентификатор записи клиента в CRM";
			$response['data']['uid'] = "Уникальный идентификатор записи клиента в вашей ИС";
			$response['data']['type'] = "Тип записи (допустимые - client,person,concurent,contractor,parnter)";
			$response['data']['date_create'] = "Дата создания. Timestamp";
			$response['data']['date_edit'] = "Дата последнего изменения. Timestamp";

			$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='client' and fld_on='yes' and identity = '$identity'");
			while ($do = $db -> fetch($resf)){
				$response['data'][$do['fld_name']] = $do['fld_title'];
			}

		break;
		case 'list':

			$sort.= get_people($iduser);

			if($params['word'] != '') {

				$sort.= " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".Cleaner($params['word'])."%' or title LIKE '%".Cleaner($params['word'])."%' or des LIKE '%".Cleaner($params['word'])."%' or mail_url LIKE '%".Cleaner($params['word'])."%' or site_url LIKE '%".Cleaner($params['word'])."%' or address LIKE '%".Cleaner($params['word'])."%')";

			}

			if($params['dateStart'] != '' and $params['dateEnd'] == '') $sort.= " and date_create > '".$params['dateStart']."'";
			if($params['dateStart'] != '' and $params['dateEnd'] != '') $sort.= " and (date_create BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
			if($params['dateStart'] == '' and $params['dateEnd'] != '') $sort.= " and date_create < '".$params['dateEnd']."'";

			//todo: проверить работу доп.фильтров
			foreach($params['filter'] as $k => $v){

				switch($k) {
					case 'relations':
						if($v != '') $sort.= " and tip_cmr = '".untag($v)."'";
					break;
					case 'idcategory':
						if($v != '') $sort.= " and idcategory = '".current_category('', untag($v))."'";
					break;
					case 'territory':
						if($v != '') $sort.= " and territory = '".current_territory('', untag($v))."'";
					break;
					case 'type':
						if($v != '') $sort.= " and type = '".untag($v)."'";
					break;
					case 'clientpath':
						if(intval($v) > 0) $sort.= " and clientpath = '".intval($v)."'";
					break;
					default:
						$sort.= " and ".$k." LIKE '%".untag($v)."%'";
					break;
				}

			}

			//print "SELECT * FROM ".$sqlname."clientcat WHERE clid > 0 ".$sort." and identity = '$identity'";

			$lpos = $offset * $limit;
			$j = 0;

			$result = $db -> query("SELECT * FROM ".$sqlname."clientcat WHERE clid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)){

				for($i=0;$i < count($fields);$i++){

					switch($fields[$i]){

						case 'head_clid':
							$response['data'][$j][$fields[$i]] = get_client_category($da[$fields[$i]]);
						break;
						case 'pid':
							$response['data'][$j][$fields[$i]] = current_person($da[$fields[$i]]);
						break;
						case 'idcategory':
							$response['data'][$j][$fields[$i]] = get_client_category($da[$fields[$i]]);
						break;
						case 'territory':
							$response['data'][$j][$fields[$i]] = current_territory($da[$fields[$i]]);
						break;
						case 'clientpath':
							$response['data'][$j][$fields[$i]] = current_clientpathbyid($da[$fields[$i]]);
						break;
						default:
							$response['data'][$j][$fields[$i]] = $da[$fields[$i]];
						break;

					}

				}
				if($params['bankinfo'] == 'yes'){
					$bankinfo = get_client_recv($da['clid'],'yes');
					foreach($bankInfoField as $key => $value){
						$response['data'][$j]['bankinfo'][$value] = $bankinfo[$value];
					}
				}
				$j++;

			}

			$response['count'] = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 ".$sort." and identity = '$identity'");

		break;
		case 'info':

			if($params['uid'] != '') $s = "uid = '".$params['uid']."'";
			elseif($params['clid'] != '') $s = "clid = '".$params['clid']."'";

			$clid = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE $s ".get_people($iduser)." and identity = '$identity'");

			if($clid < 1 and $params['clid'] != ''){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Клиент не найден в пределах аккаунта указанного пользователя.";

			}
			elseif($clid > 0){

				$cdata = get_client_info($clid,'yes');
				if(count($cdata) > 0){
					for($i=0;$i < count($fields);$i++){

						switch($fields[$i]){

							case 'head_clid':
								$response['data'][$fields[$i]] = get_client_category($cdata[$fields[$i]]);
							break;
							case 'pid':
								$response['data'][$fields[$i]] = $cdata[$fields[$i]];
								$response['data']['person'] = current_person($cdata[$fields[$i]]);
							break;
							case 'iduser':
								$response['data'][$fields[$i]] = current_userlogin($cdata[$fields[$i]]);
							break;
							case 'idcategory':
								$response['data'][$fields[$i]] = get_client_category($cdata[$fields[$i]]);
							break;
							case 'territory':
								$response['data'][$fields[$i]] = current_territory($cdata[$fields[$i]]);
							break;
							case 'clientpath':
								$response['data'][$fields[$i]] = current_clientpathbyid($cdata['clientpath2']);
							break;
							default:
								$response['data'][$fields[$i]] = $cdata[$fields[$i]];
							break;

						}

					}

					if($params['bankinfo'] == 'yes'){
						$bankinfo = get_client_recv($clid,'yes');
						foreach($bankInfoField as $key => $value){
							$response['data']['bankinfo'][$value] = $bankinfo[$value];
						}
					}

				}
				else{
					$response['result'] = 'Error';
					$response['error']['code'] = '404';
					$response['error']['text'] = "Не найдено";
				}
			}
			elseif($clid < 1 and $params['uid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры клиента";

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

			//if($params['phone'] != '') $q.= "and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($params['phone'])."%'";

			if($params['phone'] != '') $q.= "and phone LIKE '%".$params['phone']."%'";
			if($params['mail_url'] != '') $q.= " and mail_url LIKE '%".Cleaner($params['mail_url'])."%'";

			$clid = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE title = '".clientFormatTitle($params['title'])."' $q and identity = '$identity'");

			if($clid > 0){

				$response['result'] = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Найден существующий клиент - ".current_client($clid)." (clid = $clid). Запрос отклонен.";

			}

			//проверка, что есть название клиента
			elseif($params['title'] != '' and $clid < 1){

				if(!isset($params['type'])) $params['type'] = 'client';

				if(isset($params['date_create']) and strtotime($params['date_create']) != '') $params['date_create'] = date('Y-m-d H:i:s', strtotime($params['date_create']));
				else $params['date_create'] = current_datumtime();

				$i = 0; $name = $data = array();

				foreach($params as $key => $value){
					if(in_array($key, $fields)) {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case "type":
								if($data[$i] == '') $data[$i] = 'client';
								elseif(in_array($data[$i], array('client','person','concurent','contractor','parnter'))) $data[$i] = "'".$data[$i]."'";
								else $data[$i] = "'client'";
							break;
							case "title":
								$data[$i] = "'".clientFormatTitle($data[$i])."'";
							break;
							case "idcategory":
								$data[$i] = "'".getClientCategory($data[$i])."'";
							break;
							case "clientpath":
								$data[$i] = "'".getClientpath($data[$i])."'";
							break;
							case "territory":
								$data[$i] = "'".getClientTerritory(untag($data[$i]))."'";
							break;
							case "tip_cmr":
								$data[$i] = "'".getClientRelation(untag($data[$i]))."'";
							break;

							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				//формируем реквизиты
				$binfo = array();
				$bankinfo = $_REQUEST['recv'];
				foreach($bankInfoField as $key => $value){
					$binfo[] = Cleaner($bankinfo[$value]);
				}
				$recv = implode(";", $binfo);

				//формируем запрос для добавления клиента
				$names = "clid,".implode(",", $name).",creator,identity";
				$datas = "null,".implode(",", $data).",'".$iduser."','".$identity."'";

				if(count($name) > 0){

					try {

						$db -> query("insert into ".$sqlname."clientcat (".$names.") values(".$datas.")");
						$clid = $db -> insertId();

						$response['result'] = 'Успешно';
						$response['data'] = $clid;

						//запись в историю активности
						$db -> query("insert into ".$sqlname."history (cid,iduser,clid,datum,des,tip,identity) values(null, '".$iduser."', '".$clid."', '".current_datumtime()."', 'Добавлен клиент через API', 'СобытиеCRM','$identity')");

						//если указаны реквизиты - добавляем
						if($recv != '') $db -> query("update ".$sqlname."clientcat set recv = '".$recv."' where clid = '".$clid."' and identity = '$identity'");

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

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
				$response['error']['text'] = "Отсутствуют параметры - Название клиента";

			}

		break;
		case 'addlist':

			if($params['user'] == '') $user = $iduser;
			else $user = current_userbylogin($params['user']);

			//print_r($clients);
			//exit();

			for($j=0;$j<count($clients);$j++){

				//проверка на дубли
				$q = '';

				//if($params['phone'] != '') $q.= "and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone($params['phone'])."%'";

				if($clients[$j]['phone'] != '') $q.= "and phone LIKE '%".Cleaner($clients[$j]['phone'])."%'";
				if($clients[$j]['mail_url'] != '') $q.= " and mail_url LIKE '%".Cleaner($clients[$j]['mail_url'])."%'";

				$clid[$j] = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE title = '".clientFormatTitle(Cleaner($clients[$j]['title']))."' $q and identity = '$identity'");

				if($clid[$j] > 0){

					$response[$j]['result'] = 'Error';
					$response[$j]['error']['code'] = '406';
					$response[$j]['error']['text'] = "Найден существующий клиент - ".current_client($clid[$j])." (clid = ".$clid[$j]."). Запрос отклонен.";

				}

				//проверка, что есть название клиента
				elseif($clients[$j]['title'] != '' and $clid[$j] < 1){

					if(!isset($clients[$j]['type'])) $clients[$j]['type'] = 'client';

					if(isset($clients[$j]['date_create']) and strtotime($clients[$j]['date_create']) != '') $clients[$j]['date_create'] = date('Y-m-d H:i:s', strtotime($clients[$j]['date_create']));
					else $clients[$j]['date_create'] = current_datumtime();

					//$clients[$j]['iduser'] = $user;

					$i = 0;
					$name = array();
					$data = array();

					foreach($clients[$j] as $key => $value){

						if(in_array($key, $fields)) {

							$name[$i] = $key;
							$data[$i] = $value;

							switch($name[$i]){
								case "type":
									if($data[$i] == '') $data[$i] = 'client';
									elseif(in_array($data[$i], array('client','person','concurent','contractor','parnter'))) $data[$i] = "'".$data[$i]."'";
									else $data[$i] = "'client'";
								break;
								case "title":
									$data[$i] = "'".clientFormatTitle(Cleaner($data[$i]))."'";
								break;
								case "idcategory":
									$data[$i] = "'".getClientCategory(Cleaner($data[$i]))."'";
								break;
								case "clientpath":
									$data[$i] = "'".getClientpath(Cleaner($data[$i]))."'";
								break;
								case "territory":
									$data[$i] = "'".getClientTerritory(Cleaner($data[$i]))."'";
								break;
								case "tip_cmr":
									$data[$i] = "'".getClientRelation(Cleaner($data[$i]))."'";
								break;

								default: $data[$i] = "'".Cleaner($data[$i])."'"; break;
							}
							$i++;
						}
					}

					//формируем реквизиты
					$binfo = array();
					$bankinfo = $clients[$j]['recv'];
					foreach($bankInfoField as $key => $value){
						$binfo[] = Cleaner($bankinfo[$value]);
					}
					$recv[$j] = implode(";", $binfo);


					$names = "clid,".implode(",", $name).",iduser,creator,identity";
					$datas = "null,".implode(",", $data).",'".$user."','".$iduser."','".$identity."'";

					if(count($name) > 0){

						try {

							$db -> query("insert into ".$sqlname."clientcat (".$names.") values(".$datas.")");
							$clid[$j] = $db -> insertId();

							$response[$j]['result'] = 'Успешно';
							$response[$j]['data'] = $clid[$j];

							$db -> query("insert into ".$sqlname."history (cid,iduser,clid,datum,des,tip,identity) values(null, '".$iduser."', '".$clid[$j]."', '".current_datumtime()."', 'Добавлен клиент через API', 'СобытиеCRM','$identity')");

							//если указаны реквизиты - добавляем
							if($recv[$j] != '') $db -> query("update ".$sqlname."clientcat set recv = '".$recv[$j]."' where clid = '".$clid[$j]."' and identity = '$identity'");

						}
						catch (Exception $e){

							$response[$j]['result'] = 'Error';
							$response[$j]['error']['code'] = '500';
							$response[$j]['error']['text'] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

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
					$response[$j]['error']['text'] = "Отсутствуют параметры - Название клиента";

				}

			}

		break;
		case 'update':

			//проверка принадлежности clid к данному аккаунту
			$clid = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE clid = '".$params['clid']."' and identity = '$identity'");

			if($clid < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Клиент с указанным clid не найден в пределах аккаунта";

			}
			else{

				if(!isset($params['type'])) $params['type'] = 'client';

				if(isset($params['date_create']) and strtotime($params['date_create']) != '') $params['date_create'] = date('Y-m-d H:i:s', strtotime($params['date_create']));

				//print_r($params);

				$i = 0; $data = $name = array();

				foreach($params as $key => $value){
					if(in_array($key, $fields)) {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case "type":
								if($data[$i] == '') $data[$i] = 'client';
								elseif(in_array($data[$i], array('client','person','concurent','contractor','parnter'))) $data[$i] = "'".$data[$i]."'";
								else $data[$i] = "'client'";
							break;
							case "title":
								$data[$i] = "'".clientFormatTitle($data[$i])."'";
							break;
							case "idcategory":
								$data[$i] = "'".getClientCategory($data[$i])."'";
							break;
							case "clientpath":
								$data[$i] = "'".getClientpath($data[$i])."'";
							break;
							case "territory":
								$data[$i] = "'".getClientTerritory(untag($data[$i]))."'";
							break;
							case "tip_cmr":
								$data[$i] = "'".getClientRelation(untag($data[$i]))."'";
							break;

							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				$datad = get_client_info($clid, 'yes');
				$datad['clientpath'] = $datad['clientpath2'];
				$datad['date_create'] = get_unhist($datad['date_create']);

				$datas = '';
				for($i=0;$i<count($data);$i++){

					$datas.= " ".$name[$i]." = ".$data[$i].",";

					$newParams[$name[$i]] = substr($data[$i],1,-1);//массив новых параметров
					$oldParams[$name[$i]] = $datad[$name[$i]];//массив старых параметров

				}
				$datas.= " date_edit = '".current_datumtime()."', editor = '".$iduser."'";

				$log = doLogger('clid', $clid, $newParams, $oldParams);

				if($log != 'none'){

					try {

						$db -> query("update ".$sqlname."clientcat set ".$datas." where clid = '".$clid."' and identity = '$identity'");

						$response['result'] = 'Успешно'.$apdx;
						$response['data'] = $clid;

						$db -> query("insert into ".$sqlname."history (cid,iduser,clid,datum,des,tip,identity) values(null, '".$iduser."', '".$clid."', '".current_datumtime()."', '".$log."', 'ЛогCRM','$identity')");

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

					}

				}
				else {
					$response['result'] = 'Данные корректны, но идентичны имеющимся.';
					$response['data'] = $clid;
				}

				//формируем реквизиты
				$binfo = array();
				$bankinfo = $_REQUEST['recv'];
				$bankOldInfo = get_client_recv($clid, 'yes');
				foreach($bankInfoField as $key => $value){

					if($bankinfo[$value] != $bankOldInfo[$value] and $bankinfo[$value] != '') $binfo[] = Cleaner($bankinfo[$value]);
					else $binfo[] = $bankOldInfo[$value];

				}
				$recv = implode(";", $binfo);
				//если указаны реквизиты - добавляем
				if($recv != '') {

					$db -> query("update ".$sqlname."clientcat set recv = '".$recv."' where clid = '".$clid."' and identity = '$identity'");
					$response['recv'] = 'Реквизиты обновлены';

				}

			}

			if($params['clid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - clid клиента";

			}

		break;
		case 'delete':

			//проверка принадлежности clid к данному аккаунту
			$clid = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE clid = '".$params['clid']."' ".get_people($iduser)." and identity = '$identity'");

			if($clid < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Клиент с указанным clid не найден в пределах аккаунта указанного пользователя.";

			}
			else{

				$cdogs = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."dogovor WHERE clid='".$clid."' and identity = '$identity'");

				if($cdogs==0){

					//Удалим привязки персон к данной организации
					$db -> query("update ".$sqlname."personcat set clid = '' where clid = '".$clid."' and identity = '$identity'");

					//Удалим всю историю переговоров
					$db -> query("delete from ".$sqlname."history where clid = '".$clid."' and identity = '$identity'");

					//Удалим все напоминания
					$db -> query("delete from ".$sqlname."tasks where clid = '".$clid."' and identity = '$identity'");

					//Удалим всю связанные файлы
					$result4 = $db -> query("select * from ".$sqlname."file WHERE clid='".$clid."' and identity = '$identity'");
					while ($d4 = $db -> fetch($result4)) {

						@unlink("../files/".$d4['fname']);
						$db -> query("delete from ".$sqlname."file where fid = '".$d4['fid']."' and identity = '$identity'");
						$f++;

					}

					//Удалим профиль
					$db -> query("delete from ".$sqlname."profile where clid='".$clid."' and identity = '$identity'");

					logger('10','Удалена организация '.current_client($clid),$iduser);

					//удалим саму организацию
					if($db -> query("delete from ".$sqlname."clientcat where clid = '".$clid."' and identity = '$identity'")){
						$response['result'] = 'Успешно';
						$response['data'] = $clid;
					}

				}
				else{

					$response['result'] = 'Error';
					$response['error']['code'] = '406';
					$response['error']['text'] = "Удаление клиента не возможно - есть сделки";

				}
			}

			if($params['clid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - clid клиента";

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