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
 * Схема БД Нейроядра.
 *
 * DDL живёт в одном месте и является чистой функцией от префикса таблиц, поэтому
 * проверяется тестом без базы (.dsh/tests/neuro_schema_test.php). Установка плагина
 * и его обновление используют один и тот же набор операторов.
 *
 * Class Schema
 *
 * @package Neuro
 */
class Schema {

	/**
	 * Таблицы ядра (без префикса и без neuro_).
	 */
	public const TABLES = [
		'provider',
		'prompt',
		'prompt_rev',
		'task',
		'usage',
		'log',
		'cache',
	];

	/**
	 * Список таблиц ядра.
	 *
	 * @return array
	 */
	public static function tables(): array {
		return self::TABLES;
	}

	/**
	 * Полное имя таблицы.
	 *
	 * @param string $sqlname - префикс таблиц CRM
	 * @param string $table   - ключ таблицы ядра
	 *
	 * @return string
	 */
	public static function tableName(string $sqlname, string $table): string {
		return $sqlname.'neuro_'.$table;
	}

	/**
	 * Операторы создания таблиц.
	 *
	 * Важно: у провайдера НЕТ колонки со значением ключа — только имя переменной
	 * окружения (api_key_env). Хранить ключ в базе физически негде.
	 *
	 * @param string $sqlname - префикс таблиц CRM
	 *
	 * @return array - [ключ таблицы => SQL]
	 */
	public static function statements(string $sqlname): array {

		$p = $sqlname.'neuro_';

		$statements = [];

		$statements['provider'] = "
			CREATE TABLE `{$p}provider` (
				`id`          INT(20) NOT NULL AUTO_INCREMENT,
				`name`        VARCHAR(100) NOT NULL COMMENT 'человекочитаемое название: OpenAI, DeepSeek, ...',
				`code`        VARCHAR(50) NOT NULL DEFAULT 'openai' COMMENT 'openai - универсальный OpenAI-совместимый, gemini',
				`base_url`    VARCHAR(255) NULL DEFAULT NULL COMMENT 'напр. https://api.deepseek.com',
				`model`       VARCHAR(100) NULL DEFAULT NULL COMMENT 'модель по умолчанию',
				`api_key_env` VARCHAR(100) NULL DEFAULT NULL COMMENT 'ИМЯ переменной окружения с ключом, не сам ключ',
				`key_source`  VARCHAR(10) NULL DEFAULT 'env' COMMENT 'env - переменная окружения, db - ключ сохранён в CRM (зашифрован)',
				`api_key_enc` TEXT NULL DEFAULT NULL COMMENT 'ключ API, зашифрованный AES-256-GCM мастер-ключом из plugins/neuroCore/data/secret.key',
				`params`      TEXT NULL DEFAULT NULL COMMENT 'JSON: temperature, max_tokens, timeout, price_in, price_out',
				`pd_allowed`  CHAR(5) NULL DEFAULT 'yes' COMMENT 'разрешена ли отправка персональных данных',
				`active`      CHAR(5) NULL DEFAULT 'on' COMMENT 'активность',
				`date_edit`   TIMESTAMP NULL DEFAULT NULL,
				`datum`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`identity`    INT(20) NULL DEFAULT '1' COMMENT 'идентификатор',
				PRIMARY KEY (`id`),
				UNIQUE KEY `name` (`name`,`identity`),
				KEY `active` (`active`,`identity`)
			)
			COMMENT='Подключения к нейросетевым API'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		$statements['prompt'] = "
			CREATE TABLE `{$p}prompt` (
				`id`        INT(20) NOT NULL AUTO_INCREMENT,
				`code`      VARCHAR(100) NOT NULL COMMENT 'стабильный ключ: deal.summary, email.chain',
				`name`      VARCHAR(200) NULL DEFAULT NULL,
				`version`   INT(10) NULL DEFAULT '1',
				`content`   TEXT NULL DEFAULT NULL COMMENT 'шаблон mustache; переменные подставляются из payload',
				`params`    TEXT NULL DEFAULT NULL COMMENT 'JSON: provider_id, model, temperature, max_tokens, cache_ttl',
				`owner`     VARCHAR(100) NULL DEFAULT NULL COMMENT 'пакет плагина, которому принадлежит промпт',
				`source`    VARCHAR(10) NULL DEFAULT 'admin' COMMENT 'plugin - принесён плагином, admin - создан или изменён вручную',
				`plugin_content` TEXT NULL DEFAULT NULL COMMENT 'текст, который приносит плагин (видно расхождение с правкой администратора)',
				`active`    CHAR(5) NULL DEFAULT 'on',
				`iduser`    INT(10) NULL DEFAULT NULL COMMENT 'автор изменения',
				`date_edit` TIMESTAMP NULL DEFAULT NULL,
				`datum`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`identity`  INT(20) NULL DEFAULT '1',
				PRIMARY KEY (`id`),
				UNIQUE KEY `code` (`code`,`identity`)
			)
			COMMENT='Реестр промптов'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		$statements['prompt_rev'] = "
			CREATE TABLE `{$p}prompt_rev` (
				`id`        INT(20) NOT NULL AUTO_INCREMENT,
				`prompt_id` INT(20) NOT NULL COMMENT 'prompt.id',
				`version`   INT(10) NULL DEFAULT NULL,
				`content`   TEXT NULL DEFAULT NULL,
				`params`    TEXT NULL DEFAULT NULL,
				`iduser`    INT(10) NULL DEFAULT NULL,
				`datum`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`identity`  INT(20) NULL DEFAULT '1',
				PRIMARY KEY (`id`),
				KEY `prompt` (`prompt_id`,`version`)
			)
			COMMENT='Версии промптов'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		$statements['task'] = "
			CREATE TABLE `{$p}task` (
				`id`             INT(20) NOT NULL AUTO_INCREMENT,
				`code`           VARCHAR(100) NOT NULL COMMENT 'тип задачи = prompt.code',
				`prompt_id`      INT(20) NULL DEFAULT NULL,
				`prompt_version` INT(10) NULL DEFAULT NULL,
				`provider_id`    INT(20) NULL DEFAULT NULL,
				`status`         VARCHAR(10) NOT NULL DEFAULT 'new' COMMENT 'new|run|ok|error|cancel',
				`priority`       INT(5) NULL DEFAULT '5' COMMENT '1..9, меньше - важнее',
				`ref`            VARCHAR(50) NULL DEFAULT NULL COMMENT 'tip сущности CRM',
				`refid`          INT(20) NULL DEFAULT NULL COMMENT 'id сущности CRM',
				`payload`        TEXT NULL DEFAULT NULL COMMENT 'JSON: переменные для шаблона промпта',
				`result`         MEDIUMTEXT NULL DEFAULT NULL COMMENT 'ответ модели',
				`result_json`    TEXT NULL DEFAULT NULL COMMENT 'разобранный JSON',
				`error`          TEXT NULL DEFAULT NULL COMMENT 'текст ошибки для человека',
				`error_code`     VARCHAR(50) NULL DEFAULT NULL COMMENT 'http_429|http_401|timeout|parse|safety|empty',
				`attempts`       INT(3) NULL DEFAULT '0',
				`max_attempts`   INT(3) NULL DEFAULT '3',
				`run_after`      DATETIME NULL DEFAULT NULL COMMENT 'не раньше этого времени (backoff)',
				`locked_by`      VARCHAR(100) NULL DEFAULT NULL COMMENT 'pid+host воркера',
				`locked_at`      DATETIME NULL DEFAULT NULL,
				`finished_at`    DATETIME NULL DEFAULT NULL,
				`request_hash`   CHAR(64) NULL DEFAULT NULL COMMENT 'идемпотентность и кэш',
				`model`          VARCHAR(100) NULL DEFAULT NULL COMMENT 'фактически использованная модель',
				`tokens_in`      INT(10) NULL DEFAULT NULL,
				`tokens_out`     INT(10) NULL DEFAULT NULL,
				`cost`           DECIMAL(12,6) NULL DEFAULT NULL COMMENT 'только если заданы price_in/price_out',
				`latency_ms`     INT(10) NULL DEFAULT NULL,
				`iduser`         INT(10) NULL DEFAULT NULL COMMENT 'кто инициировал',
				`identity`       INT(20) NULL DEFAULT '1',
				`datum`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `claim` (`status`,`run_after`,`priority`),
				KEY `ref` (`ref`,`refid`),
				KEY `hash` (`request_hash`),
				KEY `iduser` (`iduser`,`datum`)
			)
			COMMENT='Очередь заданий к нейросети'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		$statements['usage'] = "
			CREATE TABLE `{$p}usage` (
				`id`         INT(20) NOT NULL AUTO_INCREMENT,
				`datum`      DATE NOT NULL COMMENT 'день расхода',
				`iduser`     INT(10) NULL DEFAULT NULL,
				`code`       VARCHAR(100) NULL DEFAULT NULL COMMENT 'тип задачи',
				`model`      VARCHAR(100) NULL DEFAULT '' COMMENT 'модель, которой отвечали',
				`calls`      INT(10) NULL DEFAULT '0',
				`tokens_in`  INT(20) NULL DEFAULT '0',
				`tokens_out` INT(20) NULL DEFAULT '0',
				`cost`       DECIMAL(14,6) NULL DEFAULT NULL,
				`currency`   VARCHAR(8) NULL DEFAULT NULL COMMENT 'валюта прайса подключения: ₽, $ и т.п.',
				`identity`   INT(20) NULL DEFAULT '1',
				PRIMARY KEY (`id`),
				UNIQUE KEY `day` (`datum`,`iduser`,`code`,`model`,`identity`)
			)
			COMMENT='Расход по дням (отчётность, без ограничений)'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		$statements['log'] = "
			CREATE TABLE `{$p}log` (
				`id`       INT(20) NOT NULL AUTO_INCREMENT,
				`tid`      INT(20) NULL DEFAULT NULL COMMENT 'task.id',
				`level`    VARCHAR(10) NULL DEFAULT 'info' COMMENT 'debug|info|warn|error',
				`message`  TEXT NULL DEFAULT NULL,
				`context`  TEXT NULL DEFAULT NULL COMMENT 'JSON',
				`identity` INT(20) NULL DEFAULT '1',
				`datum`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				KEY `task` (`tid`),
				KEY `level` (`level`,`datum`),
				KEY `datum` (`datum`)
			)
			COMMENT='Журнал работы Нейроядра'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		$statements['cache'] = "
			CREATE TABLE `{$p}cache` (
				`id`         INT(20) NOT NULL AUTO_INCREMENT,
				`hash`       CHAR(64) NOT NULL COMMENT 'отпечаток запроса: промпт, версия, модель, переменные',
				`code`       VARCHAR(100) NULL DEFAULT NULL COMMENT 'prompt.code',
				`model`      VARCHAR(100) NULL DEFAULT NULL COMMENT 'модель, которая ответила',
				`answer`     MEDIUMTEXT NULL DEFAULT NULL COMMENT 'ответ модели',
				`answer_json` TEXT NULL DEFAULT NULL COMMENT 'разобранный JSON',
				`tokens_in`  INT(10) NULL DEFAULT NULL,
				`tokens_out` INT(10) NULL DEFAULT NULL,
				`expires_at` DATETIME NULL DEFAULT NULL COMMENT 'до какого времени ответ годен',
				`iduser`     INT(10) NULL DEFAULT NULL,
				`identity`   INT(20) NULL DEFAULT '1',
				`datum`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				UNIQUE KEY `hash` (`hash`),
				KEY `expires` (`expires_at`)
			)
			COMMENT='Кэш одинаковых запросов к нейросети'
			COLLATE 'utf8_general_ci'
			ENGINE=InnoDB
		";

		return $statements;
	}

