<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

/**
 * Поддерживается PHP 5.6+
 */

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header( 'Access-Control-Allow-Origin: *' );

error_reporting( E_ERROR );

//настройка подключения к БД Астериска
const HOSTBD   = 'localhost';
const USERBD   = 'user';
const PASSBD   = 'password';
const DATABASE = 'database';

//см. API-key в общих настройках Панели управления
const APIKEY = 'you API key';

function json_encode_cyr($str) {
	$arr_replace_utf = [
		'\u0410',
		'\u0430',
		'\u0411',
		'\u0431',
		'\u0412',
		'\u0432',
		'\u0413',
		'\u0433',
		'\u0414',
		'\u0434',
		'\u0415',
		'\u0435',
		'\u0401',
		'\u0451',
		'\u0416',
		'\u0436',
		'\u0417',
		'\u0437',
		'\u0418',
		'\u0438',
		'\u0419',
		'\u0439',
		'\u041a',
		'\u043a',
		'\u041b',
		'\u043b',
		'\u041c',
		'\u043c',
		'\u041d',
		'\u043d',
		'\u041e',
		'\u043e',
		'\u041f',
		'\u043f',
		'\u0420',
		'\u0440',
		'\u0421',
		'\u0441',
		'\u0422',
		'\u0442',
		'\u0423',
		'\u0443',
		'\u0424',
		'\u0444',
		'\u0425',
		'\u0445',
		'\u0426',
		'\u0446',
		'\u0427',
		'\u0447',
		'\u0428',
		'\u0448',
		'\u0429',
		'\u0449',
		'\u042a',
		'\u044a',
		'\u042b',
		'\u044b',
		'\u042c',
		'\u044c',
		'\u042d',
		'\u044d',
		'\u042e',
		'\u044e',
		'\u042f',
		'\u044f'
	];
	$arr_replace_cyr = [
		'А',
		'а',
		'Б',
		'б',
		'В',
		'в',
		'Г',
		'г',
		'Д',
		'д',
		'Е',
		'е',
		'Ё',
		'ё',
		'Ж',
		'ж',
		'З',
		'з',
		'И',
		'и',
		'Й',
		'й',
		'К',
		'к',
		'Л',
		'л',
		'М',
		'м',
		'Н',
		'н',
		'О',
		'о',
		'П',
		'п',
		'Р',
		'р',
		'С',
		'с',
		'Т',
		'т',
		'У',
		'у',
		'Ф',
		'ф',
		'Х',
		'х',
		'Ц',
		'ц',
		'Ч',
		'ч',
		'Ш',
		'ш',
		'Щ',
		'щ',
		'Ъ',
		'ъ',
		'Ы',
		'ы',
		'Ь',
		'ь',
		'Э',
		'э',
		'Ю',
		'ю',
		'Я',
		'я'
	];
	$str1            = json_encode( $str );
	$str2            = str_replace( $arr_replace_utf, $arr_replace_cyr, $str1 );

	return $str2;
}

$hours = (int)$_REQUEST['hours'];
if ( !$hours ) {
	$hours = 24;
}
$params = [];

$delta     = $hours * 3600;//период времени, за который делаем запрос в часах
$zone      = 0;//смещение временной зоны сервера
$dateStart = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) - $delta );
$dateEnd   = date( 'Y-m-d H:i:s', mktime( date( 'H' ), date( 'i' ), date( 's' ), date( 'm' ), date( 'd' ), date( 'Y' ) ) );

$hash = urlencode( $_REQUEST['hash'] );

$params['dateStart'] = urldecode( $_REQUEST['dateStart'] );
if ( !$_REQUEST['dateStart'] )
	$params['dateStart'] = $dateStart;
//$params['dateEnd'] = $_REQUEST['dateEnd']; if(!$_REQUEST['dateEnd']) $params['dateEnd'] = $dateEnd;

//принудительно загружаем по текущий момент
$params['dateEnd'] = $dateEnd;

//проверяем hash на легальность
if ( $hash != APIKEY ) {

	$response['error']['code'] = '1';
	$response['error']['text'] = 'Wrong API KEY code';

	print json_encode_cyr( $response );

	exit();

}
else {

	$response['error']['code'] = '0';
	$response['error']['text'] = 'Success';

	//подключаемся к БД
	if ( $connect = mysqli_connect( HOSTBD, USERBD, PASSBD, DATABASE ) ) {

		//mysqli_select_db(DATABASE);

		//формируем массив данных
		$result = mysqli_query( $connect, "SELECT * FROM cdr WHERE calldate BETWEEN '".$params['dateStart']."' and '".$params['dateEnd']."' ORDER BY calldate DESC" );
		while ($data = mysqli_fetch_array( $result )) {

			$response['data'][] = [
				"datum" => $data['calldate'],
				//дата звонка
				"src"   => $data['src'],
				//источник вызова
				"dst"   => $data['dst'],
				//пункт назначения вызова
				"res"   => $data['disposition'],
				//результат ANSWERED, NO ANSWER, BUSY
				"dur"   => $data['duration'],
				//продолжительность вызова
				"sec"   => $data['billsec'],
				//Продолжительность вызова с момента ответа на него
				"uid"   => $data['uniqueid'],
				"file"  => $data['recordingfile'],
				"did"   => $data['did']
				//наш номер, на который поступил звонок
			];
		}

		mysqli_close( $connect );

	}
	else $response['data']['error'] = mysqli_error( $connect );

	//формируем результат в формате json
	print json_encode_cyr( $response );

}



/*
    clid: Caller*ID
    src : Источник вызова.
    dst : Пункт назначения вызова.
    dcontext : Контекст назначения.
    channel : Имя канала.
    dstchannel : Канал назначения вызова.
    astapp: Последняя выполненная функция.
    lastdata: Аргументы последней выполненной команды.
    start: Время начала вызова.
    answer: Время ответа на вызов.
    end: Время окончания вызова.
    duration: Продолжительность вызова.
    billsec: Продолжительность вызова с момента ответа на него.
    disposition : ANSWERED, NO ANSWER, BUSY
    amaflags: DOCUMENTATION, BILL, OMIT.
    accountcode: Код аккаунта канала.
    uniqueid: Уникальный идентификатор канала.
    userfield: Пользовательские данные установленные для канала.
*/

/*
Структура БД
calldate 		datetime 		Нет 	Индексированное 	0000-00-00 00:00:00
clid 			varchar(80) 	Нет 	Нет
src 			varchar(80) 	Нет 	Нет
dst 			varchar(80) 	Нет 	Индексированное
dcontext 		varchar(80) 	Нет 	Нет
channel 		varchar(80) 	Нет 	Нет
dstchannel 		varchar(80) 	Нет 	Нет
lastapp 		varchar(80) 	Нет 	Нет
lastdata 		varchar(80) 	Нет 	Нет
duration 		int(11) 		Нет 	Нет 				0
billsec 		int(11) 		Нет 	Нет 				0
disposition 	varchar(45) 	Нет 	Нет
amaflags 		int(11) 		Нет 	Нет 				0
accountcode 	varchar(20) 	Нет 	Индексированное
uniqueid 		varchar(32) 	Нет 	Нет
userfield 		varchar(255) 	Нет 	Нет
did 			varchar(50) 	Нет 	Нет
recordingfile 	varchar(255) 	Нет 	Нет
cnum 			varchar(40) 	Нет 	Нет
cnam 			varchar(40) 	Нет 	Нет
outbound_cnum 	varchar(40) 	Нет 	Нет
outbound_cnam 	varchar(40) 	Нет 	Нет
dst_cnam 		varchar(40) 	Нет
*/