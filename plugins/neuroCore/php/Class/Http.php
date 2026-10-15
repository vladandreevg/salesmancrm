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
 * HTTP-слой Нейроядра.
 *
 * Отдельная обёртка, а не SendRequestCurl() из ядра: тот отключает проверку
 * TLS-сертификата (inc/func.php), то есть ключ провайдера уходил бы по
 * неверифицированному каналу. Здесь проверка сертификата обязательна и
 * собирается чистой функцией buildOptions() — проверяется тестом без сети.
 *
 * Class Http
 *
 * @package Neuro
 */
class Http {

	public const DEFAULT_TIMEOUT = 30;
	public const MIN_TIMEOUT     = 5;
	public const MAX_TIMEOUT     = 300;

	/**
	 * Опции запроса (чистая функция).
	 *
	 * @param array $headers - готовые строки заголовков
	 * @param int   $timeout - 0 = по умолчанию; значения вне [MIN, MAX] нормализуются
	 *
	 * @return array
	 */
	public static function buildOptions(array $headers = [], int $timeout = 0): array {

		if ($timeout < self::MIN_TIMEOUT) {
			$timeout = self::DEFAULT_TIMEOUT;
		}

		if ($timeout > self::MAX_TIMEOUT) {
			$timeout = self::MAX_TIMEOUT;
		}

		return [
			'verify'          => true,
			'verify_host'     => 2,
			'timeout'         => $timeout,
			'headers'         => array_values($headers),
			'return_transfer' => true,
		];

	}

	/**
	 * Классификация результата: код ошибки для очереди и для человека.
	 *
	 * @param int    $httpCode
	 * @param string $curlError
	 *
	 * @return string - ok|http_400|http_401|http_403|http_404|http_429|http_5xx|http_4xx|timeout|network
	 */
	public static function classify(int $httpCode, string $curlError = ''): string {

		if ($httpCode === 0) {

			$error = strtolower($curlError);

			if (strpos($error, 'timed out') !== false || strpos($error, 'timeout') !== false) {
				return 'timeout';
			}

			return 'network';

		}

		if ($httpCode >= 200 && $httpCode < 300) {
			return 'ok';
		}

		if ($httpCode >= 500) {
			return 'http_5xx';
		}

		if (in_array($httpCode, [400, 401, 403, 404, 429], true)) {
			return 'http_'.$httpCode;
		}

		if ($httpCode >= 400) {
			return 'http_4xx';
		}

		return 'network';

	}

	/**
	 * Стоит ли повторять запрос с этим кодом ошибки.
	 *
	 * @param string $code
	 *
	 * @return bool
	 */
	public static function isRetryable(string $code): bool {
		return in_array($code, ['http_429', 'http_5xx', 'timeout', 'network'], true);
	}

	/**
	 * POST-запрос. Ключи передаются заголовком, а не в URL.
	 *
	 * @param string       $url
	 * @param array|string $body
	 * @param array        $headers
	 * @param int          $timeout
	 *
	 * @return array - http_code, body, error, latency_ms, code
	 */
	public static function post(string $url, $body, array $headers = [], int $timeout = 0): array {

		$options = self::buildOptions($headers, $timeout);

		$payload = is_array($body) ? json_encode($body, JSON_UNESCAPED_UNICODE) : (string)$body;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $options['return_transfer']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options['verify_host']);
		curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

		$started = microtime(true);

		$response = curl_exec($ch);
		$info     = curl_getinfo($ch);
		$error    = curl_error($ch);

		curl_close($ch);

		$httpCode = isset($info['http_code']) ? (int)$info['http_code'] : 0;

		return [
			'http_code'  => $httpCode,
			'body'       => is_string($response) ? $response : '',
			'error'      => (string)$error,
			'latency_ms' => (int)round((microtime(true) - $started) * 1000),
			'code'       => self::classify($httpCode, (string)$error),
		];

	}

}
