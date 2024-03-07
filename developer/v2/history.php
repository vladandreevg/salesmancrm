<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ERROR);
ini_set( 'display_errors', 1 );

set_time_limit(300);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

function Cleaner($string) {

	$string = trim($string);
	$string = str_replace( [
		'"',
		'\n\r',
		"'"
	], [
		'”',
		'',
		"&acute;"
	], $string );

	return $string;

}

$headers = getallheaders();

/**
 * Принимаем в формате JSON
 */
if($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);

	$APIKEY = array_key_exists( 'apikey', $headers) ? $headers['apikey'] : $headers['Apikey'];
	$LOGIN  = array_key_exists( 'login', $headers) ? $headers['login'] : $headers['Login'];

}

/**
 * Если это GET-запрос или отправка формы
 */
else {

	$params = [];
	foreach ($_REQUEST as $key => $value) {
		$params[ $key ] = (!is_array( $value )) ? Cleaner( $value ) : $value;
	}

	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];

}

if( is_null($APIKEY) && !is_null($params['apikey'])){
	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];
}

//доступные методы
$aceptedActions = [
	"tips",
	"fields",
	"list",
	"info",
	"add",
	"add.list",
	"delete"
];

$db = new SafeMysql([
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8',
	'errmode' => 'exception'
]);

//поля
$fieldsname = [
	"cid",
	"datum",
	"des",
	"tip",
	"iduser",
	"clid",
	"pid",
	"did"
];
$fields     = [
	"cid"    => "Идентификатор записи",
	"datum"  => "Дата",
	"des"    => "Содержание",
	"tip"    => "Тип активности",
	"iduser" => "Ответственный",
	"clid"   => "ID клиента",
	"pid"    => "ID контакта (массив)",
	"did"    => "ID сделки"
];

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

global $identity;

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = (int)$result['iduser'];
$username = $result['title'];
$iduser1  = (int)$result['iduser'];


require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);
$Error    = '';
$response = [];

//проверяем api-key
if ($identity == 0) {

	$response['result']        = 'Error';
	$response['error']['code'] = 400;
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';

}

//проверяем пользователя
elseif (empty($username)) {

	$response['result']        = 'Error';
	$response['error']['code'] = 401;
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';

}

//проверяем метод
elseif (!in_array($params['action'], $aceptedActions)) {

	$response['error']['code'] = 402;
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') {
	goto ext;
}

