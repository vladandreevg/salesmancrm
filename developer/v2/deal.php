<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
use Salesman\Deal;
use Salesman\Invoice;
use Salesman\Speka;
use Salesman\UIDs;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

//print file_get_contents('php://input');

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

set_time_limit(300);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";


function Cleaner($string) {

	$string = trim($string);
	$string = str_replace( [
		'\n\r',
		"'",
		'"'
	], [
		'',
		"&acute;",
		'”'
	], $string );

	return $string;

}

$headers = getallheaders();

//print_r($headers);

/**
 * Принимаем в формате JSON
 */
if($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);

	//print_r($params);

	$APIKEY = array_key_exists( 'apikey', $headers) ? $headers['apikey'] : $headers['Apikey'];
	$LOGIN  = array_key_exists( 'login', $headers) ? $headers['login'] : $headers['Login'];

}

/**
 * Если это GET-запрос или отправка формы
 */
else {

	$params = [];
	foreach ($_REQUEST as $key => $value) {
		$params[ $key ] = ( !is_array( $value ) ) ? Cleaner( $value ) : $value;
	}

	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];

}

if( is_null($APIKEY) && !is_null($params['apikey'])){
	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];
}

//$params['speka']   = $_REQUEST['speka'];
//$params['filter']  = $_REQUEST['filter'];
//$params['invoice'] = $_REQUEST['invoice'];

//print_r($params);

//порядок для реквизитов
$bankInfoField = [
	'castUrName',
	'castInn',
	'castKpp',
	'castBank',
	'castBankKs',
	'castBankRs',
	'castBankBik',
	'castOkpo',
	'castOgrn',
	'castDirName',
	'castDirSignature',
	'castDirStatus',
	'castDirStatusSig',
	'castDirOsnovanie',
	'castUrAddr'
];

//доступные методы
$aceptedActions = [
	"fields",
	"steplist",
	"direction",
	"statusclose",
	"type",
	"funnel",
	"list",
	"info",
	"add",
	"update",
	"change.step",
	"change.close",
	"change.user",
	"delete",
	"invoice.info",
	"invoice.add",
	"invoice.do",
	"invoice.express",
	"invoice.html",
	"invoice.pdf",
	"invoice.mail",
	"invoice.templates"
];

$db = new SafeMysql([
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'charset' => 'utf8',
	'errmode' => 'exception'
]);

