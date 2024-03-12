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
use Salesman\Storage;

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

//require_once "mcfunc.php";

//настройки модуля
$msettings            = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
$msettings            = json_decode( (string)$msettings, true );
$msettings['mcSklad'] = 'yes';

if ( $msettings['mcSkladPoz'] != "yes" )
	$pozzi = " and status != 'out'";
//
//prid в таблице modcatalog - это ссылка на id в прайсе (n_id)
//prid в остальных таблицах - это id записи в таблице modcatalog
//

$n_id   = (int)$_REQUEST['n_id'];
$action = $_REQUEST['action'];

$tabsOn = 'no';

//---названия полей прайса. start---//
$dname  = $dvar = $don = [];
$result = $db -> getAll( "SELECT * FROM ".$sqlname."field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
foreach ( $result as $data ) {

	$dname[ $data['fld_name'] ] = $data['fld_title'];
	$dvar[ $data['fld_name'] ]  = $data['fld_var'];
	$don[]                      = $data['fld_name'];

}
//---названия полей прайса. end---//

$sk = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_sklad WHERE identity = '$identity'" );
foreach ( $sk as $da ) {
	$skladlist[ $da['id'] ] = $da['title'];
}

function mclogger($tip, $param = [], $oldparams = []) {

	global $logerror;

	$db = $GLOBALS['db'];

	$old = '';
	$new = '';

	if ( !empty( $oldparams ) ) {
		$old = json_encode_cyr( $oldparams );
	}

	$new = json_encode_cyr( $param );

	$diff = array_diff( $param, $oldparams );

	if ( !empty( $diff ) ) {

		try {

			$db -> query( "insert into ".$GLOBALS['sqlname']."modcatalog_log (id,tip,dopzid,prid,datum,new,old,iduser,identity) value (null,'$tip','".$param['dopzid']."','".$param['prid']."','".current_datumtime()."','$new','$old','".$GLOBALS['iduser1']."','".$GLOBALS['identity']."')" );

		}
		catch ( Exception $e ) {

			$logerror = 'Ошибка'.$e -> getMessage().' в строке '.$e -> getCode();

		}

		$logerror = 'Событие добавлено в Историю<br>';

	}
	else $logerror = 'Не обнаружено изменений<br>';

	return $logerror;
}

function convertcsv($string) {

	//$string = iconv("UTF-8","CP1251", $string);
	return $string;

}

function clnall($ostring) {

	$string = trim( $ostring );

	$string = str_replace( "\n\r", "<br>", $string );
	$string = str_replace( "\n", "<br>", $string );
	$string = str_replace( "<br><br>", "<br>", $string );
	$string = str_replace( "&nbsp;", " ", $string );

	return $string;
}

$statear = [
	'0' => 'Продан',
	'1' => 'Под заказ',
	'2' => 'Ожидается',
	'3' => 'В наличии',
	'4' => 'Резерв'
];

/**
 * Редактирование позиции
 */
if ( $action == "edit" ) {

	$idz = (int)$_REQUEST['idz'];
	$ido = (int)$_REQUEST['ido'];

	if ( $n_id > 0 ) {

		$res = $db -> getRow( "select * from ".$sqlname."price where n_id='".$n_id."' and identity = '$identity'" );

		foreach ( $res as $key => $val ) {
			$par[ $key ] = $val;
		}

		$artikul  = $res["artikul"];
		$title    = clean( $res["title"] );
		$descr    = $res["descr"];
		$price_in = $res["price_in"];
		$price_1  = $res["price_1"];
		$price_2  = $res["price_2"];
		$price_3  = $res["price_3"];
		$price_4  = $res["price_4"];
		$price_5  = $res["price_5"];
		$edizm    = $res["edizm"];
		$folder   = $res["pr_cat"];
		$nds      = $res["nds"];

		$res      = $db -> getRow( "select * from ".$sqlname."modcatalog where prid='".$n_id."' and identity = '$identity'" );
		$contentt = htmlspecialchars_decode( $res["content"] );
		$status   = $res["status"];
		$kol      = $res["kol"];
		$id       = $res["id"];
		$file     = $res["files"];
		$sklad    = $res["sklad"];

	}

	if ( $idz > 0 ) {

		$res = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE id = '$idz' and identity = '$identity'" );

		$zstatus = $res['status'];
		$des     = $res['des'];
		$content = $res['content'];

		$zayavka = json_decode( (string)$des, true );

		$zay = 'Год: '.$zayavka['zGod'].', Пробег: '.$zayavka['zProbeg'].', Цена: '.$zayavka['zPriceStart'].' - '.$zayavka['zPriceEnd'].', НДС: '.$zayavka['zNDS'];

		$title  = $zayavka['zTitle'];
		$descr  = $zay."\n";
		$descr  .= $content;
		$edizm  = 'шт.';
		$status = 3;
		$kol    = 1;

		$astatus = [
			'0' => 'Создана',
			'1' => 'В работе',
			'2' => 'Выполнена',
			'3' => 'Отменена'
		];
		$colors  = [
			'0' => 'broun',
			'1' => 'blue',
			'2' => 'green',
			'3' => 'Отменена'
		];

		$oldstatus = '<B class="'.strtr( $zstatus, $colors ).'">'.strtr( $zstatus, $astatus ).'</B>';

		$zstatus++;

	}
	if ( $ido > 0 ) {

		$res     = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_offer WHERE id = '$ido' and identity = '$identity'" );
		$des     = $res['des'];
		$content = $res['content'];

		$offer = json_decode( (string)$des, true );

		$zay = 'Год: '.$offer['zGod'].', Пробег: '.$offer['zProbeg'].', Цена: '.$offer['zPriceStart'].' - '.$offer['zPriceEnd'].', НДС: '.$offer['zNDS'];

		$title  = $offer['zTitle'];
		$descr  = $zay."\n";
		$descr  .= $content;
		$edizm  = 'шт.';
		$status = 3;
		$kol    = 1;

		$astatus = [
			'0' => 'Создана',
			'1' => 'В работе',
			'2' => 'Выполнена',
			'3' => 'Отменена'
		];
		$colors  = [
			'0' => 'broun',
			'1' => 'blue',
			'2' => 'green',
			'3' => 'Отменена'
		];

		$oldstatus = '<B class="'.strtr( $zstatus, $colors ).'">'.strtr( $zstatus, $astatus ).'</B>';

		$zstatus++;
	}

	?>
	<DIV class="zagolovok">Добавить/Редактировать позицию</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<INPUT type="hidden" name="action" id="action" value="edit_on">
		<INPUT name="n_id" type="hidden" id="n_id" value="<?= $n_id ?>">
		<INPUT type="hidden" name="idz" id="idz" value="<?= $idz ?>">
		<INPUT type="hidden" name="ido" id="ido" value="<?= $ido ?>">

		<DIV id="formtabs" style="border:0; background: none;" class="wp100">

			<UL>
				<LI><A href="#tab-form-1">Общая информация</A></LI>
				<LI><A href="#tab-form-2" onclick="$('.nano').nanoScroller()">Тех.характеристики</A></LI>
				<LI><A href="#tab-form-3">Цены и Изображения</A></LI>
			</UL>

			<div id="tabse" style="overflow-x: hidden; overflow-y: auto;">

				<div id="tab-form-1"><br>

					<?php if ( $idz ) { ?>
						<div class="infodiv wp100 row">

							<div class="column12 grid-3 fs-12 pt10 right-text gray2">Новый статус заявки:</div>
							<div class="column12 grid-9">
								<select name="zstatus" id="zstatus" class="required" style="width:200px">
									<option value="none">--Выбор--</option>
									<option disabled value="0">Создана</option>
									<option disabled value="1">В работе</option>
									<option <?php if ( $zstatus == '2' )
										print "selected"; ?> value="2">Выполнена
									</option>
								</select>
								<div class="em gray2 fs-09">Текущий статус заявки: <?= $oldstatus ?></div>
							</div>

						</div>
					<?php } ?>

					<div class="row mb20">

						<?php if ( $msettings['mcArtikul'] == 'yes' ) { ?>
							<div class="column12 grid-3 fs-12 pt10 right-text gray2">Артикул:</div>
							<div class="column12 grid-9">
								<input type="text" name="artikul" id="artikul" value="<?= $artikul ?>" class="w200">
							</div>
						<?php } ?>

						<div class="column12 grid-3 fs-12 pt10 right-text gray2">Название:</div>
						<div class="column12 grid-9">
							<INPUT name="title" type="text" id="title" class="required wp97" value="<?= $title ?>">
						</div>

						<div class="column12 grid-3 fs-12 pt10 right-text gray2">Ед. измерения:</div>
						<div class="column12 grid-9">
							<input name="edizm" type="text" id="edizm" class="required w200" value="<?= $edizm ?>">
						</div>

						<div class="column12 grid-3 fs-12 pt10 right-text gray2">Категория:</div>
						<div class="column12 grid-9">
							<select name="category" id="category" class="wp97">
								<OPTION value="">--Выбор--</OPTION>
								<?php
								$catalog = Price::getPriceCatalog();
								foreach ( $catalog as $value ) {

									if(in_array($value['id'], $msettings['mcPriceCat']) || in_array($value['sub'], $msettings['mcPriceCat']) || empty($msettings['mcPriceCat'])) {

										$s = ( $value['level'] > 0 ) ? str_repeat( '&nbsp;&nbsp;', $value['level'] ).'&rarr;&nbsp;' : '';
										$a = ( $value['id'] == $folder ) ? "selected" : '';

										print '<option value="'.$value['id'].'" '.$a.'>'.$s.$value['title'].'</option>';

									}

								}
								?>
							</select>&nbsp;
						</div>

						<?php if ( $msettings['mcSklad'] == 'yes' ) { ?>
							<div class="column12 grid-3 fs-12 pt10 right-text hidden">Склад:</div>
							<div class="column12 grid-9 hidden">
								<select name="sklad" id="sklad" class="wp97">
									<OPTION value="">--Выбор--</OPTION>
									<?php
									$result = $db -> getAll( "SELECT id, title FROM ".$sqlname."modcatalog_sklad WHERE identity = '$identity'" );
									foreach ( $result as $data ) {
										if ( $data['id'] == $sklad )
											$ss = "selected";
										else $ss = '';

										print '<option value="'.$data['id'].'" '.$ss.'>'.$data['title'].'</option>';

									}
									?>
								</select>&nbsp;
							</div>
						<?php } ?>

						<hr>

						<div class="column12 grid-3 fs-12 pt10 right-text gray2">Краткое описание:</div>
						<div class="column12 grid-9">
							<TEXTAREA name="descr" rows="3" class="wp97" id="descr"><?= $descr ?></TEXTAREA>
						</div>

						<div class="column12 grid-3 fs-12 pt10 right-text gray2">Подробное описание:</div>
						<div class="column12 grid-9">
							<TEXTAREA name="content" rows="10" class="wp97" id="content"><?= $contentt ?></TEXTAREA>
						</div>

					</div>

				</div>
				<div id="tab-form-2"><br>

					<?php
					$i      = 0;
					$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord" );
					foreach ( $result as $data ) {

						//это варианты из шаблона профиля
						$variant = explode( ';', $data['value'] );

						$pole = '';

						//это ввыбранные варианты в профиле конкретного клиента
						$value = $db -> getOne( "SELECT value FROM ".$sqlname."modcatalog_field WHERE n_id = '".$n_id."' and pfid = '".$data['id']."' and identity = '$identity'" );

						if ( $data['tip'] == 'input' ) {
							$pole = '<INPUT name="field['.$data['pole'].']" id="field['.$data['pole'].']" value="'.$value.'" type="text" class="wp97">';
						}
						elseif ( $data['tip'] == 'text' ) {
							$pole = '<textarea name="field['.$data['pole'].']" id="field['.$data['pole'].']" rows="2" class="wp97">'.$value.'</textarea>';
						}
						elseif ( $data['tip'] == 'select' ) {

							$string = '';

							foreach ( $variant as $item => $val ) {

								$sel    = ($value == $val) ? 'selected' : '';
								$string .= '<option value="'.$val.'" '.$sel.'>'.$val.'</option>';

							}

							$pole = '<SELECT name="field['.$data['pole'].']" id="field['.$data['pole'].']" class="wp97"><option value="">--выбор--</option>'.$string.'</SELECT>';

						}
						elseif ( $data['tip'] == 'checkbox' ) {

							$value = ($value != '') ? explode( ';', $value ) : [];

							foreach ( $variant as $item => $val ) {

								$sel = (in_array( $val, $value )) ? 'checked' : '';

								if ( $val != '' )
									$pole .= '<div class="inline pr10 pt7"><label><input type="checkbox" name="field['.$data['pole'].'][]" id="field['.$data['pole'].'][]" value="'.$val.'" '.$sel.'>&nbsp;&nbsp;'.$val.'</label></div>';

							}

						}
						elseif ( $data['tip'] == 'radio' ) {

							$value = ($value != '') ? explode( ';', $value ) : [];

							foreach ( $variant as $item => $val ) {

								$sel = (in_array( $val, $value )) ? 'checked' : '';

								if ( $val != '' )
									$pole .= '<div class="inline pr10 pt7"><label><input type="radio" name="field['.$data['pole'].'][]" id="field['.$data['pole'].'][]" value="'.$val.'" '.$sel.'>&nbsp;'.$val.'</label></div>';
							}

						}

						if ( $data['tip'] != 'divider' ) {

							if ( $data['pwidth'] == 100 )
								$w = $data['pwidth'] - 1;
							elseif ( $data['pwidth'] > 0 )
								$w = $data['pwidth'];
							else $w = 95;

							$w = "width:".$w."%";

							print '
				<div class="row mb10">
					<div class="column12 grid-3 fs-12 pt10 right-text gray2">'.$data['name'].':</div>
					<div class="column12 grid-9">'.$pole.'</div>
				</div>
				';

						}
						if ( $data['tip'] == 'divider' )
							print '<div id="divider" class="wp100 mt10 mb10" align="center"><b class="smalltxt">'.$data['name'].'</b></div>';

						$i++;
						$v = '';

					}
					?>

				</div>
				<div id="tab-form-3">

					<div class="row">

						<div class="column12 grid-12">
							<div id="divider" align="center"><b>Уровни цен</b></div>
						</div>

						<div class="column12 grid-2 fs-12 pt10 right-text gray2"><?= $dname['price_in'] ?>:</div>
						<div class="column12 grid-4">
							<label for="price_in"></label><input name="price_in" id="price_in" class="required w160" type="text" value="<?= num_format( $price_in ) ?>">&nbsp;<?= $valuta ?>
						</div>
						<div class="column12 grid-2 fs-12 pt10 right-text gray2">НДС:</div>
						<div class="column12 grid-4">
							<input type="text" name="nds" id="nds" value="<?= num_format( $nds ) ?>" class="w60">&nbsp;%
						</div>

						<hr>

						<?php if ( in_array( 'price_1', $don ) ) { ?>
							<div class="column12 grid-2 fs-12 pt10 right-text gray2"><?= $dname['price_1'] ?>:</div>
							<div class="column12 grid-10">
								<input name="price_1" type="text" id="price_1" autocomplete="off" value="<?= num_format( $price_1 ) ?>" class="w160">&nbsp;<?= $valuta ?>
							</div>
						<?php } ?>

						<?php if ( in_array( 'price_2', $don ) ) { ?>
							<div class="column12 grid-2 fs-12 pt10 right-text gray2"><?= $dname['price_2'] ?>:</div>
							<div class="column12 grid-4">
								<input name="price_2" type="text" id="price_2" autocomplete="off" value="<?= num_format( $price_2 ) ?>" class="w160">&nbsp;<?= $valuta ?>
							</div>
						<?php } ?>

						<?php if ( in_array( 'price_3', $don ) ) { ?>
							<div class="column12 grid-2 fs-12 pt10 right-text gray2"><?= $dname['price_3'] ?>:</div>
							<div class="column12 grid-4">
								<input name="price_3" type="text" id="price_3" autocomplete="off" value="<?= num_format( $price_3 ) ?>" class="w160">&nbsp;<?= $valuta ?>
							</div>
						<?php } ?>

						<?php if ( in_array( 'price_4', $don ) ) { ?>
							<div class="column12 grid-2 fs-12 pt10 right-text gray2"><?= $dname['price_4'] ?>:</div>
							<div class="column12 grid-4">
								<input name="price_4" type="text" id="price_4" autocomplete="off" value="<?= num_format( $price_4 ) ?>" class="w160">&nbsp;<?= $valuta ?>
							</div>
						<?php } ?>

						<?php if ( in_array( 'price_5', $don ) ) { ?>
							<div class="column12 grid-2 fs-12 pt10 right-text gray2"><?= $dname['price_5'] ?>:</div>
							<div class="column12 grid-4">
								<input name="price_5" type="text" id="price_5" autocomplete="off" value="<?= num_format( $price_5 ) ?>" class="w160">&nbsp;<?= $valuta ?>
							</div>
						<?php } ?>

					</div>

					<div class="row">

						<div class="column12 grid-12">
							<div id="divider" align="center"><b>Изображения</b></div>
						</div>

						<?php if ( $file != '' ) { ?>
							<div id="tagbox">
								<div id="filelist"></div>
							</div>
							<hr>
						<?php } ?>

						<div class="viewdiv wp100">
							<?php
							include $rootpath."/content/ajax/check_disk.php";
							if ( $diskLimit == 0 || $diskUsage['percent'] < 100 ) {
								?>
								<DIV id="uploads" style="overflow:auto !important" class="wp100 flex-container box--child">

									<input type="hidden" name="fcount" id="fcount" value="1">
									<div id="file-1" class="filebox flex-string wp50 m0">

										<input name="file[]" type="file" class="file wp100" id="file[]" onchange="addfile();">
										<div class="delfilebox hand" onclick="deleteFilebox('file-1')" title="Очистить">
											<i class="icon-cancel-circled red"></i></div>

									</div>

								</DIV>
								<?php
							}
							else print '<div class="warning" style="margin-left:0; margin-right:10px" align="center"><b class="red">Превышен лимит использования диска. Текущее использование - <b>'.$diskUsage['current'].'</b> Mb ('.$diskUsage['current'].'%)</b></div>';
							?>
							<hr>
							<?php
							if ( $diskLimit > 0 ) {

								print '<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % ). ';

							}
							if ( $maxupload == '' )
								$maxupload = str_replace( [
									'M',
									'm'
								], '', @ini_get( 'upload_max_filesize' ) );
							?>
							<b class="red">Максимальный размер файла</b> = <b><?= $maxupload ?></b> Mb
						</div>

					</div>

				</div>

			</div>

		</DIV><!--tabs-->

		<hr>

		<div class="pull-aright button--pane">

			<A href="javascript:void(0)" onclick="saveForm()" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<script>

		var editor;

		$('#dialog').css('width', '900px');

		$('#formtabs').css({'max-height': '80vh'}).tabs();

		var h = $('#dialog').actual('height') - $('.zagolovok').actual('height') - $('.button--pane').actual('height') - $('#formtabs').find('ul').actual('height') - 100;

		$('#tabse').css({"height": h + "px"});

		$(function () {
			createEditor2();

			$('#filelist').load('/modules/modcatalog/core.modcatalog.php?id=<?=$id?>&action=filelist');

			$('#dialog').center();

		});

		$('#priceForm').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				//remEditor2();

				$('#resultdiv').empty();

				if ($('.catalog--board').is('div')) $('.catalog--board').load('modcatalog/dt.board.php').append('<img src=/assets/images/loading.gif>');
				if ($('.catalog--zboard').is('div')) $('.catalog--zboard').load('modcatalog/dt.zboard.php').append('<img src=/assets/images/loading.gif>');
				if ($('#tar').is('input')) configpage();
				if ($('#isCard').val() == 'yes') configpage();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}
		});

		function addfile() {
			var kol = $('#fcount').val();
			i = parseInt(kol) + 1;

			var htmltr = '<div id="file-' + i + '" class="filebox flex-string wp50 m0"><input name="file[]" type="file" class="file wp100" id="file[]" onchange="addfile();"><div class="delfilebox hand" onclick="deleteFilebox(\'file-' + i + '\')" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

			$('#fcount').val(i);
			$('#uploads').append(htmltr);
			$('#dialog').center();

		}

		function deleteFilebox(id) {
			var kol = $('.filebox').size();

			if (kol > 1) $('#' + id).remove();
			else $('#' + id + ' #file\\[\\]').val('');
		}

		function saveForm() {

			CKEDITOR.instances['content'].updateElement();

			$('#priceForm').submit();

		}

		function createEditor2() {
			//var editor;
			var html = $('#dialog #contentt').val();

			//$('.nano').css('height','95%');

			editor = CKEDITOR.replace('content',
				{
					width: '99.5%',
					height: '300px',
					toolbar:
						[
							['Format', 'Bold', 'Italic', 'Underline', 'Strike', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
							['-', 'Undo', 'Redo', '-', 'PasteText', 'PasteFromWord', 'Image', 'HorizontalRule'],
							['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'Source']
						]
				});
			//editor.setData( html );

		}

		function remEditor2() {
			//$('.nano').css('height','100%');
			var html = $('#cke_editor_content').html();
			if (editor) {
				$('#content').val(html);
				editor.destroy();
				editor = null;
			}
			return true;
		}

		function DClose2() {
			//$('.nano').css('height','100%');
			remEditor2();
			DClose();
		}

		function deleteFile(id, file) {

			$.get('/modules/modcatalog/core.modcatalog.php?id=' + id + '&file=' + file + '&action=filedelete', function () {

				$('#filelist').load('/modules/modcatalog/core.modcatalog.php?id=<?=$id?>&action=filelist');

			});

		}

	</script>
	<?php
	exit();
}

/**
 * Редактирование розничной цены
 */
if ( $action == "editone" ) {

	$id      = (int)$db -> getOne( "select id from ".$sqlname."modcatalog where prid='".$n_id."' and identity = '$identity'" );
	$price_1 = $db -> getOne( "select price_1 from ".$sqlname."price where n_id='".$n_id."' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Изменить</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<INPUT type="hidden" name="action" id="action" value="editone_on">
		<INPUT name="n_id" type="hidden" id="n_id" value="<?= $n_id ?>">
		<INPUT name="id" type="hidden" id="id" value="<?= $id ?>">
		<TABLE width="100%" border="0" cellpadding="2" cellspacing="3">
			<TR>
				<TD width="100">Новая цена:</TD>
				<TD>
					<INPUT name="price_1" type="text" id="price_1" style="width: 195px" value="<?= num_format( $price_1 ) ?>">&nbsp;<?= $valuta ?>
				</td>
			</TR>
			<TR>
				<td colspan="2">
					<hr>
				</td>
			</TR>
			<TR>
				<TD valign="top" colspan="2">
					<div class="paddbott5">Комментарий:</div>
					<TEXTAREA name="descr" rows="3" class="required" id="descr" style="width: 98.7%;"><?= $descr ?></TEXTAREA>
				</TD>
			</TR>
		</TABLE>

		<hr>

		<div class="pull-aright">
			<A href="javascript:void(0)" onclick="$('#priceForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<script>
		$(function () {

			$('#dialog').css('width', '500px');

			$('#priceForm').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$('#resultdiv').empty();

					try {
						configpage();
					} catch (e) {
					}

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

					if ($('#log').is('div')) logs();
				}
			});

			$('#dialog').center();

		});
	</script>
	<?php
	exit();
}

