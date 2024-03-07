<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

namespace Salesman;

use DOMDocument;
use DOMXPath;

/**
 * Класс для работы с объектом База знаний
 *
 * Class Budget
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example:
 * ```php
 * $Budget  = new Salesman\Knowledgebase();
 * $result = $Budget -> edit($id, $params);
 * $id = $result['data'];
 * ```
 *
 */
class Knowledgebase {

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

		$params = $this -> params;

		$this -> rootpath = dirname(__DIR__, 2);
		$this -> identity = ( $params['identity'] > 0 ) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
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
	 * Данные о записи
	 * @param int $id
	 * @return array
	 */
	public function info(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$result = $db -> getRow("SELECT * FROM {$sqlname}knowledgebase WHERE id = '$id' and identity = '$identity'");

		if (empty($result)) {

			$response['result']        = 'Error';
			$response['error']['code'] = '404';
			$response['error']['text'] = "Не найден";

			return $response;

		}

		$urls = [];

		$dom = new DOMDocument();
		@$dom -> loadHTML(htmlspecialchars_decode($result['content']));

		// захватить все на странице
		$xpath = new DOMXPath($dom);
		$hrefs = $xpath -> evaluate("//a");

		for ($i = 0; $i < $hrefs -> length; $i++) {

			$href = $hrefs -> item($i);

			$url   = $href -> getAttribute('href');
			$title = utf8_decode($href -> getAttribute('title'));
			$text  = utf8_decode($href -> textContent);

			if ($title == '' && $text != '') {
				$title = $text;
			}
			if ($title == '' && $text == '') {
				$title = 'Ссылка';
			}

			$urls[] = [
				"url"   => urldecode($url),
				"title" => $title,
				"text"  => $text
			];

		}

		return [
			"id"         => $id,
			"date"       => $result['datum'],
			"title"      => $result["title"],
			"content"    => htmlspecialchars_decode($result["content"]),
			"category"   => (int)$result["idcat"],
			"keywords"   => $result["keywords"],
			"tags"       => yexplode(",", $result["keywords"]),
			"ispin"      => $result["pin"] == 'yes',
			"pindate"    => $result["pindate"],
			"active"     => $result["active"],
			"author"     => (int)$result["author"],
			"authorName" => current_user($result["author"], 'yes'),
			"urls"       => $urls
		];

	}

	/**
	 * Редактирование записи
	 * @param int $id
	 * @param array $params
	 * @return array
	 */
	public function edit(int $id = 0, array $params = []): array {

		$sqlname  = $this -> sqlname;
		$iduser1  = $this -> iduser1;
		$identity = $this -> identity;
		$db       = $this -> db;

		$newcat = $params['newcat'];

		$kb['title']    = untag($params['title']);
		$kb['content']  = htmlspecialchars(str_replace([
			"\\r",
			"\\n"
		], "", str_replace(['\"'], '"', $params['content'])));
		$kb['keywords'] = $params['keywords'];
		$kb['active']   = $params['active'] != 'yes' ? "no" : "yes";
		$kb['idcat']    = (int)$params['idcat'];

		if (!empty($newcat)) {
			$kb['idcat'] = $this -> categoryManage($newcat, $kb['idcat']);
		}

		if (!empty($kb['keywords'])) {
			$kb['keywords'] = $this -> tagsManage($kb['keywords']);
		}

		if ($id == 0) {

			$kb['count']    = 0;
			$kb['author']   = $iduser1;
			$kb['identity'] = $identity;
			$kb['datum']    = current_datumtime();

			$db -> query("INSERT INTO {$sqlname}knowledgebase SET ?u", $kb);
			$id = $db -> insertId();

			return [
				"id"  => $id,
				"mes" => 'Готово'
			];

		}

		$author = (int)$db -> getOne("SELECT author FROM {$sqlname}knowledgebase WHERE id = '$id' and identity = '$identity'");
		if ($author == 0) {
			$kb['author'] = $iduser1;
		}

		$db -> query("UPDATE {$sqlname}knowledgebase SET ?u WHERE id = '$id'", $kb);

		return [
			"id"  => $id,
			"mes" => 'Готово'
		];

	}

	/**
	 * Закрепление/открепление записи
	 * @param int $id
	 * @param string $pin
	 * @return string
	 */
	public function pin(int $id = 0, string $pin = 'no'): string {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$kb['pin']     = $pin;
		$kb['pindate'] = current_datumtime();

		$db -> query("UPDATE {$sqlname}knowledgebase SET ?u WHERE id = '$id'", $kb);

		return 'Готово';

	}

	/**
	 * Удаление записи
	 * @param $id
	 * @return string
	 */
	public function delete($id): string {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$db -> query("DELETE FROM {$sqlname}knowledgebase WHERE id = '$id' AND identity = '$identity'");

		return 'Сделано';

	}

