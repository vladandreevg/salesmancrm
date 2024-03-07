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
use Spreadsheet_Excel_Reader;
use SpreadsheetReader;

/**
 * Класс для работы модуля ЦИЗ
 *
 * Class CallCenter
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (29/07/2020)
 *
 */
class CallCenter {

	public const STATUSES = [
		100 => "Запрос КП",
		101 => "Перезвонить менеджеру",
		102 => "Назначена встреча",
		200 => "Перезвонить. Через 10 минут",
		201 => "Перезвонить. Через 30 минут",
		202 => "Перезвонить. Через 1 час",
		203 => "Перезвонить. Через 4 часа",
		204 => "Перезвонить. Через 24 часа",
		205 => "Перезвонить. Через неделю",
		206 => "Перезвонить. Через месяц",
		300 => "Отмена. Не актуальный номер",
		301 => "Отмена. Жесткий отказ секретаря",
		302 => "Отмена. Жесткий отказ ЛПР",
		303 => "Отмена. Не доступен",
		304 => "Отмена. Не отвечает"
	];

	// Тайминги для перезвона
	public const STATUSTIME = [
		200 => 600,
		201 => 1800,
		202 => 3600,
		203 => 14400
	];

	public const STATUS = [
		'draft'  => 'Черновик',
		'active' => 'В работе',
		'do'     => 'Выполнено',
		'cancel' => 'Отменено'
	];

	public const COLORS = [
		'draft'  => 'broun',
		'active' => 'blue',
		'do'     => 'green',
		'cancel' => 'gray'
	];

	public const lines_per_page = 100;

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
	 * @var mixed
	 */
	private $coordinator;

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Currency constructor.
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		//require_once $rootpath."/vendor/autoload.php";

		$params = $this -> params;

		$this -> rootpath = dirname( __DIR__, 2 );
		$this -> identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set( $this -> tmzone );

		$ccset               = $this -> db -> getRow( "SELECT * FROM ".$this -> sqlname."modules WHERE mpath = 'callcenter' and identity = '".$this -> identity."'" );
		$settings            = json_decode( $ccset['content'], true );
		$this -> operators   = $settings['ccOperator'];
		$this -> coordinator = $settings['ccCoordinator'];

