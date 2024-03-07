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

use andreskrey\Readability\Configuration;
use andreskrey\Readability\ParseException;
use andreskrey\Readability\Readability;
use Exception;
use RuntimeException;
use SafeMySQL;
use Salesman\Client;
use Salesman\Person;
use Salesman\Upload;

/**
 * Класс для управления чатами
 *
 * Class Chats
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (21/11/2019)
 */
class Chats {

	//перенесено в метод channelsProvider()
	/*
	const CHANNELS = [
		"Telegram"     => "Telegram",
		"Viber"        => "Viber",
		"Facebook"     => "Facebook Messenger",
		"Vk"           => "VK.com",
		//"Yandex"   => "Яндекс.Диалоги",
		//"Watsapp"  => "Whatsapp",
		"Chatapi"      => "Whatsapp Сhat API",
		"ApiMessenger" => "API Messenger",
	];
	*/

	public const STATUS = [
		"free"    => "Свободный",
		"inwork"  => "В работе",
		"archive" => "В архиве",
		"blocked" => "Заблокирован"
	];

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

	/**
	 * Настройки логики
	 *
	 * @var array
	 */
	public $settings = [];

	/**
	 * Фильтры для списка чатов
	 *
	 * @var array
	 */
	public $filters = [];

	public $error;
	/**
	 * @var false|string
	 */
	private $rootpath;

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 4 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> rootpath = $rootpath;
		$this -> settings = customSettings( "socialChatsSettings", "get" );

		$this -> db = new SafeMySQL( $this -> opts );

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		$this -> params = ["silence" => false];

		date_default_timezone_set( $this -> tmzone );

