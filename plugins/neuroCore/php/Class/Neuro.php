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
 * Фасад нейроядра — то, чем пользуются фичи CRM.
 *
 * Одна строка вместо знания о провайдерах, промптах и разборе ответов:
 *
 *     $neuro = new Neuro();
 *     $res   = $neuro->ask(['prompt' => 'deal.summary', 'tags' => [...], 'iduser' => $iduser1]);
 *
 * Возвращается всегда один и тот же нормализованный массив:
 *
 *     text, json, usage, model, latency_ms, error, schema_errors, retried,
 *     prompt, prompt_version, provider
 *
 * `error` заполняется при любой неудаче (нет промпта, нет ключа, отказ API,
 * пустой ответ) — фича обязана показать человеку причину, а не пустую строку.
 *
 * Устройство: `ask()` только достаёт промпт и подключение из базы, вся работа —
 * в `run()`, который принимает готовые данные и (для тестов) готовый провайдер.
 * `compose()`, `finish()` и `retryRequest()` — чистые функции, проверяются без
 * сети и базы.
 *
 * Class Neuro
 *
 * @package Neuro
 */
class Neuro {

	private $db;
	private $sqlname;
	private $identity;
	private $rootpath;

	/**
	 * @param object|null $db       - SafeMySQL
	 * @param string      $sqlname
	 * @param int         $identity
	 * @param string      $rootpath
	 */
	public function __construct($db = NULL, string $sqlname = '', int $identity = 0, string $rootpath = '') {

		$this->db       = $db !== NULL ? $db : ($GLOBALS['db'] ?? NULL);
		$this->sqlname  = $sqlname !== '' ? $sqlname : (string)($GLOBALS['sqlname'] ?? '');
		$this->identity = $identity > 0 ? $identity : (int)($GLOBALS['identity'] ?? 0);
		$this->rootpath = $rootpath !== '' ? $rootpath : (string)($GLOBALS['rootpath'] ?? '');

	}

	/**
	 * Вызов промпта из реестра.
	 *
	 * @param array $req - prompt (код), tags, iduser, provider_id, model, temperature,
	 *                     max_tokens, timeout, json_schema
	 *
	 * @return array
	 */
	public function ask(array $req): array {

		$code = Prompt::code((string)($req['prompt'] ?? ''));

		if ($code === '') {
			return self::failure('prompt_code', 'Не указан код промпта');
		}

		$prompt = Prompt::byCode($this->db, $this->sqlname, $code, $this->identity);

		if (empty($prompt)) {
			return self::failure('prompt_missing', 'Промпт «'.$code.'» не найден или выключен');
		}

		$providerRow = $this->resolveProvider($prompt, $req);

		if (empty($providerRow)) {

			Alerts::keyProblem($this->db, $this->sqlname, $this->identity, [], 'provider_missing');

			return self::failure('provider_missing', 'Не найдено ни одного включённого подключения к нейросети');

		}

		return $this->run($prompt, $providerRow, (array)($req['tags'] ?? []), $req);

	}

	/**
	 * Низкоуровневый вызов: без промпта из реестра.
	 *
	 * @param array $req - provider_id, messages, model, temperature, max_tokens, timeout
	 *
	 * @return array
	 */
	public function request(array $req): array {

		$providerRow = $this->resolveProvider([], $req);

		if (empty($providerRow)) {

			Alerts::keyProblem($this->db, $this->sqlname, $this->identity, [], 'provider_missing');

			return self::failure('provider_missing', 'Не найдено ни одного включённого подключения к нейросети');

		}

		try {

			$provider = Providers::make($providerRow, $this->rootpath);

		}
		catch (\RuntimeException $e) {

			// ключ не сохранён или не задана переменная окружения — это не «фича
			// что-то не то сделала», а сломанное подключение: администратор должен
			// узнать об этом, даже если вызов шёл из фона
			Alerts::keyProblem($this->db, $this->sqlname, $this->identity, $providerRow, 'provider_key');

			return self::failure('provider_key', $e->getMessage());

		}

		$out = $provider->chat($req);

		// вызов без промпта тоже расход: начисляем, иначе отчёт занижался бы
		$this->account($out, ['code' => (string)($req['code'] ?? '')], $req, $providerRow);

		self::alertOnFailure($this->db, $this->sqlname, $this->identity, $out, $providerRow);

		return $out;

	}

