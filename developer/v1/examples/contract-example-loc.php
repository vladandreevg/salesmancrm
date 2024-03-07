<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

error_reporting(E_ERROR);

$baseurl = "http://sm.crm/developer/v1/contract";//Адрес расположения CRM

$params['login'] = "zaharbor@isaler.ru";//существующий пользователь в системе
$params['apikey'] = 'gCG01Q5MA8msP1jXuQUC';//'aMgiCQyj8bCToNc47BZZYrRICoWSIl'; //получаем в Панели управления CRM

function Send($url, $POST){
	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if($result === false) print $err = curl_error($ch);

	return $result;
}

//указываем метод - fields (список доступных полей), steplist (список этапов), list (список записей), info (информация по сделке), add, changestep (изменить этап), update, close, delete, addinvoice (добавление счета), addpaiment (отметка существующего счета оплаченным), addpaimentpart (отметка существующего счета оплаченным частично)
$params['action'] = 'info';

//== для списка записей == list
/*
$params['offset'] = '0';//страница, с учетом вывода 200 записей на страницу
$params['order'] = 'datum'; //поле для упорядочивания записей
$params['first'] = ''; //направление сортировки; new - по-умолчанию, old - сначала более старые
$params['user'] = '';//ограничение по login пользователя, пользователь должен быть в подчинении у текущего
$params['dateStart'] = '2015-08-01';//даты создания
$params['dateEnd'] = '2015-08-31';
$params['steps'] = '';//фильтр по этапам, поддержка нескольких значений с разделением запятой
$params['word'] = '';//строка поиска по полям title, des, phone, mail_url, site_url
$params['active'] = 'yes';//yes - активная сделка или no - закрытая
*/
/*
 * active = '' - возвращаем все
 * active = 'no' - возвращаем закрытые
 * active = 'yes' - возвращаем только открытые
*/
//todo:отладить работу доп.фильтров
/*
$params['filter']['clid'] = '';//integer, идентификатор клиента
$params['filter']['payer'] = '';//integer, идентификатор клиента-плательщика
$params['filter']['direction'] = '';//string, фильтр по направлению деятельности
$params['filter']['tip'] = '';//string, фильтр по типу сделки
$params['filter']['input1'] = '';//string, фильтр по доп.полю
//..
$params['filter']['input10'] = '';//string, фильтр по доп.полю
*/
//$params['filter']['clid'] = '1781';

//== для вывода только заданных полей из конкретной записи == list, info
//$params['fields'] = 'did,clid,title,content,kol,marga';

//== для вывода реквизитов == list, info
//$params['bankinfo'] = 'yes';
//$params['invoice'] = 'no';//по умолчанию yes
//$params['speka'] = 'no';//только для info, по умолчанию yes

//== для конкретной записи == info, update
$params['did'] = '744'; //указываем id записи

//== для добавления записи == add
/*
$params['mcid'] = '2'; //id компании в справочнике "Мои компании"
$params['user'] = '';
$params['clid'] = '1781';
$params['datum_plan'] = '2015-08-30';//планируемая дата закрытия сделки. Если пусто, то принимается текущая дата + 2 недели
$params['payer'] = '';//м.б. пустым, если равен clid
$params['title'] = 'Пробная сделка API №4';
$params['content'] = 'Календарем дел пользуются практически все - ведь он помогает не забыть о текущих делах и планировать будущие действия.';

$params['step'] = '20';
$params['kol'] = '100000.00';//не обязательно, если есть спецификация
$params['marga'] = '30000.00';//не обязательно, если есть спецификация

$params['tip'] = 'Продажа услуг';
$params['direction'] = 'Оборудование';
*/