	/**
	 * Создаёт отсутствующие таблицы. Существующие не трогает (идемпотентно).
	 *
	 * @param object $db       - SafeMySQL
	 * @param string $database - имя схемы БД
	 * @param string $sqlname  - префикс таблиц CRM
	 *
	 * @return array - отчёт по каждой таблице
	 */
	public static function install($db, string $database, string $sqlname): array {

		$result = [];

		foreach (self::statements($sqlname) as $key => $sql) {

			$table = self::tableName($sqlname, $key);

			// в try попадает и проверка существования: если она упадёт (например,
			// нет прав на INFORMATION_SCHEMA), активация не должна обрываться на
			// середине — иначе плагин остаётся включённым без части таблиц
			try {

				$exists = (int)$db->getOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = ?s AND TABLE_NAME = ?s", $database, $table);

				if ($exists > 0) {
					$result[] = ['table' => $table, 'status' => 'exists'];
					continue;
				}

				$db->query($sql);

				$result[] = ['table' => $table, 'status' => 'created'];

			}
			catch (\Exception $e) {

				$result[] = ['table' => $table, 'status' => 'error', 'message' => $e->getMessage()];

			}

		}

		return $result;
	}

	/**
	 * Аддитивные миграции: колонки, которых может не быть в уже созданных таблицах.
	 *
	 * Таблицы, созданные предыдущими версиями плагина, не пересоздаются, поэтому при
	 * изменении схемы колонки нужно достраивать явно — так же сделано в эталонном
	 * плагине neuroEmailSender (SHOW COLUMNS + ALTER TABLE).
	 *
	 * @param string $sqlname
	 *
	 * @return array - [таблица => [колонка => SQL]]
	 */
	public static function migrations(string $sqlname): array {

		$p = $sqlname.'neuro_';

		return [
			'provider' => [
				'key_source'  => "ALTER TABLE `{$p}provider` ADD COLUMN `key_source` VARCHAR(10) NULL DEFAULT 'env' COMMENT 'env - переменная окружения, db - ключ в CRM' AFTER `api_key_env`",
				'api_key_enc' => "ALTER TABLE `{$p}provider` ADD COLUMN `api_key_enc` TEXT NULL DEFAULT NULL COMMENT 'ключ API, зашифрованный мастер-ключом' AFTER `key_source`",
			],
			'prompt'   => [
				'owner'          => "ALTER TABLE `{$p}prompt` ADD COLUMN `owner` VARCHAR(100) NULL DEFAULT NULL COMMENT 'пакет плагина, которому принадлежит промпт' AFTER `params`",
				'source'         => "ALTER TABLE `{$p}prompt` ADD COLUMN `source` VARCHAR(10) NULL DEFAULT 'admin' COMMENT 'plugin - принесён плагином, admin - правка администратора' AFTER `owner`",
				'plugin_content' => "ALTER TABLE `{$p}prompt` ADD COLUMN `plugin_content` TEXT NULL DEFAULT NULL COMMENT 'текст, который приносит плагин' AFTER `source`",
			],
			'usage'    => [
				'model'    => "ALTER TABLE `{$p}usage` ADD COLUMN `model` VARCHAR(100) NULL DEFAULT '' COMMENT 'модель, которой отвечали' AFTER `code`",
				'currency' => "ALTER TABLE `{$p}usage` ADD COLUMN `currency` VARCHAR(8) NULL DEFAULT NULL COMMENT 'валюта прайса подключения: ₽, \$ и т.п.' AFTER `cost`",
			],
		];

	}

