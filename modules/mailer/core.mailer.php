<?php
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Imap\ImapUtf7;
use PHPMailer\PHPMailer\PHPMailer;
use Salesman\Mailer;
use Salesman\ZipFolder;

set_time_limit( 0 );
error_reporting( E_ERROR );

//error_reporting( E_ALL );
//ini_set('display_errors', 1);

header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];

$ym_fpath = $rootpath.'/files/'.$fpath.'ymail/';

//проверяем папку для загрузки и если нет, то создаем
createDir( $rootpath.'/files/'.$fpath.'ymail' );

//проверяем папку для загрузки и если нет, то создаем
createDir( $rootpath.'/files/'.$fpath.'ymail/inbody' );

$ymailSet = $db -> getOne( "select settings from {$sqlname}ymail_settings WHERE iduser = '$iduser1' and identity = '$identity'" );
$ymailSet = json_decode( $ymailSet, true );

$ym_param = json_decode( file_get_contents( $rootpath."/cash/signature.ymail.$iduser1.json" ), true );


/**
 * Подготовка массива для формы составления письма
 */
if ( $action == 'compose.pre' ) {

	$id        = (int)$_REQUEST['id'];
	$way       = $_REQUEST['way'];
	$priority  = $_REQUEST['priority'];
	$parentmid = 0;

	$msg = [];

	// составляем на основе существующего письма ( из черновика, ответ, пересылка )
	if ( $id > 0 ) {

		$mail       = new Mailer();
		$mail -> id = $id;
		$mail -> mailInfo();

		$msg = $mail -> Message;

		//исходящее новое
		if ( $way == '' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['newSignature'] ) );

		}
		//исходящее новое
		elseif ( $way == 'new' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['newSignature'] ) );

		}
		//ответ на сообщение
		elseif ( $way == 're' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['reSignature'] ) );

			$theme = "Re: ".str_replace( [
					"Re:",
					"Fwd:"
				], "", $theme );

			$parentmid = $id;

			$res    = $db -> getRow( "SELECT clid, pid FROM {$sqlname}ymail_messagesrec WHERE mid = '$id' and identity = '$identity'" );
			$clid   = $res['clid'];
			$pid    = $res['pid'];
			$too    = $msg['from'];
			$toname = $msg['fromname'];

			$append = '<div style="font-family: monospace; font-size:12px">';
			$append .= 'Дата: '.$datum.'<br>';
			$append .= $msg['fromname'].' пишет: <br>';
			$append .= '</div><br>';

			$content = removeChild( getHtmlBody( $content ), ['index' => 0] );

			$content = "<br>".$Signature.'<br><br><hr>'.$append.'<blockquote style="margin-left: 5px;padding-left:10px;border-left: 2px solid blue;">'.$content.'</blockquote>';

			$msg['subject']   = $theme;
			$msg['content']   = $content;
			$msg['parentmid'] = $parentmid;


		}
		//пересылка сообщения
		elseif ( $way == 'fwd' ) {

			$Signature = link_it( htmlspecialchars_decode( $ym_param['fwSignature'] ) );

			$did = '';

			$append = '<div style="font-family: monospace; font-size:12px">';
			$append .= '------ Перенаправленное сообщение --------<br>';
			$append .= 'От: '.$msg['fromname'].' <'.$msg['from'].'><br>';
			$append .= 'Дата: '.$datum.'<br>';
			$append .= 'Тема: '.$theme.'<br>';
			$append .= 'Кому: '.current_user( $iduser1 ).' <'.$ymailSet['ymailFrom'].'><br>';
			$append .= '</div><br>';

			$content = getHtmlBody( $content );

			$theme   = "Fwd: ".str_replace( [
					"Re",
					"Fwd"
				], "", $theme );
			$content = "<br>".$Signature.'<br><br><hr>'.$append.'<blockquote style="margin-left: 5px;padding-left:10px;border-left: 2px solid blue;">'.$content.'</blockquote>';

			$msg['subject'] = $theme;
			$msg['content'] = $content;
			$msg['id']      = 0;

		}

	}

	// составляем абсолютно новое сообщение
	else {

		// эти параметры могут прийти из карточки
		$pid   = $_REQUEST['pid'];
		$clid  = $_REQUEST['clid'];
		$email = $_REQUEST['email'];

		$to = [];

		if ( $email == '' ) {

			if ( $pid > 0 || $clid > 0 ) {

				if ( $pid > 0 ) {

					$res = $db -> getOne( "SELECT mail FROM {$sqlname}personcat WHERE pid = '$pid' and identity = '$identity'" );
					$too = yexplode( ",", str_replace( ";", ",", $res ), 0 );

					$toname = current_person( $pid );

					$to[] = [
						"email" => $too,
						"name"  => current_person( $pid ),
						"pid"   => $pid
					];

				}
				if ( $too == '' && $clid > 0 ) {

					$res = $db -> getOne( "SELECT mail_url FROM {$sqlname}clientcat WHERE clid = '$clid' and identity = '$identity'" );
					$too = yexplode( ",", str_replace( ";", ",", $res ), 0 );

					$toname = current_client( $clid );

					$to[] = [
						"email" => $too,
						"name"  => current_client( $clid ),
						"clid"  => $clid
					];

				}

			}

		}
		else {

			$pid = $db -> getOne( "SELECT pid FROM {$sqlname}personcat WHERE mail LIKE '%$email%' and identity = '$identity'" );

			if ( $clid > 0 ) {
				$toname = current_client( $clid );
			}

			if ( $pid > 0 ) {
				$toname = current_person( $pid );
			}

			$to[] = [
				"email" => $email,
				"name"  => $toname,
				"clid"  => $clid,
				"pid"   => $pid
			];

		}

		$msg['content'] = link_it( htmlspecialchars_decode( $ym_param['newSignature'] ) );

	}

	$msg['priority'] = $priority ?? 3;

	print json_encode_cyr( $msg );

}

/**
 * Отправка письма из формы
 */
