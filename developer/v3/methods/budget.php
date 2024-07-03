<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

use Salesman\Budget;

global $action;
global $identity, $rootpath, $path;

switch ($action) {

	//Вывод списка имен полей таблицы Бюджет
	case 'fields':

		$response['data'] = Budget ::fields()['fields'];

		break;

	//Получение информации по расходу/доходу
	case 'info':

		$xresponse = Budget ::info((int)$params['id']);

		if( !empty($xresponse['budget']) ){
			$response['data'] = $xresponse['budget'];
		}
		else{
			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = 'Не найдено';
		}

		break;

	//Список расходов
	case 'list':

		$xresponse = Budget ::getJournal((array)$params);

		$list = [];
		foreach ($xresponse as $x) {
			unset($x['ddo'], $x['tip']);
			$list[] = $x;
		}

		$response['data'] = $list;

		break;

	// Добавление расхода/дохода
	case 'add':

		$params['cat'] = (int)$params['category'];
		$params['des'] = $params['description'];
		$response = Budget ::edit(0, $params);

		break;

	// Изменение расхода/дохода
	case 'update':

		$params['cat'] = (int)$params['category'];
		$params['des'] = $params['description'];
		$params['dogproviderid'] = (int)$db -> getOne("SELECT id FROM {$sqlname}dogprovider WHERE bid = '$params[id]'");

		// дополняем данными для изменения в таблице dogprovider
		$x = $db -> getRow("SELECT did, conid, partid FROM {$sqlname}dogprovider WHERE bid = '$params[id]'");
		if((int)$x['conid'] > 0){
			$params['clid'] = (int)$x['conid'];
		}
		if((int)$x['partid'] > 0){
			$params['clid'] = (int)$x['partid'];
		}
		if((int)$x['did'] > 0){
			$params['did'] = (int)$x['did'];
		}

		$response = Budget ::edit((int)$params['id'], $params);

		break;

	// Проведение платежа
	case 'doit':

		$response = Budget ::doit((int)$params['id']);

		break;

	// Отмена платежа
	case 'undoit':

		$response = Budget ::undoit((int)$params['id']);

		break;

	// Перемещение средств между счетами
	case 'move':

		$params['rs'] = (int)$params['from'];
		$params['rs_move'] = (int)$params['to'];
		$response = Budget ::move($params);

		break;

	// Отмена перемещения средств между счетами
	case 'unmove':

		$response = Budget ::unmove((int)$params['id']);

		break;

	// Удаление расхода/дохода
	case 'delete':

		$response = Budget ::delete((int)$params['id']);

		break;

	// Добавление категории расхода/дохода
	case 'category':

		$response = Budget ::getCategory();

		break;

	// Добавление категории расхода/дохода
	case 'categoryadd':

		$response = Budget ::editCategory(0, $params);

		break;

	// Изменение категории расхода/дохода
	case 'categoryedit':

		$response = Budget ::editCategory((int)$params['id'], $params);

		break;

	// Удаление категории расхода/дохода
	case 'categorydelete':

		$response = Budget ::deleteCategory((int)$params['id']);

		break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 402;
		$response['error']['text'] = 'Неизвестный метод';
		break;

}

print $rez = json_encode_cyr($response);