	/**
	 * Список записей
	 * @param array $filters
	 * @return array
	 */
	public function list(array $filters = []): array {

		$sqlname  = $this -> sqlname;
		$iduser1  = $this -> iduser1;
		$identity = $this -> identity;
		$db       = $this -> db;

		global $isadmin;

		$page    = (int)$filters['page'];
		$idcat   = (int)$filters['idcat'];
		$word    = str_replace(" ", "%", $filters['word']);
		$keyword = $filters['tag'];

		$lines_per_page = "40";

		$sort = '';
		$list = [];

		if ($idcat > 0) {

			$subid = $db -> getCol("SELECT idcat FROM {$sqlname}kb WHERE subid = '$idcat' and identity = '$identity'");

			if ( !empty($subid) ) {
				$sort .= " and (kb.idcat = '$idcat' or kb.idcat IN (".yimplode(",", $subid)."))";
			}
			else {
				$sort .= " and kb.idcat = '$idcat'";
			}

		}

		if (!empty($word)) {
			$sort .= " and ( kb.title LIKE '%$word%' or kb.content LIKE '%$word%' or kb.keywords LIKE '%$word%' )";
		}

		if (!empty($keyword)) {
			$sort .= " and (kb.keywords LIKE '%$keyword%')";
		}

		//print
		$query = "
			SELECT
				kb.id as id,
				kb.datum as datum,
				kb.idcat as idcat,
				kb.active as active,
				kb.author as author,
				kb.title as title,
				kb.content as content,
				kb.keywords as keywords,
				kb.count as count,
				kb.pin as pin,
				{$sqlname}kb.title as category,
				{$sqlname}user.title as user
			FROM {$sqlname}knowledgebase `kb`
				LEFT JOIN {$sqlname}user ON kb.author = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}kb ON kb.idcat = {$sqlname}kb.idcat
			WHERE
				kb.id > 0
				$sort and
				kb.identity = '$identity'
			ORDER BY field(kb.pin, 'yes', 'no'), kb.datum";

		$result    = $db -> query($query);
		$all_lines = $db -> numRows($result);

		if (empty($page) || $page <= 0) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query  .= " DESC LIMIT $lpos,$lines_per_page";
		$result = $db -> getAll($query);

		$count_pages = ceil($all_lines / $lines_per_page);
		if ($count_pages == 0) {
			$count_pages = 1;
		}

		foreach ($result as $da) {

			$show = $trash = '';

			if ($da['category'] == '') {
				$da['category'] = 'Вне раздела';
			}

			if ($da['active'] != 'no' || ( $da['author'] == $iduser1 || $isadmin == 'on' )) {

				$show = 'yes';
				if ($da['active'] == 'no') {
					$trash = 'yes';
				}

			}

			$pin = ( $da['pin'] == 'no' ) ? "" : "1";

			$change = ( $da['author'] == $iduser1 or $isadmin == 'on' ) ? 'yes' : '';

			$keys    = str_replace(",", ", ", $da['keywords']);
			$content = mb_substr(untag(htmlspecialchars_decode($da['content'])), 0, 101, 'utf-8');

			$list[] = [
				"id"       => (int)$da['id'],
				"datum"    => get_sfdate($da['datum']),
				"title"    => $da['title'],
				"content"  => $content,
				"category" => $da['category'],
				"show"     => $show,
				"trash"    => $trash,
				"keys"     => $keys,
				"change"   => $change,
				"author"   => $da['user'],
				"pin"      => $pin
			];

		}

