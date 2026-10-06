<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2016.20         */

/* ============================ */

use Salesman\Deal;
use Salesman\Todo;

set_time_limit( 300 );

header( "Access-Control-Allow-Origin: *" );
header( 'Content-Type: text/html; charset=utf-8' );

error_reporting( E_ERROR );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

/**
 * Отправляет запросы
 * @param $url
 * @param $json_value
 * @param $user
 * @param $password
 * @return array
 */
function esend_request($url, $json_value, $user, $password): array {

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $json_value ) );
	curl_setopt( $ch, CURLOPT_HEADER, 1 );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, [
		'Accept: application/json',
		'Content-Type: application/json'
	] );
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_USERPWD, $user.':'.$password );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	$output = curl_exec( $ch );
	curl_close( $ch );

	$e = explode( "\n", $output );

	$res  = yexplode( " ", $e[0], 2 );
	$list = json_decode( $e[8], true );

	$error = ($res == 'OK') ? "" : $res;

	return [
		"content" => $list,
		"error"   => $error
	];

}

/**
 * Основная функция, которая делает всю работу
 *
 * @param $type
 * @param array $params
 * @return array
 */
function subscribe($type, array $params = []): array {

	$rootpath = realpath( __DIR__.'/../../' );

	include $rootpath."/inc/config.php";
	include $rootpath."/inc/dbconnector.php";
	include $rootpath."/inc/func.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$db       = $GLOBALS['db'];

	$response = $answer = [];
	$today    = date( 'Y' ).'-'.date( 'm' ).'-'.date( 'd' );
	$state    = '';

	switch ($type) {

		case "MailerLite":

			require_once "class/MailerLite/Base/RestBase.php";
			require_once "class/MailerLite/Base/Rest.php";
			require_once "class/MailerLite/Subscribers.php";
			require_once "class/MailerLite/Lists.php";

			//подпишем клиента
			$ML_Subscribers = new MailerLite\Subscribers( $params['apikey'] );

			$subscribers = [
				'email'  => $params['subscriber']['email'],
				'name'   => $params['subscriber']['name'],
				'fields' => [
					[
						'name'  => 'date_start',
						'value' => $today
					]
				]
			];

			$answer = $ML_Subscribers -> setId( $params['listID'] ) -> add( $subscribers );

			$response['date']   = $today;
			$response['query']  = $subscribers;
			$response['answer'] = $answer;

		break;
		case "Unisender":

			require_once "class/Unisender/unisenderApi.php";

			$ML_Lists = new UniSenderApi( $params['apikey'] );

			$ar = [
				"list_ids"     => $params['listID'],
				"fields"       => [
					"email" => $params['subscriber']['email'],
					"Name"  => $params['subscriber']['name']
				],
				'double_optin' => 3
			];

			$answer = json_decode( $ML_Lists -> subscribe( $ar ), true );

			$answer['email'] = $params['subscriber']['email'];

			$response['date']   = $today;
			$response['query']  = "";
			$response['answer'] = $answer;

		break;
		case "MailChimp":

			require_once 'class/MailChimp/MCAPI.class.php';

			$vars = [
				'FNAME' => $params['subscriber']['name'],
				'LNAME' => ''
			];

			$api    = new MCAPI( $params['apikey'] );
			$answer = $api -> listSubscribe( $params['listID'], $params['subscriber']['email'], $vars );

			//$answer = json_decode($ML_Lists->subscribe($ar), true);

			/*$f = fopen("worker.log", "a");
			fwrite($f, array2string($answer));
			fwrite($f, "----------------------");
			fclose($f);*/

			if ( $answer == '1' )
				$answer['email'] = $params['subscriber']['email'];

			$response['date']   = $today;
			$response['query']  = "";
			$response['answer'] = $answer;

		break;
		case "SendPulse":

			require_once('class/SendPulse/api/sendpulseInterface.php');
			require_once('class/SendPulse/api/sendpulse.php');

			define( 'API_USER_ID', $params['userid'] );
			define( 'API_SECRET', $params['apikey'] );
			define( 'TOKEN_STORAGE', 'file' );
			define( 'PATH_TO_ATTACH_FILE', 'data/'.$params['fpath'] );

			$SPApiProxy = new SendpulseApi( API_USER_ID, API_SECRET, TOKEN_STORAGE, PATH_TO_ATTACH_FILE );

			$answer = $SPApiProxy -> addEmails( $params['listID'], [
				[
					"email"     => $params['subscriber']['email'],
					"variables" => ["Name" => $params['subscriber']['name']]
				]
			] );

			if ( $answer == '1' )
				$answer['email'] = $params['subscriber']['email'];

			$response['date']   = $today;
			$response['query']  = "";
			$response['answer'] = $answer;

		break;
		case "GetResponse":

			require_once('class/GetResponse/src/GetResponseAPI3.class.php');

			$getresponse = new GetResponse( $params['apikey'] );

			$data = [
				"name"     => $params['subscriber']['name'],
				"email"    => $params['subscriber']['email'],
				"campaign" => [
					"campaignId" => $params['listID']
				]
			];

			$answer = $getresponse -> addContact( $data );

			if ( $answer -> httpStatus == NULL )
				$answer['email'] = $params['subscriber']['email'];

			$response['date']   = $today;
			$response['query']  = $data;
			$response['answer'] = $answer;

		break;
		case "eSputnik":


			$first_name = $params['subscriber']['name'];
			$email      = $params['subscriber']['email'];// email контакта

			$user     = $params['userid'];
			$password = $params['apikey'];

			$import_contacts_url  = 'https://esputnik.com/api/v1/contacts';
			$contact              = new stdClass();
			$contact -> firstName = $first_name;
			$contact -> channels  = [
				[
					'type'  => 'email',
					'value' => $email
				]
			];

			$request_entity                  = new stdClass();
			$request_entity -> contacts      = [$contact];
			$request_entity -> dedupeOn      = 'email';
			$request_entity -> contactFields = [
				'firstName',
				'email'
			];
			$request_entity -> groupNames    = [$params['listName']];

			$answerr = esend_request( $import_contacts_url, $request_entity, $user, $password );

			if ( $answerr['error'] == '' ) {
				$answer['email']   = $params['subscriber']['email'];
				$answer['content'] = $answerr['content'];
			}
			else $answer['error'] = $answerr['error'];

			$response['date']   = $today;
			$response['query']  = $request_entity;
			$response['answer'] = $answer;

		break;

	}

	//Добавим напоминание указанного типа на заданную дату
	if ( $params['period'] > 0 && $answer['email'] != '' ) {

		//посмотрим, добавляли ли мы клиента в списки
		$id = (int)$db -> getOne( "SELECT id FROM ".$sqlname."chain_log WHERE email = '".$params['subscriber']['email']."' and state = 'added' and identity = '$identity'" ) + 0;

		//если не добавляли, то добавим напоминание
		if ( $id == 0 ) {

			$state = "added";

			try {

				if ( $params['theme'] != '' || $params['tip'] != '' ) {

					$task = [
						"iduser"   => $params['iduser'],
						"clid"     => $params['clid'],
						"pid"      => $params['pid'],
						"datum"    => $params['datum'],
						"day"      => "yes",
						"title"    => $params['theme'],
						"tip"      => $params['tip'],
						"active"   => 'yes',
						"created"  => current_datumtime(),
						"identity" => $identity
					];

					$d              = (new Todo()) -> add( $params['iduser'], $task );
					$response['id'] = $d['id'];

					//$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", $task);
					//$response['id']   = $db -> insertId();

					$response['task'] = "Добавлено напоминание";

				}
				elseif ( $params['theme'] != '' ) {
					$response['task'] = "Напоминание не добавлено - не указана тема";
				}
				elseif ( $params['tip'] != '' ) {
					$response['task'] = "Напоминание не добавлено - не указан тип напоминания";
				}

			}
			catch ( Exception $e ) {

				$response['task'] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else $state = "double";

	}

	//добавим запись в лог
	$db -> query( "INSERT INTO ".$sqlname."chain_log SET ?u", [
		"tip"      => $params['event'],
		"name"     => $params['subscriber']['name'],
		"email"    => $params['subscriber']['email'],
		"clid"     => (int)$params['clid'],
		"pid"      => (int)$params['pid'],
		"did"      => (int)$params['did'],
		"iduser"   => (int)$params['iduser'],
		"content"  => $params['subtip'],
		"listID"   => $params['listID'],
		"state"    => $state,
		"identity" => $identity
	] );

	/*
	$f = fopen("worker.log", "a");
	fwrite($f, array2string($response));
	fwrite($f, "----------------------");
	fclose($f);
	*/

	return $response;

}

//находим данные клиента
$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$iduser = (int)$_REQUEST['autor'];

//тип события, которое ловим и на которое реагируем
$event = $_REQUEST['event'];

echo "autoMailingList. Ok";

ob_flush();
flush();


$field = $db -> getRow( "SHOW COLUMNS FROM `".$sqlname."chain_log` LIKE 'listID'" );
if ( $field['Field'] == '' ) {

	$db -> query( "ALTER TABLE `".$sqlname."chain_log` ADD COLUMN `listID` VARCHAR(100) NOT NULL AFTER `content`" );
	$db -> query( "ALTER TABLE `".$sqlname."chain_log` ADD COLUMN `state` VARCHAR(100) NOT NULL AFTER `listID`" );

}

//проверяем активность плагина
$pluginActive = $db -> getOne( "SELECT active FROM ".$sqlname."plugins WHERE name = 'autoMailingList' and identity = '$identity'" );

if ( $pluginActive == 'off' )
	goto act;

$error      = [];
$response   = [];
$subscriber = [];
$answer     = '';
$relation1  = '';
$relation2  = '';
$listID     = '';
$datum      = '';

$today = date( 'Y' ).'-'.date( 'm' ).'-'.date( 'd' );

$fpath = '';

if ( $isCloud ) {

	//создаем папки хранения файлов
	createDir("data/".$identity);

	$fpath = $identity.'/';

}

//отладка
//$clid = '6089';
//$event = 'addclient';

//по умолчанию
if ( !$event ) {
	$event = 'client.add';
}

$relation = '';
$category = '';

if ( $event == 'deal.close' ) {

	$info = Deal ::info( $did );

	//для успешных сделок
	if ( $info['kol_fact'] > 0 ) {
		$event = 'deal.close.plus';
	}
	//для не успешных сделок
	else {
		$event = 'deal.close.minus';
	}

}
if ( $event == 'client.expressadd' ) {

	$event = ($pid > 0) ? 'perosn.add' : 'client.add';

}

//массив настроек
$settings = json_decode( (string)file_get_contents( 'data/'.$fpath.'settings.json' ), true );
$list     = $settings['list'];//списки рассылок

$config = json_decode( (string)file_get_contents( 'data/'.$fpath.'lists.json' ), true );
$apikey = $config['param']['apikey'];
$userid = $config['param']['userid'];
$group  = (array)$config['lists'];

foreach ( $group as $id => $item ) {
	$groups[ $item['id'] ] = $item['name'];
}

//*** Готовим данные подписчика ***//

//если добавлена сделка, то просто берем данные по клиентам и контакту, а обрабатываем ниже
if ( $did > 0 ) {

	$deal = get_dog_info( $did, 'yes' );

	$client = get_client_info( (int)$deal['clid'], 'yes' );
	$clid   = (int)$client['clid'];

	$pid = (int)yexplode( ";", (string)$deal['pid_list'], 0 );

}

//если добавлен только контакт, или он передан из сделки
if ( $pid > 0 ) {

	$person              = get_person_info( $pid, 'yes' );
	$subscriber['email'] = yexplode( ",", $person['mail'], 0 );
	$subscriber['name']  = $person['person'];

	$client = get_client_info( (int)$person['clid'], 'yes' );

	//для типов лояльности
	$relation1 = "person:".$person['relation'];
	//для отраслей
	$category = "clientcategory:".$client['idcategory'];

	$iduser = (int)$person['iduser'];
	$clid   = (int)$person['clid'];

}

//если добавлен клиент или он передан из сделки
if ( $clid > 0 && ($pid == 0 || $subscriber['email'] == '') ) {

	$client = get_client_info( $clid, 'yes' );

	//если тип клиента - физ.лицо, то берем его данные
	if ( $client['type'] == 'person' ) {

		$subscriber['email'] = yexplode( ",", $client['mail_url'], 0 );
		$subscriber['name']  = $client['title'];

		if ( $relation2 == '' ) {
			$relation2 = "client:".$client['relation'];
		}
		if ( $category == '' ) {
			$category = "clientcategory:".$client['idcategory'];
		}

		$iduser = (int)$client['iduser'];

		$ymail[] = yexplode( ",", $client['mail_url'] );

	}
	//если это юр.лицо, то берем данные основного контакта
	else {

		$client = get_client_info( $clid, 'yes' );

		$person              = get_person_info( (int)$client['pid'], 'yes' );
		$subscriber['email'] = yexplode( ",", (string)$person['mail'], 0 );
		$subscriber['name']  = $person['person'];
		$pid                 = (int)$client['pid'];

		if ( $subscriber['email'] == '' || $subscriber['email'] == NULL ) {
			$subscriber['email'] = yexplode( ",", (string)$client['mail_url'], 0 );
		}
		if ( $subscriber['name'] == '' ) {
			$subscriber['name'] = yexplode( ",", (string)$client['title'], 0 );
		}

		$imail[] = yexplode( ",", (string)$client['mail_url'] );
		$imail[] = yexplode( ",", (string)$person['mail'] );

		//для типов лояльности
		if ( $relation2 == '' )
			$relation2 = "client:".$client['relation'];

		//для отраслей
		if ( $client['type'] == 'client' ) {
			$category = "clientcategory:".$client['idcategory'];
		}
		elseif ( in_array( $client['type'], [
			'contractor',
			'partner'
		] ) ) {
			$category = "providercategory:".$client['idcategory'];
			//$relation    = "";
		}

		$iduser = $client['iduser'];

	}

}
if ( $clid > 0 && $pid > 0 && $relation2 == '' ) {

	$client    = get_client_info( $clid, 'yes' );
	$relation2 = "client:".$client['relation'];

}

$data = [
	"clid"       => $clid,
	"pid"        => $pid,
	"did"        => $did,
	"event"      => $event,
	"relation1"  => $relation1,
	"relation2"  => $relation2,
	"category"   => $category,
	"apikey"     => $apikey,
	"userid"     => $userid,
	"subscriber" => $subscriber,
	"iduser"     => $iduser
];

/*
$f = fopen("worker.log", "a");
fwrite($f, array2string($data));
fclose($f);
*/

$taskid = [];

//для каждого события выполним действие
foreach ( $list as $row ) {

	if ( $row['event'] == $event ) {

		$rezult = [];

		if ( $row['relation'] == $category || $row['relation'] == $relation1 || $row['relation'] == $relation2 ) {

			$ar = $data;

			if ( $row['relation'] == $category )
				$subtip = 'Отрасль';
			if ( $row['relation'] == $relation1 )
				$subtip = 'Тип отношений';
			if ( $row['relation'] == $relation2 )
				$subtip = 'Тип отношений';

			$ar['listID']   = $row['listID'];
			$ar['listName'] = strtr( $row['listID'], $groups );
			$ar['period']   = $row['period'];
			$ar['datum']    = current_datum( -$ar['period'] );
			$ar['theme']    = $row['theme'];
			$ar['tip']      = $row['tip'];
			$ar['subtip']   = $subtip;
			$ar['iduser']   = $iduser;

			if ( $ar['listID'] != '' && $data['subscriber']['email'] != '' )
				$rezult = subscribe( $config['type'], $ar );

			//goto act;

			$response['post'] = $_REQUEST;

			$msg = json_encode_cyr( [
				"data"     => $data,
				"response" => $response,
				"answer"   => $rezult
			] );

			$f = fopen( "data/".$fpath."response.log", "a" );
			fwrite( $f, $msg."\r\n" );
			fclose( $f );

			//print array2string(array("data" => $data, "response" => $response, "answer" => $rezult['answer']),"<br>","&nbsp;&nbsp;&nbsp;")."<br>";

		}

	}

}

act:

$response['post'] = $_REQUEST;

$answer = [
	"data"     => $data,
	"response" => $response,
	"imail"    => $imail,
	"ymail"    => $ymail
];

$msg = json_encode_cyr( $answer );

$f = fopen( "data/response.log", "a" );
fwrite( $f, $msg."\r\n" );
fclose( $f );

//print_r($answer);

exit();