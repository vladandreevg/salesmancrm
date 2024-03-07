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
	$params[$key] = urldecode(Cleaner($value));
}

$params['speka'] = $_REQUEST['speka'];
$params['filter'] = $_REQUEST['filter'];
$params['invoice'] = $_REQUEST['invoice'];

//print_r($params);

//доступные методы
$aceptedActions = array("fields","steplist","direction","contracttipe","list","info","add","update","changestep","close","delete","addinvoice","addpaiment","statusclose","addpaimentpart");

$db = new SafeMysql(array('host' => $dbhostname, 'user' => $dbusername, 'pass' => $dbpassword,'db' => $database, 'charset' => 'utf8', 'errmode' => 'exception'));

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone, valuta FROM ".$sqlname."settings WHERE api_key = '".$params['apikey']."'");
$identity = $result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];
$valuta   = $result['valuta'];

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '".$params['login']."' and identity = '".$identity."'");
$iduser   = $result['iduser'];
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

	//составляем списки доступных полей для сделок
	$ifields[] = 'did';
	$ifields[] = 'uid';
	$ifields[] = 'datum';
	$ifields[] = 'datum_izm';
	$ifields[] = 'clid';
	$ifields[] = 'title';

	$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'");
	while ($do = $db -> fetch($resf)){

		if($do['fld_name'] == 'idcategory') $ifields[] = 'step';
		elseif($do['fld_name'] == 'marg') $ifields[] = 'marga';
		else $ifields[] = $do['fld_name'];
	}

	$ifields[] = 'datum_start';
	$ifields[] = 'datum_end';
	$ifields[] = 'close';
	$ifields[] = 'datum_close';
	$ifields[] = 'status_close';
	$ifields[] = 'des_fact';
	$ifields[] = 'kol_fact';

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
	$order = 'datum'; if($params['order'] != '') $order = $params['order'];
	$first = 'DESC'; if($params['first'] == 'old') $first = '';
	$limit = 200;
	$sort = '';

	switch($params['action']){

		case 'fields':

			$response['data']['did'] = "Уникальный идентификатор записи";
			$response['data']['uid'] = "Уникальный идентификатор записи во внешней системе";
			$response['data']['datum'] = "Дата создания. YYYY-MM-DD";
			$response['data']['datum_izm'] = "Дата последнего изменения. YYYY-MM-DD";
			$response['data']['clid'] = "Клиент";

			$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'");
			while ($do = $db -> fetch($resf)){

				if($do['fld_name'] == 'idcategory') $response['data']['step'] = $do['fld_title'];
				elseif($do['fld_name'] == 'marg') $response['data']['marga'] = $do['fld_title'];
				elseif($do['fld_name'] == 'mcid') $response['data']['mcid'] = "ID своей компании";
				else $response['data'][$do['fld_name']] = $do['fld_title'];
			}

			$response['data']['datum_start'] = "Период действия. Начало";
			$response['data']['datum_end'] = "Период действия. Конец";
			$response['data']['close'] = "Признак закрытой сделки";
			$response['data']['datum_close'] = "Дата закрытия. YYYY-MM-DD";
			$response['data']['status_close'] = "Результат закрытия сделки";
			$response['data']['des_fact'] = "Комментарий закрытия сделки";
			$response['data']['kol_fact'] = "Фактическая сумма продажи";

		break;
		case 'steplist':

			$re = $db -> query("SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title");
			while ($do = $db -> fetch($re)){
				$response['data'][] = array("title" => $do['title'],"content" => $do['content']);
			}

		break;
		case 'funnel':

			$did       = $params['did'];
			$direction = current_direction("", untag($params['direction']));
			$tip       = current_dogtype("", untag($params['tip']));

			$response['data'] = $funnel = getMultiStepList(array("did" => $did, "direction" => $direction, "tip" => $tip));

		break;
		case 'direction':

			$re = $db -> query("SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title");
			while ($do = $db -> fetch($re)){
				$response['data'][] = array("title" => $do['title']);
			}

		break;
		case 'contracttipe':

			$re = $db -> query("SELECT * FROM ".$sqlname."dogtips WHERE identity = '$identity' ORDER BY title");
			while ($do = $db -> fetch($re)){
				$response['data'][] = array("title" => $do['title']);
			}

		break;
		case 'statusclose':

			$re = $db -> query("SELECT * FROM ".$sqlname."dogstatus WHERE identity = '$identity' ORDER BY title");
			while ($do = $db -> fetch($re)){
				$response['data'][] = array("title" => $do['title']);
			}

		break;
		case 'list':

			if($params['user'] != '') $iduser = current_userbylogin($params['user']);

			$sort.= get_people($iduser);

			if($params['active'] == 'no') $sort.= " and close == 'yes'";
			elseif($params['active'] == 'yes') $sort.= " and close != 'yes'";
			else $sort.= "";

			if($params['word'] != '') $sort.= " and (title LIKE '%".Cleaner($params['word'])."%' or des LIKE '%".Cleaner($params['word'])."%' or adres LIKE '%".Cleaner($params['word'])."%')";

			if($params['steps'] != ''){
				$step = array();
				$st = explode(",",$params['steps']);
				foreach($st as $val){
					if($val != '') $step[] = "'".untag($val)."'";
				}
				$step = implode(",", $step);
				if($step != '') $sort.= " and idcategory IN ($step)";
			}

			if($params['dateStart'] != '' and $params['dateEnd'] == '') $sort.= " and datum > '".$params['dateStart']."'";
			if($params['dateStart'] != '' and $params['dateEnd'] != '') $sort.= " and (datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
			if($params['dateStart'] == '' and $params['dateEnd'] != '') $sort.= " and datum < '".$params['dateEnd']."'";

			//todo: проверить работу доп.фильтров
			foreach($params['filter'] as $k => $v){

				switch($k) {
					case 'clid':
						if(intval($v) > 0) $sort.= " and clid = '".intval($v)."'";
						break;
					case 'payer':
						if(intval($v) > 0) $sort.= " and payer = '".intval($v)."'";
						break;
					case 'direction':
						if($v != '') $sort.= " and direction = '".current_direction("", untag($v))."'";
						break;
					case 'tip':
						if($v != '') $sort.= " and tip = '".current_dogtype("", untag($v))."'";
						break;
					default:
						$sort.= " and ".$k." LIKE '%".untag($v)."%'";
						break;
				}

			}

			$lpos = $offset * $limit;
			$j = 0;

			//print "SELECT * FROM ".$sqlname."dogovor WHERE did > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit";

			$result = $db -> query("SELECT * FROM ".$sqlname."dogovor WHERE did > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)){

				for($i=0;$i < count($fields);$i++){

					switch($fields[$i]){

						case 'iduser':
							$response['data'][$j][$fields[$i]] = current_userlogin($da[$fields[$i]]);
						break;
						case 'clid':
							$response['data'][$j][$fields[$i]] = $da[$fields[$i]];
							$response['data'][$j]['clientname'] = current_client($da[$fields[$i]]);
						break;
						case 'payer':
							$response['data'][$j][$fields[$i]] = $da[$fields[$i]];
							$response['data'][$j]['payername'] = current_client($da[$fields[$i]]);
						break;
						case 'step':
							$response['data'][$j][$fields[$i]] = current_dogstepname($da['idcategory']);
						break;
						case 'idcategory': break;
						case 'direction':
							$response['data'][$j][$fields[$i]] = current_direction($da[$fields[$i]]);
						break;
						case 'tip':
							$response['data'][$j][$fields[$i]] = current_dogtype($da[$fields[$i]]);
						break;
						case 'status_close':
							$status = $db -> getOne("SELECT title FROM ".$sqlname."dogstatus WHERE sid='".$da['sid']."' and identity = '$identity'");
							$response['data'][$j][$fields[$i]] = $status;
						break;
						default:
							$response['data'][$j][$fields[$i]] = $da[$fields[$i]];
						break;

					}

				}

				if($params['bankinfo'] == 'yes'){
					$bankinfo = get_client_recv($da['payer'],'yes');
					foreach($bankInfoField as $key => $value){
						$response['data'][$j]['bankinfo'][$value] = $bankinfo[$value];
					}
				}

				if($params['invoice'] != 'no') {
					//составим список счетов и их статус
					$res = $db -> query("SELECT * FROM " . $sqlname . "credit WHERE did='" . $da['did'] . "' and identity = '$identity' ORDER by crid");
					while ($daa = $db -> fetch($res)) {

						$response['data'][ $j ]['invoice'][] = array('id' => $daa['crid'], 'invoice' => $daa['invoice'], 'date' => cut_date($daa['datum']), 'summa' => $daa['summa_credit'], 'nds' => $daa['nds_credit'], 'do' => $daa['do'], 'date_do' => $daa['invoice_date'], 'contract' => $daa['invoice_chek'], 'rs' => $daa['rs'], 'tip' => $daa['tip']);
					}
				}

				$j++;

			}

			$response['count'] = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."dogovor WHERE did > 0 ".$sort." and identity = '$identity'");

		break;
		case 'info':

			if($params['uid'] != '') $s = "uid = '".$params['uid']."'";
			elseif($params['did'] != '') $s = "did = '".$params['did']."'";

			$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

			if($did < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Сделка не найдена в пределах аккаунта указанного пользователя.";

			}
			elseif($did > 0){

				$da = get_dog_info($did,'yes');
				if(count($da) > 0){
					for($i=0;$i < count($fields);$i++){

						switch($fields[$i]){

							case 'iduser':
								$response['data'][$fields[$i]] = current_userlogin($da[$fields[$i]]);
								$response['data']['userUID'] = current_userUID($da[$fields[$i]]);
							break;
							case 'clid':
								$response['data'][$fields[$i]] = $da[$fields[$i]];
								$response['data']['clientname'] = current_client($da[$fields[$i]]);
							break;
							case 'payer':
								$response['data'][$fields[$i]] = $da[$fields[$i]];
								$response['data']['payername'] = current_client($da[$fields[$i]]);
							break;
							case 'step':
								$response['data'][$fields[$i]] = current_dogstepname($da['idcategory']);
							break;
							case 'idcategory': break;
							case 'direction':
								$response['data'][$fields[$i]] = current_direction($da[$fields[$i]]);
							break;
							case 'tip':
								$response['data'][$fields[$i]] = current_dogtype($da[$fields[$i]]);
							break;
							case 'status_close':
								$status = $db -> getOne("SELECT title FROM ".$sqlname."dogstatus WHERE sid='".$da['sid']."' and identity = '$identity'");
								$response['data'][$fields[$i]] = $status;
							break;
							default:
								$response['data'][$fields[$i]] = $da[$fields[$i]];
							break;

						}

					}

					if($params['bankinfo'] == 'yes'){
						$bankinfo = get_client_recv($da['payer'],'yes');
						foreach($bankInfoField as $key => $value){
							$response['data']['bankinfo'][$value] = $bankinfo[$value];
						}
					}

					if($params['speka'] != 'no') {
						$ress = $db -> query("SELECT * FROM " . $sqlname . "speca WHERE did='" . $did . "' and identity = '$identity' ORDER BY spid");
						while ($da = $db -> fetch($ress)) {

							$response['data']['speka'][] = array("prid" => $da['prid'], "artikul" => $da['artikul'], "title" => $da['title'], "kol" => $da['kol'], "dop" => $da['dop'], "price" => $da['price'], "price_in" => $da['price_in'], "nds" => $da['nds'], "comments" => $da['comments']);
						}
					}

					//составим список счетов и их статус
					if($params['invoice'] != 'no') {
						$res = $db -> query("SELECT * FROM " . $sqlname . "credit WHERE did='" . $did . "' and identity = '$identity' ORDER by crid");
						while ($daa = $db -> fetch($res)) {
							$response['data']['invoice'][] = array('id' => $daa['crid'], 'invoice' => $daa['invoice'], 'date' => cut_date($daa['datum']), 'summa' => $daa['summa_credit'], 'nds' => $daa['nds_credit'], 'do' => $daa['do'], 'date_do' => $daa['invoice_date'], 'contract' => $daa['invoice_chek'], 'rs' => $daa['rs'], 'tip' => $daa['tip']);
						}
					}

				}
				else{
					$response['result'] = 'Error';
					$response['error']['code'] = '404';
					$response['error']['text'] = "Не найдено";
				}
			}
			elseif($did < 1 and $params['uid'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры сделки";

			}
			else{

				$response['result'] = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Не найдено";

			}

		break;
		case 'add':

			if($params['user'] == '') $params['iduser'] = $iduser;
			else $params['iduser'] = current_userbylogin($params['user']);

			$clid = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE clid = '".intval($params['clid'])."' and identity = '$identity'");
			$payer = $db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE clid = '".intval($params['payer'])."' and identity = '$identity'");

			if($params['clid'] == '' and $params['payer'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - clid и payer клиента";

			}
			if($clid > 0 or $payer > 0){

				//проверка, что есть название клиента
				if($params['title'] != ''){

					if($payer > 0 and $clid < 1) $params['clid'] = $payer;
					if($payer < 1 and $clid > 0) $params['payer'] = $clid;

					if(isset($params['datum_plan']) and strtotime($params['datum_plan']) != '') $params['datum_plan'] = date('Y-m-d', strtotime($params['datum_plan']));
					else $params['datum_plan'] = strftime('%Y-%m-%d', mktime(date('H'),date('i'),date('s'), date('m'), date('d')+14, date('Y')));

					$i = 0;
					foreach($params as $key => $value){
						if(in_array($key, $fields) and $key != 'idcategory') {

							$name[$i] = $key;
							$data[$i] = $value;

							switch($name[$i]){
								case 'datum':
									$data[$i] = "'".current_datum()."'";
								break;
								case 'iduser':
									$data[$i] = "'".$data[$i]."'";
								break;
								case 'title':
									$dNum = generate_num('dogovor');
									if($dNum != '') $data[$i] = "'".$dNum.": ".$data[$i]."'";
									else $data[$i] = "'".$data[$i]."'";
								break;
								case 'clid':
									$data[$i] = "'".intval($data[$i])."'";
								break;
								case 'payer':
									if($data[$i] < 1) $data[$i] = $params['clid'];
									$data[$i] = "'".intval($data[$i])."'";
								break;
								case 'mcid':
									if($data[$i] < 1) $data[$i] = $mcDefault;
									$data[$i] = "'".intval($data[$i])."'";
								break;
								case 'idcategory':
								case 'datum_izm':
								case 'des_fact':
								case 'kol_fact':
								case 'speka':
								break;
								case 'step':
									$name[$i] = 'idcategory';
									$data[$i] = "'".getStep(intval($data[$i]))."'";
								break;
								case 'kol':
								case 'marga':
									$data[$i] = "'".pre_format($data[$i])."'";
								break;
								case 'direction':
									$data[$i] = getDirection(untag($data[$i]));
									if($data[$i] < 1) $data[$i] = $dirDefault;
									$data[$i] = "'".intval($data[$i])."'";
								break;
								case 'tip':
									$data[$i] = getDogTip(untag($data[$i]));
									if($data[$i] < 1) $data[$i] = $tipDefault;
									$data[$i] = "'".intval($data[$i])."'";
								break;

								default: $data[$i] = "'".untag($data[$i])."'"; break;
							}
							$i++;
						}
					}

					//формируем запрос для добавления
					$names = "did,datum,".implode(",", $name).",autor,identity";
					$datas = "null,'".current_datum()."',".implode(",", $data).",'".$iduser."','".$identity."'";

					if(count($name) > 0){

						try {

							$db -> query("insert into ".$sqlname."dogovor (".$names.") values(".$datas.")");

							$did = $db -> insertId();

							//меняем счетчик договоров
							if($dNum != ''){

								$cnum = $db -> getOne("select dNum from ".$sqlname."settings WHERE id = '".$identity."'") + 1;

								$db -> query("update ".$sqlname."settings set dNum ='$cnum' where id = '$identity'");

							}

							$response['result'] = 'Успешно';
							$response['data'] = $did;

							//запись в историю активности
							$db -> query("insert into ".$sqlname."history (cid,iduser,clid,did,datum,des,tip,identity) values(null, '".$iduser."', '".$clid."', '".$did."', '".current_datumtime()."', 'Добавлена сделка через API', 'СобытиеCRM','$identity')");

							//добавим спецификацию
							if(count($params['speka']) > 0){

								$summa = 0; $summain = 0;

								for($j=0; $j < count($params['speka']);$j++){

									if($params['speka'][$j]['dop'] < 1) $params['speka'][$j]['dop'] = 1;

									$db -> query("INSERT INTO ".$sqlname."speca (spid,did,prid,artikul,title,price,price_in,kol,edizm,nds,dop,comments,identity) VALUES (null, '".$did."', '".$params['speka'][$j]['prid']."', '".$params['speka'][$j]['artikul']."', '".$params['speka'][$j]['title']."', '".pre_format($params['speka'][$j]['price'])."', '".pre_format($params['speka'][$j]['price_in'])."', '".$params['speka'][$j]['kol']."', '".$params['speka'][$j]['edizm']."', '".$params['speka'][$j]['nds']."', '".$params['speka'][$j]['dop']."', '".$params['speka'][$j]['comments']."','$identity')");

									$summa += pre_format($params['speka'][$j]['price']) * pre_format($params['speka'][$j]['kol']) * pre_format($params['speka'][$j]['dop']);
									$summain += pre_format($params['speka'][$j]['price_in']) * pre_format($params['speka'][$j]['kol']) * pre_format($params['speka'][$j]['dop']);


								}

								$marga = $summa - $summain;
								$db -> query("UPDATE ".$sqlname."dogovor SET kol ='$summa', marga = '$marga', calculate = 'yes' WHERE did = $did");

							}

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
					$response['error']['code'] = '406';
					$response['error']['text'] = "Отсутствуют параметры - Название сделки";

				}
			}
			else{
				$response['result'] = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Клиент или Плательщик не найден.";
			}

		break;
		case 'update':

			$uid = untag($params["uid"]);

			//проверка принадлежности did к данному аккаунту и вообще её существование
			if($params['did'] > 0) $s = "did = '$params[did]'";
			elseif($uid != '') $s = "uid = '$uid'";

			$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

			//$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE did = '".$params['did']."' ".get_people($iduser)." and identity = '$identity'");

			if($did < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

			}
			else{

				$i = 0;
				foreach($params as $key => $value){
					if(in_array($key, $fields) and $key != 'idcategory') {

						$name[$i] = $key;
						$data[$i] = $value;

						switch($name[$i]){
							case 'datum':
								$data[$i] = "'".current_datum()."'";
							break;
							case 'datum':
								$data[$i] = "'".current_datum()."'";
							break;
							case 'iduser':
								$data[$i] = "'".$data[$i]."'";
							break;
							case 'title':
								$data[$i] = "'".$data[$i]."'";
							break;
							case 'clid':
								$data[$i] = "'".intval($data[$i])."'";
							break;
							case 'payer':
								if($data[$i] < 1) $data[$i] = $params['clid'];
								$data[$i] = "'".intval($data[$i])."'";
							break;
							case 'mcid':
								if($data[$i] < 1) $data[$i] = $mcDefault;
								$data[$i] = "'".intval($data[$i])."'";
							break;
							case 'idcategory':
							case 'datum_izm':
							case 'des_fact':
							case 'kol_fact':
							break;
							case 'step':
								$name[$i] = 'idcategory';
								$data[$i] = "'".getStep(intval($data[$i]))."'";
							break;
							case 'status_close':
								$name[$i] = 'sid';
								$data[$i] = "'".getStatusClose($data[$i])."'";
							break;
							case 'kol':
							case 'marga':
								$data[$i] = "'".pre_format($data[$i])."'";
							break;
							case 'direction':
								$data[$i] = getDirection(untag($data[$i]));
								if($data[$i] < 1) $data[$i] = $dirDefault;
								$data[$i] = "'".intval($data[$i])."'";
							break;
							case 'tip':
								$data[$i] = getDogTip(untag($data[$i]));
								if($data[$i] < 1) $data[$i] = $tipDefault;
								$data[$i] = "'".intval($data[$i])."'";
							break;

							default: $data[$i] = "'".untag($data[$i])."'"; break;
						}
						$i++;
					}
				}

				$datad = get_dog_info($did, 'yes');

				$datas = '';
				for($i=0;$i<count($data);$i++){

					$datas.= " ".$name[$i]." = ".$data[$i].",";

					if(!in_array($name, array('did'))){

						if($name[$i] != 'idcategory'){
							$newParams[$name[$i]] = substr($data[$i],1,-1);//массив новых параметров
							$oldParams[$name[$i]] = $datad[$name[$i]];//массив старых параметров
						}
						else{
							$newParams[$name[$i]] = current_dogstepname(substr($data[$i],1,-1));//массив новых параметров
							$oldParams[$name[$i]] = current_dogstepname($datad[$name[$i]]);//массив старых параметров
						}

					}

				}
				$datas.= " datum_izm = '".current_datumtime()."'";

				$log = doLogger('did', $did, $newParams, $oldParams);

				//добавим спецификацию
				if(count($params['speka']) > 0){

					//удалим предыдущую спеку
					$db -> query("delete from ".$sqlname."speca where did = '".$did."' and identity = '$identity'");

					$summa = 0; $summain = 0; $spekaGood = 0; $spekaBad = 0; $sper = array();

					for($j=0; $j < count($params['speka']);$j++){

						if($params['speka'][$j]['dop'] < 1) $params['speka'][$j]['dop'] = 1;

						try {

							$db -> query("INSERT INTO ".$sqlname."speca (spid,did,prid,artikul,title,price,price_in,kol,edizm,nds,dop,comments,identity) VALUES (null, '".$did."', '".$params['speka'][$j]['prid']."', '".$params['speka'][$j]['artikul']."', '".$params['speka'][$j]['title']."', '".pre_format($params['speka'][$j]['price'])."', '".pre_format($params['speka'][$j]['price_in'])."', '".$params['speka'][$j]['kol']."', '".$params['speka'][$j]['edizm']."', '".$params['speka'][$j]['nds']."', '".$params['speka'][$j]['dop']."', '".$params['speka'][$j]['comments']."','$identity')");

							$summa = $summa + pre_format($params['speka'][$j]['price']) * pre_format($params['speka'][$j]['kol']) * pre_format($params['speka'][$j]['dop']);
							$summain = $summain + pre_format($params['speka'][$j]['price_in']) * pre_format($params['speka'][$j]['kol']) * pre_format($params['speka'][$j]['dop']);
							$spekaGood++;

						}
						catch (Exception $e){

							$spekaBad++;
							$sper[] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

						}

					}

					if($spekaGood > 0) $apdx.= "Обновлена спецификация - всего ".$spekaGood." позиций. ";
					if($spekaBad > 0) $apdx.= "Ошибки при добавлении спецификации - всего ".$spekaBad." позиций. Описание ошибок - ".implode(", ", $sper);

					$marga = $summa - $summain;
					$db -> query("UPDATE ".$sqlname."dogovor SET kol ='$summa', marga = '$marga', calculate = 'yes' WHERE did = '$did'");

				}

				//print '<br><code>update ".$sqlname."dogovor set '.$datas.' where did = \''.$did.'\' and identity = \''.$identity.'\'"</code><br>';
				//print '<br><code>'.$log.'</code><br>';
				//exit();

				if($log != 'none'){

					try {

						$db -> query("update ".$sqlname."dogovor set ".$datas." where did = '".$did."' and identity = '$identity'");

						$response['result'] = 'Успешно. '.$apdx;
						$response['data'] = $did;

						$db -> query("insert into ".$sqlname."history (cid,iduser,clid,did,datum,des,tip,identity) values(null, '".$iduser."', '".$clid."', '".$did."', '".current_datumtime()."', '".$log."', 'ЛогCRM','$identity')");

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

					}

				}
				else {
					$response['result'] = 'Данные корректны, но идентичны имеющимся.'.$apdx;
					$response['data'] = $did;
				}

			}

			if($params['did'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - did сделки";

			}

		break;
		case 'changestep':

			$uid = untag($params["uid"]);

			//проверка принадлежности did к данному аккаунту и вообще её существование
			if($params['did'] > 0) $s = "did = '$params[did]'";
			elseif($uid != '') $s = "uid = '$uid'";

			//проверка принадлежности clid к данному аккаунту
			$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");
			//$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE did = '".$params['did']."' ".get_people($iduser)." and identity = '$identity'");

			if($did < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

			}
			else{

				if($params['step'] == '') {

					$response['result'] = 'Error';
					$response['error']['code'] = '405';
					$response['error']['text'] = "Отсутствуют параметры - Новый этап";

				}
				else{

					$oldParams['idcategory'] = current_dogstepid($did);
					$newParams['idcategory'] = getStep($params['step']);

					if($params['reason'] != '') $reason = '<br>============================<br>Описание: '.untag($params['reason']);

					$log = doLogger('did', $did, $newParams, $oldParams);

					if($log != 'none'){

						try {

							$db -> query("update ".$sqlname."dogovor set idcategory = '".$newParams['idcategory']."' where did = '".$did."' and identity = '$identity'");

							if(current_dogstepname($newParams['idcategory']) > current_dogstepname($oldParams['idcategory'])){//если новый этап больше старого

								//найдем категорию Кт. соответствующие текущему этапу
								$rec = $db -> getRow("SELECT * FROM ".$sqlname."complect_cat WHERE dstep = '".$oldParams['idcategory']."' and identity = '$identity'");
								$ccid = $rec["ccid"];
								$cctitle = $rec["title"];
								$dstep = $rec["dstep"];

								//найдем КТ, которая есть в этой сделке
								$cpid = $db -> getOne("SELECT id FROM ".$sqlname."complect WHERE ccid = '".$ccid."' and did = '".$did."' and identity = '$identity' ORDER BY id");

								//Отметим выполненной КТ
								if($cpid > 0){

									$ctitle = $db -> getOne("SELECT title FROM ".$sqlname."complect_cat WHERE ccid = '".$ccid."' and identity = '$identity'");
									$cstep = current_dogstepname($dstep);//этап, связанный с КТ

									$db -> query("update ".$sqlname."complect set data_fact='".current_datumtime()."', doit = 'yes' where id = '".$cpid."' and identity = '$identity'");
									$apdx = "Поставлена отметка о выполнении Контрольной точки - ".$ctitle.".";
									if($apdx != '') $ap = "; ".$apdx;

								}

							}

							$db -> query("insert into ".$sqlname."history (cid,iduser,did,datum,des,tip,identity) values(null, '".$iduser."', '".$did."', '".current_datumtime()."', '".$log.$reason.$ap."', 'ЛогCRM','$identity')");

							$response['result'] = 'Успешно.';
							$response['data'] = $did;
							if($apdx != '') $response['message'] = $apdx;

						}
						catch (Exception $e){

							$response['result'] = 'Error';
							$response['error']['code'] = '500';
							$response['error']['text'] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

						}

						//добавляем смену этапа в лог
						DealStepLog($did, $newParams['idcategory']);

					}
					else {
						$response['result'] = 'Данные корректны, но идентичны имеющимся.';
						$response['data'] = $did;
					}

				}

			}

			if($params['did'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Отсутствуют параметры - did сделки";

			}

		break;
		case 'close':

			$uid = untag($params["uid"]);

			//проверка принадлежности did к данному аккаунту и вообще её существование
			if($params['did'] > 0) $s = "did = '$params[did]'";
			elseif($uid != '') $s = "uid = '$uid'";

			//проверка принадлежности clid к данному аккаунту
			$re = $db -> getRow("SELECT * FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");
			//$re    = $db -> getRow("SELECT * FROM ".$sqlname."dogovor WHERE did = '".$params['did']."' ".get_people($iduser)." and identity = '$identity'");
			$did   = $re['did'];
			$kol   = $re['kol'];
			$marga = $re['marga'];
			$clid  = $re['clid'];
			$close = $re['close'];

			if($did < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

			}
			elseif($close == 'yes'){
				$response['result'] = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Сделка уже закрыта.";
			}
			else{

				if($params['kol_fact'] == '') $params['kol_fact'] = $kol;
				if($params['marga'] == '') $params['marga'] = $marga;

	//			print $params['status_close']."<br>";
				$sid = getStatusClose($params['status_close']);

				if($sid > 0){

					try {

						$db -> query("update ".$sqlname."dogovor set close = 'yes', datum_close = '".current_datum()."', sid = '$sid', kol_fact = '".pre_format($params['kol_fact'])."', marga = '".$params['marga']."', des_fact = '".untag($params['des_fact'])."' where did = $did and identity = '$identity'");

						$response['result'] = 'Успешно.';
						$response['data'] = $did;

						$mes = "Возможная сделка закрыта со статусом ".$params['status_close'];

						//внесем запись о дате последней сделки
						$db -> query("update ".$sqlname."clientcat set last_dog = '".current_datum()."' where clid = '".$clid."' and identity = '$identity'");

						$db -> query("insert into ".$sqlname."history (cid,iduser,did,datum,des,tip, identity) values(null, '".$iduser."', '".$did."', '".current_datumtime()."', '".$mes."', 'СобытиеCRM', '$identity')");

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
					$response['error']['text'] = "Отсутствуют или не корректны параметры - Статус закрытия сделки";

				}
			}

			if($params['did'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Отсутствуют параметры - did сделки";

			}

		break;
		case 'delete':

			$uid = untag($params["uid"]);

			//проверка принадлежности did к данному аккаунту и вообще её существование
			if($params['did'] > 0) $s = "did = '$params[did]'";
			elseif($uid != '') $s = "uid = '$uid'";

			//проверка принадлежности clid к данному аккаунту
			$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");
			//$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE did = '".$params['did']."' ".get_people($iduser)." and identity = '$identity'");

			if($did < 1){

				$response['result'] = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

			}
			else{

				//Проверим оплаченные счета
				$credit = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."credit WHERE did='".$did."' and identity = '$identity'");

				if ($credit == 0){

					try {

						$db -> query("delete from ".$sqlname."dogovor where did = '".$did."' and identity = '$identity'");

						$db -> query("delete from ".$sqlname."history where did = '".$did."' and identity = '$identity'");

						//Удалим всю связанные файлы
						$result1 = $db -> query("select * from ".$sqlname."file WHERE did='".$did."' and identity = '$identity'");
						while ($data_array1 = $db -> fetch($result1)) {
							@unlink("../files/".$data_array1['fname']);
							$db -> query("delete from ".$sqlname."file where fid = '".$data_array1['fid']."' and identity = '$identity'");
							$f++;
						}

						//Удалим спецификацию
						$db -> query("delete from ".$sqlname."speca where did = '".$did."' and identity = '$identity'");

						//Удалим контрольные точки
						$db -> query("delete from ".$sqlname."complect where did = '".$did."' and identity = '$identity'");

						logger('12','Удалена сделки '.current_dogovor($did),$iduser1);

						$response['result'] = 'Успешно.';
						$response['data'] = $did;

					}
					catch (Exception $e){

						$response['result'] = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();

					}

				}
				else{

					$response['result'] = 'Error';
					$response['error']['code'] = '407';
					$response['error']['text'] = "Удаление сделки не возможно - есть Оплаченные/Неоплаченые счета";

				}
			}

			if($params['did'] == '') {

				$response['result'] = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Отсутствуют параметры - did сделки";

			}

		break;
		case 'addinvoice':

			$mes = array();

			$did = intval($params["did"]);
			$uid = untag($params["uid"]);

			//Находим clid, pid
			if($did > 0) $s = "did = '$did'";
			elseif($uid != '') $s = "uid = '$uid'";
			else $s = '';

			$resu = $db -> getRow("SELECT did, pid, iduser FROM ".$sqlname."dogovor WHERE $s and identity = '$identity'");

			$did = $resu['did'];
			$pid = $resu["pid"];
			$iduser = $resu["iduser"];

			if($did > 0){


				$datum = $params["date"].' '.date('G').':'.date('i').':00';
				if($params["date_plan"] != '') $datum_credit = $params["date_plan"]; else $datum_credit = current_datum();
				if($params["date_do"] != '') $invoice_date = $params["date_do"]; else $invoice_date = current_datum();

				$invoice = $params['invoice'];
				$invoice_chek = $params['contract'];
				$rs = $params["rs"];
				$tip = $params["tip"];
				$do = $params["do"];

				$summa_credit = pre_format($params["summa"]);
				$nds_credit = pre_format($params["nds"]);

				if($params["summa"] == ''){
					$summa_credit = getDogData($did, 'kol');
				}

				//проверка наличия счета
				$count = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."credit WHERE invoice='$invoice' and identity = '$identity'");

				//проверяем расчетный счет
				$resu = $db -> getRow("SELECT COUNT(*) as count, cid FROM ".$sqlname."mycomps_recv WHERE id='".$rs."' and identity = '$identity'");
				$count = $resu["count"];
				$mcid  = $resu["cid"];

				if($count < 1){
					$resu = $db -> getRow("SELECT id,cid FROM ".$sqlname."mycomps_recv WHERE isDefault='yes' and identity = '$identity' LIMIT 1");
					$rs = $resu["id"];
					$mcid = $resu["cid"];
				}

				if($invoice == '') {
					$invoices = generate_num('invoice','');
					$igen = 'yes';
				}
				else $invoices = $invoice;

				/**
				 * todo: Добавить функционал для сервисных сделок выставление счета + обновление суммы сделки + отправка счета по email
				 */

				try {

					$db -> query("insert into ".$sqlname."credit (crid, did, clid, pid, datum, datum_credit, summa_credit, nds_credit, iduser, invoice, invoice_chek, rs, tip,identity) values (null, '".$did."', '".$clid."', '".$pid."', '".$datum."', '".$datum_credit."', '".pre_format($summa_credit)."', '".pre_format($nds_credit)."', '".$iduser."', '".$invoices."', '".$invoice_chek."', '".$rs."', '".$tip."','$identity')");

					$id = $db -> insertId();

					if($invoice == '' and $igen == 'yes') {

						//обновим счетчик счетов
						$cnum = $db -> getOne("select * from ".$sqlname."settings WHERE id = '".$identity."'") + 1;
						$db -> query("update ".$sqlname."settings set inum = '".$cnum."' WHERE id = '$identity'");

					}
					$mes[] = "Счет добавлен в платежи";

					//отметим оплату
					if($do == 'yes'){

						try {

							$db -> query("update ".$sqlname."credit set do = 'on', invoice_date = '".$invoice_date."' where crid = '".$id."' and identity = '$identity'");

							$mes[] = "Счет отмечен оплаченным";

							//Внесем деньги на расчетный счет
							rsadd($rs, $summa_credit, 'plus');

						}
						catch (Exception $e){

							$mes[] = 'Ошибка отметки оплаты: '.$e-> getMessage(). ' в строке '. $e->getCode();

						}

					}

				}
				catch (Exception $e){

					$mes[] = 'Ошибка добавления счета: '.$e-> getMessage(). ' в строке '. $e->getCode();

				}

				$mes = implode(", ", $mes);

				//Внесем запись в историю активностей
				$db -> query("insert into ".$sqlname."history (cid,iduser,did,datum,des,tip,identity) values(null, '".$iduser."', '".$did."', '".current_datumtime()."', '".$mes."', 'СобытиеCRM','$identity')");

				$response['result'] = $mes;
				$response['data']['id'] = $id;
				$response['data']['invoice'] = $invoices;

			}
			else{
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Сделка с указанным did не найдена в пределах аккаунта указанного пользователя';
			}

		break;
		case 'addpaiment':

			$mes = array();

			if($params["date_do"] != '') $invoice_date = $params["date_do"]; else $invoice_date = current_datum();

			$invoice = $params['invoice'];
			$crid = $params['id'];

			//проверяем расчетный счет
			$resu = $db -> getRow("SELECT crid, did, rs, summa_credit FROM ".$sqlname."credit WHERE (invoice='".$invoice."' or crid='".$crid."') and identity = '$identity'");
			$crid = $resu["crid"];
			$did = $resu["did"];
			$rs = $resu["rs"];
			$summa_credit = $resu["summa_credit"];

			if($crid > 0){

				//отметим оплату
				try {

					$db -> query("update ".$sqlname."credit set do = 'on', invoice_date = '".$invoice_date."' where crid = '".$crid."' and identity = '$identity'");

					$mes[] = "Счет отмечен оплаченным";

					//Внесем деньги на расчетный счет
					rsadd($rs, $summa_credit, 'plus');

				}
				catch (Exception $e){

					$mes[] = 'Ошибка отметки оплаты: '. $e-> getMessage(). ' в строке '. $e->getCode();

				}

				$mes = implode(", ", $mes);

					//Внесем запись в историю активностей
				$db -> query("insert into ".$sqlname."history (cid,iduser,did,datum,des,tip,identity) values(null, '".$iduser."', '".$did."', '".current_datumtime()."', '".$mes."', 'СобытиеCRM','$identity')");

				$response['result'] = "Успешно.";
				$response['data'] = $mes;

			}
			else{
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Счет по номеру не найден';
			}

		break;
		case 'addpaimentpart':

			$mes = array();

			if($params["date_do"] != '') $invoice_date = $params["date_do"]; else $invoice_date = current_datum();

			$invoice = $params['invoice'];
			$crid = $params['id'];
			$summa = pre_format($params['summa']);

			//проверяем расчетный счет
			$resu = $db -> getRow("SELECT * FROM ".$sqlname."credit WHERE (invoice='".$invoice."' or crid='".$crid."') and do != 'on' and identity = '$identity'");
			$crid = $resu["crid"];
			$clid = $resu["clid"];
			$pid = $resu["pid"];
			$did = $resu["did"];
			$datum = $resu["datum"];
			$datum_credit = $resu["datum_credit"];
			$rs = $resu["rs"];
			$user = $resu["iduser"];
			$summa_credit = $resu["summa_credit"];
			$invoice_chek = $resu["invoice_chek"];

			$delta = $summa_credit - $summa;

			if($crid > 0){

				if($delta >= 0){

					//добавим оплату
					try {

						$db -> query("update ".$sqlname."credit set do = 'on', summa_credit = '$summa', invoice_date = '$invoice_date' where crid = '".$crid."' and identity = '$identity'");

						$mes[] = "Внесена оплата по графику ".num_format($summa)." ".$valuta;

						//Внесем деньги на расчетный счет
						rsadd($rs, $summa, 'plus');

						$rtitle = $db -> getOne("SELECT title FROM ".$sqlname."mycomps_recv WHERE id='".$rs."' and identity = '$identity'");
						if($rtitle != ''){

							$mes[] = 'На р/с '.$rtitle.' внесена оплата в размере '.num_format($summa).' '.$valuta;

						}

					}
					catch (Exception $e){

						$mes[] = 'Ошибка: '. $e-> getMessage(). ' в строке '. $e->getCode();

					}

					//добавим новую запись в график на остаток
					if($delta > 0){

						try {

							$db -> query("insert into ".$sqlname."credit (crid,datum,did,clid,pid,invoice,datum_credit,summa_credit,invoice_chek,iduser,rs,identity) values (null, '".$datum."', '".$did."', '".$clid."', '".$pid."', '".$invoice."', '".$datum_credit."', '".pre_format($delta)."', '$invoice_chek', '".$user."', '".$rs."','$identity')");
							$mes[] = "Поступившая оплата отличается от планируемой. Добавлен дополнительный платеж ".num_format($delta)." ".$valuta;

						}
						catch (Exception $e){

							$mes[] = 'Ошибка: '. $e-> getMessage(). ' в строке '. $e->getCode();

						}

					}

					$mes = implode(", ", $mes);

					//Внесем запись в историю активностей
					$db -> query("insert into ".$sqlname."history (cid,iduser,did,datum,des,tip,identity) values(null, '".$iduser."', '".$did."', '".current_datumtime()."', '".$mes."', 'СобытиеCRM','$identity')");

					$response['result'] = "Успешно.";
					$response['data'] = $mes;

				}
				else{

					$response['error']['code'] = '405';
					$response['error']['text'] = 'Сумма превышает сумму счета';

				}

			}
			else{
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Счет по номеру не найден';
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
?>