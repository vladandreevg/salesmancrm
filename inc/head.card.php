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
 * Подключение заголовков, стилей и js для карточек
 */

use Salesman\Client;
use Salesman\Person;

error_reporting(E_ERROR);

header("X-Content-Type-Options: nosniff");

$rootpath = dirname( __DIR__ );

if (!file_exists($rootpath."/inc/config.php")) {

	header("Location: /_install/");
	exit();

}

header('Content-Type: text/html; charset=utf-8');

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth_main.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

include $rootpath."/inc/language/".$language.".php";

$express = false;

global $pluginEnabled, $pluginJS, $Language, $dadataKey, $skey, $ivc;

if ($_GET['phone'] != '') {

	/**
	 * Функция добавления клиента в базу по номеру телефона
	 * Параметры:
	 * - phone - номер телефона
	 * - add = yes - автоматически добавлять клиента в базу
	 * - person = yes - автоматически добавлять контакт к клиенту (если add = yes)
	 * - uid - идентификатор клента во внешней системе
	 * - если не передан параметр add, то будет доступны кнопки добавления Обращения или Экспресс-форма
	 */

	$callerID = getxCallerID( $_GET['phone']);

	if ($callerID['clid'] > 0) {

		$_REQUEST['clid'] = $callerID['clid'];

		print
			"
			<script>
				window.location = 'card.client?clid=".$callerID['clid']."';
			</script>
			";

	}
	elseif ($callerID['pid'] > 0) {
		header( "Location: card.person?pid=".$callerID['pid'] );
	}
	elseif ($_GET['add'] == 'yes') {

		//include_once "./inc/class/Client.php";
		//include_once "./inc/class/Person.php";

		$max = $db -> getOne("SELECT MAX(clid) FROM {$sqlname}clientcat") + 1;

		$cparams['title']  = 'Новый клиент #'.$max;
		$cparams['phone']  = $_GET['phone'];
		$cparams['iduser'] = $iduser1;
		$cparams['uid']    = $_GET['uid'];

		$client = new Client();
		$result = $client -> add($cparams);

		$clid = $result['data'];

		if ($_REQUEST['person'] == 'yes') {

			$max = $db -> getOne("SELECT MAX(pid) FROM {$sqlname}personcat") + 1;

			$pparams['person']  = 'Новый контакт #'.$max;
			$pparams['ptitle']  = 'Сотрудник';
			$pparams['tel']     = $_GET['phone'];
			$pparams['iduser']  = $iduser1;
			$pparams['clid']    = $clid;
			$pparams['mperson'] = 'yes';

			$person = new Person();
			$result = $person -> edit(0, $pparams);

		}

		if ($clid > 0) {

			$_REQUEST['clid'] = $clid;

			print "
			<script>
				window.location = 'card.client?clid=".$clid."';
			</script>
			";
			exit();

		}

	}
	else {

		$express = true;

	}

}

// include "./inc/language/".$language.".php";

$action = $_REQUEST['action'];

//start - отметка посещения CRM
$resvizit = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}logs WHERE iduser = '$iduser1' and date_format(datum, '%Y-%m-%d')= '".current_datum()."' and type = 'Начало дня' and identity = '$identity' ORDER BY id");

//если значение найдено, значит он сегодня заходил
if ( $resvizit == 0) {

	logger('9', 'Первый запуск за день', $iduser1);

}
//end - отметка посещения CRM

$k = array_keys($_GET);

