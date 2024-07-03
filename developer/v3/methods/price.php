<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $action;
global $identity, $rootpath, $path;

use Salesman\Price;

//составляем списки доступных полей для прайса
$ifields[] = 'n_id';
$ifields[] = 'artikul';
$ifields[] = 'title';
$ifields[] = 'descr';
$ifields[] = 'edizm';
$ifields[] = 'datum';
$ifields[] = 'pr_cat';
$ifields[] = 'nds';

$fields = $isfields;

$http = ( $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://' ).$_SERVER['HTTP_HOST'];

//фильтр вывода по полям из запроса или все доступные
if ($params['fields'] != '') {

	$fis = explode(",", $params['fields']);
	foreach ($fis as $fi) {

		if (in_array($fi, $ifields)) {
			$fields[] = $fi;
		}

	}

}

switch ($action) {

	//Вывод списка имен полей таблицы Прайс
	case 'fields':

		$response   = Price::fields();

	break;

	//Информация о Контакте
	case 'info':

		$response   = Price::info((int)$params['id'], $params['artikul']);

		if($response['result'] == 'Error') {

			$error = $response['error'];

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = $error;

		}

	break;

	//Вывод списка
	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'title';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		if ($params['word'] != '') {
			$sort .= " and (artikul LIKE '%".Cleaner( $params['word'] )."%' or title LIKE '%".Cleaner( $params['word'] )."%' or descr LIKE '%".Cleaner( $params['word'] )."%')";
		}

		if ($params['archive'] == 'yes') {
			$sort .= " and {$sqlname}price.archive = 'yes'";
		}
		elseif ($params['archive'] == 'no') {
			$sort .= " and {$sqlname}price.archive != 'yes'";
		}

		if($params['category'] > 0) {
			$sort .= " and ({$sqlname}price.pr_cat = '$params[category]')";
		}

		$lpos = $offset * $limit;

		$result = $db -> query("SELECT * FROM {$sqlname}price WHERE n_id > 0 $sort and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		while ($da = $db -> fetch($result)) {

			$response['data'][] = [
				"prid"     => (int)$da['n_id'],
				"artikul"  => $da['artikul'],
				"title"    => $da['title'],
				"content"  => $da['content'],
				"edizm"    => $da['edizm'],
				"category" => (int)$da['pr_cat'],
				"price_in" => (float)$da['price_in'],
				"price_1"  => (float)$da['price_1'],
				"price_2"  => (float)$da['price_2'],
				"price_3"  => (float)$da['price_3'],
				"price_4"  => (float)$da['price_4'],
				"price_5"  => (float)$da['price_5'],
				"archive"  => $da['archive']
			];

		}

		$response['count'] = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}price WHERE n_id > 0 ".$sort." and identity = '$identity'");

	break;

	// Добавление прайсовой позиции
	case 'add':

		$price = new Price();
		$response = $price -> edit(0, $params);

	break;

	// Изменение прайсовой позиции
	case 'update':

		$response   = Price::info((int)$params['id'], (string)$params['artikul']);

		if($response['result'] != 'Error') {

			//$prid = (isset($response['price'])) ? $response['price']['prid'] : $response['prid'];
			$prid = (int)$response['prid'];

			if(isset($params['newartikul'])) {
				$params['artikul'] = $params['newartikul'];
			}
			if(isset($params['description'])) {
				$params['descr'] = $params['description'];
			}
			if(isset($params['category'])) {
				$params['idcategory'] = (int)$params['category'];
			}

			//print_r($params);

			$price    = new Price();
			$response = $price -> edit($prid, $params);

		}
		else{

			$error = $response['error'];

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = $error;

		}

	break;

	// Удаление прайсовой позиции
	case 'delete':

		$response   = Price::info((int)$params['id'], (string)$params['artikul']);

		if($response['result'] != 'Error') {

			$prid = (int)$response['prid'];

			$response = Price ::delete($prid);

		}
		else{

			$error = $response['error'];

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = $error;

		}

	break;

	// Добавление категории
	case 'category':

		$response = Price::listCategory();

	break;

	// Добавление категории
	case 'category.add':

		$price = new Price();
		$response = $price -> editCategory(0, $params);

	break;

	// Изменение категории
	case 'category.update':

		$id = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}price_cat WHERE idcategory = '$params[id]' AND identity = '$identity'");

		if($id > 0) {

			$price    = new Price();
			$response = $price -> editCategory($id, $params);

		}
		else{

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Позиция не найдена";

		}

	break;

	// Удаление категории
	case 'category.delete':

		$id = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}price_cat WHERE idcategory = '$params[id]' AND identity = '$identity'");

		if($id > 0) {

			$text = Price ::deleteCategory((int)$params['id']);

			$response = [
				"result" => "Success",
				"text"   => $text,
				"data"   => $id
			];

		}
		else{

			$response = [];

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Позиция не найдена";

		}

	break;

	default:

		$response['result']        = 'Error';
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Неизвестный метод';

		break;

}

print $rez = json_encode_cyr($response);