<?php
/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  6.4
 */

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

$thisfile = basename( __FILE__ );

$did    = (int)$_REQUEST['did'];
$action = $_REQUEST['action'];

// перенаправляем на новую форму
if ( $action == 'add' ) {
	$action = 'edit';
}

$dname  = [];
$fields = [];
$result = $db -> query( "SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order" );
while ($data = $db -> fetch( $result )) {

	$dname[ $data['fld_name'] ] = $data['fld_title'];
	$dvar[ $data['fld_name'] ]  = $data['fld_var'];
	$don[]                      = $data['fld_name'];

	if($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {

		$fields[] = [
			"field" => $data['fld_name'],
			"title" => $data['fld_title'],
			"value" => $data['fld_var'],
		];

	}

}

if ( $action == 'edit' ) {

	$spid = (int)$_REQUEST['spid'];
	$prid = 0;

	if ( $spid > 0 ) {

		$clid = getDogData( $did, "clid" );

		$speka = $db -> getRow( "SELECT * FROM {$sqlname}speca WHERE spid = '$spid' and identity = '$identity'" );

		$prid = (int)$speka['prid'];

		if ( $speka['artikul'] == 'undefined' ) {
			$speka['artikul'] = '';
		}

	}
	else {

		$result       = $db -> getRow( "SELECT mcid, clid FROM {$sqlname}dogovor WHERE did = '$did' and identity = '$identity'" );
		$mcid         = (int)$result["mcid"];
		$clid         = (int)$result["clid"];
		$speka['dop'] = 1;

		$ndsi = getNalogScheme( 0, $mcid );

		$speka['nds'] = ($mcid == (int)$GLOBALS['mcDefault']) ? $GLOBALS['ndsDefault'] : 0;

		$spid         = 0;
		$speka['kol'] = 1;

	}

	if ( $prid > 0 ) {

		$price = Price ::info( $prid )['data'];

		// расчет кол-ва на складах
		if ( $GLOBALS['isCatalog'] == 'on' ) {

			$sklad = [];
			$str   = '';
			$total = 0;

			//запросим информацию по наличию на складе
			$res = $db -> query( "SELECT sklad FROM {$sqlname}modcatalog_skladpoz WHERE prid = '$prid' and status = 'in' and identity = '$identity' GROUP BY sklad" );
			while ($da = $db -> fetch( $res )) {

				$skld      = $db -> getOne( "SELECT title FROM {$sqlname}modcatalog_sklad WHERE id = '$da[sklad]' and identity = '$identity'" );
				$kol_res   = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_reserv WHERE prid = '$prid' and sklad = '$da[sklad]' and identity = '$identity'" );
				$kol_sklad = (float)$db -> getOne( "SELECT SUM(kol) as kol FROM {$sqlname}modcatalog_skladpoz WHERE prid = '$prid' and `status` = 'in' and sklad = '$da[sklad]' and identity = '$identity'" );

				$sklad[] = [
					"sklad" => $skld,
					"kol"   => $kol_sklad,
					"rez"   => $kol_res
				];

				$total += $kol_sklad - $kol_res;

				$str .= '
				<div class="pad5 ha list flex-container">
				
					<div class="flex-string wp100 border-box">
					
						<div class="ellipsis">Склад: <b>'.$skld.'</b></div><br>
						<div class="ellipsis em gray2 fs-09">На складе: '.$kol_sklad.'</div><br>
						<div class="ellipsis em gray2 fs-09">в т.ч. в резерве: '.$kol_res.'</div>
						
					</div>
					
				</div>
				';

			}

			if ( $str == '' ) {
				$str = '<div class="gray p10">Отсутствует на складах</div>';
			}

		}

	}

	?>
	<div class="zagolovok">Изменение позиции</div>

	<FORM method="post" action="/content/core/core.speca.php" enctype="multipart/form-data" name="specaForm" id="specaForm">
		<input name="action" id="action" type="hidden" value="edit">
		<input name="spid" id="spid" type="hidden" value="<?= $spid ?>">
		<input name="did" id="did" type="hidden" value="<?= $did ?>">
		<input name="clid" id="clid" type="hidden" value="<?= $clid ?>">
		<input name="prid" id="prid" type="hidden" value="<?= $prid ?>">

		<div id="formtabs" class="wp100 box--child">

			<?php
			$hooks -> do_action( "speka_form_before", $_REQUEST );
			?>

			<div class="flex-container nopad">

				<div class="flex-string wp45 nopad" style="overflow-y: auto; max-height:67vh;">

					<div id="pozition" class="wp95">

						<div class="flex-container border-box mt5">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Артикул:</div>
							<div class="flex-string wp70 pl10">
								<input type="text" name="artikul" id="artikul" value="<?= $speka['artikul'] ?>" class="wp60">&nbsp;<i class="icon-info-circled-1 blue" title="Обновлен: <?= $price['datum'] ?>"></i>
							</div>

						</div>

						<div class="flex-container border-box mt5">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Наименование:</div>
							<div class="flex-string wp70 pl10">
								<textarea name="title" id="title" rows="2" class="required wp99"><?= $speka['title'] ?></textarea>
							</div>

						</div>

						<div class="flex-container border-box mt5">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Тип:</div>
							<div class="flex-string wp70 pl10 mt10">

								<?php
								foreach ( Price::TYPES as $i => $type ) {

									$check = ($i == $speka['tip'] || $price['type'] == $type) ? "checked" : "";

									if ( $i == '2' && $GLOBALS['isCatalog'] != 'on' ) {
										continue;
									}

									?>
									<div class="inline paddright15 margleft5 mb10">

										<div class="radio">
											<label>
												<input name="tip" id="tip" value="<?= $i ?>" type="radio" <?= $check ?>>
												<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
												<span class="title"><?= $type ?></span>
											</label>
										</div>

									</div>
									<?php

									$check = '';

								}
								?>

							</div>

						</div>

						<div class="flex-container border-box mt5">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Количество:</div>
							<div class="flex-string wp70 pl10">
								<input type="number" name="kol" id="kol" step="any" value="<?= $speka['kol'] ?>" min="0" class="w100">
								<?php if ( $GLOBALS['isCatalog'] == 'on' ) { ?>
									<div class="hand tagsmenuToggler inline">
										На складе: <span class="Bold"><?= num_format( $total ) ?></span>
										<div class="tagsmenu left hidden">
											<div class="blok">
												<?= $str ?>
											</div>
										</div>
									</div>
								<?php } ?>
							</div>

						</div>

						<?php if ( $otherSettings['dop'] ) { ?>
							<div class="flex-container border-box mt5">

								<div class="flex-string wp25 gray2 fs-11 text-right pt7"><?= $otherSettings['dopName'] ?>:</div>
								<div class="flex-string wp70 pl10">
									<input type="text" name="dop" id="dop" value="<?= $speka['dop'] ?>" class="w100">
								</div>

							</div>
						<?php } ?>

						<div class="flex-container border-box mt5">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Цена:</div>
							<div class="flex-string wp70 pl10">
								<input type="text" name="price" id="price" class="w160" value="<?= num_format( $speka['price'] ) ?>">&nbsp;за&nbsp;<input name="edizm" type="text" id="edizm" size="5" value="<?= $speka['edizm'] ?>">
							</div>

						</div>

						<?php if ( in_array( 'price_1', (array)$don ) ) { ?>
							<div class="flex-container border-box mt10 mb10 <?= ($prid > 0 ? '' : 'hidden') ?>">

								<div class="flex-string wp25 gray2 fs-11 text-right pt7"><?= $dname['price_1'] ?>:</div>
								<div class="flex-string wp70 pl10 pt71">

									<div class="tags">
										<b class="red"><?= num_format( $price['price_1'] ) ?></b>&nbsp;
										<a href="javascript:void(0)" onclick="$('#price').val('<?= num_format( $price['price_1'] ) ?>')" class="txt" title="Выбрать"><i class="icon-plus broun"></i></a>
									</div>

								</div>

							</div>
						<?php } ?>

						<div class="flex-container border-box mt5 <?= ($prid > 0 ? '' : 'hidden') ?>">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Уровни цен:</div>
							<div class="flex-string wp70 pl10">

								<?php
								foreach ($fields as $field) {

									if($field['field'] !== 'price_1'){

										print '
										<div class="tags">
											<b>'.$field['title'].':</b>&nbsp;<b class="red">'.num_format( $price[$field['field']] ).'</b>&nbsp;
											<a href="javascript:void(0)" class="txt xselect" title="Выбрать" data-value="'.num_format( $price[$field['field']] ).'">&nbsp;<i class="icon-plus broun"></i></a>
										</div>
										';

									}

								}
								?>

							</div>

						</div>

						<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
							<div class="flex-container border-box mt5">

								<div class="flex-string wp25 gray2 fs-11 text-right pt7"><?= $dname['price_in'] ?>:</div>
								<div class="flex-string wp70 pl10">
									<input type="text" name="price_in" id="price_in" class="w160" value="<?= num_format( $speka['price_in'] ) ?>"> <?= ($price['price_in2'] ? '(По прайсу: <b>'.num_format( $price['price_in2'] ).'</b>)' : ''); ?>
								</div>

							</div>
						<?php } ?>

						<div class="flex-container border-box mt5">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">НДС:</div>
							<div class="flex-string wp70 pl10">
								<input type="text" name="nds" id="nds" value="<?= num_format( $speka['nds'] ) ?>" class="w100">&nbsp;%
							</div>

						</div>

						<div class="flex-container border-box mt5 <?= ($price['descr'] != '' ? '' : 'hidden') ?>">

							<div class="flex-string wp25 gray2 fs-11 text-right pt7">Описание:</div>
							<div class="flex-string wp70 pl10">

								<div class="wp97 pt7 mh0 noBold"><?= nl2br( $price['descr'] ) ?></div>

							</div>

						</div>

					</div>

				</div>
				<div class="flex-string wp55 nopad pl5">

					<div class="flex-container">

						<div class="flex-string">

							<select name="idcategory" id="idcategory" class="wp97" onchange="SeachPrice();">
								<option value="">Поиск по категории</option>
								<?php

								$catalog = Price::getPriceCatalog( 0 );
								foreach ( $catalog as $key => $value ) {

									$s = ((int)$value['level'] > 0) ? str_repeat( '&nbsp;&nbsp;&nbsp;', $value['level'] ).'&rarr;&nbsp;' : '';
									$a = ($value['id'] == $price['pr_cat']) ? "selected" : '';

									print '<option value="'.$value['id'].'" '.$a.'>'.$s.$value['title'].'</option>';

								}
								?>
							</select>

						</div>

						<div class="flex-string">
							<input type="text" name="word" id="word" class="wp97" onkeyup="SeachPrice()" autocomplete="off" placeholder="Поиск по названию">
						</div>

						<hr>

						<div class="flex-string">
							<select name="price_list" id="price_list" multiple="multiple" class="wp100" style="height:60vh" onChange="GetNom();"></select>
						</div>

					</div>

				</div>

			</div>

			<hr>

			<div class="flex-container wp100">

				<div class="flex-string wp15 fs-11 text-right pt7">Комментарий:</div>
				<div class="flex-string wp80 pl10">

					<input type="text" id="comment" name="comment" value="<?= $speka['comments'] ?>" class="wp97">
					<div class="gray2 fs-09 em">Будет виден в счете</div>

				</div>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane <?= ($spid > 0 ? 'hidden unvisible' : '') ?>">

			<div id="greenbutton" class="inline hidden-iphone">
				<A href="javascript:void(0)" onclick="addSpeka()" class="button" id="savePos">Добавить и продолжить</A>&nbsp;
			</div>
			<A href="javascript:void(0)" onclick="$('#specaForm').trigger('submit')" class="button" id="savePos">Добавить и завершить</A>&nbsp;

		</div>

		<div class="text-right button--pane <?= ($spid > 0 ? '' : 'hidden unvisible') ?>">

			<A href="javascript:void(0)" onclick="$('#specaForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</form>
	<?php

	$hooks -> do_action( "speka_form_after", $_REQUEST );

}

