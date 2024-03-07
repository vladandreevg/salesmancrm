<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

namespace Salesman;

/**
 * Класс для вывода справочников в виде массива
 *
 * Class Elements
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Guides {

	/**
	 * Список пользователей
	 *
	 * @param array $opt - опции
	 *                   - haveplan = boolean, фильтр пользователей имеющих план
	 *                   - active = boolean, фильтр пользователей по активности
	 *                   - users = array, фильтр по указанным сотрудникам
	 *                   - exclude = string|array - id пользователей, исключенные из набора
	 *                   - exold = boolean, фильтр отключает показ Деактивированных более 90 дней назад сотрудников
	 * @return array
	 */
	public static function Users(array $opt = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort = '';

		if (isset($opt['exclude'])) {

			if (is_array($opt['exclude'])) {
				$opt['exclude'] = yimplode(",", $opt['exclude']);
			}

			$sort .= " iduser NOT IN (".$opt['exclude'].") and ";

		}

		if ($opt['haveplan']) {
			$sort .= " acs_plan = 'on' and ";
		}
		if ($opt['active']) {
			$sort .= " secrty = 'yes' and ";
		}
		if (is_array($opt['users']) && count($opt['users']) > 0) {
			$sort .= " iduser IN (".yimplode(",", $opt['users']).") and ";
		}

		if ($opt['exold'] && !isset($opt['active'])) {
			$sort .= " (secrty = 'yes' OR (secrty != 'yes' AND adate != '0000-00-00' AND adate >= '".current_datum(90)."')) and ";
		}

		$element = [];

		$result = $db -> getAll("SELECT iduser, title, secrty FROM {$sqlname}user WHERE $sort identity = '$identity' ORDER by title");
		//print $db -> lastQuery();
		foreach ($result as $data) {

			if ($data['secrty'] == 'yes') {
				$element['active'][$data['iduser']] = $data['title'];
			}
			else {
				$element['inactive'][$data['iduser']] = $data['title'];
			}

		}


		return $element;

	}

	/**
	 * Клиент
	 */

	/**
	 * Выбор канала продаж
	 *
	 * @return array
	 */
	public static function Clientpath(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("id", "SELECT name, id FROM {$sqlname}clientpath WHERE identity = '$identity' ORDER by name");

	}

	/**
	 * Выбор типа отношений
	 *
	 * @return array
	 */
	public static function Relation(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}relations WHERE identity = '$identity' ORDER by title");

	}

	/**
	 * Выбор территории
	 *
	 * @param array $opt - опции
	 *                   - exclude = string|array, исключенные территории
	 * @return array
	 */
	public static function Territory(array $opt = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort = '';

		if (isset($opt['exclude'])) {

			if (!is_array($opt['exclude'])) {
				$sort .= " and  idcategory != '".$opt['exclude']."'";
			}
			else {
				$sort .= " and idcategory NOT IN '".yimplode(",", $opt['exclude'], "'")."'";
			}

		}

		$element = $db -> getIndCol("idcategory", "SELECT title, idcategory FROM {$sqlname}territory_cat WHERE identity = '$identity' $sort ORDER by title");

		return $element;

	}

	/**
	 * Выбор отрасли
	 *
	 * @param array $opt - опции
	 *                   - tip = string|array, тип клиента: client, contractor, partner, concurent
	 *                   - exclude = string|array, исключенный тип клиента: client, contractor, partner, concurent
	 * @return array
	 */
	public static function Industry(array $opt = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort = '';

		$opt['tip'] = ( $opt['tip'] != '' || $opt['tip'] == '0' ) ? $opt['sel'] : 'client';

		if (isset($opt['exclude'])) {

			if (!is_array($opt['tip']) && isset($opt['tip'])) {

				if ($opt['tip'] != 'other') {
					$sort .= " and  tip = '".$opt['tip']."'";
				}
				else {
					$sort .= " and  tip != 'client'";
				}

			}
			else {
				$sort .= " and tip IN '".yimplode(",", $opt['tip'], "'")."'";
			}

		}

		if (isset($opt['exclude'])) {

			if (!is_array($opt['exclude'])) {
				$sort .= " and  tip != '".$opt['exclude']."'";
			}
			else {
				$sort .= " and tip NOT IN '".yimplode(",", $opt['exclude'], "'")."'";
			}

		}

		return $db -> getIndCol("idcategory", "SELECT title, idcategory FROM {$sqlname}category WHERE identity = '$identity' $sort ORDER by title");

	}

	/**
	 * Контакт
	 */

	/**
	 * Выбор типа отношений
	 *
	 * @return array
	 */
	public static function Loyalty(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("idcategory", "SELECT title, idcategory FROM {$sqlname}loyal_cat WHERE identity = '$identity' ORDER by title");

	}

	/**
	 * Сделка
	 */

	/**
	 * Выбор направления
	 *
	 * @return array
	 */
	public static function Direction(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}direction WHERE identity = '$identity' ORDER by title");

	}

	/**
	 * Выбор типа сделки
	 *
	 * @return array
	 */
	public static function DealType(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("tid", "SELECT title, tid FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER by title");

	}

	/**
	 * Выбор типа сделки
	 *
	 * @return array
	 */
	public static function Step(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$element = [];

		$result = $db -> getAll("SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			$element[$data['idcategory']] = $data['title'].'% - '.$data['content'];

		}

		return $element;

	}

	public static function Steps(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$element = [];

		$result = $db -> getAll("SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			$element[$data['idcategory']] = [
				"title"   => $data['title'],
				"content" => $data['content']
			];

		}

		return $element;

	}

	/**
	 * Выбор статуса закрытия сделки
	 *
	 * @return array
	 */
	public static function CloseStatus(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("sid", "SELECT title, sid FROM {$sqlname}dogstatus WHERE identity = '$identity' ORDER by title");

	}

	/**
	 * Массив статусов закрытия сделок
	 *
	 * @param string $filter - фильтр по типам ( win | lose )
	 * @return array
	 */
	public static function closeStatusPlus(string $filter = ''): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$element = [];
		$s       = '';

		if (in_array($filter, ['win', 'lose'])) {
			$s = "result_close = '$filter' and ";
		}

		$r = $db -> getAll("SELECT * FROM {$sqlname}dogstatus WHERE $s identity = '$identity' ORDER by title");
		foreach ($r as $item) {

			$element[$item['sid']] = [
				"sid"     => (int)$item['sid'],
				"title"   => $item['title'],
				"content" => $item['content'],
				"result"  => $item['result_close']
			];

		}

		return $element;

	}

	/**
	 * Разное
	 */

	/**
	 * Выбор типа активности
	 *
	 * @param string $type - тип (activ - напоминание, task - история, all - любые)
	 * @return array
	 */
	public static function Activities(string $type = ''): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort    = ( $type == '' ) ? "" : "and tip = '$type'";
		$element = [];

		$res = $db -> query("SELECT title, id, color FROM {$sqlname}activities WHERE identity = '$identity' $sort ORDER by aorder");
		while ($data = $db -> fetch($res)) {

			$element[$data['id']] = $data['title'];

		}

		return $element;

	}

	/**
	 * Выбор результатов звонков
	 *
	 * @param bool $full
	 * @return array
	 */
	public static function CallResults(bool $full = false): array {

		return [
			'ANSWERED'   => ( !$full ) ? 'Отвечен [ANSWERED]' : '<i class="icon-ok-circled green" title="Отвечен"></i><span class="visible-iphone">Отвечен</span>',
			'CANCEL'     => ( !$full ) ? 'Отменен [CANCEL]' : '<i class="icon-minus-circled red" title="Отвечен"></i><span class="visible-iphone">Отменен</span>',
			'NOANSWER'   => ( !$full ) ? 'Не отвечен [NOANSWER]' : '<i class="icon-minus-circled red" title="Не отвечен"></i><span class="visible-iphone">Не отвечен</span>',
			'NO ANSWER'  => ( !$full ) ? 'Не отвечен [NO ANSWER]' : '<i class="icon-minus-circled red" title="Не отвечен"></i><span class="visible-iphone">Не отвечен</span>',
			'TRANSFER'   => ( !$full ) ? 'Переадресация [TRANSFER]' : '<i class="icon-forward-1 gray2" title="Переадресация"></i><span class="visible-iphone">Переадресация</span>',
			'BREAKED'    => ( !$full ) ? 'Прервано [BREAKED]' : '<i class="icon-off red" title="Прервано"></i><span class="visible-iphone">Прервано</span>',
			'BUSY'       => ( !$full ) ? 'Занято [BUSY]' : '<i class="icon-block-1 broun" title="Занято"></i><span class="visible-iphone">Занято</span>',
			'CONGESTION' => ( !$full ) ? 'Перегрузка канала [CONGESTION]' : '<i class="icon-help red" title="Перегрузка канала"></i><span class="visible-iphone">Перегрузка канала</span>',
			'FAILED'     => ( !$full ) ? 'Ошибка соединения [FAILED]' : '<i class="icon-cancel-squared red" title="Ошибка соединения"></i><span class="visible-iphone">Ошибка соединения</span>'
		];

	}

	/**
	 * Выбор типа документа
	 *
	 * @return array
	 */
	public static function Doctype(): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		return $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}contract_type WHERE identity = '$identity' ORDER by title");

	}

	/**
	 * Выбор статусов документа
	 *
	 * @param int $tip
	 * @return mixed
	 */
	public static function Docstatus(int $tip = 0) {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort = ( $tip > 0 ) ? "FIND_IN_SET('$tip', REPLACE(tip,';',',')) > 0 AND" : "";

		return $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}contract_status WHERE $sort identity = '$identity' ORDER by title");

	}

	/**
	 * Выбор категорий прайса
	 *
	 * @param array $opt - опции
	 *                   - cat = string|array - категория или массив категорий
	 * @return array
	 */
	public static function Pricecat(array $opt = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort = '';

		if (isset($opt['cat'])) {

			if (!is_array($opt['cat'])) {
				$sort .= " AND  idcategory = '$opt[cat]";
			}
			else {
				$sort .= " AND idcategory IN '".yimplode(",", $opt['cat'])."'";
			}

		}


		return $db -> getIndCol("idcategory", "SELECT title, idcategory FROM {$sqlname}price_cat WHERE identity = '$identity' $sort ORDER by title");

	}

	/**
	 * Выбор компаний
	 *
	 * @param array $opt
	 * @return array
	 */
	public static function myComps(array $opt = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $sqlname, $identity, $db;

		$sort    = '';
		$element = [];

		if (isset($opt['mcid'])) {
			$sort = " and id = '$opt[mcid]'";
		}

		$result = $db -> getAll("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' $sort ORDER by name_shot");
		foreach ($result as $data) {

			$element[$data['id']] = $data['name_shot'];

		}

		return $element;

	}

	/**
	 * Массив компаний и расч.счетов
	 * @param array $opt
	 *  - mcid - выбор конкретной компании
	 *  - active - только активные р.счета
	 *  - selectedRS - id выбранного счета (будет отмечен параметром selected)
	 * @return array
	 */
	public static function myCompsRS(array $opt = []): array {

		$rootpath = dirname(__DIR__);

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		global $userRights;
		global $sqlname, $identity, $db;

		$list = [];

		$s = (int)$opt['mcid'] > 0 ? "mc.id = '".$credit['mcid']."' AND " : "";

		$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
		$result = $db -> query("SELECT * FROM {$sqlname}mycomps `mc` WHERE $s $x mc.identity = '$identity' ORDER BY mc.name_shot");
		while ($data = $db -> fetch($result)) {

			$rs = [];

			$z   = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";

			if( isset($opt['active']) ){
				$z .= " bloc != 'yes' AND";
			}

			$res = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' AND $z identity = '$identity' ORDER BY title");
			while ($da = $db -> fetch($res)) {

				$rs[] = [
					"id"       => (int)$da['id'],
					"name"     => $da['title'],
					"summa"    => (float)$da['ostatok'],
					"selected" => $da['id'] == $opt['selectedRS'] ? 'selected' : NULL
				];

			}

			$list[(int)$data['id']] = [
				"id"   => (int)$data['id'],
				"name" => $data['name_shot'],
				"rs"   => $rs
			];

		}

		return $list;

	}

}