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

	//информация о сотруднике
	case 'info':

		$s = '';

		if ($params['uid'] != '') {
			$s = "uid = '".$params['uid']."'";
		}
		elseif ($params['user'] != '') {
			$s = "login = '".$params['user']."'";
		}
		elseif ((int)$params['iduser'] > 0) {
			$s = "iduser = '".$params['iduser']."'";
		}

		if( !empty($s) ) {

			$re        = $db -> getRow("SELECT * FROM {$sqlname}user WHERE $s and identity = '$identity'");
			$title     = $re["title"];
			$login     = $re["login"];
			$otdel     = $re["otdel"];
			$phone     = $re["phone"];
			$phone_in  = $re["phone_in"];
			$mob       = $re["mob"];
			$mail_url  = $re["email"];
			$uid       = $re["uid"];
			$iduser    = (int)$re["iduser"];
			$tip       = $re["tip"];
			$user_post = $re["user_post"];
			$secrty    = $re["secrty"];

			$oid = $db -> getOne("select uid from {$sqlname}otdel_cat where idcategory = '$otdel' and identity = '$identity'");

			if ($login != '') {

				$response['data'] = [
					"iduser"    => $iduser,
					"uid"       => $uid,
					"oid"       => $oid,
					"title"     => $title,
					"login"     => $login,
					"active"    => $secrty,
					"tip"       => $tip,
					"user_post" => $user_post,
					"email"     => $mail_url,
					"phone"     => $phone,
					"phone_in"  => $phone_in,
					"mob"       => $mob
				];

			}
			else {

				$response['error']['code'] = '404';
				$response['error']['text'] = 'Пользователь не найден';

			}

		}
		else{

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры";

		}

	break;

	case 'list':

		$re = $db -> query("SELECT * FROM {$sqlname}user WHERE identity = '$identity'");
		while ($do = $db -> fetch($re)) {

			$otdel = $db -> getOne("SELECT uid FROM {$sqlname}otdel_cat WHERE idcategory = '".$do['otdel']."' and identity = '$identity'");

			$response['data'][] = [
				"iduser" => (int)$do['iduser'],
				"uid"    => $do['uid'],
				"oid"    => $otdel,
				"title"  => $do['title'],
				"login"  => $do['login'],
				"email"  => $do['email'],
				"active" => $do['secrty']
			];

		}

	break;

	case 'add':

		$login = untag($params['user']);
		$pwd   = $params['password'];

		$title = untag($params['title']);

		$boss = $db -> getOne("SELECT iduser FROM {$sqlname}user WHERE tip = 'Руководитель организации' and identity = '$identity' LIMIT 1");

		$tip = (isset($params['tip']) && $params['tip'] != '') ? $params['tip'] : "Менеджер продаж";
		$mid = (isset($params['boss']) && $params['boss'] != '') ? current_userbylogin($params['boss']) : $boss;

		$mail = untag($params['email']);

		if ( $isCloud ) $mail = $login;

		$otdel     = $db -> getOne("SELECT idcategory FROM {$sqlname}otdel_cat WHERE uid = '$params[otdel]' and identity = '$identity'");
		$territory = $params['territory'];

		$phone     = untag($params['phone']);
		$phone_in  = untag($params['phone_in']);
		$fax       = untag($params['fax']);
		$mob       = untag($params['mob']);
		$bday      = $params['bday'];
		$user_post = untag($params['user_post']);
		$uid       = untag($params['uid']);

		$CompStart = $params['CompStart'];
		$CompEnd   = $params['CompEnd'];

		$acs_analitics = 'on';
		$acs_maillist  = '';
		$acs_files     = 'on';
		$acs_price     = 'on';
		$acs_credit    = '';
		$acs_prava     = '';
		$acs_plan      = 'on';
		$show_marga    = 'yes';
		$ac_import     = 'off;on;off;off;off;off;off;off;on;on;on;on;on;on;on;on;on;off;off';
		$secrty        = '';
		$isadmin       = '';

		$salt    = generateSalt();
		$newpass = encodePass($pwd, $salt);

		//проверка на существующий емейл
		$count = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}user WHERE login='".trim($login)."'");

		if ($count > 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = 'Ошибка: Такой логин уже существует';

		}
		else {

			try {

				$arg = [
					"login"         => $login,
					"pwd"           => $newpass,
					"sole"          => $salt,
					"title"         => $title,
					"tip"           => $tip,
					"mid"           => $mid,
					"otdel"         => $otdel,
					"email"         => $email,
					"phone"         => $phone,
					"fax"           => $fax,
					"mob"           => $mob,
					"bday"          => $bday,
					"acs_analitics" => $acs_analitics,
					"acs_maillist"  => $acs_maillist,
					"acs_files"     => $acs_files,
					"acs_price"     => $acs_price,
					"acs_credit"    => $acs_credit,
					"acs_prava"     => $acs_prava,
					"tzone"         => '0:00',
					"viget_on"      => 'on;on;on;on;on;on;on;on;on;on;on;on;on;on',
					"viget_order"   => 'd1;d2;d3;d4;d5;d6;d7;d8;d9;d10;d11;d12;d13;d14',
					"secrty"        => 'yes',
					"acs_import"    => $ac_import,
					"show_marga"    => $show_marga,
					"user_post"     => $user_post,
					"acs_plan"      => $acs_plan,
					"uid"           => $uid,
					"identity"      => $identity
				];

				$db -> query("INSERT INTO {$sqlname}USER SET ?u", arrayNullClean($arg));

				$response['result']         = 'Success';
				$response['data'] = $db -> insertId();

			}
			catch (Exception $e) {

				$response['result']        = 'Error';
				$response['error']['code'] = 500;
				$response['error']['text'] = $e -> getMessage().' в строке '.$e -> getCode();

			}

		}

	break;

}

print $rez = json_encode_cyr($response);