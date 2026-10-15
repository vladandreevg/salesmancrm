<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

use Neuro\Cache;
use Neuro\Crypto;
use Neuro\Log;
use Neuro\Prompt;
use Neuro\Providers;
use Neuro\Queue;
use Neuro\Security;
use Neuro\Usage;

/**
 * AJAX-эндпоинт раздела «Нейро».
 *
 * Требования безопасности (по итогам аудита):
 *  - вход только для администратора (гейт сразу после подключения ядра);
 *  - изменяющие действия и проверка соединения — только с CSRF-токеном;
 *  - identity/iduser — только из сессии;
 *  - все SQL-запросы — через плейсхолдеры SafeMySQL;
 *  - вывод экранируется.
 */

$rootpath = dirname(__DIR__, 3);

error_reporting(E_ERROR);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth_main.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once __DIR__."/autoload.php";

// Доступ только для администратора
if (!Security::isAdmin($db, $sqlname, (int)$iduser1, (int)$identity, $isadmin, $tipuser)) {

	http_response_code(403);

	header('Content-Type: application/json; charset=utf-8');

	print json_encode_cyr(["status" => "error", "message" => "Доступ запрещен"]);

	exit();

}

$action = isset($_REQUEST['action']) ? (string)$_REQUEST['action'] : '';

/**
 * Значения по умолчанию из настроек ядра: таймаут запроса и число попыток.
 * Читаются здесь, чтобы интерфейс и воркер брали их из одного места.
 */
$defaults = Queue::defaults((array)customSettings('neuroCore'));

/**
 * Действия, которые меняют данные или тратят деньги пользователя
 * (проверка соединения делает реальный запрос к API). Все они требуют CSRF-токен.
 */
$protected = ['settings.save', 'provider.save', 'provider.delete', 'provider.test', 'prompt.save', 'prompt.delete', 'prompt.run', 'prompt.queue', 'queue.retry', 'queue.cancel', 'cache.clear', 'log.purge'];

if (in_array($action, $protected, true)) {

	Security::requireToken(isset($_REQUEST['token']) ? $_REQUEST['token'] : '');

}

/**
 * JSON-ответ
 *
 * @param array $data
 */
function answer(array $data): void {

	header('Content-Type: application/json; charset=utf-8');

	print json_encode_cyr($data);

	exit();

}

/**
 * Сообщение об ошибке базы данных.
 *
 * У необработанного исключения SafeMySQL ответ становится 500 без тела, и человек
 * видит только «сервер не ответил» — причина прячется. Лучше показать её.
 *
 * @param Exception $e
 *
 * @return string
 */
function dbErrorText(Exception $e): string {
	return 'Ошибка базы данных: '.mb_substr($e->getMessage(), 0, 300);
}

