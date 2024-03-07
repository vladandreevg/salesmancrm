<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Leads;

set_time_limit(0);

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Access-Control-Allow-Origin: *');

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$alert     = $_REQUEST['notifi'];
$total     = 0;
$totalNote = '';

$logfile = $rootpath."/cash/LeadCron.log";

$lead = new Leads();

// список ящиков
$boxes = $lead -> boxList([
	"active"  => "yes"
]);

// обходим ящики
foreach ($boxes as $id => $ebox) {

	unset($db);
	$db = new SafeMySQL($opts);

	// устанавливаем зону к зоне клиента
	$tmzone = $db -> getOne("SELECT timezone FROM ".$sqlname."settings WHERE id = '$ebox[identity]'");

	// устанавливаем временную зону для ящика
	date_default_timezone_set($tmzone);

	/**
	 * Устанавливаем дату в БД с учетом настроек сервера и смещением для пользователя. старт
	 */
	$tz  = new DateTimeZone( $tmzone );
	$dz  = new DateTime();
	$dzz = $tz -> getOffset( $dz );

	//print $tzone;
	$bdtimezone = $dzz / 3600 + $tzone;

	//если значение не корректно (больше 12), то игнорируем смещение временной зоны
	if ( abs( $bdtimezone ) > 12 ) {

		$tzone      = 0;
		$bdtimezone = $dzz / 3600;

	}

	$bdtimezone = ($bdtimezone > 0) ? "+".abs( $bdtimezone ) : "-".abs( $bdtimezone );
	$db -> query( "SET time_zone = '".$bdtimezone.":00'" );
	/**
	 * Установили временную зону. Финиш
	 */

	try {

		$params   = [
			"id"       => $id,
			"days"     => 7,
			"process"  => true,
			"identity" => $ebox['identity']
		];
		$messages = $lead -> getMessages($params);
		$ignored  = $lead -> ignored;
		$note     = $lead -> note;
		$error    = $lead -> error;

		$countLeads = count($note);
		$countBox = count($messages);
		$countIgnored = count($ignored);

		$total += $countLeads;

	}
	catch (Exception $e) {

		$error[] = $e -> errorMessage();

	}

	$error = !empty($error) ? json_encode_cyr($error) : "Нет";

	$text =
		current_datumtime()."
		Ящик ".$ebox['name'].": ".$ebox['smtp_user']." (ID ".$ebox['id']."):
		Загружено: $countBox писем.
		Добавлено: $countLeads заявки.
		Игнорировано: $countIgnored писем - загружены ранее.
		Ошибки: ".$error."
		==================================
	";

	file_put_contents($logfile, str_replace("\t", "", $text), FILE_APPEND);

	$totalNote .= $text;

}

if ($alert == 'yes') {

	print "
		Загружено <b>$total</b> записей.<br><br>
		Лог:<br>
		".nl2br($totalNote)."
	";

}

flush();

exit();