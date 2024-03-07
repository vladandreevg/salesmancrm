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

class AutoRelation {

	var $response = array();

	/**
	 * Изменение Типа отношений
	 * @param $clid
	 * @param $relation
	 * @return array
	 */
	public function setRelation($clid, $relation){

		$rootpath = realpath(__DIR__.'/../');

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		require_once "Client.php";
		require_once "Guides.php";

		$iduser1  = $GLOBALS['iduser1'];

		//массив типов отношений
		$relations = Guides::Relation();

		$relation = (is_numeric($relation)) ? strtr($relation, $relations) : $relation;

		$arg = array(
			"tip_cmr" => $relation,
			"iduser" => $iduser1
		);

		$client = new Client();
		$response = $client ->update($clid, $arg);

		return $response;

	}

	//Текущий тип отношений клиента
	public function getRelation($clid){

		$rootpath = realpath(__DIR__.'/../');

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		require_once "Client.php";
		require_once "Guides.php";

		//массив типов отношений
		$relations = Guides::Relation();

		$current = getClientData($clid, "tip_cmr");

		return array(
			"id"    => strtr($current, $relations),
			"title" => $current
		);

	}

}