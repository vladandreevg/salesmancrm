<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*           ver. 2018.x        */
/* ============================ */

/**
 * функции для работы с Яндекс Телефония
 */


/**
 * Отправка запроса через CURL
 * @param $url
 * @param $POST
 *
 * @return mixed
 */
//для получения токина
function Send($url, $POST) {

	$ch = curl_init($url);// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($POST));
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$result = curl_exec($ch);

	//if ($result === false) print $err = curl_error($ch);

	return json_decode($result, true);

}

//для получения данных о разговорах
function Send_dan($url, $params = []) {

	$api_key      = $params['api_key'];
	$access_token = $params['token'];

	$ch = curl_init($url);// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: bearer '.$access_token,
		'x-api-key: '.$api_key
	));
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$result_dan = curl_exec($ch);

	//if ($result_dan === false) print $err = curl_error($ch);

	return json_decode($result_dan, true);
}

/**
 * Основные действия
 *
 * @param $method
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

	$baseurl = 'https://api.yandex.mightycall.ru';

	//print_r($param);

	$api_key  = $param['api_key'];
	$token    = $param['token'];
	$dobnomer = $param['dobnomer'];

	//для выбора истории по дате (дата начала выборки) для файла cdr.php
	$data_filtr = $param['data_filtr'];

	//необходимо для проврки наличия действующего токена по пользователю в таблице customsettings
	$iduser = $param['iduser'];

	$param['type'] = $param['type'] ?? 'out';

	$url      = '';
	$postdata = [];
	$rez      = [];

	switch ($method) {

		//ПОЛУЧЕНИЕ ТОКИНА с доступом данных по определенному пользователю. Токен по каждому пользователю заноситься в табллицу customsettings с указание даты его получения. После 23 часов идет запрос на получение нового токина
		case 'token':

			$url      = $baseurl.'/api/auth/token';
			$postdata = [
				"grant_type"    => "client_credentials",
				"client_id"     => $api_key,
				"client_secret" => $dobnomer
			];

			//var_dump($postdata);

		break;
		/*
		//ПОЛУЧЕНИЕ ТОКИНА с доступом данных по всем (для файла cdr.php) Не используется поскольку нет возможности получить историю из телефонии с указание добавочного номера
		case 'token_osn':
			$url = $baseurl . '/api/auth/token';
			$postdata = array(
				"grant_type" => "client_credentials",
				"client_id"   => $api_key
			);
		break;
		*/
		//ПОЛУЧЕНИЕ ДАННЫХ последнего звонка pageSize=1
		case 'history':

			$url      = $baseurl.'/api/v3/calls?pageSize=1';
			$postdata = [
				"api_key" => $api_key,
				"token"   => $token
			];

		break;

		//ПОЛУЧЕНИЕ ДАННЫХ по заданной дате.Кол-во звонков в списке установлен 1000 шт. (для файла cdr.php) Дата последней проверки заноситься в таблицу customsettings
		case 'history_osn':

			$url = $baseurl.'/api/v3/calls?pageSize=1000&startUtc='.$data_filtr;
			$postdata = [
				"api_key" => $api_key,
				"token"   => $token
			];

			//здесь можно вывести данные по номерам
			//$url = $baseurl . '/api/v1/phonenumbers';

		break;

	}

	if ($url != '') {

		//получение данных по звонкам (здесь есть проверка на дату запуска проверки)
		if (in_array($method, [
			"history",
			"history_osn"
		])) {

			$rez = $result = Send_dan($url, $postdata);

		}
		//получение токина. Здесь есть проврка на время жизки токина
		else {

			//есть ли действующий токин по пользователю
			$danToken = $db -> getRow("SELECT * FROM  {$sqlname}customsettings WHERE iduser = '$iduser' and identity = '$identity' and tip = 'yandextelToken'");

			//проверям время токина в базе (жизнь токиина по документации телефонии по пользователю 24 часа, я беру 23)
			$time1 = time();
			$time2 = strtotime($danToken['datum']);
			$diff  = ($time1 - $time2) / 60 / 60; // разница в часах

			//если действующего токена нет
			if ($danToken == 0 || $diff > 23) {

				$result = Send($url, $postdata);
				//var_dump($result);

				//если Пользователь с таким добавочным номером нет в телефонии
				if ($result == 'invalid_client') {
					print "Пользователь с таким добавочным номером нет в телефонии";
				}

				//если пользователь с таким добавочным номер есть в телефонии
				else {

					//для добавления в базу
					$insDan = [
						"datum"    => current_datumtime(),
						"tip"      => 'yandextelToken',
						"params"   => $result['access_token'],
						"iduser"   => $iduser,
						"identity" => $identity
					];
					//для обновлении в базе
					$UptDan = [
						"datum"  => current_datumtime(),
						"params" => $result['access_token'],
					];

					//если в базе есть токин по пользователю, но он уже больше 23 часа обновляем данные в базе
					if ($danToken != 0) {
						$db -> query( "UPDATE {$sqlname}customsettings SET ?u where iduser = '$iduser' and tip = 'yandextelToken' and identity = '$identity'", $UptDan );
					}

					else {
						$db -> query( "INSERT INTO {$sqlname}customsettings SET ?u", $insDan );
					}

					$rez = $result;

				}

			}

			else {

				//$rez = 'Токен уже есть на 23 часа по данному пользователю';
				$rez['access_token'] = $danToken['params'];

			}

		}

	}

	return $rez;

}