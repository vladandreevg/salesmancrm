<?php
/* ============================ */
/*         DocCollector         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*    DocCollector Project      */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

namespace Salesman;

use Exception;

/**
 * Класс для Уведомлений
 *
 * Class Notify
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class Notify {

	/**
	 * набор цветных иконок
	 * для выбора нужно указать соответствующий tip события
	 */
	public const ICONS = [
		"client"  => [
			"icon"  => "icon-building",
			"color" => "blue"
		],
		"deal"    => [
			"icon"  => "icon-briefcase",
			"color" => "broun"
		],
		"person"  => [
			"icon"  => "icon-user-1",
			"color" => "green"
		],
		"invoice" => [
			"icon"  => "icon-rouble",
			"color" => "red"
		],
		"comment" => [
			"icon"  => "icon-chat",
			"color" => "orange"
		],
		"lead"    => [
			"icon"  => "icon-paper-plane",
			"color" => "fiolet"
		],
		"todo"    => [
			"icon"  => "icon-calendar-1",
			"color" => "deepblue"
		],
		/*"project" => [
			"icon"  => "icon-tools",
			"color" => "deepblue"
		],*/
		"note"    => [
			"icon"  => "icon-bell-alt",
			"color" => "gray2"
		],
		"phone"   => [
			"icon"  => "icon-phone-squared",
			"color" => "green"
		],
		"cp"   => [
			"icon"  => "icon-check",
			"color" => "indigo"
		],
		/*"catalog" => [
			"icon"  => "icon-archive",
			"color" => "broun"
		]*/
	];

	/**
	 * набор стандартных событий
	 */
	public const EVENTS = [
		"client.add"        => "Клиент. Новый",
		"client.edit"       => "Клиент. Изменен",
		"client.userchange" => "Клиент. Передан сотруднику",
		"client.delete"     => "Клиент. Удален",
		"client.double"     => "Клиент. Проверен на дубли",

		"person.send"       => "Контакт. Передан сотруднику",

		"deal.add"          => "Сделка. Новая",
		"deal.edit"         => "Сделка. Изменена",
		"deal.userchange"   => "Сделка. Передана сотруднику",
		"deal.step"         => "Сделка. Смена этапа",
		"deal.close"        => "Сделка. Закрыта",

		"invoice.doit"      => "Счет. Проведен",

		"lead.add"          => "Заявка. Новая",
		"lead.setuser"      => "Заявка. Назначен ответственный",
		"lead.do"           => "Заявка. Обработана",

		"cp.add"            => "Контрольная точка. Новая",
		"cp.edit"           => "Контрольная точка. Изменена",
		//"cp.delete"         => "Контрольная точка. Удалена",
		"cp.doit"           => "Контрольная точка. Обработана",
		//"cp.undoit"         => "Контрольная точка. Восстановлена",

		"comment.new"       => "Обсуждение. Новая тема или ответ",
		"comment.close"     => "Обсуждение. Закрыто",

		"task.add"          => "Напоминание. Новое",
		"task.edit"         => "Напоминание. Изменено",
		"task.doit"         => "Напоминание. Выполнено",
		//"sklad"             => "События склада",
		"self"              => "Событие произвольное"
	];

	/**
	 * Добавляет свои типы уведомлений через Hook
	 *
	 * @return mixed
	 */
	public static function events() {

		global $hooks;

		return $hooks -> apply_filters( 'add_custom_notify', self::EVENTS );

	}

	/**
	 * Добавляет свою иконку из набора fontello через Hook
	 *
	 * @return mixed
	 */
	public static function icons() {

		global $hooks;

		return $hooks -> apply_filters( 'add_custom_notify_icon', self::ICONS );

	}

	/**
	 * Информация об уведомлении
	 *
	 * @param integer $id
	 * @return array
	 */
	public static function info(int $id): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$query = "
			SELECT 
				*
			FROM {$sqlname}notify
			WHERE
				{$sqlname}notify.id = '$id' AND
				{$sqlname}notify.identity = '$identity'
		";
		$res   = $db -> getRow( $query );

		return [
			"id"      => $id,
			"datum"   => $res['datum'],
			"title"   => $res['title'],
			"content" => $res['content'],
			"autor"   => $res['autor'],
			"iduser"  => $res['iduser'],
			"status"  => $res['status']
		];

	}

	/**
	 * Список уведомлений для пользователя
	 *
	 * @param       $iduser
	 * @param array $options
	 * @return array
	 */
	public static function items($iduser, array $options = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$notify = [];
		$filter = $limit = '';
		$unread = 0;

		$icons = self ::icons();

		//если статус задан, но учитываем его
		if ( !empty( $options['status'] ) ) {
			$filter .= (is_array( $options['status'] )) ? $sqlname."notify.status IN (".yimplode( ",", $options['status'] ).") AND " : $sqlname."notify.status = $options[status] AND ";
		}

		//иначе выводим только не прочитанное
		else {
			$filter .= $sqlname."notify.status = 0 AND ";
		}

		if ( !empty( $options['limit'] ) ) {
			$limit = "LIMIT 0, ".$options['limit'];
		}

		$query = "
			SELECT 
				*
			FROM {$sqlname}notify
			WHERE
				{$sqlname}notify.iduser = '$iduser' AND
				$filter
				{$sqlname}notify.identity = '$identity'
			ORDER BY {$sqlname}notify.datum DESC
			$limit
		";
		$res   = $db -> query( $query );
		while ($da = $db -> fetch( $res )) {

			$notify[] = [
				"id"        => $da['id'],
				"datum"     => $da['datum'],
				"date"      => get_sfdate3( $da['datum'] ),
				"title"     => $da['title'],
				"content"   => htmlspecialchars_decode( $da['content'] ),
				"url"       => ($da['url'] && filter_var( $da['url'], FILTER_VALIDATE_URL )) ? $da['url'] : '',
				"onclick"   => ($da['url'] && !filter_var( $da['url'], FILTER_VALIDATE_URL )) ? $da['url'] : '',
				"autor"     => $da['autor'],
				"autorName" => current_user( $da['autor'], "yes" ),
				"iduser"    => $da['iduser'],
				"tip"       => $da['tip'],
				"uid"       => $da['uid'],
				"icon"      => $icons[ $da['tip'] ]['icon'],
				"color"     => $icons[ $da['tip'] ]['color'],
				"status"    => ($da['status'] == 0) ? 'unread' : NULL
			];

			if ( $da['status'] == 0 ) {
				$unread++;
			}

		}

		self ::deleteOld();

		return [
			"list"   => $notify,
			"unread" => $unread
		];

	}

	/**
	 * Редактирование уведомления
	 *
	 * @param       $id - id
	 * @param array $params
	 * @return array
	 */
	public static function edit($id, array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		if ( !isset( $params['autor'] ) ) {
			$params['autor'] = $GLOBALS['iduser1'];
		}

		//поля, которые есть в таблице
		$allowed = [
			"datum",
			'title',
			'url',
			'tip',
			'uid',
			'content',
			'status',
			'iduser',
			'autor',
			'identity'
		];

		if ( $params['content'] != '' ) {
			$params['content'] = htmlspecialchars( $params['content'] );
		}
		
		$xparams = data2dbtypes( $params, "{$sqlname}notify" );

		//очищаем от мусора
		$nparams = $db -> filterArray( $xparams, $allowed );

		//print_r($nparams);

		//новая запись
		if ( $id < 1 ) {

			if ( $params['content'] != '' || $params['title'] != '' || (int)$params['iduser'] == 0 ) {

				$nparams['status'] = 0;

				$db -> query( "INSERT INTO {$sqlname}notify SET ?u", $nparams );
				$id = $db -> insertId();

				$result = 'ok';

			}
			else {

				$result = 'error';

			}

		}
		//обновление записи. например, отметка о прочтении
		else {

			unset( $nparams['identity'] );
			$db -> query( "UPDATE {$sqlname}notify SET ?u WHERE id = '$id' and identity = '$identity'", $nparams );

			$result = 'ok';

		}

		return [
			"id"     => $id,
			"result" => $result
		];

	}

	/**
	 * Добавление уведомления по событию
	 *
	 * @param       $event
	 * @param       $autor
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	public static function fire($event, $autor, array $params = []): array {

		$notifys = [];

		//$params['autor'] = $autor;

		$notifyText = self ::eventNotify( $event, $params );

		//пользователи, которым отправляем уведомление
		$users = $notifyText['users'];

		//print_r($users);

		//делаем отправку каждому пользователю
		foreach ( $users as $user ) {

			if ( (int)$user > 0 ) {

				//подписки пользователя
				$subscription = self ::userSubscription( $user );

				/*
				print "<br>==============<br>";
				print $event."<br>";
				print $user."<br>";
				print_r($subscription);
				print "<br>==============<br>";
				*/

				//file_put_contents(dirname(__DIR__, 2)."/cash/notify-{$user}.json", json_encode_cyr(["event" => $event, "events" => $subscription]));

				if ( $autor != $user && in_array( $event, $subscription ) ) {

					$notifys[] = self ::edit( 0, [
						"autor"   => $autor,
						"iduser"  => $user,
						"title"   => $notifyText['title'],
						"content" => $notifyText['content'],
						"url"     => $notifyText['url'],
						"tip"     => $notifyText['tip'],
						"uid"     => $notifyText['uid']
					] );

				}

			}

		}

		return $notifys;

	}

	/**
	 * Удаление уведомлений, которым более 3 суток
	 *
	 * @return string
	 */
	public static function deleteOld(): string {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db      = $GLOBALS['db'];
		$sqlname = $GLOBALS['sqlname'];

		$db -> query( "DELETE FROM {$sqlname}notify WHERE TIMESTAMPDIFF(HOUR, datum, NOW()) > 72" );

		return 'ok';

	}

	/**
	 * Пометка уведомления прочитанным
	 *
	 * @param $id
	 * @return array
	 */
	public static function readit($id): array {

		$result = self ::edit( $id, ["status" => 1] );

		return [
			"id"     => $id,
			"result" => $result
		];

	}

	/**
	 * Пометка всех уведомлений прочитанными
	 *
	 * @return string
	 */
	public static function readitAll(): string {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db      = $GLOBALS['db'];
		$sqlname = $GLOBALS['sqlname'];
		$iduser1 = $GLOBALS['iduser1'];

		$db -> query( "UPDATE {$sqlname}notify SET ?u WHERE iduser = '$iduser1' AND status = '0'", ["status" => 1] );

		return 'ok';

	}

	/**
	 * Формирование массива данных для уведомления по его типу
	 *
	 * @param       $event
	 *   - стандартные:
	 *   client.add, client.edit, client.userchange, client.delete, client.double,
	 *   person.send,
	 *   deal.add, deal.edit, deal.userchange, deal.step, deal.close,
	 *   invoice.doit,
	 *   lead.add, lead.setuser, lead.do,
	 *   comment.new, comment.close,
	 *   task.add, task.edit, task.doit,
	 *
	 * - собственное событие:
	 *   $event = 'self' - произвольное событие (все данные передаются напрямую через $params)
	 *   - $params['url'] - ссылка для открытия сущности в виде url или в виде функции js ( вида openClient('125') )
	 *   - $params['title'] - заголовок уведомления
	 *   - $params['content'] - содержимое уведомления (допускается html-оформление)
	 *   - $params['tip'] - тип события (влияет на иконку)
	 *                    - стандартные: client, person, deal, invoice, comment, lead, todo, project
	 *   - $params['id'] - идентификатор сущности
	 *   - $params['users'] - массив идентификаторов сотрудников, которым отправляется уведомление
	 * @param array $params
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function eventNotify($event, array $params = []): array {

		//global $hook;

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];
		$valuta   = $GLOBALS['valuta'];
		$iduser1  = $GLOBALS['iduser1'];

		$iduser = $params['iduser'];

		//print_r($params);

		if ( $params['identity'] > 0 ) {
			$identity = $params['identity'];
		}

		$toUsers = [];
		$tag     = [];

		//формируем ссылку в зависимости от типа события
		switch ($event) {

			case 'client.add':

				$tag['url']     = 'openClient(\''.$params['clid'].'\')';
				$tag['title']   = 'Создан новый Клиент - '.($params['title'] != '' ? $params['title'] : current_client( $params['clid'] ));
				$tag['content'] = ($params['title'] != '') ? $params['title'] : current_client( $params['clid'] );
				$tag['tip']     = 'client';
				$tag['uid']     = $params['clid'];


			break;
			case 'client.edit':

				$log = nl2br( str_replace( 'Изменены параметры записи:\n============================\n ', '', $params['log'] ) );

				$tag['url']     = 'openClient(\''.$params['clid'].'\')';
				$tag['title']   = 'Изменена запись клиента - '.($params['title'] != '' ? $params['title'] : current_client( $params['clid'] ));
				$tag['content'] = $log;
				$tag['tip']     = 'client';
				$tag['uid']     = $params['clid'];

				$iduser = ($params['iduser'] > 0) ? $params['iduser'] : getClientData( $params['clid'], 'iduser' );

			break;
			case 'client.userchange':

				$tag['url']     = 'openClient(\''.$params['clid'].'\')';
				$tag['title']   = 'Вам назначен Клиент - '.($params['title'] != '' ? $params['title'] : current_client( $params['clid'] ));
				$tag['content'] = ($params['title'] != '') ? $params['title'] : current_client( $params['clid'] );
				$tag['tip']     = 'client';
				$tag['uid']     = $params['clid'];

				$iduser = ($params['iduser'] > 0) ? $params['iduser'] : getClientData( $params['clid'], 'iduser' );

			break;
			case 'client.delete':

				$tag['url']     = '';
				$tag['title']   = "Удален клиент - ".$params['title'];
				$tag['content'] = $params['title'];
				$tag['tip']     = 'client';
				$tag['uid']     = $params['clid'];

			break;
			case 'client.double':

				$tag['url']     = '';
				$tag['title']   = "Клиент проверен на дубли - ".$params['title'];
				$tag['content'] = $params['text'];
				$tag['tip']     = 'client';
				$tag['uid']     = $params['clid'];

			break;

			case 'person.send':

				$tag['url']     = 'openPerson(\''.$params['pid'].'\')';
				$tag['title']   = 'Вам назначен Контакт';
				$tag['content'] = $params['person'];
				$tag['tip']     = 'person';
				$tag['uid']     = $params['pid'];

				$iduser = $params['iduser'];

			break;

			case 'deal.add':

				$title      = getDogData( (int)$params['did'], 'title' );
				$kol        = getDogData( (int)$params['did'], 'kol' );
				$dogstatus  = current_dogstepname( getDogData( $params['did'], 'idcategory' ) );
				$datum_plan = getDogData( (int)$params['did'], 'datum_plan' );
				$iduser     = getDogData( (int)$params['did'], 'iduser' );

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if ( isServices( (int)$params['did'] ) ) {

					$spekaData = (new Speka()) -> getSpekaData( $params['did'] );
					$kol       = $spekaData['summaItog'];

				}

				$description = "
					Название сделки: <b>".$title."</b><br>
					Текущий этап: <b>".$dogstatus."%</b><br>
					Плановая сумма: <b>".num_format( $kol ).$valuta."</b><br>
					Плановая дата: <b>".format_date_rus( $datum_plan )."</b><br>
					Ответственный: <b>".current_user( $iduser )."</b><br>
				";

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = "Создана новая Сделка - ".$title;
				$tag['content'] = $description;
				$tag['tip']     = 'deal';
				$tag['uid']     = $params['did'];


			break;
			case 'deal.edit':

				$title      = ($params['title'] != '') ? $params['title'] : getDogData( $params['did'], 'title' );
				$kol        = $params['kol'] ?? getDogData( $params['did'], 'kol' );
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname( getDogData( $params['did'], 'idcategory' ) );
				$datum_plan = ($params['datum_plan'] != '') ? $params['datum_plan'] : getDogData( $params['did'], 'datum_plan' );
				$iduser     = $params['iduser'] ?? getDogData( $params['did'], 'iduser' );
				$log        = str_replace( 'Изменены параметры записи:\n============================\n ', '', $params['log'] );

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if ( isServices( (int)$params['did'] ) ) {

					$spekaData = (new Speka()) -> getSpekaData( $params['did'] );
					$kol       = $spekaData['summaItog'];

				}

				$description = "
					Название сделки: <b>".$title."</b><br>
					Текущий этап: <b>".$dogstatus."%</b><br>
					Плановая сумма: <b>".num_format( $kol )."</b><br>
					Плановая дата: <b>".format_date_rus( $datum_plan )."</b><br>
					Ответственный: <b>".current_user( $iduser )."</b><br>
				";
				$description .= "<br><br><em>".$log."</em><br>";

				if ( $params['comment'] != '' ) {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = "Изменена Сделка - ".$title;
				$tag['content'] = $description;
				$tag['tip']     = 'deal';
				$tag['uid']     = $params['did'];

			break;
			case 'deal.userchange':

				$title      = ($params['title'] != '') ? $params['title'] : getDogData( $params['did'], 'title' );
				$kol        = $params['kol'] ?? getDogData( $params['did'], 'kol' );
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname( getDogData( $params['did'], 'idcategory' ) );
				$datum_plan = ($params['datum_plan'] != '') ? $params['datum_plan'] : getDogData( $params['did'], 'datum_plan' );
				$iduser     = $params['iduser'] ?? getDogData( $params['did'], 'iduser' );

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if ( isServices( (int)$params['did'] ) ) {

					$spekaData = (new Speka()) -> getSpekaData( $params['did'] );
					$kol       = $spekaData['summaItog'];

				}

				$description = "
					Название сделки: <b>".$title."</b><br>
					Текущий этап: <b>".$dogstatus."%</b><br>
					Плановая сумма: <b>".num_format( $kol )."</b><br>
					Плановая дата: <b>".format_date_rus( $datum_plan )."</b><br>
				";

				if ( $params['comment'] != '' ) {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = "Вы назначены куратором Сделки - ".$title;
				$tag['content'] = $description;
				$tag['tip']     = 'deal';
				$tag['uid']     = $params['did'];

			break;
			case 'deal.step':

				$title        = ($params['title'] != '') ? $params['title'] : getDogData( $params['did'], 'title' );
				$kol          = $params['kol'] ?? getDogData( $params['did'], 'kol' );
				$dogstatus    = $params['dogstatus'] ?? current_dogstepname( getDogData( $params['did'], 'idcategory' ) );
				$datum_plan   = ($params['datum_plan'] != '') ? $params['datum_plan'] : getDogData( $params['did'], 'datum_plan' );
				$iduser       = $params['iduser'] ?? getDogData( $params['did'], 'iduser' );
				$dogstatusold = $params['dogstatusold'];

				/**
				 * Для сервисной сделки сумму считаем по спецификации
				 */
				if ( isServices( (int)$params['did'] ) ) {

					$spekaData = (new Speka()) -> getSpekaData( $params['did'] );
					$kol       = $spekaData['summaItog'];

				}

				$description = "
					Название сделки: <b>".$title."</b><br>
					Текущий этап: <b>".$dogstatus."%</b><br>
					Предыдущий этап: <b>".$dogstatusold."%</b><br>
					Плановая сумма: <b>".num_format( $kol )."</b><br>
					Плановая дата: <b>".format_date_rus( $datum_plan )."</b><br>
					Ответственный: <b>".current_user( $iduser )."</b><br>
				";

				if ( $params['comment'] != '' ) {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = "Изменен этап сделки Сделки - ".$title;
				$tag['content'] = $description;
				$tag['tip']     = 'deal';
				$tag['uid']     = $params['did'];

			break;
			case 'deal.close':

				$deal = get_dog_info( $params['did'], 'yes' );

				$title      = ($params['title'] != '') ? $params['title'] : $deal['title'];
				$kol        = $params['kol'] ?? $deal['kol'];
				$dogstatus  = $params['dogstatus'] ?? current_dogstepname( $deal['idcategory'] );
				$datum_plan = ($params['datum_plan'] != '') ? $params['datum_plan'] : $deal['datum_plan'];
				$iduser     = $params['iduser'] ?? $deal['iduser'];

				$datum_close = $deal['datum_close'];
				$marga       = $deal['marga'];
				$kol_fact    = $deal['kol_fact'];
				$status      = $params['status'];

				$description = "
					Название сделки: <b>".$title."</b><br>
					Текущий этап: <b>".$dogstatus."%</b><br>
					Статус закрытия: <b>".$status."</b><br>
					Плановая сумма: <b>".num_format( $kol )."</b><br>
					Фактическая сумма: <b>".num_format( $kol_fact )."</b><br>
					Фактическая маржа: <b>".num_format( $marga )."</b><br>
					Плановая дата: <b>".format_date_rus( $datum_plan )."</b><br>
					Фактическая дата: <b>".format_date_rus( $datum_close )."</b><br>
					Ответственный: <b>".current_user( $iduser )."</b><br>
				";

				$description .= "Ответственный: <b>".current_user( $params['iduser'] )."</b><br>";

				if ( $params['comment'] != '' ) {
					$description .= "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = "Закрыта сделка - ".$title;
				$tag['content'] = $description;
				$tag['tip']     = 'deal';
				$tag['uid']     = $params['did'];

			break;

			case 'invoice.doit':

				$credit = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid = '".$params['crid']."' AND identity = '$identity'" );

				$toUsers[] = $credit['iduser'];
				$invoice   = "№".$credit['invoice'];
				$datum     = $credit['invoice_date'];
				$summa     = $credit['summa_credit'];
				$did       = $credit['did'];

				$toUsers[] = getDogData( $did, 'iduser' );

				$type = $params['tip'] ?? $credit['tip'];
				$rs   = $params['rs'] ?? $credit['rs'];

				if ( is_int( $rs ) ) {
					$rs = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id = '$rs' and identity = '$identity'" );
				}

				$description = "
					Счет <b>".$invoice."</b><br>
					Дата оплаты: <b>".format_date_rus( $datum )."</b><br>
					Сумма: <b>".num_format( $summa )." ".$valuta."</b><br>
					Тип счета: <b>".$type."</b><br>
					Расчетный счет: <b>".$rs."</b><br>
				";

				if ( $params['des'] != '' ) {
					$description .= "<br>Комментарий: <b>".$params['des']."</b><br>";
				}

				$tag['url']     = "openDogovor('$did','7')";
				$tag['title']   = "Получена оплата ".num_format( $summa )." ".$valuta." по сч. ".$invoice;
				$tag['content'] = $description;
				$tag['tip']     = "invoice";
				$tag['uid']     = $params['crid'];

			break;

			case 'lead.add':

				$result  = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."'" );
				$datum   = $result['datum'];
				$title   = $result['title'];
				$email   = $result['email'];
				$phone   = $result['phone'];
				$des     = $result['description'];
				$city    = $result['city'];
				$country = $result['country'];
				$iduser  = $result['iduser'];

				$user = ($iduser) ? current_user( $iduser ) : 'Не назначено';

				$description = '
					Дата получения: <b>'.get_sfdate( $datum ).'</b><br>
					Имя: <b>'.$title.'</b><br>
					Email: <b>'.link_it( $email ).'</b><br>
					'.($phone != '' ? 'Телефон: <b>'.formatPhoneUrl( $phone ).'</b><br>' : '').'
					'.($country != '' ? 'Страна: <b>'.$country.'</b><br>' : '').'
					'.($city != '' ? 'Город: <b>'.$city.'</b><br>' : '').'
					Ответственный: <b>'.$user.'</b><br>
					Описание: '.nl2br( $des ).'
				';

				$tag['url']     = 'openLead(\''.$params['id'].'\')';
				$tag['title']   = "Новый Входящий интерес (Лид). ID = ".$params['id'];
				$tag['content'] = $description;
				$tag['tip']     = 'lead';
				$tag['uid']     = $params['id'];

			break;
			case 'lead.setuser':

				$result  = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."' AND identity = '".$identity."'" );
				$datum   = $result['datum'];
				$title   = $result['title'];
				$email   = $result['email'];
				$phone   = $result['phone'];
				$des     = $result['description'];
				$city    = $result['city'];
				$country = $result['country'];
				$iduser  = $result['iduser'];

				$description = '
					Дата получения: <b>'.get_sfdate( $datum ).'</b><br>
					Имя: <b>'.$title.'</b><br>
					Email: <b>'.link_it( $email ).'</b><br>
					'.($phone != '' ? 'Телефон: <b>'.formatPhoneUrl( $phone ).'</b><br>' : '').'
					'.($country != '' ? 'Страна: <b>'.$country.'</b><br>' : '').'
					'.($city != '' ? 'Город: <b>'.$city.'</b><br>' : '').'
					Описание: '.nl2br( $des ).'
				';

				$tag['url']     = 'openLead(\''.$params['id'].'\')';
				$tag['title']   = "Вы назначены Ответственным за обработку Входящего интереса (Лида)";
				$tag['content'] = $description;
				$tag['tip']     = 'lead';
				$tag['uid']     = $params['id'];

			break;
			case 'lead.do':

				$result = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."' AND identity = '$identity'" );
				$iduser = (int)$result['iduser'];
				$clid   = (int)$result['clid'];
				$pid    = (int)$result['pid'];
				$did    = (int)$result['did'];
				$rez    = (int)$result['rezult'];

				$rezult = [
					1 => 'Спам',
					2 => 'Дубль',
					3 => 'Другое',
					4 => 'Не целевой'
				];

				$description = '';

				if ( $rez > 0 ) {
					$description .= 'Входящий интерес <b>дисквалифицирован</b> с результатом: <b>'.strtr( $rez, $rezult ).'</b>';
				}
				else {
					$description .= 'Входящий интерес <b>квалифицирован</b>'.($did > 0 ? ': Создана сделка.<br>' : '');
				}


				if ( $clid > 0 ) {
					$description .= '<br>Клиент: <b><A href="javascript.void(0)" onclick="openClient(\''.$clid.'\')" title="Открыть в новом окне">'.current_client( $clid ).'</a></b><br>';
				}

				if ( $pid > 0 ) {
					$description .= '<br>Контакт: <b><A href="javascript.void(0)" onclick="openPerson(\''.$pid.'\')" title="Открыть в новом окне">'.current_person( $pid ).'</a></b><br>';
				}

				if ( $did > 0 ) {
					$description .= 'Сделка: <b><A href="javascript.void(0)" onclick="openDogovor(\''.$did.'\')" title="Открыть в новом окне">'.current_dogovor( $did ).'</a></b><br>';
				}

				$tag['url']     = 'openLead(\''.$params['id'].'\')';
				$tag['title']   = "Обработан входящий интерес (Лид)";
				$tag['content'] = $description;
				$tag['tip']     = 'lead';
				$tag['uid']     = $params['id'];

			break;

			case 'comment.new':

				$comment  = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$params[id]' AND identity = '$identity'" );
				$idparent = $comment['idparent'] + 0;
				$title    = $comment['title'];
				$content  = html2text( $comment['content'] );
				$project  = $comment['project'];
				$clid     = $comment['clid'];
				$did      = $comment['did'];

				if ( $idparent == 0 ) {

					$tag['title'] = 'Новое обсуждение - '.$title;
					$idparent     = $params['id'];

				}
				else {

					$co      = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$idparent' AND identity = '$identity'" );
					$project = $co['project'];
					$clid    = $co['clid'];
					$did     = $co['did'];

					$tag['title'] = 'Новый ответ в обсуждении - '.$db -> getOne( "SELECT title FROM {$sqlname}comments WHERE id = '$idparent' AND identity = '$identity'" );

					$params['id'] = $idparent;

				}

				if ( $project > 0 ) {

					//require_once "Project.php";
					$pproject = Project ::info( $project );

				}

				$content .= '<br><br>
					'.($clid > 0 ? 'Клиент: <b>'.current_client( $clid ).'</b><br>' : '').'
					'.($did > 0 ? 'Сделка: <b>'.current_dogovor( $did ).'</b><br>' : '').'
					'.($project > 0 ? 'Проект: <b>'.$pproject['project']['name'].'</b><br>' : '');

				$tag['url']     = 'openComment(\''.$params['id'].'\')';
				$tag['content'] = $content;
				$tag['tip']     = 'comment';
				$tag['uid']     = $params['id'];

				$toUsers = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '$idparent' AND iduser != '$iduser1' AND identity = '$identity'" );

			break;
			case 'comment.close':

				$comment = $db -> getRow( "SELECT * FROM {$sqlname}comments WHERE id = '$params[id]' AND identity = '$identity'" );
				$title   = $comment['title'];
				$project = $comment['project'];
				$clid    = $comment['clid'];
				$did     = $comment['did'];

				if ( $project > 0 ) {

					require_once "Project.php";
					$pproject = Project ::info( $project );

				}

				$tag['title'] = 'Закрыто обсуждение - '.$title;

				$tag['url']     = 'openComment(\''.$params['id'].'\')';
				$tag['content'] = 'Обсуждение закрыто сотрудником '.current_user( $params['iduser'] ).'<br>
					'.($clid > 0 ? 'Клиент: <b>'.current_client( $clid ).'</b><br>' : '').'
					'.($did > 0 ? 'Сделка: <b>'.current_dogovor( $did ).'</b><br>' : '').'
					'.($project > 0 ? 'Проект: <b>'.$pproject['project']['name'].'</b><br>' : '');
				$tag['tip']     = 'comment';
				$tag['uid']     = $params['id'];

				$toUsers = $db -> getCol( "SELECT iduser FROM {$sqlname}comments_subscribe WHERE idcomment = '$params[id]' AND iduser != '$iduser1' AND identity = '$identity'" );

			break;

			case 'task.add':

				//require_once "Todo.php";

				$task = Todo ::info( $params['id'] );

				$tag['url']     = 'viewTask(\''.$params['id'].'\')';
				$tag['title']   = 'Добавлено напоминание - '.($params['title'] != '' ? $params['title'] : $task['title']);
				$tag['content'] = ($params['content'] != '') ? $params['content'] : $task['des'];
				$tag['tip']     = 'todo';
				$tag['uid']     = $params['id'];

				$toUsers[] = $task['iduser'];

			break;
			case 'task.edit':

				//require_once "Todo.php";

				$task     = Todo ::info( $params['id'] );
				$taskKeys = Todo::KEYS;

				//что изменилось
				$diff = array_diff_ext( $params['old'], $task );
				$text = [];

				foreach ( $diff as $key => $value ) {

					switch ($key) {

						case "pid":

							$plist = $polist = [];

							foreach ( $task[ $key ] as $pid ) {
								$plist[] = current_person( $pid );
							}

							foreach ( $params['old'][ $key ] as $pid ) {
								$polist[] = current_person( $pid );
							}

							$task[ $key ]          = yimplode( "; ", $plist );
							$params['old'][ $key ] = yimplode( "; ", $polist );

						break;
						case "clid":

							$task[ $key ]          = current_client( $task[ $key ] );
							$params['old'][ $key ] = current_client( $params['old'][ $key ] );

						break;
						case "did":

							$task[ $key ]          = current_dogovor( $task[ $key ] );
							$params['old'][ $key ] = current_dogovor( $params['old'][ $key ] );

						break;
						case "iduser":
						case "autor":

							$task[ $key ]          = current_user( $task[ $key ] );
							$params['old'][ $key ] = current_user( $params['old'][ $key ] );

						break;
						case "des":

							$task[ $key ]          = nl2br( $task[ $key ] );
							$params['old'][ $key ] = nl2br( $params['old'][ $key ] );

						break;
						case "priority":

							$task[ $key ]          = get_priority( 'priority', $task[ $key ] );
							$params['old'][ $key ] = get_priority( 'priority', $params['old'][ $key ] );

						break;
						case "speed":

							$task[ $key ]          = get_priority( 'speed', $task[ $key ] );
							$params['old'][ $key ] = get_priority( 'speed', $params['old'][ $key ] );

						break;
						case "readonly":
						case "alert":
						case "day":

							$task[ $key ]          = ($task[ $key ] == 'yes') ? 'Да' : 'Нет';
							$params['old'][ $key ] = ($params['old'][ $key ] == 'yes') ? 'Да' : 'Нет';

						break;
						default:

						break;

					}

					if ( $params['old'][ $key ] == '' || $params['old'][ $key ] == 0 ) {
						$params['old'][ $key ] = 'не указано';
					}

					$text[] = '
						<div class="gray uppercase Bold fs-07">'.strtr( $key, $taskKeys ).'</div>
						<div class="mb10"><b>'.$task[ $key ].'</b> <div class="inline gray">[&nbsp;было - '.$params['old'][ $key ].'&nbsp;]</div></div>
					';

				}

				$tag['url']     = 'viewTask(\''.$params['id'].'\')';
				$tag['title']   = 'Изменено напоминание - '.($params['title'] != '' ? $params['title'] : $task['title']);
				$tag['content'] = yimplode( "", $text );
				$tag['tip']     = 'todo';
				$tag['uid']     = $params['id'];

				$toUsers[] = $task['iduser'];

			break;
			case 'task.doit':

				//require_once "Todo.php";

				$task = Todo ::info( $params['id'] );

				if ( $task['status'] == 1 ) {
					$status = '<i class="icon-ok green" title="Успешно"></i>&nbsp;Успешно выполнено';
				}

				else {
					$status = '<i class="icon-block red" title="Не успешно"></i>&nbsp;Закрыто';
				}

				$tag['url']     = 'viewTask(\''.$params['id'].'\')';
				$tag['title']   = $status.' напоминание - '.($params['title'] != '' ? $params['title'] : $task['title']);
				$tag['content'] = ($params['content'] != '') ? $params['content'] : $task['des'];
				$tag['tip']     = 'todo';
				$tag['uid']     = $params['id'];

				$toUsers[] = $task['autor'];

			break;

			case "cp.add":
			case "cp.edit":

				$cp = (new ControlPoints()) -> info($params['id']);

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = $event == 'cp.add' ? "Добавлена КТ - ".$cp['title'] : "Изменена КТ - ".$cp['title'];
				$tag['content'] = "Вы указаны ответственным за её выполнение. Плановая дата: ".format_date_rus_name($cp['data_plan']);
				$tag['tip']     = 'cp';
				$tag['uid']     = $params['id'];

				$toUsers[] = $params['iduser'];

			break;
			case "cp.doit":

				$cp = (new ControlPoints()) -> info($params['id']);

				$tag['url']     = 'openDogovor(\''.$params['did'].'\')';
				$tag['title']   = 'Выполнена КТ - '.$cp['title'];
				$tag['content'] = "Выполнена КТ. Плановая дата: ".format_date_rus_name($cp['data_plan']);
				$tag['tip']     = 'cp';
				$tag['uid']     = $params['id'];

				if( $cp['did'] > 0 ){

					$deal = Deal::info((int)$cp['did'] );

					$tag['content'] .= "
						<br>
						Название сделки: <b>".$deal['title']."</b><br>
						Текущий этап: <b>".$deal['step']['steptitle']."%</b><br>
						Плановая сумма: <b>".num_format( $deal['summa'] )."</b><br>
						Плановая дата: <b>".format_date_rus( $deal['datum_plan'] )."</b><br>
						Ответственный: <b>".$deal['user']."</b><br>
					";

				}

				$toUsers[] = $cp['autor'];

			break;

			case 'self':
			case 'sklad':
			// если событие не входит в перечень указанных (кастомное)
			default:

				$event          = $params['event'];
				$tag['url']     = $params['url'];
				$tag['title']   = $params['title'];
				$tag['content'] = $params['content'];
				$tag['tip']     = $params['tip'];
				$tag['uid']     = $params['id'];

				$toUsers = $params['users'];

			break;

		}

		$arr_send = [
			'client.userchange',
			'person.send',
			'deal.userchange'
		];
		$arr_lead = [
			'lead.add',
			'lead.setuser',
			'lead.do'
		];
		$arr_task = [
			'task.add',
			'task.edit',
			'task.doit'
		];

		//если это не передача, то отправляем уведомление руководителю
		if (
			!in_array( $event, $arr_send ) &&
			!in_array( $event, $arr_lead ) &&
			!in_array( $event, $arr_task ) &&
			$tag['tip'] != 'comment'
		) {

			//от кого отправляем
			$mid = (int)$db -> getOne( "SELECT mid FROM {$sqlname}user WHERE iduser = '$iduser1' AND identity = '$identity'" );

			if ( $mid > 0 ) {
				$toUsers[] = $mid;
			}

			goto ext;

		}

		//если это передача, то отправляем текущему ответственному
		if ( in_array( $event, $arr_send ) ) {

			if ( $iduser > 0 ) {
				$toUsers[] = $iduser;
			}

			goto ext;

		}

		//если это работа с заявками
		if ( in_array( $event, $arr_lead ) ) {

			//если это лид
			if ( $event == 'lead.add' ) {

				$mdwset       = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
				$leadsettings = json_decode( $mdwset['content'], true );
				$lusers       = $leadsettings['leadOperator'];
				$coordinator  = $leadsettings["leadСoordinator"];

				if ( $iduser == 0 && $leadsettings['leadMethod'] != 'free' ) {
					$toUsers[] = $coordinator;
				}

				elseif ( $leadsettings['leadMethod'] == 'free' ) {

					foreach ( $lusers as $u ) {
						$toUsers[] = $u;
					}

					$toUsers[] = $coordinator;

				}

				elseif ( $iduser > 0 ) {
					$toUsers[] = $iduser;
				}

			}

			elseif ( $event == 'lead.setuser' ) {

				$toUsers[] = $iduser;

			}

			elseif ( $event == 'lead.do' ) {

				$mdwset       = $db -> getRow( "SELECT * FROM {$sqlname}modules WHERE mpath = 'leads' and identity = '$identity'" );
				$leadsettings = json_decode( $mdwset['content'], true );
				$coordinator  = $leadsettings["leadСoordinator"];

				$toUsers[] = $coordinator;

			}

		}

		ext:

		//если уведомление касается сделки, то уведомляем всех подписчиков
		if ( $params['did'] > 0 /*&& $event != 'deal.userchange'*/ ) {

			$qq = "
				SELECT
					{$sqlname}dostup.iduser as iduser
				FROM {$sqlname}dostup
				WHERE
					{$sqlname}dostup.did = '$params[did]' and
					{$sqlname}dostup.identity = '$identity'
			";
			$re = $db -> query( $qq );
			while ($data = $db -> fetch( $re )) {

				$subscribe = yexplode( ";", $data['subscribe'] );

				if ( $subscribe[6] == "on" ) {
					$toUsers[] = $data['iduser'];
				}

			}

		}

		//ext2:

		return [
			"event"   => $event,
			"title"   => $tag['title'],
			"content" => $tag['content'],
			"url"     => $tag['url'],
			"users"   => array_unique( $toUsers ),
			"tip"     => $tag['tip'],
			"uid"     => $tag['uid']
		];

	}

	/**
	 * Возвращает список уведомлений, на которые подписан пользователь
	 *
	 * @param $iduser
	 * @return array
	 */
	public static function userSubscription($iduser): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$settings     = $db -> getOne( "SELECT usersettings FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'" );
		$usersettings = json_decode( $settings, true );

		//return (!empty( $usersettings['notify'] )) ? $usersettings['notify'] : array_keys( self ::events() );
		return (!empty( $usersettings['notify'] )) ? $usersettings['notify'] : [];

	}

	/**
	 * Формирование сообщения уведомления. Из плагина UserNotifier
	 *
	 * @param $tip
	 * @param $params
	 * @return array
	 * @throws Exception
	 */
	public function Tags($tip, $params): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		//require_once $rootpath."/inc/class/Client.php";
		//require_once $rootpath."/inc/class/Deal.php";

		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];

		if ( $params['identity'] > 0 ) {
			$identity = $params['identity'];
		}

		$valuta = $db -> getOne( "select valuta from {$sqlname}settings WHERE id = '$identity'" );

		$server = $_SERVER['HTTP_HOST'];
		$scheme = $_SERVER['HTTP_SCHEME'] ?? ((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://';

		$response = [];

		//формируем ссылку в зависимости от типа события
		switch ($tip) {

			case 'client.expressadd':

				$client = Client ::info( $params['clid'] );

				$response['link']       = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$response['url']        = $scheme.$server.'/card.client?clid='.$params['clid'];
				$response['pid']        = $params['pid'];
				$response['clid']       = $params['clid'];
				$response['client']     = $client['title'];
				$response['category']   = $client['category'];
				$response['clientpath'] = $client['clientpath'];
				$response['territory']  = $client['territoryname'];
				$response['comment']    = $client['des'];
				$response['phone']      = $client['phone'];
				$response['email']      = $client['mail_url'];
				$response['relation']   = $client['tip_cmr'];
				$response['user']       = current_user( $params['iduser'], "yes" );
				$response['theme']      = "CRM. Создан новый Клиент";
				$response['color']      = "#e53935";
				$response['icon_emoji'] = ":sandwich:";

			break;
			case 'client.add':

				$client = Client ::info( $params['clid'] );

				$response['link']       = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$response['url']        = $scheme.$server.'/card.client?clid='.$params['clid'];
				$response['clid']       = $params['clid'];
				$response['client']     = $client['client']['title'];
				$response['category']   = $client['client']['category'];
				$response['clientpath'] = $client['client']['clientpath'];
				$response['territory']  = $client['client']['territoryname'];
				$response['comment']    = $client['client']['des'];
				$response['phone']      = $client['client']['phone'];
				$response['email']      = $client['client']['mail_url'];
				$response['relation']   = $client['client']['tip_cmr'];
				$response['idautor']    = $params['autor'];
				$response['autor']      = current_user( $params['autor'], "yes" );
				$response['user']       = current_user( $params['user'], "yes" );
				$response['iduser']     = $params['newuser'];
				$response['theme']      = "CRM. Создан новый Клиент";
				$response['color']      = "#e53935";

			break;
			case 'client.edit':

				$client = Client ::info( $params['clid'] );

				$des = $this -> Changes( 'client', $params['newparam'] );

				$response['link']       = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$response['url']        = $scheme.$server.'/card.client?clid='.$params['clid'];
				$response['clid']       = $params['clid'];
				$response['client']     = $client['client']['title'];
				$response['category']   = $client['client']['category'];
				$response['clientpath'] = $client['client']['clientpath'];
				$response['territory']  = $client['client']['territoryname'];
				$response['phone']      = $client['client']['phone'];
				$response['email']      = $client['client']['mail_url'];
				$response['relation']   = $client['client']['tip_cmr'];
				$response['idautor']    = $params['autor'];
				$response['autor']      = current_user( $params['autor'], "yes" );
				$response['user']       = current_user( $params['user'], "yes" );
				$response['iduser']     = $params['user'];

				if ( count( $des ) > 0 ) {
					$response['comment'] = yimplode( "\n", $des );
				}

				$response['theme'] = "CRM. Внесены изменения в карточку Клиента.";
				$response['color'] = "#2196F3";

			break;
			case 'client.change.user':

				$response['link']      = '<a href="'.$scheme.$server.'/card.client?clid='.$params['clid'].'">Ссылка</a>';
				$response['url']       = $scheme.$server.'/card.client?clid='.$params['clid'];
				$response['clid']      = $params['clid'];
				$response['client']    = current_client( $params['clid'] );
				$response['idautor']   = $params['autor'];
				$response['autor']     = current_user( $params['autor'], "yes" );
				$response['user']      = current_user( $params['newuser'], "yes" );
				$response['iduser']    = $params['newuser'];
				$response['userold']   = current_user( $params['olduser'], "yes" );
				$response['iduserold'] = $params['olduser'];

				if ( $params['comment'] != '' ) {
					$response['comment'] = $params['comment'];
				}

				$response['theme'] = "CRM. Изменен ответственный за Клиента";
				$response['color'] = "#2196F3";

			break;
			case 'client.delete':

				$response['clid']   = $params['clid'];
				$response['client'] = $params['client'];
				$response['autor']  = current_user( $params['autor'], "yes" );
				$response['user']   = current_user( $params['user'], "yes" );

				if ( $params['comment'] != '' ) {
					$response['comment'] = $params['comment'];
				}

				$response['theme'] = "CRM. Удален клиент";

			break;

			case 'person.add':

				$person = get_person_info( $params['pid'], "yes" );

				$response['link']     = '<a href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'">Ссылка</a>';
				$response['url']      = $scheme.$server.'/card.person?pid='.$params['pid'];
				$response['pid']      = $person['pid'];
				$response['person']   = $person['person'];
				$response['status']   = $person['ptitle'];
				$response['relation'] = $person['relation'];
				$response['client']   = current_client( $person['clid'] );
				$response['phone']    = implode( ", ", getPersonWPhone( $params['pid'], false ) );
				$response['email']    = implode( ", ", getPersonWMail( $params['pid'], false ) );
				$response['iduser']   = $person['iduser'];
				$response['user']     = current_user( $person['iduser'], "yes" );
				$response['idautor']  = $params['autor'];
				$response['autor']    = current_user( $params['autor'], "yes" );

				$response['theme'] = "CRM. Создан новый Контакт";
				$response['color'] = "#e53935";

			break;
			case 'person.edit':

				$person = get_person_info( $params['pid'], "yes" );

				$des = $this -> Changes( 'person', $params['newparam'] );

				$response['link']     = '<a href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'">Ссылка</a>';
				$response['url']      = $scheme.$server.'/card.person?pid='.$params['pid'];
				$response['pid']      = $person['pid'];
				$response['person']   = $person['person'];
				$response['status']   = $person['ptitle'];
				$response['relation'] = $person['relation'];
				$response['client']   = current_client( $person['clid'] );
				$response['phone']    = implode( ", ", getPersonWPhone( $params['pid'], false ) );
				$response['email']    = implode( ", ", getPersonWMail( $params['pid'], false ) );
				$response['iduser']   = $person['iduser'];
				$response['user']     = current_user( $person['iduser'], "yes" );
				$response['idautor']  = $params['autor'];
				$response['autor']    = current_user( $params['autor'], "yes" );

				if ( count( $des ) > 0 ) {
					$response['comment'] = yimplode( "\n", $des );
				}

				$response['theme'] = "CRM. Внесены изменения в карточку Контакта";
				$response['color'] = "#2196F3";

			break;
			case 'person.change.user':

				$person = get_person_info( $params['pid'], "yes" );

				$response['link']      = '<a href="'.$scheme.$server.'/card.person?pid='.$params['pid'].'">Ссылка</a>';
				$response['url']       = $scheme.$server.'/card.person?pid='.$params['pid'];
				$response['person']    = $person['person'];
				$response['status']    = $person['ptitle'];
				$response['client']    = current_client( $person['clid'] );
				$response['idautor']   = $params['autor'];
				$response['autor']     = current_user( $params['autor'], "yes" );
				$response['user']      = current_user( $params['newuser'], "yes" );
				$response['iduser']    = $params['newuser'];
				$response['userold']   = current_user( $params['olduser'], "yes" );
				$response['iduserold'] = $params['olduser'];

				if ( $params['comment'] != '' ) {
					$response['comment'] = $params['comment'];
				}

				$response['theme'] = "CRM. Изменен ответственный за Контакт";
				$response['color'] = "#2196F3";

			break;

			case 'deal.add':

				$response['link'] = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';
				$response['url']  = $scheme.$server.'/card.deal?did='.$params['did'];

				$deal = Deal ::info( $params['did'] );

				$response['did']        = $params['did'];
				$response['title']      = $deal['title'];
				$response['kol']        = num_format( $deal['summa'] );
				$response['marga']      = num_format( $deal['marga'] );
				$response['clid']       = $deal['client']['clid'];
				$response['client']     = $deal['client']['title'];
				$response['pid']        = $deal['person']['pid'];
				$response['person']     = $deal['person']['person'];
				$response['idstep']     = $deal['step']['stepid'];
				$response['step']       = $deal['step']['steptitle'];
				$response['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['iduser']     = $deal['iduser'];
				$response['user']       = $deal['user'];
				$response['idautor']    = $deal['autor'];
				$response['autor']      = $deal['autorName'];

				$response['tip']       = $deal['tipName'];
				$response['direction'] = $deal['directionName'];

				if ( $params['comment'] != '' ) {
					$response['comment'] = "<br>Комментарий: <b>".$params['comment']."</b><br>";
				}

				$response['theme'] = "CRM. Создана новая Сделка";
				$response['color'] = "#e53935";

			break;
			case 'deal.edit':

				$des  = $this -> Changes( 'deal', $params['newparam'] );
				$deal = Deal ::info( $params['did'] );

				$response['link']       = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';
				$response['url']        = $scheme.$server.'/card.deal?did='.$params['did'];
				$response['did']        = $params['did'];
				$response['title']      = $deal['title'];
				$response['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['kol']        = num_format( $deal['summa'] );
				$response['marga']      = num_format( $deal['marga'] );
				$response['clid']       = $deal['client']['clid'];
				$response['client']     = $deal['client']['title'];
				$response['pid']        = $deal['person']['pid'];
				$response['person']     = $deal['person']['person'];
				$response['idstep']     = $deal['step']['stepid'];
				$response['step']       = $deal['step']['steptitle'];
				$response['iduser']     = $deal['iduser'];
				$response['user']       = $deal['user'];
				$response['idautor']    = $params['autor'];
				$response['autor']      = current_user( $params['autor'], "yes" );

				$response['tip']       = $deal['tipName'];
				$response['direction'] = $deal['directionName'];

				if ( $params['comment'] != '' ) {
					$response['comment'] = "Комментарий: <b>".$params['comment']."</b>\n";
				}

				if ( count( $des ) > 0 ) {
					$response['comment'] .= yimplode( "\n", $des );
				}

				$response['theme'] = "CRM. Изменена Сделка";
				$response['color'] = "#2196F3";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;
			case 'deal.change.user':

				$response['link'] = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';
				$response['url']  = $scheme.$server.'/card.deal?did='.$params['did'];

				$deal = Deal ::info( $params['did'] );

				$response['did']        = $params['did'];
				$response['title']      = $deal['title'];
				$response['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['kol']        = num_format( $deal['kol'] );
				$response['marga']      = num_format( $deal['marga'] );
				$response['clid']       = $deal['client']['clid'];
				$response['client']     = $deal['client']['title'];
				$response['pid']        = $deal['person']['pid'];
				$response['person']     = $deal['person']['person'];
				$response['step']       = $deal['step'];
				$response['stepName']   = $deal['stepName'];
				$response['idautor']    = $params['autor'];
				$response['autor']      = current_user( $params['autor'], "yes" );
				$response['user']       = current_user( $params['newuser'], "yes" );
				$response['iduser']     = $params['newuser'];
				$response['userold']    = current_user( $params['olduser'], "yes" );
				$response['iduserold']  = $params['olduser'];

				$response['tip']       = $deal['tipName'];
				$response['direction'] = $deal['directionName'];

				if ( $params['comment'] != '' ) {
					$response['comment'] = "Комментарий: <b>".$params['comment']."</b>\n";
				}

				$response['theme'] = "CRM. Изменен куратор Сделки";
				$response['color'] = "#2196F3";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;
			case 'deal.change.step':

				$response['link'] = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';
				$response['url']  = $scheme.$server.'/card.deal?did='.$params['did'];

				$deal = Deal ::info( $params['did'] );

				$response['did']        = $params['did'];
				$response['title']      = $deal['title'];
				$response['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['kol']        = num_format( $deal['summa'] );
				$response['marga']      = num_format( $deal['marga'] );
				$response['clid']       = $deal['client']['clid'];
				$response['client']     = $deal['client']['title'];
				$response['pid']        = $deal['person']['pid'];
				$response['person']     = $deal['person']['person'];
				$response['step']       = $params['stepNew'];
				$response['stepOld']    = $params['stepOld'];
				$response['idautor']    = $params['autor'];
				$response['autor']      = current_user( $params['autor'], "yes" );
				$response['user']       = current_user( $deal['iduser'], "yes" );
				$response['iduser']     = $deal['iduser'];

				$response['tip']       = $deal['tipName'];
				$response['direction'] = $deal['directionName'];

				$response['comment'] = "Текущий этап: ".$response['step']."%\nПредыдущий этап: ".$response['stepOld']."%\n";

				if ( $params['comment'] != '' ) {
					$response['comment'] .= "Комментарий: ".str_replace( "<br>", "\n", $params['comment'] )."\n";
				}

				$response['theme'] = "CRM. Изменен этап сделки Сделки";
				$response['color'] = "#2196F3";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;
			case 'deal.close':

				$response['link'] = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'">Ссылка</a>';
				$response['url']  = $scheme.$server.'/card.deal?did='.$params['did'];

				$deal = Deal ::info( $params['did'] );

				$response['did']         = $params['did'];
				$response['title']       = $deal['title'];
				$response['datum_plan']  = format_date_rus( $deal['datum_plan'] );
				$response['datum_close'] = format_date_rus( $deal['close']['date'] );
				$response['kol']         = num_format( $deal['summa'] );
				$response['kol_fact']    = num_format( $deal['close']['summa'] );
				$response['marga']       = num_format( $deal['marga'] );
				$response['clid']        = $deal['client']['clid'];
				$response['client']      = $deal['client']['title'];
				$response['pid']         = $deal['person']['pid'];
				$response['person']      = $deal['person']['person'];
				$response['step']        = $deal['step']['steptitle'];
				$response['status']      = $deal['close']['status'];
				$response['iduser']      = $deal['iduser'];
				$response['user']        = $deal['user'];
				$response['idautor']     = $params['autor'];
				$response['autor']       = current_user( $params['autor'], "yes" );

				$response['tip']       = $deal['tipName'];
				$response['direction'] = $deal['directionName'];

				$response['comment'] = "Текущий этап: <b>".$response['step']."%</b>\nСтатус закрытия: <b>".$response['status']."</b>\nПлановая сумма: <b>".num_format( $deal['summa'] )."</b>\nФактическая сумма: <b>".num_format( $response['kol_fact'] )." ".$valuta."</b>\nФактическая маржа: <b>".num_format( $deal['marga'] )."</b>\nПлановая дата: <b>".$response['datum_plan']."</b>\nФактическая дата: <b>".$response['datum_close']."</b>\n";

				$response['theme'] = "CRM. Закрыта сделка";
				if ( $response['kol_fact'] <= 0 ) {
					$response['color'] = "#FF5722";
				}
				else {
					$response['color'] = "#4CAF50";
				}

				//формируем статус счетов по сделке
				$totalCredit = $totalCreditSumma = $doCredit = $doCreditSumma = 0;

				$re = $db -> getAll( "SELECT `do`, `summa_credit` FROM {$sqlname}credit WHERE did = '".$params['did']."' and identity = '$identity'" );
				foreach ( $re as $data ) {

					$totalCredit++;
					$totalCreditSumma += $data['summa_credit'];

					if ( $data['do'] == 'on' ) {
						$doCreditSumma += $data['summa_credit'];
						$doCredit++;
					}

				}

				$persent   = num_format( $totalCreditSumma / $deal['close']['summa'] * 100 );
				$persentDo = num_format( $doCreditSumma / $deal['close']['summa'] * 100 );

				$response['comment'] .= "Всего: ".$totalCredit." счетов по сделке на сумму ".num_format( $totalCreditSumma )." ".$valuta.". (".$persent."%). Оплачено ".$doCredit." счетов на сумму ".num_format( $doCreditSumma )." ".$valuta." (".$persentDo."%). \r\n";
				$response['comment'] .= ($doCreditSumma < $response['kol_fact']) ? "Не полная оплата по сделке" : "Полная оплата";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;

			case 'invoice.add':

				$invoice = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid='".$params['id']."' and identity = '$identity'" );

				$invoice['rs'] = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id='".$invoice['rs']."' and identity = '$identity'" );

				$response = [
					'invoice'   => $invoice['invoice'],
					'date'      => get_sfdate2( $invoice['datum'] ),
					'date_plan' => format_date_rus( $invoice['datum_credit'] ),
					'summa'     => num_format( $invoice['summa_credit'] ),
					'nalog'     => num_format( $invoice['nds_credit'] ),
					'contract'  => $invoice['invoice_chek'],
					'rs'        => $invoice['rs'],
					'tip'       => $invoice['tip'],
					//'did'       => $invoice['did'],
					//'clid'      => $invoice['clid'],
					'client'    => current_client( $invoice['clid'] ),
				];

				$response['did']     = $invoice['did'];
				$response['clid']    = $invoice['clid'];
				$response['pid']     = $invoice['pid'];
				$response['iduser']  = $invoice['iduser'];
				$response['user']    = current_user( $invoice['iduser'] );
				$response['idautor'] = $params['autor'];
				$response['autor']   = current_user( $params['autor'], "yes" );

				$deal = Deal ::info( $params['did'] );

				$response['deal']['title']      = $deal['title'];
				$response['deal']['kol']        = num_format( $deal['summa'] );
				$response['deal']['marga']      = num_format( $deal['marga'] );
				$response['deal']['clid']       = $deal['client']['clid'];
				$response['deal']['client']     = $deal['client']['title'];
				$response['deal']['idstep']     = $deal['step']['stepid'];
				$response['deal']['step']       = $deal['step']['steptitle'];
				$response['deal']['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['deal']['iduser']     = $deal['iduser'];
				$response['deal']['user']       = $deal['user'];
				$response['deal']['tip']        = $deal['tipName'];
				$response['deal']['direction']  = $deal['directionName'];

				$response['link']  = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'#7">Ссылка</a>';
				$response['url']   = $scheme.$server.'/card.deal?did='.$params['did'].'#7';
				$response['theme'] = "CRM. Выставлен счет";
				$response['color'] = "#e53935";

				//формируем статус счетов по сделке
				$totalCredit = $totalCreditSumma = $doCredit = $doCreditSumma = 0;

				$re = $db -> getAll( "SELECT `do`, `summa_credit` FROM {$sqlname}credit WHERE did = '".$params['did']."' and identity = '$identity'" );
				foreach ( $re as $data ) {

					$totalCredit++;
					$totalCreditSumma += $data['summa_credit'];

					if ( $data['do'] == 'on' ) {
						$doCreditSumma += $data['summa_credit'];
						$doCredit++;
					}

				}

				$persent   = num_format( $totalCreditSumma / $deal['summa'] * 100 );
				$persentDo = num_format( $doCreditSumma / $deal['summa'] * 100 );

				$response['invoiceStatus'] = "Всего: ".$totalCredit." счетов по сделке на сумму ".num_format( $totalCreditSumma )." ".$valuta.". (".$persent."%). Оплачено ".$doCredit." счетов на сумму ".num_format( $doCreditSumma )." ".$valuta." (".$persentDo."%). \n";
				$response['invoiceStatus'] .= ($doCreditSumma < $deal['summa']) ? "Не полная оплата по сделке" : "Полная оплата";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;
			case 'invoice.edit':

				$invoice = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid='".$params['id']."' and identity = '$identity'" );

				$invoice['rs'] = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id='".$invoice['rs']."' and identity = '$identity'" );

				$response = [
					'invoice'   => $invoice['invoice'],
					'date'      => get_sfdate2( $invoice['datum'] ),
					'date_plan' => format_date_rus( $invoice['datum_credit'] ),
					'summa'     => num_format( $invoice['summa_credit'] ),
					'nalog'     => num_format( $invoice['nds_credit'] ),
					'contract'  => $invoice['invoice_chek'],
					'rs'        => $invoice['rs'],
					'tip'       => $invoice['tip'],
					//'clid'      => $invoice['clid'],
					'client'    => current_client( $invoice['clid'] )
				];

				$response['did']     = $invoice['did'];
				$response['clid']    = $invoice['clid'];
				$response['pid']     = $invoice['pid'];
				$response['iduser']  = $invoice['iduser'];
				$response['user']    = current_user( $invoice['iduser'] );
				$response['idautor'] = $params['autor'];
				$response['autor']   = current_user( $params['autor'], "yes" );

				$deal = Deal ::info( $params['did'] );

				$response['deal']['title']      = $deal['title'];
				$response['deal']['kol']        = num_format( $deal['summa'] );
				$response['deal']['marga']      = num_format( $deal['marga'] );
				$response['deal']['clid']       = $deal['client']['clid'];
				$response['deal']['client']     = $deal['client']['title'];
				$response['deal']['idstep']     = $deal['step']['stepid'];
				$response['deal']['step']       = $deal['step']['steptitle'];
				$response['deal']['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['deal']['iduser']     = $deal['iduser'];
				$response['deal']['user']       = $deal['user'];
				$response['deal']['tip']        = $deal['tipName'];
				$response['deal']['direction']  = $deal['directionName'];

				$response['link']  = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'#7">Ссылка</a>';
				$response['url']   = $scheme.$server.'/card.deal?did='.$params['did'].'#7';
				$response['theme'] = "CRM. Изменен счет";
				$response['color'] = "#2196F3";

				//формируем статус счетов по сделке
				$totalCredit = $totalCreditSumma = $doCredit = $doCreditSumma = 0;

				$re = $db -> getAll( "SELECT `do`, `summa_credit` FROM {$sqlname}credit WHERE did = '".$params['did']."' and identity = '$identity'" );
				foreach ( $re as $data ) {

					$totalCredit++;
					$totalCreditSumma += $data['summa_credit'];

					if ( $data['do'] == 'on' ) {
						$doCreditSumma += $data['summa_credit'];
						$doCredit++;
					}

				}

				$persent   = num_format( $totalCreditSumma / $deal['summa'] * 100 );
				$persentDo = num_format( $doCreditSumma / $deal['summa'] * 100 );

				$response['invoiceStatus'] = "Всего: ".$totalCredit." счетов по сделке на сумму ".num_format( $totalCreditSumma )." ".$valuta.". (".$persent."%). Оплачено ".$doCredit." счетов на сумму ".num_format( $doCreditSumma )." ".$valuta." (".$persentDo."%). \n";
				$response['invoiceStatus'] .= ($doCreditSumma < $deal['summa']) ? "Не полная оплата по сделке" : "Полная оплата";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;
			case 'invoice.doit':

				$invoice = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid='".$params['id']."' and identity = '$identity'" );

				$invoice['rs'] = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id='".$invoice['rs']."' and identity = '$identity'" );

				$invoice['do'] = ($invoice['do'] == 'on') ? "Оплачен" : "Не оплачен";

				$response = [
					'invoice'     => $invoice['invoice'],
					'date'        => get_sfdate2( $invoice['datum'] ),
					'date_plan'   => format_date_rus( $invoice['datum_credit'] ),
					'summaCredit' => num_format( $invoice['summa_credit'] ),
					'summa'       => num_format( $params['summa'] ),
					'summaNew'    => num_format( $params['summaNew'] ),
					'nalog'       => $invoice['nds_credit'],
					'do'          => $invoice['do'],
					'date_do'     => format_date_rus( $invoice['invoice_date'] ),
					'contract'    => $invoice['invoice_chek'],
					'rs'          => $invoice['rs'],
					'tip'         => $invoice['tip'],
					//'clid'        => $invoice['clid'],
					'client'      => current_client( $invoice['clid'] )
				];

				$response['did']     = $invoice['did'];
				$response['clid']    = $invoice['clid'];
				$response['pid']     = $invoice['pid'];
				$response['iduser']  = $invoice['iduser'];
				$response['user']    = current_user( $invoice['iduser'] );
				$response['idautor'] = $params['autor'];
				$response['autor']   = current_user( $params['autor'], "yes" );

				$deal = Deal ::info( $params['did'] );

				$response['deal']['title']      = $deal['title'];
				$response['deal']['kol']        = num_format( $deal['summa'] );
				$response['deal']['marga']      = num_format( $deal['marga'] );
				$response['deal']['clid']       = $deal['client']['clid'];
				$response['deal']['client']     = $deal['client']['title'];
				$response['deal']['idstep']     = $deal['step']['stepid'];
				$response['deal']['step']       = $deal['step']['steptitle'];
				$response['deal']['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['deal']['iduser']     = $deal['iduser'];
				$response['deal']['user']       = $deal['user'];
				$response['deal']['tip']        = $deal['tipName'];
				$response['deal']['direction']  = $deal['directionName'];

				$response['link']  = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'#7">Ссылка</a>';
				$response['url']   = $scheme.$server.'/card.deal?did='.$params['did'].'#7';
				$response['theme'] = "CRM. Оплачен счет";
				$response['color'] = "#4CAF50";

				//формируем статус счетов по сделке
				$totalCredit = $totalCreditSumma = $doCredit = $doCreditSumma = 0;

				$re = $db -> getAll( "SELECT `do`, `summa_credit` FROM {$sqlname}credit WHERE did = '".$params['did']."' and identity = '$identity'" );
				foreach ( $re as $data ) {

					$totalCredit++;
					$totalCreditSumma += $data['summa_credit'];

					if ( $data['do'] == 'on' ) {
						$doCreditSumma += $data['summa_credit'];
						$doCredit++;
					}

				}

				$persent   = num_format( $totalCreditSumma / $deal['summa'] * 100 );
				$persentDo = num_format( $doCreditSumma / $deal['summa'] * 100 );

				$response['invoiceStatus'] = "Всего: ".$totalCredit." счетов по сделке на сумму ".num_format( $totalCreditSumma )." ".$valuta." (".$persent."%). Оплачено ".$doCredit." счетов на сумму ".num_format( $doCreditSumma )." ".$valuta." (".$persentDo."%). \n";
				$response['invoiceStatus'] .= ($doCreditSumma < $deal['summa']) ? "Не полная оплата по сделке" : "Полная оплата";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;
			case 'invoice.expressadd':

				$invoice = $db -> getRow( "SELECT * FROM {$sqlname}credit WHERE crid='".$params['id']."' and identity = '$identity'" );

				$invoice['rs'] = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id='".$invoice['rs']."' and identity = '$identity'" );

				$invoice['do'] = ($invoice['do'] == 'on') ? "Оплачен" : "Не оплачен";

				$response = [
					'invoice'   => $invoice['invoice'],
					'date'      => get_sfdate2( $invoice['datum'] ),
					'date_plan' => format_date_rus( $invoice['datum_credit'] ),
					'summa'     => num_format( $params['summa'] ),
					'summaNew'  => num_format( $params['summaNew'] ),
					'nalog'     => $invoice['nds_credit'],
					'do'        => $invoice['do'],
					'date_do'   => format_date_rus( $invoice['invoice_date'] ),
					'contract'  => $invoice['invoice_chek'],
					'rs'        => $invoice['rs'],
					'tip'       => $invoice['tip'],
					//'clid'      => $invoice['clid'],
					'client'    => current_dogovor( $invoice['clid'] )
				];

				$response['did']     = $invoice['did'];
				$response['clid']    = $invoice['clid'];
				$response['pid']     = $invoice['pid'];
				$response['iduser']  = $invoice['iduser'];
				$response['user']    = current_user( $invoice['iduser'] );
				$response['idautor'] = $params['autor'];
				$response['autor']   = current_user( $params['autor'], "yes" );

				$deal = Deal ::info( $params['did'] );

				$response['deal']['title']      = $deal['title'];
				$response['deal']['kol']        = num_format( $deal['summa'] );
				$response['deal']['marga']      = num_format( $deal['marga'] );
				$response['deal']['clid']       = $deal['client']['clid'];
				$response['deal']['client']     = $deal['client']['title'];
				$response['deal']['idstep']     = $deal['step']['stepid'];
				$response['deal']['step']       = $deal['step']['steptitle'];
				$response['deal']['datum_plan'] = format_date_rus( $deal['datum_plan'] );
				$response['deal']['iduser']     = $deal['iduser'];
				$response['deal']['user']       = $deal['user'];
				$response['deal']['tip']        = $deal['tipName'];
				$response['deal']['direction']  = $deal['directionName'];

				//формируем статус счетов по сделке
				$totalCredit = $totalCreditSumma = $doCredit = $doCreditSumma = 0;

				$re = $db -> getAll( "SELECT `do`, `summa_credit` FROM {$sqlname}credit WHERE did = '".$params['did']."' and identity = '$identity'" );
				foreach ( $re as $data ) {

					$totalCredit++;
					$totalCreditSumma += $data['summa_credit'];

					if ( $data['do'] == 'on' ) {
						$doCreditSumma += $data['summa_credit'];
						$doCredit++;
					}

				}

				$persent   = num_format( $totalCreditSumma / $deal['summa'] * 100 );
				$persentDo = num_format( $doCreditSumma / $deal['summa'] * 100 );

				$response['invoiceStatus'] = "Всего: ".$totalCredit." счетов по сделке на сумму ".num_format( $totalCreditSumma )." ".$valuta." (".$persent."%). Оплачено ".$doCredit." счетов на сумму ".num_format( $doCreditSumma )." ".$valuta." (".$persentDo."%). \n";
				$response['invoiceStatus'] .= ($doCreditSumma < $response['deal']['kol']) ? "Не полная оплата по сделке" : "Полная оплата";

				$response['link']  = '<a href="'.$scheme.$server.'/card.deal?did='.$params['did'].'#7">Ссылка</a>';
				$response['url']   = $scheme.$server.'/card.deal?did='.$params['did'].'#7';
				$response['theme'] = "CRM. Внесена оплата по сделке";
				$response['color'] = "#4CAF50";

				//проверим доступы к сделке и передадим массив пользователей, которые должны получать уведомления по сделке
				$response['users'] = $db -> getCol( "SELECT iduser FROM {$sqlname}dostup WHERE did = '$params[did]' and subscribe = 'on' and identity = '$identity'" );

			break;

			//todo: доработать events, webhook
			case 'lead.add':

				$lead = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."'" );

				$response['iduser'] = $params['iduser'];
				$response['user']   = current_user( $params['iduser'] );
				$response['link']   = '<a href="'.$scheme.$server.'/leads?id='.$params['id'].'">Ссылка</a>';
				$response['url']    = $scheme.$server.'/leads?id='.$params['id'];
				$response['client'] = ($lead['clid'] > 0) ? current_client( $lead['clid'] ) : "--нет в базе--";
				$response['person'] = ($lead['pid'] > 0) ? current_person( $lead['pid'] ) : "--нет в базе--";

				$response['idclientpath'] = $lead['clientpath'];
				$response['clientpath']   = ($lead['clientpath'] > 0) ? current_clientpathbyid( $lead['clientpath'] ) : '--не определен--';

				if ( $response['iduser'] == 0 ) {
					$response['user'] = 'Необходимо назначить';
				}

				$response['comment'] = "
					Дата получения: ".get_sfdate( $lead['datum'] )."
					Имя: ".$lead['title']."
					Email: ".$lead['email']."
					Телефон: ".formatPhone( $lead['phone'] )."
					Страна, Город: ".$lead['country'].", ".$lead['city'];

				if ( $lead['clid'] > 0 ) {
					$response['comment'] .= "Клиент: ".$response['client']."\n";
				}
				if ( $lead['pid'] > 0 ) {
					$response['comment'] .= "Контакт: ".$response['person']."\n";
				}

				$response['theme'] = "CRM. Новый Входящий интерес (Лид). ID = ".$params['id'];
				$response['color'] = "#e53935";

			break;
			case 'lead.setuser':

				$lead = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."'" );

				$response['iduser'] = $lead['iduser'];
				$response['user']   = current_user( $lead['iduser'] );
				$response['link']   = '<a href="'.$scheme.$server.'/leads?id='.$params['id'].'">Ссылка</a>';
				$response['url']    = $scheme.$server.'/leads?id='.$params['id'];
				$response['client'] = ($lead['clid'] > 0) ? current_client( $lead['clid'] ) : "";
				$response['person'] = ($lead['pid'] > 0) ? current_person( $lead['pid'] ) : "";

				$response['idclientpath'] = $lead['clientpath'];
				$response['clientpath']   = ($lead['clientpath'] > 0) ? current_clientpathbyid( $lead['clientpath'] ) : '--не определен--';

				$response['comment'] = "
					Дата получения: ".get_sfdate( $lead['datum'] )."
					Имя: ".$lead['title']."
					Email: ".$lead['email']."
					Телефон: ".formatPhone( $lead['phone'] )."
					Страна, Город: ".$lead['country'].", ".$lead['city'];

				if ( $lead['clid'] > 0 ) {
					$response['comment'] .= "Клиент: ".$response['client']."\n";
				}
				if ( $lead['pid'] > 0 ) {
					$response['comment'] .= "Контакт: ".$response['person']."\n";
				}

				$response['users'] = [];

				$response['users'][] = (int)$params['coordinator'];

				$response['theme'] = "CRM. Назначен ответственный за обработку Входящего интереса (Лида)";
				$response['color'] = "#2196F3";

			break;
			case 'lead.do':

				$lead = $db -> getRow( "SELECT * FROM {$sqlname}leads WHERE id = '".$params['id']."' AND identity = '".$identity."'" );

				$response['iduser'] = $lead['iduser'];
				$response['user']   = current_user( $lead['iduser'] );
				$response['link']   = '<a href="'.$scheme.$server.'/leads?id='.$params['id'].'">Ссылка</a>';
				$response['url']    = $scheme.$server.'/leads?id='.$params['id'];
				$response['client'] = ((int)$lead['clid'] > 0) ? current_client( $lead['clid'] ) : "";
				$response['person'] = ((int)$lead['pid'] > 0) ? current_person( $lead['pid'] ) : "";
				$response['deal']   = ((int)$lead['did'] > 0) ? current_dogovor( $lead['did'] ) : "";
				$rez                = (int)$lead['rezult'];
				$status             = (int)$lead['status'];

				$response['idclientpath'] = $lead['clientpath'];
				$response['clientpath']   = ($lead['clientpath'] > 0) ? current_clientpathbyid( $lead['clientpath'] ) : '--не определен--';

				$rezult   = [
					1 => 'Спам',
					2 => 'Дубль',
					3 => 'Другое',
					4 => 'Не целевой'
				];
				$statuses = [
					0 => 'Открыт',
					1 => 'В работе',
					2 => 'Обработан',
					3 => 'Закрыт'
				];

				$response['comment'] = '';

				if ( $rez > 0 ) {
					$response['comment'] .= "Входящий интерес дисквалифицирован с результатом: ".strtr( $rez, $rezult )."\n";
				}
				else {
					$response['comment'] .= "Входящий интерес квалифицирован";
					if ( $lead['did'] > 0 ) {
						$response['comment'] .= ': Создана сделка.';
					}
					$response['comment'] .= "\n";
				}

				$response['statusClose'] = strtr( $rez, $rezult );
				$response['status']      = strtr( $status, $statuses );

				if ( (int)$lead['clid'] > 0 ) {
					$response['comment'] .= "Клиент: ".$response['client']."\n";
				}
				if ( (int)$lead['pid'] > 0 ) {
					$response['comment'] .= "Контакт: ".$response['person']."\n";
				}
				if ( (int)$lead['did'] > 0 ) {
					$response['comment'] .= "Сделка: ".$response['deal']."\n";
				}

				$response['theme'] = "CRM. Обработан входящий интерес (Лид)";
				$response['color'] = "#4CAF50";

				$response['users'][] = $params['coordinator'];

			break;

			case 'entry.add':

				$response           = $db -> getRow( "SELECT * FROM {$sqlname}entry WHERE ide = '".$params['id']."' AND identity = '".$identity."'" );
				$response['autor']  = current_user( $params['autor'] );
				$response['user']   = current_user( $response['iduser'] );
				$response['link']   = '<a href="'.$scheme.$server.'/entry?id='.$params['id'].'">Ссылка</a>';
				$response['url']    = $scheme.$server.'/entry?id='.$params['id'];
				$response['client'] = ((int)$response['clid'] > 0) ? current_client( $response['clid'] ) : "";
				$response['person'] = ((int)$response['pid'] > 0) ? current_person( $response['pid'] ) : "";
				$response['deal']   = ((int)$response['did'] > 0) ? current_dogovor( $response['did'] ) : "";
				$rez                = (int)$response['status'];

				$rezult = [
					0 => 'Новое',
					1 => 'Обработано',
					2 => 'Отменено'
				];

				$response['comment'] = '';

				if ( $rez == '0' ) {
					$response['comment'] .= "Статус: создано\n";
				}
				elseif ( $rez == '2' ) {
					$response['comment'] .= "Статус: закрыто с результатом: ".strtr( $rez, $rezult )."\n";
				}
				elseif ( $rez == '1' ) {
					$response['comment'] .= "Статус: обработано";
					if ( $response['did'] > 0 ) {
						$response['comment'] .= ': Создана сделка.';
					}
					$response['comment'] .= "\n";
				}

				$response['status'] = strtr( $rez, $rezult );

				if ( $response['client'] != '' ) {
					$response['comment'] .= 'Клиент: <a href="'.$scheme.$server.'/card.client?clid='.$response['clid'].'">'.$response['client'].'</a>';
					$response['comment'] .= "\n";
				}

				if ( $response['person'] != '' ) {
					$response['comment'] .= 'Контакт: <a href="'.$scheme.$server.'/card.person?pid='.$response['pid'].'">'.$response['person'].'</a>';
					$response['comment'] .= "\n";
				}
				if ( $response['did'] > 0 ) {
					$response['comment'] .= 'Сделка: <a href="'.$scheme.$server.'/card.deal?did='.$response['did'].'">'.$response['deal'].'</a>';
					$response['comment'] .= "\n";
				}

				$response['theme'] = "CRM. Добавлено обращение";
				$response['color'] = "#e53935";

			break;
			case 'entry.status':

				$response           = $db -> getRow( "SELECT * FROM {$sqlname}entry WHERE ide = '".$params['id']."' AND identity = '".$identity."'" );
				$response['autor']  = current_user( $params['autor'] );
				$response['user']   = current_user( $response['iduser'] );
				$response['link']   = '<a href="'.$scheme.$server.'/entry?id='.$params['id'].'">Ссылка</a>';
				$response['url']    = $scheme.$server.'/entry?id='.$params['id'];
				$response['client'] = ($response['clid'] > 0) ? current_client( $response['clid'] ) : "";
				$response['person'] = ($response['pid'] > 0) ? current_person( $response['pid'] ) : "";
				$response['deal']   = ($response['did'] > 0) ? current_dogovor( $response['did'] ) : "";
				$rez                = $response['status'];

				$rezult = [
					'0' => 'Новое',
					'1' => 'Обработано',
					'2' => 'Отменено'
				];

				$response['comment'] = '';

				if ( $rez == '2' ) {
					$response['comment'] .= "Обращение закрыто с результатом: ".strtr( $rez, $rezult )."\n";
				}
				elseif ( $rez == '1' ) {
					$response['comment'] .= "Обращение обработано";
					if ( $response['did'] > 0 ) {
						$response['comment'] .= ': Создана сделка.';
					}
					$response['comment'] .= "\n";
				}

				$response['status'] = strtr( $rez, $rezult );

				if ( $response['client'] != '' ) {
					$response['client'] = '<a href="'.$scheme.$server.'/card.client?clid='.$response['clid'].'">'.$response['client'].'</a>';
					//$response['comment'] .= "\n";
				}

				if ( $response['pid'] > 0 ) {
					$response['person'] = '<a href="'.$scheme.$server.'/card.person?pid='.$response['pid'].'">'.$response['person'].'</a>';
					//$response['comment'] .= "\n";
				}

				if ( $response['did'] > 0 ) {
					$response['deal'] = '<a href="'.$scheme.$server.'/card.deal?did='.$response['did'].'">'.$response['deal'].'</a>';
				}

				$response['theme'] = "CRM. Закрыто обращение";
				$response['color'] = "#4CAF50";

			break;

		}

		return $response;

	}

	/**
	 * Формирование блока измененных данных по Клиенту, Контакту, Сделке. Из плагина UserNotifier
	 *
	 * @param $tip
	 * @param $params
	 * @return array
	 * @throws Exception
	 */
	public function Changes($tip, $params): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$db       = $GLOBALS['db'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];

		if ( $params['identity'] > 0 ) {
			$identity = $params['identity'];
		}

		$des = [];

		switch ($tip) {

			case 'client':

				//массив имен полей
				$titles = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='client' and fld_on='yes' and identity = '$identity' order by fld_order" );

				$titles['type'] = 'Тип записи';

				foreach ( $params as $key => $value ) {

					switch ($key) {

						case 'type':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.strtr( $value, [
									"client"     => "Клиент Юр.лицо",
									"person"     => "Клиент Физ.лицо",
									"partner"    => "Партнер",
									"contractor" => "Поставщик",
									"concurent"  => "Конкурент"
								] );

						break;
						case 'idcategory':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.get_client_category( $value );

						break;
						case 'clientpath':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_clientpathbyid( $value );

						break;
						case 'territory':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_territory( $value );

						break;
						case 'head_clid':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_client( $value );

						break;
						case 'pid':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_person( $value );

						break;
						default:

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.$value;

						break;
					}

				}

			break;
			case 'person':

				//массив имен полей
				$titles = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='person' and fld_on='yes' and identity = '$identity' order by fld_order" );

				foreach ( $params as $key => $value ) {

					switch ($key) {
						case 'clientpath':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_clientpathbyid( $value );

						break;
						case 'loyalty':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_loyalty( $value );

						break;
						default:

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.$value;

						break;
					}

				}

			break;
			case 'deal':

				//массив имен полей
				$t1 = [
					"clid"        => "Клиент по сделке",
					"payer"       => "Плательщик по сделке",
					"title"       => "Название",
					"content"     => "Описание",
					"tip"         => "Тип сделки",
					"idcategory"  => "Этап сделки",
					"adres"       => "Адрес",
					"datum_plan"  => "Дата план",
					"datum_start" => "Период.Начало",
					"datum_end"   => "Период.Конец",
					"kol"         => "Оборот",
					"marg"        => "Маржа",
					"dog_num"     => "Договор",
					"pid_list"    => "Контакты сделки",
					"coid1"       => "Конкуренты по сделке"
				];

				$t2 = $db -> getIndCol( 'fld_name', "select fld_title, fld_name from {$sqlname}field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '$identity' order by fld_order" );

				$titles = array_merge( $t1, $t2 );

				foreach ( $params as $key => $value ) {

					switch ($key) {
						case 'clid':
						case 'payer':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_client( $value );

						break;
						case 'datum_plan':
						case 'datum_start':
						case 'datum_end':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.format_date_rus( $value );

						break;
						case 'tip':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_dogtype( (int)$value );

						break;
						case 'kol':
						case 'marg':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.num_format( $value );

						break;
						case 'dog_num':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_contract( $value );

						break;
						case 'idcategory':

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.current_dogstepname( $value ).'%';

						break;
						case 'pid_list':

							$pids = explode( ";", $value );
							$pn   = [];
							for ( $i = 0, $iMax = count( $pids ); $i < $iMax; $i++ ) {
								$pn[] = current_person( $pids[ $i ] );
							}
							$pns = implode( ', ', $pn );

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.$pns;

						break;
						case 'coid1':

							$pids = explode( ";", $value );
							$pn   = [];
							for ( $i = 0, $iMax = count( $pids ); $i < $iMax; $i++ ) {
								$pn[] = current_client( $pids[ $i ] );
							}
							$pns = implode( ', ', $pn );

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.$pns;

						break;
						default:

							$des[] = '<b>'.strtr( $key, $titles ).'</b>: '.$value;

						break;
					}

				}

			break;

		}

		return $des;

	}

}