switch ($params['action']) {

	case 'fields':

		foreach ($fields as $key => $val) {
			$response['data'][ $key ] = $val;
		}

	break;

	case 'tips':

		$re = $db -> query("SELECT * FROM ".$sqlname."activities WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"title"  => $do['title'],
				"color"  => $do['color'],
				"filter" => $do['filter']
			];

		}

	break;

	case 'info':

		$cid = (int)$db -> getOne("SELECT cid FROM ".$sqlname."history WHERE cid = '".$params['id']."' and identity = '$identity'");

		if ($cid < 1 && $params['id'] != '') {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Не найдено в пределах аккаунта указанного пользователя.";

		}
		elseif ($cid > 0 && $params['id'] != '') {

			$cdata = get_historyinfo($cid);

			$field_types = db_columns_types( "{$sqlname}history" );

			//print_r($cdata);
			//print_r($fieldsname);

			if (!empty($cdata)) {

				$history = [];

				foreach ($fieldsname as $field) {

					switch ($field) {

						case 'cid':

							$history['cid'] = (int)$cdata[ $field ];

						break;
						case 'des':

							$history[ "content" ] = $cdata[ $field ];

						break;
						case 'clid':

							$history[ $field ] = (int)$cdata[ $field ];
							$history['client'] = current_client($cdata[ $field ]);

						break;
						case 'pid':

							$history['person'] = [];

							$pids = yexplode(";", $cdata[ $field ]);

							foreach ($pids as $pid) {

								$history['person'][] = [
									"pid"    => (int)$pid,
									"person" => current_person($pid)
								];

							}

						break;
						case 'did':

							$history[ $field ]  = (int)$cdata[ $field ];
							$history['dogovor'] = current_dogovor($cdata[ $field ]);

						break;
						case 'iduser':

							$history[ $field ] = (int)$cdata[ $field ];
							$history["user"]   = current_userlogin($cdata[ $field ]);

						break;
						default:

							//$history[ $field ] = $cdata[ $field ];

							if($field_types[ $field ] == "int"){

								$history[ $field ] = (int)$cdata[ $field ];

							}
							elseif(in_array($field_types[ $field ], ["float","double"])){

								$history[ $field ] = (float)$cdata[ $field ];

							}
							else {

								$history[ $field ] = $cdata[ $field ];

							}

						break;

					}

				}

				$history['cid'] = $cid;

				$response['data'] = $history;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		elseif ($cid < 1 && $params['id'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id напоминания";

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'datum';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';
		$active = ($params['active'] != '') ? $params['active'] : 'yes';

		$limit = 200;
		$sort  = '';

		if ($params['dateStart'] == '') {
			$params['dateStart'] = current_date();
		}
		if ($params['dateEnd'] == '') {
			$params['dateEnd'] = current_date();
		}

		//$sort .= get_people($iduser);

		if ($params['word'] != '') {
			$sort .= " and (des LIKE '%".Cleaner( $params['word'] )."%' or tip LIKE '%".Cleaner( $params['word'] )."%')";
		}

		if ($params['tip'] != '' && in_array($params['tip'], $fieldsname)) {
			$sort .= " and tip = '".Cleaner( $params['tip'] )."'";
		}

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and datum = '".$params['dateStart']."'";
		}
		elseif ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and (datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
		}
		elseif ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and datum < '".$params['dateEnd']."'";
		}
		else {
			$sort .= " and DATE_FORMAT(datum, '%Y-%m-%d') = '".current_datum()."'";
		}

		if ($params['user'] != '') {
			$sort .= " and ".$sqlname."history.iduser = '".current_userbylogin( $params['user'] )."'";
		}
		else {
			$sort .= " and ".$sqlname."history.iduser IN (".yimplode( ",", get_people( (int)$iduser, "yes" ) ).")";
		}

		if ((int)$params['clid'] > 0) {
			$sort .= " and ".$sqlname."history.clid = '".$params['clid']."'";
		}
		if ((int)$params['did'] > 0) {
			$sort .= " and ".$sqlname."history.did = '".$params['did']."'";
		}
		if ((int)['pid'] > 0) {
			$sort .= " and FIND_IN_SET('$params[pid]', REPLACE(".$sqlname."history.pid, ';',',')) > 0";
		}

		$lpos = $offset * $limit;

		$field_types = db_columns_types( "{$sqlname}history" );

		$result = $db -> query("SELECT * FROM ".$sqlname."history WHERE cid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		while ($da = $db -> fetch($result)) {

			$history = [];

			foreach ($fieldsname as $field) {

				switch ($field) {

					case 'clid':

						$history[ $field ] = (int)$da[ $field ];
						$history['client'] = current_client($da[ $field ]);

					break;
					case 'pid':

						$pids = yexplode(";", $da[ $field ]);

						foreach ($pids as $pid) {

							$history['person'][] = [
								"pid"    => (int)$pid,
								"person" => current_person($pid)
							];

						}

					break;
					case 'did':

						$history[ $field ]  = (int)$da[ $field ];
						$history['dogovor'] = current_dogovor($da[ $field ]);

					break;
					case 'des':

						$history['content'] = nl2br(untag3($da[ $field ]));

					break;
					case 'iduser':

						$history[ $field ] = (int)$da[ $field ];
						$history["user"]   = current_userlogin($da[ $field ]);

					break;
					default:

						//$history[ $field ] = $da[ $field ];

						if($field_types[ $field ] == "int"){

							$history[ $field ] = (int)$da[ $field ];

						}
						elseif(in_array($field_types[ $field ], ["float","double"])){

							$history[ $field ] = (float)$da[ $field ];

						}
						else {

							$history[ $field ] = $da[ $field ];

						}

					break;

				}

			}

			$response['data'][] = $history;

		}

	break;

	case 'add':

		if ($params['user'] == '') {
			$params['iduser'] = $iduser;
		}
		else {
			$params['iduser'] = current_userbylogin( $params['user'] );
		}

		$params['tip'] = (isset($params['tip']) && $params['tip'] != '') ? untag($params['tip']) : "Событие";

		//проверка, что есть название клиента
		if (($params['clid'] != '' || $params['pid'] != '' || $params['did'] != '') && $params['content'] != '') {

			$params['identity'] = $identity;

			try {

				$hid = addHistorty([
					'iduser'   => (int)$params['iduser'],
					'clid'     => (int)$params['clid'],
					'pid'      => yimplode(";", yexplode(";", str_replace(",", ";", $params['pid']))),//для очистки от пробелов и пустот
					'did'      => (int)$params['did'],
					'datum'    => $params['datum']." ".getTime(current_datumtime()),
					'des'      => $params['content'],
					'tip'      => $params['tip'],
					'identity' => $identity
				]);

				$response['result'] = 'Успешно';
				$response['data']   = $hid;

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры";

		}

	break;

	case 'add.list':

		if ($params['user'] == '') {
			$params['iduser'] = $iduser;
		}
		else {
			$params['iduser'] = current_userbylogin( $params['user'] );
		}

		$list = $params['list'];

		foreach ($list as $item) {

			//проверка, что есть название клиента
			if ($item['content'] != '') {

				if ($item['datum'] == '') {
					$item['datum'] = current_datumtime();
				}

				$item['identity'] = $identity;
				$item['iduser'] = (isset($item['user']) && $item['user'] != '') ? current_userbylogin($item['user']) : $params['iduser'];

				try {

					$hid = addHistorty([
						'iduser'   => (int)$item['iduser'],
						'clid'     => (int)$item['clid'],
						'pid'      => yimplode(";", yexplode(";", (string)str_replace(",", ";", $item['pid']))),//для очистки от пробелов и пустот
						'did'      => (int)$item['did'],
						'datum'    => $item['datum']." ".getTime(current_datumtime()),
						'des'      => $item['content'],
						'tip'      => $item['tip'],
						'identity' => $identity
					]);

					$res['result'] = 'Успешно';
					$res['data']   = $hid;

				}
				catch (Exception $e) {

					$res['result']        = 'Error';
					$res['error']['code'] = 500;
					$res['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}
			else {

				$res['result']        = 'Error';
				$res['error']['code'] = 405;
				$res['error']['text'] = "Отсутствуют параметры - Заголовок";

			}

			$response[] = $res;

		}

	break;

	case 'delete':

		//проверка принадлежности cid к данному аккаунту
		$cid = (int)$db -> getOne("SELECT cid FROM ".$sqlname."history WHERE cid = '".$params['id']."' ".get_people($iduser)." and identity = '$identity'");

		if ($cid < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись не найдена.";

		}
		else {

			try {

				$db -> query("DELETE FROM ".$sqlname."history WHERE cid = '$cid' and identity = '$identity'");

				$response['result'] = 'Успешно';

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

			}

		}

		if ($params['id'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - cid записи";

		}

	break;

	default:
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
	break;
}

ext:

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;
//HTTPStatus($code);

print json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";