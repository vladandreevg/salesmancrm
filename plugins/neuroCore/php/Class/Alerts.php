<?php
/* ============================ */
/*         SalesMan CRM         */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Оповещения о проблемах, которые человек иначе не заметит.
 *
 * Задания в очереди и вызовы из интерфейса падают тихо: фича показывает ошибку
 * тому, кто нажал кнопку, а если кнопку никто не нажимал (массовый импорт ночью,
 * задание из очереди), о сломанном ключе или вставшей очереди никто не узнает до
 * первого разговора с клиентом. Здесь три таких случая из плана (§9):
 *
 *  - ключ недействителен или не задана переменная окружения;
 *  - очередь выросла выше порога (значит, воркер её не разбирает);
 *  - задание окончательно упало — это уже делает `Neuro\Notify` в воркере.
 *
 * Канал — «колокольчик» CRM (`Salesman\Notify`): он работает и в интерфейсе, и в
 * фоне, и не заводит вторую систему уведомлений. Дополнительно по каждому
 * оповещению вызывается хук `neurocore.alert`, поэтому мост в Telegram/Viber
 * (плагин `userNotifier`) можно подключить, не меняя этот код: у самого
 * `userNotifier` список событий закрытый, и свои события он не принимает.
 *
 * Повторы ограничены: одно и то же оповещение не чаще, чем раз в N минут. Без
 * этого сломанный ключ превратился бы в поток уведомлений на каждый вызов.
 *
 * Class Alerts
 *
 * @package Neuro
 */
class Alerts {

	/**
	 * Порог очереди (0 — оповещать не нужно).
	 */
	public const SETTING_LIMIT = 'QUEUE_ALERT_LIMIT';

	/**
	 * Через сколько минут повторять то же оповещение.
	 */
	public const SETTING_REPEAT = 'ALERT_REPEAT_MINUTES';

	/**
	 * Состояние: когда что оповещалось (метка времени по ключу).
	 */
	public const STATE = 'ALERT_SENT';

	/**
	 * Порог по умолчанию: пятьдесят ждущих заданий означают, что воркер не запускается.
	 */
	public const DEFAULT_LIMIT = 50;

	/**
	 * Повтор по умолчанию: шесть часов.
	 */
	public const DEFAULT_REPEAT = 360;

	/**
	 * Хук для внешних каналов (Telegram, Viber, Slack).
	 */
	public const ACTION = 'neurocore.alert';

	/**
	 * Коды ошибок, которые означают «вызывать модель нечем» без всяких оговорок.
	 */
	public const KEY_CODES = ['http_401', 'http_403', 'provider_key', 'provider_missing'];

	/**
	 * Похоже ли на проблему с ключом по коду и тексту ошибки (чистая функция).
	 *
	 * Одного кода недостаточно: живой VseGPT отвечает на недействительный ключ
	 * кодом **400** и текстом «User with this API key not found» — по коду это
	 * «неверный запрос», а по сути ключ. Но и оповещать на каждый 400 нельзя:
	 * так приходит и ошибка в самом запросе (не то поле, не тот формат), а это
	 * забота того, кто вызвал модель, а не администратора с ключами.
	 *
	 * @param string $code
	 * @param string $message
	 *
	 * @return bool
	 */
	public static function looksLikeKeyProblem(string $code, string $message = ''): bool {

		if ($code === '') {
			return false;
		}

		if (in_array($code, self::KEY_CODES, true)) {
			return true;
		}

		if (strpos($code, 'http_4') !== 0) {
			return false;
		}

		$text = mb_strtolower($message);

		foreach (['api key', 'apikey', 'api_key', 'authentication', 'unauthorized', 'invalid key', 'incorrect api key', 'access denied', 'ключ', 'аутентификац', 'авторизац'] as $needle) {

			if (strpos($text, $needle) !== false) {
				return true;
			}

		}

		return false;

	}

