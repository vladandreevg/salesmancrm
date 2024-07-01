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

use EasyPeasyICS;
use event;
use Exception;
use SafeMySQL;

/**
 * Класс для работы с Напоминаниями
 *
 * Class Todo
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Todo {

	/**
	 * @var array
	 */
	public $otherSettings;

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
	 * @var false|string
	 */
	private $rootpath;

	/**
	 * Описание полей
	 */
	public const KEYS = [
		"tid"      => "Идентификатор",
		"maintid"  => "Идентификатор основного напоминания (при групповых напоминаниях)",
		"title"    => "Тема",
		"des"      => "Описание",
		"datum"    => "Дата",
		"totime"   => "Время",
		"tip"      => "Тип",
		"active"   => "Признак выполнения",
		"autor"    => "Автор",
		"iduser"   => "Исполнитель",
		"priority" => "Приоритет",
		"speed"    => "Срочность",
		"alert"    => "Уведомлять",
		"pid"      => "Контакты",
		"clid"     => "Клиент",
		"did"      => "Сделка",
		"readonly" => "Только для чтения",
		"day"      => "На весь день",
		"status"   => "Статус выполнения",
	];

	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$this -> rootpath = $rootpath;
		$this -> identity = $GLOBALS['identity'];
		$this -> iduser1  = $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> tmzone   = $GLOBALS['tmzone'];

		$this -> db = new SafeMySQL( $this -> opts );

		if ( file_exists( $rootpath."/cash/".$this -> fpath."otherSettings.json" ) ) {

			$this -> otherSettings = json_decode( file_get_contents( $rootpath."/cash/".$this -> fpath."otherSettings.json" ), true );

		}
		else {

			$other                 = explode( ";", $this -> db -> getOne( "SELECT other FROM ".$this -> sqlname."settings WHERE id = '".$this -> identity."'" ) );
			$this -> otherSettings = [
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

		}

	}

	/**
	 * Получение сырой информации по напоминанию
	 *
	 * @param int $id - id напоминания
	 *
	 * @return array - массив результата
	 * - int **tid** - Идентификатор
	 * - int **maintid** - Идентификатор основного напоминания (при групповых напоминаниях)
	 * - str **title** - Тема
	 * - str **des** - Описание
	 * - date **datum** - Дата
	 * - time **totime** - Время
	 * - str **tip** - Тип
	 * - str **active** - Признак выполнения - Уведомлять (yes|no)
	 * - int **autor** - Автор
	 * - int **iduser** - Исполнитель
	 * - int **priority** - Приоритет (0-важно, 1-обычно, 2-не важно)
	 * - int **speed** - Срочность (0-срочно, 1-обычно, 2-не срочно)
	 * - str **alert** - Уведомлять (yes|no)
	 * - str **cid** - Связанная активность
	 * - str **pid** - Контакты (разделитель ;)
	 * - int **clid** - id Клиента
	 * - str **client** - Клиент
	 * - int **did** - id Сделка
	 * - int **deal** - Сделка
	 * - str **readonly** - Только для чтения (yes|no)
	 * - str **day** - На весь день" (yes|no)
	 * - array **child** - Массив связанных напоминаний (для групповых)
	 * - array **users** - Массив связанных исполнителей (для групповых)
	 */
	public static function info(int $id = 0): array {

		$rootpath = dirname( __DIR__ );

		require_once $rootpath."/config.php";
		require_once $rootpath."/dbconnector.php";
		require_once $rootpath."/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$id = (int)$id;

		if ( $id != '' ) {

			$response           = $db -> getRow( "SELECT * FROM ".$sqlname."tasks WHERE tid = '$id' and identity = '$identity'" );
			$response['totime'] = getTime( (string)$response['totime'] );
			$response['pid']    = yexplode( ";", (string)$response['pid'] );

			$users = $tids = [];
			$res   = $db -> getAll( "SELECT iduser, tid FROM ".$sqlname."tasks WHERE maintid = '$id' and identity = '$identity'" );
			foreach ( $res as $data ) {

				$users[] = $data['iduser'];//выбранные сотрудники
				$tids[]  = $data['tid'];//связанные id напоминаний ? зачем, пока не знаю

			}

			if ( (int)$response['clid'] > 0 ) {
				$response['client'] = current_client( (int)$response['clid'] );
			}

			if ( (int)$response['did'] > 0 ) {
				$response['deal'] = current_dogovor( (int)$response['did'] );
			}

			$response['child'] = $tids;
			$response['users'] = $users;

			// очищаем от нумерных ключей
			foreach ( array_keys( $response ) as $key ) {
				if ( is_numeric( $key ) ) {
					unset( $response[ $key ] );
				}
			}

			if ( (int)$response['tid'] < 1 ) {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание с указанным id не найдено в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id напоминания";

		}

		return $response;

	}

	/**
	 * Добавление Напоминания
	 *
	 * @param int   $iduser - сотрудник, которому ставим напоминание
	 * @param array $params - параметры
	 *                      см. KEYS
	 *                      + users - массив идентификаторов сотрудников, для группового напоминания
	 *
	 * @return array
	 * good result
	 *         - result = Success
	 *         - id = id записи
	 *         - text = сообщения
	 *         - notice = предупреждения
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 * ```
	 * 405 - Отсутствуют параметры - id автора напоминания
	 * ```
	 *
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function add(int $iduser = 0, array $params = []): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		//print_r($params);

		// можно отключить отправку по email
		if ( !isset( $params['notify'] ) ) {
			$params['notify'] = true;
		}

		if ( (int)$iduser > 0 ) {

			//входные данные
			$task['title']    = untag( $params['title'] );
			$task['des']      = untag( $params['des'] );
			$task['clid']     = (int)$params['clid'];
			$task['did']      = (int)$params['did'];
			$task['pid']      = is_array( $params["pid"] ) ? yimplode( ";", $params["pid"] ) : $params["pid"];
			$task['datum']    = untag( $params['datum'] );
			$task['totime']   = untag( $params['totime'] );
			$task['tip']      = untag( $params['tip'] );
			$task['readonly'] = (!empty( $params['readonly'] )) ? untag( $params['readonly'] ) : 'no';
			$task['alert']    = (!empty( $params['alert'] )) ? untag( $params['alert'] ) : 'no';
			$task['priority'] = ($params['priority'] != '') ? (int)$params['priority'] : 0;
			$task['speed']    = ($params['speed'] != '') ? (int)$params['speed'] : 0;
			$task['created']  = current_datumtime();
			$task['active']   = 'yes';
			$task['day']      = untag( $params['day'] );

			$users = (array)$params['users'];

			$task['autor'] = (int)$params['autor'] == 0 ? $iduser1 : $params['autor'];

			if ( count( $users ) == 1 ) {
				$iduser = $users[0];
			}
			//else $iduser = $iduser1;

			//При отсутствии контакта, устанавливаем контактом напоминания основной контакт клиента
			$clinfo      = get_client_info( (int)$task['clid'], "yes" );
			$task['pid'] = ($task["pid"] != '') ? $task["pid"] : $clinfo['pid'];

			$mess     = [];
			$err      = [];
			$mailpack = [];
			$tid      = 0;

			//включена ли отправка уведомлений
			$mailme = $db -> getOne( "select mailme from ".$sqlname."settings WHERE id = '$identity'" );

			//если пользователь делает напоминание только себе
			if ( empty( $users ) ) {

				try {

					if ( $iduser == $task['autor'] )
						$task['autor'] = 0;

					$task['identity'] = $identity;
					$task['iduser']   = $iduser;

					$task1 = $task;

					$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", arrayNullClean( $task ) );
					$tid = $db -> insertId();

					$mess[] = "Добавлено напоминание";

					$mailpack[] = $this -> taskTemplate( (int)$tid, 'add' );

				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			//если напоминание для одного сотрудника
			elseif ( count( $users ) == 1 ) {

				try {

					if ( $iduser == $task['autor'] ) {
						$task['autor'] = 0;
					}

					$task['identity'] = $identity;
					$task['iduser']   = $iduser;

					$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", arrayNullClean( $task ) );
					$tid = $db -> insertId();

					$mess[] = "Добавлено напоминание";

					$mailpack[] = $this -> taskTemplate( (int)$tid, 'add' );

				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			//если напоминания делается на нескольких сотрудников
			elseif ( count( $users ) > 1 ) {

				//Создадим основное напоминание, для автора
				try {

					$task['identity'] = $identity;
					$task['iduser']   = $iduser;

					$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", arrayNullClean( $task ) );
					$maintid = $db -> insertId();

					$mess[] = "Добавлено напоминание";

					foreach ( $users as $user ) {

						$task['maintid'] = $maintid;

						//если текущий сотрудник в списке выбранных, то ему не создаем напоминание
						if ( $user != $iduser1 ) {

							$task['iduser'] = $user;

							//print_r($task);

							try {

								$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", arrayNullClean( $task ) );
								$subtid = $db -> insertId();

								$mess[] = "Запись для сотрудника ".current_user( $user )." успешно внесена";

								if ( $mailme == 'yes' )
									$mailpack[] = $this -> taskTemplate( $subtid, 'add' );

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}

				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			//print_r($mailpack);

			foreach ( $mailpack as $pack ) {

				//print "notify = ".$params['notify']."\n";

				if ( $pack['subscription'] == 'on' && $pack['to'] != '' && $pack['iduser'] != $iduser1 && $params['notify'] ) {

					//$mailsender_rez = mailer( $pack['to'], $pack['toname'], $pack['from'], $pack['fromname'], $pack['theme'], htmlspecialchars_decode( $pack['html'] ) );
					$mailsender_rez = mailto( [
						$pack['to'],
						$pack['toname'],
						$pack['from'],
						$pack['fromname'],
						$pack['theme'],
						htmlspecialchars_decode( $pack['html'] )
					] );

					if ( $mailsender_rez == '' ) {

						$mess[] = 'Отправлено сотруднику '.$pack['toname'];

					}
					else {
						$mess[] = $mailsender_rez;
					}

				}

				if ( $pack['sendcal'] == 'on' && $params['notify'] ) {
					$this -> createCal( (int)$pack['tid'], 'false' );
				}

			}

			foreach ( $mailpack as $pack ) {

				if ( $pack['iduser'] != $iduser1 ) {

					$args = [
						"id"      => $tid,
						"autor"   => $iduser1,
						"iduser"  => $pack['iduser'],
						"title"   => $task['title'],
						"type"    => $task['tip'],
						"content" => $task['des']
					];
					Notify ::fire( 'task.add', $iduser1, $args );

				}

			}

			$args = [
				"id"    => $tid,
				"autor" => $iduser1
			];
			event ::fire( 'task.add', $args );

			$response['result'] = 'Success';
			$response['id']     = $tid;
			$response['text']   = $mess;
			$response['notice'] = $err;
			//$response['params'] = $task1;
			//$response['post'] = $params;

			//print_r($response);

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id автора напоминания";

		}

		return $response;

	}

	/**
	 * Редактирование Напоминания
	 *
	 * @param       $id     - id напоминания
	 * @param array $params - параметры
	 *                      см. KEYS
	 *                      + users - массив идентификаторов сотрудников, для группового напоминания
	 *
	 * @return array
	 * good result
	 *         - result = Success
	 *         - data = id записи
	 *         - text = сообщения
	 *         - notice = предупреждения
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 * ```
	 * 405 - Отсутствуют параметры - id автора напоминания
	 * ```
	 *
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function edit($id, array $params = []): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$id = (int)$id;

		$oldTask = self ::info( $id );

		//Проверяем, включены ли уведомления во всей системе
		$mailme = $db -> getOne( "SELECT mailme FROM ".$sqlname."settings WHERE id = '$identity'" );

		if ( $id > 0 ) {

			//входные данные
			$task['iduser']   = (int)$params['iduser'];
			$task['title']    = untag( $params['title'] );
			$task['des']      = untag( $params['des'] );
			$task['clid']     = (int)$params['clid'] > 0 ? $params['clid'] : NULL;
			$task['did']      = (int)$params['did'] > 0 ? $params['did'] : NULL;
			//$task['pid']      = yimplode( ";", $params["pid"] );
			$task['pid']      = is_array( $params["pid"] ) ? yimplode( ";", $params["pid"] ) : $params["pid"];
			$task['datum']    = untag( $params['datum'] );
			$task['totime']   = untag( $params['totime'] );
			$task['tip']      = untag( $params['tip'] );
			$task['readonly'] = ($params['readonly'] != '') ? $params['readonly'] : 'no';
			$task['alert']    = ($params['alert'] != '') ? $params['alert'] : 'no';
			$task['priority'] = ($params['priority'] != '') ? (int)$params['priority'] : 0;
			$task['speed']    = ($params['speed'] != '') ? (int)$params['speed'] : 0;
			$task['active']   = ($params['active'] != '') ? $params['active'] : 'yes';
			$task['autor']    = (int)$params['autor'];
			$task['day']      = untag( $params['day'] );

			$users = (array)$params['users'];

			$mess = $err = $mailpack = [];

			if ( !empty($users) && $task['iduser'] > 0 ) {
				$users[] = $task['iduser'];
			}
			if ( empty($users) ) {
				$users[] = $iduser1;
			}

			//При отсутствии контакта, устанавливаем контактом напоминания основной контакт клиента
			$clinfo      = get_client_info( $task['clid'], "yes" );
			$task['pid'] = ($task["pid"] != '') ? $task["pid"] : $clinfo['pid'];

			//список дочерних напоминаний, т.е. пользователям
			$userexist = $db -> getCol( "SELECT iduser FROM ".$sqlname."tasks WHERE maintid = '$id' OR (maintid = '0' AND tid = '$id') AND identity = '$identity'" );

			$task['iduser'] = (count( $users ) == 1) ? $users[0] : $iduser1;

			if ( $task['iduser'] == "" ) {
				$task['iduser'] = $iduser1;
			}
			if ( $iduser1 != $task['iduser'] && $task['autor'] == 0 ) {
				$task['autor'] = $iduser1;
			}

			//объединим 2 массива - 1 - те, у кого были напоминания, + те, 2 - которые есть в текущем запросе
			$userf = array_unique( array_merge( $userexist, $users, [$iduser1] ) );
			sort( $userf );

			//print_r($userf);

			//массив пользоватлей, которым отправлено, для измежания дублей
			$sended = [];

			//чтобы можно было очистить Агенду
			$prm        = arrayNullClean( $task );
			$prm['des'] = $task['des'];
			$prm['day'] = $task['day'];

			//обновим напоминание для выбранного пользователя
			if ( count( $users ) == 1 ) {

				try {

					if ( $task['iduser'] == $task['autor'] )
						$task['autor'] = 0;

					$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$id' and identity = '$identity'", $prm );

					//удаляем напоминания всех остальных
					$db -> query( "delete from ".$sqlname."tasks where maintid = '$id' and iduser != '$task[iduser]'" );

					$mess[] = "Напоминание обновлено";

					if ( !in_array( $task['iduser'], $sended ) )
						if ( $mailme == 'yes' )
							$mailpack[] = $this -> taskTemplate( $id, 'edit' );

				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			//напоминания для выбранных сотрудников, если их больше 1
			elseif ( count( $userf ) > 1 && count( $users ) > 0 ) {

				foreach ( $userf as $user ) {

					unset( $task['maintid'], $task['identity'], $task['active'], $task['iduser'] );

					if ( $user != $iduser1 ) {

						$task['iduser'] = $user;

						$subtid = $db -> getOne( "SELECT tid FROM ".$sqlname."tasks WHERE maintid = '$id' and iduser = '$user' and identity = '$identity'" );

						if ( $subtid > 0 && in_array( $user, $userexist ) ) {

							//если такое напоминание уже есть, т.е. напоминание ранее ставилось пользователю и он !остался в списке
							if ( in_array( $user, $users ) ) {

								//обновляем напоминание
								try {

									$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$subtid' and identity = '$identity'", arrayNullClean( $task ) );

									$mess[] = current_user( $user ).": Напоминание обновлено";

									if ( $mailme == 'yes' && !in_array( $user, $sended ) )
										$mailpack[] = $this -> taskTemplate( $subtid, 'edit' );


								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

							//если такое напоминание уже есть, т.е. напоминание ранее ставилось пользователю и он *исключен из списка
							else {

								//удаляем напоминание
								try {

									$db -> query( "DELETE FROM ".$sqlname."tasks WHERE tid = '$subtid'" );
									$mess[] = current_user( $user ).": Напоминание удалено";

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

						}

						//если такого напоминание нет, т.е. напоминание ранее НЕ ставилось пользователю, то добавляем
						elseif ( $subtid < 1 && in_array( $user, $users ) ) {

							$task['maintid']  = $id;
							$task['identity'] = $identity;
							$task['active']   = 'yes';

							//добавляем напоминание
							try {

								$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", arrayNullClean( $task ) );
								$subtid = $db -> insertId();

								$mess[] = current_user( $user ).": Напоминание добавлено";

								if ( $mailme == 'yes' && !in_array( $user, $sended ) )
									$mailpack[] = $this -> taskTemplate( $subtid, 'add' );

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}
					elseif ( $user == $iduser1 ) {

						//обновляем напоминание
						try {

							$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$id' and identity = '$identity'", $task );
							//print $db -> lastQuery();

							$mess[] = current_user( $user ).": Напоминание обновлено";
							if ( $mailme == 'yes' && !in_array( $user, $sended ) )
								$mailpack[] = $this -> taskTemplate( $id, 'edit' );

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}

				}

			}

			//Высылаем обновление
			foreach ( $mailpack as $pack ) {

				if ( $pack['subscription'] == 'on' && $pack['to'] != '' && $pack['iduser'] != $pack['autor'] ) {

					//$mailsender_rez = mailer( $pack['to'], $pack['toname'], $pack['from'], $pack['fromname'], $pack['theme'], htmlspecialchars_decode( $pack['html'] ) );
					$mailsender_rez = mailto( [
						$pack['to'],
						$pack['toname'],
						$pack['from'],
						$pack['fromname'],
						$pack['theme'],
						htmlspecialchars_decode( $pack['html'] )
					] );

					if ( $mailsender_rez == '' ) {

						$mess[] = 'Отправлено сотруднику '.$pack['toname'];

					}
					else $mess[] = $mailsender_rez;

				}
				if ( $pack['sendcal'] == 'on' && $pack['to'] != '' )
					$this -> createCal( $pack['tid'], 'false' );

			}

			foreach ( $mailpack as $pack ) {

				if ( $pack['iduser'] != $iduser1 ) {

					$args = [
						"id"      => $pack['tid'],
						"autor"   => $iduser1,
						"iduser"  => $pack['iduser'],
						"title"   => $task['title'],
						"type"    => $task['tip'],
						"content" => $task['des'],
						"old"     => $oldTask
					];
					Notify ::fire( 'task.edit', $iduser1, $args );

				}

			}

			$args = [
				"id"    => $id,
				"autor" => $task['autor']
			];
			event ::fire( 'task.edit', $args );

			$response['result'] = 'Success';
			$response['data']   = $id;
			$response['text']   = $mess;
			$response['notice'] = $err;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id автора напоминания";

		}

		return $response;

	}

	/**
	 * Обновление даты напоминания
	 *
	 * @param       $id
	 * @param       $newdate
	 * @return mixed
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function editdate($id, $newdate): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$task = $oldTask = self ::info( $id );

		//Проверяем, включены ли уведомления во всей системе
		$mailme = $db -> getOne( "SELECT mailme FROM ".$sqlname."settings WHERE id = '$identity'" );

		if ( $id > 0 && $newdate != '' ) {

			//входные данные
			$task['datum'] = untag( $newdate );

			$mess = $err = $mailpack = [];

			$users[] = $task['iduser'];

			//список дочерних напоминаний, т.е. пользователям
			$userexist = $db -> getCol( "SELECT iduser FROM ".$sqlname."tasks WHERE maintid = '$id' OR (maintid = '0' AND tid = '$id') AND identity = '$identity'" );

			//объединим 2 массива - 1 - те, у кого были напоминания, + те, 2 - которые есть в текущем запросе
			$userf = array_unique( array_merge( $userexist, $users ) );
			sort( $userf );

			//массив пользоватлей, которым отправлено, для измежания дублей
			$sended = [];

			//чтобы можно было очистить Агенду
			$prm        = arrayNullClean( $task );
			$prm['des'] = $task['des'];
			$prm['day'] = $task['day'];
			$prm['pid'] = is_array( $task['pid'] ) ? yimplode( ",", $task['pid'] ) : $task['pid'];

			//print_r($users);
			//print_r($userf);
			//print_r($task);
			//print_r($prm);
			//exit();

			//обновим напоминание для выбранного пользователя
			if ( count( $users ) == 1 ) {

				try {

					$prm = $db -> filterArray( $prm, array_keys( self::KEYS ) );

					$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$id' and identity = '$identity'", $prm );

					//print $db -> lastQuery();

					$mess[] = "Напоминание обновлено";

					if ( !in_array( $task['iduser'], $sended ) ) {
						if ( $mailme == 'yes' ) {
							$mailpack[] = $this -> taskTemplate( $id, 'edit' );
						}
					}

				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

			//напоминания для выбранных сотрудников, если их больше 1
			elseif ( count( $userf ) > 1 && count( $users ) > 0 ) {

				foreach ( $userf as $user ) {

					unset( $task['maintid'], $task['identity'], $task['active'], $task['iduser'] );

					if ( $user != $iduser1 ) {

						$task['iduser'] = $user;

						$subtid = $db -> getOne( "SELECT tid FROM ".$sqlname."tasks WHERE maintid = '$id' and iduser = '$user' and identity = '$identity'" );

						if ( $subtid > 0 && in_array( $user, $userexist ) ) {

							//если такое напоминание уже есть, т.е. напоминание ранее ставилось пользователю и он !остался в списке
							if ( in_array( $user, $users ) ) {

								//обновляем напоминание
								try {

									$task = $db -> filterArray( $task, array_keys( self::KEYS ) );
									$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$subtid' and identity = '$identity'", arrayNullClean( $task ) );

									$mess[] = current_user( $user ).": Напоминание обновлено";

									if ( $mailme == 'yes' && !in_array( $user, $sended ) )
										$mailpack[] = $this -> taskTemplate( $subtid, 'edit' );


								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

							//если такое напоминание уже есть, т.е. напоминание ранее ставилось пользователю и он *исключен из списка
							else {

								//удаляем напоминание
								try {

									$db -> query( "DELETE FROM ".$sqlname."tasks WHERE tid = '$subtid'" );
									$mess[] = current_user( $user ).": Напоминание удалено";

								}
								catch ( Exception $e ) {

									$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

								}

							}

						}

						//если такого напоминание нет, т.е. напоминание ранее НЕ ставилось пользователю, то добавляем
						elseif ( $subtid < 1 && in_array( $user, $users ) ) {

							$task['maintid']  = $id;
							$task['identity'] = $identity;
							$task['active']   = 'yes';

							//добавляем напоминание
							try {

								$task = $db -> filterArray( $task, array_keys( self::KEYS ) );
								$db -> query( "INSERT INTO ".$sqlname."tasks SET ?u", arrayNullClean( $task ) );
								$subtid = $db -> insertId();

								$mess[] = current_user( $user ).": Напоминание добавлено";

								if ( $mailme == 'yes' && !in_array( $user, $sended ) )
									$mailpack[] = $this -> taskTemplate( $subtid, 'add' );

							}
							catch ( Exception $e ) {

								$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

							}

						}

					}
					elseif ( $user == $iduser1 ) {

						//обновляем напоминание
						try {

							$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$id' and identity = '$identity'", arrayNullClean( $task ) );

							$mess[] = current_user( $user ).": Напоминание обновлено";
							if ( $mailme == 'yes' && !in_array( $user, $sended ) )
								$mailpack[] = $this -> taskTemplate( $id, 'edit' );

						}
						catch ( Exception $e ) {

							$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

						}

					}

				}

			}

			//Высылаем обновление
			foreach ( $mailpack as $pack ) {

				if ( $pack['subscription'] == 'on' && $pack['to'] != '' && $pack['iduser'] != $pack['autor'] ) {

					//$mailsender_rez = mailer( $pack['to'], $pack['toname'], $pack['from'], $pack['fromname'], $pack['theme'], htmlspecialchars_decode( $pack['html'] ) );
					$mailsender_rez = mailto( [
						$pack['to'],
						$pack['toname'],
						$pack['from'],
						$pack['fromname'],
						$pack['theme'],
						htmlspecialchars_decode( $pack['html'] )
					] );

					if ( $mailsender_rez == '' ) {

						$mess[] = 'Отправлено сотруднику '.$pack['toname'];

					}
					else $mess[] = $mailsender_rez;

				}
				if ( $pack['sendcal'] == 'on' && $pack['to'] != '' )
					$this -> createCal( $pack['tid'], 'false' );

			}

			foreach ( $mailpack as $pack ) {

				if ( $pack['iduser'] != $iduser1 ) {

					$args = [
						"id"      => $pack['tid'],
						"autor"   => $iduser1,
						"iduser"  => $pack['iduser'],
						"title"   => $task['title'],
						"type"    => $task['tip'],
						"content" => $task['des'],
						"old"     => $oldTask
					];
					Notify ::fire( 'task.edit', $iduser1, $args );

				}

			}

			$args = [
				"id"    => $id,
				"autor" => $task['autor']
			];
			event ::fire( 'task.edit', $args );

			$response['result'] = 'Success';
			$response['data']   = $id;
			$response['text']   = $mess;
			$response['notice'] = $err;

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id напоминания и/или новая дата";

		}

		return $response;

	}

	/**
	 * Удаление напоминания
	 *
	 * @param int $id - id напоминания
	 *
	 * @return array
	 * good result
	 *         - result = Success
	 *         - text = сообщения
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 * ```
	 * 403 - Напоминание с указанным id не найдено в пределах аккаунта
	 * 405 - Отсутствуют параметры - id напоминания
	 * ```
	 */
	public function remove(int $id = 0): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		if ( $id > 0 ) {

			$task = $db -> getOne( "SELECT tid FROM ".$sqlname."tasks WHERE tid = '$id' and identity = '$identity'" );

			if ( $task > 0 ) {

				$db -> query( "delete from ".$sqlname."tasks where tid = '$task' and identity = '$identity'" );
				$db -> query( "delete from ".$sqlname."tasks where maintid = '$task' and identity = '$identity'" );

				$mess[] = 'Запись удалена';

				$args = [
					"id"    => $id,
					"autor" => $iduser1
				];
				event ::fire( 'task.remove', $args );

				$response['result'] = 'Success';
				$response['text']   = $mess;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание с указанным id не найдено в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id напоминания";

		}

		return $response;

	}

	/**
	 * Отметка выполненным
	 *
	 * @param       $id     - id напоминания
	 * @param array $params - параметры
	 *                      - string **rezultat** - комментарий к выполнению
	 *                      - string **tip** - тип активности
	 *                      - int **status** - статус выполнения (1-успешно,2-не успешно)
	 *                      - datetime **datum** - дата + время выполнения
	 *                      - array **files** - массив идентификаторов файлов (файлы должны быть уже загружены в
	 *                      систему)
	 *
	 * @return array
	 * good result
	 *         - result = Success
	 *         - text = сообщения
	 *         - cid = id записи Активности
	 *         - notice = предупреждения
	 *
	 * error result
	 *         - result = Error
	 *         - error
	 *              - code
	 *              - text
	 *
	 * code:
	 * ```
	 * 403 - Напоминание с указанным id не найдено в пределах аккаунта
	 * 405 - Отсутствуют параметры - id автора напоминания
	 * ```
	 *
	 * todo: добавить загрузку файлов из массива $_FILES через класс Upload
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function doit($id, array $params = []): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		if ( $id > 0 ) {

			$task = $db -> getOne( "SELECT tid FROM ".$sqlname."tasks WHERE tid = '$id' and identity = '$identity'" );

			if ( $task > 0 ) {

				$taskInfo = self :: info( $id );

				$rezultat = untag( $params['rezultat'] );
				$tip      = (isset( $params['tip'] ) && $params['tip'] != '') ? $params['tip'] : $taskInfo['tip'];
				$datum    = $params['datum'] ?? current_datumtime();
				$files    = $params['files'];
				$status   = $params['status'] ?? 1;

				$mess = $err = $mailpack = [];
				$hid  = 0;

				//Проверяем, включены ли уведомления во всей системе
				$mailme = $db -> getOne( "SELECT mailme FROM ".$sqlname."settings WHERE id = '$identity'" );

				try {

					//id записанной активности
					$hid = addHistorty( [
						'iduser'   => $iduser1,
						'clid'     => $taskInfo['clid'],
						'pid'      => yimplode( ";", $taskInfo['pid'] ),
						'did'      => $taskInfo['did'],
						'datum'    => $datum,
						'des'      => $rezultat,
						'tip'      => $tip,
						'fid'      => yimplode( ";", $files ),
						'identity' => $identity
					] );

					$db -> query( "UPDATE ".$sqlname."tasks SET ?u WHERE tid = '$id' AND identity = '$identity'", [
						'active' => 'no',
						'status' => $status,
						'cid'    => $hid
					] );

					//сделаем отметку в карточке клиента
					if ( $taskInfo['clid'] > 0 )
						$db -> query( "UPDATE ".$sqlname."clientcat SET ?u WHERE clid = '".$taskInfo['clid']."' AND identity = '$identity'", ['last_hist' => $datum] );

					if ( $mailme == 'yes' )
						$mailpack[] = $this -> taskTemplate( $id, 'doit', $rezultat );

					$mess[] = 'Добавлена Активность';

				}
				catch ( Exception $e ) {

					$err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

				// отправка email-уведомлений
				foreach ( $mailpack as $pack ) {

					if ( $pack['to'] != '' && $pack['iduser'] != $pack['autor'] ) {

						//$mailsender_rez = mailer( $pack['to'], $pack['toname'], $pack['from'], $pack['fromname'], $pack['theme'], htmlspecialchars_decode( $pack['html'] ) );
						$mailsender_rez = mailto( [
							$pack['to'],
							$pack['toname'],
							$pack['from'],
							$pack['fromname'],
							$pack['theme'],
							htmlspecialchars_decode( $pack['html'] )
						] );

						if ( $mailsender_rez == '' ) {

							$mess[] = 'Отправлено сотруднику '.$pack['toname'];
							//createCal($mailpack[ $j ]['tid'], 'false');

						}
						else $mess[] = $mailsender_rez;

					}

				}

				// добавление Уведомления
				foreach ( $mailpack as $pack ) {

					if ( $pack['autor'] != $iduser1 ) {

						$args = [
							"id"      => $id,
							"autor"   => $iduser1,
							"iduser"  => $pack['iduser'],
							"title"   => $taskInfo['title'],
							"type"    => $taskInfo['tip'],
							"content" => $rezultat
						];
						Notify ::fire( 'task.doit', $iduser1, $args );

					}

				}

				// генерация события
				$args = [
					"id"    => $id,
					"autor" => $iduser1
				];
				event ::fire( 'task.doit', $args );

				$response['result'] = 'Success';
				$response['text']   = $mess;
				$response['cid']    = $hid;
				$response['notice'] = $err;

			}
			else {

				$response['result']        = 'Error';
				$response['error']['code'] = '403';
				$response['error']['text'] = "Напоминание с указанным id не найдено в пределах аккаунта";

			}

		}
		else {

			$response['result']        = 'Error';
			$response['error']['code'] = '405';
			$response['error']['text'] = "Отсутствуют параметры - id напоминания";

		}

		return $response;

	}

	/**
	 * Формирование массива данных для отправки письма
	 *
	 * @param int    $id       - id напоминания
	 * @param string $type     - тип события: add, edit, doit
	 * @param string $rezultat - результат выполнения
	 *
	 * @return array - ответ
	 *      - **to**           - email получателя
	 *      - **toname**       - имя получателя
	 *      - **from**         - email отправителя
	 *      - **fromname**     - имя отправителя
	 *      - **theme**        - Тема сообщения
	 *      - **html**         - Содержание ( http://www.php.su/htmlspecialchars )
	 *      - **tid**          - id напоминания
	 *      - **subscription** - on|off
	 *      - **sendcal**      - on|off
	 *      - **iduser**       - id сотрудника
	 *      - **autor**        - автор напоминания
	 * todo: Перевести на шаблон в формате Mustache
	 */
	public function taskTemplate(int $id, string $type = 'add', string $rezultat = ''): array {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$productInfo = $GLOBALS['productInfo'];

		$task = $db -> getRow( "SELECT * FROM ".$sqlname."tasks WHERE tid = '$id' AND identity = '$identity'" );
		//$task['des'] = nl2br( $task['des'] );
		$pids = yexplode( ";", $task['pid'] );

		$html      = $appx = $theme = $reazon = $sendcal = '';
		$taskData  = $to = $from = [];
		$subscribe = 'off';

		if ( $iduser1 < 1 )
			$iduser1 = $task['iduser'];

		if ( $task['autor'] < 1 )
			$task['autor'] = $iduser1;

		$userName = current_user( $task['iduser'] );

		//print_r($task);

		switch ($type) {

			case "add":

				$theme = $reazon = 'Вам назначено напоминание';

				//отправитель
				//отправку письма делаем от текущего пользователя
				$from = $db -> getRow( "SELECT title, email FROM ".$sqlname."user WHERE iduser = '$task[autor]' AND identity = '$identity'" );

				//получатель (ответственный за напоминание)
				$to = $db -> getRow( "SELECT title, email, secrty, subscription FROM ".$sqlname."user WHERE iduser = '$task[iduser]' AND identity = '$identity'" );

				$to['subscription'] = explode( ";", $to['subscription'] );
				$sendcal            = $to['subscription'][8];
				$subscribe          = $to['subscription'][9];

			break;
			case "edit":

				$theme = $reazon = 'Изменено назначенное Вам напоминание';

				//отправитель
				//отправку письма делаем от текущего пользователя
				$from = $db -> getRow( "SELECT title, email FROM ".$sqlname."user WHERE iduser='".$task['autor']."' AND identity = '".$identity."'" );

				//получатель (ответственный за напоминание)
				$to = $db -> getRow( "SELECT title, email, secrty, subscription FROM ".$sqlname."user WHERE iduser='".$task['iduser']."' AND identity = '".$identity."'" );

				$to['subscription'] = explode( ";", $to['subscription'] );
				$sendcal            = $to['subscription'][8];
				$subscribe          = $to['subscription'][9];

			break;
			case "doit":

				if ( $task['status'] == 1 )
					$status = 'Успешно выполнено';

				else
					$status = 'Закрыто';

				$theme    = 'Выполнено напоминание';
				$userName = current_user( $task['autor'] );
				$reazon   = '<div style="color:#222; margin-bottom:10px">Назначенное Вами напоминание <b>'.$status.'</b>.</div>
				<div style="border:1px dotted #ECEFF1;background:#EEE;font-size:1.0em;padding: 10px;-moz-border-radius: 5px;-webkit-border-radius: 5px;border-radius: 5px;display:block;"><b style="color:#B0BEC5">Результат:</b><br>'.$rezultat.'</div>';

				//отправитель
				//отправку письма делаем от текущего пользователя
				$from = $db -> getRow( "SELECT title, email FROM ".$sqlname."user WHERE iduser='".$task['iduser']."' AND identity = '".$identity."'" );

				//получатель (ответственный за напоминание)
				$to = $db -> getRow( "SELECT title, email, secrty, subscription FROM ".$sqlname."user WHERE iduser='".$task['autor']."' AND identity = '".$identity."'" );

				$to['subscription'] = explode( ";", $to['subscription'] );
				$subscribe          = $to['subscription'][10];

			break;

		}

		$card = '<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>';

		$http = $_SERVER['HTTP_SCHEME'] ?? ((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

		foreach ( $pids as $pid ) {

			$appx   = '';
			$person = get_person_info( $pid, "yes" );

			//print_r($person);

			if ( $type != 'doit' ) {

				$appx .= ($person['mail'] != '') ? '<div style="margin-left:28px">Почта: '.link_it( $person['mail'] ).'</div>' : '';

				$phone = preparePhoneSmart( $person['tel'].",".$person['fax'].",".$person['mob'], true );

				$appx .= ($phone != '') ? '<div style="margin-left:28px">Тел.: '.$phone.'</div>' : '';

			}

			$card .= '<div>Контакт: <a href="'.$http.$_SERVER['HTTP_HOST'].'/card.person?pid='.$pid.'" target="_blank">'.$person['person'].'</a></div>'.$appx.'<br>';

		}

		if ( $task['clid'] > 0 ) {

			$appx   = '';
			$client = get_client_info( $task['clid'], 'yes' );

			if ( $type != 'doit' ) {

				$appx .= ($client['address'] != '') ? '<div style="margin-left:28px">Адрес: '.$client['address'].'</div>' : '';
				$appx .= ($client['mail_url'] != '') ? '<div style="margin-left:28px">Email: '.link_it( $client['mail_url'] ).'</div>' : '';

				$phone = preparePhoneSmart( $client['phone'].",".$client['fax'], true );

				$appx .= ($phone != '') ? '<div style="margin-left:28px">Тел.: '.$phone.'</div>' : '';

			}

			$card .= '<div>Клиент: <a href="'.$http.$_SERVER['HTTP_HOST'].'/card.client?clid='.$task['clid'].'" target="_blank">'.$client['title'].'</a>;'.$appx.'</div><br>';

		}

		if ( $task['did'] > 0 ) {

			$appx = '';

			$adress = getDogData( $task['did'], "adres" );

			$appx .= ($adress != '') ? '<div style="margin-left:28px">Адрес: '.$adress.'</div>' : '';

			$card .= '<div>Сделка: <a href="'.$http.$_SERVER['HTTP_HOST'].'/card.deal?did='.$task['did'].'" target="_blank">'.current_dogovor( $task['did'] ).'</a>'.$appx.'</div>';

		}

		$autor = ($task['autor'] > 0 && $type != 'doit') ? ', Назначил: <b>'.current_user( $task['autor'] ).'</b>' : '';

		$agenda = ($task['des'] != '') ? '<div><h4>Агенда:</h4>'.nl2br( $task['des'] ).'</div><br>' : '';

		$html .= '
		<DIV style="width:600px; margin:0 auto; border:1px solid #ECEFF1; font-family: Tahoma,Arial,sans-serif;">
			<div style="height:60px; background:#ECEFF1;">
			<a href="'.$productInfo['site'].'"><img src="data:image/png;base64,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" height="25" align="left" border="0" hspace="10" vspace="15"></a>
			</div>
			<div style="font-size: 14px; color: #222; line-height: 18px; padding: 10px 10px;">
				<div>
					<div style="font-size: 14px; color: #222;">
						<div style="margin-bottom:10px"><b>Уважаемый '.$userName.',</b></div>
						'.$reazon.'
						<div style="height:1px; border-top:1px solid #ECEFF1; margin:10px 0;"></div>
						<div style="font-size:18px; margin:10px 0 10px 0"><b style="color:red;">'.getTime( (string)$task['totime'] ).'</b>  <b style="color:#222">'.$task['title'].'</b></div>
						<div style="font-size:12px; color:#507192"><em>Дата: <b>'.format_date_rus( (string)$task['datum'] ).'</b>, Тип: <b>'.$task['tip'].'</b>'.$autor.'</em></div>
					</div>
					<div style="height:1px; border-top:1px dotted #ECEFF1; margin-top:10px;"></div>
					<div style="color:#222; font-size:14px; margin-top: 20px;">
						'.$agenda.'
						'.$card.'
					</div>
				</div>
			</div>
			<div style="font-size:10px; padding:10px; border-top:1px solid #ECEFF1; color:#507192; text-align: center">Sended from '.$productInfo['name'].'</div>
		</DIV>';

		if ( $task['secrty'] != 'no' && $subscribe == 'on' ) {

			$taskData = [
				"to"           => $to['email'],
				"toname"       => $to['title'],
				"from"         => $from['email'],
				"fromname"     => $from['title'],
				"theme"        => $theme,
				"html"         => htmlspecialchars( $html ),
				//"tid"          => $id,
				"subscription" => $subscribe,
				//"sendcal"      => $sendcal,
				"iduser"       => $task['iduser'],
				"autor"        => ($task['autor'] > 0) ? $task['autor'] : $task['iduser']
			];

		}

		$taskData['tid']     = $id;
		$taskData['sendcal'] = $sendcal;

		return $taskData;

	}

	/**
	 * Создает и отправляет по email напоминание и файл ical для импорта
	 *
	 * @param int    $id       - идентификатор напоминания
	 * @param string $printrez - true|false - вывод результата
	 *
	 * @return boolean
	 * @throws \PHPMailer\PHPMailer\Exception
	 */
	public function createCal(int $id = 0, string $printrez = "true"): bool {

		$rootpath = $this -> rootpath;

		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;

		$productInfo = $GLOBALS['productInfo'];
		$tzone       = $GLOBALS['tzone'];

		$port  = '';
		$crd   = '';
		$avtor = '';

		$server = $_SERVER['HTTP_HOST'];
		$scheme = $_SERVER['HTTP_SCHEME'] ?? ((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

		$task = self :: info( $id );

		$clid    = $task["clid"];
		$did     = $task["did"];
		$title   = $task["title"];
		$des     = str_replace( "\r\n", "<br>", $task["des"] );
		$tip     = $task["tip"];
		$iduser  = $task["iduser"];
		$author  = $task["autor"];
		$datum   = $task["datum"];
		$totime  = getTime( (string)$task["totime"] );
		$time    = $task["totime"];
		$persons = $task["pid"];

		$subscription = $db -> getOne( "SELECT subscription FROM ".$sqlname."user WHERE iduser='$iduser' AND identity = '$identity'" );
		$subscribe    = explode( ";", $subscription );
		$sendo        = $subscribe[8];

		//отправку письма делаем от текущего пользователя
		$re       = $db -> getRow( "SELECT * FROM ".$sqlname."user WHERE iduser='$iduser1' AND identity = '$identity'" );
		$fromname = $re["title"];
		$from     = $re["email"];

		//находим имя ответственного
		$re     = $db -> getRow( "SELECT * FROM ".$sqlname."user WHERE iduser='$iduser' AND identity = '$identity'" );
		$toname = $re["title"];
		$to     = $re["email"];

		$card = '<div style="height:1px; border-top:1px solid #ccc; margin:10px 0;" /></div>';

		if ( $did > 0 ) {

			$card .= '<br>Сделка: <a href="'.$scheme.$server.$port.'/card.deal?did='.$did.'" target="_blank">'.current_dogovor( $did ).'</a><br>';
			$crd  .= 'Сделка: '.current_dogovor( $did ).' ['.$scheme.$server.$port.'/card.deal?did='.$did.']\n';

		}

		//составим линки на карточки персон
		foreach ( $persons as $jValue ) {

			$apdx = [];

			$tel  = getPersonData( $jValue, 'tel' );
			$mob  = getPersonData( $jValue, 'mob' );
			$mail = getPersonData( $jValue, 'mail' );

			if ( $tel != '' )
				$apdx[] = "<li>".$tel."</li>";
			if ( $mob != '' )
				$apdx[] = "<li>".$mob."</li>";
			if ( $mail != '' )
				$apdx[] = "<li>".$mail."</li>";

			$sapdx = !empty( $apdx ) ? "  <ul>".yimplode( " ", $apdx )."</ul>" : '';

			if ( $jValue > 0 ) {

				$card .= 'Контакт: <a href="'.$scheme.$server.$port.'/card.person.php?pid='.$jValue.'" target="_blank">'.current_person( $jValue ).'</a>'.link_it( $sapdx ).'<br>';
				$crd  .= 'Контакт: '.current_person( $jValue ).$sapdx."\n\n";

			}
		}

		if ( $clid > 0 ) {

			$apdx = [];

			$tel     = getClientData( $clid, 'phone' );
			$mob     = getClientData( $clid, 'fax' );
			$mail    = getClientData( $clid, 'mail_url' );
			$address = getClientData( $clid, 'address' );

			if ( $tel != '' )
				$apdx[] = "<li>".$tel."</li>";
			if ( $mob != '' )
				$apdx[] = "<li>".$mob."</li>";
			if ( $mail != '' )
				$apdx[] = "<li>".$mail."</li>";

			$sapdx = !empty( $apdx ) ? "  <ul>".yimplode( "", $apdx )."</ul>" : "";

			$card .= 'Клиент: <a href="'.$scheme.$server.$port.'/card.client?clid='.$clid.'" target="_blank">'.current_client( $clid ).'</a>'.link_it( $sapdx ).'<br>';
			$crd  .= 'Клиент: '.current_client( $clid ).$sapdx."\n\n";//.' [http://'.$_SERVER['HTTP_HOST'].$port.'/card.client.php?clid='.$clid.']\n\n';

			if ( $address != '' ) {

				$card .= "<br>Адрес: <b>".$address."</b><br>";
				$crd  .= "\nАдрес: ".$address."\n";

			}

		}

		if ( $author > 0 ) {
			$avtor  = current_user( $author );
			$author = '[Назначил: <b style="color:red">'.current_user( $author ).'</b>]';
		}
		else {
			$author = '[Назначил: <b style="color:red">Я</b>]';
		}

		$theme = $tip.': '.$title;

		$agenda = '
		<DIV style="width:600px; margin:0 auto; border:1px solid #DFDFDF; font-family: Tahoma,Arial,sans-serif;">
			<div style="height:60px; background:#DFDFDF;">
			<a href="'.$productInfo['site'].'"><img src="'.$productInfo['site'].'/images/logo.png" height="31" align="left" border="0" hspace="30" vspace="19" /></a>
			</div>
			<div style="font-size: 14px; color: #000; line-height: 18px; padding: 10px 10px;">
				<div style="border-bottom:0 solid #DFDFDF">
					<span style="font-size: 14px; color: #000;"><b>Уважаемый '.current_user( $iduser ).',</b><br><br>Вам назначено напоминание: <br /><br /><b style="font-size:18px; color:#507192">'.$title.'</b></span>
					<div style="color:black; font-size:12px; margin-top: 20px;">
						<b style="color:red">'.$totime.'</b> '.format_date_rus( $datum ).'&nbsp;&nbsp;[<b>'.$tip.'</b>]&nbsp;'.$author.'<br><br>
						'.($des != '' ? '<b>Агенда:</b><br>'.$des.'<br>' : '').'
						'.$card.'
					</div>
				</div>
			</div>
			<div style="font-size:10px; padding:10px 10px 10px 10px; border-top:1px solid #DFDFDF; text-align: right">'.$productInfo['name'].' Team</div>
		</DIV>
		';

		$txt = "Тема: ".$title."\nОписание: ".$des."\nТип: ".$tip;
		if ( $crd ) {
			$txt .= "\n-------------------------------------\n".$crd;
		}
		if ( $author ) {
			$txt .= "-------------------------------------\nАвтор: ".$avtor;
		}

		$dstart = getTimestamp( $datum." ".$time ) + $tzone * 3600;
		$dend   = getTimestamp( $datum." ".$time ) + 600 + $tzone * 3600;

		$uniqueid = md5( '290276'.$id );

		$cal = new EasyPeasyICS( "CRM" );
		$cal -> addEvent( $dstart, $dend, htmlspecialchars( $title ), $txt, '', $uniqueid );
		$ics = $cal -> render( false );

		$filename = 'salesman'.time().'.ics';
		$handle   = fopen( $rootpath."/files/".$filename, 'wb' );
		fwrite( $handle, "$ics\r" );
		fclose( $handle );

		if ( $sendo == 'on' ) {

			$r = mailCal( [
				$to,
				$toname,
				$from,
				$fromname,
				$theme,
				$agenda,
				$ics,
				$rootpath."/files/".$filename
			] );


			if ( $printrez == "true" ) {

				if ( $r == '' ) {
					print 'Событие отправлено по Email';
				}
				else {
					print $r;
				}

			}


		}

		unlink( $rootpath."/files/".$fpath.$filename );

		return true;

	}

}