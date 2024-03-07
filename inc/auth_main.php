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
 * Проверка авторизации
 * Используется в основном интерфейсе
 */

$rurl    = $_SERVER['REQUEST_URI'];
$iduser1 = 0;

$scheme = $_SERVER[ 'HTTP_SCHEME' ] ?? ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://';

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
	$isadmin   = $result["isadmin"];

	//print_r($result);
	//exit();

	if( (int)$_COOKIE[ 'old' ] > 0) {

		$iduser1 = (int)$_COOKIE[ 'asuser' ];

		$result = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
		$iduser1   = (int)$result["iduser"];
		$usertitle = $result["title"];
		$tipuser   = $result["tip"];
		$mid       = $result["mid"];
		$login     = $result["login"];
		$identity  = (int)$result["identity"];
		$isadmin   = $result["isadmin"];

	}

	//if($istimeout == true && $isadmin == 'off') header("Location: techtimeout.php");

	if($istimeout) {
		header("Location: techtimeout.php");
	}

	if($isremoval) {
		header("Location: removal.php");
	}

	if($iduser1 == 0) {

		setcookie("rurl", $rurl, time()+60000);
		header("Location: /login?rurl=".$rurl);

		exit;
	}
	if($secrty == 'no'){

		print '
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" href="/assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
		<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
		<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
		<div class="warning" style="width:500px">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">Внимание:</b><br><br>Ваш аккаунт заблокирован администратором.<br>
		</div>
		<script type="text/javascript">
			$(".warning").center();
		</script>
		';

		exit();

	}

}
if ($_COOKIE['ses'] == '') {

	setcookie("rurl", $rurl, time()+60000);
	header("Location: /login?rurl=".$rurl);

}

$result_set = $db -> getRow("select * from {$sqlname}settings WHERE id = '$identity'");
$ipaccesse = $result_set["ipaccesse"];
$ipstart   = $result_set["ipstart"];
$ipend     = $result_set["ipend"];
$ipmask    = $result_set["ipmask"];
$iplist    = explode(",", $result_set["iplist"]);
$iplistt   = $result_set["iplist"];

$toexit = false;

if ($ipaccesse == 'yes'){

	$this_ip = $_SERVER["REMOTE_ADDR"];

	if ($ipstart != ''){

		if($ipend == '') $ipend = $ipstart;

		$range = $ipstart."-".$ipend;

		if(!IP_match($this_ip,$range)) {

			$toexit = true;

		}

	}
	elseif ($ipmask!=''){

		$range = $ipmask;

		if(!IP_match($this_ip,$range)) {

			$toexit = true;

		}

	}
	elseif($iplistt != ''){

		if(!in_array( $this_ip, $iplist ) ) {

			$toexit = true;

		}

	}

	if($toexit){

		print '
			<TITLE>Предупреждение - SalesMan CRM</TITLE>
			<META content="text/html; charset=utf-8" http-equiv="Content-Type">
			<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
			<LINK rel="stylesheet" type="text/css" href="/assets/css/fontello.css">
			<SCRIPT src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
			<SCRIPT src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
			<SCRIPT src="/assets/js/app.extended.js"></SCRIPT>
			<div class="warning p20" style="width:600px">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">Внимание:</b><br><br>
				Доступ с IP <b>'.$this_ip.'</b> запрещен администратором<br>
			</div>
			<script type="text/javascript">
				$(".warning").center();
			</script>
			';

		exit();

	}

	/*
	if ($ipstart != ''){

		if($ipend == '') $ipend = $ipstart;

		$range = $ipstart."-".$ipend;

		if(!IP_match($this_ip,$range)) {

			print '
			<TITLE>Предупреждение - SalesMan CRM</TITLE>
			<META content="text/html; charset=utf-8" http-equiv="Content-Type">
			<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
			<LINK rel="stylesheet" href="/assets/css/fontello.css">
			<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.1.1.min.js"></SCRIPT>
			<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
			<div class="warning" style="width:600px">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">Внимание:</b><br><br>
				Доступ с IP <b>'.$this_ip.'</b> запрещен администратором<br>
			</div>
			<script type="text/javascript">
				$(".warning").center();
			</script>
			';

			exit();

		}

	}
	elseif ($ipmask!=''){

		$range = $ipmask;

		if(!IP_match($this_ip,$range)) {

			print '
				<TITLE>Предупреждение - SalesMan CRM</TITLE>
				<META content="text/html; charset=utf-8" http-equiv="Content-Type">
				<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
				<LINK rel="stylesheet" href="/assets/css/fontello.css">
				<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.1.1.min.js"></SCRIPT>
				<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
				<div class="warning" style="width:600px">
					<span><i class="icon-attention red icon-5x pull-left"></i></span>
					<b class="red uppercase">Внимание:</b><br><br>
					Доступ с IP <b>'.$this_ip.'</b> запрещен администратором<br>
				</div>
				<script type="text/javascript">
					$(".warning").center();
				</script>
			';
			exit();

		}

	}
	elseif($iplistt != ''){

		if(!in_array( $this_ip, $iplist ) ) {

			print '
				<TITLE>Предупреждение - SalesMan CRM</TITLE>
				<META content="text/html; charset=utf-8" http-equiv="Content-Type">
				<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
				<LINK rel="stylesheet" href="/assets/css/fontello.css">
				<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.1.1.min.js"></SCRIPT>
				<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
				<div class="warning" style="width:600px">
					<span><i class="icon-attention red icon-5x pull-left"></i></span>
					<b class="red uppercase">Внимание:</b><br><br>
					Доступ с IP <b>'.$this_ip.'</b> запрещен администратором<br>
				</div>
				<script type="text/javascript">
					$(".warning").center();
				</script>
			';

			exit();
		}

	}
	*/

}

$hidemenu = 'no';