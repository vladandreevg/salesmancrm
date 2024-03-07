<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

namespace Salesman;

/**
 * Класс для управления пользователями, а также выдачи структурированной информации
 *
 * Class User
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     1.0 (06/09/2019)
 */
class User {

	/**
	 * Информация о пользователе + настройки доступа + персональные настройки
	 *
	 * @param       $id
	 * @param array $params
	 *
	 * @return array - массив с результатом
	 *  - result
	 *      - **title** - ФИО
	 *      - **tip** - Роль
	 *      - **otdel** - id отдела
	 *      - **mid** - iduser руководителя
	 *      - **territory** - id территории
	 *      - **office** - id офиса
	 *      - **phone** - теефон
	 *      - **fax** - факс
	 *      - **mob** - мобильный
	 *      - **email** - эл.почта
	 *      - **bday** - дата рождения (1976-02-29)
	 *      - **tzoffset** - смещение относительно времени серера (+0)
	 *      - **zam** - iduser заместителя
	 *      - **isadmin** - является администратором = on|off
	 *      - **secrty** - активен = yes|no
	 *      - **user_post** - должность
	 *      - **uid** - уникальный идентификатор DIR100
	 *      - **CompStart** - дата приема в компанию ( 2007-04-01 )
	 *      - **CompEnd** - дата увольнения ( 0000-00-00 )
	 *
	 *      - **show_marga** - видит маржу = yes|no
	 *      - **plan** - имеет план = on|off
	 *
	 *      - array **dostup** - доступы к разделам
	 *          - **analitics** - к отчетам = on|off
	 *          - **maillist** - к рассылкам = on|off
	 *          - **files** - к файлам = on|off
	 *          - **price** - к прайсу = on|off
	 *          - **group** - в группы = on|off
	 *          - **contractor** - к контрагентам = on|off
	 *
	 *      - array **rights** - права
	 *          - **invoiceDo** - может ставить оплаты = on|off
	 *          - **export** -Может экспортировать = on|off
	 *          - **import** - Может импортировать = on|off
	 *          - **delete** - Может удалять Файлы, Активности = on|off
	 *          - **viewOtherTask** - Видит чужие активности = on|off
	 *          - **massActions** - Доступ к массовым операциям = on|off
	 *          - **allInMenu** - Доступ к меню "Все сделки" = on|off
	 *          - **budjet** - Доступ к меню "Бюджет" = on|off
	 *          - **editTask** - Может редактировать напоминания = on|off
	 *          - **clientAdd** - Создание Клиентов = on|off
	 *          - **clientEdit** - изменение Клиентов = on|off
	 *          - **clientDelete** - удаление Клиентов = on|off
	 *          - **personAdd** - создание Контактов = on|off
	 *          - **personEdit** - изменение Контактов = on|off
	 *          - **personDelete** - удаление Контактов = on|off
	 *          - **dealAdd** - создание Сделок = on|off
	 *          - **dealEdit** - изменение Сделок = on|off
	 *          - **dealClosedEdit** - изменение закрытых Сделок, если он не админ = on|off
	 *          - **dealDelete** - удаление Сделок = on|off
	 *          - **dealsUnclose** - может всстанавливать сделки = on|off
	 *          - **dealsClose** - может закрывать Сделки = on|off
	 *          - **planPersonal** - индивидуальный план продаж - для руководителей = on|off
	 *          - **noChangeUser** - не может менять ответственных = on|off
	 *
	 *      - array **subscribes** - подписка на email-уведомления
	 *          - **client.new** - новый Клиент = on|off ( subscribe[0] )
	 *          - **client.send** - передача Клиента = on|off ( subscribe[1] )
	 *          - **client.delete** - удаление Клиента = on|off ( subscribe[2] )
	 *          - **person.new** - новый Контакт = on|off ( subscribe[3] )
	 *          - **person.send** - передача Контакта = on|off ( subscribe[4] )
	 *          - **deal.new** - новая Сделка = on|off ( subscribe[5] )
	 *          - **deal.edit** - изменена сделка = on|off ( subscribe[6] )
	 *          - **deal.close** - закрыта Сделка = on|off ( subscribe[7] )
	 *          - **task.ical** - файл Календаря = on|off ( subscribe[8] )
	 *          - **task.new** - новое Напоминание = on|off ( subscribe[9] )
	 *          - **task.do** - выполнено Напоминание = on|off ( subscribe[10] )
	 *          - **invoice.doit** - оплачен Счет = on|off ( subscribe[11] )
	 *
	 *      - array **usersettings** - различные настройки пользователя
	 *          - array **vigets - массив используемых виджетов
	 *          - **taskAlarm** - "напоминать" при добавлении напоминания = yes
	 *          - **userTheme** - тема - blue
	 *          - **userThemeRound** - скругление темы
	 *          - **startTab** - стартовая вкладка рабочего стола = vigets
	 *          - **menuClient** - переход из меню Клиенты = all
	 *          - **menuPerson** - переход из меню Контакты = all
	 *          - **menuDeal** - переход из меню Сделки = all
	 *          - **filterAllBy** - iduser руководителя, в рамках которого сотрудник будет видеть записи
	 *
	 *          - array **notify** - подписки на уведомления (см. Notify::EVENTS)
	 *
	 *  id - iduser сотрудника
	 */
	public static function info($id, array $params = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = (isset( $params['identity'] ) && $params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ( (int)$id > 0 ) {

			$res                = $db -> getRow( "SELECT * FROM ".$sqlname."user WHERE iduser = '$id' and identity = '$identity'" );
			$user['title']      = $res["title"];
			$user['tip']        = $res["tip"];
			$user['otdel']      = $res["otdel"];
			$user['mid']        = (int)$res["mid"];
			$user['territory']  = (int)$res["territory"];
			$user['office']     = (int)$res["office"];
			$user['phone']      = $res["phone"];
			$user['fax']        = $res["fax"];
			$user['mob']        = $res["mob"];
			$user['email']      = $res["email"];
			$user['bday']       = $res["bday"];
			$user['tzoffset']   = $res["tzone"];
			$user['zam']        = $res["zam"];
			$user['isadmin']    = $res["isadmin"];
			$user['secrty']     = $res["secrty"];
			$user['show_marga'] = $res["show_marga"];
			$user['user_post']  = $res["user_post"];
			$user['uid']        = $res["uid"];
			$user['CompStart']  = $res["CompStart"];
			$user['CompEnd']    = $res["CompEnd"];

			$user['acs_analitics'] = $res["acs_analitics"];
			$user['acs_maillist']  = $res["acs_maillist"];
			$user['acs_files']     = $res["acs_files"];
			$user['acs_price']     = $res["acs_price"];
			$user['acs_credit']    = $res["acs_credit"];
			$user['acs_prava']     = $res["acs_prava"];
			$user['plan']          = $res["acs_plan"];
			$user['import']        = $res["acs_import"];

			$user['ac_import'] = $rights = explode( ";", $res["acs_import"] );

			/**
			 * Права доступа к реазделам
			 */
			$user['dostup'] = [
				//доступ в Отчеты
				"analitics"  => $res["acs_analitics"],
				//доступ в Рассылки
				"maillist"   => $res["acs_maillist"],
				//доступ в Файлы
				"files"      => $res["acs_files"],
				//доступ в Прайс
				"price"      => $res["acs_price"],
				//доступ в Группы
				"group"      => $rights[17],
				//доступ в Связи
				"contractor" => $rights[18],
			];

			/**
			 * Права на выполнение действий
			 */
			$user['rights'] = [
				//может ставить оплаты
				"invoiceDo"      => $res["acs_credit"],
				//Может экспортировать
				"export"         => $rights[0],
				//Может импортировать
				"import"         => $rights[1],
				//Может удалять Организации, Персоны, Активности
				"delete"         => $rights[2],
				//Видит чужие активности
				"viewOtherTask"  => $rights[3],
				//Доступ к массовым операциям
				"massActions"    => $rights[4],
				//Доступ к меню "Все сделки"
				"allInMenu"      => $rights[5],
				//Доступ к меню "Бюджет"
				"budjet"         => $rights[6],
				//Может редактировать напоминания
				"editTask"       => $rights[7],
				//Создание организаций
				"clientAdd"      => $rights[8],
				//изменение организаций
				"clientEdit"     => $rights[9],
				//удаление организаций
				"clientDelete"   => $rights[10],
				//создание персон
				"personAdd"      => $rights[11],
				//изменение персон
				"personEdit"     => $rights[12],
				//удаление персон
				"personDelete"   => $rights[13],
				//создание сделок
				"dealAdd"        => $rights[14],
				//изменение сделок
				"dealEdit"       => $rights[15],
				//изменение закрытых сделок, если он не админ
				"dealClosedEdit" => $rights[23],
				//удаление сделок
				"dealDelete"     => $rights[16],
				//может всстанавливать сделки
				"dealsUnclose"   => $rights[21],
				//может закрывать сделки
				"dealsClose"     => $rights[22],
				//индивидуальный план продаж - для руководителей
				"planPersonal"   => $rights[19],
				//не может менять ответственных
				"noChangeUser"   => $rights[20]
			];

			/**
			 * taskAlarm - "напоминать" при добавлении напоминания
			 * userTheme - тема
			 * userThemeRound - скругление темы
			 * startTab - стартовая вкладка рабочего стола
			 * menuClient - переход из меню Клиенты
			 * menuPerson - переход из меню Контакты
			 * menuDeal - переход из меню Сделки
			 * notify - подписки на уведомления
			 * filterAllBy - хз
			 */
			$user['usersettings'] = json_decode( $res["usersettings"], true );

			$user['notify'] = $user['usersettings']['notify'];
			unset( $user['usersettings']['notify'] );

			$user['subscribe']  = explode( ";", $res["subscription"] );
			$user['subscribes'] = [
				"client.new"    => $user['subscribe'][0],
				"client.send"   => $user['subscribe'][1],
				"client.delete" => $user['subscribe'][2],
				"person.new"    => $user['subscribe'][3],
				"person.send"   => $user['subscribe'][4],
				"deal.new"      => $user['subscribe'][5],
				"deal.edit"     => $user['subscribe'][6],
				"deal.close"    => $user['subscribe'][7],
				"task.ical"     => $user['subscribe'][8],
				"task.new"      => $user['subscribe'][9],
				"task.do"       => $user['subscribe'][10],
				"invoice.doit"  => $user['subscribe'][11],
			];
			unset( $user['subscribe'] );

			$user['assistants'] = $db -> getCol("SELECT iduser FROM ".$sqlname."user WHERE zam = '$id' and identity = '$identity'");

			$response = [
				"result" => $user,
				"id"     => (int)$id
			];

		}
		else {

			$response = [
				"result" => 'Error',
				"error"  => "Не указаны параметры"
			];

		}

		return $response;

	}

	/**
	 * Настройки пользователя
	 * @param $id
	 * @return array
	 */
	public static function settings($id): array {

		global $identity, $sqlname, $db;

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$settingsUserFile = $rootpath."/cash/".$fpath."settings.user.{$id}.json";

		if ( file_exists( $settingsUserFile ) && filesize( $settingsUserFile ) > 0 ) {

			return (array)json_decode( (string)file_get_contents( $settingsUserFile ), true );

		}

		$result        = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser1' and identity = '$identity'" );
		$acs_analitics = $result["acs_analitics"];
		$acs_maillist  = $result["acs_maillist"];
		$acs_files     = $result["acs_files"];
		$acs_price     = $result["acs_price"];
		$acs_credit    = $result["acs_credit"];
		$acs_prava     = $result["acs_prava"];
		$acs_import    = $result["acs_import"];
		$acs_plan      = $result["acs_plan"];
		$show_marga    = $result["show_marga"];
		$tzone         = $result["tzone"];
		$isadmin       = $result["isadmin"];
		$tipuser       = $result["tip"];
		$avatar        = $result["avatar"];
		$userSettings  = json_decode( $result["usersettings"], true );

		$settingsUser = [
			"acs_analitics" => $acs_analitics,
			"acs_maillist"  => $acs_maillist,
			"acs_files"     => $acs_files,
			"acs_price"     => $acs_price,
			"acs_credit"    => $acs_credit,
			"acs_prava"     => $acs_prava,
			"acs_import"    => $acs_import,
			"acs_plan"      => $acs_plan,
			"show_marga"    => $show_marga,
			"tzone"         => $tzone,
			"isadmin"       => $isadmin,
			"tipuser"       => $tipuser,
			"avatar"        => $avatar,
			"userSettings"  => $userSettings
		];

		file_put_contents( $settingsUserFile, json_encode( $settingsUser ) );

		return $settingsUser;

	}

	/**
	 * Вывод имени пользователя
	 *
	 * @param        $id
	 * @param string $short = yes - укорачивать ФИО до ФИ
	 * @return string
	 */
	public static function userName($id, string $short = 'no'): string {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$sqlname = $GLOBALS['sqlname'];
		$db      = $GLOBALS['db'];

		$utitle = "Не определен";

		if ( (int)$id > 0 ) {

			$utitle = $db -> getOne( "SELECT title FROM ".$sqlname."user WHERE iduser = '$id'" );

			if ( $utitle == '' ) {
				$utitle = "Не определен";
			}

			if ( $short == 'yes' ) {

				$u = explode( " ", $utitle );
				if ( count( $u ) > 2 ) {
					$utitle = $u[0]." ".$u[1];
				}

			}

		}

		return $utitle;

	}

	/**
	 * Вывод массива сотрудников с краткой информацией
	 *
	 * @param null     $id    - если указан, то выводятся данные по сотруднику и его подчиненным
	 * @param int|null $level - ограничить уровень подчиненности
	 * @param null     $mid   - не заполняется
	 * @return array - массив с результатом
	 *
	 *      - int **id** - iduser
	 *      - str **title** - имя полное ( ФИО )
	 *      - str **name** - имя краткое ( ФИ )
	 *      - str **tip** - роль в системе
	 *      - str **secrty** - активность = yes|no
	 *      - str **active** - активность = yes|no
	 *      - str **avatar** - файл аватара
	 *      - int **level** - уровень подчиненности
	 *      - str **isadmin** - является администратором = on|off
	 *      - bool **canDeleted** - может быть удален = true|false
	 */
	public static function userArray($id = NULL, int $level = NULL, $mid = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$res  = [];
		$sort = '';

		if ( !$id && !$mid ) {
			$sort = " and mid = '0'";
		}
		elseif ( $id > 0 ) {
			$sort = " and iduser = '$id'";
		}
		if ( $mid > 0 ) {
			$sort = " and mid = '$mid'";
		}

		$re = $db -> getAll( "SELECT iduser, mid, title, tip, secrty, avatar, isadmin FROM ".$sqlname."user WHERE iduser > 0 $sort and identity = '$identity' ORDER BY field(COALESCE(secrty, 'no'), 'yes','no') ASC, mid, title" );
		//print $db -> lastQuery();
		foreach ( $re as $da ) {

			$uname = yexplode( " ", $da["title"] );

			$res[] = [
				"id"      => (int)$da["iduser"],
				"title"   => $da["title"],
				"name"    => $uname[0]." ".$uname[1],
				"tip"     => $da['tip'],
				"secrty"  => $da['secrty'],
				"active"  => $da['secrty'],
				"avatar"  => $da['avatar'],
				"level"   => (int)$level,
				"isadmin" => $da['isadmin'],
				//"canDeleted" => self ::canDeleted( $da[ "iduser" ] )
			];

			$count = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."user WHERE mid = '".$da["iduser"]."' AND identity = '$identity'" );
			if ( $count > 0 ) {

				$level++;
				$u = self ::userArray( NULL, $level, (int)$da["iduser"] );
				$level--;

				if ( !empty( $u ) ) {

					foreach ( $u as $us ) {

						$res[] = $us;

					}

					//$res = array_merge( $res, $u );

				}

			}

		}

		return $res;

	}

