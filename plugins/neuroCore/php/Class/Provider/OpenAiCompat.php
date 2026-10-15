<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro\Provider;

use Neuro\Http;

/**
 * Универсальный адаптер для любого OpenAI-совместимого API
 * (POST {base_url}/chat/completions).
 *
 * Покрывает OpenAI, DeepSeek, OpenRouter, Groq, Mistral, Together, а также
 * Gemini через его OpenAI-совместимый слой. Подключение новой нейросети —
 * запись в настройках (base_url + модель + имя переменной окружения), код не меняется.
 *
 * Class OpenAiCompat
 *
 * @package Neuro\Provider
 */
class OpenAiCompat implements ProviderInterface {

	private $name;
	private $baseUrl;
	private $model;
	private $apiKey;
	private $params;

	/**
	 * @param string $name    - название подключения
	 * @param string $baseUrl - базовый адрес API
	 * @param string $model   - модель по умолчанию
	 * @param string $apiKey  - значение ключа (получено из окружения вызывающим кодом)
	 * @param array  $params  - temperature, max_tokens, timeout, price_in, price_out
	 */
	public function __construct(string $name, string $baseUrl, string $model, string $apiKey, array $params = []) {

		$this->name    = $name;
		$this->baseUrl = $baseUrl;
		$this->model   = $model;
		$this->apiKey  = $apiKey;
		$this->params  = $params;

	}

	/**
	 * Приводит базовый адрес к единому виду: https://api.deepseek.com и
	 * https://api.deepseek.com/v1 должны работать одинаково.
	 *
	 * @param string $url
	 *
	 * @return string
	 */
	public static function normalizeBaseUrl(string $url): string {

		$url = rtrim(trim($url), '/');

		$parts = parse_url($url);

		if ($parts === false || empty($parts['host'])) {
			return $url;
		}

		$path = isset($parts['path']) ? trim((string)$parts['path'], '/') : '';

		// путь не указан — это корень сервиса, добавляем версию API
		if ($path === '') {
			$url .= '/v1';
		}

		return $url;

	}

	public function code(): string {
		return 'openai';
	}

	public function name(): string {
		return $this->name;
	}

	public function model(): string {
		return $this->model;
	}

	public function endpoint(): string {
		return self::normalizeBaseUrl($this->baseUrl).'/chat/completions';
	}

	public function supportsJsonSchema(): bool {
		return true;
	}

	public function models(): array {
		return $this->model !== '' ? [$this->model] : [];
	}

	/**
	 * Заголовки запроса. Ключ уходит в заголовке Authorization, а не в URL:
	 * URL попадает в логи прокси и в историю.
	 *
	 * @return array
	 */
	private function headers(): array {
		return [
			'Content-Type: application/json',
			'Authorization: Bearer '.$this->apiKey,
		];
	}

	public function buildRequest(array $req): array {

		$body = [
			'model'    => (string)($req['model'] ?? $this->model),
			'messages' => array_values((array)($req['messages'] ?? [])),
		];

		$temperature = $req['temperature'] ?? ($this->params['temperature'] ?? NULL);
		$maxTokens   = $req['max_tokens'] ?? ($this->params['max_tokens'] ?? NULL);

		if ($temperature !== NULL) {
			$body['temperature'] = (float)$temperature;
		}

		if ($maxTokens !== NULL) {
			$body['max_tokens'] = (int)$maxTokens;
		}

		if (!empty($req['json_schema'])) {

			// Не все OpenAI-совместимые API принимают response_format: DeepSeek,
			// например, отвечает «This response_format type is unavailable now» и
			// роняет весь вызов. Поэтому по умолчанию режим выключен — схему
			// обеспечивают текст промпта, разбор ответа и повтор при несоответствии
			// (Neuro\Neuro::finish/retryRequest). Если провайдер умеет, режим
			// включается настройкой подключения.
			$mode = (string)($this->params['json_mode'] ?? 'off');

			if ($mode === 'json_schema') {

				$body['response_format'] = [
					'type'        => 'json_schema',
					'json_schema' => [
						'name'   => (string)($req['json_schema']['name'] ?? 'result'),
						'schema' => (array)($req['json_schema']['schema'] ?? []),
						'strict' => true,
					],
				];

			}
			elseif ($mode === 'json_object') {

				$body['response_format'] = ['type' => 'json_object'];

			}

		}

		return $body;

	}

