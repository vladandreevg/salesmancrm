<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting(0);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename(__FILE__);

$action = $_REQUEST['action'];

if ($action == '') {
	?>

	<h2>&nbsp;Раздел: "Настройка модуля Прайс"</h2>

	<DIV id="formtabs" style="border:0">

		<div id="tab-form-3">

			<table>
				<thead class="hidden-iphone sticked--top">
				<tr>
					<TH width="100" class="nodrop">Имя поля</TH>
					<TH width="200" class="nodrop">Название</TH>
					<TH width="80" class="nodrop">Наценка, %</TH>
					<TH width="50" class="nodrop">Вкл.</TH>
					<TH width="100" class="nodrop">Действие</TH>
					<TH></TH>
				</tr>
				</thead>
				<?php
				$result = $db -> getAll("SELECT * FROM {$sqlname}field WHERE fld_tip='price' and fld_name != 'ztitle' and identity = '$identity' order by fld_name");
				foreach ($result as $data_array) {
					?>
					<tr class="ha" id="<?= $data_array['fld_id'] ?>" height="40">
						<td><strong><?= $data_array['fld_name'] ?></strong></td>
						<td><?= $data_array['fld_title'] ?></td>
						<td align="center"><?= $data_array['fld_var'] ?></td>
						<td align="center"><?php
							if ($data_array['fld_on'] == 'yes') print 'да' ?></td>
						<td align="center">
							<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php
							echo $thisfile; ?>?action=edit&fld_id=<?= $data_array['fld_id'] ?>&tip=price');" title="Изменить"><i class="icon-pencil blue"></i></A>
						</td>
						<td></td>
					</tr>
					<?php
				} ?>
			</table>

		</div>

		<hr>
		<a href="javascript:void(0)" onclick="addField()" class="button ml10">Добавить поле</a>

	</DIV>

	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/36')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<?php
}

if ($action == 'addfield') {

	$field = [];

	//считаем все доп.поля
	$result = $db -> getAll("SELECT fld_name FROM {$sqlname}field WHERE fld_name LIKE '%price%' AND fld_name != 'price_in' AND fld_tip = 'price' and identity = '$identity'");
	foreach ($result as $data) {

		$field[$data['fld_name']] = (int)preg_replace("/\D/", "", $data['fld_name']);

	}

	$last = max($field);

	$next = (int)$last + 1;

	$field = $db -> getRow("SHOW COLUMNS FROM {$sqlname}price LIKE 'price_{$next}'");
	if (!empty($field['Field'])) {
		$next++;
	}

	$db -> query("ALTER TABLE `{$sqlname}price` ADD `price_{$next}` DOUBLE(20,2) NULL DEFAULT '0.00' AFTER `price_{$last}`");

	$fieldAdd = [
		"fld_tip"   => 'price',
		"fld_name"  => "price_{$next}",
		"fld_title" => "Уровень ??",
		"fld_on"    => 'yes',
		"fld_var"   => '5',
		"identity"  => $identity
	];
	$db -> query("INSERT INTO {$sqlname}field SET ?u", arrayNullClean($fieldAdd));
	$id = $db -> insertId();

	if ($hooks) {
		$hooks -> do_action("price_addfield", $fieldAdd);
	}

	$pretext = 'Поле добавлено. Предлагаем задать его параметры.';

	print json_encode_cyr([
		"text" => $pretext,
		"id"   => $id
	]);

	exit();

}

