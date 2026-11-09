<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Единая проверка системных требований SalesMan CRM.
 *
 * Источник истины — README.md, раздел «Системные требования», и composer.json.
 * Раньше установщик (_install/install.php) и страница «Система»
 * (content/admin/sysinfo.php) несли каждый свой список, и они разъехались:
 * на странице не было zip/simplexml/iconv/fileinfo/mysqli/ftp/sockets/zlib,
 * у установщика — проверок php.ini и CLI. Теперь список один.
 *
 * Чек-лист возвращается данными (checks + failures/warnings), а рендеринг
 * и итоговый вердикт — отдельными функциями, чтобы обе точки входа выводили
 * одно и то же.
 */

$smRequirementsVersionFile = __DIR__.'/phpversion.php';

if (file_exists($smRequirementsVersionFile)) {
	require_once $smRequirementsVersionFile;
}

if (!function_exists('sm_ini_bytes')) {

	/**
	 * Переводит значение директивы php.ini (64M, 1G, 1024K) в байты.
	 * Отрицательное значение (-1 = без лимита) возвращается как -1.
	 *
	 * @param mixed $value
	 *
	 * @return int
	 */
	function sm_ini_bytes($value): int {

		$value = trim((string)$value);

		if ($value === '') {
			return 0;
		}

		$number = (int)$value;

		if ($number < 0) {
			return -1;
		}

		switch (strtolower(substr($value, -1))) {
			case 'g':
				$number *= 1024;
			// no break
			case 'm':
				$number *= 1024;
			// no break
			case 'k':
				$number *= 1024;
		}

		return (int)$number;

	}

}

if (!function_exists('sm_shell_exec')) {

	/**
	 * Обёртка над exec(): не падает, если функция отключена через disable_functions.
	 * PHP 8 удаляет отключённые функции из таблицы, и прямой вызов даёт фатал.
	 *
	 * @param string $command  Команда.
	 * @param array  $output   Вывод команды (по ссылке).
	 * @param int    $exitCode Код возврата (по ссылке).
	 *
	 * @return bool true, если exec() доступна и команда запущена.
	 */
	function sm_shell_exec(string $command, &$output = null, &$exitCode = null): bool {

		$output   = [];
		$exitCode = 1;

		if (!function_exists('exec')) {
			return false;
		}

		exec($command, $output, $exitCode);

		return true;

	}

}

if (!function_exists('sm_requirements_extensions')) {

	/**
	 * Обязательные и желательные расширения PHP по README.md / composer.json.
	 *
	 * @return array<int, array{name:string,label:string,required:bool,why:string}>
	 */
	function sm_requirements_extensions(): array {

		return [
			[ 'name' => 'curl', 'label' => 'cURL', 'required' => true, 'why' => 'HTTP-интеграции и REST API' ],
			[ 'name' => 'mbstring', 'label' => 'MBSTRING', 'required' => true, 'why' => 'UTF-8 и строковые функции' ],
			[ 'name' => 'zip', 'label' => 'ZIP', 'required' => true, 'why' => 'архивы, DOCX/XLSX, резервные копии' ],
			[ 'name' => 'zlib', 'label' => 'ZLIB', 'required' => true, 'why' => 'сжатие в PDF и архивах' ],
			[ 'name' => 'dom', 'label' => 'DOM', 'required' => true, 'why' => 'генератор PDF, разбор HTML/XML' ],
			[ 'name' => 'xml', 'label' => 'XML', 'required' => true, 'why' => 'импорт и обмен XML' ],
			[ 'name' => 'simplexml', 'label' => 'SimpleXML', 'required' => true, 'why' => 'импорт XLSX/XLS' ],
			[ 'name' => 'libxml', 'label' => 'libxml', 'required' => true, 'why' => 'основа DOM/XML' ],
			[ 'name' => 'iconv', 'label' => 'iconv', 'required' => true, 'why' => 'кодировки в почте и импорте' ],
			[ 'name' => 'fileinfo', 'label' => 'fileinfo', 'required' => true, 'why' => 'MIME-типы вложений' ],
			[ 'name' => 'openssl', 'label' => 'OPENSSL', 'required' => true, 'why' => 'SMTP TLS и шифрование' ],
			[ 'name' => 'gd', 'label' => 'GD', 'required' => true, 'why' => 'PDF-счёт и QR-коды' ],
			[ 'name' => 'mysqli', 'label' => 'mysqli', 'required' => true, 'why' => 'подключение к БД' ],
			[ 'name' => 'ftp', 'label' => 'FTP', 'required' => true, 'why' => 'FTP-хранилище файлов' ],
			[ 'name' => 'sockets', 'label' => 'sockets', 'required' => true, 'why' => 'сокеты (интеграции, печать)' ],
			[ 'name' => 'imap', 'label' => 'IMAP', 'required' => false, 'why' => 'почтовый клиент и приём писем; с PHP 8.4 расширение ставится отдельно (PECL imap или пакет дистрибутива), composer.json требует ext-imap' ],
			[ 'name' => 'xmlreader', 'label' => 'XMLReader', 'required' => false, 'why' => 'потоковый разбор XML' ],
			[ 'name' => 'intl', 'label' => 'intl', 'required' => false, 'why' => 'желательное расширение' ],
			[ 'name' => 'opcache', 'label' => 'OPcache', 'required' => false, 'why' => 'ускорение PHP; в PHP 8.5 встроен', 'special' => 'opcache' ],
		];

	}

}

