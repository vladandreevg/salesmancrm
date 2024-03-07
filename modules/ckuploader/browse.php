<?php
/**
 * Загрузчик изображений
 */

error_reporting( E_ERROR );

$rootpath = realpath( __DIR__.'/../../' );

require_once $rootpath."/vendor/autoload.php";

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/func.php";

$type    = $_REQUEST['type'];
$action  = $_REQUEST['action'];
$funcNum = $_GET['CKEditorFuncNum'];

$path = $rootpath.'/files/';

if ( $type == 'ymail' ) {//для почтовика

	$path .= $fpath.'ymail/inbody/';
	$url  = 'files/'.$fpath.'ymail/inbody/';

}
elseif ( $type == 'kb' ) {//для базы знаний

	$path .= $fpath.'kb/';
	$url  = 'files/'.$fpath.'kb/';

}
elseif ( $type == 'comments' ) {//для базы знаний

	$path .= $fpath.'comments/';
	$url  = 'files/'.$fpath.'comments/';

}

//проверяем папку для загрузки и если её нет, то создаем
if ( !file_exists( $path ) ) {

	mkdir( $path, 0777 );
	chmod( $path, 0777 );

}

if ( $action == 'upload' ) {

	if ( filesize( $_FILES['upload']['tmp_name'] ) > 0 and $_FILES['upload']['name'] != '' ) {

		$ftitle = basename( $_FILES['upload']['name'] );
		$tim    = time();
		$fname  = $tim.".".getExtention($ftitle);

		$ext_allow = [
			'PNG',
			'JPEG',
			'JPG',
			'GIF'
		];

		$uploadfile = $uploaddir.$fname;

		$cur_ext = strtoupper( getExtention($ftitle) );

		if ( in_array( $cur_ext, $ext_allow ) ) {

			if ( (filesize( $_FILES['upload']['tmp_name'] ) / 1000000) > 1000000 ) {

				$message = 'Ошибка: Превышает размеры!';
				$url     = '';

			}
			else {
				if ( move_uploaded_file( $_FILES['upload']['tmp_name'], $uploadfile ) ) {

					$url = $url.$fname;

				}
				else {

					$message = 'Ошибка:'.$_FILES['upload']['error'];
					$url     = '';

				}
			}

		}
		else {

			$message = 'Ошибка: Загружайте только изображения PNG, JPEG, JPG, GIF!';
			$url     = '';

		}

	}

	$funcNum = $_GET['CKEditorFuncNum'];

	?>
	<script type="text/javascript">
		window.parent.CKEDITOR.tools.callFunction(<?=$funcNum?>, '<?=$url?>', '<?=$message?>');
	</script>
	<?php

	exit();

}

if ( $action == 'list' ) {

	$files  = scandir( $path, 1 );
	$list   = [];
	$page   = (int)$_REQUEST['page'];
	$length = 20;

	foreach ( $files as $file ) {

		$ext = getExtention( $file );

		if ( in_array( $ext, [
			"png",
			"gif",
			"jpg",
			"jpeg"
		] ) ) {

			$size = getimagesize( $path.$file );

			if ( $size[0] > 100 )
				$list[] = [
					"name"  => $file,
					"time"  => filemtime( $path.'/'.$file ),
					"date"  => date( "d-m-Y", filemtime( $path.'/'.$file ) ),
					"size"  => num_format( round( filesize( $path.'/'.$file ) / 1024, 2 ) ),
					"width" => $size[0]
				];

		}

	}

	function ccmp($a, $b) {
		return $b['time'] > $a['time'];
	}

	usort( $list, 'ccmp' );

	//print_r($list);

	$total = count( $list ) / $length + 0.5;

	$list = array_slice( $list, $page * $length, $length );

	//print_r($list);

	print json_encode( [
		"list"  => $list,
		"total" => round($total, 0)
	] );

	exit();

}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<title>Выбор изображения SalesMan CRM</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
	<script type="text/javascript" src="/assets/js/jquery/jquery-3.4.1.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-migrate-3.0.0.min.js"></script>
	<script type="text/javascript" src="/assets/js/jquery/jquery-ui.min.js?v=2018.9"></script>
	<script type="text/javascript" src="/assets/js/mustache/mustache.js"></script>
	<script type="text/javascript" src="/assets/js/mustache/jquery.mustache.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/css/app.css">
	<link type="text/css" rel="stylesheet" href="/assets/css/fontello.css">

	<!--красивые алерты-->
	<script type="text/javascript" src="/assets/js/sweet-alert2/sweetalert2.min.js"></script>
	<link type="text/css" rel="stylesheet" href="/assets/js/sweet-alert2/sweetalert2.min.css">

	<style>

		body {
			height: 100%;
			overflow-y: auto;
			overflow-x: hidden;
		}

		.ymImagePreview {
			display               : grid;
			grid-template-columns : 1fr 1fr 1fr 1fr;
			grid-template-rows    : minmax(100px, auto);
			grid-gap              : 5px 5px;
			overflow-x            : hidden;
		}

		.ymImagePreview .picpreview {
			/*display         : inline-table;*/
			box-sizing      : border-box;
			margin          : 3px;
			padding         : 3px;
			height          : 150px;
			background-size : cover !important;
			border          : 2px solid #37474F;
			border-radius   : 5px;
		}

		.ymImagePreview .picpreview span {
			word-break  : break-all !important;
			background  : rgba(0, 0, 0, 0.5);
			color       : #fff;
			padding     : 3px 10px;
			margin-left : 10px;
			box-sizing  : border-box;
		}

		.pages{
			position : fixed;
			bottom: 0;
			width: 100%;
			z-index : 10;
			background: var(--gray-darkblue);
			color: var(--white);
			padding: 10px;
		}
		.pages a{
			color: var(--white);
		}

	</style>

</head>
<body>

<div class="ymImagePreview p10">
	<img src="/assets/images/Services.svg" width="40px">
</div>
<div class="pages"></div>

<div id="imgTpl" type="x-tmpl-mustache" class="hidden">

	{{#list}}
	<div style="background: url('/{{url}}{{name}}') no-repeat center center;" class="picpreview hand relativ" onclick="selectImage('/{{url}}{{name}}')" title="Выбрать">
		<span class="fs-07 bottom">{{date}} [ {{size}} kb ]</span>
	</div>
	{{/list}}

	<div class="space-80"></div>

</div>

<script>

	let url = '<?=$url?>';

	$(document).ready(function () {

		loadImges();

	});

	function selectImage(url) {

		window.opener.CKEDITOR.tools.callFunction(<?=$funcNum?>, url, '');
		self.close();

	}

	async function loadImges(page) {

		if(!page)
			page = 0;

		page = parseInt(page);

		await fetch('browse.php?action=list&type=<?=$type?>&page=' + page)
			.then(response => response.json())
			.then(viewData => {

				viewData.url = url;

				let template = $('#imgTpl').html();
				Mustache.parse(template);

				let rendered = Mustache.render(template, viewData);

				$('.ymImagePreview').html(rendered);

				var pg;
				var pageall = parseInt(viewData.total);
				var st = '';

				if (pageall > 1) {

					var prev = page - 1;
					var next = page + 1;

					page = page + 1;

					if (page === 1)
						st = st + '&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

					else if (page === pageall)
						st = st + '&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

					else
						st = '&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="loadImges(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

				}
				else page = 1;

				pg = 'Страница ' + page + ' из ' + pageall + st;

				$('.pages').html(pg);

			})
			.catch(error => {

				Swal.fire({
					title: 'Ошибка',
					text: error,
					type: 'error',
					showCancelButton: true
				});

			});

	}

</script>

</body>
</html>