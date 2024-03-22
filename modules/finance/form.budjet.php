<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */

/* ============================ */

use Salesman\BankStatement;
use Salesman\Budget;
use Salesman\Deal;
use Salesman\Upload;

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/developer/events.php";
include $rootpath."/inc/language/".$language.".php";

global $userRights, $userSettings;

$thisfile = basename( __FILE__ );

$year   = $_REQUEST['year'] !== 'undefined' ? (int)$_REQUEST['year'] : date('Y');
$action = $_REQUEST['action'];
$view   = $_REQUEST['view'];

if ( $action == 'edit' || $action == 'clone' || $action == 'addprovider' ) {

	$id   = (int)$_REQUEST['id'];
	$tip  = $_REQUEST['tip'] ?? NULL;
	$xtip = $_REQUEST['xtip'] ?? NULL;
	$did  = (int)$_REQUEST['did'];

	$rashod = [];
	$providerplus = '';
	$rashod['invoice'] = "бн";
	$dogproviderid = 0;

	if ( $id == 0 || empty( $_REQUEST['id'] ) ) {

		if ( isset( $_REQUEST['cat'] ) ) {
			$rashod['cat'] = (int)$_REQUEST['cat'];
		}
		if ( isset( $_REQUEST['mon'] ) ) {
			$rashod['mon'] = (int)$_REQUEST['mon'];
		}
		if ( isset( $_REQUEST['years'] ) ) {

			$rashod['year'] = (int)$year;

		}

		$rashod['do'] = '';
		$rashod['recal'] = 0;

	}
	else {

		$rashod = Budget::info($id)['budget'];

		//$rashod = $db -> getRow( "SELECT * FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'" );
		$year = (int)$rashod['year'];
		$did  = (int)$rashod['did'];

	}

	if ( $id > 0 && $action != 'addprovider' ) {

		$rashod = $db -> getRow( "SELECT * FROM {$sqlname}budjet WHERE id = '$id' and identity = '$identity'" );

		if ( (int)$rashod['conid'] > 0 ) {
			$contragent = (int)$rashod['conid'];
		}
		if ( (int)$rashod['partid'] > 0 ) {
			$contragent = (int)$rashod['partid'];
		}

		$dogproviderid = (int)$db -> getOne( "SELECT id FROM {$sqlname}dogprovider WHERE bid = '".$id."' and identity = '$identity'" );

	}
	elseif ( $id > 0 && $action == 'addprovider' ) {

		$contragent = $clid = (int)$_REQUEST['clid'];

		if ( !$clid ) {
			$clid = getDogData($did, 'clid');
		}

		$tip           = getClientData( $clid, 'type' );
		$dogproviderid = $id;

		$year = date( 'Y' );

		$rashod['summa'] = (float)$db -> getOne( "SELECT summa FROM {$sqlname}dogprovider WHERE id = '$id' and identity = '$identity'" );

		if ( $tip == 'contractor' ) {

			$provider = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE type='contractor' and clid = '".$clid."' and identity = '$identity'" );

			$rashod['title'] = "Расчеты с Поставщиком по сделке: ".untag( $db -> getOne( "SELECT title FROM {$sqlname}dogovor WHERE did = '".$_REQUEST['did']."' and identity = '$identity'" ) );

			$cel   = 'Поставщику';
			$conid = $clid;

			$rashod['cat'] = (int)$db -> getOne( "SELECT id FROM {$sqlname}budjet_cat WHERE title = 'Поставщики' and identity = '$identity'" );

		}
		if ( $tip == 'partner' ) {

			$provider = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE type='partner' and clid = '".$clid."' and identity = '$identity'" );

			$rashod['title'] = "Расчеты с Партнером по сделке: ".untag( $db -> getOne( "SELECT title FROM {$sqlname}dogovor WHERE did = '".$_REQUEST['did']."' and identity = '$identity'" ) );

			$cel    = 'Партнеру';
			$partid = $clid;

			$rashod['cat'] = $db -> getOne( "SELECT id FROM {$sqlname}budjet_cat WHERE title = 'Партнеры' and identity = '$identity'" );

		}

		$rashod['des'] = 'Оплата '.$cel.' '.$provider;
		$id            = 0;
		$rashod['do']  = '';
		
		unset($rashod['mon'], $rashod['year']);

	}

	// для прямого добавления из карточки
	if( $id == 0 && !empty($tip) ){

		$did  = (int)$_REQUEST['did'];
		$agentid = (int)$_REQUEST['agent'];

		$providerplus = 'yes';

		if ( $tip == 'contractor' ) {

			$provider = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE type='contractor' and clid = '$agentid' and identity = '$identity'" );
			$rashod['title'] = "Расчеты с Поставщиком".(!empty($deal) ? untag( $deal )." по сделке: " : "");

			$cel   = 'Поставщику';
			$conid = $contragent = $agentid;

			$rashod['cat'] = (int)$db -> getOne( "SELECT id FROM {$sqlname}budjet_cat WHERE title = 'Поставщики' and identity = '$identity'" );

		}
		if ( $tip == 'partner' ) {

			$provider = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE type='partner' and clid = '$agentid' and identity = '$identity'" );
			$rashod['title'] = "Расчеты с Партнером по сделке: ".untag( $deal );

			$cel    = 'Партнеру';
			$partid = $contragent = $agentid;

			$rashod['cat'] = $db -> getOne( "SELECT id FROM {$sqlname}budjet_cat WHERE title = 'Партнеры' and identity = '$identity'" );

		}

	}
	if( $id > 0 && !empty($tip) ){
		$providerplus = 'yes';
	}

	$deal = [];
	if($did > 0) {
		$deal = Deal::info($did);
	}

	if ( $action == 'clone' ) {

		$olddo        = $rashod['do'];
		$rashod['do'] = '';
		$id           = 0;

	}

	$rashod['datum']        = ($id > 0) ? get_smdate( $rashod['datum'] ) : current_datum();
	$rashod['datum_plan']   = ($id > 0) ? get_smdate( $rashod['datum_plan'] ) : current_datum(-5);
	$rashod['invoice_date'] = ($id > 0) ? get_smdate( $rashod['invoice_date'] ) : current_datum();

	if($id > 0){
		$rashod['recal'] = (int)$db -> getOne( "SELECT recal FROM {$sqlname}dogprovider WHERE bid = '$id' and identity = '$identity'" );
	}

	$action = '';
	?>
	<DIV class="zagolovok">Добавить/Изменить расход/поступление:</DIV>
	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">
		<INPUT name="dogproviderid" id="dogproviderid" type="hidden" value="<?= $dogproviderid ?>">
		<input name="olddo" type="hidden" id="olddo" value="<?= $rashod['do'] ?>"/>
		<INPUT name="conid" id="conid" type="hidden" value="<?= $conid ?>">
		<INPUT name="partid" id="partid" type="hidden" value="<?= $partid ?>">
		<INPUT name="providerplus" id="providerplus" type="hidden" value="<?= $providerplus ?>">
		<INPUT name="tip" id="tip" type="hidden" value="<?= $tip ?>">
		<INPUT name="xtip" id="xtip" type="hidden" value="<?= $xtip ?>">

		<div id="formtabs" style="overflow-y: auto; max-height: 80vh">

			<?php
			$hooks -> do_action( "budjet_form_before", $_REQUEST );
			?>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата:</div>
				<div class="flex-string wp80 pl10">
					<input name="datum" type="text" class="required w160 inputdate" id="datum" value="<?= $rashod['datum'] ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Срок оплаты:</div>
				<div class="flex-string wp80 pl10">
					<input name="date_plan" type="text" class="required w160 inputdate" id="invoice_paydate" value="<?= $rashod['date_plan'] ?>">
				</div>
			</div>

			<?php
			//if( $id > 0 ){
			?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt3 right-text">Дата фактической оплаты:</div>
				<div class="flex-string wp80 pl10">
					<input name="invoice_paydate" type="text" class="w160 inputdate" id="date_plan" value="<?= $rashod['invoice_paydate'] ?>">
				</div>
			</div>
			<?php //} ?>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер счета:</div>
				<div class="flex-string wp20 pl10">
					<input name="invoice" type="text" class="w160" id="invoice" value="<?= $rashod['invoice'] ?>">
				</div>

				<div class="flex-string wp15 gray2 fs-12 pt7 right-text">Дата:</div>
				<div class="flex-string wp45 pl10">
					<input name="invoice_date" type="text" class="w160 inputdate" id="invoice_date" value="<?= $rashod['invoice_date'] ?>">
				</div>
			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
				<div class="flex-string wp80 pl10">
					<input name="title" id="title" type="text" placeholder="Краткое название" class="required wp97" value="<?= $rashod['title'] ?>"/>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Раздел:</div>
				<div class="flex-string wp80 pl10">
					<select name="cat" id="cat" class="required wp97">
						<option value="">--выбор--</option>
						<optgroup label="Доходы">
							<?php
							$res = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and tip='dohod' and identity = '$identity' ORDER BY title" );
							foreach ( $res as $da ) {

								print '<option disabled>'.$da['title'].'</option>';

								$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '".$da['id']."' and tip='dohod' and identity = '$identity' ORDER BY title" );
								foreach ( $result as $data ) {

									$s = ($data['id'] == $rashod['cat']) ? 'selected' : '';

									print '<option value="'.$data['id'].'" '.$s.'>&nbsp;&nbsp;-&nbsp;'.$data['title'].'</option>';

								}

							}
							?>
						</optgroup>
						<optgroup label="Расходы">
							<?php
							$res = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and tip='rashod' and identity = '$identity' ORDER BY title" );
							foreach ( $res as $da ) {

								print '<option disabled>'.$da['title'].'</option>';

								$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '".$da['id']."' and tip='rashod' and identity = '$identity' ORDER BY title" );
								foreach ( $result as $data ) {

									$clientpath = '';

									if ( $data['clientpath'] > 0 ) {
										$clientpath = $db -> getOne("SELECT name FROM {$sqlname}clientpath WHERE id = '".$data['clientpath']."'");
									}

									$clientpath = ($clientpath != '') ? '[ Связь с каналом: '.$clientpath.' ]' : "";

									$s = ($data['id'] == $rashod['cat']) ? 'selected' : '';

									print '<option value="'.$data['id'].'" '.$s.'>&nbsp;&nbsp;-&nbsp;'.$data['title'].' '.$clientpath.'</option>';

								}

							}
							?>
						</optgroup>
					</select>
				</div>

			</div>

			<!--<hr>-->

			<div class="flex-container box--child mt10 mb10 hidden">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Период:</div>
				<div class="flex-string wp30 pl10">
					<select name="bmon" id="bmon" class="required wp97">
						<?php
						$m = date( 'm' );
						for ( $i = 1; $i <= 12; $i++ ) {

							$s = ($i == $m && !isset( $rashod['mon'] )) ? "selected" : "";

							if ( $i == $rashod['mon'] && isset( $rashod['mon'] ) ) {
								$s = "selected";
							}

							print '<option value="'.$i.'" '.$s.'>'.$lang['face']['MounthName'][ ($i - 1) ].'&nbsp;&nbsp;</option>';

						}
						?>
					</select>&nbsp;
				</div>
				<div class="flex-string wp50">
					<select name="byear" id="byear" class="required wp45">
						<?php
						$ys = $year - 1;
						$ye = $year + 5;
						for ( $i = $year - 1; $i < $year + 5; $i++ ) {

							$s = ($i == $year) ? "selected" : "";

							if ( $i == $rashod['year'] ) {
								$s = "selected";
							}

							print '<option value="'.$i.'" '.$s.'>'.$i.'&nbsp;&nbsp;</option>';

						}
						?>
					</select>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Р.счет/Касса:</div>
				<div class="flex-string wp80 pl10">
					<select name="rs" id="rs" class="required wp97">
						<option value="">--выбор--</option>
						<?php
						$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
						$result = $db -> getAll( "SELECT * FROM {$sqlname}mycomps `mc` WHERE $x mc.identity = '$identity' ORDER BY mc.name_shot" );
						foreach ( $result as $data ) {

							print '<optgroup label="'.$data['name_shot'].'">';

							$z = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";
							$res = $db -> getAll( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' AND $z bloc != 'yes' and identity = '$identity' ORDER BY title" );
							foreach ( $res as $da ) {

								$s = ($rashod['rs'] == $da['id']) ? 'selected' : '';

								print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].': '.num_format( $da['ostatok'] ).' р.</option>';

							}

							print '</optgroup>';

						}
						?>
					</select>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Контрагент:</div>
				<div class="flex-string wp80 pl10 relativ cleared">

					<INPUT name="clid" type="hidden" id="clid" value="<?= $contragent ?>">
					<INPUT name="contragent" id="contragent" type="text" placeholder="Выбор Поставщика/Партнера" value="<?= current_client( $contragent ) ?>" class="wp97">
					<span class="idel clearinputs mr10 pr15" onclick="onclearContragent()" title="Очистить"><i class="icon-block-1 red"></i></span>

				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма:</div>
				<div class="flex-string wp80 pl10">

					<input name="summa" id="summa" type="text" value="<?= num_format( $rashod['summa'] ) ?>" class="required"/>&nbsp;<span class="fs-12 gray2 pl5"><?= $valuta ?></span>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сделка:</div>
				<div class="flex-string wp80 pl10 relativ cleared">

					<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= $deal['title'] ?>" class="wp97">
					<INPUT name="did" id="did" type="hidden" value="<?= $did ?>">
					<span class="idel clearinputs mr10 pr15" title="Очистить"><i class="icon-block-1 red"></i></span>

				</div>

			</div>
			<div class="flex-container box--child mt10" data-id="recal">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
				<div class="flex-string wp80 pl10 input">

					<div class="checkbox mt5">
						<label>
							<input name="recal" type="checkbox" id="recal" value="0" <?= ((int)$rashod['recal'] == 0 ? 'checked' : '') ?>>
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Вычитать из прибыли (только для сделок)
						</label>
					</div>

				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Примечание:</div>
				<div class="flex-string wp80 pl10">

					<textarea name="des" id="des" rows="3" class="wp97"><?= $rashod['des'] ?></textarea>

				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 text-right"></div>
				<div class="flex-string wp80 pl10 pb10">

					<?php
					if ( $rashod['do'] == 'on' ) {

						print '<b class="green">Проведено</b><input name="do" type="hidden" id="do" value="on" />';

					}
					else {
						?>
						<div class="checkbox">
							<label>
								<input name="do" type="checkbox" id="do" value="on" <?php print ( $rashod['do'] == 'on' ) ? 'checked' : ''; ?> />
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								&nbsp;<b>Провести?</b>
							</label>
						</div>

						<?php
						if($id > 0) {
						?>
							<div class="checkbox hidden mt10 delta">
								<label>
									<input name="addNewRashodDelta" type="checkbox" id="addNewRashodDelta" value="yes">
									<span class="custom-checkbox"><i class="icon-ok"></i></span>
									&nbsp;Создать новый <em class="gray2">(если сумма меньше запланированной)</em>
								</label>
							</div>
							<?php
						}
					}
					?>

				</div>

			</div>

			<div id="divider">
				<b>Файлы</b><i class="icon-info-circled blue" title="Разрешенные типы файлов: <?= $ext_allow ?>"></i>
			</div>

			<div class="flex-container efiles mt10">

				<div class="flex-string wp20 text-right"></div>
				<div class="flex-string wp80 fs-09" id="filelist"></div>

				<div class="flex-string wp20 text-right"></div>
				<div class="flex-string wp80 fs-09" id="fileholder"></div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 text-right">

					<span class="<?=( $contragent > 0 ? '' : 'hidden' )?> addfileButton">
						<a href="javascript:void(0)" onclick="loadCardfiles()" title="Добавить из карточки поставщика" class="button bluebtn dotted fs-11 m0"><i class="icon-plus-circled"></i></a>
					</span>
					<a href="javascript:void(0)" onclick="addfile()" title="Добавить" class="button greenbtn dotted fs-11 m0"><i class="icon-plus-circled-1"></i></a>

				</div>
				<div class="flex-string wp80 uploads pl10 pb20">

					<div id="file-1" class="filebox relativ wp100 mb5">
						<input name="file[]" type="file" class="file wp97" id="file[]">
						<div class="idel hand mr15" title="Очистить"><i class="icon-cancel-circled red"></i></div>
					</div>

				</div>

			</div>

		</div>

		<div class="hidden" data-type="fileholder"></div>

		<hr>

		<div class="button--pane text-right wp100">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>

	</FORM>

	<!--шаблон блока для файлов карточки-->
	<div id="filesTpl" type="x-tmpl-mustache" class="hidden">

		<div class="closer" title="Закрыть"><i class="icon-cancel-circled"></i></div>

		<div class="header pl10 Bold fs-12">Выбор файла из карточки</div>

		<div class="body">
			{{#list}}
			<div class="zfile infodiv bgwhite dotted p10 mb5 ha hand wp97" data-id="{{id}}">
				{{{icon}}} <b>{{name}}</b> [ {{size}} ] <span class="xdel hidden"><A href="javascript:void(0)" onclick="xfileDelete({{id}})" title="Удалить"><i class="icon-cancel-circled red"></i></A></span>
				<input type="hidden" name="fid[]" value="{{id}}">
			</div>
			{{/list}}
		</div>
	</div>
	<?php

	$hooks -> do_action( "budjet_form_after", $_REQUEST );

}
if ( $action == 'move' ) {

	$id = $_REQUEST['id'];

	if ( $id > 0 ) {
		$move = $db -> getRow("SELECT * FROM {$sqlname}budjet WHERE id='$id' and identity = '$identity'");
	}
	else {
		$move['title'] = 'Перемещение '.current_datumtime();
	}

	$m    = date( 'm' );
	$year = date( 'Y' );


	?>
	<DIV class="zagolovok">Переместить средства:</DIV>
	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="move">

		<div id="formtabs" style="overflow-y: auto; max-height: 90vh">

			<?php
			$hooks -> do_action( "budjet_moveform_before", $_REQUEST );
			?>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название (кратко):</div>
				<div class="flex-string wp80 pl10">
					<input name="title" id="title" type="text" placeholder="Краткое название" class="required wp97" value="<?= $move['title'] ?>"/>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Счет списания:</div>
				<div class="flex-string wp80 pl10">

					<select name="rs" id="rs" class="required wp97">
						<option value="">--выбор--</option>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot" );
						foreach ( $result as $data ) {
							?>
							<optgroup label="<?= $data['name_shot'] ?>">
								<?php
								$re = $db -> getAll( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' and identity = '$identity' ORDER BY title" );
								foreach ( $re as $da ) {

									$s = ($da['id'] == $move['rs']) ? "selected" : "";

									print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].': '.num_format( $da['ostatok'] ).' р.</option>';

								}
								?>
							</optgroup>
						<?php } ?>
					</select>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Счет пополнения:</div>
				<div class="flex-string wp80 pl10">

					<select name="rs_move" id="rs_move" class="required wp97">
						<option value="">--выбор--</option>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot" );
						foreach ( $result as $data ) {
							?>
							<optgroup label="<?= $data['name_shot'] ?>">
								<?php
								$re = $db -> getAll( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' and identity = '$identity' ORDER BY title" );
								foreach ( $re as $da ) {

									$s = ($da['id'] == $move['rs2']) ? "selected" : "";

									print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].': '.num_format( $da['ostatok'] ).' р.</option>';

								}
								?>
							</optgroup>
						<?php } ?>
					</select>

				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Период:</div>
				<div class="flex-string wp80 pl10">
					<select name="bmon" id="bmon" class="required wp45">
						<?php
						$m = date( 'm' );
						for ( $i = 1; $i <= 12; $i++ ) {

							$s = ($i == $m) ? "selected" : "";

							print '<option value="'.$i.'" '.$s.'>'.ru_mon( $i ).'&nbsp;&nbsp;</option>';

						}
						?>
					</select>&nbsp;
					<select name="byear" id="byear" class="required wp45">
						<?php
						$ys = $year - 1;
						$ye = $year + 5;
						for ( $i = $year - 1; $i < $year + 5; $i++ ) {

							$s = ($i == $year) ? "selected" : "";

							print '<option value="'.$i.'" '.$s.'>'.$i.'&nbsp;&nbsp;</option>';

						}
						?>
					</select>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма:</div>
				<div class="flex-string wp80 pl10">

					<input name="summa" id="summa" type="text" value="<?= num_format( $move['summa'] ) ?>" class="required">&nbsp;<?= $valuta ?>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Примечание:</div>
				<div class="flex-string wp80 pl10">

					<textarea name="des" id="des" rows="3" class="wp97"><?= $move['des'] ?></textarea>

				</div>

			</div>

			<div id="divider">
				<b>Файлы</b><i class="icon-info-circled blue" title="Разрешенные типы файлов: <?= $ext_allow ?>"></i>
			</div>

			<div class="flex-container box--child mt10 mb10 hidden efiles">

				<div class="flex-string wp100 pb20" id="filelist"></div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 right-text">

					<a href="javascript:void(0)" onclick="addfile()" title="Добавить" class="button greenbtn dotted fs-11 m0">Добавить</a>

				</div>
				<div class="flex-string wp80 uploads pl10 pb20">

					<div id="file-1" class="filebox relativ wp100 mb5">
						<input name="file[]" type="file" class="file wp97" id="file[]">
						<div class="idel hand mr15" title="Очистить"><i class="icon-cancel-circled red"></i></div>
					</div>

				</div>

			</div>

			<hr>

			<div class="button--pane text-right">

				<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
				<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

			</div>

	</FORM>
	<?php

	$hooks -> do_action( "budjet_moveform_after", $_REQUEST );

}
if ( $action == 'view' ) {

	$id = (int)$_REQUEST['id'];

	$item = Budget::info($id)['budget'];
	?>
	<DIV class="zagolovok">Просмотр данных</DIV>

	<div id="formtabs" class="box--child" style="overflow-y: unset; max-height: 90vh">

		<div class="flex-container box--child mt10 mb20">

			<div class="flex-string wp100 text-center fs-12 Bold relativ">
				ID <?= $id ?>: <?= $item['title'] ?>

				<!--Лог изменения статусов-->
				<div class="pull-aright hidden-iphone mr10 fs-09">
					<?php
					if ( !empty( $item['changelog'] ) ) {
						?>
						<div class="tagsmenuToggler hand relativ inline" data-id="fhelper">
							<span class="fs-07 blue"><i class="icon-help-circled"></i> Лог</span>
							<div class="tagsmenu fly1 right hidden" id="fhelper" style="right:0; top: 100%">
								<div class="blok p10 w300 fs-07">
									<?php
									foreach ( $item['changelog'] as $status) {

										print '
										<div class="flex-container box--child mt5 p5 text-left infodiv">
											<div class="flex-string wp25 pr10">'.get_sfdate( $status['datum'] ).'</div>
											<div class="flex-string wp75">
												<div class="Bold fs-11">'.$status['statusName'].'</div>
												<div class="gray2 fs-09">'.$status['comment'].'</div>
											</div>
										</div>
										';

									}
									?>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>

			</div>

		</div>

		<hr>

		<div class="flex-container not--mob box--child">

		</div>

		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Раздел:</div>
			<div class="flex-string wp80 pl10 fs-12"><?= $item['razdel'] ?></div>

		</div>
		<?php if ( !empty($item['date_plan']) ) { ?>
		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Дата план:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<?= format_date_rus_name( $item['date_plan'] ) ?>
			</div>

		</div>
		<?php } ?>
		<?php if ( !empty($item['invoice_paydate']) ) { ?>
		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Дата факт:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<?= format_date_rus_name( $item['invoice_paydate'] ) ?>
			</div>

		</div>
		<?php } ?>
		<?php if ( !empty($item['invoice']) ) { ?>
		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Счет поставщика:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<b><?= $item['invoice'] ?></b> от <?= format_date_rus_name( $item['invoice_date'] ) ?>
			</div>
		<?php } ?>
		</div>
		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Период:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<b><?= ru_mon( $item['mon'] ).".".$item['year'] ?></b> (<?= get_sfdate( $item['datum'] ) ?>)
			</div>

		</div>
		<?php if ( !empty($item['bank']) ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Р.счет/Касса:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $item['bank'] ?></div>

			</div>
		<?php } ?>
		<?php if ( (int)$item['cat'] == 0 ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Счет списания:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $item['bank'] ?></div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Счет пополнения:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $item['bank2'] ?></div>

			</div>
		<?php } ?>
		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Сумма:</div>
			<div class="flex-string wp80 pl10 fs-12 Bold"><?= num_format( $item['summa'] ) ?>&nbsp;<?= $valuta ?></div>

		</div>
		<?php if ( !empty($item['des']) ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Примечание:</div>
				<div class="flex-string wp80 pl10 fs-12 flh-12"><?= nl2br( $item['des'] ) ?></div>

			</div>
		<?php } ?>
		<?php if ( (int)$item['conid'] > 0 ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Поставщик:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<A href="javascript:void(0)" onclick="openClient('<?= $item['conid'] ?>')" title="Открыть"><i class="icon-flag blue"></i><?= current_client( $item['conid'] ) ?>
					</a>
				</div>

			</div>
		<?php } ?>
		<?php if ( (int)$item['partid'] > 0 ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Партнер:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<A href="javascript:void(0)" onclick="openClient('<?= $item['partid'] ?>')" title="Открыть"><i class="icon-flag blue"></i><?= current_client( $item['partid'] ) ?>
					</a>
				</div>

			</div>
		<?php } ?>
		<?php if ( (int)$item['did'] > 0 ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Сделка:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<A href="javascript:void(0)" onclick="openDogovor('<?= $item['did'] ?>')" title="Открыть"><i class="icon-briefcase-1 broun"></i><?= current_dogovor( $item['did'] ) ?></a>
				</div>

			</div>
		<?php } ?>

		<hr>

		<div class="flex-container box--child mt10 mb10">

			<div class="flex-string wp20 gray2 fs-12 right-text">Статус:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<?php
				print $item['do'] == 'on' ? '<b class="green">Проведено</b>' : '<b class="red">Ждет проведения</b>';
				?>
			</div>

		</div>
		<?php if ( !empty( $item['files'] ) ) { ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Файлы:</div>
				<div class="flex-string wp80 pl10 fs-12">

					<div class="viewdiv">
						<?php
						foreach ( $item['files'] as $file ) {

							?>
							<div class="inline pad5">
								<a href="javascript:void(0)" onclick="fileDownload('<?= $file['id'] ?>','','yes')"><?= $file['icon'] ?>&nbsp;<?= $file['name'] ?></a>
							</div>
							<?php

						}
						?>
					</div>

				</div>

			</div>
		<?php } ?>

	</div>

	<hr>

	<div class="button--pane text-right">

		<?php if ( $item['do'] != 'on' && $userSettings['dostup']['budjet']['action'] == 'yes' ) { ?>
			<A href="javascript:void(0)" onclick="editBudjet('<?= $id ?>','edit')" class="button">Провести</A>
		<?php } ?>
		<A href="javascript:void(0)" onclick="DClose();" class="button">Закрыть</A>

	</DIV>
	<?php
}

if ( $action == 'cat.edit' ) {

	$id = $_REQUEST['id'];

	if ( $id > 0 ) {

		$result     = $db -> getRow( "SELECT * FROM {$sqlname}budjet_cat WHERE id = '$id' and identity = '$identity' ORDER BY title" );
		$title      = $result["title"];
		$tip        = $result["tip"];
		$subid      = $result["subid"];
		$clientpath = $result["clientpath"];

		$count = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}budjet_cat WHERE subid = '$id' and identity = '$identity'" );

		if ( $subid == '0' ) {
			$ttl = 'Раздел';
		}
		else {
			$ttl = 'Статью';
		}

	}
	?>
	<DIV class="zagolovok">Редактор разделов / статей расходов:</DIV>

	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="cat.edit">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" class="box--child" style="overflow-y: auto; max-height: 90vh">

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
				<div class="flex-string wp80 pl10">
					<input name="title" id="title" type="text" placeholder="Краткое название" class="required wp97" value="<?= $title ?>"/>
				</div>

			</div>
			<?php if ( $count == 0 ) { ?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Головной раздел:</div>
					<div class="flex-string wp80 pl10">

						<select name="subid" id="subid" class="wp97">
							<option value="">--нет--</option>
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and id != '$id' and identity = '$identity' ORDER BY title" );
							foreach ( $result as $data ) {

								$s = ($data['id'] == $subid) ? "selected" : "";
								print '<option value="'.$data['id'].'" '.$s.' data-tip="'.$data['tip'].'">'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>
			<?php } ?>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Тип:</div>
				<div class="flex-string wp80 pl10">

					<select name="tip" id="tip" class="required wp97">
						<option value="">--выбор--</option>
						<option value="dohod" <?php print ( $tip == 'dohod' ) ? "selected" : "" ?>>Доход</option>
						<option value="rashod" <?php print ( $tip == 'rashod' ) ? "selected" : "" ?>>Расход</option>
					</select>

				</div>

			</div>

			<div class="flex-container mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Канал продаж:</div>
				<div class="flex-string wp80 pl10">

					<?php
					$element = new Salesman\Elements();
					$path    = $element -> ClientpathSelect( 'clientpath', [
						"class" => "wp97",
						"sel"   => ($clientpath > 0) ? $clientpath : '-1'
					] );

					print $path;
					?>

				</div>

			</div>

		</div>

		<div class="infodiv" id="info">

			<h3 class="red mt0">Важно:</h3>
			- Разделы объединяют Cтатьи расходов по сути<br>
			- При создании нового <u>Раздела</u> поле "Головной раздел" оставьте пустым<br>
			- Канал следует выбирать только для Статьи расхода

		</div>

		<hr>

		<div class="button--pane text-right wp100">

			<div class="pull-left pl5">
				<A href="javascript:void(0)" onclick="editBudjet('<?= $id ?>','cat.delete')" class="button redbtn">Удалить</A>&nbsp;
			</div>

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</div>

	</FORM>

	<script>

		$(function () {

			$('#subid').trigger('change');

		});

		$(document).on('change', '#subid', function () {

			var ide = $('option:selected', this).val();
			var tip = $('option:selected', this).data('tip');

			if (ide == '') $('#clientpath').prop('disabled', true);
			else $('#clientpath').prop('disabled', false);

			if (tip != '') $('#tip').val(tip);

		});

	</script>
	<?php
}
if ( $action == 'cat.delete' ) {

	$id = $_REQUEST['id'];

	if ( $id > 0 ) {

		$result     = $db -> getRow( "SELECT * FROM {$sqlname}budjet_cat WHERE id = '$id' and identity = '$identity' ORDER BY title" );
		$title      = $result["title"];
		$tip        = $result["tip"];
		$subid      = $result["subid"];
		$clientpath = $result["clientpath"];

		$count = $db -> getOne( "SELECT COUNT(*) as count FROM {$sqlname}budjet_cat WHERE subid = '$id' and identity = '$identity'" );

		$ttl = ( $subid == '0' ) ? 'Раздела' : 'Статьи';
		$ttr = ( $subid == '0' ) ? 'Раздел' : 'Статью';

	}
	?>
	<DIV class="zagolovok">Удаление <?=$ttl?> расходов:</DIV>

	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="cat.delete">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" class="box--child" style="overflow-y: auto; max-height: 90vh">

			<?php if ( $count > 0 ) { ?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Перенести в раздел:</div>
					<div class="flex-string wp80 pl10">

						<select name="newcat" id="newcat" class="wp97">
							<option value="">--нет--</option>
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and id != '$id' and identity = '$identity' ORDER BY title" );
							foreach ( $result as $data ) {

								$s = ($data['id'] == $subid) ? "selected" : "";
								print '<option value="'.$data['id'].'" '.$s.' data-tip="'.$data['tip'].'">'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>
			<?php } ?>
			<?php if ( $count == 0 ) { ?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Перенести в статью:</div>
					<div class="flex-string wp80 pl10">

						<select name="newcat" id="newcat" class="wp97">
							<option value="">--нет--</option>
							<?php
							$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet_cat WHERE subid = '$subid' and id != '$id' and identity = '$identity' ORDER BY title" );
							foreach ( $result as $data ) {

								$s = ($data['id'] == $subid) ? "selected" : "";
								print '<option value="'.$data['id'].'" '.$s.' data-tip="'.$data['tip'].'">'.$data['title'].'</option>';

							}
							?>
						</select>

					</div>

				</div>
			<?php } ?>

		</div>

		<div class="infodiv" id="info">

			Укажите <?=$ttr?>, в которую следует перенести записи из удаляемого раздела

		</div>

		<hr>

		<div class="button--pane text-right wp100">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Выполнить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}

if ( $action == 'export.invoices' ) {

	$dstart = date( "Y-m-d", strToTime( "-30 days" ) );
	$dend   = current_datum();
	?>
	<DIV class="zagolovok"><B>Экспорт счетов</B></DIV>
	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="eform" id="eform" target="blank">
		<input name="action" id="action" type="hidden" value="export.invoices">

		<div class="row">

			<div class="column12 grid-12 fs-12 text-center gray2 Bold">Выбор периода</div>

			<hr>

			<div class="column12 grid-1 fs-12 right-text gray2 pt10">c</div>
			<div class="column12 grid-2">
				<input type="text" name="dstart" id="dstart" value="<?= $dstart ?>">
			</div>
			<div class="column12 grid-1 fs-12 right-text gray2 pt10">по</div>
			<div class="column12 grid-2">
				<input type="text" name="dend" class="required" id="dend" value="<?= $dend ?>">
			</div>

		</div>

		<div class="infodiv div-center" style="width: 95%;">Укажите период выставления счетов или оставьте пустым поле "Начало периода" для вывода всех данных</div>

		<hr>

		<DIV class="text-right">

			<A href="javascript:void(0)" onclick="$('#eform').trigger('submit')" class="button">Получить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>

	</FORM>
	<?php
}

//промотр пика раходов на текущую дату дл вбранной статьи
if ( $action == 'viewlist' ) {

	$mon   = $_REQUEST['mon'];
	$years = $_REQUEST['years'];
	$cat   = $_REQUEST['cat'];
	$do    = $_REQUEST['do'];

	//$ss = ($do == 'on') ? " and do = 'on'" : " and do != 'on'";

	?>
	<div class="zagolovok">Список доходов/расходов</div>

	<div id="formtabs" style="overflow-y: auto; max-height: 90vh">

		<TABLE class="border-bottom">
			<thead class="sticked--top">
			<TR>
				<TH class="w70">Дата</TH>
				<TH class="text-left" id="journalTitle">
					<DIV class="ellipsis">Название</DIV>
				</TH>
				<TH class="w30">Тип</TH>
				<TH class="w100 text-left">
					<DIV class="ellipsis">Статья/Источник</DIV>
				</TH>
				<TH class="w100 text-right">Сумма, <?= $valuta ?></TH>
				<TH class="w100 text-left">
					<DIV class="ellipsis">Ответственный</DIV>
				</TH>
				<TH class="w160">Примечание</TH>
			</TR>
			</thead>
			<?php
			$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet WHERE year = '$years' and mon = '$mon' and cat = '$cat' ".$ss." and identity = '$identity' ORDER by datum DESC" );
			foreach ( $result as $data ) {

				$dogovor = $ist = '';
				$color   = 'bgwhite';

				$res   = $db -> getRow( "SELECT * FROM {$sqlname}budjet_cat WHERE id = '".$data['cat']."' and identity = '$identity'" );
				$cat   = $res["title"];
				$tip   = $res["tip"];
				$subid = $res["subid"];

				$razdel = $db -> getOne( "SELECT title FROM {$sqlname}budjet_cat WHERE id = '".$subid."' and identity = '$identity'" );

				if ( $tip == 'dohod' ) {
					$tip = '<b class="green" title="Поступление"><i class="icon-up-big green"></i></b>';
				}
				elseif ( $tip == 'rashod' ) {
					$tip = '<b class="red" title="Расход"><i class="icon-down-big red"></i></b>';
				}

				if ( $data['do'] == 'on' && $data['cat'] != '0' ) {
					$do = '<a href="javascript:void(0)" onclick="budjet_doit(\''.$data['id'].'\',\'undoit\');" title="Отменить операцию"><i class="icon-ccw blue"></i></a>';
				}

				elseif ( $data['do'] != 'on' && $data['cat'] != '0' ) {
					$do    = '<a href="javascript:void(0)" onclick="budjet_doit(\''.$data['id'].'\',\'doit\');" title="Поставить отметку об оплате"><i class="icon-attention broun"></i></a>';
					$color = 'graybg-sub';
				}
				elseif ( $data['cat'] == '0' ) {
					$do  = '<i class="icon-ok green"></i>';
					$tip = '<b class="blue" title="Перемещение"><i class="icon-shuffle blue"></i></b>';
				}

				$ist = $db -> getOne( "SELECT tip FROM {$sqlname}mycomps_recv WHERE id = '".$data['rs']."' and identity = '$identity'" );

				if ( $ist == 'bank' ) {
					$istochnik = 'р/сч.';
				}
				elseif ( $ist == 'kassa' ) {
					$istochnik = 'касса';
				}
				else {
					$istochnik = '-/-';
				}

				if ( $data['did'] > 0 ) {
					$dogovor = '<DIV class="ellipsis" title="'.current_dogovor($data['did']).'"><A href="javascript:void(0)" onclick="openDogovor(\''.$data['did'].'\')" title="Открыть в новом окне"><i class="icon-briefcase broun"></i><b>'.current_dogovor($data['did']).'</b></A></DIV>';
				}

				$provider = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['conid']."' and identity = '$identity'" );
				$provider = ($provider != '') ? '<br><DIV class="ellipsis" title="'.$provider.'"><i class="icon-flag blue"></i><b>Поставщик: '.$provider.'</b></DIV>' : '';

				$partner = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['partid']."' and identity = '$identity'" );
				$partner = ($partner != '') ? '<br><DIV class="ellipsis" title="'.$partner.'"><i class="icon-flag green"></i><b>Партнер: '.$partner.'</b></DIV>' : '';

				?>
				<tr class="th40 ha <?= $color ?>" id="zebraa">
					<td class="text-left">

						<DIV class="fs-09" title="<?= get_sfdate( $data['datum'] ) ?>"><?= get_sfdate( $data['datum'] ) ?></DIV>

					</td>
					<td>
						<DIV class="ellipsis">
							<b class="blue"><a href="javascript:void(0)" onclick="editBudjet('<?= $data['id'] ?>','view')" title="Просмотр: <?= $data['title'] ?>"><?= $data['title'] ?></a></b>
						</DIV>
					</td>
					<td class="text-center">
						<div><?= $tip ?></div>
					</td>
					<td>
						<DIV class="ellipsis Bold" title="<?= $cat ?>"><?= $cat ?></DIV>
						<div class="em gray2 fs-09"><?= $istochnik ?></div>
					</td>
					<td class="text-right"><?= num_format( $data['summa'] ) ?></td>
					<td>
						<DIV class="ellipsis" title="<?= current_user( $data['iduser'] ) ?>"><?= current_user( $data['iduser'] ) ?></DIV>
					</td>
					<td>
						<?= $dogovor ?>
						<?= $provider ?>
						<?= $partner ?>
					</td>
				</tr>
				<?php
			}
			?>
		</TABLE>

	</div>
	<script>

		var dwidth = $(document).width();

		if (!isMobile) {

			if (dwidth < 945)
				dialogWidth = '90%';
			else if (dwidth > 1300)
				dialogWidth = '900px';
			else
				dialogWidth = '90vw';

		}
		$(function () {

			$('#dialog').css('width', dialogWidth).center();

		});
	</script>
	<?php

	//exit();

}
if ( $action == 'viewpayment' ) {

	$mon   = $_REQUEST['mon'];
	$years = $_REQUEST['years'];
	$do    = $_REQUEST['do'];

	if ( $do == 'on' ) {

		$ss    = " and do = 'on'";
		$dd    = 'invoice_date';
		$title = 'Оплаченные счета';

	}
	else {

		$ss    = " and do != 'on'";
		$dd    = "datum_credit";
		$title = 'Выставленные счета';

	}

	$result = $db -> getAll( "SELECT * FROM {$sqlname}credit WHERE date_format(".$dd.", '%Y-%c') = '$years-$mon' $ss and identity = '$identity' ORDER BY invoice_date DESC" );
	?>
	<div class="zagolovok">Список оплат - <?= $title ?></div>
	<div id="formtabs" style="max-height: 60vh; overflow-y: auto;">

		<TABLE class="border-bottom bgwhite">
			<thead class="sticked--top">
			<TR>
				<TH class="w80"><B>Дата</B></TH>
				<TH class="w80"><b>№ счета</b></TH>
				<TH class="w120"><B>№ договора</B></TH>
				<TH class="w120"><B>Сумма / Маржа</B></TH>
				<TH class="text-left">Сделка / Заказчик</TH>
			</TR>
			</thead>
			<tbody>
			<?php
			foreach ( $result as $data ) {

				if ( $data['clid'] > 0 ) {
					$roditel = '<SPAN title="'.current_client($data['clid']).'" class="ellipsis"><A href="javascript:void(0)" onclick="openClient(\''.$data['clid'].'\')" title="Открыть в новом окне"><i class="icon-building broun"></i></A>&nbsp;<A href="javascript:void(0)" onclick="viewClient(\''.$data['clid'].'\')" title="Заказчик">'.current_client($data['clid']).'</A></SPAN>';
				}

				if ( $data['pid'] > 0 ) {
					$roditel = '<SPAN title="'.current_person($data['pid']).'" class="ellipsis"><A href="javascript:void(0)" onclick="openPerson(\''.$data['pid'].'\')" title="Открыть в новом окне"><i class="icon-user-1 blue"></i></A>&nbsp;<A href="javascript:void(0)" onclick="viewPerson(\''.$data['pid'].'\')" title="Заказчик">'.current_person($data['pid']).'</A></SPAN>';
				}

				$payer = $db -> getOne( "SELECT payer FROM {$sqlname}dogovor where did = '".$data['did']."' and identity = '$identity'" );

				if ( $payer > 0 ) {
					$payer = '<br><span class="ellipsis"><a href="javascript:void(0)" onclick="openClient(\''.$payer.'\')" title="Плательщик"><i class="icon-building blue"></i>&nbsp;'.current_client($payer).'</a></span>';
				}


				$rs = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id = '".$data['rs']."' and identity = '$identity' ORDER by id" );

				$param = [
					'year' => $years,
					'mon'  => $mon,
					'do'   => $do
				];
				?>
				<TR class="th40 ha">
					<TD class="text-center">
						<div class="fs-09"><?= format_date_rus( $data[ $dd ] ) ?></div>
					</TD>
					<TD>
						<SPAN title="<?= $data['invoice'] ?>" class="ellipsis">
							<?php if ($otherSettings['printInvoice']){ ?>
							<a href="javascript:void(0)" onclick="editCredit('<?= $data['crid'] ?>','credit.view')" title="Просмотр"><i class="icon-eye broun"></i></a>&nbsp;
							<?php } ?>
							<B><?= $data['invoice'] ?></B>
						</SPAN>
					</TD>
					<TD>
						<SPAN title="<?= $data['invoice_chek'] ?>" class="ellipsis"><B><?= $data['invoice_chek'] ?></B></SPAN>
					</TD>
					<TD class="text-right">
						<div class="Bold"><?= num_format( $data['summa_credit'] ) ?></div>
						<div class="fs-09 gray2"><?= num_format( getMargaPayed( $data['did'], $param ) ) ?></div>
					</TD>
					<TD class="text-left">
						<span class="ellipsis">
							<A href="javascript:void(0)" onclick="openDogovor('<?= $data['did'] ?>')" title="Открыть в новом окне"><i class="icon-briefcase broun"></i></A>&nbsp;<span title="Быстрый просмотр: <?= current_dogovor( $data['did'] ) ?>" onclick="viewDogovor('<?= $data['did'] ?>')" class="list"><B><?= current_dogovor( $data['did'] ) ?></B></span>
						</span><br>
						<?= $roditel ?>
						<?= $payer ?>
					</TD>
				</TR>
			<?php } ?>
			</tbody>
		</TABLE>

	</div>
	<script>

		var dwidth = $(document).width();

		if (!isMobile) {

			if (dwidth < 945)
				dialogWidth = '90%';
			else if (dwidth > 1300)
				dialogWidth = '900px';
			else
				dialogWidth = '90vw';

		}

		$(function () {

			$('#dialog').css('width', dialogWidth).center();

		});

	</script>
	<?php
	exit();
}
if ( $action == 'journalmon' ) {

	$mon = date( 'm' );
	?>
	<DIV class="zagolovok"><B>Расходы в текущем месяце</B></DIV>

	<div style="max-height: 600px;">

		<TABLE id="bborder">
			<thead class="sticked--top">
			<TR class="header_contaner">
				<TH class="w70">Период</TH>
				<TH class="w60">Дата</TH>
				<TH class="w30">Тип</TH>
				<TH class="w100 text-left">
					<DIV class="ellipsis">Статья</DIV>
				</TH>
				<TH class="text-left" id="journalTitle">
					<DIV class="ellipsis">Название</DIV>
				</TH>
				<TH class="w50" title="Источник">Ист.</TH>
				<TH class="w100 text-right">Сумма, <?= $valuta ?></TH>
				<TH class="w100 text-left">
					<DIV class="ellipsis">Ответственный</DIV>
				</TH>
			</TR>
			</thead>
			<tbody>
			<?php
			$result = $db -> getAll( "SELECT * FROM {$sqlname}budjet WHERE year = '".$year."' and mon = '".$mon."' and identity = '$identity' ORDER by mon DESC, datum" );
			foreach ( $result as $data ) {

				$color = $ist = $dogovor = '';

				$res   = $db -> getRow( "SELECT * FROM {$sqlname}budjet_cat WHERE id='".$data['cat']."' and identity = '$identity'" );
				$cat   = $res["title"];
				$tip   = $res["tip"];
				$subid = $res["subid"];

				$razdel = $db -> getOne( "SELECT title FROM {$sqlname}budjet_cat WHERE id='".$subid."' and identity = '$identity'" );

				if ( $tip == 'dohod' ) {
					$tip = '<b class="green" title="Поступление"><i class="icon-up-big green"></i></b>';
				}
				elseif ( $tip == 'rashod' ) {
					$tip = '<b class="red" title="Расход"><i class="icon-down-big red"></i></b>';
				}

				if ( $data['cat'] == '0' ) {
					$tip = '<b class="blue" title="Перемещение"><i class="icon-shuffle blue"></i></b>';
				}

				if ( $data['do'] == 'on' && $data['cat'] != '0' ) {

					$do = '<a href="javascript:void(0)" onclick="budjet_doit(\''.$data['id'].'\',\'undoit\');" title="Отменить операцию"><i class="icon-ccw blue"></i></a>';

				}
				if ( $data['do'] != 'on' && $data['cat'] != '0' ) {

					$do    = '<a href="javascript:void(0)" onclick="doLoad(\'finance/budjet.php?action=edit&id='.$data['id'].'\')" title="Поставить отметку об оплате"><i class="icon-plus-circled broun"></i></a>';
					$color = 'graybg-sub';

				}
				if ( $data['cat'] == '0' ) {

					$do = '<i class="icon-ok green"></i>';

				}

				$ist = $db -> getOne( "SELECT tip FROM {$sqlname}mycomps_recv WHERE id = '".$data['rs']."' and identity = '$identity'" );

				if ( $ist == 'bank' ) {
					$istochnik = 'р/сч.';
				}
				elseif ( $ist == 'kassa' ) {
					$istochnik = 'касса';
				}
				else {
					$istochnik = '-/-';
				}

				if ( $data['did'] ) {
					$dogovor = '<DIV class="ellipsis" title="'.current_dogovor($data['did']).'"><A href="javascript:void(0)" onclick="openDogovor(\''.$data['did'].'\')" title="Открыть в новом окне"><i class="icon-briefcase broun"></i><b>'.current_dogovor($data['did']).'</b></A></DIV>';
				}

				$provider = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['conid']."' and identity = '$identity'" );
				$provider = ($provider != '') ? '<br><DIV class="ellipsis" title="'.$provider.'"><i class="icon-flag blue"></i><b>Поставщик: '.$provider.'</b></DIV>' : '';

				$partner = $db -> getOne( "SELECT title FROM {$sqlname}clientcat WHERE clid = '".$data['partid']."' and identity = '$identity'" );
				$partner = ($partner != '') ? '<br><DIV class="ellipsis" title="'.$partner.'"><i class="icon-flag green"></i><b>Партнер: '.$partner.'</b></DIV>' : '';

				?>
				<tr class="th40 ha <?= $color ?>">
					<td class="text-left"><b><?= $data['mon'].".".$data['year'] ?></b></td>
					<td class="text-left">
						<DIV class="smalltxt1" title="<?= get_sfdate( $data['datum'] ) ?>"><?= get_sfdate( $data['datum'] ) ?></DIV>
					</td>
					<td class="text-center"><?= $tip ?></td>
					<td>
						<DIV class="ellipsis" title="<?= $cat ?>"><?= $cat ?></DIV>
					</td>
					<td>
						<DIV class="ellipsis1">
							<a href="javascript:void(0)" onclick="editBudjet('<?= $data['id'] ?>','view')" title="Просмотр: <?= $data['title'] ?>"><?= $data['title'] ?></a>&nbsp;&nbsp;
							<?php if ( $data['cat'] != '0' ) { ?>
								<a href="javascript:void(0)" onclick="editBudjet('<?= $data['id'] ?>','add')" title="Создать новый доход/расход на основе существующего"><i class="icon-docs broun smalltxt"></i></a>
							<?php } ?>
						</DIV>
					</td>
					<td class="text-center"><?= $istochnik ?></td>
					<td class="text-right"><?= num_format( $data['summa'] ) ?></td>
					<td>
						<DIV class="ellipsis" title="<?= current_user( $data['iduser'] ) ?>"><?= current_user( $data['iduser'] ) ?></DIV>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</TABLE>

	</div>

	<hr>

	<div class="text-right">

		<a href="/finance?#journal">Журнал расходов &rarr;</a>

	</div>
	<script type="text/javascript">

		$('#dialog').css('width', '70%');

	</script>
	<?php
	exit();
}

// импорт выписки банка в формате 1С
if ( $action == 'import.statement.s1' ) {

	?>
	<div class="zagolovok">Импорт журнала расходов. Шаг 1</div>
	<form action="/modules/finance/form.budjet.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<input type="hidden" name="action" id="action" value="import.statement.s2">

		<div class="div-center pad5 margbot5">

			<input name="file" type="file" class="file wp100" id="file">
			<div class="infodiv">

				<ul>
					<li>Поддерживается банковская выписка в формате 1С Российских банков</li>
					<li>За один раз импортируется <b class="red">выписка только для одного</b> расчетного счета</li>
				</ul>

			</div>

		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="Next()" class="button next">Продолжить..</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</FORM>
	<script>

		var sfile = '';

		$(function () {

			$('#dialog').css('width', '600px').center();

		});

		$(document).on('change', '#file', function () {

			//console.log(this.files);

			sfile = this.value;

			var ext = this.value.split(".");
			var elength = ext.length;
			var carrentExt = ext[elength - 1].toLowerCase();

			if (in_array(carrentExt, ['txt']))
				$('.next').removeClass('graybtn');

			else {

				sfile = '';
				Swal.fire('Только в формате TXT', '', 'warning');
				$('#file').val('');
				//$('.next').addClass('graybtn');

			}

		});

		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var em = checkRequired();

				if (em === false) return false;

				$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				$('#resultdiv').empty().html(data);
				$('#dialog').css('width', '80%').center();

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}

		});

		function Next() {

			if ($('#file').val() !== '') {
				$('#Form').submit();
			}
			else {
				Swal.fire('Внимание', 'Нет данных для импорта', 'warning');
			}

		}

	</script>
	<?php

	exit();

}

