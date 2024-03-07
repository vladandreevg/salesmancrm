<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*          ver. 2019.х         */
/* ============================ */
/*   Developer: Ivan Drachyov   */
?>
<?php

use Salesman\User;

error_reporting(E_ERROR);
header("Pragma: no-cache");

$rootpath = realpath( __DIR__.'/../../' );

include $rootpath."/inc/config.php";
include $rootpath."/inc/dbconnector.php";
include $rootpath."/inc/auth.php";
include $rootpath."/inc/func.php";
include $rootpath."/inc/settings.php";
include $rootpath."/inc/language/".$language.".php";

$thisfile = basename( __FILE__ );

$action = $_REQUEST['action'];

//вкладка Настройки модуля
if ($action == "settings") {

	$mdcset      = $db -> getRow("SELECT * FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'");
	$mdcsettings = json_decode($mdcset['content'], true);
	?>
	<br>

	<FORM action="/modules/corpuniver/settings.corpuniver.php" method="post" enctype="multipart/form-data" name="set" id="set">
		<INPUT type="hidden" name="action" id="action" value="settings.do">
		<TABLE id="bborder">
			<tbody id="aboutset" class="">
			<tr>
				<td width="170" align="right" valign="top" style="padding-top: 8px;">
					<div class="fs-12 gray2 pt7">Положение в меню:</div>
				</td>
				<td>
				<span class="select">
				<select name="MenuTip" id="MenuTip" onchange="showPlace()">
					<option value="">--Выбор--</option>
					<option value="inMain" <?php if ($mdcsettings['MenuTip'] != 'inSub') print "selected"; ?>>Как раздел меню</option>
					<option value="inSub" <?php if ($mdcsettings['MenuTip'] == 'inSub') print "selected"; ?>>Подраздел Сервисы</option>
				</select>&nbsp;
				</span>
					<div class="smalltxt gray2">Изменение вступит в силу после обновления окна браузера</div>
				</td>
			</tr>
			<tr>
				<TD align="right" valign="top">
					<div class="fs-12 gray2 mt10">Редакторы:</div>
				</TD>
				<TD>
					<div style="overflow-y: auto; overflow-x: hidden; max-height: 350px">
						<?php
						$maxlevel = 1;

						$users = User ::userCatalog();
						foreach ($users as $key => $value) {

							if ($value['active'] != 'yes') goto a;

							$s = ($value['level'] > 0) ? str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $value['level']).'<div class="strelka mr10"></div>&nbsp;' : '';

							$ss = (in_array($value['id'], $mdcsettings['Editor'])) ? "checked" : "";

							$ss1 = (in_array($value['id'], $mdcsettings['EditorMy'])) ? "checked" : "";
							?>

							<label class="block ha coordcat" data-id="<?= $value['id'] ?>" data-sub="<?= $value['mid'] ?>">
								<div class="row">
									<div class="column grid-4">
										<div class="ellipsis"><?= $s ?>&nbsp;<?= $value['title'] ?>&nbsp;</div>
									</div>
									<div class="column grid-4">
										<input type="checkbox" name="Editor[]" id="Editor[]" value="<?= $value['id'] ?>" <?= $ss ?>>&nbsp
										<span class="gray2"><?= $value['tip'] ?></span>
									</div>

									<div class="column grid-2" title="Редактирование только своих курсов">
										<label class="block">
											<input type="checkbox" name="EditorMy[]" id="EditorMy[]" value="<?= $value['id'] ?>" <?= $ss1 ?>>&nbsp
											<span class="gray2">Только свои</span>
										</label>
									</div>
								</div>
							</label>

							<?php
							a:
						}
						?>
					</div>
					<div class="smalltxt gray2 mt10">Сотрудники, имеющие право на редактирование курсов и их категорий</div>
				</TD>
			</tr>
			</tbody>
		</TABLE>
		<br><br>
		<div class="button--group1 box--child" style="position: fixed; bottom: 40px; left: 380px; z-index: 100;">
			<a href="javascript:void(0)" class="button" onClick="$('#set').submit()"><span>Сохранить</span></a>
		</DIV>
		<div class="space-100"></div>
	</FORM>

	<script>

		$(document).ready(function () {

			$(".multiselect").multiselect({sortable: true, searchable: true});
			$(".connected-list").css('height', "120px");

			$('#set').ajaxForm({
				beforeSubmit: function () {
					var $out = $('#message');
					$out.empty();
					$out.css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');
					return true;
				},
				success: function (data) {
					$('#tab-form-1').load('modules/corpuniver/settings.corpuniver.php?action=settings').append('<img src="/assets/images/loading.gif">');
					$('#message').fadeTo(1, 1).css('display', 'block').html(data);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);
				}
			});
		});

	</script>
	<?php
	exit();
}

//обработка изменений натроек по модулю в панели управления
if ($action == "settings.do") {

	$params['MenuTip']  = $_REQUEST['MenuTip'];
	$params['Editor']   = $_REQUEST['Editor'];
	$params['EditorMy'] = $_REQUEST['EditorMy'];

	$settings = json_encode_cyr($params);

	$db -> query("UPDATE {$sqlname}modules SET content='$settings' WHERE mpath = 'corpuniver' AND identity = '$identity'");

	print "Сделано";

	exit();
}

//начальная страница при загрузке
if ($action == "") {
	?>
	<DIV id="formtabs" style="border:0">
		<UL>
			<LI><A href="#tab-form-1">Настройки модуля</A></LI>
		</UL>
		<div id="tab-form-1"></div>
	</DIV>

	<div class="pagerefresh refresh--icon admn orange" onclick="openlink('https://salesman.pro/docs/149')" title="Документация"><i class="icon-help"></i></div>

	<div class="space-100"></div>
	<?
}
?>

<script>
	var $self;

	//для закладок
	$('#formtabs').tabs();
	$('#tab-form-1').load('modules/corpuniver/settings.corpuniver.php?action=settings').append('<img src="/assets/images/loading.gif">');

	$(document).on('click', '.tagsmenu li', function () {

		var t = $('b', this).html();
		if ($self != 'ckeditor') insTextAtCursor($self, t);
		else addTagInEditor(t);

		$('.tagsmenu').addClass('hidden');


	});

</script>
