<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

header('Access-Control-Allow-Origin: *');// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Content-Type: text/html; charset=utf-8');

error_reporting(E_ALL);

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/settings.php";
include "../../inc/func.php";
include "../../inc/class/Todo.php";

if (!file_exists("./yoolla.log")) {
	$file = fopen("./yoolla.log", "w");
	fclose($file);
}
ini_set('log_errors', 'On');
ini_set('error_log', './yoolla.log');

function Cleaner($string) {
	$string = trim($string);
	$string = str_replace('"', '”', $string);
	$string = str_replace('\n\r', '', $string);
	$string = str_replace("'", "&acute;", $string);

	return $string;
}

//print_r($_REQUEST);

foreach ($_REQUEST as $key => $value) {
	$params[ $key ] = Cleaner($value);
}

//доступные методы
$aceptedActions = array(
	"tips",
	"fields",
	"list",
	"info",
	"add",
	"update",
	"doit",
	"delete",
	"addhist"
);

$db = new SafeMysql(array(
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8',
	'errmode' => 'exception'
));

//поля
$fieldsname = array(
	"tid",
	"datum",
	"totime",
	"title",
	"des",
	"tip",
	"active",
	"priority",
	"speed",
	"iduser",
	"autor",
	"clid",
	"pid",
	"did"
);
$fields     = array(
	"tid"      => "Идентификатор напоминания",
	"datum"    => "Дата",
	"totime"   => "Время",
	"title"    => "Тема",
	"des"      => "Агенда",
	"tip"      => "Тип напоминания",
	"active"   => "Признак: выполнено,активно",
	"priority" => "Важность",
	"speed"    => "Срочность",
	"iduser"   => "Ответственный",
	"autor"    => "Автор напоминания",
	"clid"     => "ID клиента",
	"pid"      => "ID контакта",
	"did"      => "ID сделки"
);

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '".$params['apikey']."'");
$identity = $result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '".$params['login']."' AND identity = '".$identity."'");
$iduser   = $result['iduser'];
$username = $result['title'];

$Error    = '';
$response = array();

//проверяем api-key
if (!$identity) {
	$response['result']        = 'Error';
	$response['error']['code'] = '400';
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';
}
//проверяем пользователя
elseif (!$username) {
	$response['result']        = 'Error';
	$response['error']['code'] = '401';
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';
}
//проверяем метод
elseif (!in_array($params['action'], $aceptedActions)) {
	$response['error']['code'] = '402';
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';
}

