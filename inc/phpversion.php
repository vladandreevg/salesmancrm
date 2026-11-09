<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/**
 * Гейт версии PHP для миграции на 8.5.
 *
 * Единая точка правды для установщика (_install/install.php) и страницы
 * «Система» (content/admin/sysinfo.php): там раньше был whitelist
 * '7.2/7.3/7.4/8.1', из-за которого PHP 8.5 объявлялся неподдерживаемым.
 *
 * Минимум — 8.3: это первая ветка с исправлением CVE-2026-109618/619
 * (PHP2Shell), см. .dsh/arch/PLAN-php85.md. Верхней границы нет.
 */

if (!defined('SM_PHP_MIN_VERSION')) {
	define('SM_PHP_MIN_VERSION', '8.3');
}

if (!function_exists('sm_php_version_supported')) {

	/**
	 * Поддерживается ли версия PHP.
	 *
	 * @param string|null $version Проверяемая версия (например, '8.5.12'); null — текущая PHP_VERSION.
	 *
	 * @return bool
	 */
	function sm_php_version_supported(?string $version = null): bool {

		return version_compare($version ?? PHP_VERSION, SM_PHP_MIN_VERSION, '>=');

	}

}

/**
 * Сборки PHP, в которых закрыты CVE-2026-109618 / CVE-2026-109619 («PHP2Shell»).
 *
 * Минимальная поддерживаемая ветка (8.3) в целом хороша, но её ранние сборки
 * уязвимы. Ветки, которых нет в карте (8.6+), считаются безопасными: данных
 * о незакрытых уязвимостях этих веток у проекта нет.
 *
 * См. .dsh/arch/PLAN-php85.md §1.1.
 */
if (!defined('SM_PHP_SECURE_BUILDS')) {

	define('SM_PHP_SECURE_BUILDS', [
		'8.3' => '8.3.36',
		'8.4' => '8.4.27',
		'8.5' => '8.5.12',
	]);

}

if (!function_exists('sm_php_version_secure')) {

	/**
	 * Содержит ли сборка PHP исправления CVE-2026-109618/619.
	 *
	 * Отличие от sm_php_version_supported(): поддерживаемая, но уязвимая сборка
	 * (например, 8.3.0 или 8.5.4) здесь даёт false — это повод для предупреждения
	 * на странице «Система», а не для блокировки.
	 *
	 * @param string|null $version Проверяемая версия; null — текущая PHP_VERSION.
	 *
	 * @return bool
	 */
	function sm_php_version_secure(?string $version = null): bool {

		$version = $version ?? PHP_VERSION;

		if (!sm_php_version_supported($version)) {
			return false;
		}

		if (preg_match('/^(\d+)\.(\d+)/', $version, $match)) {

			$branch = $match[1].'.'.$match[2];
			$fixed  = SM_PHP_SECURE_BUILDS[ $branch ] ?? null;

			if ($fixed !== null) {
				return version_compare($version, $fixed, '>=');
			}

		}

		return true;

	}

}
