<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/**
 * Подключение к БД
 */

require_once dirname( __DIR__ )."/vendor/autoload.php";

global $opts;
global $dbhostname;
global $dbusername;
global $dbpassword;
global $database;
global $sqlname;
global $identity;
global $iduser1;

$opts = [
	'host'    => $dbhostname,
	'user'    => $dbusername,
	'pass'    => $dbpassword,
	'db'      => $database,
	'errmode' => 'exception',
	'charset' => 'UTF8'
];

try {

	$db = new SafeMySQL($opts);

	if ($_COOKIE['ses']) {

		$result = (array)$db -> getRow("SELECT * FROM {$sqlname}user WHERE ses='".$_COOKIE['ses']."'");
		if (!empty($result)) {

			$iduser1 = $result["iduser"];
			$tipuser = $result["tip"];
			$mid     = $result["mid"];
			$login   = $result["login"];
			$tzone   = $result["tzone"];
			$isadmin = $result["isadmin"];

		}

	}
	if( (int)$_COOKIE[ 'old' ] > 0) {

		$iduser1 = (int)$_COOKIE[ 'asuser' ];

		$result = (array)$db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'");
		$iduser1   = $result["iduser"];
		$usertitle = $result["title"];
		$tipuser   = $result["tip"];
		$mid       = $result["mid"];
		$login     = $result["login"];
		$identity  = $result["identity"];
		$isadmin   = $result["isadmin"];
		$tzone     = $result["tzone"];

	}

	//$db -> query("SET NAMES 'utf8', collation_connection='utf8_general_ci', character_set_client='utf8', character_set_database='utf8', character_set_server='utf8', character_set_results='utf8'");

	try {

		$db -> query( "SET session sql_mode='ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES'" );

	}
	catch (Exception $e){

	}

	$db -> query('SET wait_timeout=100');

}
catch (Exception $e){

	print $err[] = 'Ошибка подключения к БД: '. $e-> getMessage() .'. Рекомендуем проверить параметры подключения к БД в файле "inc/config.php".';

	exit();

}