	/**
	 * Ядро: собрать запрос по промпту, вызвать провайдера, разобрать ответ.
	 *
	 * @param array         $prompt      - строка промпта (code, content, params, version)
	 * @param array         $providerRow - строка подключения
	 * @param array         $tags        - переменные шаблона
	 * @param array         $req         - переопределения (model, temperature, ...)
	 * @param object|null   $provider    - готовый провайдер (для тестов)
	 *
	 * @return array
	 */
	public function run(array $prompt, array $providerRow, array $tags, array $req = [], $provider = NULL): array {

		$params = array_merge(Prompt::params($prompt), array_intersect_key($req, [
			'model'       => 1,
			'temperature' => 1,
			'max_tokens'  => 1,
			'timeout'     => 1,
			'json_schema' => 1,
		]));

		$missing = Prompt::missing((string)($prompt['content'] ?? ''), $tags);

		if (!empty($missing)) {
			return self::failure('prompt_tags', 'В промпте не хватает переменных: '.implode(', ', $missing));
		}

		if ($provider === NULL) {

			try {

				$provider = Providers::make($providerRow, $this->rootpath);

			}
			catch (\RuntimeException $e) {

				Alerts::keyProblem($this->db, $this->sqlname, $this->identity, $providerRow, 'provider_key');

				return self::failure('provider_key', $e->getMessage());

			}

		}

		$request = self::compose($prompt, $tags, $params, $providerRow);

		// Кэш по отпечатку запроса: буквально тот же вопрос (тот же промпт, версия,
		// модель, температура и подставленные данные) не оплачивается второй раз.
		//
		// Отпечаток считается ДО маскирования: у разных клиентов маскированный текст
		// совпадает («позвонить [PHONE_1]»), и ключ по нему вернул бы одному клиенту
		// ответ, подготовленный для другого.
		$hash = '';
		$ttl  = Cache::settingsTtl();

		if ($this->db !== NULL && $ttl > 0) {

			$hash = Cache::key($prompt, $request, $providerRow);
			$hit  = Cache::get($this->db, $this->sqlname, $hash, $this->identity);

			if (!empty($hit)) {
				return self::fromCache($hit, $prompt, $providerRow);
			}

		}

		// Маскирование персональных данных — по решению владельца в подключении
		// (`pd_allowed = no`): модель видит структуру письма, но не телефоны, почту
		// и реквизиты; в ответе значения подставляются обратно.
		$pdAllowed = Providers::pdAllowed($providerRow);
		$maskMap   = [];

		$send = $request;

		if (!$pdAllowed) {
			$send = Pii::maskRequest($request, $maskMap);
		}

		$result = $provider->chat($send);

		if (!$pdAllowed && !empty($maskMap)) {

			$result = Pii::restoreResult($result, $maskMap);

			$this->logMasking($maskMap, $providerRow, $req);

		}

		$out = self::finish($result, $params);

		self::account($out, $prompt, $req, $providerRow);

		self::alertOnFailure($this->db, $this->sqlname, $this->identity, $out, $providerRow);

		// один повтор, если модель не попала в схему: дешевле спросить ещё раз,
		// чем возвращать фиче мусор
		if ($out['error'] === NULL && !empty($out['schema_errors'])) {

			// повтор идёт с той же (маскированной) перепиской и тем же словарём
			$retry  = self::retryRequest($send, (string)$out['text'], $out['schema_errors']);
			$answer = $provider->chat($retry);

			if (!$pdAllowed && !empty($maskMap)) {
				$answer = Pii::restoreResult($answer, $maskMap);
			}

			$second = self::finish($answer, $params);
			$second['retried'] = true;

			self::account($second, $prompt, $req, $providerRow);

			if (empty($second['schema_errors']) || $second['error'] !== NULL) {
				$out = $second;
			}

		}

		$out['prompt']         = (string)($prompt['code'] ?? '');
		$out['prompt_version'] = (int)($prompt['version'] ?? 0);
		$out['provider']       = (string)($providerRow['name'] ?? '');
		$out['cached']         = false;

		// В кэш кладём только удачный ответ: ошибку кэшировать нельзя, иначе
		// временный сбой провайдера будет повторяться из кэша весь срок жизни
		if ($hash !== '' && $out['error'] === NULL && trim((string)$out['text']) !== '') {

			Cache::put($this->db, $this->sqlname, $hash, [
				'code'        => (string)$out['prompt'],
				'model'       => (string)$out['model'],
				'answer'      => (string)$out['text'],
				'answer_json' => $out['json'] !== NULL ? (string)json_encode($out['json'], JSON_UNESCAPED_UNICODE) : NULL,
				'tokens_in'   => (int)$out['usage']['in'],
				'tokens_out'  => (int)$out['usage']['out'],
				'iduser'      => (int)($req['iduser'] ?? 0),
			], $ttl, $this->identity);

		}

		return $out;

	}