if ($express) {

	?>
	<!DOCTYPE HTML>
	<html lang="ru">
	<HEAD>
		<TITLE><?= $lang['all']['Notification'].' - '.$productInfo['name'] ?></TITLE>
		<meta content="text/html; charset=utf-8" http-equiv="content-type">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name="apple-mobile-web-app-status-bar-style" content="default">
		<link rel="stylesheet" type="text/css" href="/assets/css/app.css?v=2023.1">
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.js.css?v=2023.1">
		<link rel="stylesheet" type="text/css" href="/assets/css/app.menu.css?v=2023.1">
		<link rel="stylesheet" type="text/css" href="/assets/css/mail.css?v=2023.1">
		<LINK rel="stylesheet" type="text/css" href="/assets/css/ui.jquery.css">
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.card.css?v=2023.1">

		<?php
		if ($userSettings['userTheme'] != '') print '<link rel="stylesheet" type="text/css" href="css/themes/theme-'.$userSettings['userTheme'].'.css">';
		?>

		<link rel="stylesheet" type="text/css" href="/assets/css/nanoscroller.css">
		<link rel="stylesheet" type="text/css" href="/assets/css/fontello.css">
		<link rel="stylesheet" type="text/css" href="/assets/css/animation.css">
		<link rel="stylesheet" type="text/css" href="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.css">

		<script>
			<?php
			print !empty($pluginEnabled) ? 'var $pluginEnambled = '.$pluginEnabled.';' : 'var $pluginEnambled = [];';
			print !empty($pluginJS) ? 'var $pluginJS = ['.yimplode(",", $pluginJS).'];' : 'var $pluginJS = [];';
			?>
		</script>
		<script type="text/javascript" src="/assets/js/lodash.js/lodash.min.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js"></script>

		<script type="text/javascript" src="/assets/js/app.js?v=2023.14"></script>

		<script type="text/javascript" src="/assets/js/jquery/jquery.nanoscroller.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery.ptTimeSelect.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery.form.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery.meio.mask.min.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery.autocomplete.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/ui.multiselect.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery.tablednd.js"></script>
		<script type="text/javascript" src="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js"></script>

		<script type="text/javascript" src="/assets/js/d3/d3.min.js"></script>
		<script type="text/javascript" src="/assets/js/intro.js/intro.min.js"></script>
		<script type="text/javascript" src="/assets/js/visibility.js/visibility.min.js"></script>
		<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
		<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>
		<script type="text/javascript" src="/assets/js/ckeditor46/ckeditor.js?v=2018.62"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery.actual.min.js"></script>

		<!--подключение к Dadata-->
		<link href="/assets/js/dadata/suggestions.min.css" type="text/css" rel="stylesheet"/>
		<script type="text/javascript" src="/assets/js/dadata/suggestions.jquery.min.js"></script>
		<script type="text/javascript" src="/assets/js/dadata/suggestions.addon.js"></script>

		<!--красивые алерты-->
		<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
		<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

		<?php
		$dadataKey = rij_decrypt($dadataKey, $skey, $ivc);
		print '
		<script>
			$dadata = "'.$dadataKey.'";
		</script>';
		?>

	</HEAD>
	<BODY>
	<div id="swindow">

		<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>
		<div class="header">Header</div>
		<div class="body">Body</div>

	</div>
	<div id="subwindow">

		<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>
		<div class="body">Body</div>

	</div>
	<DIV id="dialog_container" class="dialog_container">
		<div class="dialog-preloader">
			<img src="/assets/images/rings.svg" border="0" width="128">
		</div>
		<DIV class="dialog" id="dialog" align="left">
			<DIV class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></DIV>
			<DIV id="resultdiv"></DIV>
		</DIV>
	</DIV>

	<DIV class="message" id="message" style="display:none"></DIV>

	<div style="width: 100vw; height: 100vh;">

		<div class="viewdiv flex-container block pb20" align="left" style="width:600px; position: absolute; z-index:1">

			<div class="flex-string wp100 div-center fs-12 flh-14 mb20 mt20">
				Указанный номер не найден в базе. Вы можете добавить его.
			</div>

			<div class="flex-string wp100 div-center fs-25 mb20 mt20">
				<?= $_GET['phone'] ?>
			</div>

			<div class="flex-string wp100 div-center mb20 mt20"></div>

			<div class="flex-string wp10 div-center"></div>
			<div class="flex-string wp80 div-center Bold expressbuttons">

				<a href="javascript:void()" onclick="editEntry('','edit', '<?= $_GET['phone'] ?>');" class="button greenbtn fs-14 flh-14 pad10 pr20 pl20 Bold"><i class="icon-phone-squared"><i class="sup icon-plus-circled"></i></i>&nbsp;&nbsp;Обращение</a>

				<a href="javascript:void()" onclick="expressClient('<?= $_GET['phone'] ?>');" class="button fs-14 flh-14 pad10 pr20 pl20 Bold"><i class="icon-building"><i class="sup icon-direction"></i></i>&nbsp;&nbsp;Экспресс</a>

			</div>
			<div class="flex-string wp10 div-center"></div>

		</div>

	</div>

	<script type="text/javascript">
		$(".block").center();
	</script>
	</BODY>
	</html>
	<?php
	exit();

}

