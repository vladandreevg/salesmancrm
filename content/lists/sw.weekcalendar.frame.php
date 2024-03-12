<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*        ver. 2018.x           */
/* ============================ */

header('Content-Type: text/html; charset=utf-8');

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<title><?= $title ?> - <?= $productInfo['name'] ?></title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.js.css?v=2018.62">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.css?v=2018.62">
	<link rel="stylesheet" type="text/css" href="/assets/css/app.menu.css?v=2018.62">
	<link rel="stylesheet" type="text/css" href="/assets/css/mail.css?v=2018.62">
	<link rel="stylesheet" type="text/css" href="/assets/css/nanoscroller.css?v=2018.62">
	<link rel="stylesheet" type="text/css" href="/assets/css/ui.jquery.css">
	<link rel="stylesheet" type="text/css" href="/assets/css/animation.css">
	<?php

	if ($userSettings['userTheme'] != '') print '<link rel="stylesheet" id="theme" type="text/css" href="/css/themes/theme-'.$userSettings['userTheme'].'.css">';
	else print '<link rel="stylesheet" id="theme" type="text/css" href="/css/theme.css">';
	if ($userSettings['userThemeRound'] != '') print '<link rel="stylesheet" id="theme" type="text/css" href="/css/themes/theme-rounder.css">';

	?>
	<link rel="stylesheet" type="text/css" href="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.css">
	<link rel="stylesheet" type="text/css" href="/assets/css/introjs.css">
	<link rel="stylesheet" type="text/css" href="/assets/css/fontello.css?v=2018.60">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<script>
		<?php

		if ($pluginEnabled != '') print 'var $pluginEnambled = '.$pluginEnabled.';';
		else print 'var $pluginEnambled = [];';

		if (count($pluginJS) > 0) print 'var $pluginJS = ['.yimplode(",", $pluginJS).'];';
		else print 'var $pluginJS = [];';

		?>
	</script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js"></script>

	<script type="text/javascript" src="/assets/js/app.js?v=2019.4"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.nanoscroller.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.ptTimeSelect.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.form.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.meio.mask.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.autocomplete.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/ui.multiselect.js"></script>
	<script type="text/javascript" src="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js"></script>

	<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>
	<script type="text/javascript" src="/assets/js/intro.js/intro.min.js"></script>
	<script type="text/javascript" src="/assets/js/visibility.js/visibility.min.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.actual.min.js"></script>

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<!--подключение к Dadata-->
	<link type="text/css" href="/assets/js/dadata/suggestions.min.css" rel="stylesheet" />
	<script type="text/javascript" src="/assets/js/dadata/suggestions.jquery.min.js"></script>
	<script type="text/javascript" src="/assets/js/dadata/suggestions.addon.js"></script>

	<?php
	//"084337215b46bf12c598bf7f8d6322835abbce28";
	//$dadataKey = rij_decrypt($dadataKey, $skey, $ivc);

	$dadataKey = ($isCloud == true) ? DADATA : rij_decrypt($dadataKey, $skey, $ivc);

	print '
	<script>
		$dadata = "'.$dadataKey.'";
	</script>
	';
	?>

	<!--подключение к Dadata-->
</head>
<body>

<div class="message" id="message" style="display:none"><?= $message ?></div>
<div id="dialog_container" class="dialog_container">

	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" border="0" width="128">
	</div>
	<div class="dialog" id="dialog" align="left">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>

</div>

<div class="tableHeader wc" data-id="weekcal"></div>
<div id="weekCal" class="tableCal bgwhite" style="display: block !important; max-height: 100vh; overflow-y: auto"></div>

<script>

	$elm = $('#weekCal');

	$(function () {

		$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $(document).actual('height');
		var cdwidth = $(document).actual('width');

		$('.contentloader').height(cdheight).width(cdwidth);

		$.Mustache.load('/content/tpl/dt.weekcalendar.mustaache');

		$.ajax({
			type: "POST",
			url: "/content/desktop/weekcalendar.php",
			dataType: 'json',
			success: function (viewData) {

				$elm.empty().mustache('weekcalendarTpl', viewData);

				$elm.find('.contentloader').remove();

				var wcoffset = $elm.find('#today').offset();
				var wctop = wcoffset.top - 50;

				$elm.nanoScroller({scrollTop: wctop});

				var html = $elm.find('.weeks').html();

				$('.hour--event.wtodocal').each(function () {
					$(this).draggable({
						containment: '.weekcalendar',
						cursor: 'move',
						helper: 'clone',
						revert: false,
						zIndex: 100
					});
				});

				$('.adtask').each(function () {

					$(this).droppable({
						tolerance: "pointer",
						over: function (event, ui) {//если фигура над клеткой- выделяем её границей
							$(this).addClass('greenbg-sub');
						},
						out: function (event, ui) {//если фигура ушла- снимаем границу
							$(this).removeClass('greenbg-sub');
						},
						drop: function (event, ui) {//если бросили фигуру в клетку
							$(this).removeClass('greenbg-sub');//убираем выделение

							var olddatum = $(ui.draggable).data('old');
							var oldhour = $(ui.draggable).closest('.hour--block').data('hours');
							var newdatum = $(this).data('datum');
							var newhour = $(this).data('hours');
							var tid = $(ui.draggable).data('tid');

							var date1 = new Date();
							var date2 = new Date(newdatum);
							var timeDiff = Math.ceil(date2.getTime() - date1.getTime());
							var diffDays = Math.ceil(timeDiff / (1000 * 3600));

							//console.log(date1);
							//console.log(date2);

							if (diffDays >= 0) {

								var url = '/content/core/core.tasks.php?tid=' + tid + '&action=izmdatum&olddatum=' + olddatum + '&newdatum=' + newdatum + '&oldhour=' + oldhour + '&newhour=' + newhour;

								//console.log(url);

								$('#message').empty().fadeTo(1, 1).append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
								$.post(url, function (data) {

									$('#message').fadeTo(1, 1).css('display', 'block').html(data);
									setTimeout(function () {
										$('#message').fadeTo(1000, 0);
									}, 20000);

									changeMounth();

								});

							}

						},
						accept: '.adtask'
					});

				});

				setTimeout(function () {

					//$('.tableHeader[data-id="weekcal"]').html(html).css({"width": $('.ui-layout-center').actual('width') + "px"});

				}, 150);

			}
		});

	});

	$(document).on('click', '.actions', function () {

		var datum = $(this).closest('.hour--block').data('datum');
		doLoad('/content/forms/form.task.php?action=add&date=' + datum);

	});

	$(window).resize(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {
			$(this).trigger('resizeEnd');
		}, 500);

	});
	$(window).bind('resizeEnd', function () {

	});
</script>
</body>