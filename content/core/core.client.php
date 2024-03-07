<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Client;
use Salesman\Deal;
use Salesman\Mailer;
use Salesman\Person;
use Salesman\Todo;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";

$clid   = (int)$_REQUEST['clid'];
$action = $_REQUEST['action'];

if ( $action == 'client.express' ) {

	$personIsNew = false;

	$clid = (int)$_REQUEST['client']['clid'];
	unset( $_REQUEST['client']['clid'] );

	$pid = (int)$_REQUEST['person']['pid'];
	unset( $_REQUEST['person']['pid'] );

	$err    = $fields = $mes = [];
	$newtid = 0;

	$params['tiphist'] = $_REQUEST['tiphist'];
	$params['deshist'] = $_REQUEST['deshist'];

	$iduser = (int)$_REQUEST['iduser'];

	/**
	 * КЛИЕНТ
	 */
	$client = $_REQUEST['client'];

	if ( $clid == 0 ) {

		$client['iduser'] = $_REQUEST['iduser'];

		$Client  = new Client();
		$cresult = $Client -> add( $client );

		$clid = (int)$cresult['data'];

	}
	else {

		$client['iduser'] = $_REQUEST['iduser'];

		$Client = new Client();
		$cresult = $Client -> update( (int)$clid, $client );

	}

	if ( $cresult['error']['text'] != '' ) {
		$err[] = $cresult['error']['text'];
	}

	/**
	 * КОНТАКТ
	 */

	$person = $_REQUEST['person'];

	/**
	 * Фильтры данных
	 */

	if ( $pid == 0 ) {

		$person['clid']    = $clid;
		$person['iduser']  = $iduser;
		$person['mperson'] = $_REQUEST['mperson'];

		$Person = new Person();
		$result = $Person -> edit( 0, $person );

		//$hooks -> do_action( "person_add", $person );

		$pid   = (int)$result['data'];
		$mes[] = $result['result'];

		if ( $result['error'] != '' ) {
			$err[] = $result['error'];
		}

		$personIsNew = true;

	}
	else {

		$Person = new Person();
		$result = $Person -> edit( $pid, $person );

		//$hooks -> do_action( "person_edit", $person );

		$pid   = (int)$result['data'];
		$mes[] = $result['result'];

		if ( $result['error'] != '' ) {
			$err[] = $result['error'];
		}

	}

	$did = 0;

	if ( isset( $_REQUEST['dogovor'] ) ) {

		$adeal           = $_REQUEST['dogovor'];
		$adeal['clid']   = $clid;
		$adeal['iduser'] = $iduser;

		// спецификация. не используется
		if ( count( (array)$dspeka ) > 0 ) {

			$adeal['speka']     = $dspeka;
			$adeal['calculate'] = "yes";

		}

		$deal   = new Deal();
		$result = $deal -> add( $adeal );

		$did = (int)$result['data'];

	}

	//добавляем историю активности
	if ( $params['deshist'] == '' ) {

		$params['deshist'] = "Добавлен клиент";
		$params['tiphist'] = "СобытиеCRM";

	}

	$hst = [
		"iduser"   => $iduser1,
		"clid"     => $clid,
		"pid"      => $pid,
		"did"      => $did,
		"datum"    => current_datumtime(),
		"des"      => $params['deshist'],
		"tip"      => $params['tiphist'],
		"identity" => $identity
	];

	$params['hid'] = addHistorty( $hst );

	$hst['сid'] = (int)$params['hid'];

	if ( $hooks ) {
		$hooks -> do_action( "history_add", $_REQUEST, $hst );
	}

	//если включена интеграция с телефонией, то проверим номер на наличие в базе и свяжем
	if ( $GLOBALS['sip_active'] == 'yes' ) {

		$income = substr( $_REQUEST['income'], 1 );

		//если форма была вызвана из окна телефонии или из истории звонков
		if ( $income != '' ) {

			$res = $db -> getAll( "SELECT id FROM ".$sqlname."callhistory WHERE ((src LIKE '%$income' AND direct = 'income') OR (dst LIKE '%$income' AND direct = 'outcome')) AND identity = '$identity'" );
			foreach ( $res as $data ) {

				$db -> query( "UPDATE ".$sqlname."callhistory SET clid = '$clid', pid = '$pid' WHERE id = '".$data['id']."' and identity = '$identity'" );

			}

		}
		//если нет, то придется обходить все пришедшие номера телефонов
		else {

			$t1 = (is_array( $client['phone'] )) ? $client['phone'] : yexplode( ",", (string)$client['phone'] );
			$t2 = (is_array( $person['tel'] )) ? $person['tel'] : yexplode( ",", (string)$person['tel'] );
			$t3 = (is_array( $person['mob'] )) ? $person['mob'] : yexplode( ",", (string)$person['mob'] );

			//находим все телефоны
			$ph = array_merge( $t1, $t2, $t3 );

			//print_r($ph);

			foreach ( $ph as $t ) {

				$income = substr( $t, 1 );

				if ( $income != '' ) {

					$res = $db -> getAll( "SELECT id FROM ".$sqlname."callhistory WHERE ((src LIKE '%$income' AND direct = 'income') OR (dst LIKE '%$income' AND direct = 'outcome')) AND pid < 1 AND identity = '$identity'" );
					foreach ( $res as $data ) {

						$db -> query( "UPDATE ".$sqlname."callhistory SET clid = '$clid', pid = '$pid' WHERE id = '".$data['id']."' and identity = '$identity'" );

					}

				}

			}

		}

		if ( $_REQUEST['income'] != '' ) {
			callTrack( $_REQUEST['income'], '', $clid, true );
		}

	}

	//Добавим напоминание
	$todo = $_REQUEST['todo'];
	if ( $todo['theme'] != '' ) {

		$todo = $hooks -> apply_filters( "task_addfilter", $todo );

		$iduser = $todo['touser'] = ($todo['touser'] < 1) ? $iduser1 : $todo['touser'];

		$tparam = [
			"iduser"   => $todo['touser'],
			"clid"     => $clid,
			"pid"      => $pid,
			"did"      => $did,
			"datum"    => $todo['datum'],
			"totime"   => $todo['totime'],
			"title"    => untag( $todo['theme'] ),
			"des"      => untag( $todo['des'] ),
			"tip"      => $todo['tip'],
			"active"   => 'yes',
			"autor"    => $iduser1,
			"priority" => untag( $todo['priority'] ),
			"speed"    => untag( $todo['speed'] ),
			"created"  => current_datumtime(),
			"alert"    => $todo['alert'],
			"readonly" => $todo['readonly'],
			"day"      => $todo['day'],
			"identity" => $identity
		];

		if ( isset( $todo['datumtime'] ) ) {

			$todo['datumtime'] = str_replace( [
				"T",
				"Z"
			], [
				" ",
				""
			], $todo['datumtime'] );

			$tparam['datum']  = datetime2date( $todo['datumtime'] );
			$tparam['totime'] = getTime( (string)$todo['datumtime'] );

		}

		$todo = new Todo();
		$task = $todo -> add( (int)$iduser, $tparam );

		/*
		$db -> query("INSERT INTO ".$sqlname."tasks SET ?u", $task);
		$newtid = $db -> insertId();

		createCal($newtid, "false");
		*/

		if ( $task['result'] == 'Success' ) {

			$hooks -> do_action( "task_add", $_REQUEST, $tparam );

			$mes[]  = implode( "<br>", (array)$task['text'] );
			$newtid = (int)$task['id'];

		}

	}

	//добавляем файлы почты
	$folder = (int)$db -> getOne( "SELECT idcategory FROM ".$sqlname."file_cat WHERE title = 'Файлы почты' and identity = '$identity'" );
	if ( $folder == 0 ) {

		$db -> query( "INSERT INTO ".$sqlname."file_cat SET ?u", [
			"title"    => 'Файлы почты',
			"identity" => $identity
		] );
		$folder = $db -> insertId();

	}

	//перенесем файлы из почты
	if ( (int)$_REQUEST['messageid'] > 0 || (int)$_REQUEST['rid'] > 0 ) {

		//добавим к адресату id записей клиента и контакта
		$db -> query( "UPDATE ".$sqlname."ymail_messagesrec SET ?u WHERE mid = '$_REQUEST[messageid]' and identity = '$identity'", [
			"pid"  => (int)$pid,
			"clid" => (int)$clid
		] );

		//добавим историю

		//найдем email в письме
		if ( (int)$_REQUEST['rid'] > 0 ) {

			$yemail = $db -> getOne( "SELECT email FROM ".$sqlname."ymail_messagesrec WHERE id = '$_REQUEST[rid]' and identity = '$identity'" );

			$s = " or email = '$yemail'";

		}

		//Пройдем все письма и присвоим им данные клиента
		$result = $db -> query( "SELECT * FROM ".$sqlname."ymail_messagesrec WHERE (mid = '$_REQUEST[messageid]' $s) and identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			$db -> query( "UPDATE ".$sqlname."ymail_messagesrec SET ?u WHERE id = '$data[id]'", [
				"pid"  => (int)$pid,
				"clid" => (int)$clid
			] );

			$hid = (int)$db -> getOne( "SELECT hid FROM ".$sqlname."ymail_messages WHERE id='$data[mid]' and identity = '$identity'" );

			if ( $hid == 0 ) {

				$rezz = Mailer ::putHistory( (int)$data['mid'] );
				$rez  = implode( "<br>", $rezz );

			}

		}

	}

	$iduser = getClientData( (int)$params['clid'], "iduser" );

	$message = implode( ", ", $mes );
	$error   = implode( ", ", $err );

	$args = [
		"clid"  => (int)$clid,
		"did"   => (int)$did,
		"autor" => (int)$iduser1,
		"user"  => (int)$iduser
	];

	if ( $personIsNew ) {
		$args['pid'] = (int)$pid;
	}

	if ( $newtid > 0 ) {
		$args['tid'] = $newtid;
	}

	event ::fire( 'client.expressadd', $args );

	print json_encode_cyr( [
		"clid"   => (int)$clid,
		"did"    => (int)$did,
		"result" => $message,
		"error"  => $error
	] );

	exit();

}

