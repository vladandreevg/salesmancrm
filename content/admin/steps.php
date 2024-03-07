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

$action     = $_REQUEST['action'];
$idcategory = $_REQUEST['idcategory'];
$title      = $_REQUEST['title'];
$content    = $_REQUEST['content'];

//$stepInHold = customSettings('stepInHold');

if ($action == "delete.do") {

	$multi = $_REQUEST['multi'];

	if ($multi == '') {

		// меняем этап в сделках
		$db -> query("UPDATE {$sqlname}dogovor SET idcategory = '".$_REQUEST['newid']."' WHERE idcategory = '".$_REQUEST['idcategory']."' and identity = '$identity'");

		// удаляем выбранный этап
		$db -> query("DELETE FROM {$sqlname}dogcategory WHERE idcategory = '".$_REQUEST['idcategory']."' and identity = '$identity'");

		// удаляем записи лога
		$db -> query("DELETE FROM {$sqlname}steplog WHERE step = '".$_REQUEST['idcategory']."' and identity = '$identity'");

		print "Сделано";

	}
	else {

		// меняем этап в сделках
		$db -> query("UPDATE {$sqlname}dogovor SET idcategory = '".$_REQUEST['newid']."' WHERE idcategory IN (".$multi.") and identity = '$identity'");

		// удаляем выбранный этап
		$db -> query("DELETE FROM {$sqlname}dogcategory WHERE idcategory IN (".$multi.") and identity = '$identity'");

		// удаляем записи лога
		$db -> query("DELETE FROM {$sqlname}steplog WHERE step IN (".$multi.") and identity = '$identity'");

		print "Сделано";

	}

	exit();

}
if ($action == "edit.on") {

	if ((int)$idcategory == 0) {

		$db -> query("INSERT INTO {$sqlname}dogcategory SET ?u", [
			'title'    => $title,
			'content'  => $content,
			'identity' => $identity
		]);
		$idcategory = $db -> insertId();

		print "Сделано";

	}
	else {

		$db -> query("UPDATE {$sqlname}dogcategory SET ?u WHERE idcategory = '$idcategory' and identity = '$identity'", [
			'title' => $title,
			'content' => $content
		]);

		print "Сделано";

	}

	// если этап отнесен к заморозке
	/*
	if($_REQUEST['inHold'] == 'on'){

		customSettings('stepInHold', 'put', [
			"params" => ["step" => (int)$idcategory, "input" => $_REQUEST['inHoldDate']]
		]);

	}
	*/
	// если этап пыл отнесен к заморозке, но сейчас не относится
	/*
	elseif ((int)$idcategory == $stepInHold['step']){

		customSettings('stepInHold', 'delete');

	}
	*/

	exit();
}

