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
use Salesman\Document;
use Salesman\Invoice;

error_reporting( E_ERROR );
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$view   = $_REQUEST['view'];


if ( $action == 'invoice.print' ) {

	$crid     = (int)$_REQUEST['crid'];
	$tip      = $_REQUEST['tip'];
	$download = $_REQUEST['download'];
	$nosignat = $_REQUEST['nosignat'];

	if ( $download != 'yes' && $tip == 'pdf' ) {
		$download = 'view';
	}

	$inv = new Invoice();

	if ( $tip == 'print' ) {

		print $inv -> getInvoice( $crid, [
			'tip'      => $tip,
			'nosignat' => $nosignat,
			'download' => $download
		] );

	}
	elseif ( $tip == 'pdf' ) {

		$inv -> getInvoice( $crid, [
			'tip'      => $tip,
			'nosignat' => $nosignat,
			'download' => $download
		] );

	}
	/**
	 * эта часть не работает
	 * т.к. не возможно соблюсти форматирование
	 */
	elseif ( $tip == 'docx' ) {

		$inv -> getInvoice( $crid, [
			'tip'      => $tip,
			'nosignat' => $nosignat,
			'download' => $download
		] );

	}

	exit();

}
if ( $action == 'akt.print' ) {

	$did      = (int)$_REQUEST['did'];
	$tip      = $_REQUEST['tip'];
	$download = $_REQUEST['download'];
	$temp     = $_REQUEST['temp'];
	$deid     = (int)$_REQUEST['deid'];
	$status   = (int)$_REQUEST['status'];
	$nosignat = $_REQUEST['nosignat'];

	$akt = new Akt();

	/**
	 * Изменим статус
	 */
	if ( $status > 0 ) {

		$oldstatus = $db -> getOne( "select `status` from ".$sqlname."contract WHERE deid = '$deid' and identity = '$identity'" );

		$data = [
			'status'    => ($status > 0) ? $status : 0,
			'oldstatus' => (int)$oldstatus,
			'user'      => $iduser1,
			'des'       => $des
		];

		//обновляем документ
		$rez    = new Document();
		$update = $rez -> edit( $deid, $data );

	}

	$nosignat = $nosignat == 'yes';

	if ( $tip == 'print' ) {

		print $rez = $akt -> getAkt( $deid, [
			'tip'      => $tip,
			'nosignat' => $nosignat,
			'temp'     => $temp
		] );

	}
	if ( $tip == 'pdf' ) {

		$download = ($download != 'yes') ? 'view' : $download;

		$akt -> getAkt( $deid, [
			'tip'      => $tip,
			'download' => $download,
			'nosignat' => $nosignat,
			'temp'     => $temp
		] );

	}

	exit();

}