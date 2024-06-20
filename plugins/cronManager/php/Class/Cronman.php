<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */

namespace Cronman;

use SafeMySQL;
use Cron;

/**
 * Класс для управления заданиями для Планировщика
 *
 * Class Cronman
 *
 * @package     Cronman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (24/03/2020)
 */
class Cronman {

	public const PERIODS = [
		"once"         => "Разово",
		"everyminutes" => "Каждую минуту",
		"everyhour"    => "Каждый час",
		"everyday"     => "Ежедневно",
		"everyweek"    => "Еженедельно",
		"everymonth"   => "Ежемесячно",
		"everyyear"    => "Ежегодно",
		"expert"       => "Экспертный режим",
	];
	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone, $rootpath;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Настройки логики
	 *
	 * @var array
	 */
	public $settings = [];

	/**
	 * Расширенный ответ
	 *
	 * @var array
	 */
	public $response = [];

	/**
	 * Фильтры для списка чатов
	 *
	 * @var array
	 */
	public $filters = [];
	public $error;

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 */
	public function __construct() {

		$rootpath = realpath( __DIR__.'/../../../../' );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> rootpath = $rootpath;
		$this -> identity = $GLOBALS['identity'] ?? 1;
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> settings = customSettings( "cronManager", "get" );

		$this -> db = new SafeMySQL( $this -> opts );

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		$this -> params = ["silence" => false];

		date_default_timezone_set( $this -> tmzone );

		createDir( $rootpath."/files/".($this -> fpath)."cron/" );

	}

	/**
	 * Список заданий
	 *
	 * @return array
	 */
	public function getTaskList(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$list = [];

		$data = $db -> getAll( "SELECT * FROM {$sqlname}cronmanager WHERE identity = '$identity' ORDER BY name" );
		foreach ( $data as $da ) {

			$this -> compileTaskString( $da['id'] );

			$list[] = [
				"id"         => $da['id'],
				"uid"        => $da['uid'],
				"datum"      => $da['datum'],
				"name"       => $da['name'],
				"parent"     => $da['parent'],
				"parentname" => strtr( $da['parent'], self::PERIODS ),
				"task"       => $this -> response['period'],
				"bin"        => $da['bin'],
				"script"     => $da['script'],
				"cmd"        => $this -> compileTaskString( $da['id'] ),
				"next"       => $da['period'] ? $this -> getNextTime( $da['id'] ) : '',
				"active"     => $da['active'] == 'on' ? true : NULL,
				"identity"   => $da['identity']
			];

		}

		return $list;

	}

	/**
	 * Данные задачи
	 *
	 * @param $id
	 * @return array
	 */
	public function getTask($id): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$task = [];

		//todo: добавить преобразование дат в задание

		if ( $id > 0 ) {

			$da = $db -> getRow( "SELECT * FROM {$sqlname}cronmanager WHERE id = '$id' AND identity = '$identity'" );
			//print $db -> lastQuery();

			//print_r($da);

			$task = [
				"id"     => $da['id'],
				"uid"    => $da['uid'],
				"datum"  => $da['datum'],
				"name"   => $da['name'],
				"parent" => $da['parent'],
				"task"   => $da['task'],
				"bin"    => $da['bin'],
				"script" => $da['script'],
				"period" => json_decode( $da['period'], true ),
				"active" => $da['active'] == 'on' ? true : NULL
			];

		}

