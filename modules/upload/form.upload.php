<?php
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2024.x           */
/* ============================ */

use Salesman\Upload;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$clid   = (int)$_REQUEST['clid'];
$pid    = (int)$_REQUEST['pid'];
$did    = (int)$_REQUEST['did'];
$folder = (int)$_REQUEST['folder'];
$action = $_REQUEST['action'];

global $maxFileFolderLevel;

if(empty($maxFileFolderLevel)) {
	$maxFileFolderLevel = 3;
}

if ($action == "add") {
	$action = 'edit';
}

/*
if ($action == "add") {

	?>
	<DIV class="zagolovok">Загрузка файла</DIV>

	<FORM action="/modules/upload/core.upload.php" method="post" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
		<INPUT type="hidden" name="action" id="action" value="add">
		<INPUT name="clid" type="hidden" id="clid" value="<?= $clid ?>">
		<INPUT name="pid" type="hidden" id="pid" value="<?= $pid ?>">
		<INPUT name="fver" type="hidden" id="fver" value="1">

		<DIV id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y:auto !important">

			<?php
			include $rootpath."/content/ajax/check_disk.php";
			if ($diskLimit > 0) {

				print '
				<div class="infodiv mb10" align="center">
					<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % )
				</div>
				';

			}
			if ($diskLimit > 0 && $diskUsage['percent'] >= 100) {

				print '<div class="warning mb10" align="center"><b class="red">Превышен лимит использования диска</b></div>';

			}
			else {
				?>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Выбор файлов:</div>
					<div class="flex-string wp80 pl10">

						<input name="files[]" type="file" class="files wp97" id="files[]" multiple>

						<div class="fs-07 gray2">Вы можете выбрать несколько файлов с помощью клавиши Ctrl</div>
						<div class="infodiv hidden pad5 fs-09 description" style="overflow: auto; max-height:100px"></div>

					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Описание:</div>
					<div class="flex-string wp80 pl10">

						<TEXTAREA name="ftag" rows="2" class="des wp97" id="ftag"></TEXTAREA>

					</div>

				</div>
				<div class="flex-container mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Папка:</div>
					<div class="flex-string wp80 pl10">

						<select name="idcategory" id="idcategory" class="wp97">
							<OPTION value="">--Выбор--</OPTION>
							<?php
							$result = $db -> query("SELECT * FROM ".$sqlname."file_cat WHERE subid = '0' and identity = '$identity' ORDER BY title");
							while ($datas = $db -> fetch($result)) {

								if ($datas['shared'] == 'yes') {
									$shared = ' - Общая';
								}
								?>
								<OPTION <?php
								if ($datas['idcategory'] == $folder) {
									print "selected";
								} ?> value="<?= $datas['idcategory'] ?>"><?= $datas['title'] ?><?= $shared ?></OPTION>
								<?php
								$shared  = '';
								$result2 = $db -> query("SELECT * FROM ".$sqlname."file_cat WHERE subid = '".$datas['idcategory']."' and identity = '$identity'");
								while ($data = $db -> fetch($result2)) {
									if ($data['shared'] == 'yes') {
										$shared = ' - Общая';
									}
									?>
									<OPTION <?php
									if ($data['idcategory'] == $folder) {
										print "selected";
									} ?> value="<?= $data['idcategory'] ?>">&nbsp;&nbsp;&rarr;&nbsp;<?= $data['title'] ?><?= $shared ?></OPTION>
									<?php
									$shared = '';
								}
							}
							?>
						</select>

					</div>

				</div>

				<div class="flex-container deal div-info" data-id="fdeal">

					<span class="flex-string wp5 pt10 hidden-iphone"><i class="icon-briefcase-1 blue"></i></span>
					<span class="flex-string wp95 relativ cleared1">
						<INPUT name="did" type="hidden" id="did" value="<?= $did ?>">
						<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= current_dogovor($did) ?>" class="wp95">
						<span class="idel clearinputs" title="Очистить"><i class="icon-block-1 red"></i></span>
					</span>

				</div>

				<div class="viewdiv mt10 text-wrap">

					Максимальный размер файла не должен превышать:
					<span>
						<?php
						if ($maxupload == '') {
							$maxupload = str_replace([
								'M',
								'm'
							], '', @ini_get('upload_max_filesize'));
						}
						print "<b>".$maxupload." Mb</b>";
						?>
					</span>
					<BR>
					<span>Разрешенные типы файлов: <?= $ext_allow ?></span>

				</div>

			<?php
			} ?>

		</DIV>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button"><i class="icon-upload"></i>Загрузить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}
*/

