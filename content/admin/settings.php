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

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST[ 'action' ];

//$helper = json_decode(file_get_contents('../cash/helper.json'), true);

$max = str_replace( [
	'M',
	'm'
], '', @ini_get( 'upload_max_filesize' ) );

$result_set      = $db -> getRow( "select * from ".$sqlname."settings WHERE id = '$identity'" );
$mailme          = $result_set[ "mailme" ];
$mailout         = $result_set[ "mailout" ];
$mailemail       = $result_set[ "mailemail" ];
$ipaccesse       = $result_set[ "ipaccesse" ];
$ipstart         = $result_set[ "ipstart" ];
$ipend           = $result_set[ "ipend" ];
$ipmask          = $result_set[ "ipmask" ];
$iplist          = $result_set[ "iplist" ];
$api_key         = $result_set[ "api_key" ];
$coordinator     = $result_set[ "coordinator" ];
$dNum            = $result_set[ "dNum" ];
$dFormat         = $result_set[ "dFormat" ];
$defaultDealName = $result_set[ "defaultDealName" ];
$recv            = explode( ";", $result_set[ "recv" ] );

if ( $other[ 26 ] == '' ) {
	$other[26] = 14;
}

/*
//шаблон сервисного акта
if ( !isset( $other[ 39 ] ) || $other[ 39 ] == 'no' )
	$other[ 39 ] = 'akt_full.tpl';

//шаблон сервисного счета
if ( !isset( $other[ 40 ] ) || $other[ 40 ] == 'no' )
	$other[ 40 ] = 'invoice.tpl';

//шаблон акта
if ( !isset( $other[ 41 ] ) || $other[ 41 ] == 'no' )
	$other[ 41 ] = 'akt_full.tpl';

//шаблон счета
if ( !isset( $other[ 42 ] ) || $other[ 42 ] == 'no' )
	$other[ 42 ] = 'invoice.tpl';
*/

$custom = customSettings( 'settingsMore' );

if ( $action == 'saveRecvName' ) {

	$recv[ 'recvInn' ]          = $_REQUEST[ 'recvInn' ];
	$recv[ 'recvInnCount' ]     = $_REQUEST[ 'recvInnCount' ];
	$recv[ 'recvKpp' ]          = $_REQUEST[ 'recvKpp' ];
	$recv[ 'recvKppCount' ]     = $_REQUEST[ 'recvKppCount' ];
	$recv[ 'recvOkpo' ]         = $_REQUEST[ 'recvOkpo' ];
	$recv[ 'recvOkpoCount' ]    = $_REQUEST[ 'recvOkpoCount' ];
	$recv[ 'recvOgrn' ]         = $_REQUEST[ 'recvOgrn' ];
	$recv[ 'recvOgrnCount' ]    = $_REQUEST[ 'recvOgrnCount' ];
	$recv[ 'recvBankName' ]     = $_REQUEST[ 'recvBankName' ];
	$recv[ 'recvBankBik' ]      = $_REQUEST[ 'recvBankBik' ];
	$recv[ 'recvBankBikCount' ] = $_REQUEST[ 'recvBankBikCount' ];
	$recv[ 'recvBankRs' ]       = $_REQUEST[ 'recvBankRs' ];
	$recv[ 'recvBankKs' ]       = $_REQUEST[ 'recvBankKs' ];
	$recv[ 'valutaTrans' ]      = $_REQUEST[ 'valutaTrans' ];
	$recv[ 'valutaTransSub' ]   = $_REQUEST[ 'valutaTransSub' ];

	$recvName = '{"recvInn":"'.$recv[ 'recvInn' ].'","recvInnCount":"'.$recv[ 'recvInnCount' ].'","recvKpp":"'.$recv[ 'recvKpp' ].'","recvKppCount":"'.$recv[ 'recvKppCount' ].'","recvOkpo":"'.$recv[ 'recvOkpo' ].'","recvOkpoCount":"'.$recv[ 'recvOkpoCount' ].'","recvOgrn":"'.$recv[ 'recvOgrn' ].'","recvOgrnCount":"'.$recv[ 'recvOgrnCount' ].'","recvBankName":"'.$recv[ 'recvBankName' ].'","recvBankBik":"'.$recv[ 'recvBankBik' ].'","recvBankBikCount":"'.$recv[ 'recvBankBikCount' ].'","recvBankRs":"'.$recv[ 'recvBankRs' ].'","recvBankKs":"'.$recv[ 'recvBankKs' ].'","valutaTrans":"'.$recv[ 'valutaTrans' ].'","valutaTransSub":"'.$recv[ 'valutaTransSub' ].'"}';

	$f    = $rootpath.'/cash/'.$fpath.'requisites.json';
	$file = fopen( $f, 'wb' );

	if ( !$file ) {
		$rez = 'Не могу открыть файл';
	}
	else {

		if ( fwrite( $file, $recvName ) === false ) {
			$rez = 'Ошибка записи';
		}
		else {
			$rez = 'Записано';
		}

		fclose( $file );

	}

	print '{"rez":"'.$rez.'"}';

	exit();

}
if ( $action == 'viewRecvName' ) {

	if ( file_exists( $rootpath.'/cash/'.$fpath.'requisites.json' ) ) {
		$file     = file_get_contents( $rootpath.'/cash/'.$fpath.'requisites.json' );
		$recvName = json_decode( $file, true );
	}
	else {
		$file     = file_get_contents( $rootpath.'/cash/requisites.json' );
		$recvName = json_decode( $file, true );
	}
	?>
	<div class="zagolovok">Пример реквизитов</div>
	<div style="overflow: auto; max-height: 350px;">

		<div id="recv" class="flex-vertical border--bottom box--child">

			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Юр. Название</div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Юр. Название (кратко)</div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Юр. Адрес</div>
				<div class="flex-string wp75">--</div>

			</div>

			<div class="flex-container wp50 p10">

				<div class="flex-string wp25 gray2"><?= $recvName[ 'recvInn' ] ?></div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container wp50 p10">

				<div class="flex-string wp25 gray2"><?= $recvName[ 'recvKpp' ] ?></div>
				<div class="flex-string wp75">--</div>

			</div>

			<div class="flex-container wp50 p10">

				<div class="flex-string gray2"><?= $recvName[ 'recvOkpo' ] ?></div>
				<div class="flex-string">--</div>

			</div>
			<div class="flex-container wp50 p10">

				<div class="flex-string gray2"><?= $recvName[ 'recvOgrn' ] ?></div>
				<div class="flex-string">--</div>

			</div>

			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $recvName[ 'recvBankName' ] ?></div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $recvName[ 'recvBankBik' ] ?></div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $recvName[ 'recvBankKs' ] ?></div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2"><?= $recvName[ 'recvBankRs' ] ?></div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Руководитель</div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Руководитель (подпись)</div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Должность</div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Должность (подпись)</div>
				<div class="flex-string wp75">--</div>

			</div>
			<div class="flex-container p10">

				<div class="flex-string wp25 gray2">Действует на основании</div>
				<div class="flex-string wp75">--</div>

			</div>
		</div>

	</div>
	<hr>
	<div class="button--pane text-right">
		<div class="button" onclick="DClose()">Закрыть</div>
	</div>
	<script type="text/javascript">
		$(function () {
			$('#dialog').width('600px');
		});
	</script>
	<?php

	exit();

}
if ( $action == 'editRecvName' ) {

	if ( file_exists( $rootpath.'/cash/'.$fpath.'requisites.json' ) ) {
		$file     = file_get_contents( $rootpath.'/cash/'.$fpath.'requisites.json' );
		$recvName = json_decode( $file, true );
	}
	else {
		$file     = file_get_contents( $rootpath.'/cash/requisites.json' );
		$recvName = json_decode( $file, true );
	}
	?>
	<div class="zagolovok">Изменение имен реквизитов</div>
	<FORM action="/content/admin/settings.php" method="post" enctype="multipart/form-data" name="iform" target="_blank" id="iform" autocomplete="off">
		<INPUT type="hidden" name="action" id="action" value="saveRecvName">
		<div style="overflow: auto; max-height: 70vh;">
			<table class="bborder top">
				<thead class="sticked--top">
				<tr class="header_contaner">
					<th class="w130 text-center"><b>Оригинал</b></th>
					<th><b>Адаптивное название</b></th>
					<th class="w150"><b>Кол-во символов</b></th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">ИНН</div>
					</td>
					<td>
						<input name="recvInn" type="text" id="recvInn" value="<?= $recvName[ 'recvInn' ] ?>" class="wp97">
					</td>
					<td>
						<input name="recvInnCount" type="number" id="recvInnCount" value="<?= $recvName[ 'recvInnCount' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">КПП</div>
					</td>
					<td>
						<input name="recvKpp" type="text" id="recvKpp" value="<?= $recvName[ 'recvKpp' ] ?>" class="wp97">
					</td>
					<td>
						<input name="recvKppCount" type="number" id="recvKppCount" value="<?= $recvName[ 'recvKppCount' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">ОКПО</div>
					</td>
					<td>
						<input name="recvOkpo" type="text" id="recvOkpo" value="<?= $recvName[ 'recvOkpo' ] ?>" class="wp97">
					</td>
					<td>
						<input name="recvOkpoCount" type="number" id="recvOkpoCount" value="<?= $recvName[ 'recvOkpoCount' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">ОГРН (ОГРНИП)</div>
					</td>
					<td>
						<input name="recvOgrn" type="text" id="recvOgrn" value="<?= $recvName[ 'recvOgrn' ] ?>" class="wp97">
					</td>
					<td>
						<input name="recvOgrnCount" type="number" id="recvOgrnCount" value="<?= $recvName[ 'recvOgrnCount' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">Банк (название)</div>
					</td>
					<td>
						<input name="recvBankName" type="text" id="recvBankName" value="<?= $recvName[ 'recvBankName' ] ?>" style="width:97%"/>
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">БИК банка</div>
					</td>
					<td>
						<input name="recvBankBik" type="text" id="recvBankBik" value="<?= $recvName[ 'recvBankBik' ] ?>" class="wp97">
					</td>
					<td>
						<input name="recvBankBikCount" type="number" id="recvBankBikCount" value="<?= $recvName[ 'recvBankBikCount' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">Расч.счет</div>
					</td>
					<td>
						<input name="recvBankRs" type="text" id="recvBankRs" value="<?= $recvName[ 'recvBankRs' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">Кор.счет</div>
					</td>
					<td>
						<input name="recvBankKs" type="text" id="recvBankKs" value="<?= $recvName[ 'recvBankKs' ] ?>" class="wp97">
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">Пропись (осн.)</div>
					</td>
					<td>
						<input name="valutaTrans" type="text" id="valutaTrans" value="<?= $recvName[ 'valutaTrans' ] ?>" class="wp97">
						<div class="smalltxt gray">Склонения названия валюты для суммы прописью, основная валюта</div>
					</td>
				</tr>
				<tr>
					<td>
						<div class="pt7 text-right Bold gray2">Пропись (доп.)</div>
					</td>
					<td>
						<input name="valutaTransSub" type="text" id="valutaTransSub" value="<?= $recvName[ 'valutaTransSub' ] ?>" class="wp97">
						<div class="smalltxt gray">Склонения названия валюты для суммы прописью, копейки валюты</div>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<hr>
		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#iform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>
		$(function () {

			$('#dialog').css({'width': '800px'}).center();

			$('#iform').ajaxForm({
				dataType: 'json',
				beforeSubmit: function () {

					var em = checkRequired();

					if (!em)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.rez);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});
		});
	</script>
	<?php

	exit();

}

