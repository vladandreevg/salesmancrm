<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2016.10       */
/* ============================ */

/**
 * функции для работы с Манго
 */

$answers = [
	"1000" => "Действие успешно выполнено",
	"1100" => "Вызов завершен в нормальном режиме",
	"1110" => "Вызов завершен вызывающим абонентом",
	"1111" => "Вызов не получил ответа в течение времени ожидания",
	"1120" => "Вызов завершен вызываемым абонентом",
	"1121" => "Получен ответ \"занято\" от удаленной стороны",
	"1122" => "Вызов отклонен вызываемым абонентом",
	"1123" => "Получен сигнал \"не беспокоить\"",
	"1130" => "Ограничения для вызываемого номера",
	"1131" => "Вызываемый номер недоступен",
	"1132" => "Вызываемый номер не обслуживается",
	"1133" => "Вызываемый номер не существует",
	"1134" => "Превышено максимальное число переадресаций",
	"1140" => "Вызовы на регион запрещены настройками ВАТ",
	"1150" => "Ограничения для вызывающего номера",
	"1151" => "Вызывающий номер в «черном» списке",
	"1152" => "Вызывающий номер не найден в «белом» списке",
	"1160" => "Вызов на группу не удался",
	"1161" => "Удержание запрещено настройками ВАТС",
	"1162" => "Очередь удержания заполнена",
	"1163" => "Превышено время ожидания в очереди удержания",
	"1164" => "Все операторы в данный момент недоступны",
	"1170" => "Вызов завершен согласно схеме переадресации",
	"1171" => "Неверно настроена схема переадресации",
	"1180" => "Вызов завершен командой пользователя",
	"1181" => "Вызов завершен по команде из внешней системы",
	"1182" => "Вызов завершен перехватом на другого оператора (только для исходящих плеч)",
	"1183" => "Назначен новый оператор при команде ApiConnect. Обычно при переводах)",
	"1190" => "Вызываемый номер неактивен либо нерабочее расписание",
	"1191" => "Вызываемый номер неактивен (снят флажок активности ЛК)",
	"1192" => "Вызываемый номер неактивен по расписанию",
	"2000" => "Ограничение биллинговой системы",
	"2100" => "Доступ к счету невозможен",
	"2110" => "Счет заблокирован",
	"2120" => "Счет закрыт",
	"2130" => "Счет не обслуживается (frozen)",
	"2140" => "Счет недействителен",
	"2200" => "Доступ к счету ограничен",
	"2210" => "Доступ ограничен периодом использования",
	"2211" => "Достигнут дневной лимит использования услуги",
	"2212" => "Достигнут месячный лимит использования услуги",
	"2220" => "Количество одновременных вызовов/действий ограничено",
	"2230" => "Услуга недоступна",
	"2240" => "Недостаточно средств на счете",
	"2250" => "Ограничение на количество использований услуги в биллинге",
	"2300" => "Направление заблокировано",
	"2400" => "Ошибка биллинга",
	"3000" => "Неверный запрос",
	"3100" => "Переданы неверные параметры команды",
	"3101" => "Запрос выполнен по методу, отличному от POST",
	"3102" => "Значение ключа не соответствуют рассчитанному",
	"3103" => "В запросе отсутствует обязательный параметр",
	"3104" => "Параметр передан в неправильном формате",
	"3105" => "Неверный ключ доступа",
	"3200" => "Неверно указан номер абонента",
	"3300" => "Объект не существует",
	"3310" => "Вызов не найден",
	"3320" => "Запись разговора не найдена",
	"3330" => "Номер не найден у ВАТС или сотрудника",
	"4000" => "Действие не может быть выполнено",
	"4001" => "Команда не поддерживается",
	"4002" => "Продолжительность записи меньше минимально возможной в ВАТС, запись не будет сохранена",
	"4100" => "Выполнить команду по логике работы ВАТС невозможно",
	"4101" => "Вызов завершен либо не существует",
	"4102" => "Запись разговора уже осуществляется",
	"4200" => "Связаться с абонентом в данный момент невозможно",
	"4300" => "SMS сообщение отправить не удалось",
	"4301" => "SMS сообщение устарело",
	"4400" => "Невозможно добавить участника в конференцию",
	"4401" => "Аппаратная ошибка",
	"4402" => "Сервис не доступен",
	"5000" => "Ошибка сервера",
	"5001" => "Перегрузка",
	"5002" => "Перезапуск",
	"5003" => "Технические проблемы",
	"5004" => "Проблемы доступа к базе данных"
];

/**
 * Отправка запроса через CURL
 *
 * @param        $url
 * @param        $POST
 * @param string $header
 * @return mixed
 */
function Send($url, $POST, string $header = "no") {
	$ch = curl_init();// Устанавливаем соединение
	if ( $header == 'yes' )
		curl_setopt( $ch, CURLOPT_HEADER, true );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $POST );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
	curl_setopt( $ch, CURLOPT_URL, $url );

	$result = curl_exec( $ch );

	if ( $result === false )
		print $err = curl_error( $ch );

	return $result;
}

/**
 * Основные действия
 *
 * @param        $method
 * @param array  $param
 *
 * @return array
 * @throws Exception
 */
