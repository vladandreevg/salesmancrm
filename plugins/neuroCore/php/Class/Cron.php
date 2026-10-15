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
 * Интеграция с плагином «Планировщик заданий» (cronManager).
 *
 * Нейроядро не заводит собственную настройку пути до PHP: единственный владелец
 * этого значения в системе — cronManager. Здесь только чтение.
 *
 * Важно про определение пути: getPhpInfo()['bin'] возвращает PHP_BINARY, а под
 * mod_php в Windows это httpd.exe, то есть путь к Apache, а не к интерпретатору.
 * Именно поэтому cronManager на не-Linux платформах просто использует 'php' из PATH.
 * Здесь повторяется то же правило — иначе в настройках показывался бы путь к Apache.
 *
 * Фоновые задачи (очередь заданий) появятся на этапе 3; регистрация и снятие задач
 * через Cronman\Cronman будут добавлены сюда же.
 *
 * Class Cron
 *
 * @package Neuro
 */
class Cron {

	/**
	 * Пакет плагина-планировщика.
	 */
	public const MANAGER = 'cronManager';

	/**
	 * Установлен ли плагин «Планировщик заданий».
	 *
	 * @param string $rootpath
	 *
	 * @return bool
	 */
	public static function managerInstalled(string $rootpath): bool {
		return file_exists(rtrim($rootpath, '/\\').'/plugins/'.self::MANAGER.'/php/autoload.php');
	}

	/**
	 * Путь до PHP из настроек планировщика ('' — не задан).
	 *
	 * @param array $managerSettings - customSettings('cronManager')
	 *
	 * @return string
	 */
	public static function managerPhpBinary(array $managerSettings): string {
		return trim((string)($managerSettings['php'] ?? ''));
	}

	/**
	 * Запасной вариант, когда планировщик ещё не настроен. Повторяет правило
	 * cronManager: на Windows достаточно 'php' (PHP_BINARY там — Apache),
	 * на Linux берётся путь до CLI-интерпретатора без суффикса -cgi.
	 *
	 * @param string $osFamily - PHP_OS_FAMILY
	 * @param string $binary   - PHP_BINARY
	 *
	 * @return string
	 */
	public static function fallbackPhpBinary(string $osFamily, string $binary): string {

		if ($osFamily !== 'Linux') {
			return 'php';
		}

		return str_replace('-cgi', '', $binary);

	}

	/**
	 * Действующее значение для поля bin задания планировщика.
	 *
	 * Подсказка из настроек cronManager используется, только если файл реально есть
	 * на сервере. Иначе берётся правило cronManager. Это важно: Cronman собирает
	 * команду из bin самой задачи (Cronman.php:427) и значение из своих настроек
	 * не читает — то есть поле php там забытая подсказка, а не источник истины.
	 *
	 * @param array  $managerSettings
	 * @param string $osFamily
	 * @param string $binary
	 *
	 * @return string
	 */
	public static function effectivePhpBinary(array $managerSettings, string $osFamily, string $binary): string {

		$hint = self::managerPhpBinary($managerSettings);

		if ($hint !== '' && self::pathLooksValid($hint)) {
			return $hint;
		}

		return self::fallbackPhpBinary($osFamily, $binary);

	}

	/**
	 * Похож ли путь на пригодный для запуска файл.
	 *
	 * Нужно потому, что cronManager при активации записывает в свои настройки
	 * значение по умолчанию '/opt/php72/bin/php'. На Windows это заведомо чужой
	 * путь, и показывать его как рабочий нельзя — лучше предупредить администратора.
	 *
	 * @param string $path - путь из настроек планировщика
	 *
	 * @return bool
	 */
	public static function pathLooksValid(string $path): bool {

		$path = trim($path);

		if ($path === '') {
			return false;
		}

		// без разделителей это имя команды, которое ищется в PATH: 'php', 'php.exe'
		if (strpos($path, '/') === false && strpos($path, '\\') === false) {
			return true;
		}

		return is_file($path);

	}

}
