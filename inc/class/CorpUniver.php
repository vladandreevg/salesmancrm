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

use Exception;

/**
 * Класс для работы с объектами модуля "Корпоративный университет"
 *
 * Class CorpUniver
 *
 * @package     Salesman
 * @author      Ivan Drachev
 * @co-author   Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example
 * ```
 * $CorpUniver = \Salesman\CorpUniver::info($id);
 * ```
 */
class CorpUniver {

	/**
	 * Иконки материалов
	 */
	public const ICONMATERIAL = [
		"video"    => "icon-youtube red",
		"audio"    => "icon-volume-up broun",
		"image"    => "icon-picture orange",
		"resource" => "icon-link-1 green",
		"text"     => "icon-list-nested blue",
		"file"     => "icon-attach-1 fiolet",
		"mpeg"     => "icon-file-video blue"
	];

	/**
	 * Сайты с видео
	 */
	public const VIDEOSITE = [
		'youtube',
		'youtu.be',
		'vimeo.com',
		'rutube.ru',
		'myvi.tv',
		'vimple.ru',
		'wistia.com',
		'brightcove.com',
		'sproutvideo.com',
		'oculu.com'
	];

	/**
	 * @var array
	 */
	public $response = [];

	/**
	 * Список материалов и заданий по курсу. Основной список
	 *
	 * @param      $id
	 * @param null $iduser
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function courseConstructor($id, $iduser = NULL): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$isadmin  = $GLOBALS['isadmin'];
		$iduser   = (int)$iduser > 0 ? $iduser : (int)$GLOBALS['iduser1'];
		$db       = $GLOBALS['db'];

		$id     = (int)$id;
		$iduser = (int)$iduser;

		$mdcset      = $db -> getRow("SELECT * FROM {$sqlname}modules WHERE mpath = 'corpuniver' and identity = '$identity'");
		$mdcsettings = json_decode($mdcset['content'], true);

		$Cource = self ::info($id)['data'];

		$change = ( $Cource['author'] == $iduser || $isadmin == 'on' || ( in_array($iduser, $mdcsettings['Editor'], true) && !in_array($iduser, $mdcsettings['EditorMy'], true) ) ) ? 'yes' : '';

		$lections = self ::listLections($id)['data'];

		$way = self ::infoWayCource(["idcourse" => $id]);

		$Lec = [];

		$lastLec = $lastMat = $lastTask = 0;

		// список лекций
		foreach ($lections as $num0 => $lec) {

			$lastLec = $lec['id'];

			$Mat = $Task = [];

			// материалы лекции
			$materials = self ::listMaterials($lec['id'])['data'];

			foreach ($materials as $num1 => $mat) {

				$mclass = $mtitle = '';

				$isDo = $db -> getRow("SELECT datum, datum_end FROM {$sqlname}corpuniver_coursebyusers WHERE idlecture = '$lec[id]' AND idmaterial = '$mat[id]' AND iduser = '$iduser' AND identity = '$identity'");

				if (!is_null($isDo['datum'])) {
					$mclass = 'isstart';
				}
				if (!is_null($isDo['datum_end'])) {
					$mclass = 'isend';
				}

				if (!is_null($isDo['datum'])) {
					$mtitle .= 'Начат с '.get_sfdate($isDo['datum']);
				}
				if (!is_null($isDo['datum_end'])) {
					$mtitle .= '; Пройден '.get_sfdate($isDo['datum_end']);
				}

				$icon = self ::iconBySource($mat['source']);

				if (!$icon && ( $mat['type'] == "file" || $mat['type'] == "efile" )) {

					$file = Upload ::info($mat['fid']);

					if (!in_array($file['ext'], [
						'avi',
						'mp4',
						'mpeg'
					])) {
						$icon = get_icon3($file['file']);
					}
					else {
						$icon = self::ICONMATERIAL['mpeg'];
					}

				}

				$site = parse_url($mat['source']);

				$Mat[] = [
					"idmaterial" => $mat['id'],
					"num"        => $num1,
					"name"       => $mat['name'],
					"idlection"  => $lec['id'],
					"text"       => $mtitle,
					"source"     => $mat['source'] ? : NULL,
					"host"       => $mat['source'] ? $site['host'] : NULL,
					"class"      => $mclass,
					"icon"       => $icon ? : strtr($mat['type'], self::ICONMATERIAL),
					"isStart"    => !is_null($isDo['datum']) ? true : NULL,
					"isEnd"      => !is_null($isDo['datum_end']) ? true : NULL,
					"do"         => $isDo
				];

				$lastMat = $mat['id'];

			}

			// задания лекции
			$tasks = self ::listTasks($lec['id'])['data'];

			foreach ($tasks as $num2 => $task) {

				$mtitle    = $mclass = $icon = $title = '';
				$rezlt     = $rez = [];
				$testCount = 0;

				if ($task['type'] == 'test') {

					$icon  = "icon-th orange";
					$title = "Тест";

					$testCount = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_questions WHERE task = '$task[id]'");

				}
				elseif ($task['type'] == 'question') {

					$icon  = "icon-help red";
					$title = "Вопрос";

				}

				$isDo = $db -> getRow("SELECT datum, datum_end FROM {$sqlname}corpuniver_coursebyusers WHERE idlecture = '$lec[id]' AND idtask = '$task[id]' AND iduser = '$iduser' AND identity = '$identity'");

				if (!is_null($isDo['datum'])) {
					$mclass = 'isstart';
				}
				if (!is_null($isDo['datum_end'])) {
					$mclass = 'isend';
				}

				if (!is_null($isDo['datum'])) {
					$mtitle .= 'Начат с '.get_sfdate($isDo['datum']);
				}
				if (!is_null($isDo['datum_end'])) {
					$mtitle .= '; Пройден '.get_sfdate($isDo['datum_end']);
				}

				if (!is_null($isDo['datum_end'])) {

					// в тесте несколько вопросов, у каждого верный ответ
					$q = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_questions WHERE task = '$task[id]'");
					foreach ($q as $query) {

						// ответ пользователя
						$answr = $db -> getOne("SELECT answer FROM {$sqlname}corpuniver_useranswers WHERE parent = '$query[id]'");

						// правильный ответ
						$answrGood = $db -> getOne("SELECT text FROM {$sqlname}corpuniver_answers WHERE question = '$query[id]' and status = '1'");

						$rezlt[] = [
							"title"      => ( $answr == $answrGood ) ? "Верный ответ" : "Ответ не верный",
							"query"      => trim($query['text']),
							"comment"    => "Вопрос:\n".$query['text']."\n\n\Ответ сотрудника:\n".$answr."n".( $answr == $answrGood ? "Верный ответ" : "Ответ не верный. \nВерный ответ: ".$answrGood ),
							"icon"       => ( $answr == $answrGood ) ? 'icon-ok-circled green' : 'icon-block-1 red',
							"isGood"     => ( $answr == $answrGood ) ? true : NULL,
							"answer"     => $answr,
							"answerGood" => $answrGood
						];

					}

				}

				$Task[] = [
					"idtask"    => $task['id'],
					"num"       => $num2,
					"idlection" => $lec['id'],
					"name"      => $task['name'],
					"title"     => $mtitle,
					"class"     => $mclass,
					"icon"      => $icon,
					"text"      => $title,
					"rezult"    => $rezlt,
					"isStart"   => !is_null($isDo['datum']) ? true : NULL,
					"isEnd"     => !is_null($isDo['datum_end']) ? true : NULL,
					"do"        => $isDo,
					"testCount" => $testCount > 0 ? $testCount : NULL
				];

				$lastTask = $task['id'];

			}

			$progress = self ::progressLecture($lec['id']);

			$x = (float)$progress['progress'] * 100;

			$Lec[] = [
				"idlection"       => $lec['id'],
				"num"             => $num0,
				"name"            => $lec['name'],
				"material"        => $Mat,
				"materialCount"   => count($Mat),
				"task"            => $Task,
				"taskCount"       => count($Task),
				"progressLecture" => $progress['progress'] ? num_format($x, 1) : NULL
			];

		}

		$progress = self ::progressCource($id);

		return [
			"id"           => $id,
			"change"       => $change,
			"name"         => $Cource['name'],
			"date_edit"    => $Cource['date_edit'],
			"edited"       => ( !is_null($Cource['date_edit']) ) ? 'Изменен '.get_sfdate($Cource['date_edit']) : 'Создан '.format_date_rus($Cource['date_create']),
			"autor"        => ( $Cource['date_edit'] != NULL ) ? current_user($Cource['editor'], 'yes') : current_user($Cource['author'], 'yes'),
			"user"         => current_user($iduser),
			"startText"    => ( !$way['isStart'] ) ? "Начать изучение" : "Продолжить изучение",
			"count"        => count($lections) > 0 ? 1 : NULL,
			"lection"      => $Lec,
			"lectionCount" => count($Lec),
			"progress"     => ( $progress['progress'] < 1 ) ? true : NULL,
			"lastLec"      => $lastLec,
			"lastMat"      => $lastMat,
			"lastTask"     => $lastTask,
		];

	}

	/**
	 * Получение информации о курсе
	 *
	 * @param int $id - идентификатор курса
	 *
	 * @return array "Course"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Курс с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id курса
	 *
	 * Example
	 *
	 * ```
	 * $Course = \Salesman\CorpUniver::info($id);
	 * ```
	 */
	public static function info(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_course WHERE id = '$id' AND identity = '$identity'") + 0;

			if ($count > 0) {

				$course = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_course WHERE id = '$id' AND identity = '$identity'");

				if (in_array($course['date_edit'], [
					'0000-00-00 00:00:00',
					NULL
				])) {

					$course['date_edit'] = $course['date_create']." 00:00:00";
					$course['editor']    = $course['author'];

				}

				$response['result'] = 'Success';
				$response['data']   = [
					"id"          => (int)$course['id'],
					"name"        => $course['name'],
					"cat"         => (int)$course['cat'],
					"des"         => $course['des'],
					"date_create" => $course['date_create'],
					"date_edit"   => $course['date_edit'],
					"editor"      => $course['editor'],
					"fid"         => $course['fid'],
					"author"      => $course['author'],
				];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Курс с указанным id не найден";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id курса";

		}

		return $response;

	}

