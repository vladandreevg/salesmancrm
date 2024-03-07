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
		foreach ($pids as $pid){

			$persons[ (int)$pid ] = [
				"pid" => (int)$pid,
				"title" => current_person((int)$pid)
			];

		}

		$fids = yexplode(";", $data['fid']);
		$files = [];
		foreach ($fids as $fid){

			$result = $db -> getRow( "SELECT * FROM {$sqlname}file WHERE fid = '$fid' and identity = '$identity'" );

			$files[(int)$fid] = [
				"id" => (int)$fid,
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

}