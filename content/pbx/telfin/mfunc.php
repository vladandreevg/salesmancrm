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
 * функции для работы с Гравител
 */

//для получения токина
function Send($url, $POST) {

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt( $ch, CURLOPT_HEADER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $POST );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
	curl_setopt( $ch, CURLOPT_URL, $url );

	$result = curl_exec( $ch );

	if ( $result === false ) {
		print $err = curl_error( $ch );
	}

	return json_decode( $result, true );

}

//для получения данных о разговорах. GET
function Send_dan($url, $params = []) {

	$access_token = $params['token'];

	$ch = curl_init( $url );// Устанавливаем соединение
	curl_setopt( $ch, CURLOPT_HEADER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt( $ch, CURLOPT_POST, 0 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer '.$access_token
	] );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );

	$result_dan = curl_exec( $ch );

	//if ($result_dan === false) print $err = curl_error($ch);

	return json_decode( $result_dan, true );
}

//для исходящего звонка call (post и json)
function Send_call($url, $params = [], $token = '') {

	$ch = curl_init( $url );// Устанавливаем соединение
	curl_setopt( $ch, CURLOPT_HEADER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer '.$token
	] );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );

	$result_dan = curl_exec( $ch );

	//if ($result_dan === false) print $err = curl_error($ch);

	return json_decode( $result_dan, true );

}

/**
 * Основные действия
 *
 * @param       $method
 * @param array $param
 * @return array
 */
