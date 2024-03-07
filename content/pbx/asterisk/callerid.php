<?php
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

error_reporting( E_ERROR );

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$apkey = $_REQUEST['apkey'];
$phone = $_REQUEST['phone'];
$t     = $_REQUEST['t'];

$tr = !($t == 'no');

if ( !$apkey ) {

	print 'Не указан API key';
	exit();

}

//проверим ключ
$identity = (int)$db -> getOne( "SELECT id FROM ".$sqlname."settings WHERE api_key = '$apkey'" );

if ( $identity == 0 ) {

	print "Incorrect API key";
	exit();

}

if ( $identity > 0 ) {

	/**
	 * найдем ответственного, его номер $phoneIN
	 * параметры:
	 * $phone - номер телефона
	 * 1 true - выводить значение CallerID с телефоном: Владислав <79223289466>
	 * 2 true - выводить имя в транслите
	 * 3 true - возвращать результат в виде json-массива
	 */
	$callerID = getxCallerID( $phone, true, $tr );

	//Доступные значения
	/*
	$callerID = [
		"clid", //id клиента
		"client", //название клиента
		"pid", //id контакта
		"person", //ФИО
		"iduser", //id сотрудника
		"user", //имя сотрудника
		"phonein", //внтурненний номер сотрудника
		"callerID" //имя абонента, общее значение
		]
	*/

	print $callerID['callerID'];

}
else {

	print "Incorrect API key";
	exit();

}
