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
 * Нативный адаптер Google Gemini (generativelanguage.googleapis.com).
 *
 * Нужен там, где OpenAI-совместимый слой Gemini не отдаёт строгую JSON-схему
 * (responseSchema) или настройки безопасности. Ключ передаётся заголовком
 * x-goog-api-key, а не query-параметром ?key= — URL попадает в логи.
 *
 * Class Gemini
 *
 * @package Neuro\Provider
 */
class Gemini implements ProviderInterface {

	public const DEFAULT_BASE = 'https://generativelanguage.googleapis.com/v1beta';

	private $name;
	private $baseUrl;
	private $model;
	private $apiKey;
	private $params;

	public function __construct(string $name, string $baseUrl, string $model, string $apiKey, array $params = []) {

		$this->name    = $name;
		$this->baseUrl = $baseUrl !== '' ? $baseUrl : self::DEFAULT_BASE;
		$this->model   = $model;
		$this->apiKey  = $apiKey;
		$this->params  = $params;

	}

	public function code(): string {
		return 'gemini';
	}

	public function name(): string {
		return $this->name;
	}

	public function model(): string {
		return $this->model;
	}

	public function endpoint(): string {
		return rtrim(trim($this->baseUrl), '/').'/models/'.$this->model.':generateContent';
	}

	public function supportsJsonSchema(): bool {
		return true;
	}

	public function models(): array {
		return $this->model !== '' ? [$this->model] : [];
	}

	/**
	 * @return array
	 */
	private function headers(): array {
		return [
			'Content-Type: application/json',
			'x-goog-api-key: '.$this->apiKey,
		];
	}

	public function buildRequest(array $req): array {

		$contents      = [];
		$systemText    = '';

		foreach ((array)($req['messages'] ?? []) as $message) {

			$role    = isset($message['role']) ? (string)$message['role'] : 'user';
			$content = isset($message['content']) ? (string)$message['content'] : '';

			// системная инструкция у Gemini вынесена из диалога
			if ($role === 'system') {

				$systemText .= ($systemText !== '' ? "\n\n" : '').$content;
				continue;

			}

			$contents[] = [
				'role'  => $role === 'assistant' ? 'model' : 'user',
				'parts' => [['text' => $content]],
			];

		}

		$body = ['contents' => $contents];

		if ($systemText !== '') {
			$body['systemInstruction'] = ['parts' => [['text' => $systemText]]];
		}

		$generationConfig = [];

		$temperature = $req['temperature'] ?? ($this->params['temperature'] ?? NULL);
		$maxTokens   = $req['max_tokens'] ?? ($this->params['max_tokens'] ?? NULL);

		if ($temperature !== NULL) {
			$generationConfig['temperature'] = (float)$temperature;
		}

		if ($maxTokens !== NULL) {
			$generationConfig['maxOutputTokens'] = (int)$maxTokens;
		}

		if (!empty($req['json_schema'])) {

			// Как и в OpenAI-совместимом адаптере, режим включается настройкой:
			// лишние поля в generationConfig могут привести к отказу вызова.
			$mode = (string)($this->params['json_mode'] ?? 'off');

			if ($mode === 'json_schema' || $mode === 'json_object') {

				$generationConfig['responseMimeType'] = 'application/json';

				if ($mode === 'json_schema' && !empty($req['json_schema']['schema'])) {
					$generationConfig['responseSchema'] = (array)$req['json_schema']['schema'];
				}

			}

		}

		if (!empty($generationConfig)) {
			$body['generationConfig'] = $generationConfig;
		}

		return $body;

	}

	public function parseResponse(array $json): array {

		$out = [
			'text'   => '',
			'usage'  => ['in' => 0, 'out' => 0],
			'model'  => (string)($json['modelVersion'] ?? $this->model),
			'finish' => '',
			'error'  => NULL,
		];

		// запрос заблокирован до генерации
		if (!empty($json['promptFeedback']['blockReason'])) {
			$out['error'] = ['code' => 'safety', 'message' => 'Запрос заблокирован политикой безопасности: '.$json['promptFeedback']['blockReason']];
			return $out;
		}

		if (isset($json['error'])) {

			$message = is_array($json['error'])
				? (string)($json['error']['message'] ?? 'Неизвестная ошибка провайдера')
				: (string)$json['error'];

			$out['error'] = ['code' => 'api_error', 'message' => $message];

			return $out;

		}

		$candidate = isset($json['candidates'][0]) && is_array($json['candidates'][0]) ? $json['candidates'][0] : [];

		$out['finish'] = (string)($candidate['finishReason'] ?? '');

		$text = '';

		if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
			foreach ($candidate['content']['parts'] as $part) {
				if (is_array($part) && isset($part['text'])) {
					$text .= (string)$part['text'];
				}
			}
		}

