<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Salesman;

use event;
use Exception;

/**
 * Класс для работы с объектом Контакт
 *
 * Class Person
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example:
 *
 * ```php
 * $Person  = new Salesman\Person();
 * $result = $Person -> edit(0, $params);
 * $pid = $result['data'];
 * ```
 */
class Person {

	public $isdouble = [];
	public $doubleid = 0;

	public $response = [];

	/**
	 * @var array
	 */
	public $otherSettings, $settingsUser;

	/**
	 * Абсолютный путь
	 *
	 * @var string
	 */
	public $rootpath;
	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;
	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Currency constructor.
	 */
	public function __construct() {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		require_once $rootpath."/vendor/autoload.php";

		$params = $this -> params;

		$this -> rootpath = dirname(__DIR__, 2);
		$this -> identity = ( $params['identity'] > 0 ) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> skey     = ( $this -> skey != '' ) ? $this -> skey : $GLOBALS['skey'];
		$this -> ivc      = ( $this -> ivc != '' ) ? $this -> ivc : $GLOBALS['ivc'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		// тут почему-то не срабатывает
		if (!empty($params)) {
			foreach ($params as $key => $val) {
				$this ->{$key} = $val;
			}
		}

		if (file_exists($rootpath."/cash/".$this -> fpath."otherSettings.json")) {

			$this -> otherSettings = json_decode(file_get_contents($rootpath."/cash/".$this -> fpath."otherSettings.json"), true);

		}
		else {

			$other                 = explode(";", $this -> db -> getOne("SELECT other FROM ".$this -> sqlname."settings WHERE id = '".$this -> identity."'"));
			$this -> otherSettings = [
				"partner"              => $other[0] == 'yes',
				"concurent"            => $other[1] == 'yes',
				"credit"               => $other[2] == 'yes',
				"price"                => $other[3] == 'yes',
				"dealPeriod"           => $other[4] == 'yes',
				"contract"             => $other[5] == 'yes',
				"creditAlert"          => $other[6],
				"dealAlert"            => $other[7],
				"profile"              => $other[8] == 'yes',
				"marga"                => $other[9] == 'yes',
				"potential"            => $other[10] == 'yes',
				"expressForm"          => $other[11] == 'yes',
				"printInvoice"         => $other[12] == 'yes',
				"clientIsPerson"       => $other[13] != 'yes',
				"dop"                  => $other[14] == 'yes',
				"dopName"              => $other[15],
				"comment"              => $other[16] == 'yes',
				"contractor"           => $other[17] == 'yes',
				"planByClosed"         => $other[18] == 'yes',
				"taskControl"          => (int)$other[19],
				"taskControlClientAdd" => $other[20] == 'yes',
				"woNDS"                => $other[21] == 'yes',
				"dealByContact"        => $other[22] == 'yes',
				"addClientWDeal"       => $other[23] == 'yes',
				"changeDealPeriod"     => $other[24],
				"dealStepDefault"      => $other[25],
				"dealPeriodDefault"    => $other[26] != '' && $other[26] != 'no' ? $other[26] : 14,
				"changeDealComment"    => $other[27] == 'yes',
				"changeUserComment"    => $other[28] == 'yes',
				"ndsInOut"             => $other[29] == 'yes',
				"saledProduct"         => $other[30] == 'yes',
				"guidesEdit"           => $other[31] == 'yes',
				"taskEditTime"         => $other[32],
				"taskControlInHealth"  => $other[33] == 'yes',
				"artikulInInvoice"     => $other[34] == 'yes',
				"artikulInAkt"         => $other[35] == 'yes',
				"mailerMsgUnion"       => $other[36] == 'yes',
				"stepControlInHealth"  => $other[37] == 'yes',
				"budjetDayIsNow"       => $other[38],
				"aktTempService"       => ( !isset($other[39]) || $other[39] == 'no' ) ? 'akt_full.tpl' : $other[39],
				"invoiceTempService"   => ( !isset($other[40]) || $other[40] == 'no' ) ? 'invoice.tpl' : $other[40],
				"aktTemp"              => ( !isset($other[41]) || $other[41] == 'no' ) ? 'akt_full.tpl' : $other[41],
				"invoiceTemp"          => ( !isset($other[42]) || $other[42] == 'no' ) ? 'invoice.tpl' : $other[42],
			];

		}

		$this -> settingsUser = User ::settings($this -> iduser1);

		date_default_timezone_set($this -> tmzone);

	}

	/**
	 * Добавление/ обновление контакта
	 *
	 * @param       $pid - id контакта
	 * @param array $params - параметры
	 *
	 * @return array - массив результатов
	 * good result - успешные результаты
	 *        - Добавление:
	 *             - **result** = Контакт добавлен
	 *             - **data** = $pid
	 *        - Изменение:
	 *             - **result** = Данные контакта обновлены
	 *             - **data** = $pid
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *          403 - Контакт с указанным pid не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - pid контакта
	 *
	 * Example:
	 * ```php
	 * $Person = \Salesman\Person::fullupdate($pid,$params);
	 * ```
	 * @throws Exception
	 */
	public function fullupdate($pid, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		if ($hooks) {
			$params = $hooks -> apply_filters("person_editfilter", $params);
		}

		$person = $params;
		$post   = $params;

		//print_r($person);

		unset($person['action']);

		$aparams = $fields = $err = $mes = $newParams = $oldParams = [];

		$res = $db -> getAll("select * from ".$sqlname."field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order");
		foreach ($res as $data) {

			if ($data['fld_name'] == 'social') {
				continue;
			}

			//если прилетел массив, то преобразуем его в строку
			$person[$data['fld_name']] = ( is_array($person[$data['fld_name']]) ) ? yimplode(", ", $person[$data['fld_name']]) : trim($person[$data['fld_name']]);

			$fields[] = $data['fld_name'];

		}

		foreach ($fields as $i => $field) {

			//для АПИ проверяем пришедшие поля и если поля нет, то исключаем из запроса
			//if($params['fromapi'] && !in_array($field, array_keys($params))) goto extt;

			if ($field == 'rol') {

				$aparams[$field] = yimplode(";", explode(";", $person[$field]));

			}
			else {

				$aparams[$field] = fieldClean(str_replace("\\r\\n", "\r\n", $person[$field]));

			}

			//extt:

		}

		$aparams['social'] = $params['blog'].";".$params['mysite'].";".$params['twitter'].";".$params['icq'].";".$params['skype'].";".$params['google'].";".$params['yandex'].";".$params['mykrug'];

		if ($aparams['social'] == ";;;;;;;") {
			unset($aparams['social']);
		}

		unset($params['blog'], $params['mysite'], $params['twitter'], $params['icq'], $params['skype'], $params['google'], $params['yandex'], $params['mykrug']);

		$mperson               = $params['mperson'];
		$aparams['clid']       = $params['clid'];
		$aparams['clientpath'] = !empty($params['clientpath']) ? (int)$params['clientpath'] : (int)$GLOBALS['relDefault'];
		$aparams['loyalty']    = isset($params['loyalty']) ? (int)$params['loyalty'] : 0;
		$aparams['iduser']     = $params['iduser'];

		$aparams['date_edit'] = current_datumtime();
		$aparams['editor']    = $iduser1;

		$personOld = json_decode(get_person_info($pid), true);

		if ($aparams['clientpath'] == 0) {
			$aparams['clientpath'] = (int)$personOld['clientpath'];
		}

		foreach ($aparams as $key => $value) {

			$newParams[$key] = $value;          //массив новых параметров
			$oldParams[$key] = $personOld[$key];//массив старых параметров

		}

		//print_r($aparams);

		//print_r($newParams);
		//print_r($oldParams);

		$log = doLogger('pid', $pid, $newParams, $oldParams);

		//если есть измененнные данные, то обновляем
		if ($log != 'none' && !empty((array)$params)) {

			try {

				//print_r($aparams);

				$db -> query("UPDATE ".$sqlname."personcat SET ?u where pid = '$pid' and identity = '$identity'", $aparams);

				$aparams['pid'] = $pid;

				if ($hooks) {
					$hooks -> do_action("person_edit", $post, $aparams);
				}

				//print_r($aparams);
				//print $db -> lastQuery();

				$mes[] = "Данные контакта обновлены";

				addHistorty([
					"iduser"   => $iduser1,
					"pid"      => $pid,
					"clid"     => $aparams['clid'],
					"datum"    => current_datumtime(),
					"des"      => $log,
					"tip"      => "ЛогCRM",
					"untag"    => "no",
					"identity" => $identity
				]);

				$diff2 = array_diff_ext($oldParams, $newParams);
				event ::fire('person.edit', $args = [
					"pid"      => $pid,
					"autor"    => $iduser1,
					"newparam" => $diff2
				]);

			}
			catch (Exception $e) {

				$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

			if ($aparams['iduser'] != $iduser1) {
				sendNotify('send_person', $params = [
					"pid"    => $pid,
					"person" => untag($aparams['person']),
					"clid"   => $aparams['clid'],
					"iduser" => $aparams['iduser'],
					"notice" => 'yes'
				]);
			}

			if ($mperson == 'yes' && $aparams['clid'] > 0) {

				$db -> query("update ".$sqlname."clientcat set pid = '$pid' where clid = '$aparams[clid]' and identity = '$identity'");

			}

			/**
			 * Проходим письма в почтовике и присоединим созданный Клиент/Контакт
			 */
			if ($aparams['mail'] != '') {

				//$clid = $aparams['clid'];

				$mail = yexplode(",", str_replace(";", ",", $aparams['mail']));
				foreach ($mail as $email) {

					//если pid не указан, то добавляем
					//$db -> query("UPDATE ".$sqlname."ymail_messagesrec SET pid = IF(pid = 0, '$pid', pid), clid = IF(clid = 0, '$clid', clid) WHERE email = '$email' and identity = '$identity'");
					$this -> checkMailerEmail($email, $pid);

				}

			}

		}
		else {

			$response['result'] = 'Данные корректны, но идентичны имеющимся. '.$mes;
			$response['data']   = $pid;

		}

		//проверка дублей
		$this -> checkDouble($pid);

		return [
			"result" => implode(",", $mes),
			"data"   => $pid,
			"error"  => implode(",", $err),
		];

	}

	/**
	 * Добавление/ обновление контакта
	 *
	 * @param int $pid - id контакта (0, если требуется Добавить новый)
	 * @param array $params - параметры
	 *
	 * @return array
	 * good result - результаты
	 *        - Добавление:
	 *             - **result** = Контакт добавлен
	 *             - **data** = $pid
	 *        - Изменение:
	 *             - **result** = Данные контакта обновлены
	 *             - **data** = $pid
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *         - 403 - Контакт с указанным pid не найден в пределах аккаунта
	 *         - 405 - Отсутствуют параметры - pid контакта
	 *
	 * Пример:
	 * ```php
	 * $Person = \Salesman\Person::edit($pid,$params);
	 * ```
	 * @throws Exception
	 */
	public function edit(int $pid = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;

		if ((int)$params['iduser'] == 0) {
			$params['iduser'] = !isset($params['user']) ? $iduser1 : current_userbylogin($params['user']);
		}

		$person = $params;

		if ($pid > 0) {
			$params = $hooks -> apply_filters("person_editfilter", $params);
		}

		else {
			$params = $hooks -> apply_filters("person_addfilter", $params);
		}

		//print_r($person);

		$did = (int)$params['did'];

		unset($person['action']);

		$aparams = $fields = $err = $mes = $newParams = $oldParams = [];

		$res = $db -> getAll("select * from ".$sqlname."field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order");
		foreach ($res as $data) {

			if (!$person[$data['fld_name']] || $data['fld_name'] == 'social') {
				continue;
			}

			//если прилетел массив, то преобразуем его в строку
			$person[$data['fld_name']] = ( is_array($person[$data['fld_name']]) ) ? yimplode(", ", $person[$data['fld_name']]) : trim($person[$data['fld_name']]);

			$fields[] = $data['fld_name'];

		}

		foreach ($fields as $i => $field) {

			if (isset($person[$field])) {

				switch ($field) {

					case "loyalty":

						$aparams[$field] = ( is_numeric($person[$field]) ) ? (int)$person[$field] : (int)getPersonLoyalty(untag($person[$field]));

						break;
					case "clientpath":

						$aparams[$field] = ( is_numeric($person[$field]) ) ? (int)$person[$field] : (int)getClientpath($person[$field]);

						break;
					case "rol":

						$aparams[$field] = yimplode(";", yexplode(";", $person[$field]));

						break;
					default:

						$aparams[$field] = fieldClean($person[$field]);

						break;

				}

			}

		}

		$socInfoField = [
			"blog",
			"mysite",
			"twitter",
			"icq",
			"skype",
			"google",
			"yandex",
			"mykrug"
		];

		$mperson = $params['mperson'];
		if ((int)$params['clid'] > 0) {
			$aparams['clid'] = (int)$params['clid'];
		}
		$aparams['clientpath'] = !empty($params['clientpath']) ? (int)$params['clientpath'] : (int)$GLOBALS['relDefault'];
		$aparams['loyalty']    = ( isset($params['loyalty']) ) ? (int)$params['loyalty'] : 0;
		$aparams['iduser']     = (int)$params['iduser'];

		if ($pid < 1) {

			if ($person['person'] != '') {

				if (!empty($params['socials'])) {

					$aparams['social'] = $params['socials']['blog'].";".$params['socials']['mysite'].";".$params['socials']['twitter'].";".$params['socials']['icq'].";".$params['socials']['skype'].";".$params['socials']['google'].";".$params['socials']['yandex'].";".$params['socials']['mykrug'];

				}
				else {

					$aparams['social'] = $params['blog'].";".$params['mysite'].";".$params['twitter'].";".$params['icq'].";".$params['skype'].";".$params['google'].";".$params['yandex'].";".$params['mykrug'];

				}

				if ($aparams['social'] == ";;;;;;;") {
					unset($aparams['social']);
				}

				$aparams['date_create'] = current_datumtime();
				$aparams['creator']     = $iduser1;
				$aparams['identity']    = $identity;

				//print_r($aparams);

				try {

					$db -> query("INSERT INTO ".$sqlname."personcat SET ?u", arrayNullClean($aparams));
					$pid = $db -> insertId();

					$aparams['pid'] = $pid;

					if ($hooks) {
						$hooks -> do_action("person_add", $post, $aparams);
					}

					event ::fire('person.add', $args = [
						"pid"   => $pid,
						"autor" => $iduser1
					]);

					$mes[] = "Контакт добавлен";

				}
				catch (Exception $e) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

		}
		else {

			$pid = (int)$db -> getOne("SELECT pid FROM {$sqlname}personcat WHERE (pid = '$pid') and identity = '$identity'");

			if ($pid == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Контакт с указанным pid не найден в пределах аккаунта";

				goto ext;

			}
			elseif ($pid > 0) {

				$aparams['date_edit'] = current_datumtime();
				$aparams['editor']    = $iduser1;

				$personOld             = get_person_info($pid, 'yes');
				$aparams['clientpath'] = (int)$personOld['clientpath'];

				$socOld = explode(";", $personOld['social']);

				if (isset($params['socials'])) {

					$soc = [];
					foreach ($socInfoField as $k => $v) {

						$soc[$v] = $params['socials'][$v] ?? $socOld[$k];

					}

					$aparams['social'] = implode(";", $soc);

				}
				else {

					$soc = [];
					foreach ($socInfoField as $k => $v) {

						$soc[$v] = $params[$v] ?? $socOld[$k];

					}

					$aparams['social'] = implode(";", $soc);

				}

				//print_r($aparams);

				foreach ($aparams as $key => $value) {

					$newParams[$key] = $value;          //массив новых параметров
					$oldParams[$key] = $personOld[$key];//массив старых параметров

				}

				$log = doLogger('pid', $pid, $newParams, $oldParams);

				//если есть измененнные данные, то обновляем
				if ($log != 'none' && !empty((array)$params)) {

					try {

						$db -> query("UPDATE ".$sqlname."personcat SET ?u where pid = '$pid' and identity = '$identity'", $aparams);

						$aparams['pid'] = $pid;

						if ($hooks) {
							$hooks -> do_action("person_edit", $post, $aparams);
						}

						$mes[] = "Данные контакта обновлены";

						addHistorty([
							"iduser"   => $iduser1,
							"pid"      => $pid,
							"clid"     => $aparams['clid'],
							"datum"    => current_datumtime(),
							"des"      => $log,
							"tip"      => "ЛогCRM",
							"untag"    => "no",
							"identity" => $identity
						]);

						$diff2 = array_diff_ext($oldParams, $newParams);
						event ::fire('person.edit', $args = [
							"pid"      => $pid,
							"autor"    => $iduser1,
							"newparam" => $diff2
						]);

						/*event ::fire( 'person.edit', $args = [
							"pid"   => $pid,
							"autor" => $iduser1
						] );*/

					}
					catch (Exception $e) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}
				else {

					$response['result'] = 'Данные корректны, но идентичны имеющимся. '.$mes;
					$response['data']   = $pid;

				}

			}

		}

		if ($aparams['iduser'] != $iduser1) {
			sendNotify('send_person', $params = [
				"pid"    => $pid,
				"person" => untag($aparams['person']),
				"clid"   => $aparams['clid'],
				"iduser" => $aparams['iduser'],
				"notice" => 'yes'
			]);
		}

		if ($mperson == 'yes' && $aparams['clid'] > 0) {

			$db -> query("update ".$sqlname."clientcat set pid = '$pid' where clid = '$aparams[clid]' and identity = '$identity'");

		}

		/**
		 * Проходим письма в почтовике и присоединим созданный Клиент/Контакт
		 */
		if ($aparams['mail'] != '') {

			//$clid = $aparams['clid'];

			$mail = yexplode(",", str_replace(";", ",", $aparams['mail']));
			foreach ($mail as $email) {

				//если pid не указан, то добавляем
				//$db -> query("UPDATE ".$sqlname."ymail_messagesrec SET pid = IF(pid = 0, '$pid', pid), clid = IF(clid = 0, '$clid', clid) WHERE email = '$email' and identity = '$identity'");
				$this -> checkMailerEmail($email, $pid);

			}

		}

		/**
		 * Добавляем к сделке
		 */
		if ($did > 0) {

			$pid_list = (array)yexplode(";", getDogData($did, "pid_list"));

			$pid_list[] = $pid;

			$deal = new Deal();
			$deal -> update($did, ["pid_list" => $pid_list]);

		}

		//проверка дублей. не проверяем, если редактирование вызвано из слияния дублей
		if (!isset($params["ckeck"]) && !$params["ckeck"]) {
			$this -> checkDouble($pid, ["ckeck" => $params["ckeck"]]);
		}

		ext:

		return [
			"result" => implode(",", $mes),
			"data"   => $pid,
			"error"  => implode(",", $err),
		];

	}

	/**
	 * Удаление контакта
	 *
	 * @param int $pid - идентификатор записи контакта
	 *
	 * @return array
	 * good result
	 *         - result = Контакт удален
	 *         - data = pid
	 *
	 * error result
	 *         - result = Контакт не удален
	 *         - error = Удаление записи невозможно. Причина - Имеются связанные записи - Сделки
	 *
	 * Пример:
	 * ```php
	 * $Person = \Salesman\Person::delete($pid);
	 * ```
	 */
	public function delete(int $pid = 0): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$fpath    = $this -> fpath;

		$title = current_person($pid);

		//проверяем на наличие сделок
		$count = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."dogovor WHERE pid = '$pid' and identity = '$identity'");

		if ($count > 0) {

			$error = 'Внимание: К сожалению Удаление записи не возможно. Причина - Имеются связанные записи - Сделки.';

			$response['result'] = 'Контакт не удален';
			$response['error']  = $error;
			$response['data']   = $pid;

		}
		else {

			if ($hooks) {
				$hooks -> do_action("person_delete", $pid);
			}

			$countFiles = 0;

			//Удалим всю связанные файлы
			$res = $db -> getAll("select * from ".$sqlname."file WHERE pid = '$pid' and identity = '$identity'");
			foreach ($res as $data) {

				if ($data['clid'] > 0 || $data['did'] > 0) {
					$db -> query("update ".$sqlname."file set pid = '' where pid = '$pid' and identity = '$identity'");
				}
				else {

					@unlink($rootpath."/files/".$fpath.$data['fname']);
					$db -> query("delete from ".$sqlname."file where fid = '".$data['fid']."' and identity = '$identity'");

					$countFiles++;

				}

			}

			$db -> query("update ".$sqlname."leads set pid = '' where pid = '".$pid."' and identity = '$identity'");
			$countLead = $db -> affectedRows();

			$db -> query("update ".$sqlname."entry set pid = '' where clid = '$pid' and identity = '$identity'");
			$countEntry = $db -> affectedRows();

			$countHistory = 0;
			$result1      = $db -> query("select * from ".$sqlname."history WHERE FIND_IN_SET('$pid', REPLACE(pid, ';',',')) > 0 and identity = '$identity'");
			while ($data = $db -> fetch($result1)) {

				$pids = yexplode(";", $data['pid']);

				if (( $key = array_search($pid, $pids) ) !== false) {
					unset($pids[$key]);
				}

				if ($data['clid'] < 1 and $data['did'] < 1) {

					if (count((array)$pids) == 0) {
						$db -> query("delete from ".$sqlname."history where cid = '$data[cid]' and identity = '$identity'");
					}
					else {
						$db -> query("update ".$sqlname."history set pid = '".implode(";", $pids)."' where cid = '$data[cid]' and identity = '$identity'");
					}

				}
				else {
					$db -> query("update ".$sqlname."history set pid = '".implode(";", $pids)."' where cid = '$data[cid]' and identity = '$identity'");
				}

				$countHistory++;

			}

			$db -> query("delete from ".$sqlname."dogovor where pid = '$pid' and identity = '$identity'");
			$countDogovor = $db -> affectedRows();

			//Удалим все напоминания
			$countTask = 0;
			$result1   = $db -> query("select * from ".$sqlname."tasks WHERE FIND_IN_SET('".$pid."', REPLACE(pid, ';',',')) > 0 and identity = '$identity'");
			while ($data = $db -> fetch($result1)) {

				$pids = yexplode(";", $data['pid']);

				if (( $key = array_search($pid, $pids) ) !== false) {
					unset($pids[$key]);
				}

				if ($data['clid'] < 1 and $data['did'] < 1) {

					if (count((array)$pids) == 0) {
						$db -> query("delete from ".$sqlname."tasks where tid = '".$data['tid']."' and identity = '$identity'");
					}
					else {
						$db -> query("update ".$sqlname."tasks set pid = '".implode(";", $pids)."' where tid = '".$data['tid']."' and identity = '$identity'");
					}

				}
				else {
					$db -> query("update ".$sqlname."tasks set pid = '".implode(";", $pids)."' where tid = '".$data['tid']."' and identity = '$identity'");
				}

				$countTask++;

			}

			//удалим привязки в письмах
			$db -> query("UPDATE ".$sqlname."ymail_messagesrec SET pid = '0', clid = '0' WHERE pid = '$pid' and identity = '$identity'");

			$db -> query("delete from ".$sqlname."personcat where pid = '$pid' and identity = '$identity'");

			logger('12', 'Удален контакт: '.$title, $iduser1);

			event ::fire('person.delete', $args = [
				"pid"   => $pid,
				"autor" => $iduser1
			]);

			//todo: доработать сообщение с количеством записей
			$response['message'] = 'Успешно: Контакт удален. Также удалено '.$countHistory.' записи истории активностей. Удалено '.$countTask.' записей напоминаний. Удалено '.$countFiles.' файлов.
			';

			$response['result'] = 'Клиент удален';
			$response['data']   = $pid;

		}

		return $response;

	}

	/**
	 * Привязка сообщений к записям клиента по email
	 * И добавление в историю активностей
	 * Может применяться после добавления/обновления записи
	 *
	 * @param $mail_url - e-mail клиента
	 * @param $pid - id контакта
	 *
	 * @return void
	 * @throws Exception
	 */
	private function checkMailerEmail($mail_url, $pid): void {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$clid = getPersonData($pid, 'clid');

		if ($mail_url != '') {

			//require_once $rootpath."/modules/ymail/yfunc.php";

			$mail = yexplode(",", str_replace(";", ",", $mail_url));
			foreach ($mail as $email) {

				//print "SELECT id, mid FROM ".$sqlname."ymail_messagesrec WHERE email = '$email' and identity = '$identity'\n";

				$res = $db -> query("SELECT id, mid FROM ".$sqlname."ymail_messagesrec WHERE email = '$email' and identity = '$identity'");
				while ($data = $db -> fetch($res)) {

					//если pid не указан, то добавляем
					$db -> query("UPDATE ".$sqlname."ymail_messagesrec SET pid = IF(pid = 0, '$pid', pid), clid = IF(clid = 0, '$clid', clid) WHERE id = '$data[id]' and identity = '$identity'");

					//проверяем наличие письма в истории
					$hid = $db -> getOne("SELECT hid FROM ".$sqlname."ymail_messages WHERE id = '$data[mid]'") + 0;

					//print $hid."\n";

					if ($hid == 0) {
						Mailer ::putHistory((int)$data['mid']);
					}

				}

			}

		}

	}

	/**
	 * Проверка на дубли по 2-м параметрам
	 * - person
	 * - phone, mob
	 * - email
	 * Возвращает массив с тремя параметрами, в которых найдены дубли
	 * в которых ключ = pid, значение = совпадающий параметр
	 *
	 * @param       $pid
	 * @param array $params - параметры
	 *                      - nolog = 1 - результат не будет внесен в лог дублей
	 *                      - noNotify = true - уведомления Координаторам отключаем принудительно
	 *                      - multi = true - для проверки всей базы
	 *
	 * @return object
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function checkDouble($pid, array $params = []): object {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$fpath    = $this -> fpath;

		$isdouble = [];
		$doubleid = 0;

		//загрузим настройки из кэша
		$dbl         = json_decode(file_get_contents($rootpath."/cash/".$fpath."settings.checkdoubles.json"), true);
		$Fields      = $dbl['field'];
		$Coordinator = $dbl['Coordinator'];
		$Operator    = $dbl['Operator'];

		//если модуль не активен, то выходим
		if ($dbl['active'] != 'yes') {
			goto extp;
		}

		//смотрим, производилась ли проверка организации
		$exist = (int)$db -> getOne("SELECT COUNT(*) FROM ".$sqlname."doubles WHERE tip = 'person' and idmain = '$pid' AND status = 'no'") + 0;
		if ($exist > 0) {
			goto extp;
		}

		//для мультипроверки будем фильтровать по уже проверенным
		$multi = $params['multi'] ? "pid NOT IN (SELECT idmain FROM ".$sqlname."doubles WHERE tip = 'person' and status = 'no' AND identity = '$identity') AND" : "";

		//получаем данные по контакту
		$prsn = get_person_info($pid, 'yes');

		$title = $prsn['person'];
		$phone = yexplode(",", str_replace(";", ",", $prsn['tel']));
		$fax   = yexplode(",", str_replace(";", ",", $prsn['fax']));
		$mob   = yexplode(",", str_replace(";", ",", $prsn['mob']));
		$email = yexplode(",", str_replace(";", ",", $prsn['mail']));

		$tel = array_merge($phone, $fax, $mob);

		//ищем по имени
		//отключено, т.к. очень велика вероятность словить дубли

		$ids = [];

		if (in_array("person", (array)$Fields)) {

			$ta = yexplode(" ", $title);

			if ($title != '' && count((array)$ta) > 1) {
				$ids = $db -> getCol("
				SELECT pid 
				FROM ".$sqlname."personcat 
				WHERE 
					pid != '$pid' AND 
					$multi
					person = '$title' AND 
					identity = '$identity'
				ORDER BY pid
			");
			}

			foreach ($ids as $id) {
				$isdouble['title'][$id] = $title;
				$isdouble['pid'][]      = $id;
			}

		}

		//ищем по номеру телефона
		if (in_array("tel", (array)$Fields)) {
			foreach ($tel as $phone) {

				$phone = prepareMobPhone($phone);
				$ids   = [];

				if ($phone != '' && strlen($phone) > 5) {
					$ids = $db -> getCol("
				SELECT pid 
				FROM ".$sqlname."personcat 
				WHERE 
					pid != '$pid' AND 
					$multi
					(
						FIND_IN_SET('$phone', REPLACE(replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', ''), ';',',')) > 0 or
						FIND_IN_SET('$phone', REPLACE(replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', ''), ';',',')) > 0 or
						FIND_IN_SET('$phone', REPLACE(replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', ''), ';',',')) > 0
					) AND 
					identity = '$identity'
				ORDER BY pid
			");
				}

				foreach ($ids as $id) {
					$isdouble['phone'][$id] = $phone;
					$isdouble['pid'][]      = $id;
				}

			}
		}

		//ищем по email
		if (in_array("mail", (array)$Fields)) {
			foreach ($email as $mail) {

				$ids = [];

				if ($mail != '' && strlen($mail) > 5) {
					$ids = $db -> getCol("
				SELECT pid 
				FROM ".$sqlname."personcat 
				WHERE 
					pid != '$pid' AND 
					$multi
					FIND_IN_SET('$mail', REPLACE(
						replace(
							replace(
								replace(
									replace(
										replace(mail, '+', ''), '(', ''), ')', ''
									), ' ', ''
								), '-', ''
							), ';',','
						)
					) > 0 AND 
					identity = '$identity'
				ORDER BY pid
			");
				}

				foreach ($ids as $id) {
					$isdouble['email'][$id] = $mail;
					$isdouble['pid'][]      = $id;
				}

			}
		}

		$isdouble['id']  = $pid;
		$isdouble['pid'] = ( !empty($isdouble['pid']) ) ? array_unique($isdouble['pid']) : $isdouble['pid'];

		/**
		 * Если параметр 'nolog' не задан, то добавим в раздел найденных дублей
		 */
		if (!isset($params['nolog'])) {

			//добавляем только если такой записи нет в активных дублях и реально что-то найдено
			if ($exist == 0 && count((array)$isdouble['pid']) > 0) {

				$ida   = $isdouble['pid'];
				$ida[] = $pid;

				$db -> query("INSERT INTO ".$sqlname."doubles SET ?u", [
					"idmain"   => $pid,
					"tip"      => "person",
					"list"     => json_encode_cyr($isdouble),
					"ids"      => yimplode(",", $ida),
					"identity" => $identity
				]);
				$doubleid = $db -> insertID();

				$isdouble['id'] = $doubleid;

			}

		}

		//отправляем уведомление координаторам
		if ($dbl['CoordinatorNotify'] == 'yes' && !isset($params['noNotify']) && count((array)$isdouble['pid']) > 0) {

			$html = '
			<DIV style="width:600px; margin:0 auto; border:1px solid #ECEFF1; font-family: Tahoma,Arial,sans-serif;">
				<div style="font-size: 14px; color: #222; line-height: 18px; padding: 10px 10px;">
					<div>
						<div style="font-size: 14px; color: #222;">
							<b>Уведомление о найденном дубле:</b><br>
							<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
						</div>
						<div style="color:#222; font-size:12px; margin-top: 20px;">
							<div style="border-top:0 solid #ECEFF1; margin-bottom:10px;">
								<div style="padding: 5px;">
									Проверена запись <b>{{client}}</b> [ отв. {{user}} ]
								</div>
								<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
								<div style="padding: 5px;">
									При проверке записи были найдены следующие дубли:
									<ul>
										{{text}}
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div style="font-size:10px; padding:10px; border-top:1px solid #ECEFF1; color:#507192; text-align: center">Sended from CRM</div>
			</DIV>
			';

			$text = '';
			foreach ($isdouble['pid'] as $iclid) {

				$text .= '<li>'.current_person($iclid).' [ отв. '.current_user(getPersonData($iclid, 'iduser')).' ]</li>';

			}

			$html = str_replace([
				"{{client}}",
				"{{user}}",
				"{{text}}"
			], [
				getPersonData($pid, 'person'),
				current_user(getPersonData($pid, 'iduser')),
				$text
			], $html);

			$users  = array_unique($Coordinator);
			$iduser = array_shift($users);

			$to     = $db -> getOne("SELECT title FROM {$sqlname}user WHERE iduser = '$iduser'");
			$tomail = $db -> getOne("SELECT email FROM {$sqlname}user WHERE iduser = '$iduser'");

			$cc = [];
			foreach ($users as $iduser) {

				$cc[] = [
					'email' => $db -> getOne("SELECT email FROM {$sqlname}user WHERE iduser = '$iduser'"),
					'name'  => $db -> getOne("SELECT title FROM {$sqlname}user WHERE iduser = '$iduser'")
				];

			}

			$frommail = $_SERVER['SERVER_ADMIN'];

			mailto([
				$to,
				$tomail,
				'Уведомление CRM',
				$frommail,
				"Уведомление о новых дублях в CRM",
				$html,
				[],
				$cc
			]);

		}

		extp:

		$this -> isdouble = $isdouble;
		$this -> doubleid = $doubleid;

		return $this;

	}