// импорт выписки банка. шаг 2
if ( $action == 'import.statement.s2' ) {

	$text      = '';
	$statement = [];
	$err = [];
	$balance   = 0;
	$rs        = 0;

	if ( filesize( $_FILES['file']['tmp_name'] ) > 0 ) {

		$ext = getExtention( $_FILES['file']['name'] );

		//если загружается файл
		if ($ext == "txt") {

			//require_once "../../inc/class/Upload.php";

			$upload = Upload ::upload();

			$err = array_merge( $err, (array)$upload['message'] );

			foreach ( $upload['data'] as $file ) {

				$uploadfile = $rootpath.'/files/'.$fpath.$file['name'];

				//обрабатываем данные из файла
				$text = file_get_contents( $uploadfile );

				unlink( $uploadfile );

			}

		}
		else {
			$err[] = "Разрешены только файлы TXT";
		}

		// обрабатываем файл
		if ( !empty( $text ) ) {

			//require_once "../../inc/class/BankStatement.php";
			$statements = BankStatement ::convert( $text );

			$statement = $statements['statement'];
			$balance   = $statements['balance'];
			$rs        = $statements['rs'];

		}

	}

	$category = Budget ::getCategory();
	$rss      = Budget ::getRS();

	?>
	<div class="zagolovok">Импорт журнала расходов. Шаг 2</div>
	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="import.statement.on">
		<input name="balance" id="balance" type="hidden" value="<?= $balance ?>">
		<input name="rs" id="rs" type="hidden" value="<?= $rs ?>">

		<div id="formtabs" class="bgwhite" style="overflow: auto; height: 80vh">

			<table class="border-bottom top">
				<thead class="sticked--top graybg-dark">
				<tr>
					<th class="w100b">Дата</th>
					<th class="w100b">№ док-та</th>
					<th class="w100">Сумма</th>
					<th class="w200b">Контрагент</th>
					<th class="w160b">Наименование</th>
					<th>Содержание</th>
					<th class="w40">Р/С</th>
					<th class="w100b">ИНН</th>
					<th class="w160">Статья бюджета</th>
				</tr>
				</thead>
				<tbody>
				<?php
				$total = 0;
				foreach ( $statement as $key => $item ) {

					// найдем запись в журнале
					$jid = $item['id'] + 0;

					$jcategory = $category;

					if ( $item['bid'] == 0 ) {

						//определяем тип записи
						$clientTip = getClientData( $item['clid'], 'type' );

						// не будем вносить оплату от клиентов, а также уже связанные расходы
						if ( !in_array( $item['title'], [
								'Переводы внутренние',
								'Платеж от клиента'
							] ) && $item['bid'] == 0 && !in_array( $clientTip, [
								'client',
								'person'
							] ) ) {

							$color = ($item['tip'] == 'minus' ? 'redbg-sub' : 'greenbg-sub');

							if ( $item['tip'] == 'minus' ) {
								unset($jcategory['dohod']);
							}
							else {
								unset($jcategory['rashod']);
							}

							// формируем список статей бюджета
							$catSelect = Budget ::categorySelect( 'row['.$key.'][category]', [
								"category"     => $jcategory,
								"prevcategory" => $item['category'],
								"word"         => $item['tag'],
								"class"        => "wp100"
							] );

							//todo: оставить только один инпут - id, т.к. записи уже в журнале

							print '
							<tr class="ha th40 '.$color.'">
								<td>
									<input type="hidden" id="row['.$key.'][id]" name="row['.$key.'][id]" value="'.$jid.'">
									'.$item['datum'].'
								</td>
								<td class="text-right Bold">
									'.$item['number'].'
								</td>
								<td class="text-right Bold">
									'.num_format( $item['summa'] ).'
								</td>
								<td>
									<div class="Bold">'.$item['contragent'].'</div>
									'.($item['client']['clid'] > 0 ? '<div class="ellipsis mt5 fs-09 gray"><i class="icon-building-filled broun"></i>'.$item['client']['title'].'</div>' : '').'
								</td>
								<td>'.$item['title'].'</td>
								<td>
									<div>'.$item['content'].'</div>
									'.($item['crid'] > 0 ? '<div class="ellipsis mt5 fs-09 gray">Сч. №'.$item['invoice'].' на сумму '.num_format( $item['credit'] ).'</div>' : '').'
								</td>
								<td>
									'.$rss[ $item['rs'] ]['title'].'
									<div class="ellipsis mt5 fs-09 gray">'.$rss[ $item['rs'] ]['company'].'</div>
								</td>
								<td>'.$item['inn'].'</td>
								<td>'.$catSelect.'</td>
							</tr>
							';

							unset( $catSelect );

							$total++;

						}

					}

				}
				?>
				</tbody>
			</table>

			<?php
			if ( $total == 0 ) {
				print '
					<div class="attention">
						Все данные выписки уже проведены. См. Журнал расходов.<br>
						Вы можете обновить остаток средств на расчетном счете
					</div>
				';
			}
			?>

		</div>

		<hr>

		<div class="text-right button--pane">

			<div class="pull-left pt10 pl10 text-left">

				<div class="checkbox mt5 inline">
					<label>
						<input name="setBalance" id="setBalance" type="checkbox" value="yes">
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						<span class="Bold"><b class="blue">Обновить остаток?</b> [ По выписке: <b><?= num_format( $balance ) ?></b> ]</span>
					</label>
				</div>

				<div class="checkbox mt5 ml10 inline">
					<label>
						<input name="addContragent" id="addContragent" type="checkbox" value="yes">
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						<span class="Bold"><b class="blue">Внести контрагента в базу</b> [ как Поставщика ]</span>
					</label>
				</div>

			</div>

			<a href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button next">Продолжить..</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<script>

		var sfile = '';

		$(function () {

			$('#message').fadeTo(100, 0);

			$('#dialog').css('width', '90%').center();

			Swal.fire({
				title: "Укажите категории расходов",
				text: "Для каждого расхода мы подобрали подходящую категорию. Уточните правильность и нажмите \"Продолжить\"",
				type: "info"
			});

		});

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif>Загрузка данных. Пожалуйста подождите...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + '<br>Проведено ' + data.count + ' записей.<br>Поступления: ' + data.plus + '<br>Списания: ' + data.minus);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				razdel();

			}

		});

	</script>
	<?php

	exit();

}

