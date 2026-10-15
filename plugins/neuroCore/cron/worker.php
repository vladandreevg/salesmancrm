<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

use Neuro\Alerts;
use Neuro\Cache;
use Neuro\Log;
use Neuro\Neuro;
use Neuro\Notify;
use Neuro\Queue;

/**
 * Воркер очереди Нейроядра.
 *
 * Запускается планировщиком (плагин cronManager) каждую минуту. Разбирает
 * несколько заданий из app_neuro_task и завершается — это не демон: так задание
 * переживает перезапуск сервера, а сбой одного задания не блокирует остальные.
 *
 * Ручной запуск для проверки:
 *
 *     php plugins/neuroCore/cron/worker.php --limit=1 --verbose
 *
 * Задание, взятое в работу, помечается в базе (status=run, locked_by). Если
 * воркер убит на середине, через 15 минут задание вернётся в очередь
 * (Neuro\Queue::reclaim) — то есть задание не теряется.
 *
 * Внимание: это фоновая программа. Сессии, прав пользователя и `$iduser1` у неё
 * нет, поэтому файлы авторизации здесь не подключаются; действия выполняются от
 * имени того пользователя, который поставил задание (task.iduser).
 */

// только командная строка: у скрипта нет ни сессии, ни проверки прав, и он
// расходует деньги владельца CRM — по HTTP он вызываться не должен
if (PHP_SAPI !== 'cli') {

	header('HTTP/1.1 403 Forbidden');

	print 'Нейроядро: воркер очереди запускается только из командной строки';

	exit(1);

}

$rootpath = dirname(__DIR__, 3);

set_time_limit(0);
error_reporting(E_ERROR);
ini_set('display_errors', '0');

// dbconnector.php читает cookie сессии — в консоли её нет
if (!isset($_COOKIE['ses'])) {
	$_COOKIE['ses'] = '';
}

require_once $rootpath.'/inc/config.php';
require_once $rootpath.'/inc/dbconnector.php';
require_once $rootpath.'/inc/settings.php';
require_once $rootpath.'/inc/func.php';
require_once $rootpath.'/plugins/neuroCore/php/autoload.php';

/**
 * Разбор аргументов командной строки (чистая функция).
 *
 * @param array $argv
 *
 * @return array - limit, verbose
 */
function neurocore_worker_options(array $argv): array {

	$options = ['limit' => 5, 'verbose' => false];

	foreach ($argv as $arg) {

		$arg = (string)$arg;

		if ($arg === '--verbose' || $arg === '-v') {
			$options['verbose'] = true;
		}
		elseif (strpos($arg, '--limit=') === 0) {
			$options['limit'] = (int)substr($arg, 8);
		}

	}

	// слишком большое значение означало бы минуты работы в одном процессе cron
	$options['limit'] = max(1, min(50, $options['limit']));

	return $options;

}

/**
 * Вывод строки (в cron-режиме молчим, если не просили подробностей).
 *
 * @param string $line
 * @param bool   $always
 *
 * @return void
 */
function neurocore_worker_out(string $line, bool $always = false): void {

	global $neurocoreWorkerVerbose;

	if ($always || $neurocoreWorkerVerbose) {
		print $line."\n";
	}

}

$options = neurocore_worker_options(isset($argv) ? (array)$argv : []);

$neurocoreWorkerVerbose = $options['verbose'];

if (in_array('--help', isset($argv) ? (array)$argv : [], true) || in_array('-h', isset($argv) ? (array)$argv : [], true)) {

	print "Нейроядро: воркер очереди заданий\n\n";
	print "  php worker.php [--limit=N] [--verbose]\n\n";
	print "  --limit=N   сколько заданий обработать за запуск (1..50, по умолчанию 5)\n";
	print "  --verbose   подробный вывод\n";

	exit(0);

}

$identity = (int)($GLOBALS['identity'] ?? 1);
$sqlname  = (string)($GLOBALS['sqlname'] ?? '');
$worker   = (string)gethostname().':'.getmypid();

