<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<?php
$y = $_REQUEST['y']; if(!$y) $y = date('Y');
$m = $_REQUEST['m']; if(!$m) $m = date('m');

$all = $_REQUEST['all'];

$users = $db -> getAll("SELECT iduser, title FROM ".$sqlname."user WHERE iduser != '$iduser1' ".get_people($iduser1)." and identity = '$identity' ORDER by title");
?>

<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">
	<input type="hidden" name="year" id="year" value="<?=$year?>">
	<input type="hidden" name="view" id="view" value="">

<DIV class="mainbg nano" id="lmenu">

	<span id="flyitbox"></span>

	<div class="nano-content mt5">

		<div class="contaner p5 hidden" id="userlist">

			<div class="shad">
				<i class="icon-filter blue"></i>&nbsp;Фильтры&nbsp;
			</div>

			<div class="mt20">

				<div class="ydropDown flyit" data-id="users">
					<span>По Сотруднику</span>
					<span class="ydropCount"><?=$count?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox fly users" data-id="users" style="max-height: 50vh">
						<div class="yunSelect"><i class="icon-cancel-circled2"></i>Снять выделение</div>
						<div class="ydropString ellipsis" title="Я">
							<label><input class="taskss" name="user[]" type="checkbox" id="user[]" checked="checked" value="<?=$iduser1?>" onClick="configpage()">&nbsp;<B class="red"><?=current_user($iduser1)?></B></label>
						</div>
						<?php
						foreach ($users as $data){
						?>
						<div class="ydropString ellipsis">
							<label><input class="taskss" name="user[]" type="checkbox" id="user[]" checked="checked" value="<?=$data['iduser']?>" onClick="configpage()">&nbsp;<?=$data['title']?></label>
						</div>
						<?php } ?>
					</div>
				</div>

			</div>

		</div>

		<div class="contaner p5">

			<div class="paddtop10 paddbott10">
				<b>По статусу:</b>
			</div>
			<div class="pad10">
				<div style="display:inline-block; padding:2px 0;" class="ellipsis"><label><input class="taskss" name="onlyactive" type="checkbox" id="onlyactive" value="yes" onClick="configpage()" checked>&nbsp;&nbsp;<B class="red">Только активные</B></label></div><br />
			</div>

		</div>

		<div class="contaner p5">

			<div class="uppercase paddbott10"><i class="icon-info-circled"></i><b>Инструкция</b></div>
			<ul>
				<li class="mb10"><a href="javascript:void(0)" onclick="editPlan('','export')" title="Экспорт"><b class="blue"><i class="icon-download"></i>Скачайте таблицу</b></a> в Excel - формат XLSX</li>
				<li class="mb10">Откройте в Excel</li>
				<li class="mb10">Откорректируйте плановые значения</li>
				<li class="mb10">Сохраните файл, даже если не вносили изменения</li>
				<li class="mb10"><a href="javascript:void(0)" title="Импорт" onclick="editPlan('','import')"><b class="blue"><i class="icon-upload"></i>Импортируйте</b></a> показатели обратно в CRM</li>
			</ul>

			<hr>

			<div class="pad5"><a href="javascript:void(0)" onclick="help('<?=$helper['plan']?>')"><b class="blue"><i class="icon-youtube blue"></i>&nbsp;Видео-инcтрукция</b></a></div>

		</div>

		<div class="contaner p5" data-id="stat">

			<a href="javascript:void(0)" onclick="getSwindow('reports/ent-planDoByPayment.php', 'Выполнение плана')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Выполнение плана</a>

		</div>

		<div>&nbsp;</div>

	</div>

</DIV>

</form>