<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Upload;

error_reporting( 0 );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

// Анонимный запрос: inc/auth.php без cookie сессии продолжает работу с iduser1 = 0
// (так задумано для вебхуков), поэтому обработчик карточки отказывает сам.
if ((int)$iduser1 <= 0) {

	http_response_code(403);

	print 'Доступ запрещен';

	exit();

}

$thisfile = basename( __FILE__ );

// Read/Write-IDOR (AUDIT, раунд 5/8): файл и его удаление доступны только тем, кому
// доступна запись, к которой файл приложен. Номер файла перебирается тривиально.
$ncFid  = (int)($_REQUEST['fid'] ?? 0);
$ncFile = $ncFid > 0
	? (array)$db -> getRow( "SELECT clid, pid, did FROM {$sqlname}file WHERE fid = ?i and identity = ?i", $ncFid, (int)$identity )
	: [];

$ncClid = (int)($ncFile['clid'] ?? 0);
$ncPid  = (int)($ncFile['pid'] ?? 0);
$ncDid  = (int)($ncFile['did'] ?? 0);

if ( ( $ncClid > 0 || $ncPid > 0 || $ncDid > 0 ) && !can_read_record( $ncClid, $ncPid, $ncDid ) ) {

	http_response_code( 403 );

	print 'Доступ запрещен';

	exit();

}

$cid    = (int)$_REQUEST['cid'];
$action = $_REQUEST['action'];

if ( $action == "delete" ) {

	// fid — только числом: раньше строка из $_GET уходила в SQL как есть, а гейт доступа
	// выше считает (int)$_REQUEST['fid'] — то есть проверялся один файл, а удалиться
	// могли другие (вплоть до всех файлов аккаунта через fid=1' OR '1'='1).
	$fid = (int)$_GET['fid'];

	// Удаление — общим методом класса, как в файловом блоке карточки и в комментариях.
	// Свой разбор, который здесь был, ошибался трижды: путь к файлу считался от каталога
	// скрипта ("../files/..." → content/files/...), поэтому физический файл оставался на
	// диске; файл удалялся с диска, даже если на него ссылались другие записи; список
	// файлов в истории собирался через неинициализированный $fid2 — в PHP 8 это фатальная
	// ошибка уже ПОСЛЕ удаления строки, поэтому клиент получал 500.
	Upload ::delete( $fid );

}

$fidd = $db -> getOne( "select fid from ".$sqlname."history WHERE cid = '$cid' and identity = '$identity'" );
$fids = yexplode( ";", $fidd );
//print $db -> lastQuery();

if ( !empty( $fids ) ) {

	foreach ( $fids as $fid ) {

		$result2 = $db -> getRow( "select * from ".$sqlname."file WHERE fid = '$fid' and identity = '$identity'" );
		$ftitle  = $result2["ftitle"];
		$fname   = $result2["fname"];

		print '<div class="infodiv flex-string">'.get_icon2( $ftitle ).'&nbsp;'.$ftitle.'&nbsp;<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите Удалить файл?\nФайл будет Удален из системы.\');if (cf)refresh(\'filelist\', \'/content/card/fileview.php?cid='.$cid.'&fid='.$fid.'&action=delete\');" title="Удалить"><i class="icon-cancel red"></i></A>&nbsp;</div>';

	}

	print '<input name="fid_old" id="fid_old" type="hidden" value="'.yimplode( ";", $fids ).'">';

}