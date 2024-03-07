<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2023 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/*         ver. 2024.x          */
/* ============================ */

/**
 * Выполнение скрипта после прихода записи разговора
 */
$hooks -> add_action( 'asteriskapi_record', 'record_send' );

function record_send(stdClass $call) {



}