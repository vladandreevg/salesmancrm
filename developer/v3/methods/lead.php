<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $action;
global $identity, $rootpath, $path;

use Salesman\Leads;
use Salesman\UIDs;

$mdwset       = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'");
$leadsettings = json_decode($mdwset['content'], true);
$coordinator  = $leadsettings["leadСoordinator"];
$users        = $leadsettings['leadOperator'];

$statuses = Leads::STATUSES;
$rezultes = Leads::REZULTES;

switch ($action) {

	//информация о заявке
	case 'info':

		$params['identity'] = (int)$identity;
		$id                 = (int)$params['id'];

		//добавляем заявку
		$lead = Leads ::info($id);

		unset( $lead['muid'], $lead['timezone'], $lead['rezz'], $lead['identity'] );

		$lead['statusName']     = strtr($lead['status'], $statuses);
		$lead['rezultName']     = (int)$lead['rezult'] > 0 ? strtr($lead['rezult'], $rezultes) : NULL;
		$lead['clientpathName'] = current_clientpathbyid((int)$lead['clientpath']);
		$lead['userName']       = $lead['iduser'] > 0 ? current_user((int)$lead['iduser']) : NULL;

		$lead['client'] = (int)$lead['clid'] > 0 ? current_client((int)$lead['clid']) : NULL;
		$lead['person'] = (int)$lead['pid'] > 0 ? current_person((int)$lead['pid']) : NULL;
		$lead['deal']   = (int)$lead['did'] > 0 ? current_dogovor((int)$lead['did']) : NULL;

		$response['data'] = $lead;
		$response['id']   = (int)$id;

	break;

	case 'stat':

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {

			$sort1 .= " and DATE_FORMAT({$sqlname}leads.datum, '%y-%m-%d') = '".$params['dateStart']."'";
			$sort2 .= " and DATE_FORMAT({$sqlname}leads.datum_do, '%y-%m-%d') = '".$params['dateStart']."'";

		}
		elseif ($params['dateStart'] != '' && $params['dateEnd'] != '') {

			$sort1 .= " and ({$sqlname}leads.datum BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";
			$sort2 .= " and ({$sqlname}leads.datum_do BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";

		}
		elseif ($params['dateStart'] == '' && $params['dateEnd'] != '') {

			$sort1 .= " and DATE_FORMAT({$sqlname}leads.datum, '%y-%m-%d') < '".$params['dateEnd']."'";
			$sort2 .= " and DATE_FORMAT({$sqlname}leads.datum_do, '%y-%m-%d') < '".$params['dateEnd']."'";

		}
		else {

			$sort1 .= " and DATE_FORMAT({$sqlname}leads.datum, '%Y-%m') = '".date('Y')."-".date('m')."'";
			$sort2 .= " and DATE_FORMAT({$sqlname}leads.datum_do, '%Y-%m') = '".date('Y')."-".date('m')."'";

		}

		$re = $db -> getAll("SELECT * FROM {$sqlname}user WHERE secrty = 'yes' AND iduser IN (".yimplode(",", $users).") and identity = '$identity'");
		foreach ($re as $do) {

			$open = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and status = '1' $sort1 and iduser = '".$do['iduser']."' and identity = '$identity'");

			$work = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}leads WHERE id > 0 and status IN ('2','3') $sort2 and iduser = '".$do['iduser']."' and identity = '$identity'");

			$response['data'][] = [
				"login"     => $do['login'],
				"open"      => $open,
				"processed" => $work
			];

		}

	break;

	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ((int)$params['offset'] > 0) ? (int)$params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'datum';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and DATE_FORMAT({$sqlname}leads.datum, '%y-%m-%d') = '".$params['dateStart']."'";
		}

		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and ({$sqlname}leads.datum BETWEEN '".$params['dateStart']." 00:00:00' and '".$params['dateEnd']." 23:59:59')";
		}

		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and DATE_FORMAT({$sqlname}leads.datum, '%y-%m-%d') < '".$params['dateEnd']."'";
		}

		if ($params['status'] != '') {
			$sort .= " and {$sqlname}leads.status = '".$params['status']."'";
		}

		if ($params['user'] != '') {
			$sort .= " and {$sqlname}leads.iduser = '".current_userbylogin( $params['user'] )."'";
		}

		if ( $params['word'] != '' ) {
			$params['word'] = untag3($params['word']);
			$sort .= " and ({$sqlname}leads.title LIKE '%$params[word]%' or {$sqlname}leads.email LIKE '%$params[word]%' or {$sqlname}leads.phone LIKE '%$params[word]%' or {$sqlname}leads.description LIKE '%$params[word]%')";
		}

		$lpos = $offset * $limit;

		$query = "
			SELECT
				{$sqlname}leads.id as id,
				{$sqlname}leads.datum as datum,
				{$sqlname}leads.datum_do as datum_do,
				{$sqlname}leads.pid as pid,
				{$sqlname}leads.clid as clid,
				{$sqlname}leads.did as did,
				{$sqlname}leads.description as content,
				{$sqlname}leads.status as status,
				{$sqlname}leads.rezult as rezult,
				{$sqlname}leads.iduser as iduser,
				{$sqlname}leads.clientpath as clientpath,
				{$sqlname}leads.email,
				{$sqlname}leads.phone,
				{$sqlname}leads.site,
				{$sqlname}leads.company,
				{$sqlname}leads.city,
				{$sqlname}leads.country,
				{$sqlname}clientcat.title as client,
				{$sqlname}personcat.person as person,
				{$sqlname}dogovor.title as deal,
				{$sqlname}clientpath.name as clientpathName,
				{$sqlname}user.title as user
			FROM {$sqlname}leads
				LEFT JOIN {$sqlname}user ON {$sqlname}leads.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}personcat ON {$sqlname}leads.pid = {$sqlname}personcat.pid
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}leads.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}leads.did = {$sqlname}dogovor.did
				LEFT JOIN {$sqlname}clientpath ON {$sqlname}leads.clientpath = {$sqlname}clientpath.id
			WHERE
				{$sqlname}leads.id > 0
				$sort and 
				{$sqlname}leads.identity = '$identity'
			ORDER BY $order $first 
			LIMIT $lpos,$limit
		";

		$result = $db -> query($query);
		while ($da = $db -> fetch($result)) {

			$ruids = UIDs ::info(["lid" => (int)$da['id']]);
			$uids  = $ruids['data'];

			$response['data'][] = [
				"id"             => (int)$da['id'],
				"datum"          => $da['datum'],
				"datum_do"       => $da['datum_do'],
				"email"          => $da['email'],
				"phone"          => $da['phone'],
				"site"           => $da['site'],
				"company"        => $da['company'],
				"city"           => $da['city'],
				"country"        => $da['country'],
				"content"        => $da['content'],
				"clid"           => (int)$da['clid'],
				"client"         => $da['client'],
				"pid"            => (int)$da['pid'],
				"person"         => $da['person'],
				"did"            => (int)$da['did'],
				"deal"           => $da['deal'],
				"user"           => (int)$da['user'],
				"userName"       => current_user($da['user']),
				"status"         => (int)$da['status'],
				"statusName"     => strtr($da['status'], $statuses),
				"rezult"         => $da['rezult'],
				"rezultName"     => strtr($da['rezult'], $rezultes),
				"clientpath"     => $da['clientpath'],
				"clientpathName" => $da['clientpathName'],
				"uids"           => $uids
			];

		}

		//print_r($response);

		if (empty($response['data'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	//добавление заявки
	case 'add':

		//получим источник и обработаем его
		$params['clientpath'] = getClientpath($params['clientpath'], $params['utm_source']);

		$box = new Leads();

		//назначам пользователя
		$params['iduser'] = $box -> getUser([
			"phone"    => $params['phone'],
			"email"    => $params['email'],
			"identity" => $identity
		]);

		$params['partner']  = (int)get_partnerbysite(untag($params['partner']));
		$params['iduser']   = (int)$params['iduser'];
		$params['datum']    = current_datumtime();
		$params['identity'] = (int)$identity;
		$params['status']   = ((int)$params['iduser'] == 0 || (int)$params['iduser'] == (int)$coordinator) ? 0 : 1;

		//добавляем заявку
		$rez = $box -> edit($params);

		if ($rez['result'] == 'Сделано') {

			$response['result'] = 'Success';
			$response['id']     = $rez['id'];
			if (!empty($rez['error'])) {
				$response['mailresult'] = $rez['error'];
			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 401;
			$response['error']['text'] = 'Ошибка добавления заявки. '.$rez['error'];

		}

	break;

	default:

		$response['result']        = 'Error';
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Неизвестный метод';

	break;

}

print json_encode_cyr($response);
