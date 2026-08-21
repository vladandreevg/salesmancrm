<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2025.4          */
/* ============================ */

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = dirname(__DIR__, 2);

require_once $rootpath."/inc/config.php";
require_once $rootpath."/inc/dbconnector.php";
require_once $rootpath."/inc/auth.php";
require_once $rootpath."/inc/func.php";
require_once $rootpath."/inc/settings.php";
require_once $rootpath."/inc/language/".$language.".php";

$thisfile = basename(__FILE__);
$action   = $_REQUEST['action'];

$fields = [];
$result = $db -> query("SELECT * FROM {$sqlname}field WHERE fld_tip='price' AND fld_on='yes' and identity = '$identity' ORDER BY fld_order");
while ($data = $db -> fetch($result)) {
	
	if ($data['fld_name'] != 'price_in' && $data['fld_on'] == 'yes') {
		
		if ($data['fld_sub'] == 'hidden') {
			continue;
		}
		
		$fields[] = [
			"field" => $data['fld_name'],
			"title" => $data['fld_title'],
			"value" => $data['fld_var'],
		];
		
	}
	
}

$titles = [
	"artikul" => "Артикул",
	"title" => "Название",
	"price_in" => "Закуп",
	"nds" => "НДС",
	"edizm" => "Ед.изм.",
	"content" => "Описание"
];
foreach ($fields as $field) {
	
	$titles[$field['field']] = $field['title'];
	
}

// дефолтные значения
$columnsDefault = [
	"artikul" => 120,
	"title"   => 250,
];
if ($show_marga == 'yes') {
	$columnsDefault["price_in"] = 100;
}
foreach ($fields as $field) {
	$columnsDefault[$field['field']] = 100;
}
$columnsDefault["nds"]     = 60;
$columnsDefault["content"] = "";

//считаем текущие настройки
$columnfile     = $rootpath.'/cash/price_columns_'.$iduser1.'.json';

if (file_exists($columnfile)) {
	$fc   = json_decode(file_get_contents($columnfile), true);
}
else {
	$fc = $columnsDefault;
}

if ($action == 'restore') {
	
	$file = $rootpath."/cash/price_columns_'.$iduser1.'.json";
	
	if (file_exists( $file )) {
		unlink($file);
	}
	
	print 'ok';
	
}

if ($action == 'columneditor.do') {
	
	$columns = $_REQUEST['name'];
	$width = $_REQUEST['width'];
	$on = $_REQUEST['on'];
	
	$result = [];
	
	foreach ($columns as $column) {
		
		if($on[$column] != 'yes') {
			continue;
		}
		
		$result[$column] = (int)$width[$column];
		
	}

	$x = json_encode($result, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
	
	file_put_contents($rootpath.'/cash/price_columns_'.$iduser1.'.json', $x);

}

if ($action == 'columneditor') {
	
	$pole = [];
	
	// найдем отключенные стандартные колонки
	$diff = array_keys(array_diff_key($columnsDefault, $fc));
	//print_r($diff);
	
	// добавим их в конец массива
	foreach ($diff as $key) {
		$fc[$key] = $columnsDefault[$key];
	}
	?>
	<DIV class="zagolovok">Настройка колонок</DIV>
	<FORM action="/modules/price/columneditor.php" method="post" enctype="multipart/form-data" name="Form" id="Form">
		<INPUT type="hidden" name="action" id="action" value="columneditor.do">
		
		<div id="formtabs" style="max-height:80vh; overflow:auto">
			
			<table class="rowtable middle" id="table">
				<thead class="sticked--top">
				<tr class="header_contaner noDrag th30">
					<th class="w350"><b>Название</b></th>
					<th class="w80 text-center"><b>Ширина</b></th>
					<th class="w80 text-center">&nbsp;</th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $fc as $column => $width ) {
					
					$ison = array_key_exists($column, $fc);
					
					// если колонки нет в текущих настройках, то отключим её
					if(in_array($column, $diff)) {
						$ison = false;
					}
					
					$readonly = $column == 'content' ? 'readonly' : '';
					
					$width = (int)$width == 0 ? $columnsDefault[$column] : $width;
				?>
					<tr class="noDrag0">
						<td class="w350 Bold fs-11">
							<div class="drag-handler"></div>&nbsp;<input name="name[<?= $column ?>]" type="hidden" id="name[<?= $column ?>]" <?= $ro ?> value="<?= $column ?>"><?= $titles[$column] ?>
						</td>
						<td class="w120">
							<input name="width[<?= $column ?>]" type="number" step="5" id="width[<?= $column ?>]" value="<?= $width ?>" <?= $readonly ?> style="width:90%"/>
						</td>
						<td class="w80">
							<label for="on[<?= $column ?>]" class="switch">
								<input type="checkbox" name="on[<?= $column ?>]" id="on[<?= $column ?>]" value="yes" <?php print ($ison ? "checked" : "") ?>>
								<span class="slider"></span>
							</label>
						</td>
					</tr>
				<?php
				}
				?>
				</tbody>
			</table>
			
			
			
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
			
			fetch("/modules/price/columneditor.php?action=restore")
				.then(response => response.text())
				.then(function () {
					
					DClose();
					configpage();
					
				})
				.catch(error => {
					
					//console.log(error);
					
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

if ($action == 'columnOrderSave') {
	
	$fields = $_REQUEST;
	unset($fields['action']);
	
	$columnsNew = [];
	
	foreach ($fields as $key => $value) {
		
		$key = str_replace('x_', '', $key);
		
		$columnsNew[$key] = $fc[$key];
		
	}
	
	$x = json_encode($columnsNew, JSON_UNESCAPED_UNICODE, JSON_PRETTY_PRINT);
	
	file_put_contents($rootpath.'/cash/price_columns_'.$iduser1.'.json', $x);
	
	print "Сохранено";
	
	exit();
	
}