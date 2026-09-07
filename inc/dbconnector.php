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

	// если объект подключения уже задан (напр., заглушка в тестах) — используем его,
	// иначе создаём реальное подключение
	$db = ( isset( $GLOBALS[ 'db' ] ) && is_object( $GLOBALS[ 'db' ] ) ) ? $GLOBALS[ 'db' ] : new SafeMySQL( $opts );

	if ($_COOKIE['ses']) {

		$result = (array)$db -> getRow("SELECT * FROM {$sqlname}user WHERE ses=?s", $_COOKIE['ses']);
		if (!empty($result)) {

			$iduser1 = $result["iduser"];
			$tipuser = $result["tip"];
			$mid     = $result["mid"];
			$login   = $result["login"];
			$tzone   = $result["tzone"];
			$isadmin = $result["isadmin"];

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

/**
 * Проверка, что целевой пользователь $asUser — это сам текущий пользователь $oldUser
 * или один из его подчиненных (по цепочке mid, любой глубины) и при этом не заблокирован
 * (secrty = 'yes'). Проверка выполняется в рамках одной identity.
 *
 * Эквивалент проверки in_array($asUser, $y) по результату User::userArray($oldUser),
 * но без зависимости от класса \Salesman\User: auth_main.php/auth.php подключаются
 * раньше inc/func.php, который регистрирует автозагрузчик классов \Salesman\*.
 *
 * @param mixed $db
 * @param int   $oldUser - текущий (исходный) пользователь из сессии
 * @param int   $asUser  - пользователь, от имени которого запрошена работа
 * @param int   $identity
 *
 * @return bool
 */
function canImpersonateSubordinate($db, int $oldUser, int $asUser, int $identity): bool {

	if ($oldUser < 1 || $asUser < 1 || $identity < 1) {
		return false;
	}

	$sqlname = $GLOBALS['sqlname'];

	// целевой пользователь должен существовать и быть активным
	// (в исходной проверке $y фильтровался по secrty = 'yes')
	$row = $db -> getRow("SELECT mid, secrty FROM {$sqlname}user WHERE iduser = ?i AND identity = ?i", $asUser, $identity);

	if (empty($row) || (string)$row['secrty'] !== 'yes') {
		return false;
	}

	// сам пользователь входит в набор userArray($oldUser) — сохраняем исходную семантику
	if ($asUser === $oldUser) {
		return true;
	}

	// поднимаемся по цепочке руководителей (mid) от asUser вверх;
	// если встречаем $oldUser — значит asUser у него в подчинении
	$seen = [ $asUser => true ];
	$u    = (int)$row['mid'];

	while ($u > 0) {

		if ($u === $oldUser) {
			return true;
		}

		// защита от зацикливания в mid-дереве
		if (isset($seen[$u])) {
			return false;
		}
		$seen[$u] = true;

		$u = (int)$db -> getOne("SELECT mid FROM {$sqlname}user WHERE iduser = ?i AND identity = ?i", $u, $identity);
	}

	return false;

}
