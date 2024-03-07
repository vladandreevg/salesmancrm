<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Comments;
use Salesman\Upload;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

$scheme = $_SERVER[ 'HTTP_SCHEME' ] ?? ( ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] != 'off' ) || 443 == $_SERVER[ 'SERVER_PORT' ] ) ? 'https://' : 'http://';

if ($action == "edit") {

	$id       = (int)$_REQUEST['id'];
	$idparent = (int)$_REQUEST['idparent'];
	$params   = $_REQUEST;

	$comment = new Comments();
	$r       = $comment -> edit($id, $params);

	print json_encode_cyr([
		"id"  => $r['idparent'],
		"mes" => $r['text']
	]);

}

if ($action == "close") {

	$id = (int)$_REQUEST['id'];

	$r = Comments ::close($id);

	print json_encode_cyr([
		"id"      => $id,
		"message" => $r['message']
	]);

	exit();

}

if ($action == "subscribe") {

	$id = (int)$_REQUEST['id'];

	$r = Comments ::subscribe($id, $iduser1);

	$message = $r ? "Оформлена подписка" : "Подписка не оформлена";

	print json_encode_cyr([
		"result"   => $message,
		"id"       => $id,
		"idparent" => 0
	]);

	exit();
}
if ($action == "unsubscribe") {

	$id = (int)$_REQUEST['id'];

	/*
	$db -> query("DELETE FROM ".$sqlname."comments_subscribe WHERE idcomment = '$id' and iduser = '$iduser1' and identity = '$identity'");
	*/

	$r = Comments ::unsubscribe($id, $iduser1);

	$message = $r ? "Вы отписаны от темы" : "Ошибка";


	print json_encode_cyr([
		"result"   => $message,
		"id"       => $id,
		"idparent" => 0
	]);

	exit();
}

if ($action == "subscribe.user") {

	$id    = (int)$_REQUEST['id'];
	$users = $_REQUEST['users'];

	$message = [];

	foreach ($users as $user) {

		/*
		$db -> query("INSERT INTO ".$sqlname."comments_subscribe SET ?u", array(
			'idcomment' => $id,
			'iduser'    => $user,
			'identity'  => $identity
		));
		*/

		$r = Comments ::subscribe($id, (int)$user);

		if ($r) {
			$s = Comments ::send( $id, (int)$user );
		}

		$message[] = $s['text'];

	}

	$rez = [
		"id"  => $id,
		"mes" => yimplode("<br>", $message)
	];

	print json_encode_cyr($rez);

	exit();

}
if ($action == "unsubscribe.user") {

	$mid    = (int)$_REQUEST['mid'];
	$iduser = (int)$_REQUEST['iduser'];

	$r = Comments ::unsubscribe($mid, $iduser);

	$message = $r ? "Пользователь отписан" : "Ошибка";

	exit();

}

if ($action == "delete") {

	$id = (int)$_REQUEST['id'];

	$r = Comments ::delete($id);

	print json_encode_cyr([
		"result"   => yimplode("<br>", $message),
		"id"       => $id,
		"idparent" => $r['idparent']
	]);

	exit();

}
if ($action == "delete.old") {

	$id = (int)$_REQUEST['id'];

	$res      = $db -> getRow("SELECT * FROM ".$sqlname."comments WHERE id = '$id' and identity = '$identity'");
	$fids     = $res["fid"];
	$idparent = (int)$res["idparent"];

	$message = [];

	//удалим прикрепленные файлы
	$fid = yexplode(";", $fids);
	foreach ($fid as $file) {

		//удалим запись о файле
		$rez = Upload ::delete($file);

		$message[] = 'Удалены привязанные файлы';

	}

	$db -> query("DELETE FROM ".$sqlname."comments WHERE id = '$id' and identity = '$identity'");

	//если удаляется все ветка
	if ($idparent == 0) {

		//Удалим подписки
		$db -> query("DELETE FROM ".$sqlname."comments_subscribe WHERE idcomment = '$id' and identity = '$identity'");

		//Удалим комментарии и файлы у ответов
		$result = $db -> getAll("SELECT * FROM ".$sqlname."comments WHERE idparent = '$id' and identity = '$identity'");
		foreach ($result as $data) {

			//удалим прикрепленные файлы в ответах
			$fids = yexplode(";", $db -> getOne("SELECT fid FROM ".$sqlname."comments WHERE id = '".$data['id']."' and identity = '$identity'"));

			foreach ($fids as $fid) {

				//удалим запись о файле
				$rez = Upload ::delete($file);

			}

			//удалим запись
			$db -> query("DELETE FROM ".$sqlname."comments WHERE id = '".$data['id']."' and identity = '$identity'");

			$db -> query("DELETE FROM ".$sqlname."comments WHERE idparent = '$id' and identity = '$identity'");

		}

		$message[] = "Обсуждение удалено";

	}
	else {

		//обновим дату последнего коммента
		$lastDatum = $db -> getOne("SELECT MAX(datum) as datum FROM ".$sqlname."comments WHERE idparent = '$idparent'");

		if ($lastDatum != '0000-00-00 00:00:00' && $idparent > 0) {

			$db -> query("UPDATE ".$sqlname."comments SET lastCommentDate = '$lastDatum' WHERE id = '$idparent' and identity = '$identity'");

		}

		$message[] = "Комментарий удален";

	}

	$message = yimplode("<br>", $message);

	print json_encode_cyr([
		"result"   => $message,
		"id"       => $id,
		"idparent" => $idparent
	]);

	exit();

}

if ($action == "delete.card") {

	$comid = (int)$_REQUEST['id'];

	$r = Comments ::delete($comid);

	print json_encode_cyr([
		"result"   => $message,
		"id"       => $comid,
		"idparent" => (int)$r['idparent']
	]);

	exit();

}
if ($action == "delete.file") {

	$fid = (int)$_GET['fid'];
	$id = (int)$_REQUEST['id'];

	Comments ::deleteFile($id, $fid);

	exit();

}