// настройки ядра: таймаут запроса и хранение переменных (одно место с интерфейсом)
$defaults = Queue::defaults((array)customSettings('neuroCore'));

// Выключенный плагин не должен расходовать деньги владельца CRM: задание в
// планировщике остаётся (его снимает деактивация), но воркер молча выходит.
try {

	$active = (string)$db->getOne("SELECT active FROM {$sqlname}plugins WHERE name = 'neuroCore' AND identity = ?i LIMIT 1", $identity);

}
catch (Throwable $e) {

	$active = '';

}

if ($active !== 'on') {

	neurocore_worker_out('Нейроядро: плагин выключен, работа не выполняется');

	exit(0);

}

$done      = 0;
$failed    = 0;
$retried   = 0;
$skipped   = 0;
$reclaimed = 0;

try {

	$reclaimed = Queue::reclaim($db, $sqlname, $identity, Queue::STALE_MINUTES);

	if ($reclaimed > 0) {

		Queue::log($db, $sqlname, 0, 'warn', 'Возвращено в очередь зависших заданий: '.$reclaimed, [], $identity);
		neurocore_worker_out('Возвращено в очередь зависших заданий: '.$reclaimed, true);

	}

	// Ретенция журнала: уборка идёт порциями на каждом прогоне, поэтому даже
	// большой журнал не растягивает минуту воркера. 0 дней — хранить бессрочно.
	$retention = Log::retention((array)customSettings('neuroCore'));

	if ($retention > 0) {

		$purged = Log::purge($db, $sqlname, $identity, $retention);

		if ($purged > 0) {
			neurocore_worker_out('Удалено старых записей журнала (старше '.$retention.' дн.): '.$purged, true);
		}

	}

	// Просроченные ответы кэша: их уже не отдаёт `get()`, но строки с текстом
	// ответа остаются в таблице. Порциями — по той же причине, что и у журнала.
	$purgedCache = Cache::purge($db, $sqlname, $identity);

	if ($purgedCache > 0) {
		neurocore_worker_out('Удалено просроченных ответов кэша: '.$purgedCache, true);
	}

	// Выполненные задания старше срока: в них остаются переменные с данными CRM и
	// ответ модели. Незавершённые не трогаются никогда — их заберёт воркер или
	// вернёт в очередь reclaim.
	$taskRetention = Queue::retention((array)customSettings('neuroCore'));

	if ($taskRetention > 0) {

		$purgedTasks = Queue::purge($db, $sqlname, $identity, $taskRetention);

		if ($purgedTasks > 0) {
			neurocore_worker_out('Удалено выполненных заданий старше '.$taskRetention.' дн.: '.$purgedTasks, true);
		}

	}

	// Очередь выше порога: значит воркер её не разбирает (не запущен планировщик),
	// и она будет только расти — об этом стоит сказать в «колокольчик»
	Alerts::queueTooLong($db, $sqlname, $identity);

	$tasks = Queue::candidates($db, $sqlname, $identity, (int)$options['limit']);

	neurocore_worker_out('Заданий в очереди: '.count($tasks));

	foreach ($tasks as $task) {

		$id = (int)$task['id'];

		// задание мог забрать другой воркер между выборкой и захватом
		if (!Queue::claim($db, $sqlname, $id, $worker, $identity)) {

			$skipped++;

			continue;

		}

		$payload = json_decode((string)$task['payload'], true);
		$payload = is_array($payload) ? $payload : [];

		neurocore_worker_out('Задание #'.$id.' ('.$task['code'].') — выполняю');

		try {

			$answer = neurocore_worker_run($db, $sqlname, $task, $payload, $identity, $rootpath, (int)$defaults['timeout']);

		}
		catch (Throwable $e) {

			$answer = Neuro::failure('worker', 'Сбой воркера: '.$e->getMessage());

		}

		$error = $answer['error'];

		if ($error === NULL && trim((string)$answer['text']) !== '') {

			Queue::complete($db, $sqlname, $id, $answer, $identity);

			// данные CRM из переменных больше не нужны: ответ уже записан
			if (empty($defaults['store_payload'])) {
				Queue::clearPayload($db, $sqlname, $id, $identity);
			}

			Queue::log($db, $sqlname, $id, 'info', 'Задание выполнено', [
				'provider' => (string)($answer['provider'] ?? ''),
				'model'    => (string)($answer['model'] ?? ''),
				'tokens'   => (int)($answer['usage']['in'] ?? 0).'/'.(int)($answer['usage']['out'] ?? 0),
				'latency'  => (int)($answer['latency_ms'] ?? 0),
				'retried'  => (bool)($answer['retried'] ?? false),
			], $identity);

			$done++;

			neurocore_worker_out('Задание #'.$id.' выполнено');

			// по умолчанию результат ждёт в журнале: рассылать напоминание о
			// каждом выполненном задании — значит шуметь
			if (!empty($payload['notify'])) {
				Notify::queueDone((int)$task['iduser'], $task, (string)$answer['text']);
			}

			continue;

		}

		if ($error === NULL) {
			$error = ['code' => 'empty', 'message' => 'Провайдер вернул пустой ответ'];
		}

		$state = Queue::fail($db, $sqlname, $id, $error, $identity);

		Queue::log($db, $sqlname, $id, 'error', 'Задание не выполнено: '.$error['message'], [
			'code'     => (string)$error['code'],
			'attempts' => (int)$task['attempts'] + 1,
			'state'    => $state,
		], $identity);

		if ($state === 'retry') {

			$retried++;

			neurocore_worker_out('Задание #'.$id.' — временная ошибка ('.$error['code'].'), повтор позже');

			continue;

		}

		$failed++;

		neurocore_worker_out('Задание #'.$id.' — окончательная ошибка: '.$error['message']);

		// в базе остались попытки до увеличения — для текста уведомления
		// показываем фактическое число
		$task['attempts'] = (int)$task['attempts'] + 1;

		Notify::queueFailed((int)$task['iduser'], $task, (string)$error['message']);

	}

}
catch (Throwable $e) {

	// падение не должно выглядеть как успешный запуск: планировщик пишет вывод
	// в свой журнал, и по нему администратор увидит причину
	print 'Нейроядро: сбой воркера — '.$e->getMessage()."\n";

	exit(1);

}

