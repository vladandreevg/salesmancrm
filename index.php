<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */
error_reporting(E_ERROR);

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

if($uri_parts[0] != "files" && $uri_parts[1] == '') {

	if ( $uri_parts[0] == 'install.php' || stripos( $uri_parts[0], 'install' ) !== false ) {
		header( "Location: /_install/" );
	}

	if ( $uri_parts[0] == 'update.php' || stripos( $uri_parts[0], 'update' ) !== false ) {
		header( "Location: /_install/update.php" );
	}

	$script = 'desktop.php';

	if ( !empty( $uri_parts[0] ) ) {
		$script = $uri_parts[0];
	}

	if ( stripos( $script, 'php' ) === false ) {
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
elseif( (int)$uri_parts[1] > 0 && stripos( $uri_parts[0], 'card' ) !== false ){

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
				Файл не найден.
				
			</div>
			
		</div>
	';

	exit();

}