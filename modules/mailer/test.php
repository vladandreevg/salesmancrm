<?php
error_reporting(E_ERROR);

include "../../inc/config.php";
include "../../inc/dbconnector.php";
include "../../inc/settings.php";
include "../../inc/func.php";
include "../../inc/auth.php";

//удаление писем за период 5 дней
/*
$m = [];

$r = $db -> query("SELECT id, iduser FROM ".$sqlname."ymail_messages WHERE DATEDIFF(datum, NOW() - INTERVAL 5 DAY) > 0 and state != 'deleted'");
while ($d = $db -> fetch($r)) {

	$ymail = new Ymail();
	$y     = $ymail -> getaction([
		"id"     => $d['id'],
		"iduser" => $d['iduser'],
		"tip"    => "delete"
	]);

	$m[] = [
		"id"   => $d['id'],
		"text" => $y
	];

	unset($ymail);

}

print array2string($m, "<br>", str_repeat("&nbsp;", 4));
*/

$mail = new Salesman\Mailer();

// просмотр сообщения (вывод для отображения)
/*
$mail -> id = 24159;
$mail -> mailView();
$email = $mail -> View;

print array2string($email, "<br>", str_repeat("&nbsp;", 4));
*/

// получение почты
$mail -> iduser = 1;
$mail -> days = 1;
$mail -> box = 'INBOX';
$mail -> uids = [35098];
$mail -> mailGet();

$msg = $mail -> Messages;

print_r($msg);
//print array2string($msg, "<br>", str_repeat("&nbsp;", 4));

// отправка почты
/*
$mail -> subject = "Тест с отправкой ".time();
$mail -> to      = [
	"email" => "a.vladislav.g@gmail.com",
	"name"  => "Владислав"
];
$mail -> html    = "<h1>Привет, {client}</h1><div>Это успех!</div><blockquote>А это какая-то цитата</blockquote><hr><div>{manager}</div>";

$mail -> mailEdit();

$mid   = $mail -> id;
$error = $mail -> Error;

print "ID $mid<br>";
print "Error:<br>".yimplode("<br>", $error);

try {

	$mail -> id = $mid;
	$result = $mail -> mailSubmit();

}
catch (phpmailerException $e) {

	$err[] = $e ->errorMessage();

}
catch (Exception $e) {

	$err[] = $e ->getMessage();

}

//print $mail -> messageid;
//print_r(get_object_vars($mail));

$rez = [
	"id"        => $id,
	"result" => $result,
	"error"     => $err,
	"messageid" => $result['messageid']
];

print_r($rez);
*/


// информация о сообщении
/*
$mail -> id = 27703;
$mail -> mailInfo();
$email = $mail -> Message;

print array2string($email, "<br>", str_repeat("&nbsp;", 4));
*/

