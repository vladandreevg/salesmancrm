<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */
error_reporting(E_ALL);

$url_path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', trim($url_path, ' /'));

//print_r($uri_parts);

$script = '';

if ( !empty( $uri_parts[0] ) ) {
	$script = $uri_parts[0];
}
if ( stripos( $script, 'php' ) === false ) {
	$script = "{$script}.php";
}

include_once (string)($script);