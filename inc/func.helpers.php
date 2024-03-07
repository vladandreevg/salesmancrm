<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*          ver. 2023.1         */
/* ============================ */

/**
 * Набор функций, облегчающих труд разработчика :)
 * Содержит функции для быстрого получения данных сущностей системы
 * Выделено из func.php для уменьшения размера файла
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     v.1.0 (06/09/2019)
 */

/**
 * @package Func
 */

use PHPMailer\PHPMailer\Exception;
use Salesman\Budget;
use Salesman\Client;
use Salesman\Currency;
use Salesman\Deal;

/**
 * Переформатирование имени клиента
 *
 * @param $string
 *
 * @return array|string|string[]
 * @category Core
 * @package  Func
 */
function clientFormatTitle($string) {

	$names = [
		"ООО",
		"ЗАО",
		"ОАО",
		"ИП"
	];

	//Удалим все кавычки
	$string = str_replace( '\"', '', untag( $string ) );

	//Найдем первые символы названия
	$nameFirst = mb_substr( $string, 0, 3, "utf-8" );

	//Если первые символы входят в наш массив, то переставляем тип собственности назад
	if ( in_array( $nameFirst, $names ) ) {
		$string = str_replace( $nameFirst, '', $string );
		$string = trim( $string.", ".$nameFirst );
	}

	return $string;
}

/**
 * Возвращает новый период для сервисной сделки
 *
 * @param $did
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getPeriodDeal($did): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$res   = $db -> getRow( "SELECT datum_start, datum_end FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
	$start = (string)$res["datum_start"];
	$end   = (string)$res["datum_end"];

	//количество дней в месяце
	$ecount = (int)date( "t", strtotime( $end ) );

	$s = explode( "-", $start );
	$e = explode( "-", $end );

	//считаем разницу дней
	$diff = diffDate2( $end, $start );

	//формируем новый период
	$d1 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e[1], (int)$e[2] + 1, (int)$e[0] ) );
	$d2 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e[1], (int)$e[2] + 1 + (int)$diff, (int)$e[0] ) );


	/**
	 * Корректируем период
	 */

	//если начало периода 1 число месяца, то новый период делаем с 1 числа
	if ( (int)$s[2] == 1 ) {

		$d1 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e[1] + 1, 1, (int)$e[0] ) );

	}
	//если конец периода последнее число месяца, то конец нового периода делаем последнее число
	if ( (int)$e[2] == $ecount || ((int)$e[2] == 15) ) {

		$count = date( "t", strtotime( $d2 ) );
		$e2    = explode( "-", $d2 );
		$d2    = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e2[1], (int)$count, (int)$e2[0] ) );

	}

	return [
		$d1,
		$d2
	];

}

/**
 * Помогает сформировать новый период дат на основе указанного (производтся расчет количества дней в периоде)
 * - если начало периода 1 число месяца, то новый период делаем с 1 числа
 * - если конец периода последнее число месяца, то конец нового периода делаем последнее число
 *
 * @param string|null $start
 * @param string|null $end
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getPeriodSmart(string $start = NULL, string $end = NULL): array {

	if ( $start == '' ) {
		$start = current_datumtime();
	}

	if ( $end == '' ) {
		$end = current_datumtime();
	}

	//количество дней в месяце
	$ecount = (int)date( "t", strtotime( $end ) );

	$s = explode( "-", $start );
	$e = explode( "-", $end );

	//считаем разницу дней
	$diff = diffDate2( $end, $start );

	//формируем новый период
	$d1 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e[1], (int)$e[2] + 1, (int)$e[0] ) );
	$d2 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e[1], (int)$e[2] + 1 + $diff, (int)$e[0] ) );

	/**
	 * Корректируем период
	 */

	//если начало периода 1 число месяца, то новый период делаем с 1 числа
	if ( (int)$s[2] == 1 && (int)$e[2] == $ecount ) {

		$d1 = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e[1] + 1, 1, (int)$e[0] ) );

	}
	//если конец периода последнее число месяца, то конец нового периода делаем последнее число
	if ( (int)$e[2] == $ecount || ((int)$e[2] == 15) ) {

		$count = (int)date( "t", strtotime( $d2 ) );
		$e2    = explode( "-", $d2 );
		$d2    = strftime( '%Y-%m-%d', mktime( 1, 0, 0, (int)$e2[1], $count, (int)$e2[0] ) );

	}

	return [
		$d1,
		$d2
	];

}

/**
 * Усанавливает новый период для сервисной сделки
 *
 * @param int         $did
 * @param string|null $d1 - date
 * @param string|null $d2 - date
 *
 * @return string
 * @category Core
 * @package  Func
 */
function setPeriodDeal(int $did, string $d1 = NULL, string $d2 = NULL): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	//если новый период не указан, то генерируем его
	if ( $d1 == '' ) {

		$p = getPeriodDeal( $did );//получили новый период

		$d1 = $p[0];
		$d2 = $p[1];

	}

	$db -> query( "update {$sqlname}dogovor set datum_start ='$d1', datum_end ='$d2' where did = '$did' and identity = '".$identity."'" );

	return 'ok';

}

/**
 * Возвращает количество просроченных активностей по iduser
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getOldTaskCount($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];

	return $GLOBALS['db'] -> getOne( "SELECT COUNT(tid) as count FROM {$sqlname}tasks WHERE (date_format(datum, '%Y-%m-%d') < '".current_datum()."') and active = 'yes' and iduser = '$id' and identity = '".$identity."'" );

}

/**
 * @param $id
 *
 * @return array
 * @deprecated
 * Возвращает информацию по напоминанию по его id
 *
 * @category Core
 * @package  Func
 */
function get_taskinfo($id): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$task = $db -> getRow( "SELECT * FROM {$sqlname}tasks WHERE tid = '".(int)$id."' AND identity = '".$identity."'" );

	return [
		"tid"      => $task['id'],
		"title"    => $task['title'],
		"des"      => $task['des'],
		"tip"      => $task['tip'],
		"datum"    => $task['datum'],
		"totime"   => substr( $task['totime'], 0, -3 ),
		"active"   => $task['active'],
		"priority" => $task['priority'],
		"speed"    => $task['speed'],
		"iduser"   => $task['iduser'],
		"autor"    => $task['autor'],
		"clid"     => $task['clid'],
		"pid"      => $task['pid'],
		"did"      => $task['did']
	];

}

/**
 * Возвращает информацию об активности
 *
 * @param $id
 *
 * @return array
 * @category Core
 * @package  Func
 */
function get_historyinfo($id): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$history = $db -> getRow( "SELECT * FROM {$sqlname}history WHERE cid = '".(int)$id."' AND identity = '".$identity."'" );

	return [
		"сid"    => (int)$history['cid'],
		"des"    => $history['des'],
		"tip"    => $history['tip'],
		"datum"  => $history['datum'],
		"iduser" => $history['iduser'],
		"clid"   => (int)$history['clid'],
		"pid"    => (int)$history['pid'],
		"did"    => (int)$history['did'],
		"fid"    => $history['fid'],
		"files"  => yexplode( ";", $history['fid'] ),
	];

}

/**
 * Возвращает доступ к Клиенту, Контакту или Сделке для текущего сотрудника
 *
 * @param int|NULL $clidd
 * @param int|NULL $pidd
 * @param int|NULL $didd
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_accesse(int $clidd = NULL, int $pidd = NULL, int $didd = NULL): string {

	global $userSettings;

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$tipuser  = $GLOBALS['tipuser'];
	$iduser1  = $GLOBALS['iduser1'];
	$isadmin  = $GLOBALS['isadmin'];

	$acs_vieww = $db -> getOne( "SELECT acs_view FROM {$sqlname}settings WHERE id = '$identity'" );

	$allow = 'no';
	$dostup = $sort = [];
	$juser = 0;

	//print_r($userSettings);

	//находим id Ответственного за искомое
	if ( (int)$clidd > 0 ) {

		$juser = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}clientcat WHERE clid = '$clidd' AND identity = '$identity'" );

	}
	elseif ( (int)$pidd > 0 ) {

		$juser = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}personcat WHERE pid = '$pidd' AND identity = '$identity'" );

	}
	elseif ( (int)$didd > 0 ) {

		$juser = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}dogovor WHERE did = '$didd' AND identity = '$identity'" );

	}

	$xfilter = get_people( (int)$userSettings['filterAllBy'], "yes" );

	//print $juser;
	//print_r($xfilter);
	//printf("ucard: %s, user: %s", $juser, $iduser1);

	if ( (int)$clidd > 0 ) {

		//найдем все организации, к которым у тек.сотрудника есть доступ
		$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE clid = '$clidd' AND identity = '$identity'" );

		// доступы к карточке в пределах указанного руководителя
		if( $userSettings['filterAllByClientEdit'] == 'yes' && in_array($juser, $xfilter ) && in_array($iduser1, $xfilter ) ){
			//$dostup[] = $iduser1;
			return "yes";
		}

	}
	elseif ( (int)$pidd > 0 ) {

		$clid = getPersonData( (int)$pidd, 'clid' );

		//найдем все организации, к которым у тек.сотрудника есть доступ
		$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE clid = '$clid' AND identity = '$identity'" );

	}
	elseif ( (int)$didd > 0 ) {

		//найдем все организации, к которым у тек.сотрудника есть доступ
		$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$didd' AND identity = '$identity'" );

		// доступы к карточке в пределах указанного руководителя
		if( $userSettings['filterAllByDealEdit'] == 'yes' && in_array($juser, $xfilter ) && in_array($iduser1, $xfilter )  ){
			//$dostup[] = $iduser1;
			return "yes";
		}

	}

	//print_r($dostup);

	//находим всех существующих пользователей
	$u = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

	//если такого юзера уже нет в списке (он удален), то доступ открыт
	if ( !in_array( $juser, (array)$u ) ) {
		$allow = 'yes';
	}
	else {

		if ( in_array( $tipuser, [
			"Руководитель организации",
			"Поддержка продаж",
			"Руководитель с доступом"
		] ) ) {
			$sort = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );
		}
		elseif ( in_array( $tipuser, [
			"Руководитель отдела",
			"Руководитель подразделения"
		] ) ) {

			$sort[] = $iduser1;

			$s1 = (array)$db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid = '$iduser1' AND identity = '$identity'" );

			$sort = array_merge( $sort, $s1 );

			$s2 = (!empty( $s1 )) ? (array)$db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid IN (".implode( ",", $s1 ).") AND identity = '$identity'" ) : [];

			$sort = array_merge( $sort, $s2 );

		}
		elseif ( $tipuser == "Менеджер продаж" ) {
			$sort[] = $iduser1;
		}
		else {
			$sort[] = $iduser1;
		}

		//print_r($sort);

		if ( !empty( $sort ) ) {
			$allow = (in_array( $juser, $sort ) || $juser == 0) ? 'yes' : 'no';
		}
		if ( !empty( $dostup ) ) {
			$allow = ((in_array( $iduser1, $dostup ) || $juser == 0) || $allow == 'yes') ? 'yes' : 'no';
		}
		if ( $acs_vieww == 'on' ) {
			$allow = 'yes';
		}
		if ( $juser == $iduser1 ) {
			$allow = 'yes';
		}

	}

	if ( $tipuser == 'Администратор' || $isadmin == 'on' ) {
		$allow = 'yes';
	}

	return $allow;

}

/**
 * Доступ текущего пользователя iduser1 к данным пользователя id
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_accesse_other($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$tipuser  = $GLOBALS['tipuser'];
	$iduser1  = $GLOBALS['iduser1'];
	$isadmin  = $GLOBALS['isadmin'];

	$users = [];
	$ok    = '';

	//находим всех существующих пользователей
	$userlist = (array)$db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

	if ( !in_array( $id, $userlist ) ) {
		$ok = 'yes';
	}

	if ( in_array( $tipuser, [
		"Руководитель организации",
		"Поддержка продаж",
		"Руководитель с доступом"
	] ) ) {

		//Формируем список сотрудников конкретного отдела, т.е. руководитель + подчиненные
		$users = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

	}
	elseif ( $tipuser == "Менеджер продаж" ) {
		$users[] = $iduser1;
	}
	elseif ( in_array( $tipuser, [
		"Руководитель отдела",
		"Руководитель подразделения"
	] ) ) {

		$s1 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid = '$iduser1' AND identity = '$identity'" );

		$users[] = 0;
		$users[] = $iduser1;

		if ( !empty( $s1 ) ) {

			$users = array_merge( $users, $s1 );

			$s2 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid IN (".implode( ",", $users ).") AND identity = '$identity'" );

			$users = array_merge( $users, $s2 );

		}

	}
	else {
		$users[] = $iduser1;
	}

	$allow = (in_array( $id, $users ) || $ok == 'yes') ? 'yes' : 'no';

	if ( $tipuser == 'Администратор' || $isadmin == 'on' ) {
		$allow = 'yes';
	}

	return $allow;

}

/**
 * Определяет досутп указанного сотрудника к клиенту, контакту или сделке
 *
 * @param            $iduser
 * @param array|NULL $params
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getUserAccesse($iduser, array $params = []): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$acs_vieww = $db -> getOne( "SELECT acs_view FROM {$sqlname}settings WHERE id = '$identity'" );

	$allow = 'no';
	$juser = $dostup = $sort = [];

	$r       = $db -> getRow( "SELECT tip, isadmin FROM {$sqlname}user WHERE iduser = '$iduser'" );
	$isadmin = $r['isadmin'];
	$tipuser = $r['tip'];

	//находим id Ответственного за искомое
	if ( (int)$params['clid'] > 0 ) {
		$juser = $db -> getOne( "SELECT iduser FROM {$sqlname}clientcat WHERE clid = '$params[clid]' AND identity = '$identity'" );
	}
	elseif ( (int)$params['pid'] > 0 ) {
		$juser = $db -> getOne( "SELECT iduser FROM {$sqlname}personcat WHERE pid  = '$params[pid]' AND identity = '$identity'" );
	}
	elseif ( (int)$params['did'] > 0 ) {
		$juser = $db -> getOne( "SELECT iduser FROM {$sqlname}dogovor   WHERE did  = '$params[did]' AND identity = '$identity'" );
	}

	//print $juser;
	//print $iduser1;

	if ( (int)$params['clid'] > 0 ) {

		//найдем все организации, к которым у тек.сотрудника есть доступ
		$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE clid = '$params[clid]' AND identity = '$identity'" );

	}
	elseif ( (int)$params['pid'] > 0 ) {

		$clid = getPersonData( $params['pid'], 'clid' );

		//найдем все организации, к которым у тек.сотрудника есть доступ
		$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE clid = '$clid' AND identity = '$identity'" );

	}
	elseif ( (int)$params['did'] > 0 ) {

		//найдем все организации, к которым у тек.сотрудника есть доступ
		$dostup = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' AND identity = '$identity'" );

	}

	//print_r($dostup);

	//находим всех существующих пользователей
	$u = (array)$db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

	//если такого юзера уже нет в списке (он удален), то доступ открыт
	if ( !in_array( $juser, $u ) ) {
		$allow = 'yes';
	}
	else {

		if ( in_array( $tipuser, [
			"Руководитель организации",
			"Поддержка продаж",
			"Руководитель с доступом"
		] ) ) {

			$sort = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

		}
		elseif ( in_array( $tipuser, [
			"Руководитель отдела",
			"Руководитель подразделения"
		] ) ) {

			$sort[] = $iduser;

			$s1 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid = '$iduser' AND identity = '$identity'" );

			$sort = array_merge( $sort, $s1 );

			if ( !empty( $s1 ) ) {

				$s2   = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid IN (".implode( ",", $s1 ).") AND identity = '$identity'" );
				$sort = array_merge( $sort, $s2 );

			}


		}
		else {
			$sort[] = $iduser;
		}

		if ( !empty( $sort ) ) {
			$allow = (in_array( $juser, $sort ) || $juser == 0) ? 'yes' : 'no';
		}
		if ( !empty( $dostup ) ) {
			$allow = ((in_array( $iduser, $dostup ) || $juser == 0) || $allow == 'yes') ? 'yes' : 'no';
		}
		if ( $acs_vieww == 'on' ) {
			$allow = 'yes';
		}
		if ( $juser == $iduser ) {
			$allow = 'yes';
		}

	}

	if ( $tipuser == 'Администратор' || $isadmin == 'on' ) {
		$allow = 'yes';
	}

	return $allow;

}

/**
 * Определяет доступ к управлению контрольной точкой по её id
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_cpaccesse($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$tipuser  = $GLOBALS['tipuser'];
	$iduser1  = $GLOBALS['iduser1'];
	$isadmin  = $GLOBALS['isadmin'];

	$result = $db -> getRow( "SELECT role, users FROM {$sqlname}complect_cat WHERE ccid = '$id' AND identity = '$identity'" );
	$role   = $result["role"];
	$users  = $result["users"];

	$arrole  = explode( ",", (string)$role );
	$arusers = explode( ",", (string)$users );

	$allow = 'no';

	if ( $role == '' && $users == '' ) {
		$allow = 'yes';
	}
	if ( $role != '' || $users != '' ) {

		$allow1 = ($users != '' && in_array( $iduser1, $arusers )) ? 'yes' : 'no';
		$allow2 = ($role != '' && in_array( $tipuser, $arrole )) ? 'yes' : 'no';

		if ( $allow1 == 'yes' || $allow2 == 'yes' ) {
			$allow = 'yes';
		}

	}

	if ( $isadmin == 'yes' ) {
		$allow = 'yes';
	}

	return $allow;

}

/**
 * Возвращает список подчиненных сотрудника
 *
 * @param         $iuser
 * @param string  $asarray    - "yes" возвращает массив
 * @param bool    $onlyactive - true - учитывает только активных сотрудников
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function get_people($iuser, string $asarray = "no", bool $onlyactive = false) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	global $isadmin;

	if ( $iuser < 1 ) {
		$iuser = 0;
	}

	$ao = $onlyactive ? " and secrty = 'yes'" : "";

	$itipuser = $db -> getOne( "SELECT tip FROM {$sqlname}user WHERE iduser = '$iuser' AND identity = '$identity'" );

	$users = [];

	if ( $isadmin == 'on' || in_array( $itipuser, ["Руководитель организации", "Поддержка продаж", "Руководитель с доступом", 'Администратор'] ) ) {

		$users = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity' $ao" );

		//print_r($users);

	}
	elseif ( $itipuser == "Менеджер продаж" ) {
		$users[] = $iuser;
	}
	elseif ( $itipuser == "Руководитель отдела" ) {

		$users = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid = '$iuser' $ao and identity = '$identity'" );

		$users[] = $iuser;

	}
	elseif ( $itipuser == "Руководитель подразделения" ) {

		$users[] = $iuser;

		$s1 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid = '$iuser' $ao and identity = '$identity'" );

		//print_r($s1);

		$users = array_merge( $users, $s1 );

		//print_r($users);

		if ( !empty( $s1 ) ) {

			$s2 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid IN (".implode( ",", $s1 ).") AND identity = '$identity' $ao" );

			$users = array_merge( $users, $s2 );

		}

	}
	else {
		$users[] = $iuser;
	}

	if ( $asarray != 'yes' ) {

		$rez = (!empty( $users )) ? " and iduser IN (".implode( ",", $users ).")" : " and iduser = '$iuser'";

	}
	else {
		$rez = $users;
	}

	return $rez;

}

/**
 * Возвращает список подчиненных текущего пользователя
 *
 * @param      $iuser
 * @param bool $asarray
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function get_userlist($iuser, bool $asarray = false) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$itipuser = $db -> getOne( "SELECT tip FROM {$sqlname}user WHERE iduser = '$iuser' AND identity = '$identity'" );
	$users    = [];

	if ( in_array( $itipuser, [
		"Руководитель организации",
		"Поддержка продаж",
		"Руководитель с доступом"
	] ) ) {

		$users = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

	}
	elseif ( $itipuser == "Менеджер продаж" ) {
		$users[] = $iuser;
	}
	elseif ( $itipuser == "Руководитель отдела" ) {

		$users   = $db -> getCol( "SELECT * FROM {$sqlname}user WHERE mid = '$iuser' AND identity = '$identity'" );
		$users[] = $iuser;

	}
	elseif ( $itipuser == "Руководитель подразделения" ) {

		$users[] = $iuser;

		$s1 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid = '$iuser' AND identity = '$identity'" );

		$users = array_merge( $users, $s1 );

		$s2 = $db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE mid IN (".implode( ",", $s1 ).") AND identity = '$identity'" );

		$users = array_merge( $users, $s2 );

	}
	else {
		$users[] = $iuser;
	}

	if ( $asarray ) {
		return $users;
	}

	return yimplode( ";", $users );

}

/**
 * Возвращает массив iduser пользователей
 *
 * @return array
 * @category Core
 * @package  Func
 */
function get_userarray(): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	return (array)$db -> getCol( "SELECT iduser FROM {$sqlname}user WHERE identity = '$identity'" );

}

/**
 * Список массив e-mail(-ов), с которых запрещено принимать почту
 *
 * @return array
 * @category Core
 * @package  Func
 */
function Blacklist(): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$opts     = $GLOBALS['opts'];

	/**
	 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
	 * В противном случае получим ошибку "safemysql MySQL server has gone away"
	 */
	unset( $db );
	$db = new SafeMySQL( $opts );

	return (array)$db -> getCol( "SELECT email FROM {$sqlname}ymail_blacklist WHERE identity='$identity'" );

}

/**
 * Получение имени пользователя по его iduser
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_user($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$username = $db -> getOne( "SELECT title FROM {$sqlname}user WHERE iduser = '$id' AND identity = '$identity'" );

	if ( $username == '' ) {
		$username = 'не определено';
	}

	return $username;

}

/**
 * Получение clid по сайту
 *
 * @param $name
 * @return string
 * @category Core
 * @package  Func
 */
function get_partnerbysite($name): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$partner = 'undefined';

	if ( $name ) {

		$name = str_replace( [
			"http://",
			"https://",
			"www."
		], "", $name );

		$partner = $db -> getOne( "SELECT clid FROM {$sqlname}clientcat WHERE site_url LIKE '%$name%' AND identity = '$identity'" );

	}

	return $partner;

}

/**
 * Возвращает имя пользователя
 *
 * @param        $id
 * @param string $short = "yes" для получения только Имя + Фамилия
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_user($id, string $short = 'no'): string {

	$sqlname = $GLOBALS['sqlname'];
	$opts    = $GLOBALS['opts'];

	$db = new SafeMySQL( $opts );

	$utitle = "Не определен";

	if ( (int)$id > 0 ) {

		$utitle = $db -> getOne( "SELECT title FROM {$sqlname}user WHERE iduser = '$id'" );

		if ( $utitle == '' ) {
			$utitle = "Не определен";
		}

		if ( $short == 'yes' ) {

			$u = explode( " ", (string)$utitle );
			if ( count( $u ) > 2 ) {
				$utitle = $u[0]." ".$u[1];
			}

		}

	}

	return $utitle;

}

/**
 * Возвращает iduser по логину
 *
 * @param $login
 *
 * @return int
 * @category Core
 * @package  Func
 */
function current_userbylogin($login): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( $login ) {

		$id = $db -> getOne( "SELECT iduser FROM {$sqlname}user WHERE login = '$login' AND identity = '$identity'" );

	}

	return $id;

}

/**
 * Получение логина пользователя по его iduser
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_userlogin($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$login = '';

	if ( $id > 0 ) {

		$login = $db -> getOne( "SELECT login FROM {$sqlname}user WHERE iduser = '$id' AND identity = '$identity'" );

	}

	return $login;

}

/**
 * Получение UID по iduser пользователя
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_userUID($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$uid = '';

	if ( (int)$id > 0 ) {

		$uid = (string)$db -> getOne( "SELECT uid FROM {$sqlname}user WHERE iduser = '$id' AND identity = '$identity'" );

	}

	return $uid;

}

/**
 * Получение названия клиента по его clid
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_client($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$ctitle = '';

	if ( (int)$id > 0 ) {

		$ctitle = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '$id' AND identity = '$identity'" );
		if ( $ctitle == '' ) {
			$ctitle = '--не найден--';
		}

	}

	return $ctitle;

}

/**
 * Получение имени контакта по его pid
 *
 * @param      $id
 *
 * @param bool $short
 * @return string
 * @package  Func
 * @category Core
 */
function current_person($id, bool $short = true): ?string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$person = '';

	if ( (int)$id > 0 ) {

		$person = (string)$db -> getOne( "SELECT person FROM {$sqlname}personcat WHERE pid = '$id' AND identity = '$identity'" );

		if ( $person == '' ) {
			return NULL;
		}

		if ( $short ) {

			$p      = yexplode( " ", $person );
			$person = $p[0]." ".$p[1];

		}

	}

	return $person;

}

/**
 * Название договора по его did
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_dogovor($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$dogovor = '';

	if ( (int)$id > 0 ) {

		$dogovor = $db -> getOne( "SELECT title FROM {$sqlname}dogovor WHERE did = '$id' AND identity = '$identity'" );
		if ( $dogovor == '' ) {
			$dogovor = '--не найден--';
		}

	}

	return $dogovor;

}

/**
 * Возвращает телефоны и email сотрудников
 * Применяется для фильтрации в парсере html2data()
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getUsersPhones(): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$opts     = $GLOBALS['opts'];

	//unset($db);
	$db = new SafeMySQL( $opts );

	$phones = $emails = [];

	$users = $db -> getAll( "SELECT email, phone, mob FROM {$sqlname}user WHERE identity = '$identity'" );
	foreach ( $users as $user ) {

		if ( $user['phone'] != '' ) {
			$phones[] = preparePhone( $user['phone'] );
		}
		if ( $user['mob'] != '' ) {
			$phones[] = preparePhone( $user['mob'] );
		}
		if ( $user['email'] != '' ) {
			$emails[] = $user['email'];
		}

	}

	return [
		"phone" => $phones,
		"email" => $emails
	];

}

/**
 * Получение контакта со списком телефонов в виде ссылки с учетом интеграции с телефонией
 *
 * @param      $id
 * @param bool $format - если true, то возвращает строку, иначе массив
 *
 * @return string|array
 * @category Core
 * @package  Func
 */
function getPersonWPhone($id, bool $format = true) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$str      = '';

	if ( (int)$id > 0 ) {

		$res    = $db -> getRow( "SELECT * FROM {$sqlname}personcat WHERE pid = '$id' AND identity = '$identity'" );
		$person = $res["person"];
		$tel    = $res["tel"];
		$mob    = $res["mob"];

		$plist = [];

		$tel = yexplode( ",", str_replace( ";", ",", $tel ) );
		$mob = yexplode( ",", str_replace( ";", ",", $mob ) );

		$phone = (array)array_merge( $tel, $mob );

		if ( $format ) {

			foreach ( $phone as $p ) {
				if ( $p != '' ) {
					$plist[] = "<b>".formatPhoneUrl( $p )."</b>";
				}
			}

			$phone = implode( ", ", $plist );

			return '<a href="javascript:void(0)" onclick="openPerson(\''.$id.'\')" title="В новом окне"><i class="icon-user-1 broun"></i></a><a href="javascript:void(0)" onclick="viewPerson(\''.$id.'\')" title="Просмотр">'.$person.'</a>:&nbsp;'.$phone;

		}

		return $phone;

	}

	return $str;

}

/**
 * Получение контакта со списком email в виде ссылки
 *
 * @param      $id
 * @param bool $format - если true, то возвращает строку, иначе массив
 *
 * @return string|array
 * @category Core
 * @package  Func
 */
function getPersonWMail($id, bool $format = true) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$str      = '';

	if ( (int)$id > 0 ) {

		$res    = $db -> getRow( "SELECT * FROM {$sqlname}personcat WHERE pid = '$id' AND identity = '$identity'" );
		$person = $res["person"];
		$mail   = $res["mail"];

		$plist = [];
		$mail  = (array)yexplode( ",", str_replace( ";", ",", $mail ) );

		if ( $format ) {

			foreach ( $mail as $email ) {

				if ( $email != '' ) {
					$plist[] = "<b>".link_it( $email )."</b>";
				}

			}

			$mail = implode( ", ", $plist );

			return '<a href="javascript:void(0)" onclick="openPerson(\''.$id.'\')" title="В новом окне"><i class="icon-user-1 broun"></i></a><a href="javascript:void(0)" onclick="viewPerson(\''.$id.'\')" title="Просмотр">'.$person.'</a>:&nbsp;'.$mail;

		}

		return $mail;

	}

	return $str;

}

