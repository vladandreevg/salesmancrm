<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/*этот скрипт отдает внутренний номер сотрудника, который закреплен за звонящим клиентом*/
error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

if(strlen($_REQUEST['phone']) == 11 || strlen($_REQUEST['phone']) == 8){
	$phone = substr(preg_replace("/[^0-9]/", "", $_REQUEST['phone']), 1);//уберем первый символ номера, т.к. он м.б. и 7 и 8
}
else {
	$phone = $_REQUEST['phone'];
}

$apikey = $_REQUEST['apikey'];

if( $isCloud ){

	$result = $db -> getRow("SELECT id, api_key FROM ".$sqlname."settings WHERE api_key = '".$apikey."'");
	$identity = (int)$result['id'];
	$api_key = $result['api_key'];

	if(!$identity){
		print 'Не верный API key';
		exit();
	}
}

/**
 * найдем ответственного, его номер $phoneIN
 * параметры:
 * $phone - номер телефона
 * 1 true - выводить значение CallerID с телефоном: Владислав <79223289466>
 * 2 true - выводить имя в транслите
 * 3 true - возвращать результат в виде json-массива
 */
$callerID = getxCallerID($phone,true,true);

print $callerID['phonein'];

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