<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */


use Chats\Chats;
use Chats\Comet;
use Salesman\Client;
use Salesman\Guides;
use Salesman\Person;

$rootpath = dirname( __DIR__, 3 );
$ypath    = $rootpath."/plugins/socialChats";

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

require_once $ypath."/php/autoload.php";
require_once $ypath."/vendor/autoload.php";

$action = $_REQUEST['action'];

$chat = new Chats();

/**
 * Дашбоард
 */
if ( $action == 'dashboard' ) {

	$list = $chat -> getNewChatsList();

	print json_encode_cyr( ["list" => $list] );

}

/**
 * Получает количество не распределенных чатов
 */
if ( $action == 'newChatsCount' ) {

	print $chat -> getNewChatsCount();

}

if ( $action == 'newChatsList' ) {

	print count( $chat -> getNewChatsList() );

}

// todo: вывести расчеты количества отдельно
if ( $action == 'browserNotify' ) {

	$count1 = $chat -> getNewChatsCount();
	$count2 = $chat -> getNewMessagesFromChats()['count'];

	$count = $count1 + $count2;

	print $count;

	exit();

}

if ( $action == 'list' ) {

	$filters = $_REQUEST;

	$chat -> filters = $_REQUEST;
	$list            = $chat -> getChats();

	//$list2 = $chat -> getNewChatsList();
	//$list = array_merge($list2, $list);

	// новые чаты
	//$count       = $chat -> getNewChatsCount();
	$unreadChats = $chat -> getNewMessagesFromChats();

	print json_encode_cyr( [
		"list"        => $list['list'],
		"page"        => $list['page'],
		"pageall"     => $list['pageall'],
		"filters"     => $filters,
		//"NewChatsCount" => $count,
		"unreadChats" => $unreadChats['count']
	] );

}

if ( $action == 'unreadChats' ) {

	$list = $chat -> getNewMessagesFromChats();

	print json_encode_cyr( $list['list'] );

}

if ( $action == 'messages' ) {

	$chatid = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$chat -> filters = $_REQUEST;
	$list            = $chat -> getDialogs( $chatid );

	// новые чаты
	$list['NewChatsCount'] = $chat -> getNewChatsCount();

	$unreadChats         = $chat -> getNewMessagesFromChats();
	$list['unreadChats'] = $unreadChats['count'];

	print json_encode_cyr( $list );

}

if ( $action == 'operators' ) {

	$u = $chat -> getOperatorsUID();
	print json_encode_cyr( $u );

	exit();

}

if ( $action == 'send' ) {

	$params              = $_REQUEST;
	$params['iduser']    = $iduser1;
	$params['direction'] = 'out';
	$params['chat_id']   = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$response = [];
	$msg      = '';
	$errors   = [];

	// если чата нет (например впервые пишем письмо в ватсап по номеру), то создаем его
	$chatinfo = $chat -> chatInfo( 0, $params['chat_id'] );
	if ( empty( $chatinfo ) ) {

		$firstname = $lastname = '';

		if ( $params['pid'] > 0 ) {

			$p         = yexplode( " ", (string)current_person( $params['pid'] ) );
			$firstname = $p[0];
			$lastname  = $p[1];

		}
		elseif ( $params['clid'] > 0 ) {

			$firstname = current_client( $params['clid'] );

		}

		$chat -> editChat( 0, [
			"channel_id"       => $params['channel_id'],
			"chat_id"          => $params['chat_id'],
			"client_firstname" => $firstname,
			"client_lastname"  => $lastname,
			"pid"              => $params['pid'],
			"clid"             => $params['clid'],
			"phone"            => $params['phone'],
			"users"            => $params['iduser'],
			"status"           => "inwork",
			"type"             => $params['type']
		] );

	}

	// загружаем файлы перед отправкой
	$attachments = $chat -> uploadFile();

	//print_r($attachments);

	// отправляем текст
	if ( $params['text'] != '' ) {

		// сохраним сообщение
		$id = $chat -> editMessage( 0, $params );

		if ( $id > 0 ) {

			// отправим сообщение
			$response['text'] = $chat -> sendMessage( $id );

			if ( $response['text']['result'] == 'error' ) {
				$errors[] = "Отправка сообщения: ".$response['text']['message'];
			}

			//print_r( $s );

		}

		$msg = 'ok';

	}

	// отдельно отправляем вложения
	if ( !empty( $attachments ) ) {

		$params['text'] = '';

		foreach ( $attachments as $type => $attachment ) {

			foreach ( $attachment as $file ) {

				$params['attachment'] = [];

				//print_r($file);

				// сохраним сообщение
				$params['attachment'][ $type ][] = $file;
				$id                              = $chat -> editMessage( 0, $params );

				if ( $id > 0 ) {

					//print "ID = {$id}\n";

					// отправим сообщение
					$f = $chat -> sendFile( $id );

					$response['file'][] = $f;

					if ( $f['result'] == 'error' ) {
						$errors[] = "Отправка файла: ".$f['text']['message'];
					}

					//print_r($s);

				}

			}

		}

		$msg = 'ok';

	}

	if ( $params['text'] == '' && empty( $attachments ) ) {

		$msg = 'Пустое сообщение';

	}

	print json_encode_cyr( [
		"result"   => $msg,
		"response" => $response,
		"errors"   => !empty( $errors ) ? implode( "<br>", (array)$errors ) : NULL
	] );

	exit();

}

