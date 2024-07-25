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

use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Mustache_Autoloader;
use Mustache_Engine;
use SafeMySQL;

/**
 * Класс для управления актами
 *
 * Class Akt
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example:
 * ```php
 * $Akt  = new Salesman\Akt();
 * $result = $Akt -> edit($id, $params);
 * $response = [
 * 'result'  => 'Успешно',
 * 'akt_num' => $akt_num,
 * 'deid'    => $deid,
 * 'did'     => $did,
 * 'crid'    => $crid2,
 * 'text'    => yimplode("; ", $mes),
 * 'error'   => ['text' => $err]
 * ];
 * ```
 */
class Akt {

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

			createDir( $pathTo );

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
	 * Получение информации по акту
	 *
	 * @param integer $id - идентификатор записи акта
	 *
	 * @return array - массив данныых по акту
	 *
	 * Пример:
	 *
	 * ```php
	 * $Akt = \Salesman\Akt::info($id);
	 * ```
	 *
	 */
	public static function info(int $id): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$re = $db -> getRow( "SELECT * FROM {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );

		$type = $db -> getRow( "SELECT title, type FROM {$sqlname}contract_type where id = '$re[idtype]' and identity = '$identity'" );

		$akt = [
			'deid'        => (int)$re["deid"],
			'number'      => $re["number"],
			'title'       => $re["title"],
			'datum'       => $re["datum"],
			'datum_start' => $re["datum_start"],
			'datum_end'   => $re["datum_end"],
			'des'         => $re["des"],
			'did'         => (int)$re["did"],
			'clid'        => (int)$re["clid"],
			'payer'       => (int)$re["payer"],
			'pid'         => (int)$re["pid"],
			'idtype'      => $re["idtype"],
			'type'        => $type['type'],
			'typeTitle'   => $type['title'],
			'crid'        => (int)$re["crid"],
			'mcid'        => (int)$re["mcid"],
			'iduser'      => (int)$re["iduser"],
			'status'      => $re["status"],
			'template'    => self ::getTemplates( NULL, str_replace( "htm", "tpl", $re["title"] ) ),
			"poz"         => []
		];

		// список позиций, прикрепленных к спецификации
		$re = $db -> query( "SELECT * FROM {$sqlname}contract_poz WHERE deid = '$id' and identity = '$identity'" );
		while ($da = $db -> fetch( $re )) {

			$akt['poz'][ $da['spid'] ] = [
				"id"   => (int)$da['id'],
				"spid" => (int)$da['spid'],
				"prid" => (int)$da['prid'],
				"kol"  => (float)$da['kol']
			];

		}

		return $akt;

	}

	/**
	 * Шаблоны документов
	 *
	 * @param null $id
	 * @param null $file
	 *
	 * @return array
	 *      - int **id** - id шаблона
	 *      - str **title** - Название шаблона
	 *      - str **file** - Файл шаблона
	 *      - int **typeid** - id типа шаблона
	 *
	 * Примечание:
	 *  - если $id и $file не указаны, то возвращается список шаблонов
	 *  - если указан $id - возвращает данные по названию файла шаблона
	 *  - если указан $file - возвращает данные по id шаблона
	 */
	public static function getTemplates($id = NULL, $file = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$result = [];

		if ( !$id && !$file ) {

			$ires = $db -> query( "SELECT * FROM {$sqlname}contract_temp WHERE typeid IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
			while ($data = $db -> fetch( $ires )) {

				$result[ $data['id'] ] = [
					"id"     => (int)$data['id'],
					"title"  => $data['title'],
					"file"   => $data['file'],
					"typeid" => (int)$data['typeid'],
				];

			}

		}
		elseif ( $file ) {

			$data   = $db -> getRow( "SELECT * FROM {$sqlname}contract_temp WHERE file = '$file' AND identity = '$identity' ORDER by title" );
			$result = [
				"id"     => (int)$data['id'],
				"title"  => $data['title'],
				"file"   => $data['file'],
				"typeid" => (int)$data['typeid'],
			];

		}
		else {

			$data   = $db -> getRow( "SELECT * FROM {$sqlname}contract_temp WHERE id = '$id' AND identity = '$identity' ORDER by title" );
			$result = [
				"id"     => (int)$data['id'],
				"title"  => $data['title'],
				"file"   => $data['file'],
				"typeid" => (int)$data['typeid'],
			];

		}

		return $result;

	}

	/**
	 * Массив позиций по акту и свободнях позиций
	 *
	 * @param int $id  - id акта
	 * @param int $did - id сделки
	 *
	 * @return array
	 */
	public static function getPozition(int $id = 0, int $did = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$list  = $spekaAkt = $spekaAktAll = /*$spekaAktOther =*/ [];
		$count = 0;

		if ( $did == 0 ) {
			$did = (int)$db -> getOne( "SELECT did FROM {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );
		}

		// массив позиций, которые уже есть в базе ко всем актам
		$r = $db -> getAll( "SELECT * FROM {$sqlname}contract_poz WHERE did = '$did' and identity = '$identity'" );
		foreach ( $r as $item ) {
			$spekaAktAll[ $item['spid'] ][] = (float)$item['kol'];
		}

		if ( $id > 0 ) {

			// данные по акту
			$akt = self ::info( $id );

			// данные по позициям в акте
			$spekaAkt = $akt['poz'];

			// не понятно, что это
			/*$r = $db -> getAll( "SELECT * FROM {$sqlname}contract_poz WHERE did = '$did' and deid != '$id' and identity = '$identity'" );
			foreach ( $r as $item ) {

				$spekaAktOther[ $item['spid'] ][] += (float)$item['kol'];

			}*/

		}

		// данные по спеке
		$spekaFull = (new Speka()) -> getSpekaData( $did );

		//print_r($spekaAkt);
		//print_r($spekaFull['pozition']);

		// если к этой спецификации еще не привязаны позиции
		// то выводим все еще не отгруженные
		if ( empty( $spekaAkt ) ) {

			// если еще не определены, то возвращаем всю спеку
			foreach ( (array)$spekaFull['pozition'] as $spid => $item ) {

				if ( $id == 0 ) {
					$kolOtherThis = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE spid = '$spid' and did = '$did' and identity = '$identity'" ) + 0;
				}
				else {
					$kolOtherThis = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE spid = '$spid' and did = '$did' and deid != '$id' and identity = '$identity'" ) + 0;
				}

				$kol = $item['kol'] - $kolOtherThis;

				if ( $kol > 0 ) {

					$list[] = [
						"id"         => 0,
						"spid"       => (int)$spid,
						"prid"       => (int)$item['prid'],
						"artikul"    => $item['artikul'],
						"title"      => $item['title'],
						"tip"        => $item['tip'],
						"comments"   => $item['comments'],
						"kol"        => num_format( $kol ),
						"ekol"       => 0,
						"dop"        => $item['dop'],
						"edizm"      => $item['edizm'],
						"price"      => $item['price'],
						"price_in"   => $item['price_in'],
						"nds"        => $item['nds'],
						"summa"      => $item['summa'],
						"summaZakup" => $item['summaZakup'],
						"inPrice"    => $item['inPrice'],
						"inAkt"      => true
						// делаем позиции отмеченными
					];

					$count++;

				}

			}

		}

		// иначе выводим позиции по этому акту
		else {

			foreach ( $spekaAkt as $spid => $item ) {

				$kolOtherThis = (float)$db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE spid = '$spid' and did = '$did' and deid != '$id' and identity = '$identity'" );

				$kol = (float)$spekaFull['pozition'][ $spid ]['kol'] - (float)$item['kol'] - $kolOtherThis;

				$list[] = [
					"id"         => (int)$item['id'],
					"spid"       => (int)$item['spid'],
					"prid"       => (int)$item['prid'],
					"artikul"    => $spekaFull['pozition'][ $spid ]['artikul'],
					"title"      => $spekaFull['pozition'][ $spid ]['title'],
					"tip"        => $spekaFull['pozition'][ $spid ]['tip'],
					"comments"   => $spekaFull['pozition'][ $spid ]['comments'],
					"kol"        => num_format( $item['kol'] ),
					"ekol"       => $kol,
					"dop"        => $spekaFull['pozition'][ $spid ]['dop'],
					"edizm"      => $spekaFull['pozition'][ $spid ]['edizm'],
					"price"      => $spekaFull['pozition'][ $spid ]['price'],
					"price_in"   => $spekaFull['pozition'][ $spid ]['price_in'],
					"nds"        => $spekaFull['pozition'][ $spid ]['nds'] * ($item['kol'] / $spekaFull['pozition'][ $spid ]['kol']),
					"summa"      => $spekaFull['pozition'][ $spid ]['summa'] * ($item['kol'] / $spekaFull['pozition'][ $spid ]['kol']),
					"summaZakup" => $spekaFull['pozition'][ $spid ]['summaZakup'] * ($item['kol'] / $spekaFull['pozition'][ $spid ]['kol']),
					"inPrice"    => $spekaFull['pozition'][ $spid ]['inPrice'],
					"inAkt"      => true
				];

				$count++;

			}

			// добавим позиции, которых нет в других актах и в текущем ( если частичное количество )
			foreach ( $spekaFull['pozition'] as $spid => $item ) {

				// количество позиций в других актах
				if ( $id == 0 ) {
					$kolOtherThis = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE spid = '$spid' and did = '$did' and identity = '$identity'" );
				}
				else {
					$kolOtherThis = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE spid = '$spid' and did = '$did' and deid != '$id' and identity = '$identity'" );
				}

				//print $db -> lastQuery();
				//print $kolOtherThis;

				$ekol = $spekaFull['pozition'][ $spid ]['kol'] - $kolOtherThis;

				// позиции, которых нет в текущем акте
				if ( (!array_key_exists( $spid, $spekaAktAll ) || !array_key_exists( $spid, $spekaAkt )) && $ekol > 0 ) {

					$list[] = [
						"id"         => 0,
						"spid"       => (int)$spid,
						"prid"       => (int)$item['prid'],
						"artikul"    => $item['artikul'],
						"title"      => $item['title'],
						"tip"        => $item['tip'],
						"comments"   => $item['comments'],
						"kol"        => array_key_exists( $spid, $spekaAkt ) ? num_format( $item['kol'] ) : $ekol,
						"ekol"       => num_format( $ekol ),
						"dop"        => $item['dop'],
						"edizm"      => $item['edizm'],
						"price"      => $item['price'],
						"price_in"   => $item['price_in'],
						"nds"        => $item['nds'],
						"summa"      => $item['summa'],
						"summaZakup" => $item['summaZakup'],
						"inPrice"    => $item['inPrice']
					];

				}

			}

		}

		return [
			"list"       => $list,
			"count"      => $count,
			"countSpeka" => count( $spekaFull['pozition'] ),
			"deid"       => $id
		];

	}

	/**
	 * Комплектность спецификации актами
	 *
	 * @param $did - id сделки
	 *
	 * @return float|int
	 */
	public static function getAktComplect($did) {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];

		$percent = 0;

		$countAkt = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}contract WHERE did = '$did' and idtype IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('get_akt','get_aktper') AND identity = '$identity' ORDER BY title)" );

