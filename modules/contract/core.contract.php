<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Akt;
use Salesman\Deal;
use Salesman\Document;
use Salesman\Guides;

error_reporting(E_ERROR);
ini_set('display_errors', 1);

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

if ($action == "contract.edit") {

	$deid        = (int)$_REQUEST['deid'];
	$clid        = (int)$_REQUEST['clid'];
	$pid         = (int)$_REQUEST['pid'];
	$did         = (int)$_REQUEST['did'];
	$des         = untag($_REQUEST['des']);
	$datum_start = untag($_REQUEST['datum_start']);
	$datum_end   = untag($_REQUEST['datum_end']);
	$number      = untag($_REQUEST['dnumber']);
	$idtype      = (int)$_REQUEST['idtype'];
	$title       = $_REQUEST['title'];
	$mcid        = (int)$_REQUEST['mcid'];
	$status      = $_REQUEST['status'];

	$newstep = $_REQUEST['newstep'];

	$message  = $err = [];
	$getPDF   = $_REQUEST['getPDF'];
	$template = $_REQUEST['template'];

	$rez  = new Document();

	if ($deid > 0) {

		//загрузим файлы
		$data = $rez -> upload();

		//добавим сообщения
		$message[] = yimplode(",", $data['message']);
		$err[]     = yimplode(",", $data['error']);

		$ftitle2 = yexplode(";", $data['data']['ftitle']);
		$fname2  = yexplode(";", $data['data']['fname']);
		$ftype2  = yexplode(";", $data['data']['ftype']);

		//добавим старые файлы в массив
		$fold      = $db -> getRow("select * from ".$sqlname."contract WHERE deid = '$deid' and identity = '$identity'");
		$fnameOld  = yexplode(";", $fold["fname"]);
		$ftitleOld = yexplode(";", $fold["ftitle"]);
		$ftypeOld  = yexplode(";", $fold["ftype"]);
		$oldstatus = $fold['status'];

		foreach ($ftitleOld as $j => $name) {

			$ftitle2[] = $name;
			$fname2[]  = $fnameOld[ $j ];
			$ftype2[]  = $ftypeOld[ $j ];

		}

		//массив новых файлов
		$ftitle = implode(";", $ftitle2);
		$fname  = implode(";", $fname2);
		$ftype  = implode(";", $ftype2);

		$data = [
			'clid'        => $clid,
			'pid'         => $pid,
			'did'         => $did,
			'number'      => $number,
			'datum_start' => $datum_start,
			'datum_end'   => $datum_end,
			'des'         => $des,
			'ftitle'      => $ftitle,
			'fname'       => $fname,
			'ftype'       => $ftype,
			'title'       => $title,
			'mcid'        => $mcid,
			'signer'      => (int)$_REQUEST['signer'],
			"post"        => $_REQUEST
		];

		//обновляем документ
		$update = $rez -> edit($deid, $data);

		//print_r($update['message']);
		//print_r($update['error']);

		$message[] = yimplode(", ", $update['message']);
		$err[]     = yimplode(", ", $update['error']);

		//генерируем документ по шаблону
		if ($template != '') {
			$message[] = $rez -> generate( $deid, [
				"template" => $template,
				"append"   => true,
				"getPDF"   => $getPDF
			] );
		}

		//print_r($message);

		$rezult = [
			"result" => "Выполнено<br>".yimplode('<br>', $message),
			"error"  => yimplode('<br>', $err),
			"did"    => $did
		];

	}
	else {

		//загрузим файлы
		$upload = $rez -> upload();

		//print_r($upload);

		$result        = $db -> getRow("SELECT * FROM ".$sqlname."contract_type where id = '$idtype' and identity = '$identity'");
		$type          = $result["type"];
		$num           = $result["num"];
		$format        = $result["format"];
		$contractTitle = $result["title"];

		/**
		 * Вносим изменения в сделку
		 */
		$deal = $_REQUEST['dogovor'];

		if (!empty($deal)) {

			$d = new Deal();
			$r = $d -> update($did, $deal);

		}

		if ($type == 'get_dogovor') {
			$number = ($GLOBALS['contract_format'] == '') ? untag( $_REQUEST['dnumber'] ) : generate_num( 'contract' );
		}
		else {
			$number = ($format == '') ? untag( $_REQUEST['dnumber'] ) : genDocsNum( $idtype );
		}

		//добавим сообщения
		$message[] = yimplode(",", $upload['message']);

		$ftitle = yimplode(";", $upload['data']['ftitle']);
		$fname  = yimplode(";", $upload['data']['fname']);
		$ftype  = yimplode(";", $upload['data']['ftype']);

		$data = [
			'datum'       => current_datumtime(),
			'number'      => $number,
			'datum_start' => $datum_start,
			'datum_end'   => $datum_end,
			'des'         => $des,
			'clid'        => $clid,
			'payer'       => (int)$payer,
			'pid'         => $pid,
			'did'         => $did,
			'ftitle'      => $ftitle,
			'fname'       => $fname,
			'ftype'       => $ftype,
			'iduser'      => (int)$iduser1,
			'title'       => $title,
			'idtype'      => $idtype,
			'mcid'        => $mcid,
			'signer'      => (int)$_REQUEST['signer'],
			'status'      => ($status > 0) ? $status : '0',
			'identity'    => (int)$identity,
			"post"        => $_REQUEST
		];

		//добавляем документ
		$update = $rez -> edit(0, $data);
		$deid   = $update['id'];

		//array_merge($message, $update['message'], $update['error']);
		$message[] = yimplode(",", $update['message']);
		$err[]     = yimplode(",", $update['error']);

		//привяжем к сделке, если она указана
		if ($did > 0 && $deid > 0 && $type == 'get_dogovor') {

			//привяжем договор ко всем платежам
			$crids = $db -> getCol("SELECT crid FROM ".$sqlname."credit WHERE did = '$did' AND identity = '$identity'");

			if (!empty($crids)) {
				$db -> query( "UPDATE ".$sqlname."credit SET ?u WHERE crid IN (".yimplode( ",", $crids ).") AND identity = '$identity'", ['invoice_chek' => $number] );
			}

		}

		//генерируем документ по шаблону
		if ($template != '') {
			$message[] = $rez -> generate( $deid, [
				"template" => $template,
				"append"   => true,
				"getPDF"   => $getPDF
			] );
		}

		$oldstep = getDogData($did, 'idcategory');

		//изменим этап сделки
		if ($newstep > 0 && $oldstep != $newstep) {

			$params = [
				"did"         => $did,
				"description" => "Добавлен договор",
				"step"        => $newstep
			];

			$deal = new Deal();
			$info = $deal -> changestep($did, $params);

			if ($info['error'] == '') {
				$message[] = $info['result'];
			}
			else {
				$err[] = $info['error'];
			}

		}

		$rezult = [
			"result" => yimplode('<br>', $message),
			"error"  => implode('<br>', $err),
			"did"    => $did
		];

	}

	print json_encode_cyr($rezult);

}

