<?php
/* ============================ */

/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Salesman;

use PHPMailer\PHPMailer\Exception;

/**
 * Класс для работы с объектом Проект и его элементами
 *
 * Class Project
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     2.1 (06/09/2019)
 * @example
 * $Project = \Salesman\Project::info($id);
 */
class Project {
	
	public $response = [];
	
	public const STATUSPROJECT = [
		0 => 'Новый',
		1 => 'В работе',
		2 => 'Выполнен',
		3 => 'Отменен',
		4 => 'Пауза'
	];
	
	public const COLORSPROJECT = [
		0 => 'broun',
		1 => 'blue',
		2 => 'green',
		3 => 'red',
		4 => 'gray'
	];
	
	public const ICONSPROJECT = [
		0 => 'icon-lamp',
		1 => 'icon-tools',
		2 => 'icon-check',
		3 => 'icon-cancel-circled',
		4 => 'icon-pause'
	];
	
	public const STATUSWORK = [
		0 => 'Новая',
		1 => 'В работе',
		2 => 'Проверка',
		3 => 'Пауза',
		4 => 'Выполнена',
		5 => 'Отменена'
	];
	
	public const COLORSWORK = [
		0 => 'broun',
		1 => 'blue',
		2 => 'orange',
		3 => 'gray',
		4 => 'green',
		5 => 'red'
	];
	
	public const ICONSWORK = [
		0 => 'icon-lamp',
		1 => 'icon-tools',
		2 => 'icon-jobsearch',
		3 => 'icon-pause',
		4 => 'icon-check',
		5 => 'icon-cancel-circled'
	];
	
	public const FIELDSPROJECT = [
		"id"         => "ID Проекта",
		"name"       => "Название",
		"content"    => "Описание",
		"datum"      => "Дата создания",
		"date_start" => "Дата.Старт",
		"date_end"   => "Дата.План",
		"date_fact"  => "Дата.Факт",
		"comment"    => "Комментарий",
		"status"     => "Статус",
		"did"        => "Сделка",
		"clid"       => "Клиент",
		"pid_list"   => "Контакты",
		"fid"        => "Файлы",
		"author"     => "Автор",
		"iduser"     => "Ответственный",
		"identity"   => ""
	];
	
	public const FIELDSWORK = [
		"id"         => "ID Работы",
		"idproject"  => "ID Проекта",
		"type"       => "Тип работ",
		"name"       => "Название",
		"datum"      => "Дата создания",
		"date_start" => "Дата.Старт",
		"date_end"   => "Дата.План",
		"date_fact"  => "Дата.Факт",
		"content"    => "Описание",
		"comment"    => "Комментарий",
		"status"     => "Статус",
		"author"     => "Автор",
		"iduser"     => "Ответственный",
		"workers"    => "Исполнители",
		"fid"        => "Файлы",
		"identity"   => ""
	];
	
	/**
	 * Функция возвращает все статусы, в т.ч. кастомные
	 *
	 * @param string|null $status
	 *
	 * @return array
	 */
	public static function getStatusesProject(string $status = null): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$database = $GLOBALS['database'];
		
		$response = [];
		
		/**
		 * Временное решение. Если база не обновлена
		 */
		$da = (int)$db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_status'" );
		if ($da == 0) {
			
			if (!isset( $status )) {
				
				foreach (self::STATUSPROJECT as $index => $value) {
					
					$response[] = [
						"index"    => $index,
						"name"     => $value,
						"icon"     => self::ICONSPROJECT[$index],
						"color"    => self::COLORSPROJECT[$index],
						"control"  => in_array( $index, [
							2,
							3
						] ) ? "true" : "false",
						"isfinal"  => in_array( $index, [
							2,
							3
						] ) ? "true" : "false",
						"iscancel" => $index == 3 ? "true" : "false",
						"users"    => []
					];
					
				}
				
			}
			else {
				
				$response = [
					"index"    => $status,
					"name"     => self::STATUSPROJECT[$status],
					"icon"     => self::ICONSPROJECT[$status],
					"color"    => self::COLORSPROJECT[$status],
					"control"  => in_array( $status, [
						2,
						3
					] ) ? "true" : "false",
					"isfinal"  => in_array( $status, [
						2,
						3
					] ) ? "true" : "false",
					"iscancel" => $status == 3 ? "true" : "false",
					"users"    => []
				];
				
			}
			
			return $response;
			
		}
		
		if (!isset( $status )) {
			
			$statuses = $db -> getAll( "SELECT * FROM {$sqlname}projects_status WHERE type = 'prj' AND identity = '$identity' ORDER BY sort" );
			foreach ($statuses as $item) {
				
				$response[] = [
					"index"    => (int)$item['id'],
					"name"     => $item['name'],
					"icon"     => $item['icon'],
					"color"    => $item['color'],
					"control"  => $item['control'],
					"isfinal"  => $item['isfinal'],
					"iscancel" => $item['iscancel'],
					"users"    => []
				];
				
			}
			
		}
		else {
			
			$item     = $db -> getRow( "SELECT * FROM {$sqlname}projects_status WHERE id = '$status' AND identity = '$identity'" );
			$response = [
				"index"    => (int)$item['id'],
				"name"     => $item['name'],
				"icon"     => $item['icon'],
				"color"    => $item['color'],
				"control"  => $item['control'],
				"isfinal"  => $item['isfinal'],
				"iscancel" => $item['iscancel'],
				"users"    => []
			];
			
		}
		
