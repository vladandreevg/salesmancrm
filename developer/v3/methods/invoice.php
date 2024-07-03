<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

use Salesman\Invoice;
use Salesman\Speka;

global $action, $productInfo;

$response = [];

$http = ( $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || 443 == $_SERVER['SERVER_PORT'] ) ? 'https://' : 'http://' ).$_SERVER['HTTP_HOST'];

switch ($action) {

	/**
	 * Работа со счетами
	 */

	//Вывод списка клиентов
	case 'list':

		$params['page'] = ((int)$params['offset'] > 0) ? (int)$params['offset'] : 0;
		$params['ord']  = ($params['order'] != '') ? $params['order'] : 'datum';
		$params['tuda'] = ($params['first'] == 'old') ? '' : 'DESC';

		if (!empty($params['dateStart'])) {
			$params['d1'] = $params['dateStart'];
		}
		if (!empty($params['dateEnd'])) {
			$params['d2'] = $params['dateEnd'];
		}

		$data = (new Invoice()) -> list($params);

		$response['data'] = $data['list'];

		unset($data['list']);

		$response['params'] = $data;
		$response['count'] = $data['count'];

		break;

	//добавить счет
	case 'info':

		$id = $params["id"];

		$invoice = Invoice ::info($id, $params['dealinfo']);

		if (!isset($params['id'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id Счета";

		}
		elseif (!empty($invoice)) {

			$response['data'] = $invoice;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет не найден';

		}

		break;

	//добавить счет
	case 'add':

		$did = (int)$params["did"];
		$uid = untag($params["uid"]);

		//Находим clid, pid
		if ($did > 0) {
			$s = "did = '$did'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		$resu   = $db -> getRow("SELECT did, pid, iduser, kol FROM {$sqlname}dogovor WHERE $s and identity = '$identity'");
		$did    = (int)$resu['did'];
		$pid    = (int)$resu["pid"];
		$iduser = (int)$resu["iduser"];
		$kol    = (float)$resu["kol"];

		if ($did > 0) {

			$template = Invoice ::getTemplates();

			$arg['iduser'] = ( !isset($params['user']) && $params['user'] != '' ) ? current_userbylogin($params['user']) : $iduser;

			if( (int)$params['iduser'] > 0 ){
				$arg['iduser'] = (int)$params['iduser'];
			}

			$sumdo = (float)$db -> getOne("SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");

			if ($sumdo < $kol) {

				/*
				 * НДС по спецификации
				 */
				$spekaData = Speka ::getNalog($did);

				/*
				 * процент налога от суммы спецификации
				 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
				 */
				$nalogPercent = $spekaData['nalog'] / $spekaData['summa'];

				/*
				 * налог, который должен быть с учетом уже выставленных счетов
				 */
				$nalogNotDo = ( $spekaData['summa'] - $sumdo ) * $nalogPercent;

				$arg['datum']        = $params["date"];
				$arg['datum_credit'] = $params["date.plan"];
				$arg['igen']         = ( isset($params["invoice"]) && $params["invoice"] == "auto" ) ? "yes" : "no";
				$arg['invoice']      = ( $params["invoice"] == "auto" ) ? NULL : $params['invoice'];
				$arg['contract']     = $params['contract'];
				$arg['rs']           = ( isset($params["rs"]) && $params["rs"] > 0 ) ? $params["rs"] : NULL;
				$arg['signer']       = ( isset($params["signer"]) && $params["signer"] > 0 ) ? $params["signer"] : NULL;
				$arg['tip']          = ( isset($params["tip"]) && $params["tip"] != '' ) ? $params["tip"] : "Счет-договор";
				$arg['summa']        = ( $params["summa"] == '' ) ? getDogData($did, 'kol') : pre_format($params["summa"]);
				$arg['nds']          = ( isset($params["nds"]) && $params["nds"] != '' ) ? pre_format($params["nds"]) : $nalogNotDo;

				$arg['do']      = ( isset($params["do"]) && $params["do"] != '' ) ? $params["do"] : NULL;
				$arg['date.do'] = ( isset($params["date.do"]) && $params["date.do"] != '' ) ? $params["date.do"] : NULL;

				if (!empty($params['template'])) {
					$arg['template'] = $params['template'];
				}
				elseif ((int)$params['templateID'] > 0) {
					$arg['template'] = $template[$params['templateID']]['file'];
				}

				if (empty($arg['template'])) {
					$arg['template'] = "invoice.tpl";
				}

				if (!empty($params['newstep'])) {
					$stepid = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '$params[newstep]' AND identity = '$identity'" );
					$arg['newstep'] = $stepid;
				}

				$invoice = new Invoice();

				//print_r($arg);

				$rez = $invoice -> add($did, $arg);

				$response['result']          = $rez['result'].";".$rez['text'];
				$response['data']['id']      = $rez['data'];
				$response['data']['invoice'] = $rez['invoice'];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 406;
				$response['error']['text'] = 'Уже выставлены все возможные счета';

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = 'Сделка с указанным did не найдена в пределах аккаунта указанного пользователя';

		}

		break;

	//отметка счета оплаченным
	case 'do':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = (int)$params['id'];

		//Находим clid, pid
		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '') {
			$s = "invoice = '$invoice'";
		}

		//проверяем расчетный счет
		$crid = (int)$db -> getOne("SELECT crid FROM {$sqlname}credit WHERE $s AND identity = '$identity'");

		if ($crid > 0) {

			$arg['invoice_date'] = ( $params["date.do"] != '' ) ? $params["date.do"] : current_datum();

			if((float)$params["summa"] > 0) {
				$arg['summa'] = (float)$params["summa"];
			}

			if (!empty($params['newstep'])) {
				$stepid = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '$params[newstep]' AND identity = '$identity'" );
				$arg['newstep'] = $stepid;
			}

			$invoice = new Invoice();
			$rez     = $invoice -> doit($crid, $arg);

			$response['result'] = $rez['result'];
			$response['data']   = $rez['text'];

			if ($rez['newdata'] > 0) {
				$response['newdata'] = $rez['newdata'];
			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет по ID или Номеру не найден';

		}

		break;

	//экспресс-добавление оплаты
	case 'express':

		$did = (int)$params["did"];
		$uid = untag($params["uid"]);

		//Находим clid, pid
		if ($did > 0) {
			$s = "did = '$did'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		$resu   = $db -> getRow("SELECT did, pid, iduser, kol FROM {$sqlname}dogovor WHERE $s and identity = '$identity'");
		$did    = (int)$resu['did'];
		$pid    = (int)$resu["pid"];
		$iduser = (int)$resu["iduser"];
		$kol    = $resu["kol"];

		if ($did > 0) {

			$arg['iduser'] = ( !isset($params['user']) && $params['user'] != '' ) ? current_userbylogin($params['user']) : $iduser;

			if( (int)$params['iduser'] > 0 ){
				$arg['iduser'] = (int)$params['iduser'];
			}

			$sumdo = (float)$db -> getOne("SELECT SUM(summa_credit) FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");

			if ($sumdo < $kol) {

				$template = Invoice ::getTemplates();

				/*
				 * НДС по спецификации
				 */
				$spekaData = Speka ::getNalog($did);

				/*
				 * процент налога от суммы спецификации
				 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
				 */
				$nalogPercent = $spekaData['nalog'] / $spekaData['summa'];

				/*
				 * налог, который должен быть с учетом уже выставленных счетов
				 */
				$nalogNotDo = ( $spekaData['summa'] - $sumdo ) * $nalogPercent;

				$arg['datum']        = $params["date"];
				$arg['datum_credit'] = $params["date.plan"];
				$arg['igen']         = isset($params["invoice"]) && $params["invoice"] == "auto" ? "yes" : "no";
				$arg['invoice']      = $params["invoice"] == "auto" ? "" : $params['invoice'];
				$arg['contract']     = $params['contract'];
				$arg['rs']           = ( isset($params["rs"]) && (int)$params["rs"] > 0 ) ? $params["rs"] : "";
				$arg['signer']       = ( isset($params["signer"]) && (int)$params["signer"] > 0 ) ? $params["signer"] : "";
				$arg['tip']          = ( isset($params["tip"]) && $params["tip"] != '' ) ? $params["tip"] : "ioffer";
				$arg['summa']        = ( $params["summa"] == '' ) ? getDogData($did, 'kol') : pre_format($params["summa"]);
				$arg['nds']          = ( isset($params["nds"]) && $params["nds"] != '' ) ? pre_format($params["nds"]) : $nalogNotDo;

				$arg['do']      = "on";
				$arg['date.do'] = ( isset($params["date.do"]) && $params["date.do"] != '' ) ? $params["date.do"] : current_datum();

				if (isset($params['template']) && $params['template'] != '') {
					$arg['template'] = $params['template'];
				}
				elseif ((int)$params['templateID'] > 0) {
					$arg['template'] = (int)$params['templateID'];
				}

				if ($arg['template'] == '') {
					$arg['template'] = "invoice.tpl";
				}

				if (!empty($params['newstep'])) {
					$stepid = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title = '$params[newstep]' AND identity = '$identity'" );
					$arg['newstep'] = $stepid;
				}

				$invoice = new Invoice();

				//print_r($arg);

				$rez = $invoice -> express($did, $arg);

				$response['result']          = $rez['result'].";".$rez['text'];
				$response['data']['id']      = $rez['data'];
				$response['data']['invoice'] = $rez['invoice'];

				if(!empty($rez['messages'])){
					$response['messages'] = $rez['messages'];
				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 406;
				$response['error']['text'] = 'Уже выставлены все возможные счета';

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = 'Сделка с указанным did не найдена в пределах аккаунта указанного пользователя';

		}

		break;

	//получение счета в виде HTML
	case 'mail':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = (int)$params['id'];

		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '') {
			$s = "invoice = '$invoice'";
			if ((int)$params['did'] > 0) {
				$s .= " AND did = '$params[did]'";
			}
		}

		//проверяем расчетный счет
		$crid = (int)$db -> getOne("SELECT crid FROM {$sqlname}credit WHERE $s AND identity = '$identity'");

		if ($crid > 0) {

			if( (int)$params['iduser'] > 0 ){
				$params['iduser'] = (int)$params['iduser'];
			}
			elseif(!empty($params['user'])){
				$params['iduser'] = current_userbylogin($params['user']);
			}

			$invoice = new Invoice();
			$rez     = $invoice -> mail($crid, $params, true);

			if ($rez['result'] != 'Error') {

				$response['result']          = $rez['result'];
				$response['data']['id']      = $rez['data'];
				$response['data']['invoice'] = $rez['invoice'];

			}
			else {

				$response = $rez;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет по ID или Номеру не найден';

		}

		break;

	//получение счета в виде HTML
	case 'html':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = (int)$params['id'];

		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '' && (int)$params['did'] > 0) {

			$s = "invoice = '$invoice' AND did = '$params[did]'";

		}
		else{

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = 'Не указан DID сделки';

		}

		if( $response['result'] != 'Error' ) {

			//проверяем расчетный счет
			$crid = (int)$db -> getOne("SELECT crid FROM {$sqlname}credit WHERE $s AND identity = '$identity'");

			if ($crid > 0) {

				$inv = Invoice ::info($crid);

				$params['tip'] = "print";
				$params['api'] = "yes";
				//$params['nosignat'] = $params['nosignat'] ? "yes" : "";

				$invoice = new Invoice();
				$rez     = $invoice -> getInvoice($crid, $params);

				$response['html']    = htmlspecialchars($rez);
				$response['id']      = $inv['crid'];
				$response['invoice'] = $inv['invoice'];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 403;
				$response['error']['text'] = 'Счет по ID или Номеру не найден';

			}

		}

		break;

	//получение счета в виде HTML
	case 'pdf':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = (int)$params['id'];

		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '' && (int)$params['did'] > 0) {

			$s = "invoice = '$invoice' AND did = '$params[did]'";

		}
		else{

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = 'Не указан DID сделки';

		}

		if( $response['result'] != 'Error' ) {

			//проверяем расчетный счет
			$crid    = (int)$db -> getOne("SELECT crid FROM {$sqlname}credit WHERE $s AND identity = '$identity'");
			$invoice = (string)$db -> getOne("SELECT invoice FROM {$sqlname}credit WHERE $s AND identity = '$identity'");

			if ($crid > 0) {

				$params['tip']      = "pdf";
				$params['api']      = "yes";
				$params['download'] = "no";

				$inv = new Invoice();
				$rez = $inv -> getInvoice($crid, $params);

				if ($rez != 'Error') {

					$response['url']     = $http."/file/".$rez;
					$response['id']      = $crid;
					$response['invoice'] = $invoice;

				}
				else{

					$response['result']        = 'Error';
					$response['error']['code'] = 400;
					$response['error']['text'] = 'Не правильный запрос';

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 403;
				$response['error']['text'] = 'Счет по ID или Номеру не найден';

			}

		}

		break;

	//получение списка шаблонов
	case 'templates':

		$mes = [];

		$templates = Invoice ::getTemplates();

		if (!empty($templates)) {

			$response['result'] = 'Success';
			$response['data']   = array_values($templates);

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = 'Шаблоны не найдены';

		}

		break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 402;
		$response['error']['text'] = 'Неизвестный метод';
	break;

}

print $rez = json_encode_cyr($response);