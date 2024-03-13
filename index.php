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

$rootpath = realpath( __DIR__ );

require_once $rootpath."/inc/licloader.php";

global $script;

/**
 * Файлы стандартных разделов размещаем в папке /content/interface
 * Файлы разделов модулей размещаем в папке модуля с именем interface.php
 * Файлы карточек модулей размещаем в папке модуля с именем interface.card.php
 */

$url_path  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_parts = explode('/', trim($url_path, ' /'));

//print_r($uri_parts);
//print str_contains( $uri_parts[0], 'card' );

// получение файла
if ($uri_parts[0] == "file") {

	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/auth.php";
	require_once $rootpath."/inc/func.php";
	require_once $rootpath."/inc/settings.php";

	$filename = $uri_parts[1];

	if (!is_numeric($filename)) {

		$file      = $rootpath."/files/".$fpath.$filename;
		$mime      = get_mimetype( $filename );

		header( 'Content-Type: '.$mime );
		header( 'Content-Disposition: attachment; filename="'.trim( str_replace( ",", "", $filename ) ).'"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Accept-Ranges: bytes' );

		readfile( $file );

		exit();

	}

	if((int)$filename > 0) {

		$res = $db -> getRow("SELECT fid, fname, ftitle FROM {$sqlname}file WHERE fid = '$filename' and identity = '$identity'");

		if( (int)$res['fid'] > 0 ) {

			$fname  = $res["fname"];
			$ftitle = str_replace(" ", "_", trim($res["ftitle"]));

			$file = $rootpath."/files/".$fpath.$fname;
			$mime = get_mimetype($fname);

			if (file_exists($file)) {

				header('Content-Type: '.$mime);
				header('Content-Disposition: attachment; filename="'.str_replace(",", "", $ftitle).'"');
				header('Content-Transfer-Encoding: binary');
				header('Accept-Ranges: bytes');

				readfile($file);

				exit();

			}

		}

	}

	print '
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" href="/assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
		<div style="width: 100vw; height: 100vh; position: relative; padding-top: 30vh;">
		
			<div class="warning text-left p20" style="width:300px; margin: 0 auto; padding-bottom: 40px !important;">
			
				<span><i class="icon-doc-alt red icon-5x pull-left"></i></span>
				<b class="red uppercase miditxt">Упс :(</b><br><br>
				Файл не найден.
				
			</div>
			
		</div>
	';

	exit();

}

// обычные пути, исключая ссылку на файлы (в них отсутствуют GET-параметры)
if($uri_parts[0] != "files" && empty($uri_parts[1])) {

	// обработка ссылки установщика
	if ( $uri_parts[0] == 'install.php' || stripos( $uri_parts[0], 'install' ) !== false ) {
		header( "Location: /_install/" );
	}

	// обработка ссылки установщика обновлений
	if ( $uri_parts[0] == 'update.php' || stripos( $uri_parts[0], 'update' ) !== false ) {
		header( "Location: /_install/update.php" );
	}

	// по умолчанию пусть будет стартовая страница
	$script = 'desktop.php';

	if ( !empty( $uri_parts[0] ) ) {
		$script = $uri_parts[0];
	}

	// если не указано расширение *.php, то добавляем его
	if ( stripos( $script, 'php' ) === false ) {

		// для поддержки Nginx убираем первый элемент массива, т.к. у него в GET первый параметр приходит "/card.deal"
		if( $_SERVER['SERVER_SOFTWARE'] == 'nginx' ) {
			$x = array_shift($_GET);
		}

		$script = "{$script}.php";

	}

	if ( $script === 'index.php' ) {
		$script = "desktop.php";
	}

	if ( $script === 'analitics.php' ) {
		$script = "report.php";
	}

	// подключаем стандартные модули
	if ( file_exists( $rootpath."/content/interface/$script" ) ) {
		include_once $rootpath."/content/interface/$script";
	}

	// подключаем интерфейсы модулей
	else {

		$script = pathinfo( $script )['filename'];

		// подключаем интерфейс раздела
		if ( file_exists( $rootpath."/modules/{$script}/interface.php" ) ) {
			include_once $rootpath."/modules/{$script}/interface.php";
		}

		// подключаем интерфейс карточки
		elseif ( stripos( $script, 'card' ) !== false ) {

			$s = explode( ".", $script );
			include_once $rootpath."/modules/".$s[1]."/interface.card.php";

		}
		else{

			print '
				<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
				<LINK rel="stylesheet" href="/assets/css/fontello.css">
				<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
				<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
				<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
				<div style="width: 100vw; height: 100vh; position: relative; padding-top: 30vh;">
				
					<div class="warning text-left p20" style="width:300px; margin: 0 auto; padding-bottom: 40px !important;">
					
						<span><i class="icon-doc-alt red icon-5x pull-left"></i></span>
						<b class="red uppercase miditxt">Ошибка 404</b><br><br>
						Ууупс. НЛО здесь было и всё-всё унесло :)
						
					</div>
					
				</div>
			';

			exit();

		}

	}

}

// поддержка пути /card.deal/ID
elseif( (int)$uri_parts[1] > 0 && stripos( $uri_parts[0], 'card' ) === false ){

	$script = $uri_parts[0];
	$id = (int)$uri_parts[1];

	switch ($script){
		case "card.deal":
			$_REQUEST['did'] = $id;
			$_GET['did'] = $id;
		break;
		case "card.client":
			$_REQUEST['clid'] = $id;
			$_GET['clid'] = $id;
		break;
		case "card.person":
			$_REQUEST['pid'] = $id;
			$_GET['pid'] = $id;
		break;
	}

	if ( stripos( $script, 'php' ) === false ) {
		$script = "{$script}.php";
	}

	//print_r($_REQUEST);

	if ( file_exists( $rootpath."/content/interface/$script" ) ) {
		include_once $rootpath."/content/interface/$script";
	}

}

// отсутствие пути, который не смогли обработать ранее
elseif( !file_exists( realpath( __DIR__.'/' ).$_SERVER['REQUEST_URI']) ){

	print '
		<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
		<LINK rel="stylesheet" href="/assets/css/fontello.css">
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
		<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
		<div style="width: 100vw; height: 100vh; position: relative; padding-top: 30vh;">
		
			<div class="warning text-left p20" style="width:300px; margin: 0 auto; padding-bottom: 40px !important;">
			
				<span><i class="icon-doc-alt red icon-5x pull-left"></i></span>
				<b class="red uppercase miditxt">Упс :(</b><br><br>
				Ресурс не найден.
				
			</div>
			
		</div>
	';

	exit();

}