if ($action == "contract.status") {

	$deid   = (int)$_REQUEST['deid'];
	$status = $_REQUEST['status'];
	$des    = $_REQUEST['description'];

	$message = [];

	$oldstatus = $db -> getOne("select `status` from ".$sqlname."contract WHERE deid = '$deid' and identity = '$identity'");

	$data = [
		'status'    => ($status > 0) ? $status : '0',
		'oldstatus' => $oldstatus,
		'user'      => $iduser1,
		'des'       => $des,
		"subaction" => 'status'
	];

	//обновляем документ
	$rez    = new Document();
	$update = $rez -> edit($deid, $data);

	print '{"result":"Выполнено","error":"'.yimplode("<br>", $update['error']).'","did":"'.$did.'"}';

	exit();

}

if ($action == "contract.delete") {

	$deid = (int)$_REQUEST['id'];

	$rez    = new Document();
	$delete = $rez -> delete($deid);

	print '{"result":"'.$delete['message'].'","error":"'.$delete['error'].'","did":"'.$did.'"}';

	exit();
}

if ($action == 'akt.edit') {

	$deid = $_REQUEST['deid'];

	//print_r($_REQUEST['speka']);
	//exit();

	if ($deid > 0) {

		$akt = new Akt();
		$rez = $akt -> edit($deid, $_REQUEST);

		$rezult = [
			"result" => "Сделано",
			"error"  => yimplode("<br>", $rez['error']['text']),
			"did"    => $rez['did']
		];

	}
	else {

		$akt = new Akt();
		$rez = $akt -> edit(0, $_REQUEST);

		$message = $rez['result'];
		$err     = implode("<br>", $rez['error']['text']);

		$rezult = [
			"result" => $message,
			"error"  => $err,
			"did"    => $rez['did']
		];

	}

	print json_encode_cyr($rezult);

	exit();

}

