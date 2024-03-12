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

$action    = $_REQUEST['action'];
$id        = (int)$_REQUEST['id'];
$title     = $_REQUEST['title'];
$isDefault = $_REQUEST['isDefault'];

if ($action == "delete.do") {

	//обрабатываем все записи
	$db -> query("UPDATE {$sqlname}dogovor SET direction = '$_REQUEST[newid]' WHERE direction = '$_REQUEST[id]' and identity = '$identity'");

	//удаляем тип
	$db -> query("DELETE FROM {$sqlname}direction WHERE id = '$_REQUEST[id]' and identity = '$identity'");

	print "Сделано";

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}
if ($action == "edit.on") {

	if ($isDefault == 'yes') {
		$db -> query("update {$sqlname}direction set isDefault = '' where identity = '$identity'");
	}

	if ($id < 1) {

		$db -> query("INSERT INTO {$sqlname}direction SET ?u", [
			'title'     => $title,
			'isDefault' => ($isDefault == 'yes') ? $isDefault : 'no',
			'identity'  => $identity
		]);

	}
	else {

		$db -> query("UPDATE {$sqlname}direction SET ?u WHERE id = '$id' and identity = '$identity'", [
			'title'     => $title,
			'isDefault' => ($isDefault == 'yes') ? $isDefault : 'no'
		]);

	}

	unlink($rootpath."/cash/".$fpath."settings.all.json");

	exit();

}
if ($action == "edit") {

	if ($id > 0) {

		$result    = $db -> getRow("SELECT * FROM {$sqlname}direction where id = '".$id."' and identity = '$identity'");
		$title     = $result["title"];
		$isDefault = $result["isDefault"];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM method="post" action="/content/admin/<?php echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.on">
		<INPUT name="id" id="id" type="hidden" value="<?= $id ?>">

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Название:</div>
			<div class="column12 grid-9">
				<INPUT name="title" type="text" class="wp97 required" id="title" value="<?= $title ?>">
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">&nbsp;</div>
			<div class="column12 grid-9">
				<label><input id="isDefault" name="isDefault" type="checkbox" value="yes" <?php if ($isDefault == 'yes') print 'checked' ?> />&nbsp;Использовать по-умолчанию&nbsp;</label>
			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose();" class="button">Отмена</A>

		</div>

	</FORM>
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

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ($action == "delete") {

	$tip = $db -> getOne("SELECT title FROM {$sqlname}direction WHERE id = '".$id."' and identity = '$identity'");

	?>
	<div class="zagolovok">Удалить направление "<?= $tip ?>"</div>
	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="id" name="id" value="<?= $id ?>">
		<input name="action" type="hidden" value="delete.do" id="action"/>

		<div class="infodiv">В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новое:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php
					$result_a = $db -> getAll("SELECT * FROM {$sqlname}direction WHERE id != '$id' and identity = '$identity' ORDER by title");
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

		if(!isMobile) $('#dialog').css('width', '600px');

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
if ($action == "") {
	?>
	<h2>&nbsp;Раздел: "<?php echo ($fieldsNames['dogovor']['direction'] ?? '<b class="red">Отключено</b>');?>"</h2>
	<h3 class="gray-dark fs-09 pl10">Стандарт: Направления деятельности</h3>

	<div class="flex-container box--child p10 fs-11 no-border graybg Bold hidden-iphone sticked--top">

		<div class="flex-string wp70">Направление</div>
		<div class="flex-string wp30 hidden-iphone">Действие</div>

	</div>

	<?php
	$query  = "SELECT * FROM {$sqlname}direction WHERE identity = '$identity' ORDER by title";
	$result = $db -> getAll($query);
	foreach ($result as $datar) {

		if ($datar['isDefault'] == 'yes') $df = ' [ <b class="red">По-умолчанию</b> ]';
		else $df = '';

		print '
		<div class="flex-container box--child p10 border-bottom relativ ha">
	
			<div class="flex-string wp70">
			
				<div class="fs-12 Bold pt5"><span class="gray2">ID '.$datar['id'].':</span> '.$datar['title'].'&nbsp;'.$df.'</div>
				
			</div>
			<div class="flex-string wp30 hidden-iphone">
			
				<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?action=edit&id='.$datar['id'].'\');" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
				<A href="javascript:void(0)" onclick="doLoad(\'/content/admin/'.$thisfile.'?id='.$datar['id'].'&action=delete\')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>
				
			</div>
	
		</div>
		';

	}
	?>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn green" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/29')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

<?php
}
?>