if ( $action == 'saveValuta' ) {

	$valuta = str_replace( "\\", "", $_REQUEST[ 'valuta' ] );

	$f    = $rootpath.'/cash/'.$fpath.'valuta.json';
	$file = fopen( $f, 'wb' );

	if ( !$file ) {
		$rez = 'Не могу открыть файл';
	}
	else {

		if ( fwrite( $file, $valuta ) === false ) {
			$rez = 'Ошибка записи';
		}
		else {
			$rez = 'Записано';
		}

		fclose( $file );

	}

	print '{"rez":"'.$rez.'"}';

	exit();

}
if ( $action == 'editValuta' ) {

	if ( file_exists( $rootpath.'/cash/'.$fpath.'valuta.json' ) ) {
		$file = file_get_contents( $rootpath.'/cash/'.$fpath.'valuta.json' );
	}
	else {
		$file = file_get_contents( $rootpath.'/cash/valuta.json' );
	}
	?>
	<div class="zagolovok">Изменение написаний сумм</div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="post" enctype="multipart/form-data" name="iform" target="_blank" id="iform" autocomplete="off">
		<INPUT type="hidden" name="action" id="action" value="saveValuta">
		<div style="overflow: auto; max-height: 350px;">
			<textarea id="valuta" name="valuta" spellcheck="false" style="width:99.5%; height:200px; font-size:1.0em; background:#E6E9ED; border:1px solid #656D78; color:#222; padding:10px"><?= $file ?></textarea>
		</div>
		<div class="infodiv">
			Не нарушайте структуру данных. Присылайте свои наборы по адресу - info@isaler.ru
			<hr>
			Загрузить набор для:
			<ul>
				<li class="pt5">
					<a href="javascript:void(0)" onclick="getValuta('ukr')" class="blue">Украинского языка</a></li>
				<li class="pt5">
					<a href="javascript:void(0)" onclick="getValuta('ru')" class="blue">Русского языка</a></li>
			</ul>
		</div>
		<hr>
		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#iform').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script type="text/javascript">

		$(function () {

			$('#dialog').width('600px');

			$('#iform').ajaxForm({
				dataType: 'json',
				beforeSubmit: function () {

					var em = checkRequired();

					if (!em)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');

					$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.rez);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
			});
		});

		function getValuta(lang) {
			var url = 'content/admin/<?php echo $thisfile; ?>?action=getValuta&lang=' + lang;
			$.get(url, function (data) {
				$('#valuta').val(data);
			});
		}

	</script>
	<?php

	exit();

}
if ( $action == 'getValuta' ) {

	$lang = $_REQUEST[ 'lang' ];
	$file = file_get_contents( $rootpath.'/cash/'.$fpath.'valuta.'.$lang.'.json' );

	print $file;

	exit();
}
?>

<STYLE type="text/css">
	<!--
	#settingstbl label {
		color : #2980B9;
	}

	thead .header_contaner {
		font-size      : 1.2em;
		font-weight    : 700;
		color          : var(--gray-litedarkblue);
		border-top     : 1px dotted var(--gray-litedarkblue);
		text-transform : uppercase;
	}

	thead .header_contaner.blue {
		color      : var(--blue);
		background : var(--gray);
	}

	-->
</STYLE>

