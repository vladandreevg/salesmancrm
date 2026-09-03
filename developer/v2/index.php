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

	// защита от include-траверсии: допускаем только имя существующего контроллера
	$candidate = basename((string)$uri_parts[0]);
	if ( stripos( $candidate, '.php' ) === false ) {
		$candidate = $candidate.'.php';
	}
	if ( preg_match( '#^[a-z0-9_-]+\.php$#i', $candidate ) && file_exists( __DIR__.'/'.$candidate ) ) {
		$script = $candidate;
	}

}

if ( $script !== '' ) {
	include_once $script;
}