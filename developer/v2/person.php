<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.2         */
/* ============================ */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
use Salesman\Person;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

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
		$params[ $key ] = (!is_array( $value )) ? Cleaner( $value ) : $value;
	}

	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];

}

if( is_null($APIKEY) && !is_null($params['apikey'])){
	$APIKEY = $params['apikey'];
	$LOGIN  = $params['login'];
}

//для приема массива клиентов для добавления
$persons          = $_REQUEST['persons'];
//$params['filter'] = $_REQUEST['filter'];

//доступные методы
$aceptedActions = [
	"fields",
	"list",
	"info",
	"add",
	"update",
	"add.list",
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
$iduser   = (int)$result['iduser'];
$iduser1  = (int)$result['iduser'];
$username = $result['title'];

require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/developer/events.php";

//установим временну зону под настройки аккаунта
date_default_timezone_set($timezone);

//порядок для реквизитов
$socInfoField = [
	"blog",
	"mysite",
	"twitter",
	"icq",
	"skype",
	"google",
	"yandex",
	"mykrug"
];
$socInfoName  = [
	'Блог',
	'Сайт',
	'Twitter',
	'ICQ',
	'Skype',
	'Google',
	'Я.ru',
	'Мой круг'
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

/**
 * Основные обработчики
 */

//составляем списки доступных полей для клиентов
$ifields[] = 'pid';
$ifields[] = 'date_create';
$ifields[] = 'date_edit';

$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='person' and fld_on='yes' and fld_name != 'social' and identity = '$identity'");
while ($do = $db -> fetch($resf)) {

	$ifields[] = $do['fld_name'];

}

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

switch ($params['action']) {

	//Вывод списка доступных полей
	case 'fields':

		$response['data']['pid']         = "Уникальный идентификатор записи контакта";
		$response['data']['date_create'] = "Дата создания. Timestamp";
		$response['data']['date_edit']   = "Дата последнего изменения. Timestamp";

		$resf = $db -> query("SELECT * FROM ".$sqlname."field WHERE fld_tip='person' and fld_on='yes' and fld_name != 'social' and identity = '$identity'");
		while ($do = $db -> fetch($resf)) {

			$response['data'][ $do['fld_name'] ] = $do['fld_title'];

		}

		foreach ($socInfoField as $key => $value) {

			$response['data']['social'][ $value ] = $socInfoName[ $key ];

		}

	break;

	//Информация о Контакте
	case 'info':

		$p      = Person::info($params['pid'])['person'];

		//print_r($fields);

		foreach ($fields as $field) {

			switch ($field) {

				case 'clid':

					$response['data'][ $field ] = $p[ $field ];
					$response['data']['client'] = current_client($p[ $field ]);

				break;
				case 'iduser':

					$response['data'][ $field ] = current_userlogin($p[ $field ]);

				break;
				case 'loyalty':

					$response['data'][ $field ] = current_loyalty($p[ $field ]);

				break;
				case 'clientpath':

					$response['data'][ $field ] = current_clientpathbyid($p['clientpath2']);

				break;
				case 'social':

					if ($params['socinfo'] == 'yes') {

						$socinfo = explode(";", $p['social']);
						foreach ($socInfoField as $key => $value) {
							$response['data']['socials'][ $value ] = $socinfo[ $key ];
						}

					}

				break;
				default:

					$response['data'][ $field ] = $p[ $field ];

				break;

			}

		}

		if ($params['socinfo'] == 'yes') {

			$socinfo = explode(";", $p['social']);

			foreach ($socInfoField as $key => $value) {

				$response['data']['socials'][ $value ] = $socinfo[ $key ];

			}

		}

		unset($response['data']['social']);

	break;

	//Вывод списка Контактов
	case 'list':

		//задаем лимиты по-умолчанию
		$offset = ($params['offset'] > 0) ? $params['offset'] : 0;
		$order  = ($params['order'] != '') ? $params['order'] : 'date_create';
		$first  = ($params['first'] == 'old') ? '' : 'DESC';

		$limit = 200;
		$sort  = '';

		//$sort .= get_people($iduser);

		if ($params['word'] != '') {

			$sort .= " and (replace(replace(replace(replace(replace(tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".Cleaner($params['word'])."%' or person LIKE '%".Cleaner($params['word'])."%' or ptitle LIKE '%".Cleaner($params['word'])."%' or mail LIKE '%".Cleaner($params['word'])."%')";

		}

		if ($params['dateStart'] != '' && $params['dateEnd'] == '') {
			$sort .= " and date_create > '".$params['dateStart']."'";
		}
		if ($params['dateStart'] != '' && $params['dateEnd'] != '') {
			$sort .= " and (date_create BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."')";
		}
		if ($params['dateStart'] == '' && $params['dateEnd'] != '') {
			$sort .= " and date_create < '".$params['dateEnd']."'";
		}

		if ($params['user'] != '') {
			$sort .= " and ".$sqlname."person.iduser = '".current_userbylogin( $params['user'] )."'";
		}
		else {
			$sort .= " and ".$sqlname."person.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
		}

		//todo: проверить работу доп.фильтров
		foreach ($params['filter'] as $k => $v) {

			switch ($k) {
				case 'clid':

					if ( (int)$v > 0) {
						$sort .= " and clid = '".(int)$v."'";
					}

				break;
				case 'uid':

					if ($v != '') {
						$sort .= " and uid = '".untag( $v )."'";
					}

				break;
				case 'loyalty':

					if ($v != '' && !is_numeric($v)) {
						$sort .= " and loyalty = '".current_loyalty( '', untag( $v ) )."'";
					}
					elseif ( $v != '' ) {
						$sort .= " and loyalty = '".(int)$v."'";
					}

				break;
				case 'clientpath':

					if ($v != '' && !is_numeric($v)) {
						$sort .= " and clientpath = '".getClientpath( untag( $v ) )."'";
					}
					elseif ($v != '') {
						$sort .= " and clientpath = '".(int)$v."'";
					}

				break;
				default:

					$sort .= " and ".$k." LIKE '%".untag($v)."%'";

				break;
			}

		}

		$lpos = $offset * $limit;
		$j    = 0;

		$field_types = db_columns_types( "{$sqlname}personcat" );

		$result = $db -> query("SELECT * FROM ".$sqlname."personcat WHERE pid > 0 ".$sort." and identity = '$identity' ORDER BY $order $first LIMIT $lpos,$limit");
		while ($da = $db -> fetch($result)) {

			$person = [];

			foreach ($fields as $field) {

				switch ($field) {

					case 'clid':

						$person[ $field ] = (int)$da[ $field ];
						$person['client'] = current_client($da[ $field ]);

					break;
					case 'loyalty':

						$person[ $field ] = current_loyalty($da[ $field ]);

					break;
					case 'iduser':

						$person["user"]   = current_userlogin($da[ $field ]);
						$person[ $field ] = (int)$da[ $field ];

					break;
					case 'clientpath':

						$person[ $field ] = current_clientpathbyid($da[ $field ]);

					break;
					default:

						//$person[ $field ] = $da[ $field ];

						if($field_types[ $field ] == "int"){

							$person[ $field ] = (int)$da[ $field ];

						}
						elseif(in_array($field_types[ $field ], ["float","double"])){

							$person[ $field ] = (float)$da[ $field ];

						}
						else {

							$person[ $field ] = $da[ $field ];

						}

					break;

				}

			}

			if ($params['socinfo'] == 'yes') {

				$socinfo = explode(";", $da['social']);

				foreach ($socInfoField as $key => $value) {
					$person['socials'][ $value ] = $socinfo[ $key ];
				}

			}

			$response['data'][] = $person;

		}

		$response['count'] = (int)$db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE pid > 0 ".$sort." and identity = '$identity'");

	break;

	//добавление контакта
	case 'add':

		$person   = new Person();
		$response = $person -> edit(0, $params);

	break;

	//групповое добавление
	case 'add.list':

		foreach ($persons as $i => $item) {

			$item['iduser'] = ($item['user'] == '') ? $iduser1 : current_userbylogin($item['user']);

			$person   = new Person();
			$response[] = $person -> edit(0, $item);

		}

	break;

	//обновление записи
	case 'update':

		//иначе привязка пропадет
		if(!isset($params['clid'])) {
			$params['clid'] = (int)getPersonData( $params['pid'], 'clid' );
		}

		$params['fromapi'] = true;

		$person   = new Person();
		$response = $person -> fullupdate((int)$params['pid'], $params);

	break;

	//удаление записи
	case 'delete':

		$person   = new Person();
		$response = $person -> delete((int)$params['pid']);

	break;

	default:

		$response['error']['code'] = 404;
		$response['error']['text'] = 'Неизвестный метод';

	break;
}

ext:

$code = (int)$response['error']['code'] > 0 ? (int)$response['error']['code'] : 200;
//HTTPStatus($code);

print $rez = json_encode_cyr($response);

include dirname( __DIR__)."/v2/logger.php";

exit();


