<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

namespace Salesman;

use Generator;
use PHPMailer\PHPMailer\Exception;

/**
 * Класс для работы с объектом Бюджет
 *
 * Class Budget
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example:
 * ```php
 * $Budget  = new Salesman\Budget();
 * $result = $Budget -> add($params);
 * $id = $result['data'];
 * ```
 *
 */
class Budget {

	//public $response = [];
	//var $isdouble = [];

	/**
	 * Добавление/изменение расхода/дохода
	 *
	 * @param int $id - идентификатор записи расхода/дохода
	 * @param array $params - массив с параметрами
	 *                        - **title** - название
	 *                        - **cat** - категория расхода/дохода
	 *                        - **des** - описание
	 *                        - **summa** - сумма расхода/дохода
	 *                        - **do** - пизнак проведения
	 *                        - **datum** - дата изменения
	 *                        - **rs** - номер р/с
	 *                        - **fid** - id файла
	 *                        - **did** - id сделки
	 *
	 * @return array
	 * good result
	 *         - result = Успешно добавлен/изменен
	 *         - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Расход/доход с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи расхода/дохода
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::edit($id,$params);
	 * ```
	 *
	 * @throws \Exception
	 */
	public static function edit(int $id, array $params = []): array {

		global $hooks;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$iduser1  = $GLOBALS['iduser1'];
		$db       = $GLOBALS['db'];

		$post = $params;

		$fid = $params['fid'] ?? [];

		$params = $id > 0 ? $hooks -> apply_filters("budjet_editfilter", $params) : $hooks -> apply_filters("budjet_addfilter", $params);

		$oldParams = [];

		$recal  = isset($params['recal']) ? (int)$params['recal'] : 1;

		$params['bmon']  = !empty($params['date_plan']) ? getMonth($params['date_plan']) : date('m');
		$params['byear'] = !empty($params['date_plan']) ? get_year($params['date_plan']) : date('Y');

		if ($id > 0) {

			$result = $db -> getRow("SELECT * FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

			$xf = yexplode(";", $result['fid']);
			foreach ($xf as $item) {
				$fid[] = $item;
			}

			//print_r($fid);

			// Получим старые параметры записи расхода/дохода
			$oldParams = [
				"cat"          => (int)$result['cat'],
				"title"        => $result['title'],
				"des"          => $result['des'],
				"year"         => (int)$result['year'],
				"mon"          => (int)$result['mon'],
				"summa"        => (float)$result['summa'],
				"datum"        => $result['datum'],
				"do"           => $result['do'],
				"rs"           => (int)$result['rs'],
				"did"          => (int)$result['did'],
				"conid"        => (int)$result['conid'],
				"partid"       => (int)$result['partid'],
				"iduser"       => (int)$result['iduser'],
				"date_plan"    => $result['date_plan'],
				"invoice"      => $result['invoice'],
				"invoice_date" => $result['invoice_date'],
			];

		}

		//$other = $db -> getOne("SELECT other FROM {$sqlname}settings WHERE id = '$identity'");
		//$other = explode(";", $other);

		$budget['summa']        = /*(float)pre_format($oldParams['summa']) > 0 ? pre_format($oldParams['summa']) : */ pre_format($params['summa']);
		$budget['title']        = $params['title'] == '' ? $oldParams['title'] : $params['title'];
		$budget['des']          = $params['des'] == '' ? $oldParams['des'] : $params['des'];
		$budget['date_plan']    = empty($params['date_plan']) ? $oldParams['date_plan'] : $params['date_plan'];
		$budget['invoice']      = empty($params['invoice']) ? $oldParams['invoice'] : $params['invoice'];
		$budget['invoice_date'] = empty($params['invoice_date']) ? $oldParams['invoice_date'] : $params['invoice_date'];
		$budget['cat']          = empty($params['cat']) ? (int)$oldParams['cat'] : (int)$params['cat'];
		$budget['mon']          = empty($params['bmon']) ? (int)$oldParams['mon'] : (int)$params['bmon'];
		$budget['year']         = empty($params['byear']) ? (int)$oldParams['year'] : (int)$params['byear'];
		$budget['do']           = empty($params['do']) ? $oldParams['do'] : $params['do'];
		$budget['rs']           = empty($params['rs']) ? $oldParams['rs'] : $params['rs'];
		$budget['did']          = empty($params['did']) ? 0 : (int)$params['did'];
		$budget['conid']        = empty($params['conid']) ? 0 : (int)$params['conid'];
		$budget['partid']       = empty($params['partid']) ? 0 : (int)$params['partid'];
		$budget['identity']     = $identity;
		//$budget['iduser']   = empty($params['iduser']) ? (int)$oldParams['iduser'] : (int)$params['iduser'];

		$addNewRashodDelta = $params['addNewRashodDelta'];
		$forsed            = $params['forsed'];

		$isdo = $budget['do'];
		unset($budget['do']);

		$message   = $err = [];
		$bidSecond = 0;

		$summaDelta = 0;

		//сумма, которую изначально планировали провести
		$summaPlan = $oldParams['summa'];

		// Проверяем поставщиком и партнеров
		$contragent    = (int)$params['clid'];
		$dogproviderid = (int)$params['dogproviderid'];//id связанного расхода в dogprovider
		$ctip          = getClientData($contragent, 'type');

		if ($ctip == 'partner') {
			$budget['partid'] = $contragent;
		}
		elseif ($ctip == 'contractor') {
			$budget['conid'] = $contragent;
		}

		$datum                     = $params['datum'] == '' ? current_datum() : $params['datum'];
		$budget['datum']           = $datum." ".date('H').":".date('i').":".date('s');
		$budget['invoice_paydate'] = $params['invoice_paydate'] ?? NULL;

		//Проверяем наличие тематической папки
		$folder = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}file_cat WHERE title = 'Бюджет' and identity = '$identity'");
		if ($folder == 0) {

			$db -> query("INSERT INTO {$sqlname}file_cat SET ?u", [
				"title"    => 'Бюджет',
				"shared"   => 'yes',
				"identity" => $identity
			]);
			$folder = $db -> insertId();

		}

		//Загружаем файлы в хранилище
		$upload = Upload ::upload();

		$response = array_merge($message, $upload['message']);

		foreach ($upload['data'] as $file) {

			$arg = [
				'ftitle'   => $file['title'],
				'fname'    => $file['name'],
				'ftype'    => $file['type'],
				'iduser'   => (int)$iduser1,
				'clid'     => (int)$budget['conid'],
				'did'      => (int)$budget['did'],
				'folder'   => $folder,
				'identity' => $identity
			];

			$fid[] = Upload ::edit(0, $arg);

		}

		//print_r($fid);

		//массив файлов
		$fida = implode(";", $fid);

		//конец - Загрузка файлов в хранилище

		// Обновление записи
		if ($id > 0) {

			$budget['fid']    = $fida;
			$budget['iduser'] = (int)$iduser1;

			// Проверка на существование в БД
			$bid = (int)$db -> getOne("SELECT count(*) FROM {$sqlname}budjet WHERE id='$id' and identity = '$identity'");

			//если это существующий расход
			if ($bid > 0) {

				//обновляем расход
				$db -> query("UPDATE {$sqlname}budjet SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean($budget));
				$budget['bid'] = $id;

				// лог статуса
				self ::logStatus($id, 'edit', sprintf('Изменен расход на сумму %s. Предыдущая сумма: %s', $budget['summa'], $summaPlan));

				// уведомление
				self ::sendNotify("budjet.edit", [
					"id"      => $id,
					"title"   => $budget['title'],
					"content" => $budget['des'],
					"iduser"  => $iduser1
				]);

				if ($hooks) {
					$hooks -> do_action("budjet_edit", $post, $budget);
				}

				//если до этого небыло проведения и сейчас надо провести
				if ($oldParams['do'] != 'on' && $isdo == 'on') {

					$do = self ::doit($id, $forsed);

					if ($do['error']['code'] == '406') {

						$response['result'] = 'Запись о расходе изменена.<br> Расход не проведен, так как недостаточно средств на счете';

					}
					else {

						$response['result'] = 'Успешно обновлено';

					}

				}

				//создаем расход на основе текущего на разницу
				if ($budget['summa'] < $summaPlan && $addNewRashodDelta == 'yes' && $isdo == 'on') {

					$summaDelta = $summaPlan - $budget['summa'];

					$argSecond = [
						"cat"          => (int)$budget['cat'],
						"title"        => $budget['title'],
						"des"          => $budget['des'],
						"year"         => (int)$budget['year'],
						"mon"          => (int)$budget['mon'],
						"summa"        => $summaDelta,
						"datum"        => $budget['datum'],
						"do"           => $budget['do'],
						"rs"           => (int)$budget['rs'],
						"did"          => (int)$budget['did'],
						"conid"        => (int)$budget['conid'],
						"partid"       => (int)$budget['partid'],
						"iduser"       => (int)$budget['iduser'],
						"date_plan"    => $budget['date_plan'],
						"invoice"      => $budget['invoice'],
						"invoice_date" => $budget['invoice_date'],
					];

					$db -> query("INSERT INTO {$sqlname}budjet SET ?u", arrayNullClean($argSecond));

					//получим id расхода, созданного при проведении меньшей суммы
					$bidSecond = $db -> insertId();

					// лог статуса
					self ::logStatus($bidSecond, 'corrected', sprintf('Добавлен корректирующий расход на сумму %s после проведения', $summaDelta));

				}

				$arg = [
					'conid'    => (int)$budget['conid'],
					'partid'   => (int)$budget['partid'],
					'did'      => (int)$budget['did'],
					'bid'      => $id,
					'summa'    => (float)$budget['summa'],
					"recal"    => $recal,
					'identity' => $identity
				];

				//свяжем с таблицей dogsprovider
				if ($dogproviderid > 0 && $contragent > 0) {

					//if ($arg['conid'] > 0) $s = "and conid = '".$arg['conid']."'";
					//elseif ($arg['partid'] > 0) $s = "and partid = '".$arg['partid']."'";

					//обновим старый расход в части суммы и привязки к бюджету
					//$db -> query("UPDATE {$sqlname}dogprovider SET bid = '$id', summa = '".$arg['summa']."' WHERE id = '".$dogproviderid."' AND identity = '".$identity."'");
					$db -> query("UPDATE {$sqlname}dogprovider SET ?u WHERE id = '".$dogproviderid."' AND identity = '$identity'", $arg);

					//если создан доп.расход, то добавим его в расходы по сделке
					if ($bidSecond > 0) {

						$arg['summa'] = $summaDelta;
						$arg['bid']   = $bidSecond;
						//добавим расход
						$db -> query("INSERT INTO {$sqlname}dogprovider SET ?u", arrayNullClean($arg));

					}

				}
				elseif ($dogproviderid == 0 && $contragent > 0) {

					$db -> query("INSERT INTO {$sqlname}dogprovider SET ?u", arrayNullClean($arg));

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '404';
				$response['error']['text'] = "Записи с таким id не существует!";

			}

			$response['data'] = $id;

		}

		// Добавление новой записи
		else {

			$budget['iduser'] = (int)$iduser1;
			$budget['fid']    = $fida;

			if ($budget['title'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Отсутствуют параметры - Название расхода/дохода";

			}
			else {

				$db -> query("INSERT INTO {$sqlname}budjet SET ?u", arrayNullClean($budget));
				$id = $db -> insertId();

				$budget['bid'] = $id;

				// лог статуса
				self ::logStatus($id, 'new', sprintf('Добавлен расход на сумму %s.', $budget['summa']));

				// отправка уведомления
				self ::sendNotify("budjet.new", [
					"id"      => $id,
					"title"   => $budget['title'],
					"content" => $budget['des'],
					"iduser"  => $iduser1
				]);

				if ($hooks) {
					$hooks -> do_action("budjet_add", $post, $budget);
				}

				//свяжем с таблицей dogsprovider
				if ($dogproviderid == 0 && $contragent > 0) {

					$arg = [
						'conid'    => (int)$budget['conid'],
						'partid'   => (int)$budget['partid'],
						'did'      => (int)$budget['did'],
						'bid'      => (int)$id,
						'summa'    => (float)$budget['summa'],
						"recal"    => $recal,
						'identity' => $identity
					];
					$db -> query("INSERT INTO {$sqlname}dogprovider SET ?u", arrayNullClean($arg));

				}
				if ($dogproviderid > 0) {
					$db -> query("UPDATE {$sqlname}dogprovider SET bid = '$id' WHERE id = '$dogproviderid' and identity = '$identity'");
				}

				//если есть отметка о проведении
				if ($isdo == 'on') {

					$do = self ::doit((int)$id, $forsed);

					if ($do['error']['code'] == '406') {
						$response['result'] = 'Запись расхода добавлена.<br> Расход не проведен, так как недостаточно средств на счете';
					}
					else {
						$response['result'] = 'Успешно добавлен';
					}

				}
				else {
					$response['result'] = 'Успешно';
				}

				$response['data'] = $id;

			}

		}

		return $response;

	}

	/**
	 * Удаление расхода/дохода
	 *
	 * @param int $id - идентификатор записи расхода/дохода
	 *
	 * @return array
	 * good result
	 *         - result = Успешно удален
	 *         - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Расход/доход с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи расхода/дохода
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::delete($id);
	 * ```
	 */
	public static function delete(int $id): array {

		global $hooks, $settingsMore;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		//require_once "Upload.php";

		//$files = yexplode( ';', $db -> getOne( "SELECT fid FROM {$sqlname}budjet WHERE id='$id' and identity = '$identity'" ) );

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

			//если это существующий расход
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Запись бюджета с указанным id не найдена в пределах аккаунта";

			}
			else {

				$do = $db -> getOne("SELECT `do` FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

				// удаление статусов
				self::logStatusDelete($id);

				if ($do != 'on') {

					// todo: так и не понял - нахера тут обход массива???
					//for ( $i = 0; $i < count( $files ); $i++ ) {

					//удалим расход
					$dp = (int)$db -> getOne("SELECT id FROM {$sqlname}dogprovider WHERE bid='$id' and identity = '$identity'");
					if ($dp > 0) {

						if ($settingsMore['budjetProviderPlus'] == 'yes') {
							$db -> query("DELETE FROM {$sqlname}dogprovider WHERE id = '$dp'");
						}
						else{
							$db -> query("UPDATE {$sqlname}dogprovider SET bid = '0' WHERE id='$dp' AND identity = '$identity'");
						}

					}

					$fids = (string)$db -> getOne("SELECT fid FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

					//удалим прикрепленные файлы
					$fid = yexplode(";", $fids);

					foreach ($fid as $file) {

						//удалим запись о файле
						Upload ::delete($file);

					}

					//}

					// Удалим запись расхода/дохода из табицы
					$db -> query("DELETE FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

					$response['result'] = 'Удалено';
					$response['data']   = $id;

					if ($hooks) {
						$hooks -> do_action("budjet_delete", $id);
					}

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '406';
					$response['error']['text'] = "Невозможно удалить расход/доход, т.к. он был проведен. Попробуйте отменить проведение";
				}

			}

		}
		else {
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id расхода/дохода";

		}

		return $response;
	}

	/**
	 * Проведение платежа
	 *
	 * @param int $id - идентификатор записи расхода/дохода
	 * @param bool $forsed - принудительное проведение ( игнорируя остаток )
	 *
	 * @return array
	 *                    good result
	 *                    - result = Успешно проведен
	 *                    - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *          - 403 - Расход/доход с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи расхода/дохода
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::doit($id);
	 * ```
	 * @throws \Exception
	 */
	public static function doit(int $id, bool $forsed = NULL): array {

		global $hooks;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$fpath    = $GLOBALS['fpath'];

		//$other = $db -> getOne( "SELECT other FROM {$sqlname}settings WHERE id = '$identity'" );
		//$other = explode( ";", $other );

		$otherSettings = json_decode(file_get_contents($rootpath."/cash/".$fpath."otherSettings.json"), true);

		//print_r($other);

		if ($id > 0) {

			$result  = $db -> getRow("SELECT * FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");
			$summa   = $result['summa'];
			$rs      = (int)$result['rs'];
			$cat     = (int)$result['cat'];
			$do      = $result['do'];
			$title   = $result['title'];
			$content = $result['des'];
			$bid     = (int)$result['id'];

			if ($bid > 0) {

				if ($do != 'on') {

					//поймем - расход это или поступление
					$tip      = $db -> getOne("SELECT tip FROM {$sqlname}budjet_cat WHERE id = '$cat' and identity = '$identity'");
					$operacia = ( $tip == 'rashod' ) ? 'minus' : 'plus';


					// Проверяем наличие средств на счете
					$ostatok = (float)$db -> getOne("SELECT ostatok FROM {$sqlname}mycomps_recv WHERE id = '$rs' and identity = '$identity'");

					// Наличие средств актуально только для расходов
					if (( $ostatok >= $summa && $tip == 'rashod' ) || $tip != 'rashod' || $forsed) {

						//сделаем вычет со счета
						self ::rsadd($rs, (float)$summa, $operacia);

						$arg['do'] = 'on';

						//если включена принудительная коррекция даты проведения
						if ($otherSettings['budjetDayIsNow'] == 'today') {
							$arg['datum'] = current_datumtime();
						}

						//print_r($arg);

						$db -> query("UPDATE {$sqlname}budjet SET ?u WHERE id = '$id' and identity = '$identity'", $arg);

						// лог статуса
						self ::logStatus($bid, 'complete', sprintf('Проведен расход на сумму %s', $summa));

						$response['result'] = 'Успешно проведен';
						$response['data']   = $id;

						self ::sendNotify("budjet.doit", [
							"id"      => $id,
							"title"   => $title,
							"content" => $des,
							"iduser"  => $iduser1
						]);

						if ($hooks) {
							$hooks -> do_action("budjet_do", $id);
						}

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = '406';
						$response['error']['text'] = "Расход не проведен - недостаточно средств на счете. Выберите другой расч. счет";

					}

				}
				else {

					$response['result'] = 'Отмена - расход уже проведен';
					$response['data']   = $id;

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Запись бюджета с указанным id не найдена в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id расхода/дохода";

		}

		return $response;

	}

	/**
	 * Отмена проведения платежа
	 *
	 * @param int $id - идентификатор записи расхода/дохода
	 *
	 * @return array
	 * good result
	 *         - result = Платеж успешно отменен
	 *         - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Платеж с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи платежа
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::undoit($id);
	 * ```
	 */
	public static function undoit(int $id): array {

		global $hooks;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$result = $db -> getRow("SELECT * FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");
			$summa  = $result["summa"];
			$rs     = (int)$result["rs"];
			$cat    = (int)$result["cat"];
			$do     = $result["do"];

			if ($rs > 0) {

				if ($do == 'on') {

					//поймем - расход это или поступление
					$tip = (string)$db -> getOne("SELECT tip FROM {$sqlname}budjet_cat WHERE id = '$cat' and identity = '$identity'");

					$operacia = ( $tip == 'rashod' ) ? 'plus' : 'minus';

					//сделаем вычет со счета
					self ::rsadd($rs, (float)$summa, $operacia);

					$db -> query("UPDATE {$sqlname}budjet SET do = '' WHERE id = '$id' and identity = '$identity'");

					// лог статуса
					self ::logStatus($bid, 'correct', sprintf('Отменено проведение расхода на сумму %s', $summa));

					$response['result'] = 'Проведение отменено';
					$response['data']   = $id;

					if ($hooks) {
						$hooks -> do_action("budjet_undo", $id);
					}


				}
				else {

					$response['result'] = 'Отмена - расход не проведен';
					$response['data']   = $id;

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Бюджет с указанным id не найден в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id расхода/дохода";

		}

		return $response;

	}

	/**
	 * Получение информации по расходу/доходу
	 *
	 * @param int $id - идентификатор расхода/дохода
	 *
	 * @return array
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Расход/доход с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи расхода/дохода
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::info($id);
	 * ```
	 */
	public static function info(int $id): array {

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT count(*) FROM {$sqlname}budjet WHERE id='$id' and identity = '$identity'");

			if ($count > 0) {

				$budget = $db -> getRow("SELECT * FROM {$sqlname}budjet WHERE id='$id' and identity = '$identity'");
				$razdel = $db -> getOne( "SELECT title FROM {$sqlname}budjet_cat WHERE id = '".$budget['cat']."' and identity = '$identity'" );
				$bank   = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id = '".$budget['rs']."' and identity = '$identity'" );
				$bank2  = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id = '".$budget['rs2']."' and identity = '$identity'" );

				$files = [];
				$xfiles = yexplode( ";", $budget['fid'] );
				foreach ( $xfiles as $file ) {

					$fi = $db -> getRow( "SELECT * FROM {$sqlname}file WHERE fid = '$file' and identity = '$identity'" );
					if ( $fi['ftitle'] != '' ) {

						$files[] = [
							"id" => $file,
							"name" => $fi['ftitle'],
							"file" => $fi['fname'],
							"icon" => get_icon2( $fi['ftitle'] )
						];

					}

				}

				$response['budget'] = [
					"id"              => (int)$budget['id'],
					"cat"             => (int)$budget['cat'],
					"razdel"          => $razdel,
					"title"           => $budget['title'],
					"des"             => $budget['des'],
					"year"            => (int)$budget['year'],
					"mon"             => (int)$budget['mon'],
					"summa"           => $budget['summa'],
					"datum"           => $budget['datum'],
					"date_plan"       => $budget['date_plan'],
					"invoice"         => $budget['invoice'],
					"invoice_date"    => $budget['invoice_date'],
					"invoice_paydate" => $budget['invoice_paydate'],
					"iduser"          => (int)$budget['iduser'],
					"do"              => $budget['do'],
					"rs"              => (int)$budget['rs'],
					"bank"            => $bank,
					"rs2"             => (int)$budget['rs2'],
					"bank2"           => $bank2,
					"fid"             => $budget['fid'],
					"files"           => $files,
					"did"             => (int)$budget['did'],
					"conid"           => (int)$budget['conid'],
					"partid"          => (int)$budget['partid'],
					"changelog"       => self ::logStatusGet((int)$budget['id'])
				];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Бюджет с указанным id не найден в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id расхода/дохода";

		}

		return $response;

	}

	/**
	 * Возвращет массив имен полей таблицы Бюджет
	 *
	 * @return array
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::fields();
	 * ```
	 */
	public static function fields(): array {

		$response['fields'] = [

			"id"     => 'Идентификатор записи расхода/дохода',
			"cat"    => "Категория расхода дохода из таблицы budjet_cat",
			"title"  => "Название расхода/дохода",
			"des"    => "Описание",
			"year"   => "Год",
			"mon"    => "Месяц",
			"summa"  => "Сумма",
			"datum"  => "Дата изменения записи",
			"iduser" => "id пользователя",
			"do"     => "Отметка о проведении",
			"rs"     => "id расч. счета",
			"rs2"    => "id расч. счета для перемещения средств между счетами",
			"fid"    => "id файла",
			"did"    => "id сделки",
			"conid"  => "clid для поставщиков",
			"partid" => "clid для партнеров"
		];

		return $response;

	}

	/**
	 * Добавление/изменение категории расхода/дохода
	 *
	 * @param int $catid - идентификатор категории расхода/дохода
	 * @param array $params - массив с параметрами
	 *                        - **subid** - id основной записи категории
	 *                        - **title** - название
	 *                        - **tip** - расход или доход
	 *
	 * @return array
	 * good result
	 *         - result = Успешно добавлено/изменено
	 *         - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Категория с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи категории
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::editCategory($catid,$params);
	 * ```
	 */
	public static function editCategory(int $catid = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		// Получаем старые параметры записи
		$category = $db -> getrow("SELECT * FROM {$sqlname}budjet_cat WHERE id = '$catid' and identity = '$identity'");

		$oldParams = [
			"subid"      => (int)$category['subid'],
			"title"      => $category['title'],
			"tip"        => $category['tip'],
			"clientpath" => (int)$category['clientpath'],
		];

		$cat['title']      = empty($params['title']) ? $oldParams['title'] : $params['title'];
		$cat['subid']      = empty($params['subid']) ? $oldParams['subid'] : $params['subid'];
		$cat['tip']        = empty($params['tip']) ? $oldParams['tip'] : $params['tip'];
		$cat['clientpath'] = empty($params["clientpath"]) ? $oldParams['clientpath'] : $params['clientpath'];
		$cat['identity']   = $identity;

		// Если запись найдена
		if ($catid > 0) {

			$count = (int)$db -> getOne("SELECT count(*) FROM {$sqlname}budjet_cat WHERE id='".$catid."' and identity = '$identity'");

			//если это существующий расход/доход
			if ($count > 0) {

				$db -> query("UPDATE {$sqlname}budjet_cat SET ?u WHERE id = '$catid' and identity = '$identity'", ArrayNullClean($cat));

				$response['result'] = 'Категория изменена';
				$response['data']   = $catid;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Категория расхода/дохода с указанным id не найдена в пределах аккаунта";

			}

		}
		// Создаем новую категорию расхода/прихода
		// Проверяем наличие названия
		elseif ($params['title'] != '') {

			$cat['tip'] = $cat['tip'] == '' ? "rashod" : $params['tip'];

			$db -> query("insert into {$sqlname}budjet_cat SET ?u", arrayNullClean($cat));
			$catid = $db -> insertId();

			$response['result'] = 'Категория добавлена';
			$response['data']   = $catid;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - название категории расхода/дохода";

		}

		return $response;

	}

	/**
	 * Удаление категории расхода/дохода
	 *
	 * @param int $catid - идентификатор записи категории расхода/дохода
	 * @param int $newcat - новая категория для переноса
	 *
	 * @return array
	 *                       good result
	 *                       - result = Успешно удалено
	 *                       - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Категория с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи категории
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::deleteCategory($catid);
	 * ```
	 */
	public static function deleteCategory(int $catid = 0, int $newcat = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($catid > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}budjet_cat WHERE id='$catid' and identity = '$identity'");
			$subid = (int)$db -> getOne("SELECT subid FROM {$sqlname}budjet_cat WHERE id='$catid' and identity = '$identity'");

			// Проверка на существование записи
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Расход/доход с указанным id не найден в пределах аккаунта";

			}
			//если это существующий расход/доход
			else {

				$doubles = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}budjet_cat WHERE subid='$catid' and identity = '$identity'");

				if ($doubles == 0 || ( $doubles > 0 && $newcat > 0 )) {

					// переносим расходы в новую категорию
					if ($subid == 0) {
						$db -> query("UPDATE {$sqlname}budjet_cat SET ?u WHERE subid = '$catid' and identity = '$identity'", ["subid" => $newcat]);
					}
					else {
						$db -> query("UPDATE {$sqlname}budjet SET ?u WHERE cat = '$catid' and identity = '$identity'", ["cat" => $newcat]);
					}

					// удаляем старую категорию
					$db -> query("DELETE FROM {$sqlname}budjet_cat WHERE id = '$catid' and identity = '$identity'");

					$response['result'] = 'Категория удалена';
					$response['data']   = $catid;

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '408';
					$response['error']['text'] = "Удаление категории невозможно. Имеются подразделы и не указан раздел для переноса.";

				}
			}
		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id категории расхода/дохода";


		}

		return $response;

	}

	/**
	 * Перемещение средств между счетами
	 *
	 * @param array $params - массив с параметрами
	 *                      - **summa** - сумма перевода
	 *                      - **title** - название перевода
	 *                      - **des** - описание
	 *                      - **bmon** - месяц
	 *                      - **byear** - год
	 *                      - **rs** - счет, с которого переводим
	 *                      - **rs_move** - счет, на который переводим
	 *
	 * @return array
	 * good result
	 *         - result = Средства успешно перемещены
	 *         - data = id
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::move($params);
	 * ```
	 */
	public static function move(array $params): array {

		global $hooks;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$post = $params;

		$params = $hooks -> apply_filters("budjet_movefilter", $params);

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser1  = $GLOBALS['iduser1'];

		$summa   = pre_format($params['summa']);
		$datum   = current_datumtime();
		$title   = ( !isset($params['title']) || $params['title'] == '' ) ? 'Перемещение '.$datum : $params['title'];
		$bmon    = ( !isset($params['bmon']) || $params['bmon'] == '' ) ? getMonth($datum) : $params['bmon'];
		$byear   = ( !isset($params['byear']) || $params['byear'] == '' ) ? get_year($datum) : $params['byear'];
		$do      = 'on';
		$rs      = (int)$params['rs'];
		$rs_move = (int)$params['rs_move'];
		$des     = ( !isset($params['des']) || $params['des'] == '' ) ? 'Перемещение средств '.$datum.' м/у счетами '.$rs.' и '.$rs_move : $params['des'];

		if (!isset($params['rs'], $params['rs_move']) || $params['rs'] == '' || $params['rs_move'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id р/счетов для перемещения средств";

			goto ext;

		}

		if (!isset($params['summa']) || $params['summa'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствует параметр - сумма перевода";

			goto ext;

		}


		$message = [];
		// Проверяем наличие средств на счете
		$ostatok = (float)$db -> getOne("SELECT ostatok FROM {$sqlname}mycomps_recv WHERE id = '$rs' and identity = '$identity'");

		if ($ostatok >= $summa) {

			//Проверяем наличие тематической папки
			$folder = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}file_cat WHERE title = 'Бюджет' and identity = '$identity'");
			if ($folder == 0) {

				$db -> query("INSERT INTO {$sqlname}file_cat SET ?u", [
					"title"    => 'Бюджет',
					"shared"   => 'yes',
					"identity" => $identity
				]);
				$folder = $db -> insertId();

			}

			//Загружаем файлы в хранилище
			$upload = Upload ::upload();

			$response = array_merge($message, $upload['message']);

			$fid = [];

			foreach ($upload['data'] as $file) {

				$arg = [
					'ftitle'   => $file['title'],
					'fname'    => $file['name'],
					'ftype'    => $file['type'],
					'iduser'   => $iduser1,
					'folder'   => $folder,
					'identity' => $identity
				];

				$fid[] = Upload ::edit(0, $arg);

			}

			//массив файлов
			$fida = implode(";", $fid);

			//конец - Загрузка файлов в хранилище

			$money = [
				'datum'    => current_datumtime(),
				'title'    => $title,
				'des'      => $des,
				'year'     => (int)$byear,
				'mon'      => (int)$bmon,
				'summa'    => $summa,
				'iduser'   => (int)$iduser1,
				'do'       => $do,
				'rs'       => $rs,
				'rs2'      => $rs_move,
				'fid'      => $fida,
				'identity' => $identity
			];

			$db -> query("INSERT INTO {$sqlname}budjet SET ?u", ArrayNullClean($money));

			$id = $db -> insertId();

			$money['bid'] = $id;

			if ($hooks) {
				$hooks -> do_action("deal_edit", $post, $money);
			}

			$operacia = 'move';

			//сделаем вычет со счета
			self ::rsadd($rs, (float)$summa, $operacia, $rs_move);

			$id                 = $id == 0 ? 'Успех' : $id;
			$response['result'] = "Средства успешно перемещены";
			$response['data']   = $id;
		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Средства не перемещены: Недостаточно средств на счете. Выберите другой расч. счет ";

		}

		ext:

		return $response;

	}

	/**
	 * Отмена перемещения средств м/у счетами
	 *
	 * @param int $id - идентификатор записи перемещения средств м/у счетами
	 *
	 * @return array
	 * good result
	 *         - result = Перемещение средств успешно отменено
	 *         - data = id
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          - 403 - Запись о перемещении средств с указанным id не найден в пределах аккаунта
	 *          - 405 - Отсутствуют параметры - id записи о перемещении средств
	 *
	 * Example:
	 * ```php
	 * $Budget = \Salesman\Budget::unmove($id);
	 * ```
	 */
	public static function unmove(int $id): array {

		global $hooks;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}budjet WHERE id='$id' and identity = '$identity'");

			// Проверка на существование записи
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Запись о перемещении средств с указанным id не найдена в пределах аккаунта";

			}
			else {

				$move = $db -> getRow("SELECT * FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

				$summa  = pre_format($move['summa']);
				$rsto   = (int)$move['rs'];
				$rsfrom = (int)$move['rs2'];

				// Проверяем наличие средств на счете
				$ostatok = (float)$db -> getOne("SELECT ostatok FROM {$sqlname}mycomps_recv WHERE id = '$rsfrom' and identity = '$identity'");

				if ($ostatok >= $summa) {

					//сделаем вычет со счета
					self ::rsadd($rsfrom, (float)$summa, 'move', $rsto);

					$db -> query("DELETE FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'");

					$response['result'] = 'Перемещение средств отменено';
					$response['data']   = $id;

					if ($hooks) {
						$hooks -> do_action("budjet_unmove", $id);
					}

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '406';
					$response['error']['text'] = "Отмена невозможна: Недостаточно средств на счете. Выберите другой расч. счет";

				}
			}
		}
		else {
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id записи о перемещении средств";

		}


		return $response;

	}

	/**
	 * Получаем структуру статей расхода в бюджет
	 *
	 * @return array
	 * ```php
	 * [ dohod ] => (
	 *     [ name ] => Доходы
	 *     [ main ] => (
	 *         [ 0 ] => (
	 *             [ id ] => 4
	 *             [ title ] => Прочие поступления
	 *             [ sub ] => (
	 *                 [ 0 ] => (
	 *                     [ id ] => 5
	 *                     [ title ] => Инвестиции
	 *                 )
	 *                 [ 1 ] => (
	 *                     [ id ] => 13
	 *                     [ title ] => Наличка
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 * ```
	 */
	public static function getCategory(): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$category = [];
		$tips     = [
			'dohod'  => "Доходы",
			'rashod' => "Расходы"
		];

		foreach ($tips as $tip => $name) {

			$main = [];

			// проходим основные категории
			$res = $db -> getAll("SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and tip = '$tip' and identity = '$identity' ORDER BY title");
			foreach ($res as $da) {

				$sub = [];

				// проходим подкатегории
				$result = $db -> getAll("SELECT * FROM {$sqlname}budjet_cat WHERE subid = '".$da['id']."' and tip = '$tip' and identity = '$identity' ORDER BY title");
				foreach ($result as $data) {

					$sub[] = [
						"id"    => (int)$data['id'],
						"title" => $data['title'],
					];

				}

				$main[] = [
					"id"    => (int)$da['id'],
					"title" => $da['title'],
					"sub"   => $sub
				];

			}

			$category[$tip] = [
				"name" => $name,
				"main" => $main
			];

		}

		return $category;

	}

	/**
	 * Получаем расшифровку статей расхода в бюджет
	 *
	 * @param string $word - название или ключевое слово для поиска
	 * @param string $tip - тип статьи - rashod|dohod
	 * @return array - id => title
	 */
	public static function getCategorySimple(string $word = '', string $tip = 'rashod'): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$sort = '';

		$word = strtolower($word);

		if ($word != '') {
			$sort .= "title LIKE '%$word%' AND ";
		}

		if (isset($tip) && $tip != '') {
			$sort .= "tip = '$tip' AND ";
		}

		// проходим основные категории
		return $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}budjet_cat WHERE $sort identity = '$identity' ORDER BY title");

	}

