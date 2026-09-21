<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2026.x          */
/* ============================ */

/**
 * Редактор колонок раздела "Напоминания. Все" (url = todo)
 *
 * Реализация повторяет подход модуля Прайс (modules/price/columneditor.php):
 *  - action=columneditor      - диалог настройки
 *  - action=columneditor.do   - сохранение настроек (вкл/выкл, ширина, порядок)
 *  - action=columnOrderSave   - сохранение порядка после перетаскивания колонок в списке
 *  - action=restore           - сброс к значениям по умолчанию
 *
 * Каталог колонок и работа с файлом настроек - в content/helpers/todo.columns.php
 *
 * @package Todo
 */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";
require_once $rootpath."/content/helpers/todo.columns.php";

$thisfile = basename(__FILE__);
$action   = $_REQUEST['action'];

//скрипт читает и изменяет персональные настройки - требуется авторизованная сессия
//(inc/auth.php при отсутствии cookie сессии продолжает работу с $iduser1 = 0)
if ((int)$iduser1 < 1) {

	header('HTTP/1.1 401 Unauthorized');
	print 'Требуется авторизация';

	exit();

}

$defs = todoColumnDefs();
$file = todoColumnsFile((int)$iduser1);

//сброс настроек к значениям по умолчанию
if ($action == 'restore') {

	if (file_exists($file)) {
		unlink($file);
	}

	print 'ok';

	exit();

}

//сохранение настроек из диалога
if ($action == 'columneditor.do') {

	$columns = todoColumnsFromRequest($_REQUEST, $defs);

	todoColumnsSave((int)$iduser1, $columns);

	print 'Настройки сохранены';

	exit();

}

//сохранение порядка колонок после перетаскивания в списке
if ($action == 'columnOrderSave') {

	$order = $_REQUEST;

	unset($order['action'], $order['_']);

	$fc  = todoColumnsLoad((int)$iduser1, $defs);
	$new = todoColumnsMergeOrder($order, $fc);

	todoColumnsSave((int)$iduser1, $new);

	print 'Сохранено';

	exit();

}

//диалог настройки колонок
if ($action == 'columneditor' || $action == '') {

	$fc = todoColumnsLoad((int)$iduser1, $defs);
	?>
	<DIV class="zagolovok">Настройка колонок</DIV>
	<FORM action="/content/helpers/todo.columneditor.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="columneditor.do">

		<div id="formtabs" style="max-height:80vh; overflow:auto">

			<table class="rowtable middle" id="table">
				<thead class="sticked--top disable--select">
				<tr class="header_contaner noDrag th30">
					<th class="w350"><b>Название</b></th>
					<th class="w80 text-center"><b>Ширина</b></th>
					<th class="w80 text-center"><b>Вывод</b></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ($fc as $column => $data) {

					if (!isset($defs[$column])) {
						continue;
					}

					$width = (int)$data['width'];

					?>
					<tr class="noDrag0 th30" data-id="<?= $column ?>">
						<td class="w350 Bold fs-11">
							<div class="drag-handler"></div>&nbsp;
							<input name="name[<?= $column ?>]" type="hidden" id="name[<?= $column ?>]" value="<?= $column ?>"><?= todoH($defs[$column]['title']) ?>
						</td>
						<td class="w80">
							<input name="width[<?= $column ?>]" type="number" min="30" max="550" step="5" id="width[<?= $column ?>]" value="<?= ($width > 0) ? $width : '' ?>" placeholder="авто" class="wp90 width"/>
						</td>
						<td class="w80">
							<label for="on[<?= $column ?>]" class="switch">
								<input type="checkbox" name="on[<?= $column ?>]" id="on[<?= $column ?>]" value="yes" <?php print($data['on'] == 'yes' ? "checked" : "") ?>>
								<span class="slider"></span>
							</label>
						</td>
					</tr>
					<?php

				}
				?>
				</tbody>
			</table>

			<div class="smalltxt gray2 mt10 pl10">
				Ширина указывается в пикселях (шаг 5). Пустое значение - ширина по умолчанию.<br>
				Колонки карточки клиента выводят данные клиента, привязанного к напоминанию.
			</div>

		</div>

		<DIV class="button--pane text-right">

			<div class="pull-left">
				<A href="javascript:void(0)" onclick="RestoreColumn()" class="redbtn button"><i class="icon-cancel-squared"></i>Сброс</A>
			</div>

			<A href="javascript:void(0)" onclick="$('#Form').trigger('submit')" class="button">Сохранить</A>&nbsp; <A href="javascript:void(0)" onclick="DClose()" class="button">Отмена</A>

		</DIV>
	</FORM>
	<script>

		$(function () {

			$('#dialog').css('width', '800px')

			$('#Form').ajaxForm({
				beforeSubmit: function () {

					var $out = $('#message')
					var em = checkRequired()

					if (em === false) return false

					$out.css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
					return true

				},
				success: function (data) {

					$('#message').fadeTo(1, 1).css('display', 'block').html(data)
					setTimeout(function () {
						$('#message').fadeTo(1000, 0)
					}, 20000);

					configpage()
					DClose()
				}
			});

			$('#dialog').center()

		});

		$("#table").tableDnD({
			indentArtifact: '<div class="drag-handler"></div>',
			onDragClass: "tableDrag",
			onDrop: function (table, row) {
			}
		})

		function RestoreColumn() {

			fetch("/content/helpers/todo.columneditor.php?action=restore")
				.then(response => response.text())
				.then(function () {

					DClose();
					configpage();

				})
				.catch(error => {

					Swal.fire({
						title: 'Ошибка',
						text: error,
						type: 'error',
						showCancelButton: true
					});

				});

		}

	</script>
	<?php
	exit();

}

print 'Неизвестное действие';