if ( $action == 'deleteMessage' ) {

	$message_id = $_REQUEST['message_id'];

	$res = $chat -> deleteMessage( $message_id );

	print json_encode_cyr( $res );

	exit();

}

if ( $action == 'closeChat' ) {

	$chatid = str_replace( " ", "+", $_REQUEST['chat_id'] );

	//$chat -> editChat( 0, ['chat_id' => $chatid, 'status' => 'archive'] );
	$chat -> setChatStatus( 0, $chatid, 'archive' );

	print "ok";

	exit();

}

if ( $action == 'chatSetUser' ) {

	$iduser = (int)$_REQUEST['iduser'];
	$chatid = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$r = $chat -> chatSetUser( $chatid, $iduser );

	print json_encode_cyr( $r );

	exit();

}

if ( $action == 'chatUpdateInfo' ) {

	$chatid = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$r = $chat -> updateUserInfo( 0, $chatid );

	print json_encode_cyr( $r );

	exit();

}

if ( $action == 'deleteChat' ) {

	$id      = (int)$_REQUEST['id'];
	$chat_id = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$mes = $chat -> deleteChat( $id, $chat_id );

	print yimplode( "\n", $mes );

	exit();

}

if ( $action == 'consumerInfo' ) {

	$chatid = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$chatinfo = $chat -> chatInfo( 0, $chatid );

	$person = $client = [];

	if ( $chatinfo['pid'] > 0 ) {

		$person = get_person_info( $chatinfo['pid'], "yes" );

		if ( $chatinfo['clid'] < 1 && $person['clid'] > 0 )
			$client = get_client_info( $person['clid'], "yes" );

	}
	if ( $chatinfo['clid'] > 0 ) {

		$client = get_client_info( $chatinfo['clid'], "yes" );

	}

	$territory = $relation = [];

	$terr = Guides ::Territory();
	foreach ( $terr as $k => $v )
		$territory[] = [
			"id"    => $k,
			"title" => $v,
			"sel"   => $k == $client['territory'] ? "true" : NULL
		];

	$rel = Guides ::Relation();
	foreach ( $rel as $k => $v )
		$relation[] = [
			"id"    => $v,
			"title" => $v,
			"sel"   => $v == $client['relation'] ? "true" : NULL
		];

	$consumer = [
		[
			"inputTitle" => "Клиент",
			"inputName"  => "client[title]",
			"inputValue" => $client['title'],
			"inputGoal"  => 'clid'
		],
		[
			"inputTitle" => "Тел.",
			"inputName"  => "client[phone]",
			"inputValue" => ($client['phone'] != '') ? $client['phone'] : $chatinfo['phone']
		],
		[
			"inputTitle" => "Email",
			"inputName"  => "client[mail_url]",
			"inputValue" => ($client['mail_url'] != '') ? $client['mail_url'] : $chatinfo['email']
		],
		[
			"inputTitle"   => "Территория",
			"inputName"    => "client[territory]",
			"inputValue"   => $client['territory'],
			"havevariants" => !empty( $territory ) ? true : NULL,
			"variants"     => !empty( $territory ) ? $territory : NULL
		],
		[
			"inputTitle"   => "Тип отношений",
			"inputName"    => "client[tip_cmr]",
			"inputValue"   => $client['relation'],
			"havevariants" => !empty( $relation ) ? true : NULL,
			"variants"     => !empty( $relation ) ? $relation : NULL
		],
		[
			"inputTitle" => "ФИО",
			"inputName"  => "person[person]",
			"inputValue" => $person['person'],
			"inputGoal"  => 'pid'
		],
		[
			"inputTitle" => "Моб.",
			"inputName"  => "person[mob]",
			"inputValue" => $person['mob'] != '' ? $person['mob'] : $chatinfo['phone']
		],
		[
			"inputTitle" => "Email",
			"inputName"  => "person[mail]",
			"inputValue" => $person['mail'] != '' ? $person['mail'] : $chatinfo['email']
		]
	];

	$pid  = $chatinfo['pid'] > 0 ? $chatinfo['pid'] : NULL;
	$clid = $chatinfo['clid'] > 0 ? $chatinfo['clid'] : NULL;

	//print_r($consumer);

	print json_encode_cyr( [
		"consumer" => $consumer,
		"pid"      => $pid,
		"clid"     => $clid
	] );

	exit();

}