// вывод позиций поиска
if ( $action == 'get.price' ) {

	$idcategory = (int)$_GET['idcategory'];
	$word       = texttosmall( $_GET['word'] );
	$sort       = '';

	if ( $idcategory > 0 ) {

		//список подпапок текущей
		$listcat = [];
		$catalog = Price::getPriceCatalog( $idcategory );
		foreach ( $catalog as $value ) {
			$listcat[] = (int)$value['id'];
		}

		//print_r($catalog);

		$ss   = !empty( $listcat ) ? " or {$sqlname}price.pr_cat IN (".implode( ",", $listcat ).")" : '';
		$sort .= " and ({$sqlname}price.pr_cat='".$idcategory."'".$ss.")";

	}

	if ( $word != '' ) {

		$regexp = [];
		$words  = explode( "--", $word );
		foreach ( $words as $word ) {
			$regexp[] = '('.str_replace(["(",")"], "", $word).')+';
		}

		$sort .= 'and (LOWER(title) REGEXP "'.implode( '(.*)?', $regexp ).'" or LOWER(artikul) REGEXP "'.implode( '(.*)?', $regexp ).'")';

	}

	$result = $db -> query( "SELECT * FROM {$sqlname}price WHERE n_id > 0 $sort and archive != 'yes' and identity = '$identity' ORDER BY title" );
	while ($data = $db -> fetch( $result )) {

		$art = ($data['artikul'] != '') ? $data['artikul']."  ::  " : '';

		print '<option value="'.$data['n_id'].'">'.$art.$data['title'].'</option>';

	}

	exit();

}