	/**
	 * Миграции индексов: меняем ключ, а не только колонки.
	 *
	 * Учёт расхода сначала группировался по дню, сотруднику и типу задачи. Когда
	 * подключений стало несколько, понадобилась ещё и модель: у разных моделей
	 * разная цена, и без этого измерения отчёт не отвечает на вопрос «куда уходят
	 * деньги». Ключ меняется целиком — частично заменить его MySQL не даёт.
	 *
	 * @param string $sqlname
	 *
	 * @return array - [ключ => [проверка => [имя индекса, колонка], sql]]
	 */
	public static function indexMigrations(string $sqlname): array {

		$p = $sqlname.'neuro_';

		return [
			'usage.day' => [
				'index'  => 'day',
				'column' => 'model',
				'sql'    => "ALTER TABLE `{$p}usage` DROP INDEX `day`, ADD UNIQUE KEY `day` (`datum`,`iduser`,`code`,`model`,`identity`)",
			],
			// Ретенция журнала удаляет записи по дате, а не по уровню: без индекса
			// по `datum` уборка сканирует всю таблицу на каждом прогоне воркера
			'log.datum' => [
				'index'  => 'datum',
				'column' => 'datum',
				'sql'    => "ALTER TABLE `{$p}log` ADD KEY `datum` (`datum`)",
			],
			// Уборка выполненных заданий идёт по сроку завершения, а признак
			// «воркер работает» (оповещение о вставшей очереди) читает максимум
			// `finished_at`: без индекса оба запроса сканируют таблицу
			'task.finished' => [
				'index'  => 'finished',
				'column' => 'finished_at',
				'sql'    => "ALTER TABLE `{$p}task` ADD KEY `finished` (`identity`,`status`,`finished_at`)",
			],
		];

	}

