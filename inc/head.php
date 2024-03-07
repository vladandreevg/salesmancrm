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
 * Подключение заголовков, стилей и js для основного интерфейса
 */

error_reporting(E_ERROR);
//error_reporting(E_ALL);
ini_set('display_errors', 1);

header("X-Content-Type-Options: nosniff");

$root = dirname( __DIR__ );

if (!file_exists($root."/inc/config.php")) {

	header("Location: /_install/");
	exit();

}

header('Content-Type: text/html; charset=utf-8');
header( "Pragma: no-cache" );

require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";
require_once $root."/inc/auth_main.php";
require_once $root."/inc/func.php";
require_once $root."/inc/settings.php";

require_once $root."/inc/language/".$language.".php";

global $userRights, $userSettings;
global $pluginEnabled, $pluginJS, $Language, $dadataKey, $skey, $ivc;

//start - отметка посещения CRM
$today = current_datum(); //сегодня
$today_time = current_datumtime(); //сегодня + текущее время

$periodDefault = getPeriod('month');

//найдем дату последнего визита
$resvizit = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."logs WHERE iduser = '$iduser1' and date_format(datum, '%Y-%m-%d')= '".current_datum()."' and type = 'Начало дня' and identity = '$identity' ORDER BY id") + 0;

//если значение найдено, значит он сегодня заходил
if ($resvizit < 1) {
	logger('9', 'Первый запуск за день', $iduser1);
}

//end - отметка посещения CRM
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
	<link rel="stylesheet" href="/assets/css/app.js.css?v=2023.1">
	<link rel="stylesheet" href="/assets/css/app.css?v=2024.1">
	<link rel="stylesheet" href="/assets/css/app.menu.css?v=2020.3">
	<link rel="stylesheet" href="/assets/css/mail.css?v=2023.1">
	<link rel="stylesheet" href="/assets/css/nanoscroller.css?v=2020.3">
	<link rel="stylesheet" href="/assets/css/ui.jquery.css">
	<link rel="stylesheet" href="/assets/css/animation.css">

	<?php
	$hooks->do_action('main__css');
	?>
	<?php

	if ($userSettings['userTheme'] != '') {
		print '<link rel="stylesheet" id="theme" type="text/css" href="/assets/css/themes/theme-'.$userSettings['userTheme'].'.css?v=2020.1">'."\n";
	}
	else {
		print '<link rel="stylesheet" id="theme" type="text/css" href="/assets/css/theme.css?v=2020.1">'."\n";
	}

	if ($userSettings['userThemeRound'] == 'yes') {
		print '<link rel="stylesheet" id="theme" type="text/css" href="/assets/css/themes/theme-rounder.css?v=2020.1">'."\n";
	}

	?>
	<link rel="stylesheet" href="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.css">
	<link rel="stylesheet" href="/assets/css/introjs.css">
	<link rel="stylesheet" href="/assets/css/fontello.css?v=2019.4">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<script>
		<?php
		print !empty($pluginEnabled) ? 'var $pluginEnambled = '.$pluginEnabled.';' : 'var $pluginEnambled = [];';
		print !empty($pluginJS) ? 'var $pluginJS = ['.yimplode(",", $pluginJS).'];' : 'var $pluginJS = [];';
		?>

		$Language = '<?=$Language?>';
		$language = <?=json_encode_cyr($lang)?>;

	</script>
	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>

	<script src="/assets/js/moment.js/moment.min.js"></script>
	<script src="/assets/js/app.js?v=2024.1"></script>

	<script src="/assets/js/jquery/jquery.nanoscroller.js"></script>
	<script src="/assets/js/jquery/jquery.ptTimeSelect.js"></script>
	<script src="/assets/js/jquery/jquery.form.js?v=2019.1"></script>
	<script src="/assets/js/jquery/jquery.meio.mask.min.js"></script>
	<script src="/assets/js/jquery/jquery.autocomplete.js"></script>
	<script src="/assets/js/jquery/ui.multiselect.js?v=2019.4"></script>
	<script src="/assets/js/jquery/jquery.tablednd.js"></script>
	<script src="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js"></script>
	<script src="/assets/js/resizeend/jquery.resizeend.min.js"></script>

	<script src="/assets/js/d3/d3.min.js"></script>
	<script src="/assets/js/intro.js/intro.min.js"></script>
	<script src="/assets/js/visibility.js/visibility.min.js"></script>
	<script src="/assets/js/mustache/mustache.js"></script>
	<script src="/assets/js/mustache/jquery.mustache.js"></script>
	<script src="/assets/js/ckeditor46/ckeditor.js?v=2018.62"></script>
	<script src="/assets/js/jquery/jquery.actual.min.js"></script>
	<script src="/assets/js/lodash.js/lodash.min.js"></script>

	<!--красивые алерты-->
	<script src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<!--Перемещаемые столбцы таблицы-->
	<!--https://akottr.github.io/dragtable/-->
	<!--
	<script type="text/javascript" src="js/dragtable-master/jquery.dragtable.js"></script>
	<link type="text/css" rel="stylesheet" href="js/dragtable-master/dragtable.css">
	-->

	<!--подключение к Dadata-->
	<link href="/assets/js/dadata/suggestions.min.css" rel="stylesheet">
	<script src="/assets/js/dadata/suggestions.jquery.min.js"></script>
	<script src="/assets/js/dadata/suggestions.addon.js"></script>

	<?php
	$hooks->do_action('main__js');
	?>

	<?php

	$dadataKey = rij_decrypt($dadataKey, $skey, $ivc);

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
<div class="tooltip" id="tooltip" style="display:none"></div>
<div id="swindow">

	<form name="swForm" id="swForm">

		<input type="hidden" id="swUrl" name="swUrl" value="">
		<input type="hidden" id="swPeriod" name="swPeriod" value="month">

		<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>
		<div class="header">Header</div>
		<div class="body">Body</div>
		<div class="footer div-center">

			<div class="flex-container box--child pt10">

				<div class="flex-string wp10 hidden-ipad"></div>
				<div class="flex-string wp80 div-center flh-12">

					<a href="javascript:void(0)" class="period hidden-iphone hidden-ipad" data-period="prevquart"> - Квартал</a>
					<a href="javascript:void(0)" class="period hidden-iphone" data-period="prevmonth"> - Месяц</a>
					<a href="javascript:void(0)" class="period" data-period="calendarweekprev"> - Неделя</a>
					<a href="javascript:void(0)" class="period" data-period="yestoday">Вчера</a>
					<a href="javascript:void(0)" class="period" data-period="today">Сегодня</a>
					<a href="javascript:void(0)" class="period" data-period="calendarweek">Неделя</a>
					<a href="javascript:void(0)" class="period active" data-period="month">Месяц</a>
					<a href="javascript:void(0)" class="period" data-period="quart">Квартал</a>
					<a href="javascript:void(0)" class="period hidden-iphone" data-period="year">Год</a>

				</div>
				<div class="flex-string wp10 hidden-ipad"></div>

			</div>

		</div>

	</form>

