<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2021 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2021.x           */
/* ============================ */

namespace Salesman;

use event;
use Exception;

/**
 * Класс для работы с объектом Клиент
 *
 * Class Client
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example:
 *
 * ```php
 * $Client  = new Salesman\Client();
 * $result = $Client -> add($params);
 * $clid = $result['data'];
 * ```
 */
class Client {

	//public $response = [];
	public $isdouble = [];
	public $doubleid;

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

	public $bankInfoField = [
		'castUrName',
		'castInn',
		'castKpp',
		'castBank',
		'castBankKs',
		'castBankRs',
		'castBankBik',
		'castOkpo',
		'castOgrn',
		'castDirName',
		'castDirSignature',
		'castDirStatus',
		'castDirStatusSig',
		'castDirOsnovanie',
		'castUrAddr'
	];

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
		//require_once $rootpath."/vendor/autoload.php";

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

		date_default_timezone_set($this -> tmzone);

	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	public static function getFiledType($field): string {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$temp = $db -> getOne("SELECT fld_temp FROM {$sqlname}field WHERE fld_tip = 'client' and fld_name = '$field' and identity = '$identity'");

		return ( $temp ) ? : "";

	}

	/**
	 * Инормация о клиенте
	 *
	 * @param int $clid - идентификатор записи клиента
	 *
	 * @return array - массив с результатами
	 * - client
	 *
	 *      - int **clid** - id клиента
	 *      - str **uid** - идентификатор из внешних систем
	 *      - str **clientUID** - идентификатор из внешних систем
	 *      - int **iduser** - аккаунт-менеджер
	 *      - str **title** - название клиента
	 *      - str **des** - описание
	 *      - int **idcategory** - отрасль, id
	 *      - str **category** - отрасль, название
	 *      - str **phone** - телефон
	 *      - str **fax** - факс
	 *      - str **site_url** - сайт
	 *      - str **mail_url** - почта
	 *      - str **address** - адрес
	 *      - int **pid** - основной контакт, id
	 *      - str **fav** - признак ключевого клиента yes|no
	 *      - str **trash** - признак организации в корзине yes|no
	 *      - int **head_clid** - головная организация, id
	 *      - str **head** - головная организация
	 *      - str **scheme** - схема принятия решений
	 *      - str **tip_cmr** - тип отношений
	 *      - str **relation** - тип отношений
	 *      - int **territory** - территория, id
	 *      - str **territoryname** - территория
	 *      - int **creatorID** - пользователь, создавший организацию, iduser
	 *      - str **creator** - пользователь, создавший организацию
	 *      - date **date_create** - дата создания
	 *      - int **editorID** - пользователь, изменивший организацию, iduser
	 *      - str **editor** - пользователь, изменивший организацию
	 *      - date **date_edit** - дата изменения
	 *      - array **dostup** - массив iduser, у которых есть доступ к карточке
	 *      - str **clientpath** - источник клиента
	 *      - int **clientpath2** - источник клиента
	 *      - str **type** = client - тип клиента
	 *      - str **priceLevel** = price_2 - уровень цен
	 *      - str **input1...xxx** - дополнительные поля
	 *
	 *      - array **recv** - реквизиты клиента
	 *
	 *          - int **clid** - id клиента
	 *          - str **castUrName** - полное юр.название
	 *          - str **castUrNameShort** - краткое юр.название
	 *          - str **castName** - краткое название
	 *          - str **castInn** - ИНН
	 *          - str **castKpp** - КПП
	 *          - str **castBank** - название банка
	 *          - str **castBankKs** - корреспондирующий счет
	 *          - str **castBankRs** - расчетный счет
	 *          - str **castBankBik** - БИК банка
	 *          - str **castOkpo** - ОКПО
	 *          - str **castOgrn** - ОГРН
	 *          - str **castDirName** - ФИО Директора, родит.падеж ( Андреева Владислава Германовича )
	 *          - str **castDirSignature** - Подпись Директора ( Андреев В.Г. )
	 *          - str **castDirStatus** - Должность Директора, родит.падеж ( Генерального директора )
	 *          - str **castDirStatusSig** - Должность Директора ( Генеральный директор )
	 *          - str **castDirOsnovanie** - Устава
	 *          - str **castUrAddr** - юр. адрес
	 *          - str **castFacAddr** - фактический адрес
	 *          - str **castType** - client ( person, parnter, contractor, concurent )
	 *
	 *      - array **person** - данные основного контакта
	 *
	 *          - int **pid** - id основного контакта
	 *          - str **title** - ФИО
	 *          - str **post** - Должность
	 *          - str **phone** - номер телефона
	 *          - str **mob** - номер мобильного
	 *          - str **email** - email
	 *
	 * возвращает массив или ошибку:
	 *
	 * good result
	 *         - result = Ok
	 *         - client
	 *              - recv
	 *         - person
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example:
	 * ```php
	 * $Client = \Salesman\Client::info($clid);
	 * ```
	 */
	public static function info(int $clid = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		if ($clid > 0) {

			if (getClientData($clid, "title") != '') {

				$response['result']             = 'Ok';
				$response['client']             = get_client_info($clid, 'yes');
				$response['client']['recv']     = get_client_recv($clid, 'yes');
				$response['client']['bankinfo'] = $response['client']['recv'];

				$ruids = UIDs ::info(["clid" => $clid]);
				if ($ruids['result'] != 'Error') {
					$response['client']['uids'] = $ruids['data'];
				}

				if ($response['client']['pid'] > 0) {
					$response['person'] = personinfo($response['client']['pid']);
				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 403;
				$response['error']['text'] = "Клиент с указанным clid не найден в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid клиента";

		}

		return $response;

	}

	/**
	 * Добавление клиента
	 *
	 * @param array $params - массив данных ключ = значение (см. БД {PREFIX}clientcat)
	 *                      - **recv** - реквизиты
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = clid
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      - 405 - Отсутствуют парамтеры (пустой массив $param)
	 *      - 406 - Найден существующий клиент - {ClientName} ({clid}). Запрос отклонен.
	 *      - 407 - Отсутствуют параметры - Название клиента
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> add($params);
	 * ```
	 *
	 */
	public function add(array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;

		if ($hooks) {
			$params = $hooks -> apply_filters("client_addfilter", $params);
		}

		$q = '';

		$client  = [];
		$exludef = ['recv'];

		//$fields = clientFields();
		$bankInfoField = [
			'castUrName',
			'castInn',
			'castKpp',
			'castBank',
			'castBankKs',
			'castBankRs',
			'castBankBik',
			'castOkpo',
			'castOgrn',
			'castDirName',
			'castDirSignature',
			'castDirStatus',
			'castDirStatusSig',
			'castDirOsnovanie',
			'castUrAddr',
			'castUrNameShort'
		];

		if ((int)$params['iduser'] == 0) {
			$params['iduser'] = ( $params['user'] == '' ) ? $iduser1 : current_userbylogin($params['user']);
		}

		$creator = (int)$params['creator'];

		// если пришла строка, то преобразуем её в массив
		if (!is_array($params['phone']) && $params['phone'] != '') {

			$params['phone'] = yexplode(";", str_replace(",", ";", $params['phone']));

		}

		if (is_array($params['phone'])) {

			$xq = [];

			foreach ($params['phone'] as $phone) {

				$phone = mb_substr(prepareMobPhone($phone), 1);

				$xq[] = " replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' ";

			}

			if (!empty($xq)) {
				$q .= " and (".yimplode(" OR ", $xq).")";
			}

		}

		if ($params['mail_url'] != '') {
			$q .= " and mail_url LIKE '%".clean($params['mail_url'])."%'";
		}

		$clid = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE title = '".clientFormatTitle($params['title'])."' $q and identity = '$identity'");

		//print $db -> lastQuery();

		if ($clid > 0) {

			$response['result']        = 'Error';
			$response['exists']        = $clid;
			$response['error']['code'] = 304;
			$response['error']['text'] = "Найден существующий клиент - ".current_client($clid)." (clid = $clid). Запрос отклонен.";

		}

		//проверка, что есть название клиента
		elseif ($params['title'] != '' && (int)$clid == 0) {

			$params['type'] = $params['type'] ?? 'client';

			if (is_array($params['phone'])) {
				$p = array_map(static function($a){
					return preparePhone($a);
				}, $params['phone']);
				$params['phone'] = yimplode(",", $p);
			}

			$fields[] = 'type';

			//сформируем массив полей, включенных в настройках
			$res = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order");
			while ($data = $db -> fetch($res)) {

				if (!in_array($data['fld_name'], $exludef)) {
					$fields[] = $data['fld_name'];
				}

				//если прилетел массив, то преобразуем его в строку
				if (is_array($params[$data['fld_name']]) && !in_array($data['fld_name'], $exludef)) {

					$params[$data['fld_name']] = trim(yimplode(", ", $params[$data['fld_name']]));

				}

			}

			$fields[] = 'uid';

			foreach ($params as $key => $value) {

				if (in_array($key, $fields)) {

					switch ($key) {
						case "type":
							$value = ( $value == '' ) ? 'client' : $value;
							break;
						case "title":
							$value = clientFormatTitle($value);
							break;
						case "idcategory":
							$value = ( is_numeric($value) ) ? (int)$value : getClientCategory($value);
							break;
						case "clientpath":
							$value = ( is_numeric($value) ) ? (int)$value : getClientpath($value);
							break;
						case "territory":
							$value = ( is_numeric($value) ) ? (int)$value : getClientTerritory(untag($value));
							break;
						case "tip_cmr":
							//$value = (is_numeric( $value )) ? getClientRelation( untag( $value ) ) : (int)$value;
							$value = ( is_numeric($value) ) ? getClientRelation(untag($value)) : untag($value);
							break;
						default:

							// у этого поля может быть шаблон
							// поэтому надо сравнить текст с шаблоном
							// и если они равны, то поле игнорируем
							if (self ::getFiledType($key) == 'textarea' && $value != NULL) {

								// шаблон
								$ftpl = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_tip='client' AND fld_name = '$key' AND identity = '$identity'");

								$value = ( strcasecmp($ftpl, $value) != 0 ) ? fieldClean($value) : NULL;

							}
							elseif (self ::getFiledType($key) != 'datetime') {
								$value = fieldClean($value);
							}
							else {

								$value = str_replace([
									"Z",
									"T"
								], [
									":00",
									" "
								], $value);

							}

							break;

					}

					$client[$key] = $value;

				}

			}

			//формируем реквизиты
			$binfo    = [];
			$bankinfo = $params['recv'];

			//print_r($bankinfo);
			//print_r($bankInfoField);

			foreach ($bankInfoField as $value) {
				$binfo[] = clean_all($bankinfo[$value]);
			}

			$recv = implode(";", $binfo);

			$client['clientpath'] = ( isset($client['clientpath']) ) ? (int)$client['clientpath'] : (int)$GLOBALS['relDefault'];
			//$client['uid']  = (isset($params['uid'])) ? $params['uid'] : 0;

			if (!empty($client)) {

				$client['date_create'] = ( isset($params['date_create']) && strtotime($params['date_create']) != '' ) ? date('Y-m-d H:i:s', strtotime($params['date_create'])) : current_datumtime();
				$client['creator']     = $creator > 0 ? $creator : $iduser1;
				$client['identity']    = $identity;

				try {

					$db -> query("INSERT INTO {$sqlname}clientcat SET ?u", arrayNullClean($client));
					$clid = $db -> insertId();

					$response['result'] = 'Успешно';
					$response['data']   = $clid;

					$client['clid'] = $clid;

					if ($hooks) {
						$hooks -> do_action("client_add", $post, $client);
					}

					//запись в историю активности
					addHistorty([
						"iduser"   => (int)$iduser1,
						"clid"     => (int)$clid,
						"datum"    => current_datumtime(),
						"des"      => "Добавлен клиент",
						"tip"      => "СобытиеCRM",
						"identity" => $identity
					]);

					//если указаны реквизиты - добавляем
					if ($recv != '') {
						$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", ["recv" => $recv]);
					}

					/**
					 * Отправка уведомлений
					 */
					//if ( $params['iduser'] != $iduser1 ) {

					sendNotify('new_client', [
						"clid"   => (int)$clid,
						"title"  => untag($params['title']),
						"iduser" => (int)$params['iduser'],
						"notice" => 'yes'
					]);

					/**
					 * Уведомления
					 */
					//require_once "Notify.php";

					$arg = [
						"clid"   => (int)$clid,
						"title"  => untag($params['title']),
						"iduser" => (int)$params['iduser'],
						"notice" => 'yes'
					];
					Notify ::fire("client.add", (int)$iduser1, $arg);

					//}

					if ((int)$params['iduser'] != (int)$iduser1) {

						sendNotify('send_client', [
							"clid"   => (int)$clid,
							"title"  => untag($params['title']),
							"iduser" => (int)$params['iduser'],
							"notice" => 'yes'
						]);

						/**
						 * Уведомления
						 */
						//require_once "Notify.php";
						$arg = [
							"clid"   => (int)$clid,
							"title"  => untag($params['title']),
							"iduser" => (int)$params['iduser'],
							"notice" => 'yes'
						];
						Notify ::fire("client.changeuser", (int)$iduser1, $arg);

					}

					/**
					 * Запуск события
					 */
					event ::fire('client.add', [
						"clid"  => (int)$clid,
						"autor" => (int)$iduser1,
						"user"  => (int)$params['iduser']
					]);

					/**
					 * Проходим письма в почтовике и присоединим созданный Клиент/Контакт
					 * если в параметрах указан Email
					 */
					if ($client['mail_url'] != '') {

						$mail = yexplode(",", str_replace(";", ",", (string)$client['mail_url']));
						foreach ($mail as $email) {

							//если clid не указан, то добавляем
							$this -> checkMailerEmail($email, (int)$clid);

						}

					}

					//проверка дублей
					$this -> checkDouble((int)$clid);

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 402;
				$response['error']['text'] = "Отсутствуют параметры";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 402;
			$response['error']['text'] = "Отсутствуют параметры - Название клиента";

		}

		return $response;

	}

	/**
	 * Изменение информации по клиенту
	 * Обновляет только указанные в массиве $params поля
	 *
	 * @param int $clid - идентификатор записи клиента
	 * @param array $params - массив данных ключ = значение (см. БД {PREFIX}clientcat)
	 *                        - **recv** - реквизиты
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = clid
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      - 403 - Клиент с указанным clid не найден в пределах аккаунта
	 *      - 405 - Отсутствуют параметры - clid клиента
	 *
	 * @throws Exception
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> update($clid, $params);
	 * ```
	 */
	public function update(int $clid = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;
		//$clid = $clid;

		if ($hooks) {
			$params = $hooks -> apply_filters("client_editfilter", $params);
		}

		//print_r($params);

		$newParams = $oldParams = $client = [];
		$mes       = [];
		$response  = [];

		$bankInfoField = [
			'castUrName',
			'castInn',
			'castKpp',
			'castBank',
			'castBankKs',
			'castBankRs',
			'castBankBik',
			'castOkpo',
			'castOgrn',
			'castDirName',
			'castDirSignature',
			'castDirStatus',
			'castDirStatusSig',
			'castDirOsnovanie',
			'castUrAddr',
			'castUrNameShort'
		];
		$bankinfo      = $params['recv'];

		//$fields = clientFields();
		//$clidd   = intval($clid);
		$exludef = ['recv'];

		$s = ( isset($params['uid']) ) ? " OR uid = '".untag($params['uid'])."'" : "";

		if (!isset($clid)) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid клиента";

			return $response;

		}

		//проверка принадлежности clid к данному аккаунту
		$clid = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE (clid = '$clid' $s) and identity = '$identity'");

		if ($clid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Клиент с указанным clid не найден в пределах аккаунта";

			return $response;

		}

		$fields = [];

		$res = $db -> getAll("select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order");
		foreach ($res as $da) {

			if (!in_array($da['fld_name'], $exludef)) {
				$fields[] = $da['fld_name'];
			}

			//если прилетел массив, то преобразуем его в строку
			if (is_array($params[$da['fld_name']]) && !in_array($da['fld_name'], $exludef)) {

				$params[$da['fld_name']] = trim(yimplode(", ", $params[$da['fld_name']]));

			}

		}

		$fields[] = 'uid';
		$fields[] = 'type';

		foreach ($params as $key => $value) {

			if (in_array($key, $fields) && $key != 'recv') {

				switch ($key) {
					case "type":
						$value = ( $value == '' ) ? 'client' : untag($value);
						break;
					case "title":
						$value = clientFormatTitle($value);
						break;
					case "idcategory":
						$value = ( is_numeric($value) ) ? (int)$value : getClientCategory($value);
						break;
					case "clientpath":
						$value = ( is_numeric($value) ) ? (int)$value : getClientpath($value);
						break;
					case "territory":
						$value = ( is_numeric($value) ) ? (int)$value : getClientTerritory(untag($value));
						break;
					case "tip_cmr":
						$value = ( is_numeric($value) ) ? getClientRelation(untag($value)) : $value;
						break;
					default:

						// у этого поля может быть шаблон
						// поэтому надо сравнить текст с шаблоном
						// и если они равны, то поле игнорируем
						if (self ::getFiledType($key) == 'textarea' && $value != NULL) {

							// шаблон
							$ftpl = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_tip='client' AND fld_name = '$key' AND identity = '$identity'");

							$value = ( strcasecmp($ftpl, $value) != 0 ) ? fieldClean($value) : NULL;

						}
						elseif (self ::getFiledType($key) != 'datetime') {
							$value = fieldClean($value);
						}
						else {

							$value = str_replace([
								"Z",
								"T"
							], [
								":00",
								" "
							], $value);

						}

						break;
				}

				$client[$key] = $value;

			}

		}

		$clientOld               = get_client_info($clid, 'yes');
		$clientOld['clientpath'] = $clientOld['clientpath2'];

		foreach ($client as $key => $value) {

			$newParams[$key] = $value;          //массив новых параметров
			$oldParams[$key] = $clientOld[$key];//массив старых параметров

		}

		$client['date_edit'] = current_datumtime();
		$client['editor']    = (int)$iduser1;

		$log = doLogger('clid', $clid, $newParams, $oldParams);

		//если есть измененнные данные, то обновляем
		if ($log != 'none' && !empty($client)) {

			try {

				$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", $client);

				$mes[]            = 'Успешно';
				$response['data'] = $clid;//запись в историю активности

				$client['clid'] = $clid;

				if ($hooks) {
					$hooks -> do_action("client_edit", $post, $client);
				}

				if ((int)$params['iduser'] != (int)$iduser1) {

					sendNotify('send_client', [
						"clid"   => $clid,
						"title"  => untag($params['title']),
						"iduser" => $params['iduser'],
						"notice" => 'yes'
					]);

				}

				/**
				 * Уведомления
				 */
				//require_once "Notify.php";
				$arg = [
					"clid"   => $clid,
					"title"  => untag($params['title']),
					"iduser" => $params['iduser'],
					"log"    => $log,
					"notice" => 'yes'
				];
				Notify ::fire("client.edit", (int)$iduser1, $arg);


				/**
				 * Активность
				 */
				$hid             = addHistorty([
					"iduser"   => (int)$iduser1,
					"clid"     => $clid,
					"datum"    => current_datumtime(),
					"des"      => $log,
					"tip"      => "ЛогCRM",
					"untag"    => "no",
					"identity" => $identity
				]);
				$response['hid'] = $hid;//запись в историю активности


				/**
				 * Событие
				 */
				$diff2 = array_diff_ext($oldParams, $newParams);
				event ::fire('client.edit', [
					"clid"     => $clid,
					"autor"    => $iduser1,
					"user"     => $params['iduser'],
					"newparam" => $diff2
				]);

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				return $response;

			}

		}
		else {

			$mes[]            = 'Данные корректны, но идентичны имеющимся';
			$response['data'] = $clid;

		}

		//получим имеющиеся реквизиты клиента
		$currentBinfo = get_client_recv($clid, "yes");

		//формируем реквизиты
		$binfo = [];

		//print_r($bankinfo);

		foreach ($bankInfoField as $value) {

			$binfo[] = ( isset($bankinfo[$value]) ) ? clean_all($bankinfo[$value]) : $currentBinfo[$value];

		}

		$recv = implode(";", $binfo);

		//если указаны реквизиты - добавляем
		if (!empty((array)$params['recv']) && isset($params['recv'])) {

			$hooks -> do_action("client_change_recvisites", $post);

			$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", ["recv" => $recv]);
			$mes[] = 'Реквизиты обновлены';

			event ::fire('client.change.recv', [
				"clid"  => $clid,
				"autor" => (int)$iduser1,
				"user"  => getClientData($clid, 'iduser')
			]);

		}

		$response['result'] = yimplode("; ", $mes);

		//проверка дублей
		$this -> checkDouble($clid);

		return $response;

	}

	/**
	 * Изменение информации по клиенту
	 * Обновляет ВСЕ поля по клиенту
	 * Позволяет очистить не нужные поля
	 *
	 * @param int $clid - идентификатор записи клиента
	 * @param array $params - массив данных ключ = значение (см. БД {PREFIX}clientcat)
	 *                        - **recv** - реквизиты
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = clid
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      - 403 - Клиент с указанным clid не найден в пределах аккаунта
	 *      - 405 - Отсутствуют параметры - clid клиента
	 *
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> update($clid, $params);
	 * ```
	 * @throws Exception
	 */
	public function fullupdate(int $clid = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;
		//$clid = $clid;

		//file_put_contents($this -> rootpath."/cash/request.json", json_encode_cyr($_REQUEST));

		if ($hooks) {
			$params = $hooks -> apply_filters("client_editfilter", $params);
		}

		//print_r($params);

		$newParams = $oldParams = $client = [];
		$mes       = [];
		$response  = [];

		$bankInfoField = [
			'castUrName',
			'castInn',
			'castKpp',
			'castBank',
			'castBankKs',
			'castBankRs',
			'castBankBik',
			'castOkpo',
			'castOgrn',
			'castDirName',
			'castDirSignature',
			'castDirStatus',
			'castDirStatusSig',
			'castDirOsnovanie',
			'castUrAddr',
			'castUrNameShort'
		];
		$bankinfo      = $params['recv'];

		$exludef = ['recv'];

		//проверка принадлежности clid к данному аккаунту
		$clid = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'");

		if ($clid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Клиент с указанным clid не найден в пределах аккаунта";

			return $response;

		}

		if ($clid > 0) {

			$fields = [];

			$res = $db -> getAll("select * from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order");
			foreach ($res as $da) {

				if (!in_array($da['fld_name'], $exludef)) {
					$fields[] = $da['fld_name'];
				}

				//если прилетел массив, то преобразуем его в строку
				if (is_array($params[$da['fld_name']]) && !in_array($da['fld_name'], $exludef)) {

					$params[$da['fld_name']] = trim(yimplode(", ", $params[$da['fld_name']]));

				}

			}
			$fields[] = 'uid';
			$fields[] = 'type';

			//print_r($fields);

			foreach ($fields as $field) {

				//для АПИ проверяем пришедшие поля и если поля нет, то исключаем из запроса
				if (/*isset($params['fromapi']) &&*/ array_key_exists($field, $params)) {

					if ($field == 'recv') {
						continue;
					}

					switch ($field) {
						case "type":
							$client[$field] = ( $params[$field] == '' ) ? 'client' : untag($params[$field]);
							break;
						case "title":
							$client[$field] = clientFormatTitle($params[$field]);
							break;
						case "idcategory":
							$client[$field] = ( is_numeric($params[$field]) ) ? (int)$params[$field] : getClientCategory($params[$field]);
							break;
						case "clientpath":
							$client[$field] = ( is_numeric($params[$field]) ) ? (int)$params[$field] : getClientpath($params[$field]);
							break;
						case "territory":
							$client[$field] = ( is_numeric($params[$field]) ) ? (int)$params[$field] : getClientTerritory(untag($params[$field]));
							break;
						case "tip_cmr":
							$client[$field] = ( !is_numeric($params[$field]) ) ? getClientRelation(untag($params[$field])) : $params[$field];
							break;
						case 'head_clid':
							$client[$field] = (int)$params[$field];
							break;
						default:
							$client[$field] = fieldClean($params[$field]);
							break;
					}

				}

			}

			$clientOld               = get_client_info($clid, 'yes');
			$clientOld['clientpath'] = $clientOld['clientpath2'];

			$client['uid'] = ( $clientOld['uid'] != '' ) ? $clientOld['uid'] : $client['uid'];

			//print array2string($client);

			foreach ($client as $key => $value) {

				$newParams[$key] = $value;          //массив новых параметров
				$oldParams[$key] = $clientOld[$key];//массив старых параметров

			}

			$client['date_edit'] = current_datumtime();
			$client['editor']    = (int)$iduser1;

			$log = doLogger('clid', $clid, $newParams, $oldParams);

			//если есть измененнные данные, то обновляем
			if ($log != 'none' && !empty($client)) {

				try {

					$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", $client);

					$mes[]            = 'Успешно';
					$response['data'] = $clid;//запись в историю активности

					$client['clid'] = $clid;

					if ($hooks) {
						$hooks -> do_action("client_edit", $post, $client);
					}

					if ((int)$params['iduser'] != (int)$iduser1 && (int)$clientOld['iduser'] != (int)$params['iduser']) {

						sendNotify('send_client', [
							"clid"   => $clid,
							"title"  => untag($params['title']),
							"iduser" => (int)$params['iduser'],
							"notice" => 'yes'
						]);

					}

					/**
					 * Уведомления
					 */
					//require_once "Notify.php";
					$arg = [
						"clid"   => $clid,
						"title"  => untag($params['title']),
						"iduser" => (int)$params['iduser'],
						"log"    => $log,
						"notice" => 'yes'
					];
					Notify ::fire("client.edit", $iduser1, $arg);


					/**
					 * Активность
					 */
					$hid             = addHistorty([
						"iduser"   => (int)$iduser1,
						"clid"     => $clid,
						"datum"    => current_datumtime(),
						"des"      => $log,
						"tip"      => "ЛогCRM",
						"untag"    => "no",
						"identity" => $identity
					]);
					$response['hid'] = $hid;//запись в историю активности


					/**
					 * Событие
					 */
					$diff2 = array_diff_ext($oldParams, $newParams);
					event ::fire('client.edit', [
						"clid"     => $clid,
						"autor"    => (int)$iduser1,
						"user"     => (int)$params['iduser'],
						"newparam" => $diff2
					]);

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					return $response;

				}

			}
			else {

				$mes[]            = 'Данные корректны, но идентичны имеющимся. ';
				$response['data'] = $clid;

			}


			//получим имеющиеся реквизиты клиента
			$currentBinfo = get_client_recv($clid, "yes");

			//формируем реквизиты
			$binfo = [];

			//print_r($bankinfo);

			foreach ($bankInfoField as $value) {

				$binfo[] = ( isset($bankinfo[$value]) ) ? clean($bankinfo[$value]) : $currentBinfo[$value];

			}

			$recv = implode(";", $binfo);

			//var_dump($params['recv']);

			//если указаны реквизиты - добавляем
			if (!empty((array)$params['recv']) && isset($params['recv'])) {

				$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", ["recv" => $recv]);
				$mes[] = 'Реквизиты обновлены';

			}

			$response['result'] = yimplode("; ", $mes);

			//проверка дублей
			$this -> checkDouble($clid);

		}

		return $response;

	}

	/**
	 * Удаление клиента
	 *
	 * @param int $clid - идентификатор записи клиента
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = clid
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      - 408 - Клиент не удален. Имеются связанные записи - Сделки
	 *      - 405 - Отсутствуют параметры - clid клиента
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> delete($clid);
	 * ```
	 * @throws Exception
	 */
	public function delete(int $clid): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$fpath    = $this -> fpath;

		$title = current_client($clid);

		$clid = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE (clid = '$clid') and identity = '$identity'");

		//проверяем на наличие сделок
		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE clid = '$clid' and identity = '$identity'");

		if ($clid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Не найдена запись";

		}
		elseif (!isset($clid)) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid клиента";

		}
		elseif ($count > 0) {

			$error = 'Внимание: К сожалению Удаление записи не возможно. Причина - Имеются связанные записи - Сделки.';

			$response['result']        = 'Error';
			$response['error']['code'] = 408;
			$response['error']['text'] = "Клиент не удален. ".$error;

		}
		else {

			if ($hooks) {
				$hooks -> do_action("client_delete", $clid);
			}

			//Удалим всю связанные файлы
			$res = $db -> getAll("SELECT fid, fname FROM {$sqlname}file WHERE clid = '$clid' and identity = '$identity'");
			foreach ($res as $data) {

				@unlink($rootpath."/files/".$fpath.$data['fname']);
				$db -> query("DELETE FROM {$sqlname}file WHERE fid = '".$data['fid']."' and identity = '$identity'");

			}

			$db -> query("UPDATE {$sqlname}personcat set clid = 0 WHERE clid = '$clid' and identity = '$identity'");
			$countPerson = $db -> affectedRows();

			$db -> query("UPDATE {$sqlname}contract SET clid = 0 WHERE clid = '$clid' and identity = '$identity'");
			//$countContract = $db -> affectedRows();

			$db -> query("UPDATE {$sqlname}comments SET clid = 0 WHERE clid = '$clid' and identity = '$identity'");
			//$countComment = $db -> affectedRows();

			$db -> query("UPDATE {$sqlname}leads SET clid = 0 WHERE clid = '$clid' and identity = '$identity'");
			//$countLead = $db -> affectedRows();

			$db -> query("UPDATE {$sqlname}entry SET clid = 0 WHERE clid = '$clid' and identity = '$identity'");
			//$countEntry = $db -> affectedRows();

			$db -> query("DELETE FROM {$sqlname}history WHERE clid = '$clid' and identity = '$identity'");
			$countHistory = $db -> affectedRows();

			//$db -> query("delete from {$sqlname}speca where clid = '".$clid."' and identity = '$identity'");
			//$countSpeca = $db -> affectedRows();

			//$db -> query("delete from {$sqlname}complect where clid = '".$clid."' and identity = '$identity'");
			//$countComplect = $db -> affectedRows();

			$db -> query("delete from {$sqlname}dogovor where clid = '$clid' and identity = '$identity'");
			//$countDogovor = $db -> affectedRows();

			$db -> query("delete FROM {$sqlname}credit WHERE clid='$clid' and identity = '$identity'");
			//$countCredit = $db -> affectedRows();

			$db -> query("delete FROM {$sqlname}profile WHERE clid='$clid' and identity = '$identity'");
			//$countProfile = $db -> affectedRows();

			//Удалим все напоминания
			$countTask = 0;
			$result3   = $db -> query("select * from {$sqlname}tasks WHERE clid='$clid' and identity = '$identity'");
			while ($data = $db -> fetch($result3)) {

				if ((int)$data['pid'] == 0) {
					$db -> query("delete from {$sqlname}tasks where tid = '".$data['tid']."' and identity = '$identity'");
				}
				else {
					$db -> query("update {$sqlname}tasks set clid = 0 where tid = '".$data['tid']."' and identity = '$identity'");
				}
				$countTask++;

			}

			//Удалим всю связанные файлы
			$countFiles = 0;
			$result4    = $db -> query("select * from {$sqlname}file WHERE clid='$clid' and identity = '$identity'");
			while ($data = $db -> fetch($result4)) {

				@unlink($rootpath."/files/".$data['fname']);
				$db -> query("delete from {$sqlname}file where fid = '".$data['fid']."' and identity = '$identity'");
				$countFiles++;

			}

			$db -> query("delete from {$sqlname}clientcat where clid = '$clid' and identity = '$identity'");

			logger('12', 'Удален клиент: '.$title, $iduser1);

			sendNotify('delete_client', [
				"clid"   => $clid,
				"title"  => $title,
				"iduser" => $iduser1,
				"notice" => 'yes'
			]);


			/**
			 * Уведомления
			 */
			//require_once "Notify.php";
			$arg = [
				"clid"   => $clid,
				"title"  => $title,
				"iduser" => $iduser1,
				"notice" => 'yes'
			];
			Notify ::fire("client.delete", $iduser1, $arg);

			/**
			 * Событие
			 */
			event ::fire('client.delete', [
				"clid"  => $clid,
				"autor" => $iduser1
			]);


			//todo: доработать сообщение с количеством записей
			$response['message'] = 'Успешно: Клиент удален.<br>Также удалено '.$countHistory.' записи истории активностей. Снята привязка к Клиенту у '.$countPerson.' Контактов. Удалено '.$countTask.' записей напоминаний. Удалено '.$countFiles.' файлов.';

			$response['result'] = 'Клиент удален';
			$response['data']   = $clid;

		}

		return $response;

	}

	/**
	 * todo: Полное удаление всех следов Клиента, вклчая Контакты, Сделки и все связанные записи
	 * @param int $clid
	 * @return array
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function deleteFull(int $clid): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$fpath    = $this -> fpath;

		$title = current_client($clid);

		$clid = (int)$db -> getOne("SELECT clid FROM {$sqlname}clientcat WHERE (clid = '$clid') and identity = '$identity'");

		//проверяем на наличие сделок
		$deals = $db -> getAll("SELECT did FROM {$sqlname}dogovor WHERE clid = '$clid' and identity = '$identity'");

		if ($clid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Не найдена запись";

		}
		elseif (!isset($clid)) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid клиента";

		}
		else {

			if ($hooks) {
				$hooks -> do_action("client_delete", $clid);
			}

			//Удалим всю связанные файлы
			$res = $db -> getAll("SELECT fid, fname FROM {$sqlname}file WHERE clid = '$clid' and identity = '$identity'");
			foreach ($res as $data) {

				@unlink($rootpath."/files/".$fpath.$data['fname']);
				$db -> query("DELETE FROM {$sqlname}file WHERE fid = '".$data['fid']."' and identity = '$identity'");

			}

			$db -> query("DELETE FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity'");
			$countPerson = $db -> affectedRows();

			$db -> query("DELETE FROM {$sqlname}contract WHERE clid = '$clid' and identity = '$identity'");
			//$countContract = $db -> affectedRows();

			$db -> query("DELETE FROM {$sqlname}comments WHERE clid = '$clid' and identity = '$identity'");
			//$countComment = $db -> affectedRows();

			$db -> query("UPDATE {$sqlname}leads SET clid = 0, did = 0 WHERE clid = '$clid' and identity = '$identity'");
			//$countLead = $db -> affectedRows();

			$db -> query("UPDATE {$sqlname}entry SET clid = 0, did = 0 WHERE clid = '$clid' and identity = '$identity'");
			//$countEntry = $db -> affectedRows();

			$db -> query("DELETE FROM {$sqlname}history WHERE clid = '".$clid."' AND identity = '$identity'");
			$countHistory = $db -> affectedRows();

			foreach ($deals as $did) {

				$db -> query("DELETE FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity'");
				//$countSpeca = $db -> affectedRows();

				$db -> query("DELETE FROM {$sqlname}complect WHERE did = '$did' and identity = '$identity'");
				//$countComplect = $db -> affectedRows();

			}

			$db -> query("DELETE FROM {$sqlname}dogovor WHERE clid = '$clid' and identity = '$identity'");
			//$countDogovor = $db -> affectedRows();

			$db -> query("DELETE FROM {$sqlname}credit WHERE clid = '$clid' and identity = '$identity'");
			//$countCredit = $db -> affectedRows();

			$db -> query("DELETE FROM {$sqlname}profile WHERE clid = '$clid' and identity = '$identity'");
			//$countProfile = $db -> affectedRows();

			//Удалим все напоминания
			$countTask = 0;
			$result3   = $db -> query("select * from {$sqlname}tasks WHERE clid='".$clid."' and identity = '$identity'");
			while ($data = $db -> fetch($result3)) {

				if ((int)$data['pid'] == 0) {
					$db -> query("delete from {$sqlname}tasks where tid = '".$data['tid']."' and identity = '$identity'");
				}
				else {
					$db -> query("update {$sqlname}tasks set clid = 0 where tid = '".$data['tid']."' and identity = '$identity'");
				}
				$countTask++;

			}

			//Удалим всю связанные файлы
			$countFiles = 0;
			$result4    = $db -> query("select * from {$sqlname}file WHERE clid='".$clid."' and identity = '$identity'");
			while ($data = $db -> fetch($result4)) {

				@unlink($rootpath."/files/".$data['fname']);
				$db -> query("delete from {$sqlname}file where fid = '".$data['fid']."' and identity = '$identity'");
				$countFiles++;

			}

			$db -> query("DELETE FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'");

			logger('12', 'Удален клиент: '.$title.' со всеми связанными записями', $iduser1);

			sendNotify('delete_client', [
				"clid"   => $clid,
				"title"  => $title,
				"iduser" => $iduser1,
				"notice" => 'yes'
			]);


			/**
			 * Уведомления
			 */
			//require_once "Notify.php";
			$arg = [
				"clid"   => $clid,
				"title"  => $title,
				"iduser" => $iduser1,
				"notice" => 'yes'
			];
			Notify ::fire("client.delete", $iduser1, $arg);

			/**
			 * Событие
			 */
			event ::fire('client.delete', [
				"clid"  => $clid,
				"autor" => $iduser1
			]);


			//todo: доработать сообщение с количеством записей
			$response['message'] = 'Успешно: Клиент удален.<br>Также удалено '.$countHistory.' записи истории активностей. Удалено '.$countPerson.' Контактов. Удалено '.$countTask.' записей напоминаний. Удалено '.$countFiles.' файлов.';

			$response['result'] = 'Клиент удален';
			$response['data']   = $clid;

		}

