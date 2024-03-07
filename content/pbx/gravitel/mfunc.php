<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2019.x          */
/* ============================ */

/**
 * функции для работы с Гравител
 */

/**
 * Отправка запроса через CURL
 *
 * @param $url
 * @param $POST
 *
 * @return bool|string
 */
function Send($url, $POST) {

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if (!$result) {
		$result = curl_error($ch);
	}

	return $result;

}

/**
 * Основные действия
 *
 * @param       $method
 * @param array $param
 * @return array
 * @throws Exception
 */
function doMethod($method, array $param = []) {

	$rootpath = dirname(__DIR__, 3);

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/func.php";

	//у ВАТС указан персональный урл
	$baseurl = $param['api_key'];

	//print_r($param);

	//$api_key  = $param['api_key'];
	//$api_salt = $param['api_salt'];
	$phone    = $param['phone'];
	$phone_in = $param['phone_in'];
	$actionID = $param['actionID'];
	$clid     = (int)$param['clid'];
	$pid      = (int)$param['pid'];

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$iduser   = $GLOBALS['iduser1'];
	$db       = $GLOBALS['db'];

	$param['type'] = $param['type'] ?? 'out';

	$url      = '';
	$postdata = [];
	$rez      = [];

	if ($actionID == '') {
		$actionID = 'salesman'.time();
	}

	switch ($method) {

		case 'accounts'://+

			$url = $baseurl;

			$postdata = [
				"cmd"   => "accounts",
				"token" => $param['api_salt']
			];

			break;
		case 'call'://+

			$url = $baseurl;

			$postdata = [
				"cmd"   => "makeCall",
				"phone" => $phone,
				"user"  => $phone_in,
				"token" => $param['api_salt']
			];

			break;
		case 'history'://+

			$url = $baseurl;

			$date_from = ( $param['dstart'] ) ? : modifyDatetime(current_datumtime(24), ["format" => "Ymd\THis"]);
			$date_to   = ( $param['dend'] ) ? : modifyDatetime(current_datumtime(), ["format" => "Ymd\THis"]);

			$postdata = [
				"cmd"    => "history",
				"type"   => "all",
				"period" => $param['period'] ?? "",
				"start"  => $param['period'] == '' ? DateTimeToUTC($date_from) : "",
				"end"    => $param['period'] == '' ? DateTimeToUTC($date_to) : "",
				"token"  => $param['api_salt']
			];

			if ($postdata['period'] == "") {
				unset($postdata['period']);
			}

			//print_r($postdata);

			break;

	}

	if ($url != '') {

		$result = Send($url, $postdata);

		if (in_array($method, [
				"accounts",
				"call"
			])) {

			$rez = json_decode($result, true);

			//print_r($rez);

			if ($method == 'call' && $rez['uuid'] != '') {

				//Добавим звонок в базу
				$id = $db -> getOne("select id from ".$sqlname."gravitel_log where extension = '$phone_in' and type = 'out' and identity = '$identity'");

				if ($id == 0) {

					$db -> query("INSERT INTO  {$sqlname}gravitel_log SET ?u", [
						'datum'     => current_datumtime(),
						'callid'    => $rez['uuid'],
						'extension' => $phone_in,
						'phone'     => $phone,
						'status'    => "",
						'content'   => "",
						'type'      => "out",
						'clid'      => $clid,
						'pid'       => $pid,
						'identity'  => $identity
					]);
					$id = $db -> insertId();

				}
				else {

					$db -> query("UPDATE {$sqlname}gravitel_log SET ?u WHERE id = '$id'", [
						'datum'    => current_datumtime(),
						'callid'   => $rez['uuid'],
						'phone'    => $phone,
						'status'   => "",
						'content'  => "",
						'type'     => "out",
						'clid'     => $clid,
						'pid'      => $pid,
						'identity' => $identity
					]);

				}

				$call = [
					"res"      => "",
					"src"      => $phone_in,
					"dst"      => $phone,
					"did"      => 0,
					"phone"    => $phone,
					"iduser"   => (int)$iduser,
					"direct"   => 'outcome',
					'clid'     => $clid,
					'pid'      => $pid,
					'identity' => $identity
				];

				$cid = (int)$db -> getOne("select id from  {$sqlname}callhistory where uid = '$rez[uuid]' and identity = '$identity'");

				if ($cid == 0) {

					$call['datum'] = current_datumtime();
					$call['uid']   = $rez['uuid'];
					$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);

				}
				else {

					$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $call);

				}

			}

		}
		elseif ($method == "history") {

			$rez = $result;

		}
		else {

			$rez = json_decode((string)$result, true);// Раскодируем ответ API-сервера в массив

		}

	}

	//print_r(apache_request_headers());

	return $rez;

}