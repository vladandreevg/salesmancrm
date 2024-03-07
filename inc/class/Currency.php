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

/**
 * Класс для работы с валютами
 *
 * Class Currency
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
class Currency {

	public const HTMLCODE = [
		"dollar" => "$",
		"euro"   => "€",
		"pound"  => "£",
		"yen"    => "¥",
		"yuan"   => "￥",
		"grivna" => "₴",
		"rouble" => "ք",
		"frank"  => "₣",
		"tenge"  => "₸",
	];
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
	 * Работает только с объектом.
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Currency constructor.
	 */
	public function __construct() {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

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

		$database = $GLOBALS['database'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		// тут почему-то не срабатывает
		if (!empty($params)) {
			foreach ($params as $key => $val) $this ->{$key} = $val;
		}

		date_default_timezone_set($this -> tmzone);

		//создадим таблицу, если надо
		$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."currency'");
		if ($da == 0) {

			$db -> query("
				CREATE TABLE {$sqlname}currency (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`datum` DATE NULL DEFAULT NULL COMMENT 'дата добавления',
					`name` VARCHAR(50) NULL DEFAULT NULL COMMENT 'название валюты',
					`view` VARCHAR(10) NULL DEFAULT NULL COMMENT 'отображаемое название валюты',
					`code` VARCHAR(10) NULL COMMENT 'код валюты',
					`course` DOUBLE(20,4) NOT NULL DEFAULT '1.00' COMMENT 'текущий курс',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					INDEX `id` (`id`)
				)
				COMMENT='Таблица курсов валют'
				ENGINE=InnoDB
			");

		}

		$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."currency_log'");
		if ($da == 0) {

			$db -> query("
				CREATE TABLE {$sqlname}currency_log (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id записи валюты',
					`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'дата добавления',
					`course` DOUBLE(20,4) NOT NULL DEFAULT '1.00' COMMENT 'курс на дату',
					`iduser` VARCHAR(10) NULL DEFAULT NULL COMMENT 'сотрудник, который выполнил действие',
					`identity` INT(20) NOT NULL DEFAULT '1',
					PRIMARY KEY (`id`),
					INDEX `id` (`id`)
				)
				COMMENT='Таблица изменения курсов валют'
				ENGINE=InnoDB
			");

		}

		$field = $db -> getRow("SHOW COLUMNS FROM ".$sqlname."dogovor LIKE 'idcurrency'");
		if ($field['Field'] == '') {

			$db -> query("
				ALTER TABLE ".$sqlname."dogovor
				CHANGE COLUMN `provider` `idcurrency` INT(20) NULL DEFAULT NULL COMMENT 'id валюты' AFTER `direction`,
				CHANGE COLUMN `akt_num` `idcourse` INT(20) NULL DEFAULT NULL COMMENT 'id курса по сделке' AFTER `idcurrency`;
			");

			$db -> query("UPDATE ".$sqlname."dogovor SET idcurrency = '0'");
			$db -> query("UPDATE ".$sqlname."dogovor SET idcourse = '0'");

		}

	}

	/**
	 * Лог изменений валюты
	 *
	 * @param int $idcurrency - id валюты
	 * @param int $limit - ограничение записей
	 *
	 * @return array
	 */
	public static function currencyLog(int $idcurrency = 0, int $limit = 20): array {

		$rootpath = dirname(__DIR__, 2);

		include_once $rootpath."/inc/config.php";
		include_once $rootpath."/inc/func.php";
		require_once $rootpath."/inc/dbconnector.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];

		$list = [];

		$r = $db -> query("SELECT * FROM {$sqlname}currency_log WHERE idcurrency = '$idcurrency' AND identity = '$identity' ORDER BY datum DESC LIMIT 0, $limit");
		while ($data = $db -> fetch($r)) {

			$list[] = [
				"id"     => (int)$data['id'],
				"date"   => get_sfdate($data['datum']),
				"datum"  => $data['datum'],
				"course" => (float)$data['course'],
				"iduser" => (int)$data['iduser']
			];

		}

		/**
		 * Определим направление изменения
		 */
		$list = array_reverse($list);

		foreach ($list as $key => $item) {

			$list[$key]['icon']      = '&hellip;';
			$list[$key]['direction'] = '';
			$list[$key]['color']     = 'gray';

			if ($key != 0) {

				if ($item['course'] < $list[$key - 1]['course']) {

					$list[$key]['icon']      = '&darr;';
					$list[$key]['direction'] = 'down';
					$list[$key]['color']     = 'red';

				}

				elseif ($item['course'] > $list[$key - 1]['course']) {

					$list[$key]['icon']      = '&uarr;';
					$list[$key]['direction'] = 'up';
					$list[$key]['color']     = 'green';

				}

			}

		}

		return array_reverse($list);

	}

	/**
	 * Конвертация суммы в валюту
	 *
	 * @param      $summa - сумма, которую надо конвертировать
	 * @param      $idcourse - id курса валюты ( из лога )
	 * @param bool $symbol - добавлять к сумме символ валюты или написание (если символ не указан)
	 * @param bool $format
	 * @param bool $round
	 *
	 * @return float|int
	 */
	public static function currencyConvert($summa, $idcourse, bool $symbol = false, bool $format = false, bool $round = true) {

		$before = $after = '';

		$course   = ( new Currency ) -> courseInfo($idcourse);
		$currency = ( new Currency ) -> currencyInfo((int)$course['idcurrency']);

		if ($currency['id'] > 0) {

			if ($symbol) {

				if ($currency['code'] != '') {
					$before = strtr($currency['code'], self::HTMLCODE);
				}

				elseif ($currency['view'] != '') {
					$after = $currency['view'];
				}

			}

			if (is_numeric($summa)) {

				// конвертируем
				$newSumma = ( $round ) ? round(pre_format($summa) / $course['course'], 2) : pre_format($summa) / $course['course'];

				// форматируем
				$newSumma = ( $format ) ? num_format($newSumma) : $newSumma;

				// добавляем знаки
				$newSumma = ( $symbol ) ? $before.' '.$newSumma.' '.$after : $newSumma;

			}
			else {
				$newSumma = $summa;
			}

		}
		else {
			$newSumma = $summa;
		}

		return $newSumma;

	}

	/**
	 * Обратное преобразование
	 *
	 * @param $summa
	 * @param $idcourse
	 * @return float|int
	 */
	public static function currencyRevert($summa, $idcourse) {

		$course   = ( new Currency ) -> courseInfo($idcourse);
		$currency = ( new Currency ) -> currencyInfo($course['idcurrency']);

		if ($currency['id'] > 0) {

			$newSumma = is_numeric($summa) ? round(pre_format($summa) * $course['course'], 2) : 0;

		}
		else {
			$newSumma = $summa;
		}

		return $newSumma;

	}

	/**
	 * Конвертация массива спецификации
	 *
	 * @param array $speka - массив для конвертации
	 * @param int $idcourse - id курса валюты (из лога)
	 * @param array $sumFields - дополнительные ключи, относящиеся к валютам, кроме ['summa','price','price_in','nds']
	 * @param bool $symbol - добавлять к сумме символ валюты или написание (если символ не указан)
	 *
	 * @return array
	 */
	public static function currencyConvertSpeka(array $speka = [], int $idcourse = 0, array $sumFields = [], bool $symbol = false): array {

		// ключи, обозначающие суммы
		$currencyFields = array_merge([
			'summa',
			'price',
			'price_in',
			'nds',
			'nalog',
			'pozitionTotal',
			'tovarTotal',
			'uslugaTotal',
			'dealFsumma',
			'dealFmarga'
		], $sumFields);
		$before         = $after = '';

		if ($idcourse > 0) {

			$course   = ( new Currency() ) -> courseInfo($idcourse);
			$currency = ( new Currency() ) -> currencyInfo($course['idcurrency']);

			if ($symbol) {

				if ($currency['code'] != '') {
					$before = strtr($currency['code'], self::HTMLCODE);
				}

				elseif ($currency['view'] != '') {
					$after = $currency['view'];
				}

			}

			// обходим массив
			foreach ($speka as $index => $item) {

				if (is_array($item)) {

					// обходим элементы массива
					foreach ($item as $key => $value) {

						if (!is_array($value)) {

							// если ключи являются суммами, то конвертируем
							if (arrayFindInSet($key, $currencyFields) && is_numeric(pre_format($value))) {
								$speka[$index][$key] = $before.' '.num_format(self ::currencyConvert(pre_format($value), $idcourse), 2).' '.$after;
							}

						}
						else {
							$speka[$index][$key] = self ::currencyConvertSpeka($value, $idcourse, $sumFields, $symbol);
						}

					}

				}
				elseif (arrayFindInSet($index, $currencyFields) && is_numeric(pre_format($item))) {
					$speka[$index] = $before.' '.num_format(self ::currencyConvert(pre_format($item), $idcourse), 2).' '.$after;
				};

			}

		}

		return $speka;

	}

	/**
	 * Массив валют и их курса
	 *
	 * @param int|null $id - если указано, то будет выведен массив для конкретной валюты
	 *
	 * @return array
	 */
	public function currencyList(int $id = NULL): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$list = [];

		$s = ( $id > 0 ) ? " AND idcurrency = '$id'" : "";

		$r = $db -> query("SELECT * FROM {$sqlname}currency WHERE id > 0 $s AND identity = '$identity' ORDER BY name");
		while ($data = $db -> fetch($r)) {

			$list[$data['id']] = [
				"id"     => $data['id'],
				"datum"  => $data['datum'],
				"name"   => $data['name'],
				"view"   => $data['view'],
				"code"   => strtr($data['code'], self::HTMLCODE),
				"symbol" => $data['code'] != '' ? strtr($data['code'], self::HTMLCODE) : $data['view'],
				"course" => $data['course']
			];

		}

		return $list;

	}

	/**
	 * Информация по валюте
	 *
	 * @param int|null $id - id валюты
	 *
	 * @return array
	 */
	public function currencyInfo(int $id = NULL): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$currency = [];

		if ($id !== NULL) {

			$currency = (array)$db -> getRow("SELECT * FROM {$sqlname}currency WHERE id = '$id'");

			if (!empty($currency)) {

				/*
				foreach ( $currency as $k => $item ) {
					if ( is_int( $k ) || $k == 'identity' ) {
						unset( $currency[ $k ] );
					}
				}
				*/
				$currency = data2dbtypes($currency, "{$sqlname}currency");

				$currency['symbol'] = ( $currency['code'] == '' ) ? $currency['view'] : strtr($currency['code'], self::HTMLCODE);
				$currency['log']    = self ::currencyLog($id);

			}

		}

		return $currency;

	}

	/**
	 * Информация по Курсу валюты по id валюты
	 *
	 * @param $id - id курса валюты
	 * @return mixed
	 */
	public function courseInfo($id) {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$course = $db -> getRow("SELECT * FROM {$sqlname}currency_log WHERE id = '$id'");

		return  !empty($course) ? data2dbtypes($course, "{$sqlname}currency_log") : [];

		/*
		foreach ($course as $k => $item) {
			if (is_int($k) || $k == 'identity') {
				unset($course[$k]);
			}
		}

		return $course;
		*/

	}

	/**
	 * Добавление/Изменение валюты
	 *
	 * @param       $id - id валюты
	 * @param array $params - параметры
	 *
	 * @return int
	 */
	public function edit($id, array $params = []): int {

		$identity = $this -> identity;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;

		$params['course'] = pre_format(trim($params['course']));
		$params['datum']  = current_datum();

		$courseOld = 0;

		$cparams = $db -> filterArray($params, [
			'id',
			'datum',
			'name',
			'code',
			'view',
			'course',
			'identity'
		]);

		if ($id > 0) {

			$courseOld = $this -> currencyInfo($params['id']);

			unset($cparams['id'], $cparams['identity']);
			$db -> query("UPDATE {$sqlname}currency SET ?u WHERE id = '$id'", arrayNullClean($cparams));

		}
		else {

			$cparams['identity'] = $cparams['identity'] ?? $identity;
			$db -> query("INSERT INTO {$sqlname}currency SET ?u", $cparams);
			$id = (int)$db -> insertId();

		}

		// пишем лог
		$this -> logit([
			'idcurrency' => $id,
			'course'     => $cparams['course'],
			'courseOld'  => $courseOld,
			'iduser'     => $this -> iduser1,
			'identity'   => $this -> identity
		]);

		return $id;

	}

	/**
	 * Удаление записи
	 * @param $id
	 * @return array
	 */
	public function delete($id): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$currency = $this -> currencyInfo($id);

		if ($currency['id'] > 0) {

			$db -> query("DELETE FROM {$sqlname}currency WHERE id = '$id'");
			$db -> query("DELETE FROM {$sqlname}currency_log WHERE idcurrency = '$id'");

			$response = [
				"result"  => "successe",
				"message" => "Успешно"
			];

		}
		else {

			$response = [
				"result" => "error",
				"error"  => [
					"code" => "404",
					"text" => "Валюта не найдена"
				]
			];

		}

		return $response;

	}

	/**
	 * Логгирование изменения курса валюты
	 *
	 * @param array $params
	 *
	 * @return bool
	 */
	public function logit(array $params = []): bool {

		$identity = $this -> identity;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;

		if ($params['course'] != $params['courseOld']) {

			$cparams = $db -> filterArray($params, [
				'id',
				'idcurrency',
				'course',
				'iduser',
				'identity'
			]);

			$cparams['identity'] = ( isset($cparams['identity']) ) ? $cparams['identity'] : $identity;

			$db -> query("INSERT INTO {$sqlname}currency_log SET ?u", $cparams);

		}

		return true;

	}

}