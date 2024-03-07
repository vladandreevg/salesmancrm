<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

set_time_limit( 0 );

$rootpath = dirname( __DIR__, 2 );
$ypath    = dirname( __DIR__, 2 )."/plugins/userNotifier/";

error_reporting( E_ERROR );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/vendor/core.php";
require_once $ypath."/vendor/Manager.php";

$action = $_REQUEST['action'];

$identity = $GLOBALS['identity'];
$iduser1  = $GLOBALS['iduser1'];

$fpath = '';

if ( $isCloud ) {

	//создаем папки хранения файлов
	createDir($ypath."data/".$identity);

	$fpath = $identity.'/';

}

$scheme     = $_SERVER['HTTP_SCHEME'] ?? ((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';
$serverhost = $scheme.$_SERVER["HTTP_HOST"];

$periodStart = str_replace( "/", "-", $_REQUEST['periodStart'] );
$periodEnd   = str_replace( "/", "-", $_REQUEST['periodEnd'] );

$access = $proxy = [];
$bots   = [
	"telegram"  => "Telegram",
	"slack"     => "Slack",
	"viber"     => "Viber",
	"facebook"  => "Facebook Messenger",
	"vk"        => "VK.com Chat",
	"microsoft" => "Skype Bot",
	"watsapp"   => "Whatsapp"
];
$events = [
	"client.import"      => "Импорт клиентов",
	"client.expressadd"  => "Добавлен клиент. Экспресс-форма",
	"client.add"         => "Добавлен клиент",
	"client.edit"        => "Изменен клиент",
	"client.delete"      => "Удален клиент",
	"client.change.recv" => "Изменены реквизиты клиента",
	"client.change.user" => "Изменен ответственный клиента",
	"person.add"         => "Добавлен контакт",
	"person.edit"        => "Изменен контакт",
	"person.delete"      => "Удален контакт",
	"person.change.user" => "Изменен ответственный контакта",
	"deal.import"        => "Импорт сделок",
	"deal.add"           => "Добавлена сделка",
	"deal.edit"          => "Изменена сделка",
	"deal.delete"        => "Удалена сделка",
	"deal.change.user"   => "Изменен ответственный за сделку",
	"deal.change.step"   => "Изменен этап сделки",
	"deal.close"         => "Закрыта сделка",
	"invoice.add"        => "Добавлен счет",
	"invoice.edit"       => "Изменен счет",
	"invoice.doit"       => "Оплачен счет",
	"invoice.expressadd" => "Внесена оплата по сделке",
	"task.add"           => "Напоминание добавлено",
	"task.edit"          => "Напоминание изменено",
	"task.doit"          => "Напоминание выполнено",
	"history.add"        => "Добавлена активность",
	"history.edit"       => "Изменена активность",
	"lead.add"           => "Добавлена заявка",
	"lead.setuser"       => "Ответственный по заявке",
	"lead.do"            => "Обработана заявка",
	"entry.add"          => "Добавлено обращение",
	"entry.status"       => "Обработано обращение",
];

//загружаем настройки доступа
//$file = $ypath.'data/'.$fpath.'access.json';
$settings = json_decode( file_get_contents( $ypath.'data/'.$fpath.'settings.json' ), true );

if ( !empty( $settings['proxy']['url'] ) ) {

	$proxy         = $settings['proxy'];
	$proxy["type"] = CURLPROXY_SOCKS5;

}

$access = $settings['preusers'];

//если настройки произведены, то загружаем их
if ( empty( $settings ) && $action != 'settings.do' ) {
	$access = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' and secrty = 'yes' and identity = '$identity' ORDER BY title" );
}

/**
 * Добавим признак блокировки пользователя
 */
$field = $db -> getRow( "SHOW COLUMNS FROM {$sqlname}usernotifier_users LIKE 'active'" );
if ( $field['Field'] == '' ) {

	$db -> query( "ALTER TABLE {$sqlname}usernotifier_users ADD COLUMN `active` INT(1) NOT NULL DEFAULT '1' COMMENT '0 - блокирован, 1 - активен' AFTER `iduser`" );

}

if ( $action === 'check' ) {

	$tip     = $_REQUEST['tip'];
	$token   = $_REQUEST['token'];
	$hookurl = $_REQUEST['hookurl'];

	$result = [];

	switch ($tip) {

		case 'telegram':

			$telegram = new Telegram( $token, true, $proxy );
			$result   = $telegram -> getMe();

			//print_r($result);

		break;
		case 'slack':

			$res = json_decode( outSender( "https://slack.com/api/auth.test", ["token" => $token] ), true );


			$result['ok']                 = $res['ok'];
			$result['result']             = $res;
			$result['result']['id']       = $res['user_id'];
			$result['result']['username'] = $res['user'];

		break;
		case 'viber':

			$viber = new Viber( $token );
			$viber -> BotInfo();

			$res = json_decode( $viber -> answer, true );

			$result['ok']                 = $res['status_message'] == 'ok';
			$result['result']['id']       = $res['id'];
			$result['result']['username'] = $res['name'];

		break;

	}

	print json_encode_cyr( $result );

	exit();

}
if ( $action === 'checkwebhook' ) {

	$id = (int)$_REQUEST['id'];

	$result = [];
	$bot    = $db -> getRow( "SELECT * FROM {$sqlname}usernotifier_bots WHERE id = '$id'" );

	switch ($bot['tip']) {

		case 'telegram':

			$telegram = new Telegram( $bot['token'], true, $proxy );
			$result   = $telegram -> endpoint( 'getWebhookInfo', [] );

			$result['result']['message'] = ($result['ok']) ? "подключен Webhook" : "ошибка соединения";

		break;
		case 'slack':

			$res = json_decode( outSender( "https://slack.com/api/auth.test", ["token" => $bot['token']] ), true );


			$result['ok']                 = $res['ok'];
			$result['result']             = $res;
			$result['result']['message']  = "активен";
			$result['result']['url']      = $res['webhook'];
			$result['result']['id']       = $res['user_id'];
			$result['result']['username'] = $res['user'];

		break;
		case 'viber':

			$viber = new Viber( $bot['token'] );
			$viber -> BotInfo();

			$res = json_decode( $viber -> answer, true );

			$result['ok']                 = $res['status_message'] == 'ok';
			$result['result']             = $res;
			$result['result']['url']      = $res['webhook'];
			$result['result']['message']  = "активен";
			$result['result']['id']       = $res['id'];
			$result['result']['username'] = $res['name'];

		break;

	}

	print json_encode_cyr( $result );

	exit();

}

if ( $action === 'check.webhook' ) {

	$hook = [];

	$url  = $serverhost."/plugins/userNotifier/events";
	$url2 = "{HOME}/plugins/userNotifier/events";

	//$hook['client.expressadd'] = ($db->getOne("SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.expressadd' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event") > 0) ? '<span class="green ok Bold" data-event="deal.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.expressadd\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['client.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="client.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['client.edit'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.edit' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="client.edit">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.edit\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['client.change.user'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.change.user' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="client.change.user">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.change.user\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	//$hook['client.change.recv'] = ($db->getOne("SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.change.recv' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event") > 0) ? '<span class="green ok Bold" data-event="client.change.recv">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'client.change.recv\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['person.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'person.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="person.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'person.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['person.edit'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'person.edit' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="person.edit">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'person.edit\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.edit'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.edit' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.edit">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.edit\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.change.step'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.change.step' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.change.step">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.change.step\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.change.user'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.change.user' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.change.user">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.change.user\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['deal.close'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.close' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="deal.close">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'deal.close\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['invoice.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="invoice.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'invoice.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['invoice.edit'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.edit' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="invoice.edit">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'invoice.edit\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['invoice.doit'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.doit' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="invoice.doit">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'invoice.doit\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['invoice.expressadd'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.expressadd' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="invoice.expressadd">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'invoice.expressadd\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['lead.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'lead.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="lead.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'lead.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['lead.setuser'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'lead.setuser' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="lead.setuser">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'lead.setuser\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['lead.do'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'lead.do' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="lead.do">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'lead.do\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['entry.add'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'entry.add' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="entry.add">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'entry.add\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	$hook['entry.status'] = ($db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'entry.status' and (url = '$url' or url = '$url2') and identity = '$identity' ORDER BY event" ) > 0) ? '<span class="green ok Bold" data-event="entry.status">Подключено</span>' : '<a href="javascript:void(0)" onclick="editWebhook(\'entry.status\',\''.$url2.'\')" class="red error Bold">Подключить</a>';

	print '
	<ol>
		<!--<li>Подключение к событию "<b>client.expressadd</b>" - '.$hook['client.expressadd'].'</li>-->
		<li>Подключение к событию "<b>client.add</b>" - '.$hook['client.add'].'</li>
		<li>Подключение к событию "<b>client.edit</b>" - '.$hook['client.edit'].'</li>
		<li>Подключение к событию "<b>client.change.user</b>" - '.$hook['client.change.user'].'</li>
		<!--<li>Подключение к событию "<b>client.change.recv</b>" - '.$hook['client.change.recv'].'</li>-->
		<li>Подключение к событию "<b>person.add</b>" - '.$hook['person.add'].'</li>
		<li>Подключение к событию "<b>person.edit</b>" - '.$hook['person.edit'].'</li>
		<li>Подключение к событию "<b>deal.add</b>" - '.$hook['deal.add'].'</li>
		<li>Подключение к событию "<b>deal.edit</b>" - '.$hook['deal.edit'].'</li>
		<li>Подключение к событию "<b>deal.change.step</b>" - '.$hook['deal.change.step'].'</li>
		<li>Подключение к событию "<b>deal.change.user</b>" - '.$hook['deal.change.user'].'</li>
		<li>Подключение к событию "<b>deal.close</b>" - '.$hook['deal.close'].'</li>
		<li>Подключение к событию "<b>invoice.add</b>" - '.$hook['invoice.add'].'</li>
		<li>Подключение к событию "<b>invoice.edit</b>" - '.$hook['invoice.edit'].'</li>
		<li>Подключение к событию "<b>invoice.doit</b>" - '.$hook['invoice.doit'].'</li>
		<li>Подключение к событию "<b>invoice.expressadd</b>" - '.$hook['invoice.expressadd'].'</li>
		<li>Подключение к событию "<b>lead.add</b>" - '.$hook['lead.add'].'</li>
		<li>Подключение к событию "<b>lead.setuser</b>" - '.$hook['lead.setuser'].'</li>
		<li>Подключение к событию "<b>lead.do</b>" - '.$hook['lead.do'].'</li>
		<li>Подключение к событию "<b>entry.add</b>" - '.$hook['entry.add'].'</li>
		<li>Подключение к событию "<b>entry.status</b>" - '.$hook['entry.status'].'</li>
	</ol>
	';

	$url2 = $serverhost."/plugins/userNotifier/events";
	$url1 = "{HOME}/plugins/userNotifier/events";

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE (url = '".$url1."' or url = '".$url2."') and identity = '$identity'" );

	if ( $count < 19 ) {
		print '<div class="pad5"><a href="javascript:void(0)" onclick="addWebhook()" class="button greenbtn">Подключить все</a></div>';
	}

	exit();

}
if ( $action === 'add.webhook' ) {

	$error = [];
	$good  = 0;

	$url2             = $serverhost."/plugins/userNotifier/events";
	$data['url']      = "{HOME}/plugins/userNotifier/events";
	$data['identity'] = $identity;

	$count = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.add' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" ) + 0;
	if ( $count < 1 ) {

		$data['event'] = 'client.add';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.edit' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'client.edit';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'client.change.user' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'client.change.user';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'person.add' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'person.add';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'person.edit' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0) {

		$data['event'] = 'person.edit';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.add' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'deal.add';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.edit' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'deal.edit';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.change.step' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'deal.change.step';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.change.user' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'deal.change.user';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'deal.close' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'deal.close';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.add' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'invoice.add';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.edit' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'invoice.edit';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.doit' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'invoice.doit';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'invoice.expressadd' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'invoice.expressadd';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'lead.add' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'lead.add';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'lead.setuser' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'lead.setuser';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'lead.do' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'lead.do';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'entry.add' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'entry.add';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}webhook WHERE event = 'entry.status' and (url = '".$data['url']."' or url = '".$url2."') and identity = '$identity'" );
	if ( $count == 0 ) {

		$data['event'] = 'entry.status';
		$data['title'] = 'userNotifier';

		try {
			$db -> query( "INSERT INTO {$sqlname}webhook SET ?u", $data );
			$good++;
		}
		catch ( Exception $e ) {
			$error[] = $e -> getMessage();
		}

	}

	print json_encode_cyr( [
		"result" => $good,
		"error"  => implode( "<br>", $error )
	] );

	exit();

}

//просмотр лога
if ( $action === "log.info" ) {

	$id  = (int)$_REQUEST['id'];
	$log = [];

	if ( $id > 0 ) {
		$log = $db -> getRow( "select * from {$sqlname}usernotifier_log WHERE id = '$id'" );
	}

	?>
	<DIV class="zagolovok"><B>Просмотр отправки</B></DIV>

	<div class="row" style="overflow-y: auto; max-height: 70vh;">

		<div class="column grid-2 text-right gray2 Bold">Дата:</div>
		<div class="column grid-8"><?= get_sfdate( $log['datum'] ) ?></div>

		<div class="column grid-2 text-right gray2 Bold">Событие:</div>
		<div class="column grid-8"><?= $log['event'] ?></div>

		<hr>

		<div class="column grid-2 text-right gray2 Bold">Шаблон:</div>
		<div class="column grid-8">
			<?= nl2br( $log['content'] ) ?>
		</div>

		<hr>

		<div class="column grid-2 text-right gray2 Bold">Ответ:</div>
		<div class="column grid-8 text-wrap">
			<?= array2string( json_decode( str_replace( "\n", "<br>", $log['get'] ), true ), "<br>", "&nbsp;&nbsp;&nbsp;&nbsp;" ) ?>
		</div>

	</div>

	<hr>

	<div class="text-right">
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
	</div>

	<script>

		$('#dialog').css('width', '600px');

	</script>
	<?php

	exit();
}

//настройка шаблонов сообщений
if ( $action === 'tpl.save' ) {

	$id = (int)$_REQUEST['id'];

	$data['name']    = $_REQUEST['name'];
	$data['content'] = $_REQUEST['content'];
	$data['event']   = $_REQUEST['event'];
	$data['datum']   = current_datumtime();

	if ( $id == 0 ) {

		$data['identity'] = $identity;

		try {
			$db -> query( "INSERT INTO {$sqlname}usernotifier_tpl SET ?u", $data );
			$mes = 'Готово';
		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
	else {

		try {
			$db -> query( "UPDATE {$sqlname}UserNotifier_tpl SET ?u WHERE id = '$id'", $data );
			$mes = 'Готово';
		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	print $mes;

	exit();
}
if ( $action === 'tpl.delete' ) {

	$id = (int)$_REQUEST['id'];

	try {
		$db -> query( "DELETE FROM {$sqlname}usernotifier_tpl WHERE id = '$id'" );
		$mes = 'Готово';
	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	print $mes;

	exit();
}
if ( $action === 'tpl.get' ) {

	$id = (int)$_REQUEST['id'];

	if ( $id > 0 ) {

		$tpl = $db -> getOne( "select content from {$sqlname}usernotifier_tpl WHERE id = '$id'" );

	}

	print $tpl;

	exit();

}
if ( $action === "tpl.info" ) {

	$id  = (int)$_REQUEST['id'];
	$tpl = [];

	if ( $id > 0 ) {

		$tpl = $db -> getRow( "select * from {$sqlname}usernotifier_tpl WHERE id = '$id'" );

	}

	?>
	<DIV class="zagolovok"><B>Просмотр шаблона</B></DIV>

	<div class="row">

		<div class="column grid-2 text-right gray2 Bold">Название:</div>
		<div class="column grid-8"><?= $tpl['name'] ?></div>

		<div class="column grid-2 text-right gray2 Bold">Событие:</div>
		<div class="column grid-8"><?= $tpl['event'] ?></div>

		<hr>

		<div class="column grid-2 text-right gray2 Bold">Шаблон:</div>
		<div class="column grid-8">
			<?= nl2br( $tpl['content'] ) ?>
		</div>

	</div>

	<hr>

	<div class="text-right">
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
	</div>

	<script>

		$('#dialog').css('width', '600px');

	</script>
	<?php

	exit();
}
if ( $action === "tpl.form" ) {

	$id  = (int)$_REQUEST['id'];
	$tpl = [];

	if ( $id > 0 ) {

		$tpl = $db -> getRow( "select * from {$sqlname}usernotifier_tpl WHERE id = '$id'" );

	}

	$tplOn = $db -> getCol( "select event from {$sqlname}usernotifier_tpl WHERE identity = '$identity'" );

	$tags = getTags( $identity );

	//print_r($tpl);

	?>
	<DIV class="zagolovok"><B>Редактор шаблона</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="tpl.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="row">

			<div class="column grid-10 relative">
				<span class="fs-10 label">Название:</span>
				<input type="text" name="name" id="name" class="wp100" value="<?= $tpl['name'] ?>"/>
			</div>
			<div class="column grid-10 relative">
				<span class="fs-10 label">Событие:</span>
				<span class="select">
				<select id="event" name="event" class="wp100 required">
					<option <?php if ( $tpl['event'] == '' )
						print 'selected'; ?> value="">--Укажите событие--</option>
					<optgroup label="Клиент">
						<!--<option <?php if ( $tpl['event'] == 'client.expressadd' )
							print 'selected'; ?> <?php if ( in_array( 'client.expressadd', $tplOn ) && $tpl['event'] != 'client.expressadd' )
							print 'disabled'; ?> value="client.expressadd" data-tip="client" data-title="<?= strtr( 'client.expressadd', $events ) ?>">Клиент. Добавлен (экспресс) -> client.expressadd</option>-->
						<option <?php if ( $tpl['event'] == 'client.add' )
							print 'selected'; ?> <?php if ( in_array( 'client.add', $tplOn ) && $tpl['event'] != 'client.ad' )
							print 'disabled'; ?> value="client.add" data-tip="client" data-title="<?= strtr( 'client.add', $events ) ?>">Клиент. Добавлен -> client.add</option>
						<option <?php if ( $tpl['event'] == 'client.edit' )
							print 'selected'; ?> <?php if ( in_array( 'client.edit', $tplOn ) && $tpl['event'] != 'client.edit' )
							print 'disabled'; ?> value="client.edit" data-tip="client" data-title="<?= strtr( 'client.edit', $events ) ?>">Клиент. Изменен -> client.edit</option>
						<!--<option <?php if ( $tpl['event'] == 'client.change.recv' )
							print 'selected'; ?> <?php if ( in_array( 'client.change.recv', $tplOn ) && $tpl['event'] != 'client.change.recv' )
							print 'disabled'; ?> value="client.change.recv" data-tip="client" data-title="<?= strtr( 'client.change.recv', $events ) ?>">Клиент. Изменены реквизиты -> client.change.recv</option>-->
						<option <?php if ( $tpl['event'] == 'client.change.user' )
							print 'selected'; ?> <?php if ( in_array( 'client.change.user', $tplOn ) && $tpl['event'] != 'client.change.user' )
							print 'disabled'; ?> value="client.change.user" data-tip="client" data-title="<?= strtr( 'client.change.user', $events ) ?>">Клиент. Изменен ответственный -> client.change.user</option>
					</optgroup>
					<optgroup label="Контакт">
						<option <?php if ( $tpl['event'] == 'person.add' )
							print 'selected'; ?> <?php if ( in_array( 'person.add', $tplOn ) && $tpl['event'] != 'person.add' )
							print 'disabled'; ?> value="person.add" data-tip="person" data-title="<?= strtr( 'person.add', $events ) ?>">Контакт. Добавлен -> person.add</option>
						<option <?php if ( $tpl['event'] == 'person.edit' )
							print 'selected'; ?> <?php if ( in_array( 'person.edit', $tplOn ) && $tpl['event'] != 'person.edit' )
							print 'disabled'; ?> value="person.edit" data-tip="person" data-title="<?= strtr( 'person.edit', $events ) ?>">Контакт. Изменен -> person.edit</option>
					</optgroup>
					<optgroup label="Сделка">
						<option <?php if ( $tpl['event'] == 'deal.add' )
							print 'selected'; ?> <?php if ( in_array( 'deal.add', $tplOn ) && $tpl['event'] != 'deal.ad' )
							print 'disabled'; ?> value="deal.add" data-tip="deal" data-title="<?= strtr( 'deal.add', $events ) ?>">Сделка. Добавлена -> deal.add</option>
						<option <?php if ( $tpl['event'] == 'deal.edit' )
							print 'selected'; ?> <?php if ( in_array( 'deal.edit', $tplOn ) && $tpl['event'] != 'deal.edit' )
							print 'disabled'; ?> value="deal.edit" data-tip="deal" data-title="<?= strtr( 'deal.edit', $events ) ?>">Сделка. Изменена -> deal.edit</option>
						<option <?php if ( $tpl['event'] == 'deal.change.step' )
							print 'selected'; ?> <?php if ( in_array( 'deal.change.step', $tplOn ) && $tpl['event'] != 'deal.change.step' )
							print 'disabled'; ?> value="deal.change.step" data-tip="deal" data-title="<?= strtr( 'deal.change.step', $events ) ?>">Сделка. Изменен этап -> deal.change.step</option>
						<option <?php if ( $tpl['event'] == 'deal.change.user' )
							print 'selected'; ?> <?php if ( in_array( 'deal.change.user', $tplOn ) && $tpl['event'] != 'deal.change.user' )
							print 'disabled'; ?> value="deal.change.user" data-tip="deal" data-title="<?= strtr( 'deal.change.user', $events ) ?>">Сделка. Изменен ответственный -> deal.change.user</option>
						<option <?php if ( $tpl['event'] == 'deal.close' )
							print 'selected'; ?> <?php if ( in_array( 'deal.close', $tplOn ) && $tpl['event'] != 'deal.close' )
							print 'disabled'; ?> value="deal.close" data-tip="deal" data-title="<?= strtr( 'deal.close', $events ) ?>">Сделка. Закрыта -> deal.close</option>
						<!--<option <?php if ( $tpl['event'] == 'deal.delete' )
							print 'selected'; ?> <?php if ( in_array( 'deal.delete', $tplOn ) && $tpl['event'] != 'deal.delete' )
							print 'disabled'; ?> value="deal.delete" data-tip="deal" data-title="<?= strtr( 'deal.delete', $events ) ?>">Сделка. Удалена -> deal.delete</option>-->
						<option <?php if ( $tpl['event'] == 'invoice.add' )
							print 'selected'; ?> <?php if ( in_array( 'invoice.add', $tplOn ) && $tpl['event'] != 'invoice.add' )
							print 'disabled'; ?> value="invoice.add" data-tip="deal" data-title="<?= strtr( 'invoice.add', $events ) ?>">Сделка. Выставлен счет -> invoice.add</option>
						<option <?php if ( $tpl['event'] == 'invoice.edit' )
							print 'selected'; ?> <?php if ( in_array( 'invoice.edit', $tplOn ) && $tpl['event'] != 'invoice.edit' )
							print 'disabled'; ?> value="invoice.edit" data-tip="deal" data-title="<?= strtr( 'invoice.edit', $events ) ?>">Сделка. Изменен счет -> invoice.edit</option>
						<option <?php if ( $tpl['event'] == 'invoice.doit' )
							print 'selected'; ?> <?php if ( in_array( 'invoice.doit', $tplOn ) && $tpl['event'] != 'invoice.doit' )
							print 'disabled'; ?> value="invoice.doit" data-tip="deal" data-title="<?= strtr( 'invoice.doit', $events ) ?>">Сделка. Оплата по счету -> invoice.doit</option>
						<option <?php if ( $tpl['event'] == 'invoice.expressadd' )
							print 'selected'; ?> <?php if ( in_array( 'invoice.expressadd', $tplOn ) && $tpl['event'] != 'invoice.expressadd' )
							print 'disabled'; ?> value="invoice.expressadd" data-tip="deal" data-title="<?= strtr( 'invoice.expressadd', $events ) ?>">Сделка. Внесен платеж -> invoice.expressadd</option>
					</optgroup>
					<optgroup label="Обращение">
						<option <?php if ( $tpl['event'] == 'entry.add' )
							print 'selected'; ?> <?php if ( in_array( 'entry.add', $tplOn ) && $tpl['event'] != 'entry.add' )
							print 'disabled'; ?> value="entry.add" data-tip="entry" data-title="<?= strtr( 'entry.add', $events ) ?>">Обращение. Добавлено -> entry.add</option>
						<option <?php if ( $tpl['event'] == 'entry.status' )
							print 'selected'; ?> <?php if ( in_array( 'entry.status', $tplOn ) && $tpl['event'] != 'entry.status' )
							print 'disabled'; ?> value="entry.status" data-tip="entry" data-title="<?= strtr( 'entry.status', $events ) ?>">Обращение. Обработано -> entry.status</option>
					</optgroup>
					<optgroup label="Заявка">
						<option <?php if ( $tpl['event'] == 'lead.add' )
							print 'selected'; ?> <?php if ( in_array( 'lead.add', $tplOn ) && $tpl['event'] != 'lead.add' )
							print 'disabled'; ?> value="lead.add" data-tip="lead" data-title="<?= strtr( 'lead.add', $events ) ?>">Заявка. Добавлена -> lead.add</option>
						<option <?php if ( $tpl['event'] == 'lead.setuser' )
							print 'selected'; ?> <?php if ( in_array( 'lead.setuser', $tplOn ) && $tpl['event'] != 'lead.setuser' )
							print 'disabled'; ?> value="lead.setuser" data-tip="lead" data-title="<?= strtr( 'lead.setuser', $events ) ?>">Заявка. Назначен сотрудник -> lead.setuser</option>
						<option <?php if ( $tpl['event'] == 'lead.do' )
							print 'selected'; ?> <?php if ( in_array( 'lead.do', $tplOn ) && $tpl['event'] != 'lead.do' )
							print 'disabled'; ?> value="lead.do" data-tip="lead" data-title="<?= strtr( 'lead.do', $events ) ?>">Заявка. Обработана -> lead.do</option>
					</optgroup>
				</select>
				</span>
			</div>
			<div class="column grid-10 relative">

				<div class="tagsbox mt5">
					<a href="javascript:void(0)" title="Действия" class="tagsmenuToggler fs-11"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
					<div class="tagsmenu hidden">
						<ul>
							<?php
							foreach ( $tags as $event => $tag ) {

								foreach ( $tag as $item => $value ) {

									$item = ($item == 'comment' || $item == 'link') ? "{".$item."}" : $item;

									print '<li title="'.$value.'" data-event="'.$event.'" data-tag="{{'.$item.'}}">'.$event.'&nbsp;->&nbsp;<b>{{'.$item.'}}</b></li>';

								}

							}
							?>
						</ul>
					</div>
				</div>

				<textarea name="content" id="content" style="height: 300px; max-height: 300px;" class="wp100 fs-10 p10 pt20"><?= $tpl['content'] ?></textarea>

			</div>

		</div>

		<hr>

		<div align="right">
			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>

	<script>

		$('#dialog').css('width', '800px');

		$('#event').trigger('change');

		$(document).on('change', '#event', function () {

			var event = $('option:selected', this).val();
			var name = $('option:selected', this).attr('data-title');
			var el = $('.tagsmenu');

			console.log(event);

			if (event !== '') {

				$('.tagsbox').removeClass('hidden');

				el.find('li').not('[data-event="' + event + '"]').addClass('hidden');
				el.find('li[data-event="' + event + '"]').removeClass('hidden');

				$('#name').val(name);

			}
			else {

				$('.tagsbox').addClass('hidden');
				$('#name').val();

			}

		});

	</script>
	<?php

	exit();
}

//настройка доступа
if ( $action === 'settings.do' ) {

	$settings = $_REQUEST['settings'];

	$params = json_encode_cyr( $settings );

	$f    = $ypath.'data/'.$fpath.'settings.json';
	$file = fopen( $f, 'wb' );

	if ( !$file ) {
		$rez = 'Не могу открыть файл';
	}

	else {

		$rez = (fwrite( $file, $params ) === false) ? 'Ошибка записи' : 'Записано';
		fclose( $file );

	}

	print $rez;

	exit();

}
if ( $action === "settings" ) {

	?>
	<DIV class="zagolovok"><B>Настройка</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="settings.do">

		<div class="divider mb20 mt20">Настройки доступа</div>

		<div class="row" style="overflow-y: auto; max-height: 350px">
			<?php
			$da = $db -> getAll( "SELECT * FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
			foreach ( $da as $data ) {
				?>
				<label style="display: inline-block; width: 50%; box-sizing: border-box; float: left; padding-left: 20px">
					<div class="column grid-1">
						<input name="settings[preusers][]" type="checkbox" id="settings[preusers][]" value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], $access ) )
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

		<div class="divider mb20 mt20">Настройки прокси SOCKS5 (для Телеграмм)</div>

		<div class="row">

			<div class="column grid-7 relative">

				<span class="label">URL-адрес:</span>
				<input type="text" name="settings[proxy][url]" id="settings[proxy][url]" class="wp100" value="<?= $proxy['url'] ?>">

			</div>

			<div class="column grid-3 relative">

				<span class="label">Port:</span>
				<input type="text" name="settings[proxy][port]" id="settings[proxy][port]" class="wp100" value="<?= $proxy['port'] ?>">

			</div>

			<div class="column grid-10 relative">

				<span class="label">Авторизация:</span>
				<input type="text" name="settings[proxy][auth]" id="settings[proxy][auth]" class="wp100" value="<?= $proxy['auth'] ?>">
				<div class="fs-09 blue text-center">В формате "User:Password"</div>

			</div>

		</div>

		<hr>

		<div class="text-right">
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

//настройки бота
if ( $action === 'bot.save' ) {

	$id = (int)$_REQUEST['id'];

	$data['tip']     = $_REQUEST['tip'];
	$data['name']    = trim( $_REQUEST['name'] );
	$data['content'] = $_REQUEST['content'];
	$data['botid']   = trim( $_REQUEST['botid'] );
	$data['token']   = trim( $_REQUEST['token'] );
	$data['datum']   = current_datumtime();

	$bot   = new Manager();
	$mes[] = $bot -> BotSave( $id, $data );

	/**
	 * Регистрируем Webhook
	 */

	$api_key = $db -> getOne( "select api_key from {$sqlname}settings WHERE id = '$identity'" );

	switch ($data['tip']) {

		case 'telegram':

			$urlk = $serverhost.'/plugins/userNotifier/webhook/telegram.php?botname='.$data['name'].'&api_key='.$api_key;

			$telegram = new Telegram( $data['token'], true, $proxy );
			$res   = $telegram -> setWebhook( $urlk );

			$mes[] = ($res['ok'] == 1) ? "Вебхук установлен" : $res['description'];

		break;
		case 'viber':

			$urlk = $serverhost.'/plugins/userNotifier/webhook/viber.php';

			/**
			 * адрес для веб-хук сгенерирован под Mod Rewrite
			 */
			$urlk = $serverhost."/plugins/userNotifier/webhook/viber/$api_key/".$data['botid']."/";

			//require $ypath."/vendor/viber-bot-api/Viber.php";

			$viber = new Viber( $data['token'] );
			$viber -> setWebhook( $urlk );

			//print_r($viber -> info);
			//print_r($viber -> answer);

			$res = json_decode( $viber -> answer, true );

			$mes[] = ($res['status_message'] === 'ok') ? "Вебхук установлен" : $res['status_message'];

		break;

	}

	print yimplode( "\n", $mes );

	exit();

}
if ( $action === 'bot.delete' ) {

	$id = (int)$_REQUEST['id'];

	$botinfo = Manager ::BotInfo( $id );

	$bot = new Manager();
	$mes = $bot -> BotDelete( $id );

	switch ($botinfo['tip']) {

		case 'telegram':

			$telegram = new Telegram( $data['token'], true, $proxy );
			$result   = $telegram -> deleteWebhook();

			$res = json_decode( $result, true );

			$mes[] = ($res['ok'] == 1) ? "Вебхук удален" : $res['description'];

		break;
		case 'viber':

			$viber = new Viber( $data['token'] );
			$viber -> deleteWebhook();

			$res = json_decode( $viber -> answer, true );

			$mes[] = ($res['status_message'] === 'ok') ? "Вебхук удален" : $res['status_message'];

		break;

	}

	print yimplode( "\n", $mes );

	exit();
}
if ( $action === 'bot.get' ) {

	$id  = (int)$_REQUEST['id'];
	$bot = [];

	if ( $id > 0 ) {
		$bot = Manager ::BotInfo( $id );
	}

	print json_encode_cyr( $bot );

	exit();

}
if ( $action === "bot.info" ) {

	$id  = (int)$_REQUEST['id'];
	$bot = [];

	if ( $id > 0 ) {

		$bot = $db -> getRow( "select * from {$sqlname}usernotifier_bots WHERE id = '$id'" );

	}

	?>
	<DIV class="zagolovok"><B>Информация</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="bot.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="rezult pad10 row fs-12"></div>

	</form>

	<script>

		$('#dialog').css('width', '500px');
		checkConnection();

		function checkConnection() {

			$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

			var str = 'action=checkwebhook&id=' + $('#id').val();

			$.post("index.php", str, function (data) {

				var string = '';

				if (data.ok === true) {

					if (data.result.message != '') {

						string += '<div class="column grid-10"><b class="gray2 fs-09">Ответ</b></div>';
						string += '<div class="column grid-10 infodiv fs-09">' + data.result.message + '</div>';

					}

					if (data.result.url != '') {

						string += '<div class="column grid-10 mt15"><b class="gray2 fs-09">Адрес</b></div>';
						string += '<div class="column grid-10 infodiv fs-09">' + data.result.url + '</div>';

					}

					if (data.result.has_custom_certificate) {

						string += '<hr>';
						string += '<div class="column grid-10"><b class="gray2">Самоподписанный:</b> ' + data.result.has_custom_certificate + '</div>';
						string += '<div class="column grid-10"><b class="gray2">Max подключений:</b> ' + data.result.max_connections + '</div>';

					}

					if (data.result.webhook != '') {

						string += '<hr>';
						string += '<div class="column grid-10"><b class="gray2">Адрес для webhook:</b> ' + data.result.webhook + '</div>';

					}

				}
				if (data.error_code == '404') {

					string += '<div class="column grid-10"><b class="gray2">Ответ:</b> ' + data.description + '</div>';

				}
				if (data.curl_error != '' && data.curl_error != undefined) {

					string += '<div class="column grid-10"><b class="gray2">Ответ:</b> ' + data.curl_error + '</div>';

				}

				$('.rezult').html(string);

				$('#dialog').center();

			}, 'json');

		}

	</script>
	<?php

	exit();
}
if ( $action === "bot.form" ) {

	$id  = (int)$_REQUEST['id'];
	$bot = [];

	if ( $id > 0 ) {

		$bot = $db -> getRow( "select * from {$sqlname}usernotifier_bots WHERE id = '$id'" );

	}

	$botExists = $db -> getRow( "SELECT tip FROM {$sqlname}usernotifier_bots WHERE identity = '$identity'" );

	?>
	<DIV class="zagolovok"><B>Редактировать бота</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="bot.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="row">

			<div class="column grid-10 relative">
				<span class="label">Бот для:</span>
				<span class="select">
				<select id="tip" name="tip" class="wp100 required">
					<option value="">--Укажите тип бота--</option>
					<?php
					foreach ( $bots as $tip => $name ) {

						$s = ($tip == $bot['tip']) ? "selected" : "";
						$t = (!in_array( $tip, [
							"telegram",
							"slack",
							"viber"
						] )) ? "disabled" : "";

						//if($id < 1) $s = (in_array($tip, $botExists)) ? "disabled" : "";

						print '<option value="'.$tip.'" '.$s.' '.$t.'>'.$name.'</option>';

					}
					?>
				</select>
				</span>
			</div>

			<div class="column grid-10 relative">

				<span class="label">Secret Key:</span>
				<input type="text" name="token" id="token" class="wp100 required" value="<?= $bot['token'] ?>">
				<div class="fs-09 blue text-center">Укажите ключ и нажмите "Проверить"</div>

			</div>

			<div class="column grid-10 text-center">
				<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button greenbtn fs-09 ptb5lr15"><i class="icon-ok"></i>Проверить</a>
			</div>

			<div class="rezult pad10 div-center"></div>

			<div class="divider">Данные, заполняемые после проверки</div>

			<div class="column grid-10 relative">
				<span class="label">ID бота:</span>
				<input type="text" name="botid" id="botid" class="wp100" value="<?= $bot['botid'] ?>"/>
			</div>

			<div class="column grid-10 relative">
				<span class="label">Имя бота:</span>
				<input type="text" name="name" id="name" class="wp100" value="<?= $bot['name'] ?>"/>
			</div>

			<!--
			<div class="column grid-10 relative">
				<span class="label mt5">Сертификат:</span>
				<input type="file" name="sertificate" id="sertificate" class="wp100">
			</div>

			<div class="gray2 em fs-09 pl10">В случае самоподписанного сертификата</div>
			-->

		</div>

		<hr>

		<div class="text-right">
			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>

	<script>

		$('#dialog').css('width', '500px');

		function checkConnection() {

			$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

			var str = 'action=check&tip=' + $('#tip').val() + '&token=' + $('#token').val();

			$.post("index.php", str, function (data) {

				if (data.ok === true) {

					$('.rezult').html('Ответ: <b>Соединение установлено</b>');
					$('#name').val(data.result.username);
					$('#botid').val(data.result.id);

				}
				else $('.rezult').html('Ошибка: <b>' + data.error_code + data.description + '</b>');

			}, 'json');

		}

	</script>
	<?php

	exit();

}

//настройки пользователя
if ( $action === 'user.save' ) {

	$id = (int)$_REQUEST['id'];

	$data['botid']    = $_REQUEST['botid'];
	$data['iduser']   = trim( $_REQUEST['iduser'] );
	$data['userid']   = trim( $_REQUEST['userid'] );
	$data['username'] = str_replace( "@", "", trim( $_REQUEST['username'] ) );
	$data['datum']    = current_datumtime();

	$usr = new Manager();
	$mes = $usr -> UserSave( $id, $data );

	print $mes;

	exit();

}
if ( $action === 'user.delete' ) {

	$id = (int)$_REQUEST['id'];

	$usr = new Manager();
	$mes = $usr -> UserDelete( $id );

	print $mes;

	exit();

}
if ( $action === 'user.activate' ) {

	$id = (int)$_REQUEST['id'];

	$usr = new Manager();
	$mes = $usr -> UserActiveChange( $id );

	print $mes;

	exit();

}
if ( $action === 'user.get' ) {

	$id   = (int)$_REQUEST['id'];
	$user = [];

	if ( $id > 0 ) {

		$user = $db -> getRow( "select * from {$sqlname}usernotifier_users WHERE id = '$id'" );

	}

	print json_encode_cyr( $user );

	exit();

}
if ( $action === "user.form" ) {

	$id   = (int)$_REQUEST['id'];
	$user = [];

	if ( $id > 0 ) {

		$user = $db -> getRow( "select * from {$sqlname}usernotifier_users WHERE id = '$id'" );

	}

	?>
	<DIV class="zagolovok"><B>Редактировать пользователя</B></DIV>
	<form action="index.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="user.save">
		<input name="id" type="hidden" id="id" value="<?= $id ?>">

		<div class="row">

			<div class="column grid-10 relative">
				<span class="label">Бот:</span>
				<span class="select">
				<select id="botid" name="botid" class="wp100 required">
					<option value="">--Укажите бота--</option>
					<?php
					$da = $db -> getAll( "SELECT id, tip, name FROM {$sqlname}usernotifier_bots WHERE identity = '$identity'" );
					foreach ( $da as $bot ) {

						$s = ($bot['id'] == $user['botid']) ? "selected" : "";

						print '<option value="'.$bot['id'].'" '.$s.' '.($bot['tip'] == 'viber' ? 'disabled' : '').'>'.$bot['name'].' ['.strtr( $bot['tip'], $bots ).']</option>';

					}
					?>
				</select>
				</span>
			</div>

			<div class="column grid-10 relative">
				<span class="label">Сотрудник:</span>
				<span class="select">
				<select id="iduser" name="iduser" class="wp100 required">
					<option value="">--Укажите сотрудника--</option>
					<?php
					$da = $db -> getAll( "SELECT iduser, title FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity' ORDER BY title" );
					foreach ( $da as $us ) {

						$s = ($us['iduser'] == $user['iduser']) ? "selected" : "";

						print '<option value="'.$us['iduser'].'" '.$s.'>'.$us['title'].'</option>';

					}
					?>
				</select>
				</span>
			</div>

			<div class="divider">Данные аккаунта</div>

			<div class="column grid-10 relative">
				<span class="label">Username:</span>
				<input type="text" name="username" id="username" class="wp100" value="<?= $user['username'] ?>"/>
			</div>

			<div class="column grid-10 relative">
				<span class="label">UserID:</span>
				<input type="text" name="userid" id="userid" class="wp100" value="<?= $user['userid'] ?>"/>
			</div>

			<div class="infodiv wp100">
				Эти данные сотрудники могут получить у бота <b>@userinfobot</b>.
				<ul>
					<li>Username - это первая строка ответа бота без символа @</li>
					<li>UserID - это вторая строка ответа</li>
				</ul>
			</div>

		</div>

		<hr>

		<div class="text-right">
			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>

	<script>

		$('#dialog').css('width', '500px');

	</script>
	<?php

	exit();
}

//вывод лога
if ( $action === 'loaddata' ) {

	$list = [];
	$page = $_REQUEST['page'];
	$sort = '';

	if ( $periodStart != '' ) {
		$sort = $sqlname."usernotifier_log.datum BETWEEN '$periodStart 00:00:00' AND '$periodEnd 23:59:59' AND";
	}

	$q = "
		SELECT
			{$sqlname}usernotifier_log.id as id,
			{$sqlname}usernotifier_log.uid as uid,
			{$sqlname}usernotifier_log.datum as datum,
			{$sqlname}usernotifier_log.iduser as iduser,
			{$sqlname}usernotifier_log.content as content,
			{$sqlname}usernotifier_log.get as answer,
			{$sqlname}usernotifier_log.event as event,
			{$sqlname}usernotifier_log.clid as clid,
			{$sqlname}usernotifier_log.pid as pid,
			{$sqlname}usernotifier_log.did as did,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}dogovor.title as deal,
			{$sqlname}user.title as user
		FROM {$sqlname}usernotifier_log
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}usernotifier_log.clid
			LEFT JOIN {$sqlname}personcat ON {$sqlname}personcat.pid = {$sqlname}usernotifier_log.pid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}usernotifier_log.did
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}usernotifier_log.iduser
		WHERE
			-- {$sqlname}usernotifier_log.datum BETWEEN '$periodStart 00:00:00' AND '$periodEnd 23:59:59' AND
			$sort
			{$sqlname}usernotifier_log.identity = '$identity'
		ORDER BY {$sqlname}usernotifier_log.datum DESC";

	$count = $db -> getOne( "
		SELECT 
			COUNT(*) 
		FROM {$sqlname}usernotifier_log
		WHERE
			-- {$sqlname}UserNotifier_log.datum BETWEEN '$periodStart 00:00:00' AND '$periodEnd 23:59:59' AND
			$sort
			{$sqlname}usernotifier_log.identity = '$identity'
		" );

	$lines_per_page = 100;
	if ( empty( $page ) | $page <= 0 ) {
		$page = 1;
	}
	else {
		$page = (int)$page;
	}
	$page_for_query = $page - 1;
	$lpos           = $page_for_query * $lines_per_page;

	$count_pages = ceil( $count / $lines_per_page );
	if ( $count_pages < 1 )
		$count_pages = 1;

	//print "$q LIMIT $lpos,$lines_per_page";

	$data = $db -> getAll( "$q LIMIT $lpos,$lines_per_page" );
	foreach ( $data as $da ) {

		$answer = json_decode( $da['answer'], true );

		$list[] = [
			"id"      => (int)$da['id'],
			"datum"   => get_sfdate( $da['datum'] ),
			"uid"     => $da['uid'],
			"content" => $da['content'],
			"get"     => ($answer['ok'] || $answer['status_message'] == 'ok') ? 'Успешно' : $answer['curl_error'],
			"class"   => ($answer['ok'] || $answer['status_message'] == 'ok') ? '' : 'redbg-sub',
			"event"   => $da['event'],
			"clid"    => ((int)$da['clid'] > 0) ? (int)$da['clid'] : '',
			"client"  => $da['client'],
			"pid"     => ((int)$da['pid'] > 0) ? (int)$da['pid'] : '',
			"person"  => $da['person'],
			"did"     => ((int)$da['did'] > 0) ? (int)$da['did'] : '',
			"deal"    => $da['deal'],
			"user"    => $da['user'],
		];

	}

	$data = [
		"list"    => $list,
		"page"    => $page,
		"pageall" => $count_pages
	];

	print $result = json_encode_cyr( $data );

	exit();

}
if ( $action === 'loadtpl' ) {

	$tpl = [];

	$data = $db -> getAll( "SELECT * FROM {$sqlname}usernotifier_tpl WHERE identity = '$identity' ORDER BY event" );
	foreach ( $data as $da ) {

		$tpl[] = [
			"id"      => $da['id'],
			"date"    => $da['datum'],
			"name"    => $da['name'],
			"event"   => $da['event'],
			"content" => $da['content'],
		];

	}

	print json_encode_cyr( $tpl );

	exit();

}
if ( $action === 'loadbots' ) {

	$bots = [];

	$data = $db -> getAll( "SELECT * FROM {$sqlname}usernotifier_bots WHERE identity = '$identity'" );
	foreach ( $data as $da ) {

		$bots[] = [
			"id"      => (int)$da['id'],
			"botid"   => $da['botid'],
			"date"    => $da['datum'],
			"tip"     => strtr( $da['tip'], $bots ),
			"name"    => $da['name'],
			"content" => $da['content'],
		];

	}

	print json_encode_cyr( $bots );

	exit();

}
if ( $action === 'loadusers' ) {

	$users = [];

	$data = $db -> getAll( "SELECT * FROM {$sqlname}usernotifier_users WHERE identity = '$identity'" );
	foreach ( $data as $da ) {

		$bot = $db -> getRow( "select * from {$sqlname}usernotifier_bots WHERE id = '$da[botid]'" );

		$isunlock = $db -> getOne( "SELECT secrty FROM {$sqlname}user WHERE iduser = '$da[iduser]' AND identity = '$identity'" );

		$users[] = [
			"id"       => $da['id'],
			"date"     => $da['datum'],
			"botid"    => $bot['name'],
			"bottip"   => strtr( $bot['tip'], $bots ),
			"userid"   => $da['userid'],
			"chatid"   => $da['chatid'],
			"username" => $da['username'],
			"user"     => current_user( $da['iduser'] ),
			"content"  => $da['content'],
			"active"   => $da['active'] == 1,
			"isunlock" => $isunlock == "yes",
		];

	}

	print json_encode_cyr( $users );

	exit();

}

if ( !in_array( $iduser1, $access, true ) && $isadmin !== 'on' ) {

	print '
			<TITLE>Предупреждение - CRM</TITLE>
			<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
			<LINK rel="stylesheet" href="/assets/css/fontello.css">
			<div class="warning text-left" style="width:600px; margin:0 auto;">
				<span><i class="icon-attention red icon-5x pull-left"></i></span>
				<b class="red uppercase">Предупреждение:</b>
				<br><br>
				У вас нет доступа<br><br><br>
			</div>
			';
	exit();

}


?>
<!DOCTYPE html>
<html lang="ru">
<head>

	<meta charset="utf-8">
	<title>UserNotifier - Уведомление пользователей</title>
	<link rel="stylesheet" href="/assets/css/app.css">
	<link rel="stylesheet" href="/assets/css/app.card.css">
	<link rel="stylesheet" href="/assets/css/fontello.css">
	<link rel="stylesheet" href="./plugins/tablesorter/theme.default.css">
	<link rel="stylesheet" href="./plugins/daterangepicker/daterangepicker.css">
	<link rel="stylesheet" href="./plugins/periodpicker/jquery.periodpicker.min.css">
	<link rel="stylesheet" href="./plugins/autocomplete/jquery.autocomplete.css">
	<link rel="stylesheet" href="assets/css/app.css">

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog-preloader">
		<img src="/assets/images/rings.svg" border="0" width="128">
	</div>
	<div class="dialog" id="dialog" align="left">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="fixx">
	<DIV id="head">
		<DIV id="ctitle">
			<b>UserNotifier: Уведомление пользователей</b>
			<DIV id="close" onclick="window.close();">Закрыть</DIV>
		</DIV>
	</DIV>
	<DIV id="dtabs">
		<UL>
			<LI class="ytab current" id="tb0" data-id="0"><A href="#0">Отправки</A></LI>
			<LI class="ytab" id="tb2" data-id="2"><A href="#2">Шаблоны</A></LI>
			<LI class="ytab" id="tb3" data-id="3"><A href="#3">Боты, Пользователи</A></LI>
			<LI class="ytab hidden"><A href="javascript:void(0)" onclick="setSettings()">Настройка</A></LI>
			<LI class="ytab" id="tb1" data-id="1" style="float:right"><A href="#1" onclick="checkWebhook()">Справка</A>
			</LI>
			<LI class="ytab" data-id="100"><A href="javascript:void(0)" onclick="setSettings()">Настройка</A></LI>
		</UL>
	</DIV>
</div>

<DIV class="fixbg"></DIV>

<DIV id="telo">

	<?php
	if ( is_writable( 'data' ) != true ) {
		print '
	<div class="warning margbot10">
		<p><b class="red">Внимание! Ошибка</b> - отсутствуют права на запись для папки хранения настроек доступа "<b>data</b>".</p>
	</div>';
	}
	?>

	<div id="tab-0" class="tabbody">

		<fieldset class="pad10 notoverflow">

			<legend>Отправленные за период</legend>

			<input type="hidden" id="page" name="page" value="1">

			<div class="infodiv margbot10">
				<div class="inline pull-aright1">
					Период отправки:
					<div class="inline period">
						<i class="icon-calendar-1"></i>
						<input id="periodStart" name="periodStart" type="text" value="<?= $periodStart ?>" class="dateinput">
						&divide;
						<input id="periodEnd" name="periodEnd" type="text" value="<?= $periodEnd ?>" class="dateinput">
					</div>
				</div>
				<span id="greenbutton" class="noprint div-center">
					<a href="javascript:void(0)" onclick="loadData()" class="marg0 button">Показать</a>&nbsp;
				</span>
			</div>

			<div class="wrapper">

				<table class="bgwhite tablesorter title top" id="dataTable">
					<thead>
					<tr>
						<th class="{ filter: false, sort: false } w20">№</th>
						<th class="w120">Дата</th>
						<th class="w300">Клиент</th>
						<th>Сообщение</th>
						<th class="w120">Ответ</th>
						<th class="w80">Отправитель</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>

			</div>

			<div id="pagediv" class="p10 viewdiv"></div>

		</fieldset>

	</div>

	<div id="tab-1" class="tabbody hidden">

		<fieldset class="pad10" style="overflow: auto; height: 450px">

			<legend>Справка по плагину</legend>

			<div class="margbot10">

				<pre id="copyright">
##################################################
#                                                #
#  Плагин разработан для SalesMan CRM v.2018.x   #
#  Версия: 2.0                                   #
#  Разработчик: Владислав Андреев                #
#  Контакты:                                     #
#     - Сайт:  http://isaler.ru                  #
#     - Email: vladislav@isaler.ru               #
#     - Скайп: andreev.v.g                       #
#                                                #
##################################################
				</pre>

				<hr>

				<div class="mb20 text fs-12 pl20">

					<h2>Подключение Webhook в SalesMan CRM</h2>

					<div class="mt10 mb10" id="webhook"></div>

				</div>

				<div class="mb20 text fs-12 pl20">

					<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;-webkit-hyphens: auto; -moz-hyphens: auto; hyphens: auto; width: 98%; box-sizing: border-box;">
						<?php
						//include_once "../../opensource/parsedown-master/Parsedown.php";

						$api_key = $db -> getOne( "select api_key from {$sqlname}settings WHERE id = '$identity'" );
						$url     = $productInfo['crmurl'].'/plugins/userNotifier/webhook/telegram.php?botname=BOTNAME&api_key='.$api_key;
						$url2    = $productInfo['crmurl'].'/plugins/userNotifier/webhook/'.$api_key.'/BOTNAME';

						$html = str_replace( [
							"{{telegramHookUrl}}",
							"{{viberHookUrl}}"
						], [
							$url,
							$url2
						], file_get_contents( "readme.md" ) );

						$Parsedown = new Parsedown();
						print $help = $Parsedown -> text( $html );
						?>
					</div>

				</div>

			</div>

		</fieldset>

	</div>

	<div id="tab-2" class="tabbody hidden">

		<fieldset class="pad10" style="height: 450px">

			<legend>Шаблоны</legend>

			<div class="infodiv">
				<span id="orangebutton">
					<a href="javascript:void(0)" onclick="doLoad('?action=tpl.form')" class="marg0 button"><i class="icon-plus-circled"></i>Новый шаблон</a>&nbsp;
				</span>
			</div>

			<div class="margbot10">

				<div class="wrapper2">

					<table class="bborder bgwhite top" id="tplTable">
						<thead>
						<tr>
							<th class="w20">№</th>
							<th class="w120">Название</th>
							<th class="w100">Событие</th>
							<th class="">Содержание</th>
							<th class="w120">Дата добавления</th>
							<th class="w180 {sorter: 'false'}">Действие</th>
						</tr>
						</thead>
						<tbody></tbody>

					</table>

				</div>

			</div>

		</fieldset>

	</div>

	<div id="tab-3" class="tabbody hidden">

		<fieldset class="pad10 notoverflow">

			<legend>Боты и шлюзы</legend>

			<div class="viewdiv">
				<span id="orangebutton">
					<a href="javascript:void(0)" onclick="doLoad('?action=bot.form')" class="marg0 button"><i class="icon-plus-circled"></i>Добавить бота</a>&nbsp;
				</span>
			</div>

			<div class="margbot10">

				<div class="wrapper3">

					<table class="bborder bgwhite" id="botTable">
						<thead>
						<tr>
							<th width="20">№</th>
							<th width="200">ID</th>
							<th width="200">Имя бота</th>
							<th width="200">Тип бота</th>
							<th width="120">Дата обновления</th>
							<th width="180" align="center" class="{sorter: 'false'}">Действие</th>
						</tr>
						</thead>
						<tbody></tbody>

					</table>

				</div>

			</div>

		</fieldset>

		<fieldset class="pad10 notoverflow">

			<legend>Пользователи</legend>

			<div class="viewdiv">
				<span id="greenbutton">
					<a href="javascript:void(0)" onclick="doLoad('?action=user.form')" class="marg0 button"><i class="icon-plus-circled"></i>Добавить пользователя</a>&nbsp;
				</span>
			</div>

			<div class="margbot10">

				<div class="wrapper3">

					<table width="100%" border="0" cellspacing="0" cellpadding="4" class="bborder bgwhite" id="userTable" align="center">
						<thead>
						<tr>
							<th width="20">№</th>
							<th width="200">Имя/тип бота</th>
							<th width="">ID пользователя</th>
							<th width="">ID чата</th>
							<th width="200">Имя пользователя</th>
							<th width="200">Сотрудник</th>
							<th width="150">Дата обновления</th>
							<th width="180" class="{sorter: 'false'}">Действие</th>
						</tr>
						</thead>
						<tbody></tbody>

					</table>

				</div>

			</div>

		</fieldset>

	</div>

</DIV>

<hr>

<div class="gray center-text">Сделано для SalesMan CRM</div>

<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
<script src="/assets/js/jquery/jquery-ui.min.js"></script>
<script src="/assets/js/moment.js/moment.min.js"></script>

<script src="assets/js/app.js"></script>

<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>

<script src="plugins/tablesorter/jquery.tablesorter.js"></script>
<script src="plugins/tablesorter/jquery.tablesorter.widgets.js"></script>
<script src="plugins/tablesorter/widgets/widget-cssStickyHeaders.min.js"></script>

<script src="plugins/daterangepicker/jquery.daterangepicker.js"></script>
<script src="plugins/periodpicker/jquery.periodpicker.full.min.js"></script>

</body>
</html>