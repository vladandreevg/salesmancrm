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
 * Очередь заданий к нейросети.
 *
 * Зачем она нужна: вызов модели занимает секунды, иногда десятки. Держать
 * пользователя в ожидании нельзя, а массовые операции (разбор истории, рассылка)
 * в запросе страницы вообще недопустимы. Поэтому фичи кладут задание в
 * `app_neuro_task`, а выполняет его воркер по cron.
 *
 * Ключевые свойства:
 *  - захват задания атомарный: `UPDATE … WHERE id = ?i AND status = 'new'` плюс
 *    проверка affectedRows. Два воркера не возьмут одно задание, и это не требует
 *    транзакций (ядро CRM живёт на MyISAM, транзакций в коде нет);
 *  - «зависшие» задания возвращаются в очередь: если воркер убит на середине,
 *    задание не потеряется;
 *  - повтор только для временных ошибок (429/5xx/таймаут) с растущей паузой;
 *    отказ по ключу или политике контента повторять бессмысленно;
 *  - постановка идемпотентна по `request_hash`: двойной клик не создаёт второй
 *    платный вызов.
 *
 * Class Queue
 *
 * @package Neuro
 */
class Queue {

	public const STATUS_NEW   = 'new';
	public const STATUS_RUN   = 'run';
	public const STATUS_OK    = 'ok';
	public const STATUS_ERROR = 'error';

	/**
	 * Завершённые состояния: только их касается уборка по сроку.
	 *
	 * Задание в работе (`new`, `run`) не удаляется никогда, сколько бы ни ждало:
	 * его заберёт воркер или вернёт в очередь `reclaim`.
	 */
	public const FINAL_STATUSES = [self::STATUS_OK, self::STATUS_ERROR];

	/**
	 * Срок хранения выполненных заданий по умолчанию, дней (0 — бессрочно).
	 *
	 * Задание хранит переменные с данными CRM и ответ модели, поэтому «навсегда» —
	 * это и рост таблицы, и лишние персональные данные на диске. Тридцать дней
	 * совпадают со сроком журнала: разбирать ошибку удобно по обоим.
	 */
	public const DEFAULT_RETENTION = 30;

	/**
	 * Сколько заданий удалять за один прогон воркера.
	 *
	 * Меньше, чем у журнала: строка задания весит заметно больше (переменные и
	 * ответ модели), и удаление порциями не должно держать блокировку.
	 */
	public const PURGE_LIMIT = 200;

	/**
	 * Срок хранения выполненных заданий из настроек (чистая функция).
	 *
	 * @param array $settings
	 *
	 * @return int - дней (0 — бессрочно)
	 */
	public static function retention(array $settings): int {

		if (!array_key_exists('TASK_RETENTION', $settings) || !is_numeric($settings['TASK_RETENTION'])) {
			return self::DEFAULT_RETENTION;
		}

		return max(0, min(365, (int)$settings['TASK_RETENTION']));

	}