switch ($action) {

	// -----------------------------------------------------------------------
	// Подключения
	// -----------------------------------------------------------------------

	case 'providers.list':

		$list = [];

		foreach (Providers::all($db, $sqlname, (int)$identity) as $row) {

			$keyInfo = Providers::keyInfo($row, $rootpath);
			$prices  = Providers::prices($row);

			$list[] = [
				'id'          => (int)$row['id'],
				'name'        => (string)$row['name'],
				'code'        => (string)$row['code'],
				'codeTitle'   => Providers::codeTitle((string)$row['code']),
				'base_url'    => (string)$row['base_url'],
				'model'       => (string)$row['model'],
				'env'         => (string)$row['api_key_env'],
				'source'      => $keyInfo['source'],
				'sourceTitle' => Providers::SOURCES[$keyInfo['source']] ?? $keyInfo['source'],
				'key_set'     => $keyInfo['set'],
				'key_hint'    => $keyInfo['hint'],
				'active'      => (string)$row['active'],
				'pd_allowed'  => Providers::pdAllowed($row),
				'priced'      => ($prices['in'] !== NULL && $prices['out'] !== NULL) || $prices['call'] !== NULL,
				'price_in'    => $prices['in'],
				'price_out'   => $prices['out'],
				'price_call'  => $prices['call'],
				'currency'    => $prices['currency'],
			];

		}

		answer(['status' => 'ok', 'list' => $list]);

		break;

	case 'provider.get':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		$row = Providers::byId($db, $sqlname, $id, (int)$identity);

		$params = Providers::params($row);

		$name    = (string)($row['name'] ?? '');
		$code    = (string)($row['code'] ?? 'openai');
		$baseUrl = (string)($row['base_url'] ?? '');
		$model   = (string)($row['model'] ?? '');
		$env     = (string)($row['api_key_env'] ?? '');
		$active  = (string)($row['active'] ?? 'on');
		$source  = (string)($row['key_source'] ?? Providers::DEFAULT_SOURCE);

		$keyInfo      = Providers::keyInfo($row, $rootpath);
		$hasStoredKey = trim((string)($row['api_key_enc'] ?? '')) !== '';
		$storedMask   = (string)($keyInfo['mask'] ?? '');

		// Маскирование персональных данных: по умолчанию разрешено (данные уходят как есть)
		$pdAllowed = Providers::pdAllowed($row);

		// имена переменных, которые PHP действительно видит: чтобы имя не угадывали
		$envNames = Providers::candidateNames();

		ob_start();

		?>
		<DIV class="zagolovok"><B><?php echo $id > 0 ? 'Изменить подключение' : 'Новое подключение' ?></B></DIV>

		<form name="providerForm" id="providerForm">

			<input type="hidden" name="action" value="provider.save">
			<input type="hidden" name="id" value="<?php echo (int)$id ?>">
			<input type="hidden" name="token" value="<?php echo htmlspecialchars(Security::sessionToken(), ENT_QUOTES, 'UTF-8') ?>">

			<div class="flex-container p5" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Название подключения *</div>
					<input type="text" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="Например: DeepSeek">
				</div>

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Тип подключения *</div>
					<select name="code" class="wp100">
						<?php foreach (Providers::codes() as $key => $title) { ?>
							<option value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?php echo $code === $key ? 'selected' : '' ?>><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></option>
						<?php } ?>
					</select>
					<div class="fs-09 gray">Любая нейросеть с OpenAI-совместимым API подходит без правок кода</div>
				</div>

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Адрес API (base_url) *</div>
					<input type="text" name="base_url" value="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="https://api.deepseek.com">
					<div class="fs-09 gray">Можно указывать и корень сервиса, и адрес с версией API: /v1 подставится сам</div>
				</div>

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Модель *</div>
					<input type="text" name="model" value="<?php echo htmlspecialchars($model, ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="deepseek-chat">
				</div>

				<div class="flex-string mb15 wp100">
					
					<div class="fieldtitle mb10">Откуда брать ключ *</div>

					<div class="mb5">
						<label><input type="radio" name="key_source" value="db" <?php echo $source === 'db' ? 'checked' : '' ?>>Хранить в CRM (зашифрованно)</label>
						<div class="fs-09 gray pl20">Ключ вводится здесь и сохраняется в базе в зашифрованном виде — на сервер ходить не нужно</div>
					</div>

					<div>
						<label><input type="radio" name="key_source" value="env" <?php echo $source === 'env' ? 'checked' : '' ?>>Переменная окружения сервера</label>
						<div class="fs-09 gray pl20">Ключ не хранится в CRM вообще — в базе только имя переменной</div>
					</div>
				</div>

				<div class="flex-string mb10 wp100" data-keyblock="db">

					<div class="fieldtitle">Ключ API<?php echo $hasStoredKey ? '' : ' *' ?></div>

					<input type="password" name="api_key" value="" class="wp100" autocomplete="new-password" placeholder="<?php echo $hasStoredKey ? 'оставьте пустым, чтобы не менять' : 'sk-...' ?>">

					<div class="fs-09 gray">
						<?php if ($hasStoredKey) { ?>
							Сохранён: <b><?php echo htmlspecialchars($storedMask, ENT_QUOTES, 'UTF-8') ?></b><?php echo $keyInfo['set'] ? '' : ' <span class="red">('.htmlspecialchars((string)$keyInfo['hint'], ENT_QUOTES, 'UTF-8').')</span>' ?>. Новое значение заменит ключ.
						<?php } else { ?>
							Значение сохраняется в зашифрованном виде и больше не показывается — только маска.
						<?php } ?>
					</div>

				</div>

				<div class="flex-string mb10 wp100" data-keyblock="env">

					<div class="fieldtitle">Имя переменной окружения *</div>

					<input type="text" name="api_key_env" value="<?php echo htmlspecialchars($env, ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="DEEPSEEK_API_KEY" list="neuroEnvNames" autocomplete="off">

					<?php if (!empty($envNames)) { ?>
						<datalist id="neuroEnvNames">
							<?php foreach ($envNames as $envName) { ?>
								<option value="<?php echo htmlspecialchars($envName, ENT_QUOTES, 'UTF-8') ?>"></option>
							<?php } ?>
						</datalist>
					<?php } ?>

					<div class="fs-09 gray">
						Только <b>имя</b> переменной, без значения.
						<?php if (!empty($envNames)) { ?>
							PHP видит переменные: <b><?php echo htmlspecialchars(implode(', ', array_slice($envNames, 0, 5)), ENT_QUOTES, 'UTF-8') ?></b><?php echo count($envNames) > 5 ? ' и другие' : '' ?> — можно выбрать из списка.
						<?php } else { ?>
							Задайте переменную на сервере (рецепты в справке справа) или выберите хранение ключа в CRM.
						<?php } ?>
					</div>

					<div class="fs-09 blue mt5" id="envHint"></div>

				</div>

				<div class="flex-container">

					<div class="flex-string w200 mb10">
						<div class="fieldtitle">Температура</div>
						<input type="text" name="temperature" value="<?php echo isset($params['temperature']) ? htmlspecialchars((string)$params['temperature'], ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="по умолчанию">
					</div>

					<div class="flex-string w200 mb10">
						<div class="fieldtitle">Максимум токенов</div>
						<input type="text" name="max_tokens" value="<?php echo isset($params['max_tokens']) ? (int)$params['max_tokens'] : '' ?>" placeholder="2048">
					</div>

					<div class="flex-string w200 mb10">
						<div class="fieldtitle">Таймаут, сек</div>
						<input type="text" name="timeout" value="<?php echo isset($params['timeout']) ? (int)$params['timeout'] : '' ?>" placeholder="30">
					</div>

				</div>

				<div class="flex-container box--child">

					<div class="flex-string wp30 mb10">
						<div class="fieldtitle">Цена за 1 млн входных</div>
						<input type="text" name="price_in" value="<?php echo isset($params['price_in']) ? htmlspecialchars((string)$params['price_in'], ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="не обязательно">
					</div>

					<div class="flex-string wp30 mb10">
						<div class="fieldtitle">Цена за 1 млн выходных</div>
						<input type="text" name="price_out" value="<?php echo isset($params['price_out']) ? htmlspecialchars((string)$params['price_out'], ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="не обязательно">
					</div>

					<div class="flex-string wp30 mb10">
						<div class="fieldtitle">Валюта цен</div>
						<input type="text" name="currency" value="<?php echo htmlspecialchars(Providers::currency((string)($params['currency'] ?? '')), ENT_QUOTES, 'UTF-8') ?>" placeholder="₽ или $" maxlength="8">
					</div>
				
				</div>
				
				<div class="flex-container box--child">

					<div class="flex-string mb10 p3">
						<div class="fieldtitle">Режим JSON</div>
						<select name="json_mode" class="wp100">
							<option value="" <?php echo empty($params['json_mode']) ? 'selected' : '' ?>>Не использовать</option>
							<option value="json_object" <?php echo (($params['json_mode'] ?? '') === 'json_object') ? 'selected' : '' ?>>JSON-объект</option>
							<option value="json_schema" <?php echo (($params['json_mode'] ?? '') === 'json_schema') ? 'selected' : '' ?>>Строгая схема</option>
						</select>
						<div class="fs-09 gray">Не все API это принимают: если появляется ошибка про response_format — верните «Не использовать». Схема всё равно проверяется, при несоответствии будет один повтор</div>
					</div>

				</div>

				<div class="flex-container">

					<div class="flex-string w200 mb10">
						<div class="fieldtitle">Плата за вызов</div>
						<input type="text" name="price_call" value="<?php echo isset($params['price_call']) ? htmlspecialchars((string)$params['price_call'], ENT_QUOTES, 'UTF-8') : '' ?>" placeholder="не обязательно">
						<div class="fs-09 gray">Если провайдер берёт фиксированную плату за каждый запрос: у онлайн-моделей VseGPT (Perplexity Sonar) это 0.85 ₽ за поисковый запрос — она обычно больше стоимости токенов</div>
					</div>

					<div class="flex-string mb10">
						<div class="fieldtitle">&nbsp;</div>
						<div class="fs-09 gray">
							Цены указываются <b>за миллион токенов</b>. Прайс VseGPT опубликован <b>за 1000 токенов</b>:
							умножьте его на 1000 (0.05 за 1000 → <b>50</b> за миллион). Валюта нужна, чтобы отчёт не складывал
							рубли с долларами: при разных валютах в одном периоде он покажет суммы отдельно.
						</div>
					</div>

				</div>

				<div class="fs-09 gray mb10">Если цены не указаны, в отчёте будут только токены — выдумывать прайс чужой модели мы не будем</div>

				<div class="mb10 mt10">
					<label><input type="checkbox" name="active" value="on" <?php echo $active === 'on' ? 'checked' : '' ?>> Подключение активно</label>
				</div>

				<div class="mb10 mt10">
					<label><input type="checkbox" name="pd_allowed" value="yes" <?php echo $pdAllowed ? 'checked' : '' ?>> Разрешить отправлять персональные данные клиентов</label>
					<div class="fs-09 gray pl20">
						Включено (по умолчанию): промпт уходит провайдеру как есть. Если выключить, ядро заменит телефоны,
						почту, ФИО и реквизиты плейсхолдерами вида <b>[PHONE_1]</b>, а в ответе модели подставит их обратно:
						модель видит структуру письма, но сами данные остаются в CRM. Каждая такая отправка отмечается в журнале.
						Названия компаний, должности и текст переписки не маскируются — по ним клиент всё ещё узнаваем.
					</div>
				</div>

			</div>

			<hr>

			<div class="button--pane text-right">
				<a href="javascript:void(0)" onclick="Neuro.saveProvider()" class="button">Сохранить</a>&nbsp;
				<a href="javascript:void(0)" onclick="Neuro.closeDialog()" class="button">Отмена</a>
			</div>

		</form>
		<?php

		// разметку отдаём внутри JSON: клиент запрашивает действие с dataType 'json',
		// и «голый» HTML он разобрать не сможет (на этом сломалось добавление подключения)
		answer(['status' => 'ok', 'html' => (string)ob_get_clean()]);

		break;

	case 'provider.save':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		$row = [
			'name'        => trim((string)(isset($_REQUEST['name']) ? $_REQUEST['name'] : '')),
			'code'        => trim((string)(isset($_REQUEST['code']) ? $_REQUEST['code'] : '')),
			'base_url'    => trim((string)(isset($_REQUEST['base_url']) ? $_REQUEST['base_url'] : '')),
			'model'       => trim((string)(isset($_REQUEST['model']) ? $_REQUEST['model'] : '')),
			'api_key_env' => strtoupper(trim((string)(isset($_REQUEST['api_key_env']) ? $_REQUEST['api_key_env'] : ''))),
			'key_source'  => (string)(isset($_REQUEST['key_source']) ? $_REQUEST['key_source'] : 'env'),
			'api_key'     => trim((string)(isset($_REQUEST['api_key']) ? $_REQUEST['api_key'] : '')),
			'active'      => (isset($_REQUEST['active']) && $_REQUEST['active'] === 'on') ? 'on' : 'off',
			// галочка «разрешить персональные данные»: снятая означает маскирование
			'pd_allowed'  => (isset($_REQUEST['pd_allowed']) && $_REQUEST['pd_allowed'] === 'yes') ? 'yes' : 'no',
		];

		// при изменении пустое поле ключа означает «оставить прежний»
		$existing     = Providers::byId($db, $sqlname, $id, (int)$identity);
		$hasStoredKey = trim((string)($existing['api_key_enc'] ?? '')) !== '';

		$errors = Providers::validate($row, $hasStoredKey);

		if (!empty($errors)) {
			answer(['status' => 'error', 'message' => implode('. ', array_values($errors))]);
		}

		$data = [
			'name'        => $row['name'],
			'code'        => $row['code'],
			'base_url'    => $row['base_url'],
			'model'       => $row['model'],
			'key_source'  => $row['key_source'],
			'active'      => $row['active'],
			'pd_allowed'  => $row['pd_allowed'],
			'params'      => json_encode_cyr(Providers::collectParams($_REQUEST)),
			'date_edit'   => current_datumtime(),
			'identity'    => (int)$identity,
		];

		if ($row['key_source'] === 'db') {

			// имя переменной не нужно, а сохранённый ключ меняем только если ввели новый
			$data['api_key_env'] = NULL;

			if ($row['api_key'] !== '') {

				if (!Crypto::available()) {
					answer(['status' => 'error', 'message' => 'В этой сборке PHP нет openssl с поддержкой AES-256-GCM: сохраните ключ в переменной окружения']);
				}

				$master = Crypto::ensureKey($rootpath);

				if ($master === '') {
					answer(['status' => 'error', 'message' => 'Не удалось создать файл ключа шифрования в plugins/neuroCore/data/. Проверьте права на каталог plugins/neuroCore']);
				}

				$encrypted = Crypto::encrypt($row['api_key'], $master);

				if ($encrypted === '') {
					answer(['status' => 'error', 'message' => 'Не удалось зашифровать ключ — значение не сохранено']);
				}

				$data['api_key_enc'] = $encrypted;

			}

		}
		else {

			// переключились на переменную окружения — сохранённый в CRM ключ убираем,
			// чтобы не оставался забытый секрет
			$data['api_key_env'] = $row['api_key_env'];
			$data['api_key_enc'] = NULL;

		}

		try {

			if ($id > 0) {

				$db->query("UPDATE {$sqlname}neuro_provider SET ?u WHERE id = ?i AND identity = ?i", $data, $id, (int)$identity);

			}
			else {

				$db->query("INSERT INTO {$sqlname}neuro_provider SET ?u", $data);

				$id = (int)$db->insertId();

			}

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		answer([
			'status'  => 'ok',
			'message' => $row['key_source'] === 'db' && $row['api_key'] !== '' ? 'Подключение сохранено, ключ зашифрован' : 'Подключение сохранено',
			'id'      => $id,
		]);

		break;

	case 'provider.delete':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		if ($id <= 0) {
			answer(['status' => 'error', 'message' => 'Некорректный идентификатор подключения']);
		}

		try {

			$db->query("DELETE FROM {$sqlname}neuro_provider WHERE id = ?i AND identity = ?i", $id, (int)$identity);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		answer(['status' => 'ok', 'message' => 'Подключение удалено']);

		break;

	case 'provider.test':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		$row = Providers::byId($db, $sqlname, $id, (int)$identity);

		if (empty($row)) {
			answer(['status' => 'error', 'message' => 'Подключение не найдено']);
		}

		try {

			// Проверка идёт через фасад, а не через адаптер напрямую: это настоящий
			// платный запрос, и он должен попадать в отчёт по расходу (тип задачи
			// «core.provider.test»), иначе «Проверить» тратит деньги незаметно.
			$neuro = new Neuro\Neuro($db, $sqlname, (int)$identity, $rootpath);

			$result = $neuro->request(array_merge(
				Providers::testRequest($row),
				[
					'provider_id' => $id,
					'code'        => Providers::TEST_CODE,
					'iduser'      => (int)$iduser1,
				]
			));

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => $e->getMessage()]);

		}

		if ($result['error'] !== null) {

			// Отказ подключения — событие для журнала: администратор видит его и
			// потом, когда карточка подключения уже закрыта. Ответа модели здесь
			// нет по определению, поэтому пишем метаданные.
			Log::write($db, $sqlname, 0, 'warn', 'Проверка подключения «'.$row['name'].'» не прошла: '.$result['error']['code'], [
				'provider' => (string)$row['name'],
				'model'    => (string)$result['model'],
				'error'    => (string)$result['error']['code'],
				'iduser'   => (int)$iduser1,
			], (int)$identity);

			answer([
				'status'  => 'error',
				'message' => $result['error']['code'].': '.$result['error']['message'],
				'model'   => (string)$result['model'],
				'latency' => (int)$result['latency_ms'],
				'tokens'  => ['in' => (int)$result['usage']['in'], 'out' => (int)$result['usage']['out']],
				'text'    => '',
			]);

		}

		Log::write($db, $sqlname, 0, 'debug', 'Проверка подключения «'.$row['name'].'» прошла', [
			'provider' => (string)$row['name'],
			'model'    => (string)$result['model'],
			'latency'  => (int)$result['latency_ms'],
			'tokens'   => (int)$result['usage']['in'].'/'.(int)$result['usage']['out'],
			'iduser'   => (int)$iduser1,
		], (int)$identity);

		answer([
			'status'  => 'ok',
			'message' => 'Соединение работает',
			'model'   => (string)$result['model'],
			'latency' => (int)$result['latency_ms'],
			'tokens'  => ['in' => (int)$result['usage']['in'], 'out' => (int)$result['usage']['out']],
			'text'    => mb_substr(trim((string)$result['text']), 0, 300),
		]);

		break;

	// -----------------------------------------------------------------------
	// Промпты
	// -----------------------------------------------------------------------

	case 'prompts.list':

		$list = [];

		foreach (Prompt::all($db, $sqlname, (int)$identity) as $row) {

			$params = Prompt::params($row);

			$list[] = [
				'id'      => (int)$row['id'],
				'code'    => (string)$row['code'],
				'name'    => (string)$row['name'],
				'version' => (int)$row['version'],
				'active'  => (string)$row['active'],
				'hasJson' => !empty($params['json_schema']),
				'symbols' => mb_strlen((string)$row['content']),
				'model'   => (string)($params['model'] ?? ''),
				'owner'   => (string)($row['owner'] ?? ''),
				'source'  => (string)($row['source'] ?? 'admin'),
				'differs' => Prompt::pluginDefaultDiffers($row),
			];

		}

		answer(['status' => 'ok', 'list' => $list]);

		break;

	case 'prompt.get':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		$row = Prompt::byId($db, $sqlname, $id, (int)$identity);

		if ($id > 0 && empty($row)) {
			answer(['status' => 'error', 'message' => 'Промпт не найден']);
		}

		$params   = Prompt::params($row);
		$schema   = $params['json_schema']['schema'] ?? ($params['json_schema'] ?? NULL);
		$versions = $id > 0 ? Prompt::revisions($db, $sqlname, $id, (int)$identity) : [];

		$owner     = trim((string)($row['owner'] ?? ''));
		$ownerText = $owner !== '' ? 'Промпт принадлежит плагину «'.$owner.'»' : 'Промпт создан вручную в CRM';
		$differs   = Prompt::pluginDefaultDiffers($row);

		ob_start();

		?>
		<DIV class="zagolovok"><B><?php echo $id > 0 ? 'Изменить промпт' : 'Новый промпт' ?></B></DIV>

		<div class="fs-09 gray mb10">
			<?php echo htmlspecialchars($ownerText, ENT_QUOTES, 'UTF-8') ?><?php echo ($id > 0 && (string)($row['source'] ?? '') === 'admin') ? ' · текст изменён администратором' : '' ?>
		</div>

		<?php if ($differs) { ?>

			<div class="warning p10 fs-09 mb10">
				<b>Плагин приносит другой текст этого промпта.</b> Сохранён ваш вариант — правки не потеряны.
				<a href="javascript:void(0)" onclick="$('#pluginText').toggle()" class="blue">Показать текст из плагина</a>
				<div id="pluginText" style="display:none" class="mt5"><pre class="fs-09"><?php echo htmlspecialchars((string)$row['plugin_content'], ENT_QUOTES, 'UTF-8') ?></pre></div>
			</div>

		<?php } ?>

		<form name="promptForm" id="promptForm">

			<input type="hidden" name="action" value="prompt.save">
			<input type="hidden" name="id" value="<?php echo (int)$id ?>">
			<input type="hidden" name="token" value="<?php echo htmlspecialchars(Security::sessionToken(), ENT_QUOTES, 'UTF-8') ?>">

			<div class="p5" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">

				<div class="flex-container">

					<div class="flex-string w300 mb10">
						<div class="fieldtitle">Код промпта *</div>
						<input type="text" name="code" value="<?php echo htmlspecialchars((string)($row['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="deal.summary" <?php echo $id > 0 ? 'readonly' : '' ?>>
						<div class="fs-09 gray"><?php echo $id > 0 ? 'Код не меняется: фичи ищут промпт по нему' : 'Строчные латинские буквы, цифры, точка, дефис' ?></div>
					</div>

					<div class="flex-string mb10">
						<div class="fieldtitle">Название</div>
						<input type="text" name="name" value="<?php echo htmlspecialchars((string)($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="Для списка промптов">
					</div>

				</div>

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Текст промпта *</div>
					<textarea name="content" class="wp100" rows="12" placeholder="Клиент: {{client}}&#10;История: {{history}}&#10;Кратко опиши ситуацию по сделке"><?php echo htmlspecialchars((string)($row['content'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
					<div class="fs-09 gray">Переменные в двойных фигурных скобках подставляет CRM. Доступны секции: {{#items}}…{{/items}}</div>
				</div>

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Системная инструкция (необязательно)</div>
					<textarea name="system" class="wp100" rows="3"><?php echo htmlspecialchars((string)($params['system'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
				</div>

				<div class="flex-container">

					<div class="flex-string w250 mb10">
						<div class="fieldtitle">Подключение</div>
						<select name="provider_id" class="wp100">
							<option value="">-- первое включённое --</option>
							<?php foreach (Providers::all($db, $sqlname, (int)$identity) as $provider) { ?>
								<option value="<?php echo (int)$provider['id'] ?>" <?php echo (int)($params['provider_id'] ?? 0) === (int)$provider['id'] ? 'selected' : '' ?>><?php echo htmlspecialchars((string)$provider['name'], ENT_QUOTES, 'UTF-8') ?></option>
							<?php } ?>
						</select>
					</div>

					<div class="flex-string w200 mb10">
						<div class="fieldtitle">Модель</div>
						<input type="text" name="model" value="<?php echo htmlspecialchars((string)($params['model'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="wp100" placeholder="как в подключении">
					</div>

					<div class="flex-string w150 mb10">
						<div class="fieldtitle">Температура</div>
						<input type="text" name="temperature" value="<?php echo isset($params['temperature']) ? htmlspecialchars((string)$params['temperature'], ENT_QUOTES, 'UTF-8') : '' ?>" class="wp100" placeholder="0.7">
					</div>

					<div class="flex-string w150 mb10">
						<div class="fieldtitle">Макс. токенов</div>
						<input type="text" name="max_tokens" value="<?php echo isset($params['max_tokens']) ? (int)$params['max_tokens'] : '' ?>" class="wp100" placeholder="2048">
					</div>

				</div>

				<div class="flex-string mb10 wp100">
					<div class="fieldtitle">Схема ответа JSON (необязательно)</div>
					<textarea name="json_schema" class="wp100" rows="6" placeholder='{"type":"object","properties":{"summary":{"type":"string"}},"required":["summary"]}'><?php echo $schema !== NULL ? htmlspecialchars((string)json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') : '' ?></textarea>
					<div class="fs-09 gray">Если задана, ответ проверяется по ней, а при несоответствии делается один повтор</div>
				</div>

				<div class="mb10">
					<label><input type="checkbox" name="active" value="on" <?php echo ((string)($row['active'] ?? 'on')) === 'on' ? 'checked' : '' ?>> Промпт активен</label>
				</div>

				<?php if ($id > 0) { ?>

					<hr>

					<div class="fieldtitle">Проверить на примере</div>

					<div class="fs-09 gray mb5">
						Переменные в формате JSON, например <code>{"client":"ООО Ромашка"}</code>.
						«Выполнить» делает запрос прямо сейчас и держит окно открытым до ответа модели.
						«В очередь» ставит задание: его выполнит воркер по расписанию, а результат придёт уведомлением —
						так делают массовые операции, когда ждать в интерфейсе нельзя.
					</div>

					<textarea name="tags" class="wp100" rows="3" placeholder='{"client":"ООО Ромашка"}'></textarea>

					<div class="mt10">
						<a href="javascript:void(0)" onclick="Neuro.runPrompt(<?php echo (int)$id ?>)" class="button rounded-r10">Выполнить</a>
						<a href="javascript:void(0)" onclick="Neuro.queuePrompt(<?php echo (int)$id ?>)" class="button bluebtn rounded-r10">В очередь</a>
					</div>

					<div class="nc-test-result" id="promptRunResult" style="display:none"></div>

				<?php } ?>

				<?php if (!empty($versions)) { ?>

					<hr>

					<div class="fieldtitle">Версии</div>

					<div class="fs-11">
						<?php foreach ($versions as $revision) { ?>
							<div class="nc-provider--meta">
								версия <?php echo (int)$revision['version'] ?>, <?php echo htmlspecialchars((string)$revision['datum'], ENT_QUOTES, 'UTF-8') ?>, <?php echo mb_strlen((string)$revision['content']) ?> символов
							</div>
						<?php } ?>
					</div>

				<?php } ?>

			</div>

			<hr>

			<div class="button--pane text-right">
				<a href="javascript:void(0)" onclick="Neuro.savePrompt()" class="button">Сохранить</a>&nbsp;
				<a href="javascript:void(0)" onclick="Neuro.closeDialog()" class="button">Отмена</a>
			</div>

		</form>
		<?php

		answer(['status' => 'ok', 'html' => (string)ob_get_clean()]);

		break;

	case 'prompt.save':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		$row = [
			'id'      => $id,
			'code'    => (string)(isset($_REQUEST['code']) ? $_REQUEST['code'] : ''),
			'name'    => (string)(isset($_REQUEST['name']) ? $_REQUEST['name'] : ''),
			'content' => (string)(isset($_REQUEST['content']) ? $_REQUEST['content'] : ''),
			'active'  => (isset($_REQUEST['active']) && $_REQUEST['active'] === 'on') ? 'on' : 'off',
		];

		$errors = [];

		if (preg_match('/^[a-z0-9][a-z0-9._-]{1,99}$/', Prompt::code($row['code'])) !== 1) {
			$errors[] = 'код промпта: строчные латинские буквы, цифры, точка, дефис или подчёркивание';
		}

		if (trim($row['content']) === '') {
			$errors[] = 'текст промпта пуст';
		}

		$params = [];

		foreach (['system', 'model'] as $key) {

			$value = trim((string)(isset($_REQUEST[$key]) ? $_REQUEST[$key] : ''));

			if ($value !== '') {
				$params[$key] = $value;
			}

		}

		if (isset($_REQUEST['temperature']) && trim((string)$_REQUEST['temperature']) !== '') {
			$params['temperature'] = (float)$_REQUEST['temperature'];
		}

		if (isset($_REQUEST['max_tokens']) && trim((string)$_REQUEST['max_tokens']) !== '') {
			$params['max_tokens'] = (int)$_REQUEST['max_tokens'];
		}

		if (!empty($_REQUEST['provider_id'])) {
			$params['provider_id'] = (int)$_REQUEST['provider_id'];
		}

		$schemaRaw = trim((string)(isset($_REQUEST['json_schema']) ? $_REQUEST['json_schema'] : ''));

		if ($schemaRaw !== '') {

			$schema = json_decode($schemaRaw, true);

			if (!is_array($schema)) {
				$errors[] = 'схема JSON не разбирается: '.json_last_error_msg();
			}
			else {
				$params['json_schema'] = ['name' => Prompt::code($row['code']), 'schema' => $schema];
			}

		}

		if (!empty($errors)) {
			answer(['status' => 'error', 'message' => implode('. ', $errors)]);
		}

		$row['params'] = $params;

		try {

			$saved = Prompt::save($db, $sqlname, $row, (int)$identity, (int)$iduser1);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if ($saved <= 0) {
			answer(['status' => 'error', 'message' => 'Не удалось сохранить промпт']);
		}

		answer(['status' => 'ok', 'message' => 'Промпт сохранён', 'id' => $saved]);

		break;

	case 'prompt.delete':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		if ($id <= 0) {
			answer(['status' => 'error', 'message' => 'Некорректный идентификатор промпта']);
		}

		try {

			$db->query("DELETE FROM {$sqlname}neuro_prompt_rev WHERE prompt_id = ?i AND identity = ?i", $id, (int)$identity);
			$db->query("DELETE FROM {$sqlname}neuro_prompt WHERE id = ?i AND identity = ?i", $id, (int)$identity);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		answer(['status' => 'ok', 'message' => 'Промпт удалён']);

		break;

	case 'prompt.run':

		$code    = Prompt::code((string)(isset($_REQUEST['code']) ? $_REQUEST['code'] : ''));
		$tagsRaw = trim((string)(isset($_REQUEST['tags']) ? $_REQUEST['tags'] : ''));

		$tags = [];

		if ($tagsRaw !== '') {

			$tags = json_decode($tagsRaw, true);

			if (!is_array($tags)) {
				answer(['status' => 'error', 'message' => 'Переменные должны быть JSON-объектом, например {"client":"ООО Ромашка"}']);
			}

		}

		$neuro = new Neuro\Neuro($db, $sqlname, (int)$identity, $rootpath);

		$result = $neuro->ask(['prompt' => $code, 'tags' => $tags, 'timeout' => (int)$defaults['timeout']]);

		if ($result['error'] !== NULL) {
			answer(['status' => 'error', 'message' => $result['error']['code'].': '.$result['error']['message']]);
		}

		$message = $result['model'].', '.$result['latency_ms'].' мс, токены '.(int)$result['usage']['in'].' / '.(int)$result['usage']['out'].($result['retried'] ? ', был повтор по схеме' : '');

		if (!empty($result['cached'])) {

			$message = 'Ответ взят из кэша — вызов к модели не выполнялся и не оплачивался ('
				.$result['model'].', токены '.(int)$result['usage']['in'].' / '.(int)$result['usage']['out'].' — от прежнего вызова)';

		}
		elseif ($result['prompt'] !== '' && $result['prompt'] === $code) {
			$message = 'Модель '.$message;
		}

		if (!empty($result['schema_errors'])) {
			$message .= '. Схема: '.implode('; ', $result['schema_errors']);
		}

		answer([
			'status'  => 'ok',
			'message' => $message,
			'text'    => mb_substr(trim((string)$result['text']), 0, 2000),
			'json'    => $result['json'] !== NULL ? (string)json_encode($result['json'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '',
		]);

		break;

	// -----------------------------------------------------------------------
	// Очередь заданий
	//
	// Вкладка ничего не выполняет сама: она показывает состояние и позволяет
	// поставить задание / повторить / отменить. Выполняет задания воркер по
	// расписанию — вызов модели из веб-запроса снова сделал бы интерфейс
	// заложником долгого ответа сети.
	// -----------------------------------------------------------------------

	case 'prompt.queue':

		$code    = Prompt::code((string)(isset($_REQUEST['code']) ? $_REQUEST['code'] : ''));
		$tagsRaw = trim((string)(isset($_REQUEST['tags']) ? $_REQUEST['tags'] : ''));

		$tags = [];

		if ($tagsRaw !== '') {

			$tags = json_decode($tagsRaw, true);

			if (!is_array($tags)) {
				answer(['status' => 'error', 'message' => 'Переменные должны быть JSON-объектом, например {"client":"ООО Ромашка"}']);
			}

		}

		$prompt = Prompt::byCode($db, $sqlname, $code, (int)$identity);

		if (empty($prompt)) {
			answer(['status' => 'error', 'message' => 'Промпт «'.$code.'» не найден или выключен']);
		}

		// недостающие переменные ловим здесь: в фоне человек уже не увидит ошибку
		// рендера, а задание просто упадёт в очереди
		$missing = Prompt::missing((string)$prompt['content'], $tags);

		if (!empty($missing)) {
			answer(['status' => 'error', 'message' => 'В промпте не хватает переменных: '.implode(', ', $missing)]);
		}

		try {

			$id = Queue::push($db, $sqlname, [
				'code'           => $code,
				'prompt_id'      => (int)$prompt['id'],
				'prompt_version' => (int)$prompt['version'],
				'max_attempts'   => (int)$defaults['max_attempts'],
				'payload'        => ['tags' => $tags, 'notify' => true, 'req' => ['timeout' => (int)$defaults['timeout']]],
				'iduser'         => (int)$iduser1,
			], (int)$identity);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if ($id <= 0) {
			answer(['status' => 'error', 'message' => 'Не удалось поставить задание в очередь']);
		}

		answer([
			'status'  => 'ok',
			'message' => 'Задание #'.$id.' поставлено в очередь. Его выполнит воркер по расписанию планировщика; результат придёт уведомлением.',
			'id'      => $id,
		]);

		break;

	case 'queue.list':

		$status = (string)(isset($_REQUEST['status']) ? $_REQUEST['status'] : '');
		$limit  = (int)(isset($_REQUEST['limit']) ? $_REQUEST['limit'] : 50);

		$list = [];

		foreach (Queue::listTasks($db, $sqlname, (int)$identity, $status, $limit) as $row) {

			$iduser = (int)$row['iduser'];

			$list[] = [
				'id'          => (int)$row['id'],
				'code'        => (string)$row['code'],
				'status'      => (string)$row['status'],
				'statusTitle' => Queue::statusTitle((string)$row['status']),
				'datum'       => (string)$row['datum'],
				'run_after'   => (string)$row['run_after'],
				'finished_at' => (string)$row['finished_at'],
				'attempts'    => (int)$row['attempts'],
				'maxAttempts' => (int)$row['max_attempts'],
				'error'       => mb_substr(trim((string)$row['error']), 0, 200),
				'errorCode'   => (string)$row['error_code'],
				'model'       => (string)$row['model'],
				'tokensIn'    => (int)$row['tokens_in'],
				'tokensOut'   => (int)$row['tokens_out'],
				'latency'     => (int)$row['latency_ms'],
				'iduser'      => $iduser,
				'userName'    => $iduser > 0 ? (string)current_user($iduser) : 'система',
				'ref'         => (string)$row['ref'],
				'refid'       => (int)$row['refid'],
				'canRetry'    => in_array((string)$row['status'], ['error', 'cancel'], true),
				'canCancel'   => (string)$row['status'] === 'new',
			];

		}

		answer(['status' => 'ok', 'list' => $list, 'stats' => Queue::stats($db, $sqlname, (int)$identity)]);

		break;

	case 'queue.task':

		$id  = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
		$row = Queue::byId($db, $sqlname, $id, (int)$identity);

		if (empty($row)) {
			answer(['status' => 'error', 'message' => 'Задание не найдено']);
		}

		$payload = json_decode((string)$row['payload'], true);
		$json    = trim((string)$row['result_json']);

		if ($json !== '') {

			$decoded = json_decode($json, true);

			if ($decoded !== NULL) {
				$json = (string)json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
			}

		}

		ob_start();

		?>
		<DIV class="zagolovok"><B>Задание #<?php echo (int)$row['id'] ?></B></DIV>

		<div class="fs-09 gray mb10">
			<?php echo htmlspecialchars((string)$row['code'], ENT_QUOTES, 'UTF-8') ?>
			· <?php echo htmlspecialchars(Queue::statusTitle((string)$row['status']), ENT_QUOTES, 'UTF-8') ?>
			· попыток <?php echo (int)$row['attempts'] ?> из <?php echo (int)$row['max_attempts'] ?>
		</div>

		<div class="nc-card">

			<div class="fs-09">
				создано: <?php echo htmlspecialchars((string)$row['datum'], ENT_QUOTES, 'UTF-8') ?><br>
				не раньше: <?php echo htmlspecialchars((string)$row['run_after'], ENT_QUOTES, 'UTF-8') ?><br>
				завершено: <?php echo htmlspecialchars((string)$row['finished_at'], ENT_QUOTES, 'UTF-8') ?><br>
				модель: <?php echo htmlspecialchars((string)$row['model'], ENT_QUOTES, 'UTF-8') ?>,
				токены <?php echo (int)$row['tokens_in'] ?> / <?php echo (int)$row['tokens_out'] ?>,
				<?php echo (int)$row['latency_ms'] ?> мс
			</div>

			<?php if (trim((string)$row['error']) !== '') { ?>

				<div class="warning p10 mt10 fs-09">
					<b>Ошибка<?php echo trim((string)$row['error_code']) !== '' ? ' ('.htmlspecialchars((string)$row['error_code'], ENT_QUOTES, 'UTF-8').')' : '' ?>:</b>
					<?php echo htmlspecialchars((string)$row['error'], ENT_QUOTES, 'UTF-8') ?>
				</div>

			<?php } ?>

		</div>

		<?php if (trim((string)$row['result']) !== '') { ?>

			<div class="nc-card">
				<div class="Bold mb5">Ответ модели</div>
				<div class="fs-11" style="white-space: pre-wrap;"><?php echo htmlspecialchars((string)$row['result'], ENT_QUOTES, 'UTF-8') ?></div>
			</div>

		<?php } ?>

		<?php if ($json !== '') { ?>

			<div class="nc-card">
				<div class="Bold mb5">Разобранный JSON</div>
				<pre class="fs-09" style="white-space: pre-wrap;"><?php echo htmlspecialchars($json, ENT_QUOTES, 'UTF-8') ?></pre>
			</div>

		<?php } ?>

		<?php if (!empty($payload)) { ?>

			<div class="nc-card">
				<div class="Bold mb5">Переменные задания</div>
				<div class="fs-09 gray mb5">Данные CRM, которые подставлялись в промпт. Видны только в карточке задания.</div>
				<pre class="fs-09" style="white-space: pre-wrap;"><?php echo htmlspecialchars((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
			</div>

		<?php } elseif ((string)$row['status'] === 'ok') { ?>

			<div class="fs-09 gray mt10">
				Переменные задания не сохранены: после успешного выполнения ядро их удаляет,
				чтобы данные клиентов не оставались в очереди. Включается настройкой
				«Сохранять переменные задания после выполнения».
			</div>

		<?php } ?>

		<div class="text-center mt10">
			<?php if (in_array((string)$row['status'], ['error', 'cancel'], true)) { ?>
				<a href="javascript:void(0)" onclick="Neuro.retryTask(<?php echo (int)$row['id'] ?>)" class="button rounded-r10">Повторить задание</a>
			<?php } ?>
		</div>
		<?php

		answer(['status' => 'ok', 'html' => (string)ob_get_clean()]);

		break;

	case 'queue.retry':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		try {

			$done = Queue::retry($db, $sqlname, $id, (int)$identity);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if (!$done) {
			answer(['status' => 'error', 'message' => 'Повторить можно только задание с ошибкой или отменённое, и только пока оно не выполняется']);
		}

		Queue::log($db, $sqlname, $id, 'info', 'Задание поставлено в очередь повторно администратором', ['iduser' => (int)$iduser1], (int)$identity);

		answer(['status' => 'ok', 'message' => 'Задание #'.$id.' снова в очереди']);

		break;

	case 'queue.cancel':

		$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

		try {

			$done = Queue::cancel($db, $sqlname, $id, (int)$identity);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if (!$done) {
			answer(['status' => 'error', 'message' => 'Отменить можно только задание, которое ещё не начало выполняться']);
		}

		Queue::log($db, $sqlname, $id, 'info', 'Задание отменено администратором', ['iduser' => (int)$iduser1], (int)$identity);

		answer(['status' => 'ok', 'message' => 'Задание #'.$id.' отменено']);

		break;

	// -----------------------------------------------------------------------
	// Расход и кэш
	//
	// Отчёт показывает то, что записано фасадом: каждый вызов, вернувший токены.
	// Стоимость считается только из прайсов подключения — если их не задали,
	// показываются точные токены, а не выдуманная сумма.
	// -----------------------------------------------------------------------

	case 'usage.report':

		$to   = trim((string)(isset($_REQUEST['to']) ? $_REQUEST['to'] : ''));
		$from = trim((string)(isset($_REQUEST['from']) ? $_REQUEST['from'] : ''));

		if ($to === '') {
			$to = date('Y-m-d');
		}

		if ($from === '') {
			$from = date('Y-m-d', strtotime($to.' -6 days'));
		}

		$report = Usage::report($db, $sqlname, (int)$identity, $from, $to);

		$users = [];

		foreach ($report['users'] as $iduser => $row) {
			$users[(int)$iduser] = ['title' => (int)$iduser > 0 ? (string)current_user((int)$iduser) : 'система', 'row' => $row];
		}

		$codes = [];

		foreach ($report['codes'] as $code => $row) {

			$average = Usage::average($db, $sqlname, (int)$identity, (string)$code);

			$codes[] = ['code' => (string)$code, 'row' => $row, 'average' => $average];

		}

		$models = [];

		foreach ($report['models'] as $model => $row) {
			$models[] = ['model' => (string)$model, 'row' => $row];
		}

		answer([
			'status'  => 'ok',
			'from'    => $report['from'],
			'to'      => $report['to'],
			'total'   => $report['total'],
			'days'    => $report['days'],
			'users'   => $users,
			'codes'   => $codes,
			'models'  => $models,
			'cache'   => Cache::stats($db, $sqlname, (int)$identity),
			'cacheTtl' => Cache::ttl((array)customSettings('neuroCore')),
		]);

		break;

	case 'cache.clear':

		try {

			$removed = Cache::clear($db, $sqlname, (int)$identity);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if ($removed > 0) {
			Log::write($db, $sqlname, 0, 'info', 'Кэш ответов очищен: удалено ответов — '.$removed, ['iduser' => (int)$iduser1], (int)$identity);
		}

		answer(['status' => 'ok', 'message' => $removed > 0 ? 'Кэш очищен: удалено ответов — '.$removed : 'Кэш уже пуст']);

		break;

	// -----------------------------------------------------------------------
	// Настройки
	//
	// Отдачи формы настроек здесь нет: она рендерится сразу в index.php, чтобы
	// открытие страницы не делало лишний запрос и не зависело от AJAX.
	// -----------------------------------------------------------------------

	// -----------------------------------------------------------------------
	// Журнал
	//
	// В журнале нет текстов промптов и ответов модели: только метаданные. Полный
	// ответ задания остаётся в его карточке на вкладке «Очередь».
	// -----------------------------------------------------------------------

	case 'log.list':

		$list = Log::listRows($db, $sqlname, (int)$identity, [
			'level'  => isset($_REQUEST['level']) ? (string)$_REQUEST['level'] : '',
			'period' => isset($_REQUEST['period']) ? (int)$_REQUEST['period'] : 7,
			'tid'    => isset($_REQUEST['tid']) ? (int)$_REQUEST['tid'] : 0,
			'limit'  => isset($_REQUEST['limit']) ? (int)$_REQUEST['limit'] : 100,
		]);

		answer([
			'status' => 'ok',
			'rows'   => $list['rows'],
			'total'  => $list['total'],
			'stats'  => Log::stats($db, $sqlname, (int)$identity, (int)$list['filters']['period']),
			'filters' => $list['filters'],
			'retention' => Log::retention((array)customSettings('neuroCore')),
		]);

		break;

	case 'queue.purge':

		$retention = Queue::retention((array)customSettings('neuroCore'));

		if ($retention <= 0) {

			answer(['status' => 'error', 'message' => 'Срок хранения заданий — «хранить бессрочно»: удалять нечего. Измените срок в настройках.']);

		}

		try {

			$removed = Queue::purge($db, $sqlname, (int)$identity, $retention, Queue::PURGE_LIMIT);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if ($removed > 0) {
			Log::write($db, $sqlname, 0, 'info', 'Удалены выполненные задания старше срока: '.$removed, ['retention' => $retention, 'iduser' => (int)$iduser1], (int)$identity);
		}

		$left = Queue::purgeStats($db, $sqlname, (int)$identity, $retention);

		answer([
			'status'  => 'ok',
			'message' => $removed > 0
				? 'Удалено заданий: '.$removed.' (выполненных более '.$retention.' дн. назад). Осталось убрать: '.$left['count'].' — нажмите ещё раз, уборка идёт порциями'
				: 'Выполненных заданий старше '.$retention.' дн. нет',
		]);

		break;

	case 'log.purge':

		$retention = Log::retention((array)customSettings('neuroCore'));

		if ($retention <= 0) {

			answer(['status' => 'error', 'message' => 'Срок хранения журнала — «хранить бессрочно»: удалять нечего. Измените срок в настройках.']);

		}

		try {

			$removed = Log::purge($db, $sqlname, (int)$identity, $retention, Log::PURGE_LIMIT);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		if ($removed > 0) {
			Log::write($db, $sqlname, 0, 'info', 'Удалены старые записи журнала: '.$removed, ['retention' => $retention, 'iduser' => (int)$iduser1], (int)$identity);
		}

		answer([
			'status'  => 'ok',
			'message' => $removed > 0
				? 'Удалено записей: '.$removed.' (старше '.$retention.' дн.). Если журнал большой, нажмите ещё раз — уборка идёт порциями'
				: 'Записей старше '.$retention.' дн. нет',
		]);

		break;

	case 'settings.save':

		// настройки читаются и дополняются, а не собираются заново: в них хранится
		// в том числе результат установки схемы (INSTALL_ERROR), и терять его при
		// сохранении несвязанных полей нельзя
		$settings = (array)customSettings('neuroCore');

		$settings['TIMEOUT']       = max(5, min(300, (int)(isset($_REQUEST['TIMEOUT']) ? $_REQUEST['TIMEOUT'] : 30)));
		$settings['MAX_ATTEMPTS']  = max(1, min(10, (int)(isset($_REQUEST['MAX_ATTEMPTS']) ? $_REQUEST['MAX_ATTEMPTS'] : 3)));
		$settings['STORE_PAYLOAD'] = (isset($_REQUEST['STORE_PAYLOAD']) && $_REQUEST['STORE_PAYLOAD'] === 'on') ? 'on' : 'off';

		// кэш: 0 — выключен (это осмысленное значение, а не «не задано»)
		$settings['CACHE_TTL'] = max(0, min(Neuro\Cache::MAX_TTL, (int)(isset($_REQUEST['CACHE_TTL']) ? $_REQUEST['CACHE_TTL'] : Neuro\Cache::DEFAULT_TTL)));

		// журнал: 0 — хранить бессрочно; уборку делает воркер при каждом прогоне
		$settings['LOG_RETENTION'] = max(0, min(365, (int)(isset($_REQUEST['LOG_RETENTION']) ? $_REQUEST['LOG_RETENTION'] : Neuro\Log::DEFAULT_RETENTION)));

		// задания: тот же срок, но для выполненных (в них данные CRM и ответ модели)
		$settings['TASK_RETENTION'] = max(0, min(365, (int)(isset($_REQUEST['TASK_RETENTION']) ? $_REQUEST['TASK_RETENTION'] : Neuro\Queue::DEFAULT_RETENTION)));

		// оповещения: порог очереди (0 — не оповещать) и повтор в минутах
		$settings[Neuro\Alerts::SETTING_LIMIT]  = max(0, min(10000, (int)(isset($_REQUEST['QUEUE_ALERT_LIMIT']) ? $_REQUEST['QUEUE_ALERT_LIMIT'] : Neuro\Alerts::DEFAULT_LIMIT)));
		$settings[Neuro\Alerts::SETTING_REPEAT] = max(0, min(10080, (int)(isset($_REQUEST['ALERT_REPEAT_MINUTES']) ? $_REQUEST['ALERT_REPEAT_MINUTES'] : Neuro\Alerts::DEFAULT_REPEAT)));

		// путь до PHP здесь не хранится: им управляет плагин «Планировщик заданий»
		unset($settings['PHPBIN']);

		try {

			customSettings('neuroCore', 'put', ['params' => $settings]);

		}
		catch (Exception $e) {

			answer(['status' => 'error', 'message' => dbErrorText($e)]);

		}

		answer(['status' => 'ok', 'message' => 'Настройки сохранены']);

		break;

	default:

		answer(['status' => 'error', 'message' => 'Неизвестное действие']);

}
