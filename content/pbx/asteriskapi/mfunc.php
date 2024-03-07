<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2024.x        */
/* ============================ */

/**
 * Сложим все в лог
 * @param array $data
 * @return void
 * @throws Exception
 */
function LogIt(array $data = []) {

	global $rootpath;

	$logpath = $rootpath."/cash/asterisk-events";
	createDir($logpath);

	$day = modifyDatetime(NULL, ['format' => "d-m-Y"]);

	$file = $logpath."/events-$day.log";

	$text = current_datumtime()."\n".json_encode_cyr($data)."\n\n";
	file_put_contents($file, $text, FILE_APPEND);

}

/**
 * @param $string
 * @return array|string|string[]
 */
function eventCleaner($string) {

	$string = trim($string);

	return str_replace([
		'"',
		'\n\r',
		"'"
	], [
		'”',
		'',
		"&acute;"
	], $string);

}

/**
 * Нормализация направления звонка
 * @param $origDirection
 * @return string
 */
function getCallDirection($origDirection): string {

	$direction = 'internal';

	switch ($origDirection) {

		case "inbound":
			$direction = "income";
			break;
		case "outbound":
			$direction = "outcome";
			break;

	}

	return $direction;

}

/**
 * Парссинг данных события и выдача готового результата
 * @param array $data
 * @return stdClass
 */
function getCallParams(array $data = []): stdClass {

	$result = new stdClass();

	$result -> type   = getCallDirection($data['call_direction']);
	$result -> callid = $data['call_id'];

	switch ($result -> type) {

		case "income":

			$result -> from      = normalizePhone($data['call_cid_num']);
			$result -> to        = normalizePhone($data['callee_num']);
			$result -> did       = normalizePhone($data['did']);
			$result -> phone     = normalizePhone($data['call_cid_num']);
			$result -> extention = $data['callee_num'];

			switch ($data['event']) {

				// при поступлении входящего вызова на АТС (можно использовать для перенаправления вызова на поератора)
				case "inbound_start":

					$result -> state = "INCOMING";

					break;
				// при поступлении вызова конкретному оператору
				case "inbound_dial_start":

					$result -> state = "DIALING";

					break;
				// при ответе оператора
				case "inbound_dial_answer":

					if ($data['event_type'] == 'dial_answer') {
						$result -> state = "CONNECTED";
					}

					break;
				// при конце разговора оператора
				case "inbound_dial_end":
					// завершение входящего звонка
				case "inbound_end":

					if ($data['event_type'] == 'dial_end') {
						$result -> state = "COMPLETED";
					}

					break;

			}

			break;
		case "outcome":

			$result -> from      = $data['cid_num'];
			$result -> to        = normalizePhone($data['call_callee_num']);
			$result -> phone     = normalizePhone($data['call_callee_num']);
			$result -> extention = $data['cid_num'];

			switch ($data['event']) {

				// при начале исходящего вызова на АТС
				case "outbound_start":
					// при дозвоне до абонента (у абонента звонит телефон)
				case "outbound_dial_start":

					$result -> state = "DIALING";

					break;
				// в момент ответа абонента на вызов (если ответа нет, этого события не будет)
				case "outbound_dial_answer":

					if ($data['dial_status'] == 'ANSWER') {
						$result -> state = "ANSWERED";
					}

					break;
				// когда вызов закончился (dial_status = ANSWER значит, что вызов был отвечен)
				case "outbound_dial_end":
					// завершение исходящего вызова
				case "outbound_end":

					$result -> state = "COMPLETED";

					break;

			}

			break;

	}

	// Запись вызова готова к скачиванию
	if ($data['event'] == "record_ready") {

		$result -> type = "record";
		$result -> url  = $data['recording_url'];

	}

	return $result;

}

/**
 * Инициализация вызова
 * Сначала звонок поступает оператору, затем абоненту
 * @param string $extention
 * @param string $number
 * @return stdClass
 */
function callOriginate(string $extention = '', string $number = ''): stdClass {

	global $pbxurl, $pbxuser;

	$header = [
		"Authorization" => "Basic ".base64_encode($pbxuser)
	];
	$data   = [
		"call_first_num"   => trim($extention),
		"connect_with_num" => trim(prepareMobPhone($number)),
	];

	return SendRequestCurl($pbxurl."/calls/place_call", $data, $header);

}

/**
 * Получение истории звонков
 *  - параметры from_date и to_date должны быть в формате "%Y-%m-%d" или "%Y-%m-%d %H:%M:%S"
 *  - параметры max_calls и offset позволяют получать большие выборки с помощью последовательных запросов
 *  - выбирать только звонки, в которых участвует указанный номер в качестве cid_num или callee_num.
 *  - max_calls — максимальное количество вызовов в возвращаемом результате (если не указать или указать значение больше 10000, — будет использовано значение 10000)
 *  - offset — смещение относительно начала выборки
 * @param array $params
 * @return mixed
 */