if ( $action == 'compose.on' ) {

	//to
	$email = $_REQUEST['email'];
	$clid  = $_REQUEST['clid'];
	$pid   = $_REQUEST['pid'];
	$name  = $_REQUEST['name'];

	//copy
	$cemail = $_REQUEST['ccemail'];
	$cclid  = $_REQUEST['cclid'];
	$cpid   = $_REQUEST['cpid'];
	$cname  = $_REQUEST['cname'];

	$isDraft = $_REQUEST['isDraft'];

	$to = $copy = [];

	//сформируем массив адресатов
	foreach ( $email as $key => $value ) {

		//to
		$to[] = [
			"email" => $value,
			"name"  => $name[ $key ],
			"clid"  => $clid[ $key ],
			"pid"   => $pid[ $key ]
		];

	}
	foreach ( $cemail as $key => $value ) {

		//copy
		$copy[] = [
			"email" => $value,
			"name"  => $cname[ $key ],
			"clid"  => $cclid[ $key ],
			"pid"   => $cpid[ $key ]
		];

	}

	$mail            = new Mailer();
	$mail -> id      = (int)$_REQUEST['id'];
	$mail -> subject = $_REQUEST['theme'];
	$mail -> html    = $_REQUEST['content'];
	$mail -> params  = $_REQUEST;
	$mail -> to      = $to;
	$mail -> copy    = $copy;
	$mail -> mailEdit();

	$id      = $mail -> id;
	$uploads = $mail -> attach;
	$err     = $mail -> Error;

	//print_r(get_object_vars($mail));
	//print_r($err);

	//отправляем сообщение
	if ( $isDraft == 'no' ) {

		//$mail -> mailInfo();
		//$msg = $mail -> Message;
		//$error = $mail -> Error;

		try {

			unset( $mail );

			$mail       = new Mailer();
			$mail -> id = $id;
			$result     = $mail -> mailSubmit();

		}
		catch ( Exception $e ) {

			$err[] = $e -> getMessage();

		}

	}

	$error = (!empty( $err )) ? yimplode( "<br>", $err ) : "";

	$rez = [
		"id"        => $id,
		"result"    => (!empty( $err )) ? yimplode( "<br>", $err ) : "Готово",
		"error"     => $error,
		"messageid" => $mail -> messageid,
		"files"     => $uploads,
		"sendres"   => $result
	];

	print json_encode_cyr( $rez );

	exit();

}

/**
 * Получение почты
 */
if ( $action == 'getmessage' ) {

	$box = strtoupper( $_REQUEST['box'] );

	$err = [];

	$mail           = new Mailer();
	$mail -> iduser = $iduser1;
	$mail -> box    = $box;
	$mail -> mailGet();

	$messages = $mail -> Messages;
	$error    = $mail -> Error;

	if ( !empty( $messages ) ) {

		//обрабатываем сообщения
		$mail -> box      = $box;
		$mail -> Messages = $messages;
		$mail -> iduser   = $iduser1;
		$rez              = $mail -> mailGetWorker();

	}

	$db = new SafeMySQL( $opts );

	//v.8.35 Удаляем старые сообщения
	$cday = ($ymailSet['ymailClearDay'] != '') ? (int)$ymailSet['ymailClearDay'] : 10;

	Mailer ::clearOtherMessages( (int)$iduser1, $cday );
	//Mailer ::clearOldMessages( $iduser1 );

	$r = [
		"result" => $rez['text'],
		"error"  => $rez['text'] == 'error' ? $rez['text'] : null,
		"count"  => (int)$rez['mcount'],
		"lastid" => (int)$rez['last']
	];

	print json_encode_cyr( $r );

	exit();

}

/**
 * Повторная загрузка сообщения по его ID
 */
if ( $action == 'getmessageOne' ) {

	$err = [];

	/**
	 * Получаем информацию о сообщении
	 */
	$mail       = new Mailer();
	$mail -> id = (int)$_REQUEST['id'];
	$mail -> mailInfo();
	$emptyMessage = $mail -> Message;
	$box          = $emptyMessage['folder'] == 'inbox' ? "INBOX" : "SEND";
	$uid          = $emptyMessage['uid'];
	$iduser       = $emptyMessage['iduser'];

	/**
	 * Удаляем сообщение
	 */
	Mailer ::mailActionPlus( [
		"id"   => (int)$_REQUEST['id'],
		"tip"  => "delete",
		"havy" => true,
	] );

	/**
	 * Получаем письмо заново
	 */
	$mail           = new Mailer();
	$mail -> iduser = $iduser;
	$mail -> box    = $box;
	$mail -> uids   = [$uid];
	$mail -> mailGet();

	$messages = $mail -> Messages;
	$error    = $mail -> Error;

	if ( !empty( $messages ) ) {

		//обрабатываем сообщения
		$mail -> box      = $box;
		$mail -> Messages = $messages;
		$mail -> iduser   = $iduser1;
		$rez              = $mail -> mailGetWorker();

	}

	$db = new SafeMySQL( $opts );

	$r = [
		"result" => $rez['text'],
		"error"  => $rez['text'] == 'error' ? $rez['text'] : "",
		"count"  => $rez['mcount'],
		"lastid" => $rez['last']
	];

	print json_encode_cyr( $r );

	exit();

}

/**
 * Тихая отправка пиьсма из id
 */
if ( $action == 'sendmessage' ) {

	$mail       = new Mailer();
	$mail -> id = (int)$_REQUEST['id'];

	$messageid = '';
	$err       = [];

	try {

		//$mail -> id = $mid;
		$result = $mail -> mailSubmit();

		$messageid = $mail -> messageid;

		if ( !empty( $mail -> Error ) ) {
			$err[] = empty( $mail -> Error );
		}

	}
	catch ( Exception $e ) {

		$err[] = $e -> getMessage();

	}

	//$mail -> messageid;

	$rez = [
		"id"        => $id,
		"result"    => empty( $err ) ? "Отправлено" : "Ошибка: ".yimplode( "<br>", $err ),
		"error"     => yimplode( "<br>", $err ),
		"answer"    => $result,
		"messageid" => $messageid
	];

	print json_encode_cyr( $rez );

	exit();

}

/**
 * Сохранение настроек аккаунта
 */
