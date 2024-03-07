<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Скрипт-демон для работы в фоне
 * Проверяет почтовые ящики пользователей для модуля Почтовик
 * Поддерживает PHP 5.6 и выше
 * Узнать путь, где расположен PHP ( locate -b '\php' - список размещения исполняемых файлов PHP )
 *
 * Запуск:
 *  php /path_to_folder/cron/cliYmailChecker.php
 *  где
 *   - php может быть указан как путь до папки с исполняемой версией PHP
 *
 * Остановка:
 *  - через терминал с помощью команды "kill PID" ( PID записан в файл /cash/EmailCollector.pid )
 *  - поместить пустой файл с именем ystop.txt в папку /cron/
 */

/**
 * Скрипт для проверки всех почтовых ящиков в фоне по расписанию CRON
 */
set_time_limit( 0 );

error_reporting( E_ERROR );

/**
 * Переопределяем константы, которые нам будут не доступны
 */
$_SERVER[ 'DOCUMENT_ROOT' ] = dirname( __DIR__ );

$root = dirname( __DIR__ );

require_once $root."/inc/config.php";
require_once $root."/inc/dbconnector.php";
require_once $root."/inc/func.php";

global $isCloud;

$logfile = $root."/cash/cronMailerChecker.log";
$isActive = false;
$lastTime = current_datumtime(1);

if( file_exists($logfile) ){

	$isActive = file_get_contents($logfile) == 1;
	$lastTime = unix_to_datetime(fileatime($logfile));

}