// подгрузка при выборе позиции прайса в спецификации
if ( $action == 'get.poz' ) {

	$prid = (int)$_REQUEST['prid'];
	$clid = (int)$_REQUEST['clid'];

	$priceLevel = getClientData( $clid, "priceLevel" );

	$result     = $db -> getRow( "SELECT * FROM {$sqlname}price WHERE n_id='".$prid."' and identity = '$identity'" );
	$title      = $result["title"];
	$artikul    = $result["artikul"];
	$datum      = $result["datum"];
	$descr      = $result["descr"];
	$price_in   = (float)$result["price_in"];
	$price_1    = (float)$result["price_1"];
	$price_2    = (float)$result["price_2"];
	$price_3    = (float)$result["price_3"];
	$price_4    = (float)$result["price_4"];
	$price_5    = (float)$result["price_5"];
	$edizm      = $result["edizm"];
	$nds        = (float)["nds"];
	$idcategory = (int)$result["pr_cat"];

	$price = Price ::info( $prid )['data'];

	//проверим подключение модуля Склад
	if ( $GLOBALS['isCatalog'] == 'on' ) {

		$sklad = [];
		$str   = '';
		$total = 0;

		//запросим информацию по наличию на складе
		$res = $db -> query( "SELECT sklad FROM {$sqlname}modcatalog_skladpoz WHERE prid='".$prid."' and status = 'in' and identity = '$identity' GROUP BY sklad" );
		while ($da = $db -> fetch( $res )) {

			$skld      = $db -> getOne( "select title from {$sqlname}modcatalog_sklad where id='".$da['sklad']."' and identity = '$identity'" );
			$kol_res   = (float)$db -> getOne( "select SUM(kol) as kol from {$sqlname}modcatalog_reserv where prid='".$prid."' and sklad = '$da[sklad]' and identity = '$identity'" );
			$kol_sklad = (float)$db -> getOne( "select SUM(kol) as kol from {$sqlname}modcatalog_skladpoz where prid='".$prid."' and `status` = 'in' and sklad = '$da[sklad]' and identity = '$identity'" );

			$sklad[] = [
				"sklad" => $skld,
				"kol"   => $kol_sklad,
				"rez"   => $kol_res
			];

			$total += $kol_sklad - $kol_res;

			$str .= '
			<div class="pad5 ha list flex-container">
				<div class="flex-string wp100 border-box">
					<div class="ellipsis">Склад: <b>'.$skld.'</b></div>
					<br><div class="ellipsis em gray2 fs-09">На складе: '.$kol_sklad.'</div>
					<br><div class="ellipsis em gray2 fs-09">в т.ч. в резерве: '.$kol_res.'</div>
				</div>
			</div>
			';

		}

		if ( $str == '' ) {
			$str = '<div class="gray p10">Отсутствует на складах</div>';
		}

	}

	//$price = $price_1;

	if ( $$priceLevel > 0 ) {
		$price['price_1'] = $$priceLevel;
	}

	$dop = 1;

	$nd = ($nds > 0) ? "в т.ч. НДС $nds%" : "без НДС";
	?>

	<div class="flex-container border-box mt5">

		<div class="flex-string wp25 gray2 fs-11 text-right pt7">Артикул:</div>
		<div class="flex-string wp70 pl10">
			<input type="text" name="artikul" id="artikul" value="<?= $price['artikul'] ?>" class="wp60">&nbsp;<i class="icon-info-circled-1 blue" title="Обновлен: <?= $datum ?>"></i>
		</div>

	</div>

	<div class="flex-container border-box mt5">

		<div class="flex-string wp25 gray2 fs-11 text-right pt7">Наименование:</div>
		<div class="flex-string wp70 pl10">
			<textarea name="title" id="title" rows="2" style="width:99%" class="required"><?= $price['title'] ?></textarea>
		</div>

	</div>

	<div class="flex-container border-box mt5">

		<div class="flex-string wp25 gray2 fs-11 text-right pt7">Тип:</div>
		<div class="flex-string wp70 pl10 mt10">

			<?php
			foreach ( Price::TYPES as $i => $type ) {

				$check = ($i == $price['type']) ? "checked" : "";

				if ( $i == '2' && $isCatalog != 'on' ) {
					continue;
				}
				?>
				<div class="inline paddright15 margleft5 mb10">

					<div class="radio">
						<label>
							<input name="tip" id="tip" value="<?= $i ?>" type="radio" <?= $check ?>>
							<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
							<span class="title"><?= $type ?></span>
						</label>
					</div>

				</div>
				<?php
			}
			?>

		</div>
	</div>

	<div class="flex-container border-box mt5">

		<div class="flex-string wp25 gray2 fs-11 text-right pt7">Количество:</div>
		<div class="flex-string wp70 pl10">
			<input type="number" name="kol" id="kol" value="1.00" step="any" min="0" class="w100">
			<?php if ( $isCatalog == 'on' ) { ?>
				<div class="hand tagsmenuToggler inline">
					На складе: <span class="Bold"><?= num_format( $total ) ?></span>
					<div class="tagsmenu left hidden">
						<div class="blok">
							<?= $str ?>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>

	</div>

	<?php if ( $otherSettings['dop'] ) { ?>
		<div class="flex-container border-box mt5">

			<div class="flex-string wp25 gray2 fs-11 text-right pt7"><?= $otherSettings['dopName'] ?>:</div>
			<div class="flex-string wp70 pl10">
				<input type="text" name="dop" id="dop" value="<?= $dop ?>" style="width: 100px">
			</div>

		</div>
	<?php } ?>

	<div class="flex-container border-box mt5">

		<div class="flex-string wp25 gray2 fs-11 text-right pt7">Цена:</div>
		<div class="flex-string wp70 pl10">
			<input type="text" name="price" id="price" class="w160" value="<?= num_format( $price['price_1'] ) ?>">&nbsp;за&nbsp;<input name="edizm" type="text" id="edizm" size="5" value="<?= $price['edizm'] ?>"/>
		</div>

	</div>


	<?php
	if ( $prid > 0 ) {
		?>

		<?php if ( in_array( 'price_1', (array)$don ) ) { ?>
			<div class="flex-container border-box mt10 mb10">

				<div class="flex-string wp25 gray2 fs-11 text-right pt7"><?= $dname['price_1'] ?>:</div>
				<div class="flex-string wp70 pl10 pt71">
					<div class="tags">
						<b class="red"><?= num_format( $price['price_1'] ) ?></b>&nbsp;
						<a href="javascript:void(0)" onclick="$('#price').val('<?= num_format( $price['price_1'] ) ?>')" class="txt" title="Выбрать"><i class="icon-plus broun"></i></a>
					</div>
				</div>

			</div>
		<?php } ?>
		<div class="flex-container border-box mt5">

			<div class="flex-string wp25 gray2 fs-11 text-right pt7">Уровни цен:</div>
			<div class="flex-string wp70 pl10">

				<?php
				foreach ($fields as $field) {

					if($field['field'] !== 'price_1'){

						print '
						<div class="tags">
							<b>'.$field['title'].':</b>&nbsp;<b class="red">'.num_format( $price[$field['field']] ).'</b>&nbsp;
							<a href="javascript:void(0)" class="txt xselect" title="Выбрать" data-value="'.num_format( $price[$field['field']] ).'">&nbsp;<i class="icon-plus broun"></i></a>
						</div>
						';

					}

				}
				?>

			</div>

		</div>

	<?php } ?>

	<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
		<div class="flex-container border-box mt5">

			<div class="flex-string wp25 gray2 fs-11 text-right pt7"><?= $dname['price_in'] ?>:</div>
			<div class="flex-string wp70 pl10">
				<input type="text" name="price_in" id="price_in" class="w160" value="<?= num_format( $price['price_in'] ) ?>"/> <?php if ( $price_in2 )
					print '(По прайсу: <b>'.$price_in2.'</b>)'; ?>
			</div>

		</div>
	<?php } ?>

	<div class="flex-container border-box mt5">

		<div class="flex-string wp25 gray2 fs-11 text-right pt7">НДС:</div>
		<div class="flex-string wp70 pl10">
			<input type="text" name="nds" id="nds" value="<?= num_format( $price['nds'] ) ?>" class="w100">&nbsp;%
		</div>

	</div>

	<?php
	if ( $prid > 0 ) {
		?>
		<div class="flex-container border-box mt5">

			<div class="flex-string wp25 gray2 fs-11 text-right pt7">Описание:</div>
			<div class="flex-string wp70 pl10">
				<div class="wp97 pt7 mh0 noBold infodiv bgwhite text-wrap">
					<?php
					print ($price['descr'] != '' ? nl2br( $price['descr'] ) : '<div class="gray">Отсутствует</div>');
					?>
				</div>
			</div>

		</div>
	<?php } ?>

	<?php
	exit();
}

