<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

// добавляем собственное событие к системе уведомлений по email
$hooks -> add_filter( 'add_custom_subscription', 'comments_subscription', 10 );

function comments_subscription($events = []) {

	$otherSettings = $GLOBALS['otherSettings'];

	if($otherSettings['comment']) {

		$events["comments.new"]   = "Обсуждение. Новое обсуждение";
		$events["comments.answer"] = "Обсуждение. Новый ответ";

	}

	return $events;

}
