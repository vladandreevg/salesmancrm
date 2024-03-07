<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2016.20         */
/* ============================ */

/**
 * Здесь представлено несколько способов отправки данных во внешние обработчики
 * - PHP_cURL
 * - CURL cli
 * - WGET
 * - Gearman - Сервер очереди заданий (Рекомендуемый способ для Linux, т.к. самый быстрый для пользователя CRM)
 *
 * Для использования нужного способа необходимо переименовать функцию в "outSender", а текущую функцию также переименовать в "outSenderOrig"
 */

/*
 * Описание класса event. Не надо открывать этот код - он приведен только для ознакомления и уже присутствует в системе
 * Class event
class event{
	public static $events = [];
	public static function fire($event, $args = [])
	{
		if(isset(self::$events[$event]))
		{
			foreach(self::$events[$event] as $func)
			{
				call_user_func_array($func, $args);
			}
		}
	}
	public static function register($event, Closure $func)
	{
		self::$events[$event][] = $func;
	}
}
*/

global $baseURL;

$baseURL = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER["HTTP_HOST"];

/**
 * Функция для отправки HTTP-запроса по заданному URL через cURL
 *
 * @param string $url
 * @param array  $params
 * @return bool|string
 */
function outSender(string $url, array $params){

	$rootpath = realpath(__DIR__.'/../');

	$params['identity'] = $GLOBALS['identity'];
	$params['iduser1']  = $GLOBALS['iduser1'];
	$params['ses']      = $_COOKIE['ses'];

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);
	$err = curl_error($ch);

	$logfile = $rootpath."/cash/events.log";
	$text =
		current_datumtime()."
		TYPE: events
		URL:
		$url
		Входящие данные:
		".json_encode_cyr($params)."
		Ответ:
		$result
		~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n
		";
	file_put_contents($logfile, str_replace("\t", "", $text), FILE_APPEND);

	if($result === false) {
		return $err;
	}
	else {
		return $result;
	}

}

/**
 * Импорт клиентов. Получаемые аргументы:
 * array clids - массив clid новых записей клиентов
 * array pids  - массив pid новых записей контактов
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.import', static function($args = []){

	$args['event'] = 'client.import';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении Клиента/Контакта через Экспресс-форму, через обработчик лидов
 * integer pid  - pid новой записи клонтакта
 * integer clid - clid новой записи клиента
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.expressadd', static function($args = []){

	//webhook
	if($args['clid'] > 0) {

		$args['event'] = 'client.add';

		outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);
	}

	if($args['pid'] > 0) {

		$args['event'] = 'person.add';

		outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

	}

	if($args['tid'] > 0) {

		$args['event'] = 'task.add';

		outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

	}

});

/**
 * Событие, срабатывающее при добавлении Клиента через обычную-форму, через обращения
 * integer clid - clid новой записи клиента
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.add', static function($args = []){

	$args['event'] = 'client.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении Клиента
 * integer clid - clid записи клиента
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.edit', static function($args = []){

	$args['event'] = 'client.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при удалении Клиента
 * integer clid - clid записи клиента
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.delete', static function($args = []){

	$args['event'] = 'client.delete';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении реквизитов Клиента
 * integer clid - clid записи клиента
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.change.recv', static function($args = []){

	$args['event'] = 'client.change.recv';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении Ответственного за Клиента
 * integer clid - clid новой записи клиента
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('client.change.user', static function($args = []){

	//при смене пользователя в карточке сделки приходит информация
	//clid - идентификатор клиента
	//autor (iduser пользователя, который провел операцию)
	//newuser - iduser нового пользователя
	//olduser - iduser старого пользователя
	//comment - комментарий сотрудника

	//при массовой передаче клиентов приходит информация
	//info - массив передаваемых клиентов и их старый пользователь - array(clid, olduser)
	//autor (iduser пользователя, который провел операцию)
	//newuser - iduser нового пользователя
	//comment - комментарий сотрудника

	$args['event'] = 'client.change.user';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении Контакта через обычную-форму, через обращения
 * string pid  - pid новой записи контакта
 * string autor - id сотрудника, выполнившего действие
 * string user  - id ответственного сотрудника
 */
