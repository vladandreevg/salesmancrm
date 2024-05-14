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

use Viber;

/**
 * Класс-провайдер для работы с Viber
 *
 * Class ViberProvider
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class ViberProvider {

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
			"name"      => "viber",
			"title"     => "Viber",
			"messenger" => "Viber",
			"channel"   => "Viber",
			"icon"      => "viber.png"
		];

	}

	/**
	 * Форма настроек для Провайдера
	 *
	 * @param int $id - id записи канала
	 */
	public static function settingsForm($id = 0) {

		$channel = Chats ::channelsInfo( (int)$id );

		if ( $channel['settings']['link'] == '' ) {
			$channel['settings']['link'] = 'viber://pa?chatURI=ИМЯ_БОТА&context=subscribe';
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

		<div class="rezult p10 div-center mt10"></div>

		<div class="divider wp100 mt10">Данные, заполняемые после проверки</div>

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
			<input type="text" name="link" id="link" class="wp100" value="<?= $channel['settings']['link'] ?>">
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
						$('#link').val('viber://pa?chatURI=' + data.result.link + '&context=subscribe');

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

		$rootpath = dirname( __DIR__, 5 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		//$params[ 'token' ];

		$viber = new Viber( $params['token'] );
		$viber -> botInfo();

		$res = json_decode( $viber -> answer, true );
		
		//print_r($viber);
		//print_r($res);

		$result['ok']                 = $res['status_message'] == 'ok';
		$result['message']            = $res['status'] > 0 ? $res['status_message'] : '';
		$result['result']['id']       = $res['id'];
		$result['result']['username'] = $res['name'];
		$result['result']['link']     = $res['uri'];

		return $result;

	}

	/**
	 * Срабатывает при установке вебхука
	 * Вайбер проверяет жизнеспособность ссылки
	 */
	public function callbackServerConfirmation() {

		$params = json_decode( file_get_contents( 'php://input' ), true );

		$string = is_array( $params ) ? array2string( $params ) : $params;
		file_put_contents( $GLOBALS['rootpath'].'/cash/sch-webhooks.log', current_datumtime()."\nFLTR_IN\n".$string."\n\n", FILE_APPEND );

		print 'ok';

		// если это установка вебхук, то завершаем соединение
		if ( $params['event'] == 'webhook' ) {
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

		// ссылка содержит следующие параметры после /webhooks/:
		//  - название провайдера
		//  - ключ API от CRM
		//  - имя бота или чата
		$url = $serverhost."/plugins/socialChats/php/webhooks/Viber/$api_key/".$params['channel_id']."/";

		$viber = new Viber( $params['token'] );
		$viber -> setWebhook( $url );

		$res = json_decode( $viber -> answer, true );

		//print_r($res);

		return [
			"status"  => ($res['status'] == 'ok') ? "ok" : "error",
			"message" => $res['message'],
			"viber"   => $res
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

		$viber = new Viber( $params['token'] );
		$viber -> deleteWebhook();

		$res = json_decode( $viber -> answer, true );

		return [
			"status"  => ($res['status_message'] == 'ok') ? "ok" : "error",
			"message" => $res['status_message']
		];

	}

	/**
	 * Информация о канале (не используется)
	 *
	 * @param array $params
	 * @return mixed
	 */
	public function getInfo(array $params = []) {

		$viber = new Viber( $params['token'] );
		$viber -> botInfo();

		return json_decode( $viber -> answer, true );

	}

	/**
	 * Информация о посетителе
	 *
	 * @param int   $user_id
	 * @param array $params
	 * @return array
	 */
	public function getUserInfo($user_id = 0, array $params = []): array {

		$viber = new Viber( $params['token'] );
		$viber -> getUserInfo( $user_id );

		$res = json_decode( $viber -> answer, true );

		//print_r($viber);
		//print_r($res);

		if ( !empty( $res['user'] ) ) {

			$result = [
				"result"      => 'error',
				"ok"          => false,
				"description" => $res['status_message']
			];

		}
		else {

			$result = [
				"ok"               => true,
				'chat_id'          => $res['user']['id'],
				'client_id'        => $res['user']['id'],
				'client_firstname' => $res['user']['name'],
				'client_lastname'  => $res['user']['last_name'],
				'client_avatar'    => $res['user']['avatar'],
			];

		}

		//print_r($result);

		return $result;

	}

	/**
	 * Отправка сообщения
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendMessage(array $params = []): array {

		$viber = new Viber( $params['token'] );
		$viber -> sendMessage( $params['parse_mode'], $params['text'], $params['client_id'], [
			"name"   => $params['username'],
			'avatar' => $this -> serverhost.$params['useravatar']
		] );

		$res = json_decode( $viber -> answer, true );

		//print_r($res);

		return [
			"result"      => $res['status'] == 0 ? 'ok' : 'error',
			"message_id"  => $res['message_token'],
			"error_code"  => $res['status'],
			"description" => $res['status_message'],
			"payload"     => $res
		];

	}

	/**
	 * Отправка файла
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendFile(array $params = []): array {

		$params['settings'] = !is_array( $params['channel']['settings'] ) ? json_decode( $params['channel']['settings'], true ) : $params['channel']['settings'];

		$viber = new Viber( $params['token'] );

		$res = [];

		if ( !empty( $params['attachments']['photo'] ) ) {

			$f = $params['attachments']['photo'][0];

			/**
			 * Вайбер принимает только JPG
			 * Поэтому создаем временный файл
			 */
			$f['url']  = (new Chats) -> convertImage( $f['url'], 'jpg' );
			$f['name'] = '';
			$f['type'] = "image/jpg";

			$ext = getExtention( $f['name'] );

			if ( $ext != 'jpg' ) {
				$f['name'] = changeFileExt( texttosmall( $f['name'] ), "jpg" );
			}

			$viber -> sendMessage( 'picture', $params['text'], $params['chat_id'], [
				"name"   => $params['username'],
				'avatar' => $this -> serverhost.$params['useravatar']
			], [
				"media"     => $this -> serverhost.$f['url'],
				"file_name" => $f['title'],
				"size"      => $f['size']
			] );

			$res = json_decode( $viber -> answer, true );

			// удаляем временный файл
			unlink( $f['url'] );

		}
		elseif ( !empty( $params['attachments']['doc'] ) ) {

			$f = $params['attachments']['doc'][0];

			$viber -> sendMessage( 'file', $params['text'], $params['chat_id'], [
				"name"   => $params['username'],
				'avatar' => $this -> serverhost.$params['useravatar']
			], [
				"media"     => $this -> serverhost.$f['url'],
				"file_name" => $f['title'],
				"size"      => $f['size']
			] );

			$res = json_decode( $viber -> answer, true );

		}

		//print_r($res);

		return [
			"result"      => $res['status'] == 0 ? 'ok' : 'error',
			"message_id"  => $res['message_token'],
			"error_code"  => $res['status'],
			"description" => $res['status_message']
		];

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
	 * @return bool
	 */
	public function deleteMessage($message = []) {

		return true;

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

		$event    = $params['event'];
		$rootpath = $GLOBALS['rootpath'];

		$message     = [];
		$attachments = [];

		if ( !empty( $params['sender'] ) ) {

			$message['chat_id']          = $params['sender']['id'];
			$message['message_id']       = $params['message_token'];
			$message['text']             = $params['message']['text'];
			$message['direction']        = "in";
			$message['client_firstname'] = $params['sender']['name'];
			$message['client_lastname']  = "";
			$message['client_avatar']    = $params['sender']['avatar'];

		}

		if ( !empty( $params['message']['media'] ) ) {

			// todo: надо сохранять файл на своём сервере или загружать динамически
			$filename = $params['message']['file_name'];

			$filename = md5( $filename ).".".getExtention( $filename );

			// сохраним файл в кэше, т.к. он хранится всего час
			$fc = file_get_contents( $params['message']['media'] );
			file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

			if ( $params['message']['type'] == 'picture' ) {

				$message['attachment']['photo'][] = [
					"url"     => "/files/chatcash/files/".$filename,
					"title"   => $params['message']['file_name'],
					"preview" => $params['message']['thumbnail'],
					"icon"    => 'icon-file-image yelw',
				];

			}
			if ( $params['message']['type'] == 'file' ) {

				$message['attachment']['doc'][] = [
					"url"   => "/files/chatcash/files/".$filename,
					"title" => $params['message']['file_name'],
					"icon"  => get_icon3( $filename ),
					"ext"   => getExtention( $filename ),
				];

			}

		}

		// находим чат
		$ch   = new Chats();
		$chat = $ch -> chatInfo( 0, $message['chat_id'] );

		switch ($event) {

			case "message":

				if ( !empty( $message['attachment'] ) || $message['text'] != '' )
					$message['event'] = "newMessage";

				if ( empty( $chat ) ) {

					$message['type'] = 'viber';

				}

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

}