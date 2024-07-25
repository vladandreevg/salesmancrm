<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

use Salesman\Deal;
use Salesman\Guides;
use Salesman\UIDs;

global $action;
global $identity, $rootpath, $path;

$response = [];

$deal = new Deal();

switch ($action) {

	/**
	 * справочники
	 */

	//поля
	case 'fields':

		$response['data']['did']       = "Уникальный идентификатор записи";
		$response['data']['uid']       = "Уникальный идентификатор записи во внешней системе";
		$response['data']['datum']     = "Дата создания. YYYY-MM-DD";
		$response['data']['datum_izm'] = "Дата последнего изменения. YYYY-MM-DD";
		$response['data']['clid']      = "Клиент";

		$resf = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'");
		while ($do = $db -> fetch($resf)) {

			if ($do['fld_name'] == 'idcategory') {
				$response['data']['step'] = $do['fld_title'];
			}
			elseif ($do['fld_name'] == 'marg') {
				$response['data']['marga'] = $do['fld_title'];
			}
			elseif ($do['fld_name'] == 'mcid') {
				$response['data']['mcid'] = "ID своей компании";
			}
			else {
				$response['data'][$do['fld_name']] = $do['fld_title'];
			}
		}

		$response['data']['datum_start']  = "Период действия. Начало";
		$response['data']['datum_end']    = "Период действия. Конец";
		$response['data']['close']        = "Признак закрытой сделки";
		$response['data']['datum_close']  = "Дата закрытия. YYYY-MM-DD";
		$response['data']['status_close'] = "Результат закрытия сделки";
		$response['data']['des_fact']     = "Комментарий закрытия сделки";
		$response['data']['kol_fact']     = "Фактическая сумма продажи";

		break;

	//список этапов
	case 'steplist':

		$response['data'] = $deal -> Steps();

		break;

	//направления
	case 'direction':

		$response['data'] = $deal -> Direction();

		break;

	//типы сделок
	case 'type':

		$response['data'] = $deal -> dealTypes();

		break;

	//статусы закрытия
	case 'statusclose':

		$response['data'] = array_values(Guides ::closeStatusPlus((string)$params['filter']));

		break;

	//воронки
	case 'funnel':

		$did       = (int)$params['did'];
		$direction = ( is_numeric($params['direction']) ) ? $params['direction'] : current_direction(0, untag($params['direction']));
		$tip       = ( is_numeric($params['tip']) ) ? $params['tip'] : current_dogtype(0, untag($params['tip']));

		$funnel = getMultiStepList([
			"did"       => $did,
			"direction" => $direction,
			"tip"       => $tip
		]);

		//var_export($funnel);

		$nsteps = $db -> getIndCol("idcategory", "SELECT title, idcategory FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title");

		$steps = [];
		foreach ($funnel['steps'] as $id => $time) {
			$steps[(int)$id] = strtr($id, $nsteps);
		}

		//var_export($steps);

		$defaultName = strtr($funnel['default'], $nsteps);

		/*
		$xsteps = [];
		foreach ($funnel['steps'] as $step => $length){
			$xsteps[(int)$step] = $length;
		}
		$funnel['steps'] = $xsteps;
		*/

		//$funnel = arrayAddAfter($funnel, 0, ["stepsName" => $steps]);
		//$funnel = arrayAddAfter($funnel, 3, ["defaultName" => $defaultName]);

		$funnel['stepsName'] = $steps;
		$funnel['defaultName'] = $defaultName;

		//var_export($funnel);

		$response['data'] = $funnel;

		break;

	/**
	 * Вывод данных по сделкам
	 */

	//список сделок
	case 'list':

		$params['page'] = ((int)$params['offset'] > 0) ? (int)$params['offset'] : 0;
		$params['ord']  = ($params['order'] != '') ? $params['order'] : 'datum';
		$params['tuda'] = ($params['first'] == 'old') ? 'ASC' : 'DESC';

		if (!empty($params['dateStart'])) {
			$params['d1'] = $params['dateStart'];
		}
		if (!empty($params['dateEnd'])) {
			$params['d2'] = $params['dateEnd'];
		}

		$params['word'] = Cleaner($params['word']);

		$data = $deal -> list($params);

		$response['data'] = $data['list'];

		unset($data['list']);

		$response['params'] = $data;
		$response['count'] = $data['count'];

	break;

	//информация по сделке
	case 'info':

		//составляем списки доступных полей для сделок
		$fields = $deal -> Fields((string)$params['fields']);

		if ($params['uid'] != '') {
			$s = "uid = '".$params['uid']."'";
		}
		elseif ($params['did'] != '') {
			$s = "did = '".$params['did']."'";
		}

		$did = (int)$db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

		if ($did == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка не найдена в пределах аккаунта указанного пользователя.";

		}
		elseif ($did > 0) {

			$dealInfo = $deal ::info($did);

			if ($dealInfo['did'] > 0) {

				$deal = [];

				foreach ($fields as $field) {

					switch ($field) {

						case 'iduser':

							$deal[$field]    = (int)$dealInfo[$field];
							$deal['user']    = current_userlogin($dealInfo[$field]);
							$deal['userUID'] = current_userUID($dealInfo[$field]);

							break;
						case 'step':

							$deal[$field]      = (int)$dealInfo['step']['stepid'];
							$deal["stepID"]    = (int)$dealInfo['step']['stepid'];
							$deal["stepTitle"] = $dealInfo['step']['steptitle'];

							break;
						case 'idcategory':
							break;
						case 'direction':

							$deal["directionID"] = (int)$dealInfo[$field];
							$deal[$field]        = $dealInfo['directionName'];

							break;
						case 'tip':

							$deal["tipID"] = (int)$dealInfo[$field];
							$deal[$field]  = $dealInfo['tipName'];

							break;
						case 'status_close':

							$status        = $db -> getOne("SELECT title FROM {$sqlname}dogstatus WHERE sid = '".$dealInfo['sid']."' and identity = '$identity'");
							$deal['close'] = $dealInfo['close'];

							break;
						case 'dog_num':

							$deal["contract"] = $dealInfo['contract'];

							break;
						default:

							$deal[$field] = $dealInfo[$field];

							/*
							if($field_types[ $field ] == "int"){

								$deal[ $field ] = (int)$dealInfo[ $field ];

							}
							elseif(in_array($field_types[ $field ], ["float","double"])){

								$deal[ $field ] = (float)$dinfo[ $field ];

							}
							else {

								$deal[ $field ] = $dinfo[ $field ] == "" ? $dinfo[ $field ] : NULL;

							}
							*/

							break;

					}

				}

				if ($params['client']) {
					$deal['client'] = $dealInfo['client'];
					unset($deal['client']['dostup']);
				}
				else{
					unset($deal['client']);
				}

				if ($params['payer']) {
					$deal['payer'] = $dealInfo['payer'];
					unset($deal['payer']['dostup']);
				}
				else{
					unset($deal['payer']);
				}

				if ($dealInfo['client']['pid'] > 0 && !empty($dealInfo['person']) && $params['contact']) {
					$deal['contact'] = $dealInfo['person'];
				}
				unset($deal['person']);

				if ($params['bankinfo']) {
					$deal['bankinfo'] = $dealInfo['recv'];
				}
				unset($deal['recv']);


				if ($params['speka']) {
					$deal['speka'] = $dealInfo['speca'];
				}
				unset($deal['speca']);

				//составим список счетов и их статус
				if ($params['invoice']) {
					$deal['invoice'] = $dealInfo['invoice'];
				}
				unset($deal['invoice']);

				if ($params['uids']) {

					$ruids = UIDs ::info(["did" => $did]);
					if ($ruids['result'] == 'Success') {
						$deal['uids'] = $ruids['data'];
					}

				}

				if( $params['contract'] && $dealInfo['contract']['deid'] > 0 ){
					$deal['contract'] = $dealInfo['contract'];
				}
				unset($deal['contract']);

				if( empty($deal['close']['close']) ){
					unset($deal['close']);
				}

				$response['data'] = $deal;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		elseif ($params['uid'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры сделки";

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

		break;

	//добавление сделки
	case 'add':

		if ($params['user'] == '') {
			$params['iduser'] = $iduser;
		}
		else {
			$params['iduser'] = current_userbylogin($params['user']);
		}

		$clid  = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE clid = '".(int)$params['clid']."' and identity = '$identity'");
		$payer = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE clid = '".(int)$params['payer']."' and identity = '$identity'");

		if ($params['clid'] == '' && $params['payer'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid и payer клиента";

		}
		if ($clid > 0 || $payer > 0) {

			//проверка, что есть название клиента
			if ($params['title'] != '') {

				if (isset($params['speka'])) {
					$params['calculate'] = "yes";
				}

				if (!isset($params['clid']) && (int)$params['payer'] > 0) {
					$params['clid'] = $params['payer'];
				}
				elseif (!isset($params['payer']) && (int)$params['clid'] > 0) {
					$params['payer'] = $params['clid'];
				}

				if (isset($params['pid_list'])) {
					$params['pid_list'] = str_replace(",", ";", $params['pid_list']);
				}
				$params['autor'] = $iduser1;

				$Deal     = new Deal();
				$response = $Deal -> add($params);

				//$response['params'] = $params;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 406;
				$response['error']['text'] = "Отсутствуют параметры - Название сделки";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 407;
			$response['error']['text'] = "Клиент или Плательщик не найден.";

		}

		break;

	//обновление сделки
	case 'update':

		$uid = untag($params["uid"]);

		unset($params['step'], $params['idcategory']);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ((int)$params['did'] > 0) {
			$s = "AND did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "AND uid = '$uid'";
		}

		$did = (int)$db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE did > 0 $s ".get_people($iduser)." and identity = '$identity'");

		if ($params['did'] == '' && $params['uid'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - did и uid сделки";

		}

		if ($did == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		else {

			if (isset($params['speka'])) {
				$params['calculate'] = "yes";
			}

			if (isset($params['pid_list'])) {
				$params['pid_list'] = str_replace(",", ";", $params['pid_list']);
			}

			if (!isset($params['clid']) && (int)$params['payer'] > 0) {
				$params['clid'] = (int)$params['payer'];
			}
			elseif (!isset($params['payer']) && (int)$params['clid'] > 0) {
				$params['payer'] = (int)$params['clid'];
			}

			$params['fromapi'] = true;

			$Deal     = new Deal();
			$response = $Deal -> fullupdate((int)$params['did'], $params);

		}

		break;

	//закрытие сделки
	case 'close':

		$Deal     = new Deal();
		$response = $Deal -> changeClose($params['did'], $params);

		break;

	//смена этапа
	case 'change.step':

		$uid = untag($params["uid"]);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ($params['did'] > 0) {
			$s = "did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		//проверка принадлежности clid к данному аккаунту
		$did = $db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

		if ($did < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		elseif ($params['step'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - Новый этап";

		}
		else {

			$params['step'] = getStep($params['step']);

			$Deal     = new Deal();
			$response = $Deal -> changestep($params['did'], $params);

		}

		if ($params['did'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		break;

	//заморозка
	case 'change.freeze':

		$Deal     = new Deal();
		$response = $Deal -> changeFreeze($params['did'], $params['date']);

		break;

	//смена этапа
	case 'change.user':

		$uid = untag($params["uid"]);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ($params['did'] > 0) {
			$s = "did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		if($params['client']){
			$params['client_send'] = "yes";
		}
		if($params['person']){
			$params['person_send'] = "yes";
		}

		//проверка принадлежности clid к данному аккаунту
		$did = (int)$db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE $s AND iduser IN (".yimplode(",", get_people($iduser, "yes")).") and identity = '$identity'");

		if (empty($params['did']) && empty($params['uid'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Отсутствуют параметры - did или uid сделки";

		}
		elseif ($did == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		else {

			if (!empty($params['user'])) {
				$params['newuser'] = current_userbylogin($params['user']);
			}
			elseif (!empty($params['iduser'])) {
				$params['newuser'] = (int)$params['iduser'];
			}
			else {
				$params['newuser'] = $iduser;
			}

			if ($params['client.send'] == "yes") {
				$params['client_send'] = "yes";
			}
			if ($params['person.send'] == "yes") {
				$params['person_send'] = "yes";
			}

			$Deal     = new Deal();
			$response = $Deal -> changeuser((int)$params['did'], $params);

		}

		break;

	//удаление сделки
	case 'delete':

		$uid = untag($params["uid"]);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ($params['did'] > 0) {
			$s = "did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		//проверка принадлежности clid к данному аккаунту
		$did = (int)$db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE $s AND iduser IN (".yimplode(",", get_people($iduser, "yes")).") AND identity = '$identity'");
		//print $db -> lastQuery();
		//$did = $db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE did = '".$params['did']."' ".get_people($iduser)." and identity = '$identity'");

		if ($did == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		else {

			$Deal     = new Deal();
			$response = $Deal -> delete((int)$params['did']);

		}

		if ($params['did'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		break;

	default:

		//$response['error']['code'] = 404;
		//$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';

		$response['result']        = 'Error';
		$response['error']['code'] = 402;
		$response['error']['text'] = 'Неизвестный метод';

		break;

}

print $rez = json_encode_cyr($response);