if ( $action == "import" ) {
	?>
	<FORM action="/modules/modcatalog/form.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="import_upload">
		<DIV class="zagolovok">Импорт из Excel</DIV>
		<TABLE width="100%" border="0" cellpadding="2" cellspacing="3">
			<TR>
				<TD width="100" align="right"><B>Из файла:</B></TD>
				<TD><input name="file" type="file" class="file" id="file" style="width:98%"/></TD>
			</TR>
		</TABLE>
		<div class="infodiv">
			<b>Важно:</b> Допускается импортировать не более 5000 записей за один раз. Поддерживаются форматы XLS. Вы можете загрузить
			<a href="/developer/example/price.xls" class="red"><b>пример</b></a><br>
		</div>
		<hr>
		<div align="right">
			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Импорт</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
		</div>
	</FORM>
	<script>

		$(function () {
			$('#dialog').css('width', '600px');

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;

					$out.fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
					$('#dialog').removeClass('dtransition');

					return true;

				},
				success: function (data) {

					if (data == 'Файл загружен') $('#resultdiv').empty().load('/modules/modcatalog/form.modcatalog.php?action=import_select').append('<div class="infodiv"><img src="/assets/images/loading.gif"> Обработка данных. Пожалуйста подождите...</div>');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				},
				complete: function () {
					$('#dialog').addClass('dtransition');
				}
			});

			$('#dialog').center();
		});
	</script>
	<?php
	exit();
}
if ( $action == "import_upload" ) {

	//проверяем расширение файла. Оно д.б. только csv
	$array   = explode( ".", basename( $_FILES['file']['name'] ) );
	$cur_ext = texttosmall( end( $array ) );
	if ( $cur_ext != 'xls' ) {
		print 'Ошибка при загрузке файла <b>"'.basename( $_FILES['file']['name'] ).'"</b>!<br />
		<b class="yelw">Ошибка:</b> Недопустимый формат файла. <br>Допускаются только файлы в формате <b>XLS</b>';
	}
	else {
		$url = '../../files/'.$fpath.basename( $_FILES['file']['name'] );
		//Сначала загрузим файл на сервер
		if ( move_uploaded_file( $_FILES['file']['tmp_name'], $url ) ) {
			setcookie( "url_catalog", basename( $_FILES['file']['name'] ), time() + 86400 );
			print 'Файл загружен';
		}
		else {
			print 'Ошибка при загрузке файла <b>"'.$_FILES['file']['name'].'"</b>!<br /><b class="yelw">Ошибка:</b> '.$_FILES['file']['error'].'<br />';
		}
	}
	exit();
}
if ( $action == "import_select" ) {

	//require_once '../../opensource/excel_reader/excel_reader2.php';

	$file    = $_COOKIE['url_catalog'];
	$url     = $rootpath.'/files/'.$fpath.$file;
	$array1  = explode( ".", basename( $file ) );
	$cur_ext = texttosmall( end( $array1 ) );

	$data = [];

	if ( $cur_ext == 'xls' ) {

		$datas = new SpreadsheetReader( $url );
		$datas -> ChangeSheet( 0 );

		foreach ( $datas as $k => $Row ) {

			if ( $k < 3 ) {

				foreach ( $Row as $key => $value ) {

					$data[ $k ][] = enc_detect( untag( $value ) );

				}

			}
			else {
				goto p;
			}

		}

		//print_r($data);

	}

	p:
	$data = array_values( $data );

	?>
	<DIV class="zagolovok">Импорт в базу. Шаг 2.</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="import_on">
		<table width="100%" border="0" align="center" cellpadding="5" cellspacing="1" id="zebra">
			<thead>
			<tr class="noDrag">
				<TH width="200" height="35" align="center" class="nodrop">Название поля в БД</TH>
				<TH width="250" height="35" align="center" class="nodrop">Название поля из файла</TH>
				<TH align="center" class="nodrop">Образец из файла</TH>
			</tr>
			</thead>
		</table>
		<DIV style="height:350px; overflow:auto">
			<INPUT type="hidden" name="action" id="action" value="import_on">
			<table id="zebra">
				<?php foreach ($data[0] as $item) { ?>
					<tr class="ha">
						<td width="200">
							<select id="field[]" name="field[]" style="width:100%">
								<option value="">--Выбор--</option>
								<optgroup label="Общие">
									<option value="price:artikul" <?php if ( enc_detect( $item ) == 'Артикул' ) print "selected"; ?>>Артикул</option>
									<option value="category:title" <?php if ( enc_detect( $item ) == 'Категория' ) print "selected"; ?>>Категория</option>
									<option value="price:title" <?php if ( enc_detect( $item ) == 'Наименование' ) print "selected"; ?>>Наименование</option>
									<option value="status:title" <?php if ( enc_detect( $item ) == 'Статус' ) print "selected"; ?>>Статус</option>
									<option value="catalog:kol" <?php if ( enc_detect( $item ) == 'Количество' ) print "selected"; ?>>Количество</option>
									<option value="price:edizm" <?php if ( enc_detect( $item ) == 'Ед.изм.' ) print "selected"; ?>>Ед.измерения</option>
									<option value="price:descr" <?php if ( enc_detect( $item ) == 'Описание краткое' ) print "selected"; ?>>Описание краткое</option>
									<option value="catalog:content" <?php if ( enc_detect( $item ) == 'Описание полное' ) print "selected"; ?>>Описание полное</option>
								</optgroup>
								<optgroup label="Цены">
									<option value="price:price_in" <?php if ( enc_detect( $item ) == $dname['price_in'] )
										print "selected"; ?>><?= $dname['price_in'] ?></option>
									<?php if ( in_array( 'price_1', $don ) ) { ?>
										<option value="price:price_1" <?php if ( enc_detect( $item ) == $dname['price_1'] )
											print "selected"; ?>><?= $dname['price_1'] ?></option><?php } ?>
									<?php if ( in_array( 'price_2', $don ) ) { ?>
										<option value="price:price_2" <?php if ( enc_detect( $item ) == $dname['price_2'] )
											print "selected"; ?>><?= $dname['price_2'] ?></option><?php } ?>
									<?php if ( in_array( 'price_3', $don ) ) { ?>
										<option value="price:price_3" <?php if ( enc_detect( $item ) == $dname['price_3'] )
											print "selected"; ?>><?= $dname['price_3'] ?></option><?php } ?>
									<?php if ( in_array( 'price_4', $don ) ) { ?>
										<option value="price:price_4" <?php if ( enc_detect( $item ) == $dname['price_4'] )
											print "selected"; ?>><?= $dname['price_4'] ?></option><?php } ?>
									<?php if ( in_array( 'price_5', $don ) ) { ?>
										<option value="price:price_5" <?php if ( enc_detect( $item ) == $dname['price_5'] )
											print "selected"; ?>><?= $dname['price_5'] ?></option><?php } ?>
									<option value="price:nds" <?php if ( enc_detect( $item ) == 'НДС' )
										print "selected"; ?>>НДС
									</option>
								</optgroup>
								<optgroup label="Изображения">
									<option value="catalog:files_1" <?php if ( enc_detect( $item ) == 'Изображение 1' )
										print "selected"; ?>>Изображение 1
									</option>
									<option value="catalog:files_2" <?php if ( enc_detect( $item ) == 'Изображение 2' )
										print "selected"; ?>>Изображение 2
									</option>
									<option value="catalog:files_3" <?php if ( enc_detect( $item ) == 'Изображение 3' )
										print "selected"; ?>>Изображение 3
									</option>
									<option value="catalog:files_4" <?php if ( enc_detect( $item ) == 'Изображение 4' )
										print "selected"; ?>>Изображение 4
									</option>
									<option value="catalog:files_5" <?php if ( enc_detect( $item ) == 'Изображение 5' )
										print "selected"; ?>>Изображение 5
									</option>
								</optgroup>
								<optgroup label="Характеристики">
									<?php
									$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord" );
									foreach ( $result as $datar ) {

										$s = '';

										if ( enc_detect( $item ) == $datar['name'] ) {
											$s = " selected";
										}

										print '<option value="profile:'.$datar['pole'].'"'.$s.'>'.$datar['name'].'</option>';
									}
									?>
								</optgroup>
							</select>
						</td>
						<td width="250"><b><?= enc_detect( $item ) ?></b></td>
						<td>
							<div class="ellipsis"><?= enc_detect( $item ) ?></div>
						</td>
					</tr>
				<?php } ?>
			</table>
		</DIV>
	</FORM>
	<hr>
	<div align="center" class="success">
		Теперь Вам необходимо ассоциировать загруженные данные с БД системы. Подробнее в
		<a href="https://salesman.pro/docs/47" target="blank">Документации</a>
	</div>
	<hr>
	<DIV align="right">
		<A href="javascript:void(0)" onclick="$('#Form').submit()" class="button">Импортировать</A>&nbsp;
		<A href="javascript:void(0)" onclick="Discard()" class="button">Отмена</A>
	</DIV>
	<script type="text/javascript">

		$(function () {
			$('#dialog').css('width', '800px');
			$('#dialog').center();
			$('#resultdiv').find('select').each(function () {
				$(this).wrap("<span class='select'></span>");
			});

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;


					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					$('#dialog').removeClass('dtransition');
					return true;

				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');

					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				},
				complete: function () {
					$('#dialog').addClass('dtransition');
				}
			});

			$('#dialog').center();
		});

		$('.close').click(function () {
			Discard();
		});

		function Discard() {
			var url = '/modules/modcatalog/core.modcatalog.php?action=discard';
			var str = '';
			$.post(url, str, function (data) {
				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');
				return false;
			});
		};
	</script>
	<?php
	exit();
}

/**
 * Просмотр позиции
 */
if ( $action == "view" ) {

	$state  = [
		0 => 'Нет данных',
		1 => 'Заказан',
		2 => 'Под заказ',
		3 => 'В наличии',
		4 => 'Продан'
	];
	$colors = [
		'0' => 'gray',
		'1' => 'broun',
		'2' => 'broun',
		'3' => 'green',
		'4' => 'red'
	];

	$res = $db -> getRow( "select * from ".$sqlname."price where n_id='".$n_id."' and identity = '$identity'" );

	$artikul  = $res["artikul"];
	$title    = clean( $res["title"] );
	$descr    = $res["descr"];
	$datum    = $res["datum"];
	$price_in = $res["price_in"];
	$price_1  = $res["price_1"];
	$price_2  = $res["price_2"];
	$price_3  = $res["price_3"];
	$price_4  = $res["price_4"];
	$price_5  = $res["price_5"];
	$edizm    = $res["edizm"];
	$folder   = $res["pr_cat"];
	$nds      = $res["nds"];

	$cat = $db -> getOne( "SELECT title FROM ".$sqlname."price_cat WHERE idcategory = '".$folder."' and identity = '$identity'" );

	$res     = $db -> getRow( "select * from ".$sqlname."modcatalog where prid='".$n_id."' and identity = '$identity'" );
	$content = htmlspecialchars_decode( $res["content"] );
	$status  = $res["status"];
	//$kol      = $res["kol"];
	$id   = $res["id"];
	$file = $res["files"];
	//$sklad    = $res["sklad"];

	$kol = $db -> getOne( "select SUM(kol) as kol from ".$sqlname."modcatalog_skladpoz where status != 'out' and prid='".$n_id."' $pozzi and identity = '$identity'" );

	//$sklad = $db -> getOne("SELECT title FROM ".$sqlname."modcatalog_sklad WHERE identity = '$identity'");

	$kol_res = $db -> getOne( "select SUM(kol) as kol from ".$sqlname."modcatalog_reserv where prid='".$n_id."' and identity = '$identity'" ) + 0;

	$kol_zay = $db -> getOne( "select SUM(kol) as kol from ".$sqlname."modcatalog_zayavkapoz where prid='".$n_id."' and idz NOT IN (select idz from ".$sqlname."modcatalog_zayavka where status IN (2, 3) and identity = '$identity') and identity = '$identity'" );

	$reserv = '';
	$res    = $db -> getAll( "select * from ".$sqlname."modcatalog_reserv where prid='".$n_id."' and identity = '$identity'" );
	foreach ( $res as $datar ) {

		$user   = explode( " ", current_user( get_userid( 'did', $datar['did'] ) ) );
		$reserv = '<b class="blue">'.$user[0].'</b> - <b>'.$datar['kol'].'</b> '.$edizm.'<br>';

	}

	if ( $kol == '' )
		$kol = '?';
	?>
	<div class="zagolovok"><?= $title ?></div>

	<div class="flex-container wp100" style="overflow: hidden">

		<div class="flex-string wp40">

			<div style="height: 485px;" id="pozition">

				<table width="100%" border="0" cellpadding="0" cellspacing="5" id="noborder">
					<?php if ( $msettings['mcArtikul'] == 'yes' ) { ?>
						<tr>
							<td width="100" height="25" align="right">Артикул:</td>
							<td><?php if ( $artikul != '' ) { ?>
									<b><?= $artikul ?></b>&nbsp;[Обновлен: <?= $datum ?>]<?php } else print "--//--" ?>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<td width="100" height="25" align="right">Категория:</td>
						<td><b><?= $cat ?></b></td>
					</tr>
					<?php if ( $msettings['mcSklad'] == 'yes' ) { ?>
						<tr class="hidden">
							<td width="100" height="25" align="right">Склад:</td>
							<td><b><?= $sklad ?></b></td>
						</tr>
					<?php } ?>
					<tr>
						<td height="25" align="right">Количество:</td>
						<td>
							<b><?= $kol ?></b>&nbsp;<?php if ( $kol_res > 0 )
								print '&nbsp;( <span class="green">В резерве <b><a href="javascript:void(0)" onclick="doLoad(\'modcatalog/editor.php?action=viewzrezerv&prid='.$n_id.'\')">'.$kol_res.'</a></b> '.$edizm.'</span> )'; ?>&nbsp;<?php if ( $kol_zay > 0 )
								print '&nbsp;( <span class="red">В заявках <b>'.$kol_zay.'</b> '.$edizm.'</span> )'; ?>
						</td>
					</tr>
					<?php if ( $reserv ) { ?>
						<tr height="25" class="hidden">
							<td align="right">Зарезервировано:</td>
							<td><?= $reserv ?></td>
						</tr>
					<?php } ?>
					<tr class="hidden">
						<td height="25" align="right">Статус:</td>
						<td><B class="<?= strtr( $status, $colors ) ?>"><?= strtr( $status, $state ) ?></B></td>
					</tr>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<tr>
							<td height="25" align="right"><?= $dname['price_in'] ?>:</td>
							<td><b class=""><?= num_format( $price_in ) ?></b> <?= $valuta ?>/<?= $edizm ?></td>
						</tr>
					<?php } ?>
					<tr>
						<td height="25" align="right"><?= $dname['price_1'] ?>:</td>
						<td>
							<b class="red miditxt"><?= num_format( $price_1 ) ?></b> <?= $valuta ?>/<?= $edizm ?>&nbsp;, в т.ч.
							<b>НДС </b><?= num_format( $nds ) ?>&nbsp;%
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<hr>
						</td>
					</tr>
					<tr>
						<td align="right" valign="top">Уровни цен:</td>
						<td>
							<div id="tagbox">
								<?php if ( in_array( 'price_2', $don ) ) { ?>
									<div class="tags">
										<?= $dname['price_2'] ?>:&nbsp;<b class="red"><?= num_format( $price_2 ) ?></b> <?= $valuta ?>
									</div>
								<?php } ?>
								<?php if ( in_array( 'price_3', $don ) ) { ?>
									<div class="tags">
										<?= $dname['price_3'] ?>:&nbsp;<b class="red"><?= num_format( $price_3 ) ?></b> <?= $valuta ?>
									</div>
								<?php } ?>
								<?php if ( in_array( 'price_4', $don ) ) { ?>
									<div class="tags">
										<?= $dname['price_4'] ?>:&nbsp;<b class="red"><?= num_format( $price_4 ) ?></b> <?= $valuta ?>
									</div>
								<?php } ?>
								<?php if ( in_array( 'price_5', $don ) ) { ?>
									<div class="tags">
										<?= $dname['price_5'] ?>:&nbsp;<b class="red"><?= num_format( $price_5 ) ?></b> <?= $valuta ?>
									</div>
								<?php } ?>
							</div>
						</td>
					</tr>
					<?php
					$files = json_decode( $file, true );

					if ( !empty( $files )) {
						?>
						<tr>
							<td valign="top" align="right">Изображения:</td>
							<td>
								<div>
									<?php
									foreach ($files as $file) {
										?>
										<div class="tumbs" style="background: url(<?= '/content/helpers/get.file.php?file=modcatalog/'.$file['file'] ?>) top no-repeat; background-size:cover;">
											<a href="<?= '/content/helpers/get.file.php?file=modcatalog/'.$file['file'] ?>" target="blank" title="В новом окне"><i class="icon-search gray icon-3x"></i></a>
										</div>
									<?php } ?>
								</div>
							</td>
						</tr>
					<?php } ?>
				</table>

			</div>

		</div>
		<div class="flex-string wp60">

			<DIV id="formtabs" class="wp100" style="background: none;">

				<UL style="background: none;">
					<LI><A href="#tab-form-2">Характеристики</A></LI>
					<LI><A href="#tab-form-1">Описание</A></LI>
					<LI><A href="#tab-form-3">Сделки</A></LI>
					<LI><A href="#tab-form-4">Доп.</A></LI>
				</UL>
				<div id="tab-form-1" style="height: 60vh; max-height: 60vh; overflow-y: auto; overflow-x:hidden;"><br/>
					<table width="98%" border="0" cellspacing="1" cellpadding="4">
						<tr>
							<td>
								<span class="smalltxt"><b class="blue">Описание</b>:</span><br>
								<div class="viewdiv"><?= $descr ?></div>
							</td>
						</tr>
						<tr>
							<td>
								<span class="smalltxt"><b class="blue">Расширенное описание</b>:</span><br>
								<div class="viewdiv" style="font-size:1.15em; line-height: 1.35em;" id="htmldiv"><?= $content ?>
									<br></div>
							</td>
						</tr>
					</table>
				</div>
				<div id="tab-form-2" style="height: 60vh; max-height: 60vh; overflow-y: auto; overflow-x:hidden;"><br/>
					<div style="width:99%;" class="viewdiv">
						<table width="98%" border="0" cellpadding="5" cellspacing="1" id="bborder1">
							<tr>
								<td width="120"></td>
								<td></td>
							</tr>
							<?php
							$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_fieldcat WHERE identity = '$identity' ORDER by ord" );
							foreach ( $result as $data ) {

								$value = $db -> getOne( "SELECT value FROM ".$sqlname."modcatalog_field WHERE n_id = '".$n_id."' and pfid = '".$data['id']."' and identity = '$identity'" );

								$value = ($value != '') ? (array)explode( ";", $value ) : [];

								$val = [];

								foreach ($value as $xval) {

									if ( $xval != '' ) {
										$val[] = '<b>'.$xval.'</b>';
									}

								}

								$val = implode( "; ", $val );

								if ( $val == '' )
									$val = '--не заполнен--';

								$width = $data['pwidth'] - 1;

								if ( $data['tip'] != 'divider' ) {
									print '
									<tr>
										<td align="right">'.$data['name'].':</td>
										<td><div class="text-content">'.$val.'&nbsp;</div></td>
									</tr>
										';
									print '</div>';
								}
								if ( $data['tip'] == 'divider' )
									print '
									<tr>
										<td colspan="2"><div id="divider" style="width:97%; float:left; margin-top:10px" align="center"><b>'.$data['name'].'</b></div></td>
									</tr>
									';
								$val = '';
							}
							?>
						</table>
					</div>
				</div>
				<div id="tab-form-3" style="height: 60vh; max-height: 60vh; overflow-y: auto; overflow-x:hidden;"><br/>
					<div id="dogs"></div>
				</div>
				<div id="tab-form-4" style="height: 60vh; max-height: 60vh; overflow-y: auto; overflow-x:hidden;"><br/>
					<DIV id="dopzat" class="hidden"></DIV>
					<?php if ( $setEntry['enShowButtonLeft'] == 'yes' && $isEntry == 'on' ) { ?>
						<fieldset>
							<legend><b>Обращения</b></legend>
							<DIV id="entry"></DIV>
						</fieldset>
					<?php } ?>
					<fieldset>
						<legend><b>История изменений</b></legend>
						<DIV id="log"></DIV>
					</fieldset>
				</div>

			</div>

		</div>

	</div>
	<?php
	if ( in_array( $iduser1, $msettings['mcCoordinator'] ) ) {
		?>

		<hr>

		<div class="pull-aright button--pane">

			<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?n_id=<?= $n_id ?>&action=edit');" class="button" title="Редактировать"><i class="icon-pencil"></i>&nbsp;Редактировать</a>&nbsp;
			<a href="card.modcatalog.php?n_id=<?= $n_id ?>" class="button" title="Карточка" target="blank">Карточка&nbsp;<i class="icon-angle-right"></i></a>

		</div>
	<?php } ?>
	<script>
		$(function () {

			$('#dialog').css('width', '80%');

			$('#formtabs').tabs();

			$('#dogs').load("/modules/modcatalog/card.php?n_id=<?=$n_id?>&action=getDogs");
			$("#entry").load("/modules/modcatalog/card.entry.php?n_id=<?=$n_id?>").append('<img src="/assets/images/loading.gif">');
			$("#log").load("/modules/modcatalog/card.php?n_id=<?=$n_id?>&action=getLogs&page=").append('<img src="/assets/images/loading.gif">');

			var wd = $('#dialog').width() * 0.55;

			$('#htmldiv img').css("width", wd + "px").css("height", "auto");

			$('#dialog').center();

		});

		function logs(page) {
			if (!page) page = 1;
			$("#log").load("/modules/modcatalog/card.php?n_id=<?=$n_id?>&action=getLogs&page=" + page).append('<img src="/assets/images/loading.gif">');
		}
	</script>
	<?php
	exit();
}

