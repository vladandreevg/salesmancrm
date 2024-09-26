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

use Mike4ip\ChatApi;

/**
 * Заготовка Класса-провайдера для работы с каналом Whatsapp от Chat API
 * https://chat-api.com/ru/docs.html#post_message
 * https://chat-api.com/ru/swagger.html
 *
 * Class ChatapiProvider
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class ChatapiProvider {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	public $api_key = '';
	public $serverhost = '';

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 5 );

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
		$scheme             = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
		$this -> serverhost = $scheme.$_SERVER["HTTP_HOST"];

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set( $this -> tmzone );

	}

	public static function providerName(): array {

		return [
			"name"      => "chatapi",
			"title"     => "Chatapi",
			"channel"   => "Whatsapp Сhat API",
			"messenger" => "Whatsapp",
			"icon"      => "whatsapp.png"
		];

	}

	/**
	 * Форма настроек для Провайдера
	 *
	 * @param int $id - id записи канала
	 */
	public static function settingsForm( $id = 0 ) {

		$channel = Chats ::channelsInfo( $id );

		//print_r($channel);

		if ( $channel[ 'name' ] == '' )
			$channel[ 'name' ] = 'Whatsapp Chat API';

		if ( $channel[ 'settings' ][ 'link' ] == '' )
			$channel[ 'settings' ][ 'link' ] = 'https://wa.me/ваш_номер';

		?>
		<div class="column grid-10 relative">

			<span class="label">API URL:</span>
			<input type="text" name="url" id="url" class="wp100 required" value="<?= $channel[ 'settings' ][ 'url' ] ?>">
			<div class="fs-09 blue text-left">Укажите Api URL</div>

		</div>

		<div class="column grid-10 relative">

			<span class="label">Token:</span>
			<input type="text" name="token" id="token" class="wp100 required" value="<?= $channel[ 'token' ] ?>">
			<div class="fs-09 blue text-left">Укажите токен и нажмите "QR - Авторизация"</div>

		</div>

		<div class="column grid-10 relative">
			<span class="label">ID канала:</span>
			<input type="text" name="channel_id" id="channel_id" class="wp100" value="<?= $channel[ 'channel_id' ] ?>">
		</div>

		<div class="column grid-10 text-center">
			<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button greenbtn fs-09 ptb5lr15"><i class="icon-ok"></i>QR - Авторизация</a>
		</div>

		<div class="rezult pad10 div-center"></div>

		<div class="divider">Данные, заполняемые после проверки</div>

		<div class="column grid-10 relative">
			<span class="label">Имя канала:</span>
			<input type="text" name="name" id="name" class="wp100" value="<?= $channel[ 'name' ] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Ссылка:</span>
			<input type="text" name="link" id="link" class="wp100" value="<?= $channel[ 'settings' ][ 'link' ] ?>">
		</div>

		<script>

			function checkConnection() {

				$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

				var str = 'action=channel.check&type=' + $('#type').val() + '&token=' + $('#token').val() + '&url=' + $('#url').val();
				var url = $('#Form').attr("action");

				$.post(url, str, function (data) {

					if (data.ok === true) {

						$('.rezult').html('<img src="' + data.qr + '">').prepend('<div class="Bold green">Дождитесь загрузки QR-кода.<br>Затем откройте Whatsapp на телефоне -> три точки -> WhatsApp Web и отсканируйте код</div>');

					}
					else $('.rezult').html('Ответ: <b>' + data.message + '</b>');

				}, 'json')
					.complete(function () {

						$('#dialog').center();

					});

			}

		</script>
		<?php

	}

	/**
	 * Проверка подключения. Запрос QR-кода
	 *
	 * @param array $params
	 *                     - str **url** - url для аккаунта
	 *                     - str **token** - ключ
	 *
	 * @return mixed
	 *              - ok = true - свидетельство об успешном подключении
	 *              - result - возврат параметров канала
	 *                  - id - id канала
	 *                  - username - имя канала
	 */
	public function check(array $params = []) {

		$token = $params['token'];
		$url   = $params['url'];

		$res = [];

		$api = new ChatApi( $token, $url );

		$status = $api -> getStatus();

		if ( $status != 'authenticated' ) {

			$qr_url = $api -> getQRCode();

			$data = file_get_contents( $qr_url );

			$qr = 'data:image/png;base64,'.base64_encode( $data );

			if ( $qr != '' ) {
				$result = [
					"ok" => true,
					"qr" => $qr,
				];
			}
			else {
				$result = [
					"ok"      => false,
					"message" => $res['message']
				];
			}

		}
		else {

			$result = [
				"ok"      => false,
				"message" => "Уже авторизован"
			];

		}

		return $result;

	}

	/**
	 * Подтверждение адреса сервера для webhook
	 * Здесь вы можете задать логику подтверждения url
	 * Если не нужно, то не используется
	 */
	public function callbackServerConfirmation() {


	}

	/**
	 * Установка вебхук
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 * @return array - ответ
	 *                      - str **status** - статус установки (ok - успешно, error - ошибка)
	 *                      - str **message** - сообщение
	 */
	public function setWebhook(array $params = []): array {

		$api_key    = $this -> api_key;
		$serverhost = $this -> serverhost;

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$url        = $params['settings']['url'];
		$token      = $params['token'];
		$channel_id = $params['channel_id'];

		$res = [];

		$webhookURL = $serverhost.'/plugins/socialChats/php/webhooks/Chatapi/'.$api_key.'/'.$channel_id.'/';

		$api = new ChatApi( $token, $url );

		$re = $api -> setWebhook( $webhookURL );

		//print_r($res);

		if ( $re -> set == true ) {

			$res['ok']          = true;
			$res['description'] = 'Webhook добавлен';

		}

		return [
			"status"  => ($res['ok']) ? "ok" : "error",
			"message" => $res['description']
		];

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
	public function deleteWebhook(array $params = []): array {

		return [
			"status"  => "ok",
			"message" => "Callback-сервер удаляется вручную из настроек сообщества"
		];

	}

	/**
	 * Информация о канале (не используется)
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function getInfo(array $params = []): array {

		return [];

	}

	/**
	 * Отправляет сохраненное сообщение
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 *                      - str **text** - текст сообщения
	 *
	 * @return array
	 */
	public function sendMessage(array $params = []): array {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$url     = $params['settings']['url'];
		$token   = $params['token'];
		$chat_id = $params['chat_id'];

		// признак успешности отправки
		$res['ok'] = false;

		$api = new ChatApi( $token, $url );
		$re  = $api -> sendMessage( $chat_id, $params['text'] );

		//print_r($re);

		return [
			"result"      => $re ? 'ok' : 'error',
			"message_id"  => $re['id'],
			"error_code"  => $res['response']['error'],
			"description" => $res['response']['error']
		];

	}

	/**
	 * Отправка файлов. Файлы отправляются по одному и отдельно от текста
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendFile(array $params = []): array {

		$url     = $params['settings']['url'];
		$token   = $params['token'];
		$chat_id = $params['chat_id'];

		$res = [];
		$f   = [];

		// признак успешности отправки
		$res['ok'] = false;

		/**
		 * Для фото и документов идут разные методы (у вас может быть по-другому)
		 * Должны вернуть id сообщения
		 */

		if ( !empty( $params['attachments']['photo'] ) ) {

			$f = $params['attachments']['photo'][0];

		}
		elseif ( !empty( $params['attachments']['doc'] ) ) {

			$f = $params['attachments']['doc'][0];

		}


		if ( !empty( $f ) ) {

			$fbody = $this -> serverhost.$f['url'];

			$api = new ChatApi( $token, $url );
			$res = $api -> sendFile( $chat_id, $fbody, $f['title'] );

			//print_r( $res );

		}
		else {

			$res = [
				"error"     => true,
				"error_msg" => "Какая-то ошибка :{"
			];

		}

		if ( $res['sent'] ) {

			$res['ok'] = true;

			$result = [
				"result"      => $res['ok'] ? 'ok' : 'error',
				"message_id"  => $res['id'],
				"error_code"  => $res['response']['error'],
				"description" => $res['response']['error']
			];

		}
		else {

			$result = [
				"result"      => 'error',
				"error_code"  => $res['error'],
				"description" => $res['error_msg']
			];

		}

		return $result;

	}

	/**
	 * Метод для удаления сообщения с сервера
	 * Не поддерживается провайдером
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
	public function deleteMessage(array $message = []): array {

		$params['settings'] = !is_array( $message['channel']['settings'] ) ? json_decode( $message['channel']['settings'], true ) : $message['channel']['settings'];

		$url     = $params['settings']['url'];
		$token   = $params['token'];
		$chat_id = $params['chat_id'];

		$message_id = $message['id'];
		$res['ok']  = false;

		// Здесь выполняем удаление сообщения

		if ( !empty( $res['response'] ) ) {

			$res['ok'] = true;

		}

		return [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res['error']['error_code'],
			"description" => $res['error']['error_msg']
		];

	}

	/**
	 * Помечает сообщения прочитанными
	 * Не поддерживается провайдером
	 *
	 * @param array $messages
	 * @param array $params
	 *
	 * @return array
	 */
	public function setReadStateMessage(array $messages = [], array $params = []): array {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$app_id       = $params['settings']['app_id'];
		$app_secret   = $params['settings']['app_secret'];
		$group_token  = $params['settings']['group_token'];
		$access_token = $params['token'];
		$channel_id   = $params['channel_id'];

		// todo: добавить возможность отправки изображений, документов

		$res['ok'] = false;

		// Если надо, то реализуем пометку письма прочитанным

		return [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"error_code"  => $res['response']['error'],
			"description" => $res['response']['error']
		];

	}

	/**
	 * Получает информацию по пользователю
	 * Возможно использовать при первом входящем сообщении
	 * Не поддерживается провайдером
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
	public function getUserInfo(int $user_id = 0, array $params = []): array {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		$url     = $params['settings']['url'];
		$token   = $params['token'];
		$chat_id = $params['chat_id'];

		$res = [];

		if ( !empty( $res['error'] ) ) {

			$result = [
				"result"      => 'error',
				"ok"          => false,
				"description" => $res['error']['error_msg']
			];

		}
		else {

			$result = [
				//'chat_id'          => $res[ 'response' ][ 'id' ],
				//'client_id'        => $res[ 'response' ][ 'id' ],
				//'client_firstname' => $res[ 'response' ][ 'first_name' ],
				//'client_lastname'  => $res[ 'response' ][ 'last_name' ],
				//'client_avatar'    => $res[ 'response' ][ 'avatar' ],
				'phone' => yexplode( "@", $params['chat_id'], 0 ),
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
	 *                      - str **message_id** - id сообщения на вашем сервере
	 *                      - str **chat_id** - id чата
	 *                      - str **text** - текст сообщения
	 *                      - str **direction** - направление ( = in )
	 *                      - array **attachment** - массив вложений
	 *                      - array **doc** - файлы
	 *                      - str **url** - ссылка на файл
	 *                      - str **title** - название с расширением
	 *                      - str **ext** - расширение (не обязательное)
	 *                      - str **icon** - класс иконки получаемый функцией get_icon3()
	 *                      - array **photo** - изображения
	 *                      - str **url** - ссылка на файл
	 *                      - str **title** - название с расширением
	 *                      - str **icon** - класс иконки получаемый функцией get_icon3()
	 */
	public function eventFilter(array $params = [], array $channel = []): array {

		$rootpath = $GLOBALS['rootpath'];

		$message     = [];
		$attachments = [];

		$message['message_id'] = $params['messages'][0]['id'];
		$message['chat_id']    = $params['messages'][0]['chatId'];
		$message['text']       = $params['messages'][0]['body'];
		$message['direction']  = $params['messages'][0]['fromMe'] == 1 ? "out" : "in";


		switch ($params['messages'][0]['type']) {

			case "document":
			case "image":

				$r = parse_url( $params['messages'][0]['body'] );

				$origfilename = urldecode( basename( $r['path'] ) );

				$r = parse_url( $origfilename );

				$origfilename = basename( $r['path'] );

				$filename = md5( $origfilename ).".".getExtention( $origfilename );

				$fc = $this -> getSslPage( $params['messages'][0]['body'] );
				file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

				$message['attachment']['doc'][] = [
					"url"   => "/files/chatcash/files/".$filename,
					"title" => $origfilename,
					"size"  => filesize( $rootpath."/files/chatcash/files/".$filename ),
					"ext"   => getExtention( $origfilename ),
					"icon"  => get_icon3( $origfilename ),
				];

				$message['text'] = 'Файл';

			break;
			default:

				$message['text'] = $params['messages'][0]['body'];

			break;

		}

		// находим чат
		$ch   = new Chats();
		$chat = $ch -> chatInfo( 0, $message['chat_id'] );

		// если чат не наден, то добавляем его
		if ( empty( $chat ) ) {

			// запрашиваем данные пользователя
			// Не поддерживается провайдером
			//$user = self ::getUserInfo( $message[ 'chat_id' ], $channel );

			$uname = yexplode( " ", $params['messages'][0]['senderName'] );

			$message['client_firstname'] = $uname[0];
			$message['client_lastname']  = $uname[1];
			$message['client_avatar']    = '';
			$message['phone']            = yexplode( "@", $message['chat_id'], 0 );

			$message['type'] = 'Whatsapp';

		}

		if ( $chat['firstname'] == '' ) {

			$uname = yexplode( " ", $params['messages'][0]['senderName'] );

			$message['client_firstname'] = $uname[0];
			$message['client_lastname']  = $uname[1];

		}

		/**
		 * Вроде приходит аватарка клиента
		 */
		if ( !empty( $params['chatUpdate'] ) ) {

			$r = parse_url( $params['chatUpdate'][0]['new']['image'] );

			$origfilename = basename( $r['path'] );

			$filename = md5( $origfilename ).".".getExtention( $origfilename );

			$fc = $this -> getSslPage( $params['chatUpdate'][0]['new']['image'] );
			file_put_contents( $rootpath."/files/chatcash/".$filename, $fc );

			$message['chat_id']       = $params['chatUpdate'][0]['new']['id'];
			$message['client_avatar'] = "/files/chatcash/".$filename;

			$uname = yexplode( " ", $params['chatUpdate'][0]['new']['name'] );

			$message['client_firstname'] = $uname[0];
			$message['client_lastname']  = $uname[1];
			$message['client_avatar']    = "/files/chatcash/".$filename;

			//print_r($message);

		}

		$message['event'] = "newMessage";

		//file_put_contents("test.json", json_encode_cyr($message));

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
	public function chatTransfer(string $chat_id = '', array $params = [], int $iduser = 0): bool {

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
	public function chatInvite(string $chat_id = '', array $params = [], int $iduser = 0): bool {

		return true;

	}

	/**
	 * Получение содержимого файла
	 *
	 * @param $url
	 * @return bool|string
	 */
	private function getSslPage($url) {

		$ch = curl_init();
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_HEADER, false );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_REFERER, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$result = curl_exec( $ch );
		curl_close( $ch );

		return $result;

	}

}