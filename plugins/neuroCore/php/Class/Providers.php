<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

use Neuro\Provider\ProviderInterface;

/**
 * Подключения к нейросетям: валидация настроек, чтение ключа из окружения
 * и сборка адаптера.
 *
 * Значение ключа в базе не хранится: в neuro_provider лежит только ИМЯ переменной
 * окружения. Всё, что связано с разбором настроек и окружения, — чистые функции,
 * проверяемые тестом без БД (.dsh/tests/neuro_provider_test.php).
 *
 * Class Providers
 *
 * @package Neuro
 */
class Providers {

	/**
	 * Типы подключений.
	 */
	public const CODES = [
		'openai' => 'OpenAI-совместимый API',
		'gemini' => 'Google Gemini (нативный)',
	];

	/**
	 * Откуда берётся ключ.
	 */
	public const SOURCES = [
		'env' => 'Переменная окружения сервера',
		'db'  => 'Хранить в CRM (зашифрованно)',
	];

	/**
	 * Источник по умолчанию.
	 *
	 * Хранение в CRM выбрано как основной вариант по просьбе владельца продукта:
	 * задавать переменную окружения требуется не всегда возможно (нужен доступ к
	 * серверу), а подключение должно настраиваться из интерфейса. Ключ при этом
	 * лежит в базе зашифрованным (см. Neuro\Crypto), а переменная окружения
	 * остаётся доступной как более защищённый вариант.
	 */
	public const DEFAULT_SOURCE = 'db';

	/**
	 * Тип задачи для проверки соединения.
	 *
	 * Кнопка «Проверить» делает настоящий запрос к API, то есть тратит деньги
	 * владельца. Такой вызов попадает в отчёт по расходу под этим кодом — иначе
	 * он был бы невидимой тратой, а отчёт перестал бы отвечать на вопрос
	 * «сколько я потратил».
	 */
	public const TEST_CODE = 'core.provider.test';

	/**
	 * Минимальный проверочный запрос для выбранного типа подключения.
	 *
	 * @param array $row - строка подключения
	 *
	 * @return array
	 */
	public static function testRequest(array $row): array {

		return (string)($row['code'] ?? '') === 'gemini'
			? Provider\Gemini::testRequest()
			: Provider\OpenAiCompat::testRequest();

	}

	/**
	 * @return array
	 */
	public static function codes(): array {
		return self::CODES;
	}

	/**
	 * @return array
	 */
	public static function sources(): array {
		return self::SOURCES;
	}

	/**
	 * Человекочитаемое название типа подключения.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public static function codeTitle(string $code): string {
		return self::CODES[$code] ?? $code;
	}

	/**
	 * Проверка настроек подключения перед сохранением.
	 *
	 * @param array $row
	 * @param bool  $hasStoredKey - есть ли уже сохранённый в CRM ключ (при изменении
	 *                              поле ключа можно оставить пустым)
	 *
	 * @return array - [поле => сообщение]; пустой массив = ошибок нет
	 */
	public static function validate(array $row, bool $hasStoredKey = false): array {

		$errors = [];

		if (trim((string)($row['name'] ?? '')) === '') {
			$errors['name'] = 'Укажите название подключения';
		}

		$code = (string)($row['code'] ?? '');

		if (!isset(self::CODES[$code])) {
			$errors['code'] = 'Выберите тип подключения';
		}

		$url = trim((string)($row['base_url'] ?? ''));

		if ($url === '' || preg_match('#^https?://[^\s]+$#i', $url) !== 1) {
			$errors['base_url'] = 'Адрес API должен начинаться с http:// или https://';
		}

		if (trim((string)($row['model'] ?? '')) === '') {
			$errors['model'] = 'Укажите модель';
		}

		$source = (string)($row['key_source'] ?? self::DEFAULT_SOURCE);

		if (!isset(self::SOURCES[$source])) {

			$errors['key_source'] = 'Выберите, откуда брать ключ';

		}
		elseif ($source === 'env') {

			$env = trim((string)($row['api_key_env'] ?? ''));

			if (preg_match('/^[A-Z][A-Z0-9_]{2,99}$/', $env) !== 1) {
				$errors['api_key_env'] = self::envNameHint($env);
			}

		}
		elseif (!$hasStoredKey && trim((string)($row['api_key'] ?? '')) === '') {

			$errors['api_key'] = 'Укажите ключ API — он будет сохранён в CRM в зашифрованном виде';

		}

		return $errors;

	}

