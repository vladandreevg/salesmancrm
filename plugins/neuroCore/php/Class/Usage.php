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
 * Учёт расхода на нейросети.
 *
 * Расход — это отчётность, а не ограничение: по решению владельца продукта ядро
 * ничего не блокирует по бюджету. Но без учёта невозможно ответить на вопрос
 * «куда уходят деньги», особенно когда подключено несколько моделей с разной
 * ценой.
 *
 * Учитывается каждый вызов, который вернул токены, — независимо от того, пришёл
 * он из очереди, из интерфейса или от плагина-фичи: запись делает фасад
 * (`Neuro\Neuro::run()`), то есть единственное место, где вызов модели завершается
 * успешно. Дублирования нет: воркер очереди отдельно ничего не начисляет.
 *
 * Стоимость считается только из прайсов, заданных администратором в подключении
 * (`price_in`/`price_out` за миллион токенов). Если они не заданы — в отчёте будут
 * точные токены и `cost = NULL`, а не выдуманная цифра.
 *
 * Class Usage
 *
 * @package Neuro
 */
class Usage {

	/**
	 * День учёта (Y-m-d).
	 *
	 * @param string $date - '' = сегодня
	 *
	 * @return string
	 */
	public static function day(string $date = ''): string {
		return $date !== '' ? $date : date('Y-m-d');
	}

	/**
	 * Запись одного вызова.
	 *
	 * Инкремент, а не пересчёт: пересчитывать пришлось бы всю очередь на каждый
	 * вызов, а очередь — это история за месяцы. Уникальный ключ (день, сотрудник,
	 * тип задачи, модель) превращает повторную запись в прибавление счётчиков, а
	 * не в новую строку.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param array  $call   - code, iduser, model, usage (in/out/cost), currency
	 * @param int    $identity
	 *
	 * @return void
	 */
	public static function record($db, string $sqlname, array $call, int $identity): void {

		$tokensIn  = (int)($call['usage']['in'] ?? 0);
		$tokensOut = (int)($call['usage']['out'] ?? 0);
		$cost      = $call['usage']['cost'] ?? NULL;
		$currency  = Providers::currency((string)($call['currency'] ?? ''));

		$row = self::row($call);

		// Цена прибавляется только если она известна: NULL-прайс не должен
		// превращать уже накопленную сумму в NULL (в MySQL «число + NULL = NULL»),
		// поэтому сумма прибавляется через IF(... IS NULL, cost, ...).
		$db->query(
			"INSERT INTO {$sqlname}neuro_usage
			        SET datum = ?s, iduser = ?i, code = ?s, model = ?s, calls = 1, tokens_in = ?i, tokens_out = ?i, cost = ?s, currency = ?s, identity = ?i
			 ON DUPLICATE KEY UPDATE
			        calls      = calls + 1,
			        tokens_in  = tokens_in + ?i,
			        tokens_out = tokens_out + ?i,
			        cost       = IF(?s IS NULL, cost, COALESCE(cost, 0) + ?s),
			        currency   = IF(COALESCE(currency, '') = '', ?s, currency)",
			$row[0],
			$row[1],
			$row[2],
			$row[3],
			$tokensIn,
			$tokensOut,
			$cost,
			$currency,
			$identity,
			$tokensIn,
			$tokensOut,
			$cost,
			$cost,
			$currency
		);

	}

	/**
	 * Значения строки учёта (чистая функция — проверяется тестом).
	 *
	 * @param array $call - code, iduser, model, usage
	 *
	 * @return array
	 */
	public static function row(array $call): array {

		return [
			self::day((string)($call['date'] ?? '')),
			(int)($call['iduser'] ?? 0),
			Prompt::code((string)($call['code'] ?? '')),
			trim((string)($call['model'] ?? '')),
		];

	}

	/**
	 * Отчёт за период.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param string $from - Y-m-d
	 * @param string $to   - Y-m-d
	 *
	 * @return array - rows, days, users, codes, models, total
	 */
	public static function report($db, string $sqlname, int $identity, string $from = '', string $to = ''): array {

		$from = self::day($from);
		$to   = $to !== '' ? $to : $from;

		$rows = (array)$db->getAll(
			"SELECT datum, iduser, code, model, calls, tokens_in, tokens_out, cost, currency
			   FROM {$sqlname}neuro_usage
			  WHERE identity = ?i AND datum >= ?s AND datum <= ?s
			  ORDER BY datum DESC, calls DESC",
			$identity,
			$from,
			$to
		);

		$days   = [];
		$users  = [];
		$codes  = [];
		$models = [];
		$sums   = [];

		foreach ($rows as $row) {

			$calls    = (int)$row['calls'];
			$in       = (int)$row['tokens_in'];
			$out      = (int)$row['tokens_out'];
			$cost     = $row['cost'] !== NULL ? (float)$row['cost'] : NULL;
			$currency = (string)($row['currency'] ?? '');

			self::add($days, (string)$row['datum'], $calls, $in, $out, $cost, $currency);
			self::add($users, (int)$row['iduser'], $calls, $in, $out, $cost, $currency);
			self::add($codes, (string)$row['code'], $calls, $in, $out, $cost, $currency);
			self::add($models, (string)$row['model'], $calls, $in, $out, $cost, $currency);
			self::add($sums, 'all', $calls, $in, $out, $cost, $currency);

		}

		$total = isset($sums['all']) ? $sums['all'] : self::blank();

		return [
			'from'   => $from,
			'to'     => $to,
			'rows'   => $rows,
			'days'   => $days,
			'users'  => $users,
			'codes'  => $codes,
			'models' => $models,
			'total'  => $total,
		];

	}

