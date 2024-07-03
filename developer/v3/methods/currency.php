<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

use Salesman\Currency;

global $action;
global $identity, $rootpath, $path;

$response = [];

switch ($action) {

	case 'info':

		$cur = new Currency();
		$cdata = $cur -> currencyInfo($params['id']);

		if ((int)$cdata['id'] == 0 && (int)$params['id'] > 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись с указанным id не найдена в пределах аккаунта указанного пользователя.";

		}
		elseif ((int)$cdata['id'] > 0 && (int)$params['id'] > 0) {

			if ($cdata['id'] > 0) {

				$response['data'] = $cdata;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		elseif ((int)$cdata['id'] < 1 && $params['id'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id записи";

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	case 'list':

		$cur = new Currency();
		$response['data'] = $cur -> currencyList();

	break;

	case 'add':

		$Data = [];

		$Data['identity'] = $identity;

		//проверка, что есть название клиента
		if ($params['name'] != '') {

			$Data['datum'] = ($params['datum'] == '') ? current_datum() : $params['datum'];
			$Data['name'] = ($params['name'] == '') ? "Без названия" : $params['name'];
			$Data['code'] = ($params['code'] == '') ? '' : $params['code'];
			$Data['view'] = ($params['view'] == '') ? '' : $params['view'];
			$Data['course'] = ( pre_format($params['course']) > 0 ) ? pre_format($params['course']) : 0;


			if (!empty($Data)) {

				try {

					$cur = new Currency();
					$currency = $cur -> edit(0, $Data);

					if ($currency > 0) {

						$response['result'] = 'Успешно';
						$response['data']   = $currency;

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = 409;
						$response['error']['text'] = "Не удалось выполнить";

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
			$response['error']['text'] = "Отсутствуют параметры - Название";

		}

	break;

	case 'update':

		$cur = new Currency();
		$cdata = $cur -> currencyInfo($params['id']);

		if ( $cdata['id'] < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись не найдена";

		}
		else {

			$Data['datum'] = ($params['datum'] == '') ? current_datum() : $params['datum'];
			$Data['name'] = ($params['name'] == '') ? "Без названия" : $params['name'];
			$Data['code'] = ($params['code'] == '') ? '' : $params['code'];
			$Data['view'] = ($params['view'] == '') ? '' : $params['view'];
			$Data['course'] = ( pre_format($params['course']) > 0 ) ? pre_format($params['course']) : 0;

			if (!empty($Data)) {

				try {

					$currency = $cur -> edit((int)$cdata['id'], $Data);

					if ($currency > 0) {

						$response['result'] = 'Успешно';
						$response['data']   = $currency;

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = 409;
						$response['error']['text'] = "Не удалось выполнить";

					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

				}

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
		$cur = new Currency();
		$cdata = $cur -> currencyInfo((int)$params['id']);

		if ( (int)$cdata['id'] < 1 ) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Запись не найдена";

		}
		else {

			try {

				$rez  = $cur -> delete( (int)$cdata['id'] );

				if ($rez['result'] == 'successe') {

					$response['result']  = 'Успешно';
					$response['data']    = (int)$cdata['id'];
					$response['message'] = $rez['message'];

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = 409;
					$response['error']['text'] = $rez['error']['text'];

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
			$response['error']['text'] = "Отсутствуют параметры - id записи";

		}

	break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
	break;

}

print $rez = json_encode_cyr($response);