	/**
	 * Ответ из кэша в том же виде, что и обычный ответ фасада (чистая функция).
	 *
	 * `cached = true` и нулевая задержка говорят фиче и человеку, что вызова не
	 * было: интерфейс должен написать «взято из кэша», а не показывать новую
	 * задержку и не начислять расход.
	 *
	 * @param array $row         - строка neuro_cache
	 * @param array $prompt
	 * @param array $providerRow
	 *
	 * @return array
	 */
	public static function fromCache(array $row, array $prompt, array $providerRow = []): array {

		$json = trim((string)($row['answer_json'] ?? ''));

		return [
			'text'           => (string)($row['answer'] ?? ''),
			'json'           => $json !== '' ? json_decode($json, true) : NULL,
			'usage'          => [
				'in'   => (int)($row['tokens_in'] ?? 0),
				'out'  => (int)($row['tokens_out'] ?? 0),
				'cost' => NULL,
			],
			'model'          => (string)($row['model'] ?? ''),
			'latency_ms'     => 0,
			'error'          => NULL,
			'schema_errors'  => [],
			'retried'        => false,
			'prompt'         => (string)($prompt['code'] ?? ''),
			'prompt_version' => (int)($prompt['version'] ?? 0),
			'provider'       => (string)($providerRow['name'] ?? ''),
			'cached'         => true,
		];

	}

	/**
	 * Оповещение администратора, когда вызывать модель нечем (чистая проверка).
	 *
	 * Ошибка «нет ключа» приходит двумя путями: исключением при сборке адаптера
	 * (тогда оповещение отправляет вызывающий код) и кодом ошибки в ответе
	 * провайдера (401/403 — ключ отозван или не тот). Здесь разбирается второй
	 * путь; повторы ограничивает сам `Alerts`.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param array  $out         - результат finish()
	 * @param array  $providerRow
	 *
	 * @return void
	 */
	public static function alertOnFailure($db, string $sqlname, int $identity, array $out, array $providerRow = []): void {

		$code    = (string)($out['error']['code'] ?? '');
		$message = (string)($out['error']['message'] ?? '');

		// одного кода мало: недействительный ключ VseGPT приходит кодом 400, а под
		// тем же кодом бывает ошибка в самом запросе — её разбирает вызывающий
		if (!Alerts::looksLikeKeyProblem($code, $message)) {
			return;
		}

		Alerts::keyProblem($db, $sqlname, $identity, $providerRow, $code, $message);

	}

