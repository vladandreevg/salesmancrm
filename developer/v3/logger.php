<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2024.1         */
/* ============================ */

global $rootpath, $method, $action;

/**
 * Проверим наличие таблицы, и если нет, то создадим
 */
$da = $db -> getOne( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."logapi'" );
if ( $da == 0 ) {

	$db -> query( "
			CREATE TABLE ".$sqlname."logapi (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`datum` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`content` MEDIUMTEXT NOT NULL,
			`rez` TEXT NOT NULL,
			`ip` VARCHAR(20) NOT NULL,
			`remoteaddr` TEXT NOT NULL,
			`identity` INT(20) NOT NULL,
			PRIMARY KEY (`id`)
		)
		COLLATE='utf8_general_ci'
		ENGINE=MyISAM
	" );

}

$params = array_merge( ['method' => str_replace( ".php", "", basename( $_SERVER['SCRIPT_NAME'] ) )], (array)$params );

$content = json_encode_cyr( $params );

$logdir = $rootpath."/cash/apilog";
$dir    = $logdir."/".str_replace( "-", "", current_datum() );

$thistime   = current_datumtime();

createDir( $logdir );
createDir( $dir );

function getDomain($domain): string {

	preg_match( "/^(http:\/\/)?([^\/]+)/i", $domain, $matches );
	$host = $matches[2];

	preg_match( "/[^\.\/]+\.[^\.\/]+$/", $host, $matches );
	$host = "{$matches[0]}";

	return $host;

}

$rez = ($Error != 'yes') ? 'Success' : $response['error']['text'];

$ip         = $_SERVER['REMOTE_ADDR'];
$remoteaddr = getDomain( $_SERVER['HTTP_REFERER'] );

$db -> query( "INSERT INTO ".$sqlname."logapi SET ?u", [
	"content"    => $content,
	"rez"        => $rez,
	"ip"         => $ip,
	"remoteaddr" => $remoteaddr,
	"identity"   => $identity
] );
$id = $db ->insertId();

$msg = [
	"time"     => $thistime,
	"method"   => $params['method'],
	"request"  => $params,
	"response" => $response,
	"ip"       => $ip,
	"domen"    => $remoteaddr
];


file_put_contents( $dir."/api-{$method}-{$action}-".str_replace( [" ", ":"], "", $thistime).".json", json_encode_cyr( $msg ), FILE_APPEND );


// очистка старых логов
$cmd = "find $logdir -maxdepth 1 -type d -mtime +5 -exec rm -r {} \;";
exec( $cmd, $list, $exit );