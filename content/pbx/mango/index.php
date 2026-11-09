<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 3 );

$url_path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', trim($url_path, ' /'));

//print_r($uri_parts);

$script = $uri_parts[3];

// если не указано расширение *.php, то добавляем его
if ( stripos( $script, 'php' ) === false ) {

	// для поддержки Nginx убираем первый элемент массива, т.к. у него в GET первый параметр приходит "/card.deal"
	if( $_SERVER['SERVER_SOFTWARE'] == 'nginx' ) {
		$x = array_shift($_GET);
	}

	$script = "{$script}.php";

}

include_once $rootpath."/content/pbx/mango/$script";