		return $task;

	}

	/**
	 * Редактирование здания
	 *
	 * @param int $id
	 * @param array $params
	 *                  - name - название задания
	 *                  - parent - стандартная периодичность: см. $cron::PERIODS;
	 *                  - bin - исполняемая программа
	 *                  - script - исполняемый скрипт, где {{DIR}} будет заменен на абсолютный путь до папки с CRM, или
	 *                  указываем свой путь или https-адрес
	 *                  - period - массив с указанием периодичности
	 *                  - i - минуты
	 *                  - h - часы
	 *                  - d - дни
	 *                  - m - месяцы
	 *                  - cmd - для экспертного режима, например 0 1 * * *
	 *                  - activeактивность задания - on|off
	 *
	 * @return int
	 */
	public function setTask(int $id = 0, array $params = []): int {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ( stripos( $params['script'], '{{DIR}}' ) !== false && stripos( $params['bin'], 'php' ) !== false ) {
			$params['script'] = str_replace( "{{DIR}}", $this -> rootpath, $params['script'] );
		}

		$d = [
			"id"       => $params['id'],
			"uid"      => $params['uid'],
			"datum"    => $params['datum'] ?? current_datumtime(),
			"name"     => $params['name'],
			"parent"   => $params['parent'],
			"task"     => $params['task'],
			"bin"      => $params['bin'],
			"script"   => $params['script'],
			"period"   => $params['parent'] != 'once' ? (is_array( $params['period'] ) ? json_encode( $params['period'], true ) : $params['period']) : NULL,
			"active"   => $params['active'] ?? 'off',
			'identity' => $identity
		];

		if ( $id == 0 && $params['uid'] != '' ) {
			$id = $db -> getOne( "SELECT id FROM {$sqlname}cronmanager WHERE uid = '$params[uid]'" ) + 0;
		}

		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}cronmanager SET ?u", arrayNullClean( $d ) );
			$id = $db -> InsertId();

		}
		else {

			unset( $d['id'], $d['uid'], $d['identity'] );

			$db -> query( "UPDATE {$sqlname}cronmanager SET ?u WHERE id = '$id'", arrayNullClean( $d ) );

		}

		return $id;

	}

	/**
	 * Деактивация задания
	 *
	 * @param $id
	 * @return bool
	 */
	public function disableTask($id): bool {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$db -> query( "UPDATE {$sqlname}cronmanager SET ?u WHERE id = '$id'", ["active" => "off"] );

		return true;

	}

	/**
	 * Активация задания
	 *
	 * @param $id
	 * @return bool
	 */
	public function enableTask($id): bool {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$db -> query( "UPDATE {$sqlname}cronmanager SET ?u WHERE id = '$id'", ["active" => "on"] );

		return true;

	}

	/**
	 * Удаление задания
	 *
	 * @param int $id
	 * @return array
	 */
	public function deleteTask(int $id = 0): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$info = $this -> getTask( $id );

		$result = [
			"result"  => false,
			"message" => "Не найдено"
		];

		if ( !empty( $info ) ) {

			$db -> query( "DELETE FROM {$sqlname}cronmanager WHERE id = '$info[id]'" );

			$db -> query( "DELETE FROM {$sqlname}cronmanager_log WHERE uid = '$info[id]'" );

			$result = [
				"result"  => true,
				"message" => "Выполнено"
			];

		}

		return $result;

	}

	/**
	 * Преобразование данных в готовую команду для crontab
	 * https://www.shellhacks.com/ru/crontab-format-cron-job-examples-linux/
	 *
	 * @param $id
	 * @return string
	 */
	public function compileTaskString($id): string {

		$weeknames = [
			"sunday"    => 0,
			"monday"    => 1,
			"tuesday"   => 2,
			"wednesday" => 3,
			"thursday"  => 4,
			"friday"    => 5,
			"saturday"  => 6
		];

		$task = $this -> getTask( $id );

		$cmd = '';

		$hour   = $task['period']['h'] != '' ? (int)$task['period']['h'] : 1;
		$minute = $task['period']['i'] != '' ? (int)$task['period']['i'] : 5;
		$day    = $task['period']['d'] != '' ? (int)$task['period']['d'] : 1;
		$month  = $task['period']['m'] != '' ? (int)$task['period']['m'] : 1;

		switch ($task['parent']) {

			case "everyminutes":

				$cmd = "*/$minute * * * *";

			break;
			case "everyhour":

				$cmd = "0 */$hour * * *";

			break;
			case "everyday":

				$cmd = "$minute $hour * * *";

			break;
			case "everymonth":

				$cmd = "$minute $hour $day * *";

			break;
			case "everyweek":

				$cmd = "$minute $hour * * ".strtr( yimplode( ",", $task['period']['w'] ), $weeknames );

			break;
			case "everyyear":

				$cmd = "$minute $hour $day $month *";

			break;
			case "expert":

				$cmd = $task['period']['cmd'];

			break;

		}

		$this -> response['period'] = $cmd;
		$this -> response['task']   = $task;

		return $cmd."    ".$task['bin']." ".$task['script'];//." /dev/null 2>&1";

	}

	/**
	 * Получение следующего времени срабатывания задания
	 * на основе параметров по времени
	 *
	 * @param        $id
	 * @param string $time
	 * @return string
	 */
	public function getNextTime($id, string $time = ''): string {

		$this -> compileTaskString( $id );

		$xtime = !empty($time) ? $time : current_datumtime();

		$expression = $this -> response['period'];

		$cron = Cron\CronExpression ::factory( $expression );

		return $cron -> getNextRunDate( $xtime ) -> format( 'Y-m-d H:i:s' );

	}

	/**
	 * Логгирование запусков заданий
	 *
	 * @param array $params
	 * @return bool
	 */
	public function logger(array $params = []): bool {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;

		$db = new SafeMySQL( $this -> opts );

		$allowed = [
			"id",
			"uid",
			"datum",
			"task",
			"response",
			"identity"
		];

		/**
		 * Удалим старые записи лога для этого задания, оставим только 9 последних
		 */
		$extend = $db -> getCol( "SELECT id FROM {$sqlname}cronmanager_log WHERE uid = '$params[uid]' AND identity = '$identity' ORDER BY id DESC LIMIT 0, 9" );

		if ( !empty( $extend ) )
			$db -> query( "DELETE FROM {$sqlname}cronmanager_log WHERE id NOT IN (".implode( ",", $extend ).") AND uid = '$params[uid]' AND identity = '$identity'" );

		$params['identity'] = $identity;

		$log = $db -> filterArray( $params, $allowed );

		$db -> query( "INSERT INTO {$sqlname}cronmanager_log SET ?u", arrayNullClean( $log ) );
		$id = $db -> insertId();

		return $id > 0;

	}

	/**
	 * Лог для конкретного задания
	 *
	 * @param $id
	 * @return array
	 */
	public function getLog($id): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$list = [];

		$data = $db -> getAll( "SELECT * FROM {$sqlname}cronmanager_log WHERE uid = '$id' ORDER BY id DESC" );
		foreach ( $data as $da ) {

			$list[] = [
				"id"       => $da['id'],
				"datum"    => $da['datum'],
				"datumru"  => get_sfdate( $da['datum'] ),
				"diff"     => diffDateTime( $da['datum'] ),
				//"task"     => $da[ 'task' ],
				"response" => $this -> isJSON( $da['response'] ) ? implode( "<br>", json_decode( $da['response'], true ) ) : nl2br( $da['response'] ),
				"identity" => $da['identity']
			];

		}

		return $list;

	}

	/**
	 * Функция проверки строки на формат JSON
	 *
	 * @param $string
	 * @return bool
	 */
	private function isJSON($string): bool {
		json_decode( $string, true );

		return (json_last_error() == JSON_ERROR_NONE);
	}

}