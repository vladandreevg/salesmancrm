<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

/**
 * CRON-скрипт, производящий отправку списка дел на текущий день всем сотрудникам
 */

error_reporting( E_ERROR );

$ypath = realpath( __DIR__.'/../' );

include $ypath."/inc/config.php";
include $ypath."/inc/dbconnector.php";
include $ypath."/inc/settings.php";
include $ypath."/inc/func.php";

//require_once $ypath."/opensource/Mustache/Autoloader.php";
Mustache_Autoloader ::register();

if ( count( $productInfo ) == 0 ) {
	$productInfo = [
		"name"      => "SalesMan CRM",
		"site"      => "https://isaler.ru",
		"crmurl"    => "",
		"email"     => "info@isaler.ru",
		"support"   => "support@isaler.ru",
		"info"      => "info@isaler.ru",
		"lastcalls" => true,
		"sipeditor" => true
	];
}

if ( $productInfo['crmurl'] == '' ) {
	$productInfo['crmurl'] = $_SERVER['HTTP_SCHEME'] ?? (((isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off') || 443 == $_SERVER['SERVER_PORT']) ? 'https://' : 'http://').$_SERVER["HTTP_HOST"];
}

if ( $productInfo['info'] == '' ) {
	$productInfo['info'] = 'info@'.$_SERVER["HTTP_HOST"];
}

if ( $productInfo['phone'] == '' ) {
	$productInfo['phone'] = '+7(922)3289466';
}


$template = file_get_contents( "taskToday.tpl" );

function getPersPhone($id, $identity): array {

	$ypath = realpath( __DIR__.'/../' );

	include_once $ypath."/inc/config.php";
	include_once $ypath."/inc/dbconnector.php";
	include_once $ypath."/inc/func.php";

	$sqlname = $GLOBALS['sqlname'];
	$db      = $GLOBALS['db'];

	$str = [];

	if ( $id ) {

		$resultp = $db -> getRow( "SELECT tel, mob FROM ".$sqlname."personcat WHERE pid = '$id' and identity = '$identity'" );

		if ( count( $resultp ) > 0 ) {

			$tel = $resultp["tel"];
			$mob = $resultp["mob"];

			$tel = yexplode( ",", str_replace( ";", ",", $tel ) );
			$mob = yexplode( ",", str_replace( ";", ",", $mob ) );

			$phones = array_merge( $tel, $mob );

			foreach ( $phones as $phone ) {
				if ( $phone != '' ) {
					$str[] = '<a href="tel:'.formatPhone2( $phone ).'">'.formatPhone2( $phone ).'</a>';
				}
			}

		}

	}

	return $str;

}

function getPersEmail($id, $identity): array {

	$ypath = realpath( __DIR__.'/../' );

	include_once $ypath."/inc/config.php";
	include_once $ypath."/inc/dbconnector.php";
	include_once $ypath."/inc/func.php";

	$sqlname = $GLOBALS['sqlname'];
	$db      = $GLOBALS['db'];

	$str = [];

	if ( $id ) {

		$mail = $db -> getOne( "SELECT mail FROM ".$sqlname."personcat WHERE pid = '$id' and identity = '$identity'" );
		if ( $mail != '' ) {

			$mails = yexplode( ",", str_replace( ";", ",", $mail ) );

			foreach ( $mails as $mail ) {
				if ( $mail != '' ) {
					$str[] = '<a href="mailto:'.trim( $mail ).'">'.trim( $mail ).'</a>';
				}
			}

		}

	}

	return $str;

}

function getActivTip($ttip): string {

	setlocale( LC_ALL, 'ru_RU.CP1251' );

	$tip = texttosmall( $ttip );

	if ( stripos( $tip, 'звон' ) !== false ) {
		$tipa = 'phone';
	}
	elseif ( stripos( $tip, 'фак' ) !== false ) {
		$tipa = 'phone';
	}
	elseif ( stripos( $tip, 'отправ' ) !== false ) {
		$tipa = 'email';
	}
	elseif ( stripos( $tip, 'почт' ) !== false ) {
		$tipa = 'email';
	}
	elseif ( stripos( $tip, 'предлож' ) !== false ) {
		$tipa = 'email';
	}
	//elseif(stripos($tip,'кп')!==false) $tipa = 'email';
	else {
		$tipa = '';
	}

	return $tipa;
}

$today = date( "Y-m-d", mktime( 0, 0, 0, date( 'm' ), date( 'd' ), date( 'Y' ) ) + $tm * 3600 );


//переберем всех активных сотрудников
$result = $db -> getAll( "SELECT iduser, email, title, identity FROM ".$sqlname."user where secrty = 'yes' ORDER BY iduser" );
foreach ( $result as $data ) {

	$html  = [];
	$tasks = [];

	//для текущего сотрудника составим список дел на завтра
	//отправляем только тем, у кого есть напоминания
	$resultt = $db -> query( "SELECT * FROM ".$sqlname."tasks where iduser = '".$data['iduser']."' and datum = '$today' and active = 'yes' and identity = '".$data['identity']."' ORDER BY totime" );
	while ($tdata = $db -> fetch( $resultt )) {

		//составим линки на карточки персон
		$card   = '';
		$autor  = '';
		$agenda = '';
		$border = 'border-blue';
		$person = [];

		$persons = yexplode( ";", $tdata['pid'] );
		$tipa    = getActivTip( $tdata['tip'] );

		foreach ( $persons as $pid ) {

			$url = [];
			$ap  = array_merge( getPersPhone( $pid, $data['identity'] ), getPersEmail( $pid, $data['identity'] ) );

			foreach ( $ap as $k ) {
				$url[] = ["url" => $k];
			}

			$person[] = [
				"id"    => $pid,
				"title" => current_person( $pid ),
				"url"   => $url
			];

		}

		if ( (int)$tdata['clid'] > 0 ) {
			$client = [
				"id"    => (int)$tdata['clid'],
				"title" => current_client( (int)$tdata['clid'] )
			];
		}


		if ( (int)$tdata['did'] > 0 ) {
			$deal = [
				"id"    => (int)$tdata['did'],
				"title" => current_dogovor( (int)$tdata['did'] )
			];
		}

		if ( (int)$tdata['priority'] == 2 ) {
			$border = 'border-red';
		}

		elseif ( (int)$tdata['priority'] == 1 ) {
			$border = 'border-gray';
		}

		//напоминание
		$tasks[] = [
			"time"     => ($tdata['day'] != 'yes') ? substr( $tdata['totime'], 0, 5 ) : 'Весь день',
			"title"    => $tdata['title'],
			"tip"      => $tdata['tip'],
			"agenda"   => nl2br( link_it( $tdata['des'] ) ),
			"person"   => $person,
			"isperson" => (!empty( $person ) ? 1 : ''),
			"client"   => $client,
			"deal"     => $deal,
			"border"   => ($tdata['day'] != 'yes') ? $border : 'border-green',
			"color"    => ($tdata['day'] != 'yes') ? 'blue' : 'green',
			"priority" => get_priority( 'priority', $tdata['priority'] ).get_priority( 'speed', $tdata['speed'] ),
			"iscard"   => (!empty( $person ) || !empty( $client ) || !empty( $deal )) ? true : NULL
		];

	}

	//формируем массив на день
	$html = [
		"datum"  => format_date_rus( current_datum() ),
		"crmurl" => $productInfo['crmurl'],
		"tasks"  => $tasks
	];

	//print_r($tasks);

	if ( !empty( $tasks ) ) {

		$m = new Mustache_Engine();

		//print
		$message = $m -> render( $template, $html );

		//делаем отправку письма
		$from     = 'no-replay@'.$_SERVER['HTTP_HOST'];
		$fromname = 'Напоминатель '.$productInfo['name'];
		$subject  = 'Мои дела на сегодня - '.format_date_rus( current_datum() ).' [CRM]';

		//mailer($data['email'], $data['title'], $from, $fromname, $subject, $message);
		mailto( [
			$data['email'],
			$data['title'],
			$from,
			$fromname,
			$subject,
			$message
		] );

	}

}


exit();
