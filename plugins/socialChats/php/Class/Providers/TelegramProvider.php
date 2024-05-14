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

use Telegram;

/**
 * Класс-провайдер для работы с Telegram
 *
 * Class TelegramProvider
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class TelegramProvider {

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

	public $proxy = [];

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
		$scheme             = $_SERVER['HTTP_SCHEME'] ?? ((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';
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
			"name"      => "telegram",
			"title"     => "Telegram",
			"messenger" => "Telegram",
			"channel"   => "Telegram",
			"icon"      => "telegram.png"
		];

	}

	/**
	 * Форма настроек для Провайдера
	 *
	 * @param int $id - id записи канала
	 */
	public static function settingsForm(int $id = 0) {

		$channel = Chats ::channelsInfo( $id );

		if($channel['settings'][ 'link' ] == '') {
			$channel['settings']['link'] = 'https://tlgg.ru/ИМЯ_БОТА?start=subscribe';
		}

		?>
		<div class="column grid-10 relative">

			<span class="label">Secret Key:</span>
			<input type="text" name="token" id="token" class="wp100 required" value="<?= $channel['token'] ?>">
			<div class="fs-09 blue text-center">Укажите ключ и нажмите "Проверить"</div>

		</div>

		<div class="column grid-10 text-center">
			<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button greenbtn fs-09 ptb5lr15"><i class="icon-ok"></i>Проверить</a>
		</div>

		<div class="rezult pad10 div-center"></div>

		<div class="divider">Данные, заполняемые после проверки</div>

		<div class="column grid-10 relative">
			<span class="label">ID бота:</span>
			<input type="text" name="channel_id" id="channel_id" class="wp100" value="<?= $channel['channel_id'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Имя бота:</span>
			<input type="text" name="name" id="name" class="wp100" value="<?= $channel['name'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Ссылка:</span>
			<input type="text" name="link" id="link" class="wp100" value="<?= $channel['settings'][ 'link' ] ?>">
		</div>

		<div class="divider mb20 mt20">Настройки прокси SOCKS5 (для Телеграмм)</div>

		<div class="column grid-7 relative">

			<span class="label">URL-адрес:</span>
			<input type="text" name="proxy[url]" id="proxy[url]" class="wp100" value="<?= $channel['settings']['proxy']['url'] ?>">

		</div>

		<div class="column grid-3 relative">

			<span class="label">Port:</span>
			<input type="text" name="proxy[port]" id="proxy[port]" class="wp100" value="<?= $channel['settings']['proxy']['port'] ?>">

		</div>

		<div class="column grid-10 relative">

			<span class="label">Авторизация:</span>
			<input type="text" name="proxy[auth]" id="proxy[auth]" class="wp100" value="<?= $channel['settings']['proxy']['auth'] ?>">
			<div class="fs-09 blue text-center">В формате "User:Password"</div>

		</div>

		<script>

			function checkConnection() {

				$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

				var str = 'action=channel.check&type=' + $('#type').val() + '&token=' + $('#token').val();
				var url = $('#Form').attr("action");

				$.post(url, str, function (data) {

					if (data.ok === true) {

						$('.rezult').html('Ответ: <b>Соединение установлено</b>');
						$('#name').val(data.result.username);
						$('#channel_id').val(data.result.id);
						$('#link').val('https://tlgg.ru/'+data.result.username+'?start=subscribe');

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
	public function check(array $params = []) {

		$proxy = $this -> proxy;

		return (new Telegram( $params['token'], true, $proxy )) -> getMe();

	}

	/**
	 * Срабатывает при установке вебхука
	 * Телеграм проверяет жизнеспособность ссылки
	 */
	public function callbackServerConfirmation() {

		$params = json_decode( file_get_contents( 'php://input' ), true );

		if ( empty( $params ) ) {

			print '100';
			exit();

		}

	}

	/**
	 * Установка вебхук
	 *
	 * @param array $params
	 * @return array - ответ
	 *              - str **status** - статус установки (ok - успешно, error - ошибка)
	 *              - str **message** - сообщение
	 */
	public function setWebhook(array $params = []): array {

		$api_key    = $this -> api_key;
		$serverhost = $this -> serverhost;
		$proxy      = $this -> proxy;

		$params['settings'] = !is_array( $params['channel']['settings'] ) ? json_decode( $params['channel']['settings'], true ) : $params['channel']['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		$url = $serverhost.'/plugins/socialChats/php/webhooks.php?channel=Telegram&api_key='.$api_key.'&botid='.$params['channel_id'].'';

		$telegram = new Telegram( $params['token'], true, $proxy );
		$res      = $telegram -> setWebhook( $url );

		return [
			"status"  => ($res['ok'] == 1) ? "ok" : "error",
			"message" => $res['description']
		];

	}

	/**
	 * Удаление вебхук
	 *
	 * @param array $params
	 *
	 * @return array - ответ
	 *              - str **status** - статус установки (ok - успешно, error - ошибка)
	 *              - str **message** - сообщение
	 */
	public function deleteWebhook(array $params = []): array {

		$api_key = $this -> api_key;
		$proxy   = $this -> proxy;

		$params['settings'] = !is_array( $params['channel']['settings'] ) ? json_decode( $params['channel']['settings'], true ) : $params['channel']['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		// регистрируем адрес в промежуточном скрипте
		$telegram = new Telegram( $params['token'], true, $proxy );
		$res      = $telegram -> deleteWebhook();

		return [
			"status"  => ($res['ok'] == 1) ? "ok" : "error",
			"message" => $res['description']
		];

	}

	/**
	 * Информация о канале (не используется)
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function getInfo(array $params = []) {

		$proxy = $this -> proxy;

		$params['settings'] = !is_array( $params['channel']['settings'] ) ? json_decode( $params['channel']['settings'], true ) : $params['channel']['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		return (new Telegram( $params['token'], true, $proxy )) -> getMe();

	}

	/**
	 * Отправляет сообщение
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendMessage(array $params = []): array {

		// todo: добавить возможность отправки изображений, документов

		$proxy = $this -> proxy;

		$params['settings'] = !is_array( $params['channel']['settings'] ) ? json_decode( $params['channel']['settings'], true ) : $params['channel']['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		$param = [
			"chat_id"             => $params['chat_id'],
			"text"                => $params['text'],
			"reply_to_message_id" => $params['reply_to_message_id'],
			"parse_mode"          => "HTML"
		];

		$telegram = new Telegram( $params['token'], true, $proxy );
		$res      = $telegram -> sendMessage( $param );

		//print_r($res);

		return [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $res['result']['message_id'],
			"error_code"  => $res['error_code'],
			"description" => $res['description']
		];

	}

	/**
	 * Отправка файлов
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendFile(array $params = []): array {

		$proxy    = $this -> proxy;
		$rootpath = $GLOBALS['rootpath'];

		$params['settings'] = !is_array( $params['channel']['settings'] ) ? json_decode( $params['channel']['settings'], true ) : $params['channel']['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		$param = [
			"chat_id" => $params['chat_id']
		];

		$telegram = new Telegram( $params['token'], true, $proxy );

		if ( !empty( $params['attachments']['photo'] ) ) {

			$f = $params['attachments']['photo'][0];

			$param['photo'] = curl_file_create( $rootpath.$f['url'], $f['type'], $f['title'] );
			$res            = $telegram -> sendPhoto( $param );

		}
		elseif ( !empty( $params['attachments']['doc'] ) ) {

			$f = $params['attachments']['doc'][0];

			$param['document'] = curl_file_create( $rootpath.$f['url'], $f['type'], $f['title'] );
			$res               = $telegram -> sendDocument( $param );

		}

		return [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $res['result']['message_id'],
			"error_code"  => $res['error_code'],
			"description" => $res['description']
		];

	}

	/**
	 * Метод для удаления сообщения с сервера
	 * Почему-то не отработало через прокси
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
	public function deleteMessage(array $message = []) {

		//print_r($message);

		$proxy = $this -> proxy;

		$params = $message['channel'];

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		$param = [
			"chat_id"    => $message['chat_id'],
			"message_id" => $message['message_id']
		];

		//print_r($param);

		$telegram = new Telegram( $params['token'], true, $proxy );
		$res      = $telegram -> deleteMessage( $param );

		//print_r($res);

		return [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $res['result']['message_id'],
			"error_code"  => $res['error_code'],
			"description" => $res['description']
		];

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
	public function getUserInfo(int $user_id = 0, array $params = []): array {

		$proxy    = $this -> proxy;
		$rootpath = $GLOBALS['rootpath'];

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		$token = $params['token'];

		$v   = new Telegram( $token, true, $proxy );
		$res = $v -> getUserProfilePhotos( ['user_id' => $user_id] );

		//print_r($res);

		$file_id = $res['result']['photos'][0][1]['file_id'];

		$url = $this -> getFileLink( $file_id, $params );

		$f = file_get_contents( $url );

		if ( !empty( $f ) ) {

			file_put_contents( $rootpath."/files/chatcash/{$user_id}.jpg", $f );
			$url = "/files/chatcash/{$user_id}.jpg";

		}
		else {
			$url = '';
		}

		if ( !empty( $res['error'] ) ) {

			$result = [
				"result"      => 'error',
				"ok"          => false,
				"description" => $res['error']['error_msg']
			];

		}
		else {

			$result = [
				"result"        => 'ok',
				"ok"            => true,
				'client_avatar' => $url,
			];

		}

		return $result;

	}

	/**
	 * Получение прямой ссылки на файл
	 *
	 * @param       $file_id
	 * @param array $params
	 * @return string
	 */
	public function getFileLink($file_id, array $params = []): string {

		$proxy = $this -> proxy;

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		if ( !empty( $params['settings']['proxy']['url'] ) ) {

			$proxy = $params['settings']['proxy'];
			$proxy["type"] = CURLPROXY_SOCKS5;

		}

		$token = $params['token'];

		$v   = new Telegram( $token, true, $proxy );
		$res = $v -> getFile( $file_id );

		$file_path = $res['result']['file_path'];

		return "https://api.telegram.org/file/bot{$token}/{$file_path}";

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
	public function eventFilter(array $params = [], array $channel = []) {

		$rootpath = $GLOBALS['rootpath'];

		$message     = [];
		$attachments = [];
		$type        = 'text';

		if ( !empty( $params['message'] ) ) {

			$message['chat_id']    = $params['message']['chat']['id'];
			$message['message_id'] = $params['message']['message_id'];
			$message['text']       = $params['message']['text'];

			$message['direction']        = "in";
			$message['client_firstname'] = $params['message']['from']['first_name'];
			$message['client_lastname']  = $params['message']['from']['last_name'];
			$message['client_id']        = $params['message']['from']['id'];
			//$message['client_avatar']    = $params['sender']['avatar'];

			if ( !empty( $params['message']['photo'] ) ) {

				$type        = 'photo';
				$attachments = $params['message']['photo'];

			}
			elseif ( !empty( $params['message']['document'] ) ) {

				$type        = 'doc';
				$attachments = $params['message']['document'];

			}

		}

		/**
		 * Телеграм присылает каждое вложение как отдельное письмо
		 */
		switch ($type) {

			case "photo":

				$file_id = $attachments[ (count( $attachments ) - 1) ]['file_id'];
				$url     = $this -> getFileLink( $file_id, $channel );

				$filename = md5( basename( $url ) ).".".getExtention( basename( $url ) );

				// сохраним файл в кэше, т.к. к серверу телеграм хер подключишься
				$fc = file_get_contents( $url );
				file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

				$message['attachment']['photo'][] = [
					"url"     => "/files/chatcash/files/".$filename,
					"title"   => basename( $url ),
					"preview" => $url,
					"icon"    => 'icon-file-image yelw',
				];

			break;
			case "doc":

				$file_id = $attachments['file_id'];
				$url     = $this -> getFileLink( $file_id, $channel );

				$filename = md5( basename( $url ) ).".".getExtention( basename( $url ) );

				// сохраним файл в кэше, т.к. к серверу телеграм хер подключишься
				$fc = file_get_contents( $url );
				file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

				$message['attachment']['doc'][] = [
					"url"   => "/files/chatcash/files/".$filename,
					"title" => $attachments['file_name'],
					"size"  => $attachments['file_size'],
					"ext"   => getExtention( $attachments['file_name'] ),
					"icon"  => get_icon3( $attachments['file_name'] ),
				];

			break;

		}

		// находим чат
		$ch   = new Chats();
		$chat = $ch -> chatInfo( 0, $message['chat_id'] );

		// если чат не наден, то добавляем его
		if ( empty( $chat ) ) {

			$user = $this -> getUserInfo( $message['chat_id'], $channel );

			if ( $user['result'] == 'ok' ) {

				$message['client_avatar'] = $user['client_avatar'];

			}

			$message['type'] = 'telegram';

		}

		$message['event'] = "newMessage";

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
	public function chatTransfer(string $chat_id = '', array $params = [], int $iduser = 0){

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
	public function chatInvite(string $chat_id = '', array $params = [], int $iduser = 0){

		return true;

	}

}