<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 209.x         */
/* ============================ */

/**
 * функции для работы с Ростелеком
 */

use PHPMailer\PHPMailer\Exception;

/**
 * Отправка запроса через CURL
 *
 * @param $url
 * @param array $params
 * @param array $header
 * @return stdClass
 */
function Send($url, array $params = [], array $header = []): stdClass {

	$result = new stdClass();

	$headers[] = "Content-Type: application/json;charset=UTF-8";
	$headers[] = "X-Client-ID:".$header['id'];
	$headers[] = "X-Client-Sign:".$header['sign'];

	//print_r($headers);

	$POST = (is_array($params)) ? json_encode($params) : $params;

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result -> response = curl_exec($ch);
	$result -> info     = curl_getinfo($ch);
	$result -> error    = curl_error($ch);

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
function doMethod($method, array $param = []): array {

	$rootpath = realpath( __DIR__.'/../../../' );

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";
	include $rootpath."/inc/func.php";

	$baseurl = "https://api.cloudpbx.rt.ru";

	//адрес для отладки
	//$baseurl = "https://api-test.cloudpbx.rt.ru";

	//print_r($param);

	$phone    = $param['phone'];
	$phone_in = $param['phone_in'];

	$clid = (int)$param['clid'];
	$pid  = (int)$param['pid'];

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$iduser   = $GLOBALS['iduser1'];
	$db       = $GLOBALS['db'];

	$param['type'] = (isset($param['type'])) ? $param['type'] : 'out';

	$postdata     = [];
	$header["id"] = $param['api_key'];
	$domain       = $param['domain'];
	$url          = "";

	//print_r($param);

	//заглушка
	//$domain = "SNovos4.20.rt.ru";

	switch ($method) {

		// запрос для проверки подключения
		case 'accounts'://+

			$url = $baseurl."/users_info";

			$postdata = [
				"domain"   => $domain,
				"user_pin" => $phone_in
			];

			/*
			[
				"data" => [
					"result"        => 0,
					"resultMessage" => "",
					"users"         => [
						"display_name" => "Admin",
						"name"         => "snovos4",
						"pin"          => "501"
					]
				]
			];
			*/

		break;

		// запрос для совершения вызова
		case 'call'://-

			$url = $baseurl."/call_back";

			$postdata = [
				"domain"         => $domain,
				"request_number" => $phone,
				"from_pin"       => $phone_in
			];

			//print_r($postdata);

			/*
			{
				"result": "1",
				"resultMessage": "Операция выполнена успешно",
				"session_id": "534dbe28-7e58-4705-89a4-26a308405464"
			}
			*/

		break;

		// запрос подробной информации о вызове
		case 'call.info'://-

			$url = $baseurl."/call_info";

			$postdata = [
				"domain"     => $domain,
				"session_id" => $param['uid'],
			];

			/*
			$r = [
				"result"        => 0,
				"resultMessage" => "",
				"info"          => [
					//1 – обычный звонок, 3 – callback
					"call_type"        => "1 – обычный звонок, 3 – callback",
					//1 – от внешнего клиента (входящий); 2 – внешнему клиенту (исходяший); 3 – внутренний
					"direction"        => "",
					//1 – вызов принят, 2 – вызов не принят
					"state"            => "",
					//Номер в формате SIP-URI вызывающего абонента
					"orig_number"      => "sip:m1@testdomain.ru",
					//PIN вызывающего абонента (для исходящих и внутренних вызовов)
					"orig_pin"         => "3",
					//Номер в формате SIP-URI вызываемого абонента:
					//- для входящих вызовов – номер линии домена;
					"dest_number"      => "sip:89123456789",
					//Номер первого ответившего абонента в
					//формате SIP-URI
					"answering_sipuri" => "",
					//PIN первого ответившего абонента (для
					//входящих и внутренних вызовов).
					"answering_pin" => "",
					//Дата и время входящего вызовы [ TIMESTAMP ]
					"start_call_date"  => "1552315968",
					//Продолжительность вызова в секундах,
					//при отсутствии соединения передается 0
					"duration"         => 8,
					//Краткий протокол вызова (переадресации,
					//переводы, перехваты и т.д.), как в журнале
					"session_log"      => "0:ct:89035037889;5:cc:89035037889;8:cd:89035037889;",
					"is_voicemail"     => false,
					"is_record"        => true,
					"is_fax"           => false,
					//Код ошибки соединения
					"status_code"      => "0",
					//Текст ошибки соединения
					"status_string"    => ""
				]
			];
			*/

		break;

		// Получение информации о вызывающем номере
		// Перенесено в events, т.к. это запрос от ВАТС
		/*
		case 'number.info'://-

			$url = $baseurl."/get_number_info";

			$postdata = [
				"domain"         => $domain,
				"request_number" => $phone,
				//номер входящей линии
				"from_number"    => $phone_in
			];

		break;
		*/

		// запрос записи разговора
		case 'record'://-

			$url = $baseurl."/get_record";

			/*
			if($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {

				$externalContent = file_get_contents('http://checkip.dyndns.com/');
				preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
				$externalIp = $m[1];

			}
			else
			*/
				$externalIp = $_SERVER['REMOTE_ADDR'];

			// получаем внешний IP для ПК с нашего скрипта
			// сделано в основном для локальных пользователей
			//$externalIp = file_get_contents('https://salesman.pro/myip.php');

			$postdata = [
				"domain"     => $domain,
				"session_id" => $param['uid'],
				"ip_adress"  => $externalIp
			];

			/*
			{
				"result": "0",
				"resultMessage": "Операция выполнена успешно",
				"url": "https://api.cloudpbx.rt.ru/records_new_scheme/record/download/000017494cf705ab6d72a091aa550726/188254033084"
			}
			*/

		break;

		// запрос истории звонков. На стадии реализации провайдером
		case 'history'://-

			$url = $baseurl;

			$date_from = ($param['dstart']) ? $param['dstart'] : current_datumtime(24);
			$date_to   = ($param['dend']) ? $param['dend'] : current_datumtime();

			$postdata = [
				"cmd"    => "history",
				"type"   => "all",
				"start"  => DateTimeToUTC($date_from),
				"end"    => DateTimeToUTC($date_to),
				"period" => 'this_week',
				"token"  => $param['api_salt']
			];

		break;

	}

	$header["sign"] = hash('sha256', $param['api_key'].json_encode($postdata).$param['api_salt']);

	$result = Send($url, $postdata, $header);

	$rez = [
		"data"  => json_decode($result -> response, true),
		"error" => $result -> error,
		//"info"    => $result -> info,
		//"post"  => $postdata
	];

	//добавим исходящий звонок в историю
	if ($method == 'call') {

		/*
		При методе call_back, передается коды 0 и -20
		"result": "0",
		"resultMessage": "Операция выполнена успешно",

		"result": "-20",
		"resultMessage": "Ошибка при попытке выполнения обратного вызова",
		*/
		if($rez['data']['result'] == "0")
			$rez['data']['state'] = 'Success';

		if($rez['data']['state'] == "-20")
			$rez['error'] = $rez['resultMessage'];

		if($rez['data']['session_id'] != '') {

			//Добавим звонок в базу
			$id = $db -> getOne("SELECT id FROM ".$sqlname."rostelecom_log WHERE extention = '$phone_in' and type = 'out' and identity = '$identity'");

			if ($id == 0) {

				$db -> query("INSERT INTO  {$sqlname}rostelecom_log SET ?u", [
					'datum'     => current_datumtime(),
					'callid'    => $rez['data']['session_id'],
					'extention' => $phone_in,
					'phone'     => $phone,
					'status'    => $rez['data']['state'],
					'content'   => "",
					'type'      => "out",
					'clid'      => $clid + 0,
					'pid'       => $pid + 0,
					'identity'  => $identity
				]);
				$id = $db -> insertId();

			}
			else {

				$db -> query("UPDATE {$sqlname}rostelecom_log SET ?u WHERE id = '$id'", [
					'datum'    => current_datumtime(),
					'callid'   => $rez['data']['session_id'],
					'phone'    => $phone,
					'status'   => $rez['data']['state'],
					'content'  => "",
					'type'     => "out",
					'clid'     => $clid + 0,
					'pid'      => $pid + 0,
					'identity' => $identity
				]);

			}
			$cid  = $db -> getOne("SELECT id FROM  {$sqlname}callhistory WHERE uid = '$rez[data][session_id]' and identity = '$identity'") + 0;

			$call = [
				"res"      => "",
				"src"      => $phone_in,
				"dst"      => $phone,
				"did"      => 0,
				"phone"    => $phone,
				"iduser"   => $iduser + 0,
				"direct"   => 'outcome',
				'clid'     => $clid + 0,
				'pid'      => $pid + 0,
				'identity' => $identity
			];

			if ($cid == 0) {

				$call['datum'] = current_datumtime();
				$call['uid']   = $rez['data']['session_id'];
				$db -> query("INSERT INTO {$sqlname}callhistory SET ?u", $call);

			}
			else {

				$db -> query("UPDATE {$sqlname}callhistory SET ?u WHERE id = '$cid'", $call);

			}

		}

	}

	return $rez;

}