/**
 * Не используется
 */
if ( $action == "editdop" ) {

	$id = $_REQUEST['id'];

	if ( $id > 0 ) {
		$result  = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_dop WHERE id = '".$id."' and identity = '$identity'" );
		$clid    = $result["clid"];
		$content = $result["content"];
		$summa   = num_format( $result["summa"] );
		$datum   = $result["datum"];
	}

	if ( !$datum )
		$datum = current_datum();
	?>
	<DIV class="zagolovok">Добавить / Изменить затраты</DIV>
	<FORM method="post" action="/modules/modcatalog/core.modcatalog.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="editdop_on">
		<input name="id" id="id" type="hidden" value="<?= $id ?>">
		<input name="n_id" id="n_id" type="hidden" value="<?= $n_id ?>">
		<table width="100%" border="0" cellspacing="2" cellpadding="2">
			<tr>
				<td width="100" align="right"><b>Дата:</b></td>
				<td><input type="text" name="datum" class="required" id="datum" value="<?= $datum ?>"></td>
			</tr>
			<tr>
				<td align="right"><b>Поставщик:</b></td>
				<td>
					<select name="clid" id="clid" class="required" style="width:90%">
						<option value="">--Выбор--</option>
						<?php
						$res = $db -> getAll( "SELECT clid, title FROM ".$sqlname."clientcat WHERE type = 'contractor' and identity = '$identity'" );
						foreach ( $res as $data ) {
							?>
							<option value="<?= $data['clid'] ?>" <?php if ( $data['clid'] == $clid )
								print "selected"; ?>><?= $data['title'] ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td align="right"><b>Сумма:</b></td>
				<td>
					<input name="summa" id="summa" type="text" class="required" value="<?= $summa ?>" autocomplete="off">&nbsp;<?= $valuta ?>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<hr>
					<b>Описание:</b><br><textarea name="content" rows="3" class="required" id="content" style="width: 98.7%;"><?= $content ?></textarea>
				</td>
			</tr>
		</table>
		<hr>
		<div class="button-pane text-right">
			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</DIV>
	</FORM>
	<script>

		$(function () {
			$("#datum").datepicker({
				dateFormat: "yy-mm-dd",
				firstDay: 1,
				changeMonth: true,
				changeYear: true,
				numberOfMonths: 2
			});
		});

		$(function () {

			$('#dialog').css('width', '600px');

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					logs();
					configpage();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});

			$('#dialog').center();
		});
	</script>
	<?php
	exit();
}

/**
 * Просмотр ордера
 */
if ( $action == "viewakt" ) {

	$id = (int)$_REQUEST['id'];

	$res   = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_akt WHERE id = '$id' and identity = '$identity'" );
	$clid  = (int)$res['clid'];
	$posid = (int)$res['posid'];
	$man1  = $res['man1'];
	$man2  = $res['man2'];
	$datum = $res['datum'];
	$tip   = $res['tip'];
	$isdo  = $res['isdo'];
	$did   = (int)$res['did'];

	if ( $tip == 'income' )
		$tip2 = "Приходный ордер";
	elseif ( $tip == 'outcome' )
		$tip2 = "Расходный ордер";

	?>
	<DIV class="zagolovok">Просмотр ордера</DIV>
	<div id="formtabse" style="max-height: 80vh; overflow-y: auto; overflow-x: hidden;">

		<div class="row">

			<div class="column12 grid-2 fs-12 gray2 right-text">Статус:</div>
			<div class="column12 grid-4 fs-12 Bold <?= ($isdo == 'yes') ? 'green' : 'red'; ?>"><?= ($isdo == 'yes') ? 'Проведен' : 'Черновик'; ?></div>

			<div class="column12 grid-2 fs-12 gray2 right-text">Склад:</div>
			<div class="column12 grid-4 fs-12"><?= Storage ::getSklad( $res['sklad'], 'title' ) ?></div>

		</div>
		<div class="row">

			<div class="column12 grid-2 fs-12 gray2 right-text">Тип:</div>
			<div class="column12 grid-4 fs-12"><?= $tip2 ?></div>

			<div class="column12 grid-2 fs-12 gray2 right-text">Дата:</div>
			<div class="column12 grid-4 fs-12"><?= get_sfdate( $datum ) ?></div>

		</div>
		<div class="row">

			<div class="column12 grid-2 fs-12 gray2 right-text">Сдал:</div>
			<div class="column12 grid-4 fs-12"><?= $man1 ?></div>

			<div class="column12 grid-2 fs-12 gray2 right-text">Принял:</div>
			<div class="column12 grid-4 fs-12"><?= $man2 ?></div>

		</div>

		<?php
		if ( $tip == 'income' ) {
			?>
			<div class="row">

				<div class="column12 grid-2 fs-12 gray2 right-text">Поставщик:</div>
				<div class="column12 grid-10 fs-12">
					<a href="javascript:void(0)" onclick="openClient('<?= $posid ?>')"><i class="icon-building broun"></i><?= current_client( $posid ) ?>
					</a></div>

			</div>
		<?php } ?>
		<?php
		if ( $tip == 'outcome' ) {
			?>
			<div class="row <?=($clid > 0 ? '' : 'hidden')?>">

				<div class="column12 grid-2 fs-12 gray2 right-text">Покупатель:</div>
				<div class="column12 grid-10 fs-12">
					<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')"><i class="icon-building broun"></i><?= current_client( $clid ) ?>
					</a></div>

			</div>
			<div class="row <?=($did > 0 ? '' : 'hidden')?>">

				<div class="column12 grid-2 fs-12 gray2 right-text">Сделка:</div>
				<div class="column12 grid-10 fs-12">
					<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')"><i class="icon-briefcase broun"></i><?= current_dogovor( $did ) ?>
					</a></div>

			</div>
		<?php } ?>

		<div id="divider"><b>Продукты</b></div>

		<div class="pad5">

			<table width="100%" border="0" cellspacing="0" cellpadding="4" id="bborder">
				<thead>
				<tr class="header_contaner">
					<th>Продукт</th>
					<th width="80" align="center">Кол-во</th>
					<th width="100" align="right">Стоимость</th>
				</tr>
				</thead>
				<?php

				$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_aktpoz WHERE ida = '$id' and identity = '$identity'" );
				foreach ( $res as $data ) {

					$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

					print '
			<tr height="40">
				<td><b>'.$title.'</b></td>
				<td align="center">'.num_format( $data['kol'] ).'</td>
				<td align="right">'.num_format( $data['price_in'] ).'</td>
			</tr>';
				}

				?>
			</table>

		</div>

	</div>

	<hr>

	<div class="button--pane text-right">

		<a href="/modules/modcatalog/printorder.php?id=<?= $id ?>&tip=order" target="_blank" class="button"><i class="icon-print"></i>Распечатать</a>

	</div>
	<script type="text/javascript">

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		if (!isMobile) {

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '800px'});
				$('#formtabse').css({'max-height': hh2});

			}
			else {

				$('#dialog').css('width', '80%');
				$('#formtabse').css('max-height', hh2);

			}

		}
		else{

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 120;
			$('#formtabse').css({'max-height': h2 + 'px','height': h2 + 'px'});
			$(".multiselect").addClass('wp97 h0');

		}

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php
	exit();
}

