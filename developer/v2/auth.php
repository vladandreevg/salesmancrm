<?php
/**
 * Авторизация чрз API
 */

// работает для запросов из vue axios
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	header("Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS");
	header("Access-Control-Allow-Credentials: true");
	header("Access-Control-Allow-Headers: Access-Control-Allow-Origin, Access-Control-Allow-Credentials, Content-Type, Token, X-Requested-With, Session");
	header("Access-Control-Max-Age: 1728000");

	//file_put_contents("auth-header.json", json_encode(getallheaders()).",\n", FILE_APPEND);

	exit();

}

header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
header('Content-Type: application/json; charset=utf-8');


error_reporting(E_ERROR);

$root = dirname( __DIR__, 2 );

if (!file_exists($root."/cash/error.log")) {

	$file = fopen($root."/cash/error.log", 'wb');
	fclose($file);

}
ini_set('log_errors', 'On');
ini_set('error_log', $root.'/cash/error.log');

include $root."/inc/config.php";
include $root."/inc/dbconnector.php";

function Cleaner($string) {

	$string = trim($string);

	return str_replace([
		'\n\r',
		"'",
		'"',
		"`",
	], [
		'',
		"&acute;",
		'”',
		""
	], $string);

}

$headers = getallheaders();

// file_put_contents("auth.json", json_encode_cyr($data).",\n", FILE_APPEND);

/**
 * Принимаем в формате JSON
 */
if ($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);
	$params = array_merge($params, $_REQUEST);

}

/**
 * Если это GET-запрос или отправка формы
 */
else {

	$params = [];
	foreach ($_REQUEST as $key => $value) {
		$params[ $key ] = (!is_array($value)) ? Cleaner($value) : $value;
	}

}

$data = [
	"header"  => $headers,
	"params"  => $params,
	"request" => $_REQUEST
];



//file_put_contents("auth.json", json_encode_cyr($data).",\n", FILE_APPEND);

if($params['action'] == 'login') {

	/**
	 * Ожидаемые входные данные
	 */
	$login    = $params['login'];
	$password = $params['password'];
	$token    = $headers['apikey'] ?? $params['Apikey'];

	$tokenExist = $db -> getRow("
		SELECT 
			*
		FROM {$sqlname}settings
		WHERE
			{$sqlname}settings.api_key = '$token'
	");

	if ((int)$tokenExist['id'] > 0) {

		$timezone = $tokenExist['timezone'];
		$identity = $tokenExist['identity'];

		global $identity;

		include $root."/inc/settings.php";
		include $root."/inc/func.php";

		//Составим список всех активных сотрудников
		if ( !$isCloud ) {
			$users_acsept = $db -> getCol( "SELECT iduser FROM ".$sqlname."user WHERE secrty = 'yes' and identity = '$identity' ORDER by iduser ".$userlim );
		}

		//установим временну зону под настройки аккаунта
		date_default_timezone_set($timezone);

		$ures = $db -> getRow("SELECT * FROM ".$sqlname."user WHERE login = '$login'");
		$UserID = $ures['id'];

		if ($ures['secrty'] == 'yes') {

			$salt = $ures['sole'];

			if ( encodePass( $password, $salt ) == $ures['pwd'] ) {

				if ( in_array( $ures['id'], $users_acsept ) ) {

					$session = preg_replace("#[^a-zA-Z0-9]#i", ",", crypt($login + time(), $password));

					$db -> query("UPDATE ".$sqlname."user SET ses = '$session' WHERE id = '$UserID'");

					logger('0', 'Пользователь авторизовался в системе', $UserID);

					setcookie("ses", $session, time() + $time, "/");
					setcookie("UserID", $UserID, time() + $time, "/");

					$result  = 'login';
					$message = "Success";

					header("Set-Cookie: ses=$session; Expires: ".(time() + $time)."; path=/;");
					header("Set-Cookie: UserID=$UserID; Expires: ".(time() + $time)."; path=/;");
					HTTPStatus("200")['error'];

				}
				else {

					$result  = 'error';
					$message = 'Превышен лимит пользователей';

					logger( '0', 'Неудачная авторизация (Превышен лимит пользователей) с параметрами: Логин = '.$login, $UserID );

					HTTPStatus("401")['error'];

				}

			}
			else {

				logger( '0', 'Неудачная авторизация (неверный Логин/Пароль) с параметрами: Логин = '.$login, $UserID );

				$result  = 'error';
				$message = 'Неудачная авторизация (неверный Логин/Пароль)';

				HTTPStatus("401")['error'];

			}

			print json_encode_cyr([
				"result"  => $result,
				"message" => $message,
				"session" => $session,
				"UserID"  => $UserID,
				"user"    => [
					"name"     => $ures['title'],
					"email"    => $ures['email'],
					"settings" => json_decode($ures['usersettings'], true),
					"secrty"   => $ures['secrty'] == 'yes',
					"usertip"    => $ures['tipuser'],
					"userpost"    => $ures['user_post'],
					"isadmin"  => $ures['isadmin'] == 'on'
				]
			]);

		}
		else {

			$result  = 'error';
			$message = 'Неудачная авторизация (Аккаунт заблокирован)';

			logger( '0', 'Неудачная авторизация (Аккаунт заблокирован) с параметрами: Логин = '.$login, $UserID );

			HTTPStatus("406")['error'];

			print json_encode_cyr([
				"result"  => $result,
				"message" => $message
			]);

		}

	}
	else {

		HTTPStatus("401")['error'];

		print json_encode_cyr([
			"result"  => "error",
			"message" => "Bad Token"
		]);

	}

}
elseif($params['action'] == 'check') {

	$session = $headers['ses'] ? : $headers['Session'];

	if ($session != '') {

		//id клиента
		$UserID = $db -> getOne("SELECT id FROM ".$sqlname."user WHERE ses = '$session'");

		if($UserID != 0){

			//найдем дату последнего визита
			$resvizit = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."logs WHERE iduser = '$UserID' and date_format(datum, '%Y-%m-%d')= '".current_datum()."' and type = 'Начало дня' and identity = '$identity' ORDER BY id");

			//если значение найдено, значит он сегодня заходил
			if ( (int)$resvizit == 0) {
				logger('9', 'Первый запуск за день', $iduser1);
			}

			HTTPStatus("200")['error'];

			print json_encode_cyr([
				"result"  => "ok"
			]);

		}
		else{

			HTTPStatus("401")['error'];

			print json_encode_cyr([
				"result"  => "error",
				"message" => "Session is expired"
			]);

		}

	}

}

// JWT Authorize: https://only-to-top.ru/blog/programming/2019-06-20-registraciya-i-avtorizaciya-v-php-s-jwt.html