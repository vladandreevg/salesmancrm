<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

global $rootpath;

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/settings.php";
//require_once $rootpath."/inc/func.php";

$action = $_REQUEST['action'];
$da1    = $_REQUEST['da1'];
$da2    = $_REQUEST['da2'];
$da     = $_REQUEST['da'];
$act    = $_REQUEST['act'];
$per    = $_REQUEST['per'];

if ( !$per ) {
	$per = 'nedelya';
}

$user_list    = $_REQUEST['user_list'];
$clients_list = $_REQUEST['clients_list'];
$persons_list = $_REQUEST['persons_list'];
$field        = $_REQUEST['field'];
$field_query  = $_REQUEST['field_query'];
$file         = $_REQUEST['report'];

$thisfile = $file;
?>
<!DOCTYPE HTML>
<HTML lang="ru">
<HEAD>
	<META http-equiv="Content-Type" content="text/html; charset=utf-8">
	<TITLE>Отчет</TITLE>
	<link rel="stylesheet" type="text/css" href="/assets/css/app.js.css?v=23">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.css?v=23">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.menu.css?v=23">
	<link rel="stylesheet" type="text/css" href="/assets/css/ui.jquery.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<STYLE type="text/css">
		<!--
		BODY {
			PADDING    : 0;
			margin     : 10px;
			overflow   : auto;
			background : #FFF;
			height     : auto;
		}

		html {
			height   : auto;
			overflow : auto;
		}

		a {
			text-decoration : none;
		}

		.header_tbl_ws {
			position           : fixed;
			margin-top         : 0;
			width              : 100%;
			display            : block;
			z-index            : 3;
			border-bottom      : 1px solid #79b7e7;
			box-shadow         : 1px 0 2px #999;
			-webkit-box-shadow : 1px 0 2px #999;
			-moz-box-shadow    : 1px 0 2px #999;
		}

		table {
			width          : 100%;
			border         : 1px solid #222;
			background     : #222;
			border-spacing : 1px;
		}
		table td {
			background : #FFF;
		}
		table thead tr th,
		table tbody tr td {
			border-bottom : 1px solid #222;
		}

		.noprint {
			display : none;
		}

		.forprint {
			width : unset;
		}

		-->
	</STYLE>
	<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
	<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
	<script type="text/javascript" src="/assets/js/jquery/ui.jquery.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.nanoscroller.js"></script>
	<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>
	<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
</HEAD>
<BODY>

<div id="dialog_container" class="dialog_container">

	<div class="dialog" id="dialog" align="left">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>

</div>

<?php
include $rootpath."/reports/".$file;
?>

</BODY>
</HTML>