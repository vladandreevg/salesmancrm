<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Доступ и защита от CSRF для раздела «Нейро».
 *
 * PHP-сессии в этой сборке не используются (свой cookie `ses` + запись app_user.ses),
 * поэтому токен не хранится, а выводится: HMAC(значение серверной сессии, id пользователя).
 * Подделать его, не зная `ses`, невозможно; при отсутствии сессии или пользователя
 * токена нет вовсе, и проверка закрывается (fail closed).
 *
 * Class Security
 *
 * @package Neuro
 */
class Security {

	/**
	 * Примесь к ключу HMAC — чтобы токен нельзя было переиспользовать в другой подсистеме.
	 */
	public const TOKEN_SALT = 'neurocore.csrf';

	/**
	 * Проверка административных прав по флагам пользователя.
	 * Чистая функция — проверяется без БД.
	 *
	 * @param mixed $isadmin - app_user.isadmin ('on' | 'off')
	 * @param mixed $tipuser - app_user.tip
	 *
	 * @return bool
	 */
	public static function isAdminFlag($isadmin, $tipuser): bool {
		return (string)$isadmin === 'on' || (string)$tipuser === 'Администратор';
	}

	/**
	 * Полная проверка прав: флаг администратора либо явное присутствие в списке
	 * администраторов аккаунта (как в остальных плагинах CRM).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $iduser1
	 * @param int    $identity
	 * @param mixed  $isadmin
	 * @param mixed  $tipuser
	 *
	 * @return bool
	 */
	public static function isAdmin($db, string $sqlname, int $iduser1, int $identity, $isadmin, $tipuser): bool {

		if (self::isAdminFlag($isadmin, $tipuser)) {
			return true;
		}

		if ($iduser1 <= 0) {
			return false;
		}

		$access = array_map('intval', (array)$db->getCol("SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' and secrty = 'yes' and identity = ?i", $identity));

		return in_array($iduser1, $access, true);

	}

	/**
	 * CSRF-токен текущей сессии пользователя.
	 *
	 * @param string $ses     - значение серверного session-токена (cookie ses)
	 * @param int    $iduser1 - id пользователя
	 *
	 * @return string - 64 hex-символа, либо '' если сессии/пользователя нет
	 */
	public static function csrfToken(string $ses, int $iduser1): string {

		if ($ses === '' || $iduser1 <= 0) {
			return '';
		}

		return hash_hmac('sha256', self::TOKEN_SALT.'|'.$iduser1, $ses);

	}

	/**
	 * Токен для текущего запроса.
	 *
	 * @return string
	 */
	public static function sessionToken(): string {

		$iduser1 = (int)($GLOBALS['iduser1'] ?? 0);
		$ses     = isset($_COOKIE['ses']) ? (string)$_COOKIE['ses'] : '';

		return self::csrfToken($ses, $iduser1);

	}

	/**
	 * Сравнение токенов в постоянном времени. Без «поблажек»: пустые значения,
	 * пробелы и другой регистр — не совпадение.
	 *
	 * @param mixed $given    - токен из запроса
	 * @param mixed $expected - эталонный токен
	 *
	 * @return bool
	 */
	public static function checkToken($given, $expected): bool {

		$given    = is_string($given) ? $given : '';
		$expected = is_string($expected) ? $expected : '';

		if ($given === '' || $expected === '') {
			return false;
		}

		return hash_equals($expected, $given);

	}

	/**
	 * Причина отказа по токену — чтобы человек видел, что именно случилось,
	 * а не общее «сервер не ответил». Чистая функция (проверяется тестом).
	 *
	 * @param mixed  $given
	 * @param string $expected
	 *
	 * @return string
	 */
	public static function tokenFailureReason($given, string $expected): string {

		if ($expected === '') {
			return 'Не удалось определить сессию: войдите в CRM заново и повторите действие';
		}

		if (!is_string($given) || $given === '') {
			return 'Запрос пришёл без токена безопасности: обновите страницу и повторите действие';
		}

		return 'Токен безопасности не совпал: обновите страницу и повторите действие';

	}

	/**
	 * Требует корректный CSRF-токен для мутирующего действия.
	 * При несовпадении — JSON-ошибка и остановка (fail closed).
	 *
	 * @param mixed $given
	 *
	 * @return void
	 */
	public static function requireToken($given): void {

		$expected = self::sessionToken();

		if (self::checkToken($given, $expected)) {
			return;
		}

		http_response_code(403);

		header('Content-Type: application/json; charset=utf-8');

		print json_encode(['status' => 'error', 'message' => self::tokenFailureReason($given, $expected)], JSON_UNESCAPED_UNICODE);

		exit();

	}

}