if ((empty($_GET) || $_GET[ $k[0] ] == '' || $_GET[ $k[0] ] == 0) && $_GET['phone'] == '') {

	print '
		<TITLE>'.$lang['all']['Notification'].' - '.$productInfo['name'].'</TITLE>
		<LINK rel="stylesheet" type="text/css" href="assets/css/app.css">
		<LINK rel="stylesheet" href="assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="assets/js/jquery/jquery-3.1.1.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="assets/js/app.js"></SCRIPT>
		<div id="dialoge">
			<div class="warning p20" align="left" style="width:600px">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">'.$lang['all']['Attention'].':</b><br><br>
				'.$lang['msg']['CantFindOrNotAccess'].'<br>
			</div>
			<div>
				<DIV id="ctitle" class="wp100 text-center" style="position:initial;">
					<DIV id="close" onClick="window.close();" class="p10 pl20 pr20 hand button redbtn" style="position:initial;"><i class="icon-cancel-circled red"></i>Закрыть</DIV>
				</DIV>
			</div>
		</div>
		
		<script type="text/javascript">
			$("#dialoge").center();
		</script>
		';

	exit();

}

$url_path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', trim($url_path, ' /'));

//print_r($url_path);

$error = false;
$owner = 0;
$thisfile = str_replace(".php", "", $uri_parts[0]);
$clid = $did = $pid = 0;

// print basename($thisfile);
// print basename($_SERVER['REDIRECT_URL']);

