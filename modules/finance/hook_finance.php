<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

global $hooks;

// добавляем собственное событие к системе уведомлений по email. Отключено, т.к. не реализовано
// $hooks -> add_filter( 'add_custom_subscription', 'finance_subscription' );

// добавляем собственное событие к системе уведомлений Notify
$hooks -> add_filter( 'add_custom_notify', 'finance_notify' );
$hooks -> add_filter( 'add_custom_notify_icon', 'finance_notify_icon' );

/**
 * @param array $events
 * @return array
 */
function finance_notify(array $events = [] ): array {

	$events["budjet.new"]  = "Бюджет. Новый расход";
	$events["budjet.edit"] = "Бюджет. Расход изменен";
	$events["budjet.doit"] = "Бюджет. Расход проведен";

	return $events;

}

/**
 * @param array $icons
 * @return array
 */
function finance_notify_icon(array $icons = [] ): array {

	$icons[ "budjet.new" ] =
	$icons[ "budjet.edit" ] =
	$icons[ "budjet.doit" ] = [
		"icon"  => "icon-list-alt",
		"color" => "deepblue"
	];

	return $icons;

}

function finance_subscription($events = []) {

	global $userRights;

	if($userRights['budjet']) {

		$events["budjet.new"]  = "Бюджет. Новый расход";
		$events["budjet.edit"] = "Бюджет. Расход изменен";
		$events["budjet.doit"] = "Бюджет. Расход проведен";

	}

	return $events;

}

