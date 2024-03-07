<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Knowledgebase;

error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$id = (int)$_REQUEST['id'];

$knowledgebase = new Knowledgebase();
$kb = $knowledgebase -> info($id);

$title    = $kb["title"];
$content  = $kb["content"];
$idcat    = $kb["idcat"];
$keywords = $kb["keywords"];
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title><?= $title ?>. SalesMan CRM</title>
	<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<STYLE type="text/css">
		<!--
		html {
			background : #CCCCCC;
			padding    : 10px;
			overflow-y: auto !important;
		}
		BODY {
			font-size   : 1.0em;
			FONT-FAMILY : tahoma, arial, serif;
			background  : #FFF;
			width       : 21cm;
			height      : auto;
			margin      : 0 auto;
			padding     : 20px 40px 20px 60px;
			overflow-y: auto !important;
		}
		tr, td, th, table {
			border          : 1px solid #333;
			border-collapse : collapse;
			padding         : 2px 3px 2px 3px;
			font-size       : 12px;
			font-family     : tahoma, arial, serif;
		}
		h1, h2 {
			line-height : 1.4em;
			font-weight : 600;
		}

		p, li {
			font-size   : 1.15em;
			line-height : 1.3em;
		}
		.noprint {
			display : none;
		}
		@media print {
			body {
				font-size          : 12px;
				background         : #FFFFFF;
				margin             : 0;
				padding            : 0;
				width              : auto;
				height             : auto;
				box-shadow         : 0 0 0 #FFFFFF;
				-moz-box-shadow    : 0 0 0 #FFFFFF;
				-webkit-box-shadow : 0 0 0 #FFFFFF;
			}

			html {
				background : #FFFFFF;
				padding    : 0;
			}
		}
		-->
	</STYLE>
</head>
<body>
<DIV class="zagolovok"><h1><?= $title ?></h1></DIV>
<div><?= $content ?></div>
</body>
</html>