	/**
	 * Состояние ключа подключения для интерфейса: откуда берётся, есть ли он и
	 * как его показать (маска вместо значения).
	 *
	 * @param array  $row
	 * @param string $rootpath
	 *
	 * @return array - ['source' => string, 'set' => bool, 'hint' => string, 'mask' => string]
	 */
	public static function keyInfo(array $row, string $rootpath = ''): array {

		$source = (string)($row['key_source'] ?? self::DEFAULT_SOURCE);

		$out = ['source' => $source, 'set' => false, 'hint' => '', 'mask' => ''];

		if ($source === 'db') {

			$payload = trim((string)($row['api_key_enc'] ?? ''));

			if ($payload === '') {
				$out['hint'] = 'ключ не сохранён';
				return $out;
			}

			$rootpath = $rootpath !== '' ? $rootpath : (string)($GLOBALS['rootpath'] ?? '');

			if ($rootpath === '' || Crypto::loadKey($rootpath) === '') {
				$out['hint'] = 'ключ сохранён, но файл ключа шифрования недоступен';
				return $out;
			}

			$plain = Crypto::decrypt($payload, Crypto::loadKey($rootpath));

			if ($plain === '') {
				$out['hint'] = 'ключ не расшифровывается: файл ключа шифрования заменён';
				return $out;
			}

			$out['set']  = true;
			$out['mask'] = Crypto::mask($plain);
			$out['hint'] = 'ключ сохранён в CRM: '.$out['mask'];

			return $out;

		}

		$env = trim((string)($row['api_key_env'] ?? ''));

		$out['set']  = self::resolveKey($env) !== '';
		$out['hint'] = $out['set'] ? 'переменная '.$env.' видна' : 'переменная '.$env.' не задана';

		return $out;

	}

	/**
	 * Ключ для подключения — из выбранного источника.
	 *
	 * @param array  $row
	 * @param string $rootpath
	 *
	 * @return string
	 * @throws \RuntimeException - когда причина отказа понятна человеку
	 */
	public static function keyFor(array $row, string $rootpath = ''): string {

		$source = (string)($row['key_source'] ?? self::DEFAULT_SOURCE);

		if ($source === 'db') {

			$payload = trim((string)($row['api_key_enc'] ?? ''));

			if ($payload === '') {
				throw new \RuntimeException('Ключ не сохранён: заполните поле «Ключ API» в подключении');
			}

			$rootpath = $rootpath !== '' ? $rootpath : (string)($GLOBALS['rootpath'] ?? '');

			if ($rootpath === '') {
				throw new \RuntimeException('Не известен путь установки — невозможно прочитать файл ключа шифрования');
			}

			if (!Crypto::available()) {

				throw new \RuntimeException('В этой сборке PHP нет openssl с поддержкой AES-256-GCM: сохраните ключ в переменной окружения либо включите модуль openssl');

			}

			if (Crypto::loadKey($rootpath) === '') {

				throw new \RuntimeException('Не найден файл ключа шифрования plugins/neuroCore/data/'.Crypto::KEY_FILE.'. Если база восстанавливалась из копии, восстановите и этот файл — иначе сохраните ключ API заново');

			}

			$plain = Crypto::decrypt($payload, Crypto::loadKey($rootpath));

			if ($plain === '') {
				throw new \RuntimeException('Сохранённый ключ не расшифровывается: файл ключа шифрования заменён. Сохраните ключ API заново');
			}

			return $plain;

		}

		return self::resolveKey((string)($row['api_key_env'] ?? ''));

	}

	/**
	 * Значение ключа из окружения сервера.
	 *
	 * Источников три, и это важно: Apache с mod_php кладёт значения SetEnv в
	 * $_SERVER, а не в окружение процесса, nginx + php-fpm (env[...] в пуле) — в
	 * окружение, а часть конфигураций выставляет $_ENV. Опрашиваем все три.
	 *
	 * @param string     $envName
	 * @param array|null $server - $_SERVER (для тестов)
	 * @param array|null $env    - $_ENV (для тестов)
	 *
	 * @return string - '' если переменная не задана
	 */
	public static function resolveKey(string $envName, array $server = NULL, array $env = NULL): string {

		$envName = trim($envName);

		if ($envName === '') {
			return '';
		}

		$value = getenv($envName);

		if ($value !== false && trim((string)$value) !== '') {
			return trim((string)$value);
		}

		$sources = [
			$server === NULL ? (array)$_SERVER : $server,
			$env === NULL ? (array)$_ENV : $env,
		];

		foreach ($sources as $source) {

			if (isset($source[$envName]) && is_scalar($source[$envName]) && trim((string)$source[$envName]) !== '') {
				return trim((string)$source[$envName]);
			}

		}

		return '';

	}