	/**
	 * Удаление выполненных заданий старше срока хранения.
	 *
	 * Вызывается воркером при каждом прогоне и кнопкой в интерфейсе. Задания с
	 * ошибкой хранятся так же, как успешные: причина отказа записана, но данные
	 * клиента и ответ модели держать вечно незачем.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $days
	 * @param int    $limit    - 0 = удалить всё старое за один запрос
	 *
	 * @return int - сколько заданий удалено
	 */
	public static function purge($db, string $sqlname, int $identity, int $days, int $limit = self::PURGE_LIMIT): int {

		if ($days <= 0) {
			return 0;
		}

		$before = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));

		$states = "'".implode("','", self::FINAL_STATUSES)."'";

		// Основная часть — по дате завершения: так условие попадает в индекс
		// `finished` (identity, status, finished_at). Проверено замером: 0.25 мс
		// против 3 мс на 5000 строках. `COALESCE(finished_at, datum)` в условии
		// индекс не использует — поэтому строки без даты завершения разбираются
		// отдельным запросом ниже, а не выражением здесь.
		$db->query(
			"DELETE FROM {$sqlname}neuro_task
			  WHERE identity = ?i AND status IN ({$states}) AND finished_at IS NOT NULL AND finished_at < ?s
			  ORDER BY finished_at
			  LIMIT ?i",
			$identity,
			$before,
			max(1, $limit)
		);

		$removed = (int)$db->affectedRows();

		// Остаток порции — строки без даты завершения: они остались от записей
		// старше самой колонки, их единицы, и здесь скан допустим
		$rest = $limit > 0 ? $limit - $removed : 0;

		if ($limit <= 0 || $rest > 0) {

			$sql = "DELETE FROM {$sqlname}neuro_task
			         WHERE identity = ?i AND status IN ({$states}) AND finished_at IS NULL AND datum < ?s
			         ORDER BY datum";

			if ($limit > 0) {

				$db->query($sql." LIMIT ?i", $identity, $before, $rest);

			}
			else {

				$db->query($sql, $identity, $before);

			}

			$removed += (int)$db->affectedRows();

		}

		return $removed;

	}

	/**
	 * Сколько выполненных заданий подлежит уборке и когда завершалось самое старое.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $days
	 *
	 * @return array - count, oldest
	 */
	public static function purgeStats($db, string $sqlname, int $identity, int $days): array {

		if ($days <= 0) {
			return ['count' => 0, 'oldest' => ''];
		}

		$before = date('Y-m-d H:i:s', strtotime('-'.$days.' days'));
		$states = "'".implode("','", self::FINAL_STATUSES)."'";

		$row = (array)$db->getRow(
			"SELECT COUNT(*) AS cnt, MIN(COALESCE(finished_at, datum)) AS oldest
			   FROM {$sqlname}neuro_task
			  WHERE identity = ?i AND status IN ({$states})
			    AND (finished_at < ?s OR (finished_at IS NULL AND datum < ?s))",
			$identity,
			$before,
			$before
		);

		return ['count' => (int)($row['cnt'] ?? 0), 'oldest' => (string)($row['oldest'] ?? '')];

	}

	/**
	 * Сколько минут считать задание «зависшим», если воркер пропал.
	 */
	public const STALE_MINUTES = 15;

	/**
	 * Максимум попыток по умолчанию.
	 */
	public const MAX_ATTEMPTS = 3;

	/**
	 * Постановка задания в очередь.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param array  $task     - code, prompt_id, prompt_version, provider_id, priority,
	 *                           ref, refid, payload (массив), iduser, max_attempts
	 * @param int    $identity
	 *
	 * @return int - id задания (существующего, если такое уже в очереди)
	 */
	public static function push($db, string $sqlname, array $task, int $identity): int {

		$code    = Prompt::code((string)($task['code'] ?? ''));
		$payload = (array)($task['payload'] ?? []);

		if ($code === '') {
			return 0;
		}

		$hash = (string)($task['request_hash'] ?? self::requestHash($code, (int)($task['prompt_version'] ?? 0), $payload, (int)($task['iduser'] ?? 0)));

		// такое же задание уже ждёт или выполняется — второй раз не платим
		$exists = (int)$db->getOne("SELECT id FROM {$sqlname}neuro_task WHERE request_hash = ?s AND status IN ('new','run') AND identity = ?i LIMIT 1", $hash, $identity);

		if ($exists > 0) {
			return $exists;
		}

		$db->query("INSERT INTO {$sqlname}neuro_task SET ?u", [
			'code'           => $code,
			'prompt_id'      => (int)($task['prompt_id'] ?? 0),
			'prompt_version' => (int)($task['prompt_version'] ?? 0),
			'provider_id'    => (int)($task['provider_id'] ?? 0),
			'status'         => self::STATUS_NEW,
			'priority'       => (int)($task['priority'] ?? 5),
			'ref'            => (string)($task['ref'] ?? ''),
			'refid'          => (int)($task['refid'] ?? 0),
			'payload'        => json_encode($payload, JSON_UNESCAPED_UNICODE),
			'attempts'       => 0,
			'max_attempts'   => (int)($task['max_attempts'] ?? self::MAX_ATTEMPTS),
			'run_after'      => date('Y-m-d H:i:s'),
			'request_hash'   => $hash,
			'iduser'         => (int)($task['iduser'] ?? 0),
			'identity'       => $identity,
			'datum'          => date('Y-m-d H:i:s'),
		]);

		// id забираем сразу: оповещение ниже пишет в журнал, а любой INSERT сбрасывает
		// insertId — иначе функция вернула бы чужой идентификатор
		$id = (int)$db->insertId();

		// Очередь выросла — здесь и стоит проверить, разбирает ли её кто-нибудь.
		// Проверка идёт отсюда, а не только из воркера: если воркер мёртв, он о
		// себе не расскажет, а вот положить задание может кто угодно.
		// Оповещение ограничено по частоте, порогу и признаку «воркер работает»
		// (см. Neuro\Alerts::shouldWarnQueue), поэтому на потоке заданий это
		// несколько дешёвых запросов, а не поток писем.
		try {

			Alerts::queueTooLong($db, $sqlname, $identity);

		}
		catch (\Throwable $e) {
			// оповещение не должно мешать постановке задания
		}

		return $id;

	}

	/**
	 * Задания, готовые к обработке.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $limit
	 *
	 * @return array
	 */
	public static function candidates($db, string $sqlname, int $identity, int $limit = 10): array {

		return (array)$db->getAll(
			"SELECT * FROM {$sqlname}neuro_task
			  WHERE status = 'new' AND (run_after IS NULL OR run_after <= NOW()) AND identity = ?i
			  ORDER BY priority, id
			  LIMIT ?i",
			$identity,
			$limit
		);

	}

	/**
	 * Атомарный захват задания.
	 *
	 * Выбор кандидатов и захват — разные запросы, поэтому между ними задание может
	 * забрать другой воркер: проверяем по числу изменённых строк, а не по SELECT.
	 *
	 * Условие по `run_after` здесь не лишнее: пока один воркер шёл по списку,
	 * другой мог уронить задание в паузу перед повтором, и без этой проверки
	 * задание ушло бы в работу раньше срока.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param string $worker - метка воркера (pid+хост)
	 * @param int    $identity
	 *
	 * @return bool - true, если задание наше
	 */
	public static function claim($db, string $sqlname, int $id, string $worker, int $identity): bool {

		if ($id <= 0) {
			return false;
		}

		$db->query(
			"UPDATE {$sqlname}neuro_task
			    SET status = 'run', locked_by = ?s, locked_at = NOW()
			  WHERE id = ?i AND status = 'new' AND (run_after IS NULL OR run_after <= NOW()) AND identity = ?i",
			$worker,
			$id,
			$identity
		);

		return (int)$db->affectedRows() === 1;

	}

	/**
	 * Успешное завершение: результат и расход пишем в задание.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param array  $result - нормализованный ответ (Neuro\Neuro)
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function complete($db, string $sqlname, int $id, array $result, int $identity): void {

		$db->query("UPDATE {$sqlname}neuro_task SET ?u WHERE id = ?i AND identity = ?i", array_merge(
			self::resultColumns($result),
			[
				'status'      => self::STATUS_OK,
				'error'       => NULL,
				'error_code'  => NULL,
				'finished_at' => date('Y-m-d H:i:s'),
				'locked_by'   => NULL,
				'locked_at'   => NULL,
			]
		), $id, $identity);

	}

	/**
	 * Обработка неудачи: повтор для временных ошибок, иначе окончательный отказ.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param array  $error  - ['code' => string, 'message' => string]
	 * @param int    $identity
	 *
	 * @return string - retry | error
	 */
	public static function fail($db, string $sqlname, int $id, array $error, int $identity): string {

		$row = (array)$db->getRow("SELECT attempts, max_attempts FROM {$sqlname}neuro_task WHERE id = ?i AND identity = ?i", $id, $identity);

		$attempts    = (int)($row['attempts'] ?? 0) + 1;
		$maxAttempts = (int)($row['max_attempts'] ?? self::MAX_ATTEMPTS);
		$code        = (string)($error['code'] ?? 'unknown');

		$data = [
			'attempts'   => $attempts,
			'error'      => (string)($error['message'] ?? ''),
			'error_code' => $code,
			'locked_by'  => NULL,
			'locked_at'  => NULL,
		];

		if (self::shouldRetry($code, $attempts, $maxAttempts)) {

			$data['status']    = self::STATUS_NEW;
			$data['run_after'] = date('Y-m-d H:i:s', time() + self::backoffSeconds($attempts));

			$db->query("UPDATE {$sqlname}neuro_task SET ?u WHERE id = ?i AND identity = ?i", $data, $id, $identity);

			return 'retry';

		}

		$data['status']      = self::STATUS_ERROR;
		$data['finished_at'] = date('Y-m-d H:i:s');

		$db->query("UPDATE {$sqlname}neuro_task SET ?u WHERE id = ?i AND identity = ?i", $data, $id, $identity);

		return 'error';

	}

	/**
	 * Отмена задания пользователем.
	 *
	 * Задание в работе отменить нельзя: запрос к модели уже отправлен и будет
	 * оплачен. Поэтому отменяем только то, что ещё не начато.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param int    $identity
	 *
	 * @return bool
	 */
	public static function cancel($db, string $sqlname, int $id, int $identity): bool {

		$db->query(
			"UPDATE {$sqlname}neuro_task SET status = 'cancel', finished_at = NOW(), locked_by = NULL, locked_at = NULL WHERE id = ?i AND status = 'new' AND identity = ?i",
			$id,
			$identity
		);

		return (int)$db->affectedRows() === 1;

	}

	/**
	 * Возврат «зависших» заданий: воркер убит, сервер упал, задание осталось в run.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $minutes
	 *
	 * @return int - сколько заданий возвращено
	 */
	public static function reclaim($db, string $sqlname, int $identity, int $minutes = self::STALE_MINUTES): int {

		$db->query(
			"UPDATE {$sqlname}neuro_task
			    SET status = 'new', attempts = attempts + 1, locked_by = NULL, locked_at = NULL, run_after = NOW()
			  WHERE status = 'run' AND locked_at IS NOT NULL AND locked_at < (NOW() - INTERVAL ?i MINUTE) AND identity = ?i",
			$minutes,
			$identity
		);

		return (int)$db->affectedRows();

	}

	/**
	 * Сколько секунд ждать перед повтором: 60, 120, 240… но не больше часа.
	 *
	 * @param int $attempts - номер уже сделанной попытки
	 *
	 * @return int
	 */
	public static function backoffSeconds(int $attempts): int {
		return (int)min(3600, 60 * pow(2, max(0, $attempts - 1)));
	}

	/**
	 * Стоит ли повторять задание.
	 *
	 * Повторяем только временные сбои: лимит запросов, ошибки сервера, таймаут, сеть.
	 * Отказ по ключу, политике контента или неверному запросу повторять бессмысленно —
	 * это только тратит деньги пользователя.
	 *
	 * @param string $errorCode
	 * @param int    $attempts - включая текущую
	 * @param int    $maxAttempts
	 *
	 * @return bool
	 */
	public static function shouldRetry(string $errorCode, int $attempts, int $maxAttempts): bool {

		if ($attempts >= $maxAttempts) {
			return false;
		}

		return Http::isRetryable($errorCode);

	}

	/**
	 * Отпечаток задания: одинаковые запросы не должны выполняться дважды.
	 *
	 * @param string $code
	 * @param int    $promptVersion
	 * @param array  $payload
	 * @param int    $iduser
	 *
	 * @return string
	 */
	public static function requestHash(string $code, int $promptVersion, array $payload, int $iduser): string {
		return hash('sha256', $code.'|'.$promptVersion.'|'.json_encode($payload).'|'.$iduser);
	}

	/**
	 * Поля задания из нормализованного ответа (результат и расход).
	 *
	 * @param array $result
	 *
	 * @return array
	 */
	public static function resultColumns(array $result): array {

		return [
			'result'      => (string)($result['text'] ?? ''),
			'result_json' => $result['json'] !== NULL ? json_encode($result['json'], JSON_UNESCAPED_UNICODE) : NULL,
			'model'       => (string)($result['model'] ?? ''),
			'tokens_in'   => (int)($result['usage']['in'] ?? 0),
			'tokens_out'  => (int)($result['usage']['out'] ?? 0),
			'cost'        => $result['usage']['cost'] ?? NULL,
			'latency_ms'  => (int)($result['latency_ms'] ?? 0),
		];

	}

	/**
	 * Список заданий для вкладки «Очередь».
	 *
	 * Фильтр по статусу пропускается через белый список: строка из запроса не должна
	 * попадать в SQL даже как значение, потому что опечатка в фильтре должна давать
	 * пустой список, а не ошибку.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param string $status - '' | new | run | ok | error | cancel
	 * @param int    $limit
	 * @param int    $offset
	 *
	 * @return array
	 */
	public static function listTasks($db, string $sqlname, int $identity, string $status = '', int $limit = 50, int $offset = 0): array {

		$limit  = max(1, min(200, $limit));
		$offset = max(0, $offset);

		if ($status !== '' && in_array($status, self::statuses(), true)) {

			return (array)$db->getAll(
				"SELECT * FROM {$sqlname}neuro_task WHERE status = ?s AND identity = ?i ORDER BY id DESC LIMIT ?i, ?i",
				$status,
				$identity,
				$offset,
				$limit
			);

		}

		return (array)$db->getAll("SELECT * FROM {$sqlname}neuro_task WHERE identity = ?i ORDER BY id DESC LIMIT ?i, ?i", $identity, $offset, $limit);

	}

	/**
	 * Задание по id.
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

		return (array)$db->getRow("SELECT * FROM {$sqlname}neuro_task WHERE id = ?i AND identity = ?i", $id, $identity);

	}

	/**
	 * Счётчики заданий по статусам (для шапки вкладки).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 *
	 * @return array
	 */
	public static function stats($db, string $sqlname, int $identity): array {

		$rows = (array)$db->getAll("SELECT status, COUNT(*) AS cnt FROM {$sqlname}neuro_task WHERE identity = ?i GROUP BY status", $identity);

		return self::normalizeStats($rows);

	}

	/**
	 * Счётчики по всем статусам, включая нулевые (чистая функция).
	 *
	 * Нули нужны интерфейсу: без них счётчик исчезает, когда заданий в статусе нет,
	 * и администратор не понимает, пусто там или сломалось.
	 *
	 * @param array $rows - строки вида ['status' => 'ok', 'cnt' => 3]
	 *
	 * @return array
	 */
	public static function normalizeStats(array $rows): array {

		$out = array_fill_keys(self::statuses(), 0);

		foreach ($rows as $row) {

			$status = (string)($row['status'] ?? '');

			if (array_key_exists($status, $out)) {
				$out[$status] = (int)($row['cnt'] ?? 0);
			}

		}

		return $out;

	}

	/**
	 * Ручной повтор задания из интерфейса.
	 *
	 * Повторяем только то, что уже завершилось (ошибка или отмена): задание в
	 * работе повторять нельзя, иначе один и тот же вызов уйдёт дважды и будет
	 * оплачен дважды. Счётчик попыток сбрасывается — это осознанное действие
	 * администратора, а не автоматический ретрай.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param int    $identity
	 *
	 * @return bool
	 */
	public static function retry($db, string $sqlname, int $id, int $identity): bool {

		$db->query(
			"UPDATE {$sqlname}neuro_task
			    SET status = 'new', attempts = 0, run_after = NOW(), error = NULL, error_code = NULL,
			        locked_by = NULL, locked_at = NULL, finished_at = NULL
			  WHERE id = ?i AND status IN ('error','cancel') AND identity = ?i",
			$id,
			$identity
		);

		return (int)$db->affectedRows() === 1;

	}

	/**
	 * Разрешённые статусы задания.
	 *
	 * @return array
	 */
	public static function statuses(): array {
		return [self::STATUS_NEW, self::STATUS_RUN, self::STATUS_OK, self::STATUS_ERROR, 'cancel'];
	}

	/**
	 * Названия статусов для человека.
	 */
	public const STATUS_TITLES = [
		self::STATUS_NEW   => 'В очереди',
		self::STATUS_RUN   => 'В работе',
		self::STATUS_OK    => 'Выполнено',
		self::STATUS_ERROR => 'Ошибка',
		'cancel'           => 'Отменено',
	];

	/**
	 * Название статуса (чистая функция).
	 *
	 * @param string $status
	 *
	 * @return string
	 */
	public static function statusTitle(string $status): string {
		return self::STATUS_TITLES[$status] ?? $status;
	}

	/**
	 * Значения по умолчанию из настроек ядра (чистая функция).
	 *
	 * Одно место для обеих сторон очереди: интерфейс ставит задание с этими
	 * значениями, воркер выполняет с ними же. Настройки, которые ни на что не
	 * влияют, хуже их отсутствия: администратор думает, что изменил поведение.
	 *
	 * @param array $settings - customSettings('neuroCore')
	 *
	 * @return array - timeout, max_attempts, store_payload
	 */
	public static function defaults(array $settings): array {

		$timeout  = (int)($settings['TIMEOUT'] ?? 0);
		$attempts = (int)($settings['MAX_ATTEMPTS'] ?? 0);

		return [
			'timeout'       => $timeout >= 5 && $timeout <= 300 ? $timeout : 30,
			'max_attempts'  => $attempts >= 1 && $attempts <= 10 ? $attempts : self::MAX_ATTEMPTS,
			'store_payload' => (($settings['STORE_PAYLOAD'] ?? '') === 'on'),
		];

	}

	/**
	 * Удаление переменных задания после успешного выполнения.
	 *
	 * В переменных лежат данные CRM (названия клиентов, тексты писем). Хранить их
	 * в очереди после того, как ответ получен и записан, незачем — настройка
	 * «Сохранять полные запросы» по умолчанию выключена именно из-за этого.
	 * У неудачных заданий переменные остаются: без них причину не разобрать.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $id
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function clearPayload($db, string $sqlname, int $id, int $identity): void {

		$db->query("UPDATE {$sqlname}neuro_task SET payload = NULL WHERE id = ?i AND identity = ?i", $id, $identity);

	}

	/**
	 * Запись в журнал ядра.
	 *
	 * Оставлено ради совместимости: журнал ведёт `Neuro\Log`, здесь только переход
	 * на него, чтобы у очереди и у остальных событий был один формат записи.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $taskId
	 * @param string $level
	 * @param string $message
	 * @param array  $context
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function log($db, string $sqlname, int $taskId, string $level, string $message, array $context, int $identity): void {
		Log::write($db, $sqlname, $taskId, $level, $message, $context, $identity);
	}

}
