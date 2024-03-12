<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

$roles = [
	"Руководитель организации",
	"Руководитель с доступом",
	"Руководитель подразделения",
	"Руководитель отдела",
	"Менеджер продаж",
	"Поддержка продаж",
	"Специалист",
	"Администратор"
];
?>

<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">

	<DIV class="mainbg nano" id="lmenu">

		<span id="flyitbox"></span>

		<div class="nano-content mt5">

			<div class="contaner p5" id="rolelist">

				<div class="shad">
					<i class="icon-filter blue"></i>&nbsp;Фильтры&nbsp;
				</div>

				<div class="mt20">

					<div class="ydropDown flyit" data-id="roles">
						<span>По Ролям</span>
						<span class="ydropCount">0 выбрано</span>
						<i class="icon-angle-down pull-aright"></i>
						<div class="yDoit action button hidden" onClick="configpage()">Применить</div>
						<div class="yselectBox fly roles" data-id="roles" style="max-height: 50vh">
							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>
							<?php
							foreach ( $roles as $role ) {
								?>
								<div class="ydropString ellipsis">
									<label><input class="taskss" name="role[]" type="checkbox" id="role[]" value="<?= $role ?>">&nbsp;<?= $role ?>
									</label>
								</div>
							<?php } ?>
						</div>
					</div>

				</div>

			</div>

			<div class="contaner p5">

				<div class="pt10 pb10 Bold">По статусу:</div>

				<div class="p10">
					<div style="display:inline-block; padding:2px 0;" class="ellipsis">
						<label>
							<input class="taskss" name="onlyactive" type="checkbox" id="onlyactive" value="yes" onClick="configpage()" checked>&nbsp;&nbsp;<B>Только активные</B>
						</label>
					</div>
				</div>

			</div>

			<?php
			if ( $isadmin == 'on' ) {
				?>

				<div class="contaner p5">

					<div class="uppercase pb10 Bold"><i class="icon-info-circled"></i>Совет</div>

					<ul class="ml15 p0 pl10">
						<li class="mb10">
							<a href="javascript:void(0)" onclick="$metrics.exportPlan()" title="Экспорт"><b class="blue"><i class="icon-download"></i>Загрузите таблицу</b></a> в Excel - формат CSV, кодировка Win-1251
						</li>
						<li class="mb10">Откройте Excel -> Импорт данных, разделитель - ;</li>
						<li class="mb10">Подготовьте планы для каждого сотрудника в Excel</li>
						<li class="mb10">
							<a href="javascript:void(0)" title="Импорт" onclick="$metrics.importPlan()"><b class="blue"><i class="icon-upload"></i>Импортируйте</b></a> в CRM
						</li>
					</ul>

					<hr>

					<div class="pad5"><a href="javascript:void(0)" onclick="help('<?=$helper['plan']?>')"><b class="blue"><i class="icon-youtube blue"></i>&nbsp;Видео-инcтрукция</b></a></div>

				</div>

			<?php } ?>

			<div class="contaner p5" data-id="stat">

				<a href="javascript:void(0)" onclick="getSwindow('/reports/ent-planDoByPayment.php', 'Выполнение плана')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Выполнение плана</a>

			</div>

			<div>&nbsp;</div>

		</div>

	</DIV>

</form>