/**
 * Получение клиента со списком телефонов в виде ссылки с учетом интеграции с телефонией
 *
 * @param      $id
 * @param bool $format - если true, то возвращает строку, иначе массив
 *
 * @return string|array
 * @category Core
 * @package  Func
 */
function getClientWPhone($id, bool $format = true) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$str      = '';

	if ( (int)$id > 0 ) {

		$res   = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE clid = '$id' AND identity = '$identity'" );
		$title = $res["title"];
		$tel   = $res["phone"];

		$plist = [];

		$phone = yexplode( ",", str_replace( ";", ",", $tel ) );

		if ( $format ) {

			foreach ( $phone as $p ) {
				if ( $p != '' ) {
					$plist[] = "<b>".formatPhoneUrl( $p )."</b>";
				}
			}
			$phone = implode( ", ", $plist );

			return '<a href="javascript:void(0)" onclick="openClient(\''.$id.'\')" title="В новом окне"><i class="icon-commerical-building broun"></i></a><a href="javascript:void(0)" onClick="viewClient(\''.$id.'\')" title="Просмотр">'.$title.'</a>:&nbsp;'.$phone;

		}

		return $phone;

	}

	return $str;

}

/**
 * Получение клиента со списком email в виде ссылки
 *
 * @param      $id
 * @param bool $format - если true, то возвращает строку, иначе массив
 *
 * @return string|array
 * @category Core
 * @package  Func
 */
function getClientWMail($id, bool $format = true) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$str      = '';

	if ( (int)$id > 0 ) {

		$res   = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE clid = '$id' AND identity = '$identity'" );
		$title = $res["title"];
		$mail  = $res["mail_url"];

		$plist = [];
		$mail  = yexplode( ",", (string)str_replace( ";", ",", $mail ) );

		if ( $format ) {

			foreach ( $mail as $m ) {

				if ( $m != '' ) {
					$plist[] = "<b>".link_it( $m )."</b>";
				}

			}
			$mail = yimplode( ", ", $plist );

			return '<a href="javascript:void(0)" onclick="openClient(\''.$id.'\')" title="В новом окне"><i class="icon-commerical-building broun"></i></a><a href="javascript:void(0)" onclick="viewClient(\''.$id.'\')" title="Просмотр">'.$title.'</a>:&nbsp;'.$mail;

		}

		return $mail;

	}

	return $str;

}

/**
 * Получение клиента с приставкой в зависимости от типа активности. Используется в напоминаниях
 *
 * @param $tip
 * @param $clid
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getAppendixClient($tip, $clid) {

	if ( str_contains( texttosmall( $tip ), 'звон' ) ) {
		$apdx = getClientWPhone( $clid );
	}
	elseif ( str_contains( texttosmall( $tip ), 'почт' ) ) {
		$apdx = getClientWMail( $clid );
	}
	elseif ( str_contains( texttosmall( $tip ), 'встреч' ) ) {
		$apdx = '<A href="javascript:void(0)" onclick="openClient(\''.$clid.'\')" title="Открыть карточку"><i class="icon-building broun"></i><b>'.current_client( $clid ).'</b></A> : '.getClientData( $clid, 'address' );
	}
	else {
		$apdx = '<A href="javascript:void(0)" onclick="openClient(\''.$clid.'\')" title="Открыть карточку"><i class="icon-building broun"></i>'.current_client( $clid ).'</A>';
	}

	return $apdx;

}

/**
 * Список мобильных номеров клиента + контакта
 *
 * @param int  $clid
 * @param int  $pid
 * @param bool $all - искать номера среди всех контактов клиента, а не только основной
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getMobileFromCard(int $clid = 0, int $pid = 0, bool $all = false): array {

	$sqlname = $GLOBALS['sqlname'];
	$db      = $GLOBALS['db'];

	$phones = [];
	$exists = [];

	if ( $clid > 0 ) {

		$tel   = getClientData( $clid, 'phone' );
		$title = current_client( $clid );

		$phone = yexplode( ",", (string)str_replace( ";", ",", str_replace( " ", "", $tel ) ) );

		foreach ( $phone as $p ) {

			$phn = (string)prepareMobPhone( $p );

			$p = $phn[0] == "8" ? "7".substr( $phn, 1 ) : $phn;

			if ( isPhoneMobile( $p ) && !in_array( $p, $exists ) ) {

				$phones[] = [
					"title" => $title,
					"phone" => $p,
					"clid"  => $clid
				];

				$exists[] = $p;

			}

		}

		// попробуем достать данные основного контакта
		if ( $pid == 0 && !$all ) {

			$pid = (int)getClientData( $clid, 'pid' );
			if ( $pid > 0 ) {

				$person = toShort( current_person( $pid ) );
				$ph     = getPersonMobile( $pid );
				foreach ( $ph as $p ) {

					if ( !in_array( $p, $exists ) ) {

						$phones[] = [
							"title" => $person,
							"phone" => $p,
							"pid"   => $pid
						];

						$exists[] = $p;

					}

				}

			}

		}
		elseif ( $all ) {

			$pids = $db -> getAll( "SELECT pid, person FROM {$sqlname}personcat WHERE clid = '$clid'" );
			foreach ( $pids as $p ) {

				$ph = getPersonMobile( (int)$p['pid'] );
				foreach ( $ph as $h ) {

					if ( !in_array( $h, $exists ) ) {

						$phones[] = [
							"title" => toShort( $p['person'] ),
							"phone" => $h,
							"pid"   => (int)$p['pid']
						];

						$exists[] = $h;

					}

				}

			}

		}

	}
	elseif ( $pid > 0 ) {

		$person = toShort( current_person( $pid ) );
		$ph     = getPersonMobile( $pid );
		foreach ( $ph as $p ) {

			if ( !in_array( $p, $exists ) ) {

				$phones[] = [
					"title" => $person,
					"phone" => $p,
					"pid"   => $pid
				];

				$exists[] = $p;

			}

		}

	}

	return $phones;

}

/**
 * Список мобильных номеров контакта
 *
 * @param int $pid
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getPersonMobile(int $pid = 0): array {

	$phones = [];

	if ( $pid > 0 ) {

		$phone = getPersonData( $pid, 'tel' );
		$mob   = getPersonData( $pid, 'mob' );

		$tel = yexplode( ",", str_replace( ";", ",", $phone ) );
		$mob = yexplode( ",", str_replace( ";", ",", $mob ) );

		$phone = array_merge( $tel, $mob );

		foreach ( $phone as $p ) {

			$phn = (string)prepareMobPhone( $p );

			if ( isPhoneMobile( $p ) ) {
				$phones[] = str_split( $phn )[0] == "8" ? "7".substr( $phn, 1 ) : $phn;
			}

		}

	}

	return $phones;

}

/**
 * Название этапа сделки по id
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_dogstepname($id): ?string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return $db -> getOne( "SELECT title FROM {$sqlname}dogcategory WHERE idcategory = '$id' AND identity = '$identity'" );

	}

	return NULL;

}

/**
 * Описание этапа по id
 *
 * @param $id
 *
 * @return string|null
 * @category Core
 * @package  Func
 */
function current_dogstepcontent($id): ?string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return untag( $db -> getOne( "SELECT content FROM {$sqlname}dogcategory WHERE idcategory = '$id' AND identity = '$identity'" ) );

	}

	return NULL;

}

/**
 * Текущий этап сделки по её did в виде названия этапа (20, 40...)
 *
 * @param $id
 *
 * @return string|null
 * @category Core
 * @package  Func
 */
function current_dogstep($id): ?string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		$idcat = $db -> getOne( "SELECT idcategory FROM {$sqlname}dogovor WHERE did = '$id' AND identity = '$identity'" );

		return current_dogstepname( $idcat );

	}

	return NULL;

}

/**
 * id этапа сделки по did сделки
 *
 * @param $id
 *
 * @return int|null
 * @category Core
 * @package  Func
 */
function current_dogstepid($id): ?int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$step = NULL;

	if ( (int)$id > 0 ) {

		$step = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}dogovor WHERE did = '$id' AND identity = '$identity'" );

	}

	return $step;

}

/**
 * id следующего этапа по id сделки
 *
 * @param $id
 *
 * @return int|null
 * @category Core
 * @package  Func
 */
function next_dogstep($id): ?int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	//находим текущий статус и его значение
	$idcategory = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}dogovor WHERE did = '$id' AND identity = '$identity'" );

	//составляем массив статусов, упорядоченных по возрастанию
	$data = $db -> getCol( "SELECT idcategory FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );

	//Определим следующий этап--
	foreach ( $data as $j => $d ) {

		if ( $j == 0 ) {
			$data[0] = $idcategory;
		}

		if ( $idcategory == $data[ $j ] ) {
			return $data[ $j + 1 ];
		}

	}

	//возвращает id этапа
	return NULL;

}

/**
 * возвращает id предыдущего этапа по id текущего этапа
 *
 * @param $id - id этапа сделки
 *
 * @return int
 * @category Core
 * @package  Func
 */
function prev_step($id): ?int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	//составляем массив статусов, упорядоченных по возрастанию
	$data = $db -> getCol( "SELECT idcategory FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );

	//Определим следующий этап--
	foreach ( $data as $j => $d ) {

		if ( $j == 0 && (int)$id == (int)$d ) {
			return (int)$id;
		}

		//print $j.":".(int)$id.":".(int)$data[ $j ]."::".(int)$data[ $j - 1 ]."\n";

		if ( (int)$id == (int)$d ) {
			return (int)$data[ $j - 1 ];
		}

	}

	return NULL;

}

/**
 * Возвращает название типа сделки по её id, либо наоборот
 *
 * @param             $id
 * @param string|null $title
 *
 * @return string|int|null
 * @category Core
 * @package  Func
 */
function current_dogtype($id, string $title = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return (string)$db -> getOne( "SELECT title FROM {$sqlname}dogtips WHERE tid = '$id' AND identity = '$identity'" );

	}

	if ( !empty( $title ) ) {

		return (int)$db -> getOne( "SELECT tid FROM {$sqlname}dogtips WHERE title = '$title' AND identity = '$identity'" );

	}

	return NULL;

}

/**
 * Возвращает название статуса закрытой сделки по id сделки
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_dogstatus($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$res = '';

	if ( (int)$id > 0 ) {

		$result       = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$id' AND identity = '$identity'" );
		$close        = $result["close"];
		$sid          = (int)$result["sid"];
		$CloseDesFact = $result["des_fact"];

		if ( $sid > 0 && $close == 'yes' ) {

			$title = $db -> getOne( "SELECT title FROM {$sqlname}dogstatus WHERE sid = '$sid' AND identity = '$identity'" );

			$res = ($CloseDesFact != '') ? $title.": ".$CloseDesFact : $title;

		}

	}

	return $res;

}

/**
 * По id статуса закрытия сделки возвращает массив из названия и описания статуса
 *
 * @param $id
 *
 * @return array
 * @category Core
 * @package  Func
 */
function current_dstatus($id): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$res      = [];

	if ( (int)$id > 0 ) {

		$res = $db -> getRow( "SELECT title, content FROM {$sqlname}dogstatus WHERE sid = '$id' AND identity = '$identity'" );

	}

	return $res;

}

/**
 * Возвращает номер договора по его id
 *
 * @param $id
 *
 * @return string
 */
function current_contract($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$title = 'Нет';

	if ( (int)$id > 0 ) {

		$t = $db -> getOne( "SELECT number FROM {$sqlname}contract WHERE deid = '$id' AND identity = '$identity'" );
		if ( $t != '' ) {
			$title = $t;
		}

	}

	return $title;

}

/**
 * Возвращает навание отрасли по её id или наоборот
 *
 * @param             $id
 * @param string|null $title
 *
 * @return int|string|null
 * @category Core
 * @package  Func
 */
function current_category($id, string $title = NULL): ?string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return (string)$db -> getOne( "SELECT title FROM {$sqlname}category WHERE idcategory='".$id."' AND identity = '".$GLOBALS['identity']."'" );

	}

	if ( !empty( $title ) ) {

		return (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}category WHERE title='".$title."' AND identity = '".$identity."'" );

	}

	return NULL;

}

/**
 * Возвращает название направления по её id или наоборот
 *
 * @param             $id
 * @param string|null $title
 *
 * @return int|string|null
 * @category Core
 * @package  Func
 */
function current_direction($id, string $title = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( $id > 0 ) {

		return (string)$db -> getOne( "SELECT title FROM {$sqlname}direction WHERE id='".$id."' AND identity = '".$identity."'" );

	}

	if ( !empty( $title ) ) {

		return (int)$db -> getOne( "SELECT id FROM {$sqlname}direction WHERE title='".$title."' AND identity = '".$identity."'" );

	}

	return NULL;

}

/**
 * Возвращает название нашей компании по её id или наоборот
 *
 * @param             $id
 * @param string|null $title
 * @return int|string|null
 * @category Core
 * @package  Func
 */
function current_company($id, string $title = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return (string)$db -> getOne( "SELECT name_shot FROM {$sqlname}mycomps WHERE id = '".$id."' AND identity = '".$identity."'" );

	}

	if ( !empty( $title ) ) {

		return (int)$db -> getOne( "SELECT id FROM {$sqlname}mycomps WHERE name_shot = '".$title."' AND identity = '".$identity."'" );

	}

	return NULL;

}

/**
 * Получение подписанта или списка подписантов
 * Если не указан ни один параметр, то возвращает всех подписантов в массиве, где ключи - id компаний
 *
 * @param int|null $id   - id подписанта
 * @param int|null $mcid - id компании
 * @return array
 * @category Core
 * @package  Func
 */
function getSigner(int $id = NULL, int $mcid = NULL): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$signer = [];

	// подписанты для компаний
	if ( (int)$id > 0 ) {

		$xsigner = $db -> getRow( "SELECT * FROM ".$sqlname."mycomps_signer WHERE id = '$id' and identity = '$identity'" );
		$signer  = [
			"id"        => (int)$xsigner['id'],
			"mcid"      => (int)$xsigner['mcid'],
			"title"     => $xsigner['title'],
			"status"    => $xsigner['status'],
			"signature" => $xsigner['signature'],
			"osnovanie" => $xsigner['osnovanie'],
			"stamp"     => $xsigner['stamp'],
		];

	}
	elseif ( (int)$mcid > 0 ) {

		$xsigners = $db -> getAll( "SELECT * FROM ".$sqlname."mycomps_signer WHERE mcid = '$mcid' and identity = '$identity'" );
		foreach ( $xsigners as $xsigner ) {

			$signer[ (int)$xsigner['mcid'] ][ (int)$xsigner['id'] ] = [
				"id"        => (int)$xsigner['id'],
				"mcid"      => (int)$xsigner['mcid'],
				"title"     => $xsigner['title'],
				"status"    => $xsigner['status'],
				"signature" => $xsigner['signature'],
				"osnovanie" => $xsigner['osnovanie'],
				"stamp"     => $xsigner['stamp'],
			];

		}

	}
	else {

		$xsigners = $db -> getAll( "SELECT * FROM ".$sqlname."mycomps_signer WHERE identity = '$identity'" );
		foreach ( $xsigners as $xsigner ) {

			$signer[ $xsigner['mcid'] ][] = [
				"id"        => $xsigner['id'],
				"mcid"      => $xsigner['mcid'],
				"title"     => $xsigner['title'],
				"status"    => $xsigner['status'],
				"signature" => $xsigner['signature'],
				"osnovanie" => $xsigner['osnovanie'],
				"stamp"     => $xsigner['stamp'],
			];

		}

	}

	return $signer;

}

/**
 * Возвращает название территории по её id или наоборот
 *
 * @param             $id
 * @param string|null $title
 *
 * @return int|string|null
 * @category Core
 * @package  Func
 */
function current_territory($id, string $title = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return (string)$db -> getOne( "SELECT title FROM {$sqlname}territory_cat WHERE idcategory='".$id."' AND identity = '".$identity."'" );

	}

	if ( !empty( $title ) ) {

		return (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}territory_cat WHERE title='".$title."' AND identity = '".$identity."'" );

	}

	return NULL;

}

/**
 * Возвращает название Канала по id клиента
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_clientpath($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$clientpath = '';

	if ( (int)$id > 0 ) {

		$cpath      = $db -> getOne( "SELECT clientpath FROM {$sqlname}clientcat WHERE clid='".$id."' AND identity = '".$identity."'" );
		$clientpath = $db -> getOne( "SELECT name FROM {$sqlname}clientpath WHERE id = '".$cpath."'" );

	}

	return $clientpath;

}

/**
 * Возвращает название Канала по его id
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function current_clientpathbyid($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$clientpath = '';

	if ( (int)$id > 0 ) {

		$clientpath = $db -> getOne( "SELECT name FROM {$sqlname}clientpath WHERE id = '".$id."' AND identity = '".$identity."'" );

	}

	return $clientpath;

}

/**
 * Возвращает название Типа лояльности по её id или наоборот
 *
 * @param             $id
 * @param string|null $title
 *
 * @return int|string|null
 * @category Core
 * @package  Func
 */
function current_loyalty($id, string $title = NULL) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		return (string)$db -> getOne( "SELECT title FROM {$sqlname}loyal_cat WHERE idcategory='".$id."' AND identity = '".$identity."'" );

	}

	if ( !empty( $title ) ) {

		return (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}loyal_cat WHERE title='".$title."' AND identity = '".$identity."'" );

	}

	return NULL;
}

/**
 * Возвращает true, если тип сделки относится к ежемесячным
 * Если id не указан возвращает массив id типов сделок, относящихся к сервисным
 *
 * @param string $id
 *
 * @return bool|array
 * @category Core
 * @package  Func
 */
function isServices($id = 0) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$tip      = getDogData( $id, 'tip' );
	$services = (array)$db -> getCol( "SELECT tid FROM {$sqlname}dogtips WHERE (title LIKE '%месячный%' or title LIKE '%сервис%' or title LIKE '%абонент%') and identity = '$identity'" );

	if ( $id > 0 ) {

		return in_array( $tip, $services );

	}

	return $services;

}

/**
 * Возвращает название отрасли по её id
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_client_category($id): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$category = "--не определено--";

	if ( (int)$id > 0 ) {

		$category = $db -> getOne( "SELECT title FROM {$sqlname}category WHERE idcategory='".$id."' AND identity = '".$identity."'" );

	}

	return $category;

}

/**
 * Возвращает ответственного по типу и id записи
 * tip: clid, pid, did
 *
 * @param $tip
 * @param $id
 *
 * @return int
 * @category Core
 * @package  Func
 */
function get_userid($tip, $id): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$userid = 0;

	if ( $tip == 'clid' ) {
		$userid = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}clientcat WHERE clid = '".$id."' AND identity = '".$identity."'" );
	}
	if ( $tip == 'pid' ) {
		$userid = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}personcat WHERE pid  = '".$id."' AND identity = '".$identity."'" );
	}
	if ( $tip == 'did' ) {
		$userid = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}dogovor   WHERE did  = '".$id."' AND identity = '".$identity."'" );
	}

	return $userid;

}

/**
 * Возвращает массив данных по клиенту
 *
 * @param        $id
 * @param string $isArray = yes возвращает в виде массива (default = no, в виде JSON)
 *
 * @return string|array
 * "clid" - ID клиента
 * "uid" - UID клиента
 * "clientUID" - UID клиента
 * "type" - тип записи (client, person, partner, concurent, contractor)
 * "title" - название клиента
 * "des" - описание клиента
 * "idcategory" - ID отрасли
 * "category" - название отрасли
 * "phone" - список телефонов
 * "fax" - список факсов
 * "site_url" - сайт
 * "mail_url" - список email
 * "address" - адрес
 * "iduser" - ID ответственного
 * "pid" - ID основного контакта
 * "fav" - в избранном (no|yes)
 * "trash" - в корзине (no|yes)
 * "head_clid" - ID головного клиента
 * "head" - Название головного клиента
 * "scheme" - Принятие решений
 * "tip_cmr" - Тип отношений
 * "relation" - тип отношений
 * "territory" - ID территории
 * "territoryname" - название Территории
 * "date_create" - дата создания
 * "creator" - имя автора
 * "date_edit" - дата последнего редактирования
 * "editor" - имя редактора
 * "recv" - массив реквизитов
 * "dostup" - массив iduser, у которых есть доступ к карточке
 * "clientpath" - название Канала
 * "clientpath2" - ID канала
 * "priceLevel" - уровень цен
 * "inputXXX" - доп.поля
 * @category Core
 * @package  Func
 */
function get_client_info($id, string $isArray = 'no') {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$client      = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE clid = '$id' AND identity = '$identity'" );
	$uid         = $client["uid"]; //название
	$title       = $client["title"]; //название
	$iduser      = (int)$client["iduser"]; //аккаунт-менеджер
	$des         = $client["des"]; //описание
	$idcategory  = (int)$client["idcategory"]; //отрасль
	$clientpath  = (int)$client["clientpath"]; //источник клиента
	$phone       = $client["phone"]; //телефон
	$fax         = $client["fax"]; //факс
	$site_url    = $client["site_url"]; //сайт
	$mail_url    = $client["mail_url"]; //почта
	$address     = $client["address"]; //адрес
	$pid         = $client["pid"]; //основной контакт
	$trash       = $client["trash"]; //признак свободной организации yes|no
	$fav         = $client["fav"]; //признак ключевого клиента yes|no
	$head_clid   = (int)$client["head_clid"]; //головная организация
	$scheme      = $client["scheme"]; //схема принятия решений
	$tip_cmr     = $client["tip_cmr"]; //тип отношений
	$territory   = (int)$client["territory"]; //территория
	$creator     = get_user( $client["creator"] ); //пользователь, создавший организацию iduser
	$editor      = get_user( $client["editor"] ); //пользователь, изменивший организацию iduser
	$date_create = get_sfdate( $client["date_create"] ); //дата создания
	$date_edit   = get_sfdate( $client["date_edit"] );// дата изменения
	$type        = $client["type"]; //тип клиента
	$priceLevel  = $client["priceLevel"];

	$path          = ($clientpath > 0) ? $db -> getOne( "SELECT name FROM {$sqlname}clientpath WHERE id = '$clientpath' AND identity = '$identity' ORDER BY name" ) : "";
	$category      = ($idcategory > 0) ? $db -> getOne( "SELECT title FROM {$sqlname}category WHERE idcategory = '$idcategory' and identity = '$identity'" ) : "";
	$territoryname = ($territory > 0) ? $db -> getOne( "SELECT title FROM {$sqlname}territory_cat WHERE idcategory = '$territory' and identity = '$identity'" ) : "";
	$head          = ($head_clid > 0) ? $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '$head_clid' and identity = '$identity'" ) : "";

	$dostup = $db -> getCol( "SELECT DISTINCT iduser FROM {$sqlname}dostup WHERE clid = '$id' and identity = '$identity'" );

	//данные по организации
	$data = [
		"clid"          => (int)$id,
		"uid"           => $uid,
		"clientUID"     => $uid,
		"iduser"        => $iduser,
		"title"         => $title,
		"des"           => $des,
		"idcategory"    => $idcategory,
		"category"      => $category,
		"phone"         => $phone,
		"fax"           => $fax,
		"site_url"      => $site_url,
		"mail_url"      => $mail_url,
		"address"       => $address,
		"pid"           => (int)$pid,
		"fav"           => $fav,
		"trash"         => $trash,
		"head_clid"     => $head_clid,
		"head"          => $head,
		"scheme"        => $scheme,
		"tip_cmr"       => $tip_cmr,
		"relation"      => $tip_cmr,
		"territory"     => $territory,
		"territoryname" => $territoryname,
		"created"       => $client["date_create"],
		"creator"       => $creator,
		"creatorID"     => (int)$client["creator"],
		"date_create"   => $date_create,
		"edited"        => $client["date_edit"],
		"editor"        => $editor,
		"editorID"      => (int)$client["editor"],
		"date_edit"     => $date_edit,
		"dostup"        => array_map(static function($v){ return (int)$v;}, $dostup),
		"clientpath"    => $path,
		"clientpath2"   => $clientpath,
		"type"          => $type,
		"priceLevel"    => $priceLevel
	];

	/*
	 * доп.поля
	 */
	$res = $db -> getAll( "select * from {$sqlname}field where fld_tip='client' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order" );
	foreach ( $res as $da ) {

		$data[ $da['fld_name'] ] = $client[ $da['fld_name'] ];

	}

	$data["recv"] = get_client_recv( $id );

	if ( $isArray == 'yes' ) {
		return $data;
	}

	return json_encode_cyr( $data );

}

/**
 * Возвращает реквизиты клиента в массиве или в формате json
 *
 * @param        $id
 * @param string $isArray
 *
 * @return array|string
 * @category Core
 * @package  Func
 */
function get_client_recv($id, string $isArray = 'no') {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$result      = $db -> getRow( "SELECT * FROM {$sqlname}clientcat WHERE clid = '".$id."' AND identity = '".$identity."'" );
	$recv        = explode( ";", (string)$result["recv"] );
	$castFacAddr = $result["address"];
	$castType    = $result["type"];

	$castName = current_client( (int)$id );

	if ( empty( $recv ) ) {
		$recv = [];
	}

	//данные по организации
	$data = [
		"clid"             => (int)$id,
		"castUrName"       => $recv[0],
		"castUrNameShort"  => $recv[15],
		"castName"         => $castName,
		"castInn"          => $recv[1],
		"castKpp"          => $recv[2],
		"castBank"         => $recv[3],
		"castBankKs"       => $recv[4],
		"castBankRs"       => $recv[5],
		"castBankBik"      => $recv[6],
		"castOkpo"         => $recv[7],
		"castOgrn"         => $recv[8],
		"castDirName"      => $recv[9],
		"castDirSignature" => $recv[10],
		"castDirStatus"    => $recv[11],
		"castDirStatusSig" => $recv[12],
		"castDirOsnovanie" => $recv[13],
		"castUrAddr"       => $recv[14],
		"castFacAddr"      => $castFacAddr,
		"castType"         => $castType
	];

	if ( $isArray == 'yes' ) {
		return $data;
	}

	return json_encode_cyr( $data );

}

/**
 * Возвращает базовую информацию по контакту в виде массива
 *
 * @param        $id
 * @param string $isArray
 *
 * @return array|string|string[]
 * @category Core
 * @package  Func
 */
function get_person_info($id, string $isArray = 'no') {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$field_types = db_columns_types( "{$sqlname}personcat" );

	$data = $db -> getRow( "SELECT * FROM {$sqlname}personcat WHERE pid='".$id."' AND identity = '".$identity."'" );

	if ( (int)$data['clientpath'] > 0 ) {
		$data['clientpath2'] = $db -> getOne( "SELECT name FROM {$sqlname}clientpath WHERE id = '".$data['clientpath']."' AND identity = '".$identity."'" );
	}

	if ( (int)$data['loyalty'] > 0 ) {
		$data['relation'] = $db -> getOne( "SELECT title FROM {$sqlname}loyal_cat WHERE idcategory = '".$data['loyalty']."' AND identity = '".$identity."'" );
	}

	foreach ( $data as $k => $v ) {

		if ( is_numeric( $k ) ) {
			unset( $data[ $k ] );
		}
		elseif ( $field_types[ $k ] == "int" ) {

			$data[ $k ] = (int)$v;

		}
		elseif ( in_array( $field_types[ $k ], [
			"float",
			"double"
		] ) ) {

			$data[ $k ] = (float)$v;

		}
		else {

			$data[ $k ] = $v;

		}

	}

	if ( $isArray == 'yes' ) {
		return (array)$data;
	}

	return (string)json_encode_cyr( $data );

}

/**
 * Возвращает базовую информацию по сделке в виде массива
 *
 * @param        $id
 * @param string $isArray
 *
 * @return array|string|string[]
 * @category Core
 * @package  Func
 */