<FORM action="/content/admin/settings_save.php" method="post" enctype="multipart/form-data" name="set" id="set">
	<INPUT type="hidden" name="action" id="action" value="save">

	<TABLE id="settingstbl" class="top">

		<!--Общие настройки-->
		<thead data-id="aboutset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Общие настройки
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="aboutset">
		<?php
			$logo = $db -> getOne( "select logo from ".$sqlname."settings WHERE id = '$identity'" );

			if ( $logo == '' ) {
				$logo = 'logo.png';
			}
			?>
		<TR class="th35">
			<TD class="w180 fs-12 gray2 text-right">
				<div class="pt10">Логотип компании:</div>
			</TD>
			<TD>
				<div class="mb5 bluebg-dark p10 w0 inline">
					<img src="/cash/logo/<?= $logo ?>" alt="Логотип" height="25">
				</div>
				<DIV class="infodiv margtop5">
					<input type="file" name="logo" id="logo" class="wp30" style="height: inherit">
					<div class="smalltxt gray2">Используйте файлы с расширением gif, jpeg, png. Рекомендуется горизонтальное расположение логотипа высотой 28px</div>
				</DIV>
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Название компании (краткое):</div>
			</td>
			<td><input name="company" type="text" id="company" value="<?= $company ?>" style="width:100%"/></td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Сайт компании:</div>
			</TD>
			<TD>
				<input name="company_site" type="url" id="company_site" value="<?= $company_site ?>" style="width:100%"/>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Почта компании:</div>
			</TD>
			<TD>
				<input name="company_mail" type="email" id="company_mail" value="<?= $company_mail ?>" style="width:100%"/>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Телефон компании:</div>
			</TD>
			<TD>
				<input name="company_phone" type="text" id="company_phone" value="<?= $company_phone ?>" style="width:100%"/>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Факс компании:</div>
			</TD>
			<TD>
				<input name="company_fax" type="text" id="company_fax" value="<?= $company_fax ?>" style="width:100%"/>
			</TD>
		</TR>
		<?php
		if ( $isCloud != true ) {
			?>
			<TR class="hidden">
				<TD>
					<div class="fnameForm">Google Key (<A href="https://code.google.com/intl/ru/apis/maps/signup.html" target="_blank"><B>Получить код</B></A>):
					</div>
				</TD>
				<TD><INPUT name="gkey" rows="1" id="gkey" value="<?= $gkey ?>" style="width:100%"></TD>
			</TR>
		<?php } ?>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Основное время сервера:</div>
			</TD>
			<TD>
				<?php
				$file = $rootpath.'/cash/tzone.json';
				$tmz  = json_decode( file_get_contents( $file ), true );
				//print_r($tmz);
				?>
				<select name="time_zone" id="time_zone">
					<?php
					foreach ( $tmz as $key => $val ) {
						print '<option value="'.$key.'" '.( $key == $tmzone ? 'selected' : '' ).'>'.$val.'</option>';
					}
					?>
				</select>
			</TD>
		</TR>
		</tbody>

		<!--Настройки под бизнес-->
		<thead data-id="bizset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Настройки под бизнес
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="bizset" class="hidden">
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Валюта учета:</div>
			</TD>
			<TD>
				<input name="valuta" list="valutaa" value="<?= $valuta ?>" size="10" maxlength="10" autocomplete="off">
				<datalist id="valutaa">
					<option value="руб.">
					<option value="грн.">
					<option value="usd">
					<option value="euro">
				</datalist>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Мой клиент:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="radio" name="other[13]" id="other[13]" value="yes" <?php if ( !$otherSettings[ 'clientIsPerson' ] ) print "checked"; ?>>Организация</label>&nbsp;&nbsp;&nbsp;
					<label><input type="radio" name="other[13]" id="other[13]" value="" <?php if ( $otherSettings[ 'clientIsPerson' ] ) print "checked"; ?>>Персона</label>
				</div>
				<div class="smalltxt" style="color:#666">Важно, если используются сервисы рассылок. В случае получения уведомления от сервиса рассылок о новом подписчике будет создана либо Организация, либо Персона.
					<strong>По умолчанию - Организация</strong></div>
				<div class="smalltxt" style="color:#666">Также, при создании сделок и включенном параметре "Создание Клиента" по умолчанию будет создан либо Клиент (юр.лицо), либо Киент (физ.лицо)</div>
			</td>
		</tr>
		<tr class="hidden">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Работаю без налога (НДС):</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[21]" id="other[21]" value="yes" <?php if ( $otherSettings[ 'woNDS' ] ) print "checked"; ?>>Да</label>
				</div>
				<div class="smalltxt" style="color:#666">Отключает вывод колонки налога (НДС) в счетах и актах, а также в спецификациях к договорам.</div>
				<div>
					<b class="red">Важно:</b> требуется ручная корректировка шаблона Счета и Акта - удаление заголовка колонки налога (НДС)
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Расчет налога (НДС):<b class="red">!!!</b></div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[29]" id="other[29]" value="yes" <?php if ( $otherSettings[ 'ndsInOut' ] ) print "checked"; ?>>Добавляется к цене (сверху)</label>
				</div>
				<div class="smalltxt" style="color:#666">Регулирует расчет налога. Следует отметить, если налог добавляется к цене.</div>
				<div class="warning">
					<ul>
						<li>Расчет налога производится по итоговой сумме спецификации</li>
						<li>В счетах и актах следует использовать тэг {summa_itog} для вывода суммы без налога</li>
					</ul>
					<b class="red">Важно:</b> данная функция экспериментальная. По всем вопросам обращаться по email -
					<a href="mailto:<?= $productInfo[ 'support' ] ?>" title="Задать вопрос в тех.поддержку"><?= $productInfo[ 'support' ] ?></a>
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt5">Названия реквизитов:</div>
			</td>
			<td>
				<div class="pb5 pt5">
					<a href="javascript:void(0)" onclick="doLoad('content/admin/settings.php?action=viewRecvName')" class="button"><i class="icon-eye"></i>Просмотр</a>&nbsp;
					<a href="javascript:void(0)" onclick="doLoad('content/admin/settings.php?action=editRecvName')" class="button greenbtn"><i class="icon-pencil"></i>Изменить</a>
					<a href="javascript:void(0)" onclick="doLoad('content/admin/settings.php?action=editValuta')" class="button orangebtn"><i class="icon-pencil"></i>Изменить прописи сумм</a>
				</div>
				<div class="infodiv">
					<b class="blue">Информация:</b>
					позволяет отредактировать названия реквизитов в соответствие со страной работы
				</div>
			</td>
		</tr>
		</tbody>

		<!--Дополнения к сделкам-->
		<thead data-id="dealset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Дополнения к Сделкам
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="dealset" class="hidden">
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right pt15">
				<div class="">Этап сделки по-умолчанию:</div>
			</td>
			<td>
				<div class="paddtop2">
					<SELECT name="other[25]" id="other[25]">
						<option value="none">--Нет--&nbsp;&nbsp;</option>
						<?php
						//найдем этап по-умолчанию 20%
						$result = $db -> getRow( "SELECT * FROM ".$sqlname."dogcategory WHERE title = '20' and identity = '$identity' ORDER BY title" );
						$dfs    = $result[ "idcategory" ];

						if ( $otherSettings[ 'dealStepDefault' ] == '' )
							$otherSettings[ 'dealStepDefault' ] = $dfs;

						$result = $db -> getAll( "SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' ORDER BY title" );
						foreach ( $result as $data ) {
							?>
							<option <?php if ( $data[ 'idcategory' ] == $otherSettings[ 'dealStepDefault' ] ) print "selected"; ?> value="<?= $data[ 'idcategory' ] ?>"><?= $data[ 'title' ] ?>%</option>
						<?php } ?>
					</SELECT>
				</div>
				<div class="smalltxt" style="color:#666">Этап, автоматически выбираемый при создании сделки</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Продолжительность сделки:</div>
			</td>
			<td>
				<div class="paddtop2">
					<input name="other[26]" type="number" id="other[26]" value="<?= $otherSettings[ 'dealPeriodDefault' ] ?>"/>
				</div>
				<div class="smalltxt" style="color:#666">Количество дней для расчета плановой даты сделки по умолчанию</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Сделки для Контактов:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[22]" id="other[22]" value="yes" <?php if ( $otherSettings[ 'dealByContact' ] ) print "checked"; ?>>Запретить</label>
				</div>
				<div class="smalltxt" style="color:#666">Разрешает/Запрещает создание сделок для Контактов</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Создание Клиента:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[23]" id="other[23]" value="yes" <?php if ( $otherSettings[ 'addClientWDeal' ] ) print "checked"; ?>>Разрешить</label>
				</div>
				<div class="smalltxt" style="color:#666">Разрешает/Запрещает создание Клиента (только название) при добавлении сделки</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Комментарий при смене этапа:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[27]" id="other[27]" value="yes" <?php if ( $otherSettings[ 'changeDealComment' ] ) print "checked"; ?>>Разрешить</label>
				</div>
				<div class="smalltxt" style="color:#666">Делает поле "Причина" при смене этапа сделки не обязательным</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Поле Дата заморозки:</div>
			</td>
			<td>
				<div class="paddtop2">
					<span class="select">
					<select id="other[45]" name="other[45]" class="w250">
						<option value="">Не выбрано</option>
						<?php
						$re = $db -> query( "select * from ".$sqlname."field where fld_tip IN ('dogovor') and fld_on='yes' and (fld_name LIKE '%input%') and fld_temp = 'datum' and identity = '$identity' order by fld_order" );
						while ($da = $db -> fetch( $re )) {

							print '<option value="'.$da['fld_name'].'" '.($da['fld_name'] == $otherSettings['dateFieldForFreeze'] ? 'selected' : '').'>'.$da['fld_title'].'</option>';

						}
						?>
					</select>
					</span>
				</div>
				<?php
				$fi = $db -> getRow( "SHOW COLUMNS FROM ".$sqlname."dogovor LIKE 'isFrozen'" );
				$stepInHold = customSettings('stepInHold');
				if($fi['Field'] == '' || (int)$stepInHold['step'] > 0){

					print '
					<div class="attention mt10">
						<b>Требуется выполнить миграцию.</b> Во время миграции будут произведены следующие действия:
						<ul>
							<li>сделки, находящиеся на этапе-заморозки будут возвращены на предыдущий этап</li>
							<li>замороженным сделкам будет присвоен новый статус заморозки</li>
							<li>старые настройки заморозки будут удалены</li>
							<li>действия будут применены в случае использования старого метода заморозки (Этап-заморозка)</li>
						</ul>
						<div class="attention dotted bgwhite red fs-09"><b class="red">Внимание!!!</b> Время выполнения скрипта зависит от количества сделок и может быть достаточно длительным.</div>
						<a href="/_install/freeze_update.php" class="button redbtn mt10" target="_blank" title="Выполнить">Выполнить</a>
					</div>
					';

				}
				?>
				<div class="smalltxt" style="color:#666">Активирует возможность заморозки сделки. Для автоматической разморозки рекомендуем использовать плагин <a href="https://salesman.pro/docs/152" target="_blank" class="blue Bold" title="Планировщик заданий">Планировщик заданий</a></div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Счетчик сделок:</div>
			</TD>
			<TD><input name="dNum" type="text" id="dNum" size="20" value="<?= $dNum ?>"/>
				<div class="smalltxt" style="color:#000">Укажите начальный номер счетчика.<b> Если сделок не было укажите 0 (ноль)</b>.
				</div>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt5">Формат номера сделки:</div>
			</TD>
			<TD>

				<div class="ul--group">
					<ul>
						<li onclick="addItem('dFormat','cnum');"><span>Номер</span></li>
						<li onclick="addItem('dFormat','DD');"><span>День</span></li>
						<li onclick="addItem('dFormat','MM');"><span>Месяц</span></li>
						<li onclick="addItem('dFormat','YY');"><span>Год (2 цифры)</span></li>
						<li onclick="addItem('dFormat','YYYY');"><span>Год (4 цифры)</span></li>
						<li onclick="addItem('dFormat','HH');"><span>ЧАС (2 цифры)</span></li>
						<li onclick="addItem('dFormat','MI');"><span>МИНУТА (2 цифры)</span></li>
					</ul>
				</div>
				<input name="dFormat" type="text" id="dFormat" class="wp97" value="<?= $dFormat ?>">
				<div class="smalltxt" style="color:#000"> При пустом поле &quot;Формат номера&quot; счетчик и генератор будет отключен. Используются даты на момент создания записи</div>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt5">Название сделки:</div>
			</TD>
			<TD>
				<div class="ul--group">
					<ul>
						<li onclick="addItem('defaultDealName','ClientName');"><span>Название/Имя клиента</span></li>
						<li onclick="addItem('defaultDealName','DD');"><span>День</span></li>
						<li onclick="addItem('defaultDealName','MM');"><span>Месяц</span></li>
						<li onclick="addItem('defaultDealName','YY');"><span>Год (2 цифры)</span></li>
						<li onclick="addItem('defaultDealName','YYYY');"><span>Год (4 цифры)</span></li>
						<li onclick="addItem('defaultDealName','HH');"><span>ЧАС (2 цифры)</span></li>
						<li onclick="addItem('defaultDealName','MI');"><span>МИНУТА (2 цифры)</span></li>
					</ul>
				</div>
				<input name="defaultDealName" type="text" id="defaultDealName" class="wp97" value="<?= $defaultDealName ?>">
				<div class="smalltxt" style="color:#000"> При пустом поле &quot;Формат номера&quot; счетчик и генератор будет отключен. Используются даты на момент создания записи</div>
			</TD>
		</TR>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class=""><b>Учет закрытых</b> сделок:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[18]" id="other[18]" value="yes" <?php if ( $otherSettings[ 'planByClosed' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если выполнение планов производится по
					<b>Закрытым сделкам</b>. В противном случае расчет производится по оплатам.
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Партнеры</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[0]" id="other[0]" value="yes" <?php if ( $otherSettings[ 'partner' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите<b> учитывать партнеров</b> в сделках
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Поставщики</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[17]" id="other[17]" value="yes" <?php if ( $otherSettings[ 'contractor' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите<b> учитывать партнеров</b> в сделках
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Конкуренты</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[1]" id="other[1]" value="yes" <?php if ( $otherSettings[ 'concurent' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите <b>учитывать конкурентов</b> в сделках
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>График платежей</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[2]" id="other[2]" value="yes" <?php if ( $otherSettings[ 'credit' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите <b>учитывать оплату</b> по сделкам</div>
				<hr>
				Предупредить о <b>Платеже</b> за:
				&plusmn;&nbsp;<input name="other[6]" type="text" id="other[6]" value="<?= $otherSettings[ 'creditAlert' ] ?>" size="3" maxlength="3" style="text-align:center"/>&nbsp;дней<br>
				<div class="smalltxt" style="color:#666">Диапазон дат <b>для контроля платежей</b> в меню &quot;Уведомления&quot;
				</div>
				<hr>
				Предупредить о <b>Сделке</b>:
				&plusmn;&nbsp;<input name="other[7]" type="text" id="other[7]" value="<?= $otherSettings[ 'dealAlert' ] ?>" size="3" maxlength="3" style="text-align:center"/>&nbsp;дней
				<div class="smalltxt" style="color:#666">Диапазон дат <b>для контроля факта закрытия</b> сделок</div>
				<hr>
				Учет <b>маржи</b>:&nbsp;
				<label><input type="checkbox" name="other[9]" id="other[9]" value="yes" <?php if ( $otherSettings[ 'marga' ] ) print "checked"; ?>>Включить</label>
				<div class="smalltxt" style="color:#666">Включите, если хотите <b>учитывать маржу</b></div>
				<hr>
				Выставление счетов:&nbsp;
				<label><input type="checkbox" name="other[12]" id="other[12]" value="yes" <?php if ( $otherSettings[ 'printInvoice' ] ) print "checked"; ?>>Включить</label>
				<div class="smalltxt" style="color:#666">Включите, если хотите выставлять Счета из системы</div>
				<hr>
				Артикул в счетах:&nbsp;
				<label><input type="checkbox" name="other[34]" id="other[34]" value="yes" <?php if ( $otherSettings[ 'artikulInInvoice' ] ) print "checked"; ?>>Включить</label>
				<div class="smalltxt" style="color:#666">Включите, если хотите включить актикулы в Счета</div>
				<hr>
				Артикул в актах:&nbsp;
				<label><input type="checkbox" name="other[35]" id="other[35]" value="yes" <?php if ( $otherSettings[ 'artikulInAkt' ] ) print "checked"; ?>>Включить</label>
				<div class="smalltxt" style="color:#666">Включите, если хотите включить актикулы в Актах</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Прайсы</b> и <b>Спецификации</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[3]" id="other[3]" value="yes" <?php if ( $otherSettings[ 'price' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите <b>использовать прайсы и спецификации</b>
				</div>
				<hr>
				<label><input type="checkbox" name="other[14]" id="other[14]" value="yes" <?php if ( $otherSettings[ 'dop' ] ) print "checked"; ?>>Использовать доп.множитель в спецификациях</label>
				<div class="smalltxt" style="color:#666">Включите, если хотите
					<b>использовать дополнительный множитель</b> в расчетах спецификаций
				</div>
				<hr>
				<b>Название доп.множителя:</b><br>
				<input name="other[15]" type="text" id="other[15]" value="<?= $otherSettings[ 'dopName' ] ?>"/><br>
				<div class="smalltxt" style="color:#666">Включите, если хотите
					<b>использовать дополнительный множитель</b> в расчетах спецификаций
				</div>
			</td>
		</tr>
		<tr class="hidden">
			<td>
				<div class="fnameCold">Модуль <b>Период сделки</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[4]" id="other[4]" value="yes" <?php if ( $otherSettings[ 'dealPeriod' ] ) print "checked"; ?>>Включить</label>
				</div>
				<br/>
				<div class="smalltxt" style="color:#666">Используйте если Ваш
					<b>продукт надо/можно Продлять/Обновлять</b>. Система будет напоминать об этом
				</div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Договоры с клиентами</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[5]" id="other[5]" value="yes" <?php if ( $otherSettings[ 'contract' ] ) print "checked"; ?>>Включить</label>
				</div>
				<br/>
				<div class="smalltxt" style="color:#666">Включите, если хотите <b>учитывать хоз.договоры</b> с Клиентами
				</div>
			</td>
		</tr>
		<tr class="hidden">
			<td class="w200 fs-12 gray2 text-right">
				<div class="fnameCold">Модуль <b>Номера заявок</b> в сделках:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="zayavka_on" id="zayavka_on" value="yes" <?php if ( $zayavka_on == "yes" ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите<b> фиксировать номера заявок в сделках.</b>
				</div>
			</td>
		</tr>
		<tr <?php if ( $tarif == 'Base' ) print 'hidden' ?> class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Контрольные точки</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="complect_on" id="complect_on" value="yes" <?php if ( $complect_on == "yes" ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите
					<b>контролировать различные параметры сделок. Например, дату выставления счета и факт</b></div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Потенциал клиента</b>:</div>
			</td>
			<td>
				<div class="paddtop2">
					<label><input type="checkbox" name="other[10]" id="other[10]" value="yes" <?php if ( $otherSettings[ 'potential' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включите, если хотите
					<b>контролировать план продаж в конкретного клиента</b></div>
			</td>
		</tr>
		<TR class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Субмодуль <b>Здоровье сделок</b>:</div>
			</td>
			<td>

				<div class="mb10">
					<label class="Bold"><input name="custom[dealHealthOn]" type="checkbox" id="custom[dealHealthOn]" value="yes" <?php if ( $custom[ 'dealHealthOn' ] == 'yes' ) print "checked" ?>/>Активировать субмодуль</label>
				</div>

				<div class="pt5">
					<label><input type="checkbox" name="other[33]" id="other[33]" value="yes" <?php if ( $otherSettings[ 'taskControlInHealth' ] ) print "checked"; ?>>учитывать <b>наличие Напоминаний</b></label>
				</div>

				<div class="pt5">
					<label><input type="checkbox" name="other[37]" id="other[37]" value="yes" <?php if ( $otherSettings[ 'stepControlInHealth' ] ) print "checked"; ?>>учитывать <b>длительность Этапов</b> (на основе Мультиворонки)</label>
				</div>

			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right middle">
				<div class="">Шаблон акта по умолчанию:</div>
			</td>
			<td class="">
				<div class="pt5">

					<span class="select">
					<select name="other[41]" id="other[41]" class="required w350">
						<?php
						$ires = $db -> query( "SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
						while ( $data = $db -> fetch( $ires ) ) {

							print '<option value="'.$data[ 'file' ].'" '.( $otherSettings[ 'aktTemp' ] == $data[ 'file' ] ? 'selected' : '' ).'>'.$data[ 'title' ].'</option>';

						}
						?>
					</select>
					</span>

				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right middle">
				<div class="">Шаблон счета по умолчанию:</div>
			</td>
			<td class="">
				<div class="pt5">

					<span class="select">
					<select name="other[42]" id="other[42]" class="required w350">
						<?php
						$ires = $db -> query( "SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('invoice') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
						while ( $data = $db -> fetch( $ires ) ) {

							print '<option value="'.$data[ 'file' ].'" '.( $otherSettings[ 'invoiceTemp' ] == $data[ 'file' ] ? 'selected' : '' ).'>'.$data[ 'title' ].'</option>';

						}
						?>
					</select>
					</span>

				</div>
			</td>
		</tr>
		</tbody>

		<!--Дополнения к сервисным сделкам-->
		<thead class="" data-id="sdealset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Дополнения к Сервисным Сделкам
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="sdealset" class="hidden">
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt5">Смена периода:</div>
			</td>
			<td>
				<div class="pt5">

					<label class="block pb10"><input type="radio" name="other[24]" id="other[24]" value="no" <?php if ( $otherSettings[ 'changeDealPeriod' ] != "akt" and $otherSettings[ 'changeDealPeriod' ] != "invoice" ) print "checked"; ?>>Не использовать</label>
					<label class="block pb10"><input type="radio" name="other[24]" id="other[24]" value="akt" <?php if ( $otherSettings[ 'changeDealPeriod' ] == "akt" ) print "checked"; ?>>При создании акта</label>
					<label class="block pb10"><input type="radio" name="other[24]" id="other[24]" value="invoice" <?php if ( $otherSettings[ 'changeDealPeriod' ] == "invoice" ) print "checked"; ?>>При выставлении счета (если оплата счета производится по окончании периода)</label>
					<label class="block pb10"><input type="radio" name="other[24]" id="other[24]" value="invoicedo" <?php if ( $otherSettings[ 'changeDealPeriod' ] == "invoicedo" ) print "checked"; ?>>При оплате счета (если оплата счета производится в начале периода)</label>

				</div>
				<div class="infodiv">
					Позволяет произвести смену периода в сервисных сделках (длительность периода берется автоматически) по событию
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right middle">
				<div class="">Шаблон акта по умолчанию:</div>
			</td>
			<td class="">
				<div class="pt5">

					<span class="select">
					<select name="other[39]" id="other[39]" class="required w350">
						<?php
						$ires = $db -> query( "SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
						while ( $data = $db -> fetch( $ires ) ) {

							print '<option value="'.$data[ 'file' ].'" '.( $otherSettings[ 'aktTempService' ] == $data[ 'file' ] ? 'selected' : '' ).'>'.$data[ 'title' ].'</option>';

						}
						?>
					</select>
					</span>

				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right middle">
				<div class="">Шаблон счета по умолчанию:</div>
			</td>
			<td class="">
				<div class="pt5">

					<span class="select">
					<select name="other[40]" id="other[40]" class="required w350">
						<?php
						$ires = $db -> query( "SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('invoice') AND identity = '$identity') AND identity = '$identity' ORDER by title" );
						while ( $data = $db -> fetch( $ires ) ) {

							print '<option value="'.$data[ 'file' ].'" '.( $otherSettings[ 'invoiceTempService' ] == $data[ 'file' ] ? 'selected' : '' ).'>'.$data[ 'title' ].'</option>';

						}
						?>
					</select>
					</span>

				</div>
			</td>
		</tr>
		</tbody>

		<!--Дополнения к клиентам-->
		<thead data-id="clientset">
		<TR class="hand th35 middle">
			<TD colspan="2" class="header_contaner">
				Дополнения к Клиентам
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="clientset" class="hidden">
		<tr <?php if ( $tarif == 'Base' ) print 'hidden' ?> class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Профиль</b>:</div>
			</td>
			<td>
				<div class="">
					<label><input type="checkbox" name="other[8]" id="other[8]" value="yes" <?php if ( $otherSettings[ 'profile' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Включает модуль &quot;Профилирование&quot;</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Экспресс-формы:</div>
			</td>
			<td>
				<div class=""><label><input type="checkbox" name="other[11]" id="other[11]" value="yes" <?php if ( $otherSettings[ 'expressForm' ] ) print "checked"; ?>>Включить</label></div>
				<div class="smalltxt" style="color:#666">Включите, если хотите использовать <b>упрощенные формы</b></div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right"></td>
			<td>
				<div class="mb10">Скрыть контакты Экспресс-формы (Формы Обращения):</div>
				<div class="red"><label><input type="checkbox" name="other[43]" id="other[43]" value="yes" <?php if ( $otherSettings[ 'hideContactFromExpress' ] ) print "checked"; ?>>Отключить</label></div>
				<div class="smalltxt" style="color:#666">Включите, если нужно скрыть блок Контакты</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right"></td>
			<td>
				<div class="mb10">Добавить блок Сделки для Экспресс-формы:</div>
				<div class=""><label><input type="checkbox" name="other[44]" id="other[44]" value="yes" <?php if ( $otherSettings[ 'addDealForExpress' ] ) print "checked"; ?>>Включить</label></div>
				<div class="smalltxt" style="color:#666">Включите, если нужно добавить блок добавления Сделки</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Комментарий при смене ответственного:</div>
			</td>
			<td>
				<div class="">
					<label><input type="checkbox" name="other[28]" id="other[28]" value="yes" <?php if ( $otherSettings[ 'changeUserComment' ] ) print "checked"; ?>>Разрешить</label>
				</div>
				<div class="smalltxt" style="color:#666">Делает поле "Причина передачи" при смене Ответственного не обязательным</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Блок "Приобретал":</div>
			</td>
			<td>
				<div class="">
					<label><input type="checkbox" name="other[30]" id="other[30]" value="yes" <?php if ( $otherSettings[ 'saledProduct' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt" style="color:#666">Активирует блок "Приобретал Продукты / услуги", в котором выводятся продукты и услуги из спецификаций успешно закрытых сделок</div>
			</td>
		</tr>
		</tbody>

		<!--Дополнения общие-->
		<thead data-id="abset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Дополнения общие
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="abset" class="hidden">
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Обсуждение</b>:</div>
			</td>
			<td>
				<div class="">
					<label><input type="checkbox" name="other[16]" id="other[16]" value="yes" <?php if ( $otherSettings[ 'comment' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="smalltxt">Включает модуль &quot;Обсуждения&quot;</div>

				<hr>

				<label class="Bold"><input name="custom[commentsSupportDostup]" type="checkbox" id="custom[commentsSupportDostup]" value="yes" <?php if ( $custom[ 'commentsSupportDostup' ] == 'yes' ) print "checked" ?>/>Ограничить роль пользователей "Поддержка продаж"</label><br>
				<div class="infodiv mt5">
					<b>По умолчанию "Поддержка продаж" имеет доступ ко всем записям в системе</b>. Вы можете ограничить доступ к обсуждениям - только к своим и к тем, на которые подписан
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Бюджет</b>:</div>
			</td>
			<td>

				<label><input type="checkbox" name="other[38]" id="other[38]" value="today" <?php if ( $otherSettings[ 'budjetDayIsNow' ] == "today" ) print "checked"; ?>>Принудительно устанавливать текущую дату проведения расхода</label>
				<div class="smalltxt">Принудительно устанавливает дату проведения расхода как Сегодня</div>

				<hr>

				<label class="Bold"><input name="custom[budjetEnableVijets]" type="checkbox" id="custom[budjetEnableVijets]" value="yes" <?php if ( $custom[ 'budjetEnableVijets' ] == 'yes' ) print "checked" ?>/>Виджеты рабочего стола</label><br>
				<div class="smalltxt">Включает виджеты модуля на рабочем столе (верхний блок)</div>

				<hr>

				<label class="Bold"><input name="custom[budjetProviderPlus]" type="checkbox" id="custom[budjetProviderPlus]" value="yes" <?php if ( $custom[ 'budjetProviderPlus' ] == 'yes' ) print "checked" ?>/>Расширенная работа с Поставщиками</label><br>
				<div class="smalltxt">
					Активирует расширенную работу с расходами на поставщиков:
					<ul>
						<li>сразу привязывает расходы к бюджету</li>
						<li>отправляет уведомления по изменению расхода куратору сделки и автору заявки</li>
					</ul>
				</div>

			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Контроль выполнения дел:</div>
			</td>
			<td>
				<input name="other[19]" type="number" id="other[19]" value="<?= $otherSettings[ 'taskControl' ] ?>"/>&nbsp;дел<br>
				<div class="infodiv mt5">
					Включает контроль выполнения Напоминаний ( если указано ). <br>
					Если сотрудник накопит не закрытых напоминаний больше указанного количества, то создание новых напоминаний будет не возможно.<br>
					<hr>
					<b>Затрагивает:</b> Календарь, форма добавления Истории активности,отметка дела выполненным, "Экспресс-форму", модуль "Обращения", модуль "Сборщик заявок".
				</div>

				<hr>

				<label class="Bold"><input name="other[20]" type="checkbox" id="other[20]" value="yes" <?php if ( $otherSettings[ 'taskControlClientAdd' ] ) print "checked" ?>/>Запрет добавления Клиентов и Контактов</label><br>
				<div class="infodiv mt5">
					<b>Также отключает возможность добавлять клиентов.</b>
					<hr>
					<b>Затрагивает модули:</b> Импорт, Формы для добавления Клиента и Контакта, Экспресс-форма, "Обращения", "Сборщик заявок"
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Запрет дополнения справочников:</div>
			</td>
			<td>
				<div class="">
					<label><input type="checkbox" name="other[31]" id="other[31]" value="yes" <?php if ( $otherSettings[ 'guidesEdit' ] ) print "checked"; ?>>Включить</label>
				</div>
				<div class="infodiv mt5">
					Отключает возможность вносить новые записи в справочники: Отрасли, Источники клиента, Территория через формы
					<hr>
					<b>Затрагивает модули:</b> Импорт, Формы для добавления Клиента и Контакта, Экспресс-форма, "Обращения", "Сборщик заявок"
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Редактирование дел:</div>
			</td>
			<td>
				<input name="other[32]" type="number" id="other[32]" min="0" step="1" value="<?= $otherSettings[ 'taskEditTime' ] ?>"/>&nbsp;часов<br>
				<div class="smalltxt" style="color:#666">
					Задает время в часах, в течение которого пользователь может Редактировать/Удалить напоминание, даже если ему запрещено Изменять/Удалять напоминания в настройках прав.<br>
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Отображение дел:</div>
			</td>
			<td>
				<label class="Bold"><input name="custom[viewNotSelfTasks]" type="checkbox" id="custom[viewNotSelfTasks]" value="yes" <?php if ( $custom[ 'viewNotSelfTasks' ] == 'yes' ) print "checked" ?>/>Не показывать в карточках Клиента чужие напоминания</label><br>
				<div class="infodiv mt5">
					В этом случае сотрудники, имеющие доступ к карточке Клиента не будут видеть чужие напоминания. Не касается доступа руководителя
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Предупреждения:</div>
			</td>
			<td>
				<label><input type="checkbox" name="custom[timecheck]" id="custom[timecheck]" value="yes" <?php if ( $custom[ 'timecheck' ] == "yes" ) print "checked"; ?>>Включить</label>
				<div class="smalltxt">Включает предупреждение при добавлении Напоминания, если не изменены Время и/или Тип напоминания</div>
			</td>
		</tr>
		</tbody>

		<!--Почтовые настройки-->
		<thead data-id="mailset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Почтовые настройки
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="mailset" class="hidden">
		<tr class="th40">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Уведомления:</div>
			</TD>
			<TD>
				<label><input name="mailme" type="checkbox" id="mailme" value="yes" <?php if ( $mailme == 'yes' ) print "checked" ?>/>&nbsp;Вкл./Откл. отправки уведомлений</label> (например: при создании новой организации)
			</TD>
		</TR>
		<TR class="th40 <? ( $isCloud == true ? 'hidden' : '' ) ?>">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Рассылки:</div>
			</TD>
			<TD>
				<label><input name="mailout" type="checkbox" id="mailout" value="yes" <?php if ( $mailout == 'yes' ) print "checked" ?>/>&nbsp;Вкл./Откл. модуля рассылки</label>
			</TD>
		</TR>
		<tr class="th35">
			<TD colspan="2">
				<div class="infodiv"><i class="icon-help-circled icon-2x blue"></i>Убедитесь, что на сервере настроен
					<b>Sendmail</b> или настройте внешний
					<a href="#smtp_editor"><b class="blue">SMTP-сервер</b></a>
				</div>
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Модуль <b>Почтовик</b>:</div>
			</td>
			<td>
				<div class="">
					<label><input type="checkbox" name="other[36]" id="other[36]" value="yes" <?php if ( $otherSettings[ 'mailerMsgUnion' ] ) print "checked"; ?>>&nbsp;Вкл./Откл. объединение сообщений</label>
				</div>
				<div class="smalltxt" style="color:#666">Отображение сообщений других почтовых ящиков в зависомости от связи с данным сообщением</div>
			</td>
		</tr>
		</tbody>

		<!--Настройки вывода информации-->
		<thead data-id="infoset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Настройки вывода информации
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="infoset" class="hidden">
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt10">Раздел Клиенты:</div>
			</TD>
			<TD>
				<SELECT name="num_client" id="num_client">
					<OPTION <?php if ( $num_client == "30" ) print "selected"; ?> value="30">30</OPTION>
					<OPTION <?php if ( $num_client == "40" ) print "selected"; ?> value="40">40</OPTION>
					<OPTION <?php if ( $num_client == "50" ) print "selected"; ?> value="50">50</OPTION>
					<OPTION <?php if ( $num_client == "100" ) print "selected"; ?> value="100">100</OPTION>
				</SELECT>
				&nbsp;записей
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt10">Раздел Связи:</div>
			</TD>
			<TD>
				<SELECT name="num_con" id="num_con">
					<OPTION <?php if ( $num_con == "30" ) print "selected"; ?> value="30">30</OPTION>
					<OPTION <?php if ( $num_con == "40" ) print "selected"; ?> value="40">40</OPTION>
					<OPTION <?php if ( $num_con == "50" ) print "selected"; ?> value="50">50</OPTION>
					<OPTION <?php if ( $num_con == "100" ) print "selected"; ?> value="100">100</OPTION>
				</SELECT>
				&nbsp;записей
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt10">Раздел Контакты:</div>
			</TD>
			<TD>
				<SELECT name="num_person" id="num_person">
					<OPTION <?php if ( $num_person == "30" ) print "selected"; ?> value="30">30</OPTION>
					<OPTION <?php if ( $num_person == "40" ) print "selected"; ?> value="40">40</OPTION>
					<OPTION <?php if ( $num_person == "50" ) print "selected"; ?> value="50">50</OPTION>
					<OPTION <?php if ( $num_person == "100" ) print "selected"; ?> value="100">100</OPTION>
				</SELECT>
				&nbsp;записей
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt10">Раздел Сделки:</div>
			</TD>
			<TD>
				<SELECT name="num_dogs" id="num_dogs">
					<OPTION <?php if ( $num_dogs == "30" ) print "selected"; ?> value="30">30</OPTION>
					<OPTION <?php if ( $num_dogs == "40" ) print "selected"; ?> value="40">40</OPTION>
					<OPTION <?php if ( $num_dogs == "50" ) print "selected"; ?> value="50">50</OPTION>
					<OPTION <?php if ( $num_dogs == "100" ) print "selected"; ?> value="100">100</OPTION>
				</SELECT>
				&nbsp;записей
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Формат телефонов:</div>
			</TD>
			<TD>
				<INPUT name="format_phone" type="text" id="format_phone" size="50" value="<?= $format_phone ?>">
				<div class="em blue smalltxt">
					Пустое поле отключает форматирование.<br>
					Например: <b>9(999)999-99-99</b>
				</div>
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Подсказка формата телефонов:</div>
			</TD>
			<TD>
				<INPUT name="format_tel" type="text" id="format_tel" size="50" value="<?= $format_tel ?>">
				<div class="em blue smalltxt">
					Например: <b>8(342)254-55-77</b>
				</div>
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Формат суммы (для сделок):</div>
			</TD>
			<TD>
				<INPUT name="format_dogs" type="text" id="format_dogs" size="50" value="<?= $format_dogs ?>">
				<div class="em blue smalltxt">
					Пустое поле отключает форматирование. Формат реверсный.<br>
					Пример формата: <b>99,999 999 999</b> (т.е. сначала десятичные, потом целые числа)<br>
					Пример без копеек: <b>999 999 999 999</b>
				</div>
			</TD>
		</TR>
		</tbody>

		<!--Интеграция-->
		<thead data-id="integity">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Интеграция, RestAPI
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="integity">
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">API-key:</div>
			</td>
			<td>
				<input name="api_key" type="text" id="api_key" value="<?= $api_key ?>" style="width:350px"/>&nbsp;<a href="javascript:void(0)" onclick="getKey()"><b class="blue"><i class="icon-key white"></i> Получить новый</b></a><br>
				<div class="infodiv">Используется для связи с внешними приложениями.
					<b>При смене сохраните настройки</b>
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt20">Внешние ссылки:</div>
			</TD>
			<TD>
				<div class="fs-07 Bold uppercase gray2">Клиент</div>
				<input name="outClientUrl" type="text" id="outClientUrl" value="<?= $outClientUrl ?>" style="width:350px"/><br>
				<div class="smalltxt gray">Используется для связи с внешними приложениями. Ссылка на карточку Клиента</div>

				<div class="fs-07 Bold uppercase gray2">Сделка</div>
				<input name="outDealUrl" type="text" id="outDealUrl" value="<?= $outDealUrl ?>" style="width:350px"/><br>
				<div class="smalltxt gray">Используется для связи с внешними приложениями. Ссылка на карточку Сделки</div>

				<hr>

				<div>
					<b>Пример:</b> <b class="blue">http://localhost/{uid}/{login}</b>, где<br>
					<ul>
						<li>{uid} - ID записи клиента/сделки во внешней истеме,</li>
						<li>{login} - текущий логин пользователя CRM</li>
					</ul>
				</div>
			</TD>
		</TR>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right"></td>
			<td>
				<div class="infodiv mb10">Документация по работе с RestAPI -
					<a href="<?= $productInfo[ 'site' ] ?>/api2/" target="blank" class="blue"><?= $productInfo[ 'site' ] ?>/api2/</a>
				</div>
			</td>
		</tr>
		</tbody>

		<!--Безопасность-->
		<thead data-id="secureset">
		<TR class="hand th30 middle">
			<TD colspan="2" class="header_contaner">
				Настройки безопасности
				<div class="pull-aright icon-2x"><i class="icon-angle-down"></i></div>
			</TD>
		</TR>
		</thead>
		<tbody id="secureset" class="hidden">
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Время жизни сессии авторизации:</div>
			</td>
			<td>
				<input name="session" type="text" id="session" value="<?= $session ?>" size="6" maxlength="6" style="text-align:center">&nbsp;дней
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Ограничение по ip-адресам:</div>
			</td>
			<td>

				<label class="block mt5">
					<input name="ipaccesse" type="checkbox" id="ipaccesse" value="yes" <?php if ( $ipaccesse == 'yes' ) print 'checked="checked"' ?> />&nbsp;Включить
				</label>

				<div id="ipa" class="infodiv mt10 <?php if ( $ipaccesse != 'yes' ) print 'hidden' ?>">

					<div class="mb10">
						<div class="uppercase fs-09 blue">Диапазон ip-адресов:</div>
						<div class="mt5">
							<input name="ipstart" type="text" id="ipstart" class="ips" value="<?= $ipstart ?>" size="15" maxlength="15">
							&nbsp;по&nbsp;
							<input name="ipend" type="text" id="ipend" class="ips" value="<?= $ipend ?>" size="15" maxlength="15">
						</div>
					</div>

					<div class="mb10">
						<div class="uppercase fs-09 blue">Маска для ip-адресов:</div>
						<div class="mt5">
							<input name="ipmask" type="text" id="ipmask" value="<?= $ipmask ?>" style="width: 500px;">
						</div>
					</div>

					<div class="mb10">
						<div class="uppercase fs-09 blue">IP-адреса:</div>
						<div class="mt5">
							<textarea id="iplist" name="iplist" rows="2" style="width: 500px;"><?= $iplist ?></textarea>
							<div class="smalltxt">Укажите ip-адреса, с которых разрешен доступ в систему явным образом через запятую</div>
						</div>
					</div>

				</div>

			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="">Доступ для всех:</div>
			</td>
			<td>
				<div class="">
					<label><input name="acs_view" type="checkbox" id="acs_view" value="on" <?php if ( $acs_view == 'on' ) print 'checked="checked"' ?> />&nbsp;Включить*</label>
				</div>
				<div class="infodiv mt10">
					<div class="Bold red">Режим "Коммунизм"</div>
					При включении данной опции ВСЕМ пользователям будет дан доступ к информации по ВСЕМ клиентам. <br>
					Настройки персонального доступа имеют меньший приоритет<br>
					Используйте с осторожностью
				</div>
			</td>
		</tr>
		<tr class="th35">
			<td class="w200 fs-12 gray2 text-right">
				<div class="pt7">Ограничение файлов:</div>
			</td>
			<td>
				<input name="maxupload" type="text" id="maxupload" value="<?= $maxupload ?>" size="3" maxlength="3" style="text-align:center"/>&nbsp;Mb, но
				<b>не более <?= $max ?> Mb</b>( серверное ограничение )<br>
				<div class="smalltxt">Макс. размер загружаемых файлов</div>
			</td>
		</tr>
		<?php
		if ( $isCloud == true ) {
			?>
			<tr class="th35">
				<td class="w200 fs-12 gray2 text-right">
					<div class="">Разрешенные типы файлов:</div>
				</td>
				<td>
					<input name="ext_allow" type="text" id="ext_allow" value="<?= $ext_allow ?>" style="width:100%"><br>
					<div class="infodiv mt10">
						Разделитель - <b>запятая</b><br>
						Позволяет ограничить типы загружаемых в систему файлов
					</div>
				</td>
			</tr>
			<?php
		}
		else {
			?>
			<tr class="th35">
				<td class="w200 fs-12 gray2 text-right">
					<div class="">Разрешенные типы файлов:</div>
				</td>
				<td>
					<input name="ext_allow" type="text" id="ext_allow" value="<?= $ext_allow ?>" <?php if ( $isCloud == true ) print 'readonly="readonly"'; ?> style="width:100%"/><br>
					<div class="infodiv mt10">
						Разделитель - <b>запятая</b><br>
						Позволяет ограничить типы загружаемых в систему файлов
					</div>
				</td>
			</tr>
		<?php } ?>
		</tbody>

	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
		<a href="javascript:void(0)" class="button" onclick="$('#set').trigger('submit')">Сохранить</a>
	</DIV>

	<div class="pagerefresh refresh--icon admn red" onclick="$('#set').trigger('submit')" title="Сохранить">
		<i class="icon-ok-circled"></i>
	</div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/7')" title="Документация">
		<i class="icon-help"></i>
	</div>

	<div class="space-100"></div>

</FORM>
<script>

	$(function () {

		var blok = localStorage.getItem("settingsBlock");

		if (blok != null) {

			$('#contentdiv thead[data-id="' + blok + '"]').trigger('click');

		}
		else $('#contentdiv thead:first').trigger('click');

		$('#set').ajaxForm({
			beforeSubmit: function () {
				var $out = $('#message');
				$out.empty();
				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;
			},
			success: function (data) {

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');

				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}
		});

	});

	/*$('.ips').setMask({
	 mask: '999.999.999.999',
	 autoTab: true
	 });*/

	$('#ipaccesse')
		.off('click')
		.on('click', function () {

			var che = $(this).prop('checked');

			if (che === true) $('#ipa').removeClass('hidden');
			else $('#ipa').addClass('hidden');

		});

	$('#contentdiv thead')
		.off('click')
		.on('click', function () {

			var id = $(this).data('id');

			$('#settingstbl').find('tbody:not(#' + id + ')').addClass('hidden');
			//$('#settingstbl').not('thead[data-id="' + id + '"]').find('i').removeClass('icon-angle-up').addClass('icon-angle-down');

			$('td', this).addClass('blue');
			$('#contentdiv thead').not(this).find('td').removeClass('blue');

			if ($('#settingstbl #' + id).hasClass('hidden')) {

				$('#settingstbl #' + id).removeClass('hidden');
				$('#settingstbl').find('thead[data-id="' + id + '"]').find('i').toggleClass('icon-angle-down icon-angle-up');

				localStorage.setItem("settingsBlock", id);

			}
			else {

				$('#settingstbl #' + id).addClass('hidden');
				$('#settingstbl').find('thead[data-id="' + id + '"]').find('i').removeClass('icon-angle-up').addClass('icon-angle-down');

				localStorage.removeItem("settingsBlock");

			}

		});

	function addItem(txtar, myitem) {

		insTextAtCursor(txtar, '{' + myitem + '}');
	}

	function getKey() {
		var url = '/content/admin/sip.editor.php?action=getApiKey';
		$.post(url, function (data) {
			$('#api_key').val(data);
		});
	}

</script>