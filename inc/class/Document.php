<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use clsTinyButStrong;
use icircle\Template\Docx\DocxTemplate;
use Exception;
use event;
use RuntimeException;
use SafeMySQL;

/**
 * Класс для управления Документами
 *
 * Class Document
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example
 *
 * ```php
 * $rez  = new Salesman\Document();
 * $data = $rez -> info($id);
 * ```
 */
class Document {

	public $rezult;

	public $tags = [];

	/**
	 * Прочие настройки
	 *
	 * @var array
	 */
	public $otherSettings;

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
	 * Абсолютный путь
	 *
	 * @var false|string
	 */
	private $rootpath;

	/**
	 * Akt constructor.
	 */
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

		$this -> db = new SafeMySQL( $this -> opts );

		//если папка с кэшем шрифтов не сущесвует, то создадим и поместим туда файлы из папки dompdfFontsCastom
		$pathTo   = $rootpath.'/cash/dompdf/';
		$pathFrom = $rootpath.'/vendor/dompdfFontsCastom/';

		if ( !file_exists( $rootpath.'/cash/dompdf/' ) ) {

			if ( !mkdir( $pathTo, 0777 ) && !is_dir( $pathTo ) ) {
				throw new RuntimeException( sprintf( 'Directory "%s" was not created', $pathTo ) );
			}
			chmod( $pathTo, 0777 );

			//копируем файлы из папки /vendor/dompdfFontsCastom/
			clearstatcache();

			$files = scandir( $pathFrom, 1 );
			foreach ( $files as $file ) {

				$f = strpos( $file, 'sql.zip' );

				if ( !in_array( $f, [
					'/',
					'.',
					'..'
				] ) ) {

					copyFile( $pathFrom.$file, $pathTo );
					chmod( $pathTo.$file, 0777 );

				}

			}

		}

		//$other  = $this -> db -> getOne( "SELECT other FROM ".$this -> sqlname."settings where id = '".$this -> identity."'" );
		//$other = explode(";", $other);