if ((int)$_REQUEST['clid'] > 0 && $thisfile == 'card.client') {

	$clid = (int)$_REQUEST['clid'];

	$result      = $db -> getRow("SELECT title, type, clid, iduser FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'");
	$title       = $result["title"];
	$client_type = $result["type"];
	$clidd       = (int)$result["clid"];
	$owner       = (int)$result["iduser"];

	if ($clidd == 0) {
		$error = true;
	}

}
if ((int)$_REQUEST['pid'] > 0 && $thisfile == 'card.person') {

	$pid = (int)$_REQUEST['pid'];
	$clid = 0;

	$result = $db -> getRow("SELECT person, pid FROM {$sqlname}personcat WHERE pid = '$pid' and identity = '$identity'");
	$title  = $result["person"];
	$pidd   = $result["pid"];

	if ((int)$pidd < 1) {

		$error = true;

	}

}
if ((int)$_REQUEST['did'] > 0 && $thisfile == 'card.deal') {

	$did = (int)$_REQUEST['did'];

	$result = $db -> getRow("SELECT title, did, iduser FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
	$title  = $result["title"];
	$didd   = (int)$result["did"];
	$owner  = (int)$result["iduser"];

	if ( $didd == 0 || (int)$_GET['did'] == 0) {

		$error = true;

	}

}
if ((int)$_REQUEST['comid'] > 0 && $thisfile == 'card.comments') {

	$comid = (int)$_REQUEST['comid'];

	$result  = $db -> getRow("select title, clid, pid, did from {$sqlname}comments where id='".$comid."' and identity = '$identity'");
	$commnum = $db -> affectedRows($result);
	$title   = $result["title"];
	$clidd   = (int)$result["clid"];
	$pidd    = (int)$result["pid"];
	$didd    = (int)$result["did"];

	if ( $commnum < 1) {

		$error = true;

	}

}
if ((int)$_REQUEST['n_id'] > 0 && $thisfile == 'card.modcatalog') {

	$n_id = (int)$_REQUEST['n_id'];

	$title = $db -> getOne("select title from {$sqlname}price where n_id='".$n_id."' and identity = '$identity'");

	if ($title == '') {
		$error = true;
	}

}

if ($error) {

	print '
		<TITLE>'.$lang['all']['Notification'].' - '.$productInfo['name'].'</TITLE>
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" type="text/css" href="/assets/css/fontello.css">
		<SCRIPT src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
		<SCRIPT src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
		<SCRIPT src="/assets/js/app.extended.js"></SCRIPT>
		<div id="dialoge">
			<div class="warning p20" style="width:600px">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">'.$lang['all']['Attention'].':</b><br><br>
				'.$lang['msg']['CantFindOrNotAccess'].'<br>
			</div>
			<div>
				<DIV id="ctitle" class="wp100 text-center" style="position:initial;">
					<DIV id="close" onClick="window.close();" class="p10 pl20 pr20 hand button redbtn" style="position:initial;"><i class="icon-cancel-circled red"></i>Закрыть</DIV>
				</DIV>
			</div>
		</div>
		
		<script type="text/javascript">
			$("#dialoge").center();
		</script>
		';

	exit();

}

$xdostup     = $acs_prava;
$haveAccesse = get_accesse((int)$clid, (int)$pid, (int)$did) != 'yes';

$xfilter = get_people( (int)$userSettings['filterAllBy'], "yes" );
if( $clid > 0 && $owner > 0 && $userSettings['filterAllByClientCard'] == 'yes' && in_array($iduser1, $xfilter) && in_array($owner, $xfilter) ){

	$xdostup = 'on';
	$haveAccesse = true;

}
if( $did > 0 && $owner > 0 && $userSettings['filterAllByDealCard'] == 'yes' && in_array($iduser1, $xfilter) && in_array($owner, $xfilter) ){

	$xdostup = 'on';
	$haveAccesse = true;

}
?>
<!DOCTYPE HTML>
<html lang="ru">
<HEAD>
	<TITLE><?= $title ?> - <?= $productInfo['name'] ?></TITLE>
	<META content="text/html; charset=utf-8" http-equiv="Content-Type">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<link rel="stylesheet" href="/assets/css/app.css?v=2024.1">
	<LINK rel="stylesheet" href="/assets/css/app.js.css?v=2020.1">
	<link rel="stylesheet" href="/assets/css/app.menu.css?v=2020.1">
	<link rel="stylesheet" href="/assets/css/mail.css?v=2019.4">
	<LINK rel="stylesheet" href="/assets/css/ui.jquery.css">
	<LINK rel="stylesheet" href="/assets/css/app.card.css?v=2022.3">

	<?php
	$hooks->do_action('card__css');
	?>

	<?php
	if ($userSettings['userTheme'] != '') {
		print '<link rel="stylesheet" type="text/css" href="/assets/css/themes/theme-'.$userSettings['userTheme'].'.css?v=2020.1">';
	}
	if ($userSettings['userThemeRound'] == 'yes') {
		print '<link rel="stylesheet" id="theme" type="text/css" href="/assets/css/themes/theme-rounder.css?v=2020.1">';
	}
	?>
	<link rel="stylesheet" href="/assets/css/nanoscroller.css">
	<link rel="stylesheet" href="/assets/css/fontello.css?v=2019.4">
	<link rel="stylesheet" href="/assets/css/animation.css">
	<link rel="stylesheet" href="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.css">
	<link rel="stylesheet" href="/assets/css/introjs.css">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" media="screen and (max-width: 767px)" href="/assets/css/app.card.mobile.css?v=2020.1">
	<script>
		<?php
		print !empty($pluginEnabled) ? 'var $pluginEnambled = '.$pluginEnabled.';' : 'var $pluginEnambled = [];';
		print !empty($pluginJS) ? 'var $pluginJS = ['.yimplode(",", $pluginJS).'];' : 'var $pluginJS = [];';
		?>
	</script>
	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script src="/assets/js/jquery/jquery-ui.min.js?v=2019.4"></script>

	<script src="/assets/js/moment.js/moment.min.js"></script>
	<script src="/assets/js/app.js?v=2024.1"></script>
	<script src="/assets/js/lodash.js/lodash.min.js"></script>

	<script src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<script src="/assets/js/jquery/jquery.nanoscroller.js"></script>
	<script src="/assets/js/jquery/jquery.ptTimeSelect.js"></script>
	<script src="/assets/js/jquery/jquery.form.js?v=2019.4"></script>
	<script src="/assets/js/jquery/jquery.meio.mask.min.js"></script>
	<script src="/assets/js/jquery/jquery.autocomplete.js"></script>
	<script src="/assets/js/jquery/ui.multiselect.js?v=2019.4"></script>
	<script src="/assets/js/jquery/jquery.tablednd.js"></script>
	<script src="/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js"></script>

	<script src="/assets/js/d3/d3.min.js"></script>
	<script src="/assets/js/intro.js/intro.min.js"></script>
	<script src="/assets/js/visibility.js/visibility.min.js"></script>
	<script src="/assets/js/mustache/mustache.js"></script>
	<script src="/assets/js/mustache/jquery.mustache.js"></script>
	<script src="/assets/js/ckeditor46/ckeditor.js"></script>
	<script src="/assets/js/jquery/jquery.actual.min.js"></script>

	<!--подключение к Dadata-->
	<link href="/assets/js/dadata/suggestions.min.css" rel="stylesheet">
	<script src="/assets/js/dadata/suggestions.jquery.min.js"></script>
	<script src="/assets/js/dadata/suggestions.addon.js"></script>

	<script>
		$Language = '<?=$Language?>';
		$language = <?=json_encode_cyr($lang)?>;
	</script>

	<?php
	$hooks->do_action('card__js');
	?>

	<?php
	$dadataKey = rij_decrypt($dadataKey, $skey, $ivc);

	print '
		<script>
			$dadata = "'.$dadataKey.'";
		</script>';
	?>
</HEAD>
<BODY>
<?php
if ( $xdostup != 'on' && $haveAccesse ) {

	print '
	<div id="dialoge">
		<div class="warning p20" align="left" style="width:600px">
			<span><i class="icon-attention red icon-5x pull-left"></i></span>
			<b class="red uppercase">'.$lang['all']['Attention'].':</b><br><br>
			'.$lang['msg']['NotAccess'].'<br>
		</div>
		<div>
			<DIV id="ctitle" class="wp100 text-center" style="position:initial;">
				<DIV id="close" onClick="window.close();" class="p10 pl20 pr20 hand button redbtn" style="position:initial;"><i class="icon-cancel-circled red"></i>Закрыть</DIV>
			</DIV>
		</div>
	</div>
	
	<script type="text/javascript">
		$("#dialoge").center();
	</script>
	';

	exit;

}
?>
<DIV class="message" id="message" style="display:none"></DIV>
<div id="swindow">

	<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>
	<div class="header">Header</div>
	<div class="body">Body</div>

</div>
<div id="subwindow">

	<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>
	<div class="body">Body</div>

</div>
<DIV id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" border="0" width="128">
	</div>
	<DIV class="dialog" id="dialog" align="left">
		<DIV class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></DIV>
		<DIV id="resultdiv"></DIV>
	</DIV>
</DIV>
<div id="caller" class="caller">
	<div class="hid"><i class="icon-cancel-circled white" onclick="hideCallWindow()"></i></div>
	<div id="peers"></div>
	<div id="inpeers"></div>
	<div id="callto"></div>
</div>

<?php
$hooks->do_action('card__body');
?>