if ($action == "edit") {

	$fid = (int)$_REQUEST['id'];

	$info = $fid > 0 ? Upload ::info($fid) : [
		"clid"       => $clid,
		"pid"        => $pid,
		"did"        => $did,
		"idcategory" => $folder,
		"iduser"     => $iduser1,
	];

	/*$result1 = $db -> getRow("select * from ".$sqlname."file where fid='".$fid."' and identity = '$identity'");
	$ftag    = $result1["ftag"];
	$fver    = $result1["fver"];
	$ftitle  = $result1["ftitle"];
	$oldfile = $result1["fname"];
	$iduser  = $result1["iduser"];
	$clid    = $result1["clid"];
	$pid     = $result1["pid"];
	$did     = $result1["did"];
	$tskid   = $result1["tskid"];
	$coid    = $result1["coid"];
	$folder  = $result1["folder"];*/

	?>
	<DIV class="zagolovok">Редактирование описания файла</DIV>

	<FORM action="/modules/upload/core.upload.php" method="post" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
		<INPUT type="hidden" name="action" id="action" value="edit">
		<INPUT name="clid" type="hidden" id="clid" value="<?= $info['clid'] ?>">
		<INPUT name="pid" type="hidden" id="pid" value="<?= $info['pid'] ?>">
		<INPUT name="fid" type="hidden" id="fid" value="<?= $fid ?>">
		<INPUT name="fver" type="hidden" id="fver" value="<?= $info['fver'] ?>">
		<INPUT name="oldfile" type="hidden" id="oldfile" value="<?= $info['fname'] ?>">

		<DIV id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y:auto">

			<?php
			include $rootpath."/content/ajax/check_disk.php";
			if ($diskLimit > 0) {

				print '
				<div class="infodiv mb10" align="center">
					<b>Ипользование диска:</b> Лимит: <b>'.$diskUsage['total'].'</b> Мб, Занято: <b class="red">'.$diskUsage['current'].'</b> Mb ( <b>'.$diskUsage['percent'].'</b> % )
				</div>';

			}
			if ($diskLimit > 0 && $diskUsage['percent'] >= 100) {

				print '<div class="warning mb10" align="center"><b class="red">Превышен лимит использования диска</b></div>';

			}
			else {
				?>

				<div class="flex-container box--child mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Файл:</div>
					<div class="flex-string wp80 pl10">

						<input name="file[]" type="file" class="files wp97" id="file[]" multiple accept=".<?=yimplode(",.", yexplode(",", $ext_allow))?>">

						<?php if($fid > 0){?>
						<div class="fs-07 gray2">Оригинальный файл: <b class="red"><?= $info['ftitle'] ?></b></div>
						<?php } ?>

						<div class="viewdiv mt10 text-wrap">

							Максимальный размер файла не должен превышать:
							<span>
								<?php
								if ($maxupload == '') {
									$maxupload = str_replace([
										'M',
										'm'
									], '', @ini_get('upload_max_filesize'));
								}
								print "<b>".$maxupload." Mb</b>";
								?>
							</span>
							<BR>
							<span>Разрешенные типы файлов: <?= $ext_allow ?></span>

						</div>

					</div>

				</div>
				<div class="flex-container box--child mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Описание:</div>
					<div class="flex-string wp80 pl10">

						<TEXTAREA name="ftag" rows="2" class="des wp97" id="ftag"><?= $info['text'] ?></TEXTAREA>

					</div>

				</div>
				<div class="flex-container box--child mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Ответственный:</div>
					<div class="flex-string wp80 pl10">

						<SELECT name="iduser" id="iduser" class="wp97">
							<OPTION value="none">--Выбор--</OPTION>
							<?php
							$result = $db -> query("SELECT * FROM ".$sqlname."user WHERE identity = '$identity'");
							while ($datas = $db -> fetch($result)) {
								?>
								<OPTION <?php
								if ($datas['iduser'] == $info['iduser']) {
									print "selected";
								} ?> value="<?= $datas['iduser'] ?>"><?= $datas['title'] ?></OPTION>
								<?php
							}
							?>
						</SELECT>

					</div>

				</div>
				<div class="flex-container box--child mb10">

					<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Папка:</div>
					<div class="flex-string wp80 pl10">

						<select name="idcategory" id="idcategory" class="wp97">
							<OPTION value="">--Выбор--</OPTION>
							<?php
							$catalog = Upload ::getCatalogLine(0);
							foreach ($catalog as $key => $value) {

								if ($value['level'] <= 3) {

									$xshared = ( $value['shared'] == 'yes' ) ? ' - Общая' : "";

									print '<option value="'.$value['id'].'" '.( $value['id'] == $info['idcategory'] ? "selected" : '' ).'>'.( $value['level'] > 0 ? str_repeat('&nbsp;&nbsp;', $value['level']).'&rarr;&nbsp;' : '' ).$value['title'].' '.$xshared.'</option>';
								}

							}

							?>
						</select>

					</div>

				</div>

				<div class="flex-container box--child deal div-info" data-id="fdeal">

					<span class="flex-string wp5 pt10 hidden-iphone"><i class="icon-briefcase-1 blue"></i></span>
					<span class="flex-string wp95 relativ cleared1">
						<INPUT name="did" type="hidden" id="did" value="<?= $info['did'] ?>">
						<INPUT name="dtitle" id="dtitle" type="text" placeholder="Выбор <?= $lang['face']['DealName']['1'] ?>" value="<?= current_dogovor($info['did']) ?>" class="wp95">
						<span class="idel clearinputs" title="Очистить"><i class="icon-block-1 red"></i></span>
					</span>

				</div>

				<?php
			}
			?>

		</DIV>

		<hr>

		<div class="text-right button--pane">

			<A href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>
	</FORM>
	<?php
}
if ($action == "mass") {

	$id  = $_REQUEST['ch'];
	$all = $_REQUEST['all'];
	$kol = $_REQUEST['count'];
	$ids = implode(",", $id);
	//$ids  = $_REQUEST['ids'];

	$title = $_REQUEST['sub'] == 'delete' ? "Удаление" : "Перемещение";
	?>
	<div class="zagolovok"><b>Групповое действие</b> - <?= $title ?></div>
	<form action="/modules/upload/core.upload.php" id="uploadForm" name="uploadForm" method="post" enctype="multipart/form-data">
		<input name="ids" id="ids" type="hidden" value="<?= $ids ?>">
		<input name="action" id="action" type="hidden" value="mass">
		<input name="sub" id="sub" type="hidden" value="<?= $_REQUEST['sub'] ?>">

		<DIV id="formtabs" class="box--child" style="overflow-y: auto; max-height:80vh;">

			<div class="infodiv mb10">
				<b class="red">Важная инфрмация:</b>
				<ul>
					<li class="Bold blue">При нажатой клавише Ctrl можно мышкой выбрать нужные записи</li>
					<li>Отмена групповых действий не возможна</li>
					<li>Действия будут применены только для записей, к которым у вас есть доступ</li>
					<li>Ограничение для действия составляет 1000 записей</li>
				</ul>
			</div>

			<div class="flex-container mb10">

				<?php
				if($_REQUEST['sub'] == 'move'){
				?>
				<div class="flex-string wp100 pl10">

					<div class="flex-container box--child mt10 mb10" id="stepdiv">

						<div class="flex-string wp20 gray2 fs-12 pt7 right-text">Папка:</div>
						<div class="flex-string wp80 pl10">

							<select name="folder" id="folder" class="wp97">
								<OPTION value="">--Выбор--</OPTION>
								<?php
								$catalog = Upload ::getCatalogLine(0);
								foreach ($catalog as $key => $value) {

									if ($value['level'] <= 3) {

										$xshared = ( $value['shared'] == 'yes' ) ? ' - Общая' : "";

										print '<option value="'.$value['id'].'">'.( $value['level'] > 0 ? str_repeat('&nbsp;&nbsp;', $value['level']).'&rarr;&nbsp;' : '' ).$value['title'].' '.$xshared.'</option>';
									}

								}

								?>
							</select>

						</div>

					</div>

				</div>
				<?php } ?>

			</div>

			<div class="flex-container mb10 pt15 warning bgwhite">

				<div class="flex-string wp20 gray2 fs-12 right-text">Выполнить для:</div>
				<div class="flex-string wp80 pl10">

					<div class="flex-container">

						<div class="flex-string wp40 pl10">
							<div class="radio">
								<label>
									<input name="isSelect" id="isSelect" value="doSelected" type="radio" <?php if ( $kol > 0 ) print "checked"; ?>>
									<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									<span class="title">Выбранного (<b class="blue"><?= $kol ?></b>)</span>
								</label>
							</div>
						</div>
						<div class="flex-string wp40 pl10">
							<div class="radio" title="Действие возможно для 500 записей максимум">
								<label>
									<input name="isSelect" id="isSelect" value="doAll" type="radio" <?php if ( $kol == 0 ) print "checked"; ?>>
									<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
									<span class="title">Со всех страниц ( <b class="blue"><span id="alls"><?= $all ?></span></b> )</span>
								</label>
							</div>
						</div>

					</div>

				</div>

			</div>

		</DIV>

		<!--<div id="profile">
			<table id="bborder">
				<tr>
					<td width="160"><b>Выполнить для записей:</b></td>
					<td>
						<label><input name="isSelect" id="isSelect" value="doSelected" type="radio" <?php
							if ($kol > 0) {
								print "checked";
							} ?>>&nbsp;Выбранное (<b class="blue"><?= $kol ?></b>)</label>
						<label><input name="isSelect" id="isSelect" value="doAll" type="radio" <?php
							if ($kol == 0) {
								print "checked";
							} ?>>&nbsp;Со всех страниц (<b class="blue"><span id="alls"><?= $all ?></span></b>)</label>
					</td>
				</tr>
			</table>
		</div>-->

		<div class="text-right button--pane">
			<a href="javascript:void(0)" onclick="massSubmit()" class="button">Выполнить</a>
			<a href="javascript:void(0)" onclick="DClose()" class="button">Отмена</a>
		</div>
	</form>
	<?php
}