		createDir( $rootpath."/files/".($this -> fpath)."chatcash/" );

	}

	/**
	 * Список каналов
	 *
	 * @param int    $id
	 * @param string $name
	 *
	 * @return array
	 */
	public static function channelsInfo(int $id = 0, string $name = ''): array {

		$rootpath = dirname(__DIR__, 4);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$list = [];

		if ( $id != '' ) {
			$list = $db -> getRow("select * from ".$sqlname."chats_channels WHERE id = '$id'");
		}
		elseif ( $name != '' ) {
			$list = $db -> getRow("select * from ".$sqlname."chats_channels WHERE name = '$name'");
		}

		for ( $i = 0; $i < 20; $i++ ) {
			unset( $list[ $i ] );
		}

		$list['settings'] = json_decode( $list['settings'], true );

		return $list;

	}

	/**
	 * Иконки каналов
	 *
	 * @param int    $id
	 * @param string $type
	 *
	 * @return mixed
	 */
	public static function channelsIcon(int $id = 0, string $type = '') {

		$rootpath = dirname( __DIR__, 4 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$icons = [];

		$list = self ::channelsProvider();

		foreach ( $list as $provider ) {

			$icons[ strtolower( $provider['messenger'] ) ] = $provider['icon'];

		}

		if ( $id > 0 ) {
			$type = $db -> getOne( "SELECT type FROM ".$sqlname."chats_channels WHERE id = '$id'" );
		}

		if ( $type != '' ) {
			return $icons[ strtolower( $type ) ];
		}

		return $icons;

	}

	/**
	 * Сбор сведений о подключенных провайдерах
	 *
	 * @return array
	 */
	public static function channelsProvider(): array {

		$rootpath = dirname( __DIR__, 4 );

		require_once $rootpath."/plugins/socialChats/php/autoload.php";

		$dir  = "plugins/socialChats/php/Class/Providers/";
		$list = [];

		$files = getDirFiles( $dir );

		foreach ( $files as $file ) {

			if ( $file != '' && $file != 'ExampleProvider.php' ) {

				require_once $rootpath."/plugins/socialChats/php/Class/Providers/$file";

				$provider = str_replace( ".php", "", $file );

				$type = "Chats\\".$provider;

				$prvdrClass = new $type();

				$prvdr = $prvdrClass -> providerName();

				$list[ $prvdr['name'] ] = $prvdr;

			}

		}

		return $list;

	}

	/**
	 * Отправка запросов через cURL
	 *
	 * @param      $url
	 * @param      $params
	 * @param bool $convert
	 * @return bool|string
	 */
	public static function outSender($url, $params, bool $convert = true) {

		$POST = ($convert) ? http_build_query( $params ) : $params;

		$ch = curl_init();// Устанавливаем соединение
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $POST );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
		curl_setopt( $ch, CURLOPT_URL, $url );

		$result = curl_exec( $ch );

		if ( $result === false ) {
			return curl_error( $ch );
		}

		return $result;

	}

	/**
	 * Генератор случайный цветов для фона в пастельных тонах с привязкой к словосочетанию
	 *
	 * @param $name
	 * @return string
	 */
	public static function getColor($name): string {

		$hash = md5( $name );

		$color1 = hexdec( substr( $hash, 8, 2 ) );
		$color2 = hexdec( substr( $hash, 4, 2 ) );
		$color3 = hexdec( substr( $hash, 0, 2 ) );

		if ( $color1 < 155 ) {
			$color1 += 100;
		}
		if ( $color2 < 155 ) {
			$color2 += 100;
		}
		if ( $color3 < 155 ) {
			$color3 += 100;
		}

		return "#".dechex( $color1 ).dechex( $color2 ).dechex( $color3 );

	}

	/**
	 * Сервисная функция для генерации рандомных идентификаторов
	 *
	 * @param int $nabor
	 * @param int $max
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public static function genkey(int $nabor = 1, int $max = 10): ?string {

		if ( $nabor == 0 ) {
			$chars = "1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		}
		elseif ( $nabor == 1 ) {
			$chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";
		}
		elseif ( $nabor == 2 ) {
			$chars = "AZXSWEDCVFRTGBNHYUJMKIOLP";
		}
		elseif ( $nabor == 3 ) {
			$chars = "qazxswedcvfrtgbnhyujmkiolp";
		}
		else {
			$chars = "1234567890";
		}

		$size = StrLen( $chars ) - 1;
		$key  = NULL;

		while ($max--) {
			$key .= $chars[ random_int( 0, $size ) ];
		}

		return $key;

	}

	/**
	 * Находим ссылки в сообщении и достаем картинку и описание
	 *
	 * @param $text
	 *
	 * @return array
	 */
	public static function parseURL($text): ?array {

		$result = NULL;

		preg_match_all( "/(http|https|ftp|ftps):\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $text, $matches );
		$urls = $matches[0];

		$url = $urls[0];

		if ( $url != '' ) {

			$headers = get_headers( $url, 1 );

			if ( strtolower( $headers['X-Frame-Options'] ) != "sameorigin" ) {

				$site    = parse_url( $url );
				$newhost = $site['scheme']."://".$site['host'];

				//$xssBlock = true;
				$html = file_get_contents( $url );

				$configuration = new Configuration( [
					// фиксирует относительные ссылки типа /img.png
					"FixRelativeURLs" => true,
					// на что менять
					"OriginalURL"     => $newhost
				] );

				$readability = new Readability( $configuration );

				try {

					$readability -> parse( $html );

					$name    = $readability -> getTitle();
					$images  = $readability -> getImages();
					$content = $readability -> getExcerpt();

					$img = (!empty( $images )) ? $images[0] : '';

					if ( $name != '' || $content != '' )
						$result = [
							"title"   => $name,
							"content" => $content,
							"image"   => $img,
							"url"     => $site['host']
						];

				}
				catch ( ParseException $e ) {

					//$result[ 'error' ] = sprintf( 'Error processing text: %s', $e -> getMessage() );

				}

			}

		}

		return $result;

	}

	/**
	 * Парсим текст на наличие номеров телефона и email
	 *
	 * @param      $text
	 * @param bool $withurl
	 * @return array
	 */
	public static function parseMessage($text, bool $withurl = false): array {

		$result['phone'] = getPhoneFromText( $text );
		$result['email'] = getEmailFromText( $text );

		if ( $withurl ) {

			foreach ( $result['phone'] as $k => $phone ) {

				$result['phone'][ $k ] = formatPhoneUrl2( $phone );

			}

			foreach ( $result['email'] as $k => $email ) {

				$result['email'][ $k ] = link_it( $email );

			}

		}

		return $result;

	}

	/**
	 * Массив iduser операторов
	 *
	 * @return array
	 *
	 */
	public function getOperators(): array {

		return array_values( (array)customSettings( 'socialChatsOperators' ) );

	}

	/**
	 * Массив операторов дополненный UID
	 *
	 * @return array
	 */
	public function getOperatorsUID(): array {

		$users = $this -> getOperatorsFull();

		$comet = new Comet();

		foreach ( $users as $k => $user ) {
			$users[ $k ]['uid'] = $comet -> userUID( $user['iduser'] );
		}

		return $users;

	}

	/**
	 * Массив операторов
	 *
	 * @return array
	 *              - int **iduser**
	 *              - str **title** - фио
	 *              - str **tip** - роль в системе
	 *              - str **avatar** - полный путь к аватарке от корня системы
	 */
	public function getOperatorsFull(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$users = customSettings( 'socialChatsOperators' );

		$list = [];

		if ( !empty( $users ) ) {

			$r = $db -> getAll( "SELECT iduser, title, avatar, tip FROM {$sqlname}user WHERE iduser IN (".yimplode( ",", $users ).") AND identity = '$identity'" );
			foreach ( $r as $data ) {

				$avatar = "/assets/images/noavatar.png";

				if ( $data['avatar'] ) {
					$avatar = "/cash/avatars/".$data['avatar'];
				}

				$list[ $data['iduser'] ] = [
					"iduser" => $data['iduser'],
					"title"  => $data['title'],
					"tip"    => $data['tip'],
					"avatar" => $avatar
				];

			}

		}

		return $list;

	}

	/**
	 * Данные оператора включая аватар
	 *
	 * @param $iduser
	 * @return array
	 */
	public function operatorInfo($iduser): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$user = [];

		if ( $iduser > 0 ) {

			$data = $db -> getRow( "SELECT iduser, title, avatar, tip FROM {$sqlname}user WHERE iduser = '$iduser' AND identity = '$identity'" );

			$avatar = "/assets/images/noavatar.png";

			if ( $data['avatar'] ) {
				$avatar = "/cash/avatars/".$data['avatar'];
			}

			$user = [
				"iduser" => (int)$data['iduser'],
				"title"  => $data['title'],
				"tip"    => $data['tip'],
				"avatar" => $avatar
			];

		}

		return $user;

	}

	/**
	 * Добавление операторов
	 *
	 * @param $users
	 * @return string
	 */
	public function setOperators($users): string {

		customSettings( 'socialChatsOperators', 'put', ["params" => $users] );

		return 'ok';

	}

	/**
	 * Список подключенных каналов
	 *
	 * @return array
	 */
	public function getChannels(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$icons     = self ::channelsIcon();
		$providers = self ::channelsProvider();

		//print_r($icons);
		//print_r($providers);

		$list = [];

		$data = $db -> getAll( "SELECT * FROM ".$sqlname."chats_channels WHERE identity = '$identity'" );
		foreach ( $data as $da ) {

			$stngs = json_decode( $da['settings'], true );

			$type = ($da['type'] == 'Chatapi') ? 'whatsapp' : $da['type'];

			$list[] = [
				"id"         => (int)$da['id'],
				"channel_id" => $da['channel_id'],
				"date"       => $da['datum'],
				"type"       => $providers[ strtolower( $da['type'] ) ]['channel'],
				"otype"      => $da['type'],
				"name"       => $da['name'],
				"active"     => $da['active'] == 'on' ? true : NULL,
				"icon"       => $icons[ strtolower( $type ) ],
				"uri"        => $stngs['link'],
				"messenger"  => $providers[ strtolower( $da['type'] ) ]['messenger']
			];

		}

		return $list;

	}

	/**
	 * Подключение нового канала
	 *
	 * @param       $id
	 * @param array|string $params
	 * @return array
	 */
	public function setChannels($id, $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$result    = [];
		$providers = self ::channelsProvider();

		$params['settings'] = is_array( $params['settings'] ) ? json_encode_cyr( $params['settings'] ) : $params['settings'];

		if ( (int)$id == 0 ) {

			$params['identity'] = $identity;
			$db -> query( "INSERT INTO ".$sqlname."chats_channels SET ?u", $params );
			$id = $db -> insertId();

		}
		else {

			$db -> query( "UPDATE ".$sqlname."chats_channels SET ?u WHERE id = '$id'", $params );

		}

		$type = $params['type']."Provider";

		if ( $providers[ strtolower( $params['type'] ) ]['name'] != '' ) {

			$type = "Chats\\".$type;

			$provider = new $type();
			$result   = $provider -> setWebhook( $params );

		}

		$result['id'] = $id;

		return $result;

	}

	/**
	 * Удаление канала
	 *
	 * @param $id
	 * @return array
	 */
	public function deleteChannels($id): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$mes = [];

		$channel   = self ::channelsInfo( $id );
		$providers = self ::channelsProvider();

		$type           = $channel['type']."Provider";
		$param['token'] = $channel['token'];

		if ( $providers[ strtolower( $channel['type'] ) ]['name'] != '' ) {

			$type = "Chats\\".$type;

			$provider = new $type();
			$result   = $provider -> deleteWebhook( $param );

			$mes[] = "Удаление вебхук: ".$result['status'];

		}

		$db -> query( "DELETE FROM ".$sqlname."chats_channels WHERE id = '$id'" );
		$mes[] = 'Канал удален';

		return $mes;

	}

	/**
	 * Получение информации о канале
	 *
	 * @param int $id
	 * @return array
	 */
	public function checkChannelInfo(int $id = 0): array {

		$channel = self ::channelsInfo( $id );
		$type    = $channel['type']."Provider";

		$providers = self ::channelsProvider();

		$result = [];

		//if ( in_array( $channel['type'], array_keys( self::CHANNELS ) ) ) {
		if ( $providers[ strtolower( $channel['type'] ) ]['name'] != '' ) {

			$type = "Chats\\".$type;

			$provider = new $type();
			$result   = $provider -> getInfo( $channel );

		}

		return $result;

	}

	/**
	 * Информация о канале
	 *
	 * @param int    $id
	 * @param string $channel_id
	 * @return array
	 */
	public function channelInfo(int $id = 0, string $channel_id = ''): array {

		$sqlname = $this -> sqlname;
		//$db       = $this -> db;
		$identity = $this -> identity;

		$db = new SafeMySQL( $this -> opts );

		$providers = self ::channelsProvider();

		$da = $db -> getRow( "SELECT * FROM ".$sqlname."chats_channels WHERE ".($id > 0 ? "id = '$id' AND" : "channel_id = '$channel_id' AND")." identity = '$identity'" );

		if ( !empty( $da ) ) {

			return [
				"id"         => (int)$da['id'],
				"channel_id" => $da['channel_id'],
				"date"       => $da['datum'],
				"typename"   => $providers[ strtolower( $da['type'] ) ]['channel'],
				"type"       => $da['type'],
				"name"       => $da['name'],
				"token"      => $da['token'],
				"icon"       => self ::channelsIcon( $da['id'] ),
				"settings"   => json_decode( $da['settings'], true )
			];

		}

		return [];

	}

	/**
	 * Информация о Чате/Посетителе
	 *
	 * @param int    $id
	 * @param string $chat_id
	 * @return array
	 */
	public function chatInfo(int $id = 0, string $chat_id = ''): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$chat      = [];
		$providers = self ::channelsProvider();

		/**
		 * todo: добавить пагинацию, фильтры, поиск по сообщениям из чата
		 */

		$avatar = NULL;
		$query  = "
			SELECT 
				*,
				(SELECT max(datum) FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.chat_id = {$sqlname}chats_chat.chat_id) as lastdate
			FROM {$sqlname}chats_chat 
			WHERE 
				".($id > 0 ? "id = '$id' AND" : "chat_id = '$chat_id' AND")."
				identity = '$identity'
			ORDER BY lastdate DESC
		";
		$da     = $db -> getRow( $query );

		if ( !empty( $da ) ) {

			$type = $db -> getOne( "SELECT type FROM {$sqlname}chats_channels WHERE channel_id = '$da[channel_id]'" );

			$lastMessage = $db -> getRow( "SELECT * FROM {$sqlname}chats_dialogs WHERE chat_id = '$da[chat_id]' ORDER BY datum DESC LIMIT 1" );

			$lastMessage['content'] = mb_substr( html2text( htmlspecialchars_decode( (string)$lastMessage['content'] ) ), 0, 51, 'utf-8' );
			$lastMessage['datum']   = getTime( (string)$lastMessage['datum'] );

			$chat = [
				'id'          => (int)$da['id'],
				'channel_id'  => $da['channel_id'],
				'chat_id'     => $da['chat_id'],
				'type'        => $providers[ strtolower( $da['type'] ) ]['channel'],
				"icon"        => self ::channelsIcon( 0, $type ),
				'client_id'   => $da['client_id'],
				'firstname'   => $da['client_firstname'],
				'lastname'    => $da['client_lastname'],
				'avatar'      => ($da['client_avatar']) ? $this -> cashAvatar( $da['client_avatar'] ) : $avatar,
				'users'       => $da['users'],
				'pid'         => $da['pid'],
				'clid'        => $da['clid'],
				'color'       => self ::getColor( $da['client_firstname'] ),
				'status'      => $da['status'],
				'phone'       => $da['phone'],
				'email'       => $da['email'],
				'lastmessage' => $lastMessage,
				'isnew'       => $da['users'] == 0 ? true : NULL
			];

		}

		return $chat;

	}

	public function chatInfoShort($id = 0, $chat_id = ''): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$chat = [];
		//$providers = self ::channelsProvider();

		$avatar = NULL;
		$query  = "
			SELECT 
				*
			FROM {$sqlname}chats_chat 
			WHERE 
				".($id > 0 ? "id = '$id' AND" : "chat_id = '$chat_id' AND")."
				identity = '$identity'
		";
		$da     = $db -> getRow( $query );

		if ( !empty( $da ) ) {

			//$type = $db -> getOne( "SELECT type FROM {$sqlname}chats_channels WHERE channel_id = '$da[channel_id]'" );

			$chat = [
				'id'        => (int)$da['id'],
				'chat_id'   => $da['chat_id'],
				//"icon"        => self ::channelsIcon( 0, $type ),
				'firstname' => $da['client_firstname'],
				'lastname'  => $da['client_lastname'],
				'avatar'    => ($da['client_avatar']) ? $this -> cashAvatar( $da['client_avatar'] ) : $avatar,
				'users'     => $da['users'],
				'color'     => self ::getColor( $da['client_firstname'] ),
				'isnew'     => $da['users'] == 0 ? true : NULL
			];

		}

		return $chat;

	}

	/**
	 * Список чатов, поддерживает передачу filters
	 *
	 * @return array
	 */
	public function getChats(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$filters  = $this -> filters;

		$list = [];
		$sort = $sub = $sub2 = $free = '';

		$providers = self ::channelsProvider();

		//print_r($providers);

		//$filters['status'][] = 'inwork';

		$page = $filters['page'];

		/**
		 * Включая не назначенные диалоги
		 */
		if ( $filters['shownew'] == 1 ) {
			$free = "OR {$sqlname}chats_chat.status = 'free'";
		}

		//$sub2 .= " AND {$sqlname}chats_chat.status = 'free'";

		if ( !empty( $filters['status'] ) ) {
			$sub .= " OR {$sqlname}chats_chat.status IN (".yimplode( ",", $filters['status'], "'" ).") ";
		}

		/**
		 * Сотрудник
		 */
		if ( $iduser1 > 0 )
			$sort .= "
			( 
				(
					FIND_IN_SET('$iduser1', REPLACE({$sqlname}chats_chat.users, ';',',')) > 0 AND 
					(
						{$sqlname}chats_chat.status = 'inwork' $sub
					)
					$free
				) 
			) AND ";

		/**
		 * По каналу
		 */
		if ( !empty( $filters['channel'] ) ) {
			$sort .= " {$sqlname}chats_chat.channel_id IN (".yimplode( ",", $filters['channel'], "'" ).") AND ";
		}

		/**
		 * По дате последнего сообщения
		 */
		if ( $filters['d1'] != '' && $filters['d2'] == '' ) {
			$sort .= "{$sqlname}chats_chat.chat_id IN (SELECT chat_id FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.datum > '$filters[d1] 00:00:01') AND ";
		}
		elseif ( $filters['d1'] == '' && $filters['d2'] != '' ) {
			$sort .= "{$sqlname}chats_chat.chat_id IN (SELECT chat_id FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.datum < '$filters[d2] 00:00:01') AND ";
		}
		elseif ( $filters['d1'] != '' && $filters['d2'] != '' ) {
			$sort .= "{$sqlname}chats_chat.chat_id IN (SELECT chat_id FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.datum BETWEEN '$filters[d1] 00:00:01' AND '$filters[d2] 23:59:59') AND ";
		}

		/**
		 * По имени
		 */
		if ( $filters['name'] != '' ) {
			$sort .= "({$sqlname}chats_chat.client_firstname LIKE '%".$filters['name']."%' OR {$sqlname}chats_chat.client_lastname LIKE '%".$filters['name']."%') AND ";
		}

		/**
		 * По тексту
		 */
		if ( $filters['word'] != '' ) {
			$sort .= "{$sqlname}chats_chat.chat_id IN (SELECT chat_id FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.content LIKE '%".$filters['word']."%') AND ";
		}

		$order  = ($filters['order']) ? : "DESC";
		$sortby = ($filters['sort']) ? : "lastdate";

		/**
		 * todo: добавить пагинацию, фильтры, поиск по сообщениям из чата
		 */

		$avatar = NULL;//"/images/noavatar.png";

		$current  = current_datum();
		$dateText = '';

		$query = "
			SELECT 
				*,
				(SELECT max(datum) FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.chat_id = {$sqlname}chats_chat.chat_id) as lastdate
			FROM {$sqlname}chats_chat 
			WHERE 
				$sort
				{$sqlname}chats_chat.identity = '$identity'
			ORDER BY $sortby $order
		";

		$count = $db -> getOne( "
			SELECT 
				COUNT(*)
			FROM {$sqlname}chats_chat 
			WHERE 
				$sort
				{$sqlname}chats_chat.identity = '$identity'
		" );
		//print $db -> lastQuery();

		$lines_per_page = 20;
		$page           = (empty( $page ) || $page <= 0) ? 1 : (int)$page;
		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$count_pages = ceil( $count / $lines_per_page );
		if ( $count_pages < 1 )
			$count_pages = 1;

		$data = $filters['nolimit'] ? $db -> getAll( $query ) : $db -> getAll( "$query LIMIT $lpos,$lines_per_page" );
		//print $db -> lastQuery();
		foreach ( $data as $da ) {

			$dateDivider = NULL;

			//$type = $db -> getOne( "SELECT type FROM {$sqlname}chats_channels WHERE channel_id = '$da[channel_id]'" );

			$lastMessage = $db -> getRow( "SELECT datum, content, attachment, direction FROM {$sqlname}chats_dialogs WHERE chat_id = '$da[chat_id]' ORDER BY datum DESC LIMIT 1" );

			$date = get_smdate( $lastMessage['datum'] );

			if ( diffDate2( $date, $current ) == 0 && $dateText != $date ) {

				$dateText    = $date;
				$dateDivider = 'Сегодня';

			}
			elseif ( diffDate2( $date, $current ) == -1 && $dateText != $date ) {

				$dateText    = $date;
				$dateDivider = 'Вчера';

			}
			elseif ( diffDate2( $date, $current ) < -1 && $dateText != $date ) {

				$dateText    = $date;
				$dateDivider = format_date_rus_name( $dateText );

			}

			$lastMessage['content'] = mb_substr( html2text( htmlspecialchars_decode( $lastMessage['content'] ) ), 0, 51, 'utf-8' );
			$lastMessage['datum']   = getTime( (string)$lastMessage['datum'] );
			$lastMessage['time']    = $lastMessage['datum'];

			if ( $lastMessage['content'] == '' ) {

				$attachment = json_decode( $lastMessage['attachment'], true );

				if ( !empty( $attachment ) ) {

					$type                   = array_keys( $attachment );
					$lastMessage['content'] = ($type[0] == 'doc') ? '<i class="icon-attach-1"></i> Документ' : '<i class="icon-picture"></i> Изображение';

				}

			}


			$users = yexplode( ",", $da['users'] );

			$unreadCount = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_dialogs WHERE chat_id = '$da[chat_id]' AND status IS NULL" );

			$list[] = [
				'id'           => $da['id'],
				'channel_id'   => $da['channel_id'],
				'chat_id'      => $da['chat_id'],
				'otype'        => $da['type'],
				'type'         => $providers[ strtolower( $da['type'] ) ]['messenger'],
				'channel_type' => $da['type'],
				"icon"         => self ::channelsIcon( 0, strtolower( $da['type'] ) ),
				'client_id'    => $da['client_id'],
				'firstname'    => $da['client_firstname'],
				'lastname'     => $da['client_lastname'],
				'avatar'       => ($da['client_avatar']) ? $this -> cashAvatar( $da['client_avatar'] ) : $avatar,
				'users'        => $users,
				'pid'          => $da['pid'],
				'phone'        => $da['phone'],
				'color'        => self ::getColor( $da['client_firstname'] ),
				'lastmessage'  => $lastMessage,
				'dateDivider'  => $dateDivider,
				"unread"       => $unreadCount > 0 ? $unreadCount : NULL,
				"isnew"        => $da['users'] == 0 ? true : NULL,
				"isclosed"     => $da['status'] == 'archive' ? true : NULL
			];

		}

		return [
			"list"    => $list,
			"page"    => $page,
			"pageall" => $count_pages
		];

	}

	/**
	 * Выводит количество не прочитанных сообщений в чатах
	 *
	 * @return array
	 */
	public function getNewMessagesFromChats(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$list             = [];
		$unreadChatsCount = 0;

		$query = "
			SELECT 
				*,
				(SELECT max(datum) FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.chat_id = {$sqlname}chats_chat.chat_id) as lastdate
			FROM {$sqlname}chats_chat 
			WHERE 
				(SELECT max(datum) FROM {$sqlname}chats_dialogs WHERE {$sqlname}chats_dialogs.chat_id = {$sqlname}chats_chat.chat_id) IS NOT NULL AND
				FIND_IN_SET('$iduser1', REPLACE(".$sqlname."chats_chat.users, ';',',')) > 0 AND
				-- {$sqlname}chats_chat.status = 'free' AND
				{$sqlname}chats_chat.identity = '$identity'
			ORDER BY lastdate DESC
		";

		$data = $db -> getAll( $query );
		foreach ( $data as $da ) {

			$unreadCount = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}chats_dialogs WHERE chat_id = '$da[chat_id]' /*AND direction = 'in'*/ AND status IS NULL" );

			if ( $unreadCount > 0 ) {

				$list[] = [
					'id'      => $da['id'],
					'chat_id' => $da['chat_id'],
					"unread"  => $unreadCount
				];

				$unreadChatsCount++;

			}

		}

		return [
			"list"  => $list,
			"count" => $unreadChatsCount
		];

	}

	/**
	 * Наличие не распределенных чатов
	 *
	 * @return int
	 */
	public function getNewChatsCount(): int {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$query = "
			SELECT 
				COUNT(*)
			FROM {$sqlname}chats_chat 
			WHERE 
				-- ({$sqlname}chats_chat.users = '0' OR {$sqlname}chats_chat.users IS NULL) AND
				{$sqlname}chats_chat.status = 'free' AND
				{$sqlname}chats_chat.identity = '$identity'
			ORDER BY id DESC
		";

		return $db -> getOne( $query );

	}

	/**
	 * Возвращает список не распределенных чатов
	 *
	 * @return array
	 */
	public function getNewChatsList(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$list = [];

		$providers = self ::channelsProvider();

		$query = "
			SELECT 
				*
			FROM {$sqlname}chats_chat 
			WHERE 
				({$sqlname}chats_chat.users = '0' OR {$sqlname}chats_chat.users IS NULL) AND
				{$sqlname}chats_chat.identity = '$identity'
			ORDER BY id DESC
		";

		$r = $db -> query( $query );
		while ($da = $db -> fetch( $r )) {

			$type = $db -> getOne( "SELECT type FROM {$sqlname}chats_channels WHERE channel_id = '$da[channel_id]'" );

			$lastMessage = $db -> getRow( "SELECT * FROM {$sqlname}chats_dialogs WHERE chat_id = '$da[chat_id]' ORDER BY datum DESC LIMIT 1" );

			$lastMessage['content'] = mb_substr( html2text( htmlspecialchars_decode( $lastMessage['content'] ) ), 0, 51, 'utf-8' );
			$lastMessage['datum']   = getTime( (string)$lastMessage['datum'] );

			$list[] = [
				"id"          => $da['id'],
				"channel_id"  => $da['channel_id'],
				//'type'        => strtr( $type, self::CHANNELS ),
				'type'        => $providers[ strtolower( $type ) ]['channel'],
				"icon"        => self ::channelsIcon( 0, $type ),
				"chat_id"     => $da['chat_id'],
				"firstname"   => $da['client_firstname'],
				"lastname"    => $da['client_lastname'],
				"avatar"      => $da['client_avatar'],
				'lastmessage' => $lastMessage,
				"isnew"       => true
			];

		}

		return $list;

	}

	/**
	 * Назначение чата сотруднику
	 *
	 * @param int $chat_id
	 * @param int $user
	 * @return int
	 */
	public function chatSetUser(int $chat_id = 0, int $user = 0): int {

		$settings = $this -> settings;

		$info = $this -> chatInfo( 0, $chat_id );

		//print_r($info);
		//exit();

		$iduser = $user > 0 ? $user : $this -> iduser1;

		// отправка уведомления в чат
		if ( $settings['answer']['notifyUserSet'] ) {

			$params['iduser']    = $iduser;
			$params['chat_id']   = $info['chat_id'];
			$params['direction'] = 'out';
			$params['text']      = "Назначен сотрудник - ".current_user( $iduser );

			$idmessage = $this -> editMessage( 0, $params );

			$this -> sendMessage( $idmessage );

		}

		$this -> logChat( $chat_id, 'operator', $iduser );

		return $this -> editChat( $info['id'], [
			"users"   => $iduser,
			"chat_id" => $chat_id,
			"status"  => "inwork"
		] );

	}

	/**
	 * Добавление сотрудника в чат
	 *
	 * @param string|null $chat_id
	 * @param int         $user
	 * @return int
	 */
	public function chatAppendUser(string $chat_id = NULL, int $user = 0): int {

		$settings = $this -> settings;

		$chat = $this -> chatInfo( 0, $chat_id );

		// существующие сотрудники
		$usersExist = yexplode( ",", (string)$chat['users'] );

		// отправка уведомления в чат
		if ( $settings['answer']['notifyUserAppend'] ) {

			// отправим сообщение посетителю
			$params['iduser']    = $user;
			$params['chat_id']   = $chat['chat_id'];
			$params['direction'] = 'out';
			$params['text']      = "В диалог добавлен сотрудник - ".current_user( $user );

			$idmessage = $this -> editMessage( 0, $params );

			$this -> sendMessage( $idmessage );
			// отправим сообщение посетителю

		}

		// добавляемые сотрудники
		$userNew = !is_array( $user ) ? yexplode( ",", $user ) : $user;

		$users = array_unique( array_merge( $userNew, $usersExist ) );

		$this -> logChat( $chat_id, 'operator', $users );

		return $this -> editChat( $chat['id'], [
			"users"   => $users,
			"chat_id" => $chat_id,
			"status"  => "inwork"
		] );

	}

	/**
	 * Метод передачи чата у провайдера.
	 * Работает, если эта опция предусмотрена провайдером
	 *
	 * @param int    $iduser
	 * @param string $chat_id
	 * @return mixed
	 */
	public function chatTransfer(string $chat_id = '', int $iduser = 0) {

		$chat    = $this -> chatInfo( 0, $chat_id );
		$user_id = $chat['client_id'];

		$channel = $this -> channelInfo( 0, $chat['channel_id'] );

		// отправим сообщение посетителю
		$params['iduser']    = $iduser;
		$params['chat_id']   = $chat['chat_id'];
		$params['direction'] = 'out';
		$params['text']      = "Диалог передан сотруднику - ".current_user( $iduser );

		$idmessage = $this -> editMessage( 0, $params );

		$this -> sendMessage( $idmessage );
		// отправим сообщение посетителю

		// получаем адаптированные данные
		$type = $channel['type']."Provider";
		$type = "Chats\\".$type;

		return ( new $type() ) -> chatTransfer( $chat_id, $channel, $iduser );

	}

	/**
	 * Добавление оператора в чат
	 *
	 * @param string $chat_id
	 * @param int    $iduser
	 * @return mixed
	 */
	public function chatInvite(string $chat_id = '', int $iduser = 0) {

		$chat    = $this -> chatInfo( 0, $chat_id );
		$user_id = $chat['client_id'];

		$channel = $this -> channelInfo( 0, $chat['channel_id'] );

		// получаем адаптированные данные
		$type = $channel['type']."Provider";
		$type = "Chats\\".$type;

		return ( new $type() ) -> chatInvite( $chat_id, $channel, $iduser );

	}

	/**
	 * Автоматическое закрытие чатов
	 */
	public function chatAutoClose(): void {

		$settings = $this -> settings;

		// если активировано автозакрытие
		if ( $settings['autoClose'] > 0 ) {

			// список активных диалогов
			$this -> filters = [
				"status" => ['inwork']
			];
			$chats           = $this -> getChats();

			// проходим чаты
			foreach ( $chats as $chat ) {

				// только для исходящих сообщений
				if ( $chat['lastmessage']['direction'] == 'out' ) {

					// сколько часов прошло
					$diff = difftime( $chat['lastmessage']['time'] );

					// если прошло больше заданного
					if ( $diff >= $settings['autoClose'] ) {

						$this -> setChatStatus( 0, $chat['chat_id'], 'archive' );

					}

				}

			}

		}

	}

	/**
	 * Получение сообщений конкретного диалога
	 *
	 * @param     $chat_id
	 *
	 * @return array
	 */
	public function getDialogs($chat_id): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$filters  = $this -> filters;

		$list = [];

		/**
		 * todo: добавить пагинацию, фильтры, поиск по сообщению из чата
		 */
		$chat  = $this -> chatInfo( 0, $chat_id );
		$users = $this -> getOperatorsFull();

		$current  = current_datum();
		$dateText = '';
		$sort     = '';

		if ( $filters['lastmessage'] > 0 && !isset( $filters['page'] ) )
			$sort .= "AND id > '$filters[lastmessage]'";

		if ( isset( $filters['page'] ) && $filters['page'] == 1 )
			$p = "LIMIT 0, 30";
		else {

			//if($filters['maxid'] > 0)
			//$sort .= "AND id < '$filters[maxid]'";

			$pp2 = $filters['page'] * 30;
			$pp1 = ($filters['page'] - 1) * 30;
			$p   = "LIMIT $pp1, $pp2";

		}

		$totalPages  = round( $db -> getOne( "SELECT COUNT(*) / 30 FROM ".$sqlname."chats_dialogs WHERE chat_id = '$chat_id' $sort AND identity = '$identity'" ) + 0.5, 0 );
		$currentPage = $filters['page'];

		/**
		 * Выводим последние 30 записей с двойной сортировкой
		 */
		$data = $db -> getAll( "SELECT * FROM (SELECT * FROM ".$sqlname."chats_dialogs WHERE chat_id = '$chat_id' $sort AND identity = '$identity' ORDER BY id DESC $p) A ORDER BY id" );
		$q    = $db -> lastQuery();
		foreach ( $data as $da ) {

			$dateDivider = NULL;

			$avatar = ($da['direction'] == 'in') ? $chat['avatar'] : $users[ $da['iduser'] ]['avatar'];
			$color  = ($da['direction'] == 'in') ? "blue" : "green";
			$name   = ($da['direction'] == 'in') ? $chat['firstname'].' '.$chat['lastname'] : $users[ $da['iduser'] ]['title'];

			$date = get_smdate( $da['datum'] );

			if ( diffDate2( $date, $current ) < 0 && $dateText != $date ) {

				$dateText    = $date;
				$dateDivider = format_date_rus_name( $dateText );

			}
			elseif ( diffDate2( $date, $current ) == 0 && $dateText != $date ) {

				$dateText    = $date;
				$dateDivider = 'Сегодня';

			}

			$text = htmlspecialchars_decode( nl2br( $da['content'] ) );

			$readability = $da['readability'] != '' ? json_decode( $da['readability'], true ) : self :: parseURL( $text );

			if ( $da['readability'] == '' )
				$this -> editMessage( $da['id'], [
					"readability" => self :: parseURL( $text ),
					"direction"   => $da['direction']
				] );

			if ( $da['status'] != 'seen' && $filters['viz'] )
				$this -> messageStatusChange( $da['id'], "seen" );

			$attachent = $da['attachment'] != NULL ? json_decode( $da['attachment'], true ) : NULL;

			// проверим ссылку на документ. входящие документы не загружаются в систему и нужна ссылка на них
			// обработку смотри в /content/helpers/get.file.php

			$list[] = [
				'id'            => $da['id'],
				'message_id'    => $da['message_id'],
				'chat_id'       => $da['chat_id'],
				'datum'         => $da['datum'],
				'direction'     => $da['direction'],
				'status'        => $da['status'],
				'avatar'        => $avatar,
				'color'         => $color,
				'background'    => $chat['color'],
				'iduser'        => $da['iduser'],
				'name'          => $name,
				'content'       => link_it( $text ),
				'date'          => getTime( (string)$da['datum'] ),
				'dateDivider'   => $dateDivider,
				'boxbg'         => $da['direction'] == 'out' ? 'user' : '',
				'readability'   => ($readability['title'] != '' || $readability['image'] != '') ? $readability : NULL,
				'attachment'    => $attachent,
				'hasAttachment' => $attachent != NULL ? true : NULL,
				"diricon"       => $direct = ($da['direction'] == 'in') ? 'icon-reply' : 'icon-forward-1'
			];

			//$list = array_reverse($list);

		}

		$total = $data = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."chats_dialogs WHERE chat_id = '$chat_id' AND identity = '$identity'" );

		/**
		 * Пометим сообщения прочитанными
		 */
		$db -> query( "UPDATE ".$sqlname."chats_dialogs SET ?u WHERE chat_id = '$chat_id' AND identity = '$identity'", ["status" => 'seen'] );

		$chat['users'] = yexplode( ",", $chat['users'] );

		return [
			"query"       => $q,
			"list"        => $list,
			"chat"        => $chat,
			"total"       => $total > 0 ? $total : NULL,
			"totalPages"  => $totalPages,
			"currentPage" => $currentPage,
			"loadmore"    => $currentPage < $totalPages ? true : NULL
		];

	}

	/**
	 * Логгирование событий изменения в чатах
	 *
	 * @param             $chat_id
	 * @param string      $event    - источник события (dialog - изменение статуса, operator - изменение оператора чата)
	 * @param string      $value    - новое значение
	 *                              - free,inwork,archive,blocked - изменение статуса
	 *                              - iduser - изменение оператора чата
	 *
	 * @param string      $datum    - дата, если нужно
	 * @param string|null $oldvalue - старое значение, если нужно
	 *
	 * @return int
	 */
	public function logChat($chat_id, string $event = 'dialog', string $value = '', string $datum = '', string $oldvalue = NULL): int {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$id = 0;

		$chat = $this -> chatInfo( 0, $chat_id );

		if ( ($oldvalue == '' && !is_null($oldvalue)) || empty( $oldvalue ) ) {

			switch ($event) {

				case "dialog":

					$oldvalue = $chat['status'];

				break;
				case "operator":

					$oldvalue = $chat['users'];

				break;

			}

		}

		if ( $value != $oldvalue ) {

			$data = [
				"chat_id"  => $chat_id,
				"event"    => $event,
				"oldvalue" => $oldvalue,
				"newvalue" => $value,
				"iduser"   => $this -> iduser1,
				"identity" => $identity
			];

			if ( $datum != '' )
				$data['datum'] = $datum;

			$db -> query( "INSERT INTO {$sqlname}chats_logs SET ?u", $data );
			$id = $db -> InsertId();

		}

		return $id;

	}

	/**
	 * Редактирование чата
	 *
	 * @param int   $id
	 * @param array $params
	 * @return int
	 */
	public function editChat(int $id = 0, array $params = []): int {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$d = [
			'channel_id'       => $params['channel_id'],
			'chat_id'          => $params['chat_id'],
			'client_id'        => $params['client_id'] == '' ? $params['chat_id'] : $params['client_id'],
			'client_firstname' => $params['client_firstname'],
			'client_lastname'  => $params['client_lastname'],
			'client_avatar'    => $params['client_avatar'],
			'users'            => is_array( $params['users'] ) ? yimplode( ",", $params['users'] ) : $params['users'],
			'pid'              => $params['pid'],
			'clid'             => $params['clid'],
			'status'           => $params['status'],
			'type'             => $params['type'],
			"phone"            => $params['phone'],
			"email"            => $params['email'],
			'identity'         => $identity
		];

		if ( $id == 0 )
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}chats_chat WHERE client_id = '$params[client_id]'" );

		if ( $id == 0 && $params['chat_id'] != '' )
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}chats_chat WHERE chat_id = '$params[chat_id]'" );

		//print $db -> lastQuery()."<br>";
		//print $id;

		if ( $id == 0 ) {

			$this -> logChat( $params['chat_id'], 'dialog', $params['status'] );

			$db -> query( "INSERT INTO {$sqlname}chats_chat SET ?u", arrayNullClean( $d ) );
			$id = $db -> InsertId();

		}
		else {

			unset( $d['type'], $d['channel_id'], $d['chat_id'], $d['client_id'], $d['identity'] );

			//print_r($d);

			$this -> logChat( $params['chat_id'], 'dialog', $params['status'] );

			$db -> query( "UPDATE {$sqlname}chats_chat SET ?u WHERE id = '$id'", arrayNullClean( $d ) );

		}

		return $id;

	}

	/**
	 * Удаление чата вместе со всеми сообщениями
	 *
	 * @param int    $id
	 * @param string $chat_id
	 * @return array
	 */
	public function deleteChat(int $id = 0, string $chat_id = ''): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$info = $this -> chatInfo( $id, $chat_id );

		$result = [
			"result"  => false,
			"message" => "Не найдено"
		];

		if ( !empty( $info ) ) {

			$this -> params = ["silence" => true];

			$db -> query( "DELETE FROM {$sqlname}chats_chat WHERE id = '$info[id]'" );

			$data = $db -> getAll( "SELECT * FROM (SELECT * FROM ".$sqlname."chats_dialogs WHERE chat_id = '$info[chat_id]' AND identity = '$identity'" );
			foreach ( $data as $da ) {

				$this -> deleteMessage( $da['id'] );

			}

			$result = [
				"result"  => true,
				"message" => "Выполнено"
			];

		}

		return $result;

	}

	/**
	 * Устанавливает статус чата
	 *
	 * @param int    $id
	 * @param string $chat_id
	 * @param string $status - see const STATUS
	 */
	public function setChatStatus(int $id = 0, string $chat_id = '', string $status = ''): void {

		$this -> editChat( $id, [
			'chat_id' => $chat_id,
			'status'  => $status
		] );

		//self::logChat($chat_id, 'dialog', $status);

	}

	/**
	 * TODO: блокировка чата
	 *
	 * @param int    $id
	 * @param string $chat_id
	 */
	public function blockChat(int $id = 0, string $chat_id = ''): void {

		$this -> logChat( $chat_id, 'dialog', 'blocked' );

	}

	/**
	 * Информация о сообщении
	 *
	 * @param int    $id
	 * @param string $message_id
	 *
	 * @return array
	 *           - int **id** - id записи
	 *           - str **message_id** - id сообщения на сервере
	 *           - str **chat_id** - id чата
	 *           - str **channel_id** - id канала
	 *           - str **client_id** - id участника
	 *           - str **content** - текст сообщения
	 *           - array **chat** - инфа о чате
	 *           - array **channel** - инфа о канале
	 */
	public function messageInfo(int $id = 0, string $message_id = ''): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$data    = [];
		$message = [];

		if ( $id > 0 ) {
			$data = $db -> getRow( "SELECT * FROM {$sqlname}chats_dialogs WHERE id = '$id'" );
		}

		elseif ( $message_id > 0 ) {
			$data = $db -> getRow( "SELECT * FROM {$sqlname}chats_dialogs WHERE message_id = '$message_id'" );
		}

		if ( !empty( $data ) ) {

			$chat    = $this -> chatInfo( 0, $data['chat_id'] );
			$channel = $this -> channelInfo( 0, $chat['channel_id'] );

			$message = [
				'id'          => $data['id'],
				'message_id'  => $data['message_id'],
				'chat_id'     => $chat['chat_id'],
				'channel_id'  => $chat['channel_id'],
				'client_id'   => $chat['client_id'],
				'direction'   => $chat['direction'],
				'content'     => htmlspecialchars_decode( $data['content'] ),
				'attachments' => json_decode( $data['attachment'], true ),
				'chat'        => $chat,
				'channel'     => $channel
			];

		}

		return $message;

	}

	/**
	 * Добавление / Редактирование сообщения
	 *
	 * @param int   $id
	 * @param array $params
	 *
	 * @return int
	 */
	public function editMessage(int $id = 0, array $params = []): int {

		$sqlname = $this -> sqlname;
		$identity = $this -> identity;

		$db = new SafeMySQL( $this -> opts );

		$params['iduser'] = ($params['iduser'] < 1) ? $this -> iduser1 : $params['iduser'];

		if ( !isset( $params['readability'] ) ) {
			$params['readability'] = self :: parseURL( $params['text'] );
		}

		$d = [
			'chat_id'     => $params['chat_id'],
			'message_id'  => $params['message_id'],
			'direction'   => $params['direction'] ? : 'out',
			'status'      => $params['status'],
			'content'     => htmlspecialchars( $params['text'] ),
			'readability' => is_array( $params['readability'] ) ? json_encode_cyr( $params['readability'] ) : $params['readability'],
			'attachment'  => !empty( $params['attachment'] ) ? json_encode_cyr( $params['attachment'] ) : NULL,
			'datum'       => current_datumtime(),
			'iduser'      => $params['direction'] == "out" ? $params['iduser'] : NULL,
			'identity'    => $identity
		];

		file_put_contents($this -> rootpath."/cash/msg.json", json_encode($d));

		if ( $id == 0 ) {
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}chats_dialogs WHERE chat_id = '$d[chat_id]' AND message_id = '$d[message_id]'" );
		}

		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}chats_dialogs SET ?u", $d );
			$id = $db -> InsertId();

			/**
			 * Если это ответ, то в лог добавим время ответа
			 */
			if ( $d['direction'] == 'out' ) {

				$time = $db -> getOne( "SELECT datum FROM {$sqlname}chats_dialogs WHERE chat_id = '$d[chat_id]' AND direction = 'in' ORDER BY datum DESC LIMIT 1" );
				$diff = diffDateTimeSeq( $time );

				$this -> logChat( $d['chat_id'], 'answer', $diff );

			}

		}
		else {

			unset( $d['identity'], $d['datum'], /*$d['iduser'],*/ $d['chat_id'], $d['direction'], $d['attachment'] );

			$p = arrayNullClean( $d );

			if ( !empty( $p ) ) {
				$db -> query( "UPDATE {$sqlname}chats_dialogs SET ?u WHERE id = '$id'", $p );
			}

		}

		return $id;

	}

	/**
	 * Изменение статуса сообщения
	 *
	 * @param int    $id
	 * @param string $status
	 *
	 * @return int
	 */
	public function messageStatusChange(int $id = 0, string $status = ''): int {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$d = ['status' => $status];

		if ( $id > 0 ) {

			$db -> query( "UPDATE {$sqlname}chats_dialogs SET ?u WHERE id = '$id'", arrayNullClean( $d ) );

		}

		return $id;

	}

	/**
	 * Удаление сообщения
	 *
	 * @param int    $id
	 * @param string $message_id
	 *
	 * @return array
	 */
	public function deleteMessage(int $id = 0, string $message_id = ''): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$rootpath = $GLOBALS['rootpath'];

		$result = [];

		if ( $id == 0 ) {
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}chats_dialogs WHERE message_id = '$message_id'" );
		}

		if ( $id > 0 ) {

			$message = $this -> messageInfo( $id );

			// удалить можем только свои сообщения
			if ( $message['direction'] == 'out' && !$this -> params['silence'] ) {

				$type = $message['channel']['type']."Provider";
				$type = "Chats\\".$type;

				$provider = new $type();
				$result   = $provider -> deleteMessage( $message );

			}

			$db -> query( "DELETE FROM {$sqlname}chats_dialogs WHERE id = '$id'" );

			// удаляем вложения
			foreach ( $message['attachments'] as $type => $attach ) {

				foreach ( $attach as $file ) {

					if ( file_exists( $rootpath.$file['url'] ) ) {
						unlink( $rootpath.$file['url'] );
					}

				}

			}

		}

		return $result;

	}

	/**
	 * Отправка сообщения
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function sendMessage(int $id = 0): array {

		$msg  = $this -> messageInfo( $id );
		$user = $this -> operatorInfo( $this -> iduser1 );

		$type = $msg['channel']['type']."Provider";

		$providers = self ::channelsProvider();

		//print $providers[ strtolower($msg['channel']['type']) ]['name'];

		//if ( in_array( $msg['channel']['type'], array_keys( self::CHANNELS ) ) ) {
		if ( $providers[ strtolower( $msg['channel']['type'] ) ]['name'] != '' ) {

			$param = [
				"type"       => "text",
				"channel_id" => $msg['channel_id'],
				"chat_id"    => $msg['chat_id'],
				"client_id"  => $msg['chat']['client_id'],
				"text"       => $msg['content'],
				"token"      => $msg['channel']['token'],
				"username"   => $user['title'],
				"useravatar" => $user['avatar'],
				"settings"   => $msg['channel']['settings'],
				"parse_mode" => "text"
			];

			//print_r($param['attachments']);

			$type = "Chats\\".$type;

			$provider = new $type();
			$result   = $provider -> sendMessage( $param );

			//print_r($result);

			if ( $result['result'] == 'ok' ) {

				$this -> editMessage( $id, [
					"message_id" => $result['message_id'],
					"status"     => "seen",
					"iduser"     => $this -> iduser1
				] );

				$result = [
					"result"     => "ok",
					"message_id" => $result['message_id']
				];

			}
			else {

				$result = [
					"result"  => "error",
					"message" => $result['description']
				];

			}

		}
		else {

			$result = [
				"result"  => "error",
				"message" => "Не известный канал"
			];

		}

		return $result;

	}

	/**
	 * Отправка файлов
	 *
	 * @param int $id
	 * @return array
	 */
	public function sendFile(int $id = 0): array {

		$msg  = $this -> messageInfo( $id );
		$user = $this -> operatorInfo( $this -> iduser1 );

		$type = $msg['channel']['type']."Provider";

		$providers = self ::channelsProvider();

		//if ( in_array( $msg['channel']['type'], array_keys( self::CHANNELS ) ) ) {
		if ( $providers[ strtolower( $msg['channel']['type'] ) ]['name'] != '' ) {

			$param = [
				"channel_id"  => $msg['channel_id'],
				"chat_id"     => $msg['chat_id'],
				"client_id"   => $msg['chat']['client_id'],
				"attachments" => $msg['attachments'],
				"token"       => $msg['channel']['token'],
				"username"    => $user['title'],
				"useravatar"  => $user['avatar'],
				"settings"    => $msg['channel']['settings']
			];

			//print_r($param['attachments']);

			$type = "Chats\\".$type;

			$provider = new $type();
			$result   = $provider -> sendFile( $param );

			if ( $result['result'] == 'ok' ) {

				$this -> editMessage( $id, ["message_id" => $result['message_id']] );

				$result = [
					"result"     => "ok",
					"message_id" => $result['message_id']
				];

			}
			else {

				$result = [
					"result"  => "error",
					"message" => $result['description']
				];

			}

		}
		else {

			$result = [
				"result"  => "error",
				"message" => "Не известный канал"
			];

		}

		return $result;

	}

	/**
	 * Обработка входящих событий
	 *
	 * @param string $channel_id
	 * @param array  $params
	 * @return array
	 * @throws Exception
	 */
	public function newWebhookEvent(string $channel_id = '', array $params = []): array {

		$settings = $this -> settings;
		$db       = $this -> db;
		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;

		$result = [];

		$comet         = new Comet();
		$cometSettings = $comet -> settings;

		$operators    = $this -> getOperators();
		$operatorsUID = $this -> getOperatorsUID();

		$wsSended = false;

		//print_r($settings);

		if ( $channel_id != '' ) {

			$channel = $this -> channelInfo( 0, $channel_id );

			// получаем адаптированные данные
			$type = $channel['type']."Provider";
			$type = "Chats\\".$type;

			$provider = new $type();
			$result   = $provider -> eventFilter( $params, $channel );

			//file_put_contents($this -> rootpath. "/cash/tst.txt", json_encode_cyr(["channel" => $channel, "result" => $result])."\n\n", FILE_APPEND);

			$result['channel_id'] = $channel_id;

			if ( $result['chat_id'] != '' ) {

				$chat = $this -> chatInfo( 0, $result['chat_id'] );

				if ( $chat['status'] == 'archive' ) {

					$this -> editChat( 0, [
						'chat_id' => $result['chat_id'],
						'status'  => 'inwork'
					] );

				}

				//$string = is_array($chat) ? array2string($chat) : $chat;
				//file_put_contents($GLOBALS['rootpath'].'/cash/sch-webhooks.log', current_datumtime()."\nCHAT\n".$string."\n\n", FILE_APPEND);

				// если пользователь не найден (сообщение отправлено извне)
				// то указываем первого ответственного
				if ( $result['direction'] == 'out' && $result['iduser'] < 1 ) {

					$result['iduser'] = yexplode( ",", $chat['users'], 0 );

				}

				// если чат не найден, то надо добавить его
				if ( empty( $chat ) || (int)$chat['id'] == 0 ) {

					$result['clid'] = $result['pid'] = 0;

					// для Whatsapp знаем номер телефона
					// поэтому можем поискать в базе
					if ( $result['phone'] != '' && mb_strtolower( $result['type'] ) == 'whatsapp' && $result['direction'] == 'in' ) {

						$phone = substr( prepareMobPhone( $result['phone'] ), 1 );

						if ( $settings['autoSaveAs'] == "person" ) {

							$r              = $db -> getRow( "SELECT clid FROM {$sqlname}clientcat WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' and identity = '$identity'" );
							$result['clid'] = (int)$r['clid'];
							$result['pid']  = 0;

						}
						if ( $settings['autoSaveAs'] == "client" ) {

							$r              = $db -> getRow( "SELECT pid, clid FROM {$sqlname}personcat WHERE (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' OR (replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%') and identity = '$identity'" );
							$result['pid']  = (int)$r['pid'];
							$result['clid'] = (int)$r['clid'];

							if ( $result['clid'] == 0 ) {

								$r              = $db -> getRow( "SELECT clid FROM {$sqlname}clientcat WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' and identity = '$identity'" );
								$result['clid'] = (int)$r['clid'];

							}

						}

					}

					// автоматическое назначение
					if ( $settings['autoUser'] && $result['direction'] == 'in' ) {

						$result['iduser'] = $operators[ array_rand( $operators, 1 ) ];

					}

					// автоматическое добавление Клиента/Контакта
					// если его не нашли по номеру
					if ( $settings['autoSave'] && $result['direction'] == 'in' && ($result['pid'] == 0 && $result['clid'] == 0) ) {

						// сохраняем как Клиента
						if ( $settings['autoSaveAs'] == "client" ) {

							$c  = new Client();
							$r1 = $c -> add( [
								"iduser" => $result['iduser'],
								"title"  => $result['client_firstname']." ".$result['client_lastname']
							] );

							if ( $r1['data'] > 0 )
								$result['clid'] = $r1['data'];

							//$result['c'] = $r1;
							//print_r($r1);

						}

						// сохраняем как Контакт + Клиент
						elseif ( $settings['autoSaveAs'] == "person" ) {

							$number = $db -> getOne( "SELECT (MAX(clid) + 1) FROM {$sqlname}clientcat" );

							$c  = new Client();
							$r2 = $c -> add( [
								"iduser" => $result['iduser'],
								"title"  => "Новый клиент #{$number} ({$channel[ 'type' ]})"
							] );

							if ( $r2['data'] > 0 )
								$result['clid'] = $r2['data'];

							$p  = new Person();
							$r2 = $p -> edit( 0, [
								"iduser" => $result['iduser'],
								"person" => $result['client_firstname']." ".$result['client_lastname'],
								"clid"   => $result['clid']
							] );

							if ( $r2['data'] > 0 )
								$result['pid'] = $r2['data'];

							//print_r($r2);
							//$result['p'] = $r2;

						}

					}

					$this -> logChat( $result['chat_id'], 'dialog', "free" );

					$ch = [
						"channel_id"       => $channel_id,
						"chat_id"          => $result['chat_id'],
						"client_id"        => $result['chat_id'],
						"client_firstname" => $result['client_firstname'],
						"client_lastname"  => $result['client_lastname'],
						"client_avatar"    => $result['client_avatar'],
						"clid"             => $result['clid'],
						"pid"              => $result['pid'],
						"type"             => mb_strtolower( $result['type'] ),
						"phone"            => $result['phone'],
						"email"            => $result['email'],
						"users"            => $result['iduser']
					];

					if ( $result['iduser'] > 0 ) {

						$ch['status'] = 'inwork';

					}

					// добавляем чат
					$this -> editChat( 0, $ch );

					//file_put_contents($this -> rootpath. "/cash/tst.txt", json_encode_cyr($ch));

					$text = $result['text'];

					if ( $result['text'] == '' && !empty( $result['attachment'] ) ) {
						$text = "Файл";
					}

					if ( $result['direction'] == 'in' ) {

						// если пользователь найден, то отправляем личное уведомление
						if ( (int)$result['iduser'] > 0 ) {

							$msg = [
								"text"   => $text,
								"chatid" => $result['chat_id'],
								"tip"    => 'newchat'
							];
							$comet -> sendMessage( $result['iduser'], json_encode( $msg ) );
							$wsSended = true;

						}
						// или уведомление в канал
						else {

							$msg = [
								"text"   => $text,
								"chatid" => $result['chat_id'],
								"tip"    => 'newchat'
							];
							$comet -> sendMessageChannel( $cometSettings['channel'], json_encode( $msg ) );
							$wsSended = true;

						}

						// автоответы
						if ( $settings['answers']['first'] != '' ) {

							$id = $this -> editMessage( 0, [
								'chat_id'   => $result['chat_id'],
								'direction' => 'out',
								'text'      => $settings['answers']['first'],
								'iduser'    => $result['iduser']
							] );

							if ( $id > 0 ) {

								// отправим сообщение
								$this -> sendMessage( $id );

							}

						}

					}

				}

				// для нового диалога при установке юзера сразу сменим статус
				if ( $result['iduser'] > 0 && $chat['status'] == 'free' ) {

					$this -> editChat( 0, [
						'chat_id' => $result['chat_id'],
						'status'  => 'inwork'
					] );

				}

				// для у диалога не заполнено имя, то попробуем его установить
				if ( $result['client_firstname'] != '' && $chat['firstname'] == '' ) {

					$this -> editChat( 0, [
						'chat_id'          => $result['chat_id'],
						'client_firstname' => $result['client_firstname'],
						'client_lastname'  => $result['client_lastname'],
					] );

				}

				// для у диалога не заполнена аватарка и она пришла
				if ( $result['client_avatar'] != '' && $chat['avatar'] == '' ) {

					$this -> editChat( 0, [
						'chat_id'       => $result['chat_id'],
						'client_avatar' => $result['client_avatar']
					] );

				}

				// добавляем сообщение
				//switch ( $result[ 'event' ] ) {

				//case "newMessage":

				$r = $this -> editMessage( 0, $result );

				//file_put_contents($this -> rootpath. "/cash/tst.txt", json_encode_cyr(["result" => $result, "r" => $r])."\n\n", FILE_APPEND);

				$text = $result['text'];

				if ( $result['text'] == '' && !empty( $result['attachment'] ) ) {
					$text = "Файл";
				}

				$msg = [
					"text"   => $text,
					"chatid" => $result['chat_id'],
					"tip"    => 'newmessage'
				];

				// отправку делаем ранее
				//if(!$wsSended) {

					if ( !empty( $chat ) ) {

						$users = array_unique(yexplode( ",", $chat['users'] ));
						foreach ( $users as $user ) {
							$r = $comet -> sendMessage( $user, json_encode( $msg ) );
						}

					}
					else {
						$r = $comet -> sendMessageChannel( $cometSettings['channel'], json_encode( $msg ) );
					}
					unset( $r );

				//}

				//автоответ
				if ( $settings['answers']['offline'] != '' && $result['direction'] == 'in' ) {

					$uoffline = $comet -> userOnline( $operators );

					//$string = is_array($uoffline) ? array2string($uoffline) : $uoffline;
					//file_put_contents($GLOBALS['rootpath'].'/cash/sch-webhooks.log', current_datumtime()."\nONLINE\n".$string."\n\n", FILE_APPEND);

					$ucount = 0;
					foreach ( $uoffline as $u ) {
						if ( $u ) {
							$ucount++;
						}
					}

					// если онлайн никого нет, то отправим текст
					if ( $ucount == 0 ) {

						$id = $this -> editMessage( 0, $s = [
							'chat_id'   => $result['chat_id'],
							'direction' => 'out',
							'text'      => $settings['answers']['offline'],
							'iduser'    => $result['iduser']
						] );

						if ( $id > 0 ) {

							// отправим сообщение
							$this -> sendMessage( $id );

						}

						//$string = is_array($s) ? array2string($s) : $s;
						//file_put_contents($GLOBALS['rootpath'].'/cash/sch-webhooks.log', current_datumtime()."\nINC\n".$string."\n\n", FILE_APPEND);

					}

				}

				//break;

				//}

			}

		}

		//print_r($result);

		return $result;

	}

	/**
	 * Позволяет повторно запросить информацию о посетителе
	 *
	 * @param int    $id
	 * @param string $chat_id
	 * @return array
	 */
	public function updateUserInfo(int $id = 0, string $chat_id = ''): array {

		$userdata = [];

		$chat = $this -> chatInfo( $id, $chat_id );

		$channel = $this -> channelInfo( 0, $chat['channel_id'] );

		// получаем адаптированные данные
		$type = $channel['type']."Provider";
		$type = "Chats\\".$type;

		$provider = new $type();
		$result   = $provider -> getUserInfo( $chat_id, $channel );

		//print_r($result);

		if ( $result['ok'] ) {

			$userdata = [
				"ok"               => true,
				"client_firstname" => $result['client_firstname'],
				"client_lastname"  => $result['client_lastname'],
				"client_avatar"    => $this -> cashAvatar( $result['client_avatar'] ),
			];

			$this -> editChat( $chat['id'], $userdata );

		}
		else {

			$userdata['ok'] = false;

		}

		return $userdata;

	}

	/**
	 * Кэшируем аватар посетителя
	 * Иначе браузер может не загрузить внешнее содержимое
	 * Если аватар уже кэширован, то возвращаем ссылку на кэш
	 * Папка кэша - /files/chatcash/
	 *
	 * @param        $url
	 *
	 * @return string
	 */
	public function cashAvatar($url): string {

		$rootpath = $GLOBALS['rootpath'];
		$fpath    = $this -> fpath;

		createDir( $rootpath."/files/{$fpath}chatcash" );

		$r        = parse_url( $url );
		$filename = basename( $r['path'] );

		parse_str( $r['query'], $query );

		// для вайбера
		if ( $query['fltp'] ) {
			$filename = md5($query['dlid']).".".$query['fltp'];
		}

		if ( $filename == 'generic-avatar%402x.png' ) {
			$filename = md5($filename).getExtention($filename);
		}

		if ( in_array( $r['scheme'], [
			'https',
			'http'
		] ) ) {

			if ( !file_exists( $GLOBALS['rootpath']."/files/chatcash/".$filename ) ) {

				$img = file_get_contents( $url );
				file_put_contents( $GLOBALS['rootpath']."/files/chatcash/".$filename, $img );

			}

		}

		return "/files/chatcash/".$filename;

	}

	/**
	 * Загрузка файлов
	 *
	 * @return array
	 */
	public function uploadFile(): array {

		$rootpath = $GLOBALS['rootpath'];
		$fpath    = $this -> fpath;

		$attachment = [];

		createDir( $rootpath."/files/{$fpath}chatcash/files" );

		$files = Upload ::upload( 'chatcash/files' );

		//print_r($files);

		$this -> error = $files['message'];

		foreach ( $files['data'] as $file ) {

			$ext          = getExtention( $file['name'] );
			$file['url']  = "/files/{$fpath}chatcash/files/".$file['name'];
			$file['icon'] = get_icon3( $file['name'] );
			$file['size'] = filesize( $rootpath."/files/{$fpath}chatcash/files/".$file['name'] );

			if ( in_array( $ext, [
				'png',
				'jpeg',
				'jpg',
				'gif'
			] ) ) {

				$file['type']          = "image/jpg";
				$attachment['photo'][] = $file;

			}
			else {
				$attachment['doc'][] = $file;
			}

		}

		return $attachment;

	}

	public function getComet($iduser) {

		// параметры comet-сервера
		$comet         = new Comet( $iduser );
		$cometSettings = $comet -> getSettings();

		// регистрация юзера
		return $comet -> setUser();

	}

	/**
	 * Конвертируем изображения в JPG
	 *
	 * @param $originalImage
	 * @param int $quality
	 * @return int
	 */
	public function convertImage($originalImage, int $quality = 90): ?int {

		$rootpath = $GLOBALS['rootpath'];

		// jpg, png, gif or bmp?
		$ext      = getExtention( $originalImage );
		$imageTmp = '';

		if ( $ext != 'jpg' && in_array( $ext, [
				'png',
				'jpeg',
				'jpg',
				'gif',
				'bmp'
			] ) ) {

			$newImage = changeFileExt( texttosmall( $originalImage ), 'jpg' );

			$outputImage = $newImage['pathNew'];

			if ( preg_match( '/jpg|jpeg/i', $ext ) ) {
				$imageTmp = imagecreatefromjpeg($rootpath.$originalImage);
			}
			elseif ( false !== stripos( $ext, "png" ) ) {
				$imageTmp = imagecreatefrompng($rootpath.$originalImage);
			}
			elseif ( false !== stripos( $ext, "gif" ) ) {
				$imageTmp = imagecreatefromgif($rootpath.$originalImage);
			}
			elseif ( false !== stripos( $ext, "bmp" ) ) {
				$imageTmp = imagecreatefrombmp($rootpath.$originalImage);
			}

			// quality is a value from 0 (worst) to 100 (best)
			imagejpeg( $imageTmp, $rootpath.$outputImage, $quality );
			imagedestroy( $imageTmp );

			unlink( $rootpath.$originalImage );

			return $outputImage;

		}

		return $originalImage;

	}

}