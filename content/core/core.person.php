<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Mailer;
use Salesman\Person;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/developer/events.php";

$pid  = (int)$_REQUEST['pid'];
$clid = (int)$_REQUEST['clid'];

$action = $_REQUEST['action'];

if ( $action == "person.edit" ) {

	if ( $pid > 0 ) {

		$Person = new Person();
		$result = $Person -> fullupdate( $pid, $_REQUEST );

		//$hooks -> do_action( "person_edit", $_REQUEST );

	}
	else {

		$error = '';

		//print_r($_REQUEST);

		$Person = new Person();
		$result = $Person -> edit( 0, $_REQUEST );

		//$hooks -> do_action( "person_add", $_REQUEST );

		$pid             = $result['data'];
		$result['error'] = implode( "<br>", (array)$result['error'] );

		//перенесем файлы из почты
		if ( (int)$_REQUEST['messageid'] > 0 || (int)$_REQUEST['rid'] > 0 ) {

			//добавим к адресату id записей клиента и контакта
			$db -> query( "update {$sqlname}ymail_messagesrec set ?u WHERE mid = '$_REQUEST[messageid]' and identity = '$identity'", [
				"pid"  => $pid,
				"clid" => $clid
			] );

			//добавим историю
			//include_once "../../modules/ymail/yfunc.php";

			//найдем email в письме
			if ( (int)$_REQUEST['rid'] > 0 ) {

				$yemail = $db -> getOne( "SELECT email FROM {$sqlname}ymail_messagesrec WHERE id = '$_REQUEST[rid]' and identity = '$identity'" );
				$s      = " or email = '$yemail'";

			}

			//Пройдем все письма и присвоим им данные клиента
			$resultt = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE (mid = '$_REQUEST[messageid]' $s) and identity = '$identity'" );
			while ($data = $db -> fetch( $resultt )) {

				$db -> query( "UPDATE {$sqlname}ymail_messagesrec SET ?u WHERE id = '$data[id]'", [
					"pid"  => $pid,
					"clid" => $clid
				] );

				$hid = $db -> getOne( "select hid from {$sqlname}ymail_messages WHERE id='$data[mid]' and identity = '$identity'" );

				if ( $hid == 0 ) {

					$rezz = Mailer ::putHistory( (int)$data['mid'] );
					$rez  = implode( "<br>", $rezz );

				}

			}

		}

	}

	print json_encode_cyr( [
		"pid"    => $pid,
		"result" => "Готово",
		"error"  => $result['error']
	] );

	exit();

}

