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
 * Шифрование ключей API, сохранённых в CRM.
 *
 * Почему не rij_crypt() из ядра: его ключ выводится из исходников (см. аудит,
 * раунд 3), то есть это обфускация, а не шифрование — дамп базы расшифровывается
 * кем угодно. Здесь используется AES-256-GCM с ключом, который генерируется
 * случайно при первом сохранении и лежит отдельным файлом вне базы:
 *
 *     plugins/neuroCore/data/secret.key   (каталог закрыт .htaccess, права 0600)
 *
 * Что это даёт и чего не даёт. Даёт: украденный дамп базы (бэкап, реплика, слив)
 * без файла ключа бесполезен. Не даёт: защиты от полного доступа к серверу — тому,
 * кто читает и базу, и файлы, ключ доступен по определению. Поэтому переменная
 * окружения остаётся предпочтительным вариантом, а это — удобный.
 *
 * GCM выбран потому, что он проверяет целостность: подмена шифротекста приводит к
 * ошибке расшифровки, а не к мусору.
 *
 * Class Crypto
 *
 * @package Neuro
 */
class Crypto {

	public const CIPHER    = 'aes-256-gcm';
	public const IV_LENGTH = 12;
	public const TAG_LENGTH = 16;
	public const KEY_FILE  = 'secret.key';
	public const KEY_HEX_LENGTH = 64;

	/**
	 * Доступно ли шифрование в этой сборке PHP.
	 *
	 * @return bool
	 */
	public static function available(): bool {

		if (!function_exists('openssl_encrypt') || !function_exists('openssl_decrypt')) {
			return false;
		}

		return in_array(self::CIPHER, openssl_get_cipher_methods(), true);

	}

	/**
	 * Новый мастер-ключ (hex, 32 байта).
	 *
	 * @return string
	 */
	public static function randomKey(): string {
		return bin2hex(random_bytes(32));
	}

	/**
	 * Шифрование. Возвращает base64 от (iv | tag | cipher).
	 *
	 * @param string $plain
	 * @param string $key - мастер-ключ
	 *
	 * @return string - '' при неудаче (пустые входные данные, нет openssl)
	 */
	public static function encrypt(string $plain, string $key): string {

		if ($plain === '' || $key === '' || !self::available()) {
			return '';
		}

		$iv  = random_bytes(self::IV_LENGTH);
		$tag = '';

		$cipher = openssl_encrypt($plain, self::CIPHER, self::keyBin($key), OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);

		if ($cipher === false || $tag === '') {
			return '';
		}

		return base64_encode($iv.$tag.$cipher);

	}

	/**
	 * Расшифровка. Пустая строка означает «не удалось» — вызывающий код обязан
	 * показать это человеку, а не подставлять пустой ключ.
	 *
	 * @param string $payload
	 * @param string $key
	 *
	 * @return string
	 */
	public static function decrypt(string $payload, string $key): string {

		if ($payload === '' || $key === '' || !self::available()) {
			return '';
		}

		$raw = base64_decode($payload, true);

		if ($raw === false || strlen($raw) <= (self::IV_LENGTH + self::TAG_LENGTH)) {
			return '';
		}

		$iv     = substr($raw, 0, self::IV_LENGTH);
		$tag    = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
		$cipher = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

		$plain = openssl_decrypt($cipher, self::CIPHER, self::keyBin($key), OPENSSL_RAW_DATA, $iv, $tag);

		return $plain === false ? '' : (string)$plain;

	}

	/**
	 * Мастер-ключ приводится к 32 байтам: файл хранит hex, но принимается и
	 * произвольная строка (например, заданная вручную).
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	private static function keyBin(string $key): string {

		$bin = preg_match('/^[0-9a-f]{64}$/i', $key) === 1 ? (string)hex2bin($key) : $key;

		return hash('sha256', $bin, true);

	}

	/**
	 * Путь до файла мастер-ключа.
	 *
	 * @param string $rootpath
	 *
	 * @return string
	 */
	public static function keyPath(string $rootpath): string {
		return rtrim($rootpath, '/\\').'/plugins/neuroCore/data/'.self::KEY_FILE;
	}

	/**
	 * Каталог для файла ключа (закрыт .htaccess).
	 *
	 * @param string $rootpath
	 *
	 * @return string
	 */
	public static function keyDir(string $rootpath): string {
		return rtrim($rootpath, '/\\').'/plugins/neuroCore/data';
	}

	/**
	 * Читает мастер-ключ ('' — нет или повреждён).
	 *
	 * @param string $rootpath
	 *
	 * @return string
	 */
	public static function loadKey(string $rootpath): string {

		$path = self::keyPath($rootpath);

		if (!is_file($path)) {
			return '';
		}

		$key = trim((string)@file_get_contents($path));

		return preg_match('/^[0-9a-f]{'.self::KEY_HEX_LENGTH.'}$/i', $key) === 1 ? $key : '';

	}

	/**
	 * Создаёт мастер-ключ, если его ещё нет.
	 *
	 * @param string $rootpath
	 *
	 * @return string - '' если файл создать не удалось
	 */
	public static function ensureKey(string $rootpath): string {

		$key = self::loadKey($rootpath);

		if ($key !== '') {
			return $key;
		}

		$dir = self::keyDir($rootpath);

		if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
			return '';
		}

		$key = self::randomKey();

		if (@file_put_contents(self::keyPath($rootpath), $key) === false) {
			return '';
		}

		@chmod(self::keyPath($rootpath), 0600);

		return $key;

	}

	/**
	 * Маска для показа ключа в интерфейсе: sk-4d34…8d42.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function mask(string $value): string {

		$value = trim($value);
		$len   = mb_strlen($value);

		if ($len === 0) {
			return '';
		}

		if ($len <= 8) {
			return str_repeat('•', $len);
		}

		return mb_substr($value, 0, 4).'…'.mb_substr($value, -4);

	}

}
