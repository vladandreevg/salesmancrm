<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
//error_reporting( E_ALL );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";

$action  = $_REQUEST['action'];
$service = $_REQUEST['service'];

if ( $action == "addgroup" ) {

	try {

		$db -> query( "INSERT INTO ".$sqlname."group SET ?u", [
			'name'     => $_REQUEST['name'],
			'service'  => $sname,
			'identity' => $identity
		] );

		print "Группа добавлена";

	}
	catch ( Exception $e ) {

		echo 'Ошибка:'.$e -> getMessage();

	}

	exit();

}

if ( $action == "editgroup" ) {

	try {

		$db -> query( "UPDATE ".$sqlname."group SET ?u WHERE id = '".$_REQUEST['group']."' and identity = '$identity'", ['name' => $_REQUEST['name']] );

		print '<b>Сделано</b>';
		print 'Имя группы изменено';


	}
	catch ( Exception $e ) {

		echo 'Ошибка:'.$e -> getMessage();

	}

	exit();

}
if ( $action == "deletegroup" ) {

	//удалим все записи из списков
	try {

		$db -> query( "DELETE FROM ".$sqlname."grouplist WHERE gid = '".$_REQUEST['group']."' and identity = '$identity'" );

		//удалить группу
		try {

			$db -> query( "DELETE FROM ".$sqlname."group WHERE id = '".$_REQUEST['group']."' and identity = '$identity'" );
			print 'Запись удалена. Клиенты также откреплены от группы.';

		}
		catch ( Exception $e ) {

			echo 'Ошибка:'.$e -> getMessage();

		}

	}
	catch ( Exception $e ) {

		echo 'Ошибка:'.$e -> getMessage();

	}

}