function get_dog_info($id, string $isArray = 'no') {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$data             = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$id' AND identity = '$identity'" );
	$data['content']  = str_replace( "\n", "<br>", $data['content'] );
	$data['pid_list'] = array_map( static function($x) {
		return (int)$x;
	}, yexplode( ";", (string)$data['pid_list'] ) );

	$field_types = db_columns_types( "{$sqlname}dogovor" );

	//print_r($field_types);

	foreach ( $data as $k => $v ) {

		if ( is_numeric( $k ) ) {
			unset( $data[ $k ] );
		}
		elseif ( $field_types[ $k ] == "int" ) {
			$data[ $k ] = (int)$v;
		}
		elseif ( in_array( $field_types[ $k ], [
			"float",
			"double"
		] ) ) {
			$data[ $k ] = (float)$v;
		}
		else {
			$data[ $k ] = $v;
		}

	}

	$data['isFrozen'] = (int)$data['isFrozen'] == 1 && isset( $data['isFrozen'] );

	if ( $isArray == 'yes' ) {
		return (array)$data;
	}

	return (string)json_encode_cyr( $data );

}

/**
 * Действие. Перемещение денег со счета на счет
 *
 * @param int         $rs
 * @param float       $summa
 * @param string|null $operacia
 * @param string|null $rs_move
 *
 * @return bool
 * @see      Budget::rsadd()
 * @deprecated
 * @category Core
 * @package  Func
 */
function rsadd(int $rs = 0, float $summa = 0.00, string $operacia = NULL, string $rs_move = NULL): bool {

	return true;

}

/**
 * Логгирование движения сделок по этапам
 *
 * @param       $did
 * @param       $step
 *
 * @return bool
 * @category Core
 * @package  Func
 */
function DealStepLog($did, $step): bool {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$iduser   = $GLOBALS['iduser1'];

	if( (int)$did > 0 ) {

		$db -> query( "INSERT INTO {$sqlname}steplog SET ?u", [
			"step"     => (int)$step,
			"did"      => (int)$did,
			"iduser"   => (int)$iduser,
			"identity" => $identity
		] );

	}

	return true;

}

/**
 * формирует номер договора, счета и акта
 *
 * @param $tip
 *     - contract    - номер документа
 *     - invoice     - номер счета
 *     - akt         - номер акта
 *     - dogovor     - номер сделки
 *     - namedogovor - название сделки
 *
 * @return mixed|string
 * @category Core
 * @package  Func
 */
function generate_num($tip) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	global $cnum;

	$format = '';
	$num    = 0;

	if ( !$tip ) {
		goto a;
	}


	if ( $tip == 'contract' ) {

		$results = $db -> getRow( "SELECT contract_format, contract_num FROM {$sqlname}settings WHERE id = '$identity'" );
		$format  = $results["contract_format"];
		$num     = $results["contract_num"];

	}
	if ( $tip == 'invoice' ) {

		$results = $db -> getRow( "SELECT iformat, inum FROM {$sqlname}settings WHERE id = '$identity'" );
		$format  = $results["iformat"];
		$num     = $results["inum"];

	}
	if ( $tip == 'akt' ) {

		$num    = $db -> getOne( "SELECT akt_num FROM {$sqlname}settings WHERE id = '$identity'" );
		$format = '{cnum}';

	}
	if ( $tip == 'dogovor' ) {

		$results = $db -> getRow( "SELECT dNum, dFormat FROM {$sqlname}settings WHERE id = '$identity'" );
		$num     = $results["dNum"];
		$format  = $results["dFormat"];

	}
	if ( $tip == 'namedogovor' ) {

		$format = $db -> getOne( "SELECT defaultDealName FROM {$sqlname}settings WHERE id = '$identity'" );

	}

	$cnum = (int)$num + 1;

	$d11 = date( 'd' );//получим месяц в формате MM
	$m11 = date( 'm' );//получим месяц в формате MM
	$y11 = date( 'y' );//получим год в формате 13
	$y12 = date( 'Y' );//получим год в формате 13
	$hh  = date( 'H' );
	$ii  = date( 'i' );

	$format = str_replace( [
		'{cnum}',
		'{DD}',
		'{MM}',
		'{YY}',
		'{YYYY}',
		'{HH}',
		'{MI}'
	], [
		$cnum,
		$d11,
		$m11,
		$y11,
		$y12,
		$hh,
		$ii
	], $format );

	a:

	return $format;

}

/**
 * формирует номер пользовательского документа
 *
 * @param      $id
 * @param bool $onlyNum
 *
 * @return mixed|string
 * @category Core
 * @package  Func
 */
function genDocsNum($id, bool $onlyNum = false) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id == 0 ) {
		return false;
	}

	$result = $db -> getRow( "SELECT format, num FROM {$sqlname}contract_type where id = '$id' and identity = '".$identity."'" );
	$format = $result["format"];
	$num    = $result["num"];

	if ( $format != '' ) {

		$cnum = $num + 1;

		$d11 = date( 'd' );//получим день в формате 01
		$m11 = date( 'm' );//получим месяц в формате 01
		$y11 = date( 'y' );//получим год в формате 13
		$y12 = date( 'Y' );//получим год в формате 2013
		$hh  = date( 'H' );
		$ii  = date( 'i' );

		$format = str_replace( [
			'{cnum}',
			'{DD}',
			'{MM}',
			'{YY}',
			'{YYYY}',
			'{HH}',
			'{MI}'
		], [
			$cnum,
			$d11,
			$m11,
			$y11,
			$y12,
			$hh,
			$ii
		], $format );

		if ( !$onlyNum ) {
			return $format;
		}//возвращает полный номер с форматом

		return $cnum;//возвращает номер документа

	}

	return '';

}

/**
 * Получение массива: title - Название этапа, content - Расшифровка, id
 *
 * @param        $id
 * @param string $tip - default = current, next, prev
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getPrevNextStep($id, string $tip = 'current'): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$steps   = [];
	$step    = [];
	$current = 0;

	$result = $db -> query( "SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '".$identity."' ORDER BY CAST(title AS SIGNED)" );
	while ($data = $db -> fetch( $result )) {

		$steps[] = [
			"title"   => $data['title'],
			"content" => $data['content'],
			"id"      => (int)$data['idcategory']
		];
		$step[]  = $data['title'];

		//название заданного этапа
		if ( $data['idcategory'] == $id ) {
			$current = $data['title'];
		}

	}

	//индекс текущего этапа
	$index = array_search( $current, $step );

	//предыдущий этап
	$prev = [
		"title"   => $steps[ $index - 1 ]['title'],
		"content" => $steps[ $index - 1 ]['content'],
		"id"      => $steps[ $index - 1 ]['id']
	];
	$next = [
		"title"   => $steps[ $index + 1 ]['title'],
		"content" => $steps[ $index + 1 ]['content'],
		"id"      => $steps[ $index + 1 ]['id']
	];
	$curr = [
		"title"   => $steps[ $index ]['title'],
		"content" => $steps[ $index ]['content'],
		"id"      => $steps[ $index ]['id']
	];

	if ( $tip == 'prev' ) {
		return $prev;
	}

	if ( $tip == 'next' ) {
		return $next;
	}

	return $curr;


}

/**
 * возвращает информацию по мультиворонке
 *
 * @param array $opt - параметры
 *                   did - id сделки
 *                   steps - массив этапов: id => длительность этапа
 *                   default - id этапа по умолчанию
 *                   length - длительность воронки
 *                   current - текущий этап: id, title
 *                   next - следующий этап: id, title
 *                   prev - предыдущий этап: id, title
 *                   $opt['steps'] - возвращает только этапы по сделке, работает вместе с $opt['did']
 *                   $opt['direction'] > 0, $opt['tip'] < 1 - возвращает все воронки по id Направления. Индекс = tip
 *                   $opt['direction'] < 1, $opt['tip'] > 0 - возвращает все воронки по id Типа сделки. Индекс =
 *                   direction
 *                   $opt['direction'] > 0, $opt['tip'] > 0 - возвращает воронку
 *                   $opt не установлен - возвращает все наборы воронок
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getMultiStepList(array $opt = []): array {

	$rootpath = dirname( __DIR__, 2 );

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$other    = $GLOBALS['other'];
	$fpath    = $GLOBALS['fpath'];

	$otherSettings = json_decode( file_get_contents( $rootpath."/cash/".$fpath."otherSettings.json" ), true );

	$response = [];

	if ( (int)$opt['did'] > 0 ) {

		$deal = $db -> getRow( "SELECT direction, tip, idcategory FROM {$sqlname}dogovor WHERE did = '$opt[did]' and identity = '$identity'" );

		$multistep = $db -> getRow( "SELECT * FROM {$sqlname}multisteps WHERE direction = '$deal[direction]' AND tip = '$deal[tip]' AND identity = '$identity'" );
		$steps     = json_decode( (string)$multistep['steps'], true );
		$thread    = array_keys( (array)$steps );

		//var_export($thread);

		$curIndex = array_search( $deal['idcategory'], $thread );

		//print "current = $curIndex";

		$next = $thread[ $curIndex + 1 ];
		$prev = $thread[ $curIndex - 1 ];

		/**
		 * Если мультиворонки нет, то ищем по-другому
		 */
		if ( empty( $thread ) ) {

			$current = (int)getDogData( (int)$opt['did'], 'idcategory' );

			$inext = getPrevNextStep( $current, 'next' );
			$iprev = getPrevNextStep( $current, 'prev' );

			$next = (int)$inext['id'];
			$prev = (int)$iprev['id'];

			$multistep['isdefault'] = $otherSettings['dealStepDefault'];

		}

		//если мультиворонка не задана
		if ( empty( $steps ) ) {

			$lsteps = $db -> getAll( "SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY CAST(title AS UNSIGNED)" );
			foreach ( $lsteps as $item ) {

				$steps[ (int)$item['idcategory'] ] = 5;

			}

		}

		if ( $opt['steps'] ) {
			$response = $steps;
		}
		else {

			//var_export($steps);

			$response = [
				"steps"   => $steps,
				"default" => (int)$multistep['isdefault'],
				"length"  => array_sum( $steps ),
				"funnel"  => $multistep['title'],
				"prev"    => [
					"id"      => (int)$prev,
					"title"   => current_dogstepname( (int)$prev ),
					"content" => current_dogstepcontent( (int)$prev )
				],
				"current" => [
					"id"      => (int)$deal['idcategory'],
					"title"   => current_dogstepname( (int)$deal['idcategory'] ),
					"content" => current_dogstepcontent( (int)$deal['idcategory'] )
				],
				"next"    => [
					"id"      => (int)$next,
					"title"   => current_dogstepname( (int)$next ),
					"content" => current_dogstepcontent( (int)$next )
				]
			];

		}

	}
	elseif ( (int)$opt['id'] > 0 ) {

		$multistep = $db -> getRow( "SELECT * FROM {$sqlname}multisteps WHERE id = '$opt[id]' AND identity = '$identity'" );
		$steps     = json_decode( (string)$multistep['steps'], true );
		$thread    = array_keys( (array)$steps );

		if ( $opt['steps'] ) {
			$response = $steps;
		}

		else {
			$response = [
				"steps"     => $steps,
				"thread"    => $thread,
				"default"   => $multistep['isdefault'],
				"length"    => array_sum( $steps ),
				"funnel"    => $multistep['title'],
				"direction" => $multistep['direction'],
				"tip"       => $multistep['tip']
			];
		}

	}
	elseif ( (int)$opt['direction'] > 0 && (int)$opt['tip'] == 0 ) {

		$multistep = $db -> query( "SELECT * FROM {$sqlname}multisteps WHERE direction = '$opt[direction]' AND identity = '$identity'" );
		while ($da = $db -> fetch( $multistep )) {

			$steps = json_decode( (string)$da['steps'], true );

			$response[ $da['tip'] ] = [
				"steps"   => $steps,
				"length"  => array_sum( $steps ),
				"default" => $da['isdefault'],
				"funnel"  => $da['title']
			];

		}

	}
	elseif ( (int)$opt['tip'] > 0 ) {

		if ( (int)$opt['direction'] == 0 ) {

			$multistep = $db -> query( "SELECT * FROM {$sqlname}multisteps WHERE tip = '$opt[tip]' AND identity = '$identity'" );
			while ($da = $db -> fetch( $multistep )) {

				$steps = json_decode( (string)$da['steps'], true );

				$response[ (int)$da['direction'] ] = [
					"steps"   => $steps,
					"length"  => array_sum( $steps ),
					"default" => $da['isdefault'],
					"funnel"  => $da['title']
				];

			}

		}
		else {

			$multistep = $db -> getRow( "SELECT * FROM {$sqlname}multisteps WHERE direction = '$opt[direction]' AND tip = '$opt[tip]' AND identity = '$identity'" );

			if ( !empty( $multistep ) ) {

				$steps = json_decode( (string)$multistep['steps'], true );

				$nsteps = $newsteps = [];

				foreach ( $steps as $k => $v ) {
					$nsteps[] = [
						"id"      => (int)$k,
						"name"    => current_dogstepname( $k ),
						"content" => current_dogstepcontent( $k )
					];
					$newsteps[(int)$k] = $v;
				}

				$response = [
					"steps"   => $newsteps,
					"nsteps"  => $nsteps,
					"length"  => array_sum( $steps ),
					"default" => (int)$multistep['isdefault'],
					"funnel"  => $multistep['title']
				];

			}
			else {

				$steps = $nsteps = [];

				$multistep = $db -> getAll( "SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY CAST(title AS UNSIGNED)" );
				foreach ( $multistep as $item ) {

					$steps[ $item['idcategory'] ] = 5;
					$nsteps[]                     = [
						"id"      => (int)$item['idcategory'],
						"name"    => $item['title'],
						"content" => $item['content']
					];

				}

				$response = [
					"steps"   => $steps,
					"nsteps"  => $nsteps,
					"length"  => array_sum( $steps ),
					"default" => (int)$otherSettings['dealStepDefault'],
					"funnel"  => "Общая"
				];

			}

		}

	}
	else {

		//$msCount = $db -> getOne("SELECT COUNT(*) FROM {$sqlname}multisteps WHERE identity = '$identity'") + 0;

		//$superSteps = array();

		//if($msCount == 0) {

		$steps = $nsteps = [];

		$multistep = $db -> getAll( "SELECT idcategory, title, content FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY CAST(title AS UNSIGNED)" );
		foreach ( $multistep as $item ) {

			$steps[ (int)$item['idcategory'] ] = 5;
			$nsteps[]                          = [
				"id"      => (int)$item['idcategory'],
				"name"    => $item['title'],
				"content" => $item['content']
			];

		}

		$superSteps = [
			"steps"   => $steps,
			"nsteps"  => $nsteps,
			"length"  => array_sum( $steps ),
			"default" => $otherSettings['dealStepDefault'],
			"funnel"  => "Общая"
		];

		//}

		$res = $db -> query( "SELECT * FROM {$sqlname}direction WHERE identity = '$identity'" );
		while ($data = $db -> fetch( $res )) {

			$r = $db -> query( "SELECT * FROM {$sqlname}dogtips WHERE identity = '$identity'" );
			while ($da = $db -> fetch( $r )) {

				$mmultistep = (array)$db -> getRow( "SELECT * FROM {$sqlname}multisteps WHERE direction = '$data[id]' AND tip = '$da[tid]' AND identity = '$identity'" );

				if ( !empty( $mmultistep ) ) {

					$steps = json_decode( (string)$mmultistep['steps'], true );

					$nsteps = [];

					foreach ( $steps as $k => $v ) {
						$nsteps[] = [
							"id"      => $k,
							"name"    => current_dogstepname( $k ),
							"content" => current_dogstepcontent( $k )
						];
					}

					$response[ $data['id'] ][ $da['tid'] ] = [
						"steps"   => $steps,
						"nsteps"  => $nsteps,
						"length"  => array_sum( $steps ),
						"default" => $mmultistep['isdefault'],
						"funnel"  => $mmultistep['title']
					];

				}
				else {
					$response[ $data['id'] ][ $da['tid'] ] = $superSteps;
				}

			}

		}

	}

	return $response;

}

/**
 * Устанавливает потенциал клиента
 * функцию надо вызывать при закрытии сделки
 *
 * @param $id
 * @category Core
 * @package  Func
 */
function set_capacity($id) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( (int)$id > 0 ) {

		//Получим данные по сделке
		$result    = $db -> getRow( "SELECT direction, kol_fact, clid FROM {$sqlname}dogovor WHERE did = '".$id."' AND identity = '".$identity."'" );
		$direction = (int)$result["direction"];
		$kol_fact  = (float)$result["kol_fact"];
		$clid      = (int)$result["clid"];

		//Текущие даты
		$y = (int)date( 'Y' );
		$m = (int)date( 'm' );

		//Найдем текущие параметры Потенциала клиента
		$result  = $db -> getRow( "SELECT * FROM {$sqlname}capacity_client WHERE clid = '".$clid."' AND direction = '".$direction."' AND year = '".$y."' AND mon = '".$m."' AND identity = '".$identity."'" );
		$capid   = (int)$result["id"];
		$sumfact = $kol_fact + (float)$result["sumfact"];


		//добавим новые значения к потенциалу
		if ( $capid > 0 ) {
			$db -> query( "update {$sqlname}capacity_client set sumfact = '".$sumfact."' WHERE id = '".$capid."' and identity = '".$identity."'" );
		}

	}

}

/**
 * Возвращает массив тэгов для вставки в документы
 *
 * @param int $deid - идентификатор документа
 * @param int $did  - идентификатор сделки
 * @param int $clid - идентификатор клиента, автоматически находим по did
 * @param int $mcid - идентификатор собственной компании
 * @param int $pid  - идентификатор контакта
 *
 * @return array
 * @throws \Exception
 * @category Core
 * @package  Func
 * @uses     getNewTag('100','20');
 */