		if ( file_exists( $rootpath."/cash/".$this -> fpath."otherSettings.json" ) ) {

			$this -> otherSettings = json_decode( file_get_contents( $rootpath."/cash/".$this -> fpath."otherSettings.json" ), true );

		}
		else {

			$other                 = explode( ";", $this -> db -> getOne( "SELECT other FROM ".$this -> sqlname."settings WHERE id = '".$this -> identity."'" ) );
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
				"aktTempService"       => (!isset( $other[39] ) || $other[39] == 'no') ? 'akt_full.tpl' : $other[39],
				"invoiceTempService"   => (!isset( $other[40] ) || $other[40] == 'no') ? 'invoice.tpl' : $other[40],
				"aktTemp"              => (!isset( $other[41] ) || $other[41] == 'no') ? 'akt_full.tpl' : $other[41],
				"invoiceTemp"          => (!isset( $other[42] ) || $other[42] == 'no') ? 'invoice.tpl' : $other[42],
			];

		}

	}

	/**
	 * Информация по Документу
	 *
	 * @param int $id
	 *
	 * @return array - массив данных
	 *     - **datum** - Дата создания документа
	 *     - **typeDoc** - Тип документа (title из запроса tips)
	 *     - **idtype** - ID типа документа (id из запроса tips)
	 *     - **description** - описание Документа
	 *     - **mcid** - id Компании, от которой ведется сделка
	 *     - **signer** - id подписанта
	 *     - **status** - id статуса документа
	 *     - **statusTitle** - расшифровка статуса
	 *     - **files** - массив файлов, вложенных в документ
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error]
	 *              - [code] = 404
	 *              - [text] = Запись не найдена
	 *         - [error]
	 *              - [code] = 405
	 *              - [text] = Отсутствуют параметры - id документа
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> info($id);
	 * ```
	 */
	public static function info(int $id = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$id = (int)$id;

		if ( (int)$id > 0 ) {

			$response = $db -> getRow( "select * from {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );

			if( !empty($response) ) {

				foreach ( $response as $k => $item ) {
					if ( is_int( $k ) || $k == 'identity' ) {
						unset( $response[ $k ] );
					}
				}


				if ( $response['ftitle'] != '' ) {

					$ftitles = (array)yexplode( ";", (string)$response['ftitle'] );
					$ffiles  = (array)yexplode( ";", (string)$response['fname'] );

					foreach ( $ftitles as $index => $title ) {

						$response['files'][] = [
							"name" => $title,
							"file" => $ffiles[ $index ],
							"type" => texttosmall( getExtention($title ) )
						];

					}

					//unset($response['ftitle']);
					//unset($response['fname']);
					//unset($response['ftype']);

				}

				// не понятно откуда и зачем этот код
				/*
				$result = $db -> query("SELECT * FROM {$sqlname}contract_type where type != '' and identity = '$identity'");
				while ($data = $db -> fetch($result)) {

					if ($data['type'] == 'get_dogovor') $typeDogovor[] = $data['id'];
					if ($data['type'] == 'get_aktper') $typeAktPeriod[] = $data['id'];
					if ($data['type'] == 'get_akt') $typeAkt[] = $data['id'];

				}
				*/

			}
			else{

				$response = [
					'result' => 'Error',
					'error' => [
						'code' => '404',
						'text' => "Документ не найден"
					]
				];

			}

		}
		else {

			$response = [
				'result' => 'Error',
				'error' => [
					'code' => '405',
					'text' => "Отсутствуют параметры - id документа"
				]
			];

		}

		return $response;

	}

	/**
	 * Получение статуса документа
	 *
	 * @param int $deid
	 *
	 * @return int
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> getSatus($id);
	 * ```
	 */
	public static function getSatus(int $deid = 0): int {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$deid = (int)$deid;

		$status = 0;

		if ( $deid > 0 ) {
			$status = $db -> getOne( "SELECT status FROM {$sqlname}contract WHERE deid = '$deid'" );
		}

		return $status;

	}

	/**
	 * Шаблоны документа по его типу
	 *
	 * @param array $ids
	 *
	 * @return array
	 *
	 * ```php
	 * $data  = new Salesman\Document::getTemplates();
	 * ```
	 */
	public static function getTemplates(array $ids = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$template = [];
		$sort     = '';

		if ( !empty( $ids ) ) {
			$sort .= "typeid IN (".yimplode( ",", $ids ).") AND";
		}

		$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_temp WHERE $sort identity = '$identity' ORDER by title" );
		foreach ( $result as $data ) {

			$template[ $data['typeid'] ][] = [
				"id"    => $data['id'],
				"title" => $data['title'],
				"file"  => $data['file'],
			];

		}

		return $template;

	}

	/**
	 * Типы документов
	 *
	 * @param array $ids
	 * @param array $types
	 *
	 * @return array
	 * ```php
	 * [ 2 ] => (
	 *          [ id ] => 2
	 *          [ title ] => Акт приема-передачи
	 *          [ type ] => get_akt
	 *          [ number ] => 0
	 *          [ format ] =>
	 *          [ roles ] => (
	 *                   [ 0 ] => Руководитель организации
	 *                   [ 1 ] => Руководитель с доступом
	 *                   [ 2 ] => Руководитель подразделения
	 *                   [ 3 ] => Руководитель отдела
	 *                   [ 4 ] => Поддержка продаж
	 *          )
	 *          [ users ] => (
	 *                   [ 0 ] => 1
	 *                   [ 1 ] => 23
	 *                   [ 2 ] => 25
	 *          )
	 * )
	 * ```
	 */
	public static function getTypes(array $ids = [], array $types = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$template = [];
		$sort     = '';

		if ( !empty( $ids ) ) {
			$sort .= "id IN (".yimplode( ",", $ids ).") AND";
		}

		if ( !empty( $types ) ) {
			$sort .= "type IN (".yimplode( ",", $types ).") AND";
		}

		$result = $db -> getAll( "SELECT * FROM {$sqlname}contract_type WHERE $sort identity = '$identity' ORDER by title" );
		foreach ( $result as $data ) {

			$template[ $data['id'] ] = [
				"id"     => $data['id'],
				"title"  => $data['title'],
				"type"   => $data['type'],
				"number" => $data['num'],
				"format" => $data['format'],
				"roles"  => (array)yexplode( ",", (string)$data['role'] ),
				"users"  => (array)yexplode( ",", (string)$data['users'] )
			];

		}

		return $template;

	}

	/**
	 * Получение списка всех документов и актов по Клиенту или Сделке
	 *
	 * @param array $params         - параметры
	 *                              - int **clid** - id клиента
	 *                              - int **did** - id сделки
	 *                              - str **filter** - фильтровать по типу
	 *                                  - akt - только акты
	 *                                  - contract - только договоры
	 *                                  - document - документы, кроме договоров
	 *                              - str **sort** - порядок сортировки по дате
	 *                              - **desc** - обратный порядок
	 *
	 * @return array **list**
	 *
	 * ```php
	 * [ list ] => (
	 *      [ 0 ] => (
	 *          [ id ] => 333
	 *          [ deid ] => 333
	 *          [ idtype ] => 47
	 *          [ type ] => document
	 *          [ isDocument ] => 1
	 *          [ number ] => 31
	 *          [ title ] => Дополнительное соглашение X
	 *          [ typeTitle ] => Дополнительное соглашение
	 *          [ datum ] => 2018-07-06 11:19:54
	 *          [ datumFormated ] => 05 Июля 2018
	 *          [ datum_start ] => 2018-07-05
	 *          [ startFormated ] => 05 Июля 2018
	 *          [ datum_end ] => 2018-12-31
	 *          [ endFormated ] => 31 Декабря 2018
	 *          [ color ] => graybg gray2
	 *          [ des ] =>
	 *          [ did ] => 902
	 *          [ clid ] => 1781
	 *          [ pid ] => 0
	 *          [ deal ] => (
	 *              [ title ] => СД4250: Сейлзмен Рус
	 *              [ icon ] => icon-lock red
	 *              [ isClose ] => 1
	 *          )
	 *          [ payer ] => (
	 *              [ id ] => 1781
	 *              [ name ] => Сейлзмен Рус
	 *          )
	 *          [ isAccess ] => yes
	 *          [ files ] => (
	 *              [ 0 ] => (
	 *                  [ name ] => Дополнительное соглашение №31 от 06.07.2018.docx
	 *                  [ file ] => 1530859514.docx
	 *                  [ icon ] =>
	 *                  [ size ] => 388232
	 *                  [ sizeKb ] => 388,23
	 *                  [ view ] =>
	 *                  [ topdf ] => 1
	 *              )
	 *          )
	 *          [ haveFiles ] => 1
	 *          [ status ] => (
	 *          [ id ] =>
	 *              [ title ] => Создан
	 *              [ color ] => #9edae5
	 *              [ statuslog ] =>
	 *          )
	 *          [ haveStatus ] => 1
	 *          [ canBeDeleted ] => 1
	 *      )
	 * )
	 * [ did ] => 902
	 * [ clid ] => 1781
	 * ```
	 *
	 * Example:
	 *
	 * ```php
	 * $doc = Salesman\Document ::getDocuments( [
	 *  "did"    => 902,
	 *  "filter" => "document"
	 * ] );
	 * ```
	 */
	public static function getDocuments(array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		global $userSettings;

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$fpath    = $GLOBALS['fpath'];
		$isadmin  = $GLOBALS['isadmin'];

		$clid    = $params['did'] > 0 ? 0 : (int)$params['clid'];
		$did     = (int)$params['did'];
		$docSort = untag($params['sort']);
		$filter  = $params['filter'];
		$payer   = 0;

		$showAkts = $showContract = $showDocs = true;

		$xfilter = get_people( (int)$userSettings['filterAllBy'], "yes" );

		$mycompanyes = Guides::myComps();

		if ( isset( $filter ) ) {

			if ( $filter == 'akt' ) {

				$showContract = false;
				$showDocs     = false;

			}
			elseif ( $filter == 'contract' ) {

				$showAkts = false;
				$showDocs = false;

			}
			elseif ( $filter == 'document' ) {

				$showContract = false;
				$showAkts     = false;

			}

		}

		$list = [];

		$typeAkt       = [];
		$typeAktPeriod = [];
		$typeDogovor   = [];

		$typeList = [];

		// нужен для сортировки
		// на тот случай, если дата и время разных документов совпадают
		$i = 0;

		//типы документов
		$result = $db -> query( "SELECT * FROM {$sqlname}contract_type WHERE identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			if ( $data['type'] == 'get_dogovor' ) {
				$typeDogovor[] = $data['id'];
			}

			if ( $data['type'] == 'get_aktper' ) {
				$typeAktPeriod[] = $data['id'];
			}

			if ( $data['type'] == 'get_akt' ) {
				$typeAkt[] = $data['id'];
			}

			$typeList[ $data['id'] ] = $data['title'];

		}

		$aktTypes = array_merge( $typeAkt, $typeAktPeriod );

		//составим запрос для вывода документов для текущей записи
		if ( $did > 0 ) {

			$resultt = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );
			$clid    = $resultt["clid"];
			$payer   = $resultt["payer"];

		}

		//Проверим реквизит для счета, если включено выставление счетов
		//if ( !isset( $payer ) )
			//$payer = $clid;

		/**
		 * Список актов
		 */

		if ( !empty( $aktTypes ) && $showAkts ) {

			$indexes = [];

			$result = $db -> query( "SELECT * from {$sqlname}contract WHERE (".($did < 1 ? "(clid = '$clid' or payer = '$clid')" : "did = '$did'").") and idtype IN (".yimplode( ",", $aktTypes ).") and identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				$isper = (bool)isServices( (int)$data['did'] );

				$status = $db -> getRow( "SELECT color, title FROM {$sqlname}contract_status WHERE id = '$data[status]' and identity = '$identity'" );

				//статусы, применимые к текущему типу документоа
				$statuses = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE FIND_IN_SET('$data[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord" );

				$statuslog = [];
				if ( !empty( $statuses ) ) {

					$re = $db -> getAll( "
						SELECT 
							DATE_FORMAT({$sqlname}contract_statuslog.datum, '%d.%m.%Y %H:%s') as datum,
							{$sqlname}contract_statuslog.des as des,
							{$sqlname}contract_status.title as status,
							{$sqlname}contract_status.color as color
						FROM {$sqlname}contract_statuslog 
							LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = {$sqlname}contract_statuslog.status
						WHERE 
							{$sqlname}contract_statuslog.deid = '$data[deid]' and 
							{$sqlname}contract_statuslog.identity = '$identity' 
						ORDER BY {$sqlname}contract_statuslog.datum DESC
					" );

					foreach ( $re as $stat ) {

						$statuslog[] = [
							"datum"  => $stat['datum'],
							"color"  => $stat['color'],
							"status" => $stat['status'],
							"des"    => $stat['des'],
						];

					}

				}

				$ainvoice = ($isper) ? $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid = '$data[crid]' and identity = '$identity'" ) : NULL;

				$template = Akt ::getTemplates( NULL, $data['title'] );

				$aktComplect = Akt ::getComplect( $data['deid'] );

				$close = $data['did'] > 0 ? getDogData( $data['did'], "close" ) : NULL;

				$index = (diffDateTime2( $data['datum'], '', false ) + $i);

				if( array_key_exists($index, $list) ){
					$index += 10;
				}

				$xclid = (int)$data['clid'];
				$xdid  = (int)$data['did'];

				if($xdid > 0){
					$xclid = 0;
				}

				$haveAccesse = get_accesse( $xclid, 0, $xdid ) == "yes";

				if( $xclid > 0 && $userSettings['filterAllByClientEdit'] == 'yes' && in_array($iduser1, $xfilter) ){
					$haveAccesse = true;
				}
				if( $xdid > 0 && $userSettings['filterAllByDealEdit'] == 'yes' && in_array($iduser1, $xfilter) ){
					$haveAccesse = true;
				}

				$list[ $index ] = [
					"id"                   => $data['deid'],
					"deid"                 => $data['deid'],
					"type"                 => "akt",
					"isAkt"                => true,
					"idtype"               => $data['idtype'],
					"datum"                => $data['datum'],
					"datumFormated"        => format_date_rus_name( get_smdate( $data['datum'] ) ),
					"title"                => "Акт ".(!$isper ? 'приёма-передачи' : '(Ежемесячный)'),
					"number"               => $data['number'],
					"template"             => !$isper ? $template['title'] : NULL,
					"complect"             => !$isper ? round( $aktComplect + 0.1, 0 ) : NULL,
					"did"                  => $data['did'],
					"clid"                 => $data['clid'],
					"pid"                  => $data['pid'],
					"mcid"                 => $data['mcid'],
					"company"              => $mycompanyes[$data['mcid']],
					"signer"               => (int)$data['signer'] > 0 ? getSigner((int)$data['signer']) : NULL,
					"isAccess"             => $haveAccesse ? true : NULL,
					"status"               => [
						"id"        => $status['id'],
						"title"     => $status['title'],
						"color"     => $status['color'],
						"statuslog" => !empty( $statuslog ) ? $statuslog : NULL
					],
					"haveStatus"           => !empty( $status ) ? true : NULL,
					"deal"                 => $data['did'] > 0 ? [
						"title"   => getDogData( $data['did'], 'title' ),
						"icon"    => $close == 'yes' ? "icon-lock red" : "icon-briefcase blue",
						"isClose" => $close == 'yes' ? true : NULL
					] : NULL,
					"isPeriodic"           => $isper,
					"invoice"              => $isper ? $ainvoice['invoice'] : NULL,
					"invoiceDatum"         => $isper ? $ainvoice['datum'] : NULL,
					"invoiceDatumFormated" => $isper ? format_date_rus_name( get_smdate( $ainvoice['datum'] ) ) : NULL,
					"canBeDeleted"         => $isadmin == 'on' || $close != "yes" ? true : NULL
				];

				$i++;

			}

		}

		/**
		 * Список документов ( в т.ч. Договоров )
		 */

		if ( $showContract || $showDocs ) {

			$dd = [];
			$sort = '';
			if ( $did > 0 ) {
				$sort .= "did = '$did' AND";
			}

			if ( $payer > 0 && $did == 0 ) {
				$dd[] = "payer = '$payer'";
			}

			if ( $clid > 0 && $did == 0 ) {
				$dd[] = "clid = '$clid'";
			}

			$result = $db -> query( "
			SELECT * 
			FROM {$sqlname}contract 
			WHERE 
				-- clid = '$clid' AND
			 	$sort
				".(!empty( $dd ) ? "(".yimplode( " OR ", $dd ).") AND " : "")."
				".(!empty( $aktTypes ) ? "idtype NOT IN (".yimplode( ",", $aktTypes ).") AND" : "")."
				identity = '$identity'" );

			//print $db -> lastQuery();

			while ($da = $db -> fetch( $result )) {

				$files      = [];
				$isContract = in_array( $da['idtype'], $typeDogovor );

				if ( ($isContract && $showContract) || (!$isContract && $showDocs) ) {

					//статусы, применимые к текущему типу документоа
					$statuses = $db -> getAll( "SELECT * FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord" );

					$status = $db -> getRow( "SELECT color, title FROM {$sqlname}contract_status WHERE id = '$da[status]' and identity = '$identity'" );

					$statuslog = [];
					if ( !empty( $statuses ) ) {

						$re = $db -> getAll( "
						SELECT 
							DATE_FORMAT({$sqlname}contract_statuslog.datum, '%d.%m.%Y %H:%s') as datum,
							{$sqlname}contract_statuslog.des as des,
							{$sqlname}contract_status.title as status,
							{$sqlname}contract_status.color as color
						FROM {$sqlname}contract_statuslog 
							LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = {$sqlname}contract_statuslog.status
						WHERE 
							{$sqlname}contract_statuslog.deid = '$data[deid]' and 
							{$sqlname}contract_statuslog.identity = '$identity' 
						ORDER BY {$sqlname}contract_statuslog.datum DESC
					" );

						foreach ( $re as $stat ) {

							$statuslog[] = [
								"datum"  => $stat['datum'],
								"color"  => $stat['color'],
								"status" => $stat['status'],
								"des"    => $stat['des'],
							];

						}

					}

					/**
					 * Вложенные файлы
					 */

					$ftitle = explode( ";", $da['ftitle'] );
					$fname  = explode( ";", $da['fname'] );

					if ( $da['ftitle'] != '' ) {

						foreach ( $ftitle as $i => $title ) {

							if ( $title != '' ) {

								$filename = str_replace( ".".getExtention( $fname[ $i ] ), "", $fname[ $i ] );
								$file     = $rootpath."/files/".$fpath.$fname[ $i ];

								$files[] = [
									"name"   => $title,
									"file"   => $fname[ $i ],
									"icon"   => get_icon2( $title ),
									"size"   => file_exists( $file ) ? filesize( $file ) : 0,
									"sizeKb" => file_exists( $file ) ? num_format( filesize( $file ) / 1000 ) : 0,
									"view"   => isViewable( $fname[ $i ] ),
									"topdf"  => in_array( getExtention( $fname[ $i ] ), [
										"docx",
										"doc",
										"rtf",
										"xlsx",
										"pptx",
										"ppt"
									] ) && !file_exists( $rootpath."/files/".$fpath.$filename.'.pdf' )
								];

							}

						}

					}

					$day = diffDate2( $da['datum_end'] );

					if ( is_between( $day, 0, 7 ) ) {
						$color = 'orangebg-sub';
					}
					elseif ( is_between( $day, 0, 30 ) ) {
						$color = 'bluebg-sub';
					}
					elseif ( is_between( $day, -14, 0 ) ) {
						$color = 'yellowbg-sub';
					}
					elseif ( is_between( $day, -30, -14 ) ) {
						$color = 'redbg-sub';
					}
					elseif ( $day < -30 ) {
						$color = 'graybg gray2';
					}
					else {
						$color = '';
					}

					$close = $da['did'] > 0 ? getDogData( $da['did'], "close" ) : NULL;

					$index = (diffDateTime2( $da['datum'], '', false ) + $i);

					$xclid = (int)$da['clid'];
					$xdid  = (int)$da['did'];
					if($xdid > 0){
						$xclid = 0;
					}

					$haveAccesse = get_accesse( $xclid, 0, $xdid ) == "yes";

					if( $xclid > 0 && $userSettings['filterAllByClientEdit'] == 'yes' && in_array($iduser1, $xfilter) ){
						$haveAccesse = true;
					}
					if( $xdid > 0 && $userSettings['filterAllByDealEdit'] == 'yes' && in_array($iduser1, $xfilter) ){
						$haveAccesse = true;
					}

					$list[ $index ] = [
						"id"            => $da['deid'],
						"deid"          => $da['deid'],
						"idtype"        => $da['idtype'],
						"type"          => $isContract ? "contract" : "document",
						"isDocument"    => true,
						"number"        => $da['number'],
						"title"         => $da['title'],
						"typeTitle"     => $typeList[ $da['idtype'] ],
						"datum"         => $da['datum'],
						"datumFormated" => format_date_rus_name( $da['datum_start'] ),
						"datum_start"   => $da['datum_start'] != '0000-00-00' ? $da['datum_start'] : NULL,
						"startFormated" => format_date_rus_name( $da['datum_start'] ),
						"datum_end"     => $da['datum_end'] != '0000-00-00' ? $da['datum_end'] : NULL,
						"endFormated"   => format_date_rus_name( $da['datum_end'] ),
						"color"         => diffDate2( $da['datum_end'] ) < 0 ? $color : '',
						"des"           => $da['des'] != '' ? $da['des'] : NULL,
						"did"           => $da['did'],
						"clid"          => $da['clid'],
						"pid"           => $da['pid'],
						"deal"          => $da['did'] > 0 ? [
							"title"   => getDogData( $da['did'], 'title' ),
							"icon"    => $close == 'yes' ? "icon-lock red" : "icon-briefcase blue",
							"isClose" => $close == 'yes' ? true : NULL
						] : NULL,
						"payer"         => $da['payer'] > 0 ? [
							"id"   => $da['payer'],
							"name" => current_client( $da['payer'] )
						] : NULL,
						"company"       => $mycompanyes[$da['mcid']],
						"signer"        => (int)$da['signer'] > 0 ? getSigner((int)$da['signer']) : NULL,
						"isAccess"      => $haveAccesse ? true : NULL,
						"files"         => !empty( $files ) ? $files : NULL,
						"haveFiles"     => !empty( $files ) ? true : NULL,
						"status"        => [
							"id"        => $status['id'],
							"title"     => $status['title'],
							"color"     => $status['color'],
							"statuslog" => !empty( $statuslog ) ? $statuslog : NULL
						],
						"haveStatus"    => !empty( $status ) ? true : NULL,
						"canBeDeleted"  => $isadmin == 'on' || $close != "yes" ? true : NULL
					];

					$i++;

				}

			}

		}

		//print_r($list);

		//сортируем массив документов по давности
		if ( $docSort != 'desc' ) {
			krsort( $list );
		}
		else {
			ksort( $list );
		}

		return [
			"list" => array_values( $list ),
			"did"  => $did,
			"clid" => $clid,
		];

	}

	/**
	 * Вывод списка документов
	 * @param array $params
	 *  - page - страница
	 *  - ord - сортировка
	 *  - tuda - направление сортировки (desc||asc)
	 *  - mc - id компании
	 *  - array status - статусы документа
	 *  - array type - типы документа
	 *  - word - строка поиска
	 *
	 * @return array
	 * @throws Exception
	 */
	public function list(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$list       = [];
		$sort       = '';
		$word       = $params['word'];
		$page       = $params['page'];
		$ord        = $params['ord'];
		$tuda       = $params['tuda'];
		$status     = $params['status'];
		$type       = $params['type'];
		$oldonly    = $params['oldonly'];

		$iduser1    = $GLOBALS['iduser1'];
		$tipuser    = $GLOBALS['tipuser'];
		$acs_prava  = $GLOBALS['acs_prava'];
		$userSettings = $GLOBALS['userSettings'];

		$statuses = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE identity = '$identity'");

		$mycomps = Guides ::myComps();

		$lines_per_page = 100; //Стоимость записей на страницу

		if ($word != "") {
			$sort .= " and (ct.des LIKE '%$word%' or ct.number LIKE '%$word%' OR clt.title LIKE '%$word%')";
		}

		if ($oldonly == 'old') {
			$sort .= " and ct.datum_end IS NOT NULL and DATE_FORMAT(ct.datum_end, '%Y-%m-%d') < '".current_datum(30)."'";
		}

		if ($oldonly == 'old30') {
			$sort .= " and ct.datum_end IS NOT NULL and (DATE_FORMAT(ct.datum_end, '%Y-%m-%d') BETWEEN '".current_datum(30)."' AND '".current_datum(14)."')";
		}

		if ($oldonly == 'old14') {
			$sort .= " and ct.datum_end IS NOT NULL and (DATE_FORMAT(ct.datum_end, '%Y-%m-%d') BETWEEN '".current_datum(14)."' AND '".current_datum()."')";
		}

		if (!empty($status)) {
			$sort .= " and ct.status IN (".implode(",", $status).")";
		}

		if (!empty($type)) {
			$sort .= " and ct.idtype IN (".implode(",", $type).")";
		}
		else {
			$sort .= " and (ct.idtype IN (SELECT id FROM {$sqlname}contract_type WHERE COALESCE(type, '') NOT IN ('get_akt','get_aktper') and identity = '$identity') or ct.idtype = 0)";
		}

		if ($tipuser == "Менеджер продаж" && $acs_prava != "on") {

			$sort .= " AND ct.iduser = '$iduser1'";

		}

		if ( (int)$params['mc'] > 0 ) {
			$sort .= " and dg.mcid = '$params[mc]'";
		}

		$sub[] = 'client';
		$sub[] = 'person';
		if ($userSettings['dostup']['partner'] == 'on') {
			$sub[] = 'partner';
		}
		if ($userSettings['dostup']['contractor'] == 'on') {
			$sub[] = 'contractor';
		}
		if ($userSettings['dostup']['concurent'] == 'on') {
			$sub[] = 'concurent';
		}

		$sort .= " AND clt.type IN (".yimplode(",", $sub, "'").") ";

		$query     = "
			SELECT 
				COUNT(*)
			FROM {$sqlname}contract `ct`
				LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
				LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
				LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = ct.clid
			WHERE 
				ct.deid > 0 
				$sort and 
				ct.identity = '$identity'
		";
		$all_lines = $db -> getOne($query);

		$page           = ( empty($page) || $page <= 0 ) ? 1 : (int)$page;
		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		//print
		$query = "
			SELECT 
				ct.deid,
				ct.datum_end,
				ct.datum_start,
				ct.number,
				ct.title,
				ct.clid,
				ct.pid,
				ct.did,
				ct.payer,
				ct.idtype,
				clt.title as client,
				dg.title as deal,
				{$sqlname}contract_status.title as tstatus,
				{$sqlname}contract_status.color as color,
				dg.mcid as mc,
				ct.mcid
			FROM {$sqlname}contract `ct`
				LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
				LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
				LEFT JOIN {$sqlname}clientcat `clt` ON clt.clid = ct.clid
			WHERE 
				ct.deid > 0 
				$sort and 
				ct.identity = '$identity'
		";

		$query .= " ORDER BY $ord $tuda LIMIT $lpos,$lines_per_page";

		$result      = $db -> query($query);
		$count_pages = ceil($all_lines / $lines_per_page);
		if ($count_pages == 0) {
			$count_pages = 1;
		}

		while ($da = $db -> fetch($result)) {

			$payer = '';
			$color = '';

			if ($da['datum_end'] == "0000-00-00") {
				$da['datum_end'] = "";
			}
			else {

				$day = datestoday($da['datum_end']); //дней до окончания действия

				if (is_between($day, 0, 7)) {
					$color = 'orangebg-sub';
				}
				elseif (is_between($day, 0, 30)) {
					$color = 'bluebg-sub';
				}
				elseif (is_between($day, -14, 0)) {
					$color = 'yellowbg-sub';
				}
				elseif (is_between($day, -30, -14)) {
					$color = 'redbg-sub';
				}
				elseif ($day < -30) {
					$color = 'graybg gray2';
				}

			}

			if ((int)$da['payer'] > 0 && (int)$da['payer'] != (int)$da['clid']) {
				$payer = current_client($da['payer']);
			}

			//статусы, применимые к текущему типу документоа
			$stat = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity'");

			$list[] = [
				"id"          => $da['deid'],
				"datum_start" => format_date_rus($da['datum_start']),
				"datum_end"   => format_date_rus($da['datum_end']),
				"color"       => $color,
				"number"      => $da['number'],
				"title"       => $da['title'],
				"clid"        => $da['clid'],
				"client"      => $da['client'],
				"pid"         => $da['pid'],
				"person"      => current_person($da['pid']),
				"did"         => $da['did'],
				"deal"        => $da['deal'],
				"payerid"     => $da['payer'],
				"payer"       => $payer,
				"statuson"    => ( $stat > 0 ) ? "1" : "",
				"status"      => ( $da['tstatus'] != '' ) ? $da['tstatus'] : "--",
				"statuscolor" => ( $da['tstatus'] != '' ) ? $da['color'] : "#fff",
				"mc"          => $da['mc'] > 0 ? $da['mc'] : $da['mcid'],
				"company"     => $da['mc'] > 0 ? $mycomps[$da['mc']] : $mycomps[$da['mcid']]
			];

		}

		return [
			"list"     => $list,
			"page"     => (int)$page,
			"pageall"  => (int)$count_pages,
			"ord"      => $ord,
			"desc"     => $tuda,
			"isstatus" => ( $statuses > 0 ) ? "1" : ""
		];

	}

	/**
	 * Добавление/Изменение документа
	 *
	 * @param int $id     - id документа
	 *                      - если 0, то создается новый документ
	 *                      - иначе редактируем старый
	 *
	 * @param array $params - массив с параметрами
	 *
	 *     - int **did** - id сделки
	 *     - int **clid** - id заказчика (если did > 0, то не обязательно)
	 *     - int **payer** - id плательщика (если did > 0, то не обязательно)
	 *     - int **signer** - id подписанта
	 *     - int **idtype** - id типа документа
	 *     - str **number** - номер документа (если не указан, то будет сгенерирован, в т.ч. с учетом формата номера)
	 *     - str **title** - наименование документа, если не указано, то берем из названия Типа документа
	 *     - text **des** - описание документа
	 *     - date **datum_start** - дата подписания
	 *     - date **datum_end** - период действия "до"
	 *     - int **status** - статус документа
	 *     - int **oldstatus** - предыдущий статус документа
	 *     - text **statusdes** - комментарий смены статуса
	 *     - str **subaction** - если = status, то просто меняем статус документа
	 *
	 * @return array
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> edit($id, $params);
	 * ```
	 */
	public function edit(int $id = 0, array $params = []): array {

		global $hooks;

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$iduser1       = ($params['iduser'] > 0) ? $params['iduser'] : $this -> iduser1;
		$identity      = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;
		$otherSettings = $this -> otherSettings;

		//$id = (int)$id;

		//file_put_contents($this -> rootpath."/cash/document.json", json_encode_cyr($params));

		$post = $params;

		if ( $id > 0 ) {
			$params = $hooks -> apply_filters( "document_editfilter", $params );
		}
		else {
			$params = $hooks -> apply_filters( "document_addfilter", $params );
		}

		$message = $error = [];

		//если указана сделка, то clid и payer берем из неё
		if ( (int)$params['did'] > 0 ) {

			$deal = get_dog_info( (int)$params['did'], "yes" );

			$params['clid']  = (int)$deal['clid'];
			$params['payer'] = (int)$deal['payer'];

		}

		$cparams = $params;

		unset( $cparams['user'], $cparams['oldstatus'], $cparams['statusdes'] );


		$set             = $db -> getRow( "SELECT contract_format, contract_num FROM {$sqlname}settings WHERE id = '$identity'" );
		$contract_format = $set["contract_format"];
		//$contract_num    = $set["contract_num"];

		$result = $db -> getRow( "SELECT * FROM {$sqlname}contract_type WHERE id = '$params[idtype]' and identity = '$identity'" );
		$type   = $result["type"];
		//$num           = $result["num"];
		$format        = $result["format"];
		$contractTitle = $result["title"];

		if ( (!isset( $params['number'] ) || $params['number'] == '') && $id == 0 ) {

			// добавим Hook для изменения номера документа
			if ( $hooks ) {

				$params['number'] = $hooks -> apply_filters( "document_number", [
					"type"   => $type,
					"idtype" => (int)$params['idtype'],
					"did"    => (int)$params['did']
				] );

			}

			// если это договор
			if ( $type == 'get_dogovor' ) {
				$cparams['number'] = ($contract_format == '') ? untag( $params['number'] ) : generate_num( 'contract' );
			}
			// если это другой документ
			else {
				$cparams['number'] = ($format == '') ? untag( $params['number'] ) : genDocsNum( $params['idtype'] );
			}

		}

		if ( $type != 'get_dogovor' && $id == 0 ) {
			$cparams['title'] = ($cparams['title'] != '') ? untag( $cparams['title'] ) : $contractTitle;
		}

		if ( $type != 'get_dogovor' && $id == 0 ) {
			$cparams['datum'] = current_datumtime();
		}

		$cparams['datum_start'] = (!isset( $params['datum_start'] ) || $params['datum_start'] == '') ? current_datum() : untag($params['datum_start']);
		$cparams['datum_end']   = (!isset( $params['datum_end'] ) || $params['datum_end'] == '') ? date( 'Y' )."-12-31" : untag($params['datum_end']);

		//print_r($cparams);

		$cparams = $db -> filterArray( $cparams, [
			"datum",
			"number",
			"datum_start",
			"datum_end",
			"des",
			"clid",
			"payer",
			"did",
			"pid",
			"ftitle",
			"fname",
			"ftype",
			"iduser",
			"title",
			"idtype",
			"crid",
			"mcid",
			"signer",
			"status",
			"identity"
		] );

		//редактируем запись
		if ( $id > 0 ) {

			if ( $params['subaction'] == 'status' ) {

				$cparams = [
					"status" => (int)$params['status']
				];

			}

			//unset($cparams['number']);

			$db -> query( "UPDATE {$sqlname}contract SET ?u WHERE deid = '$id' and identity = '$identity'", $cparams );
			$message[] = "Данные обновлены";

			$cparams['deid'] = $id;

			if ( $hooks ) {
				$hooks -> do_action( "document_edit", $post, $cparams );
			}

			$type = $db -> getRow( "SELECT type, format FROM {$sqlname}contract_type where id = '$params[idtype]' and identity = '$identity'" );

			/**
			 * Событие для документа
			 */

			if ( $type['type'] != 'invoice' ) {

				//это акт
				if ( in_array( $type['type'], [
					'get_akt',
					'get_aktper'
				] ) ) {
					$etype = "akt";
				}

				else {
					$etype = "contract";
				}

				event ::fire( $etype.'.edit', [
					"id"     => $id,
					"number" => $cparams['number'],
					"datum"  => $cparams['datum'],
					"did"    => (int)$cparams['did'],
					"payer"  => (int)$cparams['payer'],
					"clid"   => (int)$cparams['clid'],
					"pid"    => (int)$cparams['pid'],
					"autor"  => $iduser1
				] );

			}

		}
		//добавим новую
		else {

			$cparams['iduser'] = (int)$iduser1;

			$db -> query( "INSERT INTO {$sqlname}contract SET ?u", $cparams );
			$id = $db -> insertId();

			$cparams['deid'] = $id;

			if ( $hooks ) {
				$hooks -> do_action( "document_add", $post, $cparams );
			}

			//обновим счетчик для документа
			$contract = $db -> getRow( "select contract_num, contract_format from {$sqlname}settings WHERE id = '$identity'" );
			$type     = $db -> getRow( "SELECT type, format FROM {$sqlname}contract_type where id = '$params[idtype]' and identity = '$identity'" );

			if ( $type['format'] != '' ) {

				$db -> query( "UPDATE {$sqlname}contract_type SET ?u WHERE id = '$params[idtype]' AND identity = '$identity'", ["num" => genDocsNum( $params['idtype'], true )] );

			}

			//обновим счетчик договоров
			if ( $contract['contract_format'] != '' && $type['type'] == 'get_dogovor' ) {

				$newnum = (int)$contract['contract_num'] + 1;

				$db -> query( "UPDATE {$sqlname}settings SET ?u WHERE id = '$identity'", ['contract_num' => $newnum] );

				unlink( $rootpath."/cash/".$fpath."settings.all.json" );

			}

			//привяжем к сделке, если она указана
			if ( $cparams['did'] > 0 && $id > 0 && $type['type'] == 'get_dogovor' ) {

				$db -> query( "UPDATE {$sqlname}dogovor SET ?u WHERE did = '$cparams[did]' and identity = '$identity'", ['dog_num' => $id] );

			}

			$params['des'] = $message[] = 'Создан документ';

			/**
			 * Событие для документа
			 */

			if ( $type['type'] != 'invoice' ) {

				//это акт
				if ( in_array( $type['type'], [
					'get_akt',
					'get_aktper'
				] ) ) {
					$etype = "akt";
				}

				else {
					$etype = "document";
				}

				event ::fire( $etype.'.add', [
					"id"     => $id,
					"number" => $cparams['number'],
					"datum"  => $cparams['datum'],
					"did"    => (int)$cparams['did'],
					"payer"  => (int)$cparams['payer'],
					"clid"   => (int)$cparams['clid'],
					"pid"    => (int)$cparams['pid'],
					"autor"  => (int)$iduser1
				] );

			}


		}

		/**
		 * Добавим статус в лог
		 */
		if ( (int)$params['status'] > 0 ) {

			$this -> logging( [
				'deid'      => $id,
				'iduser'    => (int)$params['user'],
				'status'    => (int)$params['status'],
				'oldstatus' => (int)$params['oldstatus'],
				'des'       => ($params['des'] != '') ? $params['des'].". ".$params['statusdes'] : $params['statusdes']
			] );

		}

		return [
			"id"      => $id,
			"message" => $message,
			"error"   => $error
		];

	}

	/**
	 * Удаление документа
	 *
	 * @param int $id
	 *
	 * @return array - ответ
	 *               - int **id** - id записи
	 *               - string **message** - сообщение о результате
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> delete($id);
	 * ```
	 *
	 */
	public function delete(int $id = 0): array {

		global $hooks;

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$iduser1       = $this -> iduser1;
		$identity      = $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;

		$message   = $error = '';
		$uploaddir = $rootpath.'/files/'.$fpath;

		$fc    = $db -> getRow( "select fname, did, idtype from {$sqlname}contract where deid = '$id' and identity = '$identity'" );
		$fname = explode( ";", $fc['fname'] );

		$type = $db -> getRow( "SELECT * FROM {$sqlname}contract_type where id = '$fc[idtype]' and identity = '$identity'" );

		$db -> query( "delete from {$sqlname}contract where deid = '$id' and identity = '$identity'" );

		if ( $hooks ) {
			$hooks -> do_action( "document_delete", $id );
		}

		//удалим файлы
		foreach( $fname as $f) {

			@unlink( $uploaddir.$f );

		}

		//удалим договор из сделки
		if ( $type['type'] == 'get_dogovor' ) {
			$db -> query( "update {$sqlname}dogovor set dog_num = '' WHERE did = '$fc[did]' and identity = '$identity'" );
		}

		/**
		 * Удалим лог документа
		 */
		$db -> query( "DELETE FROM {$sqlname}contract_statuslog WHERE deid = '$id' and identity = '$identity'" );

		$message = 'Документ удален';

		event ::fire( 'contract.delete', [
			"deid" => $id,
			"did"  => $fc['did']
		] );

		if ( $type['type'] != 'invoice' ) {

			//это акт
			if ( in_array( $type['type'], [
				'get_akt',
				'get_aktper'
			] ) ) {
				$etype = "akt";
			}

			else {
				$etype = "document";
			}

			event ::fire( $etype.'.add', [
				"id"    => $id,
				"did"   => $fc['did'],
				"autor" => $iduser1
			] );

		}

		return [
			"id"      => $id,
			"message" => $message,
			"error"   => $error
		];

	}

	/**
	 * Генерирует файл из шаблона
	 * сгенерированные при создании документа тэги возвращаются в объекте:
	 *  - tags
	 *
	 * @param int $id
	 * @param array $params - массив с параметрами
	 *                      - int **template** - id шаблона
	 *                      - int **templateFile** - файл шаблона ( указать полный абсолютный путь до файла )
	 *                      - array **tags** - массив тэгов. если не указано, то берем из функции getNewTag
	 *                      - array **tagsAttached** - массив тэгов, которым надо дополнить массив tags
	 *                          - array **images** - массив изображений для замены ( требуется изображение-заглушка для каждого изображения, см. Примечание )
	 *                              - name - имя тега
	 *                              - file - имя файла (файл должен находиться в папке files или следует передавать имя файла с абсолютным путем к нему)
	 *                      - bool **append** - true/false - добавить файлы к существующим
	 *                      - str **getPDF** - yes - генерировать PDF
	 *                      - str **outputPath** - собственная папка для сохранения результата ( указать полный абсолютный путь до папки с конечным слэшем ). Если указано, то прикрепление к документу и генерация PDF отключается ( use doc2PDF )
	 *                      - str **fileName** - присвоить собственное имя генерируемому файлу
	 *                      - str **docName** - присвоить собственное название генерируемому документу
	 *
	 * **Примечание:**
	 *
	 * В шаблонах в формате Word изображение-заглушка должна содержать в качестве Альтернативного текста следующий код (заголовок или описание):
	 *
	 * * NAME - имя тега
	 *
	 * ```
	 * [onshow.NAME;ope=changepic;from=NAME;tagpos=inside;]
	 * ```
	 *
	 * В шаблонах в формате Excel изображение-заглушка печати должна содержать в качестве Альтернативного текста следующий код (заголовок или описание):
	 * ```
	 * [onshow.NAME;ope=changepic;tagpos=inside;adjust=inside;unique]
	 * ```
	 *
	 * @return string - ответ по результату
	 *
	 * @throws Exception
	 *
	 * Пример:
	 *
	 * ```php
	 * $params = [
	 *      "tagsAttached" => [
	 *          "images" => [
	 *              "barCode" => "barcode423424.png",
	 *              "photo"   => "photo4656646.png"
	 *          ],
	 *          "ticket" => "0004556",
	 *          "someTag" => "Это произвольный тег"
	 *      ]
	 * ];
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> generate($id, $params);
	 * ```
	 */
	public function generate(int $id = 0, array $params = []): string {

		global $hooks;

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$iduser1       = $this -> iduser1;
		$identity      = $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;

		$post = $params;

		$params = $hooks -> apply_filters( "document_generate_filter", $params );

		//проверяем папку для загрузки и если нет, то создаем
		createDir($rootpath.'/cash/DocxTemplating');

		// приходится подключать вручную, т.к. автолоад не подгружает его
		require_once $rootpath."/vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php";

		$file     = '';
		$contract = [];

		//данные документа
		if($id > 0) {

			$contract = self ::info( $id );

		}

		if(!$params['templateFile']) {

			//id шаблона
			$template = $params['template'];

			//данные для генератора шаблона
			$file         = $db -> getOne( "SELECT file FROM {$sqlname}contract_temp where id = '$template' and identity = '$identity'" );
			$templateFile = $rootpath.'/cash/'.$fpath.'templates/'.$file;

		}
		else {

			$templateFile = $params['templateFile'];

		}

		$ext    = texttosmall( getExtention($templateFile ) );
		$fxname = $fname = (!isset($params['fileName'])) ? time().'.'.$ext : $params['fileName'].'.'.$ext;

		$ftype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

		if ( $ext == 'xlsx' ) {
			$ftype = 'application/vnd.ms-excel';
		}

		//print $ext."\n";

		//данные для генератора шаблона
		$fxtitle = $ftitle = (!$params['docName']) ? $contract['title'].' №'.$contract['number'].' от '.format_date_rus( $contract['datum_start'] ).'.'.$ext : $params['docName'];

		//данные для генератора шаблона
		$tags = (empty( $params['tags'] )) ? getNewTag( $id, (int)$contract['did'], (int)$contract['clid'], (int)$contract['mcid'], (int)$contract['pid'] ) : $params['tags'];

		// qrcode
		$qr = self::getQR($tags);
		$tags["QRcode"] = str_replace( ["../", "/"], [$rootpath."/", DIRECTORY_SEPARATOR], $qr );
		//$tags['tagsAttached']['images']["[QRcode]"] = $qr;
		
		//print file_exists($qr) ? "exist" : "notfound";
		//print "\n$qr\n";

		if ( !empty( $params['tagsAttached'] ) ) {
			$tags = array_merge( $tags, $params['tagsAttached']['images'] );
			$tags['tagsAttached'] = array_merge((array)$tags['tagsAttached'], (array)$params['tagsAttached']);
		}

		//print_r($tags);

		/**
		 * Преобразование валюты
		 */
		$deal = get_dog_info( (int)$contract['did'], "yes" );
		if( (int)$deal['idcourse'] > 0 ) {

			$tags = Currency ::currencyConvertSpeka( $tags, (int)$deal['idcourse'], ['NdsPer','PriceWoNds','Sum','SumWoNds','spekaSum','tovarSum','uslugaSum','materialSum'] );

		}

		// подписант
		/*if ( (int)$contract['signer'] > 0 ){

			$xsigner = getSigner((int)$contract['signer']);

			$tags['compDirName']      = $xsigner["title"];
			$tags['compDirSignature'] = $xsigner["signature"];
			$tags['compDirStatus']    = $xsigner["status"];
			$tags['compDirOsnovanie'] = $xsigner["osnovanie"];

			$tags['compSignature'] = $root.'/cash/'.$fpath.'templates/'.$xsigner['stamp'];

		}*/

		foreach ( $tags as $key => $value ) {
			if ( !in_array( $key, [
				'speka',
				'invoices',
				'tovar',
				'usluga',
				'material'
			] ) ) {
				$GLOBALS[ $key ] = $value;
				//$tags[ $key ] = $value;
			}
		}

		if ( $tags['compSignature'] != '' ) {
			$tags['compSignature'] = str_replace( ["../", "/"], [$rootpath."/", DIRECTORY_SEPARATOR], $tags['compSignature'] );
			//$tags['compSignature'] = "../".$tags['compSignature'];
		}

		$compSignature = $GLOBALS['compSignatureXLSX'] = $tags['compSignature'];
		
		// доп.фильтр для тегов
		$tags = $hooks -> apply_filters( "document_tags_filter", $tags );
		$GLOBALS['tags'] = $tags;

		if( isset($tags['BarCode']) ){
			$params['BarCode'] = $tags['BarCode'];
			//$params["tagsAttached"]["images"]["BarCode"] = $tags["tagsAttached"]["images"]["BarCode"];
		}
		if( isset($tags['tagsAttached']) ){
			$params['tagsAttached'] = $tags['tagsAttached'];
		}

		//результат для второго обработчика
		$outputPath = (!$params['outputPath']) ? $rootpath.'/files/'.$fpath.$fname : $params['outputPath'].$fpath.$fname;

		// доп.обработка тэгов - заменяем NULL на пустое значение
		$tags = array_map(static function($a){

			if( is_array($a) && !empty($a) ){
				return $a;
			}

			if(empty($a)){
				return "";
			}

			return $a;

		}, $tags);
		
		//file_put_contents($rootpath."/cash/tags.json", json_encode_cyr($tags));

		//print $templateFile;

		//если файл шаблона существует
		if ( file_exists( $templateFile ) ) {

			//результат для первого обработчика
			$inputPath = $rootpath.'/files/'.$fpath.'d'.$fname;

			//результат для второго обработчика
			//$outputPath = $rootpath.'/files/'.$fpath.$fname;

			// обработка по новому шаблону OpenTBS. старт
			// обрабатывает только массивы

			$TBS = new clsTinyButStrong(); // new instance of TBS
			$TBS -> SetOption( 'noerr', true );
			$TBS -> PlugIn( TBS_INSTALL, OPENTBS_PLUGIN ); // load the OpenTBS plugin
			$TBS -> PlugIn( OPENTBS_RELATIVE_CELLS, true, OPENTBS_ALL );
			$TBS -> PlugIn( OPENTBS_ALREADY_XML, true, OPENTBS_ALL );
			$TBS -> PlugIn(OPENTBS_DEBUG_XML_SHOW);
			//$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_UTF8 );
			$TBS -> LoadTemplate( $templateFile, OPENTBS_ALREADY_XML );

			//меняем печать
			if ( file_exists( $compSignature ) && !is_dir( $compSignature ) ) {

				$PicRef = '[compSignature]';
				$File   = $compSignature;
				$Prms   = ['unique' => false];

				// Не работает с XLSX
				// [onshow.compSignature;ope=changepic;from=compSignature;tagpos=inside;]
				$TBS -> PlugIn( OPENTBS_CHANGE_PICTURE, $PicRef, $File, $Prms );

				// для XLSX картинка должна иметь свойства
				// [onshow.compSignatureXLSX;ope=changepic;tagpos=inside;adjust=inside;unique]
				$TBS -> PlugIn( OPENTBS_MERGE_SPECIAL_ITEMS );

			}
			if ( file_exists( $qr ) && !is_dir( $qr ) ) {
				
				$PicRef = '[QRcode]';
				$File   = $qr;
				$Prms   = ['unique' => false];
				
				// Не работает с XLSX
				// [onshow.compSignature;ope=changepic;from=compSignature;tagpos=inside;]
				$TBS -> PlugIn( OPENTBS_CHANGE_PICTURE, $PicRef, $File, $Prms );
				
				// для XLSX картинка должна иметь свойства
				// [onshow.compSignatureXLSX;ope=changepic;tagpos=inside;adjust=inside;unique]
				$TBS -> PlugIn( OPENTBS_MERGE_SPECIAL_ITEMS );
				
			}
			if ( file_exists( $rootpath.'/files/'.$params['BarCode'] ) ) {

				//sleep(5);

				$PicRef = '[BarCode]';
				$File   = str_replace( ["../", "/"], [$rootpath."/", DIRECTORY_SEPARATOR], $rootpath.'/files/'.$params['BarCode']);
				//$File   = $rootpath.'/files/'.$params['BarCode'];
				$Prms   = ['unique' => false];

				$TBS -> PlugIn( OPENTBS_CHANGE_PICTURE, $PicRef, $File, $Prms );

				$xf = $File;

				// для XLSX картинка должна иметь свойства
				// [onshow.compSignatureXLSX;ope=changepic;tagpos=inside;adjust=inside;unique]
				$TBS -> PlugIn( OPENTBS_MERGE_SPECIAL_ITEMS );

				//unset($tags['BarCode']);

			}

			// обработка сторонних изображений
			if(!empty($params['tagsAttached']['images'])){

				foreach ($params['tagsAttached']['images'] as $xname => $xfile){

					if(!file_exists($xfile)) {
						$xfile = $rootpath."/files/".$xfile;
					}

					if(file_exists($xfile)) {
						
						$xfile = str_replace( ["../", "/"], [$rootpath.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $xfile );
						
						$PicRef = "[".$xname."]";
						$File   = $xfile;
						$Prms   = ['unique' => false];

						// Не работает с XLSX
						// [onshow.compSignature;ope=changepic;from=compSignature;tagpos=inside;]
						$TBS -> PlugIn( OPENTBS_CHANGE_PICTURE, $PicRef, $File, $Prms );

						// для XLSX картинка должна иметь свойства
						// [onshow.compSignatureXLSX;ope=changepic;tagpos=inside;adjust=inside;unique]
						$TBS -> PlugIn( OPENTBS_MERGE_SPECIAL_ITEMS );
						
						flush();

					}

					unset($tags['tagsAttached']['images'][$xname]);

				}

			}

			if ( !empty( $tags['speka'] ) ) {
				$TBS -> MergeBlock( 'speka', $tags['speka'] );
			}
			if ( !empty( $tags['invoices'] ) ) {
				$TBS -> MergeBlock( 'invoices', $tags['invoices'] );
			}
			if ( !empty( $tags['tovar'] ) ) {
				$TBS -> MergeBlock( 'tovar', $tags['tovar'] );
			}
			if ( !empty( $tags['usluga'] ) ) {
				$TBS -> MergeBlock( 'usluga', $tags['usluga'] );
			}
			if ( !empty( $tags['material'] ) ) {
				$TBS -> MergeBlock( 'material', $tags['material'] );
			}

			foreach ($tags as $tag => $val) {
				$TBS -> MergeBlock("var.".$tag, $val);
			}

			// если передать этот параметр, то второй шаблонизатор не будет использоваться
			// но все одиночные теги должны будут начинаться на var [var.TAG]
			if($this -> params['useOnlyTBS'] || $tags['useOnlyTBS']) {
				$ext = 'doc';
			}
			
			//$ext = 'doc';
			
			//print $inputPath;

			if ( $ext == 'docx' ) {
				$TBS -> Show( OPENTBS_FILE, $inputPath );
			}
			else {
				$TBS -> Show( OPENTBS_FILE, $outputPath );
			}

			//обработка по новому шаблону OpenTBS. финиш

			//обработка по новому шаблону

			//обработка DOCX-файла и отдельных тегов в нем
			if ( $ext == 'docx') {

				/*if( !file_exists($inputPath)  ){
					$inputPath = $templateFile;
				}*/

				//print $inputPath;

				$docxTemplate = new DocxTemplate( $inputPath, $rootpath."/cash/DocxTemplating" );

				$docxTemplate -> merge( $tags, $outputPath );

				//удалим промежуточный файл
				unlink( $inputPath );

			}

			//обработка по новому шаблону. финиш


			//добавим файлы к существующим
			if ( $params['append'] && !isset( $params['outputPath'])) {
				$this -> appendFiles( $id, [
					'ftitle' => $ftitle,
					'fname'  => $fname,
					'ftype'  => $ftype
				] );
			}

			//сгенерируем PDF
			if ( $params['getPDF'] == 'yes' && !isset($params['outputPath']) ) {

				//doc2PDF(0, $fxname, '', $fxtitle, $id);

				$params = [
					"file" => $fxname,
					"name" => $fxtitle,
					"deid" => $id
				];

				$rez  = new self();
				$rez -> doc2PDF( 0, $params );

				if ( $hooks ) {
					$hooks -> do_action( "document_generate", $post, $params );
				}

			}

			$message = "Документ $fxtitle обработан";

		}
		else {
			$message = "Ошибка: не найден файл шаблона $file";
		}

		// удаляем картинку с qr-кодом
		//unlink($qr);

		$this -> rezult = $fxname;
		$this -> tags = $tags;

		file_put_contents($rootpath."/cash/xtags.json", json_encode_cyr([
			"tags"   => $tags,
			"params" => $params,
			"xf" => $xf,
			"xc" => $compSignature
		]));

		return $message;

	}

	/**
	 * Добавить файлы к документу
	 *
	 * @param int   $id
	 * @param array $params - массив с параметрами
	 *                      - **ftitle** - оригинальное имя
	 *                      - **fname** - системное имя
	 *                      - **ftype** - тип файла
	 *
	 * @return bool
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> appendFiles($id, $params);
	 * ```
	 */
	public function appendFiles(int $id = 0, array $params = []): bool {

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$identity      = $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;

		$contract = self ::info( $id );
		$dftitle  = (array)yexplode( ";", (string)$contract['ftitle'] );
		$dfname   = (array)yexplode( ";", (string)$contract['fname'] );
		$dftype   = (array)yexplode( ";", (string)$contract['ftype'] );

		//редактируем запись
		if ( $id > 0 ) {

			//надо удалить похожий файл
			foreach ( $dftitle as $i => $item ) {

				if ( $item == $params['ftitle'] ) {

					unset( $dftitle[ $i ], $dfname[ $i ], $dftype[ $i ] );

					unlink( $rootpath."/files/".$fpath.$dfname[ $i ] );

				}

			}

			$dftitle[] = $params['ftitle'];
			$dfname[]  = $params['fname'];
			$dftype[]  = $params['ftype'];

			$di['ftitle'] = yimplode( ";", (array)$dftitle );
			$di['fname']  = yimplode( ";", (array)$dfname );
			$di['ftype']  = yimplode( ";", (array)$dftype );

			//Добавим файл к договору
			$db -> query( "UPDATE {$sqlname}contract SET ?u WHERE deid = '$id' and identity = '$identity'", $di );

		}

		return true;

	}

	/**
	 * Загрузка файлов
	 * работает с глобальным массивом $_FILES
	 *
	 * @return array - ответ
	 * ```php
	 * [
	 *      [data] => [
	 *          "ftitle" => "XXX.docx"
	 *          "fname" => "38cf1d91050cc9f4283d2619d622ab8f.docx"
	 *          "ftype" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
	 *      ]
	 *      [message] => [
	 *          "0" => "Файл XXX.docx успешно загружен."
	 *      ]
	 * ]
	 * ```
	 */
	public function upload(): array {

		$rootpath      = $this -> rootpath;
		$fpath         = $this -> fpath;

		$maxupload = $GLOBALS['maxupload'];
		$ext_allow = $GLOBALS['ext_allow'];

		if ( $maxupload == '' ) {
			$maxupload = str_replace( [
				'M',
				'm'
			], '', @ini_get( 'upload_max_filesize' ) );
		}

		$uploaddir = $rootpath.'/files/'.$fpath;
		$extAllow  = (array)yexplode( ",", (string)$ext_allow );
		$message   = [];

		$ftitle2 = $fname2 = $ftype2 = [];
		//$ftitle  = $fname = $ftype = '';

		for ( $i = 0, $iMax = count( (array)$_FILES[ 'file' ][ 'name' ] ); $i < $iMax; $i++ ) {

			if ( filesize( $_FILES['file']['tmp_name'][ $i ] ) > 0 ) {

				$ftitle     = basename( $_FILES['file']['name'][ $i ] );
				$ext        = texttosmall( getExtention($ftitle ) );
				$fname      = md5( $ftitle.filesize( $_FILES['files']['tmp_name'][ $i ] ) ).".".$ext;
				$ftype      = $_FILES['file']['type'][ $i ];
				$uploadfile = $uploaddir.$fname;

				if ( in_array( $ext, $extAllow, true ) ) {

					if ( (filesize( $_FILES['file']['tmp_name'][ $i ] ) / 1000000) > $maxupload ) {
						$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Превышает допустимые размеры!';
					}


					else {

						if ( move_uploaded_file( $_FILES['file']['tmp_name'][ $i ], $uploadfile ) ) {

							$message[] = 'Файл '.$ftitle.' успешно загружен.';
							$ftitle2[] = $ftitle;
							$fname2[]  = $fname;
							$ftype2[]  = $ftype;

						}
						else {
							$message[] = 'Ошибка при загрузке файла '.$ftitle.' - '.$_FILES['file']['error'][ $i ];
						}

					}

				}
				else {
					$message[] = 'Ошибка при загрузке файла '.$ftitle.' - Файлы такого типа не разрешено загружать.';
				}

			}

		}

		$ftitle = implode( ";", $ftitle2 );
		$fname  = implode( ";", $fname2 );
		$ftype  = implode( ";", $ftype2 );

		//print_r($response);

		return [
			"data"    => [
				"ftitle" => $ftitle,
				"fname"  => $fname,
				"ftype"  => $ftype
			],
			"message" => $message
		];

	}

	/**
	 * Логгирование статусов
	 *
	 * @param array $params - массив с параметрами
	 *                      - int **deid** - id документа
	 *                      - int **iduser** - id сотрудника, ктоый меняет статус
	 *                      - int **status** - id нового статуса
	 *                      - int **oldstatus** - id старого статуса, если не указано, то берем из документа
	 *                      - text **des** - комментарий
	 *
	 * @return bool
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> logging($params);
	 * ```
	 */
	public function logging(array $params = []): bool {

		$sqlname       = $this -> sqlname;
		$identity      = $this -> identity;
		$db            = $this -> db;

		$params['oldstatus'] = ($params['oldstatus'] > 0) ? $params['oldstatus'] : self ::getSatus( $params['deid'] );

		$db -> query( "INSERT INTO {$sqlname}contract_statuslog SET ?u", [
			'deid'      => $params['deid'],
			'datum'     => current_datumtime(),
			'iduser'    => $params['iduser'],
			'status'    => $params['status'],
			'oldstatus' => ($params['oldstatus'] > 0) ? $params['oldstatus'] : 0,
			'des'       => $params['des'],
			'identity'  => $identity
		] );

		return true;

	}

	/**
	 * Преобразует файл в PDF
	 *
	 * @param int   $fid    - id файла
	 * @param array $params - массив с параметрами
	 *                      - string **file** - название файла (как он хранится в системе), если $fid не указан
	 *                      - string **disposition** - папка, если файл лежит не в папке files/ ( например: folder/name/ )
	 *                      - int **deid** - id документа
	 *
	 * @return mixed - имя нового файла
	 *
	 * ```php
	 * $params = [
	 *     "file"        => $file,
	 *     "name"        => "Билет",
	 *     "disposition" => "/_test/output/"
	 * ];
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> doc2PDF($id, $params);
	 * ```
	 */
	public function doc2PDF(int $fid = 0, array $params = []) {

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$identity      = $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;

		$file        = $params['file'];
		$disposition = $params['disposition'];
		//$name        = $params['name'];
		$deid = $params['deid'] + 0;

		$file = ($fid > 0) ? $db -> getOne( "SELECT fname FROM {$sqlname}file WHERE fid = '$fid' AND identity = '$identity'" ) : $file;

		//преобразуем имя файла в pdf
		$newfile = str_replace( getExtention( $file ), "pdf", $file );

		//$ext = getExtention($file);

		//print PHP_OS_FAMILY."\n";
		//print strpos( PHP_OS_FAMILY, "WIN" )."\n";

		//для Windows
		if ( PHP_OS_FAMILY == 'Windows' ) {

			//print PHP_OS;

			$dumper = '';
			$litera = $rootpath[0];

			if ( file_exists( $litera.":\\OpenServer\\tools\\OfficeToPdf\\OfficeToPDF.exe" ) ) {
				$dumper = $litera.":\\OpenServer\\tools";
			}
			elseif ( file_exists( $litera.":\\SalesmanServer\\tools\\OfficeToPdf\\OfficeToPDF.exe" ) ) {
				$dumper = $litera.":\\SalesmanServer\\tools";
			}
			elseif ( file_exists( $litera.":\\tools\\OfficeToPdf\\OfficeToPDF.exe" ) ) {
				$dumper = $litera.":\\tools";
			}

			//$path = str_replace('/', "\\", $rootpath."\\files\\".str_replace('/', "\\", $fpath));

			$path = ($disposition == '') ? str_replace( '/', "\\", $rootpath."\\files\\".str_replace( '/', "\\", $fpath ) ) : str_replace( '/', "\\", $rootpath."\\".$disposition."\\".str_replace( '/', "\\", $fpath ) );

			//print $dumper.'\\OfficeToPdf\\OfficeToPDF.exe /print '.$path.$file.' '.$path.$newfile;

			//exec( $dumper.'\\OfficeToPdf\\OfficeToPDF.exe /print '.$path.$file.' '.$path.$newfile, $output, $exit );

			$cmd = 'soffice --headless --convert-to pdf '.$path.$file.' --outdir '.$path;

			//print $cmd;

		}
		else {

			$path = ($disposition == '') ? $rootpath."/files/".$fpath : $rootpath."/".$disposition."/".$fpath;

			$cmd = 'export HOME='.$path.' && libreoffice --headless --convert-to pdf '.$path.$file.' --outdir '.$path;

			//print $cmd;

			//if(!empty($output)) print_r($output);
			//if($exit) print $exit;

		}

		//print "cmd = ".$cmd."\n";
		//print_r($output);
		//print_r($exit);

		exec( $cmd, $output, $exit );
		$this -> rezult = $output;

		//добавим к договору новый файл
		if ( $deid > 0 && file_exists( $path.$newfile ) ) {

			$fe     = $db -> getRow( "SELECT fname, ftitle FROM {$sqlname}contract WHERE deid = '$deid' AND identity = '$identity'" );
			$ftitle = explode( ";", $fe['ftitle'] );
			$fname  = explode( ";", $fe['fname'] );

			$fname1 = $ftitle1 = [];

			foreach ( $fname as $k => $vfile ) {

				$fname1[]  = $fname[ $k ];
				$ftitle1[] = $ftitle[ $k ];

				if ( $vfile == $file ) {

					$fname1[]  = $newfile;
					$ftitle1[] = trim( str_replace( getExtention( $ftitle[ $k ] ), "pdf", $ftitle[ $k ] ) );

				}

			}

			$db -> query( "UPDATE {$sqlname}contract SET ftitle = '".implode( ";", $ftitle1 )."', fname = '".implode( ";", $fname1 )."' WHERE deid = '$deid' and identity = '$identity'" );

		}

		return $newfile;

	}

	/**
	 * Позволяет получить ссылку на файл
	 *
	 * @param integer $id - id (deid) документа
	 *
	 * @return mixed
	 * ```php
	 * [ result ] => Успешно
	 * [ data ] => (
	 *      [ 0 ] => (
	 *          [ name ] => Договор №47-1119/2019 от 07.11.2019.docx
	 *          [ file ] => 1573125910.docx
	 *          [ type ] => docx
	 *      )
	 * )
	 * ```
	 * ```php
	 * [ result ] => Error
	 * [ error ] => (
	 *          [ code ] => 404
	 *          [ text ] => Документ не найден
	 * )
	 * ```
	 */
	public function getFiles(int $id = 0) {

		$deid = $id;

		if ( $id > 0 ) {

			$response = self ::info( $deid );

			//print_r($response);

			if ( $response['result'] != 'Error' ) {

				$files = $response['files'];

				$response = [
					'result' => 'Успешно',
					'data' => $files
				];

			}
			else {

				$response = [
					'result' => 'Error',
					'error' => [
						'code' => $response['error']['code'],
						'text' => $response['error']['text']
					]
				];

			}

		}
		else {

			$response = [
				'result' => 'Error',
				'error' => [
					'code' => '406',
					'text' => "Отсутствуют параметры"
				]
			];

		}

		return $response;

	}

	/**
	 * Позволяет отправить документ по Email
	 *
	 * @param int   $id     - id доккмента
	 * @param array $params - массив данных
	 *                     - int **did** - не обязательный параметр, если документ привязан к сделке
	 *                     - int **status** - id статуса документа, который требуется присвоить
	 *                     - str **email** - сторонний email, не принадлежащий Клиенту
	 *                     - str **theme** - тема сообщения
	 *                     - str **content** - содержимое сообщения (если требуется изменить стандартное)
	 *                     - str **template** - id файла шаблона документа (если требуется генерировать новый)
	 *                     - str **pdf** - требуется ли конвертировать документ в PDF (yes/no)
	 * @param bool  $auto   - автоматически найти получателя (если не указан в $params)
	 *
	 * @return mixed
	 * @throws Exception
	 *
	 * ```php
	 * $rez  = new Salesman\Document();
	 * $data = $rez -> mail($id, $params);
	 * ```
	 */
	public function mail(int $id = 0, array $params = [], bool $auto = false) {

		global $hooks;

		$rootpath      = $this -> rootpath;

		$post = $params;

		$params = $hooks -> apply_filters( "document_send_filter", $params );

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$iduser1  = ($params['iduser'] > 0) ? $params['iduser'] : $GLOBALS['iduser1'];
		$db       = $GLOBALS['db'];
		$fpath    = $GLOBALS['fpath'];

		$did    = $params['did'] + 0;
		$status = $params['status'];

		$email   = $params[ 'email' ] ?? [];
		$theme   = $params['theme'];
		$content = str_replace( "\\r\\n", "<br>", $params['content'] );
		$CC      = (array)$params['cc'];
		$BCC     = (array)$params['bcc'];

		$document = self ::info( $id );

		/**
		 * Если тема и/или содержание отсутствует
		 */
		if ( $theme == '' ) {

			$theme = $document['title'].' на согласование';

		}
		if ( $content == '' ) {

			$content = '
			Приветствую, {{person}}
			
			Отправляю Вам Документ: {{docTitle}} на согласование.
			
			Спасибо за внимание.
			
			С уважением,
			{{mName}}
			Тел.: {{mPhone}}
			Email.: {{mMail}}
			==============================
			{{mCompany}}';

		}

		$oldstatus = $document['status'];

		//print_r($document);

		if ( $did == 0 ) {
			$did = $document['did'] + 0;
		}

		/**
		 * Если массив $email не указан (например, метод вызван из API)
		 * Массив $email должен иметь формат массива = ['clid:32333','pid:3455','pid:555']
		 * то постараемся его сформировать
		 */
		$params['clid'] = $document['clid'];
		$ppid  = getDogData( $did, 'pid_list' );

		$params['pid'] = (is_array( $params['pid'] )) ? $params['pid'] : (array)yexplode( ";", (string)$ppid );

		//если в запросе отсутствуют pid, то берем из сделки
		if ( !isset( $params['pid'] ) || empty( $params['pid'] ) ) {
			$params['pid'] = (is_array( $params['pid'] )) ? $params['pid'] : (array)yexplode( ";", (string)getDogData( $params['did'], "pid_list" ) );
		}

		//если в сделке не прикреплены контакты, то берем основной контакт
		if ( empty( $params['pid'] ) ) {
			$params['pid'][] = getClientData( $params['clid'], "pid" );
		}

		//print_r($params);
		//exit(0);

		/**
		 * Если массив $email не указан (например, метод вызван из API)
		 * Массив $email должен иметь формат массива = ['clid:32333','pid:3455','pid:555']
		 * то постараемся его сформировать
		 */
		if ( empty( $email ) && (!empty( $params['pid'] ) || $params['clid'] > 0) && $auto ) {

			if ( !empty( $params['pid'] ) ) {

				foreach ( $params['pid'] as $pid ) {
					$email[] = "pid:".$pid;
				}

			}

			if ( $params['clid'] > 0 ) {
				$email[] = "clid:".$params['clid'];
			}

		}


		start:

		if ( !empty( $email ) ) {

			/**
			 * Данные договора
			 */
			$document = self ::info( $id );

			$mcid = $document['mcid'];

			//print_r($document);

			if ( $document['result'] != 'Error' ) {

				$mes = $files = $des = $ifiles = [];

				/**
				 * Если вложений нет и указан шаблон
				 */
				if ( empty( $document['files'] ) && $params['template'] > 0 ) {

					$arg = [
						"template" => $params['template'],
						"append"   => true,
						"getPDF"   => ($params['pdf']) ? "yes" : "no"
					];

					$doc = new self();
					$doc -> generate( $id, $arg );

					//начинаем заново
					goto start;

				}

				// если вложения есть
				if ( !empty( $document['files'] ) /*&& $params['template'] > 0*/ ) {

					/**
					 * Пересортируем массив вложений
					 */
					foreach ( $document['files'] as $file ) {

						$ifiles[ $file['type'] ] = [
							"name" => str_replace( "№", "", $file['name'] ),
							"file" => $file['file']
						];

					}

					//print_r($ifiles);

					/**
					 * Если требуется отправить PDF-документ
					 */
					if ( $params['pdf'] == 'yes' ) {

						/**
						 * Если файл не сгенерирован
						 */
						if ( empty( $ifiles['pdf'] ) || !file_exists( $rootpath."/files/".$fpath.$ifiles['pdf']['file'] ) ) {

							$params = [
								"file" => $ifiles['docx']['file'],
								"name" => $ifiles['docx']['name'],
								"deid" => $id
							];

							$doc = new self();
							$doc -> doc2PDF( 0, $params );

							//print_r($r);

							$files = [
								"name" => str_replace( "docx", "pdf", $ifiles['docx']['name'] ),
								"file" => str_replace( "docx", "pdf", $ifiles['docx']['file'] )
							];

							//начинаем заново
							//goto start;

						}
						else {
							$files = $ifiles['pdf'];
						}

					}
					else {

						/**
						 * если файл не найден, то сгенерируем его
						 */
						if ( !file_exists( $rootpath."/files/".$fpath.$ifiles['docx']['file'] ) ) {

							$arg = [
								"template" => $params['template'],
								"append"   => true,
								"getPDF"   => "yes"
							];

							$doc = new self();
							$doc -> generate( $id, $arg );

							$files = [
								"name" => str_replace( "№", "#", str_replace( "docx", "pdf", $ifiles['docx']['name'] ) ),
								"file" => str_replace( "docx", "pdf", $ifiles['docx']['file'] )
							];

							//начинаем заново
							//goto start;

						}
						else {
							$files = $ifiles['docx'];
						}

					}

					//print_r($files);

					//найдем данные сотрудника
					$u      = $db -> getRow( "select * from {$sqlname}user where iduser = '$iduser1' and identity = '$identity'" );
					$mMail  = $u["email"];
					$mName  = $u["title"];
					$mPhone = $u["phone"];

					$mCompany = $db -> getOne( "SELECT name_shot FROM {$sqlname}mycomps WHERE id = '$mcid'" );

					$docTitle = $document['title']." №".$document['number'];

					//$tags = array("mName" => $mName, "mMail" => $mMail, "mPhone" => $mPhone, "mCompany" => $mCompany);
					//print_r($tags);

					/**
					 * Формируем тело сообщения
					 */
					$content = str_replace( [
						"{{mName}}",
						"{{mMail}}",
						"{{mPhone}}",
						"{{mCompany}}",
						"{{docTitle}}"
					], [
						$mName,
						$mMail,
						$mPhone,
						$mCompany,
						$docTitle
					], $content );

					/**
					 * Формируем список получателей
					 */
					$toName = '';
					$toMail = '';
					//$CC     = [];

					//print_r($email);

					//$email = array();

					if ( !empty( $email ) ) {

						foreach ( $email as $mail ) {

							$inName = $inMail = '';

							$mail = explode( ":", $mail );

							if ( $mail[0] == 'pid' ) {

								$inName = getPersonData( $mail[1], 'person' );
								$array  = explode( ",", str_replace( ";", ",", str_replace( " ", "", getPersonData( $mail[ 1 ], 'mail' ) ) ) );
								$inMail = array_shift( $array );

							}
							if ( $mail[0] == 'clid' ) {

								$params['clid'] = $mail[1];

								$inName = getClientData( $mail[1], 'title' );
								$array1 = explode( ",", str_replace( ";", ",", str_replace( " ", "", getClientData( $mail[ 1 ], 'mail_url' ) ) ) );
								$inMail = array_shift( $array1 );

							}

							//если основной отправитель не указан, то указываем
							if ( $toName == '' ) {

								$toName = $inName;
								$toMail = $inMail;

							}
							//иначе добавляем в копию
							else {
								$CC[] = [
									"name"  => $inName,
									"email" => $inMail
								];
							}

						}

						$html = nl2br( str_replace( "{{person}}", $toName, $content ) );

						$afile[] = $files;
						
						// возможность вложить оригинальный word-документ
						if($params['docInclude']){
							
							$afile[] = [
								"name" => $ifiles['docx']['name'],
								"file" => $ifiles['docx']['file']
							];
							
						}

						$rez = mailto( [
							"to" => $toMail,
							"toname"   => $toName,
							"from"     => $mMail,
							"fromname" => $mName,
							"subject"  => $theme,
							"html"     => $html,
							"files"    => $afile,
							"cc"       => $CC,
							"bcc"      => $BCC
						] );

						if ( $rez == '' ) {
							$des[] = "Отправлен Документ на Email: $toMail на имя $toName. ".(!empty( $CC ) ? "Копия отправлена: ".yimplode( ", ", arraySubSearch( $CC, 'name' ) ) : "")."\n\nТема: $theme.\n\nТекст сообщения:\n$html";
						}

						if ( $rez != '' ) {

							$mes[] = 'Выполнено с ошибками. '.$rez;

							$msg = yimplode( "; ", $mes );

							$response['result']        = "Error";
							$response['error']['code'] = '407';
							$response['error']['text'] = "Не найден ни один получатель";

							$response['data']   = $id;
							$response['number'] = $document['number'];
							$response['text']   = $msg;

						}
						else {

							$mes[] = "Сделано";

							//добавим в историю
							addHistorty( [
								"iduser"   => $iduser1,
								"clid"     => $params['clid'],
								"did"      => $did,
								"datum"    => current_datumtime(),
								"des"      => implode( "<br>", $des ),
								"tip"      => 'Исх.Почта',
								"identity" => $identity
							] );

							//обновим статус
							if ( isset( $params['status'] ) ) {

								$data = [
									'status'    => ($status > 0) ? $status : '0',
									'oldstatus' => $oldstatus,
									'user'      => $iduser1,
									'statusdes' => "Отправлено по email",
									"subaction" => 'status'
								];

								//обновляем документ
								//require_once $rootpath."/modules/contract/Docgen.php";

								$rez = new self();
								$rez -> edit( $id, $data );

							}

							$msg = yimplode( "<br>", $mes );

							$response['result'] = 'Успешно';
							$response['data']   = $id;
							$response['text']   = $msg;

						}

					}
					else {

						$response['result']        = 'Error';
						$response['error']['code'] = '407';
						$response['error']['text'] = "Не найден ни один получатель";

					}

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '406';
					$response['error']['text'] = "Нечего отправлять. Попробуйте указать id шаблона";

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = $document['error']['code'];
				$response['error']['text'] = $document['error']['text'];

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '407';
			$response['error']['text'] = "Не найден ни один получатель";

		}

		return $response;

	}

	/**
	 * Генерация QR-кода в виде строки
	 *
	 * @param array  $tags   - compUrName, compBankRs, compBankName, compBankBik, compBankKs, compInn, compKpp
	 *                       InvoiceSumma, Invoice, InvoiceDate, nalogName, nalogSumma
	 * @param string $file   - путь к файлу относительно корня CRM (если не указано, то будет "/cash/qrcode-invoice.png")
	 * @return string - полный путь к сгенерированному файлу
	 */
	public static function getQR(array $tags, string $file = ''): string {

		$rootpath = dirname( __DIR__, 2 );

		if(!isset($file) || $file == ''){
			$file = "/cash/qrcode-invoice.png";
		}

		// qrcode
		$renderer = new ImageRenderer( new RendererStyle( 400 ), new ImagickImageBackEnd() );
		$writer   = new Writer( $renderer );

		$kpp = $tags[ 'compKpp' ] == '0' ? "" : $tags[ 'compKpp' ];
		
		//print $rootpath.$file."\n";
		
		$content = "ST00012|Name=".trim( str_replace( "”", "\"", $tags[ 'compUrName' ] ) )."|PersonalAcc=".trim( $tags[ 'compBankRs' ] )."|BankName=".trim( str_replace( "”", "\"", $tags[ 'compBankName' ] ) )."|BIC=".trim( $tags[ 'compBankBik' ] )."|CorrespAcc=".trim( $tags[ 'compBankKs' ] )."|PayeeINN=".trim( $tags[ 'compInn' ] )."|KPP=".trim( $kpp )."|Sum=".(pre_format($tags[ 'InvoiceSumma' ]) * 100)."|Purpose=".trim( str_replace( "”", "\"", "Оплата счета _№".$tags[ 'Invoice' ]." от ".$tags[ 'InvoiceDate' ]." ".$tags[ 'nalogName' ]." ".$tags[ 'nalogSumma' ] ) );

		$writer -> writeFile( $content, $rootpath.$file, "UTF-8" );

		return $rootpath.$file;

	}

}