// проведение записи выписки в бюджет вручную
if ( $action == 'statement.edit' ) {

	$id = $_REQUEST['id'];

	$bank = BankStatement ::info( $id );

	?>
	<DIV class="zagolovok">Добавление расхода</DIV>
	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="statement.edit">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" style="overflow-y: auto; max-height: 90vh">

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата:</div>
				<div class="flex-string wp80 pl10">
					<input name="datum" type="text" class="required w160" id="datum" value="<?= $bank['datum'] ?>">
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
				<div class="flex-string wp80 pl10">
					<input name="title" id="title" type="text" placeholder="Краткое название" class="required wp97" value="<?= $bank['title'] ?>">
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Раздел:</div>
				<div class="flex-string wp80 pl10">

					<?php
					$category = Budget ::getCategory();

					$t = ($bank['tip'] == 'dohod') ? 'rashod' : 'dohod';
					unset( $category[ $t ] );

					print $catSelect = Budget ::categorySelect( 'cat', [
						"category" => $category,
						"sel"      => $bank['category'],
						"class"    => "wp97"
					] );
					?>

				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Период:</div>
				<div class="flex-string wp30 pl10">
					<select name="bmon" id="bmon" class="required wp97">
						<?php
						$m = date( 'm' );
						for ( $i = 1; $i <= 12; $i++ ) {

							$s = ($i == $bank['mon'] && isset( $bank['mon'] )) ? "selected" : "disabled";

							print '<option value="'.$i.'" '.$s.'>'.$lang['face']['MounthName'][ ($i - 1) ].'&nbsp;&nbsp;</option>';

						}
						?>
					</select>&nbsp;
				</div>
				<div class="flex-string wp50">
					<select name="byear" id="byear" class="required wp45">
						<?php
						$ys = $year - 1;
						$ye = $year + 5;
						for ( $i = $year - 1; $i < $year + 5; $i++ ) {

							$s = ($i == $bank['year']) ? "selected" : "disabled";

							print '<option value="'.$i.'" '.$s.'>'.$i.'&nbsp;&nbsp;</option>';

						}
						?>
					</select>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Р.счет/Касса:</div>
				<div class="flex-string wp80 pl10">
					<select name="rs" id="rs" class="required wp97">
						<option value="">--выбор--</option>
						<?php
						$result = $db -> getAll( "SELECT * FROM {$sqlname}mycomps WHERE identity = '$identity' ORDER BY name_shot" );
						foreach ( $result as $data ) {

							print '<optgroup label="'.$data['name_shot'].'">';

							$res = $db -> getAll( "SELECT * FROM {$sqlname}mycomps_recv WHERE cid = '".$data['id']."' and identity = '$identity' ORDER BY title" );
							foreach ( $res as $da ) {

								$s = ($da['id'] == $bank['rs']) ? 'selected' : 'disabled';

								print '<option value="'.$da['id'].'" '.$s.'>'.$da['title'].': '.num_format( $da['ostatok'] ).' р.</option>';

							}

							print '</optgroup>';
						}
						?>
					</select>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Контрагент:</div>
				<div class="flex-string wp80 pl10 relativ cleared">

					<INPUT name="clid" type="hidden" id="clid" value="<?= $bank['clid'] ?>">
					<INPUT name="contragent" id="contragent" type="text" placeholder="Выбор Поставщика/Партнера" value="<?= current_client( $bank['clid'] ) ?>" class="wp97">
					<span class="idel clearinputs mr10 pr15" title="Очистить"><i class="icon-block-1 red"></i></span>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма:</div>
				<div class="flex-string wp80 pl10">

					<input name="summa" id="summa" type="text" value="<?= num_format( $bank['summa'] ) ?>" class="required"/>&nbsp;<span class="fs-12 gray2 pl5"><?= $valuta ?></span>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Примечание:</div>
				<div class="flex-string wp80 pl10">

					<textarea name="des" id="des" rows="3" class="wp97"><?= $bank['content'] ?></textarea>

				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 text-right"></div>
				<div class="flex-string wp80 pl10 pb10">

					<?php
					if ( $bank['do'] == 'on' ) {

						print '<b class="green">Проведено</b><input name="do" type="hidden" id="do" value="on" />';

					}
					else {
						?>
						<div class="checkbox">
							<label>
								<input name="do" type="checkbox" id="do" value="on" <?php print ( $bank['do'] == 'on' ) ? 'checked' : ''; ?> />
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								&nbsp;<b>Провести?</b>
							</label>
						</div>
						<?php
					}
					?>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right wp100">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>

	</FORM>
	<?php

}

