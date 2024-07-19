<?php
set_time_limit( 0 );
error_reporting( 0 );

/**
 * Добавить в крон задание на выполнение этого скрипта
 *    * * * * * path/to/phpbin path/to/scheduler.php 1>> /dev/null 2>&1
 *
 * Как работает:
 * https://devhub.io/repos/peppeocchi-php-cron-scheduler
 */

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/func.php";

require_once __DIR__."/php/autoload.php";
require_once __DIR__.'/vendor/autoload.php';

use GO\Scheduler;

$identity = 1;

createDir( $rootpath."/cash/cron" );

$cron = new Cronman\Cronman();

// Create a new scheduler
$scheduler = new Scheduler( [
	'tempDir'   => $rootpath."/cash/cron/"
] );

// ... configure the scheduled jobs (see below) ...

// очищаем
$scheduler -> clearJobs();

// список заданий
$list = $cron -> getTaskList();

foreach ( $list as $job ) {

	$appendix = '';

	if ( $job[ 'active' ] == 'on' ) {

		// сброс предыдущего задания
		$scheduler->resetRun();

		$appendix    = " ".$job['id'];

		// если задание надо выполнить сейчас, то передаем ID задания в качестве параметров
		if ($job['parent'] == 'once' || $job['parent'] == 'Разово') {

			$hour   = (int)date('H');
			$minute = (int)date('i');
			$day    = (int)date('d');
			$month  = (int)date('m');

			$job['task'] = "$minute $hour $day $month *";
			$appendix    = " ".$job['id'];

		}

		// формируем новое задание
		$scheduler -> php(
			$job['script'].$appendix,
			$job[ 'bin' ]
		) -> at(
			$job[ 'task' ]
		) -> then( static function( $output ) use ( $job, $cron) {

			$output = (is_array($output)) ? json_encode_cyr($output) : $output;

			//записываем лог
			$log = [
				"uid" => $job['id'],
				"task" => $job[ 'script' ],
				"response" => $output,
			];
			$cron -> logger($log);

		}, false );//->output( $rootpath."/cash/cronManager.log", true );

	}

}

$scheduler->run();

$jobsExecuted = $scheduler->getExecutedJobs();
$jobsOut = $scheduler->getVerboseOutput();

file_put_contents($rootpath."/cash/cron/cron.log", current_datumtime().":\n".array2string($jobsExecuted)."\n-----------\n", FILE_APPEND);
file_put_contents($rootpath."/cash/cron/output.log", current_datumtime().":\n".$jobsOut."\n-----------\n", FILE_APPEND);