<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

/**
 * Класс для работы с UIDs - параметрами внешних систем
 *
 * Class UIDs
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class UIDs {

	/**
	 * Данные по позиции прайса
	 *
	 * @param array $params - параметры запроса
	 *                      - id    - id записи
	 *                      - name  - имя параметра
	 *                      - value - значение параметра
	 *                      - lid   - id заявки
	 *                      - eid   - id обращения
	 *                      - clid  - id клиента
	 *                      - did   - id сделки
	 *
	 * @return array
	 * good result
	 *         - [result] = Success
	 *         - [data] = array
	 *
	 * error result
	 *         - [result] = Error
	 *         - [error] = Запись не найдена
	 *
	 */
	public static function info(array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$sort = '';

		if ( isset( $params['id'] ) && (int)$params['id'] > 0 )
			$sort .= "id = '$params[id]' AND";

		elseif ( isset( $params['name'] ) && $params['name'] != '' )
			$sort .= "name = '$params[name]' AND";

		elseif ( isset( $params['value'] ) && $params['value'] != '' )
			$sort .= "value = '$params[value]' AND";

		elseif ( isset( $params['lid'] ) && (int)$params['lid'] > 0 )
			$sort .= "lid = '$params[lid]' AND";

		elseif ( isset( $params['eid'] ) && (int)$params['eid'] > 0 )
			$sort .= "eid = '$params[eid]' AND";

		elseif ( isset( $params['clid'] ) && (int)$params['clid'] > 0 )
			$sort .= "clid = '$params[clid]' AND";

		elseif ( isset( $params['did'] ) && (int)$params['did'] > 0 )
			$sort .= "did = '$params[did]' AND";

		if ( $sort != '' ) {

			$field_types = db_columns_types( "{$sqlname}uids" );

			//print_r($field_types);

			$uids  = [];
			$ruids = $db -> getAll( "SELECT * FROM {$sqlname}uids WHERE $sort identity = '$identity'" );
			foreach ( $ruids as $i => $uid ) {

				foreach ( $uid as $k => $v ) {

					if ( !is_numeric( $k ) && $k != 'identity' && $k != 'id' && $k != 'lid' )
						$uids[ $i ][ $k ] = $field_types[ $k ] == 'int' ? (int)$v : $v;

				}

			}

			$response = [
				"result" => "Success",
				"data"   => $uids
			];

		}
		else {

			$response = [
				'result' => 'Error',
				'error'  => "Не указаны параметры"
			];

		}

		return $response;

	}

	/**
	 * Добавление имени параметра ID внешней системы
	 *
	 * @param $name
	 * @return bool
	 */
	public static function uidAdd($name): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$name = str_replace( " ", "_", $name );

		$s = $db -> getRow( "SELECT * FROM ".$sqlname."customsettings WHERE tip = 'uids' AND identity = '$identity'" );
		if ( (int)$s['id'] == 0 ) {

			$uids[] = $name;

			$a = [
				"tip"      => "uids",
				"params"   => json_encode( $uids ),
				"identity" => $identity
			];
			$db -> query( "INSERT INTO  ".$sqlname."customsettings SET ?u", $a );

		}
		else {

			$uids = json_decode( $s['params'], true );

			$uids[] = $name;

			$uids = array_values( $uids );

			$a = [
				"params" => json_encode( $uids )
			];
			$db -> query( "UPDATE ".$sqlname."customsettings SET ?u WHERE id = '$s[id]'", $a );

		}

		return true;

	}

	/**
	 * Удаление имени параметра ID внешней системы
	 *
	 * @param $name
	 * @return bool
	 */
	public static function uidDelete($name): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$s    = $db -> getRow( "SELECT * FROM ".$sqlname."customsettings WHERE tip = 'uids' AND identity = '$identity'" );
		$uids = json_decode( $s['params'], true );

		$key = array_search( $name, $uids );
		unset( $uids[ $key ] );

		$uids = array_values( $uids );

		$a = [
			"params" => json_encode( $uids )
		];
		$db -> query( "UPDATE ".$sqlname."customsettings SET ?u WHERE id = '$s[id]'", $a );

		return true;

	}

	/**
	 * Манипуляции с внешними id
	 *
	 * @param string|null $name
	 * @param string|null $value
	 * @param array|null  $params
	 * @return array
	 */
	public static function edit(string $name = NULL, string $value = NULL, array $params = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ( $name != '' && $value != '' ) {

			//проверяем наличие записи в базе
			$id = (int)$db -> getOne( "SELECT id FROM {$sqlname}uids WHERE name = '$name' AND value = '$value' AND identity = '$identity'" ) + 0;

			if ( $id == 0 ) {

				$data = [
					"name"     => $name,
					"value"    => $value,
					"lid"      => $params['lid'],
					"eid"      => $params['eid'],
					"clid"     => $params['clid'],
					"did"      => $params['did'],
					"identity" => $identity
				];

				$db -> query( "INSERT INTO {$sqlname}uids SET ?u", arrayNullClean( $data ) );
				$id = $db -> insertId();

				$message = 'Добавлена запись';

			}
			else {

				$data = [
					"lid"  => $params['lid'],
					"eid"  => $params['eid'],
					"clid" => $params['clid'],
					"did"  => $params['did']
				];
				$db -> query( "UPDATE {$sqlname}uids SET ?u WHERE name = '$name' AND value = '$value' AND identity = '$identity'", arrayNullClean( $data ) );

				$message = 'Обновлена запись';

			}

			$result = [
				"result"  => "ok",
				"id"      => $id,
				"message" => $message
			];

		}
		else
			$result = [
				"result"  => "error",
				"message" => "Не указан Параметр и его Значение"
			];

		return $result;

	}

	/**
	 * Добавление записи внешнего uid
	 *
	 * @param array $params
	 *             - int lid - id заявки
	 *             - int eid - id обращения
	 *             - int clid - id клиента
	 *             - int did - id сделки
	 *             - array uids = [["name1" => "value1"],["name2" => "value2"]]
	 */
	public static function add(array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$ruid = [];
		$arg  = [];

		if ( isset( $params['lid'] ) )
			$arg["lid"] = $params['lid'];

		if ( isset( $params['eid'] ) )
			$arg["eid"] = $params['eid'];

		if ( isset( $params['clid'] ) )
			$arg["clid"] = $params['clid'];

		if ( isset( $params['did'] ) )
			$arg["did"] = $params['did'];

		foreach ( $params['uids'] as $key => $value ) {

			//self ::edit( $key, $value, $arg );

			$arg['name']     = $key;
			$arg['value']    = $value;
			$arg['identity'] = $identity;

			$db -> query( "INSERT INTO {$sqlname}uids SET ?u", arrayNullClean( $arg ) );
			$ruid[] = $db -> insertId();

		}

		return $ruid;

	}

}