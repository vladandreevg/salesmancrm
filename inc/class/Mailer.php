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

use DateTime;
use DateTimeZone;
use Exception;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Imap\ImapUtf7;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use SafeMySQL;


/**
 * Класс для работы с почтой
 *
 * Class Mailer
 *
 * @package     Salesman
 * @author      Vladislav Andreev <v@salesman.pro>
 * @version     5.2 (06/09/2019)
 */
class Mailer {

	/**
	 * Абсолютный путь
	 *
	 * @var string
	 */
	public $rootpath;

	/**
	 * Массив для просмотра письма. Выдает метод mailView()
	 *
	 * @var array
	 */
	public $View = [];

	/**
	 * Массив данных письма. Выдает метод mailInfo()
	 *
	 * @var array
	 */
	public $Message = [];

	/**
	 * - Массив писем, Выдает mailList();
	 * - Массив полученных и пока не обработанных писем, Выдает mailGet();
	 * - Передается в качестве массива для обработки в Метод mailGetWorker();
	 *
	 * @var array
	 * @uses mailList(), mailGet(), mailGetWorker()
	 */
	public $Messages = [];

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $identity, $iduser1, $sqlname, $db, $fpath, $opts, $skey, $ivc, $tmzone;

	/**
	 * ID сотрудника, от которого идет отправка
	 * Применяется для загрузки настроек почты
	 *
	 * @var mixed
	 */
	public $iduser;

	/**
	 * Проверяемая почта: INBOX|SEND
	 *
	 * @var string
	 */
	public $box = 'INBOX';

	/**
	 * Число дней проверки почты. Методы: mailGet(), mailGetWorker()
	 *
	 * @var int
	 */
	public $days = 7;

	/**
	 * ID сообщения
	 *
	 * @var int
	 */
	public $id = 0;

	/**
	 * ID настроек почтового ящика
	 *
	 * @var int
	 */
	public $mailID = 0;

	/**
	 * Почтовый ID письма вида <****@host>
	 *
	 * @var string
	 */
	public $messageid = '';

	/**
	 * Массив uid писем, которые надо загрузить
	 *
	 * @var array
	 */
	public $uids = [];

	/**
	 * Приоритет сообщения
	 * 1 - Высокий
	 * 3 - Нормальный
	 * 5 - Низкий
	 *
	 * @var int
	 */
	public $priority = 3;

	/**
	 * Передача различных параметров
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Параметры для отправки сообщений
	 *
	 * @var string
	 */
	public $html = '', $subject = '';

	/**
	 * Нужно ли игнорировать существующие сообщения при проверке почты
	 * @var bool
	 */
	public $ignoreExist = false;

	/**
	 * Параметры для отправки сообщений
	 *
	 * @var array
	 */
	public $from = [], $to = [], $copy = [], $attach = [], $files = [];

	/**
	 * Массив ошибок
	 *
	 * @var array
	 */
	public $Error = [];

	/**
	 * Иконки папок
	 */
	public const FOLDERICON = [
		'conversation' => '<i class="icon-chat-1 orange"></i>',
		'inbox'        => '<i class="icon-download-1 blue"></i>',
		'outbox'       => '<i class="icon-upload-1 broun"></i>',
		'draft'        => '<i class="icon-doc-text gray"></i>',
		'trash'        => '<i class="icon-trash gray"></i>',
		'sended'       => '<i class="icon-forward-1 green"></i>'
	];

	/**
	 * FOLDERNAME. Имена папок
	 */
	public const FOLDERNAME = [
		'conversation' => 'Вся почта',
		'inbox'        => 'Входящее',
		'outbox'       => 'Исходящее',
		'draft'        => 'Черновик',
		'trash'        => 'Корзина',
		'sended'       => 'Отправлено'
	];

	/**
	 * Названия приоритетов для отображения
	 */
	public const PRIORITY = [
		1 => '<i class="icon-flag-1 red"></i> Высокий',
		3 => '<i class="icon-flag-1 blue"></i> Нормальный',
		5 => '<i class="icon-flag-1 yelw"></i> Низкий'
	];

	/**
	 * Иконки приоритетов
	 */
	public const PRIORITYICON = [
		'1' => '<i class="icon-flag-1 red" title="Высокий"></i>',
		'3' => '<i class="icon-flag-1 blue" title="Нормальный"></i>',
		'5' => '<i class="icon-flag-1 yelw" title="Низкий"></i>'
	];

	/**
	 * Иконки клиента в зависимости от типа Юр.лицо/Физ.лицо
	 */
	public const CLIENTICON = [
		'client' => 'icon-commerical-building',
		'person' => 'icon-user-1'
	];

	/**
	 * базовый шаблон сообщения
	 */
	public const TEMPLATE = '
		<html lang="ru">
		<head>
		<title>{subject}</title>
		<STYLE type="text/css">
		<!--
		body {
			color:#222;
			font-size: 14px;
			line-height:18px;
			font-family: arial, tahoma,serif;
			background:#FFF;
			margin:5px;
		}
		div{
			padding:5px 0;
			margin:0;
			display:block;
		}
		p{
			padding:2px 0;
		}
		hr{
			width:102%;
			border:0 none;
			border-top: #ccc 1px dotted;
			padding:0; height:1px;
			margin:5px -5px;
			clear:both;
		}
		a, a:visited, a:link{
			color: #507192;
			display:inline;
		}
		a:active, a:hover{
			color:red;
		}
		blockquote{
			border-left: 2px solid #507192;
			padding-left: 20px;
			background: #FAFAFA;
		}
		-->
		</STYLE>
		</head>
		<body>
		{html}
		</body>
		</html>
	';

	/**
	 * @var array
	 */
	public $otherSettings = [];

	public $boxSettings = [];

	/**
	 * Работает только с объектом
	 * Подключает необходимые файлы, задает первоначальные параметры
	 * Mailer constructor
	 */
	public function __construct() {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";
		require_once $rootpath."/vendor/autoload.php";

		$params = $this -> params;

		$this -> rootpath = $rootpath;
		$this -> identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$this -> iduser1  = ($this -> iduser > 0) ? $this -> iduser : $GLOBALS['iduser1'];
		$this -> sqlname  = $GLOBALS['sqlname'];
		$this -> db       = $GLOBALS['db'];
		$this -> fpath    = $GLOBALS['fpath'];
		$this -> opts     = $GLOBALS['opts'];
		$this -> skey     = ($this -> skey != '') ? $this -> skey : $GLOBALS['skey'];
		$this -> ivc      = ($this -> ivc != '') ? $this -> ivc : $GLOBALS['ivc'];
		$this -> tmzone   = $GLOBALS['tmzone'];
		$this -> iduser   = $params['iduser'] ?? $this -> iduser1;

		// тут почему-то не срабатывает
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		date_default_timezone_set( $this -> tmzone );

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

		//если не указан почтовый ящик ( для реализации 1 Юзер = 1 Ящик )
		//читаем настройки
		$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$this -> iduser.".json";

		if ( file_exists( $settingsYMail ) ) {
			$this -> boxSettings = json_decode( file_get_contents( $settingsYMail ), true );
		}
		elseif ( $this -> iduser > 0 ) {

			$x = (string)$db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '".$this -> iduser."' AND identity = '$identity'" );

			if ( !empty( $x ) ) {
				$this -> boxSettings = json_decode( $x, true );
			}

		}

		$this -> boxSettings['ymailUser'] = self ::decrypt( $this -> boxSettings['ymailUser'], $this -> skey, $this -> ivc );
		$this -> boxSettings['ymailPass'] = self ::decrypt( $this -> boxSettings['ymailPass'], $this -> skey, $this -> ivc );

	}