//проработать создание расходных ордеров без резерва, заявок
//при этом при загрузке спецификации проверять свободные позиции на складе
//а при проведении удалять неиспользованные резервы под сделку и заявки
if ( $action == "editakt" ) {

	$tip  = $_REQUEST['tip'];
	$id   = (int)$_REQUEST['id'];
	$odid = (int)$_REQUEST['odid'];
	$did  = (int)$_REQUEST['did'];

	$odogovor = current_dogovor( $odid );

	$ff = 0;

	if ( $id ) {

		$res      = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_akt WHERE id = '$id' and identity = '$identity'" );
		$clid     = $res['clid'];
		$posid    = $res['posid'];
		$man1     = $res['man1'];
		$man2     = $res['man2'];
		$datum    = $res['datum'];
		$tip      = $res['tip'];
		$isdo     = $res['isdo'];
		$did      = $res['did'];
		$cFactura = $res['cFactura'];
		$cDate    = $res['cDate'];
		if ( $cDate == '0000-00-00' )
			$cDate = '';
		$idz   = $res['idz'];
		$sklad = $res['sklad'];

	}

	if ( $_REQUEST['idz'] > 0 ) {

		$idz = $_REQUEST['idz'];

		$res        = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE id = '$idz' and identity = '$identity'" );
		$did        = $res['did'];
		$posid      = $res['conid'];
		$providerid = $res['providerid'];
		$tip        = 'income';

		$zayHidden = 'hidden';

	}

	if ( $tip == 'income' ) {

		if ( $id < 1 )
			$man2 = current_user( $iduser1 );
		$sd      = 'class="required"';
		$cd      = 'disabled';
		$fb      = "hidden";
		$sb      = "";
		$otitle  = "Приходный";
		$priceTR = '';

	}
	elseif ( $tip == 'outcome' ) {

		if ( $id < 1 )
			$man1 = current_user( $iduser1 );
		$sd      = 'disabled';
		$cd      = 'class="required"';
		$fb      = "hidden";
		$sb      = "";
		$otitle  = "Расходный";
		$priceTR = 'hidden';

	}

	if ( $odid > 0 && $tip == 'outcome' ) {

		//ищем

		$did = $odid;

	}

	//ввод серийников в ордерах (поштучный учет)
	//для приходных ордеров вызываем форму ввода серийников после проведения
	//для расходных ордеров подгружаем список имеющихся серийников (мультиселект)
	?>
	<DIV class="zagolovok">Создать <?= $otitle ?> ордер</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editakt_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<input type="hidden" name="tip" id="tip" value="<?= $tip ?>">
		<input type="hidden" name="clid" id="clid" value="<?= $clid ?>">
		<input type="hidden" name="adid" id="adid" value="<?= $did ?>">
		<input type="hidden" name="spcount" id="spcount" value="<?= $co ?>">

		<div id="formtabse" style="overflow-y: auto; max-height: 80vh; height: 80vh">

			<div class="row">

				<div class="column12 grid-2 fs-12 pt7 right-text">Сдал:</div>
				<div class="column12 grid-4">
					<input type="text" name="man1" id="man1" value="<?= $man1 ?>" class="" style="width:98%">
				</div>
				<div class="column12 grid-2 fs-12 pt7 right-text">Принял:</div>
				<div class="column12 grid-4">
					<input type="text" name="man2" id="man2" value="<?= $man2 ?>" class="" style="width:90%">
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Сч.фактура №:</div>
				<div class="column12 grid-4">
					<input type="text" name="cFactura" id="cFactura" value="<?= $cFactura ?>" style="width:98%">
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Дата сч.фактуры:</div>
				<div class="column12 grid-4">
					<input type="text" name="cDate" id="cDate" value="<?= $cDate ?>" class="datum">
				</div>

				<?php if ( $tip == 'income' ) { ?>
					<div class="column12 grid-2 fs-12 pt7 right-text">По заявке:</div>
					<div class="column12 grid-4">
						<select name="idz" id="idz" style="width:97%" onchange="specaLoad4()">
							<?php if ( $idz < 1 ) { ?>
								<option value="">--Выбор--</option>
								<?php
							}

							$apx = ($did > 0) ? " and did = '".$did."'" : "";

							$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE status = '2' $apx and identity = '$identity'" );
							foreach ( $res as $data ) {

								if ( $data['did'] > 0 )
									$d = '['.current_dogovor( $data['did'] ).']';
								else $d = '(--без сделки--)';

								$ss = ($data['id'] == $idz || $data['did'] == $did) ? 'selected' : '';

								//количество, в приходных ордерах по текущей заявке
								$kol_do = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE idz = '".$data['id']."' and identity = '$identity') and identity = '$identity'" );

								//print "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE idz = '".$data['id']."' and identity = '$identity') and identity = '$identity'\n";

								//количество в заявке
								$kol_zay = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz = '".$data['id']."' and identity = '$identity'" );

								//вычисляем количество, которое еще не находится в расходниках
								$delta_z = $kol_zay - $kol_do;

								if ( $delta_z > 0 or $data['id'] == $idz )
									print '<option value="'.$data['id'].'" '.$ss.'>#'.$data['number'].': '.$d.'</option>';

							}
							?>
						</select>
					</div>
					<div class="column12 grid-2 fs-12 pt7 right-text">&nbsp;</div>
					<div class="column12 grid-4">&nbsp;</div>

					<?php if ( $id < 1 ) { ?>

						<div id="filee" style="width:97%">
							<div class="column12 grid-2 fs-12 pt7 right-text">или из файла:</div>
							<div class="column12 grid-10">
								<input name="file" type="file" class="file" id="file" style="width:97%" onchange="upload()"/>
							</div>
						</div>

					<?php } ?>

					<div class="column12 grid-2 fs-12 pt7 right-text">Склад:</div>
					<div class="column12 grid-4">
						<select name="sklad" id="sklad" class="required" style="width: 97%;">
							<OPTION value="">--Выбор--</OPTION>
							<?php
							$result = $db -> getAll( "SELECT id, name_shot FROM ".$sqlname."mycomps WHERE identity = '$identity'" );
							foreach ( $result as $data ) {

								print '<optgroup label="'.$data['name_shot'].'" data-mcid="'.$data['id'].'">';

								$res = $db -> getAll( "SELECT id, title, isDefault FROM ".$sqlname."modcatalog_sklad WHERE mcid = '".$data['id']."' and identity = '$identity'" );
								foreach ( $res as $da ) {

									$ss = ($da['id'] == $sklad) ? "selected" : '';
									$cc = ($da['isDefault'] == "yes") ? 'data-def="isDefault"' : '';

									print '<option value="'.$da['id'].'" '.$ss.' '.$cc.'>'.$da['title'].'</option>';

								}

								print '</optgroup>';

							}
							?>
						</select>&nbsp;
					</div>
					<div class="column12 grid-2 fs-12 pt7 right-text"></div>
					<div class="column12 grid-4"></div>

					<div class="column12 grid-2 fs-12 pt7 right-text">Поставщик:</div>
					<div class="column12 grid-10">
						<select name="posid" id="posid" disabled1 style="width:97%">
							<option value="">--Выбор--</option>
							<?php
							$res = $db -> getAll( "SELECT clid, title FROM ".$sqlname."clientcat WHERE type = 'contractor' and identity = '$identity'" );
							foreach ( $res as $data ) {
								?>
								<option value="<?= $data['clid'] ?>" <?php if ( $data['clid'] == $posid )
									print "selected"; ?>><?= $data['title'] ?></option>
							<?php } ?>
						</select>
					</div>

				<?php } ?>

				<?php if ( $tip == 'outcome' ) { ?>
					<div class="column12 grid-2 fs-12 pt7 right-text">Склад:</div>
					<div class="column12 grid-4">
						<?php
						//будем учитывать каждую позицию
						//if($msettings['mcSklad'] == 'yes'){
						?>
						<select name="sklad" id="sklad" class="required" style="width: 97%;" onchange="specaLoad2()">
							<OPTION value="">--Выбор--</OPTION>
							<?php
							$result = $db -> getAll( "SELECT id, name_shot FROM ".$sqlname."mycomps WHERE identity = '$identity'" );
							foreach ( $result as $data ) {

								print '<optgroup label="'.$data['name_shot'].'" data-mcid="'.$data['id'].'">';

								$res = $db -> getAll( "SELECT id, title FROM ".$sqlname."modcatalog_sklad WHERE mcid = '".$data['id']."' and identity = '$identity'" );
								foreach ( $res as $da ) {
									if ( $da['id'] == $sklad )
										$ss = "selected";
									else $ss = '';

									print '<option value="'.$da['id'].'" '.$ss.'>'.$da['title'].'</option>';

								}

								print '</optgroup>';

							}
							?>
						</select>&nbsp;
						<?php //} ?>
					</div>
					<div class="column12 grid-2 fs-12 pt7 right-text"></div>
					<div class="column12 grid-4"></div>

					<div class="column12 grid-2 fs-12 pt7 right-text">Сделка:</div>
					<div class="column12 grid-10">
						<input name="dogovor" type="text" id="dogovor" style="width: 97%;" value="<?= current_dogovor( $did ) ?>" placeholder="Начните вводить название">
						<input name="did" type="hidden" id="did" value="<?= $did ?>">
					</div>
				<?php } ?>

			</div>

			<hr>

			<table id="tbspeca" class="top">
				<thead class="header_contaner">
				<tr>
					<th align="center">Продукт</th>
					<th width="100" align="center" class="<?= $priceTR ?>">Цена</th>
					<th width="120" align="center">Кол-во</th>
					<th width="30" align="center"></th>
				</tr>
				</thead>
				<tbody>
				<?php
				if ( !$id ) {
					$co = 0;
				}
				else {

					$i      = 0;
					$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_aktpoz WHERE ida = '$id' and identity = '$identity'" );
					foreach ( $result as $data ) {

						$apdx = '';

						$title   = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );
						$kol_in  = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_skladpoz WHERE sklad = '$sklad' and prid = '".$data['prid']."' and identity = '$identity'" ) + 0;
						$kol_res = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_reserv WHERE sklad = '$sklad' and prid = '".$data['prid']."' and did = '$did' and identity = '$identity'" ) + 0;

						//$kol_in = $kol_in + $kol_res;

						if ( $kol_in < intval( $data['kol'] ) ) {
							if ( $tip == 'outcome' )
								$ss = 'border:1px solid red';
							$tt = '<span class="red smalltxt">На складе - <b>'.num_format( $kol_in ).'</b> позиций (с учетом резерва под сделку).</span>';
							if ( $tip != 'income' ) {
								$ff++;
							}
						}
						else {
							$ss = '';
							$tt = '<span class="green smalltxt">На складе - <b>'.num_format( $kol_in ).'</b> позиций (с учетом резерва под сделку).</span>';
						}

						//если включен поштучный учет, то подгрузим к нему список серийников
						if ( $msettings['mcSkladPoz'] == "yes" && $tip == 'outcome' ) {

							$apdx = '
							<tr class="i'.$i.'"><td colspan="3">
							<div class="infodiv">
								<select id="serial['.$data['prid'].'][]" name="serial['.$data['prid'].'][]" multiple="multiple" class="multiselect" data-id="i'.$i.'">';

							$re = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE sklad = '$sklad' and prid = '".$data['prid']."' and (did = '$did' or did < 1) and identity = '$identity'" );
							foreach ( $re as $da ) {

								$ss           = ($da['did'] == $did || $da['idorder_out'] == $id) ? "selected" : "";
								$da['serial'] = ($da['serial'] != '') ? $da['serial'] : "б/н";

								$apdx .= '<option value="'.$da['id'].'" '.$ss.'>'.$da['serial'].'</option>';

							}

							$apdx .= '
								</select>
							</div>
							</td></tr>';

						}

						print '
							<tr id="i'.$i.'" class="th40" data-id="i'.$i.'">
								<td><input name="idp[]" id="idp[]" type="hidden" value="'.$data['id'].'"><input name="prid[]" id="prid[]" type="hidden" value="'.$data['prid'].'"><input name="speca_title[]" type="text" id="speca_title[]" value="'.$title.'" style="width:100%" class="requered"/>'.$tt.'</td>
								<td class="'.$priceTR.' text-center"><input name="speca_price[]" id="speca_price[]" type="text" value="'.num_format( $data['price_in'] ).'" style="width:90%;" class="requered"/></td>
								<td class="text-center"><input name="speca_kol[]" id="speca_kol[]" type="text" value="'.num_format( $data['kol'] ).'" style="width:90%; '.$ss.'" class="requered" readonly/></td>
								<td class="text-center"><div class="fpoleCold"><a href="javascript:void(0)" onclick="prTRremove('.$i.');"><i class="icon-cancel-circled red" title="Удалить"></i></a></div></td>
							</tr>'.$apdx;
						$i++;
					}
					$co = $i;

				}
				?>
				</tbody>
			</table>
			<?php //if ( $tip != "outcome" && $idz == '' ) { ?>
				<br>
				<div align="center" id="addPozButton">
					<a href="javascript:void(0)" onclick="prTRclone2()" class="sbutton"><span>Добавить продукт</span></a>
				</div>
			<?php //} ?>

		</div>

		<hr>

		<div class="text-right button--pane">

			<span style="line-height: 40px;">
				<label><input type="checkbox" name="isdo" id="isdo" value="yes" <?php if ( $ff > 0 )
						print "disabled"; ?>>&nbsp;Провести</label>&nbsp;&nbsp;
			</span>
			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>
			<span id="fakebutton" class="<?= $fb ?>">
				<A href="javascript:void(0)" class="button" title="Провести невозможно">Сохранить</A>
			</span>
			<span id="submitbutton" class="<?= $sb ?>">
				<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			</span>

		</div>

	</FORM>

	<script>

		var hh = $('#dialog_container').actual('height') * 0.95;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		if (!isMobile) {

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '900px'});
				$('#formtabse').css({'max-height': hh2});
			}
			else {
				$('#dialog').css('width', '80%');
				$('#formtabse').css('max-height', hh2);
			}

		}
		else{

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3 - 120;
			$('#formtabse').css({'max-height': h2 + 'px','height': h2 + 'px'});
			$(".multiselect").addClass('wp97 h0');

		}

		$(function () {

			var id = parseInt($('#id').val());
			var idz = parseInt($('#idz').val());
			var did = parseInt('<?=$did?>');
			var odid = parseInt('<?=$odid?>');
			var tip = $('#tip').val();

			<?php
			if($id < 1 && $odid > 0 && $tip == 'outcome'){
			?>

			$('#did').val('<?=$odid?>');
			$('#dogovor').val('<?=$odogovor?>');
			specaLoad2();

			<?php
			}

			//if($id < 1 && $tip == 'income') print 'specaLoad2();';
			if ( $id < 1 && ($idz > 0 || $did > 0) && $tip == 'income' )
				print 'specaLoad4();';

			if($id > 0 and $ff > 0){
			?>
			//$('#isdo').attr('disabled', true);
			<?php
			}
			?>

			$(".datum").datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

			$(".multiselect").multiselect({sortable: true, searchable: true});

			$(document).on("change", ".multiselect", function () {

				var id = $(this).data('id');
				var kol = $('#' + id).find('speca_kol\\[\\]').val();
				var count = $(this + ' option:selected').length();

				if (kol != count) $('#isdo').attr('disabled', true).attr('checked', false);
				else $('#isdo').attr('disabled', false);

				//console.log(kol + '::' + count);

			});

			$("#dogovor").autocomplete("/content/helpers/deal.helpers.php?action=doglist&closed=no&mcid=", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, i, n, value) {
					return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<div class="blue smalltext">' + data[3] + '</div><span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span></div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			});
			$("#dogovor").result(function (value, data) {

				var tip = $('#tip').val();

				$('#did').val(data[1]);

				if (tip == 'income') specaLoad3();
				if (tip == 'outcome') specaLoad2();
			});

			<?php
			if ( $_REQUEST['idz'] > 0 )
				print 'specaLoad4();';
			?>

			$('#dialog').center();

		});

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				var error = '';

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');

				//для поштучного учета вызываем форму ввода серийников для каждой позиции
				if (data.doit == 'yes') doLoad('/modules/modcatalog/form.modcatalog.php?id=' + data.id + '&action=editaktperpoz&tip=income');

				if (typeof configpage === 'function') {
					configpage();
				}

				if (typeof getCatalog === 'function') {
					getCatalog();
				}

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.message);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}
		});

		function selItem(i, price) {
			$("#pr_" + i + " #speca_price\\[\\]").val(price);
		}

		function addfile() {
			$('#filepole').html('<input name="file" type="file" class="file" id="file" style="width:97%" />');
		}

		function prTRclone2() {

			var i = parseInt($("#spcount").val()) + 1;

			$('#spcount').val(i);

			var trhtml = '<tr id="pr_' + i + '"><td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id="prid[]" type="hidden" value=""><input name="speca_title[]" type="text" id="speca_title[]" value="" style="width:100%" class="requered" placeholder="Начните вводить наименование"/></td><td align="center" valign="top" class="<?=$priceTR?>"><input name="speca_price[]" id="speca_price[]" type="text" value="" style="width:90%;" class="requered"/></td><td align="center"><input name="speca_kol[]" type="text" id="speca_kol[]" value="1,00" style="width:90%"></td><td align="center" valign="top"><div class="pt2 delete"><i class="icon-cancel-circled red hand" title="Удалить"></i></div></td></tr>';

			$('#tbspeca').append(trhtml);

			//$("#pr_"+i+" #speca_title\\[\\]").autocomplete("price/autoprice.php", {autofill: true, minChars: 2, cacheLength: 1, maxItemsToShow:20, selectFirst: false, multiple: false,  delay: 10, matchSubset: 1});

			$("#speca_title\\[\\]").autocomplete("/modules/modcatalog/autoprice.php", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {
					return '<div>' + data[0] + '</div>';
				},
				formatResult: function (data) {

					return data[0];

				}
			});
			$("#speca_title\\[\\]").result(function (value, data) {

				var price;

				if ($('#tip').val() == 'income') price = data[3];
				else price = data[1];

				$(this).closest('tr').find('#prid\\[\\]').val(data[2]);
				$(this).closest('tr').find('#speca_price\\[\\]').val(price);

			});

			$('#dialog').center();

		}

		function prTRremove(id) {

			$('#tbspeca #i' + id).remove();
			$('#dialog').center();

		}

		function didLoad() {

			var clid = $('#clid option:selected').val();
			var url = '/modules/modcatalog/core.modcatalog.php?action=didlist&t=order&clid=' + clid;

			$.post(url, function (data) {

				$('#did').empty().append(data).removeAttr('disabled').attr('enabled', 'enabled');
				$('#tbspeca tbody').empty();

			});

		}

		function specaLoad() {

			var did = $('#did option:selected').val();
			var count = $('#spcount').val();
			var url = '/modules/modcatalog/core.modcatalog.php?action=specalist&did=' + did + '&count=' + count;

			$.post(url, function (data) {

				$('#tbspeca tbody').empty().append(data);

				var cc = $('#tbspeca .red').length();

				//alert(cc);
				if (cc > 0) {
					$('#isdo').attr('checked', false).attr('disabled', true);
				}
				else {
					$('#isdo').attr('checked', true).attr('disabled', false);
				}

			});

		}

		//outcome
		function specaLoad2() {

			var did = $('#did').val();
			var sklad = $('#sklad option:selected').val();
			var count = $('#spcount').val();
			var idz = '';
			var url = '/modules/modcatalog/core.modcatalog.php?action=specalist2&t=outcome&sklad=' + sklad + '&did=' + did + '&idz=' + idz + '&count=' + count;
			var cc = 0;

			if (did > 0) {

				$('#sklad').find('optgroup').removeAttr('disabled');

				$('#tbspeca tbody').empty().append('<tr><td><img src="/assets/images/loading.gif"> Загрузка данных..</td></tr>');

				$.getJSON(url, function (data) {

					var da = data.speca;
					var trhtml = '';

					for (var i in da) {

						var row = da[i];
						var ss = '';
						var tt = '';
						var noexist = '';
						var apdx = '';

						if (parseInt(row.kol_free) < parseFloat(row.kol) && parseInt(row.kol_res) < parseFloat(row.kol)) {

							ss = 'border:1px solid red';
							tt = '<span class="red smalltxt">На складе - <b>' + row.kol_in + '</b> позиций (В резерве под сделку - ' + row.kol_res + '; В резерве по другим сделкам - ' + row.kol_resother + ').</span>';
							cc++;
							noexist = 'bad';

						}
						else {

							tt = '<span class="green smalltxt">На складе - <b>' + row.kol_in + '</b> позиций (В резерве под сделку - ' + row.kol_res + ').</span>';

						}

						var apdxx = row.apdx;
						var serial = '';

						if (apdxx.length > 0) {

							/*
							for (var j in apdxx) {

								serial = serial + '<option value="' + apdxx[j].id + '" ' + apdxx[j].selected + '>' + apdxx[j].serial + '</option>';

							}

							apdx =
								'<tr class="i' + i + '"><td colspan="3">' +
								'<div class="infodiv">' +
								'<select id="serial[' + row.prid + '][]" name="serial[' + row.prid + '][]" multiple="multiple" class="multiselect" data-id="i' + i + '">' + serial + '</select>' +
								'</div>' +
								'<td></tr>';
							*/

							for (var j in apdxx) {

								apdxx[j].selected = (apdxx[j].selected == 'selected') ? "checked" : "";

								serial = serial +
									'<div class="inline flex-string infodiv p10 mr5 mb5">' +
									'   <div class="checkbox">' +
									'       <label>' +
									'       <input name="serial[' + row.prid + '][]" type="checkbox" class="serial" id="serial[' + row.prid + '][]" ' + apdxx[j].selected + ' value="' + apdxx[j].id + '" data-id="i' + i + '">' +
									'       <span class="custom-checkbox success1"><i class="icon-ok"></i></span>' +
									'       <span class="pl10">' + apdxx[j].serial + '</span>' +
									'       </label>' +
									'   </div>' +
									'</div>';

							}

							apdx = '<tr class="i' + i + '"><td colspan="3"><div class="flex-container">' + serial + '</div><td></tr>';

						}

						//console.log(apdx);

						trhtml += '<tr id="i' + i + '" class="th40 ' + noexist + '" data-id="i' + i + '"><td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id=prid[]" type="hidden" value="' + row.prid + '"><input name="speca_title[]" type="text" id="speca_title[]" value="' + row.title + '" class="wp100 requered" placeholder="Начните вводить наименование"/>' + tt + '</td><td valign="top" class="text-center <?=$priceTR?>"><input name="speca_price[]" type="text" id="speca_price[]" value="' + row.price + '" class="wp90"></td><td align="center" valign="top"><input name="speca_kol[]" type="text" id="speca_kol[]" value="' + row.kol + '" style="' + ss + '" class="wp90" readonly></td><td valign="top"><div class="paddtop5" onclick="thisRem(\'' + i + '\')"><i class="icon-cancel-circled red hand" title="Удалить"></i></div></td></tr>' + apdx;

					}

					$('#clid').val(data.clid);

					$('#tbspeca tbody').empty().append(trhtml);

					$('#addPozButton').hide();

					if (cc > 0) {
						//$('#fakebutton').removeClass("hidden");
						//$('#submitbutton').addClass("hidden");
						$('#isdo').attr('disabled', true).attr('checked', false);
					}
					else {
						//$('#fakebutton').addClass("hidden");
						//$('#submitbutton').removeClass("hidden");
						$('#isdo').attr('disabled', false);//.attr('checked',true);
					}

					if (data.mcid !== 0) $('#sklad').find('optgroup').not('[data-mcid="' + data.mcid + '"]').attr('disabled', 'disabled');

					//$(".multiselect").multiselect({sortable: true, searchable: true});
					/*$('.multiselect').bind("change", function () {

						var id = $(this).data('id');
						var kol = $('#' + id).find('speca_kol\\[\\]').val();
						var count = $('option:selected', this).length();

						if (kol != count || kol == 0) $('#isdo').attr('disabled', true).attr('checked', false);
						else $('#isdo').attr('disabled', false);

					});*/

					$('.checkbox').bind('click', function () {

						var id = $(this).data('id');
						var kol = $('#' + id).find('speca_kol\\[\\]').val();
						var count = $('.serial[data-id="' + id + '"]:checked').length();

						if (kol != count || kol == 0) $('#isdo').prop('disabled', true).prop('checked', false);
						else $('#isdo').prop('disabled', false);

					});

				});

			}

			$('#dialog').center();

		}

		//income + deal
		function specaLoad3() {

			var did = $('#did').val();
			var count = $('#spcount').val();
			var sklad = $('#sklad option:selected').val();
			var url = '/modules/modcatalog/core.modcatalog.php?action=specalist3&sklad=' + sklad + '&did=' + did + '&count=' + count;
			var cc = 0;
			var ff = 0;

			$('#tbspeca tbody').empty().append('<tr><td><img src="/assets/images/loading.gif"> Загрузка данных..</td></tr>');
			$('#idz').attr("disabled", "disapled");

			$('#addPozButton').hide();
			$('.fromfile').hide();

			$.getJSON(url, function (data) {

				var da = data.speca;
				var trhtml = '';

				for (var i in da) {

					var row = da[i];
					var comment = '<div class="blue">На складе имеется ' + row.kol_in + '</div>';

					if (row.kol > row.kol_in) {
						comment = '<div class="red">Нет достаточного количества. Имеется ' + row.kol_in + '</div>';
						cc++;
					}
					else ff++;

					trhtml = trhtml + '<tr height="40"><td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id="prid[]" type="hidden" value="' + row.prid + '"><input name="speca_title[]" type="text" id="speca_title[]" value="' + row.title + '" style="width:100%" class="requered" placeholder="Начните вводить наименование"/>' + comment + '</td><td align="center" valign="top"><input name="speca_price[]" type="text" id="speca_price[]" value="' + row.price + '" style="width:90%;"></td><td align="center" valign="top"><input name="speca_kol[]" type="text"  id="speca_kol[]" value="' + setNumFormat(row.kol, ',', ' ').replace('.', ',') + '" style="width:90%;"></td><td align="center" valign="top"><div class="pt2 delete"><i class="icon-cancel-circled red hand" title="Удалить"></i></div></td></tr>';


				}

				$('#tbspeca tbody').empty();
				$('#tbspeca').append(trhtml);

				$('#posid [value="' + data.conid + '"]').attr("selected", "selected");

				if (cc > 0 || ff == 0) $('#isdo').attr("disabled", "disabled");
				else $('#isdo').attr("enabled", "enabled");

				$('#dialog').center();

			});
		}

		//income + zayavka
		function specaLoad4() {

			var idz = $('#idz option:selected').val();
			var count = $('#spcount').val();
			var sklad = $('#sklad').val();
			var did = $('#did').val();
			var url = '/modules/modcatalog/core.modcatalog.php?action=specalist3&sklad=' + sklad + '&idz=' + idz + '&count=' + count + '&did=' + did;
			var cc = 0;
			var ff = 0;

			$('#tbspeca tbody').empty().append('<tr><td><img src="/assets/images/loading.gif"> Загрузка данных..</td></tr>');
			$('#did').attr("disabled", "disapled");

			//
			//$('#posid').attr("disabled", "disapled");

			$('#sklad').find('optgroup').removeAttr('disabled');
			$('#filee').addClass('hidden');

			$('#addPozButton').hide();
			$('.fromfile').hide();

			$.getJSON(url, function (data) {

				var da = data.speca;
				var tip = $('#tip').val();
				var trhtml = '';

				for (var i in da) {

					var row = da[i];
					var comment = '<div class="blue">На складе имеется ' + row.kol_in + '</div>';

					if (tip == 'outcome') {
						if (row.kol > row.kol_in) {
							comment = '<div class="red">Нет достаточного количества. Имеется ' + row.kol_in + '</div>';
							cc++;
						}
						else ff++;
					}

					trhtml = trhtml + '<tr height="40"><td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id="prid[]" type="hidden" value="' + row.prid + '"><input name="speca_title[]" type="text" id="speca_title[]" value="' + row.title + '" style="width:100%" class="requered" placeholder="Начните вводить наименование"/>' + comment + '</td><td align="center" valign="top"><input name="speca_price[]" type="text" id="speca_price[]" value="' + row.price + '" style="width:90%;"></td><td align="center" valign="top"><input name="speca_kol[]" type="text" id="speca_kol[]" value="' + setNumFormat(row.kol, ',', ' ').replace('.', ',') + '" style="width:90%;"></td><td align="center" valign="top"><div class="pt2 delete"><i class="icon-cancel-circled red hand" title="Удалить"></i></div></td></tr>';

				}

				$('#tbspeca tbody').empty();
				$('#tbspeca').append(trhtml);

				$('#posid [value="' + data.conid + '"]').attr("selected", "selected");
				$('#did').val(data.did);
				$('#dogovor').val(data.dogovor);

				if (data.mcid != 0) {

					$('#sklad').find('optgroup').not('[data-mcid="' + data.mcid + '"]').attr('disabled', 'disabled');
					$('#sklad').find('[data-mcid="' + data.mcid + '"]').find('[data-def="isDefault"]').attr('selected', 'selected');

				}

				if (tip == 'outcome' && (cc > 0 || ff == 0)) $('#isdo').attr("disabled", "disabled");
				else $('#isdo').removeAttr('disabled');

				$('#dialog').center();

				//console.log(data);

			});
		}

		function thisRem(id) {
			$('#i' + id).remove();
			$('tr.i' + id).remove();

			var count = $('tr.bad').length;

			if (count == 0) $('#isdo').attr('disabled', false);

		}

		function upload() {

			var $input = $("#file");
			var fd = new FormData;
			var trhtml = '';

			var j = parseInt($("#spcount").val()) + 1;

			$('#tbspeca tbody').empty().append('<tr><td><img src="/assets/images/loading.gif"> Загрузка данных..</td></tr>');
			$('#idz').attr("disabled", "disabled");

			fd.append('file', $input.prop('files')[0]);

			$.ajax({
				url: '/modules/modcatalog/core.modcatalog.php?action=upload',
				data: fd,
				processData: false,
				contentType: false,
				dataType: 'json',
				type: 'POST',
				success: function (data) {

					var da = data.list;

					for (var i in da) {

						var row = da[i];

						trhtml = trhtml + '<tr height="40" id="pr_' + j + '"><td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id="prid[]" type="hidden" value="' + row.prid + '"><input name="speca_title[]" type="text" id="speca_title[]" value="' + row.title + '" style="width:98%" class="requered" placeholder="Начните вводить наименование"/></td><td align="center" valign="top"><input name="speca_kol[]" type="text" id="speca_kol[]" value="' + setNumFormat(row.kol, ',', ' ').replace('.', ',') + '" style="width:70%;"></td><td align="center" valign="top"><input name="speca_price[]" id="speca_price[]" type="text" min="1" value="' + row.price + '" style="width:90%" class="requered" valign="top"></td><td align="center" valign="top"><div class="pt2 delete"><i class="icon-cancel-circled red hand" title="Удалить"></i></div></td></tr>';

						j++;

					}

					$('#tbspeca tbody').empty();
					$('#tbspeca').append(trhtml);

					$("#spcount").val(j);

					$('#file').val('');

				}
			});

			$('#dialog').center();
		}

		$(document).on('click', '.delete', function () {
			var id = $(this).closest('tr').data('id');

			$(this).closest('tr').remove();
			$('tr .' + id).remove();
		});

	</script>
	<?php

	exit();

}

