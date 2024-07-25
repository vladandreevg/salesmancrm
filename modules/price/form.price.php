<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

use Salesman\Price;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname( __DIR__, 2 );

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$id   = (int)$_REQUEST['id'];
$action = $_REQUEST['action'];

$dname  = [];
$result = $db -> query( "SELECT * FROM ".$sqlname."field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
while ($data = $db -> fetch( $result )) {
	$dname[ $data['fld_name'] ] = $data['fld_title'];
	$dvar[ $data['fld_name'] ]  = $data['fld_var'];
	$don[]                      = $data['fld_name'];
}

if ( $action == "edit" ) {

	$price = [];

	if ( $id > 0 ) {

		/*
		$result1  = $db -> getRow( "select * from ".$sqlname."price where n_id = '$id' and identity = '$identity'" );
		$artikul  = $result1["artikul"];
		$title    = clean( $result1["title"] );
		$descr    = $result1["descr"];
		$price_in = $result1["price_in"];
		$price_1  = $result1["price_1"];
		$price_2  = $result1["price_2"];
		$price_3  = $result1["price_3"];
		$price_4  = $result1["price_4"];
		$price_5  = $result1["price_5"];
		$edizm    = $result1["edizm"];
		$folder   = $result1["pr_cat"];
		$nds      = $result1["nds"];
		$archive  = $result1["archive"];
		*/

		$price = Price::info($id)['data'];

		//print_r($price);

	}
	?>
	<DIV class="zagolovok">Редактировать позицию</DIV>

	<FORM action="/modules/price/core.price.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<INPUT type="hidden" name="action" id="action" value="edit">
		<INPUT name="n_id" type="hidden" id="n_id" value="<?= $id ?>">

		<DIV id="formtabs" style="max-height: 80vh; overflow-y:auto !important; overflow-x:hidden">

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">Артикул:</div>
				<div class="flex-string wp80">
					<input type="text" name="artikul" class="wp97" id="artikul" value="<?= $price['artikul'] ?>"/>
				</div>

			</div>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">Название:</div>
				<div class="flex-string wp80">
					<INPUT name="title" type="text" id="title" class="wp97 required" value="<?= $price['title'] ?>">
				</div>

			</div>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">Описание:</div>
				<div class="flex-string wp80">
					<textarea name="descr" id="descr" class="wp97 required"><?= $price['descr'] ?></textarea>
				</div>

			</div>

			<div class="flex-container box--child mt10 viewdiv">

				<div class="flex-string wp20 title">Архивная:</div>
				<div class="flex-string wp80 pt10">
					<label><input name="archive" id="archive" value="yes" type="checkbox" <?php if ( $price['isArchive'] ) print "checked"; ?>>&nbsp;Да</label>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">НДС:</div>
				<div class="flex-string wp80">
					<input type="text" name="nds" id="nds" value="<?= num_format( $price['nds'] ) ?>" class="w160">&nbsp;%
				</div>

			</div>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">Ед. измерения:</div>
				<div class="flex-string wp80">
					<input name="edizm" class="required w160" type="text" id="edizm" value="<?= $price['edizm'] ?>">
				</div>

			</div>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title"><?= $dname['price_in'] ?>:</div>
				<div class="flex-string wp80">
					<input name="price_in" id="price_in" onkeyup="priceCalc()" class="w160 required" type="text" value="<?= num_format( $price['price_in'] ) ?>">&nbsp;<?= $valuta ?>
				</div>

			</div>

			<hr>

			<?php if ( in_array( 'price_1', $don ) ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 title"><?= $dname['price_1'] ?>:</div>
					<div class="flex-string wp80">
						<input name="price_1" type="text" id="price_1" autocomplete="off" value="<?= num_format( $price['price_1'] ) ?>" class="w160">&nbsp;<?= $valuta ?>&nbsp;
						<input name="mnog_1" type="text" id="mnog_1" value="<?= num_format( $dvar['price_1'] ) ?>" onkeyup="priceCalc('mnog1')" title="Наценка по-умолчанию" class="w100">&nbsp;%
					</div>

				</div>
			<?php } ?>

			<?php if ( in_array( 'price_2', $don ) ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 title"><?= $dname['price_2'] ?>:</div>
					<div class="flex-string wp80">
						<input name="price_2" type="text" id="price_2" autocomplete="off" value="<?= num_format( $price['price_2'] ) ?>" class="w160">&nbsp;<?= $valuta ?>&nbsp;
						<input name="mnog_2" type="text" id="mnog_2" value="<?= num_format( $dvar['price_2'] ) ?>" onkeyup="priceCalc('mnog2')" title="Наценка по-умолчанию" class="w100">&nbsp;%
					</div>

				</div>
			<?php } ?>

			<?php if ( in_array( 'price_3', $don ) ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 title"><?= $dname['price_3'] ?>:</div>
					<div class="flex-string wp80">
						<input name="price_3" type="text" id="price_3" autocomplete="off" value="<?= num_format( $price['price_3'] ) ?>" class="w160">&nbsp;<?= $valuta ?>&nbsp;
						<input name="mnog_3" type="text" id="mnog_3" value="<?= num_format( $dvar['price_3'] ) ?>" onkeyup="priceCalc('mnog3')" title="Наценка по-умолчанию" class="w100">&nbsp;%
					</div>

				</div>
			<?php } ?>

			<?php if ( in_array( 'price_4', $don ) ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 title"><?= $dname['price_4'] ?>:</div>
					<div class="flex-string wp80">
						<input name="price_4" type="text" id="price_4" autocomplete="off" value="<?= num_format( $price['price_4'] ) ?>" style="width: 100px"/>&nbsp;<?= $valuta ?>&nbsp;
						<input name="mnog_4" type="text" id="mnog_4" value="<?= num_format( $dvar['price_4'] ) ?>" onkeyup="priceCalc('mnog4')" title="Наценка по-умолчанию" style="width: 60px"/>&nbsp;%
					</div>

				</div>
			<?php } ?>

			<?php if ( in_array( 'price_5', $don ) ) { ?>
				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 title"><?= $dname['price_5'] ?>:</div>
					<div class="flex-string wp80">
						<input name="price_5" type="text" id="price_5" autocomplete="off" value="<?= num_format( $price['price_5'] ) ?>" style="width: 100px"/>&nbsp;<?= $valuta ?>&nbsp;
						<input name="mnog_5" type="text" id="mnog_5" value="<?= num_format( $dvar['price_5'] ) ?>" onkeyup="priceCalc('mnog5')" title="Наценка по-умолчанию" style="width: 40px"/>&nbsp;%
					</div>

				</div>
			<?php } ?>

			<hr>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">Категория:</div>
				<div class="flex-string wp80">
					<select name="idcategory" id="idcategory" class="wp97">
						<option value="">--Выбор--</option>
						<?php
						$catalog = Price::getPriceCatalog();
						foreach ( $catalog as $key => $value ) {

							$s = ( $value['level'] > 0 ) ? str_repeat( '&nbsp;&nbsp;', $value['level'] ).'&rarr;&nbsp;' : '';
							$a = ( $value['id'] == $price['folder'] ) ? "selected" : '';

							print '<option value="'.$value['id'].'" '.$a.'>'.$s.$value['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title">Новая:</div>
				<div class="flex-string wp80">
					<input type="text" name="new_folder" id="new_folder" class="wp97">
				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#priceForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}

if ( $action == "view" ) {

	$price = Price::info($id)['data'];

	?>
	<DIV class="zagolovok">Просмотр позиции</DIV>

	<DIV id="formtabs" style="max-height: 80vh; overflow-y:auto !important; overflow-x:hidden">

		<div class="flex-container box--child viewdiv">

			<div class="flex-string wp100 fs-07 uppercase gray2">Наименование</div>
			<div class="flex-string wp100 fs-12 flh-12 Bold blue"><?= $price['title'] ?></div>

		</div>

		<hr>

		<?php if ( $price['artikul'] != '' ) { ?>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title pt0">Артикул:</div>
				<div class="flex-string wp80 fs-12"><?= $price['artikul'] ?></div>

			</div>
		<?php } ?>

		<?php if ( $price['folder'] > 0 ) { ?>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title pt0">Категория:</div>
				<div class="flex-string wp80 fs-12"><?= $price['category'] ?></div>

			</div>
		<?php } ?>

		<?php if ( !is_null($price['typename']) ) { ?>
			<div class="flex-container box--child mt10">

				<div class="flex-string wp20 title pt0">Тип:</div>
				<div class="flex-string wp80 fs-12"><?= $price['typename'] ?></div>

			</div>
		<?php } ?>

		<div class="flex-container box--child mt10">

			<div class="flex-string wp20 title pt0">Налог:</div>
			<div class="flex-string wp80 fs-12">
				<?= ( $price['nds'] > 0 ) ? "НДС: ".$price['nds']."%" : "НДС не облагается" ?>
			</div>

		</div>

		<hr>

		<div class="flex-container box--child mt10 p10">

			<div class="flex-string wp100 fs-07 uppercase mb10 gray2">Уровни:</div>
			<div class="flex-string wp100 fs-12">

				<div class="flex-container box--child">

					<?php if ( in_array( 'price_in', $don ) ) { ?>
						<div class="flex-string bgbluelight mr5 p10">
							<div class="fs-07 uppercase mb10 gray2"><?= $dname['price_in'] ?>:&nbsp;</div>
							<div class="Bold fs-12 blue"><?= num_format( $price['price_in'] ) ?> <?= $valuta ?></div>
						</div>
					<?php } ?>

					<?php if ( in_array( 'price_1', $don ) ) { ?>
						<div class="flex-string bgbluelight mr5 p10">
							<div class="fs-07 uppercase mb10 gray2"><?= $dname['price_1'] ?>:&nbsp;</div>
							<div class="Bold fs-12 green"><?= num_format( $price['price_1'] ) ?> <?= $valuta ?></div>
						</div>
					<?php } ?>

					<?php if ( in_array( 'price_2', $don ) ) { ?>
						<div class="flex-string bgbluelight mr5 p10">
							<div class="fs-07 uppercase mb10 gray2"><?= $dname['price_2'] ?>:&nbsp;</div>
							<div class="Bold fs-12"><?= num_format( $price['price_2'] ) ?> <?= $valuta ?></div>
						</div>
					<?php } ?>

					<?php if ( in_array( 'price_3', $don ) ) { ?>
						<div class="flex-string bgbluelight mr5 p10">
							<div class="fs-07 uppercase mb10 gray2"><?= $dname['price_3'] ?>:&nbsp;</div>
							<div class="Bold fs-12"><?= num_format( $price['price_3'] ) ?> <?= $valuta ?></div>
						</div>
					<?php } ?>

					<?php if ( in_array( 'price_4', $don ) ) { ?>
						<div class="flex-string bgbluelight mr5 p10">
							<div class="fs-07 uppercase mb10 gray2"><?= $dname['price_4'] ?>:&nbsp;</div>
							<div class="Bold fs-12"><?= num_format( $price['price_4'] ) ?> <?= $valuta ?></div>
						</div>
					<?php } ?>

					<?php if ( in_array( 'price_5', $don ) ) { ?>
						<div class="flex-string bgbluelight mr5 p10">
							<div class="fs-07 uppercase mb10 gray2"><?= $dname['price_5'] ?>:&nbsp;</div>
							<div class="Bold fs-12"><?= num_format( $price['price_5'] ) ?> <?= $valuta ?></div>
						</div>
					<?php } ?>

				</div>

			</div>

		</div>

		<?php if ( $price['descr'] != '' ) { ?>
			<hr>
			<div class="flex-container box--child viewdiv">

				<div class="flex-string wp100 fs-07 uppercase mb10 gray2">Описание:</div>
				<div class="flex-string wp80 fs-11 flh-12">
					<?= nl2br($price['descr']) ?>
				</div>

			</div>
		<?php } ?>

	</DIV>

	<hr>

	<div class="text-right button--pane">

		<A href="javascript:void(0)" onclick="editPrice('<?= $id ?>','edit');" class="button" title="Изменить"><i class="icon-pencil"></i>Изменить</A>
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

	</div>

	<?php
}

if ( $action == "import" ) {
	?>
	<DIV class="zagolovok">Импорт из Excel</DIV>
	<FORM action="/modules/price/core.price.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<INPUT type="hidden" name="action" id="action" value="import.upload">

		<div class="flex-vertical p10">

			<div class="flex-container">
				<div class="flex-string">Из файла</div>
				<div class="flex-string">
					<input name="file" type="file" class="file wp100" id="file">
				</div>
			</div>

		</div>

		<div class="infodiv">
			Поддерживаются форматы CSV, XLS и XLSX. Вы можете загрузить
			<a href="/developer/example/price.xls" class="red"><b>пример</b></a><br>
		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="Next()" class="button next">Далее</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

		</div>

	</FORM>
	<script>

		var sfile = '';

		$(document).on('change', '#file', function () {

			//console.log(this.files);

			sfile = this.value;

			var ext = this.value.split(".");
			var elength = ext.length;
			var carrentExt = ext[elength - 1].toLowerCase();

			if (in_array(carrentExt, ['csv', 'xls', 'xlsx']))
				$('.next').removeClass('graybtn');

			else {

				sfile = '';
				Swal.fire('Только в формате CSV, XLS, XLSX', '', 'warning');
				$('#file').val('');
				$('.next').addClass('graybtn');

			}

		});

		function Next() {

			if (sfile !== '')
				$('#priceForm').trigger('submit');

			else
				Swal.fire('Внимание', 'Вы забыли выбрать файл для загрузки', 'warning');

		}
	</script>
	<?php
}
if ( $action == "import.select" ) {

	$file = $_COOKIE['url_catalog'];
	$url  = $rootpath.'/files/'.$fpath.$file;

	$xdata = parceExcel( $url, 0);
	$data = [];
	$x = 0;
	while ($x < 3){
		$data[] = $xdata[ $x ];
		$x++;
	}

	?>
	<DIV class="zagolovok">Импорт в базу. Шаг 2.</DIV>
	<FORM action="/modules/price/core.price.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<INPUT type="hidden" name="action" id="action" value="import.on">
		<INPUT type="hidden" name="file" id="file" value="<?= $file ?>">

		<table id="zebra">
			<thead>
			<tr class="noDrag">
				<TH width="200" height="35" align="center" class="nodrop">Название поля в БД</TH>
				<TH width="250" height="35" align="center" class="nodrop">Название поля из файла</TH>
				<TH align="center" class="nodrop">Образец из файла</TH>
			</tr>
			</thead>
		</table>

		<DIV class="bgwhite" style="height:60vh; overflow-y:auto">

			<table id="zebra">
				<?php for ( $i = 0, $iMax = count( $data[0] ); $i < $iMax; $i++ ) { ?>
					<tr class="ha">
						<td width="200">
							<select id="field[]" name="field[]" style="width:100%">
								<option value="">--Выбор--</option>
								<optgroup label="Общие">
									<option value="price:n_id" <?php if ( ($data[0][ $i ]) == 'ID' )
										print "selected"; ?>>ID позиции
									</option>
									<option value="price:artikul" <?php if ( ($data[0][ $i ]) == 'Артикул' )
										print "selected"; ?>>Артикул
									</option>
									<option value="category:title" <?php if ( ($data[0][ $i ]) == 'Категория' )
										print "selected"; ?>>Категория
									</option>
									<option value="category:idcat" <?php if ( ($data[0][ $i ]) == 'ID категории' )
										print "selected"; ?>>ID Категории
									</option>
									<option value="price:title" <?php if ( ($data[0][ $i ]) == 'Наименование' )
										print "selected"; ?>>Наименование
									</option>
									<option value="price:edizm" <?php if ( ($data[0][ $i ]) == 'Ед.изм.' )
										print "selected"; ?>>Ед.измерения
									</option>
									<option value="price:descr" <?php if ( ($data[0][ $i ]) == 'Примечание' )
										print "selected"; ?>>Описание краткое
									</option>
								</optgroup>
								<optgroup label="Цены">
									<option value="price:price_in" <?php if ( ($data[0][ $i ]) == $dname['price_in'] )
										print "selected"; ?>><?= $dname['price_in'] ?></option>
									<?php if ( in_array( 'price_1', $don ) ) { ?>
										<option value="price:price_1" <?php if ( ($data[0][ $i ]) == $dname['price_1'] )
											print "selected"; ?>><?= $dname['price_1'] ?></option><?php } ?>
									<?php if ( in_array( 'price_2', $don ) ) { ?>
										<option value="price:price_2" <?php if ( ($data[0][ $i ]) == $dname['price_2'] )
											print "selected"; ?>><?= $dname['price_2'] ?></option><?php } ?>
									<?php if ( in_array( 'price_3', $don ) ) { ?>
										<option value="price:price_3" <?php if ( ($data[0][ $i ]) == $dname['price_3'] )
											print "selected"; ?>><?= $dname['price_3'] ?></option><?php } ?>
									<?php if ( in_array( 'price_4', $don ) ) { ?>
										<option value="price:price_4" <?php if ( ($data[0][ $i ]) == $dname['price_4'] )
											print "selected"; ?>><?= $dname['price_4'] ?></option><?php } ?>
									<?php if ( in_array( 'price_5', $don ) ) { ?>
										<option value="price:price_5" <?php if ( ($data[0][ $i ]) == $dname['price_5'] )
											print "selected"; ?>><?= $dname['price_5'] ?></option><?php } ?>
									<option value="price:nds" <?php if ( ($data[0][ $i ]) == 'НДС' )
										print "selected"; ?>>НДС
									</option>
								</optgroup>
							</select>
						</td>
						<td width="250"><b><?= $data[0][ $i ] ?></b></td>
						<td>
							<div class="ellipsis"><?= ($data[1][ $i ]) ?></div>
						</td>
					</tr>
				<?php } ?>
			</table>

		</DIV>
	</FORM>

	<hr>

	<div class="success text-center">
		Теперь Вам необходимо ассоциировать загруженные данные с БД системы. Подробнее в
		<a href="https://salesman.pro/docs/47" target="_blank">Документации</a>
	</div>

	<hr>

	<DIV class="button--pane text-right">

		<a href="javascript:void(0)" onclick="$('#priceForm').trigger('submit')" class="button">Импортировать</a>&nbsp;
		<a href="javascript:void(0)" onclick="Discard()" class="button">Отмена</a>

	</DIV>
	<?php
}

if ( $action == "mass" ) {

	$word       = $_REQUEST['word'];
	$idcategory = $_REQUEST['idcat'];
	$id         = (array)$_REQUEST['ch'];

	$sel = implode( ";", $id );
	$kol = count( $id );

	$catalog = Price::getPriceCatalog( 0 );

	if ( $idcategory > 0 ) {

		$listcat = [];
		foreach ( $catalog as $key => $value ) {
			$listcat[] = $value['id'];
		}

		$sort .= " and (".$sqlname."price.pr_cat='".$idcategory."' or ".$sqlname."price.pr_cat IN (".implode( ",", $listcat )."))";
	}

	if ( $word != '' )
		$sort .= " and ((artikul LIKE '%".$word."%') or (title LIKE '%".$word."%') or (descr LIKE '%".$word."%'))";

	$count = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."price where n_id > 0 ".$sort." and identity = '$identity'" );
	?>
	<div class="zagolovok"><b>Групповое действие</b></div>

	<FORM action="/modules/price/core.price.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<input name="ids" id="ids" type="hidden" value="<?= $sel ?>"/>
		<input name="idcategory" id="idcategory" type="hidden" value="<?= $idcategory ?>"/>
		<input name="word" id="word" type="hidden" value="<?= $word ?>"/>
		<input name="action" id="action" type="hidden" value="mass"/>

		<div id="profile">

			<table id="bborder">
				<tr>
					<td>
						<div class="fnameForm">Действие с записями:</div>
					</td>
					<td>
						<select name="doAction" id="doAction" style="width: auto;" onchange="showd()">
							<option value="">--выбор--</option>
							<option value="pArchive">В архив</option>
							<option value="pArchiveOut">Из архива</option>
							<option value="pMove">Переместить</option>
							<option value="pDele">Удалить</option>
						</select>
					</td>
				</tr>
				<tr class="hidden" id="catt">
					<td valign="top">
						<div class="fnameForm">Переместить в категорию:</div>
					</td>
					<td>
						<select name="newcat" id="newcat" style="width: 99.7%;">
							<option value="">--выбор--</option>
							<?php
							foreach ( $catalog as $key => $value ) {

								if ( $value['level'] > 0 ) {
									$s = str_repeat( '&nbsp;', $value['level'] ).'&rarr;&nbsp;';
								}
								else $s = '';

								if ( $value['id'] == $idcategory )
									$a = "selected";
								else $a = '';

								print '<option value="'.$value['id'].'" '.$a.'>'.$s.$value['title'].'</option>';

							}
							?>
						</select>
						<div class="infodiv">Позиции прайса будут перемещены в выбранную категорию</div>
					</td>
				</tr>
				<tr>
					<td width="200">
						<div class="fnameForm">Выполнить для записей:</div>
					</td>
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

		<div class="text-right button--pane">
			<a href="javascript:void(0)" onclick="$('#priceForm').trigger('submit')" class="button">Выполнить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<?php
}

if ( $action == "cat.list" ) {
	?>
	<DIV class="zagolovok">Редактор категорий</DIV>

	<DIV id="formtabs" style="max-height: 80vh; overflow-y:auto !important; overflow-x:hidden">

		<?php
		if ( $_REQUEST['sklad'] == 'yes' ) {

			$msettings = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
			$msettings = json_decode( $msettings, true );

			if(count( $msettings['mcPriceCat'] ) > 0) {

				print '<div class="attention">Отображены только категории, указанные в настройках Модуля Каталог-склад</div>';

			}

		}
		?>

		<TABLE id="zebraTable" class="bgwhite">
			<thead class="sticked--top">
			<TR class="th30">
				<th class="text-center"><b>Название категории</b></th>
				<th class="w120 text-center"></th>
			</TR>
			</thead>
			<tbody>
			<?php
			$catalog = Price::getPriceCatalog();
			foreach ( $catalog as $key => $value ) {

				if ( $_REQUEST['sklad'] == 'yes' ) {

					if ( in_array( $value['id'], $msettings['mcPriceCat'] ) || in_array( $value['sub'], $msettings['mcPriceCat'] ) || count( $msettings['mcPriceCat'] ) == 0 ) {

					}
					else {
						continue;
					}

				}

				$subb  = [];
				$sub   = [];
				$ures  = [];//глобальный массив, используемый в функции getPriceCatalog - его надо очищать
				$count = 0;

				$sub = Price::getPriceCatalog( $value['id'] );
				foreach ( $sub as $k => $v ) {
					$subb[] = $v['id'];
				}

				$subb = ( count( $subb ) > 0 ) ? " or ".$sqlname."price.pr_cat IN (".implode( ",", $subb ).")" : '';

				$count = $db -> getOne( "SELECT COUNT(*) as count FROM ".$sqlname."price WHERE (pr_cat='".$value['id']."' $subb) and identity = '$identity'" );

				$s = ( $value['level'] > 0 ) ? str_repeat( '&nbsp;&nbsp;&nbsp;', $value['level'] ).'<div class="strelka w20 ml10 mr10"></div>&nbsp;&nbsp;' : '';

				print '
				<TR class="ha th40">
					<TD>
						'.$s.'
						<div class="inline">
							<div class="fs-11">ID '.$value['id'].': <B>'.$value['title'].'</B>&nbsp;[ <span class="red" title="Число записей">'.$count.'</span> ]</div>
							'.($value['typename'] ? '<div class="fs-09 gray">Тип: '.$value['typename'].'</div>' : '').'
						</div>
					</TD>
					<TD class="w120 text-center fs-09">
						<A href="javascript:void(0)" onClick="editPrice(\''.$value['id'].'\',\'cat.edit\')" class="button bluebtn dotted m0 p3"><i class="icon-pencil inherit" title="Редактировать"></i></A>&nbsp;&nbsp;
						<A href="javascript:void(0)" onClick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)editPrice(\''.$value['id'].'\',\'cat.delete\')" class="button redbtn dotted m0 p3"><i class="icon-cancel-circled inherit" title="Удалить"></i></A>
					</TD>
				</TR>
				';

			}
			?>
			</tbody>
		</TABLE>

	</DIV>

	<hr>

	<div class="button--pane text-right">

		<div class="inline pull-left">

			<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить записи?'); if (cf)deleteEmpty();" class="button redbtn">Удалить пустые</A>

		</div>

		<A href="javascript:void(0)" onclick="editPrice('0','cat.edit')" class="button">Добавить</A>

	</div>
	<?php
}

if ( $action == "cat.edit" ) {

	$id = $_REQUEST['id'];

	if ( $id > 0 ) {

		$info = Price::infoCategory($id)['data'];

	}
	else {

		$info = [];

	}
	?>
	<DIV class="zagolovok">Редактор категорий</DIV>
	<FORM action="/modules/price/core.price.php" method="post" enctype="multipart/form-data" name="priceForm" id="priceForm">
		<input type="hidden" name="action" id="action" value="cat.edit">
		<input type="hidden" name="idcategory" id="idcategory" value="<?= $id ?>">

		<div id="formtabse">

			<div class="flex-vertical p10">

				<div class="flex-container">
					<div class="flex-string">Название</div>
					<div class="flex-string">
						<input name="title" type="text" id="title" class="wp100" value="<?= $info['title'] ?>">
					</div>
				</div>

				<?php
				//if (  !(int)$info['sub'] == 0 ) {
				?>
				<div class="flex-container mt10">
					<div class="flex-string">Главная категория</div>
					<div class="flex-string">
						<select name="sub" id="sub" class="wp100">
							<option value="0" <?php print ( $sub == 0 ) ? "selected" : ""; ?>>--Главная--</option>
							<?php
							$catalog = Price::getPriceCatalog( 0 );
							foreach ( $catalog as $key => $value ) {

								if ( $value['level'] < 3 ) {

									print '<option value="'.$value['id'].'" '.($value['id'] == $info['sub'] ? "selected" : '').'>'.($value['level'] > 0 ? str_repeat( '&nbsp;&nbsp;', $value['level'] ).'&rarr;&nbsp;' : '').$value['title'].'</option>';
								}

							}
							?>
						</select>
					</div>
				</div>
				<?php //} ?>

				<?php
				if (  (int)$info['sub'] == 0 ) {
				?>
				<div class="flex-container mt10">
					<div class="flex-string">Тип</div>
					<div class="flex-string">

						<div class="flex-container box--child">

							<div class="flex-string radio inline viewdiv mb5 mr10 inset bgwhite">
								<label>
									<input type="radio" name="type" id="type" value="0"  <?=($info['type'] == 0 ? 'checked' : '')?>>
									<span class="custom-radio"><i class="icon-radio-check"></i></span>
									<span class="title pl10 text-wrap">Товар</span>
								</label>
							</div>

							<div class="flex-string radio inline viewdiv mb5 mr10 inset bgwhite">
								<label>
									<input type="radio" name="type" id="type" value="1" <?=($info['type'] == 1 ? 'checked' : '')?>>
									<span class="custom-radio"><i class="icon-radio-check"></i></span>
									<span class="title pl10 text-wrap">Услуга</span>
								</label>
							</div>

							<div class="flex-string radio inline viewdiv mb5 mr10 inset bgwhite">
								<label>
									<input type="radio" name="type" id="type" value="2" <?=($info['type'] == 2 ? 'checked' : '')?>>
									<span class="custom-radio"><i class="icon-radio-check"></i></span>
									<span class="title pl10 text-wrap">Материал</span>
								</label>
							</div>

							<div class="flex-string radio inline viewdiv mb5 mr10 inset bgwhite">
								<label>
									<input type="radio" name="type" id="type" value="" <?=($info['type'] == null ? 'checked' : '')?>>
									<span class="custom-radio"><i class="icon-radio-check"></i></span>
									<span class="title pl10 text-wrap">Не задано</span>
								</label>
							</div>

						</div>

					</div>
				</div>
				<?php } ?>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#priceForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="editPrice('','cat.list');" class="button graybtn">Отменить</A>

		</div>
	</FORM>
	<?php
}
?>
<script>

	var action = $('#action').val();
	var fromsklad = '<?=$_REQUEST['sklad']?>';

	if (!isMobile) {

		if(!['cat.edit','import.upload'].includes(action)) {
			$('#dialog').css('width', '800px');
		}

		$(".multiselect").multiselect({sortable: true, searchable: true});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

		if (isMobile) $('table').rtResponsiveTables();

	}

	$('#dialog').center();

	$(function () {

		$('#descr').autoHeight(200);

	});

	$('.close').on('click', function () {

		if (action === 'import.on') Discard();

	});

	$('#priceForm').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			if (['cat.edit','cat.add'].includes(action)) {

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				//$('#dialog').removeClass('dtransition');

			}
			else $('#resultdiv').append('<div id="loader" class="loader"><img src="/assets/images/loading.gif"> Загрузка данных...</div>');

			return true;

		},
		success: function (data) {

			var id = jQuery('#lmenu').find('#idcat').val();

			if (!['cat.edit','cat.add'].includes(action)) {

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$('#resultdiv').empty();

			}

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			if (['cat.add','cat.edit','import.on'].includes(action)) {

				if($display === 'sklad') {

					$('.ifolder').load('/modules/modcatalog/form.modcatalog.php?action=cat.list&id=' + id, function () {
						$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
					});

					/*
					$('.ifolder a').on('click', function () {

						var id = $(this).data('id');
						var title = $(this).data('title');

						$('.ifolder a').removeClass('fol_it');
						$(this).addClass('fol_it');

						$('#idcat').val(id);
						$('#tips').html(title);
						$('#page').val('');

						preconfigpage();

					});
					*/

				}
				else{

					$('.ifolder').load('/modules/price/core.price.php?action=catlist&id=' + id, function () {
						$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
					});

				}

				editPrice(0, 'cat.list');

				//$('#resultdiv').empty().load('/modules/price/form.price.php?action=cat.list').append('<div id="loader" class="loader"><img src="/assets/images/loading.gif"> Загрузка данных...</div>');

				configpage();

				$("#lmenu").find('.nano').nanoScroller();

			}

			if (action === 'import.upload') {

				if (data === 'Файл загружен') editPrice('', 'import.select');

			}
			else configpage();

		},
		complete: function () {
			//$('#dialog').addClass('dtransition');
		}
	});

	function Discard() {

		var url = '/modules/price/core.price.php?action=import.discard';

		$.post(url, function () {

			DClose();

		});

	}

	function showd() {

		var cel = $('#doAction option:selected').val();

		if (cel == 'pMove') $('#catt').removeClass('hidden');
		else $('#catt').addClass('hidden');

	}

	function priceCalc(mnog) {

		var priceIn = parseFloat($('#price_in').val().replace(/ /g, '').replace(/,/g, '.'));

		if ($('#mnog_1').is('input')) var mnog1 = $('#mnog_1').val().replace(/ /g, '').replace(/,/g, '.');
		if ($('#mnog_2').is('input')) var mnog2 = $('#mnog_2').val().replace(/ /g, '').replace(/,/g, '.');
		if ($('#mnog_3').is('input')) var mnog3 = $('#mnog_3').val().replace(/ /g, '').replace(/,/g, '.');
		if ($('#mnog_4').is('input')) var mnog4 = $('#mnog_4').val().replace(/ /g, '').replace(/,/g, '.');
		if ($('#mnog_5').is('input')) var mnog5 = $('#mnog_5').val().replace(/ /g, '').replace(/,/g, '.');


		if (mnog1 !== '') {

			var price1 = priceIn * (1 + parseFloat(mnog1) / 100);

			$('#price_1').val(setNumFormat(price1.toFixed(2), ',', ' ').replace('.', ','));

		}
		if (mnog2 !== '') {

			var price2 = priceIn * (1 + parseFloat(mnog2) / 100);

			$('#price_2').val(setNumFormat(price2.toFixed(2), ',', ' ').replace('.', ','));

		}
		if (mnog3 !== '') {

			var price3 = priceIn * (1 + parseFloat(mnog3) / 100);

			$('#price_3').val(setNumFormat(price3.toFixed(2), ',', ' ').replace('.', ','));

		}
		if (mnog4 !== '') {

			var price4 = priceIn * (1 + parseFloat(mnog4) / 100);

			$('#price_4').val(setNumFormat(price4.toFixed(2), ',', ' ').replace('.', ','));

		}
		if (mnog5 !== '') {

			var price5 = priceIn * (1 + parseFloat(mnog5) / 100);

			$('#price_5').val(setNumFormat(price5.toFixed(2), ',', ' ').replace('.', ','));

		}

	}

	function deleteEmpty(){

		var id = jQuery('#lmenu').find('#idcat').val();

		$.get('/modules/price/core.price.php?action=deleteEmpty', function (data){

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			editPrice(0, 'cat.list');

			if($display === 'sklad') {

				$('.ifolder').load('/modules/modcatalog/form.modcatalog.php?action=cat.list' + id, function () {
					$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
				});

			}
			else{

				$('.ifolder').load('/modules/price/core.price.php?action=catlist&id=' + id, function () {
					$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
				});

			}

		})

	}

</script>