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
 * Уведомления о заданиях очереди.
 *
 * Задание выполняется в фоне, поэтому человек о результате иначе не узнает.
 * Уведомление ставится штатным механизмом CRM — `Salesman\Notify::edit()`
 * (то же, что делает `neuroEmailSender/cron/cron.import.php`): запись попадает
 * в «колокольчик» пользователя. Собственный канал (почта, телеграм) ядро не
 * заводит — это ответственность CRM, а не вспомогательного плагина.
 *
 * Напоминание (`Salesman\Todo`) здесь намеренно не создаётся: системное
 * сообщение не должно засорять план работы менеджера.
 *
 * Про формат текста. Шаблон колокольчика выводит заголовок и текст без
 * экранирования (`{{{title}}}`), но `Notify::edit()` сам прогоняет `content`
 * через htmlspecialchars. Поэтому: в заголовке разметка допустима, в тексте —
 * нет (там могут оказаться данные от провайдера, и они должны остаться текстом).
 * Строки собираются чистыми функциями и проверяются тестами; отправка отделена
 * от сборки, потому что `Notify` тянет за собой базу.
 *
 * Class Notify
 *
 * @package Neuro
 */
class Notify {

	/**
	 * Тип уведомления CRM.
	 */
	public const TIP = 'note';

	/**
	 * Сколько символов ответа модели показывать в уведомлении.
	 */
	public const EXCERPT = 300;

	/**
	 * Куда ведёт ссылка из уведомления.
	 */
	public const URL = '/plugins/neuroCore/';

	/**
	 * Доступен ли механизм уведомлений.
	 *
	 * @return bool
	 */
	public static function available(): bool {
		return class_exists('Salesman\Notify');
	}

	/**
	 * Параметры уведомления (чистая функция).
	 *
	 * @param int    $iduser
	 * @param string $title   - допустима разметка
	 * @param string $content - только текст: CRM его экранирует
	 *
	 * @return array
	 */
	public static function notifyParams(int $iduser, string $title, string $content): array {

		return [
			'iduser'  => $iduser,
			// автор — 0: уведомление системное, у фонового процесса нет пользователя
			'autor'   => 0,
			'tip'     => self::TIP,
			'title'   => $title,
			'content' => $content,
			'url'     => self::URL,
		];

	}

	/**
	 * Заголовок уведомления (чистая функция).
	 *
	 * @param string $code - код промпта (тип задания)
	 * @param bool   $ok
	 *
	 * @return string
	 */
	public static function title(string $code, bool $ok): string {
		return 'Нейросеть: '.($code !== '' ? $code : 'задание').($ok ? ' выполнено' : ' — ошибка');
	}

	/**
	 * Текст уведомления о неудаче (чистая функция).
	 *
	 * @param array  $task
	 * @param string $error
	 *
	 * @return string
	 */
	public static function failureText(array $task, string $error): string {

		$attempts = (int)($task['attempts'] ?? 0) ?: 1;
		$max      = (int)($task['max_attempts'] ?? 0);

		$out = 'Задание «'.self::excerpt((string)($task['code'] ?? ''), 100).'» не выполнено.';

		$out .= ' Попыток: '.$attempts.($max > 0 ? ' из '.$max : '').'.';

		if (trim($error) !== '') {
			$out .= ' Причина: '.self::excerpt($error, 500).'.';
		}

		return $out.' Проверьте подключение и ключ доступа в разделе «Нейроядро».';

	}

	/**
	 * Текст уведомления об успехе (чистая функция).
	 *
	 * @param array  $task
	 * @param string $result - ответ модели
	 *
	 * @return string
	 */
	public static function successText(array $task, string $result): string {

		$out = 'Задание «'.self::excerpt((string)($task['code'] ?? ''), 100).'» выполнено.';

		$excerpt = self::excerpt($result, self::EXCERPT);

		if ($excerpt !== '') {
			$out .= ' '.$excerpt;
		}

		return $out.' Полный ответ — в журнале «Нейроядра».';

	}

	/**
	 * Короткий фрагмент текста в одну строку (чистая функция).
	 *
	 * Переводы строк заменяются пробелом: уведомление выводится как обычный текст,
	 * и переносы всё равно схлопнулись бы.
	 *
	 * @param string $text
	 * @param int    $limit
	 *
	 * @return string
	 */
	public static function excerpt(string $text, int $limit = self::EXCERPT): string {

		$text = trim((string)preg_replace('/\s+/u', ' ', $text));

		if ($text === '') {
			return '';
		}

		if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $limit) {
			return rtrim(mb_substr($text, 0, $limit, 'UTF-8')).'…';
		}

		if (!function_exists('mb_strlen') && strlen($text) > $limit) {
			return rtrim(substr($text, 0, $limit)).'…';
		}

		return $text;

	}

	/**
	 * Поставить уведомление пользователю.
	 *
	 * Ошибка уведомления не должна ломать обработку задания: уведомление —
	 * вспомогательный канал, а результат уже записан в базу.
	 *
	 * @param int    $iduser
	 * @param string $title
	 * @param string $content
	 *
	 * @return bool
	 */
	public static function send(int $iduser, string $title, string $content): bool {

		if ($iduser <= 0 || !self::available()) {
			return false;
		}

		try {

			$result = \Salesman\Notify::edit(0, self::notifyParams($iduser, $title, $content));

			return ($result['result'] ?? '') === 'ok';

		}
		catch (\Throwable $e) {

			return false;

		}

	}

	/**
	 * Уведомить о неудаче задания.
	 *
	 * @param int    $iduser
	 * @param array  $task
	 * @param string $error
	 *
	 * @return bool
	 */
	public static function queueFailed(int $iduser, array $task, string $error): bool {
		return self::send($iduser, self::title((string)($task['code'] ?? ''), false), self::failureText($task, $error));
	}

	/**
	 * Уведомить об успешном задании.
	 *
	 * @param int    $iduser
	 * @param array  $task
	 * @param string $result
	 *
	 * @return bool
	 */
	public static function queueDone(int $iduser, array $task, string $result): bool {
		return self::send($iduser, self::title((string)($task['code'] ?? ''), true), self::successText($task, $result));
	}

}
