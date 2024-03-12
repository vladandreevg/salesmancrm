<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */

/* ============================ */

use Salesman\Elements;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$element = new Elements();

$action = $_REQUEST['action'];
$ccid   = $_REQUEST['ccid'];
$title  = $_REQUEST['title'];

$iroles = $element::$roles;

if ($action == "delete") {

	$db -> query("DELETE FROM {$sqlname}complect_cat WHERE ccid = '$ccid' and identity = '$identity'");
	$db -> query("DELETE FROM {$sqlname}complect WHERE ccid = '$ccid' and identity = '$identity'");

	print 'Сделано';
	exit;

}

if ($action == "edit.on") {

	$dstep = $_REQUEST['idcategory'];
	$role  = yimplode(",", $_REQUEST['role']);
	$users = yimplode(",", $_REQUEST['users']);

	if ($ccid > 0) {
		$db -> query("UPDATE {$sqlname}complect_cat SET title = '$title', dstep = '$dstep', role = '$role', users = '$users' WHERE ccid = '$ccid' and identity = '$identity'");
	}

	else {
		$db -> query("INSERT INTO {$sqlname}complect_cat SET ?u", [
			"title"    => $title,
			"dstep"    => $dstep,
			"role"     => $role,
			"users"    => $users,
			"identity" => $identity
		]);
	}

	print 'Сделано';
	exit;

}

if ($action == "edit_order") {

	$table1 = yexplode(';', yimplode(';', $_REQUEST['table-1']));

	$count1 = count($_REQUEST['table-1']);
	$err    = 0;

	//Обновляем данные для текущей записи
	for ($i = 1; $i < $count1; $i++) {

		$db -> query("update {$sqlname}complect_cat set corder = '$i' where ccid = '".$table1[$i]."' and identity = '$identity'");

	}

	print "Сделано";
	exit;

}

if ($action == "edit") {

	if ($ccid > 0) {

		$result     = $db -> getRow("SELECT * FROM {$sqlname}complect_cat where ccid='".$ccid."' and identity = '$identity'");
		$title      = $result["title"];
		$idcategory = $result["dstep"];
		$sroles     = yexplode(",", $result["role"]);
		$users      = yexplode(",", $result["users"]);

	}
	?>
	<div class="zagolovok">Редактирование</div>
	<form method="post" action="/content/admin/<?php
	echo $thisfile; ?>" enctype="multipart/form-data" name="form" id="form" autocomplete="off">
		<input type="hidden" id="action" name="action" value="edit.on">
		<input type="hidden" id="ccid" name="ccid" value="<?= $ccid ?>">

		<div id="formtabs" style="overflow-y: auto; overflow-x: hidden; max-height: 80vh" class="p5">

			<div id="divider">Новое название</div>
			<div class="flex-container">

				<div class="flex-string wp100">
					<input name="title" type="text" id="title" class="required wp100" value="<?= $title ?>">
				</div>

			</div>

			<div id="divider">Связанный этап</div>
			<div class="flex-container">

				<div class="flex-string wp100">

					<?php
					print $element -> StepSelect("idcategory", [
						"class" => "wp100",
						"sel"   => $idcategory
					]);
					?>
					<div class="fs-07 gray">Этап сделки, связанный с контрольной точкой (КТ). Отметка выполнения КТ переведет сделку на указанный этап.</div>

				</div>

			</div>

			<div id="divider">Доступ для ролей</div>
			<div class="flex-container box--child">

				<?php
				foreach ($iroles as $role) {

					print '
					<div class="flex-string wp50 p5 pl20">

						<label><input name="role[]" type="checkbox" id="role[]" value="'.$role.'" '.( in_array($role, $sroles) ? 'checked' : '' ).'>&nbsp;'.$role.'</label>
	
					</div>
					';

				}
				?>

			</div>

			<div id="divider">или<br><b>Доступ для сотрудников</div>
			<div class="flex-container">

				<div class="flex-string wp100">

					<?php
					print $element -> UsersSelect("users[]", [
						"class"    => "wp100 multiselect",
						"sel"      => $users,
						"multiple" => true,
						"active"   => true
					]);
					?>

				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</form>
	<?php
}

if ($action == '') {
	?>
	<h2>&nbsp;Раздел: "Контрольные точки"</h2>

	<table id="table-1">
		<thead class="hidden-iphone sticked--top">
		<tr class="th40">
			<th class="yw60 text-center">Порядок</th>
			<th class="text-left">Название</th>
			<th class="yw80 text-center">Этап
				<i class="icon-info-circled blue" title="Этап сделки, связанный с контрольной точкой (КТ). Отметка выполнения КТ переведет сделку на указанный этап."></i>
			</th>
			<Th class="yw150 text-left">Доступ для Ролей</Th>
			<Th class="yw150 text-left">Доступ для Сотрудников</Th>
			<th class="yw120 text-center"></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$query  = "SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER by corder";
		$result = $db -> getAll($query);
		foreach ($result as $data_array) {

			if ($data_array['dstep'] > 0) {
				$step = current_dogstepname($data_array['dstep']).'%';
			}
			else {
				$step = '';
			}
			?>
			<tr class="ha th40" id="<?= $data_array['ccid'] ?>">
				<td class="text-center"><?= $data_array['corder'] ?></td>
				<td>
					<div class="fs-11 Bold"><?= $data_array['title'] ?></div>
				</td>
				<td class="text-center"><?= $step ?></td>
				<TD>
					<div class="tagbox">
						<?php
						print yimplode("", yexplode(",", $data_array['role']), '<div class="tag">', '</div>')
						?>
					</div>
				</TD>
				<TD>
					<div class="tagbox">
						<?php
						$users = yexplode(",", $data_array['users']);

						foreach ($users as $user) {

							if ((int)$user > 0) {
								print '<div class="tag">'.current_user((int)$user).'</div>';
							}

						}
						?>
					</div>
				</TD>
				<td class="text-center">

					<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php
					echo $thisfile; ?>?action=edit&ccid=<?= $data_array['ccid'] ?>');" class="button dotted bluebtn"><i class="icon-pencil"></i></a>
					<a href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить категорию?');if (cf)deletePoint(<?= $data_array['ccid'] ?>)" class="button dotted redbtn"><i class="icon-cancel-circled"></i></a>

				</td>
			</tr>
		<?php
		} ?>
		</tbody>
	</table>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php
		echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('/content/admin/<?php
	echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/35')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<?php
}
?>

<script>

	$('#dialog').css('width', '802px');

	$(function () {

		$(".multiselect").multiselect({sortable: true, searchable: true});
		$(".connected-list").css('height', "150px");

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

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);

				$('#resultdiv').empty();
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();
			}
		});

		$('#dialog').center();

	});

	$("#table-1").tableDnD({
		onDragClass: "tableDrag",
		onDrop: function (table, row) {
			var str = '' + $('#table-1').tableDnDSerialize();
			var url = '/content/admin/<?php echo $thisfile; ?>?action=edit_order&';

			$.post(url, str, function (data) {
				$('#message').empty().css('display', 'block').html(data).fadeOut(10000);
				//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
				razdel(hash);
			});
		}
	});

	function deletePoint(id) {

		$.get('/content/admin/<?php echo $thisfile; ?>?action=delete&ccid=' + id, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			razdel(hash);

		});

	}

</script>