//ввод серийников после приходного ордера с поштучным учетом
if ( $action == "editaktperpoz" ) {

	$id = (int)$_REQUEST['id'];

	$order    = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_akt WHERE id = '$id' and identity = '$identity'" );
	$orderNum = $order['number'];
	$tip      = $order['tip'];

	$sklad = $db -> getOne( "SELECT title FROM ".$sqlname."modcatalog_sklad WHERE id = '".$order['sklad']."' and identity = '$identity'" );

	if ( $id > 0 ) {

		$list = [];

		$ord = ($tip == 'income') ? "idorder_in = '$id'" : "idorder_out = '$id'";

		$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE $ord and identity = '$identity'" );
		foreach ( $res as $da ) {

			$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$da['prid']."' and identity = '$identity'" );

			$list[] = [
				"id"          => $da['id'],
				"serial"      => $da['serial'],
				"date_create" => $da['date_create'],
				"date_period" => $da['date_period'],
				"prid"        => $da['prid'],
				"title"       => $title
			];

		}

	}

	//ввод серийников в ордерах (поштучный учет)
	//для приходных ордеров вызываем форму ввода серийников после проведения
	//для расходных ордеров подгружаем список имеющихся серийников (мультиселект)
	?>

	<DIV class="zagolovok">Ввод данных по позициям</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editaktperpoz_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<div id="formtabse" style="overflow-y: auto; min-height: 400px">

			<div class="row">

				<div class="column12 grid-2 fs-12 right-text gray2">По Ордеру:</div>
				<div class="column12 grid-10 fs-12">№ <b><?= $orderNum ?></b></div>

				<div class="column12 grid-2 fs-12 right-text gray2">Склад:</div>
				<div class="column12 grid-10 fs-12"><?= $sklad ?></div>

			</div>

			<hr>
			<table width="100%" border="0" cellspacing="1" cellpadding="5">
				<thead>
				<tr>
					<th align="center">Название</th>
					<th width="250" align="center">Серийный номер</th>
					<th width="120" align="center">Дата выпуска</th>
					<th width="120" align="center">Дата поверки</th>
					<th width="30" align="center"></th>
				</tr>
				</thead>
				<tbody>
				<?php
				$i = 0;
				foreach ( $list as $data ) {

					$s1 = $s2 = '';

					if ( $i == 0 ) {
						$s1 = '&nbsp;<i class="icon-down-big blue fs-09 dcreate hand" title="Заполнить всё"></i>';
						$s2 = '&nbsp;<i class="icon-down-big blue fs-09 dperiod hand" title="Заполнить всё"></i>';
					}

					print '
					<tr height="40" data-num="'.$i.'">
						<td><input name="id['.$i.']" id="id['.$i.']" type="hidden" value="'.$data['id'].'"><input name="prid['.$i.']" id="prid['.$i.']" type="hidden" value="'.$data['prid'].'"><input name="title['.$i.']" type="text" id="title['.$i.']" value="'.$data['title'].'" style="width:98%" readonly/></td>
						<td align="center"><input name="serial['.$i.']" id="serial['.$i.']" type="text" value="'.$data['serial'].'" style="width:90%;" class=""/></td>
						<td align="" nowrap><input name="date_create['.$i.']" id="date_create['.$i.']" type="text" value="'.$data['date_create'].'" style="width:80%" class="datum icreate"/>'.$s1.'</td>
						<td align="" nowrap><input name="date_period['.$i.']" id="date_period['.$i.']" type="text" value="'.$data['date_period'].'" style="width:80%" class="datum iperiod"/>'.$s2.'</td>
						<td align="center"></td>
					</tr>';

					$i++;
				}
				?>
				</tbody>
			</table>

		</div>
		<hr>
		<div align="right" class="button--pane">
			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
			</div>
			<A href="javascript:void(0)" onclick="Save()" class="orangebtn button" title="Окно редактора не будет закрыто">Сохранить и продолжить</A>&nbsp;
			<A href="javascript:void(0)" onclick="SaveClose()" class="button">Сохранить и закрыть</A>&nbsp;
		</div>
	</FORM>
	<script>

		var iscontinue = 'no';

		$(function () {

			var hh = $('#dialog_container').actual('height') * 0.85;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

			if ($(window).width() > 1200) {
				$('#dialog').css({'width': '900px'});
				$('#formtabse').css({'max-height': hh2});
			}
			else {
				$('#dialog').css('width', '80%');
				$('#formtabse').css('max-height', hh2);
			}

			$('#dialog').center();

		});

		function Save() {

			iscontinue = 'yes';
			$('#Form').trigger('submit');

		}

		function SaveClose() {

			iscontinue = 'no';
			$('#Form').trigger('submit');

		}

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				var error = '';

				if (iscontinue == 'no') {
					DClose();
				}

				if (typeof configpage === 'function') {
					configpage();
				}

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.message);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}
		});

		$(".datum").each(function () {

			$(this).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

		});

		$('.dcreate').on('click', function () {

			var datum = $(this).closest('td').find('input.datum').val();

			$('.icreate').val(datum);

			//console.log(datum);

		});
		$('.dperiod').on('click', function () {

			var datum = $(this).closest('td').find('input.datum').val();

			$('.iperiod').val(datum);

			//console.log(datum);

		});

	</script>
	<?php
	exit();
}

