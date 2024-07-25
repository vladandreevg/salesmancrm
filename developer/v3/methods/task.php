<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $action;
global $identity, $rootpath, $path;

use Salesman\Todo;

//поля
$fieldsname = [
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
	"did",
	"readonly",
	"day",
	"status"
];
$fields     = [
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
	"did"      => "ID сделки",
	"readonly" => "Только для чтения",
	"day"      => "На весь день",
	"status"   => "Статус выполнения"
];

switch ($action) {

	case 'fields':

		foreach ($fields as $key => $val) {
			$response['data'][ $key ] = $val;
		}

	break;

	case 'tips':

		$re = $db -> query("SELECT * FROM {$sqlname}activities WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"title"  => $do['title'],
				"color"  => $do['color'],
				"filter" => $do['filter']
			];

		}

	break;

	case 'info':

		$tid = (int)$db -> getOne("SELECT tid FROM {$sqlname}tasks WHERE tid = '".$params['id']."' and identity = '$identity'");

		if ($tid == 0 && $params['id'] != '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Напоминание с указанным id не найдено в пределах аккаунта указанного пользователя.";

		}
		elseif ($tid > 0 && $params['id'] != '') {

			$cdata = Todo ::info($tid);

			if (!empty($cdata)) {

				$todo = [];

				foreach ($fieldsname as $field) {

					switch ($field) {

						case 'clid':

							$todo[ $field ] = (int)$cdata[ $field ];
							$todo['client'] = current_client($cdata[ $field ]);

						break;
						case 'pid':

							$todo['person'] = [];

							$pids = $cdata[ $field ];

							foreach ($pids as $pid) {

								$todo['person'][] = [
									"pid"    => (int)$pid,
									"person" => current_person($pid)
								];

							}

						break;
						case 'did':

							$todo[ $field ]  = (int)$cdata[ $field ];
							$todo['dogovor'] = current_dogovor($cdata[ $field ]);

						break;
						case 'iduser':

							$todo[ $field ] = (int)$cdata[ $field ];
							$todo["user"]   = current_userlogin($cdata[ $field ]);

						break;
						case 'autor':

							$todo[ $field ]     = (int)$cdata[ $field ];
							$todo["autorlogin"] = current_userlogin($cdata[ $field ]);

						break;
						case 'totime':

							$todo[ $field ] = getTime((string)$cdata[ $field ]);

						break;
						default:

							$todo[ $field ] = $cdata[ $field ];

						break;

					}

				}

				$response['data'] = $todo;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		elseif ($tid < 1 && $params['id'] == '') {

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

		if(isset($params['status'])){

			$params['active'] = "no";

			if((int)$params['status'] == 1) {
				$sort .= " and {$sqlname}tasks.status = '".$params['status']."'";
			}

			elseif((int)$params['status'] == 2) {
				$sort .= " and {$sqlname}tasks.status = '".$params['status']."'";
			}

		}

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
			$sort .= " and (title LIKE '%".Cleaner( $params['word'] )."%' or des LIKE '%".Cleaner( $params['word'] )."%' or tip LIKE '%".Cleaner( $params['word'] )."%')";
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

		if ($params['user'] != '') {
			$sort .= " and {$sqlname}tasks.iduser = '".current_userbylogin( $params['user'] )."'";
		}
		else {
			$sort .= " and {$sqlname}tasks.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		if ((int)$params['active'] != '') {
			$sort .= " and {$sqlname}tasks.active = '".$params['active']."'";
		}

		if ((int)$params['clid'] > 0) {
			$sort .= " and {$sqlname}tasks.clid = '".$params['clid']."'";
		}
		if ((int)$params['did'] > 0) {
			$sort .= " and {$sqlname}tasks.did = '".$params['did']."'";
		}

		$lpos = $offset * $limit;
		$j    = 0;

		//print "SELECT * FROM {$sqlname}tasks WHERE tid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit";

		$result = $db -> query("SELECT * FROM {$sqlname}tasks WHERE tid > 0 $sort and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		while ($da = $db -> fetch($result)) {

			$todo = [];

			foreach ($fieldsname as $field) {

				switch ($field) {

					case 'clid':

						$todo[ $field ] = (int)$da[ $field ];
						$todo['client'] = current_client($da[ $field ]);

					break;
					case 'pid':

						$pids = $da[ $field ];

						foreach ($pids as $pid) {

							$todo['person'][] = [
								"pid"    => (int)$pid,
								"person" => current_person($pid)
							];

						}

					break;
					case 'did':

						$todo[ $field ]  = (int)$da[ $field ];
						$todo['dogovor'] = current_dogovor($da[ $field ]);

					break;
					case 'iduser':

						$todo[ $field ] = (int)$da[ $field ];
						$todo["user"]   = current_userlogin($da[ $field ]);

					break;
					case 'autor':

						$todo[ $field ]     = (int)$da[ $field ];
						$todo["autorlogin"] = current_userlogin($da[ $field ]);

					break;
					case 'totime':

						$todo[ $field ] = getTime((string)$da[ $field ]);

					break;
					default:

						$todo[ $field ] = $da[ $field ];

					break;

				}

			}

			$response['data'][] = $todo;

		}

	break;

	case 'add':

		$taskData = [];

		if ($params['user'] == '') {
			$params['iduser'] = $iduser;
		}
		else {

			$params['iduser'] = current_userbylogin($params['user']);
			$params['autor']  = $iduser;

		}

		$taskData['identity'] = $identity;

		//проверка, что есть название клиента
		if ($params['title'] != '') {

			$hour = date('H') + 1;

			$params['datum'] = ($params['datum'] == '') ? current_datum() : $params['datum'];
			$params['totime'] = ($params['totime'] == '') ? $hour.":".date('i') : $params['totime'];
			$params['priority'] = ($params['priority'] == '') ? 0 : $params['priority'];
			$params['speed'] = ($params['speed'] == '') ? 0 : $params['speed'];
			$params['active'] = ($params['active'] == '') ? "yes" : $params['active'];

			foreach ($params as $key => $value) {

				if (in_array($key, $fieldsname)) {

					$taskData[ $key ] = $value;

					switch ($key) {
						case "pid":

							$value            = str_replace(",", ";", $value);
							$taskData[ $key ] = $value;

						break;
						case "clid":
						case "did":

							$taskData[ $key ] = (int)$value;

						break;
						case "tip":

							$taskData[ $key ] = ($value != '') ? getTipTask(untag($value)) : "Задача";

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

					$todo = new Todo();
					$task = $todo -> add((int)$params['iduser'], $taskData);

					//print_r($task);

					if ($task['result'] == 'Success') {

						$response['result'] = 'Успешно';
						$response['data']   = (int)$task['id'];

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = 409;
						$response['error']['text'] = implode("\n", $task['notice']);

					}

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

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - Заголовок";

		}

	break;

	case 'update':

		if ($params['user'] == '') {
			$params['iduser'] = $iduser;
		}
		else {

			$params['iduser'] = current_userbylogin($params['user']);
			$params['autor']  = $iduser;

		}

		//проверка принадлежности clid к данному аккаунту
		$tid = (int)$db -> getOne("SELECT tid FROM {$sqlname}tasks WHERE tid = '".$params['id']."' and identity = '$identity'");

		if ($tid < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Напоминание не найдено.";

		}
		else {

			$name     = $data = [];
			$taskData = [];

			foreach ($params as $key => $value) {

				if (in_array($key, $fieldsname)) {

					$taskData[ $key ] = $value;

					switch ($key) {
						case "pid":

							$value            = str_replace(",", ";", $value);
							$taskData[ $key ] = $value;

						break;
						case "clid":
						case "did":

							$taskData[ $key ] = (int)$value;

						break;
						case "tip":

							$taskData[ $key ] = ($value != '') ? getTipTask(untag($value)) : "Задача";

						break;
						default:

							$taskData[ $key ] = untag($value);

						break;
					}

				}

			}

			//print_r($taskData);
			//exit();

			if (!empty($taskData)) {

				try {

					$todo = new Todo();
					$task = $todo -> edit($tid, $taskData);

					//print_r($task);

					if ($task['result'] == 'Success') {

						$response['result'] = 'Успешно';
						$response['data']   = $task['data'];

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = 409;
						$response['error']['text'] = implode("\n", $task['notice']);

					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

			}

		}

		if ((int)$params['id'] == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

		}

	break;

	case 'doit':

		//проверка принадлежности clid к данному аккаунту
		$tid = (int)$db -> getOne("SELECT tid FROM {$sqlname}tasks WHERE tid = '".$params['id']."' and identity = '$identity'");

		if ($params['user'] != '') {
			$taskData['iduser'] = $params['login'];
		}

		if ($tid < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Напоминание не найдено.";

		}
		else {

			try {

				$taskData['rezultat'] = ($params['description'] == '') ? 'Выполнено напоминание' : $params['description'];
				$taskData['tip']      = $params['tip'];

				// если статус не пришел, то берем как успешное
				$taskData['status'] = $params['status'] ?? 1;

				$todo = new Todo();
				$task = $todo -> doit($tid, $taskData);

				if ($task['result'] == 'Success') {

					$response['result']    = 'Успешно';
					$response['data']      = $tid;
					$response['message']   = implode("\n", $task['text']);
					$response['historyID'] = (int)$task['cid'];

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = 409;
					$response['error']['text'] = implode("\n", $task['notice']);

				}

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
			$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

		}

	break;

	case 'delete':

		//проверка принадлежности clid к данному аккаунту
		$tid = (int)$db -> getOne("SELECT tid FROM {$sqlname}tasks WHERE tid = '".$params['id']."' ".get_people($iduser)." and identity = '$identity'");

		if ($tid < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Напоминание не найдено.";

		}
		else {

			try {

				$task = new Todo();
				$rez  = $task -> remove($tid);

				if ($rez['result'] == 'Success') {

					$response['result']  = 'Успешно';
					$response['data']    = (int)$tid;
					$response['message'] = yimplode("\n", $rez['text']);

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = 409;
					$response['error']['text'] = yimplode("\n", $rez['notice']);

				}

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
			$response['error']['text'] = "Отсутствуют параметры - tid напоминания";

		}

	break;

	case 'history':

		if ($params['user'] == '') {
			$params['iduser'] = (int)$iduser;
		}
		else {
			$params['iduser'] = current_userbylogin( $params['user'] );
		}

		$params['tip'] = getTipTask(untag($params['tip']));

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

	default:

		$response['result']        = 'Error';
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Неизвестный метод';

	break;

}

print json_encode_cyr($response);