		return $response;

	}

	/**
	 * Разные действия с клиентом
	 *
	 * @param $clid
	 * @param $action - массив с параметрами
	 *                - **trash** - в корзину
	 *                - **untrash** - из корзины
	 *                - **cold** - в холодные
	 *                - **uncold** - из холодных
	 *                - **fav** - в избранные
	 *                - **unfav** - из избранных
	 *
	 * @return array
	 * good result
	 *         - result = result
	 *
	 * error result
	 *         - result = Error
	 *         - text
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> actions($clid, 'fav');
	 * ```
	 */
	public function actions($clid, $action): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$response = [];

		switch ($action) {

			case "trash":

				try {

					$db -> query("update {$sqlname}clientcat set trash = 'yes' where clid = '$clid' and identity = '$identity'");

					addHistorty($params = [
						"datum"  => current_datumtime(),
						"iduser" => (int)$iduser1,
						"clid"   => (int)$clid,
						"des"    => 'Клиент помещен в Свободные',
						"tip"    => "СобытиеCRM"
					]);

					$response['result'] = $params['des'];

				}
				catch (Exception $e) {

					$response['result'] = 'Error';
					$response['error']  = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				break;
			case "untrash":

				try {

					$db -> query("update {$sqlname}clientcat set trash = 'no' where clid = '".$clid."' and identity = '$identity'");

					addHistorty($params = [
						"datum"  => current_datumtime(),
						"iduser" => (int)$iduser1,
						"clid"   => (int)$clid,
						"des"    => 'Клиент активирован сотрудником - '.current_user($iduser1),
						"tip"    => "СобытиеCRM"
					]);

					$response['result'] = $params['des'];

				}
				catch (Exception $e) {

					$response['result'] = 'Error';
					$response['error']  = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				break;
			case "cold":

				try {

					$db -> query("update {$sqlname}clientcat set iduser='0' where clid = '$clid' and identity = '$identity'");

					addHistorty($params = [
						"datum"  => current_datumtime(),
						"iduser" => (int)$iduser1,
						"clid"   => (int)$clid,
						"des"    => 'Клиент помещен в Корзину - '.current_user($iduser1),
						"tip"    => "СобытиеCRM"
					]);

					$response['result'] = $params['des'];

				}
				catch (Exception $e) {

					$response['result'] = 'Error';
					$response['error']  = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				break;
			case "uncold":

				try {

					$db -> query("update {$sqlname}clientcat set iduser='$iduser1' where clid = '$clid' and identity = '$identity'");

					addHistorty($params = [
						"datum"  => current_datumtime(),
						"iduser" => (int)$iduser1,
						"clid"   => (int)$clid,
						"des"    => 'Клиент взят в работу сотрудником - '.current_user($iduser1),
						"tip"    => "СобытиеCRM"
					]);

					$response['result'] = $params['des'];

				}
				catch (Exception $e) {

					$response['result'] = 'Error';
					$response['error']  = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				break;
			case "fav":

				try {

					$db -> query("update {$sqlname}clientcat set fav = 'yes' where clid = '$clid' and identity = '$identity'");

					addHistorty($params = [
						"datum"  => current_datumtime(),
						"iduser" => (int)$iduser1,
						"clid"   => (int)$clid,
						"des"    => 'Клиент помещен в Избранное сотрудником - '.current_user($iduser1),
						"tip"    => "СобытиеCRM"
					]);

					$response['result'] = $params['des'];

				}
				catch (Exception $e) {

					$response['result'] = 'Error';
					$response['error']  = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				break;
			case "unfav":

				try {

					$db -> query("update {$sqlname}clientcat set fav = 'no' where clid = '$clid' and identity = '$identity'");

					addHistorty($params = [
						"datum"  => current_datumtime(),
						"iduser" => (int)$iduser1,
						"clid"   => (int)$clid,
						"des"    => 'Клиент изъят из Избранного сотрудником - '.current_user($iduser1),
						"tip"    => "СобытиеCRM"
					]);

					$response['result'] = $params['des'];

				}
				catch (Exception $e) {

					$response['result'] = 'Error';
					$response['error']  = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				break;

		}

		return $response;

	}

	/**
	 * Смена ответственного
	 *
	 * @param       $clid
	 *
	 * @param array $params - массив с параметрами
	 *                      - **newuser** - iduser нового ответственного
	 *                      - **person_send** - передавать Контакты = yes|no
	 *                      - **dog_send** - передавать Сделки = yes|no
	 *                      - **reason** - комментарий
	 *
	 * @return array
	 *
	 *      - result
	 *      - error
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> changeUser($clid, ['newuser' => 20, reason = 'в работу']);
	 * ```
	 * @throws Exception
	 */
	public function changeUser($clid, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;

		$newuser = (int)$params["newuser"];
		$olduser = getClientData($clid, 'iduser');

		$person_send = $params['person_send'];
		$dog_send    = $params['dog_send'];
		$reazon      = ( $params['reason'] == '' ) ? 'не указано' : $params['reason'];

		$post['clid'] = (int)$clid;

		if ($hooks) {
			$hooks -> do_action("client_change_user", $post);
		}

		$err = [];

		try {

			$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' AND identity = '$identity'", [
				"iduser" => $newuser,
				"trash"  => "no"
			]);

			//передадим напоминания
			$db -> query("UPDATE {$sqlname}tasks SET iduser = '$newuser' WHERE clid = '$clid' AND iduser = '$olduser'");

		}
		catch (Exception $e) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		if ($person_send == "yes") {

			$result = $db -> query("SELECT * FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity'");
			while ($data = $db -> fetch($result)) {

				try {

					$db -> query("UPDATE {$sqlname}personcat SET ?u WHERE pid = '$data[pid]' AND identity = '$identity'", ["iduser" => $newuser]);
				}
				catch (Exception $e) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				//внесем запись в историю Персоны
				addHistorty([
					"pid"      => (int)$data['pid'],
					"datum"    => current_datumtime(),
					"des"      => "Передача с Клиентом. Причина: ".$reazon,
					"iduser"   => (int)$iduser1,
					"tip"      => 'СобытиеCRM',
					"identity" => (int)$identity
				]);

			}

		}
		if ($dog_send == "yes") {

			$result = $db -> getAll("SELECT * FROM {$sqlname}dogovor WHERE clid = '$clid' and identity = '$identity'");
			foreach ($result as $data) {

				try {

					$db -> query("update {$sqlname}dogovor set ?u where did = '$data[did]' and close != 'yes' and identity = '$identity'", ["iduser" => $newuser]);

				}
				catch (Exception $e) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				//внесем запись в историю Сделки
				addHistorty([
					"did"      => (int)$data['did'],
					"datum"    => current_datumtime(),
					"des"      => "Передача с Клиентом. Причина: ".$reazon,
					"iduser"   => (int)$iduser1,
					"tip"      => 'СобытиеCRM',
					"identity" => (int)$identity
				]);

				$res = $db -> getAll("SELECT * FROM {$sqlname}complect WHERE did = '$data[did]' and doit != 'on' and identity = '$identity'");
				foreach ($res as $da) {

					try {

						$db -> query("update {$sqlname}complect set ?u where id = '$da[id]' and identity = '$identity'", ["iduser" => $newuser]);

					}
					catch (Exception $e) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

				}
			}

		}

		/**
		 * Активность
		 */
		addHistorty([
			"clid"     => (int)$clid,
			"datum"    => current_datumtime(),
			"des"      => "Смена Ответственного: ".current_user($olduser)."&rarr;".current_user($newuser).". Причина: $reazon. Изменил: ".current_user($iduser1),
			"iduser"   => (int)$iduser1,
			"tip"      => 'СобытиеCRM',
			"identity" => (int)$identity
		]);


		/**
		 * Уведомление по email
		 */
		sendNotify('send_client', [
			"clid"    => (int)$clid,
			"title"   => getClientData((int)$clid, 'title'),
			"iduser"  => $newuser,
			"notice"  => 'yes',
			"comment" => $reazon
		]);


		/**
		 * Уведомления
		 */
		//require_once "Notify.php";
		$arg = [
			"clid"    => (int)$clid,
			"title"   => getClientData((int)$clid, 'title'),
			"iduser"  => $newuser,
			"notice"  => 'yes',
			"comment" => $reazon
		];
		Notify ::fire("client.userchange", $iduser1, $arg);


		/**
		 * Событие
		 */
		event ::fire('client.change.user', [
			"clid"    => (int)$clid,
			"autor"   => (int)$iduser1,
			"olduser" => (int)$olduser,
			"newuser" => $newuser,
			"comment" => $reazon
		]);

		return [
			'result' => "Сделано",
			"error"  => $err
		];

	}

	/**
	 * Управление доступом в карточку
	 *
	 * @param       $clid
	 * @param array $params - массив с параметрами
	 *                      - **userlist**
	 *
	 * @return array
	 * good result
	 *         - result = Ok
	 *         - data
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      - 403 - Клиент не найден
	 *      - 408 - Клиент не удален. Имеются связанные записи - Сделки
	 *      - 405 - Отсутствуют параметры - clid клиента
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> changeDostup($clid, [1,2,3,4,5]);
	 * ```
	 */
	public function changeDostup($clid, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$post = $params;

		$mes = [];

		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'");

		if ((int)$clid > 0) {

			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = 403;
				$response['error']['text'] = "Клиент не найден";

			}
			else {

				$good = 0;
				$err  = [];

				//список пользователей, которые имеют доступ
				$dostup = (array)$db -> getCol("SELECT iduser FROM {$sqlname}dostup WHERE clid = '$clid' and identity = '$identity'");

				//удаление доступа
				foreach ($dostup as $iduser) {

					//если пользователя нет в списке, то удаляем его
					if (!in_array($iduser, (array)$params['userlist'])) {

						$db -> query("delete from {$sqlname}dostup where clid = '$clid' and iduser = '$iduser' and identity = '$identity'");
						$good = $db -> affectedRows();

					}

				}

				//добавление доступа
				foreach ($params['userlist'] as $user) {

					//если пользователя нет в списке, то добавляем
					if (!in_array($user, $dostup)) {

						try {

							$db -> query("INSERT INTO {$sqlname}dostup SET ?u", [
								"clid"      => (int)$clid,
								"iduser"    => (int)$user,
								"subscribe" => "off",
								"identity"  => $identity
							]);
							$good++;

						}
						catch (Exception $e) {
							$err[] = $e -> getMessage();
						}

					}

				}

				$post['clid'] = (int)$clid;

				if ($hooks) {
					$hooks -> do_action("client_change_dostup", $post);
				}

				if (empty($err)) {

					$mes['count']  = $good;
					$mes['errors'] = 0;

					$response['result'] = 'Ok';
					$response['data']   = $mes;

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;

				}

				$response['error']['text'] = $err;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid Клиента";

		}

		return $response;

	}

	/**
	 * Смена типа отношений
	 *
	 * @param       $clid
	 * @param array $params - массив с параметрами
	 *                      - str **tip_cmr** - Тип отношений
	 *                      - str **reason** - Комментарий
	 *
	 * @return array
	 *
	 *      - result => Сделано
	 *      - des
	 *      - error
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> changeRelation($clid, ['tip_cmr' => 'Ключевой','reason' => 'В работу']);
	 * ```
	 * @throws Exception
	 */
	public function changeRelation($clid, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;

		$client['tip_cmr'] = $params["tip_cmr"];
		$client['reazon']  = ( $params['reason'] != '' ) ? $params['reason'] : 'не указано';

		$err = [];

		$relationOld = getClientData((int)$clid, 'tip_cmr');

		$des = "Смена Типа отношений: $relationOld &rarr; $client[tip_cmr]. Причина: ".$client['reazon'].". Изменил: ".current_user($iduser1);

		try {

			$db -> query("update {$sqlname}clientcat set ?u where clid = '$clid' and identity = '$identity'", ["tip_cmr" => $client['tip_cmr']]);

			if ($hooks) {
				$hooks -> do_action("client_change_relation", $post);
			}

		}
		catch (Exception $e) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		addHistorty([
			"clid"     => (int)$clid,
			"datum"    => current_datumtime(),
			"des"      => $des,
			"iduser"   => (int)$iduser1,
			"tip"      => 'СобытиеCRM',
			"identity" => $identity
		]);

		/*event ::fire('client.change.relation', $args = array(
			"clid"    => $clid,
			"autor"   => $iduser1,
			"old" => $relationOld,
			"new" => $client['tip_cmr'],
			"comment" => $client['reazon']
		));*/

		return [
			"result" => "Сделано",
			"des"    => $des,
			"error"  => $err
		];

	}

	/**
	 * Проверка на дубли по 3-м параметрам
	 * - phone, fax
	 * - email
	 * - ИНН + КПП
	 * Возвращает массив с тремя параметрами, в которых найдены дубли
	 * в которых ключ = clid, значение = совпадающий параметр
	 *
	 * @param       $clid
	 * @param array $params - массив с параметрами
	 *                      - **nolog** = 1 - результат не будет внесен в лог дублей
	 *                      - **noNotify** = true - уведомления Координаторам отключаем принудительно
	 *                      - **multi** = true - для проверки всей базы
	 *
	 * @return object
	 *
	 *      - isdouble - массив дублей
	 *
	 *          - id
	 *          - phone
	 *          - clid
	 *          - recv
	 *              - inn
	 *              - kpp
	 *
	 *      - doubleid - id записи в таблице _doubles
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> checkDouble($clid, ['nolog' => 0,'noNotify' => false]);
	 * ```
	 * @throws Exception
	 */
	public function checkDouble($clid, array $params = []): object {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$fpath    = $this -> fpath;

		$isdouble = [];
		$doubleid = 0;

		$this -> isdouble = $isdouble;
		$this -> doubleid = $doubleid;

		//загрузим настройки из кэша
		$dbl         = json_decode((string)file_get_contents($rootpath."/cash/".$fpath."settings.checkdoubles.json"), true);
		$Fields      = (array)$dbl['field'];
		$Coordinator = (array)$dbl['Coordinator'];

		//если модуль не активен, то выходим
		if ($dbl['active'] != 'yes') {
			return $this;
		}

		//смотрим, производилась ли проверка организации
		$exist = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}doubles WHERE tip = 'client' AND idmain = '$clid' AND status = 'no'");
		if ($exist > 0) {
			return $this;
		}

		//для мультипроверки будем фильтровать по уже проверенным
		$multi = $params['multi'] ? "clid NOT IN (SELECT idmain FROM {$sqlname}doubles WHERE tip = 'client' and status = 'no' AND identity = '$identity') AND" : "";

		//получаем данные по клиенту
		$phone = yexplode(",", str_replace(";", ",", getClientData($clid, 'phone')));
		$fax   = yexplode(",", str_replace(";", ",", getClientData($clid, 'fax')));
		$email = yexplode(",", str_replace(";", ",", getClientData($clid, 'mail_url')));
		$recv  = (array)get_client_recv($clid, 'yes');

		$tel = array_merge($phone, $fax);

		//todo: в перспективе предусмотреть проверку на прикрепленность компании в качестве дочерней или основной

		//ищем по номеру телефона
		if (in_array("phone", $Fields)) {

			foreach ($tel as $phone) {

				$phone = prepareMobPhone($phone);
				$ids   = [];

				if ($phone != '' && strlen($phone) > 5) {

					$xphone = mb_substr(prepareMobPhone($phone), 1);

					$ids = $db -> getCol("
						SELECT clid 
						FROM {$sqlname}clientcat 
						WHERE 
							clid != '$clid' AND 
							$multi
							(
								replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$xphone%' OR
								replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$xphone%'
							) AND 
							identity = '$identity'
					");

				}

				foreach ($ids as $id) {
					$isdouble['phone'][$id] = $phone;
					$isdouble['clid'][]     = $id;
				}

			}

		}

		//ищем по email
		if (in_array("mail_url", $Fields)) {

			foreach ($email as $mail) {

				$ids = [];

				if ($mail != '' && strlen($mail) > 5) {

					$ids = $db -> getCol("
						SELECT clid 
						FROM {$sqlname}clientcat 
						WHERE 
							clid != '$clid' AND 
							$multi
							FIND_IN_SET('$mail', REPLACE(
								replace(
									replace(
										replace(
											replace(
												replace(mail_url, '+', ''), '(', ''), ')', ''
											), ' ', ''
										), '-', ''
									), ';',','
								)
							) > 0 AND 
							identity = '$identity'
					");

				}

				foreach ($ids as $id) {
					$isdouble['email'][(int)$id] = $mail;
					$isdouble['clid'][]          = (int)$id;
				}

			}

		}

		//ищем по реквизитам ИНН + КПП
		if ($recv['castInn'] != '' && $recv['castKpp'] != '' && in_array("recv", $Fields)) {

			$ids = $db -> getCol("
				SELECT clid 
				FROM {$sqlname}clientcat 
				WHERE 
					clid != '$clid' AND 
					$multi
					FIND_IN_SET('$recv[castInn]', REPLACE(recv, ';',',')) > 0 AND 
					FIND_IN_SET('$recv[castKpp]', REPLACE(recv, ';',',')) > 0 AND 
					identity = '$identity'
			");

			foreach ($ids as $id) {

				$isdouble['recv'][(int)$id] = [
					"inn" => $recv['castInn'],
					'kpp' => $recv['castKpp']
				];

				$isdouble['clid'][] = (int)$id;

			}

		}

		$isdouble['id']   = (int)$clid;
		$isdouble['clid'] = ( !empty($isdouble['clid']) ) ? array_unique($isdouble['clid']) : $isdouble['clid'];

		/**
		 * Если параметр 'nolog' не задан, то добавим в раздел найденных дублей
		 */
		if (!isset($params['nolog'])) {

			//добавляем только если такой записи нет в активных дублях и реально что-то найдено
			if ($exist == 0 && !empty((array)$isdouble['clid'])) {

				$ida   = (array)$isdouble['clid'];
				$ida[] = $clid;

				$db -> query("INSERT INTO {$sqlname}doubles SET ?u", [
					"idmain"   => $clid,
					"tip"      => "client",
					"list"     => json_encode_cyr($isdouble),
					"ids"      => yimplode(",", $ida),
					"identity" => $identity
				]);
				$doubleid = $db -> insertID();

			}

		}

		//отправляем уведомление координаторам
		if ($dbl['CoordinatorNotify'] == 'yes' && !isset($params['noNotify']) && !empty((array)$isdouble['clid'])) {

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
			foreach ($isdouble['clid'] as $iclid) {
				$text .= '<li>'.current_client((int)$iclid).' [ отв. '.current_user(getClientData((int)$iclid, 'iduser')).' ]</li>';
			}


			$html = str_replace([
				"{{client}}",
				"{{user}}",
				"{{text}}"
			], [
				getClientData((int)$clid, 'title'),
				current_user(getClientData((int)$clid, 'iduser')),
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

			/**
			 * Уведомления
			 */
			//require_once "Notify.php";
			$arg = [
				"clid"  => (int)$clid,
				"title" => getClientData((int)$clid, 'title'),
				"text"  => $text,
				"users" => $Coordinator
			];
			Notify ::fire("client.double", $iduser, $arg);

		}

		//ext:

		$this -> isdouble = $isdouble;
		$this -> doubleid = $doubleid;

		return $this;

	}

	/**
	 * Слияние дублей
	 *
	 * @param       $id - запись, в которую будем сливать
	 * @param array $params - массив с параметрами
	 *                      - **list** - одномерный массив записей, которые будем вливать в главную
	 *                      - **main** - главная запись, в которую будемсливать остальные
	 *                      - **more** - доп.опции
	 *
	 *          - **trash** - не удалять сливаемых, а поместить в корзину
	 *          - **newuser** - назначить главную запись на сотрудника
	 *          - **merge** - слить данные: телефоны, email
	 *          - **log** - добавить в лог данные сливаемых записей
	 *          - **notify** - уведомить сотрудников о слиянии
	 *          - **ignored** - строка с id, которые исключили из слияния
	 *
	 * @return string = ok
	 * @throws Exception
	 *
	 * Example:
	 *
	 *      ```php
	 *      $Client = new Client;
	 *      $rezult = $Client -> mergeDouble($id, $params);
	 *      ```
	 */
	public function mergeDouble($id, array $params = []): string {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$fpath    = $this -> fpath;
		$iduser1  = $this -> iduser1;

		$list    = $params['list'];
		$more    = $params['more'];
		$clid    = $params['main'];
		$ignored = yexplode(",", $params['main']);

		//загрузим настройки из кэша
		$dbl = json_decode(file_get_contents($rootpath."/cash/".$fpath."settings.checkdoubles.json"), true);

		//пользователи, которых будем уведомлять
		$users    = [];
		$userlist = [];

		//удалим главную запись из списка записей
		$list = array_flip($list);               //Меняем местами ключи и значения
		unset($list[$clid]);                     //Удаляем элемент массива
		$list = array_values(array_flip($list)); //Меняем местами ключи и значения и сбрасываем индексы

		if (empty($list)) {
			goto ext;
		}

		$info = $p = $f = $m = [];

		/**
		 * Сформируем массив данных для обновления главной записи
		 */

		if ($more['merge'] == 'yes') {

			//данные по основной записи
			$p = (array)yexplode(",", str_replace(";", ",", getClientData($clid, 'phone')));
			$f = (array)yexplode(",", str_replace(";", ",", getClientData($clid, 'fax')));
			$m = (array)yexplode(",", str_replace(";", ",", getClientData($clid, 'mail_url')));

			$users[] = getClientData($clid, 'iduser');

			//имена полей карточки клиента
			$fieldNames = $this -> fieldNames();

			/**
			 * Массив с информацией о клиентах, которые будут влиты в главного
			 */
			foreach ($list as $ida) {

				$c        = $this ::info((int)$ida);
				$listinfo = $c['client'];

				$userlist[(int)$ida] = [
					"title" => $listinfo['title'],
					"user"  => current_user($listinfo['iduser'])
				];

				//$p = array_merge( $p, yexplode( ",", str_replace( ";", ",", $listinfo['phone'] ) ) );

				$p1 = yexplode(",", str_replace(";", ",", (string)$listinfo['phone']));
				foreach ($p1 as $p2) {
					$p[] = $p2;
				}

				//$f = array_merge( $f, yexplode( ",", str_replace( ";", ",", $listinfo['fax'] ) ) );

				$f1 = yexplode(",", str_replace(";", ",", (string)$listinfo['fax']));
				foreach ($f1 as $p2) {
					$p[] = $p2;
				}

				//$m = array_merge( $m, yexplode( ",", str_replace( ";", ",", $listinfo['mail_url'] ) ) );

				$m1 = yexplode(",", str_replace(";", ",", (string)$listinfo['mail_url']));
				foreach ($m1 as $p2) {
					$p[] = $p2;
				}

				$values = $this -> Tags($listinfo);

				$info[$ida] = $fieldNames['title'].": ".$values['title']."\n";

				foreach ($fieldNames as $field => $name) {
					if ($field != 'title' && $values[$field] != '' && $field != 'recv') {
						$info[$ida] .= $name.": ".$values[$field]."\n";
					}
				}

				$users[] = $listinfo['iduser'];

			}

			$p = array_unique(array_map("prepareMobPhone", $p));
			$f = array_unique(array_map("prepareMobPhone", $f));
			$m = array_unique($m);

			$new = [];

			if (!empty($p)) {
				$new['phone'] = implode(",", $p);
			}
			if (!empty($f)) {
				$new['fax'] = implode(",", $f);
			}
			if (!empty($m)) {
				$new['mail_url'] = implode(",", $m);
			}

			$this -> update((int)$clid, $new);

		}

		//переводим записи на главную запись
		foreach ($list as $ida) {

			//проходим Клиентов
			$db -> query("UPDATE {$sqlname}clientcat SET head_clid = '$clid' WHERE head_clid = '$ida'");

			//проходим Контакты
			$db -> query("UPDATE {$sqlname}personcat SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Историю
			$db -> query("UPDATE {$sqlname}history SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Напоминания
			$db -> query("UPDATE {$sqlname}tasks SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Звонки
			$db -> query("UPDATE {$sqlname}callhistory SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Сделки
			$db -> query("UPDATE {$sqlname}dogovor SET clid = '$clid' WHERE clid = '$ida'");
			$db -> query("UPDATE {$sqlname}dogovor SET payer = '$clid' WHERE payer = '$ida'");

			//проходим Счета
			$db -> query("UPDATE {$sqlname}credit SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Документы
			$db -> query("UPDATE {$sqlname}contract SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Заявки
			$db -> query("UPDATE {$sqlname}leads SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Обсуждения
			$db -> query("UPDATE {$sqlname}comments SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Обращения
			$db -> query("UPDATE {$sqlname}entry SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Группы
			$db -> query("UPDATE {$sqlname}grouplist SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Файлы
			$db -> query("UPDATE {$sqlname}file SET clid = '$clid' WHERE clid = '$ida'");

			//проходим Почту
			$db -> query("UPDATE {$sqlname}ymail_messagesrec SET clid = '$clid' WHERE clid = '$ida'");

			//удалим доступы
			$db -> query("DELETE FROM {$sqlname}dostup WHERE clid = '$ida'");
			$db -> query("DELETE FROM {$sqlname}doubles WHERE idmain = '$ida'");

			//удалим запись
			if (!$more['trash']) {
				$this -> delete($ida);
			}
			else {
				$this -> actions($ida, 'trash');
			}

		}

		//если задан новый сотрудник, то меняем
		if ((int)$more['newuser'] > 0) {

			$this -> changeUser($clid, [
				"newuser" => (int)$more['newuser'],
				"reason"  => ( $more['des'] != '' ) ? "Слияние дублей. ".$more['des'] : ''
			]);

		}
		//если нет, то добавляем текущего ответственного
		else {

			$more['newuser'] = getClientData($clid, 'iduser');

		}

		/**
		 * todo: Работаем с проигнорированными записями - удалим из них совпадения по телефонам/email
		 */ /*if ( !empty( $ignored ) ) {


		}*/

		ext:

		//пометим выполненным
		$dinfo = [
			"status"  => 'yes',
			"des"     => $more['des'],
			"idmain"  => (int)$clid,
			//обязательно укажем, иначе запись может быть удалена
			"datumdo" => current_datumtime(),
			"iduser"  => (int)$iduser1
		];
		$db -> query("UPDATE {$sqlname}doubles SET ?u WHERE id = '$id'", $dinfo);

		//добавим лог
		if ($more['log'] == 'yes' && !empty($info)) {
			addHistorty([
				"clid"     => (int)$clid,
				"datum"    => current_datumtime(),
				"des"      => "Слияние дублей:\n\n".implode("\n\n", $info),
				"iduser"   => (int)$iduser1,
				"tip"      => 'СобытиеCRM',
				"identity" => (int)$identity
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
			foreach ($userlist as $item) {

				$text .= '<li>'.$item['title'].' [ отв. '.$item['user'].' ]</li>';

			}

			$html = str_replace([
				"{{client}}",
				"{{user}}",
				"{{text}}"
			], [
				getClientData($clid, 'title'),
				current_user(getClientData($clid, 'iduser')),
				$text
			], $html);

			if ((int)$more['newuser'] > 0) {

				$html = str_replace("{{newuser}}", '
					<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
					<div style="padding: 5px;">
						Установлен новый ответственный: <b>'.current_user((int)$more['newuser']).'</b>
					</div>
				', $html);

			}

			$html = str_replace("{{comment}}", '
				<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
				<div style="padding: 5px;">
					Комментарий: <b>'.nl2br($more['des']).'</b>
				</div>
			', $html);

			if ((int)$more['newuser'] > 0) {
				array_unshift($users, (int)$more['newuser']);
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
	 * Обработка дубля без слияния
	 *
	 * @param       $id
	 * @param array $params - массив с параметрами
	 *                      - array **more**
	 *                      - **des**
	 *
	 * @return string = ok
	 *
	 *
	 * Example:
	 *
	 * ```php
	 * $Client = new Client;
	 * $rezult = $Client -> ignoreDouble($id, $params);
	 * ```
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
		$titles         = $db -> getIndCol('fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order");
		$titles['type'] = 'Тип записи';

		return $titles;

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

	public function Fields($filter = ""): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		//поля клиента
		$isfields = $db -> getCol("SELECT fld_name FROM {$sqlname}field WHERE fld_tip = 'client' and fld_on='yes' and fld_name != 'recv' and identity = '$identity'");

		array_unshift($isfields, 'clid', 'uid', 'type', 'date_create', 'date_edit');

		//фильтр вывода по полям из запроса или все доступные
		if (!empty($filter)) {

			$fi     = yexplode(",", $filter);
			$fields = [];

			foreach ($fi as $f) {
				if (in_array($f, $isfields)) {
					$fields[] = $f;
				}
			}

			return $fields;

		}

		return $isfields;

	}

	public function list($params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		global $isadmin;

		$synonyms = [
			"date_chage" => "date_edit"
		];

		//задаем лимиты по-умолчанию
		$page = (int)$params['page'];
		$ord  = !empty($params['ord']) ? $params['ord'] : 'date_create';
		$tuda = !empty($params['tuda']) ? $params['tuda'] : 'DESC';

		if ($ord == 'date_change') {
			$ord = 'date_edit';
		}

		$fields = $this -> Fields((string)$params['fields']);

		$limit = 200;
		$sort  = '';

		if ($params['word'] != '') {

			$sort .= " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".Cleaner($params['word'])."%' or title LIKE '%".Cleaner($params['word'])."%' or des LIKE '%".Cleaner($params['word'])."%' or mail_url LIKE '%".Cleaner($params['word'])."%' or site_url LIKE '%".Cleaner($params['word'])."%' or address LIKE '%".Cleaner($params['word'])."%')";

		}

		if ($params['d1'] != '' && $params['d2'] == '') {
			$sort .= " and date_create > '".$params['d1']."'";
		}
		if ($params['d1'] != '' && $params['d2'] != '') {
			$sort .= " and (date_create BETWEEN '".$params['d1']."' and '".$params['d2']."')";
		}
		if ($params['d1'] == '' && $params['d2'] != '') {
			$sort .= " and date_create < '".$params['d2']."'";
		}

		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] == '') {
			$sort .= " and date_edit > '".$params['dateStart']."'";
		}
		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] != '') {
			$sort .= " and (date_edit BETWEEN '".$params['dateChangeStart']."' and '".$params['dateChangeEnd']."')";
		}
		if ($params['dateChangeStart'] == '' && $params['dateChangeEnd'] != '') {
			$sort .= " and date_edit < '".$params['dateChangeEnd']."'";
		}

		if ($params['user'] != '') {
			$sort .= " and {$sqlname}clientcat.iduser = '".current_userbylogin($params['user'])."'";
		}
		if ((int)$params['iduser'] > 0) {
			$sort .= " and {$sqlname}clientcat.iduser = '".$params['iduser']."'";
		}
		elseif ($isadmin != 'on') {
			$sort .= " and {$sqlname}clientcat.iduser IN (".yimplode(",", get_people($iduser1, "yes")).")";
		}

		$filterAllow = [
			"relations",
			"idcategory",
			"territory",
			"type",
			"clientpath",
			"trash"
		];

		$r = $db -> getCol("SELECT fld_name FROM {$sqlname}field WHERE fld_tip = 'client' and fld_name LIKE '%input%' and fld_on = 'yes' and identity = '$identity'");

		$filterAllow = array_merge($filterAllow, $r);

		//todo: проверить работу доп.фильтров
		foreach ($params['filter'] as $k => $v) {

			if ($v != '') {

				switch ($k) {
					case 'relations':

						$sort .= " and tip_cmr = '".untag($v)."'";

						break;
					case 'idcategory':

						if (!is_numeric($v)) {
							$sort .= " and idcategory = '".current_category(0, untag($v))."'";
						}
						else {
							$sort .= " and idcategory = '".(int)$v."'";
						}

						break;
					case 'territory':

						if (!is_numeric($v)) {
							$sort .= " and territory = '".current_territory('', untag($v))."'";
						}
						else {
							$sort .= " and territory = '".(int)$v."'";
						}

						break;
					case 'type':

						$sort .= " and type = '".untag($v)."'";

						break;
					case 'clientpath':

						if (!is_numeric($v)) {
							$sort .= " and clientpath = '".getClientpath(untag($v))."'";
						}
						else {
							$sort .= " and clientpath = '".(int)$v."'";
						}

						break;
					case 'trash':

						$sort .= $v ? " and trash = 'yes'" : " and COALESCE(trash, 'no') = 'no'";

						break;
					default:

						if (in_array($k, $filterAllow)) {
							$sort .= " and $k LIKE '%".untag($v)."%'";
						}

						break;
				}

			}

		}

		$lpos = $page * $limit;

		if (empty($page) || $page == 0) {
			$page = 1;
		}

		$field_types = db_columns_types("{$sqlname}clientcat");

		$list = [];

		$result = $db -> query("SELECT * FROM {$sqlname}clientcat WHERE clid > 0 $sort and identity = '$identity' ORDER BY $ord $tuda LIMIT $lpos,$limit");
		//print $db -> lastQuery();
		while ($da = $db -> fetch($result)) {

			$client = [];

			foreach ($fields as $field) {

				$field = strtr($field, $synonyms);

				switch ($field) {

					case 'head_clid':

						$client["head_clidTitle"] = get_client_category($da[$field]);
						$client[$field]           = (int)$da[$field];

						break;
					case 'pid':

						$client["person"] = current_person($da[$field]);
						$client[$field]   = (int)$da[$field];

						break;
					case 'iduser':
					case 'user':

						$client["user"] = current_userlogin($da[$field]);
						$client[$field] = (int)$da[$field];

						break;
					case 'idcategory':

						$client["idcategoryTitle"] = get_client_category($da[$field]);
						$client[$field]            = (int)$da[$field];

						break;
					case 'territory':

						$client["territoryTitle"] = current_territory($da[$field]);
						$client[$field]           = (int)$da[$field];

						break;
					case 'clientpath':

						$client["clientpathTitle"] = current_clientpathbyid($da[$field]);
						$client[$field]            = (int)$da[$field];

						break;
					default:

						//$client[ $field ] = $da[ $field ];

						if ($field_types[$field] == "int") {

							$client[$field] = (int)$da[$field];

						}
						elseif (
							in_array($field_types[$field], [
								"float",
								"double"
							])
						) {

							$client[$field] = (float)$da[$field];

						}
						else {

							$client[$field] = $da[$field];

						}

						break;

				}

			}

			if ($params['bankinfo']) {

				$bankinfo = get_client_recv($da['clid'], 'yes');

				foreach ($this -> bankInfoField as $key => $value) {
					$client['bankinfo'][$value] = $bankinfo[$value];
				}

			}

			if ($params['uids']) {

				$ruids = UIDs ::info(["clid" => $da['clid']]);
				if ($ruids['result'] == 'Success' && !empty($ruids['result']['data'])) {
					$client['uids'] = $ruids['data'];
				}

			}

			$list[] = $client;

		}

		$count       = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}clientcat WHERE clid > 0 $sort and identity = '$identity'");
		$count_pages = ceil($count / $limit);

		return [
			"list"    => $list,
			"page"    => $page,
			"pageall" => (int)$count_pages,
			"ord"     => $ord,
			"tuda"    => $tuda,
			"count"   => count($list)
		];

	}

	/**
	 * Привязка сообщений к записям клиента по email
	 * И добавление в историю активностей
	 * Может применяться после добавления/обновления записи
	 *
	 * @param $mail_url
	 * @param $clid
	 *
	 * @return void
	 * @throws Exception
	 */
	private function checkMailerEmail($mail_url, $clid): void {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ($mail_url != '') {

			//require_once $rootpath."/modules/ymail/yfunc.php";

			$mails = yexplode(",", (string)str_replace(";", ",", $mail_url));
			foreach ($mails as $email) {

				$res = $db -> query("SELECT id, mid FROM {$sqlname}ymail_messagesrec WHERE email = '$email' and identity = '$identity'");
				while ($data = $db -> fetch($res)) {

					//если clid не указан, то добавляем
					$db -> query("UPDATE {$sqlname}ymail_messagesrec SET clid = IF(clid = 0, '$clid', clid) WHERE id = '$data[id]' and identity = '$identity'");

					//проверяем наличие письма в истории
					$hid = $db -> getOne("SELECT hid FROM {$sqlname}ymail_messages WHERE id = '$data[mid]'") + 0;
					if ($hid == 0) {
						Mailer ::putHistory((int)$data['mid']);
					}

				}

			}

		}

	}

}