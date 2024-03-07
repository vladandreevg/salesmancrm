<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$y = $_REQUEST['y']; if(!$y) $y = date('Y');
$m = $_REQUEST['m']; if(!$m) $m = date('m');

$all = $_REQUEST['all'];

$url = "callhistory.php";

$users = $db -> getAll("SELECT iduser, title FROM ".$sqlname."user WHERE iduser != '$iduser1' ".get_people($iduser1)." and identity = '$identity' ORDER by title");
?>


<div class="flex-container">

	<DIV class="flex-string" id="lmenu">

		<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">
		<input type="hidden" name="page" id="page" value="1">
		<input type="hidden" name="face" id="face" value="<?=$face?>">

		<span id="flyitbox"></span>

		<div class="contaner p5" data-id="filter">

			<div class="nano">

			<div class="nano-content">

				<div class="shad" data-id="activities">
					<i class="icon-filter blue"></i>&nbsp;Фильтр&nbsp;
				</div>

				<DIV class="mt10">

					<div class="row" id="speriod">

						<div class="inline wp45"><INPUT name="da1" type="text" id="da1" value="" class="dstart inputdate wp100"></div>
						<div class="inline wp10 pt7 text-center">&nbsp;&divide;&nbsp;</div>
						<div class="inline wp45"><INPUT name="da2" type="text" id="da2" value="" class="dend inputdate wp100"></div>

						<div class="wp100">

							<a href="javascript:void(0)" onclick="configpage()" class="greenbtn button dotted wp100" title="Применить">Применить</a>

						</div>

					</div>

					<div class="paddtop5 div-center">

						<select name="period" id="period" class="wp100" data-goal="speriod" <?=(empty($preset['period']) ? '' : 'data-selected="'.$preset['period'].'"')?> <?=(empty($preset['period']) ? '' : 'data-select="false"')?> data-action="period" data-js="configpage">
							<option selected="selected">-за всё время-</option>

							<?php
							foreach ($calendarPeriods as $name => $title){
								print '<option data-period="'.$name.'" '.($preset['period'] == $name ? 'selected' : '').'>'.mb_ucfirst(strtolower($title)).'</option>';
							}
							?>

							<!--<option data-period="today">Сегодня</option>
							<option data-period="yestoday">Вчера</option>

							<option data-period="calendarweekprev">Неделя прошлая</option>
							<option data-period="calendarweek">Неделя текущая</option>

							<option data-period="monthprev">Месяц прошлый</option>
							<option data-period="month">Месяц текущий</option>

							<option data-period="quartprev">Квартал прошлый</option>
							<option data-period="quart">Квартал текущий</option>

							<option data-period="year">Год</option>-->
						</select>

						<div class="gray2 fs-09 em">За период</div>

					</div>

				</DIV>

				<?php if ($tipuser!="Менеджер продаж" && $tipuser!="Администратор"){?>

				<hr>

				<div>

					<select name="iduser" id="iduser" class="wp100" onChange="configpage()">

						<option value="<?=$iduser1?>" <?=((stripos($tipuser, 'Руководител') === false || $isadmin != 'on') ? "selected" : "")?>>Мои звонки</option>

						<option value="" <?=((stripos($tipuser, 'Руководител') !== false || $isadmin == 'on') ? "selected" : "")?>>Все</option>

						<?php
						$r = $db -> getAll("SELECT iduser, title FROM ".$sqlname."user WHERE (iduser != '$iduser1' AND iduser IN (".yimplode(",", get_people($iduser1, "yes")).")) AND secrty = 'yes' AND identity = '$identity' ORDER BY title");
						if(count($r) > 0){
						?>
						<optgroup label="Активные">
						<?php
						foreach ($r as $da){
							print '<option value="'.$da['iduser'].'" '.$s.'>'.$da['title'].'</option>';
						}
						?>
						</optgroup>
						<?php
						}

						$r = $db -> getAll("SELECT iduser, title FROM ".$sqlname."user WHERE (iduser != '$iduser1' and iduser IN (".yimplode(",", get_people($iduser1, "yes")).")) and secrty != 'yes' and identity = '$identity' ORDER by title");
						if(count($r) > 0){
						?>
						<optgroup label="Заблокированные">
						<?php
						foreach ($r as $da){
							print '<option value="'.$da['iduser'].'" '.$s.'>'.$da['title'].'</option>';
						}
						?>
						</optgroup>
						<?php } ?>

					</select>

					<div class="smalltxt gray">По сотруднику</div>

				</div>
				<?php } ?>

				<hr>

				<div class="p10">

					<div class="ellipsis mb5 block"><label><input class="taskss" name="task[]" type="checkbox" id="task[]" value="0" onClick="configpage()" checked />&nbsp;&nbsp;<i class="icon-ok-circled green"></i><B class="green">Отвеченные</B></label></div>
					<div class="ellipsis mb5 block"><label><input class="taskss" name="task[]" type="checkbox" id="task[]" value="1" onClick="configpage()" checked />&nbsp;&nbsp;<i class="icon-minus-circled red"></i><B class="red">Нет ответа</B></label></div>
					<div class="ellipsis mb5 block"><label><input class="taskss" name="task[]" type="checkbox" id="task[]" value="2" onClick="configpage()" checked />&nbsp;&nbsp;<i class="icon-block-1 broun"></i><B class="broun">Занято</B></label></div>

				</div>
				<hr>
				<div class="p10">

					<div class="ellipsis mb5 block"><label><input class="directt" name="direct[]" type="checkbox" id="direct[]" value="0" onClick="configpage()" checked />&nbsp;&nbsp;<i class="icon-down-big smalltxt green"></i>&nbsp;<B class="green">Входящий</B></label></div>
					<div class="ellipsis mb5 block"><label><input class="directt" name="direct[]" type="checkbox" id="direct[]" value="1" onClick="configpage()" checked />&nbsp;&nbsp;<i class="icon-up-big smalltxt blue"></i>&nbsp;<B class="blue">Исходящий</B></label></div>
					<div class="ellipsis mb5 block"><label><input class="directt" name="direct[]" type="checkbox" id="direct[]" value="2" onClick="configpage()" />&nbsp;&nbsp;<i class="icon-arrows-cw smalltxt broun"></i>&nbsp;<B class="broun">Внутрений</B></label></div>

				</div>

				<hr>

				<div class="paddtop10 paddbott10 tooltips" tooltip="<b>Поиск по номеру</b>: попробуйте удалить первый символ номера" tooltip-position="top">

					<div class="relativ">
						<input id="word" name="word" type="text" class="searchwordinput" data-func="configpage" placeholder="Впишите запрос">
						<div class="idel inline"><a href="#" title="Найти" onclick="configpage();"><i class="icon-search blue"></i></a></div>
					</div>
					<div class="smalltext gray">Номер, Название клиента, ФИО контакта</div>

				</div>

			</div>

			</div>

		</div>

		<div class="contaner p5" data-id="stat">

			<a href="javascript:void(0)" onclick="getSwindow('reports/call_history.php', 'Статистика звонков')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Статистика</a>

		</div>

		</form>

	</DIV>

</div>