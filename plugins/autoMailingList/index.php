<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2016.20         */
/* ============================ */
set_time_limit( 0 );

error_reporting( E_ERROR );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

function getLists($type, $params): array {

	$lists = $list = [];
	$error = '';

	switch ($type) {

		case "MailerLite":

			require_once "class/MailerLite/Base/RestBase.php";
			require_once "class/MailerLite/Base/Rest.php";
			require_once "class/MailerLite/Subscribers.php";
			require_once "class/MailerLite/Lists.php";

			$ML_Lists = new MailerLite\Lists( $params['apikey'] );
			$list     = json_decode( $ML_Lists -> getAll(), true );
			$lists    = $list['Results'];
			$error    = $list['error']['message'];

		break;
		case "Unisender":

			require_once "class/Unisender/unisenderApi.php";

			$ML_Lists = new UniSenderApi( $params['apikey'] );
			$list     = json_decode( $ML_Lists -> getLists( [] ), true );

			foreach ( $list['result'] as $i => $item ) {
				$lists[] = [
					"id"   => $item['id'],
					"name" => $item['title']
				];
			}

			$error = $list['code'];

		break;
		case "MailChimp":

			require_once 'class/MailChimp/MCAPI.class.php';

			$api  = new MCAPI( $params['apikey'] );
			$list = $api -> lists();

			foreach ( $list['data'] as $i => $item ) {
				$lists[] = [
					"id"   => $item['id'],
					"name" => $item['name']
				];
			}

			$error = $list['code'];

		break;
		case "SendPulse":

			require_once('class/SendPulse/api/sendpulseInterface.php');
			require_once('class/SendPulse/api/sendpulse.php');

			define( 'API_USER_ID', $params['userid'] );
			define( 'API_SECRET', $params['apikey'] );
			define( 'TOKEN_STORAGE', 'file' );
			define( 'PATH_TO_ATTACH_FILE', 'data/'.$params['fpath'] );


			$SPApiProxy = new SendpulseApi( API_USER_ID, API_SECRET, TOKEN_STORAGE, PATH_TO_ATTACH_FILE );

			$data = [
				'attachments' => [
					'file.txt' => file_get_contents( PATH_TO_ATTACH_FILE )
				]
			];
			$list = $SPApiProxy -> listAddressBooks();

			foreach ( $list as $i => $item ) {
				$lists[] = [
					"id"   => $item -> id,
					"name" => $item -> name
				];
			}

			$error = $list['code'];

		break;
		case "GetResponse":

			require_once('class/GetResponse/src/GetResponseAPI3.class.php');

			$getresponse = new GetResponse( $params['apikey'] );

			$list = $getresponse -> getCampaigns();

			$er = $list -> error;

			$error = '';

			if ( $er == '' ) {

				foreach ( $list as $i => $item ) {
					$lists[] = [
						"id"   => $item -> campaignId,
						"name" => $item -> name
					];
				}

			}
			else $error = $list -> error.": ".$list -> message;

		break;
		case "eSputnik":

			$url = 'https://esputnik.com/api/v1/groups';

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_HEADER, 1 );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, [
				'Accept: application/json',
				'Content-Type: application/json'
			] );
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_USERPWD, $params['userid'].':'.$params['apikey'] );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

			$list = curl_exec( $ch );

			$e = explode( "\n", $list );

			$error = yexplode( " ", $e[0], 2 );
			$list  = json_decode( $e[9], true );

			curl_close( $ch );

			if ( count( $list ) > 0 ) {

				foreach ( $list as $i => $item ) {
					$lists[] = [
						"id"   => $item['id'],
						"name" => $item['name']
					];
				}
				$error = NULL;

			}

		break;

	}

	return [
		"list"  => $lists,
		"error" => $error
	];

}

$ypath  = $rootpath."/plugins/autoMailingList/";
$action = $_REQUEST['action'];

