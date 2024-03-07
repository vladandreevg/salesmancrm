<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        salesman.pro          */
/*        ver. 2018.x           */
/* ============================ */

//require_once "inc/class/Elements.php";
?>
<DIV class="mainbg nano" id="lmenu">

	<div class="nano-content">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="ord" id="ord" value="<?= $ord ?>">
			<input type="hidden" name="tuda" id="tuda" value="desc">

			<span id="flyitbox"></span>

			<div class="contaner p5">

				<div class="mt5 mb20 fs-11">

					<i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b>

					<div class="inline pull-aright">
						<a href="javascript:void(0)" title="Снять все фильтры" onclick="clearall();" class="gray"><i class="icon-filter blue"></i><i class="sup icon-cancel red"></i></a>&nbsp;&nbsp;
						<a href="javascript:void(0)" title="Обновить представление" onclick="preconfigpage();"><i class="icon-arrows-cw blue"></i></a>
					</div>

				</div>
				<DIV id="filter">

					<div class="mt10">

						<?php
						$usr = (stripos($tipuser, 'Руководител') === false || $isadmin != 'on') ? $iduser1 : "-1";

						$element = new \Salesman\Elements();
						print $element -> UsersSelect('iduser', array(
							"class" => "wp100",
							"users" => get_people($iduser1, "yes"),
							"sel"   => $usr,
							"jsact" => "configpage();"
						));
						?>

						<span class="smalltxt gray"><b>По сотруднику</b></span>

					</div>
					<hr>

					<div class="mt10">

						<div class="ydropDown flyit" data-id="todo">
							<span>Тип</span>
							<span class="ydropCount">0 выбрано</span><i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox fly todo" data-id="todo">
								<div class="right-text">
									<div class="ySelectAll w0 inline" title="Выделить всё">
										<i class="icon-plus-circled"></i>Всё
									</div>
									<div class="yunSelect w0 inline" title="Снять выделение">
										<i class="icon-minus-circled"></i
										>Ничего
									</div>
								</div>

								<?php
								$res = $db -> getAll("SELECT * FROM {$sqlname}complect_cat WHERE identity = '$identity' ORDER BY corder");
								foreach ($res as $data) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="point[]" type="checkbox" id="point[]" value="<?= $data['ccid'] ?>">&nbsp;&nbsp;
											<?= $data['title'] ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</div>

						<span class="smalltxt gray"><b>По Контрольной точке</b></span>

					</div>
					<hr>

					<DIV class="mt10">

						<div class="row" id="eperiod">

							<div class="inline wp45"><INPUT name="da1" type="text" id="da1" value="" class="dstart inputdate wp100"></div>
							<div class="inline wp10 pt7 text-center">&nbsp;&divide;&nbsp;</div>
							<div class="inline wp45"><INPUT name="da2" type="text" id="da2" value="" class="dend inputdate wp100"></div>

							<div class="wp100">

								<a href="javascript:void(0)" onclick="preconfigpage()" class="greenbtn button dotted wp100" title="Применить">Применить</a>

							</div>

						</div>

						<div class="paddtop5 div-center">

							<!--<select name="period" id="period" class="wp100" data-goal="eperiod" data-action="period" <?php /*=(empty($preset['period']) ? '' : 'data-selected="'.$preset['period'].'"')*/?>  <?php /*=(empty($preset['period']) ? '' : 'data-select="false"')*/?> data-js="preconfigpage">-->
							<select name="period" id="period" class="wp100" data-goal="eperiod" data-action="period" data-select="false" data-js="preconfigpage">
								<option selected="selected">-за всё время-</option>

								<?php
								foreach ($calendarPeriods as $name => $title){
									print '<option data-period="'.$name.'" '.($preset['period'] == $name ? 'selected' : '').'>'.mb_ucfirst(strtolower($title)).'</option>';
								}
								?>

								<!--<option data-period="today">Сегодня</option>
								<option data-period="yestoday">Вчера</option>
								<option data-period="tomorrow">Завтра</option>

								<option data-period="calendarweekprev">Неделя прошлая</option>
								<option data-period="calendarweek">Неделя текущая</option>
								<option data-period="calendarweeknext">Неделя следующая</option>

								<option data-period="monthprev">Месяц прошлый</option>
								<option data-period="month">Месяц текущий</option>
								<option data-period="monthnext">Месяц следующий</option>

								<option data-period="quartprev">Квартал прошлый</option>
								<option data-period="quart">Квартал текущий</option>
								<option data-period="quartnext">Квартал следующий</option>

								<option data-period="year">Год</option>-->
							</select>

							<div class="gray2 fs-09 em">За период</div>

						</div>

					</DIV>

					<hr>

					<div class="pad10">
						<div class="ellipsis">
							<label><input name="pay1" type="checkbox" id="pay1" value="yes" onClick="preconfigpage();"/>&nbsp;Сделано</label>
						</div>
						<div class="ellipsis block">
							<label><input name="pay2" type="checkbox" id="pay2" value="yes" onClick="preconfigpage();" checked/>&nbsp;Не сделано</label>
						</div>
					</div>

				</DIV>

			</div>

		</form>

	</div>

</DIV>
