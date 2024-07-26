<?php

namespace Novofon_API;

use Exception;

class Client {
	public const PROD_URL = 'https://api.novofon.com';

	private $_url;
	private $_key;
	private $_secret;
	private $_httpCode;
	private $_limits = [];

	/**
	 * @param $key
	 * @param $secret
	 * @param bool|false $isSandbox
	 */
	public function __construct($key, $secret) {
		$this -> _url    = self::PROD_URL;
		$this -> _key    = $key;
		$this -> _secret = $secret;
	}

	/**
	 * @param $method - API method, including version number
	 * @param array $params - Query params
	 * @param string $requestType - (get|post|put|delete)
	 * @param string $format - (json|xml)
	 * @param bool|bool $isAuth
	 *
	 * @return mixed
	 * @throws Exception
	 *
	 */
	public function call($method, array $params = [], string $requestType = 'get', string $format = 'json', bool $isAuth = true) {

		if (!is_array($params)) {
			throw new \RuntimeException('Query params must be an array.');
		}

		$type = strtoupper($requestType);
		if (!in_array($type, [
			'GET',
			'POST',
			'PUT',
			'DELETE'
		])) {
			$type = 'GET';
		}
		$params['format'] = $format;

		$options = [
			CURLOPT_URL            => $this -> _url.$method,
			CURLOPT_CUSTOMREQUEST  => $type,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADERFUNCTION => [
				$this,
				'_parseHeaders'
			]
		];

		$ch = curl_init();

		if ($type == 'GET') {
			$options[CURLOPT_URL] = $this -> _url.$method.'?'.http_build_query($params);
		}
		else {
			$options[CURLOPT_POST]       = true;
			$options[CURLOPT_POSTFIELDS] = http_build_query($params);
		}

		if ($isAuth) {
			$options[CURLOPT_HTTPHEADER] = $this -> _getAuthHeader($method, $params);
		}

		curl_setopt_array($ch, $options);

		$response = curl_exec($ch);
		$error    = curl_error($ch);

		$this -> _httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		if ($error) {
			throw new \RuntimeException($error);
		}

		return $response;
	}

	/**
	 * @return int
	 */
	public function getHttpCode(): int {
		return $this -> _httpCode;
	}

	/**
	 * @return array
	 */
	public function getLimits(): array {
		return $this -> _limits;
	}

	/**
	 * @param $method
	 * @param $params
	 *
	 * @return array
	 */
	private function _getAuthHeader($method, $params): array {

		ksort($params);
		$paramsString = http_build_query($params);
		$signature    = base64_encode(hash_hmac('sha1', $method.$paramsString.md5($paramsString), $this -> _secret));

		return ['Authorization: '.$this -> _key.':'.$signature];

	}

	/**
	 * @param $curl
	 * @param $line
	 *
	 * @return int
	 */

	private function _parseHeaders($curl, $line): int {

		if (preg_match('/^X-RateLimit-([a-z]+):\s([0-9]+)/i', $line, $match)) {
			$this -> _limits[$match[1]] = (int)$match[2];
		}

		return strlen($line);

	}

}