// просмотр выписки
if ( $action == 'statement.view' ) {

	$id = $_REQUEST['id'];

	$sbank = BankStatement ::info( $id );

	$budjet = [];

	if ( $sbank['bid'] > 0 ) {

		$bjt    = Budget ::info( (int)$sbank['bid'] );
		$budjet = $bjt['budget'];

		$razdel = $db -> getOne( "SELECT title FROM {$sqlname}budjet_cat WHERE id = '".$budjet['cat']."' and identity = '$identity'" );
		$bank   = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id = '".$budjet['rs']."' and identity = '$identity'" );
		$bank2  = $db -> getOne( "SELECT title FROM {$sqlname}mycomps_recv WHERE id = '".$budjet['rs2']."' and identity = '$identity'" );

		$files = yexplode( ";", $budjet['fid'] );

	}

	//print_r($files);
	?>
	<DIV class="zagolovok">Просмотр данных</DIV>

	<div id="formtabs" class="box--child flh-10" style="overflow-y: auto; max-height: 90vh">

		<div class="flex-vertical">

			<div class="flex-container box--child mt10 p10 bgwhite">

				<div class="flex-string wp20">Номер</div>
				<div class="flex-string wp80 Bold"><?= $sbank['number'] ?></div>

			</div>
			<div class="flex-container wp50 box--child mt5 mb10 p10 bgwhite">

				<div class="flex-string wp20">Дата проведения</div>
				<div class="flex-string wp80 Bold"><?= format_date_rus( $sbank['datum'] ) ?></div>

			</div>
			<div class="flex-container wp50 box--child mt5 mb10 p10 bgwhite">

				<div class="flex-string wp20">Загружен</div>
				<div class="flex-string wp80 Bold"><?= get_sfdate( $sbank['date'] ) ?></div>

			</div>

			<div class="divider mt20 mb20">Плательщик</div>

			<div class="flex-container box--child mt10 p10 bgwhite">

				<div class="flex-string wp20">Наименование</div>
				<div class="flex-string wp80 Bold"><?= $sbank['from'] ?></div>

			</div>
			<div class="flex-container wp50 box--child mt5 mb10 p10 bgwhite">

				<div class="flex-string wp20">ИНН</div>
				<div class="flex-string wp80 Bold"><?= $sbank['fromINN'] ?></div>

			</div>
			<div class="flex-container wp50 box--child mt5 mb10 p10 bgwhite">

				<div class="flex-string wp20">Р.сч.</div>
				<div class="flex-string wp80 Bold"><?= $sbank['fromRS'] ?></div>

			</div>

			<div class="divider mt20 mb20">Получатель</div>

			<div class="flex-container box--child mt10 p10 bgwhite">

				<div class="flex-string wp20">Наименование</div>
				<div class="flex-string wp80 Bold"><?= $sbank['to'] ?></div>

			</div>
			<div class="flex-container wp50 box--child mt5 mb10 p10 bgwhite">

				<div class="flex-string wp20">ИНН</div>
				<div class="flex-string wp80 Bold"><?= $sbank['toINN'] ?></div>

			</div>
			<div class="flex-container wp50 box--child mt5 mb10 p10 bgwhite">

				<div class="flex-string wp20">Р.сч.</div>
				<div class="flex-string wp80 Bold"><?= $sbank['toRS'] ?></div>

			</div>

		</div>

		<?php
		if ( !empty( $budjet ) ) {
			?>

			<div class="divider mt20">Информация о записи в Журнале расходов</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Название:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $budjet['title'] ?></div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Раздел:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= $razdel ?></div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Период:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<b><?= ru_mon( $budjet['mon'] ).".".$budjet['year'] ?></b> (<?= get_sfdate( $budjet['datum'] ) ?>)
				</div>

			</div>
			<?php if ( $budjet['cat'] > 0 ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Р.счет/Касса:</div>
					<div class="flex-string wp80 pl10 fs-12"><?= $bank ?></div>

				</div>
			<?php } ?>
			<?php if ( $budjet['cat'] == 0 ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Счет списания:</div>
					<div class="flex-string wp80 pl10 fs-12"><?= $bank ?></div>

				</div>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Счет пополнения:</div>
					<div class="flex-string wp80 pl10 fs-12"><?= $bank2 ?></div>

				</div>
			<?php } ?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Сумма:</div>
				<div class="flex-string wp80 pl10 fs-12"><?= num_format( $budjet['summa'] ) ?>&nbsp;<?= $valuta ?></div>

			</div>
			<?php if ( $budjet['des'] != '' ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Примечание:</div>
					<div class="flex-string wp80 pl10 fs-10"><?= nl2br( $budjet['des'] ) ?></div>

				</div>
			<?php } ?>
			<?php if ( $budjet['conid'] > 0 ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Поставщик:</div>
					<div class="flex-string wp80 pl10 fs-12">
						<A href="javascript:void(0)" onclick="openClient('<?= $budjet['conid'] ?>')" title="Открыть"><i class="icon-flag blue"></i><?= current_client( $budjet['conid'] ) ?>
						</a>
					</div>

				</div>
			<?php } ?>
			<?php if ( $budjet['partid'] > 0 ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Партнер:</div>
					<div class="flex-string wp80 pl10 fs-12">
						<A href="javascript:void(0)" onclick="openClient('<?= $budjet['partid'] ?>')" title="Открыть"><i class="icon-flag blue"></i><?= current_client( $budjet['partid'] ) ?>
						</a>
					</div>

				</div>
			<?php } ?>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Статус:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<?php if ( $budjet['do'] == 'on' ) {
						print '<b class="green">Проведено</b>';
					}
					else {
						print '<b class="red">Ждет проведения</b>';
					} ?>
				</div>

			</div>
			<?php if ( count( $files ) > 0 ) { ?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 right-text">Файлы:</div>
					<div class="flex-string wp80 pl10 fs-12">

						<div class="viewdiv">
							<?php
							foreach ( $files as $file ) {

								$fi = $db -> getRow( "select * from {$sqlname}file where fid = '$file' and identity = '$identity'" );
								if ( $fi['ftitle'] != '' ) {
									?>
									<div class="inline pad5">
										<a href="javascript:void(0)" onclick="fileDownload('<?= $file ?>','','yes')"><?= get_icon2( $fi['ftitle'] ) ?>&nbsp;<?= $fi['ftitle'] ?></a>
									</div>
									<?php
								}

							}
							?>
						</div>

					</div>

				</div>
			<?php } ?>

			<?php
		}
		?>

	</div>

	<hr>

	<div class="button--pane text-right">

		<?php if ( $budjet['do'] != 'on' && $sbank['bid'] < 1 ) { ?>
			<A href="javascript:void(0)" onclick="editBudjet('<?= $sbank['bid'] ?>','edit')" class="button">Провести</A>
		<?php } ?>
		<A href="javascript:void(0)" onclick="DClose();" class="button">Закрыть</A>

	</DIV>
	<?php

}

