<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

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

//todo: Поиск по темам и комментариям
//todo: Убрать представление "Ответы"

$page      = $_REQUEST['page'];
$iduser    = $_REQUEST['iduser'];
$word      = str_replace(" ", "%", $_REQUEST['word']);
$keyword   = $_REQUEST['keyword'];
$tar       = $_REQUEST['tar'];
$isClose   = $_REQUEST['isClose'];
$isDeal    = $_REQUEST['isDeal'];
$isClient  = $_REQUEST['isClient'];
$isProject = $_REQUEST['isProject'];

$lists = (new \Salesman\Comments()) ->list($tar, $_REQUEST);

print json_encode_cyr($lists);

exit();