</div>
<div id="subwindow">

	<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>
	<div class="body">Body</div>

</div>

<div id="dialog_container" class="dialog_container">

	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>

</div>

<div id="caller" class="caller box--child">

	<div class="hid" onclick="hideCallWindow('hand')"><i class="icon-cancel-circled white"></i></div>
	<?php
	if ($GLOBALS['sip_active'] == 'yes' && in_array($GLOBALS['sip_tip'], (array)$sipHasCDR)) {

		if ( $productInfo['lastcalls'] ) {
			print '
				<div class="zag paddbott10 white top" id="lastcollsheader">
					<b>Последние звонки</b>&nbsp;<a href="javascript:void(0)" onclick="window.open(\'callhistory.php\')" title="Перейти в журнал звонков"><i class="icon-clock hand yelw"></i></a>
				</div>
				<div id="lastcolls" class="wp100">
					<div class="pad5">Загрузка данных...</div>
					<br>
				</div>
			';
		}

		print '
		<div id="peers"></div>
		<div class="zag paddbott10 white"><b>Звонки</b></div>
		<div id="inpeers">
			<div class="p5">Загрузка данных...</div>
		</div>
		';

	}
	?>
	<div id="callto"></div>

</div>

<div class="smframe--container">
	<iframe id="smframe"></iframe>
	<div class="smframe--close" title="Закрыть"><i class="icon-cancel"></i></div>
	<div class="smframe--url hidden" data-url="" title="Открыть в новом окне"><i class="icon-popup"></i></div>
</div>

<!--В этот блок можно помещать иконки плагинов-->
<div class="plugin--panel"></div>

<!--В этот блок можно помещать иконки обновления представлений и телефонии-->
<div class="refresh--panel"></div>

<?php
$hooks->do_action('main__body');
?>