function doMethod($method, array $param = []): array {

	$rootpath = dirname( __DIR__, 3 );

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/func.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$answers  = $GLOBALS['answers'];
	$db       = $GLOBALS['db'];

	$baseurl = "https://app.mango-office.ru/vpbx/";

	//print_r($param);

	$api_key  = $param['api_key'];
	$api_salt = $param['api_salt'];
	$phone    = $param['phone'];
	$phone_in = $param['phone_in'];
	$actionID = $param['actionID'];
	$clid     = (int)$param['clid'];
	$pid      = (int)$param['pid'];

	$url      = '';
	$postdata = [];
	$rez      = [];
	$header   = 'no';

	if ( $actionID == '' ) {
		$actionID = 'salesman'.time();
	}

	switch ($method) {

		case 'call':

			$url = $baseurl.'commands/callback';

			$data = [
				"command_id" => $actionID,
				"from"       => [
					"extension" => $phone_in,
					"number"    => ""
				],
				"to_number"  => $param['phone']
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

		break;
		case 'event':

			$url = $baseurl.'events/call';

			$data = [
				"command_id" => $actionID,
				"from"       => [
					"extension" => $phone_in,
					"number"    => ""
				],
				"to_number"  => $param['phone']
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

		break;
		case 'hangup':

			$url = $baseurl.'commands/call/hangup';

			$data = [
				"command_id" => $actionID,
				"call_id"    => $param['call_id']
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

		break;
		case 'history':

			$url = $baseurl.'stats/request';

			if ( $param['dstart'] ) {
				$date_from = date2unix( $param['dstart'] );
			}
			else {
				$date_from = date2unix( current_datum()." 00:00:01" );
			}

			if ( $param['dend'] ) {
				$date_to = date2unix( $param['dend'] );
			}
			else {
				$date_to = date2unix( current_datumtime() );
			}

			$data = [
				"date_from"  => $date_from,
				"date_to"    => $date_to,
				"from"       => [
					"extension" => "",
					"number"    => ""
				],
				"to"         => [
					"extension" => "",
					"number"    => ""
				],
				"fields"     => "records, start, finish, from_extension, from_number, to_extension, to_number, disconnect_reason",
				"request_id" => $actionID
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

		break;
		case 'records':

			$url = $baseurl.'stats/result';

			$data = [
				"key"        => $param['key'],
				"request_id" => $actionID
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

		break;
		case 'play':

			$url = $baseurl.'queries/recording/post';

			$data = [
				"recording_id" => $param['recording_id'],
				"action"       => "play"
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

			$header = 'yes';

			//print_r($postdata);

		break;
		case 'route':

			$url = $baseurl.'commands/route';

			$data = [
				"command_id" => $actionID,
				"call_id"    => $param['call_id'],
				"to_number"  => $param['extension']
			];

			$json = json_encode( $data );

			$sign = hash( 'sha256', $api_key.$json.$api_salt );

			$postdata = [
				'vpbx_api_key' => $api_key,
				'sign'         => $sign,
				'json'         => $json
			];

		break;

	}

	if ( $url != '' ) {

		if ( $phone_in != '' ) {

			//Добавим звонок в базу
			$id = $db -> getOne( "select id from ".$sqlname."mango_log where extension = '$phone_in' and type = 'abonent' and identity = '$identity'" );

			if ( $id == 0 ) {

				//если запись не найдена, то создаем её
				$db -> query( "INSERT INTO ".$sqlname."mango_log SET ?u", [
					'command_id' => $actionID,
					'extension'  => $phone_in,
					'phone'      => $phone,
					'type'       => 'abonent',
					'clid'       => $clid + 0,
					'pid'        => $pid + 0,
					'identity'   => $identity
				] );

			}
			else {

				$db -> query( "UPDATE ".$sqlname."mango_log SET ?u WHERE id = '$id'", [
					'command_id' => $actionID,
					'phone'      => $phone,
					'clid'       => $clid,
					'pid'        => $pid
				] );

			}

		}

		$result = Send( $url, $postdata, $header );

		if ( $method == "records" )
			$rez['records'] = $result;
		if ( $method == "play" ) {

			//print $result;

			$p = explode( "\n", (string)$result );
			foreach ( $p as $l ) {
				if ( stripos( $l, 'Location' ) !== false ) {
					$rez = yexplode( ": ", $l, 1 );
				}
			}

		}
		else {

			// Раскодируем ответ API-сервера в массив
			$result = json_decode( (string)$result, true );

			if ( !$result['key'] && $result['result'] ) {

				if ( $result['result'] == '1000' ) {

					if ( $method == 'call' )
						$rez['message'] = 'Ожидайте звонка..';
					if ( $method == 'hangup' )
						$rez['message'] = 'Звонок отменен..';

				}
				elseif ( $result['result'] == '3100' )
					$rez['message'] = "Ответ: Переданы неверные параметры команды либо команда не может быть выполнена с этими параметрами";
				elseif ( $result['result'] == '4001' )
					$rez['message'] = "Команда не поддерживается";
				else $rez['message'] = "Ошибка: ".$result['result'].". Ответ: ".strtr( $result['result'], $answers );


				$rez['uid']  = $actionID;
				$rez['code'] = $result['result'];

			}
			if ( !$result['key'] && !$result['result'] ) {

				$rez['name']    = $result['name'];
				$rez['message'] = $result['message'];

			}
			else {

				$rez['key']       = $result['key'];
				$rez['extension'] = $param['extension'];
				$rez['answer']    = strtr( $result['result'], $answers );

			}

			$rez['call_id']            = $result['call_id'];
			$rez['actionID']           = $actionID;
			$rez['disconnect_reason']  = $result['disconnect_reason'];
			$rez['disconnect_content'] = strtr( $result['disconnect_reason'], $answers );

		}

	}

	//print_r(apache_request_headers());

	return $rez;
}