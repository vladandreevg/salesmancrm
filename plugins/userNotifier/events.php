<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 *  Обработчик для событий
 */
error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

// не дожидаясь обработки отправим ответ
echo "userNotifier. Ok";

ob_flush();
flush();

$rootpath = dirname( __DIR__, 2 );
$ypath    = $rootpath."/plugins/userNotifier/";

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";

if ( !file_exists( $ypath."/data/error.log" ) ) {

	$file = fopen( $ypath."/data/error.log", 'wb' );
	fclose( $file );

}
ini_set( 'log_errors', 'On' );
ini_set( 'error_log', $ypath."/data/error.log" );

$indata   = (array)json_decode( (string)file_get_contents( 'php://input' ), true );
$_REQUEST = array_merge( $_REQUEST, $indata );

//print file_get_contents( 'php://input' );
//print "wow";
//print_r($_REQUEST);

$param = $_REQUEST;

// основные параметры аккаунта (на их основе формируется settings.php)
$identity = $_REQUEST['identity'];
$iduser1  = $_REQUEST['iduser1'];

//тип события, которое ловим и на которое реагируем
$event = $_REQUEST['event'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/vendor/core.php";
require_once $rootpath."/vendor/viber-bot-api/Viber.php";
require_once $ypath."/vendor/Manager.php";

//загружаем шаблонизатор
Mustache_Autoloader ::register();


/**
 * События, которые можем обработать плагином
 */
$events = [
	"client.import",
	"client.expressadd",
	"client.add",
	"client.edit",
	"client.delete",
	"client.change.recv",
	"client.change.user",
	"person.add",
	"person.edit",
	"person.delete",
	"person.change.user",
	"deal.import",
	"deal.add",
	"deal.edit",
	"deal.delete",
	"deal.change.user",
	"deal.change.step",
	"deal.close",
	"invoice.add",
	"invoice.edit",
	"invoice.doit",
	"invoice.expressadd",
	"task.add",
	"task.edit",
	"task.doit",
	"history.add",
	"history.edit",
	"lead.add",
	"lead.setuser",
	"lead.do",
	"entry.add",
	"entry.status",
];

$timezone = $db -> getOne( "SELECT timezone FROM {$sqlname}settings WHERE id = '$identity'" );
date_default_timezone_set( $timezone );

$tz         = new DateTimeZone( $timezone );
$dz         = new DateTime();
$dzz        = $tz -> getOffset( $dz );
$bdtimezone = $dzz / 3600;
$db -> query( "SET time_zone = '+".$bdtimezone.":00'" );

$settings = json_decode( file_get_contents( $ypath.'data/'.$fpath.'settings.json' ), true );

$proxy         = $settings['proxy'];
$proxy["type"] = CURLPROXY_SOCKS5;

//проверяем активность плагина
$pluginActive = $db -> getOne( "SELECT active FROM {$sqlname}plugins WHERE name = 'userNotifier' and identity = '$identity'" );

//проверяем активность пользователя
$usersActive = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE secrty = 'yes' and identity = '$identity'" );

//если плагин не активен, то выходим
if ( $pluginActive == 'off' ) {
	goto act;
}

//print_r($events);

foreach ( $events as $ev ) {

	if ( $ev == $event ) {

		//получаем шаблон
		$template = $db -> getOne( "SELECT content FROM {$sqlname}usernotifier_tpl WHERE event = '$event' and identity = '$identity'" );

		//если шаблон пуст, то выходим
		if ( $template == '' ) {
			goto act;
		}

		$tags['template'] = $template;

		//получаем данные для шаблонов
		//в зависимости от события
		$t    = new CreateTemplateTag();
		$tags = $t -> Tags( $event, $param );

		//print_r($tags);

		/*$f = fopen( $ypath."data/event.log", 'ab' );
		fwrite( $f, current_datumtime()." :: ".json_encode_cyr( $tags ) );
		fwrite( $f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n" );
		fclose( $f );*/

		//устанавливаем получаетелей
		$operators = [];

		//$operators[] = $iduser1;

		//ответственный
		if ( (int)$tags['iduser'] > 0 ) {
			$operators[] = $tags['iduser'];
		}

		//предыдущий ответственный (при изменении ответственного)
		if ( (int)$tags['iduserold'] > 0 ) {
			$operators[] = $tags['iduserold'];
		}

		//автор события
		if ( (int)$tags['idautor'] > 0 ) {
			$operators[] = $tags['idautor'];
		}

		//автор события
		/*if ($tags['autor'] > 0) {
			$operators[] = $tags['autor'];
		}*/

		if ( (int)$tags['idboss'] > 0 ) {
			$operators[] = $tags['idboss'];
		}

		//подписанные на изменения по сделке сотрудники
		if ( !empty( $tags['users'] ) ) {
			//$operators = array_merge( $operators, $tags['users'] );
			foreach ( $tags['users'] as $u ) {
				$operators[] = $u;
			}
		}

		$xoperators = $operators;

		$operators = array_values( array_unique( $operators, SORT_NUMERIC ) );

		//print_r($operators);

		//если подписчиков нет, то выходим
		if ( empty( $operators ) ) {
			goto act;
		}

		$chats = [];

		//проходим пользователей и подготавливаем массив "тип чата" -> "канал"
		$q = "
			SELECT 
				{$sqlname}usernotifier_users.iduser,
				{$sqlname}usernotifier_users.username,
				{$sqlname}usernotifier_users.userid,
				{$sqlname}usernotifier_users.chatid,
				{$sqlname}usernotifier_users.botid,
				{$sqlname}usernotifier_bots.tip as tip,
				{$sqlname}usernotifier_bots.botid as bot,
				{$sqlname}usernotifier_bots.token as token
			FROM {$sqlname}usernotifier_users 
				LEFT JOIN {$sqlname}usernotifier_bots ON {$sqlname}usernotifier_users.botid = {$sqlname}usernotifier_bots.id
			WHERE 
				{$sqlname}usernotifier_users.iduser IN (".yimplode( ",", $operators ).") and 
				{$sqlname}usernotifier_users.iduser IN (".yimplode( ",", $usersActive ).") and 
				{$sqlname}usernotifier_users.active = '1' and
				{$sqlname}usernotifier_users.identity = '$identity'
			";
		$r = $db -> query( $q );
		while ($da = $db -> fetch( $r )) {

			$secrty = $db -> getOne( "SELECT secrty FROM {$sqlname}user WHERE iduser = '$da[iduser]' AND identity = '$identity'" );

			/**
			 * Отправляем только активным сотрудникам из списка, кроме автора события
			 */
			if ( $secrty == "yes" && $da['iduser'] != $iduser1 ) {
				$chats[ $da['tip'] ][ $da['iduser'] ] = [
					"chatid"   => $da['chatid'],
					"username" => $da['username'],
					"userid"   => $da['userid'],
					"token"    => $da['token'],
					"botid"    => $da['botid'],
					"bot"      => $da['bot'],
					"iduser"   => (int)$da['iduser']
				];
			}

		}


		file_put_contents( $ypath."data/event.log", current_datumtime()." :: ".json_encode_cyr( [
				"chats"      => $chats,
				"xoperators" => $xoperators,
				"operators"  => $operators,
				"message"    => $message,
				"tags"       => $tags,
				"query"      => $q
			] )."\n========\n" );


		//print_r($chats);

		//проходим все типы чатов и отправляем в них сообщения
		foreach ( $chats as $tip => $user ) {

			switch ($tip) {

				case 'telegram':

					//рендерим шаблон
					//и получаем сообщение
					$m       = new Mustache_Engine();
					$message = $m -> render( $template, $tags );

					foreach ( $user as $iduser => $chat ) {

						$telegram = new Telegram( $chat['token'], true, $proxy );

						$rezultt = $telegram -> sendMessage( [
							"chat_id"    => $chat['chatid'],
							"text"       => $message,
							"parse_mode" => "HTML"
						] );

						//print_r($rezultt);

						/*$f = fopen($ypath . "data/event.log", "a");
						fwrite($f, current_datumtime() . " :: " . json_encode_cyr($rezult));
						fwrite($f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
						fclose($f);*/

						try {

							$db -> query( "INSERT INTO {$sqlname}usernotifier_log SET ?u", [
								"uid"      => $chat['chatid'],
								"event"    => $event,
								"content"  => $message,
								"get"      => json_encode_cyr( $rezultt ),
								"clid"     => (int)$tags['clid'],
								"pid"      => (int)$tags['pid'],
								"did"      => (int)$param['did'],
								"iduser"   => (int)$param['iduser1'],
								"identity" => $identity
							] );

						}
						catch ( Exception $e ) {

							$f = fopen( $ypath."data/xerror.log", 'ab' );
							fwrite( $f, current_datumtime()." :: Ошибка\n" );
							fwrite( $f, $e -> getMessage() );
							fwrite( $f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n" );
							fclose( $f );

						}

					}

				break;
				case 'slack':

					$tags2 = $tags;

					$replace = [
						"<"    => "&lt;",
						">"    => "&gt;",
						"<b>"  => "*",
						"</b>" => "*"
					];

					$client = URL2slack( $tags['client'] );
					$person = URL2slack( $tags['person'] );
					$deal   = URL2slack( $tags['deal'] );

					//$tags['client'] = "<".$client['url']."|".$client['text'].">";
					//$tags['person'] = "<".$person['url']."|".$person['text'].">";
					//$tags['deal']   = "<".$deal['url']."|".$deal['text'].">";

					if ( $client['url'] != '' ) {
						$tags2['client'] = $client['text'];
					}//." <".$client['url'].">";

					if ( $person['url'] != '' ) {
						$tags2['person'] = $person['text'];
					}//."* <".$person['url'].">";

					if ( $deal['url'] != '' ) {
						$tags2['deal'] = $deal['text'];
					}//  ."* <".$deal['url'].">";

					//рендерим шаблон
					//и получаем сообщение
					$m       = new Mustache_Engine();
					$message = strtr( $m -> render( str_replace( "{{{link}}}", "", $template ), $tags2 ), $replace );

					foreach ( $user as $iduser => $chat ) {

						$params = [
							"token"       => $chat['token'],
							"channel"     => "@".$chat['username'],
							"username"    => $chat['userid'],
							"text"        => "Уведомление из CRM",
							"attachments" => '[{"color": "'.$tags2['color'].'","title_link":"'.$tags2['url'].'","title":"'.$tags['theme'].'", "text":"'.$message.'", "author_name": "'.$tags2['autor'].'", "mrkdwn": true, "footer": "SalesMan CRM", "footer_icon": "'.$productInfo['site'].'/docs.img/salesman-48x.png"}]',
							"as_user"     => true,
							"parse"       => "full"
						];

						$rezult = outSender( "https://slack.com/api/chat.postMessage", $params );

						/*$f = fopen($ypath . "data/event.log", "a");
						fwrite($f, current_datumtime() . " :: " . json_encode_cyr($rezult)."\n");
						fwrite($f, current_datumtime() . " :: " . json_encode_cyr($params)."\n");
						fwrite($f, "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
						fclose($f);*/

						try {

							$db -> query( "INSERT INTO {$sqlname}usernotifier_log SET ?u", [
								"uid"      => $chat['username'],
								"event"    => $event,
								"content"  => $message,
								"get"      => $rezult,
								"clid"     => $tags2['clid'] + 0,
								"pid"      => $tags2['pid'] + 0,
								"did"      => $param['did'] + 0,
								"iduser"   => $param['iduser1'] + 0,
								"identity" => $identity
							] );

						}
						catch ( Exception $e ) {

							$f = fopen( $ypath."data/event.log", "a" );
							fwrite( $f, current_datumtime()." :: Ошибка\n" );
							fwrite( $f, $e -> getMessage() );
							fwrite( $f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n" );
							fclose( $f );

						}

					}

				break;
				case 'viber':

					$tags2 = $tags;

					$replace = [
						//"<"    => "&lt;",
						//">"    => "&gt;",
						"<b>"  => "",
						"</b>" => "",
						"\r\n" => "\n"
					];

					$client = URL2slack( $tags['client'] );
					$person = URL2slack( $tags['person'] );
					$deal   = URL2slack( $tags['deal'] );

					//$tags['client'] = "<".$client['url']."|".$client['text'].">";
					//$tags['person'] = "<".$person['url']."|".$person['text'].">";
					//$tags['deal']   = "<".$deal['url']."|".$deal['text'].">";

					if ( $client['url'] != '' ) {
						$tags2['client'] = $client['text'];
					}//." <".$client['url'].">";

					if ( $person['url'] != '' ) {
						$tags2['person'] = $person['text'];
					}//."* <".$person['url'].">";

					if ( $deal['url'] != '' ) {
						$tags2['deal'] = $deal['text'];
					}//  ."* <".$deal['url'].">";

					//рендерим шаблон
					//и получаем сообщение
					$m       = new Mustache_Engine();
					$message = strtr( $m -> render( str_replace( "{{{link}}}", "$tags2[url]", $template ), $tags2 ), $replace );

					foreach ( $user as $iduser => $chat ) {

						$bot = Manager ::BotInfo( $chat['bot'] );

						$sender = [
							"name" => $bot['name'],
							//"avatar" => $scheme.$_SERVER["HTTP_HOST"]."/plugins/userNotifier/images/avatar.jpg"
						];

						//отправим сообщение
						$viber = new Viber( $bot['token'] );
						$viber -> sendMessage( "text", $message, $chat['userid'], $sender );
						$rezult = $viber -> answer;

						//отправим ссылку
						//$viber = new \Viber($bot['token']);
						//$viber -> sendMessage("url", '', $chat['userid'], $sender, array("media" => $tags2['url']));
						//$rezult .= $viber ->answer;


						/*$f = fopen( $ypath."data/event.log", 'ab' );
						fwrite( $f, current_datumtime()." :: ".json_encode_cyr( $rezult )."\n" );
						fwrite( $f, current_datumtime()." :: ".json_encode_cyr( $params )."\n" );
						fwrite( $f, "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n" );
						fclose( $f );*/


						try {

							$db -> query( "INSERT INTO {$sqlname}usernotifier_log SET ?u", [
								"uid"      => $chat['username'],
								"event"    => $event,
								"content"  => $message,
								"get"      => $rezult,
								"clid"     => (int)$tags2['clid'],
								"pid"      => (int)$tags2['pid'],
								"did"      => (int)$param['did'],
								"iduser"   => (int)$param['iduser1'],
								"identity" => $identity
							] );

						}
						catch ( Exception $e ) {

							$f = fopen( $ypath."data/xerror.log", 'ab' );
							fwrite( $f, current_datumtime()." :: Ошибка\n" );
							fwrite( $f, $e -> getMessage() );
							fwrite( $f, "\n~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n" );
							fclose( $f );

						}

					}

				break;
				case 'skype':
				case 'facebook':
				case 'vk':
				break;

			}

		}

	}

}

act: