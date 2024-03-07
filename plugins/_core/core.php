<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */

function sendRequest($url, $params){

	$ch = curl_init();// Устанавливаем соединение
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	//curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	if(!$result)
		return $err = curl_error($ch);
	else
		return $result;

}

/**
 * Преобразует строку JSON в удобочитаемый текст
 * @param string $str - json string
 * @param bool $html - print in html format with br
 * @param integer $level
 * @return string $text
 */
function json2text($str, $html = true, $level = 0){

	//global $arr;

	$text = "";
	$newline = "\n";

	if($html) $newline = "<br>";

	if(!is_array($str)) $arr = json_decode($str, true);
	else $arr = $str;

	foreach($arr as $key => $value){

		if(is_array($value)) {

			$text.= $newline .str_repeat("&nbsp;&nbsp;&nbsp;", $level). "<b>". $key. "</b>: " . $newline .json2text($value, $html, $level + 1) . $newline;

		}
		else {

			$text.= str_repeat("<br>&nbsp;&nbsp;&nbsp;", $level). "<b>". $key. "</b>: " .$value . $newline;

		}

	}

	return $text;

}

function emailer($to, $toname = '', $from, $fromname = '', $subject, $html, $files = array()){

	global $mailsender_rez;

	$fpath = $GLOBALS['fpath'];
	$apath = $GLOBALS['apath'];
	$skey = $GLOBALS['skey'];
	$ivc = $GLOBALS['ivc'];
	$identity = $GLOBALS['identity'];
	$sqlname = $GLOBALS['sqlname'];
	$db = $GLOBALS['db'];

	$prefix = $_SERVER['DOCUMENT_ROOT'].$apath;

	include $prefix."/inc/config.php";
	include $prefix."/inc/dbconnector.php";
	include $prefix."/inc/func.php";
	include $prefix."/inc/settings.modcatalog.php";
	//require_once $prefix."inc/opensource/PHPMailer/class.phpmailer.php";
	//require_once $prefix."inc/opensource/PHPMailer/class.smtp.php";

	$result_set = $db -> getRow("select * from ".$sqlname."smtp WHERE identity = '$identity' and tip = 'send'");
	$active      = $result_set["active"];
	$smtp_host   = $result_set["smtp_host"];
	$smtp_port   = $result_set["smtp_port"];
	$smtp_auth   = $result_set["smtp_auth"];
	$smtp_secure = $result_set["smtp_secure"];
	$smtp_user   = rij_decrypt($result_set["smtp_user"], $skey, $ivc);
	$smtp_pass   = rij_decrypt($result_set["smtp_pass"], $skey, $ivc);
	$smtp_from   = $result_set["smtp_from"];

	$param = array("active" => $active, "smtp_host" => $smtp_host, "smtp_port" => $smtp_port, "smtp_auth" => $smtp_auth, "smtp_secure" => $smtp_secure, "smtp_user" => $smtp_user, "smtp_pass" => $smtp_pass, "smtp_from" => $smtp_from);

	global $mailsender_rez;

	//получим данные сервера smtp для подключения
	$mail = new PHPMailer();

	if($active == 'yes'){

		$mail->IsSMTP();
		$mail->SMTPAuth = $smtp_auth;
		$mail->SMTPSecure = $smtp_secure;
		$mail->Host = $smtp_host;
		$mail->Port = $smtp_port;
		$mail->Username = $smtp_user;
		$mail->Password = $smtp_pass;
		//$mail->SMTPDebug  = 1;

	}
	else{
		$mail->isSendmail();

	}

	$mail->CharSet = 'utf8';
	$mail->setLanguage('ru', $prefix.'vendor/phpmailer/phpmailer/language/');
	$mail->IsHTML(true);
	$mail->SetFrom('Cron', $fromname);
	$mail->AddAddress($to, $toname);

	$mail->Subject = $subject;

	if(count($files)>0){//$files - переданный массив

		for($i=0;$i<count($files);$i++){
			$file[$i] = $prefix."files/".$fpath.$files[$i]['file'];
			$mail->AddAttachment($file[$i], $files[$i]['name']);
		}

	}

	$mail->Body = $html;

	if(!$mail->Send()) {
		$mailsender_rez = 'Ошибка: '.$mail->ErrorInfo;
	}
	else $mailsender_rez = '';

	$mail->ClearAddresses();
	$mail->ClearAttachments();
	$mail->IsHTML(false);

	return $mailsender_rez;
}