// импорт прайса
if ( $action == 'import' ) {
	?>
	<div class="zagolovok">Импорт спецификации</div>
	<FORM method="post" action="/content/core/core.speca.php" enctype="multipart/form-data" name="specaForm" id="specaForm">
		<input name="did" id="did" type="hidden" value="<?= $did ?>"/>
		<input name="action" id="action" type="hidden" value="import"/>

		<div id="formtabs" class="flex-container box--child" style="overflow-y: auto; height: 70vh">

			<div class="flex-string wp70 p10" style="height: 69.5vh;">

				<TEXTAREA name="content" rows="10" id="content" class="wp100" style="height: 100%" placeholder="Скопируйте таблицу без заголовков, строк итогов и номеров строк спецификации в Excel (Ctrl+C) и вставьте в это поле (Ctrl+V). Ничего не редактируйте."></TEXTAREA>

			</div>
			<div class="flex-string wp30 p10">

				<div id="uploads">

					<div class="fs-07 uppercase gray Bold">Импортировать из файла</div>
					<input name="file" type="file" class="file wp100" id="file">

				</div>

				<hr>

				<div class="attention">

					<div class="fs-12 broun Bold mb20">Инструкция</div>

					<ul class="p0 pl20">
						<li class="mb5">Для импорта из текста скопируйте таблицу без заголовков, строк итогов и номеров строк спецификации в Excel (Ctrl+C) и вставьте в это поле (Ctrl+V). Ничего не редактируйте. Посмотрите
							<a href="/developer/example/speca.xls" title="Пример спецификации"><b class="red">пример</b></a>
						</li>
						<li class="mb5">Доступен импорт из файлов в формате CSV, XLS (Excel 97 - 2003), XLSX</li>
						<li class="mb5">Не забывайте про <b class="red">Опции</b></li>
					</ul>

				</div>

				<hr>

				<div class="infodiv bgwhite">

					<div class="fs-12 blue Bold mb20">Опции импорта</div>

					<div class="checkbox mt5 fs-09">
						<label>
							<input name="hdr" type="checkbox" id="hdr" value="yes">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Убрать заголовки
						</label>
					</div>

					<div class="checkbox mt5 fs-09">
						<label>
							<input name="stri" type="checkbox" id="stri" value="yes">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Убрать строку итогов
						</label>
					</div>

					<div class="checkbox mt5 fs-09">
						<label>
							<input name="frst" type="checkbox" id="frst" value="yes">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Убрать первый столбец с номерами строк
						</label>
					</div>

				</div>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="Next()" class="button next">Продолжить..</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
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
				//$('.next').addClass('graybtn');

			}

		});

		function Next() {

			if (sfile !== '' || $('#content').val() !== '')
				$('#specaForm').trigger('submit');

			else
				Swal.fire('Внимание', 'Вы забыли выбрать файл для загрузки', 'warning');

		}
	</script>
	<?php
}