// импорт расходов. шаг 1. Заморожено
if ( $action == 'import.bank.s1' ) {

	?>
	<div class="zagolovok">Импорт журнала расходов. Шаг 1</div>
	<FORM method="post" action="/modules/finance/form.budjet.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="import.bank.s2">

		<div id="formtabs" class="flex-container box--child" style="overflow-y: auto; height: 70vh">

			<div class="flex-string wp80 p10" style="height: 69.5vh;">

				<TEXTAREA name="content" rows="10" id="content" class="wp100" style="height: 100%" placeholder="Скопируйте таблицу без заголовков, строк итогов и номеров строк спецификации в Excel (Ctrl+C) и вставьте в это поле (Ctrl+V). Ничего не редактируйте."></TEXTAREA>

			</div>
			<div class="flex-string wp20 p10">

				<div id="uploads">

					<div class="fs-07 uppercase gray Bold">Импортировать выписку для 1С</div>
					<input name="file" type="file" class="file wp100" id="file">

				</div>

				<hr>

				<div class="attention">

					<div class="fs-12 broun Bold mb20">Инструкция</div>

					<ul class="p0 pl20">
						<li class="mb5">Для импорта из текста скопируйте таблицу без заголовков, строк итогов и номеров строк спецификации в Excel (Ctrl+C) и вставьте в это поле (Ctrl+V). Ничего не редактируйте. Посмотрите
							<a href="/example/speca.xls" title="Пример спецификации"><b class="red">пример</b></a>
						</li>
						<li class="mb5">Не забывайте про <b class="red">Опции</b></li>
						<li class="mb5">Доступен импорт выписок для 1С</li>
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

		$(function () {

			$('#dialog').css('width', '80%').center();

		});

		$(document).on('change', '#file', function () {

			//console.log(this.files);

			sfile = this.value;

			var ext = this.value.split(".");
			var elength = ext.length;
			var carrentExt = ext[elength - 1].toLowerCase();

			if (in_array(carrentExt, ['txt']))
				$('.next').removeClass('graybtn');

			else {

				sfile = '';
				Swal.fire('Только в формате TXT', '', 'warning');
				$('#file').val('');
				//$('.next').addClass('graybtn');

			}

		});

		$('#Form').ajaxForm({
			beforeSubmit: function () {

				var em = checkRequired();

				if (em === false) return false;

				return true;

			},
			success: function (data) {

				$('#resultdiv').empty().html(data);
				$('#dialog').css('width', '80%').center();

			}

		});

		function Next() {

			if ($('#content').val() !== '')
				$('#Form').submit();

			else
				Swal.fire('Внимание', 'Нет данных для импорта', 'warning');

		}

	</script>
	<?php

	exit();

}

