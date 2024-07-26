<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */


use Novofon_API\Client;

/**
 * @throws Exception
 */
function doMethod($method, $param = []) {

	$rootpath = dirname( __DIR__, 3 );

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/func.php";

	require_once $rootpath."/content/pbx/novofon/novofon-api-v1/lib/Client.php";

	$sqlname  = $GLOBALS['sqlname'];
	$identity = $GLOBALS['identity'];
	$answers  = $GLOBALS['answers'];
	$db       = $GLOBALS['db'];
	$iduser   = $GLOBALS['iduser1'];

	$api_key    = $param['api_key'];
	$api_secret = $param['api_secret'];

	//для call (исходящий)
	$from = $param['from'];
	$to   = $param['to'];
	$clid = (int)$param['clid'];
	$pid  = (int)$param['pid'];

	//для history
	$dstart = $param['dstart'];
	$dend   = $param['dend'];

	$url = '';
	$postdata = [];
	$answerObject = new stdClass();

	switch ($method) {

		//проверка подключения для sip_editor.php
		case 'balance':

			$url = '/v1/info/balance/';

		break;

		//исходящий вызов для callto.php
		case 'call':

			$url = '/v1/request/callback/';

			$postdata = [
				"from" => $from,
				"to"   => $to
			];

		break;

		//запрос истории для cdr.php
		case 'history':

			$url = '/v1/statistics/pbx/';

			$date_from = ($dstart) ? : current_datumtime( 24 );
			$date_to   = ($dend) ? : current_datumtime();

			$postdata = [
				"start"   => $date_from,
				"end"     => $date_to,
				"version" => '2',
			];

			//print_r($postdata);

		break;

		//запрос общей статистики (цена, страна и пр. херь)
		case 'statistic':

			$url = '/v1/statistics/';

			$date_from = ($dstart) ? : current_datumtime( 24 );
			$date_to   = ($dend) ? : current_datumtime();

			$postdata = [
				"start" => $date_from,
				"end"   => $date_to

			];

		break;

		case 'tzone':

			$url = '/v1/info/timezone/';

		break;

		//запрос ссылки на файл
		case 'record':

			$url = '/v1/pbx/record/request/';

			$postdata = [
				"call_id"   => $param['call_id']
			];

		break;

	}

	if ($url != '') {

		$zd = new Client($api_key, $api_secret);

		if (in_array($method, ["history", "call", "record"])) {
			$answer = $zd -> call( $url, $postdata );
		}

		else {
			$answer = $zd -> call( $url );
		}

		//print $answer;

		$answerObject = json_decode($answer);

		//исходящий звонок
		if ($method == 'call') {

			//Добавим звонок в базу
			$id = $db -> getOne("select id from {$sqlname}zadarma_log where extension = '$from' and type = 'out' and identity = '$identity'");

			if ($id == 0) {

				$db -> query("INSERT INTO  {$sqlname}zadarma_log SET ?u", [
					'datum'     => current_datumtime(),
					//'callid'    => $rez['uuid'],//не приходит в api
					'extension' => $from,
					'phone'     => $to,
					'status'    => "",
					//'content'   => "",
					'type'      => "out",
					'clid'      => $clid,
					'pid'       => $pid,
					'identity'  => $identity
				]);

			}
			else {

				$db -> query("UPDATE {$sqlname}zadarma_log SET ?u WHERE id = '$id'", [
					'datum'    => current_datumtime(),
					//'callid'   => $rez['uuid'],//не приходит в api
					'phone'    => $to,
					'status'   => "",
					//'content'  => "",
					'type'     => "out",
					'clid'     => $clid,
					'pid'      => $pid,
					'identity' => $identity
				]);

			}

		}

	}

	//var_dump($answerObject);

	return $answerObject;

}