	/**
	 * Получение списка лекций курса
	 *
	 * @param int $id - идентификатор курса
	 *
	 * @return array "Lections"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example
	 *
	 * ```php
	 * $Lections = \Salesman\CorpUniver::listLections($id);
	 * ```
	 */
	public static function listLections(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$Lections = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_lecture WHERE course = '$id' AND identity = '$identity' ORDER BY ord");

		$response['result'] = 'Success';
		$response['data']   = $Lections;

		return $response;

	}

	/**
	 * Информация по прохождению Курса/Лекции/Материала
	 *
	 * @param array $params
	 *                      - int **idcourse** - id курса
	 *                      - int **idlecture** - id лекции
	 *                      - int **idmaterial** - id матриала
	 *                      - int **idtask** - id теста
	 *
	 * @return array|bool
	 */
	public static function infoWayCource(array $params = []) {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser   = $GLOBALS['iduser1'];

		$idcourse   = $params['idcourse'];
		$idlecture  = $params['idlecture'];
		$idmaterial = $params['idmaterial'];
		$idtask     = $params['idtask'];

		if (!empty($params)) {

			$sort = '';

			if ($idcourse > 0) {
				$sort .= " AND idcourse = '$idcourse'";
			}

			if ($idlecture > 0) {
				$sort .= " AND idlecture = '$idlecture'";
			}

			if ($idmaterial > 0) {
				$sort .= " AND idmaterial = '$idmaterial'";
			}

			if ($idtask > 0) {
				$sort .= " AND idtask = '$idtask'";
			}

			$d = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_coursebyusers WHERE iduser = '$iduser' $sort AND identity = '$identity'");

			return [
				"id"         => $d['id'],
				"idcourse"   => $d['idcourse'],
				"idlecture"  => $d['idlecture'],
				"idmaterial" => $d['idmaterial'],
				"idtask"     => $d['idtask'],
				"iduser"     => $d['iduser'],
				"datum"      => $d['datum'],
				"datum_end"  => $d['datum_end'],
				"isStart"    => !is_null($d['datum']),
				"isEnd"      => !is_null($d['datum_end']),
			];

		}
		else {
			return false;
		}

	}

	/**
	 * Получение списка материалов лекции
	 *
	 * @param int $id - идентификатор лекции
	 *
	 * @return array "Materials"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example
	 *
	 * ```php
	 * $Materials = \Salesman\CorpUniver::listMaterials($id);
	 * ```
	 */
	public static function listMaterials(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$materials = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_material WHERE lecture = '$id' AND identity = '$identity' ORDER BY ord");

		$response['result'] = 'Success';
		$response['data']   = $materials;

		return $response;

	}

	/**
	 * Получение списка заданий лекции
	 *
	 * @param int $id - идентификатор лекции
	 *
	 * @return array "Tasks"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example
	 *
	 * ```php
	 * $Tasks = \Salesman\CorpUniver::listTasks($id);
	 * ```
	 */
	public static function listTasks(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$Tasks = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_task WHERE lecture = '$id' AND identity = '$identity' ORDER BY ord");

		$response['result'] = 'Success';
		$response['data']   = $Tasks;

		return $response;

	}

