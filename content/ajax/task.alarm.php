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

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

global $userRights;

$tid = $_REQUEST['pid'];

$count = $db -> getOne("
	SELECT COUNT(*) as count 
	FROM ".$sqlname."tasks 
	WHERE 
		iduser = '$iduser1' AND 
		DATE(datum) = DATE(NOW()) AND 
		(TIME(totime) > TIME(NOW() - INTERVAL 5 MINUTE) and TIME(totime) < TIME(NOW() + INTERVAL 10 MINUTE)) AND 
		active != 'no' AND 
		alert = 'yes' AND 
		identity = '$identity' 
	ORDER BY datum, totime
");

if ((int)$count > 0) {

	//шаблон для уведомления
	$template = '
	<DIV style="height: 100vh;overflow-y: auto;">
		<div style="background : linear-gradient(rgba(59, 110, 170, 0.5), rgba(59, 110, 170, 0.7)), url(/assets/images/bg/bluelight.png) repeat, rgba(59, 110, 170, 1.0);" class="menu--block sticked--top">
			<a href="https://salesman.pro">
				<img src="/assets/images/alarm.png" style="padding: 5px; height: 30px">
			</a>
			<div class="fs-14 white Bold ml10 pt15">Ближайшие дела</div>
		</div>
		<div class="fs-11 flh-14 p10">
			<div>{message}</div>
			<div class="fs-07 pt10 text-right">CRM Team</div>
		</div>
	</DIV>
	';

	$result   = $db -> getAll("
		SELECT * 
		FROM ".$sqlname."tasks 
		WHERE 
			iduser = '$iduser1' AND 
			DATE(datum) = DATE(NOW()) AND 
			(TIME(totime) > TIME(NOW() - INTERVAL 5 MINUTE) and TIME(totime) < TIME(NOW() + INTERVAL 10 MINUTE)) AND 
			active != 'no' AND 
			alert = 'yes' AND 
			identity = '$identity' 
		ORDER BY datum, totime
	");
	foreach ($result as $data) {

		$totime1 = explode(":", $data['totime']);
		$totime1 = $totime1[0].":".$totime1[1];

		$pids     = yexplode(";", (string)$data['pid']);

		$do = $persons = '';

		$hours = difftime($data['created']);

		$change = ($hours <= $hoursControlTime || $userRights['changetask']) ? 'yes' : '';

		if ($data['autor'] == 0 || $data['autor'] == $iduser1 || $data['iduser'] == $iduser1) {
			$do = 'yes';
		}
		elseif ($userRights['changetask']) {
			$change = '';
		}

		//Формируем заголовок
		$title = '
		<div class="fs-14 mb10">
			<div class="Bold red"><i class="icon-clock"></i>'.$totime1.'&nbsp;&nbsp;'.get_priority('priority', $data['priority']).get_priority('speed', $data['speed']).'</div>
			<div class="mt15" title="'.$data['title'].'">Тема: <b>'.$data['title'].'</b></div>
		</div>
		';

		foreach ($pids as $pid) {

			$persons .= '<div class="blue fs-10 pt5"><i class="icon-user-1 broun"></i>&nbsp;<a href="/card.person?pid='.$pid.'">'.current_person($pid).'</a></div>';

		}

		//Формируем тело сообщения
		$html = '
			<div class="pt5">Тип дела: <b>'.$data['tip'].'</b></div>
			'.($data['des'] != '' ? '<div class="viewdiv mt10 mb10 p5" style="border-radius:5px;">'.link_it(str_replace("\n", "<br>", $data['des'])).'</div>' : '').'
			'.($data['clid'] > 0 ? '<div class="blue fs-10 pt5"><i class="icon-building blue"></i> <A href="/card.client?clid='.$data['clid'].'" title="Открыть карточку" target="_blank">'.current_client($data['clid']).'</A></div>' : '').'
			'.($data['did'] > 0 ? '<div class="blue fs-10 pt5"><i class="icon-briefcase red"></i> <A href="/card.deal?did='.$data['did'].'" title="Открыть карточку" target="_blank">'.current_dogovor($data['did']).'</A></div>' : '').'
			'.$persons.'
			'.($data['autor'] > 0 ? '<div class="black fs-12">Назначил: <b>'.current_user($data['autor']).'</b></div>' : '').'
		';

		$hide = ($change == 'yes') ? '' : 'hidden';

		$donotalert = '
			<div class="button--group mt10 div-center">
				<a href="#" onclick="doTask(\''.$data['tid'].'\');" class="button greenbtn wp30"><i class="icon-ok"></i>Выполнено</a>
				<a href="#" onclick="hideAlert(\''.$data['tid'].'\');" class="button redbtn wp30 '.$hide.'"><i class="icon-cancel-circled2"></i>Забыть</a>
				<a href="#" onclick="editTask(\''.$data['tid'].'\');" class="button wp30 '.$hide.'"><i class="icon-pencil"></i>Изменить</a>
			</div>
			';

		$message .= '<div class="alerts" id="alert'.$data['tid'].'">'.$title.$html.$donotalert.'<hr><div style="height:10px;"></div></div>';
		$html    = '';
		$title   = '';

	}
	$message = str_replace('{message}', $message, $template);
	?>
	<!DOCTYPE HTML>
	<html lang="ru">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Уведомление CRM</title>
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.card.css">
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.menu.css">
		<link rel="stylesheet" href="/assets/css/fontello.css">
		<STYLE type="text/css">
			<!--
			BODY {
				PADDING    : 0;
				margin     : 0;
				overflow-y   : auto;
				background : #FFF;
				height     : initial;
			}
			-->
		</STYLE>
		<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
		<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
		<script type="text/javascript" src="/assets/js/app.js?v=23"></script>
		<script type="text/javascript" src="/assets/js/jquery/ui.jquery.js"></script>
	</head>
	<body>
	<?= $message ?>
	<div id="music" class="hidden">
		<audio autoplay="autoplay">
			<source src="/assets/images/alarm.ogg" type="audio/ogg; codecs=vorbis">
			<source src="/assets/images/alarm.mp3" type="audio/mpeg">
		</audio>
	</div>
	<script>

		function hideAlert(id) {

			var url = '/content/core/core.tasks.php?action=hideAlert&tid=' + id;
			$.get(url, function () {

				var count = $('.alerts').length - 1;

				if (count < 1) window.close();
				else $('#alert' + id).remove();

			});

		}

		function editTask(id) {

			window.opener.editTask(id, 'edit').focus();
			window.opener.focus();

		}

		function doTask(id) {

			window.opener.editTask(id, 'doit');
			window.opener.focus();
			window.close();

		}

	</script>
	</body>
	</html>
	<?php
}