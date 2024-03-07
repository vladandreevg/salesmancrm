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

$type = $_REQUEST[ 'type' ];

if ( $type == 'ymail' ) {//для почтовика

	$uploaddir = $rootpath.'/files/'.$fpath.'ymail/inbody/';
	$url       = '/files/'.$fpath.'ymail/inbody/';

}
elseif ( $type == 'kb' ) {//для базы знаний

	$uploaddir = $rootpath.'/files/'.$fpath.'kb/';
	$url       = '/files/'.$fpath.'kb/';

}

//проверяем папку для загрузки и если её нет, то создаем
if ( !file_exists( $uploaddir ) ) {
	mkdir( $uploaddir, 0777 );
	chmod( $uploaddir, 0777 );
}

//загрузка новых файлов

if ( filesize( $_FILES[ 'upload' ][ 'tmp_name' ] ) > 0 and $_FILES[ 'upload' ][ 'name' ] != '' ) {

	$ftitle = basename( $_FILES[ 'upload' ][ 'name' ] );
	$tim    = time();
	$fname  = $tim.".".getExtention($ftitle );

	$ext_allow = [
		'PNG',
		'JPEG',
		'JPG',
		'GIF'
	];

	$uploadfile = $uploaddir.$fname;

	$cur_ext = strtoupper( getExtention($ftitle) );

	if ( in_array( $cur_ext, $ext_allow ) ) {

		if ( ( filesize( $_FILES[ 'upload' ][ 'tmp_name' ] ) / 1000000 ) > 1000000 ) {

			$message = 'Ошибка: Превышает размеры!';
			$url     = '';

		}
		else {
			if ( move_uploaded_file( $_FILES[ 'upload' ][ 'tmp_name' ], $uploadfile ) ) {

				$url = $url.$fname;

			}
			else {

				$message = 'Ошибка:'.$_FILES[ 'upload' ][ 'error' ];
				$url     = '';

			}
		}

	}
	else {

		$message = 'Ошибка: Загружайте только изображения PNG, JPEG, JPG, GIF!';
		$url     = '';

	}

}

$funcNum = $_GET[ 'CKEditorFuncNum' ];
?>
<script type="text/javascript">
	window.parent.CKEDITOR.tools.callFunction(<?php echo $funcNum; ?>, '<?php echo $url; ?>', '<?php echo $message;?>');
</script>