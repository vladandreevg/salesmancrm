<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// https://habr.com/en/company/comet-server/blog/264425/?mobile=no

namespace Chats;

use Exception;
use SafeMySQL;

/**
 * Класс для управления чатами
 *
 *  Class Comet
 *
 * @package     Chats
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Comet {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser;

	/**
	 * Объект подключения к БД
	 *
	 * @var SafeMySQL
	 */
	public $cmt;

	public $settings;

	public function __construct($iduser = 0) {

		$rootpath = dirname(__DIR__, 4);

		require_once $rootpath."/vendor/autoload.php";

		$this -> identity = $GLOBALS['identity'];
		$this -> iduser   = $iduser;

		$this -> settings = (array)customSettings( "socialChatsSettings", "get" );

		$settings['dev_id']  = $this -> settings['dev_id'];
		$settings['dev_key'] = $this -> settings['dev_key'];
		$settings['channel'] = str_replace( ".", "", $_SERVER["HTTP_HOST"] ).$this -> identity;

		// настройки для подключения пользователя
		// и дальнейшей его авторизации через WS
		// обычно не нужны
		if ( $this -> iduser > 0 ) {

			$settings['user_id']  = $this -> userUID( $this -> iduser );
			$settings['user_key'] = md5( $this -> identity.$this -> iduser.current_user( $this -> iduser ) );

		}

		$this -> settings = $settings;

		if ( $settings['dev_id'] != '' ) {

			try {

				$this -> cmt = new SafeMySQL( [
					"host" => "app.comet-server.ru",
					"user" => $settings['dev_id'],
					"pass" => $settings['dev_key'],
					"db"   => "CometQL_v1"
				] );

			}
			catch ( Exception $e ) {

				print $e -> getMessage();

			}

		}

	}

	/**
	 * Возвращает параметры подключения к серверу
	 *
	 * @return array
	 */
	public function getSettings(): array {

		return $this -> settings;

	}

	/**
	 * Получает статус сервера (сколько пользователей онлайн, сколько сообщений)
	 *
	 * @return array
	 */
	public function status(): array {

		$cmt = $this -> cmt;

		$result = [];

		$r = $cmt -> query( "show status" );
		while ($da = $cmt -> fetch( $r )) {

			$result[ $da['Variable_name'] ] = $da['Value'];

		}

		return $result;

	}

	/**
	 * Получение данных авторизации пользователя
	 * Таблица users_auth содержит данные для авторизации пользователей на комет сервере.
	 *
	 * @return array|FALSE
	 */
	public function getUser(): array {

		$cmt      = $this -> cmt;
		$settings = $this -> settings;

		return $cmt -> getRow( "SELECT * FROM users_auth WHERE id = '$settings[user_id]'" );

	}

	/**
	 * добавление пользователя в базу авторизаций
	 *
	 * @return FALSE|resource
	 */
	public function setUser() {

		$cmt = $this -> cmt;
		$settings = $this -> settings;

		$r = [];

		try {

			$r = $cmt -> query( "INSERT INTO users_auth (id, hash ) VALUES ($settings[user_id], '$settings[user_key]')" );

		}
		catch ( Exception $e ) {

			print $e -> getTraceAsString();

		}

		return $r;

	}

	/**
	 * Удаление авторизации пользователя
	 *
	 * @return FALSE|resource
	 */
	public function deleteUser() {

		$cmt      = $this -> cmt;
		$settings = $this -> settings;

		return $cmt -> query( "DELETE FROM users_auth WHERE id = '$settings[user_id]'" );

	}

	/**
	 * Отправка сообщения пользователю
	 *
	 * @param $iduser
	 * @param $text
	 * @return FALSE|resource
	 */
	public function sendMessage($iduser, $text) {

		$cmt     = $this -> cmt;
		$user_id = $this -> userUID( $iduser );

		$r = false;

		if ( $this -> settings['dev_id'] != '' ) {

			try {

				$r = $cmt -> query( "INSERT INTO users_messages (id, event, message) VALUES ('$user_id','msg','$text')" );

			}
			catch ( Exception $e ) {

				print $r = $e -> getMessage();

			}

		}

		return $r;

	}

	/**
	 * Таблица users_time содержит данные о том когда были пользователи online.
	 * Таблица доступна только для чтения. Данные о времени хранятся в UNIX-time
	 *
	 * @param array $users
	 * @return array
	 */
	public function userOnline(array $users = []): array {

		$cmt = $this -> cmt;

		$list = [];

		if ( empty( $users ) ) {
			$users[] = $this -> userUID($this -> iduser);
		}
		else {

			foreach ( $users as $k => $u ) {
				$users[$k] = $this -> userUID($u);
			}

		}

		$result = $cmt -> getAll( "SELECT * FROM users_time WHERE id IN (".implode( ",", $users ).")" );

		foreach ( $result as $item ) {

			$list[ $item['id'] ] = $item['time'] == 0;

		}

		return $list;

	}

	/**
	 * Отправка сообщения в канал
	 * todo: переделать на отправку по id пользователей online, т.к. для облака будет всем херачить
	 *
	 * @param $channel
	 * @param $text
	 * @return FALSE|resource|string
	 */
	public function sendMessageChannel($channel, $text) {

		$cmt = $this -> cmt;

		$r = '';

		if ( $this -> settings['dev_id'] != '' ) {

			try {

				$r = $cmt -> query( "INSERT INTO pipes_messages (name, event, message) VALUES ('$channel', 'event_in_pipe', '$text')" );

			}
			catch ( Exception $e ) {

				print $r = $e -> getMessage();

			}

		}

		return $r;

	}

	// https://comet-server.com/wiki/doku.php/comet:authentication#%D0%BE%D1%82%D0%BF%D1%80%D0%B0%D0%B2%D0%BA%D0%B0_%D1%81%D0%BE%D0%BE%D0%B1%D1%89%D0%B5%D0%BD%D0%B8%D0%B9_%D0%B4%D0%BB%D1%8F_%D0%B0%D0%B2%D1%82%D0%BE%D1%80%D0%B8%D0%B7%D0%BE%D0%B2%D0%B0%D0%BD%D0%BD%D1%8B%D1%85_%D0%BF%D0%BE%D0%BB%D1%8C%D0%B7%D0%BE%D0%B2%D0%B0%D1%82%D0%B5%D0%BB%D0%B5%D0%B9
	public function getJWT($data, $pass, $dev_id = 0): string {

		// Create token header as a JSON string
		$header = json_encode( [
			'typ' => 'JWT',
			'alg' => 'HS256'
		] );

		if ( isset( $data['user_id'] ) ) {
			$data['user_id'] = (int)$data['user_id'];
		}

		// Create token payload as a JSON string
		$payload = json_encode( $data );

		// Encode Header to Base64Url String
		$base64UrlHeader = str_replace( [
			'+',
			'/',
			'='
		], [
			'-',
			'_',
			''
		], base64_encode( $header ) );

		// Encode Payload to Base64Url String
		$base64UrlPayload = str_replace( [
			'+',
			'/',
			'='
		], [
			'-',
			'_',
			''
		], base64_encode( $payload ) );

		// Create Signature Hash
		$signature = hash_hmac( 'sha256', $base64UrlHeader.".".$base64UrlPayload, $pass.$dev_id, true );

		// Encode Signature to Base64Url String
		$base64UrlSignature = str_replace( [
			'+',
			'/',
			'='
		], [
			'-',
			'_',
			''
		], base64_encode( $signature ) );

		// Create JWT
		return trim( $base64UrlHeader.".".$base64UrlPayload.".".$base64UrlSignature );

	}

	// попытка составить UID из id пользователя и host сервера, выраженный целыми числами
	public function userUID($iduser) {

		$name = str_split( $_SERVER["HTTP_HOST"] );
		//$name = str_split('sm2019.crm');
		$alfabet = array_flip( [
			'a',
			'b',
			'c',
			'd',
			'e',
			'f',
			'g',
			'h',
			'i',
			'j',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'q',
			'r',
			's',
			't',
			'u',
			'v',
			'w',
			'x',
			'y',
			'z'
		] );

		$uid = $iduser;

		foreach ( $name as $a ) {

			$uid .= $alfabet[ $a ];

		}

		return substr( $uid, 0, 9 );

	}

}