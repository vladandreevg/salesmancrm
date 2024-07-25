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
use event;
use Exception;
use Mustache_Autoloader;
use Mustache_Engine;
use Dompdf\Dompdf;
use Dompdf\Options;
use SafeMySQL;

/**
 * Класс для управления счетами
 *
 * Class Invoice
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example
 *
 * ```php
 * $Invoice  = new Salesman\Invoice();
 * $result = $Invoice -> add($did, $params);
 * $response['result']  = 'Успешно';
 * $response['data']    = $crid;
 * $response['invoice'] = $arg['invoice'];
 * $response['text']    = $mes;
 * ```
 */
class Invoice {

	/**
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
	 * @var false|string
	 */
	private $rootpath;

	/**
	 * синонимы для типов счетов, можно передавать в любом виде
	 */
	public const TIPS = [
		"ispeka"    => "По спецификации",
		"ioffer"    => "Счет-договор",
		"icontract" => "По договору",
		"ipre"      => "Предварительная оплата",
		"iafter"    => "Окончательная оплата"
	];

	public function __construct() {

		$rootpath = dirname(__DIR__, 2);

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

		$this -> db = new SafeMySQL($this -> opts);

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

	}

	/**
	 * Информация по счету
	 *
	 * @param $crid
	 * @param bool $dealinclude
	 * @return array
	 */
	public static function info($crid, $dealinclude = false): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$valuta   = $GLOBALS['valuta'];

		$invoice = [];

		$re                      = $db -> getRow("SELECT * FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");
		$invoice['crid']         = (int)$re["crid"];
		$invoice['invoice']      = $re["invoice"];
		$invoice['datum']        = $re["datum"];
		$invoice['datum_credit'] = $re["datum_credit"];
		$invoice['invoice_date'] = $re["invoice_date"];
		$invoice['contractnum']  = $re["invoice_chek"];
		$invoice['summa']        = (float)$re["summa_credit"];
		$invoice['nds']          = (float)$re["nds_credit"];
		$invoice['clid']         = (int)$re["clid"];
		$invoice['pid']          = (int)$re["pid"];
		$invoice['did']          = (int)$re["did"];
		$invoice['tip']          = $re["tip"];
		$invoice['rs']           = (int)$re["rs"];
		$invoice['iduser']       = (int)$re["iduser"];
		$invoice['do']           = $re["do"];
		$invoice['suffix']       = htmlspecialchars_decode($re["suffix"]);
		$invoice['valuta']       = $valuta;
		//$invoice['templateData']     = ( $re["template"] > 0 ) ? self ::getTemplates($re["template"])[0] : 'invoice.tpl';
		$invoice['template'] = ( $re["template"] > 0 ) ? self ::getTemplates((int)$re["template"])[0] : self ::getTemplates(0, 'invoice.tpl')[0];

		$deal = (array)get_dog_info($re["did"], 'yes');

		$currency = (int)$deal['idcurrency'] > 0 ? ( new Currency() ) -> currencyInfo((int)$deal['idcurrency']) : [];
		$course   = (int)$deal['idcourse'] > 0 ? ( new Currency() ) -> courseInfo((int)$deal['idcourse']) : [];

		$invoice['idcurrency'] = (int)$deal['idcurrency'];
		$invoice['currency']   = $currency;
		$invoice['idcourse']   = (int)$deal['idcurrency'];
		$invoice['course']     = $course;
		$invoice['signer']     = (int)$re["signer"] > 0 ? getSigner((int)$re["signer"]) : NULL;

		if ($dealinclude) {
			$invoice['deal'] = Deal ::info((int)$re["did"]);
		}

