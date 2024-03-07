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
$title  = $_REQUEST['title'];

if ($action == "delete.do") {

	$multi = $_REQUEST['multi'];
	$id    = (int)$_REQUEST['idcategory'];

	if ($multi == '') {

		$db -> query("UPDATE {$sqlname}clientcat SET territory = '".$_REQUEST['newid']."' WHERE territory = '$id' and identity = '$identity'");
		$db -> query("DELETE FROM {$sqlname}territory_cat WHERE idcategory = '$id' and identity = '$identity'");

		print "Сделано";

	}
	else {

		$db -> query("UPDATE {$sqlname}clientcat SET territory = '".$_REQUEST['newid']."' WHERE territory IN (".$multi.") and identity = '$identity'");
		$db -> query("DELETE FROM {$sqlname}territory_cat WHERE idcategory IN (".$multi.") and identity = '$identity'");

		print "Сделано";

	}

	exit();

}
if ($action == "edit.do") {

	if ($title != '') {

		if ($id == 0) {//добавляем

			$titles = explode("\n", $_REQUEST['title']);
			$good   = 0;

			foreach ($titles as $title) {

				$db -> query("INSERT INTO {$sqlname}territory_cat SET ?u", [
					'title'    => trim($title),
					'identity' => $identity
				]);

				$good++;

			}

			print "Сделано";

		}
		else {//редактируем

			$db -> query("UPDATE {$sqlname}territory_cat SET title = '$title' WHERE idcategory = '$id' and identity = '$identity'");
			echo 'Сделано';

		}

	}
	else print 'Ошибка: Не указано название';

	exit();
}

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {

	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();

}

if ($action == "edit") {

	if ($id > 0) {

		$result = $db -> getRow("SELECT * FROM {$sqlname}territory_cat WHERE idcategory = '$id' and identity = '$identity'");
		$title  = $result["title"];
		$id     = $result["idcategory"];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input name="action" id="action" type="hidden" value="edit.do"/>
		<input name="id" type="hidden" value="<?= $id ?>" id="id">

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Название:</div>
			<div class="column12 grid-9">
				<?php
				if ($id > 0) {

					print '<INPUT name="title" type="text" id="title" class="wp97" value="'.$title.'">';

				}
				else {

					print '<textarea name="title" id="title" rows="5" class="wp97">'.$title.'</textarea>';

				}
				?>
			</div>

		</div>

	</FORM>

	<div class="infodiv">Каждый новый вариант начинайте с новой строки</div>

	<hr>

	<div class="button--pane text-right">

		<A href="javascript:void(0)" onclick="$('#form').trigger('submit')" class="button">Сохранить</A>&nbsp;
		<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

	</div>

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

	$result = $db -> getRow("SELECT * FROM {$sqlname}territory_cat WHERE idcategory = '$id' and identity = '$identity'");
	$tip    = $result['title'];
	$multi  = (array)$_REQUEST['multi'];
	$count  = count($multi);
	$multi  = implode(",", $multi);

	?>
	<div class="zagolovok">Удалить <?= $tip ?></div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="idcategory" name="idcategory" value="<?= $id ?>">
		<input id="multi" name="multi" type="hidden" value="<?= $multi ?>">
		<input name="action" type="hidden" value="delete.do" id="action">

		<div class="infodiv">В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новый тип:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php

					$s = ($multi == '') ? "idcategory != '$id'" : "idcategory NOT IN (".$multi.")";

					$result_a = $db -> getAll("SELECT * FROM {$sqlname}territory_cat WHERE $s and identity = '$identity' ORDER by title");
					foreach ($result_a as $data) {

						print '<option value="'.$data['idcategory'].'">'.$data['title'].'</option>';

					}
					?>
				</select>
			</div>

		</div>
		<?php
		if ($count > 0) {
			print '<div class="infodiv text-center">Удаляется <b>'.$count.'</b> записей.</div>';
		}
		?>

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
	<h2>&nbsp;Раздел: "<?php echo ($fieldsNames['client']['territory'] ?? '<b class="red">Отключено</b>');?>"</h2>
	<h3 class="gray-dark fs-09 pl10">Стандарт: Территории</h3>

	<form id="list">

		<TABLE id="catlist">
			<thead class="hidden-iphone sticked--top">
			<TR class="th40">
				<th class="w50 text-center"></th>
				<th class="w400 text-left"><b>Название</b></th>
				<th class="w120 text-center"></th>
				<th></th>
			</TR>
			</thead>
			<tbody>
			<?php
			$i      = 0;
			$result = $db -> getAll("SELECT * FROM {$sqlname}territory_cat WHERE identity = '$identity' ORDER BY title");
			foreach ($result as $data_array) {
				?>
				<TR class="ha th40">
					<TD class="text-center">
						<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[<?= $i ?>]" id="multi[<?= $i ?>]" value="<?= $data_array['idcategory'] ?>">
					</TD>
					<TD>
						<label for="multi[<?= $i ?>]" onclick="chbCheck()"><span style="line-height: 25px; display:block;"><B><span class="gray2">ID <?= $data_array['idcategory'] ?>:</span> <?= $data_array['title'] ?></B></span></label>
					</TD>
					<TD class="text-center">

						<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data_array['idcategory'] ?>&action=edit')" class="button dotted bluebtn"><i class="icon-pencil"></i></A>
						<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data_array['idcategory'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>

					</TD>
					<TD class="text-center"></TD>
				</TR>
				<?php
				$i++;
			}
			?>
			</tbody>
		</TABLE>

	</form>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
		<a href="javascript:void(0)" onclick="multidel()" class="button redbtn box-shadow hidden amultidel" title="Удалить"><i class="icon-minus-circled"></i>Удалить выбранное</a>
		<a href="javascript:void(0)" onclick="clearCheck()" class="button greenbtn box-shadow hidden amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/21')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		function chbCheck() {

			var col = $('#catlist input:checkbox:checked').length;

			if (col > 0) $('.amultidel').removeClass('hidden');
			else $('.amultidel').addClass('hidden');

		}

		function clearCheck() {

			$('#catlist input:checkbox:checked').prop('checked', false);
			$('.amultidel').addClass('hidden');

		}

		function multidel() {
			var str = $('#list').serialize();
			doLoad('content/admin/<?php echo $thisfile; ?>?action=delete&' + str);
		}

	</script>
<?php } ?>