if ( $action == 'client.edit' ) {

	if ( $clid > 0 ) {

		unset( $_REQUEST['clid'] );

		//Обновим клиента
		$Client  = new Client();
		$cresult = $Client -> fullupdate( $clid, $_REQUEST );

		$r = $cresult['result'].$cresult['error']['text'];

	}
	else {

		$client = $_REQUEST;

		//Обновим клиента
		$Client  = new Client();
		$cresult = $Client -> add( $client );

		$r = ($cresult['result'] == 'Error') ? $cresult['error']['text'] : '';

		$clid = $cresult['data'];

		//Определим источник клиента
		callTrack( '', '', $clid, true );

		//перенесем файлы из почты
		if ( (int)$_REQUEST['messageid'] > 0 || (int)$_REQUEST['rid'] > 0 ) {

			//добавим к адресату id записей клиента и контакта
			$db -> query( "UPDATE ".$sqlname."ymail_messagesrec SET pid = '$pid', clid = '$clid' WHERE mid = '".$_REQUEST['messageid']."' and identity = '$identity'" );

			//добавим историю
			//include_once "../../modules/ymail/yfunc.php";

			//найдем email в письме
			if ( (int)$_REQUEST['rid'] > 0 ) {

				$yemail = $db -> getOne( "SELECT email FROM ".$sqlname."ymail_messagesrec WHERE id = '".$_REQUEST['rid']."' and identity = '$identity'" );

				$s = " or email = '$yemail'";

			}

			//Пройдем все письма и присвоим им данные клиента
			$result = $db -> getAll( "SELECT * FROM ".$sqlname."ymail_messagesrec WHERE (mid = '".$_REQUEST['messageid']."' $s) and identity = '$identity'" );
			foreach ( $result as $data ) {

				$db -> query( "update ".$sqlname."ymail_messagesrec set pid = '$pid', clid = '$clid' WHERE id = '".$data['id']."'" );

				$hid = $db -> getOne( "select hid from ".$sqlname."ymail_messages WHERE id = '".$data['mid']."' and identity = '$identity'" );

				if ( (int)$hid == 0 ) {

					$rezz = Mailer ::putHistory( (int)$data['mid'] );
					$rez  = implode( "<br>", $rezz );

				}

			}

		}

	}

	print '{"clid":"'.$clid.'","result":"Готово. '.$r.'"}';

	exit();

}