if ($Error != 'yes') {

	//задаем лимиты по-умолчанию
	$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
	$order  = ($params['order'] != '') ? $params['order'] : 'datum';
	$first  = ($params['first'] == 'old') ? '' : 'DESC';
	$active = ($params['active'] != '') ? $params['active'] : 'yes';

	$limit = 200;
	$sort  = '';

	switch ($params['action']) {

		case 'fields':

			foreach ($fields as $key => $val) {
				$response['data'][ $key ] = $val;
			}

		break;
		case 'tips':

			$re = $db -> query("SELECT * FROM ".$sqlname."activities WHERE identity = '$identity'");
			while ($do = $db -> fetch($re)) {
				$response['data'][] = array(
					"title" => $do['title'],
					"color" => $do['color']
				);
			}

		break;
		case 'list':

			if ($params['dateStart'] == '') $params['dateStart'] = current_date();
			if ($params['dateEnd'] == '') $params['dateEnd'] = current_date();

			$sort .= get_people($iduser);

			if ($params['word'] != '') $sort .= " and (title LIKE '%".Cleaner($params['word'])."%' or des LIKE '%".Cleaner($params['word'])."%' or tip LIKE '%".Cleaner($params['word'])."%')";

			if ($params['tip'] != '' and in_array($params['tip'], $fieldsname)) $sort .= " and tip = '".Cleaner($params['tip'])."'";

			if ($params['dateStart'] != '' and $params['dateEnd'] == '') $sort .= " and datum = '".$params['dateStart']."'";
			if ($params['dateStart'] != '' and $params['dateEnd'] != '') $sort .= " and (datum BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
			if ($params['dateStart'] == '' and $params['dateEnd'] != '') $sort .= " and datum < '".$params['dateEnd']."'";

			$lpos = $offset * $limit;
			$j    = 0;

			//print "SELECT * FROM ".$sqlname."tasks WHERE tid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit";

			$result = $db -> query("SELECT * FROM ".$sqlname."tasks WHERE tid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
			while ($da = $db -> fetch($result)) {

				for ($i = 0; $i < count($fieldsname); $i++) {

					switch ($fieldsname[ $i ]) {

						case 'clid':
							$response['data'][ $j ][ $fieldsname[ $i ] ] = $da[ $fieldsname[ $i ] ];
							$response['data'][ $j ]['client']            = current_client($da[ $fieldsname[ $i ] ]);
						break;
						case 'pid':
							$response['data'][ $j ][ $fieldsname[ $i ] ] = $da[ $fieldsname[ $i ] ];
							$response['data'][ $j ]['person']            = current_person($da[ $fieldsname[ $i ] ]);
						break;
						case 'did':
							$response['data'][ $j ][ $fieldsname[ $i ] ] = $da[ $fieldsname[ $i ] ];
							$response['data'][ $j ]['dogovor']           = current_dogovor($da[ $fieldsname[ $i ] ]);
						break;
						case 'iduser':
							$response['data'][ $j ][ $fieldsname[ $i ] ] = current_userlogin($da[ $fieldsname[ $i ] ]);
						break;
						case 'autor':
							$response['data'][ $j ][ $fieldsname[ $i ] ] = current_userlogin($da[ $fieldsname[ $i ] ]);
						break;
						default:
							$response['data'][ $j ][ $fieldsname[ $i ] ] = $da[ $fieldsname[ $i ] ];
						break;

					}

				}

				$j++;

			}

		break;
		case 'info':

			$tid = $db -> getOne("SELECT tid FROM ".$sqlname."tasks WHERE tid = '".intval($params['tid'])."' ".get_people($iduser)." and identity = '$identity'");

			if ($tid < 1 and $params['tid'] != '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание с указанным tid не найдено в пределах аккаунта указанного пользователя.";

			}
			elseif ($tid > 0 and $params['tid'] != '') {

				$cdata = get_taskinfo($params['tid']);

				if (count($cdata) > 0) {

					for ($i = 0; $i < count($fieldsname); $i++) {

						switch ($fieldsname[ $i ]) {
							case 'clid':
								$response['data'][ $fieldsname[ $i ] ] = $cdata[ $fieldsname[ $i ] ];
								$response['data']['client']            = current_client($cdata[ $fieldsname[ $i ] ]);
							break;
							case 'pid':
								$response['data'][ $fieldsname[ $i ] ] = $cdata[ $fieldsname[ $i ] ];
								$response['data']['person']            = current_person($cdata[ $fieldsname[ $i ] ]);
							break;
							case 'did':
								$response['data'][ $fieldsname[ $i ] ] = $cdata[ $fieldsname[ $i ] ];
								$response['data']['dogovor']           = current_dogovor($cdata[ $fieldsname[ $i ] ]);
							break;
							case 'iduser':
								$response['data'][ $fieldsname[ $i ] ] = current_userlogin($cdata[ $fieldsname[ $i ] ]);
							break;
							case 'totime':
								$response['data'][ $fieldsname[ $i ] ] = $cdata[ $fieldsname[ $i ] ];
							break;
							case 'autor':
								$response['data'][ $fieldsname[ $i ] ] = current_userlogin($cdata[ $fieldsname[ $i ] ]);
							break;
							default:
								$response['data'][ $fieldsname[ $i ] ] = $cdata[ $fieldsname[ $i ] ];
							break;

						}

					}

				}
				else {
					$response['result']        = 'Error';
					$response['error']['code'] = '404';
					$response['error']['text'] = "Не найдено";
				}
			}
			elseif ($tid < 1 and $params['tid'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Не найдено";

			}

		break;
		case 'add':

			$taskData = array();

			if ($params['mailalert'] == '') $params['mailalert'] = 'no';

			if ($params['user'] == '') $params['iduser'] = $iduser;
			else {
				$params['iduser'] = current_userbylogin($params['user']);
				$params['autor']  = $iduser;
			}

			$taskData['identity'] = $identity;

			//проверка, что есть название клиента
			if ($params['title'] != '') {

				$hour = date('H') + 1;

				if ($params['datum'] == '') $params['datum'] = current_datum();
				if ($params['totime'] == '') $params['totime'] = $hour.":".date('i');
				if ($params['priority'] == '') $params['priority'] = 0;
				if ($params['speed'] == '') $params['speed'] = 0;
				if ($params['active'] == '') $params['active'] = 'yes';

				foreach ($params as $key => $value) {

					if (in_array($key, $fieldsname)) {

						$taskData[ $key ] = $value;

						switch ($key) {
							case "tid":
							break;
							case "clid":
							case "pid":
							case "did":
								$taskData[ $key ] = intval($value);
							break;
							case "tip":
								$taskData[ $key ] = getTipTask(untag($value));
							break;
							default:
								$taskData[ $key ] = untag($value);
							break;
						}

					}

				}

				//print_r($taskData);

				if (!empty($taskData)) {

					try {

						$todo = new \Salesman\Todo();
						$task = $todo -> add($params['iduser'], $taskData);

						//print_r($task);

						if ($task['result'] == 'Success') {

							$response['result'] = 'Успешно';
							$response['data']   = $task['id'];

						}
						else {

							$response['result']        = 'Error';
							$response['error']['code'] = '409';
							$response['error']['text'] = implode("\n", $task['notice']);

						}

					}
					catch (Exception $e) {

						$response['result']        = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

					}

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '405';
					$response['error']['text'] = "Отсутствуют параметры";

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - Заголовок";

			}

		break;
		case 'update':

			if ($params['mailalert'] == '') $params['mailalert'] = 'no';

			//проверка принадлежности clid к данному аккаунту
			$tid = $db -> getOne("SELECT tid FROM ".$sqlname."tasks WHERE tid = '".$params['tid']."' and identity = '$identity'");

			if ($tid < 1) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание не найдено.";

			}
			else {

				$i        = 0;
				$name     = $data = array();
				$taskData = array();

				if (in_array($key, $fieldsname)) {

					$taskData[ $key ] = $value;

					switch ($key) {
						case "tid":
						break;
						case "clid":
						case "pid":
						case "did":
							$taskData[ $key ] = intval($value);
						break;
						case "tip":
							$taskData[ $key ] = getTipTask(untag($value));
						break;
						default:
							$taskData[ $key ] = untag($value);
						break;
					}

				}

				if (!empty($taskData)) {

					try {

						$todo = new \Salesman\Todo();
						$task = $todo -> edit($tid, $taskData);

						if ($task['result'] == 'Success') {

							$response['result'] = 'Успешно';
							$response['data']   = $task['id'];

						}
						else {

							$response['result']        = 'Error';
							$response['error']['code'] = '409';
							$response['error']['text'] = implode("\n", $task['notice']);

						}

					}
					catch (Exception $e) {

						$response['result']        = 'Error';
						$response['error']['code'] = '500';
						$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

					}

				}

			}

			if ($params['tid'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

			}

		break;
		case 'doit':

			//проверка принадлежности clid к данному аккаунту
			$tid = $db -> getOne("SELECT tid FROM ".$sqlname."tasks WHERE tid = '".$params['tid']."' and identity = '$identity'");

			if ($params['user'] != '') $taskData['iduser'] = $params['login'];

			if ($tid < 1) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание не найдено.";

			}
			else {

				try {

					$taskData['rezultat'] = ($params['description'] == '') ? 'Выполнено напоминание' : $params['description'];

					$todo = new \Salesman\Todo();
					$task = $todo -> doit($tid, $taskData);

					if ($task['result'] == 'Success') {

						$response['result']    = 'Успешно';
						$response['data']      = $tid;
						$response['message']   = implode("\n", $task['text']);
						$response['historyID'] = $task['cid'];

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = '409';
						$response['error']['text'] = implode("\n", $task['notice']);

					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = '500';
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			if ($params['tid'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

			}

		break;
		case 'delete':

			//проверка принадлежности clid к данному аккаунту
			$tid = $db -> getOne("SELECT tid FROM ".$sqlname."tasks WHERE tid = '".$params['tid']."' ".get_people($iduser)." and identity = '$identity'");

			if ($tid < 1) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание не найдено.";

			}
			else {

				try {

					$task = new \Salesman\Todo();
					$rez  = $task -> remove($tid);

					if ($rez['result'] == 'Success') {

						$response['result']  = 'Успешно';
						$response['data']    = $tid;
						$response['message'] = yimplode("\n", $rez['text']);

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = '409';
						$response['error']['text'] = yimplode("\n", $rez['notice']);

					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = '500';
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			if ($params['tid'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

			}

		break;
		case 'addhist':

			if ($params['user'] == '') $params['iduser'] = $iduser;
			else $params['iduser'] = current_userbylogin($params['user']);

			$params['tip'] = getTipTask(untag($params['tip']));

			//проверка, что есть название клиента
			if (($params['clid'] != '' || $params['pid'] != '' || $params['did'] != '') && $params['content'] != '') {

				$params['identity'] = $identity;

				try {

					$hid = addHistorty(array(
						'iduser'   => $params['iduser'],
						'clid'     => $params['clid'],
						'pid'      => $params['pid'],
						'did'      => $params['did'],
						'datum'    => $params['datum'],
						'des'      => $params['content'],
						'tip'      => $params['tip'],
						'identity' => $identity
					));

					$response['result'] = 'Успешно';
					$response['data']   = $hid;

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = '500';
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '405';
				$response['error']['text'] = "Отсутствуют параметры";

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

exit();
?>