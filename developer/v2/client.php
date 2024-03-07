<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
use Salesman\Client;
use Salesman\UIDs;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

error_reporting( E_ERROR );
ini_set( 'display_errors', 1 );

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/licloader.php";
require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";


function Cleaner($string) {

	$string = trim($string);
	$string = str_replace( [
		'"',
		'\n\r',
		"'"
	], [
		'”',
		'',
		"&acute;"
	], $string );

	return $string;

}


$headers = getallheaders();

/**
 * Принимаем в формате JSON
 */
if($headers["Content-Type"] == "application/json" || $headers["content-type"] == "application/json") {

	$params = json_decode(file_get_contents('php://input'), true);

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


//для приема массива клиентов для добавления
$clients          = $params['client'];
//$params['filter'] = $_REQUEST['filter'];

//print_r($params);

//доступные методы
$aceptedActions = [
	"fields",
	"list",
	"info",
	"add",
	"add.list",
	"update",
	"delete"
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
$result   = $db -> getRow("SELECT id, api_key, timezone FROM ".$sqlname."settings WHERE api_key = '$APIKEY'");
$identity = (int)$result['id'];
$api_key  = $result['api_key'];
$timezone = $result['timezone'];

global $identity;

//найдем пользователя
$result   = $db -> getRow("SELECT title, iduser FROM ".$sqlname."user WHERE login = '$LOGIN' and identity = '$identity'");
$iduser   = $iduser1 = (int)$result['iduser'];
$username = $result['title'];
$isadmin  = $result['isadmin'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

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

$Error  = '';
$fields = $response = [];

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
elseif (!in_array( $params['action'], $aceptedActions, true ) ) {

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

/**
 * Основные обработчики
 */

//поля клиента
$isfields = $db -> getCol("SELECT fld_name FROM ".$sqlname."field WHERE fld_tip='client' and fld_on='yes' and fld_name != 'recv' and identity = '$identity'");

array_unshift($isfields, 'clid', 'uid', 'type', 'date_create', 'date_edit');

//составляем списки доступных полей для клиентов
$ifields[] = 'clid';
$ifields[] = 'uid';
$ifields[] = 'type';
$ifields[] = 'date_create';
$ifields[] = 'date_edit';
$ifields[] = 'created';
$ifields[] = 'edited';

$fields = $isfields;

//фильтр вывода по полям из запроса или все доступные
if ($params['fields'] != '') {

	$fi     = yexplode(",", $params['fields']);
	$fields = [];

	foreach ($fi as $f) {
		if ( in_array( $f, $isfields ) ) {
			$fields[] = $f;
		}
	}

}

$synonyms = [
	"date_chage" => "date_edit"
];

switch ($params['action']) {

	//Вывод списка доступных полей
	case 'fields':

		$response['data']['clid']        = "Уникальный идентификатор записи клиента в CRM";
		$response['data']['uid']         = "Уникальный идентификатор записи клиента в вашей ИС";
		$response['data']['type']        = "Тип записи (допустимые - client,person,concurent,contractor,parnter)";
		$response['data']['date_create'] = "Дата создания. Timestamp";
		$response['data']['date_edit']   = "Дата последнего изменения. Timestamp";

		$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='client' and fld_on='yes' and identity = '$identity'");
		while ($do = $db -> fetch($resf)) {

			$response['data'][ $do['fld_name'] ] = $do['fld_title'];

		}

	break;

	//Вывод списка клиентов
	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'date_create';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		if($order == 'date_change') {
			$order = 'date_edit';
		}

		$limit = 200;
		$sort  = '';

		//$sort .= get_people($iduser);

		if ($params['word'] != '') {

			$phone = preparePhone($params['word']);

			$sort .= " and (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".Cleaner($params['word'])."%' or title LIKE '%".Cleaner($params['word'])."%' or des LIKE '%".Cleaner($params['word'])."%' or mail_url LIKE '%".Cleaner($params['word'])."%' or site_url LIKE '%".Cleaner($params['word'])."%' or address LIKE '%".Cleaner($params['word'])."%')";

		}

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and date_create > '".$params[ 'dateStart' ]."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and (date_create BETWEEN '".$params[ 'dateStart' ]."' and '".$params[ 'dateEnd' ]."')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and date_create < '".$params[ 'dateEnd' ]."'";
		}

		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] == '') {
			$sort .= " and date_edit > '".$params[ 'dateStart' ]."'";
		}
		if ($params['dateChangeStart'] != '' && $params['dateChangeEnd'] != '') {
			$sort .= " and (date_edit BETWEEN '".$params[ 'dateChangeStart' ]."' and '".$params[ 'dateChangeEnd' ]."')";
		}
		if ($params['dateChangeStart'] == '' && $params['dateChangeEnd'] != '') {
			$sort .= " and date_edit < '".$params[ 'dateChangeEnd' ]."'";
		}

		if ($params['user'] != '') {
			$sort .= " and ".$sqlname."clientcat.iduser = '".current_userbylogin( $params[ 'user' ] )."'";
		}
		elseif($isadmin != 'on') {
			$sort .= " and ".$sqlname."clientcat.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		$filterAllow = [
			"relations",
			"idcategory",
			"territory",
			"type",
			"clientpath",
			"trash"
		];

		$r = $db -> getCol("SELECT fld_name FROM ".$sqlname."field WHERE fld_tip = 'client' and fld_name LIKE '%input%' and fld_on = 'yes' and identity = '$identity'");

		$filterAllow = array_merge($filterAllow, $r);

		//todo: проверить работу доп.фильтров
		foreach ($params['filter'] as $k => $v) {

			if ($v != '') {

				switch ( $k ) {
					case 'relations':

						$sort .= " and tip_cmr = '".untag( $v )."'";

					break;
					case 'idcategory':

						if ( !is_numeric( $v ) ) {
							$sort .= " and idcategory = '".current_category( 0, untag( $v ) )."'";
						}
						else {
							$sort .= " and idcategory = '".(int)$v."'";
						}

					break;
					case 'territory':

						if ( !is_numeric( $v ) ) {
							$sort .= " and territory = '".current_territory( '', untag( $v ) )."'";
						}
						else {
							$sort .= " and territory = '".(int)$v."'";
						}

					break;
					case 'type':

						$sort .= " and type = '".untag( $v )."'";

					break;
					case 'clientpath':

						if ( !is_numeric( $v ) ) {
							$sort .= " and clientpath = '".getClientpath( untag( $v ) )."'";
						}
						else {
							$sort .= " and clientpath = '".(int)$v."'";
						}

					break;
					default:

						if ( in_array( $k, $filterAllow ) ) {
							$sort .= " and ".$k." LIKE '%".untag( $v )."%'";
						}

					break;
				}

			}

		}

		$lpos = $offset * $limit;

		$field_types = db_columns_types( "{$sqlname}clientcat" );

		$result = $db -> query("SELECT * FROM ".$sqlname."clientcat WHERE clid > 0 $sort and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		//print $db -> lastQuery();
		while ($da = $db -> fetch($result)) {

			$client = [];

			foreach ($fields as $field) {

				$field = strtr($field, $synonyms);

				switch ($field) {

					case 'head_clid':

						$client[ "head_clidTitle" ] = get_client_category($da[ $field ]);
						$client[ $field ] = (int)$da[ $field ];

					break;
					case 'pid':

						$client[ "person" ] = current_person($da[ $field ]);
						$client[ $field ] = (int)$da[ $field ];

					break;
					case 'iduser':
					case 'user':

					$client[ "user" ] = current_userlogin($da[ $field ]);
					$client[ $field ] = (int)$da[ $field ];

					break;
					case 'idcategory':

						$client[ "idcategoryTitle" ] = get_client_category($da[ $field ]);
						$client[ $field ] = (int)$da[ $field ];

					break;
					case 'territory':

						$client[ "territoryTitle" ] = current_territory($da[ $field ]);
						$client[ $field ] = (int)$da[ $field ];

					break;
					case 'clientpath':

						$client[ "clientpathTitle" ] = current_clientpathbyid($da[ $field ]);
						$client[ $field ] = (int)$da[ $field ];

					break;
					default:

						//$client[ $field ] = $da[ $field ];

						if($field_types[ $field ] == "int"){

							$client[ $field ] = (int)$da[ $field ];

						}
						elseif(in_array($field_types[ $field ], ["float","double"])){

							$client[ $field ] = (float)$da[ $field ];

						}
						else {

							$client[ $field ] = $da[ $field ];

						}

					break;

				}

			}

			if ($params['bankinfo'] == 'yes') {

				$bankinfo = get_client_recv($da['clid'], 'yes');

				foreach ($bankInfoField as $key => $value) {
					$client['bankinfo'][ $value ] = $bankinfo[ $value ];
				}

			}

			if ($params['uids'] == 'yes'){

				$ruids = UIDs::info(["clid" => $da['clid']]);
				if($ruids['result'] == 'Success') {
					$client[ 'uids' ] = $ruids[ 'data' ];
				}

			}

			$response['data'][] = $client;

		}

		$response['count'] = (int)$db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE clid > 0 $sort and identity = '$identity'");

	break;

	//Получение информации о клиенте по id
	case 'info':

		$s = ($params['uid'] != '') ? "AND uid = '".$params['uid']."'" : "AND clid = '".$params['clid']."'";

		if( isset($params['inn']) && $params['inn'] != ''){

			if($params['uid'] == '' && $params['clid'] == '') {
				$s = '';
			}

			$s .= "AND FIND_IN_SET('".$params['inn']."', REPLACE(recv, ';',',')) ";

		}

		if($s != '') {

			$clid = (int)$db -> getOne( "SELECT clid FROM ".$sqlname."clientcat WHERE clid > 0 $s AND iduser IN (".yimplode(",", get_people( $iduser, "yes")).") and identity = '$identity'" );

			if ( $clid == 0 && (int)$params['clid'] > 0 ) {

				$response['result']        = 'Error';
				$response['error']['code'] = 403;
				$response['error']['text'] = "Клиент не найден в пределах аккаунта указанного пользователя.";

			}
			elseif ( $clid > 0 ) {

				$cdata = get_client_info( $clid, 'yes' );

				if ( !empty( $cdata ) ) {

					foreach ( $fields as $field ) {

						switch ($field) {

							//не понятно - зачем это
							case 'head_clid':
								$response['data'][ $field ] = current_client( $cdata[ $field ] );
							break;
							case 'pid':
								$response['data'][ $field ] = (int)$cdata[ $field ];
								$response['data']['person'] = current_person( $cdata[ $field ] );
							break;
							case 'iduser':
								$response['data'][ $field ] = (int)$cdata[ $field ];
								$response['data'][ 'user' ] = current_userlogin( $cdata[ $field ] );
							break;
							case 'idcategory':
								$response['data'][ $field ] = $cdata[ $field ];
								$response['data'][ 'categoryName' ] = get_client_category( $cdata[ $field ] );
							break;
							case 'territory':
								$response['data'][ $field ] = $cdata[ $field ];
								$response['data'][ 'territoryName' ] = current_territory( $cdata[ $field ] );
							break;
							case 'clientpath':
								$response['data'][ $field ] = current_clientpathbyid( $cdata['clientpath2'] );
							break;
							case 'date_create':
								$response['data'][ 'created' ] = $cdata['created'];
								$response['data'][ $field ] = $cdata['date_create'];
							break;
							case 'date_edit':
								$response['data'][ 'edited' ] = $cdata['edited'];
								$response['data'][ $field ] = $cdata['date_edit'];
							break;
							default:

								$response['data'][ $field ] = $cdata[ $field ] != '' ? $cdata[ $field ] : NULL;

							break;

						}

					}

					if ( $params['bankinfo'] == 'yes' ) {

						$bankinfo = get_client_recv( $clid, 'yes' );

						foreach ( $bankInfoField as $key => $value ) {
							$response['data']['bankinfo'][ $value ] = $bankinfo[ $value ];
						}

					}
					if ( $params['uids'] == 'yes' ) {

						$ruids = UIDs ::info( ["clid" => $clid] );
						$uids  = $ruids['data'];

					}
					if ( $params['contacts'] == 'yes' ) {

						$contacts = [];

						$queryArray           = getFilterQuery( 'person', [
							'clid'      => $clid,
							'haveEmail' => "yes",
							'fields'    => [
								'person',
								'ptitle',
								'clid',
								'tel',
								'mob',
								'mail',
								'iduser',
								'rol',
								'date_create'
							]
						] );
						$response['contacts'] = $db -> getAll( $queryArray['query'] );

					}

				}
				else {

					$response['result']        = 'Error';
					$response['error']['code'] = 404;
					$response['error']['text'] = "Не найдено";

				}

			}
			elseif ( $clid == 0 && $params['uid'] == '' && $params['inn'] == '' ) {

				$response['result']        = 'Error';
				$response['error']['code'] = 405;
				$response['error']['text'] = "Отсутствуют параметры клиента";

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = 404;
				$response['error']['text'] = "Не найдено";

			}

		}
		else{

			$response['result']        = 'Error';
			$response['error']['code'] = 405;
			$response['error']['text'] = "Отсутствуют параметры поиска клиента - clid, uid или inn";

		}

	break;

	//Добавление клиента
	case 'add':

		//print_r($params);

		$Client   = new Client();
		$response = $Client -> add($params);

		/*
		event ::fire( 'client.add', [
			"clid"  => $response['clid'],
			"autor" => $iduser,
			"user"  => getClientData($response['clid'], 'iduser')
		] );
		*/

	break;

	//Добавление списка клиентов
	case 'add.list':

		$j = 0;

		$iClient = new Client();

		foreach ($clients as $client) {

			$client['iduser'] = (!isset($client['user'])) ? $iduser : current_userbylogin($client['user']);

			$response[ $j ] = $iClient -> add($client);

			$j++;

		}

	break;

	//Изменение клиента
	case 'update':

		$params['fromapi'] = true;

		$Client   = new Client();
		$response = $Client -> fullupdate((int)$params['clid'], $params);

	break;

	//Удаление клиента
	case 'delete':

		$Client   = new Client();
		$response = $Client -> delete($params['clid']);

	break;

	//Передача клиента
	case 'change.user':

		$Client   = new Client();
		$response = $Client -> changeUser($params['clid'], $params);

	break;

	default:
		$response['error']['code'] = 404;
		$response['error']['text'] = 'Такого метода не существует!';
	break;

}

ext:

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;

print $rez = json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";
//HTTPStatus($code);

exit();