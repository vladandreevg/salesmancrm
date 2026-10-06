<?php
$apikey = $db -> getOne("SELECT user_key FROM ".$sqlname."services where name = 'Mailchimp' and identity='".$GLOBALS['identity']."'");

//Работа со списками
function doMethodListM($method, $nameList = '', $lid = '') {

	$skey = $GLOBALS['skey'];
	$ivc  = $GLOBALS['ivc'];
	$db   = $GLOBALS['db'];

	$prefix = realpath(__DIR__.'/../../../../');

	include $prefix."/inc/config.php";
	include $prefix."/inc/dbconnector.php";
	include $prefix."/inc/settings.php";
	require_once $prefix."/inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	$result = $db -> getOne("SELECT user_key FROM ".$sqlname."services where name = 'Mailchimp' and identity='".$identity."'");
	$apikey = rij_decrypt($result, $skey, $ivc);

	global $methodResult;
	global $methodError;
	switch ($method) {

		case 'getLists'://получить списки рассылки с их кодами
			$api    = new MCAPI($apikey);
			$result = $api -> lists();
			//print_r($result['data']);
			//exit();
		break;

		/*case 'createList'://создать новый список рассылки
			//$param = "Новый список рассылки";
			$POST = array ('api_key' => $api_key, 'title' => $nameList);// Создаём POST-запрос
			$result = Send('http://api.unisender.com/ru/api/createList?format=json', $POST);
		break;*/

		case 'updateList'://изменить свойства списка рассылки
			$POST   = array(
				'api_key' => $apikey,
				'title'   => $nameList,
				'list_id' => $lid
			);// Создаём POST-запрос
			$result = Send('http://api.unisender.com/ru/api/updateList?format=json', $POST);
		break;

		case 'deleteList'://удалить список рассылки
			$POST   = array(
				'api_key' => $apikey,
				'list_id' => $lid
			);// Создаём POST-запрос
			$result = Send('http://api.unisender.com/ru/api/deleteList?format=json', $POST);
		break;
	}

	if ($api -> errorCode) {
		$methodError = "Ошибка: "."[".$api -> errorCode."] ".$api -> errorMessage;// Ошибка соединения с API-сервером
	}
	else {
		$methodResult = $result['data'];
	}
}

//Работа с конкретным контактом
function doMethodContactM($method, $paramList) {

	$skey = $GLOBALS['skey'];
	$ivc  = $GLOBALS['ivc'];
	$db   = $GLOBALS['db'];

	$prefix = realpath(__DIR__.'/../../../../');

	include $prefix."/inc/config.php";
	include $prefix."/inc/dbconnector.php";
	include $prefix."/inc/settings.php";
	require_once $prefix."/inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	$result = $db -> getOne("SELECT user_key FROM ".$sqlname."services where name = 'Mailchimp' and identity='".$identity."'");
	$apikey = rij_decrypt($result, $skey, $ivc);

	global $methodResult;
	global $methodError;

	$vars = array(
		'FNAME' => $paramList['user_name'],
		'LNAME' => ''
	);

	switch ($method) {
		case 'subscribe'://отписать адресата от рассылки
			$api    = new MCAPI($apikey);
			$result = $api -> listSubscribe($paramList['user_lists'], $paramList['user_email'], $vars);
		break;
		case 'unsubscribe'://отписать адресата от рассылки
			$api    = new MCAPI($apikey);
			$result = $api -> listUnsubscribe($paramList['user_lists'], $paramList['user_email']);
		break;
	}

	print $api -> errorMessage;

	if ($api -> errorCode) {
		$methodError = "Ошибка: "."[".$api -> errorCode."] ".$api -> errorMessage;// Ошибка соединения с API-сервером
	}
	else {
		$methodResult = "";
	}
}

//Работа с множеством контактов
function doMethodSyncM($method, $paramList) {

	$skey = $GLOBALS['skey'];
	$ivc  = $GLOBALS['ivc'];
	$db   = $GLOBALS['db'];

	$prefix = realpath(__DIR__.'/../../../../');

	include $prefix."/inc/config.php";
	include $prefix."/inc/dbconnector.php";
	include $prefix."/inc/settings.php";
	require_once $prefix."/inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	$result = $db -> getOne("SELECT user_key FROM ".$sqlname."services where name = 'Mailchimp' and identity='".$identity."'");
	$apikey = rij_decrypt($result, $skey, $ivc);

	global $methodResult;
	global $methodError;

	switch ($method) {

		case 'importContacts'://массовый экспорт в сервис и синхронизация контактов


		break;

		case 'exportContacts'://импорт всех данных контактов из сервиса в CRM

			$api    = new MCAPI($apikey);
			$result = $api -> listMembers($paramList['user_list'], 'subscribed', '', $paramList["offset"], $paramList['limit']);

		break;
	}

	if ($api -> errorCode) {
		$methodError = "Ошибка: "."[".$api -> errorCode."] ".$api -> errorMessage;// Ошибка соединения с API-сервером
	}
	else {
		$methodResult = $result['data'];
	}
}

function doMethodExportM($paramList) {

	$skey = $GLOBALS['skey'];
	$ivc  = $GLOBALS['ivc'];
	$db   = $GLOBALS['db'];

	$prefix = realpath(__DIR__.'/../../../../');

	include $prefix."/inc/config.php";
	include $prefix."/inc/dbconnector.php";
	include $prefix."/inc/settings.php";
	require_once $prefix."/inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	$result = $db -> getOne("SELECT user_key FROM ".$sqlname."services where name = 'Mailchimp' and identity='".$identity."'");
	$apikey = rij_decrypt($result, $skey, $ivc);

	global $methodResult;
	global $methodError;

	//найдем аппендикс - 3 последних символа
	$sub = substr($apikey, -3);

	$chunk_size = 4096; //in bytes
	$url        = 'http://'.$sub.'.api.mailchimp.com/export/1.0/list?apikey='.$apikey.'&id='.$paramList["user_list"];

	$handle = @fopen($url, 'r');
	if (!$handle) {
		$methodError = "failed to access url\n";
	}
	else {
		$i      = 0;
		$header = array();
		while (!feof($handle)) {
			$buffer = fgets($handle, $chunk_size);
			if (trim($buffer) != '') {
				if ($i != 0) $obj[] = json_decode($buffer);
				$i++;
			}
		}
		fclose($handle);
	}

	$methodResult = $obj;
}

function getMemberInfoM($email, $paramList) {

	$skey = $GLOBALS['skey'];
	$ivc  = $GLOBALS['ivc'];
	$db   = $GLOBALS['db'];

	$prefix = realpath(__DIR__.'/../../../../');

	include $prefix."/inc/config.php";
	include $prefix."/inc/dbconnector.php";
	include $prefix."/inc/settings.php";
	require_once $prefix."/inc/func.php";

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	global $memberInfo;

	$result = $db -> getOne("SELECT user_key FROM ".$sqlname."services where name = 'Mailchimp' and identity='".$identity."'");
	$apikey = rij_decrypt($result, $skey, $ivc);

	$api2       = new MCAPI($apikey);
	$memberInfo = $api2 -> listMemberInfo($paramList['user_list'], $email);

	if ($api2 -> errorCode) {
		print "Ошибка: "."[".$api2 -> errorCode."] ".$api2 -> errorMessage;// Ошибка соединения с API-сервером
	}

}