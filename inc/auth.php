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

$rurl      = $_SERVER['REQUEST_URI'];
$ipaccesse = 'no';

require_once dirname( __DIR__ )."/inc/dbconnector.php";

$db = $GLOBALS['db'];

global $tipuser, $iduser1, $titleuser, $identity, $usertitle;

if ($_COOKIE['ses'] != '') {

	$result = $db -> getRow("SELECT * FROM {$sqlname}user WHERE ses='".$_COOKIE['ses']."'");
	$iduser1   = (int)$result["iduser"];
	$tipuser   = $result["tip"];
	$titleuser = $result["title"];
	$secrty    = $result["secrty"];
	$identity  = (int)$result["identity"];

	if( (int)$_COOKIE[ 'old' ] > 0) {

		$iduser1 = (int)$_COOKIE[ 'asuser' ];

		$result = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser='$iduser1' and identity = '$identity'");
		$iduser1   = (int)$result["iduser"];
		$usertitle = $result["title"];
		$tipuser   = $result["tip"];
		$mid       = (int)$result["mid"];
		$login     = $result["login"];
		$identity  = (int)$result["identity"];

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