if ( $action == 'consumerSave' ) {

	//print_r($_REQUEST);

	$chat_id = str_replace( " ", "+", $_REQUEST['chat_id'] );

	$r1 = $r2 = [];

	/**
	 * Добавляем/Обновляем Клиента
	 */

	$clid   = (int)$_REQUEST['clid'];
	$client = $_REQUEST['client'];

	if ( $client['title'] != '' ) {

		$client['phone'] = prepareMobPhone( $client['phone'] );

		$c = new Client();

		if ( $clid < 1 ) {

			$client['iduser'] = $iduser1;
			$r1               = $c -> add( $client );

			$clid = $r1['data'];

		}
		else {

			/**
			 * не даем перезаписать телефоны и email. добавляем новые
			 */
			if ( $client['phone'] != '' ) {

				$cclient = get_client_info( $clid, "yes" );
				$cphone  = (array)yexplode( ",", (string)$cclient['phone'] );

				foreach ( $cphone as $k => $p )
					$cphone[ $k ] = prepareMobPhone( $p );

				if ( !in_array( $client['phone'], $cphone ) ) {

					array_unshift( $cphone, $client['phone'] );
					$client['phone'] = yimplode( ",", $cphone );

				}

			}
			if ( $client['mail_url'] != '' ) {

				$cclient = get_client_info( $clid, "yes" );
				$cmail   = (array)yexplode( ",", (string)$cclient['mail_url'] );

				foreach ( $cmail as $k => $p )
					$cmail[ $k ] = untag( $p );

				if ( !in_array( $client['mail_url'], $cmail ) ) {

					array_unshift( $cmail, $client['mail_url'] );
					$client['mail_url'] = yimplode( ",", $cmail );

				}

			}

			$r1 = $c -> fullupdate( (int)$clid, $client );

		}

		$chat -> editChat( 0, [
			"chat_id" => $chat_id,
			"clid"    => (int)$clid
		] );

	}

	/**
	 * Добавляем/Обновляем Контакт
	 */

	$pid    = (int)$_REQUEST['pid'];
	$person = $_REQUEST['person'];

	if ( $person['person'] != '' ) {

		$person['clid'] = (int)$clid;

		$prsn = new Person();

		if ( $pid == 0 ) {

			$r2 = $prsn -> edit( 0, $person );

		}
		else {

			$cperson = get_person_info( $pid, "yes" );

			/**
			 * не даем перезаписать телефоны и email. добавляем новые
			 */
			if ( $person['mob'] != '' ) {

				$person['mob'] = prepareMobPhone( $person['mob'] );
				$cphone        = yexplode( ",", $cperson['mob'] );

				foreach ( $cphone as $k => $p )
					$cphone[ $k ] = prepareMobPhone( $p );

				if ( !in_array( $person['mob'], $cphone ) ) {

					array_unshift( $cphone, $person['mob'] );
					$person['mob'] = yimplode( ",", $cphone );

				}

			}
			if ( $person['mail'] != '' ) {

				$cmail = yexplode( ",", $cperson['mail'] );

				foreach ( $cmail as $k => $p )
					$cmail[ $k ] = untag( $p );

				if ( !in_array( $person['mail'], $cmail ) ) {

					array_unshift( $cmail, $person['mail'] );
					$person['mail'] = yimplode( ",", $cmail );

				}

			}

			$r2 = $prsn -> edit( $pid, $person );

		}

		if ( $pid < 1 )
			$pid = $r2['data'];

		$chat -> editChat( 0, [
			"chat_id" => $chat_id,
			"pid"     => $pid
		] );

	}

	print json_encode_cyr( [
		"pid"  => $pid,
		"clid" => $clid,
		"r1"   => $r1,
		"r2"   => $r2
	] );

	exit();

}

