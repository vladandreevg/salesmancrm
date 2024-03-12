<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

ini_set( 'memory_limit', '-1' );

error_reporting( E_ERROR );

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

require_once $rootpath."/developer/events.php";

$helper = json_decode( file_get_contents( $rootpath.'/cash/helper.json' ), true );

$action = $_REQUEST[ 'action' ];

if ( $action == "discard" ) {

	$url = $rootpath.'/files/'.$fpath.$_COOKIE[ 'url' ];
	setcookie( "url", '' );
	unlink( $url );

}
if ( $action == "import" ) {
	?>
	<DIV class="zagolovok">Импорт клиентов в базу. Шаг 1.</DIV>
	<form action="/content/helpers/client.import.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" name="action" id="action" value="upload">

		<div class="infodiv div-center pad5 margbot5">
			<TABLE>
				<TR>
					<TD width="150" align="right"><B>Из файла:</B></TD>
					<TD><input name="file" type="file" class="file wp95" id="file"></TD>
				</TR>
			</TABLE>
		</div>

		<div class="div-center">
			<b>Импортируйте существующую базу клиентов</b> в CRM. Вы можете использовать файлы в формате XLSX, XLS, CSV.
			<br/>Посмотреть
			<a href="/developer/example/clients.xls" target="_blank" style="color:red">пример файла</a> или
			<a href="<?= $productInfo[ 'site' ] ?>/docs/47" target="blank"><i class="icon-help-circled blue"></i><b class="blue">пошаговую инструкцию</b></a>.
			<hr>
			<iframe width="640" height="360" src="https://www.youtube.com/embed/quFtEe8Ihh8" frameborder="0" allowfullscreen></iframe>
		</div>

		<hr>

		<div align="center">

			<A href="javascript:void(0)" onClick="Next()" class="button graybtn next">Далее...</A>&nbsp;
			<A href="javascript:void(0)" onClick="Discard()" class="button">Закрыть</A>

		</div>

	</FORM>
	<?php
}