	/**
	 * HTML-элемент select
	 *
	 * @param string $name - имя элемента
	 *
	 * @param array $opt - массив с параметрами
	 *                     - array **category** - массив элементов (готовый массив [ получать вызовом getCategory() ],
	 *                     применять в случае, если метод надо вызвать несколько раз, а структура одна)
	 *                     - int **sel** - выбранный элемент
	 *                     - array **disabled** - отключенные элементы
	 *                     - str **word** - поисковое слово названия статьи
	 *                     - str **class** - класс для элемента select
	 *
	 * @return string
	 */
	public static function categorySelect(string $name = "category", array $opt = []): string {

		$category  = ( empty($opt['category']) ) ? self ::getCategory() : $opt['category'];
		$catSelect = '';

		if ((int)$opt['prevcategory'] > 0) {

			$opt['sel']  = 0;
			$opt['word'] = '';

		}

		foreach ($category as $tip) {

			$catSelect .= '<optgroup label="'.$tip['name'].'">';

			foreach ($tip['main'] as $items) {

				$catSelect .= '<option disabled>'.$items['title'].'</option>';

				foreach ($items['sub'] as $item) {

					$sel = '';

					if (( $opt['sel'] > 0 && $item['id'] == $opt['sel'] ) || ( $opt['word'] != '' && stripos(texttosmall($item['title']), $opt['word']) !== false )) {
						$sel = 'selected';
					}
					elseif ($opt['prevcategory'] > 0 && $opt['prevcategory'] == $item['id']) {
						$sel = 'selected';
					}

					$catSelect .= '<option value="'.$item['id'].'" '.$sel.' '.( in_array($item['id'], (array)$opt['disabled']) ? 'disabled' : '' ).' '.( in_array($item['id'], (array)$opt['disabled']) ? 'class="graybg"' : '' ).'>&nbsp;&nbsp;-&nbsp;'.$item['title'].'</option>';

				}

			}

			$catSelect .= '</optgroup>';

		}

		return '<span class="select"><select name="'.$name.'" id="'.$name.'" class="'.$opt['class'].'"><option value="">--выбор--</option>'.$catSelect.'</select></span>';

	}

