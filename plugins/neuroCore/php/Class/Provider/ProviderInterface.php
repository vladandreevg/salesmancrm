<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro\Provider;

/**
 * Контракт провайдера нейросети.
 *
 * Ядро работает с нормализованным запросом и нормализованным ответом:
 *
 *  запрос:  ['messages' => [['role' => system|user|assistant, 'content' => string], ...],
 *            'temperature' => float, 'max_tokens' => int,
 *            'json_schema' => ['name' => string, 'schema' => array], 'timeout' => int]
 *
 *  ответ:   ['text' => string, 'json' => array|null,
 *            'usage' => ['in' => int, 'out' => int, 'cost' => float|null],
 *            'model' => string, 'latency_ms' => int, 'raw' => array,
 *            'error' => null|['code' => string, 'message' => string]]
 *
 * Благодаря этому фича CRM не знает, какая именно нейросеть подключена, а новая
 * нейросеть с OpenAI-совместимым API добавляется настройкой, без правок кода.
 *
 * Interface ProviderInterface
 *
 * @package Neuro\Provider
 */
interface ProviderInterface {

	/**
	 * Тип подключения: openai | gemini.
	 *
	 * @return string
	 */
	public function code(): string;

	/**
	 * Название подключения (для интерфейса и логов).
	 *
	 * @return string
	 */
	public function name(): string;

	/**
	 * Адрес вызова.
	 *
	 * @return string
	 */
	public function endpoint(): string;

	/**
	 * Тело запроса в формате провайдера (чистая функция — проверяется тестом).
	 *
	 * @param array $req - нормализованный запрос
	 *
	 * @return array
	 */
	public function buildRequest(array $req): array;

	/**
	 * Разбор успешного ответа (чистая функция).
	 *
	 * @param array $json - декодированный ответ провайдера
	 *
	 * @return array - text, usage, model, finish, error
	 */
	public function parseResponse(array $json): array;

	/**
	 * Разбор ошибки по HTTP-коду и телу ответа (чистая функция).
	 *
	 * @param int    $httpCode
	 * @param string $body
	 *
	 * @return array - ['code' => string, 'message' => string]
	 */
	public function parseError(int $httpCode, string $body): array;

	/**
	 * Выполнить запрос и вернуть нормализованный ответ.
	 *
	 * @param array $req
	 *
	 * @return array
	 */
	public function chat(array $req): array;

	/**
	 * Доступные модели (для интерфейса).
	 *
	 * @return array
	 */
	public function models(): array;

	/**
	 * Умеет ли провайдер строгую JSON-схему ответа.
	 *
	 * @return bool
	 */
	public function supportsJsonSchema(): bool;

	/**
	 * Минимальный проверочный вызов для кнопки «Проверить».
	 *
	 * @return array - нормализованный ответ
	 */
	public function test(): array;

	/**
	 * Стоимость вызова. null, если прайс не задан пользователем: выдумывать
	 * цену чужой модели нельзя.
	 *
	 * @param array  $usage - ['in' => int, 'out' => int]
	 * @param string $model
	 *
	 * @return float|null
	 */
	public function cost(array $usage, string $model): ?float;

}