function doMethod($method, array $param = []): array {

	$rootpath = dirname( __DIR__, 3 );

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$iduser   = $GLOBALS['iduser1'];

	$api_key    = $param['api_key'];
	$api_secret = $param['api_secret'];
	$token      = $param['token'];

	//для history
	$callid = $param['callid'];

	//для history_osn
	$start_datetime = $param['start_datetime'];
	$end_datetime   = $param['end_datetime'];

	//для record
	$record = $param['record'];

	//для call_id и call
	$extensionID = $param['extension_id'];

	//для call_id
	$src_num = $param['src_num'];
	$dst_num = $param['dst_num'];

	//для call
	$call_api_id = $param['call_api_id'];

	//для extension_id
	$extension = $param['extension'];

	$url      = '';
	$postdata = [];
	$rez      = [];

	$baseurl = 'https://apiproxy.telphin.ru';

	switch ($method) {

		//получение токена (для sip_editor.php) время его жизни 1 час
		//Авторизация доверенных приложений (trusted) - подтверждение пользователя не требуется, приложение получает токен доступа без участия пользователя.
		case 'token':

			$url = $baseurl.'/oauth/token';

			$postdata = [
				"grant_type"    => "client_credentials",
				"client_id"     => $api_key,
				"client_secret" => $api_secret
			];

		break;

		//получение истории после звонка (для events.php)(сортировка по умолчанию от меньшей дате к большей)
		case 'history':

			$url = $baseurl.'/api/ver1.0/client/@me/call_history/'.$callid;

			$postdata = [
				"token" => $token
			];

			//var_dump($postdata);

		break;

		//получение истории всей (для cdr.php)(сортировка по умолчанию от большей даты к меньшей)
		case 'history_osn':

			//URL-кодирует строку.
			/*$start_datetime='2017-09-26 11:52:56';
			$end_datetime='2017-09-26 11:52:56';*/ $start_datetime = urlencode( $start_datetime );
			$end_datetime                                          = urlencode( $end_datetime );

			$url = $baseurl.'/api/ver1.0/client/@me/call_history/?start_datetime='.$start_datetime.'&end_datetime='.$end_datetime;

			$postdata = [
				"token" => $token
			];

			//var_dump($postdata);

		break;

		//получение ссылки на запись звонка (для events.php)
		case 'record':

			$url = $baseurl.'/api/ver1.0/client/@me/record/'.$record.'/storage_url/';

			$postdata = [
				"token" => $token
			];

			//var_dump($postdata);

		break;

		//запрос на получение extension_id, поскольку необходим для того что бы осуществить звонок с добавочного (для callto.php)
		case 'extension_id':

			$url = $baseurl.'/api/ver1.0/client/@me/extension/';

			$postdata = [
				"token" => $token
			];

			//var_dump($postdata);

		break;

		//получение id для исходящего звонка (для callto.php)
		case 'call_id':

			$url = $baseurl.'/api/ver1.0/extension/'.$extensionID.'/callback/';

			$postdata = [
				"src_num" => $src_num,
				"dst_num" => $dst_num,
			];

		break;

	}

	if ( $url != '' ) {

		//получение данных по звонкам (здесь есть проверка на дату запуска проверки)
		if ( in_array( $method, [
			"history",
			"history_osn",
			"record"
		] ) ) {

			$result = Send_dan( $url, $postdata );
			/*var_dump($result);
			exit();*/
			$rez = $result;
			//print_r($result);

		}

		if ( $method == "call_id" ) {

			//print $method;
			$postdata = json_encode( $postdata );
			//var_dump($postdata);
			$result = Send_call( $url, $postdata, $token );
			//var_dump($result);
			//exit();
			$rez = $result;

		}

		//получение токина. Здесь есть проврка на время жизки токина. Поскольку токе живет только 1 час запишев в таблицу customsettings
		if ( $method == "token" ) {

			//есть ли действующий токин
			$danToken = $db -> getRow( "SELECT id, datum, params FROM  {$sqlname}customsettings WHERE identity = '$identity' and tip='TelfinToken'" );

			//print_r($danToken);
			//print_r($postdata);

			//проверям время токина в базе (жизнь токиина по документации телефонии по пользователю 1 часа, я беру 55 минут )
			$time1 = time();
			$time2 = strtotime( $danToken['datum'] );
			$diff  = ($time1 - $time2) / 60; // разница в минутах

			//если действующего токена нет или он действует уже более 55 минут
			if ( $danToken['params'] == NULL || $diff > 55 ) {

				//var_dump($postdata);
				$result = Send( $url, $postdata );
				//var_dump($result);

				//для добавления в базу
				$insDan = [
					"datum"    => current_datumtime(),
					"tip"      => 'TelfinToken',
					"params"   => $result['access_token'],
					"iduser"   => $iduser,
					"identity" => $identity
				];

				//для обновлении в базе
				$UptDan = [
					"datum"  => current_datumtime(),
					"params" => $result['access_token'],
				];

				//если в базе есть токин, но он уже больше 55 минут обновляем данные в базе
				if ( $danToken['id'] > 0 ) {
					$db -> query( "UPDATE {$sqlname}customsettings SET ?u WHERE tip='TelfinToken' and identity = '$identity'", $UptDan );
				}

				//если в базе нет токена добавляем его
				else {
					$db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", $insDan );
				}

				$rez = $result;

			}
			else {

				//$rez = 'Токен уже есть на 55 минуты';
				$rez['access_token'] = $danToken['params'];

			}

		}

		//получение extension_id для добавочного номера. Здесь есть проврка на существание extension_id они храняться в таблице customsettings
		if ( $method == "extension_id" ) {

			//префикс для добавочного необходим поскольку необходим добавочный с предиксом
			$prefix = Send_dan( $baseurl.'/api/ver1.0/client/@me/client/', $postdata );
			$name   = $prefix['prefix'].'*'.$extension;

			//есть ли в базе extension_id
			$danExID = $db -> getRow( "SELECT * FROM  {$sqlname}customsettings WHERE identity = '$identity' and params='$name' and tip='TelfinExtensionID'" );

			//если extension_id нет
			if ( $danExID == 0 ) {

				$url .= '?name='.$name;
				//(получаем extension_id)
				$result = Send_dan( $url, $postdata );
				/*var_dump($result);
				exit();
				*/
				//для добавления в базу
				$insDan = [
					"datum"    => current_datumtime(),
					"tip"      => 'TelfinExtensionID',
					"params"   => $result[0]['name'],
					"iduser"   => $result[0]['id'],
					"identity" => $identity
				];
				$db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", $insDan );

				$rez = $result['id'];

			}
			else {

				$rez = $danExID['iduser'];

			}

		}


	}

	return $rez;

}