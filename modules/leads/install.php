<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

//Скрипт добавляет строку в базу настроек плагина

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

//добавим запись в таблицу модулей для текущего аккаунта
$isModule = $db -> getOne( "SELECT COUNT(*) FROM {$sqlname}modules WHERE mpath = 'leads' AND identity = '$identity'" ) + 0;
if ( $isModule == 0 ) {

	$db -> query( "INSERT INTO {$sqlname}modules SET ?u", [
		'title'        => 'Сборщик заявок',
		'mpath'        => 'leads',
		'icon'         => 'icon-mail-alt',
		'active'       => 'on',
		'content'      => '{"leadСoordinator":"","leadMethod":"randome","leadOperator":[],"leadSendCoordinatorNotify":"yes","leadSendOperatorNotify":"yes","leadSendClientNotify":"yes","leadSendClientWellcome":"yes","leadCanDelete":"all","leadCanView":"yes"}',
		'activateDate' => current_datumtime(),
		'identity'     => $identity
	] );

	print 'Модуль "Сборщик заявок" активирован<br>';

}

exit();