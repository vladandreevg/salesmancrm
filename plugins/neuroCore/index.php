<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Раздел «Нейро»: подключения к нейросетям и настройки ядра.
 *
 * Открывается из меню плагинов как plugins/neuroCore/ (DirectoryIndex index.php).
 */

$rootpath = dirname(__DIR__, 2);

error_reporting(E_ERROR);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once __DIR__."/php/autoload.php";

use Neuro\Security;

// Доступ только для администратора
if (!Security::isAdmin($db, $sqlname, (int)$iduser1, (int)$identity, $isadmin, $tipuser)) {

	print 'Доступ запрещен';

	exit();

}

$about = json_decode(str_replace(["  ", "\t", "\n", "\r"], "", (string)file_get_contents(__DIR__."/plugin.json")), true);

$token = Security::csrfToken(isset($_COOKIE['ses']) ? (string)$_COOKIE['ses'] : '', (int)$iduser1);

// Настройки отдаются сразу в разметке: страница не должна делать лишний запрос
// только чтобы показать форму, а администратор должен видеть ошибку установки
// именно здесь (общий cash/actions.log перезаписывают хуки других плагинов).
$settings = (array)customSettings('neuroCore');

$installError = isset($settings['INSTALL_ERROR']) ? (array)$settings['INSTALL_ERROR'] : [];

$timeout = !empty($settings['TIMEOUT']) ? (int)$settings['TIMEOUT'] : 30;
$tries   = !empty($settings['MAX_ATTEMPTS']) ? (int)$settings['MAX_ATTEMPTS'] : 3;
$store   = (isset($settings['STORE_PAYLOAD']) && $settings['STORE_PAYLOAD'] === 'on');

// срок жизни кэша: 0 — выключен; значение по умолчанию задаёт сам класс кэша
$cacheTtl = array_key_exists('CACHE_TTL', $settings)
	? (int)$settings['CACHE_TTL']
	: (int)(Neuro\Cache::DEFAULT_TTL);

// срок хранения журнала: 0 — бессрочно; уборку делает воркер при каждом прогоне
$logRetention = Neuro\Log::retention($settings);

// срок хранения выполненных заданий: в них остаются переменные с данными CRM
$taskRetention = Neuro\Queue::retention($settings);

// сколько заданий уже подлежит уборке (0 или пустой список — ничего не показываем)
$taskPurge = $taskRetention > 0
	? Neuro\Queue::purgeStats($db, $sqlname, (int)$identity, $taskRetention)
	: ['count' => 0, 'oldest' => ''];

// оповещения: порог очереди и ограничение повторов
$alertLimit  = Neuro\Alerts::limit($settings);
$alertRepeat = Neuro\Alerts::repeat($settings);

// Виджет: состояние проверяем по самому файлу карты виджетов, а не по сохранённой
// настройке — файл cash/map.vigets.castom.json может быть очищен сбросом кэша или
// откатом файлов рабочей копии, и тогда запись исчезает, хотя плагин считает, что
// всё зарегистрировано. Если записи нет — создаём её сразу: страница доступна
// только администратору, а запись идемпотентна
$vigetRegistered = Neuro\Viget::registered($rootpath);
$vigetRepair     = '';

if (!$vigetRegistered) {

	$vigetRepair     = Neuro\Viget::register($rootpath);
	$vigetRegistered = Neuro\Viget::registered($rootpath);

}

// Путь до PHP не задаётся здесь: им управляет плагин «Планировщик заданий».
// Показываем то значение, которое реально уйдёт в bin задания: подсказка из
// настроек планировщика принимается только если такой файл есть на сервере
// (cronManager при активации сеет туда Linux-путь /opt/php72/bin/php).
$cronInstalled  = Neuro\Cron::managerInstalled($rootpath);
$cronSettings   = (array)customSettings(Neuro\Cron::MANAGER);
$cronHint       = Neuro\Cron::managerPhpBinary($cronSettings);
$cronPhp        = Neuro\Cron::effectivePhpBinary($cronSettings, PHP_OS_FAMILY, PHP_BINARY);
$cronHintBroken = ($cronHint !== '' && $cronHint !== $cronPhp);

