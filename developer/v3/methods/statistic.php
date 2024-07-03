<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $action;
global $identity, $rootpath, $path;

//для приема массива клиентов для добавления
use Salesman\Statistic;

$http = ( $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://' ).$_SERVER['HTTP_HOST'];

/**
 * Основные обработчики
 */

$ilogin = $params['user'];

if ($params['user'] != '') {
	$params['user'] = current_userbylogin($params['user']) + 0;
}

switch ($action) {

	//Выврд общей статистики
	case 'list':

		$response = Statistic ::all($params['period'], $params);

	break;

	// Вывод кол-ва новых клиентов
	case 'clients':

		$response = Statistic ::clients($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $http."/".$response['url'];
		}

	break;

	// Вывод статистики по новым сделкам
	case 'dealsNew':

		$response = Statistic ::dealsNew($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $http."/".$response['url'];
		}

	break;

	// Вывод статистики по закрытым сделкам
	case 'dealsClose':

		$response = Statistic ::dealsClose($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $http."/".$response['url'];
		}

	break;

	// Вывод статистики по новым счетам
	case 'invoices':

		$response = Statistic ::invoices($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $http."/".$response['url'];
		}

	break;

	// Отмена платежа
	case 'payments':

		$response = Statistic ::payments($params['period'], $params);

		if($response['url'] != '') {
			$response['url'] = $http."/".$response['url'];
		}

	break;

}

if(!empty($ilogin)){

	$response['comment'] = 'Данные для пользователя с логином '.$params['login'];

}
if(!empty($ilogin) && $params['user'] == 0){

	$response['comment'] = 'Пользователь с логином '.$ilogin.' не найден. Данные для пользователя с логином '.$params['login'];

}

print $rez = json_encode_cyr($response);