if ($action == "cat.list") {
	?>
	<div class="zagolovok">Редактор папок</div>

	<div style="height:60vh; overflow:auto" class="bgwhite">

		<TABLE id="bborder">
			<thead class="header_contaner sticked--top">
			<TR class="">
				<th><b>Название папки</b></th>
				<th class="w100"></th>
			</TR>
			</thead>
			<tbody>
			<?php
			$catalog = Upload ::getCatalogLine();
			foreach ($catalog as $key => $value) {

				$padding = 'mt5 Bold';

				if ((int)$value['level'] == 1) {
					$padding = 'pl20';
				}
				elseif ((int)$value['level'] > 1) {
					$x       = 20 + (int)$value['level'] * 10;
					$padding = "pl{$x} ml15 fs-09";
				}

				$folder = ( $value['level'] == 0 ? 'icon-folder-open deepblue' : ( $value['level'] == 1 ? 'icon-folder-open blue' : 'icon-folder broun' ) );

				print '
				<TR class="ha th40">
					<TD class="text-left '.$padding.'">
						<div class="strelka w10 ml10 mr10"></div><div class="pl5 Bold fs-11 inline"><i class="'.$folder.'"></i>'.$value['title'].'</div>
						[ <b class="gray2" title="Число файлов в папке">'.$value['count'].'</b> ]&nbsp;'.( $value['shared'] == 'yes' ? '&nbsp;<i class="icon-users-1 sup green" title="Общая папка"></i> ' : '' ).'
					</TD>
					<TD class="text-center">
						<A href="javascript:void(0)" onclick="editUpload(\''.$value['id'].'\',\'cat.edit\')" class="button bluebtn dotted m0 p3"><i class="icon-pencil" title="Редактировать"></i></A>
						<A href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить папку?\');if (cf)editUpload(\''.$value['id'].'\',\'cat.delete\')" class="button redbtn dotted m0 p3"><i class="icon-cancel-circled" title="Удалить"></i></A>
					</TD>
				</TR>
				';

			}
			?><?php
			/*
			$result = $db -> query("SELECT * FROM ".$sqlname."file_cat WHERE subid = '0' and identity = '$identity' ORDER BY title");
			while ($datas = $db -> fetch($result)) {

				$shared = ($datas['shared'] == 'yes') ? ' <sup class="green">Общая папка</sup>' : '';

				$all = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."file WHERE folder='".$datas['idcategory']."' and identity = '$identity'");
				?>
				<TR class="ha th40">
					<TD class="text-left">
						<div class="pl5 Bold fs-11 inline"><?= $datas['title'] ?></div>
						[ <b class="gray2" title="Число файлов в папке"><?= $all ?></b> ]&nbsp;<?= $shared ?>
					</TD>
					<TD class="text-center">
						<A href="javascript:void(0)" onclick="editUpload('<?= $datas['idcategory'] ?>','cat.edit')"><i class="icon-pencil blue" title="Редактировать"></i></A>
						<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить папку?');if (cf)editUpload('<?= $datas['idcategory'] ?>','cat.delete')"><i class="icon-cancel-circled red" title="Удалить"></i></A>
					</TD>
				</TR>
				<?php
				$result2 = $db -> query("SELECT * FROM ".$sqlname."file_cat WHERE subid = '".$datas['idcategory']."' and identity = '$identity' ORDER BY title");
				while ($data = $db -> fetch($result2)) {

					$shared = ($data['shared'] == 'yes') ? ' <sup class="green">Общая папка</sup>' : '';

					$all = $db -> getOne("SELECT COUNT(*) FROM ".$sqlname."file WHERE folder='".$data['idcategory']."' and identity = '$identity'");
					?>
					<TR class="ha th40">
						<TD class="text-left">
							<div class="pl20 Bold blue fs-11 inline"><?= $data['title'] ?></div>&nbsp;[
							<b class="gray2" title="Число файлов в папке"><?= $all ?></b> ]&nbsp;<?= $shared ?>
						</TD>
						<TD class="text-center">
							<A href="javascript:void(0)" onclick="editUpload('<?= $data['idcategory'] ?>','cat.edit')"><i class="icon-pencil blue" title="Редактировать"></i></A>
							<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить папку?');if (cf)editUpload('<?= $data['idcategory'] ?>','cat.delete')"><i class="icon-cancel-circled red" title="Удалить"></i></A>
						</TD>
					</TR>
					<?php

				}

			}*/
			?>
			</tbody>
		</TABLE>

	</div>
	<hr>
	<div class="text-right button--pane">
		<A href="javascript:void(0)" onclick="editUpload(0,'cat.edit')" class="button">+ Создать</A>
	</div>
	<?php

}
if ($action == "cat.edit") {

	$idcategory = $_REQUEST['id'];

	$result     = $db -> getRow("SELECT * FROM ".$sqlname."file_cat where idcategory='".$idcategory."' and identity = '$identity'");
	$title      = $result["title"];
	$idcategory = (int)$result["idcategory"];
	$subid      = (int)$result["subid"];
	$shared     = $result["shared"];

	//узнаем, есть ли вложенные папки
	$issub = (int)$db -> getOne("SELECT COUNT(*) FROM ".$sqlname."file_cat where subid='".$idcategory."' and identity = '$identity'");
	?>
	<div class="zagolovok">Редактирование папки</div>
	<FORM action="/modules/upload/core.upload.php" method="post" enctype="multipart/form-data" name="uploadForm" id="uploadForm">
		<INPUT type="hidden" name="action" id="action" value="cat.edit">
		<INPUT type="hidden" name="idcategory" id="idcategory" value="<?= $idcategory ?>">

		<DIV id="formtabs" class="box--child flex-vertical p10" style="max-height:80vh; overflow-x: hidden; overflow-y:auto !important">

			<div class="flex-container mb10">
				<div class="flex-string">Название</div>
				<div class="flex-string">
					<INPUT name="title" type="text" class="wp100" id="title" value="<?= $title ?>">
				</div>
			</div>

			<div class="flex-container mb10">
				<div class="flex-string">Главная папка</div>
				<div class="flex-string">
					<select name="subid" id="subid" class="wp100">
						<OPTION value="">--Выбор--</OPTION>
						<?php
						$catalog = Upload ::getCatalogLine(0);
						foreach ($catalog as $key => $value) {

							if ($value['level'] < $maxFileFolderLevel) {

								$xshared = ( $datas['shared'] == 'yes' ) ? ' - Общая' : "";

								print '<option value="'.$value['id'].'" '.( $value['id'] == $subid ? "selected" : '' ).'>'.( $value['level'] > 0 ? str_repeat('&nbsp;&nbsp;', $value['level']).'&rarr;&nbsp;' : '' ).$value['title'].' '.$xshared.'</option>';
							}

						}

						?>
					</select>
				</div>
			</div>

			<div class="flex-container mb10">
				<div class="flex-string"></div>
				<div class="flex-string">
					<label>
						<input type="checkbox" name="shared" id="shared" value="yes" <?php
						if ($shared == 'yes') {
							print "checked";
						} ?> /> Общая папка
					</label>
				</div>
			</div>

		</DIV>

		<hr>

		<div class="text-right button--pane">
			<A href="javascript:void(0)" onclick="$('#uploadForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="editUpload('','cat.list')" class="button">Отменить</A>
		</div>

	</FORM>
	<?php
}