// DEPRECATED. Возможность скопировать таблицу со спецификацией
if ( $action == 'export' ) {
	?>
	<div class="zagolovok">Экспорт спецификации</div>
	<div style="height: 350px; max-height: 350px; overflow: auto;">

		<table id="bborder">
			<thead>
			<tr>
				<th class="w20 text-center"><b>№ п.п.</b></th>
				<th class="text-center"><b>Номенклатура</b></th>
				<th width="30" align="center"><b>Ед.изм.</b></th>
				<th width="50" align="center"><b>НДС, %</b></th>
				<th width="60" align="center"><b>Кол-во</b></th>
				<?php if ( $otherSettings['dop'] ) { ?>
					<th width="60" align="center"><b><?= $otherSettings['dopName'] ?></b></th>
				<?php } ?>
				<th width="100" align="center"><b>Цена за ед.,<?= $valuta ?></b></th>
				<th width="100" align="center"><b>Цена итого,<?= $valuta ?></b></th>
				<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
					<th width="100" align="center" class="vhod"><b>Итого закуп,<?= $valuta ?></b></th>
				<?php } ?>
			</tr>
			</thead>
			<?php
			$i        = 1;
			$err      = 0;
			$num      = 0;
			$sum_in   = 0;
			$result_s = $db -> query( "SELECT * FROM {$sqlname}speca WHERE did = '$did' and identity = '$identity' ORDER BY spid" );
			while ($data = $db -> fetch( $result_s )) {

				$delta           = 0;
				$price_in_actual = 0;
				$all             = 0;

				$price_in_actual = $db -> getOne( "SELECT price_in FROM {$sqlname}price WHERE artikul='".$data['artikul']."' and identity = '$identity'" ) + 0;

				$kol_sum = pre_format( $data['kol'] ) * num_format( $data['price'] ) * num_format( $data['dop'] );
				$sum     += $kol_sum;
				$num     += $data['kol'];
				$in_sum  = $data['price_in'] * $data['kol'] * $data['dop'];
				$sum_in  += $in_sum;

				//расчет суммы НДС
				$nds += $kol_sum * (1 - 1 / (1 + $data['nds'] / 100)); //print "НДС=".$nds."; sum=".$sum."<br>";

				if ( $data['artikul'] == '' )
					$data['artikul'] = '???';

				?>
				<tr class="ha th35">
					<td align="center"><?= $i ?></td>
					<td><?= $data['title'] ?></td>
					<td align="center"><?= $data['edizm'] ?></td>
					<td align="center"><?= $data['nds'] ?></td>
					<td align="right"><?= pre_format( $data['kol'] ) ?></td>
					<?php if ( $otherSettings['dop'] ) { ?>
						<td align="right"><?= num_format( $data['dop'] ) ?></td>
					<?php } ?>
					<td align="right"><b><?= num_format( $data['price'] ) ?></b></td>
					<td align="right"><b><?= num_format( $kol_sum ) ?></b></td>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<td align="right" class="vhod"><?= num_format( $in_sum ) ?></td>
					<?php } ?>
				</tr>
				<?php $i++;
				$kol_sum = 0;
			}

			$marga = $sum - $sum_in;

			if ( $sum > 0 ) {
				?>
				<tr style="background:#E9E9E9" height="35px">
					<td align="center">&nbsp;</td>
					<td align="right"><b>ИТОГО:</b></td>
					<td align="center">&nbsp;</td>
					<td align="center">&nbsp;</td>
					<td align="right"><b><?= num_format( $num ) ?></b></td>
					<?php if ( $otherSettings['dop'] ) { ?>
						<td align="right">&nbsp;</td>
					<?php } ?>
					<td align="right">&nbsp;</td>
					<td align="right"><b><?= num_format( $sum ) ?></b></td>
					<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
						<td width="100" align="right" class="vhod"><b><?= num_format( $sum_in ) ?></b></td>
					<?php } ?>
				</tr>
			<?php } ?>
		</table>

	</div>

	<hr>

	<div class="infodiv">
		Скопируйте таблицу для вставки в КП или в Excel.
		<span class="pull-aright"><a href="javascript:void(0)" onclick="$('.vhod').toggleClass('hidden')">Скрыть/Показать &quot;Закуп&quot;</a></span>
	</div>

	<hr>

	<div class="text-right button--pane">

		<a href="javascript:void(0)" onclick="editSpeca('','export','<?= $did ?>')" class="button">Получить CSV-файл</a>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>
	<?php
}