if ( $action == "editzayavka" ) {

	$id  = (int)$_REQUEST['id'];
	$tip = $_REQUEST['tip'];
	$did = (int)$_REQUEST['did'];

	if ( $id ) {

		$res            = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
		$did            = $res['did'];
		$status         = $res['status'];
		$iduser         = $res['iduser'];
		$sotrudnik      = $res['sotrudnik'];
		$content        = $res['content'];
		$des            = $res['des'];
		$isHight        = $res['isHight'];
		$datum_priority = $res['datum_priority'];
		$cInvoice       = $res['cInvoice'];
		$cDate          = $res['cDate'];
		if ( $cDate == '0000-00-00' )
			$cDate = '';
		$bid        = $res['bid'];
		$conid      = $res['conid'];
		$providerid = $res['providerid'];

		$clid = $db -> getOne( "SELECT clid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );
		if ( $providerid > 0 )
			$cSumma = $db -> getOne( "SELECT summa FROM ".$sqlname."dogprovider WHERE id = '$dogprovider' and identity = '$identity'" );

		$zayavka = json_decode( $des, true );
		if ( $zayavka['zTitle'] )
			$tip = 'cold';

		$ss = "disabled";
	}
	else {
		$iduser         = $iduser1;
		$datum_priority = current_datum();
	}

	if ( $datum_priority == '0000-00-00' )
		$datum_priority = '';

	//$pridExists = $db -> getCol("SELECT id FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz = '$id' and identity = '$identity' ORDER BY prid");
	//print_r($pridExists);
	?>
	<DIV class="zagolovok">Создать/Редактировать Заявку</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editzayavka_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div style="width:99.2%; padding:5px; overflow-y: auto; overflow-x: hidden; max-height: 400px;" id="formtabse">

			<div class="row">

				<div class="column12 grid-2 fs-12 pt10 right-text">Автор заявки:</div>
				<div class="column12 grid-4">
					<select name="iduser" id="iduser" class="required">
						<option value="none">--Выбор--</option>
						<?php
						$result = $db -> getAll( "SELECT * FROM ".$sqlname."user where secrty='yes' and identity = '$identity' ORDER by title ".$userlim );
						foreach ( $result as $data ) {
							?>
							<option <?php if ( $data['iduser'] == $iduser )
								print "selected"; ?> value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
						<?php } ?>
					</select>
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Срочность:</div>
				<div class="column12 grid-4">
					<input type="text" name="datum_priority" id="datum_priority" value="<?= $datum_priority ?>" class="yw80 datum">
					&nbsp;&nbsp;
					<label><input type="checkbox" name="isHight" id="isHight" <?php if ( $isHight == 'yes' )
							print "checked"; ?> value="yes">&nbsp;<span class="red pt10 fs-09">Срочно</span></label>
				</div>

			</div>

			<?php
			//для создания заявки на поиск товара
			if ( $tip != 'cold' ) {

				if ( in_array( $iduser1, $msettings['mcCoordinator'] ) ) {
					?>
					<div class="hidden1">
						<div class="row greenbg-sub pb10">

							<div class="column12 grid-12">
								<div id="divider" class="red" align="center"><b class="red">Поставщик</b></div>
							</div>

							<div class="column12 grid-2 fs-12 pt10 right-text">Поставщик:</div>
							<div class="column12 grid-10">
								<input name="contractor" type="text" id="contractor" style="width: 97%;" value="<?= current_client( $conid ) ?>" placeholder="Начните вводить название. Например: Сэйлзмэн">
								<input type="hidden" name="conid" id="conid" value="<?= $conid ?>">
							</div>

							<div class="column12 grid-2 fs-12 pt10 right-text">Счет №:</div>
							<div class="column12 grid-4">
								<input type="text" name="cInvoice" id="cInvoice" value="<?= $cInvoice ?>">
							</div>

							<div class="column12 grid-2 fs-12 pt10 right-text">Сумма:</div>
							<div class="column12 grid-4">
								<span class="tooltips" tooltip="<blue>Что это?</blue><hr>При выполнении заявки в Бюджет будет добавлен новый расход на указанную сумму. Если заявка привязана к сделке, то также будет добавлен расход к Сделке" tooltip-position="bottom"><input type="text" name="cSumma" id="cSumma" value="<?= num_format( $cSumma ) ?>"><i class="icon-info-circled blue"></i></span>
							</div>

							<div class="column12 grid-2 fs-12 pt10 right-text">Дата счета:</div>
							<div class="column12 grid-4">
								<input type="text" name="cDate" id="cDate" value="<?= $cDate ?>" class="datum">
							</div>

						</div>
					</div>
					<?php
				}
			}
			?>

			<?php
			//для создания заявки на поиск товара
			if ( $tip == 'cold' ) {
				?>
				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="red" align="center"><b class="red">Позиция для поиска</b></div>
					</div>

					<div class="column12 grid-2 fs-12 pt10 right-text">Название:</div>
					<div class="column12 grid-10">
						<input type="text" name="zTitle" id="zTitle" value="<?= $zayavka['zTitle'] ?>" style="width:98.7%">
					</div>

					<div class="column12 grid-2 fs-12 pt10 right-text">Год выпуска:</div>
					<div class="column12 grid-10">
						<input type="text" name="zGod" id="zGod" value="<?= $zayavka['zGod'] ?>" style="width:100px">
					</div>

					<div class="column12 grid-2 fs-12 pt10 right-text">Пробег:</div>
					<div class="column12 grid-10">
						<input type="text" name="zProbeg" id="zProbeg" value="<?= $zayavka['zProbeg'] ?>" style="width:100px">
					</div>

					<div class="column12 grid-2 fs-12 pt10 right-text">Цена от:</div>
					<div class="column12 grid-2">
						<input type="text" name="zPriceStart" id="zPriceStart" value="<?= $zayavka['zPriceStart'] ?>" style="width:150px">
					</div>
					<div class="column12 grid-1 fs-12 pt10 right-text">До:</div>
					<div class="column12 grid-6">
						<input type="text" name="zPriceEnd" id="zPriceEnd" value="<?= $zayavka['zPriceEnd'] ?>" style="width:150px">
						<label><input type="checkbox" name="zNDS" id="zNDS" <?php if ( $zayavka['zNDS'] = 'yes' )
								print "checked"; ?> value="yes">&nbsp;НДС</label>
					</div>

					<div class="column12 grid-2 fs-12 pt10 right-text">Сделка:</div>
					<div class="column12 grid-10">
						<input name="dogovor" type="text" id="dogovor" style="width: 97%;" value="<?= current_dogovor( $did ) ?>" placeholder="Начните вводить название">
						<input name="did" type="hidden" id="did" value="<?= $did ?>">
					</div>

				</div>
			<?php } ?>

			<?php
			//для заявки по прайсу
			if ( $tip != 'cold' ) {
				?>
				<div class="row">

					<div class="column12 grid-2 fs-12 pt10 right-text hidden">Клиент:</div>
					<div class="column12 grid-4 hidden">
						<input name="client" type="text" id="client" style="width: 97%;" value="<?= current_client( $clid ) ?>" placeholder="Начните вводить название. Например: Сэйлзмэн">
						<input name="clid" type="hidden" id="clid" value="<?= $clid ?>">
					</div>

					<?php
					//print "SELECT ".$sqlname."dogovor.did, ".$sqlname."dogovor.title, ".$sqlname."dogovor.clid, ".$sqlname."dogcategory.title as step FROM ".$sqlname."dogovor LEFT JOIN ".$sqlname."dogcategory ON ".$sqlname."dogcategory.idcategory = ".$sqlname."dogovor.idcategory WHERE ".$sqlname."dogovor.did > 0 and ".$sqlname."dogovor.close != 'yes' and ".$sqlname."dogcategory.title >= ".$msettings['mcStepPers']." and ".$sqlname."dogovor.identity = '$identity' GROUP BY ".$sqlname."dogovor.did";
					?>

					<div class="column12 grid-2 fs-12 pt10 right-text">Сделка:</div>
					<div class="column12 grid-10">

						<select name="did" id="did" style="width:80%" onchange="specaLoad2()">
							<option value="">--Выбор--</option>
							<?php
							if ( $did > 0 ) {

								$data = $db -> getRow( "SELECT * FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );
								print '<option value="'.$data['did'].'" selected>'.$data['title'].' - '.$data['kol'].'</option>';

							}
							else {

								//только активные сделки с этапом более mcStepPers
								$q = "SELECT ".$sqlname."dogovor.did, ".$sqlname."dogovor.title, ".$sqlname."dogovor.clid, ".$sqlname."dogcategory.title as step FROM ".$sqlname."dogovor LEFT JOIN ".$sqlname."dogcategory ON ".$sqlname."dogcategory.idcategory = ".$sqlname."dogovor.idcategory WHERE ".$sqlname."dogovor.did > 0 and ".$sqlname."dogovor.close != 'yes' and ".$sqlname."dogcategory.title >= ".$msettings['mcStepPers']." and ".$sqlname."dogovor.identity = '$identity' GROUP BY ".$sqlname."dogovor.did ORDER BY ".$sqlname."dogcategory.title";

								$data = $db -> getAll( $q );
								foreach ( $data as $da ) {

									//только позиции из прайса
									$countSpeca = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."speca WHERE did = '".$da['did']."' and prid > 0 and identity = '$identity'" );

									//количество позиций уже размещенных в заявках
									$countZayavka = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz IN (SELECT id FROM ".$sqlname."modcatalog_zayavka WHERE did = '".$da['did']."' and status NOT IN (2,3) and identity = '$identity') and identity = '$identity'" );

									//количество позиций уже размещенных в заявках
									$countOrder = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE did = '$da[did]' and tip = 'outcome' and identity = '$identity') and identity = '$identity'" );

									//кол-во позиций в резерве под сделку
									$countReserve = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_reserv WHERE did = '$da[did]' and identity = '$identity'" );

									//число позиций, не в заявках и не в ордерах
									$co = $countSpeca - $countOrder - $countZayavka - $countReserve;

									if ( $countSpeca > 0 && $co > 0 )
										print '<option value="'.$da['did'].'">['.$da['step'].'%]: '.$da['title'].' ['.current_client( $da['clid'] ).']['.$co.' ед.]</option>';

								}

							}
							?>
						</select>
						&nbsp;<a href="javascript:void(0)" onclick="specaLoad2()" title="Загрузить снова"><i class="icon-ccw blue"></i></a>&nbsp;
						<span class="tooltips" tooltip="<blue>Что это?</blue><hr>Список сделок с этапом более <?= $msettings['mcStepPers'] ?>%, имеющие не размещенные в заявках позиции" tooltip-position="left" style="width:100%;"><i class="icon-info-circled blue"></i></span>

					</div>

				</div>
				<div class="row">

					<div class="column12 grid-12">
						<div id="divider" class="red" align="center"><b class="red">Продукты</b></div>
					</div>

				</div>
				<div class="row">

					<div class="column12 grid-12">

						<div style="max-height: 400px; overflow-y: auto;">
							<table width="100%" border="0" cellspacing="0" cellpadding="5" id="tbspeca">
								<thead>
								<tr>
									<th align="center">Наименование</th>
									<th width="120" align="center">Кол-во</th>
									<th width="30" align="center"></th>
								</tr>
								</thead>
								<tbody>
								<?php
								$i   = 0;
								$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz = '$id' and identity = '$identity'" );
								foreach ( $res as $data ) {

									$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

									print '
									<tr id="pr_'.$i.'">
										<td><input name="idp[]" id="idp[]" type="hidden" value="'.$data['id'].'"><input name="prid[]" id="prid[]" type="hidden" value="'.$data['prid'].'"><input name="speca_title[]" type="text" id="speca_title[]" value="'.$title.'" style="width:98%" class="requered"/></td>
										<td align="center"><input name="speca_kol[]" id="speca_kol[]" type="text" readonly1 value="'.num_format( $data['kol'] ).'" style="width:70%" class="requered"/></td>
										<td align="center"><i class="icon-cancel-circled red hand delete" title="Удалить"></i></td>
									</tr>
									';
									$i++;

								}
								$co = $i;
								?>
								</tbody>
							</table>
							<input type="hidden" name="spcount" id="spcount" value="<?= $co ?>">
						</div>

						<hr>

						<div class="addProduct pull-aright">
							<a href="javascript:void(0)" onclick="prTRclone2()" class="button">Добавить</a>
						</div>
						<div class="addProductDisabled hidden" id="fakebutton">
							<a href="javascript:void(0)" class="button">Добавить</a>
						</div>

					</div>

				</div>
			<?php } ?>

			<div class="row">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center"><b class="red">Комментарий</b></div>
				</div>

				<div class="column12 grid-12">
					<TEXTAREA name="content" rows="3" id="content" style="width: 99.7%;"><?= $content ?></TEXTAREA>
				</div>

			</div>

			<?php if ( in_array( $iduser1, $msettings['mcCoordinator'] ) ) { ?>

				<div class="row hidden">

					<div class="column12 grid-12">
						<div id="divider" class="red" align="center"><b class="red">Решение</b></div>
					</div>

				</div>
				<div class="row hidden">

					<div class="column12 grid-12">
						<TEXTAREA name="zAnswer" rows="3" id="zAnswer" style="width: 99.7%;"><?= $zayavka['zAnswer'] ?></TEXTAREA>
					</div>

				</div>
				<div class="row hidden">

					<div class="column12 grid-2 fs-12 pt7 right-text">Ответственный:</div>
					<div class="column12 grid-4">
						<select name="zCoordinator" id="zCoordinator" class="required">
							<option value="none">--Выбор--</option>
							<?php
							$res = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user where secrty='yes' and identity = '$identity' ORDER by title ".$userlim );
							foreach ( $res as $data ) {
								?>
								<option <?php if ( $data['iduser'] == $iduser1 or $data['iduser'] == $zayavka['zCoordinator'] )
									print "selected"; ?> value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
							<?php } ?>
						</select>
					</div>

				</div>
				<?php
			}
			elseif ( $id ) {
				?>
				<div class="row hidden">

					<div class="column12 grid-12">
						<?= $zayavka['zAnswer'] ?>
						<input type="hidden" name="zAnswer" id="zAnswer" value="<?= $zayavka['zAnswer'] ?>">
					</div>

					<div class="column12 grid-2 fs-12 pt7 right-text">Ответственный:</div>
					<div class="column12 grid-4">
						<?= current_user( $zayavka['zCoordinator'] ) ?>
						<input type="hidden" name="zCoordinator" id="zCoordinator" value="<?= $zayavka['zCoordinator'] ?>">
					</div>

				</div>
			<?php } ?>

		</div>

		<hr>

		<div align="right" class="button--pane">

			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>

			<span id="fakebutton" class="hidden">
				<A href="javascript:void(0)" class="button" title="Провести не возможно">Сохранить</A>
			</span>

			<span id="submitbutton" class="hidden1">
				<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			</span>

		</div>

	</FORM>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;
		var zdid = parseInt('<?=$did?>');

		if (!isMobile) {

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '900px'});
				$('#formtabse').css({'max-height': hh2});
			}
			else {
				$('#dialog').css('width', '80%');
				$('#formtabse').css('max-height', hh2);
			}

		}
		else{

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - hh3 - 120;
			$('#formtabse').css({'max-height': h2 + 'px','height': h2 + 'px'});
			$(".multiselect").addClass('wp97 h0');

		}

		$(function () {

			//если указана сделка, то установим её
			if (zdid > 0) $('#did').val(zdid).trigger('change');

			//$('#dialog').css('width','800px');
			$('#zPriceStart').setMask({mask: '999 999 999 999', type: 'reverse'});
			$('#zPriceEnd').setMask({mask: '999 999 999 999', type: 'reverse'});

			$(".datum").datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false) return false;

					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

					return true;

				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();

					if ($('.catalog--zboard').is('div')) $('.catalog--zboard').load('/modules/modcatalog/dt.zboard.php').append('<img src=/assets/images/loading.gif>');

					if (typeof configpage === 'function') configpage();

					if (typeof getCatalog === 'function') getCatalog();

					if (isCard === true) settab('7');

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 5000);

				}
			});

			$("#client").autocomplete('/content/helpers/client.helpers.php?action=clientlist', {
				autofill: false,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {
					//return '<div onclick="selItem(\'client\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
					return '<div>' + data[0] + '&nbsp;[<span class="gray2">' + data[2] + '</span>]</div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			});
			$("#client").result(function (value, data) {
				//selItem('client', data[1] );
				dealLoad(data[1]);
			});

			$("#dogovor").autocomplete("/content/helpers/deal.helpers.php?action=doglist&closed=no", {
				autofill: true,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, i, n, value) {
					return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			});
			$("#dogovor").result(function (value, data) {
				$('#did').val(data[1]);
			});

			$("#contractor").autocomplete('/content/helpers/client.helpers.php?action=clientlist&tip=contragent', {
				autofill: false,
				minChars: 2,
				cacheLength: 2,
				maxItemsToShow: 10,
				selectFirst: false,
				multiple: false,
				delay: 10,
				matchSubset: 1,
				formatItem: function (data, j, n, value) {
					return '<div>' + data[0] + '&nbsp;[<span class="gray2">' + data[2] + '</span>]</div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			});
			$("#contractor").result(function (value, data) {
				$('#conid').val(data[1]);
			});


			/*tooltips*/
			$('.tooltips').append("<span></span>");
			$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$(".tooltips").mouseenter(function () {
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});
			/*tooltips*/

			$('#dialog').center();

		});

		function selItem(i, price) {
			$("#pr_" + i + " #speca_price\\[\\]").val(price);
		}

		function addfile() {
			$('#filepole').html('<input name="file" type="file" class="file" id="file" style="width:97%" />');
		}

		function prTRclone2() {

			var i = parseInt($("#spcount").val()) + 1;

			var trhtml = '<tr id="pr_' + i + '"><td><input name="idp[]" id="idp[]" type="hidden" value=""><input name="prid[]" id="prid[]" type="hidden" value="" class="prid"><input name="speca_title[]" type="text" id="speca_title[]" value="" style="width:98%" class="requered" placeholder="Начните вводить наименование"/></td><td align="center"><input name="speca_kol[]" type="text" id="speca_kol[]" value="1,00" style="width:70%"></td><td align="center"><a href="javascript:void(0)" onclick="prTRremove(' + i + ');"><i class="icon-cancel-circled red hand" title="Удалить"></i></a></td></tr>';

			$('#tbspeca').append(trhtml);
			$('#spcount').val(i);

			$("#pr_" + i).find("#speca_title\\[\\]").autocomplete("/modules/modcatalog/autoprice.php",
				{
					autofill: true,
					minChars: 2,
					cacheLength: 2,
					maxItemsToShow: 10,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1,
					formatItem: function (data, j, n, value) {
						return '<div>' + data[0] + '</div>';
					},
					formatResult: function (data) {

						return data[0];

					}
				}
			);
			$("#pr_" + i).find("#speca_title\\[\\]").result(function (value, data) {
				$("#pr_" + i).find('#prid\\[\\]').val(data[2]);
			});

			$('#dialog').center();

		}

		function prTRremove(id) {
			$('#tbspeca #pr_' + id).remove();
			$('#dialog').center();
		}

		function didLoad() {
			var clid = $('#clid option:selected').val();
			var url = '/modules/modcatalog/core.modcatalog.php?action=didlist&t=zayavka&clid=' + clid;

			$.post(url, function (data) {

				$('#did').empty().append(data).removeAttr('disabled').attr('enabled', 'enabled');
				$('#tbspeca tbody').empty();

			});
		}

		function dealLoad(clid) {
			var url = '/modules/modcatalog/core.modcatalog.php?action=deallist&t=zayavka&clid=' + clid;

			$.post(url, function (data) {

				var string = '<option value="">--Выбор--</option>';

				for (var i in data) {

					string = string + '<option value="' + data[i].did + '">' + data[i].title + ' - ' + data[i].summa + '</option>';

				}

				$('#did').empty().append(string).removeAttr('disabled').attr('enabled', 'enabled');
				$('#tbspeca tbody').empty();

			}, 'json');
		}

		function specaLoad() {
			var did = $('#did option:selected').val();
			var count = $('#spcount').val();
			var url = '/modules/modcatalog/core.modcatalog.php?action=specalist&did=' + did + '&count=' + count;

			$.post(url, function (data) {

				$('#tbspeca tbody').empty().append(data);

			}).done(function () {
				$('#dialog').center();
			});
		}

		function specaLoad2() {

			var did = $('#did option:selected').val();
			var count = $('#spcount').val();
			var idz = $('#id').val();
			var url = 'modules/modcatalog/core.modcatalog.php?action=specalist4&idz=' + idz + '&did=' + did + '&count=' + count;
			var cc = 0;
			var trhtml = '';

			$('#tbspeca tbody').empty();

			$.getJSON(url, function (data) {

				for (var i in data) {

					var row = data[i];
					var ss = '';
					var tt = '';

					//console.log(row);

					if (parseInt(row.kol_in) < parseInt(row.kol)) {

						ss = 'border:1px solid red';
						tt = '<div class="smalltxt"><span class="red">Нет достаточного количества на складе.</span> На складе - <b>' + setNumFormat(row.kol_in, ',', ' ').replace('.', ',') + '</b> позиций.</div>';
						cc++;

					}
					else if (parseInt(row.kol_in) >= parseInt(row.kol)) {

						tt = '<div class="smalltxt">На складе - <b>' + setNumFormat(row.kol_in, ',', ' ').replace('.', ',') + '</b> позиций.</div>';

					}

					trhtml += '<tr height="40"><td><input name="idp[]" id="idp[]" type="hidden" value="' + row.idp + '"><input name="prid[]" id=prid[]" type="hidden" value="' + row.prid + '"><input name="speca_title[]" type="text" id="speca_title[]" value="' + row.title + '" style="width:98%" class="requered" placeholder="Начните вводить наименование"/>' + tt + '</td><td align="center" valign="top"><input name="speca_kol[]" type="text"  id="speca_kol[]" value="' + setNumFormat(row.kol, ',', ' ').replace('.', ',') + '" style="width:70%; ' + ss + '"></td><td align="center" valign="top"><div class="pt2 delete"><i class="icon-cancel-circled red hand" title="Удалить"></i></div></td></tr>';

				}

				//console.log(data);

				$('#tbspeca tbody').append(trhtml);

				//if(data.length < 1) $('#tbspeca').append('<div class="p5 red fs-11">Все позиции заказаны, либо находятся в ордерах</div>');

			}).done(function () {

				$('#dialog').center();

			});

			if (cc > 0) {

				$('#fakebutton').removeClass("hidden");
				$('#submitbutton').addClass("hidden");

			}
			else {

				$('#fakebutton').addClass("hidden");
				$('#submitbutton').removeClass("hidden");

			}

			if (did > 0) {

				$('.addProduct').addClass('hidden');
				$('.addProductDisabled').removeClass('hidden');

			}
			else {

				$('.addProduct').removeClass('hidden');
				$('.addProductDisabled').addClass('hidden');

			}

			$('#addPozButton').hide();
		}

		$(document).on('click', '.delete', function () {

			$(this).closest('tr').remove();

			//$('.addProduct').removeClass('hidden');
			//$('.addProductDisabled').addClass('hidden');

		});

	</script>
	<?php

	exit();

}
if ( $action == "editzayavkastatus" ) {

	$id  = (int)$_REQUEST['id'];
	$tip = $_REQUEST['tip'];

	if ( $id > 0 ) {

		$res       = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
		$status    = $res['status'];
		$sotrudnik = $res['sotrudnik'];
		$number    = $res['number'];
		$des       = $res['des'];
		$did       = $res['did'];
		$cInvoice  = $res['cInvoice'];
		$cDate     = $res['cDate'];
		if ( $cDate == '0000-00-00' )
			$cDate = '';
		$cSumma     = num_format( $res['cSumma'] );
		$bid        = $res['bid'];
		$conid      = $res['conid'];
		$providerid = $res['providerid'];

		$cSummaP = 0;


		$clid = $db -> getOne( "SELECT clid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );
		if ( $providerid > 0 )
			$cSummaP = $db -> getOne( "SELECT summa FROM ".$sqlname."dogprovider WHERE id = '$dogprovider' and identity = '$identity'" ) + 0;

		//print "c=".$cSummaP;

		$cSumma = ($cSummaP == 0) ? $cSumma : $cSummaP;

		//print_r($res);

		$astatus = [
			'0' => 'Создана',
			'1' => 'В работе',
			'2' => 'Выполнена'
		];
		$colors  = [
			'0' => 'broun',
			'1' => 'blue',
			'2' => 'green'
		];

		$oldstatus = '<B class="'.strtr( $status, $colors ).'">'.strtr( $status, $astatus ).'</B>';

		if ( $status < 2 )
			$status = $status + 1;

		$zayavka = json_decode( $des, true );

		$usr = implode( ",", $msettings['mcCoordinator'] );

		$zayavka['zTitle'] != '' ? $isCold = "true" : $isCold = "false";

		//print $isCold;
	}
	?>
	<DIV class="zagolovok">Изменение статуса Заявки # <?= $number ?></DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editzayavkastatus_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<INPUT type="hidden" name="did" id="did" value="<?= $did ?>">
		<INPUT type="hidden" name="isCold" id="isCold" value="<?= $isCold ?>">
		<div id="formtabs" style="width:100%; max-height: 80vh; overflow-y: auto; overflow-x: hidden;">

			<div class="row">

				<div class="column12 grid-3 fs-12 pt7 right-text">Исполнитель заявки:</div>
				<div class="column12 grid-9">
					<select name="sotrudnik" id="sotrudnik" class="required">
						<option value="none">--Выбор--</option>
						<?php
						$res = $db -> getAll( "SELECT * FROM ".$sqlname."user where secrty='yes' and iduser IN (".$usr.") and identity = '$identity' ORDER by title ".$userlim );
						foreach ( $res as $data ) {
							?>
							<option <?php if ( $data['iduser'] == $sotrudnik )
								print "selected"; ?> value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
						<?php } ?>
					</select>
				</div>

				<div class="column12 grid-3 fs-12 pt7 right-text">Новый статус:</div>
				<div class="column12 grid-9">
					<select name="status" id="status" class="required" onchange="changeStatusZ()">
						<option value="none">--Выбор--</option>
						<option <?php if ( $status == '0' )
							print "selected"; ?> value="0">Создана
						</option>
						<option <?php if ( $status == '1' )
							print "selected"; ?> value="1">В работе
						</option>
						<option <?php if ( $status == '2' )
							print "selected"; ?> value="2" <?php if ( $id < 1 )
							print "disabled"; ?>>Выполнена
						</option>
						<option disabled="disabled">-------------------</option>
						<option <?php if ( $status == '3' )
							print "selected"; ?> value="3">Отменена
						</option>
					</select>
					&nbsp;Текущий: <?= $oldstatus ?>
				</div>

			</div>
			<div class="row provider">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center"><b class="red">Поставщик</b></div>
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Поставщик:</div>
				<div class="column12 grid-10">
					<input name="contractor" type="text" id="contractor" style="width: 97%;" value="<?= current_client( $conid ) ?>" placeholder="Начните вводить название. Например: Сэйлзмэн">
					<input type="hidden" name="conid" id="conid" value="<?= $conid ?>">
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Счет №:</div>
				<div class="column12 grid-4">
					<input type="text" name="cInvoice" id="cInvoice" value="<?= $cInvoice ?>">
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Сумма:</div>
				<div class="column12 grid-4">
					<span class="tooltips" tooltip="<blue>Что это?</blue><hr>При выполнении заявки в Бюджет будет добавлен новый расход на указанную сумму. Если заявка привязана к сделке, то также будет добавлен расход к Сделке" tooltip-position="bottom"><input type="text" name="cSumma" id="cSumma" value="<?= $cSumma ?>"><i class="icon-info-circled blue"></i></span>
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">Дата счета:</div>
				<div class="column12 grid-4">
					<input type="text" name="cDate" id="cDate" value="<?= $cDate ?>" class="datum">
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center"><b class="red">Решение</b></div>
				</div>

				<div class="column12 grid-12">
					<TEXTAREA name="zAnswer" rows="3" id="zAnswer" style="width: 98.7%;"><?= $zayavka['zAnswer'] ?></TEXTAREA>
				</div>

			</div>

			<div class="infodiv <?php if ( $isCold != 'true' )
				print 'hidden'; ?>">

				<b class="red">Внимание!</b> Для заявок на поиск после сохранения будет открыта форма добавления позиции в каталог

			</div>

		</div>
		<hr>
		<div class="button--pane">

			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>
			<span id="fakebutton" class="hidden">
				<A href="javascript:void(0)" class="button" title="Проведести не возможно">Сохранить</A>
			</span>
					<span id="submitbutton" class="hidden1">
				<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			</span>

		</div>
	</FORM>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.8;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight');

		if (!isMobile) {

			if ($(window).width() > 990)
				$('#dialog').css({'width': '700px'});
			else
				$('#dialog').css('width', '90vw');

			$('#formtabs').css({'max-height': hh2 + 'px'});
			$(".connected-list").css('height', "160px");
			$(".multiselect").multiselect({sortable: true, searchable: true});

		}
		else{

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 120;
			$('#formtabs').css({'max-height': h2 + 'px','height': h2 + 'px'});
			$(".multiselect").addClass('wp97 h0');

		}

		$(function () {

			//$('#dialog').css('width', '700px');

			var status = $('#status option:selected').val();

			$(".datum").datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

			$("#contractor").autocomplete('/content/helpers/client.helpers.php?action=clientlist&tip=contragent',
				{
					autofill: false,
					minChars: 2,
					cacheLength: 2,
					maxItemsToShow: 10,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1,
					formatItem: function (data, j, n, value) {
						return '<div>' + data[0] + '&nbsp;[<span class="gray2">' + data[2] + '</span>]</div>';
					},
					formatResult: function (data) {
						return data[0];
					}
				}
			);
			$("#contractor").result(function (value, data) {
				$('#conid').val(data[1]);
			});

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;

					$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					var status = $('#status option:selected').val();
					var isCold = $('#isCold').val();

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();

					if (typeof configpage === 'function') configpage();

					if (typeof getCatalog === 'function') getCatalog();

					if (isCard === true) settab('7');

					if ($('.catalog--board').is('div')) {
						$('.catalog--board').load('/modules/modcatalog/dt.board.php').append('<img src=/assets/images/loading.gif>');
					}
					if ($('.catalog--zboard').is('div')) {
						$('.catalog--zboard').load('/modules/modcatalog/dt.zboard.php').append('<img src=/assets/images/loading.gif>');
					}

					if (status == '2' && isCold == 'true') {
						doLoad('/modules/modcatalog/form.modcatalog.php?action=edit&idz=<?=$id?>');
					}

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});

			/*tooltips*/
			$('.tooltips').append("<span></span>");
			$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$(".tooltips").mouseenter(function () {
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});
			/*tooltips*/

			$('#dialog').center();

		});

		function changeStatusZ() {
			var status = $('#status option:selected').val();
			var isCold = $('#isCold').val();
			if (status == '2' && isCold == 'true') {
				$('.infodiv').removeClass('hidden');
			}
		}
	</script>
	<?php
	exit();
}
if ( $action == "editzayavkacomplete" ) {

	$did  = (int)$_REQUEST['did'];
	$list = [];

	if ( $did > 0 ) {

		$result = Storage::totalSpeka($did, $filter = '');
		//$result = $db -> getAll( "SELECT * FROM ".$sqlname."speca WHERE did = '$did' and prid > 0 and identity = '$identity'" );
		foreach ( $result as $data ) {

			//Зарезервировано под сделку
			$kolRes = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_reserv WHERE prid = '$data[prid]' and did = '$did' and identity = '$identity'" );

			//В ордерах под сделку
			$kolOrder = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE did = '$did' and tip = 'outcome' and identity = '$identity') and prid = '$data[prid]' and identity = '$identity'" );

			//смотрим, сколько уже заказано под эту сделку
			$q      = "
			SELECT 
				SUM(".$sqlname."modcatalog_zayavkapoz.kol) as kol
			FROM ".$sqlname."modcatalog_zayavkapoz 
				LEFT JOIN ".$sqlname."modcatalog_zayavka ON ".$sqlname."modcatalog_zayavka.id = ".$sqlname."modcatalog_zayavkapoz.idz
			WHERE 
				".$sqlname."modcatalog_zayavkapoz.prid = '".$data['prid']."' and 
				".$sqlname."modcatalog_zayavka.did = '$did' and 
				".$sqlname."modcatalog_zayavkapoz.identity = '$identity'
			";
			$kolZak = $db -> getOne( $q );

			$list[] = [
				"prid"     => $data['prid'],
				"title"    => $data['title'],
				"kol"      => $data['kol'],
				"kolZak"   => $kolZak,
				"kolRes"   => $kolRes,
				"kolOrder" => $kolOrder,
				"kolFree"  => ($data['kol'] - $kolOrder)
			];

		}

		?>
		<div class="zagolovok">Данные по <?= $lang['face']['DealName'][2] ?>: "<?= current_dogovor( $did ) ?>"</div>

		<div id="formtabs" style="overflow: auto; max-height: 70vh;" class="bgwhite1">

			<table id="zebraTable" class="bborder top">
				<thead class="sticked--top">
				<tr>
					<TH class="w20 text-center">#</TH>
					<TH class="text-center">Название</TH>
					<TH class="w80 text-center">Кол-во<br><span class="fs-09 gray2">в счете</span></TH>
					<TH class="w100">Кол-во <br><span class="fs-09 gray2">в заявках</span></TH>
					<TH class="w100">Кол-во <br><span class="fs-09 gray2">в резерве</span></TH>
					<TH class="w100">Кол-во <br><span class="fs-09 gray2">в ордерах</span></TH>
					<TH class="w100">Не отгружено</TH>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $list as $i => $li ) {
					?>
					<tr class="th40 ha bgwhite">
						<td><?= ($i+1) ?></td>
						<td class="Bold"><?= $li['title'] ?></td>
						<td class="text-right"><?= num_format( $li['kol'] ) ?></td>
						<td class="text-right"><?= num_format( $li['kolZak'] ) ?></td>
						<td class="text-right"><?= num_format( $li['kolRes'] ) ?></td>
						<td class="text-right"><?= num_format( $li['kolOrder'] ) ?></td>
						<td class="text-right"><?= num_format( $li['kolFree'] ) ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>
			</table>

		</div>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')" class="button"><i class="icon-briefcase"></i>Карточка <?= $lang['face']['DealName'][1] ?></a>

		</div>

		<script>
			$(function () {

				var dwidth = $(document).width();
				var dialogWidth;
				var dialogHeight;

				if (dwidth < 945) {
					dialogWidth = '90%';
				}
				else {
					dialogWidth = '80%';
				}

				$('#dialog').css('width', dialogWidth).center();

			});
		</script>
		<?php

	}

	exit();

}

//перемещение между складами
if ( $action == "movetoskald" ) {

	$list = $_REQUEST['ch'];
	$id   = (int)$_REQUEST['id'];

	?>
	<DIV class="zagolovok">Перемещение м/у складами</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="movetoskald_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabse">

			<div class="row">

				<div class="column12 grid-2 fs-12 pt10 right-text">Со склада:</div>
				<div class="column12 grid-4">
					<select name="skladfrom" id="skladfrom" class="required" onchange="getSklad()" style="width: 97%;">
						<OPTION value="">--Выбор--</OPTION>
						<?php
						$result = $db -> getAll( "SELECT id, name_shot FROM ".$sqlname."mycomps WHERE identity = '$identity'" );
						foreach ( $result as $data ) {

							print '<optgroup label="'.$data['name_shot'].'" data-mcid="'.$data['id'].'">';

							$res = $db -> getAll( "SELECT id, title FROM ".$sqlname."modcatalog_sklad WHERE mcid = '".$data['id']."' and identity = '$identity'" );
							foreach ( $res as $da ) {

								if ( $da['id'] == $skladfrom )
									$ss = "selected";
								else $ss = '';

								if ( $da['isDefault'] == $sklad )
									$cc = 'data-def="isDefault"';
								else $cc = '';

								print '<option value="'.$da['id'].'" '.$ss.' '.$cc.'>'.$da['title'].'</option>';

							}

							print '</optgroup>';

						}
						?>
					</select>
				</div>

				<div class="column12 grid-2 fs-12 pt10 right-text">На склад:</div>
				<div class="column12 grid-4">
					<select name="skladto" id="skladto" class="required" style="width: 97%;">
						<OPTION value="">--Выбор--</OPTION>
						<?php
						$result = $db -> getAll( "SELECT id, name_shot FROM ".$sqlname."mycomps WHERE identity = '$identity'" );
						foreach ( $result as $data ) {

							print '<optgroup label="'.$data['name_shot'].'" data-mcid="'.$data['id'].'">';

							$res = $db -> getAll( "SELECT id, title FROM ".$sqlname."modcatalog_sklad WHERE mcid = '".$data['id']."' and identity = '$identity'" );
							foreach ( $res as $da ) {

								if ( $da['id'] == $skladto )
									$ss = "selected";
								else $ss = '';

								if ( $da['isDefault'] == $sklad )
									$cc = 'data-def="isDefault"';
								else $cc = '';

								print '<option value="'.$da['id'].'" '.$ss.' '.$cc.'>'.$da['title'].'</option>';

							}

							print '</optgroup>';

						}
						?>
					</select>
				</div>

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center"><b class="red">Позиции</b></div>
				</div>

			</div>
			<div class="row" style="width:99.2%; padding:5px; overflow-y: auto; overflow-x: hidden; max-height: 400px;">

				<table width="100%" border="0" cellspacing="1" cellpadding="5" id="bborder">
					<thead>
					<tr>
						<th align="center">Продукт</th>
						<th width="150" align="left">Серийный номер</th>
						<th width="80" align="center">Кол-во</th>
						<th width="30" align="center"></th>
					</tr>
					</thead>
					<tbody>
					<?php
					//для нового перемещения
					if ( !empty( $list ) ) {

						foreach ( $list as $k => $idp ) {

							$poz = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE id = '".$idp."' and identity = '$identity'" );

							$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$poz['prid']."' and identity = '$identity'" );

							$sklad = $db -> getOne( "SELECT title FROM ".$sqlname."modcatalog_sklad WHERE id = '".$poz['sklad']."' and identity = '$identity'" );

							if ( $msettings['mcSkladPoz'] == "yes" )
								$ss = 'disabled';
							else $ss = '';

							print '
							<tr class="ha" data-sklad="'.$poz['sklad'].'">
								<td>
									<input name="idp[]" id="idp[]" type="hidden" value="'.$idp.'">
									<input name="prid[]" id="prid[]" type="hidden" value="'.$poz['prid'].'">
									<div class="fs-10">'.$title.'</div>
									<div class="em gray2 mt5">'.$sklad.'</div>
								</td>
								<td>'.$poz['serial'].'</td>
								<td><input name="kol[]" id="kol[]" type="text" value="'.num_format( $poz['kol'] ).'" style="width:70%;" class="requered" '.$ss.'/></td>
								<td><div class="delete hand"><i class="icon-cancel-circled red" title="Удалить"></i></div></td>
							</tr>
							';

						}

					}
					//для редактирования перемещения
					//на перспективу, не используется, т.к. сразу проводим
					else {
						$result = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_moveskladpoz WHERE idm = '$id' and identity = '$identity'" );
						foreach ( $result as $data ) {

							$poz = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE sklad = '$sklad' and prid = '".$data['prid']."' and identity = '$identity'" );

							$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$poz['prid']."' and identity = '$identity'" );

							print '
			<tr>
				<td><input name="idp[]" id="idp[]" type="hidden" value="'.$data['id'].'"></td>
				<td>'.$title.'</td>
				<td><input name="kol[]" id="kol[]" type="text" value="'.num_format( $data['kol'] ).'" style="width:70%;" class="requered"/></td>
				<td><div class="delete"><i class="icon-cancel-circled red" title="Удалить"></i></div></td>
			</tr>
			';

						}
					}
					?>
					</tbody>
				</table>

			</div>

		</div>

		<hr>

		<div align="right" class="button--pane">

			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>
			<span id="submitbutton">
				<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			</span>

		</div>
	</FORM>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		if ($(window).width() > 990) {

			$('#dialog').css({'width': '800px'});
			$('#formtabse').css({'max-height': hh2});
		}
		else {
			$('#dialog').css('width', '80%');
			$('#formtabse').css('max-height', hh2);
		}

		$(function () {

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var em = checkRequired();

					if (!em)
						return false;

					$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					$('tbody').find('tr.hidden').remove();

					return true;

				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();

					if ($display === 'desktop') {
						$('.catalog--zboard').load('/modules/modcatalog/dt.zboard.php').append('<img src=/assets/images/loading.gif>');
					}

					if (typeof configpage === 'function') {
						configpage();
					}

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 5000);

				}

			});

			/*tooltips*/
			$('.tooltips').append("<span></span>");
			$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$(".tooltips").mouseenter(function () {
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});
			/*tooltips*/

			$('#dialog').center();

		});

		function getSklad() {

			var sklad = $('#skladfrom option:selected').val();

			$('#dialog').find('tbody tr').not('[data-sklad="' + sklad + '"]').addClass('hidden');
			$('#dialog').find('[data-sklad="' + sklad + '"]').removeClass('hidden');

			$('#skladto option[value="' + sklad + '"]').attr("disabled", "disabled");
			$('#skladto option').not('[value="' + sklad + '"]').removeAttr("disabled");

		}

		$(document).on('click', '.delete', function () {
			$(this).closest('tr').remove();
		});

	</script>
	<?php
	exit();
}
if ( $action == "movetoskaldview" ) {

	$id = (int)$_REQUEST['id'];

	$res       = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_skladmove WHERE id = '$id' and identity = '$identity'" );
	$iduser    = $res['iduser'];
	$datum     = $res['datum'];
	$skladfrom = $res['skladfrom'];
	$skladto   = $res['skladto'];

	if ( $msettings['mcSkladPoz'] == "yes" )
		$ss = 'hidden';
	else $ss = '';
	?>
	<DIV class="zagolovok">Просмотр Перемещения</DIV>
	<div id="formtabse">

		<div class="row fs-12">

			<div class="column12 grid-2 right-text">Дата:</div>
			<div class="column12 grid-4"><?= get_sdate( $datum ) ?></div>

			<div class="column12 grid-2 right-text">Со склада:</div>
			<div class="column12 grid-4"><?= $skladlist[ $skladfrom ] ?></div>

			<div class="column12 grid-2 right-text">Сотрудник:</div>
			<div class="column12 grid-4"><?= current_user( $iduser ) ?></div>

			<div class="column12 grid-2 right-text">На склад:</div>
			<div class="column12 grid-4"><?= $skladlist[ $skladto ] ?></div>

			<div class="column12 grid-12">
				<div id="divider" class="red"><b class="red">Позиции</b></div>
			</div>

		</div>
		<div class="row" style="width:99.2%; padding:5px; overflow-y: auto; overflow-x: hidden; max-height: 400px;">

			<table id="bborder">
				<thead>
				<tr>
					<th class="text-center">Продукт</th>
					<th class="w150 text-left <?= $ss ?>">Серийный номер</th>
					<th class="w80 text-center">Кол-во</th>
					<th class="w30 text-center"></th>
				</tr>
				</thead>
				<tbody>
				<?php
				$res = $db -> getALL( "SELECT * FROM ".$sqlname."modcatalog_skladmovepoz WHERE idm = '$id' and identity = '$identity'" );
				foreach ( $res as $da ) {

					$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$da['prid']."' and identity = '$identity'" );

					print '
					<tr class="ha th40">
						<td><div>'.$title.'</div></td>
						<td class="'.$ss.'">'.$da['serial'].'</td>
						<td class="text-right">'.num_format( $da['kol'] + 0 ).'</td>
						<td></td>
					</tr>
					';

				}
				?>
				</tbody>
			</table>

		</div>

	</div>

	<hr>

	<div class="text-right button--pane">

		<div id="cancelbutton">
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>
		</div>

	</div>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		if ($(window).width() > 990) {

			$('#dialog').css({'width': '800px'});
			$('#formtabse').css({'max-height': hh2});
		}
		else {
			$('#dialog').css('width', '80%');
			$('#formtabse').css('max-height', hh2);
		}

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php
	exit();
}

