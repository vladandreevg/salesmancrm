<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );

// сессия принимается только из cookie — параметр ses игнорируется
// (защита от session fixation и утечки токена через URL/логи)
$ses = $_COOKIE['ses'];

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

// выдача файлов — только авторизованным пользователям
if ((int)$iduser1 < 1) {

	print '
	<div class="warning text-left">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>Сбой авторизации. Авторизуйтесь заново <a href="/login" class="button">здесь</a>.<br>
	</div>
	';

	exit();

}

$thisfile = basename( __FILE__ );

$download = $_REQUEST['disposition'];
$filename = $_REQUEST['file'];
$fid      = (int)$_REQUEST['fid'];
$oname    = $_REQUEST['oname'];

$disp = ($download == 'yes') ? 'attachment' : 'inline';

if ( $filename != '' ) {

	$filename = str_replace( "../", "", $filename );

	$file      = $rootpath."/files/".$fpath.$filename;
	$mime      = get_mimetype( $filename );
	$extension = texttosmall( substr( strrchr( $filename, "." ), 1 ) );

	//если мы смотрим файл из почты
	if ( stripos( $filename, 'ymail' ) !== false ) {

		$f        = str_replace( 'ymail/', '', $filename );
		$filename = $db -> getOne( "SELECT name FROM ".$sqlname."ymail_files WHERE file = ?s and identity = ?i", $f, (int)$identity );

	}

	//из документов
	if ( $oname != '' ) {
		$filename = str_replace( "/", "--", $oname );
	}

	$filename = str_replace( [
		" ",
		","
	], [
		"_",
		""
	], $filename );

	// защита от Path Traversal: файл обязан лежать внутри /files/
	$file = safeFilePath( $rootpath."/files/".$fpath.$filename, $rootpath."/files/".$fpath );

	if ( $file !== false && file_exists( $file ) ) {

		// попытаемся посмотреть файл
		// функция не работает. основная причина - невозможно сохранить форматирование
		if ( isViewable( $filename ) && $extension == 'docx' && $download != 'yes' ) {

			//require "opensource/class/docx_reader.php";

			$doc = new Docx_reader();
			$doc -> setFile( $file );

			$html = $doc -> to_html();

			$error = $doc -> get_errors();

			if ( $html == '' ) {
				print '
				<LINK rel="stylesheet" type="text/css" href="/assets/css/app.css">
				<LINK rel="stylesheet" href="/assets/css/fontello.css">
				<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></SCRIPT>
				<SCRIPT type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></SCRIPT>
				<SCRIPT type="text/javascript" src="/assets/js/app.js"></SCRIPT>
				<div class="warning text-left" style="width:300px">
					<span><i class="icon-attention red icon-5x pull-left"></i></span>
					<b class="red uppercase miditxt">Упс :(</b><br><br>
					Не могу получить содержимое.<br>
				</div>
				<script type="text/javascript">
					$(".warning").center();
				</script>
				';
				exit();
			}

			$template = file_get_contents( $rootpath."/content/tpl/docx.view.mustache" );
			$tags     = [
				"title"   => str_replace( "_", " ", trim( $filename ) ),
				"content" => $html
			];

			foreach ( $tags as $tag => $value ) {
				$template = str_replace( "{{".$tag."}}", $value, $template );
			}

			print $template;

		}
		else {

			header( 'Content-Type: '.$mime );
			header( 'Content-Disposition: '.$disp.'; filename="'.safeHeaderValue( trim( str_replace( ",", "", $filename ) ) ).'"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Accept-Ranges: bytes' );

			readfile( $file );

		}

		exit();

	}

	$notExist = true;

}
if ( $fid > 0 ) {

	$res = $db -> getRow( "select fname, ftitle from ".$sqlname."file where fid=?i and identity = ?i", (int)$fid, (int)$identity );

	$fname  = $res["fname"];
	$ftitle = str_replace( " ", "_", trim( $res["ftitle"] ) );

	// защита от Path Traversal: файл обязан лежать внутри /files/
	$file = safeFilePath( $rootpath."/files/".$fpath.$fname, $rootpath."/files/".$fpath );
	$mime = get_mimetype( $fname );

	$extention = texttosmall( getExtention( $fname) );

	if ( $file !== false && file_exists( $file ) ) {

		header( 'Content-Type: '.$mime );
		header( 'Content-Disposition: '.$disp.'; filename="'.safeHeaderValue( str_replace( ",", "", $ftitle ) ).'"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Accept-Ranges: bytes' );

		readfile( $file );

		exit();

	}

	$notExist = true;

}

// для чатов
if ( $_REQUEST['attach'] != '' ) {

	$attach = $_REQUEST['attach'];
	$name = $_REQUEST['name'];

	// внешняя ссылка (например, изображение из чата, хранимое на внешнем CDN)
	$parsed = parse_url( $attach );
	$isExternal = isset( $parsed['scheme'] ) && $parsed['scheme'] != '';

	if( $isExternal ) {

		// допускаем только http/https ссылки (изображения из чата, размещенные на внешнем CDN).
		// Блокируем javascript:, data:, file: и CR/LF (защита от open-redirect и header injection).
		if ( !in_array( strtolower( (string)$parsed['scheme'] ), [ 'http', 'https' ] ) ) {
			$notExist = true;
		}
		elseif ( preg_match( '#[\r\n]#', $attach ) ) {
			$notExist = true;
		}
		else {
			header( "Location: ".$attach );
			exit();
		}

	}
	else {

		// внутренний путь - ограничиваем каталогом /files/
		$file = safeFilePath( $rootpath.$attach, $rootpath."/files/" );
		$mime = get_mimetype( $file === false ? $attach : $file );
		$extension = getExtention( $file === false ? $attach : $file );

		if ( $file !== false && file_exists( $file ) ) {

			header( 'Content-Type: '.$mime );
			header( 'Content-Disposition: '.$disp.'; filename="'.safeHeaderValue( trim( str_replace( ",", "", $name ) ) ).'"' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Accept-Ranges: bytes' );

			readfile( $file );

			exit();

		}

		$notExist = true;

	}

}

if ( $notExist ) {

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