$identity = (int)$GLOBALS['identity'];
$iduser1  = (int)$GLOBALS['iduser1'];

$scheme = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');

$periodStart = str_replace( "/", "-", $_REQUEST['periodStart'] );
$periodEnd   = str_replace( "/", "-", $_REQUEST['periodEnd'] );

if ( !$periodStart ) {
	
	$period = getPeriod( 'month' );

	$periodStart = $period[0];
	$periodEnd   = $period[1];
	
}

$fpath = '';

if ( $isCloud ) {

	//создаем папки хранения файлов
	if ( !file_exists( "data/".$identity ) ) {

		mkdir( "data/".$identity, 0777 );
		chmod( "data/".$identity, 0777 );

	}

	$fpath = $identity.'/';

}

$access = [];

//загружаем настройки доступа
$file     = $ypath.'data/'.$fpath.'access.json';
$settings = json_decode( file_get_contents( $ypath.'data/'.$fpath.'settings.json' ), true );

$settings['type'] = ($settings['type'] == '') ? "MailerLite" : $settings['type'];

//если настройки произведены, то загружаем их
if ( file_exists( $file ) && $action != 'access.do' ) {

	$access = json_decode( file_get_contents( $file ), true );

}
else {

	$access = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY title" );

}

$lists = [];

//загружаем настройки
$config   = json_decode( (string)file_get_contents( __DIR__.'/data/'.$fpath.'settings.json' ), true );
$settings = json_decode( (string)file_get_contents( __DIR__.'/data/'.$fpath.'lists.json' ), true );
$lists    = $settings['lists'];
$spisok   = json_encode_cyr( $lists );

// поддержка старого хранения настроек
if(is_array($config['list'])){
	$config = $config['list'];
}

$etype = [
	"client.add" => "Новый клиент",
	"person.add" => "Новый контакт",
	"deal.add"   => "Новая сделка",
	"deal.close" => "Закрытая сделка"
];

//настройка доступа
if ( $action == 'access.do' ) {

	$preusers = $_REQUEST['preusers'];

	$params = json_encode_cyr( $preusers );

	$f    = $ypath.'data/'.$fpath.'access.json';
	$file = fopen( $f, "w" );

	if ( !$file ) {
		$rez = 'Не могу открыть файл';
	}
	else {

		if ( fputs( $file, $params ) === false ) {
			$rez = 'Ошибка записи';
		}
		else $rez = 'Записано';

		fclose( $file );

	}

	print $rez;

	exit();

}
if ( $action == "access" ) {
	?>
	<DIV class="zagolovok"><B>Доступы пользователей:</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="access.do">

		<div class="row" style="overflow-y: auto; max-height: 350px">
			<?php
			$da = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
			foreach ( $da as $data ) {
				?>
				<label style="display: inline-block; width: 50%; box-sizing: border-box; float: left; padding-left: 20px">
					<div class="column grid-1">
						<input name="preusers[]" type="checkbox" id="preusers[]" value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], (array)$access ) )
							print 'checked'; ?>>
					</div>
					<div class="column grid-9">
						<?= $data['title'] ?>
					</div>
				</label>
				<?php
			}
			?>
		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="saveAccess()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		$('#dialog').css('width', '700px');

		function saveAccess() {

			var str = $('#Form').serialize();

			$('#dialog_container').css('display', 'none');

			$.post("index.php", str, function (data) {

				yNotifyMe("CRM. Результат," + data + ",signal.png");

				DClose();

			});
		}
	</script>
	<?php

	exit();
}

