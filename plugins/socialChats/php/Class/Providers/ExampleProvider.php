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

/**
 * Заготовка Класса-провайдера для работы с вашим каналом
 *
 * Class ExampleProvider
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class ExampleProvider {

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

		$this -> identity = $GLOBALS[ 'identity' ];
		$this -> iduser1  = $GLOBALS[ 'iduser1' ];
		$this -> sqlname  = $GLOBALS[ 'sqlname' ];
		$this -> db       = $GLOBALS[ 'db' ];
		$this -> fpath    = $GLOBALS[ 'fpath' ];
		$this -> opts     = $GLOBALS[ 'opts' ];
		$this -> tmzone   = $GLOBALS[ 'tmzone' ];

		$this -> api_key    = $GLOBALS[ 'db' ] -> getOne( "SELECT api_key FROM ".$GLOBALS[ 'sqlname' ]."settings WHERE id = '$GLOBALS[identity]'" );
		$scheme             = isset( $_SERVER[ 'HTTP_SCHEME' ] ) ? $_SERVER[ 'HTTP_SCHEME' ] : ( ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://' );
		$this -> serverhost = $scheme.$_SERVER[ "HTTP_HOST" ];

		// тут почему-то не срабатывает
		if ( !empty( $params ) )
			foreach ( $params as $key => $val )
				$this ->{$key} = $val;

		date_default_timezone_set( $this -> tmzone );

	}

	public static function providerName() {

		return [
			"name"      => "example",
			"title"     => "Example",
			"messenger" => "Example",
			"channel"   => "Example Channel",
			"icon"      => "example.png"
		];

	}

	/**
	 * Форма настроек для Провайдера
	 *
	 * @param int $id - id записи канала
	 */
	public static function settingsForm( $id = 0 ) {

		$scheme     = isset( $_SERVER[ 'HTTP_SCHEME' ] ) ? $_SERVER[ 'HTTP_SCHEME' ] : ( ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://' );
		$serverhost = $scheme.$_SERVER[ "HTTP_HOST" ];

		$channel = Chats ::channelsInfo( $id );

		?>
		<div class="column grid-10">

			<div class="infodiv bgwhite">
				Зарегистрируйте приложение в
				<a href="https://vk.com/apps?act=manage" target="_blank" title="Мои приложения">Vk.com</a> и укажите
			</div>

		</div>

		<div class="column grid-10 relative">
			<span class="label">ID приложения вконтакте</span>
			<input type="text" name="app_id" id="app_id" class="wp100 required" value="<?= $channel[ 'settings' ][ 'app_id' ] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Секретный код приложения</span>
			<input type="text" name="app_secret" id="app_secret" class="wp100" value="<?= $channel[ 'settings' ][ 'app_secret' ] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Токен группы</span>
			<input type="text" name="group_token" id="group_token" class="wp100" value="<?= $channel[ 'settings' ][ 'group_token' ] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">ID группы</span>
			<input type="text" name="channel_id" id="channel_id" class="wp100" value="<?= $channel[ 'channel_id' ] ?>">
			<div class="fs-09 gray2">Можно получить из URL сообщества: https://vk.com/<b class="red">salesmancrm</b>
			</div>
		</div>

		<div class="column grid-10 relative hidden">
			<span class="label">CONFIRMATION_TOKEN</span>
			<input type="text" name="confirmation_token" id="confirmation_token" class="wp100" value="<?= $channel[ 'settings' ][ 'confirmation_token' ] ?>">
			<div class="fs-09 gray2">Строка для подтверждения адреса сервера из настроек Callback API вида
				<b class="red">d8v2ve07</b></div>
		</div>

		<div class="column grid-10 text-center">

			<a href="javascript:void(0)" onclick="getToken()" title="Проверить" class="button greenbtn fs-09 ptb5lr15 hidden1"><i class="icon-ok"></i>Авторизация</a>
			<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button fs-09 ptb5lr15"><i class="icon-ok"></i>Проверить</a>

		</div>

		<div class="column grid-10 relative">
			<span class="label">Токен приложения</span>
			<input type="text" name="token" id="token" class="wp100" value="<?= $channel[ 'token' ] ?>">
			<div class="fs-09 gray2">Получить после Авторизации - скопировать из адреса окна авторизации</div>
		</div>

		<div class="divider mt20">Данные, заполняемые после проверки</div>

		<div class="column grid-10 relative">
			<span class="label">Callback Server ID</span>
			<input type="text" name="server_id" id="server_id" class="wp100" value="<?= $channel[ 'settings' ][ 'server_id' ] ?>">
			<div class="fs-09 gray2">ID Callback-сервера</div>
		</div>

		<div class="column grid-10 relative">
			<span class="label">Имя группы:</span>
			<input type="text" name="name" id="name" class="wp100" value="<?= $channel[ 'name' ] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Ссылка:</span>
			<input type="text" name="link" id="link" class="wp100" value="<?= $channel['settings'][ 'link' ] ?>">
		</div>

		<script>

			function getToken() {

				var app_id = $('#app_id').val();

				var url = 'https://oauth.vk.com/authorize?client_id=' + app_id + '&redirect_uri=https://oauth.vk.com/blank.html&display=popup&response_type=token&scope=groups,photos,docs,offline';

				//var url = 'https://oauth.vk.com/authorize?client_id=' + app_id + '&redirect_uri=https://oauth.vk.com/blank.html&display=popup&response_type=token&scope=manage,messages,photos,docs';
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

						$('.rezult').html('Ответ: <b>Соединение установлено</b>');
						$('#channel_id').val(data.channel_id);
						$('#name').val(data.name);

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
	 */
	public function check( $params = [] ) {

		$app_id       = $params[ 'app_id' ];
		$app_secret   = $params[ 'app_secret' ];
		$access_token = $params[ 'token' ];
		$channel_id   = $params[ 'channel_id' ];

		$res = [];

		if ( !empty( $res[ 'response' ] ) )
			$result = [
				"ok"         => true,
				"channel_id" => $res[ 'response' ][ 'id' ],
				"name"       => $res[ 'response' ][ 'name' ]
			];

		else
			$result = [
				"ok"      => false,
				"message" => $res[ 'error' ][ 'error_msg' ]
			];

		return $result;

	}

	/**
	 * Подтверждение адреса сервера для webhook
	 * Здесь вы можете задать логику подтверждения url
	 * Если не нужно, то не используется
	 */
	public function callbackServerConfirmation() {

		$db      = $this -> db;
		$sqlname = $this -> sqlname;

		$params = json_decode( file_get_contents( 'php://input' ), true );

		if ( $params[ 'type' ] == 'confirmation' ) {

			$channel_id = $params[ 'group_id' ];
			$settings   = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}chats_channels WHERE channel_id = '$channel_id'" ), true );

			print $settings[ 'confirmation_token' ];

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
	 */
	public function setWebhook( $params = [] ) {

		$api_key    = $this -> api_key;
		$serverhost = $this -> serverhost;
		$db         = $this -> db;
		$sqlname    = $this -> sqlname;

		$params[ 'settings' ] = !is_array( $params[ 'settings' ] ) ? json_decode( $params[ 'settings' ], true ) : $params[ 'settings' ];

		//print_r($params);

		$app_id     = $params[ 'settings' ][ 'app_id' ];
		$app_secret = $params[ 'settings' ][ 'app_secret' ];
		$access_token = $params[ 'token' ];
		$channel_id   = $params[ 'channel_id' ];

		$server_id = 0;
		$res = [];

		// Здесь выполняется ваша логика, если API поддерживает установку webhook автоматом
		// Формат URL:
		// 1 - Example - Название провайтера
		// 2 - Api-key - для идентификации принимающим скриптом
		// 3 - channel_id - ID-канала или аккаунта в вашей системе
		$webhookURL = $serverhost.'/plugins/socialChats/php/webhooks/Example/'.$api_key.'/'.$channel_id.'/';

		if ( $server_id > 0 ) {

			$res[ 'ok' ]          = true;
			$res[ 'description' ] = 'Callback Server добавлен';

			/**
			 * Добавим id канала в настройки
			 */

			$settings                = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}chats_channels WHERE channel_id = '$channel_id'" ), true );
			$settings[ 'server_id' ] = $server_id;

			$db -> query( "UPDATE {$sqlname}chats_channels SET ?u WHERE channel_id = '$channel_id'", ["settings" => json_encode_cyr( $settings )] );

		}

		$result = [
			"status"    => ( $res[ 'ok' ] ) ? "ok" : "error",
			"server_id" => $server_id,
			"message"   => $res[ 'description' ]
		];

		return $result;

	}

	/**
	 * Удаление вебхук, если поддерживается
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 *
	 * @return array - ответ
	 *              - str **status** - статус установки (ok - успешно, error - ошибка)
	 *              - str **message** - сообщение
	 */
	public function deleteWebhook( $params = [] ) {

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
	public function getInfo( $params = [] ) {

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
	public function sendMessage( $params = [] ) {

		$params[ 'settings' ] = !is_array( $params[ 'settings' ] ) ? json_decode( $params[ 'settings' ], true ) : $params[ 'settings' ];

		//print_r($params);

		$app_id       = $params[ 'settings' ][ 'app_id' ];
		$app_secret   = $params[ 'settings' ][ 'app_secret' ];
		$access_token = $params[ 'token' ];
		$channel_id   = $params[ 'channel_id' ];
		$user_id      = $params[ 'chat_id' ];

		// todo: добавить возможность отправки изображений, документов

		// id сообщения в вашей системе
		$message_id  = 0;
		// признак успешности отправки
		$res[ 'ok' ] = false;

		$result = [
			"result"      => $res[ 'ok' ] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res[ 'response' ][ 'error' ],
			"description" => $res[ 'response' ][ 'error' ]
		];

		return $result;

	}

	/**
	 * Отправка файлов. Файлы отправляются по одному и отдельно от текста
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendFile( $params = [] ) {

		$rootpath = $GLOBALS[ 'rootpath' ];

		$params[ 'settings' ] = !is_array( $params[ 'channel' ][ 'settings' ] ) ? json_decode( $params[ 'channel' ][ 'settings' ], true ) : $params[ 'channel' ][ 'settings' ];

		$app_id       = $params[ 'settings' ][ 'app_id' ];
		$app_secret   = $params[ 'settings' ][ 'app_secret' ];
		$access_token = $params[ 'token' ];
		$channel_id   = $params[ 'channel_id' ];
		$user_id      = $params[ 'chat_id' ];

		$res   = [];
		$param = [];
		$message_id = '';

		/**
		 * Для фото и документов идут разные методы (у вас может быть по-другому)
		 * Должны вернуть id сообщения
		 */

		if ( !empty( $params[ 'attachments' ][ 'photo' ] ) ) {

			$message_id = '';

		}
		elseif ( !empty( $params[ 'attachments' ][ 'doc' ] ) ) {

			$message_id = '';

		}

		if ( !isset( $res[ 'error' ] ) ) {

			$res[ 'ok' ] = true;

			$result = [
				"result"      => $res[ 'ok' ] ? 'ok' : 'error',
				"message_id"  => $message_id,
				"error_code"  => $res[ 'response' ][ 'error' ],
				"description" => $res[ 'response' ][ 'error' ]
			];

		}
		else {

			$result = [
				"result"      => 'error',
				"error_code"  => $res[ 'response' ][ 'error' ],
				"description" => $res[ 'response' ][ 'error_msg' ]
			];

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
	 *                       - array **chat** - инфа о чате
	 *                       - array **channel** - инфа о канале
	 *
	 * @return array
	 */
	public function deleteMessage( $message = [] ) {

		$params[ 'settings' ] = !is_array( $message[ 'channel' ][ 'settings' ] ) ? json_decode( $message[ 'channel' ][ 'settings' ], true ) : $message[ 'channel' ][ 'settings' ];

		//print_r($params);

		$app_id       = $params[ 'settings' ][ 'app_id' ];
		$app_secret   = $params[ 'settings' ][ 'app_secret' ];
		$access_token = $message[ 'channel' ][ 'token' ];
		$channel_id   = $message[ 'channel_id' ];

		$message_id  = $message['id'];
		$res[ 'ok' ] = false;

		// Здесь выполняем удаление сообщения

		if ( !empty( $res[ 'response' ] ) ) {

			$res[ 'ok' ] = true;

		}

		$result = [
			"result"      => $res[ 'ok' ] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res[ 'error' ][ 'error_code' ],
			"description" => $res[ 'error' ][ 'error_msg' ]
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
	public function setReadStateMessage( $messages = [], $params = [] ) {

		$params[ 'settings' ] = !is_array( $params[ 'settings' ] ) ? json_decode( $params[ 'settings' ], true ) : $params[ 'settings' ];

		//print_r($params);

		$app_id       = $params[ 'settings' ][ 'app_id' ];
		$app_secret   = $params[ 'settings' ][ 'app_secret' ];
		$group_token  = $params[ 'settings' ][ 'group_token' ];
		$access_token = $params[ 'token' ];
		$channel_id   = $params[ 'channel_id' ];

		// todo: добавить возможность отправки изображений, документов

		$res[ 'ok' ] = false;

		// Если надо, то реализуем пометку письма прочитанным

		$result = [
			"result"      => $res[ 'ok' ] ? 'ok' : 'error',
			"error_code"  => $res[ 'response' ][ 'error' ],
			"description" => $res[ 'response' ][ 'error' ]
		];

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
	public function getUserInfo( $user_id = 0, $params = [] ) {

		$params[ 'settings' ] = !is_array( $params[ 'settings' ] ) ? json_decode( $params[ 'settings' ], true ) : $params[ 'settings' ];

		$app_id       = $params[ 'settings' ][ 'app_id' ];
		$app_secret   = $params[ 'settings' ][ 'app_secret' ];
		$access_token = $params[ 'token' ];

		$res = [];

		if ( !empty( $res[ 'error' ] ) ) {

			$result = [
				"result"      => 'error',
				"ok"          => false,
				"description" => $res[ 'error' ][ 'error_msg' ]
			];

		}
		else {

			$result = [
				'chat_id'          => $res[ 'response' ][ 'id' ],
				'client_id'        => $res[ 'response' ][ 'id' ],
				'client_firstname' => $res[ 'response' ][ 'first_name' ],
				'client_lastname'  => $res[ 'response' ][ 'last_name' ],
				'client_avatar'    => $res[ 'response' ][ 'avatar' ],
			];

		}

		return $result;

	}

	/**
	 * Обработка входящих сообщений
	 * Вызывается из скрипта Webhook
	 * Метод должен вернуть данные в формате плагина
	 *
	 * @param array $params - пришедшие на вебхук данные
	 *                      - channel - вся информация из настроек канала
	 * @param array $channel
	 * @return array
	 *              - str **message_id** - id сообщения на вашем сервере
	 *              - str **chat_id** - id чата
	 *              - str **text** - текст сообщения
	 *              - str **direction** - направление ( = in )
	 *              - array **attachment** - массив вложений
	 *                  - array **doc** - файлы
	 *                      - str **url** - ссылка на файл
	 *                      - str **title** - название с расширением
	 *                      - str **ext** - расширение (не обязательное)
	 *                      - str **icon** - класс иконки получаемый функцией get_icon3()
	 *                  - array **photo** - изображения
	 *                      - str **url** - ссылка на файл
	 *                      - str **title** - название с расширением
	 *                      - str **icon** - класс иконки получаемый функцией get_icon3()
	 */
	public function eventFilter( $params = [], $channel = [] ) {

		$event = $params[ 'type' ];

		$message     = [];
		$attachments = [];

		$message[ 'message_id' ] = $params[ 'object' ][ 'id' ];
		$message[ 'chat_id' ]    = $params[ 'object' ][ 'user_id' ];
		$message[ 'text' ]       = $params[ 'object' ][ 'text' ];
		$message[ 'direction' ]  = $params[ 'object' ][ 'out' ] == 1 ? "out" : "in";
		$attachments             = $params[ 'object' ][ 'attachments' ];

		foreach ( $attachments as $attach ) {

			switch ( $attach[ 'type' ] ) {

				case "doc":

					$message[ 'attachment' ][ 'doc' ][] = [
						"url"   => $attach[ 'doc' ][ 'url' ],
						"title" => $attach[ 'doc' ][ 'title' ],
						"size"  => $attach[ 'doc' ][ 'size' ],
						"ext"   => $attach[ 'doc' ][ 'ext' ],
						"icon"  => get_icon3( $attach[ 'doc' ][ 'title' ] ),
					];

				break;
				case "photo":

					$r        = parse_url( $attach[ 'photo' ][ 'sizes' ][ '3' ][ 'url' ] );
					$filename = basename( $r[ 'path' ] );

					$message[ 'attachment' ][ 'photo' ][] = [
						"url"     => $attach[ 'photo' ][ 'sizes' ][ '3' ][ 'url' ],
						"title"   => $filename,
						"preview" => $attach[ 'photo' ][ 'sizes' ][ '1' ][ 'url' ],
						"icon"    => 'icon-file-image yelw',
					];

				break;

			}

		}

		// находим чат
		$ch   = new Chats();
		$chat = $ch -> chatInfo( 0, $message[ 'chat_id' ] );

		switch ( $event ) {

			case "message_new":
			case "message_reply":

				// если чат не наден, то добавляем его
				if ( empty( $chat ) ) {

					// запрашиваем данные пользователя
					$user = self ::getUserInfo( $message[ 'chat_id' ], $channel );

					$message[ 'client_firstname' ] = $user[ 'client_firstname' ];
					$message[ 'client_lastname' ]  = $user[ 'client_lastname' ];
					$message[ 'client_avatar' ]    = $user[ 'client_avatar' ];

					$message['type'] = 'example';

				}

				$message[ 'event' ] = "newMessage";

			break;

		}

		return $message;

	}

	/**
	 * Заглушка для метода передачи чата другому оператору
	 * @param string $chat_id
	 * @param integer $iduser
	 * @param array $params
	 * @return bool
	 */
	public function chatTransfer($chat_id = '', $params = [], $iduser = 0){

		return true;

	}

	/**
	 * Заглушка для метода приглашение оператора в чат
	 * @param string $chat_id
	 * @param integer $iduser
	 * @param array $params
	 * @return bool
	 */
	public function chatInvite($chat_id = '', $params = [], $iduser = 0){

		return true;

	}

}