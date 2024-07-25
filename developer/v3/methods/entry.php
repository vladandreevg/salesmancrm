<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $action;
global $identity, $rootpath, $path;

$astatus = [
	0 => 'Новое',
	1 => 'Обработано',
	2 => 'Отменено'
];

$id = $params['id'];

switch ($action) {

	case 'info':

		$entry = $db -> getRow("SELECT * FROM {$sqlname}entry WHERE ide = '$id' and identity = '$identity'");

		//очистим от цифровых индексов и приведем соответствие типов
		$entry = data2dbtypes($entry, "{$sqlname}entry");

		$entry['products'] = $db -> getAll("SELECT * FROM {$sqlname}entry_poz WHERE ide = '$id' and identity = '$identity'");

		//очистим от цифровых индексов
		foreach ($entry['products'] as $i => $item) {

			$entry['products'][ $i ] = data2dbtypes($entry['products'][ $i ], "{$sqlname}entry_poz");

		}


		$response['data'] = $entry;

	break;

	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'datum';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and DATE_FORMAT({$sqlname}entry.datum, '%y-%m-%d') = '".$params['dateStart']."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and ({$sqlname}entry.datum BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and DATE_FORMAT({$sqlname}entry.datum, '%y-%m-%d') < '".$params['dateEnd']."'";
		}

		if ($params['status'] != '') {
			$sort .= " and {$sqlname}entry.status IN (".$params['status'].")";
		}

		if ($params['user'] != '') {
			$sort .= " and {$sqlname}entry.iduser = '".current_userbylogin( $params['user'] )."'";
		}
		else {
			$sort .= " and {$sqlname}entry.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		$lpos = $offset * $limit;

		$query = "
			SELECT
				{$sqlname}entry.ide as ide,
				{$sqlname}entry.datum as datum,
				{$sqlname}entry.datum_do as datum_do,
				{$sqlname}entry.pid as pid,
				{$sqlname}entry.clid as clid,
				{$sqlname}entry.did as did,
				{$sqlname}entry.content as content,
				{$sqlname}entry.status as status,
				{$sqlname}entry.iduser as iduser,
				{$sqlname}entry.autor as autor,
				{$sqlname}clientcat.title as client,
				{$sqlname}personcat.person as person,
				{$sqlname}dogovor.title as deal,
				{$sqlname}user.title as user
			FROM {$sqlname}entry
				LEFT JOIN {$sqlname}user ON {$sqlname}entry.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}personcat ON {$sqlname}entry.pid = {$sqlname}personcat.pid
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}entry.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}entry.did = {$sqlname}dogovor.did
			WHERE
				{$sqlname}entry.ide > 0
				$sort
				and {$sqlname}entry.identity = '$identity'
			ORDER BY $order $first LIMIT $lpos,$limit
		";

		$field_types = db_columns_types( "{$sqlname}entry_poz" );

		$result = $db -> query($query);
		while ($da = $db -> fetch($result)) {

			$products = $db -> getAll("SELECT * FROM {$sqlname}entry_poz WHERE ide = '$da[ide]' and identity = '$identity'");

			//очистим от цифровых индексов
			foreach ($products as $i => $item) {
				$products[ $i ] = data2dbtypes($item, "{$sqlname}entry_poz");
			}

			//print_r($products);

			$response['data'][] = [
				"ide"        => (int)$da['ide'],
				"datum"      => $da['datum'],
				"datum_do"   => $da['datum_do'] == '0000-00-00 00:00:00' ? NULL : $da['datum_do'],
				"content"    => $da['content'],
				"clid"       => (int)$da['clid'],
				"client"     => $da['client'],
				"pid"        => (int)$da['pid'],
				"person"     => $da['person'],
				"did"        => (int)$da['did'],
				"deal"       => $da['deal'],
				"iduser"     => (int)$da['iduser'],
				"user"       => $da['user'],
				"idautor"    => (int)$da['autor'],
				"autor"      => current_user($da['autor']),
				"status"     => (int)$da['status'],
				"statusName" => strtr((int)$da['status'], $astatus),
				"products"   => $products
			];

		}

		if ($db -> affectedRows() == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	case 'status':

		$id = (int)$db -> getOne("SELECT ide FROM {$sqlname}entry WHERE (ide = '$params[id]' or uid = '$params[uid]') and identity = '$identity'");

		//проверка, что есть название клиента
		if ($id > 0) {

			if ((int)$params['uid'] > 0) {
				$db -> query( "update {$sqlname}entry set uid = '$params[uid]' where ide = '$id' and identity = '$identity'" );
			}
			elseif ((int)$params['status'] > 0) {
				$db -> query( "update {$sqlname}entry set status = '$params[status]' where ide = '$id' and identity = '$identity'" );
			}

			$response['result'] = 'Успешно';
			$response['data']   = $id;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 402;
		$response['error']['text'] = 'Неизвестный метод';
	break;

}

print $rez = json_encode_cyr($response);