	/**
	 * Состояние переменной окружения для интерфейса: видно ли значение PHP.
	 *
	 * @param string $envName
	 *
	 * @return array - ['name' => string, 'set' => bool]
	 */
	public static function keyStatus(string $envName): array {
		return ['name' => $envName, 'set' => self::resolveKey($envName) !== ''];
	}

	/**
	 * Имена переменных, которые PHP реально видит и которые похожи на ключи API.
	 *
	 * Нужны, чтобы имя не приходилось угадывать: администратор выбирает готовое.
	 * Значения переменных здесь не участвуют — только имена.
	 *
	 * @param array|null $server - $_SERVER (для тестов)
	 * @param int        $limit
	 *
	 * @return array
	 */
	public static function candidateNames(array $server = NULL, int $limit = 50): array {

		$server = $server === NULL ? (array)$_SERVER : $server;

		$env = getenv();

		$names = array_merge(
			is_array($env) ? array_keys($env) : [],
			array_keys($server),
			array_keys((array)$_ENV)
		);

		$out = [];

		foreach ($names as $name) {

			$name = (string)$name;

			if ($name === '' || strlen($name) > 60) {
				continue;
			}

			// служебные переменные сервера не показываем — только то, что похоже на ключ
			if (preg_match('/^(HTTP_|PHP_|SERVER_|REDIRECT_|SCRIPT_|REQUEST_|QUERY_|DOCUMENT_|GATEWAY_|REMOTE_|CONTENT_|PATH|PATHEXT|SYSTEMROOT|WINDIR|COMSPEC|TEMP|TMP|OS|USER|HOME|HOSTNAME|TERM|SHELL|LANG)/i', $name) === 1) {
				continue;
			}

			if (preg_match('/KEY|TOKEN|SECRET|API/i', $name) !== 1) {
				continue;
			}

			$out[$name] = $name;

		}

		ksort($out);

		return array_slice(array_values($out), 0, $limit);

	}

	/**
	 * Подсказка к неверно заполненному полю «Имя переменной окружения».
	 *
	 * Поле подписано так, что в него вставляют значение ключа целиком (и даже строку
	 * NAME=значение). Сообщение должно объяснять это, а не просто «неверный формат».
	 *
	 * @param string $env
	 *
	 * @return string
	 */
	public static function envNameHint(string $env): string {

		if (strpos($env, '=') !== false) {
			return 'Здесь нужно только имя переменной, без значения. Например: DEEPSEEK_API_KEY — а строку с ключом добавьте в настройки сервера';
		}

		if (preg_match('/\s/', $env) === 1) {
			return 'Имя переменной не содержит пробелов. Например: DEEPSEEK_API_KEY';
		}

		if (preg_match('/^(sk-|AIza|gsk_|xai-)/i', $env) === 1 || (strlen($env) > 24 && preg_match('/[a-z]/', $env) === 1)) {
			return 'Похоже, вы вставили значение ключа. CRM его не хранит: укажите только имя переменной (например DEEPSEEK_API_KEY), а значение задайте в окружении сервера';
		}

		return 'Имя переменной окружения: заглавные латинские буквы, цифры и подчёркивание, например DEEPSEEK_API_KEY';

	}

	/**
	 * Разбор params подключения (JSON) в массив.
	 *
	 * @param array $row
	 *
	 * @return array
	 */
	public static function params(array $row): array {

		$params = $row['params'] ?? [];

		if (is_array($params)) {
			return $params;
		}

		$raw = trim((string)$params);

		if ($raw === '') {
			return [];
		}

		$data = json_decode($raw, true);

		return is_array($data) ? $data : [];

	}

	/**
	 * Сборка params из данных формы (пустые поля не сохраняем).
	 *
	 * @param array $request
	 *
	 * @return array
	 */
	public static function collectParams(array $request): array {

		$params = [];

		foreach (['temperature', 'price_in', 'price_out', 'price_call'] as $key) {
			if (isset($request[$key]) && trim((string)$request[$key]) !== '') {
				$params[$key] = (float)$request[$key];
			}
		}

		foreach (['max_tokens', 'timeout'] as $key) {
			if (isset($request[$key]) && trim((string)$request[$key]) !== '') {
				$params[$key] = (int)$request[$key];
			}
		}

		// валюта, в которой заданы цены: без неё в отчёте нельзя складывать
		// подключения с разными прайсами (рубли VseGPT и доллары DeepSeek)
		$currency = self::currency(isset($request['currency']) ? (string)$request['currency'] : '');

		if ($currency !== '') {
			$params['currency'] = $currency;
		}

		// режим JSON: off (по умолчанию) | json_object | json_schema.
		// По умолчанию выключен: не все OpenAI-совместимые API принимают
		// response_format, и лишний параметр роняет вызов целиком.
		$mode = (string)(isset($request['json_mode']) ? $request['json_mode'] : '');

		if (in_array($mode, ['json_object', 'json_schema'], true)) {
			$params['json_mode'] = $mode;
		}

		return $params;

	}