	/**
	 * Запись в журнал факта маскирования.
	 *
	 * Пишутся только виды данных и их количество — ни одного значения. Смысл
	 * записи: администратор видит, что данные клиентов уходили за контур в
	 * замаскированном виде, и что именно маскировалось.
	 *
	 * @param array $maskMap     - «плейсхолдер => значение»
	 * @param array $providerRow
	 * @param array $req
	 *
	 * @return void
	 */
	private function logMasking(array $maskMap, array $providerRow, array $req): void {

		if ($this->db === NULL) {
			return;
		}

		$summary = Pii::summary($maskMap);

		$parts = [];

		foreach ($summary as $kind => $count) {
			$parts[] = (Pii::KINDS[$kind] ?? $kind).': '.$count;
		}

		Log::write($this->db, $this->sqlname, 0, 'info', 'Персональные данные замаскированы перед отправкой', [
			'provider' => (string)($providerRow['name'] ?? ''),
			'masked'   => implode(', ', $parts),
			'total'    => count($maskMap),
			'iduser'   => (int)($req['iduser'] ?? 0),
		], $this->identity);

	}

	/**
	 * Начисление расхода за состоявшийся вызов модели.
	 *
	 * Считается каждый вызов, вернувший токены, — и первый, и повтор по схеме:
	 * провайдер выставит счёт за оба. Ошибки без токенов не начисляются.
	 *
	 * Валюта берётся из подключения: стоимость в отчёте — это сумма в валюте
	 * прайса, и без этой метки итог по разным подключениям складывать нельзя.
	 *
	 * @param array $out         - результат finish()
	 * @param array $prompt
	 * @param array $req
	 * @param array $providerRow - строка подключения (источник валюты прайса)
	 *
	 * @return void
	 */
	private function account(array $out, array $prompt, array $req, array $providerRow = []): void {

		if ($this->db === NULL) {
			return;
		}

		$in  = (int)($out['usage']['in'] ?? 0);
		$tokensOut = (int)($out['usage']['out'] ?? 0);

		if ($in <= 0 && $tokensOut <= 0) {
			return;
		}

		$prices = Providers::prices($providerRow);

		Usage::record($this->db, $this->sqlname, [
			'code'     => (string)($prompt['code'] ?? ''),
			'iduser'   => (int)($req['iduser'] ?? 0),
			'model'    => (string)($out['model'] ?? ''),
			'usage'    => $out['usage'],
			'currency' => (string)$prices['currency'],
		], $this->identity);

	}

	/**
	 * Сборка запроса к провайдеру по промпту (чистая функция).
	 *
	 * @param array $prompt
	 * @param array $tags
	 * @param array $params
	 * @param array $providerRow
	 *
	 * @return array
	 */
	public static function compose(array $prompt, array $tags, array $params, array $providerRow = []): array {

		$messages = [];

		$system = trim((string)($params['system'] ?? ''));

		if ($system !== '') {
			$messages[] = ['role' => 'system', 'content' => Prompt::render($system, $tags)];
		}

		$messages[] = ['role' => 'user', 'content' => Prompt::render((string)($prompt['content'] ?? ''), $tags)];

		$request = [
			'messages' => $messages,
			'model'    => self::requestModel($params, $providerRow),
		];

		foreach (['temperature' => 'float', 'max_tokens' => 'int', 'timeout' => 'int'] as $key => $cast) {

			if (isset($params[$key]) && $params[$key] !== '') {
				$request[$key] = $cast === 'int' ? (int)$params[$key] : (float)$params[$key];
			}

		}

		if (!empty($params['json_schema'])) {
			$request['json_schema'] = (array)$params['json_schema'];
		}

		return $request;

	}

	/**
	 * Модель для запроса (чистая функция).
	 *
	 * Пустое значение в параметрах промпта означает «как в подключении», а не
	 * «без модели»: форма промпта сохраняет поле модели как есть, и незаполненное
	 * поле раньше уходило провайдеру пустой строкой — DeepSeek отвечал
	 * `400 … but you passed .`, то есть вызов падал из-за пустого имени модели.
	 *
	 * @param array $params      - params промпта
	 * @param array $providerRow - строка подключения
	 *
	 * @return string
	 */
	public static function requestModel(array $params, array $providerRow = []): string {

		$model = trim((string)($params['model'] ?? ''));

		if ($model !== '') {
			return $model;
		}

		return trim((string)($providerRow['model'] ?? ''));

	}

