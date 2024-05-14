<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Chats;

use VK\VK;
use VK\VKException;

/**
 * Класс-провайдер для работы с Ck
 *
 * Class VkProvider
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class VkProvider {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	var $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	var $params = [];

	var $api_key = '';
	var $serverhost = '';

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 */
	function __construct() {

		$rootpath = realpath( __DIR__.'/../../../../../' );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		$this -> api_key    = $GLOBALS['db'] -> getOne( "SELECT api_key FROM ".$GLOBALS['sqlname']."settings WHERE id = '$GLOBALS[identity]'" );
		$scheme             = isset( $_SERVER['HTTP_SCHEME'] ) ? $_SERVER['HTTP_SCHEME'] : (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
		$this -> serverhost = $scheme.$_SERVER["HTTP_HOST"];

		// тут почему-то не срабатывает
		if ( !empty( $params ) )
			foreach ( $params as $key => $val )
				$this ->{$key} = $val;

		date_default_timezone_set( $this -> tmzone );

	}

	public static function providerName() {

		return [
			"name"      => "vk",
			"title"     => "Vk",
			"messenger" => "Vk",
			"channel"   => "VK.com",
			"icon"      => "vk.png"
		];

	}

	/**
	 * Форма настроек для Провайдера
	 *
	 * @param int $id - id записи канала
	 */
	public static function settingsForm($id = 0) {

		//$scheme     = isset( $_SERVER[ 'HTTP_SCHEME' ] ) ? $_SERVER[ 'HTTP_SCHEME' ] : ( ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://' );
		//$serverhost = $scheme.$_SERVER[ "HTTP_HOST" ];

		$channel = Chats ::channelsInfo( $id );

		if ( $channel['settings']['link'] == '' )
			$channel['settings']['link'] = 'https://vk.com/club{ID_страницы}';

		?>
		<div class="column grid-10">

			<div class="infodiv bgwhite">
				Зарегистрируйте приложение в
				<a href="https://vk.com/apps?act=manage" target="_blank" title="Мои приложения">Vk.com</a> и укажите
			</div>

		</div>

		<div class="column grid-10 relative">
			<span class="label">ID приложения вконтакте</span>
			<input type="text" name="app_id" id="app_id" class="wp100 required" value="<?= $channel['settings']['app_id'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Секретный код приложения</span>
			<input type="text" name="app_secret" id="app_secret" class="wp100" value="<?= $channel['settings']['app_secret'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Токен группы</span>
			<input type="text" name="group_token" id="group_token" class="wp100" value="<?= $channel['settings']['group_token'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">ID группы</span>
			<input type="text" name="channel_id" id="channel_id" class="wp100" value="<?= $channel['channel_id'] ?>">
			<div class="fs-09 gray2">Можно получить из URL сообщества: https://vk.com/<b class="red">salesmancrm</b>
			</div>
		</div>

		<div class="column grid-10 relative hidden">
			<span class="label">CONFIRMATION_TOKEN</span>
			<input type="text" name="confirmation_token" id="confirmation_token" class="wp100" value="<?= $channel['settings']['confirmation_token'] ?>">
			<div class="fs-09 gray2">Строка для подтверждения адреса сервера из настроек Callback API вида
				<b class="red">d8v2ve07</b></div>
		</div>

		<div class="column grid-10 text-center">

			<a href="javascript:void(0)" onclick="getToken()" title="Проверить" class="button greenbtn fs-09 ptb5lr15 hidden1"><i class="icon-ok"></i>Авторизация</a>
			<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button fs-09 ptb5lr15"><i class="icon-ok"></i>Проверить</a>

		</div>

		<div class="column grid-10 relative">
			<span class="label">Токен приложения</span>
			<input type="text" name="token" id="token" class="wp100" value="<?= $channel['token'] ?>">
			<div class="fs-09 gray2">Получить после Авторизации - скопировать из адреса окна авторизации</div>
		</div>

		<div class="divider mt20">Данные, заполняемые после проверки</div>

		<div class="column grid-10 relative">
			<span class="label">Callback Server ID</span>
			<input type="text" name="server_id" id="server_id" class="wp100" value="<?= $channel['settings']['server_id'] ?>">
			<div class="fs-09 gray2">ID Callback-сервера</div>
		</div>

		<div class="column grid-10 relative">
			<span class="label">Имя группы:</span>
			<input type="text" name="name" id="name" class="wp100" value="<?= $channel['name'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Ссылка:</span>
			<input type="text" name="link" id="link" class="wp100" value="<?= $channel['settings']['link'] ?>">
		</div>

		<script>

			$(document).off('change keyup', '#channel_id');
			$(document).on('change keyup', '#channel_id', function () {

				let id = $(this).val();

				$('#link').val('https://vk.com/club' + id + '?ref=subscribe');

			});

			function getToken() {

				var app_id = $('#app_id').val();

				var url = 'https://oauth.vk.com/authorize?client_id=' + app_id + '&redirect_uri=https://oauth.vk.com/blank.html&display=popup&response_type=token&scope=groups,photos,docs,offline';

				var left = screen.availWidth / 2 - 250;
				var top = screen.availHeight / 2 - 250;

				//var url2 = 'php/oauth.php?url=' + url;

				window.open(url, 'CRM', 'width=500, height=500, menubar=no, location=no, resizable=no, scrollbars=yes, status=no, left=' + left + ', top=' + top);

			}

			function checkConnection() {

				$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

				var str = 'action=channel.check&type=' + $('#type').val() + '&app_id=' + $('#app_id').val() + '&app_secret=' + $('#app_secret').val() + '&token=' + $('#token').val() + '&channel_id=' + $('#channel_id').val();
				var url = $('#Form').attr("action");

				$.post(url, str, function (data) {

					if (data.ok === true) {

						Swal.fire({
							title: 'Есть контакт',
							html: 'Теперь можно сохранить настройки',
							type: 'success',
							showConfirmButton: false,
							timer: 3500
						});

						$('.rezult').html('Ответ: <b>Соединение установлено</b>');
						$('#channel_id').val(data.channel_id);
						$('#name').val(data.name);
						$('#link').val('https://vk.com/club' + data.channel_id);

					}
					else $('.rezult').html('Ошибка: <b>' + data.message + '</b>');

				}, 'json');

			}

		</script>
		<?php

	}

	/**
	 * Проверка подключения
	 *
	 * @param array $params
	 *                     - str **token** - ключ
	 *
	 * @return mixed
	 *              - ok = true - свидетельство об успешном подключении
	 *              - result - возврат параметров канала
	 *                  - id - id канала
	 *                  - username - имя канала
	 * @throws VKException
	 */
	public function check($params = []) {

		$app_id       = $params['app_id'];
		$app_secret   = $params['app_secret'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];

		$vk = new VK( $app_id, $app_secret, $access_token );
		$vk -> setApiVersion( '5.103' );
		$res = $vk -> api( 'groups.getById', ['group_ids' => $channel_id] );


		if ( !empty( $res['response'] ) )
			$result = [
				"ok"         => true,
				"channel_id" => $res['response'][0]['id'],
				"name"       => $res['response'][0]['name']
			];

		else
			$result = [
				"ok"      => false,
				"message" => $res['error']['error_msg']
			];

		return $result;

	}

	/**
	 * Подтверждение сервера
	 */
	public function callbackServerConfirmation() {

		$db      = $this -> db;
		$sqlname = $this -> sqlname;

		$params = json_decode( file_get_contents( 'php://input' ), true );

		if ( $params['type'] == 'confirmation' ) {

			$channel_id = $params['group_id'];
			$settings   = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}chats_channels WHERE channel_id = '$channel_id'" ), true );

			print $settings['confirmation_token'];

			exit();

		}

	}

	/**
	 * Установка вебхук
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 * @return array - ответ
	 *                      - str **status** - статус установки (ok - успешно, error - ошибка)
	 *                      - str **message** - сообщение
	 * @throws VKException
	 */
	public function setWebhook($params = []) {

		$api_key    = $this -> api_key;
		$serverhost = $this -> serverhost;
		$db         = $this -> db;
		$sqlname    = $this -> sqlname;

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id     = $params['settings']['app_id'];
		$app_secret = $params['settings']['app_secret'];
		//$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];

		$server_id = 0;

		$vk = new VK( $app_id, $app_secret, $access_token );
		$vk -> setApiVersion( '5.103' );

		// получаем список серверов
		$servers = $vk -> api( 'groups.getCallbackServers', [
			'group_id' => $channel_id
		] );

		//print $serverhost."\n";
		//print_r( $servers );

		// находим наш по имени
		foreach ( $servers as $server ) {

			$r = parse_url( $server['url'] );
			$s = parse_url( $serverhost );
			//print_r($r);

			//if ( $server[ 'title' ] == 'SalesMan CRM' && $server[ 'status' ] == 'ok' )
			//if ( $server[ 'title' ] == $serverhost && $server[ 'status' ] == 'ok' )
			if ( $r['host'] == $s['host'] && $server['status'] == 'ok' ) {

				$server_id = $server['id'];
				break;

			}

		}

		// получаем код подтверждения
		$res = $vk -> api( 'groups.getCallbackConfirmationCode', [
			'group_id' => $channel_id
		] );

		$confirmationCode = $res['response']['code'];

		// добавляем код в настройки
		if ( is_string( $confirmationCode ) ) {

			$settings                       = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}chats_channels WHERE channel_id = '$channel_id'" ), true );
			$settings['confirmation_token'] = $confirmationCode;

			$db -> query( "UPDATE {$sqlname}chats_channels SET ?u WHERE channel_id = '$channel_id'", ["settings" => json_encode_cyr( $settings )] );

		}

		// если сервер не найден, добавляем новый
		// ссылка содержит следующие параметры после /webhooks/:
		//  - название провайдера
		//  - ключ API от CRM
		//  - имя бота или чата
		if ( $server_id == 0 ) {

			$res = $vk -> api( 'groups.addCallbackServer', [
				'group_id'   => $channel_id,
				'url'        => $serverhost.'/plugins/socialChats/php/webhooks/Vk/'.$api_key.'/'.$channel_id.'/',
				'title'      => 'SalesMan CRM',
				//'title'      => $serverhost,
				'secret_key' => $api_key
			] );

			//print_r( $res );

			$server_id = $res['response']['server_id'];

		}
		// или обновляем существующий
		else {

			$res = $vk -> api( 'groups.editCallbackServer', [
				'group_id'   => $channel_id,
				'server_id'  => $server_id,
				'url'        => $serverhost.'/plugins/socialChats/php/webhooks/Vk/'.$api_key.'/'.$channel_id.'/',
				'title'      => 'SalesMan CRM',
				//'title'      => $serverhost,
				'secret_key' => $api_key
			] );

			//print_r( $res );

			$server_id = $res['response']['server_id'];

		}

		if ( $server_id > 0 ) {

			$res['ok']          = true;
			$res['description'] = 'Callback Server добавлен';

			/**
			 * Добавим id канала в настройки
			 */

			$settings              = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}chats_channels WHERE channel_id = '$channel_id'" ), true );
			$settings['server_id'] = $server_id;

			$db -> query( "UPDATE {$sqlname}chats_channels SET ?u WHERE channel_id = '$channel_id'", ["settings" => json_encode_cyr( $settings )] );

		}

		return [
			"status"    => ($res['ok']) ? "ok" : "error",
			"server_id" => $server_id,
			"message"   => $res['description']
		];

	}

	/**
	 * Удаление вебхук
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 *
	 * @return array - ответ
	 *              - str **status** - статус установки (ok - успешно, error - ошибка)
	 *              - str **message** - сообщение
	 */
	public function deleteWebhook($params = []) {

		$result = [
			"status"  => "ok",
			"message" => "Callback-сервер удаляется вручную из настроек сообщества"
		];

		return $result;

	}

	/**
	 * Информация о канале (не используется)
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function getInfo($params = []) {

		$result = [];

		return $result;

	}

	/**
	 * Отправляет сохраненное сообщение
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 *                      - str **text** - текст сообщения
	 *
	 * @return array
	 */
	public function sendMessage($params = []) {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];
		$user_id      = $params['chat_id'];

		// todo: добавить возможность отправки изображений, документов

		$message_id = 0;
		$res['ok']  = false;

		try {

			$vk = new VK( $app_id, $app_secret, $group_token );
			$vk -> setApiVersion( '5.103' );
			$res = $vk -> api( 'messages.send', [
				'group_id'  => $channel_id,
				'user_id'   => $user_id,
				'random_id' => Chats ::genkey( 4, 64 ),
				'message'   => $params['text']
			] );

			if ( $res['response'] > 0 ) {

				$res['ok']  = true;
				$message_id = $res['response'];

			}

			//print_r($res);

		}
		catch ( VKException $e ) {

			$res['ok'] = false;

		}

		$result = [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res['response']['error'],
			"description" => $res['response']['error']
		];

		return $result;

	}

	/**
	 * Отправка файлов
	 *
	 * @param array $params
	 * @return array
	 * @throws VKException
	 */
	public function sendFile($params = []) {

		//print_r($params);

		$rootpath = $GLOBALS['rootpath'];

		//$params[ 'settings' ] = !is_array( $params[ 'channel' ][ 'settings' ] ) ? json_decode( $params[ 'channel' ][ 'settings' ], true ) : $params[ 'channel' ][ 'settings' ];

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];
		$user_id      = $params['chat_id'];

		//print_r($params);

		$res   = [];
		$param = [];

		$vk = new VK( $app_id, $app_secret, $access_token );
		$vk -> setApiVersion( '5.103' );

		// 	медиавложения к личному сообщению, перечисленные через запятую. Каждое прикрепление представлено в формате:
		// <type><owner_id>_<media_id>
		// <type> — тип медиавложения:
		//    photo — фотография;
		//    video — видеозапись;
		//    audio — аудиозапись;
		//    doc — документ;
		// <owner_id> — идентификатор владельца медиавложения (обратите внимание, если объект находится в сообществе, этот параметр должен быть отрицательным).
		// <media_id> — идентификатор медиавложения.
		$attach = '';

		/**
		 * Для фото и документов идут разные методы
		 */

		if ( !empty( $params['attachments']['photo'] ) ) {

			// сначала получае ссылку на сервер
			$server = $vk -> api( 'photos.getMessagesUploadServer', [
				'group_id' => $channel_id,
				'peer_id'  => $user_id
			] );

			//print_r($server);

			if ( !isset( $server['error'] ) ) {

				$server_url = $server['response']['upload_url'];

				$f = $params['attachments']['photo'][0];

				$param['photo'] = curl_file_create( $rootpath.$f['url'], $f['type'], $f['title'] );

				// отправляем через системную функцию
				$fu = Chats ::outSender( $server_url, $param, false );

				$file = json_decode( $fu, true );
				//print_r($file);

				if ( !empty( $file ) ) {


					//$vk = new VK( $app_id, $app_secret, $access_token );
					$vk = new VK( $app_id, $app_secret, $group_token );
					$vk -> setApiVersion( '5.103' );
					$ds = $vk -> api( 'photos.saveMessagesPhoto', [
						'photo'  => $file['photo'],
						'server' => $file['server'],
						'hash'   => $file['hash']
					] );

					$attach = "photo{$ds['response'][0]['owner_id']}_{$ds[ 'response' ][ 0 ][ 'id' ]}";

				}

				if ( $attach != '' ) {

					// отправляем пустое сообщение с вложением
					$fs = $vk -> api( 'messages.send', $a = [
						'group_id'   => $channel_id,
						'user_id'    => $user_id,
						'random_id'  => Chats ::genkey( 4, 64 ),
						//'message'    => 'Примите файл '.$f['title'],
						'attachment' => $attach,
						'reply_to'   => 0
					] );

					if ( $fs['response'] > 0 ) {

						$fs['ok']   = true;
						$message_id = $fs['response'];

						$result = [
							"result"      => $fs['ok'] ? 'ok' : 'error',
							"message_id"  => $message_id,
							"error_code"  => $res['response']['error'],
							"description" => $res['response']['error']
						];

					}

				}

			}
			else {

				$result = [
					"result"      => 'error',
					"error_code"  => $server['response']['error'],
					"description" => $server['response']['error_msg']
				];

			}

		}
		elseif ( !empty( $params['attachments']['doc'] ) ) {

			// сначала получае ссылку на сервер
			$server = $vk -> api( 'docs.getMessagesUploadServer', [
				'group_id' => $channel_id,
				'type'     => 'doc',
				'peer_id'  => $user_id
			] );

			//print_r($server);

			if ( !isset( $server['error'] ) ) {

				$server_url = $server['response']['upload_url'];

				$f = $params['attachments']['doc'][0];

				$param['file'] = curl_file_create( $rootpath.$f['url'], $f['type'], $f['title'] );

				//++ print_r($param);

				// отправляем через системную функцию
				$fu = Chats ::outSender( $server_url, $param, false );

				$file = json_decode( $fu, true );

				if ( !empty( $file ) ) {


					//$vk = new VK( $app_id, $app_secret, $access_token );
					$vk = new VK( $app_id, $app_secret, $group_token );
					$vk -> setApiVersion( '5.103' );
					$ds = $vk -> api( 'docs.save', [
						'file'  => $file['file'],
						'title' => $f['title'],
						'tags'  => 'doc,salesman'
					] );

					$attach = "doc{$ds['response']['doc']['owner_id']}_{$ds[ 'response' ][ 'doc' ][ 'id' ]}";

				}

				if ( $attach != '' ) {

					// отправляем пустое сообщение с вложением
					$fs = $vk -> api( 'messages.send', $a = [
						'group_id'   => $channel_id,
						'user_id'    => $user_id,
						'random_id'  => Chats ::genkey( 4, 64 ),
						'message'    => 'Примите файл '.$f['title'],
						'attachment' => $attach,
						'reply_to'   => 0
					] );

					if ( $fs['response'] > 0 ) {

						$fs['ok']   = true;
						$message_id = $fs['response'];

						$result = [
							"result"      => $fs['ok'] ? 'ok' : 'error',
							"message_id"  => $message_id,
							"error_code"  => $res['response']['error'],
							"description" => $res['response']['error']
						];

					}

				}

			}
			else {

				$result = [
					"result"      => 'error',
					"error_code"  => $server['response']['error'],
					"description" => $server['response']['error_msg']
				];

			}

		}

		return $result;

	}

	/**
	 * Метод для удаления сообщения с сервера
	 *
	 * @param array $message - массив данных сообщения, включая информацию о канале и чате
	 *                       - int **id** - id записи
	 *                       - str **message_id** - id сообщения на сервере
	 *                       - str **chat_id** - id чата
	 *                       - str **channel_id** - id канала
	 *                       - str **client_id** - id участника
	 *                       - str **content** - текст сообщения
	 *                       - array **chat** - инфа о чате
	 *                       - array **channel** - инфа о канале
	 *
	 * @return array
	 */
	public function deleteMessage($message = []) {

		$params['settings'] = !is_array( $message['channel']['settings'] ) ? json_decode( $message['channel']['settings'], true ) : $message['channel']['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $message['channel']['token'];
		$channel_id   = $message['channel_id'];

		// todo: добавить возможность отправки изображений, документов

		$message_id = 0;
		$res['ok']  = false;

		try {

			$vk = new VK( $app_id, $app_secret, $group_token );
			$vk -> setApiVersion( '5.103' );
			$res = $vk -> api( 'messages.delete', [
				'group_id'       => $channel_id,
				'message_ids'    => $message['message_id'],
				'delete_for_all' => '1'
			] );

			//print_r($res);

			if ( !empty( $res['response'] ) ) {

				$res['ok'] = true;

			}

		}
		catch ( VKException $e ) {

			$res['ok'] = false;

		}

		$result = [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res['error']['error_code'],
			"description" => $res['error']['error_msg']
		];

		return $result;

	}

	/**
	 * Помечает сообщения прочитанными
	 *
	 * @param array $messages
	 * @param array $params
	 *
	 * @return array
	 */
	public function setReadStateMessage($messages = [], $params = []) {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];

		// todo: добавить возможность отправки изображений, документов

		$res['ok'] = false;

		try {

			$vk = new VK( $app_id, $app_secret, $group_token );
			$vk -> setApiVersion( '5.103' );
			$res = $vk -> api( 'messages.markAsRead', [
				'group_id'    => $channel_id,
				'message_ids' => implode( ",", $messages )
			] );

			if ( $res['response'] > 0 ) {

				$res['ok'] = true;

			}

			//print_r($res);

		}
		catch ( VKException $e ) {

			$res['ok'] = false;

		}

		$result = [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"error_code"  => $res['response']['error'],
			"description" => $res['response']['error']
		];

		return $result;

	}

	/**
	 * Получает информацию по участникам сообщества
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 *
	 * @return mixed
	 */
	public function getUsers($params = []) {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];


		try {

			$vk = new VK( $app_id, $app_secret, $group_token );
			$vk -> setApiVersion( '5.103' );

			$result = $vk -> api( 'groups.getMembers', [
				'group_id' => $channel_id,
				'fields'   => 'sex, city, country, photo_50, photo_100, photo_200, online, online_mobile, domain, contacts, connections, site, status'
			] );

		}
		catch ( VKException $e ) {

			$result['error'] = $e -> getTrace();

		}

		return $result;

	}

	/**
	 * Получает информацию по пользователю
	 * Возможно использовать при первом входящем сообщении
	 *
	 * @param int   $user_id - chat_id (id пользователя Вк)
	 * @param array $params  - данные из метода $chat -> channelInfo()
	 *
	 * @return array
	 *              - int **chat_id** - id пользвателя
	 *              - int **client_id** - id пользвателя
	 *              - str **client_firstname** - имя
	 *              - str **client_lastname** - фамилия
	 *              - str **client_avatar** - ссылка на аватар
	 */
	public function getUserInfo($user_id = 0, $params = []) {

		// https://vk.com/dev/users.get
		// Возвращает расширенную информацию о пользователях.
		// Этот метод можно вызвать с сервисным ключом доступа. Возвращаются только общедоступные данные.
		// Этот метод можно вызвать с ключом доступа пользователя.
		// Этот метод можно вызвать с ключом доступа сообщества.

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];

		try {

			$vk = new VK( $app_id, $app_secret, $group_token );
			$vk -> setApiVersion( '5.103' );

			$res = $vk -> api( 'users.get', [
				'user_id' => $user_id,
				'fields'  => 'sex, city, country, photo_50, photo_100, photo_200, online, online_mobile, domain, contacts, connections, site, status'
			] );

			if ( !empty( $res['error'] ) ) {

				$result = [
					"result"      => 'error',
					"ok"          => false,
					"description" => $res['error']['error_msg']
				];

			}
			else {

				$result = [
					'chat_id'          => $res['response'][0]['id'],
					'client_id'        => $res['response'][0]['id'],
					'client_firstname' => $res['response'][0]['first_name'],
					'client_lastname'  => $res['response'][0]['last_name'],
					'client_avatar'    => $res['response'][0]['photo_100'],
				];

			}

		}
		catch ( VKException $e ) {

			$result['sys_error'] = $e -> getTrace();

		}

		return $result;

	}

	/**
	 * Обработка входящих сообщений
	 * Метод должен вернуть данные в формате плагина
	 *
	 * @param array $params - пришедшие на вебхук данные
	 *                      - channel - вся информация из настроек канала
	 * @param array $channel
	 * @return array
	 */
	public function eventFilter($params = [], $channel = []) {

		$rootpath = $GLOBALS['rootpath'];

		$event = $params['type'];

		$message     = [];
		$attachments = [];

		if ( !empty( $params['object'] ) && !isset( $params['object']['message'] ) ) {

			$message['message_id'] = $params['object']['id'];
			$message['chat_id']    = $params['object']['user_id'] == '' ? $params['object']['peer_id'] : $params['object']['user_id'];
			$message['text']       = $params['object']['body'] != '' ? $params['object']['body'] : $params['object']['text'];
			$message['direction']  = $params['object']['out'] == 1 ? "out" : "in";
			$attachments           = $params['object']['attachments'];

		}
		elseif ( !empty( $params['object']['message'] ) ) {

			$message['message_id'] = $params['object']['message']['id'];
			$message['chat_id']    = $params['object']['message']['from_id'];
			$message['text']       = $params['object']['message']['text'];
			$message['direction']  = $params['object']['message']['out'] == 1 ? "out" : "in";
			$attachments           = $params['object']['message']['attachments'];

		}

		foreach ( $attachments as $attach ) {

			switch ($attach['type']) {

				case "link":
				case "video":


				break;
				case "doc":

					// сохраним на диск
					$filename = md5( basename( $attach['doc']['url'] ) ).".".$attach['doc']['ext'];

					// сохраним файл в кэше, т.к. к серверу телеграм хер подключишься
					$fc = file_get_contents( $attach['doc']['url'] );
					file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

					$message['attachment']['doc'][] = [
						"url"   => "/files/chatcash/files/".$filename,
						//"url"   => $attach[ 'doc' ][ 'url' ],
						"title" => $attach['doc']['title'],
						"size"  => $attach['doc']['size'],
						"ext"   => $attach['doc']['ext'],
						"icon"  => get_icon3( $filename ),
					];

				break;
				case "photo":

					$r        = parse_url( $attach['photo']['sizes']['3']['url'] );
					$filename = basename( $r['path'] );

					// сохраним на диск
					$xfilename = md5( basename( $attach['photo']['url'] ) ).".jpeg";

					// сохраним файл в кэше, т.к. к серверу телеграм хер подключишься
					$fc = file_get_contents( $attach['photo']['sizes']['3']['url'] );
					file_put_contents( $rootpath."/files/chatcash/files/".$xfilename, $fc );

					$message['attachment']['photo'][] = [
						"url"     => "/files/chatcash/files/".$xfilename,
						//"url"     => $attach[ 'photo' ][ 'sizes' ][ '3' ][ 'url' ],
						"title"   => $filename,
						"preview" => $attach['photo']['sizes']['1']['url'],
						"icon"    => 'icon-file-image yelw',
					];

				break;

			}

		}

		// находим чат
		$ch   = new Chats();
		$chat = $ch -> chatInfo( 0, $message['chat_id'] );

		switch ($event) {

			case "message_new":
			case "message_reply":

				// если чат не наден, то добавляем его
				if ( empty( $chat ) ) {

					// запрашиваем данные пользователя
					$user = $this -> getUserInfo( $message['chat_id'], $channel );

					$message['client_firstname'] = $user['client_firstname'];
					$message['client_lastname']  = $user['client_lastname'];
					$message['client_avatar']    = $user['client_avatar'];

					$message['type'] = 'vk';

				}

				$message['event'] = "newMessage";

			break;

		}

		return $message;

	}

	/**
	 * Заглушка для метода передачи чата другому оператору
	 *
	 * @param string  $chat_id
	 * @param integer $iduser
	 * @param array   $params
	 * @return bool
	 */
	public function chatTransfer($chat_id = '', $params = [], $iduser = 0) {

		return true;

	}

	/**
	 * Заглушка для метода приглашение оператора в чат
	 *
	 * @param string  $chat_id
	 * @param integer $iduser
	 * @param array   $params
	 * @return bool
	 */
	public function chatInvite($chat_id = '', $params = [], $iduser = 0) {

		return true;

	}

	// получение файла для скачивания. Не работает
	public function getFile($params = [], $ids = []) {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];

		try {

			$vk = new VK( $app_id, $app_secret, $group_token );
			$vk -> setApiVersion( '5.131' );

			$result = $vk -> api( 'docs.getById', ["docs"         => yimplode( ",", $ids ),
			                                       "access_token" => $access_token
			] );

			//print_r($result);

		}
		catch ( VKException $e ) {

			$result['sys_error'] = $e -> getTrace();

		}

		return $result;

	}

}