<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2023.x           */
/* ============================ */

namespace Salesman;

/**
 * Класс для работы с объектом Активность
 *
 * Class History
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 */
class History {

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
		//require_once $rootpath."/vendor/autoload.php";

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
	 * Информация по записи активности
	 * @param int $id
	 * @return array
	 */
	public function info(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$data = $db -> getRow("
			SELECT 
				tsk.cid,
				tsk.datum,
				tsk.tip,
				tsk.iduser,
				tsk.clid,
				tsk.did,
				tsk.pid,
				tsk.des,
				tsk.fid,
				cc.title as iclient,
				deal.title as ideal
			FROM {$sqlname}tasks `tsk`
				LEFT JOIN {$sqlname}clientcat `cc` ON tsk.clid = cc.clid
				LEFT JOIN {$sqlname}dogovor `deal` ON tsk.did = deal.did
			WHERE 
				tsk.cid = '$id' AND 
				tsk.identity = '$identity'
		");

		$pids = yexplode(";", $data['pid']);

		$persons = [];
		foreach ($pids as $pid) {

			$persons[(int)$pid] = [
				"pid"   => (int)$pid,
				"title" => current_person((int)$pid)
			];

		}

		$fids  = yexplode(";", $data['fid']);
		$files = [];
		foreach ($fids as $fid) {

			$result = $db -> getRow("SELECT * FROM {$sqlname}file WHERE fid = '$fid' and identity = '$identity'");

			$files[(int)$fid] = [
				"id"   => (int)$fid,
				"name" => $result["ftitle"],
				"file" => $result["fname"]
			];

		}

		return [
			"cid"     => (int)$data['cid'],
			"datum"   => $data['datum'],
			"datumf"  => get_sdate($data['datum']),
			"tip"     => $data['tip'],
			"icon"    => get_ticon($data['tip']),
			"iduser"  => (int)$data['iduser'],
			"user"    => current_user($data['iduser']),
			"clid"    => (int)$data['clid'],
			"client"  => $data['iclient'],
			"did"     => (int)$data['did'],
			"deal"    => $data['ideal'],
			"persons" => $persons,
			"content" => $data['des'],
			"html"    => link_it(nl2br($data['des'])),
			"fids"    => $files,
		];

	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function list(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$list           = [];
		$lines_per_page = 10;
		$did            = (int)$params['did'];
		$pid            = (int)$params['pid'];
		$clid           = (int)$params['clid'];
		$tiphistory     = $params['tip'];
		$page           = $params['page'];

		$sort = $params['log'] == 'yes' ? " and {$sqlname}history.tip IN ('СобытиеCRM','ЛогCRM')" : " and {$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM')";

		if ($did == 0) {

			if ($pid > 0) {
				$sort .= " and FIND_IN_SET('$pid', REPLACE({$sqlname}history.pid, ';',',')) > 0";
			}

			if ($clid > 0) {

				//пройдемся по контактам
				$pids = $db -> getCol("SELECT pid FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'");

				$s = [];
				foreach ($pids as $pi) {

					if ($pi > 0) {
						$s[] = "FIND_IN_SET('$pi', REPLACE({$sqlname}history.pid, ';',',')) > 0";
					}

				}

				$so = ( !empty($s) ) ? " OR (".implode(" OR ", $s).")" : "";

				$sort .= " and ({$sqlname}history.clid = '$clid' $so)";

			}

		}
		if ($did > 0) {
			$sort .= " and {$sqlname}history.did = '$did'";
		}

		if (!empty($tiphistory)) {
			$sort .= " and {$sqlname}activities.id IN (".implode(",", $tiphistory).")";
		}

		$query = "
		SELECT
			DISTINCT({$sqlname}history.cid),
			{$sqlname}history.tip,
			{$sqlname}history.datum,
			{$sqlname}history.datum_izm,
			{$sqlname}history.clid,
			{$sqlname}history.pid,
			{$sqlname}history.did,
			{$sqlname}history.uid,
			{$sqlname}history.fid,
			{$sqlname}history.iduser,
			{$sqlname}history.iduser_izm,
			{$sqlname}history.des,
			{$sqlname}activities.id,
			{$sqlname}activities.color as color,
			{$sqlname}clientcat.title as client,
			{$sqlname}dogovor.title as dogovor,
			{$sqlname}user.title as user
		FROM {$sqlname}history
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}history.clid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}history.did
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}history.iduser
			LEFT JOIN {$sqlname}activities ON {$sqlname}activities.title = {$sqlname}history.tip
		WHERE
			{$sqlname}history.cid > 0
			$sort and
			{$sqlname}history.identity = '$identity'
		GROUP BY {$sqlname}history.cid
		ORDER BY {$sqlname}history.datum DESC
		";

		$result    = $db -> query($query);
		$all_lines = $db -> numRows($result);

		if (empty($page) || $page <= 0) {
			$page = 1;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;
		$count_pages    = ceil($all_lines / $lines_per_page);

		$query .= empty($params['nolimit']) ? " LIMIT $lpos,$lines_per_page" : "";
		$result = $db -> getAll($query);

		foreach ($result as $row) {

			$list[] = [
				"id"        => (int)$row['cid'],
				"tip"       => $row['tip'],
				"color"     => $row['color'],
				"datum"     => $row['datum'],
				"datum_izm" => $row['datum_izm'],
				"clid"      => (int)$row['clid'],
				"pid"       => yexplode(";", $row['pid']),
				"did"       => (int)$row['did'],
				"uid"       => $row['uid'],
				"fid"       => yexplode(";", $row['fid']),
				"user"      => $row['user'],
				"content"   => $row['des'],
				"deal"   => $row['dogovor'],
				"client"    => $row['client'],
			];

		}

		return [
			"list"  => $list,
			"page"  => $page,
			"total" => $count_pages,
		];

	}

}