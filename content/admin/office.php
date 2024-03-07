<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

error_reporting(0);

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
$title  = $_REQUEST['title'];

if ($action == "delete.do") {

	//обрабатываем все записи
	$db -> query("UPDATE {$sqlname}user SET office = '".$_REQUEST['newid']."' WHERE office = '$id' and identity = '$identity'");

	$db -> query("DELETE FROM {$sqlname}office_cat WHERE idcategory = '$id' and identity = '$identity'");
	echo 'Запись удачно удалена';

}

if ($action == "edit.on") {

	if ($id == 0) {

		$db -> query("INSERT INTO {$sqlname}office_cat SET ?u", [
			'title'    => $title,
			'identity' => $identity
		]);

	}
	else {

		$db -> query("UPDATE {$sqlname}office_cat SET ?u WHERE idcategory = '$id' and identity = '$identity'", [
			'title' => $title
		]);

	}

	echo 'Сделано';

	exit();

}

if ($action == "edit") {

	$result = $db -> getRow("SELECT * FROM {$sqlname}office_cat WHERE idcategory = '$id' and identity = '$identity'");
	$title  = $result["title"];

	?>
	<div class="zagolovok">Изменить/добавить</div>
	<form action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.on">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Название:</div>
			<div class="column12 grid-9">
				<INPUT name="title" type="text" class="wp97" id="title" value="<?= $title ?>">
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</div>

	</form>
	<script>
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

	?>
	<div class="zagolovok">Удалить</div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="delete.do" id="action"/>

		<div class="infodiv">В случае удаления, данный Офис останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новое:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php
					$res = $db -> getAll("SELECT * FROM {$sqlname}office_cat WHERE idcategory != '$id' and identity = '$identity' ORDER by title");
					foreach ($res as $data) {
						?>
						<option value="<?= $data['idcategory'] ?>"><?= $data['title'] ?></option>
					<?php } ?>
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

		if (!isMobile) $('#dialog').css('width', '600px');

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
?>

<h2>&nbsp;Раздел: "Офисы"</h2>

<TABLE>
	<thead class="hidden-iphone sticked--top">
	<TR class="th35">
		<Th class="text-center">Адрес офиса</Th>
		<Th class="w120 text-center">Действия</Th>
	</TR>
	</thead>
	<tbody>
	<?php
	$result = $db -> getAll("SELECT * FROM {$sqlname}office_cat WHERE identity = '$identity' ORDER BY title");
	foreach ($result as $data) {
		?>
		<TR class="ha th40">
			<TD class="text-left"><B><?= $data['title'] ?></B></TD>
			<TD class="w120 text-center">

				<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data['idcategory'] ?>&action=edit')" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
				<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить категорию?');if (cf)refresh('contentdiv','content/admin/<?php echo $thisfile; ?>?id=<?= $data['idcategory'] ?>&action=delete');" class="button dotted redbtn"><i class="icon-cancel"></i></A>

			</TD>
		</TR>
	<?php } ?>
	</tbody>
</TABLE>

<div class="pagerefresh refresh--icon admn green" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/15')" title="Документация"><i class="icon-help"></i></div>

<div class="space-100"></div>