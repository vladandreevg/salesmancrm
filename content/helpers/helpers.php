<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Различные хелперы, для выдачи данных
 */
//ini_set('display_errors', 1);
use Salesman\Akt;
use Salesman\Client;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

// массив для формирования списка документов, доступных для создания в карточках
// см. также js $cardsf.docMenu()
if ($action == 'getDocTypes') {

	$card = $_REQUEST['card'];
	$clid = (int)['clid'];
	$did  = (int)$_REQUEST['did'];

	$list = [];

	//этап, с которого можно печатать акт
	$stepApprove = current_dogstepname($GLOBALS['akt_step']);
	$stepCurrent = current_dogstepname(getDogData($did, 'idcategory'));

	$isContract = ($did > 0) ? $db -> getOne("SELECT deid FROM {$sqlname}contract WHERE did = '$did' and COALESCE((SELECT COUNT(*) FROM {$sqlname}contract_type WHERE {$sqlname}contract_type.type = 'get_dogovor' AND {$sqlname}contract_type.id = {$sqlname}contract.idtype), 0) > 0 and identity = '$identity'") + 0 : 0;

	$isAkt = ($did > 0) ? $db -> getOne("SELECT deid FROM {$sqlname}contract WHERE did = '$did' and COALESCE((SELECT COUNT(*) FROM {$sqlname}contract_type WHERE {$sqlname}contract_type.type = 'get_akt' AND {$sqlname}contract_type.id = {$sqlname}contract.idtype), 0) > 0 and identity = '$identity'") + 0 : 0;

	$isper = (bool)isServices( $did );

	$result = $db -> query("SELECT * FROM {$sqlname}contract_type WHERE identity = '$identity' ORDER by title");
	while ($data = $db -> fetch($result)) {

		$roles = explode(',', (string)$data['role']);
		$users = explode(',', (string)$data['users']);

		//начальное значение доступа - закрыт
		$access   = false;
		$editable = true;

		//если условия доступа не заданы, то доступ даем всем
		if ($data['users'] == '' && $data['role'] == '') {
			$access = true;
		}

		else {

			//если роли указаны, а сотрудники нет
			if ($data['role'] != '' && $data['users'] == '')
				$access = in_array($tipuser, $roles);

			//если сотрудники указаны, а роли нет
			if ($data['role'] == '' && $data['users'] != '')
				$access = in_array( $iduser1, (array)$users );

			//если указаны и роли и сотрудники
			if ($data['role'] != '' && $data['users'] != '')
				$access = in_array( $tipuser, (array)$roles ) || in_array( $iduser1, (array)$users );

		}

		if ($data['type'] == 'get_dogovor' && $isContract > 0)
			$editable = false;

		// комплектность актами
		$complect = round(Akt::getAktComplect($did), 0);
		$aktComplect = !($isper || $complect < 100);

		if ( $access ) {

			if ($data['type'] == 'get_dogovor' && $editable) {
				$list[] = [
					"id"         => $data['id'],
					"isContract" => true,
					"type"       => $data['type'],
					"title"      => $data['title'],
					"add"        => true,
					"access"     => 1
				];
			}
			elseif ($data['type'] == 'get_dogovor' && !$editable) {
				$list[] = [
					"id"         => 0,
					"isContract" => true,
					"title"      => $data['title'],
					"add"        => false,
					"access"     => 1
				];
			}

			if ($data['type'] == 'get_akt' && $did > 0 && !$aktComplect && !$isper) {
				$list[] = [
					"id"      => $data['id'],
					"isAkt"   => true,
					"did"     => $did,
					"title"   => $data['title'],
					"add"     => $stepCurrent >= $stepApprove,
					"tooltip" => $stepCurrent >= $stepApprove ? "" : "Акт можно сформировать начиная с этапа $stepApprove%",
					"access"  => 1
				];
			}
			elseif ($data['type'] == 'get_akt' && $did > 0 && $aktComplect && !$isper) {
				$list[] = [
					"id"      => 0,
					"isAkt"   => true,
					"title"   => $data['title'],
					"add"     => false,
					"tooltip" => "Уже существует",
					"access"  => 1
				];
			}

			if ($data['type'] == 'get_aktper' && $did > 0 && $isper) {
				$list[] = [
					"id"      => $data['id'],
					"isAkt"   => true,
					"did"     => $did,
					"title"   => $data['title'],
					"add"     => $stepCurrent >= $stepApprove,
					"tooltip" => $stepCurrent >= $stepApprove ? "" : "Акт можно сформировать начиная с этапа $stepApprove%",
					"access"  => 1
				];
			}

			if ($data['type'] == '') {
				$list[] = [
					"id"     => $data['id'],
					"isDoc"  => true,
					"type"   => $data['type'],
					"title"  => $data['title'],
					"add"    => true,
					"access" => 1
				];
			}

		}
		else {
			$list[] = [
				"id"     => 0,
				"title"  => $data['title'],
				"access" => 0
			];
		}

	}

	print json_encode_cyr($list);

	exit();

}

// сортировка номеров из карточек. Не готово!!!
if ($action == 'sortphones'){

	switch ($_REQUEST['type']){

		case "client":

			$response = (new Client()) ->update((int)$_REQUEST['clid'], [
				"phone" => $_REQUEST['phone']
			]);

		break;
		case "person":



		break;

	}

	print json_encode_cyr($_REQUEST);
	exit();

}