		return $invoice;

	}

	/**
	 * Вывод списка счетов
	 * @param array $params
	 * - page - страница
	 * - ord - сортировка
	 * - tuda - направление сортировки (desc||asc)
	 * - iduser
	 * - mc - id компании
	 * - bool pay[on] - только оплаченные
	 * - bool pay[off] - только не оплаченные
	 * - word - строка поиска
	 *
	 * @return array
	 * @throws Exception
	 */
	public function list(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		global $acs_credit, $valuta;

		$list = [];
		$sort = '';
		$word = $params['word'];
		$page = $params['page'];
		$ord  = $params['ord'];
		$tuda = $params['tuda'];

		$iduser1       = $this -> iduser1;
		$otherSettings = $this -> otherSettings;

		// разрешенные поля для сортировки
		$acceptedOrder = [
			"datum",
			"datum_credit",
			"summa_credit",
			"invoice_date",
			"invoice",
		];

		// синонимы полей для API
		$synonyms = [
			"date_create" => "datum",
			"dateCreate"  => "datum",
			"date_plan"   => "datum_credit",
			"datePlan"    => "datum_credit",
			"date_fact"   => "invoice_date",
			"dateFact"    => "invoice_date",
			"summa"       => "summa_credit",
		];

		if (array_key_exists($ord, $synonyms)) {
			$ord = $synonyms[$ord];
		}

		if (!in_array($ord, $acceptedOrder)) {
			$ord = "datum_credit";
		}

		$ordd = "crd.$ord";

		$mycomps = Guides ::myComps();

		if ($params['pay1'] == 'yes' && $params['pay2'] != 'yes') {
			$sort .= " and crd.do = 'on'";
		}
		elseif ($params['pay1'] != 'yes' && $params['pay2'] == 'yes') {
			$sort .= " and crd.do != 'on'";
		}

		if ($params['pay']['on'] && !$params['pay']['off']) {
			$sort .= " and crd.do = 'on'";
		}
		elseif (!$params['pay']['on'] && $params['pay']['off']) {
			$sort .= " and crd.do != 'on'";
		}

		// фильтр по дате счета
		$sort .= ( $params['d1'] != '' ) ? " ( DATE(crd.datum) >= '$params[d1]' AND DATE(crd.datum) <= '$params[d2]' ) AND" : "";

		// фильтр по дате оплаты
		$sort .= ( $params['dc1'] != '' ) ? " ( DATE(crd.invoice_date) >= '$params[dc1]' AND DATE(crd.invoice_date) <= '$params[dc2]' ) AND" : "";

		// фильтр по дате создания (для API)
		if ($params['dateCreateStart'] != '' && $params['dateCreateEnd'] == '') {
			$sort .= " and crd.datum > '".$params['dateStart']."'";
		}
		if ($params['dateCreateStart'] != '' && $params['dateCreateEnd'] != '') {
			$sort .= " and (crd.datum BETWEEN '".$params['dateCreateStart']."' and '".$params['dateCreateEnd']."')";
		}
		if ($params['dateCreateStart'] == '' && $params['dateCreateEnd'] != '') {
			$sort .= " and crd.datum < '".$params['dateCreateEnd']."'";
		}

		// фильтр по дате плановой (для API)
		if ($params['datePlanStart'] != '' && $params['datePlanEnd'] == '') {
			$sort .= " and crd.datum_credit > '".$params['datePlanStart']."'";
		}
		if ($params['datePlanStart'] != '' && $params['datePlanEnd'] != '') {
			$sort .= " and (crd.datum_credit BETWEEN '".$params['datePlanStart']."' and '".$params['datePlanEnd']."')";
		}
		if ($params['datePlanStart'] == '' && $params['datePlanEnd'] != '') {
			$sort .= " and crd.datum_credit < '".$params['datePlanEnd']."'";
		}

		// фильтр по дате фактической (для API)
		if ($params['dateFactStart'] != '' && $params['dateFactEnd'] == '') {
			$sort .= " and crd.invoice_date > '".$params['dateFactStart']."'";
		}
		if ($params['dateFactStart'] != '' && $params['dateFactEnd'] != '') {
			$sort .= " and (crd.invoice_date BETWEEN '".$params['dateFactStart']."' and '".$params['dateFactEnd']."')";
		}
		if ($params['dateFactStart'] == '' && $params['dateFactEnd'] != '') {
			$sort .= " and crd.invoice_date < '".$params['dateFactEnd']."'";
		}

		if ((int)$params['iduser'] > 0) {
			$sort .= " and crd.iduser= '".$params['iduser']."'";
		}
		else {
			$sort .= " and crd.iduser IN (".yimplode(",", (array)get_people($iduser1, "yes")).")";
		}

		if (!empty($word)) {
			$sort .= " and (crd.invoice LIKE '%$word%' or crd.invoice_chek LIKE '%$word%' or dg.title LIKE '%$word%' or clt.title LIKE '%$word%')";
		}

		if ((int)$params['mc'] > 0) {
			$sort .= " and crd.rs IN (SELECT id FROM {$sqlname}mycomps_recv WHERE {$sqlname}mycomps_recv.cid = '$params[mc]')";
		}

		if ($params['client'] != '') {
			$sort .= " and clt.title LIKE '%".$params['client']."%'";
		}

		if ((int)$params['clid'] > 0) {
			$sort .= " and crd.clid = '".$params['clid']."'";
		}

		if ((int)$params['did'] > 0) {
			$sort .= " and crd.did = '".$params['did']."'";
		}

		$lines_per_page = $GLOBALS['num_client']; //Стоимость записей на страницу

		$query = "
		SELECT
			crd.crid,
			crd.invoice,
			crd.datum,
			crd.datum_credit,
			crd.invoice_date,
			crd.invoice_chek,
			crd.summa_credit,
			crd.do,
			crd.pid,
			crd.clid,
			crd.did,
			clt.title as client,
			{$sqlname}personcat.person as person,
			dg.title as dogovor,
			dg.kol as summa,
			dg.close as close,
			(SELECT cid FROM {$sqlname}mycomps_recv WHERE {$sqlname}mycomps_recv.id = crd.rs) as mc
		FROM {$sqlname}credit `crd`
			LEFT JOIN {$sqlname}clientcat `clt` ON crd.clid = clt.clid
			LEFT JOIN {$sqlname}dogovor `dg` ON crd.did = dg.did
			LEFT JOIN {$sqlname}personcat ON crd.pid = {$sqlname}personcat.pid
		WHERE
			crd.crid > 0
			$sort and
			crd.identity = '$identity'
		";

		$result    = $db -> query($query);
		$all_lines = $db -> affectedRows($result);
		if (empty($page) || $page <= 0) {
			$page = 1;
		}
		else {
			$page = (int)$page;
		}
		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;

		if ($ord == 'invoice') {
			$ordd = ' (crd.invoice -1)';
		}
		elseif ($ord == 'invoice_chek') {
			$ordd = ' (crd.invoice_chek -1)';
		}

		$query .= " ORDER BY $ordd $tuda LIMIT $lpos,$lines_per_page";

		//print $query;

		$result      = $db -> query($query);
		$count_pages = (int)ceil($all_lines / $lines_per_page);

		while ($da = $db -> fetch($result)) {

			$color   = NULL;
			$warning = NULL;
			$view    = NULL;
			$dole    = NULL;
			$cando   = NULL;
			$do      = NULL;
			$isclose = NULL;

			if ($da['do'] == 'on') {
				$do = true;
			}
			else {

				if ($acs_credit == 'on') {
					$cando = true;
				}
				else {
					$do = true;
				}

				$color = 'redbg-sublite';

				if ($da['close'] == 'yes') {
					$warning = true;
					$color   = 'graybg';
					$isclose = true;
				}

			}

			//найдем дублирующие счета (не полная оплата одного счета)
			$r     = $db -> getRow("SELECT COUNT(*) as count, SUM(summa_credit) as summa FROM {$sqlname}credit WHERE invoice ='".$da['invoice']."' and did ='".$da['did']."' and identity = '$identity' GROUP BY invoice");
			$count = (int)$r["count"];
			$summa = $r["summa"];

			$delta = $da['summa'] > 0 ? ( $da['summa_credit'] / $da['summa'] ) * 100 : 0;

			if ($count > 1) {
				$dole = $summa > 0 ? number_format($da['summa_credit'] / $summa * 100, 2, ",", " ") : 0;
			}

			if ($otherSettings['printInvoice']) {
				$view = 1;
			}

			$list[] = [
				"id"           => (int)$da['crid'],
				"crid"         => (int)$da['crid'],
				"date_create"  => $da['datum'],
				"date_createf" => get_sfdate2($da['datum']),
				"date_plan"    => $da['datum_credit'],
				"date_planf"   => format_date_rus($da['datum_credit']),
				"date_fact"    => $da['invoice_date'],
				"date_factf"   => format_date_rus($da['invoice_date']),
				"contract"     => $da['invoice_chek'],
				"invoice"      => $da['invoice'],
				"summaf"       => num_format($da['summa_credit']),
				"summa"        => (float)$da['summa_credit'],
				"color"        => $color,
				"ddo"          => $do,
				"warning"      => $warning,
				"do"           => $do,
				"cando"        => $cando,
				"view"         => $view,
				"clid"         => (int)$da['clid'],
				"client"       => $da['client'],
				"pid"          => (int)$da['pid'],
				"person"       => $da['person'],
				"did"          => (int)$da['did'],
				"deal"         => $da['dogovor'],
				"isclose"      => $isclose,
				"count"        => $count.' '.morph($count, "часть", "части", "частей"),
				"dole"         => $dole,
				"company"      => $mycomps[$da['mc']],
				"mc"           => (int)$da['mc'],
				"deltaf"       => num_format($delta),
				"delta"        => (float)$delta
			];

		}

		return [
			"list"    => $list,
			"page"    => $page,
			"pageall" => $count_pages,
			"ord"     => $ord,
			"tuda"    => $tuda,
			"valuta"  => $valuta,
			"count"   => $all_lines
		];

	}

	/**
	 * Данные для вывода счетов в картчоку сделки
	 * @param int $did
	 * @return array
	 * @throws Exception
	 */
	public function card(int $did = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$valuta     = $GLOBALS['valuta'];
		$iduser1    = $GLOBALS['iduser1'];
		$tipuser    = $GLOBALS['tipuser'];
		$isadmin    = $GLOBALS['isadmin'];
		$acs_credit = $GLOBALS['acs_credit'];
		$ndsRaschet = $GLOBALS['ndsRaschet'];

		$otherSettings = $this -> otherSettings;

		$deal = Deal ::info($did);

		$invoices    = $this -> getCreditData($did);
		$nalogScheme = getNalogScheme(0, (int)$deal['mcid']);

		$Speka = ( new Speka() ) -> getSpekaData($did);
		$acss  = get_accesse(0, 0, $did);
		$summa = 0;

		// кол-во счетов
		$icount = count($invoices);
		// кол-во выполненных счетов
		$icountDo = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}credit WHERE did = '$did' AND do = 'on' AND identity = '$identity'");

		if ($icount == 0) {
			return [];
		}

		$credit = $creditDo = [];
		foreach ($invoices as $data) {

			$status = [];
			$rights = [];

			if ((int)$data['day'] < 0 && $data['do'] != "on") {
				$status['overdue'] = true;
			}
			elseif ((int)$data['day'] >= 0 && $data['do'] != "on") {
				$status['expected'] = true;
			}

			$summa += pre_format($data['summa']);

			$nds_credit = $data['nds'];

			if ($nds_credit <= 0) {

				$xnds_credit = getNalog((float)$data['summa'], (float)$nalogScheme['nalog'], $ndsRaschet);
				$nds_credit  = $xnds_credit['nalog'];

			}
			if ($Speka['summaNalog'] == 0 && $Speka['summaInvoice'] > 0) {
				$nds_credit = 0;
			}

			//если это доплата по счету, то берем ID основного счета
			$crid = ( $data['crid'] != $data['crid_main'] ) ? $data['crid_main'] : $data['crid'];

			/**
			 * Формируем кнопки
			 */
			if ($data['do'] != "on" && ( $acss == "yes" || $tipuser == "Администратор" || $isadmin == "on" ) && $acs_credit == "on") {
				$rights['check'] = true;
			}

			if ($otherSettings['price'] && $otherSettings['printInvoice'] == 'yes') {
				$rights['email'] = true;
				$rights['print'] = true;
			}

			if ($acss == "yes" || $tipuser == "Администратор" || $isadmin == "on") {

				if ($data['do'] != "on") {
					$rights['edit']   = true;
					$rights['delete'] = true;
				}
				elseif ($tipuser == "Администратор" || $isadmin == "on") {
					$rights['edit'] = true;
				}

			}

			if (( $acss == "yes" || $tipuser == "Администратор" || $isadmin == "on" ) && $acs_credit == "on" && $data['do'] == "on") {
				$rights['undo'] = true;
			}
			elseif ($data['do'] == "on") {
				$rights['undonotaccess'] = true;
			}

			$string = [
				"crid"       => (int)$data['crid'],
				"invoice"    => $data['invoice'],
				"date"       => format_date_rus(modifyDatetime($data['dcreate'], ["format" => "Y-m-d"])),
				"dateplan"   => format_date_rus($data['dplan']),
				"datefact"   => format_date_rus($data['dfact']),
				"iduser"     => $data['iduser'],
				"user"       => current_user($data['iduser']),
				"summa"      => $data['summa'],
				"summaf"     => num_format($data['summa']),
				"incurrency" => Currency ::currencyConvert($data['summa'], $deal['idcourse'], true, true),
				"iscurrency" => $deal['idcurrency'] > 0,
				"nds"        => $nds_credit,
				"ndsf"       => num_format($nds_credit),
				"isdo"       => $data['do'] == 'on',
				"days"       => $data['day'],
				"status"     => $status,
				"contract"   => ( $data['contract'] != '' ? $data['contract'] : 'Без договора' ),
				"rights"     => $rights
			];

			if (
				// если нет неоплаченных счетов
				( empty($credit) && ( $icount - $icountDo ) == 0 ) || //если это НЕ сервисная сделка и число оплаченных счетов меньше 2-х
				( empty($credit) && $icountDo <= 2 && !isServices((int)$did) ) || //если счет не оплачен
				$data['do'] != 'on'
			) {

				$credit[] = $string;

			}
			else {
				$creditDo[] = $string;
			}

		}

		$xdelta = (float)pre_format($deal['summa']) - (float)pre_format($summa);
		$delta  = num_format($xdelta);

		return [
			"credit"    => $credit,
			"creditDo"  => $creditDo,
			"haveDo"    => count($creditDo) > 0 ? true : NULL,
			"countDo"   => count($creditDo),
			"delta"     => $xdelta,
			"deltaf"    => num_format($delta),
			"haveDelta" => $xdelta > 0 ? true : NULL,
			"close"     => $deal['close']['close'] == 'yes' ? true : NULL,
			"valuta"    => $valuta
		];

	}

	/**
	 * Добавление счета
	 * Также:
	 *  - смена периода
	 *  - смена этапа сделки
	 *
	 * @param int $did - id сделки
	 * @param array $params - массив с параметрами
	 *                                  - invoice: номер счета, если не нужен автогенератор
	 *                                  - igen: если номер счета надо сгенерировать (yes|no)
	 *                                  - date date, datum: дата счета
	 *                                  - date date_plan, datum_credit: плановая дата оплаты
	 *                                  - float summa, summa_credit: сумма счета
	 *                                  - contract, invoice_chek: номер договора
	 *                                  - ??? date_do, invoice_date: дата фактической оплаты
	 *                                  - ??? do: признак оплаты (on|no) - не нужен. !!! нужет только при оплате
	 *                                  - rs: id расчетного счета
	 *                                  - signer: id подписанта
	 *                                  - tip: тип счета
	 *                                  - ispeka, По спецификации
	 *                                  - ioffer, Счет-договор
	 *                                  - icontract, По договору
	 *                                  - ipre, Предварительная оплата
	 *                                  - iafter, Окончательная оплата
	 *                                  - user, iduser: id ответственного по счету
	 *                                  - suffix: текстовая часть счета
	 *                                  - nds, nds_credit: сумма НДС по счету
	 *                                  для сервисных сделок:
	 *                                  - changePeriod: изменять ли период (yes|no)
	 *                                  - dstart: начало периода
	 *                                  - dend: конец периода
	 *                                  для смены этапа сделки:
	 *                                  - newstep: id нового этапа
	 *
	 * @return array
	 * @throws Exception
	 */
	public function add(int $did = 0, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$fpath   = $GLOBALS['fpath'];
		$valuta  = $GLOBALS['valuta'];

		$post = $params;

		$params = $hooks -> apply_filters("invoice_addfilter", $params);

		$deal = get_dog_info($did, "yes");

		/**
		 * Входящие параметры
		 */
		$invoice = untag($params['invoice']);
		$igen    = $params['igen'];

		$params["datum"]        = ( isset($params["datum"]) && $params["datum"] != '' ) ? $params["datum"] : current_datum();
		$params["datum_credit"] = ( isset($params["datum_credit"]) && $params["datum_credit"] != '' ) ? $params["datum_credit"] : current_datum(-5);

		$arg['datum']        = ( isset($params['date']) && $params['date'] != '' ) ? untag($params['date']) : untag($params["datum"]).' '.date('G').':'.date('i').':00';
		$arg['datum_credit'] = ( isset($params['date.plan']) && $params['date.plan'] != '' ) ? untag($params['date.plan']) : untag($params["datum_credit"]);
		$arg['summa_credit'] = ( isset($params['summa']) && $params['summa'] != '' ) ? pre_format($params['summa']) : pre_format($params["summa_credit"]);
		$arg['invoice_chek'] = ( isset($params['contract']) && $params['contract'] != '' ) ? untag($params['contract']) : untag($params['invoice_chek']);

		$arg['invoice_date'] = ( isset($params['date.do']) && $params['date.do'] != '' ) ? untag($params['date.do']) : untag($params['invoice_date']);
		$arg['do']           = ( isset($params['do']) && $params['do'] != '' ) ? untag($params['do']) : 'no';

		$deal['rs'] = $db -> getOne("SELECT id FROM {$sqlname}mycomps_recv WHERE cid = '$deal[mcid]' and isDefault = 'yes' and identity = '$identity' ORDER BY title");

		$arg['rs']         = ( isset($params["rs"]) && $params['rs'] > 0 ) ? $params["rs"] : $deal['rs'];
		$arg['signer']     = (int)$params["signer"];
		$arg['tip']        = strtr($params["tip"], self::TIPS);
		$arg['iduser']     = ( isset($params['iduser']) && $params['iduser'] > 0 ) ? $params['iduser'] : $params["user"];
		$arg['suffix']     = htmlspecialchars($params["suffix"]);
		$arg['nds_credit'] = ( isset($params['nds']) ) ? pre_format($params['nds']) : pre_format($params["nds_credit"]);

		$arg['idowner'] = $deal['iduser'];

		if (!is_numeric($params['template'])) {
			$params['template'] = $db -> getOne("SELECT id FROM {$sqlname}contract_temp WHERE file = '$params[template]' AND identity = '$identity' LIMIT 1");
		}

		//id шаблона счета
		$arg['template'] = $params['template'];

		$changePeriod = $params['changePeriod'];
		$dstart       = $params['dstart'];
		$dend         = $params['dend'];

		$mes = [];

		//print_r($arg);
		//exit();

		if ($did > 0) {

			$arg['did'] = $did;

			//определяем - сервисная это сделка или нет
			$isper = ( isServices($did) ) ? 'yes' : 'no';

			//Находим clid, pid
			$resultcl    = $db -> getRow("SELECT clid, pid, iduser FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
			$arg['clid'] = $resultcl["clid"];
			$arg['pid']  = $resultcl["pid"];

			//генерируем номер счета, если надо
			$arg['invoice'] = ( $invoice == '' && $igen == 'yes' ) ? generate_num('invoice') : $invoice;

			$arg['identity'] = $identity;

			/**
			 * Добавляем счет
			 */
			$db -> query("INSERT INTO {$sqlname}credit SET ?u", arrayNullClean($arg));
			$crid = $db -> insertId();

			$arg['crid'] = $crid;

			if ($hooks) {
				$hooks -> do_action("invoice_add", $post, $arg);
			}

			/**
			 * Если счет добавлен
			 */
			if ($crid > 0) {

				//обновим счетчик счетов
				if ($invoice == '' && $igen == 'yes') {

					$cnum = $db -> getOne("SELECT inum FROM {$sqlname}settings WHERE id = '$identity'") + 1;

					$db -> query("UPDATE {$sqlname}settings SET inum = '$cnum' WHERE id = '$identity'");

					unlink($rootpath."/cash/".$fpath."settings.all.json");

				}

				$mes[] = "Счет добавлен в платежи";

				/**
				 * Если счет выставлен на сотрудника
				 * у которого нет доступа к сделке
				 * то предоставляем доступ
				 */
				if (!in_array(getDogData($did, 'iduser'), (array)get_people($arg['iduser'], 'yes'))) {

					$param = [
						"did"    => $did,
						"dostup" => [
							[
								"iduser" => $arg['iduser'],
								"notify" => ""
							]
						],
						"iduser" => $iduser1
					];
					$deal  = new Deal();
					$info  = $deal -> changeDostup($did, $param);

					if ($info['result'] == 'Ok') {

						$mes[] = 'Ответственному '.current_user($arg['iduser']).' предоставлен доступ в карточку Сделки';

					}

				}

				//если это ежемесячный счет, то
				//пересчитаем сумму по сделке
				if ($isper == 'yes') {

					//по спеке найдем сумму платежа и маржу
					$result   = $db -> getRow("
						SELECT 
						    spid, 
						    SUM(price * kol) as sum, 
						    SUM(price_in * kol) as sum_in 
						FROM {$sqlname}speca 
						WHERE 
							did = '$did' and 
							identity = '$identity'
							GROUP BY 1
						");
					$summa_sp = $result["sum"];
					$marga_sp = $summa_sp - $result["sum_in"];

					//найдем долю маржи в спецификации
					$dolya = $marga_sp / $summa_sp;

					//найдем маржу по оплатам
					$sum  = $db -> getOne("SELECT SUM(summa_credit) as kol FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");
					$marg = $sum * $dolya;

					if ($changePeriod == 'yes') {

						$rez   = setPeriodDeal($did, (string)$dstart, (string)$dend);
						$mes[] = ( $rez == 'ok' ) ? 'Период сделки изменен' : $rez;

					}

					//обновим суммы по сделке
					$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did' and identity = '$identity'", [
						'kol'   => $sum,
						'marga' => $marg
					]);
					$mes[] = 'Суммы по сделке обновлены';

				}

				$oldstep = getDogData($did, 'idcategory');

				//изменим этап сделки
				$newstep = $params['newstep'];
				if ($newstep > 0 && $oldstep != $newstep) {

					$params = [
						"did"         => $did,
						"description" => "Выставлен счет №".$arg['invoice']." на сумму ".num_format($arg['summa_credit'])." ".$valuta,
						"step"        => $newstep
					];

					$deal = new Deal();
					$info = $deal -> changestep($did, $params);

					if ($info['error'] == '') {
						$mes[] = $info['response'];
					}
					else {
						$mes[] = $info['error'];
					}

				}

				$mes = implode("<br>", $mes);

				//Внесем запись в историю активностей
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => $mes,
					"tip"      => 'СобытиеCRM',
					"identity" => $identity
				]);

				//Вызовем событие добавления счета
				event ::fire('invoice.add', [
					"did"     => $did,
					"autor"   => $iduser1,
					"user"    => $arg['iduser'],
					"userUID" => current_userUID($arg['iduser']),
					"id"      => $crid,
					"summa"   => $arg['summa_credit'],
					"invoice" => $arg['invoice']
				]);

				$response['result']  = 'Успешно';
				$response['data']    = $crid;
				$response['invoice'] = $arg['invoice'];
				$response['summa']   = $arg['summa_credit'];
				$response['text']    = $mes;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = "Ошибка при добавлении счета";

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
	 * Редактирование счета
	 *
	 * @param int $crid
	 * @param array $params - массив с параметрами
	 *                              - did: id сделки
	 *                              - invoice: номер счета, если не нужен автогенератор
	 *                              - date, datum: дата счета
	 *                              - date_plan, datum_credit: плановая дата оплаты
	 *                              - summa, summa_credit: сумма счета
	 *                              - contract, invoice_chek: номер договора
	 *                              - rs: id расчетного счета
	 *                              - signer: id подписанта
	 *                              - tip: тип счета
	 *                              - ispeka, По спецификации
	 *                              - ioffer, Счет-договор
	 *                              - icontract, По договору
	 *                              - ipre, Предварительная оплата
	 *                              - iafter, Окончательная оплата
	 *                              - user, iduser: id ответственного по счету
	 *                              - suffix: текстовая часть счета
	 *                              - nds, nds_credit: сумма НДС по счету
	 *
	 * @return array
	 * @throws Exception
	 */
	public function edit(int $crid = 0, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$fpath   = $GLOBALS['fpath'];
		$valuta  = $GLOBALS['valuta'];

		$post = $params;

		$params = $hooks -> apply_filters("invoice_editfilter", $params);

		$did = (int)$params['did'];

		if ($did == 0) {
			$did = (int)$db -> getOne("SELECT did FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");
		}

		$mes = $response = [];

		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0 && $count == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			$isper = ( isServices($did) ) ? 'yes' : 'no';

			$inv = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");

			if ($crid > 0) {

				if ($inv == 0) {

					$response['result']        = 'Error';
					$response['error']['code'] = '403';
					$response['error']['text'] = "Счет не найден";

				}
				elseif ($inv > 0) {

					/**
					 * Данные счета до изменения
					 */
					$invOld = self ::info($crid);

					$arg['invoice'] = $params['invoice'];

					$arg['datum']        = isset($params['date']) ? untag($params['date']) : untag($params["datum"]).' '.date('G').':'.date('i').':00';
					$arg['datum_credit'] = isset($params['date_plan']) ? untag($params['date_plan']) : untag($params["datum_credit"]);
					$arg['summa_credit'] = isset($params['summa']) ? pre_format($params['summa']) : pre_format($params["summa_credit"]);
					$arg['invoice_chek'] = isset($params['contract']) ? untag($params['contract']) : untag($params['invoice_chek']);

					$arg['rs']         = $params["rs"];
					$arg['signer']     = (int)$params["signer"];
					$arg['tip']        = strtr($params["tip"], self::TIPS);
					$arg['iduser']     = $params["iduser"] ?? $params["user"];
					$arg['nds_credit'] = ( isset($params['nds']) ) ? pre_format($params['nds']) : pre_format($params["nds_credit"]);
					$arg['suffix']     = htmlspecialchars($params["suffix"]);

					$arg['idowner']  = getDogData($did, 'iduser');
					$arg['template'] = $params['template'];

					/**
					 * Обновляем счет
					 */
					$db -> query("UPDATE {$sqlname}credit SET ?u WHERE crid = '$crid' and identity = '$identity'", arrayNullClean($arg));

					$arg['crid'] = $crid;

					if ($hooks) {
						$hooks -> do_action("invoice_edit", $post, $arg);
					}

					//удалим pdf-файл, чтобы при печати генерировать новый
					unlink($rootpath."/files/".$fpath."invoice_".$crid.".pdf");

					$mes[] = "Сделано";

					if ($arg['iduser'] != $invOld['iduser'] && !in_array(getDogData($did, 'iduser'), (array)get_people($arg['iduser'], 'yes'))) {

						$params = [
							"did"    => $did,
							"dostup" => [
								[
									"iduser" => $arg['iduser'],
									"notify" => "off"
								]
							],
							"iduser" => $iduser1
						];

						$deal = new Deal();
						$info = $deal -> changeDostup($did, $params);

						if ($info['result'] == 'Ok') {

							$mes[] = 'Ответственному '.current_user($arg['iduser']).' предоставлен доступ в карточку Сделки';

							//Внесем запись в историю активностей
							addHistorty([
								"iduser"   => $iduser1,
								"did"      => $did,
								"datum"    => current_datumtime(),
								"des"      => implode("\n", $mes),
								"tip"      => 'СобытиеCRM',
								"identity" => $identity
							]);

						}

					}

					if ($isper == 'yes') {

						//по спеке найдем сумму платежа и маржу
						$result   = $db -> getRow("SELECT spid, SUM(price * kol) as sum, SUM(price_in * kol) as sum_in FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity'");
						$summa_sp = $result["sum"];
						$marga_sp = $summa_sp - $result["sum_in"];

						//найдем долю маржи в спецификации
						$dolya = $marga_sp / $summa_sp;

						//найдем маржу по оплатам
						$sum  = $db -> getOne("SELECT SUM(summa_credit) as kol FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");
						$marg = $sum * $dolya;

						//обновим суммы по сделке
						$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did' and identity = '$identity'", [
							'kol'   => $sum,
							'marga' => $marg
						]);

					}

					//Если счет был проведен, и пользователь изменил расчетный счет,
					//то перекинем деньги между счетами
					if ($invOld['do'] == 'on' && $invOld['rs'] != $arg['rs']) {

						$mess = '';

						Budget ::rsadd((int)$invOld['rs'], (float)$arg['summa_credit'], 'minus');
						Budget ::rsadd((int)$arg['rs'], (float)$arg['summa_credit'], 'plus');

						$rsTitle    = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$arg[rs]' and identity = '$identity'");
						$rsTitleOld = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$invOld[rs]' and identity = '$identity'");

						$mess .= 'Изменен расчетный счет в оплаченном счете. ';
						$mess .= 'На р/с '.$rsTitle.' внесена оплата в размере '.$arg['summa_credit'].' '.$valuta.' ';
						$mess .= 'С р/с '.$rsTitleOld.' списана оплата в размере '.$arg['summa_credit'].' '.$valuta;

						//Внесем запись в историю активностей
						addHistorty([
							"iduser"   => (int)$iduser1,
							"did"      => $did,
							"datum"    => current_datumtime(),
							"des"      => $mess,
							"tip"      => 'СобытиеCRM',
							"identity" => $identity
						]);

					}

					$mes = yimplode("<br>", $mes);

					event ::fire('invoice.edit', [
						"did"     => $did,
						"autor"   => (int)$iduser1,
						"user"    => (int)$arg['iduser'],
						"userUID" => current_userUID((int)$arg['iduser']),
						"id"      => $crid,
						"summa"   => $arg['summa_credit'],
						"invoice" => $arg['invoice']
					]);

					$response['result'] = 'Успешно';
					$response['data']   = $crid;
					$response['text']   = $mes;

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = "Ошибка при изменении счета - не указан ID счета";

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
	 * Экспресс внесение оплаты
	 * Создается счет и проводится одновременно
	 * ??? Не доступно для сервисных сделок через форму
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - invoice: номер счета, если не нужен автогенератор
	 *                      - igen: если номер счета надо сгенерировать (yes|no)
	 *                      - date, datum: дата счета
	 *                      - date_plan, datum_credit: плановая дата оплаты
	 *                      - summa, summa_credit: сумма счета
	 *                      - contract, invoice_chek: номер договора
	 *                      - date_do, invoice_date: дата фактической оплаты
	 *                      - ??? do: признак оплаты (on|no) - не нужен. !!! нужет только при оплате
	 *                      - rs: id расчетного счета
	 *                      - signer: id подписанта
	 *                      - tip: тип счета
	 *                      - ispeka, По спецификации
	 *                      - ioffer, Счет-договор
	 *                      - icontract, По договору
	 *                      - ipre, Предварительная оплата
	 *                      - iafter, Окончательная оплата
	 *                      - user, iduser: id ответственного по счету
	 *                      - suffix: текстовая часть счета
	 *                      - nds, nds_credit: сумма НДС по счету
	 *                      - createDelta: требуется ли создавать счет, если оплата не полная (yes|no)
	 *                      для сервисных сделок:
	 *                      - changePeriod: изменять ли период (yes|no)
	 *                      - dstart: начало периода
	 *                      - dend: конец периода
	 *                      для смены этапа сделки:
	 *                      - newstep: id нового этапа
	 *
	 * @return array
	 * @throws Exception
	 */
	public function express(int $did = 0, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$fpath   = $GLOBALS['fpath'];
		$valuta  = $GLOBALS['valuta'];

		$post = $params;

		$params = $hooks -> apply_filters("invoice_express_filter", $params);

		$mes = $response = [];

		$count = (int)$db -> getOne("SELECT COUNT(*) FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0) {

			if ($count == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Сделка не найдена";

			}
			elseif ($count > 0) {

				/**
				 * Входящие параметры
				 */
				$invoice = untag($params['invoice']);
				$igen    = $params['igen'];

				//определяем - сервисная это сделка или нет
				$isper = ( isServices($did) ) ? 'yes' : 'no';

				$params["datum"]        = ( isset($params["datum"]) && $params["datum"] != '' ) ? $params["datum"] : current_datum();
				$params["datum_credit"] = ( isset($params["datum_credit"]) && $params["datum_credit"] != '' ) ? $params["datum_credit"] : current_datum(-5);

				$arg['datum']        = ( isset($params['date']) && $params['date'] != '' ) ? untag($params['date']) : untag($params["datum"]).' '.date('G').':'.date('i').':00';
				$arg['datum_credit'] = ( isset($params['date.plan']) && $params['date.plan'] != '' ) ? untag($params['date.plan']) : untag($params["datum_credit"]);
				$arg['summa_credit'] = ( isset($params['summa']) && $params['summa'] != '' ) ? pre_format($params['summa']) : pre_format($params["summa_credit"]);
				$arg['invoice_chek'] = ( isset($params['contract']) && $params['contract'] != '' ) ? untag($params['contract']) : untag($params['invoice_chek']);
				$arg['invoice_date'] = ( isset($params['date.do']) ) ? untag($params['date.do']) : untag($params['datum']);

				$arg['did'] = $did;
				//$arg['rs']         = (int)$params["rs"];
				$arg['signer']     = (int)$params["signer"];
				$arg['tip']        = strtr($params["tip"], self::TIPS);
				$arg['iduser']     = $params['iduser'] ?? $params["user"];
				$arg['suffix']     = htmlspecialchars($params["suffix"]);
				$arg['nds_credit'] = ( isset($params['nds']) ) ? pre_format($params['nds']) : pre_format($params["nds_credit"]);

				if (!is_numeric($params['template'])) {
					$params['template'] = $db -> getOne("SELECT id FROM {$sqlname}contract_temp WHERE file = '$params[template]' AND identity = '$identity' LIMIT 1");
				}

				$arg['idowner']  = getDogData($did, 'iduser');
				$arg['template'] = $params['template'];


				if ($arg['datum_credit'] == '0000-00-00') {
					$arg['datum_credit'] = $params["datum"];
				}

				$mcid = getDogData($did, 'mcid');
				$rs   = (int)$db -> getOne("SELECT id FROM {$sqlname}mycomps_recv WHERE cid = '$mcid' and isDefault = 'yes' and identity = '$identity' ORDER BY title");

				$arg['rs'] = ( isset($params["rs"]) && $params['rs'] > 0 ) ? $params["rs"] : $rs;

				//определяем - сервисная это сделка или нет
				//$isper = (isServices($did)) ? 'yes' : 'no';

				if ($arg['iduser'] < 1) {
					$arg['iduser'] = $iduser1;
				}

				//Находим clid, pid
				$re          = $db -> getRow("SELECT clid, pid, iduser, kol FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
				$arg['clid'] = $re["clid"];
				$arg['pid']  = $re["pid"];
				$summaDeal   = $re["kol"];

				if ($arg['summa_credit'] > $summaDeal) {
					$arg['summa_credit'] = $summaDeal;
				}

				//генерируем номер счета, если надо
				$arg['invoice'] = ( $invoice == '' && $igen == 'yes' ) ? generate_num('invoice') : $invoice;

				$arg['identity'] = $identity;

				/**
				 * Обратное преобразование валюты
				 */ // Пока закроем, т.к. есть проблемы с конвертацией в связи с округлением
				/*
				$deal = get_dog_info($did, "yes");
				if ( $deal['idcurrency'] > 0 ) {

					$arg[ 'summa_credit' ] = Currency::currencyRevert($arg[ 'summa_credit' ], $deal['idcourse']);
					$arg[ 'nds_credit' ] = Currency::currencyRevert($arg[ 'nds_credit' ], $deal['idcourse']);

					$currency = (new Currency) -> currencyInfo( $deal['idcurrency']);
					$valuta = $currency['symbol'];

				}
				*/

				/**
				 * Добавляем счет
				 */
				$db -> query("INSERT INTO {$sqlname}credit SET ?u", arrayNullClean($arg));
				$crid = $db -> insertId();

				$arg['crid'] = $crid;

				if ($hooks) {
					$hooks -> do_action("invoice_express", $post, $arg);
				}

				/**
				 * Проводим счет
				 */
				$db -> query("UPDATE {$sqlname}credit SET ?u WHERE crid = '$crid' and identity = '$identity'", [
					'do'           => 'on',
					'summa_credit' => $arg['summa_credit']
				]);
				$mes[] = "Внесена оплата ".num_format($arg['summa_credit'])." $valuta";

				//фиксируем средства на р/с
				Budget ::rsadd((int)$arg['rs'], (float)$arg['summa_credit'], 'plus');

				$rtitle = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '".$arg['rs']."' and identity = '$identity'");

				if ($rtitle != '') {
					$mes[] = 'На р/с '.$rtitle.' внесена оплата в размере '.$arg['summa_credit'].' '.$valuta;
				}

				/**
				 * Если счет добавлен
				 */
				if ($crid > 0) {

					//обновим счетчик счетов
					if ($invoice == '' && $igen == 'yes') {

						$cnum = $db -> getOne("SELECT inum FROM {$sqlname}settings WHERE id = '$identity'") + 1;

						$db -> query("update {$sqlname}settings set inum = '$cnum' WHERE id = '$identity'");

						unlink($rootpath."/cash/".$fpath."settings.all.json");

					}

					$mes[] = "Счет добавлен в платежи";

					/**
					 * Если счет выставлен на сотрудника
					 * у которого нет доступа к сделке
					 * то предоставляем доступ
					 */
					if (!in_array(getDogData($did, 'iduser'), (array)get_people($arg['iduser'], 'yes'))) {

						$param = [
							"did"    => $did,
							"dostup" => [
								[
									"iduser" => $arg['iduser'],
									"notify" => "off"
								]
							],
							"iduser" => $iduser1
						];

						$deal = new Deal();
						$info = $deal -> changeDostup($did, $param);

						if ($info['result'] == 'Ok') {

							$mes[] = 'Ответственному '.current_user($arg['iduser']).' предоставлен доступ в карточку Сделки';

						}

					}

					/**
					 * Выставляем доп.счет
					 * если оплата не полная
					 */

					//Находим уже выставленные счета
					$summaCredit = $db -> getOne("SELECT SUM(summa_credit) as sum FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'") + 0;

					//проверим, что счет полностью перекрывает сумму сделки
					$delta = $summaDeal - $summaCredit;

					//если оплата не полная, то добавим счет
					if ($delta > 0 && $params['createDelta'] == 'yes') {

						$arg['summa_credit'] = pre_format($delta);
						$db -> query("INSERT INTO {$sqlname}credit SET ?u", arrayNullClean($arg));

						$mes[] = "Поступившая оплата отличается от планируемой. Добавлен дополнительный платеж ".num_format($delta)." ".$valuta;

					}

					//если это ежемесячный счет, то
					//пересчитаем сумму по сделке
					if ($isper == 'yes') {

						$changePeriod = $params['changePeriod'];
						$dstart       = $params['dstart'];
						$dend         = $params['dend'];

						//по спеке найдем сумму платежа и маржу
						$result   = $db -> getRow("SELECT spid, SUM(price * kol) as sum, SUM(price_in * kol) as sum_in FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity'");
						$summa_sp = $result["sum"];
						$marga_sp = $summa_sp - $result["sum_in"];

						//найдем долю маржи в спецификации
						$dolya = $marga_sp / $summa_sp;

						//найдем маржу по оплатам
						$sum  = $db -> getOne("SELECT SUM(summa_credit) as kol FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");
						$marg = $sum * $dolya;

						if ($changePeriod == 'yes') {

							$rez   = setPeriodDeal($did, (string)$dstart, (string)$dend);
							$mes[] = ( $rez == 'ok' ) ? 'Период сделки изменен' : $rez;

						}

						//обновим суммы по сделке
						$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did' and identity = '$identity'", [
							'kol'   => $sum,
							'marga' => $marg
						]);
						$mes[] = 'Суммы по сделке обновлены';

					}

					/**
					 * Обновим этап сделки
					 * если он указан
					 */

					//текущий этап
					$oldstep = (int)getDogData($did, 'idcategory');

					//новый этап
					$newstep = (int)$params['newstep'];
					if ($newstep > 0 && $oldstep != $newstep) {

						$params = [
							"did"         => $did,
							"description" => "Выставлен счет №".$arg['invoice']." на сумму ".num_format($arg['summa_credit'])." ".$valuta,
							"step"        => $newstep
						];

						$deal = new Deal();
						$info = $deal -> changestep($did, $params);

						if ($info['error'] == '') {
							$mes[] = $info['result'];
						}
						else {
							$mes[] = $info['error'];
						}

					}

					$message = $mes;
					$mes     = implode("<br>", $mes);

					//Внесем запись в историю активностей
					addHistorty([
						"iduser"   => $iduser1,
						"did"      => $did,
						"datum"    => current_datumtime(),
						"des"      => $mes,
						"tip"      => 'СобытиеCRM',
						"identity" => $identity
					]);

					//Вызовем событие добавления счета
					event ::fire('invoice.add', [
						"did"     => $did,
						"autor"   => $iduser1,
						"user"    => $arg['iduser'],
						"userUID" => current_userUID($arg['iduser']),
						"id"      => $crid,
						"summa"   => $arg['summa_credit'],
						"invoice" => $arg['invoice']
					]);

					$response['result']   = 'Успешно';
					$response['data']     = $crid;
					$response['invoice']  = $arg['invoice'];
					$response['text']     = $mes;
					$response['messages'] = $message;

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = '500';
					$response['error']['text'] = "Ошибка при добавлении счета";

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
	 * Отметка оплаты счета
	 * Также:
	 *  - смена периода
	 *  - смена этапа сделки
	 *
	 * @param int $crid
	 * @param array $params - массив с параметрами
	 *                      - invoice: номер счета, если не нужен автогенератор
	 *                      - summa, payment_now: сумма счета
	 *                      - date_do, invoice_date: дата фактической оплаты
	 *                      - do: признак оплаты (on|no) - не нужен. !!! нужет только при оплате
	 *                      - rs: id расчетного счета
	 *                      - user, iduser: id ответственного по счету
	 *                      - createDelta: требуется ли создавать счет, если оплата не полная (yes|no)
	 *                      для сервисных сделок:
	 *                      - changePeriod: изменять ли период (yes|no)
	 *                      - dstart: начало периода
	 *                      - dend: конец периода
	 *                      для смены этапа сделки:
	 *                      - newstep: id нового этапа
	 *
	 * @return array
	 * @throws \PHPMailer\PHPMailer\Exception
	 * @throws Exception
	 */
	public function doit(int $crid = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$valuta  = $GLOBALS['valuta'];

		$post = $params;

		$params = $hooks -> apply_filters("invoice_dofilter", $params);

		$mes     = $response = [];
		$newcrid = 0;

		$inv = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");

		if ($crid > 0) {

			if ($inv == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Счет не найден";

			}
			elseif ($inv > 0) {

				/**
				 * Входящие параметры
				 */
				$invoice      = untag($params['invoice']);
				$invoice_chek = ( isset($params['contract']) && $params['contract'] != '' ) ? untag($params['contract']) : untag($params['invoice_chek']);
				$invoice_date = ( isset($params['date_do']) && $params['date_do'] != '' ) ? untag($params['date_do']) : untag($params['datum_credit']);
				$summa        = ( isset($params['summa']) && $params['summa'] != '' ) ? pre_format($params['summa']) : pre_format($params['payment_now']);
				//$datum_credit = untag($params['datum_credit']);
				//$rs           = $params['rs'];

				//Данные счета
				$invOld       = self ::info($crid);
				$summa_credit = $invOld["summa"];
				$did          = (int)$invOld["did"];
				$tip          = $invOld["tip"];
				$datum        = $invOld["datum"];
				$datum_credit = $invOld["datum_credit"];
				$rss          = (int)$invOld["rs"];

				if (( $summa + 0 ) == 0) {
					$summa = $summa_credit;
				}
				if ($invoice == '') {
					$invoice = $invOld['invoice'];
				}

				//определяем - сервисная это сделка или нет
				$isper = ( isServices($did) ) ? 'yes' : 'no';

				$rs = ( isset($params['rs']) && $params['rs'] > 0 ) ? (int)$params['rs'] : $rss;

				$changePeriod = $params['changePeriod'];
				$dstart       = $params['dstart'];
				$dend         = $params['dend'];

				//Находим clid, pid
				$resultcl = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");
				$clid     = $resultcl["payer"];
				$pid      = $resultcl["pid"];
				$iduser   = $resultcl["iduser"];

				$delta = $summa_credit - $summa;

				$db -> query("UPDATE {$sqlname}credit SET ?u WHERE crid = '$crid' and identity = '$identity'", arrayNullClean([
					"do"           => 'on',
					"invoice"      => $invoice,
					"invoice_chek" => $invoice_chek,
					"invoice_date" => $invoice_date,
					"summa_credit" => $summa,
					'rs'           => $rs
				]));

				$mes[] = "Внесена оплата по графику ".num_format($summa)." $valuta";

				//если оплата меньше счета, то создадим еще один платеж
				//только не для сервисных сделок
				if ($delta > 0 && $isper != 'yes') {

					$db -> query("INSERT INTO {$sqlname}credit SET ?u", arrayNullClean([
						'did'          => $did,
						'clid'         => $clid,
						'pid'          => $pid,
						'invoice'      => $invoice,
						'datum'        => $datum,
						'datum_credit' => $datum_credit,
						'summa_credit' => pre_format($delta),
						'iduser'       => $iduser,
						'rs'           => $rs,
						'tip'          => $tip,
						'identity'     => $identity
					]));
					$newcrid = $db -> insertId();

					$mes[] = "Поступившая оплата отличается от планируемой.<br>Добавлен дополнительный платеж ".num_format($delta)." $valuta";

				}

				$arg['crid']    = $crid;
				$arg['newcrid'] = $newcrid;

				if ($hooks) {
					$hooks -> do_action("invoice_do", $post, $arg);
				}

				//Внесем деньги на расчетный счет
				Budget ::rsadd($rs, (float)$summa, 'plus');

				$rtitle = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$rs' and identity = '$identity'");
				if ($rtitle != '') {
					$mes[] = "На р/с $rtitle внесена оплата в размере ".num_format($summa)." $valuta";
				}

				/**
				 * Меняем период сделки
				 */
				if ($changePeriod == 'yes') {

					$rez   = setPeriodDeal($did, (string)$dstart, (string)$dend);
					$mes[] = ( $rez == 'ok' ) ? 'Период сделки изменен' : $rez;

				}

				/**
				 * изменим этап сделки
				 */
				$newstep = $params['newstep'];
				$oldstep = getDogData($did, 'idcategory');
				if ($newstep > 0 && $oldstep != $newstep) {

					$params = [
						"did"         => $did,
						"description" => "На р/с ".$rtitle." внесена оплата в размере ".num_format($summa)." ".$valuta,
						"step"        => $newstep
					];

					$deal = new Deal();
					$info = $deal -> changestep($did, $params);

					if ($info['error'] == '') {
						$mes[] = $info['response'];
					}
					else {
						$mes[] = $info['error'];
					}

				}

				$mes = implode("<br>", $mes);

				/**
				 * Активность
				 */
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => $mes,
					"tip"      => 'СобытиеCRM',
					"identity" => $identity
				]);


				/**
				 * Уведомления
				 */
				sendNotify('invoice_doit', [
					"crid"         => $crid,
					"did"          => $did,
					"clid"         => $clid,
					'invoice'      => $invoice,
					'invoice_chek' => $invoice_chek,
					'invoice_date' => $invoice_date,
					'summa_credit' => $summa,
					'des'          => $mes,
					'iduser'       => $iduser,
					'rs'           => $rtitle,
					'tip'          => $tip,
				]);


				/**
				 * Уведомления
				 */
				//require_once "Notify.php";
				$arg = [
					"crid"         => $crid,
					"did"          => $did,
					"clid"         => $clid,
					'invoice'      => $invoice,
					'invoice_chek' => $invoice_chek,
					'invoice_date' => $invoice_date,
					'summa_credit' => $summa,
					'des'          => $mes,
					'iduser'       => $iduser,
					'rs'           => $rtitle,
					'tip'          => $tip,
				];
				Notify ::fire("invoice.doit", $iduser1, $arg);


				/**
				 * Событие
				 */
				event ::fire('invoice.doit', [
					"id"       => $crid,
					"did"      => $did,
					"autor"    => $iduser1,
					"userUID"  => current_userUID($iduser),
					"invoice"  => $invoice,
					"summa"    => $summa,
					"summaNew" => $delta
				]);

				$response['result'] = 'Успешно';
				$response['data']   = $crid;

				if ($newcrid > 0) {
					$response['newdata'] = $newcrid;
				}

				$response['text'] = $mes;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Не указан ID счета";

		}

		return $response;

	}

	/**
	 * Отмена проведения счета
	 *
	 * @param int $crid
	 * @param array $params - массив с параметрами
	 *                      - user, iduser: id ответственного за действие
	 *
	 * @return array
	 * @throws Exception
	 */
	public function undoit(int $crid = 0, array $params = []): array {

		global $hooks;

		//$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		//$fpath   = $GLOBALS['fpath'];
		$valuta = $GLOBALS['valuta'];

		$mes = $response = [];

		$inv = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");

		if ($crid > 0) {

			if ($inv == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Счет не найден";

			}
			elseif ($inv > 0) {

				$invOld       = self ::info($crid);
				$rs           = (int)$invOld["rs"];
				$summa_credit = (float)$invOld["summa"];
				$did          = (int)$invOld["did"];
				$invoice      = $invOld["invoice"];

				$db -> query("UPDATE {$sqlname}credit SET ?u WHERE crid = '$crid' and identity = '$identity'", [
					"do"           => '',
					"invoice_date" => NULL
				]);
				$mes[] = "Отменена оплата по сч № ".$invoice;

				//Внесем деньги на расчетный счет
				Budget ::rsadd($rs, $summa_credit, 'minus');

				$rtitle = $db -> getOne("SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$rs' and identity = '$identity'");
				if ($rtitle != '') {
					$mes[] = 'С р/с <b>'.$rtitle.'</b> отменена оплата в размере <b>'.$summa_credit.'</b> '.$valuta;
				}

				$isper = ( isServices($did) ) ? 'yes' : 'no';

				//изменим этап сделки
				//$oldStep = getDogData($did, "idcategory");
				//$newstep = getPrevNextStep($oldStep, 'prev');

				/**
				 * Изменим этап сделки
				 */
				$mFunnel = getMultiStepList(["did" => $did]);
				if (empty((array)$mFunnel['steps'])) {

					$oldStep = getDogData($did, 'idcategory');
					$newstep = getPrevNextStep($oldStep, 'prev');

				}
				else {

					$oldStep = $mFunnel['current']['id'];
					$newstep = $mFunnel['prev'];

				}

				if ((int)$newstep['id'] > 0 && $isper != 'yes') {

					$params = [
						"did"         => $did,
						"description" => "Отменена оплата по сч. №".$invoice,
						"step"        => $newstep['id']
					];

					$deal = new Deal();
					$info = $deal -> changestep($did, $params);

					$db -> query("DELETE FROM {$sqlname}steplog WHERE did = '$did' and step = '$oldStep' and identity = '$identity'");

					$mes[] = ( $info['error'] == '' ) ? $info['response'] : $info['error'];

				}

				$mes = implode("<br>", $mes);

				//Внесем запись в историю активностей
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => $mes,
					"tip"      => 'СобытиеCRM',
					"identity" => $identity
				]);

				event ::fire('invoice.undoit', $data = [
					"id"      => $crid,
					"did"     => $did,
					"autor"   => $iduser1,
					"userUID" => current_userUID($iduser1),
					"invoice" => $invoice
				]);

				if ($hooks) {
					$hooks -> do_action("invoice_undo", $crid, $data);
				}

				$response['result'] = 'Успешно';
				$response['data']   = $crid;
				$response['text']   = $mes;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Не указан ID счета";

		}

		return $response;

	}

	/**
	 * Удаление счета
	 *
	 * @param int $crid
	 * @param array $params
	 *
	 * @return array
	 * @throws Exception
	 */
	public function delete(int $crid = 0, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$fpath   = $GLOBALS['fpath'];
		$valuta  = $GLOBALS['valuta'];

		$mes = $response = [];

		$inv = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");

		if ($crid > 0) {

			if ($inv == 0) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Счет не найден";

			}
			elseif ($inv > 0) {

				$invOld  = self ::info($crid);
				$did     = $invOld["did"];
				$invoice = $invOld["invoice"];

				$db -> query("DELETE FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");
				$mes[] = 'Платеж по сч № '.$invoice.' успешно удален';

				if ($hooks) {
					$hooks -> do_action("invoice_delete", $crid);
				}

				//удалим pdf-файл, чтобы при печати генерировать новый
				unlink($rootpath."/files/".$fpath."invoice_".$crid.".pdf");

				//удалим платежку
				unlink($rootpath."/files/payorders/".$fpath."payorder_".$crid.".txt");

				//определяем - сервисная это сделка или нет
				$isper = ( isServices((int)$did) ) ? 'yes' : 'no';

				/**
				 * Расчет суммы по сделке для сервисной сделки
				 */
				if ($isper == 'yes') {

					//получим сумму счетов
					$result = $db -> getRow("SELECT SUM(summa_credit) as kol, COUNT(crid) as count FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");
					$kol    = $result["kol"];
					$count  = $result["count"];

					//получим сумму и маржу из спеки и увеличим на число счетов (счета одинаковые)
					$result = $db -> getRow("SELECT spid, SUM(price * kol) as sum, SUM(price_in * kol) as sum_in FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity'");
					$sum    = $count * $result["sum"];
					$sum_in = $count * $result["sum_in"];

					$marg = $sum - $sum_in;

					//обновим суммы по сделке
					$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did' and identity = '$identity'", [
						'kol'   => $sum,
						'marga' => $marg
					]);

				}

				$mes = implode("<br>", $mes);

				//Внесем запись в историю активностей
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => "Платеж на сумму ".num_format($invOld["summa"])." $invOld[valuta] успешно удален из графика платежей",
					"tip"      => 'СобытиеCRM',
					"identity" => $identity
				]);

				event ::fire('invoice.delete', [
					"id"      => $crid,
					"did"     => $did,
					"autor"   => $iduser1,
					"userUID" => current_userUID($iduser1),
					"invoice" => $invoice
				]);

				$response['result'] = 'Успешно';
				$response['data']   = $did;
				$response['text']   = $mes;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Не указан ID счета";

		}

		return $response;

	}

	/**
	 * Отправка счета по email
	 *
	 * @param int $crid
	 * @param array $params - массив с параметрами
	 *                      - did: id сделки (не обязательно)
	 *                      - email: Массив email должен иметь формат массива = ['clid:32333','pid:3455','pid:555'] (не
	 *                      обязательно)
	 *                      - emails: Массив с указанием адресатов [["name" => "Name1","email" => "Email1"],["name" =>
	 *                      "Name2","email" => "Email2"]]
	 *                      - clid: id клиента, если не задан параметр email и emails
	 *                      - pid: id контакта, если не задан параметр email и emails
	 *
	 *   - theme: Тема сообщения
	 *   - content: Содержание сообщения
	 * @param bool $auto : параметр разрешает автоматически находить адресатов при отсутствии массива email
	 *
	 * @return array
	 * @throws \PHPMailer\PHPMailer\Exception
	 * @throws Exception
	 */
	public function mail(int $crid = 0, array $params = [], bool $auto = false): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$iduser1 = ( $params['iduser'] > 0 ) ? $params['iduser'] : $this -> iduser1;
		$fpath   = $GLOBALS['fpath'];
		$valuta  = $GLOBALS['valuta'];

		$email   = $params['email'] ?? [];
		$theme   = $params['theme'];
		$content = str_replace("\\r\\n", "<br>", $params['content']);
		$emails  = $params['emails'];
		$CC      = (array)$params['cc'];
		$BCC     = (array)$params['bcc'];

		/**
		 * Если тема и/или содержание отсутствует
		 */
		if ($theme == '') {

			$theme = 'Счет на оплату';

		}
		if ($content == '') {

			$content = '
			Приветствую, {{person}}
			
			Отправляю Вам Счет на оплату.
			
			Спасибо за внимание.
			С уважением,
			{{mName}}{{#mPhone}}
			Тел.: {{mPhone}}{{/mPhone}}{{#mMail}}
			Email.: {{mMail}}{{/mMail}}
			{{#mCompany}}==============================
			{{mCompany}}{{/mCompany}}';

		}

		//данные счета
		$credit = $db -> getRow("SELECT * FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");

		$params['did']  = (int)$credit['did'];
		$params['clid'] = (int)$credit['clid'];

		$params['pid'] = [];

		//если в запросе отсутствуют pid, то берем из сделки
		if (empty($params['pid'])) {

			$params['pid'] = yexplode(";", getDogData($params['did'], "pid_list"));

		}

		//если в сделке не прикреплены контакты, то берем основной контакт
		if (empty($params['pid'])) {

			$params['pid'] = [getClientData($params['clid'], "pid")];

		}

		/**
		 * Если массив $email не указан (например, метод вызван из API)
		 * Массив $email должен иметь формат массива = ['clid:32333','pid:3455','pid:555']
		 * то постараемся его сформировать
		 */
		if (empty($email) && ( !empty($params['pid']) || $params['clid'] > 0 ) && $auto) {

			if ((int)$params['clid'] > 0) {

				$email[] = "clid:".$params['clid'];

			}
			if (!empty($params['pid'])) {

				foreach ($params['pid'] as $pid) {
					$email[] = "pid:".$pid;
				}

			}

		}

		/**
		 * Данные счета
		 */
		$datum   = format_date_rus_name(cut_date($credit["datum"]));
		$invoice = $credit["invoice"];

		if ($invoice != '') {

			$mes = $files = $des = [];
			$err = '';

			$invoice = str_replace("/", "-", $invoice);

			$file = "invoice_".$crid.".pdf";

			/**
			 * если файл не найден, то сгенерируем его
			 */
			if (!file_exists($rootpath."/files/".$fpath."invoice_".$crid.".pdf")) {
				$file = $this -> getInvoice($crid, [
					'tip'      => 'pdf',
					'download' => 'no'
				]);
			}

			//сформируем данные по вложению
			$files[] = [
				"file" => $file,
				"name" => "Счет №".$invoice." от ".$datum.".pdf"
			];

			//найдем данные сотрудника
			$u      = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
			$mMail  = $u["email"];
			$mName  = $u["title"];
			$mPhone = $u["phone"];

			$mcid     = (int)$db -> getOne("SELECT cid FROM {$sqlname}mycomps_recv WHERE id = '$credit[rs]'");
			$mCompany = $db -> getOne("SELECT name_shot FROM {$sqlname}mycomps WHERE id = '$mcid'");

			/**
			 * Формируем тело сообщения
			 */
			/*$content = str_replace([
				"{{mName}}",
				"{{mMail}}",
				"{{mPhone}}",
				"{{mCompany}}"
			], [
				$mName,
				$mMail,
				$mPhone,
				$mCompany
			], $content);
			$theme   = str_replace([
				"{{mName}}",
				"{{mMail}}",
				"{{mPhone}}",
				"{{mCompany}}"
			], [
				$mName,
				$mMail,
				$mPhone,
				$mCompany
			], $theme);
			*/

			/**
			 * Формируем список получателей
			 */
			$toName = '';
			$toMail = '';

			//print_r($email);

			if (!empty($email)) {

				foreach ($email as $mail) {

					$inName = $inMail = '';

					$mail = explode(":", $mail);

					if ($mail[0] == 'pid') {

						$inName = getPersonData($mail[1], 'person');
						$array  = explode(",", str_replace(";", ",", str_replace(" ", "", getPersonData($mail[1], 'mail'))));
						$inMail = array_shift($array);

					}
					if ($mail[0] == 'clid') {

						$params['clid'] = $mail[1];

						$inName = getClientData($mail[1], 'title');
						$array1 = explode(",", str_replace(";", ",", str_replace(" ", "", getClientData($mail[1], 'mail_url'))));
						$inMail = array_shift($array1);

					}

					//если основной отправитель не указан, то указываем
					if ($toName == '') {

						$toName = $inName;
						$toMail = $inMail;

					}
					elseif (!empty($emails)) {

						$e = array_shift($emails);

						$toName = $e['name'];
						$toMail = $e['email'];

						foreach ($emails as $em) {
							$CC[] = [
								"name"  => $em['name'],
								"email" => $em['email']
							];
						}

					}
					//иначе добавляем в копию
					else {
						$CC[] = [
							"name"  => $inName,
							"email" => $inMail
						];
					}

				}

				//$html = nl2br(str_replace("{{person}}", $toName, $content));

				$xtags = [
					"person"   => $toName,
					"mName"    => $mName,
					"mMail"    => $mMail,
					"mPhone"   => $mPhone,
					"mCompany" => $mCompany
				];

				$m     = new Mustache_Engine();
				$html  = nl2br($m -> render($content, $xtags));
				$theme = $m -> render($theme, $xtags);

				$rez = mailto([
					"to"       => $toMail,
					"toname"   => $toName,
					"from"     => $mMail,
					"fromname" => $mName,
					"subject"  => $theme,
					"html"     => $html,
					"files"    => $files,
					"cc"       => $CC,
					"bcc"      => $BCC
				]);

				//print $rez;

				if ($rez != '') {
					$err = $rez;
				}
				else {
					$des[] = "Отправлен Счет на Email: $toMail на имя $toName. ".( !empty($CC) ? "Копия отправлена: ".yimplode(", ", arraySubSearch($CC, 'name')) : "" )."\n\nТема: $theme.\n\nТекст сообщения:\n$html";
				}


				if ($err != '') {

					$mes[] = 'Выполнено с ошибками. '.$err;

					$msg = yimplode("; ", $mes);

					$response['result']        = "Error";
					$response['error']['code'] = 407;
					$response['error']['text'] = "Не найден ниодин получатель";

					$response['data']    = $crid;
					$response['invoice'] = $invoice;
					$response['text']    = $msg;

				}
				else {

					$mes[] = "Сделано";

					//добавим в историю
					addHistorty([
						"iduser"   => $iduser1,
						"clid"     => $params['clid'],
						"did"      => $params['did'],
						"datum"    => current_datumtime(),
						"des"      => implode("<br>", $des),
						"tip"      => 'Исх.Почта',
						"identity" => $identity
					]);

					$msg = yimplode("<br>", $mes);

					$response['result']  = 'Успешно';
					$response['data']    = $crid;
					$response['invoice'] = $invoice;
					$response['text']    = $msg;

				}

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 407;
				$response['error']['text'] = "Не указан ни один получатель";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Счет не найден";

		}

		return $response;

	}

	/**
	 * Генерация PDF и предоставление ссылки на файл счета
	 *
	 * @param int $crid
	 *
	 * @return array
	 * @throws Exception
	 */
	public function link(int $crid = 0): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$fpath = $GLOBALS['fpath'];

		/**
		 * Данные счета
		 */
		$result  = $db -> getRow("SELECT * FROM {$sqlname}credit WHERE crid = '$crid' and identity = '$identity'");
		$datum   = format_date_rus_name(cut_date($result["datum"]));
		$invoice = $result["invoice"];

		if ($invoice != '') {

			$invoice = str_replace("/", "-", $invoice);

			if (!file_exists($rootpath."/files/".$fpath."invoice_".$crid.".pdf")) {
				$this -> getInvoice($crid, ['tip' => 'pdf']);
			}

			$file = "invoice_".$crid.".pdf";

			$filename = "Счет №".$invoice." от ".$datum;

			$payorder     = "payorders/payorder_".$crid.".txt";
			$payordername = "Платежное поручение";

			$response['result'] = [
				"file"         => $file,
				"name"         => $filename,
				"payorder"     => $payorder,
				"payorderName" => $payordername
			];
			$response['data']   = $crid;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '406';
			$response['error']['text'] = "Счет не найден";

		}

		return $response;

	}

	/**
	 * Подготовка счета для печати или в виде PDF
	 *
	 * @param int $crid
	 * @param array $params - массив с параметрами
	 *
	 *      - tip: действие, def = tags (tags - вывод тэгов, print - вывод на печать, pdf - преобразование в pdf)
	 *      - download: если tip = pdf, то yes - выдача на скачивание, no - выдача в праузер, link - ссылка на файл,
	 *      content - содержимое файла для записи
	 *      - tagsAttached: дополнение тегов:
	 * ```php
	 * $params = [
	 *      "tagsAttached" => [
	 *          "images" => [
	 *              "barCode" => ["file" => "path/to/file/barcode423424.png", "tag" => "barCode",
	 *              "photo"   => ["file" => "path/to/file/photo4656646.png", "tag" => "barCode"
	 *          ],
	 *          "ticket" => "0004556",
	 *          "someTag" => "Это произвольный тег"
	 *      ]
	 * ];
	 * ```
	 *
	 * @return array|bool
	 * @throws Exception
	 */
	public function getInvoice(int $crid = 0, array $params = []) {

		global $pdfname;
		global $hooks;

		$rootpath      = $this -> rootpath;
		$sqlname       = $this -> sqlname;
		$identity      = ( $params['identity'] > 0 ) ? $params['identity'] : $this -> identity;
		$db            = $this -> db;
		$fpath         = $this -> fpath;
		$otherSettings = $this -> otherSettings;

		$params['crid'] = $crid;
		$params         = $hooks -> apply_filters("invoice_getfilter", $params);

		/**
		 * действие -
		 *      tags - вывод тэгов
		 *      print - вывод на печать
		 *      pdf - преобразование в pdf
		 *      txt - формирование платежного поручения
		 */
		$tip = $params['tip'] ?? 'tags';

		/**
		 * если tip = pdf, то
		 *      yes - выдача на скачивание
		 *      view - выдача в браузер
		 *      no - возвращает только имя файла
		 */
		$download = $params['download'] ?? 'no';

		//if($download != 'yes' && $tip == 'pdf') $download = 'view';

		/**
		 * исключение из выдачи печати и подписи
		 */
		$nosignat = $params['nosignat'];

		$tags = [];

		if (!empty($params['tagsAttached'])) {
			$tags = array_merge($tags, $params['tagsAttached']);
		}

		if ($tip == 'print') {
			$tags['forPRINT'] = '1';
		}
		if ($tip == 'pdf') {
			$tags['forPDF'] = '1';
		}

		$root = ( $tip == 'pdf' || $params['editor'] == 'yes' ) ? $rootpath."" : '';

		if ($params['api'] == "yes") {

			$server = $_SERVER['HTTP_HOST'];
			$scheme = $_SERVER['HTTP_SCHEME'] ?? ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ) || $_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://';

			$root = $scheme.$server."/";

		}

		//$root = 'http://sm2020.crm';

		//$other      = explode( ";", $db -> getOne( "SELECT other FROM {$sqlname}settings WHERE id = '$identity'" ) );
		$ndsRaschet = $otherSettings['ndsInOut'];

		//загрузим данные счета
		$result          = $db -> getRow("SELECT * FROM {$sqlname}credit WHERE crid = '$crid' AND identity = '$identity'");
		$tags['Invoice'] = $invoice = $result['invoice'];
		$datum           = $result['datum'];
		$datum_credit    = $result['datum_credit'];
		$sum             = $result['summa_credit'];
		$invoice_chek    = $result['invoice_chek'];
		$rs              = (int)$result['rs'];
		$did             = (int)$result['did'];
		$clid            = (int)$result['clid'];
		$pid             = (int)$result['pid'];
		$tip_credit      = $result['tip'];
		$nds             = $result['nds_credit'];
		$iduser          = (int)$result['iduser'];
		$tempID          = $result['template'];
		$signer          = (int)$result['signer'];
		$suffixInc       = htmlspecialchars_decode($result['suffix']);

		//print_r($result);

		$template = ( $tempID > 0 ) ? $db -> getOne("SELECT file FROM {$sqlname}contract_temp WHERE id = '$tempID' AND identity = '$identity'") : 'invoice.tpl';

		$tags['InvoiceDate']          = format_date_rus_name(cut_date($datum));
		$tags['InvoiceDateShort']     = format_date_rus(cut_date($datum));
		$tags['InvoiceDatePlan']      = format_date_rus_name($datum_credit)." года";
		$tags['InvoiceDatePlanShort'] = format_date_rus_name($datum_credit)." года";

		$nalogScheme = getNalogScheme($rs);

		//print_r($nalogScheme);

		$itogTovar  = $itogUsluga = $itogMaterial = $sum;
		$nalogTovar = $nalogUsluga = 0;

		$zak = $clid; //заказчик

		$fileInvoice = $rootpath."/files/".$fpath."invoice_".$crid.".pdf";

		//удаляем уже сгенерированные файлы
		if (file_exists($fileInvoice) && $tip == 'pdf') {
			unlink($fileInvoice);
		}

		//получим данные договора
		if ($invoice_chek && $tip_credit == 'По договору') {

			$result = $db -> getRow("SELECT payer, datum_start FROM {$sqlname}contract WHERE number = '$invoice_chek' AND did = '$did' AND identity = '$identity'");
			$payer  = (int)$result["payer"];
			//дата документа
			$contract_date = get_date($result["datum_start"]);

			//номер + дата
			//$invoice_chek = $invoice_chek." от ".$contract_date;

			//получим плательщика из сделки если такой договор не найден
			if ($payer < 1) {
				$payer = getDogData($did, 'payer');
			}
			if ($payer > 0 && $clid != $payer) {
				$clid = $payer;
			}

		}
		else {

			//получим номер договора из сделки
			$result = $db -> getRow("SELECT * FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'");
			$deid   = (int)$result["dog_num"];//номер договора

			$payer = $result["payer"];
			if ($payer > 0 && $clid != $payer) {
				$clid = $payer;
			}

			$result        = $db -> getRow("SELECT * FROM {$sqlname}contract WHERE deid = '$deid' AND identity = '$identity'");
			$contract_date = get_date($result["datum_start"]);
			$invoice_chek  = $result["number"];
			//$invoice_chek  = $invoice_chek." от ".$contract_date;

		}

		$tags['dog_num']        = $invoice_chek;
		$tags['dog_date']       = $contract_date;
		$tags['ContractNumber'] = $invoice_chek;
		$tags['ContractDate']   = $contract_date;

		//найдем банковские реквизиты по id расчетного счета
		$result  = $db -> getRow("SELECT * FROM {$sqlname}mycomps_recv WHERE id = '$rs' AND identity = '$identity'");
		$bank_rs = $result["rs"];
		$cid     = $result["cid"];
		$bankr   = explode(";", $result["bankr"]);

		$tags['compBankBik']  = ( $bankr[0] != '' ) ? $bankr[0] : '-';
		$tags['compBankKs']   = ( $bankr[1] != '' ) ? $bankr[1] : '-';
		$tags['compBankName'] = ( $bankr[2] != '' ) ? $bankr[2] : '-';
		$tags['compBankRs']   = $bank_rs;

		//найдем реквизиты нашей компании по id компании
		$mcomp                    = $db -> getRow("SELECT * FROM {$sqlname}mycomps WHERE id = '$cid' AND identity = '$identity'");
		$tags['compUrName']       = str_replace('”', '"', $mcomp["name_ur"]);
		$tags['compShotName']     = str_replace('”', '"', $mcomp["name_shot"]);
		$tags['compUrAddr']       = $mcomp["address_yur"];
		$tags['compFacAddr']      = $mcomp["address_post"];
		$tags['compDirName']      = $mcomp["dir_name"];
		$tags['compDirSignature'] = $mcomp["dir_signature"];
		$tags['compDirStatus']    = $mcomp["dir_status"];
		$tags['compDirOsnovanie'] = $mcomp["dir_osnovanie"];

		$innkpp = explode(";", (string)$mcomp["innkpp"]);
		$okog   = explode(";", (string)$mcomp["okog"]);

		//print_r($mcomp);

		// логотип
		$tags['logo'] = $logo = empty($mcomp["logo"]) ? $root."/cash/templates/logo.png" : $root.'/cash/'.$fpath.'templates/'.$mcomp["logo"];

		//$tags['logo'] = str_replace("/", "\\", $tags['logo']);
		//print $logo;
		//exit();

		//подпись
		$tags['stamp'] = $stamp = empty($mcomp["stamp"]) ? $root.'/cash/templates/signature.png' : $root.'/cash/'.$fpath.'templates/'.$mcomp["stamp"];

		// если указан кастомный подписант
		if ($signer > 0) {

			$xsigner = getSigner($signer);

			$tags['compDirName']      = $xsigner["title"];
			$tags['compDirSignature'] = $xsigner["signature"];
			$tags['compDirStatus']    = $xsigner["status"];
			$tags['compDirOsnovanie'] = $xsigner["osnovanie"];

			$tags['stamp'] = $stamp = $root.'/cash/'.$fpath.'templates/'.$xsigner['stamp'];

		}

		if ($tip == 'pdf') {

			$tags['logo']  = 'data:image/png;base64,'.base64_encode(file_get_contents($logo));
			$tags['stamp'] = 'data:image/png;base64,'.base64_encode(file_get_contents($stamp));

		}

		if ($params['api'] != "yes") {

			if ($tip == 'pdf' || $params['editor'] == 'yes') {

				if (!file_exists($logo)) {
					$tags['logo'] = '';
				}
				if (!file_exists($stamp)) {
					$tags['stamp'] = '';
				}

			}
			else {

				//print $rootpath.$stamp;

				if (!file_exists($rootpath.$logo)) {
					$tags['logo'] = '';
				}

				if (!file_exists($rootpath.$stamp)) {
					$tags['stamp'] = '';
				}

			}

		}

		//печать
		$tags['stamp'] = $tags['signature'] = ( !$nosignat && $tags['stamp'] != '' ) ? $tags['stamp'] : '';

		$tags['compInn']  = ( $innkpp[0] != '' ) ? $innkpp[0] : '-';
		$tags['compKpp']  = ( $innkpp[1] != '' ) ? $innkpp[1] : '-';
		$tags['compOkpo'] = ( $okog[0] != '' ) ? $okog[0] : '-';
		$tags['compOgrn'] = ( $okog[1] != '' ) ? $okog[1] : '-';

		$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
		$settings     = json_decode((string)file_get_contents($settingsFile), true);

		$tags['compBrand'] = $settings["company"];
		$tags['compSite']  = $settings["company_site"];
		$tags['compMail']  = $settings["company_mail"];
		$tags['compPhone'] = $settings["company_phone"];

		//найдем реквизиты компании клиента
		if ($clid > 0) {

			$data = get_client_recv($clid, "yes");
			//$data = json_decode( $json, true );

			$tags['castName']         = $castName = str_replace('”', '"', $data['castName']);
			$tags['castUrName']       = $castUrName = str_replace('”', '"', $data['castUrName']);
			$tags['castUrNameShort']  = str_replace('”', '"', $data['castUrNameShort']);
			$tags['castInn']          = $castInn = $data['castInn'];
			$tags['castKpp']          = $castKpp = $data['castKpp'];
			$tags['castBank']         = str_replace('”', '"', $data['castBank']);
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

			if (empty($castUrName)) {
				$tags['castUrName'] = $castUrName = $castName;
			}
			if (empty($castName)) {
				$tags['castName'] = $castName = $castUrName;
			}

			//сформируем строку Получателя
			//для счета
			$castCard = '';
			if (!empty($castName)) {
				$castCard .= $castName;
			}
			if (!empty($castInn)) {
				$castCard .= ", ИНН ".$castInn;
			}
			if (!empty($castKpp)) {
				$castCard .= ", КПП ".$castKpp;
			}
			if (!empty($castFacAddr)) {
				$castCard .= ", Факт.адрес: ".$castFacAddr;
			}
			if (!empty($invoice_chek)) {
				$castCard .= "<br>Основание: Договор №".$invoice_chek;
			}

			$tags['castCard'] = $castCard;

		}

		//если заказчик по сделке - это контакт
		elseif ($pid > 0 && $payer < 1) {
			$tags['castUrName'] = $castUrName = current_person($pid);
		}

		//название счета
		$tags['offer'] = ( $tip_credit == 'Счет-договор' ) ? '(договор-оферта)' : '';

		//спецификация по сделке
		$Speka = ( new Speka() ) -> getSpekaData($did, $rs);

		$summaItog = $summaNalog = 0;

		/**
		 * НОВОЕ. Определим суммы по спецификации
		 */
		//require_once "Speka.php";

		$spekaData = Speka ::getNalog($did);
		/**
		 * СУММЫ по спеке
		 */

		$speka = $stovar = $smaterial = $susluga = [];

		if ($tip_credit == 'По договору') {

			$nalogArray = getNalog($sum, $nalogScheme['nalog'], $otherSettings['ndsInOut']);

			//массив спецификации(без учета материалов)
			$summaInvoice = $sum;                //$Speka['summaInvoice'];
			$summaItog    = $sum;                //$Speka['summaItog'];
			$summaNalog   = $nalogArray['nalog'];//$Speka['summaNalog'];

			if ($spekaData['nalog'] == 0) {
				$summaNalog = 0;
			}

			$str['nalog'] = ( $summaNalog > 0 && $nalogScheme['nalog'] > 0 ) ? '' : num_format($summaNalog);

			$speka[] = [
				"Number"   => '1',
				"Title"    => "Оплата по договору №".$invoice_chek,
				"Artikul"  => '',
				"Comments" => '',
				"Edizm"    => '',
				"Kol"      => '-',
				"Price"    => num_format($summaInvoice),
				"Nalog"    => num_format($summaNalog),
				"Summa"    => num_format($summaInvoice),
				"Dop"      => ( $otherSettings['dop'] ) ? '-' : ''
			];

		}
		elseif ($tip_credit == 'Предварительная оплата') {

			//массив спецификации
			$summaInvoice = $sum;//$Speka['summaInvoice'];
			$summaItog    = $sum;//$Speka['summaItog'];
			$summaNalog   = $nds;//$nalogArray['nalog'];//$Speka['summaNalog'];

			if ($spekaData['nalog'] == 0) {
				$summaNalog = 0;
			}

			$poz = file_get_contents($rootpath.'/cash/'.$fpath.'templates/invoice_first.htm');
			$poz = str_replace([
				"{dog_num}",
				"{{ContractNumber}}"
			], [
				$invoice_chek,
				$invoice_chek
			], $poz);

			$str['nalog'] = ( $summaNalog >= 0 && $nalogScheme['nalog'] > 0 ) ? num_format($summaNalog) : '0,00';

			$speka[] = [
				"Number"   => '1',
				"Title"    => $poz,
				"Artikul"  => '',
				"Comments" => '',
				"Kol"      => '-',
				"Edizm"    => '-',
				"Price"    => num_format($summaInvoice),
				"Nalog"    => $nds,
				"Summa"    => num_format($summaInvoice),
				"Dop"      => ( $otherSettings['dop'] ) ? '-' : ''
			];

		}
		elseif ($tip_credit == 'Окончательная оплата') {

			//массив спецификации
			$summaInvoice = $sum;//$Speka['summaInvoice'];
			$summaItog    = $sum;//$Speka['summaItog'];
			$summaNalog   = $nds;//$nalogArray['nalog'];//$Speka['summaNalog'];

			if ($spekaData['nalog'] == 0) {
				$summaNalog = 0;
			}

			$poz = file_get_contents($rootpath.'/cash/'.$fpath.'templates/invoice_last.htm');
			$poz = str_replace([
				"{dog_num}",
				"{{ContractNumber}}"
			], [
				$invoice_chek,
				$invoice_chek
			], $poz);

			$str['nalog'] = ( $summaNalog > 0 && $nalogScheme['nalog'] > 0 ) ? num_format($summaNalog) : '';

			$speka[] = [
				"Number"   => '1',
				"Title"    => $poz,
				"Artikul"  => '',
				"Comments" => '',
				"Kol"      => '-',
				"Edizm"    => '-',
				"Price"    => num_format($summaInvoice),
				"Nalog"    => $nds,
				"Summa"    => num_format($summaInvoice),
				"Dop"      => ( $otherSettings['dop'] ) ? '-' : ''
			];

		}
		else {

			//массив спецификации
			$summaInvoice = $Speka['summaInvoice'];
			$summaItog    = $Speka['summaItog'];
			$summaNalog   = $Speka['summaNalog'];
			$pozition     = $Speka['pozition'];

			foreach ($pozition as $str) {

				$str['nalog'] = ( $str['nds'] == '' || $nalogScheme['nalog'] == 0 ) ? '0,00' : num_format($str['nds']);

				if ($spekaData['nalog'] == 0) {
					$str['nalog'] = 0;
				}

				$speka[] = [
					"Number"   => $str['num'],
					"Title"    => $str['title'],
					"Tip"      => $str['tip'],
					"Artikul"  => ( $str['artikul'] != '' && $otherSettings['dop'] ) ? $str['artikul'] : "",
					"Comments" => $str['comments'],
					"Kol"      => $str['kol'],
					"Edizm"    => $str['edizm'],
					"Price"    => num_format($str['price']),
					"Nalog"    => $str['nalog'],
					"Summa"    => num_format($str['summa']),
					"Dop"      => ( $str['dop'] != '' ) ? num_format($str['dop']) : '',
					"spid"     => $str['spid']
				];

			}

			//массив товаров
			$itogTovar  = $Speka['itogTovar'];
			$nalogTovar = $Speka['nalogTovar'];
			$tovar      = $Speka['tovar'];

			foreach ($tovar as $str) {

				$str['nalog'] = ( $str['nds'] == '' || $nalogScheme['nalog'] == 0 ) ? '0,00' : num_format($str['nds']);

				if ($spekaData['nalog'] == 0) {
					$str['nalog'] = 0;
				}

				$stovar[] = [
					"Number"   => $str['num'],
					"Title"    => $str['title'],
					"Tip"      => $str['tip'],
					"Artikul"  => ( $str['artikul'] != '' && $otherSettings['dop'] ) ? $str['artikul'] : "",
					"Comments" => $str['comments'],
					"Kol"      => $str['kol'],
					"Edizm"    => $str['edizm'],
					"Price"    => num_format($str['price']),
					"Nalog"    => $str['nalog'],
					"Summa"    => num_format($str['summa']),
					"Dop"      => ( $str['dop'] != '' ) ? num_format($str['dop']) : '',
					"spid"     => $str['spid']
				];

			}

			//массив услуг
			$itogUsluga  = $Speka['itogUsluga'];
			$nalogUsluga = $Speka['nalogUsluga'];
			$usluga      = $Speka['usluga'];

			foreach ($usluga as $str) {

				$str['nalog'] = ( $str['nds'] == '' || $nalogScheme['nalog'] == 0 ) ? '0,00' : num_format($str['nds']);

				if ($spekaData['nalog'] == 0) {
					$str['nalog'] = 0;
				}

				$susluga[] = [
					"Number"   => $str['num'],
					"Title"    => $str['title'],
					"Tip"      => $str['tip'],
					"Artikul"  => ( $str['artikul'] != '' && $otherSettings['dop'] ) ? $str['artikul'] : "",
					"Comments" => $str['comments'],
					"Kol"      => $str['kol'],
					"Edizm"    => $str['edizm'],
					"Price"    => num_format($str['price']),
					"Nalog"    => $str['nalog'],
					"Summa"    => num_format($str['summa']),
					"Dop"      => ( $str['dop'] != '' ) ? num_format($str['dop']) : '',
					"spid"     => $str['spid']
				];

			}

			//массив материалов
			$itogMaterial = $Speka['itogMaterial'];
			$material     = $Speka['material'];

			foreach ($material as $str) {

				$str['nalog'] = ( $str['nds'] == '' || $nalogScheme['nalog'] == 0 ) ? '0,00' : num_format($str['nds']);

				if ($spekaData['nalog'] == 0) {
					$str['nalog'] = 0;
				}

				$smaterial[] = [
					"Number"   => $str['num'],
					"Title"    => $str['title'],
					"Tip"      => $str['tip'],
					"Artikul"  => ( $str['artikul'] != '' && $otherSettings['dop'] ) ? $str['artikul'] : "",
					"Comments" => $str['comments'],
					"Kol"      => $str['kol'],
					"Edizm"    => $str['edizm'],
					"Price"    => num_format($str['price']),
					"Nalog"    => $str['nalog'],
					"Summa"    => num_format($str['summa']),
					"Dop"      => ( $str['dop'] != '' ) ? num_format($str['dop']) : '',
					"spid"     => $str['spid']
				];

			}

		}

		$tags['speka']    = $speka;
		$tags['tovar']    = $stovar;
		$tags['material'] = $smaterial;
		$tags['usluga']   = $susluga;

		//итоги по счету(без материалов)
		$tags['InvoiceSumma'] = num_format($summaInvoice);
		$tags['TotalSumma']   = num_format($summaInvoice);
		$tags['ItogSumma']    = num_format($summaItog);

		//итоги по счету(товары)
		$tags['ItogTovar'] = num_format($itogTovar);

		//итоги по счету(услуги)
		$tags['ItogUsluga'] = num_format($itogUsluga);

		//итоги по счету(материалы)
		$tags['ItogMaterial'] = num_format($itogMaterial);

		if ($spekaData['nalog'] == 0) {
			$summaNalog = 0;
		}

		//налоги
		$tags['nalogSumma'] = num_format($summaNalog);
		$tags['nalogName']  = ( $ndsRaschet != 'yes' ) ? "В том числе НДС" : "Налог";
		$tags['nalogTitle'] = ( $ndsRaschet != 'yes' ) ? "НДС" : "Налог";

		//налог на товары
		$tags['nalogTovar'] = num_format($nalogTovar);
		//налог на услуги
		$tags['nalogUsluga'] = num_format($nalogUsluga);

		if ($nalogScheme['nalog'] == 0 || $summaNalog == 0) {
			$tags['nalogSumma'] = "Не облагается";
		}

		if ($summaNalog == 0) {
			$tags['nalogTitle'] = '';
		}

		$tags['suff']               = ( $tip_credit == 'Счет-договор' ) ? ' (договор-оферта)' : '';
		$tags['suffix']             = $suffixInc;
		$tags['noSignature']        = ( !$nosignat ) ? '' : true;
		$tags['suffixinc']          = $suffixInc;
		$tags['dopName']            = ( $otherSettings['dop'] ) ? $otherSettings['dopName'] : '';
		$tags['dopsName']           = ( $otherSettings['dop'] ) ? $otherSettings['dopName'] : '';
		$tags['InvoiceSummaPropis'] = ( $ndsRaschet != 'yes' ) ? " ".mb_ucfirst(trim(num2str((float)$summaItog))) : " ".mb_ucfirst(trim(num2str((float)$summaInvoice)));

		//тэги по доп.полям сделки
		if ($did > 0) {

			$ddata = get_dog_info($did, "yes");
			//$ddata = json_decode( $json, true );

			$res = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_name LIKE '%input%' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order");
			while ($da = $db -> fetch($res)) {

				$tags['dealF'.$da['fld_name']] = $ddata[$da['fld_name']];

			}

			$tags['dealFtitle']       = $ddata['title'];
			$tags['dealFsumma']       = num_format($ddata['kol']);
			$tags['dealFmarga']       = num_format($ddata['marga']);
			$tags['dealFperiodStart'] = format_date_rus_name($ddata['datum_start']);
			$tags['dealFperiodEnd']   = format_date_rus_name($ddata['datum_end']);

			$deal = get_dog_info($did, "yes");

			$currency = ( new Currency() ) -> currencyInfo($deal['idcurrency']);
			$course   = ( new Currency() ) -> courseInfo($deal['idcourse']);

			$tags['currencyName']   = $currency['name'];
			$tags['currencySymbol'] = $currency['symbol'];
			$tags['currencyCourse'] = $course['course'];

		}

		//тэги по полям Заказчика
		if ($zak > 0) {

			$zclient = (array)get_client_info($zak, 'yes');

			$includ = [
				'title',
				'address',
				'phone',
				'fax',
				'mail_url',
				'site_url'
			];

			$result_k = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='client' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order");
			while ($data = $db -> fetch($result_k)) {

				if (in_array($data['fld_name'], $includ) || stripos($data['fld_name'], 'input') !== false) {

					$tags['castomerF'.$data['fld_name']] = $zclient[$data['fld_name']];

				}

			}

		}

		//тэги по полям Контакта
		if ($pid > 0) {

			$json  = get_person_info($pid);
			$pdata = json_decode($json, true);

			$includ = [
				'person',
				'ptitle',
				'tel',
				'mob',
				'mail',
				'rol'
			];

			$result_k = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='person' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order");
			while ($data = $db -> fetch($result_k)) {

				if (in_array($data['fld_name'], $includ) || stripos($data['fld_name'], 'input') !== false) {

					$tags['personF'.$data['fld_name']] = $pdata[$data['fld_name']];

				}

			}

		}

		//тэги по сотруднику
		$results            = $db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser'");
		$tags['UserName']   = $results["title"];
		$tags['UserStatus'] = $results["tip"];
		$tags['UserPhone']  = $results["phone"];
		$tags['UserMob']    = $results["mob"];
		$tags['UserEmail']  = $results["email"];

		//print_r($tags);

		if (empty($template) || ( !file_exists($rootpath.'/cash/'.$fpath.'templates/'.$template) )) {
			$template = 'invoice.tpl';
		}

		//шаблон счета
		$html = file_get_contents($rootpath.'/cash/'.$fpath.'templates/'.$template);

		// проверяем шаблон на наличие тэга
		if (str_contains($html, '{{qrcode}}')) {

			// qrcode
			$qr = self ::getQR($tags);

			$tags['qrcode'] = 'data:image/png;base64,'.base64_encode($qr);

		}

		// обработка сторонних изображений
		if (!empty($params['tagsAttached']['images'])) {

			foreach ($params['tagsAttached']['images'] as $image) {

				if (file_exists($image['file'])) {

					$tags[$image['tag']] = 'data:image/png;base64,'.base64_encode(file_get_contents($image['file']));

				}

			}

		}

		/**
		 * данные из редактора шаблонов
		 */
		if ($params['tags']) {

			$tags             = $params['tags'];
			$tags['forPRINT'] = '1';

		}

		//загружаем шаблон
		if ($tip != 'tags') {

			/**
			 * Формируем платежное поручение
			 */

			/*$output = "
				1CClientBankExchange
				Кодировка=Windows
				Отправитель=".$tags['compBankName']."
				Получатель=".$tags['castBank']."
				ДатаСоздания=".format_date_rus( datetime2date( $datum ) )."
				ВремяСоздания=".get_time( $datum )."
				ДатаНачала=".format_date_rus( datetime2date( $datum ) )."
				ДатаКонца=".format_date_rus( datetime2date( $datum ) )."
				РасчСчет=".$tags['castBankRs']."
				Документ=Платежное поручение
				СекцияДокумент=Платежное поручение
				Дата=".format_date_rus( datetime2date( $datum ) )."
				Сумма=".$sum."
				ПлательщикСчет=".$tags['castBankRs']."
				ПлательщикИНН=".$tags['castInn']."
				ПлательщикКПП=".$tags['castKpp']."
				Плательщик=".$tags['castInn']." ".$tags['compUrName']."
				Плательщик1=".$tags['compUrName']."
				ПлательщикРасчСчет=".$tags['castBankRs']."
				ПлательщикБанк1=".$tags['castBank']."
				ПлательщикБанк2=".$tags['castBank']."
				ПлательщикБИК=".$tags['castBankBik']."
				ПлательщикКорсчет=".$tags['castBankKs']."
				ПолучательСчет=".$tags['compBankRs']."
				ПолучательИНН=".$tags['compInn']."
				Получатель=".$tags['compShotName']."
				Получатель1=".$tags['compShotName']."
				ПолучательРасчСчет=".$tags['compBankRs']."
				ПолучательБанк1=".$tags['compBankName']."
				ПолучательБанк2=".$tags['compBankName']."
				ПолучательБИК=".$tags['compBankBik']."
				ПолучательКорсчет=".$tags['compBankKs']."
				ВидПлатежа=
				ВидОплаты=01
				Очередность=5
				НазначениеПлатежа=Оплата счета _№".$invoice." от ".$tags['InvoiceDate'].", без НДС
				НазначениеПлатежа1=Оплата счета _№".$invoice." от ".$tags['InvoiceDate'].", без НДС
				КонецДокумента
				КонецФайла
			";

			$payorder = "payorder_".$crid.".txt";*/

			//createDir( $rootpath."/files/payorders/" );
			//file_put_contents( $rootpath."/files/payorders/".$fpath.$payorder, $output );

			//print_r($tags);

			/**
			 * Преобразование валюты
			 */
			$deal = get_dog_info($did, "yes");
			if ((int)$deal['idcourse'] > 0) {

				$tags = Currency ::currencyConvertSpeka($tags, (int)$deal['idcourse']);

			}

			//обработка через шаблонизатор
			Mustache_Autoloader ::register();
			$m              = new Mustache_Engine();
			$tags['suffix'] = $m -> render($tags['suffix'], $tags);

			$m    = new Mustache_Engine();
			$html = $m -> render($html, $tags);

			//выводим на печать
			if ($tip == 'print') {
				return $html;
			}

			//генерируем PDF
			if ($tip == 'pdf') {

				$options = new Options();

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
				//$options -> set( 'fontCache', $rootpath.'/cash/dompdf/dompdf_font_family_cache.dist.php' );
				$options -> set( 'fontDir', $rootpath.'/cash/dompdf/' );
				//$options -> set( 'fontDir', $rootpath.'/vendor/dompdfFontsCastom/' );
				$options -> set( 'tempDir', $rootpath.'/cash/dompdf/' );
				$options -> set( 'dpi', 100 );
				*/

				$options -> set('A4', 'portrait');
				$options -> set('defaultPaperSize ', 'A4');
				$options -> set('fontHeightRatio', '0.9');
				$options -> set('defaultMediaType ', 'print');
				$options -> set('isHtml5ParserEnabled', true);
				$options -> set('isFontSubsettingEnabled', true);
				$options -> set('isRemoteEnabled', true);
				$options -> set('defaultFont', 'PT Sans');
				$options -> set('rootDir', $rootpath.'/vendor/dompdf/dompdf/');
				$options -> set('chroot', $rootpath.'/cash/dompdf/');
				$options -> set('fontCache', $rootpath.'/cash/dompdf/');
				$options -> set('fontDir', $rootpath.'/vendor/dompdfFontsCastom/');
				$options -> set('tempDir', $rootpath.'/cash/');
				$options -> set('dpi', 100);

				$dompdf = new Dompdf($options);
				$dompdf -> loadHtml($html);
				$dompdf -> render();
				$output = $dompdf -> output();


				$invoice = str_replace("/", "-", $invoice);

				file_put_contents($rootpath."/files/".$fpath."invoice_".$crid.".pdf", $output);
				$file = $rootpath."/files/".$fpath."invoice_".$crid.".pdf";

				$pdfname = 'Счет №'.$invoice.' от '.format_date_rus_name(substr($datum, 0, 10))." года";

				if ($download == "yes") {

					$fname = 'Счет №'.$invoice.' от '.format_date_rus_name(substr($datum, 0, 10))." года";
					header("Content-Type: application/pdf");
					header("Content-Disposition: attachment; filename=".str_replace(" ", "_", $fname).".pdf");
					@readfile($file);

				}
				elseif ($download == "link") {

					return "/files/invoice_".$crid.".pdf";

				}
				elseif ($download == "content") {

					return $output;

				}
				elseif (file_exists($file)) {

					if ($download == "view") {

						header('Content-Type: application/pdf');
						header('Content-Disposition: inline; filename="'.$pdfname.'"');
						header('Content-Transfer-Encoding: binary');
						header('Accept-Ranges: bytes');

						readfile($file);

					}
					/*elseif ( $download == "url" ) {


					}*/
					else {
						return "invoice_".$crid.".pdf";
					}

				}
				else {
					return "Error";
				}

			}

			//генерирует DOCX, не работает
			elseif ($tip == 'docx') {

				$file = $rootpath."/files/".$fpath."invoice_".$crid.".docx";

			}

			return true;

		}

		return $tags;

	}

	/**
	 * Возвращает массив выставленных счетов по сделке
	 *
	 * @param $did
	 *
	 * @return array
	 * @category Core
	 * @package  Func
	 */
	public function getCreditData($did): array {

		$sqlname  = $this -> sqlname;
		$identity = $this -> identity;
		$db       = $this -> db;

		$invoice = [];

		$res = $db -> query("
			SELECT 
			    COALESCE(invoice, 0) AS invoice, 
			    SUM(summa_credit) AS summa,
			    MAX(datum_credit) AS datum_credit
			FROM {$sqlname}credit 
			WHERE 
			    did = '$did' AND 
			    identity = '$identity' 
			GROUP BY 1
			ORDER BY MAX(datum_credit) DESC
		");
		//print $db -> lastQuery();
		while ($data = $db -> fetch($res)) {

			$r = $db -> query("SELECT * FROM {$sqlname}credit WHERE COALESCE(invoice, 0) = '$data[invoice]' and did = '$did' and identity = '$identity'");
			while ($da = $db -> fetch($r)) {

				$invoice[] = [
					"crid"       => (int)$da['crid'],
					"crid_main"  => (int)$data['crid'],
					"invoice"    => $da['invoice'],
					"summa"      => $da['summa_credit'],
					"summaTotal" => (float)$data['summa'],
					"nds"        => (float)$da['nds_credit'],
					"do"         => $da['do'],
					"dcreate"    => $da['datum'],
					"dplan"      => $da['datum_credit'],
					"dfact"      => $da['invoice_date'],
					"iduser"     => $da['iduser'],
					"clid"       => (int)$da['clid'],
					"pid"        => (int)$da['pid'],
					"did"        => (int)$da['did'],
					"rs"         => (int)$da['rs'],
					"tip"        => $da['tip'],
					"suffix"     => $da['suffix'],
					"contract"   => $da['invoice_chek'],
					"day"        => round(diffDate2($da['datum_credit']))
				];

			}

		}

		return $invoice;

	}

	/**
	 * Выдает массив данных по шаблонам счетов
	 *
	 * @param null $id - id шаблона
	 * @param null $file - файл шаблона
	 *
	 *                   - если не указаны, то в ответе приходит массив по всем шаблонам
	 *                   - если указан только id или file, то возвращается информация по конкретному шаблону
	 *                   - все данные возвращаются в подмассивах
	 *
	 * @return array - ответ
	 *               - **id** - id записи
	 *               - **title** - название
	 *               - **file** - файл
	 *               - **typeid** - id типа документа
	 */
	public static function getTemplates($id = NULL, $file = NULL): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$result = [];

		if (!$id && !$file) {

			$ires = $db -> query("SELECT * FROM {$sqlname}contract_temp WHERE typeid IN (SELECT id FROM {$sqlname}contract_type WHERE type IN ('invoice') AND identity = '$identity') AND identity = '$identity' ORDER by title");
			while ($data = $db -> fetch($ires)) {

				$result[(int)$data['id']] = [
					"id"     => (int)$data['id'],
					"title"  => $data['title'],
					"file"   => $data['file'],
					"typeid" => (int)$data['typeid'],
				];

			}

		}
		elseif ($file) {

			$data     = $db -> getRow("SELECT * FROM {$sqlname}contract_temp WHERE file = '$file' AND identity = '$identity' ORDER by title");
			$result[] = [
				"id"     => (int)$data['id'],
				"title"  => $data['title'],
				"file"   => $data['file'],
				"typeid" => (int)$data['typeid'],
			];

		}
		else {

			$data     = $db -> getRow("SELECT * FROM {$sqlname}contract_temp WHERE id = '$id' AND identity = '$identity'");
			$result[] = [
				"id"     => (int)$data['id'],
				"title"  => $data['title'],
				"file"   => $data['file'],
				"typeid" => (int)$data['typeid'],
			];

		}

		return $result;

	}

	/**
	 * Генерация QR-кода в виде строки
	 *
	 * @param array $tags - compUrName, compBankRs, compBankName, compBankBik, compBankKs, compInn, compKpp
	 *                      InvoiceSumma, Invoice, InvoiceDate, nalogName, nalogSumma
	 * @return string
	 */
	public static function getQR(array $tags): string {

		// qrcode
		$renderer = new ImageRenderer(new RendererStyle(400), new ImagickImageBackEnd());
		$writer   = new Writer($renderer);

		$kpp = $tags['compKpp'] == '0' ? "" : $tags['compKpp'];

		//$qr = $writer -> writeString( "ST00011|Name=".mb_convert_encoding( trim( str_replace( "”", "\"", $tags[ 'compUrName' ] ) ), "UTF-8", "ISO-8859-1" )."|PersonalAcc=".mb_convert_encoding( trim( $tags[ 'compBankRs' ] ), "UTF-8", "ISO-8859-1" )."|BankName=".mb_convert_encoding( trim( str_replace( "”", "\"", $tags[ 'compBankName' ] ) ), "UTF-8", "ISO-8859-1" )."|BIC=".mb_convert_encoding( trim( $tags[ 'compBankBik' ] ), "UTF-8", "ISO-8859-1" )."|CorrespAcc=".mb_convert_encoding( trim( $tags[ 'compBankKs' ] ), "UTF-8", "ISO-8859-1" )."|PayeeINN=".mb_convert_encoding( trim( $tags[ 'compInn' ] ), "UTF-8", "ISO-8859-1" )."|KPP=".mb_convert_encoding( trim( $tags[ 'compKpp' ] ), "UTF-8", "ISO-8859-1" )."|Sum=".($summaInvoice * 100)."|Purpose=".mb_convert_encoding( trim( str_replace( "”", "\"", "Оплата счета _№".$invoice." от ".$tags[ 'InvoiceDate' ]." ".$tags[ 'nalogName' ]." ".$tags[ 'nalogSumma' ] ) ), "UTF-8", "ISO-8859-1" )."" );

		//$writer -> writeFile( "ST00012|Name=".trim( str_replace( "”", "\"", $tags[ 'compUrName' ] ) )."|PersonalAcc=".trim( $tags[ 'compBankRs' ] )."|BankName=".trim( str_replace( "”", "\"", $tags[ 'compBankName' ] ) )."|BIC=".trim( $tags[ 'compBankBik' ] )."|CorrespAcc=".trim( $tags[ 'compBankKs' ] )."|PayeeINN=".trim( $tags[ 'compInn' ] )."|KPP=".trim( $kpp )."|Sum=".($tags[ 'InvoiceSumma' ] * 100)."|Purpose=".trim( str_replace( "”", "\"", "Оплата счета _№".$tags[ 'Invoice' ]." от ".$tags[ 'InvoiceDate' ]." ".$tags[ 'nalogName' ]." ".$tags[ 'nalogSumma' ] ) ), $root."/cash/qrcode-invoice.png", "UTF-8" );

		return $writer -> writeString("ST00012|Name=".trim(str_replace("”", "\"", $tags['compUrName']))."|PersonalAcc=".trim($tags['compBankRs'])."|BankName=".trim(str_replace("”", "\"", $tags['compBankName']))."|BIC=".trim($tags['compBankBik'])."|CorrespAcc=".trim($tags['compBankKs'])."|PayeeINN=".trim($tags['compInn'])."|KPP=".trim($kpp)."|Sum=".( pre_format($tags['InvoiceSumma']) * 100 )."|Purpose=".trim(str_replace("”", "\"", "Оплата счета _№".$tags['Invoice']." от ".$tags['InvoiceDate']." ".$tags['nalogName']." ".$tags['nalogSumma'])), "UTF-8");

	}

}