if ( $action == 'account.on' ) {

	$id = (int)$_REQUEST['id'];

	//var_dump($_REQUEST);

	$param['ymailInProtocol'] = $_REQUEST['ymailInProtocol'];
	$param['ymailInHost']     = $_REQUEST['ymailInHost'];
	$param['ymailInPort']     = $_REQUEST['ymailInPort'];
	$param['ymailInSecure']   = $_REQUEST['ymailInSecure'];

	$param['ymailOutProtocol'] = $_REQUEST['ymailOutProtocol'];
	$param['ymailOutHost']     = $_REQUEST['ymailOutHost'];
	$param['ymailOutPort']     = $_REQUEST['ymailOutPort'];
	$param['ymailOutSecure']   = $_REQUEST['ymailOutSecure'];
	$param['ymailOutCharset']  = $_REQUEST['ymailOutCharset'];

	$param['ymailAuth'] = $_REQUEST['ymailAuth'];
	$param['ymailFrom'] = trim( $_REQUEST['ymailFrom'] );

	$param['ymailOnReadSeen'] = $_REQUEST['ymailOnReadSeen'] == "true";
	$param['ymailOnDelete']   = $_REQUEST['ymailOnDelete'] == "true";
	$param['ymailFolderSent'] = $_REQUEST['ymailFolderSent'];

	$param['ymailAddHistoryInbox']  = $_REQUEST['ymailAddHistoryInbox'] == "true";
	$param['ymailAddHistorySended'] = $_REQUEST['ymailAddHistorySended'] == "true";
	$param['ymailAddHistoryDeal']   = $_REQUEST['ymailAddHistoryDeal'] == "true";
	$param['ymailAutoSaveTimer']    = (int)$_REQUEST['ymailAutoSaveTimer'];
	$param['ymailClearDay']         = (int)$_REQUEST['ymailClearDay'];
	$param['ymailAutoCheckTimer']   = (int)$_REQUEST['ymailAutoCheckTimer'];

	$param['ymailUser'] = rij_crypt( trim( $_REQUEST['ymailUser'] ), $skey, $ivc );
	$param['ymailPass'] = rij_crypt( trim( $_REQUEST['ymailPass'] ), $skey, $ivc );

	//print $_REQUEST['ymailFolderList'];

	if ( $_REQUEST['ymailFolderList'] != '' ) {

		//$param['ymailFolderList']   = explode(",", str_replace(array("[","]","\\","\""), "", $_REQUEST['ymailFolderList']));
		$param['ymailFolderList'] = json_decode( str_replace( "\\", "", $_REQUEST['ymailFolderList'] ), true );

	}
	else {

		//include_once "yfunc.php";

		//-start--проверка получения почты
		if ( $param['ymailInSecure'] != '' ) {
			$ymailInSecure = '/'.$param['ymailInSecure'].'/novalidate-cert';
		}
		else {
			$ymailInSecure = $param['ymailInSecure'];
		}

		$imap = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$ymailInSecure.'}';

		$mailbox = $imap.'INBOX';

		$conn  = imap_open( $mailbox, $param['ymailUser'], $param['ymailPass'] );
		$error = imap_last_error();

		//проверим список папок
		$box = imap_list( $conn, $imap, "*" );

		$folders = [];
		foreach ( $box as $folder ) {

			$param['ymailFolderList'][] = str_replace( $imap, "", ImapUtf7 ::decode( $folder ) );

		}

		imap_close( $conn );

	}

	//$param['newSignature'] = $ym_param['newSignature'];
	//$param['reSignature']  = $ym_param['reSignature'];
	//$param['fwSignature']  = $ym_param['fwSignature'];

	$settings = json_encode_cyr( $param );

	//устаревшее
	//$db -> query("ALTER TABLE {$sqlname}ymail_settings CHANGE COLUMN `lasttime` `lasttime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'дата и время последнего события' AFTER `settings`");

	if ( $id > 0 ) {
		$db -> query( "UPDATE {$sqlname}ymail_settings SET ?u WHERE iduser = '$iduser1' and identity = '$identity'", ['settings' => $settings] );
	}
	else {
		$db -> query( "INSERT INTO {$sqlname}ymail_settings SET ?u", [
			'iduser'   => $iduser1,
			'settings' => $settings,
			'identity' => $identity
		] );
	}
	print "Данные успешно сохранены";

	unlink( $rootpath."/cash/".$fpath."settings.ymail.".$iduser1.".json" );

	exit();

}

/**
 * Проверка настроек. Тестовое сообщение
 */
if ( $action == 'account.check' ) {

	$tip = $_REQUEST['tip'];

	$param['ymailInProtocol'] = trim( $_REQUEST['ymailInProtocol'] );
	$param['ymailInHost']     = trim( $_REQUEST['ymailInHost'] );
	$param['ymailInPort']     = trim( $_REQUEST['ymailInPort'] );
	$param['ymailInSecure']   = trim( $_REQUEST['ymailInSecure'] );

	$param['ymailOutProtocol'] = trim( $_REQUEST['ymailOutProtocol'] );
	$param['ymailOutHost']     = trim( $_REQUEST['ymailOutHost'] );
	$param['ymailOutPort']     = trim( $_REQUEST['ymailOutPort'] );
	$param['ymailOutSecure']   = trim( $_REQUEST['ymailOutSecure'] );

	$param['ymailAuth'] = trim( $_REQUEST['ymailAuth'] );
	$param['ymailFrom'] = trim( $_REQUEST['ymailFrom'] );

	$param['ymailUser'] = trim( $_REQUEST['ymailUser'] );
	$param['ymailPass'] = trim( $_REQUEST['ymailPass'] );

	//print_r($param);

	if ( $tip == 'in' ) {

		//-start--проверка получения почты
		if ( $param['ymailInSecure'] != '' ) {
			$param['ymailInSecure'] = '/'.$param['ymailInSecure'].'/novalidate-cert';
		}

		$imap = '{'.$param['ymailInHost'].':'.$param['ymailInPort'].'/'.$param['ymailInProtocol'].$param['ymailInSecure'].'}';

		if ( stripos( texttosmall( $param['ymailInHost'] ), 'google' ) !== false || stripos( texttosmall( $param['ymailInHost'] ), 'gmail' ) !== false ) {
			$isGmail = true;
		}

		$mailbox = $imap.'INBOX';

		$conn  = imap_open( $mailbox, $param['ymailUser'], $param['ymailPass'] );
		$error = implode( "\n", (array)imap_errors() );

		if ( !$error ) {

			//проверим список папок
			$box = imap_list( $conn, $imap, "*" );

			$folders = [];
			foreach ( $box as $folder ) {

				$folders[] = str_replace( $imap, "", ImapUtf7 ::decode( $folder ) );

			}

			imap_close( $conn );

		}

		if ( $error ) {
			$rezIncome = 'Ошибка соединения: <b class="red">'.$error.'</b>';
		}
		else {
			$rezIncome = '<b class="green">Параметры корректны.</b> Соединение установлено';
		}
		//-enf--проверка получения почты

	}
	else {

		//-start--проверка отправки почты

		//получим данные сервера smtp для подключения
		$mail = new PHPMailer();

		$host = yexplode( "@", $param['ymailFrom'], 1 );

		$mail -> IsSMTP();
		$mail -> SMTPAuth   = $param['ymailAuth'];
		$mail -> SMTPSecure = $param['ymailOutSecure'];
		$mail -> Host       = $param['ymailOutHost'];
		$mail -> Port       = $param['ymailOutPort'];
		$mail -> Username   = $param['ymailUser'];
		$mail -> Password   = $param['ymailPass'];

		//Email priority (1 = High, 3 = Normal, 5 = low).
		$mail -> Priority   = 1;
		//$mail -> SMTPDebug  = 1;

		$mail -> MessageID   = "<CRM".$iduser1.time()."@".$host.">";
		$mail -> SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		$mail -> CharSet = 'utf8';
		$mail -> setLanguage( 'ru', $rootpath.'/vendor/phpmailer/phpmailer/language/' );
		$mail -> IsHTML( false );
		$mail -> SetFrom( $param['ymailUser'], "Тест CRM" );
		$mail -> AddAddress( $param['ymailFrom'], current_user( $iduser1 ) );
		$mail -> Subject = "Проверка отправки сообщений из CRM";
		$mail -> Body    = "Это тест";

		//print_r($mail);

		if ( !$mail -> Send() ) {
			$rezOutcome = 'Ошибка соединения: <b class="red">'.$mail -> ErrorInfo.'</b>';
		}
		else {
			$rezOutcome = '<b class="green">Параметры корректны.</b> Проверочное письмо отправлено. ';
		}
		//-enf--проверка получения почты

	}

	$response = [
		"folder"  => $folders,
		"income"  => $rezIncome,
		"outcome" => $rezOutcome
	];

	print json_encode_cyr( $response );

	exit();
}