event::register('person.add', static function($args = []){

	$args['event'] = 'person.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении Контакта
 * integer pid   - pid записи контакта
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('person.edit', static function($args = []){

	$args['event'] = 'person.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при удалении Контакта
 * integer pid   - pid записи контакта
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('person.delete', static function($args = []){

	$args['event'] = 'person.delete';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении Ответственного за Контакт
 * integer pid   - pid записи контакта
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 * newuser - iduser нового пользователя
 * olduser - iduser старого пользователя
 * comment - комментарий сотрудника
 */
event::register('person.change.user', static function($args = []){

	$args['event'] = 'person.change.user';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Импорт клиентов. Получаемые аргументы:
 * array clids - массив clid новых записей клиентов
 * array dids  - массив did новых записей сделок
 * integer autor - id сотрудника, выполнившего действие
 * integer user  - id ответственного сотрудника
 */
event::register('deal.import', static function($args = []){

	$args['event'] = 'deal.import';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении новой сделки
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string userUID  - UID ответственного сотрудника во внешней системе
 */
event::register('deal.add', static function($args = []){

	$args['event'] = 'deal.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении основной информации сделки
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * integer userUID - id сотрудника, выполнившего действие во внешней системе
 * array newparam  - массив параметров
 *       ide - id обращения, если сделка создана по обращению
 *       др.параметры сделки
 */
event::register('deal.edit', static function($args = []){

	$args['event'] = 'deal.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при удалении сделки
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 */
event::register('deal.delete', static function($args = []){

	$args['event'] = 'deal.delete';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении ответственного за сделку
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * integer olduser - id предыдущего сотрудника, ответственного за сделку
 * integer newuser - id нового сотрудника, ответственного за сделку
 * string comment - комментарий внесения изменений
 */
event::register('deal.change.user', static function($args = []){

	//при смене пользователя в карточке сделки приходит информация
	//did - идентификатор сделки
	//autor (iduser пользователя, который провел операцию)
	//newuser - iduser нового пользователя
	//olduser - iduser старого пользователя
	//comment - комментарий сотрудника

	//при массовой передаче сделок приходит информация
	//info - массив передаваемых сделок и их старый пользователь - array(did, olduser)
	//autor (iduser пользователя, который провел операцию)
	//newuser - iduser нового пользователя
	//comment - комментарий сотрудника

	$args['event'] = 'deal.change.user';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении ответственного за сделку
 * вызывается из класса Deal -> dchangestep
 * не срабатывает при групповых действиях смены этапа
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * integer stepOld - название предыдущего этапа (например 10)
 * integer stepNew - название нового этапа (например 20)
 * string comment - комментарий внесения изменений
 * string reason - комментарий внесения изменений
 */
event::register('deal.change.step', static function($args = []){

	$args['event'] = 'deal.change.step';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

	//file_put_contents("event.stepchange", json_encode($args));

});

/**
 * Событие, срабатывающее при закрытии сделки
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string summa - сумма закрытия сделки (фактическая, например - 10340.00)
 * string status - статус закрытия (например Победа)
 */
event::register('deal.close', static function($args = []){

	$args['event'] = 'deal.close';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении нового счета
 * integer id - id записи счета
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string user  - id ответственного сотрудника
 * string userUID  - UID ответственного сотрудника во внешней системе
 * integer id - id записи выставленного счета
 * string summa - сумма счета (например - 10340.00)
 * string invoice - номер счета
 */
event::register('invoice.add', static function($args = []){

	//при добавлении счета
	//did - идентификатор сделки
	//crid - идентификатор счета в БД
	//autor (iduser пользователя, который провел операцию)

	$args['event'] = 'invoice.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);


});

/**
 * Событие, срабатывающее при внесении изменений в счет
 * integer id - id записи счета
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string user  - id ответственного сотрудника
 * string userUID  - UID ответственного сотрудника во внешней системе
 * string summa - сумма счета (например - 10340.00)
 * string invoice - номер счета
 */
event::register('invoice.edit', static function($args = []){

	//при изменении счета
	//did - идентификатор сделки
	//crid - идентификатор счета в БД
	//autor (iduser пользователя, который провел операцию)

	$args['event'] = 'invoice.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при отметке счета оплаченным
 * integer id - id записи счета
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string userUID  - UID ответственного сотрудника во внешней системе
 * string invoice - номер счета
 * string summa - сумма платежа (например - 10340.00), может быть частичной
 * string summaNew - сумма созданного платежа (например - 10340.00), вычисляется как разница сумм по счету и суммы платежа
 */
event::register('invoice.doit', static function($args = []){

	//при добавлении счета
	//did - идентификатор сделки
	//crid - идентификатор счета в БД
	//autor (iduser пользователя, который провел операцию)

	$args['event'] = 'invoice.doit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при отмене оплаты счета
 * integer id - id записи счета
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string userUID  - UID ответственного сотрудника во внешней системе
 * string invoice - номер счета
 */
event::register('invoice.undoit', static function($args = []){

	//при добавлении счета
	//did - идентификатор сделки
	//crid - идентификатор счета в БД
	//autor (iduser пользователя, который провел операцию)

	$args['event'] = 'invoice.undoit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при внесении платежа без выставления счета
 * integer id - id записи счета
 * integer did   - did записи сделки
 * integer autor - id сотрудника, выполнившего действие
 * string userUID  - UID ответственного сотрудника во внешней системе
 * string invoice - номер счета
 * string summa - сумма платежа (например - 10340.00), может быть частичной
 * string summaNew - сумма созданного платежа (например - 10340.00), вычисляется как разница сумм по счету и суммы платежа
 */
event::register('invoice.expressadd', static function($args = []){

	//при добавлении счета
	//did - идентификатор сделки
	//crid - идентификатор счета в БД
	//autor (iduser пользователя, который провел операцию)

	$args['event'] = 'invoice.expressadd';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении напоминания
 * integer id   - id записи напоминания
 * integer autor - id сотрудника, выполнившего действие
 */
event::register('task.add', static function($args = []){

	$args['event'] = 'task.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении напоминания
 * integer id   - id записи напоминания
 * integer autor - id сотрудника, выполнившего действие
 */
event::register('task.edit', static function($args = []){

	$args['event'] = 'task.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при выполнении напоминания
 * integer id   - id записи напоминания
 * integer autor - id сотрудника, выполнившего действие
 */
event::register('task.doit', static function($args = []){

	$args['event'] = 'task.doit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении записи в Историю
 * integer id   - id записи напоминания
 * integer autor - id сотрудника, выполнившего действие
 */
event::register('history.add', static function($args = []){

	$args['event'] = 'history.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении записи в Историю
 * integer id   - id записи
 * integer autor - id сотрудника, выполнившего действие
 */
event::register('history.edit', static function($args = []){

	$args['event'] = 'history.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при поступлении заявки, не назначенной исполнителю
 * integer id   - id записи
 * integer clid - id записи клиента, если добавлен новый клиент
 * integer pid - id записи контакта, если добавлен новый контакт
 * integer did - id записи сделки, если добавлена
 * integer iduser - id записи координатора
 */
event::register('lead.add', static function($args = []){

	$args['event'] = 'lead.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при поступлении заявки
 * integer id   - id записи
 * integer clid - id записи клиента, если добавлен новый клиент
 * integer pid - id записи контакта, если добавлен новый контакт
 * integer did - id записи сделки, если добавлена
 * integer iduser - id записи сотрудника
 * integer coordinator - id записи координатора
 */
event::register('lead.setuser', static function($args = []){

	$args['event'] = 'lead.setuser';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при обработке заявки
 * Внимание: при обработке заявки другие события не вызываются
 * integer id   - id записи
 * integer autor - id сотрудника, выполнившего действие
 * integer clid - id записи клиента, если добавлен новый клиент
 * integer pid - id записи контакта, если добавлен новый контакт
 * integer did - id записи сделки, если добавлена
 * integer coordinator - id записи координатора
 */
event::register('lead.do', static function($args = []){

	$args['event'] = 'lead.do';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении обращения
 * id - id записи обращения
 * clid - id записи клиента
 * pid - id записи контакта
 * did - id записи сделки, если добавлена
 * autor - id сотрудника, выполнившего действие
 */
event::register('entry.add', static function($args = []){

	$args['event'] = 'entry.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при отмене обращения
 * id - id обращения
 * autor - id сотрудника, выполнившего действие
 * status = 2 ['0' => 'Новое', '1' => 'Обработано', '2' => 'Отменено'];
 */
event::register('entry.status', static function($args = []){

	$args['event'] = 'entry.status';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении документа
 * id - id записи
 * number - номер документа
 * datum - дата документа
 * payer - id записи Плательщика по сделке
 * clid - id записи клиента
 * pid - id записи контакта
 * did - id записи сделки, если добавлена
 * autor - id сотрудника, выполнившего действие
 */
event::register('contract.add', static function($args = []){

	$args['event'] = 'contract.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении документа
 * id - id записи обращения
 * number - номер документа
 * datum - дата документа
 * payer - id записи Плательщика по сделке
 * clid - id записи клиента
 * pid - id записи контакта
 * did - id записи сделки, если добавлена
 * autor - id сотрудника, выполнившего действие
 */
event::register('contract.edit', static function($args = []){

	$args['event'] = 'contract.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при удалении документа
 * id - id записи документа
 * autor - id сотрудника, выполнившего действие
 */
event::register('contract.delete', static function($args = []){

	$args['event'] = 'contract.delete';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при добавлении Акта
 * id - id записи
 * number - номер документа
 * datum - дата документа
 * payer - id записи Плательщика по сделке
 * clid - id записи клиента
 * pid - id записи контакта
 * did - id записи сделки, если добавлена
 * autor - id сотрудника, выполнившего действие
 */
event::register('akt.add', static function($args = []){

	$args['event'] = 'akt.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при изменении Акта
 * id - id записи обращения
 * number - номер документа
 * datum - дата документа
 * payer - id записи Плательщика по сделке
 * clid - id записи клиента
 * pid - id записи контакта
 * did - id записи сделки, если добавлена
 * autor - id сотрудника, выполнившего действие
 */
event::register('akt.edit', static function($args = []){

	$args['event'] = 'akt.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Событие, срабатывающее при удалении Акта
 * id - id записи документа
 * autor - id сотрудника, выполнившего действие
 */
event::register('akt.delete', static function($args = []){

	$args['event'] = 'akt.delete';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * События перечисленные ниже не подключены и не работают
 */

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при отправке письма. Почтовик
 */
event::register('email.send', static function($args = []){

	$args['event'] = 'email.send';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при получении письма. Почтовик
 */
event::register('email.get', static function($args = []){

	$args['event'] = 'email.get';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при добавлении расхода. Финансы
 */
event::register('budjet.add', static function($args = []){

	$args['event'] = 'budjet.add';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при добавлении расхода. Финансы
 */
event::register('budjet.edit', static function($args = []){

	$args['event'] = 'budjet.edit';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при добавлении расхода. Финансы
 */
event::register('budjet.do', static function($args = []){

	$args['event'] = 'budjet.do';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при добавлении расхода. Финансы
 */
event::register('budjet.undo', static function($args = []){

	$args['event'] = 'budjet.undo';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});

/**
 * Перспектива! Не подключено
 * Событие, срабатывающее при добавлении расхода. Финансы
 */
event::register('budjet.move', static function($args = []){

	$args['event'] = 'budjet.move';

	//webhook
	outSender($GLOBALS['baseURL']."/developer/webhooks.php", $args);

});