if ($action == 'akt.add.old') {

	$akt = new Akt();
	$rez = $akt -> edit(0, $_REQUEST);

	$message = $rez['result'];
	$err     = yimplode("<br>", $rez['error']['text']);

	print '{"result":"'.$message.'","error":"'.$err.'","did":"'.$rez['did'].'"}';

	exit();

}
if ($action == 'akt.edit.old') {

	$deid = $_REQUEST['deid'];

	$akt = new Akt();
	$rez = $akt -> edit($deid, $_REQUEST);

	print '{"result":"Сделано","error":"'.yimplode("<br>", $rez['error']['text']).'","did":"'.$rez['did'].'"}';

	exit();
}

if ($action == 'akt.delete') {

	$deid = $_REQUEST['deid'];

	$akt = new Akt();
	$rez = $akt -> delete($deid);

	print '{"result":"Сделано","error":"'.yimplode("<br>", $rez['error']['text']).'","did":"'.$_REQUEST['did'].'"}';

	exit();
}

if ($action == 'akt.mail') {

	$did  = (int)$_REQUEST['did'];
	$deid = (int)$_REQUEST['deid'];

	$akt = new Akt();
	$rez = $akt -> mail($deid, $_REQUEST);

	$msg = yimplode("<br>", $rez['text']);

	print '{"result":"Сделано","error":"'.$msg.'","did":"'.$did.'"}';

	exit();

}

if ($action == 'akt.link') {

	$deid = (int)$_REQUEST['deid'];
	$did  = (int)$_REQUEST['did'];

	$akt = new Akt();
	$rez = $akt -> link($deid, $_REQUEST);

	$d = ["files" => $rez['data']];

	print json_encode_cyr($d);

	exit();

}