		return $response;
		
	}
	
	/**
	 * Получение следующего статуса проекта
	 *
	 * @param string $status
	 *
	 * @return mixed|string
	 */
	public static function getNextStatusProject(string $status) {
		
		$statuses = self ::getStatusesProject();
		
		$next = end( $statuses )['index'];
		
		foreach ($statuses as $i => $item) {
			
			if ($item['index'] == $status) {
				
				$next = $statuses[((int)$i + 1)]['index'] ?? end( $statuses )['index'];
				
			}
			
		}
		
		if ($next > 0) {
			return $next;
		}
		
		return $status;
		
	}
	
	/**
	 * Функция возвращает все статусы, в т.ч. кастомные
	 *
	 * @param string|null $status
	 *
	 * @return array
	 */
	public static function getStatusesWork(string $status = null): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$database = $GLOBALS['database'];
		
		$response = [];
		
		/**
		 * Временное решение. Если база не обновлена
		 */
		$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}projects_status'" );
		if ($da == 0) {
			
			if (!isset( $status )) {
				
				foreach (self::STATUSWORK as $index => $value) {
					
					$response[] = [
						"index"    => $index,
						"name"     => $value,
						"icon"     => self::ICONSWORK[$index],
						"color"    => self::COLORSWORK[$index],
						"control"  => in_array( $index, [
							4,
							5
						] ) ? "true" : "false",
						"isfinal"  => in_array( $index, [
							4,
							5
						] ) ? "true" : "false",
						"iscancel" => $index == 5 ? "true" : "false",
						"users"    => []
					];
					
				}
				
			}
			else {
				
				$response = [
					"index"    => $status,
					"name"     => self::STATUSWORK[$status],
					"icon"     => self::ICONSWORK[$status],
					"color"    => self::COLORSWORK[$status],
					"control"  => in_array( $status, [
						4,
						5
					] ) ? "true" : "false",
					"isfinal"  => in_array( $status, [
						4,
						5
					] ) ? "true" : "false",
					"iscancel" => $status == 5 ? "true" : "false",
					"users"    => []
				];
				
			}
			
			return $response;
			
		}
		
		if (!isset( $status )) {
			
			$statuses = $db -> getAll( "SELECT * FROM {$sqlname}projects_status WHERE type = 'wrk' AND identity = '$identity' ORDER BY sort" );
			foreach ($statuses as $item) {
				
				$response[] = [
					"index"    => (int)$item['id'],
					"name"     => $item['name'],
					"icon"     => $item['icon'],
					"color"    => $item['color'],
					"control"  => $item['control'],
					"isfinal"  => $item['isfinal'],
					"iscancel" => $item['iscancel'],
					"users"    => []
				];
				
			}
			
		}
		else {
			
			$item     = $db -> getRow( "SELECT * FROM {$sqlname}projects_status WHERE id = '$status' AND identity = '$identity'" );
			$response = [
				"index"    => (int)$item['id'],
				"name"     => $item['name'],
				"icon"     => $item['icon'],
				"color"    => $item['color'],
				"control"  => $item['control'],
				"isfinal"  => $item['isfinal'],
				"iscancel" => $item['iscancel'],
				"users"    => []
			];
			
		}
		
		return $response;
		
	}
	
	/**
	 * Получение следующего статуса работы
	 *
	 * @param string $status
	 *
	 * @return mixed|string
	 */
	public static function getNextStatusWork(string $status) {
		
		$statuses = self ::getStatusesWork();
		
		$next = end( $statuses )['index'];
		
		foreach ($statuses as $i => $item) {
			
			if ($item['index'] == $status) {
				
				$next = $statuses[(int)$i + 1]['index'] ?? end( $statuses )['index'];
				
			}
			
		}
		
		if ($next > 0) {
			return $next;
		}
		
		return $status;
		
	}
	
	/**
	 * Получение массива финальных статусов Проектов
	 *
	 * @return array
	 */
	public static function getFinalStatuses(): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		$statusFinal     = $db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND isfinal = 'true' AND identity = '$identity'" );
		$statusFinalWork = $db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND isfinal = 'true' AND identity = '$identity'" );
		
		return [
			"prj" => $statusFinal,
			"wrk" => $statusFinalWork
		];
		
	}
	
	/**
	 * Получение массива финальных статусов Работ
	 *
	 * @return array
	 */
	public static function getCancelStatuses(): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		$statusCancel     = (array)$db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND iscancel = 'true' AND identity = '$identity'" );
		$statusCancelWork = (array)$db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND iscancel = 'true' AND identity = '$identity'" );
		
		return [
			"prj" => $statusCancel,
			"wrk" => $statusCancelWork
		];
		
	}
	
	/**
	 * Получение массива финальных статусов Работ
	 *
	 * @return array
	 */
	public static function getWinStatuses(): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		$statusWin     = (array)$db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND isfinal = 'true' AND iscancel != 'true' AND identity = '$identity'" );
		$statusWinWork = (array)$db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND isfinal = 'true' AND iscancel != 'true' AND identity = '$identity'" );
		
		return [
			"prj" => $statusWin,
			"wrk" => $statusWinWork
		];
		
	}
	
	/**
	 * вывод списка проектов с работами для API. НЕ ДЛЯ ИНТЕРФЕЙСА
	 *
	 * @param array $filter
	 * array status - по статусу проекта
	 * array statusWork - по статусу работ
	 * int page - номер страницы
	 * int iduser - id куратора
	 * str order - сортировать по столбцу, def = datum
	 * str sort - порядок сотрировки, def = DESC
	 * str word - поиск по слову
	 * str da1 - фильтр по дате начала
	 * str da2 - поиск по дате конца
	 *
	 * @return array
	 */
	public static function listFull(array $filter = []): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$iduser1  = $GLOBALS['iduser1'];
		$db       = $GLOBALS['db'];
		
		$statuss     = (array)$filter['status'];
		$statussWork = $filter['statusWork'];
		$iduser      = (int)$filter['iduser'];
		$ord         = $filter['order'];
		$tuda        = mb_strtoupper( $filter['sort'] );
		$word        = $filter['word'];
		$d1          = $filter['da1'];
		$d2          = $filter['da2'];
		$page        = $filter['page'];
		
		//число записей проектов на страницу
		$lines_per_page = 200;
		
		//настройки модуля
		$mdcset      = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'projects' and identity = '$identity'" );
		$mdcsettings = (array)json_decode( (string)$mdcset['content'], true );
		
		$sort = '';
		$tuda = $tuda ?? 'DESC';
		$ord  = $ord ?? 'datum';
		$ordd = 'datum';
		
		if ($ord == 'status' && $tuda != "DESC") {
			$ordd = " ORDER BY field({$sqlname}projects.status, 0, 1, 2, 3)";
		}
		
		elseif ($ord != "progress") {
			$ordd = " ORDER BY {$sqlname}projects.".$ord;
		}
		
		//$sort .= ( $d1 != '' && $d2 != '' ) ? " AND ({$sqlname}projects.date_start >= '$d1 00:00:01' AND {$sqlname}projects.date_end <= '$d2 23:59:59')" : ( $d1 == '' && $d2 != '' ? " AND {$sqlname}projects.date_end <= '$d2 23:59:59'" : " AND {$sqlname}projects.date_start >= '$d1 00:00:01'" );
		
		if ($d1 != '' && $d2 != '') {
			
			$sort .= " AND ({$sqlname}projects.date_start >= '$d1 00:00:01' AND {$sqlname}projects.date_end <= '$d2 23:59:59')";
			
		}
		elseif ($d1 == '' && $d2 != '') {
			$sort .= " AND {$sqlname}projects.date_end <= '$d2 23:59:59'";
		}
		else {
			$sort .= " AND {$sqlname}projects.date_start >= '$d1 00:00:01'";
		}
		
		if ($word != '') {
			$sort .= " AND (
				({$sqlname}projects.name LIKE '%".$word."%')
				OR
				({$sqlname}clientcat.title LIKE '%".$word."%')
				OR
				({$sqlname}dogovor.title LIKE '%".$word."%')
				OR
				({$sqlname}dogovor.did ='$word')
			)";
		}
		
		if (!empty( $statuss )) {
			$sort .= " AND {$sqlname}projects.status IN (".implode( ",", $statuss ).")";
		}
		
		if ($iduser > 0) {
			$sort .= " AND (
				{$sqlname}projects.iduser = '$iduser'
				OR
				(SELECT COUNT(*) FROM {$sqlname}projects_work WHERE idproject = {$sqlname}projects.id and FIND_IN_SET($iduser,{$sqlname}projects_work.workers) > 0 and identity = '$identity') > 0
			)";
		}
		
		//ограничиваем доступ
		if (!empty( $mdcsettings['projCoordinatorView'] ) && in_array( $iduser1, (array)$mdcsettings['projCoordinator'] ) && in_array( $iduser1, (array)$mdcsettings['projCoordinatorView'] )) {
			$sort .= " AND (
				{$sqlname}projects.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).")
				OR
				(SELECT COUNT(*) FROM {$sqlname}projects_work WHERE idproject = {$sqlname}projects.id and FIND_IN_SET($iduser1,{$sqlname}projects_work.workers) > 0 and identity = '$identity') > 0
			)";
		}
		
		if (!in_array( $iduser1, (array)$mdcsettings['projCoordinator'] )) {
			$sort .= " AND (
				{$sqlname}projects.iduser IN (".yimplode( ",", (array)get_people( $iduser1, "yes" ) ).")
				OR
				(SELECT COUNT(*) FROM {$sqlname}projects_work WHERE idproject = {$sqlname}projects.id and FIND_IN_SET($iduser1,{$sqlname}projects_work.workers) > 0 and identity = '$identity') > 0
				OR
				(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE did={$sqlname}projects.did AND iduser='$iduser1' AND identity='$identity') > 0
			)";
		}
		
		//список проектов
		$list  = [];
		$query = "
		SELECT 
			{$sqlname}projects.id,
			{$sqlname}projects.datum,
			{$sqlname}projects.name,
			{$sqlname}projects.date_start,
			{$sqlname}projects.date_end,
			{$sqlname}projects.date_fact,
			{$sqlname}projects.iduser,
			{$sqlname}projects.author,
			{$sqlname}projects.did,
			{$sqlname}projects.status,
			{$sqlname}projects.content
		FROM {$sqlname}projects
		WHERE 
			{$sqlname}projects.id > 0 AND
			{$sqlname}projects.identity = '$identity'
			$sort
		GROUP BY {$sqlname}projects.id
		";
		
		$result    = $db -> query( $query );
		$all_lines = $db -> numRows( $result );
		
		if (empty( $page ) || $page <= 0) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}
		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;
		
		$count_pages = ceil( $all_lines / $lines_per_page );
		if ($count_pages < 1) {
			$count_pages = 1;
		}
		
		$query .= " $ordd $tuda LIMIT $lpos,$lines_per_page";
		
		$result = $db -> query( $query );
		while ($prj = $db -> fetch( $result )) {
			
			//список работ по проекту
			$works = [];
			
			$subquery = "
			SELECT 
				{$sqlname}projects_work.id,
				{$sqlname}projects_work.idproject,
				{$sqlname}projects_work.type,
				{$sqlname}projects_work.name,
				{$sqlname}projects_work.datum,
				{$sqlname}projects_work.date_start,
				{$sqlname}projects_work.date_end,
				{$sqlname}projects_work.date_fact,
				{$sqlname}projects_work.status,
				{$sqlname}projects_work.content,
				{$sqlname}projects_work.iduser,
				{$sqlname}projects_work.workers
			FROM {$sqlname}projects_work
			WHERE 
				{$sqlname}projects_work.id > 0 AND
				{$sqlname}projects_work.idproject = '$prj[id]' AND
				{$sqlname}projects_work.identity = '$identity'
				".(!empty( $statussWork ) ? " AND {$sqlname}projects_work.status IN (".implode( ",", (array)$statussWork ).")" : "")."
			ORDER BY {$sqlname}projects_work.date_start";
			$res      = $db -> getAll( $subquery );
			foreach ($res as $work) {
				
				$typeName = $db -> getOne( "SELECT title FROM {$sqlname}projects_work_types WHERE id = '$work[type]' and identity = '$identity'" );
				
				$works = [
					"id"         => $work['id'],
					"name"       => $work['name'],
					"type"       => $work['type'],
					"typeName"   => $typeName,
					"datum"      => $work['datum'],
					"dstart"     => $work['date_start'],
					"dplan"      => $work['date_end'],
					"dfact"      => ($work['date_fact'] != '0000-00-00') ? $work['date_fact'] : null,
					"status"     => $work['status'],
					"statusName" => self ::getStatusesWork( $work['status'] )['name'],
					//strtr( $work[ 'status' ], self::STATUSWORK ),
					"content"    => $work['content'],
					"iduser"     => $work['iduser'],
					"workers"    => yexplode( ",", $work['workers'] )
				];
				
			}
			
			//Количество работ
			$countWorks = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}projects_work WHERE idproject = '$prj[id]' and identity = '$identity'" );
			
			//Количество завершенных работ
			$countWorksDo = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}projects_work WHERE idproject ='$prj[id]' AND status IN (".yimplode( ",", self ::getFinalStatuses()['wrk'] ).") and identity = '$identity'" );
			
			$persent = ($countWorks > 0) ? round( $countWorksDo / $countWorks * 100, 2 ) : 0;
			
			$clid = ($prj['did'] > 0) ? (int)getDogData( $prj['did'], 'clid' ) : 0;
			
			$list[] = [
				'id'         => (int)$prj['id'],
				'name'       => $prj['name'],
				"dcreate"    => $prj['datum'],
				"dstart"     => $prj['date_start'],
				"dplan"      => $prj['date_end'],
				"dfact"      => ($prj['date_fact'] != '0000-00-00') ? $prj['date_fact'] : null,
				"status"     => $prj['status'],
				"statusName" => self ::getStatusesProject( $prj['status'] )['name'],
				//strtr( $prj[ 'status' ], self::STATUSPROJECT ),
				"content"    => $prj['content'],
				"clid"       => ($clid > 0) ? $clid : null,
				"client"     => ($clid > 0) ? current_client( $clid ) : null,
				"did"        => ((int)$prj['did'] > 0) ? (int)$prj['did'] : null,
				"deal"       => ((int)$prj['did'] > 0) ? current_dogovor( $prj['did'] ) : null,
				"author"     => (int)$prj['author'],
				"iduser"     => (int)$prj['iduser'],
				"progress"   => $persent,
				"count"      => [
					"total"    => $countWorks,
					"complete" => $countWorksDo
				],
				'works'      => $works
			];
			
		}
		
		return [
			"list"      => $list,
			"page"      => $page,
			"pageTotal" => $count_pages
		];
		
	}
	
	/**
	 * Вывод лога изменения статуса
	 *
	 * @param int    $id
	 * @param string $type
	 *
	 * @return array
	 */
	public static function statusLog(int $id = 0, string $type = 'project'): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		$list = [];
		
		if ($id > 0) {
			
			if ($type == 'project') {
				
				$r = $db -> query( "SELECT * FROM {$sqlname}projects_statuslog WHERE project = '$id' AND identity = '$identity' ORDER BY date" );
				while ($s = $db -> fetch( $r )) {
					
					$list[] = [
						"date"       => $s['date'],
						"status"     => $s['status'],
						"statusName" => self ::getStatusesProject( $s['status'] )['name'],
						//strtr( $s[ 'status' ], self::STATUSPROJECT ),
						"work"       => $s['work'],
						"comment"    => $s['comment'],
						"iduser"     => $s['iduser']
					];
					
				}
				
			}
			elseif ($type == 'work') {
				
				$r = $db -> query( "SELECT * FROM {$sqlname}projects_statuslog WHERE work = '$id' AND identity = '$identity' ORDER BY date" );
				while ($s = $db -> fetch( $r )) {
					
					$list[] = [
						"date"       => $s['date'],
						"status"     => $s['status'],
						"statusName" => self ::getStatusesWork( $s['status'] )['name'],
						//strtr( $s[ 'status' ], self::STATUSWORK ),
						"comment"    => $s['comment'],
						"iduser"     => $s['iduser']
					];
					
				}
				
			}
			
		}
		
		return $list;
		
	}
	
	/**
	 * Получение информации о проекте
	 *
	 * @param int $id - идентификатор проекта
	 *
	 * @return array "Project"
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *          403 - Проект с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id проекта
	 *
	 * @example $Project = \Salesman\Project::info($id);
	 */
	public static function info(int $id = 0): array {
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		if ($id > 0) {
			
			$count = (int)$db -> getOne( "SELECT count(*) FROM {$sqlname}projects WHERE id = '$id' and identity = '$identity'" );
			
			if ($count > 0) {
				
				$project = $db -> getRow( "SELECT * FROM {$sqlname}projects WHERE id = '$id' and identity = '$identity'" );
				
				$response['result']  = 'Success';
				$response['project'] = [
					"id"         => (int)$project['id'],
					"name"       => $project['name'],
					"content"    => $project['content'],
					"datum"      => $project['datum'],
					"date_start" => $project['date_start'],
					"date_end"   => $project['date_end'],
					"date_fact"  => $project['date_fact'],
					"comment"    => $project['comment'],
					"status"     => $project['status'],
					"did"        => (int)$project['did'],
					"clid"       => (int)$project['clid'],
					"pid_list"   => $project['pid_list'],
					"fid"        => (int)$project['fid'],
					"author"     => $project['author'],
					"iduser"     => $project['iduser']
				];
				
			}
			else {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Проект с указанным id не найден";
				
			}
			
		}
		else {
			
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id проекта";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Добавление/изменение проекта
	 *
	 * @param int   $id     - идентификатор записи проекта
	 * @param array $params
	 *                      [name] - название
	 *                      [catid] - категория проекта
	 *                      [content] - описание
	 *                      [datum] - дата создания проекта
	 *                      [date_start] - дата принятия в работу
	 *                      [date_end] - плановая дата завершения
	 *                      [datum_fact] - фактическая дата завершения
	 *                      [comment] - комментарий к закрытию
	 *                      [status] - статус проекта
	 *                      [did] - id сделки
	 *                      [clid] - id клиента
	 *                      [iduser] - создатель проекта
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно добавлен/изменен
	 *         - [data] = id
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *
	 *          403 - Проект с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id проекта
	 *
	 * @throws Exception
	 * @throws \Exception
	 * @example $Project = \Salesman\Project::edit($id,$params);
	 */
	public static function edit(int $id = 0, array $params = []): array {
		
		global $hooks;
		
		$rootpath = dirname( __DIR__ ).'/';
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity    = $GLOBALS['identity'];
		$sqlname     = $GLOBALS['sqlname'];
		$iduser1     = $GLOBALS['iduser1'];
		$db          = $GLOBALS['db'];
		$productInfo = $GLOBALS['productInfo'];
		
		$mes = '';
		
		$post = $params;
		
		if ($id > 0) {
			$params = $hooks -> apply_filters( "project_editfilter", $params );
		}
		else {
			$params = $hooks -> apply_filters( "project_addfilter", $params );
		}
		
		//настройки модуля
		$mdcset      = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'projects' and identity = '$identity'" );
		$mdcsettings = (array)json_decode( (string)$mdcset['content'], true );
		
		$result = $db -> getrow( "SELECT * FROM {$sqlname}projects WHERE id = '$id' and identity = '$identity'" );
		
		// Получим старые данные о проекте
		$oldParams = [
			"name"       => $result['name'],
			"content"    => $result['content'],
			"datum"      => $result['datum'],
			"date_start" => $result['date_start'],
			"date_end"   => $result['date_end'],
			"datum_fact" => $result['datum_fact'],
			"comment"    => $result['comment'],
			"status"     => $result['status'],
			"did"        => (int)$result['did'],
			"clid"       => (int)$result['clid'],
			"author"     => (int)$result['author'],
			"iduser"     => (int)$result['iduser']
		];
		
		$params['identity'] = $identity;
		
		$project = $params;
		//unset($project['createDeal']);
		//unset($project['createDealStep']);
		
		// Обновление записи
		if ($id > 0) {
			
			// Проверка на существование в БД
			$prid = (int)$db -> getOne( "SELECT count(*) FROM {$sqlname}projects WHERE id='$id' and identity = '$identity'" );
			
			//если это существующий проект
			if ($prid > 0) {
				
				//добавляем сделку
				if ((int)$project['did'] == 0 && $mdcsettings['createDeal'] == 'yes') {
					
					$param = [
						"title"      => $project['name'],
						"datum_plan" => current_datum( -14 ),
						"content"    => $project['content'],
						"iduser"     => $iduser1,
						"clid"       => (int)$project['clid'],
						"category"   => $mdcsettings['createDealStep']
					];
					
					$deal = new Deal();
					$resp = $deal -> add( $param );
					
					if ($resp['result'] != 'Error' && $resp['data'] > 0) {
						
						$project['did'] = $resp['data'];
						$mes            = "Добавлена сделка";
						
					}
					else {
						
						$mes = "Ошибка при добавлении сделки - ".$resp['result']['text'];
						
					}
					
				}
				
				//фильтруем ключи
				$data = $db -> filterArray( $project, array_keys( self::FIELDSPROJECT ) );
				
				//print_r($data);
				
				//очистка от говна
				if (isset( $data['did'] )) {
					$data['did'] = (int)$data['did'];
				}
				
				//if ( isset( $data[ 'clid' ] ) )
				//$data[ 'clid' ] = $data[ 'clid' ];
				
				if (isset( $data['name'] )) {
					$data['name'] = untag( $data['name'] );
				}
				
				if (isset( $data['content'] )) {
					$data['content'] = untag( $data['content'] );
				}
				
				if (isset( $data['date_start'] )) {
					$data['date_start'] = untag( $data['date_start'] );
				}
				
				if (isset( $data['date_end'] )) {
					$data['date_end'] = untag( $data['date_end'] );
				}
				
				if (isset( $data['author'] )) {
					$data['author'] = (int)$data['author'];
				}
				
				if (isset( $data['iduser'] )) {
					$data['iduser'] = (int)$data['iduser'];
				}
				
				//if ( isset( $data[ 'status' ] ) )
				//$data[ 'status' ] = (int)$data[ 'status' ];
				
				if (isset( $data['date_fact'] )) {
					$data['date_fact'] = untag( $data['date_fact'] );
				}
				
				if (isset( $data['comment'] ) && $data['comment'] != '') {
					$data['comment'] = untag( $data['comment'] );
				}
				else {
					unset( $data['comment'] );
				}
				
				if (isset( $data['pid_list'] )) {
					$data['pid_list'] = yimplode( ',', (array)$data['pid_list'] );
				}
				
				if (empty( $data['pid_list'] )) {
					unset( $data['pid_list'] );
				}
				
				if (empty( $data['date_fact'] )) {
					unset( $data['date_fact'] );
				}
				
				//обновляем проект
				$db -> query( "UPDATE {$sqlname}projects SET ?u WHERE id = '$id' and identity = '$identity'", $data );
				
				$response['result'] = 'Данные по проекту обновлены';
				$response['data']   = $id;
				
				$data['id'] = $id;
				
				if ($hooks) {
					$hooks -> do_action( "project_edit", $post, $data );
				}
				
				/*
				if ( is_numeric( $oldStatus ) && $oldStatus != $project[ 'status' ] && $project[ 'status' ] > 0 ) {

					self ::statusProject( $id, [
						'status'  => $project[ 'status' ],
						'project' => $id,
						'comment' => untag( $project[ 'comment' ] ),
						'nolog'   => 'yes',
						'noemail' => 'yes'
					] );

				}
				*/
				
				//что изменилось
				$new       = self ::info( $id );
				$newParams = $new['project'];
				unset( $newParams['id'] );
				
				$diff = array_keys( array_diff_ext( $oldParams, $newParams ) );
				
				$text = [];
				
				foreach ($diff as $key) {
					
					$item = $oitem = [];
					
					switch ($key) {
						
						case "pid":
							
							$plist = $polist = [];
							
							foreach ($newParams[$key] as $pid) {
								$plist[] = current_person( $pid );
							}
							
							foreach ($oldParams[$key] as $pid) {
								$polist[] = current_person( $pid );
							}
							
							$item[$key]  = yimplode( "; ", $plist );
							$oitem[$key] = yimplode( "; ", $polist );
						
						break;
						case "clid":
							
							$item[$key]  = current_client( $newParams[$key] );
							$oitem[$key] = current_client( $oldParams[$key] );
						
						break;
						case "did":
							
							$item[$key]  = current_dogovor( $newParams[$key] );
							$oitem[$key] = current_dogovor( $oldParams[$key] );
						
						break;
						case "iduser":
						case "autor":
							
							$item[$key]  = current_user( $newParams[$key] );
							$oitem[$key] = current_user( $oldParams[$key] );
						
						break;
						case "content":
						case "comment":
							
							$item[$key]  = nl2br( $newParams[$key] );
							$oitem[$key] = nl2br( $oldParams[$key] );
						
						break;
						case "status":
							
							$item[$key]  = self ::getStatusesProject( $newParams[$key] )['name'];//strtr( $newParams[ $key ], self::STATUSPROJECT );
							$oitem[$key] = self ::getStatusesProject( $oldParams[$key] )['name'];//strtr( $oldParams[ $key ], self::STATUSPROJECT );
						
						break;
						default:
							
							$item[$key]  = $newParams[$key];
							$oitem[$key] = $oldParams[$key];
						
						break;
						
					}
					
					if ($oitem[$key] == '' || (is_numeric( $oitem[$key] ) && $oitem[$key] == 0)) {
						$oitem[$key] = 'не указано';
					}
					
					$text[] = '
						<div class="gray uppercase Bold fs-07">'.strtr( $key, self::FIELDSPROJECT ).'</div>
						<div class="mb10"><b>'.$item[$key].'</b> <div class="inline gray">[&nbsp;было - '.$oitem[$key].'&nbsp;]</div></div>
					';
					
				}
				
				/**
				 * Отправляем уведомление
				 */
				self ::sendNotify( 'project.edit', [
					"id"      => $id,
					"type"    => 'project',
					"content" => yimplode( "", $text ),
					"iduser"  => $iduser1
				] );
				
			}
			else {
				
				$response['result'] = 'Проект с таким id не существует!';
				$response['data']   = $id;
				
			}
			
		}
		
		// Добавление новой записи
		else {
			
			$project['author'] = $iduser1;
			$project['datum']  = ($project['datum'] == '') ? current_datum() : untag( $project['datum'] );
			
			if ($project['name'] == '') {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Отсутствуют параметры - Название проекта";
				
			}
			else {
				
				if ($project['did'] < 1 && $mdcsettings['createDeal'] == 'yes') {
					
					$param = [
						"title"      => $project['name'],
						"datum_plan" => current_datum( -14 ),
						"content"    => $project['content'],
						"author"     => $iduser1,
						"iduser"     => $project['iduser'],
						"clid"       => $project['clid'],
						"category"   => $mdcsettings['createDealStep']
					];
					
					$deal = new Deal();
					$resp = $deal -> add( $param );
					
					if ($resp['result'] != 'Error' && $resp['data'] > 0) {
						
						$project['did'] = $resp['data'];
						$mes            = "Добавлена сделка";
						
					}
					else {
						$mes = "Ошибка при добавлении сделки - ".$resp['result']['text'];
					}
					
				}
				
				//фильтруем ключи
				$data = $db -> filterArray( $project, array_keys( self::FIELDSPROJECT ) );
				
				//print_r($data);
				
				$db -> query( "INSERT INTO {$sqlname}projects SET ?u", arrayNullClean( $data ) );
				$id = $db -> insertId();
				
				$response['result'] = 'Проект успешно добавлен; ';
				$response['data']   = $id;
				
				// создаем работы по выбранному шаблону
				if ((int)$params['template'] > 0) {
					
					// первый статус
					$wstatus = (int)$db -> getOne("SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' ORDER BY sort LIMIT 1");
					
					// загружаем шаблон
					$template = self ::getTemplate( (int)$params['template'] );
					$wxcount = 0;
					
					if (!empty( $template['works'] )) {
						
						foreach ($template['works'] as $work) {
							
							$w = [
								"idproject"  => $id,
								"type"       => (int)$work['type'],
								"name"       => $work['work'],
								"status"     => $wstatus,
								"date_start" => modifyDatetime( $params['date_start'], ["hours" => 24 * $work['offset']] ),
								"date_end"   => modifyDatetime( $params['date_start'], ["hours" => 24 * ($work['offset'] + $work['length'])] ),
							];
							
							$wx = self::updateWork(0, $w);
							//print_r($wx);
							
							$wxcount++;
							
						}
						
					}
					
					$response['result'] .= sprintf("Добавлено %s работ; ", $wxcount);
					
				}
				
				$data['id'] = $id;
				
				if ($hooks) {
					$hooks -> do_action( "project_add", $post, $data );
				}
				
				//отправка email-уведомления
				if (in_array( $project['iduser'], $mdcsettings['projNotify'] )) {
					
					$user   = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='$project[iduser]'" );
					$author = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='$iduser1'" );
					
					// Данные отправителя
					$from     = 'no-replay@'.$_SERVER['HTTP_HOST'];
					$fromname = $productInfo['name'];
					$subject  = 'Создан новый Проект';
					
					// Берем шаблон сообщения из БД
					$html = htmlspecialchars_decode( $db -> getOne( "SELECT content FROM {$sqlname}tpl WHERE tip = 'createProject' and identity = '$identity'" ) );
					
					//подгружаем теги
					$tags = self ::getTags( $id );
					
					// Данные Ответственного
					$tags['projectUserName']  = $user['title'];
					$tags['projectUserPhone'] = $user['phone'];
					$tags['projectUserMob']   = $user['mob'];
					$tags['projectUserEmail'] = $user['email'];
					
					// Данные Автора
					$tags['projectAuthorName']  = $author['title'];
					$tags['projectAuthorPhone'] = $author['phone'];
					$tags['projectAuthorMob']   = $author['mob'];
					$tags['projectAuthorEmail'] = $author['email'];
					
					// Данные о Проекте
					//$tags['projectName']     = $project['name'];
					//$tags['projectStatus']   = $project['status'];
					//$tags['projectDatePlan'] = $project['date_end'];
					//$tags['link']            = '<a href="'.$productInfo['crmurl'].'/card.projects.php?id='.$id.'" style="text-decoration: none;"> Ссылка</a>';
					
					foreach ($tags as $tag => $val) {
						
						$html = str_replace( "{".$tag."}", $val, $html );
						
					}
					
					// Отправка письма
					mailto( [
						$user['email'],
						$user['title'],
						$from,
						$fromname,
						$subject,
						$html
					] );
					
				}
				
				/**
				 * Отправляем уведомление
				 */
				self ::sendNotify( 'project.add', [
					"id"     => $id,
					"type"   => 'project',
					"iduser" => $iduser1
				] );
				
			}
			
		}
		
		$response['result'] .= "<br>".$mes;
		
		return $response;
		
	}
	
	/**
	 * Удаление проекта
	 *
	 * @param int $id - идентификатор записи проекта
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно удален
	 *         - [data] = id
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *          403 - Проект с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id проекта
	 *
	 * @example $Project = \Salesman\Project::delete($id);
	 */
	public static function delete(int $id = 0): array {
		
		global $hooks;
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		if ($id > 0) {
			
			$count = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}projects WHERE id = '$id' and identity = '$identity'" ) + 0;
			
			//проверка на существование проекта
			if ($count == 0) {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Проект с указанным id не найден";
				
			}
			else {
				
				// Удаляем проект
				$db -> query( "delete from {$sqlname}projects WHERE id = '$id' and identity = '$identity'" );
				
				if ($hooks) {
					$hooks -> do_action( "project_delete", $id );
				}
				
				//Удаляем связанные элементы
				$works = $db -> getCol( "SELECT id FROM {$sqlname}projects_work WHERE idproject = '$id' and identity = '$identity'" );
				
				foreach ($works as $work) {
					
					//удаляем работу
					$db -> query( "delete from {$sqlname}projects_work WHERE idproject = '$id' and identity = '$identity'" );
					
					//удаляем задания
					$db -> query( "delete from {$sqlname}projects_task WHERE idwork = '$work' and identity = '$identity'" );
					
				}
				
				$response['result'] = 'Проект удален';
				$response['data']   = $id;
				
			}
		}
		else {
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id проекта";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Добавление/изменение типа работы
	 *
	 * @param int   $id     - идентификатор типа
	 *
	 * @param array $params
	 *                      [title] - наименования
	 *                      [dirdeal] - связанное направление сделки
	 *                      [content] - описание
	 *                      [active] - признак актуальности
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно добавлен/изменен
	 *         - [data] = id
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *          403 - Тип с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id типа проекта
	 *
	 * @example $Type = \Salesman\Project::editType($id);
	 */
	public static function editType(int $id = 0, array $params = []): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		// Обновление записи
		if ($id > 0) {
			
			// Проверка на существование в БД
			$prid = (int)$db -> getOne( "SELECT count(*) FROM {$sqlname}projects_work_types WHERE id = '$id' and identity = '$identity'" );
			
			if ($prid > 0) {
				
				//обновляем тип
				$db -> query( "UPDATE {$sqlname}projects_work_types SET ?u WHERE id = '$id' and identity = '$identity'", $params );
				$response['result'] = 'Тип работы изменен';
				
			}
			else {
				$response['result'] = 'Тип с таким id не существует!';
			}
			
			$response['data'] = $id;
			
			return $response;
			
		}
		
		// Добавление новой записи
		if ($params['title'] == '') {
			
			$response['result']        = 'Error';
			$response['error']['code'] = '407';
			$response['error']['text'] = "Отсутствуют параметры - Наименование типа работы";
			
		}
		else {
			
			$params['identity'] = $identity;
			
			$db -> query( "INSERT INTO {$sqlname}projects_work_types SET ?u", $params );
			$id = $db -> insertId();
			
			$response['result'] = 'Добавлен новый тип работы';
			$response['data']   = $id;
			
		}
		
		return $response;
		
	}
	
	/**
	 * Удаление типа работы
	 *
	 * @param int $id    - идентификатор типа
	 * @param int $newid - тип работ, который устанавливаем
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно удален
	 *         - [data] = id
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *          403 - Тип с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id типа работы
	 *
	 * @example $Type = \Salesman\Project::deleteType($id, $newid);
	 */
	public static function deleteType(int $id = 0, int $newid = 0): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		if ($id > 0) {
			
			$count = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}projects_work_types WHERE id = '$id' and identity = '$identity'" ) + 0;
			
			//проверка на существование типа проекта
			if ($count == 0) {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Тип с указанным id не найден";
				
			}
			else {
				
				// Меняем у проектов тип на новый
				$db -> query( "UPDATE {$sqlname}projects_work SET type='$newid' WHERE type = '$id' and identity = '$identity'" );
				$kol = $db -> affectedRows();
				
				// Удаляем тип
				$db -> query( "delete from {$sqlname}projects_work_types WHERE id = '$id' and identity = '$identity'" );
				
				$response['result'] = "Тип удален <br> Новый тип присвоен ".$kol." работам";
				$response['data']   = $id;
				
			}
			
		}
		else {
			
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id типа работы";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Получение данных о работе
	 *
	 * @param int $id - идентификатор работы
	 *
	 * @return array work
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *          403 - Работа с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id работы
	 *
	 * @example $Work = \Salesman\Work::info($id);
	 */
	public static function getWorkInfo(int $id = 0): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		if ($id > 0) {
			
			$count = (int)$db -> getOne( "SELECT count(*) FROM {$sqlname}projects_work WHERE id = '$id' and identity = '$identity'" );
			
			if ($count > 0) {
				
				$q = "
					SELECT 
						{$sqlname}projects_work.id,
						{$sqlname}projects_work.idproject as idproject,
						{$sqlname}projects_work.type,
						{$sqlname}projects_work.name,
						{$sqlname}projects_work.date_start,
						{$sqlname}projects_work.date_end,
						{$sqlname}projects_work.date_fact,
						{$sqlname}projects_work.status,
						{$sqlname}projects_work.content,
						{$sqlname}projects_work.comment,
						{$sqlname}projects_work.workers,
						{$sqlname}projects_work.iduser,
						{$sqlname}projects_work.fid,
						{$sqlname}projects_work_types.title as type_name,
						{$sqlname}projects.name as project,
						{$sqlname}projects.status as projectstatus,
						{$sqlname}projects.did as did,
						{$sqlname}dogovor.title as dogovor,
						{$sqlname}dogovor.clid as clid,
						{$sqlname}clientcat.title as client
					FROM {$sqlname}projects_work
						LEFT JOIN {$sqlname}projects ON {$sqlname}projects.id = {$sqlname}projects_work.idproject
						LEFT JOIN {$sqlname}projects_work_types ON {$sqlname}projects_work_types.id = {$sqlname}projects_work.type
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}projects.did
						LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
					WHERE 
						{$sqlname}projects_work.id = '$id' and
						{$sqlname}projects_work.identity = '$identity'
					GROUP BY {$sqlname}projects_work.id
					";
				
				$data = $db -> getRow( $q );
				
				$keys = array_keys( $data );
				
				//очищаем от значений с нумерованными ключами
				foreach ($keys as $k) {
					
					if (is_numeric( $k )) {
						unset( $data[$k] );
					}
					
				}
				
				$response['work']   = $data;
				$response['result'] = 'Success';
				
			}
			else {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Работа с указанным id не найдена";
				
			}
			
		}
		else {
			
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id работы";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Добавление/изменение работы
	 *
	 * @param int   $id     - идентификатор записи работы
	 * @param array $params
	 *                      [idproject] - id проекта
	 *                      [name] - наименование
	 *                      [type] - тип
	 *                      [datum] - дата добавления
	 *                      [content] - описание
	 *                      [date_start] - дата принятия в работу
	 *                      [date_end] - плановая дата завершения
	 *                      [date_fact] - фактическая дата завершения
	 *                      [comment] - комментарий при выполнении
	 *                      [status] - статус выполнения
	 *                      [clid] - id клиента
	 *                      [iduser] - создатель
	 *                      [workers] - исполнители
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно изменено
	 *         - [data] = id
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *
	 *          403 - Работа с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id работы
	 *
	 * @throws Exception
	 * @example $Work = \Salesman\Project::updateWork($id,$params);
	 */
	public static function updateWork(int $id = 0, array $params = []): array {
		
		global $hooks;
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity    = $GLOBALS['identity'];
		$sqlname     = $GLOBALS['sqlname'];
		$iduser1     = $GLOBALS['iduser1'];
		$db          = $GLOBALS['db'];
		$productInfo = $GLOBALS['productInfo'];
		
		$params['identity'] = $identity;
		
		if (!empty( $params['idproject'] )) {
			$params['idproject'] = (int)$params['idproject'];
		}
		if (!empty( $params['type'] )) {
			$params['type'] = (int)$params['type'];
		}
		if (!empty( $params['name'] )) {
			$params['name'] = untag( $params['name'] );
		}
		if (!empty( $params['date_start'] )) {
			$params['date_start'] = untag( $params['date_start'] );
		}
		if (!empty( $params['date_end'] )) {
			$params['date_end'] = untag( $params['date_end'] );
		}
		if (!empty( $params['content'] )) {
			$params['content'] = untag( $params['content'] );
		}
		if (!empty( $params['workers'] )) {
			$params['workers'] = (is_array( $params['workers'] )) ? yimplode( ',', $params['workers'] ) : $params['workers'];
		}
		if (!empty( $params['status'] )) {
			$params['status'] = (int)$params['status'];
		}
		if (!empty( $params['date_fact'] )) {
			$params['date_fact'] = untag( $params['date_fact'] );
		}
		else {
			unset( $params['date_fact'] );
		}
		if (!empty( $params['comment'] )) {
			$params['comment'] = untag( $params['comment'] );
		}
		
		$post = $params;
		
		//print_r($params);
		
		if ($id > 0) {
			$params = $hooks -> apply_filters( "projectwork_editfilter", $params );
		}
		else {
			$params = $hooks -> apply_filters( "projectwork_addfilter", $params );
		}
		
		//настройки модуля
		$mdcset      = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'projects' and identity = '$identity'" );
		$mdcsettings = json_decode( $mdcset['content'], true );
		
		$wrk  = self ::getWorkInfo( $id );
		$work = $wrk['work'];
		
		$prj     = self ::info( (int)$work['idproject'] );
		$project = $prj['project'];
		
		// Получим старые данные о работе
		$oldParams = [
			"idproject"  => $work['idproject'],
			"type"       => $work['type'],
			"name"       => $work['name'],
			"datum"      => $work['datum'],
			"date_start" => $work['date_start'],
			"date_end"   => $work['date_end'],
			"date_fact"  => !empty( $work['date_fact'] ) ? $work['date_fact'] : null,
			"comment"    => $work['comment'],
			"status"     => $work['status'],
			"content"    => $work['content'],
			"iduser"     => $work['iduser'],
			"workers"    => $work['workers']
		];
		
		//if ( isset( $params['worker'] ) )
		//$params['workers'] = (is_array( $params['worker'] )) ? yimplode( ',', $params['worker'] ) : $params['worker'];
		
		$params['identity'] = $identity;
		
		// Обновление записи
		if ($id > 0 && $work['id'] > 0) {
			
			//print_r($params);
			
			//фильтруем ключи
			$data = $db -> filterArray( $params, array_keys( self::FIELDSWORK ) );
			
			//print_r($data);
			
			//обновляем проект
			$db -> query( "UPDATE {$sqlname}projects_work SET ?u WHERE id = '$id' and identity = '$identity'", $data );
			//print $db -> lastQuery();
			
			$response['result'] = 'Данные работы обновлены';
			$response['data']   = $id;
			
			$data['id'] = $id;
			
			if ($hooks) {
				$hooks -> do_action( "projectwork_edit", $post, $data );
			}
			
			//что изменилось
			$new       = self ::getWorkInfo( $id );
			$newParams = $new['work'];
			
			unset( $newParams['datum'] );
			
			$diff = array_keys( array_diff_ext( $oldParams, $newParams ) );
			
			$text = [];
			
			foreach ($diff as $key) {
				
				$item = $oitem = [];
				
				switch ($key) {
					
					case "type":
						
						$item[$key]  = $db -> getOne( "SELECT title FROM {$sqlname}projects_work_types WHERE id = '".$newParams[$key]."' and identity = '$identity'" );
						$oitem[$key] = $db -> getOne( "SELECT title FROM {$sqlname}projects_work_types WHERE id = '".$oldParams[$key]."' and identity = '$identity'" );
					
					break;
					case "iduser":
					case "autor":
						
						$item[$key]  = current_user( (int)$newParams[$key] );
						$oitem[$key] = current_user( (int)$oldParams[$key] );
					
					break;
					case "workers":
						
						$oldParams[$key] = (is_array( $oldParams[$key] )) ? $oldParams[$key] : yexplode( ",", (string)$oldParams[$key] );
						$newParams[$key] = (is_array( $newParams[$key] )) ? $newParams[$key] : yexplode( ",", (string)$newParams[$key] );
						
						$plist = $polist = [];
						
						foreach ($newParams[$key] as $user) {
							$plist[] = current_user( $user );
						}
						
						foreach ($oldParams[$key] as $user) {
							$polist[] = current_user( $user );
						}
						
						$item[$key]  = yimplode( "; ", $plist );
						$oitem[$key] = yimplode( "; ", $polist );
					
					break;
					case "content":
					case "comment":
						
						$item[$key]  = nl2br( $newParams[$key] );
						$oitem[$key] = nl2br( $oldParams[$key] );
					
					break;
					case "status":
						
						$item[$key]  = self ::getStatusesWork( $newParams[$key] )['name'];//strtr( $newParams[ $key ], self::STATUSWORK );
						$oitem[$key] = self ::getStatusesWork( $oldParams[$key] )['name'];//strtr( $oldParams[ $key ], self::STATUSWORK );
					
					break;
					default:
						
						$item[$key]  = $newParams[$key];
						$oitem[$key] = $oldParams[$key];
					
					break;
					
				}
				
				if ($oitem[$key] == '' || (is_numeric( $oitem[$key] ) && $oitem[$key] == 0)) {
					$oitem[$key] = 'не указано';
				}
				
				$text[] = '
					<div class="gray uppercase Bold fs-07">'.strtr( $key, self::FIELDSPROJECT ).'</div>
					<div class="mb10"><b>'.$item[$key].'</b> <div class="inline gray">[&nbsp;было - '.$oitem[$key].'&nbsp;]</div></div>
				';
				
			}
			
			/**
			 * Отправляем уведомление
			 */
			self ::sendNotify( 'work.edit', [
				"id"      => $id,
				"type"    => 'work',
				"content" => yimplode( "", $text ),
				"iduser"  => $iduser1
			] );
			
			//если изменен статус
			if ($oldParams['status'] != $params['status'] && $params['nolog'] != 'yes') {
				
				self ::statusWork( $id, [
					'status'  => $params['status'],
					'project' => $params['idproject'],
					'comment' => untag( $params['comment'] ),
				] );
				
			}
			
		}
		
		// Добавление новой записи
		else {
			
			$params['iduser'] = $iduser1;
			$params['datum']  = current_datum();
			
			if ($params['name'] == '') {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Отсутствуют параметры - Название работы";
				
			}
			else {
				
				//фильтруем ключи
				$data = $db -> filterArray( $params, array_keys( self::FIELDSWORK ) );
				
				$db -> query( "INSERT INTO {$sqlname}projects_work SET ?u", $data );
				$id = $db -> insertId();
				
				$response['result'] = 'Работа успешно добавлена';
				$response['data']   = $id;
				
				$data['id'] = $id;
				
				if ($hooks) {
					$hooks -> do_action( "projectwork_add", $post, $data );
				}
				
				$params['comment'] = ($params['comment'] != '') ? $params['comment'] : 'Статус изменен при редактировании';
				
				if (!isset( $params['noemail'] ) || $params['noemail'] != 'yes') {
					
					//подгружаем теги
					$tags = self ::getTags( $id, 'work' );
					
					//Отправка уведомления Исполнителям работы
					$workers = array_unique( yexplode( ",", $params['workers'] ) );
					foreach ($workers as $worker) {
						
						if (in_array( $worker, $mdcsettings['projNotify'] )) {
							
							$user   = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='$worker'" );
							$author = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='$iduser1'" );
							
							// Данные отправителя
							$from     = 'no-replay@'.$_SERVER['HTTP_HOST'];
							$fromname = $productInfo['name'];
							$subject  = 'Добавлена новая работа';
							
							// Берем шаблон сообщения из БД
							$html = htmlspecialchars_decode( $db -> getOne( "SELECT content FROM {$sqlname}tpl WHERE tip = 'addWork' and identity = '$identity'" ) );
							
							// Данные Ответственного за Проект
							$tags['projectUserName']  = $author['title'];
							$tags['projectUserPhone'] = $author['phone'];
							$tags['projectUserMob']   = $author['mob'];
							$tags['projectUserEmail'] = $author['email'];
							
							// Данные о работе
							//$tags['workName']     = $work['name'];
							//$tags['workStatus']   = $work['status'];
							//$tags['workDatePlan'] = $work['date_end'];
							$tags['workUser'] = $user['title'];
							
							// Данные о Проекте
							//$tags['projectName']     = $project['name'];
							//$tags['projectStatus']   = $project['status'];
							//$tags['projectDatePlan'] = $project['date_end'];
							//$tags['link']            = '<a href="'.$productInfo['crmurl'].'/card.projects.php?id='.$project['id'].'" style="text-decoration: none;"> Ссылка</a>';
							
							foreach ($tags as $tag => $val) {
								
								$html = str_replace( "{".$tag."}", $val, $html );
								
							}
							
							// Отправка письма
							mailto( [
								$user['email'],
								$user['title'],
								$from,
								$fromname,
								$subject,
								$html
							] );
							
						}
					}
					
				}
				
				/**
				 * Отправляем уведомление
				 */
				self ::sendNotify( 'work.add', [
					"id"     => $id,
					"type"   => 'work',
					"iduser" => $iduser1
				] );
				
			}
			
		}
		
		/**
		 * Корректируем даты всего Проекта в зависимости от дат работ, если разрешено в настройках
		 */
		if ($mdcsettings['periodCorrects'] == 'yes') {
			
			//находим минимальную и максимальную даты работ и устанавливаем их в Проект
			$range = $db -> getRow( "SELECT MIN(date_start) as dmin, MAX(date_end) as dmax FROM {$sqlname}projects_work WHERE idproject = '$work[idproject]' and status != '3' and identity = '$identity'" );
			
			if (strtotime( $project['date_start'] ) > strtotime( $range['dmin'] )) {
				$db -> query( "UPDATE {$sqlname}projects SET date_start = '".$range['dmin']."' WHERE id = '$work[idproject]' and identity = '$identity'" );
			}
			
			if (strtotime( $project['date_end'] ) < strtotime( $range['dmax'] )) {
				$db -> query( "UPDATE {$sqlname}projects SET date_end = '".$range['dmax']."' WHERE id = '$work[idproject]' and identity = '$identity'" );
			}
			
		}
		
		//Проверим статус проекта
		//Если проект имеет статус 0 - Новый, а работа статус 1 - В работе, то переводим проект в статус 1 - В работе
		$currentStatus = $db -> getOne( "SELECT status FROM {$sqlname}projects WHERE id = '$work[idproject]' and identity = '$identity'" ) + 0;
		if ($currentStatus == 0 && $wrk['status'] > 0) {
			
			self ::statusProject( $work['idproject'], [
				'date_fact' => current_datum(),
				'status'    => 1,
				'comment'   => 'Статус изменен с началом первой Работы'
			] );
			
			//$db -> query("UPDATE {$sqlname}projects SET status = '1' WHERE id = '$work[idproject]' and identity = '$identity'");
			
		}
		
		return $response;
		
	}
	
	/**
	 * Удаление работы
	 *
	 * @param int $id - идентификатор записи работы
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно удалено
	 *         - [data] = id
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 *
	 * code:
	 *          403 - Работа с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id работы
	 *
	 * @example $Work = \Salesman\Project::deleteWork($id);
	 */
	public static function deleteWork(int $id = 0): array {
		
		global $hooks;
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		
		if ($id > 0) {
			
			$count = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}projects_work WHERE id = '$id' and identity = '$identity'" ) + 0;
			
			//проверка на существование проекта
			if ($count == 0) {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Работа с указанным id не найдена";
				
			}
			else {
				
				//удаляем работу
				$db -> query( "delete from {$sqlname}projects_work WHERE id = '".$id."' and identity = '$identity'" );
				
				//удаляем задания
				$db -> query( "delete from {$sqlname}projects_task WHERE idwork = '".$id."' and identity = '$identity'" );
				
				if ($hooks) {
					$hooks -> do_action( "projectwork_delete", $id );
				}
				
				$response['result'] = 'Работа удалена';
				$response['data']   = $id;
				
			}
		}
		else {
			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id работы";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Изменение статуса Проекта
	 *
	 * @param       $id
	 * @param array $params
	 *   int status - новый статус проекта
	 *   date date_fact - текущая дата
	 *   string comment - комментарий
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно
	 *         - [message] = Сообщения
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 * @throws Exception
	 */
	public static function statusProject($id, array $params = []): array {
		
		global $hooks;
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $GLOBALS['iduser1'];
		
		$id = (int)$id;
		
		$post = $params;
		
		$params = $hooks -> apply_filters( "project_statusfilter", $params );
		
		$response = [];
		$mes      = [];
		
		$productInfo = $GLOBALS['productInfo'];
		
		//настройки модуля
		$mdcset      = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'projects' and identity = '$identity'" );
		$mdcsettings = json_decode( $mdcset['content'], true );
		
		//пришедшие параметры
		$status    = (int)$params['status'];
		$date_fact = untag( $params['date_fact'] );
		$comment   = untag( $params['comment'] );
		
		/**
		 * Финальные статусы
		 */
		$final       = self ::getFinalStatuses();
		$statusFinal = $final['prj'];//$db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND isfinal = 'true' AND identity = '$identity'" );
		//$statusFinalWork = $final['wrk'];//$db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND isfinal = 'true' AND identity = '$identity'" );
		
		/**
		 * Финальные статусы с победой
		 */
		$winfinal           = self ::getWinStatuses();
		$statusWinFinal     = $winfinal['prj'];//$db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND isfinal = 'true' AND identity = '$identity'" );
		$statusWinFinalWork = $winfinal['wrk'];//$db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND isfinal = 'true' AND identity = '$identity'" );
		
		/**
		 * Отмененные статусы
		 */
		$cancel           = self ::getCancelStatuses();
		$statusCancel     = $cancel['prj'];//$db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND iscancel = 'true' AND identity = '$identity'" );
		$statusCancelWork = $cancel['wrk'];//$db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND iscancel = 'true' AND identity = '$identity'" );
		
		$prj = self ::info( (int)$id );
		
		if ($prj['project']['id'] > 0) {
			
			if ($status > 0) {
				
				$sts = [
					'date_fact' => $date_fact,
					'status'    => $status,
					'comment'   => $comment
				];
				
				if (!in_array( $status, $statusFinal )) {
					
					unset( $sts['date_fact'] );
					
				}
				
				//print_r($sts);
				
				//обновляем проект
				$r     = self ::edit( $id, $sts );
				$mes[] = $r['result'];
				
				$sts['id'] = $id;
				
				if ($hooks) {
					$hooks -> do_action( "project_status", $post, $sts );
				}
				
				/*
				$db -> query("UPDATE {$sqlname}projects SET ?u WHERE id = '$id' and identity = '$identity'", [
					'date_fact' => $date_fact,
					'status'    => $status,
					'comment'   => $comment
				]);
				*/
				
				//записываем в лог
				$db -> query( "INSERT INTO {$sqlname}projects_statuslog SET ?u", [
					'date'     => current_datumtime(),
					'status'   => $status,
					'project'  => $id,
					'comment'  => $comment,
					'iduser'   => (int)$iduser1,
					'identity' => $identity
				] );
				
				//количество открытых работ-
				$countOpen = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}projects_work WHERE idproject = '$id' and status NOT IN (".yimplode( ",", $statusFinal ).") and identity = '$identity'" );
				
				//если есть открытые работы и мы закрываем проект
				if ($countOpen > 0 && in_array( $status, $statusWinFinal )) {
					
					//$wf = $db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND isfinal = 'true' AND iscancel = 'true' AND identity = '$identity'" );
					
					$db -> query( "UPDATE {$sqlname}projects_work SET ?u WHERE idproject = '$id' and status NOT IN (".yimplode( ",", $statusFinal ).") and identity = '$identity'", [
						'status'    => end( $statusWinFinalWork ),
						'date_fact' => $date_fact
					] );
					
					$mes[] = 'Новый статус проекта - Выполнен. Все не завершенные работы отмечены выполненными';
					
					//изменяем этап сделки, если настроено
					if ($mdcsettings['projChangeNewStep'] > 0) {
						
						$did = $prj['project']['did'];
						
						$deal = new Deal();
						$rez  = $deal -> changestep( $did, [
							"step"        => $mdcsettings['projChangeNewStep'],
							"description" => "Выполнен проект: ".$prj['project']['name']
						] );
						
						$mes[] = "Смена этапа сделки: ".$rez['result'];
						
					}
					
				}
				
				//если есть открытые работы и мы отменяем проект
				if ($countOpen > 0 && in_array( $status, $statusFinal ) && in_array( $status, $statusCancel )) {
					
					$db -> query( "UPDATE {$sqlname}projects_work SET ?u WHERE idproject = '$id' and status NOT IN (".yimplode( ",", $statusFinal ).") and identity = '$identity'", [
						'status'    => end( $statusCancelWork ),
						'date_fact' => $date_fact
					] );
					
					$mes[] = 'Новый статус проекта - Отменен. Все не завершенные работы отменены';
					
				}
				
				// Отправка уведомления при выполнении проекта
				//if ( in_array($status, $statusFinal) ) {
				
				// данные Проекта
				$project = $prj['project'];
				
				//$user   = $db -> getRow("SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='".$project['iduser']."'");
				$author = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='".$project["author"]."'" );
				
				// Данные отправителя
				$from     = 'no-replay@'.$_SERVER['HTTP_HOST'];
				$fromname = $productInfo['name'];
				
				$subject = "Изменен статус Проекта";
				
				// шаблон проекта
				$html = htmlspecialchars_decode( $db -> getOne( "SELECT content FROM {$sqlname}tpl WHERE tip = 'doProject' and identity = '$identity'" ) );
				
				if (in_array( $status, $statusFinal )) {
					
					$subject = !in_array( $status, $statusCancel ) ? 'Проект выполнен' : 'Проект отменен';
					
				}
				else {
					
					$html = str_replace( "Проект выполнен!", "Изменился статус проекта, новый статус - {projectStatus}", $html );
					
				}
				
				$tags = self ::getTags( $id );
				
				// Данные Ответственного за Проект
				$tags['projectUserName']  = $author['title'];
				$tags['projectUserPhone'] = $author['phone'];
				$tags['projectUserMob']   = $author['mob'];
				$tags['projectUserEmail'] = $author['email'];
				
				// Данные о Проекте
				//$tags['projectName']     = $project['name'];
				//$tags['projectStatus']   = $project['status'];
				//$tags['projectDatePlan'] = $project['date_end'];
				//$tags['projectStatus']   = $project['status'];
				//$tags['link']            = '<a href="'.$productInfo['crmurl'].'/card.projects.php?id='.$project['id'].'" style="text-decoration: none;"> Ссылка</a>';
				
				foreach ($tags as $tag => $val) {
					
					$html = str_replace( "{".$tag."}", $val, $html );
					
				}
				
				// Отправка письма
				mailto( [
					$author['email'],
					$author['title'],
					$from,
					$fromname,
					$subject,
					$html
				] );
				
				//}
				
				$response['result']  = 'Успешно';
				$response['message'] = yimplode( "; ", $mes );
				
				/**
				 * Отправляем уведомление
				 */
				self ::sendNotify( 'project.status', [
					"id"      => $id,
					"type"    => 'project',
					"content" => yimplode( "<br>", $mes ),
					"iduser"  => $iduser1
				] );
				
			}
			else {
				
				$response['result']        = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Не указан параметр - Статус";
				
			}
			
		}
		else {
			
			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Проект не найден";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Изменение статуса Работы
	 *
	 * @param       $id
	 * @param array $params
	 *    status
	 *    date_fact
	 *    comment
	 *
	 * @return array
	 * good result
	 *         - [result] = Успешно
	 *         - [message] = Сообщения
	 *
	 * error result
	 *         - [result] = result
	 *         - [error][code]
	 *         - [error][text]
	 * @throws Exception
	 */
	public static function statusWork($id, array $params = []): array {
		
		global $hooks;
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $GLOBALS['iduser1'];
		
		$post = $params;
		
		$id = (int)$id;
		
		$params = $hooks -> apply_filters( "projectwork_statusfilter", $params );
		
		$response = [];
		$mes      = [];
		
		$productInfo = $GLOBALS['productInfo'];
		
		//настройки модуля
		$mdcset      = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'projects' and identity = '$identity'" );
		$mdcsettings = json_decode( $mdcset['content'], true );
		
		/**
		 * Финальные статусы
		 */
		$statusFinal     = $db -> getCol( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND isfinal = 'true' AND identity = '$identity'" );
		$statusFinalWork = $db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND isfinal = 'true' AND identity = '$identity'" );
		
		/**
		 * Отмененные статусы
		 */
		//$statusCancel     = $db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'prj' AND iscancel = 'true' AND identity = '$identity'" );
		$statusCancelWork = $db -> getOne( "SELECT id FROM {$sqlname}projects_status WHERE type = 'wrk' AND iscancel = 'true' AND identity = '$identity'" );
		
		//параметры узла
		$statusWork   = (int)$params['status'];
		$date_fact    = untag( $params['date_fact'] );
		$comment      = untag( $params['comment'] );
		$current_date = current_datumtime();
		//$oldStatus    = $params['oldStatus'];
		
		$res  = self ::getWorkInfo( $id );
		$item = $res['work'];
		
		$statusesProject = self ::getStatusesProject();
		//$statusesWork = self::getStatusesWork();
		
		if ($item['id'] > 0) {
			
			//текущий статус
			$oldStatusWork = $item['status'];
			
			// данные Проекта
			$res2    = self ::info( (int)$item['idproject'] );
			$project = $res2['project'];
			
			//$tags['projectName'] = $project['name'];
			
			//Проверим статус проекта
			//Если проект имеет статус 0 - Новый, а работа статус 1 - В работе, то переводим проект в статус 1 - В работе
			$currentStatusProject = $project['status'];
			
			if ($currentStatusProject == $statusesProject[0]['index'] /*&& $item[ 'status' ] != $statusesWork[0]['index']*/) {
				
				//обновляем статус Проекта до "В работе"
				$r     = self ::edit( $project['id'], ["status" => self ::getNextStatusProject( $project['status'] )] );
				$mes[] = $r['result'];
				
				/*
				$r = self ::statusProject($project['id'], [
					"status"    => '1',
					"date_fact" => current_datum(),
					"comment"   => "Изменение статуса проекта вместе со статусом Работы ".$item['name'],
					"iduser"    => $iduser1,
					'noemail'   => 'yes',
					'nolog'     => 'yes',
				]);
				*/
				
				$mes[] = "Изменен статус проекта. Текущий статус - ".self ::getStatusesProject( $currentStatusProject )['name'];
				
			}
			
			/**
			 * Если проект ещё не завершен
			 */
			if (!in_array( $currentStatusProject, $statusFinal['prj'] )) {
				
				$r     = self ::updateWork( $id, $sts = [
					'date_fact' => $date_fact,
					'status'    => $statusWork,
					'comment'   => $comment,
					'noemail'   => 'yes',
					'nolog'     => 'yes',
				] );
				$mes[] = $r['result'];
				
				$sts['id'] = $id;
				
				if ($hooks) {
					$hooks -> do_action( "projectwork_status", $post, $sts );
				}
				
				/*
				$db -> query("UPDATE {$sqlname}projects_work SET ?u WHERE id = '$id' and identity = '$identity'", [
					'date_fact' => $date_fact,
					'status'    => $statusWork,
					'comment'   => $comment
				]);
				*/
				
				//записываем в лог
				$db -> query( "INSERT INTO {$sqlname}projects_statuslog SET ?u", [
					'date'     => $current_date,
					'status'   => $statusWork,
					'project'  => $item['idproject'],
					'work'     => $id,
					'comment'  => $comment,
					'iduser'   => $iduser1,
					'identity' => $identity
				] );
				
				$tpl = (!in_array( $statusWork, $statusCancelWork ) && in_array( $statusWork, $statusFinalWork )) ? 'doWork' : 'statusWork';
				
				// Берем шаблон сообщения из БД
				$html = htmlspecialchars_decode( $db -> getOne( "SELECT content FROM {$sqlname}tpl WHERE tip = '$tpl' and identity = '$identity'" ) );
				
				$tags = self ::getTags( $item['id'], 'work' );
				
				// Данные о работе
				$tags['statusOld']     = self ::getStatusesWork( $item['status'] )['name'];//strtr( $item[ 'status' ], self::STATUSWORK );
				$tags['statusNew']     = self ::getStatusesWork( $statusWork )['name'];//strtr( $statusWork, self::STATUSWORK );
				$tags['workCloseText'] = $comment;
				
				//print_r($tags);
				
				//Отправка уведомления
				$workers = yexplode( ',', (string)$item['workers'] );
				foreach ($workers as $worker) {
					
					if (in_array( $worker, $mdcsettings['projNotify'] )) {
						
						$user   = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser = '$worker'" );
						$author = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser = '".$item["iduser"]."'" );
						
						// Данные отправителя
						$from     = 'no-replay@'.$_SERVER['HTTP_HOST'];
						$fromname = $productInfo['name'];
						
						if (!in_array( $statusWork, $statusCancelWork ) && in_array( $statusWork, $statusFinalWork )) {
							
							$subject = 'Работа выполнена';
							$to      = $author['email'];
							$toName  = $author['title'];
							
						}
						elseif (in_array( $statusWork, $statusCancelWork )) {
							
							$subject = 'Работа отменена';
							$to      = $author['email'];
							$toName  = $author['title'];
							
						}
						else {
							
							$subject = 'Изменен статус Работы';
							$to      = $user['email'];
							$toName  = $user['title'];
							
						}
						
						// Данные Ответственного за Проект
						$tags['projectUserName']  = $author['title'];
						$tags['projectUserPhone'] = $author['phone'];
						$tags['projectUserMob']   = $author['mob'];
						$tags['projectUserEmail'] = $author['email'];
						
						$tags['workUser'] = $user['title'];
						
						foreach ($tags as $tag => $val) {
							
							$html = str_replace( "{".$tag."}", $val, $html );
							
						}
						
						// Отправка письма
						mailto( [
							$to,
							$toName,
							$from,
							$fromname,
							$subject,
							$html
						] );
						
					}
					
				}
				
				/**
				 * Отправляем уведомление
				 */
				self ::sendNotify( 'work.status', [
					"id"        => $id,
					"type"      => 'work',
					"content"   => yimplode( "<br>", $mes ),
					"statusOld" => $oldStatusWork,
					"status"    => $statusWork,
					"iduser"    => $iduser1
				] );
				
			}
			else {
				
				$mes[] = "Статус не изменен - Проект завершен";
				
			}
			
			$response['result']  = 'Успешно';
			$response['message'] = yimplode( "<br>", $mes );
			
		}
		else {
			
			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Работа не найдена";
			
		}
		
		return $response;
		
	}
	
	/**
	 * Подготовка тегов для шаблонов email
	 *
	 * @param int    $id
	 * @param string $type
	 *
	 * @return mixed
	 */
	public static function getTags(int $id = 0, string $type = 'project'): array {
		
		$rootpath = dirname( __DIR__, 2 );
		
		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		
		$sqlname     = $GLOBALS['sqlname'];
		$db          = $GLOBALS['db'];
		$fpath       = $GLOBALS['fpath'];
		$productInfo = $GLOBALS['productInfo'];
		
		$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
		$settings     = json_decode( file_get_contents( $settingsFile ), true );
		
		//print file_get_contents( $settingsFile );
		//print_r($settings);
		
		$tags['compName']  = $settings["company"];
		$tags['compSite']  = $settings["company_site"];
		$tags['compMail']  = $settings["company_mail"];
		$tags['compPhone'] = $settings["company_phone"];
		
		$tags['currentDatum']      = format_date_rus_name( current_datum() );
		$tags['currentDatumShort'] = format_date_rus( current_datum() );
		
		if ($id > 0) {
			
			if ($type == 'project') {
				
				$prj     = self ::info( $id );
				$project = $prj['project'];
				
				//print_r($project);
				
				//Данные о компании
				if ($project['did'] > 0) {
					
					$mcid    = getDogData( $project['did'], 'mcid' );
					$company = $db -> getRow( "SELECT * FROM {$sqlname}mycomps WHERE id = '$mcid'" );
					
					$tags['compName']   = $company["name_shot"];
					$tags['compUrName'] = $company["name_ur"];
					
				}
				
				// Данные о Проекте
				$tags['projectName']     = $project['name'];
				$tags['projectStatus']   = self ::getStatusesProject( $project['status'] )['name'];
				$tags['projectDatePlan'] = $project['date_end'];
				$tags['link']            = '<a href="'.$productInfo['crmurl'].'/card.projects?id='.$id.'" style="text-decoration: none;"> Ссылка</a>';
				
			}
			else {
				
				$wrk  = self ::getWorkInfo( $id );
				$work = $wrk['work'];
				
				$prj     = self ::info( (int)$work['idproject'] );
				$project = $prj['project'];
				
				//Данные о компании
				if ($project['did'] > 0) {
					
					$mcid    = getDogData( $project['did'], 'mcid' );
					$company = $db -> getRow( "SELECT * FROM {$sqlname}mycomps WHERE id = '$mcid'" );
					
					$tags['compName']   = $company["name_shot"];
					$tags['compUrName'] = $company["name_ur"];
					
				}
				
				// Данные о работе
				$tags['workName']     = $work['name'];
				$tags['workStatus']   = self ::getStatusesWork( $work['status'] )['name'];//$work[ 'status' ];
				$tags['workDatePlan'] = $work['date_end'];
				
				// Данные о Проекте
				$tags['projectName']     = $project['name'];
				$tags['projectStatus']   = self ::getStatusesProject( $project['status'] )['name'];//$project[ 'status' ];
				$tags['projectDatePlan'] = $project['date_end'];
				$tags['link']            = '<a href="'.$productInfo['crmurl'].'/card.projects?id='.$project['id'].'" style="text-decoration: none;"> Ссылка</a>';
				
			}
			
		}
		
		return $tags;
		
	}

	/**
	 * Отправка уведомлений через систему Нотификации
	 *
	 * @param       $event -
	 *                     project.add, project.edit, project.status
	 *                     work.add, work.edit, work.status
	 *
	 * @param array $params
	 *                     type - тип события: project, work [ обязательное]
	 *                     id - ID Проекта или Работы [обязательное ]
	 *                     title - Заголовок  [ не обязательное ]
	 *                     content - Содержание [ желательное ]
	 *
	 * @return array
	 * @throws \Exception
	 */
	public static function sendNotify($event, array $params = []): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		require_once $rootpath."/class/Notify.php";
		
		$iduser1 = $GLOBALS['iduser1'];
		
		$tag = $r = [];
		
		$tag['users'] = [];
		
		if ($params['id'] > 0) {
			
			//если уведомление для проекта
			if ($params['type'] == 'project') {
				
				$prj = self ::info( (int)$params['id'] );
				
				//print_r($prj);
				
				$tag['users'] = [];
				
				switch ($event) {
					
					case 'project.add':
						
						$tag['url']     = "openProject('".$params['id']."');";
						$tag['title']   = 'Новый проект - '.($params['title'] != '' ? $params['title'] : $prj['project']['name']);
						$tag['content'] = ($params['content'] != '') ? $params['content'] : $prj['project']['content'];
						$tag['tip']     = 'project';
						$tag['id']      = $prj['project']['id'];
						
						if ($prj['project']['iduser'] != $iduser1) {
							$tag['users'][] = $prj['project']['iduser'];
						}
						
						$tag['users'][] = $params['iduser'];
						$tag['users'][] = $iduser1;
					
					break;
					case 'project.edit':
						
						//если изменения внес не Ответственный
						$tag['url']     = "openProject('".$params['id']."');";
						$tag['title']   = 'Изменен проект - '.($params['title'] != '' ? $params['title'] : $prj['project']['name']);
						$tag['content'] = ($params['content'] != '') ? $params['content'] : $prj['project']['comment'];
						$tag['tip']     = 'project';
						$tag['uid']     = $params['id'];
						
						if ($prj['project']['iduser'] != $iduser1) {
							$tag['users'][] = $prj['project']['iduser'];
						}
						$tag['users'][] = $params['iduser'];
						$tag['users'][] = $iduser1;
					
					break;
					case 'project.status':
						
						//если изменения внес не Ответственный
						$tag['url']     = "openProject('".$params['id']."');";
						$tag['title']   = 'Изменен статус проекта - '.($params['title'] != '' ? $params['title'] : $prj['project']['name']);
						$tag['content'] = ($params['content'] != '') ? $params['content'] : $prj['project']['comment'];
						$tag['tip']     = 'project';
						$tag['uid']     = $params['id'];
						
						if ($params['iduser'] != $prj['project']['iduser']) {
							$tag['users'][] = $prj['project']['iduser'];
						}
						$tag['users'][] = $prj['project']['author'];
						$tag['users'][] = $params['iduser'];
						$tag['users'][] = $iduser1;
					
					break;
					
				}
				
				if (!empty( $tag['users'] )) {
					$r = Notify ::fire( "self", $iduser1, $tag );
				}
				
			}
			elseif ($params['type'] == 'work') {
				
				$wrk = self ::getWorkInfo( $params['id'] );
				$prj = self ::info( (int)$wrk['work']['idproject'] );
				
				switch ($event) {
					
					case 'work.add':
						
						$tag['url']     = "openProject('".$wrk['work']['idproject']."');";
						$tag['title']   = 'Новая работа - '.($params['title'] != '' ? $params['title'] : $wrk['work']['name']);
						$tag['content'] = ($params['content'] != '') ? $params['content'] : $wrk['work']['content'];
						$tag['tip']     = 'project';
						$tag['id']      = $wrk['work']['idproject'];
						
						$tag['users'] = yexplode( ",", (string)$wrk['work']['workers'] );
					
					break;
					case 'work.edit':
						
						//если изменения внес не Ответственный
						$tag['url']     = "openProject('".$wrk['work']['idproject']."');";
						$tag['title']   = 'Изменена работа - '.($params['title'] != '' ? $params['title'] : $wrk['work']['name']);
						$tag['content'] = ($params['content'] != '') ? $params['content'] : $wrk['work']['comment'];
						$tag['tip']     = 'project';
						$tag['uid']     = $wrk['work']['idproject'];
						
						$tag['users'] = yexplode( ",", (string)$wrk['work']['workers'] );
					
					break;
					case 'work.status':
						
						//если изменения внес не Ответственный
						$tag['url']     = "openProject('".$wrk['work']['idproject']."');";
						$tag['content'] = ($params['content'] != '') ? $params['content'] : $wrk['work']['comment'];
						$tag['tip']     = 'project';
						$tag['uid']     = $wrk['work']['idproject'];
						
						/*
						if ( $params[ 'statusWork' ] == '2' ) {

							$tag[ 'title' ]   = 'Изменен статус Работ -> На проверку. '.$wrk[ 'work' ][ 'name' ];
							$tag[ 'users' ][] = $wrk[ 'work' ][ 'author' ];

						}
						else {*/
						
						//$tag[ 'title' ]   = 'Изменен статус Работ -> '.( $params[ 'status' ] == '1' && $params[ 'statusOld' ] == '2' ? "На доработку" : strtr( $params[ 'status' ], self::STATUSWORK ) ).'. '.$wrk[ 'work' ][ 'name' ];
						
						$tag['title']   = 'Изменен статус Работ -> '.self ::getStatusesWork( $params['status'] )['name'].'. '.$wrk['work']['name'];
						$tag['users'][] = $wrk['work']['iduser'];
						
						//}
						
						$tag['users'][] = $prj['project']['iduser'];
					
					break;
					
				}
				
				if (!empty( $tag['users'] )) {
					$r = Notify ::fire( "self", $iduser1, $tag );
				}
				
			}
			
		}
		
		return [
			"result" => $r,
			"data"   => $tag
		];
		
	}
	
	/**
	 * Информация по Задаче. Если 0, то подготавливает данные для формы Задачи для новой
	 *
	 * @param int   $id
	 * @param array $params
	 *                     - idproject
	 *                     - idwork
	 *
	 * @return mixed
	 */
	public static function taskInfo(int $id = 0, array $params = []): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		$iduser1  = $GLOBALS['iduser1'];
		
		if ($id > 0) {
			
			$q = "
			SELECT 
				{$sqlname}projects_task.id,
				{$sqlname}projects_task.tid,
				{$sqlname}projects_task.idwork,
				DATE_FORMAT({$sqlname}projects_task.datum, '%Y-%m-%d') as datum,
				DATE_FORMAT({$sqlname}projects_task.datum, '%H:%i') as time,
				{$sqlname}projects_task.comment,
				{$sqlname}projects_task.workers,
				{$sqlname}projects.id as idproject,
				{$sqlname}projects.name as project,
				{$sqlname}dogovor.did as did,
				{$sqlname}dogovor.title as dogovor,
				{$sqlname}clientcat.clid as clid,
				{$sqlname}clientcat.title as client,
				{$sqlname}projects_work.status as workstatus,
				{$sqlname}projects_work.id as idwork,
				{$sqlname}projects_work.name as workname,
				{$sqlname}tasks.tip as tip,
				{$sqlname}tasks.iduser as iduser
			FROM {$sqlname}projects_task
				LEFT JOIN {$sqlname}projects_work ON {$sqlname}projects_work.id = {$sqlname}projects_task.idwork
				LEFT JOIN {$sqlname}tasks ON {$sqlname}tasks.tid = {$sqlname}projects_task.tid
				LEFT JOIN {$sqlname}projects ON {$sqlname}projects.id = {$sqlname}projects_work.idproject
				LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}projects.did
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
			WHERE 
				{$sqlname}projects_task.id = '$id' and
				{$sqlname}projects_task.identity = '$identity'
			";
			
			$task = $db -> getRow( $q );
			
			$task['datumtime'] = $task['datum']." ".$task['time'];
			
		}
		else {
			
			$task['idproject'] = (int)$params['idproject'];
			$task['idwork']    = (int)$params['idwork'];
			$task['tip']       = "Задача";
			$task['project']   = $task['idproject'] > 0 ? $db -> getOne( "SELECT name FROM {$sqlname}projects WHERE id = '".$task['idproject']."' and identity = '$identity'" ) : 0;
			$task['work']      = $task['idwork'] > 0 ? $db -> getOne( "SELECT name FROM {$sqlname}projects_work WHERE id = '".$task['idwork']."' and identity = '$identity'" ) : 0;
			
			$task['iduser'] = $iduser1;
			$task['datum']  = current_datumtime();
			
			$t                 = getDateTimeArray( $task['datum'] );
			$task['datumtime'] = $t['Y']."-".$t['m'].'-'.$t['d'].' '.$t['H'].':'.$t['i'];
			
		}
		
		return $task;
		
	}
	
	/**
	 * Редактирование напоминания
	 *
	 * @param int   $id
	 * @param array $params
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function taskEdit(int $id = 0, array $params = []): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		
		//print_r($params);
		
		$param['tid']     = (int)$params['tid'];
		$param['idwork']  = (int)$params['idwork'];
		$param['datum']   = untag( $params['datum'] );
		$tip              = untag( $params['tip'] );
		$iduser           = (int)$params['iduser'];
		$param['comment'] = untag( $params['comment'] );
		$param['workers'] = yimplode( ",", $params['workers'] );
		
		$workInfo = self ::getWorkInfo( $param['idwork'] );
		$work     = $workInfo['work'];
		$t        = getDateTimeArray( $param['datum'] );
		
		$taskRez = [];
		
		if ($id > 0) {
			
			$taskParam = [
				"datum"  => $t['Y']."-".$t['m'].'-'.$t['d'],
				"totime" => $t['H'].':'.$t['i'].':00',
				"tip"    => $tip,
				"title"  => "Задание по работе ".$work['name'],
				"des"    => $param['comment'],
				"clid"   => $work['clid'],
				"did"    => $work['did']
			];
			
			if ($param['tid'] < 1) {
				
				$task    = new Todo();
				$taskRez = $task -> add( $iduser, $taskParam );
				
				$param['tid'] = $taskRez['id'];
				
			}
			else {
				
				$taskParam['users'][] = $iduser;
				
				$task    = new Todo();
				$taskRez = $task -> edit( $param['tid'], $taskParam );
				
			}
			
			$db -> query( "UPDATE {$sqlname}projects_task SET ?u WHERE id = '$id' and identity = '$identity'", $param );
			
		}
		else {
			
			$param['identity'] = $identity;
			
			if ($param['tid'] < 1) {
				
				$taskParam = [
					"datum"  => $t['Y']."-".$t['m'].'-'.$t['d'],
					"totime" => $t['H'].':'.$t['i'].':00',
					"tip"    => "Задача",
					"title"  => "Задание по работе ".$work['name'],
					"des"    => $param['comment'],
					"clid"   => $work['clid'],
					"did"    => $work['did'],
					"iduser" => $iduser
				];
				
				$task    = new Todo();
				$taskRez = $task -> add( $iduser, $taskParam );
				
				$param['tid'] = $taskRez['id'];
				
			}
			$db -> query( "INSERT INTO {$sqlname}projects_task SET ?u", $param );
			//$id = $db -> insertId();
			
		}
		
		return $taskRez;
		
	}
	
	/**
	 * Список шаблонов проектов
	 *
	 * @return array
	 */
	public static function getTemplates(): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		
		$templates = [];
		
		$users = User ::userList( 0 );
		
		$list = $db -> getAll( "SELECT * FROM {$sqlname}projects_templates WHERE identity = '$identity'" );
		foreach ($list as $item) {
			
			$templates[] = [
				"id"        => (int)$item['id'],
				"title"     => $item['title'],
				"datum"     => $item['datum'],
				"autor"     => $item['autor'],
				"autorName" => $users[$item['autor']],
				"state"     => (int)$item['state'],
				"works"     => json_decode( $item['content'], true )
			];
			
		}
		
		return $templates;
		
	}
	
	/**
	 * Шаблон проекта
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public static function getTemplate(int $id = 0): array {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		
		if ($id > 0) {
			
			$item = $db -> getRow( "SELECT * FROM {$sqlname}projects_templates WHERE id = '$id' AND identity = '$identity'" );
			
			return [
				"id"    => (int)$item['id'],
				"title" => $item['title'],
				"datum" => $item['datum'],
				"autor" => $item['autor'],
				"state" => (int)$item['state'],
				"works" => json_decode( $item['content'], true )
			];
			
		}
		
		return [];
		
	}
	
	/**
	 * Добавление/Обновление шаблона
	 *
	 * @param int   $id
	 * @param array $params
	 *
	 * @return int
	 */
	public static function setTemplate(int $id = 0, array $params = []): int {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		global $sqlname, $db, $identity, $iduser1;
		
		if ($id > 0) {
			
			$db -> query( "UPDATE {$sqlname}projects_templates SET ?u WHERE id = '$id'", [
				"autor"   => $iduser1,
				"title"   => $params['title'],
				"state"   => (int)$params['state'],
				"content" => is_array( $params['content'] ) ? json_encode_cyr( $params['content'] ) : $params['content']
			] );
			
		}
		else {
			
			$db -> query( "INSERT INTO {$sqlname}projects_templates SET ?u", [
				"autor"    => $iduser1,
				"title"    => $params['title'],
				"state"    => (int)$params['state'],
				"content"  => is_array( $params['content'] ) ? json_encode_cyr( $params['content'] ) : $params['content'],
				"identity" => $identity
			] );
			$id = $db -> insertId();
			
		}
		
		return $id;
		
	}
	
	/**
	 * Переключатель статуса активности шаблона
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	public static function stateToggleTemplate(int $id = 0): int {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		global $sqlname, $db, $identity, $iduser1;
		
		$tpl = self ::getTemplate( $id );
		
		$state = $tpl['state'] == 0 ? 1 : 0;
		
		$db -> query( "UPDATE {$sqlname}projects_templates SET ?u WHERE id = '$id'", [
			"state" => $state
		] );
		
		return $id;
		
	}
	
	/**
	 * Удаление шаблона проекта
	 *
	 * @param int $id
	 *
	 * @return int
	 */
	public static function deleteTemplate(int $id = 0): int {
		
		$rootpath = dirname( __DIR__ );
		
		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";
		
		global $sqlname, $db;
		
		$db -> query( "DELETE FROM {$sqlname}projects_templates WHERE id = '$id'" );
		
		return $id;
		
	}
	
}