//ищем аккаунт по apikey
$result   = $db -> getRow("SELECT id, api_key, timezone, valuta FROM ".$sqlname."settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];
$valuta   = $result['valuta'];

global $identity;

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = (int)$result['iduser'];
$username = $result['title'];
$iduser1  = (int)$result['iduser'];
$isadmin  = $result['isadmin'];

//добавим после определения iduser1 для загрузки настроек

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//print_r($settingsApp);

$Error    = '';
$response = [];

//проверяем api-key
if ($identity == 0) {

	$response['result']        = 'Error';
	$response['error']['code'] = 400;
	$response['error']['text'] = 'Не верный API key';

	$Error = 'yes';

}

//проверяем пользователя
elseif (empty($username)) {

	$response['result']        = 'Error';
	$response['error']['code'] = 401;
	$response['error']['text'] = 'Неизвестный пользователь';

	$Error = 'yes';

}

//проверяем метод
elseif (!in_array($params['action'], $aceptedActions)) {

	$response['error']['code'] = 402;
	$response['error']['text'] = 'Неизвестный метод';

	$Error = 'yes';

}

/**
 * Если есть ошибки, то выходим
 */
if ($Error == 'yes') {
	goto ext;
}

//составляем списки доступных полей для сделок
$ifields[] = 'did';
$ifields[] = 'uid';
$ifields[] = 'datum';
$ifields[] = 'datum_izm';
$ifields[] = 'clid';
$ifields[] = 'title';

$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'");
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


$fields = $ifields;

//фильтр вывода по полям из запроса или все доступные
if ($params['fields'] != '') {

	$fi     = yexplode(",", $params['fields']);
	$fields = [];

	foreach ($fi as $f) {
		if ( in_array( $f, $ifields ) ) {
			$fields[] = $f;
		}
	}

}

//задаем лимиты по-умолчанию
$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
$order  = ($params['order'] != '') ? $params['order'] : 'datum';
$first  = ($params['first'] == 'old') ? '' : 'DESC';

$limit = 200;
$sort  = '';

$synonyms = [
	"date_chage" => "datum_izm",
	"date_start" => "datum_start",
	"date_end" => "datum_end",
	"date_close" => "datum_close",
	"date_create" => "datum"
];

switch ($params['action']) {

	/**
	 * справочники
	 */

	//поля
	case 'fields':

		$response['data']['did']       = "Уникальный идентификатор записи";
		$response['data']['uid']       = "Уникальный идентификатор записи во внешней системе";
		$response['data']['datum']     = "Дата создания. YYYY-MM-DD";
		$response['data']['datum_izm'] = "Дата последнего изменения. YYYY-MM-DD";
		$response['data']['clid']      = "Клиент";

		$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'");
		while ($do = $db -> fetch($resf)) {

			if ($do['fld_name'] == 'idcategory') {
				$response['data']['step'] = $do['fld_title'];
			}
			elseif ($do['fld_name'] == 'marg') {
				$response['data']['marga'] = $do['fld_title'];
			}
			elseif ($do['fld_name'] == 'mcid') {
				$response['data']['mcid'] = "ID своей компании";
			}
			else {
				$response['data'][ $do['fld_name'] ] = $do['fld_title'];
			}
		}

		$response['data']['datum_start']  = "Период действия. Начало";
		$response['data']['datum_end']    = "Период действия. Конец";
		$response['data']['close']        = "Признак закрытой сделки";
		$response['data']['datum_close']  = "Дата закрытия. YYYY-MM-DD";
		$response['data']['status_close'] = "Результат закрытия сделки";
		$response['data']['des_fact']     = "Комментарий закрытия сделки";
		$response['data']['kol_fact']     = "Фактическая сумма продажи";

	break;

	//список этапов
	case 'steplist':

		$stepInHold = customSettings('stepInHold');

		$re = $db -> query("SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {
			$z = [
				"idcategory" => $do['idcategory'],
				"title"      => $do['title'],
				"content"    => $do['content']
			];

			if($stepInHold['step'] == $do['idcategory']){
				$z['inHold'] = true;
				$z['inHoldInput'] = $stepInHold['input'];
			}

			$response['data'][] = $z;

		}

	break;

	//направления
	case 'direction':

		$re = $db -> query("SELECT * FROM ".$sqlname."direction WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"id"        => $do['id'],
				"title"     => $do['title'],
				"isDefault" => $do['isDefault']
			];
		}

	break;

	//типы сделок
	case 'type':

		$re = $db -> query("SELECT * FROM ".$sqlname."dogtips WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"tid"       => $do['tid'],
				"title"     => $do['title'],
				"isDefault" => $do['isDefault']
			];
		}

	break;

	//статусы закрытия
	case 'statusclose':

		$re = $db -> query("SELECT * FROM ".$sqlname."dogstatus WHERE identity = '$identity' ORDER BY title");
		while ($do = $db -> fetch($re)) {
			$response['data'][] = [
				"sid"   => $do['sid'],
				"title" => $do['title']
			];
		}

	break;

	//воронки
	case 'funnel':

		$did       = $params['did'];
		$direction = (is_numeric($params['direction'])) ? $params['direction'] : current_direction(0, untag($params['direction']));
		$tip       = (is_numeric($params['tip'])) ? $params['tip'] : current_dogtype(0, untag($params['tip']));

		$funnel = getMultiStepList([
			"did"       => $did,
			"direction" => $direction,
			"tip"       => $tip
		]);

		$nsteps = $db -> getIndCol("idcategory", "SELECT title, idcategory FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER by title");

		$steps = [];
		foreach ($funnel['steps'] as $id => $time) {
			$steps[ $id ] = strtr( $id, $nsteps );
		}

		$defaultName = strtr($funnel['default'], $nsteps);

		$funnel = arrayAddAfter($funnel, 0, ["stepsName" => $steps]);
		$funnel = arrayAddAfter($funnel, 3, ["defaultName" => $defaultName]);

		$response['data'] = $funnel;

	break;

	/**
	 * Вывод данных по сделкам
	 */

	//список сделок
	case 'list':

		$sort = "";

		if($order == 'date_create') {
			$order = 'datum';
		}
		elseif($order == 'date_change') {
			$order = 'datum_izm';
		}

		if ($params['user'] != '') {
			$iduser = current_userbylogin( $params[ 'user' ] );
		}

		//$sort .= get_people($iduser);

		if ($params['active'] == 'no') {
			$sort .= " and close = 'yes'";
		}
		elseif ($params['active'] == 'yes') {
			$sort .= " and close != 'yes'";
		}

		if ($params['word'] != '') {
			$sort .= " and (title LIKE '%".Cleaner( $params[ 'word' ] )."%' or content LIKE '%".Cleaner( $params[ 'word' ] )."%' or adres LIKE '%".Cleaner( $params[ 'word' ] )."%')";
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
				$sort .= " and idcategory IN (".yimplode( ",", $step ).")";
			}

		}

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and datum >= '".$params[ 'dateStart' ]."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and (datum BETWEEN '".$params[ 'dateStart' ]."' and '".$params[ 'dateEnd' ]."')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and datum <= '".$params[ 'dateEnd' ]."'";
		}

		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] == '') {
			$sort .= " and datum_izm >= '".$params[ 'dateStart' ]."'";
		}
		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] != '') {
			$sort .= " and (datum_izm BETWEEN '".$params[ 'dateChangeStart' ]."' and '".$params[ 'dateChangeEnd' ]."')";
		}
		if ($params['dateChangeStart'] == '' && $params['dateChangeEnd'] != '') {
			$sort .= " and datum_izm <= '".$params[ 'dateChangeEnd' ]."'";
		}

		if ($params['user'] != '') {
			$sort .= " and ".$sqlname."dogovor.iduser = '".current_userbylogin( $params[ 'user' ] )."'";
		}
		elseif($isadmin != 'on') {
			$sort .= " and ".$sqlname."dogovor.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		//print_r($params['filter']);

		//todo: проверить работу доп.фильтров
		foreach ($params['filter'] as $k => $v) {

			if (!in_array($k, $ifields) || empty($v) || $v == '') {
				if($k != 'phone'){
					continue;
				}
			}

			switch ($k) {

				case 'clid':

					if ( (int)$v > 0) {
						$sort .= " and clid = '".(int)$v."'";
					}

				break;
				case 'payer':

					if ( (int)$v > 0) {
						$sort .= " and payer = '".(int)$v."'";
					}

				break;
				case 'idcategory':

					if (!is_numeric($v)) {
						$sort .= " and idcategory = '".getStep( untag( $v ) )."'";
					}
					else {
						$sort .= " and idcategory = '".(int)$v."'";
					}

				break;
				case 'direction':

					if (!is_numeric($v)) {
						$sort .= " and direction = '".current_direction( 0, untag( $v ) )."'";
					}
					else {
						$sort .= " and direction = '".(int)$v."'";
					}

				break;
				case 'tip':

					if (!is_numeric($v)) {
						$sort .= " and tip = '".current_dogtype( 0, untag( $v ) )."'";
					}
					else {
						$sort .= " and tip = '".(int)$v."'";
					}

				break;
				case 'phone':

					$sort .= " and clid IN (SELECT clid FROM ".$sqlname."clientcat WHERE (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%". substr(prepareMobPhone( $v ), 1)."%' or replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".substr( prepareMobPhone( $v ), 1 )."%'))";

				break;
				default:

					$sort .= " and ".$k." LIKE '%".untag($v)."%'";

				break;

			}

		}

		$lpos = $offset * $limit;

		$result = $db -> query("SELECT * FROM ".$sqlname."dogovor WHERE did > 0 $sort and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");

		$field_types = db_columns_types( "{$sqlname}dogovor" );

		while ($da = $db -> fetch($result)) {

			$deal = [];

			foreach ($fields as $field) {

				$field = strtr($field, $synonyms);

				switch ($field) {

					case 'iduser':
					case 'user':

						$deal[ $field ]    = (int)$da[ $field ];
						$deal["user"]      = current_userlogin($da[ $field ]);
						$deal["userTitle"] = current_user($da[ $field ]);

					break;
					case 'clid':

						$deal[ $field ]      = (int)$da[ $field ];
						$deal['clientTitle'] = current_client($da[ $field ]);

					break;
					case 'payer':

						$deal[ $field ]     = (int)$da[ $field ];
						$deal['payerTitle'] = current_client($da[ $field ]);

					break;
					case 'step':

						$deal[ $field ]    = (int)current_dogstepname($da['idcategory']);
						$deal["stepID"]    = (int)$da['idcategory'];
						$deal["stepTitle"] = current_dogstepcontent($da['idcategory']);

					break;
					case 'idcategory':

					break;
					case 'direction':

						$deal["directionID"] = (int)$da[ $field ];
						$deal[ $field ]      = current_direction((int)$da[ $field ]);

					break;
					case 'tip':

						$deal["tipID"]  = (int)$da[ $field ];
						$deal[ $field ] = current_dogtype((int)$da[ $field ]);

					break;
					case 'status_close':

						$status         = $db -> getOne("SELECT title FROM ".$sqlname."dogstatus WHERE sid = '".$da['sid']."' and identity = '$identity'");
						$deal[ $field ] = $status;

					break;
					case 'dog_num':

						$deal["contractID"] = (int)$da[ $field ];

						$c = $db -> getRow("SELECT title, number, datum_start FROM ".$sqlname."contract WHERE deid = '".$da[ $field ]."' and identity = '$identity'");

						$deal["contractTitle"]  = $c['title'];
						$deal["contractNumber"] = $c['number'];
						$deal["contractDate"]   = $c['datum_start'];

					break;
					default:

						//$deal[ $field ] = $da[ $field ];

						if($field_types[ $field ] == "int"){

							$deal[ $field ] = (int)$da[ $field ];

						}
						elseif(in_array($field_types[ $field ], ["float","double"])){

							$deal[ $field ] = (float)$da[ $field ];

						}
						else {

							$deal[ $field ] = $da[ $field ] != "" ? $da[ $field ] : NULL;

						}

					break;

				}

			}

			if ($params['bankinfo'] == 'yes') {

				$bankinfo = get_client_recv($da['payer'], 'yes');

				foreach ($bankInfoField as $key => $value) {

					$deal['bankinfo'][ $value ] = $bankinfo[ $value ];

				}

			}

			if ($params['invoice'] == 'yes') {

				//составим список счетов и их статус
				$res = $db -> query("SELECT * FROM ".$sqlname."credit WHERE did = '".$da['did']."' and identity = '$identity' ORDER by crid");
				while ($daa = $db -> fetch($res)) {

					$deal['invoice'][] = [
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

			}

			if ($params['uids'] == 'yes') {

				$ruids = UIDs ::info(["did" => $da['did']]);
				if ($ruids['result'] == 'Success') {
					$deal[ 'uids' ] = $ruids[ 'data' ];
				}

			}

			if(isset($params['filter']['phone'])){

				$deal[ 'client' ] = get_client_info($da['clid'],'yes');
				unset($deal[ 'client' ]['recv']);

			}

			$response['data'][] = $deal;

		}

		$response['count'] = (int)$db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."dogovor WHERE did > 0 ".$sort." and identity = '$identity'");
		//print $db->lastQuery();

	break;

	//информация по сделке
	case 'info':

		if ($params['uid'] != '') {
			$s = "uid = '".$params['uid']."'";
		}
		elseif ($params['did'] != '') {
			$s = "did = '".$params['did']."'";
		}

		$did = (int)$db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

		if ($did == 0 ) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка не найдена в пределах аккаунта указанного пользователя.";

		}
		elseif ($did > 0) {

			$dinfo = get_dog_info($did, 'yes');

			if (count($dinfo) > 0) {

				$deal = [];

				foreach ($fields as $field) {

					switch ($field) {

						case 'iduser':

							$deal[ $field ]  = (int)$dinfo[ $field ];
							$deal['user']    = current_userlogin($dinfo[ $field ]);
							$deal['userUID'] = current_userUID($dinfo[ $field ]);

						break;
						/*case 'clid':

							$deal[ $field ] = $dinfo[ $field ];
							$deal['clientTitle']    = current_client($dinfo[ $field ]);

						break;
						case 'payer':

							$deal[ $field ] = $dinfo[ $field ];
							$deal['payerTitle']     = current_client($dinfo[ $field ]);

						break;*/
						case 'step':

							$deal[ $field ]    = (int)current_dogstepname($dinfo['idcategory']);
							$deal["stepID"]    = (int)$dinfo['idcategory'];
							$deal["stepTitle"] = current_dogstepcontent($dinfo['idcategory']);

						break;
						case 'idcategory':
						break;
						case 'direction':

							$deal["directionID"] = (int)$dinfo[ $field ];
							$deal[ $field ]      = current_direction((int)$dinfo[ $field ]);

						break;
						case 'tip':

							$deal["tipID"]  = (int)$dinfo[ $field ];
							$deal[ $field ] = current_dogtype((int)$dinfo[ $field ]);

						break;
						case 'status_close':

							$status         = $db -> getOne("SELECT title FROM ".$sqlname."dogstatus WHERE sid = '".$dinfo['sid']."' and identity = '$identity'");
							$deal[ $field ] = $status;

						break;
						case 'dog_num':

							$deal["contractID"] = (int)$dinfo[ $field ];

							$c = $db -> getRow("SELECT title, number, datum_start FROM ".$sqlname."contract WHERE deid = '".$dinfo[ $field ]."' and identity = '$identity'");

							$deal["contractTitle"]  = $c['title'];
							$deal["contractNumber"] = $c['number'];
							$deal["contractDate"]   = $c['datum_start'];

						break;
						default:

							//$deal[ $field ] = $dinfo[ $field ];

							if($field_types[ $field ] == "int"){

								$deal[ $field ] = (int)$dinfo[ $field ];

							}
							elseif(in_array($field_types[ $field ], ["float","double"])){

								$deal[ $field ] = (float)$dinfo[ $field ];

							}
							else {

								$deal[ $field ] = $dinfo[ $field ] == "" ? $dinfo[ $field ] : NULL;

							}

						break;

					}

				}

				if ($params['client'] == 'yes') {

					$deal['client'] = get_client_info($dinfo['clid'], "yes");

					$deal['client']['recv'] = json_decode($deal['client']['recv'], true);

					unset($deal['client']['dostup']);

				}
				else {
					unset( $deal['client'] );
				}

				if ($params['payer'] == 'yes') {

					$deal['payer'] = get_client_info($dinfo['payer'], "yes");

					$deal['payer']['recv'] = json_decode($deal['payer']['recv'], true);

					unset($deal['payer']['dostup']);

				}
				else {
					unset( $deal['payer'] );
				}

				$pid_list = $dinfo["pid_list"];

				foreach ($pid_list as $k => $pids) {

					$deal['person'][] = personinfo($pids);

				}

				if ($response['client']['pid'] > 0 && count($pid_list) == 0) {
					$response['person'][] = personinfo( $response['client']['pid'] );
				}

				if ($params['bankinfo'] == 'yes') {

					$bankinfo = get_client_recv($dinfo['payer'], 'yes');

					foreach ($bankInfoField as $key => $value) {
						$deal['bankinfo'][ $value ] = $bankinfo[ $value ];
					}

				}

				if ($params['speka'] == 'yes') {

					$ress = $db -> query("SELECT * FROM ".$sqlname."speca WHERE did = '$did' and identity = '$identity' ORDER BY spid");
					while ($da = $db -> fetch($ress)) {

						$deal['speka'][] = [
							"spid"     => (int)$da['spid'],
							"prid"     => (int)$da['prid'],
							"artikul"  => $da['artikul'],
							"title"    => $da['title'],
							"tip"      => $da['tip'],
							"kol"      => (float)$da['kol'],
							"dop"      => (float)$da['dop'],
							"price"    => (float)$da['price'],
							"price_in" => (float)$da['price_in'],
							"nds"      => (float)$da['nds'],
							"comments" => $da['comments']
						];
					}

				}

				//составим список счетов и их статус
				if ($params['invoice'] == 'yes') {

					$res = $db -> query("SELECT * FROM ".$sqlname."credit WHERE did = '$did' and identity = '$identity' ORDER by crid");
					while ($daa = $db -> fetch($res)) {

						$deal['invoice'][] = [
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

				}

				if ($params['uids'] == 'yes') {

					$ruids = UIDs ::info(["did" => $did]);
					if ($ruids['result'] == 'Success') {
						$client['uids'] = $ruids['data'];
					}

				}

				$response['data'] = $deal;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		elseif ($did == 0 && $params['uid'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры сделки";

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = "Не найдено";

		}

	break;

	//добавление сделки
	case 'add':

		if ($params['user'] == '') {
			$params['iduser'] = $iduser;
		}
		else {
			$params['iduser'] = current_userbylogin( $params['user'] );
		}

		$clid  = (int)$db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE clid = '".(int)$params[ 'clid' ]."' and identity = '$identity'");
		$payer = (int)$db -> getOne("SELECT clid FROM ".$sqlname."clientcat WHERE clid = '".(int)$params[ 'payer' ]."' and identity = '$identity'");

		if ($params['clid'] == '' && $params['payer'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - clid и payer клиента";

		}
		if ($clid > 0 || $payer > 0) {

			//проверка, что есть название клиента
			if ($params['title'] != '') {

				if (isset($params['speka'])) {
					$params['calculate'] = "yes";
				}

				if (!isset($params['clid']) && (int)$params['payer'] > 0) {
					$params['clid'] = $params['payer'];
				}
				elseif (!isset($params['payer']) && (int)$params['clid'] > 0) {
					$params['payer'] = $params['clid'];
				}

				if (isset($params['pid_list'])) {
					$params['pid_list'] = str_replace( ",", ";", $params['pid_list'] );
				}
				$params['autor'] = $iduser1;

				$Deal     = new Deal();
				$response = $Deal -> add($params);

				//$response['params'] = $params;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 406;
				$response['error']['text'] = "Отсутствуют параметры - Название сделки";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = 407;
			$response['error']['text'] = "Клиент или Плательщик не найден.";

		}

	break;

	//обновление сделки
	case 'update':

		$uid = untag($params["uid"]);

		unset( $params['step'], $params['idcategory'] );

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ((int)$params['did'] > 0) {
			$s = "AND did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "AND uid = '$uid'";
		}

		$did = (int)$db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE did > 0 $s ".get_people($iduser)." and identity = '$identity'");

		if ($params['did'] == '' && $params['uid'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - did и uid сделки";

		}

		if ($did == 0) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		else {

			if (isset($params['speka'])) {
				$params['calculate'] = "yes";
			}

			if (isset($params['pid_list'])) {
				$params['pid_list'] = str_replace( ",", ";", $params['pid_list'] );
			}

			if (!isset($params['clid']) && (int)$params['payer'] > 0) {
				$params['clid'] = (int)$params['payer'];
			}
			elseif (!isset($params['payer']) && (int)$params['clid'] > 0) {
				$params['payer'] = (int)$params['clid'];
			}

			$params['fromapi'] = true;

			$Deal     = new Deal();
			$response = $Deal -> fullupdate((int)$params['did'], $params);

		}

	break;

	//смена этапа
	case 'change.step':

		$uid = untag($params["uid"]);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ($params['did'] > 0) {
			$s = "did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		//проверка принадлежности clid к данному аккаунту
		$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

		if ($did < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		elseif ( $params['step'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - Новый этап";

		}
		else {

			$params['step'] = getStep($params['step']);

			$Deal     = new Deal();
			$response = $Deal -> changestep($params['did'], $params);

		}

		if ($params['did'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

	break;

	//закрытие сделки
	case 'change.close':

		$Deal     = new Deal();
		$response = $Deal -> changeClose($params['did'], $params);

	break;

	//заморозка
	case 'change.freeze':

		$Deal     = new Deal();
		$response = $Deal -> changeFreeze($params['did'], $params['date']);

	break;

	//смена этапа
	case 'change.user':

		$uid = untag($params["uid"]);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ($params['did'] > 0) {
			$s = "did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		//проверка принадлежности clid к данному аккаунту
		$did = (int)$db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");

		if ($did < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		else {

			if ($params['user'] == '') {
				$params['newuser'] = $iduser;
			}
			else {
				$params['newuser'] = current_userbylogin( $params['user'] );
			}

			if ($params['client.send'] == "yes") {
				$params['client_send'] = "yes";
			}
			if ($params['person.send'] == "yes") {
				$params['person_send'] = "yes";
			}

			$Deal     = new Deal();
			$response = $Deal -> changeuser((int)$params['did'], $params);

		}

		if ($params['did'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

	break;

	//удаление сделки
	case 'delete':

		$uid = untag($params["uid"]);

		//проверка принадлежности did к данному аккаунту и вообще её существование
		if ($params['did'] > 0) {
			$s = "did = '$params[did]'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		//проверка принадлежности clid к данному аккаунту
		$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE $s ".get_people($iduser)." and identity = '$identity'");
		//$did = $db -> getOne("SELECT did FROM ".$sqlname."dogovor WHERE did = '".$params['did']."' ".get_people($iduser)." and identity = '$identity'");

		if ($did < 1) {

			$response['result']        = 'Error';
			$response['error']['code'] = 403;
			$response['error']['text'] = "Сделка с указанным did не найдена в пределах аккаунта указанного пользователя.";

		}
		else {

			$Deal     = new Deal();
			$response = $Deal -> delete($params['did']);

		}

		if ($params['did'] == '') {

			$response['result']        = 'Error';
			$response['error']['code'] = 406;
			$response['error']['text'] = "Отсутствуют параметры - did сделки";

		}

	break;

	/**
	 * Работа со счетами
	 */

	//добавить счет
	case 'invoice.info':

		$id = $params["id"];

		$invoice = Invoice::info($id);

		if (!isset($params['id'])) {

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры - id Счета";

		}
		elseif (!empty($invoice)) {

			$response['data'] = $invoice;

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет не найден';

		}

	break;

	//добавить счет
	case 'invoice.add':

		$did = $params["did"];
		$uid = untag($params["uid"]);

		//Находим clid, pid
		if ($did > 0) {
			$s = "did = '$did'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		$resu   = $db -> getRow("SELECT did, pid, iduser, kol FROM ".$sqlname."dogovor WHERE $s and identity = '$identity'");
		$did    = $resu['did'];
		$pid    = $resu["pid"];
		$iduser = $resu["iduser"];
		$kol    = $resu["kol"];

		if ($did > 0) {

			//require_once "../../inc/class/Invoice.php";
			//require_once "../../inc/class/Speka.php";

			$template = Invoice ::getTemplates();

			$arg['iduser'] = (!isset($params['user']) && $params['user'] != '') ? current_userbylogin($params['user']) : $iduser;

			$sumdo = $db -> getOne("SELECT SUM(summa_credit) FROM ".$sqlname."credit WHERE did = '$did' and identity = '$identity'");

			if ($sumdo < $kol) {

				/*
				 * НДС по спецификации
				 */
				$spekaData = Speka ::getNalog($did);

				/*
				 * процент налога от суммы спецификации
				 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
				 */
				$nalogPercent = $spekaData['nalog'] / $spekaData['summa'];

				/*
				 * налог, который должен быть с учетом уже выставленных счетов
				 */
				$nalogNotDo = ($spekaData['summa'] - $sumdo) * $nalogPercent;

				$arg['datum']        = $params["date"];
				$arg['datum_credit'] = $params["date.plan"];
				$arg['igen']         = (isset($params["invoice"]) && $params["invoice"] == "auto") ? "yes" : "no";
				$arg['invoice']      = ($params["invoice"] == "auto") ? "" : $params['invoice'];
				$arg['contract']     = $params['contract'];
				$arg['rs']           = (isset($params["rs"]) && $params["rs"] > 0) ? $params["rs"] : "";
				$arg['signer']       = (isset($params["signer"]) && $params["signer"] > 0) ? $params["signer"] : "";
				$arg['tip']          = (isset($params["tip"]) && $params["tip"] != '') ? $params["tip"] : "Счет-договор";
				$arg['summa']        = ($params["summa"] == '') ? getDogData($did, 'kol') : pre_format($params["summa"]);
				$arg['nds']          = (isset($params["nds"]) && $params["nds"] != '') ? pre_format($params["nds"]) : $nalogNotDo;

				$arg['do']      = (isset($params["do"]) && $params["do"] != '') ? $params["do"] : "";
				$arg['date.do'] = (isset($params["date.do"]) && $params["date.do"] != '') ? $params["date.do"] : "";

				if(isset($params['template']) && $params['template'] != '') {
					$arg['template'] = $params['template'];
				}

				elseif(isset($params['templateID']) && $params['templateID'] > 0) {
					$arg['template'] = $template[ $params['templateID'] ]['file'];
				}

				if($arg['template'] == '') {
					$arg['template'] = "invoice.tpl";
				}

				$invoice = new Invoice();

				//print_r($arg);

				$rez = $invoice -> add($did, $arg);

				$response['result']          = $rez['result'].";".$rez['text'];
				$response['data']['id']      = $rez['data'];
				$response['data']['invoice'] = $rez['invoice'];

			}
			else {

				$response['error']['code'] = 406;
				$response['error']['text'] = 'Уже выставлены все возможные счета';

			}

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Сделка с указанным did не найдена в пределах аккаунта указанного пользователя';

		}

	break;

	//отметка счета оплаченным
	case 'invoice.do':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = (int)$params['id'];

		//Находим clid, pid
		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '') {
			$s = "invoice = '$invoice'";
		}

		//проверяем расчетный счет
		$crid = $db -> getOne("SELECT crid FROM ".$sqlname."credit WHERE $s AND identity = '$identity'") + 0;

		if ($crid > 0) {

			//require_once "../../inc/class/Invoice.php";

			$arg['invoice_date'] = ($params["date.do"] != '') ? $params["date.do"] : current_datum();
			$arg['summa']        = $params["summa"];

			$invoice = new Invoice();
			$rez     = $invoice -> doit($crid, $arg);

			$response['result'] = $rez['result'];
			$response['data']   = $rez['text'];
			if ($rez['newdata'] > 0) {
				$response['newdata'] = $rez['newdata'];
			}

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет по ID или Номеру не найден';

		}

	break;

	//экспресс-добавление оплаты
	case 'invoice.express':

		$did = (int)$params["did"];
		$uid = untag($params["uid"]);

		//Находим clid, pid
		if ($did > 0) {
			$s = "did = '$did'";
		}
		elseif ($uid != '') {
			$s = "uid = '$uid'";
		}

		$resu   = $db -> getRow("SELECT did, pid, iduser, kol FROM ".$sqlname."dogovor WHERE $s and identity = '$identity'");
		$did    = (int)$resu['did'];
		$pid    = (int)$resu["pid"];
		$iduser = (int)$resu["iduser"];
		$kol    = $resu["kol"];

		if ($did > 0) {

			$arg['iduser'] = (!isset($params['user']) && $params['user'] != '') ? current_userbylogin($params['user']) : $iduser;

			$sumdo = $db -> getOne("SELECT SUM(summa_credit) FROM ".$sqlname."credit WHERE did = '$did' and identity = '$identity'");

			if ($sumdo < $kol) {

				/*
				 * НДС по спецификации
				 */
				$spekaData = Speka ::getNalog($did);

				/*
				 * процент налога от суммы спецификации
				 * он может отличаться в смешанных счетах (есть позиции с ндс и без)
				 */
				$nalogPercent = $spekaData['nalog'] / $spekaData['summa'];

				/*
				 * налог, который должен быть с учетом уже выставленных счетов
				 */
				$nalogNotDo = ($spekaData['summa'] - $sumdo) * $nalogPercent;

				$arg['datum']        = $params["date"];
				$arg['datum_credit'] = $params["date.plan"];
				$arg['igen']         = (isset($params["invoice"]) && $params["invoice"] == "auto") ? "yes" : "no";
				$arg['invoice']      = ($params["invoice"] == "auto") ? "" : $params['invoice'];
				$arg['contract']     = $params['contract'];
				$arg['rs']           = (isset($params["rs"]) && $params["rs"] > 0) ? $params["rs"] : "";
				$arg['signer']       = (isset($params["signer"]) && $params["signer"] > 0) ? $params["signer"] : "";
				$arg['tip']          = (isset($params["tip"]) && $params["tip"] != '') ? $params["tip"] : "Счет-договор";
				$arg['summa']        = ($params["summa"] == '') ? getDogData($did, 'kol') : pre_format($params["summa"]);
				$arg['nds']          = (isset($params["nds"]) && $params["nds"] != '') ? pre_format($params["nds"]) : $nalogNotDo;

				$arg['do']      = "on";
				$arg['date.do'] = (isset($params["date.do"]) && $params["date.do"] != '') ? $params["date.do"] : current_datum();

				if(isset($params['template']) && $params['template'] != '') {
					$arg['template'] = $params['template'];
				}

				elseif(isset($params['templateID']) && $params['templateID'] > 0) {
					$arg['template'] = $template[ $params['templateID'] ]['file'];
				}

				if($arg['template'] == '') {
					$arg['template'] = "invoice.tpl";
				}

				$invoice = new Invoice();

				//print_r($arg);

				$rez = $invoice -> express($did, $arg);

				$response['result']          = $rez['result'].";".$rez['text'];
				$response['data']['id']      = $rez['data'];
				$response['data']['invoice'] = $rez['invoice'];

			}
			else {

				$response['error']['code'] = 406;
				$response['error']['text'] = 'Уже выставлены все возможные счета';

			}

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Сделка с указанным did не найдена в пределах аккаунта указанного пользователя';

		}

	break;

	//получение счета в виде HTML
	case 'invoice.html':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = $params['id'];

		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '') {
			$s = "invoice = '$invoice'";
		}

		//проверяем расчетный счет
		$crid = $db -> getOne("SELECT crid FROM ".$sqlname."credit WHERE $s AND identity = '$identity'") + 0;

		if ($crid > 0) {

			//require_once "../../inc/class/Invoice.php";

			$inv = Invoice ::info($crid);

			$params['tip'] = "print";
			$params['api'] = "yes";

			$invoice = new Invoice();
			$rez     = $invoice -> getInvoice($crid, $params);

			$response['html']    = htmlspecialchars($rez);
			$response['id']      = $inv['crid'];
			$response['invoice'] = $inv['invoice'];

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет по ID или Номеру не найден';

		}

	break;

	//получение счета в виде HTML
	case 'invoice.pdf':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = $params['id'];

		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '') {
			$s = "invoice = '$invoice'";
		}

		//проверяем расчетный счет
		$crid    = $db -> getOne("SELECT crid FROM ".$sqlname."credit WHERE $s AND identity = '$identity'") + 0;
		$invoice = $db -> getOne("SELECT invoice FROM ".$sqlname."credit WHERE $s AND identity = '$identity'") + 0;

		$u = "../../files/".$fpath."invoice_".$crid.".pdf";

		if ($crid > 0) {

			if (!file_exists($u)) {

				//require_once "../../inc/class/Invoice.php";

				$params['tip']      = "pdf";
				$params['api']      = "yes";
				$params['download'] = "no";

				$inv = new Invoice();
				$rez = $inv -> getInvoice($crid, $params);

				if ($rez != 'Error') {

					$response['url']     = $productInfo['crmurl']."/files/".$fpath.$rez;
					$response['id']      = $crid;
					$response['invoice'] = $invoice;

				}

			}
			else {

				$response['url']     = $productInfo['crmurl']."/files/".$fpath."invoice_".$crid.".pdf";
				$response['id']      = $crid;
				$response['invoice'] = $invoice;

			}

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет по ID или Номеру не найден';

		}

	break;

	//получение счета в виде HTML
	case 'invoice.mail':

		$mes = [];

		$invoice = $params['invoice'];
		$crid    = $params['id'];

		if ($crid > 0) {
			$s = "crid = '$crid'";
		}
		elseif ($invoice != '') {
			$s = "invoice = '$invoice'";
		}

		//проверяем расчетный счет
		$crid = $db -> getOne("SELECT crid FROM ".$sqlname."credit WHERE $s AND identity = '$identity'") + 0;

		if ($crid > 0) {

			//require_once "../../inc/class/Invoice.php";

			$invoice = new Invoice();
			$rez     = $invoice -> mail($crid, $params, true);

			if ($rez['result'] != 'Error') {

				$response['result']          = $rez['result'];
				$response['data']['id']      = $rez['data'];
				$response['data']['invoice'] = $rez['invoice'];

			}
			else {

				$response = $rez;

			}

		}
		else {

			$response['error']['code'] = 403;
			$response['error']['text'] = 'Счет по ID или Номеру не найден';

		}

	break;

	//получение списка шаблонов
	case 'invoice.templates':

		$mes = [];

		//require_once "../../inc/class/Invoice.php";

		$templates = Invoice ::getTemplates();

		if (!empty($templates)) {

			$response['result'] = 'Success';
			$response['data']   = array_values($templates);

		}
		else {

			$response['result'] = 'Error';
			$response['error']['code'] = 404;
			$response['error']['text'] = 'Шаблоны не найдены';

		}

	break;

	default:

		$response['error']['code'] = 404;
		$response['error']['text'] = 'Не понимаю чЁ происходит. Может в следующий раз?';

	break;

}

ext:

//$response['globals'] = $GLOBALS;

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;
//HTTPStatus($code);

print $rez = json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";