if ($action == 'akt.export') {

	$dstart = $_REQUEST['dstart'];
	$dend   = $_REQUEST['dend'];
	$mc     = $_REQUEST['mc'];

	$per     = '';
	$mycomps = Guides ::myComps();

	$otchet[] = [
		"Номер акта",
		"Дата акта",
		"Сумма",
		"Договор",
		"Плательщик",
		"ИНН",
		"Сделка",
		"Ответственный",
		"Компания"
	];

	if ($dstart) {
		$per .= " and (".$sqlname."contract.datum BETWEEN '".$dstart." 00:00:01' and '".$dend." 23:59:59')";
	}

	if ($mc > 0) {
		$per .= " and ".$sqlname."dogovor.mcid = '$mc'";
	}

	$query = "
		SELECT 
			".$sqlname."contract.deid, 
			".$sqlname."contract.datum, 
			".$sqlname."contract.did, 
			".$sqlname."contract.payer, 
			".$sqlname."contract.idtype, 
			".$sqlname."contract.crid, 
			".$sqlname."contract.number, 
			".$sqlname."dogovor.mcid as mc,
			".$sqlname."dogovor.title as deal,
			".$sqlname."dogovor.iduser as dealuser
		FROM ".$sqlname."contract 
			LEFT JOIN ".$sqlname."dogovor ON ".$sqlname."contract.did = ".$sqlname."dogovor.did
		WHERE 
			".$sqlname."contract.idtype IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('get_akt','get_aktper') and identity = '$identity')
			$per and 
			".$sqlname."contract.identity = '$identity' 
		ORDER BY ".$sqlname."contract.datum DESC
	";

	$result = $db -> query($query);

	//print $db -> lastQuery();
	//exit();

	while ($data = $db -> fetch($result)) {

		$type = $db -> getOne("SELECT type FROM ".$sqlname."contract_type where id = '".$data['idtype']."' and identity = '$identity'");

		// учитываем частичную отгрузку
		if ($type == 'get_akt') {

			//$kol = getDogData( $data['did'], 'kol' );
			$akt = Akt::getAktSpeka($data['deid']);
			$kol = $akt['aktSummaItog'];

		}

		if ($type == 'get_aktper') {
			$kol = $db -> getOne( "SELECT summa_credit FROM ".$sqlname."credit where crid = '".$data['crid']."' and identity = '$identity'" );
		}

		$deid = getDogData($data['did'], 'dog_num');

		if($deid > 0){
			$contract = Document::info($deid);
		}
		else{
			$contract['number'] = '';
		}

		$recv = get_client_recv($data['payer'], 'yes');

		$otchet[] = [
			$data['number'],
			get_smdate($data['datum']),
			$kol,
			$contract['number'],
			$recv['castUrName'],
			$recv['castInn'],
			$data['deal'],
			current_user($data['dealuser']),
			$mycomps[ $data['mc'] ]
		];

	}

	/*
	$xls = new Excel_XML('UTF-8', true, 'Akts');
	$xls -> addArray($otchet);
	$xls -> generateXML('Akts');
	*/

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.akts.xlsx');

	exit();

}

// выводит в форму добавления/редактирования Акта спецификацию для частичной отгрузки
if ($action == 'akt.pozitions'){

	$did  = (int)$_REQUEST['did'];
	$deid = (int)$_REQUEST['deid'];

	$list = Akt::getPozition($deid, $did);

	//print_r($list);

	print json_encode_cyr($list);

}

if ($action == 'payment.export') {

	$dstart = $_REQUEST['dstart'];
	$dend   = $_REQUEST['dend'];

	$otchet[] = [
		"Номер счета",
		"Дата счета",
		"Дата план.",
		"Дата факт.",
		"Сумма",
		"Ответственный по сделке",
		"Автор счета"
	];

	//print "SELECT * FROM ".$sqlname."credit WHERE crid > 0 and (datum BETWEEN '".$dstart." 00:00:01' and '".$dend." 23:59:59') ".$sort1." and identity = '$identity' ORDER BY datum DESC";

	$result = $db -> query("SELECT * FROM ".$sqlname."credit WHERE crid > 0 and (datum BETWEEN '".$dstart." 00:00:01' and '".$dend." 23:59:59') ".$sort1." and identity = '$identity' ORDER BY datum DESC");
	while ($data = $db -> fetch($result)) {

		$iduser = $db -> getOne("SELECT iduser FROM ".$sqlname."dogovor WHERE did = '".$data['did']."' and identity = '$identity'");

		$otchet[] = [
			$data['invoice'],
			$data['datum'],
			$data['datum_credit'],
			$data['invoice_date'],
			$data['summa_credit'],
			current_user($iduser),
			current_user($data['iduser'])
		];

	}

	/*
	$xls = new Excel_XML('UTF-8', false, 'Payments');
	$xls -> addArray($otchet);
	$xls -> generateXML('Payments');
	*/

	Shuchkin\SimpleXLSXGen::fromArray( $otchet )->downloadAs('export.payments.xlsx');

	exit();

}

if ($action == 'getpdf') {

	$fid                   = (int)$_REQUEST['fid'];
	$params['file']        = $_REQUEST['file'];
	$params['disposition'] = $_REQUEST['disposition'];
	$params['name']        = $_REQUEST['name'];
	$params['deid']        = (int)$_REQUEST['deid'];

	//$newfile = doc2PDF($fid, $file, $disposition, $name, $deid);

	$rez     = new Document();
	$newfile = $rez -> doc2PDF($fid, $params);

	print $newfile;

	exit();

}