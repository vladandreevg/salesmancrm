<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

use Salesman\Client;

global $action;

//для приема массива клиентов для добавления
$clients          = $params['client'];

global $identity, $rootpath, $path;

$response = [];

$fields = ( new Client() ) -> Fields((string)$params['fields']);

/**
 * Основные обработчики
 */

switch ($action) {

	//Вывод списка доступных полей
	case 'fields':

		$response['data']['clid']        = "Уникальный идентификатор записи клиента в CRM";
		$response['data']['uid']         = "Уникальный идентификатор записи клиента в вашей ИС";
		$response['data']['type']        = "Тип записи (допустимые - client,person,concurent,contractor,parnter)";
		$response['data']['date_create'] = "Дата создания. Timestamp";
		$response['data']['date_edit']   = "Дата последнего изменения. Timestamp";

		$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='client' and fld_on='yes' and identity = '$identity'");
		while ($do = $db -> fetch($resf)) {

			$response['data'][ $do['fld_name'] ] = $do['fld_title'];

		}

	break;

	//Вывод списка клиентов
	case 'list':

		$params['page'] = ((int)$params['offset'] > 0) ? (int)$params['offset'] : 0;
		$params['ord']  = ($params['order'] != '') ? $params['order'] : 'datum';
		$params['tuda'] = ($params['first'] == 'old') ? '' : 'DESC';

		if (!empty($params['dateStart'])) {
			$params['d1'] = $params['dateStart'];
		}
		if (!empty($params['dateEnd'])) {
			$params['d2'] = $params['dateEnd'];
		}

		$data = (new Client()) -> list($params);

		$response['data'] = $data['list'];

		unset($data['list']);

		$response['params'] = $data;
		$response['count'] = $data['count'];

	break;

	//Получение информации о клиенте по id
	case 'info':

		$s = ($params['uid'] != '') ? "AND uid = '".$params['uid']."'" : "AND clid = '".$params['clid']."'";

		if( isset($params['inn']) && $params['inn'] != ''){

			if($params['uid'] == '' && $params['clid'] == '') {
				$s = '';
			}

			$s .= "AND FIND_IN_SET('".$params['inn']."', REPLACE(recv, ';',',')) ";

		}

		if($s != '') {

			$clid = (int)$db -> getOne( "SELECT clid FROM ".$sqlname."clientcat WHERE clid > 0 $s AND iduser IN (".yimplode(",", get_people( $iduser, "yes")).") and identity = '$identity'" );

			//print $db -> lastQuery();

			if ( $clid == 0 && (int)$params['clid'] > 0 ) {

				$response['result']        = 'Error';
				$response['error']['code'] = 403;
				$response['error']['text'] = "Клиент не найден в пределах аккаунта указанного пользователя.";

			}
			elseif ( $clid > 0 ) {

				$result = Client::info($clid);

				$response['data'] = $result['client'];
				$response['data']['person'] = $result['person'];

				unset($response['data']['recv']);

				if ( !$params['bankinfo'] ) {
					unset($response['data']['bankinfo']);
				}

				if ( !$params['uids'] ) {
					unset($response['data']['uids']);
				}

				if ( $params['contacts'] ) {

					$contacts = [];

					$queryArray = getFilterQuery( 'person', [
						'clid'      => $clid,
						'haveEmail' => "yes",
						'fields'    => [
							'person',
							'ptitle',
							'clid',
							'tel',
							'mob',
							'mail',
							'iduser',
							'rol',
							'date_create'
						]
					] );
					$response['data']['contacts'] = $db -> getAll( $queryArray['query'] );

				}

			}
			elseif ( $clid == 0 && $params['uid'] == '' && $params['inn'] == '' ) {

				$response['result']        = 'Error';
				$response['error']['code'] = 405;
				$response['error']['text'] = "Отсутствуют параметры клиента";

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		else{

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры поиска клиента - clid, uid или inn";

		}

	break;

	//Добавление клиента
	case 'add':

		$Client   = new Client();
		$response = $Client -> add($params);

	break;

	//Добавление списка клиентов
	case 'add.list':

		$iClient = new Client();

		foreach ($clients as $client) {

			$client['iduser'] = (!isset($client['user'])) ? $iduser : current_userbylogin($client['user']);
			$response[] = $iClient -> add($client);

		}

	break;

	//Изменение клиента
	case 'update':

		$params['fromapi'] = true;

		if( !empty($params['uid']) && (int)$params['clid'] == 0 ){
			$params['clid'] = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE uid = '$params[uid]' and identity = '$identity'");
		}

		if( is_array($params['phone']) ){
			$params['phone'] = yimplode(",", $params['phone']);
		}

		$Client   = new Client();
		$response = $Client -> fullupdate((int)$params['clid'], $params);

	break;

	//Удаление клиента
	case 'delete':

		$Client   = new Client();
		$response = $Client -> delete((int)$params['clid']);

	break;

	//Передача клиента
	case 'change.user':

		$Client   = new Client();
		$response = $Client -> changeUser((int)$params['clid'], $params);

	break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 402;
		$response['error']['text'] = 'Неизвестный метод';
	break;

}

print $rez = json_encode_cyr($response);