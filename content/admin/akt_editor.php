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

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$action = $_REQUEST['action'];

$thisfile = basename( __FILE__ );

$idtype = (int)$db -> getOne( "SELECT id FROM {$sqlname}contract_type WHERE type = 'get_akt' AND identity = '$identity'" );
if ( $idtype == 0 ) {

	$db -> query( "INSERT INTO {$sqlname}contract_type SET ?u", [
		"title"    => "Акт приема-передачи",
		"type"     => "get_akt",
		"identity" => $identity
	] );
	$idtype = $db -> insertId();

	$idtemplate = (int)$db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE file = 'akt_simple.tpl'" );
	if ( $idtemplate == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
			"typeid"   => $idtype,
			"title"    => "Приёма-передачи. Услуги",
			"file"     => "akt_simple.tpl",
			"identity" => $identity
		] );

	}
	else {

		$db -> query( "UPDATE {$sqlname}contract_temp SET ?u WHERE id = '$idtemplate'", ["typeid" => $idtype] );

	}

	$idtemplate = (int)$db -> getOne( "SELECT id FROM {$sqlname}contract_temp WHERE file = 'akt_full.tpl'" );
	if ( $idtemplate == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
			"typeid"   => $idtype,
			"title"    => "Приёма-передачи. Услуги (расширенный)",
			"file"     => "akt_full.tpl",
			"identity" => $identity
		] );

	}
	else {

		$db -> query( "UPDATE {$sqlname}contract_temp SET ?u WHERE id = '$idtemplate'", ["typeid" => $idtype] );

	}

}
else {

	// проверим наличие шаблона
	$idtemp = $db -> getRow( "SELECT id, typeid FROM {$sqlname}contract_temp WHERE file = 'akt_full.tpl' AND identity = '$identity'" );
	if ( (int)$idtemp['id'] == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
			"typeid"   => $idtype,
			"title"    => "Приёма-передачи. Услуги (расширенный)",
			"file"     => "akt_full.tpl",
			"identity" => $identity
		] );

	}
	elseif ( (int)$idtemp['typeid'] == 0 ) {

		$db -> query( "UPDATE {$sqlname}contract_temp SET ?u WHERE id = '$idtemp[id]'", ["typeid" => $idtype] );

	}

	$idtemp = $db -> getRow( "SELECT id, typeid FROM {$sqlname}contract_temp WHERE file = 'akt_simple.tpl' AND identity = '$identity'" );
	if ( (int)$idtemp['id'] == 0 ) {

		$db -> query( "INSERT INTO {$sqlname}contract_temp SET ?u", [
			"typeid"   => $idtype,
			"title"    => "Приёма-передачи. Услуги",
			"file"     => "akt_simple.tpl",
			"identity" => $identity
		] );

	}
	elseif ( (int)$idtemp['typeid'] == 0 ) {

		$db -> query( "UPDATE {$sqlname}contract_temp SET ?u WHERE id = '$idtemp[id]'", ["typeid" => $idtype] );

	}

}