	/**
	 * Безопасный фрагмент текста ошибки провайдера (чистая функция).
	 *
	 * Провайдеры любят повторить ключ в сообщении («your api key: sk-…»), поэтому
	 * похожее на ключ вырезается: в уведомлении и журнале значения ключа быть не
	 * должно, даже если его прислал сам провайдер.
	 *
	 * @param string $message
	 * @param int    $limit
	 *
	 * @return string
	 */
	public static function safeExcerpt(string $message, int $limit = 200): string {

		$text = trim((string)preg_replace('/\s+/u', ' ', $message));

		$text = (string)preg_replace('/\b(AIza[0-9A-Za-z_\-]{10,}|sk-[A-Za-z0-9_\-]{10,}|gsk_[A-Za-z0-9]{10,}|xai-[A-Za-z0-9]{10,})/', '***', $text);

		if (mb_strlen($text) > $limit) {
			$text = rtrim(mb_substr($text, 0, $limit)).'…';
		}

		return $text;

	}

	/**
	 * Порог оповещения об очереди из настроек (чистая функция).
	 *
	 * @param array $settings
	 *
	 * @return int - 0 = выключено
	 */
	public static function limit(array $settings): int {

		// нечисловое значение (например, случайный текст в поле) не должно молча
		// выключать оповещения — берём значение по умолчанию
		if (!array_key_exists(self::SETTING_LIMIT, $settings) || !is_numeric($settings[self::SETTING_LIMIT])) {
			return self::DEFAULT_LIMIT;
		}

		return max(0, min(10000, (int)$settings[self::SETTING_LIMIT]));

	}

	/**
	 * Через сколько минут повторять оповещение (чистая функция).
	 *
	 * @param array $settings
	 *
	 * @return int - минуты (0 — не повторять никогда)
	 */
	public static function repeat(array $settings): int {

		if (!array_key_exists(self::SETTING_REPEAT, $settings) || !is_numeric($settings[self::SETTING_REPEAT])) {
			return self::DEFAULT_REPEAT;
		}

		return max(0, min(10080, (int)$settings[self::SETTING_REPEAT]));

	}

	/**
	 * Пора ли оповещать (чистая функция).
	 *
	 * @param int $now    - текущая метка времени
	 * @param int $last   - когда оповещали в прошлый раз (0 — никогда)
	 * @param int $repeat - минуты между повторами (0 — только один раз)
	 *
	 * @return bool
	 */
	public static function shouldSend(int $now, int $last, int $repeat): bool {

		if ($last <= 0) {
			return true;
		}

		if ($repeat <= 0) {
			return false;
		}

		return ($now - $last) >= $repeat * 60;

	}

	/**
	 * Состояние оповещений из настроек (чистая функция).
	 *
	 * @param array $settings
	 *
	 * @return array - [ключ => метка времени]
	 */
	public static function state(array $settings): array {

		$state = $settings[self::STATE] ?? [];

		if (!is_array($state)) {
			$state = json_decode((string)$state, true);
		}

		return is_array($state) ? $state : [];

	}

	/**
	 * Отметка об отправленном оповещении (чистая функция).
	 *
	 * @param array  $settings
	 * @param string $key
	 * @param int    $now
	 *
	 * @return array - новые настройки
	 */
	public static function remember(array $settings, string $key, int $now): array {

		$state = self::state($settings);

		$state[$key] = $now;

		// состояние не должно расти бесконечно: держим только свежие записи
		if (count($state) > 20) {
			arsort($state);
			$state = array_slice($state, 0, 20, true);
		}

		$settings[self::STATE] = $state;

		return $settings;

	}

	/**
	 * Текст оповещения о вставшей очереди (чистая функция).
	 *
	 * @param int $pending - сколько заданий ждёт
	 * @param int $limit   - порог
	 * @param int $errors  - сколько заданий с ошибкой
	 *
	 * @return string
	 */
	public static function queueText(int $pending, int $limit, int $errors = 0): string {

		$out = 'Очередь Нейроядра не разбирается: ждут выполнения '.$pending.' заданий (порог '.$limit.').';

		if ($errors > 0) {
			$out .= ' Заданий с ошибкой: '.$errors.'.';
		}

		return $out.' Похоже, не запускается планировщик: без него очередь разбирается только вручную — php plugins/neuroCore/cron/worker.php.';

	}

