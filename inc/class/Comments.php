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
 * Класс для управления обсуждениями
 *
 * Class Comments
 *
 * @package Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Comments {

	/**
	 * @var array
	 */
	public $otherSettings, $settingsUser;

	public $response = [];

	/**
	 * Абсолютный путь
	 *
	 * @var string
	 */
	public $rootpath;

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 */
	public function __construct() {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		require_once $rootpath."/vendor/autoload.php";

		$params = $this -> params;

		$this -> rootpath = dirname(__DIR__, 2);
		$this -> identity = ( $params['identity'] > 0 ) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> skey     = ( $this -> skey != '' ) ? $this -> skey : $GLOBALS['skey'];
		$this -> ivc      = ( $this -> ivc != '' ) ? $this -> ivc : $GLOBALS['ivc'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		// тут почему-то не срабатывает
		if (!empty($params)) {
			foreach ($params as $key => $val) {
				$this ->{$key} = $val;
			}
		}

		if (file_exists($rootpath."/cash/".$this -> fpath."otherSettings.json")) {

			$this -> otherSettings = json_decode(file_get_contents($rootpath."/cash/".$this -> fpath."otherSettings.json"), true);

		}
		else {

			$other                 = explode(";", $this -> db -> getOne("SELECT other FROM ".$this -> sqlname."settings WHERE id = '".$this -> identity."'"));
			$this -> otherSettings = [
				"partner"              => $other[0] == 'yes',
				"concurent"            => $other[1] == 'yes',
				"credit"               => $other[2] == 'yes',
				"price"                => $other[3] == 'yes',
				"dealPeriod"           => $other[4] == 'yes',
				"contract"             => $other[5] == 'yes',
				"creditAlert"          => $other[6],
				"dealAlert"            => $other[7],
				"profile"              => $other[8] == 'yes',
				"marga"                => $other[9] == 'yes',
				"potential"            => $other[10] == 'yes',
				"expressForm"          => $other[11] == 'yes',
				"printInvoice"         => $other[12] == 'yes',
				"clientIsPerson"       => $other[13] != 'yes',
				"dop"                  => $other[14] == 'yes',
				"dopName"              => $other[15],
				"comment"              => $other[16] == 'yes',
				"contractor"           => $other[17] == 'yes',
				"planByClosed"         => $other[18] == 'yes',
				"taskControl"          => (int)$other[19],
				"taskControlClientAdd" => $other[20] == 'yes',
				"woNDS"                => $other[21] == 'yes',
				"dealByContact"        => $other[22] == 'yes',
				"addClientWDeal"       => $other[23] == 'yes',
				"changeDealPeriod"     => $other[24],
				"dealStepDefault"      => $other[25],
				"dealPeriodDefault"    => $other[26] != '' && $other[26] != 'no' ? $other[26] : 14,
				"changeDealComment"    => $other[27] == 'yes',
				"changeUserComment"    => $other[28] == 'yes',
				"ndsInOut"             => $other[29] == 'yes',
				"saledProduct"         => $other[30] == 'yes',
				"guidesEdit"           => $other[31] == 'yes',
				"taskEditTime"         => $other[32],
				"taskControlInHealth"  => $other[33] == 'yes',
				"artikulInInvoice"     => $other[34] == 'yes',
				"artikulInAkt"         => $other[35] == 'yes',
				"mailerMsgUnion"       => $other[36] == 'yes',
				"stepControlInHealth"  => $other[37] == 'yes',
				"budjetDayIsNow"       => $other[38],
				"aktTempService"       => ( !isset($other[39]) || $other[39] == 'no' ) ? 'akt_full.tpl' : $other[39],
				"invoiceTempService"   => ( !isset($other[40]) || $other[40] == 'no' ) ? 'invoice.tpl' : $other[40],
				"aktTemp"              => ( !isset($other[41]) || $other[41] == 'no' ) ? 'akt_full.tpl' : $other[41],
				"invoiceTemp"          => ( !isset($other[42]) || $other[42] == 'no' ) ? 'invoice.tpl' : $other[42],
			];

		}

		$this -> settingsUser = User::settings($this -> iduser1);

		date_default_timezone_set($this -> tmzone);

	}

	/**
	 * Информация по обсуждению
	 *
	 * @param $id
	 *
	 * @return array
	 *
	 *      -
	 *      - int **id** - id записи
	 *      - int **idparent** - id обсуждения, = 0, если это голова ветки, > 0, если это ответ
	 *      - str **title** - название обсуждения, пусто для ответов
	 *      - str **content** - текст обсуждения/ответа
	 *      - array **fid** - массив вложенных файлов ( id )
	 *      - int **clid** - id Клиента
	 *      - int **did** - id Сделки
	 *      - int **project** - id Проекта
	 *
	 */
	public static function info($id): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$id = (int)$id;

		$r = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");

		$response = [
			'id'       => $r['id'] + 0,
			'idparent' => $r['idparent'] + 0,
			'title'    => $r['title'],
			'content'  => $r['content'],
			'fid'      => yexplode(";", $r['fid']),
			'clid'     => $r['clid'] + 0,
			'did'      => $r['did'] + 0,
			'project'  => $r['project'] + 0
		];

		return $response;

	}

	/**
	 * Метод добавления/редактирования записей
	 *
	 * @param int   $id     - id записи или 0
	 *
	 * @param array $params - массив с параметрами
	 *
	 *    - int **iduser** - пользователь, от имени которого добавляется обсуждение или комментарий ( = $iduser1, если не указан )
	 *    - str **title** - название обсуждения ( для комментария не указывается )
	 *    - str **content** - содержание записи
	 *    - int **pid** - id контакта
	 *    - int **clid** - id клиента
	 *    - int **did** - id сделки
	 *    - int **project** - id проекта
	 *
	 * @return array
	 *    - int id - id записи
	 *    - int idparent - id обсуждения
	 *    - str text - ответ
	 * @throws Exception
	 */
	public function edit(int $id = 0, array $params = []): array {

		global $hooks;
		global $iduser1;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$message = [];

		$id = (int)$id;

		$content  = htmlspecialchars(str_replace("'", "", $params['content']));
		$title    = clean_all($params['title']);
		$pid      = (int)$params["pid"];
		$clid     = (int)$params["clid"];
		$did      = (int)$params["did"];
		$project  = (int)$params["project"];
		$idparent = (int)$params["idparent"];
		$mid      = (int)$params["mid"];
		$iduser   = (int)$params['iduser'] > 0 ? (int)$params['iduser'] : $iduser1;
		$users    = $params['users'];

		if (!isHTML($params['content'])) {
			$content = nl2br( $content );
		}

		//находим папку
		$folder = (int)$db -> getOne("SELECT idcategory FROM {$sqlname}file_cat WHERE title = 'Файлы комментариев' and identity = '$identity'");

		//если такой папки нет, то добавляем
		if ($folder == 0) {

			$db -> query("INSERT INTO {$sqlname}file_cat SET ?u", [
				'title'    => 'Файлы комментариев',
				'shared'   => 'yes',
				'identity' => $identity
			]);
			$folder = $db -> insertId();

		}

		/**
		 * Загружаем файлы
		 */
		$upload = Upload ::upload();

		$message = array_merge($message, $upload['message']);

		$fid = [];

		foreach ($upload['data'] as $file) {

			$arg = [
				'ftitle'   => $file['title'],
				'fname'    => $file['name'],
				'ftype'    => $file['type'],
				'iduser'   => $iduser,
				'clid'     => $clid,
				'pid'      => $pid,
				'did'      => $did,
				'project'  => $project,
				'folder'   => $folder,
				'identity' => $identity
			];

			$fid[] = Upload ::edit(0, $arg);

		}

		$thistime = current_datumtime();

		if ( $id == 0) {

			//записываем тему или коммент
			$arg = [
				'idparent' => $idparent,
				'datum'    => $thistime,
				'mid'      => $mid,
				'clid'     => $clid,
				'pid'      => $pid,
				'did'      => $did,
				'project'  => $project,
				'title'    => $title,
				'content'  => $content,
				'iduser'   => $iduser,
				'fid'      => yimplode(";", $fid),
				'identity' => $identity
			];
			$db -> query("INSERT INTO {$sqlname}comments SET ?u", arrayNullClean($arg));

			$message[] = "Информация успешно добавлена";
			$id        = $db -> insertId();

			//добавим mid для первой ветки темы, т.к. для неё mid = id
			if ($idparent == 0) {

				//обновим время последнего комментария, чтобы тема была "поднята" наверх
				$db -> query("UPDATE {$sqlname}comments SET lastCommentDate = '$thistime' WHERE id = '$id' and identity = '$identity'");

				//print_r($users);

				//добавим подписчиков
				foreach ($users as $user) {

					// подпишем на уведомления
					self ::subscribe($id, $user);

					// отправим уведомление
					//$s         = self ::send($id, $user, 'new');
					//$message[] = $s['text'];

				}

				// новая отправка
				$s         = self ::sendplus($id, $users );
				$message[] = $s['text'];

				// подпишем на уведомления текущего пользователя
				$r = self ::subscribe($id, $iduser);

				if ($r) {
					$message[] = "Оформлена подписка на ответы";
				}

				$idparent = $id;

			}
			elseif ($idparent > 0) {

				//обновим время последнего комментария
				$db -> query("UPDATE {$sqlname}comments SET lastCommentDate = '$thistime' WHERE id = '$idparent' and identity = '$identity'");

				//найдем всех пользователей, подписанных на тему
				/*$result = $db -> getAll("SELECT * FROM {$sqlname}comments_subscribe WHERE idcomment = '$idparent' and iduser != '$iduser' and identity = '$identity'");
				foreach ($result as $data) {

					$s         = self ::send($idparent, $data['iduser'], 'answer', $id);
					$message[] = $s['text'];

				}*/

				$users = $db -> getCol("SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '$idparent' and iduser != '$iduser' and identity = '$identity'");

				// новая отправка
				$s         = self ::sendplus($idparent, $users, 'answer', $id);
				$message[] = $s['text'];

			}

			/**
			 * Уведомления
			 */
			$arg = [
				"id"     => $id,
				"iduser" => $iduser,
				"notice" => 'yes'
			];
			Notify ::fire("comment.new", $iduser, $arg);

			$message = yimplode('<br>', $message);

			$response = [
				"id"       => $id,
				"idparent" => $idparent,
				"text"     => $message
			];

		}
		else {

			// найдем существующие файлы
			$cmnt  = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");
			$fileo = yexplode(";", $cmnt['fid']);

			// добавим новые файлы
			$fid = yimplode(";", array_unique(array_merge($fileo, $fid)));

			$arg = [
				'title'   => $title,
				'content' => $content,
				'fid'     => $fid
			];

			if ($idparent == 0) {

				if ($clid > 0 && $cmnt['clid'] != $clid) {
					$arg['clid'] = $clid;
				}

				if ($did > 0 && $cmnt['did'] != $did) {
					$arg['did'] = $did;
				}

				if ($project > 0 && $cmnt['project'] != $project) {
					$arg['project'] = $project;
				}

			}

			$db -> query("UPDATE {$sqlname}comments SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean($arg));

			$message[] = "Информация обновлена";

			$message = implode('<br>', $message);

			if ($idparent > 0) {
				$id = $idparent;
			}
			else {
				$idparent = $id;
			}

			$response = [
				"id"       => $id,
				"idparent" => $idparent,
				"text"     => $message
			];

		}

		return $response;

	}

	/**
	 * Вывод списка тем или записей обсуждений
	 * @param string $type
	 * @param array $params
	 * @return array
	 */
	public function list(string $type = 'themes', array $params = []): array {

		global $iduser1;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$settingsMore = json_decode( $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'settingsMore' and identity = '$identity'" ), true );

		$lines_per_page = 30;

		$page      = (int)$params['page'];
		$iduser    = $params['iduser'];
		$word      = str_replace(" ", "%", $params['word']);

		$isClose   = $params['isClose'];
		$isDeal    = $params['isDeal'];
		$isClient  = $params['isClient'];
		$isProject = $params['isProject'];

		$sort = $sort2 = '';
		$list = [];

		$modules = $db -> getCol("SELECT mpath FROM {$sqlname}modules WHERE active = 'on' AND identity = '$identity'");

		if ($word != '') {

			$sort .= " and (
				cm.title LIKE '%$word%' OR
				cm.content LIKE '%$word%' OR
				(cm.content LIKE '%$word%' and cm.idparent = id) OR
				cc.title LIKE '%$word%' OR
				deal.title LIKE '%$word%'
				".(in_array("project", $modules) ? " OR cm.project IN (SELECT id FROM {$sqlname}projects WHERE {$sqlname}projects.name LIKE '%$word%' or {$sqlname}projects.content LIKE '%$word%' and {$sqlname}projects.identity = '$identity')" : "")."
			)";

		}
		else {
			$sort2 .= " and cm.idparent = '0'";
		}

		if($isDeal == 'yes') {
			$sort .= " and cm.did > 0";
		}
		if($isClient == 'yes') {
			$sort .= " and cm.clid > 0";
		}
		if($isProject == 'yes') {
			$sort .= " and cm.project > 0";
		}

		if ($type == "themes") {

			//найдем все темы, на кооторые подписан юзер
			if ($this -> settingsUser['isadmin'] != 'on') {

				$sort .= "
					and (
						cm.id IN (SELECT idcomment from {$sqlname}comments_subscribe WHERE {$sqlname}comments_subscribe.id > 0 and {$sqlname}comments_subscribe.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and {$sqlname}comments_subscribe.identity = '$identity') or 
						cm.iduser = '$iduser1' or 
						cm.idparent IN (SELECT idcomment from {$sqlname}comments_subscribe WHERE {$sqlname}comments_subscribe.id > 0 and {$sqlname}comments_subscribe.iduser IN (".yimplode( ",", get_people( $iduser1, "yes" ) ).") and {$sqlname}comments_subscribe.identity = '$identity') or 
						cm.iduser = '$iduser1'
					)";

			}

			if($this -> settingsUser['tipuser'] == 'Поддержка продаж' && $settingsMore['commentsSupportDostup'] == 'yes'){

				$sort .= " 
				and (
					{$sqlname}comments.id IN (
						SELECT idcomment from {$sqlname}comments_subscribe WHERE {$sqlname}comments_subscribe.id > 0 and {$sqlname}comments_subscribe.iduser = '$iduser1' and {$sqlname}comments_subscribe.identity = '$identity') 
					or {$sqlname}comments.iduser = '$iduser1' 
					or {$sqlname}comments.idparent IN (
						SELECT idcomment from {$sqlname}comments_subscribe WHERE {$sqlname}comments_subscribe.id > 0 and {$sqlname}comments_subscribe.iduser = '$iduser1' and {$sqlname}comments_subscribe.identity = '$identity') 
					or {$sqlname}comments.iduser = '$iduser1'
				)";

			}

			if ($isClose == 'active') {
				$sort .= " and cm.isClose != 'yes'";
			}
			if ($isClose == 'closed') {
				$sort .= " and cm.isClose = 'yes'";
			}

			if ($iduser > 0) {
				$sort .= " and cm.iduser = '$iduser'";
			}
			else{
				$sort .= " and (SELECT COUNT(*) FROM {$sqlname}comments_subscribe WHERE {$sqlname}comments_subscribe.idcomment = cm.id and iduser = '$iduser1') > 0";
			}

			//конечный запрос до пагинации
			//print
			$query = "
				SELECT
					if(cm.idparent > 0, cm.idparent, cm.id) as id,
					cm.datum as datum,
					cm.pid as pid,
					cm.clid as clid,
					cc.title as client,
					cm.did as did,
					deal.title as deal,
					cm.title as title,
					cm.content as content,
					cm.iduser as iduser,
					us.title as user,
					cm.lastCommentDate as lastCommentDate,
					cm.isClose as isClose,
					cm.dateClose as dateClose
				FROM {$sqlname}comments `cm`
					LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = cm.clid
					LEFT JOIN {$sqlname}dogovor `deal` ON deal.did = cm.did
					LEFT JOIN {$sqlname}user `us` ON cm.iduser = us.iduser
				WHERE
					cm.id > 0
					$sort2
					$sort and
					cm.identity = '$identity'
				GROUP BY cm.id
				ORDER BY cm.lastCommentDate";

			$result    = $db -> query($query);
			$all_lines = (int)$db -> numRows($result);

			if (empty($page) || $page <= 0) {
				$page = 1;
			}

			$page_for_query = $page - 1;
			$lpos           = $page_for_query * $lines_per_page;

			$query .= " DESC LIMIT $lpos,$lines_per_page";

			$result      = $db -> query($query);
			$count_pages = ceil($all_lines / $lines_per_page);
			if ($count_pages == 0) {
				$count_pages = 1;
			}

			while ($da = $db -> fetch($result)) {

				$closed    = '';
				$dateClose = '';

				if ($da['title'] == '') {

					$r           = $db -> getRow("select * from {$sqlname}comments WHERE id = '".$da['id']."' and identity = '$identity'");
					$da['title'] = "Ответ в тему: ".$r['title'];

				}

				//$content = mb_substr(untag(htmlspecialchars_decode($da['content'])), 0, 401, 'utf-8');
				$content = mb_substr(untag(htmlspecialchars_decode($da['content'])), 0, 101, 'utf-8');

				$r = $db -> getRow("select * from {$sqlname}comments WHERE id > 0 and idparent = '".$da['id']."' and identity = '$identity' ORDER BY datum DESC LIMIT 1");

				$scontent = mb_substr(untag(str_replace("<br>", "\n\r", htmlspecialchars_decode($r['content']))), 0, 401, 'utf-8');
				$sdatum   = get_sfdate($r['datum']);
				$day      = untag(diffDateTime($r['datum']));
				$lastuser = current_user($r['iduser'], "yes");

				$users = [];
				$res   = $db -> getAll("
					SELECT 
					    cs.iduser,
					    us.title as user
					FROM {$sqlname}comments_subscribe `cs`
						LEFT JOIN {$sqlname}user `us` ON cs.iduser = us.iduser
					WHERE 
					    cs.idcomment = '".$da['id']."' and 
					    cs.iduser != '".$da['iduser']."' and 
					    cs.identity = '$identity'
				");
				foreach ($res as $datas) {
					$users[] = [
						"iduser" => (int)$datas['iduser'],
						"user"   => toShort($datas['user'])
					];
				}

				if ($da['isClose'] == 'yes') {
					$closed    = 1;
					$dateClose = get_sfdate($da['dateClose']);
				}

				$change = ($iduser1 == $da['iduser'] && $closed != 1) ? true : NULL;

				$list[] = [
					"id"              => (int)$da['id'],
					"datum"           => get_sfdate($da['datum']),
					"title"           => $da['title'],
					"content"         => $content,
					"clid"            => (int)$da['clid'],
					"client"          => $da['client'],
					"pid"             => (int)$da['pid'],
					"person"          => current_person($da['pid']),
					"did"             => (int)$da['did'],
					"deal"            => $da['deal'],
					"iduser"          => (int)$da['iduser'],
					"user"            => $da['user'],
					"userlist"        => $users,
					"ucount"          => count($users),
					"scontent"        => $scontent,
					"sdatum"          => $sdatum,
					"day"             => $day,
					"lastuser"        => $lastuser,
					"lastCommentDate" => $da['lastCommentDate'],
					"isClose"         => $closed,
					"dateClose"       => $dateClose,
					"odateClose"      => $da['isClose'] == 'yes' ? $da['dateClose'] : NULL,
					"change"          => $change
				];

			}

		}
		else{

			//найдем все темы, на кооторые подписан юзер
			if ($this -> settingsUser['isadmin'] != 'on') {

				$sort .= " or (id IN (SELECT {$sqlname}comments_subscribe.idcomment from {$sqlname}comments_subscribe WHERE {$sqlname}comments_subscribe.id > 0 ".str_replace( "comments", "comments_subscribe", $sort )." and {$sqlname}comments_subscribe.identity = '$identity' ORDER BY {$sqlname}comments_subscribe.id) or cm.iduser = '$iduser1')";

			}

			//конечный запрос до пагинации
			$query = "
				SELECT
					cm.id as id,
					cm.idparent as idparent,
					cm.datum as datum,
					cm.title as title,
					cm.content as content,
					cm.iduser as iduser,
					us.title as user
				FROM {$sqlname}comments `cm`
					LEFT JOIN {$sqlname}user `us` ON cm.iduser = us.iduser
				WHERE
					cm.id > 0
					$sort and
					cm.idparent != '0' and
					cm.identity = '$identity'
				ORDER BY cm.id";

			$result    = $db -> query($query);
			$all_lines = (int)$db -> numRows($result);

			if (empty($page) || $page <= 0) {
				$page = 1;
			}

			$page_for_query = $page - 1;
			$lpos           = $page_for_query * $lines_per_page;

			$query .= " DESC LIMIT $lpos,$lines_per_page";

			$result      = $db -> query($query);
			$count_pages = ceil($all_lines / $lines_per_page);

			if ($count_pages == 0) {
				$count_pages = 1;
			}

			while ($da = $db -> fetch($result)) {

				$r      = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '".$da['idparent']."' and identity = '$identity'");

				$list[] = [
					"id"       => (int)$da['id'],
					"idparent" => (int)$da['idparent'],
					"datum"    => get_sfdate($da['datum']),
					"theme"    => $r['title'],
					"content"  => mb_substr(untag(htmlspecialchars_decode($da['content'])), 0, 401, 'utf-8'),
					"user"     => $da['user'],
					"author"   => current_user($r['iduser']),
					"tcontent" => $r['content'],
					"tdatum"   => get_sfdate($r['datum'])
				];

			}

		}

		$lists = [
			"list"    => $list,
			"page"    => $page,
			"pageall" => (int)$count_pages,
			"settingsMore" => $settingsMore
		];

		unset($db);

		return $lists;

	}

	/**
	 * TODO: не доделано
	 * Обсуждения в списке обсуждений
	 * @param $id
	 * @return void
	 */
	public function listForComments($id = 0){

		global $iduser1;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$users = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' and identity = '$identity' ORDER BY id" );

		$theme  = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'" );
		$author  = (int)$theme["iduser"];
		$isClose = $theme["isClose"];

		$accsess = in_array( $iduser1, $users ) || $iduser1 == $author;

		//$theme = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$id' and idparent = 0 and identity = '$identity'" );



	}

	/**
	 * Метод закрытия/открытия обсуждения
	 *
	 * @param int $id
	 *
	 * @return array - массив с результатом
	 *
	 *     - int **id**  - id обсуждения
	 *     - str **message** - ответ
	 * @throws Exception
	 */
	public static function close(int $id = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser1  = $GLOBALS['iduser1'];

		$message = [];

		$id = (int)$id;

		$isClose = $db -> getOne("select isClose from {$sqlname}comments where id = '$id' and identity = '$identity'");

		// закрываем обсуждение
		if ($isClose != 'yes') {

			$s = 'yes';
			$d = current_datumtime();

			$db -> query("UPDATE {$sqlname}comments SET isClose = '$s', dateClose = '$d' where id = '$id' and identity = '$identity'");
			$message[] = "Обсуждение закрыто";

			/**
			 * Уведомления
			 */
			$arg = [
				"id"     => $id,
				"iduser" => $iduser1,
				"notice" => 'yes'
			];
			Notify ::fire("comment.close", $iduser1, $arg);

		}
		// активируем обсуждение
		else {

			$s = 'no';
			$d = current_datumtime();

			$db -> query("UPDATE {$sqlname}comments SET isClose = '$s', dateClose = '$d' WHERE id = '$id' and identity = '$identity'");
			$message[] = "Обсуждение открыто";

		}

		$message = yimplode('<br>', $message);

		return [
			"id"      => $id,
			"message" => $message
		];

	}

	/**
	 * Метод удаление комментария/обсуждения ( в т.ч. удаляет подписки, если это обсуждение, и файлы )
	 *
	 * @param int $id
	 *
	 * @return array - массив с результатом
	 *
	 *      - str **res** - ok | error
	 *      - str **text** - ответ
	 *      - int **idparent** - id обсуждения
	 */
	public static function delete(int $id = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$isadmin  = $GLOBALS['isadmin'];
		$iduser1  = $GLOBALS['iduser1'];

		$message = [];
		$result  = 'error';

		$id = (int)$id;

		$res      = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");
		$fids     = $res["fid"];
		$idparent = $res["idparent"];
		$iduser   = $res["iduser"];
		$title    = $res["title"];

		$author = ($idparent == 0) ? $iduser : 0;

		if ($isadmin == 'on' || $author == $iduser1) {

			//удалим прикрепленные файлы
			$fid = yexplode(";", $fids);
			foreach ($fid as $file) {

				//удалим запись о файле
				Upload ::delete($file);

				$message[] = 'Удалены привязанные файлы';

			}

			$db -> query("DELETE FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");

			//если удаляется все ветка
			if ($idparent == 0) {

				//Удалим подписки
				$db -> query("DELETE FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' and identity = '$identity'");

				//Удалим комментарии и файлы у ответов
				$result = $db -> getAll("SELECT * FROM {$sqlname}comments WHERE idparent = '$id' and identity = '$identity'");
				foreach ($result as $data) {

					//удалим прикрепленные файлы в ответах
					$fids = yexplode(";", $db -> getOne("SELECT fid FROM {$sqlname}comments WHERE id = '".$data['id']."' and identity = '$identity'"));

					foreach ($fids as $fid) {

						//удалим запись о файле
						Upload ::delete($fid);

					}

					//удалим запись
					$db -> query("DELETE FROM {$sqlname}comments WHERE id = '".$data['id']."' and identity = '$identity'");

					$db -> query("DELETE FROM {$sqlname}comments WHERE idparent = '$id' and identity = '$identity'");

				}

				$message[] = "Обсуждение удалено";
				$message[] = "Также удалены связанные комментарии, файлы и подписки.";

				logger('10', 'Удалено обсуждение и комментарии - '.$title, $iduser1);

			}
			else {

				//обновим дату последнего коммента
				$lastDatum = $db -> getOne("SELECT MAX(datum) as datum FROM {$sqlname}comments WHERE idparent = '$idparent'");

				if ($lastDatum != '0000-00-00 00:00:00' && $idparent > 0) {

					$db -> query("UPDATE {$sqlname}comments SET lastCommentDate = '$lastDatum' WHERE id = '$idparent' and identity = '$identity'");

				}

				$message[] = "Комментарий удален";

			}


			$result = 'ok';

		}
		else {

			$message = 'Внимание: К сожалению Удаление записи не возможно. Причина - Удалить может только Автор или Администратор.';


		}

		return [
			"result"   => $result,
			"text"     => $message,
			"idparent" => $idparent
		];

	}

	/**
	 * Удаление файла из обсуждения или комментария
	 *
	 * @param int $id
	 * @param int $fid
	 */
	public static function deleteFile(int $id = 0, int $fid = 0): void {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$id = (int)$id;
		$fid = (int)$fid;

		//удалим запись о файле
		Upload ::delete($fid);

		//составим массив файлов в записи
		$fidds = yexplode(";", $db -> getOne("SELECT fid FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'"));

		// новый массив
		$fids = yimplode(";", (array)arraydel($fidds, $fid));

		//запишем новый массив файлов, уже без удаляемого
		$db -> query("UPDATE {$sqlname}comments SET fid = '$fids' WHERE id = '$id' and identity = '$identity'");

	}

	/**
	 * Метод подписки пользователя на обсуждение
	 *
	 * @param int $id     - id обсуждения
	 * @param int $iduser - id сотрудника
	 *
	 * @return bool
	 */
	public static function subscribe(int $id = 0, int $iduser = 0): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$id = (int)$id;
		$iduser = (int)$iduser;

		$r = false;

		$re = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' and iduser = '$iduser' and identity = '$identity'") + 0;
		if ($re == 0) {

			$db -> query("INSERT INTO {$sqlname}comments_subscribe SET ?u", [
				'idcomment' => $id,
				'iduser'    => $iduser,
				'identity'  => $identity
			]);

			$r = true;

		}

		return $r;

	}

	/**
	 * Метод отписки пользователя от обсуждения
	 *
	 * @param int $id     - id обсуждения
	 * @param int $iduser - id сотрудника
	 *
	 * @return bool
	 */
	public static function unsubscribe(int $id = 0, int $iduser = 0): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$id = (int)$id;
		$iduser = (int)$iduser;

		$r = false;

		$re = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' and iduser = '$iduser' and identity = '$identity'") + 0;
		if ($re > 0) {

			$db -> query("DELETE FROM {$sqlname}comments_subscribe WHERE idcomment = '$id' AND iduser = '$iduser'");
			$r = true;

		}

		return $r;

	}

	/**
	 * Метод ортправки email-уведомления
	 *
	 * @param int    $id     - id обсуждения
	 * @param int    $iduser - id сотрудника
	 * @param string $event  - тип события (new - новое обсуждение, answer - ответ в ветке)
	 * @param int    $idcomment
	 * @return array
	 * @throws Exception
	 */
	public static function send(int $id, int $iduser = 0, string $event = 'new', int $idcomment = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$scheme   = $GLOBALS['scheme'];

		$baseURL = $scheme.$_SERVER["HTTP_HOST"];
		$pproject = [];
		$subject = $mes = '';

		// данные о ветке
		$theme = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");

		//найдем контакты пользователя
		$resultu = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'");
		$utitle  = $resultu["title"];
		$umail   = $resultu["email"];
		// различные настройки пользователя
		$usersettings = json_decode($resultu["usersettings"], true);

		$url = '<a href="'.$baseURL.'/card.comments?comid='.$id.'" target="_blank">Перейти</a>';

		if ((int)$theme['project'] > 0) {

			$pproject = Project ::info($theme['project']);

			//$url = '<a href="javascript:void(0)" onclick="openProject(\''.$theme['project'].'\')" class="black"><i class="icon-buffer green"></i>&nbsp;'.$pproject['project']['name'].'</a>&nbsp;';

			if ((int)$theme['clid'] < 1) {
				$theme['clid'] = (int)$pproject['project']['clid'];
			}

			if ((int)$theme['did'] < 1) {
				$theme['did'] = (int)$pproject['project']['did'];
			}

		}

		//if( $event == '' && $event == 'new' && $usersettings['subscribs']['comments.new'] == 'on') {
		if( ($event == '' || $event == 'new') && $usersettings['subscribs']['comments.new'] == 'on') {

			$answer = self::info($id);

			$subject = "Приглашение к обсуждению - ".$theme['title']." ( CRM )";
			$mes     = "
				<b>Вы приглашены к обсуждению.</b><br><br>
				Тема &#8220;".$theme['title']."&#8221;.<br><br>
				Текст: ".htmlspecialchars_decode($answer['content'])."<br><br>
				Адрес обсуждения: ".$url."<br><br>
				".($theme['clid'] > 0 ? "Клиент: <b>".current_client( $theme['clid'] )."</b><br>" : "")."
				".($theme['pid'] > 0 ? "Клиент: <b>".current_person( $theme['pid'] )."</b><br>" : "")."
				".($theme['did'] > 0 ? "Сделка: <b>".current_dogovor( $theme['did'] )."</b><br>" : "")."
				".($theme['project'] > 0 ? "Проект: <b>".$pproject['project']['name']."</b><br>" : "")."
				=======================<br>
				Администратор CRM
			";

		}
		if($event == 'answer' && $usersettings['subscribs']['comments.answer'] == 'on') {

			$answer = self::info($idcomment);

			$subject = "Новый ответ в обсуждении - ".$theme['title']." ( CRM )";
			$mes     = "
				<b>В обсуждении появились новые ответы.</b><br><br>
				Тема &#8220;".$theme['title']."&#8221;.<br><br>
				Текст ответа: ".htmlspecialchars_decode($answer['content'])."<br><br>
				Адрес обсуждения: ".$url."<br><br>
				".($theme['clid'] > 0 ? "Клиент: <b>".current_client( $theme['clid'] )."</b><br>" : "")."
				".($theme['pid'] > 0 ? "Клиент: <b>".current_person( $theme['pid'] )."</b><br>" : "")."
				".($theme['did'] > 0 ? "Сделка: <b>".current_dogovor( $theme['did'] )."</b><br>" : "")."
				".($theme['project'] > 0 ? "Проект: <b>".$pproject['project']['name']."</b><br>" : "")."
				=======================<br>
				Администратор CRM
			";

		}

		if($subject != '' && $mes != '') {

			if ( $umail ) {

				$rez = mailto( [$umail, $utitle, "no-replay@localhost", "CRM", $subject, $mes] );

				$message = ($rez == '') ? "Сотрудник ".current_user( $iduser )." приглашен к обсуждению" : "Ошибка: ".$rez;

			}
			else {

				$rez     = '';
				$message = 'Ошибка: Не найден Email сотрудника '.current_user( $iduser );

			}

		}
		else{

			$rez     = '';
			$message = '';

		}

		return [
			"result" => ($rez == '') ? "ok" : "error",
			"text"   => $message,
			"iduser" => $iduser
		];

	}

	/**
	 * Метод ортправки email-уведомления всем сотрудникам в одном письме
	 *
	 * @param int        $id    - id обсуждения
	 * @param array|null $users - массив id сотрудников
	 * @param string     $event - тип события (new - новое обсуждение, answer - ответ в ветке)
	 * @param int        $idcomment
	 * @return array
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public static function sendplus(int $id = 0, array $users = NULL, string $event = 'new', int $idcomment = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$scheme   = $GLOBALS['scheme'];

		$baseURL = $scheme.$_SERVER["HTTP_HOST"];
		$pproject = [];
		$subject = $mes = '';

		$rez     = 'Какая-то ошибка';
		$message = "Некому отправлять";

		if(empty($users)){

			return [
				"result" => "error",
				"text"   => $message
			];

		}

		// данные о ветке
		$theme = $db -> getRow("SELECT * FROM {$sqlname}comments WHERE id = '$id' and identity = '$identity'");

		$url = '<a href="'.$baseURL.'/card.comments?comid='.$id.'" target="_blank">Перейти</a>';

		if ( $theme['project'] > 0 ) {

			$pproject = Project ::info( $theme['project'] );

			//$url = '<a href="javascript:void(0)" onclick="openProject(\''.$theme['project'].'\')" class="black"><i class="icon-buffer green"></i>&nbsp;'.$pproject['project']['name'].'</a>&nbsp;';

			if ( $theme['clid'] < 1 ) {
				$theme['clid'] = $pproject['project']['clid'];
			}

			if ( $theme['did'] < 1 ) {
				$theme['did'] = $pproject['project']['did'];
			}

		}

		if ( $event == '' || $event == 'new' ) {

			$answer = self ::info( $id );

			$subject = "Приглашение к обсуждению - ".$theme['title']." ( CRM )";
			$mes     = "
				<b>Вы приглашены к обсуждению.</b><br><br>
				Тема &#8220;".$theme['title']."&#8221;.<br><br>
				Текст: ".htmlspecialchars_decode( $answer['content'] )."<br><br>
				Адрес обсуждения: ".$url."<br><br>
				".($theme['clid'] > 0 ? "Клиент: <b>".current_client( $theme['clid'] )."</b><br>" : "")."
				".($theme['pid'] > 0 ? "Клиент: <b>".current_person( $theme['pid'] )."</b><br>" : "")."
				".($theme['did'] > 0 ? "Сделка: <b>".current_dogovor( $theme['did'] )."</b><br>" : "")."
				".($theme['project'] > 0 ? "Проект: <b>".$pproject['project']['name']."</b><br>" : "")."
				=======================<br>
				Администратор CRM
			";

		}
		elseif ( $event == 'answer' ) {

			$answer = self ::info( $idcomment );

			$subject = "Новый ответ в обсуждении - ".$theme['title']." ( CRM )";
			$mes     = "
				<b>В обсуждении появились новые ответы.</b><br><br>
				Тема &#8220;".$theme['title']."&#8221;.<br><br>
				Текст ответа: ".htmlspecialchars_decode( $answer['content'] )."<br><br>
				Адрес обсуждения: ".$url."<br><br>
				".($theme['clid'] > 0 ? "Клиент: <b>".current_client( $theme['clid'] )."</b><br>" : "")."
				".($theme['pid'] > 0 ? "Клиент: <b>".current_person( $theme['pid'] )."</b><br>" : "")."
				".($theme['did'] > 0 ? "Сделка: <b>".current_dogovor( $theme['did'] )."</b><br>" : "")."
				".($theme['project'] > 0 ? "Проект: <b>".$pproject['project']['name']."</b><br>" : "")."
				=======================<br>
				Администратор CRM
			";

		}

		$cc = [];
		$names = [];
		foreach ($users as $user) {

			//найдем контакты пользователя
			$resultu = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$user' and identity = '$identity'" );
			$utitle  = $resultu["title"];
			$umail   = $resultu["email"];

			// различные настройки пользователя
			$usersettings = json_decode( $resultu["usersettings"], true );

			// отправляем только если пользователь подписан
			if ( ( ($event == '' || $event == 'new') && $usersettings['subscribs']['comments.new'] == 'on' ) || ( $event == 'answer' && $usersettings['subscribs']['comments.answer'] == 'on' ) ) {

				$cc[] = [
					"email" => $umail,
					"name"  => $utitle
				];

				$names[] = $utitle;

			}

		}

		if($subject != '' && $mes != '' && !empty($cc)) {

			$u = array_shift($cc);

			$rez     = mailto( [$u['email'], $u['name'], "no-replay@localhost", "CRM", $subject, $mes, [], $cc] );
			$message = ($rez == '') ? "Сотрудники ".yimplode( ", ", $names )." приглашены к обсуждению" : "Ошибка: ".$rez;

		}

		return [
			"result" => ($rez == '') ? "ok" : "error",
			"text"   => $message
		];

	}

}