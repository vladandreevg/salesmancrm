<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

$rootpath = dirname( __DIR__, 2 );
$ypath    = $rootpath.'/'."/plugins/cronManager/";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

$about = json_decode( str_replace( [
	"  ",
	"\t",
	"\n",
	"\r"
], "", file_get_contents( "plugin.json" ) ), true );

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title><?php echo $about['package']." - ".$about['name'] ?></title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="default">

	<link type="text/css" rel="stylesheet" href="/assets/css/app.css">
	<link type="text/css" rel="stylesheet" href="/assets/css/fontello.css">
	<link type="text/css" rel="stylesheet" href="assets/css/app.css">
	<link type="text/css" rel="stylesheet" href="/assets/js/smMultiSelect/smMultiSelect.css">

	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>
	<script src="/assets/js/moment.js/moment.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery.actual.min.js"></script>

	<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>

	<script type="text/javascript" src="assets/js/jquery.flexdatalist/jquery.flexdatalist.min.js"></script>
	<link type="text/css" rel="stylesheet" href="assets/js/jquery.flexdatalist/jquery.flexdatalist.min.css">

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<script type="text/javascript" src="assets/js/app.js"></script>

	<script type="text/javascript" src="/assets/js/smMultiSelect/smMultiSelect.js"></script>
</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" width="128">
	</div>
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div id="helper"><i class="icon-help-circled"></i></div>

<div class="cron-main box--child">

	<div class="" style="max-height: 100vh; overflow-y: auto">

		<div class="cron-first">

			<div class="flex-container float w400">

				<div class="flex-string w80">
					<img src="data:image/svg+xml;base64,<?php echo $about['iconSVGinBase64'] ?>" class="icon" width="50" height="50">
				</div>
				<div class="flex-string float">
					<div class="fs-20 flh-11 Bold"><?php echo $about['name'] ?></div>
					<div class="pl10"><?php echo $about['package'] ?></div>
				</div>

			</div>

			<h2><i class="icon-monitor blue"></i> Задания</h2>

			<div class="space-0"></div>

			<div class="p0 mt10 mb10" data-id="tasks"></div>

			<div class="sticked--bottom text-center">
				<a href="javascript:void(0)" onclick="$app.editTask(0)" class="button bluebtn mb20"><i class="icon-plus-circled"></i> Добавить задание</a>
			</div>

		</div>

		<div class="space-20"></div>
		<div class="gray center-text mt20">Сделано для SalesMan CRM</div>

	</div>
	<div class="graybg-lite pl20 pr20 relativ" id="help" style="max-height: 100vh; overflow-y: auto; overflow-x: hidden">

		<div id="helpcloser"><i class="icon-cancel"></i></div>

		<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">

			<?php
			$html = file_get_contents("readme.md");
			$Parsedown = new Parsedown();

			$maincrontask = '<div style="overflow-x:auto" class="viewdiv"><code class="blue">* * * * * <b>path/to/phpbin</b> '.$rootpath.'/plugins/cronManager/scheduler.php 1>> /dev/null 2>&1</code></div>';

			$xphp = getPhpInfo();
			$xbin = $xphp['bin'];

			$phppathText = "";
			$phppath = "php";
			//print "os= ".PHP_OS_FAMILY;

			if(PHP_OS_FAMILY == 'Linux') {

				$phppathText = '<div class="warning m0 mt5">Возможно установлена альтернативная версия PHP.<br>Путь до исполняемого файла определился как:<br><div class="warning bgwhite p5 enable--select">'.$xphp['bin'].'</div></div>';
				$phppath = $xphp['bin'];

			}

			$help = $Parsedown -> text($html);

			$help = str_replace( [
				"{{package}}",
				"{{version}}",
				"{{versiondate}}",
				"{{maincrontask}}",
				"{{cronpath}}",
				"{{phppathText}}"
			], [
				$about['package'],
				$about['version'],
				$about['versiondate'],
				$maincrontask,
				$rootpath.'/plugins/cronManager/',
				$phppathText
			], $help );

			print $help;
			?>

			<div class="space-50"></div>

		</div>

	</div>

</div>

</body>
</html>