<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*          ver. 2018.x         */
/* ============================ */


/**
 * Основные действия
 *
 * @param $method
 * @param array $param
 * @return array
 */
function doMethod($method, array $param = []): array {

	$rootpath = dirname( __DIR__, 3 );

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";
	include $rootpath."/inc/auth.php";
	include $rootpath."/inc/func.php";
	include $rootpath."/inc/settings.php";

	require_once $rootpath."/content/pbx/onlinepbx/lib/onpbx_http_api.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	//$iduser   = $GLOBALS['iduser1'];

	//SIP домен и API-ключ из ЛК onlinepbx
	$domain = $param['api_salt'];
	$apikey = $param['api_key'];

	$from    = $param['from'];
	$to      = $param['to'];
	$clid    = (int)$param['clid'];
	$pid     = (int)$param['pid'];
	$call_id = $param['call_id'];

	$dstart = $param['date_from'];
	$dend   = $param['date_to'];

	$url      = '';
	$postdata = [];
	$rez      = [];

	$baseurl = 'api.onlinepbx.ru/';

	switch ($method) {

		//Осуществление вызова
		case 'call':

			$url = '/call/now.json';

			$postdata = [
				"from" => $from,
				"to"   => $to
			];

		break;

		//получение истории звонков
		case 'history':

			$url = '/history/search.json';

			$date_from = ($dstart) ? $dstart : current_datumtime(24);
			$date_to   = ($dend) ? $dend : current_datumtime();

			$postdata = [
				"date_from" => $date_from,
				"date_to"   => $date_to
			];

			//var_dump($postdata);
		break;

		//получение записей звонков
		case 'record':

			$url = '/history/search.json';

			$postdata = [
				"download" => 1,
				"uuid"     => $call_id
			];

		break;

	}

	//Получаем ключи
	/*
	 * $domain - SIP домен
	 * $apikey - ключ API
	 * true/false - признак формирования нового ключа
	 * return:
	 *          status (0/1) - статус регистрации ключа
	 *          comment
	 *          data=array()
	 *              - key - ключ
	 *              - key_id - id ключа
	*/
	$check_keys = onpbx_get_secret_key($domain, $apikey, false);

	$url = $baseurl.$domain.$url;

	// Проверка на актуальность ключей
	if ($check_keys['status'] == 0) {

		$check_keys = onpbx_get_secret_key($domain, $apikey, true);

		if ($check_keys['status'] == 0) {

			$rez['status']  = 0;
			$rez['message'] = 'Ошибка!'."<br>".'Проверьте правильность SIP-домена и API ключа';

		}


	}
	//Сохранение данных аккаунта onpbx в БД
	elseif ($method == "auth") {

		//print_r($danToken);
		//print_r($postdata);

		//Данные для шифрования
		$skey = 'vanilla'.pow($identity + 7, 3).'round'.pow($identity + 3, 2).'robin';
		$ivc  = $db -> getOne("SELECT ivc FROM ".$sqlname."settings WHERE id = '$identity'");

		//Достаем данные аккаунта из БД, если есть
		$sdata = $db -> getRow("SELECT * FROM {$sqlname}services WHERE folder = 'onlinepbx' AND identity = '$identity'");

		$sid        = $sdata['id'];
		$check_user = rij_decrypt($sdata["user_id"], $skey, $ivc);
		$check_key  = rij_decrypt($sdata["user_key"], $skey, $ivc);

		if ($check_user != $domain || $check_key != $apikey) {

			//var_dump($postdata);

			if ($sid < 1) {

				$db -> query("INSERT INTO {$sqlname}services SET ?u", [
					'name'     => 'OnlinePBX',
					'folder'   => 'onlinepbx',
					'tip'      => 'sip',
					'user_key' => rij_crypt($apikey, $skey, $ivc),
					'user_id'  => rij_crypt($domain, $skey, $ivc),
					'identity' => $identity
				]);

				$rez['message'] = 'Данные успешно добавлены';

			}
			else {

				$db -> query("UPDATE {$sqlname}services SET ?u WHERE folder = 'onlinepbx' AND identity = '$identity'", [
					'user_key' => rij_crypt($apikey, $skey, $ivc),
					'user_id'  => rij_crypt($domain, $skey, $ivc),
				]);

				$rez['message'] = 'Данные успешно обновлены';

			}

			$rez['status'] = 1;


		}
		else {

			//$rez = 'Токен уже есть';
			$rez['status']  = 1;
			$rez['message'] = 'Данные успешно обновлены';

		}

	}
	elseif ($method == "call") {

		$call = onpbx_api_query($check_keys['data']['key'], $check_keys['data']['key_id'], $url, $postdata);

		//Добавим звонок в базу
		$id = $db -> getOne("SELECT id FROM {$sqlname}onlinepbx_log WHERE extension = '$from' AND type = 'out' AND identity = '$identity'");

		if ($id < 1) {

			$db -> query("INSERT INTO {$sqlname}onlinepbx_log SET ?u", [
				'datum'     => current_datumtime(),
				'callid'    => $call['data']['uuid'],
				'extension' => $from,
				'phone'     => $to,
				'status'    => 'CALLING',
				'comment'   => ($call['comment'] != '') ? $call['comment'] : "NORMAL_CLEARING",
				'type'      => "out",
				'clid'      => $clid,
				'pid'       => $pid,
				'identity'  => $identity
			]);

		}
		else {

			$db -> query("UPDATE {$sqlname}onlinepbx_log SET ?u WHERE id = '$id'", [
				'datum'    => current_datumtime(),
				'callid'   => $call['data']['uuid'],
				'phone'    => $to,
				'status'   => 'CALLING',
				'comment'  => ($call['comment'] != '') ? $call['comment'] : "NORMAL_CLEARING",
				'type'     => "out",
				'clid'     => $clid,
				'pid'      => $pid,
				'identity' => $identity
			]);

		}

		// Запишем статус звонка: 0 - невозможно осуществить звонок; 1 - удачный/неудачный звонок(напр. абонент занят)
		if (!$call['status']) $call['status'] = "1";

		$rez = $call;

	}
	elseif ($method == "history") {

		//Получаем историю звонков
		$history = onpbx_api_query($check_keys['data']['key'], $check_keys['data']['key_id'], $url, $postdata);

		$rez = $history['data'];

	}
	elseif ($method == "record") {

		//Получаем запись разговора
		$record = onpbx_api_query($check_keys['data']['key'], $check_keys['data']['key_id'], $url, $postdata);

		$rez = $record;

	}

	ext:

	return $rez;

}