function getCallHistory(array $params = []) {

	global $pbxurl, $pbxuser;

	$header = [
		"Authorization" => "Basic ".base64_encode($pbxuser)
	];
	$data   = [
		"from_date" => $params['from_date'],
		"to_date" => $params['to_date'],
		//"num",
		//"max_calls",
		//"offset"
	];

	$result = SendRequestCurl($pbxurl."/calls/list", $data, $header, 'JSON', 'GET');

	/* Пример ответа
	```json
	{
	  "resp_status": "ok",
	   "format": [
			"call_date",
			"call_id",
			"cid_num",
			"callee_num",
			"did",
			"call_status",
			"ringing_duration",
			"talking_duration",
			"recording_url"
		],
		"calls": [
			[
				"<время начала вызова>",
				"<ID вызова в Asterisk>",
				"<номер звонящего>",
				"<вызванный номер>",
				"<городской номер, на который поступил вызов>",
				"<статус вызова>",
				"<длительность ожидания ответа>",
				"<длительность разговора>",
				"<ссылка на запись вызова>"
			]
		],
		"calls_count": "<количество звонков в выборке>",
		"max_calls": "<фактическое значение max_calls, использованное при выборке>",
		"offset": "<значение offset, использованное при выборке>"
	}
	```
	 */

	return json_decode($result -> response, true);

}

/**
 * Получение истории звонков. Расширенный вариант
 *  - параметры from_date и to_date должны быть в формате "%Y-%m-%d" или "%Y-%m-%d %H:%M:%S"
 *  - параметры max_calls и offset позволяют получать большие выборки с помощью последовательных запросов
 *  - выбирать только звонки, в которых участвует указанный номер в качестве cid_num или callee_num.
 *  - max_calls — максимальное количество вызовов в возвращаемом результате (если не указать или указать значение больше 10000, — будет использовано значение 10000)
 *  - offset — смещение относительно начала выборки
 * обычных столбцов есть дополнительные столбцы в этом порядке (в поле format в ответе это указано):
 * time_answer, direction, call_direction, dial_status
 * @param array $params
 * @return mixed
 */
function getCallHistoryExtra(array $params = []) {

	global $pbxurl, $pbxuser;

	$header = [
		"Authorization" => "Basic ".base64_encode($pbxuser)
	];
	$data   = [
		"from_date" => $params['from_date'],
		"to_date" => $params['to_date'],
		//"num",
		//"max_calls",
		//"offset"
	];

	$result = SendRequestCurl($pbxurl."/calls/list_extra", $data, $header, 'JSON', 'GET');

	/* Пример ответа
	```json
	{
	  "resp_status": "ok",
	   "format": [
			"call_date",
			"call_id",
			"cid_num",
			"callee_num",
			"did",
			"call_status",
			"ringing_duration",
			"talking_duration",
			"recording_url",
			"time_answer",
			"direction",
			"call_direction",
			"dial_status"
		],
		"calls": [
			[
				"<время начала вызова>",
				"<ID вызова в Asterisk>",
				"<номер звонящего>",
				"<вызванный номер>",
				"<городской номер, на который поступил вызов>",
				"<статус вызова>",
				"<длительность ожидания ответа>",
				"<длительность разговора>",
				"<ссылка на запись вызова>",
				"<время ответа (если ответа не было, будет значение "0000-00-00 00:00:00.00000")>",
				"<изначальное направление вызова>",
				"<направление текущего этапа вызова>",
				"<статус дозвона>",
			]
		],
		"calls_count": "<количество звонков в выборке>",
		"max_calls": "<фактическое значение max_calls, использованное при выборке>",
		"offset": "<значение offset, использованное при выборке>"
	}
	```
	 */

	return json_decode($result -> response, true);

}

/**
 * Нормализация номера телефона, приведение к 11-значному виду
 * в формате "7хххххххххх"
 * @param string $phone
 * @return string
 */
function normalizePhone(string $phone = ''): string {

	// оставляем только цифры
	$phone = preg_replace("/\D/", "", $phone);

	// если длина номера = 10, т.е. без 7/8
	if (strlen($phone) == 10) {

		return "7".$phone;

	}

	// если первая цифра номера = 7/8
	if (
		( in_array(str_split($phone)[0], [
			"7",
			"8"
		]) )
	) {

		return "7".substr($phone, 1);

	}

	return $phone;

}