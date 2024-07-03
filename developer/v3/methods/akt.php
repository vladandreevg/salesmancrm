<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

use Salesman\Akt;
use Salesman\Document;

global $action;
global $identity, $rootpath, $path;

$response = [];

$set  = $db -> getRow("select * from {$sqlname}settings WHERE id = '$identity'");
$inum = $set["akt_num"];

$temps = [];

$template  = Akt ::getTemplates();
$templates = array_values($template);
foreach ($templates as $v) {
	$temps[ $v['file'] ] = $v['title'];
}

$itemp = [
	"simple" => 'akt_simple.htm',
	"full"   => 'akt_full.htm',
	"prava"  => 'akt_prava.htm'
];

switch ($action) {

	case 'statuses':

		$statuses = (new Akt()) -> statuses();

		if(!empty($statuses)) {
			$response['data'] = $statuses;
		}
		else{

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;
	case 'templates':

		if (!empty($templates)) {
			$response['data'] = $templates;
		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Шаблонов не найдено";

		}

	break;
	case 'info':

		$deid = (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'");

		if (!isset($params['id'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id Акта";

		}
		elseif ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Документ не найден";

		}
		elseif ($deid > 0) {

			$res = Akt ::info($deid);

			$document = [
				"deid"           => (int)$res['deid'],
				"datum"          => format_date_rus(get_smdate($res['datum'])),
				"number"         => $res['number'],
				"title"          => $res['typeTitle'],
				"description"    => $res['des'],
				"template"       => $res['template']['file'],
				"templateID"     => (int)$res['template']['id'],
				"templateTitle"  => $res['template']['title'],
				"templateTypeID" => $res['template']['typeid'],
				"clid"           => (int)$res['clid'],
				"clientTitle"    => current_client($res['clid']),
				"payer"          => (int)$res['payer'],
				"payerTitle"     => current_client($res['payer']),
				"did"            => (int)$res['did'],
				"dealTitle"      => current_dogovor($res['did']),
				"mcid"           => (int)getDogData($res['did'], "mcid"),
				"signer"         => (int)$res['signer'],
				"status"         => (int)$res['status'],
				"statusTitle"    => $db -> getOne("SELECT title FROM {$sqlname}contract_status WHERE id = '$res[status]' and identity = '$identity'"),
				"positions"      => $res['poz']
			];

			if ((int)$res['crid'] > 0) {
				$document['crid'] = (int)$res['crid'];
			}

			$response['data'] = $document;
			//$response['http'] = $_SERVER['HTTP_HOST'];

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Документ не найден";

		}

	break;
	case 'list':

		//задаем лимиты по-умолчанию
		$params['page'] = ((int)$params['offset'] > 0) ? (int)$params['offset'] : 0;
		$params['ord']  = ($params['order'] != '') ? $params['order'] : 'datum';
		$params['tuda']  = ($params['first'] == 'old') ? '' : 'DESC';

		if ($params['user'] != '') {
			$params['iduser'] = current_userbylogin( $params['user'] );
		}

		if (!empty($params['dateStart'])) {
			$params['d1'] = $params['dateStart'];
		}
		if (!empty($params['dateEnd'])) {
			$params['d2'] = $params['dateEnd'];
		}

		$data = (new Akt()) -> list($params);

		$response['data'] = $data['list'];

		unset($data['list']);

		$response['params'] = $params;
		$response['count'] = $data['count'];

	break;
	case 'add':

		$params['iduser'] = ($params['user'] == '') ? $iduser : current_userbylogin($params['user']);

		$params['did'] = (isset($params['did'])) ? (int)$db -> getOne("SELECT did FROM {$sqlname}dogovor WHERE did = '".$params['did']."' and identity = '$identity'") : 0;

		//print_r($params);

		if ((int)$params['did'] == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Не найдено записи Сделки";

		}
		/*elseif (!isset($params['mcid'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Если не указан did сделки, то должен быть указан mcid - идентификатор компании";

		}*/
		else {

			$params['des']     = $params['description'];
			$params['akt_num'] = (isset($params['number']) && $params['number'] != '') ? $params['number'] : '';
			$params['datum']   = (isset($params['date']) && $params['date'] != '') ? $params['date'] : '';

			//$params['temp']    = (isset($params['template']) && $params['template'] != '') ? strtr($params['template'], $itemp) : "akt_full.htm";

			if (isset($params['template']) && $params['template'] != '') {
				$params['temp'] = $params['template'];
			}

			elseif (isset($params['templateID']) && $params['templateID'] > 0) {
				$params['temp'] = $template[ $params['templateID'] ]['file'];
			}

			if ($params['temp'] == '') {
				$params['temp'] = "akt_full.tpl";
			}

			$document = new Akt();

			//print_r($params);

			/**
			 * добавляем документ
			 */
			$res  = $document -> edit(0, $params);
			$deid = (int)$res['id'];

			if ($res['result'] != 'Error') {

				//print_r($res);

				$response['result']         = ($res['text'] != '') ? $res['result'].";".$res['text'] : $res['result'];
				$response['data']['id']     = (int)$res['deid'];
				$response['data']['number'] = $res['akt_num'];
				$response['data']['did']    = (int)$res['did'];
				if ((int)$res['crid'] > 0) {
					$response['data']['crid'] = (int)$res['crid'];
				}

			}
			else {

				//print_r($res);

				$response['result']        = 'Error';
				$response['error']['code'] = (int)$res['error']['code'];
				$response['error']['text'] = $res['error']['text'];

			}

		}

	break;
	case 'update':

		$params['iduser'] = ($params['user'] == '') ? $iduser : current_userbylogin($params['user']);

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			$params['des'] = $params['description'];
			//$params['temp']  = (isset($params['template']) && $params['template'] != '') ? strtr($params['template'], $itemp) : "akt_full.htm";
			$params['datum'] = (isset($params['date']) && $params['date'] != '') ? $params['date'] : '';

			if (isset($params['template']) && $params['template'] != '') {
				$params['temp'] = $params['template'];
			}

			elseif (isset($params['templateID']) && $params['templateID'] > 0) {
				$params['temp'] = $template[ $params['templateID'] ]['file'];
			}

			if ($params['temp'] == '') {
				$params['temp'] = "akt_full.htm";
			}

			$document = new Akt();

			/**
			 * добавляем документ
			 */
			$res = $document -> edit($deid, $params);

			if ($res['result'] != 'Error') {

				//print_r($res);

				$response['result']         = ($res['text'] != '') ? $res['result'].";".$res['text'] : $res['result'];
				$response['data']['id']     = (int)$res['deid'];
				$response['data']['number'] = $res['akt_num'];
				$response['data']['did']    = (int)$res['did'];
				if ($res['crid'] > 0) {
					$response['data']['crid'] = (int)$res['crid'];
				}

			}
			else {

				//print_r($res);

				$response['result']        = 'Error';
				$response['error']['code'] = (int)$res['error']['code'];
				$response['error']['text'] = $res['error']['text'];

			}

		}

	break;
	case 'status.change':

		$params['iduser'] = ($params['user'] == '') ? $iduser : current_userbylogin($params['user']);

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") + 0 : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			$oldstatus = (int)$db -> getOne("select status from {$sqlname}contract WHERE deid = '$deid' and identity = '$identity'");

			$document = new Document();

			//$params['subaction'] = 'status';

			$arg = [
				"status"    => (int)$params['status'],
				'oldstatus' => $oldstatus,
				"user"      => (int)$params['iduser'],
				"statusdes" => $params['description'],
				"subaction" => 'status',
			];

			/**
			 * Обновляем статус документа
			 */
			$res = $document -> edit($deid, $arg);

			if ($res['id'] > 0) {

				$response['result'] = "Успешно: ".yimplode(";", $res['message']);
				$response['data']   = $deid;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $res['error'];

			}

		}

	break;
	case 'delete':

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") + 0 : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			$document = new Akt();

			/**
			 * Удаляем документ
			 */
			$res = $document -> delete($deid);

			$response['result'] = $res['result'];
			$response['data']   = $deid;
			if ($res['error'] != '') {
				$response['error'] = $res['error'];
			}

		}

	break;
	case 'mail':

		$deid = (isset($params['id'])) ? (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE deid = '".$params['id']."' and identity = '$identity'") + 0 : 0;

		if ($deid == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}
		else {

			$document = new Akt();

			/**
			 * Удаляем документ
			 */
			$res = $document -> mail($deid, $params, true);

			//print_r($res);

			/*$response['result'] = ($res['text'] != '') ? $res['result'].": ".$res['text'] : $res['result'];
			$response['data']   = $deid;

			if ($res['error'] != '') {
				$response['error'] = $res['error'];
			}*/

			$response = $res;

		}

	break;
	//получение акта в виде HTML
	case 'html':

		$mes = [];

		$number = $params['number'];
		$id     = (int)$params['id'];

		if ($id > 0) {
			$s = "deid = '$id'";
		}
		elseif ($number != '') {
			$s = "number = '$number'";
		}

		//проверяем расчетный счет
		$deid = (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE $s AND identity = '$identity'") + 0;

		if ($deid > 0) {

			$akt = Akt ::info($deid);

			$params['tip']  = "print";
			$params['api']  = "yes";
			$params['temp'] = $akt['title'];

			$document = new Akt();
			$rez      = $document -> getAkt($deid, $params);

			$response['html']   = htmlspecialchars($rez);
			$response['id']     = (int)$akt['deid'];
			$response['number'] = $akt['number'];

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Акт по ID или Номеру не найден';

		}

	break;
	//получение акта в виде HTML
	case 'pdf':

		$mes = [];
		$http = ( $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://' ).$_SERVER['HTTP_HOST'];

		$number = $params['number'];
		$id     = (int)$params['id'];

		if ($id > 0) {
			$s = "deid = '$id'";
		}
		elseif ($number != '') {
			$s = "number = '$number'";
		}

		//проверяем расчетный счет
		$deid = (int)$db -> getOne("SELECT deid FROM {$sqlname}contract WHERE $s AND identity = '$identity'") + 0;

		$u = $rootpath."/files/".$fpath."akt_".$deid.".pdf";

		if ($deid > 0) {

			$akt = Akt ::info($deid);

			if (!file_exists($u)) {

				$params['tip']      = "pdf";
				$params['api']      = "yes";
				$params['download'] = "no";

				$inv = new Akt();
				$rez = $inv -> getAkt($deid, $params);

				if ($rez != 'Error') {

					$response['url']    = $http."/files/".$fpath.$rez;
					$response['id']     = $akt['deid'];
					$response['number'] = $akt['number'];

				}

			}
			else {

				$response['url']    = $http."/files/".$fpath."akt_".$deid.".pdf";
				$response['id']     = $akt['deid'];
				$response['number'] = $akt['number'];

			}

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Акт по ID или Номеру не найден';

		}

	break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 402;
		$response['error']['text'] = 'Неизвестный метод';
		break;

}

print $rez = json_encode_cyr($response);