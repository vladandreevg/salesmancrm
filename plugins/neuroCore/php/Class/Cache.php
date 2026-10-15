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
 * Кэш одинаковых запросов к модели.
 *
 * Зачем: пользователь нажимает «Выполнить» ещё раз, две вкладки отправляют один и
 * тот же промпт, фича перебирает список и повторяет запрос с теми же данными —
 * каждый такой повтор оплачивается. Ключ кэша считается от того, что реально
 * уходит модели: код промпта, его версия, модель, температура и итоговый текст
 * сообщений. Изменились данные клиента — изменился ключ, и ответ берётся свежий;
 * это не «устаревший кэш», а буквально тот же вопрос.
 *
 * Срок годности задаётся настройкой ядра (минуты, 0 — кэш выключен). По умолчанию
 * он включён на час: вызовы моделей недетерминированы, и держать ответ дольше
 * смысла нет, а защита от случайного двойного клика важна сразу.
 *
 * Class Cache
 *
 * @package Neuro
 */
class Cache {

	/**
	 * Срок жизни по умолчанию, минуты.
	 */
	public const DEFAULT_TTL = 60;

	/**
	 * Максимум, минуты (неделя).
	 */
	public const MAX_TTL = 10080;

	/**
	 * Срок жизни кэша в секундах (чистая функция).
	 *
	 * @param array $settings - customSettings('neuroCore')
	 *
	 * @return int - 0 = кэш выключен
	 */
	public static function ttl(array $settings): int {

		if (!array_key_exists('CACHE_TTL', $settings)) {
			return self::DEFAULT_TTL * 60;
		}

		$minutes = (int)$settings['CACHE_TTL'];

		if ($minutes <= 0) {
			return 0;
		}

		return min($minutes, self::MAX_TTL) * 60;

	}

	/**
	 * Срок жизни из настроек ядра (для вызовов вне веб-контекста — по умолчанию).
	 *
	 * @return int
	 */
	public static function settingsTtl(): int {
		return self::ttl(function_exists('customSettings') ? (array)customSettings('neuroCore') : []);
	}

	/**
	 * Отпечаток запроса (чистая функция).
	 *
	 * @param array $prompt      - строка промпта (code, version)
	 * @param array $request     - запрос к модели (messages, model, temperature, json_schema)
	 * @param array $providerRow - подключение
	 *
	 * @return string
	 */
	public static function key(array $prompt, array $request, array $providerRow = []): string {

		$material = [
			'prompt'   => (string)($prompt['code'] ?? ''),
			'version'  => (int)($prompt['version'] ?? 0),
			'provider' => (string)($providerRow['code'] ?? ''),
			'request'  => $request,
		];

		// json_schema влияет на ответ, поэтому он уже внутри request; temperature —
		// тоже (compose() кладёт их в запрос)
		unset($material['request']['timeout']);

		return hash('sha256', json_encode($material, JSON_UNESCAPED_UNICODE));

	}

	/**
	 * Ответ из кэша.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param string $hash
	 * @param int    $identity
	 *
	 * @return array - пусто, если ответа нет или срок истёк
	 */
	public static function get($db, string $sqlname, string $hash, int $identity): array {

		if ($hash === '') {
			return [];
		}

		$row = (array)$db->getRow(
			"SELECT * FROM {$sqlname}neuro_cache WHERE hash = ?s AND identity = ?i AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1",
			$hash,
			$identity
		);

		return $row;

	}

	/**
	 * Положить ответ в кэш (перезаписывая прежний ответ по тому же ключу).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param string $hash
	 * @param array  $data - code, model, answer, answer_json, tokens_in, tokens_out, iduser
	 * @param int    $ttl  - секунды (0 = не кэшировать)
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function put($db, string $sqlname, string $hash, array $data, int $ttl, int $identity): void {

		if ($hash === '' || $ttl <= 0) {
			return;
		}

		$db->query(
			"INSERT INTO {$sqlname}neuro_cache SET hash = ?s, code = ?s, model = ?s, answer = ?s, answer_json = ?s, tokens_in = ?i, tokens_out = ?i, expires_at = DATE_ADD(NOW(), INTERVAL ?i SECOND), iduser = ?i, identity = ?i
			 ON DUPLICATE KEY UPDATE code = VALUES(code), model = VALUES(model), answer = VALUES(answer), answer_json = VALUES(answer_json),
			                         tokens_in = VALUES(tokens_in), tokens_out = VALUES(tokens_out), expires_at = VALUES(expires_at), iduser = VALUES(iduser)",
			$hash,
			(string)($data['code'] ?? ''),
			(string)($data['model'] ?? ''),
			(string)($data['answer'] ?? ''),
			$data['answer_json'] !== NULL ? (string)$data['answer_json'] : NULL,
			(int)($data['tokens_in'] ?? 0),
			(int)($data['tokens_out'] ?? 0),
			$ttl,
			(int)($data['iduser'] ?? 0),
			$identity
		);

	}

	/**
	 * Сколько просроченных ответов удалять за один прогон воркера.
	 *
	 * Порциями: `DELETE` без ограничения на большом кэше держит блокировку и
	 * растягивает минуту воркера. Остаток уберут следующие прогоны.
	 */
	public const PURGE_LIMIT = 500;

	/**
	 * Удаление просроченных ответов: кэш не должен расти бесконечно.
	 *
	 * Вызывается воркером при каждом прогоне и кнопкой в интерфейсе. Просроченный
	 * ответ бесполезен: `get()` его и так не отдаёт, но строка остаётся в таблице,
	 * а вместе с ней — текст ответа модели, который незачем хранить.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param int    $limit    - 0 = удалить всё просроченное за один запрос
	 *
	 * @return int - сколько удалено
	 */
	public static function purge($db, string $sqlname, int $identity, int $limit = self::PURGE_LIMIT): int {

		if ($limit > 0) {

			// ORDER BY по сроку годности: индекс `expires` позволяет начать с самых
			// старых записей, не сканируя таблицу целиком
			$db->query(
				"DELETE FROM {$sqlname}neuro_cache
				  WHERE identity = ?i AND expires_at IS NOT NULL AND expires_at <= NOW()
				  ORDER BY expires_at
				  LIMIT ?i",
				$identity,
				$limit
			);

		}
		else {

			$db->query("DELETE FROM {$sqlname}neuro_cache WHERE identity = ?i AND expires_at IS NOT NULL AND expires_at <= NOW()", $identity);

		}

		return (int)$db->affectedRows();

	}

	/**
	 * Полная очистка кэша (кнопка в интерфейсе).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 *
	 * @return int
	 */
	public static function clear($db, string $sqlname, int $identity): int {

		$size = (int)$db->getOne("SELECT COUNT(*) FROM {$sqlname}neuro_cache WHERE identity = ?i", $identity);

		$db->query("DELETE FROM {$sqlname}neuro_cache WHERE identity = ?i", $identity);

		return $size;

	}

	/**
	 * Сколько ответов сейчас в кэше и когда истекает ближайший.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 *
	 * @return array - count, next
	 */
	public static function stats($db, string $sqlname, int $identity): array {

		$row = (array)$db->getRow(
			"SELECT COUNT(*) AS cnt, MIN(expires_at) AS next FROM {$sqlname}neuro_cache WHERE identity = ?i AND (expires_at IS NULL OR expires_at > NOW())",
			$identity
		);

		return ['count' => (int)($row['cnt'] ?? 0), 'next' => (string)($row['next'] ?? '')];

	}

}
