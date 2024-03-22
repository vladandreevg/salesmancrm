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

use Exception;

/**
 * Класс для обработки выписок из банка
 *
 * Class BankStatement
 *
 * @package Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class BankStatement {

	/**
	 * Обработчик выписок из банка. На входе - содержимое файла выписки, на выходе обработанный массив
	 *
	 * @param string $text
	 * @param bool $save
	 * @return array
	 *
	 */
	public static function convert(string $text, bool $save = true): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		/**
		 * Добавим таблицу журнала, если нужно
		 */
		self ::checkDB();

		// начальные значения
		$list           = $statement = [];
		$i              = 0;
		$currentBalance = 0; // остаток на р/с
		$currentRS      = 0; //р.сч., по которому делаем проводку

		// загружаем выписку из банка
		$data = explode("\n", $text);

		// находим имеющиеся у нас расчетные счета
		$myRS = $db -> getIndCol("rs", "SELECT id, rs FROM {$sqlname}mycomps_recv WHERE rs != '' and rs != '0' and identity = '$identity' ORDER by id");

		// получим ИНН наших компаний
		$myINN = [];
		$r     = $db -> getCol("SELECT innkpp FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER by id");
		foreach ($r as $item) {
			$myINN[] = yexplode(";", (string)$item, 0);
		}

		// массив сопоставления типов данных
		$tips = [
			'СекцияДокумент'    => 'tip',
			'Номер'             => 'number',
			'Дата'              => 'datum',
			'Сумма'             => 'summa',
			'НазначениеПлатежа' => 'title',
			'Плательщик'        => 'from',
			'Плательщик1'       => 'from',
			'ПлательщикИНН'     => 'outINN',
			'ПлательщикСчет'    => 'outRS',
			'Получатель'        => 'to',
			'Получатель1'       => 'to',
			'ПолучательИНН'     => 'inINN',
			'ПолучательСчет'    => 'inRS'
		];

		// проходим строки и получаем предварительный массив с данными
		foreach ($data as $string) {

			// разбиваем строку
			$estring = explode("=", enc_detect($string));

			// обработка данных
			if (array_key_exists($estring[0], $tips)) {
				$list[$i][$tips[$estring[0]]] = untag(trim($estring[1]));
			}
			elseif ($estring[0] == 'КонечныйОстаток') {
				$currentBalance = $estring[1];
			}
			elseif ($estring[0] == 'РасчСчет') {
				$currentRS = strtr($estring[1], $myRS);
			}
			// обработка конца строки
			elseif (stripos(enc_detect($string), 'КонецДокумента') !== false) {
				$i++;
			}

		}

		// формируем окончательный массив
		foreach ($list as $item) {

			$tip     = 'minus';
			$invoice = [];
			$isNew   = true;

			// определяем направление - расход / поступление
			if (in_array($item['inINN'], $myINN)) {
				$tip = "plus";
			}
			if (array_key_exists($item['outRS'], $myRS)) {
				$tip = "minus";
			}
			if (array_key_exists($item['inRS'], $myRS)) {
				$tip = "plus";
			}

			// todo: перемещение между своими счетами
			// if ( in_array( $item['outRS'], array_keys($myRS) ) && in_array( $item['inRS'], array_keys($myRS) ) ) $tip = "move";

			$datum = self ::dateConvert($item['datum']);
			$title = self ::getTitle($item['title'], $tip);
			$teg   = self ::getBudjetTag($item['title'], $tip);

			$year = get_year($datum);
			$mon  = (int)getMonth($datum);

			//поищем клиента по реквизитам ИНН
			$client = ( $tip == "plus" ) ?
				$db -> getRow("SELECT clid, type, title FROM {$sqlname}clientcat WHERE clid > 0 AND (recv LIKE '%$item[outRS]%' OR recv LIKE '%$item[outINN]%') AND identity = '$identity'") :
				$db -> getRow("SELECT clid, type, title FROM {$sqlname}clientcat WHERE clid > 0 AND (recv LIKE '%$item[inRS]%' OR recv LIKE '%$item[inINN]%') AND identity = '$identity'");

			//обработаем клиента
			if (
				(int)$client['clid'] > 0 && /*$title == 'Платеж от клиента' &&*/ in_array($client['type'], [
					'client',
					'person'
				])
			) {

				// найдем счет клиенту по ИНН
				$invoice = $db -> getRow("SELECT * FROM {$sqlname}credit WHERE clid = '$client[clid]' AND summa_credit = '$item[summa]' and DATE_FORMAT(datum_credit, '%Y-%c') = '$year-$mon'");

			}

			// если платёж клиента не распознан
			if ($title == '' && (int)$client['clid'] > 0 && $tip == "plus") {
				$title = 'Платеж от клиента';
			}

			// если платёж клиента не распознан
			if ($title == '' && (int)$client['clid'] > 0 && $tip == "minus") {
				$title = 'Возврат клиенту';
			}

			// прочие, не распознанные расходы
			if ($title == '' && $tip == "minus") {

				$title = 'Платеж контрагенту';
				$teg   = 'поставщик';

			}

			if ($teg == '') {
				$teg = 'поставщик';
			}

			/**
			 * добавим расход в журнал выписок
			 */

			// найдем запись в журнале
			$bank = ( $tip == "minus" ) ? $db -> getRow("SELECT id, bid FROM {$sqlname}budjet_bank WHERE datum = '$datum' AND number = '$item[number]' AND (toINN = '$item[inINN]' OR `to` = '$item[to]') AND identity = '$identity'") : $db -> getRow("SELECT id, bid FROM {$sqlname}budjet_bank WHERE datum = '$datum' AND number = '$item[number]' AND (fromINN = '$item[outINN]' OR `from` = '$item[from]') AND identity = '$identity'");
			$jid  = (int)$bank['id'];
			$bid  = (int)$bank['bid'];

			// найдем категорию от предыдущей заливки по ИНН
			$prevCategory = ( $tip == "minus" ) ? (int)$db -> getOne("SELECT category FROM {$sqlname}budjet_bank WHERE category > 0 AND (toINN = '$item[inINN]' OR `to` = '$item[to]') AND identity = '$identity' ORDER BY id DESC LIMIT 1") : (int)$db -> getOne("SELECT category FROM {$sqlname}budjet_bank WHERE category > 0 AND (fromINN = '$item[outINN]' OR `from` = '$item[from]') AND identity = '$identity' ORDER BY id DESC LIMIT 1");

			//print $db -> lastQuery()."\n";

			// если записи нет, то добавим
			if ($jid == 0 && $save) {

				if (( $tip == "minus" )) {
					$xrs = $myRS[$item['outRS']];
				}
				else {
					$xrs = $myRS[$item['inRS']];
				}

				// добавляем в журнал, если найден р.сч. в базе
				if (in_array($xrs, array_values($myRS))) {

					$jid = self ::edit(0, [
						"number"   => $item['number'],
						"datum"    => $datum,
						"mon"      => $mon,
						"year"     => $year,
						"title"    => $title,
						"content"  => $item['title'],
						// strtr подставляет оригинальное значение RS из выписки, если он не найден в базе, поэтому отключаем
						//"rs"       => ($tip == "minus") ? strtr($item['outRS'], $myRS) : strtr($item['inRS'], $myRS),
						"rs"       => $xrs,
						"from"     => $item['from'],
						"fromRS"   => $item['outRS'],
						"fromINN"  => $item['outINN'],
						"to"       => $item['to'],
						"toRS"     => $item['inRS'],
						"toINN"    => $item['inINN'],
						"tip"      => ( $tip == "minus" ) ? "rashod" : "dohod",
						"summa"    => $item['summa'],
						"clid"     => (int)$client['clid'],
						// расход пока не проведен
						"bid"      => 0,
						// статья расхода не установлена
						"category" => 0,
						"identity" => $identity
					]);

				}


			}
			else {
				$isNew = false;
			}

			$statement[] = [
				"id"         => (int)$jid,
				"isNew"      => $isNew,
				"number"     => $item['number'],
				"tip"        => $tip,
				"title"      => $title,
				"content"    => $item['title'],
				"tag"        => $teg,
				"mon"        => $mon,
				"year"       => $year,
				"datum"      => $datum." 12:00:00",
				"summa"      => $item['summa'],
				"bid"        => $bid,
				"rs"         => ( $tip == "minus" ) ? strtr($item['outRS'], $myRS) : strtr($item['inRS'], $myRS),
				"contragent" => ( $tip == "minus" ) ? $item['to'] : $item['from'],
				"rsOut"      => $item['outRS'],
				"rsIn"       => $item['inRS'],
				"inn"        => ( $tip == "minus" ) ? $item['inINN'] : $item['outINN'],
				"client"     => $client,
				"clid"       => (int)$client['clid'],
				"crid"       => (int)$invoice['crid'],
				"invoice"    => $invoice['invoice'],
				"credit"     => $invoice['summa_credit'],
				"category"   => $prevCategory
			];

		}

		return [
			"statement" => $statement,
			"balance"   => $currentBalance,
			"rs"        => $currentRS
		];

	}

	/**
	 * Добавляяем запись в журнал выписки
	 *
	 * @param int $id
	 * @param array $params
	 * @return int
	 */
	public static function edit(int $id = 0, array $params = []): int {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$allow = [
			"number",
			"datum",
			"mon",
			"year",
			"tip",
			"title",
			"content",
			"rs",
			"from",
			"fromRS",
			"fromINN",
			"to",
			"toRS",
			"toINN",
			"summa",
			"clid",
			"bid",
			"category",
			"identity"
		];

		$data = $db -> filterArray($params, $allow);

		//print_r($data);

		if(empty($data)){
			return 0;
		}

		if ($id == 0) {

			$db -> query("INSERT INTO {$sqlname}budjet_bank SET ?u", $data);
			$id = $db -> insertId();

		}
		else {

			$db -> query("UPDATE {$sqlname}budjet_bank SET ?u WHERE id = '$id'", $data);

		}

		return $id;

	}

	/**
	 * Возвращает информацию по расходу из выписки
	 * @param $id
	 * @return array
	 */
	public static function info($id): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$r = $db -> getRow("SELECT * FROM {$sqlname}budjet_bank WHERE id = '$id'");

		return [
			"date"     => $r['date'],
			"number"   => $r['number'],
			"datum"    => $r['datum'],
			"mon"      => $r['mon'],
			"year"     => $r['year'],
			"tip"      => $r['tip'],
			"title"    => $r['title'],
			"content"  => $r['content'],
			"rs"       => $r['rs'],
			"from"     => $r['from'],
			"fromRS"   => $r['fromRS'],
			"fromINN"  => $r['fromINN'],
			"to"       => $r['to'],
			"toRS"     => $r['toRS'],
			"toINN"    => $r['toINN'],
			"summa"    => $r['summa'],
			"clid"     => (int)$r['clid'],
			"bid"      => (int)$r['bid'],
			"category" => (int)$r['category']
		];

	}

	/**
	 * Проводит полученные данные
	 *
	 * @param int $id
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	public static function toBudjet(int $id, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		$iduser1  = $GLOBALS['iduser1'];

		$params["on"] = ( !isset($params["on"]) ) ? "on" : "";
		$response     = [];

		// информация по записи выписки
		$statement = self ::info($id);

		$arg = [
			"cat"    => $statement['category'],
			"title"  => $statement['title'],
			"des"    => $statement['content'],
			"byear"  => $statement['year'],
			"bmon"   => $statement['mon'],
			"summa"  => $statement['summa'],
			"datum"  => $statement['datum'],
			"forsed" => true,
			"do"     => $params["on"],
			"rs"     => $statement['rs'],
			"iduser" => $iduser1
		];

		// информация по клиенту
		if ((int)$statement['clid'] > 0) {

			$clientTip = getClientData($statement['clid'], 'type');

			if ($clientTip == 'contractor') {
				$arg['conid'] = (int)$statement['clid'];
			}
			elseif ($clientTip == 'partner') {
				$arg['partid'] = (int)$statement['clid'];
			}

		}

		//добавим расход
		if ((int)$statement['bid'] == 0 && (int)$statement['rs'] > 0) {

			$response = Budget ::edit(0, $arg);

			if ($response['data'] > 0) {

				$prvdr = [
					'bid'      => $response['data'],
					"summa"    => $statement['summa'],
					'conid'    => (int)$arg['conid'],
					'partid'   => (int)$arg['partid'],
					"identity" => $identity
				];

				//добавим расход в таблицу dogprovider
				$db -> query("INSERT INTO {$sqlname}dogprovider SET ?u", $prvdr);

			}

		}
		else {
			$response['error'] = [
				"code" => 408,
				"text" => "Не указан Расчетный счет"
			];
		}

		//обновим запись в журнале
		if ((int)$response['data'] > 0) {
			self ::edit($id, ["bid" => (int)$response['data']]);
		}

		$response['summa'] = $statement['summa'];
		$response['tip']   = $statement['tip'];

		return $response;

	}

	/**
	 * Преобразуем дату
	 * @param $date
	 * @return string
	 */
	private static function dateConvert($date): string {

		$d = yexplode(".", $date);

		return $d[2]."-".$d[1]."-".$d[0];

	}

	/**
	 * Получаем название расхода
	 *
	 * @param string $string
	 * @param string $tip
	 * @return string
	 */
	private static function getTitle(string $string, string $tip = 'plus'): string {

		$t = '';

		$sitem = texttosmall($string);

		// платежи от клиентов
		if (
			stripos($sitem, 'оплата по счет') !== false ||
			stripos($sitem, 'согласно счет') !== false ||
			stripos($sitem, 'оплата счет') !== false ||
			stripos($sitem, 'по счет') !== false ||
			stripos($sitem, 'по счёт') !== false ||
			stripos($sitem, 'по договор') !== false ||
			stripos($sitem, 'услуги по сч') !== false ||
			stripos($sitem, 'счет №') !== false
		) {
			$t = ( $tip == 'plus' ) ? 'Платеж от клиента' : 'Платеж контрагенту';
		}

		// банковская комиссия
		elseif (stripos($sitem, 'комисси') !== false) {
			$t = 'Комиссия';
		}

		// банковская выплата процентов на остаток
		elseif (stripos($sitem, 'проценты за') !== false) {
			$t = 'Процент на остаток';
		}

		// переводы со счета на счет
		elseif (stripos($sitem, 'перевод между счет') !== false) {
			$t = 'Переводы внутренние';
		}

		// зарплата
		elseif (
			stripos($sitem, 'заработная') !== false ||
			stripos($sitem, 'зарплат') !== false ||
			stripos($sitem, 'зпл') !== false
		) {
			$t = 'Выдача зарплаты';
		}

		// аренда офиса
		elseif (stripos($sitem, 'аренд') !== false) {
			$t = 'Аренда';
		}

		// платежи в налоговую, бюджет
		elseif (
			stripos($sitem, 'налог') !== false ||
			stripos($sitem, 'ифнс') !== false ||
			stripos($sitem, 'взнос') !== false ||
			stripos($sitem, 'ндфл') !== false ||
			stripos($sitem, 'уфк') !== false
		) {
			$t = 'Налоги и выплаты';
		}

		return $t;

	}

	/**
	 * Получаем ключевое слово для статьи бюджета
	 *
	 * @param string $string
	 * @param string $tip
	 * @return string
	 */
	private static function getBudjetTag(string $string, string $tip = 'plus'): string {

		$t = '';

		$sitem = texttosmall($string);

		// платежи от клиентов
		if (
			stripos($sitem, 'оплата по счет') !== false ||
			stripos($sitem, 'согласно счет') !== false ||
			stripos($sitem, 'оплата счет') !== false ||
			stripos($sitem, 'по счет') !== false ||
			stripos($sitem, 'по счёт') !== false ||
			stripos($sitem, 'по договор') !== false ||
			stripos($sitem, 'услуги по сч') !== false
		) {
			$t = ( $tip == 'plus' ) ? 'клиент' : 'поставщик';
		}

		// банковская комиссия, выплата процентов на остаток
		elseif (stripos($sitem, 'комисси') !== false) {
			$t = 'поставщик';
		}

		// банковская комиссия, выплата процентов на остаток
		elseif (stripos($sitem, 'проценты за') !== false) {
			$t = 'инвестиции';
		}

		// переводы со счета на счет
		elseif (stripos($sitem, 'перевод между счет') !== false) {
			$t = 'перемещение';
		}

		// зарплата
		elseif (
			stripos($sitem, 'заработная') !== false ||
			stripos($sitem, 'зарплат') !== false ||
			stripos($sitem, 'зпл') !== false
		) {
			$t = 'зарплат';
		}

		// аренда офиса
		elseif (stripos($sitem, 'аренд') !== false) {
			$t = 'аренда';
		}

		// платежи в налоговую, бюджет
		elseif (
			stripos($sitem, 'налог') !== false ||
			stripos($sitem, 'ифнс') !== false ||
			stripos($sitem, 'взнос') !== false ||
			stripos($sitem, 'ндфл') !== false ||
			stripos($sitem, 'уфк') !== false
		) {
			$t = 'налог';
		}

		return $t;

	}

	/**
	 * Добавляет необходимую таблицу в БД
	 * @return bool
	 */
	public static function checkDB(): bool {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$database = $GLOBALS['database'];

		/**
		 * Добавим таблицу журнала, если нужно
		 */
		$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}budjet_bank'");
		if ($da == 0) {

			$db -> query("
				CREATE TABLE `{$sqlname}budjet_bank` (
					`id` INT(20) NOT NULL AUTO_INCREMENT,
					`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'метка времени',
					`number` VARCHAR(50) NULL DEFAULT NULL COMMENT 'номер документа',
					`datum` DATE NULL DEFAULT NULL COMMENT 'дата проводки',
					`mon` VARCHAR(2) NULL DEFAULT NULL COMMENT 'месяц',
					`year` VARCHAR(4) NULL DEFAULT NULL COMMENT 'год',
					`tip` VARCHAR(10) NULL DEFAULT NULL COMMENT 'направление расхода - dohod, rashod',
					`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'название расхода',
					`content` TEXT NULL COMMENT 'описание расхода',
					`rs` INT(20) NULL DEFAULT NULL COMMENT 'id расчетного счета',
					`from` TEXT NULL COMMENT 'название плательщика',
					`fromRS` VARCHAR(20) NULL DEFAULT NULL COMMENT 'р.с. плательщика',
					`fromINN` VARCHAR(10) NULL DEFAULT NULL COMMENT 'инн плательщика',
					`to` TEXT NULL COMMENT 'название получателя',
					`toRS` VARCHAR(20) NULL DEFAULT NULL COMMENT 'р.с. получателя',
					`toINN` VARCHAR(10) NULL DEFAULT NULL COMMENT 'инн получателя',
					`summa` FLOAT(20,2) NULL DEFAULT NULL COMMENT 'сумма расхода',
					`clid` INT(20) NULL DEFAULT NULL COMMENT 'id связанного клиента',
					`bid` INT(20) NULL DEFAULT NULL COMMENT 'id связанной записи в бюджете',
					`category` INT(20) NULL DEFAULT NULL COMMENT 'id статьи расхода',
					`identity` INT(20) NULL DEFAULT '1',
					PRIMARY KEY (`id`)
				)
				COMMENT='Журнал банковской выписки'
				COLLATE='utf8_general_ci'
				ENGINE=InnoDB
			");

		}

		return true;

	}

	/**
	 * Список записей из банковской выписки
	 * @param array $params
	 *  - bool bypage - делить на страницы
	 *  - int page - текущая страница
	 *  - do - статус проведения (NULL|do|nodo)
	 *  - int year - фильтр по году
	 *  - int mon - фильтр по месяцу
	 *  - array category - по статье расхода
	 *  - array rs - по расчетному счету
	 *  - word - поиск по названию/содержимому расхода
	 * @return array
	 * @throws Exception
	 */
	public static function getStatement(array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		/**
		 * Добавим таблицу журнала, если нужно
		 */
		self ::checkDB();

		$do       = $params['do'];
		$category = (array)$params['category'];
		$year     = (int)$params['year'];
		$mon      = (int)$params['mon'];
		$rs       = (array)$params['rs'];
		$page     = (int)$params['page'];
		$word     = urldecode($params['word']);

		$sort           = '';
		$count_pages    = 0;
		$lines_per_page = 50;

		// находим имеющиеся у нас расчетные счета
		$myRS = $db -> getIndCol("id", "SELECT id, title FROM {$sqlname}mycomps_recv WHERE rs != '' and rs != '0' and identity = '$identity' ORDER by id");

		if ($do == 'do') {
			$sort .= " and bank.bid > 0";
		}
		if ($do == 'nodo') {
			$sort .= " and bank.bid = 0";
		}

		if ($word != '') {
			$sort .= " and (bank.title LIKE '%$word%' or bank.content LIKE '%$word%')";
		}

		if ($mon > 0) {
			$sort .= " and bank.mon = '$mon'";
		}

		if (!empty($rs)) {
			$sort .= " and bank.rs IN (".implode(",", $rs).")";
		}
		if (!empty($category)) {
			$sort .= " and bank.category IN (".implode(",", $category).")";
		}

		$query = "
			SELECT 
			    bank.id,
			    bank.bid,
			    bank.clid,
			    bank.summa,
			    bank.datum,
			    bank.year,
			    bank.mon,
			    bank.category,
			    bank.tip,
			    bank.title,
			    bank.from,
			    bank.to,
			    bank.content,
			    bank.rs,
			    bj.did,
			    bj.do,
			    deal.title as deal,
			    cc.title as client,
			    cc.type as type,
			    bc.title as categoryName,
			    bc.tip as categoryTip,
			    bc.subid as subid
			FROM {$sqlname}budjet_bank `bank`
			    LEFT JOIN {$sqlname}budjet `bj` ON bj.id = bank.bid
				LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = bank.clid
				LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = bj.did
				LEFT JOIN {$sqlname}budjet_cat `bc` ON bc.id = bank.category
			WHERE 
			    bank.year = '$year' 
			    $sort and 
			    bank.identity = '$identity' 
			ORDER by  CAST(bank.mon AS UNSIGNED) DESC, bank.datum DESC
		";

		if ($params['bypage']) {

			$xquery    = "
				SELECT 
				    COUNT(bank.id)
				FROM {$sqlname}budjet_bank `bank`
				WHERE 
				    bank.year = '$year' 
				    $sort and 
				    bank.identity = '$identity' 
				ORDER by bank.mon DESC, bank.datum DESC
			";
			$all_lines = $db -> getOne($xquery);

			if (empty($page) || $page <= 0) {
				$page = 1;
			}
			$page_for_query = $page - 1;
			$lpos           = $page_for_query * $lines_per_page;

			$count_pages = ceil($all_lines / $lines_per_page);

			$result = $db -> query($query." LIMIT $lpos,$lines_per_page");

		}
		else {

			$result = $db -> query($query);

		}

		while ($data = $db -> fetch($result)) {

			$tip        = NULL;
			$invoice    = [];
			$isnoclient = false;
			$color      = NULL;
			$deal       = NULL;

			//обработаем клиента
			if (
				(int)$data['clid'] > 0 && in_array($data['type'], [
					'client',
					'person'
				])
			) {

				// найдем счет клиенту по ИНН
				$invoice = $db -> getRow("SELECT crid, invoice, did FROM {$sqlname}credit WHERE clid = '$data[clid]' AND summa_credit = '$data[summa]' and DATE_FORMAT(datum_credit, '%Y-%c') = '$data[year]-$data[mon]'");

				if ((int)$invoice['did'] > 0) {

					$deal = current_dogovor((int)$invoice['did']);

				}

			}

			if ($data['tip'] == 'dohod') {
				$tip = '<b class="green" title="Поступление"><i class="icon-up-big green"></i></b>';
			}
			elseif ($data['tip'] == 'rashod') {
				$tip = '<b class="red" title="Расход"><i class="icon-down-big red"></i></b>';
			}

			if (
				(int)$data['bid'] == 0 &&
				$data['title'] != 'Переводы внутренние' &&
				!in_array($data['type'], [
					'client',
					'person'
				])
			) {

				$tip        .= '<i class="icon-block-1 red" title="Не добавлен в расходы"></i>';
				$isnoclient = true;
				$color      = 'redbg-sub';

			}
			elseif (
				(int)$data['bid'] == 0 && in_array($data['type'], [
					'client',
					'person'
				])
			) {

				$tip .= '<i class="icon-block-1 gray" title="Платежи клиентов не обрабатываются"></i>';

			}
			elseif ((int)$data['bid'] == 0 && $data['title'] == 'Переводы внутренние') {

				$tip        .= '<i class="icon-block-1 gray" title="Данный вид расходов не обрабатывается"></i>';
				$isnoclient = false;

			}

			$list[] = [
				"id"         => (int)$data['id'],
				"bid"        => (int)$data['bid'] > 0 ? (int)$data['bid'] : NULL,
				"datum"      => $data['datum'],
				"datumf"     => format_date_rus($data['datum']),
				"period"     => $data['mon'].".".$data['year'],
				"title"      => $data['title'],
				"content"    => $data['content'],
				"summa"      => $data['summa'],
				"summaf"     => num_format($data['summa']),
				"tip"        => $tip,
				"category"   => $data['categoryName'],
				"rs"         => (int)$data['rs'],
				"rsName"     => $myRS[(int)$data['rs']] ?? $data['rs'],
				//"rsName"     => strtr( $data['rs'], $myRS ),
				"clid"       => (int)$data['clid'] > 0 ? (int)$data['clid'] : NULL,
				"client"     => $data['client'],
				"did"        => (int)$invoice['did'],
				"deal"       => $deal,
				"iduser"     => (int)$data['iduser'],
				"user"       => current_user($data['iduser']),
				"color"      => $data['title'] == 'Платеж от клиента' ? "graybg-sub" : $color,
				"mon"        => (int)$data['mon'],
				"year"       => (int)$data['year'],
				"contragent" => $data['tip'] == 'dohod' ? $data['from'] : $data['to'],
				"crid"       => (int)$invoice['crid'],
				"invoice"    => $invoice['invoice'],
				"isdo"       => $data['do'] == 'on' ? true : NULL,
				"isnoclient" => $isnoclient,
			];

		}

		return [
			"list"    => $list,
			"page"    => $page,
			"pageall" => (int)$count_pages,
			"valuta"  => $valuta
		];

	}

}