	public function parseResponse(array $json): array {

		$out = [
			'text'   => '',
			'usage'  => ['in' => 0, 'out' => 0],
			'model'  => (string)($json['model'] ?? $this->model),
			'finish' => '',
			'error'  => NULL,
		];

		// ошибка, пришедшая внутри 200-ответа
		if (isset($json['error'])) {

			$message = is_array($json['error'])
				? (string)($json['error']['message'] ?? 'Неизвестная ошибка провайдера')
				: (string)$json['error'];

			$out['error'] = ['code' => 'api_error', 'message' => $message];

			return $out;

		}

		$choice = isset($json['choices'][0]) && is_array($json['choices'][0]) ? $json['choices'][0] : [];

		$out['finish'] = (string)($choice['finish_reason'] ?? '');

		$content = $choice['message']['content'] ?? '';

		// некоторые модели отдают массив частей вместо строки
		if (is_array($content)) {

			$parts = [];

			foreach ($content as $part) {
				if (is_array($part) && isset($part['text'])) {
					$parts[] = (string)$part['text'];
				}
			}

			$content = implode('', $parts);

		}

		$out['text'] = (string)$content;

		if (isset($json['usage']) && is_array($json['usage'])) {
			$out['usage'] = [
				'in'  => (int)($json['usage']['prompt_tokens'] ?? 0),
				'out' => (int)($json['usage']['completion_tokens'] ?? 0),
			];
		}

		// сначала отказ по политике: иначе пустой ответ выглядит как «модель промолчала»
		if ($out['finish'] === 'content_filter') {
			$out['error'] = ['code' => 'safety', 'message' => 'Ответ заблокирован фильтром провайдера'];
			return $out;
		}

		if (trim($out['text']) === '') {

			// Пусто, потому что модель упёрлась в лимит вывода (у «думающих» моделей
			// лимит съедают внутренние размышления) — это не молчание провайдера, и
			// человеку нужно сказать, что именно поправить.
			if ($out['finish'] === 'length') {

				$reasoning = (int)($json['usage']['completion_tokens_details']['reasoning_tokens'] ?? 0);

				$message = 'Модель израсходовала лимит вывода'.($reasoning > 0 ? ' ('.$reasoning.' токенов ушло на внутренние размышления)' : '').' и не успела ответить. Увеличьте «Макс. токенов» в промпте или в подключении.';

				$out['error'] = ['code' => 'empty', 'message' => $message];

				return $out;

			}

			$out['error'] = ['code' => 'empty', 'message' => 'Провайдер вернул пустой ответ'];

		}

		return $out;

	}

	public function parseError(int $httpCode, string $body): array {

		$message = self::extractMessage($body, $httpCode);

		// подсказка, иначе непонятно, что делать: ошибка приходит из-за параметра,
		// который мы отправили по настройке
		if (stripos($body, 'response_format') !== false || stripos($body, 'json_schema') !== false) {
			$message .= '. Похоже, провайдер не поддерживает строгий режим JSON — выключите его в настройках подключения (параметр «Режим JSON»)';
		}

		return [
			'code'    => Http::classify($httpCode),
			'message' => $message,
		];

	}

	/**
	 * Текст ошибки для человека: сообщение провайдера, иначе HTTP-код.
	 *
	 * @param string $body
	 * @param int    $httpCode
	 *
	 * @return string
	 */
	private static function extractMessage(string $body, int $httpCode = 0): string {

		$json = json_decode($body, true);

		// Некоторые сервисы (проверено на OpenAI-совместимом слое Gemini) отдают
		// ошибку объектом внутри массива: [{"error":{...}}]. Без разворачивания
		// администратор видел бы сырой JSON вместо причины отказа.
		if (is_array($json) && isset($json[0]) && is_array($json[0])) {
			$json = $json[0];
		}

		if (is_array($json)) {

			if (isset($json['error']['message'])) {
				return (string)$json['error']['message'];
			}

			if (isset($json['error']) && is_string($json['error'])) {
				return $json['error'];
			}

			if (isset($json['message'])) {
				return (string)$json['message'];
			}

		}

		$text = trim(mb_substr(strip_tags($body), 0, 500));

		if ($text === '' && $httpCode > 0) {
			$text = 'HTTP '.$httpCode;
		}

		return $text;

	}

	public function chat(array $req): array {

		$timeout = (int)($req['timeout'] ?? ($this->params['timeout'] ?? 0));

		$result = Http::post($this->endpoint(), $this->buildRequest($req), $this->headers(), $timeout);

		return $this->normalize($result, $req);

	}

	/**
	 * Запрос для проверки соединения (чистая функция — проверяется тестом).
	 *
	 * Лимит вывода не случайный: у «думающих» моделей он расходуется на внутренние
	 * размышления, и при малом значении проверка соединения показывает «провайдер
	 * вернул пустой ответ» на исправном подключении (проверено на живом Gemini,
	 * где ответ в одно слово потребовал 337 внутренних токенов).
	 *
	 * @return array
	 */
	public static function testRequest(): array {
		return [
			'messages'    => [['role' => 'user', 'content' => 'Ответь одним словом: работает?']],
			'max_tokens'  => 512,
			'temperature' => 0,
			'timeout'     => 30,
		];
	}

