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

use phpDocumentor\Reflection\Types\This;
use SafeMySQL;

/**
 * Класс для работы с Прайсом
 *
 * Class Price
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Price {

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
	 * @var false|string
	 */
	//private $rootpath;

	public const TYPES = [
		0 => 'Товар',
		1 => 'Услуга',
		2 => 'Материал'
	];

	/**
	 * Akt constructor.
	 */
	public function __construct() {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> db       = new SafeMySQL($this -> opts);

	}

	/**
	 * Данные по позиции прайса
	 *
	 * @param int $id - id прайсовой позиции
	 * @param string $artikul - артикул позиции
	 *
	 * @return array
	 * good result
	 *         - [result] = Success
	 *         - [data] = price(array)
	 *         - [prid] = id
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error] = Запись не найдена
	 *         - [data] = id
	 *
	 * ```php
	 * $Price = \Salesman\Price::info($id,$artikul);
	 * ```
	 */
	public static function info(int $id, string $artikul = ''): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$prid = 0;

		if ($id > 0) {
			$prid = (int)$db -> getOne("SELECT n_id FROM {$sqlname}price WHERE n_id = '$id' AND identity = '$identity'");
		}
		elseif( !empty($artikul) ) {
			$prid = (int)$db -> getOne("SELECT n_id FROM {$sqlname}price WHERE artikul = '$artikul' AND identity = '$identity'");
		}

		//print $db -> lastQuery();

		if ($prid > 0) {

			$fields = [];
			$result = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
			while ($data = $db -> fetch( $result )) {

				if($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

					$fields[] = [
						"field" => $data['fld_name'],
						"title" => $data['fld_title'],
						"value" => $data['fld_var'],
					];

				}

			}

			//Если склад активен, то выдаем информацию из него
			$isSklad = $db -> getOne("SELECT active FROM {$sqlname}modules WHERE mpath = 'modcatalog' AND identity = '$identity'");
			if ($isSklad == 'on') {

				$price = Storage ::info($prid, ["identity" => $identity])['price'];

				// берем признак типа у нулевого каталога
				$pcat = self ::parentCatalog($price['folder']);
				$cat  = $db -> getRow("SELECT * FROM {$sqlname}price_cat where idcategory = '$pcat' and identity = '$identity'");

				$price['type']     = $cat['type'];
				$price['typename'] = !is_null($cat['type']) ? self::TYPES[$cat['type']] : NULL;

			}
			else {

				//данные по позиции
				$res                = $db -> getRow("SELECT * FROM {$sqlname}price WHERE n_id = '$prid' AND identity = '$identity'");
				$price['prid']      = (int)$res["n_id"];
				$price['artikul']   = $res["artikul"];
				$price['datum']   = $res["datum"];
				$price['title']     = clean($res["title"]);
				$price['descr']     = $res["descr"];
				$price['price_in']  = (float)$res["price_in"];
				$price['edizm']     = $res["edizm"];
				$price['folder']    = (int)$res["pr_cat"];
				$price['nds']       = (float)$res["nds"];
				$price['archive']   = $res["archive"];
				$price['isArchive'] = $res["archive"] == 'yes';

				foreach ($fields as $field) {

					$price[$field['field']] = (float)$res[$field['field']];

				}

				// берем признак типа у нулевого каталога
				$pcat = self ::parentCatalog($res['pr_cat']);
				$cat  = $db -> getRow("SELECT * FROM {$sqlname}price_cat where idcategory = '$pcat' and identity = '$identity'");

				$price['type']     = $cat['type'];
				$price['typename'] = !is_null($cat['type']) ? self::TYPES[(int)$cat['type']] : NULL;
				$price['category'] = $db -> getOne("SELECT title FROM {$sqlname}price_cat WHERE idcategory = '$res[pr_cat]' and identity = '$identity'");

			}

			$response = [
				"result" => "Success",
				"data"   => $price,
				'prid'   => $id
			];

		}
		else {

			$response = [
				'result' => 'Error',
				'error'  => "Запись не найдена",
				'data'   => $id
			];

		}

		return $response;

	}

	/**
	 * Получение имен активных полей прайса
	 *
	 * @return array
	 */
	public static function fields(): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$field = [];

		$field["prid"]     = 'id';
		$field["artikul"]  = 'Артикул';
		$field["title"]    = 'Наименование';
		$field["descr"]    = 'Описание';
		$field["edizm"]    = 'Ед.изм.';
		$field["datum"]    = 'Дата добавления';
		$field["category"] = 'Категория';
		$field["nds"]      = 'НДС';

		$result = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip = 'price' AND fld_on = 'yes' and identity = '$identity' ORDER BY fld_order");
		while ($data = $db -> fetch($result)) {

			$field[$data['fld_name']] = [
				"title"    => $data['fld_title'],
				"values"   => $data['fld_var'],
				"required" => $data['fld_required']
			];

			if($data['fld_name'] != 'price_in') {

				$field['fields'][$data['fld_name']] = [
					"field" => $data['fld_name'],
					"title" => $data['fld_title'],
					"value" => $data['fld_var'],
				];

			}

		}

		return $field;

	}

	/**
	 * Добавление/Редактирование позиции прайса
	 *
	 * @param       $id - id позиции
	 * @param array $params - массив с параметрами
	 *                      - artikul - артикул
	 *                      - title - название
	 *                      - descr - описание
	 *                      - price_in - закупочная цена
	 *                      - price_1 - розница
	 *                      - price_2 - уровень цены 1
	 *                      - price_3 - уровень цены 2
	 *                      - price_4 - уровень цены 3
	 *                      - price_5 - уровень цены 4
	 *                      - edizm - единица измерения
	 *                      - nds - размер ндс
	 *                      - pr_cat - категория прайса
	 *
	 * @return array
	 * good result
	 *         - result = Success
	 *         - text = Позиция добавлена/изменена
	 *
	 * error result
	 *         - result = Error
	 *         - text = Ошибка добавления / Позиция не найдена
	 *
	 * ```php
	 * $Price = \Salesman\Price::edit($id,$params);
	 * ```
	 */
	public function edit($id, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$post = $params;

		$fields = [];
		$result = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
		while ($data = $db -> fetch( $result )) {

			if($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

				$fields[] = [
					"field" => $data['fld_name'],
					"title" => $data['fld_title'],
					"value" => $data['fld_var'],
				];

			}

		}

		//поля, которые есть в таблице
		$allowed = [
			'artikul',
			'title',
			'descr',
			'price_in',
			'price_1',
			'price_2',
			'price_3',
			'price_4',
			'price_5',
			'edizm',
			'nds',
			'archive',
			'pr_cat',
			'identity'
		];

		foreach ($fields as $field) {
			$allowed[] = $field['field'];
			print $params[$field['field']]."\n";
			$params[$field['field']] = pre_format($params[$field['field']]);
			print $params[$field['field']]."\n";
		}

		$params['pr_cat']   = (int)$params['idcategory'];

		//print_r($params);

		$xparams = (array)data2dbtypes($params, "{$sqlname}price");
		$params = $xparams;

		//print_r($xparams);

		$params['archive']  = ( $params['archive'] == 'yes' ) ? 'yes' : 'no';

		$params['new_folder'] = untag($params['new_folder']);

		//добавляем категорию
		if ($params['new_folder'] != '') {

			$params['pr_car'] = self ::addCategoryFromTitle($params['new_folder']);

		}

		//очищаем от мусора и случайных элементов
		$params = $db -> filterArray($params, $allowed);

		//новая запись
		if ($id == 0) {

			$params = $hooks -> apply_filters("price_addfilter", $params);

			$params['identity'] = $identity;

			$db -> query("INSERT INTO {$sqlname}price SET ?u", arrayNullClean($params));
			$id = $db -> insertId();

			if ($hooks) {
				$hooks -> do_action("price_add", $post, $params);
			}

			if ($id > 0) {

				$result = "Success";
				$text   = "Позиция добавлена";

			}
			else {

				$result = "Error";
				$text   = "Ошибка добавления";

			}

		}
		//обновление записи
		else {

			//проверим наличие записи по id
			$prid = (int)$db -> getOne("SELECT * FROM {$sqlname}price WHERE n_id = '$id' AND identity = '$identity'");

			if ($prid > 0) {

				unset($params['identity']);

				$params = $hooks -> apply_filters("price_editfilter", $params);

				$db -> query("UPDATE {$sqlname}price SET ?u WHERE n_id = '$id' and identity = '$identity'", $params);
				//print $db -> lastQuery();

				if ($hooks) {
					$hooks -> do_action("price_edit", $post, $params);
				}

				$result = "Success";
				$text   = "Позиция обновлена";

			}
			else {

				$result = "Error";
				$text   = "Позиция не найдена";

			}

		}

		return [
			'result' => $result,
			'text'   => $text,
			'data'   => $id
		];

	}

	/**
	 * Удаление позиции прайса
	 *
	 * @param $id - id позиции прайса
	 *
	 * @return array - массив с параметрами
	 * good result
	 *         - result = Success
	 *         - text =Готово
	 *         - data = id
	 *
	 * error result
	 *         - result = Error
	 *         - text = Запись не найдена
	 *         - data id
	 *
	 * ```php
	 * $Price = \Salesman\Price::delete($id);
	 * ```
	 */
	public static function delete($id): array {

		global $hooks;

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$n_id = (int)$db -> getOne("SELECT n_id FROM {$sqlname}price WHERE n_id = '$id' AND identity = '$identity'");

		if ($n_id > 0) {

			if ($hooks) {
				$hooks -> do_action("price_delete", $did);
			}

			$db -> query("DELETE FROM {$sqlname}price WHERE n_id = '$id' AND identity = '$identity'");

			$response = [
				"result" => "Success",
				"text"   => "Готово",
				"data"   => $id
			];

		}
		else {

			$response = [
				'result' => 'Error',
				'text'   => "Запись не найдена",
				'data'   => $id
			];

		}

		return $response;

	}

	/**
	 * Данные категории
	 *
	 * @param int $id
	 * @return array
	 */
	public static function infoCategory(int $id): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$cat = $db -> getRow("SELECT * FROM {$sqlname}price_cat WHERE idcategory = '$id' and identity = '$identity'");

		if ((int)$cat['idcategory'] > 0) {

			foreach ($cat as $k => $item) {
				if (is_numeric($k) || $k == 'identity') {
					unset($cat[$k]);
				}
			}

			$issub = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}price_cat WHERE sub = '$id' and identity = '$identity'");

			$cat['haveSub'] = $issub > 0;

			$response = [
				"result" => "Success",
				"data"   => $cat,
				'id'     => $id
			];

		}
		else {

			$response = [
				'result' => 'Error',
				'error'  => "Запись не найдена",
				'id'     => $id
			];

		}

		return $response;

	}

	/**
	 * Добавление новой категории по названию
	 *
	 * @param string $title - название категории
	 * @param int $sub - id родительской категории
	 *
	 * @return int - id добавленной записи
	 */
	public static function addCategoryFromTitle(string $title, int $sub = 0): int {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];

		$id = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}price_cat WHERE title = '$title' AND identity = '$identity'");

		if ($id == 0) {

			$db -> query("INSERT INTO {$sqlname}price_cat SET ?u", arrayNullClean([
				'title'    => untag($title),
				'sub'      => $sub,
				'identity' => $identity
			]));
			$id = (int)$db -> insertId();

		}

		return $id;

	}

	/**
	 * Добавление/Редактирование категорий прайса
	 *
	 * @param       $id - id категории прайса(для редактирования)
	 * @param array $params - массив с параметрами
	 *                      - title - название
	 *                      - sub - id родительской категории
	 *
	 * @return array
	 * good result
	 *          - result = Success
	 *          - text = Добавлено/обновлено
	 *          - data = id
	 *
	 * error result
	 *          - result= Error
	 *          - text = Позиция не найдена
	 *          - data = id
	 */
	public function editCategory($id, array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$arg = [
			'title'    => untag($params['title']),
			'sub'      => $params['sub'] ?? 0,
			'type'     => (int)$params['sub'] == 0 ? $params['type'] : NULL,
			'identity' => $identity
		];

		if ($id == 0) {

			$db -> query("INSERT INTO {$sqlname}price_cat SET ?u", arrayNullClean($arg));
			$id = $db -> insertId();

			$text = "Добавлено";

			$response = [
				"result" => "Success",
				"text"   => $text,
				"data"   => (int)$id
			];

		}
		else {

			$id = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}price_cat WHERE idcategory = '$id' AND identity = '$identity'");

			if ($id > 0) {

				unset($arg['identity']);

				$db -> query("UPDATE {$sqlname}price_cat SET ?u WHERE idcategory = '$id' and identity = '$identity'", arrayNullClean($arg));

				$text = "Обновлено";

				$response = [
					"result" => "Success",
					"text"   => $text,
					"data"   => $id
				];

			}
			else {

				$response = [
					"result" => "Error",
					"error"  => "Позиция не найдена",
					"data"   => $id
				];

			}

		}

		return $response;

	}

	/**
	 * Удаление категории
	 *
	 * Если категория содержит подкатегории, то они будут перемещены вверх
	 *
	 * @param $id - id категории
	 *
	 * @return string
	 *  $mes = Запись удалена. Перемещено - ".$good." позиций
	 */
	public static function deleteCategory($id): string {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		$good     = 0;

		//старшая категория у текущей
		$folder = (int)$db -> getOne("SELECT sub FROM {$sqlname}price_cat WHERE idcategory = '$id' AND identity = '$identity'");

		$folder = ( $folder > 0 ) ? $folder : 0;

		//удаляем категорию
		$db -> query("DELETE FROM {$sqlname}price_cat WHERE idcategory = '$id' AND identity = '$identity'");

		//находим все вложенные категории
		$subcat = $db -> getCol("SELECT idcategory FROM {$sqlname}price_cat WHERE sub = '$id' AND identity = '$identity'");

		//проходим эти категории
		foreach ($subcat as $sub) {

			$db -> query("UPDATE {$sqlname}price_cat SET sub = '$folder' WHERE idcategory = '$sub' AND identity = '$identity'");

		}


		//все записи из категории перемещаем в категорию "$folder"
		//$num = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}price WHERE pr_cat = '$id' AND identity = '$identity'");

		//проходим все записи этой катеогрии поштучно
		$result = $db -> query("SELECT COUNT(*) FROM {$sqlname}price WHERE pr_cat = '$id' AND identity = '$identity'");
		while ($data = $db -> fetch($result)) {

			$db -> query("UPDATE {$sqlname}price SET pr_cat = '$folder' WHERE n_id = '".$data['n_id']."' and identity = '$identity'");
			$good++;

		}

		return "Запись удалена.<br>Перемещено - ".$good." позиций";

	}

	/**
	 * Возвращает массив со списком категорий
	 *
	 * @return array
	 */
	public static function listCategory(): array {

		return self ::getPriceCatalog();

	}

	/**
	 * Возвращает структуру каталога, но без вложения подкаталогов в основной каталог
	 *
	 * @param int $id
	 * @param int $level
	 * @param array $ures
	 *
	 * @return array
	 */
	public static function getPriceCatalog(int $id = 0, int $level = 0, array $ures = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$sort     = $GLOBALS['sort'];
		$maxlevel = preg_replace("/[^0-9]/", "", $GLOBALS['maxlevel']);
		//$maxlevel = 5;

		global $ures;

		$sort .= ( $id > 0 ) ? " and sub = '$id'" : " and sub = '0'";

		if ($maxlevel != '' && $level > $maxlevel) {
			goto la;
		}

		$re = $db -> query("SELECT * FROM {$sqlname}price_cat WHERE idcategory > 0 $sort and identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			$ures[] = [
				"id"       => (int)$da["idcategory"],
				"title"    => $da["title"],
				"type"     => $da["type"],
				"typename" => !is_null($da["type"]) ? self::TYPES[(int)$da["type"]] : NULL,
				"level"    => $level,
				"sub"      => (int)$da["sub"]
			];

			if ((int)$da['idcategory'] > 0) {

				$level++;
				self ::getPriceCatalog((int)$da['idcategory'], $level);
				$level--;

			}

		}

		la:

		return (array)$ures;

	}

	/**
	 * Рекрсивно возвращает массив со всеми категориями и подкатегориями прайс-листа.
	 * Можно задать стартовый id категории. Тогда будет возвращена только эта ветка
	 *
	 * @param int $id
	 * @param int $level
	 *
	 * @return array
	 */
	public static function getCatalog(int $id = 0, int $level = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$category = [];

		$sort = ( $id > 0 ) ? "sub = '$id' AND" : "sub = 0 AND";

		$re = $db -> query("SELECT * FROM {$sqlname}price_cat WHERE $sort identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			//найдем категории, в которых данная категория является главной
			$count  = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}price_cat WHERE idcategory = '$da[idcategory]' AND identity = '$identity'");
			$xcount = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}price WHERE pr_cat = '$da[idcategory]' AND identity = '$identity'");

			$subcat = ( $count > 0 ) ? self ::getCatalog($da['idcategory'], $level + 1) : [];

			$category[(int)$da["idcategory"]] = [
				"id"       => (int)$da["idcategory"],
				"title"    => $da["title"],
				"type"     => $da["type"],
				"typename" => !is_null($da["type"]) ? self::TYPES[(int)$da["type"]] : NULL,
				"level"    => $level,
				"count"    => $xcount
			];

			//если есть подкатегории, то добавим их рекурсивно
			if (!empty($subcat)) {
				$category[$da["idcategory"]]["subcat"] = $subcat;
			}

		}

		return $category;

	}

	/**
	 * Обработка готового массива каталогов, полученного в Price::getCatalog();
	 * Происходит расчет количества позиций в каталоге с учетом вложенных категорий
	 * @param $catalog
	 * @return array
	 */
	public static function getCatalogCounts($catalog): array {

		foreach ($catalog as $idA => $cats) {

			$countA = $cats['count'];

			// второй уровень
			foreach ($cats['subcat'] as $idB => $cat) {

				$countA += (int)$cat['count'];
				$countB = (int)$cat['count'];

				// третий уровень (максимальный)
				foreach ($cat['subcat'] as $c) {

					$countA += (int)$c['count'];
					$countB += (int)$c['count'];

				}

				$catalog[$idA]['subcat'][$idB]['count'] = $countB;

			}

			$catalog[$idA]['count'] = $countA;

		}

		return $catalog;

	}

	/**
	 * Рекурсивно возвращает массив со всеми категориями и подкатегориями прайс-листа.
	 *
	 * Можно задать стартовый id категории. Тогда будет возвращена только эта ветка.
	 * Оформление задается с помощью параметров $template и $block. По умолчанию:
	 * $block    = <ul>{{html}}</ul> - обертка блока категорий
	 * $template = <li data-id="{{id}}><a href="javascript:void(0)" title="{{title}}" class="category"
	 * data-id="{{id}}">{{title}}</a>{{sub}}</li> - шаблон списка где {{sub}} - вставка подкатегорий (применяется
	 * основной шаблон)
	 *
	 * @param int $id
	 * @param string $template
	 * @param string $block
	 *
	 * @return string
	 */
	public static function getCatalogHtml(int $id = 0, string $template = '<li data-id="{{id}}"><a href="javascript:void(0)" title="{{title}}" class="category" data-id="{{id}}">{{title}}</a>{{sub}}</li>', string $block = '<ul>{{html}}</ul>'): string {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$html = '';

		$sort = ( $id > 0 ) ? "sub = '$id' AND" : "sub = 0 AND";

		$re = $db -> query("SELECT * FROM {$sqlname}price_cat WHERE $sort identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			//найдем категории, в которых данная категория является главной
			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}price_cat WHERE idcategory = '$da[idcategory]' AND identity = '$identity'");

			$subcat = ( $count > 0 ) ? self ::getCatalogHtml((int)$da['idcategory']) : [];

			$tags = [
				"{{id}}"    => (int)$da['idcategory'],
				"{{title}}" => $da["title"]
			];

			$html .= strtr($template, $tags);

			//если есть подкатегории, то добавим их рекурсивно
			$html = strtr($html, ["{{sub}}" => $subcat]);

		}

		if ($html != '') {
			$html = str_replace("{{html}}", $html, $block);
		}

		return $html;

	}

	/**
	 * Поиск главной категории (нулевого уровня)
	 * @param int $id
	 * @return int
	 */
	public static function parentCatalog(int $id): int {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$parent = (int)$db -> getOne("SELECT sub FROM {$sqlname}price_cat WHERE idcategory = '$id' AND identity = '$identity' ORDER BY title");

		if ($parent == 0) {

			return $id;

		}

		return self::parentCatalog($parent);

	}

	/**
	 * Возвращает массив со всеми вложенными категориями
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public static function getCatalogTree(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$category = [];

		$sort = ( $id > 0 ) ? "sub = '$id' AND" : "sub = 0 AND";

		$re = $db -> query("SELECT * FROM {$sqlname}price_cat WHERE $sort identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			//найдем категории, в которых данная категория является главной
			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}price_cat WHERE idcategory = '$da[idcategory]' AND identity = '$identity'");

			$subcat = ( $count > 0 ) ? self ::getCatalogTree((int)$da['idcategory']) : [];

			$category[] = $da["idcategory"];

			//если есть подкатегории, то добавим их рекурсивно
			if (!empty($subcat)) {

				foreach ($subcat as $sub) {

					$category[] = $sub;

				}

			}

		}

		return $category;

	}

	/**
	 * Возвращает массив, в котором
	 * - category - линейный массив категорий (все подкатегории вынесены в основной массив)
	 * - empty    - массив пустых категорий (без позиций прайса)
	 *
	 * @param $catalog
	 * @return array
	 * @example
	 * ```php
	 * $catalog  = Price::getCatalog();
	 * $xcatalog = Price::getCatalogCounts($catalog);
	 * $simple   = Price::simplifyCatalog($xcatalog);
	 * ```
	 */
	public static function simplifyCatalog($catalog): array {

		$newCatalog = [];
		$empty      = [];

		foreach ($catalog as $idA => $cats) {

			$newCatalog[$idA] = $cats;

			if ($cats['count'] == 0) {
				$empty[] = $idA;
			}

			// второй уровень
			foreach ($cats['subcat'] as $idB => $cat) {

				$newCatalog[$idB] = $cat;

				if ($cat['count'] == 0) {
					$empty[] = $idB;
				}

				// третий уровень (максимальный)
				foreach ($cat['subcat'] as $idC => $c) {

					$newCatalog[$idC] = $c;

					if ($c['count'] == 0) {
						$empty[] = $idC;
					}

				}

				unset($newCatalog[$idB]['subcat']);

			}

			unset($newCatalog[$idA]['subcat']);

		}

		return [
			"catalog" => $newCatalog,
			"empty"   => $empty
		];

	}

	public static function getPriceList(array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$fields = self::fields()['fields'];

		$page       = (int)$params['page'] > 0 ? (int)$params['page'] : 1;
		$idcategory = (int)$params['idcat'];
		$oldonly    = $params['oldonly'];
		$old        = $params['old'];
		$tuda       = $params['tuda'];
		$ord        = $params['ord'] ?? "title";
		$fromcat    = $params['fromcat'];

		$word = str_replace(" ", "%", $params['word']);
		$sort = '';

		$lines_per_page = 50; //Стоимость записей на страницу

		if ($idcategory > 0 && empty($fromcat)) {

			$listcat = [];
			$catalog = self ::getPriceCatalog($idcategory);
			foreach ($catalog as $value) {
				$listcat[] = $value['id'];
			}

			$ss = ( !empty($listcat) ) ? " or prc.pr_cat IN (".implode(",", $listcat).")" : '';

			$sort .= " and (prc.pr_cat = '$idcategory' $ss)";

		}
		// выводить только из указанной категории, без учета вложенных категорий
		elseif( $idcategory > 0 && !empty($fromcat) ){
			$sort .= " and (prc.pr_cat = '$idcategory')";
		}

		// только архивные
		if ($oldonly == 'yes') {
			$sort .= " and prc.archive = 'yes'";
		}
		// все
		elseif ($old == 'yes') {
			$sort .= " and (prc.archive = 'yes' OR prc.archive != 'yes')";
		}
		// только активные
		else {
			$sort .= " and COALESCE(prc.archive, 'no') != 'yes'";
		}

		if ($word != '') {
			$sort .= " and (prc.artikul LIKE '%$word%' or prc.title LIKE '%$word%' or prc.descr LIKE '%$word%')";
		}

		$qfields = [];
		foreach ($fields as $field ) {
			$qfields[] = "prc.".$field['field']." as ".$field['field'];
		}

		//print
		$query = "
		SELECT
			prc.n_id as id,
			prc.datum as datum,
			prc.pr_cat as idcat,
			prc.title as title,
			SUBSTRING(prc.descr, 1, 100) as content,
			prc.artikul as artikul,
			prc.edizm as edizm,
			prc.price_in as price_in,
			".yimplode(",", $qfields).",
			prc.archive as archive,
			{$sqlname}price_cat.title as category
		FROM {$sqlname}price `prc`
			LEFT JOIN {$sqlname}price_cat ON prc.pr_cat = {$sqlname}price_cat.idcategory
		WHERE
			prc.n_id > 0
			$sort and
			prc.identity = '$identity'
		";

		$result      = $db -> query($query);
		$all_lines   = $db -> affectedRows($result);
		$count_pages = ceil($all_lines / $lines_per_page);

		if ($page > $count_pages) {
			$page = 1;
		}

		if (empty($page) || $page <= 0) {
			$page = 1;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query  .= " ORDER BY prc.$ord $tuda LIMIT $lpos,$lines_per_page";
		$result = $db -> query($query);

		if ($count_pages == 0) {
			$count_pages = 1;
		}

		while ($da = $db -> fetch($result)) {

			$pfields = [];
			foreach ($fields as $field ) {

				$pfields[] = [
					"name" => $field['title'],
					"value" => num_format($da[$field['field']])
				];

			}

			$list[] = [
				"id"       => (int)$da['id'],
				"artikul"  => $da['artikul'],
				"title"    => $da['title'],
				"content"  => $da['content'],
				"edizm"    => $da['edizm'],
				"category" => $da['category'],
				"price_in" => num_format($da['price_in']),
				"fields" => $pfields,
				"price_1"  => num_format($da['price_1']),
				"price_2"  => num_format($da['price_2']),
				"price_3"  => num_format($da['price_3']),
				"archive"  => ( $da['archive'] == 'yes' ) ? '1' : ''
			];

		}

		return [
			"list"      => $list,
			"page"      => $page,
			"pageall"   => $count_pages,
			"ord"       => $ord,
			"desc"      => $tuda
		];

	}

}