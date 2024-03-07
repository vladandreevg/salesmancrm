<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Akt;
use Salesman\Budget;
use Salesman\ControlPoints;
use Salesman\Deal;
use Salesman\Document;
use Salesman\Invoice;
use Salesman\Todo;

error_reporting( E_ERROR );
//ini_set('display_errors', 1);

header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";


$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

/**
 * Действия по сделке. основные
 */

if ( $action == "deal.edit" ) {

	if ( $did > 0 ) {

		if ( !isset( $_REQUEST['calculate'] ) ) {
			$_REQUEST['calculate'] = 'no';
		}

		$deal   = new Deal();
		$result = $deal -> fullupdate( $did, $_REQUEST );

		//print_r($result);

		$mes = $result['result'];

		if ( $result['error']['text'] ) {
			$mes .= "<br>".implode( "<br>", (array)$result['error']['text'] );
		}

		print json_encode_cyr( [
			"did"    => "",
			"result" => $mes
		] );

	}
	else {

		$deal   = new Deal();
		$result = $deal -> add( $_REQUEST );

		$mes = $result['result'];

		if ( $result['error']['text'] ) {
			$mes .= "<br>".implode( "<br>", $result['error']['text'] );
		}

		print json_encode_cyr( [
			"did"     => $result['data'],
			"result"  => $mes,
			"message" => $result['error']
		] );

	}

	exit();

}

if ( $action == "deal.delete" ) {

	$deal   = new Deal();
	$result = $deal -> delete( $did );

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['code']." - ".$result['error']['text']
	] );

	exit();

}

if ( $action == "deal.restore" ) {

	$deal   = new Deal();
	$result = $deal -> changeUnclose( $did );

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['text']
	] );

	exit();
}

if ( $action == "deal.freeze" ) {

	$deal   = new Deal();
	$result = $deal -> changeFreeze( $did, $_REQUEST['date'] );

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['text']
	] );

	exit();

}

