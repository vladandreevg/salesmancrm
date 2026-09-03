<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/**
 * Проверка авторизации пользователя
 * в скриптах, загружаемых через Ajax
 */

use Salesman\User;

$rurl      = $_SERVER['REQUEST_URI'];
$ipaccesse = 'no';

require_once dirname( __DIR__ )."/inc/dbconnector.php";

$db = $GLOBALS['db'];

global $tipuser, $iduser1, $titleuser, $identity, $usertitle;

if ($_COOKIE['ses'] != '') {

	$result = $db -> getRow("SELECT * FROM {$sqlname}user WHERE ses=?s", $_COOKIE['ses']);
	$iduser1   = (int)$result["iduser"];
	$tipuser   = $result["tip"];
	$titleuser = $result["title"];
	$secrty    = $result["secrty"];
	$identity  = (int)$result["identity"];
	$isadmin   = $result["isadmin"];

	if( (int)$_COOKIE[ 'old' ] > 0) {

		// находим подчиненных
		$x = User::userArray($iduser1);
		$y = array_column(
			array_filter($x, static function($var) {
				return $var['secrty'] == 'yes';
			}),
			'id'
		);

		// замещение разрешено только если целевой пользователь назначил текущего
		// своим замещающим (zam) и не заблокирован (secrty='yes')
		// так же функция доступна администратору и ко всем подчиненным текущего юзера
		if ($isadmin == 'on' || canImpersonate($db, (int)$iduser1, (int)$_COOKIE['asuser'], (int)$identity) || in_array((int)$_COOKIE['asuser'], $y)) {

			$result = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser=?i and identity=?i", (int)$_COOKIE['asuser'], (int)$identity);
			$iduser1   = (int)$result["iduser"];
			$usertitle = $result["title"];
			$tipuser   = $result["tip"];
			$mid       = (int)$result["mid"];
			$login     = $result["login"];
			$identity  = (int)$result["identity"];
			$secrty    = $result["secrty"];
			$isadmin   = $result["isadmin"];

		}
		else {

			// невалидная попытка замещения — сбрасываем cookies
			setcookie("old", '', time() - 3600, "/");
			setcookie("asuser", '', time() - 3600, "/");

		}

	}

	if($iduser1 == 0) {

		setcookie("rurl", $rurl, time()+60000);

		print '
		<div class="warning text-left">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">Внимание:</b><br><br>Сбой авторизации. Авторизуйтесь заново <a href="/login" class="button">здесь</a>.<br>
		</div>
		';

		exit();
	}
	if($secrty == 'no'){
		print '
		<div class="warning text-left">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">Внимание:</b><br><br>Сбой авторизации. Ваш аккаунт заблокирован администратором.<br>
		</div>
		';

		exit();
	}

}
// Примечание: при отсутствии cookie сессии скрипт продолжает работу с $iduser1 = 0,
// т.к. auth.php подключается и в webhook-скриптах (PBX/API), работающих без сессии.
// Чувствительные скрипты обязаны самостоятельно проверять (int)$iduser1 > 0.

$result_set = $db -> getRow("select * from {$sqlname}settings WHERE id = '$identity'");
$ipaccesse = $result_set["ipaccesse"];
$ipstart   = $result_set["ipstart"];
$ipend     = $result_set["ipend"];
$ipmask    = $result_set["ipmask"];
$iplist    = explode(",", $result_set["iplist"]);
$iplistt   = $result_set["iplist"];


if ($ipaccesse=='yes'){

	$this_ip = $_SERVER["REMOTE_ADDR"];

}