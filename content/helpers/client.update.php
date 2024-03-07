<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST[ 'action' ];

if ( $action == "import_on" ) {

	$tip = $_REQUEST[ 'tip' ];
	$mes = '';

	//разбираем запрос из файла
	$ftitle = basename( $_FILES[ 'file' ][ 'name' ] );
	$fname  = time().".".getExtention( $ftitle );//переименуем файл
	$ftype  = $_FILES[ 'file' ][ 'type' ];

	$maxupload      = str_replace( [
		'M',
		'm'
	], '', @ini_get( 'upload_max_filesize' ) );
	$uploaddir      = $rootpath.'/files/'.$fpath;
	$uploadfile     = $uploaddir.$fname;
	$file_ext_allow = [
		'xls',
		'csv'
	];
	$cur_ext        = texttosmall( getExtention( $ftitle ) );

	if ( !$ftitle ) {
		print 'Не выбран файл';
		exit();
	}

	//проверим тип файла на поддерживаемые типы
	if ( in_array( $cur_ext, $file_ext_allow ) ) {
		
		if ( ( filesize( $_FILES[ 'file' ][ 'tmp_name' ] ) / 1000000 ) > $maxupload ) {
			$mess .= 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b>!<br /> <b class="yelw">Ошибка:</b> Превышает допустимые размеры!<br />';
			$cls  = "bad";
		}
		else {
			
			if ( move_uploaded_file( $_FILES[ 'file' ][ 'tmp_name' ], $uploadfile ) ) {
				
				//обрабатываем данные из файла
				/*
				if ( $cur_ext == 'csv' ) {

					$uploadfile = fopen( $url, 'rb' );
					while ( ( $data = fgetcsv( $uploadfile, 1000, ";" ) ) !== false ) {
						$datas[] = implode( ";", $data );
					}

				}
				if ( $cur_ext == 'xls' ) {

					$data = new Spreadsheet_Excel_Reader();
					$data -> setOutputEncoding( 'UTF-8' );
					$data -> read( $uploadfile, false );
					$datasd = $data -> dumptoarray();//получили двумерный массив с данными

					$k = 0;
					for ( $i = 2, $iMax = count( $datasd ); $i <= $iMax; $i++ ) {
						
						$g = 0;
						for ( $j = 1, $jMax = count( $datasd[ $i ] ); $j <= $jMax; $j++ ) {

							$datas[ $k ][ $g ] = $datasd[ $i ][ $j ];
							$g++;
						}
						$k++;
						
					}
				}
				*/

				$datas = parceExcel($uploadfile);

				//конец загрузки из поля
				unlink( $uploadfile );
				
			}
			else {
				$mess .= 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - '.$_FILES[ 'file' ][ 'error' ].'<br />';
				$cls  = "bad";
			}

		}
		
	}
	else {
		$mess .= 'Ошибка при загрузке файла <b>"'.$ftitle.'"</b> - Файлы такого типа не разрешено загружать.';
		$cls  = "bad";
	}

	//print $mess;
	//print_r($datas);
	//exit();

	if ( $tip == 'client' ) {

		//Здесь операции над файлом
		foreach ($datas as $d) {

			$title     = enc_detect( clean( $d[ 0 ] ) );
			$phone     = enc_detect( clean( $d[ 1 ] ) );
			$address   = enc_detect( clean( $d[ 2 ] ) );
			$territory = enc_detect( clean( $d[ 3 ] ) );

			//разбираем массив в текущей строке
			if ( $title != '' ) { //убеждаемся, что строка содержит позицию

				$clid = $db -> getOne( "select clid from ".$sqlname."clientcat where title='".enc_detect( $title )."' and identity = '$identity'" );

				if ( $clid > 0 ) {

					$s = 0;
					$p = [];

					if ( $phone != '' ) {
						$p[] = "phone = '".enc_detect( $phone )."'";
						$s++;
					}
					if ( $address != '' ) {
						$p[] = "address = '".enc_detect( $address )."'";
						$s++;
					}
					if ( $territory != '' ) {

						$idter = $db -> getOne( "SELECT idcategory FROM ".$sqlname."territory_cat WHERE title = '".enc_detect( $territory )."' and identity = '$identity'" );


						if ( $idter < 1 ) {

							$db -> query( "insert into ".$sqlname."territory_cat (`idcategory`, `title`, `identity`) values(null, '".enc_detect( $territory )."','$identity')" );
							$idter = $db -> insertId();

						}

						$p[] = "territory = '".$idter."'";
						$s++;

					}

					$pp = implode( ",", $p );

					if ( $s > 0 ) {

						try {

							$db -> query( "update ".$sqlname."clientcat set ".$pp." WHERE clid='".$clid."' and identity = '$identity'" );
							$up++;

						}
						catch ( Exception $e ) {

							$err++;

						}

					}

					$s     = 0;
					$p     = [];
					$idter = 0;

				}

			}
		}

	}

	if ( $tip == 'person' ) {

		//Здесь операции над файлом
		foreach ($datas as $d) {

			$person = enc_detect( clean( $d[ 0 ] ) );
			$mail   = enc_detect( clean( $d[ 1 ] ) );
			$tel    = enc_detect( clean( $d[ 2 ] ) );
			$mob    = enc_detect( clean( $d[ 3 ] ) );

			//разбираем массив в текущей строке
			if ( $person != '' ) { //убеждаемся, что строка содержит позицию

				$pid = $db -> getOne( "select pid from ".$sqlname."personcat where person='".enc_detect( $person )."' and identity = '$identity'" );

				if ( $pid > 0 ) {

					$s = 0;
					$p = [];

					if ( $tel != '' ) {
						$p[] = "tel = '".$tel."'";
						$s++;
					}
					if ( $mail != '' ) {
						$p[] = "mail = '".$mail."'";
						$s++;
					}
					if ( $mob != '' ) {
						$p[] = "mob = '".$mob."'";
						$ss++;
					}

					$pp = implode( ",", $p );

					if ( $s > 0 ) {
						try {

							$db -> query( "update ".$sqlname."personcat set ".$pp." WHERE pid='".$pid."' and identity = '$identity'" );
							$up++;

						}
						catch ( Exception $e ) {

							$err++;

						}

					}

					$s = 0;
					$p = [];

				}

			}
		}

	}

	if ( $err < 1 ) {
		print "Выполнено.<br> Обработано <b>".$up."</b> позиций.<br> Ошибок: нет";
	}
	else {
		print "Выполнено с ошибками.<br> обработано <b>".$up."</b> позиций.<br>Ошибок: ".$err;
	}

	exit();
	
}
if ( $action == "import" ) {
	?>
	<FORM action="/content/helpers/client.update.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="import_on">
		<DIV class="zagolovok">Обновление записей</DIV>
		<TABLE width="100%" border="0" cellpadding="2" cellspacing="3">
			<TR>
				<TD class="text-right w150"><B>Из файла:</B></TD>
				<TD><input name="file" type="file" class="file" id="file" class="required" style="width:98%"/></TD>
			</TR>
			<TR>
				<TD class="text-right"><B>Что обновляем:</B></TD>
				<TD>
					<select name="tip" id="tip" style="width: 150px;">
						<option value="client">Клиентов</option>
						<option value="person">Контакты</option>
					</select>
				</TD>
			</TR>
		</TABLE>

		<div class="infodiv">
			Этот скрипт может обновить существующие данные по Клиентам или по Контактам.
			<hr>
			Подготовьте таблицу с колонками и их порядком, строго соответствующими примеру:
			<ul>
				<li>для Клиентов - Название клиента | Телефон | Адрес | Территория</li>
				<li>для Контактов - ФИО | Email | Телефон | Мобильный</li>
			</ul>
			Сопоставление осуществляется по Названию (для Клиентов) или ФИО (для Контактов). Пустые поля игнорируются.
			<hr>
			Поддерживаются форматы CSV, XLS. <!--Вы можете загрузить <a href="../example/price.xls" class="red"><b>пример</b></a>-->
			<br>
		</div>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Импорт</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
		</div>
	</FORM>
	<?php
}
?>
<script>
	$(function () {

		$('#dialog').css('width', '700px').center();

		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				configpage();

			}
		});

	});
</script>