if ($cronHint === '') {
	$cronSource = 'определён автоматически: планировщик не настроен';
}
elseif ($cronHintBroken) {
	$cronSource = 'определён автоматически: значение из планировщика ('.$cronHint.') на сервере не найдено';
}
else {
	$cronSource = 'настройка плагина «Планировщик заданий»';
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="utf-8">
	<title><?php echo $about['package']." - ".$about['name'] ?></title>
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="default">

	<link type="text/css" rel="stylesheet" href="/assets/css/app.css">
	<link type="text/css" rel="stylesheet" href="/assets/css/fontello.css">
	<link type="text/css" rel="stylesheet" href="/assets/css/ui.jquery.css">
	<link type="text/css" rel="stylesheet" href="assets/css/neurocore.css?v=<?php echo urlencode((string)$about['version']) ?>">

	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js"></script>

	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<script>
		var NeuroConfig = {
			url: 'php/modal.php',
			token: <?php echo json_encode($token) ?>
		};
	</script>

	<script type="text/javascript" src="assets/js/neurocore.js?v=<?php echo urlencode((string)$about['version']) ?>"></script>
</head>
<body>

<div id="dialog_container" class="dialog_container">
	<div class="dialog" id="dialog">
		<div class="close" title="Закрыть или нажмите ^ESC"><i class="icon-cancel"></i></div>
		<div id="resultdiv"></div>
	</div>
</div>

<div class="nc-main">

	<div class="nc-first">

		<div class="nc-header">
			<div class="nc-icon"><i class="<?php echo $about['icon'] ?>"></i></div>
			<div>
				<div class="nc-title"><?php echo $about['name'] ?></div>
				<div class="nc-subtitle"><?php echo $about['package'] ?>, версия <?php echo $about['version'] ?> от <?php echo $about['versiondate'] ?></div>
			</div>
			<?php /* Справка для сторонних разработчиков: как применять ядро в своём
			         плагине. Открывается отдельным окном — см. Neuro.openDocs() */ ?>
			<div class="nc-header--actions">
				<a href="javascript:void(0)" onclick="Neuro.openDocs()" class="button greenbtn dotted" title="Как применять Нейроядро в своём плагине: интерфейс, примеры, очередь"><i class="icon-doc-text"></i> Инструкция для разработчиков</a>
			</div>
		</div>

		<div class="nc-tabs">
			<div class="nc-tab current" data-tab="providers"><i class="icon-flash"></i> Подключения</div>
			<div class="nc-tab" data-tab="prompts"><i class="icon-lightbulb"></i> Промпты</div>
			<div class="nc-tab" data-tab="queue"><i class="icon-clock"></i> Очередь</div>
			<div class="nc-tab" data-tab="usage"><i class="icon-chart-bar"></i> Расход</div>
			<div class="nc-tab" data-tab="log"><i class="icon-list-alt"></i> Журнал</div>
			<div class="nc-tab" data-tab="settings"><i class="icon-cog-alt"></i> Настройки</div>
		</div>

		<?php if (!empty($installError)) { ?>

			<div class="nc-card" style="border-left: 3px solid #c62828;">

				<div class="Bold red">Таблицы ядра создать не удалось<?php echo !empty($settings['INSTALL_ERROR_DATE']) ? ' ('.htmlspecialchars((string)$settings['INSTALL_ERROR_DATE'], ENT_QUOTES, 'UTF-8').')' : '' ?></div>

				<div class="fs-11 mt5"><?php echo htmlspecialchars(implode('; ', $installError), ENT_QUOTES, 'UTF-8') ?></div>

				<div class="fs-09 gray mt10">Плагин не сможет работать, пока таблицы не созданы. Устраните причину и нажмите «Обновить» в списке плагинов.</div>

			</div>

		<?php } ?>

		<div class="nc-content current" data-id="providers">

			<div class="nc-card">

				<div class="nc-provider--meta mb10">
					Нейросеть подключается настройкой: укажите адрес API, модель и имя переменной окружения с ключом.
					Сам ключ в CRM не хранится.
				</div>

				<div data-block="providers">
					<div class="nc-empty"><img src="/assets/images/loading.gif"> Загрузка...</div>
				</div>

			</div>

			<div class="text-center">
				<a href="javascript:void(0)" onclick="Neuro.editProvider(0)" class="button dotted bluebtn"><i class="icon-plus"></i> Добавить подключение</a>
			</div>

		</div>

		<div class="nc-content" data-id="prompts">

			<div class="nc-card">

				<div class="nc-provider--meta mb10">
					Промпт — это инструкция для модели с переменными вида <b>{{client}}</b>, которые подставляет CRM.
					Фичи вызывают промпт по коду, поэтому код лучше не менять после того, как он появился в коде фичи.
				</div>

				<div data-block="prompts">
					<div class="nc-empty">Откройте вкладку, чтобы загрузить список.</div>
				</div>

			</div>

			<div class="text-center">
				<a href="javascript:void(0)" onclick="Neuro.editPrompt(0)" class="button dotted bluebtn"><i class="icon-plus"></i> Добавить промпт</a>
			</div>

		</div>

		<div class="nc-content" data-id="queue">

			<div class="nc-card">

				<div class="nc-provider--meta mb10">
					Задания из очереди выполняет воркер по расписанию планировщика. Так сделано потому,
					что ответ модели приходит за секунды, а иногда и за десятки секунд: держать на этом
					запрос страницы нельзя. К тому же задание в очереди переживает перезапуск сервера,
					а повтор при временной ошибке оплачивается один раз, а не при каждом клике.
				</div>

				<?php if (!$cronInstalled) { ?>

					<div class="warning p10 fs-09 mb10">
						<b>Плагин «Планировщик заданий» не установлен.</b> Задания будут копиться в очереди и не будут
						выполняться сами. Установите планировщик или запускайте воркер вручную.
					</div>

				<?php } elseif (empty($settings['CRON_TASK_ID'])) { ?>

					<div class="warning p10 fs-09 mb10">
						<b>Задание планировщика ещё не создано.</b> Откройте страницу «Плагины» и нажмите «Обновить»
						у плагина «Нейроядро» — задание создаётся при активации и обновлении.
					</div>

				<?php } else { ?>

					<div class="fs-09 gray mb10">
						Задание планировщика <b>#<?php echo (int)$settings['CRON_TASK_ID'] ?></b> — каждую минуту,
						интерпретатор <b><?php echo htmlspecialchars($cronPhp, ENT_QUOTES, 'UTF-8') ?></b>.
						Расписанием и путём до PHP управляет плагин «Планировщик заданий».
					</div>

				<?php } ?>

				<?php if (!empty($settings['CRON_ERROR'])) { ?>

					<div class="warning p10 fs-09 mb10">
						<b>Задание планировщика создать не удалось:</b>
						<?php echo htmlspecialchars((string)$settings['CRON_ERROR'], ENT_QUOTES, 'UTF-8') ?>
					</div>

				<?php } ?>

				<div class="fs-09 gray mb15">
					Если планировщик на сервере не запускается, очередь можно разобрать вручную из консоли:
					<b>php plugins/neuroCore/cron/worker.php --limit=5 --verbose</b>
				</div>

				<div class="nc-queue-filters mb10">
					<a href="javascript:void(0)" class="nc-chip" data-status="" onclick="Neuro.loadQueue('')">Все</a>
					<a href="javascript:void(0)" class="nc-chip" data-status="new" onclick="Neuro.loadQueue('new')">В очереди</a>
					<a href="javascript:void(0)" class="nc-chip" data-status="run" onclick="Neuro.loadQueue('run')">В работе</a>
					<a href="javascript:void(0)" class="nc-chip" data-status="ok" onclick="Neuro.loadQueue('ok')">Выполненные</a>
					<a href="javascript:void(0)" class="nc-chip" data-status="error" onclick="Neuro.loadQueue('error')">С ошибкой</a>
					<a href="javascript:void(0)" class="nc-chip" data-status="cancel" onclick="Neuro.loadQueue('cancel')">Отменённые</a>
					<a href="javascript:void(0)" class="blue" onclick="Neuro.loadQueue(Neuro.queueStatus)"><i class="icon-arrows-cw"></i> Обновить</a>
				</div>

				<div data-block="queue">
					<div class="nc-empty">Откройте вкладку, чтобы загрузить очередь.</div>
				</div>

				<div class="fs-09 gray mt15">
					Выполненные задания хранятся <?php echo $taskRetention > 0 ? (int)$taskRetention.' дней' : 'бессрочно' ?>:
					в них остаются переменные с данными CRM и ответ модели, поэтому старые убираются при каждом прогоне воркера.
					Незавершённые задания не удаляются никогда.
					<?php if ($taskRetention > 0 && $taskPurge['count'] > 0) { ?>
						Сейчас под уборку подходит <b><?php echo (int)$taskPurge['count'] ?></b> заданий.
					<?php } ?>
					<a href="javascript:void(0)" onclick="Neuro.purgeTasks()" class="red"><i class="icon-trash"></i> Убрать старые задания сейчас</a>
					<div class="mt5">Срок хранения заданий меняется в настройках плагина.</div>
				</div>

			</div>

		</div>

		<div class="nc-content" data-id="usage">

			<div class="nc-card">

				<div class="nc-provider--meta mb10">
					Учитывается каждый вызов, который вернул токены: из очереди, из интерфейса и от плагинов-фич.
					Стоимость считается только по ценам, заданным в подключении (за миллион токенов). Если цены
					не заданы, показываются точные токены — придуманных сумм здесь нет.
				</div>

				<div class="flex-container box--child mb10 infodiv">
					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">Период с:</div>
						<input type="text" id="usageFrom" value="<?php echo date('Y-m-d', strtotime('-6 days')) ?>" class="wp100 datepicker" placeholder="гггг-мм-дд" readonly>
					</div>
					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">Период до:</div>
						<input type="text" id="usageTo" value="<?php echo date('Y-m-d') ?>" class="wp100 datepicker" placeholder="гггг-мм-дд" readonly>
					</div>
					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">&nbsp;</div>
						<a href="javascript:void(0)" onclick="Neuro.loadUsage()" class="button dotted bluebtn">Показать</a>
					</div>
				</div>

				<div class="fs-09 gray">Формат даты: ГГГГ-ММ-ДД. Период по умолчанию — последние 7 дней.</div>

				<div data-block="usage" class="mt15">
					<div class="nc-empty">Откройте вкладку, чтобы загрузить отчёт.</div>
				</div>

			</div>

		</div>

		<div class="nc-content" data-id="log">

			<div class="nc-card">

				<div class="nc-provider--meta mb10">
					Журнал отвечает на вопрос «что вообще происходило»: сюда попадают итоги заданий,
					отказы подключений, уборка кэша и маскирование персональных данных. Текстов промптов
					и ответов модели здесь нет — только метаданные; полный ответ задания виден в его карточке.
				</div>

				<div class="flex-container box--child mb10 infodiv">

					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">Уровень</div>
						<select id="logLevel" class="wp100">
							<option value="">Все</option>
							<?php foreach (Neuro\Log::LEVELS as $levelKey => $levelTitle) { ?>
								<option value="<?php echo htmlspecialchars($levelKey, ENT_QUOTES, 'UTF-8') ?>"><?php echo htmlspecialchars($levelTitle, ENT_QUOTES, 'UTF-8') ?></option>
							<?php } ?>
						</select>
					</div>

					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">Период, дней</div>
						<input type="text" id="logPeriod" value="7" class="wp100">
					</div>

					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">Задание №</div>
						<input type="text" id="logTid" value="" class="wp100" placeholder="все">
					</div>

					<div class="flex-string p3 w200 mb10">
						<div class="fieldtitle">&nbsp;</div>
						<a href="javascript:void(0)" onclick="Neuro.loadLog()" class="button dotted bluebtn">Показать</a>
					</div>

				</div>

				<div class="fs-09 gray mb10">
					Срок хранения журнала задаётся в настройках: старые записи удаляет воркер очереди.
					Пока он не запускается (на этой машине планировщик выключен), журнал растёт — почистить его можно отсюда.
				</div>

				<div class="mb10">
					<a href="javascript:void(0)" onclick="Neuro.purgeLog()" class="red fs-09"><i class="icon-trash"></i> Удалить записи старше срока хранения</a>
				</div>

				<div data-block="log">
					<div class="nc-empty">Откройте вкладку, чтобы загрузить журнал.</div>
				</div>

			</div>

		</div>

		<div class="nc-content" data-id="settings">

			<form name="settingsForm" id="settingsForm">
				<input type="hidden" name="action" value="settings.save">
				<input type="hidden" name="token" value="<?php echo $token ?>">

				<div class="nc-card">

					<div class="Bold mb10">Виджет рабочего стола</div>

					<div class="fs-11">
						<?php if (!empty($settings['VIGET_ERROR'])) { ?>

							<div class="warning p10 fs-09">
								<b>Запись виджета в карту виджетов добавить не удалось:</b>
								<?php echo htmlspecialchars((string)$settings['VIGET_ERROR'], ENT_QUOTES, 'UTF-8') ?><br>
								Виджет не появится в «Мои настройки» → «Виджеты», пока каталог <b>cash</b> не станет доступен для записи.
								Обновите плагин на странице «Плагины», чтобы повторить попытку.
							</div>

						<?php } elseif (!$vigetRegistered) { ?>

							<div class="warning p10 fs-09">
								<b>Записи виджета в карте виджетов нет и создать её не удалось.</b>
								Файл <b>cash/map.vigets.castom.json</b> удалён или недоступен для записи<?php echo $vigetRepair !== '' ? ' ('.$vigetRepair.')' : '' ?>,
								поэтому виджет не появится в «Мои настройки» → «Виджеты». Проверьте права на каталог <b>cash</b>.
							</div>

						<?php } else { ?>

							Виджет «Нейроядро: очередь и расход» зарегистрирован<?php echo $vigetRepair !== '' ? ' (запись восстановлена: '.htmlspecialchars($vigetRepair, ENT_QUOTES, 'UTF-8').')' : '' ?>.
							Он показывает состояние очереди, расход за сегодня и последние ошибки.
							<div class="fs-09 flh-11 gray mt10">
								Виджет и его данные доступны <b>только администраторам</b>: у сотрудника без прав нет ни строки
								в списке виджетов, ни плитки на рабочем столе, а запрос данных закрыт на сервере.
								Администратор включает его себе сам: «Мои настройки» → «Виджеты».
								Запись живёт в <b>cash/map.vigets.castom.json</b> — этот файл не перезаписывается при обновлении.
							</div>

						<?php } ?>
					</div>

				</div>

				<div class="nc-card">

					<div class="Bold mb10">Планировщик заданий</div>

					<div class="fs-10">
						<div>Плагин «Планировщик заданий» (cronManager): <?php echo $cronInstalled ? '<b class="green">установлен</b>' : '<b class="red">не установлен</b>' ?></div>
						<div>Путь до PHP для фоновых заданий: <b><?php echo htmlspecialchars($cronPhp, ENT_QUOTES, 'UTF-8') ?></b></div>
						<div class="fs-09 gray mt10">Источник: <?php echo htmlspecialchars($cronSource, ENT_QUOTES, 'UTF-8') ?></div>
					</div>

					<div class="fs-09 gray mt10">
						Путь до PHP здесь не задаётся — им управляет плагин «Планировщик заданий».
						Задание на разбор очереди Нейроядра регистрируется там же при активации и обновлении плагина.
						<div class="mt5">Версия PHP: <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?>, SAPI: <?php echo htmlspecialchars(PHP_SAPI, ENT_QUOTES, 'UTF-8') ?></div>
					</div>

					<?php if ($cronHintBroken) { ?>

						<div class="warning p10 mt10 fs-09">
							<b>В настройках планировщика указан путь, которого нет на этом сервере:</b> <?php echo htmlspecialchars($cronHint, ENT_QUOTES, 'UTF-8') ?>.
							Похоже, там осталось значение по умолчанию для Linux. Для фоновых заданий будет использовано <b><?php echo htmlspecialchars($cronPhp, ENT_QUOTES, 'UTF-8') ?></b>,
							но правильнее открыть плагин «Планировщик заданий» и привести путь в порядок:
							на Windows достаточно <b>php</b> (интерпретатор берётся из PATH), на Linux — полный путь, например <b>/usr/bin/php</b>.
						</div>

					<?php } ?>

				</div>

				<div class="nc-card">

					<div class="flex-container">

						<div class="flex-string wp25 mb10">
							<div class="fieldtitle">Таймаут запроса, сек</div>
							<input type="number" name="TIMEOUT" value="<?php echo (int)$timeout ?>" class="wp100" min="5" max="300">
							<div class="fs-09 gray">от 5 до 300</div>
						</div>

						<div class="flex-string wp25 mb10">
							<div class="fieldtitle">Попыток при ошибке</div>
							<input type="number" name="MAX_ATTEMPTS" value="<?php echo (int)$tries ?>" class="wp100" min="1" max="10">
							<div class="fs-09 gray">от 1 до 10</div>
						</div>

						<div class="flex-string wp25 mb10">
							<div class="fieldtitle">Кэш ответов, минут</div>
							<input type="number" name="CACHE_TTL" value="<?php echo (int)$cacheTtl ?>" class="wp100" min="0" max="10080">
							<div class="fs-09 gray">0 — выключен, максимум 10080 (неделя)</div>
						</div>
						
						<div class="flex-string wp25 mb10">
							<div class="fieldtitle">Хранить журнал, дней</div>
							<input type="number" name="LOG_RETENTION" value="<?php echo (int)$logRetention ?>" class="wp100" min="0" max="365">
							<div class="fs-09 gray">0 — бессрочно, максимум 365</div>
						</div>

					</div>
					
					<div class="flex-container">
						
						<div class="flex-string w200 mb10">
							<div class="fieldtitle">Хранить задания, дней</div>
							<input type="number" name="TASK_RETENTION" value="<?php echo (int)$taskRetention ?>" class="wp100" min="0" max="365">
							<div class="fs-09 gray">выполненные; 0 — бессрочно, максимум 365</div>
						</div>
						
						<div class="flex-string w200 mb10">
							<div class="fieldtitle">Оповещать о очереди, заданий</div>
							<input type="number" name="QUEUE_ALERT_LIMIT" value="<?php echo (int)$alertLimit ?>" class="wp100" min="0" max="<?php echo (int)Neuro\Alerts::DEFAULT_LIMIT ?>">
							<div class="fs-09 gray">0 — не оповещать; по умолчанию <?php echo (int)Neuro\Alerts::DEFAULT_LIMIT ?></div>
						</div>
						
						<div class="flex-string w200 mb10">
							<div class="fieldtitle">Повтор оповещения, минут</div>
							<input type="number" name="ALERT_REPEAT_MINUTES" value="<?php echo (int)$alertRepeat ?>" class="wp100" min="0" max="<?php echo (int)$alertRepeat ?>">
							<div class="fs-09 gray">чтобы одно и то же не сыпалось на каждый вызов</div>
						</div>
					
					</div>

					<div class="fs-09 gray mb10">
						Кэш экономит деньги: буквально тот же вопрос (тот же промпт, версия, модель и данные)
						не отправляется модели второй раз — ответ берётся из кэша, и вызов не оплачивается.
						Изменились данные — изменился и вопрос, ответ будет свежим.
					</div>

					<div class="fs-09 gray mb10">
						<b>Оповещения</b> приходят в «колокольчик» CRM всем администраторам и попадают в журнал ядра:
						подключение не работает (ключ отозван, не задана переменная окружения, нет включённых подключений)
						и очередь выросла выше порога — последнее обычно означает, что не запускается планировщик.
						Окончательная ошибка задания уведомляет того, кто его поставил, всегда.
						Для Telegram/Viber есть хук <b>neurocore.alert</b>: его слушает тот, кому нужен внешний канал.
					</div>
					
					<hr>

					<div class="mb10 mt10">
						<label><input type="checkbox" name="STORE_PAYLOAD" value="on" <?php echo $store ? 'checked' : '' ?>> Сохранять переменные задания после выполнения</label>
						<div class="fs-09 gray pl20">
							Выключено (по умолчанию): после успешного задания переменные с данными CRM удаляются из очереди.
							У заданий с ошибкой они остаются всегда — иначе причину не разобрать.
						</div>
					</div>

				</div>

			</form>

			<div class="text-center">
				<a href="javascript:void(0)" onclick="Neuro.saveSettings()" class="button dotted bluebtn"><i class="icon-ok-circled"></i>Сохранить</a>
			</div>

		</div>

		<div class="space-20"></div>
		<div class="gray center-text mt20">Сделано для SalesMan CRM</div>

	</div>

	<div class="graybg-lite relativ" id="help">

		<div style="overflow-wrap: normal;word-wrap: break-word;word-break: normal;line-break: strict;hyphens: auto; width: 98%; box-sizing: border-box;">

			<?php

			$help = file_get_contents(__DIR__."/readme.md");

			$Parsedown = new Parsedown();
			$help = $Parsedown->text($help);

			$help = str_replace(
				["{{package}}", "{{version}}", "{{versiondate}}"],
				[$about['package'], $about['version'], $about['versiondate']],
				$help
			);

			print $help;

			?>

			<div class="space-50"></div>

		</div>

	</div>

</div>

</body>
</html>
