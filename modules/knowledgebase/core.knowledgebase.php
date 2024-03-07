<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Knowledgebase;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];

$knowledgebase = new Knowledgebase();

if ($action == "delete") {

	print $knowledgebase -> delete($_REQUEST['id']);

	exit();

}

if ($action == "edit") {

	$id = (int)$_REQUEST['id'];

	$r = $knowledgebase -> edit($id, [
		"title"    => $_REQUEST['title'],
		"content"  => $_REQUEST['content'],
		"keywords" => $_REQUEST['keywords'],
		"idcat"    => (int)$_REQUEST['idcat'],
		"newcat"   => $_REQUEST['newcat'],
		"active"   => ( $_REQUEST['active'] != 'no' ) ? "yes" : "no",
	]);

	print json_encode_cyr($r);

	exit();

}
if ($action == "edit.old") {

	$id             = (int)$_REQUEST['id'];
	$newcat         = $_REQUEST['newcat'];
	$kb['title']    = untag($_REQUEST['title']);
	$kb['content']  = htmlspecialchars(str_replace([
		"\\r",
		"\\n"
	], "", str_replace(['\"'], '"', $_REQUEST['content'])));
	$kb['keywords'] = $_REQUEST['keywords'];
	$kb['active']   = ( $_REQUEST['active'] != 'no' ) ? "yes" : "no";
	$kb['idcat']    = $_REQUEST['idcat'];

	$word = $r = [];

	//Массив имеющихя тегов
	$tags = $db -> getCol("SELECT DISTINCT name FROM {$sqlname}kbtags WHERE identity = '$identity'");

	$qs = yexplode(",", $kb['keywords']);

	foreach ($qs as $q) {

		if (trim($q) != '') {

			$word = texttosmall(trim($q));

			if (!in_array($word, $tags)) {

				$db -> query("INSERT INTO {$sqlname}kbtags SET ?u", [
					"name"     => $word,
					"identity" => $identity
				]);

			}

		}

	}
	$kw = yimplode(",", $word);

	$subid = (int)$db -> getOne("SELECT idcat FROM {$sqlname}kb WHERE idcat = '".$kb['idcat']."' and subid = '0' and identity = '$identity'");

	if (!empty($newcat)) {

		$kb['idcat'] = (int)$db -> getOne("SELECT idcat FROM {$sqlname}kb WHERE title LIKE '$newcat' and identity = '$identity'");

		if ($kb['idcat'] == 0) {

			$db -> query("INSERT INTO {$sqlname}kb SET ?u", [
				"subid"    => $kb['idcat'],
				"title"    => $newcat,
				"identity" => $identity
			]);
			$kb['idcat'] = $db -> insertId();

		}

	}

	if ($id == 0) {

		$kb['count']    = 0;
		$kb['author']   = $iduser1;
		$kb['identity'] = $identity;
		$kb['datum']    = current_datumtime();

		$db -> query("INSERT INTO {$sqlname}knowledgebase SET ?u", $kb);
		$id = $db -> insertId();

		$mes = 'Готово';

		$r = [
			"id"  => $id,
			"mes" => $mes
		];

	}
	else {

		$author = $db -> getOne("SELECT author FROM {$sqlname}knowledgebase WHERE id = '$id' and identity = '$identity'") + 0;
		if ($author == 0) {
			$kb['author'] = $iduser1;
		}

		$db -> query("UPDATE {$sqlname}knowledgebase SET ?u WHERE id = '".$id."'", $kb);

		$mes = 'Готово';

		$r = [
			"id"  => $id,
			"mes" => $mes
		];

	}

	print json_encode_cyr($r);

	exit();
}

if ($action == "pin") {

	$id  = (int)$_REQUEST['id'];
	$pin = 'yes';

	print $knowledgebase -> pin($id, $pin);

	exit();

}
if ($action == "unpin") {

	$id  = (int)$_REQUEST['id'];

	print $knowledgebase -> pin($id);

	exit();

}

if ($action == "cat.delete") {

	$id = (int)$_REQUEST['id'];

	$knowledgebase -> categoryDelete($id);

	print json_encode_cyr([
		"id"  => $id,
		"mes" => 'Готово'
	]);

	exit();
}
if ($action == "cat.edit") {

	$idcat = (int)$_REQUEST['idcat'];

	$id = $knowledgebase -> categoryEdit($idcat, $_REQUEST);

	print json_encode_cyr([
		"id"  => $id,
		"mes" => 'Готово'
	]);

	exit();

}

if ($action == "tags") {

	$list = $knowledgebase -> taglist(NULL, true);

	foreach ($list as $data) {

		print '<div class="tags" data-tag="'.$data['tag'].'">'.$data['tag'].'</div>';

	}

	exit();

}

if ($action == "taglist") {

	$q = texttosmall($_REQUEST['q']);

	$list = $knowledgebase -> taglist($_REQUEST['q']);
	foreach ($list as $data) {

		echo $data['tag']."\n";

	}

	exit();
}
if ($action == "catlist") {

	$idcat = (int)$_REQUEST['id'];

	$r = $knowledgebase -> categorylist($idcat);

	print '<a href="javascript:void(0)" data-id="" data-title="" class="'.$ss.'"><i class="icon-folder blue"></i>&nbsp;[все]</a>';

	foreach ($r as $item){

		print '<a href="javascript:void(0)" class="'.$item['fol'].'" data-id="'.$item['id'].'" data-title="'.$item['title'].'"><span class="ellipsis">'.($item['level'] == 0 ? '' : '&nbsp;<div class="strelka w5 ml10 mr10"></div>').'<i class="icon-folder blue"></i>&nbsp;'.$item['title'].'</span></a>';

	}

	exit();

}