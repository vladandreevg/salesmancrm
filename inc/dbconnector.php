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

		$result = (array)$db -> getRow("SELECT * FROM {$sqlname}user WHERE ses=?s", $_COOKIE['ses']);
		if (!empty($result)) {

			$iduser1 = $result["iduser"];
			$tipuser = $result["tip"];
			$mid     = $result["mid"];
			$login   = $result["login"];
			$tzone   = $result["tzone"];
			$isadmin = $result["isadmin"];

			// замещение (замена сотрудника) — только если целевой пользователь назначил
			// текущего своим замещающим (zam) и не заблокирован (secrty='yes')
			if ((int)$_COOKIE['old'] > 0 && (int)$identity > 0 && canImpersonate($db, (int)$iduser1, (int)$_COOKIE['asuser'], (int)$identity)) {

				$result = (array)$db -> getRow("SELECT * FROM {$sqlname}user WHERE iduser = ?i and identity = ?i", (int)$_COOKIE['asuser'], (int)$identity);
				$iduser1   = $result["iduser"];
				$usertitle = $result["title"];
				$tipuser   = $result["tip"];
				$mid       = $result["mid"];
				$login     = $result["login"];
				$identity  = $result["identity"];
				$isadmin   = $result["isadmin"];
				$tzone     = $result["tzone"];

			}

		}

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

/**
 * Проверка легитимности "замещения" (подмены сотрудника).
 * Действовать от имени $asUser может только пользователь $oldUser, которого
 * целевой пользователь назначил своим замещающим (поле zam), при условии,
 * что целевой аккаунт не заблокирован (secrty = 'yes').
 *
 * @param mixed $db
 * @param int   $oldUser - текущий (исходный) пользователь из сессии
 * @param int   $asUser  - пользователь, от имени которого запрошена работа
 * @param int   $identity
 *
 * @return bool
 */
function canImpersonate($db, int $oldUser, int $asUser, int $identity): bool {

	if ($oldUser < 1 || $asUser < 1 || $asUser === $oldUser || $identity < 1) {
		return false;
	}

	$cnt = (int)$db -> getOne(
		"SELECT COUNT(*) FROM {$GLOBALS['sqlname']}user WHERE iduser = ?i AND zam = ?i AND secrty = 'yes' AND identity = ?i",
		$asUser,
		$oldUser,
		$identity
	);

	return $cnt > 0;

}