//спецификация или набор продуктов для add, update
/*
$params['speka'][0] = array(
	"artikul" => "7414",
	"title" => "BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
	"kol" => "5",
	"dop" => "1",//доп.множитель, если не используется = 1
	"price" => "18831,15",//не обязательно, если корректно указан artikul или title
	"price_in" => "13 949,00",//не обязательно, если корректно указан artikul или title
	"edizm" => "шт.",//не обязательно, если корректно указан artikul или title
	"nds" => "18"//НДС в % если есть
);
$params['speka'][1] = array(
	"artikul" => "7722",
	"title" => "SIP-T12P SIP-телефон, 2 линии, PoE",
	"kol" => "10",
	"dop" => "1",//доп.множитель, если не используется = 1
	"price" => "3821,85",//не обязательно, если корректно указан artikul или title
	"price_in" => "2 831,00",//не обязательно, если корректно указан artikul или title
	"edizm" => "шт.",//не обязательно, если корректно указан artikul или title
	"nds" => "18"//НДС в % если есть
);
*/

//== для обновления записи == update
/*
$params['datum_plan'] = '2015-06-10';
$params['title'] = 'Пробная сделка API №1-1';

$params['step'] = '40';
$params['kol'] = '120000.00';
$params['marga'] = '40000.00';
*/

//== для добавления записи == changestep
/*
$params['step'] = '80';
$params['reason'] = 'Согласовали спецификацию. Готовим договор.';
*/

//== для закрытия сделки == close
/*
$params['status_close'] = 'Победа полная';
$params['kol_fact'] = '100000.00';
$params['marga'] = '30000.00';
$params['des_fact'] = 'Комментарий к закрытию сделки.';
*/

//== для выставления счета клиенту == addinvoice
/*
$params['did'] = '779';//уникальный идентификатор сделки в CRM
$params['uid'] = '';//уникальный идентификатор сделки во внешней системе
$params['date'] = '2015-08-10';//дата счета
$params['date_plan'] = '2015-08-10';//ожидаемая дата оплаты счета

$params['summa'] = '40000.00';//сумма счета
$params['invoice'] = '64';//номер счета, если пусто, то будем генерировать очередной из системы
$params['contract'] = '';//номер договора, если пусто, то смотрим Договор, прикрепленный к сделке
$params['do'] = '';//признак оплаченного счета - yes / no
$params['date_do'] = '';//дата оплаты, если пусто - текущая дата
$params['rs'] = '1';//идентификатор расчетного счета (список можно получить из справочника) - если пусто, то берем первый по списку с признаком "по-умолчанию"
$params['nds'] = '';//размер НДС в абсолютных цифрах
$params['tip'] = 'Счет-договор'; //тип счета - Предварительная оплата, Окончательная оплата, По спецификации, По договору, Счет-договор
*/

//== для отметки счета оплаченным == addpaiment
/*
$params['invoice'] = '64';//номер счета, не должен быть пустым
$params['date_do'] = '';//дата оплаты, если пусто - текущая дата
*/

//== для отметки счета оплаченным с указанием суммы оплаты == addpaimentpart
//== если сумма меньше, чем сумма счета, то в графике платежей будет создана еще одна запись на оставшуюся сумму
/*
$params['invoice'] = '45';//номер счета, не должен быть пустым
$params['date_do'] = '';//дата оплаты, если пусто - текущая дата
$params['summa'] = '353700';//сумма оплаты, должна быть больше нуля и меньше/равно сумме счета
*/

//тест добавления спецификации
//$params = json_decode('{"login":"vladislav@nxt.ru","apikey":"GIDB8OypmUlT1w7V0Q4ESPkII29xi5","action":"add","user":"","clid":"39445","uid":"10000","payer":"","title":"Заказ # 100 Екатерина","content":"Доставка после 16.00","tip":"Продажа товара","direction":"","speka":[{"artikul":"123139","title":"Шторы Риверu (бежевый)","kol":"1.00","price":"9200.00","dop":1},{"artikul":"124401","title":"Шторы Сицилия v3 (малина-фисташка) (мульти полоска)","kol":"1.00","price":"7350.00","dop":1}],"filter":null,"invoice":null,"datum_plan":"2016-04-14"}',true);

// Создаем подпись к параметрам
$urlparams = http_build_query($params);

print "<code>".$urlparams."</code><br><br>";

//print_r($params);

// Устанавливаем соединение
$res = Send($baseurl, $urlparams);

//print $res;

//раскодируем ответ из JSON-формата
$result = json_decode($res, true);

if($result['result'] == 'Error') {
	print "Ошибка: ".$result['error']['text'];
	exit();
}

print_r($result);

exit();
?>