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

use SafeMySQL;

/**
 * Класс для работы с KPI
 *
 * Class Metrics
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Metrics {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $tmzone, $rootpath, $other;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Расширенный ответ
	 *
	 * @var array
	 */
	public $response = [];

	public $error;
	/**
	 * @var false|string
	 */
	public $otherSettings;

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Chats constructor
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> rootpath = $rootpath;
		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['UserID'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> other    = $GLOBALS['other'];

		$this -> db = new SafeMySQL( $this -> opts );

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set( $this -> tmzone );

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
	 * Возвращает список названий метрик
	 *
	 * @return array - массив параметров, где
	 *               - ключ - обозначение параметра
	 *               - значение - расшифровка
	 */
	public static function MetricList(): array {

		return [
			"activities"       => "Кол-во Активностей",
			"calls"            => "Звонки, Количество (все направления)",
			"callsDuration"    => "Звонки, Продолжительность (все направления)",
			"callsOut"         => "Звонки Исходящие, Количество",
			"callsOutDuration" => "Звонки Исходящие, Продолжительность",
			"callsIn"          => "Звонки Входящие, Количество",
			"callsInDuration"  => "Звонки Входящие, Продолжительность",
			"clientsNew"       => "Новые Клиенты, Количество",
			"dealsNewAll"      => "Новые Сделки, Количество",
			"dealsNewDouble"   => "Повторные Сделки, Количество",
			"dealsCloseAll"    => "Закрытые Сделки, Количество",
			"dealsSumAll"      => "Новые Сделки, Сумма",
			"dealsSumDouble"   => "Повторные Сделки, Сумма",
			"dealsSumClose"    => "Закрытые Сделки, Сумма",
			"contractsNew"     => "Документов, Количество",
			"invoicesNewCount" => "Выставленные Счета, Количество",
			"invoicesNewSum"   => "Выставленные Счета, Сумма",
			"invoicesDoCount"  => "Оплаченные Счета, Количество",
			"invoicesDoSum"    => "Оплаченные Счета, Сумма",
			"leadsNewCount"    => "Новые Заявки, Количество",
			"leadsDoCount"     => "Обработанные Заявки, Количество",
			"productCount"     => "Продажи продукта, Количество",
			"productSumma"     => "Продажи продукта, Сумма",
		];

	}

	/**
	 * Дополнительные фильтры для параметра
	 *
	 * @param string $main - название параметра
	 *
	 * @return array - массив параметров, где
	 *               - ключ - обозначение параметра ( см. MetricList() )
	 *               - значение - массив возможных суб.парметров
	 */
	public static function MetricSubList(string $main = ''): array {

		$list = [];

		switch ($main) {

			case 'dealsNewAll':
			case 'dealsNewDouble':
			case 'dealsCloseAll':
			case 'dealsSumAll':
			case 'dealsSumDouble':
			case 'dealsSumClose':
			case 'invoicesNewCount':
			case 'invoicesNewSum':
			case 'invoicesDoCount':
			case 'invoicesDoSum':

				$list = Guides ::DealType();

			break;
			case 'contractsNew':

				$list = Guides ::Docstatus( $main );

			break;
			case 'productCount':
			case 'productSumma':

				//$list = Guides ::Pricecat($main);

			break;
			case 'calls':
			case 'callsDuration':
			case 'callsOut':
			case 'callsOutDuration':
			case 'callsIn':
			case 'callsInDuration':

			break;

		}

		return $list;

	}

	/**
	 * Возвращает список названий единиц измерения метрик
	 *
	 * @return array - массив параметров, где
	 *               - ключ - обозначение параметра ( см. MetricList() )
	 *               - значение - единица измерения
	 */
	public static function metricEdizm(): array {

		return [
			"activities"       => "шт.",
			"calls"            => "шт.",
			"callsDuration"    => "мин.",
			"callsOut"         => "шт.",
			"callsOutDuration" => "мин.",
			"callsIn"          => "шт.",
			"callsInDuration"  => "мин.",
			"clientsNew"       => "шт.",
			"dealsNewAll"      => "шт.",
			"dealsNewDouble"   => "шт.",
			"dealsCloseAll"    => "шт.",
			"dealsSumAll"      => "руб.",
			"dealsSumDouble"   => "руб.",
			"dealsSumClose"    => "руб.",
			"contractsNew"     => "шт.",
			"invoicesNewCount" => "шт.",
			"invoicesNewSum"   => "руб.",
			"invoicesDoCount"  => "шт.",
			"invoicesDoSum"    => "руб.",
			"leadsNewCount"    => "шт.",
			"leadsDoCount"     => "шт.",
			"productCount"     => "шт.",
			"productSumma"     => "руб.",
		];

	}

	/**
	 * Возвращает элементы справочников по типу
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public static function getElements(string $type = ''): array {

		$elements = [];

		$Class = [
			"activities"       => "Activities",
			"calls"            => "CallResults",
			"callsDuration"    => "CallResults",
			"callsOut"         => "CallResults",
			"callsOutDuration" => "CallResults",
			"callsIn"          => "CallResults",
			"callsInDuration"  => "CallResults",
			"clientsNew"       => "Industry",
			"dealsNewAll"      => "Direction",
			"dealsNewDouble"   => "Direction",
			"dealsCloseAll"    => "Direction",
			"dealsSumAll"      => "Direction",
			"dealsSumDouble"   => "Direction",
			"dealsSumClose"    => "Direction",
			"contractsNew"     => "Doctype",
			"invoicesNewCount" => "Direction",
			"invoicesNewSum"   => "Direction",
			"invoicesDoCount"  => "Direction",
			"invoicesDoSum"    => "Direction",
			"leadsNewCount"    => "Clientpath",
			"leadsDoCount"     => "Clientpath",
			"productCount"     => "Pricecat",
			"productSumma"     => "Pricecat"
		];

		if ( array_key_exists( $type, $Class ) ) {

			$var = $Class[ $type ];

			$elements = Guides ::$var();

		}

		return $elements;

	}

	/**
	 * Возвращает список базовых KPI
	 *
	 * @param int|null $id - если указан, выводит параметры конкретного KPI
	 *
	 * @return array - ответ
	 *          - int id - id записи
	 *          - str title - название параметра
	 *          - str tip - тип параметра ( см. MetricList() )
	 *          - str tipTitle - расшифровка параметра
	 *          - str values - значения, разделенные (,)
	 *          - str subvalues - дополнительные значения, разделенные (,)
	 */
	public static function getKPIs(int $id = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$list = [];

		$sort = ($id > 0) ? "id = '$id' AND" : "";

		$tips = self ::MetricList();

		$result = $db -> query( "SELECT * FROM {$sqlname}kpibase WHERE $sort identity = '$identity' ORDER BY title" );
		while ($data = $db -> fetch( $result )) {

			$list[] = [
				"id"        => $data['id'],
				"title"     => $data['title'],
				"tip"       => $data['tip'],
				"tipTitle"  => strtr( $data['tip'], $tips ),
				"values"    => $data['values'],
				"subvalues" => $data['subvalues'],
			];

		}

		if ( $id > 0 ) {
			$list = $list[0];
		}

		return $list;

	}

	/**
	 * Возвращает список KPI сотрудника по iduser
	 * или значения конкретного KPI по id записи
	 *
	 * @param array $params - параметры
	 *                      - int id - id записи KPI
	 *                      - int kpi - id показателя
	 *                      - int iduser - id пользователя
	 *                      - str year - год
	 *                      - bool as_money - форматирование значение показателя в сумму (разделители разрядов)
	 *
	 * @return array - ответ
	 *              - int id - id записи
	 *              - int kpi - id записи KPI
	 *              - str kpititle - название базового параметра
	 *              - str year - год
	 *              - str value - значение
	 *              - int iduser - id сотрудника
	 *              - str tip - тип параметра ( см. MetricList() )
	 *              - str tipTitle - расшифровка параметра
	 *              - str edizm - единица измерения
	 *              - isPersonal - признак персонального показателя
	 *              - period - обозначение периода ( day, week, month, quartal, year )
	 *              - str periodname - расшифровка периода
	 */
	public static function getUserKPI(array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];
		$lang     = $GLOBALS['lang'];

		$list = [];
		$sort = '';

		$tips  = self ::MetricList();
		$edizm = self ::metricEdizm();

		if ( (int)$params['id'] > 0 ) {
			$sort .= "id = '$params[id]' AND ";
		}
		if ( (int)$params['kpi'] > 0 ) {
			$sort .= "kpi = '$params[kpi]' AND ";
		}
		if ( (int)$params['iduser'] > 0 ) {
			$sort .= "iduser = '$params[iduser]' AND ";
		}
		if ( (int)$params['year'] > 0 ) {
			$sort .= "year = '$params[year]' AND ";
		}

		// сезонные коэффициенты к плану
		//$kpiSeason = ( new Metrics ) -> getSeason($params[ 'year' ]);

		//print "SELECT * FROM {$sqlname}kpi WHERE $sort identity = '$identity'";

		$rez = $db -> query( "SELECT * FROM {$sqlname}kpi WHERE $sort identity = '$identity'" );
		while ($da = $db -> fetch( $rez )) {

			$kpi = $db -> getRow( "SELECT tip, title FROM {$sqlname}kpibase WHERE id = '$da[kpi]' AND identity = '$identity'" );

			// применяем сезонный коэффициент к плану
			//$da['val'] = $da['val'] * $kpiSeason[''];

			$list[] = [
				"id"         => (int)$da['id'],
				"kpi"        => (int)$da['kpi'],
				"kpititle"   => $kpi['title'],
				"year"       => (int)$da['year'],
				"value"      => ($params['as_money']) ? num_format( $da['val'] ) : $da['val'],
				"iduser"     => (int)$da['iduser'],
				"tip"        => $kpi['tip'],
				"tipTitle"   => strtr( $kpi['tip'], $tips ),
				"edizm"      => $edizm[ $kpi['tip'] ],
				"isPersonal" => $da['isPersonal'] != 0,
				"period"     => $da['period'],
				"periodname" => $lang['period'][ $da['period'] ]
			];

		}

		if ( (int)$params['id'] > 0 || (int)$params['kpi'] > 0 ) {
			return (array)$list[0];
		}

		return $list;

	}

	/**
	 * Возвращает список сотрудников
	 * у которых есть KPI с указанным id
	 *
	 * @param int         $id
	 * @param string|null $element - возвращает указанное поле в качестве значения или iduser (если не указано)
	 *
	 * @return array
	 */
	public static function getKPIUsers(int $id = 0, string $element = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$db       = $GLOBALS['db'];

		$KPI     = [];
		$element = (empty( $element )) ? 'iduser' : $element;

		$rez = $db -> query( "SELECT * FROM {$sqlname}kpi WHERE kpi = '$id' AND identity = '$identity'" );
		while ($da = $db -> fetch( $rez )) {

			$KPI[] = $da[ $element ];

		}

		//print_r($KPI);

		return $KPI;

	}

	/**
	 * Сохраняет базовый KPI
	 *
	 * @param int   $id
	 * @param array $params - параметры
	 *
	 * @return array - ответ
	 *      - str result = Изменено/Добавлено
	 *      - int data = id
	 */
	public function saveKPIbase(int $id = 0, array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ( is_array( $params['values'] ) ) {
			$params['values'] = yimplode( ",", $params['values'] );
		}

		if ( is_array( $params['subvalues'] ) ) {
			$params['subvalues'] = yimplode( ",", $params['subvalues'] );
		}

		if ( $id < 1 ) {

			$db -> query( "INSERT INTO {$sqlname}kpibase SET ?u", [
				'tip'       => $params['tip'],
				'title'     => $params['title'],
				'values'    => $params['values'],
				'subvalues' => $params['subvalues'],
				'identity'  => $identity
			] );
			$id = $db -> insertId();

			$result = 'Добавлено';

		}
		else {

			$db -> query( "UPDATE {$sqlname}kpibase SET ?u WHERE id = '$id'", [
				'tip'       => $params['tip'],
				'title'     => $params['title'],
				'values'    => $params['values'],
				'subvalues' => $params['subvalues'],
				'identity'  => $identity
			] );

			$result = 'Изменено';

		}

		return [
			"id"     => $id,
			"result" => $result
		];

	}

	/**
	 * Удаление базового показателя
	 *
	 * @param int $id
	 *
	 * @return string $result
	 */
	public function deleteKPIbase(int $id = 0): string {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		if ( $id > 0 ) {

			$db -> query( "DELETE FROM {$sqlname}kpibase WHERE id = '$id'" );
			$result = 'Удалено';

			//удалим базовый показатель у сотрудников
			$db -> query( "DELETE FROM {$sqlname}kpi WHERE kpi = '$id'" );

		}
		else {
			$result = 'Не указан ID параметра';
		}

		return $result;

	}

	/**
	 * Сохраняет KPI для сотрудника
	 *
	 * @param int   $id
	 * @param array $params - параметры
	 *                      - int kpi - id показателя
	 *                      - str year - год
	 *                      - str period - период расчета ( day, week, month, quartal, year )
	 *                      - int iduser - id сотрудника
	 *                      - str values - значение показателя
	 *                      - bool isPersonal - признак персонального показателя
	 *
	 * @return array - ответ
	 *          - str result = Изменено/Добавлено
	 *          - int data = id
	 */
	public function saveKPI(int $id = 0, array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ( is_array( $params['val'] ) ) {
			$params['val'] = yimplode( ",", $params['val'] );
		}

		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}kpi SET ?u", [
				'kpi'        => (int)$params['kpi'],
				'year'       => (int)$params['year'],
				'period'     => $params['period'],
				'iduser'     => (int)$params['iduser'],
				'val'        => $params['val'],
				'isPersonal' => $params['isPersonal'],
				'identity'   => $identity
			] );
			$id = $db -> insertId();

			$result = 'Добавлено';

		}
		else {

			$db -> query( "UPDATE {$sqlname}kpi SET ?u WHERE id = '$id'", [
				'kpi'        => (int)$params['kpi'],
				'year'       => (int)$params['year'],
				'period'     => $params['period'],
				'iduser'     => (int)$params['iduser'],
				'val'        => $params['val'],
				'isPersonal' => $params['isPersonal'],
				'identity'   => $identity
			] );

			$result = 'Изменено';

		}

		return [
			"id"     => $id,
			"result" => $result
		];

	}

	/**
	 * Удаление показателя сотрудника
	 *
	 * @param int $id - id записи KPI
	 *
	 * @return string - ответ
	 *      - good result = Удалено
	 *      - error result = Не указан ID параметра
	 */
	public function deleteKPI(int $id = 0): string {

		$sqlname = $this -> sqlname;
		$db      = $this -> db;

		if ( $id > 0 ) {

			$db -> query( "DELETE FROM {$sqlname}kpi WHERE id = '$id'" );
			$result = 'Удалено';

		}
		else {
			$result = 'Не указан ID параметра';
		}

		return $result;

	}

	/**
	 * Расчет параметров для конкретного пользователя для конкретного показателя
	 *
	 * @param int          $iduser - id сотрудника
	 * @param int          $id     - id показателя ( см. getKPIs() или getUserKPI() )
	 * @param string       $period - период, DEFAULT = month
	 * @param string|array $datum  - дата, DEFAULT = ''
	 *
	 * @return integer $result
	 */
	public function calculateKPI(int $iduser = 0, int $id = 0, string $period = 'month', $datum = NULL) {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$result = $sort = '';
		$year   = (int)date( 'Y' );
		$month  = (int)date( 'm' );
		$day    = (int)date( 'd' );

		// print "$period\n";

		//если указан период
		if ( $period != '' && ($datum == '' || empty( $datum )) ) {

			if ( $period === 'quartal' ) {
				$period = 'quart';
			}

			if ( $period === 'week' ) {
				$period = 'calendarweek';
			}

			$nperiod = getPeriod( $period );
			$dStart  = $nperiod[0]." 00:00:00";
			$dEnd    = $nperiod[1]." 23:59:59";

			//print $dStart." : ".$dEnd."<br>";

		}
		//если указан и период и дата
		//то считаем
		elseif ( is_array( $datum ) && !empty( $datum ) ) {

			$dStart = $datum[0]." 00:00:00";
			$dEnd   = $datum[1]." 23:59:59";

		}
		//если указана дата и дата как строка
		elseif ( is_string( $datum ) && $datum != '' ) {

			$year = (int)get_year( $datum );

			$dStart = $datum." 00:00:00";
			$dEnd   = $datum." 23:59:59";

			//print $dStart." : ".$dEnd."<br>";

		}
		else {

			$dStart = "$year-$month-$day 00:00:00";
			$dEnd   = "$year-$month-$day 23:59:59";

			//print $dStart." : ".$dEnd."<br>";

		}

		//требуемый базовый показатель
		$kpi = self ::getKPIs( $id );

		//print_r($kpi);

		//значения показателя для сотрудника
		$userKPI = self ::getUserKPI( [
			'iduser' => $iduser,
			'year'   => $year,
			'kpi'    => $id
		] );

		//if($id == 18)
		//print_r($userKPI);

		//todo: этот параметр надо внести в KPI сотрудника
		$isPersonal = $userKPI['isPersonal'];

		//print_r($userKPI);

		$isort = ($isPersonal) ? " AND iduser = '$iduser'" : " AND iduser IN (".yimplode( ",", get_people( $iduser, 'yes' ) ).")";
		$usort = ($isPersonal) ? " = '$iduser'" : " IN (".yimplode( ",", get_people( $iduser, 'yes' ) ).")";
		//$users = ($isPersonal == true) ? array($iduser) : get_people($iduser, 'yes');

		//расчет параметров для
		switch ($userKPI['tip']) {

			case 'activities':

				if ( $kpi['values'] != '' ) {

					$tips = $db -> getCol( "SELECT title FROM {$sqlname}activities WHERE id IN (".$kpi['values'].") AND identity = '$identity'" );
					if ( !empty( $tips ) ) {
						$sort .= " {$sqlname}history.tip IN (".yimplode( ",", $tips, "'" ).") AND ";
					}

				}

				/*
				$result = $db -> getOne("
					SELECT COUNT(*) 
					FROM {$sqlname}history
					WHERE 
						{$sqlname}history.iduser $usort AND 
						{$sqlname}history.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(
							(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) = 0 OR
							(
								(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) > 0 AND
								(SELECT status FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid AND {$sqlname}tasks.status = 1)
							)
						) AND
						$sort 
						{$sqlname}history.identity = '$identity'
					") + 0;
				*/

				$result = (int)$db -> getOne( "
					SELECT COUNT({$sqlname}history.cid) 
					FROM {$sqlname}history
					WHERE 
						{$sqlname}history.iduser $usort AND 
						{$sqlname}history.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(
							(SELECT COUNT(*) FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) = 0 OR
							(SELECT status FROM {$sqlname}tasks WHERE {$sqlname}tasks.cid = {$sqlname}history.cid) = 1
						) AND
						$sort 
						{$sqlname}history.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'calls':

				if ( $kpi['values'] != '' ) {

					$tips = yexplode( ",", $kpi['values'] );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						COUNT({$sqlname}callhistory.id) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct IN ('outcome','income') AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'callsDuration':

				if ( $kpi['values'] != '' ) {

					$tips = yexplode( ",", $kpi['values'] );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (float)$db -> getOne( "
					SELECT 
						SUM(sec) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct IN ('outcome','income') AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				$result = round( $result / 60 );

			break;
			case 'callsOut':

				if ( $kpi['values'] != '' ) {

					$tips = yexplode( ",", $kpi['values'] );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						COUNT({$sqlname}callhistory.id) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'outcome' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

			break;
			case 'callsOutDuration':

				if ( $kpi['values'] != '' ) {

					$tips = yexplode( ",", $kpi['values'] );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (float)$db -> getOne( "
					SELECT 
						SUM(sec) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'outcome' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				$result = round( $result / 60 );

			break;
			case 'callsIn':

				if ( $kpi['values'] != '' ) {

					$tips = yexplode( ",", $kpi['values'] );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						COUNT({$sqlname}callhistory.id) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'income' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

			break;
			case 'callsInDuration':

				if ( $kpi['values'] != '' ) {

					$tips = yexplode( ",", $kpi['values'] );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (float)$db -> getOne( "
					SELECT 
						SUM(sec) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'income' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				$result = round( $result / 60 );

			break;
			case 'clientsNew':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}clientcat.idcategory IN (".$kpi['values'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT({$sqlname}clientcat.clid) 
					FROM {$sqlname}clientcat 
					WHERE 
						{$sqlname}clientcat.creator $usort AND 
						{$sqlname}clientcat.date_create BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}clientcat.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsNewAll':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT({$sqlname}dogovor.did) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsSumAll':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT SUM({$sqlname}dogovor.kol) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsNewDouble':

				if ( $kpi['values'] != '' ) {
					$sort .= " direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT({$sqlname}dogovor.did) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor AS dg WHERE dg.clid = {$sqlname}dogovor.clid AND identity = '$identity' $isort) > 1 AND
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

			break;
			case 'dealsSumDouble':

				if ( $kpi['values'] != '' ) {
					$sort .= " direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT SUM({$sqlname}dogovor.kol) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor AS dg WHERE dg.clid = {$sqlname}dogovor.clid AND identity = '$identity' $isort) > 1 AND
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsCloseAll':

				if ( $kpi['values'] != '' ) {
					$sort .= " direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT({$sqlname}dogovor.did) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.iduser $usort AND 
						close = 'yes' AND 
						datum_close BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) > 1 AND
						$sort 
						identity = '$identity'
					" );

			break;
			case 'dealsSumClose':

				if ( $kpi['values'] != '' ) {
					$sort .= " direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT SUM({$sqlname}dogovor.kol) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.iduser $usort AND 
						close = 'yes' AND 
						datum_close BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) > 1 AND
						$sort 
						identity = '$identity'
					" );

			break;
			case 'contractsNew':

				if ( $kpi['values'] != '' ) {
					$sort .= $sqlname."contract.idtype IN (".$kpi['values'].") AND ";
				}

				//если суб-параметры указаны
				if ( $kpi['subvalues'] != '' ) {

					$result = (int)$db -> getOne( "
						SELECT 
							COUNT({$sqlname}contract_statuslog.id) 
						FROM {$sqlname}contract_statuslog
							LEFT JOIN {$sqlname}contract ON {$sqlname}contract_statuslog.deid = {$sqlname}contract.deid
						WHERE 
							{$sqlname}contract_statuslog.iduser $usort AND 
							{$sqlname}contract_statuslog.status IN (".$kpi['subvalues'].") AND
							{$sqlname}contract_statuslog.datum BETWEEN '$dStart' AND '$dEnd' AND 
							$sort 
							{$sqlname}contract_statuslog.identity = '$identity'
							GROUP BY {$sqlname}contract_statuslog.deid
							ORDER BY {$sqlname}contract_statuslog.datum DESC
						" );

				}
				else {

					$result = (int)$db -> getOne( "
						SELECT 
							COUNT(*) 
						FROM {$sqlname}contract
						WHERE 
							{$sqlname}contract.iduser $usort AND 
							{$sqlname}contract.datum BETWEEN '$dStart' AND '$dEnd' AND
							$sort 
							{$sqlname}contract.identity = '$identity'
						" );

				}

				//print $db -> lastQuery()."<br>";

			break;
			case 'invoicesNewCount':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT
						COUNT({$sqlname}credit.crid)
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) and
						$sort 
						{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'invoicesNewSum':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT
						SUM({$sqlname}credit.summa_credit) as count
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) AND
						$sort 
						{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'invoicesDoCount':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT
						COUNT({$sqlname}credit.crid)
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.do = 'on' AND
						{$sqlname}credit.invoice_date BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) and
						$sort 
						{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'invoicesDoSum':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT
						SUM({$sqlname}credit.summa_credit) as count
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.do = 'on' AND
						{$sqlname}credit.invoice_date BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) AND
						$sort 
						{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'leadsNewCount':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}leads.clientpath IN (".$kpi['values'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT
						COUNT({$sqlname}leads.id)
					FROM {$sqlname}leads
					WHERE
						{$sqlname}leads.datum BETWEEN '$dStart' AND '$dEnd' AND 
						-- {$sqlname}leads.iduser $usort AND
						$sort
						{$sqlname}leads.identity = '$identity'
				" );

			break;
			case 'leadsDoCount':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}leads.clientpath IN (".$kpi['values'].") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT
						COUNT({$sqlname}leads.id) as count
					FROM {$sqlname}leads
					WHERE
						{$sqlname}leads.status IN (2,3) AND
						{$sqlname}leads.datum_do BETWEEN '$dStart' AND '$dEnd' AND 
						{$sqlname}leads.iduser $usort AND
						$sort
						{$sqlname}leads.identity = '$identity'
				" );

			break;
			case 'productCount':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}price.pr_cat IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}speca.prid IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT
						SUM({$sqlname}speca.kol) as count
					FROM {$sqlname}speca
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}speca.did = {$sqlname}dogovor.did
						LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
					WHERE
						{$sqlname}dogovor.iduser $usort AND
						{$sqlname}dogovor.close = 'yes' AND
						{$sqlname}dogovor.datum_close BETWEEN '$dStart' AND '$dEnd' AND 
						$sort
						{$sqlname}dogovor.identity = '$identity'
				" );

				//print $db -> lastQuery();

			break;
			case 'productSumma':

				if ( $kpi['values'] != '' ) {
					$sort .= " {$sqlname}speca.prid IN (".$kpi['values'].") AND ";
				}
				if ( $kpi['subvalues'] != '' ) {
					$sort .= " {$sqlname}speca.prid IN (".$kpi['subvalues'].") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT
						SUM({$sqlname}speca.price * {$sqlname}speca.kol) as count
					FROM {$sqlname}speca
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}speca.did = {$sqlname}dogovor.did
						LEFT JOIN {$sqlname}price ON {$sqlname}speca.prid = {$sqlname}price.n_id
					WHERE
						{$sqlname}dogovor.iduser $usort AND
						{$sqlname}dogovor.close = 'yes' AND
						{$sqlname}dogovor.datum_close BETWEEN '$dStart' AND '$dEnd' AND 
						$sort
						{$sqlname}dogovor.identity = '$identity'
				" );

			break;

		}

		//$this -> users = $users;

		//$result += 0;

		return $result;

	}

	/**
	 * Расчет показателей по типу
	 *
	 * @param int    $iduser
	 * @param string $tip    - MetricList()
	 * @param array  $params - параметры
	 *                       - str period - именованный период
	 *                       - date datum - конкретная дата или массив дат (начало, конец периода)
	 *                       - str values - массив значений для выборки
	 *                       - str subvalues - массив уточненных значений для выборки
	 *                       - bool personal - признак учета персональных результатов
	 *
	 * @return float|int
	 */
	public function calculateFact(int $iduser = 0, string $tip = '', array $params = []) {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$sort   = '';
		$result = 0;
		$year   = (int)date( 'Y' );
		$month  = (int)date( 'm' );
		$day    = (int)date( 'd' );

		$period    = $params['period'];
		$datum     = $params['datum'];
		$values    = (!is_array( $params['values'] )) ? $params['values'] : yimplode( ",", $params['values'] );
		$subvalues = $params['subvalues'];

		//если указан период
		if ( $period != '' && ($datum == '' || empty( $datum )) ) {

			if ( $period == 'quartal' ) {
				$period = 'quart';
			}

			$period = getPeriod( $period );
			$dStart = $period[0]." 00:00:00";
			$dEnd   = $period[1]." 23:59:59";

		}
		//если указан и период и дата
		//то считаем
		elseif ( is_array( $datum ) && !empty( $datum ) ) {

			$dStart = $datum[0]." 00:00:00";
			$dEnd   = $datum[1]." 23:59:59";

		}
		//если указана дата и дата как строка
		elseif ( is_string( $datum ) && $datum != '' ) {

			$dStart = $datum." 00:00:00";
			$dEnd   = $datum." 23:59:59";

		}
		else {

			$dStart = "$year-$month-$day 00:00:00";
			$dEnd   = "$year-$month-$day 23:59:59";

		}

		//print_r($userKPI);

		$isort = (isset( $params['personal'] ) && $params['personal'] == 'yes') ? " AND iduser = '$iduser'" : " AND iduser IN (".yimplode( ",", get_people( $iduser, 'yes' ) ).")";
		$usort = (isset( $params['personal'] ) && $params['personal'] == 'yes') ? " = '$iduser'" : " IN (".yimplode( ",", get_people( $iduser, 'yes' ) ).")";

		//расчет параметров для
		switch ($tip) {

			case 'activities':

				if ( $values !== '' ) {

					$tips = $db -> getCol( "SELECT title FROM {$sqlname}activities WHERE id IN (".$values.") AND identity = '$identity'" );
					if ( !empty( $tips ) ) {
						$sort .= " {$sqlname}history.tip IN (".yimplode( ",", $tips, "'" ).")  AND ";
					}

				}

				$result = (int)$db -> getOne( "
					SELECT COUNT(*) 
					FROM {$sqlname}history
					WHERE 
						{$sqlname}history.iduser $usort AND 
						{$sqlname}history.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}history.identity = '$identity'
					" );

			break;
			case 'calls':

				if ( $values !== '' ) {

					$tips = yexplode( ",", $values );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						COUNT(*) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct IN ('outcome','income') AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

			break;
			case 'callsDuration':

				if ( $values != '' ) {

					$tips = yexplode( ",", $values );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (float)$db -> getOne( "
					SELECT 
						SUM(sec) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct IN ('outcome','income') AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				$result = round( $result / 60 );

			break;
			case 'callsOut':

				if ( $values != '' ) {

					$tips = yexplode( ",", $values );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						COUNT(*) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'outcome' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

			break;
			case 'callsOutDuration':

				if ( $values != '' ) {

					$tips = yexplode( ",", $values );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (float)$db -> getOne( "
					SELECT 
						SUM(sec) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'outcome' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				$result = round( $result / 60 );

			break;
			case 'callsIn':

				if ( $values != '' ) {

					$tips = yexplode( ",", $values );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						COUNT(*) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'income' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

			break;
			case 'callsInDuration':

				if ( $values != '' ) {

					$tips = yexplode( ",", $values );
					$sort .= " {$sqlname}callhistory.res IN (".yimplode( ",", $tips, "'" ).") AND ";

				}

				$result = (int)$db -> getOne( "
					SELECT 
						SUM(sec) 
					FROM {$sqlname}callhistory
					WHERE 
						{$sqlname}callhistory.iduser $usort AND 
						{$sqlname}callhistory.direct = 'income' AND 
						{$sqlname}callhistory.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}callhistory.identity = '$identity'
					" );

				$result = round( $result / 60 );

			break;
			case 'clientsNew':

				if ( $values != '' ) {
					$sort .= " {$sqlname}clientcat.idcategory IN (".$values.") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT(*) 
					FROM {$sqlname}clientcat 
					WHERE 
						{$sqlname}clientcat.creator $usort AND 
						{$sqlname}clientcat.date_create BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}clientcat.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsNewAll':

				if ( $values != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$values.") AND ";
				}
				if ( $subvalues != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$subvalues.") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT(*) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsSumAll':

				if ( $values != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$values.") AND ";
				}
				if ( $subvalues != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$subvalues.") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT SUM({$sqlname}dogovor.kol) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

				//print $db -> lastQuery();

			break;
			case 'dealsNewDouble':

				if ( $values != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$values.") AND ";
				}
				if ( $subvalues != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$subvalues.") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT(*) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) > 1 AND
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

			break;
			case 'dealsSumDouble':

				if ( $values != '' ) {
					$sort .= " {$sqlname}dogovor.direction IN (".$values.") AND ";
				}
				if ( $subvalues != '' ) {
					$sort .= " {$sqlname}dogovor.tip IN (".$subvalues.") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT SUM({$sqlname}dogovor.kol) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.autor $usort AND 
						{$sqlname}dogovor.datum BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) > 1 AND
						$sort 
						{$sqlname}dogovor.identity = '$identity'
					" );

			break;
			case 'dealsCloseAll':

				if ( $values != '' ) {
					$sort .= " direction IN (".$values.") AND ";
				}
				if ( $subvalues != '' ) {
					$sort .= " tip IN (".$subvalues.") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT COUNT(*) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.iduser $usort AND 
						close = 'yes' AND 
						datum_close BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) > 1 AND
						$sort 
						identity = '$identity'
					" );

			break;
			case 'dealsSumClose':

				if ( $values != '' ) {
					$sort .= " direction IN (".$values.") AND ";
				}
				if ( $subvalues != '' ) {
					$sort .= " tip IN (".$subvalues.") AND ";
				}

				$result = (float)$db -> getOne( "
					SELECT SUM({$sqlname}dogovor.kol) 
					FROM {$sqlname}dogovor 
					WHERE 
						{$sqlname}dogovor.iduser $usort AND 
						close = 'yes' AND 
						datum_close BETWEEN '$dStart' AND '$dEnd' AND 
						(SELECT COUNT(*) FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) > 1 AND
						$sort 
						identity = '$identity'
					" );

			break;
			case 'contractsNew':

				if ( $values != '' ) {
					$sort .= $sqlname."contract.idtype IN (".$values.") AND ";
				}

				//если суб-параметры указаны
				if ( $subvalues != '' ) {

					$result = (int)$db -> getOne( "
						SELECT 
							COUNT(*) 
						FROM {$sqlname}contract_statuslog
							LEFT JOIN {$sqlname}contract ON {$sqlname}contract_statuslog.deid = {$sqlname}contract.deid
						WHERE 
							{$sqlname}contract_statuslog.iduser $usort AND 
							{$sqlname}contract_statuslog.status IN (".$subvalues.") AND
							{$sqlname}contract_statuslog.datum BETWEEN '$dStart' AND '$dEnd' AND 
							$sort 
							{$sqlname}contract_statuslog.identity = '$identity'
							GROUP BY {$sqlname}contract_statuslog.deid
							ORDER BY {$sqlname}contract_statuslog.datum DESC
						" );

				}
				else {

					$result = (int)$db -> getOne( "
						SELECT 
							COUNT(*) 
						FROM {$sqlname}contract
						WHERE 
							{$sqlname}contract.iduser $usort AND 
							{$sqlname}contract.datum BETWEEN '$dStart' AND '$dEnd' AND
							$sort 
							{$sqlname}contract.identity = '$identity'
						" );

				}

				//print $db -> lastQuery();

			break;
			case 'invoicesNewCount':

				$result = (int)$db -> getOne( "
					SELECT
						COUNT(*) as count
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.do != 'on' AND
						{$sqlname}credit.datum_credit BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) and
						{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'invoicesNewSum':

				$result = (float)$db -> getOne( "
					SELECT
						SUM({$sqlname}credit.summa_credit) as count
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.do != 'on' AND
						{$sqlname}credit.datum_credit BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) AND
						{$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'invoicesDoCount':

				$result = (int)$db -> getOne( "
					SELECT
						COUNT(*) as count
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.do = 'on' AND
						{$sqlname}credit.invoice_date BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) and
						-- {$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'invoicesDoSum':

				$result = (float)$db -> getOne( "
					SELECT
						SUM({$sqlname}credit.summa_credit) as count
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.did = {$sqlname}credit.did
					WHERE
						{$sqlname}credit.do = 'on' AND
						{$sqlname}credit.invoice_date BETWEEN '$dStart' AND '$dEnd' AND 
						(
							{$sqlname}credit.iduser $usort OR
							{$sqlname}credit.iduser IN (SELECT iduser FROM {$sqlname}dogovor WHERE identity = '$identity' $isort) AND {$sqlname}credit.identity = '$identity'
						) AND
						-- {$sqlname}credit.did IN (SELECT did FROM {$sqlname}dogovor WHERE close != 'yes' AND identity = '$identity') AND
						{$sqlname}credit.identity = '$identity'
				" );

			break;
			case 'leadsNewCount':

				if ( $values != '' ) {
					$sort .= " {$sqlname}leads.clientpath IN (".$values.") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT
						COUNT(*) as count
					FROM {$sqlname}leads
					WHERE
						{$sqlname}leads.datum BETWEEN '$dStart' AND '$dEnd' AND 
						-- {$sqlname}leads.iduser $usort AND
						$sort
						{$sqlname}leads.identity = '$identity'
				" );

			break;
			case 'leadsDoCount':

				if ( $values != '' ) {
					$sort .= " {$sqlname}leads.clientpath IN (".$values.") AND ";
				}

				$result = (int)$db -> getOne( "
					SELECT
						COUNT(*) as count
					FROM {$sqlname}leads
					WHERE
						{$sqlname}leads.status IN (2,3) AND
						{$sqlname}leads.datum_do BETWEEN '$dStart' AND '$dEnd' AND 
						{$sqlname}leads.iduser $usort AND
						$sort
						{$sqlname}leads.identity = '$identity'
				" );

			break;

		}

		//$this -> users = $users;
		//$result += 0;

		return $result;

	}

	/**
	 * Расчет выполнения плана сотрудником
	 *
	 * @param int      $iduser - id сотрудника
	 * @param int|null $syear  - год расчета или текущий
	 * @param int|null $month  - месяц расчета или текущий
	 * @param bool     $ignorePersonal
	 *
	 * @return array - ответ
	 *         - float summa - оборот факт
	 *         - float summaPlan - оборот плановый
	 *         - float summaPercent - процент выполнения
	 *         - float marga - маржа факт
	 *         - float margaPlan - маржа плановый
	 *         - float margaPercent - процент выполнения
	 *         - int|array users - число или массив с iduser
	 */
	public function getPlanDo(int $iduser = 0, int $syear = NULL, int $month = NULL, bool $ignorePersonal = false): array {

		$sqlname       = $this -> sqlname;
		$db            = $this -> db;
		$identity      = $this -> identity;
		$otherSettings = $this -> otherSettings;

		$result = [];
		//$sort = $usort = $dsort = '';

		//if(empty($other))
		//$other = explode(";", $db -> getOne("SELECT other FROM {$sqlname}settings WHERE id = '$identity'"));

		$summa = $marga = 0;

		$year  = (int)$syear > 0 ? (int)$syear : (int)date( 'Y' );

		if( $month === NULL ) {
			$month = (int)date( 'n' );
		}

		if ( $year < 2000 ) {
			$year += 2000;
		}

		$uset = $db -> getOne( "SELECT acs_import FROM {$sqlname}user WHERE iduser = '$iduser' AND acs_plan = 'on' AND identity = '$identity'" );
		$ac   = explode( ";", $uset );

		//индивидуальный план продаж пользователя
		$isPersonal = $ac[19];

		//расчет планов только по закрытым сделкам
		$isClosed = $otherSettings['planByClosed'];

		//активна рассрочка, счета
		//yes - считаем по оплатам
		//no  - считаем по суммам в сделках
		$isInvoice = $otherSettings['credit'];

		//формируем параметры уточнения запроса
		$sort  = ($isPersonal == 'on' || $ignorePersonal) ? " AND iduser = '$iduser'" : " AND iduser IN (".yimplode( ",", get_people( $iduser, 'yes' ) ).")";
		$usort = ($isPersonal == 'on' || $ignorePersonal) ? "iduser = '$iduser'" : "iduser IN (".yimplode( ",", get_people( $iduser, 'yes' ) ).")";
		$users = ($isPersonal == 'on' || $ignorePersonal) ? [$iduser] : get_people( $iduser, 'yes' );
		$dsort = ($isClosed == 'yes') ? " AND close = 'yes'" : "";

		//расчет по суммам в сделках
		if ( $isInvoice != 'yes' ) {

			$result = $db -> getRow( "
				SELECT 
					SUM(kol_fact) AS summa, SUM(marga) AS marga 
				FROM {$sqlname}dogovor 
				WHERE 
					DATE_FORMAT(datum_close, '%Y') = '$year' AND 
					DATE_FORMAT(datum_close, '%c') = '$month' 
					$sort 
					$dsort AND 
					identity = '$identity'
			" );
			$summa  = (float)$result['summa'];
			$marga  = (float)$result['marga'];

		}

		//если расчет идет по оплаченным счетам
		//то нам надо узнать долю маржи в оплаченном счете
		if ( $isInvoice == 'yes' ) {

			if ( $isClosed != 'yes' ) {

				$result = $db -> getAll( "
					SELECT 
						{$sqlname}credit.do,
						{$sqlname}credit.did,
						{$sqlname}credit.invoice_date,
						{$sqlname}credit.summa_credit,
						{$sqlname}credit.iduser
					FROM {$sqlname}credit 
					WHERE 
						{$sqlname}credit.do = 'on' AND 
						DATE_FORMAT({$sqlname}credit.invoice_date, '%Y') = '$year' AND 
						DATE_FORMAT({$sqlname}credit.invoice_date, '%c') = '$month' AND 
						{$sqlname}credit.did IN (
							SELECT did 
							FROM {$sqlname}dogovor 
							WHERE did > 0 $sort
						) 
						OR 
						(
							{$sqlname}credit.$usort AND 
							DATE_FORMAT({$sqlname}credit.invoice_date, '%Y') = '$year' AND 
							DATE_FORMAT({$sqlname}credit.invoice_date, '%c') = '$month'
						) 
						AND 
						{$sqlname}credit.identity = '$identity'
				" );

			}

			if ( $isClosed == 'yes' ) {

				$result = $db -> getAll( "
					SELECT 
						{$sqlname}credit.do,
						{$sqlname}credit.did,
						{$sqlname}credit.summa_credit,
						{$sqlname}credit.iduser,
						{$sqlname}dogovor.kol,
						{$sqlname}dogovor.marga,
						{$sqlname}dogovor.close,
						{$sqlname}dogovor.datum_close
					FROM {$sqlname}credit
						LEFT JOIN {$sqlname}dogovor ON {$sqlname}credit.did = {$sqlname}dogovor.did
					WHERE
						{$sqlname}credit.do = 'on' AND
						{$sqlname}dogovor.close = 'yes' AND
						DATE_FORMAT({$sqlname}dogovor.datum_close, '%Y-%c') = '$year-$month' AND 
						{$sqlname}credit.$usort AND 
						{$sqlname}credit.identity = '$identity'
				" );

			}

			foreach ( $result as $data ) {

				//расчет процента размера платежа от суммы сделки
				$kolp   = $db -> getOne( "SELECT kol FROM ${sqlname}dogovor WHERE did = '$data[did]' AND identity = '$identity'" );
				$margap = $db -> getOne( "SELECT marga FROM ${sqlname}dogovor WHERE did = '$data[did]' AND identity = '$identity'" );

				//% оплаченной суммы от суммы по договору
				$dolya = ($kolp > 0) ? (float)$data['summa_credit'] / $kolp : 0;

				$summa += pre_format( (float)$data['summa_credit'] );
				$marga += $margap * $dolya;

			}

		}

		// плановые цифры
		//плановые показатели для текущего сотрудника
		$month2 = (int)$month;
		$rplan  = $db -> getRow( "SELECT SUM(kol_plan) as summa, SUM(marga) as marga FROM {$sqlname}plan WHERE year = '$year' and mon = '$month2' and iduser = '$iduser' and identity = '$identity'" );

		//file_put_contents($rootpath."/cash/static.log", $db -> lastQuery());

		return [
			"summa"        => (float)$summa,
			"summaPlan"    => (float)$rplan['summa'],
			"summaPercent" => ($rplan['summa'] > 0) ? round( ($summa / $rplan['summa']) * 100, 2 ) : 100,
			"marga"        => (float)$marga,
			"margaPlan"    => (float)$rplan['marga'],
			"margaPercent" => ($rplan['marga'] > 0) ? round( ($marga / $rplan['marga']) * 100, 2 ) : 100,
			"users"        => $users
		];

	}

	/**
	 * Сохраняет коэффициенты сезонности
	 *
	 * @param int   $year
	 * @param array $params
	 *                      - array **rate** - массив коэффициентов месяц => коэффициент
	 *                      - int **kpi** - отношение к конкретному показателю ( не применяется )
	 * @return string
	 */
	public function setSeason(int $year = 0, array $params = []): string {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ( $year == '' ) {
			$year = (int)date( 'Y' );
		}

		if ( !isset( $params['kpi'] ) ) {
			$params['kpi'] = 0;
		}

		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}kpiseason WHERE year = '$year' AND identity = '$identity'" );

		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}kpiseason SET ?u", [
				'kpi'      => (int)$params['kpi'],
				'year'     => $year,
				'rate'     => json_encode( $params['rate'] ),
				'identity' => $identity
			] );
			//$id = $db -> insertId();

			$result = 'Добавлено';

		}
		else {

			$db -> query( "UPDATE {$sqlname}kpiseason SET ?u WHERE id = '$id'", [
				'kpi'  => (int)$params['kpi'],
				'rate' => json_encode( $params['rate'] ),
			] );

			$result = 'Изменено';

		}

		return $result;

	}

	/**
	 * Выводит массив коэффициентов на указанный год
	 *
	 * @param string $year
	 * @return array
	 */
	public function getSeason($year = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		//$list = [];

		$res = $db -> getOne( "SELECT rate FROM {$sqlname}kpiseason WHERE year = '$year' AND identity = '$identity'" );

		// если коэффициенты сезонности не устанавливались, то ставим как 1
		if ( $res == '' ) {

			$r = [];
			for ( $i = 1; $i <= 12; $i++ ) {
				$r[ $i ] = 1;
			}

			return $r;

		}

		$l = json_decode( $res, true );

		// прогоняем массив с коэффициентами, на случай, если они не указаны
		$m = 1;
		while ($m <= 12) {

			if ( (int)$l[ $m ] == 0 ) {
				$l[ $m ] = 1;
			}

			$m++;

		}

		return $l;

		//return json_decode( $res, true );

	}

}