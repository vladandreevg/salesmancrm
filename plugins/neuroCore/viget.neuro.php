<?php
/* ============================ */
/*         SalesMan CRM         */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

use Neuro\Cache;
use Neuro\Log;
use Neuro\Queue;
use Neuro\Security;
use Neuro\Usage;

/**
 * Виджет рабочего стола «Нейроядро: очередь и расход».
 *
 * Отвечает на вопрос, который возникает каждый день: «нейросети работают и сколько
 * они уже потратили». Показывает состояние очереди, расход за сегодня и последние
 * ошибки из журнала.
 *
 * Виджет загружается в блок рабочего стола отдельным запросом, поэтому это фрагмент
 * разметки, а не страница: без <html>, без подключения css/js (они уже есть на
 * странице) и без побочных действий — только чтение.
 *
 * Расположение файла: `plugins/neuroCore/viget.neuro.php`, а НЕ в подкаталоге
 * `vigets/`. Причина в коде CRM: страница «Мои настройки» ищет виджет по адресу
 * `content/<url>`, если в url есть подстрока «vigets» (content/ajax/user.settings.php:861),
 * и по `content/` её не найти — виджет просто не появится в списке. Так же сделан
 * единственный виджет другого плагина (`plugins/licenseGenerator/viget.control.php`).
 *
 * Регистрация — в `cash/map.vigets.castom.json` (запись добавляет активация плагина),
 * включает виджет сам сотрудник в «Мои настройки» → «Виджеты».
 */

error_reporting(E_ERROR);

$rootpath = dirname(__DIR__, 2);

if (!isset($_COOKIE['ses'])) {
	$_COOKIE['ses'] = '';
}

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/plugins/neuroCore/php/autoload.php";

$identity = (int)($GLOBALS['identity'] ?? 0);
$iduser   = (int)($GLOBALS['iduser1'] ?? 0);

// Виджет показывает расход и ошибки всей установки, поэтому он только для
// администратора. Остальным — пустой блок: виджет можно включить в «Моих
// настройках», и красная плашка «Доступ запрещен» выглядела бы как поломка.
if (!Security::isAdmin($db, $sqlname, $iduser, $identity, $isadmin ?? '', $tipuser ?? '')) {
	return;
}

$enabled = false;

try {
	$enabled = ((string)$db->getOne("SELECT active FROM {$sqlname}plugins WHERE name = 'neuroCore' AND identity = ?i LIMIT 1", $identity) === 'on');
}
catch (Throwable $e) {
	$enabled = false;
}

if (!$enabled) {
	print '<div class="p10 fs-11 gray2">Плагин «Нейроядро» выключен — очередь и расход не ведутся.</div>';
	return;
}

$stats   = Queue::stats($db, $sqlname, $identity);
$report  = Usage::report($db, $sqlname, $identity, date('Y-m-d'), date('Y-m-d'));
$total   = $report['total'];
$journal = Log::listRows($db, $sqlname, $identity, ['period' => 7, 'level' => 'error', 'limit' => 3]);
$cache   = Cache::settingsTtl();
$models  = (int)$db->getOne("SELECT COUNT(*) FROM {$sqlname}neuro_provider WHERE active = 'on' AND identity = ?i", $identity);

// Деньги: одна валюта — одна сумма, разные — показываем отдельно (как в отчёте):
// складывать рубли с долларами нельзя.
if (!empty($total['mixed'])) {

	$money = [];

	foreach ((array)$total['currencies'] as $currency => $sum) {
		$money[] = number_format((float)$sum, 4, '.', ' ').($currency !== '' ? ' '.$currency : '');
	}

	$moneyText = implode(' + ', $money);

}
elseif (!empty($total['priced'])) {

	$moneyText = number_format((float)$total['cost'], 4, '.', ' ').((string)$total['currency'] !== '' ? ' '.(string)$total['currency'] : '');

}
else {
	$moneyText = '';
}

$cards = [
	['title' => 'В очереди', 'value' => (int)($stats['new'] ?? 0), 'color' => (int)($stats['new'] ?? 0) > 0 ? 'blue' : 'gray2'],
	['title' => 'В работе', 'value' => (int)($stats['run'] ?? 0), 'color' => (int)($stats['run'] ?? 0) > 0 ? 'orange' : 'gray2'],
	['title' => 'С ошибкой', 'value' => (int)($stats['error'] ?? 0), 'color' => (int)($stats['error'] ?? 0) > 0 ? 'red' : 'gray2'],
	['title' => 'Подключений', 'value' => $models, 'color' => $models > 0 ? 'green' : 'red'],
];

?>
<div class="p10">

	<div class="fs-10 mb10">
		Сегодня: вызовов <b><?php echo (int)$total['calls'] ?></b>,
		токены <b><?php echo (int)$total['tokens_in'] ?></b> / <b><?php echo (int)$total['tokens_out'] ?></b><?php
		if ($moneyText !== '') {
			?>, стоимость <b><?php echo htmlspecialchars($moneyText, ENT_QUOTES, 'UTF-8') ?></b><?php
		}
		?>.
		<?php if ($moneyText === '' && (int)$total['calls'] > 0) { ?>
			<div class="fs-09 gray2">Цены не заданы в подключении — считается только расход в токенах.</div>
		<?php } ?>
	</div>

	<div class="flex-container box--child mb10">

		<?php foreach ($cards as $card) { ?>

			<div class="flex-string wp25 text-center">
				<div class="fs-18 Bold <?php echo $card['color'] ?>"><?php echo (int)$card['value'] ?></div>
				<div class="fs-09 gray2 ellipsis" title="<?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?>"><?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
			</div>

		<?php } ?>

	</div>

	<?php if (!empty($journal['rows'])) { ?>

		<div class="fs-09 Bold mb5">Последние ошибки</div>

		<?php foreach ($journal['rows'] as $row) { ?>

			<div class="fs-09 mb5" title="<?php echo htmlspecialchars((string)$row['datum'], ENT_QUOTES, 'UTF-8') ?>">
				<i class="icon-attention red"></i>
				<span class="gray2"><?php echo htmlspecialchars(mb_substr((string)$row['message'], 0, 110), ENT_QUOTES, 'UTF-8') ?></span>
			</div>

		<?php } ?>

	<?php } else { ?>

		<div class="fs-09 green mb5"><i class="icon-ok-circled"></i> Ошибок за неделю нет</div>

	<?php } ?>

	<div class="fs-09 gray2 mt10">
		Кэш ответов: <?php echo $cache > 0 ? 'включён ('.(int)round($cache / 60).' мин)' : 'выключен' ?>.
		<a href="/plugins/neuroCore/" target="_blank" class="blue">Открыть Нейроядро</a>
	</div>

</div>
