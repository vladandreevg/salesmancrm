<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\Akt;
use Salesman\Document;
use Salesman\Elements;

error_reporting(E_ERROR);

$rootpath = dirname( __DIR__, 2 );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$clid = $_REQUEST['clid'];
$pid  = $_REQUEST['pid'];
$did  = $_REQUEST['did'];
$dog  = $_REQUEST['dog'];

$action = $_REQUEST['action'];

// перенаправляем на новую форму
if($action == 'contract.add') {
	$action = 'contract.edit';
}

// перенаправляем на новую форму
if($action == 'akt.add') {
	$action = 'akt.edit';
}

$maxupload = $GLOBALS['maxupload'];

/**
 * Единая форма добавления/изменения документа
 */
if ($action == "contract.edit") {

	$deid = $_REQUEST['id'];

	$contract = [];
	$isDogovor = false;

	// редактиуем док
	if($deid > 0) {

		$contract      = $db -> getRow("select * from ".$sqlname."contract where deid = '$deid' and identity = '$identity'");

		if($contract['did'] > 0) {

			$deal = get_dog_info($contract['did'], "yes");

			$contract['payer'] = $deal["payer"];

			if (!$contract['clid']) {
				$contract['clid'] = $deal["clid"];
			}

			if (!$contract['pid']) {
				$contract['pid'] = $deal["pid"];
			}

			$contract['mcid'] = $deal["mcid"];

		}

		//print_r($contract);

	}

	// создаем новый
	else{

		$deid = 0;

		$contract['type']   = $_REQUEST['type'];
		$contract['idtype'] = (int)$_REQUEST['idtype'];
		$contract['clid']   = (int)$_REQUEST['clid'];
		$contract['payer']  = (int)$_REQUEST['payer'];
		$contract['pid']    = (int)$_REQUEST['pid'];
		$contract['did']    = (int)$_REQUEST['did'];

		$contract['datum_start'] = current_datum();
		$contract['datum_end'] = current_datum(-( date('L')?366:365 ));

		$contract['number'] = '';

		// если пришел idtype
		if ((int)$contract['idtype'] > 0) {

			$result = $db -> getRow("SELECT * FROM ".$sqlname."contract_type WHERE id = '$contract[idtype]' and identity = '$identity'");
			$contract['type']  = $result["type"];
			$contract['title'] = $result["title"];

		}

		// если пришел tipe
		if ($contract['type'] > 0) {

			$result = $db -> getRow("SELECT * FROM ".$sqlname."contract_type WHERE type = '$contract[type]' and identity = '$identity'");
			$contract['idtype'] = $result["id"];
			$contract['title'] = $result["title"];

		}

		if((int)$contract['did'] > 0) {

			$deal = get_dog_info($contract['did'], "yes");

			$contract['payer'] = $deal["payer"];

			if (!empty($contract['clid'])) {
				$contract['clid'] = $deal["clid"];
			}

			if (!empty($contract['pid'])) {
				$contract['pid'] = $deal["pid"];
			}

			$contract['mcid'] = $deal["mcid"];

		}

		//проверим, есть ли у сделки прикрепленный договор
		if ($contract['type'] == 'get_dogovor') {

			$isDogovor = true;

			$dog_num = $db -> getOne("SELECT dog_num FROM ".$sqlname."dogovor where did = '$contract[did]' and identity = '$identity'");
			$deid    = (int)$db -> getOne("SELECT deid FROM ".$sqlname."contract where deid = '$dog_num' and identity = '$identity'");

			if ($deid == 0) {
				$dog_num = '';
			}

		}
		else {
			$contract['number'] = genDocsNum( $contract['idtype'] );
		}

		if ( $deid > 0) {

			print '
			<div class="warning"><b class="red">Ошибка!</b> К сделке уже привязан документ <b>Договор</b></div>
			<hr>
			<div class="button-pane text-right">
				<a href="javascript:void(0)" onClick="DClose()" class="button">Закрыть</a>
			</div>
			';

			goto ext;

		}

	}

	$ctitle = $db -> getOne("SELECT title FROM ".$sqlname."contract_type where id = '$contract[idtype]' and identity = '$identity'");

	?>
	<DIV class="zagolovok">Изменение документа <?= $ctitle ?></DIV>
	<FORM action="/modules/contract/core.contract.php" method="post" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
		<INPUT type="hidden" name="action" id="action" value="contract.edit">
		<INPUT name="clid" type="hidden" id="clid" value="<?= $contract['clid'] ?>">
		<INPUT name="payer" type="hidden" id="payer" value="<?= $contract['payer'] ?>">
		<INPUT name="pid" type="hidden" id="pid" value="<?= $contract['pid'] ?>">
		<INPUT name="deid" type="hidden" id="deid" value="<?= $contract['deid'] ?>">
		<INPUT name="did" type="hidden" id="did" value="<?= $contract['did'] ?>">
		<INPUT name="idtype" type="hidden" id="idtype" value="<?= $contract['idtype'] ?>">

		<DIV id="formtabs" style="max-height:70vh; overflow-x: hidden; overflow-y:auto !important">

			<?php
			$hooks -> do_action( "document_form_before", $_REQUEST );
			?>

			<div class="flex-container mb5 mt20 box--child" data-id="number">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер:</div>
				<div class="flex-string wp80 pl10 relativ">

					<?php
					if ($contract['type'] == 'get_dogovor' && $contract['deid'] < 1) {

						if ($GLOBALS['contract_format'] == '') {

							print '<INPUT type="text" name="dnumber" id="dnumber" class="required wp97">';

						}
						else {

							$cnum = generate_num('contract');
							print '<div class="fs-11 pt7 infodiv wp97"><b class="red">'.$cnum.'</b>&nbsp;<b class="">(Предварительный). Будет присвоен после сохранения.</b></div>';

						}

					}
					elseif ($type != 'get_dogovor' && $contract['deid'] < 1) {

						if ($contract['number'] == '') {

							print '<INPUT type="text" name="dnumber" id="dnumber" class="required wp97">';

						}
						else {

							print '<div class="fs-11 pt7 infodiv wp97"><b class="red">№'.$contract['number'].'</b>&nbsp;<b class="">(Предварительный). Будет присвоен после сохранения.</b></div>';

						}

					}
					else {

						print '<INPUT name="dnumber" type="text" id="dnumber" class="required wp97" value="'.$contract['number'].'">';

					}
					?>

				</div>

			</div>

			<div class="flex-container mb5 mt10 box--child" data-id="title">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
				<div class="flex-string wp80 pl10 relativ">

					<INPUT type="text" name="title" id="title" value="<?= $contract['title'] ?>" style="width:97%">

				</div>

			</div>

			<?php
			/**
			 * Статус документа
			 */
			if((int)$contract['deid'] == 0) {

				$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$contract[idtype]', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");

				if (!empty($statuses)) {
					?>
					<div class="flex-container mb10 mt20 box--child" data-id="status">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус документа:</div>
						<div class="flex-string wp80 pl10 relativ">

							<select name="status" id="status" class="wp97">
								<?php
								foreach ($statuses as $da) {

									print '<option value="'.$da['id'].'" data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>
					<?php
				}

			}
			?>

			<div class="flex-container mb5 mt10 box--child" data-id="create">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата создания:</div>
				<div class="flex-string wp80 pl10 relativ">
					<INPUT type="text" name="datum_start" id="datum_start" class="w160 inputdate" value="<?= $contract['datum_start'] ?>">
				</div>

			</div>

			<div class="flex-container mb5 mt10 box--child" data-id="finish">


				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действителен до:</div>
				<div class="flex-string wp80 pl10 relativ">
					<INPUT type="text" name="datum_end" id="datum_end" class="w160 inputdate" value="<?= $contract['datum_end'] ?>">
				</div>

			</div>

			<?php
			$templates = Document::getTemplates([$contract['idtype']]);

			if (!empty($templates)) {
				?>
				<div class="flex-container mb5 mt10 box--child" data-id="template">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Шаблон
						<i class="icon-info-circled blue list" title="CRM может сгенерировать заполненный файл документа на основе шаблона. Вам надо просто выбрать нужный"></i>:
					</div>
					<div class="flex-string wp80 pl10 relativ">

						<select name="template" id="template" class="wp97">
							<option value="">--выбор--</option>
							<?php
							foreach ($templates[$contract['idtype']] as $id => $template) {
								print '<option value="'.$template['id'].'">'.$template['title'].'</option>';
							}
							?>
						</select>
						<div class="mt10 checkbox inline">
							<label>
								<input name="getPDF" type="checkbox" id="getPDF" value="yes">
								<span class="custom-checkbox"><i class="icon-ok"></i></span>
								&nbsp;Создать PDF&nbsp;
							</label>
						</div>

					</div>

				</div>
			<?php
			}
			?>

			<?php
			$mcount = (int)$db -> getOne("SELECT COUNT(*) FROM ".$sqlname."mycomps WHERE identity = '$identity'");
			// если документ создается из карточки Клиента
			if ($mcount > 1 && (int)$contract['did'] == 0) {
				?>

				<div class="flex-container mb5 mt10 box--child" data-id="company">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Компания
						<i class="icon-info-circled blue list" title="Реквизиты какой своей компании использовать?"></i>:
					</div>
					<div class="flex-string wp80 pl10 relativ">

						<?php
						$element = new Elements();
						print $element -> mycompSelect('mcid', [
							"class" => "wp97",
							"sel"   => $contract['mcid']
						]);
						?>

					</div>

				</div>

				<?php
			}
			elseif ($mcount == 1 && $contract['did'] < 1) {

				$mcid = $db -> getOne("SELECT id FROM ".$sqlname."mycomps WHERE identity = '$identity' ORDER BY id LIMIT 1");
				print '<INPUT name="mcid" type="hidden" id="mcid" value="'.$contract['mcid'].'">';

			}
			elseif ($contract['did'] > 0) {

				$mcid = $db -> getOne("SELECT mcid FROM ".$sqlname."dogovor where did = '$contract[did]' and identity = '$identity'");
				print '<INPUT name="mcid" type="hidden" id="mcid" value="'.$contract['mcid'].'">';

			}
			?>

			<?php
			$signers = getSigner( 0, (int)$contract['mcid'] );
			if(!empty($signers)){
				?>
				<div class="flex-container mb5 mt10 box--child" data-id="signer">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><span class="middle">Подписант:</span></div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="signer" id="signer" class="wp97">
							<option value="">--выбор--</option>
							<?php
							foreach ($signers as $xsigners) {

								foreach($xsigners as $xsigner) {

									print '<option value="'.$xsigner['id'].'" '.($contract['signer'] == $xsigner['id'] ? 'selected' : '').' data-mcid="'.$xsigner['mcid'].'">'.$xsigner['signature'].': '.$xsigner['status'].' ('.current_company((int)$xsigner['mcid']).')</option>';

								}

							}
							?>
						</select>
					</div>

				</div>
			<?php } ?>

			<div class="flex-container mb10 mt20 box--child" data-id="files">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Файлы:</div>
				<div class="flex-string wp80 pl10">

					<?php
					include $rootpath."/content/ajax/check_disk.php";
					if ($diskLimit == 0 || $diskUsage['percent'] < 100) {
						?>
						<div class="relativ like-input wp97 pt10 pb10 block">

							<div class="tagsmenuToggler hand mr5 pull-aright">
								<span class="fs-10 blue"><i class="icon-help-circled"></i> Подсказка</span>
								<div class="tagsmenu top right hidden" style="right: 10px">
									<div class="blok p10 w350">
										- Максимальный размер:&nbsp;<b><?php
											if ($maxupload == '')
												$maxupload = str_replace([
												'M',
												'm'
												], '', @ini_get('upload_max_filesize'));
											print "<b>".$maxupload." Mb</b>"; ?></b>;<br>
										- Разрешенные типы:&nbsp;<b><?= str_replace(",", ", ", $ext_allow) ?></b>;
									</div>
								</div>
							</div>

							<a href="javascript:void(0)" onclick="$('.filebox').toggleClass('hidden'); $('#dialog').center();" class="gray block" title="Прикрепить файлы">
								<i class="icon-attach-1 blue"></i> Прикрепить файлы
							</a>

							<div class="filebox wp97 hidden mt20">

								<div class="eupload relativ">
									<input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100">
									<div class="idel hand delbox" title="Очистить">
										<i class="icon-cancel-circled red"></i></div>
								</div>

							</div>

						</div>
						<?php
					}
					else print '<div class="warning wp97 text-center"><b class="red">Превышен лимит использования диска</b></div>';
					?>

				</div>

			</div>

			<div class="flex-container mb5 mt10 box--child" data-id="uploads">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Загруженные:</div>
				<div class="flex-string wp80 pl10">

					<div class="like-input wp97 block bluebg-sub p5" id="filelist" style="display: table !important;"></div>

				</div>

			</div>

			<div class="flex-container mb5 mt10 box--child" data-id="des">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Описание:</div>
				<div class="flex-string wp80 pl10 relativ">
					<TEXTAREA name="des" id="des" rows="3" class="wp97"><?= $contract['des'] ?></TEXTAREA>
				</div>

			</div>

			<?php
			if ($isDogovor && $contract['did'] > 0 && $contract['did'] > 0) {
				?>

				<div class="flex-container mb10 mt20 box--child" data-id="newstep">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый этап:</div>
					<div class="flex-string wp80 pl10 relativ">
						<?php
						$mFunnel = getMultiStepList(["did" => $contract['did']]);
						$ss      = (count($mFunnel['steps']) > 0) ? " and idcategory IN (".implode(",", array_keys($mFunnel['steps'])).")" : "";
						if (count($mFunnel['steps']) == 0) {

							$old_step = getDogData($contract['did'], 'idcategory');
							$step     = getPrevNextStep($old_step, 'next');

						}
						else {

							$old_step = $mFunnel['current']['id'];
							$step     = $mFunnel['next'];

						}
						?>
						<select name="newstep" id="newstep" class="wp97">
							<option value="<?= $old_step ?>">--Не менять--</option>
							<?php
							$result = $db -> getAll("SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' $ss ORDER BY title");
							foreach ($result as $data) {

								$s = ($data['title'] == $step['title']) ? 'selected' : '';
								$d = ($data['idcategory'] == $old_step) ? 'disabled' : '';

								?>
								<option value="<?= $data['idcategory'] ?>" <?= $s ?> <?= $d ?>><?= $data['title']."% -".$data['content'] ?></option>
							<?php } ?>
						</select>

					</div>

				</div>

				<div id="stepfields" class="viewdiv1 hidden mt20 mb20"></div>

				<?php
			}
			?>

			<div class="space-20"></div>

		</DIV>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose(); settab('15')" class="button">Отмена</A>

		</div>

	</FORM>
	<?php

	$hooks -> do_action( "document_form_after", $_REQUEST );

	ext:

}

// для подгрузки из сторонних форм
// кроме документов типа Договор
if ($action == "contract.add.extended") {

	$idtype = $_REQUEST['idtype'];
	$did    = $_REQUEST['did'];

	//заглушка
	//$idtype = 91;

	//шаблоны для данного типа документов
	$templates = $db -> getAll("SELECT * FROM ".$sqlname."contract_temp WHERE typeid = '$idtype' and identity = '$identity' ORDER BY title");

	$result = $db -> getRow("SELECT * FROM ".$sqlname."contract_type where id = '$idtype' and identity = '$identity'");
	$type   = $result["type"];
	$ctitle = $result["title"];

	//проверим, есть ли у сделки прикрепленный договор
	$number = genDocsNum($idtype);

	//если шаблонов нет, то выходим
	if(empty($templates))
		exit();
	?>

	<div class="flex-container mb5 mt20 box--child">

		<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Номер:</div>
		<div class="flex-string wp80 pl10 relativ">

			<?php
			if ($number == '')
				print '<INPUT type="text" name="doc[number]" id="doc[number]" class="required wp97">';

			else
				print '<div class="fs-11 pt7 infodiv wp97"><b class="red">№'.$number.'</b>&nbsp;<b class="">(Предварительный). Будет присвоен после сохранения.</b></div>';
			?>

		</div>

	</div>

	<?php
	/**
	 * Статус документа
	 */
	$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$idtype', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");

	if (!empty($statuses)) {
		?>
		<div class="flex-container mb10 mt20 box--child">

			<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус документа:</div>
			<div class="flex-string wp80 pl10 relativ">

				<select name="doc[status]" id="doc[status]" class="wp97">
					<?php
					foreach ($statuses as $da) {

						print '<option value="'.$da['id'].'" data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

					}
					?>
				</select>

			</div>

		</div>
		<?php
	}
	?>

	<div class="flex-container mb5 mt10 box--child">

		<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Название:</div>
		<div class="flex-string wp80 pl10 relativ">
			<INPUT type="text" name="doc[title]" id="doc[title]" class="required wp97" value="<?= $ctitle ?>">
		</div>

	</div>

	<div class="flex-container mb5 mt10 box--child">

		<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Дата создания:</div>
		<div class="flex-string wp80 pl10 relativ">
			<INPUT type="text" name="doc[datum_start]" id="doc[datum_start]" class="w160 inputdate" value="<?= current_datum(); ?>">
		</div>

	</div>

	<div class="flex-container mb5 mt10 box--child">

		<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Действителен до:</div>
		<div class="flex-string wp80 pl10 relativ">
			<INPUT type="text" name="doc[datum_end]" id="doc[datum_end]" class="w160 inputdate" value="<?= date('Y') ?>-12-31">
		</div>

	</div>

	<div class="flex-container mb5 mt10 box--child">

		<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Шаблон
			<i class="icon-info-circled blue list" title="CRM может сгенерировать заполненный файл документа на основе шаблона. Вам надо просто выбрать нужный"></i>:
		</div>
		<div class="flex-string wp80 pl10 relativ">

			<span class="select">
			<select name="doc[template]" id="doc[template]" class="wp97">
				<?php
				foreach ($templates as $template) {
					print '<option value="'.$template['id'].'" '.(count($templates) == 1 ? 'selected' : '').'>'.$template['title'].'</option>';
				}
				?>
			</select>
			</span>
			<div class="mt10 checkbox inline">
				<label>
					<input name="getPDF" type="checkbox" id="getPDF" value="yes">
					<span class="custom-checkbox"><i class="icon-ok"></i></span>
					&nbsp;Создать PDF&nbsp;
				</label>
			</div>

		</div>

	</div>

	<?php

}

if ($action == "contract.status") {

	$deid = $_REQUEST['id'];

	$result      = $db -> getRow("select * from ".$sqlname."contract where deid='$deid' and identity = '$identity'");
	$datum_start = $result["datum_start"];
	$datum_end   = $result["datum_end"];
	$number      = $result["number"];
	$des         = $result["des"];
	$clid        = $result["clid"];
	$payer       = $result["payer"];
	$pid         = $result["pid"];
	$did         = $result["did"];
	$title       = $result["title"];
	$idtype      = $result["idtype"];
	$mcid        = $result["mcid"];
	$status      = $result["status"];

	$ctitle = $db -> getOne("SELECT title FROM ".$sqlname."contract_type where id = '$idtype' and identity = '$identity'");

	?>
	<DIV class="zagolovok">Изменение статуса документа<span class="hidden-iphone"> <?= $ctitle ?></span></DIV>
	<FORM action="/modules/contract/core.contract.php" method="post" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
		<INPUT type="hidden" name="action" id="action" value="contract.status">
		<INPUT name="deid" type="hidden" id="deid" value="<?= $deid ?>">

		<DIV id="formtabs" style="max-height:70vh; overflow-x: hidden; overflow-y:auto !important">

			<?php
			/**
			 * Статус документа
			 */
			$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$idtype', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");

			if (count($statuses) > 0) {
				?>
				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><label for="status">Статус документа:</label></div>
					<div class="flex-string wp80 pl10 relativ">

						<select name="status" id="status" class="wp97 required">
							<option value="">--не менять--</option>
							<?php

							$oldStatus = $status;//текущий статус документа
							$changed   = false;//признак, что статус мы еще не меняли

							//если статус не был установлен ранее, то выбираем первый
							if ($oldStatus < 1) {

								$status  = $statuses[0]['id'];
								$changed = true;//ставим признак, что статус уже поменяли

							}

							foreach ($statuses as $i => $da) {

								$s = ($status == $da['id']) ? "selected" : "";
								$d = ($oldStatus == $da['id']) ? "disabled" : "";

								print '<option value="'.$da['id'].'" '.$s.' '.$d.' data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

								if ($status == $da['id'] && $changed == false) {
									$status  = $statuses[ $i + 1 ]['id'];
									$changed = true;//ставим признак, что статус уже поменяли
								}

							}
							?>
						</select>

					</div>

				</div>
				<?php
			}
			?>

			<div class="flex-container mb10 mt20 box--child">

				<div class="flex-string wp20 gray2 fs-12 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10">

					<textarea name="description" rows="4" id="description" class="wp97 required"></textarea>
					<div class="gray2 fs-09 em">Укажите причину смены статуса документа</div>

				</div>

			</div>

		</DIV>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose(); settab('15')" class="button">Отмена</A>

		</div>

	</FORM>
	<?php
}
if ($action == "contract.view") {

	$deid = $_REQUEST['id'];

	$da = $db -> getRow("select * from ".$sqlname."contract WHERE deid='".$deid."' and identity = '$identity'");

	if ($da['datum_end'] == "0000-00-00") $da['datum_end'] = "__";
	if ($da['payer'] > 0) {
		$payerr = '<a href="javascript:void(0)" onclick="openClient(\''.$da['payer'].'\')" title="Плательщик"><i class="icon-building broun"></i>'.current_client($da['payer']).'</a>';
	}
	?>
	<div class="zagolovok">Просмотр документа</div>

	<DIV id="formtabs" style="max-height:70vh; overflow-x: hidden; overflow-y:auto !important">

		<div class="flex-container mb5 mt10 box--child">

			<div class="flex-string wp20 gray2 right-text">Документ:</div>
			<div class="flex-string wp80 pl10 Bold"><?= $da['title'] ?> № <?= $da['number'] ?></div>

		</div>

		<?php
		$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$da[idtype]', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");

		$status = $db -> getRow("SELECT color, title FROM ".$sqlname."contract_status WHERE id = '$da[status]' and identity = '$identity'");

		if ($status['title'] == '') {
			$status['title'] = '<span class="red">Не установлен</span>';
			$status['color'] = '#ccc';
		}

		if (count($statuses) > 0) {
			?>
			<div class="flex-container mb5 mt10 box--child">

				<div class="flex-string wp20 gray2 right-text">Статус документа:</div>
				<div class="flex-string wp80 pl10 relativ">

					<div class="colordiv" style="background-color:<?= $status['color'] ?>"></div>&nbsp;<?= $status['title'] ?>
					<a href="javascript:void(0)" onClick="editContract('<?= $da['deid'] ?>','contract.status')" class="pull-aright gray"><?= $lang['all']['Edit'] ?></a>
					<?php
					$re = $db -> getAll("
					SELECT 
						DATE_FORMAT(".$sqlname."contract_statuslog.datum, '%d.%m.%Y %H:%s') as datum,
						".$sqlname."contract_statuslog.des as des,
						".$sqlname."contract_status.title as status,
						".$sqlname."contract_status.color as color
					FROM ".$sqlname."contract_statuslog 
						LEFT JOIN ".$sqlname."contract_status ON ".$sqlname."contract_status.id = ".$sqlname."contract_statuslog.status
					WHERE 
						".$sqlname."contract_statuslog.deid = '$deid' and 
						".$sqlname."contract_statuslog.identity = '$identity' 
					ORDER BY ".$sqlname."contract_statuslog.datum DESC
				");

					if (count($re) > 0) {
						?>

						<div class="tagsmenuToggler hand mr15 pull-aright relativ" data-id="fhelper">
							<span class="fs-10 blue"><i class="icon-help-circled"></i> Лог</span>
							<div class="tagsmenu fly right hidden" id="fhelper" style="right:0; top: 100%">
								<div class="blok p10 w350">
									<?php
									foreach ($re as $stat) {

										print '
									<div class="flex-container border-bottom fs-09 mt5">
										<div class="flex-string wp25">'.$stat['datum'].'</div>
										<div class="flex-string wp75">
											<div class="Bold fs-11 ellipsis"><div class="colordiv" style="background-color:'.$stat['color'].'"></div>&nbsp;'.$stat['status'].'</div>
											<div class="gray2 fs-09 em">'.$stat['des'].'</div>
										</div>
									</div>
									';

									}
									?>
								</div>
							</div>
						</div>

					<?php }
					?>

				</div>

			</div>

			<hr>
		<?php } ?>

		<?php if ($payerr != '') { ?>
			<div class="flex-container mb5 mt10 box--child">

				<div class="flex-string wp20 gray2 right-text">Плательщик:</div>
				<div class="flex-string wp80 pl10 relativ"><?= $payerr ?></div>

			</div>
		<?php } ?>

		<?php if ($da['clid'] > 0) { ?>
			<div class="flex-container mb5 mt10 box--child">

				<div class="flex-string wp20 gray2 right-text">Заказчик:</div>
				<div class="flex-string wp80 pl10 relativ">
					<a href="javascript:void(0)" onclick="openClient('<?= $da['clid'] ?>')" title="Плательщик"><i class="icon-building blue"></i><?= current_client($da['clid']) ?>
					</a></div>

			</div>
		<?php } ?>

		<div class="flex-container mb5 mt10 box--child">

			<div class="flex-string wp20 gray2 right-text">Подписан:</div>
			<div class="flex-string wp80 pl10 relativ"><?= format_date_rus_name($da['datum_start']) ?>&nbsp;года</div>

		</div>

		<?php if ($da['datum_end'] != "0000-00-00") { ?>
			<div class="flex-container mb5 mt10 box--child">

				<div class="flex-string wp20 gray2 right-text">Действует до:</div>
				<div class="flex-string wp80 pl10 relativ"><?= format_date_rus_name($da['datum_end']) ?>&nbsp;года</div>

			</div>
		<?php } ?>

		<?php if ($da['des'] != "") { ?>
			<div class="flex-container mb10 mt10 box--child">

				<div class="flex-string wp20 gray2 right-text">Описание:</div>
				<div class="flex-string wp80 pl10 relativ"><?= $da['des'] ?></div>

			</div>
		<?php } ?>

		<?php
		$ftitle = yexplode(";", $da['ftitle']);
		$fname  = yexplode(";", $da['fname']);
		$last   = count($ftitle) - 1;

		if (!empty($ftitle)) {
			?>

			<hr>

			<div class="flex-container mb5 mt10 box--child">

				<div class="flex-string wp20 gray2 right-text">Файлы:</div>
				<div class="flex-string wp80 pl10">

					<?php
					for ( $i = 0, $iMax = count( $ftitle ); $i < $iMax; $i++) {

						print '
						<div class="row mb5 viewdiv fs-09 box--child">
							<div class="column grid-2 wp10 gray2">'.get_icon2($ftitle[ $i ]).'</div>
							<div class="column grid-8 wp90">
								<A href="javascript:void(0)" onclick="fileDownload(\'\',\''.$fname[ $i ].'\',\'yes\',\''.$ftitle[ $i ].'\')" title="Скачать">'.$ftitle[ $i ].'</A>
								&nbsp;['.num_format(filesize($rootpath."/files/".$fpath.$fname[ $i ]) / 1000).' kb.]
							</div>
						</div>
						';

					}
					?>

				</div>

			</div>

		<?php } ?>

	</DIV>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="DClose();" class="button">Закрыть</A>

	</div>
	<?php
}

// объединенная форма
if ($action == "akt.edit") {

	$did  = $_REQUEST['did'];
	$deid = $_REQUEST['deid'];

	$akt = [];

	if($deid > 0) {

		$result   = $db -> getRow("SELECT * FROM ".$sqlname."contract WHERE deid='".$deid."' and identity = '$identity'");
		$template     = yexplode(".", $result["title"], 0);
		$akt_date = get_smdate($result["datum"]);
		$akt_num  = $result["number"];
		$crid     = $result["crid"];
		$des      = $result["des"];
		$signer   = $result["signer"];

		$isper = (isServices($did)) ? 'yes' : '';

		// комплектность актами
		$complect = round(Akt::getAktComplect($did), 0);
		$aktComplect = !( $isper || $complect < 100 );

	}
	else{

		$deid = 0;
		$akt_date = current_datum();
		$akt_num = generate_num("akt");

		$count = $db -> getOne("SELECT COUNT(*) as count FROM ".$sqlname."contract WHERE did = '$did' and idtype IN (SELECT id FROM ".$sqlname."contract_type WHERE type = 'get_akt' and identity = '$identity') and identity = '$identity'");

		//этап, с которого можно печатать акт
		$stepApprove = current_dogstepname($GLOBALS['akt_step']);
		$stepCurrent = current_dogstepname(getDogData($did, 'idcategory'));

		$isper = (isServices($did)) ? 'yes' : "";

		$mcid = getDogData($did, 'mcid');

		if ($isper == 'yes') {

			$result     = $db -> getRow("SELECT idcategory, mcid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'");
			$idcategory = $result["idcategory"];
			$mcid       = $result["mcid"];

			$kol   = $db -> getOne("SELECT SUM(price * kol) as kol FROM ".$sqlname."speca WHERE did = '$did' and identity = '$identity'");
			$count = 0;

			$dealFperiodStart = format_date_rus(getDogData($did, 'datum_start'));
			$dealFperiodEnd   = format_date_rus(getDogData($did, 'datum_end'));

			$des = "Акт составлен за период с $dealFperiodStart по $dealFperiodEnd";

			$idtype = $db -> getOne("SELECT id FROM ".$sqlname."contract_type WHERE type = 'get_aktper' and identity = '$identity'");

		}
		else {

			$idtype = $db -> getOne("SELECT id FROM ".$sqlname."contract_type WHERE type = 'get_akt' and identity = '$identity'");

		}

		$template = yexplode(".", ($isper == 'yes' ? $otherSettings['aktTempService'] : $otherSettings['aktTemp']), 0);

		// комплектность актами
		$complect = round(Akt::getAktComplect($did), 0);
		$aktComplect = !( $isper || $complect < 100 );

		// если сделка еще не дошла до заданного этапа
		if (!$aktComplect && $stepCurrent < $stepApprove) {

			print '
			<div class="zagolovok">Изменить Акт</div>
			<div class="warning">
				<b class="red">Ошибка!</b> Акт можно сформировать начиная с этапа <b class="red">'.$stepApprove.'%</b>
			</div>
			<div class="text-right">
				<hr><a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>
			</div>
			';

			exit();

		}

		// если сделка НЕ сервисная и акт уже есть
		if ($aktComplect){

			print '
			<div class="zagolovok">Изменить Акт</div>
			<div class="warning"><b class="red">Ошибка!</b> Акт приема-передачи уже есть по этой сделке</div>
			<div class="text-right">
				<hr><a href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</a>
			</div>
			';

			exit();

		}

	}

	?>
	<div class="zagolovok">Изменить Акт</div>
	<form method="post" action="/modules/contract/core.contract.php" enctype="multipart/form-data" name="uploadForm" id="uploadForm" autocomplete="off">
		<input type="hidden" id="did" name="did" value="<?= $did ?>">
		<input type="hidden" id="deid" name="deid" value="<?= $deid ?>">
		<input type="hidden" id="action" name="action" value="akt.edit">

		<div id="formtabs" class="box--child wp100" style="max-height:80vh; overflow-y:auto !important; overflow-x:hidden">

			<?php
			$hooks -> do_action( "akt_form_before", $_REQUEST );
			?>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 pt7 fs-12 right-text">Дата:</div>
				<div class="flex-string wp80 pl10">
					<INPUT name="datum" type="text" id="datum" class="inputdate w160" value="<?= $akt_date ?>">
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 pt7 fs-12 right-text">Номер акта:</div>
				<div class="flex-string wp80 pl10">
					<?php
					if($deid > 0) {
						?>
						<INPUT name="akt_num" type="text" id="akt_num" readonly="readonly" class="gray w120" value="<?= $akt_num ?>">
						<i class="icon-info-circled blue" title="Номер изменить нельзя"></i>
						<?php
					}
					else {
						?>
						<INPUT name="akt_num" type="text" id="akt_num" class="w120" value="<?= $akt_num ?>">&nbsp;
						<label title="Номер акта будет присвоен автоматически" class="inline"><input name="igen" id="igen" type="checkbox" value="yes" checked="checked"/>&nbsp;авто.</label>
						<br>
						<span class="gray2 em fs-09">(Предварительный). Будет присвоен после сохранения.</span>
						<?php
					}
					?>
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Шаблон:</div>
				<div class="flex-string wp80 pl10">
					<select name="temp" id="temp" class="required wp95">
						<?php
						$ires = $db -> query("SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title");
						while ($data = $db -> fetch($ires)) {

							print '<option value="'.$data['file'].'" '.($template == yexplode(".", $data['file'], 0) ? 'selected' : '').'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>

			<?php
			$signers = getSigner( 0, (int)$mcid );
			if(!empty($signers)){
				?>
				<div class="flex-container mb5 mt10 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text"><span class="middle">Подписант:</span></div>
					<div class="flex-string wp80 pl10 relativ">
						<select name="signer" id="signer" class="wp95">
							<option value="">--выбор--</option>
							<?php
							foreach ($signers as $xsigners) {

								foreach($xsigners as $xsigner) {

									print '<option value="'.$xsigner['id'].'" '.($signer == $xsigner['id'] ? 'selected' : '').' data-mcid="'.$xsigner['mcid'].'">'.$xsigner['signature'].': '.$xsigner['status'].' ('.current_company((int)$xsigner['mcid']).')</option>';

								}

							}
							?>
						</select>
					</div>

				</div>
			<?php } ?>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt20 mt10 right-text">Комментарий:</div>
				<div class="flex-string wp80 pl10">
					<div class="pull-aright mr20 mb10">
						<a href="javascript:void(0)" title="Действия" onclick="$('.tagsmenu[data-id=\'a423\']').toggleClass('hidden')"><b class="blue">Вставить тэг</b>&nbsp;<i class="icon-angle-down" id="mapii"></i></a>
						<div class="tagsmenu hidden" data-id="a423" style="max-height: 200px; right: 20px;">
							<ul>
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
								<li title="ФИО руководителя (В контексте «в лице кого»)">
									<b class="red">{{compDirName}}</b></li>
								<li title="Должность руководителя (Директор, Генеральный директор)">
									<b class="red">{{compDirStatus}}</b></li>
								<li title="Должность руководителя (краткая, Петров И.И.)">
									<b class="red">{{compDirSignature}}</b></li>
								<li title="На основании чего действует руководитель (Устава, Доверенности..)">
									<b class="red">{{compDirOsnovanie}}</b></li>
								<li title="Название Бренда"><b class="red">{{compBrand}}</b></li>
								<li title="Сайт Бренда"><b class="red">{{compSite}}</b></li>
								<li title="Email Бренда"><b class="red">{{compMail}}</b></li>
								<li title="Телефон Бренда"><b class="red">{{compPhone}}</b></li>

								<li title="Название Клиента (Как отображается в CRM)"><b class="blue">{{castName}}</b>
								</li>
								<li title="Юридическое название Клиента (из реквизитов)">
									<b class="blue">{{castUrName}}</b></li>
								<li title="ИНН Клиента (из реквизитов)"><b class="blue">{{castInn}}</b></li>
								<li title="КПП Клиента (из реквизитов)"><b class="blue">{{castKpp}}</b></li>
								<li title="Банк Клиента (из реквизитов)"><b class="blue">{{castBank}}</b></li>
								<li title="Кор.счет Клиента (из реквизитов)"><b class="blue">{{castBankKs}}</b></li>
								<li title="Расч.счет Клиента (из реквизитов)"><b class="blue">{{castBankRs}}</b></li>
								<li title="БИК банка Клиента (из реквизитов)"><b class="blue">{{castBankBik}}</b></li>
								<li title="ОКПО Клиента (из реквизитов)"><b class="blue">{{castOkpo}}</b></li>
								<li title="ОГРН Клиента (из реквизитов)"><b class="blue">{{castOgrn}}</b></li>
								<li title="ФИО руководителя Клиента, в родительном падеже (в лице кого) - Иванова Ивана Ивановича (из реквизитов)">
									<b class="blue">{{castDirName}}</b></li>
								<li title="ФИО руководителя Клиента, например Иванов И.И. (из реквизитов)">
									<b class="blue">{{castDirSignature}}</b></li>
								<li title="Должность руководителя Клиента, в род.падеже, например: Директора (из реквизитов)">
									<b class="blue">{{castDirStatus}}</b></li>
								<li title="Должность руководителя Клиента, например: Директор (из реквизитов)">
									<b class="blue">{{castDirStatusSig}}</b></li>
								<li title="Основание прав Руководителя Клиента, в родительном падеже - Устава, Доверенности №ХХХ от ХХ.ХХ.ХХХХ г. (из реквизитов)">
									<b class="blue">{{castDirOsnovanie}}</b></li>
								<li title="Юр.адрес Клиента (из реквизитов)"><b class="blue">{{castUrAddr}}</b></li>
								<li title="Фактич.адрес Клиента (из реквизитов)"><b class="blue">{{castFacAddr}}</b>
								</li>

								<li title="Заказчик. Название (Как отображается в CRM)">
									<b class="blue">{{castomerFtitle}}</b></li>
								<li title="Заказчик. Адрес"><b class="blue">{{castomerFaddress}}</b></li>
								<li title="Заказчик. Телефон"><b class="blue">{{castomerFphone}}</b></li>
								<li title="Заказчик. Факс"><b class="blue">{{castomerFfax}}</b></li>
								<li title="Заказчик. Email"><b class="blue">{{castomerFmail_url}}</b></li>
								<li title="Заказчик. Сайт"><b class="blue">{{castomerFsite_url}}</b></li>

								<?php
								$re = $db -> getAll("select * from ".$sqlname."field where fld_tip='client' and fld_on='yes' and fld_name LIKE '%input%' and identity = '".$identity."' order by fld_order");
								foreach ($re as $d) {

									print '<li title="Заказчик. '.$d['fld_title'].'"><b class="blue">{{castomerF'.$d['fld_name'].'}}</b></li>';

								}
								?>

								<li title="Номер счета (из сделки)"><b class="green">{{Invoice}}</b></li>
								<li title="Дата счета (в формате: 29 февраля 2014 года)">
									<b class="green">{{InvoiceDate}}</b></li>
								<li title="Дата счета (в формате: 29.02.2014)"><b class="green">{{InvoiceDateShort}}</b>
								</li>
								<li title="Дата оплаты плановая (в формате: 29 февраля 2014 года)">
									<b class="green">{{InvoiceDatePlan}}</b></li>
								<li title="Дата оплаты плановая (в формате: 29.02.2014)">
									<b class="green">{{InvoiceDatePlanShort}}</b></li>
								<!--<li title="Номер акта (из сделки)"><b class="green">{{AktNumber}}</b></li>-->
								<!--<li title="Дата акта (из сделки)"><b class="green">{{AktDate}}</b></li>-->
								<li title="Сумма прописью (сумма сделки)"><b class="green">{{InvoiceSummaPropis}}</b>
								</li>
								<li title="Общая сумма сделки (из сделки)"><b class="green">{{InvoiceSumma}}</b></li>
								<li title="Сумма позиций счета (из счета). При налоге 'сверху' не включает налог">
									<b class="green">{{ItogSumma}}</b></li>
								<!--<li title="Сумма НДС (из сделки)"><b class="green">{{summa_nds}}</b></li>-->
								<li title="Сумма НДС (из сделки)"><b class="green">{{nalogSumma}}</b></li>
								<li title="Название налога (например, в т.ч. НДС)"><b class="green">{{nalogName}}</b>
								</li>
								<li title="Название налога (например, НДС)"><b class="green">{{nalogTitle}}</b></li>
								<li title="Номер договора (из сделки)"><b class="green">{{ContractNumber}}</b></li>
								<li title="Дата договора (из сделки)"><b class="green">{{ContractDate}}</b></li>
								<li title="Название сделки"><b class="green">{{dealFtitle}}</b></li>
								<li title="Сумма сделки"><b class="green">{{dealFsumma}}</b></li>
								<li title="Маржа сделки"><b class="green">{{dealFmarga}}</b></li>
								<?php
								$res = $db -> getAll("select * from ".$sqlname."field where fld_tip='dogovor' and fld_name LIKE '%input%' and fld_on='yes' and identity = '".$GLOBALS['identity']."' order by fld_order");
								foreach ($res as $data) {

									print '<li title="'.$data['fld_title'].'"><b class="green">{{dealF'.$data['fld_name'].'}}</b></li>';

								}
								?>
								<li title="Период. Начало (из сделки)"><b class="green">{{dealFperiodStart}}</b></li>
								<li title="Период. Конец (из сделки)"><b class="green">{{dealFperiodEnd}}</b></li>
							</ul>
						</div>
					</div>
					<textarea name="des" id="des" rows="3" class="wp95"><?= $des ?></textarea>
					<div class="smalltxt blue">Комментарий может быть добавлен в акт через тэг <b>{{aktComment}}</b></div>
				</div>

			</div>

			<?php
			/**
			 * Статус документа. Только для новых актов
			 */
			if($deid == 0) {

				$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$idtype', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");
				if (count($statuses) > 0) {
					?>
					<div class="flex-container mb10 mt20 box--child">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус документа:</div>
						<div class="flex-string wp80 pl10 relativ">

							<select name="status" id="status" class="wp95">
								<?php
								foreach ($statuses as $da) {

									print '<option value="'.$da['id'].'" data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

								}
								?>
							</select>

						</div>

					</div>
					<?php
				}

			}

			if ($isper == 'yes' && $deid > 0) {
				?>
				<div class="flex-container box--child mt10 mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Акт к счету:</div>
					<div class="flex-string wp80 pl10">
						<select name="crid" id="crid" class="wp95">
							<OPTION value="">--не выбран--</OPTION>
							<?php
							$result = $db -> query("SELECT * FROM ".$sqlname."credit WHERE did = '$did' and identity = '$identity'");
							while ($data = $db -> fetch($result)) {

								$datum = yexplode(" ", $data['datum'], 0);

								print '<option value="'.$data['crid'].'" '.($crid == $data['crid'] ? 'selected' : '').'>Счет №'.$data['invoice'].' от '.get_date($datum).'</option>';

							}
							?>
						</select>
					</div>

				</div>
			<?php
			}
			if ($isper == 'yes' && $deid == 0) {

				//найдем последний не оплаченный счет
				$crid = $db -> getOne("SELECT MAX(crid) FROM ".$sqlname."credit WHERE did = '$did' and identity = '$identity'");
				?>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Сумма акта:</div>
					<div class="flex-string wp80 pl10 norelativ">
						<INPUT name="summa" type="text" id="summa" class="w100" value="<?= num_format($kol) ?>">&nbsp;<?= $valuta ?>&nbsp;<i class="icon-info-circled blue list" title="На основе спецификации"></i>
					</div>

				</div>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Акт к счету:</div>
					<div class="flex-string wp80 pl10 norelativ">

						<select name="crid" id="crid" class="wp95 required">
							<OPTION value="">--не выбран--</OPTION>
							<?php
							//выбираем те счета, которые не привязаны к актам
							$result = $db -> query("SELECT * FROM ".$sqlname."credit WHERE did = '$did' and crid NOT IN (SELECT crid FROM ".$sqlname."contract WHERE crid > 0 and identity = '$identity') and identity = '$identity'");
							while ($data = $db -> fetch($result)) {

								$datum = format_date_rus(get_smdate($data['datum']));
								$dd    = ($crid == $data['crid'] ? 'selected' : '');

								$s = ($data['do'] != 'on' ? ' [ не оплачен ]' : '');
								$c = ($data['do'] != 'on' ? 'redbg' : '');
								$x = ($data['do'] != 'on' ? '1' : '0');

								print '<option value="'.$data['crid'].'" '.$dd.' class="'.$c.'" data-isdo="'.$x.'">Счет №'.$data['invoice'].' от '.$datum.$s.'</option>';

							}
							?>
						</select>

					</div>

				</div>

				<?php
				//Вот это пока спорная опция. Не реализовано
				?>
				<div class="flex-container box--child mt10 hidden">

					<div class="flex-string wp20"></div>
					<div class="flex-string wp80 pl10">

						<label for="doInvoice" class="switch">
							<input type="checkbox" name="doInvoice" id="doInvoice" value="yes">
							<span class="slider empty"></span>
						</label>
						<label class="inline" for="doInvoice"><span class="text Bold gray2 ml10">Отметить счет оплаченным</span></label>

					</div>

				</div>

				<hr class="m0 mt20">

				<div class=" bgbluelight no-border pt10 pb10">

					<div class="flex-container box--child mt20">

						<div class="flex-string wp20 gray2 pt7 fs-12 right-text">Расчетный счет:</div>
						<div class="flex-string wp80 pl10 norelativ">

							<select name="rs" id="rs" class="required wp95">
								<option value="">--выбор--</option>
								<?php
								$result = $db -> query("SELECT * FROM ".$sqlname."mycomps WHERE id = '$mcid' and identity = '$identity' ORDER BY name_shot");
								while ($data = $db -> fetch($result)) {
									?>
									<optgroup label="<?= $data['name_shot'] ?>">
										<?php
										$res = $db -> query("SELECT * FROM ".$sqlname."mycomps_recv WHERE cid = '".$data['id']."' and identity = '$identity' ORDER BY title");
										while ($da = $db -> fetch($res)) {

											print '<option value="'.$da['id'].'" '.($da['isDefault'] == 'yes' ? 'selected' : '').'>'.$da['title'].'</option>';

										}
										?>
									</optgroup>
								<?php } ?>
							</select>

						</div>

					</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 pl10 norelativ">
							<label><input type="checkbox" name="newinvoice" id="newinvoice" checked="checked" value="yes">Выставить новый счет</label>
						</div>

					</div>

					<?php
					$p = getPeriodDeal((int)$did);

					if ($otherSettings['changeDealPeriod'] == 'akt') {

						$ch = 'checked';
						$hh = '';
						$hg = '';

					}
					else {

						$ch = '';
						$hh = 'hidden';
						$hg = 'hidden';

					}
					?>

					<div class="flex-container box--child mt10 <?= $hg ?>">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 pl10 norelativ">
							<label><input type="checkbox" name="changePeriod" id="changePeriod" <?= $ch ?> value="yes" onclick="$('#per').toggleClass('hidden'); $('#dialog').center()">Изменить период сделки</label>
						</div>

					</div>

					<div class="flex-container box--child mt10 <?= $hh ?>" id="per">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 norelativ">

							<div class="flex-container p10">

								<div class="flex-string wp30">
									<input type="text" name="dstart" class="required inputdate wp97" id="dstart" value="<?= $p[0] ?>">
									<div class="em gray2 fs-07">Начало периода</div>
								</div>

								<div class="flex-string wp30">
									<input type="text" name="dend" class="required inputdate wp97" id="dend" value="<?= $p[1] ?>">
									<div class="em gray2 fs-07">Конец периода</div>
								</div>

								<div class="flex-string wp40"></div>

							</div>

						</div>

					</div>

					<div class="flex-container box--child mt10">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text"></div>
						<div class="flex-string wp80 pl10 norelativ bgbluelight no-border p10">

							<span><i class="icon-info-circled blue icon-2x pull-left"></i></span>На указанную сумму будет составлен счет и акт. Сумма сделки будет увеличена на сумму акта.

						</div>

					</div>

				</div>

				<?php
			}
			if ($isper != 'yes' && $deid == 0) {
				?>

				<div class="flex-container box--child mt10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Новый этап:</div>
					<div class="flex-string wp80 pl10 norelativ">
						<?php
						$mFunnel = getMultiStepList(array("did" => $did));
						$ss      = (count($mFunnel['steps']) > 0) ? " and idcategory IN (".implode(",", array_keys($mFunnel['steps'])).")" : "";
						if (count($mFunnel['steps']) == 0) {

							$oldStep = getDogData($did, 'idcategory');
							$newStep = getPrevNextStep($old_step, 'next');

						}
						else {

							$oldStep = $mFunnel['current']['id'];
							$newStep = $mFunnel['next'];

						}
						?>
						<select name="newstep" id="newstep" class="wp95">
							<option value="<?= $oldStep ?>">--Не менять--</option>
							<?php
							$result = $db -> query("SELECT * FROM ".$sqlname."dogcategory WHERE identity = '$identity' $ss ORDER BY title");
							while ($data = $db -> fetch($result)) {

								$s = ($data['title'] == $newStep['title']) ? 'selected' : '';
								$d = ($data['idcategory'] == $oldStep) ? 'disabled' : '';

								print '<option value="'.$data['idcategory'].'" '.$s.' '.$d.'>'.$data['title']."%-".$data['content'].'</option>';

							}
							?>
						</select>
					</div>

				</div>

				<div id="stepfields" class="viewdiv hidden mt10 mb20"></div>

			<?php
			}
			?>
			<?php
			if ($isper != 'yes'){
			?>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt20 mt10 right-text"></div>
				<div class="flex-string wp80 pl10">

					<div class="checkbox">

						<label for="pozitions" class="block">
							<input name="pozitions" type="checkbox" id="pozitions" value="yes">
							<span class="custom-checkbox"><i class="icon-ok"></i></span>
							&nbsp;Частичная отгрузка
							&nbsp;<i class="icon-info-circled blue" title="Включает возможность создавать спецификацию. Данные берутся на основе спецификации."></i>
						</label>

					</div>

				</div>

			</div>

			<div class="mt10 mb10 hidden" id="pozition">

				<div class="divider">Позиции спецификации</div>

				<div class="p10" data-id="poz"></div>

			</div>
			<?php } ?>

		</div>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button">Сохранить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>
	</form>

	<!--шаблон списка позиций-->
	<script id="spekatemplate" type="x-tmpl-mustache" class="hidden">

		{{resetIndex}}
		{{#list}}
		{{setUpIndex}}
		<div class="flex-container box--child float mb5">

			<div class="flex-string float like-input pl5 pr5 pt10">

				<input type="hidden" id="[{{getIndex}}]speka[id]" name="speka[{{getIndex}}][id]" value="{{id}}">
				<input type="hidden" id="speka[{{getIndex}}][prid]" name="speka[{{getIndex}}][prid]" value="{{prid}}">
				<div class="checkbox">

					<label>
						<input type="checkbox" id="speka[{{getIndex}}][spid]" name="speka[{{getIndex}}][spid]" value="{{spid}}" {{#inAkt}}checked{{/inAkt}}>
						<span class="custom-checkbox"><i class="icon-ok"></i></span>
						<span class="title pl10">{{title}}</span>
					</label>

				</div>

			</div>
			<div class="flex-string w100 pl10">
				<input type="text" id="speka[{{getIndex}}][kol]" name="speka[{{getIndex}}][kol]" value="{{kol}}" class="w90">
				<div class="gray2 fs-07"></div>
			</div>

		</div>
		<div class="mb5 border-bottom fs-10 text-right gray">{{#ekol}}Не отгружено еще &nbsp;<b>{{ekol}}</b>&nbsp; {{edizm}}{{/ekol}}</div>
		{{/list}}

		<div class="infodiv">Выберите позиции, которые будут отгружены. Загружаются только позиции, которые не участвуют в других актах</div>

	</script>

	<script>

		$(function () {

			$('#newstep').trigger('change');

			$.get("/modules/contract/core.contract.php?action=akt.pozitions&did="+$('#did').val()+"&deid="+$('#deid').val(), function(data){

				var template = $('#spekatemplate').html();
				Mustache.parse(template);   // optional, speeds up future uses

				// манипуляции с расстановкой индексов
				var datas = {
					list: data.list,
					setUpIndex: function() {
						++window['INDEX']||(window['INDEX']=0);
						return;
					},
					getIndex: function() {
						return window['INDEX'] - 1;
					},
					resetIndex: function() {
						window['INDEX']=null;
						return;
					}
				};

				var rendered = Mustache.render(template, datas);
				$('div[data-id="poz"]').html(rendered);

				if(data.count !== data.countSpeka || data.count > 0) {

					$('div#pozition').removeClass('hidden');
					$('#pozitions').attr('checked', 'checked');
					//$('#pozitions').attr('disabled', 'disabled');
					$('#pozitions').closest('.checkbox').addClass('hidden');

					$('#dialog').center();

				}
				if(data.deid > 0){



				}

			}, "json");

		});

		$(document).off('change', '#newstep');
		$(document).on('change', '#newstep', function () {

			if ($('#stepfields').is('div')) {

				$.get('content/helpers/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&idcategory=' + $('option:selected', this).val(), function (data) {

					$('#stepfields').html(data);

					if (!data) $('#stepfields').addClass('hidden');
					else $('#stepfields').removeClass('hidden');

					$('.inputdatetime').each(function () {

						$(this).datetimepicker({
							timeInput: false,
							timeFormat: 'HH:mm',
							oneLine: true,
							showSecond: false,
							showMillisec: false,
							showButtonPanel: true,
							timeOnlyTitle: 'Выберите время',
							timeText: 'Время',
							hourText: 'Часы',
							minuteText: 'Минуты',
							secondText: 'Секунды',
							millisecText: 'Миллисекунды',
							timezoneText: 'Часовой пояс',
							currentText: 'Текущее',
							closeText: '<i class="icon-ok-circled"></i>',
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1)
						});

					});
					$('.inputdate').each(function () {

						if (isMobile != true) $(this).datepicker({
							dateFormat: 'yy-mm-dd',
							numberOfMonths: 2,
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1),
							showButtonPanel: true,
							currentText: 'Сегодня',
							closeText: 'Готово'
						});

					});

					if (!isMobile) {

						$(".multiselect").multiselect({sortable: true, searchable: true});
						$(".connected-list").css('max-height', "200px");

					}

					$('input[data-type="address"]').each(function () {

						$(this).suggestions({
							token: $dadata,
							type: "ADDRESS",
							count: 5,
							formatResult: formatResult,
							formatSelected: formatSelected,
							onSelect: function (suggestion) {

								console.log(suggestion);

							},
							addon: "clear",
							geoLocation: true
						});

					});

				}).done(function () {

					$('#dialog').center();

				});

			}

		});

		$(document).off('change','#pozitions');
		$(document).on('change','#pozitions',function(){

			var state = $(this).prop('checked');
			var disabled = $(this).prop('disabled');

			if(!disabled) {

				if (!state)
					$('div#pozition').addClass('hidden');

				else
					$('div#pozition').removeClass('hidden');

			}

			$('#dialog').center();

		});

	</script>
	<?php

	$hooks -> do_action( "akt_form_after", $_REQUEST );

}

if ($action == "akt.print") {

	$deid = $_REQUEST['deid'];

	$result   = $db -> getRow("SELECT * FROM ".$sqlname."contract WHERE deid = '$deid' AND identity = '$identity'");
	$akt_num  = $result["number"];
	$idtype   = $result["idtype"];
	$akt_date = get_sfdate2($result["datum"]);
	$akt_temp = $result["title"];
	$status   = $result["status"];

	$akt_num1 = '<b class="red">№'.$akt_num.'</b> от <b>'.$akt_date.'</b>';

	if (!$akt_num) {

		$akt_num1 = '<b class="red">№'.generate_num("akt").'</b> от <b>'.format_date_rus(current_datum()).'</b><br><span class="smalltxt">(Предварительный). Будет присвоен после сохранения.</span>';

	}

	$file = $rootpath."/files/".$fpath."akt_".$akt_num.".pdf";

	//заменяем старое расширение новым
	$akt_temp = str_replace(".htm", ".tpl", $akt_temp);

	?>
	<div class="zagolovok">Печать Акта</div>
	<form method="post" action="/content/helpers/get.doc.php" enctype="multipart/form-data" name="aktForm" id="aktForm" autocomplete="off" target="blank">
		<input type="hidden" id="did" name="did" value="<?= $did ?>">
		<input type="hidden" id="deid" name="deid" value="<?= $deid ?>">
		<input name="action" id="action" type="hidden" value="akt.print"/>

		<div id="formtabs" class="box--child wp100" style="overflow-y:auto !important; overflow-x:hidden">

			<div class="flex-container box--child mt10 mb20">

				<div class="flex-string wp20 gray2 fs-12 right-text">Номер:</div>
				<div class="flex-string wp80 pl10 fs-12">
					<?= $akt_num1 ?>
				</div>

			</div>

			<div class="flex-container box--child mt20 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text">Выводить:</div>
				<div class="flex-string wp80 pl10">
					<label class="pr15 inline">
						<input type="radio" name="tip" id="tip" value="print" title="На экран" checked="checked">&nbsp;На экран
					</label>
					<label class="inline">
						<input type="radio" name="tip" id="tip" value="pdf" title="На экран">&nbsp;в PDF-файл
					</label>
				</div>

			</div>

			<div class="flex-container box--child mt10 mb10 hidden" id="pdf">

				<div class="flex-string wp20 gray2 fs-12 right-text"></div>
				<div class="flex-string wp80 pl10">
					<label class="margtop10 Bold blue"><input name="download" id="download" type="checkbox" value="yes" checked="checked">&nbsp;Скачать</label>
				</div>

			</div>

			<hr>

			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Шаблон:</div>
				<div class="flex-string wp80 pl10">
					<select name="temp" id="temp" class="required wp95">
						<?php
						$ires = $db -> query("SELECT * FROM ".$sqlname."contract_temp WHERE typeid IN (SELECT id FROM ".$sqlname."contract_type WHERE type IN ('get_akt') AND identity = '$identity') AND identity = '$identity' ORDER by title");
						while ($data = $db -> fetch($ires)) {

							print '<option value="'.$data['file'].'" '.($akt_temp == $data['file'] ? 'selected' : '').'>'.$data['title'].'</option>';

						}
						?>
					</select>
				</div>

			</div>
			<div class="flex-container box--child mt10 mb10">

				<div class="flex-string wp20 gray2 fs-12 right-text"></div>
				<div class="flex-string wp80 pl10">
					<label><input name="nosignat" id="nosignat" type="checkbox" value="yes"/>&nbsp;без штампа</label>
				</div>

			</div>

			<?php
			/**
			 * Статус документа
			 */
			$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$idtype', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");

			if (count($statuses) > 0) {
				?>
				<div class="flex-container mb10 mt20 box--child">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Статус документа:</div>
					<div class="flex-string wp80 pl10 relativ">

						<select name="status" id="status" class="wp97">
							<option value="">--не менять--</option>
							<?php

							$oldStatus = $status;//текущий статус документа
							$changed   = false;//признак, что статус мы еще не меняли

							//если статус не был установлен ранее, то выбираем первый
							if ($oldStatus < 1) {

								$status  = $statuses[0]['id'];
								$changed = true;//ставим признак, что статус уже поменяли

							}

							foreach ($statuses as $i => $da) {

								//здесь не будем заставлять менять стаутс
								//$s = ($status == $da['id']) ? "selected" : "";
								//$d = ($oldStatus == $da['id']) ? "disabled" : "";

								print '<option value="'.$da['id'].'" '.$s.' '.$d.' data-color="'.$da['color'].'" style="color:'.$da['color'].'">'.$da['title'].'</option>';

								if ($status == $da['id'] && $changed == false) {
									$status  = $statuses[ $i + 1 ]['id'];
									$changed = true;//ставим признак, что статус уже поменяли
								}

							}
							?>
						</select>

					</div>

				</div>
				<?php
			}
			?>

		</div>

		<?php
		if (file_exists($file)) {

			$link = "content/helpers/get.file.php?file=akt_".$akt_num.".pdf";
			?>
			<div class="formdiv text-center">Файл PDF Акта уже есть в системе. <br>Вы можете его получить по этой
				<a href="<?= $link ?>" class="red" target="blank"><b>ссылке</b></a> или <b>сгенерировать новый</b>.
			</div>
		<?php } ?>

		<hr>

		<div class="button--pane text-right">

			<a href="javascript:void(0)" onclick="$('#aktForm').trigger('submit'); DClose();" class="button">Получить</a>&nbsp;
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

		</div>

	</form>
	<script>
		$('input[type=radio]').on('change', function () {

			if ($(this).val() === 'pdf') {

				$('#pdf').removeClass('hidden');

			}
			else $('#pdf').addClass('hidden');

		});
	</script>
	<?php
}

if ($action == "akt.mail") {

	$did  = $_REQUEST['did'];
	$deid = $_REQUEST['deid'];
	?>
	<div class="zagolovok">Отправка документа</div>
	<?php

	$emails = [];

	//Проверим на подключенный SMTP-сервер
	$active = $db -> getOne("select active from ".$sqlname."smtp WHERE identity = '$identity' and tip = 'send'");

	if ($GLOBALS['isCloud'] == 'yes' && $active != 'yes') {

		print '<div class="warning m0">Отправка возможно только при настроенном SMTP-сервере. Настроить его можно в разделе:<br>"Панель управления" / "Интеграция" / Почтовый сервер</div>';

	}
	else {

		//найдем клиента
		$clid = $db -> getOne("SELECT clid FROM ".$sqlname."dogovor WHERE did = '$did' and identity = '$identity'");

		$result = $db -> getRow("select * from ".$sqlname."user where iduser = '$iduser1' and identity = '$identity'");
		$mMail  = $result["email"];
		$mName  = $result["title"];
		$mPhone = $result["phone"];

		$content = '
Приветствую, {{person}}

Отправляю Вам закрывающие документы.

Спасибо за внимание.
С уважением,
'.$mName.'
Тел.: '.$mPhone.'
Email.: '.$mMail.'
==============================
'.$company;

		?>
		<FORM method="post" action="/modules/contract/core.contract.php" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
			<input name="action" type="hidden" value="akt.mail"/>
			<input name="did" id="did" type="hidden" value="<?= $did ?>"/>
			<input name="deid" id="deid" type="hidden" value="<?= $deid ?>"/>

			<div id="formtabs" class="wp100" style="max-height: 70vh; overflow-y: auto; overflow-x: hidden;">

				<div class="flex-container">

					<div class="flex-string wp70 nopad" style="max-height: 70vh; width:69%; overflow-y: auto; overflow-x: hidden; float:left">

						<div class="row pt5">

							<div class="column12 grid-12 border-box pt0">

								<div class="gray2 fs-09">Тема сообщения</div>
								<input type="text" name="theme" class="required wp100" id="theme" value="Закрывающие документы">

							</div>

						</div>
						<div class="row">

							<div class="column12 grid-12 border-box">

								<textarea name="content" class="wp100" id="content" style="min-height: 250px; height: 30vh;"><?= $content ?></textarea>

							</div>

						</div>

					</div>
					<div class="flex-string wp30 nopad">

						<div class="row">

							<div class="column12 grid-12 border-box">
								<?php
								$count = 0;

								$pids = yexplode(";", getDogData($did, 'pid_list'));

								$result = $db -> query("SELECT * FROM ".$sqlname."personcat WHERE clid = '$clid' and mail != '' and identity = '$identity'");
								while ($data = $db -> fetch($result)) {

									$s = "";
									if (in_array($data['pid'], $pids)) {
										$s = "checked";
										$count++;
									}

									$emails[] = '<label><input type="checkbox" name="email[]" id="email[]" class="email" value="pid:'.$data['pid'].'" '.$s.'>&nbsp;'.$data['person'].'</label>';

								}

								$mail_url = yexplode(",", str_replace(";", ",", str_replace(" ", "", getClientData($clid, 'mail_url'))), 0);

								if ($mail_url != '') {

									array_unshift($emails, '<label><input type="checkbox" name="email[]" id="email[]" class="email" checked="checked" value="clid:'.$clid.'">&nbsp;'.current_client($clid).'</label>');
									$count++;
								}

								if (count($emails) == 0) {

									print '<div class="warning m0">Внимание! Не найдено ни одного Email. <b class="red">Отправка невозможна!</b></div>';
									exit();

								}

								?>

								<div class="ydropDown border">
									<span>Получатели</span>
									<span class="ydropCount"><?= $count ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
									<div class="yselectBox" style="max-height: 350px;">
										<?php
										foreach ($emails as $email) {

											echo '<div class="ydropString ellipsis">'.$email.'</div>';

										}
										?>
									</div>
								</div>

							</div>

						</div>

						<div class="row">

							<div class="column12 grid-12 border-box">
								<?php
								/**
								 * Статус документа
								 */
								$idtype = $db -> getOne("select idtype from ".$sqlname."contract where deid = '$deid' and identity = '$identity'");

								$statuses = $db -> getAll("SELECT * FROM ".$sqlname."contract_status WHERE FIND_IN_SET('$idtype', REPLACE(".$sqlname."contract_status.tip, ';',',')) > 0 AND identity = '$identity' ORDER by ord");

								$status = $db -> getOne("select `status` from ".$sqlname."contract where deid = '$deid' and identity = '$identity'");

								$tstatus = $db -> getOne("select `title` from ".$sqlname."contract_status where id = '$status' and identity = '$identity'");

								$current = ($tstatus != '') ? $tstatus : 'не менять';

								/**
								 * Находим след.статус и устанавливаем его
								 */
								$elements = arrayElements($tstatus, $statuses, 'title');
								if($elements['next'] != ''){

									$tstatus = $elements['next'];

								}

								if (count($statuses) > 0) {
									?>
									<div class="ydropDown border">
										<span>Статус</span>
										<span class="ydropText"><?= $tstatus ?></span>
										<i class="icon-angle-down pull-aright arrow"></i>
										<div class="yselectBox" style="max-height: 350px;">
											<?php
											foreach ($statuses as $da) {

												$s = ($status == $da['id']) ? '<b class="blue">&#9733;</b>&nbsp;' : '';

												echo '
												<div class="ydropString yRadio ellipsis">
													<label style="color:'.$da['color'].'">
													<input type="radio" name="status" id="status" data-title="'.$da['title'].'" value="'.$da['id'].'" '.($tstatus == $da['title'] ? 'checked' : '').' class="hidden">&nbsp;'.$s.$da['title'].'
													</label>
												</div>
												';

											}
											?>
										</div>
									</div>
									<?php
								}
								?>

							</div>

						</div>

						<div class="p5">

							<div class="gray2 fs-07">Вложения</div>

							<div id="attach" class="infodiv wp100">

								<span id="loader"><img src="/assets/images/loading.gif" width="12"> Получаю файл...</span>

							</div>

						</div>

					</div>

				</div>

			</div>

			<hr>

			<div class="text-right button--pane">

				<a href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button hidden sender">Отправить</a>&nbsp;
				<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>

			</div>
		</form>
		<script>

			var count = $('.email').length;

			$.get('/modules/contract/core.contract.php?action=akt.link&did=<?=$did?>&deid=<?=$deid?>', function (data) {

				var f = data.files;
				var html = '';

				for (var i in f) {

					html = html + '<div class="mb5"><a href="/content/helpers/get.file.php?file=' + f[i].file + '" target="blank" title="Скачать"><i class="icon-attach-1"></i>&nbsp;' + f[i].name + '</a><input name="file\[\]" id="file\[\]" type="hidden" value="' + f[i].file + '" /><input name="name\[\]" id="name\[\]" type="hidden" value="' + f[i].name + '"></div>';

				}

				if (f.length > 0) $('.sender').removeClass('hidden');
				$('#attach').html(html);

			}, 'json');

		</script>
		<?php

	}

}

if ($action == 'akt.export') {

	//$dstart = date("Y-m-d", strToTime("-30 days"));
	//$dend   = current_datum();
	?>
	<DIV class="zagolovok"><B>Экспорт актов</B></DIV>
	<FORM method="post" action="/modules/contract/core.contract.php" enctype="multipart/form-data" name="eForm" id="eForm" target="blank">
		<input name="action" id="action" type="hidden" value="akt.export">
		<input name="mc" id="mc" type="hidden" value="<?=$_REQUEST['mc']?>">
		<input name="user_list[]" id="user_list[]" type="hidden" value="<?=$_REQUEST['iduser']?>">

		<div class="row" id="speriod">

			<div class="column12 grid-12 fs-12 text-center gray2 Bold">Выбор периода</div>

			<hr>

			<div class="column12 grid-1 fs-12 right-text gray2 pt10">c</div>
			<div class="column12 grid-2">
				<input type="text" name="dstart" id="dstart" class="dstart inputdate" value="<?= $dstart ?>">
			</div>
			<div class="column12 grid-1 fs-12 right-text gray2 pt10">по</div>
			<div class="column12 grid-2">
				<input type="text" name="dend" class="dend inputdate" id="dend" value="<?= $dend ?>">
			</div>

		</div>

		<hr>

		<div class="flex-container mb10">

			<div class="flex-string wp100 div-center">
				<select name="period" id="period" class="w200" data-goal="speriod" data-action="period">
					<option selected="selected">-за всё время-</option>
					<option data-period="today">Сегодня</option>
					<option data-period="yestoday">Вчера</option>

					<option data-period="calendarweekprev">Неделя прошлая</option>
					<option data-period="calendarweek">Неделя текущая</option>

					<option data-period="monthprev">Месяц прошлый</option>
					<option data-period="month">Месяц текущий</option>

					<option data-period="quartprev">Квартал прошлый</option>
					<option data-period="quart">Квартал текущий</option>

					<option data-period="year">Год</option>
				</select>
				<div class="gray fs-10 em">Быстрый выбор</div>
			</div>

		</div>

		<div class="infodiv div-center">Укажите период для экспорта. Выгрузка производится по дате акта</div>

		<hr>

		<DIV class="text-right">
			<A href="javascript:void(0)" onclick="$('#eForm').trigger('submit')" class="button">Получить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>
		</DIV>

	</FORM>
	<?php
}

if ($action == 'payment.export') {

	//$dstart = $m1;
	//$dend   = $m2;
	?>
	<DIV class="zagolovok"><B>Экспорт счетов</B></DIV>
	<FORM method="post" action="/reports/ent-InvoiceStateByUser.php" enctype="multipart/form-data" name="eForm" id="eForm" target="blank">
		<input name="action" id="action" type="hidden" value="export">
		<input name="mc" id="mc" type="hidden" value="<?=$_REQUEST['mc']?>">
		<input name="user_list[]" id="user_list[]" type="hidden" value="<?=$_REQUEST['iduser']?>">

		<div class="row" id="speriod">

			<div class="column12 grid-12 fs-12 text-center gray2 Bold">Выбор периода</div>

			<hr>

			<div class="column12 grid-1 fs-12 right-text gray2 pt10">c</div>
			<div class="column12 grid-2">
				<input type="text" name="da1" id="da1" class="dstart inputdate" value="<?= $dstart ?>">
			</div>
			<div class="column12 grid-1 fs-12 right-text gray2 pt10">по</div>
			<div class="column12 grid-2">
				<input type="text" name="da2" class="dend inputdate required" id="da2" value="<?= $dend ?>">
			</div>

		</div>

		<hr>

		<div class="flex-container mb10">

			<div class="flex-string wp100 div-center">
				<select name="period" id="period" class="w200" data-goal="speriod" data-action="period">
					<option selected="selected">-за всё время-</option>
					<option data-period="today">Сегодня</option>
					<option data-period="yestoday">Вчера</option>

					<option data-period="calendarweekprev">Неделя прошлая</option>
					<option data-period="calendarweek">Неделя текущая</option>

					<option data-period="monthprev">Месяц прошлый</option>
					<option data-period="month">Месяц текущий</option>

					<option data-period="quartprev">Квартал прошлый</option>
					<option data-period="quart">Квартал текущий</option>

					<option data-period="year">Год</option>
				</select>
				<div class="gray fs-10 em">Быстрый выбор</div>
			</div>

		</div>

		<div class="infodiv div-center">Укажите период для экспорта. Выгрузка производится по дате счета</div>

		<hr>

		<DIV class="text-right">

			<A href="javascript:void(0)" onclick="$('#eForm').trigger('submit')" class="button">Получить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</DIV>

	</FORM>
	<?php
}
?>

<script>

	includeJS('/assets/js/timepickeraddon/jquery-ui-timepicker-addon.js');

	var action = $('#action').val();

	if (!isMobile) {

		if (in_array(action, ['akt.print', 'akt.per.print'])) {

			$('#dialog').css({'width': '600px'});

		}
		else if (in_array(action, ['akt.export', 'export'])) {

			$('#dialog').css('width', '500px');

		}
		else {

			$('#dialog').css('width', '800px');

		}


	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

	}

	$(function () {

		$('.inputdate').each(function () {

			if (!isMobile)
				$(this).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 2,
					firstDay: 1,
					dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
					monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
					changeMonth: true,
					changeYear: true,
					yearRange: '1940:2030',
					minDate: new Date(1940, 1 - 1, 1),
					showButtonPanel: true,
					currentText: 'Сегодня',
					closeText: 'Готово'
				});

		});

		$('#des').autoHeight(250);

		$('#uploadForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false)
					return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.empty().css('display', 'block').fadeTo(1, 1).append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');

				return true;

			},
			success: function (data) {

				var errors = '';

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$('#resultdiv').empty();

				if (data.error !== 'undefined' || data.error != null || data.error !== '')
					errors = '<br>Note: ' + data.error;

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result + errors);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if (in_array(action, ['akt.add', 'akt.edit', 'akt.per.add', 'akt.per.edit'])) {

					settab('0', false);

					if (typeof cardload == 'function')
						cardload();

				}

				if (typeof configpage == 'function')
					configpage();

				if (typeof settab == 'function')
					settab('15');

			}
		});

		$('#idcategory').trigger('change');

		$('#filelist').load('/modules/contract/fileview.php?deid=<?=$deid?>', function () {

			$('#dialog').center();

			if( $('#filelist').html() === '')
				$('#filelist').closest('.flex-container').addClass('hidden');

		});

	});

	$('#idcategory').on('change', function () {

		if ($('#stepfields').is('div') && action !== 'change.close') {

			$.get('/content/card/deal.helpers.php?action=getStepFields&did=' + $('#did').val() + '&idcategory=' + $('option:selected', this).val(), function (data) {

				if (!data)
					$('#stepfields').addClass('hidden');

				else {

					$('#stepfields').removeClass('hidden').html(data);

					$('.inputdatetime').each(function () {

						$(this).datetimepicker({
							timeInput: false,
							timeFormat: 'HH:mm',
							oneLine: true,
							showSecond: false,
							showMillisec: false,
							showButtonPanel: true,
							timeOnlyTitle: 'Выберите время',
							timeText: 'Время',
							hourText: 'Часы',
							minuteText: 'Минуты',
							secondText: 'Секунды',
							millisecText: 'Миллисекунды',
							timezoneText: 'Часовой пояс',
							currentText: 'Текущее',
							closeText: '<i class="icon-ok-circled"></i>',
							dateFormat: 'yy-mm-dd',
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1)
						});

					});
					$('.inputdate').each(function () {

						if (!isMobile) $(this).datepicker({
							dateFormat: 'yy-mm-dd',
							numberOfMonths: 2,
							firstDay: 1,
							dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
							monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
							changeMonth: true,
							changeYear: true,
							yearRange: '1940:2030',
							minDate: new Date(1940, 1 - 1, 1),
							showButtonPanel: true,
							currentText: 'Сегодня',
							closeText: 'Готово'
						});

					});

					if (!isMobile) {

						$(".multiselect").multiselect({sortable: true, searchable: true});
						$(".connected-list").css('max-height', "200px");

					}

				}

			}).done(function () {

				$('#dialog').center();

			});

		}

	});

	$('.tagsmenu li').on('click', function () {

		var t = $('b', this).html();

		if ($('#suffix').is('textarea')) addTagInEditor('suffix', t);
		if ($('#des').is('textarea')) addTagInEditor('des', t);

	});

	function addefile() {

		var htmltr = '<div class="eupload relativ"><input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100"><div class="idel hand delbox" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('.filebox').append(htmltr);

		$('#dialog').center();

	}

	$(document).on('click', '.delbox', function () {

		var count = $('.eupload').length;

		if (count === 1) $(this).closest('.eupload').find('#file\\[\\]').val('');
		else $(this).closest('.eupload').remove();

	});

	function filedelete(id, fname) {

		var url = '/modules/contract/fileview.php?action=delete&deid=' + id + '&fname=' + fname;

		$.get(url, function (data) {

			$('#filelist').load('/modules/contract/fileview.php?deid=' + id);

			return false;
		});
	}

	function exportDo() {
		var str = $('#pageform').serialize();
		var url = '/modules/contract/core.contract.php?action=akt.export&dstart=' + $('#dstart').val() + '&dend=' + $('#dend').val() + '&' + str;
		window.open(url);

		return false;
	}

</script>