if ( $_REQUEST['act'] == 'tmp' ) {

	$content = $_REQUEST['content'];
	$file    = $_REQUEST['file'];

	$tmp = $file.".tmp";
	$url = $rootpath.'/cash/'.$fpath.'templates/'.$tmp;

	file_put_contents( $url, $content );

	$date = date( "Y-m-d H:i:s", filemtime( $url ) );

	if ( diffDateTimeSeq( $date ) > 300 )
		$tmp = $file;

	print $tmp;

	exit();

}
if ( $action == 'preview' ) {

	/**
	 * Тестовые данные для отображения
	 */

	$tags = json_decode( str_replace( [
		"  ",
		"\t",
		"\n",
		"\r"
	], "", '{
		"InvoiceDate":"30 Января 2017",
		"InvoiceDatePlan":"30 Января 2017 года",
		"InvoiceSumma":"133060.00",
		"Invoice":"106",
		"InvoiceDateShort":"30.01.2017",
		"InvoiceDatePlanShort":"30 Января 2017 года",
		"ContractNumber":"39-1015\/2015",
		"ContractDate":"19.10.2015",
		"compBankBik":"045744863",
		"compBankKs":"00000000000000000000",
		"compBankName":"ОАО \u00abБАНК\u00bb",
		"compBankRs":"1234567890000000000000000",
		"compUrName":"Общество с ограниченной ответственностью \u201dБрикет Солюшн\u201d",
		"compUrNameShort":"ООО \u201dБрикет Солюшн\u201d",
		"compShotName":"ООО \u201dБрикет Солюшн\u201d",
		"compUrAddr":"614007, г. Пермь, ул. Ленина, 10",
		"compFacAddr":"614007, г. Пермь, ул. ул. Ленина, 10",
		"compDirName":"Директора Андреева Владислава Германовича",
		"compDirSignature":"Андреев В.Г.",
		"compDirStatus":"Директор",
		"compDirOsnovanie":"Устава",
		"logo":"/cash/templates/logo.png",
		"signature":"/cash/templates/signature.png",
		"compInn":"000000000000",
		"compKpp":"000000000",
		"compOkpo":"",
		"compOgrn":"000000000000000",
		"castName":"Рога и копыта",
		"castUrName":"ООО \"Рога и копыта\"",
		"castInn":"000000000000",
		"castKpp":"000000000",
		"castBank":"ОАО \u00abБанк\u00bb",
		"castBankKs":"00000000000000000000",
		"castBankRs":"00000000000000000000",
		"castBankBik":"000000000",
		"castOkpo":"0000000000",
		"castOgrn":"0000000000000",
		"castDirName":"Косых Виталия Владимировича",
		"castDirSignature":"Косых В.В.",
		"castDirStatus":"Генерального директора",
		"castDirStatusSig":"Генеральный директор",
		"castDirOsnovanie":"Устава",
		"castUrAddr":"г. Пермь, ул. Пушкина 60",
		"castFacAddr":"Россия, Пермь, улица ул. Пушкина 60",
		"castCard":"Рога и копыта, ИНН 000000000000, КПП 000000000, Факт.адрес: Россия, Пермь, улица Пушкина 60, Основание: Договор \u211639-1015\/2015 от 19.10.2015",
		"offer":"",
		"speka":[
			{
				"Number":1,
				"Title":"BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
				"Artikul":"0000000001",
				"Comments":"Срок поставки 3 недели",
				"Kol":"5,00",
				"Edizm":"шт.",
				"Price":"19 516,00",
				"Nalog":"0,00",
				"Summa":"97 580,00",
				"Dop":""
			},
			{
				"Number":2,
				"Title":"SIP-T12P SIP-телефон, 2 линии, PoE",
				"Artikul":"",
				"Comments":"",
				"Kol":"10,00",
				"Edizm":"шт.",
				"Price":"3 774,00",
				"Nalog":"0,00",
				"Summa":"37 740,00",
				"Dop":""
			},
			{
				"Number":3,
				"Title":"SIP-T12P SIP-телефон, 2 линии, PoE",
				"Artikul":"",
				"Comments":"",
				"Kol":"10,00",
				"Edizm":"шт.",
				"Price":"3 774,00",
				"Nalog":"0,00",
				"Summa":"37 740,00",
				"Dop":""
			},
			{
				"Number":4,
				"Title":"EHS36 адаптер для беспроводных гарнитур Plantronics\/Jabra\/Sennheiser для телефонов T38G\/T28P\/T26P",
				"Artikul":"",
				"Comments":"",
				"Kol":"1,00",
				"Edizm":"шт.",
				"Price":"1 986,00",
				"Nalog":"0,00",
				"Summa":"1 986,00",
				"Dop":""
			},
			{
				"Number":5,
				"Title":"Монтаж оборудования",
				"Artikul":"0000010001",
				"Comments":"Срок поставки 3 недели",
				"Kol":"5,00",
				"Edizm":"шт.",
				"Price":"2 000,00",
				"Nalog":"0,00",
				"Summa":"10 000,00",
				"Dop":""
			}],
			"tovar":[
			{
				"Number":1,
				"Title":"BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
				"Artikul":"0000000001",
				"Comments":"Срок поставки 3 недели",
				"Kol":"5,00",
				"Edizm":"шт.",
				"Price":"19 516,00",
				"Nalog":"0,00",
				"Summa":"97 580,00",
				"Dop":""
			},
			{
				"Number":2,
				"Title":"SIP-T12P SIP-телефон, 2 линии, PoE",
				"Artikul":"",
				"Comments":"",
				"Kol":"10,00",
				"Edizm":"шт.",
				"Price":"3 774,00",
				"Nalog":"0,00",
				"Summa":"37 740,00",
				"Dop":""
			},
			{
				"Number":3,
				"Title":"SIP-T12P SIP-телефон, 2 линии, PoE",
				"Artikul":"",
				"Comments":"",
				"Kol":"10,00",
				"Edizm":"шт.",
				"Price":"3 774,00",
				"Nalog":"0,00",
				"Summa":"37 740,00",
				"Dop":""
			},
			{
				"Number":4,
				"Title":"EHS36 адаптер для беспроводных гарнитур Plantronics\/Jabra\/Sennheiser для телефонов T38G\/T28P\/T26P",
				"Artikul":"",
				"Comments":"",
				"Kol":"1,00",
				"Edizm":"шт.",
				"Price":"1986.00",
				"Nalog":"0,00",
				"Summa":"1 986,00",
				"Dop":""
			}],
			"usluga":[
			{
				"Number":1,
				"Title":"Монтаж оборудования",
				"Artikul":"0000010001",
				"Comments":"Срок поставки 3 недели",
				"Kol":"5,00",
				"Edizm":"шт.",
				"Price":"2 000,00",
				"Nalog":"0,00",
				"Summa":"10 000,00",
				"Dop":""
			}],
			"material":[
			{
				"Number":1,
				"Title":"Лист жести",
				"Artikul":"",
				"Comments":"",
				"Kol":"5,00",
				"Edizm":"шт.",
				"Price":"1 500,00",
				"Nalog":"0,00",
				"Summa":"7 500,00",
				"Dop":""
			}],
			"stroka":"",
			"InvoiceSumma":"185 046,00",
			"TotalSumma":"185 046,00",
			"InvoiceSumma":"185 046,00",
			"ItogSumma":"185 046,00",
			"ItogTovar":"175 046,00",
			"summaTovarPropis":"'.mb_ucfirst( trim( num2str( 175046.00 ) ) ).'",
			"ItogUsluga":"10 000,00",
			"summaUslugaPropis":"'.mb_ucfirst( trim( num2str( 10000.00 ) ) ).'",
			"ItogMaterial":"7 500,00",
			"summaMaterialPropis":"'.mb_ucfirst( trim( num2str( 7500.00 ) ) ).'",
			"nalogSumma":"0,00",
			"nalogName":"в т.ч. НДС",
			"nalogTitle":"НДС",
			"AktNumber":"12",
			"AktDate":"'.format_date_rus( current_datum() ).'",
			"AktSumma":"185 046,00",
			"AktSummaPropis":"Сто восемдесят пять тысяч сорок шесть рублей 00 копеек",
			"AktComment":"",
			"suff":"",
			"suffix":"",
			"suffixinc":"",
			"colspan":6,
			"dopName":"",
			"dopsName":"",
			"InvoiceSummaPropis":" Сто восемдесят пять тысяч сорок шесть рублей 00 копеек",
			"dealFsumma":"175 046,00",
			"dealFmarga":"34 239,00",
			"dealFinput7":"",
			"dealFinput1":"",
			"dealFinput6":"",
			"dealFinput2":"",
			"dealFinput3":"",
			"dealFinput4":"",
			"dealFinput5":"",
			"dealFperiodStart":"",
			"dealFperiodEnd":"",
			"dealFtitle":"Заказ А5434",
			"castomerFtitle":"Рога и копыта",
			"castomerFphone":"7(342)254-55-77, 8(912)884-45-55",
			"castomerFmail_url":"info@roga.su",
			"castomerFsite_url":"www.roga.su",
			"castomerFaddress":"Россия, Пермь, улица Пушкина 60",
			"castomerFfax":"",
			"castomerFinput1":"3 - постоянные закупки",
			"castomerFinput3":"Свердловский",
			"castomerFinput4":"Вариант 1, Вариант 5",
			"castomerFinput2":"1999-05-20",
			"castomerFinput5":"Вариант 2",
			"castomerFinput6":"Вариант 2",
			"castomerFinput7":"40-50",
			"castomerFinput8":"",
			"UserName":"Марусин Андрей Вениаминович",
			"UserStatus":"Руководитель отдела",
			"UserPhone":"79031706342",
			"UserMob":"",
			"UserEmail":"marand@omadaru.ru"
		}' ), true );

	$file = $_REQUEST['file'];
	$tmp  = $_REQUEST['tmp'];

	$tags['suffix'] = '<p>&nbsp;</p><p><b>ВНИМАНИЕ! СЧЕТ ДЕЙСТВИТЕЛЕН В ТЕЧЕНИЕ 5 ДНЕЙ С ДАТЫ СОЗДАНИЯ.</b></p><p align="justify">Стороны договорились, что стоимость услуг оплачивается Покупателем в размере 100 (сто) % от стоимости, указанной в вышеприведённой таблице. Датой оплаты счета считается дата списания денежных средств с расчетного счета Покупателя.</p><p>Настоящий счет является договором-офертой в соответствии со ст.435 ГК РФ. Настоящая оферта действительна и ее акцепт возможен <b>&nbsp;в течение 5 (пяти)&nbsp;&nbsp;</b>&nbsp; рабочих дней с момента выставления счета. При осуществлении оплаты по окончании срока действия оферты, Поставщик вправе потребовать доплаты, при условии изменения текущей розничной стоимости товара, либо не принимать оплату. Оплата по настоящему счёту является заключением договора об оказании услуг, при условии принятия ее поставщиком, в том числе по истечении срока, установленного для акцепта данной оферты.</p><p>&nbsp;</p><p><b>Датой окончания предоставления услуги считается дата подписания обеими Сторонами акта приёма-передачи работ. Настоящий счёт-договор вступает в силу с момента его оплаты Покупателем и действует до момента исполнения всех обязательств по нему.</b></p>';

	$html = ($tmp != '') ? file_get_contents( $rootpath.'/cash/'.$fpath.'templates/'.$tmp ) : file_get_contents( $rootpath.'/cash/'.$fpath.'templates/'.$file );

	$tags['forPRINT'] = '1';

	//обработка через шаблонизатор
	//require_once $rootpath."/opensource/Mustache/Autoloader.php";

	Mustache_Autoloader ::register();

	$m    = new Mustache_Engine();
	$html = $m -> render( $html, $tags );

	print $html;

	exit();

}

//Редактирование категории
if ( $action == "save" ) {

	$good    = 0;
	$bad     = 0;
	$message = '';
	//Сохраним настройки для генератора номеров счетов и номеров договоров
	$akt_num  = $_POST['akt_num'];
	$akt_step = $_POST['akt_step'];

	$db -> query( "update ".$sqlname."settings set akt_num = '$akt_num', akt_step = '$akt_step' WHERE id = '$identity'" );

	print "Сделано";

	unlink( $rootpath."/cash/".$fpath."settings.all.json" );

	exit();
}
if ( $action == "edit.on" ) {

	$temp = $_REQUEST['file'];
	$f    = $rootpath.'/cash/'.$fpath.'templates/'.$temp;
	$file = fopen( $f, 'wb' );

	$content = str_replace( [
		'\r',
		'\n',
		'\\'
	], [
		"\r",
		"\n",
		''
	], $_REQUEST['content'] );

	if ( fwrite( $file, $content ) ) {
		print "Сохранено";
	}

	exit();

}
if ( $action == 'clone.on' ) {

	$id    = $_REQUEST['id'];
	$title = $_REQUEST['title'];

	if ( $id == 0 ) {

		$file    = uniqid( "ACT", false )."_akt.tpl";
		$content = str_replace( "<!--Базовый шаблон-->\n", "", file_get_contents( $rootpath.'/cash/'.$fpath.'templates/akt_full.tpl' ) );

		$content = "<!--".$title."-->\n".$content;

		$typeID = (int)$db -> getOne( "SELECT id FROM ".$sqlname."contract_type WHERE type = 'get_akt' AND identity = '$identity'" );

		if ( $typeID == 0 ) {

			$db -> query( "INSERT INTO {$sqlname}contract_type SET ?u", [
				"title"    => "Акт приема-передачи",
				"type"     => "get_akt",
				"identity" => $identity
			] );
			$typeID = $db -> insertId();

		}

		$db -> query( "INSERT INTO ".$sqlname."contract_temp SET ?u", [
			"title"    => $title,
			"typeid"   => $typeID,
			"file"     => $file,
			"identity" => $identity
		] );

		file_put_contents( $rootpath.'/cash/'.$fpath.'templates/'.$file, $content );

	}
	else {

		$db -> query( "UPDATE ".$sqlname."contract_temp SET ?u WHERE id = '$id'", ["title" => $title] );

	}

	print "Выполнено";

	exit();

}
if ( $action == 'delete.temp' ) {

	$id = (int)$_REQUEST['id'];

	$file = $db -> getOne( "SELECT file FROM ".$sqlname."contract_temp WHERE id = '$id' AND identity = '$identity'" );

	$db -> query( "DELETE FROM ".$sqlname."contract_temp WHERE id = '$id'" );
	unlink( $rootpath.'/cash/'.$fpath.'templates/'.$file );

	print "ok";

}

if ( $action == "restore" ) {

	$temp = $_REQUEST['temp'];

	$file = $rootpath."/cash/templates.zip";

	try {

		$zip = zip_open( $file );

	}
	catch ( Exception $e ) {

		echo $e -> getMessage();

	}

	if ( $zip ) {

		while ($zip_entry = zip_read( $zip )) {

			if ( zip_entry_name( $zip_entry ) == $temp && zip_entry_open( $zip, $zip_entry, "r" ) ) {

				$buf = zip_entry_read( $zip_entry, zip_entry_filesize( $zip_entry ) );

				zip_entry_close( $zip_entry );
			}

		}

		zip_close( $zip );

	}

	print $buf;
	exit();

}

if ( empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) != 'xmlhttprequest' ) {

	print '<div class="bad text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ( $action == 'clone' ) {

	$id = (int)$_REQUEST['id'];

	if ( $id > 0 )
		$title = $db -> getOne( "SELECT title FROM ".$sqlname."contract_temp WHERE id = '$id' AND identity = '$identity'" );

	?>
	<div class="zagolovok">Файл шаблона</div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="temp" id="temp">
		<INPUT type="hidden" name="action" id="action" value="clone.on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabs" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden" class="p5">

			<div class="flex-container mb10">

				<div class="flex-string wp100">Название шаблона</div>
				<div class="flex-string wp100">
					<INPUT name="title" type="text" class="wp100 required" id="title" maxlength="100" value="<?= $title ?>" placeholder="Например: Приема-передачи (модифицированный))">
				</div>

			</div>

			<div class="infodiv">
				<div class="Bold blue">Рекомендация:</div>

				<ul>
					<li>Название шаблона должно отображать его особенности</li>
					<li>Название должно содержать максимум 100 символов (включая пробелы и знаки препинания)</li>
					<li>Рекомендуется односложное название - "Расширенный", "Физ.лица" и т.п.</li>
				</ul>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#temp').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<script>

		$(function () {

			$('#dialog').center();

		});

		$('#temp').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');

				$out.empty().css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

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
if ( $action == 'edit' ) {

	$file    = $_REQUEST['file'];
	$content = file_get_contents( $rootpath.'/cash/'.$fpath.'templates/'.$file );

	?>
	<style>
		#coder,
		.CodeMirror {
			width  : 99%;
			height : 80vh !important;
		}
		.CodeMirror-scroll {
			height : 80vh;
		}
	</style>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="temp" id="temp">
		<INPUT type="hidden" name="action" id="action" value="edit.on">
		<INPUT type="hidden" name="file" id="file" value="<?= $file ?>">
		<INPUT type="hidden" name="tmp" id="tmp" value="">

		<div class="zagolovok">Редактирование шаблона</div>

		<div class="flex-container" style="height: 80vh;">

			<div class="flex-string relativ wp50">

				<div class="pad5 pull-right panel zindex-20 p10" style="top:-5px; right: 10px;">

					<div class="inline relativ">

						<div class="tagsmenuToggler hand inline mr10" data-id="fhelper">

							<span class="gray2 Bold"><i class="icon-help-circled"></i>HotKeys</span>
							<div class="tagsmenu hidden" id="fhelper" style="left: inherit; right: 0">
								<div class="blok p10 w200 fs-09">
									<b>Ctrl-Q</b> - свернуть/развернуть блок<br>
									<b>Ctrl-B</b> - жирный текст<br>
									<b>Ctrl-U</b> - подчеркнутый текст<br>
									<b>Ctrl-I</b> - наклонный текст
								</div>
							</div>

						</div>

					</div>
					<div class="inline relativ" id="tagsmenu">

						<a href="javascript:void(0)" title="Действия" class="tagsmenuToggler"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
						<div class="hidden tagsmenu" style="left: inherit; right: 0">
							<ul>
								<li title="Название валюты"><b class="blue">{{currencyName}}</b></li>
								<li title="Знак валюты"><b class="blue">{{currencySymbol}}</b></li>
								<li title="Курс валюты"><b class="blue">{{currencyCourse}}</b></li>

								<li title="Номер Акта"><b class="green">{{AktNumber}}</b></li>
								<li title="Дата Акта"><b class="green">{{AktDate}}</b></li>
								<li title="Дата Акта"><b class="green">{{AktDateShort}}</b></li>
								<li title="Сумма Акта"><b class="green">{{AktSumma}}</b></li>
								<li title="Сумма Акта. Прописью"><b class="green">{{AktSummaPropis}}</b></li>

								<li title="Ответственный. ФИО"><b class="broun">{{UserName}}</b></li>
								<li title="Ответственный. Должность"><b class="broun">{{UserStatus}}</b></li>
								<li title="Ответственный. Телефон"><b class="broun">{{UserPhone}}</b></li>
								<li title="Ответственный. Мобильный"><b class="broun">{{UserMob}}</b></li>
								<li title="Ответственный. Email"><b class="broun">{{UserEmail}}</b></li>

								<li title="Юридическое название нашей компании"><b class="red">{{compUrName}}</b></li>
								<li title="Краткое юр. название нашей компании"><b class="red">{{compShotName}}</b></li>
								<li title="Наш юр.адрес"><b class="red">{{compUrAddr}}</b></li>
								<li title="Наш почтовый адрес"><b class="red">{{compFacAddr}}</b></li>
								<li title="ИНН нашей компании"><b class="red">{{compInn}}</b></li>
								<li title="КПП нашей компании"><b class="red">{{compKpp}}</b></li>
								<li title="ОГРН нашей компании"><b class="red">{{compOgrn}}</b></li>
								<li title="ОКПО нашей компании"><b class="red">{{compOkpo}}</b></li>
								<li title="Наш банк"><b class="red">{{compBankName}}</b></li>
								<li title="БИК нашего банка"><b class="red">{{compBankBik}}</b></li>
								<li title="наш Расчетный счет"><b class="red">{{compBankRs}}</b></li>
								<li title="Корр.счет нашего банка"><b class="red">{{compBankKs}}</b></li>
								<li title="Подпись руководителя, изображение"><b class="red">{{signature}}</b></li>
								<li title="ФИО руководителя (В контексте «в лице кого»)"><b class="red">{{compDirName}}</b></li>
								<li title="Должность руководителя (Директор, Генеральный директор)"><b class="red">{{compDirStatus}}</b></li>
								<li title="Должность руководителя (краткая, Петров И.И.)"><b class="red">{{compDirSignature}}</b></li>
								<li title="На основании чего действует руководитель (Устава, Доверенности..)"><b class="red">{{compDirOsnovanie}}</b></li>
								<li title="Название Бренда"><b class="red">{{compBrand}}</b></li>
								<li title="Сайт Бренда"><b class="red">{{compSite}}</b></li>
								<li title="Email Бренда"><b class="red">{{compMail}}</b></li>
								<li title="Телефон Бренда"><b class="red">{{compPhone}}</b></li>

								<li title="Название Клиента (Как отображается в CRM)"><b class="blue">{{castName}}</b></li>
								<li title="Юридическое название Клиента (из реквизитов)"><b class="blue">{{castUrName}}</b>
								<li title="Юридическое название Клиента (из реквизитов), краткое"><b class="blue">{{castUrNameShort}}</b></li>
								<li title="ИНН Клиента (из реквизитов)"><b class="blue">{{castInn}}</b></li>
								<li title="КПП Клиента (из реквизитов)"><b class="blue">{{castKpp}}</b></li>
								<li title="Банк Клиента (из реквизитов)"><b class="blue">{{castBank}}</b></li>
								<li title="Кор.счет Клиента (из реквизитов)"><b class="blue">{{castBankKs}}</b></li>
								<li title="Расч.счет Клиента (из реквизитов)"><b class="blue">{{castBankRs}}</b></li>
								<li title="БИК банка Клиента (из реквизитов)"><b class="blue">{{castBankBik}}</b></li>
								<li title="ОКПО Клиента (из реквизитов)"><b class="blue">{{castOkpo}}</b></li>
								<li title="ОГРН Клиента (из реквизитов)"><b class="blue">{{castOgrn}}</b></li>
								<li title="ФИО руководителя Клиента, в родительном падеже (в лице кого) - Иванова Ивана Ивановича (из реквизитов)"><b class="blue">{{castDirName}}</b></li>
								<li title="ФИО руководителя Клиента, например Иванов И.И. (из реквизитов)"><b class="blue">{{castDirSignature}}</b></li>
								<li title="Должность руководителя Клиента, в род.падеже, например: Директора (из реквизитов)"><b class="blue">{{castDirStatus}}</b></li>
								<li title="Должность руководителя Клиента, например: Директор (из реквизитов)"><b class="blue">{{castDirStatusSig}}</b></li>
								<li title="Основание прав Руководителя Клиента, в родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г. (из реквизитов)"><b class="blue">{{castDirOsnovanie}}</b></li>
								<li title="Юр.адрес Клиента (из реквизитов)"><b class="blue">{{castUrAddr}}</b></li>
								<li title="Фактич.адрес Клиента (из реквизитов)"><b class="blue">{{castFacAddr}}</b></li>

								<li title="Заказчик. Название (Как отображается в CRM)"><b class="blue">{{castomerFtitle}}</b></li>
								<li title="Заказчик. Адрес"><b class="blue">{{castomerFaddress}}</b></li>
								<li title="Заказчик. Телефон"><b class="blue">{{castomerFphone}}</b></li>
								<li title="Заказчик. Факс"><b class="blue">{{castomerFfax}}</b></li>
								<li title="Заказчик. Email"><b class="blue">{{castomerFmail_url}}</b></li>
								<li title="Заказчик. Сайт"><b class="blue">{{castomerFsite_url}}</b></li>

								<?php
								$re = $db -> getAll( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '".$identity."' order by fld_order" );
								foreach ( $re as $d ) {

									print '<li title="Заказчик. '.$d['fld_title'].'"><b class="blue">{{castomerF'.$d['fld_name'].'}}</b></li>';

									if($d['fld_temp'] == 'datum'){
										print '<li title="Заказчик. '.$d['fld_title'].' - ДД.ММ.ГГГГ"><b class="blue">{{castomerF'.$d['fld_name'].'Format}}</b></li>';
									}
									elseif($d['fld_temp'] == 'datetime'){
										print '<li title="Заказчик. '.$d['fld_title'].' - ДД.ММ.ГГГГ ЧЧ:ММ"><b class="blue">{{castomerF'.$d['fld_name'].'Format}}</b></li>';
									}

								}
								?>
								<li title="Массив с данными Спецификации"><b class="broun">{{speka}}</b></li>
								<li title="Массив с данными Спецификации. Только Товары"><b class="broun">{{tovar}}</b></li>
								<li title="Массив с данными Спецификации. Только Услуги"><b class="broun">{{usluga}}</b></li>
								<li title="Массив материалов спецификации"><b class="broun">{{material}}</b></li>

								<li title="Номер счета (из сделки)"><b class="green">{{Invoice}}</b></li>
								<li title="Дата счета (в формате: 29 февраля 2014 года)"><b class="green">{{InvoiceDate}}</b></li>
								<li title="Дата счета (в формате: 29.02.2014)"><b class="green">{{InvoiceDateShort}}</b></li>
								<li title="Дата оплаты плановая (в формате: 29 февраля 2014 года)"><b class="green">{{InvoiceDatePlan}}</b></li>
								<li title="Дата оплаты плановая (в формате: 29.02.2014)"><b class="green">{{InvoiceDatePlanShort}}</b></li>
								<!--<li title="Номер акта (из сделки)"><b class="green">{{AktNumber}}</b></li>-->
								<!--<li title="Дата акта (из сделки)"><b class="green">{{AktDate}}</b></li>-->
								<li title="Сумма прописью (сумма счета)"><b class="green">{{InvoiceSummaPropis}}</b></li>
								<li title="Сумма прописью (сумма товара)"><b class="green">{{summaTovarPropis}}</b></li>
								<li title="Сумма прописью (сумма услуг)"><b class="green">{{summaUslugaPropis}}</b></li>
								<li title="Сумма прописью (сумма материалов)"><b class="green">{{summaMaterialPropis}}</b></li>
								<li title="Общая сумма сделки (из сделки)"><b class="green">{{InvoiceSumma}}</b></li>
								<li title="Сумма позиций счета (из счета). При налоге 'сверху' не включает налог"><b class="green">{{ItogSumma}}</b></li>
								<!--<li title="Сумма НДС (из сделки)"><b class="green">{{summa_nds}}</b></li>-->
								<li title="Сумма НДС (из сделки)"><b class="green">{{nalogSumma}}</b></li>
								<li title="Название налога (например, в т.ч. НДС)"><b class="green">{{nalogName}}</b></li>
								<li title="Название налога (например, НДС)"><b class="green">{{nalogTitle}}</b></li>
								<li title="Номер договора (из сделки)"><b class="green">{{ContractNumber}}</b></li>
								<li title="Дата договора (из сделки)"><b class="green">{{ContractDate}}</b></li>
								<li title="Название сделки"><b class="green">{{dealFtitle}}</b></li>
								<li title="Сумма сделки"><b class="green">{{dealFsumma}}</b></li>
								<li title="Маржа сделки"><b class="green">{{dealFmarga}}</b></li>
								<?php
								$res = $db -> getAll( "select * from ".$sqlname."field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '".$GLOBALS['identity']."' order by fld_order" );
								foreach ( $res as $data ) {

									print '<li title="'.$data['fld_title'].'"><b class="green">{{dealF'.$data['fld_name'].'}}</b></li>';

									if($data['fld_temp'] == 'datum'){
										print '<li title="'.$data['fld_title'].' - ДД.ММ.ГГГГ"><b class="green">{{dealF'.$data['fld_name'].'Format}}</b></li>';
									}
									elseif($data['fld_temp'] == 'datetime'){
										print '<li title="'.$data['fld_title'].' - ДД.ММ.ГГГГ ЧЧ:ММ"><b class="green">{{dealF'.$data['fld_name'].'Format}}</b></li>';
									}

								}
								?>
								<li title="Период. Начало (из сделки)"><b class="green">{{dealFperiodStart}}</b></li>
								<li title="Период. Конец (из сделки)"><b class="green">{{dealFperiodEnd}}</b></li>
							</ul>
						</div>

					</div>

				</div>

				<div id="coder"></div>
				<textarea name="content" class="des hidden" id="content" spellcheck="false" style="width:99.5%; height:80vh; font-size:1.0em; background:#E6E9ED; border:1px solid #656D78; color:#222; padding:10px"><?= $content ?></textarea>

			</div>
			<div class="flex-string wp50">

				<iframe id="invoiceView" style="width:99.5%; height:80vh; font-size:1.1em; background:#E6E9ED; border:1px solid #656D78; color:#222;"></iframe>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<div class="pull-left pl5 wp50">

				<div class="inline mr10 relativ" data-id="fhelper">

					<A href="javascript:void(0)" class="button tagsmenuToggler"><i class="icon-ccw"></i>Восстановить</A>
					<div class="tagsmenu top left hidden" id="fhelper" style="">
						<div class="items fs-09">

							<div class="item ha hand" data-file="invoice.tpl" onclick="restoreTemp('akt_prava.tpl')">
								<i class="icon-doc-text-inv blue"></i> Акт на права&nbsp;
							</div>
							<div class="item ha hand" data-file="pko_invoice.tpl" onclick="restoreTemp('akt_simple.tpl')">
								<i class="icon-doc-text-inv blue"></i> Акт на услуги&nbsp;
							</div>
							<div class="item ha hand" data-file="pko_invoice.tpl" onclick="restoreTemp('akt_full.tpl')">
								<i class="icon-doc-text-inv blue"></i> Акт расширенный&nbsp;
							</div>
							<div class="item ha hand" data-file="pko_invoice.tpl" onclick="restoreTemp('pko_invoice.tpl')">
								<i class="icon-doc-text-inv blue"></i> Шаблон ПКО&nbsp;
							</div>

						</div>
					</div>

				</div>

			</div>

			<A href="javascript:void(0)" onclick="sendForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>

	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/lib/codemirror.css">
	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/theme/idea.css">
	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/addon/fold/foldgutter.css"/>
	<link type="text/css" rel="stylesheet" href="/assets/js/codemirror/addon/lint/lint.css">
	<script src="/assets/js/codemirror/lib/codemirror.js"></script>
	<script src="/assets/js/codemirror/addon/fold/foldcode.js"></script>
	<script src="/assets/js/codemirror/addon/fold/foldgutter.js"></script>
	<script src="/assets/js/codemirror/addon/fold/brace-fold.js"></script>
	<script src="/assets/js/codemirror/addon/fold/xml-fold.js"></script>
	<script src="/assets/js/codemirror/addon/fold/indent-fold.js"></script>
	<script src="/assets/js/codemirror/addon/fold/comment-fold.js"></script>
	<script src="/assets/js/codemirror/addon/lint/lint.js"></script>
	<script src="/assets/js/codemirror/addon/lint/css-lint.js"></script>
	<script src="/assets/js/codemirror/addon/lint/html-lint.js"></script>
	<script src="/assets/js/codemirror/addon/selection/active-line.js"></script>
	<script src="/assets/js/codemirror/addon/edit/closetag.js"></script>
	<script src="/assets/js/codemirror/addon/edit/matchtags.js"></script>
	<script src="/assets/js/codemirror/mode/htmlmixed/htmlmixed.js"></script>
	<script src="/assets/js/codemirror/mode/xml/xml.js"></script>
	<script src="/assets/js/codemirror/mode/css/css.js"></script>
	<script>

		var editorCodeMirror;

		$(function () {

			$('#content').trigger('keyup');

			$('#dialog').css('width', '95vw');//.css('height','90%');

			$('#temp').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					$out.empty();
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;
				},
				success: function (data) {

					$('#dialog').removeAttr('height');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
					DClose();

					//$("#contentdiv").load('admin/invoiceeditor.php');
				}
			});

			$('.tagsmenu li').on('click', function () {

				var $t = $('b', this).html();

				if (!in_array($t, ['{{speka}}', '{{tovar}}', '{{usluga}}', '{{material}}'])) {

					insertTextAtCursor($t);

				}
				else {

					var s1, s2, itog, h1;

					switch ($t) {

						case "{{speka}}":

							s1 = "{{#speka}}";
							s2 = "{{/speka}}";
							itog = "{{ItogMaterial}}";
							h1 = "Товары (работы, услуги)";

							break;
						case "{{tovar}}":

							s1 = "{{#tovar}}";
							s2 = "{{/tovar}}";
							itog = "{{ItogTovar}}";
							h1 = "Товары";

							break;
						case "{{usluga}}":

							s1 = "{{#usluga}}";
							s2 = "{{/usluga}}";
							itog = "{{ItogUsluga}}";
							h1 = "Услуги";

							break;
						case "{{material}}":

							s1 = "{{#material}}";
							s2 = "{{/material}}";
							itog = "{{ItogMaterial}}";
							h1 = "Материалы";

							break;

					}

					$t = '\t<table width="{{#forPRINT}}100%{{/forPRINT}}{{#forPDF}}100%{{/forPDF}}" border="0" cellpadding="4" cellspacing="0">\n' +
						'\t\t<tr>\n' +
						'\t\t\t<td width="20" align="center" valign="middle" bgcolor="#E9E9E9" style="height:18pt" class="bt br bb bl">№</td>\n' +
						'\t\t\t<td align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">' + h1 + '</td>\n' +
						'\t\t\t<td width="50" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Кол.</td>\n' +
						'\t\t\t{{#dopName}}\n' +
						'\t\t\t<td width="70" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">{{dopName}}</td>\n' +
						'\t\t\t{{/dopName}}\n' +
						'\t\t\t<td width="30" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Ед.</td>\n' +
						'\t\t\t<td width="60" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">Цена</td>\n' +
						'\t\t\t{{#nalogTitle}}\n' +
						'\t\t\t<td width="60" align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br">{{nalogTitle}}</td>\n' +
						'\t\t\t{{/nalogTitle}}\n' +
						'\t\t\t<td align="center" valign="middle" bgcolor="#E9E9E9" class="bt bb br w60">Сумма</td>\n' +
						'\t\t</tr>\n' +
						'\t\t' + s1 + '\n' +
						'\t\t\t<tr class="small">\n' +
						'\t\t\t\t<td width="20" align="center" class="bt br bb bl">{{Number}}</td>\n' +
						'\t\t\t\t<td align="left" valign="middle" class="bb br">\n' +
						'\t\t\t\t\t<div style="display: block; width:96%;">{{#Artikul}}[{{Artikul}}]  {{/Artikul}}<b>{{Title}}</b></div>\n' +
						'\t\t\t\t\t<em>{{Comments}}</em>\n' +
						'\t\t\t\t</td>\n' +
						'\t\t\t\t<td width="50" align="right" valign="middle" class="bb br">{{Kol}}</td>\n' +
						'\t\t\t\t{{#dopName}}\n' +
						'\t\t\t\t<td width="70" align="right" valign="middle" class="bb br">{{Dop}}</td>\n' +
						'\t\t\t\t{{/dopName}}\n' +
						'\t\t\t\t<td width="30" align="center" valign="middle" class="bb br">{{Edizm}}</td>\n' +
						'\t\t\t\t<td width="60" align="right" valign="middle" class="bb br">{{Price}}</td>\n' +
						'\t\t\t\t{{#nalogTitle}}\n' +
						'\t\t\t\t<td width="60" align="right" valign="middle" class="bb br">{{Nalog}}</td>\n' +
						'\t\t\t\t{{/nalogTitle}}\n' +
						'\t\t\t\t<td align="right" valign="middle" class="bb br w60">{{Summa}}</td>\n' +
						'\t\t\t</tr>\n' +
						'\t\t' + s2 + '\n' +
						'\t</table>\n' +
						'\n' +
						'\t<table width="{{#forPRINT}}100%{{/forPRINT}}{{#forPDF}}100%{{/forPDF}}" border="0" cellpadding="4" cellspacing="0">\n' +
						'\t\t<tr>\n' +
						'\t\t\t<td class="br" align="right">Итого:  </td>\n' +
						'\t\t\t<td align="right" class="bb br w60" style="height:14px">' + itog + '</td>\n' +
						'\t\t</tr>\n' +
						'\t</table>';

					//insTextAtCursor('content', $t);
					insertText($t);

				}

				$('.tagsmenu').addClass('hidden');

			});

			editorCodeMirror = CodeMirror(document.getElementById("coder"), {
				value: $('#content').text(),
				mode: "htmlmixed",
				lineNumbers: true,
				lineWrapping: false,
				smartIndent: true,
				tabSize: 4,
				indentWithTabs: true,
				theme: 'idea',
				extraKeys: {
					"Ctrl-Q": function (cm) {
						cm.foldCode(cm.getCursor());
					},
					"Ctrl-J": "toMatchingTag",
					// bold
					'Ctrl-B': function (cm) {
						var s = cm.getSelection(),
							t = s.slice(0, 3) === '<b>' && s.slice(-4) === '</b>';
						cm.replaceSelection(t ? s.slice(3, -4) : '<b>' + s + '</b>', 'around');
					},
					// italic
					'Ctrl-I': function (cm) {
						var s = cm.getSelection(),
							t = s.slice(0, 3) === '<i>' && s.slice(-4) === '</i>';
						cm.replaceSelection(t ? s.slice(3, -4) : '<i>' + s + '</i>', 'around');
					},
					// underline
					'Ctrl-U': function (cm) {
						var s = cm.getSelection(),
							t = s.slice(0, 3) === '<u>' && s.slice(-4) === '</u>';
						cm.replaceSelection(t ? s.slice(3, -4) : '<u>' + s + '</u>', 'around');
					},
				},
				foldGutter: true,
				gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter", "CodeMirror-lint-markers"],
				lint: true,
				styleActiveLine: true,
				styleActiveSelected: true,
				autoCloseTags: true,
				matchTags: {bothTags: true}
			});

			$('#dialog').center();

			$('#content').trigger('keyup');

		});

		function insertText(data) {

			var cm = $(".CodeMirror")[0].CodeMirror;
			var doc = cm.getDoc();
			var cursor = doc.getCursor(); // gets the line number in the cursor position
			var line = doc.getLine(cursor.line); // get the line contents
			var pos = {
				line: cursor.line
			};

			if (line.length === 0) {
				// check if the line is empty
				// add the data
				doc.replaceRange(data, pos);
			}
			else {
				// add a new line and the data
				doc.replaceRange("" + data, pos);
			}

			updateHtml();

		}

		/**
		 * Отлично работает
		 * @param text
		 */
		function insertTextAtCursor(text) {

			var editor = $(".CodeMirror")[0].CodeMirror;
			var doc = editor.getDoc();
			var cursor = doc.getCursor();
			doc.replaceRange(text, cursor);

			updateHtml();

			$('#content').trigger('keyup');

		}

		function sendForm() {

			var content = editorCodeMirror.getValue();
			$('#content').val(content);

			$('#temp').trigger('submit');

		}

		function updateHtml() {

			var content = editorCodeMirror.getValue();
			$('#content').val(content);

			$('#content').trigger('keyup');

		}

		function restoreTemp(temp) {

			$.get("/content/admin/<?php echo $thisfile; ?>?action=restore&temp=" + temp, function (data) {

				$('#content').val(data);
				editorCodeMirror.setValue(data);

				yNotifyMe("CRM. Загружен исходный шаблон");

				updateHtml();

			})
		}

		$('#coder').on('keyup', function () {

			updateHtml();

		});

		$('#content').on('keyup', function () {

			var str = $('#temp').serialize();

			$.post('/content/admin/<?php echo $thisfile; ?>?act=tmp', str, function (data) {

				var url = '';

				if (data !== '') {

					$('#tmp').val(data);
					url = '/content/admin/<?php echo $thisfile; ?>?action=preview&tmp=' + data;

				}
				else {

					url = '/content/admin/<?php echo $thisfile; ?>?action=preview&file=' + $('#file').val();

				}

				$('#invoiceView').attr('src', url);

			});


		});

	</script>
	<?php
	exit();
}