	/**
	 * Средние токены на вызов по типу задачи — для оценки перед массовой операцией.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param string $code
	 *
	 * @return array - calls, in, out (средние)
	 */
	public static function average($db, string $sqlname, int $identity, string $code): array {

		$row = (array)$db->getRow(
			"SELECT SUM(calls) AS calls, SUM(tokens_in) AS tokens_in, SUM(tokens_out) AS tokens_out
			   FROM {$sqlname}neuro_usage WHERE code = ?s AND identity = ?i",
			Prompt::code($code),
			$identity
		);

		$calls = (int)($row['calls'] ?? 0);

		if ($calls <= 0) {
			return ['calls' => 0, 'in' => 0, 'out' => 0];
		}

		return [
			'calls' => $calls,
			'in'    => (int)round((int)$row['tokens_in'] / $calls),
			'out'   => (int)round((int)$row['tokens_out'] / $calls),
		];

	}

	/**
	 * Оценка стоимости массовой операции (чистая функция).
	 *
	 * Нужна перед запуском импорта на сотни клиентов: «будет N вызовов, это
	 * примерно X». Если прайсы не заданы или нет истории вызовов, оценка честно
	 * помечается как невозможная, а не показывается нулём.
	 *
	 * Плата за вызов (`call`) учитывается отдельно от токенов: у онлайн-моделей
	 * VseGPT она берётся за каждый поисковый запрос независимо от расхода и
	 * обычно перевешивает стоимость токенов.
	 *
	 * @param int        $calls
	 * @param array      $average - средние токены (in/out)
	 * @param array|null $prices  - ['in' => цена за 1М, 'out' => цена за 1М, 'call' => цена за вызов, 'currency' => метка]
	 *
	 * @return array - calls, cost, priced, reason, currency
	 */
	public static function estimate(int $calls, array $average, $prices = NULL): array {

		$out = [
			'calls'    => max(0, $calls),
			'cost'     => NULL,
			'priced'   => false,
			'reason'   => '',
			'currency' => is_array($prices) ? Providers::currency((string)($prices['currency'] ?? '')) : '',
		];

		if ($out['calls'] === 0) {
			$out['reason'] = 'Нет заданий для оценки';
			return $out;
		}

		if ((int)($average['calls'] ?? 0) <= 0) {
			$out['reason'] = 'Пока нет истории вызовов этого типа — оценка появится после первых заданий';
			return $out;
		}

		$hasTokens = is_array($prices) && isset($prices['in']) && isset($prices['out']);
		$hasCall   = is_array($prices) && isset($prices['call']) && $prices['call'] !== NULL;

		if ($hasTokens) {

			$cost = ((int)$average['in'] * $out['calls'] / 1000000) * (float)$prices['in']
				+ ((int)$average['out'] * $out['calls'] / 1000000) * (float)$prices['out'];

			if ($hasCall) {
				$cost += (float)$prices['call'] * $out['calls'];
			}

			$out['cost']   = round($cost, 4);
			$out['priced'] = true;

			return $out;

		}

		if ($hasCall) {

			// цены за токены нет, но плата за вызов известна: показываем её и прямо
			// говорим, что это не полная стоимость
			$out['cost']   = round((float)$prices['call'] * $out['calls'], 4);
			$out['priced'] = true;
			$out['reason'] = 'Учтена только плата за вызов: цены за токены в подключении не заданы';

			return $out;

		}

		$out['reason'] = 'В подключении не заданы цены за миллион токенов — считается только расход в токенах';

		return $out;

	}

	/**
	 * Пустая строка отчёта.
	 *
	 * @return array
	 */
	public static function blank(): array {
		return ['calls' => 0, 'tokens_in' => 0, 'tokens_out' => 0, 'cost' => NULL, 'priced' => false, 'currency' => '', 'mixed' => false, 'currencies' => []];
	}

	/**
	 * Складывает строку отчёта в измерение (чистая функция — проверяется тестом).
	 *
	 * Суммы по валютам копятся отдельно, а `cost` заполняется одной цифрой только
	 * если все учтённые суммы в одной валюте. Складывать рубли с долларами нельзя:
	 * получилось бы число, которого не существует, — вместо этого отчёт отдаёт
	 * разбивку `currencies`, а `mixed` говорит интерфейсу показать её.
	 *
	 * @param array      $bucket
	 * @param mixed      $key
	 * @param int        $calls
	 * @param int        $in
	 * @param int        $out
	 * @param float|null $cost
	 * @param string     $currency - '' = валюта не указана в подключении
	 *
	 * @return void
	 */
	private static function add(array &$bucket, $key, int $calls, int $in, int $out, $cost, string $currency = ''): void {

		if (!isset($bucket[$key])) {
			$bucket[$key] = self::blank();
		}

		$bucket[$key]['calls']      += $calls;
		$bucket[$key]['tokens_in']  += $in;
		$bucket[$key]['tokens_out'] += $out;

		if ($cost !== NULL) {

			$bucket[$key]['priced'] = true;

			if (!isset($bucket[$key]['currencies'][$currency])) {
				$bucket[$key]['currencies'][$currency] = 0.0;
			}

			$bucket[$key]['currencies'][$currency] += $cost;

			// одна валюта — одна сумма; валют несколько — итога нет, есть разбивка
			$bucket[$key]['mixed'] = count($bucket[$key]['currencies']) > 1;

			if ($bucket[$key]['mixed']) {
				$bucket[$key]['cost']     = NULL;
				$bucket[$key]['currency'] = '';
			}
			else {
				$bucket[$key]['currency'] = (string)key($bucket[$key]['currencies']);
				$bucket[$key]['cost']     = round((float)current($bucket[$key]['currencies']), 6);
			}

		}

	}

}
