<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// добавляем собственное событие к системе уведомлений Notify
$hooks -> add_filter( 'add_custom_notify', 'modcatalog_notify' );
$hooks -> add_filter( 'add_custom_notify_icon', 'modcatalog_notify_icon' );

function modcatalog_notify($events = []) {

	$events["sklad"] = "События склада";

	return $events;

}

function modcatalog_notify_icon($icons = []) {

	$icons["catalog"] = [
		"icon"  => "icon-archive",
		"color" => "broun"
	];

	return $icons;

}