	/**
	 * Вывод массива сотрудников с краткой информацией
	 *
	 * @param null     $id    - если указан, то выводятся данные по сотруднику и его подчиненным
	 * @param int|null $level - ограничить уровень подчиненности
	 * @param null     $mid   - не заполняется
	 * @return array - массив с результатом
	 *
	 *      - int **id** - iduser
	 *      - str **title** - имя полное ( ФИО )
	 *      - str **name** - имя краткое ( ФИ )
	 *      - str **tip** - роль в системе
	 *      - str **secrty** - активность = yes|no
	 *      - str **active** - активность = yes|no
	 *      - str **avatar** - файл аватара
	 *      - int **level** - уровень подчиненности
	 *      - str **isadmin** - является администратором = on|off
	 *      - bool **canDeleted** - может быть удален = true|false
	 */
	public static function userArrayMenu($id = NULL, int $level = NULL, $mid = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$res  = [];
		$sort = '';

		if ( !$id && !$mid ) {
			$sort = " and mid = '0'";
		}

		elseif ( $id > 0 ) {
			$sort = " and iduser = '$id'";
		}

		if ( $mid > 0 ) {
			$sort = " and mid = '$mid'";
		}

		$re = $db -> getAll( "SELECT iduser, mid, title, tip, secrty, avatar, isadmin, otdel FROM ".$sqlname."user WHERE iduser > 0 $sort AND secrty = 'yes' and identity = '$identity' ORDER BY mid, title" );
		foreach ( $re as $da ) {

			$uname = yexplode( " ", $da["title"] );

			$res[] = [
				"id"      => (int)$da["iduser"],
				"title"   => $da["title"],
				"name"    => $uname[0]." ".$uname[1],
				"tip"     => $da['tip'],
				"secrty"  => $da['secrty'],
				"active"  => $da['secrty'],
				"avatar"  => $da['avatar'],
				"level"   => (int)$level,
				"isadmin" => $da['isadmin'],
				"otdel"   => (int)$da['otdel'],
			];

			$count = $db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."user WHERE mid = '".$da["iduser"]."' AND identity = '$identity'" );
			if ( $count > 0 ) {

				$level++;
				$u = self ::userArrayMenu( NULL, $level, $da["iduser"] );
				$level--;

				if ( !empty( $u ) ) {

					foreach ( $u as $us ) {

						$res[] = $us;

					}

				}

			}

		}