if ($action == "edit") {

	if($idcategory > 0) {

		$query   = "SELECT * FROM {$sqlname}dogcategory where idcategory = '$idcategory' and identity = '$identity'";
		$result  = $db -> getRow($query);
		$title   = $result["title"];
		$content = $result["content"];

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>
	<FORM method="post" action="content/admin/<?php echo $thisfile; ?>" name="form" id="form">
		<INPUT name="action" id="action" type="hidden" value="edit.on">
		<INPUT name="idcategory" id="idcategory" type="hidden" value="<?= $idcategory ?>">

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Этап:</div>
			<div class="column12 grid-9">
				<INPUT name="title" id="title" type="number" min="0" max="100" class="required w100" value="<?= $title ?>">
			</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Расшифровка:</div>
			<div class="column12 grid-9">
				<textarea name="content" class="required wp97" id="content"><?= $content ?></textarea>
			</div>

			<!--
			<div class="column12 grid-12 divider mt20 mb20">Признак заморозки</div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Заморозка</div>
			<div class="column12 grid-9">

				<label for="inHold" class="switch mt10">
					<input type="checkbox" name="inHold" id="inHold" value="on" <?=($idcategory == $stepInHold['step'] && $idcategory > 0 ? "checked" : "")?>>
					<span class="slider"></span>
				</label>

			</div>

			<div class="column12 grid-12 mt5 mb5"></div>

			<div class="column12 grid-3 fs-12 pt10 right-text">Поле даты</div>
			<div class="column12 grid-9">

				<select id="inHoldDate" name="inHoldDate" class="wp95">
					<?php
					$re = $db -> query( "select * from {$sqlname}field where fld_tip IN ('dogovor') and fld_on='yes' and (fld_name LIKE '%input%') and fld_temp = 'datum' and identity = '$identity' order by fld_order" );
					while ($da = $db -> fetch( $re )) {

						print '<option value="'.$da['fld_name'].'" '.($da['fld_name'] == $stepInHold['input'] ? 'selected' : '').'>'.$da['fld_title'].'</option>';

					}
					?>
				</select>

			</div>
			-->

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

	$tip = $db -> getOne("SELECT title FROM {$sqlname}dogcategory WHERE idcategory = '$idcategory' and identity = '$identity'");

	$multi = (array)$_REQUEST['multi'];
	$count = count($multi);
	$multi = implode(",", $multi);
	?>
	<div class="zagolovok">Удалить этап <?= $tip ?></div>
	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="form" id="form">
		<input type="hidden" id="idcategory" name="idcategory" value="<?= $idcategory ?>">
		<input name="action" type="hidden" value="delete.do" id="action"/>
		<input id="multi" name="multi" type="hidden" value="<?= $multi ?>">

		<div class="infodiv">В случае удаления, данный тип останется в существующих записях и они не будут участвовать в отчетах. Вы можете перевести их на новый тип.</div>

		<hr>

		<div class="row">

			<div class="column12 grid-3 fs-12 pt10 right-text">Новый тип:</div>
			<div class="column12 grid-9">
				<select name="newid" id="newid" class="required wp100">
					<option value="">--выбрать--</option>
					<?php

					$s = ($multi == '') ? "idcategory != '$idcategory'" : "idcategory NOT IN (".$multi.")";

					$result_a = $db -> getAll("SELECT * FROM {$sqlname}dogcategory WHERE $s and identity = '$identity' ORDER by title");
					foreach ($result_a as $data) {

						print '<option value="'.$data['idcategory'].'">'.$data['title'].'% - '.$data['content'].'</option>';

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

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
	print '<div class="warning text-center"><br>Доступ запрещен.<br>Обратитесь к администратору.<br><br></div>';
	exit();
}

if ($action == '') {
	?>

	<h2>&nbsp;Раздел: "<?php echo ($fieldsNames['dogovor']['idcategory'] ?? '<b class="red">Отключено</b>');?>"</h2>
	<h3 class="gray-dark fs-09 pl10">Стандарт: Этапы сделок</h3>

	<form id="list">

		<table id="catlist">
			<thead class="hidden-iphone sticked--top">
			<tr class="th35">
				<Th class="w50 text-center"></Th>
				<th class="w80 text-center">Этап</th>
				<th class="text-center">Расшифровка</th>
				<th class="w120">Действия</th>
				<Th></Th>
			</tr>
			</thead>
			<?php
			$i = 0;
			$result = $db -> getAll("SELECT * FROM {$sqlname}dogcategory WHERE identity = '$identity' ORDER by title");
			foreach ($result as $datar) {

				$pcolor = ' gray';

				if ($datar['title'] >= 20 && $datar['title'] < 60) $pcolor = ' green';
				elseif ($datar['title'] >= 60 && $datar['title'] < 90) $pcolor = ' blue';
				elseif ($datar['title'] >= 90 && $datar['title'] <= 100) $pcolor = ' red';
				?>
				<tr class="ha th40">
					<TD class="text-center">
						<input type="checkbox" onclick="chbCheck()" class="mm" name="multi[<?= $i ?>]" id="multi[<?= $i ?>]" value="<?= $datar['idcategory'] ?>">
					</TD>
					<td class="text-center">
						<label for="multi[<?= $i ?>]" onclick="chbCheck()"><span class="fs-10 Bold <?= $pcolor ?>"><?= $datar['title'] ?>%</span></label>
					</td>
					<td>
						<div class="gray2 Bold fs-09">ID <?= $datar['idcategory'] ?><?=($datar['idcategory'] == $stepInHold['step'] ? '<span>&nbsp;[ <i class="icon-snowflake-o bluemint"></i> Заморозка ]</span>' : '')?></div>
						<label for="multi[<?= $i ?>]" onclick="chbCheck()"><span class="fs-10 Bold"><?= $datar['content'] ?></span></label>
					</td>
					<td>

						<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit&idcategory=<?= $datar['idcategory'] ?>');" class="button dotted bluebtn"><i class="icon-pencil"></i></a>
						<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?idcategory=<?= $datar['idcategory'] ?>&action=delete')" class="button dotted redbtn"><i class="icon-cancel"></i></a>

					</td>
					<TD></TD>
				</tr>
			<?php
				$i++;
			}
			?>
		</table>

	</form>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>
		<a href="javascript:void(0)" onclick="multidel()" class="button redbtn box-shadow hidden amultidel" title="Удалить"><i class="icon-minus-circled"></i>Удалить выбранное</a>
		<a href="javascript:void(0)" onclick="clearCheck()" class="button greenbtn box-shadow hidden amultidel" title="Снять выделение"><i class="icon-th"></i>Снять выделение</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/32')" title="Документация"><i class="icon-help"></i></div>

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