$total = $done + $failed + $retried;

if ($total > 0 || $reclaimed > 0) {

	neurocore_worker_out(
		'Нейроядро: выполнено '.$done.', повторим '.$retried.', с ошибкой '.$failed.($skipped > 0 ? ', пропущено '.$skipped : ''),
		true
	);

}

exit(0);

/**
 * Выполнение одного задания: промпт из реестра + подключение из задания.
 *
 * Код промпта берётся из задания, переменные — из payload. Версия промпта
 * намеренно не фиксируется: если администратор поправил промпт, пока задание
 * ждало очереди, применять нужно исправленный текст, а не старый.
 *
 * @param object $db
 * @param string $sqlname
 * @param array  $task
 * @param array  $payload
 * @param int    $identity
 * @param string $rootpath
 * @param int    $timeout  - таймаут из настроек ядра
 *
 * @return array - нормализованный ответ Neuro\Neuro
 */
function neurocore_worker_run($db, string $sqlname, array $task, array $payload, int $identity, string $rootpath, int $timeout = 0): array {

	$req = (array)($payload['req'] ?? []);

	$req['prompt']      = (string)$task['code'];
	$req['tags']        = (array)($payload['tags'] ?? []);
	$req['iduser']      = (int)$task['iduser'];
	$req['provider_id'] = (int)$task['provider_id'];

	// задание могло быть поставлено со своим таймаутом
	if (empty($req['timeout']) && $timeout > 0) {
		$req['timeout'] = $timeout;
	}

	$neuro = new Neuro($db, $sqlname, $identity, $rootpath);

	return $neuro->ask($req);

}