if (!function_exists('sm_requirements_check')) {

	/**
	 * Собирает чек-лист системных требований.
	 *
	 * @param string     $rootpath Корень установки CRM.
	 * @param object|null $db      Подключение SafeMySQL (нужно для проверки версии БД).
	 * @param string     $mode     'runtime' — страница «Система», 'install' — установщик.
	 *
	 * @return array
	 */
	function sm_requirements_check(string $rootpath, $db = null, string $mode = 'runtime'): array {

		$install = ($mode === 'install');
		$base    = rtrim($rootpath, '/\\');

		$result = [
			'mode'     => $mode,
			'checks'   => [],
			'failures' => 0,
			'warnings' => 0,
			'cli'      => [
				'available' => false,
				'version'   => '',
				'output'    => [],
				'binary'    => PHP_BINARY,
			],
		];

		$add = function (string $group, string $key, string $status, string $label, string $message) use (&$result): void {
			$result['checks'][] = [
				'group'   => $group,
				'key'     => $key,
				'status'  => $status,
				'label'   => $label,
				'message' => $message,
			];
		};

		// --- Версии ПО ----------------------------------------------------------
		$phpSupported = function_exists('sm_php_version_supported') ? sm_php_version_supported() : version_compare(PHP_VERSION, '8.3', '>=');

		if ($phpSupported) {
			$add('versions', 'php_version', 'ok', 'PHP', 'Требуется <b>PHP</b> не ниже <b>8.3</b>. Текущая версия <b>'.PHP_VERSION.'</b>.');
		}
		else {
			$add('versions', 'php_version', 'fail', 'PHP', 'Версия <b>PHP</b> ниже <b>8.3</b> <b>не поддерживается</b>. Установлена версия <b>'.PHP_VERSION.'</b>.');
		}

		if ($phpSupported) {

			$phpSecure    = function_exists('sm_php_version_secure') ? sm_php_version_secure() : true;
			$secureBuilds = defined('SM_PHP_SECURE_BUILDS') ? SM_PHP_SECURE_BUILDS : [];

			if ($phpSecure) {
				$add('versions', 'php_secure', 'ok', 'PHP (CVE)', 'Сборка <b>PHP '.PHP_VERSION.'</b> содержит исправления <b>CVE-2026-109618/619</b>.');
			}
			else {
				$fixed = [];

				foreach ($secureBuilds as $build) {
					$fixed[] = '<b>'.$build.'</b>';
				}

				$add(
					'versions',
					'php_secure',
					'warn',
					'PHP (CVE)',
					'Сборка <b>PHP '.PHP_VERSION.'</b> уязвима к <b>CVE-2026-109618/619 (PHP2Shell)</b>. Используйте исправленную сборку своей ветки'.( $fixed ? ': '.implode( ', ', $fixed ) : '' ).'.'
				);
			}

		}

		if (is_object($db) && method_exists($db, 'getOne')) {

			$dbVersion = '';

			try {
				$dbVersion = (string)$db -> getOne('SELECT VERSION()');
			}
			catch (Throwable $e) {
				$dbVersion = '';
			}

			if ($dbVersion === '') {
				$add('versions', 'mysql', 'warn', 'MySQL', 'Не удалось определить версию сервера БД. Требуется <b>MySQL 8.x</b> / <b>MariaDB 10.x</b>.');
			}
			else {

				$isMaria = stripos($dbVersion, 'mariadb') !== false;
				$clean   = $isMaria ? (string)preg_replace('/^5\.5\.5-/', '', $dbVersion) : $dbVersion;
				$number  = preg_match('/^(\d+\.\d+(?:\.\d+)?)/', $clean, $match) ? $match[1] : '';
				$minimum = $isMaria ? '10.0' : '8.0';

				if ($number === '') {
					$add('versions', 'mysql', 'warn', 'MySQL', 'Версию сервера БД не удалось разобрать: <b>'.$dbVersion.'</b>. Требуется <b>MySQL 8.x</b> / <b>MariaDB 10.x</b>.');
				}
				elseif (version_compare($number, $minimum, '<')) {
					$add('versions', 'mysql', 'fail', 'MySQL', 'Версия <b>'.$dbVersion.'</b> ниже требуемой <b>'.( $isMaria ? 'MariaDB 10.x' : 'MySQL 8.x' ).'</b>.');
				}
				else {
					$add('versions', 'mysql', 'ok', 'MySQL', 'Версия <b>MySQL/MariaDB в порядке</b>. Сервер: <b>'.$dbVersion.'</b>.');
				}

			}

		}

		// --- Расширения PHP -----------------------------------------------------
		foreach (sm_requirements_extensions() as $extension) {

			$special = $extension['special'] ?? '';
			$loaded  = ($special === 'opcache') ? function_exists('opcache_get_status') : extension_loaded($extension['name']);

			if ($loaded) {
				$add('extensions', 'ext_'.$extension['name'], 'ok', $extension['label'], 'Модуль <u><b>'.$extension['label'].'</b></u> <b>подключён</b>.');
				continue;
			}

			if ($extension['required']) {
				$add('extensions', 'ext_'.$extension['name'], 'fail', $extension['label'], 'Модуль <u><b>'.$extension['label'].'</b></u> <b>не подключён</b>. '.$extension['why'].'.');
			}
			else {
				$add('extensions', 'ext_'.$extension['name'], 'warn', $extension['label'], 'Модуль <u><b>'.$extension['label'].'</b></u> <b>не подключён</b> (не критично). '.$extension['why'].'.');
			}

		}

		if (extension_loaded('domxml')) {
			$add('extensions', 'ext_domxml', 'warn', 'DOMXML', 'Модуль <u><b>DOMXML</b></u> <b>подключён</b> — он мешает dompdf. Отключите его.');
		}
		else {
			$add('extensions', 'ext_domxml', 'ok', 'DOMXML', 'Модуль <u><b>DOMXML</b></u> <b>не подключён</b> — правильно.');
		}

		// --- Параметры php.ini --------------------------------------------------
		$memoryRaw = (string)ini_get('memory_limit');
		$memory    = sm_ini_bytes($memoryRaw);

		if ($memory === -1 || $memory >= 1024 * 1024 * 1024) {
			$add('php_ini', 'memory_limit', 'ok', 'memory_limit', 'Параметр <u><b>memory_limit</b></u> = <b>'.$memoryRaw.'</b> — не менее 1024M.');
		}
		else {
			$add('php_ini', 'memory_limit', 'warn', 'memory_limit', 'Параметр <u><b>memory_limit</b></u> = <b>'.$memoryRaw.'</b>; рекомендуется не менее <b>1024M</b> (PDF, XLSX, большие выгрузки).');
		}

		$postRaw = (string)ini_get('post_max_size');
		$post    = sm_ini_bytes($postRaw);

		if ($post === -1 || $post === 0 || $post >= 64 * 1024 * 1024) {
			$add('php_ini', 'post_max_size', 'ok', 'post_max_size', 'Параметр <u><b>post_max_size</b></u> = <b>'.$postRaw.'</b> — не менее 64M.');
		}
		else {
			$add('php_ini', 'post_max_size', 'warn', 'post_max_size', 'Параметр <u><b>post_max_size</b></u> = <b>'.$postRaw.'</b>; рекомендуется <b>64M</b> (загрузка файлов, резервное копирование).');
		}

		$uploadRaw = (string)ini_get('upload_max_filesize');
		$upload    = sm_ini_bytes($uploadRaw);

		if ($upload === -1 || $upload >= 64 * 1024 * 1024) {
			$add('php_ini', 'upload_max_filesize', 'ok', 'upload_max_filesize', 'Параметр <u><b>upload_max_filesize</b></u> = <b>'.$uploadRaw.'</b> — не менее 64M.');
		}
		else {
			$add('php_ini', 'upload_max_filesize', 'warn', 'upload_max_filesize', 'Параметр <u><b>upload_max_filesize</b></u> = <b>'.$uploadRaw.'</b>; рекомендуется <b>64M</b> (загрузка файлов).');
		}

		$execTime = (int)ini_get('max_execution_time');

		if ($execTime === 0 || $execTime >= 300) {
			$add('php_ini', 'max_execution_time', 'ok', 'max_execution_time', 'Параметр <u><b>max_execution_time</b></u> = <b>'.$execTime.'</b> сек. — не менее 300 (для тяжёлых выгрузок рекомендуется 900).');
		}
		else {
			$add('php_ini', 'max_execution_time', 'warn', 'max_execution_time', 'Параметр <u><b>max_execution_time</b></u> = <b>'.$execTime.'</b> сек.; рекомендуется не менее <b>300</b>.');
		}

		$displayErrors = strtolower(trim((string)ini_get('display_errors')));

		if (!$install) {

			if (in_array($displayErrors, [ '', '0', 'off', 'stderr' ], true)) {
				$add('php_ini', 'display_errors', 'ok', 'display_errors', 'Параметр <u><b>display_errors</b></u> = <b>'.( $displayErrors === '' ? 'Off' : $displayErrors ).'</b>.');
			}
			else {
				$add('php_ini', 'display_errors', 'warn', 'display_errors', 'Параметр <u><b>display_errors</b></u> включён — на проде ошибки должны писаться в лог, а не в вывод (<b>Off</b> + <b>log_errors=On</b>).');
			}

			if ((bool)ini_get('log_errors')) {
				$add('php_ini', 'log_errors', 'ok', 'log_errors', 'Параметр <u><b>log_errors</b></u> включён.');
			}
			else {
				$add('php_ini', 'log_errors', 'warn', 'log_errors', 'Параметр <u><b>log_errors</b></u> выключен — ошибки не попадут в лог.');
			}

		}

		$timezone = (string)ini_get('date.timezone');

		if ($timezone === '') {
			$add('php_ini', 'timezone', 'fail', 'date.timezone', 'Не задан параметр <u><b>date.timezone</b></u> (см. php.ini). Пропишите, например, <b>date.timezone = Europe/Moscow</b>. Критично.');
		}
		else {
			$add('php_ini', 'timezone', 'ok', 'date.timezone', 'Параметр <u><b>date.timezone</b></u> задан: <b>'.$timezone.'</b>.');
		}

		// --- Права на папки -----------------------------------------------------
		$directories = $install
			? [
				[ 'files', true ],
				[ 'cash', true ],
				[ 'cash/templates', true ],
				[ 'cash/logo', true ],
				[ 'inc', true ],
			]
			: [
				[ 'files', true ],
				[ 'files/backup', false ],
				[ 'cash', true ],
				[ 'cash/templates', true ],
				[ 'cash/logo', true ],
				[ 'cash/dompdf', false ],
			];

		foreach ($directories as $directory) {

			$relative = $directory[0];
			$critical = $directory[1];
			$path     = $base.'/'.$relative;
			$key      = 'dir_'.str_replace('/', '_', $relative);

			if (!file_exists($path)) {
				$status  = $critical ? 'fail' : 'warn';
				$message = 'Папка <b>/'.$relative.'</b> не найдена'.( $critical ? ' — создайте её и дайте права на запись (0777).' : ' (создаётся при первом обращении).' );
			}
			elseif (!is_dir($path)) {
				$status  = $critical ? 'fail' : 'warn';
				$message = 'Путь <b>/'.$relative.'</b> существует, но не является папкой — удалите файл/симлинк и создайте каталог с правами <b>0777</b>.';
			}
			elseif (!is_writable($path)) {
				$status  = $critical ? 'fail' : 'warn';
				$message = 'Папка <b>/'.$relative.'</b> недоступна для записи — нужны права <b>0777</b>.';
			}
			else {
				$status  = 'ok';
				$message = 'Папка <b>/'.$relative.'</b> доступна для записи.';
			}

			$add('folders', $key, $status, '/'.$relative, $message);

		}

		// --- Безопасность -------------------------------------------------------
		if (!$install) {

			$incPath = $base.'/inc';

			if (DIRECTORY_SEPARATOR === '\\') {
				$add('security', 'inc_write', 'info', '/inc', 'Проверка прав <b>/inc</b> по chmod на Windows не применима: убедитесь, что веб-пользователь не может писать в <b>/inc</b>.');
			}
			elseif (!file_exists($incPath)) {
				$add('security', 'inc_write', 'warn', '/inc', 'Папка <b>/inc</b> не найдена.');
			}
			elseif (is_writable($incPath)) {
				$add('security', 'inc_write', 'warn', '/inc', 'Папка <b>/inc</b> доступна для записи — снимите права на запись (в ней лежит конфигурация).');
			}
			else {
				$add('security', 'inc_write', 'ok', '/inc', 'Папка <b>/inc</b> защищена от записи — правильно.');
			}

		}

		// --- PHP CLI и окружение ------------------------------------------------
		if (!$install) {

			$execAvailable = function_exists('exec');

			if ($execAvailable) {
				$add('runtime', 'exec', 'ok', 'exec()', 'Функция <u><b>exec()</b></u> доступна (нужна для проверки CLI и конвертера Office→PDF).');
			}
			else {
				$add('runtime', 'exec', 'warn', 'exec()', 'Функция <u><b>exec()</b></u> отключена (<b>disable_functions</b>): не работают проверка PHP CLI и конвертер Office→PDF.');
			}

			if ($execAvailable) {

				$cliOutput = [];
				$cliExit   = 1;

				@exec('php -v 2>&1', $cliOutput, $cliExit);

				$cliVersion = '';

				if ($cliExit === 0 && !empty($cliOutput[0]) && preg_match('/PHP\s+(\d+\.\d+\.\d+)/', $cliOutput[0], $match)) {
					$cliVersion = $match[1];
				}

				$result['cli'] = [
					'available' => true,
					'version'   => $cliVersion,
					'output'    => $cliOutput,
					'binary'    => PHP_BINARY,
				];

				if ($cliVersion === '') {
					$add('runtime', 'cli_php', 'warn', 'PHP CLI', 'Интерпретатор <b>php</b> не найден в PATH или не отвечает: cron и консольные скрипты работать не будут. Проверьте PATH и симлинк <b>php</b>.');
				}
				elseif (version_compare($cliVersion, PHP_VERSION, '<')) {
					$add('runtime', 'cli_php', 'warn', 'PHP CLI', 'Версия <b>PHP CLI '.$cliVersion.'</b> ниже веб-версии <b>'.PHP_VERSION.'</b> — cron будет работать на другой ветке PHP. Выровняйте версии.');
				}
				elseif (version_compare($cliVersion, PHP_VERSION, '>')) {
					$add('runtime', 'cli_php', 'warn', 'PHP CLI', 'Версия <b>PHP CLI '.$cliVersion.'</b> выше веб-версии <b>'.PHP_VERSION.'</b> — cron будет работать на другой ветке PHP. Выровняйте версии.');
				}
				else {
					$add('runtime', 'cli_php', 'ok', 'PHP CLI', 'Версия <b>PHP CLI '.$cliVersion.'</b> совпадает с веб-версией.');
				}

			}
			else {
				$add('runtime', 'cli_php', 'warn', 'PHP CLI', 'Проверка PHP CLI недоступна: <b>exec()</b> отключён.');
			}

		}

		// --- Счётчики -----------------------------------------------------------
		foreach ($result['checks'] as $check) {
			if ($check['status'] === 'fail') {
				$result['failures']++;
			}
			elseif ($check['status'] === 'warn') {
				$result['warnings']++;
			}
		}

		return $result;

	}

}

