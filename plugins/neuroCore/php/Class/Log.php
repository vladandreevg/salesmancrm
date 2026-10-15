<?php
/* ============================ */
/*         SalesMan CRM         */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Журнал Нейроядра (`app_neuro_log`).
 *
 * Зачем он нужен, если есть очередь: очередь отвечает на вопрос «что случилось с
 * этим заданием», а журнал — «что вообще происходило». Разница видна на практике:
 * отказ по ключу, попадание данных под маскирование, очистка кэша и возврат
 * зависших заданий не принадлежат ни одному заданию, но именно они объясняют
 * поведение системы, когда заданий в очереди уже нет.
 *
 * Что в журнал НЕ попадает: тексты промптов и ответы модели. Полные данные
 * задания хранит `neuro_task` (и только при включённой настройке хранения
 * переменных). Здесь — метаданные: код задачи, модель, токены, коды ошибок.
 *
 * Class Log
 *
 * @package Neuro
 */
class Log {

	/**
	 * Уровни записей: от подробного к самому важному.
	 */
	public const LEVELS = [
		'debug' => 'Подробно',
		'info'  => 'Информация',
		'warn'  => 'Предупреждение',
		'error' => 'Ошибка',
	];

	/**
	 * Сколько дней хранить журнал по умолчанию.
	 */
	public const DEFAULT_RETENTION = 30;

	/**
	 * Сколько записей удалять за один прогон воркера.
	 *
	 * Удаление порциями: `DELETE` без ограничения на большом журнале держит
	 * блокировку и растягивает минуту воркера. Несколько запусков уберут остаток.
	 */
	public const PURGE_LIMIT = 500;

	/**
	 * Запись в журнал.
	 *
	 * Ошибка записи не должна ломать основную операцию: журнал — вспомогательная
	 * вещь, и падать из-за него на вызове модели нельзя.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $taskId   - id задания (0 — запись не про конкретное задание)
	 * @param string $level    - debug|info|warn|error
	 * @param string $message
	 * @param array  $context  - метаданные (без текстов клиентов и ответов модели)
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function write($db, string $sqlname, int $taskId, string $level, string $message, array $context = [], int $identity = 1): void {

		$level = self::level($level);

		try {

			$db->query("INSERT INTO {$sqlname}neuro_log SET ?u", [
				'tid'      => $taskId > 0 ? $taskId : NULL,
				'level'    => $level,
				'message'  => mb_substr($message, 0, 2000),
				'context'  => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : NULL,
				'identity' => $identity,
				'datum'    => date('Y-m-d H:i:s'),
			]);

		}
		catch (\Exception $e) {
			// журнал не должен ронять вызывающий код
		}

	}

	/**
	 * Нормализация уровня (чистая функция).
	 *
	 * Неизвестный уровень приводится к `info`: он не должен ни теряться, ни
	 * превращаться в ошибку фильтрации.
	 *
	 * @param string $level
	 *
	 * @return string
	 */
	public static function level(string $level): string {

		$level = strtolower(trim($level));

		return isset(self::LEVELS[$level]) ? $level : 'info';

	}

	/**
	 * Название уровня для человека (чистая функция).
	 *
	 * @param string $level
	 *
	 * @return string
	 */
	public static function levelTitle(string $level): string {
		return self::LEVELS[self::level($level)] ?? $level;
	}

	/**
	 * Фильтры списка (чистая функция — проверяется тестом).
	 *
	 * @param array $request - level, tid, period (дней), limit, offset
	 *
	 * @return array - level, tid, from, limit, offset
	 */
	public static function filters(array $request): array {

		$level = strtolower(trim((string)($request['level'] ?? '')));

		if (!isset(self::LEVELS[$level])) {
			$level = '';
		}

		$days = (int)($request['period'] ?? 7);

		if ($days < 1) {
			$days = 1;
		}

		if ($days > 365) {
			$days = 365;
		}

		$limit = (int)($request['limit'] ?? 100);

		if ($limit < 1 || $limit > 500) {
			$limit = 100;
		}

		$offset = max(0, (int)($request['offset'] ?? 0));

		return [
			'level'  => $level,
			'tid'    => max(0, (int)($request['tid'] ?? 0)),
			'from'   => date('Y-m-d H:i:s', strtotime('-'.$days.' days')),
			'limit'  => $limit,
			'offset' => $offset,
		];

	}