if ( $action == "deal.mass" ) {

	set_time_limit( 0 );

	$ids         = yexplode( ",", $_REQUEST['ids'] );
	$doAction    = $_REQUEST['doAction'];
	$isSelect    = $_REQUEST['isSelect'];
	$newuser     = $_REQUEST['newuser'];
	$duser       = $_REQUEST['duser'];
	$reazon      = $_REQUEST['reazon'];
	$newStepID   = $_REQUEST['nstep'];
	$datum_plan  = $_REQUEST['datum_plan'];
	$credit_send = $_REQUEST['credit_send'];

	$msg       = [];
	$err       = [];
	$good      = 0;
	$pgood     = 0;
	$dgood     = 0;
	$hgood     = 0;
	$noac      = 0;
	$outFunnel = 0;

	if ( $isSelect == 'doAll' ) {

		$iduser      = (int)$_REQUEST['iduser'];
		$idcategory  = (int)$_REQUEST['idcategory'];
		$word        = $_REQUEST['word'];
		$tbl_list    = $_REQUEST['tbl_list'];
		$tid         = (int)$_REQUEST['tid'];
		$tar         = $_REQUEST['list'];
		$direction   = (int)$_REQUEST['direction'];
		$isOld       = $_REQUEST['isOld'];
		$haveCredit  = $_REQUEST['haveCredit'];
		$haveHistory = $_REQUEST['haveHistory'];
		$haveTask    = $_REQUEST['haveTask'];
		$ord         = $_REQUEST['ord'];
		$tuda        = $_REQUEST['tuda'];

		if ( $ord == '' ) {
			$ord = "datum_plan";
		}
		elseif ( $ord == 'dcreate' ) {
			$ord = 'datum';
		}
		elseif ( $ord == 'datum_plan' ) {
			$ord = 'datum_plan';
		}
		elseif ( $ord == 'dplan' ) {
			$ord = 'datum_plan';
		}
		elseif ( $ord == 'status' ) {
			$ord = 'idcategory';
		}
		elseif ( $ord == 'user' ) {
			$ord = 'iduser';
		}
		elseif ( $ord == 'marg' ) {
			$ord = 'marga';
		}
		elseif ( $ord == 'history' ) {
			$ord = 'last_hist';
		}
		elseif ( $ord == 'last_hist' ) {
			$ord = 'last_history';
		}

		if ( $ord == 'idcategory' ) {
			$ord2 = $sqlname."dogcategory.title";
		}
		elseif ( $ord == 'client' ) {
			$ord2 = $sqlname."clientcat.title";
		}
		elseif ( $ord == 'iduser' ) {
			$ord2 = $sqlname."user.title";
		}
		elseif ( $ord == 'last_hist' ) {
			$ord2 = 'last_history';
		}
		elseif ( $ord == 'last_history' ) {
			$ord2 = 'last_history';
		}
		else {
			$ord2 = $sqlname."dogovor.".$ord;
		}

		$query = getFilterQuery( 'dogovor', [
			'iduser'      => $iduser,
			'word'        => $word,
			'tbl_list'    => $tbl_list,
			'filter'      => $tar,
			'idcategory'  => $idcategory,
			'tid'         => $tid,
			'direction'   => $direction,
			'isOld'       => $isOld,
			'haveCredit'  => $haveCredit,
			'haveHistory' => $haveHistory,
			'haveTask'    => $haveTask,
			'dostup'      => $doAction == 'dostupDelete' ? $duser : NULL,
			'fields'      => [
				'title',
				'idcategory',
				'direction',
				'tip'
			]
		], false );

		$total = 0;

		$query .= " ORDER BY $ord2 $tuda";

		//произведем действия
		switch ($doAction) {

			case 'userChange':

				$dogovor = [];

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( get_accesse( 0, 0, (int)$data['did'] ) == "yes" ) {

						if ( $total < 500 ) {

							$olduser = getDogData( $data['did'], 'iduser' );

							try {

								$db -> query( "UPDATE {$sqlname}dogovor SET iduser = '$newuser' WHERE did = '".$data['did']."' and identity = '$identity'" );

								$good++;

								$dogovor[] = [
									"did"     => $data['did'],
									"title"   => $data['title'],
									"olduser" => $olduser
								];

								if ( $credit_send == 'yes' ) {

									$db -> query( "UPDATE {$sqlname}credit SET iduser = '$newuser' WHERE did = '".$data['did']."' and do != 'on' and identity = '$identity'" );

								}

								//отметим ответственного за сделку в счете
								$db -> query( "UPDATE {$sqlname}credit SET idowner = '$newuser' WHERE did = '".$data['did']."' and do != 'on' and identity = '$identity'" );

								//передадим напоминания
								if ( $_REQUEST['todo_send'] == 'yes' ) {
									$db -> query( "UPDATE {$sqlname}tasks set iduser = '$newuser' WHERE did = '".$data['did']."' and iduser = '$olduser'" );
								}

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

							//внесем запись в историю Организации
							$current_user = current_user( get_userid( 'did', $data['did'] ) );
							if ( $current_user == '' ) {
								$current_user = "Свободный Контакт";
							}

							try {

								//$db -> query("insert into {$sqlname}history (cid, did, datum, des, iduser, tip,identity) values(null, '".$data['did']."', '".current_datumtime()."', 'Передача Сделки от ".$current_user.". <b>Причина</b>:".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')");

								$hid = addHistorty( [
									"iduser"   => $iduser1,
									"did"      => $data['did'],
									"datum"    => current_datumtime(),
									"des"      => 'Передача Сделки от '.$current_user.'. <b>Причина</b>:'.$reazon,
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
					else {
						$noac++;
					}

					$total++;

				}

				$msg[] = sendMassNotify( "send_dog", $params = [
					"dogovor" => $dogovor,
					"notice"  => "yes",
					"iduser"  => $newuser,
					"comment" => $reazon
				] );

				event ::fire( 'deal.change.user', $args = [
					"info"    => $dogovor,
					"autor"   => $iduser1,
					"newuser" => $newuser,
					"comment" => $reazon
				] );

			break;
			case 'dostupChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$acc = $isadmin == 'on' ? 'yes' : get_accesse( 0, 0, (int)$data['did'] );

					if ( $acc == "yes" ) {

						if ( $total < 500 || $isadmin == 'on' ) {

							$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '".$data['did']."' and identity = '$identity'" );

							if ( !in_array( $data['did'], (array)$dostup ) ) {

								//$db -> query("insert into {$sqlname}dostup (id,did,iduser,identity) values (null,'".$data['did']."','".$duser."','$identity')");
								$db -> query( "INSERT INTO {$sqlname}dostup SET ?u", [
									'did'      => $data['did'],
									'iduser'   => $duser,
									'identity' => $identity
								] );
								$good++;

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

					$acc = $isadmin == 'on' ? 'yes' : get_accesse( 0, 0, (int)$data['did'] );

					if ( $acc == "yes" ) {

						if ( $total < 1000 || $isadmin == 'on' ) {

							$db -> query( "DELETE FROM {$sqlname}dostup WHERE did = '".$data['did']."' AND iduser = '$duser'" );
							$good++;

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'datumChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$current_datum = $db -> getOne( "SELECT datum_plan FROM {$sqlname}dogovor WHERE did = '".$data['did']."' and identity = '$identity'" );

					if ( get_accesse( 0, 0, (int)$data['did'] ) == "yes" ) {

						if ( $total < 500 ) {

							try {

								$db -> query( "update {$sqlname}dogovor set datum_plan = '".$datum_plan."' where did = '".$data['did']."' and identity = '$identity'" );
								$good++;

								//$db -> query("insert into {$sqlname}history (cid, did, datum, des, iduser, tip,identity) values(null, '".$data['did']."', '".current_datumtime()."', 'изменение плановой даты Сделки с ".$current_datum." на ".$datum_plan.". <b>Причина</b>:".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')");
								$hid = addHistorty( [
									"iduser"   => $iduser1,
									"did"      => $data['did'],
									"datum"    => current_datumtime(),
									"des"      => "изменение плановой даты Сделки с ".$current_datum." на ".$datum_plan.". <b>Причина</b>:".$reazon,
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
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'stepChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					$msg = [];

					$acc = get_accesse( 0, 0, (int)$data['did'] );

					//print $acc." : ".$data['did']."\n";

					if ( $acc == "yes" ) {

						if ( $total < 500 ) {

							//$oldstep = current_dogstepname(current_dogstepid($data['did']));

							$tip       = getDogData( $data['did'], 'tip' );
							$direction = getDogData( $data['did'], 'direction' );

							//проверим этап, и если его нет в текущей воронке установим ближайший меньший
							$mFunnel       = getMultiStepList( [
								"direction" => $direction,
								"tip"       => $tip
							] );
							$steps         = array_keys( $mFunnel['steps'] );
							$currentStepID = current_dogstepid( $data['did'] );
							$currentStep   = current_dogstepname( $currentStepID );
							$newStep       = current_dogstepname( $newStepID );

							//print_r($steps);

							//если этапа нет в новой цепочке
							/*if(!in_array(current_dogstepid($data['did']), $steps)) {

								$newStepID = array_shift($steps);
								$newStep = current_dogstepname($newStepID);

								//вычисляем ближайший меньший
								foreach ($steps as $k => $v) {

									if(current_dogstepname($k) < $currentStep){
										$newStepID = $k;
										$newStep = current_dogstepname($k);
									}

								}

							}*/

							//проводим только в случае, если в воронке есть новый этап или если воронка не задана
							if ( in_array( $newStepID, (array)$steps ) || count( (array)$steps ) == 0 ) {

								try {

									//print $newStepID."\n";

									$db -> query( "update {$sqlname}dogovor set idcategory = '".$newStepID."' where did = '".$data['did']."' and identity = '$identity'" );
									$good++;

									DealStepLog( $data['did'], $newStepID );

									//$new = current_dogstepname($nstep);
									$msg[] = 'Этап сделки изменен на '.$newStep.'%. Предыдущий этап '.$currentStep.'%.';

									if ( $reazon != '' ) {
										$msg[] = ' <b>Примечание</b>: '.$reazon;
									}

									//$db -> query("insert into {$sqlname}history (cid,iduser,did,datum,des,tip,identity) values(null, '".$iduser1."', '".$data['did']."', '".current_datumtime()."', '".implode("<br>", $msg)."', 'СобытиеCRM','$identity')");
									$hid = addHistorty( [
										"iduser"   => $iduser1,
										"did"      => $data['did'],
										"datum"    => current_datumtime(),
										"des"      => implode( "<br>", $msg ),
										"tip"      => "СобытиеCRM",
										"identity" => $identity
									] );

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}
							else {
								$outFunnel++;
							}

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;

		}

	}
	if ( $isSelect == 'doSelected' ) {

		//произведем действия
		switch ($doAction) {

			case 'userChange':

				$dogovor = [];

				foreach ( $ids as $did ) {

					if ( get_accesse( 0, 0, (int)$did ) == "yes" && (int)$did > 0 ) {

						$current_user = current_user( get_userid( 'did', $did ) );
						$olduser      = getDogData( $did, 'iduser' );

						try {

							$db -> query( "UPDATE {$sqlname}dogovor SET iduser = '$newuser' WHERE did = '$did' and identity = '$identity'" );
							$good++;

							$dogovor[] = [
								"did"     => $did,
								"title"   => current_dogovor( $did ),
								"olduser" => $olduser
							];

							if ( $credit_send == 'yes' ) {

								$db -> query( "UPDATE {$sqlname}credit SET iduser = '$newuser' WHERE did = '$did' and do != 'on' and identity = '$identity'" );

							}

							//передадим напоминания
							if ( $_REQUEST['todo_send'] == 'yes' ) {
								$db -> query( "UPDATE {$sqlname}tasks set iduser = '$newuser' WHERE did = '$did' and iduser = '$olduser'" );
							}

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

						//внесем запись в историю Организации
						$current_user = current_user( get_userid( 'did', $did ) );
						if ( $current_user == '' ) {
							$current_user = "Свободный клиент";
						}

						try {

							//$db -> query("insert into {$sqlname}history (cid, did, datum, des, iduser, tip,identity) values(null, '".$ids[ $i ]."', '".current_datumtime()."', 'Передача Сделки от ".$current_user.". <b>Причина</b>:".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')");

							$hid = addHistorty( [
								"iduser"   => $iduser1,
								"did"      => $did,
								"datum"    => current_datumtime(),
								"des"      => 'Передача Сделки от '.$current_user.'. <b>Причина</b>:'.$reazon,
								"tip"      => "СобытиеCRM",
								"identity" => $identity
							] );

							$hgood++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else {
						$noac++;
					}
				}

				$msg[] = sendMassNotify( "send_dog", $params = [
					"dogovor" => $dogovor,
					"notice"  => "yes",
					"iduser"  => $newuser,
					"comment" => $reazon
				] );

				event ::fire( 'deal.change.user', $args = [
					"info"    => $dogovor,
					"autor"   => $iduser1,
					"newuser" => $newuser,
					"comment" => $reazon
				] );

			break;
			case 'dostupChange':

				foreach ( $ids as $did ) {

					if ( get_accesse( 0, 0, (int)$did ) == "yes" && (int)$did > 0 ) {

						$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$did' and identity = '$identity'" );

						if ( !in_array( $did, (array)$dostup ) ) {

							try {

								//$db -> query("insert into {$sqlname}dostup (id,did,iduser,identity) values (null,'$did','".$duser."','$identity')");

								$db -> query( "INSERT INTO {$sqlname}dostup SET ?u", arrayNullClean( [
									'did'      => $did,
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
					else {
						$noac++;
					}

				}

			break;
			case 'dostupDelete':

				foreach ( $ids as $did ) {

					if ( get_accesse( 0, 0, (int)$did ) == "yes" && (int)$did > 0 ) {

						$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$did' and identity = '$identity'" );

						if ( !in_array( $did, (array)$dostup ) ) {

							try {

								$db -> query( "DELETE FROM {$sqlname}dostup WHERE did = '$did' AND iduser = '$duser'" );
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
			case 'datumChange':

				foreach ( $ids as $did ) {

					if ( get_accesse( 0, 0, (int)$did ) == "yes" && (int)$did > 0 ) {

						$current_datum = $db -> getOne( "SELECT datum_plan FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );

						try {

							$db -> query( "UPDATE {$sqlname}dogovor SET datum_plan = '$datum_plan' WHERE did = '$did' and identity = '$identity'" );
							$good++;

							//$db -> query("insert into {$sqlname}history (cid, did, datum, des, iduser, tip,identity) values(null, '".$did."', '".current_datumtime()."', 'изменение плановой даты Сделки с ".$current_datum." на ".$datum_plan.". <b>Причина</b>:".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')");

							$hid = addHistorty( [
								"iduser"   => $iduser1,
								"did"      => $did,
								"datum"    => current_datumtime(),
								"des"      => "Изменение плановой даты Сделки с $current_datum на $datum_plan. <b>Причина</b>: $reazon",
								"tip"      => "СобытиеCRM",
								"identity" => $identity
							] );

							$hgood++;

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
			case 'stepChange':

				foreach ( $ids as $did ) {

					$acc = get_accesse( 0, 0, (int)$did );

					//print $acc." : ".$did."\n";

					if ( $acc == "yes" && $did > 0 ) {

						$tip       = getDogData( $did, 'tip' );
						$direction = getDogData( $did, 'direction' );

						//проверим этап, и если его нет в текущей воронке установим ближайший меньший
						$mFunnel       = getMultiStepList( [
							"direction" => $direction,
							"tip"       => $tip
						] );
						$steps         = array_keys( $mFunnel['steps'] );
						$currentStepID = current_dogstepid( $did );
						$currentStep   = current_dogstepname( $currentStepID );
						$newStep       = current_dogstepname( $newStepID );

						//print_r($steps);
						//print $currentStepID;

						//если этапа нет в новой цепочке
						/*if(!in_array(current_dogstepid($ids[$i]), $steps)) {

							$newStepID = array_shift($steps);
							$newStep = current_dogstepname($newStepID);

							//вычисляем ближайший меньший
							foreach ($steps as $k => $v) {

								if(current_dogstepname($k) < $currentStep){
									$newStepID = $k;
									$newStep = current_dogstepname($k);
								}

							}

						}*/

						//проводим только в случае, если в воронке есть новый этап или если воронка не задана
						if ( in_array( $newStepID, (array)$steps ) || count( (array)$steps ) == 0 ) {

							try {

								$db -> query( "UPDATE {$sqlname}dogovor SET idcategory = '$newStepID' WHERE did = '$did' and identity = '$identity'" );
								$good++;

								DealStepLog( $did, $newStepID );

								$msg[] = "Этап сделки изменен на $newStep%. Предыдущий этап $currentStep.";
								if ( $reazon != '' ) {
									$msg[] = ' <b>Примечание</b>: '.$reazon;
								}

								//$db -> query("insert into {$sqlname}history (cid,iduser,did,datum,des,tip,identity) values(null, '".$iduser1."', '".$did."', '".current_datumtime()."', '".implode("<br>", $msg)."', 'СобытиеCRM','$identity')");

								$hid = addHistorty( [
									"iduser"   => $iduser1,
									"did"      => $did,
									"datum"    => current_datumtime(),
									"des"      => implode( "\n", $msg ),
									"tip"      => "СобытиеCRM",
									"identity" => $identity
								] );

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}
						else {
							$outFunnel++;
						}

					}
					else {
						$noac++;
					}

				}

			break;

		}

	}

	$message = [];

	//print $outFunnel."\n";

	if ( $noac > 0 ) {
		$message[] = 'Нет доступа к <b>'.$noac.'</b> записям';
	}
	if ( $outFunnel > 0 ) {
		$message[] = 'Выбранный этап вне воронки по <b>'.$outFunnel.'</b> записям';
	}

	if ( $total >= 1000 ) {
		$message[] = 'Выполненено для '.$good.' записей ( масимальное количество 500 )';
	}

	else {
		$message[] = "Выполнено для <b>".$good."</b> записей.<br>Ошибок: <b>".count( (array)$err )."</b>";
	}

	print '{"result":"'.implode( '<br>', $message ).'"}';

	exit();

}

/**
 * Привязка договора к сделке
 */
if ( $action == "deal.append.contract" ) {

	$deid = (int)$_REQUEST['deid'];

	try {

		$db -> query( "UPDATE {$sqlname}dogovor SET dog_num = '$deid' WHERE did = '$did' and identity = '$identity'" );
		$db -> query( "UPDATE {$sqlname}contract SET did = '$did' WHERE deid = '$deid' and identity = '$identity'" );

		$mes = "Сделано";

	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	print '{"result":"'.$mes.'","error":""}';

	exit();
}

/**
 * Действия по сделке. дополнительные
 */
if ( $action == 'deal.change.user' ) {

	$newUser = (int)$_REQUEST['newuser'];
	$oldUser = (int)$_REQUEST['olduser'];

	$deal   = new Deal();
	$result = $deal -> changeuser( $did, $_REQUEST );

	if ( $_REQUEST['todo_send'] == 'yes' ) {

		$db -> query( "UPDATE {$sqlname}tasks SET iduser = '$newUser' WHERE iduser = '$oldUser' AND did = '$did' AND identity = '$identity'" );

	}

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['text']
	] );

	exit();
}

if ( $action == 'deal.change.dplan' ) {

	$deal   = new Deal();
	$result = $deal -> changeDatumPlan( $did, $_REQUEST );

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['text']
	] );

	exit();
}

if ( $action == "deal.change.close" ) {

	/**
	 * Вносим изменения в сделку
	 */
	$deal = $_REQUEST['dogovor'];

	if ( !empty($deal) ) {

		$deal   = new Deal();
		$result = $deal -> update( $did, $deal );

	}

	/**
	 * Закрываем сделку
	 */
	$params['datum']    = $_REQUEST['datum'];
	$params['sid']      = (int)$_REQUEST['sid'];
	$params['des_fact'] = untag( $_REQUEST['des_fact'] );
	$params['kol_fact'] = pre_format( $_REQUEST['kol_fact'] );
	$params['coid']     = (int)$_REQUEST['coid'];
	$params['co_kol']   = pre_format( $_REQUEST['co_kol'] );
	$params['marga']    = pre_format( $_REQUEST['marga'] );

	$deal   = new Deal();
	$result = $deal -> changeClose( $did, $params );

	// закрываем все напоминания по-тихому
	if ( $_REQUEST['closetask'] == 'yes' ) {

		$users[] = $iduser1;

		$iduser = getDogData( $did, 'iduser' );

		if ( (int)$iduser > 0 ) {
			$users[] = $iduser;
		}

		$tasks = $db -> getAll( "SELECT * FROM {$sqlname}tasks WHERE did = '$did' AND active = 'yes' AND iduser IN (".implode( ",", $users ).") AND identity = '$identity'" );

		//print_r($tasks);

		foreach ( $tasks as $task ) {

			(new Todo()) -> doit( $task['tid'], [
				"rezultat" => "Закрыто опционально при закрытии сделки",
				"tip"      => "СобытиеCRM",
				"status"   => 1
			] );

			//print_r($r);

		}

	}

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['text']
	] );

	exit();

}

if ( $action == 'deal.change.description' ) {

	$new_des = $_REQUEST["des"];

	//найдем текущее значения поля Описание
	$des = $db -> getOne( "select content from {$sqlname}dogovor where did='".$did."' and identity = '$identity'" );
	$des = 'Добавлено: '.current_datumtime().'; Добавил: '.current_user( $iduser1 ).'\n'.$new_des.'\n\n'.$des;

	$params['des'] = $des;

	$deal   = new Deal();
	$result = $deal -> update( $did, $params );

	$mes = $result['result'];
	if ( $result['error']['text'] ) {
		$mes .= "<br>".implode( "<br>", $result['error']['text'] );
	}

	print $mes;

	exit();

}

if ( $action == "deal.change.step" ) {

	$mes = '';

	/**
	 * Вносим изменения в сделку
	 */
	$adeal = (array)$_REQUEST['dogovor'];

	if ( !empty($adeal) ) {

		$deal   = new Deal();
		$result = $deal -> update( $did, $adeal );

		$mes = $result['result'];
		if ( $result['error']['text'] ) {
			$mes .= "<br>".implode( "<br>", $result['error']['text'] );
		}

	}

	$params = [
		"did"         => $did,
		"description" => $_REQUEST['description'],
		"step"        => $_REQUEST['idcategory'],
		"iduser"      => $iduser1
	];

	$deal = new Deal();
	$info = $deal -> changestep( $did, $params );

	$error = ($info['error']['text'] != '') ? $info['error']['text'] : "no";

	print json_encode_cyr( [
		"result" => $mes.' '.$info['result'],
		"error"  => $error,
		"did"    => $did
	] );

	//print '{"result":"'.$info['result'].'","error":"'.$info['error'].'","did":"'.$did.'", "text":"'.$info['sklad'].'"}';

	exit();

}

if ( $action == 'deal.change.dostup' ) {

	$deal = new Deal();
	$info = $deal -> changeDostup( $did, $_REQUEST );

	$good = $info['data']['count'];
	$err  = $info['data']['errors'];

	$result = ($info['result'] == 'Ok') ? "Выполнено для $good записей.<br>Ошибок: $err" : "Ошибок: $err";

	print json_encode_cyr( [
		"result" => $result,
		"error"  => implode( "<br>", $info['error']['text'] ),
		"did"    => $did
	] );

	exit();

}

if ( $action == 'deal.change.period' ) {

	$params['datum_start'] = $_REQUEST['dstart'];
	$params['datum_end']   = $_REQUEST['dend'];

	$deal   = new Deal();
	$result = $deal -> changePeriod( $did, $params );

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => $result['error']['text']
	] );

	exit();

}

// Одиночное изменение поля сделки
if ( $action == 'deal.change.field' ) {

	$field = $_REQUEST['field'];

	if ( $field == 'coid1' ) {
		$val = yimplode( ";", $_REQUEST['value'] );
	}
	else {
		$val = (gettype( $_REQUEST['value'] ) == "array") ? yimplode( ",", $_REQUEST['value'] ) : $_REQUEST['value'];
	}

	$params[ $field ] = $val;

	$deal   = new Deal();
	$result = $deal -> update( $_REQUEST['did'], $params );

	$mes = $result['result'];

	if ( $result['error']['text'] ) {
		$mes .= "<br>".implode( "<br>", $result['error']['text'] );
	}

	print $mes;

	exit();

}

/**
 * Работа со счетами
 */
if ( $action == "credit.edit" ) {

	$crid = (int)$_REQUEST['crid'];

	$params = $_REQUEST;

	if ( $crid > 0 ) {

		$invoice = new Invoice();
		$result  = $invoice -> edit( $crid, $params );

	}
	else {

		/**
		 * Вносим изменения в сделку
		 */
		$deal = $_REQUEST['dogovor'];
		if ( $deal ) {

			$d = new Deal();
			$r = $d -> update( $did, $deal );

		}

		$invoice = new Invoice();
		$result  = $invoice -> add( $did, $params );

	}
	$mes = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => untag( $mes )
	] );

	exit();

}

if ( $action == "credit.express" ) {

	/**
	 * Вносим изменения в сделку
	 */
	$deal = (array)$_REQUEST['dogovor'];

	if ( !empty($deal) ) {

		$d = new Deal();
		$r = $d -> update( $did, $deal );

	}

	$params = $_REQUEST;

	$invoice = new Invoice();
	$result  = $invoice -> express( $did, $params );

	$mes = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	if ( $_REQUEST['createAkt'] == 'yes' ) {

		//include_once "../../inc/class/Akt.php";

		$aktdata = [
			"did"       => $did,
			"igen"      => $_REQUEST['akt']['igen'],
			"akt_num"   => $_REQUEST['akt']['num'],
			"temp"      => $_REQUEST['akt']['temp'],
			"status"    => $_REQUEST['akt']['status'],
			"subaction" => 'status'
		];

		$akt = new Akt();
		$rez = $akt -> edit( 0, $aktdata );

	}

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => untag( $mes )
	] );

	exit();
}
if ( $action == "credit.doit" ) {

	$crid = (int)$_REQUEST['crid'];

	/**
	 * Вносим изменения в сделку
	 */
	$deal = $_REQUEST['dogovor'];

	if ( $deal ) {

		$d = new Deal();
		$r = $d -> update( $did, $deal );

	}

	$params = $_REQUEST;

	$invoice = new Invoice();
	$result  = $invoice -> doit( $crid, $params );

	$mes = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	//генерируем акт
	if ( $_REQUEST['createAkt'] == 'yes' ) {

		//include_once "../../inc/class/Akt.php";

		$aktdata = [
			"did"       => $did,
			"igen"      => $_REQUEST['akt']['igen'],
			"akt_num"   => $_REQUEST['akt']['num'],
			"temp"      => $_REQUEST['akt']['temp'],
			"status"    => $_REQUEST['akt']['status'],
			"subaction" => 'status'
		];

		$akt = new Akt();
		$rez = $akt -> edit( 0, $aktdata );

	}

	//генерируем документ
	if ( $_REQUEST['createDoc'] == 'yes' ) {

		//include_once "../../modules/contract/Docgen.php";

		$odeal = get_dog_info( $did, "yes" );

		$data = [
			'datum'       => current_datumtime(),
			'datum_start' => $_REQUEST['doc']['datum_start'],
			'datum_end'   => $_REQUEST['doc']['datum_end'],
			'clid'        => (int)$odeal['clid'],
			'payer'       => (int)$odeal['payer'],
			'pid'         => (int)$odeal['pid'],
			'did'         => $did,
			'iduser'      => (int)$iduser1,
			'title'       => $_REQUEST['doc']['title'],
			'idtype'      => (int)$_REQUEST['doc']['idtype'],
			'mcid'        => (int)$odeal['mcid'],
			'status'      => '0',
			'identity'    => (int)$identity
		];

		//file_put_contents($rootpath."/cash/xdata.json", json_encode_cyr($data));

		$doc    = new Document();
		$update = $doc -> edit( 0, $data );
		$deid   = $update['id'];

		$mes .= (empty( $update['error'] )) ? yimplode( "<br>", $update['error'] ) : yimplode( "<br>", $update['message'] );

		//генерируем документ по шаблону
		if ( $_REQUEST['doc']['template'] != '' ) {
			try {
				$mes .= $doc -> generate( $deid, [
					"template" => $_REQUEST['doc']['template'],
					"append"   => true,
					"getPDF"   => $_REQUEST['doc']['getPDF']
				] );
			}
			catch ( Exception $e ) {

				$mes .= $e -> getMessage();

			}
		}

	}

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => untag( $mes ),
		"did"    => $did
	] );

	exit();

}
if ( $action == "credit.undoit" ) {

	$crid = (int)$_REQUEST['crid'];

	$params = $_REQUEST;

	$invoice = new Invoice();
	$result  = $invoice -> undoit( $crid, $params );

	$mes = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => untag( $mes ),
		"did"    => $did
	] );

	exit();

}
if ( $action == "credit.delete" ) {

	$crid = (int)$_REQUEST['crid'];

	$params = $_REQUEST;

	$invoice = new Invoice();
	$result  = $invoice -> delete( $crid, $params );

	$mes = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => untag( $mes ),
		"did"    => $did
	] );

	exit();
}

/**
 * Отправка счета по Email
 */
if ( $action == 'invoice.mail' ) {

	$crid = (int)$_REQUEST['crid'];

	$params = $_REQUEST;

	$invoice = new Invoice();
	$result  = $invoice -> mail( $crid, $params );

	$msg = ($result['error']['text'] != '') ? $result['error']['text'] : $result['text'];

	print json_encode_cyr( [
		"result" => $result['result'],
		"error"  => untag( $msg ),
		"did"    => $did
	] );

	exit();

}
if ( $action == 'invoice.link' ) {

	$crid = (int)$_REQUEST['crid'];

	$invoice = new Invoice();
	$result  = $invoice -> link( $crid );

	$r = [
		"file" => $result['result']['file'],
		"name" => $result['result']['name'],
		//"payorder" => $result['result']['payorder'],
		//"payorderName" => $result['result']['payorderName']
	];

	print json_encode_cyr( $r );

	exit();

}

if ( $action == 'provider.add' ) {

	$tip    = $_REQUEST['tip'];
	$conid  = (int)$_REQUEST['conid'];
	$partid = (int)$_REQUEST['partid'];
	$summa  = (float)pre_format( $_REQUEST['summa'] );
	$recal  = isset($_REQUEST['recal']) ? (int)$_REQUEST['recal'] : 1;

	try {

		$arg = [
			"conid"    => (int)$conid,
			"partid"   => (int)$partid,
			"did"      => $did,
			"summa"    => $summa,
			"recal"    => $recal,
			"identity" => $identity
		];
		$db -> query( "INSERT INTO {$sqlname}dogprovider SET ?u", $arg );

		$mes  = 'Сделано';
		$suma = 0;

		if ( $recal == 0 ) {
			addProviderRashod( $did, $summa );
		}

	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	//print '{"result":"Сделано","error":"'.$mes.'","did":"'.$did.'"}';

	print json_encode_cyr([
		"result" => "Сделано",
		"error"  => $mes,
		"did"    => $did
	]);

	exit();
}
if ( $action == 'provider.edit' ) {

	//print_r($_REQUEST);

	$tip    = $_REQUEST['tip'];
	$conid  = (int)$_REQUEST['conid'];
	$partid = (int)$_REQUEST['partid'];
	$id     = (int)$_REQUEST['id'];
	$summa  = (float)pre_format( $_REQUEST['summa'] );
	$recal  = isset($_REQUEST['recal']) ? (int)$_REQUEST['recal'] : 1;

	//Текущая сумма
	$psumma = $db -> getOne( "SELECT summa FROM {$sqlname}dogprovider WHERE id = '$id' and did = '$did' and identity = '$identity'" );

	//на сколько изменилась сумма
	$psum = $psumma - $summa;

	try {

		$arg = [
			"conid"  => $conid ,
			"partid" => $partid,
			"summa"  => $summa,
			"recal"  => $recal
		];
		$db -> query( "UPDATE {$sqlname}dogprovider SET ?u WHERE id = '$id' and identity = '$identity'", $arg );

		//print_r($arg);

		if ( $recal == 0 ) {
			$mes = addProviderRashod( $did, $psum );
		}
		else{
			reCalculate($did);
		}

	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	//print '{"result":"Сделано","error":"'.$mes.'","did":"'.$did.'"}';

	print json_encode_cyr([
		"result" => "Сделано",
		"error"  => $mes,
		"did"    => $did
	]);

	exit();
}
if ( $action == 'provider.delete' ) {

	$tip = $_REQUEST['tip'];
	$id  = (int)$_REQUEST['id'];

	//Текущая сумма
	$result = $db -> getRow( "SELECT summa, bid, recal FROM {$sqlname}dogprovider WHERE id = '$id' and did = '$did' and identity = '$identity'" );
	$psumma = pre_format( $result["summa"] );
	$bid    = (int)$result["bid"];
	$recal  = (int)$result["recal"];

	try {

		$db -> query( "DELETE FROM {$sqlname}dogprovider WHERE id = '$id' and identity = '$identity'" );

		if ( $recal == 0 ) {
			//addProviderRashod( $did, -$psumma );
			reCalculate($did);
		}

		//проверим наличие записи в расходах
		if ( $bid > 0 ) {

			$result = $db -> getRow( "SELECT * FROM {$sqlname}budjet WHERE id='".$bid."' and identity = '$identity'" );
			$do     = $result["do"];
			$summa  = $result["summa"];
			$rs     = (int)$result["rs"];

			//если расход проведен делаем обратное перемещение
			if ( $do == 'on' ) {
				Budget::rsadd( $rs, $summa, 'plus' );
			}

			//удаляем расход
			$db -> query( "delete from {$sqlname}budjet WHERE id = '".$bid."' and identity = '$identity'" );

		}

	}
	catch ( Exception $e ) {

		$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

	}

	print '{"result":"Сделано","error":"'.$mes.'","did":"'.$did.'"}';

	exit();

}

if ( $action == 'controlpoint.add' ) {

	$point = new ControlPoints();
	$res   = $point -> edit( 0, $_REQUEST );

	print json_encode_cyr( [
		"result" => $res['message'],
		"error"  => $res['error'],
		"did"    => (int)$res['did']
	] );

	exit();

}
if ( $action == 'controlpoint.edit' ) {

	$id = (int)$_REQUEST['id'];

	$point = new ControlPoints();
	$res   = $point -> edit( $id, $_REQUEST );

	print json_encode_cyr( [
		"result" => $res['message'],
		"error"  => $res['error'],
		"did"    => (int)$res['did']
	] );

	exit();

}
if ( $action == "controlpoint.doit" ) {

	$id = (int)$_REQUEST['id'];

	/**
	 * Вносим изменения в сделку
	 */
	$deal = (array)$_REQUEST['dogovor'];

	if ( !empty($deal) ) {

		$d = new Deal();
		$r = $d -> update( $did, $deal );

	}

	$point = new ControlPoints();
	$res   = $point -> doit( $id, $_REQUEST['datum'] );

	print json_encode_cyr( [
		"result" => $res['message'],
		"error"  => $res['error'],
		"did"    => (int)$res['did']
	] );

	exit();

}
if ( $action == 'controlpoint.undoit' ) {

	//Данные по комплектности сделки
	$id = (int)$_REQUEST['id'];

	$point = new ControlPoints();
	$res   = $point -> undoit( $id );

	print json_encode_cyr( [
		"result" => $res['message'],
		"error"  => $res['error'],
		"did"    => (int)$res['did']
	] );

	exit();

}
if ( $action == 'controlpoint.delete' ) {

	//Данные по комплектности сделки
	$id = (int)$_REQUEST['id'];

	$point = new ControlPoints();
	$res   = $point -> delete( $id );

	print json_encode_cyr( [
		"result" => $res['message'],
		"error"  => $res['error'],
		"did"    => (int)$res['did']
	] );

	exit();

}