function getNewTag(int $deid = 0, int $did = 0, int $clid = 0, int $mcid = 0, int $pid = 0): array {

	$rootpath = dirname( __DIR__, 2 );

	$identity   = $GLOBALS['identity'];
	$sqlname    = $GLOBALS['sqlname'];
	$db         = $GLOBALS['db'];
	$fpath      = $GLOBALS['fpath'];
	$ndsRaschet = $GLOBALS['ndsRaschet'];
	$iduser     = $GLOBALS['iduser1'];

	$payer = 0;
	$summa = 0;
	//$mcid  = 0;
	$signer       = 0;
	$docOsnovanie = '';

	//$other = explode( ";", $db -> getOne( "SELECT other FROM {$sqlname}settings WHERE id = '$identity'" ) );

	//получим данные документа
	if ( $deid > 0 ) {

		$result         = $db -> getRow( "SELECT * FROM {$sqlname}contract WHERE deid = '$deid' AND identity = '$identity'" );
		$did            = (int)$result["did"];
		$doc_num        = $result["number"];
		$doc_date       = get_date( $result["datum"] );
		$doc_date_start = get_date( $result["datum_start"] );
		$doc_date_end   = get_date( $result["datum_end"] );
		$signer         = (int)$result["signer"];

	}

	//для случаев, когда действующий договор привязывается к сделке
	//в данном случае спека из новой сделки будет привязана при генерации нового файла договора
	if ( $deid > 0 && $did < 1 ) {

		$did = (int)$db -> getOne( "SELECT did FROM {$sqlname}dogovor WHERE dog_num = '$deid' AND identity = '$identity'" );

	}

	//получим данные из сделки
	if ( $did > 0 ) {

		$result = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
		$clid   = (int)$result["clid"];
		//$pid             = $result["pid"];
		$payer           = (int)$result["payer"];
		$summa           = $result["kol"];
		$marga           = $result["marga"];
		$iduser          = (int)$result["iduser"];
		$dog_adres       = $result["adres"];
		$dog_datum       = $result["datum"];
		$dog_title       = $result["title"];
		$dog_description = $result["content"];
		$dog_datum_start = get_date( $result["datum_start"] );
		$dog_datum_end   = get_date( $result["datum_end"] );
		$numb            = (int)$result["dog_num"];

		if($mcid == 0) {
			$mcid = (int)$result["mcid"];
		}

		if ( $pid < 1 ) {
			$pid = (int)yexplode( ";", (string)$result["pid_list"], 0 );
		}

		if ( $payer > 0 && $clid != $payer ) {
			$clid = $payer;
		}

		//найдем номер договора по сделке

		if ( $numb > 0 ) {

			$result   = $db -> getRow( "SELECT * FROM {$sqlname}contract WHERE deid = '$numb' AND identity = '$identity'" );
			$dog_num  = $result["number"];
			$dog_date = get_date( $result["datum"] );
			//$datum_start = get_date( $result["datum_start"] );
			//$datum_end   = get_date( $result["datum_end"] );

			$docOsnovanie = "Договор №".$dog_num." от ".$dog_date;

		}
		else {

			$result  = $db -> getRow( "SELECT invoice, datum FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity' LIMIT 1" );
			$invoice = $result["invoice"];
			$datum   = $result["datum"];

			$docOsnovanie = "Счет №".$invoice." от ".get_date( $datum );

		}

	}

	//если договор создается из карточки Клиента
	if ( $mcid == 0 ) {
		$mcid = (int)$db -> getOne( "SELECT id FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY id LIMIT 1" );
	}

	//найдем реквизиты нашей компании по id компании
	if ( $mcid > 0 ) {

		$result          = $db -> getRow( "SELECT * FROM {$sqlname}mycomps WHERE id = '$mcid' AND identity = '$identity'" );
		$name_ur         = $result["name_ur"];
		$name_shot       = $result["name_shot"];
		$address_yur     = $result["address_yur"];
		$address_post    = $result["address_post"];
		$dir_name        = $result["dir_name"];
		$dir_signature   = $result["dir_signature"];
		$dir_status      = $result["dir_status"];
		$dir_osnovanie   = $result["dir_osnovanie"];
		$result["stamp"] = ($result["stamp"] != '') ? $result["stamp"] : 'signature.png';
		$result["logo"]  = ($result["logo"] != '') ? $result["logo"] : 'logo.png';

		$mc_stamp = "../cash/".$fpath."templates/".$result["stamp"];
		$mc_logo  = "../cash/".$fpath."templates/".$result["logo"];
		$innkpp   = explode( ";", (string)$result["innkpp"] );
		$okog     = explode( ";", (string)$result["okog"] );

		$inn = $innkpp[0];
		$kpp = $innkpp[1];

		if ( !empty( $okog ) ) {

			$comp_okpo = $okog[0];
			$comp_ogrn = $okog[1];

		}

		//$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
		//$settingsDefault = json_decode( file_get_contents( $settingsFile ), true );

		//найдем банковские реквизиты по id расчетного счета
		$result  = $db -> getRow( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$mcid' AND isDefault = 'yes' AND identity = '$identity' LIMIT 1" );
		$bank_rs = $result["rs"];
		$bankr   = explode( ";", $result["bankr"] );

		$bank_bik  = $bankr[0];
		$bank_ks   = $bankr[1];
		$bank_name = $bankr[2];

	}

	//найдем реквизиты клиента
	if ( $clid > 0 ) {

		$data             = get_client_recv( $clid, "yes" );
		$castName         = $data['castName'];
		$castUrName       = $data['castUrName'];
		$castUrNameShort  = $data['castUrNameShort'];
		$castInn          = $data['castInn'];
		$castKpp          = $data['castKpp'];
		$castBank         = $data['castBank'];
		$castBankKs       = $data['castBankKs'];
		$castBankRs       = $data['castBankRs'];
		$castBankBik      = $data['castBankBik'];
		$castOkpo         = $data['castOkpo'];
		$castOgrn         = $data['castOgrn'];
		$castDirName      = $data['castDirName'];
		$castDirSignature = $data['castDirSignature'];
		$castDirStatus    = $data['castDirStatus'];
		$castDirStatusSig = $data['castDirStatusSig'];
		$castDirOsnovanie = $data['castDirOsnovanie'];
		$castUrAddr       = $data['castUrAddr'];
		$castFacAddr      = $data['castFacAddr'];

		if ( !$iduser ) {
			$iduser = (int)$data['iduser'];
		}

		if ( $castUrName == '' ) {
			$castUrName = $castName;
		}
		if ( $castName == '' ) {
			$castName = $castUrName;
		}

	}
	elseif ( $pid > 0 && $payer < 1 ) {

		$castUrName = current_person( $pid );

	}

	//схема налогооблажения
	$nalogScheme = getNalogScheme( 0, $mcid );

	//выводим спецификацию
	$spekaArray = $tovarArray = $uslugaArray = $materialArray = $invoiceArray = [];
	$spekaSum   = $spekaNum = $spekaSumWoN = $spekaNds = $creditSum = 0;
	//$tovarSum    = $tovarNum = $tovarSumWoN = $tovarNds = 0;
	//$uslugiSum   = $uslugiNum = $uslugiSumWoN = $uslugiNds = 0;
	//$materialSum = $materialNum = $materialSumWoN = $materialNds = 0;
	$i = $t = $m = $u = 1;

	if ( $did > 0 ) {

		$res = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity' ORDER BY spid" );
		while ($da = $db -> fetch( $res )) {

			$description = '';
			$priceWoNds  = $nds_i = $summaWoNds = $kol_sum = 0;

			if ( $nalogScheme['nalog'] == 0 ) {
				$da['nds'] = 0;
			}

			if ( $da['tip'] != 2 ) {

				$kol_sum = pre_format( $da['kol'] ) * pre_format( $da['dop'] ) * pre_format( $da['price'] );
				$in_sum  = pre_format( $da['price_in'] ) * pre_format( $da['kol'] ) * pre_format( $da['dop'] );

				$ndsa = getNalog( $kol_sum, $da['nds'], $ndsRaschet );
				$ndsi = getNalog( $da['price'], $da['nds'], $ndsRaschet );

				//сумма НДС
				$nds_i = $ndsa['nalog'];

				//цена без НДС
				if ( $ndsRaschet == 'yes' ) {

					$priceWoNds = pre_format( $da['price'] ) * pre_format( $da['dop'] );
					$summaWoNds = $priceWoNds * pre_format( $da['kol'] );

					$spekaNds    += $nds_i; //НДС
					$spekaSum    += $kol_sum + $ndsa['nalog']; //Сумма
					$spekaSumWoN += $summaWoNds; //Сумма

					$kol_sum += $ndsa['nalog'];

				}
				else {

					$priceWoNds = (pre_format( $da['price'] ) - $ndsi['nalog']) * pre_format( $da['dop'] );
					$summaWoNds = $priceWoNds * pre_format( $da['kol'] );

					$spekaNds    += $nds_i; //НДС
					$spekaSum    += $kol_sum; //Сумма
					$spekaSumWoN += $summaWoNds; //Сумма

				}

				//итоговые цифры
				$spekaNum = $spekaNum + pre_format( $da['kol'] ) * pre_format( $da['dop'] );

			}

			//описание продукта
			if ( $da['prid'] > 0 ) {
				$description = $db -> getOne( "SELECT descr FROM {$sqlname}price WHERE n_id = '".$da['prid']."' AND identity = '$identity'" );
			}

			if ( $da['tip'] != 2 ) {

				$spekaArray[] = $st = [
					"Number"      => $i,
					"Artikul"     => $da['artikul'],
					"Title"       => untag2($da['title']),
					"Kol"         => num_format( $da['kol'] ),
					"Edizm"       => $da['edizm'],
					"Dop"         => num_format( $da['dop'] ),
					"PriceWoNds"  => num_format( $priceWoNds ),
					"Price"       => num_format( $da['price'] ),
					"NdsPer"      => $da['nds'],
					"Nds"         => num_format( $nds_i ),
					"SumWoNds"    => num_format( $summaWoNds ),
					"Sum"         => num_format( $kol_sum ),
					"Description" => untag2($description),
					"Comment"     => $da['comments'],
					"spid"        => (int)$da['spid'],
					"prid"        => (int)$da['prid']
				];

				if ( $da['tip'] == 0 ) {

					$st['Number'] = $t;
					$tovarArray[] = $st;

					$t++;

				}
				if ( $da['tip'] == 1 ) {

					$st['Number']  = $u;
					$uslugaArray[] = $st;

					$u++;

				}

				$i++;

			}
			else {

				$materialArray[] = [
					"Number"      => $m,
					"Artikul"     => $da['artikul'],
					"Title"       => untag2($da['title']),
					"Kol"         => num_format( $da['kol'] ),
					"Edizm"       => $da['edizm'],
					"Dop"         => num_format( $da['dop'] ),
					"PriceWoNds"  => num_format( $priceWoNds ),
					"Price"       => num_format( $da['price'] ),
					"NdsPer"      => $da['nds'],
					"Nds"         => num_format( $nds_i ),
					"SumWoNds"    => num_format( $summaWoNds ),
					"Sum"         => num_format( $kol_sum ),
					"Description" => untag2($description),
					"Comment"     => $da['comments'],
					"spid"        => (int)$da['spid'],
					"prid"        => (int)$da['prid']
				];

				$m++;

			}

		}

		//выводим график платежей
		$j = 1;
		//$creditSum = 0;
		$res = $db -> query( "SELECT * FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity' ORDER BY crid" );
		while ($da = $db -> fetch( $res )) {

			$invoiceArray[] = [
				"Count"    => $j,
				"Number"   => $da['invoice'],
				"Date"     => format_date_rus_name( get_smdate( $da['datum'] ) ),
				"DatePlan" => format_date_rus_name( $da['datum_credit'] ),
				"DateOrig" => $da['datum_credit'],
				"Summa"    => num_format( $da['summa_credit'] ),
				"Nds"      => num_format( $da['nds_credit'] ),
				"crid"     => (int)$da['crid']
			];

			$creditSum += $da['summa_credit'];

			$j++;

		}

	}

	if ( $spekaSum == 0 ) {
		$spekaSum = $summa;
	}

	if ( $docOsnovanie == '' ) {
		$docOsnovanie = ' ';
	}

	//заменим в шаблоне подстановочные данные на нужные
	$tags = [];

	$tags['did']  = $did;
	$tags['clid'] = $clid;

	//спецификация (без материалов)
	$tags["speka"]             = $spekaArray;
	$tags["spekaCount"]        = count( $spekaArray );
	$tags["spekaKol"]          = num_format( arraysum( $spekaArray, 'Kol', true ) );//num_format($spekaNum);
	$tags["spekaNds"]          = num_format( arraysum( $spekaArray, 'Nds', true ) );//num_format($spekaNds);
	$tags["spekaSum"]          = num_format( arraysum( $spekaArray, 'Sum', true ) );//num_format($spekaSum);
	$tags["spekaSumWoNds"]     = num_format( arraysum( $spekaArray, 'SumWoNds', true ) );//num_format($spekaSumWoN);
	$tags["spekadocOsnovanie"] = $docOsnovanie;

	//позиции Товар
	$tags["tovar"]         = $tovarArray;
	$tags["tovarCount"]    = count( $tovarArray );
	$tags["tovarKol"]      = num_format( arraysum( $tovarArray, 'Kol', true ) );//num_format($tovarNum);
	$tags["tovarNds"]      = num_format( arraysum( $tovarArray, 'Nds', true ) );//num_format($tovarNds);
	$tags["tovarSum"]      = num_format( arraysum( $tovarArray, 'Sum', true ) );//num_format($tovarSum);
	$tags["tovarSumWoNds"] = num_format( arraysum( $tovarArray, 'SumWoNds', true ) );//num_format($tovarSumWoN);

	//позиции Услуга
	$tags["usluga"]         = $uslugaArray;
	$tags["uslugaCount"]    = count( $uslugaArray );
	$tags["uslugaKol"]      = num_format( arraysum( $uslugaArray, 'Kol', true ) );//num_format($uslugiNum);
	$tags["uslugaNds"]      = num_format( arraysum( $uslugaArray, 'Nds', true ) );//num_format($uslugiNds);
	$tags["uslugaSum"]      = num_format( arraysum( $uslugaArray, 'Sum', true ) );//num_format($uslugiSum);
	$tags["uslugaSumWoNds"] = num_format( arraysum( $uslugaArray, 'SumWoNds', true ) );//num_format($uslugiSumWoN);

	//Позиция Материал
	$tags["material"]         = $materialArray;
	$tags["materialCount"]    = count( $materialArray );
	$tags["materialKol"]      = num_format( arraysum( $materialArray, 'Kol', true ) );//num_format($materialNum);
	$tags["materialNds"]      = num_format( arraysum( $materialArray, 'Nds', true ) );//num_format($materialNds);
	$tags["materialSum"]      = num_format( arraysum( $materialArray, 'Sum', true ) );//num_format($materialSum);
	$tags["materialSumWoNds"] = num_format( arraysum( $materialArray, 'SumWoNds', true ) );//num_format($materialSumWoN);

	$tags["invoices"]      = $invoiceArray;
	$tags["invoicesCount"] = count( $invoiceArray );

	$tags["compUrName"]       = $name_ur ?? NULL;
	$tags["compShotName"]     = $name_shot ?? NULL;
	$tags["compUrAddr"]       = $address_yur ?? NULL;
	$tags["compFacAddr"]      = $address_post ?? NULL;
	$tags["compInn"]          = $inn ?? NULL;
	$tags["compKpp"]          = $kpp ?? NULL;
	$tags["compOgrn"]         = $comp_ogrn ?? NULL;
	$tags["compOkpo"]         = $comp_okpo ?? NULL;
	$tags["compBankName"]     = $bank_name ?? NULL;
	$tags["compBankBik"]      = $bank_bik ?? NULL;
	$tags["compBankKs"]       = $bank_ks ?? NULL;
	$tags["compBankRs"]       = $bank_rs ?? NULL;
	$tags["compDirName"]      = $dir_name ?? NULL;
	$tags["compDirStatus"]    = $dir_status ?? NULL;
	$tags["compDirSignature"] = $dir_signature ?? NULL;
	$tags["compDirOsnovanie"] = $dir_osnovanie ?? NULL;
	$tags["compUser"]         = current_user( $iduser );
	$tags["compSignature"]    = $mc_stamp ?? NULL;
	$tags["compLogo"]         = $mc_logo ?? NULL;

	// если указан кастомный подписант
	if ( $signer > 0 ) {

		$xsigner = getSigner( $signer );

		$tags['compDirName']      = $xsigner["title"];
		$tags['compDirSignature'] = $xsigner["signature"];
		$tags['compDirStatus']    = $xsigner["status"];
		$tags['compDirOsnovanie'] = $xsigner["osnovanie"];

		$tags["compSignature"] = '../cash/'.$fpath.'templates/'.$xsigner['stamp'];

	}

	//контакты компании из Общих настроек

	$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";

	$settings = (file_exists( $settingsFile )) ? json_decode( file_get_contents( $settingsFile ), true ) : $db -> getRow( "select * from {$sqlname}settings WHERE id = '$identity'" );

	$company       = $settings["company"];
	$company_site  = $settings["company_site"];
	$company_mail  = $settings["company_mail"];
	$company_phone = $settings["company_phone"];

	$tags["compName"]  = $company;
	$tags["compPhone"] = $company_phone;
	$tags["compSite"]  = $company_site;
	$tags["compMail"]  = $company_mail;

	$tags["castName"]         = $castName ?? NULL;
	$tags["castUrName"]       = $castUrName ?? NULL;
	$tags["castUrNameShort"]  = $castUrNameShort ?? NULL;
	$tags["castInn"]          = $castInn ?? NULL;
	$tags["castKpp"]          = $castKpp ?? NULL;
	$tags["castBankName"]     = $castBank ?? NULL;
	$tags["castBankKs"]       = $castBankKs ?? NULL;
	$tags["castBankRs"]       = $castBankRs ?? NULL;
	$tags["castBankBik"]      = $castBankBik ?? NULL;
	$tags["castOkpo"]         = $castOkpo ?? NULL;
	$tags["castOgrn"]         = $castOgrn ?? NULL;
	$tags["castDirName"]      = $castDirName ?? NULL;
	$tags["castDirSignature"] = $castDirSignature ?? NULL;
	$tags["castDirStatus"]    = $castDirStatus ?? NULL;
	$tags["castDirStatSig"]   = $castDirStatusSig ?? NULL;
	$tags["castDirOsnovanie"] = $castDirOsnovanie ?? NULL;
	$tags["castUrAddr"]       = $castUrAddr ?? NULL;
	$tags["castFacAddr"]      = $castFacAddr ?? NULL;

	$tags['invoiceNum']        = $tags['invoices'][0]['Number'];
	$tags['invoiceDatum']      = $tags['invoices'][0]['Date'];
	$tags["invoiceDatumShort"] = $tags['invoices'][0]['DateOrig'] != '' ? modifyDatetime( $tags['invoices'][0]['DateOrig'], ["format" => "d.m.Y"] ) : "";

	//$tags["invoiceNum"]        = $invoice;
	//$tags["invoiceDatum"]      = format_date_rus_name( cut_date( $datum ) );
	//$tags["invoiceDatumShort"] = modifyDatetime( $datum, ["format" => "d.m.Y"] );
	//$tags["invoiceDatumCredit"] = format_date_rus_name($datum_credit)." года";

	//$tags["dogSpeka"] = $speca;
	//$tags["dogSpekWONds"] = $specaSmall;

	$tags["summaCredit"]         = num_format( $creditSum );
	$tags["summaNds"]            = num_format( $spekaNds );
	$tags["summaPropis"]         = mb_ucfirst( trim( num2str( (float)$spekaSum ) ) );
	$tags["summaUslugaPropis"]   = mb_ucfirst( trim( num2str( (float)$tags["uslugaSum"] ) ) );
	$tags["summaTovarPropis"]    = mb_ucfirst( trim( num2str( (float)$tags["tovarSum"] ) ) );
	$tags["summaMaterialPropis"] = mb_ucfirst( trim( num2str( (float)$tags["materialSum"] ) ) );
	$tags["summaDogovor"]        = num_format( (float)$summa );
	$tags["summaDogovorPropis"]  = mb_ucfirst( trim( num2str( (float)$summa ) ) );

	$tags["dogNum"]        = $dog_num ?? NULL;
	$tags["dogDate"]       = $dog_date ?? NULL;
	$tags["dogDateCreate"] = $dog_datum ?? NULL;//новое - дата создания
	$tags["dogAdres"]      = $dog_adres ?? NULL;//новое - адрес
	$tags["dogTitle"]      = $dog_title ?? NULL;//новое - название сделки
	$tags["dogContent"]    = $dog_description ?? NULL;//новое - описание сделки
	$tags["dogDataStart"]  = $dog_datum_start ?? NULL;
	$tags["dogDataEnd"]    = $dog_datum_end ?? NULL;

	$tags["docNum"]       = $doc_num ?? NULL;
	$tags["docDate"]      = $doc_date_start ?? NULL;//$doc_date;
	$tags["docDStart"]    = $doc_date_start ?? NULL;
	$tags["docDEnd"]      = $doc_date_end ?? NULL;
	$tags["docOsnovanie"] = $docOsnovanie;

	if ( empty( $tags["dogNum"] ) ) {
		$tags["dogNum"] = $tags["docNum"];
	}
	if ( empty( $tags["dogDate"] ) ) {
		$tags["dogDate"] = $tags["docDate"];
	}

	$result     = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser'" );
	$userName   = $result["title"];
	$UserStatus = $result["tip"];
	$UserPhone  = $result["phone"];
	$UserMob    = $result["mob"];
	$UserEmail  = $result["email"];

	$tags["currentDatum"]      = format_date_rus_name( current_datum() );
	$tags["currentDatumShort"] = format_date_rus( current_datum() );
	$tags["UserName"]          = $userName;
	$tags["UserPhone"]         = $UserPhone;
	$tags["UserMob"]           = $UserMob;
	$tags["UserStatus"]        = $UserStatus;
	$tags["UserEmail"]         = $UserEmail;

	//добавим теги из полей клиента
	if ( $clid ) {

		$includ = [
			'clid',
			'title',
			'address',
			'phone',
			'fax',
			'mail_url',
			'site_url'
		];
		$fields = [];

		$res = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='client' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order" );
		while ($xdata = $db -> fetch( $res )) {

			if ( in_array( $xdata['fld_name'], $includ ) || str_contains( $xdata['fld_name'], 'input' ) ) {
				$fields[] = $xdata['fld_name'];
			}

		}

		$data = get_client_info( $clid, 'yes' );

		if ( (int)$pid == 0 ) {
			$pid  = (int)$data['pid'];
		}

		foreach ( $fields as $field ) {
			$tags[ "clientF".$field ] = $data[ $field ];
		}

		//$includ = [];

	}

	//добавим теги из полей контакта
	//этот блок нужен, чтобы обрабатывать тэги даже для компаний без контактов

	//if ($pid > 0) {

	$pinclud = [
		'pid',
		'person',
		'ptitle',
		'tel',
		'mob',
		'mail',
		'rol'
	];
	$fields  = [];

	$res = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='person' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order" );
	while ($data = $db -> fetch( $res )) {

		if ( in_array( $data['fld_name'], $pinclud, true ) || str_contains( $data['fld_name'], 'input' ) ) {
			$fields[] = $data['fld_name'];
		}

	}

	$data = get_person_info( $pid, 'yes' );
	foreach ( $fields as $field ) {
		$tags[ "personF".$field ] = $data[ $field ];
	}

	//}

	//добавим теги из полей клиента
	if ( (int)$did > 0 ) {

		$data = get_dog_info( $did, 'yes' );

		$res = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' AND fld_name LIKE '%input%' AND fld_on='yes' AND identity = '$identity' ORDER BY fld_order" );
		while ($da = $db -> fetch( $res )) {

			$tags[ "dogF".$da['fld_name'] ] = $data[ $da['fld_name'] ];

		}

		//$includ = [];

	}

	$deal = get_dog_info( $did, 'yes' );

	$currency = (int)$deal['idcurrency'] > 0 ? (new Currency()) -> currencyInfo( (int)$deal['idcurrency'] ) : [];
	$course   = (int)$deal['idcourse'] > 0 ? (new Currency()) -> courseInfo( (int)$deal['idcourse'] ) : [];

	$tags['currencyName']   = !empty( $currency ) ? $currency['name'] : '';
	$tags['currencySymbol'] = !empty( $currency ) ? $currency['symbol'] : '';
	$tags['currencyCourse'] = !empty( $course ) ? $course['course'] : '';

	return $tags;

}

/**
 * Расширяет функцию getNewTag и дополняет новыми данными
 *
 * @param int $did
 * @param int $clid
 * @param int $pid
 *
 * @return array
 * @throws Exception
 * @package  Func
 * @category Core
 */
function getSmartTag(int $did = 0, int $clid = 0, int $pid = 0): array {

	$deal   = $mob = [];
	$iduser = 0;
	$phone  = $mobile = '';

	//параметры сделки
	if ( $did > 0 ) {

		$deal = Deal ::info( $did );

		if ( $deal['person']['pid'] < 1 ) {

			$person = get_person_info( $deal['client']['pid'], "yes" );

			$deal['person']['pid']   = $person['pid'];
			$deal['person']['email'] = $person['mail'];
			$deal['person']['mob']   = $person['mob'];
			$deal['person']['phone'] = $person['tel'];

		}

		$iduser = $deal['iduser'];

	}
	elseif ( $clid > 0 ) {

		$client = Client ::info( $clid );

		if ( $client['client']['pid'] > 0 && $client['client']['type'] == 'client' ) {

			$person = get_person_info( $client['client']['pid'], "yes" );

			$deal['person']['title'] = $person['person'];
			$deal['person']['pid']   = $person['pid'];
			$deal['person']['email'] = $person['mail'];
			$deal['person']['mob']   = $person['mob'];
			$deal['person']['phone'] = $person['tel'];

			//print_r($person);
			//print_r($deal);

		}
		else {

			$deal['person']['title'] = $client['client']['title'];
			$deal['person']['email'] = $client['client']['mail_url'];
			$deal['person']['mob']   = $client['client']['phone'];
			$deal['person']['phone'] = $client['client']['phone'];
			$deal['client']['phone'] = $client['client']['phone'];

			//print_r($client);
			//print_r($deal);

		}

		$iduser = $client['iduser'];

	}
	elseif ( $pid > 0 ) {

		$person = get_person_info( $pid, "yes" );

		$deal['person']['pid']   = $person['pid'];
		$deal['person']['email'] = $person['mail'];
		$deal['person']['mob']   = $person['mob'];
		$deal['person']['phone'] = $person['tel'];

		$iduser = $person['iduser'];

	}

	$email = yexplode( ",", (string)$deal['person']['email'] );
	if ( count( $email[0] ) == 0 ) {
		$email = yexplode( ",", (string)$deal['client']['email'] );
	}

	$name = $deal['person']['title'];
	if ( $name == '' ) {
		$name = $deal['client']['title'];
	}

	$mob = (array)yexplode( ",", (string)$deal['person']['mob'] );
	if ( empty( $mob ) ) {
		$cmob[] = $deal['client']['phone'];
	}

	$cmob = yexplode( ",", (string)$deal['client']['phone'] );
	$pmob = yexplode( ",", (string)$deal['person']['mob'] );
	$ptel = yexplode( ",", (string)$deal['person']['phone'] );

	if ( isPhoneMobile( $cmob[0] ) ) {
		$mobile = prepareMobPhone( $cmob[0] );
	}
	elseif ( isPhoneMobile( $pmob[0] ) ) {
		$mobile = prepareMobPhone( $pmob[0] );
	}
	elseif ( isPhoneMobile( $ptel[0] ) ) {
		$mobile = prepareMobPhone( $ptel[0] );
	}

	if ( $cmob[0] != '' && isPhoneMobile( $cmob[0] ) === false ) {
		$phone = prepareMobPhone( $cmob[0] );
	}
	elseif ( $pmob[0] != '' && isPhoneMobile( $pmob[0] ) === false ) {
		$phone = prepareMobPhone( $pmob[0] );
	}
	elseif ( $ptel[0] != '' && isPhoneMobile( $ptel[0] ) === false ) {
		$phone = prepareMobPhone( $ptel[0] );
	}

	//готовим тэги для шаблона и контакты для отправки
	$tags = getNewTag( 0, $did, $clid );

	$tags['iduser'] = $iduser;

	$tags['castomerName']   = $name;
	$tags['castomerEmail']  = $email[0];
	$tags['castomerPhone']  = formatPhone2( $phone );
	$tags['castomerMobile'] = formatPhone2( $mobile );
	$tags['dealTitle']      = $deal['title'];

	return $tags;

}

/**
 * По номеру телефона возвращает CallerID (имя абонента)
 * Также создает глобальные переменные - !отключено с версии 2018.6
 * global $clientID - clid
 * global $clientTitle - Название клиента
 * global $personID - pid
 * global $personTitle - Имя контакта
 * global $userID - iduser
 * global $userTitle - Имя пользователя
 *
 * @param string $phone    - номер телефона
 * @param bool   $shownum  - возвращать с Именем и номер телефона
 * @param bool   $translit - транслетировать имя
 * @param bool   $full     - вернуть полный массив результатов
 *
 * @return mixed|string
 * @category Core
 * @package  Func
 */
function getCallerID(string $phone, bool $shownum = false, bool $translit = false, bool $full = false) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$callerID    = '';
	$clientID    = $personID = $userID = 0;
	$clientTitle = $personTitle = $userTitle = $phoneIN = '';

	$result      = $db -> getRow( "SELECT sip_channel, sip_numout, sip_pfchange FROM {$sqlname}sip WHERE identity = '".$identity."'" );
	$sip_channel = $result["sip_channel"];
	$sip_numout  = $result["sip_numout"];

	if ( $phone != '' ) {

		//для внутренних номеров
		if ( strlen( $phone ) < 6 ) {

			$phone = str_replace( $sip_channel, "", $phone );

			$result = $db -> getRow( "
			SELECT 
				{$sqlname}user.iduser,
				{$sqlname}user.title,
				{$sqlname}user.phone_in 
			FROM {$sqlname}user 
			WHERE 
				({$sqlname}user.phone_in = '$phone' OR replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$phone."%') AND
				{$sqlname}user.identity = '$identity'
			" );

			$callerID  = $userTitle = $result["title"];
			$userID    = (int)$result["iduser"];
			$userTitle = $result["user"];
			$phoneIN   = $result["phone_in"];

			if ( !$callerID ) {
				$callerID = 'Unknown';
			}

			if ( $shownum ) {
				$callerID = ''.$callerID;
			}

			if ( $translit ) {
				$callerID = translit( $callerID );
			}

		}
		else {

			$phone1 = prepareMobPhone( $phone );

			if ( strlen( $phone1 ) == 11 || strlen( $phone ) == 8 ) {
				$phone1 = substr( $phone1, 1 );
			}
			//else $phone = $phone;

			//ищем оператора
			$result = $db -> getRow( "
				SELECT 
					{$sqlname}user.iduser,
					{$sqlname}user.title,
					{$sqlname}user.phone_in,
					{$sqlname}user.phone,
					{$sqlname}user.mob 
				FROM {$sqlname}user 
				WHERE 
					(
					{$sqlname}user.phone_in = '$phone' OR 
					replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%' OR
					replace(replace(replace(replace(replace({$sqlname}user.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%'
					)
					AND {$sqlname}user.identity = '$identity'
			" );

			$callerID = $result["title"].' [user]';
			//$userTitle = $result["title"];
			$userID    = (int)$result["iduser"];
			$userTitle = $result["title"];
			$phoneIN   = $result["phone_in"];

			if ( $callerID != ' [user]' ) {
				goto res;
			}

			//ищем контакт
			$res = $db -> getRow( "
				SELECT
					{$sqlname}personcat.person as person,
					{$sqlname}personcat.pid as pid,
					{$sqlname}clientcat.clid as clid,
					{$sqlname}clientcat.title as title,
					{$sqlname}user.iduser as iduser,
					{$sqlname}user.title as user,
					{$sqlname}user.phone_in as phone_in
				FROM {$sqlname}personcat
					LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}personcat.clid = {$sqlname}clientcat.clid
				WHERE ((replace(replace(replace(replace(replace({$sqlname}personcat.tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%') or (replace(replace(replace(replace(replace({$sqlname}personcat.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%')) and {$sqlname}personcat.identity = '$identity'
				ORDER by {$sqlname}personcat.pid DESC LIMIT 1
			" );

			$callerID    = $res["person"];
			$personID    = (int)$res["pid"];
			$personTitle = $callerID;
			$clientID    = (int)$res["clid"];
			$clientTitle = $res["title"];
			$userID      = (int)$res["iduser"];
			$userTitle   = $res["user"];
			$phoneIN     = $res["phone_in"];

			if ( $callerID != '' ) {
				goto res;
			}

			//ищем в клиентах
			$res = $db -> getRow( "
				SELECT
				{$sqlname}clientcat.clid as clid,
				{$sqlname}clientcat.pid as pid,
				{$sqlname}clientcat.title as title,
				{$sqlname}user.iduser as iduser,
				{$sqlname}user.title as user,
				{$sqlname}user.phone_in as phone_in
				FROM {$sqlname}clientcat
				LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
				WHERE (replace(replace(replace(replace(replace({$sqlname}clientcat.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%' OR replace(replace(replace(replace(replace({$sqlname}clientcat.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%') and {$sqlname}clientcat.identity = '$identity'
				ORDER by {$sqlname}clientcat.clid DESC LIMIT 1
			" );

			$callerID    = $res["title"];
			$clientID    = (int)$res["clid"];
			$personID    = (int)$res["pid"];
			$personTitle = current_person( $personID );
			$clientTitle = $callerID;
			$userID      = (int)$res["iduser"];
			$userTitle   = $res["user"];
			$phoneIN     = $res["phone_in"];

			res:

			if ( !$callerID ) {
				$callerID = "Not found";
			}
			if ( $shownum ) {
				$callerID = ''.$callerID.' <'.preparePhone( $phone ).'>';
			}
			if ( $translit ) {
				$callerID = translit( $callerID );
			}

		}

	}

	if ( !$full ) {

		return $callerID;

	}

	return json_encode_cyr( [
		"clid"     => $clientID,
		"client"   => $clientTitle,
		"pid"      => $personID,
		"person"   => $personTitle,
		"iduser"   => $userID,
		"user"     => $userTitle,
		"callerID" => $callerID,
		"phonein"  => $phoneIN
	] );

}

/**
 * По номеру телефона возвращает массив данных
 * - int clientID - clid
 * - clientTitle - Название клиента
 * - int personID - pid
 * - personTitle - Имя контакта
 * - int userID - iduser
 * - userTitle - Имя пользователя
 * - callerID - Имя абонента
 * - phonein - Внутренний номер
 *
 * @param string $phone    - номер телефона
 * @param bool   $shownum  - возвращать с Именем и номер телефона
 * @param bool   $translit - транслетировать имя
 * @return array|string[]
 * @category Core
 * @package  Func
 */
function getxCallerID(string $phone, bool $shownum = false, bool $translit = false): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$callerID    = '';
	$clientID    = $personID = $userID = 0;
	$clientTitle = $personTitle = $userTitle = $phoneIN = '';

	$result      = $db -> getRow( "SELECT sip_channel, sip_numout, sip_pfchange FROM {$sqlname}sip WHERE identity = '$identity'" );
	$sip_channel = $result["sip_channel"];
	$sip_numout  = $result["sip_numout"];

	if ( $phone != '' ) {

		//для внутренних номеров
		if ( strlen( $phone ) < 6 ) {

			$phone = str_replace( $sip_channel, "", $phone );

			$result = $db -> getRow( "
			SELECT 
				us.iduser,
				us.title,
				us.phone_in 
			FROM {$sqlname}user `us`
			WHERE 
				(us.phone_in = '$phone' OR replace(replace(replace(replace(replace(us.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%') AND
				us.identity = '$identity'
			" );

			$callerID  = $result["title"];
			$userID    = (int)$result["iduser"];
			$userTitle = $result["title"];
			$phoneIN   = $result["phone_in"];

			if ( !$callerID ) {
				$callerID = 'Unknown';
			}

			if ( $shownum ) {
				$callerID = ''.$callerID;
			}

			if ( $translit ) {
				$callerID = translit( $callerID );
			}

		}
		else {

			$phone1 = prepareMobPhone( $phone );

			if ( strlen( $phone1 ) == 11 || strlen( $phone ) == 8 ) {
				$phone1 = substr( $phone1, 1 );
			}
			//else $phone = $phone;

			//ищем оператора
			$result = $db -> getRow( "
				SELECT 
					us.iduser,
					us.title,
					us.phone_in,
					us.phone,
					us.mob 
				FROM {$sqlname}user `us`
				WHERE 
					us.secrty = 'yes' AND
					(
					us.phone_in = '$phone' OR 
					replace(replace(replace(replace(replace(us.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%' OR
					replace(replace(replace(replace(replace(us.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%'
					)
					AND us.identity = '$identity'
			" );

			$callerID = $result["title"].' [user]';
			//$userTitle = $result["title"];
			$userID    = (int)$result["iduser"];
			$userTitle = $result["title"];
			$phoneIN   = $result["phone_in"];

			if ( $callerID != ' [user]' ) {
				goto res;
			}

			//ищем контакт
			$res = $db -> getRow( "
				SELECT
					{$sqlname}personcat.person as person,
					{$sqlname}personcat.pid as pid,
					{$sqlname}clientcat.clid as clid,
					{$sqlname}clientcat.title as title,
					{$sqlname}user.iduser as iduser,
					{$sqlname}user.title as user,
					{$sqlname}user.phone_in as phone_in
				FROM {$sqlname}personcat
					LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}personcat.clid = {$sqlname}clientcat.clid
				WHERE ((replace(replace(replace(replace(replace({$sqlname}personcat.tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%') or (replace(replace(replace(replace(replace({$sqlname}personcat.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%')) and {$sqlname}personcat.identity = '$identity'
				ORDER by {$sqlname}personcat.pid DESC LIMIT 1
			" );

			$callerID    = $res["person"];
			$personID    = (int)$res["pid"];
			$personTitle = $callerID;
			$clientID    = (int)$res["clid"];
			$clientTitle = $res["title"];
			$userID      = (int)$res["iduser"];
			$userTitle   = $res["user"];
			$phoneIN     = $res["phone_in"];

			if ( $callerID != '' ) {
				goto res;
			}

			//ищем в клиентах
			$res = $db -> getRow( "
				SELECT
					cc.clid as clid,
					cc.pid as pid,
					cc.title as title,
					us.iduser as iduser,
					us.title as user,
					us.phone_in as phone_in
				FROM {$sqlname}clientcat `cc`
					LEFT JOIN {$sqlname}user `us` ON cc.iduser = us.iduser
				WHERE 
					(
						replace(replace(replace(replace(replace(cc.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%' OR 
						replace(replace(replace(replace(replace(cc.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone1%'
					) and 
					cc.identity = '$identity'
				ORDER by cc.clid DESC LIMIT 1
			" );

			$callerID    = $res["title"];
			$clientID    = (int)$res["clid"];
			$personID    = (int)$res["pid"];
			$personTitle = current_person( $personID );
			$clientTitle = $callerID;
			$userID      = (int)$res["iduser"];
			$userTitle   = $res["user"];
			$phoneIN     = $res["phone_in"];

			res:

			if ( empty($callerID) ) {
				$callerID = "Not found";
			}
			if ( $shownum ) {
				$callerID = ''.$callerID.' <'.preparePhone( $phone ).'>';
			}
			if ( $translit ) {
				$callerID = translit( $callerID );
			}

		}

	}

	return [
		"clid"     => $clientID,
		"client"   => $clientTitle,
		"pid"      => $personID,
		"person"   => $personTitle,
		"iduser"   => $userID,
		"user"     => $userTitle,
		"callerID" => $callerID,
		"phonein"  => $phoneIN
	];

}

/**
 * Аналог функции getCallerID, возвращает данные в виде массива
 *
 * @param $phone
 *
 * @return array
 * [
 * "clid"      => $clientID,
 * "client"    => $clientTitle,
 * "pid"       => $personID,
 * "person"    => $personTitle,
 * "iduser"    => $userID,
 * "user"      => $userTitle,
 * "extension" => $phoneIN,
 * "mob"       => $mob,
 * "callerID"  => $callerID
 * ]
 * @category Core
 * @package  Func
 */
function getCaller($phone): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$clientTitle = $callerID = $userTitle = $phoneIN = $personTitle = $clientTitle = $mob = '';
	$userID      = $personID = $clientID = 0;

	$len = strlen( $phone );

	//для внутренних номеров
	if ( $len < 6 ) {

		$result   = $db -> getRow( "SELECT iduser,title,phone_in FROM {$sqlname}user WHERE (phone_in = '$phone' OR replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%') AND identity = '$identity'" );
		$callerID = $result["title"];
		//$userTitle = $result["title"];
		$userID    = (int)$result["iduser"];
		$userTitle = $result["user"];
		$phoneIN   = $result["phone_in"];

		if ( !$callerID ) {
			$callerID = 'Unknown';
		}

	}
	else {

		if ( in_array( $len, [
			11,
			8
		] ) ) {

			$phone1 = substr( $phone, 1 );

		}
		else {
			$phone1 = $phone;
		}

		$num = prepareMobPhone( $phone1 );

		//ищем оператора
		$result = $db -> getRow( "SELECT iduser,title,phone_in, phone, mob FROM {$sqlname}user WHERE (phone_in = '$phone' OR replace(replace(replace(replace(replace({$sqlname}user.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' OR replace(replace(replace(replace(replace({$sqlname}user.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%') AND identity = '$identity'" );

		$callerID  = $result["title"];
		$userTitle = $result["title"];
		$userID    = (int)$result["iduser"];
		$phoneIN   = $result["phone_in"];
		$mob       = $result["mob"];

		if ( $callerID != '' ) {
			goto res;
		}

		//ищем контакт
		$res = $db -> getRow( "
			SELECT
				{$sqlname}personcat.person as person,
				{$sqlname}personcat.pid as pid,
				{$sqlname}clientcat.clid as clid,
				{$sqlname}clientcat.title as title,
				{$sqlname}user.iduser as iduser,
				{$sqlname}user.title as user,
				{$sqlname}user.phone_in as phone_in,
				{$sqlname}user.mob as mob
			FROM {$sqlname}personcat
				LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
				LEFT JOIN {$sqlname}clientcat ON {$sqlname}personcat.clid = {$sqlname}clientcat.clid
			WHERE (replace(replace(replace(replace(replace({$sqlname}personcat.tel, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%' or replace(replace(replace(replace(replace({$sqlname}personcat.mob, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%') and {$sqlname}personcat.identity = '$identity'
			ORDER by {$sqlname}personcat.pid DESC LIMIT 1
		" );

		$callerID    = $res["person"];
		$personID    = (int)$res["pid"];
		$personTitle = $callerID;
		$clientID    = (int)$res["clid"];
		$clientTitle = $res["title"];
		$userID      = (int)$res["iduser"];
		$userTitle   = $res["user"];
		$phoneIN     = $res["phone_in"];
		$mob         = $res["mob"];

		if ( $callerID != '' ) {
			goto res;
		}

		//ищем в клиентах
		$res = $db -> getRow( "
			SELECT
			{$sqlname}clientcat.clid as clid,
			{$sqlname}clientcat.pid as pid,
			{$sqlname}clientcat.title as title,
			{$sqlname}user.iduser as iduser,
			{$sqlname}user.title as user,
			{$sqlname}user.phone_in as phone_in,
			{$sqlname}user.mob as mob
			FROM {$sqlname}clientcat
			LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
			WHERE (replace(replace(replace(replace(replace({$sqlname}clientcat.phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%' OR replace(replace(replace(replace(replace({$sqlname}clientcat.fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$num%') and {$sqlname}clientcat.identity = '$identity'
			ORDER by {$sqlname}clientcat.clid DESC LIMIT 1
		" );

		$callerID    = $res["title"];
		$clientID    = (int)$res["clid"];
		$personID    = (int)$res["pid"];
		$personTitle = current_person( $personID );
		$clientTitle = $callerID;
		$userID      = (int)$res["iduser"];
		$userTitle   = $res["user"];
		$phoneIN     = $res["phone_in"];
		$mob         = $res["mob"];

		res:

		if ( !$callerID ) {
			$callerID = "Not found";
		}

	}

	if ( $clientID == 0 ) {
		$clientID = 0;
	}

	if ( $personID == 0 ) {
		$personID = 0;
	}

	return [
		"clid"      => $clientID,
		"client"    => $clientTitle,
		"pid"       => $personID,
		"person"    => $personTitle,
		"iduser"    => $userID,
		"user"      => $userTitle,
		"extension" => $phoneIN,
		"mob"       => $mob,
		"callerID"  => $callerID
	];

}

/**
 * Возвращает iduser сотрудника по внутреннему номеру
 *
 * @param $phone
 *
 * @return mixed $userID
 * @category Core
 * @package  Func
 */
function getUserID($phone) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	global $userID;

	$userID = $db -> getOne( "SELECT iduser FROM {$sqlname}user WHERE phone_in = '$phone' OR  phone = '".prepareMobPhone( $phone )."' AND identity = '$identity'" );

	return $userID;

}

/**
 * Пересчет сумм по сделке
 *
 * @param $did
 *
 * @return string
 * @category Core
 * @package  Func
 */
function reCalculate($did): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	//$marga = 0;

	$res       = $db -> getRow( "SELECT calculate, kol FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
	$calculate = $res["calculate"];
	$kol       = $res["kol"];

	$isper = (isServices( (int)$did )) ? 'yes' : 'no';

	if ( $calculate == 'yes' ) {

		if ( $isper != 'yes' ) {

			$sum    = 0;
			$sum_in = 0;

			//найдем сумму по спецификации, если она включена (без материалов)
			$res = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' AND tip != '2' AND identity = '$identity' ORDER BY spid" );
			while ($data = $db -> fetch( $res )) {

				$nds    = 1;
				$sum    += $data['kol'] * $data['price'] / $nds * $data['dop'];
				$sum_in += $data['price_in'] / $nds * $data['kol'] * $data['dop'];

			}

			$marga = $sum - $sum_in;

			//print $sum."<br>";
			//print $sum_in."<br>";
			//print $marga."<br>";

		}
		else {

			//по спеке найдем сумму платежа и маржу
			$res      = $db -> getRow( "SELECT spid, SUM(price * kol) AS sum, SUM(price_in * kol) AS sum_in FROM {$sqlname}speca WHERE did = '$did' AND tip!='2' AND identity = '$identity'" );
			$summa_sp = $res["sum"];
			$marga_sp = $res["sum"] - $res["sum_in"];

			//найдем долю маржи в спецификации
			$dolya = $marga_sp / $summa_sp;

			//найдем маржу по оплатам
			$sum   = $db -> getOne( "SELECT SUM(summa_credit) AS kol FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity'" );
			$marga = $sum * $dolya;

		}

	}
	else {

		//или сумму, указанную в сделке
		$res   = $db -> getRow( "SELECT kol, marga FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
		$sum   = $res["kol"];
		$marga = $res["marga"];

	}

	//если маржа не указана
	if ( $sum == 0 ) {
		$sum = $kol;
	}
	//if ($marga == 0) $marga = $sum;

	// найдем сумму поставщиков и партнеров, которые помечены для списания
	$sum_prov = $db -> getOne( "SELECT SUM(summa) FROM {$sqlname}dogprovider WHERE did = '$did' AND recal = '0' AND identity = '$identity'" );
	$marga    -= $sum_prov;

	// найдем сумму материалов по спецификации
	$sum_material = $db -> getOne( "SELECT SUM(kol * price_in) FROM {$sqlname}speca WHERE did = '$did' AND tip = '2' AND identity = '$identity'" );
	$marga        -= $sum_material;

	//print $sum;
	//print "UPDATE {$sqlname}dogovor SET kol = '$sum', marga = '$marga' WHERE did = '$did'";

	$db -> query( "UPDATE {$sqlname}dogovor SET kol = '$sum', marga = '$marga' WHERE did = '$did'" );

	return "good";

}

/**
 * Добавляет расход по поставщикам, партнерам в бюджет
 *
 * @param $did
 * @param $summa
 *
 * @return string
 * @category Core
 * @package  Func
 */
function addProviderRashod($did, $summa): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$res       = $db -> getRow( "SELECT calculate, kol FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
	$calculate = $res["calculate"];
	$kol       = $res["kol"];

	$isper = (isServices( (int)$did )) ? 'yes' : 'no';

	if ( $calculate == 'yes' ) {

		if ( $isper != 'yes' ) {

			$sum = $sum_in = 0;

			//найдем сумму по спецификации, если она включена
			$result1 = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity' ORDER BY spid" );
			while ($data = $db -> fetch( $result1 )) {

				$nds    = 1;
				$sum    += $data['kol'] * $data['price'] / $nds * $data['dop'];
				$sum_in += $data['price_in'] / $nds * $data['kol'] * $data['dop'];

			}
			$marga = $sum - $sum_in;

			//print
			$summa = (float)$db -> getOne( "SELECT SUM(summa) as suma FROM {$sqlname}dogprovider WHERE did = '$did' AND recal = '0' and identity = '$identity'" );
			//$summa = $suma;

		}
		else {

			//по спеке найдем сумму платежа и маржу
			$res      = $db -> getRow( "SELECT spid, SUM(price * kol) AS sum, SUM(price_in * kol) AS sum_in FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity'" );
			$summa_sp = (float)$res["sum"];
			$marga_sp = $summa_sp - (float)$res["sum_in"];

			//найдем долю маржи в спецификации
			$dolya = $marga_sp / $summa_sp;

			//найдем маржу по оплатам
			$sum   = (float)$db -> getOne( "SELECT SUM(summa_credit) AS kol FROM {$sqlname}credit WHERE did = '$did' AND identity = '$identity'" );
			$marga = $sum * $dolya;

		}

	}
	else {

		//или сумму, указанную в сделке
		$res   = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
		$sum   = $res["kol"];
		$marga = $res["marga"];

	}

	//если маржа не указана
	if ( $sum == 0 ) {
		$sum = $kol;
	}
	if ( $marga == 0 ) {
		$marga = $sum;
	}

	$marga -= $summa;

	if ( $db -> query( "UPDATE {$sqlname}dogovor set kol = '$sum', marga = '$marga' WHERE did = '$did'" ) ) {

		return "good";

	}

	return "error";

}

/**
 * Расчет затрат на партнеров и поставщиков по сделке
 *
 * @param $did
 *
 * @return float
 * @category Core
 * @package  Func
 */
function getProviderSum($did): float {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	//найдем сумму поставщиков и партнеров
	return (float)$db -> getOne( "SELECT SUM(summa) FROM {$sqlname}dogprovider WHERE did = '$did' AND recal = '0' AND identity = '$identity'" );

}

/**
 * Расчет себестоимости по сделке
 *
 * @param $did
 *
 * @return float|int
 * @category Core
 * @package  Func
 */
function getSpecaSum($did) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$sum = $sum_in = 0;

	//найдем сумму по спецификации, если она включена
	$result = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' AND tip!=2 AND identity = '$identity' ORDER BY spid" );
	while ($data = $db -> fetch( $result )) {

		$nds = 1;
		//$sum    += $data['kol'] * $data['price'] / $nds * $data['dop'];
		$sum_in += $data['price_in'] / $nds * $data['kol'] * $data['dop'];

	}

	return $sum_in;
}

/**
 * Расчет НДС по сделке
 *
 * @param $id
 *
 * @return string
 * @category Core
 * @package  Func
 */
function get_nds($id): string {

	$identity   = $GLOBALS['identity'];
	$sqlname    = $GLOBALS['sqlname'];
	$db         = $GLOBALS['db'];
	$ndsRaschet = $GLOBALS['ndsRaschet'];

	$mcid = (int)getDogData( $id, "mcid" );

	$nalogScheme = getNalogScheme( 0, $mcid );

	$nds = 0;
	$res = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did='".$id."' AND identity = '".$identity."'" );
	while ($data = $db -> fetch( $res )) {

		$summa = pre_format( $data['kol'] ) * pre_format( $data['price'] );

		if ( $nalogScheme['nalog'] == 0 ) {
			$data['nds'] = 0;
		}

		$ndsa = getNalog( $summa, $data['nds'], $ndsRaschet );
		$nds  += $ndsa['nalog'];

	}

	return num_format( $nds );

}

/**
 * Возвращает спецификацию по сделке в массиве
 *
 * @param     $did
 * @param int $rs
 *
 * @return array|null
 * @see      Speka::getSpekaData()
 *
 * @deprecated
 * @deprecated
 * @category Core
 * @package  Func
 */
function getSpekaData($did, int $rs = 0): ?array {

	return NULL;

}

/**
 * Возвращает массив выставленных счетов по сделке
 *
 * @param $did
 *
 * @return array|null
 * @deprecated
 * @see      Invoice::getCreditData()
 *
 * @category Core
 * @package  Func
 */
function getCreditData($did): ?array {

	return NULL;

}

/**
 * Расчет налога если он в цене или добавляется сверху
 *
 * @param             $summa
 * @param float|null  $nalog
 * @param string|null $type
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getNalog($summa, float $nalog = NULL, string $type = NULL): array {

	$price = $summa;

	if ( empty( $type ) ) {
		$type = 'no';
	}

	if ( $nalog === NULL ){
		$nalog = 0.00;
	}

	//если цена с НДС и надо выделить НДС (налог в цене)
	if ( $type == 'no' ) {

		$nds   = $summa * (1 - 1 / (1 + $nalog / 100));
		$price = $summa - $nds;

	}
	//если цена без НДС и надо выделить НДС (налог сверху)
	else {

		$nds   = $summa * ($nalog / 100);
		$price = $summa + $nds;

	}

	return [
		"summa" => $price,
		"nalog" => $nds
	];

}

/**
 * Возвращает информацию по налоговой схеме по расчетному счету или счету по умолчанию у компании
 *
 * @param     $rs
 * @param int $mcid
 *
 * @return array
 * @category Core
 * @package  Func
 */
function getNalogScheme($rs, int $mcid = 0): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$snalog = 0;

	if ( $mcid > 0 && $rs < 1 ) {

		$result = $db -> getRow( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '$mcid' and isDefault = 'yes' and identity = '$identity'" );
		$rs     = $result["id"];
		$snalog = $result["ndsDefault"];

	}
	elseif ( $rs > 0 ) {

		$result = $db -> getRow( "SELECT * FROM {$sqlname}mycomps_recv WHERE id = '$rs' and identity = '$identity'" );
		$mcid   = $result["cid"];
		$snalog = $result["ndsDefault"];

	}

	return [
		"mcid"  => $mcid,
		"rs"    => $rs,
		"nalog" => $snalog
	];

}

/**
 * функция возвращает сумму маржи с оплаченной суммы, т.к. сумма оплаты может отличаться от сумы маржи по сделке
 *
 * @param       $did
 * @param array $param
 *
 * @return int|string
 * @category Core
 * @package  Func
 */
function getMargaPayed($did, array $param = []) {

	$rootpath = dirname( __DIR__, 2 );

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$other    = $GLOBALS['other'];
	$fpath    = $GLOBALS['fpath'];

	$otherSettings = json_decode( file_get_contents( $rootpath."/cash/".$fpath."otherSettings.json" ), true );

	$margaD = 0;

	$result     = $db -> getRow( "SELECT kol, marga FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
	$kol_full   = pre_format( $result['kol'] );
	$marga_full = pre_format( $result['marga'] );

	if ( !$otherSettings['credit'] ) {
		$margaD = $marga_full;

	}
	//если включена рассрочка, то считаем по оплатам
	if ( $otherSettings['credit'] ) {

		if ( $param['do'] == 'on' ) {
			$ss = " and do = 'on'";
			$dd = 'invoice_date';
		}
		else {
			$ss = " and do != 'on'";
			$dd = "datum_credit";
		}

		$marga = 0;

		$result = $db -> query( "SELECT summa_credit FROM {$sqlname}credit WHERE date_format(".$dd.", '%Y-%m') = '".$param['year']."-".$param['mon']."' $ss AND did = '$did' AND identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			//расчет процента размера оплат в указанном месяце от суммы сделки
			$dolya = ($kol_full > 0) ? $data['summa_credit'] / $kol_full : 0;

			$marga += $marga_full * $dolya;

		}

		$margaD = $marga;

	}

	return $margaD;

}

/**
 * Добавляет запись в историю активности
 *
 * @param array $params
 *
 * @return int
 * @package  Func
 * @category Core
 */
function addHistorty(array $params = []): int {

	global $opts;

	$sqlname            = $GLOBALS['sqlname'];
	$db                 = $GLOBALS['db'];
	$params['identity'] = $params['identity'] ?? $GLOBALS['identity'];
	$params['iduser']   = ($params['iduser'] < 0) ? $GLOBALS['iduser1'] : $params['iduser'];

	unset( $db );
	$db = new SafeMySQL( $opts );

	$timezone = $db -> getOne( "SELECT timezone FROM {$sqlname}settings WHERE id = '$params[identity]'" );
	date_default_timezone_set( $timezone );

	$tz         = new DateTimeZone( $timezone );
	$dz         = new DateTime();
	$dzz        = $tz -> getOffset( $dz );
	$bdtimezone = $dzz / 3600;
	$db -> query( "SET time_zone = '+".$bdtimezone.":00'" );

	$hid = 0;

	if ( !empty( $params ) ) {

		if ( isset( $params['content'] ) ) {

			$params['des'] = $params['content'];
			unset( $params['content'] );

		}

		$params['des']   = ($params['untag'] == "no") ? str_replace( "\\r\\n", "\r\n", $params['des'] ) : untag( str_replace( "\\r\\n", "\r\n", $params['des'] ) );
		$params['datum'] = (empty( $params['datum'] ) || $params['datum'] == '') ? current_datumtime() : $params['datum'];

		unset( $params['untag'] );

		$db -> query( "INSERT INTO {$sqlname}history SET ?u", arrayNullClean( $params ) );
		$hid = $db -> insertId();

		//добавим запись о дате активности по клиенту
		if ( (int)$params['clid'] > 0 ) {
			$db -> query( "UPDATE {$sqlname}clientcat SET last_hist = '".current_datumtime()."' WHERE clid = '$params[clid]'" );
		}

	}

	return $hid;

}

/**
 * Редактирование записи активности
 *
 * @param       $id
 * @param array $params
 *
 * @return mixed
 * @throws Exception
 * @package  Func
 * @category Core
 */
function editHistorty($id, array $params = []) {

	$sqlname            = $GLOBALS['sqlname'];
	$db                 = $GLOBALS['db'];
	$params['identity'] = $params['identity'] ?? $GLOBALS['identity'];
	$params['iduser']   = ($params['iduser'] < 0) ? $GLOBALS['iduser1'] : $params['iduser'];

	$timezone = $db -> getOne( "SELECT timezone FROM {$sqlname}settings WHERE id = '$params[identity]'" );
	date_default_timezone_set( $timezone );

	$tz         = new DateTimeZone( $timezone );
	$dz         = new DateTime();
	$dzz        = $tz -> getOffset( $dz );
	$bdtimezone = $dzz / 3600;
	$db -> query( "SET time_zone = '+".$bdtimezone.":00'" );

	if ( !empty( $params ) ) {

		if ( isset( $params['content'] ) ) {

			$params['des'] = $params['content'];
			unset( $params['des'] );

		}

		$params['des']   = ($params['untag'] == "no") ? str_replace( "\\r\\n", "\r\n", $params['des'] ) : untag( str_replace( "\\r\\n", "\r\n", $params['des'] ) );
		$params['datum'] = (empty( $params['datum'] )) ? current_datumtime() : $params['datum'];

		unset( $params['untag'] );

		$db -> query( "UPDATE {$sqlname}history SET ?u WHERE cid = '$id'", arrayNullClean( $params ) );

	}

	return $id;

}

/**
 * Возвращает указанный параметр по клиенту
 *
 * @param $id
 * @param $tip
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getClientData($id, $tip): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	return (string)$db -> getOne( "select $tip from {$sqlname}clientcat where clid='".$id."' and identity = '".$identity."'" );

}

/**
 * Возвращает указанный параметр по контакту
 *
 * @param $id
 * @param $tip
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getPersonData($id, $tip): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	//$opts     = $GLOBALS[ 'opts' ];

	if ( $tip == 'title' ) {
		$tip = 'person';
	}

	//unset($db);
	//$db = new SafeMySQL($opts);

	return (string)$db -> getOne( "SELECT $tip FROM {$sqlname}personcat WHERE pid = '$id' AND identity = '$identity'" );

}

/**
 * Возвращает указанный параметр по сделке
 *
 * @param $id
 * @param $tip
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getDogData($id, $tip): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	return (string)$db -> getOne( "SELECT $tip FROM {$sqlname}dogovor WHERE did = '$id' and identity = '$identity'" );

}

/**
 * Возвращает id канала для трекинга источника клиента
 * Если канал не найден в БД, то создает его
 *
 * @param        $path
 * @param string|NULL $source
 * @param string|NULL $destination
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getClientpath($path, string $source = NULL, string $destination = NULL): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( !empty($source) ) {
		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}clientpath WHERE utm_source = '$source' AND identity = '$identity'" );
	}
	elseif ( !empty($destination) ) {
		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}clientpath WHERE destination = '".preparePhone( $destination )."' AND identity = '$identity'" );
	}
	elseif ( !empty($path) ) {
		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}clientpath WHERE name = '$path' AND identity = '".$identity."'" );
	}

	if ( $id == 0 && !empty($path) ) {

		$db -> query( "INSERT INTO {$sqlname}clientpath SET ?u", [
			"name"       => $path,
			"utm_source" => $source,
			"identity"   => $identity
		] );
		$id = $db -> insertId();

	}

	return $id;

}

/**
 * id отрасли по названию
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getClientCategory($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( $name != '' ) {

		$id = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}category WHERE title = '$name' AND identity = '$identity' ORDER BY title" );

		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}category SET ?u", [
				"title"    => $name,
				"identity" => $identity
			] );
			$id = $db -> insertId();

		}

	}

	return $id;
}

/**
 * id территории по названию
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getClientTerritory($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( $name != '' ) {

		$id = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}territory_cat WHERE title = '$name' AND identity = '$identity' ORDER BY title" );

		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}territory_cat SET ?u", [
				"title"    => $name,
				"identity" => $identity
			] );
			$id = $db -> insertId();

		}

	}

	return $id;

}

/**
 * Проверка Типа отношений по названию
 * Если не найден - создаем
 *
 * @param $name
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getClientRelation($name): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	if ( $name != '' ) {

		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}relations WHERE title = '$name' AND identity = '$identity'" );
		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}relations SET ?u", [
				"title"    => $name,
				"identity" => $identity
			] );

		}

	}

	return $name;

}

/**
 * Возвращает id типа лояльности по имени
 * Если не найдено - создает
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getPersonLoyalty($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( $name != '' ) {

		$id = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}loyal_cat WHERE title = '$name' AND identity = '$identity'" );
		if ( $id == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}loyal_cat SET ?u", [
				"title"    => $name,
				"identity" => $identity
			] );
			$id = $db -> insertId();

		}

	}

	return $id;

}

/**
 * Если тип активности не наден по имени, то создает её
 *
 * @param $name
 *
 * @return string
 * @category Core
 * @package  Func
 */
function getTipTask($name): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}activities WHERE title = '".$name."' AND identity = '".$identity."'" );
	if ( $id == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}activities (id,title,identity) VALUES(NULL, '".$name."','".$identity."')" );

	}

	return $name;

}

/**
 * Возвращает id типа активности по имени
 * Если не найдено, то создает новую и возвращает id
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getTipHistory($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}activities WHERE title = '".$name."' AND identity = '".$identity."'" );
	if ( $id == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}activities (id,title,identity) VALUES(NULL, '".$name."','".$identity."')" );
		$id = $db -> insertId();

	}

	return $id;

}

/**
 * Возвращает id типа сделки по имени
 * Если не надено, то создает новый тип
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getDogTip($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = (int)$db -> getOne( "SELECT tid FROM {$sqlname}dogtips WHERE title = '".$name."' AND identity = '".$identity."'" );
	if ( $id == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}dogtips (tid,title,identity) VALUES(NULL, '".$name."','".$identity."')" );
		$id = $db -> insertId();

	}

	return $id;

}

/**
 * Возвращает id этапа сделки по значению
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getStep($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$idcat = 0;

	if ( $name != '' ) {

		//print "SELECT idcategory FROM {$sqlname}dogcategory WHERE title='".$name."' AND identity = '".$identity."'";

		$idcat = (int)$db -> getOne( "SELECT idcategory FROM {$sqlname}dogcategory WHERE title='".$name."' AND identity = '".$identity."'" );

	}

	return $idcat;

}

/**
 * Возвращает id направления по названию
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getDirection($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( $name != '' ) {

		$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}direction WHERE title='".$name."' AND identity = '".$identity."'" );

	}

	return $id;

}

/**
 * Возвращает id статуса закрытия сделки
 *
 * @param $name
 *
 * @return int
 * @category Core
 * @package  Func
 */
function getStatusClose($name): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$id = 0;

	if ( $name != '' ) {

		$id = (int)$db -> getOne( "SELECT sid FROM {$sqlname}dogstatus WHERE title='".$name."' AND identity = '$identity'" );
	}

	return $id;

}

/**
 * Подготавливает ссылку по типу: phone, email
 *
 * @param $t
 * @param $s
 *
 * @return string
 * @category Core
 * @package  Func
 */
function prepareLinkByTip($t, $s): string {

	$str = [];
	$r   = '';

	if ( $t != '' && $s != '' ) {

		switch ($t) {
			case 'phone':

				$s = explode( ",", str_replace( ";", ",", str_replace( "", "", $s ) ) );

				foreach ( $s as $ss ) {
					if ( $ss != '' ) {
						$str[] = '<a href="tel:'.$ss.'" data-rel="external" target="blank">'.$ss.'</a>';
					}
				}

			break;
			case 'email':

				$s = explode( ",", str_replace( ";", ",", str_replace( " ", "", $s ) ) );

				foreach ( $s as $ss ) {
					if ( $ss != '' ) {
						$str[] = '<a href="mailto:'.$ss.'" data-rel="external" target="blank">'.$ss.'</a>';
					}
				}

			break;
		}

		$r = yimplode( ", ", $str );

	}

	return $r;

}

/**
 * инструменты для системы событий
 *
 * @param $pid
 *
 * @return array
 * @category Core
 * @package  Func
 */
function personinfo($pid): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$result = $db -> getRow( "SELECT * FROM {$sqlname}personcat WHERE pid='".$pid."' AND identity = '".$identity."'" );
	$ptitle = $result["ptitle"];
	$person = $result["person"];
	$tel    = $result["tel"];
	$mob    = $result["mob"];
	$mail   = $result["mail"];

	return [
		"pid"   => (int)$pid,
		"title" => $person,
		"post"  => $ptitle,
		"phone" => $tel,
		"mob"   => $mob,
		"email" => $mail
	];

}

/**
 * Поля для сделок
 *
 * @return array
 * @category Core
 * @package  Func
 */
function dealFields(): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$ifields[] = 'did';
	$ifields[] = 'uid';
	$ifields[] = 'datum';
	$ifields[] = 'datum_izm';
	$ifields[] = 'clid';
	$ifields[] = 'title';
	$resf      = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='dogovor' and fld_on='yes' and fld_name NOT IN ('kol_fact','money','pid_list','oborot','period','des') and identity = '$identity'" );
	while ($do = $db -> fetch( $resf )) {
		if ( $do['fld_name'] == 'idcategory' ) {
			$ifields[] = 'step';
		}
		if ( $do['fld_name'] == 'marg' ) {
			$ifields[] = 'marga';
		}
		else {
			$ifields[] = $do['fld_name'];
		}
	}
	$ifields[] = 'datum_start';
	$ifields[] = 'datum_end';
	$ifields[] = 'close';
	$ifields[] = 'kol';
	$ifields[] = 'marga';
	$ifields[] = 'datum_close';
	$ifields[] = 'status_close';
	$ifields[] = 'des_fact';
	$ifields[] = 'kol_fact';
	$ifields[] = 'category';

	return $ifields;
}

/**
 * Поля для клиентов
 *
 * @return array
 * @category Core
 * @package  Func
 */
function clientFields(): array {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$ifields[] = 'clid';
	$ifields[] = 'uid';
	$ifields[] = 'type';
	$ifields[] = 'date_create';
	$ifields[] = 'date_edit';
	$ifields[] = 'priceLevel';
	$resf      = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='client' and fld_on='yes' and fld_name != 'recv' and identity = '$identity'" );
	while ($do = $db -> fetch( $resf )) {
		$ifields[] = $do['fld_name'];
	}

	return $ifields;
}

/**
 * Сопоставление номера телефона клиента, номера линии или clid с источником клиента
 * Возвращает id источника
 *
 * @param          $phone
 * @param string   $dest
 * @param int      $clid
 * @param bool     $update (true - обновляет источник клиента в базе)
 *
 * @return int
 * @category Core
 * @package  Func
 */
function callTrack($phone, string $dest = NULL, int $clid = NULL, bool $update = false): int {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$path       = [];
	$clientpath = $currentPath = 0;

	$phone = preparePhone( $phone );
	$dest  = preparePhone( $dest );

	//составим список источников и связанных номеров
	$res = $db -> getAll( "SELECT id, destination FROM {$sqlname}clientpath WHERE destination != '' AND identity = '$identity'" );
	foreach ( $res as $item ) {

		$path[ $item['id'] ] = preparePhone( $item['destination'] );

	}

	//получим наличие источника в заявках
	if ( $clid == 0 ) {

		$lead = $db -> getRow( "SELECT clientpath, datum FROM {$sqlname}leads WHERE clientpath > 0 AND replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$phone%' AND identity = '$identity' ORDER BY datum DESC LIMIT 1" );

	}
	else {

		$lead = $db -> getRow( "SELECT clientpath, datum FROM {$sqlname}leads WHERE clientpath > 0 and clid = '$clid' and identity = '$identity' ORDER BY datum DESC LIMIT 1" );

	}

	//найдем DID из истории звонков
	if ( $clid == 0 ) {

		$res = $db -> getRow( "SELECT did, datum FROM {$sqlname}callhistory WHERE src LIKE '%$phone%' AND direct = 'income' AND identity = '$identity'" );

	}
	else {

		$res = $db -> getRow( "SELECT did, datum FROM {$sqlname}callhistory WHERE clid = '$clid' AND direct = 'income' AND identity = '$identity'" );

		$currentPath = getClientData( $clid, 'clientpath' );

	}

	if ( $dest != '' ) {
		$clientpath = strtr( $dest, $path );
	}
	elseif ( $lead['datum'] != '' && $lead['datum'] != NULL && $res['datum'] < $lead['datum'] && (int)$res['did'] > 0 ) {
		$clientpath = strtr( $res['did'], $path );
	}
	elseif ( (int)$lead['clientpath'] > 0 ) {
		$clientpath = $lead['clientpath'];
	}

	if ( $update && $clid > 0 && $clientpath > 0 && $currentPath < 1 ) {
		$db -> query( "UPDATE {$sqlname}clientcat SET clientpath = '".(int)$clientpath."' WHERE clid = '$clid' and identity = '$identity'" );
	}

	return (int)$clientpath;

}

/**
 * Вывод категорий прайса любой степени вложенности
 *
 * @param int   $id    - id категории или 0, для вывода всех
 * @param int   $level - уровень вывода
 * @param array $ures  - глобальный массив, не заполняется
 *
 * @return array
 * @deprecated
 * @see      Price::getPriceCatalog()
 * @category Core
 * @package  Func
 */
function getPriceCatalog(int $id = 0, int $level = 0, array $ures = []): array {

	return [];
}

/**
 * Добавление записи в группу почтового сервиса
 *
 * @param     $tip
 * @param     $id
 * @param int $gid
 *
 * @return array
 * @throws \Exception
 * @deprecated Функционал удален
 * @category   Core
 * @package    Func
 */
function addToService($tip, $id, int $gid = 0): array {

	return [];

}

/**
 * Возвращает SQL запрос для формирования списков Клиентов, Контактов и Сделок
 *
 * @param       $tip        : client, person or dogovor
 * @param array $params
 *                          - array **fields** - перечень полей, которые будут добавлены в запрос
 *                          - bool **excludeDostup** - исключение записей, к которым есть доступ
 *                          - string **selectplus** - дополнительные параметры для блока SELECT, разделенные запятой
 *                          - bool **namereplace** - true | false - включить переименование полей, правила передаются в
 *                          параметре freplace
 *                          - array **freplace** - массив для переименования полей. например "title" => "name" будет
 *                          "title as name"
 * @param bool  $countQuery : выводить запрос на расчет количества, если false, то возвращает строку запроса
 *
 * @return array|string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function getFilterQuery($tip, array $params = [], bool $countQuery = true) {

	$identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
	$iduser1  = ($params['iduser1'] > 0) ? $params['iduser1'] : $GLOBALS['iduser1'];
	$tipuser  = ($params['tipuser'] > 0) ? $params['tipuser'] : $GLOBALS['tipuser'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];

	$query  = $queryCount = $queryAllCounts = '';
	$inputs = '';
	$sort_4 = '';

	$userSet = $db -> getOne( "SELECT usersettings FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'" );
	$userSet = json_decode( $userSet, true );

	//print_r($userSet);

	//доп.поля, которые надо выводить
	$fields = $params['fields'];

	$isDate = (array)$db -> getCol( "SELECT fld_name FROM {$sqlname}field WHERE fld_tip = '$tip' and fld_name LIKE '%input%' and fld_on='yes' and fld_temp = 'datum' and identity = '$identity' ORDER BY fld_title" );
	array_push( $isDate, "date_create", "datum_plan", "datum_close", "last_dog" );

	$fieldTypes = $db -> getIndCol( "fld_name", "SELECT fld_name, fld_temp FROM {$sqlname}field WHERE fld_tip = '$tip' and fld_on='yes' and identity = '$identity' ORDER BY fld_title" );

	switch ($tip) {

		case 'person':

			if ( isset( $params['fields'] ) && !empty( $fields ) ) {

				foreach ( $fields as $field ) {

					$inputs .= $sqlname."personcat.".$field." as ".$field.",";

				}

			}

			$iduser = $params['iduser'];
			$word   = str_replace( " ", "", trim( $params['word'] ) );
			//$dword    = untag($params['word']);
			$alf      = $params['alf'];
			$tbl_list = $params['tbl_list'];
			$clid     = (int)$params['clid'];

			$filter = ($params['filter'] != '') ? $params['filter'] : 'my';

			$clientpath = $params['clientpath'];
			$loyalty    = $params['loyalty'];

			$haveEmail    = $params['haveEmail'];
			$havePhone    = $params['havePhone'];
			$haveMobPhone = $params['haveMobPhone'];
			$haveTask     = $params['haveTask'];

			$selectplus = (isset( $params['selectplus'] )) ? ", ".$params['selectplus'] : "";
			$filterplus = $params['filterplus'] ?? "";

			if ( $tbl_list == 'tel' || $tbl_list == 'fax' ) {
				$word = str_replace( [
					"(",
					"+",
					")",
					"-",
					" "
				], "", $word );
			}

			$ar = [];

			$query_f = explode( ':', (string)$filter );
			$count   = count( $query_f );

			//print_r($query_f);

			//стандартные представления
			if ( $count < 2 ) {

				if ( $filter == 'my' ) {
					$sort_4 .= "and {$sqlname}personcat.iduser = '$iduser1'";
				}
				elseif ( $filter == 'otdel' ) {
					$sort_4 .= "and {$sqlname}personcat.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).")";
				}
				elseif ( $filter == 'all' && $userSet['filterAllBy'] > 0 ) {
					$sort_4 .= "and {$sqlname}personcat.iduser IN (".implode( ",", get_people( $userSet['filterAllBy'], "yes" ) ).")";
				}

				if ( $iduser > 0 ) {
					$sort_4 .= " and {$sqlname}personcat.iduser = '$iduser'";
				}

			}

			//поиск по поисковым представлениям
			if ( $count > 1 && $query_f[0] == 'search' ) {

				$squery = $db -> getOne( "SELECT squery FROM {$sqlname}search WHERE seid = '".$query_f['1']."' and identity = '$identity'" );
				$squery = explode( ';', $squery );

				sort( $squery );

				/**
				 * Группируем запрос по полям и условию. Новая реализация
				 */
				$xq = [];
				foreach ( $squery as $x ) {

					$sq                       = explode( ':', $x );
					$xq[ $sq[0] ][ $sq[1] ][] = $sq[2];

				}

				$xsub = [];
				foreach ( $xq as $field => $xquery ) {

					foreach ( $xquery as $term => $values ) {

						switch ($term) {

							case "=":

								if ( in_array( $field, $isDate ) ) {

									if ( !in_array( $values[0], [
										'{today}',
										'{week}',
										'{prevweek}',
										'{nextweek}',
										'{month}',
										'{prevmonth}',
										'{nextmonth}'
									] ) ) {

										$xsub[] = " (DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m-%d') $term '$values[0]')";

									}
									elseif ( $values[0] == '{today}' ) {

										$xsub[] = " DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m-%d') $term DATE( NOW() )";

									}
									else {

										$period = getPeriod( 'month' );

										if ( $values[0] == '{week}' ) {
											$period = getPeriod( 'calendarweek' );
										}
										elseif ( $values[0] == '{prevweek}' ) {
											$period = getPeriod( 'calendarweekprev' );
										}
										elseif ( $values[0] == '{nextweek}' ) {
											$period = getPeriod( 'calendarweeknext' );
										}
										/*elseif ( $values[0] == '{prevmonth}' ) {
											$period = getPeriod( 'prevmonth' );
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$period = getPeriod( 'nextmonth' );
										}*/

										//$xsub[] = "({$sqlname}personcat.$field BETWEEN '$period[0]' and '$period[1]')";

										if ( $values[0] == '{month}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m') $term DATE_FORMAT(NOW(), '%Y-%m')";
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')";
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m')";
										}
										else {
											$xsub[] = "({$sqlname}personcat.$field BETWEEN '$period[0]' and '$period[1]')";
										}

									}

								}
								else {

									$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}personcat.$field = '$values[0]'" : "{$sqlname}personcat.$field IN (".yimplode( ",", $values ).")";

								}

							break;
							case "!=":

								$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}personcat.$field != '$values[0]'" : "{$sqlname}personcat.$field NOT IN (".yimplode( ",", $values ).")";

							break;
							case ">":
							case "<":
							case "<=":

								if ( in_array( $field, $isDate ) ) {

									if ( !in_array( $values[0], [
										'{today}',
										'{week}',
										'{prevweek}',
										'{nextweek}',
										'{month}',
										'{prevmonth}',
										'{nextmonth}'
									] ) ) {

										$xsub[] = " (DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m-%d') $term '$values[0]')";

									}
									elseif ( $values[0] == '{today}' ) {

										$xsub[] = " DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m-%d') $term DATE( NOW() )";

									}
									else {

										$period = getPeriod( 'month' );

										if ( $values[0] == '{week}' ) {
											$period = getPeriod( 'calendarweek' );
										}
										elseif ( $values[0] == '{prevweek}' ) {
											$period = getPeriod( 'calendarweekprev' );
										}
										elseif ( $values[0] == '{nextweek}' ) {
											$period = getPeriod( 'calendarweeknext' );
										}
										/*elseif ( $values[0] == '{prevmonth}' ) {
											$period = getPeriod( 'prevmonth' );
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$period = getPeriod( 'nextmonth' );
										}*/

										//$p      = $term == '<' ? $period[0] : $period[1];
										//$xsub[] = "DATE({$sqlname}personcat.$field) $term '$p'";

										if ( $values[0] == '{month}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m') $term DATE_FORMAT(NOW(), '%Y-%m')";
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')";
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}personcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m')";
										}
										else {
											$p      = $term == '<' || $term == '<=' ? $period[0] : $period[1];
											$xsub[] = "DATE({$sqlname}personcat.$field) $term '$p'";
										}

									}

								}
								else {

									$xsub[] = "{$sqlname}personcat.$field $term '$values[0]'";

								}

							break;
							case "LIKE":
							case "NOT LIKE":

								if ( in_array( $field, [
									'loyalty',
									'clientpath',
									'iduser'
								] ) ) {

									$xsub[] = $term == 'LIKE' ? "{$sqlname}personcat.$field IN (".yimplode( ",", $values ).")" : "{$sqlname}dogovor.$field NOT IN (".yimplode( ",", $values ).")";

								}
								elseif ( in_array( $field, [
										'person',
										'ptitle',
										'rol'
									] ) || ($fieldTypes[ $field ] == "--Обычное--" || $fieldTypes[ $field ] == "") ) {

									if ( $term == 'LIKE' ) {

										$xy = [];
										foreach ( $values as $value ) {
											$xy[] = "{$sqlname}personcat.$field $term '%".texttosmall( $value )."%'";
										}

										if ( !empty( $xy ) ) {
											$xsub[] = "( ".yimplode( " OR ", $xy )." )";
										}

									}
									else {

										foreach ( $values as $value ) {
											$xsub[] = "{$sqlname}personcat.$field $term '%".texttosmall( $value )."%'";
										}

									}

								}
								else {

									foreach ( $values as $value ) {

										if ( in_array( $field, [
											'phone',
											'tel',
											'mob',
											'fax'
										] ) ) {

											$xsub[] = "replace(replace(replace(replace(replace('%{$sqlname}personcat.$field%', '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') $term '$value'";

										}
										else {
											$xsub[] = "{$sqlname}personcat.$field $term '%$value%'";
										}

									}

								}

							break;

						}

					}

				}

				if ( !empty( $xsub ) ) {
					$sort_4 .= " AND ".yimplode( " AND ", $xsub );
				}

			}
			//поиск по группам
			elseif ( $count > 1 && $query_f[0] == 'group' ) {

				//$garray = $db -> getCol("select pid from {$sqlname}grouplist where pid > 0 and gid = '".$query_f['1']."' and identity = '$identity'");
				//$glist  = implode(",", $garray);

				//if (count($garray) > 0) $sort_4 .= " and {$sqlname}personcat.pid IN (".$glist.")";

				if ( $query_f['1'] > 0 ) {
					$sort_4 .= " and (SELECT COUNT(id) FROM {$sqlname}grouplist WHERE {$sqlname}grouplist.pid = {$sqlname}personcat.pid and {$sqlname}grouplist.gid = '".$query_f['1']."' and {$sqlname}grouplist.identity = '$identity') > 0";
				}


			}

			if ( $haveEmail == 'yes' ) {
				$sort_4 .= " and {$sqlname}personcat.mail != ''";
			}
			if ( $havePhone == 'yes' ) {
				$sort_4 .= " and {$sqlname}personcat.tel != ''";
			}
			if ( $haveMobPhone == 'yes' ) {
				$sort_4 .= " and {$sqlname}personcat.mob != ''";
			}

			if ( $haveTask == 'yes' ) {
				$sort_4 .= " and (SELECT COUNT({$sqlname}tasks.tid) FROM {$sqlname}tasks WHERE FIND_IN_SET({$sqlname}personcat.pid, REPLACE({$sqlname}tasks.pid, ';',',')) > 0 AND {$sqlname}tasks.active = 'yes') = 0";
			}

			if ( $clid > 0 ) {
				$sort_4 .= " and {$sqlname}personcat.clid = '$clid'";
			}


			if ( $alf == "09" ) {
				$sort_4 .= " and {$sqlname}personcat.person REGEXP '^[0-9]'";
			}
			elseif ( $alf == "AZ" ) {
				$sort_4 .= " and {$sqlname}personcat.person REGEXP '^[a-zA-Z]'";
			}
			elseif ( $alf != "" ) {
				$sort_4 .= " and {$sqlname}personcat.person LIKE '$alf%'";
			}

			$arrr = [
				'tel',
				'mob',
				'fax'
			];

			if ( $word != "" && $tbl_list == 'person' ) {

				$regexp = $so = [];
				$words  = yexplode( " ", (string)$params['word'] );

				if ( count( $words ) > 1 ) {

					asort( $words );

					foreach ( $words as $word ) {

						if ( $word != ' ' ) {
							$regexp[] = '('.$word.')+';
						}

					}

					$so[] = " LOWER({$sqlname}personcat.person) REGEXP '".implode( "(.*)?", $regexp )."'";

					//$regexp = array();

					//if ( count( $words ) > 1 ) {

					rsort( $words );

					foreach ( $words as $word ) {

						if ( $word != ' ' ) {
							$regexp[] = '('.$word.')+';
						}

					}

					//}

					$so[] = " LOWER({$sqlname}personcat.person) REGEXP '".implode( "(.*)?", $regexp )."'";

					$sort_4 .= " and (".implode( " or ", $so ).")";

				}
				else {
					$sort_4 .= " and {$sqlname}personcat.".$tbl_list." LIKE '%$word%'";
				}

			}
			elseif ( $word != "" && in_array( $tbl_list, $arrr ) ) {
				$sort_4 .= " and replace(replace(replace(replace(replace({$sqlname}personcat.".$tbl_list.", '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$word."%'";
			}
			elseif ( $word != "" ) {
				$sort_4 .= " and ({$sqlname}personcat.".$tbl_list." LIKE '%$word%')";
			}

			if ( $clientpath != '' ) {
				$sort_4 .= " and {$sqlname}personcat.clientpath = '$clientpath'";
			}
			if ( $clientpath == '0' ) {
				$sort_4 .= " and {$sqlname}personcat.clientpath = ''";
			}
			if ( $loyalty != '' ) {
				$sort_4 .= " and {$sqlname}personcat.loyalty = '$loyalty'";
			}
			if ( $loyalty == '0' ) {
				$sort_4 .= " and {$sqlname}personcat.loyalty < 1";
			}

			//print
			$query = "
				SELECT
					DISTINCT
					{$sqlname}personcat.pid as pid,
					$inputs
					{$sqlname}clientcat.title as client,
					{$sqlname}user.title as user,
					{$sqlname}loyal_cat.color as color,
					{$sqlname}loyal_cat.title as relation,
					{$sqlname}clientpath.name as clientpath
					$selectplus
				FROM {$sqlname}personcat
					LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}personcat.clid
					LEFT JOIN {$sqlname}loyal_cat ON {$sqlname}loyal_cat.idcategory = {$sqlname}personcat.loyalty
					LEFT JOIN {$sqlname}clientpath ON {$sqlname}personcat.clientpath = {$sqlname}clientpath.id
				WHERE 
					{$sqlname}personcat.pid > 0 
					$sort_4 $filterplus and 
					{$sqlname}personcat.identity = '$identity' 
				-- GROUP BY {$sqlname}personcat.pid
			";

			if ( $countQuery ) {

				$queryCount = "
					SELECT
						COUNT({$sqlname}personcat.pid)
					FROM {$sqlname}personcat
						LEFT JOIN {$sqlname}user ON {$sqlname}personcat.iduser = {$sqlname}user.iduser
						LEFT JOIN {$sqlname}clientcat ON {$sqlname}clientcat.clid = {$sqlname}personcat.clid
						LEFT JOIN {$sqlname}loyal_cat ON {$sqlname}loyal_cat.idcategory = {$sqlname}personcat.loyalty
						LEFT JOIN {$sqlname}clientpath ON {$sqlname}personcat.clientpath = {$sqlname}clientpath.id
					WHERE 
						{$sqlname}personcat.pid > 0 
						$sort_4 $filterplus and 
						{$sqlname}personcat.identity = '$identity' 
				";

			}

		break;
		case 'client':

			if ( isset( $params['fields'] ) && !empty( $fields ) ) {

				foreach ( $fields as $field ) {

					$inputs .= $sqlname."clientcat.".$field." as ".$field.",";

				}

			}

			$iduser      = $params['iduser'];
			$word        = str_replace( " ", "", untag( $params['word'] ) );
			$alf         = $params['alf'];
			$tbl_list    = $params['tbl_list'];
			$filter      = ($params['filter'] == '') ? 'my' : $params['filter'];
			$groups      = $params['groups'];
			$haveEmail   = $params['haveEmail'];
			$havePhone   = $params['havePhone'];
			$haveTask    = $params['haveTask'];
			$otherParam  = $params['otherParam'];
			$haveHistory = $params['haveHistory'];
			$idcategory  = is_array( $params['idcategory'] ) ? yimplode( ",", $params['idcategory'] ) : $params['idcategory'];
			$territory   = is_array( $params['territory'] ) ? yimplode( ",", $params['territory'] ) : $params['territory'];
			$haveDostup  = (int)$params['dostup'];

			$tip_cmra = (array)$params['tip_cmr']; //print_r($tip_cmra);
			$tip_cmrt = [];

			if ( !empty( $tip_cmra ) ) {
				$tip_cmrt = array_map(

					static function($string) {

						if ( $string == '0' ) {
							return NULL;
						}

						return "'".$string."'";

					}, $tip_cmra

				);
			}

			$tip_cmrts  = yimplode( ",", $tip_cmrt );
			$clientpath = yimplode( ",", $params['clientpath'] );

			$type = $params['type'];

			$dog_history    = $params['dog_history'];
			$client_history = $params['client_history'];

			$selectplus = isset( $params['selectplus'] ) ? ", ".$params['selectplus'] : "";
			$filterplus = $params['filterplus'] ?? "";

			$dos = '';

			if ( $dog_history ) {

				$sort_4 .= " and ( ( SELECT date_format( MAX(datum_close), '%Y-%m-%d') FROM {$sqlname}dogovor WHERE clid = {$sqlname}clientcat.clid and close = 'yes' and identity = '$identity' ) < '".current_datum( $dog_history )."' or {$sqlname}clientcat.last_dog = '0000-00-00')";

			}

			if ( $client_history ) {

				$sort_4 .= " and ( ( SELECT date_format( MAX(datum), '%Y-%m-%d') FROM {$sqlname}history WHERE clid = {$sqlname}clientcat.clid and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity' ) < '".current_datum( $client_history )."')";

			}

			if ( $tbl_list == 'phone' || $tbl_list == 'fax' ) {
				$word = str_replace( [
					"(",
					"+",
					")",
					"-",
					" "
				], "", $word );
			}
			else {
				$word = urldecode( $word );
			}

			$query_f = explode( ':', (string)$filter );
			$count   = count( $query_f );

			//найдем все организации, к которым у тек.сотрудника есть доступ
			if ( !$params['excludeDostup'] ) {
				$dos = " or (SELECT COUNT(clid) FROM {$sqlname}dostup WHERE clid = {$sqlname}clientcat.clid AND iduser = '$iduser1') > 0";
			}

			if ( $haveEmail == 'yes' ) {
				$sort_4 .= " and {$sqlname}clientcat.mail_url != ''";
			}
			if ( $havePhone == 'yes' ) {
				$sort_4 .= " and {$sqlname}clientcat.phone != ''";
			}

			$ht  = '';
			$htq = '';
			$htg = '';

			$closeStatusWin = $db -> getCol( "SELECT sid FROM ".$sqlname."dogstatus WHERE result_close = 'win' AND identity = '$identity' ORDER by title" );

			if ( $haveTask == 'yes' ) {

				$ht .= " LEFT JOIN {$sqlname}tasks ON {$sqlname}tasks.clid = {$sqlname}clientcat.clid";

				$htg    .= " GROUP BY {$sqlname}clientcat.clid";
				$sort_4 .= " and (SELECT COUNT({$sqlname}tasks.tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.clid = {$sqlname}clientcat.clid AND {$sqlname}tasks.active = 'yes') = 0";

			}
			if ( $otherParam == 'haveDeals' ) {

				$ht     .= " LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid";
				$sort_4 .= " and (SELECT COUNT({$sqlname}dogovor.did) FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.clid = {$sqlname}clientcat.clid and {$sqlname}dogovor.close != 'yes') > 0";

				$htg .= ($htg != '') ? " " : " GROUP BY {$sqlname}clientcat.clid";

			}
			if ( $otherParam == 'haveDealsClose' ) {

				$ht     .= " LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid";
				$sort_4 .= " and (SELECT COUNT({$sqlname}dogovor.did) FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.clid = {$sqlname}clientcat.clid and {$sqlname}dogovor.close = 'yes') > 0";

				$htg .= ($htg != '') ? " " : " GROUP BY {$sqlname}clientcat.clid";

			}
			if ( $otherParam == 'haveDealsClosePlus' ) {

				$ht .= " LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid";

				$sort_4 .= " and (SELECT COUNT({$sqlname}dogovor.did) FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.clid = {$sqlname}clientcat.clid and {$sqlname}dogovor.close = 'yes' and {$sqlname}dogovor.sid IN (".yimplode( ",", $closeStatusWin ).")) > 0";

				$htg .= ($htg != '') ? " " : " GROUP BY {$sqlname}clientcat.clid";

			}
			if ( $otherParam == 'haveNoDeals' ) {

				$ht     .= " LEFT JOIN {$sqlname}dogovor ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid";
				$sort_4 .= " and (SELECT COUNT({$sqlname}dogovor.did) FROM {$sqlname}dogovor WHERE {$sqlname}dogovor.clid = {$sqlname}clientcat.clid) = 0";

				$htg .= ($htg != '') ? " " : " GROUP BY {$sqlname}clientcat.clid";

			}
			if ( $haveHistory == 'yes' ) {

				$ht     .= " LEFT JOIN {$sqlname}history ON {$sqlname}history.clid = {$sqlname}clientcat.clid";
				$sort_4 .= " and (SELECT COUNT({$sqlname}history.cid) FROM {$sqlname}history WHERE {$sqlname}history.clid = {$sqlname}clientcat.clid and {$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM')) > 0";

				$htg .= ($htg != '') ? " " : " GROUP BY {$sqlname}clientcat.clid";


			}
			if ( $haveHistory == 'no' ) {

				$ht     .= " LEFT OUTER JOIN {$sqlname}history ON {$sqlname}history.clid = {$sqlname}clientcat.clid";
				$sort_4 .= " and (SELECT COUNT({$sqlname}history.cid) FROM {$sqlname}history WHERE {$sqlname}history.clid = {$sqlname}clientcat.clid and {$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM')) = 0";

				$htg .= ($htg != '') ? " " : " GROUP BY {$sqlname}clientcat.clid";

			}

			// поиск по группам
			if ( !empty( $groups ) && !in_array( 0, $groups ) ) {
				$sort_4 .= " AND (SELECT COUNT(clid) FROM {$sqlname}grouplist WHERE clid = {$sqlname}clientcat.clid AND gid IN (".implode( ",", $groups ).") AND identity = '$identity') > 0";
			}
			// вне групп
			elseif ( !empty( $groups ) && in_array( 0, $groups ) ) {
				$sort_4 .= " AND (SELECT COUNT(clid) FROM {$sqlname}grouplist WHERE clid = {$sqlname}clientcat.clid) = 0";
			}

			//стандартные поисковые представления
			if ( $count < 2 ) {

				if ( !in_array( $filter, [
					'partner',
					'contractor',
					'concurent',
					'other'
				] ) ) {

					if ( $filter == 'my' ) {
						$sort_4 .= " and {$sqlname}clientcat.trash != 'yes' and ({$sqlname}clientcat.iduser = '$iduser1' $dos)";
					}

					elseif ( $filter == 'otdel' ) {
						$sort_4 .= "
							and {$sqlname}clientcat.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).") 
							and {$sqlname}clientcat.trash != 'yes'
						";
					}

					//elseif ( $filter == 'fav' && $tipuser == 'Менеджер продаж' )
					//$sort_4 .= " and {$sqlname}clientcat.fav = 'yes' and ({$sqlname}clientcat.iduser = '$iduser1' $dos)";

					elseif ( $filter == 'fav' ) {

						if ( $tipuser == 'Менеджер продаж' ) {
							$sort_4 .= " and COALESCE({$sqlname}clientcat.fav, 'no') = 'yes' and ({$sqlname}clientcat.iduser = '$iduser1' $dos)";
						}

						elseif ( $tipuser != 'Менеджер продаж' ) {
							$sort_4 .= " and {$sqlname}clientcat.fav = 'yes' and {$sqlname}clientcat.iduser IN (".implode( ",", get_people( $iduser1, "yes" ) ).")";
						}

						else {
							$sort_4 .= " and {$sqlname}clientcat.fav = 'yes' and {$sqlname}clientcat.trash != 'yes'";
						}

					}

					elseif ( $filter == 'trash' ) {
						$sort_4 .= " and ({$sqlname}clientcat.trash='yes' or {$sqlname}clientcat.iduser = '0' or {$sqlname}clientcat.iduser = '')";
					}

					//при показе ВСЕХ, но с ограничением по руководителю
					elseif ( $filter == 'all' && (int)$userSet['filterAllBy'] > 0 ) {

						$sort_4 .= " and {$sqlname}clientcat.iduser IN (".implode( ",", get_people( (int)$userSet['filterAllBy'], "yes" ) ).") and COALESCE({$sqlname}clientcat.trash, 'no') != 'yes' ";

					}

					else {
						$sort_4 .= " and {$sqlname}clientcat.trash = 'no' ";
					}

					//print $tipuser."\n";
					$sort_4 .= " and {$sqlname}clientcat.type IN ('client','person')";

				}
				elseif ( in_array( $filter, [
					'partner',
					'contractor',
					'concurent'
				] ) ) {

					if ( $filter == 'partner' ) {
						$sort_4 .= " and {$sqlname}clientcat.type = 'partner'";
					}
					if ( $filter == 'contractor' ) {
						$sort_4 .= " and {$sqlname}clientcat.type = 'contractor'";
					}
					if ( $filter == 'concurent' ) {
						$sort_4 .= " and {$sqlname}clientcat.type = 'concurent'";
					}

				}
				else {

					$shares = [];
					if ( $userSet['dostup']['partner'] == 'on' ) {
						$shares[] = 'partner';
					}
					if ( $userSet['dostup']['contractor'] == 'on' ) {
						$shares[] = 'contractor';
					}
					if ( $userSet['dostup']['concurent'] == 'on' ) {
						$shares[] = 'concurent';
					}

					$sort_4 .= " and {$sqlname}clientcat.type IN (".yimplode( ",", $shares, "'" ).")";

				}

			}

			//поиск по поисковым представлениям
			if ( $count > 1 && $query_f[0] == 'search' ) {

				$squery = $db -> getOne( "select squery from {$sqlname}search where seid = '".$query_f['1']."' and identity = '$identity'" );
				$squery = explode( ';', $squery );

				sort( $squery );

				$xq = $pfxq = [];
				foreach ( $squery as $x ) {

					$sq   = explode( ':', $x );
					$fild = explode( "--", $sq[0] );

					if ( $fild[0] == 'profile' ) {
						$pfxq[ $fild[1] ][ $sq[1] ][] = $sq[2];
					}
					else {
						$xq[ $sq[0] ][ $sq[1] ][] = $sq[2];
					}

				}

				$xsub         = [];
				$countProfile = 0;
				$profileAr    = [];
				foreach ( $xq as $field => $xquery ) {

					foreach ( $xquery as $term => $values ) {

						switch ($term) {

							case "=":

								if ( in_array( $field, $isDate ) ) {

									if ( !in_array( $values[0], [
										'{today}',
										'{week}',
										'{prevweek}',
										'{nextweek}',
										'{month}',
										'{prevmonth}',
										'{nextmonth}'
									] ) ) {

										$xsub[] = " (DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m-%d') $term '$values[0]')";

									}
									elseif ( $values[0] == '{today}' ) {

										$xsub[] = " DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m-%d') $term DATE( NOW() )";

									}
									else {

										$period = getPeriod( 'month' );

										if ( $values[0] == '{week}' ) {
											$period = getPeriod( 'calendarweek' );
										}
										elseif ( $values[0] == '{prevweek}' ) {
											$period = getPeriod( 'calendarweekprev' );
										}
										elseif ( $values[0] == '{nextweek}' ) {
											$period = getPeriod( 'calendarweeknext' );
										}
										/*elseif ( $values[0] == '{prevmonth}' ) {
											$period = getPeriod( 'prevmonth' );
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$period = getPeriod( 'nextmonth' );
										}*/

										//$xsub[] = "({$sqlname}clientcat.$field BETWEEN '$period[0]' and '$period[1]')";

										if ( $values[0] == '{month}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m') $term DATE_FORMAT(NOW(), '%Y-%m')";
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')";
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m')";
										}
										else {
											$xsub[] = "({$sqlname}clientcat.$field BETWEEN '$period[0] 00:00:01' and '$period[1] 23:59:59')";
										}

									}

								}
								else {

									$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}clientcat.$field = '$values[0]'" : "{$sqlname}clientcat.$field IN (".yimplode( ",", $values ).")";

								}

							break;
							case "!=":


								$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}clientcat.$field != '$values[0]'" : "{$sqlname}clientcat.$field NOT IN (".yimplode( ",", $values ).")";

							break;
							case ">":
							case ">=":
							case "<":
							case "<=":

								if ( in_array( $field, $isDate ) ) {

									if ( !in_array( $values[0], [
										'{today}',
										'{week}',
										'{prevweek}',
										'{nextweek}',
										'{month}',
										'{prevmonth}',
										'{nextmonth}'
									] ) ) {

										$xsub[] = " (DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m-%d') $term '$values[0]')";

									}
									elseif ( $values[0] == '{today}' ) {

										$xsub[] = " DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m-%d') $term DATE( NOW() )";

									}
									else {

										$period = getPeriod( 'month' );

										if ( $values[0] == '{week}' ) {
											$period = getPeriod( 'calendarweek' );
										}
										elseif ( $values[0] == '{prevweek}' ) {
											$period = getPeriod( 'calendarweekprev' );
										}
										elseif ( $values[0] == '{nextweek}' ) {
											$period = getPeriod( 'calendarweeknext' );
										}
										/*elseif ( $values[0] == '{prevmonth}' ) {
											$period = getPeriod( 'prevmonth' );
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$period = getPeriod( 'nextmonth' );
										}*/

										if ( $values[0] == '{month}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m') $term DATE_FORMAT(NOW(), '%Y-%m')";
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')";
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}clientcat.$field, '%Y-%m') $term DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m')";
										}
										else {

											$p      = $term == '<' || $term == '<=' ? $period[0] : $period[1];
											$xsub[] = "DATE({$sqlname}clientcat.$field) $term '$p'";

										}

									}

								}
								else {

									$xsub[] = "{$sqlname}clientcat.$field $term '$values[0]'";

								}

							break;
							case "LIKE":
							case "NOT LIKE":

								if ( in_array( $field, [
									'idcategory',
									'clientpath',
									'territory',
									'iduser'
								] ) ) {

									$xsub[] = $term == 'LIKE' ? "{$sqlname}clientcat.$field IN (".yimplode( ",", $values ).")" : "{$sqlname}clientcat.$field NOT IN (".yimplode( ",", $values ).")";

								}
								elseif ( in_array( $field, [
										'title',
										'des',
										'address'
									] ) || ($fieldTypes[ $field ] == "--Обычное--" || $fieldTypes[ $field ] == "") ) {

									if ( $term == 'LIKE' ) {

										$xy = [];
										foreach ( $values as $value ) {
											$xy[] = "{$sqlname}clientcat.$field $term '%".texttosmall( $value )."%'";
										}

										if ( !empty( $xy ) ) {
											$xsub[] = "( ".yimplode( " OR ", $xy )." )";
										}

									}
									else {

										foreach ( $values as $value ) {
											$xsub[] = "{$sqlname}clientcat.$field $term '%".texttosmall( $value )."%'";
										}

									}

								}
								else {

									foreach ( $values as $value ) {

										if ( in_array( $field, [
											'phone',
											'tel',
											'mob',
											'fax'
										] ) ) {

											$xsub[] = "replace(replace(replace(replace(replace('%{$sqlname}clientcat.$field%', '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') $term $value";

										}
										else {
											$xsub[] = "{$sqlname}clientcat.$field $term '%".texttosmall( $value )."%'";
										}

									}

								}

							break;

						}

					}

				}

				if ( !empty( $xsub ) ) {
					$sort_4 .= " AND ".yimplode( " AND ", $xsub );
				}

				if ( !empty( $pfxq ) ) {

					foreach ( $pfxq as $prf => $xquery ) {

						$ptip = $db -> getOne( "SELECT tip FROM {$sqlname}profile_cat WHERE id = '$prf' and identity = '$identity'" );

						foreach ( $xquery as $term => $values ) {

							foreach ( $values as $value ) {

								$value = trim( str_replace( [
									"\n\r",
									"\n",
									"\r",
									","
								], "", $value ) );

								//одно из значений, можем искать = или !=
								if ( in_array( $ptip, [
									"select",
									"radio"
								] ) ) {

									$profileAr[] = "({$sqlname}profile.id = '$prf' and ({$sqlname}profile.value != '' and {$sqlname}profile.value $term '$value'))";

								}
								//текст, можем искать в подстроке
								elseif ( in_array( $ptip, [
									"text",
									"input"
								] ) ) {

									$profileAr[] = "({$sqlname}profile.id = '$prf' and ({$sqlname}profile.value != '' and {$sqlname}profile.value $term '%$value%'))";

								}
								elseif ( $ptip == "checkbox" ) {//несколько значений, надо делать FIND_IN_SET

									if ( in_array( $term, [
										'NOT LIKE',
										'!='
									] ) ) {
										$profileAr[] = "({$sqlname}profile.id = '$prf' and (FIND_IN_SET('$value', REPLACE(value, ';',',')) = 0))";
									}
									elseif ( in_array( $term, [
										'LIKE',
										'='
									] ) ) {
										$profileAr[] = "({$sqlname}profile.id = '$prf' and (FIND_IN_SET('$value', REPLACE(value, ';',',')) > 0))";
									}
									else {
										$profileAr[] = "({$sqlname}profile.id = '$prf' and ({$sqlname}profile.value $term '$value'))";
									}

								}

							}

							$countProfile++;

						}

					}

				}

				/**
				 * работающий подзапрос, который выводит совпадающие со всеми параметрами искомого профиля записи
				 */
				if ( $countProfile > 0 ) {

					$rp = $db -> getAll( "SELECT COUNT(clid) as count, clid FROM {$sqlname}profile WHERE pfid > 0 and (".implode( " or ", $profileAr ).") and identity = '$identity' GROUP BY clid HAVING count = $countProfile" );

					$pclid = [];
					foreach ( $rp as $pro ) {
						$pclid[] = $pro['clid'];
					}

					if ( !empty( $pclid ) ) {
						$sort_4 .= " and {$sqlname}clientcat.clid IN (".implode( ",", $pclid ).")";
					}
					else {
						$sort_4 .= " and {$sqlname}clientcat.clid = 0";
					}

				}

			}
			if ( $count > 1 && $query_f[0] == 'group' ) {

				$ht     .= " LEFT JOIN {$sqlname}grouplist ON {$sqlname}clientcat.clid = {$sqlname}grouplist.clid";
				$sort_4 .= " and {$sqlname}grouplist.gid = '".$query_f[1]."'";

			}

			if ( $iduser > 0 ) {
				$sort_4 .= " and {$sqlname}clientcat.iduser = '$iduser'";
			}

			$so = (in_array( "0", $tip_cmra )) ? " or {$sqlname}clientcat.tip_cmr IS NULL" : "";

			if ( $tip_cmrts != "null" && $tip_cmrts != "''" && $tip_cmrts != "" ) {
				$sort_4 .= " and {$sqlname}clientcat.tip_cmr IN ($tip_cmrts) $so ";
			}
			elseif ( $tip_cmrts == "null" ) {
				$sort_4 .= " and {$sqlname}clientcat.tip_cmr IS NULL ";
			}

			if ( $idcategory != '' ) {
				$sort_4 .= " and {$sqlname}clientcat.idcategory IN ($idcategory)";
			}
			if ( $clientpath != '' ) {
				$sort_4 .= " and {$sqlname}clientcat.clientpath IN ($clientpath)";
			}
			if ( $territory != '' ) {
				$sort_4 .= " and {$sqlname}clientcat.territory IN ($territory)";
			}
			if ( $type != '' ) {
				$sort_4 .= " and {$sqlname}clientcat.type='$type'";
			}

			if ( $alf == "09" ) {
				$sort_4 .= " and {$sqlname}clientcat.title REGEXP '^[0-9]'";
			}
			elseif ( $alf == "AZ" ) {
				$sort_4 .= " and {$sqlname}clientcat.title REGEXP '^[a-zA-Z]'";
			}
			elseif ( $alf != "" ) {
				$sort_4 .= " and {$sqlname}clientcat.title LIKE '$alf%'";
			}

			if ( $haveDostup > 0 ) {

				$sort_4 .= " and (SELECT COUNT(id) FROM {$sqlname}dostup WHERE clid = {$sqlname}clientcat.clid AND iduser = '$haveDostup') > 0";

			}

			$arrr = [
				'phone',
				'tel',
				'fax'
			];

			if ( !empty($word) ) {

				if ( in_array( $tbl_list, $arrr ) ) {
					$sort_4 .= " and replace(replace(replace(replace(replace({$sqlname}clientcat.".$tbl_list.", '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%$word%'";
				}
				elseif ( $tbl_list == 'title' ) {

					$regexp = $aso = [];
					$words  = yexplode( " ", (string)$params['word'] );

					//print_r($words);

					if ( !empty( $words ) ) {

						asort( $words );

						foreach ( $words as $word ) {
							if ( $word != ' ' ) {
								$regexp[] = '('.$word.')+';
							}
						}


						$aso[] = " LOWER({$sqlname}clientcat.title) REGEXP '".implode( "(.*)?", $regexp )."'";

						$regexp = [];

						//if ( !empty( $words ) ) {

						rsort( $words );

						foreach ( $words as $word ) {
							if ( $word != ' ' ) {
								$regexp[] = '('.$word.')+';
							}
						}


						//}

						$aso[] = " LOWER({$sqlname}clientcat.title) REGEXP '".implode( "(.*)?", $regexp )."'";

						$sort_4 .= " and (".yimplode( " or ", $aso ).")";

					}
					else {
						$sort_4 .= " and {$sqlname}clientcat.".$tbl_list." LIKE '%$word%'";
					}

				}
				elseif ( $tbl_list == 'titledes' ) {
					$sort_4 .= " and ({$sqlname}clientcat.title LIKE '%$word%' or {$sqlname}clientcat.des LIKE '%$word%')";
				}
				else {
					$sort_4 .= " and ({$sqlname}clientcat.".$tbl_list." LIKE '%$word%')";
				}

			}

			if ( $htg == '' && (!empty( $groups ) && in_array( 0, $groups )) ) {
				$htg = " GROUP BY {$sqlname}clientcat.clid";
			}

			//конечный запрос
			//print
			$query = "
				SELECT
					DISTINCT
					{$sqlname}clientcat.clid as clid,
					{$sqlname}clientcat.title as title,
					$inputs
					{$sqlname}personcat.person as person,
					{$sqlname}personcat.tel as tel,
					{$sqlname}personcat.mob as mob,
					{$sqlname}personcat.mail as pemail,
					{$sqlname}user.title as user,
					{$sqlname}relations.color as color,
					{$sqlname}category.title as category,
					{$sqlname}territory_cat.title as territory,
					{$sqlname}clientpath.name as clientpath,
					(SELECT MAX(datum) FROM {$sqlname}history WHERE clid = {$sqlname}clientcat.clid and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity') as last_history,
					(SELECT MAX(datum) FROM {$sqlname}dogovor WHERE clid = {$sqlname}clientcat.clid and identity = '$identity') as last_deal
					$selectplus
					$htq
				FROM {$sqlname}clientcat
					LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
					LEFT JOIN {$sqlname}personcat ON {$sqlname}clientcat.pid = {$sqlname}personcat.pid
					LEFT JOIN {$sqlname}relations ON {$sqlname}relations.title = {$sqlname}clientcat.tip_cmr
					LEFT JOIN {$sqlname}category ON {$sqlname}clientcat.idcategory = {$sqlname}category.idcategory
					LEFT JOIN {$sqlname}territory_cat ON {$sqlname}clientcat.territory = {$sqlname}territory_cat.idcategory
					LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientcat.clientpath = {$sqlname}clientpath.id
					$ht
				WHERE {$sqlname}clientcat.clid > 0 $sort_4 $filterplus and {$sqlname}clientcat.identity = '$identity' $htg1";

			if ( $countQuery ) {
				$queryCount = "
				SELECT
					COUNT(DISTINCT {$sqlname}clientcat.clid)
					-- $htq
				FROM {$sqlname}clientcat
					LEFT JOIN {$sqlname}user ON {$sqlname}clientcat.iduser = {$sqlname}user.iduser
					LEFT JOIN {$sqlname}personcat ON {$sqlname}clientcat.pid = {$sqlname}personcat.pid
					LEFT JOIN {$sqlname}relations ON {$sqlname}relations.title = {$sqlname}clientcat.tip_cmr
					LEFT JOIN {$sqlname}category ON {$sqlname}clientcat.idcategory = {$sqlname}category.idcategory
					LEFT JOIN {$sqlname}territory_cat ON {$sqlname}clientcat.territory = {$sqlname}territory_cat.idcategory
					LEFT JOIN {$sqlname}clientpath ON {$sqlname}clientcat.clientpath = {$sqlname}clientpath.id
					$ht
				WHERE 
					{$sqlname}clientcat.clid > 0 
					$sort_4 AND 
					{$sqlname}clientcat.identity = '$identity' 
					-- $htg
				";
			}

		break;
		case 'dogovor':

			$freplace = $params['freplace'] ?? [
				"datum"       => "dcreate",
				"datum_plan"  => "dplan",
				"datum_close" => "dclose",
				"idcategory"  => "idstep",
				"kol_fact"    => "kolf"
			];

			if ( isset( $params['fields'] ) && !empty( $fields ) ) {

				foreach ( $fields as $field ) {

					$asfield = (array_key_exists( $field, $freplace ) && $params['namereplace']) ? strtr( $field, $freplace ) : $field;
					$inputs  .= $sqlname."dogovor.".$field." as ".$asfield.",";

				}

			}

			$iduser     = $params['iduser'];
			$idcategory = !empty( $params['idcategory'] ) ? implode( ",", $params['idcategory'] ) : '';
			$word       = untag( $params['word'] );
			$tbl_list   = $params['tbl_list'];
			$tid        = $params['tid'];
			$filter     = $params['filter'];
			$direction  = $params['direction'];
			$mcid       = $params['mcid'];
			$isOld      = $params['isOld'];

			$haveCredit  = $params['haveCredit'];
			$haveHistory = $params['haveHistory'];
			$haveTask    = $params['haveTask'];
			$haveDostup  = (int)$params['dostup'];
			$isFrozen    = $params['isFrozen'];

			$selectplus = (isset( $params['selectplus'] )) ? ", ".$params['selectplus'] : "";

			$query_f = explode( ':', (string)$filter );
			$count   = count( $query_f );
			$dos     = '';
			//$ar      = [];

			//определим доступ к чужим сделкам
			if ( !$params['excludeDostup'] ) {
				$dos = " OR (SELECT COUNT({$sqlname}dostup.did) FROM {$sqlname}dostup WHERE did = {$sqlname}dogovor.did and {$sqlname}dostup.iduser = '$iduser1') > 0";
			}

			$xusers = (array)get_people( $iduser1, "yes" );

			$us = !empty( $xusers ) ? " AND {$sqlname}dogovor.iduser IN (".yimplode( ",", $xusers ).")" : "";

			//поля, которые относятся к датам
			array_push( $isDate, "datum", "datum_plan", "datum_izm", "datum_close", "datum_start", "datum_end" );

			//для системных представлений
			if ( $count < 2 ) {

				if ( $filter == 'otdel' ) {
					$sort_4 .= " and (COALESCE({$sqlname}dogovor.close, 'no') != 'yes' $us)";
				}
				if ( $filter == 'my' ) {
					$sort_4 .= " and COALESCE({$sqlname}dogovor.close, 'no') != 'yes' and ({$sqlname}dogovor.iduser = '$iduser1' $dos)";
				}
				if ( $filter == 'all' ) {
					$sort_4 .= " and COALESCE({$sqlname}dogovor.close, 'no') != 'yes'";
				}

				//при показе ВСЕХ, но с ограничением по руководителю
				if ( $filter == 'all' && $userSet['filterAllBy'] > 0 ) {
					$sort_4 .= " and {$sqlname}dogovor.iduser IN (".implode( ",", (array)get_people( $userSet['filterAllBy'], "yes" ) ).") ";
				}

				if ( $filter == 'close' ) {
					$sort_4 .= " and {$sqlname}dogovor.close = 'yes' $us";
				}
				if ( $filter == 'closedealsday' ) {
					$sort_4 .= " and ({$sqlname}dogovor.datum_close = '".current_datum()."')$us";
				}
				if ( $filter == 'closedealsweek' ) {

					$first = strtotime( "last Monday" );//monday
					$w1    = strftime( '%Y-%m-%d', $first );
					$w2    = strftime( '%Y-%m-%d', $first + 6 * 86400 );

					$sort_4 .= " and ({$sqlname}dogovor.datum_close BETWEEN '$w1' and '$w2') $us";

				}
				if ( $filter == 'closedealsmounth' ) {

					$m      = getPeriod( 'month' );
					$sort_4 .= " and ({$sqlname}dogovor.datum_close BETWEEN '$m[0]' and '$m[1]') $us";

				}

				if ( $filter == 'alldeals' ) {
					$sort_4 .= "";
				}
				if ( $filter == 'alldealsday' ) {
					$sort_4 .= " and ({$sqlname}dogovor.datum = '".current_datum()."')";
				}
				if ( $filter == 'alldealsweek' ) {

					$first  = strtotime( "last Monday" );//monday
					$w1     = strftime( '%Y-%m-%d', $first );
					$w2     = strftime( '%Y-%m-%d', $first + 6 * 86400 );
					$sort_4 .= " and ({$sqlname}dogovor.datum BETWEEN '$w1' and '$w2')";

				}
				if ( $filter == 'alldealsmounth' ) {

					$m      = getPeriod( 'month' );
					$sort_4 .= " and ({$sqlname}dogovor.datum BETWEEN '$m[0]' and '$m[1]')";

				}

			}
			//для пользовательских представлений
			else {

				$squery = explode( ';', $db -> getOne( "select squery from {$sqlname}search where seid = '$query_f[1]' and identity = '$identity'" ) );
				sort( $squery );

				/**
				 * Группируем запрос по полям и условию. Новая реализация
				 */
				$xq = [];
				foreach ( $squery as $x ) {

					$sq                       = explode( ':', $x );
					$xq[ $sq[0] ][ $sq[1] ][] = $sq[2];

				}

				$xsub = [];
				foreach ( $xq as $field => $xquery ) {

					foreach ( $xquery as $term => $values ) {

						switch ($term) {

							case "=":

								if ( in_array( $field, $isDate ) ) {

									if ( !in_array( $values[0], [
										'{today}',
										'{week}',
										'{prevweek}',
										'{nextweek}',
										'{month}',
										'{prevmonth}',
										'{nextmonth}'
									] ) ) {

										$xsub[] = " (DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m-%d') $term '$values[0]')";

									}
									elseif ( $values[0] == '{today}' ) {

										$xsub[] = " DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m-%d') $term DATE( NOW() )";

									}
									else {

										$period = getPeriod( 'month' );

										if ( $values[0] == '{week}' ) {
											$period = getPeriod( 'calendarweek' );
										}
										elseif ( $values[0] == '{prevweek}' ) {
											$period = getPeriod( 'calendarweekprev' );
										}
										elseif ( $values[0] == '{nextweek}' ) {
											$period = getPeriod( 'calendarweeknext' );
										}
										/*elseif ( $values[0] == '{prevmonth}' ) {
											$period = getPeriod( 'prevmonth' );
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$period = getPeriod( 'nextmonth' );
										}*/

										if ( $values[0] == '{month}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m') $term DATE_FORMAT(NOW(), '%Y-%m')";
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m') $term DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')";
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m') $term DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m')";
										}
										else {
											$xsub[] = "({$sqlname}dogovor.$field BETWEEN '$period[0] 00:00:01' and '$period[1] 23:59:59')";
										}

									}

								}
								elseif ( in_array( $field, [
									'idcategory',
									'iduser',
									'tip',
									'sid',
									'mcid',
									'direction'
								] ) ) {

									$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}dogovor.$field = '$values[0]'" : "{$sqlname}dogovor.$field IN (".yimplode( ",", $values ).")";

								}
								elseif ( $field == 'close' ) {

									$xsub[] = $values[0] == 'no' ? "COALESCE({$sqlname}dogovor.close, 'no') != 'yes'" : "{$sqlname}dogovor.$field = 'yes'";

								}
								else {

									$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}dogovor.$field = '$values[0]'" : "{$sqlname}dogovor.$field IN (".yimplode( ",", $values, "'" ).")";

								}

							break;
							case "!=":

								$xsub[] = count( (array)$values ) == 1 ? "{$sqlname}dogovor.$field != '$values[0]'" : "{$sqlname}dogovor.$field NOT IN (".yimplode( ",", $values ).")";

							break;
							case ">":
							case ">=":
							case "<":
							case "<=":

								if ( in_array( $field, $isDate ) ) {

									if ( !in_array( $values[0], [
										'{today}',
										'{week}',
										'{prevweek}',
										'{nextweek}',
										'{month}',
										'{prevmonth}',
										'{nextmonth}'
									] ) ) {

										$xsub[] = " (DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m-%d') $term '$values[0]')";

									}
									elseif ( $values[0] == '{today}' ) {

										$xsub[] = " DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m-%d') $term '".current_datum()."'";

									}
									else {

										$period = getPeriod( 'month' );

										if ( $values[0] == '{week}' ) {
											$period = getPeriod( 'calendarweek' );
										}
										elseif ( $values[0] == '{prevweek}' ) {
											$period = getPeriod( 'calendarweekprev' );
										}
										elseif ( $values[0] == '{nextweek}' ) {
											$period = getPeriod( 'calendarweeknext' );
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$period = getPeriod( 'prevmonth' );
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$period = getPeriod( 'nextmonth' );
										}

										if ( $values[0] == '{month}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m') $term DATE_FORMAT(NOW(), '%Y-%m')";
										}
										elseif ( $values[0] == '{prevmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m') $term DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')";
										}
										elseif ( $values[0] == '{nextmonth}' ) {
											$xsub[] = "DATE_FORMAT({$sqlname}dogovor.$field, '%Y-%m') $term DATE_FORMAT(NOW() + INTERVAL 1 MONTH, '%Y-%m')";
										}
										else {

											$p      = $term == '<' || $term == '<=' ? $period[0] : $period[1];
											$xsub[] = "DATE({$sqlname}dogovor.$field) $term '$p'";

										}

									}

								}
								elseif ( $field == 'idcategory' ) {

									$step = current_dogstepname( (int)str_replace( "%", "", $values[0] ) );

									$xsub[] = "{$sqlname}dogcategory.title $term '$step'";

								}
								else {

									$xsub[] = "{$sqlname}dogovor.$field $term '$values[0]'";

								}

							break;
							case "LIKE":
							case "NOT LIKE":

								if ( in_array( $field, [
									'idcategory',
									'iduser',
									'tip',
									'sid',
									'mcid',
									'direction'
								] ) ) {

									$xsub[] = $term == 'LIKE' ? "{$sqlname}dogovor.$field IN (".yimplode( ",", $values ).")" : "{$sqlname}dogovor.$field NOT IN (".yimplode( ",", $values ).")";

								}
								elseif ( in_array( $field, [
										'title',
										'content',
										'adres'
									] ) || ($fieldTypes[ $field ] == "--Обычное--" || $fieldTypes[ $field ] == "") ) {

									if ( $term == 'LIKE' ) {

										$xy = [];
										foreach ( $values as $value ) {
											$xy[] = "{$sqlname}dogovor.$field $term '%".texttosmall( $value )."%'";
										}

										if ( !empty( $xy ) ) {
											$xsub[] = "( ".yimplode( " OR ", $xy )." )";
										}

									}
									else {

										foreach ( $values as $value ) {
											$xsub[] = "{$sqlname}dogovor.$field $term '%".texttosmall( $value )."%'";
										}

									}

								}
								else {

									foreach ( $values as $value ) {

										if ( in_array( $field, [
											'phone',
											'tel',
											'mob',
											'fax'
										] ) ) {

											$xsub[] = "replace(replace(replace(replace(replace('%{$sqlname}dogovor.$field%', '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') $term '$value'";

										}
										else {
											$xsub[] = "{$sqlname}dogovor.$field $term '%".texttosmall( $value )."%'";
										}

									}

								}

							break;

						}

					}

				}

				if ( !empty( $xsub ) ) {
					$sort_4 .= " AND ".yimplode( " AND ", $xsub );
				}

				// print_r($xsub);

			}

			if ( $isOld == "older" ) {
				$sort_4 .= " and {$sqlname}dogovor.datum_plan < '".current_datum()."'";
			}
			elseif ( $isOld == "futur" ) {
				$sort_4 .= " and {$sqlname}dogovor.datum_plan >= '".current_datum()."'";
			}

			if ( $idcategory != "" ) {
				$sort_4 .= " and {$sqlname}dogovor.idcategory IN (".$idcategory.")";
			}

			if ( $direction != "" ) {
				$sort_4 .= " and {$sqlname}dogovor.direction = '$direction'";
			}

			if ( $mcid != "" ) {
				$sort_4 .= " and {$sqlname}dogovor.mcid = '$mcid'";
			}

			if ( $tid != "" ) {
				$sort_4 .= " and {$sqlname}dogovor.tip = '$tid'";
			}

			if ( $iduser > 0 ) {
				$sort_4 .= " and {$sqlname}dogovor.iduser = '$iduser'";
			}

			if ( !empty($word) ) {

				if ( $tbl_list == 'title' ) {

					$f = '';

					if ( prepareMobPhone( $word ) != '' ) {
						$f .= "or (replace(replace(replace(replace(replace(phone, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $word )."%') or (replace(replace(replace(replace(replace(fax, '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".prepareMobPhone( $word )."%')";
					}

					$sort_4 .= " and ({$sqlname}dogovor.title LIKE '%$word%' or {$sqlname}dogovor.clid IN (SELECT clid FROM {$sqlname}clientcat WHERE (title LIKE '%$word%' $f) and identity = '$identity'))";

				}
				elseif ( $tbl_list == 'titledes' ) {
					$sort_4 .= " and ({$sqlname}dogovor.title LIKE '%".$word."%' or {$sqlname}dogovor.content LIKE '%$word%')";
				}
				elseif ( $tbl_list == 'titleclient' ) {
					$sort_4 .= " and {$sqlname}dogovor.clid IN (SELECT clid FROM {$sqlname}clientcat WHERE {$sqlname}clientcat.title LIKE '%".$word."%' AND identity = '$identity')";
				}
				else {
					$sort_4 .= " and ({$sqlname}dogovor.".$tbl_list." LIKE '%$word%')";
				}

			}

			$ht  = '';
			$htq = '';

			if ( $haveTask == 'yes' ) {
				$sort_4 .= " and (SELECT COUNT({$sqlname}tasks.tid) FROM {$sqlname}tasks WHERE {$sqlname}tasks.did = {$sqlname}dogovor.did AND active = 'yes') = 0";
			}
			if ( $haveHistory == 'yes' ) {
				$sort_4 .= " and (SELECT COUNT({$sqlname}history.cid) FROM {$sqlname}history WHERE {$sqlname}history.did = {$sqlname}dogovor.did and {$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM')) > 0";
			}
			elseif ( $haveHistory == 'no' ) {
				$sort_4 .= " and (SELECT COUNT({$sqlname}history.cid) FROM {$sqlname}history WHERE {$sqlname}history.did = {$sqlname}dogovor.did and {$sqlname}history.tip NOT IN ('СобытиеCRM','ЛогCRM')) = 0";
			}

			if ( $isFrozen == '1' ) {
				$sort_4 .= " and {$sqlname}dogovor.isFrozen = '1'";
			}
			elseif ( $isFrozen == '0' ) {
				$sort_4 .= " and {$sqlname}dogovor.isFrozen = '0'";
			}

			/* Фильтр по сделкам со счетами */
			if ( $haveCredit == 'yes' ) {
				$sort_4 .= " and (SELECT COUNT({$sqlname}credit.crid) FROM {$sqlname}credit WHERE {$sqlname}credit.did = {$sqlname}dogovor.did) > 0";
			}
			elseif ( $haveCredit == 'no' ) {
				$sort_4 .= " and (SELECT COUNT({$sqlname}credit.crid) FROM {$sqlname}credit WHERE {$sqlname}credit.did = {$sqlname}dogovor.did) = 0";
			}

			if ( $haveDostup > 0 ) {
				$sort_4 .= " and (SELECT COUNT(id) FROM {$sqlname}dostup WHERE did = {$sqlname}dogovor.did AND iduser = '$haveDostup') > 0";
			}

			//print_r($params['client']);

			if ( !empty( $params['client'] ) && $params['client']['word'] != '' ) {

				switch ($params['client']['tbl_list']) {

					case "phone":
					case "fax":

						$params['client']['word'] = str_replace( [
							"(",
							"+",
							")",
							"-",
							" "
						], "", $params['client']['word'] );

						$sort_4 .= " and (replace(replace(replace(replace(replace({$sqlname}clientcat.".$params['client']['tbl_list'].", '+', ''), '(', ''), ')', ''), ' ', ''), '-', '') LIKE '%".$params['client']['word']."%')";

					break;
					default:

						$sort_4 .= " and ({$sqlname}clientcat.".$params['client']['tbl_list']." LIKE '%".$params['client']['word']."%')";

					break;

				}

			}

			$htg = " GROUP BY {$sqlname}dogovor.did";

			//конечный запрос
			$query = "
				SELECT
					DISTINCT
					{$sqlname}dogovor.did as did,
					$inputs
					{$sqlname}user.title as user,
					{$sqlname}clientcat.title as client,
					{$sqlname}dogcategory.idcategory as stepid,
					{$sqlname}dogcategory.title as step,
					{$sqlname}dogcategory.content as steptitle,
					{$sqlname}dogtips.title as tips,
					{$sqlname}dogstatus.title as dstatus,
					{$sqlname}direction.title as direction,
					(SELECT name_shot FROM {$sqlname}mycomps WHERE id = {$sqlname}dogovor.mcid and identity = '$identity') as mcid,
					MIN({$sqlname}credit.datum_credit) as creditDate,
					(SELECT MAX(datum) FROM {$sqlname}history WHERE did = {$sqlname}dogovor.did and tip NOT IN ('СобытиеCRM','ЛогCRM') and identity = '$identity') as last_history
					$selectplus
					$htq
				FROM {$sqlname}dogovor
					LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
					-- LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
					LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
					LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
					LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
					LEFT JOIN {$sqlname}direction ON {$sqlname}dogovor.direction = {$sqlname}direction.id
					LEFT JOIN {$sqlname}credit ON {$sqlname}dogovor.did = {$sqlname}credit.did
					$ht
				WHERE
					{$sqlname}dogovor.did > 0
					$sort_4 and
					{$sqlname}dogovor.identity = '$identity'
					$htg";

			if ( $countQuery ) {

				$queryCount = "
					SELECT
						COUNT({$sqlname}dogovor.did)
						$htq
					FROM {$sqlname}dogovor
						-- LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
						-- LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
						LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
						LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
						-- LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
						-- LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
						-- LEFT JOIN {$sqlname}direction ON {$sqlname}dogovor.direction = {$sqlname}direction.id
						-- LEFT JOIN {$sqlname}credit ON {$sqlname}dogovor.did = {$sqlname}credit.did
						$ht
					WHERE
						{$sqlname}dogovor.did > 0
						$sort_4 and
						{$sqlname}dogovor.identity = '$identity'
						$htg";
			}

			$queryAllCounts = "
				SELECT
					SUM(if(COALESCE({$sqlname}dogovor.close, 'no') != 'yes', {$sqlname}dogovor.kol, {$sqlname}dogovor.kol_fact)) as kol,
					SUM({$sqlname}dogovor.marga) as marga
					$htq
				FROM {$sqlname}dogovor
					-- LEFT JOIN {$sqlname}user ON {$sqlname}dogovor.iduser = {$sqlname}user.iduser
					-- LEFT JOIN {$sqlname}personcat ON {$sqlname}dogovor.pid = {$sqlname}personcat.pid
					LEFT JOIN {$sqlname}clientcat ON {$sqlname}dogovor.clid = {$sqlname}clientcat.clid
					LEFT JOIN {$sqlname}dogcategory ON {$sqlname}dogovor.idcategory = {$sqlname}dogcategory.idcategory
					-- LEFT JOIN {$sqlname}dogtips ON {$sqlname}dogovor.tip = {$sqlname}dogtips.tid
					-- LEFT JOIN {$sqlname}dogstatus ON {$sqlname}dogovor.sid = {$sqlname}dogstatus.sid
					-- LEFT JOIN {$sqlname}direction ON {$sqlname}dogovor.direction = {$sqlname}direction.id
					-- LEFT JOIN {$sqlname}credit ON {$sqlname}dogovor.did = {$sqlname}credit.did
					$ht
				WHERE
					{$sqlname}dogovor.did > 0
					$sort_4 and
					{$sqlname}dogovor.identity = '$identity'
					-- $htg
				";

		break;

	}

	//print $query;

	if ( $countQuery ) {

		return [
			"query"          => $query,
			"queryCount"     => $queryCount,
			"queryAllCounts" => $queryAllCounts,
			"sort"           => $sort_4
		];

	}

	return $query;

}

/**
 * Лог изменений в записях Клиента, Контакта, Сделки
 *
 * @param       $tip
 * @param       $id
 * @param array $param
 * @param array $oldparam
 * @param bool  $add
 *
 * @return string
 * @throws \Exception
 * @package  Func
 * @category Core
 */
function doLogger($tip, $id, array $param = [], array $oldparam = [], bool $add = false): string {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$iduser1  = $GLOBALS['iduser1'];

	//$loger = '';
	$des  = [];
	$clid = $did = $pid = 0;

	$log['old'] = $oldparam;
	$log['new'] = $param;

	if ( isset( $param['pid_list'] ) ) {
		$param['pid_list'] = yexplode( ";", $param['pid_list'] );
	}

	$diff = array_diff_ext( $oldparam, $param );

	$log['diff'] = $diff;

	//file_put_contents(dirname( __DIR__, 2 )."/cash/logger.json", json_encode_cyr($log));

	unset( $diff['action'] );

	switch ($tip) {

		case 'clid':

			$clid = $id;

			//массив имен полей
			$titles = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );

			$titles['type'] = 'Тип записи';

			foreach ( $diff as $key => $value ) {

				switch ($key) {

					case 'idcategory':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.get_client_category( $param[ $key ] ).'</b> (было - '.get_client_category( $oldparam[ $key ] ).')';

					break;
					case 'clientpath':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_clientpathbyid( $param[ $key ] ).'</b> (было - '.current_clientpathbyid( $oldparam[ $key ] ).')';

					break;
					case 'territory':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_territory( $param[ $key ] ).'</b> (было - '.current_territory( $oldparam[ $key ] ).')';

					break;
					case 'head_clid':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_client( $param[ $key ] ).'</b> (было - '.current_client( $oldparam[ $key ] ).')';

					break;
					case 'pid':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_person( $param[ $key ] ).'</b> (было - '.current_person( $oldparam[ $key ] ).')';

					break;
					case 'phone':
					case 'mob':
					case 'fax':

						$param[ $key ]    = (is_array( $param[ $key ] )) ? yimplode( ",", $param[ $key ] ) : $param[ $key ];
						$oldparam[ $key ] = (is_array( $oldparam[ $key ] )) ? yimplode( ",", $oldparam[ $key ] ) : $oldparam[ $key ];

						if ( $param[ $key ] == '' ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] == '' ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.$param[ $key ].'</b> (было - '.$oldparam[ $key ].')';

					break;
					default:

						$param[ $key ]    = (is_array( $param[ $key ] )) ? yimplode( ";", $param[ $key ] ) : $param[ $key ];
						$oldparam[ $key ] = (is_array( $oldparam[ $key ] )) ? yimplode( ";", $oldparam[ $key ] ) : $oldparam[ $key ];

						if ( $param[ $key ] == '' ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] == '' ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.$param[ $key ].'</b> (было - '.$oldparam[ $key ].')';

					break;
				}

			}

		break;
		case 'pid':

			$pid = $id;

			//массив имен полей
			$titles = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order" );

			foreach ( $diff as $key => $value ) {

				switch ($key) {
					case 'clientpath':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_clientpathbyid( $param[ $key ] ).'</b> (было - '.current_clientpathbyid( $oldparam[ $key ] ).')';

					break;
					case 'loyalty':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_loyalty( $param[ $key ] ).'</b> (было - '.current_loyalty( $oldparam[ $key ] ).')';

					break;
					case 'clid':
						$des[] = strtr( $key, $titles ).' - <b>'.current_client( $param[ $key ] ).'</b> (было - '.current_client( $oldparam[ $key ] ).')';
					break;
					default:

						$param[ $key ]    = (is_array( $param[ $key ] )) ? yimplode( ", ", $param[ $key ] ) : $param[ $key ];
						$oldparam[ $key ] = (is_array( $oldparam[ $key ] )) ? yimplode( ", ", $oldparam[ $key ] ) : $oldparam[ $key ];

						if ( $param[ $key ] == '' ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] == '' ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.$param[ $key ].'</b> (было - '.$oldparam[ $key ].')';

					break;
				}

			}

		break;
		case 'did':

			$did = $id;

			//массив имен полей
			$t1 = [
				"uid"         => "UID сделки",
				"clid"        => "Клиент по сделке",
				"payer"       => "Плательщик по сделке",
				"title"       => "Название",
				"content"     => "Описание",
				"tip"         => "Тип сделки",
				"mcid"        => "Компания",
				"idcategory"  => "Этап сделки",
				"direction"   => "Направление",
				"adres"       => "Адрес",
				"datum_plan"  => "Дата план",
				"datum_start" => "Период.Начало",
				"datum_end"   => "Период.Конец",
				"datum_close" => "Дата закрытия",
				"kol"         => "Оборот",
				"marga"       => "Маржа",
				//"marg"        => "Маржа",
				"dog_num"     => "Договор",
				"pid_list"    => "Контакты сделки",
				"coid1"       => "Конкуренты по сделке",
				"calculate"   => "Спецификация",
				"idcourse"    => "Курс валюты",
				"idcurrency"  => "Валюта"
			];

			$t2 = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order" );

			$titles = array_merge( $t1, $t2 );

			unset( $diff['datum_close'] );

			foreach ( $diff as $key => $value ) {

				switch ($key) {

					case 'clid':
						$des[] = strtr( $key, $titles ).' - <b>'.current_client( $param[ $key ] ).'</b> (было - '.current_client( $oldparam[ $key ] ).')';
					break;
					case 'payer':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_client( $param[ $key ] ).'</b> (было - '.current_client( $oldparam[ $key ] ).')';

					break;
					case 'datum_plan':
					case 'datum_start':
					case 'datum_end':

						if ( empty( $param[ $key ] ) ) {
							$param[ $key ] = 'Нет';
						}
						if ( empty( $oldparam[ $key ] ) ) {
							$oldparam[ $key ] = 'Нет';
						}

						if ( $param[ $key ] != $oldparam[ $key ] && $param[ $key ] != '0000-00-00' ) {
							$des[] = strtr( $key, $titles ).' - <b>'.format_date_rus( $param[ $key ] ).'</b> (было - '.format_date_rus( $oldparam[ $key ] ).')';
						}

					break;
					case 'tip':

						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_dogtype( (int)$param[ $key ] ).'</b> (было - '.current_dogtype( $oldparam[ $key ] ).')';

					break;
					case 'direction':

						if ( (int)$param[ $key ] == 0 ) {
							$param[ $key ] = 'Нет';
						}
						if ( (int)$oldparam[ $key ] == 0 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_direction( (int)$param[ $key ] ).'</b> (было - '.current_direction( $oldparam[ $key ] ).')';

					break;
					case 'mcid':

						if ( (int)$param[ $key ] == 0 ) {
							$param[ $key ] = 'Нет';
						}
						if ( (int)$oldparam[ $key ] == 0 ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.current_company( (int)$param[ $key ] ).'</b> (было - '.current_company( $oldparam[ $key ] ).')';

					break;
					case 'marga':
					case 'kol':
						$des[] = strtr( $key, $titles ).' - <b>'.num_format( $param[ $key ] ).'</b> (было - '.num_format( $oldparam[ $key ] ).')';
					break;
					case 'dog_num':
						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}
						$des[] = strtr( $key, $titles ).' - <b>'.current_contract( $param[ $key ] ).'</b> (было - '.current_contract( $oldparam[ $key ] ).')';
					break;
					case 'idcategory':
						if ( $param[ $key ] < 1 ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] < 1 ) {
							$oldparam[ $key ] = 'Нет';
						}
						$des[] = strtr( $key, $titles ).' - <b>'.current_dogstepname( $param[ $key ] ).'%</b> (было - '.current_dogstepname( $oldparam[ $key ] ).'%)';
					break;
					case 'pid_list':

						$pids = !is_array( $param[ $key ] ) ? explode( ";", $param[ $key ] ) : $param[ $key ];
						$pna  = [];

						foreach ( $pids as $p ) {

							$pna[] = current_person( $p );

						}

						$pn = implode( ', ', $pna );

						$oldpids = !is_array( $oldparam[ $key ] ) ? explode( ";", $oldparam[ $key ] ) : $oldparam[ $key ];
						$apo     = [];

						foreach ( $oldpids as $p ) {
							$apo[] = current_person( $p );
						}

						$po = implode( ', ', $apo );

						if ( $pn == '' ) {
							$pn = 'Нет';
						}

						if ( $po == '' ) {
							$po = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.$pn.'</b> (было - '.$po.')';

					break;
					case 'coid1':

						$pids = explode( ";", $param[ $key ] );
						$apn  = [];
						foreach ( $pids as $c ) {
							$apn[] = current_client( $c );
						}
						$pn = implode( ', ', $apn );

						$oldpids = explode( ";", $oldparam[ $key ] );
						$apo     = [];
						foreach ( $oldpids as $p ) {
							$apo[] = current_client( $p );
						}
						$po = implode( ', ', $apo );

						if ( $pn == '' ) {
							$pn = 'Нет';
						}
						if ( $po == '' ) {
							$po = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.$pn.'</b> (было - '.$po.')';

					break;
					case 'calculate':

						$param[ $key ]    = ($param[ $key ] == 'yes') ? 'Вкл.' : 'Откл.';
						$oldparam[ $key ] = ($oldparam[ $key ] == 'yes') ? 'Вкл.' : 'Откл.';

						$des[] = 'Спецификация - <b>'.$param[ $key ].'</b> (было - '.$oldparam[ $key ].')';

					break;
					case 'idcurrency':

						$currency    = (new Currency()) -> currencyInfo( (int)$param[ $key ] );
						$currencyOld = (new Currency()) -> currencyInfo( (int)$oldparam[ $key ] );

						$des[] = strtr( $key, $titles ).' - <b>'.$currency['name'].'</b> (было - '.$currencyOld['name'].')';

					break;
					case 'idcourse':

						$course    = (new Currency()) -> courseInfo( (int)$param[ $key ] );
						$courseOld = (new Currency()) -> courseInfo( (int)$oldparam[ $key ] );

						$des[] = strtr( $key, $titles ).' - <b>'.$course['course'].'</b> (было - '.$courseOld['course'].')';

					break;
					default:

						$param[ $key ]    = (is_array( $param[ $key ] )) ? yimplode( ", ", $param[ $key ] ) : $param[ $key ];
						$oldparam[ $key ] = (is_array( $oldparam[ $key ] )) ? yimplode( ", ", $oldparam[ $key ] ) : $oldparam[ $key ];

						if ( $param[ $key ] == '' ) {
							$param[ $key ] = 'Нет';
						}
						if ( $oldparam[ $key ] == '' ) {
							$oldparam[ $key ] = 'Нет';
						}

						$des[] = strtr( $key, $titles ).' - <b>'.$param[ $key ].'</b> (было - '.$oldparam[ $key ].')';

					break;
				}

			}

		break;

	}

	if ( !empty( $diff ) ) {
		$loger = "Изменены параметры записи:\n============================\n ".implode( "\n", $des );
	}
	else {
		$loger = 'none';
	}

	if ( !empty( $diff ) && $add ) {

		addHistorty( [
			'iduser'   => $iduser1,
			'clid'     => $clid,
			'pid'      => $pid,
			'did'      => $did,
			'datum'    => current_datumtime(),
			'des'      => yimplode( "\n", $des ),
			'tip'      => 'ЛогCRM',
			'identity' => $identity
		] );

		$loger = 'Событие добавлено в Историю<br>';

	}

	return $loger;

}

/**
 * Получает прочие настройки системы ( параметр other )
 *
 * @param array $params
 *                     - int **identity**
 *                     - string **name** - имя параметра, если не указано возвращает массив всех параметров
 *
 * @return array|mixed
 * @category Core
 * @package  Func
 */
function otherSettings(array $params = []) {

	$rootpath = dirname( __DIR__, 2 );

	$identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$fpath    = $GLOBALS['fpath'];

	$otherSettings = [];

	if ( !file_exists( $rootpath."/cash/".$fpath."otherSettings.json" ) ) {

		$other         = $db -> getOne( "SELECT other FROM {$sqlname}settings WHERE id = '$identity'" );
		$otherSettings = [
			"partner"              => $other[0] == 'yes',
			"concurent"            => $other[1] == 'yes',
			"credit"               => $other[2] == 'yes',
			"price"                => $other[3] == 'yes',
			"dealPeriod"           => $other[4] == 'yes',
			"contract"             => $other[5] == 'yes',
			"creditAlert"          => $other[6],
			"dealAlert"            => $other[7],
			"profile"              => $other[8] == 'yes',
			"marga"                => $other[9] == 'yes',
			"potential"            => $other[10] == 'yes',
			"expressForm"          => $other[11] == 'yes',
			"printInvoice"         => $other[12] == 'yes',
			"clientIsPerson"       => $other[13] != 'yes',
			"dop"                  => $other[14] == 'yes',
			"dopName"              => $other[15],
			"comment"              => $other[16] == 'yes',
			"contractor"           => $other[17] == 'yes',
			"planByClosed"         => $other[18] == 'yes',
			"taskControl"          => (int)$other[19],
			"taskControlClientAdd" => $other[20] == 'yes',
			"woNDS"                => $other[21] == 'yes',
			"dealByContact"        => $other[22] == 'yes',
			"addClientWDeal"       => $other[23] == 'yes',
			"changeDealPeriod"     => $other[24],
			"dealStepDefault"      => $other[25],
			"dealPeriodDefault"    => $other[26] != '' && $other[26] != 'no' ? $other[26] : 14,
			"changeDealComment"    => $other[27] == 'yes',
			"changeUserComment"    => $other[28] == 'yes',
			"ndsInOut"             => $other[29] == 'yes',
			"saledProduct"         => $other[30] == 'yes',
			"guidesEdit"           => $other[31] == 'yes',
			"taskEditTime"         => $other[32],
			"taskControlInHealth"  => $other[33] == 'yes',
			"artikulInInvoice"     => $other[34] == 'yes',
			"artikulInAkt"         => $other[35] == 'yes',
			"mailerMsgUnion"       => $other[36] == 'yes',
			"stepControlInHealth"  => $other[37] == 'yes',
			"budjetDayIsNow"       => $other[38],
			"aktTempService"       => (!isset( $other[39] ) || $other[39] == 'no') ? 'akt_full.tpl' : $other[39],
			"invoiceTempService"   => (!isset( $other[40] ) || $other[40] == 'no') ? 'invoice.tpl' : $other[40],
			"aktTemp"              => (!isset( $other[41] ) || $other[41] == 'no') ? 'akt_full.tpl' : $other[41],
			"invoiceTemp"          => (!isset( $other[42] ) || $other[42] == 'no') ? 'invoice.tpl' : $other[42],
		];

		file_put_contents( $rootpath."/cash/".$fpath."otherSettings.json", json_encode( $otherSettings ) );

	}
	else {

		$otherSettings = json_decode( file_get_contents( $rootpath."/cash/".$fpath."otherSettings.json" ), true );

	}

	if ( isset( $params['name'] ) ) {

		return (string)$otherSettings[ $params['name'] ];

	}

	return $otherSettings;

}

/**
 * Расчет маржи по сделке с учетом расходов
 *
 * @param $did
 *
 * @return float|int
 * @category Core
 * @package  Func
 */
function getMargaSum($did) {

	$identity = $GLOBALS['identity'];
	$sqlname  = $GLOBALS['sqlname'];
	$db       = $GLOBALS['db'];
	$marga    = 0;

	$calculate = $db -> getOne( "SELECT calculate FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );

	if ( $calculate != 'yes' ) {

		$sum = $sum_in = 0;

		//найдем сумму по спецификации, если она включена
		$result1 = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' AND identity = '$identity' ORDER BY spid" );
		while ($data = $db -> fetch( $result1 )) {

			$nds    = 1;
			$sum    += $data['kol'] * $data['price'] / $nds * $data['dop'];
			$sum_in += $data['price_in'] / $nds * $data['kol'] * $data['dop'];

		}
		$marga = $sum - $sum_in;

	}
	else {

		//или сумму, указанную в сделке
		$res   = $db -> getRow( "SELECT * FROM {$sqlname}dogovor WHERE did = '$did' AND identity = '$identity'" );
		$sum   = $res["kol"];
		$marga = $res["marga"];

	}

	//если маржа не указана
	if ( $marga == 0 ) {
		$marga = $sum;
	}

	//найдем сумму поставщиков и партнеров
	$sum_prov = $db -> getOne( "SELECT SUM(summa) FROM {$sqlname}dogprovider WHERE did = '$did' AND identity = '$identity'" );
	$marga    -= $sum_prov;

	return $marga;

}