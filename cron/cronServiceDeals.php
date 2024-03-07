<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Скрипт для автоматического формирования актов и счетов для Сервисных сделок. Не закончен
 */

set_time_limit(0);

// Устанавливаем возможность отправлять ответ для любого домена или для указанных
header('Access-Control-Allow-Origin: *');

error_reporting(E_ERROR);

/**
 * Переопределяем константы, которые нам будут не доступны
 */
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__.'/../');

$root = realpath(__DIR__.'/../');


/**
 * Параметры для работы скрипта
 */
$alert = "yes";

require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";
require_once $root."/inc/func.php";

//require_once $root."/inc/class/Invoice.php";
//require_once $root."/inc/class/Akt.php";

$messages = $error = [];

/**
 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
 * В противном случае получим ошибку "safemysql MySQL server has gone away"
 */
unset($db);
$db = new \SafeMySQL($opts);

//находим все типы сервисных сделок
$services = isServices();

if (empty($services)) goto ext;

//проходим все сделки, у которых период заканчивается в течение +/- 5 дней
$result = $db -> query("SELECT * FROM ".$sqlname."dogovor WHERE tip IN (".yimplode(",", $services).") AND datum_end BETWEEN DATE_FORMAT(CURRENT_DATE - INTERVAL 5 DAY, '%Y-%m-%d') AND DATE_FORMAT(CURRENT_DATE + INTERVAL 5 DAY, '%Y-%m-%d')");
while ($item = $db -> fetch($result)) {

	$identity = $item['identity'];

	//смотрим последний оплаченный счет
	$crid = $db -> getOne("SELECT MAX(crid) FROM ".$sqlname."credit WHERE do != 'on' and did = '$item[did]' and identity = '$identity'");

	//шаблон для акта
	$temp = $other[39];

	//работаем, если счет ещё не выставлен
	if ($crid < 1) {

		/**
		 * ID последнего оплаченного счета
		 */
		$invoiceLast = $db -> getOne("SELECT * FROM ".$sqlname."credit WHERE crid = (SELECT MAX(crid) FROM ".$sqlname."credit WHERE do = 'on' AND did = '$item[did]' AND identity = '$identity') AND did = '$item[did]' AND identity = '$identity'");

		/**
		 * Данные по спецификации
		 */
		$speka = getSpekaData($item['did']);

		// !!! этот блок закрыт, потому что новый счет выписываем после создания акта
		/**
		 * нужно выставить счет
		 */
		/*
		$paramInvoice = [
			"tip"          => "По спецификации",
			"igen"         => "yes",
			"template"     => "",
			"iduser"       => $item['iduser'],
			"summa_credit" => $speka['summaInvoice'],
			"nds_credit"   => $speka['summaNalog'],
		];
		$invoice = new \Salesman\Invoice();
		$r = $invoice ->add($item['did'], $paramInvoice);
		*/

		/**
		 * выписать акт по предыдущему счету. Для Сервисных сделок предусмотрено двойное действие Акт + Счет
		 */
		$paramAkt = [
			"did"        => $item['did'],
			"igen"       => "yes",
			"iduser"     => $item['iduser'],
			"temp"       => $temp,
			"newinvoice" => "yes",
			"rs"         => $invoiceLast['rs'],
			"tip"        => $invoiceLast['tip'],
			"template"   => $invoiceLast['template'],
			"summa"      => $invoiceLast['summa_credit'],
			"crid"       => $invoiceLast['crid']
		];
		$akt      = new \Salesman\Akt();
		$r        = $akt -> edit(0, $paramAkt);

		if($r['deid'] > 0) {

			/**
			 * получим вложения и ссылки на них
			 */
			$r2 = $akt -> link($r['deid'], ["did" => $item['did']]);

			if(!empty($r2['data'])) {

				/**
				 * отправить всё это по email
				 */
				$paramMail = [
					"did"    => $item['did'],
					//"file"   => $r2['data'],
					"iduser" => $item['iduser'],
				];
				$r3        = $akt -> mail($r['deid'], $paramMail);

			}

		}

	}

}

if ($alert == 'yes') {

	$er = (!empty($error)) ? "Error count: $count. Description:\n".implode("\n", $error) : "Successe";

	print "Loaded $count items. $er\n";

}

ext:

flush();