	/**
	 * Информация по сообщению
	 *
	 * ```php
	 * $mail = new Salesman\Mailer();
	 * $mail -> id = 27651;
	 * $mail -> mailInfo();
	 * $email = $mail -> Message;
	 * ```
	 */
	public function mailInfo(): void {

		$identity = $this -> identity;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$id       = $this -> id;

		if ( $id > 0 ) {

			//общие параметры сообщения
			$resultm         = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );
			$html            = htmlspecialchars_decode( $resultm['content'] );
			$msg['id']       = (int)$resultm['id'];
			$msg['uid']      = $resultm['uid'];
			$msg['date']     = $resultm['datum'];
			$msg['folder']   = $resultm['folder'];
			$msg['status']   = $resultm['state'];
			$msg['subject']  = $resultm['theme'];
			$msg['html']     = str_replace( '{html}', $html, self::TEMPLATE );
			$msg['priority'] = $resultm['priority'];
			$did             = (int)$resultm['did'];
			$xfid            = str_replace( ";", ",", $resultm['fid'] );
			$msg['iduser']   = (int)$resultm['iduser'];
			//$msg['from']     = current_user( $resultm['iduser'] );
			//$msg['fromname'] = current_user( $resultm['iduser'] );
			$msg['isTrash'] = $resultm['trash'] != 'no';

			preg_match( '/(?<=\[D#)[0-9.\/]*(?=]$)/', $msg['subject'], $d );

			if ( $did > 0 && empty( $d ) ) {

				$msg['subject'] .= " [D#".$did."]";

				$msg['did']  = $did;
				$msg['clid'] = getDogData( $did, 'clid' );

			}

			$too = [];

			$ymailSet = $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '".$resultm['iduser']."' and identity = '$identity'" );
			$ymailSet = json_decode( $ymailSet, true );

			if ( $msg['folder'] == 'inbox' ) {

				$msg['from']     = $resultm['fromm'];
				$msg['fromname'] = $resultm['fromname'];

				$too[] = [
					"email"  => $ymailSet['ymailFrom'],
					"name"   => current_user( $resultm['iduser'] ),
					"iduser" => $resultm['iduser']
				];

			}
			else {

				$msg['from']     = $ymailSet['ymailFrom'];
				$msg['fromname'] = current_user( $resultm['iduser'] );

				//список адресатов
				$resultm = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec where mid = '$id' and identity = '$identity'" );
				while ($data = $db -> fetch( $resultm )) {

					//активируем, когда подключим класс Mailer
					$too[] = [
						"email" => $data['email'],
						"name"  => $data['name'],
						"clid"  => (int)$data['clid'],
						"pid"   => (int)$data['pid']
					];

				}

			}

			$msg['to'] = $too;

			$exists = [];
			$filess = [];

			//вложения
			$xf = yexplode( ",", (string)$xfid );
			if ( !empty( $xf ) ) {

				$s = " and fid IN (".$xfid.")";

				$result = $db -> query( "SELECT * FROM {$sqlname}file WHERE fid > 0 $s and identity = '$identity' GROUP BY ftitle ORDER BY fid DESC" );
				while ($da = $db -> fetch( $result )) {

					$filess[] = [
						"fid"  => (int)$da['fid'],
						"file" => $da['fname'],
						"name" => $da['ftitle'],
						"icon" => get_icon3( $da['fname'] )
					];

					$exists[] = $da['fname'];

				}

			}

			$msg['files'] = $filess;
			$attachs      = [];

			//вложения
			$resultf = $db -> query( "SELECT * FROM {$sqlname}ymail_files where mid = '$id' and identity = '$identity'" );
			while ($data = $db -> fetch( $resultf )) {

				if ( !in_array( $data['file'], $exists ) ) {
					$attachs[] = [
						"id"   => (int)$data['id'],
						"file" => $data['file'],
						"name" => $data['name'],
						"icon" => get_icon3( $data['file'] )
					];
				}

			}

			$msg['attach'] = $attachs;

			$this -> Message = $msg;

		}
		else {
			$this -> Error = ["error" => "Unknown message"];
		}

	}

	/**
	 * Список писем (левая панель)
	 *
	 * ```php
	 * $list = new \Salesman\Mailer();
	 * $list -> params = $params;
	 *
	 * $list -> mailList();
	 * $lists = $list -> Messages;
	 * ```
	 */
	public function mailList(): void {

		global $bdtimezone;

		$identity      = $this -> identity;
		$sqlname       = $this -> sqlname;
		$db            = $this -> db;
		$params        = $this -> params;
		$iduser        = $this -> iduser;
		$otherSettings = $this -> otherSettings;

		global $tipuser, $isadmin;

		$page   = $params['page'];
		$folder = $params['folder'];
		$word   = str_replace( " ", "%", trim( $params['word'] ) );
		$da1    = $params['date1'];
		$da2    = $params['date2'];
		$clid   = (int)$params['clid'];
		$pid    = (int)$params['pid'];
		$did    = (int)$params['did'];
		$sort   = '';
		$dos    = '';
		$list   = [];

		if ( $word != '' ) {
			$sort .= " and ({$sqlname}ymail_messages.theme LIKE '%$word%' or {$sqlname}ymail_messages.content LIKE '%$word%' or {$sqlname}ymail_messages.fromname LIKE '%$word%' or {$sqlname}ymail_messages.fromm LIKE '%$word%')";
		}

		if ( $folder != 'trash' ) {
			$sort .= " and COALESCE({$sqlname}ymail_messages.folder, '') = '$folder' and COALESCE({$sqlname}ymail_messages.trash, '') != 'yes'";
		}
		else {
			$sort .= " and COALESCE({$sqlname}ymail_messages.trash, '') = 'yes'";
		}

		if ( $da1 != '' && $da2 != '' ) {
			$sort .= " and DATE(datum) BETWEEN '$da1' AND '$da2'";
		}

		//$set   = $db -> getOne("SELECT other FROM {$sqlname}settings WHERE id = '$identity'");
		//$other = explode(";", $set);

		if ( $otherSettings['mailerMsgUnion'] ) {

			/*if(in_array($tipuser, ['Руководитель организации','Руководитель отдела']) || $isadmin == 'on'){
				$dos .= " or iduser IN (".yimplode(",", get_people($iduser, "yes")).")";
			}
			else {*/

			$dostup_array = $db -> getCol( "SELECT clid FROM {$sqlname}dostup WHERE iduser = '$iduser' and identity = '$identity'" );
			if ( !empty( (array)$dostup_array ) ) {
				$dos .= " or ({$sqlname}ymail_messagesrec.clid IN (SELECT {$sqlname}dostup.clid FROM {$sqlname}dostup WHERE {$sqlname}dostup.iduser = '$iduser' and {$sqlname}dostup.identity = '$identity'))";
			}

			$dostup_array = $db -> getCol( "SELECT did FROM {$sqlname}dostup WHERE iduser = '$iduser' and identity = '$identity'" );
			if ( !empty( (array)$dostup_array ) ) {
				$dos .= " or ({$sqlname}ymail_messages.did IN (SELECT {$sqlname}dostup.did FROM {$sqlname}dostup WHERE {$sqlname}dostup.iduser = '$iduser' and {$sqlname}dostup.identity = '$identity'))";
			}

			//}

		}

		//$shared = (($folder == 'sended' || $folder == 'inbox')) ? "({$sqlname}ymail_messages.iduser = '$iduser' $dos) and " : $sqlname."ymail_messages.iduser = '$iduser' and ";

		$shared = (in_array( $folder, [
			'sended',
			'inbox',
			'conversation'
		] )) ? "({$sqlname}ymail_messages.iduser = '$iduser' $dos) and " : $sqlname."ymail_messages.iduser = '$iduser' and ";

		if ( $clid > 0 ) {

			//получаем массив контактов
			$pids = $db -> getCol( "SELECT pid FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity' ORDER BY person" );

			$si = (!empty( $pids )) ? " or {$sqlname}ymail_messagesrec.pid IN (".yimplode( ",", $pids ).")" : "";

			$sort .= " and ({$sqlname}ymail_messagesrec.clid = '$clid' $si)";

		}
		elseif ( $pid > 0 ) {
			$sort .= " and {$sqlname}ymail_messagesrec.pid = '$pid'";
		}

		if ( $did > 0 ) {
			$sort .= " and {$sqlname}ymail_messages.did = '$did'";
		}


		$all_lines = $db -> getOne( "
			SELECT 
				COUNT(DISTINCT {$sqlname}ymail_messages.id)
			FROM {$sqlname}ymail_messages 
				LEFT JOIN {$sqlname}ymail_messagesrec ON {$sqlname}ymail_messagesrec.mid = {$sqlname}ymail_messages.id
			WHERE 
				$shared 
				{$sqlname}ymail_messages.id > 0 and 
				{$sqlname}ymail_messages.state != 'deleted' and
				{$sqlname}ymail_messages.identity = '$identity'
				$sort
				-- GROUP BY {$sqlname}ymail_messages.id
		" );

		//print $db -> lastQuery();

		$lines_per_page = 30;
		$page           = (empty( $page ) || $page <= 0) ? 1 : (int)$page;

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;
		$count_pages    = ceil( $all_lines / $lines_per_page );
		if ( $count_pages == 0 ) {
			$count_pages = 1;
		}

		$query = "
			SELECT 
				DISTINCT {$sqlname}ymail_messages.id as id,
				{$sqlname}ymail_messages.iduser as iduser,
				".($otherSettings['mailerMsgUnion'] ? $sqlname."ymail_messagesrec.clid as clid," : "")."
				".($otherSettings['mailerMsgUnion'] ? $sqlname."ymail_messagesrec.pid as pid," : "")."
				{$sqlname}ymail_messages.did as did,
				{$sqlname}ymail_messages.datum as datum,
				{$sqlname}ymail_messages.folder as folder,
				{$sqlname}ymail_messages.state as state,
				{$sqlname}ymail_messages.fid as fid,
				{$sqlname}ymail_messages.hid as hid,
				{$sqlname}ymail_messages.did as did,
				{$sqlname}ymail_messages.trash as trash,
				{$sqlname}ymail_messages.priority as priority,
				{$sqlname}ymail_messages.theme as theme,
				{$sqlname}ymail_messages.fromname as fromname,
				{$sqlname}ymail_messages.fromm as fromm
			FROM {$sqlname}ymail_messages 
				".($otherSettings['mailerMsgUnion'] ? "LEFT JOIN {$sqlname}ymail_messagesrec ON {$sqlname}ymail_messagesrec.mid = {$sqlname}ymail_messages.id" : "")."
			WHERE 
				$shared
				{$sqlname}ymail_messages.id > 0 and 
				COALESCE({$sqlname}ymail_messages.state, '') != 'deleted' and
				{$sqlname}ymail_messages.identity =$identity
				$sort
			GROUP BY {$sqlname}ymail_messages.id
			ORDER BY {$sqlname}ymail_messages.datum
			";

		$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

		$query  .= " DESC LIMIT $lpos,$lines_per_page";
		$result = $db -> query( $query );
		while ($data = $db -> fetch( $result )) {

			$from = $to = '';

			$toar    = [];
			$copyar  = [];
			$reazons = [];

			// время получения/отправки в констексте количества прошедшего времени
			$datum    = $data['datum'];
			$diff     = diffDate( $datum );
			$diffyear = (int)get_year( date( 'Y' ) ) - (int)get_year( $datum );

			if ( $diffyear == 0 ) {
				$date = ($diff < 1) ? get_time( $datum ) : get_dateru( $datum )." в ".get_time( $datum );
			}
			else {
				$date = get_sfdate2( $datum );
			}

			/**
			 * Количество вложений
			 */

			$fcount  = $db -> getOne( "SELECT COUNT(*) as fcount FROM {$sqlname}ymail_files WHERE mid = '$data[id]' and identity = '$identity'" );
			$xfcount = count( yexplode( ",", (string)$data['fid'] ) );
			$fcount  += $xfcount;

			$res = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$data[id]' and identity = '$identity'" );
			while ($dataa = $db -> fetch( $res )) {

				if ( (int)$dataa['pid'] > 0 ) {

					$person = $db -> getRow( "SELECT mail, person FROM {$sqlname}personcat WHERE pid = '$dataa[pid]' and identity = '$identity'" );

					$to = [
						"email" => $person['email'],
						"name"  => $person['person'],
						"icon"  => "icon-user-1 broun"
					];

					//$to = '<span title="'.$dataa['email'].'"><i class="icon-user-1 broun"></i>'.$person.'</span>';

				}
				elseif ( (int)$dataa['clid'] > 0 ) {

					$rest = $db -> getRow( "SELECT mail_url, type FROM {$sqlname}clientcat WHERE clid = '$dataa[clid]' and identity = '$identity'" );
					$type = $rest['type'];

					$to = [
						"email" => $dataa['email'],
						"name"  => $dataa['name'],
						"icon"  => strtr( $type, self::CLIENTICON )
					];

					//$to = '<span title="'.$dataa['email'].'"><i class="'.strtr($type, self::CLIENTICON).'"></i>'.$dataa['name'].'</span>';

				}
				else {

					$iduserFrom = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_settings WHERE settings LIKE '%$dataa[email]%' and identity = '$identity'" );
					if ( $iduserFrom > 0 ) {

						$to = [
							"email"  => $dataa['email'],
							"name"   => current_user( $iduserFrom ),
							"iduser" => $iduserFrom,
							"icon"   => "icon-user-1 green"
						];

						//$isfromuser = true;

					}
					else {

						if ( $dataa['name'] == '0' || $dataa['name'] == '' ) {
							$dataa['name'] = $dataa['email'];
						}

						$to = [
							"email" => $dataa['email'],
							"name"  => $dataa['name'],
							"icon"  => 'icon-help-circled-1 gray'
						];

						//$to = '<span title="'.$dataa['email'].'"><i class="icon-user-1 broun"></i>'.$dataa['name'].'</span>';

					}

				}

				if ( $dataa['tip'] == 'to' ) {
					$toar[] = $to;
				}
				elseif ( $dataa['tip'] == 'copy' ) {
					$copyar[] = $to;
				}

			}

			if ( $data['folder'] != 'inbox' ) {
				$to = $toar;
			}

			$copy = $copyar;

			if ( !$data['fromname'] ) {
				$data['fromname'] = $data['fromm'];
			}

			if ( $data['folder'] != 'draft' && $data['folder'] != 'sended' ) {

				$iduserFrom = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_settings WHERE settings LIKE '%$data[fromm]%' and identity = '$identity'" );
				if ( $iduserFrom > 0 ) {

					$from = [
						"email"  => $dataa['fromm'],
						"name"   => current_user( $iduserFrom ),
						"iduser" => $iduserFrom,
						"icon"   => "icon-user-1 green"
					];

				}
				else {

					$from = [
						"email" => $data['fromm'],
						"name"  => $data['fromname'],
						"icon"  => 'icon-help-circled-1 gray'
					];

				}

				$to = [];

			}

			//если письмо, не текущего пользователя
			if ( (int)$data['iduser'] != $iduser ) {

				//$too = current_user($data['iduser']);

				//привязка в зависомости от связи с сообщением
				if ( (int)$data['clid'] > 0 ) {

					$isDostup = (int)$db -> getOne( "SELECT id FROM {$sqlname}dostup WHERE clid = '$data[clid]' and iduser = '$iduser' and identity = '$identity'" );

					$reazons['isOwnerClient']  = getClientData( $data['clid'], 'iduser' ) == $iduser ? true : NULL;
					$reazons["isDostupClient"] = $isDostup > 0 ? true : NULL;

				}

				if ( (int)$data['pid'] > 0 ) {

					$reazons["isOwnerPerson"] = getPersonData( $data['pid'], 'iduser' ) == $iduser ? true : NULL;

				}

				if ( (int)$data['did'] > 0 ) {

					$isDostup = (int)$db -> getOne( "SELECT id FROM {$sqlname}dostup WHERE did = '$data[did]' and iduser = '$iduser' and identity = '$identity'" );

					$reazons["isOwnerDeal"]  = $data['did'] > 0 ? true : NULL;
					$reazons["isDostupDeal"] = $isDostup > 0 ? true : NULL;

				}

			}

			//добавим иконку наличия email в заявках
			$countInLeads = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}leads WHERE email = '$data[fromm]' AND status = '0' AND identity = '$identity'" );

			$list[] = [
				"id"                => (int)$data['id'],
				"from"              => $from,
				"to"                => $to,
				"copy"              => $copy,
				"unseen"            => ($data['folder'] == 'inbox' && $data['state'] == 'unread') ? 'unseen' : NULL,
				"priority"          => strtr( $data['priority'], self::PRIORITYICON ),
				"theme"             => mb_substr( clean( $data['theme'] ), 0, 101, 'utf-8' ),
				"folder"            => $data['folder'],
				"folderIcon"        => strtr( $data['folder'], self::FOLDERICON ),
				"folderName"        => strtr( $data['folder'], self::FOLDERNAME ),
				"isDraft"           => ($data['folder'] == 'draft') ? true : NULL,
				"isInbox"           => ($data['folder'] == 'inbox') ? true : NULL,
				"isTrash"           => ($data['trash'] == 'yes') ? true : NULL,
				"isTrashNo"         => ($data['trash'] != 'yes' && $data['iduser'] == $iduser) ? true : NULL,
				"date"              => $date,
				"hid"               => ($data['hid'] > 0) ? true : NULL,
				"files"             => ($fcount > 0) ? $fcount : NULL,
				"isUserOwner"       => ($data['iduser'] != $iduser) ? NULL : true,
				"Owner"             => current_user( $data['iduser'] ),
				"inleads"           => ($countInLeads > 0) ? true : NULL,
				"elementIsDisabled" => ($iduser != $data['iduser']) ? true : NULL,
				"reazons"           => (!empty( $reazons )) ? $reazons : NULL
			];

		}

		//file_put_contents($this -> rootpath."/cash/mailer.sql", $query);

		$lists = [
			"list"    => $list,
			"page"    => $page,
			"pageall" => $count_pages
		];

		$this -> Messages = $lists;

	}

	/**
	 * Вывод писем из Входящих и Исходящих
	 */
	public function mailListConversation(): void {

		global $tipuser, $isadmin, $bdtimezone;

		$identity      = $this -> identity;
		$sqlname       = $this -> sqlname;
		$db            = $this -> db;
		$params        = $this -> params;
		$iduser        = $this -> iduser;
		$iduser1       = $this -> iduser1;
		$otherSettings = $this -> otherSettings;

		$page   = (int)$params['page'];
		$folder = $params['folder'];
		$word   = str_replace( " ", "%", trim( $params['word'] ) );
		$da1    = $params['date1'];
		$da2    = $params['date2'];
		$clid   = (int)$params['clid'];
		$pid    = (int)$params['pid'];
		$did    = (int)$params['did'];
		$sort   = '';
		$dos    = '';
		$list   = [];
		$haveAccess = false;

		$userInfo = User ::info( $iduser )['result'];

		if ( $otherSettings['mailerMsgUnion'] ) {

			if ( in_array( $userInfo['tip'], ['Руководитель организации', 'Руководитель отдела'] ) || $userInfo['isadmin'] == 'on' ) {

				$haveAccess = true;

			}

		}

		if ( $word != '' ) {
			$sort .= " and (msg.theme LIKE '%$word%' or msg.content LIKE '%$word%' or msg.fromname LIKE '%$word%' or msg.fromm LIKE '%$word%' or msg.id IN (SELECT mid FROM {$sqlname}ymail_messagesrec WHERE email = '$word' AND identity = '$identity'))";
		}

		$sort .= " and msg.folder IN ('inbox','sended') and msg.trash != 'yes'";

		if ( $da1 != '' && $da2 != '' ) {
			$sort .= " and DATE(msg.datum) BETWEEN '$da1' AND '$da2'";
		}

		//$set   = $db -> getOne("SELECT other FROM {$sqlname}settings WHERE id = '$identity'");
		//$other = explode(";", $set);

		if ( $otherSettings['mailerMsgUnion'] ) {

			//print $tipuser;

			if ( in_array( $userInfo['tip'], ['Руководитель организации', 'Руководитель отдела'] ) || $userInfo['isadmin'] == 'on' ) {
				$dos .= " or msg.iduser IN (".yimplode( ",", get_people( $iduser, "yes" ) ).")";
			}
			else {

				$dostup_array = $db -> getCol( "SELECT clid FROM {$sqlname}dostup WHERE iduser = '$iduser' and identity = '$identity'" );

				if ( !empty( (array)$dostup_array ) ) {
					$dos .= " or (mrec.clid IN (SELECT {$sqlname}dostup.clid FROM {$sqlname}dostup WHERE {$sqlname}dostup.iduser = '$iduser' and {$sqlname}dostup.identity = '$identity'))";
				}

				$dostup_array = $db -> getCol( "SELECT did FROM {$sqlname}dostup WHERE iduser = '$iduser' and identity = '$identity'" );

				if ( !empty( (array)$dostup_array ) ) {
					$dos .= " or (msg.did IN (SELECT {$sqlname}dostup.did FROM {$sqlname}dostup WHERE {$sqlname}dostup.iduser = '$iduser' and {$sqlname}dostup.identity = '$identity'))";
				}

			}

			if ( !empty( $userInfo['assistants'] ) ) {
				$dos .= " or msg.iduser IN (".yimplode( ",", $userInfo['assistants'] ).")";
			}

		}

		//print $folder;

		$shared = (in_array( $folder, ['sended', 'inbox', 'conversation'] )) ? "(msg.iduser = '$iduser' $dos) and " : "msg.iduser = '$iduser' and ";

		if ( $clid > 0 ) {

			//получаем массив контактов
			$pids = $db -> getCol( "SELECT pid FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity' ORDER BY person" );

			$si = (!empty( $pids )) ? " or mrec.pid IN (".yimplode( ",", $pids ).")" : "";

			$sort .= " and (mrec.clid = '$clid' $si)";

		}
		elseif ( $pid > 0 ) {
			$sort .= " and mrec.pid = '$pid'";
		}

		if ( $did > 0 ) {
			$sort .= " and msg.did = '$did'";
		}

		$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

		$all_lines = $db -> getOne( "
			SELECT 
				COUNT(DISTINCT msg.id)
			FROM {$sqlname}ymail_messages `msg`
				LEFT JOIN {$sqlname}ymail_messagesrec `mrec` ON mrec.mid = msg.id
			WHERE 
				$shared 
				msg.id > 0 and 
				msg.state != 'deleted' and
				msg.identity = '$identity'
				$sort
				-- GROUP BY {$sqlname}ymail_messages.id
		" );

		//print $db -> lastQuery();

		$lines_per_page = 30;
		if ( empty( $page ) || $page <= 0 ) {
			$page = 1;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;
		$count_pages    = ceil( $all_lines / $lines_per_page );
		if ( $count_pages == 0 ) {
			$count_pages = 1;
		}

		$query = "
			SELECT 
				DISTINCT msg.id as id,
				msg.iduser as iduser,
				".($otherSettings['mailerMsgUnion'] ? "mrec.clid as clid," : "")."
				".($otherSettings['mailerMsgUnion'] ? "mrec.pid as pid," : "")."
				msg.did as did,
				msg.datum as datum,
				msg.folder as folder,
				msg.state as state,
				msg.fid as fid,
				msg.hid as hid,
				msg.did as did,
				msg.trash as trash,
				msg.priority as priority,
				msg.theme as theme,
				msg.fromname as fromname,
				msg.fromm as fromm
			FROM {$sqlname}ymail_messages `msg`
				".($otherSettings['mailerMsgUnion'] ? "LEFT JOIN {$sqlname}ymail_messagesrec `mrec` ON mrec.mid = msg.id" : "")."
			WHERE 
				$shared
				msg.id > 0 and 
				msg.state != 'deleted' and
				msg.identity = '$identity'
				$sort
			GROUP BY msg.id
			ORDER BY msg.datum
			";

		//print
		$query  .= " DESC LIMIT $lpos,$lines_per_page";
		$result = $db -> query( $query );
		while ($data = $db -> fetch( $result )) {

			$from = $to = $copy = $email = '';

			$toar    = [];
			$copyar  = [];
			$reazons = [];

			// время получения/отправки в констексте количества прошедшего времени
			$datum    = $data['datum'];
			$diff     = diffDate( $datum );
			$diffyear = get_year( date( 'Y' ) ) - get_year( $datum );

			if ( $diffyear == 0 ) {
				$date = ($diff < 1) ? get_time( $datum ) : get_dateru( $datum )." в ".get_time( $datum );
			}
			else {
				$date = get_sfdate2( $datum );
			}

			/**
			 * Количество вложений
			 */

			$fcount  = $db -> getOne( "SELECT COUNT(*) as fcount FROM {$sqlname}ymail_files WHERE mid = '$data[id]' and identity = '$identity'" );
			$xfcount = count( yexplode( ",", (string)$data['fid'] ) );
			$fcount  += $xfcount;

			if ( $data['folder'] != 'inbox' ) {

				$res = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$data[id]' and identity = '$identity'" );
				while ($dataa = $db -> fetch( $res )) {

					if ( (int)$dataa['pid'] > 0 ) {

						$person = $db -> getRow( "SELECT mail, person FROM {$sqlname}personcat WHERE pid = '$dataa[pid]' and identity = '$identity'" );

						$to = [
							"email" => (string)yexplode( ",", (string)$person['mail'], 0 ),
							"name"  => $person['person'],
							"icon"  => "icon-user-1 broun"
						];

						$email = (string)yexplode( ",", (string)$person['mail'], 0 );

					}
					elseif ( (int)$dataa['clid'] > 0 ) {

						$to = [
							"email" => $dataa['email'],
							"name"  => $dataa['name'],
							"icon"  => 'icon-building'
						];

						$email = $dataa['email'];

					}
					else {

						$iduserFrom = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_settings WHERE settings LIKE '%$dataa[email]%' and identity = '$identity'" );
						if ( $iduserFrom > 0 ) {

							$to = [
								"email"  => $dataa['email'],
								"name"   => current_user( $iduserFrom ),
								"iduser" => $iduserFrom,
								"icon"   => "icon-user-1 green"
							];

						}
						else {

							if ( $dataa['name'] == '0' || $dataa['name'] == '' ) {
								$dataa['name'] = $dataa['email'];
							}

							$to = [
								"email" => $dataa['email'],
								"name"  => $dataa['name'],
								"icon"  => 'icon-help-circled-1 gray'
							];

							$email = $dataa['email'];

						}

					}

					if ( $dataa['tip'] == 'to' ) {
						$toar[] = $to;
					}
					elseif ( $dataa['tip'] == 'copy' ) {
						$copyar[] = $to;
					}

				}

				//$to   = $toar;
				//$copy = $copyar;

			}
			// входящие
			else {

				$res = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$data[id]' and identity = '$identity'" );
				while ($dataa = $db -> fetch( $res )) {

					if ( (int)$dataa['pid'] > 0 ) {

						$person = $db -> getRow( "SELECT mail, person FROM {$sqlname}personcat WHERE pid = '$dataa[pid]' and identity = '$identity'" );

						$to = [
							"email" => (string)yexplode( ",", (string)$person['mail'], 0 ),
							"name"  => $person['person'],
							"icon"  => "icon-user-1 broun"
						];

						//$email = (string)yexplode( ",", (string)$person['mail'], 0 );

					}
					elseif ( (int)$dataa['clid'] > 0 ) {

						$to = [
							"email" => $dataa['email'],
							"name"  => $dataa['name'],
							"icon"  => 'icon-building'
						];

						//$email = (string)yexplode( ",", (string)$dataa['email'], 0 );

					}
					else {

						$iduserFrom = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}ymail_settings WHERE settings LIKE '%$dataa[email]%' and identity = '$identity'" );
						if ( $iduserFrom > 0 ) {

							$to = [
								"email"  => $dataa['email'],
								"name"   => current_user( $iduserFrom ),
								"iduser" => $iduserFrom,
								"icon"   => "icon-user-1 green"
							];

							//$email = (string)yexplode( ",", (string)$dataa['email'], 0 );

						}
						else {

							if ( $dataa['name'] == '0' || $dataa['name'] == '' ) {
								$dataa['name'] = $dataa['email'];
							}

							$to = [
								"email" => $dataa['email'],
								"name"  => $dataa['name'],
								"icon"  => 'icon-help-circled-1 gray'
							];

						}

					}

					if ( $dataa['tip'] == 'to' ) {
						$toar[] = $to;
					}
					elseif ( $dataa['tip'] == 'copy' ) {
						$copyar[] = $to;
					}

				}

				$iduserFrom = (int)$db -> getOne( "SELECT iduser FROM {$sqlname}ymail_settings WHERE settings LIKE '%$data[fromm]%' and identity = '$identity'" );
				if ( $iduserFrom > 0 ) {

					$from = [
						"email"  => $data['fromm'],
						"name"   => current_user( $iduserFrom ),
						"iduser" => $iduserFrom,
						"icon"   => "icon-user-1 green"
					];

				}

				$email = $data['fromm'];

			}

			if ( !$data['fromname'] ) {
				$data['fromname'] = $data['fromm'];
			}

			//если письмо, не текущего пользователя
			if ( (int)$data['iduser'] != (int)$iduser ) {

				//$too = current_user($data['iduser']);

				//привязка в зависомости от связи с сообщением
				if ( (int)$data['clid'] > 0 ) {

					$isDostup = (int)$db -> getOne( "SELECT id FROM {$sqlname}dostup WHERE clid = '$data[clid]' and iduser = '$iduser' and identity = '$identity'" );

					$reazons['isOwnerClient']  = getClientData( $data['clid'], 'iduser' ) == $iduser ? true : NULL;
					$reazons["isDostupClient"] = $isDostup > 0 ? true : NULL;

				}

				if ( (int)$data['pid'] > 0 ) {

					$reazons["isOwnerPerson"] = getPersonData( $data['pid'], 'iduser' ) == $iduser ? true : NULL;

				}

				if ( (int)$data['did'] > 0 ) {

					$isDostup = (int)$db -> getOne( "SELECT id FROM {$sqlname}dostup WHERE did = '$data[did]' and iduser = '$iduser' and identity = '$identity'" );

					$reazons["isOwnerDeal"]  = true;
					$reazons["isDostupDeal"] = $isDostup > 0 ? true : NULL;

				}

			}
			// если письмо текущего пользователя и это входящее, то убираем получателя, т.к. это текущий пользователь
			elseif ( $data['folder'] == 'inbox' ) {
				$from = $to;
				$to   = [];
			}

			//добавим иконку наличия email в заявках
			$countInLeads = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}leads WHERE email = '$data[fromm]' AND status = '0' AND identity = '$identity'" );

			$list[] = [
				"id"                => (int)$data['id'],
				"email"             => $email,
				// todo: переделать для исходящих
				"from"              => $from,
				"to"                => $to,
				"copy"              => $copy,
				"unseen"            => ($data['folder'] == 'inbox' && $data['state'] == 'unread') ? 'unseen' : NULL,
				"priority"          => strtr( $data['priority'], self::PRIORITYICON ),
				"theme"             => mb_substr( clean( $data['theme'] ), 0, 101, 'utf-8' ),
				"folder"            => $data['folder'],
				"folderIcon"        => strtr( $data['folder'], self::FOLDERICON ),
				"folderName"        => strtr( $data['folder'], self::FOLDERNAME ),
				"isDraft"           => ($data['folder'] == 'draft') ? true : NULL,
				"isInbox"           => ($data['folder'] == 'inbox') ? true : NULL,
				"isOutbox"          => ($data['folder'] == 'sended') ? true : NULL,
				"isTrash"           => ($data['trash'] == 'yes') ? true : NULL,
				"isTrashNo"         => ($data['trash'] != 'yes' && (int)$data['iduser'] == (int)$iduser) ? true : NULL,
				"date"              => $date,
				"hid"               => ((int)$data['hid'] > 0) ? true : NULL,
				"files"             => ($fcount > 0) ? $fcount : NULL,
				"isUserOwner"       => ((int)$data['iduser'] != (int)$iduser) ? NULL : true,
				"haveAccesse"       => ((int)$data['iduser'] != (int)$iduser && $haveAccess ) ? true : NULL,
				"Owner"             => current_user( $data['iduser'] ),
				"inleads"           => ($countInLeads > 0) ? true : NULL,
				"elementIsDisabled" => ((int)$iduser != (int)$data['iduser'] && !$haveAccess) ? true : NULL,
				"reazons"           => (!empty( $reazons )) ? $reazons : NULL,
				"isConversation"    => true,
			];

		}

		$lists = [
			"list"         => $list,
			"page"         => $page,
			"pageall"      => (int)$count_pages,
			"conversation" => true
		];

		$this -> Messages = $lists;

	}

	/**
	 * Список ID сообщений для вывода в карточке Клиента/Контакта/Сделки
	 */
	public function mailListCard(): void {

		$identity = $this -> identity;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$params   = $this -> params;

		$page = (int)$params['page'];
		$clid = (int)$params['clid'];
		$pid  = (int)$params['pid'];
		$did  = (int)$params['did'];
		$sort = '';
		$list = [];

		if ( $clid > 0 ) {

			//получаем массив контактов
			$pids = $db -> getCol( "SELECT pid FROM {$sqlname}personcat WHERE clid = '$clid' and identity = '$identity' ORDER BY person" );

			$si = (!empty( $pids )) ? " or {$sqlname}ymail_messagesrec.pid IN (".yimplode( ",", $pids ).")" : "";

			$sort .= " and ({$sqlname}ymail_messagesrec.clid = '$clid' $si)";

		}
		elseif ( $pid > 0 ) {
			$sort .= " and {$sqlname}ymail_messagesrec.pid = '$pid'";
		}

		if ( $did > 0 ) {
			$sort .= " and {$sqlname}ymail_messages.did = '$did'";
		}


		$all_lines = $db -> getOne( "
			SELECT 
				COUNT(DISTINCT {$sqlname}ymail_messages.id)
			FROM {$sqlname}ymail_messages 
				LEFT JOIN {$sqlname}ymail_messagesrec ON {$sqlname}ymail_messagesrec.mid = {$sqlname}ymail_messages.id
			WHERE 
				{$sqlname}ymail_messages.id > 0 and 
				{$sqlname}ymail_messages.state != 'deleted' and
				{$sqlname}ymail_messages.identity = '$identity'
				$sort
				-- GROUP BY {$sqlname}ymail_messages.id
		" );
		//print $db -> lastQuery();

		$lines_per_page = 5;
		if ( empty( $page ) || $page <= 0 ) {
			$page = 1;
		}

		$page_for_query = $page - 1;
		$lpos           = $page_for_query * $lines_per_page;
		$count_pages    = ceil( $all_lines / $lines_per_page );

		if ( $count_pages == 0 ) {
			$count_pages = 1;
		}

		$query = "
			SELECT 
				DISTINCT {$sqlname}ymail_messages.id as id,
				{$sqlname}ymail_messages.iduser as iduser,
				{$sqlname}ymail_messagesrec.clid as clid,
				{$sqlname}ymail_messagesrec.pid as pid,
				{$sqlname}ymail_messages.did as did,
				{$sqlname}ymail_messages.datum as datum,
				{$sqlname}ymail_messages.folder as folder,
				{$sqlname}ymail_messages.state as state,
				{$sqlname}ymail_messages.fid as fid,
				{$sqlname}ymail_messages.hid as hid,
				{$sqlname}ymail_messages.did as did,
				{$sqlname}ymail_messages.trash as trash,
				{$sqlname}ymail_messages.priority as priority,
				{$sqlname}ymail_messages.theme as theme,
				{$sqlname}ymail_messages.fromname as fromname,
				{$sqlname}ymail_messages.fromm as fromm
			FROM {$sqlname}ymail_messages 
				LEFT JOIN {$sqlname}ymail_messagesrec ON {$sqlname}ymail_messagesrec.mid = {$sqlname}ymail_messages.id
			WHERE 
				{$sqlname}ymail_messages.id > 0 and 
				{$sqlname}ymail_messages.state != 'deleted' and
				{$sqlname}ymail_messages.identity =$identity
				$sort
			GROUP BY {$sqlname}ymail_messages.id
			ORDER BY {$sqlname}ymail_messages.datum
			";

		$query  .= " DESC LIMIT $lpos,$lines_per_page";
		$result = $db -> query( $query );
		while ($data = $db -> fetch( $result )) {

			$list[] = (int)$data['id'];

		}

		$lists = [
			"list"    => $list,
			"page"    => $page,
			"pageall" => (int)$count_pages
		];

		$this -> Messages = $lists;

	}

	/**
	 * Сохранение/редактирование сообщения
	 *
	 * ```php
	 * $mail = new Salesman\Mailer();
	 * $mail -> subject = "Тест с отправкой";
	 * $mail -> to      = [
	 *      "email" => "a.vladislav.g@gmail.com",
	 *      "name"  => "Владислав"
	 * ];
	 * $mail -> html    = "<h1>Привет, {client}</h1><div>Это успех!</div><div>{manager}</div>";
	 *
	 * $mail -> mailEdit();
	 * $mid   = $mail -> id;
	 * $error = $mail -> Error;
	 * ```
	 */
	public function mailEdit(): void {

		global $bdtimezone;

		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$iduser   = $this -> iduser;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$fpath    = $this -> fpath;
		$rootpath = $this -> rootpath;
		$id       = $this -> id;
		$mailID   = $this -> mailID;

		$iduser = $iduser ?? $iduser1;

		//если не указан почтовый ящик ( для реализации 1 Юзер = 1 Ящик )
		if ( $mailID == 0 ) {

			//читаем настройки
			$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json";

			if ( file_exists( $settingsYMail ) ) {
				$param = json_decode( (string)file_get_contents( $settingsYMail ), true );
			}

			else {
				$param = json_decode( (string)$db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '$iduser' AND identity = '$identity'" ), true );
			}

		}
		// если указан ID конкретного ящика ( для реализации 1 Юзер = ХХХ ящиков )
		else {

			$p      = $db -> getRow( "SELECT settings, iduser FROM {$sqlname}ymail_settings WHERE id = '$mailID'" );
			$param  = json_decode( (string)$p['settings'], true );
			$iduser = $p['iduser'];

		}

		// различные параметры в массиве
		$params = $this -> params;
		$to     = $this -> to;
		$copy   = $this -> copy;

		$did      = max( (int)$params['did'], 0 );
		$content  = $this -> html ? htmlspecialchars( $this -> html ) : htmlspecialchars( $params['content'] );
		$priority = $params['priority'] ?? $this -> priority;
		$theme    = $this -> subject ? : $params['theme'];
		$from     = $this -> from ? : $param['ymailFrom'];
		$fromname = current_user( $iduser );

		//to
		$email = $params['email'];
		$clid  = $params['clid'];
		$pid   = $params['pid'];
		$name  = $params['name'];

		//copy
		$cemail = $params['ccemail'];
		$cclid  = $params['cclid'];
		$cpid   = $params['cpid'];
		$cname  = $params['cname'];

		if ( $did < 1 ) {

			preg_match( '/(?<=\[D#)[0-9.\/]*(?=]$)/', $theme, $didtag );
			if ( $didtag[0] != '' ) {
				$did = (int)$didtag[0];
			}

		}
		else {

			preg_match( '/(?<=\[D#)[0-9.\/]*(?=]$)/', $theme, $d );
			if ( empty( $d ) ) {
				$theme .= " [D#".$did."]";
			}

		}

		$uploads = $filed = $oldtoo = $oldcopy = $err = [];

		//оставшиеся, загруженные ранее файлы
		$fid = $params['fid'];

		//прикрепленные файлы из файлов карточки и общих папок
		$xfid = $params['xfid'];

		//прикрепленные файлы из документов
		$xfile = (array)$params['xfile'];
		$xname = (array)$params['xname'];

		$cnames = [];

		//сформируем массив адресатов
		if ( empty( $to ) ) {
			foreach ( $email as $key => $value ) {

				//to
				$to[] = [
					"email" => $value,
					"name"  => $name[ $key ],
					"clid"  => $clid[ $key ],
					"pid"   => $pid[ $key ]
				];

				$cnames[] = $name[ $key ];

			}
		}
		elseif ( $to['name'] != '' ) {

			$to = [$to];

			$cnames = arraySubSearch( $to, 'name' );

		}
		else {

			$cnames = arraySubSearch( $to, 'name' );

		}

		if ( empty( $copy ) ) {
			foreach ( $cemail as $key => $value ) {

				//copy
				$copy[] = [
					"email" => $value,
					"name"  => $cname[ $key ],
					"clid"  => $cclid[ $key ],
					"pid"   => $cpid[ $key ]
				];

			}
		}
		elseif ( $copy['email'] != '' ) {

			$copy   = [$copy];
			$cnames = $copy['name'];

		}

		/**
		 * Загружаем файлы
		 */
		$upload = Upload ::upload( 'ymail' );
		//$err    = array_merge($err, $upload['message']);

		foreach ( $upload['data'] as $file ) {

			$filed[] = [
				"file" => $file['name'],
				"name" => $file['title']
			];

		}

		$uploaddir = $rootpath.'/files/'.$fpath;

		$tags['client'] = yimplode( ", ", $cnames );

		//обрабатываем теги шаблона
		$result_set           = $db -> getRow( "SELECT * FROM {$sqlname}settings WHERE id = '$identity'" );
		$tags['company']      = $result_set["company"];
		$tags['company_full'] = $result_set["company_full"];
		$tags['company_site'] = $result_set["company_site"];

		//От кого письмо отправляется

		$result_set      = $db -> getRow( "SELECT * FROM {$sqlname}user WHERE iduser = '$iduser' and identity = '$identity'" );
		$tags['manager'] = $result_set["title"];
		$tags['phone']   = $result_set["phone"];
		$tags['fax']     = $result_set["fax"];
		$tags['mob']     = $result_set["mob"];
		$tags['email']   = $result_set["email"];

		foreach ( $tags as $key => $tag ) {

			switch ($key) {

				case "{company_site}":
					$content = str_replace( "{".$key."}", '<a href="https://'.str_replace( 'https://', '', $tag ).'>'.$tag.'</a>', $content );
				break;
				case "{email}":
					$content = str_replace( "{".$key."}", '<a href="mailto:'.$tag.'>'.$tag.'</a>', $content );
				break;
				default:
					$content = str_replace( "{".$key."}", $tag, $content );
				break;

			}

		}

		//если сообщение уже сохранено
		if ( $id > 0 ) {

			//сохраним сообщение
			$db -> query( "UPDATE {$sqlname}ymail_messages SET ?u WHERE id = '$id' and identity = '$identity'", [
				'datum'    => current_datumtime(),
				'priority' => $priority,
				'theme'    => $theme,
				'content'  => $content
			] );

			//удалим исключенные из письма файлы
			$resultf = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
			while ($data = $db -> fetch( $resultf )) {

				if ( !in_array( $data['id'], (array)$fid ) ) {

					$db -> query( "DELETE FROM {$sqlname}ymail_files WHERE id = '$data[id]' and identity = '$identity'" );
					unlink( $rootpath.'/files/ymail/'.$fpath.$data['file'] );

				}

			}

			//добавим новые файлы
			foreach ( $filed as $file ) {

				if ( $file['file'] != '' ) {

					$db -> query( "INSERT INTO {$sqlname}ymail_files SET ?u", [
						'datum'    => current_datumtime(),
						'mid'      => $id,
						'name'     => $file['name'],
						'file'     => $file['file'],
						'identity' => $identity
					] );
					$fid = $db -> insertId();

					$uploads[] = [
						"fid"  => (int)$fid,
						"icon" => get_icon3( $file['name'] ),
						"name" => $file['name'],
						"file" => $file['file']
					];

				}

			}

			//доавим файлы из файлов карточки и общих папок
			if ( !empty( $xfid ) ) {
				$db -> query( "UPDATE {$sqlname}ymail_messages SET fid = '".yimplode( ",", $xfid )."' WHERE id = '$id' and identity = '$identity'" );
			}

			else {
				$db -> query( "UPDATE {$sqlname}ymail_messages SET fid = '' WHERE id = '$id' and identity = '$identity'" );
			}

			//доавим файлы из документов
			$xfile = array_combine( $xfile, $xname );

			foreach ( $xfile as $file => $name ) {

				if ( $name != '' ) {

					//скопируем файл в папку ymail
					copyFile( $uploaddir.$file, $uploaddir.'ymail/' );

					$db -> query( "INSERT INTO {$sqlname}ymail_files SET ?u", [
						'datum'    => current_datumtime(),
						'mid'      => $id,
						'name'     => $name,
						'file'     => $file,
						'identity' => $identity
					] );
					$ffid = $db -> insertId();

					$uploads[] = [
						"fid"  => (int)$ffid,
						"icon" => get_icon3( $file ),
						"name" => $name,
						"file" => $file
					];

				}

			}

			//удалим из базы исключенных адресатов
			$resultm = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$id' and identity = '$identity'" );
			while ($data = $db -> fetch( $resultm )) {

				if ( $data['tip'] == 'to' ) {

					if ( !in_array( (int)$data['clid'], (array)$clid ) && (int)$data['clid'] > 0 ) {

						$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE id = '$data[id]' and identity = '$identity'" );

					}
					elseif ( !in_array( (int)$data['pid'], (array)$pid ) && $data['pid'] > 0 ) {

						$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE id = '$data[id]' and identity = '$identity'" );

					}
					else {
						$oldtoo[] = $data['email'];
					}//массив имеющихся записей

				}
				if ( $data['tip'] == 'copy' ) {

					if ( !in_array( (int)$data['clid'], (array)$cclid ) && (int)$data['clid'] > 0 ) {

						$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE id = '$data[id]' and identity = '$identity'" );

					}
					elseif ( !in_array( (int)$data['pid'], (array)$cpid ) && (int)$data['pid'] > 0 ) {

						$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE id = '$data[id]' and identity = '$identity'" );

					}
					else {
						$oldcopy[] = $data['email'];
					}//массив имеющихся записей

				}

			}

			//добавим новых адресатов
			foreach ( $to as $t ) {

				if ( !in_array( $t['email'], $oldtoo ) && $t['email'] != '' ) {

					if ( $t['name'] == '' ) {
						$t['name'] = $t['email'];
					}

					//для свободных email попробуем найти в базе
					if ( (int)$t['clid'] < 1 && (int)$t['pid'] < 1 ) {

						if ( $pid > 0 ) {

							$t['pid']  = $pid;
							$t['name'] = current_person( $pid );

						}
						else {

							$result = $db -> getRow( "SELECT clid,title FROM {$sqlname}clientcat WHERE mail_url LIKE '%".$t['email']."%' and identity = '$identity'" );
							$clid   = (int)$result['clid'];
							$client = $result['title'];

							if ( $clid > 0 ) {
								$t['clid'] = $clid;
							}
							if ( $client != '' ) {
								$t['name'] = $client;
							}

						}

					}
					//для свободных email

					$db -> query( "INSERT INTO {$sqlname}ymail_messagesrec SET ?u", [
						'mid'      => $id,
						'tip'      => 'to',
						'email'    => $t['email'],
						'name'     => $t['email'],
						'clid'     => (int)$t['clid'],
						'pid'      => (int)$t['pid'],
						'identity' => $identity
					] );

				}

			}

			foreach ( $copy as $c ) {

				if ( !in_array( $c['email'], $oldcopy ) && $c['email'] != '' ) {

					if ( $c['name'] == '' ) {
						$c['name'] = $c['email'];
					}

					if ( (int)$c['pid'] > 0 ) {
						$c['name'] = current_person( (int)$c['pid'] );
					}
					if ( (int)$c['clid'] > 0 ) {
						$c['name'] = current_client( (int)$c['clid'] );
					}

					$db -> query( "INSERT INTO {$sqlname}ymail_messagesrec SET ?u", [
						'mid'      => $id,
						'tip'      => 'to',
						'email'    => $c['email'],
						'name'     => $c['email'],
						'clid'     => (int)$c['clid'],
						'pid'      => (int)$c['pid'],
						'identity' => $identity
					] );

				}

			}

		}

		//если новое сообщение
		else {

			$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

			$db -> query( "INSERT INTO {$sqlname}ymail_messages SET ?u", [
				'datum'    => current_datumtime(),
				'folder'   => 'draft',
				'priority' => $priority,
				'fromm'    => $from,
				'fromname' => $fromname,
				'theme'    => $theme,
				'content'  => $content,
				'iduser'   => (int)$iduser1,
				'did'      => (int)$did,
				'identity' => $identity
			] );
			$id = $db -> insertId();
			//print $db -> lastQuery();

			// добавим новые загруженные файлы
			foreach ( $filed as $file ) {

				if ( $file['file'] != '' ) {

					$db -> query( "INSERT INTO {$sqlname}ymail_files SET ?u", [
						'mid'      => $id,
						'datum'    => current_datumtime(),
						'name'     => $file['name'],
						'file'     => $file['file'],
						'identity' => $identity
					] );
					$fidd = $db -> insertId();

					$uploads[] = [
						"fid"  => (int)$fidd,
						"icon" => get_icon3( $file['name'] ),
						"name" => $file['name'],
						"file" => $file['file']
					];

				}

			}

			// доавим файлы из документов
			if ( !empty( $xfid ) ) {

				$db -> query( "UPDATE {$sqlname}ymail_messages SET fid = '".yimplode( ",", $xfid )."' WHERE id = '$id' and identity = '$identity'" );

			}

			// доавим файлы из документов
			$xfile = array_combine( (array)$xfile, (array)$xname );

			$r = [];

			foreach ( $xfile as $file => $name ) {

				if ( $name != '' ) {

					//скопируем файл в папку ymail
					copyFile( $uploaddir.$file, $uploaddir.'ymail/' );

					$r[]  = $db -> query( "INSERT INTO {$sqlname}ymail_files SET ?u", [
						'datum'    => current_datumtime(),
						'mid'      => (int)$id,
						'name'     => $name,
						'file'     => $file,
						'identity' => $identity
					] );
					$ffid = $db -> insertId();

					$uploads[] = [
						"fid"  => (int)$ffid,
						"icon" => get_icon3( $file ),
						"name" => $name,
						"file" => $file
					];

				}

			}

			/*
			createDir($rootpath."/cash/mailer");
			file_put_contents($rootpath."/cash/mailer/mailer-{$id}.json", json_encode_cyr([
				"params" => $params,
				"xfile"  => $xfile,
				"r" => $r
			]));
			*/

			// добавим существующие файлы (если идет пересылка)
			foreach ( $fid as $f ) {

				if ( $f != '' ) {

					$r    = $db -> getRow( "SELECT * FROM {$sqlname}ymail_files WHERE id = '$f' and identity = '$identity'" );
					$name = $r['name'];
					$file = $r['file'];

					$db -> query( "INSERT INTO {$sqlname}ymail_files SET ?u", [
						'mid'      => (int)$id,
						'name'     => $name,
						'file'     => $file,
						'identity' => $identity
					] );

				}

			}

			// добавим адресатов
			foreach ( $to as $t ) {

				if ( $t['name'] == '' ) {
					$t['name'] = $t['email'];
				}

				if ( (int)$t['pid'] > 0 ) {
					$t['name'] = current_person( (int)$t['pid'] );
				}
				if ( (int)$t['clid'] > 0 ) {
					$t['name'] = current_client( (int)$t['clid'] );
				}

				//найдем, к какой организации относится контакт
				if ( (int)$t['pid'] > 0 && (int)$t['clid'] < 1 ) {
					$t['clid'] = getPersonData( $t['pid'], 'clid' );
				}

				//для свободных email попробуем найти в базе
				if ( (int)$t['clid'] < 1 && (int)$t['pid'] < 1 ) {

					$result = $db -> getRow( "SELECT pid,ptitle FROM {$sqlname}personcat WHERE mail LIKE '%".$t['email']."%' and identity = '$identity'" );
					$pid    = (int)$result['pid'];
					$person = $result['person'];

					if ( $pid > 0 ) {

						$t['pid'] = $pid;
						if ( $person != '' ) {
							$t['name'] = $person;
						}

					}
					else {

						$result = $db -> getRow( "SELECT clid,title FROM {$sqlname}clientcat WHERE mail_url LIKE '%".$t['email']."%' and identity = '$identity'" );
						$clid   = (int)$result['clid'];
						$client = $result['title'];

						if ( $clid > 0 ) {
							$t['clid'] = $clid;
						}
						if ( $client != '' ) {
							$t['name'] = $client;
						}

					}

				}
				//для свободных email

				$db -> query( "INSERT INTO {$sqlname}ymail_messagesrec SET ?u", [
					'mid'      => (int)$id,
					'tip'      => 'to',
					'email'    => $t['email'],
					'name'     => $t['name'],
					'clid'     => (int)$t['clid'],
					'pid'      => (int)$t['pid'],
					'identity' => $identity
				] );

			}

			// добавим получателей копии. Не активно
			foreach ( $copy as $t ) {

				if ( (int)$t['pid'] > 0 ) {
					$t['name'] = current_person( (int)$t['pid'] );
				}

				if ( (int)$t['clid'] > 0 ) {
					$t['name'] = current_client( (int)$t['clid'] );
				}

				$db -> query( "INSERT INTO {$sqlname}ymail_messagesrec SET ?u", [
					'mid'      => (int)$id,
					'tip'      => 'to',
					'email'    => $t['email'],
					'name'     => $t['name'],
					'clid'     => (int)$t['clid'],
					'pid'      => (int)$t['pid'],
					'identity' => $identity
				] );

			}

		}

		$this -> id     = $id;
		$this -> attach = $uploads;
		$this -> Error  = $err;

		//print_r($tags);
		//print_r(get_object_vars($this));

	}

	/**
	 * вывод данных сообщения
	 *
	 * ```php
	 * $mail = new Salesman\Mailer();
	 * $mail -> id = 27620;
	 * $mail -> mailView();
	 * $email = $mail -> View;
	 * ```
	 */
	public function mailView(): void {

		global $tipuser, $isadmin;

		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$fpath    = $this -> fpath;
		$id       = $this -> id;
		$otherSettings = $this -> otherSettings;

		//$params['id'] = $this ->id;

		$clids        = [];
		$pids         = [];
		$isfromuser   = false;
		$attachments  = [];
		$clid         = $pid = $rid = 0;
		$sizeFiles    = 0;
		$countFiles   = 0;
		$attach       = [];
		$afrom        = [];
		$ato          = [];
		$acopy        = [];
		$actions      = NULL;
		$toactions    = NULL;
		$isdownload   = false;
		$emptyMessage = false;
		$needDownload = 0;
		$haveAccess   = false;

		$View = [
			"emptyMessage" => true
		];

		$blacklist = Blacklist();

		//print $id."\n";

		if ( $id > 0 ) {

			$result   = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );
			$datum    = $result['datum'];
			$content  = htmlspecialchars_decode( $result['content'] );
			$theme    = $result['theme'];
			$folder   = $result['folder'];
			$priority = $result['priority'];
			$from     = $result['fromm'];
			$fromname = $result['fromname'];
			$iduser   = $result['iduser'];
			$state    = $result['state'];
			$hid      = $result['hid'];
			$trash    = $result['trash'];
			$uid      = $result['uid'];
			$xfid     = $result['fid'];
			$did      = ($result['did'] > 0) ? $result['did'] : NULL;

			//print $content;

			$userInfo = User ::info( $iduser1 )['result'];

			//print_r($userInfo);

			if ( $otherSettings['mailerMsgUnion'] ) {

				if ( in_array( $userInfo['tip'], ['Руководитель организации', 'Руководитель отдела'] ) || $userInfo['isadmin'] == 'on' ) {

					$haveAccess = true;

				}

			}

			$date = get_sfdate2( $datum );

			if ( $state == 'unread' ) {

				$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'read' WHERE id = '$id' and identity = '$identity'" );

				if ( $this -> boxSettings['ymailOnReadSeen'] || (string)$this -> boxSettings['ymailOnReadSeen'] == 'true' ) {

					// отметим прочитанным
					self ::mailAction( $id, 'seen' );

				}

			}

			/**
			 * Контроль диска
			 */
			$diskControl = 0;
			$diskUsage   = getFileLimit();

			if ( $diskUsage['total'] == 0 || $diskUsage['percent'] < 100 ) {
				$diskControl = 1;
			}
			//--контроль диска

			if ( $folder != '' && $state != 'deleted' ) {

				$content = self ::clearHtml( $content );

				//Находим кому и от кого письмо
				$result = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$id' and identity = '$identity'" );
				while ($data = $db -> fetch( $result )) {

					if ( $data['clid'] > 0 && $data['pid'] < 1 ) {

						$type = getClientData( $data['clid'], 'type' );

						$clids[] = $data['clid'];

						$t = [
							"name"  => current_client( $data['clid'] ),
							"email" => $data['email'],
							"clid"  => $data['clid'],
							"icon"  => strtr( $type, self::CLIENTICON )
						];

					}
					elseif ( $data['pid'] > 0 ) {

						$pids[] = $data['pid'];

						$t = [
							"name"  => current_person( $data['pid'] ),
							"email" => $data['email'],
							"pid"   => $data['pid'],
							"icon"  => "icon-user-1"
						];

					}
					else {

						if ( $data['name'] == '0' ) {
							$data['name'] = '';
						}

						$blacklist = self ::blackList();

						$t = [
							"unknown" => true,
							"name"    => $data['name'],
							"email"   => $data['email'],
							"icon"    => "icon-user-1",
							"action"  => [
								"id"        => $id,
								"from"      => $data['email'],
								"rid"       => $data['id'],
								"blacklist" => (in_array( $from, $blacklist )) ? true : NULL
							]
						];

					}

					if ( $data['tip'] == 'to' ) {
						$ato[] = $t;
					}
					elseif ( $data['tip'] == 'copy' ) {
						$acopy[] = $t;
					}

				}

				// время получения/отправки в констексте количества прошедшего времени
				$diff     = diffDate( $datum );
				$diffyear = get_year( date( 'Y' ) ) - get_year( $datum );

				if ( $folder == 'inbox' ) {

					$ato[] = [
						"name"   => current_user( $iduser ),
						"iduser" => $iduser,
						"icon"   => "icon-user-1"
					];

					$result = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '$id' and identity = '$identity'" );
					$email  = $result['email'];
					$name   = $result['name'];
					$pid    = (int)$result['pid'];
					$clid   = (int)$result['clid'];
					$rid    = (int)$result['id'];

					//поищем сотрудника
					$iduserFrom = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_settings WHERE settings LIKE '%$email%' and identity = '$identity'" );
					if ( $iduserFrom > 0 ) {

						$t = [
							"name"   => current_user( $iduserFrom ),
							"iduser" => $iduserFrom,
							"icon"   => "icon-user-1",
							"action" => [
								"id"        => $id,
								"from"      => $email,
								"rid"       => $rid,
								"blacklist" => (in_array( $from, $blacklist )) ? true : NULL
							]
						];

						$isfromuser = true;

					}
					else {

						//поищем email в базе
						if ( $clid == 0 && $pid == 0 ) {

							$resultt = $db -> getRow( "SELECT pid, clid FROM {$sqlname}personcat WHERE mail LIKE '%$from%' AND identity = '$identity'" );
							$pid     = (int)$resultt['pid'];
							$clid    = (int)$resultt['clid'];

							if ( $clid == 0 && $pid == 0 ) {
								$clid = (int)$db -> getOne( "SELECT clid FROM {$sqlname}clientcat WHERE mail_url LIKE '%$from%' AND identity = '$identity'" );
							}

							if ( $clid > 0 || $pid > 0 ) {
								$db -> query( "UPDATE {$sqlname}ymail_messagesrec SET ?u WHERE mid = '$id' and identity = '$identity'", [
									'pid'  => $pid,
									'clid' => $clid
								] );
							}

						}

						if ( $clid > 0 && $pid == 0 ) {

							$type = getClientData( $clid, 'type' );

							$t = [
								"name"  => current_client( $clid ),
								"email" => $email,
								"clid"  => $clid,
								"icon"  => strtr( $type, self::CLIENTICON )
							];

						}
						elseif ( $pid > 0 ) {

							$t = [
								"name"  => current_person( $pid ),
								"email" => $email,
								"pid"   => $pid,
								"icon"  => "icon-user-1"
							];

						}
						else {

							$t = [
								"unknown" => true,
								"name"    => ($name) ? : $from,
								"email"   => $email,
								"action"  => [
									"id"        => $id,
									"from"      => $email,
									"rid"       => $rid,
									"blacklist" => (in_array( $from, $blacklist )) ? true : NULL
								]
							];

						}

					}

					$afrom = $t;

				}
				if ( $folder == 'sended' ) {

					$afrom = [
						"name"   => $fromname,
						"email"  => $from,
						"iduser" => $iduser,
						"icon"   => "icon-user-1"
					];

				}
				if ( $folder == 'draft' ) {

					$set = $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '$iduser' and identity = '$identity'" );
					$set = json_decode( $set, true );

					$afrom = [
						"name"   => current_user( $iduser, "yes" ),
						"email"  => $set['ymailFrom'],
						"iduser" => $iduser,
						"icon"   => "icon-user-1"
					];

					$isfromuser = true;

				}

				if ( $diffyear == 0 ) {
					$date = ($diff < 1) ? "Сегодня в ".get_time( $datum ) : get_dateru( $datum )." в ".get_time( $datum );
				}

				/**
				 * Вложенные файлы
				 */
				$uploaddir = $this -> rootpath.'/files/'.$fpath;

				//файлы, загруженные в систему
				$xfid   = yexplode( ",", (string)$xfid );
				$xcount = count( (array)$xfid );

				//файлы, оставшиеся в письме
				$resultt    = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
				$count      = $db -> affectedRows( $resultt );
				$countFiles = $count + $xcount;

				if ( $countFiles > 0 ) {

					$fnames = [];

					// файлы, перемещенные в систему
					if ( !empty( $xfid ) ) {

						$result = $db -> query( "SELECT * FROM {$sqlname}file WHERE fid IN (".implode( ",", str_replace( ";", ",", $xfid ) ).") and identity = '$identity'" );
						while ($da = $db -> fetch( $result )) {

							$size = filesize( $uploaddir.$da['fname'] );
							$fext = texttosmall( substr( $da['fname'], strrpos( $da['fname'], '.' ) + 1 ) );

							$sizeFiles += $size;

							if ( $size > 0 ) {

								$attach[] = [
									"fid"    => $da['fid'],
									"name"   => $da['ftitle'],
									"file"   => $da['fname'],
									"icon"   => get_icon2( $da['fname'] ),
									"tip"    => in_array( $fext, [
										"png",
										"jpeg",
										"jpg",
										"gif"
									] ) ? 'pic' : NULL,
									"size"   => num_format( $size / 1000 ),
									"infile" => true
								];

							}
							else {

								$attach[] = [
									"id"     => $da['id'],
									"name"   => $da['ftitle'],
									"file"   => $da['fname'],
									"icon"   => get_icon2( $da['fname'] ),
									"infile" => true
								];

								$attachments[] = ["file" => $da['ftitle']];

							}

							$fnames[] = $da['ftitle'];

						}

					}

					// файлы, остающиеся в пределах Почтовика
					while ($data = $db -> fetch( $resultt )) {

						$ft = '';

						$size = filesize( $uploaddir.'ymail/'.$data['file'] );
						$fext = texttosmall( substr( $data['file'], strrpos( $data['file'], '.' ) + 1 ) );

						$sizeFiles += $size;

						if ( $size > 0 && !in_array( $data['name'], $fnames ) ) {

							$attach[] = [
								"id"     => $data['id'],
								"name"   => $data['name'],
								"file"   => $data['file'],
								"icon"   => get_icon2( $data['name'] ),
								"tip"    => in_array( $fext, [
									"png",
									"jpeg",
									"jpg",
									"gif"
								] ) ? 'pic' : NULL,
								"size"   => num_format( $size / 1000 ),
								"path"   => $fpath."ymail/",
								"infile" => NULL
							];

							$attachments[] = ["file" => $data['name']];

						}
						elseif ( !in_array( $data['name'], $fnames ) ) {

							$attach[] = [
								"id"     => NULL,
								"name"   => $data['name'],
								"file"   => $data['file'],
								"icon"   => get_icon2( $data['name'] ),
								"tip"    => $ft,
								"size"   => num_format( $size / 1000 ),
								"infile" => NULL
							];

							$attachments[] = ["file" => $data['name']];

							$needDownload++;

						}

					}

					// требуется ли скачивать не скачанные файлы
					$isdownload = (!empty( $attachments )) ? true : NULL;
					//$needdownload = ($needDownload > 0) ? true : NULL;

				}

			}
			else {

				//при не выбранном письме
				$emptyMessage = true;

			}

			if ( $folder == 'inbox' && empty( $clids ) && empty( $pids ) && !str_contains( $from, 'replay' ) && !$isfromuser ) {
				$actions = [
					"id"        => $id,
					"from"      => $from,
					"rid"       => $rid,
					"blacklist" => (in_array( $from, $blacklist )) ? true : NULL
				];
			}

			//добавим иконку наличия email в заявках
			$countInLeads = (int)$db -> getOne( "SELECT COUNT(*) FROM {$sqlname}leads WHERE email = '$from' AND status = '0' AND identity = '$identity'" );

			$nextid = (int)$db -> getOne( "SELECT id FROM {$sqlname}ymail_messages WHERE id > '$id' AND state = 'unread' and iduser = '$iduser' and identity = '$identity' ORDER BY id DESC LIMIT 1" );

			$View = [
				"id"                => $id,
				"uid"               => $uid,
				"from"              => $afrom,
				"fromname"          => $fromname,
				"isfromuser"        => $isfromuser ? true : NULL,
				"priority"          => strtr( $priority, self::PRIORITY ),
				"content"           => $content,
				"isInbox"           => $folder == 'inbox' ? true : NULL,
				"isSended"          => $folder == 'sended' ? true : NULL,
				"isDraft"           => $folder == 'draft' ? true : NULL,
				"isNotDraft"        => $folder != 'draft' ? true : NULL,
				"toTrash"           => $trash != 'yes' && $iduser1 == $iduser ? true : NULL,
				"fromTrash"         => $trash == 'yes' ? true : NULL,
				"folder"            => $folder,
				"folderIcon"        => strtr( $folder, self::FOLDERICON ),
				"folderName"        => strtr( $folder, self::FOLDERNAME ),
				"date"              => $date,
				"to"                => $ato,
				"copy"              => $acopy,
				"haveTo"            => !empty( $ato ) ? true : NULL,
				"haveCopy"          => !empty( $acopy ) ? true : NULL,
				"theme"             => $theme,
				"links"             => [
					"did"  => ($did > 0) ? $did : NULL,
					"clid" => ($clid > 0) ? $clid : NULL,
					"pid"  => ($pid > 0) ? $pid : NULL,
				],
				"did"               => $did,
				//"clid"              => $clid,
				//"pid"               => $pid,
				"countClidsPids"    => ((!empty( $clids )) > 0 || (!empty( $pids )) > 0) ? 'yes' : '',
				"hid"               => $hid > 0 ? $hid : NULL,
				"files"             => $attach,
				"isdownload"        => $isdownload,
				"needdownload"      => ($needDownload > 0) ? true : NULL,
				"emptyfile"         => ($sizeFiles == 0) ? 1 : NULL,
				"attachments"       => !empty( $attachments ) ? $attachments : NULL,
				"hasAttachments"    => !empty( $attachments ) ? true : NULL,
				"emptyMessage"      => $emptyMessage,
				"countFiles"        => $countFiles > 0 ? true : NULL,
				"isAccesse"         => ($iduser1 == $iduser || $haveAccess) ? true : NULL,
				"inleads"           => ($countInLeads > 0) ? true : NULL,
				"actions"           => !empty( $actions ) ? $actions : NULL,
				"toactions"         => $toactions,
				"diskPercent"       => $diskUsage['percent'],
				"diskControl"       => ($diskControl > 0) ? 1 : '',
				"userIsOwner"       => ($iduser1 == $iduser) ? true : NULL,
				"elementIsDisabled" => ($iduser1 != $iduser && !$haveAccess) ? true : NULL,
				"next"              => $nextid,
				//"boxSettings" => $this -> boxSettings
			];

		}

		$this -> View = $View;

	}

	/**
	 * Получение сообщений
	 *
	 * ```php
	 * $mail = new Salesman\Mailer()
	 * $mail -> params['ignoreattachments'] = 1 // игнорировать вложения ( для Сборщика заявок )
	 * $mail -> params['deletemess'] = true // удалить письмо с сервера ( для Сборщика заявок )
	 * $mail -> params['smtp'] = [] // передать собственные настройки соединения ( для Сборщика заявок )
	 * $mail -> params['ignoreuids'] = [] // передать массив uid сообщений, которые следует игнорировать
	 * $mail -> ignoreuids = [] // передать массив uid сообщений, которые следует игнорировать (альтернатива)
	 * $mail -> iduser = 14 // id сотрудника ( не нужен, если задан mailID )
	 * $mail -> uids = [] // передать массив uid сообщений, которые следует загружать (например для повторной загрузки)
	 * $mail -> box = 'INBOX' // проверяемый ящик (INBOX, SEND)
	 * $mail -> days = 1 // количество дней проверки ( по умолчанию = 7 )
	 * $mail -> mailID = 2 // id настроек почтового ящика из таблицы *ymail_settings
	 * $mail -> mailGet()
	 * $email = $mail -> Messages
	 * ```
	 */
	public function mailGet(): void {

		global $bdtimezone;

		$identity = $this -> identity;
		$iduser1  = $this -> iduser1;
		$sqlname  = $this -> sqlname;
		$fpath    = $this -> fpath;
		$ivc      = $this -> ivc;
		$skey     = $this -> skey;
		$opts     = $this -> opts;
		$rootpath = $this -> rootpath;

		// id сотрудника, для которого проверяем почту
		$iduser = $this -> iduser;
		// различные параметры в массиве
		$params = $this -> params;
		// проверяемая папка
		$box = $this -> box;
		// ограничение в днях
		$days = $this -> days;
		// id настроек почтового ящика ( для мультиящиков )
		$mailID = $this -> mailID;
		// загружать уже существующие письма
		$ignoreExist = $this -> ignoreExist ?? false;

		// преобразуем значения массива параметров в переменные
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$this ->{$key} = $val;
			}
		}

		//print_r(get_object_vars($this));

		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail';
		$messages = [];
		$iduser   = ($iduser > 0) ? $iduser : $iduser1;

		//проверяем папку для загрузки и если нет, то создаем
		createDir( $ym_fpath );

		//проверяем папку для загрузки и если нет, то создаем
		createDir( $ym_fpath.'/inbody' );

		if ( !file_exists( $rootpath."/cash/ymail_error.log" ) ) {

			$file = fopen( $rootpath."/cash/ymail_error.log", 'wb' );
			fclose( $file );

		}

		ini_set( 'log_errors', 'On' );
		ini_set( 'error_log', $rootpath.'/cash/ymail_error.log' );


		$db = new SafeMySQL( $opts );

		if ( !isset( $params['smtp'] ) ) {

			//если не указан почтовый ящик ( для реализации 1 Юзер = 1 Ящик )
			if ( $mailID == 0 ) {

				//читаем настройки
				$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json";

				if ( file_exists( $settingsYMail ) ) {
					$param = json_decode( file_get_contents( $settingsYMail ), true );
				}
				else {
					$param = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '$iduser' AND identity = '$identity'" ), true );
				}

			}
			// если указан ID конкретного ящика ( для реализации 1 Юзер = ХХХ ящиков )
			else {

				$p      = $db -> getRow( "SELECT settings, iduser FROM {$sqlname}ymail_settings WHERE id = '$mailID'" );
				$param  = json_decode( $p['settings'], true );
				$iduser = $p['iduser'];

			}

			$param['ymailUser'] = self ::decrypt( $param['ymailUser'], $skey, $ivc );
			$param['ymailPass'] = self ::decrypt( $param['ymailPass'], $skey, $ivc );

		}
		// возможность отправить настройки в виде параметров
		else {

			$param = [
				"ymailUser"       => $params['smtp']['smtp_user'],
				"ymailPass"       => $params['smtp']['smtp_pass'],
				"ymailInHost"     => $params['smtp']['smtp_host'],
				"ymailInPort"     => $params['smtp']['smtp_port'],
				"ymailInSecure"   => $params['smtp']['smtp_secure'],
				"ymailInProtocol" => $params['smtp']['smtp_protocol'],
				"ymailFolderSent" => $params['smtp']['box'] ?? "INBOX"
			];

		}

		//print_r($param);

		unset( $db );
		$db = new SafeMySQL( $opts );
		$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

		//если параметры не указаны - отключаем проверку
		if ( $param['ymailUser'] != '' && $param['ymailPass'] != '' ) {

			$folder = ($box == 'SEND') ? 'sended' : 'inbox';

			$mexist = $params['ignoreuids'] ?? (!$ignoreExist ? $db -> getCol( "SELECT uid FROM {$sqlname}ymail_messages WHERE folder = '$folder' and uid > 0 and datum BETWEEN '".current_datum( 10 )." 00:00:00' AND '".current_datum( -2 )." 23:59:59' AND iduser = '$iduser' AND identity = '$identity' ORDER BY uid DESC" ) : []);

			//print_r($mexist);

			//максимальная дата проверки
			$date = date( "d-M-Y", strtotime( "-$days days" ) );

			if ( $box == 'SEND' ) {

				$box = ImapUtf7 ::encode( $param['ymailFolderSent'] );

				if ( $box == '' ) {
					goto extt;
				}

			}

			//-start--проверка получения почты
			if ( $param['ymailInSecure'] != '' ) {
				$param['ymailInSecure'] = '/'.$param['ymailInSecure'].'/novalidate-cert'.(!$params['deletemess'] ? "/readonly" : "");
			}

			$mailbox = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$param['ymailInSecure'].'}'.$box;
			$conn    = imap_open( $mailbox, $param['ymailUser'], $param['ymailPass'] );

			if ( !$conn ) {

				$this -> Error = 'Ошибка подключения';

				return;

			}

			$error = imap_last_error();

			//print array2string($param);
			//print $mailbox."\n";
			//print $error."\n";

			$msgnom = [];
			$msguid = [];

			// Поиск параметров только указанных UIDS
			if ( !empty( $this -> uids ) ) {

				foreach ( $this -> uids as $uid ) {

					$emails = imap_fetch_overview( $conn, $uid.":*", FT_UID );
					//var_dump($emails);

					$msguid[] = $emails[0] -> uid;
					$msgnom[] = $emails[0] -> msgno;

				}

			}

			//print_r($msguid);
			//print_r($msgnom);

			/**
			 * todo: добавить возможности проверки по уточнениям - criteria:
			 * criteria - Строка, разделённая пробелами, в которой допустимо использовать следующие ключевые слова. Любые аргументы, состоящие из нескольких слов, должны быть заключены в двойные кавычки (например FROM "joey smith"). Результат будет совпадать со всеми заданными в параметре criteria критериями.
			 * ALL - возвращать все сообщения, соответствующие остальной части критерия
			 * ANSWERED - совпадать с сообщениями с установленным флагом \\ANSWERED
			 * BCC "string" - совпадать с сообщениями со "string" в поле Bcc:
			 * BEFORE "date" - совпадать с сообщениями с Date: перед "date"
			 * BODY "string" - совпадать с сообщениями со "string" в теле сообщения
			 * CC "string" - совпадать с сообщениями со "string" в поле Cc:
			 * DELETED - совпадать с удалёнными сообщениями
			 * FLAGGED - совпадать с сообщениями с установленным флагом \\FLAGGED (иногда называемым Important или Urgent)
			 * FROM "string" - совпадать с сообщениями со "string" в поле From:
			 * KEYWORD "string" - совпадать с сообщениями со "string" - ключевым словом
			 * NEW - совпадать с новыми сообщениями
			 * OLD - совпадать со старыми сообщениями
			 * ON "date" - совпадать с сообщениями с Date: совпадающей с "date"
			 * RECENT - совпадать с сообщениями с установленным флагом \\RECENT
			 * SEEN - совпадать с прочитанными сообщениями (установлен флаг \\SEEN)
			 * SINCE "date" - совпадать с сообщениями с Date: после "date"
			 * SUBJECT "string" - совпадать с сообщениями со "string" в поле Subject:
			 * TEXT "string" - совпадать с сообщениями с текстовой "string"
			 * TO "string" - совпадать с сообщениями со "string" в поле To:
			 * UNANSWERED - совпадать с сообщениями, на которые не дан ответ
			 * UNDELETED - совпадать с сообщениями, которые не удалены
			 * UNFLAGGED - совпадать с сообщениями, которые не помечены флагами
			 * UNKEYWORD "string" - совпадать с сообщениями, не имеющими ключевого слова "string"
			 * UNSEEN - совпадать с сообщениями, которые ещё не прочитаны
			 */

			// если поиск не конкретный UIDS, то получаем по дате
			if ( empty( $msguid ) ) {

				$msgnom = imap_search( $conn, 'SINCE '.$date );//, SE_UID); //выводим uid писем
				$msguid = imap_search( $conn, 'SINCE '.$date, SE_UID ); //выводим uid писем

				//print_r($msguid);
				//print_r($mexist);

			}

			//количество писем в ящике
			$all = !empty( $msguid ) ? count( $msguid ) - 1 : 0;

			//максимум загружаем 30 писем за раз
			$max = $all - 50;

			//принимаем с последнего письма
			for ( $i = $all; $i > $max; $i-- ) {

				$attach = [];

				if ( !in_array( $msguid[ $i ], (array)$mexist ) ) {

					//$uid = imap_uid($conn, $msgno[ $i ]);

					$uid   = $msguid[ $i ];
					$msgno = $msgnom[ $i ];

					//print $uid." -- ".$msgno."\n";

					if ( $uid > 0 ) {

						$header = imap_headerinfo( $conn, $msgno );

						//читаем аттачи
						$structure = imap_fetchstructure( $conn, $msgno );

						if ( !is_object( $structure ) ) {
							$structure = (object)$structure;
						}

						$parts    = $structure -> parts;
						$numparts = !empty( $parts ) ? count( (array)$parts ) : 0;

						$asub     = self ::objectToArray( $structure );
						$codeBody = strtoupper( self ::searchEncode( $asub['parts'] ) );

						if ( $msguid[ $i ] == 6513 ) {
							//file_put_contents($rootpath."/cash/msg.json", json_encode_cyr($asub));
						}

						$codeBody = ($codeBody == 'UTF8') ? "UTF-8" : $codeBody;

						if ( str_contains( $codeBody, 'UTF-8' ) ) {
							$codeBody = 'UTF-8';
						}

						//$muid      = $header -> message_id;
						$fromInfo  = $header -> from[0];
						$toInfo    = $header -> to[0];
						$replyInfo = $header -> reply_to[0];
						//$MailDate  = $header -> MailDate;

						$blacklist = self ::blackList();

						// выводим для проблемного письма
						if ( $msguid[ $i ] == 40139 ) {

							//print_r( $structure );
							//print_r($header);
							//print "\n".$codeBody."\n";

						}

						//если ящик в черном листе, то завершаем
						if ( in_array( $fromInfo -> mailbox."@".$fromInfo -> host, $blacklist ) ) {
							continue;
						}

						$details = [
							"fromAddr"  => isset( $fromInfo -> mailbox, $fromInfo -> host ) ? $fromInfo -> mailbox."@".$fromInfo -> host : "",
							"fromName"  => $fromInfo -> personal ?? "",
							"toAddr"    => isset( $toInfo -> mailbox, $toInfo -> host ) ? $toInfo -> mailbox."@".$toInfo -> host : "",
							"toName"    => $toInfo -> personal ?? "",
							"replyAddr" => isset( $replyInfo -> mailbox, $replyInfo -> host ) ? $replyInfo -> mailbox."@".$replyInfo -> host : "",
							"subject"   => $header -> subject ?? "",
							"udate"     => $header -> udate ?? "",
							//"udate"     => (isset( $header -> udate )) ? $header -> MailDate : "",
							//"udate"     => $header -> Date ?? "",
							"messageid" => $header -> message_id ?? ""
						];

						//print "d: ".$header -> Date." => ".modifyDatetime($header -> Date)."\n";
						//print "m: ".$header -> MailDate." => ".modifyDatetime($header -> MailDate)."\n";
						//print "u: ".$header -> udate." => ".modifyDatetime($header -> udate)."\n";

						// если не задано игнорирование вложений
						if ( !isset( $params['ignoreattachments'] ) ) {

							/**
							 * Вложения
							 */
							$attachments = [];
							$bimage      = [];

							if ( $numparts > 0 ) {

								$endwhile = false;
								$stack    = [];
								//$attachments = [];
								//$bimage      = [];
								$j = 0;

								while (!$endwhile) {

									if ( !$parts[ $j ] ) {

										if ( !empty( $stack ) ) {

											$parts = $stack[ count( $stack ) - 1 ]["p"];
											$j     = $stack[ count( $stack ) - 1 ]["i"] + 1;
											array_pop( $stack );

										}
										else {
											$endwhile = true;
										}

									}

									if ( !$endwhile ) {

										$partstring = "";
										$isJP       = false;

										foreach ( $stack as $s ) {
											$partstring .= ($s["i"] + 1).".";
										}

										$partstring .= ($j + 1);

										//print_r($parts[$j]);

										$dsp = strtoupper( $parts[ $j ] -> disposition );

										if ( in_array( $dsp, [
												"ATTACHMENT",
												"INLINE"
											] ) || in_array( strtoupper( $parts[ $j ] -> subtype ), [
												"PNG",
												"JPG",
												"JPEG",
												"GIF"
											] ) ) {

											$parts1[ $j ] = self ::objectToArray( $parts[ $j ] );

											$fnameOrig1 = $parts1[ $j ]['dparameters'][0]['value'];
											$fnameOrig2 = $parts1[ $j ]['parameters'][0]['value'];

											if ( $fnameOrig1 == '' ) {
												$fnameOrig1 = $fnameOrig2;
											}

											if ( str_contains( strtoupper( $fnameOrig1 ), "ISO-2022-JP" ) ) {
												$isJP = true;
											}

											if ( !$isJP ) {

												$fnameOrigg1 = imap_mime_header_decode( $fnameOrig1 );
												$fnameOrigg2 = imap_mime_header_decode( $fnameOrig2 );

												//print_r($fnameOrigg1);
												//print_r($fnameOrigg2);

												$filename1 = '';
												$filename2 = '';

												for ( $h = 0, $hMax = count( (array)$fnameOrigg1 ); $h < $hMax; $h++ ) {
													$filename1 .= $fnameOrigg1[ $h ] -> text;
												}

												for ( $h = 0, $hMax = count( (array)$fnameOrigg2 ); $h < $hMax; $h++ ) {
													$filename2 .= $fnameOrigg2[ $h ] -> text;
												}

											}
											else {

												$filename1 = $fnameOrig1;
												$filename2 = $fnameOrig2;

											}

											if ( $isJP ) {

												$filename1 = str_replace( "?", "№", self ::encodeToUtf8( $filename1 ) );
												$filename2 = str_replace( "?", "№", self ::encodeToUtf8( $filename2 ) );

											}

											//если имя файла в кодировке WINDOWS-1251
											if ( str_contains( strtoupper( $fnameOrig1 ), "WINDOWS-1251" ) ) {

												$filename1 = mb_convert_encoding( $filename1, "UTF-8", "WINDOWS-1251" );
												$filename2 = mb_convert_encoding( $filename1, "UTF-8", "WINDOWS-1251" );

											}

											//если имя файла в кодировке KOI8-R
											if ( str_contains( strtoupper( $fnameOrig1 ), "KOI8-R" ) || str_contains( strtoupper( $fnameOrig1 ), "KOI8R" ) ) {

												$filename1 = mb_convert_encoding( $filename1, "UTF-8", "KOI8-R" );
												$filename2 = mb_convert_encoding( $filename1, "UTF-8", "KOI8-R" );

											}

											//print $filename1."\n";
											//print "utf=".stripos( strtoupper( $filename1 ), "UTF-8''" )."\n";
											//print "utf=".stripos( strtoupper( $filename1 ), "UTF8''" )."\n";
											//print "utf=".str_contains( strtoupper( $filename1 ), "UTF8''" )."\n";

											if ( str_contains( strtoupper( $filename1 ), "UTF-8''" ) || str_contains( strtoupper( $filename1 ), "UTF8''" ) ) {
												$filename = $filename2;
											}
											else {
												$filename = $filename1;
											}

											if ( $filename == 'UTF-8' ) {
												$fnameOrigg = imap_mime_header_decode( $parts[ $j ] -> parameters[1] -> value );

												$filename = '';
												for ( $h = 0, $hMax = count( (array)$fnameOrigg ); $h < $hMax; $h++ ) {
													$filename .= $fnameOrigg[ $h ] -> text;
												}

											}

											$filename = str_replace( [
												"'",
												"`",
												"$",
												")",
												"(",
												'\t',
												'\n'
											], "", $filename );

											$fext = str_replace( [
												' ',
												'\t',
												'\n'
											], "", substr( $filename, strrpos( $filename, '.' ) + 1 ) );

											if ( $fext != '' && $dsp == "ATTACHMENT" ) {

												$fsize = $parts[ $j ] -> bytes;

												$attachments[] = [
													"file"     => md5( $fsize.$filename.$details['fromAddr'] ).".".$fext,
													"name"     => $filename,
													"id"       => "0",
													"enc"      => $parts[ $j ] -> encoding,
													"partNum"  => $partstring,
													"filedata" => imap_fetchbody( $conn, $msgno, $partstring, FT_PEEK ),
													"size"     => $fsize
												];

											}
											elseif ( $fext != '' && $dsp == "INLINE" ) {

												//$file = $parts[ $j ] -> id;
												//$file = ($file == '') ? $filename.$msgno : $file;

												//$fname = time() + $j;
												$fsize = $parts[ $j ] -> bytes;

												$bimage[] = [
													"file"     => md5( $fsize.$filename.$details['fromAddr'] ).".".$fext,
													"name"     => $filename,
													"id"       => $parts[ $j ] -> id,
													"enc"      => $parts[ $j ] -> encoding,
													"partNum"  => $partstring,
													"filedata" => imap_fetchbody( $conn, $msgno, $partstring, FT_PEEK ),
													"size"     => $fsize
												];

											}
											//elseif (in_array(strtoupper($parts[ $j ] -> subtype), ["PNG", "JPG", "JPEG", "GIF"])){
											else {

												//$file = $parts[ $j ] -> id;
												//$file = ($file == '') ? $filename.$msgno[ $i ] : $file;

												$fext = strtolower( $parts[ $j ] -> subtype );

												//$fname = time() + $j;
												$fsize = $parts[ $j ] -> bytes;

												$bimage[] = [
													"file"     => md5( $fsize.$filename.$details['fromAddr'] ).".".$fext,
													//$fname.".".$fext,
													"name"     => $filename,
													"id"       => $parts[ $j ] -> id,
													"enc"      => $parts[ $j ] -> encoding,
													"partNum"  => $partstring,
													"filedata" => imap_fetchbody( $conn, $msgno, $partstring, FT_PEEK ),
													"size"     => $fsize
												];

											}

										}

									}

									if ( $parts[ $j ] -> parts ) {

										$stack[] = [
											"p" => $parts,
											"i" => $j
										]; // next stack

										$parts = $parts[ $j ] -> parts; // parts
										$j     = 0;
									}
									else {
										$j++;
									}

								}

							}

							//$parts = array();

							/**
							 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
							 * В противном случае получим ошибку "safemysql MySQL server has gone away"
							 */
							unset( $db );
							$db = new SafeMySQL( $opts );

							//загрузка файлов
							foreach ( $attachments as $attachment ) {

								$attach[] = [
									"uid"  => $uid,
									"part" => $attachment["partNum"],
									"enc"  => $attachment["enc"],
									"file" => $attachment["file"],
									"name" => $attachment["name"]
								];

								$diskUsage = getFileLimit();

								if ( $diskUsage['total'] == 0 || $diskUsage['percent'] < 100 ) {

									self ::downloadAttachment( $conn, $uid, $attachment["partNum"], $attachment["enc"], $ym_fpath, $attachment["file"] );

								}

							}

							//загрузка вложенных изображений
							$bodyimg = [];
							foreach ( $bimage as $bimg ) {

								$bodyimg[] = [
									"uid"  => $uid,
									"id"   => str_replace( [
										"<",
										">"
									], "", $bimg["id"] ),
									"part" => $bimg["partNum"],
									"enc"  => $bimg["enc"],
									"file" => $bimg["file"],
									"name" => $bimg["name"]
								];

								//загружаем файлы
								if ( !file_exists( $ym_fpath."/inbody/".$bimg["file"] ) ) {
									self ::downloadAttachment( $conn, $uid, $bimg["partNum"], $bimg["enc"], $ym_fpath."/inbody/", $bimg["file"] );
								}

							}

						}

						//ВАЖНО!!!
						//для нескольких получателей в Отправленных должно быть несколько toAddr + toName
						//но по факту приходит только 1 строка массива
						//может надо смотреть CopyTo
						//массив $toInfo

						$isJP = false;

						if ( str_contains( $details["subject"], 'ISO-2022-JP' ) ) {
							$isJP = true;
						}

						$namee  = imap_mime_header_decode( $details["fromName"] );
						$toname = imap_mime_header_decode( $details["toName"] );

						$themee = (!$isJP) ? imap_mime_header_decode( $details["subject"] ) : $details["subject"];


						/*if($msguid[ $i ] == 40160) {
							print_r( $themee );
						}*/


						$name = '';
						for ( $h = 0, $hMax = count( (array)$namee ); $h < $hMax; $h++ ) {
							$name .= " ".str_replace( "'", "", untag( $namee[ $h ] -> text ) );
						}


						$subject = '';

						if ( is_object( $themee ) || is_array($themee) ) {

							foreach ($themee as $item){

								$subject .= str_replace( "'", " ", untag( $item -> text ) );

							}

							/*for ( $h = 0, $hMax = count( (array)$themee ); $h < $hMax; $h++ ) {
								if ( !$isJP ) {
									$subject .= "".str_replace( "'", " ", untag( $themee[ $h ] -> text ) );
								}
							}*/
							//print $subject;

						}

						//бывает, что заголовок приходит в UTF-8 не зашифрованным, т.е. без приставок "=?UTF"
						//такие заголовки не будем перекодировать
						elseif ( strpos( $details["subject"], '=?' ) === false ) {
							$subject = $details["subject"];
						}

						else {
							$subject = self ::encodeToUtf8( $details["subject"] );
						}

						//кодировка заголовков, ибо надо проверять отдельно
						//потому что, сука, всякие веб-клиенты мэйл.сру, яндекс и пр. отправляют в разных кодировках
						$code = strtoupper( $themee[0] -> charset );

						//$details['datum'] = modifyDatetime( $details["udate"] );
						$details['datum'] = unix_to_datetime( $details["udate"] );
						$details['msgno'] = $msgno[ $i ];
						$details['theme'] = ($subject != '') ? trim( $subject ) : self ::encodeToUtf8( $details["subject"] );

						//декодируем имя отправителя и заголовок
						if ( $code == "KOI8-R" || $code == "KOI8R" ) {

							/*
							$details['fromName'] = iconv("KOI8-R", "UTF-8", trim($name));
							$details['toName']   = iconv("KOI8-R", "UTF-8", $toname[0] -> text);
							*/

							$details['fromName'] = mb_convert_encoding( trim( $name ), "UTF-8", "KOI8-R" );
							$details['toName']   = mb_convert_encoding( $toname[0] -> text, "UTF-8", "KOI8-R" );
							$details['theme']    = mb_convert_encoding ( $subject, "UTF-8", "KOI8-R" );


						}
						elseif ( $code == "WINDOWS-1251" ) {

							//if (!$isJP) $details['theme'] = iconv("WINDOWS-1251", "UTF-8", $subject);
							//$details['fromName'] = iconv("WINDOWS-1251", "UTF-8", trim($name));
							//$details['toName']   = iconv("WINDOWS-1251", "UTF-8", $toname[0] -> text);

							//$details['theme'] = mb_convert_encoding ( $subject, "UTF-8", "WINDOWS-1251" );
							$details['fromName'] = mb_convert_encoding( trim( $name ), "UTF-8", "WINDOWS-1251" );
							$details['toName']   = mb_convert_encoding( $toname[0] -> text, "UTF-8", "WINDOWS-1251" );

						}
						else {

							if ( !$isJP ) {
								$details['theme'] = $subject;
							}
							$details['fromName'] = trim( $name );
							$details['toName']   = $toname[0] -> text;

						}

						$details['theme']    = str_replace( [
							"'",
							"`",
							"$",
							")",
							"("
						], "", untag( $details['theme'] ) );
						$details['fromName'] = str_replace( [
							"'",
							"`",
							"$"
						], "", untag( $details['fromName'] ) );
						$details['toName']   = str_replace( [
							"'",
							"`",
							"$"
						], "", untag( $details['toName'] ) );

						//тело сообщения
						$html = self ::getBody( $uid, $conn );

						//декодируем текст письма, т.к. веб-клиенты типа Яндекса и Майл.сру отправляют заголовки в разных кодировках
						if ( str_contains( $codeBody, "ASCII" ) ) {
							$details['html']    = $html;
							$details['convert'] = "ASCII";
						}
						elseif ( $codeBody == "WINDOWS-1251" ) {
							//$details[ 'html' ] = $html;
							$details['html'] = str_replace( "charset=windows-1251", "charset=utf-8", mb_convert_encoding( $html, "UTF-8", "WINDOWS-1251" ) );
							//$details['html']    = mb_convert_encoding( $html, "UTF-8", "WINDOWS-1251" );
							$details['convert'] = "WINDOWS-1251";
						}
						elseif ( $codeBody == "KOI8-R" || $codeBody == "KOI8R" ) {
							$details['html']    = str_replace( "charset=koi8-r", "charset=utf-8", mb_convert_encoding( $html, "UTF-8", "KOI8-R" ) );
							$details['convert'] = "KOI8-R";
						}
						else {
							$details['html']    = $html;
							$details['convert'] = false;
						}

						$details['html'] = getHtmlBody( $details['html'] );

						if ( !empty( $bodyimg ) ) {
							foreach ( $bodyimg as $bimg ) {

								$details['html'] = str_replace( "cid:".$bimg['id'], "/files/".$fpath."ymail/inbody/".$bimg['file'], $details['html'] );

							}
						}

						//$details['html']    = htmlspecialchars($details['html']);
						$details['uid'] = $uid;

						if ( !empty( $attach ) ) {
							$details['attach'] = $attach;
						}

						if ( !empty( $bodyimg ) ) {
							$details['bodyimg'] = $bodyimg;
						}

						$messages[] = $details;

						//удаляем письма вручную
						if ( $params['deletemess'] ) {

							imap_delete( $conn, $msgno );

							//FT_UID - опция, чтобы моно было применить uid вместо msgno
							imap_delete( $conn, $uid, FT_UID );

						}

					}

				}

			}

			//если настроено, то удаляем письмо из ящика
			if ( $params['deletemess'] ) {

				imap_expunge( $conn );

			}

			imap_close( $conn );

			setcookie( "ymail", "" );

			$this -> Error = $error;

		}

		extt:

		$this -> Messages = $messages;

	}

	/**
	 * Обработка полученных методом mailGet писем
	 *
	 * @return array
	 * ```php
	 *  [
	 *      "result" => empty($err) ? "Получено $d писем <b>$mcount</b>" : "error",
	 *      "text" => empty($err) ? "Получено $d писем <b>$mcount</b>" : "Ошибка: ".implode("<br>", $err),
	 *      "mcount" => $mcount,
	 *      "last" => $last
	 *  ]
	 * ```
	 *
	 * ```php
	 * $mail           = new Salesman\Mailer();
	 * $mail -> iduser = $iduser1;
	 * $mail -> box    = $box;
	 * $mail -> mailGet();
	 *
	 * $messages = $mail -> Messages;
	 * $error    = $mail -> Error;
	 *
	 * $mail -> box      = $box;
	 * $mail -> Messages = $messages;
	 * $mail -> iduser   = $iduser1;
	 * $rez              = $mail -> mailGetWorker();
	 * ```
	 * @throws Exception
	 */
	public function mailGetWorker(): array {

		global $bdtimezone;

		$identity = $this -> identity;
		$iduser   = $this -> iduser;
		$sqlname  = $this -> sqlname;
		$fpath    = $this -> fpath;
		$opts     = $this -> opts;
		$rootpath = $this -> rootpath;
		$mailID   = $this -> mailID;

		$messages = $this -> Messages;
		$box      = $this -> box;

		$mailbox = ($box == 'SEND') ? 'sended' : 'inbox';
		$tip     = ($box == 'SEND') ? 'to' : 'from';

		$mcount = 0;
		$last   = 0;
		$err    = [];

		$db = new SafeMySQL( $opts );
		$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

		$db -> query( "UPDATE {$sqlname}ymail_settings SET lasttime = '".current_datumtime()."' WHERE iduser = '$iduser' and identity = '$identity'" );

		$cUser = current_user( $iduser );

		$ymfpath = $rootpath.'/files/'.$fpath.'ymail/';

		//если не указан почтовый ящик ( для реализации 1 Юзер = 1 Ящик )
		if ( $mailID == 0 ) {

			//читаем настройки
			$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json";

			if ( file_exists( $settingsYMail ) ) {
				$ymailSet = json_decode( file_get_contents( $settingsYMail ), true );
			}

			else {
				$ymailSet = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '$iduser' AND identity = '$identity'" ), true );
			}

		}

		// если указан ID конкретного ящика ( для реализации 1 Юзер = ХХХ ящиков )
		else {

			$p        = $db -> getRow( "SELECT settings, iduser FROM {$sqlname}ymail_settings WHERE id = '$mailID'" );
			$ymailSet = json_decode( $p['settings'], true );

		}

		foreach ( $messages as $message ) {

			unset( $db );
			$db = new SafeMySQL( $opts );
			$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

			//найдем тэг ID сделки в заголовке
			preg_match( '/(?<=\[D#)[0-9.\/]*(?=]$)/', $message['theme'], $didtag );
			$did = ($didtag[0] != '') ? (int)$didtag[0] : "0";

			if ( $message['theme'] == '' ) {
				$message['theme'] = "--Без темы--";
			}
			else {
				$message['theme'] = substr( $message['theme'], 0, 250 );
			}

			if ( $message['fromName'] == '' ) {
				$message['fromName'] = $message['fromAddr'];
			}

			//найдем аналогичные письма, чтобы исключить дубли
			$msgid = $db -> getOne( "SELECT id FROM {$sqlname}ymail_messages WHERE messageid = '$message[messageid]' and fromm = '$message[fromAddr]' and iduser = '$iduser' and identity = '$identity'" );

			//для сообщений из ящика Отправленные
			if ( $mailbox == 'sended' ) {

				if ( $message['fromName'] == '' ) {
					$message['fromName'] = $cUser;
				}

				if ( $message['fromName'] == $message['fromAddr'] ) {
					$message['fromName'] = $cUser;
				}

			}

			foreach ( $message['bodyimg'] as $bimg ) {
				$message['html'] = str_replace( "cid:".$bimg['id'], $ymfpath."inbody/".$bimg['file'], $message['html'] );
			}


			//если такого письма нет (для всех входящих точно нет, для проверки папки Отправленные возможно
			if ( $msgid < 1 ) {

				unset( $db );
				$db = new SafeMySQL( $opts );
				$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

				//file_put_contents($rootpath."/cash/mailer-".$message['uid'].".json", json_encode_cyr($message));

				//try {

				$blacklist = self ::blackList();

				if ( !in_array( $message['fromAddr'], $blacklist ) ) {

					$msg['datum']     = $message['datum'];
					$msg['messageid'] = $message['messageid'];
					$msg['uid']       = $message['uid'];
					$msg['folder']    = $mailbox;
					$msg['state']     = 'unread';
					$msg['priority']  = '3';
					$msg['fromm']     = $message['fromAddr'];
					$msg['fromname']  = remove_emoji( $message['fromName'] );
					$msg['theme']     = remove_emoji( $message['theme'] );
					$msg['content']   = htmlspecialchars( str_replace( [
						"<p> </p>",
						"<p>&nbsp;</p>",
						"<p>&nbsp; </p>",
						"<p class=\"MsoNormal\"></p>",
						"<p></p>"
					], "", (isHTML( $message['html'] ) ? str_replace( "\n", "", remove_emoji( $message['html'] ) ) : $message['html']) ) );
					$msg['iduser']    = $iduser;
					$msg['did']       = $did;
					$msg['identity']  = $identity;

					//file_put_contents($rootpath."/cash/mailer-".$message['uid'].".json", json_encode_cyr($msg));

					$db -> query( "INSERT INTO {$sqlname}ymail_messages SET ?u", arrayNullClean( $msg ) );
					$msgid = $db -> insertId();

					$last = $msgid;

					//print $db -> lastQuery();
					//print $msgid.": ".$msg['fromm']."\n";

					$mcount++;

					//для сообщений из ящика Отправленные
					if ( $tip == 'to' ) {

						$message['fromName'] = $message['toName'];
						$message['fromAddr'] = $message['toAddr'];

					}

					//найдем и добавим отправителя по базе и добавим его
					$r    = $db -> getRow( "SELECT pid, clid FROM {$sqlname}personcat WHERE mail LIKE '%".$message['fromAddr']."%' AND identity = '$identity'" );
					$pid  = $r['pid'];
					$clid = $r['clid'];

					if ( $clid < 1 && $pid < 1 ) {

						$clid = $db -> getOne( "SELECT clid FROM {$sqlname}clientcat WHERE mail_url LIKE '%".$message['fromAddr']."%' AND identity = '$identity'" );

						if ( $clid < 1 ) {
							$pid = $db -> getOne( "SELECT pid FROM {$sqlname}personcat WHERE mail LIKE '%".$message['fromAddr']."%' AND identity = '$identity'" );
						}

					}

					if ( $message['fromName'] == '' ) {

						if ( (int)$clid > 0 ) {
							$message['fromName'] = current_client( $clid );
						}
						if ( (int)$pid > 0 ) {
							$message['fromName'] = current_person( $pid );
						}

					}

					$msgrec = [
						'mid'      => $msgid,
						'tip'      => $tip,
						'email'    => $message['fromAddr'],
						'name'     => remove_emoji( $message['fromName'] ),
						'clid'     => (int)$clid,
						'pid'      => (int)$pid,
						'identity' => $identity
					];
					$db -> query( "INSERT INTO {$sqlname}ymail_messagesrec SET ?u", $msgrec );

					//добавим файлы вложения
					$attach = $message['attach'];

					foreach ( $attach as $att ) {

						if ( $att['name'] != '' ) {

							$at = [
								'mid'      => $msgid,
								'datum'    => current_datumtime(),
								'name'     => $att['name'],
								'file'     => $att['file'],
								'identity' => $identity
							];
							$db -> query( "INSERT INTO {$sqlname}ymail_files  SET ?u", arrayNullClean( $at ) );

						}

					}

				}

				//}
				//catch ( Exception $e ) {

				//$err[] = 'Ошибка MSG: '.$e -> getMessage();

				//}

				//если есть сделка, клиент или контакт, то добавим письмо в историю
				if ( ($did > 0 && $ymailSet['ymailAddHistoryDeal']) || ($mailbox == 'inbox' && $ymailSet['ymailAddHistoryInbox']) || ($mailbox == 'sended' && $ymailSet['ymailAddHistorySended']) ) {

					//unset($db);
					//$db = new SafeMySQL($opts);

					self ::putHistory( (int)$msgid );

					/*try {

						self ::putHistory( (int)$msgid );

					}
					catch ( Exception $e ) {

						$err[] = 'Ошибка HIST: '.$e -> getMessage();

					}*/

				}

			}

		}

		$d = ($mailbox == 'sended') ? 'Отправленных' : 'Входящих';

		return [
			"result" => empty( $err ) ? "Получено $d писем <b>$mcount</b>" : "error",
			"text"   => empty( $err ) ? "Получено $d писем <b>$mcount</b>" : "Ошибка: ".implode( "<br>", $err ),
			"mcount" => $mcount,
			"last"   => $last
		];

	}

	/**
	 * Отправка сообщения
	 *
	 *      - int **$iduser** - пользователь, от которого происходит отправка
	 *      - int **$identity**
	 *      - int **$priority** - приоритет сообщения
	 *      - string **$subject** - тема сообщения
	 *      - string **$html** - текст сообщения
	 *      - string|array **$to** - массив email получателей
	 *      - string|array **$toname** - массив имен получателей
	 *      - array **$attach** - массив вложений из папки ymail
	 *          - name => file
	 *      - array **$files** - массив вложений
	 *          - name => file
	 *
	 * @return array
	 *         - messageid
	 *         - error
	 *         - imaperror
	 *
	 * @throws Exception
	 *
	 * ```php
	 * $mail = new Salesman\Mailer();
	 * try {
	 *
	 *      $mail -> id = $mid;
	 *      $result = $mail -> mailSubmit();
	 *
	 * }
	 * catch (phpmailerException $e) {
	 *
	 *      $err[] = $e ->errorMessage();
	 *
	 * }
	 * catch (Exception $e) {
	 *
	 *      $err[] = $e ->getMessage();
	 *
	 * }
	 *
	 * $rez = [
	 *      "id"        => $id,
	 *      "result"    => $result,
	 *      "error"     => $err,
	 *      "messageid" => $result['messageid']
	 * ];
	 * ```
	 */
	public function mailSubmit(): array {

		global $bdtimezone;

		$identity = $this -> identity;
		$sqlname  = $this -> sqlname;
		$db       = $this -> db;
		$fpath    = $this -> fpath;
		$ivc      = $this -> ivc;
		$skey     = $this -> skey;
		$rootpath = $this -> rootpath;
		$opts     = $this -> opts;

		// id сотрудника, для которого проверяем почту
		$iduser = $this -> iduser;
		// различные параметры в массиве
		$params = $this -> params;
		// id настроек почтового ящика ( для мультиящиков )
		$mailID = $this -> mailID;

		// детали письма
		$subject  = $this -> subject;
		$html     = $this -> html;
		$from     = $this -> from;
		$to       = $this -> to;
		$priority = $this -> priority;
		$attach   = $this -> attach;
		$files    = $this -> files;

		$id = $this -> id;

		//print_r(get_object_vars($this));

		// если параметры не пришли, то найдем их через ID письма
		if ( $id > 0 ) {

			$this -> mailInfo();
			$msg = $this -> Message;

			//print_r($msg);

			$subject  = $msg['subject'];
			$html     = str_replace( "{subject}", $subject, $msg['html'] );
			$from     = $msg['from'];
			$fromname = $msg['fromname'];
			$to       = $msg['to'];
			$priority = $msg['priority'];
			$attach   = $msg['attach'];
			$files    = $msg['files'];

		}
		else {

			$fromname = current_user( (int)$iduser );

		}

		$html = htmlspecialchars_decode( $html );

		// преобразуем значения массива параметров в переменные
		if ( !empty( $params ) ) {
			foreach ( $params as $key => $val ) {
				$$key = $val;
			}
		}

		$response = [];

		//если не указан почтовый ящик ( для реализации 1 Юзер = 1 Ящик )
		if ( $mailID == 0 ) {

			//читаем настройки
			$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.$iduser.json";

			if ( file_exists( $settingsYMail ) ) {
				$settings = json_decode( file_get_contents( $settingsYMail ), true );
			}
			else {
				$settings = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '$iduser' AND identity = '$identity'" ), true );
			}

		}
		// если указан ID конкретного ящика ( для реализации 1 Юзер = ХХХ ящиков )
		else {

			$p        = $db -> getRow( "SELECT settings, iduser FROM {$sqlname}ymail_settings WHERE id = '$mailID'" );
			$settings = json_decode( $p['settings'], true );
			$iduser   = $p['iduser'];

		}

		//копирование в отправленные делаем кроме Gmail, т.к. он сам кладет письма в отправленные, если отправлять через SMTP
		if ( str_contains( strtolower( $settings['ymailInHost'] ), 'google' ) ) {
			$copyInSent = false;
		}
		else {
			$copyInSent = true;
		}

		// раскодируем логин/пароль
		$settings['ymailUser'] = self ::decrypt( $settings['ymailUser'], $skey, $ivc );
		$settings['ymailPass'] = self ::decrypt( $settings['ymailPass'], $skey, $ivc );

		$charset = (!$settings['ymailOutCharset']) ? 'utf-8' : $settings['ymailOutCharset'];

		//закодируем все вложенные файлы в base64
		$images   = [];
		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

		//находим ссылки на изображения в массив $images
		preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', $html, $images, PREG_SET_ORDER );

		// включаем изображения в теле письма как base64
		foreach ( $images as $img ) {

			$imgSrc  = $img[1];
			$array   = yexplode( "/", (string)$img[1] );
			$imgOrig = array_pop( $array );

			if ( file_exists( $ym_fpath."inbody/".$imgOrig ) ) {

				$imgBase64 = base64_encode( file_get_contents( $ym_fpath."inbody/".$imgOrig ) );
				$src       = 'data:'.mime_content_type( $ym_fpath."inbody/".$imgOrig ).';base64,'.$imgBase64;

				$html = str_replace( $imgSrc, $src, $html );

			}

		}

		$messageid = 'salesman'.$iduser.time();
		$host      = (yexplode( "@", $settings['ymailFrom'], 1 ) != '') ? (string)yexplode( "@", (string)$settings['ymailFrom'], 1 ) : 'localhost';

		if ( empty( $host ) ) {
			$host = 'salesmancrm.local';
		}

		// присваиваем письму идентификатор
		$messageid = "<".$messageid."@".$host.">";

		//получим данные сервера smtp для подключения
		$mail = new PHPMailer();

		$mail -> IsSMTP();
		$mail -> SMTPAuth   = $settings['ymailAuth'];
		$mail -> SMTPSecure = $settings['ymailOutSecure'];
		$mail -> Host       = $settings['ymailOutHost'];
		$mail -> Port       = $settings['ymailOutPort'];
		$mail -> Username   = $settings['ymailUser'];
		$mail -> Password   = $settings['ymailPass'];
		$mail -> Priority   = $priority; //Email priority (1 = High, 3 = Normal, 5 = low).
		//$mail->SMTPDebug  = 1;

		//новый формат для MessageID, т.к. в PHPMail начали делать проверку на стандарты
		$mail -> MessageID   = $messageid;
		$mail -> SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		//print $fromname;

		$mail -> CharSet = $charset;
		$mail -> setLanguage( 'ru', $rootpath.'/vendor/phpmailer/phpmailer/language/' );
		$mail -> IsHTML();

		if ( $charset != 'utf-8' ) {
			$fromname = iconv( "utf-8", $charset, $fromname );
		}

		$mail -> SetFrom( $settings['ymailUser'], $fromname );

		//print $param['from'];

		// новая реализация
		if ( !isset( $params['toname'] ) ) {

			if ( $to[0]['name'] != '' ) {

				foreach ( $to as $t ) {

					if ( $charset != 'utf-8' ) {
						$t['name'] = iconv( "utf-8", $charset, $t['name'] );
					}

					$mail -> AddAddress( $t['email'], $t['name'] );

				}

			}
			else {

				if ( $charset != 'utf-8' ) {
					$to['name'] = iconv( "utf-8", $charset, $to['name'] );
				}

				$mail -> AddAddress( $to['email'], $to['name'] );

			}

		}

		// старая реализация ( передача email и имен отправителей в разных массивах - to и toname )
		elseif ( is_array( $params['to'] ) ) {

			for ( $i = 0, $iMax = count( (array)$params['to'] ); $i < $iMax; $i++ ) {

				if ( $charset != 'utf-8' ) {
					$params['toname'][ $i ] = iconv( "utf-8", $charset, $params['toname'][ $i ] );
				}

				$mail -> AddAddress( $params['to'][ $i ], $params['toname'][ $i ] );

			}

		}

		else {

			if ( $charset != 'utf-8' ) {
				$params['toname'] = iconv( "utf-8", $charset, $params['toname'] );
			}

			$mail -> AddAddress( $params['to'], $params['toname'] );

		}

		// вложения из папки ymail ( для не загруженных в систему файлов )
		foreach ( $attach as $file ) {

			if ( $charset != 'utf-8' ) {
				$file['name'] = iconv( "utf-8", $charset, $file['name'] );
			}

			$mail -> AddAttachment( $rootpath."/files/{$fpath}ymail/".$file['file'], $file['name'] );

		}

		// вложения из папки files
		foreach ( $files as $file ) {

			if ( $charset != 'utf-8' ) {
				$file['name'] = iconv( "utf-8", $charset, $file['name'] );
			}

			$mail -> AddAttachment( $rootpath."/files/".$fpath.$file['file'], $file['name'] );

		}

		if ( $charset != 'utf-8' ) {
			$subject = iconv( "utf-8", $charset, $subject );
			$html    = iconv( "utf-8", $charset, $html );
		}

		$mail -> Subject = $subject;
		$mail -> Body    = $html;
		$mail -> AltBody = html2text( $html );

		//print_r(get_object_vars($mail));

		if ( !$mail -> Send() ) {
			$this -> Error = 'Ошибка отправки: '.$mail -> ErrorInfo;
		}

		else {

			$mail -> preSend();
			$content = $mail -> getSentMIMEMessage();

			unset( $db );
			$db = new SafeMySQL( $opts );
			$db -> query( "SET time_zone = '".$bdtimezone.":00'" );

			//$db -> query( "UPDATE {$sqlname}ymail_messages SET folder = 'sended', state = 'read', messageid = '$messageid', datum = '".current_datumtime()."' WHERE id = '$id'" );
			$db -> query( "UPDATE {$sqlname}ymail_messages SET ?u WHERE id = '$id'", [
				"folder"    => 'sended',
				"state"     => 'read',
				"messageid" => $messageid,
				"datum"     => current_datumtime()
			] );
			//print "yml: ".$db -> lastQuery();

			if ( $settings['ymailAddHistorySended'] == 'true' ) {

				try {

					self ::putHistory( $id );

				}
				catch ( Exception $e ) {

					$this -> Error = $e -> getMessage();

				}

			}

			//$copyInSent = false;//принудительно отключим копирование в папку отправленные

			//копируем в отправленные на сервере
			if ( $copyInSent ) {

				//$mail -> preSend();
				//$content = $mail -> getSentMIMEMessage();

				if ( $settings['ymailInSecure'] == 'ssl' ) {
					$settings['ymailInSecure'] = '/'.$settings['ymailInSecure'].'/novalidate-cert';
				}

				$box = ImapUtf7 ::encode( $settings['ymailFolderSent'] );

				$mailbox = '{'.$settings['ymailInHost'].':'.$settings['ymailInPort'].'/'.$settings['ymailInProtocol'].$settings['ymailInSecure'].'}'.$box;
				$conn    = imap_open( $mailbox, $settings['ymailUser'], $settings['ymailPass'] );

				imap_append( $conn, $mailbox, $content, "\\Seen" );

				$response['imaperror'] = imap_last_error();

				imap_close( $conn );

			}

		}

		$mail -> ClearAddresses();
		$mail -> ClearAttachments();
		$mail -> IsHTML( false );

		$response['messageid'] = $messageid;

		$this -> messageid = $messageid;

		return $response;

	}

	/**
	 * Выполнение различных действий с сообщением непосредственно по IMAP, т.е. на сервере
	 *
	 * @param int    $id     - id письма
	 * @param string $action - действие: delete, seen, unseen
	 * @return string
	 */
	public static function mailAction(int $id, string $action = ''): string {

		$rootpath = dirname( __DIR__, 2 );

		global $mail_rez;

		include_once $rootpath."/inc/config.php";
		include_once $rootpath."/inc/func.php";
		require_once $rootpath."/inc/dbconnector.php";

		$skey     = $GLOBALS['skey'];
		$ivc      = $GLOBALS['ivc'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		if ( $id < 1 ) {

			$mail_rez = 'Не указано id сообщения';

		}
		else {

			$msg = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '$id' AND identity = '$identity'" );

			$param = $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '".$msg['iduser']."' AND identity = '$identity'" );
			$param = json_decode( $param, true );

			$param['ymailUser'] = self ::decrypt( $param['ymailUser'], $skey, $ivc );
			$param['ymailPass'] = self ::decrypt( $param['ymailPass'], $skey, $ivc );

			//если параметры не указаны - отключаем проверку
			if ( $param['ymailUser'] != '' || $param['ymailPass'] != '' ) {

				$uid = $msg['uid'];

				if ( $param['ymailInSecure'] != '' ) {
					$param['ymailInSecure'] = '/'.$param['ymailInSecure'].'/novalidate-cert';
				}

				$mailbox  = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$param['ymailInSecure'].'}INBOX';
				$conn     = imap_open( $mailbox, $param['ymailUser'], $param['ymailPass'] );
				$mail_rez = imap_last_error();

				if ( $mail_rez == '' ) {

					//FT_UID - опция, чтобы моно было применить uid вместо msgno
					if ( ($action == 'delete') && imap_delete( $conn, $uid, FT_UID ) ) {

						imap_expunge( $conn );
						$mail_rez = 'Сообщение удалено с сервера';

					}
					if ( $action == 'seen' ) {

						if ( !imap_setflag_full( $conn, $uid, "\\Seen", ST_UID ) ) {
							$mail_rez = imap_last_error();
						}

						else {

							$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'read' WHERE id = '$id' and identity = '$identity'" );
							$mail_rez = 'Выполнено';

						}

					}
					if ( $action == 'unseen' ) {

						if ( !imap_clearflag_full( $conn, $uid, "\\Seen", ST_UID ) ) {
							$mail_rez = imap_last_error();
						}

						else {

							$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'unread' WHERE id = '$id' and identity = '$identity'" );
							$mail_rez = 'Выполнено';

						}

					}

				}

				imap_close( $conn );
				setcookie( "ymail", "" );

			}
			else {

				$mail_rez = 'Не настроен почтовый ящик';

			}

		}

		//ext:

		return $mail_rez;

	}

	/**
	 * Выполнение различных действий с письмами как на стороне CRM, так и на сервере
	 *
	 * @param array $param
	 *      - int **iduser** - iduser для которого выполняются действия ( в т.ч. для загрузки настроек подключения к
	 *      серверу )
	 *      - str **tip** - действие ( seen, delete, trash, untrash, multitrash, emptytrash, readall, multidelete,
	 *      history )
	 *      - int **id** - id сообщения ( для seen, delete, trash, untrash, history )
	 *      - array **multi** - массив id писем ( для multitrash, emptytrash, multidelete )
	 *
	 * @return string
	 */
	public static function mailActionPlus(array $param = []) {

		$rootpath = dirname( __DIR__, 2 );

		include_once $rootpath."/inc/config.php";
		include_once $rootpath."/inc/func.php";
		require_once $rootpath."/inc/dbconnector.php";

		$identity = ($param['identity'] > 0) ? $param['identity'] : $GLOBALS['identity'];
		$iduser   = ($param['iduser1'] > 0) ? $param['iduser'] : $GLOBALS['iduser1'];
		$fpath    = $GLOBALS['fpath'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$tip          = $param['tip'];
		$params['id'] = $param['id'];//id сообщения
		$ids          = $param['multi'];
		// если задано, то сообщение будет удалено из базы
		$havy = $params['havy'];

		$params['iduser'] = ($param['iduser'] < 1) ? $iduser : $param['iduser'];

		$rez      = '';
		$err      = [];
		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

		//читаем настройки
		$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$params['iduser'].".json";

		if ( file_exists( $settingsYMail ) ) {
			$ym_param = json_decode( file_get_contents( $settingsYMail ), true );
		}
		else {
			$ym_param = json_decode( $db -> getOne( "SELECT settings FROM {$sqlname}ymail_settings WHERE iduser = '".$params['iduser']."' AND identity = '".$identity."'" ), true );
		}

		if ( $tip != 'emptytrash' && $params['id'] < 1 && count( (array)$ids ) < 1 ) {

			$rez = 'Отмена. Не выбрано писем';

		}
		else {

			$result = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '".$params['id']."' and identity = '$identity'" );
			//$params['uid'] = $result['uid'];
			$state     = $result['state'];
			$folder    = $result['folder'];
			$messageid = $result['messageid'];

			if ( $tip == 'seen' ) {

				if ( $state == 'unread' ) {
					$params['action'] = 'seen';
				}
				else {
					$params['action'] = 'unseen';
				}

				if ( $ym_param['ymailOnReadSeen'] == "true" ) {

					// отметим прочитанным
					self ::mailAction( $id, $params['action'] );
					flush();

				}

				//этот функционал не работает - проблему с запросом серверу почты
				//if ($ym_param['ymailOnReadSeen'] == "true") $rez = ym_mailAction($params);

				if ( $params['action'] == 'seen' ) {

					$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'read' WHERE id = '".$params['id']."' and identity = '$identity'" );
					$rez = 'Выполнено';

				}
				if ( $params['action'] == 'unseen' ) {

					$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'unread' WHERE id = '".$params['id']."' and identity = '$identity'" );
					$rez = 'Выполнено';

				}

			}

			//удаление сообщения в самом сообщении
			if ( $tip == 'delete' ) {

				$provIduser = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_messages WHERE id = '".$params['id']."' and identity = '$identity'" );

				if ( $provIduser == $params['iduser'] ) {

					//если это сообщение из почты или не отправлено из почтовика
					if ( $messageid != '' && $folder != 'sended' && $ym_param['ymailOnDelete'] == "true" ) {

						$params['action'] = 'delete';

						self ::mailAction( $params['id'], 'delete' );
						flush();

					}

					//получаем текст сообщения
					$result  = $db -> getOne( "SELECT content FROM {$sqlname}ymail_messages WHERE id = '".$params['id']."' and identity = '$identity'" );
					$content = htmlspecialchars_decode( $result );

					$images = [];

					//находим ссылки на изображения в массив $images
					preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', $content, $images, PREG_SET_ORDER );

					foreach ( $images as $img ) {

						$array = (array)yexplode( "/", (string)$img[1] );
						$img   = array_pop( $array );

						if ( file_exists( $ym_fpath."inbody/".$img ) ) {
							unlink( $ym_fpath."inbody/".$img );
						}

					}

					// вместо удаления записи пометим её удаленной статусом state и очисткой содержимого
					if ( !isset( $havy ) ) {
						$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'deleted', theme = '', content = '', fid = '' WHERE id = '".$params['id']."' and identity = '$identity'" );
					}
					// или удалим жёстко
					else {
						$db -> query( "DELETE FROM {$sqlname}ymail_messages WHERE id = '".$params['id']."' and identity = '$identity'" );
					}

					$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE mid = '".$params['id']."' and identity = '$identity'" );

					$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '".$params['id']."' and identity = '$identity'" );
					while ($data = $db -> fetch( $result )) {

						$db -> query( "DELETE FROM {$sqlname}ymail_files WHERE mid = '".$params['id']."' and id = '".$data['id']."' and identity = '$identity'" );

						if ( file_exists( $ym_fpath.$data['file'] ) ) {
							unlink( $ym_fpath.$data['file'] );
						}

					}

					if ( !empty( $err ) ) {
						$rez = 'Есть ошибки: '.implode( "<br>", $err );
					}

					else {
						$rez = 'Сообщение удалено';
					}

				}

			}

			//удаление в корзину в самом сообщении (и в левой части у каждого сообщения)
			if ( $tip == 'trash' ) {

				$provIduser = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_messages WHERE id = '".$params['id']."' and identity = '$identity'" );
				if ( $provIduser == $params['iduser'] ) {

					$db -> query( "update {$sqlname}ymail_messages set trash = 'yes' WHERE id = '".$params['id']."' and identity = '$identity'" );
					$rez = 'Выполнено';

				}

			}

			if ( $tip == 'untrash' ) {

				$db -> query( "update {$sqlname}ymail_messages set trash = 'no' WHERE id = '".$params['id']."' and identity = '$identity'" );
				$rez = 'Выполнено';

			}

			//Удалить выбранные сообщения в Корзину
			if ( $tip == 'multitrash' ) {

				foreach ( $ids as $id ) {

					$provIduser = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );
					if ( $provIduser == $params['iduser'] ) {

						$db -> query( "update {$sqlname}ymail_messages set trash = 'yes' WHERE id = '$id' and identity = '$identity'" );

					}

				}

				if ( !empty( $err ) ) {
					$rez = 'Выполнено с ошибками. Ошибки:<br>'.implode( ",", $err );
				}

				else {
					$rez = 'Выполнено';
				}

			}

			// Очистка корзины
			if ( $tip == 'emptytrash' ) {

				$result = $db -> query( "SELECT * FROM {$sqlname}ymail_messages WHERE trash = 'yes' and identity = '$identity'" );
				while ($data = $db -> fetch( $result )) {

					$resultt = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '".$data['id']."' and identity = '$identity'" );
					while ($dataa = $db -> fetch( $resultt )) {

						$db -> query( "DELETE FROM {$sqlname}ymail_files WHERE id = '".$dataa['id']."' and identity = '$identity'" );

						if ( !file_exists( $ym_fpath.$dataa['file'] ) ) {
							$err[] = 'Файл '.$dataa['name'].' не найден';
						}

						elseif ( !unlink( $ym_fpath.$dataa['file'] ) ) {
							$err[] = 'Файл '.$dataa['name'].' не удален';
						}

					}

					$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE mid = '".$data['id']."' and identity = '$identity'" );
					$db -> query( "DELETE FROM {$sqlname}ymail_messages WHERE id = '".$data['id']."' and identity = '$identity'" );

				}

				if ( !empty( $err ) ) {
					$rez = 'Выполнено с ошибками. Ошибки:<br>'.implode( ",", $err );
				}
				else {
					$rez = 'Выполнено';
				}

			}

			// Отметка всех писем прочитанными или выбранного
			if ( $tip == 'readall' ) {

				$s = !isset( $params['id'] ) ? "iduser = '$iduser' AND" : "id IN (".yimplode( ",", (array)$params['id'] ).") AND";

				$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'reed' WHERE state = 'unread' AND $s identity = '$identity'" );
				$rez = (int)$db -> affectedRows();

				if ( $ym_param['ymailOnReadSeen'] == "true" ) {

					foreach ( $params['id'] as $xid ) {

						self ::mailAction( $xid, 'seen' );
						flush();

					}

				}

			}

			//Удалить выбранные сообщения из CRM
			if ( $tip == 'multidelete' ) {

				foreach ( $ids as $id ) {

					$provIduser = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );

					if ( $provIduser == $params['iduser'] ) {

						$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
						while ($dataa = $db -> fetch( $result )) {

							if ( unlink( $ym_fpath.$dataa['file'] ) ) {
								$db -> query( "DELETE FROM {$sqlname}ymail_files WHERE mid = '$id' and id = '".$dataa['id']."' and identity = '$identity'" );
							}
							else {
								$err[] = 'Файл '.$dataa['name'].' не удален';
							}

						}

						$db -> query( "DELETE FROM {$sqlname}ymail_messagesrec WHERE mid = '$id' and identity = '$identity'" );

						$result = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );
						$folder = $result['folder'];
						//$uid     = $result['uid'];
						//$iduser  = $result['iduser'];
						$content = htmlspecialchars_decode( $result['content'] );//получаем текст сообщения

						$images = [];

						//находим ссылки на изображения в массив $images
						preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', $content, $images, PREG_SET_ORDER );

						foreach ( $images as $img ) {

							$array1 = (array)yexplode( "/", (string)$img[1] );
							$img    = array_pop( $array1 );

							if ( file_exists( $ym_fpath."inbody/".$img ) ) {
								unlink( $ym_fpath."inbody/".$img );
							}

						}

						//вместо удаления записи пометим её удаленной статусом state и очисткой содержимого
						$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'deleted', theme = '', content = '', fid = '' WHERE id = '$id' and identity = '$identity'" );

						//этот функционал не работает - проблему с запросом серверу почты
						if ( $folder == 'inbox' && $ym_param['ymailOnDelete'] == "true" ) {

							self ::mailAction( $id, 'delete' );

						}

					}

				}

				if ( !empty( $err ) ) {
					$rez = 'Выполнено с ошибками. Ошибки:<br>'.implode( ",", $err );
				}
				else {
					$rez = 'Выполнено';
				}

			}

			/**
			 * Добавить запись в Историю активностей
			 */
			if ( $tip == 'history' ) {

				$result = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messagesrec WHERE mid = '".$params['id']."' and identity = '$identity'" );
				$clid   = $result['clid'];
				$pid    = $result['pid'];

				if ( $pid > 0 || $clid > 0 ) {

					try {

						$rezz = self ::putHistory( (int)$params['id'] );

					}
					catch ( Exception $e ) {

						$rezz[] = $e -> getMessage();

					}

					$rez = implode( "<br>", $rezz );

				}
				else {
					$rez = 'Можно добавлять только в существующие записи Килент или Контакт';
				}

			}

		}

		return $rez;

	}

	/**
	 * Добавление письма в историю
	 *
	 * @param     $id
	 * @param int $iduser
	 * @return array
	 * @throws Exception
	 */
	public static function putHistory($id, int $iduser = 0): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$mrez = [];

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$opts     = $GLOBALS['opts'];

		unset( $db );
		$db = new SafeMySQL( $opts );

		$timezone = $db -> getOne( "SELECT timezone FROM {$sqlname}settings WHERE id = '$identity'" );
		date_default_timezone_set( $timezone );

		$tz         = new DateTimeZone( $timezone );
		$dz         = new DateTime();
		$dzz        = $tz -> getOffset( $dz );
		$bdtimezone = $dzz / 3600;
		$db -> query( "SET time_zone = '+".$bdtimezone.":00'" );

		$ym_fpath = $rootpath.'/files/'.$fpath;
		$htip     = 'Почта';

		unset( $db );
		$db = new SafeMySQL( $opts );

		$result  = $db -> getRow( "SELECT * FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );
		$theme   = $result['theme'];
		$folder  = $result['folder'];
		$content = $result['content'];
		$did     = $result['did'];
		$xfid    = yexplode( ",", (string)$result['fid'] );
		$content = html2text( removeChild( htmlspecialchars_decode( $content ), ['index' => 2] ) );
		$iduser  = ($iduser == 0) ? $result['iduser'] : $iduser;
		$hid     = $result['hid'];

		if ( $hid == 0 && $content != '' ) {

			$content = $theme."\n".$content;

			if ( $folder == 'inbox' ) {
				$htip = 'вх.Почта';
			}
			if ( $folder == 'sended' ) {
				$htip = 'исх.Почта';
			}

			$ffolder = $db -> getOne( "SELECT idcategory FROM {$sqlname}file_cat WHERE title = 'Файлы почты' and identity = '$identity'" );

			if ( $ffolder < 1 ) {

				$db -> query( "INSERT INTO {$sqlname}file_cat (idcategory,title,identity) values(null, 'Файлы почты', '$identity')" );
				$ffolder = $db -> insertId();

			}

			unset( $db );
			$db = new SafeMySQL( $opts );

			$resultm = $db -> query( "SELECT * FROM {$sqlname}ymail_messagesrec where mid = '$id' and identity = '$identity'" );
			while ($data = $db -> fetch( $resultm )) {

				//добавим файлы
				$fids   = [];
				$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
				while ($dataa = $db -> fetch( $result )) {

					if ( $dataa['name'] != '' && ($data['pid'] > 0 || $data['clid'] > 0) ) {

						$db -> query( "INSERT INTO {$sqlname}file (`fid`, `ftitle`, `fname`, `iduser`, `clid`, `pid`, `folder`, `identity`) values (null, '".$dataa['name']."', '".$dataa['file']."', '".$iduser."', '".$data['clid']."', '".$data['pid']."', '".$ffolder."','$identity')" );
						$fids[] = $db -> insertId();

						$mrezz = copyFile( $ym_fpath.'ymail/'.$dataa['file'], $ym_fpath );
						if ( $mrezz != '' ) {
							$mrez[] = $mrezz;
						}

					}

				}

				//прикрепленные файлы
				$fids  = array_unique( array_merge( $fids, $xfid ) );
				$fidss = implode( ";", $fids );

				//найдем, к какой организации относится контакт
				if ( $data['pid'] > 0 && $data['clid'] < 1 ) {

					unset( $db );
					$db = new SafeMySQL( $opts );

					$data['clid'] = getPersonData( $data['pid'], 'clid' );

				}

				if ( $data['clid'] > 0 || $data['pid'] > 0 ) {

					unset( $db );
					$db = new SafeMySQL( $opts );

					$hid = addHistorty( [
						"iduser"   => $iduser,
						"clid"     => (int)$data['clid'],
						"pid"      => (int)$data['pid'],
						"did"      => (int)$did,
						"des"      => html2text( remove_emoji($content) ),
						"tip"      => $htip,
						"fid"      => $fidss,
						"datum"    => $data['datum'],
						//current_datumtime(),
						"identity" => $identity
					] );

					$db -> query( "UPDATE {$sqlname}ymail_messages SET hid = '$hid' WHERE id = '$id' and identity = '$identity'" );
					$mrez[] = 'Добавлено в активности';

					//оптимизация хранения файлов-вложений в письмах - прикрепленные к истории файлы удаляем из папки files/ymail и прикрепляем из папки files

					//v8.31: прикрепим файлы из активности к письму, для экономии пространства
					$db -> query( "update {$sqlname}ymail_messages set did = '$did', fid = '".str_replace( ";", ",", $fidss )."' WHERE id = '$id' and identity = '$identity'" );

					//v8.31: удалим загруженные вложения из письма
					$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
					while ($data = $db -> fetch( $result )) {

						if ( file_exists( $ym_fpath.'ymail/'.$data['file'] ) ) {

							unlink( $ym_fpath.'ymail/'.$data['file'] );

							$db -> query( "DELETE FROM {$sqlname}ymail_files WHERE id = '$data[id]'" );

						}

					}

				}

			}

		}

		return $mrez;

	}

	/**
	 * Загружает вложения конкретного письма по его uid
	 *
	 * @param     $uid  - uid сообщения
	 * @param     $mid  - id сообщения
	 * @param int $file - конкретный файл или все файлы, если не указано
	 * @return array
	 *                  - int **uid** - uid письма
	 *                  - array|string **file** системное имя
	 *                  - string **name** оригинальное имя файла
	 */
	public static function getAttachmentFromEmail($uid, $mid, $file = [] | ""): array {

		$rootpath = dirname( __DIR__, 2 );

		global $error;

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$skey     = $GLOBALS['skey'];
		$ivc      = $GLOBALS['ivc'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$attach   = [];
		$isJP     = false;
		$isGmail  = false;
		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

		$isFiles = isset( $file );

		//проверяем папку для загрузки и если нет, то создаем
		createDir( $rootpath.'/files/'.$fpath.'ymail' );

		//ответственный за письмо, чтобы загрузить его настройки подключения к IMAP
		$res    = $db -> getRow( "SELECT iduser, folder FROM {$sqlname}ymail_messages WHERE uid = '$uid' and id = '$mid' and identity = '$identity'" );
		$iduser = $res["iduser"];
		$ybox   = $res["folder"];

		//читаем настройки
		$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json";
		$param         = json_decode( file_get_contents( $settingsYMail ), true );

		if ( $ybox == 'sended' ) {
			$box = ImapUtf7 ::encode( $param['ymailFolderSent'] );
		}

		else {
			$box = 'INBOX';
		}

		//читаем настройки
		//$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json";
		//$param         = json_decode( file_get_contents( $settingsYMail ), true );

		$param['ymailUser'] = self ::decrypt( $param['ymailUser'], $skey, $ivc );
		$param['ymailPass'] = self ::decrypt( $param['ymailPass'], $skey, $ivc );

		//print_r($param);

		//если параметры не указаны - отключаем проверку
		if ( $param['ymailUser'] != '' && $param['ymailPass'] != '' ) {

			//-start--проверка получения почты
			if ( $param['ymailInSecure'] != '' ) {
				$param['ymailInSecure'] = '/'.$param['ymailInSecure'].'/novalidate-cert';
			}

			if ( str_contains( strtolower( $param['ymailInHost'] ), 'google' ) || str_contains( strtolower( $param['ymailInHost'] ), 'gmail' ) ) {
				$isGmail = true;
			}

			$mailbox = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$param['ymailInSecure'].'}'.$box;
			$conn    = imap_open( $mailbox, $param['ymailUser'], $param['ymailPass'] );

			$error = imap_last_error();

			$msgno = imap_msgno( $conn, $uid );

			if ( $msgno > 0 ) {

				//читаем аттачи
				$structure = imap_fetchstructure( $conn, $msgno );
				$parts     = $structure -> parts;
				$numparts  = count( (array)$parts );

				//print_r($structure);

				if ( $numparts > 1 ) {

					$endwhile    = false;
					$stack       = [];
					$attachments = [];
					$j           = 0;

					while (!$endwhile) {

						if ( !$parts[ $j ] ) {

							if ( !empty( $stack ) ) {

								$parts = $stack[ count( $stack ) - 1 ]["p"];
								$j     = $stack[ count( $stack ) - 1 ]["i"] + 1;
								array_pop( $stack );

							}
							else {
								$endwhile = true;
							}

						}

						if ( !$endwhile ) {

							$partstring = "";

							foreach ( $stack as $s ) {
								$partstring .= ($s["i"] + 1).".";
							}

							$partstring .= ($j + 1);

							if ( strtoupper( $parts[ $j ] -> disposition ) == "ATTACHMENT" ) {

								$fnameOrig1 = $parts[ $j ] -> dparameters[0] -> value;
								$fnameOrig2 = $parts[ $j ] -> parameters[0] -> value;

								if ( str_contains( strtoupper( $fnameOrig1 ), "ISO-2022-JP" ) ) {
									$isJP = true;
								}

								if ( str_contains( strtoupper( $fnameOrig2 ), "ISO-2022-JP" ) ) {
									$isJP = true;
								}

								if ( !$isJP ) {

									$fnameOrigg1 = imap_mime_header_decode( $fnameOrig1 );
									$fnameOrigg2 = imap_mime_header_decode( $fnameOrig2 );

									$filename1 = '';
									$filename2 = '';

									for ( $h = 0, $hMax = count( (array)$fnameOrigg1 ); $h < $hMax; $h++ ) {

										$filename1 .= $fnameOrigg1[ $h ] -> text;

									}
									for ( $h = 0, $hMax = count( (array)$fnameOrigg2 ); $h < $hMax; $h++ ) {

										$filename2 .= $fnameOrigg2[ $h ] -> text;

									}

								}
								else {

									$filename1 = $fnameOrig1;
									$filename2 = $fnameOrig2;

								}

								if ( $isJP ) {
									$filename1 = str_replace( "?", "№", self ::encodeToUtf8( $filename1 ) );
									$filename2 = str_replace( "?", "№", self ::encodeToUtf8( $filename2 ) );
								}

								//если имя файла в кодировке WINDOWS-1251
								if ( str_contains( strtoupper( $fnameOrig1 ), "WINDOWS-1251" ) ) {
									$filename1 = mb_convert_encoding( $filename1, "UTF-8", "WINDOWS-1251" );
									$filename2 = mb_convert_encoding( $filename1, "UTF-8", "WINDOWS-1251" );
								}

								//если имя файла в кодировке KOI8-R
								if ( str_contains( strtoupper( $fnameOrig1 ), "KOI8-R" ) ) {
									$filename1 = mb_convert_encoding( $filename1, "UTF-8", "KOI8-R" );
									$filename2 = mb_convert_encoding( $filename1, "UTF-8", "KOI8-R" );
								}

								if ( str_contains( strtoupper( $filename1 ), "UTF-8''" ) ) {
									$filename = $filename2;
								}
								else {
									$filename = $filename1;
								}

								if ( $filename == 'UTF-8' ) {

									$fnameOrigg = imap_mime_header_decode( $parts[ $j ] -> parameters[1] -> value );

									$filename = '';
									for ( $h = 0, $hMax = count( (array)$fnameOrigg ); $h < $hMax; $h++ ) {

										$filename .= $fnameOrigg[ $h ] -> text;

									}

								}

								if ( $isGmail && !$isJP ) {
									$filename = iconv( "KOI8-R", "UTF-8", $filename );
								}

								$filename = str_replace( [
									"'",
									"`",
									"$",
									")",
									"("
								], "", $filename );

								$fext = substr( $filename, strrpos( $filename, '.' ) + 1 );

								if ( $fext != '' ) {

									//$fname         = time() + $j;
									$attachments[] = [
										"file"     => md5( $parts[ $j ] -> bytes.$filename ).".".$fext,
										//$fname.".".$fext,
										"name"     => $filename,
										"enc"      => $parts[ $j ] -> encoding,
										"partNum"  => $partstring,
										"filedata" => imap_fetchbody( $conn, $msgno, $partstring, FT_PEEK ),
										"size"     => $parts[ $j ] -> bytes
									];

									//print_r(imap_fetchbody($conn, $msgno, $partstring, FT_PEEK));

								}

							}

						}

						//print_r($attachments);

						if ( $parts[ $j ] -> parts ) {

							$stack[] = [
								"p" => $parts,
								"i" => $j
							]; // next stack
							$parts   = $parts[ $j ] -> parts; // parts
							$j       = 0;

						}
						else {
							$j++;
						}

					}

				}
				else {
					$attachments = [];
				}

				//print_r($attachments);

				//загрузка файлов
				//$attach = [];
				foreach ( $attachments as $attachment ) {

					//если в запросе не указан конкретный файл для загрузки
					if ( !$isFiles && $file == "" ) {

						$attach[] = [
							"uid"  => $uid,
							"file" => $attachment["file"],
							"name" => $attachment["name"]
						];

						//загружаем файлы
						/*$diskUsage = getFileLimit();

						if($diskUsage['total'] == 0 || $diskUsage['percent'] < 100)*/
						self ::downloadAttachment( $conn, $uid, $attachment["partNum"], $attachment["enc"], $ym_fpath, $attachment["file"] );

					}
					elseif ( $isFiles && in_array( $attachment["name"], (array)$file ) ) {

						$attach[] = [
							"uid"  => $uid,
							"file" => $attachment["file"],
							"name" => $attachment["name"]
						];

						//загружаем файлы
						/*$diskUsage = getFileLimit();

						if($diskUsage['total'] == 0 || $diskUsage['percent'] < 100)*/
						self ::downloadAttachment( $conn, $uid, $attachment["partNum"], $attachment["enc"], $ym_fpath, $attachment["file"] );

					}
					elseif ( $file == $attachment["name"] ) {

						$attach[] = [
							"uid"  => $uid,
							"file" => $attachment["file"],
							"name" => $attachment["name"]
						];

						//загружаем файлы
						/*$diskUsage = getFileLimit();

						if($diskUsage['total'] == 0 || $diskUsage['percent'] < 100)*/
						self ::downloadAttachment( $conn, $uid, $attachment["partNum"], $attachment["enc"], $ym_fpath, $attachment["file"] );

					}

				}

			}

			//if ($error != '') print $error;

			imap_close( $conn );

		}

		return $attach;

	}

	/**
	 * Загрузка всех вложений письма
	 *
	 * @param int   $id
	 * @param array $attachments
	 *
	 * @return array
	 *      - str **rezult** = ok|error
	 *      - array **attachments**
	 *          - **size** - размер файла, кб
	 *          - **icon** - иконка формата
	 *          - **fid** - id файла, есзли загружен в систему
	 *          - **folder** - папка = ymail/, если не загружен в систему
	 *      - array **error**
	 */
	public static function getAttachments(int $id = 0, array $attachments = []): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$fids   = [];
		$ifids  = [];
		$err    = [];
		$rezult = 'error';

		if ( $id > 0 ) {

			$hid = (int)$db -> getOne( "SELECT hid FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );

			if ( $hid > 0 ) {

				$ifids = yexplode( ";", (string)$db -> getOne( "SELECT fid FROM {$sqlname}history WHERE cid = '$hid' and identity = '$identity'" ) );

				foreach ( $ifids as $fid ) {

					$fids[ $fid ] = $db -> getOne( "SELECT ftitle FROM {$sqlname}file WHERE fid = '$fid' and identity = '$identity'" );

				}

			}

			foreach ( $attachments as $key => $attach ) {

				if ( $hid == 0 ) {

					$fidd = $db -> getOne( "SELECT id FROM {$sqlname}ymail_files WHERE mid = '$id' and name = '".$attach['name']."' and identity = '$identity'" );

					//запишем файл в базу
					$db -> query( "UPDATE {$sqlname}ymail_files SET file = '".$attach['file']."' WHERE id = '$fidd'" );

					$uploaddir                     = $rootpath.'/files/'.$fpath;
					$attachments[ $key ]['size']   = num_format( filesize( $uploaddir.'ymail/'.$attach['file'] ) / 1000 );
					$attachments[ $key ]['icon']   = get_icon2( $attach['file'] );
					$attachments[ $key ]['fid']    = $fidd;
					$attachments[ $key ]['folder'] = 'ymail/';

				}
				else {

					$fidd = array_search( $attach['name'], $fids );

					$uploaddir = $rootpath.'/files/'.$fpath;

					//запишем файл в базу
					try {

						$db -> query( "UPDATE {$sqlname}file SET fname = '".$attach['file']."' WHERE fid = '$fidd'" );

						copyFile( $uploaddir.'ymail/'.$attach['file'], $uploaddir, 'yes' );
						unlink( $uploaddir.'ymail/'.$attach['file'] );

						$attachments[ $key ]['size']   = num_format( filesize( $uploaddir.$attach['file'] ) / 1000 );
						$attachments[ $key ]['icon']   = get_icon2( $attach['file'] );
						$attachments[ $key ]['folder'] = '';

					}
					catch ( Exception $e ) {

						$err[] = $e -> getMessage();

					}

					$fid = yimplode( ";", $ifids );
					$db -> query( "UPDATE {$sqlname}ymail_messages SET fid = '$fid' WHERE id = '$id'" );

				}

			}

			$rezult = 'ok';

		}
		else {
			$err[] = "Не указано ID сообщения";
		}

		return [
			"rezult"      => $rezult,
			"attachments" => $attachments,
			"error"       => $err,
		];

	}

	/**
	 * Загружает вложения конкретного письма по его uid
	 *
	 * @param        $uid  - uid сообщения
	 * @param string $file - конкретный файл или все файлы, если не указано
	 * @return array
	 *                     - **uid**
	 *                     - **file** (системное имя)
	 *                     - **name** (оригинальное имя файла)
	 */
	public static function zipAttachmentFromEmail($uid, string $file = ""): array {

		$rootpath = dirname( __DIR__, 2 );

		global $error;

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$skey     = $GLOBALS['skey'];
		$ivc      = $GLOBALS['ivc'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$attach   = [];
		$isJP     = false;
		$isGmail  = false;
		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';


		//проверяем папку для загрузки и если нет, то создаем
		createDir( $rootpath.'/files/'.$fpath.'ymail' );

		//ответственный за письмо, чтобы загрузить его настройки подключения к IMAP
		$iduser = $db -> getOne( "SELECT iduser FROM {$sqlname}ymail_messages WHERE uid = '$uid' and identity = '$identity'" );

		//читаем настройки
		$settingsYMail = $rootpath."/cash/".$fpath."settings.ymail.".$iduser.".json";
		$param         = json_decode( file_get_contents( $settingsYMail ), true );

		$param['ymailUser'] = self ::decrypt( $param['ymailUser'], $skey, $ivc );
		$param['ymailPass'] = self ::decrypt( $param['ymailPass'], $skey, $ivc );

		//если параметры не указаны - отключаем проверку
		if ( $param['ymailUser'] != '' || $param['ymailPass'] != '' ) {

			//-start--проверка получения почты
			if ( $param['ymailInSecure'] != '' ) {
				$param['ymailInSecure'] = '/'.$param['ymailInSecure'].'/novalidate-cert';
			}

			if ( str_contains( strtolower( $param['ymailInHost'] ), 'google' ) || str_contains( strtolower( $param['ymailInHost'] ), 'gmail' ) ) {
				$isGmail = true;
			}

			$mailbox = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$param['ymailInSecure'].'}INBOX';
			$conn    = imap_open( $mailbox, $param['ymailUser'], $param['ymailPass'] );

			$error = imap_last_error();

			$msgno = imap_msgno( $conn, $uid );

			if ( $msgno > 0 ) {

				//читаем аттачи
				$structure = imap_fetchstructure( $conn, $msgno );
				$parts     = $structure -> parts;
				$numparts  = count( (array)$parts );
				$header    = imap_headerinfo( $conn, $msgno );

				$fromInfo = $header -> from[0];

				$details = [
					"fromAddr" => (isset( $fromInfo -> mailbox, $fromInfo -> host )) ? $fromInfo -> mailbox."@".$fromInfo -> host : ""
				];

				if ( $numparts > 1 ) {

					$endwhile    = false;
					$stack       = [];
					$attachments = [];
					$j           = 0;

					while (!$endwhile) {

						if ( !$parts[ $j ] ) {
							if ( !empty( $stack ) ) {
								$parts = $stack[ count( $stack ) - 1 ]["p"];
								$j     = $stack[ count( $stack ) - 1 ]["i"] + 1;
								array_pop( $stack );
							}
							else {
								$endwhile = true;
							}
						}

						if ( !$endwhile ) {

							$partstring = "";

							foreach ( $stack as $s ) {
								$partstring .= ($s["i"] + 1).".";
							}

							$partstring .= ($j + 1);

							if ( strtoupper( $parts[ $j ] -> disposition ) == "ATTACHMENT" ) {

								$fnameOrig1 = $parts[ $j ] -> dparameters[0] -> value;
								$fnameOrig2 = $parts[ $j ] -> parameters[0] -> value;

								if ( str_contains( strtoupper( $fnameOrig1 ), "ISO-2022-JP" ) ) {
									$isJP = true;
								}

								if ( !$isJP ) {

									$fnameOrigg1 = imap_mime_header_decode( $fnameOrig1 );
									$fnameOrigg2 = imap_mime_header_decode( $fnameOrig2 );

									$filename1 = '';
									$filename2 = '';

									for ( $h = 0, $hMax = count( (array)$fnameOrigg1 ); $h < $hMax; $h++ ) {
										$filename1 .= $fnameOrigg1[ $h ] -> text;
									}

									for ( $h = 0, $hMax = count( $fnameOrigg2 ); $h < $hMax; $h++ ) {
										$filename2 .= $fnameOrigg2[ $h ] -> text;
									}

								}
								else {

									$filename1 = $fnameOrig1;
									$filename2 = $fnameOrig2;

								}

								if ( $isJP ) {

									$filename1 = str_replace( "?", "№", self ::encodeToUtf8( $filename1 ) );
									$filename2 = str_replace( "?", "№", self ::encodeToUtf8( $filename2 ) );

								}

								if ( str_contains( strtoupper( $filename1 ), "UTF-8''" ) ) {
									$filename = $filename2;
								}

								else {
									$filename = $filename1;
								}

								if ( $filename == 'UTF-8' ) {

									$fnameOrigg = imap_mime_header_decode( $parts[ $j ] -> parameters[1] -> value );

									$filename = '';
									for ( $h = 0, $hMax = count( (array)$fnameOrigg ); $h < $hMax; $h++ ) {

										$filename .= $fnameOrigg[ $h ] -> text;

									}

								}

								if ( $isGmail && !$isJP ) {
									$filename = iconv( "KOI8-R", "UTF-8", $filename );
								}

								$filename = str_replace( [
									"'",
									"`",
									"$",
									")",
									"("
								], "", $filename );

								$fext = substr( $filename, strrpos( $filename, '.' ) + 1 );

								if ( $fext != '' ) {

									$fsize         = $parts[ $j ] -> bytes;
									$attachments[] = [
										"file"     => md5( $fsize.$filename.$details['fromAddr'] ).".".$fext,
										"name"     => $filename,
										"enc"      => $parts[ $j ] -> encoding,
										"partNum"  => $partstring,
										"filedata" => imap_fetchbody( $conn, $msgno, $partstring, FT_PEEK )
										// data, duhh!
									];

								}

							}
						}

						//print_r($attachments);

						if ( $parts[ $j ] -> parts ) {
							$stack[] = [
								"p" => $parts,
								"i" => $j
							]; // next stack
							$parts   = $parts[ $j ] -> parts; // parts
							$j       = 0;
						}
						else {
							$j++;
						}
					}
				}
				else {
					$attachments = [];
				}

				//загрузка файлов
				//$attach = [];
				foreach ( $attachments as $attachment ) {

					//если в запросе не указан конвретный файл для загрузки
					if ( $file == "" ) {

						$attach[] = [
							"uid"  => $uid,
							"file" => $attachment["file"],
							"name" => $attachment["name"]
						];

						self ::downloadAttachment( $conn, $uid, $attachment["partNum"], $attachment["enc"], $ym_fpath, $attachment["file"] );

					}
					elseif ( $file == $attachment["name"] ) {

						$attach[] = [
							"uid"  => $uid,
							"file" => $attachment["file"],
							"name" => $attachment["name"]
						];

						self ::downloadAttachment( $conn, $uid, $attachment["partNum"], $attachment["enc"], $ym_fpath, $attachment["file"] );

					}

				}

			}

			//if ($error != '') print $error;

			imap_close( $conn );

		}

		return $attach;

	}

	/**
	 * Архивация всех вложений письма и выдача в браузер для скачивания
	 *
	 * @param $id
	 */
	public static function zipAttachment($id): void {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		//$id = $_REQUEST['mid'];

		$uploaddir = $rootpath.'/files/'.$fpath;

		$path = $uploaddir."tmp".time()."/";

		if ( !is_dir( $path ) && !mkdir( $path, 0766 ) && !is_dir( $path ) ) {
			throw new RuntimeException( sprintf( 'Directory "%s" was not created', $path ) );
		}
		chmod( $path, 0777 );

		$hid = $db -> getOne( "SELECT hid FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" );

		if ( $hid > 0 ) {

			$fids = yexplode( ";", (string)$db -> getOne( "SELECT fid FROM {$sqlname}history WHERE cid = '$hid' and identity = '$identity'" ) );

			foreach ( $fids as $fid ) {

				$result = $db -> getRow( "SELECT * FROM {$sqlname}file WHERE fid = '$fid' and identity = '$identity'" );
				$file   = $result['fname'];
				$name   = str_replace( " ", "-", translit( $result['ftitle'] ) );

				if ( file_exists( $uploaddir.$file ) ) {
					copy( $uploaddir.$file, $path.$name );
				}

			}

		}
		else {

			$uploaddir .= 'ymail/';

			$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
			while ($data = $db -> fetch( $result )) {

				if ( file_exists( $uploaddir.$data['file'] ) ) {
					copy( $uploaddir.$data['file'], $path.str_replace( " ", "-", translit( $data['name'] ) ) );
				}

			}

		}

		$file = 'attach'.time().'.zip';

		/*
		$zip = new zip_file($file);
		$zip -> set_options([
			'basedir'    => $rootpath."/files/",
			'inmemory'   => 1,
			'level'      => 9,
			'storepaths' => 0
		]);
		$zip -> add_files($path);
		$zip -> create_archive();
		$zip -> download_file();
		*/

		$zip = new ZipFolder();
		$zip -> zipFile( $file, $rootpath."/files/", $path );

		header( 'Content-Type: '.get_mimetype( $file ) );
		header( 'Content-Disposition: attachment; filename="'.$file.'"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Accept-Ranges: bytes' );

		readfile( $rootpath."/files/".$file );

		//удалим не нужные файлы

		if ( $dh = opendir( $path ) ) {

			while (($file = readdir( $dh )) !== false) {

				if ( $file != "." && $file != ".." ) {
					unlink( $path.$file );
				}

			}

		}

		rmdir( $path );

	}

	/**
	 * Удаляет все письма с вложениями ранее $day дней, которые не занесены в историю активностей
	 *
	 * @param int $iduser
	 * @param int $day
	 * @return bool
	 */
	public static function clearOtherMessages(int $iduser = 0, int $day = 10): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$opts     = $GLOBALS['opts'];

		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

		//удалим к ебеням письма, не прикрепленные к истории (входящие или в корзине), а значит не нужные

		if ( $day > 0 ) {

			/**
			 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
			 * В противном случае получим ошибку "safemysql MySQL server has gone away"
			 */
			unset( $db );
			$db = new SafeMySQL( $opts );

			$result = $db -> query( "SELECT id, datum FROM {$sqlname}ymail_messages WHERE id > 0 and iduser = '$iduser' and (folder = 'inbox' or trash = 'yes') and DATE_FORMAT(datum, '%Y-%m-%d') < '".current_datum( $day )."' and (hid < 1 OR hid IS NULL) and identity = '$identity' ORDER BY datum DESC LIMIT 20" );
			while ($da = $db -> fetch( $result )) {

				//удалим файлы inbody
				/*
				preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', htmlspecialchars_decode( $da['content'] ), $images, PREG_SET_ORDER );
				foreach ( $images as $img ) {

					$array = yexplode( "/", $img[1] );
					$image = array_pop( $array );

					if ( file_exists( $ym_fpath."inbody/".$image ) )
						unlink( $ym_fpath."inbody/".$image );

				}
				*/

				//$db -> query("delete from " . $sqlname . "ymail_messages WHERE id = '" . $da['id'] . "' and identity = '$identity'");

				//пометим письмо как удаленное
				$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'deleted', theme = '', content = '', fid = '' WHERE id = '$da[id]' and identity = '$identity'" );

				//удалим адресатов
				$db -> query( "delete from {$sqlname}ymail_messagesrec WHERE mid = '$da[id]' and identity = '$identity'" );

				$res = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$da[id]' and identity = '$identity'" );
				while ($data = $db -> fetch( $res )) {

					//удалим файлы
					$db -> query( "delete from {$sqlname}ymail_files WHERE mid = '$da[id]' and id = '$data[id]' and identity = '$identity'" );

					if ( file_exists( $ym_fpath.$data['file'] ) ) {
						unlink( $ym_fpath.$data['file'] );
					}

				}

			}

			/**
			 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
			 * В противном случае получим ошибку "safemysql MySQL server has gone away"
			 */
			unset( $db );
			$db = new SafeMySQL( $opts );

			//удалим вложения из писем, прикрепленных к истории
			$result = $db -> query( "SELECT id, hid FROM {$sqlname}ymail_messages where id > 0 and iduser = '$iduser' and folder != 'draft' and (hid != '0' and hid IS NOT NULL) and identity = '$identity' ORDER BY datum DESC LIMIT 20" );
			while ($da = $db -> fetch( $result )) {

				//v8.31: найдем файлы, прикрепленные к истории
				$result = $db -> getOne( "select fid from {$sqlname}history WHERE cid = '".$da['hid']."' and identity = '$identity'" );
				$fids   = yexplode( ";", (string)$result );
				$fidx   = yexplode( ",", (string)$da['fid'] );

				$xfid = implode( ",", array_unique( array_merge( $fids, $fidx ) ) );

				//v8.31: прикрепим файлы из активности к письму, для экономии пространства
				$db -> query( "update {$sqlname}ymail_messages set fid = '".str_replace( ";", ",", $xfid )."' WHERE id = '".$da['id']."' and identity = '$identity'" );

				//v8.31: удалим загруженные вложения из письма
				$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '".$da['id']."' and identity = '$identity'" );
				while ($data = $db -> fetch( $result )) {

					$db -> query( "delete from {$sqlname}ymail_files WHERE mid = '".$da['id']."' and id = '".$data['id']."' and identity = '$identity'" );

					if ( file_exists( $ym_fpath.$data['file'] ) ) {
						unlink( $ym_fpath.$data['file'] );
					}

				}

			}

			//удалим не нужные файлы
			//$myDir = "files/".$fpath."ymail/";

			// не совсем удачный алгоритм
			//$fls = getDirFiles( $myDir );
			$fls = [];

			unset( $db );
			$db = new SafeMySQL( $opts );

			// ограничим только 20 файлами
			$fcount = 0;
			foreach ( $fls as $file ) {

				if ( $file != '' && $fcount < 20 ) {

					/**
					 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
					 * В противном случае получим ошибку "safemysql MySQL server has gone away"
					 */

					$yfid = $db -> getOne( "SELECT id FROM {$sqlname}ymail_files WHERE file = '$file' and identity = '$identity'" );

					if ( (int)$yfid > 0 ) {

						unlink( $ym_fpath.$file );

					}

					$fcount++;

				}

			}

			/*
			if ($dh = opendir($myDir)) {

				while (($file = readdir($dh)) !== false) {

					if ($file != "." && $file != "..") {

						unset($db);
						$db = new SafeMySQL($opts);

						$yfid = $db -> getOne("SELECT id FROM {$sqlname}ymail_files WHERE file = '$file' and identity = '$identity'");

						if (!$yfid) {

							unlink($myDir."".$file);

						}

					}

				}

			}
			*/

		}

		return true;

	}

	/**
	 * Удаляет все письма с вложениями старше $day дней
	 *
	 * @param     $iduser
	 * @param int $day
	 * @return bool
	 */
	public static function clearOldMessages($iduser, int $day = 90): bool {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		//$db       = $GLOBALS['db'];
		$opts = $GLOBALS['opts'];

		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

		//удалим старые сообщения (более 3-х месяцев)

		$db = new SafeMySQL( $opts );

		$result = $db -> query( "SELECT id FROM {$sqlname}ymail_messages where id > 0 and iduser = '$iduser' and (folder = 'inbox' or folder = 'sended' or trash = 'yes') and DATE_FORMAT(datum, '%Y-%m-%d') < '".current_datum( $day )."' and hid > 0 and identity = '$identity'" );
		while ($da = $db -> fetch( $result )) {

			/**
			 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
			 * В противном случае получим ошибку "safemysql MySQL server has gone away"
			 */
			unset( $db2 );
			$db2 = new SafeMySQL( $opts );

			//удалим файлы inbody
			/*preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', htmlspecialchars_decode( $da['content'] ), $images, PREG_SET_ORDER );

			foreach ( $images as $img ) {

				$array = yexplode( "/", $img[1] );
				$image = array_pop( $array );

				if ( file_exists( $ym_fpath."inbody/".$image ) ) {
					unlink( $ym_fpath."inbody/".$image );
				}

			}*/

			//пометим письмо как удаленное
			$db2 -> query( "UPDATE {$sqlname}ymail_messages SET state = 'deleted', theme = '', content = '', fid = '' WHERE id = '$da[id]' and identity = '$identity'" );

			//удалим адресатов
			$db2 -> query( "delete from {$sqlname}ymail_messagesrec WHERE mid = '$da[id]' and identity = '$identity'" );

			$res = $db2 -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$da[id]' and identity = '$identity'" );
			while ($data = $db2 -> fetch( $res )) {

				//удалим файлы
				$db2 -> query( "delete from {$sqlname}ymail_files WHERE mid = '$da[id]' and id = '$data[id]' and identity = '$identity'" );

				if ( file_exists( $ym_fpath.$data['file'] ) ) {
					unlink( $ym_fpath.$data['file'] );
				}

			}

		}

		/**
		 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
		 * В противном случае получим ошибку "safemysql MySQL server has gone away"
		 */
		unset( $db );
		$db = new SafeMySQL( $opts );

		//удалим вложения из писем, прикрепленных к истории
		$result = $db -> query( "SELECT id, hid FROM {$sqlname}ymail_messages where id > 0 and iduser = '".$iduser."' and folder != 'draft' and hid > 0 and identity = '$identity'" );
		while ($da = $db -> fetch( $result )) {

			unset( $db2 );
			$db2 = new SafeMySQL( $opts );

			//v8.31: найдем файлы, прикрепленные к истории
			$result = $db2 -> getOne( "select fid from {$sqlname}history WHERE cid = '".$da['hid']."' and identity = '$identity'" );
			$fids   = yexplode( ";", $result );
			$fidx   = yexplode( ",", $da['fid'] );

			$xfid = implode( ",", array_unique( array_merge( $fids, $fidx ) ) );

			//v8.31: прикрепим файлы из активности к письму, для экономии пространства
			$db2 -> query( "update {$sqlname}ymail_messages set fid = '".str_replace( ";", ",", $xfid )."' WHERE id = '".$da['id']."' and identity = '$identity'" );

			//v8.31: удалим загруженные вложения из письма
			$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '".$da['id']."' and identity = '$identity'" );
			while ($data = $db2 -> fetch( $result )) {

				$db2 -> query( "delete from {$sqlname}ymail_files WHERE mid = '".$da['id']."' and id = '".$data['id']."' and identity = '$identity'" );

				if ( file_exists( $ym_fpath.$data['file'] ) ) {
					unlink( $ym_fpath.$data['file'] );
				}

			}

		}

		//удалим не нужные файлы
		//$myDir = "files/".$fpath."ymail/";

		$fls = []; // getDirFiles( $myDir );

		// ставим заглушку, т.к. эту функцию вынес в cronCleaner
		// да и работает как-то не правильно
		foreach ( $fls as $file ) {

			if ( $file != '' ) {

				/**
				 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
				 * В противном случае получим ошибку "safemysql MySQL server has gone away"
				 */
				unset( $db );
				$db = new SafeMySQL( $opts );

				$yfid = $db -> getOne( "SELECT id FROM {$sqlname}ymail_files WHERE file = '$file' and identity = '$identity'" );

				if ( (int)$yfid > 0 ) {

					unlink( $ym_fpath.$file );

				}

			}

		}


		/*
		if ($dh = opendir($myDir)) {

			while (($file = readdir($dh)) !== false) {

				if ($file != "." && $file != "..") {

					$yfid = $db -> query("select id from {$sqlname}ymail_files WHERE file = '$file' and identity = '".$identity."'");

					if (!$yfid) {

						unlink($myDir."".$file);

					}

				}

			}

		}
		*/

		//удалим удаленные сообщения старше месяца
		$db -> query( "DELETE FROM {$sqlname}ymail_messages WHERE state = 'deleted' AND datum < '".current_datum( 30 )."'" );

		return true;

	}

	/**
	 * Функция очистки изображений из папки inbody, которые отсутствуют в теле писем почтовика
	 *
	 * @return int
	 */
	public static function clearInBody(): int {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$fpath    = $GLOBALS['fpath'];
		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

		$count = 0;

		$imgExist = [];

		$result = $db -> getCol( "SELECT content FROM {$sqlname}ymail_messages where identity = '$identity'" );
		foreach ( $result as $html ) {

			//найдем все валидные файлы
			preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', htmlspecialchars_decode( $html ), $images, PREG_SET_ORDER );

			foreach ( $images as $img ) {

				$array = yexplode( "/", $img[1] );
				$image = array_pop( $array );

				$imgExist[] = $image;

			}

		}

		//проходим дирректорию и удаляем не валидные файлы
		clearstatcache();

		$files = scandir( $ym_fpath."inbody", 1 );

		foreach ( $files as $file ) {

			if ( !in_array( $file, [
					"0",
					".",
					".."
				], true ) && !in_array( $file, $imgExist ) ) {

				unlink( $ym_fpath."inbody/".$file );
				$count++;

			}

		}

		return $count;

	}

	/**
	 * Функция удаления писем без следов, включая вложения
	 *
	 * @param int $iduser - id сотрудника, письма которого удаляем
	 * @param int $period - количество дней, за которое удаляем сообщения ( default = 5 )
	 *
	 * @return array
	 *   **id** - идентификатор письма
	 *   **text** - сообщение с результатом
	 */
	public static function deleteMsgByPeriod(int $iduser = 0, int $period = 5): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$m = [];

		$s = ($iduser > 0) ? " AND iduser = '$iduser'" : "";

		$r = $db -> query( "SELECT id, iduser FROM {$sqlname}ymail_messages WHERE DATEDIFF(datum, NOW() - INTERVAL $period DAY) > 0 $s AND state != 'deleted' AND identity = '$identity'" );
		while ($d = $db -> fetch( $r )) {

			$y = self ::mailActionPlus( [
				"id"     => $d['id'],
				"iduser" => $d['iduser'],
				"tip"    => "delete"
			] );

			$m[] = [
				"id"   => $d['id'],
				"text" => $y
			];

		}

		return $m;

	}

	/**
	 * Изменение статуса сообщений прочитанными
	 *
	 * @param array $params
	 *      int **iduser** - ограничение по сотруднику (иначе iduser1)
	 *      int|array **id** - массив id сообщений или конкретный id
	 *
	 * @return int
	 */
	public static function readAll(array $params = []): int {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		require_once $rootpath."/inc/func.php";

		$identity = ($params['identity'] > 0) ? $params['identity'] : $GLOBALS['identity'];
		$iduser   = ($params['iduser1'] > 0) ? $params['iduser'] : $GLOBALS['iduser1'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];

		$s = !isset( $params['id'] ) ? "iduser = '$iduser' AND" : "id IN (".yimplode( ",", (array)$params['id'] ).") AND";

		$db -> query( "UPDATE {$sqlname}ymail_messages SET state = 'reed' WHERE state = 'unread' AND $s identity = '$identity'" );

		return (int)$db -> affectedRows();

	}

	/**
	 * Список e-mail(-ов), с которых запрещено принимать почту
	 *
	 * @return array $blacklist - возвращает список запрещенных e-mail для данного пользователя
	 */
	public static function blackList(): array {

		$rootpath = dirname( __DIR__, 2 );

		require_once $rootpath."/inc/config.php";
		require_once $rootpath."/inc/dbconnector.php";
		include_once $rootpath."/inc/func.php";

		$identity = $GLOBALS['identity'];
		$sqlname  = $GLOBALS['sqlname'];
		$db       = $GLOBALS['db'];
		$opts     = $GLOBALS['opts'];

		/**
		 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
		 * В противном случае получим ошибку "safemysql MySQL server has gone away"
		 */
		unset( $db );
		$db = new \SafeMySQL( $opts );

		return $db -> getCol( "SELECT email FROM {$sqlname}ymail_blacklist WHERE identity='$identity'" );

	}

	/**
	 * Загрузка вложений
	 *
	 * @param $imap
	 * @param $uid
	 * @param $partNum
	 * @param $encoding
	 * @param $path
	 * @param $filename
	 * @return void
	 */
	private static function downloadAttachment($imap, $uid, $partNum, $encoding, $path, $filename): void {

		$rootpath = dirname( __DIR__, 2 );

		include_once $rootpath."/inc/func.php";

		//$partStruct = imap_bodystruct($imap, imap_msgno($imap, $uid), $partNum);
		//$filename = str_replace(" ", "_", translit($partStruct->dparameters[0]->value));

		$message = imap_fetchbody( $imap, $uid, $partNum, FT_UID );

		switch ($encoding) {
			case 0:
			case 1:
				$message = imap_8bit( $message );
			break;
			case 2:
				$message = imap_binary( $message );
			break;
			case 3:
			case 5:
				$message = imap_base64( $message );
			break;
			case 4:
				$message = quoted_printable_decode( $message );
			break;
		}

		if ( is_string( $message ) ) {

			$fp = fopen( $path.'/'.$filename, 'wb' );

			if ( is_resource( $fp ) ) {

				fwrite( $fp, $message );
				fclose( $fp );

			}

		}

	}

	/**
	 * Получение тела письма
	 *
	 * @param $uid
	 * @param $imap
	 * @return bool|string
	 */
	private static function getBody($uid, $imap) {

		$body = self ::getPart( $imap, $uid, "TEXT/HTML" );

		if ( $body == "" ) {
			$body = self ::getPart( $imap, $uid, "TEXT/PLAIN" );
		}

		return $body;

	}

	/**
	 * Получает нужную часть письма
	 *
	 * @param      $imap
	 * @param      $uid
	 * @param      $mimetype
	 * @param      $structure
	 * @param      $partNumber
	 * @return bool|string
	 */
	private static function getPart($imap, $uid, $mimetype, $structure = NULL, $partNumber = NULL) {

		if ( !$structure ) {
			$structure = imap_fetchstructure( $imap, $uid, FT_UID );
		}

		if ( $structure ) {

			if ( $mimetype == self ::getMimeType( $structure ) ) {

				if ( !$partNumber ) {
					$partNumber = 1;
				}

				$text = imap_fetchbody( $imap, $uid, $partNumber, FT_UID );

				switch ($structure -> encoding) {
					case 3:
						return imap_base64( $text );
					case 4:
						return imap_qprint( $text );
					default:
						return $text;
				}

			}

			// multipart
			if ( $structure -> type == 1 ) {

				foreach ( $structure -> parts as $index => $subStruct ) {

					$rootpath = "";
					if ( $partNumber ) {
						$rootpath = $partNumber.".";
					}

					$data = self ::getPart( $imap, $uid, $mimetype, $subStruct, $rootpath.($index + 1) );
					if ( $data ) {
						return $data;
					}

				}

			}

		}

		return false;

	}

	/**
	 * Получение MimeType по структуре письма
	 *
	 * @param $structure
	 * @return string
	 */
	private static function getMimeType($structure): string {

		$primaryMimetype = [
			"TEXT",
			"MULTIPART",
			"MESSAGE",
			"APPLICATION",
			"AUDIO",
			"IMAGE",
			"VIDEO",
			"OTHER"
		];

		if ( $structure -> subtype ) {
			return $primaryMimetype[ (int)$structure -> type ]."/".$structure -> subtype;
		}

		return "TEXT/PLAIN";

	}

	/**
	 * Преобразование объекта в массив, с учетом подмассивов, которые тоже могут быть объектами
	 *
	 * @param $object
	 * @return mixed
	 */
	private static function objectToArray($object) {
		// First we convert the object into a json string
		$json = json_encode( $object );

		// Then we convert the json string to an array
		return json_decode( $json, true );

	}

	/**
	 * Поиск кодировки в массиве заголовков
	 *
	 * @param $array
	 * @return mixed
	 */
	public static function searchEncode($array) {

		//global $charset;
		$charset = '';

		foreach ( $array as $arr ) {

			if(!empty($charset)){
				continue;
			}

			if ( is_array( $arr ) && !isset( $arr['disposition'] ) ) {

				if ( strtoupper( $arr[0]['attribute'] ) == 'CHARSET' ) {
					$charset = $arr[0]['value'];
					continue;
				}

				$charset = self ::searchEncode( $arr );

			}

		}

		return $charset;

	}

	/**
	 * Преобразование в кодировку UTF-8
	 *
	 * @param $string
	 * @return false|string
	 */
	private static function encodeToUtf8($string) {

		mb_internal_encoding( "utf-8" );
		$str_japan = mb_convert_encoding( $string, "UTF-8", "ISO-2022-JP" );

		//"=?iso-2022-jp?B?GyRCQjZCPjhsOEBCLDtuGyhCIC0gdGVzdGluZw==?=";
		return mb_decode_mimeheader( $str_japan );

	}

	/**
	 * Расшифровка логина/пароля
	 *
	 * @param $text
	 * @param $key
	 * @param $iv
	 * @return string
	 */
	private static function decrypt($text, $key, $iv): string {

		return openssl_decrypt( base64_decode( $text ), 'AES-256-CTR', $key, OPENSSL_RAW_DATA, base64_decode( $iv ) );

	}

	/**
	 * Поиск вложенных изображений и конвертация в base64
	 * Применяется в создании подписи
	 *
	 * @param      $html
	 * @param bool $resize
	 * @return mixed
	 * @throws ImageResizeException
	 */
	public static function image2base64($html, bool $resize = false) {

		preg_match_all( '/<img[^>]+src="?\'?([^"\']+)"?\'?[^>]*>/i', $html, $images, PREG_SET_ORDER );

		/**
		 * Нужно проверить поддержки JPEG, т.к. в PHP7.4 её по дефолту нет
		 */
		$gd = gd_info();

		foreach ( $images as $img ) {

			// уменьшаем изображение только если есть поддержка работы с JPEG
			if ( $resize && file_exists( $img[1] ) && $gd['JPEG Support'] ) {

				$image = new ImageResize( $img[1] );
				$image -> resizeToWidth( 300 );
				$image -> save( $img[1], IMAGETYPE_PNG );

			}

			if ( file_exists( $img[1] ) ) {

				$imgBase64 = base64_encode( file_get_contents( $img[1] ) );
				$src       = 'data:'.mime_content_type( $img[1] ).';base64,'.$imgBase64;

				$html = str_replace( $img[1], $src, $html );

			}

		}

		return $html;

	}

	/**
	 * Очищает входящий HTML от говна, которое добавляют почтовые сервисы - стили, js и пр.
	 * Также производится проверка на наличие
	 *
	 * @param $html
	 * @return string
	 */
	public static function clearHtml($html): string {

		$content = $html;

		if ( preg_match( '|<body.*?>(.*)</body>|si', $content, $arr ) ) {
			$content = $arr[1];
		}

		$content = str_replace( [
			'<base',
			'base>',
			'underline'
		], [
			'<span',
			'span>',
			'none'
		], $content );

		// старый метод
		/*$content = preg_replace( '|<style.*?>(.*)</style>|si', "", $content );*/

		// новый метод. работает качественнее
		$content = preg_replace( '|<body.*</body>|isU', "", $content );
		$content = preg_replace( '|<style.*</style>|isU', "", $content );
		$content = preg_replace( '|<title.*</title>|isU', "", $content );
		$content = preg_replace( '~style="[^"]*"~i', "", $content );

		//если текст не имеет форматирования, то заменяем \n на br
		$k = 0;
		if ( strpos( $content, "<p" ) !== false ) {
			$k++;
		}
		if ( strpos( $content, "<div" ) !== false ) {
			$k++;
		}
		if ( strpos( $content, "<span" ) !== false ) {
			$k++;
		}
		if ( strpos( $content, "<table" ) !== false ) {
			$k++;
		}
		if ( strpos( $content, "class=" ) !== false ) {
			$k++;
		}
		if ( strpos( $content, "<br" ) !== false ) {
			$k++;
		}

		if ( $k == 0 ) {
			$content = nl2br( $content );
		}

		return $content;

	}

}