	/**
	 * Достраивает отсутствующие колонки. Существующие данные не трогает.
	 *
	 * @param object $db
	 * @param string $sqlname
	 *
	 * @return array - [таблица.колонка => added|exists|error]
	 */
	public static function migrate($db, string $sqlname): array {

		$result = [];

		foreach (self::migrations($sqlname) as $key => $columns) {

			$table = self::tableName($sqlname, $key);

			foreach ($columns as $column => $sql) {

				$target = $table.'.'.$column;

				try {

					$exists = (int)$db->getOne("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND TABLE_NAME = ?s AND COLUMN_NAME = ?s", $table, $column);

					if ($exists > 0) {
						$result[$target] = 'exists';
						continue;
					}

					$db->query($sql);

					$result[$target] = 'added';

				}
				catch (\Exception $e) {

					$result[$target] = 'error: '.$e->getMessage();

				}

			}

		}

		// индексы — после колонок: ключ может ссылаться на только что добавленную
		foreach (self::indexMigrations($sqlname) as $target => $migration) {

			$table = self::tableName($sqlname, strstr($target, '.', true));

			try {

				$has = (int)$db->getOne(
					"SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = DATABASE() AND TABLE_NAME = ?s AND INDEX_NAME = ?s AND COLUMN_NAME = ?s",
					$table,
					(string)$migration['index'],
					(string)$migration['column']
				);

				if ($has > 0) {
					$result[$target] = 'exists';
					continue;
				}

				$db->query((string)$migration['sql']);

				$result[$target] = 'added';

			}
			catch (\Exception $e) {

				$result[$target] = 'error: '.$e->getMessage();

			}

		}

		return $result;

	}

	/**
	 * Удаляет таблицы ядра (используется при удалении плагина).
	 *
	 * @param object $db
	 * @param string $sqlname
	 *
	 * @return array
	 */
	public static function uninstall($db, string $sqlname): array {

		$result = [];

		// в обратном порядке: сначала зависимые
		$order = ['cache', 'log', 'usage', 'task', 'prompt_rev', 'prompt', 'provider'];

		foreach ($order as $key) {

			$table = self::tableName($sqlname, $key);

			try {
				$db->query("DROP TABLE IF EXISTS `{$table}`");
				$result[] = $table;
			}
			catch (\Exception $e) {
				// таблицы может не быть — это не ошибка удаления
			}

		}

		return $result;
	}

}