	public function test(): array {
		return $this->chat(self::testRequest());
	}

	public function cost(array $usage, string $model): ?float {
		return self::costOf($this->params, $usage);
	}

	/**
	 * Стоимость вызова по прайсу подключения (чистая функция — проверяется тестом).
	 *
	 * Две составляющие, потому что провайдеры выставляют счёт по-разному:
	 *  - `price_in`/`price_out` — за миллион токенов (счёт по расходу);
	 *  - `price_call` — за сам вызов, независимо от токенов. У VseGPT онлайн-модели
	 *    (Perplexity Sonar) берут фиксированную плату за поисковый запрос, и она
	 *    многократно больше стоимости токенов: без неё отчёт занижал бы расход
	 *    почти до нуля.
	 *
	 * Если модель израсходовала токены, а цена за токены не задана, сумма
	 * неизвестна целиком (NULL): показать только плату за вызов значило бы
	 * занизить расход, а придумать цену — соврать. Плата за вызов без расхода
	 * токенов считается и без цен за токены.
	 *
	 * @param array $params - params подключения
	 * @param array $usage  - ['in' => int, 'out' => int]
	 *
	 * @return float|null
	 */
	public static function costOf(array $params, array $usage): ?float {

		$in   = isset($params['price_in']) ? (float)$params['price_in'] : NULL;
		$out  = isset($params['price_out']) ? (float)$params['price_out'] : NULL;
		$call = isset($params['price_call']) ? (float)$params['price_call'] : NULL;

		$tokensIn  = (int)($usage['in'] ?? 0);
		$tokensOut = (int)($usage['out'] ?? 0);

		if ($tokensIn > 0 || $tokensOut > 0) {

			// без прайса за токены полная стоимость неизвестна
			if ($in === NULL || $out === NULL) {
				return NULL;
			}

			$cost = ($tokensIn / 1000000) * $in + ($tokensOut / 1000000) * $out;

			if ($call !== NULL) {
				$cost += $call;
			}

			return round($cost, 6);

		}

		// токенов нет: вызов всё равно мог стоить (поисковый запрос онлайн-модели)
		return $call !== NULL ? round($call, 6) : NULL;

	}

	/**
	 * Приводит результат HTTP-запроса к нормализованному ответу.
	 *
	 * @param array $result - ответ Http::post()
	 * @param array $req    - исходный запрос
	 *
	 * @return array
	 */
	private function normalize(array $result, array $req): array {

		$out = [
			'text'       => '',
			'json'       => NULL,
			'usage'      => ['in' => 0, 'out' => 0, 'cost' => NULL],
			'model'      => $this->model,
			'latency_ms' => (int)($result['latency_ms'] ?? 0),
			'raw'        => [],
			'error'      => NULL,
		];

		// сеть/таймаут
		if ($result['http_code'] === 0 || $result['error'] !== '') {

			$code = Http::classify((int)$result['http_code'], (string)$result['error']);

			$out['error'] = [
				'code'    => $code,
				'message' => $result['error'] !== '' ? $result['error'] : 'Не удалось соединиться с провайдером',
			];

			return $out;

		}

		// HTTP-ошибка
		if ((int)$result['http_code'] >= 400) {
			$out['error'] = $this->parseError((int)$result['http_code'], (string)$result['body']);
			return $out;
		}

		$json = json_decode((string)$result['body'], true);

		if (!is_array($json)) {

			$out['error'] = ['code' => 'parse', 'message' => 'Ответ провайдера не является JSON'];

			return $out;

		}

		$parsed = $this->parseResponse($json);

		$out['text']            = $parsed['text'];
		$out['model']           = $parsed['model'];
		$out['raw']             = $json;
		$out['error']           = $parsed['error'];
		$out['usage']['in']     = $parsed['usage']['in'];
		$out['usage']['out']    = $parsed['usage']['out'];
		$out['usage']['cost']   = $this->cost($parsed['usage'], $parsed['model']);

		if ($out['error'] === NULL && !empty($req['json_schema'])) {
			$out['json'] = self::decodeJson($out['text']);
		}

		return $out;

	}

	/**
	 * Извлекает JSON из текста ответа (модель может обернуть его в ```json).
	 * Полноценный разбор со схемой появится на этапе 2.
	 *
	 * @param string $text
	 *
	 * @return array|null
	 */
	private static function decodeJson(string $text) {

		$text = trim($text);

		if ($text === '') {
			return NULL;
		}

		$text = preg_replace('/^```(?:json)?\s*/i', '', $text);
		$text = preg_replace('/\s*```$/', '', (string)$text);

		$data = json_decode(trim((string)$text), true);

		return is_array($data) ? $data : NULL;

	}

}
