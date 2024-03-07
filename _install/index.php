<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */

//ini_set( 'display_errors', 1 );
error_reporting( E_ERROR );

$rootpath = dirname(__DIR__);

$filename = $rootpath."/inc/config.php";

if ( file_exists( $filename ) ) {

	require_once $rootpath."/vendor/autoload.php";
	require_once $rootpath."/inc/config.php";
	require_once $rootpath."/inc/dbconnector.php";
	require_once $rootpath."/inc/func.php";
	require_once $rootpath."/inc/settings.php";

	$current = $db -> getRow( "SELECT * FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1" );
}


$actual = json_decode( file_get_contents( "https://salesman.pro/download/repo/version.json" ), true );

$ver = json_decode( file_get_contents( $rootpath."/_whatsnew/version.json" ), true );

$toinstall = true;

$button = $ibutton = '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta content="text/html; charset=utf-8" http-equiv="content-type">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="default">

	<title>SalesMan CRM</title>

	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">

	<style>
		<!--
		@import url("/assets/css/app.css");
		@import url("/assets/css/fontello.css");

		body {
			font-size  : 14px;
			background : #FFF;
			overflow   : auto !important;
			padding    : 30px 100px;
		}

		.variants {
			margin                : 0 auto;
			display               : grid;
			grid-template-columns : 1fr 1fr;
			grid-gap              : 0;
			border                : 1px solid var(--gray-lite);
			border-radius         : 5px;
		}

		.variant {
			display            : grid;
			grid-template-rows : 50px auto 50px;
			grid-gap           : 0;
			padding            : 20px;
		}

		.variant:first-child {
			border-right : 1px solid var(--gray-lite);
		}

		.height {
			height : 220px;
		}

		.success,
		.attention,
		.warning{
			box-sizing : content-box;
			margin-bottom      : 20px;
		}

		-->
	</style>

	<script src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script src="/assets/js/jquery/jquery.form.js"></script>
	<script src="/assets/js/app.extended.js"></script>
</head>
<body>

<div class="logotype text-center mt10 mb20">
	<img src="/assets/images/logo.png" height="30">
</div>

<div class="variants wp80">

	<div class="variant to-install text-center">
		<div class="fs-16 Bold green uppercase">Установка</div>
		<div class="">
			Используется для новой установки системы
			<?php

			if ( file_exists( $filename ) ) {

				print '
					<div class="warning mt20 p20 text-left height">
						<p class="red fs-12 uppercase Bold mb20"><i class="icon-attention red"></i> Ошибка</p>
						<p>Имеется Конфигурационный файл (<b class="red">/inc/config.php</b>).</p> 
						<p>Сначала удалите его (не забывайте сделать резервную копию файла).</p>
					</div>
					';

				$ibutton = '<a href="#" class="button graybtn" title="Установить">Установить</a>';

				$toinstall = false;

			}
			else {

				print '
				<div class="success mt20 p20 text-left height">
					<p class="green fs-12 uppercase Bold mb20"><i class="icon-ok-circled green"></i> Готово к установке</p>
					<ul class="">
						<li>Версия дистрибутива - '.$ver[ 'version' ].' build '.$ver[ 'build' ].'</li>
						<li>Версия на сервере - '.$actual[ 'version' ].' build '.$actual[ 'build' ].'</li>
					</ul>
					<p>Можно переходить к установке</p>
				</div>
				';

				$ibutton = '<a href="install.php" class="button greenbtn" title="Установить">Установить</a>';

			}
			?>
		</div>
		<div>
			<?php echo $ibutton; ?>
		</div>
	</div>
	<div class="variant to-update text-center">
		<div class="fs-16 Bold blue uppercase">Обновление</div>
		<div class="">
			Используется для обновления системы
			<?php
			if ( file_exists( $filename ) ) {

				require_once $rootpath."/vendor/autoload.php";
				require_once $rootpath."/inc/config.php";
				require_once $rootpath."/inc/dbconnector.php";
				require_once $rootpath."/inc/func.php";
				require_once $rootpath."/inc/settings.php";

				$current = $db -> getRow( "SELECT * FROM ".$sqlname."ver ORDER BY id DESC LIMIT 1" );

				//$current[ 'current' ] = '2018.1';

				if ( $ver[ 'build' ] >= $actual[ 'build' ] && !$toinstall ) {

					if ( $ver[ 'version' ] != $current[ 'current' ] ) {

						print '
						<div class="attention mt20 p20 text-left height">
							<p class="broun fs-12 uppercase Bold mb20"><i class="icon-info-circled broun"></i> Готово обновление</p>
							<ul class="">
								<li>Ваша версия - '.$current[ 'current' ].'</li>
								<li>Версия дистрибутива - '.$ver[ 'version' ].' build '.$ver[ 'build' ].'</li>
								<li>Версия на сервере - '.$actual[ 'version' ].' build '.$actual[ 'build' ].'</li>
							</ul>
							<p>Можно переходить к обновлению</p>
						</div>
						';

						$button = '<a href="update.php" class="button redbtn" title="Обновить">Обновить</a>';

					}
					else {

						print '
						<div class="success mt20 p20 text-left height">
							<p class="green fs-12 uppercase Bold mb20"><i class="icon-ok green"></i> Обновлений нет</p>
							<ul class="">
								<li>Ваша версия - '.$current[ 'current' ].'</li>
								<li>Версия дистрибутива - '.$ver[ 'version' ].' build '.$ver[ 'build' ].'</li>
								<li>Версия на сервере - '.$actual[ 'version' ].' build '.$actual[ 'build' ].'</li>
							</ul>
							<p>Можно приступать к работе</p>
						</div>
						';

						$button = '<a href="/desktop" class="button greenbtn" title="На Рабочий стол">На Рабочий стол</a>';

					}

				}
				elseif ( !$toinstall ) {

					print '
					<div class="attention mt20 p20 text-left height">
						<p class="broun fs-12 uppercase Bold mb20"><i class="icon-attention red"></i> Доступно обновление</p>
						<ul class="">
							<li>Ваша версия - '.$current[ 'current' ].'</li>
							<li>Версия дистрибутива - '.$ver[ 'version' ].' build '.$ver[ 'build' ].'</li>
							<li>Версия на сервере - '.$actual[ 'version' ].' build '.$actual[ 'build' ].'</li>
						</ul>
					</div>
					';

					$button = '<a href="https://salesman.pro/download/" class="button brounbtn" title="Скачать обновление">Скачать обновление</a>';

				}
				else {

					print '
						<div class="warning mt20 p20 text-left height">
							<p class="red fs-12 uppercase Bold mb20"><i class="icon-attention red"></i> Система не установлена</p>
							<p>Сначала установите систему</p>
						</div>
						';

				}

			}
			else {

				print '
					<div class="warning mt20 p20 text-left height">
						<p class="red fs-12 uppercase Bold mb20"><i class="icon-attention red"></i> Система не установлена</p>
						<p>Сначала установите систему</p>
					</div>
					';

			}
			?>
		</div>
		<div>
			<?php echo $button; ?>
		</div>
	</div>

</div>

</body>
</html>