	/**
	 * Валюта прайса: короткая метка для отчёта (₽, $, €, руб, USD).
	 *
	 * Пустое значение — «валюта не указана»: суммы в отчёте показываются без
	 * символа, как было до появления этого поля.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function currency(string $value): string {

		$value = trim($value);

		// убираем всё, что может сломать разметку отчёта: метка короткая
		$value = preg_replace('/[\x00-\x1F<>"\']/u', '', $value);
		$value = trim((string)$value);

		return mb_substr($value, 0, 8);

	}

	/**
	 * Прайс подключения в виде, пригодном для расчёта и оценки (чистая функция).
	 *
	 * @param array $params - params подключения (или сама строка подключения)
	 *
	 * @return array - in, out, call, currency
	 */
	public static function prices(array $params): array {

		if (isset($params['params'])) {
			$params = self::params($params);
		}

		return [
			'in'       => isset($params['price_in']) ? (float)$params['price_in'] : NULL,
			'out'      => isset($params['price_out']) ? (float)$params['price_out'] : NULL,
			'call'     => isset($params['price_call']) ? (float)$params['price_call'] : NULL,
			'currency' => self::currency(isset($params['currency']) ? (string)$params['currency'] : ''),
		];

	}

	/**
	 * Разрешена ли отправка персональных данных в это подключение (чистая функция).
	 *
	 * По умолчанию — разрешена: владелец сам решает, уходят ли данные клиентов на
	 * сервер провайдера. Значение `no` включает маскирование (Neuro\Pii): телефоны,
	 * почта, ФИО и реквизиты заменяются плейсхолдерами с обратной подстановкой.
	 *
	 * @param array $row - строка neuro_provider
	 *
	 * @return bool
	 */
	public static function pdAllowed(array $row): bool {
		return strtolower(trim((string)($row['pd_allowed'] ?? 'yes'))) !== 'no';
	}

	/**
	 * Адаптер по строке подключения.
	 *
	 * @param array $row - строка neuro_provider
	 *
	 * @return ProviderInterface
	 * @throws \RuntimeException - неизвестный тип подключения или не задана переменная окружения
	 */
	public static function make(array $row, string $rootpath = ''): ProviderInterface {

		$code = (string)($row['code'] ?? '');

		$env  = trim((string)($row['api_key_env'] ?? ''));

		$key  = self::keyFor($row, $rootpath);

		$name    = (string)($row['name'] ?? '');
		$baseUrl = (string)($row['base_url'] ?? '');
		$model   = (string)($row['model'] ?? '');

		$params = self::params($row);

		if ($code === 'openai') {

			if ($key === '') {
				throw new \RuntimeException('Не задана переменная окружения'.($env !== '' ? ' '.$env : '').': добавьте ключ в окружение сервера или переключите подключение на хранение ключа в CRM');
			}

			return new Provider\OpenAiCompat($name, $baseUrl, $model, $key, $params);

		}

		if ($code === 'gemini') {

			if ($key === '') {
				throw new \RuntimeException('Не задана переменная окружения'.($env !== '' ? ' '.$env : '').': добавьте ключ в окружение сервера или переключите подключение на хранение ключа в CRM');
			}

			return new Provider\Gemini($name, $baseUrl, $model, $key, $params);

		}

		throw new \RuntimeException('Неизвестный тип подключения: '.$code);

	}

	/**
	 * Список подключений.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 *
	 * @return array
	 */
	public static function all($db, string $sqlname, int $identity): array {
		return (array)$db->getAll("SELECT * FROM {$sqlname}neuro_provider WHERE identity = ?i ORDER BY name", $identity);
	}

	/**
	 * Подключение по id (в пределах аккаунта).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param int    $identity
	 *
	 * @return array
	 */
	public static function byId($db, string $sqlname, int $id, int $identity): array {

		if ($id <= 0) {
			return [];
		}

		$row = $db->getRow("SELECT * FROM {$sqlname}neuro_provider WHERE id = ?i AND identity = ?i", $id, $identity);

		return is_array($row) ? $row : [];

	}

}