if ( $action == "person.mass" ) {

	$ids         = explode( ",", $_REQUEST['ids'] );
	$doAction    = $_REQUEST['doAction'];
	$isSelect    = $_REQUEST['isSelect'];
	$newuser     = (int)$_REQUEST['newuser'];
	$duser       = (int)$_REQUEST['duser'];
	$nterritory  = (int)$_REQUEST['nterritory'];
	$tipcmr      = (int)$_REQUEST['tipcmr'];
	$person_send = $_REQUEST['person_send'];
	$dog_send    = $_REQUEST['dog_send'];
	$reazon      = $_REQUEST['reazon'];
	$newgid      = (int)$_REQUEST['newgid'];

	$haveEmail    = $_REQUEST['haveEmail'];
	$havePhone    = $_REQUEST['havePhone'];
	$haveMobPhone = $_REQUEST['haveMobPhone'];

	$noac  = 0;
	$good  = 0;
	$pgood = 0;
	$dgood = 0;
	$hgood = 0;
	$err   = $msg = [];

	if ( $isSelect == 'doAll' ) {

		$iduser     = $_REQUEST['iduser'];
		$word       = str_replace( " ", "", trim( $_REQUEST['word'] ) );
		$alf        = $_REQUEST['alf'];
		$tbl_list   = $_REQUEST['tbl_list'];
		$filter     = $_REQUEST['list'];
		$filter     = ($filter == '') ? '' : 'my';
		$clientpath = $_REQUEST['clientpath'];
		$loyalty    = $_REQUEST['loyalty'];

		$query = getFilterQuery( 'person', [
			'iduser'     => $iduser,
			'word'       => $word,
			'alf'        => $alf,
			'tbl_list'   => $tbl_list,
			'filter'     => $filter,
			'clientpath' => $clientpath,
			'loyalty'    => $loyalty
		], false );

		$total = 0;

		//произведем действия
		switch ($doAction) {

			case 'userChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( get_accesse( 0, (int)$data['pid'] ) == "yes" && $total < 1000 ) {

						try {

							$db -> query( "UPDATE {$sqlname}personcat SET iduser = ".$newuser." where pid = '".$data['pid']."' and identity = '$identity'" );
							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

						//внесем запись в историю Организации
						$current_user = current_user( get_userid( 'pid', $data['pid'] ) );
						if ( $current_user == '' ) {
							$current_user = "Свободный Контакт";
						}

						try {

							$db -> query( "insert into {$sqlname}history (cid, pid, datum, des, iduser, tip,identity) values(null, '".$data['pid']."', '".current_datumtime()."', 'Передача Контакта от ".$current_user.". <b>Причина</b>:".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')" );
							$hgood++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}
					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'dostupChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( get_accesse( 0, (int)$data['pid'] ) == "yes" && $total < 1000 ) {

						$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE pid = '".$data['pid']."' and identity = '$identity'" );

						if ( !in_array( $data['pid'], $dostup ) ) {

							try {

								$db -> query( "insert into {$sqlname}dostup (id,pid,iduser,identity) values (null,'".$data['pid']."','".$duser."','$identity')" );
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

					$total++;

				}

			break;
			case 'cmrChange':

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( get_accesse( 0, (int)$data['pid'] ) == "yes" && $total < 1000 ) {

						try {

							$db -> query( "update {$sqlname}personcat set loyalty = '".$tipcmr."' where pid = '".$data['pid']."' and identity = '$identity'" );
							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else {
						$noac++;
					}

					$total++;

				}

			break;
			case 'groupChange':

				$service = $db -> getOne( "SELECT service FROM {$sqlname}group WHERE id = '".$newgid."' and identity = '$identity'" );

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( get_accesse( 0, (int)$data['pid'] ) == "yes" && $total < 1000 ) {

						$user_email = yexplode( ",", (string)str_replace( ";", ",", $data['mail'] ), 0 );
						$user_name  = $data['person'];

						//проверим - есть ли подписчик в группе, в которую копируем
						if ( $service ) {

							$id = $db -> getOne( "SELECT id FROM {$sqlname}grouplist WHERE user_email = '$user_email' and gid = '$newgid' and identity = '$identity'" );

						}
						else {

							$id = $db -> getOne( "SELECT id FROM {$sqlname}grouplist WHERE pid = '".$data['pid']."' and gid = '$newgid' and identity = '$identity'" );

						}

						if ( $id < 1 ) {

							$methodError = '';

							if ( $methodError == '' ) {

								try {

									//$db -> query( "insert into {$sqlname}grouplist (id,gid,pid,service,user_name,user_email,tags,identity) values(null, '".$newgid."', '".$data['pid']."','".$service."','".$user_name."','".$user_email[0]."', '".$tags."','$identity')" );

									$db -> query( "INSERT INTO {$sqlname}grouplist SET ?u", arrayNullClean( [
										'gid'        => $newgid + 0,
										'pid'       => $data['pid'] + 0,
										'service'    => $service,
										'user_name'  => $user_name,
										'user_email' => $user_email,
										'tags'       => ($tags != '') ? $tags : ' ',
										'identity'   => $identity
									]) );

									$good++;

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

						}

					}
					else $noac++;

					$total++;

				}

			break;
			case 'clientDelete':

				$p   = 0;
				$pd  = 0;
				$h   = 0;
				$t   = 0;
				$mes = '';

				$result = $db -> query( $query );
				while ($data = $db -> fetch( $result )) {

					if ( get_accesse( 0, (int)$data['pid'] ) == "yes" && $total < 1000 ) {

						$dogs = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}dogovor WHERE clid = 0 and pid='".$data['pid']."' and identity = '$identity'" );

						if ( $dogs == 0 ) {

							//Удалим всю историю переговоров
							$result2 = $db -> query( "select * from {$sqlname}history WHERE pid='".$data['pid']."' and identity = '$identity'" );
							while ($data_array2 = $db -> fetch( $result2 )) {

								if ( $data_array2['clid'] < 1 ) {

									$db -> query( "delete from {$sqlname}history where cid = '".$data_array2['cid']."' and identity = '$identity'" );
									$h++;

								}
								else {
									$db -> query("update {$sqlname}history set pid = '0' where cid = '".$data_array2['cid']."' and identity = '$identity'");
								}

							}

							//Удалим все напоминания
							$result3 = $db -> query( "select * from {$sqlname}tasks WHERE pid='".$data['pid']."' and identity = '$identity'" );
							while ($data_array3 = $db -> fetch( $result3 )) {

								if ( $data_array3['clid'] < 1 ) {

									$db -> query( "delete from {$sqlname}tasks where tid = '".$data_array3['tid']."' and identity = '$identity'" );
									$t++;

								}
								else $db -> query( "update {$sqlname}tasks set pid = '0' where tid = '".$data_array3['tid']."' and identity = '$identity'" );

							}

							//Удалим всю связанные файлы
							$result4 = $db -> query( "select * from {$sqlname}file WHERE pid='".$data['pid']."' and identity = '$identity'" );
							while ($data_array4 = $db -> fetch( $result4 )) {

								if ( $data_array4['clid'] < 1 ) {

									@unlink( "../../files/".$fpath.$data_array4['fname'] );
									$db -> query( "delete from {$sqlname}file where fid = '".$data_array4['fid']."' and identity = '$identity'" );
									$f++;

								}
								else $db -> query( "update {$sqlname}file set pid = '0' where fid = '".$data_array4['fid']."' and identity = '$identity'" );
							}

							try {

								$db -> query( "delete from {$sqlname}personcat where pid = '".$data['pid']."' and identity = '$identity'" );
								$good++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}
						else $err[] = 1;

					}
					else $noac++;

					$total++;

				}

				if ( $p > 0 )
					$msg[] = 'Обновлено <b>'.$p.'</b> Контактов - снята привязка к Клиенту';
				if ( $pd > 0 )
					$msg[] = 'Удалено <b>'.$p.'</b>';
				if ( $f > 0 )
					$msg[] = 'Так же удалено <b>'.$f.'</b> файлов';

				if ( count( $err ) > 0 )
					$msg[] = 'У <b>'.count( $err ).'</b> контактов есть сделки. Они не доступны.';


			break;

		}

	}

	if ( $isSelect == 'doSelected' ) {

		//произведем действия
		switch ($doAction) {

			case 'userChange':

				foreach ($ids as $id) {

					if ( get_accesse( 0, (int)$id ) == "yes" ) {

						try {

							$db -> query( "update {$sqlname}personcat set iduser = ".$newuser." where pid = '".$id."' and identity = '$identity'" );
							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

						//внесем запись в историю Организации
						$current_user = current_user( get_userid( 'pid', $id ) );
						if ( $current_user == '' )
							$current_user = "Свободный клиент";

						try {

							//$db -> query( "insert into {$sqlname}history (cid, pid, datum, des, iduser, tip,identity) values(null, '".$ids[ $i ]."', '".current_datumtime()."', 'Передача Контакта от ".$current_user.". <b>Причина</b>:".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')" );

							$hid = addHistorty( [
								"pid"     => $id,
								"datum"    => current_datumtime(),
								"des"      => "Передача Контакта от ".$current_user.". <b>Причина</b>:".$reazon,
								"tip"      => "СобытиеCRM",
								"iduser"   => $iduser1,
								"identity" => $identity
							] );

							$hgood++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else $noac++;
				}

			break;
			case 'dostupChange':

				foreach ($ids as $id) {

					if ( get_accesse( 0, (int)$id ) == "yes" ) {

						$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE pid = '$id' and identity = '$identity'" );

						if ( !in_array( $id, $dostup ) ) {

							try {

								$db -> query( "insert into {$sqlname}dostup (id,pid,iduser,identity) values (null,'$id','".$duser."','$identity')" );
								$good++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}
					else $noac++;
				}

			break;
			case 'cmrChange':

				foreach ($ids as $id) {

					if ( get_accesse( 0, (int)$id ) == "yes" ) {

						try {

							$db -> query( "update {$sqlname}personcat set loyalty = '".$tipcmr."' where pid = '$id' and identity = '$identity'" );
							$good++;

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}
					else $noac++;
				}

			break;
			case 'groupChange':

				$service = $db -> getOne( "SELECT service FROM {$sqlname}group WHERE id = '".$newgid."' and identity = '$identity'" );

				foreach ($ids as $id) {

					$resultg1   = $db -> getRow( "SELECT mail, person FROM {$sqlname}personcat WHERE pid = '$id' and identity = '$identity'" );
					$user_email = yexplode( ",", str_replace( ";", ",", $resultg1["mail"] ), 0 );
					$user_name  = $resultg1['person'];

					//проверим - есть ли подписчик в группе, в которую копируем
					if ( $service ) { //если помещаем в группу сервиса

						$xid = $db -> getOne( "SELECT id FROM {$sqlname}grouplist WHERE user_email = '".$user_email."' and gid = '$newgid' and identity = '$identity'" );

					}
					else {

						$xid = $db -> getOne( "SELECT id FROM {$sqlname}grouplist WHERE pid = '$id' and gid = '$newgid' and identity = '$identity'" );

					}

					if ( $xid < 1 ) {

						$methodError = '';

						if ( $methodError == '' ) {

							try {

								//$db -> query( "insert into {$sqlname}grouplist (id,gid,pid,service,user_name,user_email,tags,identity) values(null, '".$newgid."', '".$ids[ $i ]."','".$service."','".$user_name."','".$user_email[0]."', '".$tags."','$identity')" );

								$db -> query( "INSERT INTO {$sqlname}grouplist SET ?u", arrayNullClean( [
									'gid'        => $newgid + 0,
									'pid'        => $id + 0,
									'service'    => $service,
									'user_name'  => $user_name,
									'user_email' => $user_email,
									'tags'       => ($tags != '') ? $tags : ' ',
									'identity'   => $identity
								]) );

								$good++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}
				}

			break;
			case 'clientDelete':

				$p   = 0;
				$pd  = 0;
				$h   = 0;
				$t   = 0;
				$mes = '';

				foreach ($ids as $id) {

					if ( get_accesse( 0, (int)$id ) == "yes" ) {

						$dogs = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}dogovor WHERE clid = '0' and pid='$id' and identity = '$identity'" );

						if ( $dogs == 0 ) {

							//Удалим всю историю переговоров
							$result2 = $db -> query( "select * from {$sqlname}history WHERE pid='$id' and identity = '$identity'" );
							while ($data_array2 = $db -> fetch( $result2 )) {

								if ( (int)$data_array2['clid'] == 0 ) {

									$db -> query( "delete from {$sqlname}history where cid = '".$data_array2['cid']."' and identity = '$identity'" );
									$h++;

								}
								else $db -> query( "update {$sqlname}history set pid = '0' where cid = '".$data_array2['cid']."' and identity = '$identity'" );
							}

							//Удалим все напоминания
							$result3 = $db -> query( "select * from {$sqlname}tasks WHERE pid='$id' and identity = '$identity'" );
							while ($data_array3 = $db -> fetch( $result3 )) {

								if ( $data_array3['clid'] < 1 ) {

									$db -> query( "delete from {$sqlname}tasks where tid = '".$data_array3['tid']."' and identity = '$identity'" );
									$t++;

								}
								else $db -> query( "update {$sqlname}tasks set pid = '0' where tid = '".$data_array3['tid']."' and identity = '$identity'" );

							}
							//Удалим всю связанные файлы
							$result4 = $db -> query( "select * from {$sqlname}file WHERE pid='".$ids[ $i ]."' and identity = '$identity'" );
							while ($data_array4 = $db -> fetch( $result4 )) {

								if ( $data_array4['clid'] < 1 ) {

									@unlink( $rootpath."/files/".$fpath.$data_array4['fname'] );
									$db -> query( "delete from {$sqlname}file where fid = '".$data_array4['fid']."' and identity = '$identity'" );
									$f++;

								}
								else $db -> query( "update {$sqlname}file set pid = '0' where fid = '".$data_array4['fid']."' and identity = '$identity'" );

							}

							try {

								$db -> query( "delete from {$sqlname}personcat where pid = '".$ids[ $i ]."' and identity = '$identity'" );
								$good++;

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}
						else $err[] = 1;

					}
					else $noac++;

				}

				if ( $f > 0 )
					$msg[] = 'Так же удалено <b>'.$f.'</b> файлов';

			break;

		}

	}

	if ( $noac > 0 ) {
		$msg[] = 'Нет доступа к <b>'.$noac.'</b> записям';
	}

	if($total >= 1000) {
		$msg[] = 'Выполненено для '.$good.' записей ( масимальное количество 1000 )';
	}

	$msg[] = "Выполнено для <b>".$good."</b> записей.<br>Ошибок: <b>".count( $err )."</b>";

	if ( !empty( $msg ) ) {
		$rez = implode('<br>', $msg);
	}

	print json_encode_cyr( [
		"pid"    => $pid,
		"result" => $rez
	] );

	exit();
}

if ( $action == "person.delete" ) {

	$mes   = '';
	$error = '';

	$person = current_person( $pid );

	//Проверяем на наличие договоров
	$count = (int)$db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}dogovor WHERE pid='".$pid."' and identity = '$identity'" );

	if ( $count > 0 ) {
		$error = '<b class="yelw uppercase">Внимание:</b><br>К сожалению Удаление записи не возможно. Причина - Имеются связанные записи - Сделки.';
	}
	else {

		if ( $pid > 0 ) {

			$p = 0;
			$h = 0;
			$t = 0;
			$f = 0;

			//$hooks -> do_action( "person_delete", $pid );

			//Удалим всю историю переговоров
			$result1 = $db -> query( "select * from {$sqlname}history WHERE FIND_IN_SET('".$pid."', REPLACE(pid, ';',',')) > 0 and identity = '$identity'" );
			while ($data = $db -> fetch( $result1 )) {

				$pids = yexplode( ";", (string)$data['pid'] );

				if ( ($key = array_search( $pid, $pids )) !== false ) {
					unset($pids[$key]);
				}

				if ( (int)$data['clid'] == 0 && (int)$data['did'] == 0 ) {

					if ( empty( (array)$pids ) ) {
						$db -> query("DELETE FROM {$sqlname}history WHERE cid = '".$data['cid']."' and identity = '$identity'");
					}
					else {
						$db -> query("UPDATE {$sqlname}history SET pid = '".implode(";", $pids)."' WHERE cid = '".$data['cid']."' and identity = '$identity'");
					}

				}
				else {

					$db -> query( "UPDATE {$sqlname}history SET pid = '".implode( ";", $pids )."' WHERE cid = '".$data['cid']."' and identity = '$identity'" );

				}

				$h++;

			}

			//Удалим всю связанные файлы
			$result1 = $db -> query( "select * from {$sqlname}file WHERE pid='".$pid."' and identity = '$identity'" );
			while ($data = $db -> fetch( $result1 )) {

				if ( $data['clid'] < 1 && $data['did'] < 1 ) {

					@unlink( $rootpath."/files/".$data['fname'] );

					$db -> query( "DELETE FROM {$sqlname}file where fid = '".$data['fid']."' and identity = '$identity'" );

				}
				else {
					$db -> query("UPDATE {$sqlname}file SET pid = '0' WHERE fid = '".$data['fid']."' and identity = '$identity'");
				}
				$f++;

			}

			//Удалим все напоминания
			$result1 = $db -> query( "select * from {$sqlname}tasks WHERE FIND_IN_SET('".$pid."', REPLACE(pid, ';',',')) > 0 and identity = '$identity'" );
			while ($data = $db -> fetch( $result1 )) {

				$pids = yexplode( ";", (string)$data['pid'] );

				if ( ($key = array_search( $pid, (array)$pids )) !== false )
					unset( $pids[ $key ] );

				if ( (int)$data['clid'] == 0 && (int)$data['did'] == 0 ) {

					if ( empty( (array)$pids ) )
						$db -> query( "delete from {$sqlname}tasks where tid = '".$data['tid']."' and identity = '$identity'" );
					else $db -> query( "update {$sqlname}tasks set pid = '".implode( ";", $pids )."' where tid = '".$data['tid']."' and identity = '$identity'" );

				}
				else {
					$db -> query( "update {$sqlname}tasks set pid = '".implode( ";", $pids )."' where tid = '".$data['tid']."' and identity = '$identity'" );
				}

				$t++;

			}

			//удалим привязки в письмах
			$db -> query( "UPDATE {$sqlname}ymail_messagesrec SET pid = '0', clid = '0' WHERE pid = '$pid' and identity = '$identity'" );

			logger( '11', 'Удален контакт '.$person, $iduser1 );

			$db -> query( "delete from {$sqlname}personcat where pid = '$pid' and identity = '$identity'" );

			event ::fire( 'person.delete', $args = [
				"pid"   => $pid,
				"autor" => $iduser1
			] );

		}
		$mes = '<b>Успешно:</b><br><b>Контакт удален.</b><br>Также удалено <b>'.$h.'</b> записи истории активностей, <b>'.$f.'</b> файлов, <b>'.$t.'</b> напоминаний.';

	}

	print json_encode_cyr( [
		"result" => $mes,
		"error"  => $error
	] );

	exit();
}

/**
 * DEPRECATED
 * Пененесено в client.change.user
 */
if ( $action == 'change.user' ) {

	$newuser     = (int)$_REQUEST["newuser"];
	$person_send = $_REQUEST['person_send'];
	$dog_send    = $_REQUEST['dog_send'];
	$reazon      = $_REQUEST['reason'];

	if ( $reazon == '' ) {
		$reazon = 'не указано';
	}

	$olduser = getPersonData( $pid, 'iduser' );

	$db -> query( "update {$sqlname}personcat set iduser = '".$newuser."' where pid = '".$pid."' and identity = '$identity'" );

	if ( $dog_send == "yes" ) {

		$result = $db -> query( "SELECT * FROM {$sqlname}dogovor WHERE pid='".$pid."' and clid = '' and identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			$db -> query( "update {$sqlname}dogovor set iduser = '".$newuser."' where did = '".$data['did']."' and close!='yes' and identity = '$identity'" );

			//внесем запись в историю Сделки
			$db -> query( "insert into {$sqlname}history (cid, did, datum, des, iduser, tip,identity) values(null, '".$data['did']."', '".current_datumtime()."', 'Передача с Контактом. <b>Причина</b>: ".$reazon."', '".$iduser1."', 'СобытиеCRM','$identity')" );

			$resultc = $db -> query( "SELECT * FROM {$sqlname}complect WHERE did='".$data['did']."' and doit != 'on' and identity = '$identity'" );
			while ($datac = $db -> fetch( $resultc )) {
				$db -> query( "update {$sqlname}complect set iduser = '".$newuser."' where id = '".$datac['id']."' and identity = '$identity'" );
			}

		}

	}

	$db -> query( "insert into {$sqlname}history (cid,iduser,pid,datum,des,tip,identity) values(null, '".$iduser1."', '".$pid."', '".current_datumtime()."', 'Смена Ответственного: ".current_user( $olduser )."&rarr;".current_user( $newuser ).". Причина: ".$reazon.". Изменил: ".current_user( $iduser1 )."', 'СобытиеCRM','$identity')" );

	sendNotify( 'send_person', $params = [
		"pid"     => $pid,
		"title"   => getPersonData( $pid, 'person' ),
		"iduser"  => $newuser,
		"notice"  => 'yes',
		"comment" => $reazon
	] );

	event ::fire( 'person.change.user', $args = [
		"pid"     => $pid,
		"autor"   => $iduser1,
		"olduser" => $olduser,
		"newuser" => $newuser,
		"comment" => $reazon
	] );

	print json_encode_cyr( [
		"result" => "Сделано"
	] );

	exit();

}