if ( $action == "addtoGroup" ) {

	$clid = (int)$_REQUEST['clid'];
	$pid  = (int)$_REQUEST['pid'];
	$ids  = (array)$_REQUEST['id'];//группы, на которые надо подписать

	$tags = str_replace( ", ", ",", $_REQUEST['tags'] );

	$message = $error = [];
	$count   = 0;

	//получим набор групп в которых уже состоит объект
	if ( $clid > 0 ) {
		$list = $db -> getCol( "SELECT gid FROM ".$sqlname."grouplist WHERE clid = '$clid' and identity = '$identity'" );
	}
	elseif ( $pid > 0 ) {
		$list = $db -> getCol( "SELECT gid FROM ".$sqlname."grouplist WHERE pid = '$pid' and identity = '$identity'" );
	}

	//переберем все выбранные группы
	foreach ( $ids as $id ) {

		//сначала определим сервис, к которому относится группа
		$result    = $db -> getRow( "SELECT * FROM ".$sqlname."group WHERE id = '$id' and identity = '$identity'" );
		$groupName = $result["name"];
		$service   = $result["service"];
		$idservice = $result["idservice"];

		if ( !in_array( $id, $list ) ) {

			//определим имя и email объекта
			if ( $clid > 0 ) {

				$result    = $db -> getRow( "SELECT * FROM ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
				$userName  = $result["title"];
				$userMail  = yexplode( ",", str_replace( ";", ",", $result["mail_url"] ), 0 );
				$userPhone = yexplode( ",", str_replace( ";", ",", $result["phone"] ), 0 );

			}
			elseif ( $pid > 0 ) {

				$result    = $db -> getRow( "SELECT * FROM ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity'" );
				$userName  = $result["person"];
				$userMail  = yexplode( ",", str_replace( ";", ",", $result["mail"] ), 0 );
				$userPhone = yexplode( ",", str_replace( ";", ",", $result["mob"] ), 0 );

			}

			try {

				$data = [
					'gid'        => $id,
					'clid'       => $clid,
					'pid'        => $pid,
					'user_name'  => $userName,
					'user_email' => $userMail,
					'user_phone' => prepareMobPhone( $userPhone ),
					'tags'       => $tags,
					'identity'   => $identity
				];
				$db -> query( "INSERT INTO ".$sqlname."grouplist SET ?u", arrayNullClean( $data ) );

				$message[] = "Объект добавлен в группу <b>".$groupName."</b>";

			}
			catch ( Exception $e ) {

				$error[] = '<b class="yelw">Ошибка:</b> '.$e -> getMessage();

			}

		}
		else {

			$count   = 1;
			$error[] = 'Пропущено: Запись уже находится в группе '.$groupName;

		}

	}

	if ( !empty( $message ) ) {
		addHistorty( $params = [
			"datum"  => current_datumtime(),
			"iduser" => $iduser1,
			"clid"   => $clid,
			"des"    => implode( "\n", $message ),
			"tip"    => "СобытиеCRM"
		] );
	}

	if ( !empty( $message ) ) {
		print implode( "<br>", $message );
	}
	if ( !empty( $error ) ) {
		print "Ошибки: <br>".implode( "<br>", $error );
	}

	exit();

}
if ( $action == "removefromGroup" ) {

	$gid  = (int)$_REQUEST['gid']; //группы, с которых надо отписать или удалить
	$id   = (int)$_REQUEST['id'];  //id записи в списке контактов
	$pid  = (int)$_REQUEST['pid'];  //id записи в списке контактов
	$clid = (int)$_REQUEST['clid'];  //id записи в списке контактов
	$tip  = $_REQUEST['tip'];//для Unisender - тип отписки - email или phone

	if ( !$tip ) {
		$tip = 'email';
	}

	$result     = $db -> getRow( "SELECT * FROM ".$sqlname."grouplist WHERE id='".$id."' and identity = '$identity'" );
	$user_email = $result["user_email"];
	$user_phone = $result["user_phone"];

	//сначала определим сервис, к которому относится группа
	$groupName = $db -> getOne( "SELECT name FROM ".$sqlname."group WHERE id = '$gid' and identity = '$identity'" );

	$message = $error = [];

	if ( $id > 0 ) {

		try {
			$db -> query( "delete from ".$sqlname."grouplist where id = '".$id."' and identity = '$identity'" );
			$message[] = "Пользователь удален из группы [".$groupName."]";
		}
		catch ( Exception $e ) {
			$error[] = '<b class="yelw">Ошибка:</b> '.$e -> getMessage();
		}

	}

	addHistorty( $params = [
		"datum"  => current_datumtime(),
		"iduser" => $iduser1,
		"clid"   => $clid,
		"des"    => implode( "\\n", $message ),
		"tip"    => "СобытиеCRM"
	] );

	print implode( "<br>", $message );

	if ( !empty( $error ) ) {
		print "<br>Ошибки: ".implode( "<br>", $error );
	}

	exit();

}

if ( $action == "exportlists" ) {

	//список данных для экспорта в сервис
	$pid_list  = yexplode( ";", (string)$_POST['pid'] );
	$clid_list = yexplode( ";", (string)$_POST['clid'] );
	$gids      = (array)$_POST['id'];//группы, на которые надо подписать. ВАЖНО: за раз подписываем только на один сервис, но несколько его групп
	$tags      = str_replace( ", ", ",", $_POST['tags'] );
	$service   = $_POST['service'];//имя сервиса

	$idservice = [];

	//сначала определим id группы сервиса
	foreach ( $gids as $gid ) {

		$idservice[ (int)$gid ] = (int)$db -> getOne( "SELECT idservice FROM ".$sqlname."group WHERE id = '$gid' and identity = '$identity'" );

	}

	//общие параметры для импорта в сервис
	$params["user_lists"] = yimplode( ",", array_values( $idservice ) );//список id групп для подписки
	$params["tags"]       = $tags;

	//формируем параметры отправки данных в сервис
	if ( !empty( $pid_list ) ) {

		foreach ( $pid_list as $pid ) {

			$re        = $db -> getRow( "SELECT * FROM ".$sqlname."personcat WHERE pid = '$pid' and identity = '$identity'" );
			$user_name = $re["person"];

			$user_email = yexplode( ",", str_replace( ";", ",", $re["mail"] ), 0 );

			$user_phone = prepareMobPhone( yexplode( ",", str_replace( ";", ",", $re["mob"] ), 0 ) );

			if ( $user_email != '' && $user_name != '' ) {
				$params["data"][] = [
					"pid"        => (int)$pid,
					"user_name"  => $user_name,
					"user_email" => $user_email,
					"user_phone" => $user_phone
				];
			}

		}

	}
	if ( !empty( $clid_list ) ) {

		foreach ( $clid_list as $clid ) {

			$re        = $db -> getRow( "SELECT * FROM ".$sqlname."clientcat WHERE clid = '$clid' and identity = '$identity'" );
			$user_name = $re["title"];

			$user_email = yexplode( ",", str_replace( ";", ",", $re["mail_url"] ), 0 );
			$user_phone = prepareMobPhone( yexplode( ",", str_replace( ";", ",", $re["phone"] ), 0 ) );

			if ( $user_email != '' && $user_name != '' ) {
				$params["data"][] = [
					"clid"       => (int)$clid,
					"user_name"  => $user_name,
					"user_email" => $user_email,
					"user_phone" => $user_phone
				];
			}

		}

	}
	//закончили формировать данные

	//print_r($params);
	//exit();

	$count  = 0;//всего добавлено в группу
	$update = 0;

	//добавим объект в группу
	foreach ( $gids as $gid ) {

		foreach ( $params['data'] as $row ) {

			if ( (int)$row['pid'] > 0 ) {//если объект - персона

				$idlist = (int)$db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE pid = '".$row['pid']."' and gid = '$gid' and identity = '$identity' LIMIT 1" );

			}
			elseif ( (int)$row['clid'] > 0 ) {//если объект - персона

				$idlist = (int)$db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE clid = '".$row['clid']."' and gid = '$gid' and identity = '$identity' LIMIT 1" );

			}

			//проверим существование объекта в группе, и если он есть, то обновляем данные
			if ( $idlist > 0 ) {

				$db -> query( "UPDATE ".$sqlname."grouplist SET ?u  WHERE id = '$idlist' and identity = '$identity'", [
					'user_name'  => $row['user_name'],
					'user_email' => $row['user_email'],
					'user_phone' => $row['user_phone'],
					'tags'       => $params['tags']
				] );
				$update++;

			}
			else {

				$db -> query( "INSERT INTO ".$sqlname."grouplist SET ?u", [
					'gid'        => $gid[ $j ],
					'clid'       => (int)$row['clid'],
					'pid'        => (int)$row['pid'],
					'user_name'  => $row['user_name'],
					'user_email' => $row['user_email'],
					'user_phone' => $row['user_phone'],
					'tags'       => $params['tags'],
					'identity'   => $identity
				] );
				$count++;

			}

		}

	}

	print "Добавлено <b>".$count."</b> новых записей<br>Обновлено <b>".$update."</b> записей";

	exit();

}

if ( $action == "remove" ) {

	try {

		$db -> query( "delete from ".$sqlname."grouplist where id = '".$_REQUEST['id']."' and identity = '$identity'" );
		print 'Результат: Запись удалена из группы';

	}
	catch ( Exception $e ) {
		echo 'Результат: <b class="yelw">Ошибка:</b> '.$e -> getMessage();
	}

	exit();

}

if ( $action == "mass" ) {

	$gid      = (int)$_REQUEST['gid'];
	$ids      = explode( ";", $_REQUEST['ids'] );
	$doAction = $_REQUEST['doAction'];
	$newgid   = (int)$_REQUEST['newgid'];
	$cgid     = (int)$_REQUEST['cgid'];
	$isSelect = $_REQUEST['isSelect'];

	//print_r($ids);
	//exit();

	if ( $gid > 0 ) {
		$gg = "gid = '$gid' and";
	}

	$good = 0;
	$err  = [];

	if ( $newgid > 0 && $doAction == 'pMove' ) {//если перемещаем

		if ( $isSelect == 'doAll' ) {//все

			$results = $db -> query( "SELECT * FROM ".$sqlname."grouplist WHERE $gg identity = '$identity' ORDER BY id" );
			while ($datas = $db -> fetch( $results )) {

				try {
					$db -> query( "update ".$sqlname."grouplist set gid='$newgid' WHERE id='".$data['id']."' and identity = '$identity'" );
					$good++;
				}
				catch ( Exception $e ) {
					$err[] = $e -> getMessage();
				}

			}

		}
		if ( $isSelect == 'doSel' && !empty( $ids ) ) {//выбранные

			foreach ( $ids as $id ) {

				if ( (int)$id > 0 ) {

					$oldgid = $db -> getOne( "SELECT gid FROM ".$sqlname."grouplist WHERE id = '$id' and identity = '$identity'" );

					try {
						$db -> query( "update ".$sqlname."grouplist set gid='$newgid' WHERE id = '$id' and identity = '$identity'" );
						$good++;
					}
					catch ( Exception $e ) {
						$err[] = $e -> getMessage();
					}

				}

			}

		}

	}
	if ( $cgid > 0 && $doAction == 'pCopy' ) {//если перемещаем

		if ( $isSelect == 'doAll' ) {//все

			$results = $db -> query( "SELECT * FROM ".$sqlname."grouplist WHERE $gg identity = '$identity' ORDER BY id" );
			while ($datas = $db -> fetch( $results )) {

				//проверим - есть ли подписчик в группе, в которую копируем
				$id = (int)$db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE user_email = '".$datas['user_email']."' and gid = '$cgid' and identity = '$identity'" );

				if ( $id == 0 ) {//если такого нет

					try {

						$db -> query( "INSERT INTO ".$sqlname."grouplist SET ?u", [
							'gid'        => $cgid,
							'clid'       => (int)$datas['clid'],
							'pid'        => (int)$datas['pid'],
							'service'    => $service,
							'user_name'  => $datas['user_name'],
							'user_email' => $datas['user_email'],
							'tags'       => $tags,
							'identity'   => $identity
						] );
						$good++;

					}
					catch ( Exception $e ) {
						$err[] = $e -> getMessage();
					}

				}

			}

		}
		if ( $isSelect == 'doSel' && !empty( $ids ) ) {//выбранные

			foreach ( $ids as $id ) {

				if ( (int)$id > 0 ) {

					$resultg1   = $db -> getRow( "SELECT * FROM ".$sqlname."grouplist WHERE id = '$id' and identity = '$identity'" );
					$user_email = $resultg1['user_email'];
					$user_name  = $resultg1['user_name'];
					$tags       = $resultg1['tags'];
					$pid        = (int)$resultg1['pid'];
					$clid       = (int)$resultg1['clid'];

					//проверим - есть ли подписчик в группе, в которую копируем
					$xid = (int)$db -> getOne( "SELECT id FROM ".$sqlname."grouplist WHERE user_email = '".$user_email."' and gid = '$cgid' and identity = '$identity'" );

					if ( (int)$xid == 0 ) {//если такого нет

						try {

							$db -> query( "INSERT INTO ".$sqlname."grouplist SET ?u", [
								'gid'        => $cgid,
								'clid'       => $clid,
								'pid'        => $pid,
								'service'    => $service,
								'user_name'  => $user_name,
								'user_email' => $user_email,
								'tags'       => $tags,
								'identity'   => $identity
							] );
							$good++;

						}
						catch ( Exception $e ) {
							$err[] = $e -> getMessage();
						}

					}

				}

			}

		}

	}
	if ( $doAction == 'pDele' ) {//если удаляем

		if ( $isSelect == 'doAll' ) {//все

			$results = $db -> query( "SELECT * FROM ".$sqlname."grouplist WHERE $gg identity = '$identity' ORDER BY id" );
			while ($datas = $db -> fetch( $results )) {

				try {
					$db -> query( "delete from ".$sqlname."grouplist where id = '".$datas['id']."' and identity = '$identity'" );
					$good++;
				}
				catch ( Exception $e ) {
					$err[] = $e -> getMessage();
				}

			}
		}
		if ( $isSelect == 'doSel' && !empty( $ids ) ) {//выбранные

			foreach ( $ids as $id ) {

				if ( (int)$id > 0 ) {

					//$oldgid  = (int)$db -> getOne( "SELECT gid FROM ".$sqlname."grouplist WHERE id='$id' and identity = '$identity'" );

					try {
						$db -> query( "delete from ".$sqlname."grouplist where id = '$id' and identity = '$identity'" );
						$good++;
					}
					catch ( Exception $e ) {
						$err[] = $e -> getMessage();
					}

				}

			}

		}

	}
	if ( $doAction == 'pDeleC' ) {//если удаляем

		if ( $isSelect == 'doAll' ) {//все

			$results = $db -> query( "SELECT * FROM ".$sqlname."grouplist WHERE $gg identity = '$identity' ORDER BY id" );
			while ($datas = $db -> fetch( $results )) {

				try {
					$db -> query( "delete from ".$sqlname."grouplist where id = '".$datas['id']."' and identity = '$identity'" );
					$good++;
				}
				catch ( Exception $e ) {
					$err[] = $e -> getMessage();
				}

			}

		}
		if ( $isSelect == 'doSel' && !empty( $ids ) ) {//выбранные

			foreach ( $ids as $id ) {

				if ( (int)$id > 0 ) {

					try {
						$db -> query( "delete from ".$sqlname."grouplist where id = '$id' and identity = '$identity'" );
						$good++;
					}
					catch ( Exception $e ) {
						$err[] = $e -> getMessage();
					}

				}

			}

		}

	}
	if ( $doAction == 'pSync' ) {//если удаляем

		if ( $isSelect == 'doAll' ) {//все

			$results = $db -> query( "SELECT * FROM ".$sqlname."grouplist WHERE $gg identity = '$identity' ORDER BY id" );
			while ($datas = $db -> fetch( $results )) {

				$clid = (int)$db -> getOne( "SELECT clid FROM ".$sqlname."clientcat where mail_url LIKE '%".$datas['user_email']."%' and identity = '$identity' LIMIT 1" );

				$pid = (int)$db -> getOne( "SELECT pid FROM ".$sqlname."personcat where mail LIKE '%".$datas['user_email']."%' and identity = '$identity' LIMIT 1" );

				if ( $clid > 0 || $pid > 0 ) {
					try {
						$db -> query( "update ".$sqlname."grouplist set clid='$clid', pid='$pid' WHERE id='".$datas['id']."' and identity = '$identity'" );
						$good++;
					}
					catch ( Exception $e ) {
						$err[] = $e -> getMessage();
					}
				}

			}

		}
		if ( $isSelect == 'doSel' && !empty( $ids ) ) {//выбранные

			foreach ( $ids as $id ) {

				if ( (int)$id > 0 ) {

					$user_email = $db -> getOne( "SELECT user_email FROM ".$sqlname."grouplist where id = '$id' and identity = '$identity' LIMIT 1" );

					$clid = (int)$db -> getOne( "SELECT clid FROM ".$sqlname."clientcat where mail_url LIKE '%".$user_email."%' and identity = '$identity' LIMIT 1" );
					$pid  = (int)$db -> getOne( "SELECT pid FROM ".$sqlname."personcat where mail LIKE '%".$user_email."%' and identity = '$identity' LIMIT 1" );

					if ( $clid > 0 || $pid > 0 ) {

						try {
							$db -> query( "UPDATE ".$sqlname."grouplist SET ?u WHERE id = '$id' and identity = '$identity'", [
								'clid' => $clid,
								'pid'  => $pid
							] );
							$good++;
						}
						catch ( Exception $e ) {
							$err[] = $e -> getMessage();
						}

					}

				}

			}

		}

	}

	print "Выполнено для <b>".$good."</b> записей.<br>Ошибок: <b>".count( $err )."</b>";

	exit();

}
if ( $action == 'export' ) {

	$gid = (int)$_REQUEST['gid'];

	if ( $gid > 0 ) {
		$gg = "gid = '$gid' and";
	}

	$stroka[] = [
		'Имя',
		'Email',
		'Группа',
		'Метки'
	];

	$resultt = $db -> query( "SELECT * FROM ".$sqlname."grouplist where $gg identity = '$identity'" );
	while ($data = $db -> fetch( $resultt )) {

		$service  = $db -> getOne( "SELECT service FROM ".$sqlname."group WHERE id = '".$data['gid']."' and identity = '$identity'" );
		$stroka[] = [
			$data['user_name'],
			$data['user_email'],
			$service,
			untag( $data['tags'] )
		];

	}

	$filename = $rootpath."/modules/group/files/export.group.xlsx";

	Shuchkin\SimpleXLSXGen ::fromArray( $stroka ) -> downloadAs( 'export.group.xlsx' );

	exit();
}