if ($action == "info") {

	$fid = $_REQUEST['id'];

	$result     = $db -> getRow("select * from ".$sqlname."file where fid = '$fid' and identity = '$identity'");
	$ftitle     = $result["ftitle"];
	$fname      = $result["fname"];
	$fver       = $result["fver"];
	$idcategory = $result["folder"];
	$ftag       = $result["ftag"];
	$clid       = $result["clid"];
	$pid        = $result["pid"];
	$did        = $result["did"];
	$tskid      = $result["tskid"];
	$coid       = $result["coid"];
	$iduser     = $result["iduser"];

	$size = num_format(filesize($rootpath."/files/".$fpath.$fname) / 1000);
	//$icon = get_icon2($ftitle);

	$result2 = $db -> getRow("SELECT * FROM ".$sqlname."file_cat WHERE idcategory = '$idcategory' and identity = '$identity'");
	$folder  = $result2["title"];
	$shared  = $result2["shared"];

	$url = "";

	if ($clid > 0) {

		$roditel  = current_client($clid);
		$url_load = "openClient('".$clid."')";
		$img      = '<i class="icon-building blue"></i>';
		$type     = "Клиент";

	}
	if ($pid > 0) {

		$roditel  = current_person($pid);
		$url_load = "openPerson('".$pid."')";
		$img      = '<i class="icon-user-1 blue"></i>';
		$type     = "Контакт";

	}
	if ($did > 0) {

		$roditel  = current_dogovor($did);
		$url_load = "openDogovor('".$did."')";
		$img      = '<i class="icon-briefcase broun"></i>';
		$type     = "Сделка";

	}
	?>
	<DIV class="zagolovok">Информация о файле</DIV>

	<DIV id="formtabs" class="box--child" style="overflow-x: hidden; overflow-y:auto !important">

		<div class="flex-container mt10 mb15">

			<div class="flex-string wp20 gray2 fs-12 right-text">Ответственный:</div>
			<div class="flex-string wp80 pl10 fs-12">
				<?= current_user($iduser) ?>
			</div>

		</div>

		<div class="flex-container mt10 mb15">

			<div class="flex-string wp20 gray2 fs-12 right-text">Название:</div>
			<div class="flex-string wp80 pl10 Bold fs-12">
				<span><?= get_icon2($ftitle) ?>&nbsp;<?= $ftitle ?> </span>
				<div class="noBold mt10">Размер: <?= $size ?>&nbsp;kb</div>
			</div>

		</div>

		<div class="flex-container mt10 mb15">

			<div class="flex-string wp20 gray2 fs-12 right-text">Папка:</div>
			<div class="flex-string wp80 pl10 Bold fs-12">
				<i class="icon-folder blue"></i><?= $folder ?><?php
				if ($shared == 'yes') {
					print " - Общая папка";
				} ?>
			</div>

		</div>

		<div class="flex-container mt10 mb15">

			<div class="flex-string wp20 gray2 fs-12 right-text">Описание:</div>
			<div class="flex-string wp80 pl10 fs-12 flh-14">
				<span><?= ( $ftag != '' ? nl2br($ftag) : '--' ) ?></span>
			</div>

		</div>

		<?php
		if ($roditel != '') { ?>
			<div class="flex-container mt10 mb15">

				<div class="flex-string wp20 gray2 fs-12 right-text">К записи:</div>
				<div class="flex-string wp80 pl10 Bold fs-12">

					<?php
					print '<A href="javascript:void(0)" onClick="'.$url_load.'">'.$img.$roditel.'</A>';
					?>

				</div>

			</div>
		<?php
		} ?>

	</DIV>

	<hr>

	<div class="text-right button--pane">

		<A href="javascript:void(0)" onclick="editUpload('<?= $fid ?>','download')" class="button">Открыть</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Закрыть</A>

	</div>
	<?php
}
?>

