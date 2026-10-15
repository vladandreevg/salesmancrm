<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

global $rootpath, $hooks;

/**
 * Плагин Нейроядро.
 *
 * Загружается ядром как plugins/neuroCore/neurocore.php (см. inc/func.php
 * loadIncludes(): имя файла = имя папки в нижнем регистре), а при
 * активации/деактивации — страницей управления плагинами
 * (content/admin/plugins.php, где определена константа SMPLUGIN).
 *
 * Таблицы создаются на хуке активации: отдельного install.php в жизненном цикле
 * плагинов SalesMan нет.
 */

/**
 * Виджет «Нейроядро: очередь и расход» — только для администраторов.
 *
 * Данные виджета закрыты на сервере (`viget.neuro.php` не отдаёт ничего без прав
 * администратора), а здесь закрывается видимость: страницы «Мои настройки» и
 * рабочий стол — файлы дистрибутива, и правки в них теряются при обновлении CRM.
 * Своего признака «только для админа» в карте виджетов нет: там есть лишь
 * привязка к модулю, а она проверяется по аккаунту, а не по пользователю.
 *
 * Поэтому сотруднику без прав скрипт плагина убирает строку виджета из «Моих
 * настроек» (вместе со строкой уходит hidden-поле `order[]`, то есть включить
 * виджет нельзя) и плитку с рабочего стола. Администратору не выводится ничего:
 * его страницы не меняются вообще.
 *
 * Хук `main__js` срабатывает в inc/head.php уже после авторизации, поэтому права
 * пользователя здесь известны. Регистрация хука — вне гварда SMPLUGIN: на обычных
 * страницах константа не определена, а хуки нужны именно там.
 */
$hooks->add_action('main__js', 'js_neurocore');

function js_neurocore(): void {

	require_once __DIR__.'/php/autoload.php';

	if (Neuro\Security::isAdminFlag($GLOBALS['isadmin'] ?? '', $GLOBALS['tipuser'] ?? '')) {
		return;
	}

	print '
	<script>
		var NeuroCoreViget = {hide: true};
	</script>
	<script src="/plugins/neuroCore/assets/js/neurocore.viget.js"></script>
	';

}