// если скрипт не запущен ИЛИ прошло более 5 минут с момента записи в лог-файл, то выполняем
if(!$isActive || diffDateTimeSeq($lastTime) > 600 ) {

	file_put_contents($logfile, true);

	/**
	 * Параметры для работы скрипта
	 * $alert - выводить результат на экран
	 *
	 */
	$alert = "no";

	$tcount = 1;

	$da = $db -> getCol( "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '".$sqlname."tempcron'" );
	if ( $da[0] == 0 ) {

		$sql = "
		CREATE TABLE ".$sqlname."tempcron (
			id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, 
			date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, 
			mail VARCHAR(250) NOT NULL,  
			iduser INT(11) NOT NULL, 
			identity INT(30) NOT NULL DEFAULT '1'
		)
		ENGINE=InnoDB";

		$db -> query( $sql );

	}

	$direct = [
		"INBOX",
		"SEND"
	];


	$counts = 0;

	/**
	 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
	 * В противном случае получим ошибку "safemysql MySQL server has gone away"
	 */
	unset( $db );
	$db = new SafeMySQL( $opts );

	/**
	 * Список почтовых ящиков
	 */
	$ymBox = $db -> query( "SELECT settings, iduser, identity FROM ".$sqlname."ymail_settings" );
	//print $db -> lastQuery();
	//print_r( $ymBox );
	while ($value = $db -> fetch( $ymBox )) {

		$identity             = $value['identity'];
		$ymailSet             = json_decode( $value['settings'], true );
		$ymailSet['iduser']   = $value['iduser'];
		$ymailSet['identity'] = $value['identity'];

		/**
		 * Устанавливаем временную зону. Старт
		 */
		$fpath    = '';

		if ( $isCloud ) {
			$fpath = $identity."/";
		}

		$settingsFile = $rootpath."/cash/".$fpath."settings.all.json";
		$settings = json_decode( file_get_contents( $settingsFile ), true );
		$tmzone       = $settings["tmzone"];

		if ( $tmzone == '' ) {
			$tmzone = 'Europe/Moscow';
		}

		date_default_timezone_set( $tmzone );

		/**
		 * Устанавливаем дату в БД с учетом настроек сервера и смещением для пользователя. старт
		 */
		$tz  = new DateTimeZone( $tmzone );
		$dz  = new DateTime();
		$dzz = $tz -> getOffset( $dz );

		//print $tzone;
		$bdtimezone = $dzz / 3600 + $tzone;

		//если значение не корректно (больше 12), то игнорируем смещение временной зоны
		if ( abs( $bdtimezone ) > 12 ) {

			$tzone      = 0;
			$bdtimezone = $dzz / 3600;

		}

		$bdtimezone = ($bdtimezone > 0) ? "+".abs( $bdtimezone ) : "-".abs( $bdtimezone );
		$db -> query( "SET time_zone = '".$bdtimezone.":00'" );
		/**
		 * Установили временную зону. Финиш
		 */

		/**
		 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
		 * В противном случае получим ошибку "safemysql MySQL server has gone away"
		 */
		unset( $db2 );
		$db2 = new SafeMySQL( $opts );
		$db2 -> query( "SET time_zone = '".$bdtimezone.":00'" );

		//получаем ключ для расшифровки пароля
		$ivc  = $db2 -> getOne( "SELECT ivc FROM ".$sqlname."settings WHERE id = '$identity'" );
		$skey = 'vanilla'.(($identity + 7) ** 3).'round'.(($identity + 3) ** 2).'robin';

		$ymailSet['ymailUser'] = rij_decrypt( $ymailSet['ymailUser'], $skey, $ivc );

		/**
		 * Проверим время последнего срабатывания для указанного ящика
		 */
		$lasttime = $db2 -> getRow( "SELECT id, (TIMESTAMPDIFF(SECOND, date, CURRENT_TIMESTAMP)) as time FROM ".$sqlname."tempcron WHERE mail = '$ymailSet[ymailUser]' AND identity = '$identity' ORDER BY id DESC LIMIT 1" );

		//print "lasttime=".$lasttime;

		//проверяем, если прошло более 10 секунд
		if ( ($lasttime['id'] < 1 || $lasttime['time'] >= 5) && $ymailSet['ymailUser'] != '' ) {

			if ( $alert == 'yes' ) {
				print "Проверяю аккаунт $ymailSet[ymailUser]\n";
			}
			//ob_flush();
			//flush();

			/**
			 * Запишем в лог
			 */
			if ( $lasttime['id'] < 1 ) {
				$db2 -> query( "INSERT INTO ".$sqlname."tempcron SET ?u", [
					'date'     => current_datumtime(),
					'mail'     => $ymailSet['ymailUser'],
					'iduser'   => $value['iduser'],
					'identity' => $identity
				] );
			}
			else {
				$db2 -> query( "UPDATE ".$sqlname."tempcron SET ?u WHERE id = '$lasttime[id]'", [
					'date' => current_datumtime()
				] );
			}

			$count = 0;

			//$log = (imap_last_error() != '') ? current_datumtime().": Errors on Box $ymailSet[ymailUser] (id = $value[iduser]): ".imap_last_error() : current_datumtime().": Ok";

			if ( $ymailSet['ymailPass'] != '' ) {

				foreach ( $direct as $box ) {

					if ( $alert == 'yes' ) {
						print "Проверяю папку $box\n";
					}
					//ob_flush();
					//flush();

					unset( $db2 );
					$db2 = new SafeMySQL( $opts );
					$db2 -> query( "SET time_zone = '".$bdtimezone.":00'" );

					unset( $mail );

					$mail             = new Salesman\Mailer();
					$mail -> identity = $identity;
					$mail -> skey     = $skey;
					$mail -> ivc      = $ivc;
					$mail -> iduser   = $value['iduser'];
					$mail -> box      = $box;
					// параметр days задает количество дней проверки. по-умолчанию = 7
					//$mail -> days     = 14;
					$mail -> mailGet();

					$messages = $mail -> Messages;
					$error    = $mail -> Error;

					if ( $alert == 'yes' ) {
						print "Найдено ".count( $messages )." писем\n";
					}
					//ob_flush();
					//flush();

					if ( !empty( $messages ) ) {

						if ( $alert == 'yes' ) {
							print "Начинаю обработку писем\n";
						}
						//ob_flush();
						//flush();

						//обрабатываем сообщения
						$mail -> box      = $box;
						$mail -> Messages = $messages;
						$mail -> iduser   = $value['iduser'];
						$rez              = $mail -> mailGetWorker();

						//print array2string($rez)."\n";

						if ( $alert == 'yes' ) {
							print "Результат обработки: \n";
						}
						if ( $alert == 'yes' ) {
							print untag3( $rez['result'] )."\n";
						}

						if ( $rez['result'] == 'error' && $alert == 'yes' ) {
							print untag3( $rez['text'] )."\n";
						}
						//ob_flush();
						//flush();

					}

					//v.8.35 Удаляем старые сообщения
					$cday = ($ymailSet['ymailClearDay'] != '') ? (int)$ymailSet['ymailClearDay'] : 10;

					//Salesman\Mailer ::clearOtherMessages( $value['iduser'], $cday );
					//Salesman\Mailer ::clearOldMessages( $value['iduser'] );

					$result = [
						"result" => $rez['text'],
						"error"  => $rez['text'] == 'error' ? $rez['text'] : "",
						"count"  => $rez['mcount'],
						"lastid" => $rez['last']
					];

					$ff = fopen( $root."/cash/YmailChecker.log", 'ab' );

					$err = $result['error'];

					fwrite( $ff, $log."\n" );
					fwrite( $ff, current_datumtime().": Box ".$ymailSet['ymailUser']." (id=".$value['iduser'].") - ".$rez['result']."\n" );
					if ( $err != '' ) {
						fwrite( $ff, "Error description:\n".implode( "\n", $result['error'] ) );
					}
					fwrite( $ff, "==================================\n\n" );
					fclose( $ff );

					$count += $result['count'];

					$lastError = imap_last_error();


				}

			}
			else {

				if ( $alert == 'yes' ) {
					print "Ошибка: не найдены USERNAME и/или PASSWORD\n";
				}
				//ob_flush();
				//flush();

				$ff = fopen( $root."/cash/YmailChecker.log", 'ab' );
				fwrite( $ff, current_datumtime().": Error in Box ".$ymailSet['ymailUser']." - invalid USERNAME/PASSWORD\n" );
				fwrite( $ff, "==================================\n\n" );
				fclose( $ff );

				$lastError = "Invalid USERNAME/PASSWORD";

				$er1 = imap_errors();

				goto endBox;

			}


			/**
			 * Удаляем объект подключения к базе и создаем новый, для следующего цикла
			 * В противном случае получим ошибку "safemysql MySQL server has gone away"
			 */
			unset( $db );
			$db = new SafeMySQL( $opts );

			endBox:

			$time = current_datumtime();

			$log = ($lastError != '') ? "$time: $ymailSet[ymailUser] (id = $ymailSet[iduser]): Ошибки: $lastError" : "Результат: Ok";

			//после вызова стек ошибок вычищается, чтобы для следующего ящика они не применялись
			$er0 = imap_errors();

			$er = ($lastError != '') ? "Error. Description: $lastError\n" : "Result: Success\n";

			print "[".current_datumtime()."]\nFrom Box $ymailSet[ymailUser] (id = $ymailSet[iduser]) received $count letters;\n".$er."\n";

			$tcount++;

			flush();

			//делаем перерыв 5 секунд
			sleep( 10 );

		}

	}

	file_put_contents($logfile, false);

}

exit();