		$out['text'] = $text;

		if (isset($json['usageMetadata']) && is_array($json['usageMetadata'])) {

			// У «думающих» моделей Gemini внутренние размышления НЕ входят в
			// candidatesTokenCount, но оплачиваются как выходные токены. Без
			// прибавления thoughtsTokenCount расход в отчёте занижался бы
			// (проверено на живом ответе: 10 входных, 13 размышлений, ответа нет).
			$out['usage'] = [
				'in'  => (int)($json['usageMetadata']['promptTokenCount'] ?? 0),
				'out' => (int)($json['usageMetadata']['candidatesTokenCount'] ?? 0) + (int)($json['usageMetadata']['thoughtsTokenCount'] ?? 0),
			];

		}

		if ($out['finish'] === 'SAFETY' || $out['finish'] === 'PROHIBITED_CONTENT') {
			$out['error'] = ['code' => 'safety', 'message' => 'Ответ заблокирован политикой безопасности'];
			return $out;
		}

		if (trim($out['text']) === '') {

			// Пустой ответ при MAX_TOKENS — это не молчание провайдера: модель
			// упёрлась в лимит вывода (у думающих моделей его съедают размышления).
			if ($out['finish'] === 'MAX_TOKENS') {

				$thoughts = (int)($json['usageMetadata']['thoughtsTokenCount'] ?? 0);

				$message = 'Модель израсходовала лимит вывода'.($thoughts > 0 ? ' ('.$thoughts.' токенов ушло на внутренние размышления)' : '').' и не успела ответить. Увеличьте «Макс. токенов» в промпте или в подключении.';

				$out['error'] = ['code' => 'empty', 'message' => $message];

				return $out;

			}

			$out['error'] = ['code' => 'empty', 'message' => 'Провайдер вернул пустой ответ'];

		}

		return $out;

	}

	public function parseError(int $httpCode, string $body): array {

		$json    = json_decode($body, true);
		$message = '';

		// тот же случай, что и в OpenAI-совместимом адаптере: ошибка может прийти
		// объектом внутри массива
		if (is_array($json) && isset($json[0]) && is_array($json[0])) {
			$json = $json[0];
		}

		if (is_array($json) && isset($json['error']['message'])) {
			$message = (string)$json['error']['message'];
		}

		if ($message === '') {
			$message = trim(mb_substr(strip_tags($body), 0, 500));
		}

		if ($message === '' && $httpCode > 0) {
			$message = 'HTTP '.$httpCode;
		}

		return ['code' => Http::classify($httpCode), 'message' => $message];

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
	 * размышления. На живом API у gemini-flash-latest на ответ в одно слово ушло
	 * 337 внутренних токенов — при лимите в 16 токенов проверка соединения
	 * показывала «провайдер вернул пустой ответ» на полностью исправном ключе.
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

		// правило одно на все адаптеры: цена за токены + возможная плата за вызов
		return OpenAiCompat::costOf($this->params, $usage);

	}

	/**
	 * @param array $result - ответ Http::post()
	 * @param array $req
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

		if ($result['http_code'] === 0 || $result['error'] !== '') {

			$code = Http::classify((int)$result['http_code'], (string)$result['error']);

			$out['error'] = [
				'code'    => $code,
				'message' => $result['error'] !== '' ? $result['error'] : 'Не удалось соединиться с провайдером',
			];

			return $out;

		}

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

		$out['text']          = $parsed['text'];
		$out['model']         = $parsed['model'];
		$out['raw']           = $json;
		$out['error']         = $parsed['error'];
		$out['usage']['in']   = $parsed['usage']['in'];
		$out['usage']['out']  = $parsed['usage']['out'];
		$out['usage']['cost'] = $this->cost($parsed['usage'], $parsed['model']);

		if ($out['error'] === NULL && !empty($req['json_schema'])) {

			$text = trim($out['text']);
			$text = preg_replace('/^```(?:json)?\s*/i', '', $text);
			$text = preg_replace('/\s*```$/', '', (string)$text);

			$data = json_decode(trim((string)$text), true);

			$out['json'] = is_array($data) ? $data : NULL;

		}

		return $out;

	}

}
