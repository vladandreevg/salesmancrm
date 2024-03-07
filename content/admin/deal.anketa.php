<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\DealAnketa;

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
$id     = $_REQUEST['id'];
$ida    = $_REQUEST['ida'];
$name   = $_REQUEST['name'];
$pwidth = $_REQUEST['pwidth'];
$tip    = $_REQUEST['tip'];
$ord    = $_REQUEST['ord'];

if ($action == '') $action = 'list';

if ($tip == 'divider') $pwidth = 100;

$ttip = [
	'input',
	'text',
	'number',
	'datum',
	'datetime'
];

/**
 * Добавим таблицу для списка базовых анкет
 */
$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}deal_anketa_list'");
if ($da == 0) {

	$db -> query("
			CREATE TABLE {$sqlname}deal_anketa_list (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`active` INT(1) NOT NULL DEFAULT '1' COMMENT 'Активность анкеты',
				`datum` DATETIME NOT NULL COMMENT 'Дата создания',
				`datum_edit` DATETIME NOT NULL COMMENT 'Дата изменения',
				`title` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Название анкеты',
				`content` TEXT NULL COMMENT 'Описание анкеты',
				`iduser` INT(10) NULL DEFAULT NULL COMMENT 'id Сотрудника-автора',
				`identity` INT(10) NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			COMMENT='Список базовых анкет для сделок'
			ENGINE=InnoDB
		");

}

/**
 * Добавим таблицу для списка базовых анкет
 */
$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}deal_anketa_base'");
if ($da == 0) {

	$db -> query("
			CREATE TABLE {$sqlname}deal_anketa_base (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`block` INT(11) NOT NULL DEFAULT '0' COMMENT 'id блока',
				`ida` INT(11) NOT NULL COMMENT 'id анкеты',
				`name` VARCHAR(255) NOT NULL COMMENT 'Название поля',
				`tip` VARCHAR(10) NOT NULL COMMENT 'Тип поля',
				`value` TEXT NULL COMMENT 'Возможные значения',
				`ord` INT(5) NULL DEFAULT NULL COMMENT 'Порядок вывода',
				`pole` VARCHAR(10) NULL DEFAULT NULL COMMENT 'id поля',
				`pwidth` INT(3) NULL DEFAULT '50' COMMENT 'ширина поля',
				`identity` INT(30) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`)
			)
			COMMENT='База полей для анкеты'
			ENGINE=InnoDB
		");

}

/**
 * Добавим таблицу для списка базовых анкет
 */
$da = $db -> getOne("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = '$database' and TABLE_NAME = '{$sqlname}deal_anketa'");
if ($da == 0) {

	$db -> query("
			CREATE TABLE {$sqlname}deal_anketa (
				`id` INT(11) NOT NULL AUTO_INCREMENT,
				`idbase` INT(11) NOT NULL DEFAULT '0' COMMENT 'id поля анкеты',
				`ida` INT(10) NOT NULL COMMENT 'id анкеты',
				`did` INT(11) NULL DEFAULT NULL COMMENT 'id сделки',
				`clid` INT(11) NULL DEFAULT NULL COMMENT 'id клиента',
				`value` VARCHAR(255) NULL,
				`identity` INT(30) NOT NULL DEFAULT '1',
				PRIMARY KEY (`id`),
				FULLTEXT INDEX `value` (`value`)
			)
			COMMENT='Значения для анкет по сделкам'
			ENGINE=InnoDB
		");

}

/**
 * обработка - очистка от говна
 */
if (!in_array($tip, $ttip)) {

	$value = str_replace("\n", ";", $_REQUEST['value']);

	$varr = yexplode(";", $value);

	for ($g = 0; $g < count($varr); $g++) {

		$varr[ $g ] = trim(str_replace([
			"\\n\\r",
			"\\n",
			"\\r",
			","
		], "", $varr[ $g ]));

	}

	$value = implode(";", $varr);

}
else $value = $_REQUEST['value'];

/**
 * Удаляем поле
 */
if ($action == "delete.item") {

	$id    = $_REQUEST['id'];
	$ida   = $_REQUEST['ida'];
	$block = $_REQUEST['block'];

	$db -> query("DELETE FROM {$sqlname}deal_anketa_base WHERE id = '$id' AND ida = '$ida' AND identity = '$identity'");
	$db -> query("DELETE FROM {$sqlname}deal_anketa WHERE idbase = '$id' AND identity = '$identity'");

	/**
	 * если надо удалить все вложенные поля
	 */
	if ($block == 'yes') {

		$db -> query("DELETE FROM {$sqlname}deal_anketa_base WHERE block = '$id' AND ida = '$ida' AND identity = '$identity'");

	}

	$action = 'list.item';

	print "Выполнено";
	exit();

}

/**
 * Добавляем/Редактируем поле
 */
if ($action == "edit.do.item") {

	$id     = $_REQUEST['id'];
	$ida    = $_REQUEST['ida'];
	$name   = $_REQUEST['name'];
	$block  = $_REQUEST['block'];
	$pwidth = $_REQUEST['pwidth'];
	$tip    = $_REQUEST['tip'];

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}deal_anketa_base SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean([
			'name'   => $name,
			'tip'    => $tip,
			'block'  => $block,
			'value'  => $value,
			'pwidth' => $pwidth
		]));

		print "Готово";

	}
	else {

		$db -> query("INSERT INTO {$sqlname}deal_anketa_base SET ?u", arrayNullClean([
			'ida'      => $ida,
			'name'     => $name,
			'tip'      => $tip,
			'value'    => $value,
			'block'    => $block,
			'pwidth'   => $pwidth,
			'identity' => $identity
		]));

		$id    = $db -> insertId();
		$pname = 'pole'.$id;

		if ($block < 1) $count = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}deal_anketa_base WHERE ida = '$ida'") + 1;
		else $count = $db -> getOne("SELECT MAX(ord) FROM {$sqlname}deal_anketa_base WHERE ida = '$ida' AND block = '$block'") + 1;

		$db -> query("UPDATE {$sqlname}deal_anketa_base SET ?u WHERE id = '$id' AND identity = '$identity'", [
			'pole' => $pname,
			'ord'  => $count
		]);

		print "Готово";

	}

	exit();

}

/**
 * Изменим порядок сортировки
 */
if ($action == "edit.order.item") {


	if ($_REQUEST['table'] != '') {

		$block   = $_REQUEST['table'];
		$table   = $_REQUEST[ 'table-'.$block ];
		$blockid = $_REQUEST['blockid'];

		//Обновляем данные для текущей записи
		foreach ($table as $i => $row) $db -> query("UPDATE {$sqlname}deal_anketa_base SET ord = '".($i + 1)."' WHERE id = '$row' and identity = '$identity'");

	}
	else {

		$table = $_REQUEST['table-1'];

		//Обновляем данные для текущей записи
		foreach ($table as $i => $row) $db -> query("UPDATE {$sqlname}deal_anketa_base SET ord = '$i' WHERE id = '$row' and identity = '$identity'");

	}

	print "Обновлено";

	exit();

}

/**
 * Форма редактирования поля
 */
if ($action == "edit.item") {

	$id    = $_REQUEST['id'];
	$ida   = $_REQUEST['ida'];
	$block = $_REQUEST['block'];
	$tip   = 'input';

	if ($id > 0) {

		$result = $db -> getRow("SELECT * FROM {$sqlname}deal_anketa_base WHERE id = '$id' and identity = '$identity'");
		$ida    = $result["ida"];
		$name   = $result["name"];
		$tip    = $result["tip"];
		$value  = $result["value"];
		$block  = $result["block"];
		$pwidth = $result["pwidth"];
		$value  = str_replace(";", "\n", $value);

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="editForm" id="editForm" enctype="multipart/form-data">
		<input name="action" id="action" type="hidden" value="edit.do.item">
		<input name="id" id="id" type="hidden" value="<?= $id ?>">
		<input name="ida" id="ida" type="hidden" value="<?= $ida ?>">

		<div id="formtabs" class="box--child" style="max-height:80vh; overflow-x: hidden; overflow-y: auto !important;">

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Название:</div>
				<div class="flex-string wp85 pl10">
					<INPUT name="name" type="text" id="name" class="required wp97" value="<?= $name ?>">
				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Тип вывода:</div>
				<div class="flex-string wp85 pl10">

					<div class="flex-container box--child wp97" data-id="tip">

						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="input" <?php if ($tip == 'input') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Поле ввода</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="inputlist" <?php if ($tip == 'inputlist') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Поле с вариантами</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="text" <?php if ($tip == 'text') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Поле текста</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="datum" <?php if ($tip == 'datum') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Дата</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="datetime" <?php if ($tip == 'datetime') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Дата и время</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="number" <?php if ($tip == 'number') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Число</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="select" <?php if ($tip == 'select') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Список выбора</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="checkbox" <?php if ($tip == 'checkbox') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Множественный выбор</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="radio" <?php if ($tip == 'radio') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Одиночный выбор</span>
							</label>
						</div>
						<div class="flex-string radio inline viewdiv mb5 mr5">
							<label>
								<input type="radio" name="tip" id="tip" value="divider" <?php if ($tip == 'divider') print 'checked' ?>>
								<span class="custom-radio success1"><i class="icon-radio-check"></i></span>
								<span class="title pl10">Набор полей</span>
							</label>
						</div>

					</div>

				</div>

			</div>

			<?php
			$result = $db -> getIndCol("id", "SELECT name, id FROM {$sqlname}deal_anketa_base WHERE ida = '$ida' and tip = 'divider' and identity = '$identity'");
			if (!empty($result)) {
				?>
				<div class="flex-container box--child mt20" data-id="block">

					<div class="flex-string wp15 right-text fs-12 pt7 gray2">Набор:</div>
					<div class="flex-string wp85 pl10">

						<select id="block" name="block" class="wp97">
							<option value="">--вне блока--</option>
							<?php
							foreach ($result as $itm => $val) {

								print '<option value="'.$itm.'" '.($itm == $block ? "selected" : "").'>'.$val.'</option>';

							}
							?>
						</select>

					</div>

				</div>
			<?php } ?>

			<!--Не будем использовать-->
			<div class="flex-container box--child mt20 hidden">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Ширина поля:</div>
				<div class="flex-string wp85 pl10">
					<INPUT name="pwidth" type="number" step="5" id="pwidth" value="<?= $pwidth ?>" class="w90">&nbsp;%
				</div>

			</div>

			<div class="flex-container box--child mt20" data-id="variants">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Варианты:</div>
				<div class="flex-string wp85 pl10">
					<textarea name="value" rows="5" id="value" class="wp97"><?= $value ?></textarea>
					<br>
					<div class="infodiv fs-09 wp93">

						<ul class="p0 m0 pl20">
							<li>Каждый вариант начните с новой строки с помощью клавиши Enter.</li>
							<li>Для полей типа "Поле ввода", "Поле текста", "Разделитель блока", "Название блока" поле "Варианты выбора" оставьте пустым.</li>
							<li>Поле разделитель принудительно имеет ширину 100%</li>
							<li>Набор "???" добавляет поле для ввода собственного варианта (для полей с типом "Одиночный выбор", "Множественный выбор", "Список выбора")</li>
						</ul>

					</div>
				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#editForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<script>

		if (!isMobile) {

			if ($(window).width() > 990) $('#dialog').css('width', '892px');
			else {
				$('#dialog').css('width', '80%');
				$('#formtabs').css('height', '300px');
			}


			$(".multiselect").multiselect({sortable: true, searchable: true});
			$(".connected-list").css('max-height', "200px");

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

			$(".multiselect").addClass('wp97 h0');

			$('#dialog').find('table').rtResponsiveTables();

		}

		$('#editForm').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				$('.refresh--panel').find('.admn').remove();

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>?action=list.item&ida=<?=$ida?>');
				$('#message').fadeTo(1, 1).css('display', 'block').html(data);

				setTimeout(function () {

					$('.refresh--panel').prepend( $('.pagerefresh') );

				}, 500);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				DClose();
			}
		});

		$(function () {

			$('#tip:checked').trigger('change');

			$('#value').autoHeight(200, 3);

		});

		$(document).on('change', '#tip', function () {

			var tip = $(this).val();

			if (in_array(tip, ['select', 'checkbox', 'radio', 'multiselect', 'inputlist'])) $('div[data-id="variants"]').removeClass('hidden');
			else $('div[data-id="variants"]').addClass('hidden');

			if (tip === 'divider') $('div[data-id="block"]').val('').addClass('hidden');
			else $('div[data-id="block"]').removeClass('hidden');

			$('#value').autoHeight(200, 3);

			if (!isMobile) $('#dialog').center();

		});

	</script>
	<?php

	exit();

}

/**
 * Вывод профиля - как он получился
 */
if ($action == 'list.item') {

	$ida = $_REQUEST['ida'];

	$names = [
		"divider"   => "Блок",
		"input"     => "Поле ввода",
		"inputlist" => "Поле с вариантами",
		"select"    => "Варианты (меню)",
		"checkbox"  => "Варианты (выбор нескольких)",
		"radio"     => "Варианты (выбор одного)",
		"text"      => "Поле текста",
		"datum"     => "Дата",
		"datetime"  => "Дата и время",
		"number"    => "Число"
	];

	$a = $db -> getRow("SELECT title, content FROM {$sqlname}deal_anketa_list where id = '$ida' and identity = '$identity'");

	?>

	<div onclick="razdel(hash);" title="К списку" class="blue mt10 hand">
		<i class="icon-left-open"></i> К списку
	</div>

	<h2>&nbsp;Анкета: "<?= $a['title'] ?>"</h2>
	<div class="gray2 mb20"><?= $a['content'] ?></div>

	<DIV class="itable wp100 mb20">

		<TABLE id="table-1" class="block disable--select table--newface">
			<thead class="hidden">
			<TR class="header_contaner">
				<Th class="w50"><b>Порядок</b></Th>
				<Th class="w350"><b>Имя поля</b></Th>
				<Th><b>Вид в форме</b></Th>
				<Th class="w100"></Th>
			</TR>
			</thead>
			<tbody class="block wp100">
			<?php
			//получаем структуру анкеты
			$abase = DealAnketa ::anketabase($ida);
			foreach ($abase as $pole => $data) {

				$field = DealAnketa ::field($data['tip'], $pole, $data['value']);

				if ($data['tip'] != 'divider') {

					?>
					<TR class="ablock th40" id="<?= $data['id'] ?>">
						<TD class="handle w60 text-center">
							<span class="clearevents"><?= $data['order'] ?></span>
						</TD>
						<TD class="p10 w350">
							<div class="pull-aright fs-12">
								<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data['id'] ?>&ida=<?= $ida ?>&action=edit.item')" class="gray"><i class="icon-pencil"></i></A>
							</div>
							<div class="fs-12 Bold clearevents"><?= $data['name'] ?></div>
							<div class="gray clearevents mt10"><?= strtr($data['tip'], $names) ?></div>
						</TD>
						<TD>
							<div style="max-height:250px; overflow-y:auto; overflow-x:hidden" class="clearevents1">
								<?php
								print ($data['tip'] == 'select' ? '<span class="select wp100">'.$field.'</span>' : $field);
								?>
							</div>
						</TD>
						<TD class="text-center w60">
							<A href="javascript:void(0)" onclick="deleteItem('<?= $data['id'] ?>');"><i class="icon-cancel-circled red"></i></A>
						</TD>
					</TR>
					<?php
				}
				else {

					$string = '';

					foreach ($data['block'] as $ipole => $item) {

						$field = DealAnketa ::field($item['tip'], $ipole, $item['value']);

						$string .= '
						<TR class="ablock th40" id="'.$item['id'].'">
							<TD class="handle2 w60 text-center"><span class="clearevents">'.$item['order'].'</span></TD>
							<TD class="w350">
								<div class="pull-aright fs-12">
									<A href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?id='.$item['id'].'&ida='.$ida.'&action=edit.item\')" class="gray"><i class="icon-pencil"></i></A>
								</div>
								<div class="fs-11 Bold clearevents">'.$item['name'].'</div>
								<div class="gray clearevents mt10">'.strtr($item['tip'], $names).'</div>
							</TD>
							<TD>
								<div style="max-height:250px; overflow-y:auto; overflow-x:hidden" class="clearevents1">
									'.($item['tip'] == 'select' ? '<span class="select wp100">'.$field.'</span>' : $field).'
								</div>
							</TD>
							<TD class="w50">
								<A href="javascript:void(0)" onclick="deleteItem(\''.$item['id'].'\');"><i class="icon-cancel-circled red"></i></A>
							</TD>
						</TR>
						';

					}

					print '
					<TR class="bgbluelight th40" id="'.$data['id'].'">
						<TD class="handle w60 text-center"><span class="clearevents">'.$data['order'].'</span></TD>
						<TD colspan="3">
	
							<div class="wp100 pull-left pt10 pb10 gray2">
								блок полей "<b>'.$data['name'].'</b>"
								
								<span class="pull-aright">
								
									<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?id='.$data['id'].'&ida='.$ida.'&action=edit.item\')" title=""><i class="icon-pencil blue"></i>Редактировать блок</a>&nbsp;
									
									'.($string == '' ? '<a href="javascript:void(0)" onclick="deleteItem(\''.$data['id'].'\');"><i class="icon-cancel-circled red"></i>Удалить блок</a>' : '').'
									
								</span>
								
							</div>
	
							<div class="viewdiv1 enable--select">
	
								<TABLE id="table-2'.$data['id'].'" class="disable--select table--newface" data-blockid="'.$data['id'].'">
								<tbody>
								'.$string.'
								</tbody>
								</TABLE>
	
								<script>
								$("#table-2'.$data['id'].'").tableDnD({
									onDragClass: "tableDrag",
									onDrop: function (table, row) {
	
										var str = $(\'#table-2'.$data['id'].'\').tableDnDSerialize();
										var url = \'content/admin/'.$thisfile.'?action=edit.order.item&ida='.$ida.'&blockid='.$data['id'].'&table=2'.$data['id'].'\';
										$(\'.refresh--panel\').find(\'.admn\').remove();
	
										$.post(url, str, function (data) {
	
											$(\'#contentdiv\').load(\'content/admin/'.$thisfile.'?action=list.item&ida='.$ida.'\');
											$(\'#message\').fadeTo(1, 1).css(\'display\', \'block\').html(data);

											setTimeout(function () {
								
												$(\'.refresh--panel\').prepend( $(\'.pagerefresh\') );
								
											}, 500);
	
											setTimeout(function () {
												$(\'#message\').fadeTo(1000, 0);
											}, 20000);
	
										});
	
									}
									, dragHandle: \'handle2\'
								});
								</script>
	
							</div>
	
							<div class="space-20 p5">
							
								<a href="javascript:void(0)" onclick="doLoad(\'content/admin/'.$thisfile.'?action=edit.item&ida='.$ida.'&block='.$data['id'].'\');" title=""><i class="icon-plus-circled green"></i>Добавить поле</a>&nbsp;
							
								<span class="hidden">
								
									<a href="javascript:void(0)" onclick="doLoad(\'admin/content/admin/'.$thisfile.'?id='.$data['id'].'&ida='.$ida.'&action=edit.item\')" title=""><i class="icon-pencil blue"></i>Редактировать блок</a>&nbsp;
								
									<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)delBlock(\''.$data['id'].'\');" title=""><i class="icon-cancel-circled red"></i>Удалить блок</a>&nbsp;
								
									<a href="javascript:void(0)" onclick="cf=confirm(\'Вы действительно хотите удалить запись?\');if (cf)delAll(\''.$data['id'].'\');" title=""><i class="icon-cancel-circled red"></i>Удалить блок и содержимое</a>&nbsp;
								
								</span>
								
								'.($string == '' ? '<a href="javascript:void(0)" onclick="deleteItem(\''.$data['id'].'\');"><i class="icon-cancel-circled red"></i>Удалить блок</a>' : '').'
								
							</div>
	
						</TD>
					</TR>
					';

				}

			}
			?>
			</tbody>
		</TABLE>

	</DIV>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit.item&ida=<?= $ida ?>');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить поле</a>

	</div>

	<div class="pagerefresh refresh--icon admn green" onclick="toAnket('<?= $ida ?>');" title="Перезагрузить анкету"><i class="icon-arrows-cw"></i></div>
	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit.item&ida=<?= $ida ?>');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/22')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$(function () {

			$(".nano").nanoScroller();

		});

		$("#table-1").tableDnD({
			onDragClass: "tableDrag",
			onDrop: function (table, row) {

				var str = '' + $('#table-1').tableDnDSerialize();
				var url = 'content/admin/<?php echo $thisfile; ?>?action=edit.order.item&ida=<?=$ida?>';
				$('.refresh--panel').find('.admn').remove();

				$.post(url, str, function (data) {

					$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>?action=list.item&ida=<?=$ida?>');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {

						$('.refresh--panel').prepend( $('.pagerefresh') );

					}, 500);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				});

			}
			, dragHandle: 'handle'
		});

		function deleteItem(id) {

			//$('.refresh--panel').find('.admn').remove();

			Swal.fire(
				{
					type: "question",
					title: "Удалить запись?",
					text: "Также будут удалены заполненные данные по сделкам",
					html: '<label><input type="checkbox" name="qblock" id="qblock" value="yes">С полями</label>',
					/*input: 'checkbox',
					inputValue: 'yes',
					inputPlaceholder: "С полями",*/
					showConfirmButton: true,
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {

					deleteBlock(id, <?=$ida?>, '');

					//refresh('contentdiv', 'content/admin/<?php echo $thisfile; ?>?id=' + id + '&ida=<?=$ida?>&action=delete.item');

					/*setTimeout(function () {

						$('.refresh--panel').prepend( $('.pagerefresh') );

					}, 500);*/

				}
			).then((result) => {

				var block = ($('#qblock:checked').val() === undefined) ? 'no' : 'yes';

				if (result.value) {

					/*refresh('contentdiv', 'content/admin/<?php echo $thisfile; ?>?id=' + id + '&ida=<?=$ida?>&action=delete.item&block=' + block);

					setTimeout(function () {

						$('.refresh--panel').prepend( $('.pagerefresh') );

					}, 500);*/

					deleteBlock(id, <?=$ida?>, block);

				}

			});

		}


		function deleteBlock(id, ida, block) {

			//$('.refresh--panel').find('.admn').remove();

			$.get('content/admin/<?php echo $thisfile; ?>?id=' + id + '&ida=<?=$ida?>&action=delete.item&block=' + block, function(data){

				toAnket(ida);

				/*setTimeout(function () {

					$('.refresh--panel').prepend( $('.pagerefresh') );

				}, 500);*/

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}

		if (!isMobile) {

			$('.inputdate').each(function () {

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

		}

	</script>
	<?php
}

/**
 * Удаление анкеты с данными
 */
if ($action == "delete") {

	$id = $_REQUEST['id'];

	$db -> query("DELETE FROM {$sqlname}deal_anketa_list WHERE id = '$id' AND identity = '$identity'");
	$db -> query("DELETE FROM {$sqlname}deal_anketa_base WHERE ida = '$id' AND identity = '$identity'");
	$db -> query("DELETE FROM {$sqlname}deal_anketa WHERE ida = '$id' AND identity = '$identity'");

	$action = 'list';

	print "Выполнено";
	exit();

}

/**
 * Добавляем/Редактируем анкету
 */
if ($action == "edit.do") {

	$id      = $_REQUEST['id'];
	$title   = $_REQUEST['title'];
	$content = $_REQUEST['content'];

	if ($id > 0) {

		$db -> query("UPDATE {$sqlname}deal_anketa_list SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean([
			'title'      => $title,
			'content'    => $content,
			'datum_edit' => current_datumtime()
		]));

		print "Готово";

	}
	else {

		$db -> query("INSERT INTO {$sqlname}deal_anketa_list SET ?u", arrayNullClean([
			'title'    => $title,
			'content'  => $content,
			'datum'    => current_datumtime(),
			'iduser'   => $iduser1,
			'identity' => $identity
		]));

		print "Готово";

	}

	exit();

}

/**
 * Форма добавления/Изменения анкеты
 */
if ($action == "edit") {

	if ($id > 0) {

		$anketa = $db -> getRow("SELECT * FROM {$sqlname}deal_anketa_list where id = '$id' and identity = '$identity'");

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить анкету</b></div>

	<FORM action="content/admin/<?php echo $thisfile; ?>" method="POST" name="editForm" id="editForm" enctype="multipart/form-data">
		<input name="action" id="action" type="hidden" value="edit.do">
		<input name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" class="box--child" style="overflow-x: hidden; overflow-y: auto !important;">

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Название:</div>
				<div class="flex-string wp85 pl10">
					<INPUT name="title" type="text" id="title" class="required wp97" value="<?= $anketa['title'] ?>">
				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Описание:</div>
				<div class="flex-string wp85 pl10">
					<textarea name="content" rows="10" id="content" class="wp97"><?= $anketa['content'] ?></textarea>
				</div>

			</div>

		</div>

		<hr>

		<div class="button--pane text-right">

			<A href="javascript:void(0)" onclick="$('#editForm').trigger('submit')" class="button">Сохранить</A>&nbsp;
			<A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</div>

	</FORM>
	<script>

		if (!isMobile) {

			if ($(window).width() > 990) $('#dialog').css('width', '892px');
			else {
				$('#dialog').css('width', '80%');
				$('#formtabs').css('height', '300px');
			}


			$(".multiselect").multiselect({sortable: true, searchable: true});
			$(".connected-list").css('max-height', "200px");

			$('#dialog').center();

		}
		else {

			var h2 = $(window).height() - $('.zagolovok').actual('outerHeight') - $('.button--pane').actual('outerHeight') - 30;
			$('#formtabs').css({'max-height': h2 + 'px', 'height': h2 + 'px'});

			$(".multiselect").addClass('wp97 h0');

			$('#dialog').find('table').rtResponsiveTables();

		}

		$('#editForm').ajaxForm({
			beforeSubmit: function () {

				var $out = $('#message');
				var em = checkRequired();

				if (em === false) return false;

				$('#dialog').css('display', 'none');
				$('#dialog_container').css('display', 'none');

				$out.fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
				$('.refresh--panel').find('.admn').remove();

				return true;

			},
			success: function (data) {

				$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>?action=list');

				setTimeout(function () {

					$('.refresh--panel').prepend( $('.pagerefresh') );

				}, 500);

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

/**
 * Список анкет
 */
if ($action == 'list') {
	?>

	<h2>&nbsp;Раздел: "Анкеты по сделке"</h2>

	<TABLE class="disable--select">
		<thead class="hidden-iphone sticked--top">
		<TR class="th35">
			<th class="wp60 text-center"><b>Название анкеты</b></th>
			<th class="w120 text-center"><b>Дата создания</b></th>
			<th class="w120 text-center"><b>Дата изменения</b></th>
			<th class="w160 text-center"><b>Автор</b></th>
			<th class="text-center"></th>
		</TR>
		</thead>
		<tbody>
		<?php
		$result = $db -> getAll("SELECT * FROM {$sqlname}deal_anketa_list WHERE identity = '$identity' ORDER by id");
		foreach ($result as $data) {

			?>
			<TR class="th50" id="<?= $data['id'] ?>">
				<TD class="wp60">

					<div class="fs-12 Bold mt5">

						Анкета "<a href="javascript:void(0)" onclick="toAnket(<?= $data['id'] ?>);" title="Перейти к анкете"><?= $data['title'] ?></a>"

					</div>

					<div class="mt10 fs-09 gray"><?= $data['content'] ?></div>

					<div class="fs-10 mb5 mt10">

						<A href="javascript:void(0)" onclick="toAnket(<?= $data['id'] ?>);" class="green"><i class="icon-doc-inv-alt green"></i> К анкете</A>
						<A href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?id=<?= $data['id'] ?>&action=edit')" class="blue"><i class="icon-pencil blue"></i> Изменить описание</A>

					</div>

				</TD>
				<TD class="w120 text-left">

					<span><?= get_sfdate($data['datum']) ?></span>

				</TD>
				<TD class="w120 text-left">

					<span><?= get_sfdate($data['datum_edit']) ?></span>

				</TD>
				<TD class="w160 text-left">

					<span><?= current_user($data['iduser']) ?></span>

				</TD>
				<TD class="text-left">

					<A href="javascript:void(0)" onclick="deleteAnketa('<?= $data['id'] ?>')" class="button dotted redbtn"><i class="icon-cancel-circled"></i></A>

				</TD>
			</TR>
		<?php } ?>
		</tbody>
	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/22')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		function deleteAnketa(id) {

			Swal.fire({
					title: 'Вы уверены?',
					text: "Будет удалена анкета и все заполненные данные!",
					type: 'question',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {

					//refresh('contentdiv', 'content/admin/<?php echo $thisfile; ?>?id=' + id + '&action=delete');
					deleteAnket(id);

				}
			).then((result) => {

				if (result.value) {

					//refresh('contentdiv', 'content/admin/<?php echo $thisfile; ?>?id=' + id + '&action=delete');
					deleteAnket(id);

				}

			});

		}

		function toAnket(id){

			$('.refresh--panel').find('.admn').remove();
			$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>?ida='+id+'&action=list.item');

			setTimeout(function () {

				$('.refresh--panel').prepend( $('.pagerefresh') );

			}, 500);

		}
		function deleteAnket(id) {

			$('.refresh--panel').find('.admn').remove();

			$.get('content/admin/<?php echo $thisfile; ?>?action=delete&id='+id, function(data){

				razdel(hash);

				setTimeout(function () {

					$('.refresh--panel').prepend( $('.pagerefresh') );

				}, 500);

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			});

		}

	</script>
	<?php
}
