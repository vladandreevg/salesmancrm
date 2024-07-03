<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $action;
global $identity, $rootpath, $path;

$response = [];

switch ($action) {

	case 'category':

		$re = $db -> query("SELECT * FROM {$sqlname}category WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"id"    => (int)$do['idcategory'],
				"title" => $do['title'],
				"tip"   => $do['tip']
			];

		}

	break;

	case 'territory':

		$re = $db -> query("SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"id"    => (int)$do['idcategory'],
				"title" => $do['title']
			];

		}

	break;

	case 'relations':

		$re = $db -> query("SELECT * FROM {$sqlname}relations WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"id"    => (int)$do['id'],
				"title" => $do['title'],
				"color" => $do['color']
			];
		}

	break;

	case 'clientpath':

		$re = $db -> query("SELECT * FROM {$sqlname}clientpath WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$response['data'][] = [
				"id"          => (int)$do['id'],
				"title"       => $do['name'],
				"utm_source"  => $do['utm_source'],
				"destination" => $do['destination']
			];

		}

	break;

	case 'company.list':

		$re = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)){

			// подписанты
			$signers = getSigner(0, $do['id']);

			$d1 = explode(";", $do['innkpp']);
			$d2 = explode(";", $do['okog']);

			$response['data'][] = [
				"mcid"             => (int)$do['id'],
				"compUrName"       => $do['name_ur'],
				"compShotName"     => $do['name_shot'],
				"compUrAddr"       => $do['address_yur'],
				"compFacAddr"      => $do['address_post'],
				"compDirName"      => $do['dir_name'],
				"compDirSignature" => $do['dir_signature'],
				"compDirStatus"    => $do['dir_status'],
				"compDirOsnovanie" => $do['dir_osnovanie'],
				"compInn"          => $d1[0],
				"compKpp"          => $d1[1],
				"compOgrn"         => $d2[1],
				"signers"          => array_values((array)array_values($signers)[0])
			];

		}

	break;

	case 'company.listfull':

		$re = $db -> query("SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$d1 = explode(";", $do['innkpp']);
			$d2 = explode(";", $do['okog']);

			// подписанты
			$signers = getSigner(0, $do['id']);

			$rs = [];

			$res = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$do[id]' AND identity = '$identity'");
			while ($da = $db -> fetch($res)) {

				$bankr = explode(";", $da['bankr']);

				$rs[] = [
					"id"           => (int)$da['id'],
					"tip"          => $da['tip'],
					"isDefault"    => $da['isDefault'],
					"ndsDefault"   => $da['ndsDefault'],
					"compNameRs"   => $da['title'],
					"compBankBik"  => $bankr[0],
					"compBankRs"   => $da['rs'],
					"compBankKs"   => $bankr[1],
					"compBankName" => $bankr[2],
				];

			}

			$response['data'][] = [
				"mcid"             => (int)$do['id'],
				"compUrName"       => $do['name_ur'],
				"compShotName"     => $do['name_shot'],
				"compUrAddr"       => $do['address_yur'],
				"compFacAddr"      => $do['address_post'],
				"compDirName"      => $do['dir_name'],
				"compDirSignature" => $do['dir_signature'],
				"compDirStatus"    => $do['dir_status'],
				"compDirOsnovanie" => $do['dir_osnovanie'],
				"compInn"          => $d1[0],
				"compKpp"          => $d1[1],
				"compOgrn"         => $d2[1],
				"bank"             => $rs,
				"signers"          => array_values((array)array_values($signers)[0])
			];

		}

	break;

	case 'company.bank':

		$re = $db -> query("SELECT * FROM {$sqlname}mycomps_recv WHERE identity = '$identity'");
		while ($da = $db -> fetch($re)) {

			$bankr = explode(";", $da['bankr']);

			$response['data'][] = [
				"id"           => (int)$da['id'],
				"mcid"         => (int)$da['cid'],
				"tip"          => $da['tip'],
				"isDefault"    => $da['isDefault'],
				"ndsDefault"   => $da['ndsDefault'],
				"compNameRs"   => $da['title'],
				"compBankBik"  => $bankr[0],
				"compBankRs"   => $da['rs'],
				"compBankKs"   => $bankr[1],
				"compBankName" => $bankr[2]
			];

		}

	break;

	case 'company.signers':

		$response['data'][] = getSigner();

	break;

	default:
		$response['result']        = 'Error';
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';
	break;

}


print $rez = json_encode_cyr($response);