if ($action == 'edit') {

	$result       = $db -> getRow("select * from {$sqlname}field where fld_id = '".$_REQUEST['fld_id']."' and identity = '$identity'");
	$fld_name     = $result["fld_name"];
	$fld_title    = $result["fld_title"];
	$fld_type     = $result["fld_type"];
	$fld_required = $result["fld_required"];
	$fld_on       = $result["fld_on"];
	$fld_tip      = $result["fld_tip"];
	$fld_order    = $result["fld_order"];
	$fld_temp     = $result["fld_temp"];
	$fld_var      = $result["fld_var"];
	$fld_sub      = $result["fld_sub"];

	$exclude = [
		'clid',
		'title',
		'pid',
		'person',
		'marg',
		'mcid',
		'idcategory',
		'period'
	];

	if (in_array($fld_name, $exclude)) {
		$d = "readonly";
	}
	?>
	<DIV class="zagolovok">Изменение поля</DIV>
	<FORM action="/content/admin/<?=$thisfile?>" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="edit.on">
		<INPUT name="fld_id" type="hidden" id="fld_id" value="<?= $_REQUEST['fld_id'] ?>">
		<INPUT name="fld_tip" type="hidden" id="fld_tip" value="<?= $fld_tip ?>">
		<INPUT name="fld_name" type="hidden" id="fld_name" value="<?= $fld_name ?>">
		<INPUT name="tip" type="hidden" id="tip" value="<?= $_REQUEST['tip'] ?>">

		<?php
		$hooks -> do_action("price_formfield_before", $_REQUEST);
		?>

		<table id="zebra">
			<tr height="25" class="noborder">
				<td width="180"><b>Название:</b></td>
				<td><input name="fld_title" id="fld_title" type="text" value="<?= $fld_title ?>" style="width:98%"></td>
			</tr>
			<?php
			if ($fld_name != 'price_in') { ?>
				<tr height="25" class="noborder">
					<td valign="top">
						<div style="margin-top: 5px;"><b>Наценка по-умолчанию:</b></div>
					</td>
					<td><input type="text" name="fld_var" id="fld_var" value="<?= $fld_var ?>" style="width:100px;">%</td>
				</tr>
			<?php
			} ?>
			<?php
			if ($fld_name != 'price_in') { ?>
				<tr height="25" class="noborder">
					<td valign="top">
						<div style="margin-top: 5px;"><b>Скрыть колонку:</b></div>
					</td>
					<td>
						<input name="fld_sub" id="fld_sub" type="checkbox" <?php print ($fld_sub == 'hidden') ? "checked" : "" ?> value="hidden">
					</td>
				</tr>
			<?php
			} ?>
			<tr height="25" class="noborder">
				<td><b>Включено:</b></td>
				<td>
					<?php
					if ($fld_name != 'price_in') { ?>
						<input name="fld_on" id="fld_on" type="checkbox" <?= $d ?> <?php
						if ($fld_on == 'yes') print "checked" ?> value="yes">
						<?php
					}
					else {
						?>
						<b class="red">!</b> Это поле всегда должно быть включено
						<input name="fld_on" id="fld_on" type="hidden" value="yes">
						<?php
					} ?>
				</td>
			</tr>
		</table>

		<?php
		if ($fld_name != 'price_in') { ?>
		<hr>

		<div class="flex-container mt10 mb10">
			<div class="flex-string text-center Bold blue">
				<label>
				<input name="recalc" id="recalc" type="checkbox" value="yes"> Пересчитать прайс
				</label>
			</div>
		</div>
		<?php } ?>

		<hr>

		<div class="button--pane text-right">
			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>
		</div>
	</FORM>
	<?php
	$hooks -> do_action("price_formfield_after", $_REQUEST);
}
if ($action == "edit.on") {

	$fld_id    = $_REQUEST['fld_id'];
	$fld_tip   = $_REQUEST['fld_tip'];
	$fld_title = $_REQUEST['fld_title'];
	$fld_var   = $_REQUEST['fld_var'];
	$fld_on    = $_REQUEST['fld_on'];
	$fld_sub   = $_REQUEST['fld_sub'];
	$fld_name  = $_REQUEST['fld_name'];
	$tip       = $fld_tip;

	//Обновляем данные для текущей записи
	//if (mysql_query("update {$sqlname}field set fld_title = '$fld_title', fld_temp = '$fld_temp', fld_required = '$fld_required', fld_on = '$fld_on' where fld_id = $fld_id")) print "Запись обновлена";

	if ($db -> query("UPDATE {$sqlname}field set ?u WHERE fld_id = '$fld_id' and identity = '$identity'", [
		"fld_title" => $fld_title,
		"fld_var"   => $fld_var,
		"fld_on"    => $fld_on,
		"fld_sub"   => $fld_sub
	])) {
		print "Запись обновлена";
	}
	
	if($_REQUEST['recalc'] == 'yes') {
	
		$db -> query("UPDATE {$sqlname}price SET $fld_name = (price_in * (1 + $fld_var / 100))");
		
	}

	exit();

}
?>
<script>
	$(function () {
		$('#Form').ajaxForm({
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

				//$("#contentdiv").load('content/admin/<?php echo $thisfile; ?>?tip=<?=$_REQUEST['tip']?>');
				razdel(hash);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);
				DClose();

			}
		});
	})

	function addField() {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif">Пожалуйста подождите...</div>');

		$.get('/content/admin/<?php echo $thisfile; ?>?action=addfield', function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data.text);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit&fld_id=' + data.id);

			razdel(hash);

		}, 'json');

	}
</script>