// DEPRECATED. Не понятно для чего это
if ( $action == 'priceSelect' ) {

	$dop    = 1;
	$result = $db -> getRow( "SELECT mcid, clid FROM {$sqlname}dogovor WHERE did='".$did."' and identity = '$identity'" );
	$mcid   = (int)$result["mcid"];
	$clid   = (int)$result["clid"];

	$ndsi = getNalogScheme( 0, $mcid );

	$nds = ($mcid == (int)$mcDefault) ? $ndsDefault : 0;
	?>
	<DIV class="zagolovok">Добавление позиции</DIV>
	<FORM method="post" action="/content/core/core.speca.php" enctype="multipart/form-data" name="specaForm" id="specaForm">
		<input name="action" id="action" type="hidden" value="add">
		<input name="did" id="did" type="hidden" value="<?= $did ?>">
		<input name="clid" id="clid" type="hidden" value="<?= $clid ?>">
		<input name="prid" id="prid" type="hidden" value="">
		<table border="0" cellspacing="2" cellpadding="2" width="99%">
			<tr>
				<td valign="top" width="45%">
					<div style="display:inline-block; width:99%" id="pozition">
						<table width="100%" border="0" cellpadding="4" cellspacing="1" id="bborder">
							<tr>
								<td width="100" height="25" align="right"><strong>Артикул:</strong></td>
								<td>
									<input type="text" name="artikul" id="artikul" value="<?= $artikul ?>"/>&nbsp;<i class="icon-info-circled-1 blue" title="Обновлен: <?= $datum ?>"></i>
								</td>
							</tr>
							<tr>
								<td width="100" height="25" align="right" valign="top"><strong>Наименование:</strong>
								</td>
								<td>
									<textarea name="title" id="title" rows="2" style="width:99%" class="required"><?= $title ?></textarea>
							</tr>
							<tr>
								<td height="25" align="right"><strong>Количество:</strong></td>
								<td>
									<input type="text" name="kol" id="kol" min="1" value="<?= pre_format( $kol ) ?>" style="width: 100px">
								</td>
							</tr>
							<?php
							if ( $otherSettings['dop'] ) {
								?>
								<tr>
									<td height="20" align="right"><strong><?= $otherSettings['dopName'] ?>:</strong>
									</td>
									<td>
										<input type="text" name="dop" id="dop" value="<?= $dop ?>" style="width: 100px"/>
									</td>
								</tr>
							<?php } ?>
							<tr>
								<td height="25" align="right"><strong>Цена:</strong></td>
								<td>
									<input type="text" name="price" id="price"/>&nbsp;за&nbsp;<?php if ( $artikul == '' ) { ?>
										<input name="edizm" type="text" id="edizm" size="5" value="<?= $edizm ?>" /><?php } else print $edizm; ?>
								</td>
							</tr>
							<tr>
								<td height="25" align="right"><strong>НДС:</strong></td>
								<td>
									<input type="text" name="nds" id="nds" value="<?= num_format( $nds ) ?>" style="width: 60px">&nbsp;%
								</td>
							</tr>
							<?php if ( in_array( 'price_in', (array)$don ) ) { ?>
								<?php if ( $show_marga == 'yes' && $otherSettings['marga'] ) { ?>
									<tr>
										<td height="25" align="right"><strong><?= $dname['price_in'] ?>:</strong></td>
										<td><input type="text" name="price_in" id="price_in"/></td>
									</tr>
								<?php } ?>
							<?php } ?>
							<tr>
								<td colspan="2"><b>Описание:</b>
									<div style="max-height:70px; height:70px; width:98%; overflow:auto"></div>
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td valign="top">
					<div style="display:inline-block; width:99%; height:320px" class="formdiv">
						<table width="100%" border="0" cellspacing="1" cellpadding="4">
							<tr>
								<td>
									<i class="icon-folder broun"></i>&nbsp;<select name="idcategory" id="idcategory" style="width: 95%;" onChange="SeachPrice();">
										<option value="">--Выбор--</option>
										<?php

										$catalog = Price::getPriceCatalog( 0 );
										foreach ( $catalog as $value ) {

											if ( $value['level'] > 0 ) {
												$s = str_repeat( '&nbsp;&nbsp;&nbsp;', $value['level'] ).'&rarr;&nbsp;';
											}
											else $s = '';

											if ( $value['id'] == $subid )
												$a = "selected";
											else $a = '';

											print '<option value="'.$value['id'].'" '.$a.'>'.$s.$value['title'].'</option>';

										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<i class="icon-search broun"></i>&nbsp;<input type="text" name="word" id="word" style="width:95%" onkeyup="SeachPrice()"/>
								</td>
							</tr>
							<tr>
								<td>
									<select name="price_list" id="price_list" multiple="multiple" style="width:100%; height:240px" onChange="GetNom();" onClick="GetNom();"></select>
								</td>
							</tr>
						</table>
					</div>
					<div>
						<hr>
						<label for="comment">Комментарий к позиции:</label>
						<input type="text" id="comment" name="comment" value="" style="width: 98%">
					</div>
				</td>
			</tr>
		</table>
		<hr>
		<div class="text-right button--pane">
			<A href="javascript:void(0)" onclick="addPosition()" class="button">Добавить и завершить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</form>
	<script>

		function addPosition() {

		}

	</script>
	<?php

	exit();
}
?>
<script>

	var action = $('#action').val();

	if (!isMobile) {

		var hh = $('#dialog_container').actual('height');
		var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 20;

		$('#dialog').css({'width': '80vw'});
		$('#formtabs').css({'max-height': hh2 + 'px'});

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({
			'max-height': h2 + 'px',
			'height': h2 + 'px',
			'overflow-y': 'auto !important',
			'overflow-x': 'hidden'
		});

	}

	$(function () {

		$('#price_list').load('/content/forms/form.speca.php?action=get.price&idcategory=<?=$price['pr_cat']?>&clid=<?=$clid?>');

		$('#specaForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif>Загрузка данных. Пожалуйста подождите...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');

				var errors = (data.error !== undefined && data.error !== '' && data.error !== null) ? '<br>Note: ' + data.error : '';

				settab('0', false);
				settab('7', false);

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}
		});

		$('#dialog').center();

		ShowModal.fire({
			etype: 'spekaForm',
			action: action
		});

		$(".required").on("change", function () {
			$(".required").css({"background": "white", "color": "black"});
		});

		$('.xselect')
			.off('click')
			.on('click', function () {
				$('#price').val( $(this).data('value') )
			})

	});

	function addSpeka() {

		var url = '/content/core/core.speca.php';
		var str = $('#specaForm').serialize() + '&event=true';
		var id = $('#price_list option:selected').val();
		var clid = $('#clid').val();
		var errors;

		var em = checkRequired();

		if (em === false)
			return false;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif">Загрузка данных. Пожалуйста подождите...</div>');

		$.post(url, str, function (data) {

			if (data.error !== undefined && data.error !== '' && data.error) errors = '<br>Note: ' + data.error;
			else errors = '';

			settab('0', false);
			settab('7', false);

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, "json")
			.done(function () {

				$('#pozition').load('/content/forms/form.speca.php?action=get.poz&prid=0&clid=' + clid);

			});


	}

	function spekaCancel() {

		$.post('/content/core/core.speca.php?action=change.recalculate&did=' + $('#did').val());
		DClose();

	}

	// поиск позиции
	function SeachPrice() {

		var word = $('#word').val().replace(/ /g, '--');
		var url = '/content/forms/form.speca.php?action=get.price&idcategory=' + $('#idcategory').val() + '&word=' + word;

		$('#price_list').load(url);

	}

	// выбор позиции
	function GetNom() {

		var id = $('#price_list option:selected').val();
		var clid = $('#clid').val();
		var url = '/content/forms/form.speca.php?action=get.poz&prid=' + id + '&clid=' + clid;

		$.get(url, function (data) {

			$('#pozition').html(data);

		})
			.done(function () {

				$('.xselect')
					.off('click')
					.on('click', function () {
						$('#price').val( $(this).data('value') )
					})

				$('#dialog').center();

			});

		$('#prid').val(id);

	}

</script>