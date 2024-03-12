<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

error_reporting( E_ERROR );
header( "Pragma: no-cache" );

$rootpath = dirname(__DIR__, 2);

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];
$id     = (int)$_REQUEST['id'];
$name   = $_REQUEST['name'];
$pwidth = $_REQUEST['pwidth'];
$tip    = $_REQUEST['tip'];
$ord    = $_REQUEST['ord'];

if ( $tip == 'divider' ) {
	$pwidth = 100;
}

$ttip = [
	'input',
	'text'
];

/**
 * обработка - очистка от говна
 */
if ( !in_array( $tip, $ttip ) ) {

	$value = str_replace( "\n", ";", $_REQUEST['value'] );

	$varr = yexplode( ";", $value );

	for ( $g = 0, $gMax = count( $varr ); $g < $gMax; $g++ ) {

		$varr[ $g ] = trim( str_replace( [
			"\\n\\r",
			"\\n",
			"\\r",
			","
		], "", $varr[ $g ] ) );

	}

	$value = implode( ";", $varr );

}
else {
	$value = $_REQUEST['value'];
}

/**
 * Удаляем поле
 */
if ( $action == "delete" ) {

	$db -> query( "delete from {$sqlname}profile_cat where id = '".$id."' and identity = '$identity'" );
	$action = '';

}

/**
 * Добавляем/Редактируем поле
 */
if ( $action == "edit_do" ) {

	if ( $id > 0 ) {

		$db -> query( "UPDATE {$sqlname}profile_cat SET ?u WHERE id = '$id' and identity = '$identity'", arrayNullClean( [
			'name'   => $name,
			'tip'    => $tip,
			'value'  => $value,
			'pwidth' => $pwidth
		] ) );

	}
	else {

		$db -> query( "INSERT INTO {$sqlname}profile_cat SET ?u", arrayNullClean( [
			'name'     => $name,
			'tip'      => $tip,
			'value'    => $value,
			'pwidth'   => $pwidth,
			'identity' => $identity
		] ) );

		$id    = $db -> getOne( "SELECT id FROM {$sqlname}profile_cat WHERE name = '$name'" );
		$pname = 'pole'.$id;

		$db -> query( "UPDATE {$sqlname}profile_cat SET ?u WHERE id = '$id' AND identity = '$identity'", ['pole' => $pname] );

	}
	
	print "Готово";

	exit();

}

/**
 * Изменим порядок сортировки
 */
if ( $action == "edit_order" ) {

	$table = $_REQUEST['table-1'];
	$count = count( $table );
	$err   = 0;

	//Обновляем данные для текущей записи
	foreach ( $table as $i => $row ) {
		$db -> query("UPDATE {$sqlname}profile_cat SET ord = '$i' WHERE id = '$row' and identity = '$identity'");
	}

	print "Обновлено. Ошибок: ".$err;

	exit();

}

/**
 * Форма редактирования поля
 */