if ( $action == 'check.webhook' ) {

	$hook = [];

	$url  = $scheme.$_SERVER["HTTP_HOST"]."/plugins/autoMailingList/events";
	$url2 = "{HOME}/plugins/autoMailingList/events";

	$hook['client.expressadd'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.expressadd' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.expressadd\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['client.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="client.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['person.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'person.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="person.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'person.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.close'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.close' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.close">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.close\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	print '
	<ul>
		<li>Подключение к событию "<b>client.expressadd</b>" - '.$hook['client.expressadd'].'</li>
		<li>Подключение к событию "<b>client.add</b>" - '.$hook['client.add'].'</li>
		<li>Подключение к событию "<b>person.add</b>" - '.$hook['person.add'].'</li>
		<li>Подключение к событию "<b>deal.add</b>" - '.$hook['deal.add'].'</li>
		<li>Подключение к событию "<b>deal.close</b>" - '.$hook['deal.close'].'</li>
	</ul>
	';

	exit();

}

//todo: доп.настройки - Только основному контакту, Только должностям (=,содержит)

if ( $action == 'save' ) {

	//$param['apikey']   = $config['apikey'];

	for ( $i = 0; $i < count( (array)$_REQUEST['relation'] ); $i++ ) {

		$param['list'][ $i ]['listID']   = (int)$_REQUEST['listID'][ $i ];
		$param['list'][ $i ]['relation'] = $_REQUEST['relation'][ $i ];
		$param['list'][ $i ]['event']    = $_REQUEST['event'][ $i ];
		$param['list'][ $i ]['period']   = $_REQUEST['period'][ $i ];
		$param['list'][ $i ]['tip']      = $_REQUEST['tip'][ $i ];
		$param['list'][ $i ]['theme']    = $_REQUEST['theme'][ $i ];

	}

	//print_r($param);

	$error = [];

	$f    = __DIR__.'/data/'.$fpath.'settings.json';
	$file = fopen( $f, "w" );

	if ( !$file )
		$rez = 'Не могу открыть файл';
	else {

		if ( fputs( $file, json_encode_cyr( $param ) ) === false ) {
			$rez = 'Ошибка записи';
		}
		else $rez = 'Настройки записаны';

		fclose( $file );

	}

	//подключаемся к Сервису

	//проверка подключения к сервису

	print $rez;

	exit();
}
if ( $action == "settings" ) {

	?>
	<DIV class="zagolovok"><B>Настройка подключения:</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="getLists">

		<div class="row">

			<div class="column grid-2 right-text ">
				<label for="apikey" class="paddtop5">Email-сервис:</label>
			</div>
			<div class="column grid-8">
				<span class="select">
				<select name="type" id="type" class="wp97">
					<option value="MailerLite" <?php if ( $settings['type'] == 'MailerLite' ) print "selected"; ?> data-type="MailerLite">MailerLite</option>
					<option value="Unisender" <?php if ( $settings['type'] == 'Unisender' )
						print "selected"; ?> data-type="Unisender">Unisender</option>
					<option value="MailChimp" <?php if ( $settings['type'] == 'MailChimp' )
						print "selected"; ?> data-type="MailChimp">MailChimp</option>
					<option value="SendPulse" <?php if ( $settings['type'] == 'SendPulse' )
						print "selected"; ?> data-type="SendPulse">SendPulse</option>
					<option value="GetResponse" <?php if ( $settings['type'] == 'GetResponse' )
						print "selected"; ?> data-type="GetResponse">GetResponse</option>
					<option value="eSputnik" <?php if ( $settings['type'] == 'eSputnik' )
						print "selected"; ?> data-type="eSputnik">eSputnik</option>
				</select>
				</span>
			</div>

		</div>

		<div class="typ apikey">

			<hr>

			<div class="row hidden type SendPulse eSputnik">

				<div class="column grid-2 right-text">
					<label for="userid" class="paddtop5">API USER_ID:</label>
				</div>
				<div class="column grid-8">
					<input type="text" name="userid" id="userid" value="<?= $settings['param']['userid'] ?>" class="wp97">
				</div>

			</div>
			<div class="row" style="overflow-y: auto; max-height: 350px">

				<div class="column grid-2 right-text ">
					<label for="apikey" class="paddtop5">API Key:</label>
				</div>
				<div class="column grid-8">
					<input type="text" name="apikey" id="apikey" value="<?= $settings['param']['apikey'] ?>" class="wp97">
				</div>

			</div>

		</div>

		<hr>

		<div class="div-center">

		</div>

		<div class="pad10 pr30" id="Lists">

			<div>Загруженные списки:</div>

			<ul>
				<?php
				foreach ($lists as $list) {

					$t = ($list['total'] > 0) ? '</b>, Подписчиков: <b>'.$list['total'].'</b>' : '';

					print '<li>ID: <b>'.$list['id'].'</b>, Название: <b>'.$list['name'].'</b>'.$t.'</li>';

				}
				?>
			</ul>

		</div>

		<hr>

		<div class="button--pane text-right">
			
			<a href="javascript:void(0)" onclick="getLists()" title="Проверить" class="button">Загрузить списки</a>
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
			
		</div>
	</form>
	<script>

		$(function () {

			$('#dialog').css('width', '600px');

			var type = $("#type option:selected").data('type');

			$('.type').addClass('hidden');
			$('.' + type).removeClass('hidden');

		});

		$('#type').on("change", function () {

			var type = $("#type option:selected").data('type');

			$('.type').addClass('hidden');
			$('.' + type).removeClass('hidden');

		});
	</script>
	<?php

	exit();
}

//настройки подключения к сервису
if ( $action == 'getLists' ) {

	$type            = $_REQUEST['type'];
	$param['apikey'] = $_REQUEST['apikey'];
	$param['userid'] = $_REQUEST['userid'];
	$param['fpath']  = $fpath;

	$lists = getLists( $type, $param );

	//print_r($lists);

	$list = json_encode_cyr( $lists );

	$settings = json_encode_cyr( [
		"type"  => $type,
		"param" => $param,
		"lists" => $lists['list']
	] );

	$f    = __DIR__.'/data/'.$fpath.'lists.json';
	$file = fopen( $f, "w" );

	if ( !$file )
		$rez = 'Не могу открыть файл';
	else {

		if ( fputs( $file, $settings ) === false ) {
			$rez = 'Ошибка записи';
		}
		else $rez = 'Нстройки записаны';

		fclose( $file );

	}

	print $list;

	exit();

}

//вывод лога
if ( $action == 'loaddata' ) {

	$list = [];

	$q = "
		SELECT
			{$sqlname}chain_log.id,
			{$sqlname}chain_log.datum,
			{$sqlname}chain_log.iduser,
			{$sqlname}chain_log.name,
			{$sqlname}chain_log.email,
			{$sqlname}chain_log.clid,
			{$sqlname}chain_log.pid,
			{$sqlname}chain_log.did,
			{$sqlname}chain_log.tip,
			{$sqlname}chain_log.content,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}dogovor.title as dogovor,
			{$sqlname}user.title as user
		FROM {$sqlname}chain_log
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}chain_log.clid
			LEFT JOIN {$sqlname}personcat ON {$sqlname}personcat.pid = {$sqlname}chain_log.pid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}chain_log.did
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}chain_log.iduser
		WHERE
			{$sqlname}chain_log.datum BETWEEN '$periodStart 00:00:00' and '$periodEnd 23:59:59' and
			{$sqlname}chain_log.identity = '$identity'
		ORDER BY {$sqlname}chain_log.datum DESC";

	$data = $db -> getAll( $q );
	foreach ( $data as $da ) {

		$list[] = [
			"datum"  => get_sfdate( $da['datum'] ),
			"name"   => $da['name'],
			"email"  => $da['email'],
			"clid"   => (int)$da['clid'],
			"client" => $da['client'],
			"pid"    => (int)$da['pid'],
			"person" => $da['person'],
			"did"    => (int)$da['did'],
			"deal"   => $da['dogovor'],
			"iduser" => (int)$da['iduser'],
			"user"   => $da['user'],
			"tip"    => strtr( $da['tip'], $etype ),
			"subtip" => $da['content'],
		];

	}

	$data = ["list" => $list];

	print $result = json_encode_cyr( $data );

	exit();
}
if ( $action == "" ) {

	//выборка типов отношений
	$listRel  = '<optgroup label="Клиент"></optgroup>';
	$countRel = 0;

	$result = $db -> getAll( "SELECT * FROM {$sqlname}relations where identity = '$identity' ORDER by title" );
	foreach ( $result as $da ) {

		$listRel .= '<option value="client:'.$da['title'].'">Клиент: '.$da['title'].'</option>';
		$countRel++;

	}

	$listRel .= '<optgroup label="Контакт"></optgroup>';

	$result = $db -> getAll( "SELECT * FROM {$sqlname}loyal_cat where identity = '$identity' ORDER by title" );
	foreach ( $result as $da ) {

		$listRel .= '<option value="person:'.$da['title'].'">Контакт: '.$da['title'].'</option>';
		$countRel++;

	}

	$listRel .= '<optgroup label="Клиент или Контакт"></optgroup>';

	$result = $db -> getAll( "SELECT * FROM {$sqlname}category where tip = 'client' and identity = '$identity' ORDER by title" );
	foreach ( $result as $da ) {

		$listRel .= '<option value="clientcategory:'.$da['idcategory'].'">Отрасль: '.$da['title'].'</option>';
		$countRel++;

	}

	$listRel .= '<optgroup label="Поставщик или Партнер"></optgroup>';

	$result = $db -> getAll( "SELECT * FROM {$sqlname}category where tip IN ('partner', 'contractor') and identity = '$identity' ORDER by title" );
	foreach ( $result as $da ) {

		$listRel .= '<option value="providercategory:'.$da['idcategory'].'">Отрасль: '.$da['title'].'</option>';
		$countRel++;

	}

	//макс.количество строк умножаем на 2 с учетом событий по сделкам
	$countRel = $countRel * 2;

	//выбор списков
	$option = '';
	foreach ($lists as $list) {

		$option .= '<option value="'.$list['id'].'">'.$list['name'].'</option>';

	}

	$listSelect = '<span class="select wp99"><select name="listID[]" id="listID[]" class="wp95">'.$option.'</select></span>';

	//выборка типов аткивности
	$listTips = '';

	$result = $db -> getAll( "SELECT * FROM {$sqlname}activities where filter IN ('task','all') and identity = '$identity' ORDER by title" );
	foreach ( $result as $da ) {

		$listTips .= '<option value="'.$da['title'].'">'.$da['title'].'</option>';

	}
	?>
	<!DOCTYPE html>
	<html lang="ru-RU">
	<head>
		<meta charset="utf-8">
		<title>Листы рассылок</title>
		<link rel="stylesheet" href="/assets/css/app.css">
		<link rel="stylesheet" href="/assets/css/app.card.css">
		<link rel="stylesheet" href="/assets/css/fontello.css">
		<link rel="stylesheet" href="plugins/tablesorter/theme.default.css">
		<link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
		<link rel="stylesheet" href="plugins/periodpicker/jquery.periodpicker.min.css">
		<link rel="stylesheet" href="plugins/autocomplete/jquery.autocomplete.css">
		<link rel="stylesheet" href="css/app.css">

		<!--красивые алерты-->
		<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
		<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

		<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
		<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
		<script src="/assets/js/moment.js/moment.min.js"></script>
		<script src="js/app.js"></script>

		<script src="plugins/tablesorter/jquery.tablesorter.js"></script>
		<script src="plugins/tablesorter/jquery.tablesorter.widgets.js"></script>
		<script src="plugins/tablesorter/widgets/widget-cssStickyHeaders.min.js"></script>

		<script src="plugins/daterangepicker/jquery.daterangepicker.js"></script>
		<script src="plugins/periodpicker/jquery.periodpicker.full.min.js"></script>

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

	<div class="fixx">
		<DIV id="head">
			<DIV id="ctitle">
				<b>Листы рассылок</b>
				<DIV id="close" onClick="window.close();">Закрыть</DIV>
			</DIV>
		</DIV>
		<DIV id="dtabs">
			<UL>
				<LI class="ytab" id="tb0" data-id="0"><A href="#0">Лог работы</A></LI>
				<LI class="ytab current" id="tb2" data-id="2"><A href="#2">Обработчики</A></LI>
				<LI class="ytab"><A href="javascript:void(0)" onclick="setSettings()">Подключение</A></LI>
				<LI class="ytab" id="tb1" data-id="1" style="float:right" onclick="checkWebhook()"><A href="#1">Справка</A></LI>
				<LI class="ytab"><A href="javascript:void(0)" onclick="setAccess()">Доступы</A></LI>
			</UL>
		</DIV>
	</div>

	<DIV class="fixbg"></DIV>

	<DIV id="telo">

		<?php
		if ( !is_writable( 'data' ) ) {
			print '
			<div class="warning margbot10">
				<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения настроек доступа"<b>data</b>".</p>
			</div>';
		}
		?>

		<!--Лог-->
		<div id="tab-0" class="tabbody hidden">

			<fieldset class="pad10 notoverflow">

				<legend>Статистика за период</legend>

				<div class="infodiv margbot10">
					<div class="inline pull-aright1">
						Период отправки:
						<div class="inline period">
							<i class="icon-calendar-1"></i>
							<input id="periodStart" name="periodStart" type="text" value="<?= $periodStart ?>">
							&divide;
							<input id="periodEnd" name="periodEnd" type="text" value="<?= $periodEnd ?>">
						</div>
					</div>
					<span id="greenbutton" class="noprint div-center">
					<a href="javascript:void(0)" onclick="loadData()" class="marg0 button">Показать</a>&nbsp;
				</span>
				</div>

				<div class="wrapper">

					<table class="bgwhite tablesorter" id="dataTable">
						<thead>
						<tr>
							<th class="w20 { filter: false }">№</th>
							<th class="w90">Дата</th>
							<th class="w90">Событие</th>
							<th>Клиент</th>
							<th class="w200">Имя</th>
							<th class="w150">Email</th>
							<th class="w150">Сотрудник</th>
						</tr>
						</thead>
						<tbody></tbody>
					</table>

				</div>

			</fieldset>

		</div>
		<!--//Лог-->

		<!--Справка-->
		<div id="tab-1" class="tabbody hidden">

			<fieldset class="pad10" style="overflow: auto; height: 450px">

				<legend>Справка по плагину</legend>

				<div class="margbot10 pad10">

				<pre id="copyright">
##################################################
#                                                #
#   Плагин разработан для SalesMan CRM v.2016    #
#   Разработчик: Владислав Андреев               #
#   Контакты:                                    #
#     - Сайт:  http://isaler.ru                  #
#     - Email: vladislav@isaler.ru               #
#     - Скайп: andreev.v.g                       #
#                                                #
##################################################
				</pre>

					<hr>

					<div class="margbot10 text fs-11">

						<h2>Проверка Webhook</h2>

						<div class="mt10 mb10" id="webhook">

						</div>

					</div>

					<hr>

					<div class="margbot10 text">

						<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">
							<?php
							$Parsedown = new Parsedown();
							print $help = $Parsedown -> text( file_get_contents( "readme.md" ) );
							?>
						</div>

					</div>

				</div>

			</fieldset>

		</div>
		<!--//Справка-->

		<!--Настройка событий-->
		<div id="tab-2" class="tabbody">

			<fieldset class="pad10 notoverflow1" style="height: 450px">

				<legend>Настройка</legend>

				<div class="infodiv margbot10" style="display: table; width: 100%; box-sizing: border-box">
				<span id="greenbutton" class="noprint pull-left">
					<a href="javascript:void(0)" onclick="addworker()" class="marg0 button"><i class="icon-plus-circled"></i>&nbsp;&nbsp;Добавить</a>&nbsp;
				</span>
					<span class="noprint pull-left">
					<a href="javascript:void(0)" onclick="save()" class="button"><i class="icon-ok-circled"></i>Сохранить</a>
				</span>
				</div>

				<div class="wrapper2">

					<form action="index.php" method="post" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
						<input type="hidden" id="action" name="action" value="save">

						<table class="bborder bgwhite tablesorter" id="pathTable">
							<thead>
							<tr>
								<th>Признак</th>
								<th class="wp20">Событие</th>
								<th class="wp15">Список рассылки</th>
								<th class="wp10">Период, дней</th>
								<th class="wp10">Тип напоминания</th>
								<th class="wp20">Тема напоминания</th>
								<th class="w5"></th>
							</tr>
							</thead>
							<tbody>
							<?php
							foreach ($config as $row) {

								//print_r($row);
								?>
								<tr class="th40 ha">
									<td>
										<?php //print $row['relation']; ?>
										<span class="select wp99">
										<select name="relation[]" id="relation[]" class="wp95">
											<optgroup label="Клиент"></optgroup>
											<?php
											$result = $db -> getAll( "SELECT * FROM {$sqlname}relations where identity = '$identity' ORDER by title" );
											foreach ( $result as $da ) {
												$s = ( "client:".$da['title'] == $row['relation'] ) ? 'selected="selected"' : '';
												?>
												<option <?= $s ?> value="client:<?= $da['title'] ?>">Клиент: <?= $da['title'] ?></option>
											<?php } ?>
											<optgroup label="Контакт"></optgroup>
											<?php
											$result = $db -> getAll( "SELECT * FROM {$sqlname}loyal_cat where identity = '$identity' ORDER by title" );
											foreach ( $result as $da ) {
												$s = ( "person:".$da['title'] == $row['relation'] ) ? 'selected="selected"' : '';
												?>
												<option <?= $s ?> value="person:<?= $da['title'] ?>">Контакт: <?= $da['title'] ?></option>
											<?php } ?>
											<optgroup label="Отрасль: Клиент или Контакт"></optgroup>
											<?php
											$result = $db -> getAll( "SELECT * FROM {$sqlname}category where tip = 'client' and identity = '$identity' ORDER by title" );
											foreach ( $result as $da ) {
												$s = ( "clientcategory:".$da['idcategory'] == $row['relation'] ) ? 'selected="selected"' : '';
												?>
												<option <?= $s ?> value="clientcategory:<?= $da['idcategory'] ?>">Отрасль: <?= $da['title'] ?></option>
											<?php } ?>
											<optgroup label="Отрасль: Поставщик или Партнер"></optgroup>
											<?php
											$result = $db -> getAll( "SELECT * FROM {$sqlname}category where tip IN ('partner', 'contractor') and identity = '$identity' ORDER by title" );
											foreach ( $result as $da ) {
												$s = ( "providercategory:".$da['idcategory'] == $row['relation'] ) ? 'selected="selected"' : '';
												?>
												<option <?= $s ?> value="providercategory:<?= $da['idcategory'] ?>">Отрасль: <?= $da['title'] ?></option>
											<?php } ?>
										</select>
										</span>
									</td>
									<td>
										<span class="select wp99">
										<select name="event[]" id="event[]" class="wp95">
											<option <?php if ( $row['event'] == "client.add" ) print 'selected="selected"'; ?> value="client.add">Новая запись - Клиент, Поставщик, Партнер</option>
											<option <?php if ( $row['event'] == "person.add" ) print 'selected="selected"'; ?> value="person.add">Новая запись - Контакт</option>
											<option <?php if ( $row['event'] == "deal.add" ) print 'selected="selected"'; ?> value="deal.add">Новая запись - Сделка</option>
											<option <?php if ( $row['event'] == "deal.close.plus" ) print 'selected="selected"'; ?> value="deal.close.plus">Закрытая сделка. Выигрыш</option>
											<option <?php if ( $row['event'] == "deal.close.minus" ) print 'selected="selected"'; ?> value="deal.close.minus">Закрытая сделка. Проигрыш</option>
										</select>
										</span>
									</td>
									<td>
										<span class="select wp99">
										<select name="listID[]" id="listID[]" class="wp95">
											<?php
											foreach ($lists as $list) {
												$s = ( $list['id'] == $row['listID'] ) ? 'selected="selected"' : '';
												print '<option value="'.$list['id'].'" '.$s.'>'.$list['name'].'</option>';
											}
											?>
										</select>
										</span>
									</td>
									<td class="text-center">
										<input type="number" name="period[]" id="period[]" value="<?= $row['period'] ?>" class="wp60" placeholder="Дней">
									</td>
									<td>
										<span class="select wp99">
										<select name="tip[]" id="tip[]" class="wp95">
											<option value="">--выбор--</option>
											<?php
											$result = $db -> getAll( "SELECT * FROM {$sqlname}activities where filter IN ('task','all') and identity = '$identity' ORDER by title" );
											foreach ( $result as $da ) {
												$s = ( $da['title'] == $row['tip'] ) ? 'selected="selected"' : '';
												?>
												<option <?= $s ?> value="<?= $da['title'] ?>"><?= $da['title'] ?></option>
											<?php } ?>
										</select>
										</span>
									</td>
									<td>
										<input type="text" name="theme[]" id="theme[]" value="<?= $row['theme'] ?>" class="wp90" placeholder="Тема для напоминания">
									</td>
									<td class="text-center">
										<span class="pt2"><i class="icon-cancel-circled red delete hand"></i></span>
									</td>
								</tr>
								<?php
							}
							?>
							</tbody>
						</table>

					</form>

				</div>

			</fieldset>

		</div>
		<!--//Настройка событий-->

	</DIV>

	<hr>

	<div class="h40 gray center-text">Сделано для SalesMan CRM</div>

	<script>
		var $lists = '<?=$spisok?>';
		var $listSelect = '<?=$listSelect?>';

		function addworker() {

			var currentCount = $('#pathTable tr').length;
			//var maxCount = JSON.parse($lists).length;
			var maxCount = <?=$countRel?>;

			//console.log( currentCount );
			//console.log( maxCount );
			//console.log( JSON.parse($lists) );

			if (currentCount <= maxCount) {

				var str = '<tr class="th40 ha"><td><span class="select"><select name="relation[]" id="relation[]" class="wp95"><option value="">--выбор--</option><?=$listRel?></select></span></td><td><span class="select wp99"><select name="event[]" id="event[]" class="wp95"><option value="client.add">Новая запись - Клиент, Поставщик, Партнер</option><option value="person.add">Новая запись - Контакт</option><option value="deal.add">Новая запись - Сделка</option><option value="deal.close.plus">Закрытая сделка. Выигрыш</option><option value="deal.close.minus">Закрытая сделка. Проигрыш</option></select></span></td><td><?=$listSelect?></td><td align="center"><input type="number" name="period[]" id="period[]" value="10" class="wp60"></td><td><span class="select"><select name="tip[]" id="tip[]" class="wp95"><option value="">--выбор--</option><?=$listTips?></select></span></td><td><input type="text" name="theme[]" id="theme[]" value="Контакт после рассылки" class="wp90" placeholder="Тема для напоминания"></td><td align="center"><span class="pt2"><i class="icon-cancel-circled red delete hand"></i></span></td><td align="center"><span class="pt2"></tr>';

				$('#pathTable tbody').append(str);

				var resort = true;
				$("#pathTable").trigger("update", [resort]);

			}
			else alert('Возможно только ' + maxCount + ' записей.');

		}
	</script>
	</body>
	</html>
<?php } ?>