	/**
	 * Слияние дублей
	 *
	 * @param       $id - запись, в которую будем сливать
	 * @param array $params - параметры
	 *                      - list - одномерный массив записей, которые будем вливать в главную
	 *                      - main - главная запись, в которую будемсливать остальные
	 *                      - more - доп.опции
	 *                      - newuser - назначить главную запись на сотрудника
	 *                      - merge - слить данные: телефоны, email
	 *                      - log - добавить в лог данные сливаемых записей
	 *                      - notify - уведомить сотрудников о слиянии
	 *
	 * @return string
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function mergeDouble($id, array $params = []): string {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$fpath    = $this -> fpath;

		$list = $params['list'];
		$more = $params['more'];
		$pid  = $params['main'];

		//загрузим настройки из кэша
		$dbl = json_decode(file_get_contents($rootpath."/cash/".$fpath."settings.checkdoubles.json"), true);

		//пользователи, которых будем уведомлять
		$users    = [];
		$userlist = [];

		$info = [];
		//$p = $f = $mo = $m = [];

		//удалим главную запись из списка записей
		$list = array_flip($list);               //Меняем местами ключи и значения
		unset ($list[$pid]);                     //Удаляем элемент массива
		$list = array_values(array_flip($list)); //Меняем местами ключи и значения и сбрасываем индексы

		if (count($list) == 0) {
			goto ext;
		}

		/**
		 * Сформируем массив данных для обновления главной записи
		 */