//групповое редактирование позиций на складе. Задел на перспективу
if ( $action == "editskladpozgroup" ) {

}

//персональное редактирование позиций на складе для поштучного учета
if ( $action == "editskladpozone" ) {

	$id = (int)$_REQUEST['id'];

	$data = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE id = '".$id."' and identity = '$identity'" );

	$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Редактирование позиции</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editskladpozone_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabse">

			<div class="row">

				<div class="column12 grid-12 fs-12 pt10 infodiv"><?= $title ?></div>

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center"><b class="red">Данные</b></div>
				</div>

				<div class="column12 grid-4 fs-12 pt10 right-text">Стоимость:</div>
				<div class="column12 grid-8">
					<input type="text" name="summa" id="summa" value="<?= num_format( $data['summa'] ) ?>" class="wp90">
				</div>

				<div class="column12 grid-4 fs-12 pt10 right-text">Серийный номер:</div>
				<div class="column12 grid-8">
					<input type="text" name="serial" id="serial" value="<?= $data['serial'] ?>" class="wp90">
				</div>

				<div class="column12 grid-4 fs-12 pt10 right-text">Дата поступления:</div>
				<div class="column12 grid-8">
					<input type="text" name="date_in" id="date_in" value="<?= $data['date_in'] ?>" class="wp50 datum">
				</div>

				<div class="column12 grid-4 fs-12 pt10 right-text">Дата выбытия:</div>
				<div class="column12 grid-8">
					<input type="text" name="date_out" id="date_out" value="<?= $data['date_out'] ?>" class="wp50 datum">
				</div>

				<hr>

				<div class="column12 grid-4 fs-12 pt10 right-text">Дата производства:</div>
				<div class="column12 grid-8">
					<input type="text" name="date_create" id="date_create" value="<?= $data['date_create'] ?>" class="wp50 datum">
				</div>

				<div class="column12 grid-4 fs-12 pt10 right-text">Дата поверки:</div>
				<div class="column12 grid-8">
					<input type="text" name="date_period" id="date_period" value="<?= $data['date_period'] ?>" class="wp50 datum">
				</div>

				<hr>

				<div class="column12 grid-4 fs-12 pt10 right-text">Сделка:</div>
				<div class="column12 grid-8">
					<input type="text" name="dogovor" id="dogovor" value="<?= current_dogovor( $data['did'] ) ?>" class="wp90">
					<INPUT type="hidden" name="did" id="did" value="<?= $data['did'] ?>">
				</div>

			</div>

		</div>

		<hr>

		<div align="right" class="button--pane">

			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>
			<span id="submitbutton">
			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			</span>

		</div>
	</FORM>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		$('#dialog').css({'width': '600px'});

		if ($(window).width() > 990) {

			//$('#dialog').css({'width':'800px'});
			$('#formtabse').css({'max-height': hh2});
		}
		else {
			//$('#dialog').css('width','80%');
			$('#formtabse').css('max-height', hh2);
		}

		$(function () {

			$(".datum").datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

			$("#dogovor").autocomplete("/content/helpers/deal.helpers.php?action=doglist&closed=no",
				{
					autofill: true,
					minChars: 2,
					cacheLength: 2,
					maxItemsToShow: 10,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1,
					formatItem: function (data, i, n, value) {
						return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
					},
					formatResult: function (data) {
						return data[0];
					}
				}
			);
			$("#dogovor").result(function (value, data) {
				$('#did').val(data[1]);
			});

			/*tooltips*/
			$('.tooltips').append("<span></span>");
			$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$(".tooltips").mouseenter(function () {
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});
			/*tooltips*/

			$('#dialog').center();

		});

		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных...</div>');
				return true;


			},
			success: function (data) {

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();

				if ($('.catalog--zboard').is('div')) {
					$('.catalog--zboard').load('/modules/modcatalog/dt.zboard.php').append('<img src=/assets/images/loading.gif>');
				}

				try {
					configpage();
				} catch (err) {
				}

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 5000);
			}
		});
	</script>
	<?php
	exit();
}

//персональное редактирование кол-ва позиций на складе для валового учета
if ( $action == "editskladpoz" ) {

	$id = (int)$_REQUEST['id'];

	$data = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE id = '".$id."' and identity = '$identity'" );

	$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Редактирование позиции</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editskladpoz_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">

		<div id="formtabse">

			<div class="row">

				<div class="column12 grid-12 fs-12 pt10 infodiv"><?= $title ?></div>

				<div class="column12 grid-12">
					<div id="divider" class="red" align="center"><b class="red">Данные</b></div>
				</div>

				<div class="column12 grid-4 fs-12 pt10 right-text">Кол-во:</div>
				<div class="column12 grid-8">
					<input type="text" name="kol" id="kol" value="<?= num_format( $data['kol'] ) ?>" class="wp90">
				</div>

			</div>

		</div>

		<hr>

		<div align="right" class="button--pane">

			<div id="cancelbutton">
				<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
			</div>
			<span id="submitbutton">
				<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			</span>

		</div>
	</FORM>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		$('#dialog').css({'width': '600px'});

		if ($(window).width() > 990) {

			//$('#dialog').css({'width':'800px'});
			$('#formtabse').css({'max-height': hh2});
		}
		else {
			//$('#dialog').css('width','80%');
			$('#formtabse').css('max-height', hh2);
		}

		$(function () {

			$(".datum").datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '2014:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

			$("#dogovor").autocomplete("/content/helpers/deal.helpers.php?action=doglist&closed=no",
				{
					autofill: true,
					minChars: 2,
					cacheLength: 2,
					maxItemsToShow: 10,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1,
					formatItem: function (data, i, n, value) {
						return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
					},
					formatResult: function (data) {
						return data[0];
					}
				}
			);
			$("#dogovor").result(function (value, data) {
				$('#did').val(data[1]);
			});

			/*tooltips*/
			$('.tooltips').append("<span></span>");
			$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
			$(".tooltips").mouseenter(function () {
				$(this).find('span').empty().append($(this).attr('tooltip'));
			});
			/*tooltips*/

			$('#dialog').center();

		});

		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				$('#dialog_container').css('display', 'none');
				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();

				if ($('.catalog--zboard').is('div')) {
					$('.catalog--zboard').load('/modules/modcatalog/dt.zboard.php').append('<img src=images/loading.gif>');
				}

				try {
					configpage();
				} catch (err) {
				}

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 5000);
			}
		});
	</script>
	<?php
	exit();
}

if ( $action == "viewzayavka" ) {

	$id = (int)$_REQUEST['id'];

	$res            = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
	$did            = $res['did'];
	$iduser         = $res['iduser'];
	$sotrudnik      = $res['sotrudnik'];
	$datum          = $res['datum'];
	$des            = $res['des'];
	$content        = $res['content'];
	$isHight        = $res['isHight'];
	$datum_start    = $res['datum_start'];
	$datum_priority = $res['datum_priority'];
	$cInvoice       = $res['cInvoice'];
	$cDate          = $res['cDate'];
	$cSumma         = num_format( $res['cSumma'] );
	$bid            = $res['bid'];
	$conid          = $res['conid'];
	$number         = $res['number'];
	$status         = $res['status'];

	//print_r($res);

	$clid = $db -> getOne( "SELECT clid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );

	$zayavka = json_decode( $des, true );
	if ( $zayavka['zTitle'] )
		$tip = 'cold';

	if ( $tip != 'cold' )
		$ds = 'colspan="3"';
	?>
	<DIV class="zagolovok">Просмотр заявки # <?= $number ?></DIV>

	<div id="formtabse" style="max-height: 80vh; overflow-y: auto; overflow-x: hidden;" class="pad5">

		<div class="row">

			<div class="column12 grid-3 fs-12 gray2 right-text">Дата создания:</div>
			<div class="column12 grid-9 fs-12">

				<b class="blue"><?= get_sfdate( $datum ) ?></b>&nbsp;&nbsp;
				<?php if ( $isHight == 'yes' ) { ?> <b class="red">Срочно</b><?php } ?>
				<?php if ( $datum_start != '0000-00-00 00:00:00' ) { ?>
					<div class="pull-aright">В работе с: <b class="red"><?= get_sfdate( $datum_start ) ?></b></div>
				<?php } ?>

			</div>

		</div>
		<?php if ( $datum_priority != NULL && $datum_priority != '0000-00-00' ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Срочность:</div>
				<div class="column12 grid-9 fs-12">

					<?= format_date_rus( $datum_priority ) ?>

				</div>

			</div>
		<?php } ?>
		<?php if ( $tip == 'cold' ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Название:</div>
				<div class="column12 grid-9 fs-12"><?= $zayavka['zTitle'] ?></div>

			</div>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Год выпуска:</div>
				<div class="column12 grid-9 fs-12">
					<b><?= $zayavka['zGod'] ?></b>,&nbsp;Пробег:&nbsp;<b><?= $zayavka['zProbeg'] ?></b></div>

			</div>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Цена от:</div>
				<div class="column12 grid-9 fs-12">
					<b><?= num_format( $zayavka['zPriceStart'] ) ?></b>&nbsp;до:&nbsp;<b><?= num_format( $zayavka['zPriceEnd'] ) ?></b>&nbsp;<?php if ( $zayavka['zNDS'] = 'yes' )
						print '<b class="red">с НДС</b>'; ?>
				</div>

			</div>
		<?php } ?>
		<?php if ( $content != '' ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Комментарий:</div>
				<div class="column12 grid-9 fs-12"><?= $content ?></div>

			</div>
		<?php } ?>
		<?php if ( $conid > 0 ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Поставщик:</div>
				<div class="column12 grid-9 fs-12">
					<a href="javascript:void(0)" onclick="openClient('<?= $conid ?>')"><i class="icon-building blue"></i><b><?= current_client( $conid ) ?></b></a>
				</div>

			</div>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Счет:</div>
				<div class="column12 grid-9 fs-12">
					<b><?= $cSumma ?></b> <?= $valuta ?> по счету № <?= $cInvoice ?> от <?= format_date_rus( $cDate ) ?>
				</div>

			</div>
		<?php } ?>
		<?php if ( $clid > 0 ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Покупатель:</div>
				<div class="column12 grid-9 fs-12">
					<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')"><i class="icon-building broun"></i><b><?= current_client( $clid ) ?></b></a>
				</div>

			</div>
		<?php } ?>
		<?php if ( $did > 0 ) { ?>
			<div class="row">

				<div class="column12 grid-3 fs-12 gray2 right-text">Сделка:</div>
				<div class="column12 grid-9 fs-12">
					<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')"><i class="icon-briefcase broun"></i><b><?= current_dogovor( $did ) ?></b></a>
				</div>

			</div>
		<?php } ?>

		<?php if ( $zayavka['zAnswer'] != '' ) { ?>
			<hr>
			<div class="row greenbg-sub">

				<div class="column12 grid-12 fs-12">
					<div id="divider" align="center"><b>Решение</b></div>
				</div>

				<div class="column12 grid-12 fs-12">

					<?= $zayavka['zAnswer'] ?>.<br>
					<b>Ответственный:</b>
					<i class="icon-user-1 blue"></i><?= current_user( $zayavka['zCoordinator'] ) ?>

				</div>

			</div>
		<?php } ?>

		<?php if ( $tip != 'cold' ) { ?>

			<div id="divider" align="center"><b>Продукты</b></div>
			<table width="100%" border="0" cellspacing="0" cellpadding="4" id="bborder" class="bgwhite">
				<thead>
				<tr class="header_contaner">
					<th align="left">Продукт</th>
					<th width="120" align="center">Кол-во<br>
						<div class="gray2">В заявке</div>
					</th>
					<th width="120" align="center">Кол-во<br>
						<div class="gray2">В ордерах</div>
					</th>
				</tr>
				</thead>
				<?php
				$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz = '$id' and identity = '$identity'" );
				foreach ( $res as $data ) {

					$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

					$countOrder = $db -> getOne( "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE idz = '".$id."' and idz > 0 and identity = '$identity') and prid = '".$data['prid']."' and identity = '$identity'" );

					//print "SELECT SUM(kol) as kol FROM ".$sqlname."modcatalog_aktpoz WHERE ida IN (SELECT id FROM ".$sqlname."modcatalog_akt WHERE idz = '".$id."' and idz > 0 and identity = '$identity') and prid = '".$data['prid']."' and identity = '$identity'";

					print '
					<tr height="40">
						<td><b>'.$title.'</b></td>
						<td align="center">'.num_format( $data['kol'] ).'</td>
						<td align="center">'.$countOrder.'</td>
					</tr>';

				}
				?>
			</table>

		<?php } ?>

	</div>
	<?php
	if ( in_array( $iduser1, $msettings['mcCoordinator'] ) ) {
		?>
		<hr>
		<div class="button--pane">

			<div class="pull-aright">

			<?php if ( in_array( $status, [0, 1] ) ) { ?>
				<a href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?id=<?= $id ?>&action=editzayavkastatus')" class="button">Обработать</a>&nbsp
			<?php } ?>

				<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

			</div>

		</div>
	<?php } ?>
	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		if (!isMobile) {

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '800px'});
				$('#formtabse').css({'max-height': hh2});
			}
			else {
				$('#dialog').css('width', '80%');
				$('#formtabse').css('max-height', hh2);
			}

		}
		else{

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 80;
			$('#formtabse').css({'max-height': h2 + 'px','height': h2 + 'px'});
			$(".multiselect").addClass('wp97 h0');

			$('#dialog').css('width', '100%');

		}

		$(function () {

			$('#dialog').center();

		});
	</script>
	<?php
	exit();
}

//todelete: не понятно что это
if ( $action == "viewzayavkashot" ) {

	$id = (int)$_REQUEST['id'];

	$res            = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_zayavka WHERE id = '$id' and identity = '$identity'" );
	$did            = $res['did'];
	$iduser         = $res['iduser'];
	$sotrudnik      = $res['sotrudnik'];
	$datum          = $res['datum'];
	$des            = $res['des'];
	$content        = $res['content'];
	$isHight        = $res['isHight'];
	$datum_start    = $res['datum_start'];
	$datum_priority = $res['datum_priority'];

	$clid = $db -> getOne( "SELECT clid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'" );

	$zayavka = json_decode( (string)$des, true );
	if ( $zayavka['zTitle'] )
		$tip = 'cold';

	if ( $tip != 'cold' )
		$ds = 'colspan="3"';
	?>
	<TABLE id="bborder">
		<tr height="25">
			<td width="150" align="right">Дата создания:</td>
			<td colspan="3">
				<b class="blue"><?= get_sfdate( $datum ) ?></b>&nbsp;&nbsp;<?php if ( $isHight = 'yes' ) { ?>
					<b class="red">Срочно</b><?php } ?>
				<?php if ( $datum_start != '0000-00-00 00:00:00' ) { ?>
					<div class="pull-aright">В работе с: <b class="red"><?= get_sfdate( $datum_start ) ?></b></div>
				<?php } ?>
			</td>
		</tr>
		<tr height="30">
			<td width="100" align="right">Срочность:</td>
			<td colspan="3"><b><?= format_date_rus( $datum_priority ) ?></b></td>
		</tr>
		<?php if ( $tip == 'cold' ) { ?>
			<tr height="30">
				<td width="100" align="right">Название:</td>
				<td colspan="3"><b><?= $zayavka['zTitle'] ?></b></td>
			</tr>
			<tr height="30">
				<td width="100" align="right">Год выпуска:</td>
				<td colspan="3"><b><?= $zayavka['zGod'] ?></b>,&nbsp;Пробег:&nbsp;<b><?= $zayavka['zProbeg'] ?></b></td>
			</tr>
			<tr height="30">
				<td width="100" align="right">Цена от:</td>
				<td colspan="3">
					<b><?= num_format( $zayavka['zPriceStart'] ) ?></b>&nbsp;до:&nbsp;<b><?= num_format( $zayavka['zPriceEnd'] ) ?></b>&nbsp;<?php if ( $zayavka['zNDS'] = 'yes' )
						print '<b class="red">с НДС</b>'; ?>
				</td>
			</tr>
		<?php } ?>
		<tr height="30">
			<td width="100" align="right" valign="top">Комментарий:</td>
			<td colspan="3"><?= $content ?></td>
		</tr>
		<?php if ( $clid > 0 ) { ?>
			<tr height="25">
				<td align="right">Покупатель:</td>
				<td>
					<a href="javascript:void(0)" onclick="openClient('<?= $clid ?>')"><i class="icon-commerical-building broun"></i><b><?= current_client( $clid ) ?></b></a>
				</td>
			</tr>
		<?php } ?>
		<?php if ( $did > 0 ) { ?>
			<tr height="25">
				<td align="right">Сделка:</td>
				<td>
					<a href="javascript:void(0)" onclick="openDogovor('<?= $did ?>')"><i class="icon-briefcase broun"></i><b><?= current_dogovor( $did ) ?></b></a>
				</td>
			</tr>
		<?php } ?>
		<?php if ( $tip != 'cold' ) { ?>
			<tr>
				<td colspan="2">
					<hr>
					<div style="max-height: 300px; width:99.2%; padding:5px; overflow-y: auto;">
						<table width="100%" border="0" cellspacing="0" cellpadding="4" id="bborder">
							<thead>
							<tr class="header_contaner">
								<th align="left">Продукт</th>
								<th width="80" align="center">Кол-во</th>
							</tr>
							</thead>
							<?php

							$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavkapoz WHERE idz = '$id' and identity = '$identity'" );
							foreach ( $res as $data ) {

								$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

								print '
<tr height="30">
	<td><b>'.$title.'</b></td>
	<td align="center">'.num_format( $data['kol'] ).'</td>
</tr>';
							}

							?>
						</table>
					</div>
				</td>
			</tr>
		<?php } ?>
	</TABLE>
	<?php
	exit();
}

