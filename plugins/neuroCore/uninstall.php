<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Удаление плагина Нейроядро.
 *
 * Вызывается страницей управления плагинами (content/admin/plugins.php,
 * action=uninstall): там определяется константа SM_UNINSTALL_PLUGIN, подключается
 * этот файл и затем вызывается хук plugin_uninstall. Работа выполняется в
 * обработчике хука — как в остальных плагинах SalesMan.
 *
 * Пользователь предупреждён в интерфейсе, что вместе с плагином удаляются
 * собранные им данные.
 */

if (!defined('SM_UNINSTALL_PLUGIN')) {
	die;
}

global $hooks;

require_once __DIR__.'/php/autoload.php';

$hooks->add_action('plugin_uninstall', 'neurocore_uninstall');

/**
 * Удаляет данные плагина: задачу планировщика, настройки и таблицы ядра.
 *
 * @param array $argv - ["name" => имя плагина]
 */
function neurocore_uninstall(array $argv = []): void {

	if (($argv['name'] ?? '') !== 'neuroCore') {
		return;
	}

	$rootpath = (string)$GLOBALS['rootpath'];

	// 1. Снимаем задачу планировщика, если плагин её ставил
	if (file_exists($rootpath.'/plugins/cronManager/php/autoload.php')) {

		require_once $rootpath.'/plugins/cronManager/php/autoload.php';

		if (class_exists('Cronman\Cronman')) {

			try {

				$cron = new Cronman\Cronman();

				$task = $cron->taskExist($rootpath.'/plugins/neuroCore/cron/worker.php');

				if ($task > 0) {
					$cron->deleteTask($task);
				}

			}
			catch (Exception $e) {

				// планировщик может быть не установлен или не настроен —
				// на удаление собранных данных это не влияет

			}

		}

	}

	// 2. Виджет рабочего стола: убираем запись из карты виджетов, иначе она
	// останется ссылкой на удалённый файл и сломает рабочий стол сотрудникам
	try {

		Neuro\Viget::unregister($rootpath);

	}
	catch (Throwable $e) {

		// карта виджетов может быть недоступна для записи — удаление плагина
		// из-за этого прерывать нельзя

	}

	// 3. Настройки плагина
	customSettings('neuroCore', 'delete');

	// 4. Таблицы ядра
	Neuro\Schema::uninstall($GLOBALS['db'], (string)$GLOBALS['sqlname']);

}