		if ($more['merge'] == 'yes') {

			//данные по основной записи
			$p  = (array)yexplode(",", str_replace(";", ",", getPersonData($pid, 'tel')));
			$f  = (array)yexplode(",", str_replace(";", ",", getPersonData($pid, 'fax')));
			$mo = (array)yexplode(",", str_replace(";", ",", getPersonData($pid, 'fax')));
			$m  = (array)yexplode(",", str_replace(";", ",", getPersonData($pid, 'mail')));

			$users[] = getPersonData($pid, 'iduser');

			//имена полей карточки клиента
			$fieldNames = $this -> fieldNames();

			/**
			 * массив с информацией о клиентах, которые будут влиты в главного
			 */
			foreach ($list as $ida) {

				$c        = $this ::info($ida);
				$listinfo = $c['person'];

				$userlist[$ida] = [
					"title" => $listinfo['person'],
					"user"  => current_user($listinfo['iduser'])
				];

				//$p  = array_merge( $p, yexplode( ",", str_replace( ";", ",", $listinfo[ 'tel' ] ) ) );

				$p1 = yexplode(",", str_replace(";", ",", $listinfo['tel']));
				foreach ($p1 as $p2) {
					$p[] = $p2;
				}

				//$f  = array_merge( $f, yexplode( ",", str_replace( ";", ",", $listinfo[ 'fax' ] ) ) );

				$f1 = yexplode(",", str_replace(";", ",", $listinfo['fax']));
				foreach ($f1 as $p2) {
					$f[] = $p2;
				}

				//$mo = array_merge( $mo, yexplode( ",", str_replace( ";", ",", $listinfo[ 'mob' ] ) ) );

				$mo1 = yexplode(",", str_replace(";", ",", $listinfo['mob']));
				foreach ($mo1 as $p2) {
					$mo[] = $p2;
				}

				//$m  = array_merge( $m, yexplode( ",", str_replace( ";", ",", $listinfo[ 'mail' ] ) ) );

				$m1 = yexplode(",", str_replace(";", ",", $listinfo['mail']));
				foreach ($m1 as $p2) {
					$m[] = $p2;
				}

				$values = $this -> Tags($listinfo);

				$info[$ida] = $fieldNames['title'].": ".$values['title']."\n";

				foreach ($fieldNames as $field => $name) {

					if ($field != 'title' && $values[$field] != '') {
						$info[$ida] .= $name.": ".$values[$field]."\n";
					}

				}

				$users[] = $listinfo['iduser'];

			}

			$p  = array_unique(array_map("prepareMobPhone", $p));
			$f  = array_unique(array_map("prepareMobPhone", $f));
			$mo = array_unique(array_map("prepareMobPhone", $mo));
			$m  = array_unique($m);

			$new = [];

			if (count($p) > 0) {
				$new['tel'] = implode(",", $p);
			}
			if (count($f) > 0) {
				$new['fax'] = implode(",", $f);
			}
			if (count($mo) > 0) {
				$new['mob'] = implode(",", $mo);
			}
			if (count($m) > 0) {
				$new['mail_url'] = implode(",", $m);
			}

			//метка, что больше не надо проверять
			$new['check'] = false;

			$this -> edit($pid, $new);

		}