// Одиночное изменение поля клиента
if ( $action == 'client.change.field' ) {

	$val = (is_array( $_REQUEST['value'] )) ? yimplode( ",", $_REQUEST['value'] ) : $_REQUEST['value'];

	$params[ $_REQUEST['field'] ] = $val;

	$client = new Client();
	$result = $client -> update( (int)$_REQUEST['clid'], $params );

	$mes = $result['result'];

	if ( $result['error']['text'] ) {
		$mes .= "<br>".implode( "<br>", $result['error']['text'] );
	}

	print $mes;

	exit();

}

if ( $action == "client.delete" ) {

	//Обновим клиента
	$Client  = new Client();
	$cresult = $Client -> delete( $clid );

	print json_encode_cyr( [
		"result" => $cresult['message'],
		"error"  => $cresult['error']['text']
	] );

	exit();
}

if ( $action == "client.mass" ) {

	set_time_limit( 0 );

	$ids        = yexplode( ",", $_REQUEST['ids'] );
	$doAction   = $_REQUEST['doAction'];
	$isSelect   = $_REQUEST['isSelect'];
	$newuser    = $_REQUEST['newuser'];
	$duser      = $_REQUEST['duser'];
	$nterritory = $_REQUEST['nterritory'];
	$tipcmr     = $_REQUEST['tipcmr'];

	$person_send = $_REQUEST['person_send'];
	$dog_send    = $_REQUEST['dog_send'];
	$credit_send = $_REQUEST['credit_send'];

	$reazon = ($_REQUEST['reazon'] != '') ? $_REQUEST['reazon'] : 'Не указана';
	$newgid = $_REQUEST['newgid'];
	$matip  = $_REQUEST['matip'];
	$ord    = $_REQUEST['ord'];
	$tuda   = $_REQUEST['tuda'];

	$rez = '';
	$msg = [];

	//поля, которые относятся к датам
	$isDate = [
		"date_create",
		"last_dog"
	];

	$good  = 0;
	$pgood = 0;
	$dgood = 0;
	$hgood = 0;
	$err   = [];
	$noac  = 0;

	if ( $isSelect == 'doAll' ) {

		//--новый запрос. старт

		$iduser     = (int)$_REQUEST['iduser'];
		$idcategory = implode( ",", $_REQUEST['idcategory'] );
		$word       = $_REQUEST['word'];
		$alf        = $_REQUEST['alf'];
		$tbl_list   = $_REQUEST['tbl_list'];
		$filter     = $_REQUEST['list'];
		$groups     = $_REQUEST['groups'];

		$tip_cmr = $_REQUEST['tip_cmr'];

		$clientpath  = implode( ",", $_REQUEST['clientpath'] );
		$clientpath0 = $_REQUEST['clientpath0'];
		$territory   = $_REQUEST['territory'];
		$type        = $_REQUEST['type'];

		$haveEmail   = $_REQUEST['haveEmail'];
		$havePhone   = $_REQUEST['havePhone'];
		$haveTask    = $_REQUEST['haveTask'];
		$haveHistory = $_REQUEST['haveHistory'];
		$otherParam  = $_REQUEST['otherParam'];

		$dog_history    = $_REQUEST['dog_history'];
		$client_history = $_REQUEST['client_history'];

		$showHistTip = $_REQUEST['showHistTip'];

		$fields = [
			'title',
			'idcategory',
			'date_create',
			'pid',
			'type',
			'phone',
			'site_url',
			'mail_url',
			'iduser',
			'tip_cmr',
			'clientpath',
			'last_hist',
			'last_dog'
		];

		$query = getFilterQuery( 'client', [
			'iduser'         => $iduser,
			'word'           => $word,
			'alf'            => $alf,
			'tbl_list'       => $tbl_list,
			'filter'         => $filter,
			'idcategory'     => $idcategory,
			'clientpath'     => $clientpath,
			'clientpath0'    => $clientpath0,
			'territory'      => $territory != 0 ? $territory : [],
			'tip_cmr'        => $tip_cmr,
			'type'           => $type,
			'haveEmail'      => $haveEmail,
			'havePhone'      => $havePhone,
			'haveTask'       => $haveTask,
			'haveHistory'    => $haveHistory,
			'otherParam'     => $otherParam,
			'groups'         => $groups,
			'dog_history'    => $dog_history,
			'client_history' => $client_history,
			'dostup'         => $doAction == 'dostupDelete' ? $duser : NULL,
			'fields'         => $fields
		], false );

		//параметр сортировки
		if ( $ord == '' ) {
			$ord = "title";
		}
		elseif ( $ord == 'email' ) {
			$ord = 'mail_url';
		}
		elseif ( $ord == 'site' ) {
			$ord = 'site_url';
		}
		elseif ( $ord == 'category' ) {
			$ord = 'idcategory';
		}
		elseif ( $ord == 'user' ) {
			$ord = 'iduser';
		}
		elseif ( $ord == 'relation' ) {
			$ord = 'tip_cmr';
		}
		elseif ( $ord == 'dcreate' ) {
			$ord = 'date_create';
		}
		elseif ( $ord == 'history' ) {
			$ord = 'last_hist';
		}
		elseif ( $ord == 'dogovor' ) {
			$ord = 'last_dog';
		}
		elseif ( $ord == 'last_hist' ) {
			$ord = 'last_history';
		}
		elseif ( $ord == 'last_history' ) {
			$ord = 'last_history';
		}
		else {
			$ord = $sqlname."clientcat.".$ord;
		}

		$total = 0;

		$query .= " ORDER BY $ord $tuda";

		//произведем действия
		switch ($doAction) {

			case 'userChange':

				$clients = [];

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = get_accesse( (int)$data['clid'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 ) {

							$olduser = getClientData( (int)$data['clid'], 'iduser' );

							try {

								$db -> query( "UPDATE ".$sqlname."clientcat SET iduser = '$newuser', trash = 'no' WHERE clid = '".$data['clid']."' and identity = '$identity'" );
								$good++;

								$clients[] = [
									"clid"    => (int)$data['clid'],
									"title"   => $data['title'],
									"olduser" => $olduser
								];

								//передадим напоминания
								if ( $_REQUEST['todo_send'] == 'yes' ) {
									$db -> query( "UPDATE ".$sqlname."tasks set iduser = '$newuser' WHERE clid = '".$data['clid']."' and iduser = '$olduser'" );
								}

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

							//внесем запись в историю Организации
							$current_user = current_user( get_userid( 'clid', $data['clid'] ) );
							if ( $current_user == '' ) {
								$current_user = "Свободный клиент";
							}

							try {

								$hid = addHistorty( [
									"iduser"   => $iduser1,
									"clid"     => (int)$data['clid'],
									"datum"    => current_datumtime(),
									"des"      => 'Передача Клиента от '.$current_user.'. <b>Причина</b>:'.$reazon,
									"tip"      => "СобытиеCRM",
									"identity" => $identity
								] );
								$hgood++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

							if ( $person_send == "yes" ) {

								$resultp = $db -> query( "SELECT * FROM ".$sqlname."personcat WHERE clid = '".$data['clid']."' and identity = '$identity'" );
								while ($datap = $db -> fetch( $resultp )) {

									try {

										$db -> query( "UPDATE ".$sqlname."personcat SET iduser = '$newuser' WHERE pid = '".$datap['pid']."' and identity = '$identity'" );
										$pgood++;

									}
									catch ( Exception $e ) {

										$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

									}

									//внесем запись в историю Персоны
									try {

										$hid = addHistorty( [
											"iduser"   => $iduser1,
											"pid"      => $datap['pid'],
											"datum"    => current_datumtime(),
											"des"      => 'Передача с Клиентом. <b>Причина</b>: '.$reazon,
											"tip"      => "СобытиеCRM",
											"identity" => $identity
										] );
										$hgood++;

									}
									catch ( Exception $e ) {

										$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

									}

								}

							}
							if ( $dog_send == "yes" ) {

								$resultd = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE clid = '".$data['clid']."' and identity = '$identity'" );
								while ($datad = $db -> fetch( $resultd )) {

									try {

										$db -> query( "UPDATE ".$sqlname."dogovor SET iduser = '$newuser' WHERE did = '".$datad['did']."' and close != 'yes' and identity = '$identity'" );
										$dgood++;

										if ( $credit_send == 'yes' ) {
											$db -> query( "UPDATE ".$sqlname."credit SET iduser = '$newuser' WHERE did = '".$datad['did']."' and do != 'on' and identity = '$identity'" );
										}

										//передадим напоминания
										if ( $_REQUEST['todo_send'] == 'yes' ) {
											$db -> query( "UPDATE ".$sqlname."tasks set iduser = '$newuser' WHERE did = '".$datad['did']."' and iduser = '$olduser'" );
										}

									}
									catch ( Exception $e ) {

										$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

									}

									//внесем запись в историю Сделки
									try {

										$hid = addHistorty( [
											"iduser"   => $iduser1,
											"did"      => $datad['did'],
											"datum"    => current_datumtime(),
											"des"      => 'Передача с Клиентом. <b>Причина</b>: '.$reazon,
											"tip"      => "СобытиеCRM",
											"identity" => $identity
										] );
										$hgood++;

									}
									catch ( Exception $e ) {

										$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

									}

								}

							}

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

				$msg[] = sendMassNotify( "send_client", $params = [
					"clients" => $clients,
					"notice"  => "yes",
					"iduser"  => $newuser,
					"comment" => $reazon
				] );

				event ::fire( 'client.change.user', $args = [
					"info"    => $clients,
					"autor"   => $iduser1,
					"newuser" => $newuser,
					"comment" => $reazon
				] );

			break;
			case 'dostupChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = $isadmin == 'on' ? 'yes' : get_accesse( (int)$data['clid'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 || $isadmin == 'on' ) {

							$dostup = $db -> getCol( "SELECT iduser FROM ".$sqlname."dostup WHERE clid = '".$data['clid']."' and identity = '$identity'" );

							if ( !in_array( $data['clid'], (array)$dostup ) ) {

								$db -> query( "INSERT INTO ".$sqlname."dostup SET ?u", [
									'clid'     => $data['clid'],
									'iduser'   => $duser,
									'identity' => $identity
								] );
								$good++;

							}

							if ( $dog_send == "yes" ) {

								$resultp = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE clid='".$data['clid']."' and identity = '$identity'" );
								while ($datap = $db -> fetch( $resultp )) {

									$dostup = $db -> getCol( "SELECT iduser FROM ".$sqlname."dostup WHERE did = '".$datap['did']."' and identity = '$identity'" );

									if ( !in_array( $datap['did'], (array)$dostup ) ) {

										$db -> query( "INSERT INTO ".$sqlname."dostup SET ?u", [
											'did'      => $datap['did'],
											'iduser'   => $duser,
											'identity' => $identity
										] );
										$good++;

									}

								}

							}

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'dostupDelete':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = $isadmin == 'on' ? 'yes' : get_accesse( (int)$data['clid'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 || $isadmin == 'on' ) {

							$db -> query( "DELETE FROM ".$sqlname."dostup WHERE clid = '".$data['clid']."' AND iduser = '$duser'" );
							$good++;

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'terChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = $isadmin == 'on' ? 'yes' : get_accesse( (int)$data['clid']);

					if ( $acc == "yes" ) {

						if ( $total < 1000 || $isadmin == 'on' ) {

							$db -> query( "UPDATE ".$sqlname."clientcat SET ?u WHERE clid = '".$data['clid']."' and identity = '$identity'", ['territory' => $nterritory] );
							$good++;

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'cmrChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = $isadmin == 'on' ? 'yes' : get_accesse( (int)$data['clid'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 || $isadmin == 'on' ) {

							$db -> query( "UPDATE ".$sqlname."clientcat SET ?u WHERE clid = '".$data['clid']."' and identity = '$identity'", ['tip_cmr' => $tipcmr] );
							$good++;

						}

					}
					else $noac++;

					$total++;

				}

			break;
			case 'groupChange':

				$service = $db -> getOne( "SELECT service FROM ".$sqlname."group WHERE id = '$newgid' and identity = '$identity'" );

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( $total < 1000 ) {

						$id = 0;

						$user_email = yexplode( ";", $data['mail_url'], 0 );
						$user_name  = $data['title'];

						//проверим - есть ли подписчик в группе, в которую копируем
						if ( $service != '' ) {
							$id = $db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE user_email = '$user_email' and gid = '$newgid' and identity = '$identity'" );
						}
						else {
							$id = $db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE clid = '".$data['clid']."' and gid = '$newgid' and identity = '$identity'" ) + 0;
						}

						if ( $id < 1 ) {

							$db -> query( "INSERT INTO ".$sqlname."grouplist SET ?u", arrayNullClean( [
								'gid'        => $newgid + 0,
								'clid'       => $data['clid'] + 0,
								'service'    => $service,
								'user_name'  => $user_name,
								'user_email' => $user_email,
								'tags'       => ($tags != '') ? $tags : ' ',
								'identity'   => $identity
							] ) );

							$good++;

						}

						$total++;

					}

				}

			break;
			case 'clientTrash':

				$result = $db -> query( $query );
				$count  = $db -> affectedRows();
				while ($data = $db -> fetch( $result )) {

					$acc = get_accesse( (int)$data['clid'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 ) {

							$db -> query( "UPDATE ".$sqlname."clientcat SET ?u WHERE clid = '".$data['clid']."' and identity = '$identity'", ['iduser' => '0', 'trash' => 'yes'] );
							$good++;

							if ( $count <= 100 ) {
								$hid = addHistorty( [
									"iduser"   => $iduser1,
									"clid"     => $data['clid'],
									"datum"    => current_datumtime(),
									"des"      => 'Перемещение в корзину. Причина: '.$reazon,
									"tip"      => "СобытиеCRM",
									"identity" => $identity
								] );
							}

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'clientDelete':

				$p   = 0;
				$pd  = 0;
				$h   = 0;
				$t   = 0;
				$mes = '';
				$di  = 0;

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = get_accesse( (int)$data['clid'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 ) {

							$dogs = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE clid='".$data['clid']."' and identity = '$identity'" );

							if ( $dogs == 0 ) {

								//Удалим привязки персон к данной организации
								if ( $person_send != "yes" ) {

									$db -> query( "UPDATE ".$sqlname."personcat SET clid = '' WHERE clid='".$data['clid']."' and identity = '$identity'" );
									$p = $db -> affectedRows();

								}
								if ( $person_send == "yes" ) {

									//Удалим всю историю переговоров
									$db -> query( "delete from ".$sqlname."history where pid IN (select pid from ".$sqlname."personcat WHERE clid='".$data['clid']."' and identity = '$identity') and identity = '$identity'" );
									$h = $db -> affectedRows();

									$db -> query( "delete from ".$sqlname."personcat where clid='".$data['clid']."' and identity = '$identity'" );
									$pd = $db -> affectedRows();

								}

								//Удалим всю историю переговоров
								$db -> query( "delete from ".$sqlname."history where clid='".$data['clid']."' and identity = '$identity'" );
								$h += $db -> affectedRows();

								//Удалим все напоминания
								$db -> query( "delete from ".$sqlname."tasks where clid='".$data['clid']."' and identity = '$identity'" );
								$t = $db -> affectedRows();

								//Удалим всю связанные файлы
								$re = $db -> query( "select * from ".$sqlname."file WHERE clid='".$data['clid']."' and identity = '$identity'" );
								while ($da = $db -> fetch( $re )) {

									@unlink( $rootpath."/files/".$fpath.$da['fname'] );
									$db -> query( "delete from ".$sqlname."file where fid = '".$da['fid']."' and identity = '$identity'" );
									$f++;

								}

								$db -> query( "delete from ".$sqlname."clientcat where clid = '".$data['clid']."' and identity = '$identity'" );
								$good++;

							}
							else {

								$di++;
								$err[] = 1;

							}

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

				if ( $di > 0 ) {
					$msg[] = 'У <b>'.$di.'</b> записей есть сделки. ';
				}
				if ( $p > 0 ) {
					$msg[] = 'Обновлено <b>'.$p.'</b> Контактов - снята привязка к Клиенту';
				}
				if ( $pd > 0 ) {
					$msg[] = 'Удалено <b>'.$p.' Контактов</b>';
				}
				if ( $f > 0 ) {
					$msg[] = '<br>Так же удалено <b>'.$f.'</b> файлов';
				}

			break;

		}

	}
	if ( $isSelect == 'doSelected' ) {

		//произведем действия
		switch ($doAction) {

			case 'userChange':

				$clients[] = [];

				foreach ( $ids as $clid ) {

					if ( get_accesse( (int)$clid ) == "yes" && (int)$clid > 0 ) {

						$olduser = getClientData( (int)$clid, 'iduser' );

						try {

							$db -> query( "UPDATE ".$sqlname."clientcat SET iduser = '$newuser', trash = 'no' WHERE clid = '$clid' and identity = '$identity'" );
							$good++;

							$clients[] = [
								"clid"    => $clid,
								"title"   => current_client( $clid ),
								"olduser" => $olduser
							];

							//передадим напоминания
							if ( $_REQUEST['todo_send'] == 'yes' ) {
								$db -> query( "UPDATE ".$sqlname."tasks SET iduser = '$newuser' WHERE clid = '$clid' and iduser = '$olduser'" );
							}

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

						//внесем запись в историю Организации
						$current_user = current_user( get_userid( 'clid', $clid ) );
						if ( $current_user == '' ) {
							$current_user = "Свободный клиент";
						}

						try {

							$hid = addHistorty( [
								"iduser"   => $iduser1,
								"clid"     => $clid,
								"datum"    => current_datumtime(),
								"des"      => "Передача Клиента от $current_user. <b>Причина</b>: $reazon",
								"tip"      => "СобытиеCRM",
								"identity" => $identity
							] );

							$hgood++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

						if ( $person_send = "yes" ) {

							$resultp = $db -> query( "SELECT * FROM ".$sqlname."personcat WHERE clid = '$clid' and identity = '$identity'" );
							while ($datap = $db -> fetch( $resultp )) {

								try {

									$db -> query( "UPDATE ".$sqlname."personcat SET iduser = '$newuser' WHERE pid = '".$datap['pid']."' and identity = '$identity'" );
									$pgood++;

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

								//внесем запись в историю Персоны
								try {

									$hid = addHistorty( [
										"iduser"   => $iduser1,
										"pid"      => $datap['pid'],
										"datum"    => current_datumtime(),
										"des"      => "Передача с Клиентом от $current_user. <b>Причина</b>: $reazon",
										"tip"      => "СобытиеCRM",
										"identity" => $identity
									] );

									$hgood++;

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

						}

						if ( $dog_send = "yes" ) {

							$resultd = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE clid = '$clid' and identity = '$identity'" );
							while ($datad = $db -> fetch( $resultd )) {

								try {

									$db -> query( "UPDATE ".$sqlname."dogovor SET iduser = '$newuser' WHERE did = '".$datad['did']."' and close != 'yes' and identity = '$identity'" );
									$dgood++;

									//передадим напоминания
									if ( $_REQUEST['todo_send'] == 'yes' ) {
										$db -> query( "UPDATE ".$sqlname."tasks SET iduser = '$newuser' WHERE did = '$datad[did]' and iduser = '$olduser'" );
									}

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

								//внесем запись в историю Сделки
								try {

									$hid = addHistorty( [
										"iduser"   => $iduser1,
										"did"      => $datad['did'],
										"datum"    => current_datumtime(),
										"des"      => "Передача с Клиентом от $current_user. <b>Причина</b>: $reazon",
										"tip"      => "СобытиеCRM",
										"identity" => $identity
									] );

									$hgood++;

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

						}

					}
					else {
						$noac++;
					}

				}

				$msg[] = sendMassNotify( "send_client", $params = [
					"clients" => $clients,
					"notice"  => "yes",
					"iduser"  => $newuser,
					"comment" => $reazon
				] );

				event ::fire( 'client.change.user', $args = [
					"info"    => $clients,
					"autor"   => $iduser1,
					"newuser" => $newuser,
					"comment" => $reazon
				] );

			break;
			case 'dostupChange':

				foreach ( $ids as $clid ) {

					if ( get_accesse( (int)$clid ) == "yes" && (int)$clid > 0 ) {

						$dostup = $db -> getCol( "SELECT iduser FROM ".$sqlname."dostup WHERE clid = '$clid' and identity = '$identity'" );

						if ( !in_array( $clid, (array)$dostup ) ) {

							try {

								$db -> query( "INSERT INTO ".$sqlname."dostup SET ?u", arrayNullClean( [
									'clid'     => $clid,
									'iduser'   => $duser,
									'identity' => $identity
								] ) );
								$good++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}
						if ( $dog_send = "yes" ) {

							$resultp = $db -> query( "SELECT * FROM ".$sqlname."dogovor WHERE clid = '$clid' and identity = '$identity'" );
							while ($datap = $db -> fetch( $resultp )) {

								$dostup = $db -> getCol( "SELECT iduser FROM ".$sqlname."dostup WHERE did = '".$datap['did']."' and identity = '$identity'" );

								if ( !in_array( $datap['did'], (array)$dostup ) ) {

									try {

										$db -> query( "INSERT INTO ".$sqlname."dostup SET ?u", arrayNullClean( [
											'did'      => $datap['did'],
											'iduser'   => $duser,
											'identity' => $identity
										] ) );
										$good++;

									}
									catch ( Exception $e ) {

										$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

									}

								}

							}

						}

					}
					else {
						$noac++;
					}

				}

			break;
			case 'dostupDelete':

				foreach ( $ids as $clid ) {

					if ( ($isadmin == 'on' || get_accesse( (int)$clid ) == "yes") && (int)$clid > 0 ) {

						$dostup = $db -> getCol( "SELECT iduser FROM ".$sqlname."dostup WHERE clid = '$clid' and identity = '$identity'" );

						if ( !in_array( $clid, (array)$dostup ) ) {

							try {

								$db -> query( "DELETE FROM ".$sqlname."dostup WHERE clid = '$clid' AND iduser = '$duser'" );
								$good++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}
					else {
						$noac++;
					}

				}

			break;
			case 'terChange':

				foreach ( $ids as $clid ) {

					if ( get_accesse( (int)$clid ) == "yes" ) {

						try {

							$db -> query( "UPDATE ".$sqlname."clientcat SET territory = '$nterritory' WHERE clid = '$clid' and identity = '$identity'" );
							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else {
						$noac++;
					}

				}

			break;
			case 'cmrChange':

				foreach ( $ids as $clid ) {

					if ( get_accesse( (int)$clid ) == "yes" && (int)$clid > 0 ) {

						try {

							$db -> query( "UPDATE ".$sqlname."clientcat SET tip_cmr = '$tipcmr' WHERE clid = '$clid' and identity = '$identity'" );
							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else {
						$noac++;
					}

				}

			break;
			case 'groupChange':

				$service = $db -> getOne( "SELECT service FROM ".$sqlname."group WHERE id = '$newgid' and identity = '$identity'" );

				foreach ( $ids as $clid ) {

					$resultg1   = $db -> getRow( "SELECT mail_url, title FROM ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
					$user_email = yexplode( ",", str_replace( ";", ",", $resultg1["mail_url"] ), 0 );
					$user_name  = $resultg1['title'];

					$gid = 0;

					//проверим - есть ли подписчик в группе, в которую копируем
					if ( $service != '' ) {

						$gid = $db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE user_email = '$user_email' and gid = '$newgid' and identity = '$identity'" );

					}
					else {

						$gid = $db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE clid = '$clid' and gid = '$newgid' and identity = '$identity'" );

					}

					if ( (int)$gid == 0 ) {

						try {

							$db -> query( "INSERT INTO ".$sqlname."grouplist SET ?u", arrayNullClean( [
								'gid'        => $newgid,
								'clid'       => $clid,
								'service'    => $service,
								'user_name'  => $user_name,
								'user_email' => $user_email,
								'tags'       => $tags,
								'identity'   => $identity
							] ) );

							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else {
						$noac++;
					}

				}

			break;
			case 'clientTrash':

				foreach ( $ids as $clid ) {

					if ( get_accesse( (int)$clid ) == "yes" && (int)$clid > 0 ) {

						try {

							$db -> query( "UPDATE ".$sqlname."clientcat SET iduser = '0', trash = 'yes' WHERE clid = '$clid' and identity = '$identity'" );
							$good++;

							$hid = addHistorty( [
								"iduser"   => $iduser1,
								"clid"     => $clid,
								"datum"    => current_datumtime(),
								"des"      => 'Перемещение в корзину. Причина:'.$reazon,
								"tip"      => "СобытиеCRM",
								"identity" => $identity
							] );

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else $noac++;

				}

			break;
			case 'clientDelete':

				$p   = 0;
				$pd  = 0;
				$h   = 0;
				$t   = 0;
				$mes = '';

				foreach ( $ids as $clid ) {

					if ( get_accesse( (int)$clid ) == "yes" ) {

						$dogs = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."dogovor WHERE clid = '$clid' and identity = '$identity'" );

						if ( $dogs == 0 ) {

							//Удалим привязки персон к данной организации
							if ( $person_send != "yes" ) {

								$db -> query( "UPDATE ".$sqlname."personcat SET clid = '' WHERE clid = '$clid' and identity = '$identity'" );
								$p = $db -> affectedRows();

							}
							if ( $person_send == "yes" ) {

								//Удалим всю историю переговоров
								$db -> query( "DELETE FROM ".$sqlname."history WHERE pid IN (SELECT pid FROM ".$sqlname."personcat WHERE clid = '$clid' and identity = '$identity') and identity = '$identity'" );
								$h = $db -> affectedRows();

								$db -> query( "DELETE FROM ".$sqlname."personcat WHERE clid = '$clid' and identity = '$identity'" );
								$pd = $db -> affectedRows();

							}

							//Удалим всю историю переговоров
							$db -> query( "DELETE FROM ".$sqlname."history WHERE clid = '$clid' and identity = '$identity'" );
							$h += $db -> affectedRows();

							//Удалим все напоминания
							$db -> query( "DELETE FROM ".$sqlname."tasks WHERE clid = '$clid' and identity = '$identity'" );
							$t = $db -> affectedRows();

							//Удалим всю связанные файлы
							$re = $db -> query( "SELECT * FROM ".$sqlname."file WHERE clid = '$clid' and identity = '$identity'" );
							while ($da = $db -> fetch( $re )) {

								@unlink( "../../files/".$fpath.$da['fname'] );
								$db -> query( "DELETE FROM ".$sqlname."file WHERE fid = '".$da['fid']."' and identity = '$identity'" );
								$f++;

							}

							$db -> query( "DELETE FROM ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
							$good++;

						}
						else {

							$di++;
							$err[] = 1;

						}

					}
					else $noac++;

				}

				if ( $di > 0 )
					$msg[] = 'У <b>'.$di.'</b> записей есть сделки. ';
				if ( $p > 0 )
					$msg[] = 'Обновлено <b>'.$p.'</b> Контактов - снята привязка к Клиенту';
				if ( $pd > 0 )
					$msg[] = 'Удалено <b>'.$p.' Контактов</b>';
				if ( $f > 0 )
					$msg[] = '<br>Так же удалено <b>'.$f.'</b> файлов';

			break;

		}

	}

	$message = [];

	//print $outFunnel."\n";

	if ( $noac > 0 && $doAction != 'groupChange' ) {
		$message[] = 'Нет доступа к <b>'.$noac.'</b> записям';
	}
	elseif ( $doAction == 'groupChange' ) {
		$message[] = 'Уже есть в группе <b>'.$noac.'</b> записей';
	}

	if ( $total >= 1000 ) {
		$message[] = 'Выполненено для '.$good.' записей ( масимальное количество 1000 )';
	}
	else {
		$message[] = "Выполнено для <b>".$good."</b> записей.<br>Ошибок: <b>".count( (array)$err )."</b>";
	}

	print json_encode_cyr( [
		"result" => implode( '<br>', $message )
	] );

	exit();

}

if ( $action == "client.trash" ) {

	$Client  = new Client();
	$cresult = $Client -> actions( $clid, "trash" );

	print 'Результат: '.$cresult['result'];
	if ( $cresult['error'] != '' )
		print "Ошибка: ".$cresult['error'];

	exit();

}
if ( $action == "client.untrash" ) {

	$Client  = new Client();
	$cresult = $Client -> actions( $clid, "untrash" );

	print 'Результат: '.$cresult['result'];
	if ( $cresult['error'] != '' )
		print "Ошибка: ".$cresult['error'];

	exit();

}

if ( $action == "client.cold" ) {

	$Client  = new Client();
	$cresult = $Client -> actions( $clid, "cold" );

	print 'Результат: '.$cresult['result'];
	if ( $cresult['error'] != '' )
		print "Ошибка: ".$cresult['error'];

	exit();

}
if ( $action == "client.uncold" ) {

	$Client  = new Client();
	$cresult = $Client -> actions( $clid, "uncold" );

	print 'Результат: '.$cresult['result'];
	if ( $cresult['error'] != '' )
		print "Ошибка: ".$cresult['error'];

	exit();

}

if ( $action == "client.add_fav" ) {

	$Client  = new Client();
	$cresult = $Client -> actions( $clid, "fav" );

	print 'Результат: '.$cresult['result'];
	if ( $cresult['error'] != '' )
		print "Ошибка: ".$cresult['error'];

	exit();

}
if ( $action == "client.del_fav" ) {

	$Client  = new Client();
	$cresult = $Client -> actions( $clid, "unfav" );

	print 'Результат: '.$cresult['result'];
	if ( $cresult['error'] != '' )
		print "Ошибка: ".$cresult['error'];

	exit();

}

if ( $action == 'client.change.user' ) {

	$clid    = (int)$_REQUEST["clid"];
	$pid     = (int)$_REQUEST["pid"];
	$newUser = (int)$_REQUEST['newuser'];

	$result = [];

	if ( $clid > 0 ) {

		$oldUser = getClientData( $clid, 'iduser' );

		$Client = new Client();
		$result = $Client -> changeUser( $clid, $_REQUEST );

		if ( $_REQUEST['todo_send'] == 'yes' ) {

			$db -> query( "UPDATE ".$sqlname."tasks SET iduser = '$newUser' WHERE iduser = '$oldUser' AND clid = '$clid' AND identity = '$identity'" );

		}

		//$hooks -> do_action( "client_change_user", $_REQUEST );

	}
	if ( $pid > 0 ) {

		$oldUser = getPersonData( $clid, 'iduser' );

		$Person = new Person();
		$result = $Person -> changeUser( $pid, $_REQUEST );

		if ( $_REQUEST['todo_send'] == 'yes' ) {

			$db -> query( "UPDATE ".$sqlname."tasks SET iduser = '$newUser' WHERE iduser = '$oldUser' AND pid = '$pid' AND identity = '$identity'" );

		}

		//$hooks -> do_action( "person_change_user", $_REQUEST );

	}

	print json_encode_cyr( [
		"result" => "Сделано",
		"error"  => implode( "<br>", $result['error'] )
	] );

	exit();

}
if ( $action == 'client.change.recvisites' ) {

	$params['recv'] = $_REQUEST['recv'];

	//Обновим клиента
	$Client = new Client();
	$result = $Client -> update( (int)$clid, $params );

	$r = $result['result'].$result['error']['text'];

	//$hooks -> do_action( "client_change_recvisites", $_REQUEST );

	print json_encode_cyr( [
		"clid"   => "",
		"result" => $r
	] );

	exit();
}
if ( $action == 'client.change.dostup' ) {

	$client = new Client();
	$result = $client -> changeDostup( $clid, $_REQUEST );

	$good = $result['data']['count'];
	$err  = $result['data']['errors'];

	$res = ($result['result'] == 'Ok') ? "Выполнено для $good записей.<br>Ошибок: $err" : "Ошибок: $err";

	//$hooks -> do_action( "client_change_dostup", $_REQUEST );

	print json_encode_cyr( [
		"result" => $res,
		"error"  => implode( "<br>", $result['error']['text'] )
	] );

	exit();

}
if ( $action == 'client.change.relation' ) {

	$params['tip_cmr'] = $_REQUEST["tip_cmr"];
	$params['reazon']  = ($_REQUEST['reason'] != '') ? $_REQUEST['reason'] : 'не указано';

	//Обновим клиента
	$Client = new Client();
	$result = $Client -> changeRelation( $clid, $_REQUEST );

	//$color = "transparent";
	$color = $db -> getOne( "SELECT color FROM ".$sqlname."relations WHERE title='$params[tip_cmr]' and identity = '$identity'" );

	//$hooks -> do_action( "client_change_relation", $_REQUEST );

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => implode( "<br>", $result['error'] )
	] );

	//event ::fire( 'client.change.relation', $args = array("clid" => $clid, "autor" => $iduser1, "user" => $iduser) );

	exit();

}
if ( $action == 'client.change.description' ) {

	$des = $_POST["des"];

	//найдем текущее значения поля Описание
	//$des = getClientData($clid, 'des');

	$params['des'] = "Добавлено: ".current_datumtime()."; Добавил: ".current_user( $iduser1 )."\n".$des."\n\n".getClientData( $clid, 'des' );

	//Обновим клиента
	$Client  = new Client();
	$cresult = $Client -> update( (int)$clid, $params );

	$r = $cresult['result'].$cresult['error']['text'];

	print json_encode_cyr( [
		"result" => $r
	] );

	exit();
}
if ( $action == 'client.change.priceLevel' ) {

	$newParams["priceLevel"] = $_REQUEST["priceLevel"];
	$oldParams["priceLevel"] = $_REQUEST["oldLevel"];

	$reazon = ($_REQUEST['reason'] == '') ? 'не указано' : $_REQUEST['reason'];

	$rez = 'Сделано';

	try {

		$hooks -> do_action( "client_change_priceLevel", $_REQUEST );

		$db -> query( "update ".$sqlname."clientcat set ?u where clid = '".$clid."' and identity = '$identity'", ["priceLevel" => $newParams["priceLevel"]] );

		//внесем запись в историю Персоны
		addHistorty( [
			"clid"     => $clid,
			"datum"    => current_datumtime(),
			"des"      => "Изменен уровень цен: ".strtr( $oldParams['priceLevel'], $fieldsNames['price'] )."&rarr;".strtr( $newParams['priceLevel'], $fieldsNames['price'] ).". Причина: ".$reazon.". Изменил: ".current_user( $iduser1 ),
			"iduser"   => $iduser1,
			"tip"      => 'СобытиеCRM',
			"identity" => $identity
		] );

	}
	catch ( Exception $e ) {

		$rez = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	print json_encode_cyr( [
		"result" => $rez
	] );

	exit();
}

if ( $action == "client.addcategory" ) {

	$tip   = $_REQUEST['tip'];
	$title = $_REQUEST['title'];

	if ( !$tip )
		$tip = 'client';

	$idd = $db -> getOne( "SELECT idcategory FROM ".$sqlname."category WHERE title = '".$title."' and tip = '".$tip."' and identity = '$identity' ORDER BY title" );

	if ( $idd < 1 ) {

		$db -> query( "insert into ".$sqlname."category (idcategory,title,tip,identity) values(null, '".$title."', '".$tip."','$identity')" );
		$id = $db -> insertId();

	}
	else $id = $idd;

	print $id;

	exit();
}
if ( $action == "client.addclientpath" ) {

	$title = $_REQUEST['title'];

	$id = $db -> getOne( "SELECT id FROM ".$sqlname."clientpath WHERE name = '".$title."' and identity = '$identity' ORDER BY name" );

	if ( $id < 1 ) {

		$db -> query( "insert into ".$sqlname."clientpath (id,name,identity) values(null, '".$title."','$identity')" );
		$id = $db -> insertId();

	}

	print $id;

	exit();
}
if ( $action == "client.addterritory" ) {

	$title = $_REQUEST['title'];

	$id = $db -> getOne( "SELECT idcategory FROM ".$sqlname."territory_cat WHERE title = '".$title."' and identity = '$identity' ORDER BY title" ) + 0;

	if ( $id < 1 ) {

		$db -> query( "insert into ".$sqlname."territory_cat (idcategory,title,identity) values(null, '".$title."','$identity')" );
		$id = $db -> insertId();

	}

	print $id;

	exit();
}