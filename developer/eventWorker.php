<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*      SalesMan Project        */
/*        www.isaler.ru         */
/*         ver. 2018.x          */
/* ============================ */
?>
<?php
/**
 *  Обработчик для событий
 */
error_reporting(E_ERROR);

include "../inc/config.php";
include "../inc/dbconnector.php";
include "../inc/auth.php";
include "../inc/settings.php";
include "../inc/func.php";


function doevent($job) {

	$path = realpath(__DIR__.'/../');

	$workload = $job -> workload();
	$data = json_decode($workload, true);

	$url      = $data['url'];

	//удалим url целевого скрипта из блока данных
	unset($data['url']);

	//отправляем данные
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result = curl_exec($ch);

	$f = fopen($path."/cash/events.log", "a");
	fwrite($f, current_datumtime()." :: ".json_encode_cyr($data)."\n");
	fwrite($f, "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~\n\n");
	fclose($f);

	if($result === false) return $err = curl_error($ch);
	else return $result;

}


$worker = new GearmanWorker();
$worker -> addServer();
$worker -> addFunction('doevent', 'doevent');

while (1) {

	$worker->work();
	if ($worker->returnCode() != GEARMAN_SUCCESS) break;

}