	/**
	 * Текст оповещения о неработающем подключении (чистая функция).
	 *
	 * Значения ключа в тексте нет и быть не может — только название подключения и
	 * код ошибки.
	 *
	 * @param string $provider - название подключения
	 * @param string $code     - код ошибки
	 * @param string $message  - текст ошибки провайдера (для незнакомых кодов)
	 *
	 * @return string
	 */
	public static function keyText(string $provider, string $code, string $message = ''): string {

		$provider = trim($provider);

		$reason = [
			'http_401'         => 'ключ недействителен или отозван',
			'http_403'         => 'доступ запрещён (проверьте права ключа и регион)',
			'provider_key'     => 'ключ недоступен: не сохранён в подключении, не найден в переменной окружения или не расшифровывается',
			'provider_missing' => 'не выбрано ни одного включённого подключения',
		][$code] ?? '';

		if ($reason === '') {

			// незнакомый код (например, 400 у VseGPT на неверный ключ): показываем,
			// что ответил провайдер, — без значений ключа
			$excerpt = self::safeExcerpt($message);

			$reason = 'провайдер отказал ('.$code.')'.($excerpt !== '' ? ': '.$excerpt : '');

		}

		$out = $provider !== ''
			? 'Подключение «'.$provider.'» не работает: '.$reason.'.'
			: 'Подключение к нейросети не работает: '.$reason.'.';

		return $out.' Пока это не исправлено, задания к этой нейросети выполняться не будут. Проверьте подключение в разделе «Нейроядро» → «Подключения».';

	}

	/**
	 * Кому адресованы системные оповещения: администраторам аккаунта.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 *
	 * @return array - список iduser
	 */
	public static function admins($db, string $sqlname, int $identity): array {

		try {

			return array_map('intval', (array)$db->getCol(
				"SELECT iduser FROM {$sqlname}user WHERE isadmin = 'on' AND secrty = 'yes' AND identity = ?i",
				$identity
			));

		}
		catch (\Throwable $e) {

			return [];

		}

	}

	/**
	 * Оповещение о проблеме с ключом (с ограничением повторов).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param array  $providerRow - строка подключения (нужно только название)
	 * @param string $code        - код ошибки
	 * @param string $message     - текст ошибки провайдера
	 *
	 * @return bool - отправлено ли оповещение сейчас
	 */
	public static function keyProblem($db, string $sqlname, int $identity, array $providerRow = [], string $code = '', string $message = ''): bool {

		if ($db === NULL) {
			return false;
		}

		$name = (string)($providerRow['name'] ?? '');

		$key = 'key:'.($name !== '' ? $name : 'none');

		return self::dispatch($db, $sqlname, $identity, $key, 'Нейроядро: проблема с подключением', self::keyText($name, $code, $message), [
			'provider' => $name,
			'code'     => $code,
		]);

	}

	/**
	 * Сколько минут без выполненных заданий считать признаком «воркер не работает».
	 *
	 * Совпадает с `Queue::STALE_MINUTES`: если задание не завершалось дольше, значит
	 * цепочка «планировщик → воркер → очередь» разорвана.
	 */
	public const GRACE_MINUTES = 15;

	/**
	 * Нужно ли оповещать о застрявшей очереди (чистая функция).
	 *
	 * Одного порога мало: во время большого импорта очередь законно перерастает
	 * порог — воркер разбирает её по несколько заданий в минуту. Поэтому сигнал
	 * подаётся, только если ждущих заданий больше порога, среди них есть готовые к
	 * выполнению, и при этом давно ничего не завершалось.
	 *
	 * @param int $pending      - сколько заданий ждёт выполнения
	 * @param int $ready        - сколько из них готовы прямо сейчас (не в паузе)
	 * @param int $lastDoneMin  - сколько минут назад завершалось последнее задание
	 *                            (PHP_INT_MAX — не завершалось никогда)
	 * @param int $limit        - порог (0 — оповещения выключены)
	 * @param int $graceMinutes - сколько минут тишины считать поломкой
	 *
	 * @return bool
	 */
	public static function shouldWarnQueue(int $pending, int $ready, int $lastDoneMin, int $limit, int $graceMinutes = self::GRACE_MINUTES): bool {

		if ($limit <= 0 || $pending < $limit) {
			return false;
		}

		// ничего не готово к запуску — задания в паузе между попытками, это не поломка
		if ($ready <= 0) {
			return false;
		}

		// задания завершались недавно — воркер работает и разбирает очередь
		return $lastDoneMin > $graceMinutes;

	}