		return [
			"list"    => $list,
			"page"    => $page,
			"pageall" => $count_pages
		];

	}

	/**
	 * Список тегов
	 * @param $word
	 * @param bool $exists
	 * @return array
	 */
	public function taglist($word = NULL, bool $exists = false): array {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$tags = [];
		$s    = '';

		if (!empty($word)) {
			$word = texttosmall($word);
			$s    = "name LIKE '%$word%' and ";
		}

		$result = $db -> getAll("SELECT * FROM {$sqlname}kbtags WHERE $s identity = '$identity' ORDER by name");
		//print $db -> lastQuery();
		foreach ($result as $data) {

			//print_r($data);

			$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}knowledgebase WHERE keywords LIKE '%".$data['name']."%' and active != 'no' and identity = '$identity'");

			if ($exists) {

				$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}knowledgebase WHERE keywords LIKE '%".$data['name']."%' and active != 'no' and identity = '$identity'");
				if ($count > 0) {
					$tags[] = [
						"tag"   => $data['name'],
						"count" => $count
					];
				}

			}
			else {

				//print_r($data);

				$tags[] = [
					"tag"   => $data['name'],
					"count" => $count
				];
			}

		}

		return $tags;

	}

	/**
	 * Обрабатывает строку тегов и, при необходимости, добавляет новые теги в базу
	 * возвращает обработанную строку с очисткой
	 * @param $tags
	 * @return string
	 */
	public function tagsManage($tags): string {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$t = [];

		//Массив имеющихя тегов
		$xtags = $db -> getCol("SELECT DISTINCT name FROM {$sqlname}kbtags WHERE identity = '$identity'");

		$qs = yexplode(",", $tags);

		foreach ($qs as $q) {

			if (trim($q) != '') {

				$t[] = $word = texttosmall(trim($q));

				if (!in_array($word, $xtags)) {

					$db -> query("INSERT INTO {$sqlname}kbtags SET ?u", [
						"name"     => $word,
						"identity" => $identity
					]);

				}

			}

		}

		return yimplode(",", $t);

	}

	/**
	 * Проверяет наличие категории в базе и, если её нет, то создает
	 * так же проверяет $subid - если это категория верхнего уровня, то прикрепляет новую категорию к ней
	 * @param $category
	 * @param int $subid
	 * @return string
	 */
	public function categoryManage($category, int $subid = 0): string {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		if (!empty($category)) {

			if ($subid > 0) {

				$xsubid = (int)$db -> getOne("SELECT subid FROM {$sqlname}kb WHERE idcat = '$subid' and identity = '$identity'");

				if ($xsubid > 0) {
					$subid = 0;
				}

			}

			$idcat = (int)$db -> getOne("SELECT idcat FROM {$sqlname}kb WHERE title LIKE '$category' and identity = '$identity'");

			if ($idcat == 0) {

				$db -> query("INSERT INTO {$sqlname}kb SET ?u", [
					"subid"    => $subid,
					"title"    => $category,
					"identity" => $identity
				]);
				return $db -> insertId();

			}

			return $idcat;

		}

		return 0;

	}

	/**
	 * Список категорий
	 * @param int $id
	 * @return array
	 */
	public function categorylist(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$list = [];

		$result = $db -> getAll("SELECT * FROM {$sqlname}kb WHERE subid = '0' and identity = '$identity' ORDER by title");
		foreach ($result as $data) {

			$count = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}knowledgebase WHERE idcat = '".$data['idcat']."' and identity = '$identity'");

			$list[] = [
				"id"    => $data['idcat'],
				"title" => $data['title'],
				"fol"   => $data['idcat'] == $id ? 'fol_it' : 'fol',
				"count" => $count,
				"level" => 0
			];

			$res = $db -> getAll("SELECT * FROM {$sqlname}kb WHERE subid = '".$data['idcat']."' and identity = $identity ORDER by title");
			foreach ($res as $da) {

				$count = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}knowledgebase WHERE idcat = '".$da['idcat']."' and identity = '$identity'");

				$list[] = [
					"id"    => $da['idcat'],
					"title" => $da['title'],
					"fol"   => $da['idcat'] == $id ? 'fol_it' : 'fol',
					"count" => $count,
					"level" => 1
				];

			}

		}

		return $list;

	}

	/**
	 * @param int $id
	 * @return array
	 */
	public function categoryInfo(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$result = $db -> getRow("SELECT * FROM {$sqlname}kb WHERE idcat = '$id' and identity = '$identity'");

		if (empty($result)) {

			$response['result']        = 'Error';
			$response['error']['code'] = '404';
			$response['error']['text'] = "Не найден";

			return $response;

		}

		return [
			"id"    => (int)$result["idcat"],
			"subid" => (int)$result["subid"],
			"title" => $result["title"],
		];

	}

	/**
	 * Редактирование записи категории
	 * @param int $id
	 * @param array $params
	 * @return int
	 */
	public function categoryEdit(int $id = 0, array $params = []): int {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$subid = (int)$params['subid'];
		$title = $params['title'];

		if ($id > 0) {

			$db -> query("UPDATE {$sqlname}kb SET ?u WHERE idcat = '$id' and identity = '$identity'", [
				"title" => $title,
				"subid" => $subid
			]);

		}
		else {

			$db -> query("INSERT INTO {$sqlname}kb SET ?u", [
				"subid"    => $subid,
				"title"    => $title,
				"identity" => $identity
			]);
			$id = $db -> insertId();

		}

		return $id;

	}

	/**
	 * Удаление категории
	 * @param $id
	 * @return string
	 */
	public function categoryDelete($id): string {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$db -> query("DELETE FROM {$sqlname}kb WHERE idcat = '$id' AND identity = '$identity'");
		$db -> query("UPDATE {$sqlname}knowledgebase SET idcat = '0' WHERE idcat = '$id' AND identity = '$identity'");

		return 'Сделано';

	}

}