if ( $action == "" ) {
	?>

	<h2>&nbsp;Раздел: &quot;Настройка актов"</h2>

	<?php
	if(!$otherSettings['contract']){

		print '<div class="warning mb10">Ведение Договоров <b>отключено</b> (см. Общие настройки / Дополнения к сделкам)</div>';

	}
	?>

<FORM action="/content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="set" id="set">
	<INPUT type="hidden" name="action" id="action" value="save">

	<h2 class="blue mt20 mb20 pl5">Генератор номеров актов</h2>

	<div class="flex-container mt20 box--child ha p10 border-bottom">

		<div class="flex-string wp20 right-text fs-12 gray2 pt5">Счетчик актов:</div>
		<div class="flex-string wp80 pl10">

			<input name="akt_num" type="text" id="akt_num" size="10" value="<?= $akt_num ?>"/>
			<div class="smalltxt black">Укажите номер последнего акта. <b>Если актов не было укажите 0 (ноль)</b>.</div>

		</div>

	</div>

	<div class="flex-container box--child ha p10 border-bottom">

		<div class="flex-string wp20 right-text fs-12 gray2 pt5">Этап сделки:</div>
		<div class="flex-string wp80 pl10">

			<span class="select">
			<select name="akt_step" id="akt_step" class="required">
				<option value="">--Выбор--</option>
				<?php
				$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
				foreach ( $result as $data ) {

					print '<option '.($data['idcategory'] == $akt_step ? "selected" : "").' value="'.$data['idcategory'].'">'.$data['title']."%-".$data['content'].'</option>';

				}
				?>
			</select>
			</span>
			<div class="smalltxt black">Укажите этап сделки с которого доступен Акт. Формирование Акта будет доступно только начиная с этого этапа.</div>

		</div>

	</div>

	<h2 class="blue mt20 mb20 pl5">Шаблоны актов</h2>

	<div class="border--bottom border-bottom pb10">

		<?php
		$ires = $db -> query( "SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('get_akt','get_aktper') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
		while ($data = $db -> fetch( $ires )) {
			?>

			<div class="flex-container float box--child ha p10">

				<div class="flex-string w10">&nbsp;</div>
				<div class="flex-string w400">

					<div class="green fs-11 Bold"><?= $data['title'] ?></div>
					<div class="gray fs-09 mt5 mb5">
						<span class="enable--select"><?= $data['file'] ?></span>, <?= date( 'd.m.Y H:i', filemtime( $rootpath.'/cash/'.$fpath.'templates/'.$data['file'] ) ) ?>
					</div>
					<?php
					$file = $rootpath.'/cash/'.$fpath.'templates/'.$data['file'];
					if ( file_exists( $file ) && !is_writable( $file ) )
						print '
						<div class="attention">
							<i class="icon-attention red" title=""></i> Проверьте права на запись файла: '.str_replace( "../", "", $file ).'.
						</div>
						';
					?>

				</div>
				<div class="flex-string float">

					<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit&file=<?= $data['file'] ?>&id=<?= $data['id'] ?>')" title="Редактировать шаблон"><i class="icon-doc-text-inv blue"></i></a>&nbsp;

					<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=clone&id=<?= $data['id'] ?>')" title="Редактировать название"><i class="icon-pencil green"></i></a>&nbsp;

					<a href="javascript:void(0)" onclick="deleteTemp('<?= $data['id'] ?>')" class="<?= (in_array( $data['file'], [
						'akt_full.tpl',
						'akt_simple.tpl',
						'akt_prava.tpl'
					] ) ? 'hidden' : '') ?>" title="Редактировать название"><i class="icon-cancel-circled red"></i></a>

				</div>

			</div>

			<?php
		}
		?>

		<div class="pl5 mt20">

			<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=clone')" class="button greenbtn fs-09 p5 pl10 pr10"><i class="icon-plus-circled"></i>Добавить</a>

		</div>

	</div>

	<div class="flex-container box--child ha p10 border-bottom">

		<div class="flex-string wp20 right-text fs-12 gray2 pt10">Тэги (для шаблона):</div>
		<div class="flex-string wp80 pl10 enable--select">

			<ul class="simple p0 mt5">
				<li><b class="blue">{{currencyName}}</b> - Название валюты</li>
				<li><b class="blue">{{currencySymbol}}</b> - Знак валюты</li>
				<li><b class="blue">{{currencyCourse}}</b> - Курс валюты</li>
				<li>
					<hr>
				</li>
				<li><b class="green">{{AktNumber}}</b> - Номер Акта</li>
				<li><b class="green">{{AktDate}}</b> - Дата Акта</li>
				<li><b class="green">{{AktDateShort}}</b> - Дата Акта</li>
				<li><b class="green">{{AktSumma}}</b> - Сумма Акта</li>
				<li><b class="green">{{AktSummaPropis}}</b> - Сумма Акта. Прописью</li>
				<li>
					<hr>
				</li>
				<li><b class="broun">{{UserName}}</b> - Ответственный. ФИО</li>
				<li><b class="broun">{{UserStatus}}</b> - Ответственный. Должность</li>
				<li><b class="broun">{{UserPhone}}</b> - Ответственный. Телефон</li>
				<li><b class="broun">{{UserMob}}</b> - Ответственный. Мобильный</li>
				<li><b class="broun">{{UserEmail}}</b> - Ответственный. Email</li>
				<li>
					<hr>
				</li>
				<li><b class="red">{{compUrName}}</b> - Юридическое название нашей компании</li>
				<li><b class="red">{{compShotName}}</b> - Краткое юр. название нашей компании</li>
				<li><b class="red">{{compUrAddr}}</b> - Наш юр.адрес</li>
				<li><b class="red">{{compFacAddr}}</b> - Наш почтовый адрес</li>
				<li><b class="red">{{compInn}}</b> - ИНН нашей компании</li>
				<li><b class="red">{{compKpp}}</b> - КПП нашей компании</li>
				<li><b class="red">{{compOkpo}}</b> - ОКПО нашей компании</li>
				<li><b class="red">{{compBankName}}</b> - Наш банк</li>
				<li><b class="red">{{compBankBik}}</b> БИК нашего банка</li>
				<li><b class="red">{{compBankRs}}</b> - наш Расчетный счет</li>
				<li><b class="red">{{compBankKs}}</b> - Корр.счет нашего банка</li>
				<li><b class="red">{{compDirName}}</b> - ФИО руководителя (В контексте «в лице кого»)</li>
				<li><b class="red">{{compDirStatus}}</b> - Должность руководителя (Директор, Генеральный директор)</li>
				<li><b class="red">{{compDirSignature}}</b> - Должность руководителя (краткая, Петров И.И.)</li>
				<li><b class="red">{{compDirOsnovanie}}</b> - На основании чего действует руководитель (Устава, Доверенности..)</li>
				<li><b class="red">{{compBrand}}</b> - Название Бренда</li>
				<li><b class="red">{{compSite}}</b> - Сайт Бренда</li>
				<li><b class="red">{{compMail}}</b> - Email Бренда</li>
				<li><b class="red">{{compPhone}}</b> - Телефон Бренда</li>
				<li>
					<hr>
				</li>
				<li><b class="red">{{signature}}</b> - изображение Печать + Подпись руководителя</li>
				<li>
					<hr>
				</li>
				<li><b class="blue">{{castName}}</b> - Плательщик. Название (Как отображается в CRM)</li>
				<li><b class="blue">{{castUrName}}</b> - Плательщик. Юридическое название (из реквизитов)</li>
				<li><b class="blue">{{castUrNameShort}}</b> - Плательщик. Юридическое название (из реквизитов), краткое</li>
				<li><b class="blue">{{castInn}}</b> - Плательщик. ИНН (из реквизитов)</li>
				<li><b class="blue">{{castKpp}}</b> - Плательщик. КПП (из реквизитов)</li>
				<li><b class="blue">{{castBank}}</b> - Плательщик. Банк (из реквизитов)</li>
				<li><b class="blue">{{castBankKs}}</b> - Плательщик. Кор.счет (из реквизитов)</li>
				<li><b class="blue">{{castBankRs}}</b> - Плательщик. Расч.счет (из реквизитов)</li>
				<li><b class="blue">{{castBankBik}}</b> - Плательщик. БИК банка (из реквизитов)</li>
				<li><b class="blue">{{castOkpo}}</b> - Плательщик. ОКПО (из реквизитов)</li>
				<li><b class="blue">{{castOgrn}}</b> Плательщик. ОГРН (из реквизитов)</li>
				<li><b class="blue">{{castDirName}}</b> - Плательщик. ФИО руководителя, в родительном падеже (в лице кого) - Иванова Ивана Ивановича (из реквизитов)</li>
				<li><b class="blue">{{castDirSignature}}</b> - Плательщик. ФИО руководителя, например Иванов И.И. (из реквизитов)</li>
				<li><b class="blue">{{castDirStatus}}</b> - Плательщик. Должность руководителя, в род.падеже, например: Директора (из реквизитов)</li>
				<li><b class="blue">{{castDirStatusSig}}</b> - Плательщик. Должность руководителя, например: Директор (из реквизитов)</li>
				<li><b class="blue">{{castDirOsnovanie}}</b> - Плательщик. Основание прав Руководителя, в родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г. (из реквизитов)</li>
				<li><b class="blue">{{castUrAddr}}</b> - Плательщик. Юр.адрес (из реквизитов)</li>
				<li><b class="blue">{{castFacAddr}}</b> - Плательщик. Фактич.адрес (из реквизитов)</li>

				<li>
					<hr>
				</li>

				<li><b class="blue">{{castomerFtitle}}</b> - Заказчик. Название (Как отображается в CRM)</li>
				<li><b class="blue">{{castomerFaddress}}</b> - Заказчик. Адрес</li>
				<li><b class="blue">{{castomerFphone}}</b> - Заказчик. Телефон</li>
				<li><b class="blue">{{castomerFfax}}</b> - Заказчик. Факс</li>
				<li><b class="blue">{{castomerFmail_url}}</b> - Заказчик. Email</li>
				<li><b class="blue">{{castomerFsite_url}}</b> - Заказчик. Сайт</li>

				<?php
				$re = $db -> query( "select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '".$identity."' order by fld_order" );
				while ($d = $db -> fetch( $re )) {

					print '<li><b class="blue">{{castomerF'.$d['fld_name'].'}}</b> - Заказчик. '.$d['fld_title'].'</li>';

					if($data['fld_temp'] == 'datum'){
						print '<li><b class="blue">{{castomerF'.$data['fld_name'].'Format}}</b> - Заказчик. '.$data['fld_title'].' - ДД.ММ.ГГГГ</li>';
					}
					elseif($data['fld_temp'] == 'datetime'){
						print '<li><b class="blue">{{castomerF'.$data['fld_name'].'Format}}</b> - Заказчик. '.$data['fld_title'].' - ДД.ММ.ГГГГ ЧЧ:ММ</li>';
					}

				}
				?>

				<li>
					<hr>
				</li>

				<li><b class="green">{{ContractNumber}}</b> - Номер договора (из сделки)</li>
				<li><b class="green">{{ContractDate}}</b> - Дата договора (из сделки)</li>
				<li><b class="green">{{Invoice}}</b> - Номер счета (из сделки)</li>
				<li><b class="green">{{InvoiceDate}}</b> - Дата счета (в формате: 29 февраля 2014 года)</li>
				<li><b class="green">{{InvoiceDateShort}}</b> - Дата счета (в формате: 29.02.2014)</li>
				<!--<li><b class="green">{akt_num}</b> - Номер акта (из сделки)</li>
				<li><b class="green">{akt_date}</b> - Дата акта (из сделки, в формате: 29.02.2014)</li>-->
				<li>
					<hr>
				</li>
				<li><b class="broun">{{speka}}</b> - Массив с данными Спецификации</li>
				<li><b class="broun">{{tovar}}</b> - Массив с данными Спецификации. Только Товары</li>
				<li><b class="broun">{{usluga}}</b> - Массив с данными Спецификации. Только Услуги</li>
				<li><b class="broun">{{material}}</b> - Массив с материалами</li>
				<li>
					<hr>
				</li>
				<li>
					<b class="green">{{ItogSumma}}</b> - Сумма позиций счета (из счета). При налоге "сверху" не включает налог
				</li>
				<li><b class="green">{{InvoiceSummaPropis}}</b> - Сумма прописью (сумма сделки)</li>
				<li><b class="green">{{summaTovarPropis}}</b> - Сумма прописью (сумма товара)</li>
				<li><b class="green">{{summaUslugaPropis}}</b> - Сумма прописью (сумма услуг)</li>
				<li><b class="green">{{summaMaterialPropis}}</b> - Сумма прописью (сумма материалов)</li>
				<li><b class="green">{{ItogTovar}}</b> - Сумма товаров (из счета). При налоге "сверху" не включает налог</li>
				<li><b class="green">{{ItogUsluga}}</b> - Сумма услуг (из счета). При налоге "сверху" не включает налог</li>
				<li><b class="green">{{ItogMaterial}}</b> - Сумма материалов (из счета).</li>
				<li><b class="green">{{InvoiceSumma}}</b> - Общая сумма счета</li>
				<li><b class="green">{{nalogSumma}}</b> - Сумма НДС</li>
				<li><b class="green">{{nalogTovar}}</b> - Сумма НДС по товарам</li>
				<li><b class="green">{{nalogUsluga}}</b> - Сумма НДС по услугам</li>
				<li><b class="green">{{dealFtitle}}</b> - Название сделки</li>
				<li><b class="green">{{dealFsumma}}</b> - Сумма сделки</li>
				<li><b class="green">{{dealFmarga}}</b> - Маржа сделки</li>
				<li><b class="green">{{dealFperiodStart}}</b> - Период. Начало (из сделки)</li>
				<li><b class="green">{{dealFperiodEnd}}</b> - Период. Конец (из сделки)</li>

				<li>
					<hr>
				</li>
				<?php
				$res = $db -> getAll( "select * from ".$sqlname."field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '".$GLOBALS['identity']."' order by fld_order" );
				foreach ( $res as $data ) {

					print '<li><b class="green">{{dealF'.$data['fld_name'].'}}</b> - '.$data['fld_title'].' (из сделки)</li>';

					if($data['fld_temp'] == 'datum'){
						print '<li><b class="green">{{dealF'.$data['fld_name'].'Format}}</b> - '.$data['fld_title'].' - ДД.ММ.ГГГГ</li>';
					}
					elseif($data['fld_temp'] == 'datetime'){
						print '<li><b class="green">{{dealF'.$data['fld_name'].'Format}}</b> - '.$data['fld_title'].' - ДД.ММ.ГГГГ ЧЧ:ММ</li>';
					}

				}
				?>
			</ul>

		</div>

	</div>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="$('#set').trigger('submit')" class="button bluebtn box-shadow" title="Добавить"><i class="icon-ok-circled"></i>Сохранить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="$('#set').trigger('submit')" title="Сохранить">
		<i class="icon-ok-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/11')" title="Документация">
		<i class="icon-help"></i></div>

	<div class="space-100">&nbsp;</div>

	<script type="text/javascript">

		$(function () {

			$('#set').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');

					$out.empty();
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$("#contentdiv").load('/content/admin/<?php echo $thisfile; ?>');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});
			$('#dialog').center();

		});

		function deleteTemp(id) {

			Swal.fire(
				{
					title: 'Вы уверены?',
					text: "Восстановить удаленный шаблон не возможно!",
					type: 'question',
					showCancelButton: true,
					confirmButtonText: 'Продолжить',
					cancelButtonText: 'Отменить',
					customClass: {
						confirmButton: 'button greenbtn',
						cancelButton: 'button redbtn'
					},
				}
			).then((result) => {

				if (result.value) {

					$.get("/content/admin/akt_editor.php?action=delete.temp&id=" + id, function () {

						$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');

					});

				}

			});

		}

	</script>
	<?php
}