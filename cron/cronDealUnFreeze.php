<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2021 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2022.x           */
/* ============================ */

/**
 * Скрипт проверяет наличие активностей по сделкам
 * Если активностей сегодня не было, то отправляем уведомление
 */

use Salesman\Notify;
use Salesman\Todo;
use SalesmanPro\Comet;

set_time_limit( 0 );

error_reporting( E_ERROR );
//ini_set( 'display_errors', 1 );
//ini_set('memory_limit', '512M');

$root = realpath( __DIR__.'/../' );

$identity = 1;

require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";
require_once $root."/inc/settings.php";
require_once $root."/inc/func.php";

$dir  = $root."/cash/unfreezReports";
$time = time();
$test = false;

createDir( $dir );

// очистим старые
$cmd0 = 'find '.$dir.' -maxdepth 1 -type f -name "*.xlsx" -mtime +5 -exec rm -f {} \;';
exec($cmd0, $list, $exit2 );

// текущая дата
$now = current_datum();

//номер недели
$data      = new DateTime();
$weekToday = $data -> format( 'N' );

// загружаем праздники и выходные
$holidays = file_exists( $root."/cash/holidays2022.json" ) ? json_decode( file_get_contents( $root."/cash/holidays2022.json" ), true ) : [];

// bool, является ли дата выходным
$isHoliday = in_array( current_datum(), $holidays['holidays'] );

// этап заморозки
$stepInHold = customSettings( 'stepInHold' );

$freezeInput = $otherSettings['dateFieldForFreeze'];

// активные сотрудники
$activeUsers = $db -> getCol( str_replace( "app_", $sqlname, "SELECT iduser FROM app_user WHERE secrty = 'yes'" ) );

$response = [];

if ( $weekToday < 6 && !$isHoliday ) {

	$db -> query("set sql_mode='ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

	/**
	 * Замороженные сделки. Контролируем по полю
	 */

	if ( $freezeInput != '' ) {

		$query = "
		SELECT
		    deal.did,
		    deal.clid,
		    deal.title,
		    deal.iduser
		FROM app_dogovor `deal`
		WHERE
			deal.did > 0 AND
			COALESCE(deal.close, 'no') != 'yes' AND
			deal.isFrozen = '1' AND 
			DATE(deal.{$freezeInput}) <= DATE(NOW())
		";

		$query = str_replace( "app_", $sqlname, $query );

		$result = $db -> query( $query );
		while ($da = $db -> fetch( $result )) {

			$taskTitle = "Внимание к ЗАМОРОЖЕННОЙ сделке";

			// если сотрудник не активен, то назначаем его руководителю
			if ( !in_array( $da['iduser'], $activeUsers ) ) {
				$da['iduser'] = $db -> getOne( str_replace( "app_", $sqlname, "SELECT mid FROM app_user WHERE iduser = '$da[iduser]'" ) );
				$taskTitle    = "Внимание к ЗАМОРОЖЕННОЙ сделке уволенного подчиненного";
			}

			// счетчик поставленных напоминаний
			$counts[ $da['iduser'] ]++;

			// последнее время напоминания
			$lastTime[ $da['iduser'] ] = $now." 13:30:00";

			// автор последнего перемещения на этап
			$lastUser = $db -> getOne( str_replace( "app_", $sqlname, "SELECT iduser FROM app_steplog WHERE did = '$da[did]'" ) );

			// если у сделки нет куратора, то берем последнего, изменившего этап
			if( (int)$da['iduser'] == 0 && !$test ){
				$da['iduser'] = (int)$lastUser;
			}

			$tsk = [
				"title"    => $taskTitle,
				"tip"      => "исх.Звонок",
				"alert"    => "yes",
				"clid"     => $da['clid'],
				"did"      => $da['did'],
				"datum"    => modifyDatetime( $lastTime[ $da['iduser'] ], ['format' => 'Y-m-d'] ),
				"totime"   => modifyDatetime( $lastTime[ $da['iduser'] ], ['format' => 'H:i:00'] ),
				"autor"    => 1,
				"notify"   => false,
				"user"     => $da['iduser'],
				"lastTime" => $lastTime[ $da['iduser'] ],
				"lastUser" => $lastUser
			];

			// ставим напоминание
			if( !$test ) {
				$r = $task -> add( $da['iduser'], $tsk );
			}

			$response[] = $tsk;

		}

	}

	// отправка уведомлений
	foreach ( $counts as $iduser => $count ) {

		$args      = [
			"id"      => $tid,
			"autor"   => 1,
			"iduser"  => $iduser,
			"title"   => sprintf( "Вам назначены напоминания по %s сделкам", $count ),
			"tip"     => "note",
			"content" => sprintf( "Вам назначены напоминания по %s сделкам", $count )
		];
		$notifys[] = Notify ::edit( 0, $args );

	}

	$header = ["Напоминание", "Тип","Напоминать","CLID","DID","Дата","Время","Автор","Уведомление","Кому","LastTime","LastUser"];
	array_unshift( $response, $header );

	Shuchkin\SimpleXLSXGen ::fromArray( $response ) -> saveAs( $dir."/".$time.".xlsx" );

	printf( "Установлено %s напоминаний для %s сотрудников. Отчет: <a href=\"/cash/unfreezReports/".$time.".xlsx\"></a>", array_sum( $counts ), count( $counts ) );

	//flush();
	//ob_flush();

}

exit();