if ( $action == "edit" ) {

	if ( $id > 0 ) {

		$result = $db -> getRow( "SELECT * FROM {$sqlname}profile_cat where id = '".$id."' and identity = '$identity'" );
		$name   = $result["name"];
		$tip    = $result["tip"];
		$value  = $result["value"];
		$pwidth = $result["pwidth"];
		$value  = str_replace( ";", "\n", $value );

	}
	?>
	<div class="zagolovok"><b>Изменить / Добавить</b></div>

	<FORM action="/content/admin/<?php echo $thisfile; ?>" method="POST" name="editForm" id="editForm" enctype="multipart/form-data">
		<input name="action" id="action" type="hidden" value="edit_do">
		<input name="id" id="id" type="hidden" value="<?= $id ?>">

		<div id="formtabs" class="box--child" style="overflow-x: hidden; overflow-y: auto !important;">

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Название:</div>
				<div class="flex-string wp85 pl10">
					<INPUT name="name" type="text" id="name" class="required wp97" value="<?= $name ?>">
				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Тип вывода:</div>
				<div class="flex-string wp85 pl10">
					<select id="tip" name="tip" class="required wp97">
						<option value="input" <?php if ( $tip == 'input' )
							print 'selected="selected"' ?>>Поле ввода
						</option>
						<option value="text" <?php if ( $tip == 'text' )
							print 'selected="selected"' ?>>Поле текста
						</option>
						<option value="select" <?php if ( $tip == 'select' )
							print 'selected="selected"' ?>>Список выбора
						</option>
						<option value="checkbox" <?php if ( $tip == 'checkbox' )
							print 'selected="selected"' ?>>Чекбоксы
						</option>
						<option value="radio" <?php if ( $tip == 'radio' )
							print 'selected="selected"' ?>>Радиокнопки
						</option>
						<option value="divider" <?php if ( $tip == 'divider' )
							print "selected" ?>>Разделитель
						</option>
					</select>
				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Ширина поля:</div>
				<div class="flex-string wp85 pl10">
					<INPUT name="pwidth" type="number" step="5" id="pwidth" value="<?= $pwidth ?>" class="w90">&nbsp;%
				</div>

			</div>

			<div class="flex-container box--child mt20">

				<div class="flex-string wp15 right-text fs-12 pt7 gray2">Варианты выбора:</div>
				<div class="flex-string wp85 pl10">
					<textarea name="value" rows="10" id="value" class="wp97"><?= $value ?></textarea>
					<br>
					<div class="smalltxt">

						<ul class="p0">
							<li>Каждый вариант начните с новой строки с помощью клавиши Enter.</li>
							<li>Для полей типа "Поле ввода", "Поле текста", "Разделитель блока", "Название блока" поле "Варианты выбора" оставьте пустым.</li>
							<li>Поле разделитель принудительно имеет ширину 100%</li>
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

/**
 * Вывод профиля - как он получился
 */
if ( $action == '' ) {

	$names = [
		"divider"  => "Разделитель",
		"input"    => "Поле ввода",
		"select"   => "Варианты (меню)",
		"checkbox" => "Варианты (выбор нескольких)",
		"radio"    => "Варианты (выбор одного)",
		"text"     => "Поле текста"
	];
	?>

	<h2>&nbsp;Раздел: "Настройки профилей"</h2>

	<TABLE id="table-1" class="disable--select top">
		<thead class="hidden-iphone sticked--top">
		<TR class="th35">
			<Th class="w50 text-center"><b>Порядок</b></Th>
			<Th class="w350 text-center"><b>Имя поля</b></Th>
			<Th class="text-center"><b>Вид в форме</b></Th>
			<Th class="w50 text-center"><b></b></Th>
		</TR>
		</thead>
		<tbody>
		<?php
		$result = $db -> getAll( "SELECT * FROM {$sqlname}profile_cat WHERE identity = '$identity' ORDER by ord" );
		foreach ( $result as $data ) {

			$color = '';

			if ( $data['tip'] == 'divider' ) {

				$color = 'broun';
				$value = '<div id="divider" class="wp97 pull-left mt10"><b>'.$data['name'].'</b></div>';

			}
			elseif ( $data['tip'] == 'select' ) {

				$variant = yexplode( ";", $data['value'] );
				$v       = '';

				foreach ( $variant as $row ) {

					$v .= '<option value="'.$row.'">'.$row.'</option>';

				}

				$value = '<span class="select wp97"><SELECT id="'.$data['id'].'" class="wp100"><option value="">--выбор--</option>'.$v.'</SELECT></span>';

			}
			elseif ( $data['tip'] == 'checkbox' ) {

				$variant = yexplode( ';', $data['value'] );
				$v       = '';

				foreach ( $variant as $row ) {

					$v .= '
				<div class="block">
					<label><input type="checkbox" name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" value="'.$row.'">&nbsp;&nbsp;'.$row.'</label>;&nbsp;
				</div>
				';

				}

				$value = $v;

			}
			elseif ( $data['tip'] == 'radio' ) {

				$variant = yexplode( ';', $data['value'] );
				$v       = '';

				foreach ( $variant as $row ) {

					$v .= '
				<div class="block">
					<label><input type="radio" name="'.$data['pole'].'[]" id="'.$data['pole'].'[]" value="'.$row.'">&nbsp;&nbsp;'.$row.'</label>;&nbsp;
				</div>
				';

				}

				$value = $v;

			}
			else {

				$value = $data['value'];

			}
			?>
			<TR class="th40" id="<?= $data['id'] ?>">
				<TD class="w50 text-center handle">

					<span class="miditxt clearevents"><?= $data['ord'] ?></span>

				</TD>
				<TD>

					<div class="pull-aright fs-12">
						<A href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?id=<?= $data['id'] ?>&action=edit')" class="gray"><i class="icon-pencil"></i></A>
					</div>
					<div class="fname fs-14 Bold clearevents <?= $color ?>">
						<?= $data['name'] ?>
					</div>
					<div class="gray clearevents"><?= strtr( $data['tip'], $names ) ?></div>

				</TD>
				<TD>

					<div style="max-height:250px; overflow-y:auto; overflow-x:hidden" class=""><?= $value ?></div>

				</TD>
				<TD class="w50 text-center">

					<A href="javascript:void(0)" onclick="cf=confirm('Вы действительно хотите удалить запись?');if (cf)refresh('contentdiv','/content/admin/<?php echo $thisfile; ?>?id=<?= $data['id'] ?>&action=delete');"><i class="icon-cancel-circled red"></i></A>

				</TD>
			</TR>
		<?php } ?>
		</tbody>
	</TABLE>

	<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">

		<a href="javascript:void(0)" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" class="button bluebtn box-shadow" title="Добавить"><i class="icon-plus-circled"></i>Добавить</a>

	</div>

	<div class="pagerefresh refresh--icon admn red" onclick="doLoad('/content/admin/<?php echo $thisfile; ?>?action=edit');" title="Добавить"><i class="icon-plus-circled"></i></div>
	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/22')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>

	<script>

		$("#table-1").tableDnD({
			onDragClass: "tableDrag",
			onDrop: function (table, row) {

				var str = '' + $('#table-1').tableDnDSerialize();
				var url = '/content/admin/<?php echo $thisfile; ?>?action=edit_order&';
				$.post(url, str, function (data) {

					//$('#contentdiv').load('content/admin/<?php echo $thisfile; ?>');
					razdel(hash);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data);

					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				});
			}
			//, dragHandle: 'dragg'
		});

	</script>
<?php } ?>