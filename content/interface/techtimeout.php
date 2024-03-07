<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( 0 );

global $rootpath;

include $rootpath."/inc/config.php";

if ( !$istimeout ) {
	header( "Location: /" );
}
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Технические работы</title>
	<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
	<LINK rel="stylesheet" type="text/css" href="/assets/css/app.js.css">
	<LINK rel="stylesheet" type="text/css" href="/assets/css/ui.jquery.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<STYLE type="text/css">
		<!--
		body {
			background-color : #ECF0F1;
			background-image : repeating-linear-gradient(-45deg, transparent, transparent 5px, rgba(255, 255, 255, .5) 5px, rgba(255, 255, 255, .5) 10px);
		}
		-->
	</STYLE>
	<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
	<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
	<SCRIPT type="text/javascript" src="/assets/js/jquery/ui.jquery.js"></SCRIPT>
	<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
	<script>
		<?php

		print 'var $pluginEnambled = [];';
		print 'var $pluginJS = [];';

		?>
	</script>
</head>
<body>
<DIV id="dialog_container" class="dialog_container"></DIV>
<div class="techcontent div-center">

	<div class="icon paddbott20">
		<img src="/assets/images/logo.png" height="50px"><br>
	</div>

	<div class="text">
		<h1><img src="/assets/images/Services.svg" width="30px" height="30px">&nbsp;У нас технические работы.</h1>
		<h3>Прямо сейчас мы делаем SalesMan CRM лучше!</h3>
		<br>Примите наши извинения. Скоро мы закончим
	</div>

	<div class="signature paddtop10">Команда SalesMan</div>
</div>
<script>
	$('.techcontent').center();
</script>
</body>
</html>