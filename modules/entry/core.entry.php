<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */

/* ============================ */

use Salesman\Client;
use Salesman\Deal;
use Salesman\Person;
use Salesman\Todo;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );


$clid   = (int)$_REQUEST['clid'];
$action = $_REQUEST['action'];

if ( $action == 'edit' ) {

	//print_r($_REQUEST);

	$ide    = (int)$_REQUEST['ide'];
	$iduser = (int)$_REQUEST['iduser'];

	$post = $_REQUEST;

	$params['deshist'] = untag( $_REQUEST['content'] );
	$params['tiphist'] = untag( $_REQUEST['tiphist'] );

	$dodog = untag( $_REQUEST['dodog'] );

	$clid = (int)$_REQUEST['client']['clid'];
	unset( $_REQUEST['client']['clid'] );

	$pid = (int)$_REQUEST['person']['pid'];
	unset( $_REQUEST['person']['pid'] );

	$mes        = $err = [];
	$error      = '';
	$hid        = $good = $del = $upd = $did = 0;
	$nameFields = '';
	$dataFields = '';

	/**
	 * КЛИЕНТ
	 */

	$client           = $_REQUEST['client'];
	$client['iduser'] = $iduser;

	/*if ( $clid > 0 )
		$client = $hooks -> apply_filters( "client_editfilter", $client );

	else
		$client = $hooks -> apply_filters( "client_addfilter", $client );*/

	if ( $clid < 1 ) {

		$Client  = new Client();
		$cresult = $Client -> add( $client );

		//$hooks -> do_action( "client_add", $client );

		$clid = $cresult['data'];

	}
	else {

		$client['iduser'] = getClientData( $clid, 'iduser' );

		$Client  = new Client();
		$cresult = $Client -> update( $clid, $client );

		//$hooks -> do_action( "client_edit", $client );

		$hid = $cresult['hid'];

	}

	/**
	 * КОНТАКТ
	 */

	$person               = $_REQUEST['person'];
	$person['clientpath'] = $client['clientpath'];
	$person['iduser']     = $iduser;
	$person['clid']       = $clid;

	/*if ( $pid > 0 )
		$person = $hooks -> apply_filters( "person_editfilter", $person );

	else
		$person = $hooks -> apply_filters( "person_addfilter", $person );*/

	if ( $pid < 1 ) {

		$Person = new Person();
		$result = $Person -> edit( 0, $person );

		$pid   = $result['data'];
		$mes[] = $result['result'];

		//$hooks -> do_action( "person_add", $person );

	}
	else {

		$person['iduser'] = getPersonData( $pid, 'iduser' );

		$Person = new Person();
		$result = $Person -> edit( $pid, $person );

		//$hooks -> do_action( "person_edit", $person );

		$pid   = $result['data'];
		$mes[] = $result['result'];

	}

	/*
	 * Добавим запись в историю для новых записей
	 */
	if ( $ide < 1 && $params['deshist'] != '' ) {

		//добавляем историю активности
		if ( $params['deshist'] == '' ) {

			$params['deshist'] = "Добавлен клиент";
			$params['tiphist'] = "СобытиеCRM";

		}
		else $db -> query( "UPDATE ".$sqlname."clientcat SET last_hist = '".current_datumtime()."' WHERE clid = '$clid' and identity = '$identity'" );

		try {

			$hst = [
				"iduser"   => $iduser1,
				"clid"     => $clid,
				"pid"      => $pid,
				"datum"    => current_datumtime(),
				"des"      => $params['deshist'],
				"tip"      => $params['tiphist'],
				"identity" => $identity
			];

			$hst = $hooks -> apply_filters( "history_addfilter", $hst );

			//запись в историю активности
			$params['hid'] = addHistorty( $hst );

			$hst['сid'] = $params['hid'];

			if ( $hooks )
				$hooks -> do_action( "history_add", $_REQUEST, $hst );

		}
		catch ( Exception $e ) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	//если включена интеграция с телефонией, то проверим номер на наличие в истории звонков и свяжем
	if ( $GLOBALS['sip_active'] == 'yes' ) {

		$income = substr( $_REQUEST['income'], 1 );

		if ( $income != '' ) {

			$res = $db -> getAll( "SELECT id FROM ".$sqlname."callhistory WHERE ((src LIKE '%$income' AND direct = 'income') OR (dst LIKE '%$income' AND direct = 'outcome')) AND identity = '".$identity."'" );
			if ( count( $res ) > 0 ) {
				foreach ( $res as $data ) {

					$db -> query( "UPDATE ".$sqlname."callhistory SET ?u WHERE id = '".$data['id']."' and identity = '$identity'", [
						'clid' => $clid,
						'pid'  => $pid
					] );

				}
			}
		}

	}

	$dspeka = $espeka = $newentry = [];

	/**
	 * Подготовим спецификацию
	 */
	$speca_idp   = $_REQUEST['idp'];
	$speca_prid  = $_REQUEST['prid'];
	$speca_title = $_REQUEST['speca_title'];
	$speca_kol   = $_REQUEST['speca_kol'];
	$speca_price = $_REQUEST['speca_price'];

	for ( $i = 0; $i < count( $speca_title ); $i++ ) {

		if ( $speca_title[ $i ] != '' ) {

			$prid = ($speca_prid[ $i ] < 1) ? $db -> getOne( "SELECT n_id FROM ".$sqlname."price WHERE title = '".untag( $speca_title[ $i ] )."' AND identity = '$identity'" ) : $speca_prid[ $i ];

			$poz = $db -> getRow( "SELECT * FROM ".$sqlname."price WHERE n_id = '$prid' AND identity = '$identity'" );

			//спецификация для обращения
			$espeka[] = [
				"idp"   => (int)$speca_idp[ $i ],
				"prid"  => (int)$prid,
				"title" => untag( $speca_title[ $i ] ),
				"kol"   => untag( $speca_kol[ $i ] ),
				"price" => pre_format( $speca_price[ $i ] )
			];

			$dspeka[] = [
				"prid"     => $prid,
				"artikul"  => untag( $poz['artikul'] ),
				"title"    => untag( $speca_title[ $i ] ),
				"kol"      => untag( $speca_kol[ $i ] ),
				"edizm"    => untag( $poz['edizm'] ),
				"price"    => pre_format( $speca_price[ $i ] ),
				"price_in" => pre_format( $poz['price_in'] ),
				"nds"      => pre_format( $poz['nds'] )
			];

		}

	}

	/*
	 * Добавим/Обновим обращение
	 */
	if ( $ide < 1 ) {

		try {

			$eparams = [
				"clid"     => $clid,
				"pid"      => $pid,
				"datum"    => current_datumtime(),
				"iduser"   => $iduser,
				"autor"    => $iduser1,
				"content"  => untag( $params['deshist'] ),
				"identity" => $identity
			];

			$params = $hooks -> apply_filters( "entry_addfilter", $eparams );

			$db -> query( "INSERT INTO ".$sqlname."entry SET ?u", $eparams );
			$ide = $db -> insertId();

			$eparams['ide'] = $ide;

			$hooks -> do_action( "entry_add", $post, $eparams );

			$mes[] = "Обращение добавлено";

			/*
			 * Добавляем позиции
			 */
			foreach ( $espeka as $value ) {

				$db -> query( "INSERT INTO ".$sqlname."entry_poz SET ?u", [
					"ide"      => $ide,
					"prid"     => $value['prid'],
					"title"    => $value['title'],
					"kol"      => $value['kol'],
					"price"    => pre_format( $value['price'] ),
					"identity" => $identity
				] );
				$good++;

			}

			event ::fire( 'entry.add', $args = [
				"id"     => $ide,
				"clid"   => $clid,
				"pid"    => $pid,
				"did"    => $did,
				"iduser" => $iduser,
				"autor"  => $iduser1
			] );

		}
		catch ( Exception $e ) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
	else {

		//массив idp имеющихся позиций
		$idp_exist = $db -> getCol( "SELECT idp FROM ".$sqlname."entry_poz WHERE ide = '$ide' and identity = '$identity'" );

		try {

			$eparams = [
				"clid"    => $clid,
				"pid"     => $pid,
				"iduser"  => $iduser,
				"content" => $params['deshist']
			];

			$db -> query( "UPDATE ".$sqlname."entry SET ?u WHERE ide = '$ide' and identity = '$identity'", $eparams );

			$eparams['ide'] = $ide;

			$hooks -> do_action( "entry_edit", $post, $eparams );

			$mes[] = "Обращение обновлено";

			/**
			 * Манипуляция со спецификацией
			 */
			foreach ( $espeka as $value ) {

				//если это существующая позиция, то обновим
				if ( $value['idp'] > 0 ) {

					$db -> query( "UPDATE ".$sqlname."entry_poz SET ?u WHERE idp = '$value[idp]'", [
						"prid"  => $value['prid'],
						"title" => $value['title'],
						"kol"   => $value['kol'],
						"price" => pre_format( $value['price'] )
					] );
					$upd++;

				}
				//в противном случае добавим
				else {

					$db -> query( "INSERT INTO ".$sqlname."entry_poz SET ?u", [
						"ide"      => $ide,
						"prid"     => $value['prid'],
						"title"    => $value['title'],
						"kol"      => $value['kol'],
						"price"    => pre_format( $value['price'] ),
						"identity" => $identity
					] );
					$good++;

				}

			}

			//найдем удаленные позиции
			foreach ( $idp_exist as $idp ) {

				if ( !in_array( $idp, $speca_idp ) ) {

					$db -> query( "DELETE FROM ".$sqlname."entry_poz WHERE idp = '$idp'" );
					$del++;

				}

			}

			if ( $params['hid'] < 1 ) {

				try {

					$hst = [
						"iduser"   => $iduser1,
						"clid"     => $clid,
						"pid"      => $pid,
						"datum"    => current_datumtime(),
						"des"      => "Обновлено обращение # $ide : ".untag( $params['deshist'] ),
						"tip"      => 'СобытиеCRM',
						"identity" => $identity
					];

					$params['hid'] = $hid = editHistorty( 0, $hst );

					$hst['сid'] = $params['hid'];

					if ( $hooks ) {
						$hooks -> do_action( "history_add", $_REQUEST, $hst );
					}


				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

		}
		catch ( Exception $e ) {

			$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	/*
	 * Добавим сделку
	 */
	if ( $_REQUEST['ide'] < 1 && $dodog == 'yes' ) {

		$adeal           = $_REQUEST['dogovor'];
		$adeal['clid']   = $clid;
		$adeal['iduser'] = $iduser;
		$adeal['ide']    = $ide;

		if ( count( $dspeka ) > 0 ) {

			$adeal['speka']     = $dspeka;
			$adeal['calculate'] = "yes";

		}


		$deal   = new Deal();
		$result = $deal -> add( $adeal );

		$did = $result['data'];

	}

	/*
	 * Добавим запись в историю
	 */
	if ( $hid < 1 ) {

		//запись в историю активности
		$hid = addHistorty( [
			"iduser"   => $iduser1,
			"clid"     => $clid,
			"pid"      => $pid,
			"did"      => $did,
			"datum"    => current_datumtime(),
			"des"      => "Обращение №".$ide.": ".$params['deshist'],
			"tip"      => $params['tiphist'],
			"identity" => $identity
		] );

	}
	else {

		$db -> query( "UPDATE ".$sqlname."history SET did = '$did' WHERE cid = '$hid' and identity = '$identity'" );

	}

	/*
	 * Добавим напоминание
	 */
	$todo = $_REQUEST['todo'];

	if ( $_REQUEST['ide'] < 1 && $todo['theme'] != '' ) {

		$todo = $hooks -> apply_filters( "task_addfilter", $todo );

		$todo['title'] = untag( $todo['theme'] );
		//$tparam['tip']      = $todo['tip'];
		$todo['des']      = untag( $todo['des'] );
		$todo['datum']    = $todo['datum_task'];
		$todo['totime']   = $todo['totime_task'];
		$touser           = $todo['touser'];
		$todo['priority'] = untag( $todo['priority'] );
		$todo['speed']    = untag( $todo['speed'] );
		//$tparam['alert']    = $todo['alert'];
		//$tparam['readonly'] = $todo['readonly'];
		//$tparam['day']      = $todo['day'];
		$todo['clid']  = $clid;
		$todo['pid']   = $pid;
		$todo['did']   = $did;
		$todo['autor'] = $iduser1;

		if ( isset( $todo['datumtime'] ) ) {

			$todo['datumtime'] = str_replace( [
				"T",
				"Z"
			], [
				" ",
				""
			], $todo['datumtime'] );

			$todo['datum'] = datetime2date( (string)$todo['datumtime'] );
			$todo['totime'] = getTime( (string)$todo['datumtime'] );

		}

		if ( $touser < 1 )
			$touser = $iduser1;

		$t    = new Todo();
		$task = $t -> add( (int)$touser, $todo );

		if ( $task['result'] == 'Success' ) {

			$hooks -> do_action( "task_add", $todo, $_REQUEST );

			$mes[]  = implode( "<br>", $task['text'] );
			$newtid = $task['id'];

		}
		else $err[] = implode( "<br>", $task['notice'] );

	}

	/*
	 * Получаем источник клиента
	 */
	callTrack( '', '', $clid, true );

	if ( $good > 0 )
		$mes[] = "<br>Добавлено позиций ".$good;
	if ( $upd > 0 )
		$mes[] = "<br>Обновлено позиций ".$upd;
	if ( $del > 0 )
		$mes[] = "<br>Удалено позиций ".$del;


	if ( count( $err ) == 0 ) {

		$mes[] = "Сделано. Ошибок нет";

	}
	else $error = "Сделано. Есть ошибки:<br>".implode( "<br>", $err );

	$mess = implode( ", ", $mes );

	print '{"mess":"'.$mess.'","err":"'.$error.'","clid":"'.$clid.'"}';

	exit();

}

if ( $action == 'delete' ) {

	$ide = (int)$_REQUEST['id'];

	if ( $ide > 0 ) {

		$db -> query( "delete from ".$sqlname."entry WHERE ide = '".$ide."' and identity = '$identity'" );
		$db -> query( "delete from ".$sqlname."entry_poz WHERE ide = '".$ide."' and identity = '$identity'" );

		print '{"result":"Готово","error":"","clid":""}';


	}
	else print '{"result":"Ошибка","error":"Не найдено обращение","clid":""}';

	exit();

}
if ( $action == "status" ) {

	//параметры узла
	$id      = (int)$_REQUEST['id'];
	$status  = (int)$_REQUEST['status'];
	$date_do = $_REQUEST['datum_do'];
	$comment = untag( $_REQUEST['content'] );

	$mes = $err = '';

	try {
		$db -> query( "UPDATE ".$sqlname."entry SET ?u WHERE ide = '".$id."' and identity = '$identity'", [
			'datum_do' => $date_do,
			'status'   => $status,
			'content'  => $comment
		] );

		$mes = "Сделано";

		event ::fire( 'entry.status', $args = [
			"id"     => $id,
			"status" => $status,
			"autor"  => $iduser1
		] );

	}
	catch ( Exception $e ) {

		$err = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	print '{"result":"'.$mes.'","error":"'.$err.'"}';

	exit();

}
