<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */

/* ============================ */

use Salesman\Price;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];
$name   = $_REQUEST['name'];
$pwidth = $_REQUEST['pwidth'];
$tip    = $_REQUEST['tip'];
$ord    = $_REQUEST['ord'];

if ( $action == "ftpcheck" ) {

	$ftpparams['mcFtpServer'] = $_REQUEST['mcFtpServer'];
	$ftpparams['mcFtpUser']   = $_REQUEST['mcFtpUser'];
	$ftpparams['mcFtpPass']   = $_REQUEST['mcFtpPass'];
	$ftpparams['mcFtpPath']   = $_REQUEST['mcFtpPath'];

	//require_once "../../opensource/class/Ftp.class.php";

	$ftpsettings = $db -> getOne( "SELECT ftp FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
	$ftpsettings = json_decode( $ftpsettings, true );

	$dfile = $ftpsettings['mcFtpPath']."/salesman.png";
	$sfile = $rootpath."/assets/images/logo.png";

	try {

		$ftp = new Ftp;
		$ftp -> connect( $ftpparams['mcFtpServer'] );
		$ftp -> login( $ftpparams['mcFtpUser'], $ftpparams['mcFtpPass'] );
		$ftp -> put( $dfile, $sfile, FTP_BINARY );

	}
	catch ( FtpException $e ) {
		$err = 'Error: <b class="red">'.$e -> getMessage().'</b><br>';
	}

	if ( $err ) {
		print $err;
		//print_r( $ftpparams );
	}
	else {
		print '<b class="green">Успешно</b>. Можете проверить наличие файла <b>salesman.png</b> на сервере в папке <b>'.$ftpsettings['mcFtpPath'].'</b>';
	}

}

if ( $action == "delete" ) {

	$db -> query( "delete from {$sqlname}modcatalog_fieldcat where id = '".$id."' and identity = '$identity'" );

	$i = 1;

	$result = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_fieldcat where identity = '$identity' ORDER BY ord" );
	foreach ( $result as $data ) {

		$db -> query( "update {$sqlname}modcatalog_fieldcat set ord = '".$i."' where id = '".$data['id']."' and identity = '$identity'" );

	}

	$action = '';
}

if ( $action == "edit_do" ) {

	if ( $tip == 'divider' ) {
		$pwidth = 100;
	}

	$ttip = [
		'input',
		'text'
	];

	//print $_REQUEST['value'];

	if ( !in_array( $tip, $ttip ) ) {

		$value = str_replace( [
			"\\n",
			"\n",
			"\\r",
			"\r"
		], [
			";",
			";",
			"",
			""
		], $_REQUEST['value'] );

		$vars = yexplode( ";", $value );

		$value = implode( ";", $vars );

	}
	else {

		$value = $_REQUEST['value'];

	}

	if ( $id > 0 ) {

		try {

			$db -> query( "UPDATE {$sqlname}modcatalog_fieldcat SET ?u WHERE id = '$id' and identity = '$identity'", [
				"name"   => $name,
				"tip"    => $tip,
				"value"  => $value,
				"pwidth" => $pwidth
			] );
			print "Готово";

		}
		catch ( Exception $e ) {

			print $err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
	else {

		$old = $db -> getOne( "SELECT ord FROM {$sqlname}modcatalog_fieldcat where identity = '$identity' ORDER BY ord DESC LIMIT 1" );

		$ord++;

		try {

			$db -> query( "INSERT INTO {$sqlname}modcatalog_fieldcat (id,name,tip,value,pwidth,ord,identity) values(null, '".$name."', '".$tip."', '".$value."', '".$pwidth."', '".$ord."','$identity')" );
			$id = $db -> insertId();

			$pname = 'pole'.$id;

			$db -> query( "update {$sqlname}modcatalog_fieldcat set pole = '".$pname."' where id = '".$id."' and identity = '$identity'" );

			print "Готово";

		}
		catch ( Exception $e ) {

			print $err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	exit();

}
if ( $action == "edit_order" ) {

	$table = explode( ';', implode( ';', $_REQUEST['table-1'] ) );
	$count = count( $_REQUEST['table-1'] );
	$err   = 0;

	//Обновляем данные для текущей записи

	for ( $i = 1; $i < $count; $i++ ) {

		try {

			$db -> query( "update {$sqlname}modcatalog_fieldcat set ord = '".$i."' where id = '".$table[ $i ]."' and identity = '$identity'" );

		}
		catch ( Exception $e ) {

			//$err[] = 'Ошибка'. $e-> getMessage(). ' в строке '. $e->getCode();
			$err++;

		}

	}

	print "Обновлено. Ошибок:".$err;


	exit();
}
if ( $action == "edit" ) {

	if ( $id > 0 ) {

		$res    = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_fieldcat where id = '".$id."' and identity = '$identity'" );
		$name   = $res["name"];
		$tip    = $res["tip"];
		$value  = $res["value"];
		$pwidth = $res["pwidth"];

		$value = str_replace( ";", "\n", $value );

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="/modules/modcatalog/settings.modcatalog.php" method="POST" name="editForm" id="editForm" enctype="multipart/form-data">
		<input name="action" id="action" type="hidden" value="edit_do"/>
		<input name="id" id="id" type="hidden" value="<?= $id ?>"/>
		<TABLE>
			<TR>
				<TD width="160" align="right"><b>Название</b>:</TD>
				<TD><INPUT name="name" type="text" id="name" class="required" style="width:90%" value="<?= $name ?>">
				</TD>
			</TR>
			<TR>
				<TD align="right"><b>Тип вывода</b>:</TD>
				<TD>
					<select id="tip" name="tip" class="required">
						<option value="input" <?php if ( $tip == 'input' )
							print 'selected="selected"' ?>>Поле ввода
						</option>
						<option value="text" <?php if ( $tip == 'text' )
							print 'selected="selected"' ?>>Поле текста
						</option>
						<option value="select" <?php if ( $tip == 'select' )
							print 'selected="selected"' ?>>Список выбора
						</option>
						<option value="checkbox" <?php if ( $tip == 'checkbox' )
							print 'selected="selected"' ?>>Чекбоксы
						</option>
						<option value="radio" <?php if ( $tip == 'radio' )
							print 'selected="selected"' ?>>Радиокнопки
						</option>
						<option value="divider" <?php if ( $tip == 'divider' )
							print "selected" ?>>Разделитель
						</option>
					</select>
				</TD>
			</TR>
			<TR>
				<TD width="160" align="right"><b>Ширина поля</b>:</TD>
				<TD><INPUT name="pwidth" type="text" id="pwidth" value="<?= $pwidth ?>" style="width:50px">&nbsp;%</TD>
			</TR>
			<TR>
				<TD align="right"><b>Варианты выбора</b>:</TD>
				<TD>
					<textarea name="value" rows="10" id="value" style="width:90%"><?= $value ?></textarea>
					<br/>
					<div class="smalltxt">
						<ul>
							<li>Каждый вариант начните с новой строки с помощью клавиши Enter.</li>
							<li>Для полей типа "Поле ввода", "Поле текста", "Разделитель блока", "Название блока" поле "Варианты выбора" оставьте пустым.</li>
							<li>Поле разделитель принудительно имеет ширину 100%</li>
						</ul>
					</div>
				</TD>
			</TR>
		</TABLE>
		<hr>
		<div align="right">
			<A href="javascript:void(0)" onclick="$('#editForm').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<script>
		$('#dialog').css('width', '800px');
		$('#editForm').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (!em)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#tab-form-2').load('/modules/modcatalog/settings.modcatalog.php?action=pole').append('<img src="/assets/images/loading.gif">');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});
	</script>
	<?php
	exit();
}

if ( $action == "settings_do" ) {

	$oldsettings = $db -> getOne( "SELECT settings FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
	$oldsettings = json_decode( $oldsettings, true );

	//$params['mcActive'] = $_REQUEST['mcActive'];
	$params['mcArtikul'] = $_REQUEST['mcArtikul'];
	$params['mcStep']    = $_REQUEST['mcStep'];

	$params['mcStepPers'] = $db -> getOne( "SELECT title FROM {$sqlname}dogcategory WHERE idcategory = '".$params['mcStep']."' and identity = '$identity'" );

	$params['mcKolEdit']      = $_REQUEST['mcKolEdit'];
	$params['mcStatusEdit']   = $_REQUEST['mcStatusEdit'];
	$params['mcUseOrder']     = $_REQUEST['mcUseOrder'];
	$params['mcCoordinator']  = $_REQUEST['mcCoordinator'];
	$params['mcSpecialist']   = $_REQUEST['mcSpecialist'];
	$params['mcAutoRezerv']   = $_REQUEST['mcAutoRezerv'];
	$params['mcAutoWork']     = $_REQUEST['mcAutoWork'];
	$params['mcAutoStatus']   = $_REQUEST['mcAutoStatus'];
	$params['mcSklad']        = 'yes';//$_REQUEST['mcSklad'];
	$params['mcSkladPoz']     = $_REQUEST['mcSkladPoz'];
	$params['mcAutoProvider'] = $_REQUEST['mcAutoProvider'];
	$params['mcAutoPricein']  = $_REQUEST['mcAutoPricein'];

	$params['mcDBoardSkladName']   = $_REQUEST['mcDBoardSkladName'];
	$params['mcDBoardSklad']       = $_REQUEST['mcDBoardSklad'];
	$params['mcDBoardZayavkaName'] = $_REQUEST['mcDBoardZayavkaName'];
	$params['mcDBoardZayavka']     = $_REQUEST['mcDBoardZayavka'];
	$params['mcDBoardOfferName']   = $_REQUEST['mcDBoardOfferName'];
	$params['mcDBoardOffer']       = $_REQUEST['mcDBoardOffer'];

	$params['mcMenuTip']   = $_REQUEST['mcMenuTip'];
	$params['mcMenuPlace'] = $_REQUEST['mcMenuPlace'];

	$params['mcOfferName1'] = $_REQUEST['mcOfferName1'];
	$params['mcOfferName2'] = $_REQUEST['mcOfferName2'];

	$params['mcPriceCat'] = $_REQUEST['mcPriceCat'] ?? [];

	if ( $params['mcMenuTip'] == 'inSub' )
		$params['mcMenuPlace'] = '';

	$settings = json_encode_cyr( $params );

	$ftpparams['mcFtpServer'] = $_REQUEST['mcFtpServer'];
	$ftpparams['mcFtpUser']   = $_REQUEST['mcFtpUser'];
	$ftpparams['mcFtpPass']   = $_REQUEST['mcFtpPass'];
	$ftpparams['mcFtpPath']   = $_REQUEST['mcFtpPath'];

	$ftpsettings = json_encode_cyr( $ftpparams );

	//print "update {$sqlname}modcatalog_set set settings = '".$settings."', ftp = '".$ftpsettings."' WHERE identity = '$identity'";

	$db -> query( "update {$sqlname}modcatalog_set set settings = '".$settings."', ftp = '".$ftpsettings."' WHERE identity = '$identity'" );

	print "Сделано";

	//print "<br>".$params['mcSkladPoz']." :: ".$oldsettings['mcSkladPoz'];

	//если включен поштучный учет, то разбиваем позиции по штукам
	if ( $params['mcSkladPoz'] == 'yes' && $oldsettings['mcSkladPoz'] != 'yes' ) {

		$res = $db -> getAll( "select * FROM {$sqlname}modcatalog_skladpoz WHERE identity = '$identity'" );
		foreach ( $res as $da ) {

			for ( $i = 0; $i < $da['kol']; $i++ ) {

				$db -> query( "INSERT INTO {$sqlname}modcatalog_skladpoz (id, prid, sklad, status, date_in, kol, identity) VALUES (NULL, '".$da['prid']."', '".$da['sklad']."', 'in', '".current_date()."', '1', '".$identity."')" );

			}

			//удаляем данные, т.к. они не нужны
			$db -> query( "DELETE FROM {$sqlname}modcatalog_skladpoz WHERE id = '".$da['id']."'" );

		}

		print "<br>Включен поштучный учет";

	}

	//если был включен поштучный учет, а сейчас нет, то объединяем позиции
	elseif ( $params['mcSkladPoz'] != 'yes' && $oldsettings['mcSkladPoz'] == 'yes' ) {

		$list = [];

		$res = $db -> getAll( "select * FROM {$sqlname}modcatalog_skladpoz WHERE identity = '$identity'" );
		foreach ( $res as $da ) {

			$list[ $da['sklad'] ][ $da['prid'] ] += $da['kol'];

		}

		foreach ( $list as $sklad => $val ) {

			foreach ( $val as $prid => $kol ) {

				//удаляем все записи по позиции по складу, т.к. они не нужны
				$db -> query( "DELETE FROM {$sqlname}modcatalog_skladpoz WHERE sklad = '".$sklad."' AND prid = '".$prid."'" );

				$db -> query( "INSERT INTO {$sqlname}modcatalog_skladpoz (id, prid, sklad, status, date_in, kol, identity) VALUES (NULL, '".$prid."', '".$sklad."', 'in', '".current_datum()."', '".$kol."', '".$identity."')" );

			}

		}

		print "<br>Включен простой учет";

	}

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );

	exit();
}

if ( $action == "editsklad_do" ) {

	$title     = $_REQUEST['title'];
	$mcid      = $_REQUEST['mcid'];
	$id        = $_REQUEST['id'];
	$isDefault = $_REQUEST['isDefault'];

	//сбросим все умолчания
	if ( $isDefault == 'yes' ) {

		$db -> query( "update {$sqlname}modcatalog_sklad set isDefault='no' WHERE mcid='".$mcid."' and identity = '$identity'" );

	}

	if ( $id < 1 ) {

		try {

			$db -> query( "insert into {$sqlname}modcatalog_sklad (id,title,mcid,isDefault,identity) values(null, '".$title."', '".$mcid."','$isDefault','$identity')" );
			echo 'Сделано';

		}
		catch ( Exception $e ) {

			print $err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}

	if ( $id > 0 ) {

		try {

			$db -> query( "update {$sqlname}modcatalog_sklad set title = '".$title."', mcid = '".$mcid."', isDefault = '$isDefault' where id = '".$id."' and identity = '$identity'" );
			echo 'Сделано';

		}
		catch ( Exception $e ) {

			print $err[] = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

	}
}
if ( $action == "editsklad" ) {

	$id = $_REQUEST['id'];

	if ( $id > 0 ) {

		$result    = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_sklad where id = '".$id."' and identity = '$identity'" );
		$title     = $result['title'];
		$mcid      = $result['mcid'];
		$isDefault = $result['isDefault'];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="/modules/modcatalog/settings.modcatalog.php" method="POST" name="form" id="form">
		<input name="action" type="hidden" value="editsklad_do" id="action"/>
		<input name="id" type="hidden" value="<?= $id ?>" id="<?= $id ?>"/>
		<TABLE>
			<TR height="40">
				<TD width="120" align="right"><b>Название</b>:</TD>
				<TD><INPUT name="title" type="text" class="btn" id="title" style="width:99%" value="<?= $title ?>"></TD>
			</TR>
			<TR>
				<TD width="120" align="right"><b>Компания</b>:</TD>
				<TD>
					<select name="mcid" id="mcid" class="required" title="Укажите, от какой Вашей компании совершается сделка">
						<?php
						$res = $db -> getAll( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot" );
						foreach ( $res as $data ) {
							?>
							<option <?php if ( $data['id'] == $mcid ) {
								print "selected";
							} ?> value="<?= $data['id'] ?>"><?= $data['name_shot'] ?></option>
						<?php } ?>
					</select>
				</TD>
			</TR>
			<tr>
				<td></td>
				<td>
					<label><input id="isDefault" name="isDefault" type="checkbox" value="yes" <?php if ( $isDefault == 'yes' )
							print 'checked' ?> />&nbsp;Использовать по-умолчанию&nbsp;</label>
				</td>
			</tr>
		</TABLE>
		<hr>
		<div align="right">
			<A href="javascript:void(0)" onclick="$('#form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<script>
		$('#form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (!em)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('div[data-id="sklad"]').load('/modules/modcatalog/settings.modcatalog.php?action=sklad');

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();

			}
		});
	</script>
	<?php
	exit();
}
if ( $action == "deletesklad_do" ) {

	$id       = $_REQUEST['id'];
	$newsklad = $_REQUEST['newsklad'];

	try {

		$db -> query( "update {$sqlname}modcatalog set sklad = '".$_REQUEST['newsklad']."' WHERE sklad = '".$id."' and identity = '$identity'" );

		$db -> query( "delete from {$sqlname}modcatalog_sklad where id = '".$id."' and identity = '$identity'" );

		print "Сделано";

	}
	catch ( Exception $e ) {
		print $mes = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();
	}

	exit();
}

if ( $action == "deletesklad" ) {

	$title = $db -> getOne( "SELECT title FROM {$sqlname}modcatalog_sklad WHERE id = '".$id."' and identity = '$identity'" );
	?>
	<div class="zagolovok">Удалить склад "<?= $title ?>"?</div>
	<FORM action="/modules/modcatalog/settings.modcatalog.php" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="deletesklad_do" id="action"/>
		<div class="infodiv">В случае удаления, данный склад останется в существующих записях. Вы можете перевести их на новый склад.</div>
		<hr>
		<TABLE id="zebra2">
			<TR class="bordered">
				<TD align="right" width="100"><b>Новый склад</b></TD>
				<TD>
					<select name="newsklad" id="newsklad" style="width: 100%;" class="required">
						<option value="">--выбрать--</option>
						<?php
						$res = $db -> query( "SELECT * FROM {$sqlname}modcatalog_sklad WHERE id != '".$id."' and identity = '$identity' ORDER by title" );
						while ($data = $db -> fetch( $res )) {
							?>
							<option value="<?= $data['id'] ?>"><?= $data['title'] ?></option>
						<?php } ?>
					</select>
				</TD>
			</TR>
		</TABLE>
		<hr>
		<div align="right">
			<A href="javascript:void(0)" onclick="$('#form').submit()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<script>
		$('#dialog').css('width', '600px');

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var newd = $('#newid option:selected').val();
				var $out = $('#message');
				var em = 0;

				$(".required").removeClass("empty").css({"color": "inherit", "background": "#FFF"});
				$(".required").each(function () {

					if ($(this).val() === '') {
						$(this).addClass("empty").css({"color": "red", "background": "#FF8080"});
						em = em + 1;
					}

				});

				$out.empty();

				if (em > 0 || newd < 1) {

					alert("Не заполнены обязательные поля\n\rОни выделены цветом");
					return false;

				}
				if (em === 0 && newd > 0) {
					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;
				}

			},
			success: function (data) {
				$('#contentdiv').load('/modules/modcatalog/settings.modcatalog.php?action=sklad');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
				DClose();
			}
		});
	</script>
	<?php
	exit();
}

if ( $action == "settings" ) {

	$result      = $db -> getRow( "SELECT * FROM {$sqlname}modcatalog_set WHERE identity = '$identity'" );
	$settings    = $result["settings"];
	$ftpsettings = $result["ftp"];

	$settings    = json_decode( $settings, true );
	$ftpsettings = json_decode( $ftpsettings, true );

	$settings['mcSklad'] = 'yes';

	$css = "hidden";
	?>
	<STYLE type="text/css">
		<!--
		#settingstbl label {
			color : #2980B9;
		}

		thead .header_contaner {
			font-size      : 1.0em;
			font-weight    : 700;
			color          : var(--gray-litedarkblue);
			border-top     : 1px dotted var(--gray-litedarkblue);
			text-transform : uppercase;
			text-align     : left;
		}

		thead .header_contaner.blue {
			color      : var(--blue);
			background : var(--gray);
		}

		-->
	</STYLE>

	<FORM action="/modules/modcatalog/settings.modcatalog.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="settings_do">

		<TABLE id="settingstbl" class="top">
			<thead class="hand" data-id="aboutset">
			<TR class="th35">
				<th colspan="2" class="header_contaner">
					Общие настройки
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="aboutset" class="hidden">
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2">Использовать артикул:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcArtikul" id="mcArtikul" value="yes" <?php if ( $settings['mcArtikul'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает использование артикулов</div>
				</td>
			</tr>
			<tr class="hidden"><!--склад всегда включен-->
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Использовать склад:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcSklad" id="mcSklad" readonly value="yes" <?php if ( $settings['mcSklad'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает использование складов.</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2">Поштучный учет:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcSkladPoz" id="mcSkladPoz" value="yes" <?php if ( $settings['mcSkladPoz'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает поштучный учет товара на складах, в т.ч. учет серийных номеров, даты производства, даты поверки</div>
					<div class="viewdiv">
						<div class="Bold red">Внимание!</div>
						<ul>
							<li>При переходе от простого к поштучному учету складские остатки будут
								<u>распределены</u> для учета каждой позиции.
							</li>
							<li>При переходе от поштучного к простому учету складские остатки будут
								<u>объеденены</u> с удалением дополнительных данных (серийные номера, даты производства и пр.).
							</li>
						</ul>

					</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2">Обработка поступлений на склад:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcAutoWork" id="mcAutoWork" value="yes" <?php if ( $settings['mcAutoWork'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает автоматическую обработку приходных ордеров - будут проверены все заявки и автоматически поставлены в резерв, если ордер не привязн к заявке и/или к сделке.</div>
				</td>
			</tr>
			<tr class="hidden"><!--статусы больше не актуальны, т.к. ведется учет наличия по складам-->
				<td class="w180 text-right">
					<div class="fs-12 gray2">Обработка статусов:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcAutoStatus" id="mcAutoStatus" value="yes" <?php if ( $settings['mcAutoStatus'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Автоматически проверяет статусы позиций на основе наличия на складе или в резерве.</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2">Автоматическое резервирование:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcAutoRezerv" id="mcAutoRezerv" value="yes" <?php if ( $settings['mcAutoRezerv'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает автоматическое резервирование позиций и создание заявок при достижении сделок указанного ниже этапа.<br>В противном случае резервирование производится вручную в модуле Каталог - меню "<b>Обновить резерв</b>" .
					</div>
					<div class="viewdiv">
						<b class="red">Внимание!</b> Автоматическое резервирование происходит только со склада "по умолчанию" для каждой компании
					</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Этап сделки:</div>
				</td>
				<td>
					<div class="select">
						<select name="mcStep" id="mcStep">
							<option value="">--Выбор--</option>
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER BY title" );
							foreach ( $result as $data ) {
								?>
								<option <?php if ( $data['idcategory'] == $settings['mcStep'] ) {
									print "selected";
								} ?> value="<?= $data['idcategory'] ?>"><?= $data['title']."% - ".$data['content'] ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="smalltxt gray2">Этап сделки, с которого разрешается создавать заявки, резервировать позиции</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2">Добавить в расходы:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcAutoProvider" id="mcAutoProvider" value="yes" <?php if ( $settings['mcAutoProvider'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает автоматическое добавление расхода по Заявке в Бюджет / Расходы на поставщиков. Если заявка привязана к сделке, расход также будет привязан к сделке</div>
					<div class="viewdiv">
						<b class="red">Внимание!</b> Автоматическое добавление расхода происходит только при выполнении заявки - т.е. её перевод в статус "Выполнено"
					</div>
				</td>
			</tr class="th35">
			<tr class="">
				<td class="w180 text-right">
					<div class="fs-12 gray2">Обновить закуп:</div>
				</td>
				<td>
					<label><input type="checkbox" name="mcAutoPricein" id="mcAutoPricein" value="yes" <?php if ( $settings['mcAutoPricein'] == 'yes' )
							print "checked"; ?>>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает автоматическое обновление прайсовой позиции при проведении Приходного ордера</div>
					<div class="viewdiv"><b class="red">Внимание!</b> Лог изменений цены не ведется</div>
				</td>
			</tr>
			<tr class="hidden"><!--должен быть включен-->
				<TD class="w180 text-right">
					<div class="fs-12 gray2">Использовать ордеры:</div>
				</TD>
				<TD>
					<label><input type="checkbox" name="mcUseOrder" id="mcUseOrder" value="yes" checked>&nbsp;Да</label><br>
					<div class="smalltxt gray2">Включает Приходные/Расходные ордеры.
						<b>В случае отключения рекомендуем включить редактирование Количества и Статуса</b></div>
				</TD>
			</tr>
			</tbody>

			<thead class="hand" data-id="menucat">
			<TR class="th35">
				<th colspan="2" class="header_contaner">
					Настройка меню
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="menucat" class="hidden">
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Как отображать:</div>
				</td>
				<td>
					<div class="select">
						<select name="mcMenuTip" id="mcMenuTip" onchange="showPlace()">
							<option value="">--Выбор--</option>
							<option value="inMain" <?php if ( $settings['mcMenuTip'] == 'inMain' )
								print "selected"; ?>>Как раздел меню
							</option>
							<option value="inSub" <?php if ( $settings['mcMenuTip'] == 'inSub' )
								print "selected"; ?>>Подраздел Сервисы
							</option>
						</select>
					</div>
					<span id="submenu" class="<?= $css ?>">после раздела&nbsp;
				<select name="mcMenuPlace" id="mcMenuPlace">
					<option value="">--Выбор--</option>
					<option value="afterClients" <?php if ( $settings['mcMenuPlace'] == 'afterClients' )
						print "selected"; ?>>Клиенты</option>
					<option value="afterSales" <?php if ( $settings['mcMenuPlace'] == 'afterSales' )
						print "selected"; ?>>Продажи</option>
					<option value="afterCalendar" <?php if ( $settings['mcMenuPlace'] == 'afterCalendar' )
						print "selected"; ?>>Календарь</option>
					<option value="afterMarketing" <?php if ( $settings['mcMenuPlace'] == 'afterMarketing' )
						print "selected"; ?>>Маркетинг</option>
					<option value="afterAnalitics" <?php if ( $settings['mcMenuPlace'] == 'afterAnalitics' )
						print "selected"; ?>>Аналитика</option>
					<option value="afterFinance" <?php if ( $settings['mcMenuPlace'] == 'afterFinance' )
						print "selected"; ?>>Финансы</option>
					<option value="afterServices" <?php if ( $settings['mcMenuPlace'] == 'afterServices' )
						print "selected"; ?>>Сервисы</option>
				</select>&nbsp;
			</span>
					<div class="smalltxt gray2">Изменение вступит в силу после обновления окна браузера</div>
				</td>
			</tr>
			</tbody>

			<thead class="hand" data-id="dashboard">
			<TR class="th35">
				<th colspan="2" class="header_contaner" align="left">
					Дашбоарды Рабочего стола
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="dashboard" class="hidden">
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Позиции в наличии:</div>
				</td>
				<td>
					<div class="pull-left w250">
						<input type="text" name="mcDBoardSkladName" id="mcDBoardSkladName" value="<?= $settings['mcDBoardSkladName'] ?>">&nbsp;
						<div class="smalltxt gray2">Название вкладки - Позиции в наличии</div>
					</div>
					<div class="pt10">
						<label><input type="checkbox" name="mcDBoardSklad" id="mcDBoardSklad" value="yes" <?php if ( $settings['mcDBoardSklad'] == 'yes' )
								print "checked"; ?>>&nbsp;Включить вкладку</label>
					</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Заявки:</div>
				</td>
				<td>
					<div class="pull-left w250">
						<input type="text" name="mcDBoardZayavkaName" id="mcDBoardZayavkaName" value="<?= $settings['mcDBoardZayavkaName'] ?>">&nbsp;<div class="smalltxt gray2">Название вкладки - Заявки</div>
					</div>
					<div class="pt10">
						<label><input type="checkbox" name="mcDBoardZayavka" id="mcDBoardZayavka" value="yes" <?php if ( $settings['mcDBoardZayavka'] == 'yes' )
								print "checked"; ?>>&nbsp;Включить вкладку</label>
					</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Предложения:</div>
				</td>
				<td>
					<div class="pull-left w250">
						<input type="text" name="mcDBoardOfferName" id="mcDBoardOfferName" value="<?= $settings['mcDBoardOfferName'] ?>">&nbsp;<div class="smalltxt gray2">Название вкладки - Предложения</div>
					</div>
					<div class="pt10">
						<label><input type="checkbox" name="mcDBoardOffer" id="mcDBoardOffer" value="yes" <?php if ( $settings['mcDBoardOffer'] == 'yes' )
								print "checked"; ?>>&nbsp;Включить вкладку</label>
					</div>
				</td>
			</tr>
			</tbody>

			<thead class="hand" data-id="offer">
			<TR class="th35">
				<th colspan="2" class="header_contaner" align="left">
					Поля предложения
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="offer" class="hidden">
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Поле №1:</div>
				</td>
				<td>
					<input type="text" name="mcOfferName1" id="mcOfferName1" value="<?= $settings['mcOfferName1'] ?>">&nbsp;<div class="smalltxt gray2">Название первого поля</div>
				</td>
			</tr>
			<tr class="th35">
				<td class="w180 text-right">
					<div class="fs-12 gray2 pt7">Поле №2:</div>
				</td>
				<td>
					<input type="text" name="mcOfferName2" id="mcOfferName2" value="<?= $settings['mcOfferName2'] ?>">&nbsp;<div class="smalltxt gray2">Название второго поля</div>
				</td>
			</tr>
			</tbody>

			<thead class="hand" data-id="cat">
			<TR class="th35">
				<th colspan="2" class="header_contaner" align="left">
					Категории прайса для склада
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="cat" class="hidden">
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Категории:</div>
				</TD>
				<TD>
					<div style="overflow-y: auto; overflow-x: hidden; max-height: 500px">
						<?php
						//$maxlevel = 1;
						$catalog = Price ::getPriceCatalog();

						foreach ( $catalog as $key => $value ) {

							if ( $value['level'] > 0 ) {
								$s = str_repeat( '&nbsp;&nbsp;&nbsp;&nbsp;', $value['level'] ).'<div class="strelka mr10"></div>&nbsp;';
							}
							else $s = '';

							$ss = (in_array( $value['id'], $settings['mcPriceCat'] )) ? "checked" : "";
							?>

							<label class="block ha pricecat" data-id="<?= $value['id'] ?>" data-sub="<?= $value['sub'] ?>">
								<div class="row">
									<div class="column grid-4">
										<div class="ellipsis"><?= $s ?>&nbsp;<?= $value['title'] ?>&nbsp;</div>
									</div>
									<div class="column grid-6">
										<input type="checkbox" name="mcPriceCat[]" id="mcPriceCat[]" value="<?= $value['id'] ?>" <?= $ss ?>>
									</div>
								</div>
							</label>

						<?php }
						?>
					</div>
					<div class="smalltxt gray2 mt10">Укажите категории прайса, являющиеся товаром. Это нужно для того, чтобы не учитывать на складе услуги</div>
				</TD>
			</tr>
			</tbody>

			<thead class="hand hidden" data-id="act">
			<TR class="th35">
				<th colspan="2" class="header_contaner" align="left">
					Действия
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="act" class="hidden">
			<tr class="hidden"><!--с версии 2016.20 количество регулируется только через ордеры-->
				<TD align="right" valign="top" style="padding-top: 8px;"><b>Редактировать количество</b>:</TD>
				<TD>
					<label><input type="checkbox" name="mcKolEdit" id="mcKolEdit" value="yes" <?php if ( $settings['mcKolEdit'] == 'yes' )
							print "checked"; ?>>&nbsp;Разрешить</label><br>
					<div class="smalltxt gray2">Разрешает изменение количества в форме изменения позиции. В противном случае производится через Заявки или Ордера</div>
				</TD>
			</tr>
			<tr class="hidden"><!--с версии 2016.20 количество регулируется только через ордеры-->
				<TD align="right" valign="top" style="padding-top: 8px;"><b>Редактировать статус</b>:</TD>
				<TD>
					<label><input type="checkbox" name="mcStatusEdit" id="mcStatusEdit" value="yes" <?php if ( $settings['mcStatusEdit'] == 'yes' )
							print "checked"; ?>>&nbsp;Разрешить</label><br>
					<div class="smalltxt gray2">Разрешает изменение статуса в форме изменения позиции. В противном случае производится через Заявки или Ордера</div>
				</TD>
			</tr>
			</tbody>

			<thead class="hand" data-id="dostup">
			<TR class="th35">
				<th colspan="2" class="header_contaner" align="left">
					Доступ
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="dostup" class="hidden">
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Координаторы:</div>
				</TD>
				<TD>
					<div style="max-width: 800px">
						<SELECT name="mcCoordinator[]" id="mcCoordinator[]" multiple="multiple" class="multiselect" style="width:50%">
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}user where secrty='yes' and identity = '$identity' ORDER by title" );
							foreach ( $result as $data ) {
								?>
								<OPTION value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], $settings['mcCoordinator'] ) )
									print "selected" ?>><?= $data['title'] ?></OPTION>
							<?php } ?>
						</SELECT>
					</div>
					<div class="smalltxt gray2">Сотрудники отдела снабжения</div>
				</TD>
			</tr>
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Сотрудники:</div>
				</TD>
				<TD>
					<div style="max-width: 800px">
						<SELECT name="mcSpecialist[]" id="mcSpecialist[]" multiple="multiple" class="multiselect" style="width:50%">
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}user where secrty='yes' and identity = '$identity' ORDER by title" );
							foreach ( $result as $data ) {
								?>
								<OPTION value="<?= $data['iduser'] ?>" <?php if ( in_array( $data['iduser'], $settings['mcSpecialist'] ) )
									print "selected" ?>><?= $data['title'] ?></OPTION>
							<?php } ?>
						</SELECT>
					</div>
					<div class="smalltxt gray2">Сотрудники с доступом к работе с Каталогом</div>
				</TD>
			</tr>
			</tbody>

			<thead class="hand" data-id="ftp">
			<TR class="th35">
				<th colspan="2" class="header_contaner" align="left">
					Настройки FTP
					<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
				</th>
			</TR>
			</thead>
			<tbody id="ftp" class="hidden">
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Сервер:</div>
				</TD>
				<TD>
					<input type="text" name="mcFtpServer" id="mcFtpServer" value="<?= $ftpsettings['mcFtpServer'] ?>" style="width:300px">
				</TD>
			</tr>
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Пользователь:</div>
				</TD>
				<TD>
					<input type="text" name="mcFtpUser" id="mcFtpUser" value="<?= $ftpsettings['mcFtpUser'] ?>" style="width:300px">
				</TD>
			</tr>
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Пароль:</div>
				</TD>
				<TD>
					<input type="text" name="mcFtpPass" id="mcFtpPass" value="<?= $ftpsettings['mcFtpPass'] ?>" style="width:300px">
				</TD>
			</tr>
			<tr class="th35">
				<TD class="w180 text-right">
					<div class="fs-12 gray2 pt7">Удаленная папка:</div>
				</TD>
				<TD>
					<input type="text" name="mcFtpPath" id="mcFtpPath" value="<?= $ftpsettings['mcFtpPath'] ?>" style="width:300px">
				</TD>
			</tr>
			<tr class="th35">
				<td colspan="2">
					<div class="infodiv" style="padding-left: 30px;">
						<b>Проверка соединения с сервером:</b>
						<hr>
						<div id="res" class="hidden pad5 marg3">Запустите проверку</div>
						<br><a href="javascript:void(0)" onclick="checkConnection()" class="button"><i class="icon-arrows-cw white"></i>Проверить</a>&nbsp;&nbsp;&nbsp;
					</div>
				</td>
			</tr>
			</tbody>
		</TABLE>

		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
			<a href="javascript:void(0)" class="button" onclick="$('#set').submit()">Сохранить</a>
		</DIV>

	</FORM>
	<script>
		$(function () {

			$(".multiselect").each(function () {

				$(this).multiselect({sortable: true, searchable: true});

			});

			$(".connected-list").css('height', "200px");

			var blok = localStorage.getItem("settingsBlockCatalog");

			if (blok != null) {

				$('#tab-form-1 thead[data-id="' + blok + '"]').trigger('click');

			}
			else $('#tab-form-1 thead:first').trigger('click');


			$('#set').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');

					$out.empty();
					$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					$('div[data-id="settings"]').load('/modules/modcatalog/settings.modcatalog.php?action=settings').append('<img src="/assets/images/loading.gif">');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});

		});

		$('#tab-form-1 thead').click(function () {

			var id = $(this).data('id');

			$('#settingstbl').find('tbody:not(#' + id + ')').addClass('hidden');
			//$('#settingstbl').not('thead[data-id="' + id + '"]').find('i').removeClass('icon-angle-up').addClass('icon-angle-down');

			$('th', this).addClass('blue');
			$('#tab-form-1 thead').not(this).find('th').removeClass('blue');

			if ($('#settingstbl #' + id).hasClass('hidden')) {

				$('#settingstbl #' + id).removeClass('hidden');
				$('#settingstbl').find('thead[data-id="' + id + '"]').find('i').toggleClass('icon-angle-down icon-angle-up');

				localStorage.setItem("settingsBlockCatalog", id);

			}
			else {

				$('#settingstbl #' + id).addClass('hidden');
				$('#settingstbl').find('thead[data-id="' + id + '"]').find('i').removeClass('icon-angle-up').addClass('icon-angle-down');

				localStorage.removeItem("settingsBlockCatalog");

			}

		});

		$('.pricecat').bind('click', function () {

			var id = $(this).data('id');
			var sub = $(this).data('sub');

			if (sub === "0") {

				if ($(this).find('input').attr('checked')) {

					$('.pricecat[data-sub="' + id + '"]').find('input').attr("checked", "checked");

				}
				else $('.pricecat[data-sub="' + id + '"]').find('input').removeAttr("checked");

			}

		});

		function checkConnection() {

			var url = "/modules/modcatalog/settings.modcatalog.php";
			var str = $('#set').serialize() + '&action=ftpcheck';

			$('#res').css('display', 'block').append('<img src="/assets/images/loading.gif">');

			$.post(url, str, function (data) {
				if (data) {
					$('#res').html(data);
				}
				return false;
			});
		}
	</script>
	<?php

	exit();
}
if ( $action == "pole" ) {
	?>
	<DIV style="float:right; position:absolute;top:10px;right:15px; z-index:1001">
		<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/settings.modcatalog.php?action=edit');"><b class="blue"><i class="icon-plus-circled"></i>Добавить</b></a>&nbsp;&nbsp;
	</DIV>

	<h2>&nbsp;Раздел: "Настройки полей каталога"</h2>

	<TABLE id="table-5">
		<thead class="sticked--top">
		<TR class="th30">
			<th class="w60 text-left"><b>П.п</b></th>
			<th class="w250 text-center"><b>Название поля</b></th>
			<th class="w100 text-center" colspan="2"><b>Действия</b></th>
			<th class="w100 text-center"><b>Тип</b></th>
			<th class="w100 text-center"><b>Имя поля</b></th>
			<th class="w100 text-center">Ширина</th>
			<th class="text-center"><b>Значения</b></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$result = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord" );
		foreach ( $result as $data ) {
			?>
			<TR class="ha th40" id="<?= $data['id'] ?>">
				<TD class="text-center"><?= $data['ord'] ?></TD>
				<TD>
					<div class="fs-11 text-left Bold"><?= $data['name'] ?></div>
				</TD>
				<TD class="text-center">
					<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/settings.modcatalog.php?id=<?= $data['id'] ?>&action=edit')"><i class="icon-pencil blue"></i></A>
				</TD>
				<TD class="text-center">
					<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)refresh('contentdiv','/modules/modcatalog/settings.modcatalog.php?id=<?= $data['id'] ?>&action=delete');"><i class="icon-cancel red"></i></A>
				</TD>
				<TD class="text-center"><?= $data['tip'] ?></TD>
				<TD class="text-center"><?= $data['pole'] ?></TD>
				<TD class="text-center"><?= $data['pwidth'] ?>%</TD>
				<TD class="text-left"><?= $data['value'] ?></TD>
			</TR>
		<?php } ?>
		</tbody>
	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/settings.modcatalog.php?action=edit');" class="button"><i class="icon-plus-circled"></i>Добавить</a>

	</div>
	<div class="space-40"></div>

	<script type="text/javascript">
		$("#zebra tr:nth-child(even)").addClass("even");
		$("#table-5").tableDnD({
			onDrop: function (table, row) {
				var str = '' + $('#table-1').tableDnDSerialize();
				var url = '/modules/modcatalog/settings.modcatalog.php?action=edit_order&';
				$.post(url, str, function (data) {
					$('div[data-id="pole"]').load('/modcatalog/settings.modcatalog.php?action=pole').append('<img src="/assets/images/loading.gif">');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				});
			}
		});
	</script>
	<?php
	exit();
}
if ( $action == "sklad" ) {
	?>

	<TABLE>
		<thead class="sticked--top">
		<TR class="th30">
			<th class="w250 text-center">Название</th>
			<th class="text-center">Компания</th>
			<th class="w100 text-center" colspan="2">Действия</th>
		</TR>
		</thead>
		<?php
		$result = $db -> getAll( "SELECT * FROM {$sqlname}modcatalog_sklad WHERE identity = '$identity' ORDER BY title" );
		foreach ( $result as $data ) {

			$mcid = $db -> getOne( "SELECT name_shot FROM {$sqlname}mycomps WHERE id = '".$data['mcid']."' and identity = '$identity'" );

			$def = ($data['isDefault'] == 'yes') ? '<sup class="red">По умолчанию</sup>' : '';
			?>
			<TR class="ha th40">
				<TD class="text-left Bold fs-11"><?= $data['title'] ?><?= $def ?></TD>
				<TD class="text-left"><?= $mcid ?></TD>
				<TD class="w50 text-center">
					<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/settings.modcatalog.php?action=editsklad&id=<?= $data['id'] ?>')"><i class="icon-pencil blue"></i></A>
				</TD>
				<TD class="w50 text-center">
					<A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/settings.modcatalog.php?id=<?= $data['id'] ?>&action=deletesklad')"><i class="icon-cancel red"></i></A>
				</TD>
			</TR>
		<?php } ?>
	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
		<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/settings.modcatalog.php?action=editsklad');" class="button"><i class="icon-plus-circled"></i>Добавить</a>
	</div>
	<?php
}

if ( $action == "" ) {
	?>
	<DIV id="formtabss" style="border:0px">
		<UL>
			<LI><A href="#tab-form-1">Настройки модуля</A></LI>
			<LI><A href="#tab-form-2">Доп.поля позиций</A></LI>
			<LI><A href="#tab-form-3">Склады</A></LI>
		</UL>
		<div id="tab-form-1">

			<div data-id="settings"></div>

			<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/102')" title="Документация">
				<i class="icon-help"></i></div>

			<div class="space-100"></div>

		</div>
		<div id="tab-form-2">

			<div data-id="pole"></div>

		</div>
		<div id="tab-form-3">

			<div data-id="sklad"></div>

		</div>
	</DIV>

	<script>

		$('#formtabss').tabs();

		$.get('/modules/modcatalog/settings.modcatalog.php?action=settings', function (data) {

			$('div[data-id="settings"]').html(data);

		});

		$('div[data-id="pole"]').load('/modules/modcatalog/settings.modcatalog.php?action=pole').append('<img src="/assets/images/loading.gif">');
		$('div[data-id="sklad"]').load('/modules/modcatalog/settings.modcatalog.php?action=sklad').append('<img src="/assets/images/loading.gif">');

	</script>
	<?php
}
?>