// импорт расходов. шаг 1. Заморожено
if ( $action == 'import.bank.s2' ) {

	$data = $_REQUEST['content'];
	$rows = [];

	//require_once "../../inc/class/Budget.php";

	$headers = [
		"number"     => "№ документа",
		"datum"      => "Дата",
		"contragent" => "Контрагент",
		//"inn"        => "ИНН",
		//"kpp"        => "КПП",
		"innkpp"     => "ИНН",
		"bank"       => "Банк контрагента",
		"bik"        => "БИК корр.",
		"rs"         => "Счет корреспондента",
		"title"      => "Назначение платежа",
		"plus"       => "Приход",
		"minus"      => "Расход",
		"budjet"     => "Статья бюджета",
	];

	if ( $data ) {

		//строки собираем в массив
		$strings = explode( "\n", $data );

		//переберем элементы массива и каждую строку разобъем также на массивы
		foreach ( $strings as $string ) {
			if ($string != '') {
				$rows[] = explode("\t", $string);
			}
		}

	}

	if ( filesize( $_FILES['file']['tmp_name'] ) > 0 ) {

		$ext = getExtention( $_FILES['file']['name'] );

		//если загружается файл
		if ( in_array( $ext, ["txt"] ) ) {

			//require_once "../../inc/class/Upload.php";

			$upload = Upload ::upload();

			$err = array_merge( $err, $upload['message'] );

			foreach ( $upload['data'] as $file ) {

				$uploadfile = $rootpath.'/files/'.$fpath.$file['name'];

				//обрабатываем данные из файла
				$data = file_get_contents( $uploadfile );


			}

		}
		else {
			$err[] = "Разрешены только файлы TXT";
		}

	}

	$category = Budget ::getCategory();

	$category['dohod']['main'][0]['sub'][] = [
		'id'    => 0,
		'title' => 'Клиент'
	];

	?>
	<div class="zagolovok">Импорт журнала расходов. Шаг 2</div>
	<FORM method="post" action="/modules/finance/core.budjet.php" enctype="multipart/form-data" name="Form" id="Form">
		<input name="action" id="action" type="hidden" value="import.bank.s2.on">

		<div id="formtabs" class="bgwhite" style="overflow: auto; height: 70vh">

			<?php
			$tbody = '';

			// формируем строки таблицы
			foreach ( $rows as $key => $items ) {

				$s    = [];
				$t    = $inn = $kpp = '';
				$clid = 0;

				foreach ( $items as $k => $item ) {

					$s[] = '<td><input type="hidden" id="row['.$key.'][]">'.$item.'</td>';

					if ( $t == '' ) {

						/**
						 * Сопоставим данные с типом расходов, если это возможно
						 */
						$sitem = texttosmall( $item );

						// платеж от клиента
						if ( stripos( $sitem, 'счету №' ) !== false || stripos( $sitem, 'счет №' ) !== false ) {

							//попробуем разбить строку
							$innkpp = yexplode( ", ", $item );

							// строка содержит ИНН, КПП
							if ( count( $innkpp ) == 2 && strlen( $innkpp[0] ) == 10 ) {

								$inn = $innkpp[0];
								$kpp = $innkpp[1];

								// найдем плательщика по ИНН
								$clid = $db -> getOne( "SELECT clid FROM {$sqlname}clientcat WHERE clid > 0 and recv LIKE '%$inn%' AND identity = '$identity'" ) + 0;

								if ( $clid > 0 ) {
									$t = 'клиент';
								}

							}

						}

						// банковская комиссия
						if ( stripos( $sitem, 'комисси' ) !== false ) {
							$t = 'поставщик';
						}

						// зарплата
						if ( stripos( $sitem, 'заработная' ) !== false || stripos( $sitem, 'зарплат' ) !== false || stripos( $sitem, 'зпл' ) !== false ) {
							$t = 'зарплат';
						}

						// аренда офиса
						if ( stripos( $sitem, 'аренд' ) !== false ) {
							$t = 'аренда';
						}

						// платежи в налоговую, бюджет
						if ( stripos( $sitem, 'налог' ) !== false || stripos( $sitem, 'ифнс' ) !== false || stripos( $sitem, 'взнос' ) !== false || stripos( $sitem, 'ндфл' ) !== false || stripos( $sitem, 'уфк' ) !== false ) {
							$t = 'налог';
						}

					}

				}

				// формируем список статей бюджета
				$catSelect = Budget ::categorySelect( 'row['.$key.'][]', [
					"category" => $category,
					"word"     => $t,
					"class"    => "wp100"
				] );

				$tbody .= '
				<tr class="ha th40 ">
					'.implode( "\n", $s ).'
					<td>'.$catSelect.'</td>
				</tr>
				';

			}
			?>

			<table class="border-bottom">
				<thead class="sticked--top">
				<tr>
					<?php
					foreach ( $headers as $key => $head ) {

						$s = '';
						foreach ( $headers as $k => $h ) {
							$s .= '<option value="'.$k.'" '.( $k == $key ? "selected" : "" ).'>'.$h.'</option>';
						}

						print '
						<th '.($key == 'budjet' ? 'class="w160"' : '').'>
							<span class="select">
								<select name="head['.$key.']" id="head['.$key.']" class="wp100">'.$s.'</select>
							</span>
						</th>';

					}
					?>
				</tr>
				</thead>
				<tbody>
				<?= $tbody ?>
				</tbody>
			</table>

		</div>

		<hr>

		<div class="text-right button--pane">

			<a href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button next">Продолжить..</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<script>

		var sfile = '';

		$(function () {

			$('#dialog').css('width', '90%').center();

		});

		$('#Form').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif>Загрузка данных. Пожалуйста подождите...</div>');

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				return true;

			},
			success: function (data) {

				$('#dialog').css('display', 'none');
				$('#resultdiv').empty();
				$('#dialog_container').css('display', 'none');

				$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
			}

		});

	</script>
	<?php

	exit();

}
?>

