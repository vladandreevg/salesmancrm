<?
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*          ver. 2018.x         */
/* ============================ */

/**
 * Class Viber -  класс работы с Viber Bot API
 * Требования: cURL, PHP5.3 и выше, Apache Mod_Rewrite
 * Документация: https://developers.viber.com/docs/api/rest-bot-api/
 */
class Viber {

	public $url = 'https://chatapi.viber.com/pa/';

	public $auth;
	public $ch;
	public $answer, $info;
	public $headers;
	public $error;

	public function __construct($token) {
		$this -> auth = 'X-Viber-Auth-Token: '.$token;
	}

	private function start($method, $request = null): void {

		if (is_array($request) && !empty($request)) {
			$request = json_encode($request);
		}

		if(empty($request)){
			$request = [];
		}

		$this -> headers = [
			$this -> auth,
			"Content-Type: application/json"
		];

		$this -> ch = curl_init();
		curl_setopt($this -> ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($this -> ch, CURLOPT_HEADER, 0);
		curl_setopt($this -> ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($this -> ch, CURLOPT_BINARYTRANSFER, 1);
		//curl_setopt($this -> ch, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($this -> ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this -> ch, CURLOPT_URL, $this -> url.$method);

		if ($request != '') {
			curl_setopt($this -> ch, CURLOPT_POSTFIELDS, $request);
		}

	}

	private function exec(): void {

		curl_setopt($this -> ch, CURLOPT_HTTPHEADER, $this -> headers);
		$this -> answer = curl_exec($this -> ch);
		$this -> info   = curl_getinfo($this -> ch);
		$this -> error  = curl_error($this -> ch);
		curl_close($this -> ch);

	}

	/**
	 * get_account_info - Информация о боте
	 * @return bool
	 */
	public function botInfo(): bool {

		$this -> start('get_account_info');
		$this -> exec();

		return true;

	}

	/**
	 * set_webhook - Устанавливает адрес Webhook
	 * @param string $url
	 * @return bool
	 */
	public function setWebhook(string $url): bool {

		$params = [
			"url"         => $url,
			"event_types" => [
				"delivered",
				"seen",
				"failed",
				"subscribed",
				"unsubscribed",
				"conversation_started"
			],
			"send_name"   => true,
			"send_photo"  => true
		];

		$this -> start('set_webhook', $params);
		$this -> exec();

		// Error!
		return $this -> info['http_code'] == '200';

	}

	/**
	 * set_webhook - Удаляет адрес Webhook
	 * @return bool
	 */
	public function deleteWebhook(): bool {

		$params = [
			"url" => ""
		];

		$this -> start('set_webhook', $params);
		$this -> exec();

		// Error!
		return $this -> info['http_code'] == '200';

	}

	/**
	 * get_user_details - получает информацию о пользователе
	 *
	 * @param $id
	 * @return bool
	 */
	public function getUserInfo($id): bool {

		$this -> start('get_user_details', ["id" => $id]);
		$this -> exec();

		return true;

	}

	/**
	 * Отправляет сообщение
	 * @param $type - тип сообщения (text, picture, file, video, url, contact)
	 * @param $message - текст сообщения
	 * @param $receiver - id пользователя - получателя
	 * @param array $sender - массив данных бота
	 *     name, avatar url
	 * @param array $other - прочие параметры, если отправка не текстовая
	 *     $type != text => + media (url)
	 *     $type == file => + size, + file_name
	 * @param array $keyboard - параметры клавиатуры
	 *     "Type"          => "keyboard",
			* "DefaultHeight" => true,
			* "Buttons"       => array(
				* array(
					* "ActionType" => "reply",
					* "ActionBody" => "reply to me",
					* "Text"       => "Key text",
					* "TextSize"   => "regular"
				* )
			* )
	* )
	 * @return bool
	 */
	public function sendMessage($type, $message, $receiver, array $sender = [], array $other = [], array $keyboard = []): bool {

		$params = [
			"receiver"      => $receiver,
			"type"          => $type,
			"sender"        => [
				"name"   => $sender['name'] ?? "Bot",
				"avatar" => $sender['avatar'] ?? "",
			],
			"tracking_data" => "tracking_data"
		];

		//если текстовое сообщение
		if (in_array($type, [
			'text',
			'picture'
		])) {
			$params["text"] = $message;
		}

		if (!empty($other)) {

			//для всех медиа-типов добавляем параметр media - ссылка на ресурс
			if ($other['media'] && in_array($type, [
					"picture",
					"file",
					"video",
					"url"
				])) {
				$params['media'] = $other['media'];
			}

			//для файла нужны доп.параметры
			if ($other['media'] && $type == "file") {

				$params['size']      = $other['size'];
				$params['file_name'] = $other['file_name'];

			}

		}

		if (!empty($keyboard)) {
			$params['keyboard'] = $keyboard;
		}

		$this -> start('send_message', $params);
		$this -> exec();

		// Error!
		return $this -> info['http_code'] == '200';

	}

}