	/**
	 * Прогресс выполнения лекции пользователем
	 *
	 * @param     $id
	 * @param int $iduser
	 *
	 * @return array
	 */
	public static function progressLecture($id, int $iduser = 0): ?array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		//$identity = $GLOBALS['identity'];
		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];
		$iduser  = $iduser > 0 ? $iduser : (int)$GLOBALS['iduser1'];

		if ($id > 0) {

			// Прохождение материалов
			$count['MaterialsTotal'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_material WHERE lecture = '$id'");

			$count['MaterialsDo'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_coursebyusers WHERE idlecture = '$id' AND datum_end IS NOT NULL AND idmaterial > 0 AND iduser = '$iduser'");

			// Прохождение заданий
			$count['TasksTotal'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_task WHERE lecture = '$id'");

			$count['TasksDo'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_coursebyusers WHERE idlecture = '$id' AND datum_end IS NOT NULL AND idmaterial = 0 AND idtask > 0 and iduser = '$iduser'");

			$count['progress'] = (float)( $count['MaterialsTotal'] + $count['TasksTotal'] ) > 0 ? ( $count['MaterialsDo'] + $count['TasksDo'] ) / ( $count['MaterialsTotal'] + $count['TasksTotal'] ) : 0;

			return $count;

		}

		return [
			"MaterialsTotal" => 0,
			"MaterialsDo"    => 0,
			"TasksTotal"    => 0,
			"TasksDo"       => 0,
			"progress"      => 0,
		];

	}

	/**
	 * Прогресс курса
	 *
	 * @param     $id
	 * @param int $iduser
	 *
	 * @return array
	 */
	public static function progressCource($id, int $iduser = 0): ?array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		//$identity = $GLOBALS['identity'];
		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];
		$iduser  = $iduser > 0 ? $iduser : (int)$GLOBALS['iduser1'];

		if ($id > 0) {

			// Прохождение Лекций
			$count['LecturesTotal'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_lecture WHERE course = '$id'");

			$count['LecturesDo'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_coursebyusers WHERE idcourse = '$id' AND idlecture IN (SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$id') AND datum_end IS NOT NULL AND idmaterial = 0 AND idtask = 0 and iduser = '$iduser'");

			// Прохождение материалов
			$count['MaterialsTotal'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_material WHERE lecture IN (SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$id')");

			$count['MaterialsDo'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_coursebyusers WHERE idcourse = '$id' AND datum_end IS NOT NULL AND idmaterial > 0 AND iduser = '$iduser'");

			// Прохождение заданий
			$count['TasksTotal'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_task WHERE lecture IN (SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$id')");

			$count['TasksDo'] = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_coursebyusers WHERE idcourse = '$id' AND datum_end IS NOT NULL AND idlecture IN (SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$id') AND idmaterial = 0 AND idtask > 0 and iduser = '$iduser'");

			$count['progressMaterial'] = (int)$count['MaterialsTotal'] > 0 ? ( $count['MaterialsDo'] ) / ( $count['MaterialsTotal'] ) : 0;

			$count['progress'] = ( $count['MaterialsTotal'] + $count['TasksTotal'] ) > 0 ? ( $count['MaterialsDo'] + $count['TasksDo'] ) / ( $count['MaterialsTotal'] + $count['TasksTotal'] ) : 0;

			$count['progressTotal'] = (float)( $count['LecturesTotal'] + $count['MaterialsTotal'] + $count['TasksTotal'] ) > 0 ? ( $count['LecturesDo'] + $count['MaterialsDo'] + $count['TasksDo'] ) / ( $count['LecturesTotal'] + $count['MaterialsTotal'] + $count['TasksTotal'] ) : 0;

			return $count;

		}

		return [];

	}

	/**
	 * Вывод материалов курса при прохождении
	 *
	 * @param     $id
	 * @param int $iduser
	 *
	 * @return array
	 */
	public static function courseList($id, int $iduser = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$iduser1 = $iduser > 0 ? $iduser : $GLOBALS['iduser1'];
		$db      = $GLOBALS['db'];

		//$cource   = self ::info( $id );
		$lections = self ::listLections($id)['data'];
		$progress = self ::progressCource($id);

		$slide            = 0;
		$currentSlide     = 0;
		$currentSlideType = 'material';
		$currentLecture   = 0;
		$lecturelist      = [];

		// Первый не пройденный материал
		$currentMaterial = (int)$db -> getOne("
			SELECT 
				{$sqlname}corpuniver_material.id as id
			FROM {$sqlname}corpuniver_material 
				LEFT JOIN {$sqlname}corpuniver_coursebyusers ON {$sqlname}corpuniver_coursebyusers.idmaterial = {$sqlname}corpuniver_material.id
			WHERE 
				{$sqlname}corpuniver_material.lecture IN (SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$id') AND 
				{$sqlname}corpuniver_coursebyusers.datum IS NOT NULL AND
				{$sqlname}corpuniver_coursebyusers.datum_end IS NULL AND
				{$sqlname}corpuniver_coursebyusers.iduser = '$iduser1'
			ORDER BY {$sqlname}corpuniver_material.ord 
			LIMIT 1
		");

		$currentTask = (int)$db -> getOne("
			SELECT 
				{$sqlname}corpuniver_task.id as id
			FROM {$sqlname}corpuniver_task 
				LEFT JOIN {$sqlname}corpuniver_coursebyusers ON {$sqlname}corpuniver_coursebyusers.idtask = {$sqlname}corpuniver_task.id
			WHERE 
				{$sqlname}corpuniver_task.lecture IN (SELECT id FROM {$sqlname}corpuniver_lecture WHERE course = '$id') AND 
				{$sqlname}corpuniver_coursebyusers.datum IS NOT NULL AND
				{$sqlname}corpuniver_coursebyusers.datum_end IS NULL AND
				{$sqlname}corpuniver_coursebyusers.iduser = '$iduser1'
			ORDER BY {$sqlname}corpuniver_task.ord 
			LIMIT 1
		");

		// Вывод лекций
		foreach ($lections as $i => $lec) {

			$materialist = $tasklist = [];

			// Вывод материалов
			$materials = self ::listMaterials($lec['id'])['data'];

			foreach ($materials as $mat) {

				$way = self ::infoWayCource(["idmaterial" => $mat['id']]);

				$icon = self ::iconBySource($mat['source']);

				$materialist[] = [
					"slide"   => $slide,
					"id"      => $mat['id'],
					"name"    => $mat['name'],
					"icon"    => $icon ? $icon : strtr($mat['type'], self::ICONMATERIAL),
					"isStart" => $way['isStart'] ? get_sfdate($way['datum']) : NULL,
					"isEnd"   => $way['isEnd'] ? get_sfdate($way['datum_end']) : NULL,
				];

				if ($currentMaterial > 0 && $mat['id'] == $currentMaterial) {

					$currentSlide     = $slide;
					$currentSlideType = 'material';
					$currentLecture   = $lec['id'];

				}

				$slide++;

			}

			// Вывод заданий
			$tasks = self ::listTasks($lec['id'])['data'];

			if (!empty($tasks)) {

				$icon = $title = '';

				foreach ($tasks as $tsk) {

					if ($tsk['type'] == 'test') {

						$icon  = "icon-th orange";
						$title = "Тест";

					}
					elseif ($tsk['type'] == 'question') {

						$icon  = "icon-help red";
						$title = "Вопрос";

					}

					if ($currentTask > 0 && $tsk['id'] == $currentTask) {

						$currentSlide     = $slide;
						$currentSlideType = 'task';
						$currentLecture   = $lec['id'];

					}

					$way = self ::infoWayCource(["idtask" => $tsk['id']]);

					$tasklist[] = [
						"slide"   => $slide,
						"id"      => $tsk['id'],
						"name"    => $tsk['name'],
						"icon"    => $icon,
						"title"   => $title,
						"isStart" => $way['isStart'] ? get_sfdate($way['datum']) : NULL,
						"isEnd"   => $way['isEnd'] ? get_sfdate($way['datum_end']) : NULL,
					];

					$slide++;

				}

			}

			// формируем лекцию
			if (!empty($materialist) || !empty($tasks)) {
				$lecturelist[] = [
					"title"     => 'Лекция '.( ++$i ),
					"lecture"   => $lec['name'],
					"num"       => ( --$i ),
					"materials" => $materialist,
					"haveTask"  => !empty($tasklist) ? true : NULL,
					"tasks"     => $tasklist
				];
			}

		}

		return [
			"lecturelist"      => $lecturelist,
			"current"          => $currentMaterial,
			"currentTask"      => $currentTask,
			"currentSlide"     => $currentSlide,
			"currentSlideType" => $currentSlideType,
			"currentLecture"   => $currentLecture,
			"maxSlide"         => $slide,
			"progress"         => ( (int)$progress['progressTotal'] == 0 ) ? true : NULL
		];

	}

	/**
	 * Добавление/изменение курса
	 *
	 * @param int $id - идентификатор курса
	 * @param array $params - данные курса
	 *
	 * @return array "Course"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Курс с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id курса
	 *          407 - Отсутствуют параметры - Название клиента
	 *
	 * Example
	 *
	 * ```php
	 * $Course = \Salesman\CorpUniver::edit($id, $params);
	 * ```
	 */
	public static function edit(int $id = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$iduser1  = $GLOBALS['iduser1'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$params['editor']    = $iduser1;
			$params['date_edit'] = current_datumtime();

			// Проверка на существование в БД
			$cid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_course WHERE id='$id' AND identity = '$identity'") + 0;

			//если это существующий курс
			if ($cid > 0) {

				$db -> query("UPDATE {$sqlname}corpuniver_course SET ?u WHERE id = '$id' AND identity = '$identity'", $params);

				$response['result'] = 'Данные курса обновлены';
				$response['data']   = $id;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Курс с таким id не существует!';

			}

		}
		else {

			$params['author']      = $iduser1;
			$params['date_create'] = current_datum();

			if ($params['name'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Отсутствуют параметры - Название курса";

			}
			else {


				$db -> query("INSERT INTO {$sqlname}corpuniver_course SET ?u", $params);
				$response['data']   = $db -> insertId();
				$response['result'] = 'Курс добавлен';

			}
		}

		return $response;

	}

	/**
	 * Удаление курса
	 *
	 * @param int $id - идентификатор курса
	 * @return array "Course"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Курс с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id курса
	 *
	 *Example
	 *
	 * ```php
	 * $Course = \Salesman\CorpUniver::delete($id);
	 * ```
	 */
	public static function delete(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_course WHERE id = '$id' AND identity = '$identity'") + 0;

			//проверка на существование курса
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Курс с указанным id не найден";

			}
			else {

				//удаляем курс
				$db -> query("delete from {$sqlname}corpuniver_course WHERE id = '".$id."' AND identity = '$identity'");

				//удаляем лекции
				$db -> query("delete from {$sqlname}corpuniver_lecture WHERE course = '".$id."' AND identity = '$identity'");

				$response['result'] = 'Курс удален';
				$response['data']   = $id;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id курса";

		}

		return $response;

	}

	/**
	 * Добавление/изменение категории(раздела) курсов
	 *
	 * @param int $id - идентификатор категории
	 * @param array $params - данные категории
	 * @return array "Category"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Категория с указанным id не найдена в пределах аккаунта
	 *          405 - Отсутствуют параметры - id категории
	 *          407 - Отсутствуют параметры - Название категории
	 *
	 * Example
	 *
	 * ```php
	 * $Category = \Salesman\CorpUniver::editCategory($id, $params);
	 * ```
	 */
	public static function editCategory(int $id = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		//$iduser1  = $GLOBALS['iduser1'];
		$db = $GLOBALS['db'];

		$subid = $params['subid'];
		$title = $params['title'];

		if ($id > 0) {

			// Проверка на существование в БД
			$cid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_course_cat WHERE id='$id' AND identity = '$identity'") + 0;

			//если это существующая категория
			if ($cid > 0) {

				$db -> query("UPDATE {$sqlname}corpuniver_course_cat SET title = '".$title."', subid = '".$subid."' WHERE id = '".$id."' AND identity = '$identity'");

				$response['data']   = $db -> insertId();
				$response['result'] = 'Категория изменена';

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Категория с таким id не существует!';

			}

		}
		else {

			if ($params['title'] == '') {

				$response['result']        = 'Error';
				$response['error']['code'] = '407';
				$response['error']['text'] = "Отсутствуют параметры - Название категории";

			}
			else {

				$db -> query("INSERT INTO {$sqlname}corpuniver_course_cat (id,subid,title,identity) values(null, '$subid', '$title','$identity')");

				$response['data']   = $db -> insertId();
				$response['result'] = 'Категория добавлена';

			}

		}

		return $response;

	}

	/**
	 * Добавление/изменение категории(раздела) курсов
	 *
	 * @param int $id - идентификатор категории
	 *
	 * @return array "Category"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Категория с указанным id не найдена в пределах аккаунта
	 *          405 - Отсутствуют параметры - id категории
	 *          407 - Отсутствуют параметры - Название категории
	 *
	 * Example
	 *
	 * ```php
	 * $Category = \Salesman\CorpUniver::editCategory($id, $params);
	 * ```
	 */
	public static function deleteCategory(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		//$iduser1  = $GLOBALS['iduser1'];
		$db = $GLOBALS['db'];

		if ($id > 0) {

			// Проверка на существование в БД
			$cid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_course_cat WHERE id='$id' AND identity = '$identity'") + 0;

			//если это существующая категория
			if ($cid > 0) {

				$db -> query("DELETE FROM {$sqlname}corpuniver_course_cat WHERE id = '".$id."' AND identity = '$identity'");
				$db -> query("UPDATE {$sqlname}corpuniver_course SET cat = '' WHERE cat = '".$id."' AND identity = '$identity'");

				$response['result'] = 'Категория удалена';

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Категория с таким id не существует!';

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id категории";

		}

		return $response;

	}

	/**
	 * Возвращает структуру каталога, но без вложения подкаталогов в основной каталог
	 *
	 * @param int $id
	 * @param int $level
	 * @param array $ures
	 *
	 * @return array
	 */
	public static function getCategories(int $id = 0, int $level = 0, array $corpures = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$sort     = $GLOBALS['sort'];
		$maxlevel = preg_replace("/[^0-9]/", "", $GLOBALS['maxlevel']);
		//$maxlevel = 5;

		global $corpures;

		$sort .= ( $id > 0 ) ? " and subid = '$id'" : " and subid = '0'";

		if ($maxlevel != '' && $level > $maxlevel) {
			goto la;
		}

		$re = $db -> query("SELECT * FROM {$sqlname}corpuniver_course_cat WHERE id > 0 $sort and identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			$corpures[] = [
				"id"    => (int)$da["id"],
				"title" => $da["title"],
				"level" => $level,
				"subid" => (int)$da["subid"]
			];

			if ((int)$da['id'] > 0) {

				$level++;
				self ::getCategories((int)$da['id'], $level);
				$level--;

			}

		}

		la:

		return (array)$corpures;

	}

	/**
	 * Рекрсивно возвращает массив со всеми категориями и подкатегориями.
	 * Можно задать стартовый id категории. Тогда будет возвращена только эта ветка
	 *
	 * @param int $id
	 * @param int $level
	 *
	 * @return array
	 */
	public static function getCatalog(int $id = 0, int $level = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$category = [];

		$sort = ( $id > 0 ) ? "subid = '$id' AND" : "subid = 0 AND";

		$re = $db -> query("SELECT * FROM {$sqlname}corpuniver_course_cat WHERE $sort identity = '$identity' ORDER BY title");
		while ($da = $db -> fetch($re)) {

			//найдем категории, в которых данная категория является главной
			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_course_cat WHERE subid = '$da[id]' AND identity = '$identity'");

			$subcat = ( $count > 0 ) ? self ::getCatalog($da['id'], $level + 1) : [];

			$category[(int)$da["id"]] = [
				"id"    => (int)$da["id"],
				"title" => $da["title"],
				"level" => $level,
				"subid" => (int)$da["subid"]
			];

			//если есть подкатегории, то добавим их рекурсивно
			if (!empty($subcat)) {
				$category[$da["id"]]["subid"] = $subcat;
			}

		}

		return $category;

	}

	/**
	 * Получение информации о лекции
	 *
	 * @param int $id - идентификатор лекции
	 * @return array "Lecture"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Лекция с указанным id не найдена в пределах аккаунта
	 *          405 - Отсутствуют параметры - id лекции
	 *
	 * Example
	 *
	 * ```php
	 * $Lecture = \Salesman\CorpUniver::infoLecture($id);
	 * ```
	 */
	public static function infoLecture(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_lecture WHERE id = '$id' AND identity = '$identity'") + 0;

			if ($count > 0) {

				$task = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_lecture WHERE id = '$id' AND identity = '$identity'");

				$response['result'] = 'Success';
				$response['data']   = [
					"id"     => $task['id'],
					"course" => $task['course'],
					"name"   => $task['name'],
					"ord"    => $task['ord'],
				];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Задание с указанным id не найдено";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id задания";

		}

		return $response;

	}

	/**
	 * Добавление/изменение лекции
	 *
	 * @param int $id - идентификатор лекции
	 * @param string|null $name - название лекции
	 * @param int $course - id курса
	 *
	 * @return array "Lecture"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Лекция с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id лекции
	 *
	 * Example
	 *
	 * ```php
	 * $Lecture = \Salesman\CorpUniver::editLecture($id, $name, $course);
	 * ```
	 */
	public static function editLecture(int $id = 0, string $name = NULL, int $course = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		//$iduser1  = $GLOBALS['iduser1'];
		$db = $GLOBALS['db'];

		if ($id > 0) {

			// Проверка на существование в БД
			$cid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_lecture WHERE id='$id' AND identity = '$identity'") + 0;

			//если это существующая лекция
			if ($cid > 0) {

				$db -> query("UPDATE {$sqlname}corpuniver_lecture SET name = '$name' WHERE id = '$id' AND identity = '$identity'");

				$response['result'] = 'Лекция изменена';
				$response['data']   = $id;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Лекция с таким id не существует!';

			}

		}
		else {

			$ord = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}corpuniver_lecture WHERE course = '$course' AND identity = '$identity'") + 1;

			$db -> query("INSERT INTO {$sqlname}corpuniver_lecture SET ?u", [
				'name'     => $name,
				'course'   => $course,
				'ord'      => $ord,
				'identity' => $identity
			]);

			$response['data']   = $db -> insertId();
			$response['result'] = 'Лекция добавлена';

		}

		return $response;

	}

	/**
	 * Удаление лекции
	 *
	 * @param int $id - идентификатор лекции
	 * @return array "Lecture"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Лекция с указанным id не найдена в пределах аккаунта
	 *          405 - Отсутствуют параметры - id лекции
	 *
	 * Example
	 *
	 * ```php
	 * $Lecture = \Salesman\CorpUniver::deleteLecture($id);
	 * ```
	 */
	public static function deleteLecture(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_lecture WHERE id = '$id' AND identity = '$identity'");

			//проверка на существование лекции
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Лекция с указанным id не найдена";

			}
			else {

				//удаляем лекцию
				$db -> query("delete from {$sqlname}corpuniver_lecture WHERE id = '".$id."' AND identity = '$identity'");

				$response['result'] = 'Лекция удалена';
				$response['data']   = $id;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id лекции";

		}

		return $response;

	}

	/**
	 * Получение информации о материале
	 *
	 * @param int $id - идентификатор материала
	 * @return array "Material"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Материал с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id материала
	 *
	 * Example
	 *
	 * ```php
	 * $Material = \Salesman\CorpUniver::infoMaterial($id);
	 * ```
	 */
	public static function infoMaterial(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser   = $GLOBALS['iduser1'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_material WHERE id = '$id' AND identity = '$identity'") + 0;

			if ($count > 0) {

				$material = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_material WHERE id = '$id' AND identity = '$identity'");
				$idcourse = (int)$db -> getOne("SELECT course FROM {$sqlname}corpuniver_lecture WHERE id = '$material[lecture]' AND identity = '$identity'");
				$last     = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}corpuniver_material WHERE lecture = '$material[lecture]' AND identity = '$identity'");

				// все материалы лекции по порядку
				$orders = $db -> getCol("SELECT id FROM {$sqlname}corpuniver_material WHERE lecture = '$material[lecture]' AND identity = '$identity' ORDER BY ord");

				// предыдущий материал
				$previouse = arrayPrev($id, $orders);

				// слудующий материал
				$next = arrayNext($id, $orders);

				$isDo = $db -> getRow("SELECT datum, datum_end FROM {$sqlname}corpuniver_coursebyusers WHERE idcourse = '$idcourse' AND idlecture = '$material[lecture]' AND idmaterial = '$id' AND iduser = '$iduser' AND identity = '$identity'");

				$response['result'] = 'Success';
				$response['data']   = [
					"id"        => $material['id'],
					"lecture"   => $material['lecture'],
					"course"    => $idcourse,
					"type"      => $material['type'],
					"name"      => $material['name'],
					"text"      => $material['text'],
					"source"    => $material['source'],
					"fid"       => $material['fid'],
					"ord"       => $material['ord'],
					"previouse" => $previouse,
					"next"      => $next,
					"isLast"    => !( ( $last != $material['ord'] ) ),
					"datum"     => $isDo['datum'],
					"datum_end" => $isDo['datum_end']
				];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Материал с указанным id не найден";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id материала";

		}

		return $response;

	}

	/**
	 * Добавление/изменение материала
	 *
	 * @param int $id - идентификатор материала
	 * @param array $params - данные материала
	 *
	 * @return array "Material"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Материал с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id материала
	 *          407 - Отсутствуют параметры - Название материала
	 *
	 * Example
	 *
	 * ```php
	 * $Material = \Salesman\CorpUniver::editMaterial($id, $params);
	 * ```
	 */
	public static function editMaterial(int $id = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			// Проверка на существование в БД
			$cid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_material WHERE id = '$id' AND identity = '$identity'") + 0;

			//если это существующий материал
			if ($cid > 0) {

				$db -> query("UPDATE {$sqlname}corpuniver_material SET ?u WHERE id = '$id' AND identity = '$identity'", ArrayNullClean($params));

				$response['result'] = 'Материал изменен';
				$response['data']   = $id;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Материал с таким id не существует!';

			}

		}
		else {

			$params['ord']      = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}corpuniver_material WHERE lecture = '".$params['lecture']."' AND identity = '$identity'") + 1;
			$params['identity'] = $identity;

			$db -> query("INSERT INTO {$sqlname}corpuniver_material SET ?u", $params);

			$response['data']   = $db -> insertId();
			$response['result'] = 'Материал добавлен';

		}

		return $response;

	}

	/**
	 * Удаление материала
	 *
	 * @param int $id - идентификатор материала
	 * @return array "Material"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Материал с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id материала
	 *
	 * Example
	 *
	 * ```php
	 * $Material = \Salesman\CorpUniver::deleteMaterial($id);
	 * ```
	 */
	public static function deleteMaterial(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_material WHERE id = '$id' AND identity = '$identity'");

			//проверка на существование материала
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Материал с указанным id не найден";

			}
			else {

				//удаляем материал
				$db -> query("delete from {$sqlname}corpuniver_material WHERE id = '".$id."' AND identity = '$identity'");

				$response['result'] = 'Учебный материал удален';
				$response['data']   = $id;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id материала";

		}

		return $response;

	}

	/**
	 * Получение информации о задании
	 *
	 * @param int $id - идентификатор задания
	 * @return array "Task"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Задание с указанным id не найдено в пределах аккаунта
	 *          405 - Отсутствуют параметры - id задания
	 *
	 * Example
	 *
	 * ```php
	 * $Task = \Salesman\CorpUniver::infoTask($id);
	 * ```
	 */
	public static function infoTask(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser   = $GLOBALS['iduser1'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_task WHERE id = '$id' AND identity = '$identity'") + 0;

			if ($count > 0) {

				$task     = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_task WHERE id = '$id' AND identity = '$identity'");
				$idcourse = $db -> getOne("SELECT course FROM {$sqlname}corpuniver_lecture WHERE id = '$task[lecture]' AND identity = '$identity'");
				$last     = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}corpuniver_task WHERE lecture = '$task[lecture]' AND identity = '$identity'");

				// все материалы лекции по порядку
				$orders = $db -> getCol("SELECT id FROM {$sqlname}corpuniver_task WHERE lecture = '$task[lecture]' AND identity = '$identity' ORDER BY ord");

				// предыдущий материал
				$previouse = arrayPrev($id, $orders);

				// слудующий материал
				$next = arrayNext($id, $orders);

				$isDo = $db -> getRow("SELECT datum, datum_end FROM {$sqlname}corpuniver_coursebyusers WHERE idcourse = '$idcourse' AND idlecture = '$task[lecture]' AND idtask = '$id' AND iduser = '$iduser' AND identity = '$identity'");

				$response['result'] = 'Success';
				$response['data']   = [
					"id"        => $task['id'],
					"lecture"   => $task['lecture'],
					"course"    => $idcourse,
					"type"      => $task['type'],
					"name"      => $task['name'],
					"fid"       => $task['fid'],
					"ord"       => $task['ord'],
					"previouse" => $previouse,
					"next"      => $next,
					"isLast"    => !( $last != $task['ord'] ),
					"datum"     => $isDo['datum'],
					"datum_end" => $isDo['datum_end']
				];

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Задание с указанным id не найдено";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id задания";

		}

		return $response;

	}

	/**
	 * Добавление/изменение задания
	 *
	 * @param int $id - идентификатор задания
	 * @param array $params - данные задания
	 *
	 * @return array "Material"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Задание с указанным id не найдено в пределах аккаунта
	 *          405 - Отсутствуют параметры - id задания
	 *          407 - Отсутствуют параметры - Название задания
	 *
	 * Example
	 *
	 * ```php
	 * $Task = \Salesman\CorpUniver::editTask($id, $params);
	 * ```
	 */
	public static function editTask(int $id = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			// Проверка на существование в БД
			$cid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_task WHERE id = '$id' AND identity = '$identity'");

			//если это существующее задание
			if ($cid > 0) {

				$db -> query("UPDATE {$sqlname}corpuniver_task SET ?u WHERE id = '$id' AND identity = '$identity'", ArrayNullClean($params));

				$response['result'] = 'Задание изменено';
				$response['data']   = $id;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Задание с таким id не существует!';

			}

		}
		else {

			$params['ord']      = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}corpuniver_task WHERE lecture = '".$params['lecture']."' AND identity = '$identity'") + 1;
			$params['identity'] = $identity;

			$db -> query("INSERT INTO {$sqlname}corpuniver_task SET ?u", $params);
			$id = $db -> insertId();

			//print $db -> lastQuery();
			//print "\nid = $id";

			$response['data']   = $id;
			$response['result'] = 'Задание добавлено';

		}

		return $response;

	}

	/**
	 * Удаление задания
	 *
	 * @param int $id - идентификатор задания
	 *
	 * @return array "Task"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Задание с указанным id не найдено в пределах аккаунта
	 *          405 - Отсутствуют параметры - id задания
	 *
	 * Example
	 *
	 * ```php
	 * $Task = \Salesman\CorpUniver::deleteTask($id);
	 * ```
	 */
	public static function deleteTask(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$task = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_task WHERE id = '$id' AND identity = '$identity'");

		if ((int)$task['id'] > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_task WHERE id = '$id' AND identity = '$identity'") + 0;

			if ($task['type'] == 'test') {

				$l = $db -> getCol("SELECT id FROM {$sqlname}corpuniver_questions WHERE task = '".$task['id']."' AND identity = '$identity'");

				foreach ($l as $question) {

					$db -> query("DELETE FROM {$sqlname}corpuniver_questions WHERE id = '".$question."' AND identity = '$identity'");
					$db -> query("DELETE FROM {$sqlname}corpuniver_answers WHERE question = '".$question."' AND identity = '$identity'");

				}

			}

			//проверка на существование задания
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Задание с указанным id не найдено";

			}
			else {

				//удаляем задание
				$db -> query("delete from {$sqlname}corpuniver_task WHERE id = '".$id."' AND identity = '$identity'");

				$response['result'] = 'Задание удалено';
				$response['data']   = $id;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id задания";

		}

		return $response;

	}

	/**
	 * Получение списка вопросов
	 *
	 * @param int $id - идентификатор задания
	 *
	 * @return array "Questions"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example
	 *
	 * ```php
	 * $Questions = \Salesman\CorpUniver::listQuestions($id);
	 * ```
	 */
	public static function listQuestions(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) // Вопросы задания
		{
			$quests = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_questions WHERE task = '$id' AND identity = '$identity' ORDER BY ord");
		}
		else // Все вопросы
		{
			$quests = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_questions WHERE identity = '$identity'");
		}

		$response['result'] = 'Success';
		$response['data']   = [];

		foreach ($quests as $q) {

			$response['data'][] = [
				"id"   => $q['id'],
				"text" => $q['text']
			];

		}

		return $response;

	}

	/**
	 * Получение информации о вопросе и ответов на него
	 *
	 * @param int $id - идентификатор задания
	 * @param int $tid - идентификатор задания
	 *
	 * @return array "Question"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example
	 *
	 * ```php
	 * $Question = \Salesman\CorpUniver::infoQuestion($id);
	 * ```
	 */
	public static function infoQuestion(int $id = 0, int $tid = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {
			$quest = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_questions WHERE id = '$id' AND identity = '$identity'");
		}
		else {
			$quest = $db -> getRow("SELECT * FROM {$sqlname}corpuniver_questions WHERE task = '$tid' AND identity = '$identity'");
		}

		$answers = $db -> getAll("SELECT * FROM {$sqlname}corpuniver_answers WHERE question = '".$quest['id']."' AND identity = '$identity'");

		$response['result'] = 'Success';

		$response['id']   = $quest['id'];
		$response['text'] = $quest['text'];

		foreach ($answers as $ans) {

			$response['answers'][] = [
				"id"     => $ans['id'],
				"text"   => $ans['text'],
				"status" => $ans['status'],
			];

		}

		return $response;

	}

	/**
	 * Добавление/изменение вопроса
	 *
	 * @param int $id - идентификатор вопроса
	 * @param array $params - данные вопроса
	 *
	 * @return array "Question"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Вопрос с указанным id не найден в пределах аккаунта
	 *          405 - Отсутствуют параметры - id вопроса
	 *
	 * Example
	 *
	 * ```php
	 * $Question = \Salesman\CorpUniver::editQuestion($id, $params);
	 * ```
	 */
	public static function editQuestion(int $id = 0, array $params = []): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			// Проверка на существование в БД
			$qid = (int)$db -> getOne("SELECT count(id) FROM {$sqlname}corpuniver_questions WHERE id='$id' AND identity = '$identity'") + 0;

			//если это существующий вопрос
			if ($qid > 0) {

				$db -> query("UPDATE {$sqlname}corpuniver_questions SET text='".$params['text']."' WHERE id = '$id' AND identity = '$identity'");

				$answers = $db -> getCol("SELECT id FROM {$sqlname}corpuniver_answers WHERE question='$id' AND identity = '$identity'");

				$answers = array_slice($answers, 0, count($params['answers']));

				if (!empty($answers)) {
					$db -> query("DELETE FROM {$sqlname}corpuniver_answers WHERE id NOT IN (".yimplode(',', $answers, "'").") AND question='$id' AND identity = '$identity'");
				}

				foreach ($params['answers'] as $i => $ans) {

					$status = ( $ans == $params['right'] ) ? 1 : 0;

					if ($i < count($answers)) {
						$db -> query("UPDATE {$sqlname}corpuniver_answers SET ?u WHERE id = '".$answers[$i]."' AND identity = '$identity'", [
							"text"     => $ans,
							"status"   => $status,
							"question" => $id,
							"identity" => $identity
						]);
					}
					else {
						$db -> query("INSERT INTO {$sqlname}corpuniver_answers SET ?u", [
							"text"     => $ans,
							"status"   => $status,
							"question" => $id,
							"identity" => $identity
						]);
					}

				}

				$response['result'] = 'Вопрос изменен';
				$response['data']   = $id;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = 'Вопрос с таким id не существует!';

			}

		}
		else {

			$ord = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}corpuniver_questions WHERE task = '".$params['idtask']."' AND identity = '$identity'") + 1;

			$db -> query("INSERT INTO {$sqlname}corpuniver_questions SET ?u", [
					"task"     => $params['idtask'],
					"text"     => untag3($params['text']),
					"ord"      => $ord,
					"identity" => $identity
				]);
			$id = $db -> insertId();

			foreach ($params['answers'] as $i => $ans) {

				$status = ( $ans == $params['right'] ) ? 1 : 0;

				$db -> query("INSERT INTO {$sqlname}corpuniver_answers SET ?u", [
					"text"     => $ans,
					"status"   => $status,
					"question" => $id,
					"identity" => $identity
				]);

			}

			$response['data']   = $id;
			$response['result'] = 'Вопрос добавлен';

		}

		return $response;

	}

	/**
	 * Удаление вопроса
	 *
	 * @param int $id - идентификатор вопроса
	 *
	 * @return array "Question"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *          403 - Задание с указанным id не найдено в пределах аккаунта
	 *          405 - Отсутствуют параметры - id задания
	 *
	 * Example
	 *
	 * ```php
	 * $Task = \Salesman\CorpUniver::deleteTask($id);
	 * ```
	 */
	public static function deleteQuestion(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ($id > 0) {

			$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}corpuniver_questions WHERE id = '$id' AND identity = '$identity'") + 0;

			//проверка на существование задания
			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Вопрос с указанным id не найден";

			}
			else {

				//удаляем вопрос
				$db -> query("DELETE FROM {$sqlname}corpuniver_questions WHERE id = '".$id."' AND identity = '$identity'");

				//удаляем варианты ответа
				$db -> query("DELETE FROM {$sqlname}corpuniver_answers WHERE question = '".$id."' AND identity = '$identity'");

				$response['result'] = 'Вопрос удален';
				$response['data']   = $id;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id задания";

		}

		return $response;

	}

	/**
	 * Получение списка вариантов ответа к вопросу
	 *
	 * @param int $id - идентификатор вопроса
	 *
	 * @return array "listAnswers"
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 *
	 * Example
	 *
	 * ```php
	 * $Answers = \Salesman\CorpUniver::listAnswers($id);
	 * ```
	 */
	public static function listAnswers(int $id = 0): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$response = [];

		$answers = $db -> getAll("SELECT id, text FROM {$sqlname}corpuniver_answers WHERE question = '$id' AND identity = '$identity'");

		foreach ($answers as $a) {

			$response[] = [

				"id"    => $a['id'],
				"title" => $a['text']

			];

		}

		return $response;

	}

	/**
	 * Отмечаем предыдущий материал выполненным
	 *
	 * @param     $id
	 * @param int $iduser
	 *
	 * @return bool
	 */
	public static function progressCheckWayCource($id, int $iduser = 0): ?bool {

		$iduser = $iduser > 0 ? $iduser : (int)$GLOBALS['iduser1'];

		if ($id > 0) {

			$material = self ::infoMaterial($id);

			$progress = self ::progressCource($material['cource'], $iduser);

			// предыдущий материал
			if ($material['data']['previouse'] > 0) {

				// статус выполнения предыдущего Материала
				$way = self ::infoWayCource([
					"idcourse"   => $material['cource'],
					"idlecture"  => $material['lecture'],
					"idmaterial" => $material['data']['previouse']
				]);

				// если он не отмечен законченным, то отмечаем законченным
				if (!$way['isEnd'] && $progress['progress'] == 1) {

					self ::startWayCource([
						"idcourse"   => $material['cource'],
						"idlecture"  => $material['lecture'],
						"idmaterial" => $material['data']['previouse'],
						"end"        => true
					]);

				}

				return true;

			}

			return false;

		}

		return false;

	}

	/**
	 * Отметка начала прохождения Курса/Лекции/Материала/Задачи и его окончания
	 *
	 * @param array $params - параметры
	 *                      - int **idcourse** - id курса
	 *                      - int **idlecture** - id лекции
	 *                      - int **idmaterial** - id матриала
	 *                      - int **idtask** - id теста
	 *                      - bool **start** = true, если это начало прохождения
	 *                      - bool **end** = true, если это окончание прохождения
	 *
	 * @param int $iduser
	 *
	 * @return bool
	 */
	public static function startWayCource(array $params = [], int $iduser = 0): ?bool {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser   = $iduser > 0 ? $iduser : (int)$GLOBALS['iduser1'];

		$idcourse   = (int)$params['idcourse'];
		$idlecture  = (int)$params['idlecture'];
		$idmaterial = (int)$params['idmaterial'];
		$idtask     = (int)$params['idtask'];

		if (!empty($params)) {

			$arg = [
				"idcourse"   => $idcourse,
				"idlecture"  => $idlecture,
				"idmaterial" => $idmaterial,
				"idtask"     => $idtask,
				"iduser"     => $iduser
			];

			$sort = '';

			if ($idcourse > 0) {
				$sort .= " AND idcourse = '$idcourse'";
			}

			if ($idlecture > 0) {
				$sort .= " AND idlecture = '$idlecture'";
			}

			if ($idmaterial > 0) {
				$sort .= " AND idmaterial = '$idmaterial'";
			}

			if ($idtask > 0) {
				$sort .= " AND idtask = '$idtask'";
			}

			$id = $db -> getOne("SELECT id FROM {$sqlname}corpuniver_coursebyusers WHERE iduser = '$iduser' $sort AND identity = '$identity'");

			// признак начала изучения Курса/Лекции/Материала
			if ($params['start']) {
				$arg['datum'] = current_datumtime();
			}

			// признак окончания изучения Курса/Лекции/Материала
			if ($params['end']) {
				$arg['datum_end'] = current_datumtime();
			}

			// проверим прогресс прохождения материалов
			//$progress = self ::progressCource( $idcourse );

			// если это не задание ИЛИ если это задание, но все материалы пройдены
			//if ( $progress['progressMaterial'] == 1 ) {
			//if ( !$params['idtask'] || ($idtask > 0 && $progress['progressMaterial'] == 1) ) {

			if ($id > 0) {
				$db -> query("UPDATE {$sqlname}corpuniver_coursebyusers SET ?u WHERE id = '$id'", arrayNullClean($arg));
			}

			else {

				$arg['identity'] = $identity;
				$db -> query("INSERT INTO {$sqlname}corpuniver_coursebyusers SET ?u", arrayNullClean($arg));

			}

			// если это материал
			if ($idmaterial > 0) {

				self ::progressCheckWayCource($idmaterial);

			}

			return true;

			//}
			//else
			//	return false;

		}
		else {
			return false;
		}

	}

	/**
	 * Добавление ответа на вопрос
	 *
	 * @param array $params
	 *
	 * @return int
	 */
	public static function addAnswer(array $params = []): int {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser   = $GLOBALS['iduser1'];

		$arg = [
			"type"   => $params['type'],
			"datum"  => current_datumtime(),
			"iduser" => $iduser,
			"parent" => $params['parent'],
			"answer" => $params['answer']
		];

		$id = $db -> getOne("SELECT id FROM {$sqlname}corpuniver_useranswers WHERE iduser = '$iduser' and type = '$params[type]' and parent = '$params[parent]'") + 0;

		if ($id == 0) {

			$arg["identity"] = $identity;
			$db -> query("INSERT INTO {$sqlname}corpuniver_useranswers SET ?u", arrayNullClean($arg));

		}
		else {

			$db -> query("UPDATE {$sqlname}corpuniver_useranswers SET ?u WHERE id = '$id'", arrayNullClean($arg));
			$id = $db -> insertId();

		}

		return $id;

	}

	/**
	 * Возвращает иконку видео, если ссылка с видеохостинга
	 *
	 * @param $url
	 *
	 * @return bool|mixed
	 */
	private static function iconBySource($url) {

		return arrayFindInSet($url, self::VIDEOSITE) ? self::ICONMATERIAL['video'] : false;

	}

}