		//переводим записи на главную запись
		foreach ($list as $ida) {

			//проходим Клиентов
			$db -> query("UPDATE {$sqlname}clientcat SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Историю
			$result = $db -> query("select * from ".$sqlname."history WHERE FIND_IN_SET('$ida', REPLACE(pid, ';',',')) > 0 and identity = '$identity'");
			while ($data = $db -> fetch($result)) {

				$pids = [];

				if ($data['pid'] > 0) {
					$pids = (array)yexplode(";", $data['pid']);
				}

				if (( $key = array_search($ida, $pids) ) !== false) {
					unset($pids[$key]);
				}

				if ($pid > 0) {
					$pids[] = $pid;
				}

				$db -> query("update ".$sqlname."history set pid = '".implode(";", $pids)."' where cid = '$data[cid]' and identity = '$identity'");

			}

			//проходим Напоминания
			$result = $db -> query("select * from ".$sqlname."tasks WHERE FIND_IN_SET('".$ida."', REPLACE(pid, ';',',')) > 0 and identity = '$identity'");
			while ($data = $db -> fetch($result)) {

				$pids = [];

				if ($data['pid'] > 0) {
					$pids = (array)yexplode(";", $data['pid']);
				}

				if (( $key = array_search($ida, $pids) ) !== false) {
					unset($pids[$key]);
				}

				if ($pid > 0) {
					$pids[] = $pid;
				}

				$db -> query("update ".$sqlname."tasks set pid = '".implode(";", $pids)."' where tid = '".$data['tid']."' and identity = '$identity'");

			}

			//проходим Звонки
			$db -> query("UPDATE {$sqlname}callhistory SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Сделки
			$db -> query("UPDATE {$sqlname}dogovor SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Заявки
			$db -> query("UPDATE {$sqlname}leads SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Обращения
			$db -> query("UPDATE {$sqlname}entry SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Группы
			$db -> query("UPDATE {$sqlname}grouplist SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Файлы
			$db -> query("UPDATE {$sqlname}file SET pid = '$pid' WHERE pid = '$ida'");

			//проходим Почту
			$db -> query("UPDATE {$sqlname}ymail_messagesrec SET pid = '$pid' WHERE pid = '$ida'");

			//удалим запись
			$this -> delete($ida);

		}

		//если задан новый сотрудник, то меняем
		if ($more['newuser'] > 0) {

			$this -> changeUser($pid, [
				"newuser" => $more['newuser'],
				"reason"  => ( $more['des'] != '' ) ? "Слияние дублей. ".$more['des'] : ''
			]);

		}
		//если нет, то добавляем текущего ответственного
		else {

			$more['newuser'] = getPersonData($pid, 'iduser');

		}

		ext:

		//пометим выполненным
		$dinfo = [
			"status"  => 'yes',
			"des"     => $more['des'],
			"idmain"  => $pid,
			//обязательно укажем, иначе запись может быть удалена
			"datumdo" => current_datumtime(),
			"iduser"  => $iduser1
		];
		$db -> query("UPDATE {$sqlname}doubles SET ?u WHERE id = '$id'", $dinfo);

		//добавим лог
		if ($more['log'] == 'yes') {
			addHistorty([
				"pid"      => $pid,
				"datum"    => current_datumtime(),
				"des"      => "Слияние дублей:\n\n".implode("\n\n", $info),
				"iduser"   => $iduser1,
				"tip"      => 'СобытиеCRM',
				"identity" => $identity
			]);
		}

		//отправляем уведомление
		if ($dbl['UserNotify'] == 'yes') {

			$html = '
			<DIV style="width:600px; margin:0 auto; border:1px solid #ECEFF1; font-family: Tahoma,Arial,sans-serif;">
				<div style="font-size: 14px; color: #222; line-height: 18px; padding: 10px 10px;">
					<div>
						<div style="font-size: 14px; color: #222;">
							<b>Уведомление о слиянии дублей:</b><br>
							<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
						</div>
						<div style="color:#222; font-size:12px; margin-top: 20px;">
							<div style="border-top:0 solid #ECEFF1; margin-bottom:10px;">
								<div style="padding: 5px;">
									Основной записью назначена запись: <b>{{client}}</b> [ отв. {{user}} ]
								</div>
								<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
								<div style="padding: 5px;">
									Произведено слияние следующих дублей записей:
									<ul>
										{{text}}
									</ul>
								</div>
								{{newuser}}
								{{comment}}
							</div>
						</div>
					</div>
				</div>
				<div style="font-size:10px; padding:10px; border-top:1px solid #ECEFF1; color:#507192; text-align: center">Sended from CRM</div>
			</DIV>
			';

			$text = '';
			foreach ($userlist as $clida => $item) {

				$text .= '<li>'.$item['title'].' [ отв. '.$item['user'].' ]</li>';

			}

			$html = str_replace([
				"{{client}}",
				"{{user}}",
				"{{text}}"
			], [
				getPersonData($pid, 'person'),
				current_user(getPersonData($pid, 'iduser')),
				$text
			], $html);

			if ($more['newuser'] > 0) {
				$html = str_replace("{{newuser}}", '
					<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
					<div style="padding: 5px;">
						Установлен новый ответственный: <b>'.current_user($more['newuser']).'</b>
					</div>
				', $html);
			}

			$html = str_replace("{{comment}}", '
				<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
				<div style="padding: 5px;">
					Комментарий: <b>'.nl2br($more['des']).'</b>
				</div>
			', $html);

			if ($more['newuser'] > 0) {
				array_unshift($users, $more['newuser']);
			}

			$users = array_unique($users);

			$iduser = array_shift($users);

			$to     = $db -> getOne("SELECT title FROM {$sqlname}user WHERE iduser = '$iduser'");
			$tomail = $db -> getOne("SELECT email FROM {$sqlname}user WHERE iduser = '$iduser'");

			$cc = [];
			foreach ($users as $iduser) {

				$cc[] = [
					'email' => $db -> getOne("SELECT email FROM {$sqlname}user WHERE iduser = '$iduser'"),
					'name'  => $db -> getOne("SELECT title FROM {$sqlname}user WHERE iduser = '$iduser'")
				];

			}

			$frommail = $_SERVER['SERVER_ADMIN'];

			mailto([
				$to,
				$tomail,
				'Уведомление CRM',
				$frommail,
				"Уведомление о слиянии дублей в CRM",
				$html,
				[],
				$cc
			]);

		}

		return 'ok';

	}

	/**
	 * Вспомогательная функция
	 * Возвращает массив имен полей Клиента
	 *
	 * @return array
	 */
	public function fieldNames(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		//массив имен полей
		return $db -> getIndCol('fld_name', "select fld_title, fld_name from ".$sqlname."field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order");

	}

	/**
	 * Информация о контакте
	 *
	 * @param $pid - id контакта
	 *
	 * @return array - ответ
	 *
	 * good result - Возвращается массив данных по контакту
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *          403 - Контакт с указанным pid не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - pid клиента
	 *
	 * Example:
	 * ```php
	 * $Person = \Salesman\Person::info($pid);
	 * ```
	 */
	public static function info($pid): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		if ((int)$pid > 0) {

			if (getPersonData($pid, "person") != '') {

				$response['person'] = get_person_info($pid, 'yes');

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Контакт с указанным pid не найден в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - pid клиента";

		}

		return $response;

	}

	/**
	 * Возвращает готовые данные по клиенту
	 * в которых идентификаторы заменены конечными значениями
	 *
	 * @param $params - входные данные по клиенту в виде массива, который выдает функция info
	 *
	 * @return array - ключ = имя поля, значение = конечное значение
	 */
	public function Tags($params): array {

		$des = [];

		foreach ($params as $key => $value) {

			switch ($key) {

				case 'type':

					$des[$key] = strtr($value, [
						"client"     => "Клиент Юр.лицо",
						"person"     => "Клиент Физ.лицо",
						"partner"    => "Партнер",
						"contractor" => "Поставщик",
						"concurent"  => "Конкурент"
					]);

					break;
				case 'idcategory':

					$des[$key] = get_client_category($value);

					break;
				case 'clientpath':

					$des[$key] = current_clientpathbyid($value);

					break;
				case 'territory':

					$des[$key] = current_territory($value);

					break;
				case 'head_clid':

					$des[$key] = current_client($value);

					break;
				case 'pid':

					$des[$key] = current_person($value);

					break;
				case 'iduser':

					$des[$key] = current_user($value);

					break;
				default:

					$des[$key] = $value;

					break;
			}

		}

		return $des;

	}

	/**
	 * Смена ответственного
	 *
	 * @param       $pid - id контакта
	 * @param array $params - параметры
	 *                      - newuser - id пользователя, который будет ответственным
	 *                      - reason - причина
	 *
	 * @return array
	 *          result = Сделано
	 *
	 * Пример:
	 * ```php
	 * $Person = \Salesman\Person::changeUser($pid,$params);
	 * ```
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function changeUser($pid, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;

		$newuser = $params["newuser"];
		$olduser = getPersonData($pid, 'iduser');

		$reazon = ( $params['reason'] == '' ) ? 'не указано' : $params['reason'];

		$post['pid'] = $pid;

		if ($hooks) {
			$hooks -> do_action("person_change_user", $post);
		}

		$err = [];

		try {

			$db -> query("update ".$sqlname."personcat set ?u where pid = '$pid' and identity = '$identity'", ["iduser" => $newuser]);

		}
		catch (Exception $e) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		addHistorty([
			"pid"      => $pid,
			"datum"    => current_datumtime(),
			"des"      => "Смена Ответственного: ".current_user($olduser)."&rarr;".current_user($newuser).". Причина: $reazon. Изменил: ".current_user($iduser1),
			"iduser"   => $iduser1,
			"tip"      => 'СобытиеCRM',
			"identity" => $identity
		]);

		sendNotify('send_person', $params = [
			"pid"     => $pid,
			"title"   => getPersonData($pid, 'person'),
			"iduser"  => $newuser,
			"notice"  => 'yes',
			"comment" => $reazon
		]);

		event ::fire('person.change.user', $args = [
			"pid"     => $pid,
			"autor"   => $iduser1,
			"olduser" => $olduser,
			"newuser" => $newuser,
			"comment" => $reazon
		]);

		return $response = [
			'result' => "Сделано",
			"error"  => $err
		];

	}

	/**
	 * Обработка дубля без слияния
	 *
	 * @param       $id
	 * @param array $params
	 *
	 * @return string
	 */
	public function ignoreDouble($id, array $params = []): string {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;
		$iduser1 = $this -> iduser1;

		$more = $params['more'];

		//пометим выполненным
		$dinfo = [
			"status"  => 'ign',
			"des"     => $more['des'],
			"datumdo" => current_datumtime(),
			"iduser"  => $iduser1
		];
		$db -> query("UPDATE {$sqlname}doubles SET ?u WHERE id = '$id'", $dinfo);

		return 'ok';

	}

	public function card(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		global $ymEnable;

		$otherSettings = $this -> otherSettings;
		$settingsUser  = $this -> settingsUser;
		$iduser1       = $this -> iduser1;

		$valuta      = $GLOBALS['valuta'];
		$fieldsNames = $GLOBALS['fieldsNames'];
		$fieldsOn    = $GLOBALS['fieldsOn'];
		$isMobile    = $GLOBALS['isMobile'];

		$clid = (int)$params['clid'];

		$list = [];

		$fieldsView = ['tel','mob','mail','clientpath','loyalty','rol'];

		if( !empty($params['fields']) ){
			$fieldsView = $params['fields'];
		}

		$user = User ::info($iduser1);

		//Основной контакт клиента
		$main = (int)getClientData($clid, 'pid');

		$extentionField = $db -> getOne("SELECT fld_name FROM {$sqlname}field WHERE fld_tip = 'person' AND fld_on = 'yes' AND fld_title IN ('Добавочный','Extention','Доб.номер') AND identity = '$identity'");

		if( !empty($extentionField) ){

			$fieldsViewExt = [];

			foreach ($fieldsView as $i => $v){

				$fieldsViewExt[] = $v;

				if($v == 'tel'){
					$fieldsViewExt[] = $extentionField;
				}

			}

			$fieldsView = $fieldsViewExt;

		}

		$inputs = [];
		//$res    = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip = 'person' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");

		$res    = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip = 'person' AND fld_on = 'yes' AND identity = '$identity' AND fld_name IN (".yimplode(",", $fieldsView, "'").") ORDER BY FIELD(fld_name, ".yimplode(",", $fieldsView, "'")."), fld_order");
		while ($fld = $db -> fetch($res)) {

			$inputs["pc.".$fld['fld_name']] = [
				"field" => $fld['fld_name'],
				"title" => $fld['fld_title'],
				"type"  => $fld['fld_temp'],
			];

		}

		$result = $db -> getAll("
			SELECT 
				pc.pid,
				-- pc.uid,
				pc.person,
				pc.ptitle,
				pc.tel,
				pc.mob,
				pc.mail,
				pc.rol,
				pc.clientpath,
				pc.loyalty,
				lt.title as loyaltyName,
				lt.color as loyaltyColor,
				pc.social,
				".( !empty(array_keys($inputs)) ? yimplode(",", array_keys($inputs))."," : "" )."
				pc.date_create,
				pc.date_edit,
				pc.iduser,
				pc.creator,
				pc.editor,
				cp.name as clientpathName
			FROM {$sqlname}personcat `pc`
				LEFT JOIN {$sqlname}clientpath `cp` ON cp.id = pc.clientpath
				LEFT JOIN {$sqlname}loyal_cat `lt` ON lt.idcategory = pc.loyalty
			WHERE 
				pc.clid = '$clid' AND 
				pc.identity = '$identity' 
			ORDER BY FIELD(pc.pid,'$main') DESC, pc.person
		");

		foreach ($result as $row) {

			$btn = [];

			$accesse =
				( get_accesse($clid) == "yes" && $user['result']['tip'] != 'Поддержка продаж' ) ||
				(
					get_accesse($clid, 0, 0) == "yes" &&
					$user['result']['tip'] == 'Поддержка продаж' && $row['iduser'] == $iduser1
				);

			if ($accesse) {
				$btn['task'] = true;
				$btn['card'] = true;
			}
			if ($user['result']['rights']['personEdit'] == 'on' && $accesse) {
				$btn['edit'] = true;
			}
			if ($user['result']['rights']['personDelete'] == 'on') {
				$btn['delete'] = true;
			}

			$r = [
				"pid"            => (int)$row['pid'],
				"person"         => $row['person'],
				"ptitle"         => $row['ptitle'],
				"rol"            => $row['rol'],
				"rolf"           => yexplode(";", $row['rol']),
				"tel"            => $row['tel'],
				//"telf"           => preparePhoneData($row['tel'], $clid, (int)$row['pid']),
				"mob"            => $row['mob'],
				//"mobf"           => preparePhoneData($row['mob'], $clid, (int)$row['pid']),
				"mail"           => $row['mail'],
				//"mailf"          => prepareEmailData($row['mail']),
				"clientpath"     => (int)$row['clientpath'],
				//"clientpathName" => $row['clientpathName'],
				"loyalty"        => (int)$row['loyalty'],
				//"loyaltyName"    => $row['loyaltyName'],
				//"loyaltyColor"   => $row['loyaltyColor'],
				//"social"         => self ::parseSocial($row['social']),
				"isMain"         => (int)$row['pid'] == $main,
				"accesse"        => $accesse,
				"rights"         => $btn
			];

			foreach ($inputs as $field) {

				if (empty($row[$field['field']])) {
					continue;
				}

				$x = [
					"field"      => $field['title'],
					"value"      => $row[$field['field']],
					"text"       => $row[$field['field']],
					"html"       => nl2br($row[$field['field']]),
					"format"     => $field['type'] == 'datum' ? format_date_rus_name($row[$field['field']]) : NULL,
					"isSimple"   => true
				];

				if( in_array($field['field'], ['tel','mob']) ){
					$x['format'] = preparePhoneData($row[ $field['field'] ], $clid, (int)$row['pid']);
					$x['isPhone'] = true;
					$x['isSimple'] = NULL;
				}
				elseif($field['field'] == 'mail'){
					$x['format'] = prepareEmailData($row[ $field['field'] ], $clid, (int)$row['pid']);
					$x['isEmail'] = true;
					$x['isSimple'] = NULL;
				}
				elseif($field['field'] == 'clientpath'){
					$x['text'] = $row['clientpathName'];
					$x['html'] = $row['clientpathName'];
				}
				elseif($field['field'] == 'loyalty'){
					$x['text'] = $row['loyaltyName'];
					$x['html'] = $row['loyaltyName'];
					$x['color'] = $row['loyaltyColor'];
				}
				elseif($field['field'] == 'social'){
					$x['social'] = self ::parseSocial($row['social']);
					$x['isSimple'] = NULL;
				}
				elseif($field['field'] == 'rol'){
					$x['format'] = yexplode(";", $row['rol']);
					$x['isSimple'] = NULL;
					$x['isArray'] = true;
				}

				if( $field['type'] == 'datetime' ){
					$x['formattime'] = modifyDatetime($row[$field['field']], ["format" => "d.m.Y H:s"]);
					$x['isDateTime'] = true;
				}
				elseif( $field['type'] == 'datum' ){
					$x['isDate'] = true;
				}
				elseif( $field['type'] == 'adres' ){
					$x['isAddress'] = true;
				}
				elseif( in_array($field['type'], ['inputlist','multiselect']) ){
					$x['format'] = yexplode(",", $row[$field['field']]);
					$x['isArray'] = true;
					$x['isSimple'] = NULL;
				}

				$r['inputs'][] = $x;

			}

			$list[] = $r;

		}

		return [
			"clid"        => (int)$params['clid'],
			"list"        => $list,
			//"user"        => $user['result'],
			"inputs"      => $inputs,
			"fieldsNames" => $fieldsNames['person'],
			"iduser1"     => $iduser1,
			"isMobile"    => $isMobile
		];

	}

	/**
	 * Парсит строку с данными социальных сетей и возвращает массив с параметарми
	 * @param string|NULL $str
	 * @return array[]|null[]
	 */
	public static function parseSocial(string $str = NULL): array {

		$soc = explode(';', (string)$str);

		return [
			"blog"     => !empty($soc[0]) ? [
				"isblog" => true,
				"type"   => "blog",
				"name"   => "Блог",
				"value"  => $soc[0],
				"icon"   => "icon-globe broun"
			] : NULL,
			"site"     => !empty($soc[1]) ? [
				"issite" => true,
				"type"   => "site",
				"name"   => "Сайт",
				"value"  => $soc[1],
				"icon"   => "icon-globe broun"
			] : NULL,
			"twitter"  => !empty($soc[2]) ? [
				"istwitter" => true,
				"type"      => "twitter",
				"name"      => "Twitter",
				"value"     => $soc[2],
				"icon"      => "icon-twitter blue"
			] : NULL,
			"icq"      => !empty($soc[3]) ? [
				"isicq" => true,
				"type"  => "icq",
				"name"  => "ICQ",
				"value" => $soc[3],
				"icon"  => "icon-cog-1 green"
			] : NULL,
			"skype"    => !empty($soc[4]) ? [
				"isskype" => true,
				"type"    => "skype",
				"name"    => "Skype",
				"value"   => $soc[4],
				"icon"    => "icon-skype green"
			] : NULL,
			"gplus"    => !empty($soc[5]) ? [
				"isgplus" => true,
				"type"    => "gplus",
				"name"    => "Google+",
				"value"   => $soc[5],
				"icon"    => "icon-gplus-squared red"
			] : NULL,
			"facebook" => !empty($soc[6]) ? [
				"isfacebook" => true,
				"type"       => "facebook",
				"name"       => "Facebook",
				"value"      => $soc[6],
				"icon"       => "icon-facebook-squared blue"
			] : NULL,
			"vk"       => !empty($soc[7]) ? [
				"isvk"  => true,
				"type"  => "vk",
				"name"  => "VK",
				"value" => $soc[7],
				"icon"  => "icon-vkontakte blue"
			] : NULL,
		];

	}

}