	/**
	 * Разбор ответа провайдера и проверка по схеме (чистая функция).
	 *
	 * @param array $result - нормализованный ответ провайдера
	 * @param array $params - params промпта
	 *
	 * @return array
	 */
	public static function finish(array $result, array $params = []): array {

		$out = [
			'text'          => (string)($result['text'] ?? ''),
			'json'          => $result['json'] ?? NULL,
			'usage'         => [
				'in'   => (int)($result['usage']['in'] ?? 0),
				'out'  => (int)($result['usage']['out'] ?? 0),
				'cost' => $result['usage']['cost'] ?? NULL,
			],
			'model'         => (string)($result['model'] ?? ''),
			'latency_ms'    => (int)($result['latency_ms'] ?? 0),
			'error'         => $result['error'] ?? NULL,
			'schema_errors' => [],
			'retried'       => false,
		];

		$schema = $params['json_schema'] ?? NULL;

		if ($out['error'] !== NULL || empty($schema)) {
			return $out;
		}

		$data = $out['json'] !== NULL ? $out['json'] : Json::extract($out['text']);

		if ($data === NULL) {

			$out['schema_errors'] = ['ответ не содержит JSON'];

			return $out;

		}

		$body = isset($schema['schema']) && is_array($schema['schema']) ? $schema['schema'] : (array)$schema;

		$out['schema_errors'] = Json::validate($data, $body);

		if (empty($out['schema_errors'])) {
			$out['json'] = $data;
		}

		return $out;

	}

	/**
	 * Запрос на повтор, когда ответ не соответствует схеме (чистая функция).
	 *
	 * @param array  $request  - исходный запрос
	 * @param string $previous - что ответила модель
	 * @param array  $errors   - ошибки схемы
	 *
	 * @return array
	 */
	public static function retryRequest(array $request, string $previous, array $errors): array {

		$messages = (array)($request['messages'] ?? []);

		if (trim($previous) !== '') {
			$messages[] = ['role' => 'assistant', 'content' => $previous];
		}

		$messages[] = [
			'role'    => 'user',
			'content' => 'Ответ не соответствует требуемой схеме: '.implode('; ', $errors).'. Верни ТОЛЬКО JSON по схеме, без пояснений и без markdown.',
		];

		$request['messages'] = $messages;

		return $request;

	}

	/**
	 * Стандартный ответ с ошибкой.
	 *
	 * @param string $code
	 * @param string $message
	 *
	 * @return array
	 */
	public static function failure(string $code, string $message): array {

		return [
			'text'           => '',
			'json'           => NULL,
			'usage'          => ['in' => 0, 'out' => 0, 'cost' => NULL],
			'model'          => '',
			'latency_ms'     => 0,
			'error'          => ['code' => $code, 'message' => $message],
			'schema_errors'  => [],
			'retried'        => false,
			'prompt'         => '',
			'prompt_version' => 0,
			'provider'       => '',
			'cached'         => false,
		];

	}

	/**
	 * Подключение для вызова: указанное в запросе, в params промпта, либо первое включённое.
	 *
	 * @param array $prompt
	 * @param array $req
	 *
	 * @return array
	 */
	private function resolveProvider(array $prompt, array $req): array {

		$params = Prompt::params($prompt);

		$id = (int)($req['provider_id'] ?? ($params['provider_id'] ?? 0));

		if ($id > 0) {
			return Providers::byId($this->db, $this->sqlname, $id, $this->identity);
		}

		$rows = (array)$this->db->getAll("SELECT * FROM {$this->sqlname}neuro_provider WHERE active = 'on' AND identity = ?i ORDER BY id", $this->identity);

		return !empty($rows) ? (array)$rows[0] : [];

	}

}
