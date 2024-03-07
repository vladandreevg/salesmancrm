<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];

if ($action == "delete.do") {

	//название нового типа, которому передаем удалемые записи
	$newtip = $db -> getOne("SELECT title FROM {$sqlname}relations WHERE id = '".$_REQUEST['newid']."' and identity = '$identity'");

	//название старого типа, который удаляем
	$tip = $db -> getOne("SELECT title FROM {$sqlname}relations WHERE id = '".$_REQUEST['id']."' and identity = '$identity'");

	//обрабатываем все записи
	$db -> query("update {$sqlname}clientcat set tip_cmr = '$newtip' WHERE tip_cmr = '$tip' and identity = '$identity'");

	//удаляем тип
	$db -> query("delete from {$sqlname}relations where id = '$_REQUEST[id]' and identity = '$identity'");

	print "Сделано";

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}
if ($action == "edit.do") {

	$title     = $_REQUEST['title'];
	$colord    = $_REQUEST['color1'];
	$isDefault = $_REQUEST['isDefault'];

	//снимем умолчания
	if ($isDefault == 'yes') $db -> query("UPDATE {$sqlname}relations SET isDefault = '' WHERE identity = '$identity'");

	if ($id < 1) {

		if ($title != '') {

			$db -> query("INSERT INTO {$sqlname}relations SET ?u", [
				'title'     => $title,
				'color'     => $colord,
				'isDefault' => ($isDefault == 'yes') ? $isDefault : 'no',
				'identity'  => $identity
			]);

			echo 'Сделано';

		}
		else {
			print 'Ошибка: Не указано название';
		}

	}
	else {

		if ($title != '') {

			$oldtitle = $db -> getOne("SELECT title FROM {$sqlname}relations where id = '$id' and identity = '$identity'");

			$db -> query("UPDATE {$sqlname}relations SET ?u WHERE id = '$id' AND identity = '$identity'", [
				'title'     => $title,
				'color'     => $colord,
				'isDefault' => ($isDefault == 'yes') ? $isDefault : 'no'
			]);

			$db -> query("UPDATE {$sqlname}clientcat SET ?u WHERE tip_cmr = '$oldtitle' AND identity = '$identity'", array('tip_cmr' => $title));

			echo 'Сделано';

		}
		else {
			print 'Ошибка: Не указано название';
		}

	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ($action == "edit") {

	if ($id > 0) {

		$result    = $db -> getRow("SELECT * FROM {$sqlname}relations where id='".$id."' and identity = '$identity'");
		$title     = $result["title"];
		$colord    = $result["color"];
		$isDefault = $result["isDefault"];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input name="action" type="hidden" value="edit.do" id="action"/>
		<input name="id" type="hidden" value="<?= $id ?>" id="<?= $id ?>"/>

		<div class="row">

			<div class="column12 grid-3 fs-12 right-text">Цвет:</div>
			<div class="column12 grid-9">
				<input name="color1" type="text" id="color1" value="<?= $colord ?>" size="7" maxlength="7"/>
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Название:</div>
			<div class="column12 grid-9">
				<INPUT name="title" type="text" class="wp97" id="title" value="<?= $title ?>">
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">&nbsp;</div>
			<div class="column12 grid-9">
				<label><input id="isDefault" name="isDefault" type="checkbox" value="yes" <?php if ($isDefault == 'yes') print 'checked' ?> />&nbsp;Использовать по-умолчанию&nbsp;</label>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<script type="text/javascript" src="/assets/js/jquery/jquery.colorPicker.js"></script>
	<script>

		$('#color1').colorPicker();

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
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
if ($action == "delete") {

	$tip = $db -> getOne("SELECT title FROM {$sqlname}relations WHERE id = '".$id."' and identity = '$identity'");

	?>
	<div class="zagolovok">Удалить тип "<?= $tip ?>"</div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="delete.do" id="action"/>

		<div class="infodiv">В случае удаления, данный тип отношений останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новый тип:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php
					$result_a = $db -> getAll("SELECT * FROM {$sqlname}relations WHERE id != '$id' and identity = '$identity' ORDER by title");
					foreach ($result_a as $data) {

						print '<option value="'.$data['id'].'">'.$data['title'].'</option>';

					}
					?>
				</select>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<script>

		$('#dialog').css('width', '600px');

		$('#form').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');
				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				return true;

			},
			success: function (data) {

				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
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

if ($action == '') {

	?>
	<h2>&nbsp;Раздел: "<?php echo ($fieldsNames['client']['tip_cmr'] ?? '<b class="red">Отключено</b>');?>"</h2>
	<h3 class="gray-dark fs-09 pl10">Стандарт: Типы отношений</h3>

	<TABLE id="zebra">
		<thead class="hidden-iphone sticked--top">
		<TR class="th40">
			<th class="wp40 text-center"><b>Название</b></th>
			<th></th>
			<th></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$result = $db -> getAll("SELECT * FROM {$sqlname}relations WHERE identity = '$identity' ORDER BY title");
		foreach ($result as $data_array) {

			$df = ($data_array['isDefault'] == 'yes') ? ' [ <b class="red">По-умолчанию</b> ]' : '';
			?>
			<TR class="ha th40">
				<TD>

					<div class="colordiv" style="background-color:<?= $data_array['color'] ?>"></div>&nbsp;&nbsp;
					<div class="fs-12 Bold inline"><span class="gray2">ID <?= $data_array['id'] ?>:</span> <?= $data_array['title'] ?></div><?= $df ?>

				</TD>
				<TD>

					<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data_array['id'] ?>&action=edit')" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
					<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data_array['id'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>

				</TD>
				<TD></TD>
			</TR>
			<?php
		}
		?>
		</tbody>
	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/19')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

<?php } ?>