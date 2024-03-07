<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Salesman;

use Exception;

/**
 * Класс для управления Контрольными точками
 *
 * Class ControlPoints
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 *
 * Example
 *
 * ```php
 * $rez  = new Salesman\ControlPoints();
 * $data = $rez -> info($id);
 * ```
 */
class ControlPoints {

	/**
	 * Абсолютный путь
	 *
	 * @var string
	 */
	public $rootpath;

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$params = $this -> params;

		$this -> rootpath = dirname( __DIR__, 2 );
		$this -> identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set( $this -> tmzone );

	}

	/**
	 * Массив базовых Контрольных точек с ключем = ccid
	 *
	 * @return array
	 */
	public function points(): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		$response = [];

		$points = $db -> getAll( "SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER BY corder" );

		foreach ( $points as $item ) {

			$response[ $item['ccid'] ] = [
				"title" => $item['title'],
				"step"  => $item['dstep'],
				"users" => yexplode( ",", $item['users'] ),
				"roles" => yexplode( ",", $item['role'] )
			];

		}

		return $response;

	}

	/**
	 * Информация о базовой контрольной точке
	 *
	 * @param int $id
	 * @return array
	 */
	public function pointinfo(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ( (int)$id > 0 ) {

			$response       = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE ccid = '$id' and identity = '$identity'" );
			$response['id'] = $response['ccid'];

			if ( !empty( $response ) ) {

				foreach ( $response as $k => $item ) {
					if ( is_int( $k ) || $k == 'identity' ) {
						unset( $response[ $k ] );
					}
				}

			}
			else {

				$response = [
					'result' => 'Error',
					'error'  => [
						'code' => '404',
						'text' => "Запись не найдена"
					]
				];

			}

		}
		else {

			$response = [
				'result' => 'Error',
				'error'  => [
					'code' => '405',
					'text' => "Отсутствуют параметры - id записи"
				]
			];

		}

		return $response;

	}

	/**
	 * Список Контрольных точек по сделке
	 *
	 * @param int  $did
	 * @param bool $pointsonly
	 * @return array
	 */
	public function pointsbydeal(int $did = 0, bool $pointsonly = false): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$list = [];

		$res = $db -> query( "SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER BY corder" );
		while ($dataa = $db -> fetch( $res )) {

			$complects = $db -> getAll( "SELECT * FROM {$sqlname}complect WHERE ccid = '".$dataa['ccid']."' and did = '$did' and identity = '$identity' ORDER BY id" );

			//print_r($complects);

			if( !empty($complects) ) {

				foreach ( $complects as $complect ) {

					if ( !$pointsonly ) {

						$list[ $dataa['ccid'] ] = [
							'datePlan'    => ($complect['data_plan'] != '0000-00-00') ? $complect['data_plan'] : NULL,
							'datFact'     => ($complect['data_fact'] != '0000-00-00') ? $complect['data_fact'] : NULL,
							'do'          => $complect['doit'] == 'yes',
							'pointAccess' => get_cpaccesse( $dataa['ccid'] ) == 'yes' || $complect['iduser'] == $iduser1,
							'dealAccess'  => get_accesse( 0, 0, (int)$did ) == "yes",
							'step'        => $dataa['dstep'],
							'stepName'    => current_dogstepname( $dataa['dstep'] )
						];

					}
					else {

						$list[] = $dataa['id'];

					}

				}

			}

		}

		return $list;

	}

	/**
	 * Информация о Контрольной точке
	 *
	 * @param int $id
	 * @return array
	 */
	public function info(int $id = 0): array {

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;

		if ( (int)$id > 0 ) {

			$response = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE id = '$id' and identity = '$identity'" );

			$result            = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE ccid = '$response[ccid]' and identity = '$identity'" );
			$response['title'] = $result["title"];
			$response['step']  = $result["dstep"];

			$response['doit'] = $response['doit'] == 'yes';

			if ( !empty( $response ) ) {

				foreach ( $response as $k => $item ) {
					if ( is_int( $k ) || $k == 'identity' ) {
						unset( $response[ $k ] );
					}
				}

			}
			else {

				$response = [
					'result' => 'Error',
					'error'  => [
						'code' => '404',
						'text' => "Запись не найдена"
					]
				];

			}

		}
		else {

			$response = [
				'result' => 'Error',
				'error'  => [
					'code' => '405',
					'text' => "Отсутствуют параметры - id записи"
				]
			];

		}

		return $response;

	}

	/**
	 * Добавление/Редактирование Контрольной точки
	 *
	 * @param int   $id
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	public function edit(int $id = 0, array $params = []): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$msg = $error = [];

		$post = $params;

		if ( $id == 0 ) {
			$params = $hooks -> apply_filters( "cp_addfilter", $params );
		}
		else{
			$params = $hooks -> apply_filters( "cp_editfilter", $params );
		}

		//Данные по комплектности сделки
		$cpoint['did']       = (int)$params['did'];
		$cpoint['data_plan'] = $params['data_plan'] ?? $params['datum'];
		$cpoint['ccid']      = (int)$params['ccid'];
		$cpoint['iduser']    = (int)$params['iduser'];

		if ( $id == 0 ) {

			//Если указана плановая дата и такой записи нет, то добавляем
			if ( $cpoint['ccid'] > 0 ) {

				try {

					$cpoint['identity'] = $identity;

					$db -> query( "INSERT INTO {$sqlname}complect SET ?u", $cpoint );
					$id = $db -> insertId();

					if ( $hooks ) {
						$hooks -> do_action( "cp_add", $post, $cpoint );
					}

					$title = $db -> getOne( "SELECT title FROM {$sqlname}complect_cat WHERE ccid = '$cpoint[ccid]' and identity = '$identity'" );

					//проверим доступ к сделке у сотрудника
					$accsess = getUserAccesse( $cpoint['iduser'], ["did" => $cpoint['did']] );
					if ( $accsess != 'yes' ) {

						$Deal = new Deal();
						$Deal -> changeDostup( (int)$cpoint['did'], [
							"dostup" => [
								[
									"iduser" => $cpoint['iduser'],
									"notify" => "on"
								]
							]
						] );

						//print_r( $c );
						//print_r( $d );

					}

					$msg[] = "КТ: добавлена сотрудником ".current_user( $iduser1 ).". **$title**";

					//Внесем запись в историю активностей
					addHistorty( [
						"iduser"   => $iduser1,
						"did"      => $cpoint['did'],
						"datum"    => current_datumtime(),
						"des"      => yimplode( "\n", $msg ),
						"tip"      => 'СобытиеCRM',
						"identity" => $identity
					] );

					// отправляем только если исполнитель не является автором
					if((int)$cpoint['iduser'] != $iduser1) {

						/**
						 * Уведомления
						 */
						$arg = [
							"id"         => $id,
							"title"      => $title,
							"did"        => (int)$cpoint['did'],
							"deal"       => getDogData( (int)$cpoint['did'], 'title' ),
							"datum_plan" => $cpoint['data_plan'],
							"iduser"     => (int)$cpoint['iduser'],
							"autor"      => (int)$iduser1,
							"notice"     => 'yes',
						];
						Notify ::fire( "cp.add", $iduser1, $arg );

					}

				}
				catch ( Exception $e ) {

					$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}
			else {

				$error[] = 'Ошибка: Не выбрана контрольная точка';

			}

		}
		else {

			$db -> query( "UPDATE {$sqlname}complect SET ?u WHERE id = '$id' AND identity = '$identity'", [
				"data_plan" => $cpoint['data_plan'],
				"iduser"    => $cpoint['iduser']
			] );

			if ( $hooks ) {
				$hooks -> do_action( "cp_edit", $post, $cpoint );
			}

			//проверим доступ к сделке у сотрудника
			$accsess = getUserAccesse( $cpoint['iduser'], ["did" => $cpoint['did']] );
			if ( $accsess != 'yes' ) {

				$Deal = new Deal();
				$r    = $Deal -> changeDostup( (int)$cpoint['did'], [
					"dostup" => [
						[
							"iduser" => $cpoint['iduser'],
							"notify" => "on"
						]
					]
				] );

				if ( $r['result'] == 'Ok' ) {
					$msg[] = 'Сотруднику предоставлен доступ к карточке';
				}

			}

			$ccid  = $db -> getOne( "SELECT ccid FROM {$sqlname}complect WHERE id = '$id' and identity = '$identity'" );
			$title = $db -> getOne( "SELECT title FROM {$sqlname}complect_cat WHERE ccid = '$ccid' and identity = '$identity'" );

			$msg[] = "КТ: обновлена сотрудником ".current_user( $iduser1 ).". **$title**";

			/**
			 * Уведомления
			 */
			$arg = [
				"id"         => $id,
				"title"      => $title,
				"did"        => (int)$cpoint['did'],
				"deal"       => getDogData( (int)$cpoint['did'], 'title' ),
				"datum_plan" => $cpoint['data_plan'],
				"iduser"     => (int)$cpoint['iduser'],
				"autor"      => (int)$iduser1,
				"notice"     => 'yes',
			];
			Notify ::fire( "cp.edit", $iduser1, $arg );

		}

		return [
			"id"      => $id,
			"did"     => $cpoint['did'],
			"result"  => !empty( $error ) ? "error" : "ok",
			"message" => yimplode( "\n", $msg ),
			"error"   => !empty( $error ) ? yimplode( "\n", $error ) : NULL
		];

	}

	/**
	 * Удаление Контрольной точки
	 *
	 * @param int $id
	 * @return array
	 */
	public function delete(int $id = 0): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$msg = $error = [];
		$did = 0;

		//Если указана плановая дата и такой записи нет, то добавляем
		if ( $id ) {

			$resultc = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE id = '$id' and identity = '$identity'" );
			$ccid    = $resultc["ccid"];
			$did     = $resultc["did"];

			$title = $db -> getOne( "SELECT title FROM {$sqlname}complect_cat WHERE ccid = '$ccid' and identity = '$identity'" );

			try {

				$db -> query( "DELETE FROM {$sqlname}complect WHERE id = '$id' and identity = '$identity'" );

				$hooks -> do_action( "cp_delete", $id );

				$msg[] = 'Выполнено';

				//запись в историю активности
				addHistorty( [
					"iduser"   => $iduser1,
					"did"      => $did,
					"datum"    => current_datumtime(),
					"des"      => "КТ: Удалена. **$title**",
					"tip"      => "СобытиеCRM",
					"identity" => $identity
				] );

			}
			catch ( Exception $e ) {

				$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {
			$error[] = 'Ошибка: Не выбрана контрольная точка';
		}

		return [
			"id"      => $id,
			"did"     => $did,
			"message" => yimplode( "\n", $msg ),
			"error"   => !empty( $error ) ? yimplode( "\n", $error ) : NULL
		];

	}

	/**
	 * Выполнение Контрольной точки
	 *
	 * @param int         $id
	 * @param string|null $date
	 * @return array
	 */
	public function doit(int $id = 0, string $date = NULL): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$msg = $error = [];

		$resultct = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE id = '$id' and identity = '$identity'" );
		$ccid     = $resultct["ccid"];
		$did      = $resultct["did"];
		$autor    = $resultct["autor"];

		$resultcc = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE ccid = '$ccid' and identity = '$identity'" );
		$ctitle   = $resultcc["title"];
		$dstep    = $resultcc["dstep"];//связанный этап сделки

		$cstep = getPrevNextStep( getDogData( $did, "idcategory" ) );//текущий этап сделки
		$nstep = getPrevNextStep( $cstep['id'], 'next' );//этап сделки, на который будем её переводить
		$pstep = getPrevNextStep( $dstep, 'prev' );//предыдущий этап сделки относительно связанного

		$nstepID = $nstep['id'];
		$nstep   = (int)$nstep['title'];
		$pstep   = (int)$pstep['title'];

		//изменять будем только если КТ не привязана к этапу или текущий этап является предыдущим связанному
		if ( $dstep == 0 || ($dstep > 0 && $cstep['title'] >= $pstep) ) {

			try {

				$db -> query( "UPDATE {$sqlname}complect SET ?u WHERE id = '$id' and identity = '$identity'", $arg = [
					"data_fact" => !empty($date) ? $date : current_datum(),
					"doit"      => 'yes'
				] );

				if ( $hooks ) {
					$hooks -> do_action( "cp_doit", $id, $arg );
				}

				$msg[] = "КТ: Поставлена отметка о выполнении сотрудником ".current_user( $iduser1 ).". **$ctitle** ";

				/**
				 * Уведомления
				 */
				$xarg = [
					"id"         => $id,
					"title"      => $ctitle,
					"did"        => (int)$did,
					"deal"       => getDogData( (int)$did, 'title' ),
					"data_fact"  => $arg['data_fact'],
					"iduser"     => (int)$autor,
					"autor"      => (int)$iduser1,
					"notice"     => 'yes',
				];
				Notify ::fire( "cp.doit", $iduser1, $xarg );

				//-Начало//-Изменим этап сделки, если он указан в типе КТ и текущий этап ниже связанного с КТ
				//делаем только для связанных КТ
				//если следующий этап равен КТ ??м.б. не надо проверять?
				if ( ($dstep > 0) && $nstepID == $dstep ) {

					$params = [
						"did"         => $did,
						"description" => "Этап сделки изменен на $nstep%. Причина - выполнение контрольной точки **$ctitle**. Предыдущий этап $cstep[title]%",
						"step"        => $nstepID,
						"iduser"      => $iduser1
					];

					$deal = new Deal();
					$deal -> changestep( $did, $params );

				}
				//-Конец//

				//запись в историю активности
				if ( $dstep < 1 ) {

					addHistorty( [
						"iduser"   => $iduser1,
						"did"      => $did,
						"datum"    => current_datum(),
						"des"      => yimplode( "\n", $msg ),
						"tip"      => "СобытиеCRM",
						"identity" => $identity
					] );

				}

			}
			catch ( Exception $e ) {

				$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {
			$error[] = "Текущий этап сделки меньше <b>$pstep%</b>. Сначала перейдите на этап <b>$pstep%</b>";
		}

		return [
			"id"      => $id,
			"did"     => $did,
			"message" => yimplode( "\n", $msg ),
			"error"   => !empty( $error ) ? yimplode( "\n", $error ) : NULL
		];

	}

	/**
	 * Отмена выполнения Контрольной точки
	 *
	 * @param int $id
	 * @return array
	 */
	public function undoit(int $id = 0): array {

		global $hooks;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$msg = $error = [];
		$did = 0;

		if ( $id > 0 ) {

			$resultc = $db -> getRow( "SELECT * FROM {$sqlname}complect WHERE id='$id' and identity = '$identity'" );
			$ccid    = $resultc["ccid"];
			$did     = $resultc["did"];

			$resultcc = $db -> getRow( "SELECT * FROM {$sqlname}complect_cat WHERE ccid='$ccid' and identity = '$identity'" );
			$title    = $resultcc["title"];
			$dstep    = $resultcc["dstep"];//связанный этап сделки

			try {

				$db -> query( "UPDATE {$sqlname}complect SET ?u WHERE id = '$id' and identity = '$identity'", [
					"doit" => 'no',
					"data_fact" => NULL
				] );

				if ( $hooks ) {
					$hooks -> do_action( "cp_undoit", $id );
				}

				$msg[] = "КТ: восстановлена сотрудником ".current_user( $iduser1 ).". **$title**";

				//изменим этап на предыдущий, если КТ связана с этапом
				if ( $dstep > 0 ) {

					$current     = getDogData( $did, 'idcategory' );
					$newStep     = getPrevNextStep( $current, 'prev' );
					$curStepName = getPrevNextStep( $current );

					//$mes.= "<br>Предыдущий этап: ".$curStepName['title']."%. Восстановлен этап: ".$newStep['title']."%.";

					$params = [
						"did"         => $did,
						"description" => yimplode(";", $msg),
						"step"        => $newStep['id'],
						"iduser"      => $iduser1
					];

					$deal = new Deal();
					$info = $deal -> changestep( $did, $params );

					if ( $info['result'] != 'Error' ) {

						//удалим из лога этапов запись перехода на тек. этап
						$db -> query( "delete from {$sqlname}steplog where did = '$did' and step = '$current' and identity = '$identity'" );

					}

				}

				//запись в историю активности
				if ( $dstep < 1 ) {
					addHistorty( [
						"iduser"   => $iduser1,
						"did"      => $did,
						"datum"    => current_datumtime(),
						"des"      => yimplode( "\n", $msg ),
						"tip"      => "СобытиеCRM",
						"identity" => $identity
					] );
				}

			}
			catch ( Exception $e ) {

				$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

			}

		}
		else {

			$error[] = 'Ошибка: Не выбрана контрольная точка';

		}

		return [
			"id"      => $id,
			"did"     => $did,
			"message" => yimplode( "\n", $msg ),
			"error"   => !empty( $error ) ? yimplode( "\n", $error ) : NULL
		];

	}

	/**
	 * Автоматическое добавление Контрольной точки
	 *
	 * @param int $did
	 * @param int $offset - количество дней от текущей даты ( для плановой даты по КТ )
	 * @return array
	 * @throws Exception
	 */
	public function autoAdd(int $did = 0, int $offset = 5): array {

		/**
		 * Задача - найти следующий этап для сделки, затем найти для этого этапа Контрольную точку и установить её
		 */
		$cpid = 0;

		/**
		 * Ответственный за сделку
		 */
		$iduser = getDogData( $did, "iduser" );

		// получаем воронку
		$mFunnel = getMultiStepList( ["did" => $did] );

		// id следующего этапа сделки
		$next = $mFunnel['next']['id'];

		// получаем список Контрольных точек
		$points = $this -> points();
		foreach ( $points as $id => $point ) {

			if ( $point['step'] == $next ) {

				$cpid = $id;

			}

		}

		// проверим наличие Контрольной точки в сделке
		$points = $this -> pointsbydeal($did, true);

		if(!in_array($cpid, $points)) {

			// добавляем Контрольную точку
			return $this -> edit( 0, [
				"did"       => $did,
				"ccid"      => $cpid,
				"data_plan" => current_datum( -$offset ),
				"iduser"    => $iduser
			] );

		}

		return [
			"result"   => "error",
			"message"  => "Контрольная точка для следующего этапа уже добавлена"
		];

	}

}