<script>

	var action = $('#action').val();

	if (!isMobile) {

		$('#dialog').css('width', '700px');

		if (["cat.add", "cat.edit"].includes(action)) {
			$('#dialog').css('width', '500px');
		}

	}
	else {

		var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
		$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

	}

	$(function () {

		$('#ftag').autoHeight(200);

		$('#uploadForm').ajaxForm({
			dataType: 'json',
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.empty().css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

				return true;

			},
			success: function (data) {

				var id = $('#lmenu #idcat').val();

				if (['cat.edit', 'cat.add'].includes(action)) {

					doLoad('/modules/upload/form.upload.php?action=cat.list');

					$('#zfolder').load('/modules/upload/core.upload.php?action=catlist&id=' + id, function () {
						$('.ifolder [data-id=' + id + ']').addClass('fol_it');
					});
					configpage();

					$("#lmenu").find('.nano').nanoScroller();

				}
				else if (isCard) {
					settab('6');
				}
				else {
					configpage();
				}

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.message + data.error);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}

		});

		$("#dtitle").autocomplete("/content/helpers/deal.helpers.php?action=doglist&clid=" + $('#clid').val(), {
			autofill: true,
			minChars: 0,
			cacheLength: 5,
			maxItemsToShow: 10,
			selectFirst: false,
			multiple: false,
			delay: 500,
			matchSubset: 1,
			formatItem: function (data, i, n, value) {
				return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;<span class="pull-aright">[<span class="broun">' + data[5] + '</span>]</span><div class="blue smalltext">' + data[3] + '</div></div>';
			},
			formatResult: function (data) {
				return data[0];
			}
		})
			.result(function (value, data) {

				$('#did').val(data[1]);
				$('#clid').val(data[2]);
				$('#client').val(data[3]);

				if (data[4] !== '')
					$("#pid_list").append('<div class="pid_box" id="person_' + data[4] + '" title="' + data[6] + '"><INPUT type="hidden" name="pid[]" id="pid[]" value="' + data[4] + '"><div class="el"><div class="del" onclick="delItem(\'' + data[4] + '\')"></div>' + data[6] + '</div></div>');

			});

		if (parseInt($('#did').val()) > 0) {

			$('.adddeal').hide();
			$('.deal').removeClass('hidden');

		}

		$('#dialog').center();

	});

	$(document).on('change', '.files', function () {

		var string = '';
		var size = '';

		for (var x = 0; x < this.files.length; x++) {

			size = this.files[x].size / 1024;

			string = string + '<li>' + this.files[x].name + ' <span class="gray">[' + setNumFormat(size.toFixed(2)) + ' kb]</span> </li>';

		}

		//console.log(string);

		$('.description').empty().append('<b>Выбраны файлы:</b> <ul class="pad3 marg0 ml15">' + string + '</ul>').removeClass('hidden');

		if (!isMobile)
			$('#dialog').center();

	});

	function addfile() {

		var htmltr = '<tr><td height=25><input name="file[]" type="file" class="file" id="file[]" onchange="addfile();" style="width:98%" /></tr></td>';
		$('#filetr').append(htmltr);
		$('#dialog').center();

	}

	function massSubmit() {

		var empty = $(".required").removeClass("empty").filter('[value=""]').addClass("empty");

		if (empty.size()) {

			empty.css({color: "#ffffff", background: "#FF8080"});
			alert("Не заполнены обязательные поля\n\rОни выделены цветом");

		}
		if (!empty.size()) {

			$('#dialog').css('display', 'none');
			$('#dialog_container').css('display', 'none');

			var str = $('#uploadForm').serialize() + '&' + $('#pageform').serialize();
			var url = "/modules/upload/core.upload.php";

			$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных...</div>');

			$.post(url, str, function (data) {

				$('#resultdiv').empty();

				configpage();

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.message + data.error);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}, 'json');

		}

	}

</script>