if (!function_exists('sm_requirements_render')) {

	/**
	 * HTML-строки чек-листа (иконка + сообщение) для страницы «Система»/установщика.
	 *
	 * @param array    $result         Результат sm_requirements_check().
	 * @param string[] $groups         Группы для вывода (пусто — все).
	 * @param bool     $showGroupTitles Печатать заголовки групп.
	 *
	 * @return string
	 */
	function sm_requirements_render(array $result, array $groups = [], bool $showGroupTitles = false): string {

		$titles = [
			'versions'   => 'Версии ПО и модулей',
			'extensions' => 'Расширения PHP',
			'php_ini'    => 'Параметры php.ini',
			'folders'    => 'Права на папки',
			'runtime'    => 'PHP CLI и окружение',
			'security'   => 'Безопасность',
		];

		$icons = [
			'ok'   => [ 'icon-ok-circled', 'green' ],
			'warn' => [ 'icon-attention', 'broun' ],
			'fail' => [ 'icon-attention', 'red' ],
			'info' => [ 'icon-info-circled', 'blue' ],
		];

		$html    = '';
		$current = null;

		foreach ($result['checks'] as $check) {

			if ($groups && !in_array($check['group'], $groups, true)) {
				continue;
			}

			if ($showGroupTitles && $check['group'] !== $current) {

				$current = $check['group'];

				$html .= '<div class="blue mt10 mb5"><b>'.( $titles[ $current ] ?? $current ).':</b></div>';

			}

			$icon = $icons[ $check['status'] ] ?? $icons['info'];

			$html .= '<i class="'.$icon[0].' '.$icon[1].'"></i>&nbsp;'.$check['message'].'<br>';

		}

		return $html;

	}

}

if (!function_exists('sm_requirements_verdict')) {

	/**
	 * Итоговый вердикт по чек-листу (HTML-строка).
	 *
	 * @param array $result Результат sm_requirements_check().
	 *
	 * @return string
	 */
	function sm_requirements_verdict(array $result): string {

		$failures = (int)$result['failures'];
		$warnings = (int)$result['warnings'];

		if ($failures > 0) {
			return '<div class="warning inline mt5"><i class="icon-attention red"></i>&nbsp;<b>Обнаружены критичные проблемы: '.$failures.'</b>. Работа системы может быть нарушена.</div>';
		}

		if ($warnings > 0) {
			return '<div class="attention inline mt5"><i class="icon-attention broun"></i>&nbsp;Критичных проблем нет, замечаний: <b>'.$warnings.'</b>.</div>';
		}

		return '<div class="success inline mt5"><i class="icon-ok-circled green"></i>&nbsp;<b>Система соответствует системным требованиям.</b></div>';

	}

}
