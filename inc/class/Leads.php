<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Salesman;

use event;
use \PHPMailer\PHPMailer\Exception;
use SafeMySQL;
use Spreadsheet_Excel_Reader;
use stdClass;

/**
 * Класс для модуля Сборщик заявок
 *
 * Class Leads
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Leads {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone, $isCloud;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * @var false|string
	 */
	private $rootpath;

	/**
	 * Массив обработанных писем в заявки
	 *
	 * @see getMessages -> edit
	 * @var array - результат
	 *
	 *      - **id** - id заявки
	 *      - **result** = "Сделано"
	 *      - **error**
	 */
	public $note = [];

	/**
	 * Массив обработанных сообщений
	 *
	 * @see getMessages
	 * @var array
	 */
	public $messages = [];

	/**
	 * Массив ошибок
	 *
	 * @var array
	 */
	public $error = [];

	/**
	 * Массив не обработанных сообщений, т.к. они загружены ранее
	 *
	 * @see getMessages
	 * @var array
	 */
	public $ignored = [];

	/**
	 * Массив списка заявок для метода list
	 *
	 * @var array
	 */
	public $lists = [];

	/**
	 * статусы заявок
	 *
	 * @var array
	 */
	public const STATUSES = [
		0 => 'Открыт',
		1 => 'В работе',
		2 => 'Обработан',
		3 => 'Закрыт'
	];

	/**
	 * цвета статусов
	 *
	 * @var array
	 */
	public const COLORS = [
		0 => 'red',
		1 => 'green',
		2 => 'blue',
		3 => 'gray'
	];

	/**
	 * Статусы закрытия заявок
	 *
	 * @var array
	 */
	public const REZULTES = [
		1 => 'Спам',
		2 => 'Дубль',
		3 => 'Другое',
		4 => 'Не целевой'
	];

	/**
	 * Цвета статусов закрытия
	 *
	 * @var array
	 */
	public const COLORREZULT = [
		1 => 'red',
		2 => 'gray',
		3 => 'blue',
		4 => 'gray'
	];

	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$this -> rootpath = $rootpath;
		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> isCloud  = $GLOBALS['isCloud'];

		$this -> db = new SafeMySQL( $this -> opts );

	}

	/**
	 * Информация о заявке
	 *
	 * @param $id
	 *
	 * @return array (prixe, sklad)
	 */
	public static function info($id): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$field_types = db_columns_types( "{$sqlname}leads" );

		$lead = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '$id' and identity = '$identity'" );

		// очищаем от элементов с цифровым ключем
		foreach ( $lead as $k => $v ) {

			if ( is_numeric( $k ) ) {
				unset( $lead[ $k ] );
			}

			else {

				$lead[ $k ] = $field_types[ $k ] == 'int' ? (int)$v : $v;

			}

		}

		$lead['description'] = str_replace( "\t", "", $lead['description'] );

		$ruids = UIDs ::info( ["lid" => $id] );
		$uids  = $ruids['data'];

		if ( !empty( $uids ) ) {
			$lead['uids'] = $uids;
		}

		return $lead;

	}

	/**
	 * Список заявок
	 *
	 * @param array $params - параметры вывода, в т.ч. фильтры
	 *                      - int **page** - страница ( по 100 записей )
	 *                      - str **ord** - сортировка по полю
	 *                      - str **tuda** - направление вывода
	 *                      - date **da1** - дата начала
	 *                      - date **da2** - дата конец
	 *                      - str|array **status** - массив статусов
	 *                      - str|array **user** - массив пользователей
	 *                      - str **word** - поисковое слово
	 *                      - str **email** - поиск по email
	 *
	 *
	 * Example
	 *
	 * ```php
	 * $params = [
	 *      "page"   => $_REQUEST['page'],
	 *      "user"   => $_REQUEST['user'],
	 *      "status" => $_REQUEST['statuss'],
	 *      "da1"    => $_REQUEST['da1'],
	 *      "da2"    => $_REQUEST['da2'],
	 *      "email"  => $_REQUEST['email'],
	 *      "ord"    => $_REQUEST['ord'],
	 *      "tuda"   => $_REQUEST['tuda'],
	 *      "word"   => $_REQUEST['word'],
	 * ];
	 *
	 * $l = new Salesman\Leads();
	 * $l -> listLeads($params);
	 *
	 * $lists = $l -> lists;
	 * ```
	 */
	public function listLeads($params = []): void {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$leadsettings = $this -> settings( $identity );
		$coordinator  = $leadsettings["leadСoordinator"];

		$sort = '';
		$item = [];
		$page = $params['page'];

		if ( $params['ord'] == 'clientpath' ) {
			$ord = " ORDER BY {$sqlname}clientpath.name";
		}
		elseif ( $params['ord'] == 'user' ) {
			$ord = " ORDER BY {$sqlname}user.title";
		}
		else {
			$ord = " ORDER BY {$sqlname}leads.".$params['ord'];
		}

		$d1               = $params['da1'];
		$d2               = $params['da2'];
		$params['status'] = (is_array( $params['status'] )) ? yimplode( ",", (array)$params['status'] ) : $params['status'];
		$params['user']   = (is_array( $params['user'] )) ? yimplode( ",", (array)$params['user'] ) : $params['user'];

		//$icn = ($params['tuda'] == "desc") ? '<i class="icon-angle-down"></i>' : '<i class="icon-angle-up"></i>';
		//$so = ($leadsettings['leadMethod'] == 'free') ? " or {$sqlname}leads.iduser = 0" : "";

		if ( $params['user'] == '' ) {

			if ( $iduser1 != $coordinator ) {

				if ( $leadsettings['leadCanView'] != 'yes' ) {
					$sort .= str_replace( "iduser", $sqlname."leads.iduser", get_people( $iduser1 ) );
				}
				elseif ( $leadsettings['leadCanView'] == 'yes' ) {
					$sort .= " and {$sqlname}leads.iduser IN (".implode( ",", (array)get_userarray() ).") ";
				}

			}

		}
		elseif ( $leadsettings['leadMethod'] == 'free' ) {
			$sort .= " and ({$sqlname}leads.iduser IN (".implode( ",", (array)get_userarray() ).") or {$sqlname}leads.iduser = 0)";
		}

		if ( $params['user'] != '' ) {
			$sort .= " and {$sqlname}leads.iduser IN (".$params['user'].")";
		}

		if ( $params['status'] != '' && $params['word'] == '' ) {
			$sort .= " and {$sqlname}leads.status IN (".$params['status'].")";
		}

		if ( $params['word'] != '' ) {
			$sort .= " and ({$sqlname}leads.title LIKE '%$params[word]%' or {$sqlname}leads.email LIKE '%$params[word]%' or {$sqlname}leads.phone LIKE '%$params[word]%' or {$sqlname}leads.description LIKE '%$params[word]%')";
		}

		if ( $params['email'] != '' ) {
			$sort .= " and {$sqlname}leads.email = '$params[email]'";
		}

		if ( $params['email'] == '' && $d1 != '' && $params['word'] == '' ) {
			$sort .= " and ({$sqlname}leads.datum BETWEEN '$d1 00:00:01' and '$d2 23:59:59')";
		}

		$query = "
		SELECT
			{$sqlname}leads.id as id,
			{$sqlname}leads.datum as datum,
			{$sqlname}leads.datum_do as datum_do,
			{$sqlname}leads.title as title,
			{$sqlname}leads.email as email,
			{$sqlname}leads.phone as phone,
			{$sqlname}leads.status as status,
			{$sqlname}leads.rezult as rezult,
			{$sqlname}leads.company as company,
			{$sqlname}leads.clientpath as idpath,
			{$sqlname}leads.iduser as iduser,
			{$sqlname}leads.pid as pid,
			{$sqlname}leads.clid as clid,
			{$sqlname}leads.did as did,
			{$sqlname}user.title as user,
			{$sqlname}clientpath.name as clientpath,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}dogovor.title as deal
		FROM {$sqlname}leads
			LEFT JOIN {$sqlname}user ON {$sqlname}leads.iduser = {$sqlname}user.iduser
			LEFT JOIN {$sqlname}personcat ON {$sqlname}leads.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}leads.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}leads.did = {$sqlname}dogovor.did
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}leads.clientpath = {$sqlname}clientpath.id
		WHERE
			{$sqlname}leads.id > 0
			".$sort."
			and {$sqlname}leads.identity = '$identity'
		";

		$result    = $db -> query( $query );
		$all_lines = $db -> numRows( $result );

		$lines_per_page = 100;

		if ( $all_lines / $lines_per_page < 2 ) {
			$page = 1;
		}

		if ( !isset( $page ) || empty( $page ) || $page <= 0 ) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query .= " $ord $params[tuda] LIMIT $lpos,$lines_per_page";

		$count_pages = ceil( $all_lines / $lines_per_page );
		if ( $count_pages < 1 ) {
			$count_pages = 1;
		}

		$result = $db -> getAll( $query );
		foreach ( $result as $da ) {

			$do          = '';
			$status      = '';
			$statusclass = '';
			$rezult      = '';
			$rezultclass = '';
			$countemail  = $countphone = 0;
			$action      = [];
			$stat        = '';
			$del         = '';

			//ищем в базе лиды с походими данными
			if ( $da['email'] != '' ) {
				$countemail = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE email LIKE '%".$da['email']."%' and identity = '$identity'" );
			}

			if ( $da['phone'] != '' ) {
				$countphone = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}leads WHERE replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".substr( prepareMobPhone( $da['phone'] ), 1 )."%' and identity = '$identity'" );
			}

			switch ($da['status']) {
				case "0":

					if ( $coordinator == $iduser1 || $leadsettings['leadMethod'] == 'free' ) {

						$action = [
							"setuser" => true,
							"workit"  => true,
							"view"    => true,
							"status"  => strtr( $da['status'], self::STATUSES )
						];

					}

				break;
				case "1":

					if ( $da['iduser'] == $iduser1 || $coordinator == $iduser1 ) {

						$action = [
							"workit" => true,
							"view"   => true,
							"status" => strtr( $da['status'], self::STATUSES )
						];

					}

				break;
				case "2":
				case "3":

					if ( $da['iduser'] == $iduser1 || $coordinator == $iduser1 ) {

						$action = [
							"view"   => true,
							"status" => strtr( $da['status'], self::STATUSES )
						];

					}

				break;

			}

			if ( $coordinator == $iduser1 && $leadsettings['leadCanDelete'] != 'nodelete' ) {

				if ( $leadsettings['leadCanDelete'] == 'unknown' && $da['clientpath'] == '' ) {
					$del = '1';
				}
				elseif ( $leadsettings['leadCanDelete'] == 'nophone' && $da['phone'] == '' ) {
					$del = '1';
				}
				elseif ( $leadsettings['leadCanDelete'] == 'noemail' && $da['email'] == '' ) {
					$del = '1';
				}
				else {
					$del = '1';
				}

				$edit = ($da['status'] != '3' && $da['status'] != '2') ? '1' : '';

			}
			else {

				$edit = ($da['status'] != '3' && $da['status'] != '2' && $iduser1 == $da['iduser']) ? '1' : '';

			}

			if ( in_array( $da['status'], [
				'0',
				'1',
				'2'
			] ) ) {

				$statusclass = strtr( $da['status'], self::COLORS );
				$status      = strtr( $da['status'], self::STATUSES );

			}

			if ( $da['rezult'] ) {

				$rezultclass = strtr( $da['rezult'], self::COLORREZULT );
				$rezult      = strtr( $da['rezult'], self::REZULTES );

			}

			if ( $da['datum_do'] != '0000-00-00 00:00:00' && $da['datum_do'] != '' ) {
				$do = get_hist( $da['datum_do'] );
			}

			$array = explode( ",", str_replace( ";", ",", str_replace( " ", "", $da['phone'] ) ) );
			$phone = array_shift( $array );

			$array1 = explode( ",", str_replace( ";", ",", str_replace( " ", "", $da['email'] ) ) );
			$email  = array_shift( $array1 );

			$item[] = [
				"id"          => $da['id'],
				"datum"       => get_hist( $da['datum'] ),
				"ddo"         => $do,
				"title"       => $da['title'],
				"email"       => link_it( $email ),
				"temail"      => $da['email'],
				"phone"       => formatPhoneUrl( $phone, $da['clid'], $da['pid'] ),
				"tphone"      => $phone,
				"countemail"  => $countemail > 1 ? $countemail : NULL,
				"countphone"  => $countphone > 1 ? $countphone : NULL,
				"clientpath"  => $da['clientpath'],
				"company"     => $da['company'],
				"status"      => $status,
				"statusclass" => $statusclass,
				"rezult"      => $rezult,
				"rezultclass" => $rezultclass,
				"clid"        => $da['clid'],
				"client"      => $da['client'],
				"pid"         => $da['pid'],
				"person"      => $da['person'],
				"did"         => $da['did'],
				"deal"        => $da['deal'],
				"user"        => $da['user'],
				"action"      => !empty( $action ) ? $action : NULL,
				"stat"        => $stat,
				"edit"        => $edit,
				"del"         => $del
			];

		}

		$lists = [
			"list"    => $item,
			"page"    => $page,
			"orderby" => $ord,
			"desc"    => $params['tuda'],
			"pageall" => $count_pages
		];

		$this -> lists = $lists;

	}

	/**
	 * Список каналов
	 *
	 * @param array $params - параметры вывода, в т.ч. фильтры
	 *                      - int **page** - страница ( по 100 записей )
	 *                      - str **ord** - сортировка по полю
	 *                      - str **tuda** - направление вывода
	 *                      - str **word** - поисковое слово
	 */
	public function listSources($params = []): void {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$isadmin  = $GLOBALS['isadmin'];

		$leadsettings = (new Leads) -> settings( $identity );
		$coordinator  = $leadsettings["leadСoordinator"];

		$sort = '';
		$item = [];
		$page = $params['page'];

		$ord = " ORDER BY {$sqlname}clientpath.".$params['ord'];

		if ( $params['word'] != '' ) {
			$sort .= " and ({$sqlname}clientpath.name LIKE '%$params[word]%' or {$sqlname}clientpath.utm_source LIKE '%$params[word]%')";
		}

		$query = "
		SELECT
			{$sqlname}clientpath.id,
			{$sqlname}clientpath.name,
			{$sqlname}clientpath.utm_source,
			{$sqlname}clientpath.destination,
			{$sqlname}clientpath.isDefault
		FROM {$sqlname}clientpath
		WHERE
			{$sqlname}clientpath.id > 0
			".$sort."
			and {$sqlname}clientpath.identity = '$identity'
		";

		$result    = $db -> query( $query );
		$all_lines = $db -> numRows( $result );

		$lines_per_page = 100;

		if ( $all_lines / $lines_per_page < 2 ) {
			$page = 1;
		}

		if ( !isset( $page ) || empty( $page ) || $page <= 0 ) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query .= " $ord $params[tuda] LIMIT $lpos,$lines_per_page";

		$count_pages = ceil( $all_lines / $lines_per_page );
		if ( $count_pages < 1 ) {
			$count_pages = 1;
		}

		$result = $db -> getAll( $query );
		foreach ( $result as $da ) {

			$item[] = [
				"id"          => $da['id'],
				"name"        => $da['name'],
				"utm_source"  => $da['utm_source'],
				"destination" => $da['destination'],
				"isDefault"   => ($da['isDefault'] == 'yes') ? true : ""
			];

		}

		$edit = ($coordinator == $iduser1 || $isadmin == 'on') ? "yes" : "";

		$lists = [
			"list"    => $item,
			"page"    => (int)$page,
			"orderby" => $ord,
			"desc"    => $params['tuda'],
			"pageall" => (int)$count_pages,
			"edit"    => $edit
		];

		$this -> lists = $lists;

	}

	/**
	 * Список UTM-ссылок
	 *
	 * @param array $params - параметры вывода, в т.ч. фильтры
	 *                      - int **page** - страница ( по 100 записей )
	 *                      - str **ord** - сортировка по полю
	 *                      - str **tuda** - направление вывода
	 *                      - str **word** - поисковое слово
	 */
	public function listUTM($params = []): void {

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$isadmin  = $GLOBALS['isadmin'];

		$leadsettings = (new Leads) -> settings( $identity );
		$coordinator  = $leadsettings["leadСoordinator"];

		$sort   = '';
		$item   = [];
		$page   = $params['page'];
		$target = time();

		if ( $params['ord'] == 'clientpath' ) {
			$ord = " ORDER BY {$sqlname}clientpath.name";
		}
		else {
			$ord = " ORDER BY {$sqlname}leads_utm.".$params['ord'];
		}

		if ( $params['word'] != '' ) {
			$sort .= " and ({$sqlname}leads_utm.url LIKE '%$params[word]%' or {$sqlname}leads_utm.utm_source LIKE '%$params[word]%' or {$sqlname}clientpath.name LIKE '%$params[word]%' or {$sqlname}leads_utm.utm_url LIKE '%$params[word]%')";
		}

		$query = "
		SELECT
			{$sqlname}leads_utm.id,
			{$sqlname}leads_utm.datum,
			{$sqlname}leads_utm.utm_url,
			{$sqlname}leads_utm.utm_source,
			{$sqlname}leads_utm.utm_medium,
			{$sqlname}leads_utm.utm_campaign,
			{$sqlname}leads_utm.utm_term,
			{$sqlname}leads_utm.utm_content,
			{$sqlname}clientpath.name as clientpath
		FROM {$sqlname}leads_utm
			LEFT JOIN {$sqlname}clientpath ON {$sqlname}leads_utm.clientpath = {$sqlname}clientpath.id
		WHERE
			{$sqlname}leads_utm.id > 0
			".$sort."
			and {$sqlname}leads_utm.identity = '$identity'
		";

		$result    = $db -> query( $query );
		$all_lines = $db -> numRows( $result );

		$lines_per_page = 100;

		if ( $all_lines / $lines_per_page < 2 ) {
			$page = 1;
		}

		if ( !isset( $page ) || empty( $page ) || $page <= 0 ) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		$query .= " $ord $params[tuda] LIMIT $lpos,$lines_per_page";

		$count_pages = ceil( $all_lines / $lines_per_page );
		if ( $count_pages < 1 ) {
			$count_pages = 1;
		}

		$result = $db -> getAll( $query );
		foreach ( $result as $da ) {

			$item[] = [
				"id"           => $da['id'],
				"datum"        => get_date( $da['datum'] ),
				"utm_url"      => $da['utm_url'],
				"utm_source"   => $da['utm_source'],
				"utm_medium"   => $da['utm_medium'],
				"utm_campaign" => $da['utm_campaign'],
				"utm_term"     => $da['utm_term'],
				"clientpath"   => $da['clientpath'],
				"target"       => $target++
			];

		}

		$edit = ($coordinator == $iduser1 || $isadmin == 'on') ? "yes" : "";


		$lists = [
			"list"    => $item,
			"page"    => $page,
			"orderby" => $ord,
			"desc"    => $params['tuda'],
			"pageall" => $count_pages,
			"edit"    => $edit
		];

		$this -> lists = $lists;

	}

	/**
	 * Добавление/изменение заявки
	 *
	 * @param array $params - параметры
	 *                      - int **iduser** - id куратора заявки (не обязательный)
	 *                      - str **tel** | **phone** - номер телефона
	 *                      - str **email** - email
	 *                      - int **clid** - id клиента (не обязательный)
	 *                      - int **pid** - id контакта
	 *                      - str **title** - ФИО
	 *                      - str **company** - название компании
	 *                      - str **site** - сайт
	 *                      - str **city** - город
	 *                      - str **country** - страна
	 *                      - int **partner** - id партнера (не обязательный)
	 *                      - str **description** - комментарий к заявке (содержимое сообщения)
	 *                      - str **muid** - messageid для письма (чтобы исключать дубли)
	 *                      - str|int **clientpath** - id канала или его название
	 *                      - str **utm_medium** - Тип трафика (не обязательный)
	 *                      - str **utm_campaign** - Рекламная Кампания (не обязательный)
	 *                      - str **utm_term** - Ключевая фраза (не обязательный)
	 *                      - str **utm_content** - Содержание (не обязательный)
	 *                      - str **utm_referrer** - Аналогичен clientpath (не обязательный)
	 *                      - array **uids** - массив внешних uids (не обязательный)
	 *                      - uid => value
	 *                      - int **identity** - идентификатор аккаунта (не обязательный)
	 *
	 * @return array - массив результата
	 *          - **id** - id заявки ( lid )
	 *          - **result** - Сделано
	 *          - **error** - ошибка
	 *
	 * @throws Exception
	 */
	public function edit(array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$post = $params;

		if ( (int)$params['id'] == 0 ) {
			$params = $hooks -> apply_filters( "lead_addfilter", $params );
		}

		else {
			$params = $hooks -> apply_filters( "lead_editfilter", $params );
		}

		$settings = $this -> settings( $identity );

		$params['phone'] = $params['tel'] ?? $params['phone'];

		//если не задан клиент и контакт. то найдем его по базе
		$client = (!isset( $params['clid'] ) && !isset( $params['pid'] )) ? $this -> getClient( [
			"phone"    => $params['phone'],
			"email"    => $params['email'],
			"identity" => $identity
		] ) : [];

		unset( $params['action'] );

		$err = [];

		$id                  = (int)$params['id'];
		$lead['title']       = untag( $params['title'] );
		$lead['email']       = untag( $params['email'] );
		$lead['phone']       = yimplode( ",", (array)$params['phone'] );
		$lead['site']        = untag( $params['site'] );
		$lead['city']        = untag( $params['city'] );
		$lead['clientpath']  = is_numeric( $params['clientpath'] ) ? $params['clientpath'] : self ::getPath( $params['clientpath'] );
		$lead['partner']     = $params['partner'];
		$lead['description'] = untag( $params['description'] );
		$lead['iduser']      = isset( $params['iduser'] ) ? (int)$params['iduser'] : 0;
		$lead['muid']        = $params['muid'];
		$lead['company']     = untag( $params['company'] );
		$lead['country']     = untag( $params['country'] );
		$lead['clid']        = (isset( $params['clid'] )) ? (int)$params['clid'] : (int)$client['clid'];
		$lead['pid']         = (isset( $params['pid'] )) ? (int)$params['pid'] : (int)$client['pid'];

		$lead['utm_source'] = $db -> getOne( "SELECT utm_source FROM {$sqlname}clientpath WHERE id = '$lead[clientpath]' and identity = '$identity'" );

		$lead['utm_medium']   = untag( $params['utm_medium'] );
		$lead['utm_campaign'] = untag( $params['utm_campaign'] );
		$lead['utm_term']     = untag( $params['utm_term'] );
		$lead['utm_content']  = untag( $params['utm_content'] );
		$lead['utm_referrer'] = untag( $params['utm_referrer'] );

		if ( (int)$lead['clid'] < 1 ) {
			$lead['clid'] = 0;
		}

		if ( $lead['pid'] < 1 ) {
			$lead['pid'] = 0;
		}

		if ( $id < 1 ) {

			$lead['datum']    = current_datumtime();
			$lead['status']   = ($lead['iduser'] > 0) ? 1 : 0;
			$lead['identity'] = $identity;

			$db -> query( "INSERT INTO {$sqlname}leads SET ?u", arrayNullClean( $lead ) );
			$id = $db -> insertId();

			$notify['notice'] = 'no';
			$notify['id']     = $id;

			$lead['id'] = $id;

			if ( $hooks ) {
				$hooks -> do_action( "lead_add", $post, $lead );
			}


			//отправим уведомление куратору
			if ( $lead['iduser'] > 0 && $settings['leadSendOperatorNotify'] == 'yes' ) {

				sendNotify( 'lead_setuser', $notify );

				/**
				 * Уведомления
				 */
				Notify ::fire( "lead.setuser", $lead['iduser'], $notify );

				event ::fire( 'lead.setuser', $args = [
					"id"     => $id,
					"clid"   => $lead['clid'],
					"pid"    => $lead['pid'],
					"did"    => 0,
					"iduser" => $lead['iduser']
				] );

			}

			//или координатору
			elseif ( $settings['leadSendCoordinatorNotify'] == 'yes' ) {

				sendNotify( 'lead_add', $notify );

				event ::fire( 'lead.add', $args = [
					"id"     => $id,
					"clid"   => $lead['clid'],
					"pid"    => $lead['pid'],
					"did"    => 0,
					"iduser" => $settings["leadСoordinator"]
				] );

			}

			/**
			 * Уведомления
			 */
			Notify ::fire( "lead.add", 0, $notify );

			//отправим уведомление клиенту
			if ( $lead['iduser'] > 0 && $settings['leadSendClientWellcome'] == 'yes' ) {

				$this -> sendNotifyClient( [
					"id"     => $id,
					"type"   => "wellcome",
					"clid"   => $lead['clid'],
					"pid"    => $lead['pid'],
					"did"    => 0,
					"iduser" => $lead['iduser']
				] );

			}

			addHistorty( [
				"datum"    => current_datumtime(),
				"iduser"   => $iduser1,
				"des"      => 'Добавлен лид #'.$id,
				"tip"      => 'СобытиеCRM',
				"identity" => $identity
			] );

			/**
			 * Обработаем внешние ID
			 */
			//$ruid = [];
			$arg = [
				"lid"  => $id,
				"clid" => $lead['clid']
			];

			foreach ( $params['uids'] as $key => $value ) {

				//$ruid[] = UIDs ::edit( $key, $value, $arg );

				$prms = [
					"lid"  => $id,
					"clid" => $lead['clid'],
					"uids" => [
						$key => $value
					]
				];

				UIDs ::add( $prms );

			}

		}
		else {

			//$db -> query("ALTER TABLE {$sqlname}leads CHANGE COLUMN `datum` `datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `id`");

			$lead['status'] = ($lead['iduser'] > 0) ? 1 : 0;
			$db -> query( "UPDATE {$sqlname}leads SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean( $lead ) );

			$lead['id'] = $id;

			if ( $hooks ) {
				$hooks -> do_action( "lead_edit", $post, $lead );
			}

		}

		return [
			"id"     => $id,
			"result" => "Сделано",
			"error"  => $err
		];

	}

	/**
	 * Удаление заявки
	 *
	 * @param $id
	 * @return array
	 */
	public static function delete($id): array {

		global $hooks;

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$error = '';

		if ( $id > 0 ) {

			$db -> query( "DELETE FROM {$sqlname}leads WHERE id = '$id' and identity = '$identity'" );

			if ( $hooks ) {
				$hooks -> do_action( "lead_delete", $id );
			}

		}
		else {

			$error = 'Не указан ID заявки';

		}

		return [
			"result" => "Сделано",
			"error"  => $error
		];

	}

	/**
	 * Назначение заявки пользователю или дисквалификация
	 *
	 * @param array $params - параметры
	 *                      - int **id** - id заявки ( lid )
	 *                      - int **iduser** - id куратора, которому названаем заявку
	 *                      - str **rezult** - комментарий для кураторв ( если пусто, то считаем, что заявка
	 *                      дисквалифицирована )
	 *                      - str **rezz** - комментарий при дисквалификации заявки
	 *                      - int **identity** - идентификатор аккаунта (не обязательный)
	 *
	 * @return array - массив результата
	 *       - **id** - id заявки ( lid )
	 *       - **result** - Сделано
	 *       - **error** - ошибка
	 *
	 * @throws Exception
	 */
	public function setuser($params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$post = $params;

		$params = $hooks -> apply_filters( "lead_setuserfilter", $params );

		$settings = $this -> settings( $identity );

		unset( $params['action'] );

		$id     = $params['id'];
		$iduser = $params['iduser'];
		$rezult = $params['rezult'];
		$rezz   = untag( $params['rezz'] );

		$err = '';

		if ( $iduser > 0 && $rezult == '' ) {

			//интерес квалифицирован и передан в работу сотруднику, статус изменен на "В работе"
			$db -> query( "UPDATE {$sqlname}leads SET ?u WHERE id = '$id' and identity = '$identity'", $lead = [
				"iduser" => $iduser,
				"status" => '1'
			] );

			$lead['id'] = $id;

			if ( $hooks ) {
				$hooks -> do_action( "lead_setuser", $post, $lead );
			}

			addHistorty( [
				"datum"    => current_datumtime(),
				"iduser"   => $iduser1,
				"des"      => 'Назначен лид #'.$id,
				"tip"      => 'СобытиеCRM',
				"identity" => $identity
			] );

			$notify['notice'] = 'yes';
			$notify['id']     = $id;

			//отправим уведомление куратору
			if ( $settings['leadSendOperatorNotify'] == 'yes' ) {
				sendNotify( 'lead_setuser', $notify );
			}

			//отправим уведомление клиенту
			if ( $settings['leadSendClientWellcome'] == 'yes' ) {

				$this -> sendNotifyClient( [
					"id"   => $id,
					"type" => "wellcome"
				] );

			}

			/**
			 * Уведомления
			 */
			Notify ::fire( "lead.setuser", $iduser1, $notify );

			event ::fire( 'lead.setuser', $args = [
				"id"          => $id,
				"iduser"      => $iduser,
				"coordinator" => $settings['leadСoordinator']
			] );

		}
		elseif ( $rezult != '' ) {

			//интерес дисквалифицирован (отсеян координатором) и закрыт с результатом
			//1 = Спам, 2 - Дубль, 3 - Другое
			//Установлен статус 3 - Закрыт
			$db -> query( "UPDATE {$sqlname}leads SET ?u WHERE id = '$id' and identity = '$identity'", $lead = [
				"datum_do" => current_datumtime(),
				"rezult"   => $rezult,
				"status"   => '3',
				"rezz"     => $rezz
			] );

			$lead['id'] = $id;

			if ( $hooks ) {
				$hooks -> do_action( "lead_close", $post, $lead );
			}

			addHistorty( [
				"datum"    => current_datumtime(),
				"iduser"   => $iduser1,
				"des"      => 'Обработан лид #'.$id.': '.$rezz,
				"tip"      => 'СобытиеCRM',
				"identity" => $identity
			] );

			event ::fire( 'lead.do', $args = [
				"id"          => $id,
				"iduser"      => $iduser,
				"coordinator" => $settings['leadСoordinator']
			] );

		}
		else {
			$err = "Не выбрано действий";
		}

		return [
			"id"     => $id,
			"result" => "Сделано",
			"error"  => $err
		];

	}

	/**
	 * Обработка заявки сотрудником, либо закрытие
	 *
	 * @param array $params - параметры
	 *                      - int **id** - id заявки ( lid )
	 *                      - int **iduser** - id куратора, которому названаем заявку
	 *                      - int **clid** - id клиента ( если не задан, то будет добавлен новый )
	 *                      - int **pid** - id контакта ( если не задан, то будет добавлен новый )
	 *                      - str **rezult** - комментарий для кураторов ( если пусто, то считаем, что заявка
	 *                      обрабатывается, иначе - дисквалифицирована )
	 *                      - str **rezz** - комментарий при дисквалификации заявки ( если rezult - пусто )
	 *
	 *      - Для добавления Контакта:
	 *          - str **personname** - ФИО
	 *          - str **ptitle** - должность
	 *          - str **mail** - email
	 *          - array **tel** - массив номеров телефона
	 *          - int **personpath** - id источника клиента
	 *          - int **loyalty** - id лояльности
	 *
	 *      - Для добавления Клиента:
	 *          - str **type** - тип клиента ( client, person, contractor, ... )
	 *          - str **title** - название
	 *          - str **mail_url** - email
	 *          - str **site_url** - сайт
	 *          - array **phone** - массив номеров телефонов
	 *          - str **address** - адрес
	 *          - int **clientpath** - id канала
	 *          - str **tip_cmr** - тип отношений
	 *          - int **territory** - id территории
	 *          - int **idcategory** - id категории
	 *
	 *      - Для добавления Сделки:
	 *          - str **dodog** = yes - если будем добавлять договор
	 *          - str **dogovor** - название Сделки
	 *          - date **datum_plan** - плановая дата
	 *          - int **tip** - id типа сделки
	 *          - int **direction** - id направления
	 *          - int **step** - id этапа
	 *          - str **description** - описание сделки
	 *
	 *      - Для добавления Активности:
	 *          - str **content** - если будем добавлять договор
	 *          - str **tiphist** - тип активности
	 *
	 *      - int **identity** - идентификатор аккаунта (не обязательный)
	 *
	 * @return array -  массив результата
	 *      - int **id**     - id заявки
	 *      - str **result** = Сделано
	 *      - str **error**  - ошибки
	 *      - int **did**    - id сделки
	 *      - int **clid**   - id клиента
	 *
	 * @throws Exception
	 * @throws Exception
	 */
	public function workit(array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$post = $params;

		$params = $hooks -> apply_filters( "lead_workitfilter", $params );

		$settings = $this -> settings( $identity );

		unset( $params['action'] );

		$id     = (int)$params['id'];
		$rezult = $params['rezult'];
		$rezz   = $params['rezz'];

		$errors = [];
		$err    = [];

		$person['person']     = $params['personname'];
		$person['ptitle']     = $params['ptitle'];
		$person['mail']       = $params['mail'];
		$person['tel']        = $params['tel'];
		$person['clientpath'] = $params['personpath'];
		$person['loyalty']    = $params['loyalty'];

		$client['type']       = $params['type'];
		$client['title']      = $params['title'];
		$client['mail_url']   = $params['mail_url'];
		$client['site_url']   = $params['site_url'];
		$client['phone']      = yimplode( ",", (array)$params['phone'] );
		$client['address']    = $params['address'];
		$client['clientpath'] = $params['clientpath'];
		$client['tip_cmr']    = $params['tip_cmr'];
		$client['territory']  = $params['territory'];
		$client['idcategory'] = $params['idcategory'];

		$description = $params['deal']['content'];

		$pid  = (int)$params['pid'];
		$clid = (int)$params['clid'];
		//$partner = $params['partner'];

		$content = untag( $params['content'] );
		$tiphist = untag( $params['tiphist'] );

		$fclient = untag( $params['client'] );
		$fperson = untag( $params['person'] );

		//$clientAdd = $personAdd = $dealAdd = 'no';
		$did = 0;

		//если мы обрабатываем заявку
		if ( $rezult == '' ) {

			//обработка в случае, если с заявкой сопоставлен Клиент и/или Контакт
			if ( $clid > 0 && $fclient != '' ) {

				$cclient = get_client_info( $clid, "yes" );

				$client['mail_url'] = ($cclient['mail_url'] != '') ? prepareStringSmart( $client['mail_url'].", ".$cclient['mail_url'] ) : $params['mail_url'];
				$client['site_url'] = ($cclient['site_url'] != '') ? prepareStringSmart( $client['site_url'].", ".$cclient['site_url'] ) : $params['site_url'];
				$client['phone']    = ($cclient['phone'] != '') ? $client['phone'].", ".$cclient['phone'] : $client['phone'];

				$client['date_edit'] = current_datumtime();
				$client['editor']    = $iduser1;

				// сохраняем название старым
				unset($client['title']);

				//обработка на исключение дублей
				$client['phone'] = preparePhoneSmart( $client['phone'] );

				/*
				$client['clid'] = $clid;
				if($hooks)
					$client = $hooks -> apply_filters( "client_editfilter", $client );
				*/

				//Обновим клиента
				$Client  = new Client();
				$cresult = $Client -> update( $clid, $client );

				/*
				if($hooks)
					$hooks -> do_action( "client_edit", $client );
				*/

				if ( $cresult['result'] == 'Error' ) {
					$err[] = $cresult['error']['text'];
				}

				//$db -> query("update {$sqlname}clientcat set ?u WHERE clid = '".$clid."' and identity = '$identity'", $client);

				//обработаем изменения в логе
				doLogger( 'clid', $clid, [
					"phone"    => $client['phone'],
					"mail_url" => $client['mail_url'],
					"site_url" => $client['site_url']
				], [
					"phone"    => $cclient['phone'],
					"mail_url" => $cclient['mail_url'],
					"site_url" => $cclient['site_url']
				], true );

			}
			if ( $pid > 0 && $fperson != '' ) {

				$cperson = get_person_info( $pid, "yes" );

				$t = $m = [];

				$person['mail'] = ($cperson['mail'] != '') ? prepareStringSmart( $person['mail'].", ".$cperson['mail'] ) : $person['mail'];
				$person['tel']  = (is_array( $person['tel'] )) ? $person['tel'] : yexplode( ",", (string)$person['tel'] );
				$person['tel']  = array_merge( $person['tel'], yexplode( ",", (string)$cperson['tel'] ), yexplode( ",", (string)$cperson['mob'] ) );

				foreach ( $person['tel'] as $item ) {

					if ( !isPhoneMobile( $item ) ) {
						$t[] = $item;
					}
					else {
						$m[] = $item;
					}

				}

				$person['tel'] = preparePhoneSmart( $t, true );
				$person['mob'] = preparePhoneSmart( $m, true );

				$person['date_edit'] = current_datumtime();
				$person['editor']    = $iduser1;

				$person['pid'] = $pid;

				// сохраняем название старым
				unset($person['person']);

				/*if($hooks)
					$person = $hooks -> apply_filters( "person_editfilter", $person );*/

				$Person = new Person();
				$Person -> edit( $pid, $person );

				/*if($hooks)
					$hooks -> do_action( "person_edit", $person );*/

				//$db -> query( "update {$sqlname}personcat set ?u WHERE pid = '".$pid."' and identity = '$identity'", $person );

				//обработаем изменения в логе
				doLogger( 'pid', $pid, [
					"tel"  => $person['tel'],
					"mail" => $person['mail'],
					"mob"  => $person['mob']
				], [
					"tel"  => $cperson['tel'],
					"mail" => $cperson['mail'],
					"mob"  => $cperson['mob']
				], true );

			}

			$person['tel'] = yimplode( ", ", (array)$person['tel'] );

			//добавим клиента
			if ( $clid == 0 && $client['title'] != '' ) {

				$client['date_create'] = current_datumtime();
				$client['creator']     = $iduser1;
				$client['identity']    = $identity;

				/*if($hooks)
					$client = $hooks -> apply_filters( "client_addfilter", $client );*/

				//Добавим клиента
				$Client  = new Client();
				$cresult = $Client -> add( $client );

				//print_r($cresult);

				if ( $cresult['result'] == 'Error' ) {
					$errors[] = $cresult['error']['text'];
				}
				else {

					$clid = (int)$cresult['data'];
					//$clientAdd = 'yes';

					$client['clid'] = (int)$cresult['data'];

					/*if($hooks)
						$hooks -> do_action( "client_add", $client );*/

				}

			}

			//добавим контакт
			if ( $pid == 0 && $person['person'] != '' ) {

				$person['date_create'] = current_datumtime();
				$person['creator']     = $iduser1;
				$person['clientpath']  = $params['clientpath'];
				$person['identity']    = $identity;
				$person['clid']        = $clid;
				$person['mperson']     = "yes";

				$Person  = new Person();
				$presult = $Person -> edit( 0, $person );

				if ( $presult['result'] == 'Error' ) {
					$errors[] = $presult['error']['text'];
				}

				else {

					$pid = (int)$presult['data'];
					//$personAdd = 'yes';

					$person['pid'] = $pid;

					/*if($hooks)
						$hooks -> do_action( "person_add", $person );*/

				}

			}

			$dodog   = $params['dodog'];
			$dogovor = $params['deal'];

			//$dogovor['mc']         = $GLOBALS['mcDefault'];
			//$dogovor['lid']        = $params['id'];

			//добавим сделку
			if ( $dogovor['title'] != '' && $dodog == 'yes' ) {

				//$dNum = generate_num( 'dogovor' );
				//if( $dNum != '' ) $dogovor['title'] = $dNum.": ".$dogovor['title'];

				if ( $clid > 0 ) {
					$dogovor['pid_list'] = $pid;
				}
				else {
					$dogovor['pid'] = $pid;
				}

				$dogovor['datum']    = current_datumtime();
				$dogovor['iduser']   = $iduser1;
				$dogovor['autor']    = $iduser1;
				$dogovor['identity'] = $identity;
				$dogovor['clid']     = $clid;

				$Deal    = new Deal();
				$dresult = $Deal -> add( $dogovor );


				if ( $dresult['result'] == 'Error' ) {
					$errors[] = $dresult['error']['text'];
				}
				else {
					$did = (int)$dresult['data'];
					//$dealAdd = 'yes';
				}

			}


			//Обновляем статус заявки
			$db -> query( "UPDATE {$sqlname}leads SET ?u WHERE id = '$id' and identity = '$identity'", $lead = [
				"iduser"   => $iduser1,
				"datum_do" => current_datumtime(),
				"status"   => '2',
				"clid"     => $clid,
				"pid"      => $pid,
				"did"      => $did
			] );

			if ( $hooks ) {
				$hooks -> do_action( "lead_workit", $post, $lead );
			}

			$notify['notice'] = 'yes';
			$notify['id']     = $id;

			if ( $iduser1 != $settings['leadСoordinator'] && $settings['leadSendCoordinatorNotify'] == 'yes' ) {
				sendNotify( 'lead_do', $notify );
			}

			addHistorty( [
				"iduser"   => $iduser1,
				"clid"     => $clid,
				"pid"      => $pid,
				"did"      => $did,
				"datum"    => current_datumtime(),
				"des"      => 'Обработан лид #'.$id.':<br>'.$description,
				"tip"      => 'СобытиеCRM',
				"identity" => $identity
			] );

			//сделаем отметку в карточке клиента
			$db -> query( "UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", ["last_hist" => current_datumtime()] );

			if ( $content != '' ) {

				$hst = [
					"iduser"   => (int)$iduser1,
					"clid"     => $clid,
					"pid"      => $pid,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => $content,
					"tip"      => $tiphist,
					"identity" => $identity
				];

				$hst = $hooks -> apply_filters( "history_addfilter", $hst );

				$cid = addHistorty( $hst );

				$hst['cid'] = $cid;

				if ( $hooks ) {
					$hooks -> do_action( "history_add", $params, $hst );
				}

			}

			//отправим уведомление клиенту
			if ( $settings['leadSendClientNotify'] == 'yes' ) {

				$this -> sendNotifyClient( [
					"id"   => $id,
					"clid" => $clid,
					"pid"  => $pid,
					"did"  => $did
				] );

			}

		}

		//если мы закрываем заявку или назначаем ответственного
		else {

			$db -> query( "UPDATE {$sqlname}leads SET ?u WHERE id = '$id' and identity = '$identity'", [
				"iduser"   => (int)$iduser1,
				"datum_do" => current_datumtime(),
				"rezult"   => $rezult,
				"status"   => '3',
				"rezz"     => $rezz
			] );

		}


		/**
		 * Обновляем внешний идентификатор
		 */
		$uids = $db -> getAll( "SELECT * FROM {$sqlname}uids WHERE lid = '$id' AND identity = '$identity'" );
		foreach ( $uids as $uid ) {

			$arg = [
				"clid" => $clid,
				"did"  => $did
			];
			$this -> editUID( $uid['name'], $uid['value'], $arg );

		}

		//Добавим напоминание
		$todo = $params['todo'];
		if ( $todo['theme'] != '' ) {

			$task['title']    = $todo['theme'];
			$task['tip']      = $todo['tip'];
			$task['des']      = untag( $todo['des'] );
			$task['datum']    = $todo['datum'];
			$task['totime']   = $todo['totime'];
			$task['priority'] = untag( $todo['priority'] );
			$task['speed']    = untag( $todo['speed'] );
			$task['active']   = 'yes';
			$task['alert']    = $todo['alert'];
			$task['readonly'] = $todo['readonly'];
			$task['day']      = $todo['day'];
			$task['clid']     = $clid;
			$task['pid']      = $pid;
			$task['did']      = $did;
			$task['autor']    = $iduser1;

			if ( isset( $todo['datumtime'] ) ) {

				$todo['datumtime'] = str_replace( [
					"T",
					"Z"
				], [
					" ",
					""
				], $todo['datumtime'] );

				$task['datum']  = datetime2date( (string)$todo['datumtime'] );
				$task['totime'] = getTime( (string)$todo['datumtime'] );

			}

			$iduser = ($todo['iduser'] != '') ? $todo['iduser'] : $iduser1;

			if ( $hooks ) {
				$task = $hooks -> apply_filters( "task_addfilter", $task );
			}

			$newTask = new Todo();
			$newTask -> add( (int)$iduser, $task );

			if ( $hooks ) {
				$hooks -> do_action( "task_add", $todo, $task );
			}

		}

		$args = [
			"id"          => $id,
			"autor"       => $iduser1,
			"clid"        => $clid,
			"pid"         => $pid,
			"did"         => $did,
			"coordinator" => $settings['leadСoordinator']
		];

		echo str_pad( '', 1024 );
		@ob_flush();
		flush();


		/**
		 * Уведомления
		 */
		Notify ::fire( "lead.do", $iduser1, ["id" => $id] );

		event ::fire( 'lead.do', $args );

		//if ($clientAdd == 'yes') event ::fire('client.add', $args);
		//if ($personAdd == 'yes') event ::fire('person.add', $args);
		//if ($dealAdd == 'yes') event ::fire('deal.add', $args);

		return [
			"id"     => $id,
			"result" => "Сделано",
			"error"  => implode( ", ", (array)$errors ),
			"did"    => $did,
			"clid"   => $clid
		];

	}

	/**
	 * Импорт записей из файла
	 *
	 * @param array $params - доп.параметры
	 *                      - asarray = true - выводит только обработанный массив ( иначе добавляет заявки в базу )
	 *
	 * @return array
	 * @throws Exception
	 */
	public function import(array $params = []): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;
		//$fpath    = ($isCloud) ? $identity."/" : "";

		$settings = $this -> settings( $identity );

		unset( $params['action'] );

		$iduser = $params['iduser'];

		$leads = $err = [];
		$plus  = 0;

		//порядок полей
		$fields = [
			"title",
			"email",
			"phone",
			"site",
			"city",
			"company",
			"description",
			"clientpath",
			"partner"
		];

		//если загружается файл
		if ( filesize( $_FILES['file']['tmp_name'] ) > 0 ) {

			//разбираем запрос из файла
			$ftitle = basename( $_FILES['file']['name'] );

			//переименуем файл
			$fname = time().".".getExtention( $ftitle );

			$maxupload = str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) );

			$uploaddir      = $rootpath.'/files/'.$fpath;
			$uploadfile     = $uploaddir.$fname;
			$file_ext_allow = ['xls'];
			$cur_ext        = texttosmall( getExtention( $ftitle ) );

			//проверим тип файла на поддерживаемые типы
			if ( in_array( $cur_ext, $file_ext_allow ) ) {

				if ( (filesize( $_FILES['file']['tmp_name'] ) / 1000000) > $maxupload ) {
					$err[] = 'Ошибка при загрузке файла: Превышает допустимые размеры!';
				}
				elseif ( move_uploaded_file( $_FILES['file']['tmp_name'], $uploadfile ) ) {

					//обрабатываем данные из файла
					$data = new Spreadsheet_Excel_Reader();
					$data -> setOutputEncoding( 'UTF-8' );
					$data -> read( $uploadfile, false );
					$lead = $data -> dumptoarray();//получили двумерный массив с данными

					$k = 0;

					foreach ( $lead as $l ) {

						if ( $l[1] != '' ) {

							$g = 0;

							foreach ( $l as $li ) {

								$leads[ $k ][ strtr( $g, $fields ) ] = $li;
								$g++;

							}

							$k++;

						}

					}

					//конец загрузки спеки из поля
					unlink( $uploadfile );

				}
				else {
					$err[] = 'Ошибка при загрузке файла: '.$_FILES['file']['error'];
				}

			}
			else {
				$err[] = 'Ошибка при загрузке файла: Файлы такого типа не разрешено загружать.';
			}

			if ( !$params['asarray'] ) {

				if ( !empty( $leads ) ) {

					foreach ( $leads as $lead ) {

						//найдем ущетвующий контакт в базе
						if ( $lead['email'] != '' ) {
							$q = "SELECT * FROM {$sqlname}personcat WHERE mail LIKE '%".$lead['email']."%' and identity = '$identity'";
						}

						elseif ( $lead['phone'] != '' ) {
							$q = "SELECT * FROM {$sqlname}personcat WHERE (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $lead['phone'] )."%') or (replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $lead['phone'] )."%') and identity = '$identity'";
						}

						$pid = $clid = $iduserr = 0;

						if ( isset( $q ) ) {

							$result  = $db -> getRow( $q );
							$pid     = (int)$result['pid'];
							$clid    = (int)$result['clid'];
							$iduserr = (int)$result['iduser'];

						}

						//если контакт не найден поищем в компаниях
						if ( $clid < 1 && $pid < 1 ) {

							if ( $lead['email'] != '' ) {
								$result = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE mail_url LIKE '%".$lead['email']."%' and identity = '$identity'" );
							}

							elseif ( $lead['phone'] != '' ) {
								$result = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $lead['phone'] )."%') or (replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $lead['phone'] )."%') and identity = '$identity'" );
							}

							if ( isset( $result ) ) {

								$clid    = (int)$result['clid'];
								$iduserr = (int)$result['iduser'];

							}

						}

						if ( $iduserr > 0 ) {

							if ( $iduser == 0 ) {

								$user = $iduserr;

							}
							else {

								$user = $iduser;

							}
							$status = 1;

						}
						else {

							$user   = 0;
							$status = 0;

						}

						$da = [
							"datum"       => current_datumtime(),
							"iduser"      => $user,
							"clid"        => $clid,
							"pid"         => $pid,
							"status"      => $status,
							"title"       => untag( $lead["title"] ),
							"email"       => untag( $lead["email"] ),
							"phone"       => untag( $lead["phone"] ),
							"site"        => untag( $lead["site"] ),
							"city"        => untag( $lead["city"] ),
							"company"     => untag( $lead["company"] ),
							"description" => untag( $lead["description"] ),
							"clientpath"  => self ::getPath( $lead["clientpath"] ),
							"partner"     => get_partnerbysite( $lead["partner"] ),
							"identity"    => $identity
						];

						$db -> query( "INSERT INTO {$sqlname}leads SET ?u", $da );
						$id = $db -> insertId();

						$plus++;

						//отправим уведомление куратору
						$notify['notice'] = 'no';
						$notify['id']     = $id;

						if ( $user > 0 && $settings['leadSendOperatorNotify'] == 'yes' ) {
							sendNotify( 'lead_setuser', $notify );
						}

					}

				}

			}

		}

		$err[] = 'Всего обработано: <b>'.count( $leads ).'</b> записей.<br>Добавлено: <b>'.$plus.' записей</b>';

		return !$params['asarray'] ? [
			'result' => "Сделано",
			"error"  => $err
		] : $leads;

	}

	/**
	 * Получает id источника по Имени или Метке
	 *
	 * @param string      $path
	 * @param string|null $source
	 *
	 * @return mixed
	 */
	public static function getPath(string $path = '', string $source = NULL) {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ( $source != '' ) {
			$clientpath = $db -> getOne( "SELECT id FROM {$sqlname}clientpath WHERE utm_source = '$source' AND identity = '$identity'" );
		}
		else {
			$clientpath = $db -> getOne( "SELECT id FROM {$sqlname}clientpath WHERE name = '$path' AND identity = '$identity'" );
		}

		if ( $clientpath < 1 ) {

			$data = [
				"name"       => $path,
				"utm_source" => $source,
				"identity"   => $identity
			];

			if ( empty( $data['name'] ) ) {
				unset( $data['name'] );
			}
			if ( empty( $data['utm_source'] ) ) {
				unset( $data['utm_source'] );
			}

			$db -> query( "INSERT INTO {$sqlname}clientpath SET ?u", $data );
			$clientpath = $db -> insertId();

		}

		return $clientpath;

	}

	/**
	 * Получение ответственного за заявку по алгоритму
	 *
	 * @param array $params - параметры
	 *                      - str **phone**
	 *                      - str **email**
	 *                      - int **iduser**
	 *
	 * @return array
	 */
	public function getUser(array $params = []) {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$settings = $this -> settings( $identity );
		$users    = $settings['leadOperator'];

		$client           = $this -> getClient( [
			"phone"    => $params['phone'],
			"email"    => $params['email'],
			"identity" => $identity
		] );
		$params['iduser'] = $client['iduser'];

		//поищем дублирующие заявки, уже распределенные на пользователей
		$leadUserExist = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}leads WHERE (email LIKE '%".$params['email']."%' or replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%') and identity = '$identity' LIMIT 1" );

		//print "leadUserExist: ".$leadUserExist."\n";

		if ( $leadUserExist > 0 ) {

			//проверим пользователя - активный он или нет
			$secrty = $db -> getOne( "SELECT secrty FROM {$sqlname}user WHERE iduser = '$leadUserExist'" );

			//print "secrty: ".$secrty."\n";

			//если пользователь уже уволен, то ставим 0 - типа не знаем его
			$leadUserExist = ($secrty == 'yes') ? $leadUserExist : 0;

		}

		//print "leadUserExist: ".$leadUserExist."\n";

		//print "=======================";

		if ( $leadUserExist > 0 && (int)$params['iduser'] == 0 ) {
			$params['iduser'] = $leadUserExist;
		}

		//отрабатываем алгоритм назначения заявок
		switch ($settings['leadMethod']) {

			//Через координатора (только неизвестные)
			case "unknown":

				if ( (int)$params['iduser'] == 0 ) {
					$params['iduser'] = 0;
				}

			break;

			//Рулетка
			case "randome":

				if ( (int)$params['iduser'] == 0 ) {
					$params['iduser'] = (int)$users[ array_rand( array_values( $users ), 1 ) ];
				}

			break;

			//Сбободная касса
			case "free":

				//if ($params['iduser'] == 0)
				$params['iduser'] = 0;

			break;

			//Равномерно
			case "equal":

				if ( $params['iduser'] == 0 ) {

					//посчитаем количество заявок у каждого сотрудника за месяц и найдем того, у кого их меньше
					$ucount = [];
					foreach ( $users as $item ) {

						$ucount[ $item ] = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}leads WHERE iduser = '$item' and identity = '$identity'" );

					}

					//сортировка массива от меньшего к большему, чтобы узнать пользователя с наименьшим числом заявок за месяц
					asort( $ucount, SORT_NUMERIC );
					//ставим указатель на первый элемент
					$ucount = reset( $ucount );

					//выбор ключа первого элемента
					$params['iduser'] = key( $ucount );

				}

			break;

			//Эффективно
			case "effective":

			break;

			//Через координатора (все)
			default:

				$params['iduser'] = 0;

			break;

		}

		return $params['iduser'];

	}

	/**
	 * Проверяет почту с заданного ящика и возвращает список писем
	 *
	 * @param array $params - параметры SMTP-сервера + доп.параметры
	 *                      - int **id** - id настроек из БД, если задано, то 6 параметров smtp-сервера не нужны, или
	 *                      - str **name** - Названеи почтового ящика, если задано то параметры smtp-сервера также не
	 *                      нужны
	 *
	 *      - str **smtp_host** - хост ( imap.yandex.ru )
	 *      - str **smtp_port** - порт (25, 465 или 587)
	 *      - str **smtp_protocol** - протокол (IMAP, POP3)
	 *      - str **smtp_secure** - авторизация ( SSL )
	 *      - str **smtp_user** - email
	 *      - str **smtp_pass** - пароль
	 *      - str **deletemess** - удалять сообщения с сервера ( true|false )
	 *      - str **divider** - разделитель для поиска пар "ключ : значение" ( : )
	 *      - str **filter** - фильтр по темам письма ( Заявка )
	 *      - int **days** - количество дней проверки ( 1 )
	 *
	 *      - bool **process** = false - добавить заявки в базу
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getMessages(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;
		$opts     = $this -> opts;

		$msg = $messages = $mailnote = $ignored = [];

		$process = $params['process'];
		$days    = $params['days'];

		//print_r($params);

		if ( isset( $params['id'] ) ) {

			$params   = self :: boxSettings( $params['id'], NULL, $params );
			$identity = $params['identity'];
			$days     = $params['days'];

			//print_r($params);

		}
		elseif ( isset( $params['name'] ) ) {

			$params   = self :: boxSettings( 0, $params['name'], $params );
			$identity = $params['identity'];
			$days     = $params['days'];

		}

		//print_r($params);

		// игнорируемые uid уже загруженных писем
		$ignoreuids = $db -> getCol( "SELECT muid FROM {$sqlname}leads WHERE muid IS NOT NULL AND DATEDIFF(datum, NOW() - INTERVAL 10 DAY) > 0 AND identity = '$identity'" );

		//print_r($ignoreuids);

		// получаем письма с помощью Почтовика
		$mail         = new Mailer();
		$mail -> boxSettings  = $params;
		$mail -> box  = 'INBOX';
		$mail -> days = ($days > 0) ? $days : 3;

		// параметры подключения + доп.параметры
		$mail -> params = [
			"smtp"              => $params,
			"deletemess"        => $params['deletemess'] != 'false',
			"ignoreattachments" => true,
			"ignoreExist"       => false,
			"ignoreuids"        => $ignoreuids
		];
		$mail -> mailGet();
		$emessages = $mail -> Messages;
		$errors    = $mail -> Error;

		//print_r($errors);

		// обработка сообщений
		foreach ( $emessages as $k => $message ) {

			unset( $db );
			$db = new SafeMySQL( $opts );

			if ( !in_array( $message['messageid'], $ignoreuids ) ) {

				// парсим тело сообщения
				$source = html2data( $message['html'] );

				$message['html'] = html2text( getHtmlBody( str_replace( "'", "\"", $message['html'] ) ) );

				$msg['muid'] = $message['messageid'];

				$msg['phone']       = prepareMobPhone( $source['phone'] );
				$msg['email']       = $source['email'];
				$msg['site']        = $source['site'];
				$msg['description'] = $source['description'];

				//парсим текст в поисках полей ИМЯ, КОМПАНИЯ, СТРАНА, ГОРОД, IP + UTM-метки
				$param = self ::parseText( $message['html'], $params['divider'] );

				//print_r($params);

				$msg['title']        = $param['title'];
				$msg['city']         = $param['city'];
				$msg['country']      = $param['country'];
				$msg['ip']           = $param['ip'];
				$msg['company']      = $param['company'];
				$msg['utm_source']   = $param['utm_source'];
				$msg['utm_medium']   = $param['utm_medium'];
				$msg['utm_campaign'] = $param['utm_campaign'];
				$msg['utm_term']     = $param['utm_term'];
				$msg['utm_content']  = $param['utm_content'];
				$msg['utm_referrer'] = $param['utm_referrer'];

				if ( $msg['title'] == '' ) {
					$msg['title'] = $message['fromName'];
				}

				$so = [];

				//обрубаем номер до 11 знаков
				$msg['phone'] = (strlen( $msg['phone'] ) > 12) ? substr( $msg['phone'], 0, 11 ) : $msg['phone'];

				//для поиска по базе удаляем первый знак
				$fone = substr( $msg['phone'], 1 );

				// подзапрос по email
				if ( $msg['email'] != '' ) {

					$str = [];
					$em  = yexplode( ",", (string)$msg['email'] );
					foreach ( $em as $emi ) {
						$str[] = "mail LIKE '%$emi%'";
					}

					$so[] = "(".yimplode( " OR ", (array)$str ).")";

				}

				// подзапрос по телефону
				if ( $fone != '' ) {
					$so[] = "(replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$fone."%' OR replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$fone."%' OR replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$fone."%')";
				}

				// сводим подзапрос
				$sort = yimplode( " OR ", (array)$so );

				// получаем отправителей
				if ( $sort != '' ) {

					unset( $db );
					$db = new SafeMySQL( $GLOBALS['opts'] );

					//ищем Клиента/Контакт
					$rt     = $db -> getRow( "SELECT pid, clid FROM {$sqlname}personcat WHERE ($sort) and identity = '$identity'" );
					$pid    = (int)$rt['pid'];
					$clid   = (int)$rt['clid'];
					$iduser = (int)$rt['iduser'];

					if ( $clid < 1 && $pid < 1 ) {

						$so = [];

						if ( $msg['email'] != '' ) {
							$so[] = "mail_url LIKE '%".$msg['email']."%'";
						}

						if ( $fone != '' ) {
							$so[] = "(replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$fone."%' OR replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$fone."%')";
						}

						$sort = yimplode( " OR ", (array)$so );

						$rt     = $db -> getRow( "SELECT clid FROM {$sqlname}clientcat WHERE ($sort) and identity = '$identity'" );
						$clid   = (int)$rt['clid'];
						$iduser = (int)$rt['iduser'];

					}

					$msg['pid']    = $pid;
					$msg['clid']   = $clid;
					$msg['iduser'] = $iduser;

				}

				$msg['datum'] = $message['datum'];

				/**
				 * Посмотрим внешние id и если есть, то добавим в базу
				 */
				$uids = self ::parseUID( $message['html'], $params['divider'] );
				if ( !empty( $uids ) ) {
					$msg['uids'] = $uids;
				}

				//Если фильтр по теме активен, то фильтруем
				if ( $params['filter'] != '' ) {
					$isFilter = (stripos( texttosmall( $message['theme'] ), $params['filter'] ) !== false) ? 'yes' : 'no';
				}

				else {
					$isFilter = 'yes';
				}

				if ( $isFilter == 'yes' ) {

					$messages[] = $msg;

					if ( $process ) {

						$namelead = $params['name'] ?? $db -> getOne( "SELECT name FROM {$sqlname}smtp WHERE smtp_from = '$params[smtp_user]'" );

						$namelead = ($msg['utm_campaign'] != '') ? $msg['utm_campaign'] : $namelead;

						$msg['clientpath'] = self ::getPath( $namelead, $msg['utm_source'] ) + 0;
						$msg['datum']      = date( "Y-m-d H:i:s", strtotime( $msg['datum'] ) );
						$msg['partner']    = (int)get_partnerbysite( untag( $msg['partner'] ) );

						//получаем пользователя по алгоритму
						$msg['iduser'] = $this -> getUser( [
							"email" => $msg['email'],
							"phone" => $msg['phone']
						] );
						$msg['status'] = ($msg['iduser'] == 0) ? 0 : 1;

						$mailnote[] = $this -> edit( $msg );

					}

				}

			}

			else {

				$ignored[] = $message;
				unset( $emessages[ $k ] );

			}


		}

		//endBox:

		if ( !empty( $ignored ) ) {
			$this -> ignored = $ignored;
		}

		$this -> note  = $mailnote;
		$this -> error = $errors;

		return $messages;

	}

	/**
	 * Отправка уведомления клиенту
	 *
	 * @param array $params - параметры
	 *                      - int **id** - id заявки
	 *                      - str **type** - null|wellcome
	 *                      - int **iduser** - пользователь
	 *                      - int **clid**
	 *                      - int **pid**
	 *                      - int **did**
	 *
	 * @return string
	 * @throws Exception
	 */
	public function sendNotifyClient(array $params = []): string {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$rez = '';

		$tags = [];

		$lead = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '$params[id]' and identity = '$identity'" );

		$html = ($params['type'] != 'wellcome') ? htmlspecialchars_decode( $db -> getOne( "SELECT content FROM {$sqlname}tpl WHERE tip = 'leadClientNotifyTemp' and identity = '$identity'" ) ) : htmlspecialchars_decode( $db -> getOne( "SELECT content FROM {$sqlname}tpl WHERE tip = 'leadSendWellcomeTemp' and identity = '$identity'" ) );

		if ( $html != '' && $lead['email'] != '' && $params['iduser'] > 0 ) {

			$user = $db -> getRow( "SELECT iduser, title, email, phone, mob FROM {$sqlname}user WHERE iduser='$params[iduser]'" );

			if ( $params['did'] > 0 || $params['clid'] > 0 || $params['pid'] > 0 ) {
				$tags = getSmartTag( (int)$params['did'], (int)$params['clid'], (int)$params['pid'] );
			}

			if ( $params['did'] > 0 ) {
				$tags['dogID'] = $params['did'];
			}

			if ( $params['clid'] > 0 ) {
				$tags['clientID'] = $params['clid'];
			}

			if ( $params['pid'] > 0 ) {
				$tags['personID'] = $params['pid'];
			}

			/*
			отключено, т.к. часто назначает ответственным текущего сотрудника
			$tags['castomerName'] = ($tags['castomerName'] != '') ? $tags['castomerName'] : $lead['title'];
			$tags['UserName']     = ($tags['UserName'] != '') ? $tags['UserName'] : $user['title'];
			$tags['UserPhone']    = ($tags['UserPhone'] != '') ? $tags['UserPhone'] : $user['phone'];
			$tags['UserMob']      = ($tags['UserMob'] != '') ? $tags['UserMob'] : $user['mob'];
			$tags['UserEmail']    = ($tags['UserEmail'] != '') ? $tags['UserEmail'] : $user['email'];
			*/

			//print_r($tags);
			//print_r($user);

			$tags['compName']     = $tags['compShotName'];
			$tags['castomerName'] = $lead['title'];
			$tags['UserName']     = $user['title'];
			$tags['UserPhone']    = $user['phone'];
			$tags['UserMob']      = $user['mob'];
			$tags['UserEmail']    = $user['email'];

			foreach ( $tags as $tag => $val ) {

				$html = str_replace( "{".$tag."}", $val, $html );

			}

			$param['subject']  = ($tags['compName'] != '') ? "Ваша заявка принята в работу [".$tags['compName']."]" : $db -> getOne( "SELECT company FROM {$sqlname}settings WHERE id = '$identity'" );
			$param['html']     = $html;
			$param['priority'] = '3';
			$param['iduser']   = ($tags['iduser'] > 0) ? $tags['iduser'] : $user['iduser'];
			$param['from']     = $user['email'];
			$param['fromname'] = $user['title'];
			$param['to']       = $lead['email'];
			$param['toname']   = $lead['title'];

			//print_r($param);

			$rez = mailto( [
				$param['to'],
				$param['toname'],
				$param['from'],
				$param['fromname'],
				$param['subject'],
				$param['html']
			] );

		}

		return $rez;

	}

	/**
	 * Вспомогательная функция Парсит текст на составляющие
	 *
	 * @param string|null $text
	 * @param string      $divider
	 *
	 * @return array - массив значений
	 *      - field => value ( Например: email => m.3000@mail.io )
	 */
	public static function parseText(string $text = NULL, string $divider = ':'): array {

		$text = strip_tags( str_replace( "</li>", "\n", $text ) );

		//массив сопоставлений
		$pole = [
			"title"              => "title",
			"name"               => "title",
			"фио"                => "title",
			"ф.и.о."             => "title",
			"имя"                => "title",
			"email"              => "email",
			"e-mail"             => "email",
			"электронная почта"  => "email",
			"почта"              => "email",
			"phone"              => "phone",
			"callerphone"        => "phone",
			"телефон"            => "phone",
			"тел"                => "phone",
			"мобильный"          => "phone",
			"сотовый"            => "phone",
			"company"            => "company",
			"компания"           => "company",
			"организация"        => "company",
			"клиент"             => "company",
			"фирма"              => "company",
			"site"               => "site",
			"сайт"               => "site",
			"интернет"           => "site",
			"city"               => "city",
			"город"              => "city",
			"адрес"              => "city",
			"country"            => "country",
			"страна"             => "country",
			"description"        => "description",
			"описание"           => "description",
			"сообщение"          => "description",
			"текст"              => "description",
			"partner"            => "partner",
			"партнер"            => "partner",
			"referal"            => "partner",
			"источник"           => "utm_source",
			"from"               => "utm_source",
			"utm_source"         => "utm_source",
			"рекламная система"  => "utm_source",
			"utm_medium"         => "utm_medium",
			"тип трафика"        => "utm_medium",
			"utm_campaign"       => "utm_campaign",
			"рекламная кампания" => "utm_campaign",
			"utm_term"           => "utm_term",
			"ключевое слово"     => "utm_term",
			"utm_content"        => "utm_content",
			"тип объявления"     => "utm_content",
		];

		$arrs = yexplode( "\n", (string)$text );

		foreach ( $arrs as $arr ) {

			$re = (trim( $arr ) != '') ? explode( $divider, trim( $arr ) ) : '';

			if ( trim( $re[1] ) != '' ) {
				$rez[ strtr( texttosmall( trim( $re[0] ) ), $pole ) ] = str_replace( "\t", "", trim( $re[1] ) );
			}

		}

		$rez['description'] = $text;

		return $rez;

	}

	/**
	 * Возвращает массив id внешних сервисов
	 *
	 * @param $text
	 * @param $divider
	 *
	 * @return array - массив uid
	 *      - uid => value ( Например: roistat_id => 232SFSDF456RTG )
	 */
	public static function parseUID($text, $divider): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$rez = [];

		$uids = json_decode( $db -> getOne( "SELECT params FROM {$sqlname}customsettings WHERE tip = 'uids' AND identity = '$identity'" ), true );

		$text = strip_tags( str_replace( "</li>", "\n", $text ) );

		$arrs = yexplode( "\n", (string)$text );

		foreach ( $arrs as $arr ) {

			$re = (trim( $arr ) != '') ? yexplode( $divider, (string)trim( $arr ) ) : '';

			if ( trim( $re[1] ) != '' && in_array( $re[0], $uids ) ) {
				$rez[ texttosmall( trim( $re[0] ) ) ] = str_replace( "\t", "", trim( $re[1] ) );
			}

		}

		return $rez;

	}

	/**
	 * Манипуляции с внешними id
	 *
	 * @param string $name
	 * @param string $value
	 * @param array  $params - доп.параметры
	 *                       - **lid** - id заявки
	 *                       - **clid** - id клиента
	 *                       - **did** - id сделки
	 *
	 * @return array
	 * good result
	 *         - str **result** = ok
	 *         - int **id** = id
	 *         - str **message** = Комментарий
	 *
	 * error result
	 *         - **result** = error
	 *         - **message** = Не указан Параметр и его Значение
	 */
	public function editUID(string $name = '', string $value = '', array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		if ( $name != '' && $value != '' ) {

			//проверяем наличие записи в базе
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}uids WHERE name = '$name' AND value = '$value' AND identity = '$identity'" ) + 0;

			if ( $id == 0 ) {

				$data = [
					"name"     => $name,
					"value"    => $value,
					"lid"      => (int)$params['lid'],
					"clid"     => (int)$params['clid'],
					"did"      => (int)$params['did'],
					"identity" => $identity
				];

				$db -> query( "INSERT INTO {$sqlname}uids SET ?u", arrayNullClean( $data ) );
				$id = $db -> insertId();

				$message = 'Добавлена запись';

			}
			else {

				$data = [
					"lid"  => (int)$params['lid'],
					"clid" => (int)$params['clid'],
					"did"  => (int)$params['did']
				];
				$db -> query( "UPDATE {$sqlname}uids SET ?u WHERE name = '$name' AND value = '$value' AND identity = '$identity'", arrayNullClean( $data ) );

				$message = 'Обновлена запись';

			}

			$result = [
				"result"  => "ok",
				"id"      => $id,
				"message" => $message
			];

		}
		else {
			$result = [
				"result"  => "error",
				"message" => "Не указан Параметр и его Значение"
			];
		}

		return $result;

	}

	/**
	 * Функция автоматического создания Заявки по номеру телефона
	 *
	 * @param string $phone
	 * @param array  $params - опционально
	 *                       - **title** - Название клиента
	 *                       - **description** - Описание заявки
	 *
	 * @return stdClass
	 *      - **id** - id заявки
	 *      - **error** - ошибки
	 *
	 * @throws Exception
	 */
	public function autoLeadCreate(string $phone, array $params = []): stdClass {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$response          = new stdClass();
		$response -> id    = 0;
		$response -> error = 'The phone number already exist in Leads';

		//обрезаем первый символ
		//т.к. он может быть 7 или 8
		$lphone = substr( $phone, 1 );

		//проверяем наличие телефона в заявках
		$count = (int)$db -> getOne( "SELECT * FROM {$sqlname}leads WHERE phone LIKE '%$lphone%' AND identity = '$identity'" ) + 0;

		//если в заявках номер не найден, то
		if ( $count == 0 ) {

			$params = [
				"phone"       => $phone,
				"title"       => $params['title'] ?? "Неизвестный клиент",
				"description" => $params['description'] ?? "Автоматически создан при входящем звонке"
			];

			$result = $this -> edit( $params );

			$response -> id    = (int)$result['id'];
			$response -> error = $result['error'];

		}

		return $response;

	}

	/**
	 * Загрузка параметров конкретного ящика для сбора заявок
	 *
	 * @param int         $id
	 * @param string|null $name
	 * @param array       $params
	 *
	 * @return array - настройки конкретного почтового ящика
	 *      - int **id** - id записи
	 *      - int **name** - имя ящика
	 *      - str **smtp_host** - хост ( imap.yandex.ru )
	 *      - str **smtp_port** - порт (25, 465 или 587)
	 *      - str **smtp_protocol** - протокол (IMAP, POP3)
	 *      - str **smtp_secure** - авторизация ( SSL )
	 *      - str **smtp_user** - email
	 *      - str **smtp_pass** - пароль
	 *      - str **deletemess** - удалять сообщения с сервера ( true|false )
	 *      - str **divider** - разделитель для поиска пар "ключ : значение" ( : )
	 *      - str **filter** - фильтр по темам письма ( Заявка )
	 */
	public static function boxSettings(int $id = 0, string $name = NULL, array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $params['identity'] ?? $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$skey     = $params['skey'] ?? $GLOBALS['skey'];
		$ivc      = $params['ivc'] ?? $GLOBALS['ivc'];

		/**
		 * Параметры передаем для режима работы в терминале, там они пусты
		 */
		if ( $ivc == '' ) {
			$ivc = $db -> getOne( "SELECT ivc FROM {$sqlname}settings WHERE id = '$identity'" );
		}
		if ( $skey == '' ) {
			$skey = 'vanilla'.(($identity + 7) ** 3).'round'.(($identity + 3) ** 2).'robin';
		}

		$settingsBox = [];

		if ( $id > 0 ) {
			$settingsBox = $db -> getRow( "SELECT * FROM {$sqlname}smtp WHERE id = '$id' and identity = '$identity'" );
		}

		if ( !empty($name) ) {
			$settingsBox = $db -> getRow( "SELECT * FROM {$sqlname}smtp WHERE name = '$name' and identity = '$identity'" );
		}

		//print array2string($settingsBox);

		$keys = array_keys( $settingsBox );

		// очищаем от элементов с цифровым ключем
		foreach ( $keys as $k ) {

			if ( is_numeric( $k ) ) {
				unset( $settingsBox[ $k ] );
			}

		}

		$settingsBox['smtp_user'] = rij_decrypt( $settingsBox["smtp_user"], $skey, $ivc );
		$settingsBox['smtp_pass'] = rij_decrypt( $settingsBox["smtp_pass"], $skey, $ivc );

		return $settingsBox;

	}

	/**
	 * Список всех почтовых ящиков с настройками
	 *
	 * @param array $filters - фильтры для cron-скрипта
	 *                       - str **active** = yes - только активные
	 *                       - int **lastBOX** - начальный id ящика ( вывод начнется со следующего )
	 *                       - int **limit** - ограничение по количеству
	 *
	 * @return array - массив настройек ящика
	 *      - int **id** - в качестве ключа
	 *          - int **id** - id записи
	 *          - int **name** - имя ящика
	 *          - str **smtp_host** - хост ( imap.yandex.ru )
	 *          - str **smtp_port** - порт (25, 465 или 587)
	 *          - str **smtp_protocol** - протокол (IMAP, POP3)
	 *          - str **smtp_secure** - авторизация ( SSL )
	 *          - str **smtp_user** - email
	 *          - str **smtp_pass** - пароль
	 *          - str **deletemess** - удалять сообщения с сервера ( true|false )
	 *          - str **divider** - разделитель для поиска пар "ключ : значение" ( : )
	 *          - str **filter** - фильтр по темам письма ( Заявка )
	 */
	public function boxList(array $filters = []): array {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$boxes = [];
		$sort  = '';

		if ( isset( $filters['lastBOX'] ) ) {
			$sort .= " AND id > '$filters[lastBOX]'";
		}

		if ( isset( $filters['active'] ) ) {
			$sort .= " AND active = '$filters[active]'";
		}

		if ( isset( $filters['limit'] ) ) {
			$sort .= " LIMIT 0, $filters[limit]";
		}

		$r = $db -> query( "SELECT * FROM {$sqlname}smtp WHERE tip = 'lead' $sort" );
		while ($d = $db -> fetch( $r )) {

			$ivc  = $db -> getOne( "SELECT ivc FROM {$sqlname}settings WHERE id = '$d[identity]'" );
			$skey = 'vanilla'.(($d['identity'] + 7) ** 3).'round'.(($d['identity'] + 3) ** 2).'robin';

			/**
			 * Параметры передаем для режима работы в терминале
			 */
			$boxes[ $d['id'] ] = self ::boxSettings( (int)$d['id'], NULL, [
				"ivc"      => $ivc,
				"skey"     => $skey,
				"identity" => (int)$d['identity']
			] );

		}

		return $boxes;

	}

	/**
	 * Поиск клиента, контакта, пользователя по email, phone
	 *
	 * @param array $params - параметры
	 *                      - **email**
	 *                      - **phone**
	 *                      - **identity**
	 *
	 * @return array - массив результатов
	 *      - **clid**
	 *      - **pid**
	 *      - **iduser**
	 */
	private function getClient(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$identity = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db       = $this -> db;

		$q    = '';
		$clid = $pid = $iduser = 0;

		if ( $params['email'] != '' ) {

			$q .= " and mail LIKE '%".$params['email']."%'";

		}
		if ( $params['phone'] != '' ) {

			$q .= " and (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%') or (replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%')";

		}
		if ( $params['email'] != '' && $params['phone'] != '' ) {

			$q .= " and (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%') or (replace(replace(replace(replace(replace(mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%' or mail LIKE '%".$params['email']."%')";

		}

		if ( $q != '' ) {

			$res = $db -> getRow( "SELECT * FROM {$sqlname}personcat WHERE pid > 0 $q and identity = '$identity'" );

			$pid    = (int)$res['pid'];
			$clid   = (int)$res['clid'];
			$iduser = (int)$res['iduser'];

		}

		//если ничего не найдено, то ищем в клиентах
		if ( $clid == 0 && $pid == 0 ) {

			$q = '';

			if ( $params['email'] != '' ) {

				$q .= " and mail_url LIKE '%".$params['email']."%'";

			}
			if ( $params['phone'] != '' ) {

				$q .= " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%') or (replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%')";

			}
			if ( $params['email'] != '' && $params['phone'] != '' ) {

				$q = " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%') or (replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $params['phone'] )."%' or mail_url LIKE '%".$params['email']."%')";

			}

			if ( $q != '' ) {

				$res    = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE clid > 0 $q and identity = '$identity'" );
				$clid   = (int)$res['clid'];
				$iduser = (int)$res['iduser'];

			}

		}

		//проверим пользователя - активный он или нет
		$secrty = $db -> getOne( "SELECT secrty FROM {$sqlname}user WHERE iduser = '$iduser'" );

		//если пользователь уже уволен, то ставим 0 - типа не знаем его
		$iduser = ($secrty == 'yes') ? $iduser : 0;

		return [
			"clid"   => $clid,
			"pid"    => $pid,
			"iduser" => $iduser
		];

	}

	/**
	 * Настройки модуля
	 *
	 * @param int $identity
	 *
	 * @return mixed
	 */
	private function settings(int $identity = 1) {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		$set = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
		//$users        = $leadsettings['leadOperator'];
		//$coordinator  = $leadsettings["leadСoordinator"];

		return json_decode( $set['content'], true );

	}

}