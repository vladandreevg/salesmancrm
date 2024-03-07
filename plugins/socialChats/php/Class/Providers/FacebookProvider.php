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

use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;

/**
 * Класс-провайдер для работы с Facebook Messenger
 *
 * Class FacebookProvider
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class FacebookProvider {

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
	public $webhookurl = '';

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 *
	 * @param string $channel_id
	 */
	public function __construct( $channel_id = '') {

		global $api_key;

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

		$this -> api_key    = $api_key = $GLOBALS['db'] -> getOne( "SELECT api_key FROM ".$GLOBALS['sqlname']."settings WHERE id = '$GLOBALS[identity]'" );
		$scheme             = isset( $_SERVER['HTTP_SCHEME'] ) ? $_SERVER['HTTP_SCHEME'] : (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
		$this -> serverhost = $scheme.$_SERVER["HTTP_HOST"];

		$this -> webhookurl = $this -> serverhost.'/plugins/socialChats/php/webhooks/Facebook/'.$this -> api_key.'/'.$channel_id.'/';

		// тут почему-то не срабатывает
		if ( !empty( $params ) )
			foreach ( $params as $key => $val )
				$this ->{$key} = $val;

		date_default_timezone_set( $this -> tmzone );

	}

	public static function providerName() {

		return [
			"name"      => "facebook",
			"title"     => "Facebook",
			"messenger" => "Facebook",
			"channel"   => "Facebook Messenger",
			"icon"      => "facebook.png"
		];

	}

	/**
	 * Форма настроек для Провайдера
	 * https://qna.habr.com/q/541971 - хорошо пояснили как эта ебатня работает (чоб ты сдох МЦ)
	 *
	 * @param int $id - id записи канала
	 */
	public static function settingsForm($id = 0) {

		$api_key = $GLOBALS['db'] -> getOne( "SELECT api_key FROM ".$GLOBALS['sqlname']."settings WHERE id = '$GLOBALS[identity]'" );

		$scheme     = isset( $_SERVER['HTTP_SCHEME'] ) ? $_SERVER['HTTP_SCHEME'] : (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://');
		$serverhost = $scheme.$_SERVER["HTTP_HOST"];

		$channel = Chats ::channelsInfo( $id );

		if ( !isset( $channel['settings']['verify_token'] ) || $channel['settings']['verify_token'] == '' )
			$channel['settings']['verify_token'] = Chats ::genkey( 1, 25 );

		if ( !isset( $channel['settings']['channel_id'] ) )
			$channel['settings']['channel_id'] = '{ID страницы}';

		if ( !isset( $channel['name'] ) )
			$channel['name'] = 'Страница в Facebook';

		if($channel['settings'][ 'link' ] == '')
			$channel['settings'][ 'link' ] = 'https://m.me/ID_страницы?ref=subscribe';

		?>
		<div class="column grid-10">

			<div class="infodiv bgwhite">

				<div class="mb10 hidden">Зарегистрируйте приложение в
					<a href="https://developers.facebook.com/apps/" target="_blank" title="Мои приложения">Facebook</a> и укажите в форме.
				</div>

				<div class="Bold">Ссылка для Webhook:</div>
				<div>
					<code class="webhook"><?php echo $serverhost.'/plugins/socialChats/php/webhooks/Facebook/'.$api_key.'/'.$channel['settings']['channel_id'].'/' ?></code>
				</div>

			</div>

		</div>

		<div class="column grid-10 relative">
			<span class="label">ID страницы</span>
			<input type="text" name="channel_id" id="channel_id" class="wp100 required" value="<?= $channel['settings']['channel_id'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Токен (маркер) подтверждения</span>
			<input type="text" name="verify_token" id="verify_token" class="wp100" value="<?= $channel['settings']['verify_token'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">ID приложения</span>
			<input type="text" name="app_id" id="app_id" class="wp100 required" value="<?= $channel['settings']['app_id'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Секретный код приложения</span>
			<input type="text" name="app_secret" id="app_secret" class="wp100" value="<?= $channel['settings']['app_secret'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Токен страницы (Маркер страницы)</span>
			<textarea type="text" name="token" id="token" class="wp100"><?= $channel['token'] ?></textarea>
		</div>

		<div class="column grid-10 text-center">

			<a href="javascript:void(0)" onclick="preSave()" title="Проверить" class="button orangebtn fs-09 ptb5lr15"><i class="icon-ok"></i>Сохранить</a>
			<a href="javascript:void(0)" onclick="checkConnection()" title="Проверить" class="button fs-09 ptb5lr15"><i class="icon-ok"></i>Проверить</a>

		</div>

		<div class="divider mt20">Данные, заполняемые после проверки</div>

		<div class="column grid-10 relative">
			<span class="label">Имя страницы:</span>
			<input type="text" name="name" id="name" class="wp100" value="<?= $channel['name'] ?>">
		</div>

		<div class="column grid-10 relative">
			<span class="label">Ссылка:</span>
			<input type="text" name="link" id="link" class="wp100" value="<?= $channel['settings'][ 'link' ] ?>">
		</div>

		<script>

			$('#token').autoHeight('100', 2);

			$(document).off('change keyup', '#channel_id');
			$(document).on('change keyup', '#channel_id', function () {

				let id = $(this).val();
				let url = '<?php echo $serverhost.'/plugins/socialChats/php/webhooks/Facebook/'.$api_key.'/'?>' + id + '/';

				$('.webhook').html(url);
				$('#link').val('https://m.me/'+id+'?ref=subscribe');

			});

			function preSave() {

				$('.rezult').append('<div id="loader"><img src="/assets/images/loading.svg"></div>');

				var str = 'action=channel.edit.do&name=Facebook&type=' + $('#type').val() + '&app_id=' + $('#app_id').val() + '&app_secret=' + $('#app_secret').val() + '&token=' + $('#token').val() + '&channel_id=' + $('#channel_id').val() + '&id=' + parseInt($('#id').val()) + '&verify_token=' + $('#verify_token').val();
				var url = $('#Form').attr("action");

				$.post(url, str, function (data) {

					if (data.id > 0) {

						$('#id').val(data.id);

						Swal.fire({
							title: 'Сохранено',
							html: 'Теперь можно настроить Webhook',
							type: 'success',
							showConfirmButton: false,
							timer: 3500
						});

						$app.loadChannels();

					}

				}, 'json');

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

						$('#name').val(data.name);
						$('#link').val(data.link);

					}
					else Swal.fire({
						title: 'Ошибка',
						html: data.message,
						type: 'error',
						showConfirmButton: false,
						timer: 6500
					});

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
	public function check($params = []) {

		$res = [];

		//print_r($params);

		$appID     = $params['app_id'];
		$appSecret = $params['app_secret'];
		$pageToken = $params['token'];

		try {

			$fb = new Facebook( [
				'app_id'                => $appID,
				'app_secret'            => $appSecret,
				'default_graph_version' => 'v5.0',
				//'default_access_token' => '{access-token}', // optional
			] );

			// Returns a `FacebookFacebookResponse` object
			$response = $fb -> get( "/me", $pageToken );

			//print_r($response);

			$res['response']['id']   = $response -> getGraphUser() -> getId();
			$res['response']['name'] = $response -> getGraphUser() -> getName();

		}
		catch ( FacebookSDKException $e ) {

			$res['error']['error_msg'] = 'Facebook SDK returned an error: '.$e -> getMessage();

		}

		if ( !empty( $res['response'] ) )
			$result = [
				"ok"         => true,
				"channel_id" => $res['response']['id'],
				"name"       => $res['response']['name'],
				"link"       => $res['response']['id'],
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

		$db      = $GLOBALS['db'];
		$sqlname = $GLOBALS['sqlname'];

		$channel  = $db -> getRow( "SELECT * FROM ".$sqlname."chats_channels WHERE channel_id = '$_REQUEST[botid]'" );
		$settings = json_decode( $channel['settings'], true );

		$hub_mode     = $_REQUEST['hub_mode'];
		$challenge    = $_REQUEST['hub_challenge'];
		$verify_token = $_REQUEST['hub_verify_token'];

		// подтверждение webhook в момент его установки
		// в других случаях этот блок не нужен
		if ( $verify_token == $settings['verify_token'] && $hub_mode == 'subscribe' ) {

			header( 'Content-Type: text/plain' );
			print $challenge;

			exit();

		}

	}

	/**
	 * Установка вебхук. Для Facebook не работает
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 * @return array
	 */
	public function setWebhook($params = []) {

		return [
			"status"  => "ok",
			"message" => "Успешно"
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

		return [
			"status"  => "ok",
			"message" => "Callback-сервер удаляется вручную в настроках приложения"
		];

	}

	/**
	 * Информация о канале (не используется)
	 *
	 * @param array $params
	 *
	 * @return mixed
	 */
	public function getInfo($params = []) {

		return [];

	}

	/**
	 * Отправляет сохраненное сообщение
	 * https://developers.facebook.com/docs/messenger-platform/reference/send-api
	 *
	 * @param array $params - данные из метода $chat -> channelInfo()
	 *                      - str **text** - текст сообщения
	 *
	 * @return array
	 */
	public function sendMessage($params = []) {

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$appID      = $params['settings']['app_id'];
		$appSecret  = $params['settings']['app_secret'];
		$pageToken  = $params['token'];
		$channel_id = $params['channel_id'];
		$user_id    = $params['chat_id'];

		// todo: добавить возможность отправки изображений, документов

		$message_id = 0;
		$res['ok']  = false;

		try {

			$fb = new Facebook( [
				'app_id'                => $appID,
				'app_secret'            => $appSecret,
				'default_graph_version' => 'v5.0',
				//'default_access_token' => '{access-token}', // optional
			] );

			// https://askvoprosy.com/voprosy/send-private-messages-to-facebook-page-followers-php-sdk
			$post = [
				"recipient"    => ["id" => $user_id],
				"message"      => ["text" => $params['text']],
				"access_token" => $pageToken
			];

			$response = $fb -> post( "/me/messages", $post, $pageToken );

			$message = $response -> getDecodedBody();

			$message_id = $message['message_id'];

			$res['ok'] = ($message_id != '') ? true : false;

			//$string = is_array($response) ? array2string($response) : $response;
			//file_put_contents($GLOBALS['rootpath'].'/cash/sch-webhooks.log', current_datumtime()."\nFBresp\n".$string."\n\n", FILE_APPEND);

			//print_r($response);

		}
		catch ( FacebookSDKException $e ) {

			$res['response']['code']  = $e -> getCode();
			$res['response']['error'] = 'Facebook SDK returned an error: '.$e -> getMessage();

		}

		//$string = is_array($res) ? array2string($res) : $res;
		//file_put_contents($GLOBALS['rootpath'].'/cash/sch-webhooks.log', current_datumtime()."\nFB\n".$string."\n\n", FILE_APPEND);

		$result = [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res['response']['code'],
			"description" => $res['response']['error']
		];

		return $result;

	}

	/**
	 * Отправка файлов осуществляется в 2 этапа
	 * 1. загружаем файл на сервер и получаем его id
	 * 2. отправляем как сообщение с вложением (указываем id вложения)
	 * FB API не дает задать имя файла, поэтому создаем временный файл в транслите, а после отправки удаляем его
	 * https://developers.facebook.com/docs/messenger-platform/reference/attachment-upload-api
	 *
	 * @param array $params
	 * @return array
	 */
	public function sendFile($params = []) {

		$rootpath = $GLOBALS['rootpath'];

		$appID      = $params['settings']['app_id'];
		$appSecret  = $params['settings']['app_secret'];
		$pageToken  = $params['token'];
		$channel_id = $params['channel_id'];
		$user_id    = $params['chat_id'];

		//print_r($params);

		$res             = [];
		$attachment_id   = '';
		$attachment_type = '';
		$message_id      = 0;

		try {

			$fb = new Facebook( [
				'app_id'                => $appID,
				'app_secret'            => $appSecret,
				'default_graph_version' => 'v5.0',
			] );

			if ( !empty( $params['attachments']['photo'] ) ) {

				$f = $params['attachments']['photo'][0];

				// создаем временный файл
				$filename = translit(str_replace(" ", "_", $f['title']));
				$fc = file_get_contents( $rootpath."/".$f['url'] );
				file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

				$post = [
					"message" => [
						"attachment" => [
							"type"    => "image",
							"payload" => [
								"is_reusable" => false,
								"url"         => $this -> serverhost."/files/chatcash/files/".$filename
							]
						]
					]
				];

				//print_r($post);

				$response = $fb -> post( "/me/message_attachments", $post, $pageToken );

				//print_r($response);

				$graphNode = $response -> getGraphNode();

				$attachment_id   = $graphNode['attachment_id'];
				$attachment_type = 'image';

				// удаляем временный файл
				unlink($rootpath."/files/chatcash/files/".$filename);

			}
			if ( !empty( $params['attachments']['doc'] ) ) {

				$f = $params['attachments']['doc'][0];

				// создаем временный файл
				$filename = translit(str_replace(" ", "_", $f['title']));
				$fc = file_get_contents( $rootpath."/".$f['url'] );
				file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

				$post = [
					"message" => [
						"attachment" => [
							"type"    => "file",
							"payload" => [
								"is_reusable" => false,
								"url"         => $this -> serverhost."/files/chatcash/files/".$filename
							]
						]
					]
				];

				//print_r($post);

				$response = $fb -> post( "/me/message_attachments", $post, $pageToken );

				//print_r($response);

				$graphNode = $response -> getDecodedBody();

				//print_r($graphNode);

				$attachment_id   = $graphNode['attachment_id'];
				$attachment_type = 'file';

				// удаляем временный файл
				unlink($rootpath."/files/chatcash/files/".$filename);

			}

			if ( $attachment_id != '' ) {

				$post     = [
					"recipient" => ["id" => $user_id],
					"message"   => [
						"attachment" => [
							"type"    => $attachment_type,
							"payload" => [
								"attachment_id" => $attachment_id
							]
						]
					]
				];
				$response = $fb -> post( "/me/messages", $post, $pageToken );

				//print_r($response);

				$message = $response -> getDecodedBody();

				$message_id = $message['message_id'];

				$res['ok'] = ($message_id != '') ? true : false;

			}

		}
		catch ( FacebookSDKException $e ) {

			$res['response']['code']  = $e -> getCode();
			$res['response']['error'] = 'Facebook SDK returned an error: '.$e -> getMessage();

		}

		$result = [
			"result"      => $res['ok'] ? 'ok' : 'error',
			"message_id"  => $message_id,
			"error_code"  => $res['response']['code'],
			"description" => $res['response']['error']
		];

		return $result;

	}

	/**
	 * Метод для удаления сообщения с сервера
	 * TODO: проверить работу
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

		$appID     = $params['settings']['app_id'];
		$appSecret = $params['settings']['app_secret'];
		$pageToken = $params['token'];

		try {

			$fb = new Facebook( [
				'app_id'                => $appID,
				'app_secret'            => $appSecret,
				'default_graph_version' => 'v5.0',
			] );

			$response = $fb -> delete( "/$message[message_id]", $pageToken );

			//print_r( $response );

			$result = [
				"result"      => $response['ok'] ? 'ok' : 'error',
				"message_id"  => $response['result']['message_id'],
				"error_code"  => $response['error_code'],
				"description" => $response['description']
			];

		}
		catch ( FacebookSDKException $e ) {

			$result = [
				"result"      => 'error',
				"ok"          => false,
				"description" => $e -> getMessage()
			];

		}

		$message_id = 0;
		$res['ok']  = false;

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

		$res['ok'] = false;

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

		return $result = [];

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

		$rootpath = $GLOBALS['rootpath'];

		// Возвращает расширенную информацию о пользователях.

		$params['settings'] = !is_array( $params['settings'] ) ? json_decode( $params['settings'], true ) : $params['settings'];

		//print_r($params);

		$appID     = $params['settings']['app_id'];
		$appSecret = $params['settings']['app_secret'];
		$pageToken = $params['token'];

		try {

			$fb = new Facebook( [
				'app_id'                => $appID,
				'app_secret'            => $appSecret,
				'default_graph_version' => 'v5.0',
			] );

			$response = $fb -> get( "/$user_id", $pageToken );

			//print_r($response);

			// как-то не понятно возвращает то полную инфу, то только имя и id
			$profile = $response -> getGraphUser() -> getName();

			$first_name = yexplode( " ", $profile, 0 );
			$last_name  = yexplode( " ", $profile, 1 );
			$avatar     = NULL;

			if ( $profile == '' ) {

				$first_name = $response -> getGraphUser() -> getFirstName();
				$last_name  = $response -> getGraphUser() -> getLastName();
				$avatar     = $response -> getGraphUser() -> getField( 'profile_pic' );

				$r = parse_url( $avatar );
				$origfilename = urldecode( basename( $r['path'] ) );

				$r = parse_url( $origfilename );
				$origfilename = basename( $r['path'] );

				$filename = md5( $origfilename.$first_name ).".jpg";

				$f = file_get_contents($avatar);
				file_put_contents($rootpath."/files/chatcash/".$filename,$f);

				$avatar = "/files/chatcash/$filename";

			}

			$result = [
				"ok"      => true,
				'chat_id'          => $user_id,
				'client_id'        => $user_id,
				'client_firstname' => $first_name,
				'client_lastname'  => $last_name,
				'client_avatar'    => $avatar
			];

		}
		catch ( FacebookSDKException $e ) {

			$result = [
				"result"      => 'error',
				"ok"          => false,
				"description" => $e -> getMessage()
			];

		}

		return $result;

	}

	/**
	 * Обработка входящих сообщений
	 * Метод должен вернуть данные в формате плагина
	 * https://developers.facebook.com/docs/messenger-platform/reference/webhook-events/messages
	 *
	 * @param array $params - пришедшие на вебхук данные
	 *                      - channel - вся информация из настроек канала
	 * @param array $channel
	 * @return array
	 */
	public function eventFilter($params = [], $channel = []) {

		$rootpath = $GLOBALS['rootpath'];

		$message = [];

		// тестовое сообщение от FB пришло завернутым в массив
		if ( isset( $params['entry'] ) ) {

			$params = $params['entry'][0]['messaging'][0];

		}

		if ( !isset( $params['delivery'] ) ) {

			$message['message_id'] = $params['message']['mid'];
			$message['chat_id']    = $params['sender']['id'];
			$message['text']       = $params['message']['text'];
			$message['direction']  = $params['recipient']['id'] == $channel['channel_id'] ? "in" : "out";
			$attachments           = $params['message']['attachments'];

			foreach ( $attachments as $attach ) {

				$r            = parse_url( $attach['payload']['url'] );
				$origfilename = urldecode( basename( $r['path'] ) );

				$filename = md5( $origfilename ).".".getExtention( $origfilename );

				$fc = file_get_contents( $attach['payload']['url'] );
				file_put_contents( $rootpath."/files/chatcash/files/".$filename, $fc );

				switch ($attach['type']) {

					case "file":

						$message['attachment']['doc'][] = [
							"url"   => "/files/chatcash/files/".$filename,
							"title" => $origfilename,
							"ext"   => getExtention( $filename ),
							"icon"  => get_icon3( $filename ),
						];

					break;
					case "image":

						$message['attachment']['photo'][] = [
							"url"   => "/files/chatcash/files/".$filename,
							"title" => "Image",
							"ext"   => getExtention( $filename ),
							"icon"  => 'icon-file-image yelw',
						];

					break;
					case "fallback":

						$message['attachment']['doc'][] = [
							"url"   => $attach['url'],
							"title" => $attach['title'],
							"icon"  => get_icon3( $attach['title'] ),
						];

					break;
					case "link":
					case "video":

					break;

				}

			}

			// находим чат
			$ch   = new Chats();
			$chat = $ch -> chatInfo( 0, $message['chat_id'] );


			// если чат не наден, то добавляем его
			if ( empty( $chat ) ) {

				// запрашиваем данные пользователя
				$user = $this -> getUserInfo($message['chat_id'], $channel );

				$message['client_firstname'] = $user['client_firstname'];
				$message['client_lastname']  = $user['client_lastname'];
				$message['client_avatar']    = $user['client_avatar'];

				$message['type'] = 'facebook';

			}

			if($message['text'] != '' || !empty($message['attachment']))
				$message['event'] = "newMessage";

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