		return $res;

	}

	/**
	 * Вывод списка сотрудников любой степени вложенности
	 *
	 * @param null   $id     integer - iduser сотрудника или 0, для вывода всех
	 * @param string $active - выводить только активных
	 * @param int    $level  - уровень вывода
	 * @param null   $mid    - руководитель - текущий сотрудник
	 *
	 * @return array - массив с результатом
	 *
	 *      - int **id** - iduser
	 *      - str **title** - имя полное ( ФИО )
	 *      - str **tip** - роль в системе
	 *      - str **active** - активность = yes|no
	 *      - str **avatar** - файл аватара
	 *      - int **level** - уровень подчиненности
	 *      - str **isadmin** - является администратором = on|off
	 *      - bool **canDeleted** - может быть удален = true|false
	 */
	public static function userCatalog($id = NULL, string $active = 'yes', int $level = NULL, $mid = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$ures   = [];
		$sort   = "";
		$filter = "";

		if ( !$id && !$mid ) {
			$sort = "and mid = '0'";
		}

		elseif ( $id > 0 ) {
			$sort = "and iduser = '$id'";
		}

		if ( $mid > 0 ) {
			$sort = "and mid = '$mid'";
		}

		if ( $active == 'yes' ) {
			$filter = " AND secrty = 'yes'";
		}

		elseif ( $active == 'no' ) {
			$filter = " AND secrty = 'no'";
		}

		$re = $db -> query( "SELECT iduser, mid, title, secrty, tip, avatar, isadmin FROM ".$sqlname."user WHERE iduser > 0 $sort $filter and secrty = 'yes' and identity = '$identity' ORDER BY mid, title" );
		while ($da = $db -> fetch( $re )) {

			$ures[] = [
				"id"         => (int)$da["iduser"],
				"title"      => $da["title"],
				"level"      => $level,
				"mid"        => (int)$da['mid'],
				"active"     => $da['secrty'],
				"tip"        => $da['tip'],
				"avatar"     => $da['avatar'],
				"isadmin"    => $da['isadmin'],
				"canDeleted" => self ::canDeleted( $da["iduser"] )
			];

			$count = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."user WHERE mid = '".$da["iduser"]."' $filter AND identity = '$identity'" );
			if ( $count > 0 ) {


				$level++;
				$u = self ::userCatalog( NULL, $active, $level, $da['iduser'] );
				$level--;

				if ( !empty( $u ) ) {

					foreach ( $u as $us ) {

						$ures[] = $us;

					}

					//$ures = array_merge( $ures, $u );

				}

			}

		}

		return $ures;

	}

	/**
	 * Вывод орг.структуры с вложенностью подчиненных
	 *
	 * Применяется в основном в разделе Панель управления / Сотрудники
	 *
	 * @param int|null $id  integer - iduser сотрудника или 0, для вывода всех
	 * @param bool     $active
	 * @param null     $mid - руководитель - текущий сотрудник
	 * @return array - массив с результатом
	 *
	 *      - int **id** - iduser
	 *      - str **title** - имя полное ( ФИО )
	 *      - str **name** - имя краткое ( ФИ )
	 *      - int **mid** - iduser руководителя
	 *      - str **active** - активность = yes|no
	 *      - str **tip** - роль в системе
	 *      - str **post** - должность
	 *      - int **otdel** - id отдела
	 *      - str **avatar** - файл аватара
	 *      - date **adate** - дата изменения активности
	 *      - str **isadmin** - является администратором = on|off
	 *      - bool **canDeleted** - может быть удален = true|false
	 *      - array **users** - массив подчиненных с теми же характеристиками
	 */
	public static function userOrgChart(int $id = NULL, bool $active = true, $mid = NULL): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$ures   = [];
		$sort   = "";
		$filter = "";

		if ( !$id && !$mid ) {
			$sort = "and mid = '0'";
		}

		elseif ( $id > 0 ) {
			$sort = "and iduser = '$id'";
		}

		if ( $mid > 0 ) {
			$sort = "and mid = '$mid'";
		}

		if ( $active == 'yes' ) {
			$filter = " AND secrty = 'yes'";
		}

		elseif ( $active == 'no' ) {
			$filter = " AND secrty = 'no'";
		}

		$re = $db -> getAll( "SELECT iduser, mid, title, secrty, tip, user_post, avatar, otdel, isadmin, adate FROM ".$sqlname."user WHERE iduser > 0 $sort $filter and secrty = 'yes' and identity = '$identity' ORDER BY mid, title" );
		foreach ( $re as $da ) {

			$users = [];

			$count = (int)$db -> getOne( "SELECT COUNT(*) FROM ".$sqlname."user WHERE mid = '".$da["iduser"]."' $filter AND identity = '$identity'" );
			if ( $count > 0 ) {
				$users = self ::userOrgChart( NULL, $active, (int)$da['iduser'] );
			}

			$uname = yexplode( " ", $da["title"] );

			$ures[] = [
				"id"         => (int)$da["iduser"],
				"title"      => $da["title"],
				"name"       => $uname[0]." ".$uname[1],
				"mid"        => (int)$da['mid'],
				"active"     => $da['secrty'],
				"tip"        => $da['tip'],
				"post"       => $da['user_post'],
				"otdel"      => (int)$da['otdel'],
				"avatar"     => $da['avatar'],
				"isadmin"    => $da['isadmin'],
				"adate"      => $da['adate'],
				"canDeleted" => self ::canDeleted( (int)$da["iduser"] ),
				"users"      => $users
			];

		}

		return $ures;

	}

	/**
	 * Получение списка пользователей в виде массива
	 *
	 * @param       $id
	 * @param array $params - массив с результатом
	 *                      - **as** - выбор вида массива
	 *                      - **id** - массив в виде iduser
	 *                      - **title** - массив в виде Имен
	 *                      - если не указано, то в виде iduser => title
	 *
	 * @return array
	 */
	public static function userList($id, array $params = []): array {

		$users = self ::userArray( $id );

		$u = [];

		if ( $params['as'] == 'id' ) {

			foreach ( $users as $user ) {
				$u[] = (int)$user['id'];
			}

		}
		elseif ( $params['as'] == 'title' ) {

			foreach ( $users as $user ) {
				$u[] = $user['title'];
			}

		}
		else {

			//print_r($users);

			foreach ( $users as $user ) {
				$u[ (int)$user['id'] ] = $user['title'];
			}

		}

		return $u;

	}

	/**
	 * Вывод списка подчиненности сотрудника, т.е. кому он подчиняется
	 * в первом звене выводит данные текущего сотрудника, далее по возрастающей подчиненности ( мой руководитель -
	 * руководитель руководителя ... )
	 *
	 * @param int $id   - iduser сотрудника
	 * @param array $ures - глобальный массив, не заполняется
	 *
	 * @return array - массив с результатом
	 *      - INDEX
	 *          - int **id** - iduser
	 *          - str **title** - ФИО
	 *          - int **mid** - iduser руководителя
	 *          - bool **active** - признак активности = true|false
	 *          - str **tip** - роль в системе
	 */
	public static function userBoss(int $id = 0, array $ures = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$user = $db -> getRow( "SELECT iduser, mid, title, secrty, tip FROM ".$sqlname."user WHERE iduser = '$id' and identity = '$identity'" );

		$user['secrty'] = ($user['secrty'] == 'yes') ? "true" : "false";

		$ures[] = [
			"id"     => (int)$user["iduser"],
			"title"  => $user["title"],
			"mid"    => (int)$user['mid'],
			"active" => $user['secrty'],
			"tip"    => $user['tip']
		];

		if ( $user['mid'] > 0 ) {
			$ures = array_merge( $ures, self ::userBoss( (int)$user['mid'] ) );
		}

		return $ures;

	}

	/**
	 * Вывод списка коллег и их подчиненных для текущего сотрудника
	 *
	 * @param $id   int - iduser сотрудника
	 * @param $full bool - вывести массив с детальной информацией, false - выводит только id
	 *
	 * @return array - массив с результатом
	 *      - INDEX
	 *          - int **id** - iduser
	 *          - str **title** - ФИО
	 *          - int **mid** - iduser руководителя
	 *          - bool **active** - признак активности = true|false
	 *          - str **tip** - роль в системе
	 */
	public static function userColleagues(int $id = 0, bool $full = true): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$u = $users = [];

		$boss = $db -> getRow( "SELECT iduser, mid, tip, title, secrty FROM ".$sqlname."user WHERE iduser = '$id' and identity = '$identity' ORDER BY iduser, title" );

		$users[0] = [
			"id"     => (int)$boss['iduser'],
			"title"  => $boss["title"],
			"level"  => '0',
			"mid"    => (int)$boss['mid'],
			"active" => $boss['secrty'],
			"tip"    => $boss['tip']
		];

		//print_r($users);

		switch ($boss['tip']) {

			case 'Руководитель организации':
			case 'Руководитель с доступом':
			case 'Администратор':

				$users = array_merge( $users, self ::userCatalog() );

			break;
			case 'Руководитель подразделения':
			case 'Руководитель отдела':
			case 'Поддержка продаж':

				//$users = getUserCatalog($id);

				$res = $db -> query( "SELECT iduser FROM ".$sqlname."user WHERE (mid = '$boss[mid]' or iduser = '$id') and secrty = 'yes' and identity = '$identity' ORDER BY iduser, title" );
				while ($da = $db -> fetch( $res )) {

					$u = self ::userCatalog( (int)$da["iduser"] );

					if ( !empty( $u ) ) {

						foreach ( $u as $us ) {

							$users[] = $us;

						}

						//$users = array_merge( $users, $u );

					}

				}

				//print_r($users);

			break;
			case 'Менеджер продаж':

				$boss2 = $db -> getRow( "SELECT iduser, mid, tip, title, secrty FROM ".$sqlname."user WHERE iduser = '$boss[mid]' and identity = '$identity' ORDER BY iduser, title" );

				$res = $db -> query( "SELECT iduser, mid, title, secrty, tip FROM ".$sqlname."user WHERE mid = '$boss2[mid]' and secrty = 'yes' and identity = '$identity' ORDER BY iduser, title" );
				while ($da = $db -> fetch( $res )) {

					$users[] = [
						"id"     => (int)$da["iduser"],
						"title"  => $da["title"],
						"mid"    => (int)$da['mid'],
						"active" => $da['secrty'],
						"tip"    => $da['tip']
					];

				}

			break;

		}

		foreach ( $users as $user ) {
			if ( !in_array( $user, $u ) ) {
				$u[ (int)$user['id'] ] = $user;
			}
		}//($full == true) ? $user : $user['id'];


		if ( !$full ) {
			$u = array_values( $u );
		}

		return $u;

	}

	/**
	 * Возвращает телефоны и email сотрудников
	 * Применяется для фильтрации в парсере html2data()
	 *
	 * @return array
	 */
	public static function userPhones(): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$phones = $emails = [];

		$users = $db -> getAll( "SELECT email, phone, mob FROM ".$sqlname."user WHERE identity = '$identity'" );
		foreach ( $users as $user ) {

			if ( $user['phone'] != '' ) {
				$phones[] = prepareMobPhone( $user['phone'] );
			}

			if ( $user['mob'] != '' ) {
				$phones[] = prepareMobPhone( $user['mob'] );
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
	 * Проверка возможности удаления пользователя
	 *
	 * @param $id
	 * @return bool
	 */
	public static function canDeleted($id): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$iduser1  = $GLOBALS['iduser1'];

		$all = 0;

		//проверим наличие клиентов
		$all += (int)$db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."clientcat WHERE iduser = '$id' AND identity = '$identity'" );

		//проверим наличие персон
		$all += (int)$db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."personcat WHERE iduser = '$id' and identity = '$identity'" );

		//проверим наличие сделок
		$all += (int)$db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."dogovor WHERE iduser = '$id' and identity = '$identity'" );

		//проверим наличие подчиненных
		$all += (int)$db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."user WHERE mid = '$id' and identity = '$identity'" );

		if ( $iduser1 == $id ) {
			$all++;
		}

		return $all == 0;

	}

	/**
	 * Вывод названия отдела для всех сотрудников
	 *
	 * @return array
	 */
	public static function otdel(): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$otdel = [];

		$all = $db -> getIndCol( "idcategory", "SELECT idcategory, title FROM ".$sqlname."otdel_cat WHERE identity = '$identity' ORDER BY title" );

		$users = $db -> getAll( "SELECT iduser, otdel FROM ".$sqlname."user WHERE identity = '$identity'" );
		foreach ( $users as $user ) {

			$otdel[ $user['iduser'] ] = (int)$user['otdel'] > 0 ? $all[ (int)$user['otdel'] ] : NULL;

		}

		return $otdel;

	}

}