		//print_r($settings);

	}

	/**
	 * Список контактов по заданию
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	public function listContacts(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$list = [];

		//$userPinned = [];

		//операторы задания
		$taskOperator = yexplode( ",", (string)$db -> getOne( "SELECT iduser FROM {$sqlname}callcenter WHERE id = '$id' and identity = '$identity'" ) );

		$sort = ($iduser1 == $this -> coordinator || $GLOBALS['isadmin'] == 'yes' || $GLOBALS['tipuser'] == 'Руководитель организации') ? "(ccl.iduser IN (".yimplode( ",", $taskOperator ).") OR ccl.iduser = '0' OR ccl.iduser = '$iduser1') AND" : "(ccl.iduser = '$iduser1' OR ccl.iduser = '0') AND";

		//$sort = ( $iduser1 == $this -> coordinator ) ? "" : "({$sqlname}callcenter_list.iduser = '$iduser1' OR {$sqlname}callcenter_list.iduser = '0') AND";

		//todo: Составить запрос для получения списка по выбранному заданию
		$query = "
		SELECT 
			ccl.id as id,
			ccl.torder as torder,
			ccl.task as task,
			ccl.datum as datum,
			ccl.datum_do as dodatum,
			ccl.clid as clid,
			ccl.pid as pid,
			ccl.phone as phone,
			ccl.iduser as iduser,
			ccl.isdo as status,
			ccl.rezult as rezult,
			ccl.content as content
		FROM {$sqlname}callcenter_list `ccl`
			-- LEFT JOIN {$sqlname}callcenter ON {$sqlname}callcenter.id = {$sqlname}callcenter_list.task
		WHERE
			ccl.task = '$id' AND
			ccl.isdo != 'yes' AND
			(
				(
					ccl.rezult = '200' AND
					MINUTE(TIMEDIFF(ccl.datum_do, NOW())) >= 10
				)
				OR 
				(
					ccl.rezult = '201' AND
					MINUTE(TIMEDIFF(ccl.datum_do, NOW())) >= 30
				)
				OR 
				(
					ccl.rezult = '202' AND
					MINUTE(TIMEDIFF(ccl.datum_do, NOW())) >= 60
				)
				OR 
				(
					ccl.rezult = '203' AND
					MINUTE(TIMEDIFF(ccl.datum_do, NOW())) >= 240
				)
				OR 
				(
					ccl.rezult = '204' AND
					MINUTE(TIMEDIFF(ccl.datum_do, NOW())) >= 1440
				)
				OR 
				(
					ccl.rezult = '205' AND
					MINUTE(TIMEDIFF(ccl.datum_do, NOW())) >= 10080
				)
				OR
				ccl.rezult = ''
			) AND
			$sort
			ccl.identity = '$identity'
		ORDER BY ccl.iduser DESC, ccl.torder
		LIMIT 3";

		$result = $db -> query( $query );
		while ($da = $db -> fetch( $result )) {

			$bgcolor = "bgwhite";

			//массив списка, для начисления пользователю
			//$userPinned[] = $da[ 'id' ];

			//если это контакт, то найдем клиента
			if ( (int)$da['clid'] == 0 ) {
				$da['clid'] = (int)getPersonData( $da['pid'], 'clid' );
			}

			//прикрепим запись к сотруднику, который её загрузил, если он оператор
			if ( in_array( $iduser1, $taskOperator ) && (int)$da['iduser'] == 0 ) {

				$db -> query( "UPDATE {$sqlname}callcenter_list SET iduser = '$iduser1' WHERE id = '$da[id]'" );
				$da['iduser'] = $iduser1;

			}

			$rezult = ($da['rezult'] != '') ? 1 : NULL;

			$rezTitle = ($da['rezult'] != '') ? strtr( $da['rezult'], self::STATUSES ) : "";

			if ( in_array( $da['rezult'], [
				'200',
				'201',
				'202',
				'203',
				'204',
				'205'
			] ) ) {

				$time    = (int)strtotime( $da['dodatum'] ) + (int)strtr( $da['rezult'], self::STATUSTIME );
				$bgcolor = "yellowbg-sub";

			}

			$list[] = [
				"id"       => (int)$da['id'],
				"task"     => (int)$da['task'],
				"datum"    => get_sfdate( $da['datum'] ),
				"clid"     => (int)$da['clid'],
				"client"   => current_client( $da['clid'] ),
				"pid"      => (int)$da['pid'],
				"person"   => current_person( $da['pid'] ),
				"iduser"   => (int)$da['iduser'],
				"user"     => (int)$da['iduser'] > 0 ? current_user( $da['iduser'] ) : "",
				"phone"    => $da['phone'],
				"status"   => $da['status'],
				"comment"  => $da['content'],
				"dodatum"  => untag( get_sdate( $da['dodatum'] ) ),
				"bgcolor"  => $bgcolor,
				"rezult"   => $rezult,
				"rezTitle" => $rezTitle,
			];

		}

		//данные по скрипту

		$sspScrypt = '';
		$hsScrypt  = '';

		$hsr = $db -> getRow( "SELECT script, content FROM {$sqlname}callcenter WHERE id = '$id' AND  identity = '$identity'" );

		$posScrypt = explode( ":", (string)$hsr['script'] );

		if ( $posScrypt[0] == 'ssp' ) {
			$sspScrypt = $posScrypt[1];
		}
		else {
			$hsScrypt = $hsr['script'];
		}

		$hsContent = $hsr['content'];

		//вывод списков

		return [
			"list"      => $list,
			"count"     => count( $list ),
			"hsScrypt"  => $hsScrypt,
			"sspScrypt" => $sspScrypt,
			"hsContent" => nl2br( $hsContent )
		];

	}

	/**
	 * Список контактов для редактора заданий
	 *
	 * @param int   $id
	 * @param array $filters
	 * @return array
	 */
	public function listContactsEditor(int $id = 0, array $filters = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$bgcolor        = "bgwhite";
		$list           = [];
		$lines_per_page = self::lines_per_page;

		//операторы задания
		$taskOperator = yexplode( ",", (string)$db -> getOne( "SELECT iduser FROM ".$sqlname."callcenter WHERE id = '$id' and identity = '$identity'" ) );

		$sort = ($iduser1 != $this -> coordinator) ? "(".$sqlname."callcenter_list.iduser IN (".yimplode( ",", $taskOperator ).") OR ".$sqlname."callcenter_list.iduser = '0') AND" : "";

		$query = "
		SELECT 
			".$sqlname."callcenter_list.id as id,
			".$sqlname."callcenter_list.task as task,
			".$sqlname."callcenter_list.datum as datum,
			".$sqlname."callcenter_list.datum_do as dodatum,
			".$sqlname."callcenter_list.clid as clid,
			".$sqlname."callcenter_list.pid as pid,
			".$sqlname."callcenter_list.phone as phone,
			".$sqlname."callcenter_list.iduser as iduser,
			".$sqlname."callcenter_list.isdo as status,
			".$sqlname."callcenter_list.rezult as rezult,
			".$sqlname."callcenter_list.content as content
		FROM ".$sqlname."callcenter_list
			-- LEFT JOIN ".$sqlname."callcenter ON ".$sqlname."callcenter.id = ".$sqlname."callcenter_list.task
		WHERE
			".$sqlname."callcenter_list.task = '$id' AND
			$sort
			".$sqlname."callcenter_list.identity = '$identity'
		ORDER BY ".$sqlname."callcenter_list.rezult";

		$all_lines = $db -> getOne( "
		SELECT 
			COUNT(*)
		FROM ".$sqlname."callcenter_list
			-- LEFT JOIN ".$sqlname."callcenter ON ".$sqlname."callcenter.id = ".$sqlname."callcenter_list.task
		WHERE
			".$sqlname."callcenter_list.task = '$id' AND
			$sort
			".$sqlname."callcenter_list.identity = '$identity'
		ORDER BY ".$sqlname."callcenter_list.rezult" );

		$page = (empty( $filters['page'] ) || $filters['page'] <= 0) ? 1 : (int)$filters['page'];

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query  .= " LIMIT $lpos,$lines_per_page";
		$result = $db -> query( $query );

		$count_pages = ceil( $all_lines / $lines_per_page );
		if ( $count_pages == 0 )
			$count_pages = 1;

		while ($da = $db -> fetch( $result )) {

			$time = '';

			if ( (int)$da['clid'] < 1 ) {

				$da['clid']   = (int)getPersonData( $da['pid'], 'clid' );
				$da['client'] = getClientData( $da['clid'], 'title' );

			}

			if ( in_array( $da['rezult'], [
				'200',
				'201',
				'202',
				'203',
				'204',
				'205'
			] ) ) {

				$time    = (int)strtotime( $da['dodatum'] ) + (int)strtr( $da['rezult'], self::STATUSTIME );
				$bgcolor = "yellowbg-sub";

			}
			elseif ( in_array( $da['rezult'], [
				'100',
				'101',
				'102'
			] ) ) {

				$bgcolor = "greenbg-sub";

			}
			elseif ( in_array( $da['rezult'], [
				'300',
				'301',
				'302',
				'303',
				'304'
			] ) ) {

				$bgcolor = "redbg-sub";

			}

			$list[] = [
				"id"       => (int)$da['id'],
				"task"     => (int)$da['task'],
				"datum"    => get_sfdate( $da['datum'] ),
				"clid"     => (int)$da['clid'],
				"client"   => current_client( $da['clid'] ),
				"pid"      => (int)$da['pid'],
				"person"   => current_person( $da['pid'] ),
				"iduser"   => (int)$da['iduser'],
				"user"     => (int)$da['iduser'] > 0 ? current_user( (int)$da['iduser'] ) : NULL,
				"phone"    => $da['phone'],
				"status"   => $da['status'],
				"rezult"   => ($da['rezult'] != '') ? 1 : NULL,
				"rezTitle" => ($da['rezult'] != '') ? strtr( $da['rezult'], self::STATUSES ) : NULL,
				"comment"  => $da['content'],
				"dodatum"  => untag( get_sdate( $da['dodatum'] ) ),
				"bgcolor"  => $bgcolor,
				"time"     => $time
			];

		}

		return [
			"list"    => $list,
			"page"    => (int)$page,
			"pageall" => (int)$count_pages,
			"count"   => count( $list ),
		];

	}

	/**
	 * Список заданий
	 *
	 * @param array $params
	 * @return array
	 */
	public function listTasks(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$list = [];

		$foredit        = $params['foredit'];
		$lines_per_page = self::lines_per_page;
		$sort           = '';

		$statuss = ($foredit) ? "'draft','active','cancel'" : "'active'";
		$sort    .= ($iduser1 != $this -> coordinator) ? " find_in_set($iduser1,{$sqlname}callcenter.iduser) > 0 and " : "";
		$sort    .= ($iduser1 != $this -> coordinator) ? " {$sqlname}callcenter.dstart <= '".current_datumtime()."' and " : "";

		if($GLOBALS['isadmin'] == 'yes' || $GLOBALS['tipuser'] == 'Руководитель организации')
			$sort = '';

		$all_lines = $db -> getOne( "
			SELECT
				COUNT(*)
			FROM {$sqlname}callcenter
				LEFT JOIN {$sqlname}group ON {$sqlname}callcenter.gid = {$sqlname}group.id
			WHERE
				{$sqlname}callcenter.id > 0 AND
				$sort
				{$sqlname}callcenter.status IN ($statuss) AND
				{$sqlname}callcenter.identity = '$identity'
			ORDER BY {$sqlname}callcenter.dstart
		" );

		//todo: Составить запрос для получения списка заданий
		$query = "
		SELECT
			{$sqlname}callcenter.id as id,
			{$sqlname}callcenter.datum as datum,
			{$sqlname}callcenter.dstart as dstart,
			{$sqlname}callcenter.dend as dend,
			{$sqlname}callcenter.gid as gid,
			{$sqlname}callcenter.title as title,
			{$sqlname}callcenter.content as content,
			{$sqlname}callcenter.status as status,
			{$sqlname}callcenter.script as script,
			{$sqlname}callcenter.scriptTitle as scriptTilte,
			{$sqlname}callcenter.iduser as iduser,
			{$sqlname}group.name as groupes
		FROM {$sqlname}callcenter
			LEFT JOIN {$sqlname}group ON {$sqlname}callcenter.gid = {$sqlname}group.id
		WHERE
			{$sqlname}callcenter.id > 0 AND
			$sort
			{$sqlname}callcenter.status IN ($statuss) AND
			{$sqlname}callcenter.identity = '$identity'
		ORDER BY {$sqlname}callcenter.dstart";

		$page = (empty( $params['page'] ) || $params['page'] <= 0) ? 1 : (int)$params['page'];

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query .= " DESC LIMIT $lpos,$lines_per_page";

		$result      = $db -> query( $query );
		$count_pages = ceil( $all_lines / $lines_per_page );

		if ( $count_pages == 0 )
			$count_pages = 1;

		while ($da = $db -> fetch( $result )) {

			$list[] = [
				"id"          => (int)$da['id'],
				"datum"       => $da['datum'],
				"title"       => $da['title'],
				"content"     => $da['content'],
				"status"      => strtr( $da['status'], self::STATUS ),
				"statusColor" => strtr( $da['status'], self::COLORS ),
				"dstart"      => $da['dstart'],
				"dend"        => $da['dend'],
				"gid"         => (int)$da['gid']
			];

		}

		return [
			"list"    => $list,
			"page"    => (int)$page,
			"pageall" => (int)$all_lines,
			"count"   => count( $list ),
		];

	}

	/**
	 * Вывод списка заданий для статистики
	 *
	 * @param array $params
	 * @return array|array[]
	 */
	public function listTasksStat(array $params = []): array {

		$sqlname        = $this -> sqlname;
		$db             = $this -> db;
		$identity       = $this -> identity;
		$iduser1        = $this -> iduser1;
		$lines_per_page = self::lines_per_page;

		$us   = '';
		$list = [];

		$us .= ($iduser1 != $this -> coordinator) ? " find_in_set($iduser1,{$sqlname}callcenter.iduser) > 0 and " : "";
		$us .= ($iduser1 != $this -> coordinator) ? " {$sqlname}callcenter.dstart <= '".current_datumtime()."' and " : "";

		if($GLOBALS['isadmin'] == 'yes' || $GLOBALS['tipuser'] == 'Руководитель организации')
			$us = '';

		$query = "
		SELECT
			{$sqlname}callcenter.id as id,
			{$sqlname}callcenter.datum as datum,
			{$sqlname}callcenter.dstart as dstart,
			{$sqlname}callcenter.dend as dend,
			{$sqlname}callcenter.gid as gid,
			{$sqlname}callcenter.title as title,
			{$sqlname}callcenter.status as status,
			{$sqlname}callcenter.script as script,
			{$sqlname}callcenter.scriptTitle as scriptTilte,
			{$sqlname}callcenter.iduser as iduser,
			{$sqlname}group.name as xgroup,
			{$sqlname}user.title as user
		FROM {$sqlname}callcenter
			LEFT JOIN {$sqlname}group ON {$sqlname}callcenter.gid = {$sqlname}group.id
			LEFT JOIN {$sqlname}user ON {$sqlname}user.iduser = {$sqlname}callcenter.iduser
		WHERE
			{$sqlname}callcenter.id > 0 and
			$us
			{$sqlname}callcenter.status IN ('do','active') and
			{$sqlname}callcenter.identity = '$identity'
		ORDER BY field({$sqlname}callcenter.datum, 'do', 'active'), {$sqlname}callcenter.datum";

		$result    = $db -> query( $query );
		$all_lines = $db -> numRows( $result );

		$page = (empty( $params['page'] ) || $params['page'] <= 0) ? 1 : (int)$params['page'];

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query .= " DESC LIMIT $lpos,$lines_per_page";

		$result      = $db -> query( $query );
		$count_pages = ceil( $all_lines / $lines_per_page );

		if ( $count_pages == 0 )
			$count_pages = 1;

		while ($da = $db -> fetch( $result )) {

			$list[] = [
				"id"          => (int)$da['id'],
				"datum"       => $da['datum'],
				"title"       => $da['title'],
				"status"      => strtr( $da['status'], self::STATUS ),
				"statusColor" => strtr( $da['status'], self::COLORS ),
				"dstart"      => $da['dstart'],
				"dend"        => $da['dend']
			];

		}

		return ["list" => $list];

	}

	/**
	 * Возвращает прогресс выполнения задания
	 *
	 * @param array $params
	 * @return array
	 */
	public function progress(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		//$gid = $db -> getOne( "SELECT gid FROM {$sqlname}callcenter WHERE id = '$params[task]' and identity = '$identity'" );

		$all   = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}callcenter_list WHERE task = '$params[task]' and identity = '$identity'" );
		$count = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}callcenter_list WHERE task = '$params[task]' and isdo = 'yes' and identity = '$identity'" );

		$progress = $all > 0 ? round( $count / $all * 100, 0, 2 ) : 0;

		//$progress = rand(1, 100);//заглушка

		$good = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}callcenter_list WHERE task = '$params[task]' and isdo = 'yes' and rezult IN ('100','101','102','103') and identity = '$identity'" );

		$recall = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}callcenter_list WHERE task = '$params[task]' and isdo != 'yes' and rezult IN ('200','201','202','203','204','205','206') and identity = '$identity'" );

		$bad = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}callcenter_list WHERE task = '$params[task]' and isdo = 'yes' and rezult IN ('300','301','302','303','304') and identity = '$identity'" );

		return [
			"count"    => $all,
			"good"     => $good,
			"recall"   => $recall,
			"bad"      => $bad,
			"progress" => $progress
		];

	}

	/**
	 * Различные справочники для формы
	 *
	 * @return array
	 */
	public function guides(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$relation = $loyalty = $category = $territory = $hsdata = [];

		$mdwset = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'callcenter' and identity = '$identity'" );
		$hsdata = json_decode( $mdwset['content'], true );

		//типы отношений
		$res = $db -> getAll( "SELECT * FROM {$sqlname}relations WHERE identity = '$identity'" );
		foreach ( $res as $da ) {

			$relation[] = [
				"id"    => (int)$da['id'],
				"title" => $da['title'],
				"color" => $da['color']
			];

		}

		//лояльность контактов
		$res = $db -> getAll( "SELECT * FROM {$sqlname}loyal_cat WHERE identity = '$identity'" );
		foreach ( $res as $da ) {

			$loyalty[] = [
				"id"    => (int)$da['idcategory'],
				"title" => $da['title'],
				"color" => $da['color']
			];

		}

		//отрасли
		$res = $db -> getAll( "SELECT * FROM {$sqlname}category WHERE identity = '$identity'" );
		foreach ( $res as $da ) {

			$category[] = [
				"id"    => (int)$da['idcategory'],
				"title" => $da['title']
			];

		}

		//территории
		$res = $db -> getAll( "SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity'" );
		foreach ( $res as $da ) {

			$territory[] = [
				"id"    => (int)$da['idcategory'],
				"title" => $da['title']
			];

		}

		//ссылка для интеграции
		$urlTemp = getCallUrl();

		//варианты быстрых ответов
		//$answers = yexplode( ";", $hsdata['answers'] );

		return [
			"hsdata"    => $hsdata,
			"relation"  => $relation,
			"loyalty"   => $loyalty,
			"category"  => $category,
			"territory" => $territory,
			"user"      => $iduser1,
			"urlTemp"   => $urlTemp,
			"answers"   => $hsdata['answers']
		];

	}

	/**
	 * Информация о задании
	 *
	 * @param int $id
	 * @return array
	 */
	public function info(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$query = "
		SELECT
			{$sqlname}callcenter.id as id,
			{$sqlname}callcenter.datum as datum,
			{$sqlname}callcenter.dstart as dstart,
			{$sqlname}callcenter.dend as dend,
			{$sqlname}callcenter.gid as gid,
			{$sqlname}callcenter.title as title,
			{$sqlname}callcenter.content as content,
			{$sqlname}callcenter.status as status,
			{$sqlname}callcenter.script as script,
			{$sqlname}callcenter.scriptTitle as scriptTitle,
			{$sqlname}callcenter.iduser as iduser,
			{$sqlname}callcenter.operators as operators,
			{$sqlname}callcenter.Method as Method
		FROM {$sqlname}callcenter
			-- LEFT JOIN {$sqlname}group ON {$sqlname}callcenter.gid = {$sqlname}group.id
		WHERE
			{$sqlname}callcenter.id = '$id' and
			{$sqlname}callcenter.identity = '$identity'
		ORDER BY {$sqlname}callcenter.datum";

		$da = $db -> getRow( $query );

		$userResult = array_map( "current_user", explode( ",", (string)$da['operators'] ) );

		$u     = [];
		$users = yexplode( ",", (string)$da['iduser'] );
		foreach ( $users as $user ) {

			$u[] = current_user( $user );

		}

		$list = [
			"id"          => (int)$da['id'],
			"datum"       => get_sfdate( $da['datum'] ),
			"title"       => $da['title'],
			"content"     => nl2br( $da['content'] ),
			"status"      => strtr( $da['status'], self::STATUS ),
			"statusColor" => strtr( $da['status'], self::COLORS ),
			"dstart"      => get_sfdate( $da['dstart'] ),
			"dend"        => get_sfdate( $da['dend'] ),
			"gid"         => (int)$da['gid'],
			"user"        => yimplode( ", ", $u ),
			"users"       => $userResult,
			"scriptTitle" => $da['scriptTitle'],
			"Method"      => strtr( $da['Method'], [
				"randome" => "Случайно",
				"equal"   => "Равномерно"
			] )
		];

		//print_r($list);

		$progress = $this -> progress( [
			"task"     => $id,
			"identity" => $identity
		] );

		$progressColor = 'progress-red';

		$progressVal = (int)$progress['progress'];

		if ( $progressVal >= 50 && $progressVal < 80 ) {
			$progressColor = 'progress-blue';
		}
		elseif ( $progressVal >= 80 ) {
			$progressColor = 'progress-green';
		}

		return [
			"list"          => $list,
			"progress"      => $progress,
			"progressColor" => $progressColor
		];

	}

	/**
	 * Данные контакта для формы
	 *
	 * @param int $id
	 * @return array
	 */
	public function formdata(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$res = $db -> getRow( "
			SELECT
				{$sqlname}callcenter_list.id as id,
				{$sqlname}callcenter_list.task as task,
				{$sqlname}callcenter_list.datum as datum,
				{$sqlname}callcenter_list.clid as clid,
				{$sqlname}callcenter_list.pid as pid,
				{$sqlname}callcenter_list.phone as phone,
				{$sqlname}callcenter_list.content as content,
				{$sqlname}clientcat.title as client,
				{$sqlname}clientcat.tip_cmr as relation,
				{$sqlname}clientcat.idcategory as category,
				{$sqlname}clientcat.address as address,
				{$sqlname}clientcat.territory as territory,
				{$sqlname}personcat.person as person,
				{$sqlname}personcat.ptitle as ptitle,
				{$sqlname}personcat.loyalty as loyalty
			FROM {$sqlname}callcenter_list
				LEFT JOIN {$sqlname}callcenter ON {$sqlname}callcenter.id = {$sqlname}callcenter_list.task
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}callcenter_list.clid
				LEFT JOIN {$sqlname}personcat ON {$sqlname}personcat.pid = {$sqlname}callcenter_list.pid
			WHERE
				{$sqlname}callcenter_list.id = '$id' and
				{$sqlname}callcenter_list.identity = '$identity'
		" );

		$res['loyalty']  = ((int)$res['loyalty'] == 0) ? $GLOBALS['loyalDefault'] : $res['loyalty'];
		$res['relation'] = ((int)$res['relation'] == 0) ? $GLOBALS['relTitleDefault'] : $res['relation'];

		if ( (int)$res['clid'] < 1 ) {

			$res['clid'] = (int)getPersonData( $res['pid'], 'clid' );

			$client = get_client_info( $res['clid'], 'yes' );

			$res['client']    = $client['title'];
			$res['category']  = (int)$client['idcategory'];
			$res['address']   = $client['address'];
			$res['territory'] = (int)$client['territory'];

		}

		$list = [
			"id"        => (int)$res['id'],
			"pid"       => (int)$res['pid'],
			"phone"     => $res['phone'],
			"person"    => $res['person'],
			"ptitle"    => $res['ptitle'],
			"loyalty"   => $res['loyalty'],
			"clid"      => (int)$res['clid'],
			"client"    => $res['client'],
			"category"  => (int)$res['category'],
			"relation"  => (int)$res['relation'],
			"address"   => $res['address'],
			"territory" => (int)$res['territory'],
			"content"   => $res['content']
		];

		return ["list" => $list];

	}

	/**
	 * Добавление/Изменение валюты
	 *
	 * @param       $id     - id валюты
	 * @param array $params - параметры
	 *
	 * @return array
	 * @throws Exception
	 */
	public function edit($id, array $params = []): array {

		$identity = $this -> identity;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;

		$post = $params;

		$params = $db -> filterArray( $params, [
			'id',
			'gid',
			'datum',
			'title',
			'content',
			'dstart',
			'dend',
			'status',
			'iduser',
			'script',
			'scriptTitle',
			'operators',
			'Method',
			'dayoffset',
			'timeoffset',
			'identity'
		] );

		$param['title']       = untag( $params['title']);
		$param['status']      = $params['status'];
		$param['content']     = untag( $params['content'] );
		$param['script']      = $params['script'];
		$param['scriptTitle'] = $params['scriptTitle'];
		$param['iduser']      = !is_array($params['iduser']) ? $params['iduser'] : yimplode( ",", $params['iduser'] );
		$param['dstart']      = $params['dstart'].":00";
		$param['timeoffset']  = (int)$params['timeoffset'];
		$param['dayoffset']   = (int)$params['dayoffset'];
		$param['Method']      = $params['Method'];
		$param['operators']   = !is_array($params['operators']) ? $params['operators'] : yimplode( ",", $params['operators'] );
		$param['identity']    = $identity;

		$cfilter = $post['cfilter'];
		$pfilter = $post['pfilter'];

		$addbase   = $post['addbase'];
		$gid       = (int)$post['gid'];
		$deleteold = $post['deleteold'];

		$mess = $list = [];

		//очистим старый список контактов
		if ( $deleteold == 'yes' ) {
			$db -> query( "DELETE FROM ".$sqlname."callcenter_list WHERE task = '$id' AND isdo != 'yes'" );
		}

		//порядок сортировки
		$order = 0;

		//импортируем данные в список
		//разбираем запрос из файла
		if ( $_FILES['file']['name'] != '' ) {

			$ftitle = basename( $_FILES['file']['name'] );
			$fname  = time().".".getExtention( $ftitle );//переименуем файл

			$maxupload = ($GLOBALS['maxupload'] == '') ? str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) ) : $GLOBALS['maxupload'];

			$uploaddir = $this -> rootpath.'/files/'.$this -> fpath;
			$url       = $uploaddir.$fname;

			$ext_allow = [
				'csv',
				'xls',
				'xlsx'
			];
			$cur_ext   = texttosmall( getExtention( $ftitle ) );

			$res = 'error';

			//проверим тип файла на поддерживаемые типы
			if ( in_array( $cur_ext, $ext_allow ) ) {

				if ( (filesize( $_FILES['file']['tmp_name'] ) / 1000000) > $maxupload ) {
					$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Превышает допустимые размеры!';
				}

				elseif ( move_uploaded_file( $_FILES['file']['tmp_name'], $url ) ) {
					$res = "ok";
				}

				else {
					$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - '.$_FILES['file']['error'].'<br />';
				}

			}
			else {
				$mess[] = 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Файлы такого типа не разрешено загружать.';
			}

			$data = [];

			if ( $cur_ext == 'xls' ) {

				$datas = new Spreadsheet_Excel_Reader();
				$datas -> setOutputEncoding( 'UTF-8' );
				$datas -> read( $url, false );
				$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

				for ( $j = 0, $jMax = count( $data1 ); $j < $jMax; $j++ ) {

					for ( $g = 0, $gMax = count( $data1[ $j + 1 ] ); $g < $gMax; $g++ ) {

						$data[ $j ][] = untag( $data1[ $j + 1 ][ $g + 1 ] );

					}

				}

			}
			if ( $cur_ext == 'csv' || $cur_ext == 'xlsx' ) {

				$datas = new SpreadsheetReader( $url );
				$datas -> ChangeSheet( 0 );

				foreach ( $datas as $k => $Row ) {

					foreach ( $Row as $key => $value ) {

						$data[ $k ][] = ($cur_ext == 'csv') ? enc_detect( untag( $value ) ) : untag( $value );

					}

				}

			}

			//удаляем первую строку
			array_shift( $data );

			//перестроим индексы
			$list = array_values( $data );

		}

		try {

			if ( $id < 1 ) {

				$db -> query( "INSERT INTO ".$sqlname."callcenter SET ?u", $param );
				$id = $db -> insertId();

			}
			else {

				$db -> query( "UPDATE ".$sqlname."callcenter SET ?u WHERE id = '$id'", $param );

			}

			$mess[] = "Сделано";

		}
		catch ( Exception $e ) {

			$mess[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		//обрабатываем список
		$good = 0;
		foreach ( $list as $client ) {

			$callcenter = [];

			if ( $addbase == 'client' ) {

				//добавим клиента
				$clients = [
					"title"      => $client[0],
					"address"    => $client[2],
					"territory"  => $client[1],
					"phone"      => $client[3],
					"idcategory" => $client[4],
					"iduser"     => '0',
					"identity"   => $identity
				];

				$Client    = new Client();
				$newClient = $Client -> add( $clients );

				$clid = ((int)$newClient['data'] > 0) ? (int)$newClient['data'] : (int)$newClient['exists'];

				//print_r($newClient);

				//добавим запись в список заданий
				$callcenter = [
					"task"     => $id,
					"torder"   => $order,
					"clid"     => $clid,
					"phone"    => $client[3],
					"iduser"   => $param['iduser'],
					"identity" => $identity
				];

			}
			elseif ( $addbase == 'person' ) {

				//добавим клиента
				$clients = [
					"title"      => $client[4],
					"address"    => $client[6],
					"territory"  => $client[5],
					"phone"      => $client[3],
					"idcategory" => $client[7],
					"iduser"     => '0',
					"identity"   => $identity
				];

				$Client    = new Client();
				$newClient = $Client -> add( $clients );
				$clid      = ($newClient['data'] > 0) ? $newClient['data'] : $newClient['exists'];

				//добавим контакт
				$persons = [
					"person"   => $client[0],
					"ptitle"   => $client[1],
					"mail"     => $client[2],
					"tel"      => $client[3],
					"clid"     => $clid,
					"iduser"   => '0',
					"identity" => $identity
				];

				$Person = new Person();
				$result = $Person -> edit( 0, $persons );
				$pid    = $result['data'];

				//$db -> query("INSERT INTO ".$sqlname."personcat SET ?u", $persons);
				//$pid = $db -> insertId();

				//добавим запись в список заданий
				$callcenter = [
					"task"     => (int)$id,
					"torder"   => $order,
					"clid"     => (int)$clid,
					"pid"      => (int)$pid,
					"phone"    => $client[3],
					"iduser"   => (int)$param['iduser'],
					"identity" => $identity
				];

			}

			if ( !empty( $callcenter ) ) {

				$db -> query( "INSERT INTO ".$sqlname."callcenter_list SET ?u", $callcenter );

				$good++;
				$order++;

			}

		}

		if ( $good > 0 ) {
			$mess[] = "Загружено ".$good." записей из файла";
		}

		//импортируем из группы
		if ( $gid > 0 ) {

			$good = 0;

			$q = "
				SELECT 
					".$sqlname."grouplist.id,
					".$sqlname."clientcat.clid,
					".$sqlname."clientcat.phone,
					".$sqlname."personcat.pid,
					".$sqlname."personcat.tel,
					".$sqlname."personcat.mob
				FROM ".$sqlname."grouplist 
				LEFT JOIN ".$sqlname."clientcat ON ".$sqlname."clientcat.clid = ".$sqlname."grouplist.clid
				LEFT JOIN ".$sqlname."personcat ON ".$sqlname."personcat.pid  = ".$sqlname."grouplist.pid
				WHERE 
					(".$sqlname."clientcat.phone != '' OR ".$sqlname."personcat.mob != '' OR ".$sqlname."personcat.tel != '') AND 
					".$sqlname."grouplist.gid = '$gid' AND 
					".$sqlname."grouplist.identity = '$identity'
			";

			$res = $db -> getAll( $q );
			foreach ( $res as $glist ) {

				$p = [];

				if ( $glist['phone'] != '' ) {
					$p = preparePhoneSmart( $glist['phone'], false, true );
				}
				elseif ( $glist['tel'] != '' ) {
					$p = preparePhoneSmart( $glist['tel'], false, true );
				}
				elseif ( $glist['mob'] != '' ) {
					$p = preparePhoneSmart( $glist['mob'], false, true );
				}

				$s = ($glist['pid'] > 0) ? " AND pid = '".$glist['pid']."'" : " AND clid = '".$glist['clid']."'";

				//проверяем номер на наличие в списке задания
				$ex = (int)$db -> getOne( "SELECT id FROM ".$sqlname."callcenter_list WHERE task = '$id' $s AND identity = '$identity'" );
				if ( $ex == 0 && $p[0] != '' ) {

					$callcenter = [
						"task"     => $id,
						"torder"   => $order,
						"clid"     => (int)$glist['clid'],
						"pid"      => (int)$glist['pid'],
						"phone"    => $p[0],
						//"iduser"   => $param['iduser'],
						"identity" => $identity
					];

					$db -> query( "INSERT INTO ".$sqlname."callcenter_list SET ?u", $callcenter );

					$good++;
					$order++;

				}

			}

			$mess[] = "Загружено ".$good." записей из группы";

		}

		//импортируем из фильтров
		if ( $cfilter != '' ) {

			$query = getFilterQuery( 'client', [
				'filter' => $cfilter,
				'fields' => [
					'clid',
					'pid',
					'phone'
				]
			], false );

			$res = $db -> query( $query );
			while ($da = $db -> fetch( $res )) {

				$p = [];

				if ( $da['phone'] != '' ) {
					$p = preparePhoneSmart( $da['phone'], false, true );
				}
				elseif ( $da['tel'] != '' ) {
					$p = preparePhoneSmart( $da['tel'], false, true );
				}
				elseif ( $da['mob'] != '' ) {
					$p = preparePhoneSmart( $da['mob'], false, true );
				}

				$s = ($da['pid'] > 0) ? " AND pid = '".$da['pid']."'" : " AND clid = '".$da['clid']."'";

				//проверяем номер на наличие в списке задания
				$ex = (int)$db -> getOne( "SELECT id FROM ".$sqlname."callcenter_list WHERE task = '$id' $s AND identity = '$identity'" );
				if ( $ex == 0 && $p[0] != '' ) {

					$callcenter = [
						"task"     => (int)$id,
						"torder"   => $order,
						"clid"     => (int)$da['clid'],
						"pid"      => (int)$da['pid'],
						"phone"    => $p[0],
						//"iduser"   => (int)$param['iduser'],
						"identity" => $identity
					];

					$db -> query( "INSERT INTO ".$sqlname."callcenter_list SET ?u", $callcenter );

					$good++;
					$order++;

				}

			}

		}

		if ( $pfilter != '' ) {

			$query = getFilterQuery( 'person', [
				'filter'     => $pfilter,
				'fields'     => [
					'clid',
					'pid',
					'tel',
					'mob'
				],
				'selectplus' => $sqlname."clientcat.phone as phone",
			], false );

			$res = $db -> query( $query );
			while ($da = $db -> fetch( $res )) {

				$p = [];

				if ( $da['phone'] != '' ) {
					$p = preparePhoneSmart( $da['phone'], false, true );
				}
				elseif ( $da['tel'] != '' ) {
					$p = preparePhoneSmart( $da['tel'], false, true );
				}
				elseif ( $da['mob'] != '' ) {
					$p = preparePhoneSmart( $da['mob'], false, true );
				}

				$s = ($da['pid'] > 0) ? " AND pid = '".$da['pid']."'" : " AND clid = '".$da['clid']."'";

				//проверяем клиента/контакт на наличие в списке задания
				$ex = (int)$db -> getOne( "SELECT id FROM ".$sqlname."callcenter_list WHERE task = '$id' $s AND identity = '$identity'" );
				if ( $ex == 0 && $p[0] != '' ) {

					$callcenter = [
						"task"     => $id,
						"torder"   => $order,
						"clid"     => (int)$da['clid'],
						"pid"      => (int)$da['pid'],
						"phone"    => $p[0],
						//"iduser"   => (int)$param['iduser'],
						"identity" => $identity
					];

					$db -> query( "INSERT INTO ".$sqlname."callcenter_list SET ?u", $callcenter );

					$good++;
					$order++;

				}

			}

		}

		$message = implode( "<br>", $mess );

		return [
			"id"  => $id,
			"mes" => $message
		];

	}

	/**
	 * Удаление записи
	 *
	 * @param $id
	 * @return array
	 */
	public function delete($id): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$task = $this -> info( $id );

		if ( (int)$task['list']['id'] > 0 ) {

			$db -> query( "DELETE FROM {$sqlname}callcenter WHERE id = '$id'" );
			$db -> query( "DELETE FROM {$sqlname}callcenter_list WHERE task = '$id'" );

			$response = [
				"result"  => "success",
				"message" => "Успешно"
			];

		}
		else {

			$response = [
				"result" => "error",
				"error"  => [
					"code" => "404",
					"text" => "Запись не найдена"
				]
			];

		}

		return $response;

	}

	/**
	 * Удаление контакта из задания
	 *
	 * @param $id
	 * @return array|string[]
	 */
	public function deleteContact($id): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$task = $this -> info( $id );

		if ( (int)$task['id'] > 0 ) {

			$db -> query( "DELETE FROM ".$sqlname."callcenter_list WHERE id = '$id'" );

			$response = [
				"result"  => "success",
				"message" => "Успешно"
			];

		}
		else {

			$response = [
				"result" => "error",
				"error"  => [
					"code" => "404",
					"text" => "Запись не найдена"
				]
			];

		}

		return $response;

	}

}