	/**
	 * Список записей журнала.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param array  $request - фильтры (см. filters())
	 *
	 * @return array - rows, total, filters
	 */
	public static function listRows($db, string $sqlname, int $identity, array $request = []): array {

		$f = self::filters($request);

		$where  = ["identity = ?i", "datum >= ?s"];
		$params = [$identity, $f['from']];

		if ($f['level'] !== '') {
			$where[]  = "level = ?s";
			$params[] = $f['level'];
		}

		if ($f['tid'] > 0) {
			$where[]  = "tid = ?i";
			$params[] = $f['tid'];
		}

		$sql = implode(' AND ', $where);

		$total = (int)$db->getOne("SELECT COUNT(*) FROM {$sqlname}neuro_log WHERE {$sql}", ...$params);

		$params[] = $f['limit'];
		$params[] = $f['offset'];

		$rows = (array)$db->getAll(
			"SELECT id, tid, level, message, context, datum
			   FROM {$sqlname}neuro_log WHERE {$sql}
			  ORDER BY id DESC LIMIT ?i OFFSET ?i",
			...$params
		);

		$out = [];

		foreach ($rows as $row) {

			$context = json_decode((string)($row['context'] ?? ''), true);

			$out[] = [
				'id'        => (int)$row['id'],
				'tid'       => (int)($row['tid'] ?? 0),
				'level'     => (string)$row['level'],
				'levelTitle' => self::levelTitle((string)$row['level']),
				'message'   => (string)$row['message'],
				'context'   => is_array($context) ? $context : [],
				'datum'     => (string)$row['datum'],
			];

		}

		return ['rows' => $out, 'total' => $total, 'filters' => $f];

	}

	/**
	 * Сколько записей каждого уровня (для счётчиков на вкладке).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $days
	 *
	 * @return array - [уровень => количество]
	 */
	public static function stats($db, string $sqlname, int $identity, int $days = 7): array {

		$stats = array_fill_keys(array_keys(self::LEVELS), 0);

		$rows = (array)$db->getAll(
			"SELECT level, COUNT(*) AS cnt FROM {$sqlname}neuro_log
			  WHERE identity = ?i AND datum >= ?s GROUP BY level",
			$identity,
			date('Y-m-d H:i:s', strtotime('-'.max(1, $days).' days'))
		);

		foreach ($rows as $row) {
			$stats[self::level((string)$row['level'])] += (int)$row['cnt'];
		}

		return $stats;

	}

	/**
	 * Срок хранения журнала из настроек (чистая функция).
	 *
	 * 0 — хранить бессрочно (осмысленное значение, а не «не задано»).
	 *
	 * @param array $settings - customSettings('neuroCore')
	 *
	 * @return int - дней
	 */
	public static function retention(array $settings): int {

		if (!array_key_exists('LOG_RETENTION', $settings)) {
			return self::DEFAULT_RETENTION;
		}

		return max(0, min(365, (int)$settings['LOG_RETENTION']));

	}

	/**
	 * Удаление старых записей журнала (ретенция).
	 *
	 * Вызывается воркером при каждом прогоне: журнал растёт от каждой ошибки, и
	 * без уборки он однажды станет самой большой таблицей ядра.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $days
	 * @param int    $limit
	 *
	 * @return int - сколько записей удалено
	 */
	public static function purge($db, string $sqlname, int $identity, int $days, int $limit = self::PURGE_LIMIT): int {

		if ($days <= 0) {
			return 0;
		}

		$db->query(
			"DELETE FROM {$sqlname}neuro_log WHERE identity = ?i AND datum < ?s ORDER BY id LIMIT ?i",
			$identity,
			date('Y-m-d H:i:s', strtotime('-'.$days.' days')),
			max(1, $limit)
		);

		return (int)$db->affectedRows();

	}

}
