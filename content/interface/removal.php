<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
global $rootpath;

error_reporting(0);

require_once $rootpath."/inc/config.php";

if (!$isremoval) {
	header("Location: /");
}
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Ваша CRM переехала</title>
	<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
	<LINK rel="stylesheet" type="text/css" href="/assets/css/app.js.css">
	<LINK rel="stylesheet" type="text/css" href="/assets/css/ui.jquery.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<STYLE type="text/css">
		body {
			background-color : #ECF0F1;
			background-image : repeating-linear-gradient(-45deg, transparent, transparent 5px, rgba(255, 255, 255, .5) 5px, rgba(255, 255, 255, .5) 10px);
		}
		.techcontent{
			width: 400px;
			position: absolute;
			top: calc(50% - 200px);
			left: calc(50% - 200px);
		}
	</STYLE>
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<SCRIPT src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
	<script src="/assets/js/jquery/ui.jquery.js"></script>
	<script src="/assets/js/app.extended.js"></script>
</head>
<body>
<DIV id="dialog_container" class="dialog_container"></DIV>
<div class="techcontent div-center">

	<div class="icon paddbott20">
		<img src="/assets/images/logo.png" height="50px"><br>
	</div>

	<div class="text">
		<h1><img src="/assets/images/Services.svg" width="30px" height="30px">&nbsp;CRM переехала на новый домен</h1>
		<h3 class="red"><?= $removalURL ?></h3>
		<br>Вы будете перенаправлены через <span class="count Bold red">10</span> сек.
	</div>

	<div class="signature mt20">Команда SalesMan</div>
</div>
<script>

	var countdown = 10;

	$(function () {

		setInterval(() => {
			counter()
		}, 1000);

	})

	function counter(){

		countdown--

		console.log(countdown)

		$('.count').html(countdown)

		if(countdown === 0){
			window.location = '<?= $removalURL ?>'
		}

	}

</script>
</body>
</html>