/**
 * Сохранение автоподписей
 */
if ( $action == 'signature.on' ) {

	$signatureYMail = $rootpath."/cash/".$fpath."signature.ymail.".$iduser1.".json";

	$param['newSignature'] = str_replace( "\r\n", "", htmlspecialchars( Mailer ::image2base64( $_REQUEST['newSignature'], true ) ) );
	$param['reSignature']  = str_replace( "\r\n", "", htmlspecialchars( Mailer ::image2base64( $_REQUEST['reSignature'], true ) ) );
	$param['fwSignature']  = str_replace( "\r\n", "", htmlspecialchars( Mailer ::image2base64( $_REQUEST['fwSignature'], true ) ) );

	//$file = fopen( $signatureYMail, "w" );
	//fputs( $file, json_encode_cyr( $param ) );

	//print_r($param);

	file_put_contents( $signatureYMail, json_encode_cyr( $param ) );

	print "Готово";

	exit();
}

/**
 * Расчет количества сообщений по папкам
 */
if ( $action == 'folder.count' ) {

	$inbox = $inboxUR = $sended = $draft = 0;

	$query = "
	SELECT 
		folder,
		state,
		COUNT(id) as icount 
	FROM {$sqlname}ymail_messages 
	WHERE 
		(
			(COALESCE(folder, '') = 'draft') OR 
			(COALESCE(folder, '') = 'inbox' AND COALESCE(state, '') != 'deleted') OR 
			(COALESCE(folder, '') = 'sended' AND COALESCE(state, '') != 'deleted')
		) AND
		COALESCE(state, '') != 'deleted' AND  
		iduser = '$iduser1' AND 
		identity = '1'
	GROUP BY 1, 2
	";
	$rez   = $db -> getAll( $query );

	foreach ( $rez as $folder ) {

		switch ($folder['folder']) {

			case 'inbox':

				if ( $folder['state'] == 'read' ) {
					$inbox = (int)$folder['icount'];
				}
				else {
					$inboxUR = (int)$folder['icount'];
				}

			break;
			case 'draft':

				$draft = (int)$folder['icount'];

			break;
			case 'sended':

				$sended += (int)$folder['icount'];

			break;

		}

	}

	$blacklist = $db -> getOne( "SELECT COUNT(*) as bcount FROM {$sqlname}ymail_blacklist WHERE identity = '$identity'" );

	print json_encode_cyr( [
		'inbox'       => $inbox + $inboxUR,
		'inboxUnread' => $inboxUR,
		'sended'      => $sended,
		'draft'       => $draft,
		'blacklist'   => $blacklist,
		"total"       => $inbox + $inboxUR + $sended,
		"totalUnread" => $inboxUR,
	] );

	//print '{"inbox":"'.$inbox.'","inboxUnread":"'.$inboxUR.'","sended":"'.$sended.'","draft":"'.$draft.'","blacklist":"'.$blacklist.'"}';

	exit();

}
if ( $action == 'lastmessage.count' ) {

	$inbox = 0;
	$draft = 0;

	$inbox = (int)$db -> getOne( "
		SELECT 
		    COUNT(id) as icount 
		FROM {$sqlname}ymail_messages 
		WHERE 
			folder = 'inbox' and 
			state = 'unread' and 
			iduser = '$iduser1' and 
			identity = '$identity' 
		ORDER BY datum 
		DESC limit 20
	" );

	print $inbox;

	exit();

}

/**
 * Загрузка последних 20 сообщений в левой панели
 */
if ( $action == 'lastmessage' ) {

	$html = '';

	$result = $db -> query( "
		SELECT 
			ym.id,
			ym.datum,
			ym.fromname,
			ym.fromm,
			ym.theme,
			(SELECT COUNT(*) FROM {$sqlname}ymail_files WHERE mid = ym.id and identity = '$identity') as fcount
		FROM {$sqlname}ymail_messages `ym`
		where 
			ym.folder = 'inbox' AND 
			ym.state = 'unread' AND 
			ym.iduser = '$iduser1' AND 
			ym.identity = '$identity' 
		ORDER BY ym.datum 
		DESC LIMIT 20
	" );
	while ($data = $db -> fetch( $result )) {

		$datum    = $data['datum'];
		$diff     = diffDate( $datum );
		$diffyear = get_year( date( 'Y' ) ) - get_year( $datum );

		if ( $diffyear == 0 ) {
			$date = ($diff < 1) ? "Сегодня в ".get_time( $datum ) : get_dateru( $datum )." в ".get_time( $datum );
		}

		else {
			$date = get_sfdate2( $datum )." в ".get_time( $datum );
		}

		if ( !$data['fromname'] ) {
			$data['fromname'] = $data['fromm'];
		}

		$from = '<i class="icon-user-1 gray"></i>'.$data['fromname'];

		$fcount = ($fcount > 0) ? '&nbsp;<span class="gray">\</span><i class="icon-attach-1 blue"></i>'.$fcount : '';

		$html .= '
		<div class="replay ha" style="margin-right:0">
			<div class="smalltext gray paddtop5">'.$date.$data['fcount'].'</div>
			<div onclick="$mailer.preview(\''.$data['id'].'\')" class="hand paddtop5">
				<div class="ellipsis Bold paddbott10" style="margin-left:5px">'.mb_substr( clean( $data['theme'] ), 0, 101, 'utf-8' ).'</div>
				<div class="blue paddbott5">'.$from.'</div>
			</div>
			<div class="cnopka paddbott5 text-right">
				<a href="javascript:void(0)" onclick="$mailer.compose(\''.$data['id'].'\',\'re\');"><i class="icon-reply blue" title="Ответить"></i><span class="blue">Ответить</span></a>&nbsp;
			</div>
		</div>
		';

	}

	print $html;

	exit();

}

/**
 * Получение настроек популярных почтовых сервисов
 */
if ( $action == 'get.servers' ) {

	$serv = $_REQUEST['server'];
	$tip  = $_REQUEST['tip'];

	if ( $tip == 'income' ) {

		$file = file_get_contents( $rootpath.'/cash/imap.json' );
		$fc   = json_decode( $file, true );

		foreach ( $fc as $key => $value ) {
			if ( $key == $serv ) {
				$dc = json_encode_cyr( $value );
			}
		}

	}

	if ( $tip == 'outcome' ) {

		$file = file_get_contents( $rootpath.'/cash/smtp.json' );
		$fc   = json_decode( $file, true );

		foreach ( $fc as $key => $value ) {
			if ( $key == $serv ) {
				$dc = json_encode_cyr( $value );
			}
		}

	}

	print $dc;
}

/**
 * Загрузка вложений с почтового сервера
 */
if ( $action == 'getAttachments' ) {

	$uid  = $_REQUEST['uid'];
	$mid  = (int)$_REQUEST['mid'];
	$file = $_REQUEST['file'];

	//print_r($_REQUEST);

	$fids = $ifids = [];

	if ( $mid < 1 ) {

		$mid = $db -> getOne( "SELECT id FROM {$sqlname}ymail_messages WHERE uid = '$uid' and iduser = '$iduser1' and identity = '$identity'" );

	}

	$attachments = Mailer ::getAttachmentFromEmail( $uid, $mid, $file );

	$res = Mailer ::getAttachments( (int)$mid, $attachments );

	print json_encode_cyr( $res['attachments'] );

	exit();

}

/**
 * Скачивание вложений одним архивом
 */
if ( $action == 'zipAttachments' ) {

	$id = (int)$_REQUEST['mid'];

	$uploaddir = $rootpath.'/files/'.$fpath;

	$path = $uploaddir."tmp".time()."/";

	createDir( $path );

	/*
	$hid = $db -> getOne("SELECT hid FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'");

	if ($hid > 0) {

		$fids = yexplode(";", $db -> getOne("SELECT fid FROM {$sqlname}history WHERE cid = '$hid' and identity = '$identity'"));

		foreach ($fids as $fid) {

			$result = $db -> getRow("SELECT * FROM {$sqlname}file WHERE fid = '$fid' and identity = '$identity'");
			$file   = $result['fname'];
			$name   = str_replace(" ", "-", translit($result['ftitle']));

			if (file_exists($uploaddir.$file))
				copy($uploaddir.$file, $path.$name);

		}

	}
	else {

		$uploaddir .= 'ymail/';

		$result = $db -> query("SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'");
		while ($data = $db -> fetch($result)) {

			if (file_exists($uploaddir.$data['file']))
				copy($uploaddir.$data['file'], $path.str_replace(" ", "-", translit($data['name'])));

		}

	}
	*/

	// значала скопируем загруженные файлы из системы
	$fids = yexplode( ";", $db -> getOne( "SELECT fid FROM {$sqlname}ymail_messages WHERE id = '$id' and identity = '$identity'" ) );
	foreach ( $fids as $fid ) {

		$file = $db -> getRow( "SELECT * FROM {$sqlname}file WHERE fid = '$fid' and identity = '$identity'" );

		if ( file_exists( $uploaddir.$file['fname'] ) ) {
			copy( $uploaddir.$file['fname'], $path.str_replace( " ", "-", translit( $file['ftitle'] ) ) );
		}

	}

	// теперь не загруженные
	$result = $db -> query( "SELECT * FROM {$sqlname}ymail_files WHERE mid = '$id' and identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {

		if ( file_exists( $uploaddir.'ymail/'.$data['file'] ) ) {
			copy( $uploaddir.'ymail/'.$data['file'], $path.str_replace( " ", "-", translit( $data['name'] ) ) );
		}

	}

	/*
	$zip = new zip_file( 'attach'.time().'.zip' );
	$zip -> set_options( [
		'basedir'    => $rootpath."/files/",
		'inmemory'   => 1,
		'level'      => 9,
		'storepaths' => 0
	] );
	$zip -> add_files( $path );
	$zip -> create_archive();
	$zip -> download_file();
	*/

	$zipfile = 'attach'.time().'.zip';
	$zip     = new ZipFolder();
	$zip -> zipFile( $zipfile, $rootpath."/files/", $path );

	header( 'Content-Type: '.get_mimetype( $zipfile ) );
	header( 'Content-Disposition: attachment; filename="'.$zipfile.'"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Accept-Ranges: bytes' );

	readfile( $rootpath."/files/".$zipfile );

	//удалим не нужные файлы
	if ( $dh = opendir( $path ) ) {

		while (($file = readdir( $dh )) !== false) {

			if ( $file != "." && $file != ".." ) {
				unlink( $path.$file );
			}

		}

	}

	rmdir( $path );
	unlink( $rootpath."/files/".$zipfile );

	exit();

}

/**
 * Прикрепление письма к Сделке
 */
if ( $action == 'todeal.do' ) {

	$id  = (int)$_REQUEST['id'];
	$did = (int)$_REQUEST['did'];

	//Проверим привязку к другой сделке
	$olddid = $db -> getRow( "SELECT did, hid FROM {$sqlname}ymail_messages WHERE id = '$id'" );

	$db -> query( "UPDATE {$sqlname}ymail_messages SET did = '$did' WHERE id = '$id' and identity = '$identity'" );

	//если привязки еще небыло, то добавляем запись в историю
	if ( (int)$olddid['hid'] < 1 ) {

		try {

			Mailer ::putHistory( (int)$id );

		}
		catch ( Exception $e ) {

		}

	}
	//в противном случае переносим запись в новую сделку
	else {

		$db -> query( "UPDATE {$sqlname}history SET did = '$did' WHERE cid = '$olddid[hid]' and identity = '$identity'" );

	}

	print 'Готово';

	exit();
}

/**
 * Прикрепление письма к Клиенту
 */
if ( $action == 'toclient.do' ) {

	$id      = (int)$_REQUEST['id'];
	$clid    = (int)$_REQUEST['clid'];
	$mperson = $_REQUEST['mperson'];
	$newmail = $_REQUEST['email'];

	$email = yexplode( ",", str_replace( ";", ",", getClientData( $clid, "mail_url" ) ) );

	if ( $mperson == 'yes' ) {
		array_unshift( $email, $newmail );
	}
	else {
		$email[] = $newmail;
	}


	$db -> query( "UPDATE {$sqlname}clientcat SET mail_url = '".yimplode( ", ", $email )."' WHERE clid = '$clid' and identity = '$identity'" );
	$db -> query( "UPDATE {$sqlname}ymail_messagesrec SET clid = '$clid' WHERE email = '$newmail' and identity = '$identity'" );

	// добавляем в историю все существующие письма
	$mids = $db -> getCol( "SELECT DISTINCT(mid) FROM {$sqlname}ymail_messagesrec WHERE email = '$newmail' and identity = '$identity'" );
	foreach ( $mids as $mid ) {

		try {

			Mailer ::putHistory( (int)$mid );

		}
		catch ( Exception $e ) {

			print $e -> getMessage()."<br>";

		}

	}

	/*
	try {

		Mailer ::putHistory( $id );

	}
	catch ( Exception $e ) {

	}
	*/

	print 'Сделано';

	exit();
}

/**
 * Прикрепление письма к Контакту
 */
if ( $action == 'tocontact.do' ) {

	$id      = (int)$_REQUEST['id'];
	$pid     = (int)$_REQUEST['pid'];
	$mperson = $_REQUEST['mperson'];
	$newmail = $_REQUEST['email'];

	$email = yexplode( ",", str_replace( ";", ",", getPersonData( $pid, "mail" ) ) );

	if ( $mperson == 'yes' ) {
		array_unshift( $email, $newmail );
	}
	else {
		$email[] = $newmail;
	}

	$clid = (int)getPersonData( $pid, 'clid' );

	$db -> query( "UPDATE {$sqlname}personcat SET mail = '".yimplode( ", ", $email )."' WHERE pid = '$pid' and identity = '$identity'" );
	$db -> query( "UPDATE {$sqlname}ymail_messagesrec SET ?u WHERE email = '$newmail' and identity = '$identity'", ["pid"  => $pid,
	                                                                                                                  "clid" => (int)$clid
	] );

	// добавляем в историю все существующие письма
	$mids = $db -> getCol( "SELECT DISTINCT(mid) FROM {$sqlname}ymail_messagesrec WHERE email = '$newmail' and identity = '$identity'" );
	foreach ( $mids as $mid ) {

		try {

			Mailer ::putHistory( (int)$mid );

		}
		catch ( Exception $e ) {

			print $e -> getMessage()."<br>";

		}

	}

	print 'Сделано';

	exit();
}

/**
 * Различные действия
 */
if ( $action == 'getaction' ) {

	$param['tip']   = $_REQUEST['tip'];
	$param['id']    = (int)$_REQUEST['id'];
	$param['multi'] = $_REQUEST['multi'];

	$rez = Mailer ::mailActionPlus( $param );

	print json_encode_cyr( ["result" => $rez] );

	exit();

}

/**
 * Отметить все письма пользователя прочитанными
 */
if ( $action == 'readall' ) {

	Mailer ::readAll();

	exit();

}

/**
 * Получение списка файлов, в т.ч. привязанных к Сделке, Клиенту, Контакту или общие
 */
if ( $action == 'getFiles' ) {

	$link = $_REQUEST['link'];
	$did  = (int)$_REQUEST['did'];
	$clid = (array)yexplode( ",", $_REQUEST['clids'] );
	$pid  = (array)yexplode( ",", $_REQUEST['pids'] );

	if ( count( $pid ) == 1 ) {
		$clid[] = getPersonData( $pid[0], 'clid' );
	}

	$files = [];

	$uploaddir = $rootpath.'/files/'.$fpath;

	if ( $link == "card" ) {

		$s  = [];
		$s1 = '';

		if ( $did > 0 ) {

			$s[] = "did = '$did'";

			$clid[] = getDogData( $did, 'clid' );
			$pidl   = yexplode( ";", getDogData( $did, 'pid_list' ) );

			$pid = array_unique( array_merge( $pid, $pidl ) );

		}

		if ( !empty( $clid ) && $clid[0] > 0 ) {
			$s[] = "(clid IN (".implode( ",", $clid ).") OR pid IN (SELECT pid FROM {$sqlname}personcat WHERE clid IN (".implode( ",", $clid ).") AND identity = '$identity'))";
		}

		if ( !empty( $pid ) && $pid[0] > 0 ) {
			$s[] = "pid IN (".implode( ",", $pid ).")";
		}

		if ( empty( $s ) ) {
			goto e1;
		}

		$s1 = " and (".implode( " or ", $s ).")";

		//список файлов, прикрепленных к клиенту
		$result = $db -> query( "select * from {$sqlname}file WHERE fid > 0 $s1 and fname != '' and identity = '$identity' GROUP BY ftitle ORDER BY fid DESC" );
		while ($da = $db -> fetch( $result )) {

			if ( (int)$da['did'] > 0 ) {
				$parent = '<i class="icon-briefcase-1"></i>'.current_dogovor( (int)$da['did'] );
			}
			elseif ( (int)$da['clid'] > 0 ) {
				$parent = '<i class="icon-building"></i>'.current_client( (int)$da['clid'] );
			}
			elseif ( (int)$da['pid'] > 0 ) {
				$parent = '<i class="icon-user-1"></i>'.current_person( (int)$da['pid'] );
			}

			$size  = num_format( filesize( $uploaddir.$da['fname'] ) / 1000 );
			$dtime = filemtime( $uploaddir.$da['fname'] );

			if ( filesize( $uploaddir.$da['fname'] ) > 0 ) {
				$files[] = [
					"fid"    => (int)$da['fid'],
					"icon"   => get_icon2( $da['fname'] ),
					"file"   => $da['fname'],
					"name"   => $da['ftitle'],
					"date"   => date( 'H:i d-m-Y', $dtime ),
					"parent" => $parent,
					"size"   => $size
				];
			}

		}

		e1:

	}
	if ( $link == "docs" ) {

		$s  = [];
		$s1 = '';

		if ( $did > 0 ) {

			$s[] = "did = '$did'";

			$clid[] = (int)getDogData( $did, 'clid' );
			$pidl   = yexplode( ";", getDogData( $did, 'pid_list' ) );

			$pid = array_unique( array_merge( $pid, $pidl ) );
		}

		if ( !empty( $clid ) && (int)$clid[0] > 0 ) {
			$s[] = "(clid IN (".implode( ",", $clid ).") or payer IN (".implode( ",", $clid )."))";
		}

		if ( !empty( $pid ) && (int)$pid[0] > 0 ) {
			$s[] = "pid IN (".implode( ",", $pid ).")";
		}

		if ( empty( $s ) ) {
			goto e2;
		}

		$s1 = " and (".implode( " or ", $s ).")";

		$result = $db -> query( "
			SELECT *
			FROM {$sqlname}contract
			WHERE
			    deid > 0
			    $s1 and
			    (idtype IN (SELECT id FROM {$sqlname}contract_type WHERE COALESCE(type, '') NOT IN ('get_akt','get_aktper') and identity = '$identity') or (idtype = 0)) and
			    identity = '$identity'
			ORDER BY datum DESC" );
		while ($da = $db -> fetch( $result )) {

			if ( (int)$da['did'] > 0 ) {
				$parent = '<i class="icon-briefcase-1"></i>'.current_dogovor( $da['did'] );
			}
			elseif ( (int)$da['clid'] > 0 ) {
				$parent = '<i class="icon-building"></i>'.current_client( $da['clid'] );
			}
			elseif ( (int)$da['pid'] > 0 ) {
				$parent = '<i class="icon-user-1"></i>'.current_person( $da['pid'] );
			}

			$ftitle = yexplode( ";", $da['ftitle'] );
			$fname  = yexplode( ";", $da['fname'] );
			$last   = count( $ftitle ) - 1;

			for ( $i = 0, $iMax = count( $ftitle ); $i < $iMax; $i++ ) {

				if ( $ftitle[ $i ] != '' ) {

					$size  = num_format( filesize( $uploaddir.$fname[ $i ] ) / 1000 );
					$dtime = filemtime( $uploaddir.$fname[ $i ] );

					if ( filesize( $uploaddir.$fname[ $i ] ) > 0 ) {
						$files[] = [
							"fid"    => 0,
							"icon"   => get_icon2( $fname[ $i ] ),
							"file"   => $fname[ $i ],
							"name"   => $ftitle[ $i ],
							"date"   => date( 'H:i d-m-Y', $dtime ),
							"parent" => $parent,
							"size"   => $size
						];
					}

				}

			}

		}

		e2:

	}
	if ( $link == "file" ) {

		$query = "
		SELECT
			{$sqlname}file.fid as id,
			{$sqlname}file.pid as pid,
			{$sqlname}file.clid as clid,
			{$sqlname}file.did as did,
			{$sqlname}file.ftitle as title,
			{$sqlname}file.fname as file,
			{$sqlname}file.iduser as iduser,
			{$sqlname}clientcat.title as client,
			{$sqlname}personcat.person as person,
			{$sqlname}dogovor.title as deal,
			{$sqlname}user.title as user
		FROM {$sqlname}file
			LEFT JOIN {$sqlname}user ON {$sqlname}file.iduser = {$sqlname}user.iduser
			LEFT JOIN {$sqlname}personcat ON {$sqlname}file.pid = {$sqlname}personcat.pid
			LEFT JOIN {$sqlname}clientcat ON {$sqlname}file.clid = {$sqlname}clientcat.clid
			LEFT JOIN {$sqlname}dogovor ON {$sqlname}file.did = {$sqlname}dogovor.did
		WHERE
			{$sqlname}file.fid > 0 AND
			{$sqlname}file.folder IN (SELECT idcategory FROM {$sqlname}file_cat WHERE shared = 'yes' and identity = '$identity') AND
			( COALESCE({$sqlname}file.clid, 0) = 0 AND COALESCE({$sqlname}file.pid, 0) = 0 AND COALESCE({$sqlname}file.did, 0) = 0) AND
			{$sqlname}file.identity = '$identity'
		GROUP BY {$sqlname}file.ftitle
		ORDER BY {$sqlname}file.fname";

		$result = $db -> query( $query );
		while ($da = $db -> fetch( $result )) {

			$parent = '';

			if ( (int)$da['did'] > 0 ) {
				$parent = '<i class="icon-briefcase-1"></i>'.current_dogovor( (int)$da['did'] );
			}
			elseif ( (int)$da['clid'] > 0 ) {
				$parent = '<i class="icon-building"></i>'.current_client( (int)$da['clid'] );
			}
			elseif ( (int)$da['pid'] > 0 ) {
				$parent = '<i class="icon-user-1"></i>'.current_person( (int)$da['pid'] );
			}

			$size  = num_format( filesize( $uploaddir.$da['file'] ) / 1000 );
			$dtime = filemtime( $uploaddir.$da['file'] );

			if ( filesize( $uploaddir.$da['file'] ) > 0 ) {
				$files[] = [
					"fid"    => (int)$da['id'],
					"icon"   => get_icon2( $da['file'] ),
					"file"   => $da['file'],
					"name"   => $da['title'],
					"date"   => date( 'H:i d-m-Y', $dtime ),
					"parent" => $parent,
					"size"   => $size
				];
			}

		}

	}
	if ( $link == "akt" ) {

		$s  = [];
		$s1 = '';

		$temps = [
			'akt_simple.htm' => 'Акт приема-передачи. Услуги',
			'akt_full.htm'   => 'Акт приема-передачи (расширенный). Услуги',
			'akt_prava.htm'  => 'Акт приема-передачи. Права'
		];

		//типы документов
		$typeAkt = $typeAktPeriod = [];
		$result  = $db -> query( "SELECT * FROM {$sqlname}contract_type where type != '' and identity = '$identity'" );
		while ($data = $db -> fetch( $result )) {

			if ( $data['type'] == 'get_dogovor' ) {
				$typeDogovor[] = (int)$data['id'];
			}
			if ( $data['type'] == 'get_aktper' ) {
				$typeAktPeriod[] = (int)$data['id'];
			}
			if ( $data['type'] == 'get_akt' ) {
				$typeAkt[] = (int)$data['id'];
			}

		}

		$isAkt = array_merge( $typeAkt, $typeAktPeriod );

		$typeAktPeriod = implode( ",", $typeAktPeriod );  //акты ежемесячные
		$typeAkt       = implode( ",", $typeAkt );        //акты приема-передачи

		if ( (int)$did > 0 ) {

			$s[] = "did = '$did'";

			$clid[] = (int)getDogData( $did, 'clid' );
			$pidl   = yexplode( ";", getDogData( $did, 'pid_list' ) );

			$pid = array_unique( array_merge( $pid, $pidl ) );

		}

		if ( !empty( $clid ) && $clid[0] > 0 ) {
			$s[] = "(clid IN (".implode( ",", $clid ).") OR payer IN (".implode( ",", $clid ).") OR pid IN (SELECT pid FROM {$sqlname}personcat WHERE clid IN (".implode( ",", $clid ).") AND identity = '$identity'))";
		}

		if ( !empty( $pid ) && $pid[0] > 0 ) {
			$s[] = "pid IN (".implode( ",", $pid ).")";
		}

		if ( empty( $s ) ) {
			goto e3;
		}

		$s1 = " and (".implode( " or ", $s ).")";

		$result = $db -> query( "SELECT * FROM {$sqlname}contract WHERE deid > 0 $s1 and idtype IN (".implode( ",", $isAkt ).") and identity = '$identity' ORDER BY datum DESC" );
		while ($da = $db -> fetch( $result )) {

			$title = str_replace( " ", "_", 'Акт №'.$da['number'].' от '.format_date_rus_name( get_smdate( $da['datum'] ) ).' года.pdf' );
			$file  = "akt_".$da['number'].".pdf";

			if ( (int)$da['did'] > 0 ) {
				$parent = '<i class="icon-briefcase-1"></i>'.current_dogovor( (int)$da['did'] );
			}

			$exist = (file_exists( $uploaddir.$file )) ? 'yes' : 'no';

			$files[] = [
				"deid"   => (int)$da['deid'],
				"icon"   => '<i class="icon-file-pdf red"></i>',
				"file"   => $file,
				"name"   => $title,
				"date"   => get_sfdate2( $da['datum'] ),
				"parent" => $parent,
				"exist"  => $exist
			];

		}

		e3:

	}
	if ( $link == "invoice" ) {

		$s  = [];
		$s1 = '';

		if ( $did > 0 ) {

			$s[] = "did = '$did'";

			$clid[] = (int)getDogData( $did, 'clid' );
			$pidl   = yexplode( ";", getDogData( $did, 'pid_list' ) );

			$pid = array_unique( array_merge( $pid, $pidl ) );

		}

		if ( !empty( $clid ) && (int)$clid[0] > 0 ) {
			$s[] = "(clid IN (".implode( ",", $clid ).") OR pid IN (SELECT pid FROM {$sqlname}personcat WHERE clid IN (".implode( ",", $clid ).") AND identity = '$identity'))";
		}

		if ( !empty( $pid ) && (int)$pid[0] > 0 ) {
			$s[] = "pid IN (".implode( ",", $pid ).")";
		}

		if ( empty( $s ) ) {
			goto e4;
		}

		$s1 = " and (".implode( " or ", $s ).")";

		$res = $db -> query( "SELECT * FROM {$sqlname}credit WHERE crid > 0 $s1 and identity = '$identity' ORDER by crid DESC" );
		while ($data = $db -> fetch( $res )) {

			$title = str_replace( [
				" ",
				"/"
			], [
				"_",
				"-"
			], 'Счет №'.$data['invoice'].' от '.format_date_rus_name( get_smdate( $data['datum'] ) )." года.pdf" );
			$file  = "invoice_".$data['crid'].".pdf";

			if ( (int)$data['did'] > 0 ) {
				$parent = '<i class="icon-briefcase-1"></i>'.current_dogovor( $data['did'] );
			}

			$exist = (file_exists( $uploaddir.$file )) ? 'yes' : 'no';

			$files[] = [
				"crid"   => (int)$data['crid'],
				"icon"   => '<i class="icon-file-pdf red"></i>',
				"file"   => $file,
				"name"   => $title,
				"date"   => get_sfdate2( $data['datum'] ),
				"parent" => $parent,
				"exist"  => $exist
			];

		}

		e4:

	}

	if ( empty( $files ) ) {
		$error = "Упс. Ничего нет. Может быть не выбран получатель?";
	}

	$data = [
		"files" => arrayNullClean( $files ),
		"error" => $error
	];

	print json_encode_cyr( $data );
	exit();

}

/**
 * Поиск Контакта или клиента для написания письма
 */
if ( $action == 'search' ) {

	$q = texttosmall( $_REQUEST["q"] );
	if ( $q == '' ) {
		print 'error';
	}

	$list = [];

	$result = $db -> query( "SELECT LOWER(title) as title2, title, clid, iduser, mail_url FROM {$sqlname}clientcat WHERE (title LIKE '%".$q."%' or mail_url LIKE '%".$q."%') and identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {

		$mails = yexplode( ",", str_replace( ";", ",", (string)$data['mail_url'] ) );

		foreach ( $mails as $mail ) {
			if ( filter_var( $mail, FILTER_VALIDATE_EMAIL ) ) {
				$list[] = trim( $data['title'] )."|".trim( $mail )."|".$data['clid']."||".current_user( $data['iduser'] );
			}
		}

	}

	$result = $db -> query( "SELECT LOWER(person) as title2, person, pid, iduser, mail, clid FROM {$sqlname}personcat WHERE (person LIKE '%".$q."%' or mail LIKE '%".$q."%') and identity = '$identity'" );
	while ($data = $db -> fetch( $result )) {

		$mails = yexplode( ",", (string)str_replace( ";", ",", $data['mail'] ) );

		foreach ( $mails as $mail ) {
			if ( filter_var( $mail, FILTER_VALIDATE_EMAIL ) ) {
				$list[] = trim( $data['person'] )."|".trim( $mail )."||".$data['pid']."|".current_user( $data['iduser'] )."|".current_client( $data['clid'] );
			}
		}


	}

	if ( empty( $list ) ) {
		$list[] = trim( $q )."|".trim( $q )."||0||";
	}

	print yimplode( "\n", $list );

}

if ( $action == "tpl.edit.do" ) {

	$id      = (int)$_REQUEST['id'];
	$name    = $_REQUEST['name'];
	$content = htmlspecialchars( $_REQUEST['content'] );
	$share   = $_REQUEST['share'];

	$mes  = '';
	$list = [];

	if ( $id > 0 ) {

		try {

			$db -> query( "UPDATE {$sqlname}ymail_tpl SET ?u WHERE id = '$id' and identity = '$identity'", [
				"name"    => $name,
				"content" => $content,
				"share"   => $share
			] );
			$mes = "Готово";

		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
	else {

		try {

			$db -> query( "INSERT INTO {$sqlname}ymail_tpl SET ?u", [
				"name"     => $name,
				"content"  => $content,
				"iduser"   => $iduser1,
				"share"    => $share,
				"identity" => $identity
			] );

			$id  = $db -> insertId();
			$mes = "Готово";

		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	$r = $db -> getAll( "SELECT * FROM {$sqlname}ymail_tpl WHERE iduser = '$iduser1' and identity = '$identity'" );
	foreach ( $r as $tpl ) {

		$list[] = [
			"id"   => (int)$tpl['id'],
			"name" => $tpl['name']
		];

	}

	$response = [
		"id"   => $id,
		"mes"  => $mes,
		"list" => $list
	];

	print json_encode_cyr( $response );

	exit();
}
if ( $action == "tpl.delete" ) {

	$id = (int)$_REQUEST['id'];

	$mes  = '';
	$list = [];

	if ( $id > 0 ) {

		try {

			$db -> query( "delete from {$sqlname}ymail_tpl WHERE id = '".$id."' and identity = '$identity'" );
			$mes = "Готово";

		}
		catch ( Exception $e ) {

			$mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
	else {
		$mes = "Не выбран шаблон";
	}

	$response = [
		"id"  => $id,
		"mes" => $mes
	];

	print json_encode_cyr( $response );

	exit();
}
if ( $action == "tpl.get" ) {

	$id = (int)$_REQUEST['id'];

	$tpl = $db -> getRow( "SELECT * FROM {$sqlname}ymail_tpl WHERE id = '$id' and identity = '$identity'" );

	$response = [
		"id"      => $id,
		"name"    => $tpl['name'],
		"content" => htmlspecialchars_decode( $tpl['content'] ),
		"share"   => $tpl['share']
	];

	print json_encode_cyr( $response );

	exit();
}