<script>

	var $action = $('#action').val()

	if (!isMobile) {

		if ($('#formtabs').is('div')) {

			var hh = $('#dialog_container').actual('height') * 0.95;
			var hh2 = hh - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 50;

			if ($(window).width() > 990) {

				$('#dialog').css({'width': '700px'});
				$('#formtabs').css({'max-height': hh2});
			}
			else {
				$('#dialog').css('width', '80%');
				$('#formtabs').css('max-height', hh2);
			}

		}

	}
	else {

		var h1 = ($('#info').is('div')) ? $('#info').actual('outerHeight') : 0;
		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - h1 - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

		$(".multiselect").addClass('wp97 h0');

		if (isMobile) $('table').rtResponsiveTables();

	}

	$(function () {

		if ($action === 'export.invoices') {
			$('#dialog').css('width', '500px')
		}

		$.get('/modules/finance/fileview.php?id=<?=$id?>', function (data) {

			if (data !== '') {

				$('#filelist').html(data)
				$('.efiles').removeClass('hidden')
				$('#dialog').center()

			}

		})

		if (!isMobile) $(".inputdate").datepicker({
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 1
		})

		if (!isMobile) $("#dstart").datepicker({
			dateFormat: "yy-mm-dd",
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 2
		})
		if (!isMobile) $("#dend").datepicker({
			dateFormat: "yy-mm-dd",
			firstDay: 1,
			changeMonth: true,
			changeYear: true,
			numberOfMonths: 2
		})

		$(document).off('click', '#dtitle')
		$(document).on('click', '#dtitle', function () {

			$("#dtitle").autocomplete("/content/helpers/deal.helpers.php?action=doglist", {
				autofill: true,
				minChars: 2,
				cacheLength: 10,
				max: 30,
				selectFirst: false,
				multiple: false,
				delay: 500,
				matchSubset: 1,
				// extraParams: {clid: $('#clid').val()},
				formatItem: function (data, i, n, value) {
					return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
				},
				formatResult: function (data) {
					return data[0];
				}
			})
				.result(function (value, data) {

					$('#did').val(data[1]);
					//$('#clid').val(data[2]);

				});

		})

		$("#contragent").autocomplete('/content/helpers/client.helpers.php?action=clientlist&xtip='+ $('#tip').val()+'&tip='+ $('#xtip').val(), {
			autofill: false,
			minChars: 2,
			cacheLength: 2,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, j, n, value) {
				return '<div onclick="selItem(\'\',\'' + data[1] + '\')">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]<div class="gray fs-09">' + data[4] + '</div></div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {
				selItem('', data);
			})

		$('#dialog').center()

	});

	$('#form').ajaxForm({
		beforeSubmit: function () {

			var $out = $('#message');
			var em = checkRequired();

			if (em === false) return false;

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			$out.empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

			return true;

		},
		success: function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data)
			setTimeout(function () {
				$('#message').fadeTo(1000, 0)
			}, 20000)

			if (isCard) {

				settab("13", false)
				settab("cbudjet", false)

				//var url = '/content/card/card.provider.php?did=<?=$did?>';
				//$('#tab13').load(url).append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка. Пожалуйста подождите...</div>')

				setTimeout(function () {
					//settab(13, false)
					settab("0", false)
				}, 20000)

			}
			else {

				configpage()
				$("#stat").load('/modules/finance/stat.php')

			}

			DClose()

		}
	});

	$(document)
		.off('change', '#datum')
		.on('change', '#datum', function () {

			var date = $(this).val().split("-")

			$('#formtabse').find('#bmon').val(parseInt(date[1]))
			$('#formtabse').find('#byear').val(date[0])

		})

	if ($action === 'edit') {

		$(document)
			.off('click', '#do')
			.on('click', '#do', function () {
				if ( $('#id').val() !== '0' ) {

					if ($(this).prop("checked")) {

						$('.delta').removeClass('hidden')

					}
					else {

						$('.delta').addClass('hidden')
						$('#addNewRashodDelta').attr('checked', false)

					}

				}
			});

		$('#dialog').center()

	}

	$(document)
		.off('click', '.idel')
		.on('click', '.idel', function () {

		var count = $('#dialog input[type="file"]').size();

		if (count > 1) {
			$(this).closest('.filebox').remove();
		}
		else{
			$(this).closest('.filebox').find('.file').val('');
		}

		$('#dialog').center();

		return true;

	})

	$(document)
		.off('change', '#contragent')
		.on('change', '#contragent', function (){

			var clid = parseInt( $('#clid').val() )
			$('.addfileButton').removeClass('hidden')

		})

	function selItem(tip, data, title) {

		$("#clid").val(data[1]);

		if(data[3] === 'partner'){
			$('#partid').val(data[1]);
		}
		else if(data[3] === 'contractor'){
			$('#conid').val(data[1]);
		}

	}

	function addfile() {

		var kol = $('.filebox').size();
		var i = kol + 1;
		var htmltr = '<div id="file-' + i + '" class="filebox relativ wp100 mb5"><input name="file[]" type="file" class="file wp97" id="file[]"><div class="idel hand mr15" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('#dialog').find('.uploads').append(htmltr);
		$('#dialog').center();
	}

	function filedelete(id, fid) {

		var url = '/modules/finance/fileview.php?action=delete&id=' + id + '&fid=' + fid;

		$.get(url, function (data) {

			$('#filelist').load('/modules/finance/fileview.php?id=' + id);

			return false;
		});
	}

	function loadCardfiles(){

		var clid = parseInt( $('#clid').val() )

		if( clid > 0 ){

			var $elm = $('#subwindow')

			$elm.addClass('open front').empty().append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>')

			$.getJSON("/modules/finance/core.budjet.php?action=getFiles&clid=" + clid, function (viewData) {

				let template = $('#filesTpl').html()
				Mustache.parse(template)

				//console.log(template)

				let rendered = Mustache.render(template, viewData)
				$elm.empty().append(rendered)

			})
				.done(function (){

					$(document)
						.on('click', '.closer', function () {
							subwindowClose()
						})

					$('.zfile')
						.off('click')
						.on('click', function (){
							$('#fileholder').append($(this).clone()).html()
							$('#fileholder').find('.xdel').removeClass('hidden')
						})

				});

		}
		else{

			$('div[data-type="fileholder"]').empty()
			$('.addfileButton').addClass('hidden')

		}

	}

	function xfileDelete(id){

		$('#fileholder').find('.zfile[data-id="'+id+'"]').remove()

	}

	function onclearContragent(){
		$('div[data-type="fileholder"]').empty()
		$('.addfileButton').addClass('hidden')
	}

</script>