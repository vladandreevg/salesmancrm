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
 * Реестр промптов: хранение, версии, подстановка переменных.
 *
 * Промпты живут в базе, а не в коде фич: их правит администратор, у каждого есть
 * версии (`neuro_prompt_rev`), чтобы связать качество результата с тем, что именно
 * отправляли модели.
 *
 * Подстановка сделана без HTML-экранирования: в промпт идут реальные данные CRM
 * (названия компаний, текст письма), и «&quot;» вместо кавычки испортил бы запрос.
 * Используется Mustache с entity_flags = 0, поэтому доступны и секции
 * ({{#список}}…{{/список}}), а при отсутствии библиотеки работает простая
 * подстановка {{переменная}}.
 *
 * Class Prompt
 *
 * @package Neuro
 */
class Prompt {

	/**
	 * Движок шаблонов (создаётся один раз: он кэширует разобранные шаблоны).
	 *
	 * @var \Mustache_Engine|null
	 */
	private static $engine;

	/**
	 * Нормализация кода промпта: `Deal.Summary` и `deal.summary` — одно и то же.
	 *
	 * @param string $code
	 *
	 * @return string
	 */
	public static function code(string $code): string {
		return strtolower(trim($code));
	}

	/**
	 * Подстановка переменных в шаблон.
	 *
	 * @param string $template
	 * @param array  $tags
	 *
	 * @return string
	 */
	public static function render(string $template, array $tags = []): string {

		if (trim($template) === '') {
			return '';
		}

		if (class_exists('Mustache_Engine')) {
			return (string)self::engine()->render($template, $tags);
		}

		return self::plainRender($template, $tags);

	}

	/**
	 * Движок без HTML-экранирования.
	 *
	 * В промпт идут реальные данные CRM, поэтому `{{переменная}}` должен
	 * подставляться как есть. Вариант с entity_flags = 0 не годится: он убирает
	 * экранирование кавычек, но `&`, `<` и `>` htmlspecialchars всё равно меняет.
	 * Поэтому подменяем саму функцию экранирования.
	 *
	 * @return \Mustache_Engine
	 */
	private static function engine() {

		if (self::$engine === NULL) {

			self::$engine = new \Mustache_Engine([
				'escape' => static function ($value) {
					return $value;
				},
			]);

		}

		return self::$engine;

	}

	/**
	 * Простая подстановка {{переменная}} и {{вложенная.переменная}} — запасной
	 * вариант, если шаблонизатор недоступен (в CRM он есть всегда: vendor/mustache
	 * подключается через inc/dbconnector.php).
	 *
	 * @param string $template
	 * @param array  $tags
	 *
	 * @return string
	 */
	public static function plainRender(string $template, array $tags): string {

		return (string)preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', static function (array $m) use ($tags) {

			$value = $tags;

			foreach (explode('.', $m[1]) as $part) {

				if (is_array($value) && array_key_exists($part, $value)) {
					$value = $value[$part];
					continue;
				}

				return '';

			}

			return is_scalar($value) ? (string)$value : '';

		}, $template);

	}

	/**
	 * Переменные шаблона, которых нет в данных.
	 *
	 * Лучше отказаться от вызова, чем отправить модели промпт с пустыми местами:
	 * фича получит непредсказуемый ответ и будет искать причину в модели.
	 *
	 * @param string $template
	 * @param array  $tags
	 *
	 * @return array
	 */
	public static function missing(string $template, array $tags): array {

		if (preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', $template, $m) === 0) {
			return [];
		}

		$missing = [];

		foreach (array_unique($m[1]) as $name) {

			if (!self::has($tags, (string)$name)) {
				$missing[] = (string)$name;
			}

		}

		return $missing;

	}

	/**
	 * Есть ли значение по пути «a.b.c».
	 *
	 * @param array  $tags
	 * @param string $path
	 *
	 * @return bool
	 */
	private static function has(array $tags, string $path): bool {

		$value = $tags;

		foreach (explode('.', $path) as $part) {

			if (!is_array($value) || !array_key_exists($part, $value)) {
				return false;
			}

			$value = $value[$part];

		}

		return true;

	}

	/**
	 * Разбор params промпта (JSON в базе).
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
	 * Промпт по коду.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param string $code
	 * @param int    $identity
	 * @param bool   $onlyActive - искать только включённые (для регистрации нужны все)
	 *
	 * @return array
	 */
	public static function byCode($db, string $sqlname, string $code, int $identity, bool $onlyActive = true): array {

		$sql = "SELECT * FROM {$sqlname}neuro_prompt WHERE code = ?s AND identity = ?i";

		if ($onlyActive) {
			$sql .= " AND active = 'on'";
		}

		$row = $db->getRow($sql, self::code($code), $identity);

		return is_array($row) ? $row : [];

	}

	/**
	 * Регистрация промпта, который приносит плагин.
	 *
	 * Вызывается при активации фичи: её промпты должны работать сразу, без ручного
	 * создания администратором. Правила:
	 *  - промпта нет → создаём (версия 1, source = plugin);
	 *  - промпт есть → текст и параметры НЕ трогаем (ими владеет администратор),
	 *    но запоминаем, что приносит плагин, и помечаем владельца.
	 *
	 * Возвращает: created | exists | differs | skipped.
	 * `differs` означает, что сохранённый текст отличается от того, что приносит
	 * плагин: администратор поправил промпт или плагин обновил свой вариант —
	 * в интерфейсе это видно, и решение принимает человек, а не код.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param string $code
	 * @param string $content
	 * @param array  $params
	 * @param string $owner    - пакет плагина
	 * @param int    $identity
	 * @param int    $iduser
	 *
	 * @return string
	 */
	public static function register($db, string $sqlname, string $code, string $content, array $params, string $owner, int $identity, int $iduser = 0): string {

		$code = self::code($code);

		if ($code === '' || trim($content) === '') {
			return 'skipped';
		}

		$owner = trim($owner);

		$row = self::byCode($db, $sqlname, $code, $identity, false);

		if (empty($row)) {

			$db->query("INSERT INTO {$sqlname}neuro_prompt SET ?u", [
				'code'           => $code,
				'name'           => $code,
				'version'        => 1,
				'content'        => $content,
				'params'         => json_encode($params, JSON_UNESCAPED_UNICODE),
				'owner'          => $owner,
				'source'         => 'plugin',
				'plugin_content' => $content,
				'active'         => 'on',
				'iduser'         => $iduser,
				'datum'          => date('Y-m-d H:i:s'),
				'identity'       => $identity,
			]);

			return 'created';

		}

		$db->query("UPDATE {$sqlname}neuro_prompt SET ?u WHERE id = ?i AND identity = ?i", [
			'owner'          => $owner,
			'plugin_content' => $content,
		], (int)$row['id'], $identity);

		return (string)$row['content'] === $content ? 'exists' : 'differs';

	}

	/**
	 * Отличается ли сохранённый текст от того, что приносит плагин.
	 *
	 * @param array $row
	 *
	 * @return bool
	 */
	public static function pluginDefaultDiffers(array $row): bool {

		$plugin = (string)($row['plugin_content'] ?? '');

		return trim($plugin) !== '' && $plugin !== (string)($row['content'] ?? '');

	}

	/**
	 * Промпт по id.
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

		$row = $db->getRow("SELECT * FROM {$sqlname}neuro_prompt WHERE id = ?i AND identity = ?i", $id, $identity);

		return is_array($row) ? $row : [];

	}

	/**
	 * Все промпты аккаунта.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 *
	 * @return array
	 */
	public static function all($db, string $sqlname, int $identity): array {
		return (array)$db->getAll("SELECT * FROM {$sqlname}neuro_prompt WHERE identity = ?i ORDER BY owner, code", $identity);
	}

	/**
	 * История версий промпта.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $promptId
	 * @param int    $identity
	 *
	 * @return array
	 */
	public static function revisions($db, string $sqlname, int $promptId, int $identity): array {

		if ($promptId <= 0) {
			return [];
		}

		return (array)$db->getAll("SELECT * FROM {$sqlname}neuro_prompt_rev WHERE prompt_id = ?i AND identity = ?i ORDER BY version DESC", $promptId, $identity);

	}

	/**
	 * Сохранение промпта: обновление повышает версию и пишет ревизию.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param array  $row      - id, code, name, content, params, active
	 * @param int    $identity
	 * @param int    $iduser
	 *
	 * @return int - id промпта (0 — не сохранилось)
	 */
	public static function save($db, string $sqlname, array $row, int $identity, int $iduser): int {

		$id = (int)($row['id'] ?? 0);

		$data = [
			'code'      => self::code((string)($row['code'] ?? '')),
			'name'      => trim((string)($row['name'] ?? '')),
			'content'   => (string)($row['content'] ?? ''),
			'params'    => is_array($row['params'] ?? NULL) ? json_encode($row['params'], JSON_UNESCAPED_UNICODE) : (string)($row['params'] ?? ''),
			'active'    => ((string)($row['active'] ?? 'on')) === 'on' ? 'on' : 'off',
			'source'    => 'admin',
			'iduser'    => $iduser,
			'date_edit' => date('Y-m-d H:i:s'),
			'identity'  => $identity,
		];

		if ($id > 0) {

			$current = self::byId($db, $sqlname, $id, $identity);
			$version = (int)($current['version'] ?? 1) + 1;

			$data['version'] = $version;

			$db->query("UPDATE {$sqlname}neuro_prompt SET ?u WHERE id = ?i AND identity = ?i", $data, $id, $identity);

		}
		else {

			$version = 1;

			$data['version'] = $version;
			$data['datum']   = date('Y-m-d H:i:s');

			$db->query("INSERT INTO {$sqlname}neuro_prompt SET ?u", $data);

			$id = (int)$db->insertId();

		}

		if ($id <= 0) {
			return 0;
		}

		self::addRevision($db, $sqlname, $id, $version, $data['content'], $data['params'], $iduser, $identity);

		return $id;

	}

	/**
	 * Запись версии промпта в историю.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $promptId
	 * @param int    $version
	 * @param string $content
	 * @param string $params
	 * @param int    $iduser
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function addRevision($db, string $sqlname, int $promptId, int $version, string $content, string $params, int $iduser, int $identity): void {

		$db->query("INSERT INTO {$sqlname}neuro_prompt_rev SET ?u", [
			'prompt_id' => $promptId,
			'version'   => $version,
			'content'   => $content,
			'params'    => $params,
			'iduser'    => $iduser,
			'identity'  => $identity,
		]);

	}

}
