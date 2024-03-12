<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */


namespace Salesman;

use event;
use Exception;

/**
 * Класс для работы с объектом Сделка
 *
 * Class Deal
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example
 *
 * ```php
 * $Deal  = new Salesman\Deal();
 * $result = $Deal -> add($params);
 * $did = $result['data'];
 * ```
 */
class Deal {

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
	 * Возвращает информацию о сделке
	 *
	 * @param $did
	 *
	 * Возвращает полную информацию по сделке:
	 *
	 *   - базовая информация в основном массиве
	 *
	 *      - int **did** - id сделки
	 *      - str **dealUID** - идентификатор из внешних систем
	 *      - str **title** - название
	 *      - date **datum_plan** - плановая дата
	 *      - date **date_create** - дата создания
	 *      - date **date_update** - дата изменения
	 *      - date **datum_start** - период. старт
	 *      - date **datum_end** - период. конец
	 *      - array **step** - массив "этап сделки"
	 *
	 *          - int **stepid** - id этапа
	 *          - str **steptitle** - цифровое обозначение
	 *          - str **stepname** - название этапа
	 *
	 *      - str **stepName** - название этапа
	 *      - int **direction** - id направления
	 *      - str **directionName** - название направления
	 *      - str **adres** - адрес
	 *      - float **summa** - плановая сумма
	 *      - float **marga** - маржа
	 *      - float **kol_fact** - фактическая сумма
	 *      - int **iduser** - iduser куратора по сделке
	 *      - str **userUID** - uid куратора
	 *      - str **user** - имя куратора
	 *      - int **autor** - iduser автора сделки
	 *      - str **autorName** - имя автора сделки
	 *      - str **autorUID** - uid автора сделки
	 *      - str **calculate** -  = yes
	 *      - int **tip** - id типа сделки
	 *      - str **tipName** - название типа сделки
	 *      - str **content** - описание
	 *      - str **inputXXX** - доп.поля
	 *
	 *   - array **contract** - список документов по сделке
	 *   [deid,datum,title,number,datum_start,datum_end,des,clid,did,idtype,crid] - массив "документы"
	 *   - array **company** - массив "компания" - компания, от которой идет сделка со всеми реквизитами
	 *   - array **invoice** - массив "счета"
	 *
	 *   - array **close** - массив "статус закрытия", если сделка закрыта
	 *          - **close** - статус закрытия = yes|no
	 *          - **date** - дата закрытия
	 *          - **summa** - сумма конкурента
	 *          - **status** - id статуса
	 *          - **statustext** - название статуса
	 *
	 *   - array **speca** - массив "спецификация"
	 *   - array **client** - массив "клиент" - вся информация по клиенту - заказчику, вкл. реквизиты (массив recv)
	 *   - array **payer** - массив "плательщик" - вся информация по клиенту - плательщику, вкл. реквизиты (массив
	 *   recv)
	 *   - array **person** - массив "контакты" - контакты по сделке [pid,title,post,phone,mob,email]
	 *
	 * @return array
	 *
	 * Example:
	 * ```php
	 * $Deal = \Salesman\Deal::info($pid);
	 * ```
	 */
	public static function info($did): array {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$r = $db -> getRow("
			SELECT
				{$sqlname}dogovor.did,
				{$sqlname}dogovor.uid as uid,
				{$sqlname}dogovor.title,
				{$sqlname}dogovor.datum,
				{$sqlname}dogovor.datum_plan,
				{$sqlname}dogovor.datum_close,
				{$sqlname}dogovor.idcategory,
				{$sqlname}dogovor.tip,
				{$sqlname}dogovor.clid,
				{$sqlname}dogovor.payer,
				{$sqlname}dogovor.pid,
				{$sqlname}dogovor.mcid,
				{$sqlname}dogovor.kol,
				{$sqlname}dogovor.marga,
				{$sqlname}dogovor.kol_fact,
				{$sqlname}dogovor.close,
				{$sqlname}dogovor.iduser,
				{$sqlname}dogovor.autor,
				{$sqlname}dogovor.adres,
				{$sqlname}dogovor.pid_list,
				{$sqlname}dogovor.dog_num,
				{$sqlname}dogovor.calculate,
				{$sqlname}dogovor.content,
				{$sqlname}dogovor.direction,
				{$sqlname}dogovor.isFrozen,
				{$sqlname}dogovor.idcurrency,
				{$sqlname}dogovor.idcourse,
				{$sqlname}personcat.person,
				{$sqlname}user.title as user,
				{$sqlname}dogcategory.title as step,
				{$sqlname}dogcategory.content as stepName,
				{$sqlname}dogtips.title as tipName,
				{$sqlname}dogstatus.title as dstatus,
				{$sqlname}dogstatus.content as dstatusText,
				{$sqlname}direction.title as directionName
			FROM {$sqlname}dogovor
				LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
				LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
				LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
				LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
				LEFT JOIN {$sqlname}direction ON {$sqlname}dogovor.direction = {$sqlname}direction.id
				LEFT JOIN {$sqlname}credit ON {$sqlname}dogovor.did = {$sqlname}credit.did
			WHERE
				{$sqlname}dogovor.did = '$did' and
				{$sqlname}dogovor.identity = '$identity'
			");

		if( (int)$r['did'] == 0 ){

			return [
				"code" => 400,
				"result" => "error",
				"error"  => "Сделка не найдена"
			];

		}

		$pid_list = yexplode(";", $r["pid_list"]);

		$dostup = $db -> getCol("SELECT iduser FROM {$sqlname}dostup WHERE did = '$did'");

		$accesse = array_map(static function($a){
			return (int)$a;
		}, $dostup);

		$response = [
			"did"           => (int)$did,
			"dealUID"       => $r['uid'],
			"title"         => $r['title'],
			"datum_plan"    => $r['datum_plan'],
			"date_create"   => $r['datum'],
			"date_update"   => $r['datum_izm'],
			"datum_start"   => $r['datum_start'],
			"datum_end"     => $r['datum_end'],
			//"step"          => $r['step'],
			"stepName"      => $r['stepName'],
			"isFrozen"      => $r['isFrozen'] == 0,
			"idcategory"     => (int)$r['idcategory'],
			"direction"     => (int)$r['direction'],
			"directionName" => $r['directionName'],
			"adres"         => $r['adres'],
			"dog_num"       => $r['dog_num'],
			"kol"           => (float)$r['kol'],
			"summa"         => (float)$r['kol'],
			"marga"         => (float)$r['marga'],
			"kol_fact"      => (float)$r['kol_fact'],
			"iduser"        => (int)$r['iduser'],
			"userUID"       => current_userUID($r['iduser']),
			"user"          => $r['user'] ?? 'Не определен',
			"autor"         => (int)$r['autor'],
			"autorName"     => current_user($r['autor']),
			"autorUID"      => current_userUID($r['autor']),
			"calculate"     => $r['calculate'],
			"tip"           => (int)$r['tip'],
			"tipName"       => $r['tipName'],
			"content"       => $r['content'],
			"mcid"          => (int)$r['mcid'],
			"idcourse"      => (int)$r['idcourse'],
			"idcurrency"    => (int)$r['idcurrency'],
			"accesse"       => $accesse
		];

		//доп.поля
		$d = get_dog_info($did, 'yes');

		$res = $db -> getAll("select * from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");
		foreach ($res as $da) {

			$response[$da['fld_name']] = $d[$da['fld_name']];

		}
		//доп.поля

		//договор
		$res                  = $db -> getRow("select * from {$sqlname}contract where deid='".$r['dog_num']."' and identity = '$identity'");
		$response['contract'] = [
			"deid"        => (int)$res['deid'],
			"datum"       => $res['datum'],
			"title"       => $res['title'],
			"number"      => $res['number'],
			"datum_start" => $res['datum_start'],
			"datum_end"   => $res['datum_end'],
			"des"         => $res['des'],
			"clid"        => (int)$res['clid'],
			"did"         => (int)$res['did'],
			"idtype"      => (int)$res['idtype'],
			"crid"        => (int)$res['crid'],
		];

		//todo: компания и реквизиты
		$res                 = $db -> getRow("select * from {$sqlname}mycomps where id='".$r['mcid']."' and identity = '$identity'");
		$response['company'] = [
			"id"            => (int)$res['id'],
			"name_ur"       => $res['name_ur'],
			"name_shot"     => $res['name_shot'],
			"address_yur"   => $res['address_yur'],
			"address_post"  => $res['address_post'],
			"dir_name"      => $res['dir_name'],
			"dir_signature" => $res['dir_signature'],
			"dir_status"    => $res['dir_status'],
			"dir_osnovanie" => $res['dir_osnovanie'],
			"inn"           => yexplode(";", $res['innkpp'], 0),
			"kpp"           => yexplode(";", $res['innkpp'], 1),
			"okpo"          => yexplode(";", $res['okog'], 1),
			"ogrn"          => yexplode(";", $res['okog'], 0),
		];

		//составим список счетов и их статус
		$response['invoice'] = [];
		$res                 = $db -> getAll("SELECT * FROM {$sqlname}credit WHERE did='".$did."' and identity = '$identity' ORDER by crid");
		foreach ($res as $da) {

			$response['invoice'][] = [
				'id'           => (int)$da['crid'],
				'invoice'      => $da['invoice'],
				'date'         => cut_date($da['datum']),
				'datum_credit' => $da['datum_credit'],
				'summa'        => (float)$da['summa_credit'],
				'nds'          => (float)$da['nds_credit'],
				'do'           => $da['do'],
				'date_do'      => $da['invoice_date'],
				'contract'     => $da['invoice_chek'],
				'rs'           => (int)$da['rs'],
				'tip'          => $da['tip']
			];

		}

		$response['step'] = [
			"stepid"    => (int)$r['idcategory'],
			"steptitle" => (int)$r['step'],
			"stepname"  => $r['stepName']
		];

		$response['close'] = [
			"close"      => $r['close'],
			"date"       => $r['datum_close'],
			"summa"      => (float)$r['kol_fact'],
			"status"     => $r['dstatus'],
			"statustext" => $r['dstatusText']
		];

		$response['speca'] = [];
		$ress              = $db -> getAll("SELECT * FROM {$sqlname}speca WHERE did='".$did."' and identity = '$identity' ORDER BY spid");
		foreach ($ress as $da) {

			$response['speca'][] = [
				"spid"     => (int)$da['spid'],
				"prid"     => (int)$da['prid'],
				"artikul"  => $da['artikul'],
				"title"    => $da['title'],
				"tip"      => $da['tip'],
				"kol"      => (float)$da['kol'],
				"dop"      => $da['dop'],
				"edizm"    => $da['edizm'],
				"price"    => (float)$da['price'],
				"price_in" => (float)$da['price_in'],
				"nds"      => (float)$da['nds'],
				"comment"  => $da['comments'],
				"comments" => $da['comments']
			];
		}

		$response['client']         = get_client_info($r['clid'], "yes");
		$response['client']['recv'] = get_client_recv($r['clid'], 'yes');
		$response['client']['bankinfo'] = $response['client']['recv'];

		$response['payer']         = get_client_info($r['payer'], "yes");
		$response['payer']['recv'] = get_client_recv($r['payer'], 'yes');
		$response['payer']['bankinfo'] = $response['payer']['recv'];

		$person = [];
		foreach ($pid_list as $pids) {

			$person[] = personinfo($pids);

		}

		if ((int)$response['client']['pid'] > 0 && empty((array)$pid_list)) {
			$person[] = personinfo($response['client']['pid']);
		}

		$response['person'] = $person;

		return $response;

	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	public static function getFiledType($field): string {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$temp = $db -> getOne("SELECT fld_temp FROM {$sqlname}field WHERE fld_tip = 'dogovor' and fld_name = '$field' and identity = '$identity'");

		return ( $temp ) ? : "";

	}

	/**
	 * Добавление новой сделки
	 *
	 * @param array $params - параметры
	 *                      - integer **uid** – уникальный идентификатор во внешней ИС
	 *                      - string **title** | **title_dog** – название сделки (обязательное поле)
	 *                      - integer **clid** – идентификатор клиента, к которому будет привязана сделка (обязательное
	 *                      поле)
	 *                      - integer **payer** - идентификатор клиента-плательщика
	 *                      - date **datum_plan** – плановая дата закрытия сделки
	 *                      - date **datum_start** – период сделки. старт
	 *                      - date **datum_end** – период сделки. конец
	 *                      - integer **direction** – направление деятельности. Если не указано, то принимается
	 *                      значение по умолчанию из справочника
	 *                      - integer **tip** – тип сделки. Если не указано, то принимается значение по умолчанию из
	 *                      справочника
	 *                      - integer **idcategory** – ID этапа
	 *                      - integer **mcid** – идентификатор своей компании (можно получить в отдельном запросе).
	 *                      Если mcid всё-таки не указан, то возьмем последний использованный
	 *                      - integer **iduser** – login пользователя в SalesMan CRM назначаемого Ответственным за
	 *                      клиента
	 *                      - array **pid_list** - список id контактов, через запятую
	 *                      - string **прочие поля** ( inputXXX ) – информация для добавления
	 *                      - string **calculate** = yes/no - признак включения Спецификации. Должно быть "yes", если
	 *                      добавляется Спецификация
	 *                      - array **speka** - массив данных для добавления продуктов, для каждого добавляемого
	 *                      продукта содержит следующие данные:
	 *                      - integer **prid** - ID позиции прайса (n_id)
	 *                      - string **artikul** - артикул позиции прайса (уникальный идентификатор товара/услуги)
	 *                      - string **title** - наименование позиции
	 *                      - float **kol** - количество
	 *                      - int **tip** - тип позиции ( 0 - товар, 1 - услуга, 2 - материал )
	 *                      - float **dop** - доп.поле, по умолчанию = 1
	 *                      - float **price** - розничная цена (не обязателный параметр, при совпадении актикула или
	 *                      названия с позицией в прайсе берется из прайса)
	 *                      - float **price_in** - закупочная цена (не обязателный параметр, при совпадении актикула
	 *                      или названия с позицией в прайсе берется из прайса)
	 *                      - string **edizm** - единица измерения (не обязателный параметр, при совпадении актикула
	 *                      или названия с позицией в прайсе берется из прайса)
	 *                      - float **nds** - НДС, в %
	 *                      - string **comments** - комментарий к позиции
	 *
	 * Примечание:
	 *      - параметр **datum_plan** может быть указан в запросе. Если он отсутствует, то будет принято текущая дата +
	 *      2 недели
	 *      - параметр **payer** может быть указан в запросе. Если он отсутствует, то будет принято payer = clid
	 *      - параметр **mcid** может отсутствовать в запросе. Если не указано, то принимается значение по умолчанию из
	 *      справочника
	 *      - при пустом поле **iduser** Ответственным будет назначен текущий пользователь (из запроса)
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 * @throws Exception
	 */
	public function add(array $params = []): array {

		global $hooks;
		global $iduser1;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$mcDefault  = $GLOBALS['mcDefault'];
		$dirDefault = $GLOBALS['dirDefault'];
		$tipDefault = $GLOBALS['tipDefault'];
		//$iduser1    = (int)$params['iduser'] > 0 ? $params['iduser'] : $iduser1;
		//$other      = $GLOBALS['other'];
		$fpath = $GLOBALS['fpath'];

		$otherSettings = json_decode(file_get_contents($rootpath."/cash/".$fpath."otherSettings.json"), true);

		$post = $params;

		$params = $hooks -> apply_filters("deal_addfilter", $params);

		$message  = [];
		$response = [];

		$fields = dealFields();
		array_push($fields, "coid1", "pid_list", "idcourse", "idcurrency", "idcategory");

		$params['calculate'] = ( $params['calculate'] == 'yes' ) ? $params['calculate'] : 'no';
		$params['payer']     = ( (int)$params['payer'] < 1 ) ? (int)$params['clid'] : (int)$params['payer'];
		$params['title']     = ( !$params['title'] ) ? untag($params['title_dog']) : untag($params['title']);

		//Если clid или pid не указан,но включено создание Клиента/Контакта, то сначала добавим его
		if ((int)$params['clid'] < 1 && (int)$params['pid'] < 1 && $otherSettings['addClientWDeal']) {

			$cld = untag($params['cild']);

			//зависимость от настроек
			$ctype = ( $otherSettings['clientIsPerson'] ) ? 'person' : 'client';

			if ($params['client'] != '') {

				if ($cld == 'org') {//добавим клиента (только название)

					$Client = new Client();
					$rez    = $Client -> add([
						"type"        => $ctype,
						"title"       => untag($params['client']),
						"iduser"      => (int)$params['iduser'],
						"date_create" => current_datumtime(),
						"creator"     => $iduser1,
						"identity"    => $identity
					]);

					$params['clid'] = (int)$rez['data'];

					$message[] = "Добавлен клиент";

				}
				elseif ($cld == 'psn') {

					$Client = new Person();
					$rez    = $Client -> edit(0, [
						"person"      => untag($params['client']),
						"iduser"      => $params['iduser'],
						"date_create" => current_datumtime(),
						"creator"     => $iduser1,
						"identity"    => $identity
					]);

					$params['pid'] = (int)$rez['data'];

					$message[] = "Добавлен контакт";

				}

			}

		}

		if ((int)$params['clid'] == 0 && (int)$params['payer'] == 0) {

			$response['result']        = 'Error. Не указан Плательщик (Контакт не может быть Плательщиком)';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметр - payer";

		}
		elseif ($params['title'] != '') {

			if ((int)$params['payer'] > 0 && (int)$params['clid'] == 0 && (int)$params['pid'] == 0) {
				$params['clid'] = (int)$params['payer'];
			}

			if ((int)$params['payer'] == 0 && (int)$params['clid'] > 0) {
				$params['payer'] = (int)$params['clid'];
			}

			if (isset($params['datum_plan']) && strtotime($params['datum_plan']) != '') {
				$params['datum_plan'] = date('Y-m-d', strtotime($params['datum_plan']));
			}

			else {
				$params['datum_plan'] = current_datum(-$otherSettings['dealPeriodDefault']);
			}

			$params['datum_start'] = ( $params['datum_start'] != '' ) ? untag($params['datum_start']) : NULL;
			$params['datum_end']   = ( $params['datum_end'] != '' ) ? untag($params['datum_end']) : NULL;

			$params['kol']   = ( isset($params['summa']) ) ? (float)pre_format($params['summa']) : pre_format($params['kol']);
			$params['marga'] = ( isset($params['marga']) ) ? pre_format($params['marga']) : pre_format($params['marg']);

			$params['direction'] = ( $params['direction'] ?? $dirDefault );
			$params['tip']       = $params['tip'] ?? $tipDefault;
			$params['close']     = 'no';


			if (in_array("zayavka", $fields)) {

				//Посмотрим номер последней заявки в базе
				$nzayavka = (int)$db -> getOne("SELECT MAX(zayavka) FROM {$sqlname}dogovor WHERE identity = '$identity'") + 1;

				//если номер заявки = auto, то добавим расчетную
				if ($params['zayavka'] == 'авто' || $nzayavka == 0) {
					$params['zayavka'] = $nzayavka;
				}

				$dparams['zayavka'] = untag($params['zayavka']);
				$dparams['ztitle']  = untag($params['ztitle']);

			}
			else {
				$params['zayavka'] = 0;
			}

			//преобразуем спецификацию, если она пришла в виде не обработанного массива
			if (!isset($params['speka']) && $params['calculate'] == 'yes') {

				$spek = [];

				foreach ($params['speca_title'] as $i => $value) {

					if ($value != '') {

						if ($params['speca_artikul'][$i] == '' && (int)$params['speca_prid'][$i] > 0) {

							$params['speca_artikul'][$i] = $db -> getOne("SELECT artikul FROM {$sqlname}price WHERE n_id = '".(int)$params['speca_prid'][$i]."' and identity = '$identity'");

						}

						$spek[] = [
							"prid"     => (int)$params['speca_prid'][$i],
							"artikul"  => untag($params['speca_artikul'][$i]),
							"title"    => untag($value),
							"tip"      => (int)$params['speca_tip'][$i],
							"edizm"    => untag($params['speca_ediz'][$i]),
							"kol"      => (float)$params['speca_kol'][$i],
							"dop"      => untag($params['speca_dop'][$i]),
							"price"    => (float)pre_format($params['speca_summa'][$i]),
							"price_in" => (float)pre_format($params['price_in'][$i]),
							"nds"      => (float)pre_format($params['speca_nds'][$i]),
							"comments" => $params['speca_comment'][$i],
						];

					}

				}

				//print_r($spek);

				$params['speka'] = $spek;

			}

			$deal = [];
			$dNum = generate_num('dogovor');


			/**
			 * Если mcid всё-таки не указан, то возьмем последний использованный
			 */
			if ((int)$params['mcid'] == 0) {

				$params['mcid'] = (int)$db -> getOne("SELECT mcid FROM {$sqlname}dogovor WHERE did = (SELECT MAX(did) FROM {$sqlname}dogovor WHERE identity = '$identity') and identity = '$identity'");

			}

			foreach ($params as $key => $value) {

				if (in_array($key, $fields) && $key != 'idcategory2') {

					if (is_array($value)) {

						if (
							in_array($key, [
								'coid1',
								'pid_list'
							])
						) {
							$value = trim(yimplode(";", $value));
						}
						//если пришел массив, то преобразуем в строку
						else {
							$value = trim(yimplode(", ", $value));
						}


					}

					switch ($key) {
						case 'datum':
							$value = current_datum();
							break;
						case 'title':
							$value = ( $dNum != '' ) ? $dNum.": ".untag($value) : untag($value);
							break;
						case 'payer':
							$value = ( $value < 1 ) ? (int)$params['clid'] : (int)$value;
							break;
						case 'mcid':

							$value = ( $value < 1 ) ? (int)$mcDefault : (int)$value;

							break;
						/*case 'idcategory':
							$deal['idcategory'] = (int)$value;
						break;*/ case 'category':
						$key   = 'idcategory';
						$value = (int)$value == 0 ? (int)$otherSettings['dealStepDefault'] : (int)$value;
						//$params['idcategory'] = $value;
						break;
						case 'datum_izm':
						case 'des_fact':
						case 'kol_fact':
						case 'speka':
							break;
						case 'step':
							$key   = 'idcategory';
							$value = getStep($value);
							//$params['idcategory'] = $value;
							//$value = is_numeric( $value ) ? (int)$value : getStep( untag( $value ) );
							break;
						case 'kol':
						case 'marga':
							$value = $value == '' ? 0 : (float)pre_format($value);
							break;
						case 'direction':

							// через API может приходить название Направления
							if ((int)$value == 0 && !empty($value)) {

								$value = getDirection(untag($value));

							}

							if ($value == 0) {
								$value = $dirDefault;
							}

							break;
						case 'tip':

							// через API может приходить название Типа сделки
							if ((int)$value == 0 && !empty($value)) {

								$value = getDogTip(untag($value));

							}

							//$value = (is_numeric( $value )) ? (int)$value : getDogTip( untag( $value ) );

							if ($value < 1) {
								$value = $tipDefault;
							}

							break;
						default:

							// у этого поля может быть шаблон
							// поэтому надо сравнить текст с шаблоном
							// и если они равны, то поле игнорируем
							if (self ::getFiledType($key) == 'textarea' && $value != NULL) {

								// шаблон
								$ftpl = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_name = '$key' AND identity = '$identity'");

								$value = ( strcasecmp($ftpl, $value) != 0 ) ? fieldClean($value) : NULL;

							}
							elseif (self ::getFiledType($key) != 'datetime') {
								$value = $value != NULL ? fieldClean($value) : NULL;
							}
							else {
								$value = str_replace([
									"Z",
									"T"
								], [
									":00",
									" "
								], $value);
							}

							break;
					}

					$deal[$key] = $value;

				}

			}

			$deal['pid']       = ( (int)$params['pid'] == 0 ) ? 0 : (int)$params['pid'];
			$deal['mcid']      = ( (int)$deal['mcid'] == 0 ) ? (int)$mcDefault : (int)$deal['mcid'];
			$deal['calculate'] = ( !$deal['calculate'] ) ? $params['calculate'] : $deal['calculate'];
			$deal['datum']     = current_datum();
			$deal['autor']     = ( !isset($params['autor']) ) ? $iduser1 : (int)$params['autor'];
			$deal['iduser']    = ( !isset($params['iduser']) ) ? $iduser1 : (int)$params['iduser'];
			$deal['identity']  = $identity;

			if ((int)$deal['idcategory'] == 0 && isset($params['category'])) {
				$deal['idcategory'] = (int)$params['category'];
			}
			elseif ((int)$deal['idcategory'] == 0 && isset($params['step'])) {
				$deal['idcategory'] = getStep((int)$params['step']);
			}

			//print_r($params);
			//print_r($deal);

			//мультиворонка
			$mFunnel = getMultiStepList([
				"tip"       => $deal['tip'],
				'direction' => $deal['direction']
			]);

			if ((int)$deal['idcategory'] == 0) {
				$deal['idcategory'] = (int)$mFunnel['default'];
			}

			if ((int)$deal['idcurrency'] == 0) {
				$deal['idcurrency'] = 0;
			}

			if ((int)$deal['idcurrency'] == 0 || (int)$deal['idcourse'] == 0) {
				$deal['idcourse'] = 0;
			}

			if (!empty($deal)) {

				//try {

				$db -> query("INSERT INTO {$sqlname}dogovor SET ?u", $deal);
				$did = (int)$db -> insertId();

				//меняем счетчик договоров
				if ($dNum != '') {

					$cnum = (int)$db -> getOne("SELECT dNum FROM {$sqlname}settings WHERE id = '$identity'") + 1;
					$db -> query("UPDATE {$sqlname}settings SET dNum ='$cnum' where id = '$identity'");

				}

				$deal['did'] = $did;

				// Информация об UIDS
				$uids = UIDs ::info(['clid' => (int)$deal['clid']]);

				if ($uids['result'] == 'Success') {

					$deal['uids'] = $uids['data'];

					// добавляем uids из клиента
					foreach ($deal['uids'] as $xuid) {

						$prms = [
							"clid" => $deal['clid'],
							"did"  => $deal['did'],
							"uids" => [
								$xuid['name'] => $xuid['value']
							]
						];

						UIDs ::add($prms);

					}

				}

				if ($hooks) {
					$hooks -> do_action("deal_add", $post, $deal);
				}

				//добавляем смену этапа в лог
				if ((int)$params['idcategory'] > 0) {
					DealStepLog($did, $params['idcategory']);
				}

				//запись в историю активности
				addHistorty([
					"iduser"   => $iduser1,
					"clid"     => (int)$params['clid'],
					"datum"    => current_datumtime(),
					"des"      => "Добавлена сделка",
					"tip"      => "СобытиеCRM",
					"identity" => $identity
				]);

				//добавим спецификацию
				if (!empty($params['speka']) && $params['calculate'] == 'yes') {

					$spekas = [];

					foreach ($params['speka'] as $speka) {

						//найдем данные по позиции в прайсе
						if ($speka['title'] != '' && $speka['artikul'] != '') {

							$speka['dop'] = ( (int)$speka['dop'] == 0 ) ? 1 : $speka['dop'];

							$resp = $db -> getRow("SELECT * FROM {$sqlname}price WHERE n_id > 0 and (n_id = '$speka[prid]' or artikul='$speka[artikul]' or title = '$speka[title]') and identity = '$identity'");

							//print_r($resp);

							$spekas[] = [
								'prid'     => (int)$speka['prid'] > 0 ? (int)$speka['prid'] : (int)$resp["n_id"],
								'artikul'  => !empty($resp["artikul"]) ? $resp["artikul"] : $speka["artikul"],
								'title'    => !empty($speka['title']) ? $speka['title'] : $resp["title"],
								'tip'      => $speka['tip'],
								'edizm'    => !empty($speka['edizm']) ? $speka['edizm'] : $resp["edizm"],
								'price'    => (float)pre_format($speka['price']) > 0 ? pre_format($speka['price']) : pre_format($resp["price_1"]),
								'price_in' => (float)pre_format($speka["price_in"]) > 0 ? pre_format($speka["price_in"]) : pre_format($resp["price_in"]),
								'nds'      => (float)pre_format($speka["nds"]) > 0 ? pre_format($speka["nds"]) : pre_format($resp["nds"]),
								'kol'      => $speka["kol"],
								'comments' => untag($speka["comments"]),
								'dop'      => $speka["dop"]
							];

						}
						else {
							$spekas[] = $speka;
						}

					}

					//print_r($spekas);

					$sspeka = new Speka();
					$rez    = $sspeka -> mass($did, $spekas);

					$message[] = yimplode("<br>", $rez['text']);

				}

				if ((int)$params['ide'] > 0) {

					$db -> query("update {$sqlname}entry set did ='$did', status = '1', datum_do = '".current_datumtime()."' where ide = '$params[ide]' and identity = '$identity'");

					//print $db -> lastQuery();

					event ::fire('entry.status', $args = [
						"id"      => (int)$params['ide'],
						"status"  => "1",
						"autor"   => $iduser1,
						"userUID" => current_userUID($iduser1)
					]);

					$message[] = "Обновлен статус обращения";

				}

				sendNotify('new_dog', $paramss = [
					"did"        => $did,
					"title"      => $deal['title'],
					"kol"        => $deal['kol'],
					"clid"       => (int)$params['clid'],
					"client"     => getClientData((int)$params['clid'], 'title'),
					"pid"        => (int)$params['pid'],
					"person"     => getPersonData((int)$params['pid'], 'person'),
					"dogstatus"  => current_dogstep($did),
					"datum_plan" => $deal['datum_plan'],
					"iduser"     => $deal['iduser'],
					"notice"     => 'yes',
					"comment"    => ""
				]);

				//print_r($paramss);

				/**
				 * Уведомления
				 */
				$arg = [
					"did"        => $did,
					"title"      => $deal['title'],
					"kol"        => $deal['kol'],
					"clid"       => (int)$params['clid'],
					"client"     => getClientData((int)$params['clid'], 'title'),
					"pid"        => (int)$params['pid'],
					"person"     => getPersonData((int)$params['pid'], 'person'),
					"dogstatus"  => current_dogstep($did),
					"datum_plan" => untag($deal['datum_plan']),
					"iduser"     => (int)$deal['iduser'],
					"notice"     => 'yes',
				];
				Notify ::fire("deal.add", $iduser1, $arg);

				/**
				 * События
				 */
				event ::fire('deal.add', $args = [
					"did"      => $did,
					"autor"    => $iduser1,
					"userUID"  => (string)current_userUID((int)$iduser1),
					"newparam" => $deal
				]);

				$response['result'] = 'Успешно';
				$response['data']   = $did;
				if (!empty($message)) {
					$response['text'] = $message;
				}

				//}
				/*catch ( Exception $e ) {

					//print_r($e -> getTrace());
					//print $e -> getTraceAsString();

					$error = $e -> getTrace();

					$response[ 'result' ]          = 'Error';
					$response[ 'error' ][ 'code' ] = '500';
					$response[ 'error' ][ 'text' ] = $error;

				}*/

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 402;
			$response['error']['text'] = "Отсутствуют параметры - Название сделки";

		}

		return $response;

	}

	/**
	 * Обновление информации по сделке. Обновляет только пришедшие поля
	 *
	 * @param int $did - ID сделки
	 * @param array $params - массив с параметрами
	 *                        - integer **uid **– уникальный идентификатор во внешней ИС
	 *                        - string **title** | **title_dog** – название сделки (обязательное поле)
	 *                        - integer **clid** –идентификатор клиента, к которому будет привязана сделка
	 *                        (обязательное поле)
	 *                        - integer **payer** - идентификатор клиента-плательщика
	 *                        - date **datum_plan** – плановая дата закрытия сделки
	 *                        - date **datum_start** – период сделки. старт
	 *                        - date **datum_end** – период сделки. конец
	 *                        - integer **direction** – направление деятельности. Если не указано, то принимается
	 *                        значение по умолчанию из справочника
	 *                        - integer **tip** – тип сделки. Если не указано, то принимается значение по умолчанию из
	 *                        справочника
	 *                        - integer **idcategory** – ID этапа
	 *                        - integer **mcid** – идентификатор своей компании (можно получить в отдельном запросе).
	 *                        Если mcid всё-таки не указан, то возьмем последний использованный
	 *                        - integer **iduser** – login пользователя в SalesMan CRM назначаемого Ответственным за
	 *                        клиента
	 *                        - array **pid_list** - список id контактов, через запятую
	 *                        - string **прочие поля** ( inputXXX ) – информация для добавления
	 *                        - string **calculate** = yes/no - признак включения Спецификации. Должно быть "yes", если
	 *                        добавляется Спецификация
	 *                        - array **speka** - массив данных для добавления продуктов, для каждого добавляемого
	 *                        продукта содержит следующие данные:
	 *                        - integer **prid** - ID позиции прайса (n_id)
	 *                        - string **artikul** - артикул позиции прайса (уникальный идентификатор товара/услуги)
	 *                        - string **title** - наименование позиции
	 *                        - int **tip** - тип позиции ( 0 - товар, 1 - услуга, 2 - материал )
	 *                        - float **kol** - количество
	 *                        - float **dop** - доп.поле, по умолчанию = 1
	 *                        - float **price** - розничная цена (не обязателный параметр, при совпадении актикула или
	 *                        названия с позицией в прайсе берется из прайса)
	 *                        - float **price_in** - закупочная цена (не обязателный параметр, при совпадении актикула
	 *                        или названия с позицией в прайсе берется из прайса)
	 *                        - string **edizm** - единица измерения (не обязателный параметр, при совпадении актикула
	 *                        или названия с позицией в прайсе берется из прайса)
	 *                        - float **nds** - НДС, в %
	 *                        - string **comments** - комментарий к позиции
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 * @throws Exception
	 */
	public function update(int $did = 0, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$mcDefault  = $GLOBALS['mcDefault'];
		$dirDefault = $GLOBALS['dirDefault'];
		$tipDefault = $GLOBALS['tipDefault'];

		$post = $params;

		$params = $hooks -> apply_filters("deal_editfilter", $params);

		$fields = dealFields();
		$deal   = $newParams = $oldParams = [];

		$params['marga'] = ( isset($params['marg']) ) ? pre_format($params['marg']) : pre_format($params['marga']);
		unset($params['marg']);

		array_push($fields, "coid1", "pid_list", "calculate", "idcourse", "idcurrency");

		unset($fields["datum"]);

		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		$dealOld = get_dog_info($did, 'yes');

		if ($params['marga'] == 0) {
			$params['marga'] = $dealOld['marga'];
		}

		if ($did > 0 && $count == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			//если это форма сделки, то добавим доп.параметры
			//в противном случае будем обновлять только переданные данные
			if (isset($params['title']) || isset($params['title_dog'])) {

				$params['payer'] = ( (int)$params['payer'] == 0 ) ? (int)$params['clid'] : (int)$params['payer'];
				$params['title'] = ( !$params['title'] ) ? untag($params['title_dog']) : untag($params['title']);
				if ($params['title'] == '') {
					$deal['title'] = $dealOld['title'];
				}

				$params['datum_start'] = ( $params['datum_start'] != '' ) ? untag($params['datum_start']) : '0000-00-00';
				$params['datum_end']   = ( $params['datum_end'] != '' ) ? untag($params['datum_end']) : '0000-00-00';

				/**
				 * Мультиворонка
				 */
				//проверим этап, и если его нет в текущей воронке установим ближайший меньший
				$mFunnel       = getMultiStepList([
					"direction" => (int)$params['direction'],
					"tip"       => (int)$params['tip']
				]);
				$steps         = array_keys($mFunnel['steps']);
				$currentStepID = current_dogstepid($did);
				$currentStep   = current_dogstepname($currentStepID);

				//если этапа нет в новой цепочке
				if (!in_array(current_dogstepid($did), (array)$steps)) {

					$newStepID = array_shift($steps);

					//вычисляем ближайший меньший
					foreach ($steps as $k => $v) {

						if (current_dogstepname($k) >= $currentStep) {
							goto lbl;
						}
						else {
							$newStepID = $k;
						}

					}

					lbl:

					$oldParams["idcategory"] = $currentStepID;
					$newParams["idcategory"] = $newStepID;

					$deal['idcategory'] = $newStepID;

				}
				else {
					$deal['idcategory'] = $currentStepID;
				}

				//по блоку Заявки
				if (!in_array("zayavka", (array)$fields['dogovor'])) {

					$deal['zayavka'] = 0;
					$deal['ztitle']  = '';

				}

			}

			/* Нужно ли?
			 * При изменении 1-го поля карточки, обнуляются и перезаписываются контакты и конкуренты
			 *
			if (!isset($params['pid_list'])) $params['pid_list'] = '';
			if (!isset($params['coid1'])) $params['coid1'] = '';
			*/
			//Обрабатываем данные
			foreach ($params as $key => $value) {

				if (
					in_array($key, (array)$fields) && !in_array($key, [
						'idcategory',
						'step',
						'close'
					])
				) {

					if (is_array($value)) {

						if (
							in_array($key, [
								'coid1',
								'pid_list'
							])
						) {
							$value = trim(yimplode(";", $value));
						}
						//если пришел массив, то преобразуем в строку
						else {
							$value = trim(yimplode(", ", $value));
						}

					}

					switch ($key) {
						case 'datum':
							$value = current_datum();
							break;
						case 'title':
							$value = untag($value);
							break;
						case 'payer':
							$value = ( (int)$value == 0 ) ? $params['clid'] : (int)$value;
							break;
						case 'mcid':
							$value = ( (int)$value == 0 ) ? $mcDefault : (int)$value;
							break;
						/*case 'idcategory':
						case 'datum_izm':
						case 'des_fact':
						case 'kol_fact':
						break;
						case 'step':
							//$key   = 'idcategory';
							//$value = getStep($value);
						break;*/ case 'status_close':
						$key   = 'sid';
						$value = getStatusClose($value);
						break;
						case 'kol':
						case 'marga':
							$value = pre_format($value);
							break;
						case 'direction':

							//$value = (is_numeric( $value )) ? (int)$value : getDirection( untag( $value ) );

							// через API может приходить название Направления
							if ((int)$value == 0 && !empty($value)) {

								$value = getDirection(untag($value));

							}

							if ($value == 0) {
								$value = $dirDefault;
							}

							break;
						case 'tip':

							//$value = (is_numeric( $value )) ? (int)$value : getDogTip( untag( $value ) );

							// через API может приходить название Направления
							if ((int)$value == 0 && !empty($value)) {

								$value = getDogTip(untag($value));

							}

							if ($value == 0) {
								$value = $tipDefault;
							}

							break;

						default:

							// у этого поля может быть шаблон
							// поэтому надо сравнить текст с шаблоном
							// и если они равны, то поле игнорируем
							if (self ::getFiledType($key) == 'textarea' && !is_null($value)) {

								// шаблон
								$ftpl = $db -> getOne("SELECT fld_var FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_name = '$key' AND identity = '$identity'");

								$value = ( strcasecmp($ftpl, $value) != 0 ) ? fieldClean($value) : NULL;

							}
							elseif (self ::getFiledType($key) != 'datetime') {
								$value = fieldClean($value);
							}
							else {

								$value = str_replace([
									"Z",
									"T"
								], [
									":00",
									" "
								], $value);

							}

							break;
					}

					$deal[$key] = $value;

				}

			}

			//эти параметры не изменяются
			unset($deal['idcategory'], $deal['did'], $deal['datum']);

			if (isset($params['calculate'])) {
				$deal['calculate'] = $params['calculate'];
			}

			foreach ($deal as $key => $value) {

				if (
					!in_array($key, [
						'did',
						'idcategory'
					])
				) {

					$newParams[$key] = $value;        //массив новых параметров
					$oldParams[$key] = $dealOld[$key];//массив старых параметров

				}

			}

			if ($dealOld['datum_start'] == '0000-00-00' && in_array('datum_start', (array)$params)) {
				$oldParams['datum_start'] = '0000-00-00';
			}
			if ($dealOld['datum_end'] == '0000-00-00' && in_array('datum_end', (array)$params)) {
				$oldParams['datum_end'] = '0000-00-00';
			}

			//проверка только если данные переданы из формы сделки
			if ($params['calculate'] != 'yes' && ( isset($params['title']) || isset($params['title_dog']) )) {

				$oldParams["kol"]   = (float)$dealOld['kol'];
				$oldParams["marga"] = (float)$dealOld['marga'];
				$newParams["kol"]   = pre_format($params['kol']);
				$newParams["marga"] = pre_format($params['marga']);

				$deal['kol']   = pre_format($params['kol']);
				$deal['marga'] = pre_format($params['marga']);

			}

			//добавим новую спецификацию
			if (isset($params['speka']) && is_array($params['speka']) && $params['calculate'] == 'yes') {

				unset($oldParams["kol"], $oldParams["marga"], $newParams["kol"], $newParams["marga"]);

				//удалим предыдущую спеку
				$db -> query("delete from {$sqlname}speca where did = '$did' and identity = '$identity'");

				//require_once "Speka.php";

				$spekas = [];

				foreach ($params['speka'] as $speka) {

					//найдем данные по позиции в прайсе
					if ($speka['title'] != '' && $speka['artikul'] != '') {

						$speka['dop'] = ( $speka['dop'] < 1 ) ? 1 : $speka['dop'];

						$resp = $db -> getRow("SELECT * FROM {$sqlname}price WHERE n_id > 0 and (n_id = '".(int)$speka['prid']."' or artikul='".untag($speka['artikul'])."' or title = '".untag($speka['title'])."') and identity = '$identity'");

						$spekas[] = [
							'prid'     => ( $speka['prid'] > 0 ) ? (int)$speka['prid'] : (int)$resp["n_id"],
							'artikul'  => $resp["artikul"],
							'title'    => ( $speka['title'] != '' ) ? untag($speka['title']) : untag($resp["title"]),
							'tip'      => ( $speka['tip'] != '' ) ? (int)$speka['tip'] : (int)$resp["tip"],
							'edizm'    => ( $speka['edizm'] != '' ) ? untag($speka['edizm']) : $resp["edizm"],
							'price'    => ( $speka['price'] > 0 ) ? pre_format($speka['price']) : $resp["price_1"],
							'price_in' => pre_format($resp["price_in"]),
							'nds'      => pre_format($resp["nds"]),
							'kol'      => pre_format($speka["kol"]),
							'comments' => untag($speka["comments"]),
							'dop'      => (float)$speka["dop"]
						];

					}
					else {
						$spekas[] = $speka;
					}

				}

				$sspeka = new Speka();
				$rez    = $sspeka -> mass($did, $spekas);

				$message[] = yimplode("<br>", $rez['text']);

			}

			if (isset($deal['idcurrency'])) {

				if ((int)$dealOld['idcurrency'] > 0 && (int)$deal['idcurrency'] == 0) {
					$deal['idcurrency'] = 0;
				}

				if ((int)$deal['idcurrency'] == 0 || ( (int)$dealOld['idcourse'] > 0 && (int)$deal['idcourse'] == 0 )) {
					$deal['idcourse'] = 0;
				}

			}

			$log = doLogger('did', $did, $newParams, $oldParams);

			if ($log != 'none') {

				//если спека была вкл., а щас выключили, то удаляем спеку
				if ($params['calculate'] == 'no' && $dealOld['calculate'] == 'yes') {

					$db -> query("DELETE FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity'");

				}

				try {

					$deal['datum_izm'] = current_datum();
					$deal['pid']       = ( (int)$params['pid'] == 0 ) ? 0 : (int)$params['pid'];

					$db -> query("UPDATE {$sqlname}dogovor SET ?u where did = '$did' and identity = '$identity'", $deal);

					//file_put_contents($rootpath."/cash/query.sql", $db -> lastQuery());

					$response['result'] = 'Успешно';
					$response['data']   = $did;

					$deal['did'] = $did;

					if ($hooks) {
						$hooks -> do_action("deal_edit", $post, $deal);
					}

					//пересчитаем маржу и сумму
					reCalculate($did);

					//привяжем договор к плательщику
					$db -> query("UPDATE {$sqlname}contract SET ?u WHERE deid = '$deal[dog_num]' and identity = '$identity'", [
						'payer' => (int)$deal['payer'],
						'did'   => $did
					]);

					addHistorty([
						"iduser"   => $iduser1,
						"did"      => $did,
						"clid"     => $params['clid'],
						"pid"      => $params['pid'],
						"datum"    => current_datumtime(),
						"des"      => $log,
						"tip"      => "ЛогCRM",
						"untag"    => "no",
						"identity" => $identity
					]);

					sendNotify('edit_dog', $params = [
						"did"        => $did,
						"title"      => untag($params['title']),
						"kol"        => getDogData($did, 'kol'),
						"clid"       => (int)$params['clid'],
						"client"     => getClientData($params['clid'], 'title'),
						"pid"        => (int)$params['pid'],
						"person"     => getPersonData($params['pid'], 'person'),
						"dogstatus"  => current_dogstep($did),
						"datum_plan" => untag($params['datum_plan']),
						"iduser"     => $iduser1,
						"notice"     => 'yes',
						"comment"    => untag($params['reazon']),
						"log"        => nl2br($log)
					]);


					/**
					 * Уведомления
					 */
					//require_once "Notify.php";
					$arg = [
						"did"        => $did,
						"title"      => untag($params['title']),
						"kol"        => getDogData($did, 'kol'),
						"clid"       => (int)$params['clid'],
						"client"     => getClientData($params['clid'], 'title'),
						"pid"        => (int)$params['pid'],
						"person"     => getPersonData($params['pid'], 'person'),
						"dogstatus"  => current_dogstep($did),
						"datum_plan" => untag($params['datum_plan']),
						"iduser"     => $iduser1,
						"notice"     => 'yes',
						"comment"    => $params['reazon'],
						"log"        => nl2br($log)
					];
					Notify ::fire("deal.edit", $iduser1, $arg);


					//разница в параметрах
					$diff2 = array_diff_ext($oldParams, $newParams);
					event ::fire('deal.edit', [
						"did"      => $did,
						"autor"    => $iduser1,
						"newparam" => $diff2
					]);

					$response['result'] = 'Готово';
					if (!empty($message)) {
						$response['text'] = $message;
					}

				}
				catch (Exception $e) {

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $e -> getMessage();

				}

			}
			else {

				$response['result']        = 'Данные корректны, но идентичны имеющимся.';
				$response['data']          = $did;
				$response['error']['code'] = 304;
				if (!empty($message)) {
					$response['text'] = $message;
				}

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 402;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Обновление информации по сделке
	 * Обновляет ВСЕ поля по сделке
	 * Позволяет очистить не нужные поля
	 *
	 * @param int $did - ID сделки
	 * @param array $params - массив с параметрами
	 *                        - integer **uid** – уникальный идентификатор во внешней ИС
	 *                        - string **title** | **title_dog** – название сделки (обязательное поле)
	 *                        - integer **clid** –идентификатор клиента, к которому будет привязана сделка
	 *                        (обязательное поле)
	 *                        - integer **payer** - идентификатор клиента-плательщика
	 *                        - date **datum_plan** – плановая дата закрытия сделки
	 *                        - date **datum_start** – период сделки. старт
	 *                        - date **datum_end** – период сделки. конец
	 *                        - integer **direction** – направление деятельности. Если не указано, то принимается
	 *                        значение по умолчанию из справочника
	 *                        - integer **tip** – тип сделки. Если не указано, то принимается значение по умолчанию из
	 *                        справочника
	 *                        - integer **idcategory** – ID этапа
	 *                        - integer **mcid** – идентификатор своей компании (можно получить в отдельном запросе).
	 *                        Если mcid всё-таки не указан, то возьмем последний использованный
	 *                        - integer **iduser** – login пользователя в SalesMan CRM назначаемого Ответственным за
	 *                        клиента
	 *                        - array **pid_list** - список id контактов, через запятую
	 *                        - string **прочие поля** ( inputXXX ) – информация для добавления
	 *                        - string **calculate** = yes/no - признак включения Спецификации. Должно быть "yes", если
	 *                        добавляется Спецификация
	 *                        - array **speka** - массив данных для добавления продуктов, для каждого добавляемого
	 *                        продукта содержит следующие данные:
	 *                        - integer **prid** - ID позиции прайса (n_id)
	 *                        - string **artikul** - артикул позиции прайса (уникальный идентификатор товара/услуги)
	 *                        - string **title** - наименование позиции
	 *                        - int **tip** - тип позиции ( 0 - товар, 1 - услуга, 2 - материал )
	 *                        - float **kol** - количество
	 *                        - float **dop** - доп.поле, по умолчанию = 1
	 *                        - float **price** - розничная цена (не обязателный параметр, при совпадении актикула или
	 *                        названия с позицией в прайсе берется из прайса)
	 *                        - float **price_in** - закупочная цена (не обязателный параметр, при совпадении актикула
	 *                        или названия с позицией в прайсе берется из прайса)
	 *                        - string **edizm** - единица измерения (не обязателный параметр, при совпадении актикула
	 *                        или названия с позицией в прайсе берется из прайса)
	 *                        - float **nds** - НДС, в %
	 *                        - string **comments** - комментарий к позиции
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 * @throws Exception
	 */
	public function fullupdate(int $did = 0, array $params = []): array {

		global $hooks;

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$mcDefault  = $GLOBALS['mcDefault'];
		$dirDefault = $GLOBALS['dirDefault'];
		$tipDefault = $GLOBALS['tipDefault'];

		$post = $params;
		$did  = (int)$did;

		$params = $hooks -> apply_filters("deal_editfilter", $params);

		$fields = dealFields();
		$did    = (int)$did;
		//$deal = $params;
		$deal = $newParams = $oldParams = [];

		$params['marga'] = ( isset($params['marg']) ) ? pre_format($params['marg']) : pre_format($params['marga']);
		unset($params['marg']);

		array_push($fields, "coid1", "pid_list", "calculate", "idcourse", "idcurrency");

		//Исключаем правки для полей закрытия сделки
		unset($fields["datum"], $fields["sid"], $fields["kol_fact"], $fields["datum_close"]);


		/*
		unset($fields["status_close"]);
		unset($fields["step"]);

		foreach($fields as $field){

			if(!isset($params[$field])) $params[$field] = '';

		}
		*/

		//print_r($params);

		$count = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		$dealOld = get_dog_info($did, 'yes');

		if ($did > 0 && $count < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			//если это форма сделки, то добавим доп.параметры
			//в противном случае будем обновлять только переданные данные
			if (isset($params['title']) || isset($params['title_dog'])) {

				//$params['payer'] = ( empty($params['payer']) ) ? $params['clid'] : $params['payer'];
				//$params['calculate'] = (isset($params['calculate'])) ? $params['calculate'] : $dealOld['calculate'];
				$params['title'] = ( !$params['title'] ) ? untag($params['title_dog']) : untag($params['title']);
				if (empty($params['title'])) {
					$deal['title'] = $dealOld['title'];
				}
				if( !empty($params['datum_start']) ){
					$params['datum_start'] = untag($params['datum_start']);
				}
				if( !empty($params['datum_end']) ){
					$params['datum_end'] = untag($params['datum_end']);
				}

				//$params['datum_start'] = ( !empty($params['datum_start']) ) ? untag($params['datum_start']) : NULL;
				//$params['datum_end']   = ( !empty($params['datum_end']) ) ? untag($params['datum_end']) : NULL;

				//по блоку Заявки
				if (!in_array("zayavka", (array)$fields['dogovor'])) {

					$deal['zayavka'] = 0;
					$deal['ztitle']  = '';

				}

			}

			if( !empty($params['payer']) ){
				$params['payer'] = (int)$params['payer'];
			}

			//print_r($params);

			if (isset($params['direction'], $params['tip'])) {

				$params['direction'] = is_numeric($params['direction']) ? (int)$params['direction'] : getStep(untag($params['direction']));
				$params['tip']       = is_numeric($params['tip']) ? (int)$params['tip'] : getDogTip(untag($params['tip']));

				//$params['direction'] = $params['direction'] ?? $dealOld['direction'];
				//$params['tip']       = $params['tip'] ?? $dealOld['tip'];

				//Мультиворонка
				//проверим этап, и если его нет в текущей воронке установим ближайший меньший
				$mFunnel       = getMultiStepList([
					"direction" => (int)$params['direction'],
					"tip"       => (int)$params['tip']
				]);
				$steps         = array_keys($mFunnel['steps']);
				$currentStepID = current_dogstepid($did);
				$currentStep   = current_dogstepname($currentStepID);

				//если этапа нет в новой цепочке
				if (!in_array(current_dogstepid($did), (array)$steps)) {

					$newStepID = array_shift($steps);

					//вычисляем ближайший меньший
					foreach ($steps as $k => $v) {

						if (current_dogstepname($k) >= $currentStep) {
							goto lbl;
						}
						else {
							$newStepID = $k;
						}

					}

					lbl:

					$oldParams["idcategory"] = $currentStepID;
					$newParams["idcategory"] = $newStepID;

					$deal['idcategory'] = $newStepID;

				}
				else {
					$deal['idcategory'] = $currentStepID;
				}

			}

			//print_r($params);

			if (!isset($params['pid_list'])) {
				$params['pid_list'] = '';
			}
			if (!isset($params['coid1'])) {
				$params['coid1'] = '';
			}

			//Обрабатываем данные
			foreach ($fields as $field) {

				//для АПИ проверяем пришедшие поля и если поля нет, то исключаем из запроса
				if ($params['fromapi'] && !array_key_exists($field, $params)) {
					continue;
				}

				//пропускаем все поля, относящиеся к Этапу, а также к закрытию сделки
				if (
					in_array($field, (array)$fields) && !in_array($field, [
						'idcategory',
						'category',
						'step',
						'sid',
						'close',
						'kol_fact',
						'datum_close',
						'status_close'
					])
				) {

					//если пришел массив, то преобразуем в строку
					if (is_array($params[$field])) {

						if (
							in_array($field, [
								'coid1',
								'pid_list'
							])
						) {
							$params[$field] = trim(yimplode(";", $params[$field]));
						}
						else {
							$params[$field] = trim(yimplode(", ", $params[$field]));
						}

					}

					switch ($field) {
						case 'datum':
							$deal[$field] = current_datum();
							break;
						case 'title':
							$deal[$field] = untag($params[$field]);
							break;
						case 'payer':
							$deal[$field] = ( $params[$field] < 1 ) ? (int)$params['clid'] : $params[$field];
							break;
						case 'mcid':
							$deal[$field] = ( $params[$field] < 1 ) ? $mcDefault : (int)$params[$field];
							break;
						case 'idcourse':
						case 'idcurrency':
							$deal[$field] = (int)$params[$field];
							break;
						case 'idcategory':
						case 'datum_izm':
						case 'des_fact':
						case 'kol_fact':

							break;
						case 'step':
							$field        = 'idcategory';
							$deal[$field] = getStep($params[$field]);
							break;
						case 'status_close':
							$field        = 'sid';
							$deal[$field] = getStatusClose($params[$field]);
							break;
						case 'kol':
						case 'marga':
							$deal[$field] = pre_format($params[$field]);
							break;
						case 'direction':

							//$deal[ $field ] = (is_numeric( $params[ $field ] )) ? (int)$params[ $field ] : getDirection( untag( $params[ $field ] ) );

							// через API может приходить название Направления
							if ((int)$params[$field] == 0 && !empty($params[$field])) {

								$deal[$field] = getDirection(untag($params[$field]));

							}
							else {

								$deal[$field] = (int)$params[$field];

							}

							if ((int)$params[$field] == 0) {
								$deal[$field] = $dirDefault;
							}

							break;
						case 'tip':

							//$deal[ $field ] = (is_numeric( $params[ $field ] )) ? (int)$params[ $field ] : getDogTip( untag( $params[ $field ] ) );

							// через API может приходить название Направления
							if ((int)$params[$field] == 0 && !empty($params[$field])) {

								$deal[$field] = getDogTip(untag($params[$field]));

							}
							else {

								$deal[$field] = (int)$params[$field];

							}

							if ((int)$params[$field] == 0) {
								$deal[$field] = $tipDefault;
							}

							break;

						default:
							//$deal[ $field ] = untag($params[ $field ]);
							if (self ::getFiledType($field) != 'datetime') {
								$deal[$field] = fieldClean($params[$field]);
							}
							else {
								$deal[$field] = str_replace([
									"Z",
									"T"
								], [
									":00",
									" "
								], $params[$field]);
							}
							break;
					}

				}

			}

			//эти параметры не изменяются
			unset($deal['did'], $deal['datum']);

			if (isset($params['calculate'])) {
				$deal['calculate'] = $params['calculate'];
			}

			foreach ($deal as $key => $value) {

				if (
					!in_array($key, [
						'did',
						'idcategory',
						'kol',
						'marga'
					])
				) {

					$newParams[$key] = $value;        //массив новых параметров
					$oldParams[$key] = $dealOld[$key];//массив старых параметров

				}

			}

			if ($dealOld['datum_start'] == NULL && array_key_exists('datum_start', (array)$params)) {
				$oldParams['datum_start'] = NULL;
			}
			if ($dealOld['datum_end'] == NULL && array_key_exists('datum_end', (array)$params)) {
				$oldParams['datum_end'] = NULL;
			}

			//проверка только если данные переданы из формы сделки
			if ($params['calculate'] != 'yes' && ( isset($params['title']) || isset($params['title_dog']) )) {

				if( !empty($params['kol']) ){
					$oldParams["kol"]   = $dealOld['kol'];
					$newParams["kol"]   = pre_format($params['kol']);
					$deal['kol']   = pre_format($params['kol']);
				}
				if( !empty($params['marga']) ){
					$oldParams["marga"] = $dealOld['marga'];
					$newParams["marga"] = pre_format($params['marga']);
					$deal['marga'] = pre_format($params['marga']);
				}

			}

			if (isset($deal['idcurrency'])) {

				if ($dealOld['idcurrency'] > 0 && $deal['idcurrency'] < 1) {
					$deal['idcurrency'] = 0;
				}

				if ($deal['idcurrency'] == 0 || ( $dealOld['idcourse'] > 0 && $deal['idcourse'] < 1 )) {
					$deal['idcourse'] = 0;
				}

			}

			//print_r($deal);
			//exit();

			// если UID отсутствует в запросе, то исключим из лога
			if (!isset($params['uid'])) {

				unset($newParams['uid'], $oldParams['uid']);

			}

			//$newParams['uid'] = ($dealOld['uid'] != '') ? $dealOld['uid'] : $deal['uid'];

			$log = doLogger('did', $did, $newParams, $oldParams);

			if ($log != 'none') {

				//если спека была вкл., а щас выключили, то удаляем спеку
				if ($params['calculate'] == 'no' && $dealOld['calculate'] == 'yes') {

					$db -> query("DELETE FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity'");

				}

				try {

					$deal['datum_izm'] = current_datum();
					$deal['pid']       = ( $params['pid'] < 1 ) ? 0 : (int)$params['pid'];
					$deal['uid']       = ( $dealOld['uid'] != '' ) ? $dealOld['uid'] : $deal['uid'];

					$db -> query("UPDATE {$sqlname}dogovor SET ?u where did = '$did' and identity = '$identity'", $deal);
					//file_put_contents($rootpath."/cash/query.sql", $db -> lastQuery());

					$response['result'] = 'Успешно';
					$response['data']   = $did;

					$deal['did'] = $did;

					if ($hooks) {
						$hooks -> do_action("deal_edit", $post, $deal);
					}

					//пересчитаем маржу и сумму
					reCalculate($did);

					//привяжем договор к плательщику
					$db -> query("update {$sqlname}contract set payer = '$deal[payer]', did = '$did' where deid = '$deal[dog_num]' and identity = '$identity'");

					addHistorty([
						"iduser"   => $iduser1,
						"did"      => $did,
						"clid"     => (int)$params['clid'],
						"pid"      => (int)$params['pid'],
						"datum"    => current_datumtime(),
						"des"      => $log,
						"tip"      => "ЛогCRM",
						"untag"    => "no",
						"identity" => $identity
					]);

					sendNotify('edit_dog', $params = [
						"did"        => $did,
						"title"      => untag($params['title']),
						"kol"        => getDogData($did, 'kol'),
						"clid"       => (int)$params['clid'],
						"client"     => getClientData($params['clid'], 'title'),
						"pid"        => (int)$params['pid'],
						"person"     => getPersonData($params['pid'], 'person'),
						"dogstatus"  => current_dogstep($did),
						"datum_plan" => untag($params['datum_plan']),
						"iduser"     => $iduser1,
						"notice"     => 'yes',
						"comment"    => untag($params['reazon']),
						"log"        => nl2br($log)
					]);


					/**
					 * Уведомления
					 */
					//require_once "Notify.php";
					$arg = [
						"did"        => $did,
						"title"      => untag($params['title']),
						"kol"        => getDogData($did, 'kol'),
						"clid"       => (int)$params['clid'],
						"client"     => getClientData($params['clid'], 'title'),
						"pid"        => (int)$params['pid'],
						"person"     => getPersonData($params['pid'], 'person'),
						"dogstatus"  => current_dogstep($did),
						"datum_plan" => $params['datum_plan'],
						"iduser"     => $iduser1,
						"notice"     => 'yes',
						"comment"    => untag($params['reazon']),
						"log"        => nl2br($log)
					];
					Notify ::fire("deal.edit", $iduser1, $arg);


					//разница в параметрах
					$diff2 = array_diff_ext($oldParams, $newParams);
					event ::fire('deal.edit', $args = [
						"did"      => $did,
						"autor"    => $iduser1,
						"newparam" => $diff2
					]);

					$response['result'] = 'Готово';

				}
				catch (Exception $e) {
					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $e -> getMessage();
				}

			}
			else {
				$response['result']        = 'Данные корректны, но идентичны имеющимся.';
				$response['data']          = $did;
				$response['error']['code'] = 302;
			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 402;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Удаление сделки
	 *
	 * @param int $did - ID сделки
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 * @throws Exception
	 */
	public function delete(int $did = 0): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$title = current_dogovor($did);

		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}credit WHERE did = '$did' and identity = '$identity'");
		//print $db -> lastQuery();

		if ($count > 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = '407';
			$response['error']['text'] = "Не возможно удалить сделку - есть счета";

		}
		else {

			if ($hooks) {
				$hooks -> do_action("deal_delete", $did);
			}

			//Удалим всю связанные файлы
			$res = $db -> query("select * from {$sqlname}file WHERE did='".$did."' and identity = '$identity'");
			while ($data = $db -> fetch($res)) {
				@unlink($this -> rootpath."/files/".$GLOBALS['fpath'].$data['fname']);
				$db -> query("delete from {$sqlname}file where fid = '$data[fid]' and identity = '$identity'");
			}

			$db -> query("update {$sqlname}contract set did = 0 where did = '$did' and identity = '$identity'");
			$db -> query("update {$sqlname}comments set did = 0 where did = '$did' and identity = '$identity'");
			$db -> query("update {$sqlname}leads set did = 0 where did = '$did' and identity = '$identity'");
			$db -> query("update {$sqlname}entry set did = 0 where did = '$did' and identity = '$identity'");
			$db -> query("update {$sqlname}tasks set did = 0 where did = '$did' and identity = '$identity'");

			$db -> query("delete from {$sqlname}history where did = '$did' and identity = '$identity'");
			$db -> query("delete from {$sqlname}speca where did = '$did' and identity = '$identity'");
			$db -> query("delete from {$sqlname}complect where did = '$did' and identity = '$identity'");
			$db -> query("delete from {$sqlname}dogovor where did = '$did' and identity = '$identity'");
			$db -> query("delete FROM {$sqlname}credit WHERE did='$did' and identity = '$identity'");

			logger('12', 'Удалена сделка '.$title, $iduser1);


			/**
			 * Уведомления
			 */
			//require_once "Notify.php";
			$arg = [
				"did"    => $did,
				"title"  => $title,
				"iduser" => $iduser1,
				"notice" => 'yes'
			];
			Notify ::fire("deal.delete", $iduser1, $arg);


			event ::fire('deal.delete', $args = [
				"did"   => $did,
				"autor" => $iduser1
			]);

			$response['result'] = 'Сделка удалена';
			$response['data']   = $did;

		}

		return $response;

	}

	/**
	 * Смена ответственного
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - int **newuser** - id нового
	 *                      - str **client_send** - yes|no - передать клиента
	 *                      - str **person_send** - yes|no - передать контакты
	 *                      - str **reason** - комментарий
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 */
	public function changeuser(int $did = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$mes = [];

		$did = (int)$did;

		$count = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0 && $count < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			$newuser = $params["newuser"];

			$olduser = getDogData($did, 'iduser');
			$clid    = getDogData($did, 'clid');

			$person_send = $params['person_send'];
			$client_send = $params['client_send'];

			$reazon = ( $params['reason'] == '' ) ? 'не указано' : $params['reason'];

			try {

				$db -> query("update {$sqlname}dogovor set ?u where did = '$did' and identity = '$identity'", ["iduser" => $newuser]);

				//переведем клиента
				if ($clid > 0 && $client_send == "yes") {

					try {

						if ($hooks) {
							$hooks -> do_action("deal_change_user", $params);
						}

						$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE clid = '$clid' and identity = '$identity'", ["iduser" => $newuser]);

					}
					catch (Exception $e) {

						$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					}

					//внесем запись в историю Сделки
					addHistorty([
						"iduser"   => $iduser1,
						"clid"     => $clid,
						"datum"    => current_datumtime(),
						"des"      => "Передача со сделкой. Причина: ".$reazon,
						"tip"      => "СобытиеCRM",
						"identity" => $identity
					]);

				}
				else {
					$mes[] = 'Клиент не переведен';
				}

				//переведем контакты
				if ($clid > 0 && $person_send == "yes") {

					$result = $db -> query("SELECT * FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity'");
					while ($data = $db -> fetch($result)) {

						try {

							$db -> query("update {$sqlname}personcat set iduser = '".$newuser."' where pid = '".$data['pid']."' and identity = '$identity'");

						}
						catch (Exception $e) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

						//внесем запись в историю Персоны
						addHistorty([
							"iduser"   => $iduser1,
							"pid"      => $data['pid'],
							"datum"    => current_datumtime(),
							"des"      => "Передача со сделкой. Причина: ".$reazon,
							"tip"      => "СобытиеCRM",
							"identity" => $identity
						]);

					}

				}
				else {
					$mes[] = 'Контакты не переведены';
				}

				//передадим контрольные точки
				$db -> query("UPDATE {$sqlname}complect SET ?u WHERE did = '$did' and identity = '$identity'", ["iduser" => $newuser]);

				//передадим напоминания
				$db -> query("UPDATE {$sqlname}tasks set iduser = '$newuser' WHERE did = '$did' and iduser = '$olduser'");

				//проверим счета
				$db -> query("UPDATE {$sqlname}credit set idowner = '$newuser' WHERE did = '$did' and do != 'on'");

				/**
				 * История
				 */
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => "Смена Ответственного: ".current_user($olduser)."&rarr;".current_user($newuser).". Причина: ".$reazon.". Изменил: ".current_user($iduser1),
					"tip"      => "СобытиеCRM",
					"identity" => $identity
				]);

				$deal = get_dog_info($did, 'yes');

				/**
				 * Уведомления
				 */
				sendNotify('send_dog', $params = [
					"did"        => $did,
					"title"      => $deal['title'],
					"kol"        => $deal['kol'],
					"clid"       => $deal['clid'],
					"client"     => getClientData($deal['clid'], 'title'),
					"pid"        => $deal['pid'],
					"person"     => getPersonData($deal['pid'], 'person'),
					"dogstatus"  => current_dogstep($deal['did']),
					"datum_plan" => $deal['datum_plan'],
					"iduser"     => $newuser,
					"notice"     => 'yes',
					"comment"    => $reazon
				]);


				/**
				 * Уведомления
				 */
				//require_once "Notify.php";
				$arg = [
					"did"        => $deal['did'],
					"title"      => $deal['title'],
					"kol"        => $deal['kol'],
					"clid"       => $deal['clid'],
					"client"     => getClientData($deal['clid'], 'title'),
					"pid"        => $deal['pid'],
					"person"     => getPersonData($deal['pid'], 'person'),
					"dogstatus"  => current_dogstep($deal['did']),
					"datum_plan" => $deal['datum_plan'],
					"iduser"     => $newuser,
					"notice"     => 'yes',
					"comment"    => $reazon
				];
				Notify ::fire("deal.userchange", $iduser1, $arg);


				/**
				 * Событие
				 */
				event ::fire('deal.change.user', $args = [
					"did"     => $did,
					"autor"   => $iduser1,
					"olduser" => $olduser,
					"newuser" => $newuser,
					"comment" => $reazon
				]);

				$response['result'] = 'Успешно. '.implode("<br>", $mes);
				$response['data']   = $did;

			}
			catch (Exception $e) {

				$err = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = $err;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Смена плановой даты
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - date **newdate** - новая дата
	 *                      - str **reason** - комментарий(причина смены)
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 */
	public function changeDatumPlan(int $did = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$mes = [];

		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0 && $count == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			$newdate = $params["newdate"];

			$olddate = getDogData($did, 'datum_plan');

			$reazon = ( $params['reason'] == '' ) ? 'не указано' : $params['reason'];

			try {

				if ($hooks) {
					$hooks -> do_action("deal_change_datumplan", $params);
				}

				$db -> query("update {$sqlname}dogovor set ?u where did = '$did' and identity = '$identity'", ["datum_plan" => $newdate]);

				//запись в историю активности
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => "Смена Плановой даты: ".get_date($olddate)."&rarr;".get_date($newdate).". Причина: ".$reazon.". Изменил: ".current_user($iduser1),
					"tip"      => "СобытиеCRM",
					"identity" => $identity
				]);

				$deal = get_dog_info($did, 'yes');

				sendNotify('edit_dog', $params = [
					"did"        => $did,
					"title"      => $deal['title'],
					"kol"        => $deal['kol'],
					"clid"       => $deal['clid'],
					"client"     => getClientData($deal['clid'], 'title'),
					"pid"        => $deal['pid'],
					"person"     => getPersonData($deal['pid'], 'person'),
					"dogstatus"  => current_dogstep($deal['did']),
					"datum_plan" => $newdate,
					"iduser"     => $deal['autor'],
					"notice"     => 'yes',
					"comment"    => $reazon
				]);

				event ::fire('deal.change.datum_plan', $args = [
					"did"     => $did,
					"autor"   => $iduser1,
					"olddate" => $olddate,
					"newdate" => $newdate,
					"comment" => $reazon
				]);

				$response['result'] = 'Успешно. '.implode("<br>", $mes);
				$response['data']   = $did;

			}
			catch (Exception $e) {

				$err = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = $err;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Смена этапа сделки
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - int **step** - id нового этапа
	 *                      - str **description** - комментарий
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 */
	public function changestep(int $did = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$rootpath = $this -> rootpath;

		$isCatalog = $GLOBALS['isCatalog'];

		$mes = [];

		$did = (int)$did;

		$count = $db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0 && $count < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			$description = untag($params['description']);
			$step        = $params['step'];

			//найдем категорию Кт. соответствующие текущему этапу
			$ccid = $db -> getOne("SELECT ccid FROM {$sqlname}complect_cat WHERE dstep = '$step' and identity = '$identity'");

			//найдем КТ, которая есть в этой сделке
			$cpid = $db -> getOne("SELECT id FROM {$sqlname}complect WHERE ccid = '$ccid' and did = '$did' and identity = '$identity'");

			//$oldStep = getPrevNextStep($step);

			$stepOld = current_dogstepid($did);

			$oldstep = current_dogstepname($stepOld);
			$newstep = current_dogstepname($step);

			if($oldstep != $newstep) {

				try {

					$db -> query("update {$sqlname}dogovor set idcategory = '$params[step]' where did = '$did' and identity = '$identity'");

					$params['step']    = $step;
					$params['stepOld'] = $stepOld;

					$params['stepValue']    = $newstep;
					$params['stepValueOld'] = $oldstep;

					$params['reason'] = $description;

					if ($hooks) {
						$hooks -> do_action("deal_change_step", $params);
					}

					//добавляем смену этапа в лог
					DealStepLog($did, $step);

					if ((int)$newstep > (int)$oldstep) {//если новый этап больше старого

						//Отметим выполненной КТ
						if ($cpid > 0) {

							$ctitle = $db -> getOne("SELECT title FROM {$sqlname}complect_cat WHERE ccid = (SELECT ccid FROM {$sqlname}complect WHERE id = '".$cpid."' and identity = '$identity') and identity = '$identity'");

							//$cstep = current_dogstepname($dstep);//этап, связанный с КТ

							$db -> query("update {$sqlname}complect set data_fact = '".current_datumtime()."', doit = 'yes' where id = '".$cpid."' and identity = '$identity'");
							$mes[] = "Поставлена отметка о выполнении Контрольной точки - ".$ctitle.".";

						}

					}

					//Внесем запись в историю активностей
					$mes[] = 'Этап сделки изменен на '.$newstep.'%. Предыдущий этап '.$oldstep.'%.';

					$msg = implode("\n", $mes);

					if ($description != '') {
						$msg .= "Примечание: ".$description;
					}
					else {
						$msg .= "Примечание: Причина не указана";
					}

					addHistorty([
						"did"      => $did,
						"datum"    => current_datumtime(),
						"des"      => $msg,
						"iduser"   => $iduser1,
						"tip"      => 'СобытиеCRM',
						"identity" => $identity
					]);

					$data = get_dog_info($did, 'yes');

					sendNotify('step_dog', $params = [
						"did"          => $did,
						"title"        => $data['title'],
						"kol"          => $data['kol'],
						"clid"         => $data['clid'],
						"client"       => getClientData($data['clid'], 'title'),
						"pid"          => $data['pid'],
						"person"       => getPersonData($data['pid'], 'person'),
						"dogstatus"    => $newstep,
						"dogstatusold" => $oldstep,
						"datum_plan"   => $data['datum_plan'],
						"iduser"       => $data['iduser'],
						"notice"       => 'yes',
						"comment"      => $description
					]);


					/**
					 * Уведомления
					 */
					//require_once "Notify.php";
					$arg = [
						"did"          => $did,
						"title"        => $data['title'],
						"kol"          => $data['kol'],
						"clid"         => $data['clid'],
						"client"       => getClientData($data['clid'], 'title'),
						"pid"          => $data['pid'],
						"person"       => getPersonData($data['pid'], 'person'),
						"dogstatus"    => $newstep,
						"dogstatusold" => $oldstep,
						"datum_plan"   => $data['datum_plan'],
						"iduser"       => $data['iduser'],
						"notice"       => 'yes',
						"comment"      => $description
					];
					Notify ::fire("deal.step", $iduser1, $arg);


					$response['result'] = nl2br(implode("<br>", $mes));
					$response['data']   = $did;

					//обновляем резерв каталога при смене этапа
					//тормозит систему
					if ($isCatalog == 'on') {

						//require_once $this -> rootpath."/modules/modcatalog/mcfunc.php";
						//mcSyncReserv("no", $did);

						$response['sklad'] = ( new Storage ) -> mcSyncPoz("no", ["did" => $did]);

						$settings = json_decode($db -> getOne("SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'"), true);

						if ($settings['mcAutoRezerv'] == 'yes' && $settings['mcStep'] == $newstep) {

							( new Storage ) -> mcSyncReserv('no');

						}

					}

					event ::fire('deal.change.step', $args = [
						"did"     => $did,
						"autor"   => $iduser1,
						"stepOld" => $oldstep,
						"stepNew" => $newstep,
						"reason"  => $description,
						"comment" => $description
					]);

				}
				catch (Exception $e) {

					$err = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

					$response['result']        = 'Error';
					$response['error']['code'] = 500;
					$response['error']['text'] = $err;

				}

			}
			else{

				$response['result']        = 'Error';
				$response['error']['code'] = 302;
				$response['error']['text'] = "Данные корректны, но идентичны имеющимся";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Закрытие сделки
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - date **datum_close** - дата закрытия
	 *                      - int **sid** - id статуса закрытия
	 *                      - str **status_close** - название статуса закрытия (вместо sid) - win|lose
	 *                      - date **des_fact** - комментарий
	 *                      - float **kol_fact** - факт.сумма сделки
	 *                      - float **marga** - факт.маржа сделки
	 *                      - int **coid **- id конкурента,выигравшего сделку
	 *                      - float **co_kol** - сумма конкурента
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 */
	public function changeClose(int $did = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$post = $params;

		$did = (int)$did;

		$mes = [];
		$err = '';

		//print_r($params, true);

		$count = $db -> getRow("SELECT did, sid FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0 && (int)$count['did'] == 0) {

			$response['result']        = 'Error';
			$response['data']          = "Сделка не найдена";
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && (int)$count['sid'] > 0) {

			$response['result']        = 'Error';
			$response['data']          = "Сделка уже закрыта";
			$response['error']['code'] = 407;
			$response['error']['text'] = "Сделка уже закрыта";

		}
		elseif ($did > 0 && (int)$count['did'] > 0) {

			$status = $db -> getOne("SELECT result_close FROM {$sqlname}dogstatus WHERE sid = '".(int)$params['sid']."' AND identity = '$identity' ORDER by title");

			$deal['datum_close'] = ( $params['datum'] != '' && isset($params['datum']) ) ? untag($params['datum']) : current_datum();
			$deal['sid']         = (int)$params['sid'];
			$deal['des_fact']    = untag($params['des_fact']);
			$deal['coid']        = (int)$params['coid'];
			$deal['co_kol']      = pre_format($params['co_kol']);

			$deal['marga']       = isset($params['marga']) ? pre_format($params['marga']) : (float)getDogData($did, 'marga');
			$deal['kol_fact']    = isset($params['kol_fact']) ? pre_format($params['kol_fact']) : (float)getDogData($did, 'kol');

			$deal['close']       = 'yes';

			if ($status == 'lose') {
				$deal['marga'] = 0;
			}

			//если статус закрытия передается текстом
			if ($deal['sid'] == 0 && !empty($params['status_close'])) {

				$params['status_close'] = getStatusClose($params['status_close']);
				$deal['sid']            = ( (int)$deal['sid'] > 0 && $params['status_close'] == 0 ) ? (int)$deal['sid'] : (int)$params['status_close'];

			}

			if (isset($params['description'])) {

				$deal['des_fact'] = untag($params['description']);

			}

			$data = get_dog_info($did, 'yes');

			//print_r($data);

			//последний этап сделки по процессу
			$lastStep = (int)$db -> getOne("select idcategory from {$sqlname}dogcategory where identity = '$identity' ORDER BY CAST(title AS UNSIGNED) DESC LIMIT 1");

			//если этап не максимальный и сделка успешная
			//то установим этот этам (обычно 100%) и занесем в лог
			if ($data['idcategory'] != $lastStep && $deal['kol_fact'] > 0) {

				$deal['idcategory'] = $lastStep;
				DealStepLog($did, $lastStep);

			}

			try {

				$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did' and identity = '$identity'", $deal);

				/*if($did == 3020){
					file_put_contents($this -> rootpath."/cash/query$did.sql", $db -> lastQuery());
					file_put_contents($this -> rootpath."/cash/data$did.json", json_encode_cyr($params));
				}*/

				$deal['did'] = $did;

				if ($hooks) {
					$hooks -> do_action("deal_close", $post, $deal);
				}

				$status = $db -> getOne("SELECT title FROM {$sqlname}dogstatus WHERE sid = '$deal[sid]' AND identity = '$identity'");

				$mes = "Сделка закрыта со статусом ".$status;

				//внесем запись о дате последней сделки
				$db -> query("UPDATE {$sqlname}clientcat SET ?u where clid = '$data[clid]' and identity = '$identity'", ["last_dog" => $deal['datum']]);

				//Внесем запись в историю активностей
				addHistorty([
					"iduser"   => $iduser1,
					"did"      => $did,
					"des"      => $mes,
					"tip"      => "СобытиеCRM",
					"datum"    => current_datumtime(),
					"identity" => $identity
				]);

				//отправим уведомление
				sendNotify('close_dog', $param = [
					"did"         => $did,
					"title"       => $data['title'],
					"kol"         => $data['kol'],
					"clid"        => $data['clid'],
					"client"      => getClientData($data['clid'], 'title'),
					"pid"         => $data['pid'],
					"person"      => getPersonData($data['pid'], 'person'),
					"dogstatus"   => current_dogstep($data['did']),
					"datum_plan"  => $data['datum_plan'],
					"kol_fact"    => $data['kol_fact'],
					"marga"       => $data['marga'],
					"status"      => $status,
					"datum_close" => $params['datum'],
					"iduser"      => $data['iduser'],
					"notice"      => 'yes',
					"comment"     => $deal['des_fact']
				]);


				/**
				 * Уведомления
				 */
				//require_once "Notify.php";
				$arg = [
					"did"         => $did,
					"title"       => $data['title'],
					"kol"         => $data['kol'],
					"clid"        => $data['clid'],
					"client"      => getClientData($data['clid'], 'title'),
					"pid"         => $data['pid'],
					"person"      => getPersonData($data['pid'], 'person'),
					"dogstatus"   => current_dogstep($data['did']),
					"datum_plan"  => $data['datum_plan'],
					"kol_fact"    => $data['kol_fact'],
					"marga"       => $data['marga'],
					"status"      => $status,
					"datum_close" => $params['datum'],
					"iduser"      => $data['iduser'],
					"notice"      => 'yes',
					"comment"     => $deal['des_fact']
				];
				Notify ::fire("deal.close", $iduser1, $arg);


				//print_r($param);

				event ::fire('deal.close', $args = [
					"did"      => $did,
					"autor"    => $iduser1,
					"summa"    => $deal['kol_fact'],
					"status"   => $status,
					"identity" => $identity
				]);

				//Добавим сумму факт.сделки в Потенциал
				set_capacity($did);

			}
			catch (Exception $e) {

				$err = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

			if ($err == '') {

				$response['result'] = 'Ok';
				$response['data']   = $mes;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = $err;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Восстановление закрытой сделки
	 *
	 * @param int $did
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 */
	public function changeUnclose(int $did = 0): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		try {

			$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = $did", [
				"close"       => 'no',
				"datum_close" => NULL,
				"sid"         => NULL,
				"kol_fact"    => NULL,
				"des_fact"    => NULL,
				"coid"        => NULL,
				"co_kol"      => NULL
			]);

			//пересчитаем маржу и сумму
			reCalculate($did);

			if ($hooks) {
				$hooks -> do_action("deal_restore", $did);
			}

			//запись в историю активности
			addHistorty([
				"iduser"   => $iduser1,
				"did"      => $did,
				"datum"    => current_datumtime(),
				"des"      => 'Закрытая сделка восстановлена',
				"tip"      => "СобытиеCRM",
				"identity" => $identity
			]);

			$response['result'] = "Вы Восстановили закрытую сделку";

		}
		catch (Exception $e) {

			$response['result']        = 'Error';
			$response['error']['code'] = 500;
			$response['error']['text'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		return $response;

	}

	/**
	 * Изменение периода сделки
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - date **datum_start** – период сделки. старт
	 *                      - date **datum_end** – период сделки. конец
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 * @throws Exception
	 */
	public function changePeriod(int $did = 0, array $params = []): array {

		return $this -> update($did, $params);

	}

	/**
	 * Управление доступом к сделке
	 *
	 * @param int $did
	 * @param array $params - массив с параметрами
	 *                      - array **dostup**
	 *                      - int **iduser** => notify ( on/off )
	 *
	 *
	 * Примечание:
	 *  **notify** - признак того, что пользователя надо подписать на уведомления о изменениях в сделке
	 *
	 * @return array
	 * good result
	 *         - result = Успешно
	 *         - data = id
	 *         - text = Комментарий
	 *
	 * error result
	 *         - result = result
	 *         - error
	 *              - code
	 *              - text
	 */
	public function changeDostup(int $did = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$post = $params;

		$did = (int)$did;

		$mes = $userlist = $subscribe = $list = [];

		//массив, который передается из формы управления доступом
		foreach ($params['user'] as $k => $user) {

			$list[(int)$user] = $params['notify'][$k] ?? 'off';

		}

		//массив, который передается из других методов
		foreach ($params['dostup'] as $i => $v) {

			$list[(int)$v['iduser']] = $v['notify'] ?? 'off';

		}

		$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'");

		if ($did > 0 && $count == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = '403';
			$response['error']['text'] = "Сделка не найдена";

		}
		elseif ($did > 0 && $count > 0) {

			$good = 0;
			$err  = [];

			//список пользователей, которые имеют доступ
			$dostup = $db -> getCol("SELECT iduser FROM {$sqlname}dostup WHERE did = '$did' and identity = '$identity'");

			//удаление доступа
			foreach ($dostup as $iduser) {

				//если пользователя нет в списке, то удаляем его
				//игнорируем, если действие для одного сотрудника
				if (!array_key_exists((int)$iduser, $list) && !isset($params['dostup']) && count((array)$params['user']) > 1) {
					$db -> query("delete from {$sqlname}dostup where did = '$did' and iduser = '$iduser' and identity = '$identity'");
				}

				if (empty($list)) {
					$db -> query("delete from {$sqlname}dostup where did = '$did' and iduser = '$iduser' and identity = '$identity'");
				}

			}

			//добавление доступа
			foreach ($list as $user => $notify) {

				//если пользователя нет в списке, то добавляем
				if (!in_array((int)$user, (array)$dostup)) {

					$db -> query("INSERT INTO {$sqlname}dostup SET ?u", arrayNullClean([
						"did"       => $did,
						"iduser"    => $user,
						"subscribe" => $notify,
						"identity"  => $identity
					]));

				}

				//если есть в списке, то обновляем подписку
				else {

					$db -> query("UPDATE {$sqlname}dostup SET ?u WHERE iduser = '$user' and did = '$did'", ["subscribe" => $notify]);

				}
				$good++;

			}

			if ($hooks) {
				$hooks -> do_action("deal_change_dostup", $post);
			}

			if (empty($err)) {

				$mes['count']  = $good;
				$mes['errors'] = 0;

				$response['result']        = 'Ok';
				$response['data']          = $mes;
				$response['error']['text'] = $err;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '500';
				$response['error']['text'] = $err;

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

		return $response;

	}

	/**
	 * Заморозка|заморозка сделки
	 *
	 * @param int $did
	 * @param string $date - если указана, то замораживаем до этой даты
	 * @return array
	 * @throws Exception
	 */
	public function changeFreeze(int $did = 0, string $date = ''): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$iduser1  = $this -> iduser1;
		$identity = $this -> identity;

		$response = [];

		$state = (int)$db -> getOne("SELECT isFrozen FROM {$sqlname}dogovor WHERE did = '$did'");

		if ($state == 0) {

			$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did'", [
				"isFrozen"                                      => '1',
				$GLOBALS['otherSettings']['dateFieldForFreeze'] => $date
			]);

			if ($hooks) {
				$hooks -> do_action("deal_freeze", [
					"did"  => $did,
					"date" => $date
				]);
			}

			$response['result'] = "Сделка заморожена";

			//запись в историю активности
			addHistorty([
				"iduser"   => $iduser1,
				"did"      => $did,
				"datum"    => current_datumtime(),
				"des"      => $response['result'],
				"tip"      => "СобытиеCRM",
				"identity" => $identity
			]);

		}
		else {

			$db -> query("UPDATE {$sqlname}dogovor SET ?u WHERE did = '$did'", [
				"isFrozen"                                      => '0',
				$GLOBALS['otherSettings']['dateFieldForFreeze'] => NULL
			]);

			if ($hooks) {
				$hooks -> do_action("deal_freeze", ["did" => $did]);
			}

			$response['result'] = "Сделка разморожена";

			//запись в историю активности
			addHistorty([
				"iduser"   => $iduser1,
				"did"      => $did,
				"datum"    => current_datumtime(),
				"des"      => $response['result'],
				"tip"      => "СобытиеCRM",
				"identity" => $identity
			]);

		}

		return $response;

	}

	/**
	 * Вывод списка сделок по клиенту для карточки клиента
	 * @param array $params
	 *  - int clid
	 *  - string dealsSort = DESC|NULL - сортировка списка по дате создания
	 *  - bool bytype = TRUE (default FALSE) - разделять сделки на подмассив с ключами "close"/"active" по признаку активности сделки
	 * @return array
	 * @throws Exception
	 */
	public function card(array $params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$otherSettings = $this -> otherSettings;
		$settingsUser  = $this -> settingsUser;
		$iduser1       = $this -> iduser1;

		$valuta      = $GLOBALS['valuta'];
		$fieldsNames = $GLOBALS['fieldsNames'];
		$fieldsOn    = $GLOBALS['fieldsOn'];
		$isMobile    = $GLOBALS['isMobile'];

		$clid    = (int)$params['clid'];
		$pid     = (int)$params['pid'];
		$docSort = $params['dealsSort'];

		$s      = '';
		$client = '';
		$list   = [];

		if ($clid > 0) {
			$s      = "deal.clid = '$clid' OR deal.payer = '$clid'";
			$client = current_client($clid);
		}
		if ($pid > 0) {
			$s      = "deal.pid = '$pid'";
			$client = current_person($pid);
		}

		$ssort = ( $docSort == 'DESC' ) ? '' : 'DESC';
		$icon  = ( $docSort == 'DESC' ) ? 'icon-sort-alt-down' : 'icon-sort-alt-up';

		$inputs = [];
		$res    = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order");
		while ($fld = $db -> fetch($res)) {

			$inputs["deal.".$fld['fld_name']] = [
				"field" => $fld['fld_name'],
				"title" => $fld['fld_title'],
				"type"  => $fld['fld_temp'],
			];

		}

		$res = $db -> getAll("
			SELECT 
				deal.did,
				deal.uid,
				deal.clid,
				cc.title as payerName,
				deal.payer,
				deal.pid,
				deal.title,
				deal.content,
				deal.datum,
				deal.datum_plan,
				deal.datum_close,
				deal.datum_start,
				deal.datum_end,
				deal.kol,
				deal.marga,
				deal.adres,
				deal.idcategory,
				dc.title as step,
				dc.content as stepContent,
				deal.dog_num,
				deal.mcid,
				comp.name_shot as company,
				deal.direction,
				dir.title as directionName,
				deal.tip,
				dt.title as tipName,
				deal.iduser,
				deal.autor,
				".( !empty(array_keys($inputs)) ? yimplode(",", array_keys($inputs))."," : "" )."
				COALESCE(deal.close, 'no') as close,
				deal.datum_close,
				deal.sid,
				deal.kol_fact,
				deal.des_fact,
				st.title as closeStatus,
				st.content as closeContent
			FROM {$sqlname}dogovor `deal`
				LEFT JOIN {$sqlname}dogcategory `dc` ON dc.idcategory = deal.idcategory
				LEFT JOIN {$sqlname}direction `dir` ON dir.id = deal.direction
				LEFT JOIN {$sqlname}dogtips `dt` ON dt.tid = deal.tip
				LEFT JOIN {$sqlname}mycomps `comp` ON comp.id = deal.mcid
				LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = deal.payer
				LEFT JOIN {$sqlname}dogstatus `st` ON st.sid = deal.sid
			WHERE 
				$s and 
				deal.identity = '$identity' 
			ORDER by FIELD( IF( IFNULL(deal.close, 'no') = '', 'no', IFNULL(deal.close, 'no')), NULL, 'no', '', 'yes'), deal.datum_plan 
			$ssort
		");

		foreach ($res as $row) {

			$contract = $db -> getRow("SELECT number, datum_start FROM {$sqlname}contract WHERE deid = '".$row['dog_num']."' and identity = '$identity'");

			$r = [
				"did"           => (int)$row['did'],
				"uid"           => !empty($row['uid']) ? $row['uid'] : NULL,
				"close"         => $row['close'] == 'yes' ? true : NULL,
				"iduser"        => (int)$row['iduser'],
				"user"          => current_user((int)$row['iduser'] ),
				"autor"         => (int)$row['autor'],
				"autorName"     => current_user( (int)$row['autor'] ),
				"title"         => $row['title'],
				"content"       => $row['content'],
				"html"          => nl2br($row['content']),
				"datum"         => $row['datum'],
				"datumf"        => format_date_rus_name($row['datum_plan']),
				"datum_plan"    => $row['datum_plan'],
				"datum_planf"   => format_date_rus_name($row['datum_plan']),
				"datum_close"   => $row['datum_close'] != '0000-00-00' && !is_null($row['datum_close']) ? $row['datum_close'] : NULL,
				"datum_closef"  => $row['datum_close'] != '0000-00-00' && !is_null($row['datum_close']) ? format_date_rus_name($row['datum_close']) : NULL,
				"datum_start"   => $row['datum_start'] != '0000-00-00' && !is_null($row['datum_start']) ? $row['datum_start'] : NULL,
				"datum_startf"  => $row['datum_start'] != '0000-00-00' && !is_null($row['datum_start']) ? format_date_rus_name($row['datum_start']) : NULL,
				"datum_end"     => $row['datum_end'] != '0000-00-00' && !is_null($row['datum_end']) ? $row['datum_end'] : NULL,
				"datum_endf"    => $row['datum_end'] != '0000-00-00' && !is_null($row['datum_end']) ? format_date_rus_name($row['datum_end']) : NULL,
				"contract"      => !empty($contract) ? [
					"number" => $contract['number'],
					"date"   => $contract['datum_start'],
					"datef"  => format_date_rus_name($contract['datum_start']),
				] : NULL,
				"adres"         => !empty($row['adres']) && in_array('adres', $fieldsOn['dogovor']) ? $row['adres'] : NULL,
				"kol"           => (float)$row['kol'],
				"kolf"          => num_format($row['kol']),
				"marga"         => (float)$row['marga'],
				"margaf"        => num_format($row['marga']),
				"kol_fact"      => (float)$row['kol_fact'],
				"kol_factf"     => num_format($row['kol_fact']),
				"des_fact"      => $row['des_fact'],
				"html_fact"     => nl2br($row['des_fact']),
				"closeStatus"   => $row['closeStatus'],
				"closeContent"  => $row['closeContent'],
				"userlogin"     => current_userlogin($iduser1),
				"mcid"          => (int)$row['mcid'],
				"company"       => $row['company'],
				"tip"           => (int)$row['tip'],
				"tipName"       => in_array('tip', $fieldsOn['dogovor']) ? $row['tipName'] : NULL,
				"direction"     => (int)$row['direction'],
				"directionName" => in_array('direction', $fieldsOn['dogovor']) ? $row['directionName'] : NULL,
				"step"          => $row['step'],
				"stepContent"   => $row['stepContent'],
				"payer"         => ( (int)$row['payer'] != (int)$row['clid'] ) ? (int)$row['payer'] : NULL,
				"payerName"     => ( (int)$row['payer'] != (int)$row['clid'] ) ? $row['payerName'] : NULL,
				"showPeriod"    => $row['datum_start'] != "0000-00-00" && !is_null($row['datum_start']) && in_array('period', $fieldsOn['dogovor']),
				"showButtons"   => ( get_accesse(0, 0, (int)$row['did']) == "yes" || (int)$row['iduser'] == (int)$iduser1 ) ? true : NULL,
			];

			foreach ($inputs as $field) {

				if (empty($row[$field['field']])) {
					continue;
				}

				$r['inputs'][] = [
					"field"     => $field['title'],
					"value"     => $row[$field['field']],
					"html"      => nl2br($row[$field['field']]),
					"format"    => $field['type'] == 'datum' ? format_date_rus_name($row[$field['field']]) : NULL,
					"formattime"    => $field['type'] == 'datetime' ? modifyDatetime($row[$field['field']], ["format" => "d.m.Y H:s"]) : NULL,
					"isDate"    => $field['type'] == 'datum' ? true : NULL,
					"isDateTime" => $field['type'] == 'datetime' ? true : NULL,
					"isAddress" => $field['type'] == 'adres' ? true : NULL,
				];

			}

			$state = $row['close'] == 'yes' ? "close" : "active";

			if( $params['bytype'] ) {

				$list[$state][] = $r;

			}
			else{

				$list[] = $r;

			}

		}

		return [
			"clid"          => (int)$params['clid'],
			"list"          => $list,
			"isEmpty"       => empty($list) ? true : NULL,
			"icon"          => $icon,
			"client"        => $client,
			"valuta"        => $valuta,
			"fieldsNames"   => $fieldsNames['dogovor'],
			"showMarga"     => ( $settingsUser['show_marga'] == 'yes' && $otherSettings['marga'] ),
			"settingsUser"  => $this -> settingsUser,
			"otherSettings" => $otherSettings,
			"iduser1"       => $iduser1,
			"isMobile"      => $isMobile
		];

	}

	public function Fields($filter = ""): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$ifields[] = 'did';
		$ifields[] = 'uid';
		$ifields[] = 'datum';
		$ifields[] = 'datum_izm';
		$ifields[] = 'clid';
		$ifields[] = 'title';

		$resf = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'");
		while ($do = $db -> fetch($resf)) {

			if ($do['fld_name'] == 'idcategory') {
				$ifields[] = 'step';
			}
			elseif ($do['fld_name'] == 'marg') {
				$ifields[] = 'marga';
			}
			else {
				$ifields[] = $do[ 'fld_name' ];
			}

		}

		$ifields[] = 'datum_start';
		$ifields[] = 'datum_end';
		$ifields[] = 'dog_num';
		$ifields[] = 'close';
		$ifields[] = 'datum_close';
		$ifields[] = 'status_close';
		$ifields[] = 'des_fact';
		$ifields[] = 'kol_fact';

		//фильтр вывода по полям из запроса или все доступные
		if (!empty($filter)) {

			$fi     = yexplode(",", $filter);
			$fields = [];

			foreach ($fi as $f) {
				if (in_array($f, $ifields)) {
					$fields[] = $f;
				}
			}

			return $fields;

		}

		return $ifields;

	}

	/**
	 * Этапы (для API)
	 * @return array
	 */
	public function Steps(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$response = [];

		$stepInHold = customSettings('stepInHold');

		$re = $db -> query("SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {

			$z = [
				"idcategory" => (int)$do['idcategory'],
				"title"      => $do['title'],
				"content"    => $do['content']
			];

			if((int)$stepInHold['step'] == (int)$do['idcategory']){
				$z['inHold'] = true;
				$z['inHoldInput'] = $stepInHold['input'];
			}

			$response[] = $z;

		}

		return $response;

	}

	/**
	 * Направления (Для API))
	 * @return array
	 */
	public function Direction(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$response = [];

		$re = $db -> query("SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {

			$response[] = [
				"id"        => (int)$do['id'],
				"title"     => $do['title'],
				"isDefault" => $do['isDefault']
			];

		}

		return $response;

	}

	/**
	 * Типы сделок (для API)
	 * @return array
	 */
	public function dealTypes(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$response = [];

		$re = $db -> query("SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {

			$r = [
				"tid"       => (int)$do['tid'],
				"title"     => $do['title'],
				"isDefault" => $do['isDefault']
			];

			if(empty($r['isDefault'])){
				unset($r['isDefault']);
			}

			$response[] = $r;

		}

		return $response;

	}

	public function list($params = []): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		global $isadmin;

		//составляем списки доступных полей для сделок
		$fields = $this -> Fields((string)$params['fields']);

		//задаем лимиты по-умолчанию
		$page = $params['page'];
		$ord  = !empty($params['ord']) ? $params['ord'] : 'datum';
		$tuda  = !empty($params['tuda']) ? $params['tuda'] : 'DESC';

		$limit = 200;
		$sort  = '';

		$synonyms = [
			"date_chage"  => "datum_izm",
			"date_start"  => "datum_start",
			"date_end"    => "datum_end",
			"date_close"  => "datum_close",
			"date_create" => "datum"
		];

		if ($ord == 'date_create') {
			$ord = 'datum';
		}
		elseif ($ord == 'date_change') {
			$ord = 'datum_izm';
		}

		//print "$ord\n";

		if(array_key_exists($ord, $synonyms)){
			$ord = $synonyms[$ord];
			//print "$ord\n";
		}


		if ($params['user'] != '') {
			$iduser1 = current_userbylogin($params['user']);
		}

		if ((int)$params['iduser'] > 0) {
			$iduser1 = (int)$params['iduser'];
		}

		//$sort .= get_people($iduser);

		if ($params['active'] == 'no' || !$params['active']) {
			$sort .= " and COALESCE(deal.close, 'no') = 'yes'";
		}
		elseif ($params['active'] == 'yes' || $params['active']) {
			$sort .= " and COALESCE(deal.close, 'no') != 'yes'";
		}

		if ($params['word'] != '') {
			$sort .= " and (deal.title LIKE '%".$params['word']."%' or deal.content LIKE '%".$params['word']."%' or deal.adres LIKE '%".$params['word']."%')";
		}

		if ($params['client'] != '') {
			$sort .= " and cc.title LIKE '%".$params['client']."%'";
		}

		if ($params['steps'] != '') {

			$step = [];
			$st   = yexplode(",", $params['steps']);

			foreach ($st as $val) {

				$s = getStep($val);

				if ($s > 0) {
					$step[] = $s;
				}

			}

			if (!empty($step)) {
				$sort .= " and deal.idcategory IN (".yimplode(",", $step).")";
			}

		}

		if ($params['d1'] != '' && $params['d2'] == '') {
			$sort .= " and deal.datum >= '".$params['d1']."'";
		}
		if ($params['d1'] != '' && $params['d2'] != '') {
			$sort .= " and (deal.datum BETWEEN '".$params['d1']."' and '".$params['d2']."')";
		}
		if ($params['d1'] == '' && $params['d2'] != '') {
			$sort .= " and deal.datum <= '".$params['d2']."'";
		}

		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] == '') {
			$sort .= " and deal.datum_izm >= '".$params['dateStart']."'";
		}
		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] != '') {
			$sort .= " and (deal.datum_izm BETWEEN '".$params['dateChangeStart']."' and '".$params['dateChangeEnd']."')";
		}
		if ($params['dateChangeStart'] == '' && $params['dateChangeEnd'] != '') {
			$sort .= " and deal.datum_izm <= '".$params['dateChangeEnd']."'";
		}

		if ($params['user'] != '') {
			$sort .= " and deal.iduser = '".current_userbylogin($params['user'])."'";
		}
		elseif ($isadmin != 'on') {
			$sort .= " and deal.iduser IN (".yimplode(",", get_people($iduser1, "yes")).")";
		}

		//print_r($params['filter']);

		//todo: проверить работу доп.фильтров
		foreach ($params['filter'] as $k => $v) {

			if (!in_array($k, $fields) || empty($v) || $v == '') {
				if ($k != 'phone') {
					continue;
				}
			}

			switch ($k) {

				case 'clid':

					if ((int)$v > 0) {
						$sort .= " and deal.clid = '".(int)$v."'";
					}

					break;
				case 'payer':

					if ((int)$v > 0) {
						$sort .= " and deal.payer = '".(int)$v."'";
					}

					break;
				case 'idcategory':

					if (!is_numeric($v)) {
						$sort .= " and deal.idcategory = '".getStep(untag($v))."'";
					}
					else {
						$sort .= " and deal.idcategory = '".(int)$v."'";
					}

					break;
				case 'direction':

					if (!is_numeric($v)) {
						$sort .= " and deal.direction = '".current_direction(0, untag($v))."'";
					}
					else {
						$sort .= " and deal.direction = '".(int)$v."'";
					}

					break;
				case 'tip':

					if (!is_numeric($v)) {
						$sort .= " and deal.tip = '".current_dogtype(0, untag($v))."'";
					}
					else {
						$sort .= " and deal.tip = '".(int)$v."'";
					}

					break;
				case 'phone':

					//$sort .= " and deal.clid IN (SELECT clid FROM {$sqlname}clientcat WHERE (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".substr(prepareMobPhone($v), 1)."%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".substr(prepareMobPhone($v), 1)."%'))";

					$sort .= " and (replace(replace(replace(replace(replace(cc.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".substr(prepareMobPhone($v), 1)."%' or replace(replace(replace(replace(replace(cc.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".substr(prepareMobPhone($v), 1)."%'))";

					break;
				default:

					$sort .= " and deal.$k LIKE '%".untag($v)."%'";

					break;

			}

		}

		$lpos = $page * $limit;

		if (empty($page) || $page == 0) {
			$page = 1;
		}

		$query = "
			SELECT 
			    deal.*,
			    cc.title as client
			FROM {$sqlname}dogovor `deal`
				LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = deal.clid
			WHERE 
			    deal.did > 0 
			    $sort and 
			    deal.identity = '$identity' 
			ORDER BY deal.$ord $tuda";

		//print $query;

		//$result = $db -> query("SELECT * FROM {$sqlname}dogovor WHERE did > 0 $sort and identity = '$identity' ORDER BY $ord $tuda LIMIT $lpos,$limit");
		$result = $db -> query("$query LIMIT $lpos,$limit");

		$field_types = db_columns_types("{$sqlname}dogovor");

		$list = [];

		while ($da = $db -> fetch($result)) {

			$deal = [];

			foreach ($fields as $field) {

				$field = strtr($field, $synonyms);

				switch ($field) {

					case 'iduser':
					case 'user':

						$deal[$field]      = (int)$da[$field];
						$deal["user"]      = current_userlogin($da[$field]);
						$deal["userTitle"] = current_user($da[$field]);

						break;
					case 'clid':

						$deal[$field]        = (int)$da[$field];
						//$deal['clientTitle'] = current_client($da[$field]);
						$deal['clientTitle'] = $da['client'];

						break;
					case 'payer':

						$deal[$field]       = (int)$da[$field];
						$deal['payerTitle'] = current_client($da[$field]);

						break;
					case 'step':

						$deal[$field]      = (int)current_dogstepname($da['idcategory']);
						$deal["stepID"]    = (int)$da['idcategory'];
						$deal["stepTitle"] = current_dogstepcontent($da['idcategory']);

						break;
					case 'idcategory':

						break;
					case 'direction':

						$deal["directionID"] = (int)$da[$field];
						$deal[$field]        = current_direction((int)$da[$field]);

						break;
					case 'tip':

						$deal["tipID"] = (int)$da[$field];
						$deal[$field]  = current_dogtype((int)$da[$field]);

						break;
					case 'status_close':

						$status       = $db -> getOne("SELECT title FROM {$sqlname}dogstatus WHERE sid = '".$da['sid']."' and identity = '$identity'");
						$deal[$field] = $status;

						break;
					case 'dog_num':

						$deal["contractID"] = (int)$da[$field];

						$c = $db -> getRow("SELECT title, number, datum_start FROM {$sqlname}contract WHERE deid = '".$da[$field]."' and identity = '$identity'");

						$deal["contractTitle"]  = $c['title'];
						$deal["contractNumber"] = $c['number'];
						$deal["contractDate"]   = $c['datum_start'];

						break;
					default:

						//$deal[ $field ] = $da[ $field ];

						if ($field_types[$field] == "int") {

							$deal[$field] = (int)$da[$field];

						}
						elseif (
							in_array($field_types[$field], [
								"float",
								"double"
							])
						) {

							$deal[$field] = (float)$da[$field];

						}
						else {

							$deal[$field] = $da[$field] != "" ? $da[$field] : NULL;

						}

						break;

				}

			}

			if ($params['bankinfo'] == 'yes' || $params['bankinfo']) {

				$bankinfo = get_client_recv($da['payer'], 'yes');

				foreach ((new Client()) ->bankInfoField as $key => $value) {

					$deal['bankinfo'][$value] = $bankinfo[$value];

				}

			}

			if ($params['invoice'] == 'yes' || $params['invoice']) {

				$invoices = [];

				//составим список счетов и их статус
				$res = $db -> query("SELECT * FROM {$sqlname}credit WHERE did = '".$da['did']."' and identity = '$identity' ORDER by crid");
				while ($daa = $db -> fetch($res)) {

					$invoices[] = [
						'id'       => (int)$daa['crid'],
						'invoice'  => $daa['invoice'],
						'date'     => cut_date($daa['datum']),
						'summa'    => (float)$daa['summa_credit'],
						'nds'      => (float)$daa['nds_credit'],
						'do'       => $daa['do'],
						'date_do'  => $daa['invoice_date'],
						'contract' => $daa['invoice_chek'],
						'rs'       => (int)$daa['rs'],
						'tip'      => $daa['tip']
					];

				}

				$deal['invoice'] = $invoices;

			}

			if ($params['uids'] == 'yes' || $params['uids']) {

				$ruids = UIDs ::info(["did" => $da['did']]);
				if ($ruids['result'] == 'Success') {
					$deal['uids'] = $ruids['data'];
				}

			}

			if (isset($params['filter']['phone'])) {

				$deal['client'] = get_client_info($da['clid'], 'yes');
				unset($deal['client']['recv']);

			}

			$list[] = $deal;

		}

		//$count = (int)$db -> getOne("SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE did > 0 $sort and identity = '$identity'");
		$count = (int)$db -> getOne("
			SELECT 
			    COUNT(deal.did)
			FROM {$sqlname}dogovor `deal`
				LEFT JOIN {$sqlname}clientcat `cc` ON cc.clid = deal.clid
			WHERE 
			    deal.did > 0 
			    $sort and 
			    deal.identity = '$identity'");
		$count_pages = ceil($count / $limit);

		return [
			"list"     => $list,
			"page"     => (int)$page,
			"pageall"  => (int)$count_pages,
			"ord"      => $ord,
			"tuda"     => $tuda,
			"count"    => count($list)
		];

	}

}