	/**
	 * Получаем информацию по расчетным счетам
	 *
	 * @return array
	 *              - int **id** - id записи
	 *                  - str **title** - название р/с
	 *                  - float **summa** - сумма на счету
	 *                  - str **rs** - р/с
	 *                  - str **company** - название компании
	 */
	public static function getRS(): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$rs = [];

		$re = $db -> getAll("SELECT * FROM {$sqlname}mycomps_recv WHERE identity = '$identity' ORDER by id");
		foreach ($re as $da) {

			$rs[(int)$da['id']] = [
				"title"   => $da['title'],
				"summa"   => (float)$da['ostatok'],
				"rs"      => (int)$da['rs'],
				"company" => $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps WHERE id = '$da[сid]' AND identity = '$identity'")
			];

		}

		return $rs;

	}

	/**
	 * Вывод данных для формирования сводной таблицы Бюджета
	 *
	 * @param array $params
	 * - array rs - массив id расчетных счетов
	 * - int year - год
	 */
	public static function getBudjetStat(array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		global $identity, $sqlname, $db, $userRights, $userSettings;

		$list = [];
		$year = (int)$params['year'] ? : (int)date('Y');
		$rs   = (array)$params['rs'];
		$sort = '';

		$categories = self ::getCategory();

		if (!empty($rs)) {
			$sort .= " bdj.rs IN (".implode(",", $rs).") AND";
		}

		if( $userSettings['dostup']['budjet']['chart'] != 'yes' ){
			$sort .= "bdj.iduser = '$iduser1' AND";
		}

		$query   = "
			SELECT 
				bdj.cat, bdj.mon, bdj.do, SUM(bdj.summa) as summa
			FROM {$sqlname}budjet `bdj`
			WHERE 
				$sort
				bdj.year = '$year' AND
				identity = '$identity'
			GROUP BY 1, 2, 3
		";
		$journal = $db -> getAll($query);

		$xjournal = $yjournal = $finRezult = [];
		foreach ($journal as $x) {

			$x['do'] = $x['do'] == 'on' ? $x['do'] : 'off';

			// расходы по категории/месяцу/проведению
			$xjournal[(int)$x['cat']][(int)$x['mon']][$x['do']] = $x['summa'];

			// суммы расходов по категории/проведению (расчет итогов)
			$yjournal[(int)$x['cat']][$x['do']] += $x['summa'];

		}

		$itog = [];
		foreach ($categories as $type => $types) {

			foreach ($types as $items) {

				foreach ($items as $item) {

					$xitog = [];

					foreach ($item as $rows) {

						foreach ($rows as $k => $row) {

							foreach ($xjournal[(int)$row['id']] as $month => $jitem) {

								if ($type == 'dohod') {
									$finRezult[(int)$month] += (float)$jitem['on'];
								}
								else {
									$finRezult[(int)$month] -= (float)$jitem['on'];
								}

								$list['itog'][$type][(int)$month]['on']  += (float)pre_format($jitem['on']);
								$list['itog'][$type][(int)$month]['off'] += (float)pre_format($jitem['off']);

								$yitog['total'][$type][(int)$month]['on']  += (float)pre_format($jitem['on']);
								$yitog['total'][$type][(int)$month]['off'] += (float)pre_format($jitem['off']);

								$itog[$row['id']]['on'][(int)$month]  += (float)$jitem['on'];
								$itog[$row['id']]['off'][(int)$month] += (float)$jitem['off'];

								$xitog['bymonth'][(int)$month]['on']  += (float)pre_format($jitem['on']);
								$xitog['bymonth'][(int)$month]['off'] += (float)pre_format($jitem['off']);

								$xitog['total']['on']  += (float)$jitem['on'];
								$xitog['total']['off'] += (float)$jitem['off'];

							}

							$list['total'][$type] = $yitog['total'][$type];

							$rows[(int)$k]['id']      = (int)$rows[(int)$k]['id'];
							$rows[(int)$k]['journal'] = $xjournal[$row['id']];
							$rows[(int)$k]['catitog'] = [
								'on'  => array_sum((array)$itog[$row['id']]['on']),
								'off' => array_sum((array)$itog[$row['id']]['off'])
							];

						}

						$list[$type][(int)$item['id']]['sub'] = array_values((array)$rows);

					}

					$list[$type][(int)$item['id']] = [
						"id"    => (int)$item['id'],
						"title" => $item['title'],
						"sub"   => $list[$type][(int)$item['id']]['sub'],
						"itog"  => $xitog['bymonth'],
						"total" => $xitog['total'],
					];

				}

			}

		}

		$csort = '';
		if( $userSettings['dostup']['budjet']['chart'] != 'yes' ){
			$csort .= "cr.iduser = '$iduser1' AND";
		}

		// оплаты
		$payments  = [];
		$xpayments = $db -> getAll("
			SELECT
				MONTH(cr.invoice_date) as month, 
				cr.do,
				SUM(cr.summa_credit) as summa
			FROM {$sqlname}credit `cr`
			WHERE
			    cr.do = 'on' AND
			    YEAR(cr.invoice_date) = '$year' AND
			    $csort
			    cr.identity = '$identity'
			GROUP BY 1, 2
			ORDER BY month
		");
		foreach ($xpayments as $item) {

			$payments['data'][(int)$item['month']]['on'] = $item['summa'];
			$finRezult[(int)$item['month']]              += $item['summa'];

			$payments['summa']['on'] += $item['summa'];

		}

		// не оплаченные счета
		$xpayments = $db -> getAll("
			SELECT
				MONTH(cr.datum_credit) as month, 
				COALESCE(cr.do, 'no'),
				SUM(cr.summa_credit) as summa
			FROM {$sqlname}credit `cr`
			WHERE
			    cr.do != 'on' AND
			    YEAR(cr.datum_credit) = '$year' AND
			    $csort
			    cr.identity = '$identity'
			GROUP BY 1, 2
			ORDER BY month
		");
		foreach ($xpayments as $item) {

			$payments['data'][(int)$item['month']]['off'] = $item['summa'];
			$payments['summa']['off']                     += $item['summa'];

		}


		// итоги по доходам и расходам
		// задаем массив вручную, для случаев, когда доходов нет, только оплаты счетов
		//foreach ( $list['itog'] as $type => $sum ) {
		$tps = [
			'dohod',
			'rashod'
		];
		foreach ($tps as $type) {

			$month = 1;
			while ($month <= 12) {

				if ($type == 'dohod') {

					$list['itog'][$type][$month]['on']  += $payments['data'][$month]['on'];
					$list['itog'][$type][$month]['off'] += $payments['data'][$month]['off'];

				}

				$month++;

			}

		}

		return [
			"payments"  => $payments,
			"journal"   => $list,
			"summa"     => $yjournal,
			"finResult" => $finRezult
		];

	}

	/**
	 * Вывод журнала
	 *
	 * @param array $params
	 *  - string do = do|nodo - проведен/не проведен
	 *  - array rs - массив id расчетных счетов
	 *  - int year - год
	 *  - array category - массив статей расхода
	 *  - string word - поиск по названию/описанию расхода
	 * @return Generator
	 * @throws \Exception
	 */
	public static function getJournal(array $params = []): Generator {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		global $identity, $sqlname, $db, $userRights, $userSettings, $iduser1, $tipuser;

		$sort     = '';
		$doo      = $params['do'];
		$rs       = $params['rs'];
		$category = $params['category'];
		$year     = $params['year'] ? : date('Y');

		if ($doo == 'do') {
			$sort .= " bdj.do = 'on' AND";
		}
		elseif ($doo == 'nodo') {
			$sort .= " COALESCE(bdj.do, '') != 'on' AND";
		}

		if (isset($params['month']) && $params['month'] > 0) {
			$sort .= " bdj.mon = '$params[month]' AND";
		}

		$word = urldecode($params['word']);

		if ($word != '') {
			$sort .= " (bdj.title LIKE '%".$word."%' or bdj.des LIKE '%".$word."%') and ";
		}
		if (!empty($rs)) {
			$sort .= " bdj.rs IN (".implode(",", $rs).") and ";
		}
		if (!empty($category)) {
			$sort .= " bdj.cat IN (".implode(",", $category).") and ";
		}

		if( $userSettings['dostup']['budjet']['onlyself'] == 'yes' ){
			//$sort .= "bdj.iduser IN (".yimplode(",", get_people( $iduser1, 'yes' )).") and";
			if($tipuser != 'Поддержка продаж') {
				$sort .= "bdj.iduser IN (".yimplode(",", get_people($iduser1, 'yes')).") AND";
			}
			else {
				$sort .= "bdj.iduser = '$iduser1' AND";
			}
		}

		$query = "
			SELECT 
				bdj.id,
				bdj.datum,
				bdj.mon,
				bdj.year,
				bdj.cat,
				bdj.do,
				bdj.rs,
				bdj.rs2,
				bdj.conid,
				bdj.partid,
				bdj.title,
				bdj.des,
				bdj.did,
				bdj.mon,
				bdj.iduser,
				bdj.summa,
				bdj.invoice as invoice,
				bdj.invoice_date as invoice_date,
				bdj.date_plan as date_plan,
				bdj.invoice_paydate as invoice_paydate,
				bc.title as category,
				bc.tip as type,
				mc.tip as source
			FROM {$sqlname}budjet `bdj`
				LEFT JOIN {$sqlname}budjet_cat `bc` ON bc.id = bdj.cat
				LEFT JOIN {$sqlname}mycomps_recv `mc` ON mc.id = bdj.rs
			WHERE 
				bdj.year = '$year' AND
				$sort
				bdj.identity = '$identity' 
			ORDER by mon DESC, datum DESC";

		$res = $db -> query($query);
		while ($data = $db -> fetch($res)) {

			$change = $move = $isdo = $smove = NULL;
			$clone  = 1;

			if ($data['cat'] == '0') {
				$data['category'] = 'Внетреннее';
			}

			if ($data['type'] == 'dohod') {
				$data['type'] = '<b class="green" title="Поступление"><i class="icon-up-big green"></i></b>';
			}
			if ($data['type'] == 'rashod') {
				$data['type'] = '<b class="red" title="Расход"><i class="icon-down-big red"></i></b>';
			}

			if ((int)$data['cat'] == 0) {
				$data['type'] = '<b class="blue" title="Перемещение"><i class="icon-shuffle blue"></i></b>';
			}

			if ($data['do'] == 'on' && (int)$data['cat'] != 0) {

				$do    = '<a href="javascript:void(0)" onclick="editBudjet(\''.$data['id'].'\',\'undoit\');" title="Отменить" class="gray gray2"><i class="icon-ccw blue"></i></a>';
				$color = '';

			}
			if ($data['do'] == 'on' && (int)$data['cat'] == 0) {

				$do    = '<a href="javascript:void(0)" onclick="editBudjet(\''.$data['id'].'\',\'unmove\');" title="Отменить" class="gray gray2"><i class="icon-ccw blue"></i></a>';
				$color = '';

			}
			if ($data['do'] != 'on' && (int)$data['cat'] != 0) {

				$do    = '<a href="javascript:void(0)" onclick="editBudjet(\''.$data['id'].'\',\'edit\')" title="Провести" class="gray orange"><i class="icon-plus-circled broun"></i></a>';
				$color = 'graybg-sub';

			}
			if ((int)$data['cat'] != 0 && $data['do'] != 'on') {
				$change = 'yes';
			}

			if ((int)$data['cat'] == 0) {

				$color = '';
				$clone = '';
				$move  = 1;

			}
			if ($data['do'] == 'on') {

				$isdo = 1;

			}

			if ($data['rs2'] > 0) {

				$move = 1;

				$rsfrom = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$data[rs]' and identity = '$identity'");
				$rsto   = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$data[rs2]' and identity = '$identity'");

				$smove = "Со счета $rsfrom на счет $rsto";

			}

			if ($data['source'] == 'bank') {
				$istochnik = 'р/сч.';
			}
			elseif ($data['source'] == 'kassa') {
				$istochnik = 'касса';
			}
			else {
				$istochnik = '-/-';
			}

			$provider = $db -> getOne("SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['conid']."' and identity = '$identity'");
			$partner  = $db -> getOne("SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['partid']."' and identity = '$identity'");

			yield [
				"id"              => (int)$data['id'],
				"create"          => modifyDatetime($da['datum'], ["format" => "d.m.Y H:i"]),
				"datum"           => get_sfdate2($data['datum']),
				"period"          => $data['mon'].".".$data['year'],
				"ddo"             => $userSettings['dostup']['budjet']['action'] == 'yes' ? $do : NULL,
				"title"           => $data['title'],
				"content"         => $data['des'],
				"summa"           => num_format($data['summa']),
				"tip"             => $data['type'],
				"category"        => $data['category'],
				"istochnik"       => $istochnik,
				"conid"           => (int)$data['conid'],
				"provider"        => $provider,
				"partid"          => (int)$data['partid'],
				"partner"         => $partner,
				"did"             => (int)$data['did'],
				"deal"            => current_dogovor($data['did']),
				"user"            => current_user($data['iduser']),
				"change"          => $userSettings['dostup']['budjet']['action'] == 'yes' ? $change : NULL,
				"color"           => $color,
				"mon"             => $data['mon'],
				"clone"           => $userSettings['dostup']['budjet']['action'] == 'yes' ? $clone : NULL,
				"move"            => $userSettings['dostup']['budjet']['action'] == 'yes' ? $move : NULL,
				"smove"           => $smove,
				"isdo"            => $isdo,
				"edit"            => $userSettings['dostup']['budjet']['action'] == 'yes' ? true : NULL,
				"invoice"         => $data['invoice'],
				"invoice_date"    => !empty($data['invoice_date']) ? format_date_rus($data['invoice_date']) : NULL,
				"invoice_paydate" => !empty($data['invoice_paydate']) ? format_date_rus($data['invoice_paydate']) : NULL,
				"datePlan"        => !empty($data['date_plan']) ? format_date_rus($data['date_plan']) : NULL,
				"isOverdue"       => diffDate2($data['date_plan']) < 0 ? true : NULL,
			];

		}

	}

	/**
	 * Вывод расходов раздела Контрагенты
	 * @param array $params
	 * @return array
	 * @throws \Exception
	 */
	public static function getAgentsJournal(array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		global $identity, $sqlname, $db, $userRights, $userSettings, $iduser1, $tipuser;

		$filter = (array)$params['do'];
		$partid = $params['partid'];
		$conid  = $params['conid'];
		$word   = $params['word'];
		$year   = $params['year'];
		$user   = (int)$params['user'];
		$sort   = '';

		//print_r($tipuser);

		$list  = [];
		$total = 0;

		$count = count($filter);
		$fsort = [];
		$psort = [];

		//выполненные
		if (in_array('do', $filter)) {
			$fsort[] = "(bj.do = 'on' AND bj.year = '$year')";
		}

		//не запланированные
		if (in_array('noadd', $filter)) {
			$fsort[] = "bj.id is NULL";
		}

		//запланированные
		if (in_array('plan', $filter)) {
			$fsort[] = "(COALESCE(bj.do, '') != 'on' AND dp.bid > 0)";
		}

		if (!empty($fsort)) {
			$sort .= " (".implode(' OR ', $fsort).") AND";
		}

		//if (strlen($mon) < 2) $mon = "0".$mon;

		if (!empty($partid)) {
			$psort[] = "dp.partid IN (".yimplode(",", $partid).")";
		}

		if (!empty($conid)) {
			$psort[] = "dp.conid IN (".yimplode(",", $conid).")";
		}

		if (!empty($psort)) {
			$sort .= " (".implode(' OR ', $psort).") AND ";
		}

		if ($user > 0) {
			$sort .= " bj.iduser = '$user' AND ";
		}

		if( $userSettings['dostup']['budjet']['onlyself'] == 'yes' ){
			if($tipuser != 'Поддержка продаж') {
				$sort .= "bj.iduser IN (".yimplode(",", get_people($iduser1, 'yes')).") AND";
			}
			else {
				$sort .= "bj.iduser = '$iduser1' AND";
			}
		}

		if(!empty($word)){
			$sort .= " (SELECT COUNT(clid) FROM {$sqlname}clientcat WHERE title LIKE '%$word%' and type IN ('partner','contractor') AND (clid = dp.conid OR clid = dp.partid)) > 0 AND";
		}

		//print
		$q = "
			SELECT
				dp.id as id,
				dp.did as did,
				dp.conid as conid,
				dp.partid as partid,
				dp.summa as summa,
				dp.bid as bid,
				deal.title as dogovor,
				deal.idcategory as idcategory,
				deal.datum_plan as dplan,
				deal.clid as clid,
				deal.pid as pid,
				bj.id as bjid,
				bj.title as title,
				bj.invoice as invoice,
				bj.invoice_date as invoice_date,
				bj.date_plan as date_plan,
				bj.invoice_paydate as invoice_paydate,
				bj.do as do,
				bj.mon as mon,
				bj.year as year,
				bj.datum as datum,
				bj.conid as xconid,
				bj.partid as xpartid,
				us.iduser as iduser,
				us.title as user
			FROM {$sqlname}dogprovider `dp`
				LEFT JOIN {$sqlname}dogovor `deal` ON dp.did = deal.did
				LEFT JOIN {$sqlname}budjet `bj` ON dp.bid = bj.id
				LEFT JOIN {$sqlname}user `us` ON bj.iduser = us.iduser
			WHERE
				dp.id > 0 AND 
				$sort
				(
					( 
						(COALESCE(bj.year, '') = '' OR bj.year IS NULL) AND 
						DATE_FORMAT(deal.datum_plan, '%Y') = '$year'
					) OR 
					bj.year = '$year' OR 
					dp.did = '0'
				) AND 
			    dp.identity = '$identity'
			ORDER BY dp.id DESC
		";

		$result = $db -> getAll($q);
		foreach ($result as $da) {

			$provid     = 0;
			$progressbg = ' progress-gray';
			$prcolor    = 'black';

			$dogstatus  = (int)current_dogstepname($da['idcategory']);
			$dogcontent = current_dogstepcontent($da['idcategory']);

			if ($dogstatus != '') {

				if (is_between($dogstatus, 0, 40)) {
					//$progressbg = ' progress-gray';
					//$prcolor    = 'black';
				}
				elseif (is_between($dogstatus, 40, 60)) {
					$progressbg = ' progress-green';
					$prcolor    = 'white';
				}
				elseif (is_between($dogstatus, 60, 90)) {
					$progressbg = ' progress-red';
					$prcolor    = 'white';
				}
				elseif (is_between($dogstatus, 90, 100)) {
					$progressbg = ' progress-blue';
					$prcolor    = 'white';
				}

			}

			$period = ( (int)$da['mon'] > 0 ) ? $da['mon'].'.'.$da['year'] : NULL;

			$icn = ( getDogData($da['did'], 'close') == 'yes' ) ? '<i class="icon-lock red"></i>' : '';

			$conid  = (int)$da['conid'] > 0 ? (int)$da['conid'] : (int)$da['xconid'];
			$partid = (int)$da['partid'] > 0 ? (int)$da['partid'] : (int)$da['xpartid'];

			if ( $conid > 0 ) {
				$contragent = current_client($conid);
				$tip        = 'contractor';
				$tipname    = 'Поставщик';
				$provid     = $conid;
				$s          = "and conid = '$conid'";
			}
			elseif ( $partid > 0 ) {
				$contragent = current_client($partid);
				$tip        = 'partner';
				$tipname    = 'Партнер';
				$provid     = $partid;
				$s          = "and partid = '$partid'";
			}

			if ( (int)$da['did'] == 0 && empty($da['date_plan'])) {
				$da['dplan'] = $da['year'].'-'.$da['mon'].'-01';
			}

			$dplan = !empty($da['date_plan']) ? $da['date_plan'] : $da['dplan'];
			$bgcolor = ( $da['do'] == 'on' ) ? 'bgwhite' : 'graybg-sub gray2';

			$changelog = self ::logStatusGet((int)$da['bjid']);

			$list[] = [
				"id"              => (int)$da['id'],
				"create"          => modifyDatetime($da['datum'], ["format" => "d.m.Y H:i"]),
				"month"           => getMonth($dplan),
				"bid"             => ( (int)$da['bjid'] == 0 ) ? NULL : (int)$da['bjid'],
				"do"              => ( $da['do'] == 'on' ) ? true : NULL,
				"dotext"          => $da['do'],
				"title"           => $da['title'],
				"period"          => $period,
				"conid"           => (int)$da['conid'],
				"partid"          => (int)$da['partid'],
				"providerId"      => $provid,
				"providerTitle"   => $contragent,
				"providerTip"     => $tip,
				"providerTipName" => $tipname,
				"invoice"         => $da['invoice'],
				"invoice_date"    => !empty($da['invoice_date']) ? format_date_rus($da['invoice_date']) : NULL,
				"invoice_paydate" => !empty($da['invoice_paydate']) ? format_date_rus($da['invoice_paydate']) : NULL,
				"summa"           => num_format($da['summa']),
				"progressbar"     => ( (int)$da['did'] > 0 ) ? '<DIV class="progressbarr">'.$dogstatus.'%<DIV id="test" class="progressbar-completed '.$progressbg.'" style="width:'.$dogstatus.'%" title="'.$dogstatus." - ".$dogcontent.'"><DIV class="status '.$prcolor.'"></DIV></DIV></DIV>' : '',
				"datePlan"        => !empty($dplan) ? format_date_rus($dplan) : NULL,
				"isOverdue"       => diffDate2($dplan) < 0 ? true : NULL,
				"clid"            => ( (int)$da['clid'] > 0 ) ? (int)$da['clid'] : NULL,
				"client"          => ( (int)$da['clid'] > 0 ) ? current_client($da['clid']) : NULL,
				"pid"             => ( (int)$da['pid'] > 0 ) ? (int)$da['pid'] : NULL,
				"person"          => ( (int)$da['pid'] > 0 ) ? current_person($da['pid']) : NULL,
				"did"             => ( (int)$da['did'] > 0 ) ? (int)$da['did'] : NULL,
				"deal"            => ( (int)$da['did'] > 0 ) ? current_dogovor($da['did']) : NULL,
				"icon"            => $icn,
				"bgcolor"         => $bgcolor,
				"edit"            => $userSettings['dostup']['budjet']['action'] == 'yes' ? true : NULL,
				"iduser"          => (int)$da['iduser'],
				"user"            => $da['user'],
				"changelog"       => !empty($changelog) ? true : NULL
			];

			$total += $da['summa'];

		}

		return [
			"list"     => $list,
			"page"     => (int)$page,
			"pageall"  => (int)$count_pages,
			"valuta"   => $valuta,
			"total"    => num_format($total),
			"dealname" => $lang['face']['DealName'][0]
		];

	}

	/**
	 * Действие. Перемещение денег со счета на счет
	 *
	 * @param int $rs
	 * @param float $summa
	 * @param string|null $operacia
	 * @param string|null $rs_move
	 *
	 * @return bool
	 * @category Core
	 * @package  Func
	 */
	public static function rsadd(int $rs = 0, float $summa = 0.00, string $operacia = NULL, string $rs_move = NULL): bool {

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($rs > 0) {

			//найдем сумму, имеющуюся на р/счете
			$ostatok1 = $db -> getOne("SELECT ostatok FROM {$sqlname}mycomps_recv WHERE id = '$rs' AND identity = '$identity'");

			if (
				in_array($operacia, [
					'plus',
					'minus'
				])
			) {

				//добавим платеж на р/счет
				$new_summ = ( $operacia == 'plus' ) ? ( (float)pre_format($ostatok1) + (float)pre_format($summa) ) : ( pre_format($ostatok1) - pre_format($summa) );

				$db -> query("UPDATE {$sqlname}mycomps_recv SET ostatok = '$new_summ' WHERE id = '$rs' and identity = '$identity'");

			}
			elseif ($operacia == 'move') {

				$ostatok2 = $db -> getOne("SELECT ostatok FROM {$sqlname}mycomps_recv WHERE id = '$rs_move' AND identity = '$identity'");

				$sum1 = pre_format($ostatok1) - pre_format($summa);              //с этого счета снимаем
				$sum2 = (float)pre_format($ostatok2) + (float)pre_format($summa);//на этот счет получаем

				$db -> query("UPDATE {$sqlname}mycomps_recv SET ostatok = '$sum1' WHERE id = '$rs' and identity = '$identity'");
				$db -> query("UPDATE {$sqlname}mycomps_recv SET ostatok = '$sum2' WHERE id = '$rs_move' and identity = '$identity'");

			}

		}

		return true;

	}

	/**
	 * Метод ортправки уведомления всем сотрудникам в одном письме
	 *
	 * @param int $id - id расхода
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	public static function sendEmailNotify(int $id = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$subject = $mes = '';

		$rez     = 'Какая-то ошибка';
		$message = "Некому отправлять";

		if (empty($params['users'])) {

			return [
				"result" => "error",
				"text"   => $message
			];

		}

		// данные о ветке
		$theme = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");

		if ($event == '' || $event == 'new') {

			$answer = self ::info($id);

			$subject = "Приглашение к обсуждению - ".$theme['title']." ( CRM )";
			$mes     = "
				<b>Вы приглашены к обсуждению.</b><br><br>
				Тема &#8220;".$theme['title']."&#8221;.<br><br>
				Текст: ".htmlspecialchars_decode($answer['content'])."<br><br>
				".( $theme['clid'] > 0 ? "Клиент: <b>".current_client($theme['clid'])."</b><br>" : "" )."
				".( $theme['pid'] > 0 ? "Клиент: <b>".current_person($theme['pid'])."</b><br>" : "" )."
				".( $theme['did'] > 0 ? "Сделка: <b>".current_dogovor($theme['did'])."</b><br>" : "" )."
				=======================<br>
				Администратор CRM
			";

		}
		elseif ($event == 'answer') {

			$answer = self ::info($idcomment);

			$subject = "Новый ответ в обсуждении - ".$theme['title']." ( CRM )";
			$mes     = "
				<b>В обсуждении появились новые ответы.</b><br><br>
				Тема &#8220;".$theme['title']."&#8221;.<br><br>
				Текст ответа: ".htmlspecialchars_decode($answer['content'])."<br><br>
				".( $theme['clid'] > 0 ? "Клиент: <b>".current_client($theme['clid'])."</b><br>" : "" )."
				".( $theme['pid'] > 0 ? "Клиент: <b>".current_person($theme['pid'])."</b><br>" : "" )."
				".( $theme['did'] > 0 ? "Сделка: <b>".current_dogovor($theme['did'])."</b><br>" : "" )."
				=======================<br>
				Администратор CRM
			";

		}

		$cc    = [];
		$names = [];
		foreach ($users as $user) {

			//найдем контакты пользователя
			$resultu = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$user' and identity = '$identity'");
			$utitle  = $resultu["title"];
			$umail   = $resultu["email"];

			// различные настройки пользователя
			$usersettings = json_decode($resultu["usersettings"], true);

			// отправляем только если пользователь подписан
			if (
				( ( $event == '' || $event == 'new' ) && $usersettings['subscribs']['comments.new'] == 'on' ) || ( $event == 'answer' && $usersettings['subscribs']['comments.answer'] == 'on' )
			) {

				$cc[] = [
					"email" => $umail,
					"name"  => $utitle
				];

				$names[] = $utitle;

			}

		}

		if ($subject != '' && $mes != '' && !empty($cc)) {

			$u = array_shift($cc);

			$rez     = mailto([
				$u['email'],
				$u['name'],
				"no-replay@localhost",
				"CRM",
				$subject,
				$mes,
				[],
				$cc
			]);
			$message = ( $rez == '' ) ? "Сотрудники ".yimplode(", ", $names)." приглашены к обсуждению" : "Ошибка: ".$rez;

		}

		return [
			"result" => ( $rez == '' ) ? "ok" : "error",
			"text"   => $message
		];

	}

	/**
	 * Отправка уведомлений через систему Нотификации
	 *
	 * @param       $event - budjet.new, budjet.edit, budjet.doit
	 *
	 * @param array $params
	 *                     id - ID Расхода [обязательное ]
	 *                     title - Заголовок  [ не обязательное ]
	 *                     content - Содержание [ желательное ]
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function sendNotify($event, array $params = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		require_once $rootpath."/class/Notify.php";

		global $iduser1, $valuta;

		$tag   = $r = [];
		$title = '';

		$tag['users'] = [];

		if ((int)$params['id'] > 0) {

			$tag['users'] = [];

			$budjet = self ::info($params['id']);

			switch ($event) {

				case 'budjet.new':

					$title = 'Новый расход';

					break;
				case 'budjet.edit':

					$title = 'Изменен расход';

					break;
				case 'budjet.doit':

					$title = 'Проведен расход';

					break;

			}

			$tag['url']     = "editBudjet('".$params['id']."','view');";
			$tag['title']   = $title.' - '.( !empty($params['title']) ? $params['title'] : $budjet['budget']['title'] );
			$tag['content'] = "Сумма: ".$budjet['budget']['summa']." $valuta. Комментарий: ".( !empty($params['content']) ? $params['content'] : $budjet['budget']['des'] );
			$tag['tip']     = $event;
			$tag['id']      = $params['id'];

			if ($params['iduser'] != $iduser1) {
				$tag['users'][] = $params['iduser'];
			}

			// todo: добавить получатетей из доступов к делке
			if ($budjet['budget']['did'] > 0) {

				$deal = Deal ::info($budjet['budget']['did']);

				$tag['content'] .= ". По сделке: ".$deal['title'];

				// куратор по сделке
				$tag['users'][] = $deal['iduser'];

				// сотрудники с доступом к сделке
				// тут вопрос - а надо ли?
				$tag['users'] = array_merge($tag['users'], $deal['accesse']);

			}

			//$tag['users'][] = $params['iduser'];
			//$tag['users'][] = $iduser1;

			$tag['users'] = array_unique($tag['users']);

			if (!empty($tag['users'])) {
				$r = Notify ::fire("self", $iduser1, $tag);
			}

		}

		return [
			"result" => $r,
			"data"   => $tag
		];

	}

	/**
	 * Логгирование статуса
	 * @param $id
	 * @param string $status
	 * @param string $comment
	 * @return bool
	 */
	public static function logStatus($id, string $status = 'new', string $comment = ''): bool {

		global $iduser1, $identity, $sqlname, $db;

		if ((int)$id > 0) {

			$db -> query("INSERT INTO {$sqlname}budjetlog SET ?u", [
				"status"   => $status,
				"bjid"     => (int)$id,
				"iduser"   => (int)$iduser1,
				"comment"  => cleanTotal($comment),
				"identity" => $identity
			]);

			return true;

		}

		return false;

	}

	/**
	 * Лог изменения статусов
	 * @param int $id
	 * @return array
	 */
	public static function logStatusGet(int $id = 0): array {

		global $identity, $sqlname, $db;

		$statuses = [
			"new"       => "Новый",
			"edit"      => "Изменен",
			"corrected" => "Коррекция",
			"complete"  => "Выполнен"
		];

		$list = [];

		$result = $db -> getAll("
			SELECT
				sl.datum,
				sl.status,
				sl.iduser,
				sl.comment,
				us.title as user
			FROM {$sqlname}budjetlog `sl`
				LEFT JOIN {$sqlname}user `us` ON us.iduser = sl.iduser
			WHERE 
				sl.bjid = '$id' and 
				sl.identity = '$identity' 
			ORDER BY sl.id
		");
		foreach ($result as $item) {

			$list[] = [
				"datum"      => $item['datum'],
				"status"     => $item['status'],
				"statusName" => $statuses[$item['status']],
				"iduser"     => toShort($item['iduser']),
				"user"       => $item['user'],
				"comment"    => $item['comment']
			];

		}

		return $list;

	}

	/**
	 * Удаление статусов (например, при удалении расхода)
	 * @param int $id
	 * @return void
	 */
	public static function logStatusDelete(int $id = 0): void {

		global $identity, $sqlname, $db;

		$db -> query("DELETE FROM {$sqlname}budjetlog WHERE bjid = '$id' and identity = '$identity'");

	}

}