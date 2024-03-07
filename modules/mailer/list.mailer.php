<?php
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Mailer;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$params['page']   = $_REQUEST['page'];
$params['word']   = $_REQUEST['word'];
$params['folder'] = $_REQUEST['folder'];
$params['date1']  = $_REQUEST['date1'];
$params['date2']  = $_REQUEST['date2'];
$params['period'] = $_REQUEST['period'];
$params['clid']   = (int)$_REQUEST['clid'];
$params['pid']    = (int)$_REQUEST['pid'];
$params['did']    = (int)$_REQUEST['did'];

$keys = $db -> getRow("SHOW KEYS FROM {$sqlname}dostup WHERE Key_name = 'yindex'");
if (empty($keys)) {

	$db -> query("ALTER TABLE {$sqlname}dostup ADD INDEX `yindex` (`clid`, `pid`, `did`, `iduser`)");
	$db -> query("ALTER TABLE {$sqlname}dostup ADD INDEX `clid` (`clid`), ADD INDEX `did` (`did`), ADD INDEX `iduser` (`iduser`)");
	$db -> query("ALTER TABLE {$sqlname}ymail_messages ENGINE=MyISAM");

}

$list = new Mailer();

if (!isset($_REQUEST['card']) && $_REQUEST['folder'] != 'conversation') {

	$list -> params = $params;

	$list -> mailList();
	$lists = $list -> Messages;

}
elseif($_REQUEST['folder'] == 'conversation') {

	$list -> params = $params;

	$list -> mailListConversation();
	$lists = $list -> Messages;

}
else {

	$list -> params = $params;

	$list -> mailListCard();
	$l = $list -> Messages;

	//print_r($l);

	foreach ($l['list'] as $uid) {

		$list -> params = [];
		$list -> id     = $uid;
		$list -> mailView();

		$lists['list'][] = $list -> View;

	}

	$lists['page']    = (int)$l['page'];
	$lists['pageall'] = (int)$l['pageall'];

}

print json_encode_cyr($lists);
