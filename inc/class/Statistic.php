<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use pChart\pChart;
use pChart\pData;

/**
 * Класс для получения статистических данных
 *
 * Class Statistic
 *
 * @package Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example:
 * ```php
 * $statistic = new Salesman\Statistic();
 * $result = $statistic -> clients($params);
 * $count = $result['count'];
 * $period
 * ```
 */
class Statistic {

	public $response = [];
	public $period = [];

	public static $periods = [
		"today",
		"yestoday",
		"week",
		"calendarweek",
		"calendarweekprev",
		"month",
		"monthprev",
		"prevmonth",
		"quart",
		"quartprev",
		"year",
		"yearprev"
	];

	/**
	 * Вывод полной статистики по всем показателям
	 *
	 * @param string $period - период отчетности
	 * @param array  $params - массив параметров
	 *
	 * @return array
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - [result] = Ok
	 *         - [title]
	 *         - [count]
	 *         - [info]
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * @throws \Exception
	 * @example $Statistic = \Salesman\Statistic::all($period,$params);
	 */
	public static function all(string $period, array $params = []): array {

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$per = getPeriod($period);

		//print_r(self::$periods);

		if ($period != '') {

			if (in_array($period, self::$periods)) {

				//Получаем данные

				$f = new Metrics();

				$iduser = ($params['user'] == '') ? current_userbylogin($params['login']) : $params['user'];

				$response['title'] = 'Статистика';

				$response['period']      = ($per[0] == $per[1]) ? format_date_rus($per[0]) : "с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
				$response['periodStart'] = $per[0];
				$response['periodEnd']   = $per[1];

				$params['period'] = $period;

				$details = [
					'clients' => $f -> calculateFact($iduser, 'clientsNew', $params),
					'deals' => [
						'new' => [
							'count' => $f -> calculateFact($iduser, 'dealsNewAll', $params),
							'sum'   => $f -> calculateFact($iduser, 'dealsSumAll', $params)
						],
						'close' => [
							'count' => $f -> calculateFact($iduser, 'dealsCloseAll', $params),
							'sum'   => $f -> calculateFact($iduser, 'dealsSumClose', $params)
						]
					],
					'invoices' => [
						'count' => $f -> calculateFact($iduser, 'invoicesNewCount', $params),
						'sum'   => $f -> calculateFact($iduser, 'invoicesNewSum', $params)
					],
					'payments' => [
						'count' => $f -> calculateFact($iduser, 'invoicesDoCount', $params),
						'sum'   => $f -> calculateFact($iduser, 'invoicesDoSum', $params)
					]
				];

				$response['details'] = $details;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Период не поддерживается";
				//$response['error']['info'] = "Возможные варианты: today, yestoday,week,month,quart,year";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - период для отчетности";

		}

		return $response;

	}

	/**
	 * Информация о новых клиентах
	 *
	 * @param string $period - период отчетности
	 * @param array  $params - массив параметров
	 *
	 * @return array
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - [result] = Ok
	 *         - [title]
	 *         - [count]
	 *         - [info]
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * @throws \Exception
	 * @example $NewClients = \Salesman\Statistic::clients($period,$params);
	 */
	public static function clients(string $period, array $params = []): array {

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$list = [];

		$iduser = ($params['user'] == '') ? current_userbylogin($params['login']) : $params['user'];

		if ($period != '') {

			if (in_array($period, self::$periods)) {

				$per = getPeriod($period);

				$sort = ($iduser > 0) ? $sqlname."clientcat.creator IN (".yimplode(",", get_people($iduser, 'yes')).") AND " : "";

				if ($params['personal'] == "yes") $sort = $sqlname."clientcat.creator = '$iduser' AND ";

				$q = "
					SELECT 
						".$sqlname."clientcat.creator as user,
						COUNT(".$sqlname."clientcat.clid) as count
					FROM ".$sqlname."clientcat
					WHERE 
						".$sqlname."clientcat.date_create BETWEEN '$per[0]' AND '$per[1]' AND 
						$sort
						".$sqlname."clientcat.identity = '$identity'
					GROUP BY ".$sqlname."clientcat.creator
					ORDER BY count DESC
				";

				$re = $db -> getAll($q);

				$kol = 0;

				foreach ($re as $da) {

					$list[] = [
						"user"  => current_user($da['user'], "yes"),
						"count" => (int)$da['count']
					];
					$kol    += (int)$da['count'];

				}

				$response['title']       = "Новые клиенты";
				$response['period']      = ($per[0] == $per[1]) ? format_date_rus($per[0]) : "с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
				$response['periodStart'] = $per[0];
				$response['periodEnd']   = $per[1];
				$response['count']       = $kol;

				if (empty($list)) {

					$response['details'] = "За этот период новых клиентов не найдено";

				}
				else {

					$details = [];

					foreach ($list as $i => $d) {

						$details[ $i ]['user']  = $d['user'];
						$details[ $i ]['count'] = $d['count'];
						$details[ $i ]['part']  = round(($d['count'] / $kol * 100), 2)."%";

					}

					$response['details'] = $details;

					if ($params['diagram'] == 'yes' && $kol > 1) {

						$response['url'] = self ::CreateDiagram($list, $period, "Новые клиенты");

					}

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Период не поддерживается";
				//$response['error']['info'] = "Возможные варианты: today, yestoday, week, month, quart, year";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - период для отчетности";

		}

		return $response;

	}

	/**
	 * Информация о новых сделках
	 *
	 * @param string $period - период отчетности
	 * @param array  $params - массив параметров
	 *
	 * @return array
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - [result] = Ok
	 *         - [title]
	 *         - [count]
	 *         - [info]
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * @throws \Exception
	 * @example $NewDeals = \Salesman\Statistic::dealsNew($period,$params);
	 */
	public static function dealsNew(string $period, array $params = []): array {

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$iduser = ($params['user'] == '') ? current_userbylogin($params['login']) : $params['user'];

		$list = [];

		if ($period != '') {

			if (in_array($period, self::$periods)) {

				$per = getPeriod($period);

				$sort = ((int)$params['user'] > 0) ? $sqlname."dogovor.autor IN (".yimplode(",", get_people($iduser, 'yes')).") AND " : "";

				if ($params['personal'] == "yes") $sort = $sqlname."dogovor.autor = '$iduser' AND";

				$q = "
					SELECT 
						".$sqlname."dogovor.autor as iduser,
						COUNT(".$sqlname."dogovor.did) as count,
						SUM(".$sqlname."dogovor.kol) as sum
					FROM ".$sqlname."dogovor
					WHERE 
						".$sqlname."dogovor.datum BETWEEN '$per[0]' AND '$per[1]' AND
						$sort 
						".$sqlname."dogovor.identity = '$identity'
					GROUP BY ".$sqlname."dogovor.autor
					ORDER BY count DESC
				";

				$re = $db -> getAll($q);

				$sum = 0;
				$kol = 0;

				foreach ($re as $da) {

					$list[] = [
						"user"  => current_user($da['iduser'], "yes"),
						"count" => (int)$da['count'],
						"sum"   => (float)$da['sum']
					];

					$kol += (int)$da['count'];
					$sum += (float)$da['sum'];

				}

				$response['title']       = "Новые сделки";
				$response['period']      = ($per[0] == $per[1]) ? format_date_rus($per[0]) : "с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
				$response['periodStart'] = $per[0];
				$response['periodEnd']   = $per[1];
				$response['count']       = $kol;

				if (empty($list)) {

					$response['details'] = "За этот период новых сделок не найдено";

				}
				else {

					$details = [];

					foreach ($list as $i => $d) {

						$details[ $i ]['user']  = $d['user'];
						$details[ $i ]['count'] = $d['count'];
						$details[ $i ]['summa'] = $d['sum'];

					}

					$response['details'] = $details;

					if ($params['diagram'] == 'yes' && $kol > 1) {

						$response['url'] = self ::CreateDiagram($list, $period, "Новые сделки");

					}

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Период не поддерживается";
				//$response['error']['info'] = "Возможные варианты: today, yestoday,week,month,quart,year";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - период для отчетности";

		}

		return $response;

	}

	/**
	 * Информация о закрытых сделках
	 *
	 * @param string $period - период отчетности
	 * @param array  $params - массив параметров
	 *
	 * @return array
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - [result] = Ok
	 *         - [title]
	 *         - [count]
	 *         - [info]
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * @throws \Exception
	 * @example $CloseDeals = \Salesman\Statistic::dealsClose($period,$params);
	 */
	public static function dealsClose(string $period, array $params = []): array {

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$list = [];

		$iduser = ($params['user'] == '') ? current_userbylogin($params['login']) : $params['user'];

		if ($period != '') {

			if (in_array($period, self::$periods)) {

				$per = getPeriod($period);

				$sort = ($params['user'] > 0) ? $sqlname."dogovor.iduser IN (".yimplode(",", get_people($iduser, 'yes')).") AND " : "";

				if ($params['personal'] == "yes") $sort = $sqlname."dogovor.iduser = '$iduser' AND";

				//print $sort;

				$q = "
					SELECT 
						".$sqlname."dogovor.iduser as user,
						COUNT(".$sqlname."dogovor.did) as count,
						SUM(".$sqlname."dogovor.kol) as sum
					FROM ".$sqlname."dogovor
					WHERE 
						".$sqlname."dogovor.close = 'yes' AND 
						".$sqlname."dogovor.datum_close BETWEEN '$per[0]' AND '$per[1]' AND
						$sort 
						".$sqlname."dogovor.identity = '$identity'
					GROUP BY ".$sqlname."dogovor.iduser
					ORDER BY count DESC
				";

				$re = $db -> getAll($q);

				$sum = 0;
				$kol = 0;

				foreach ($re as $da) {


					$list[] = [
						"user"  => current_user($da['user'], "yes"),
						"count" => (int)$da['count'],
						"sum"   => (float)$da['sum']
					];

					$kol += (int)$da['count'];
					$sum += (float)$da['sum'];

				}

				$response['title']       = "Закрытые сделки";
				$response['period']      = ($per[0] == $per[1]) ? format_date_rus($per[0]) : "с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
				$response['periodStart'] = $per[0];
				$response['periodEnd']   = $per[1];
				$response['count']       = $kol;

				if (empty($list)) {

					$response['details'] = "За этот период закрытых сделок не найдено";

				}
				else {

					$details = [];

					foreach ($list as $i => $d) {

						$details[ $i ]['user']  = $d['user'];
						$details[ $i ]['count'] = $d['count'];
						$details[ $i ]['summa'] = $d['sum'];

					}

					$response['details'] = $details;

					if ($params['diagram'] == 'yes' && $kol > 1) {

						$response['url'] = self ::CreateDiagram($list, $period, "Закрытые сделки");

					}

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Период не поддерживается";
				//$response['error']['info'] = "Возможные варианты: today, yestoday,week,month,quart,year";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - период для отчетности";

		}

		return $response;

	}

	/**
	 * Информация о новых выставленных счетах
	 *
	 * @param string $period - период отчетности
	 * @param array  $params - массив параметров
	 *
	 * @return array
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - [result] = Ok
	 *         - [title]
	 *         - [count]
	 *         - [info]
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * @throws \Exception
	 * @example $Invoices = \Salesman\Statistic::invoices($period,$params);
	 */
	public static function invoices(string $period, array $params = []): array {

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$list = [];

		$iduser = ($params['user'] == '') ? current_userbylogin($params['login']) : $params['user'];

		if ($period != '') {

			if (in_array($period, self::$periods)) {

				$per = getPeriod($period);

				$sort = ((int)$params['user'] > 0) ? $sqlname."credit.iduser IN (".yimplode(",", get_people($iduser, 'yes')).") AND " : "";

				if ($params['personal'] == "yes") $sort = $sqlname."credit.iduser = '$iduser' AND";

				$q = "
					SELECT 
						".$sqlname."credit.iduser as user,
						COUNT(".$sqlname."credit.clid) as count,
						SUM(".$sqlname."credit.summa_credit) as sum
					FROM ".$sqlname."credit
					WHERE 
						".$sqlname."credit.datum BETWEEN '$per[0]' AND '$per[1]' AND 
						-- ".$sqlname."credit.do != 'on' AND 
						$sort
						".$sqlname."credit.identity = '$identity'
					GROUP BY ".$sqlname."credit.iduser
					ORDER BY count DESC
				";

				$re = $db -> getAll($q);

				$sum = 0;
				$kol = 0;

				foreach ($re as $da) {

					$list[] = [
						"user"  => current_user($da['user'], "yes"),
						"count" => (int)$da['count'],
						"sum"   => (float)$da['sum']
					];

					$kol += (int)$da['count'];
					$sum += (float)$da['sum'];

				}

				$response['title']       = "Новые счета";
				$response['period']      = ($per[0] == $per[1]) ? format_date_rus($per[0]) : "с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
				$response['periodStart'] = $per[0];
				$response['periodEnd']   = $per[1];
				$response['count']       = $kol;

				if (empty($list)) {

					$response['details'] = "За этот период новых выставленных счетов не найдено";

				}
				else {

					$details = [];

					foreach ($list as $i => $d) {

						$details[ $i ]['user']  = $d['user'];
						$details[ $i ]['count'] = $d['count'];
						$details[ $i ]['summa'] = $d['sum'];

					}

					$response['details'] = $details;

					if ($params['diagram'] == 'yes' && $kol > 1) {

						$response['url'] = self ::CreateDiagram($list, $period, "Новые счета");

					}

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Период не поддерживается";
				//$response['error']['info'] = "Возможные варианты: today, yestoday,week,month,quart,year";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - период для отчетности";

		}

		return $response;

	}

	/**
	 * Информация об оплаченных счетах
	 *
	 * @param string $period - период отчетности
	 * @param array  $params - массив параметров
	 *
	 * @return array
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - [result] = Ok
	 *         - [title]
	 *         - [count]
	 *         - [info]
	 *p
	 * error result
	 *         - [result] = Error
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * @throws \Exception
	 * @example $Invoices = \Salesman\Statistic::payments($period,$params);
	 */
	public static function payments(string $period, array $params = []): array {

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$list = [];
		$response = [];
		$sort = '';

		$iduser = ($params['user'] == '') ? current_userbylogin($params['login']) : $params['user'];

		if ($period != '') {

			if (in_array($period, self::$periods)) {

				$per = getPeriod($period);

				if ($params['personal'] == "yes")
					$sort = ((int)$params['user'] > 0) ? $sqlname."credit.iduser = '".$params['user']."' AND" : "";

				if ($params['personal'] != "yes")
					$sort = $sqlname."credit.iduser IN (".yimplode(",", get_people($iduser, 'yes')).") AND ";

				$q = "
					SELECT 
						".$sqlname."credit.iduser as user,
						COUNT(".$sqlname."credit.clid) as count,
						SUM(".$sqlname."credit.summa_credit) as sum
					FROM ".$sqlname."credit
					WHERE 
						".$sqlname."credit.invoice_date BETWEEN '$per[0]' AND '$per[1]' AND 
						".$sqlname."credit.do = 'on' AND 
						$sort
						".$sqlname."credit.identity = '$identity'
					GROUP BY ".$sqlname."credit.iduser
					ORDER BY count DESC
				";

				//file_put_contents($rootpath."/cash/static.log", $q);

				$re = $db -> getAll($q);

				$sum = 0;
				$kol = 0;

				foreach ($re as $da) {

					$list[] = [
						"user"  => current_user($da['user'], "yes"),
						"count" => (int)$da['count'],
						"sum"   => (float)$da['sum']
					];

					$kol += (int)$da['count'];
					$sum += (float)$da['sum'];

				}

				$response['title']       = "Оплаченные счета";
				$response['period']      = ($per[0] == $per[1]) ? format_date_rus($per[0]) : "с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
				$response['periodStart'] = $per[0];
				$response['periodEnd']   = $per[1];
				$response['count']       = $kol;

				if (empty($list)) {

					$response['details'] = "За этот период оплаченных счетов не найдено";

				}
				else {

					$details = [];

					foreach ($list as $i => $d) {

						$details[ $i ]['user']  = $d['user'];
						$details[ $i ]['count'] = $d['count'];
						$details[ $i ]['summa'] = $d['sum'];

					}

					$response['details'] = $details;

					/*if ($params['diagram'] == 'yes' && $kol > 1) {

						$response['url'] = Statistic ::CreateDiagram($list, $period, "Оплаченные счета");

					}*/

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Период не поддерживается";
				//$response['error']['info'] = "Возможные варианты: today, yestoday,week,month,quart,year";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - период для отчетности";

		}

		return $response;

	}

	/**
	 * Создание диаграммы
	 *
	 * @param array  $data   - массив данных
	 * @param int    $period - период отчетности
	 * @param string $method - показатель
	 *
	 * @return string
	 * Возвращает ссылку на изображение
	 *
	 * @throws \Exception
	 */
	public static function CreateDiagram(array $data, int $period, string $method): string {

		$identity = $GLOBALS['identity'];

		$rootpath = realpath(__DIR__.'/../../');

		require_once $rootpath."/inc/func.php";

		require_once $rootpath."/vendor/pChart/pChart/pData.class";
		require_once $rootpath."/vendor/pChart/pChart/pChart.class";

		$img = $identity."-".time().".jpg";

		//папка для хранения диаграмм
		$path = $rootpath."/files/statistic";

		//если не существует, то создаем
		createDir($path);

		//очищаем старое
		$files = scandir($path, 1);
		foreach ($files as $file) {

			if (strpos($file, 'jpg') !== false) {

				//дата создания файла
				$date = unix_to_datetime(filemtime($path.'/'.$file));

				//если создан больше 1 часа назад, то удаляем
				if (diffDateTimeSeq($date) > 3600) unlink($path.'/'.$file);

			}

		}

		/**
		 * Генерируем диаграмму
		 */

		$DataSet = new pData;

		$cur_date = date('d.m.Y H:i:s');

		$per = getPeriod($period);

		if ($per[0] != $per[1]) {
			$pers = "период с ".format_date_rus($per[0])." по ".format_date_rus($per[1]);
		}
		else $pers = $per[0];

		// Initialise the graph
		$Test = new pChart(850, 390);

		foreach ($data as $i => $str) {

			$DataSet -> AddPoint($str['count'], $str['user']);
			$Test -> setColorPalette($i, rand(0, 255), rand(0, 255), rand(0, 255));

		}

		$DataSet -> AddAllSeries();

		$Test -> setFontProperties($rootpath."/vendor/pChart/Fonts/tahoma.ttf", 12);

		$DataSet -> SetYAxisName("Количество");

		$DataSet -> SetXAxisName(str_repeat(" ", 30)."Данные актуальны на $cur_date");

		$Test -> setFontProperties($rootpath."/vendor/pChart/Fonts/tahoma.ttf", 8);

		// Границы графика($X1,$Y1,$X2,$Y2)
		$Test -> setGraphArea(50, 30, 680, 310);

		//Фон изображения
		$Test -> drawFilledRoundedRectangle(7, 7, 840, 330, 5, 176, 196, 222);

		//Рамка изображения
		$Test -> drawRoundedRectangle(5, 5, 840, 330, 5, 0, 0, 0);

		//Фон за графиком

		//params(R,G,B, штриховка)
		$Test -> drawGraphArea(240, 240, 240);
		$Test -> drawScale($DataSet -> GetData(), $DataSet -> GetDataDescription(), SCALE_START0, 150, 150, 150, true, 0, 2, true);
		//Параметры: (ширина линии, мозайка фона, R, G, B, прозрачность)
		$Test -> drawGrid(4, false, 230, 230, 230, 255);

		// Draw the bar graph
		$Test -> drawBarGraph($DataSet -> GetData(), $DataSet -> GetDataDescription(), false, 100);

		// Finish the graph
		$Test -> setFontProperties($rootpath."/vendor/pChart/Fonts/tahoma.ttf", 8);
		$Test -> drawLegend(690, 10, $DataSet -> GetDataDescription(), 240, 240, 240);
		$Test -> setFontProperties($rootpath."/vendor/pChart/Fonts/tahoma.ttf", 10);
		$Test -> drawTitle(70, 22, "$method за $pers", 50, 50, 50, 700);

		$Test -> Render($path."/diagram-$img");

		//unlink("../../images/statistic/NewClients-$img");

		unset($Test);

		return "files/statistic/diagram-$img";

	}

}