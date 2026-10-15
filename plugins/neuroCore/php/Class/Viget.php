<?php
/* ============================ */
/*         SalesMan CRM         */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Neuro;

/**
 * Регистрация виджета рабочего стола в карте виджетов CRM.
 *
 * Виджет подключается не кодом, а записью в `cash/map.vigets.castom.json` — файл
 * читает и рабочий стол, и страница «Мои настройки». Поэтому плагин добавляет
 * запись при активации и обновлении и убирает её при удалении: без записи файл
 * виджета существует, но в списке виджетов его нет и включить его нельзя.
 *
 * Почему `map.vigets.castom.json`, а не `map.vigets.json`: второй перезаписывается
 * при обновлении дистрибутива (это прямо написано в developer/v3/docs/vidget.md).
 *
 * Запись чужих виджетов не трогается: файл читается, изменяется только наш ключ и
 * записывается целиком атомарно (cacheWrite — временный файл + rename), иначе
 * параллельный запрос мог бы оставить обрезанный JSON и сломать рабочий стол всем.
 *
 * Class Viget
 *
 * @package Neuro
 */
class Viget {

	/**
	 * Ключ виджета в карте.
	 */
	public const KEY = 'neuro_core';

	/**
	 * Адрес файла виджета относительно корня установки.
	 *
	 * Именно `plugins/neuroCore/viget.neuro.php`, без подкаталога `vigets/`:
	 * страница «Мои настройки» (content/ajax/user.settings.php) ищет виджет по
	 * адресу `content/<url>`, если в url встречается подстрока «vigets», и в
	 * подкаталоге плагина его не находит — виджет не появляется в списке.
	 */
	public const URL = 'plugins/neuroCore/viget.neuro.php';

	/**
	 * Запись виджета для карты (чистая функция — проверяется тестом).
	 *
	 * @return array
	 */
	public static function entry(): array {

		return [
			'name'               => 'Нейроядро: очередь и расход',
			'container'          => 'neuro_core',
			'containerclass'     => 'relativ',
			'url'                => self::URL,
			'icon'               => 'icon-magic',
			'active'             => 'on',
			'tooltips'           => 'Состояние очереди заданий к нейросети и расход за сегодня',
			'tooltips-position'  => 'top',
			'actionPlus'         => '<a href="/plugins/neuroCore/" class="pull-aright refresh gray mr5" target="_blank" title="Открыть Нейроядро"><i class="icon-list broun"></i></a>',
			'class'              => 'scroll noscroll',
			'expressReport'      => '',
			'expressReportTitle' => '',
			'settingsURL'        => '',
			'description'        => 'Очередь заданий к нейросети, расход за сегодня и последние ошибки',
			'width'              => '50',
			'height'             => '',
		];

	}

	/**
	 * Путь к карте виджетов.
	 *
	 * @param string $rootpath
	 *
	 * @return string
	 */
	public static function file(string $rootpath): string {
		return rtrim($rootpath, '/\\').'/cash/map.vigets.castom.json';
	}

	/**
	 * Разбор карты виджетов из текста (чистая функция).
	 *
	 * @param string $json
	 *
	 * @return array
	 */
	public static function parse(string $json): array {

		$json = trim($json);

		if ($json === '') {
			return [];
		}

		$data = json_decode($json, true);

		return is_array($data) ? $data : [];

	}

	/**
	 * Есть ли запись виджета в карте прямо сейчас.
	 *
	 * Нужна странице настроек: файл `cash/map.vigets.castom.json` может быть
	 * очищен или удалён (например, при сбросе кэша настроек), и тогда виджет
	 * исчезает из «Моих настроек», хотя плагин считает, что всё зарегистрировано.
	 * Проверка по файлу показывает настоящее положение дел.
	 *
	 * @param string $rootpath
	 *
	 * @return bool
	 */
	public static function registered(string $rootpath): bool {

		$file = self::file($rootpath);

		if (!file_exists($file)) {
			return false;
		}

		$map = self::parse((string)file_get_contents($file));

		return isset($map[self::KEY]) && trim((string)($map[self::KEY]['url'] ?? '')) !== '';

	}

	/**
	 * Добавляет (или освежает) запись виджета в карте.
	 *
	 * Идемпотентно: повторный вызов при обновлении плагина только приводит запись
	 * к текущему виду и не создаёт дублей. Существующие чужие виджеты сохраняются.
	 *
	 * @param string $rootpath
	 *
	 * @return string - created|updated|exists|error:…
	 */
	public static function register(string $rootpath): string {

		$file = self::file($rootpath);

		try {

			$current = file_exists($file) ? self::parse((string)file_get_contents($file)) : [];

			$entry = self::entry();
			$have  = isset($current[self::KEY]) ? (array)$current[self::KEY] : [];

			if ($have === $entry) {
				return 'exists';
			}

			$status = empty($have) ? 'created' : 'updated';

			$current[self::KEY] = $entry;

			$json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

			if ($json === false) {
				return 'error: не удалось собрать карту виджетов';
			}

			if (!is_dir(dirname($file))) {
				return 'error: не найден каталог cash';
			}

			// атомарная запись: рабочий стол читает этот файл на каждой странице
			if (function_exists('cacheWrite')) {
				cacheWrite($file, $json);
			}
			else {
				file_put_contents($file, $json);
			}

			// Проверяем результат записи, а не право на неё: is_writable() в Windows
			// отвечает «нет» для только что созданного каталога, хотя запись проходит
			// (на этом спотыкался тест виджета). Читаем файл обратно и убеждаемся, что
			// запись на месте.
			$check = self::parse(file_exists($file) ? (string)file_get_contents($file) : '');

			if (!isset($check[self::KEY])) {
				return 'error: не удалось записать карту виджетов (проверьте права на каталог cash)';
			}

			return $status;

		}
		catch (\Throwable $e) {

			return 'error: '.$e->getMessage();

		}

	}

	/**
	 * Убирает запись виджета из карты (при удалении плагина).
	 *
	 * @param string $rootpath
	 *
	 * @return bool
	 */
	public static function unregister(string $rootpath): bool {

		$file = self::file($rootpath);

		try {

			if (!file_exists($file)) {
				return true;
			}

			$current = self::parse((string)file_get_contents($file));

			if (!isset($current[self::KEY])) {
				return true;
			}

			unset($current[self::KEY]);

			$json = json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

			if ($json === false) {
				return false;
			}

			if (function_exists('cacheWrite')) {
				cacheWrite($file, $json);
			}
			else {
				file_put_contents($file, $json);
			}

			return true;

		}
		catch (\Throwable $e) {

			return false;

		}

	}

}