if (defined('SMPLUGIN')) {

	$hooks->add_action('plugin_activate', 'activate_neurocore');
	$hooks->add_action('plugin_deactivate', 'deactivate_neurocore');
	$hooks->add_action('plugin_update', 'update_neurocore');

	/**
	 * Активация плагина: создаём недостающие таблицы ядра.
	 *
	 * @param array $argv - ["name" => имя плагина]
	 */
	function activate_neurocore(array $argv = []): void {

		if (($argv['name'] ?? '') !== 'neuroCore') {
			return;
		}

		require_once __DIR__.'/php/autoload.php';

		try {

			$result  = Neuro\Schema::install($GLOBALS['db'], (string)$GLOBALS['database'], (string)$GLOBALS['sqlname']);
			$migrate = Neuro\Schema::migrate($GLOBALS['db'], (string)$GLOBALS['sqlname']);

		}
		catch (Throwable $e) {

			// ни одна ошибка установки не должна вылетать наружу: запись в
			// app_plugins уже сделана, и падение хука оставило бы плагин
			// включённым без таблиц
			$result  = [['table' => '-', 'status' => 'error', 'message' => $e->getMessage()]];
			$migrate = [];

		}

		neurocore_install_result($result, $migrate);

		neurocore_register_cron();
		neurocore_register_viget();

	}

	/**
	 * Деактивация плагина: данные сохраняем (таблицы не удаляем).
	 *
	 * Задание планировщика выключаем: скрипт воркера остаётся на диске, и без этого
	 * он продолжал бы разбирать очередь выключенного плагина. Само задание не
	 * удаляем — при повторном включении оно снова станет активным. Виджет рабочего
	 * стола тоже остаётся: он показывает «плагин выключен» и сам это объясняет.
	 *
	 * @param array $argv
	 */
	function deactivate_neurocore(array $argv = []): void {

		if (($argv['name'] ?? '') !== 'neuroCore') {
			return;
		}

		neurocore_cron_task('disable');

	}

	/**
	 * Обновление плагина: доводим схему до текущей версии.
	 *
	 * @param array $argv
	 */
	function update_neurocore(array $argv = []): void {

		if (($argv['name'] ?? '') !== 'neuroCore') {
			return;
		}

		require_once __DIR__.'/php/autoload.php';

		try {

			$result  = Neuro\Schema::install($GLOBALS['db'], (string)$GLOBALS['database'], (string)$GLOBALS['sqlname']);
			$migrate = Neuro\Schema::migrate($GLOBALS['db'], (string)$GLOBALS['sqlname']);

		}
		catch (Throwable $e) {

			$result  = [['table' => '-', 'status' => 'error', 'message' => $e->getMessage()]];
			$migrate = [];

		}

		neurocore_install_result($result, $migrate);

		neurocore_register_cron();
		neurocore_register_viget();

	}

	/**
	 * Регистрирует виджет рабочего стола в карте виджетов CRM.
	 *
	 * Ошибка регистрации не должна ломать активацию: ядро работает и без виджета,
	 * а причина остаётся в настройках плагина, чтобы её было видно на его странице.
	 */
	function neurocore_register_viget(): void {

		require_once __DIR__.'/php/autoload.php';

		$rootpath = dirname(__DIR__, 2);

		try {

			$status = Neuro\Viget::register($rootpath);

		}
		catch (Throwable $e) {

			$status = 'error: '.$e->getMessage();

		}

		$settings = (array)customSettings('neuroCore');

		$settings['VIGET_STATUS'] = $status;
		$settings['VIGET_ERROR']  = (strpos($status, 'error') === 0) ? $status : '';

		customSettings('neuroCore', 'put', ['params' => $settings]);

	}

	/**
	 * Ставит в Планировщик задание на разбор очереди.
	 *
	 * Планировщик — единственный владелец расписания и пути до интерпретатора:
	 * своего расписания ядро не заводит, а путь берёт из настроек cronManager
	 * (см. Neuro\Cron::effectivePhpBinary).
	 *
	 * Задание идемпотентно по uid: повторный вызов при обновлении плагина не
	 * создаёт второе, а только освежает путь до PHP.
	 *
	 * Ошибка планировщика не должна ломать активацию плагина: ядро умеет работать
	 * и без очереди (вызовы из интерфейса идут напрямую), поэтому ошибка только
	 * запоминается для страницы настроек.
	 */
	function neurocore_register_cron(): void {

		neurocore_cron_task('enable');

	}

	/**
	 * Включение/выключение задания планировщика.
	 *
	 * @param string $mode - enable|disable
	 *
	 * @return bool
	 */
	function neurocore_cron_task(string $mode): bool {

		$rootpath = dirname(__DIR__, 2);

		require_once __DIR__.'/php/autoload.php';

		if (!Neuro\Cron::managerInstalled($rootpath)) {
			return false;
		}

		$settings = (array)customSettings('neuroCore');

		try {

			require_once $rootpath.'/plugins/cronManager/php/autoload.php';
			require_once $rootpath.'/plugins/cronManager/vendor/autoload.php';

			$cron = new Cronman\Cronman();

			$uid = 'neurocore.worker';

			if ($mode === 'disable') {

				$id = (int)($settings['CRON_TASK_ID'] ?? 0);

				if ($id <= 0) {
					$id = (int)$cron->taskExist($rootpath.'/plugins/neuroCore/cron/worker.php');
				}

				if ($id > 0) {
					$cron->disableTask($id);
				}

				return true;

			}

			$id = (int)$cron->setTask(0, [
				'id'     => 0,
				'uid'    => $uid,
				'datum'  => date('Y-m-d H:i:s'),
				'name'   => 'Нейроядро: очередь заданий',
				'parent' => 'everyminutes',
				'task'   => NULL,
				'bin'    => Neuro\Cron::effectivePhpBinary((array)customSettings('cronManager'), PHP_OS_FAMILY, PHP_BINARY),
				'script' => $rootpath.'/plugins/neuroCore/cron/worker.php',
				'period' => ['i' => '1', 'h' => '', 'd' => '', 'm' => '', 'cmd' => ''],
				'active' => 'on',
			]);

			$settings['CRON_TASK_ID'] = $id;
			$settings['CRON_ERROR']   = '';

		}
		catch (Throwable $e) {

			// cronManager написан под PHP 8 (использует str_contains), а ядро
			// поддерживает 7.2 — несовместимость не должна ронять активацию
			$settings['CRON_ERROR'] = $e->getMessage();

		}

		customSettings('neuroCore', 'put', ['params' => $settings]);

		return empty($settings['CRON_ERROR']);

	}

	/**
	 * Запоминает результат установки и достройки схемы.
	 *
	 * Ошибка пишется и в файл, и в настройки плагина: общий cash/actions.log
	 * перезаписывают хуки других плагинов (они срабатывают после нашего и дампят
	 * туда свои аргументы), поэтому единственное надёжное место, где ошибку
	 * увидит администратор, — страница самого плагина.
	 *
	 * @param array $result  - отчёт Schema::install()
	 * @param array $migrate - отчёт Schema::migrate()
	 */
	function neurocore_install_result(array $result, array $migrate = []): void {

		$error = [];

		foreach ($result as $row) {
			if ($row['status'] === 'error') {
				$error[] = $row['table'].': '.$row['message'];
			}
		}

		foreach ($migrate as $target => $status) {

			if (strpos((string)$status, 'error') === 0) {
				$error[] = $target.': '.trim(substr((string)$status, 6), ': ');
			}

		}

		$settings = (array)customSettings('neuroCore');

		$settings['INSTALL_ERROR']      = $error;
		$settings['INSTALL_ERROR_DATE'] = !empty($error) ? date('d.m.Y H:i') : '';

		customSettings('neuroCore', 'put', ['params' => $settings]);

		if (!empty($error)) {
			file_put_contents($GLOBALS['rootpath'].'/cash/actions.log', json_encode(['neuroCore' => $error], JSON_UNESCAPED_UNICODE));
		}

	}

}