if ( $action == "upload" ) {

	//проверяем расширение файла. Оно д.б. только csv
	$cur_ext = getExtention( $_FILES[ 'file' ][ 'name' ] );
	if ( !in_array( $cur_ext, [
		'csv',
		'xls',
		'xlsx'
	] ) ) {

		print 'Ошибка при загрузке файла <b>"'.basename( $_FILES[ 'file' ][ 'name' ] ).'"</b>!<br>
		<b class="yelw">Ошибка:</b> Недопустимый формат файла. <br>Допускаются только файлы в формате <b>CSV</b>, <b>XLSX</b> или <b>XLS</b>';

	}
	else {

		$url = $rootpath.'/files/'.$fpath.'import'.$iduser1.time().".".$cur_ext;

		//Сначала загрузим файл на сервер
		if ( move_uploaded_file( $_FILES[ 'file' ][ 'tmp_name' ], $url ) ) {

			setcookie( "url", 'import'.$iduser1.time().".".$cur_ext, time() + 86400 );
			print 'Файл загружен';

		}
		else {
			print 'Ошибка при загрузке файла <b>"'.$_FILES['file']['name'].'"</b>!<br /><b class="yelw">Ошибка:</b> '.$_FILES['file']['error'].'<br />';
		}

	}

	exit();

}
if ( $action == "select" ) {

	$result = $db -> query( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
	while ( $data = $db -> fetch( $result ) ) {
		$fieldClient[ $data[ 'fld_name' ] ] = $data[ 'fld_title' ];
	}

	$url     = $rootpath.'/files/'.$fpath.$_COOKIE[ 'url' ];
	$cur_ext = getExtention( $_COOKIE[ 'url' ] );

	if ( $cur_ext == 'xls' ) {

		//require_once '../../opensource/excel_reader/excel_reader2.php';

		$datas = new Spreadsheet_Excel_Reader();
		$datas -> setOutputEncoding( 'UTF-8' );
		$datas -> read( $url, false );
		$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

		//print_r($data1);
		//exit();

		for ( $j = 0; $j < 2; $j++ ) {

			for ( $g = 0; $g < count( $data1[ $j + 1 ] ); $g++ ) {

				$data[ $j ][] = untag( $data1[ $j + 1 ][ $g + 1 ] );

			}

		}

	}
	if ( $cur_ext == 'csv' || $cur_ext == 'xlsx' ) {

		$datas = new SpreadsheetReader( $url );
		$datas -> ChangeSheet( 0 );

		foreach ( $datas as $k => $Row ) {

			if ( $k < 3 ) {

				foreach ( $Row as $key => $value ) {

					$data[ $k ][] = ( $cur_ext == 'csv' ) ? enc_detect( untag( $value ) ) : untag( $value );

				}

			}
			else goto p;

		}

		p:
		$data = array_values( $data );

	}

	//выводим поля для выбора и ассоциации с данными

	if ( file_exists( $rootpath.'/cash/'.$fpath.'requisites.json' ) ) {

		$file     = file_get_contents( $rootpath.'/cash/'.$fpath.'requisites.json' );
		$recvName = json_decode( $file, true );

	}
	else {

		$file     = file_get_contents( $rootpath.'/cash/requisites.json' );
		$recvName = json_decode( $file, true );

	}
	?>
	<DIV class="zagolovok">Импорт клиентов в базу. Шаг 2.</DIV>

	<form action="/content/helpers/client.import.php" method="post" enctype="multipart/form-data" name="Form2" id="Form2">
		<input type="hidden" name="action" id="action" value="import_on">

		<div id="formtabs">

			<div class="flex-container">

				<div class="flex-string wp30 p5">

					<div class="fs-07 gray uppercase Bold">Назначить:</div>
					<select name="new_user" id="new_user" class="wp100">
						<option selected="selected" value="<?= $iduser1 ?>" class="greenbg-sub">На себя</option>
						<option value="0">В холодные организации</option>
						<optgroup label="Сотруднику"></optgroup>
						<?php
						$result = $db -> query( "SELECT * FROM ".$sqlname."user WHERE identity = '$identity' ORDER by title ".$userlim );
						while ( $da = $db -> fetch( $result ) ) {
							print '<option value="'.$da[ 'iduser' ].'">'.$da[ 'title' ].'</option>';
						}
						?>
					</select>

				</div>
				<div class="flex-string wp30 p5">

					<div class="fs-07 gray uppercase Bold">Тип клиента:</div>
					<SELECT name="ctype" id="ctype" class="wp100">
						<OPTION value="client" <?php if ( !$otherSettings[ 'clientIsPerson'] ) print "selected" ?>>Клиент. Юр.лицо</OPTION>
						<OPTION value="person" <?php if ( $otherSettings[ 'clientIsPerson'] ) print "selected" ?>>Клиент. Физ.лицо</OPTION>
						<OPTION value="concurent">Конкурент</OPTION>
						<OPTION value="contractor">Поставщик</OPTION>
						<OPTION value="partner">Партнер</OPTION>
					</SELECT>

				</div>
				<div class="flex-string wp30 p5 relativ">

					<div class="fs-07 gray uppercase Bold">Источник списка:</div>
					<select name="clientpath" id="clientpath" class="<?= ( !$otherSettings[ 'guidesEdit'] ? 'wp85' : 'wp100' ) ?>">
						<option selected="selected" value="">--Не указывать--</option>
						<?php
						$result = $db -> query( "SELECT * FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER by name" );
						while ( $da = $db -> fetch( $result ) ) {
							print '<option value="'.$da[ 'id' ].'">'.$da[ 'name' ].'</option>';
						}
						?>
					</select>
					<?php if ( !$otherSettings[ 'guidesEdit'] ) { ?>
						&nbsp;
						<a href="javascript:void(0)" onclick="add_sprav('clientpath','clientpath')" title="Добавить" class="paddtop5"><i class="icon-plus-circled red"></i></a>
					<?php } ?>

				</div>

			</div>

		</div>

		<hr>

		<table id="zebra">
			<thead>
			<tr class="header_contaner noDrag">
				<TH width="200" height="35" align="left" class="nodrop">Название поля в БД</TH>
				<TH width="250" height="35" align="left" class="nodrop">Название поля из файла</TH>
				<TH align="left" class="nodrop">Образец из файла</TH>
			</tr>
			</thead>
		</table>
		<DIV class="bgwhite" style="height:43vh; overflow:auto">

			<table id="zebra">
				<?php
				foreach ( $data[ 0 ] as $i => $item ) {
					?>
					<tr class="ha">
						<td width="200">
							<select id="field[]" name="field[]" class="required" style="width:100%">
								<option value="">--Выбор--</option>
								<optgroup label="Клиент">
									<?php if ( $fieldClient[ 'idcategory' ] ) { ?>
										<option value="otrasl:title" <?php if ( $item == $fieldClient[ 'idcategory' ] ) print "selected"; ?>>Клиент: <?= $fieldClient[ 'idcategory' ] ?></option>
									<?php } ?>
									<?php if ( $fieldClient[ 'territory' ] ) { ?>
										<option value="territory:title" <?php if ( $item == $fieldClient[ 'territory' ] ) print "selected"; ?>>Клиент: <?= $fieldClient[ 'territory' ] ?></option>
									<?php } ?>
									<?php if ( $fieldClient[ 'clientpath' ] ) { ?>
										<option value="clientpath:title" <?php if ( $item == $fieldClient[ 'clientpath' ] ) print "selected"; ?>>Клиент: <?= $fieldClient[ 'clientpath' ] ?></option>
									<?php } ?>
									<?php if ( $fieldClient[ 'tip_cmr' ] ) { ?>
										<option value="relations:title" <?php if ( $item == $fieldClient[ 'tip_cmr' ] ) print "selected"; ?>>Клиент: <?= $fieldClient[ 'tip_cmr' ] ?></option>
									<?php } ?>
									<option value="date:dateCreate">Клиент: Дата создания</option>
									<option value="client:uid" <?php if ( $item == 'UID' ) print "selected"; ?>>Клиент: UID</option>
									<?php
									$exclude = [
										'clid',
										'pid',
										'idcategory',
										'iduser',
										'territory',
										'loyalty',
										'relations',
										'tip_cmr',
										'head_clid',
										'datum',
										'clientpath',
										'recv'
									];
									$result  = $db -> query( "select * from ".$sqlname."field where fld_on='yes' and fld_tip='client' and identity = '$identity' order by fld_tip, fld_order" );
									while ( $da = $db -> fetch( $result ) ) {

										if ( !in_array( $da[ 'fld_name' ], (array)$exclude ) ) {

											if ( $da[ 'fld_tip' ] == 'client' ) {

												$s1 = 'client:';
												$s2 = 'Клиент: ';

											}
											else {

												$s1 = 'person:';
												$s2 = 'Персона: ';

											}

											$s3 = ( $item == $da[ 'fld_title' ] ) ? " selected" : '';

											print '<option value="'.$s1.$da[ 'fld_name' ].'" '.$s3.'>'.$s2.$da[ 'fld_title' ].'</option>';

										}

									}
									?>
								</optgroup>
								<?php if ( $fieldClient[ 'recv' ] ) { ?>
									<optgroup label="Клиент. Реквизиты">
										<option value="recvizit:CastNameUr" <?php if ( $item == 'Юр.название' ) print "selected"; ?>>Реквизиты: Юр.название</option>
										<option value="recvizit:castInn" <?php if ( $item == $recvName[ 'castInn' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvInn' ] ?></option>
										<option value="recvizit:castKpp" <?php if ( $item == $recvName[ 'castKpp' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvKpp' ] ?></option>
										<option value="recvizit:castOgrn" <?php if ( $item == $recvName[ 'castOgrn' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvOgrn' ] ?></option>
										<option value="recvizit:castUrAddr" <?php if ( $item == $recvName[ 'castUrAddr' ] ) print "selected"; ?>>Реквизиты: Юр.адрес</option>
										<option value="recvizit:castBankRs" <?php if ( $item == $recvName[ 'recvBankRs' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvBankRs' ] ?></option>
										<option value="recvizit:castBankName" <?php if ( $item == $recvName[ 'recvBankName' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvBankName' ] ?></option>
										<option value="recvizit:castBankBik" <?php if ( $item == $recvName[ 'recvBankBik' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvBankBik' ] ?></option>
										<option value="recvizit:castBankKs" <?php if ( $item == $recvName[ 'recvBankKs' ] ) print "selected"; ?>>Реквизиты: <?= $recvName[ 'recvBankKs' ] ?></option>
										<option value="recvizit:castDirName" <?php if ( $item == 'Руководитель' ) print "selected"; ?>>Реквизиты: Имя Руководителя</option>
										<option value="recvizit:castDirStatus" <?php if ( $item == 'Должность' ) print "selected"; ?>>Реквизиты: Должность Руководителя</option>
										<option value="recvizit:castDirOsnovanie" <?php if ( $item == 'Основание' ) print "selected"; ?>>Реквизиты: Основание полномочий Руководителя</option>
									</optgroup>
								<?php } ?>
								<optgroup label="Контакт">
									<option value="personpath:title">Контакт: Источник</option>
									<?php
									$exclude = [
										'clid',
										'pid',
										'idcategory',
										'iduser',
										'territory',
										'loyalty',
										'head_clid',
										'datum',
										'clientpath',
										'recv'
									];
									$result  = $db -> query( "select * from ".$sqlname."field where fld_on='yes' and fld_tip='person' and identity = '$identity' order by fld_tip, fld_order" );
									while ( $da = $db -> fetch( $result ) ) {

										if ( !in_array( $da[ 'fld_name' ], (array)$exclude ) ) {

											if ( $da[ 'fld_tip' ] == 'client' ) {
												$s1 = 'client:';
												$s2 = 'Клиент: ';
											}
											else {
												$s1 = 'person:';
												$s2 = 'Контакт: ';
											}

											print '<option value="'.$s1.$da[ 'fld_name' ].'">'.$s2.$da[ 'fld_title' ].'</option>';

										}

									}
									?>
								</optgroup>
								<optgroup label="Активности">
									<option value="history:datum">Активности: Дата</option>
									<option value="history:tip">Активности: Тип</option>
									<option value="history:des">Активности: Содержание</option>
								</optgroup>
							</select>
						</td>
						<td width="250"><b><?= $data[ 0 ][ $i ] ?></b></td>
						<td>
							<div class="ellipsis"><?= $data[ 1 ][ $i ] ?></div>
						</td>
					</tr>
				<?php } ?>
			</table>

		</DIV>

	</FORM>

	<hr>

	<div align="center" class="success pad5">
		<p>Теперь Вам необходимо ассоциировать загруженные данные с БД системы. Подробнее в
			<a href="<?= $productInfo[ 'site' ] ?>/docs/47" target="blank">Документации</a></p>
		<p>
			<b>Важно:</b> Допускается импортировать не более 5000 записей за один раз. Во время импорта производится сопоставление импортируемых записей с существующими по полям "UID" (при наличии) или "<?= $fieldClient[ 'title' ] ?>", затем, при наличии, по полям "<?= $fieldClient[ 'phone' ] ?>", "<?= $fieldClient[ 'fax' ] ?>", "Email"
		</p>
	</div>

	<hr>

	<DIV class="button--pane text-right">

		<A href="javascript:void(0)" onClick="$('#Form2').submit()" class="button">Импортировать</A>&nbsp;
		<A href="javascript:void(0)" onClick="Discard()" class="button">Отмена</A>

	</DIV>
	<?php
}
if ( $action == "import_on" ) {

	//файл для расшифровки
	$url = $rootpath.'/files/'.$fpath.$_COOKIE[ 'url' ];

	$field      = $_REQUEST[ 'field' ]; //порядок полей
	$new_user   = $_REQUEST[ 'new_user' ];
	$clientpath = $_REQUEST[ 'clientpath' ];
	$ctype      = $_REQUEST[ 'ctype' ];

	$names  = [];
	$indexs = [];

	$trash = ( $new_user == '0' ) ? "yes" : "no";

	$date_create = current_datumtime();

	$cc = 0;
	$pp = 0;
	$z  = 0;

	$recv = [
		'CastNameUr',
		'castInn',
		'castKpp',
		'castBankName',
		'castBankKs',
		'castBankRs',
		'castBankBik',
		'castOkpo',
		'castOgrn',
		'castDirName',
		'castDirSignature',
		'castDirStatus',
		'castDirStatusSig',
		'castDirOsnovanie',
		'castUrAddr'
	];

	//составим массивы ассоциации данных по типам. $i - это номер колонки из таблицы.
	for ( $i = 0; $i < count( $field ); $i++ ) {

		$clients = [];

		if ( strstr( $field[ $i ], 'client' ) != false and strstr( $field[ $i ], 'clientpath' ) == false ) {

			$c = str_replace( "client:", "", $field[ $i ] );
			if ( $c == 'title' ) {
				$cc++; //индикатор наличия организации
				$clx = $i;
			}
			if ( $c == 'mail_url' ) {
				$clm = $i;
			}
			if ( $c == 'phone' ) {
				$clt = $i;
			}
			if ( $c == 'fax' ) {
				$clf = $i;
			}
			if ( $c == 'uid' ) {
				$cc++; //индикатор наличия организации
				$clu = $i;
			}
			//массив данных по клиенту
			$indexs[ 'client' ][]    = $i;//массив ключ поля -> номер столбца
			$names[ 'client' ][ $i ] = $c;//массив номер столбца -> индекс поля

			$clients[ $c ] = $i;

		}
		if ( strstr( $field[ $i ], 'person' ) !== false and strstr( $field[ $i ], 'personpath' ) === false ) {

			$c = str_replace( "person:", "", $field[ $i ] );

			if ( $c == 'person' ) {
				$pp++; //индикатор наличия персоны
				$plx = $i;
			}
			if ( $c == 'mail' ) {
				$plm = $i;
			}
			if ( $c == 'mob' ) {
				$plt = $i;
			}

			//массив данных по контакту
			$indexs[ 'person' ][]    = $i;
			$names[ 'person' ][ $i ] = $c;//массив номер столбца -> индекс поля

		}
		if ( strstr( $field[ $i ], 'recvizit' ) !== false ) {

			$c = str_replace( "recvizit:", "", $field[ $i ] );
			//массив
			$indexs[ 'recvizit' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'history' ) !== false ) {

			$c                         = str_replace( "history:", "", $field[ $i ] );
			$indexs[ 'history' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'otrasl' ) !== false ) {

			$c = str_replace( "otrasl:", "", $field[ $i ] );
			//массив
			$indexs[ 'otrasl' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'territory' ) !== false ) {

			$c = str_replace( "territory:", "", $field[ $i ] );
			//массив
			$indexs[ 'territory' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'relations' ) !== false ) {

			$c = str_replace( "relations:", "", $field[ $i ] );
			//массив
			$indexs[ 'relations' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'clientpath' ) !== false ) {

			$c = str_replace( "clientpath:", "", $field[ $i ] );
			//массив
			$indexs[ 'clientpath' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'personpath' ) !== false ) {

			$c = str_replace( "personpath:", "", $field[ $i ] );
			//массив
			$indexs[ 'personpath' ][ $c ] = $i;//массив ключ поля -> номер столбца

		}
		if ( strstr( $field[ $i ], 'dateCreate' ) !== false ) {

			$indexs[ 'date' ][ 'date_create' ] = $i;//массив ключ поля -> номер столбца

		}

	}

	$data = [];

	//считываем данные из файла в массив
	$cur_ext = texttosmall( getExtention(basename( $_COOKIE[ 'url' ] ) ) );

	$maxImport = 5001;

	if ( $cur_ext == 'csv' ) $maxImport = 5001;
	if ( $cur_ext == 'xls' ) $maxImport = 5001;

	if ( $cur_ext == 'xls' ) {

		//require_once '../../opensource/excel_reader/excel_reader2.php';

		$datas = new Spreadsheet_Excel_Reader();
		$datas -> setOutputEncoding( 'UTF-8' );
		$datas -> read( $url, false );
		$data1 = $datas -> dumptoarray();//получили двумерный массив с данными

		for ( $j = 0; $j < $maxImport; $j++ ) {

			for ( $g = 0; $g < count( $data1[ $j + 1 ] ); $g++ ) {

				$data[ $j ][] = untag( $data1[ $j + 1 ][ $g + 1 ] );

			}

		}

	}
	if ( $cur_ext == 'csv' || $cur_ext == 'xlsx' ) {

		//require_once '../../opensource/spreadsheet-reader-master/SpreadsheetReader.php';
		//require_once '../../opensource/spreadsheet-reader-master/php-excel-reader/excel_reader2.php';

		$datas = new SpreadsheetReader( $url );
		$datas -> ChangeSheet( 0 );

		foreach ( $datas as $k => $Row ) {

			if ( $k < $maxImport ) {

				foreach ( $Row as $key => $value ) {

					$data[ $k ][] = ( $cur_ext == 'csv' ) ? enc_detect( untag( $value ) ) : untag( $value );

				}

			}
			else goto p1;

		}

		p1:
		$data = array_values( $data );

	}

	$good  = 0;
	$good2 = 0;
	$good3 = 0;
	$err   = 0;
	$err2  = 0;
	$err3  = 0;

	$clids = [];
	$pids  = [];

	$date_create = [];

	$cpath = $db -> getCol( "SELECT name FROM ".$sqlname."clientpath WHERE identity = '$identity' ORDER BY name" );

	//импортируем данные из файла
	for ( $i = 1; $i < count( $data ); $i++ ) {

		$client = $person = [];

		$idcategory  = 0;
		$idterritory = 0;
		$idrelation  = '';
		$idcppath    = $clientpath + 0;
		$idpppath    = $clientpath + 0;
		$date_create = current_datumtime();

		$castName = $data[ $i ][ $indexs[ 'client' ][ 'title' ] ];

		//обработаем отрасль
		if ( $data[ $i ][ $indexs[ 'otrasl' ][ 'title' ] ] != '' ) {

			//Массив имеющихся в базе Отраслей
			$otrasli = $db -> getCol( "SELECT title FROM ".$sqlname."category WHERE identity = '$identity' ORDER BY title" );

			//сопоставляем id отрасли текущего клиента, если нет создаем.
			if ( in_array( $data[ $i ][ $indexs[ 'otrasl' ][ 'title' ] ], (array)$otrasli ) ) {

				//если такое название уже сужествует, то сопоставляем id
				$idcategory = $db -> getOne( "SELECT idcategory FROM ".$sqlname."category WHERE title = '".$data[ $i ][ $indexs[ 'otrasl' ][ 'title' ] ]."' and identity = '$identity'" );

			}
			else {

				$db -> query( "insert into ".$sqlname."category (`idcategory`, `title`, `identity`) values(null, '".untag( $data[ $i ][ $indexs[ 'otrasl' ][ 'title' ] ] )."','$identity')" );
				$idcategory = $db -> insertId();

			}

		}

		//обработаем территорию
		if ( $data[ $i ][ $indexs[ 'territory' ][ 'title' ] ] != '' ) {

			$territ = $db -> getCol( "SELECT title FROM ".$sqlname."territory_cat WHERE identity = '$identity' ORDER BY title" );

			//сопоставляем id территории текущего клиента, если нет создаем.
			if ( in_array( $data[ $i ][ $indexs[ 'territory' ][ 'title' ] ], (array)$territ ) ) {

				//если такое название уже сужествует, то сопоставляем id
				$idterritory = $db -> getOne( "SELECT idcategory FROM ".$sqlname."territory_cat WHERE title = '".$data[ $i ][ $indexs[ 'territory' ][ 'title' ] ]."' and identity = '$identity'" );

			}
			else {

				$db -> query( "insert into ".$sqlname."territory_cat (`idcategory`, `title`, `identity`) values(null, '".$data[ $i ][ $indexs[ 'territory' ][ 'title' ] ]."','$identity')" );
				$idterritory = $db -> insertId();

			}
		}

		//обработаем тип отношений
		if ( $data[ $i ][ $indexs[ 'relations' ][ 'title' ] ] != '' ) {

			$relations = $db -> getCol( "SELECT title FROM ".$sqlname."relations WHERE identity = '$identity' ORDER BY title" );

			//сопоставляем id территории текущего клиента, если нет создаем.
			if ( in_array( $data[ $i ][ $indexs[ 'relations' ][ 'title' ] ], (array)$relations ) ) {

				//если такое название уже сужествует, то сопоставляем id
				$idrelation = $db -> getOne( "SELECT title FROM ".$sqlname."relations WHERE title = '".$data[ $i ][ $indexs[ 'relations' ][ 'title' ] ]."' and identity = '$identity'" );

			}
			else {

				$db -> query( "insert into ".$sqlname."relations (id, title,identity) values(null, '".$data[ $i ][ $indexs[ 'relations' ][ 'title' ] ]."','$identity')" );
				$idrelation = $data[ $i ][ $indexs[ 'relations' ][ 'title' ] ];

			}

		}

		//обработаем дату создания
		if ( $data[ $i ][ $indexs[ 'date' ][ 'date_create' ] ] != '' )
			$date_create = $data[ $i ][ $indexs[ 'date' ][ 'date_create' ] ];

		//обработаем источник клиента, если не указан общий источник
		if ( !$clientpath ) {

			if ( $data[ $i ][ $indexs[ 'clientpath' ][ 'title' ] ] != '' ) {

				//сопоставляем id источника текущего клиента, если нет создаем.
				if ( in_array( $data[ $i ][ $indexs[ 'clientpath' ][ 'title' ] ], (array)$cpath ) ) {

					//если такое название уже сужествует, то сопоставляем id
					$idcppath = $db -> getOne( "SELECT id FROM ".$sqlname."clientpath WHERE name = '".$data[ $i ][ $indexs[ 'clientpath' ][ 'title' ] ]."' and identity = '$identity'" );

				}
				else {

					$db -> query( "insert into ".$sqlname."clientpath (`id`, `name`, `identity`) values(null, '".$data[ $i ][ $indexs[ 'clientpath' ][ 'title' ] ]."','$identity')" );
					$idcppath = $db -> insertId();

				}

			}

			$cpath = [];

		}

		//обработаем источник контакта, если не указан общий источник
		if ( !$clientpath ) {

			if ( $data[ $i ][ $indexs[ 'personpath' ][ 'title' ] ] != '' ) {

				//сопоставляем id территории текущего клиента, если нет создаем.
				if ( in_array( $data[ $i ][ $indexs[ 'personpath' ][ 'title' ] ], (array)$cpath ) ) {

					//если такое название уже сужествует, то сопоставляем id
					$idpppath = $db -> getOne( "SELECT id FROM ".$sqlname."clientpath WHERE name = '".$data[ $i ][ $indexs[ 'personpath' ][ 'title' ] ]."' and identity = '$identity'" );

				}
				else {

					$db -> query( "insert into ".$sqlname."clientpath (`id`, `name`, `identity`) values(null, '".$data[ $i ][ $indexs[ 'personpath' ][ 'title' ] ]."','$identity')" );
					$idpppath = $db -> insertId();

				}

			}

		}

		//если в данных есть клиент
		if ( $cc > 0 && ( $data[ $i ][ $clx ] != '' || $data[ $i ][ $clu ] != '' ) ) {

			//поищем клиента в базе
			$qr = '';

			if ( $data[ $i ][ $clu ] == '' )
				$qr .= " and title='".clientFormatTitle( untag( $data[ $i ][ $clx ] ) )."'";
			else
				$qr .= " and uid='".$data[ $i ][ $clu ]."'";

			if ( $data[ $i ][ $clm ] != '' )
				$qr .= "and mail_url LIKE '%".$data[ $i ][ $clm ]."%'";

			if ( $data[ $i ][ $clt ] != '' && $data[ $i ][ $clf ] == '' )
				$qr .= "and phone LIKE '%".$data[ $i ][ $clt ]."%'";

			elseif ( $data[ $i ][ $clt ] == '' && $data[ $i ][ $clf ] != '' )
				$qr .= "and fax LIKE '%".$data[ $i ][ $clf ]."%'";

			elseif ( $data[ $i ][ $clt ] != '' && $data[ $i ][ $clf ] != '' )
				$qr .= "and (phone LIKE '%".$data[ $i ][ $clt ]."%' or fax LIKE '%".$data[ $i ][ $clf ]."%')";

			$clid = $db -> getOne( "select clid from ".$sqlname."clientcat where clid > 0 $qr and identity = '$identity'" ) + 0;

			if ( $clid == 0 ) {

				$client = [
					"iduser"      => $new_user,
					"creator"     => $iduser1,
					"idcategory"  => $idcategory,
					"date_create" => $date_create,
					"trash"       => $trash,
					"clientpath"  => $idcppath,
					"territory"   => $idterritory,
					"tip_cmr"     => $idrelation,
					"type"        => $ctype,
					"identity"    => $identity
				];

				for ( $k = 0; $k < count( $indexs[ 'client' ] ); $k++ ) {

					$client[ $names[ 'client' ][ $indexs[ 'client' ][ $k ] ] ] = ( $k == $clx ) ? clientFormatTitle( $data[ $i ][ $indexs[ 'client' ][ $k ] ] ) : $data[ $i ][ $indexs[ 'client' ][ $k ] ];

				}

				try {

					$db -> query( "INSERT INTO ".$sqlname."clientcat SET ?u", arrayNullClean( $client ) );

					$good++;
					$clids[] = $db -> insertId();

					$clid = $db -> insertId();

				}
				catch ( Exception $e ) {

					$err++;
					$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

				}

			}

		}

		//добавим контакт,если он есть в данных
		if ( $pp > 0 && $data[ $i ][ $plx ] != '' ) {

			$qr = '';

			if ( $clid > 0 )
				$qr .= " and clid = '$clid'";

			if ( untag( $data[ $i ][ $plm ] ) != '' )
				$qr .= "and mail LIKE '%".$data[ $i ][ $plm ]."%'";

			if ( untag( $data[ $i ][ $plt ] ) != '' )
				$qr .= "and (tel LIKE '%".$data[ $i ][ $plt ]."%' OR mob LIKE '%".$data[ $i ][ $plt ]."%')";

			$result = $db -> getRow( "SELECT pid,clid FROM ".$sqlname."personcat WHERE person = '".$data[ $i ][ $plx ]."' $qr and identity = '$identity'" );
			if ( count( $result ) > 0 ) {

				$pid  = $result[ "pid" ];
				$clid = $result[ "clid" ];

				$db -> query( "UPDATE ".$sqlname."clientcat SET pid = '$pid' WHERE clid = '$clid' and identity = '$identity'" );

			}
			else {

				$person = [
					"clid"        => $clid,
					"iduser"      => $new_user,
					"creator"     => $iduser1,
					"date_create" => $date_create,
					"clientpath"  => $idpppath,
					"identity"    => $identity
				];


				for ( $k = 0; $k < count( $indexs[ 'person' ] ); $k++ )
					$person[ $names[ 'person' ][ $indexs[ 'person' ][ $k ] ] ] = $data[ $i ][ $indexs[ 'person' ][ $k ] ];


				try {

					$db -> query( "INSERT INTO ".$sqlname."personcat SET ?u", arrayNullClean( $person ) );
					$pids[] = $db -> insertId();
					$good2++;

					$pid = $db -> insertId();

				}
				catch ( Exception $e ) {

					$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();
					$err2++;

				}


				if ( $clid > 0 ) {

					//Добавим основной контакт к организации
					$db -> query( "UPDATE ".$sqlname."clientcat SET pid = '$pid' WHERE clid = '$clid' and identity = '$identity'" );

				}

			}

		}

		//добавим запись в историю активности
		if ( count( $indexs[ 'history' ] ) > 0 ) {

			try {

				addHistorty( [
					"clid"     => $clid,
					"pid"      => $pid,
					"datum"    => ( $data[ $i ][ $indexs[ 'history' ][ 'datum' ] ] == '' ) ? current_datumtime() : $data[ $i ][ $indexs[ 'history' ][ 'datum' ] ]." 12:00:00",
					"tip"      => $data[ $i ][ $indexs[ 'history' ][ 'tip' ] ],
					"des"      => $data[ $i ][ $indexs[ 'history' ][ 'des' ] ],
					"iduser"   => $iduser1,
					"identity" => $identity
				] );

				$good3++;

			}
			catch ( Exception $e ) {

				$error[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();
				$err3++;

			}

		}

		//добавим реквизиты клиента
		$rcv = [];
		for ( $g = 0; $g < count( $recv ); $g++ ) {

			if ( $recv[ $g ] == 'castUrName' )
				$rcv[] = ( $data[ $i ][ $indexs[ 'recvizit' ][ $recv[ $g ] ] ] == '' ) ? $castName : $data[ $i ][ $indexs[ 'recvizit' ][ $recv[ $g ] ] ];

			else
				$rcv[] = ( $data[ $i ][ $indexs[ 'recvizit' ][ $recv[ $g ] ] ] != '' ) ? $data[ $i ][ $indexs[ 'recvizit' ][ $recv[ $g ] ] ] : '';

		}

		if ( !empty( $rcv ) ) $db -> query( "UPDATE ".$sqlname."clientcat SET recv = '".implode( ";", $rcv )."' WHERE clid = '$clid' and identity = '$identity'" );

		$clid = 0;
		$pid  = 0;

	}

	unlink( $url );

	$mesg = '';

	if ( $err == 0 )
		$mesg .= "Список клиентов импортирован успешно.<br> Импортировано <strong>".$good."</strong> записей.<br> Ошибок: нет<br>";
	else
		$mesg .= "Список клиентов импортирован с ошибками.<br> Импортировано <strong>".$good."</strong> позиций.<br> Ошибок: ".$err."<br>";

	if ( $err2 == 0 )
		$mesg .= "Список персон импортирован успешно.<br> Импортировано <strong>".$good2."</strong> записей.<br> Ошибок: нет<br>";
	else
		$mesg .= "Список персон импортирован с ошибками.<br> Импортировано <strong>".$good2."</strong> позиций.<br> Ошибок: ".$err2;

	if ( $err3 == 0 )
		$mesg .= "Список активностей импортирован успешно.<br> Импортировано <strong>".$good3."</strong> записей.<br> Ошибок: нет<br>";
	else
		$mesg .= "Список активностей импортирован с ошибками.<br> Импортировано <strong>".$good3."</strong> позиций.<br> Ошибок: ".$err3;

	logger( '6', 'Импорт клиентов и персон', $iduser1 );

	print $mesg;

	event ::fire( 'client.import', $args = [
		"clids"    => $clids,
		"pids"     => $pids,
		"autor"    => $iduser1,
		"user"     => $iduser,
		"identity" => $identity
	] );

	exit();

}
?>
<script>

	$('#dialog').css('width', '800px').center();

	var action = $('#action').val();
	var selectedOpt = [];

	$(document).ready(function () {

		//$('#fpole').width('auto');

		$('#resultdiv').find('select').each(function () {

			$(this).wrap("<span class='select'></span>");

		});

		if (action === 'import_on') {

			changeSel();

			$('#dialog').find('select').bind('change', function () {

				var opt = $('option:selected', this).val();
				selectedOpt.push(opt);

				$('#dialog').find('select').not(this).find('option[value="' + opt + '"]').prop('disabled', true);

				changeSel();

			});

		}

		setTimeout(function () {
			$('#dialog').center();
		}, 1000);

	});

	$('#Form').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
			$('#dialog').removeClass('dtransition');

			return true;

		},
		success: function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			$('#resultdiv').empty().append('<DIV class="zagolovok">Импорт клиентов в базу. Читаю данные.</DIV><div class="contentloader margtop20 margbot20"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			if (data === 'Файл загружен') {

				$.get('/content/helpers/client.import.php?action=select', function (resp) {

					if (resp !== '') $('#resultdiv').html(resp);
					else {
						$('#resultdiv').html('<DIV class="zagolovok">Ошибка.</DIV><div class="contentloader margtop20 margbot20">Файл содержит слишком большое количество записей. Попробуйте загрузить не более 5000 строк</div>');
						//Discard();
					}

				});

			}
		},
		complete: function () {
			$('#dialog').addClass('dtransition');
		}
	});

	$('#Form2').ajaxForm({
		beforeSubmit: function () {
			var $out = $('#message');
			var ef = $("#field\\[\\]").filter('[value=""]').size();
			var ff = $("#field\\[\\]").size();
			emp = (ff - ef);
			$out.empty();
			if (emp == 0) {
				$("#field\\[\\]").filter('[value=""]').css({color: "#FFF", background: "#FF8080"});
				alert("Не сопоставлено ниодного поля");
				return false;
			}
			else {
				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');
				$('#dialog').removeClass('dtransition');
				return true;
			}
		},
		success: function (data) {
			$('#dialog_container').css('display', 'none');
			$('#dialog').css('display', 'none');

			if (typeof configpage === 'function') {
				configpage();
			}

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);
		},
		complete: function () {
			$('#dialog').addClass('dtransition');
		}
	});

	$(document).on('change', '#file', function () {

		//console.log(this.files);

		var ext = this.value.split(".");
		var elength = ext.length;
		var carrentExt = ext[elength - 1].toLowerCase();

		if (in_array(carrentExt, ['csv', 'xls', 'xlsx']))
			$('.next').removeClass('graybtn');

		else {

			Swal.fire('Только в формате CSV, XLS, XLSX', '', 'warning');
			$('#file').val('');
			$('.next').addClass('graybtn');

		}

	});

	function Next() {

		if (!$('.next').hasClass('graybtn'))
			$('#Form').submit();

		else
			Swal.fire('Внимание', 'Вы забыли выбрать файл для загрузки', 'warning');

	}

	function Discard() {

		var url = '/content/helpers/client.import.php?action=discard';

		$.post(url, function () {

		});

		DClose();

	}

	function changeSel() {

		selectedOpt = [];

		$('#dialog').find('option').prop('disabled', false);

		$('#dialog').find('select').each(function () {

			var opt = $('option:selected', this).val();

			if (opt !== '') {

				selectedOpt.push(opt);
				$('#dialog').find('select').not(this).find('option[value="' + opt + '"]').prop('disabled', true);

			}


		});

	}

</script>