		if($countAkt == 0){
			return NULL;
		}

		$countAktPoz = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE did = '$did'" );
		$countSpeka  = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}speca WHERE did = '$did' AND tip != '2'" );

		//print $countAktPoz."<br>";
		//print $countSpeka."<br>";

		if ( $countAktPoz == 0 ) {
			$percent = 100;
		}
		elseif ( $countAktPoz == $countSpeka ) {
			$percent = 100;
		}
		elseif ( $countAktPoz < $countSpeka ) {
			$percent = $countAktPoz / $countSpeka * 100;
		}

		return $percent;

	}

	/**
	 * Возвращает спецификацию по акту в массиве
	 *
	 * @param $id - id акта
	 *
	 * @return array - массив с ответом
	 *
	 * - array **pozition** - массив позиций без фильтра по типу
	 *      - int **num** - номер по порядку
	 *      - int **spid** - id позиции в спецификации
	 *      - int **prid** - id позиции в прайсе
	 *      - str **artikul** - Артикул
	 *      - str **title** - Название
	 *      - int **tip** - Тип (0 - товар, 1 - услуга, 2 - материал)
	 *      - str **comments** - Комментарий
	 *      - float **kol** - Количество
	 *      - str **dop** - Дополнительное поле
	 *      - str **edizm** - Единица измерения
	 *      - float **price** - Цена за единицу
	 *      - float **price_in** - Цена закупочная за единицу
	 *      - float **nds** - НДС
	 *      - float **summa** - Сумма позиции
	 *      - float **summaZakup** - Сумма закупа позиции
	 *      - float **inPrice** - Прайсовая стоимость
	 * - float **pozitionNalog** - сумма налога
	 * - float **pozitionSumma** - сумма позиций ( без налога )
	 * - float **pozitionTotal** - сумма позиций ( с налогом )
	 *
	 * - array **tovar** - массив позиций с типом = товар
	 * - float **tovarNalog** - сумма налога товарных позиций
	 * - float **tovarSumma** - сумма товарных позиций ( без налога )
	 * - float **tovarTotal** - сумма товарных позиций ( с налогом )
	 *
	 * - array **usluga** - массив позиций с типом = услуга
	 * - float **uslugaNalog** - сумма налога позиций с услугами
	 * - float **uslugaSumma** - сумма позиций с услугами ( без налога )
	 * - float **uslugaTotal** - сумма позиций с услугами ( с налогом )
	 *
	 */
	public static function getAktSpeka($id): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity   = $GLOBALS['identity'];
		$sqlname    = $GLOBALS['sqlname'];
		$db         = $GLOBALS['db'];
		$ndsRaschet = $GLOBALS['ndsRaschet'];
		//$other      = $GLOBALS['other'];
		$fpath      = $GLOBALS['fpath'];

		$otherSettings = json_decode( file_get_contents( $rootpath."/cash/".$fpath."otherSettings.json" ), true );

		$spekaAkt = [];

		$i           = 0;
		$did         = (int)$db -> getOne( "SELECT did FROM {$sqlname}contract WHERE deid = '$id'" );
		$mcid        = (int)$db -> getOne( "SELECT mcid FROM {$sqlname}dogovor WHERE did = '$did'" );
		$nalogScheme = getNalogScheme( 0, $mcid );

		// позиции по акту ( частичная отгрузка )
		$r = $db -> getAll( "SELECT * FROM {$sqlname}contract_poz WHERE deid = '$id' and identity = '$identity'" );
		foreach ( $r as $item ) {
			$spekaAkt[ $item['spid'] ] += $item['kol'];
		}

		$summaInvoice  = $summaItog = 0;
		$pozition      = $tovar = $usluga = [];
		$pozitionSumma = $tovarSumma = $uslugaSumma = 0;
		$pozitionZakup = $tovarZakup = $uslugaZakup = 0;
		$pozitionNalog = $tovarNalog = $uslugaNalog = 0;

		$aktSumma = 0;

		$isper = isServices( $did );

		//print_r($spekaAkt);

		$result = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity' ORDER BY spid" );
		while ($data = $db -> fetch( $result )) {

			//если позиций вообще нет или такая позиция есть в позициях акта
			if ( empty( $spekaAkt ) || $spekaAkt[ $data['spid'] ] > 0 || $isper ) {

				if ( $spekaAkt[ $data['spid'] ] > 0 ) {
					$data['kol'] = $spekaAkt[ $data['spid'] ];
				}

				$s = '';

				if ( $data['prid'] > 0 ) {
					$s = " and n_id='".$data['prid']."'";
				}
				elseif ( $data['artikul'] != '' ) {
					$s = " and artikul='".$data['artikul']."'";
				}

				$priceIn = $db -> getOne( "SELECT price_in FROM {$sqlname}price WHERE n_id > 0 $s and identity = '$identity'" );

				if ( $data['tip'] != '2' ) {

					//если у компании Налог = 0, то она его не платит
					if ( $data['nds'] > 0 && $nalogScheme['nalog'] == 0 ) {
						$data['nds'] = 0;
					}

					//стоимость позиций спецификации (без учета материалов)
					$summaPoz = pre_format( $data['kol'] ) * pre_format( $data['price'] ) * pre_format( $data['dop'] );

					$pozitionZakup += $data['kol'] * $data['price_in'] * $data['dop'];
					$pozitionSumma += $summaPoz;

					$ndsa = getNalog( $summaPoz, $data['nds'], $ndsRaschet );//НДС на все количество
					$ndsi = getNalog( $data['price'], $data['nds'], $ndsRaschet );//НДС на 1 ед.изм.

					$pozitionNalog += $ndsa['nalog'];
					$ndsPoz        = $ndsi['nalog'];

					$summaInvoice += ($ndsRaschet == 'yes') ? $summaPoz + $ndsa['nalog'] : $summaPoz;

					$pozition[ $data['spid'] ] = [
						"num"        => $i,
						"prid"       => $data['prid'],
						"artikul"    => $data['artikul'],
						"title"      => $data['title'],
						"tip"        => $data['tip'],
						"comments"   => $data['comments'],
						"kol"        => $data['kol'],
						"dop"        => ($otherSettings['dop']) ? num_format( $data['dop'] ) : '',
						"edizm"      => $data['edizm'],
						"price"      => $data['price'],
						"price_in"   => $data['price_in'],
						"nds"        => ($nalogScheme['nalog'] != 0) ? $ndsPoz : '',
						"summa"      => $summaPoz,
						"summaZakup" => $pozitionZakup,
						"inPrice"    => $priceIn,
						"spid"       => $data['spid']
					];

					$aktSumma += $summaPoz;

					if ( $data['tip'] == '0' ) {

						//если у компании Налог = 0, то она его не платит
						if ( $data['nds'] > 0 && $nalogScheme['nalog'] == 0 ) {
							$data['nds'] = 0;
						}

						//стоимость товаров (всего количества)
						$summaPoz = pre_format( $data['kol'] ) * pre_format( $data['price'] ) * pre_format( $data['dop'] );

						$tovarZakup += $data['kol'] * $data['price_in'] * $data['dop'];
						$tovarSumma += $summaPoz;

						$ndsa = getNalog( $summaPoz, $data['nds'], $ndsRaschet );//НДС на все количество
						$ndsi = getNalog( $data['price'], $data['nds'], $ndsRaschet );//НДС на 1 ед.изм.

						$tovarNalog += $ndsa['nalog'];
						$ndsPoz     = $ndsi['nalog'];

						$tovar[] = [
							"num"        => $i,
							"prid"       => $data['prid'],
							"artikul"    => $data['artikul'],
							"title"      => $data['title'],
							"tip"        => $data['tip'],
							"comments"   => $data['comments'],
							"kol"        => num_format( $data['kol'] ),
							"dop"        => ($otherSettings['dop']) ? num_format( $data['dop'] ) : '',
							"edizm"      => $data['edizm'],
							"price"      => $data['price'],
							"price_in"   => $data['price_in'],
							"nds"        => ($nalogScheme['nalog'] != 0) ? $ndsPoz : '',
							"summa"      => num_format( $summaPoz ),
							"summaZakup" => $tovarZakup,
							"inPrice"    => $priceIn,
							"spid"       => $data['spid']
						];

					}
					else {

						//если у компании Налог = 0, то она его не платит
						if ( $data['nds'] > 0 && $nalogScheme['nalog'] == 0 ) {
							$data['nds'] = 0;
						}

						//стоимость услуг (всего количества)
						$summaPoz = pre_format( $data['kol'] ) * pre_format( $data['price'] ) * pre_format( $data['dop'] );

						$uslugaZakup += $data['kol'] * $data['price_in'] * $data['dop'];
						$uslugaSumma += $summaPoz;

						$ndsa = getNalog( $summaPoz, $data['nds'], $ndsRaschet );//НДС на все количество
						$ndsi = getNalog( $data['price'], $data['nds'], $ndsRaschet );//НДС на 1 ед.изм.

						$uslugaNalog += $ndsa['nalog'];
						$ndsPoz      = $ndsi['nalog'];

						$usluga[] = [
							"num"        => $i,
							"prid"       => $data['prid'],
							"artikul"    => $data['artikul'],
							"title"      => $data['title'],
							"tip"        => $data['tip'],
							"comments"   => $data['comments'],
							"kol"        => num_format( $data['kol'] ),
							"dop"        => ($otherSettings['dop']) ? num_format( $data['dop'] ) : '',
							"edizm"      => $data['edizm'],
							"price"      => $data['price'],
							"price_in"   => $data['price_in'],
							"nds"        => ($nalogScheme['nalog'] != 0) ? $ndsPoz : '',
							"summa"      => num_format( $summaPoz ),
							"summaZakup" => $uslugaZakup,
							"inPrice"    => $priceIn,
							"spid"       => $data['spid']
						];

					}

				}

				$i++;

			}

		}

		return [
			"pozition"      => $pozition,
			"pozitionNalog" => $pozitionNalog,
			"pozitionSumma" => $pozitionSumma,
			"pozitionTotal" => ($ndsRaschet == 'yes') ? ($pozitionSumma + $pozitionNalog) : $pozitionSumma,
			"tovar"         => $tovar,
			"tovarNalog"    => $tovarNalog,
			"tovarSumma"    => $tovarSumma,
			"tovarTotal"    => ($ndsRaschet == 'yes') ? ($tovarSumma + $tovarNalog) : $tovarSumma,
			"usluga"        => $usluga,
			"uslugaNalog"   => $uslugaNalog,
			"uslugaSumma"   => $uslugaSumma,
			"uslugaTotal"   => ($ndsRaschet == 'yes') ? ($uslugaSumma + $uslugaNalog) : $uslugaSumma,
			"aktSumma"      => $aktSumma,
			"aktSummaItog"  => $summaInvoice
		];

	}

	/**
	 * Процент, на который текущий акт закрывает спецификацию
	 *
	 * @param $id - id акта
	 *
	 * @return float|int
	 */
	public static function getComplect($id) {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];

		$percent = 0;
		$did     = $db -> getOne( "SELECT did FROM {$sqlname}contract WHERE deid = '$id'" );

		// количество актов по сделке
		$countAkt = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}contract WHERE did = '$did' and idtype IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('get_akt','get_aktper') AND identity = '$identity' ORDER BY title)" ) + 0;

		// количество в текущем акте
		$countAktPoz = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}contract_poz WHERE deid = '$id'" );

		// количество в спеке
		$countSpeka = $db -> getOne( "SELECT SUM(kol) FROM {$sqlname}speca WHERE did = '$did' AND tip != '2'" );

		if ( $countAktPoz == 0 ) {
			$percent = 100;
		}
		elseif ( $countAktPoz == $countSpeka ) {
			$percent = 100;
		}
		elseif ( $countAktPoz < $countSpeka ) {
			$percent = $countAktPoz / $countSpeka * 100;
		}

		return $percent;

	}

	/**
	 * Вывод списка документов
	 * @param array $params
	 *  - page - страница
	 *  - ord - сортировка
	 *  - tuda - направление сортировки (desc||asc)
	 *  - mc - id компании
	 *  - array status - статусы документа
	 *  - word - строка поиска
	 *  - isService - тип сделки - сервисная||обычная (yes||no)
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
		$isService  = $params['isService'];

		$iduser1    = $GLOBALS['iduser1'];
		$valuta     = $GLOBALS['valuta'];
		$isMobile   = $GLOBALS['isMobile'];

		$statuses = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE identity = '$identity'");

		$mycomps = Guides ::myComps();

		$lines_per_page = 100; //Стоимость записей на страницу

		// фильтр по дате акта
		//$sort .= ($params['d1'] != '') ? " AND ( DATE(ct.datum) >= '$params[d1]' AND DATE(ct.datum) <= '$params[d2]' )" : "";

		if ($params['d1'] != '' && $params['d2'] == '') {
			$sort .= " ct.datum >= '".$params['d1']."' AND ";
		}
		if ($params['d1'] != '' && $params['d2'] != '') {
			$sort .= " ( DATE(ct.datum) >= '$params[d1]' AND DATE(ct.datum) <= '$params[d2]' ) AND ";
		}
		if ($params['d1'] == '' && $params['d2'] != '') {
			$sort .= " ct.datum <= '".$params['d2']."' AND ";
		}

		if (!empty($status)) {
			$sort .= " ct.status IN (".implode(",", $status).") AND ";
		}

		if ($word != "") {
			$sort .= " (ct.number = '$word' OR crd.invoice = '$word' OR clt.title LIKE '%$word%' OR dg.title LIKE '%$word%') AND ";
		}

		if ( (int)$params['iduser'] > 0 ) {
			$sort .= " ct.iduser = '$params[iduser]' AND ";
		}
		else{
			$sort .= " ct.iduser IN (".yimplode(",", get_people($iduser1, "yes")).") AND ";
		}

		if ($isService == 'yes') {
			$sort .= " ct.crid > 0 AND ";
		}
		elseif ($isService == 'no') {
			$sort .= " ct.crid = 0 AND ";
		}

		if ( (int)$params['mc'] > 0 ) {
			$sort .= " dg.mcid = '$params[mc]' AND";
		}

		if ( (int)$params['clid'] > 0 ) {
			$sort .= " (ct.clid = '$params[clid]' OR dg.clid = '$params[clid]') AND ";
		}

		$query = "
		SELECT 
			COUNT(*)
		FROM {$sqlname}contract `ct`
			LEFT JOIN {$sqlname}personcat ON ct.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat `clt` ON ct.clid = clt.clid
			LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
			LEFT JOIN {$sqlname}contract_type `tp` ON ct.idtype = tp.id
			LEFT JOIN {$sqlname}credit `crd` ON ct.crid = crd.crid
			LEFT JOIN {$sqlname}contract_status ON {$sqlname}contract_status.id = ct.status
		WHERE 
			tp.type IN ('get_akt','get_aktper') AND
			$sort 
			ct.identity = '$identity'
		";

		$all_lines = $db -> getOne($query);

		if (empty($page) || $page <= 0) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}
		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		if (empty($page) || $page == 0) {
			$page = 1;
		}

		if ($ord == 'number') {
			$ordd = " (ct.number -1)";
		}
		elseif ($ord == 'invoice') {
			$ordd = " (crd.invoice)";
		}
		elseif ($ord == 'summa') {
			$ordd = " summa";
		}
		else {
			$ordd = "ct.$ord";
		}

		$query = "
		SELECT 
			ct.deid,
			DATE_FORMAT(ct.datum, '%d.%m.%Y') as datum,
			DATE_FORMAT(ct.datum_start, '%d.%m.%Y') as datum_start,
			DATE_FORMAT(ct.datum_end, '%d.%m.%Y') as datum_end,
			ct.number,
			ct.clid,
			ct.pid,
			ct.did,
			ct.payer,
			ct.iduser,
			ct.idtype,
			ct.signer,
			tp.type,
			crd.summa_credit,
			crd.invoice,
			clt.title as client,
			pc.person as person,
			dg.title as deal,
			dg.kol as kol,
			dg.mcid as mc,
			us.title as user,
			IF(tp.type = 'get_akt', dg.kol, crd.summa_credit) as summa,
			st.title as status,
			st.color as color
		FROM {$sqlname}contract `ct`
			LEFT JOIN {$sqlname}user `us` ON ct.iduser = us.iduser
			LEFT JOIN {$sqlname}personcat `pc` ON ct.pid = pc.pid
			LEFT JOIN {$sqlname}clientcat `clt` ON ct.clid = clt.clid
			LEFT JOIN {$sqlname}dogovor `dg` ON ct.did = dg.did
			LEFT JOIN {$sqlname}contract_type `tp` ON ct.idtype = tp.id
			LEFT JOIN {$sqlname}credit `crd` ON ct.crid = crd.crid
			LEFT JOIN {$sqlname}contract_status `st` ON st.id = ct.status
		WHERE 
			tp.type IN ('get_akt','get_aktper') AND 
			$sort
			ct.identity = '$identity'
		";

		//print
		$query = "$query ORDER BY $ordd $tuda LIMIT $lpos,$lines_per_page";

		$result      = $db -> query($query);
		$count_pages = ceil($all_lines / $lines_per_page);

		while ($da = $db -> fetch($result)) {

			$invoice = [];

			//статусы, применимые к текущему типу документоа
			$stat = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE({$sqlname}contract_status.tip, ';',',')) > 0 AND identity = '$identity'");

			$aktComplect = self ::getComplect($da['deid']);

			//$isper = (bool)isServices($da['did']);

			if( empty($da['invoice']) ){

				$invoice = $db -> getCol("SELECT invoice FROM {$sqlname}credit WHERE did = '$da[did]'");
				$da['invoice'] = yimplode("; ", $invoice);

			}

			$list[] = [
				"id"          => (int)$da['deid'],
				"datum"       => $da['datum'],
				"datum_start" => $da['datum_start'],
				"datum_end"   => $da['datum_end'],
				"number"      => $da['number'],
				"summaf"      => num_format($da['summa']),
				"summa"       => (float)$da['summa'],
				"title"       => $da['title'],
				"clid"        => (int)$da['clid'],
				"client"      => $da['client'],
				"pid"         => (int)$da['pid'],
				"person"      => $da['person'],
				"did"         => (int)$da['did'],
				"deal"        => $da['deal'],
				"payerid"     => (int)$da['payer'],
				"payer"       => current_client($da['payer']),
				"crid"        => (int)$da['crid'],
				"invoice"     => $da['invoice'],
				"invoices"    => $invoice,
				"user"        => $da['user'],
				"statuson"    => ( $stat > 0 ) ? "1" : "",
				"status"      => $da['status'],
				"statusTitle" => ( $da['status'] != '' ) ? $da['status'] : "--",
				"statusColor" => ( $da['status'] != '' ) ? $da['color'] : "#fff",
				"mc"          => $mycomps[$da['mc']],
				"mcid"        => (int)$da['mc'],
				"complect"    => !(bool)isServices((int)$da['did']) ? round($aktComplect + 0.1, 0) : 100,
				"isServices"  => !(bool)isServices((int)$da['did']) ? NULL : true,
			];

		}

		return [
			"list"     => $list,
			"page"     => (int)$page,
			"pageall"  => (int)$count_pages,
			"ord"      => $ord,
			"tuda"     => $tuda,
			"valuta"   => $valuta,
			"isstatus" => ( $statuses > 0 ) ? "1" : "",
			"count"    => count($list),
			"isMobile" => $isMobile
		];

	}

	/**
	 * Добавление / изменение акта
	 * Обновляет только указанные в массиве $params поля
	 *
	 * @param int   $id     - идентификатор записи акта
	 * @param array $params - массив данных для редактирования
	 *
	 *      - **iduser** - id пользователя
	 *      - **did** - id сделки
	 *      - **akt_num** - номер акта ( если не указан, то генерируем автоматически )
	 *      - **igen** = yes - если нужно сгенерировать номер акта
	 *      - **temp** - шаблон акта
	 *      - **status** - статус документа
	 *      - **des** - комментарий
	 *      - **newstep** - id нового этапа, если надо сменить этап сделки
	 *      - **rs** - id расчетного счета, если нужно выставить новый счет ( Сервисная сделка )
	 *      - **summa** - сумма счета, если нужно выставить новый счет ( Сервисная сделка )
	 *      - **newinvoice** = yes - если нужно выставить новый счет ( Сервисная сделка )
	 *      - **crid** - id счета, на который выставляем Акт ( Сервисная сделка )
	 *      - **tip** - тип счета, не обязательно ( Сервисная сделка )
	 *      - **template** - шаблон счета, не обязательно ( Сервисная сделка )
	 *      - **changePeriod** = yes  - если надо изменить период сделки ( Сервисная сделка )
	 *      - **dstart** - начало нового периода ( Сервисная сделка )
	 *      - **dend** - конец нового периода ( Сервисная сделка )
	 *
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = akt_num
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 * ```
	 * 406 - Отсутствуют параметры сделки
	 * 408 - Акт уже добавлен к сделке
	 * 409 - Минимальный этап для акта - $stepApprove %
	 * ```
	 *
	 * Пример:
	 *
	 * ```php
	 * $Akt = \Salesman\AKt::edit($id,$params);
	 * ```
	 * @throws Exception
	 */
	public function edit(int $id, array $params): array {

		global $hooks;

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$iduser1       = ((int)$params['iduser'] > 0) ? (int)$params['iduser'] : $this -> iduser1;
		$identity      = ((int)$params['identity'] > 0) ? (int)$params['identity'] : $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;
		$otherSettings = $this -> otherSettings;

		$post = $params;

		if ( $id > 0 ) {
			$params = $hooks -> apply_filters( "akt_editfilter", $params );
		}
		else {
			$params = $hooks -> apply_filters( "akt_addfilter", $params );
		}

		$crid2    = 0;
		$response = [];
		$mes      = [];

		/**
		 * Входящие параметры
		 */

		$did      = (int)$params['did'];
		$akt_num  = $params['akt_num'];
		$akt_date = $params['datum'] ?? current_datum();
		$temp     = $params['temp'];
		$iduser   = $params['iduser'] ?? (int)$iduser1;
		$crid     = (int)$params['crid'];
		$igen     = ($akt_num != '') ? $params['igen'] : 'yes';
		$des      = untag( $params['des'] );
		$status   = $params['status'];

		//только для периодических сделок

		//для создания счета
		$summa  = $params['summa'];
		$rs     = (int)$params['rs'];
		$signer = (int)$params['signer'];

		//для изменения периода сделки
		$changePeriod = $params['changePeriod'];
		$dstart       = $params['dstart'];
		$dend         = $params['dend'];

		$did = ($did > 0) ? $did : (int)$db -> getOne( "SELECT did FROM {$sqlname}contract WHERE deid = '$id'" );

		//только для периодических сделок
		$isper = (isServices( $did )) ? 'yes' : '';
		$type  = ($isper == 'yes') ? 'get_aktper' : 'get_akt';

		if ( $did > 0 ) {

			//минимальный этап для актов
			$akt_step = $db -> getOne( "SELECT akt_step FROM {$sqlname}settings WHERE id = '$identity'" );

			$stepApprove = current_dogstepname( $akt_step );
			$stepCurrent = current_dogstepname( getDogData( $did, 'idcategory' ) );

			//добавляем новый акт
			if ( $id == 0 ) {

				if ( $igen == 'yes' ) {
					$akt_num = generate_num( "akt" );
				}

				//тип договора
				$idtype = (int)$db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = '$type' and identity = '$identity'" );

				//полуим данные по сделке
				$result = $db -> getRow( "SELECT clid, payer, pid FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );
				$clid   = (int)$result["clid"];
				$payer  = (int)$result["payer"];
				$pid    = (int)$result["pid"];

				//число имеющихся актов
				//$acount = 0;

				// комплектность актами
				$complect    = round( self ::getAktComplect( $did ), 0 );
				$aktComplect = !($isper || $complect < 100);

				/**
				 * Добавим новый счет
				 */
				if ( $isper == 'yes' ) {

					$newinvoice = $params['newinvoice'];

					//добавим счет
					if ( $newinvoice == 'yes' ) {

						//$xsumma = $db -> getOne("SELECT summa_credit FROM {$sqlname}credit WHERE did = $did ORDER BY crid DESC LIMIT 1");

						$arg = [
							'did'          => $did,
							'clid'         => $clid,
							'pid'          => $pid,
							'datum'        => current_datum(),
							'datum_credit' => current_datum(),
							'summa_credit' => pre_format( $summa ),
							'iduser'       => $iduser1,
							'igen'         => $igen,
							'tip'          => $params['tip'] ?? 'По спецификации',
							'template'     => isset( $params['template'] ) ? $params['tip'] : 'По спецификации',
							'rs'           => $rs,
							'changePeriod' => $changePeriod,
							'dstart'       => $dstart,
							'dend'         => $dend
						];

						$invoice = new Invoice();
						$result  = $invoice -> add( $did, $arg );

						$crid2 = $result['data'];
						$mes[] = 'Выставлен счет';

					}

					$temp = $otherSettings['aktTempService'];

				}

				if ( !$aktComplect && $stepCurrent >= $stepApprove ) {

					/**
					 * добавим акт в документы
					 */
					$adata = [
						'datum'    => $akt_date,
						'number'   => $akt_num,
						'des'      => $des,
						'clid'     => $clid,
						'payer'    => $payer,
						'pid'      => $pid,
						'did'      => $did,
						'iduser'   => (int)$iduser,
						'title'    => $temp,
						'idtype'   => $idtype,
						'crid'     => $crid,
						'signer'   => $signer,
						'identity' => (int)$identity
					];

					$db -> query( "INSERT INTO {$sqlname}contract SET ?u", arrayNullClean( $adata ) );
					$id = $db -> insertId();

					$adata['deid'] = $id;

					if ( $hooks ) {
						$hooks -> do_action( "akt_add", $post, $adata );
					}

					/**
					 * обновим счетчик актов
					 */
					$cnum = $db -> getOne( "SELECT akt_num FROM {$sqlname}settings WHERE id = '$identity'" ) + 1;
					$db -> query( "UPDATE {$sqlname}settings SET akt_num = '$cnum' WHERE id = '$identity'" );

					unlink( $rootpath."/cash/".$fpath."settings.all.json" );

					$oldstep = getDogData( $did, 'idcategory' );

					/**
					 * Вносим изменения в сделку
					 */
					$deal = $params['dogovor'];
					if ( $deal ) {

						$d = new Deal();
						$d -> update( $did, $deal );

					}

					//изменим этап сделки
					$newstep = $params['newstep'];
					if ( $newstep > 0 && $newstep != $oldstep ) {

						$dparams = [
							"did"         => $did,
							"description" => "Выписан акт приема-передачи №$akt_num",
							"step"        => $newstep
						];

						$deal = new Deal();
						$info = $deal -> changestep( $did, $dparams );

						if ( $info['error'] == '' ) {
							$mes[] = $info['response'];
						}
						else {
							$err[] = $info['error'];
						}

					}

					//обновим статус
					$oldstatus = $db -> getOne( "SELECT status FROM {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );


					//обновляем документ
					$rez    = new Document();
					$sdata  = [
						'status'    => ($status > 0) ? $status : '0',
						'oldstatus' => $oldstatus,
						'user'      => $iduser1,
						'statusdes' => "Создан документ",
						'subaction' => 'status'
					];
					$update = $rez -> edit( $id, $sdata );

					$mes[] = $update['result'];
					$err[] = $update['error'];

					$response = [
						'result'  => 'Успешно',
						'akt_num' => $akt_num,
						'deid'    => $id,
						'did'     => $did,
						'crid'    => $crid2,
						'text'    => yimplode( "; ", $mes ),
						'error'   => ['text' => $err]
					];

				}
				elseif ( !$aktComplect == 0 && $stepCurrent < $stepApprove ) {

					$response['result']        = 'Error';
					$response['error']['code'] = '409';
					$response['error']['text'] = 'Минимальный этап для акта - '.$stepApprove.'%';

				}
				elseif ( $aktComplect ) {

					$response['result']        = 'Error';
					$response['error']['code'] = '408';
					$response['error']['text'] = 'Акт уже добавлен к сделке';

				}

			}

			//редактируем старый
			else {

				if ( $params['subaction'] != 'status' || $temp != '' ) {

					$akt_num = $db -> getOne( "SELECT number FROM {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );

					$akt_date = ($akt_date != '') ? $akt_date.' 12:00:00' : '';

					$adata = [
						'title'  => $temp,
						'datum'  => $akt_date,
						'des'    => $des,
						"signer" => $signer
					];

					//добавим акт в документы
					$db -> query( "UPDATE {$sqlname}contract SET ?u WHERE deid = '$id' and identity = '$identity'", arrayNullClean( $adata ) );

					unlink( $rootpath."/files/".$fpath."akt_".str_replace( "/", "-", $akt_num ).".pdf" );

					$adata['deid'] = $id;

					if ( $hooks ) {
						$hooks -> do_action( "akt_edit", $post, $adata );
					}

				}

				$response = [
					'result'  => 'Успешно',
					'akt_num' => $akt_num,
					'deid'    => $id,
					'did'     => $did,
					'crid'    => $crid2,
					'error'   => ['text' => $mes]
				];

			}

			/**
			 * Добавляем/обновляем позиции акта
			 */
			if ( isset( $params['speka'] ) && $id > 0 && $params['pozitions'] == 'yes' ) {

				foreach ( $params['speka'] as $item ) {

					if ( $item['spid'] > 0 && $item['kol'] > 0 ) {

						if ( $item['id'] > 0 ) {
							$db -> query( "UPDATE {$sqlname}contract_poz SET ?u WHERE id = $item[id]", [
								"spid" => $item['spid'],
								"prid" => $item['prid'],
								"kol"  => pre_format( $item['kol'] )
							] );
						}

						else {
							$db -> query( "INSERT INTO {$sqlname}contract_poz SET ?u", [
								"deid"     => $id,
								"did"      => $did,
								"spid"     => $item['spid'],
								"prid"     => $item['prid'],
								"kol"      => pre_format( $item['kol'] ),
								"identity" => $identity
							] );
						}

						//print $db -> lastQuery()."\n";

					}

				}

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Отсутствуют параметры - Сделка";

		}

		return $response;

	}

	/**
	 * Удаление акта
	 *
	 * @param int $id - идентификатор записи акта
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - did - id сделки
	 *         - deid - id удаленного Акта
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 * 406 - Отсутствуют параметры - id акта
	 *
	 * Пример:
	 *
	 * ```php
	 * $Akt = \Salesman\AKt::delete($id);
	 * ```
	 */
	public function delete(int $id): array {

		global $hooks;

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;

		/**
		 * Входящие параметры
		 */

		if ( $id > 0 ) {

			$akt = $db -> getRow( "SELECT did, number FROM {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );

			//удалим акт в документы
			$db -> query( "DELETE FROM {$sqlname}contract WHERE deid = '$id' and identity = '$identity'" );
			$db -> query( "DELETE FROM {$sqlname}contract_statuslog WHERE deid = '$id' and identity = '$identity'" );
			$db -> query( "DELETE FROM {$sqlname}contract_poz WHERE deid = '$id' and identity = '$identity'" );

			unlink( $rootpath."/files/".$fpath."akt_".str_replace( "/", "-", $akt['number'] ).".pdf" );

			//$db -> query( "UPDATE {$sqlname}dogovor SET akt_temp = '', akt_date = '', akt_num = '' WHERE did = '$akt[did]' and identity = '$identity'" );

			$response['result'] = 'Успешно';
			$response['did']    = $akt['did'];
			$response['deid']   = $id;

			if ( $hooks ) {
				$hooks -> do_action( "akt_delete", $id );
			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Отсутствуют параметры - ID Акта";

		}

		return $response;

	}

	/**
	 * Получение ссылки на файл акта
	 *
	 * @param integer $id     - идентификатор записи акта
	 *
	 * @param array   $params - массив данных
	 *
	 *      - int **did** - id сделки
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = [
	 *              ["name" => $name,"file" => $file]
	 *          ]
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      406 - Отсутствуют параметры - Сделка
	 *
	 * Пример:
	 *
	 * ```php
	 * $Link = \Salesman\AKt::link($id,$params);
	 * ```
	 * @throws Exception
	 */
	public function link(int $id, array $params): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;

		$deid = $id;
		$did  = (int)$params['did'];

		$a = new Akt();

		if ( $id > 0 || $did > 0 ) {

			$isper = (isServices( $did )) ? 'yes' : '';

			$result   = $db -> getRow( "SELECT * FROM {$sqlname}contract WHERE deid = '$deid' and identity = '$identity'" );
			$akt_num  = $result["number"];
			$akt_date = $result["datum"];

			if ( $isper != 'yes' ) {

				$file = $a -> getAkt( $deid, [
					'tip'      => 'pdf',
					'download' => 'no',
					'nosignat' => false
				] );

				$name = "Акт №".$akt_num." от ".get_sfdate2( $akt_date ).".pdf";

				$data[] = [
					"name" => $name,
					"file" => $file
				];

			}
			else {

				//найдем данные акта
				$crid     = $result["crid"];
				$did      = $result["did"];

				//найдем последний не оплаченный счет
				$crid2 = $db -> getOne( "SELECT MAX(crid) FROM {$sqlname}credit WHERE do != 'on' and did = '$did' and identity = '$identity'" );

				if ( !file_exists( $rootpath."/files/".$fpath."akt_".str_replace( "/", "-", $akt_num ).".pdf" ) ) {
					$file = $a -> getAkt( $deid, [
						'tip'      => 'pdf',
						'download' => 'no',
						'nosignat' => false
					] );
				}//getAkt($did, "pdf", "no", $akt_temp, $deid);

				else {
					$file = "akt_".str_replace( "/", "-", $akt_num ).".pdf";
				}

				$name = "Акт №".$akt_num." от ".get_sfdate2( $akt_date ).".pdf";

				$data[] = [
					"name" => $name,
					"file" => $file
				];

				//если новый счет не найден, то отправляем старый
				if ( $crid2 < 1 && $crid > 0 ) {

					//найдем данные прикрепленного к акту счета
					$result        = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'" );
					$invoice_num   = $result["invoice"];
					$invoice_datum = get_sfdate2( $result["datum"] );

					if ( !file_exists( $rootpath."/files/".$fpath."invoice_".str_replace( "/", "-", $invoice_num ).".pdf" ) ) {

						$inv = new Invoice();

						$file = $inv -> getInvoice( $crid, [
							'tip'      => 'pdf',
							'download' => 'no'
						] );

					}
					else {
						$file = "invoice_".str_replace( "/", "-", $invoice_num ).".pdf";
					}

					$name = "Счет №".str_replace( "/", "-", $invoice_num )." от ".$invoice_datum.".pdf";

					//формируем массив
					$data[] = [
						"name" => $name,
						"file" => $file
					];

				}


				if ( $crid2 > 0 ) {

					//найдем данные прикрепленного к акту счета
					$result        = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid = '$crid2' and identity = '$identity'" );
					$invoice_num   = $result["invoice"];
					$invoice_datum = get_sfdate2( $result["datum"] );

					if ( !file_exists( $rootpath."/files/".$fpath."invoice_".str_replace( "/", "-", $invoice_num ).".pdf" ) ) {

						//require_once "Invoice.php";
						$inv = new Invoice();

						$file = $inv -> getInvoice( $crid2, [
							'tip'      => 'pdf',
							'download' => 'no'
						] );
						//getInvoice($crid, "pdf", "no");

					}
					else {
						$file = "invoice_".str_replace( "/", "-", $invoice_num ).".pdf";
					}

					$name = "Счет №".str_replace( "/", "-", $invoice_num )." от ".$invoice_datum.".pdf";

					//формируем массив
					$data[] = [
						"name" => $name,
						"file" => $file
					];

				}

			}

			$response['result'] = 'Успешно';
			$response['data']   = $data;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Отсутствуют параметры - Сделка";

		}

		return $response;

	}

	/**
	 * Отправка акта по e-mail
	 *
	 * @param int   $id       - идентификатор записи акта
	 * @param array $params   - массив с параметрами
	 *                        - **did** - id сделки
	 *                        - **iduser** - id пользователя (для подписи)
	 *                        - **status** - статус документа (id статуса), если требуется изменить
	 *                        - **theme** - тема письма (если не указано = Закрывающие документы)
	 *                        - array **email** - адреса получателей, может иметь формат массива =
	 *                        ['clid:32333','pid:3455','pid:555'] или ['1@ya.ru', '2@ya.ru']
	 *                        - **file** - файл акта (не обязательно, будет сгенерирован)
	 *                        - **files** - массив доп.файлов [[name, file][name, file]]
	 *                        - array pid - массив pid контактов
	 * @param bool  $auto     - [true/false] - автоматическое определение email адресата (если email не указан),
	 *                        следует передать:
	 *                        - pid - массив pid контактов
	 *                        - clid - массив clid клиентов
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 *
	 *      406 - Счет не найден
	 *      407 - не указан ни один получатель
	 *
	 * Пример:
	 *
	 * ```php
	 * $params = [
	 *  "did"    => 678,
	 *  "iduser" => 1,
	 *  "status" => 2,
	 *  "theme"  => "Документы от БигСейлзРус",
	 *  "email"  => ['clid:32333','pid:3455','pid:555']
	 * ];
	 * $Akt = \Salesman\AKt::edit($id,$params);
	 * ```
	 * @throws Exception
	 */
	public function mail(int $id, array $params = [], bool $auto = false): array {

		$rootpath = $this -> rootpath;
		$sqlname  = $this -> sqlname;
		$iduser1  = ((int)$params['iduser'] > 0) ? (int)$params['iduser'] : $this -> iduser1;
		$identity = ((int)$params['identity'] > 0) ? (int)$params['identity'] : $this -> identity;
		$db       = $this -> db;
		$fpath    = $this -> fpath;

		$did    = (int)$params['did'];
		$status = $params['status'];

		$email   = $params['email'] ?? [];
		$theme   = $params['theme'];
		$content = str_replace( "\\r\\n", "<br>", $params['content'] );
		$CC      = (array)$params['cc'];
		$BCC     = (array)$params['bcc'];

		$file = $params['file'];
		$name = $params['name'];

		$files = (array)$params['files'];

		$mes = $des = [];
		$err = "";

		/**
		 * Данные акта
		 */
		$document = self ::info( $id );

		$datum     = format_date_rus_name( cut_date( $document["datum"] ) );
		$number    = $document["number"];
		$oldstatus = $document['status'];

		/**
		 * Если тема и/или содержание отсутствует
		 */
		if ( $theme == '' ) {

			$theme = 'Закрывающие документы';

		}
		if ( $content == '' ) {

			$content = '
			Приветствую, {{person}}
			
			Отправляю Вам документ: {{docTitle}}.
			
			Спасибо за внимание.
			С уважением,
			{{mName}}
			Тел.: {{mPhone}}
			Email.: {{mMail}}
			==============================
			{{mCompany}}';

		}

		if ( $did == 0 ) {
			$did = (int)$document['did'];
		}

		/**
		 * Если массив $email не указан (например, метод вызван из API)
		 * Массив $email должен иметь формат массива = ['clid:32333','pid:3455','pid:555']
		 * то постараемся его сформировать
		 */
		$params['clid'] = (int)$document['clid'];

		if ( !isset( $params['pid'] ) ) {
			$params['pid'] = (array)yexplode( ";", getDogData( $did, 'pid_list' ) );
		}

		$params['pid'] = (is_array( $params['pid'] )) ? (array)$params['pid'] : (array)yexplode( ";", $params['pid'] );

		//если в запросе отсутствуют pid, то берем из сделки
		if ( empty( $params['pid'] ) ) {
			$params['pid'] = (array)yexplode( ";", getDogData( $params['did'], "pid_list" ) );
		}

		//если в сделке не прикреплены контакты, то берем основной контакт
		if ( empty( $params['pid'] ) ) {
			$params['pid']   = [];
			$params['pid'][] = getClientData( (int)$params['clid'], "pid" );
		}

		/**
		 * Если массив $email не указан (например, метод вызван из API)
		 * Массив $email должен иметь формат массива = ['clid:32333','pid:3455','pid:555']
		 * то постараемся его сформировать
		 */
		if ( empty( $email ) && (!empty( (array)$params['pid'] ) || (int)$params['clid'] > 0) && $auto ) {

			if ( !empty( $params['pid'] ) ) {

				foreach ( $params['pid'] as $pid ) {
					$email[] = "pid:".$pid;
				}

			}
			if ( (int)$params['clid'] > 0 ) {
				$email[] = "clid:".$params['clid'];
			}

		}

		//print_r($email);
		//$email = [];

		if ( !empty( $email ) ) {

			if ( $number != '' ) {

				/**
				 * если файл не найден, то сгенерируем его
				 */
				if ( !file_exists( $rootpath."/files/".$fpath."akt_".$id.".pdf" ) ) {

					$fi = $this -> getAkt( $id, [
						'tip'      => 'pdf',
						'download' => 'no'
					] );

					if ( empty( $file ) ) {

						$file[] = $fi;
						$name[] = "Акт №{$number} от {$datum}.pdf";

					}

				}
				elseif ( empty( $file ) ) {

					$file[] = "akt_$number.pdf";
					$name[] = "Акт №{$number} от {$datum}.pdf";

				}

				/**
				 * для Сервисных сделок добавляем привязанный счет
				 */
				if ( (int)$document['crid'] > 0 ) {

					//require_once "Invoice.php";

					$ifile = "invoice_".$document['crid'].".pdf";

					$inv = new Invoice();

					$invoice = $inv ::info( (int)$document['crid'] );

					/**
					 * если файл не найден, то сгенерируем его
					 */
					if ( !file_exists( $rootpath."/files/".$fpath."invoice_".$document['crid'].".pdf" ) ) {

						$ifile = $inv -> getInvoice( $document['crid'], [
							'tip'      => 'pdf',
							'download' => 'no'
						] );

					}

					//сформируем данные по вложению
					$files[] = [
						"file" => $ifile,
						"name" => "Счет №".$invoice['invoice']." от ".format_date_rus_name( cut_date( $invoice['datum'] ) ).".pdf"
					];

				}

				//сформируем данные по вложению
				/*$files[] = array(
					"file" => $file,
					"name" => "Акт №".$number." от ".$datum.".pdf"
				);*/

				foreach ( $file as $i => $f ) {

					$files[] = [
						"file" => $f,
						"name" => $name[ $i ]
					];

				}

				//найдем данные сотрудника
				$u      = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'" );
				$mMail  = $u["email"];
				$mName  = $u["title"];
				$mPhone = $u["phone"];

				$mcid     = getDogData( $did, "mcid" );
				$mCompany = $db -> getOne( "SELECT name_shot FROM {$sqlname}mycomps WHERE id = '$mcid'" );

				$docTitle = $document['typeTitle']." №".$document['number']." от ".$datum;

				/**
				 * Формируем тело сообщения
				 */
				//$tags = array("mName" => $mName, "mMail" => $mMail, "mPhone" => $mPhone, "mCompany" => $mCompany);

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

				//print_r($email);

				//if ( !empty( $email ) ) {

				foreach ( $email as $mail ) {

					if ( filter_var( $mail, FILTER_VALIDATE_EMAIL ) ) {

						$toName = $mail;
						$toMail = $mail;

					}
					else {

						$inName = $inMail = '';

						$mail = explode( ":", $mail );

						if ( $mail[0] == 'pid' ) {

							$inName = getPersonData( $mail[1], 'person' );
							$array  = explode( ",", str_replace( ";", ",", str_replace( " ", "", getPersonData( $mail[1], 'mail' ) ) ) );
							$inMail = array_shift( $array );

						}
						if ( $mail[0] == 'clid' ) {

							//$clid = $mail[1];

							$inName = getClientData( $mail[1], 'title' );
							$array1 = explode( ",", str_replace( ";", ",", str_replace( " ", "", getClientData( $mail[1], 'mail_url' ) ) ) );
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

				}

				$html = nl2br( str_replace( "{{person}}", $toName, $content ) );

				//$rez = mailer( $toMail, $toName, $mMail, $mName, $theme, $html, $files, $CC );
				$rez = mailto( $x = [
					"to"       => $toMail,
					"toname"   => $toName,
					"from"     => $mMail,
					"fromname" => $mName,
					"subject"  => $theme,
					"html"     => $html,
					"files"    => $files,
					"cc"       => $CC,
					"bcc"      => $BCC
				] );

				//file_put_contents($rootpath."/cash/akt.json", json_encode_cyr($x));

				if ( $rez != '' ) {
					$err = $rez;
				}
				else {
					$des[] = "Отправлен Акт на Email: $toMail на имя $toName. ".(!empty( $CC ) ? "Копия отправлена: ".yimplode( ", ", arraySubSearch( $CC, 'name' ) ) : "")."\n\nТема: $theme.\n\nТекст сообщения:\n$html";
				}


				if ( $err != '' ) {

					$mes[] = 'Выполнено с ошибками. '.$err;

					$msg = yimplode( "; ", $mes );

					$response['result']        = "Error";
					$response['error']['code'] = 407;
					$response['error']['text'] = "Не найдено получателей";

					$response['data'] = $id;
					$response['text'] = $msg;

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
						$rez  = new Document();
						$rez -> edit( $id, $data );

					}

					$msg = yimplode( "<br>", $mes );

					$response['result'] = 'Успешно';
					$response['data']   = $id;
					$response['text']   = $msg;

				}

				//}
				/*else {

					$response[ 'result' ]          = 'Error';
					$response[ 'error' ][ 'code' ] = '407';
					$response[ 'error' ][ 'text' ] = "Не указан ни один получатель";

				}*/

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '406';
				$response['error']['text'] = "Акт не найден";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '407';
			$response['error']['text'] = "Не найдено получателей";

		}

		return $response;

	}

	/**
	 * Формирование акта для печати
	 *
	 * @param int   $id       - идентификатор записи акта
	 *
	 * @param array $params   - массив с параметрами
	 *                        - str **tip** - действие
	 *                        - **tags** - вывод тэгов
	 *                        - **print** - вывод на печать
	 *                        - **pdf** - преобразование в pdf
	 *                        - str **download** - вариант вывода для $tip = "pdf"
	 *                        - **yes** - выдача на скачивание
	 *                        - **view** - выдача в браузер
	 *                        - **no** - возвращает только имя файла
	 *                        - bool **nosignat** = true/false - исключение из выдачи печати и подписи
	 *                        - str **temp** - файл шаблона
	 *
	 * @return string|array
	 *
	 * good result
	 *          $tags
	 * error result
	 *         Error
	 *
	 * Пример:
	 *
	 * ```php
	 * $params = [
	 *  "tip"      => "print",
	 *  "nosignat" => true,
	 *  "temp"     => "ACT5c91175e8094b_akt.tpl"
	 * ];
	 * $Akt = \Salesman\AKt::mail($id,$params);
	 * ```
	 * @throws Exception
	 */
	public function getAkt(int $id, array $params = []) {

		global $pdfname, $hooks;

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$identity      = ($params['identity'] > 0) ? $params['identity'] : $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;
		$otherSettings = $this -> otherSettings;
		$ndsRaschet    = $GLOBALS['ndsRaschet'];

		/**
		 * действие -
		 *      tags - вывод тэгов
		 *      print - вывод на печать
		 *      pdf - преобразование в pdf
		 */
		$tip = $params['tip'] ?? 'tags';

		/**
		 * если tip = pdf, то
		 *      yes - выдача на скачивание
		 *      view - выдача в браузер
		 *      no - возвращает только имя файла
		 */
		$download = $params['download'] ?? 'no';

		/**
		 * исключение из выдачи печати и подписи
		 * true/false
		 */
		$nosignat = $params['nosignat'];

		/**
		 * пришедший файл шаблона
		 */
		$temp = $params['temp'] ?? '';

		$tags = [];

		//if($params['tags']) $tags = $params['tags'];

		if ( $tip == 'print' ) {
			$tags['forPRINT'] = '1';
		}
		if ( $tip == 'pdf' ) {
			$tags['forPDF'] = '1';
		}

		$root = ($tip == 'pdf' || $params['editor'] == 'yes') ? $rootpath."" : '';

		if ( $params['api'] == "yes" ) {

			$server = $_SERVER['HTTP_HOST'];
			$scheme = $_SERVER['HTTP_SCHEME'] ?? ((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

			$root = $scheme.$server."/";

		}

		//$other = explode( ";", $db -> getOne( "SELECT other FROM {$sqlname}settings WHERE id = '$identity'" ) );

		//сначала попробуем получить данные из списка актов
		$rakt     = $db -> getRow( "SELECT * FROM {$sqlname}contract WHERE deid = '$id' AND identity = '$identity'" );
		$akt_num  = $rakt["number"];
		$akt_des  = $rakt["des"];
		$akt_temp = $rakt["title"];
		$akt_date = get_smdate( $rakt["datum"] );
		$did      = (int)$rakt["did"];
		$crid     = (int)$rakt["crid"];
		$mcid     = (int)$rakt["mcid"];
		$signer   = (int)$rakt["signer"];
		$clid     = (int)$rakt["clid"];
		$pid      = (int)$rakt["pid"];
		$payer    = (int)$rakt["payer"];


		$rdog   = $db -> getRow( "SELECT iduser, mcid, clid, pid, payer FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
		$iduser = (int)$rdog["iduser"];
		if ( $pid == 0 ) {
			$pid = (int)$rdog["pid"];
		}
		if ( $clid == 0 ) {
			$clid = (int)$rdog["clid"];
		}
		if ( $payer == 0 ) {
			$payer = (int)$rdog["payer"];
		}
		if ( $mcid == 0 ) {
			$mcid = (int)$rdog["mcid"];
		}

		if ( $payer > 0 && $clid != $payer ) {
			$clid = $payer;
		}

		//заказчик по сделке
		$zak = $payer;

		//не помню для чего, видимо устарело
		/*if ( $akt_num == '' ) {

			$akt_num  = $rdog["akt_num"];
			$akt_date = $rdog["akt_date"];

		}*/

		if ( $akt_temp == '' ) {
			$akt_temp = "akt_simple.tpl";
		}

		//загружаем шаблон
		//$html = file_get_contents($rootpath.'/cash/'.$fpath.'templates/'.$akt_temp);

		//схема налогооблажения
		$nalogScheme = getNalogScheme( 0, $mcid );

		//if ( $akt_temp == '' )
		//$akt_temp = $rdog["akt_temp"];

		if ( $temp != '' ) {
			$akt_temp = $temp;
		}

		/**
		 * Формируем заголовок Акта
		 */

		//берем договор из сделки
		$deid = $db -> getOne( "SELECT dog_num FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );

		$result   = $db -> getRow( "SELECT * FROM {$sqlname}contract WHERE deid = '$deid' AND identity = '$identity'" );
		$dog_num  = $result["number"];
		$dog_date = format_date_rus( $result["datum_start"] );

		//если к сделке привязан договор
		if ( $dog_num != '' ) {
			$tags['offer'] = $offer = 'Договору №';
		}

		$countInvoice = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity'" );

		//если договор не привязан
		if ( empty( $dog_num ) || $dog_num == '' ) {

			$tags['offer'] = $offer = 'Счету (договору-оферте) №';

			$result   = $db -> getRow( "SELECT crid, invoice, invoice_date, datum FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity' LIMIT 1" );
			$crid     = $result["crid"];
			$dog_num  = $result["invoice"];
			$dog_date = get_date( $result["datum"] );

			$tags['ContractNumber'] = $dog_num;
			$tags['ContractDate']   = $dog_date;

		}

		//если НЕ привязан договор и число счетов = 1
		elseif ( $countInvoice == 1 ) {

			$tags['offer'] = $offer = 'Счету №';

			$result                 = $db -> getRow( "SELECT crid, invoice, datum_credit FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity' LIMIT 1" );
			$crid                   = $result["crid"];
			$tags['ContractNumber'] = $dog_num = $result["invoice"];
			$tags['ContractDate']   = $dog_date = get_date( $result["datum_credit"] );

		}

		//если привязан договор и число счетов больше одного
		elseif ( $countInvoice > 1 ) {

			$tags['offer']          = $offer = 'Договору №';
			$tags['ContractNumber'] = $dog_num;
			$tags['ContractDate']   = $dog_date;

		}

		//найдем реквизиты нашей компании по id компании
		$mcomp                    = $db -> getRow( "SELECT * FROM {$sqlname}mycomps WHERE id = '$mcid' AND identity = '$identity'" );
		$tags['compUrName']       = str_replace( '”', '"', $mcomp["name_ur"] );
		$tags['compShotName']     = str_replace( '”', '"', $mcomp["name_shot"] );
		$tags['compUrAddr']       = $mcomp["address_yur"];
		$tags['compFacAddr']      = $mcomp["address_post"];
		$tags['compDirName']      = $mcomp["dir_name"];
		$tags['compDirSignature'] = $mcomp["dir_signature"];
		$tags['compDirStatus']    = $mcomp["dir_status"];
		$tags['compDirOsnovanie'] = $mcomp["dir_osnovanie"];

		$innkpp = explode( ";", $mcomp["innkpp"] );
		$okog   = explode( ";", $mcomp["okog"] );

		$tags['compInn']  = ($innkpp[0] != '') ? $innkpp[0] : '-';
		$tags['compKpp']  = ($innkpp[1] != '') ? $innkpp[1] : '-';
		$tags['compOkpo'] = ($okog[0] != '') ? $okog[0] : '-';
		$tags['compOgrn'] = ($okog[1] != '') ? $okog[1] : '-';

		//найдем банковские реквизиты по id расчетного счета
		$result  = $db -> getRow( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$mcid' AND isDefault = 'yes' AND identity = '$identity' LIMIT 1" );
		$bank_rs = $result["rs"];
		$bankr   = explode( ";", $result["bankr"] );

		$tags['compBankBik']  = ($bankr[0] != '') ? $bankr[0] : '-';
		$tags['compBankKs']   = ($bankr[1] != '') ? $bankr[1] : '-';
		$tags['compBankName'] = ($bankr[2] != '') ? $bankr[2] : '-';
		$tags['compBankRs']   = $bank_rs;

		// логотип
		$tags['logo'] = $logo = (!$mcomp["logo"]) ? $root."/cash/templates/logo.png" : $root."/cash/".$fpath."templates/".$mcomp["logo"];

		//подпись
		$tags['stamp'] = $stamp = (!$mcomp["stamp"]) ? $root.'/cash/templates/signature.png' : $root.'/cash/'.$fpath.'templates/'.$mcomp["stamp"];

		//print $stamp;

		// если указан кастомный подписант
		if ( $signer > 0 ) {

			$xsigner = getSigner( $signer );

			$tags['compDirName']      = $xsigner["title"];
			$tags['compDirSignature'] = $xsigner["signature"];
			$tags['compDirStatus']    = $xsigner["status"];
			$tags['compDirOsnovanie'] = $xsigner["osnovanie"];

			$stamp = $root.'/cash/'.$fpath.'templates/'.$xsigner['stamp'];

		}
		//print $stamp;

		if ( $tip == 'pdf' ) {

			$tags['logo']  = 'data:image/png;base64,'.base64_encode( file_get_contents( $logo ) );
			$tags['stamp'] = 'data:image/png;base64,'.base64_encode( file_get_contents( $stamp ) );

		}

		if ( $params['api'] != "yes" ) {

			if ( $tip == 'pdf' || $params['editor'] == 'yes' ) {

				if ( !file_exists( $logo ) ) {
					$tags['logo'] = '';
				}

				if ( !file_exists( $stamp ) ) {
					$tags['stamp'] = '';
				}

			}
			else {

				if ( !file_exists( $rootpath.$logo ) ) {
					$tags['logo'] = '';
				}

				if ( !file_exists( $rootpath.$stamp ) ) {
					$tags['stamp'] = '';
				}

			}

		}

		//print $tags['stamp'];

		$tags['stamp'] = $tags['signature'] = (!$nosignat && $tags['stamp'] != '') ? $tags['stamp'] : '';

		//print $tags['signature'];

		//найдем реквизиты компании клиента
		if ( $clid > 0 ) {

			$json = get_client_recv( $clid );
			$data = json_decode( $json, true );

			$tags['castName']         = $castName = str_replace( '”', '"', $data['castName'] );
			$tags['castUrName']       = $castUrName = str_replace( '”', '"', $data['castUrName'] );
			$tags['castUrNameShort']  = str_replace( '”', '"', $data['castUrNameShort'] );
			$tags['castInn']          = $castInn = $data['castInn'];
			$tags['castKpp']          = $castKpp = $data['castKpp'];
			$tags['castBank']         = str_replace( '”', '"', $data['castBank'] );
			$tags['castBankKs']       = $data['castBankKs'];
			$tags['castBankRs']       = $data['castBankRs'];
			$tags['castBankBik']      = $data['castBankBik'];
			$tags['castOkpo']         = $data['castOkpo'];
			$tags['castOgrn']         = $data['castOgrn'];
			$tags['castDirName']      = $data['castDirName'];
			$tags['castDirSignature'] = $data['castDirSignature'];
			$tags['castDirStatus']    = $data['castDirStatus'];
			$tags['castDirStatusSig'] = $data['castDirStatusSig'];
			$tags['castDirOsnovanie'] = $data['castDirOsnovanie'];
			$tags['castUrAddr']       = $data['castUrAddr'];
			$tags['castFacAddr']      = $castFacAddr = $data['castFacAddr'];

			if ( $castUrName == '' ) {
				$tags['castUrName'] = $castUrName = $castName;
			}
			if ( $castName == '' ) {
				$tags['castName'] = $castName = $castUrName;
			}

		}
		if ( $pid > 0 && $payer < 1 ) {

			$tags['castUrName'] = current_person( $pid );

		}

		//тэги по доп.полям сделки
		if ( $did > 0 ) {

			$json  = get_dog_info( $did );
			$ddata = json_decode( $json, true );

			$res = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_name LIKE '%input%' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order" );
			while ($da = $db -> fetch( $res )) {

				$tags[ 'dealF'.$da['fld_name'] ] = $ddata[ $da['fld_name'] ];

			}

			$tags['dealFtitle']       = $ddata['title'];
			$tags['dealFsumma']       = num_format( $ddata['kol'] );
			$tags['dealFmarga']       = num_format( $ddata['marga'] );
			$tags['dealFperiodStart'] = format_date_rus_name( $ddata['datum_start'] );
			$tags['dealFperiodEnd']   = format_date_rus_name( $ddata['datum_end'] );

		}

		//тэги по полям Заказчика
		if ( $zak > 0 ) {

			$zclient = get_client_info( $zak, 'yes' );

			$includ = [
				'title',
				'address',
				'phone',
				'fax',
				'mail_url',
				'site_url'
			];

			$result_k = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='client' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order" );
			while ($data = $db -> fetch( $result_k )) {

				if ( in_array( $data['fld_name'], $includ ) || stripos( $data['fld_name'], 'input' ) !== false ) {

					$tags[ 'castomerF'.$data['fld_name'] ] = $zclient[ $data['fld_name'] ];

				}

			}

		}

		//тэги по полям Контакта
		if ( $pid > 0 ) {

			$json  = get_person_info( $pid );
			$pdata = json_decode( $json, true );

			$includ = [
				'person',
				'ptitle',
				'tel',
				'mob',
				'mail',
				'rol'
			];

			$result_k = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='person' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order" );
			while ($data = $db -> fetch( $result_k )) {

				if ( in_array( $data['fld_name'], $includ ) || stripos( $data['fld_name'], 'input' ) !== false ) {

					$tags[ 'personF'.$data['fld_name'] ] = $pdata[ $data['fld_name'] ];

				}

			}

		}

		//сформируем строку Получателя
		//для счета
		$castCard = '';
		if ( isset( $castName ) ) {
			$castCard .= $castName;
		}
		if ( isset( $castInn ) ) {
			$castCard .= ", ИНН ".$castInn;
		}
		if ( isset( $castKpp ) ) {
			$castCard .= ", КПП ".$castKpp;
		}
		if ( isset( $castFacAddr ) ) {
			$castCard .= ", Факт.адрес: ".$castFacAddr;
		}
		//if ( isset( $invoice_chek ) )
		//$castCard .= "<br>Основание: Договор №".$invoice_chek;

		$tags['castCard'] = $castCard;

		$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
		$settings     = json_decode( file_get_contents( $settingsFile ), true );

		$tags['compBrand'] = $settings["company"];
		$tags['compSite']  = $settings["company_site"];
		$tags['compMail']  = $settings["company_mail"];
		$tags['compPhone'] = $settings["company_phone"];

		$sumInvoice = 0;
		$nds        = 0;

		$Speka        = self ::getAktSpeka( $id );//getSpekaData($did);
		$summaInvoice = $Speka['pozitionSumma'];
		$summaItog    = $Speka['pozitionTotal'];
		$summaNalog   = $Speka['pozitionNalog'];
		$pozition     = $Speka['pozition'];

		$tags['TotalSumma'] = num_format( $summaInvoice );
		$tags['ItogSumma']  = num_format( $summaItog );

		// todo: добавить вывод позиций по акту

		//print_r($Speka);

		$tags['speka'] = [];

		if ( !empty( $pozition ) ) {

			$num           = 1;
			$summaItog     = 0;
			$summaAktNalog = 0;

			foreach ( $pozition as $data ) {

				//$data['nalog'] = $nds_string = ($data['nds'] != '') ? num_format( $data['nds'] ) : "";

				$data['artikul'] = ($data['artikul'] != '' && $otherSettings['artikulInInvoice']) ? $data['artikul'] : "";

				$data['nalog'] = ($data['nds'] != '') ? $data['nds'] : 0;

				$tags['speka'][] = [
					"Number"   => $num,
					//$data['num'],
					"Title"    => $data['title'],
					"tip"      => $data['tip'],
					"Artikul"  => $data['artikul'],
					"Comments" => $data['comments'],
					"Kol"      => $data['kol'],
					"Edizm"    => $data['edizm'],
					"Price"    => num_format( $data['price'] ),
					"Nalog"    => ($summaNalog > 0) ? num_format( (float)$data['nalog'] ) : '0,00',
					"Summa"    => num_format( $data['summa'] ),
					"Dop"      => $data['dop'],
					"spid"     => $data['spid']
				];

				$summaItog     += $data['summa'];
				$summaAktNalog += ($summaNalog > 0) ? $data['nalog'] : 0;

				$num++;

			}

		}
		else {

			//загрузим данные счета
			$summaInvoice = $db -> getOne( "SELECT kol FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );

			if ( $nds <= 0 && !$otherSettings['woNDS'] ) {
				$nds = get_nds( $did );
			}

			$tags['speka'][] = [
				"Number"   => '1',
				"Title"    => "По ".$dog_num,
				"tip"      => "Товар",
				"Artikul"  => '',
				"Comments" => '',
				"Kol"      => '-',
				"Edizm"    => '-',
				"Price"    => num_format( $sumInvoice ),
				"Nalog"    => num_format( $nds ),
				"Summa"    => num_format( $sumInvoice ),
				"Dop"      => ''
			];

		}

		//массив товаров
		$ItogTovar  = $Speka['itogTovar'];
		$nalogTovar = $Speka['nalogTovar'];
		$tovar      = $Speka['tovar'];

		$tags['tovar'] = [];

		foreach ( $tovar as $data ) {

			//$data['nalog'] = $nds_string = ($data['nds'] != '') ? num_format( $data['nds'] ) : "";

			$data['artikul'] = ($data['artikul'] != '' && $otherSettings['artikulInInvoice']) ? $data['artikul'] : "";

			$data['nalog'] = ($data['nds'] != '') ? $data['nds'] : '';

			$tags['tovar'][] = [
				"Number"   => $data['num'],
				"Title"    => $data['title'],
				"tip"      => $data['tip'],
				"Artikul"  => $data['artikul'],
				"Comments" => $data['comments'],
				"Kol"      => $data['kol'],
				"Edizm"    => $data['edizm'],
				"Price"    => num_format( $data['price'] ),
				"Nalog"    => ($nalogTovar > 0) ? num_format( $data['nalog'] ) : '',
				"Summa"    => num_format( $data['summa'] ),
				"Dop"      => $data['dop'],
				"spid"     => $data['spid']
			];

		}

		//массив услуг
		$ItogUsluga  = $Speka['itogUsluga'];
		$nalogUsluga = $Speka['nalogUsluga'];
		$usluga      = $Speka['usluga'];

		$tags['usluga'] = [];

		foreach ( $usluga as $data ) {

			//$data['nalog'] = $nds_string = ($data['nds'] != '') ? num_format( $data['nds'] ) : "";

			$data['artikul'] = ($data['artikul'] != '' && $otherSettings['artikulInInvoice']) ? $data['artikul'] : "";

			$data['nalog'] = ($data['nds'] != '') ? $data['nds'] : '';

			$tags['usluga'][] = [
				"Number"   => $data['num'],
				"Title"    => $data['title'],
				"tip"      => $data['tip'],
				"Artikul"  => $data['artikul'],
				"Comments" => $data['comments'],
				"Kol"      => $data['kol'],
				"Edizm"    => $data['edizm'],
				"Price"    => num_format( $data['price'] ),
				"Nalog"    => ($nalogUsluga > 0) ? num_format( $data['nalog'] ) : '',
				"Summa"    => num_format( $data['summa'] ),
				"Dop"      => $data['dop'],
				"spid"     => $data['spid']
			];

		}

		//массив услуг
		$ItogMaterial = $Speka['itogMaterial'];
		$material     = $Speka['material'];

		foreach ( $material as $data ) {

			//$data['nalog'] = $nds_string = ($data['nds'] != '') ? num_format( $data['nds'] ) : "";

			$data['artikul'] = ($data['artikul'] != '' && $otherSettings['artikulInInvoice']) ? $data['artikul'] : "";

			$data['nalog'] = ($data['nds'] != '') ? $data['nds'] : '';

			$tags['material'][] = [
				"Number"   => $data['num'],
				"Title"    => $data['title'],
				"tip"      => $data['tip'],
				"Artikul"  => $data['artikul'],
				"Comments" => $data['comments'],
				"Kol"      => $data['kol'],
				"Edizm"    => $data['edizm'],
				"Price"    => num_format( $data['price'] ),
				"Summa"    => num_format( $data['summa'] ),
				"Dop"      => $data['dop'],
				"spid"     => $data['spid']
			];

		}


		//загрузим данные счета
		//актуально для счетов-договоров
		//и для сервисных сделок
		if ( $crid > 0 ) {

			$result          = $db -> getRow( "SELECT datum, invoice, datum_credit FROM {$sqlname}credit WHERE crid = '$crid' AND identity = '$identity'" );
			$tags['Invoice'] = $result["invoice"];
			$datum           = $result["datum"];
			$datum_credit    = $result["datum_credit"];

			$tags['InvoiceDate']          = format_date_rus( cut_date( $datum ) );
			$tags['InvoiceDateShort']     = format_date_rus( cut_date( $datum ) );
			$tags['InvoiceDatePlan']      = format_date_rus_name( $datum_credit )." года";
			$tags['InvoiceDatePlanShort'] = format_date_rus_name( $datum_credit )." года";

			$tags['InvoiceSumma']       = num_format( $summaInvoice );
			$tags['TotalSumma']         = num_format( $summaInvoice );
			$tags['ItogSumma']          = num_format( $summaItog );
			$tags['ItogTovar']          = num_format( $ItogTovar );
			$tags['ItogUsluga']         = num_format( $ItogUsluga );
			$tags['ItogMaterial']       = num_format( $ItogMaterial );
			$tags['InvoiceSummaPropis'] = $sumPropis = ($ndsRaschet != 'yes') ? " ".mb_ucfirst( trim( num2str( (float)$summaItog ) ) ) : " ".mb_ucfirst( trim( num2str( $summaInvoice ) ) );

		}

		$tags['dopName']     = ($otherSettings['dop']) ? $otherSettings['dopName'] : '';
		$tags['noSignature'] = (!$nosignat) ? '' : true;

		//налоги
		$tags['nalogSumma']  = num_format( $summaNalog );
		$tags['nalogTovar']  = num_format( $nalogTovar );
		$tags['nalogUsluga'] = num_format( $nalogUsluga );
		$tags['nalogName']   = ($ndsRaschet != 'yes') ? "В том числе НДС" : "Налог";
		$tags['nalogTitle']  = ($ndsRaschet != 'yes') ? "НДС" : "Налог";

		if ( $nalogScheme['nalog'] == 0 || $summaNalog == 0 ) {
			$tags['nalogSumma'] = "Не облагается";
		}
		if ( $nalogScheme['nalog'] == 0 || $nalogTovar == 0 ) {
			$tags['nalogTovar'] = "Не облагается";
		}
		if ( $nalogScheme['nalog'] == 0 || $nalogUsluga == 0 ) {
			$tags['nalogUsluga'] = "Не облагается";
		}

		if ( $summaNalog == 0 ) {
			$tags['nalogTitle'] = '';
		}
		if ( $tags['nalogTovar'] == 0 ) {
			$tags['nalogTitle'] = '';
		}
		if ( $tags['nalogUsluga'] == 0 ) {
			$tags['nalogTitle'] = '';
		}

		$results            = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser'" );
		$tags['UserName']   = $results["title"];
		$tags['UserStatus'] = $results["tip"];
		$tags['UserPhone']  = $results["phone"];
		$tags['UserMob']    = $results["mob"];
		$tags['UserEmail']  = $results["email"];

		$sumPropis = ($ndsRaschet != 'yes' && $summaItog > 0) ? " ".mb_ucfirst( trim( num2str( $summaItog ) ) ) : " ".mb_ucfirst( trim( num2str( (float)$summaInvoice ) ) );

		$tags['AktNumber']      = $akt_num;
		$tags['AktDate']        = format_date_rus( $akt_date );
		$tags['AktDateShort']   = format_date( $akt_date );
		$tags['AktSumma']       = num_format( $summaItog );
		$tags['AktSummaPropis'] = $sumPropis;
		$AktComment             = $akt_des;

		$deal = get_dog_info( $did, 'yes' );

		$currency = (new Currency()) -> currencyInfo( (int)$deal['idcurrency'] );
		$course   = (new Currency()) -> courseInfo( (int)$deal['idcourse'] );

		$tags['currencyName']   = $currency['name'];
		$tags['currencySymbol'] = $currency['symbol'];
		$tags['currencyCourse'] = $course['course'];

		/**
		 * данные из редактора шаблонов
		 */
		if ( $params['tags'] ) {

			$tags             = $params['tags'];
			$tags['forPRINT'] = '1';

		}

		//заменяем старое расширение новым
		$akt_temp = str_replace( ".htm", ".tpl", $akt_temp );

		if ( $akt_temp == '' || (!file_exists( $rootpath.'/cash/'.$fpath.'templates/'.$akt_temp )) ) {
			$akt_temp = 'akt_simple.tpl';
		}

		//шаблон акта
		$html = file_get_contents( $rootpath.'/cash/'.$fpath.'templates/'.$akt_temp );


		/**
		 * Преобразование валюты
		 */
		$deal = get_dog_info( $did, "yes" );
		if ( (int)$deal['idcourse'] > 0 ) {

			$tags = Currency ::currencyConvertSpeka( $tags, (int)$deal['idcourse'] );

		}

		// доп.фильтр для тегов
		$tags = $hooks -> apply_filters( "akt_tags_filter", $tags );

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

		//обработка через шаблонизатор
		Mustache_Autoloader ::register();
		$m                  = new Mustache_Engine();
		$tags['AktComment'] = $m -> render( $AktComment, $tags );

		if ( $tip != 'tags' ) {

			$html = $m -> render( $html, $tags );

			//выводим на печать
			if ( $tip == 'print' ) {
				return $html;
			}

			//генерируем PDF
			$options = new Options();

			/*$options = new Options();
			$options -> set( 'A4', 'portrait' );
			$options -> set( 'defaultPaperSize ', 'A4' );
			$options -> set( 'fontHeightRatio', '1.0' );
			$options -> set( 'defaultMediaType ', 'print' );
			$options -> set( 'isHtml5ParserEnabled', true );
			$options -> set( 'isFontSubsettingEnabled', true );
			$options -> set( 'defaultFont', 'PT Sans' );
			$options -> set( 'rootDir', $rootpath.'/cash/dompdf/' );
			$options -> set( 'fontCache', $rootpath.'/cash/dompdf/' );
			$options -> set( 'fontCache', $rootpath.'/cash/dompdf/dompdf_font_family_cache.dist.php' );
			$options -> set( 'fontDir', $rootpath.'/vendor/dompdfFontsCastom/' );
			$options -> set( 'tempDir', $rootpath.'/cash/dompdf/' );
			$options -> set( 'dpi', 100 );*/

			/*
			$options -> set( 'A4', 'portrait' );
			$options -> set( 'defaultPaperSize ', 'A4' );
			$options -> set( 'fontHeightRatio', '0.9' );
			$options -> set( 'defaultMediaType ', 'print' );
			$options -> set( 'isHtml5ParserEnabled', true );
			$options -> set( 'isFontSubsettingEnabled', true );
			$options -> set( 'isRemoteEnabled', true );
			$options -> set( 'defaultFont', 'PT Sans' );
			$options -> set( 'rootDir', $rootpath.'/cash/dompdf/' );
			$options -> set( 'chroot', $rootpath.'/cash/dompdf/' );
			$options -> set( 'fontCache', $rootpath.'/cash/dompdf/' );
			$options -> set( 'fontDir', $rootpath.'/cash/dompdf/' );
			$options -> set( 'tempDir', $rootpath.'/cash/dompdf/' );
			$options -> set( 'dpi', 100 );*/

			$options -> set( 'A4', 'portrait' );
			$options -> set( 'defaultPaperSize ', 'A4' );
			$options -> set( 'fontHeightRatio', '0.9' );
			$options -> set( 'defaultMediaType ', 'print' );
			$options -> set( 'isHtml5ParserEnabled', true );
			$options -> set( 'isFontSubsettingEnabled', true );
			$options -> set( 'isRemoteEnabled', true );
			$options -> set( 'defaultFont', 'PT Sans' );
			$options -> set( 'rootDir', $rootpath.'/vendor/dompdf/dompdf/' );
			$options -> set( 'chroot', $rootpath.'/cash/dompdf/' );
			$options -> set( 'fontCache', $rootpath.'/cash/dompdf/' );
			$options -> set( 'fontDir', $rootpath.'/vendor/dompdfFontsCastom/' );
			$options -> set( 'tempDir', $rootpath.'/cash/' );
			$options -> set( 'dpi', 100 );

			$dompdf = new Dompdf( $options );
			$dompdf -> loadHtml( $html );
			$dompdf -> render();
			$output = $dompdf -> output();


			file_put_contents( $rootpath."/files/".$fpath."akt_".$akt_num.".pdf", $output );
			$file = $rootpath."/files/".$fpath."akt_".$akt_num.".pdf";

			$pdfname = "Акт №".$akt_num." от ".format_date_rus_name( $akt_date )." года";

			if ( $download == "yes" ) {

				$fname = 'Акт №'.$akt_num.' от '.format_date_rus_name( $akt_date )." года";
				header( "Content-Type: application/pdf" );
				header( "Content-Disposition: attachment; filename=".str_replace( " ", "_", $fname ).".pdf" );
				@readfile( $file );

			}
			elseif ( file_exists( $file ) ) {

				if ( $download == "view" ) {

					header( 'Content-Type: application/pdf' );
					header( 'Content-Disposition: inline; filename="'.$pdfname.'"' );
					//header('Content-Transfer-Encoding: binary');
					//header('Accept-Ranges: bytes');

					readfile( $file );

				}
				else {
					return "akt_".$akt_num.".pdf";
				}

			}
			else {
				return "Error";
			}

			return true;

		}

		return $tags;

	}

	/**
	 * Возвращает статусы для документов
	 * @return array
	 */
	public function statuses(): array {

		$sqlname       = $this -> sqlname;
		$identity      = $this -> identity;
		$db            = $this -> db;

		$response = [];

		$tips = $db -> getIndCol("id", "SELECT title, id FROM {$sqlname}contract_type WHERE type IN ('get_akt','get_aktper') AND identity = '$identity' ORDER BY title");

		$result = $db -> getAll("SELECT * FROM {$sqlname}contract_status WHERE identity = '$identity' ORDER by ord");
		foreach ($result as $da) {

			$idtype = [];

			$t = yexplode(";", (string)$da['tip']);
			foreach ($t as $tip) {

				if ( (int)strtr( $tip, $tips ) == 0 ) {
					$idtype[ $tip ] = strtr( $tip, $tips );
				}

			}

			if (!empty($idtype)) {
				$response[] = [
					"id"    => (int)$da['id'],
					"title" => $da['title'],
					"color" => $da['color'],
					"type"  => (int)$idtype
				];
			}

		}

		return $response;

	}

}