if ( $action == 'transfer' ) {

	$chat_id = str_replace( " ", "+", $_REQUEST['chat_id'] );

	?>
	<DIV class="zagolovok"><B>Передача диалога</B></DIV>
	<form action="php/chats.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="ti.action">
		<input type="hidden" id="type" name="type" value="transfer">
		<input type="hidden" name="chat_id" id="chat_id" value="<?= $chat_id ?>">

		<div class="row">

			<div class="column grid-10 relativ">
				<span class="label">Новый оператор:</span>
				<span class="select">
					<select id="iduser" name="iduser" class="wp100 required">
						<option value="">--Укажите оператора--</option>
						<?php
						$users     = $chat -> getOperatorsUID();
						$operators = $chat -> getOperators();
						$online    = (new Comet()) -> userOnline( $operators );

						foreach ( $users as $user ) {

							print '<option value="'.$user['iduser'].'" class="'.($online[ $user['uid'] ] ? "greenbg" : "graybg").'">'.$user['title'].' ['.($online[ $user['uid'] ] ? "online" : "offline").']</option>';

						}
						?>
					</select>
				</span>
			</div>

		</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		$(function () {

			$('#dialog').css('width', '500px');
			$('#type').trigger('change');

		});

		function saveForm() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				if (data === 'ok') {

					Swal.fire({
						imageUrl: 'assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						position: 'bottom-end',
						html: 'Успешно',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}
				else {

					Swal.fire({
						imageUrl: 'assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: 'Ошибка',
						icon: 'error',
						showConfirmButton: false,
						timer: 3500
					});

				}

				$chat_id = 0;
				localStorage.setItem("lastChat", '0');

				$('li[data-id="reload"]').trigger('click');

				DClose();

			});
		}

	</script>
	<?php

	exit();

}

if ( $action == 'invite' ) {

	$chat_id = str_replace( " ", "+", $_REQUEST['chat_id'] );

	?>
	<DIV class="zagolovok"><B>Пригласить коллегу</B></DIV>
	<form action="php/chats.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" id="action" name="action" value="ti.action">
		<input type="hidden" id="type" name="type" value="invite">
		<input type="hidden" name="chat_id" id="chat_id" value="<?= $chat_id ?>">

		<div class="row">

			<div class="column grid-10 relativ">
				<span class="label">Кого пригласить:</span>
				<span class="select">
					<select id="iduser" name="iduser" class="wp100 required">
						<option value="">--Укажите оператора--</option>
						<?php
						$users     = $chat -> getOperatorsUID();
						$operators = $chat -> getOperators();
						$online    = (new Comet()) -> userOnline( $operators );

						foreach ( $users as $user ) {

							print '<option value="'.$user['iduser'].'" class="'.($online[ $user['uid'] ] ? "greenbg" : "graybg").'">'.$user['title'].' ['.($online[ $user['uid'] ] ? "online" : "offline").']</option>';

						}
						?>
					</select>
				</span>
			</div>

		</div>

		<hr>

		<div class="text-right">

			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</form>

	<script>

		$(function () {

			$('#dialog').css('width', '500px');
			$('#type').trigger('change');

		});

		function saveForm() {

			var str = $('#Form').serialize();
			var url = $('#Form').attr("action");

			$('#dialog_container').css('display', 'none');

			$.post(url, str, function (data) {

				if (data === 'ok') {

					Swal.fire({
						imageUrl: 'assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						position: 'bottom-end',
						html: 'Успешно',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

				}
				else {

					Swal.fire({
						imageUrl: 'assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: 'Ошибка',
						icon: 'error',
						showConfirmButton: false,
						timer: 3500
					});

				}

				//$chat_id = 0;
				//localStorage.setItem("lastChat", '0');

				$('li[data-id="reload"]').trigger('click');

				DClose();

			});
		}

	</script>
	<?php

	exit();

}

if ( $action == 'ti.action' ) {

	$chat_id = str_replace( " ", "+", $_REQUEST['chat_id'] );
	$type    = $_REQUEST['type'];
	$iduser  = $_REQUEST['iduser'];

	$r    = 0;
	$text = '';

	$comet         = new Comet();
	$cometSettings = $comet -> settings;

	switch ($type) {

		case "transfer":

			$r    = $chat -> chatSetUser( $chat_id, $iduser );
			$text = "Вам передан Диалог";

			/**
			 * Вызов метода передачи диалога
			 * Если поддерживается провайдером
			 */
			$tr = $chat -> chatTransfer( $chat_id, $iduser );

		break;
		case "invite":

			$r    = $chat -> chatAppendUser( $chat_id, $iduser );
			$text = "Вас добавили в Диалог";

			/**
			 * Вызов метода приглашения оператора в диалог
			 * Если поддерживается провайдером
			 */
			$tr = $chat -> chatInvite( $chat_id, $iduser );

		break;

	}

	// отправка ws-сообщения пользователю
	if ( $text != '' ) {

		$msg = [
			"text"   => $text,
			"chatid" => $chat_id,
			"tip"    => 'alert'
		];
		$c   = $comet -> sendMessage( $iduser, json_encode( $msg ) );

	}

	print $r > 0 ? "ok" : "error";

	exit();

}

if ( $action == 'autoClose' ) {

	$chat -> chatAutoClose();

}