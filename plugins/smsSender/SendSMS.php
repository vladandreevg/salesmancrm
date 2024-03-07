<?php

namespace pSMS;

class SendSMS {

	public function sendSMS($action, $params = []): array {

		//$prefix = self ::_router();
		$prefix = realpath( __DIR__.'/../../' );

		require_once $prefix."/plugins/_core/core.php";

		$r     = [];
		$fpath = $GLOBALS['fpath'];

		$settings = json_decode( file_get_contents( $prefix.'/plugins/smsSender/data/'.$fpath.'settings.json' ), true );

		$type = $settings['type'];

		switch ($type) {

			case "sms.ru":

				$answer = [
					"-1"  => "Сообщение не найдено.",
					"100" => "Сообщение находится в очереди",
					"101" => "Сообщение передается оператору",
					"102" => "Сообщение отправлено (в пути)",
					"103" => "Сообщение доставлено",
					"104" => "Не может быть доставлено: время жизни истекло",
					"105" => "Не может быть доставлено: удалено оператором",
					"106" => "Не может быть доставлено: сбой в телефоне",
					"107" => "Не может быть доставлено: неизвестная причина",
					"108" => "Не может быть доставлено: отклонено",
					"200" => "Неправильный api_id",
					"201" => "Не хватает средств на лицевом счету",
					"202" => "Неправильно указан получатель",
					"203" => "Нет текста сообщения",
					"204" => "Имя отправителя не согласовано с администрацией",
					"205" => "Сообщение слишком длинное (превышает 8 СМС)",
					"206" => "Будет превышен или уже превышен дневной лимит на отправку сообщений",
					"207" => "На этот номер (или один из номеров) нельзя отправлять сообщения, либо указано более 100 номеров в списке получателей",
					"208" => "Параметр time указан неправильно",
					"209" => "Вы добавили этот номер (или один из номеров) в стоп-лист",
					"210" => "Используется GET, где необходимо использовать POST",
					"211" => "Метод не найден",
					"212" => "Текст сообщения необходимо передать в кодировке UTF-8 (вы передали в другой кодировке)",
					"220" => "Сервис временно недоступен, попробуйте чуть позже.",
					"230" => "Сообщение не принято к отправке, так как на один номер в день нельзя отправлять более 60 сообщений.",
					"231" => "Превышен лимит одинаковых сообщений на этот номер в минуту.",
					"232" => "Превышен лимит одинаковых сообщений на этот номер в день.",
					"300" => "Неправильный token (возможно истек срок действия, либо ваш IP изменился)",
					"301" => "Неправильный пароль, либо пользователь не найден",
					"302" => "Пользователь авторизован, но аккаунт не подтвержден (пользователь не ввел код, присланный в регистрационной смс)"
				];

				define( 'BASEURL', 'https://sms.ru/' );

				$result  = '';
				$balance = '';
				$uid     = 0;
				$cost    = 0;
				$count   = 0;
				$apikey  = $params['apikey'];

				if ( !$apikey ) {
					$apikey = $settings['apikey'];
				}

				$data = [
					"api_id" => $apikey
				];

				switch ($action) {
					//отправка
					case "send":

						$data['to']   = $params['phone'];
						$data['text'] = $params['content'];

						$response = sendRequest( BASEURL."sms/send", $data );

						[ $code, $uid ] = explode( "\n", $response );

						$result = strtr( $code, $answer );

					break;
					//получение статуса
					case "status":

						$data['id'] = $params['uid'];

						$response = sendRequest( BASEURL."sms/status", $data );

						[ $code, $balance ] = explode( "\n", $response );

						$result = strtr( $code, $answer );

					break;
					//получение стоимости
					case "cost":

						$data['to']   = $params['phone'];
						$data['text'] = $params['content'];

						//print_r($data);

						$response = sendRequest( BASEURL."sms/cost", $data );

						[ $code, $cost, $count ] = explode( "\n", $response );

						$result = strtr( $code, $answer );

					break;
					//баланс
					case "balance":

						$response = sendRequest( BASEURL."my/balance", $data );

						[ $code, $balance ] = explode( "\n", $response );

						$result = strtr( $code, $answer );

					break;
				}

				$r = [
					"result"  => $result,
					"balance" => $balance,
					"uid"     => $uid,
					"cost"    => $cost,
					"count"   => $count,
					"apikey"  => $apikey
				];

			break;
			case "smsaero.ru":

				define( 'BASEURL', 'https://gate.smsaero.ru/' );

				$login     = $params['login'];
				$password  = $params['password'];
				$signature = $params['signature'];

				$answer = [
					"accepted"                                                       => "Сообщение принято сервисом",
					"empty field. reject"                                            => "Не все обязательные поля заполнены",
					"incorrect user or password"                                     => "Ошибка авторизации",
					"no credits"                                                     => "Недостаточно средств на балансе",
					"incorrect sender name. reject"                                  => "Неверная (незарегистрированная) подпись отправителя",
					"incorrect destination adress. reject"                           => "Неверно задан номер телефона (формат 71234567890)",
					"incorrect date. reject"                                         => "Неправильный формат даты",
					"in blacklist. reject"                                           => "Телефон находится в черном списке. Внимание! Данные номера исключаются из рассылки при использование типа отправки sendtogroup",
					"incorrect language in '...' use the cyrillic or roman alphabet" => "в слове '...' одновременно используются символы из кириллицы и латиницы"
				];

				$settings = json_decode( file_get_contents( 'data/'.$fpath.'settings.json' ), true );

				if ( !$login ) {
					$login = $settings['login'];
				}
				if ( !$password ) {
					$password = $settings['password'];
				}
				if ( !$signature ) {
					$signature = $settings['signature'];
				}

				$data = [
					"user"     => $login,
					"password" => md5( $password ),
					"from"     => $signature,
					"answer"   => "json",
					"type"     => "3",
				];

				switch ($action) {
					//отправка
					case "send":

						$data['to']   = $params['phone'];
						$data['text'] = $params['content'];

					break;
					//получение статуса
					case "status":

						$data['id'] = $params['uid'];

					break;
					//получение стоимости. не поддерживается сервисом
					case "cost":

						$data['to']   = $params['phone'];
						$data['text'] = $params['content'];

						$response = sendRequest( BASEURL."balance/", $data );

					break;
					//баланс
					case "balance":

					break;
				}

				$response = file_get_contents( BASEURL.$action."/?".http_build_query( $data ) );

				$result = json_decode( $response, true );

				$r = [
					"result"  => strtr( $result['result'], $answer ),
					"balance" => $result['balance'],
					"uid"     => $result['id'],
					"text"    => $result['reason']
				];

			break;

		}

		return $r;

	}

}