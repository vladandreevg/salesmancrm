<?php
/* ============================ */
/* (C) 2015 Vladislav Andreev   */
/*        Yoolla Project        */
/*        www.yoolla.ru         */
/*           ver. 8.15          */
/* ============================ */

$content = json_encode_cyr($params);

function getDomain($domain){

	preg_match("/^(http:\/\/)?([^\/]+)/i", $domain, $matches);
	$host = $matches[2];

	preg_match("/[^\.\/]+\.[^\.\/]+$/", $host, $matches);
	$host="{$matches[0]}\n";

	return $host;

}

if($Error != 'yes') $rez = 'Success';
else $rez = $response['error']['text'];

//$result = $db -> query("INSERT INTO url (site, uid, url) VALUES ('$host', '$iidd', '$sites')");? не понятная таблица

$ip = $_SERVER['REMOTE_ADDR'];
$remoteaddr = getDomain($_SERVER['HTTP_REFERER']);

$db -> query("insert into ".$sqlname."logapi (id,content,rez,ip,remoteaddr,identity) values(null,'$content','$rez','$ip','$remoteaddr','$identity')");

$msg = array("Запрос" => $params, "Результат" => $rez, "IP-адрес" => $ip, "Домен" => $remoteaddr);

$f = fopen("salesmanapi.log", "a");
fwrite($f, json_encode_cyr($msg) . "\r\n");
fclose($f);