	/**
	 * Оповещение о вставшей очереди (с ограничением повторов).
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param bool   $force     - не проверять, когда очередь разбиралась в последний раз
	 *
	 * @return bool
	 */
	public static function queueTooLong($db, string $sqlname, int $identity, bool $force = false): bool {

		if ($db === NULL) {
			return false;
		}

		$settings = (array)customSettings('neuroCore');
		$limit    = self::limit($settings);

		if ($limit <= 0) {
			return false;
		}

		$stats = Queue::stats($db, $sqlname, $identity);

		$pending = (int)($stats[Queue::STATUS_NEW] ?? 0);

		if ($pending < $limit) {
			return false;
		}

		if (!$force) {

			$ready = (int)$db->getOne(
				"SELECT COUNT(*) FROM {$sqlname}neuro_task
				  WHERE status = 'new' AND (run_after IS NULL OR run_after <= NOW()) AND identity = ?i",
				$identity
			);

			$lastDone = (string)$db->getOne(
				"SELECT MAX(finished_at) FROM {$sqlname}neuro_task WHERE status = 'ok' AND identity = ?i",
				$identity
			);

			$lastDoneMin = ($lastDone === '' || $lastDone === NULL)
				? PHP_INT_MAX
				: (int)round((time() - (int)strtotime($lastDone)) / 60);

			if (!self::shouldWarnQueue($pending, $ready, $lastDoneMin, $limit)) {
				return false;
			}

		}

		return self::dispatch($db, $sqlname, $identity, 'queue', 'Нейроядро: очередь не разбирается', self::queueText($pending, $limit, (int)($stats[Queue::STATUS_ERROR] ?? 0)), [
			'pending' => $pending,
			'limit'   => $limit,
			'errors'  => (int)($stats[Queue::STATUS_ERROR] ?? 0),
		]);

	}

	/**
	 * Отправка оповещения: журнал, «колокольчик», внешний хук.
	 *
	 * Ошибка любого из каналов не должна ломать вызывающий код: оповещение —
	 * вспомогательная вещь, а вызов модели или разбор очереди важнее.
	 *
	 * @param object $db
	 * @param string $sqlname
	 * @param int    $identity
	 * @param string $key      - ключ ограничения повторов
	 * @param string $title
	 * @param string $text
	 * @param array  $context
	 *
	 * @return bool - отправлено ли сейчас
	 */
	private static function dispatch($db, string $sqlname, int $identity, string $key, string $title, string $text, array $context = []): bool {

		$settings = (array)customSettings('neuroCore');
		$now      = time();
		$last     = (int)(self::state($settings)[$key] ?? 0);

		if (!self::shouldSend($now, $last, self::repeat($settings))) {
			return false;
		}

		// 1. журнал: след остаётся даже если уведомление не ушло
		Log::write($db, $sqlname, 0, 'warn', $title.': '.$text, $context, $identity);

		// 2. «колокольчик» администраторам
		if (Notify::available()) {

			foreach (self::admins($db, $sqlname, $identity) as $iduser) {
				Notify::send($iduser, $title, $text);
			}

		}

		// 3. хук для внешних каналов (мост в Telegram/Viber делает тот, кому это нужно)
		try {

			if (isset($GLOBALS['hooks']) && method_exists($GLOBALS['hooks'], 'do_action')) {
				$GLOBALS['hooks']->do_action(self::ACTION, [['key' => $key, 'title' => $title, 'text' => $text, 'context' => $context]]);
			}

		}
		catch (\Throwable $e) {
			// чужой обработчик не должен ломать оповещение
		}

		// 4. отметка о повторе
		customSettings('neuroCore', 'put', ['params' => self::remember($settings, $key, $now)]);

		return true;

	}

}