if ( $action == "viewzayavkapoz" ) {

	$prid = (int)$_REQUEST['prid'];

	$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '$prid' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Просмотр заявок с позицией</DIV>
	<TABLE>
		<tr height="25">
			<td width="100">Название:</td>
			<td><b><?= $title ?></b></td>
		</tr>
	</TABLE>
	<hr>
	<div style="max-height: 300px; width:99.2%; padding:5px; overflow-y: auto;">
		<table id="bborder">
			<thead>
			<tr>
				<th width="60" align="left">Заявка</th>
				<th align="left">Сделка</th>
				<th width="80" align="center">Кол-во</th>
			</tr>
			</thead>
			<?php
			$ids = $db -> getCol( "SELECT id FROM ".$sqlname."modcatalog_zayavka where status NOT IN (2, 3) and identity = '$identity'" );

			if ( !empty( $ids ) )
				$ids = "and idz IN (".implode( ",", $ids ).")";

			$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_zayavkapoz WHERE prid = '$prid' ".$ids." and identity = '$identity'" );
			foreach ( $res as $data ) {

				$di = $db -> getRow( "SELECT did, number FROM ".$sqlname."modcatalog_zayavka WHERE id = '".$data['idz']."' and identity = '$identity'" );

				print '
<tr height="40" class="ha">
	<td align="right"><b>'.$di['number'].'</b></td>
	<td><b><a href="javascript:void(0)" onclick="openDogovor(\''.$di['did'].'\')"><i class="icon-briefcase broun"></i>'.current_dogovor( $di['did'] ).'</a></b></td>
	<td align="center">'.num_format( $data['kol'] ).'</td>
</tr>';

			}
			?>
		</table>
	</div>
	<script>
		$('#dialog').css('width', '700px');

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php
	exit();
}
if ( $action == "viewzrezerv" ) {

	$prid  = (int)$_REQUEST['prid'];
	$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '$prid' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Просмотр резерва</DIV>
	<div class="infodiv Bold"><?= $title ?></div>
	<div style="max-height: 60vh; overflow-y: auto;">
		<table class="bgwhite">
			<thead class="sticked--top">
			<tr class="header_contaner">
				<th class="text-left">Сделка</th>
				<th class="w80">Кол-во</th>
				<th class="w150">Ответственный</th>
			</tr>
			</thead>
			<?php
			$res = $db -> getAll( "SELECT * FROM ".$sqlname."modcatalog_reserv WHERE prid = '$prid' and identity = '$identity'" );
			foreach ( $res as $data ) {

				print '
				<tr class="th40 ha">
					<td>
						<b><a href="javascript:void(0)" onclick="openDogovor(\''.$data['did'].'\')"><i class="icon-briefcase broun"></i>'.current_dogovor( $data['did'] ).'</a></b>
					</td>
					<td class="text-center">'.num_format( $data['kol'] ).'</td>
					<td class="text-right"><i class="icon-user-1 blue"></i>'.current_user( get_userid( 'did', $data['did'] ) ).'</td>
				</tr>
				';

			}
			?>
		</table>
	</div>
	<script>

		$('#dialog').css('width', '700px');

		$(function () {

			$('#dialog').center();

		});

	</script>
	<?php
	exit();
}

if ( $action == "viewskladpozone" ) {

	$id = (int)$_REQUEST['id'];

	$data = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_skladpoz WHERE id = '".$id."' and identity = '$identity'" );

	$title = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '".$data['prid']."' and identity = '$identity'" );

	?>
	<DIV class="zagolovok">Просмотр позиции</DIV>

	<div id="formtabse">

		<div class="row">

			<div class="column12 grid-12 fs-12 infodiv"><?= $title ?></div>

			<hr>

			<div class="column12 grid-4 fs-12 right-text gray2">Стоимость:</div>
			<div class="column12 grid-8 fs-12"><?= num_format( $data['summa'] ) ?></div>

			<div class="column12 grid-4 fs-12 right-text gray2">Серийный номер:</div>
			<div class="column12 grid-8 fs-12"><?= $data['serial'] ?></div>

			<div class="column12 grid-4 fs-12 right-text gray2">Дата поступления:</div>
			<div class="column12 grid-8 fs-12"><?= get_date( $data['date_in'] ) ?></div>

			<div class="column12 grid-4 fs-12 right-text gray2">Дата выбытия:</div>
			<div class="column12 grid-8 fs-12"><?= get_date( $data['date_out'] ) ?></div>

			<hr>

			<div class="column12 grid-4 fs-12 right-text gray2">Дата производства:</div>
			<div class="column12 grid-8 fs-12"><?= get_date( $data['date_create'] ) ?></div>

			<div class="column12 grid-4 fs-12 right-text gray2">Дата поверки:</div>
			<div class="column12 grid-8 fs-12"><?= get_date( $data['date_period'] ) ?></div>

			<hr>

			<div class="column12 grid-4 fs-12 right-text gray2">Сделка:</div>
			<div class="column12 grid-8 fs-12">
				<a href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')"><i class="icon-briefcase broun"></i><?= current_dogovor( $data['did'] ) ?>
				</a></div>

		</div>

	</div>

	<script>

		var hh = $('#dialog_container').actual('height') * 0.85;
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

		$('#dialog').css({'width': '600px'});

		if ($(window).width() > 990) {

			//$('#dialog').css({'width':'800px'});
			$('#formtabse').css({'max-height': hh2});
		}
		else {
			//$('#dialog').css('width','80%');
			$('#formtabse').css('max-height', hh2);
		}

		$(function () {

			$('#dialog').center();

		});
	</script>
	<?php
	exit();
}

if ( $action == "editoffer" ) {

	$id = (int)$_REQUEST['id'];

	if ( $id ) {

		$res     = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_offer WHERE id = '$id' and identity = '$identity'" );
		$did     = (int)$res['did'];
		$status  = $res['status'];
		$iduser  = $res['iduser'];
		$content = $res['content'];
		$des     = $res['des'];

		$zayavka = json_decode( (string)$des, true );

		if ( $zayavka['zFile'] )
			$curImg = '<span class="blue">Приложено изображение: <b>'.$zayavka['zFile']['file'].'</b></span><hr>';
	}
	else {
		$iduser         = $iduser1;
		$datum_priority = current_datum();
	}

	if ( $datum_priority == '0000-00-00' )
		$datum_priority = '';

	?>
	<DIV class="zagolovok">Создать/изменить предложение</DIV>
	<FORM action="/modules/modcatalog/core.modcatalog.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="editoffer_on">
		<INPUT type="hidden" name="id" id="id" value="<?= $id ?>">
		<div style="width:99.2%; padding:5px; overflow-y: auto;">
			<TABLE>
				<tr height="30">
					<td width="150" align="right"><b>Новый статус:</b></td>
					<td>
						<select name="status" id="status" class="required">
							<option value="none">--Выбор--</option>
							<option <?php if ( $status == '0' )
								print "selected"; ?> value="0">Актуально
							</option>
							<option <?php if ( $status == '1' )
								print "selected"; ?> value="1">Закрыто
							</option>
						</select>
					</td>
				</tr>
				<tr height="30">
					<td width="100" align="right"><b>Ответственный:</b></td>
					<td>
						<select name="iduser" id="iduser" class="required">
							<option value="none">--Выбор--</option>
							<?php
							$res = $db -> getAll( "SELECT iduser, title FROM ".$sqlname."user where secrty='yes' and identity = '$identity' ORDER by title ".$userlim );
							foreach ( $res as $data ) {
								?>
								<option <?php if ( $data['iduser'] == $iduser )
									print "selected"; ?> value="<?= $data['iduser'] ?>"><?= $data['title'] ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<tr height="30">
					<td width="100" align="right"><b>Название:</b></td>
					<td width="260" colspan="3">
						<input type="text" name="zTitle" id="zTitle" value="<?= $zayavka['zTitle'] ?>" style="width:98.7%">
					</td>
				</tr>
				<?php if ( $msettings['mcOfferName1'] != '' ) { ?>
					<tr height="30">
						<td width="100" align="right"><b><?= $msettings['mcOfferName1'] ?>:</b></td>
						<td colspan="3">
							<input type="text" name="zGod" id="zGod" value="<?= $zayavka['zGod'] ?>" style="width:200px">
							&nbsp;<b><?= $msettings['mcOfferName2'] ?>:</b>&nbsp;<input type="text" name="zProbeg" id="zProbeg" value="<?= $zayavka['zProbeg'] ?>" style="width:200px">
						</td>
					</tr>
				<?php } ?>
				<tr height="30">
					<td width="100" align="right"><b>Цена:</b></td>
					<td colspan="3">
						<input type="text" name="zPrice" id="zPrice" value="<?= $zayavka['zPrice'] ?>" style="width:150px">
						<label><input type="checkbox" name="zNDS" id="zNDS" <?php if ( $zayavka['zNDS'] = 'yes' )
								print "checked"; ?> value="yes">&nbsp;<b>НДС</b></label>
					</td>
				</tr>
				<TR height="30">
					<TD width="100" align="right" valign="top"><B>Изображение:</B></TD>
					<TD colspan="3">
						<?= $curImg ?><input name="file" type="file" class="file" id="file" style="width:98.7%">
						<div class="infodiv" style="width:96.7%">Допускается наличие 1 изображения. В случае замены старое изображение будет удалено.</div>
					</TD>
				</TR>
				<TR>
					<TD valign="top" colspan="4">
						<hr>
						<div class="paddbott5"><B class="smalltxt">Краткое описание: </B></div>
						<TEXTAREA name="content" id="content" style="width: 98.7%; height:160px"><?= $content ?></TEXTAREA>
					</TD>
				</TR>
			</TABLE>
		</div>
		<hr>
		<div class="button--pane text-right">
			<div id="cancelbutton"><A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A></div>
			<span id="submitbutton" class="hidden1"><A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;</span>
		</div>
	</FORM>
	<script>

		$('#zPrice').setMask({mask: '999 999 999 999', type: 'reverse'});

		$(function () {

			$('#dialog').css('width', '800px');

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;

					$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true;

				},
				success: function (data) {

					$('#dialog_container').css('display', 'none');
					$('#dialog').css('display', 'none');
					$('#resultdiv').empty();

					try {
						configpage();
					}
					catch (err) {
					}

					if ($('.catalog--oboard').is('div')) {
						$('.catalog--oboard').load('/modules/modcatalog/dt.oboard.php').append('<img src=/assets/images/loading.gif>');
					}

					if ($('.catalog--board').is('div')) {
						$('.catalog--oboard').load('/modules/modcatalog/dt.board.php').append('<img src=/assets/images/loading.gif>');
						$('.catalog--zboard').load('/modules/modcatalog/dt.zboard.php').append('<img src=/assets/images/loading.gif>');
					}

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 5000);
				}
			});

			$('#dialog').center();
		});
	</script>
	<?php
	exit();
}
if ( $action == "viewoffer" ) {

	$id = (int)$_REQUEST['id'];

	$res       = $db -> getRow( "SELECT * FROM ".$sqlname."modcatalog_offer WHERE id = '$id' and identity = '$identity'" );
	$iduser    = $res['iduser'];
	$datum     = $res['datum'];
	$datum_end = $res['datum_end'];
	$des       = $res['des'];
	$content   = $res['content'];
	$users     = $res['users'];
	$prid      = $res['prid'];

	if ( $prid > 0 ) {

		$prtitle = $db -> getOne( "SELECT title FROM ".$sqlname."price WHERE n_id = '$prid' and identity = '$identity'" );

	}

	$users = json_decode( $users, true );

	$zayavka = json_decode( $des, true );

	if ( $zayavka['zFile']['file'] != '' ) {
		$fl = 'style="background: url(\'/content/helpers/get.file.php?file=modcatalog/'.$zayavka['zFile']['file'].'\') top no-repeat; background-size:cover; width:200px; height:140px; float:right; margin-left:10px; border:2px solid #ddd; padding:2px; cursor:zoom-in;" onclick="window.open(\'/content/helpers/get.file.php?file=modcatalog/'.$zayavka['zFile']['file'].'\')" title="Просмотр" class="list"';
	}
	else {
		$fl = 'style="background: url(\'/modules/modcatalog/images/noimage.png\') top no-repeat; background-size:cover;"';
	}
	?>
	<DIV class="zagolovok">Просмотр Предложения</DIV>
	<div style="width: 100%; display:table;">
		<div style="display: inline-block; float:left; width:480px">
			<TABLE width="100%" border="0" cellpadding="2" cellspacing="1" id="bborder">
				<tr height="25">
					<td width="150">
						<div class="fnameCold">Дата создания:</div>
					</td>
					<td colspan="3">
						<div class="fpoleCold"><b class="blue"><?= get_sfdate( $datum ) ?></b>&nbsp;&nbsp;
							<?php if ( $datum_end != '0000-00-00 00:00:00' ) { ?>
								<div class="pull-aright">Закрыто: <b class="red"><?= get_sfdate( $datum_end ) ?></b>
								</div>
							<?php } ?>
						</div>
					</td>
				</tr>
				<tr height="30">
					<td width="100" valign="top">
						<div class="fnameCold">Название:</div>
					</td>
					<td colspan="3" valign="top">
						<div class="fpoleCold Bold"><?= $zayavka['zTitle'] ?></div>
					</td>
				</tr>
				<?php if ( $msettings['mcOfferName1'] != '' ) { ?>
					<tr height="30">
						<td width="100">
							<div class="fnameCold"><?= $msettings['mcOfferName1'] ?>:</div>
						</td>
						<td colspan="3">
							<div class="fpoleCold">
								<b><?= $zayavka['zGod'] ?></b>,&nbsp;<?= $msettings['mcOfferName2'] ?>:&nbsp;<b><?= $zayavka['zProbeg'] ?></b>
							</div>
						</td>
					</tr>
				<?php } ?>
				<tr height="30">
					<td width="100">
						<div class="fnameCold">Цена:</div>
					</td>
					<td colspan="3">
						<div class="fpoleCold">
							<b><?= num_format( $zayavka['zPrice'] ) ?></b>&nbsp;<?php if ( $zayavka['zNDS'] = 'yes' )
								print '<b class="red">с НДС</b>'; ?>
						</div>
					</td>
				</tr>
				<tr height="30">
					<td width="100" valign="top">
						<div class="fnameCold">Комментарий:</div>
					</td>
					<td colspan="3">
						<div class="fpoleCold" style="max-height: 250px; overflow: auto;"><?= $content ?></div>
					</td>
				</tr>
				<?php if ( $prid > 0 ) { ?>
					<tr height="30">
						<td width="100" valign="top">
							<div class="fnameCold">Создана позиция:</div>
						</td>
						<td colspan="3">
							<div class="fpoleCold" style="max-height: 250px;">
								<a href="javascript:void(0)" onclick="doLoad('modcatalog/editor.php?action=view&n_id=<?= $prid ?>');"><i class="icon-archive broun"></i><?= $prtitle ?>
								</a></div>
						</td>
					</tr>
				<?php } ?>
				<tr height="30">
					<td width="100">
						<div class="fnameCold"><b class="blue">Интерес:</b></div>
					</td>
					<td colspan="3">
						<div class="fpoleCold">
							<?php
							foreach ($users as $iValue) {
								$ma[] = '<i class="icon-user-1 blue"></i>&nbsp;'.current_user($iValue);
							}
							print implode( "; ", $ma );
							?>
						</div>
					</td>
				</tr>
			</TABLE>
		</div>
		<div <?= $fl ?>></div>
	</div>
	<?php if ( $prid == 0 ) { ?>
		<div style="display:block; width: 100%;" align="right">
			<span id="orangebutton" style="font-size:0.95em;"><A href="javascript:void(0)" onclick="doLoad('/modules/modcatalog/form.modcatalog.php?ido=<?= $id ?>&action=edit');" class="button" title="В каталог">В каталог</A></span>
		</div>
	<?php } ?>
	<script>

		$('#dialog').css('width', '700px');

		$(function () {

			$('#dialog').center();
		});

	</script>
	<?php
	exit();
}

if ( $action == "mass" ) {

	$id  = (array)$_REQUEST['ch'];
	$sel = implode( ";", $id );
	$kol = count( $id );

	$word = $_REQUEST['word'];

	$idcategory = $_REQUEST['idcat'];

	if ( $idcategory > 0 ) {

		//список подпапок текущей
		$sort = '';
		$sub  = (array)$db -> getCol( "SELECT idcategory FROM ".$sqlname."price_cat WHERE sub='$idcategory' and identity = '$identity' ORDER BY title" );

		$sub  = (!empty( $sub ) ) ? ' or pr_cat IN ('.implode( ',', $sub ).')' : '';
		$sort .= " and (pr_cat='".$idcategory."' ".$sub.")";

	}

	if ( $word != '' ) {
		$sort .= " and ((artikul LIKE '%".$word."%') or (title LIKE '%".$word."%') or (descr LIKE '%".$word."%'))";
	}

	$count = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."price where n_id > 0 ".$sort." and identity = '$identity'" );

	?>
	<div class="zagolovok"><b>Групповое действие</b></div>
	<form action="/modules/modcatalog/core.modcatalog.php" id="Form" name="Form" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $sel ?>"/>
		<input name="idcategory" id="idcategory" type="hidden" value="<?= $idcategory ?>"/>
		<input name="word" id="word" type="hidden" value="<?= $word ?>"/>
		<input name="action" id="action" type="hidden" value="mass_do"/>
		<div id="profile">
			<table id="bborder">
				<tr>
					<td><b>Действие с записями:</b></td>
					<td>
						<select name="doAction" id="doAction" style="width: auto;" onchange="showd()" class="required">
							<option value="">--выбор--</option>
							<!--<option value="pMove">Переместить</option>-->
							<option value="pDele">Удалить</option>
						</select>
					</td>
				</tr>
				<tr class="hidden" id="catt">
					<td><b>Переместить в категорию:</b></td>
					<td>
						<select name="newcat" id="newcat" style="width: 99.7%;">
							<option value="">--выбор--</option>
						</select>
						<div class="infodiv">Позиции прайса будут перемещены в выбранную категорию</div>
					</td>
				</tr>
				<tr>
					<td width="160"><b>Выполнить для записей:</b></td>
					<td>
						<label><input name="isSelect" id="isSelect" value="doSel" type="radio" <?php if ( $kol > 0 )
								print "checked"; ?>>&nbsp;Выбранное (<b class="blue"><?= $kol ?></b>)</label>
						<label><input name="isSelect" id="isSelect" value="doAll" type="radio" <?php if ( $kol == 0 )
								print "checked"; ?>>&nbsp;Со всех страниц (<b class="blue"><?= $count ?></b>)</label>
					</td>
				</tr>
			</table>
		</div>

		<hr>

		<div class="button-pane text-right">
			<a href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<script>

		$('#dialog').css('width', '608px');

		$(function () {

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message');
					var em = checkRequired();

					if (em === false)
						return false;

					$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

					return true;

				},
				success: function (data) {
					//var url = $('#query').html().replace(/&amp;/g,'&');
					//var pp = $('#pc').html().replace(/&amp;/g,'&');
					$('#dialog').css('display', 'none');
					$('#dialog_container').css('display', 'none');
					$('#resultdiv').empty();

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
					configpage();
				}
			});

			$('#dialog').center();
		});

		function showd() {

			var cel = $('#doAction option:selected').val();

			if (cel == 'pMove') $('#catt').removeClass('hidden');
			else $('#catt').addClass('hidden');

			$('#dialog').center();
		}
	<?php
}

if( $action == "cat.list" ){

	$id = (int)$_REQUEST['id'];

	$ss = ( $id == '' ) ? 'fol_it' : 'fol';

	$catalog = Price::getPriceCatalog();
	foreach ( $catalog as $key => $value ) {

		if ( in_array( $value['id'], (array)$msettings['mcPriceCat'] ) || in_array( $value['sub'], (array)$msettings['mcPriceCat'] ) || empty( $msettings['mcPriceCat'] ) ) {

			$folder = ($value['level'] == 0 ? 'icon-folder-open deepblue' : ($value['level'] == 1 ? 'icon-folder-open blue' : 'icon-folder broun'));
			$padding = ($value['level'] == 0 ? 'mt5 Bold' : ($value['level'] == 1 ? 'pl20' : 'pl20 ml15 fs-09'));

			$ss = ( $value[ 'id' ] == $id ) ? 'fol_it' : 'fol';

			print '
			<div class="pt5">
				<a href="javascript:void(0)" class="'.$ss.' block ellipsis hand '.$padding.'" data-id="'.$value['id'].'" data-title="'.$value['title'].'">
					<div class="strelka w5 mr10"></div><i class="'.$folder.'"></i>&nbsp;'.$value['title'].'
				</a>
			</div>
			';

		}

	}

	exit();

}