<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*          ver. 2019.2         */
/* ============================ */
?>
<DIV class="mainbg nano" id="lmenu" data-step="5" data-intro="<h1>Фильтры</h1>Помогают отфильтровать данные по параметрам" data-position="right">

	<div class="nano-content">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="isLead" id="isLead" value="yes">
			<input type="hidden" name="tar" id="tar" value="list">
			<input type="hidden" name="ord" id="ord" value="datum"/>
			<input type="hidden" name="tuda" id="tuda" value="desc"/>

			<div class="contaner p5 mt5 contaner-utms">

				<div class="p5 Bold fs-12 mb10 mt10">
					<i class="icon-filter blue"></i>&nbsp;ФИЛЬТР
				</div>

				<DIV style="overflow: hidden;">

					<div class="pt10 pb10">

						<div class="relativ">
							<input id="wordu" name="wordu" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
							<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
						</div>
						<div class="smalltext gray">По Источнику клиента, Названию источника, URL</div>

					</div>

				</DIV>

			</div>

			<div class="contaner p5 contaner-source">

				<div class="p5 Bold fs-12 mb10 mt10">
					<i class="icon-filter blue"></i>&nbsp;ФИЛЬТР
				</div>

				<DIV style="overflow: hidden;">

					<div class="pt10 pb10">

						<div class="relativ">
							<input id="words" name="words" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
							<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
						</div>
						<div class="smalltext gray">По Источнику клиента, Названию источника</div>

					</div>

				</DIV>

			</div>

			<div class="contaner p5 contaner-lists">

				<div class="p5 Bold fs-12 mb10 mt10">
					<i class="icon-filter blue"></i>&nbsp;ФИЛЬТР
				</div>

				<DIV class="relativ" style="overflow: hidden;">

					<DIV class="mt10">

						<div class="row" id="lperiod">

							<div class="inline wp45">
								<INPUT name="da1" type="text" id="da1" value="" class="dstart inputdate wp100">
							</div>
							<div class="inline wp10 pt7 text-center">&nbsp;&divide;&nbsp;</div>
							<div class="inline wp45">
								<INPUT name="da2" type="text" id="da2" value="" class="dend inputdate wp100">
							</div>

							<div class="wp100">

								<a href="javascript:void(0)" onclick="preconfigpage()" class="greenbtn button dotted wp100" title="Применить">Применить</a>

							</div>

						</div>

						<div class="paddtop5 div-center">

							<select name="period" id="period" class="wp100" data-goal="lperiod" data-action="period" <?=(empty($preset['period']) ? '' : 'data-selected="'.$preset['period'].'"')?>  <?=(empty($preset['period']) ? '' : 'data-select="false"')?> data-js="preconfigpage">
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

					<div class="pt10 pb10">

						<div class="relativ">
							<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
							<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="preconfigpage();"><i class="icon-search blue"></i></a></span>
						</div>
						<div class="smalltext gray">По Имени, email, телефону, описанию</div>

					</div>

					<?php
					//print_r($lusers);
					if ($tipuser != "Менеджер продаж" || $iduser1 == $GLOBALS['coordinator']) {

						//print_r($lusers);

						$s = (!empty($lusers) && (int)$lusers[0] > 0) ? "and iduser IN (".yimplode(",", $lusers).")" : '';

						$result = $db -> getAll("SELECT * FROM ".$sqlname."user WHERE iduser != '$iduser1' $s and identity = '$identity' ORDER by title");
						$count = count($result) + 1;
						?>
						<div class="pt10 pb10">

							<div class="pb10"><b>По Сотруднику:</b></div>
							<div class="pad101 fs-09" style="overflow-x: hidden; overflow-y: auto; max-height: 200px">

								<div class="pt10 pb10 pl10">

									<div class="ellipsis inline mb5">

										<label><input class="user" name="user[]" type="checkbox" id="user[]" value="0" onclick="preconfigpage()">&nbsp;&nbsp;<span class="gray2">не назначено</span></label>

									</div>

									<br>

									<div class="ellipsis inline mb5">

										<label><input class="user" name="user[]" type="checkbox" id="user[]" value="<?= $iduser1 ?>" onclick="preconfigpage()">&nbsp;&nbsp;<span class="green"><?= current_user($iduser1) ?></span></label>

									</div>

									<br>

									<?php
									foreach ($result as $data) {
										?>
										<div class="ellipsis inline mb5">

											<label><input class="user" name="user[]" type="checkbox" id="user[]" value="<?= $data['iduser'] ?>" onclick="preconfigpage()">&nbsp;&nbsp;<?= $data['title'] ?></label>

										</div>
										<br>
										<?php
									}
									?>
								</div>

							</div>

						</div>
						<?php
					}
					elseif ($leadsettings['leadCanView'] == 'yes' && !empty($lusers)) {

						$result = $db -> getAll("SELECT * FROM ".$sqlname."user WHERE iduser!='".$iduser1."' and iduser IN (".yimplode(",", $lusers).") and acs_plan = 'on' and identity = '$identity' ORDER by title");
						$count  = count($result);
						?>

						<div class="paddtop10 paddbott10">

							<div class="pb10"><b>По Сотруднику:</b></div>
							<div class="pad101 fs-09" style="overflow-x: hidden; overflow-y: auto; max-height: 200px">
								<div class="pt10 pb10 pl10">

									<?php if ($leadsettings['leadMethod'] == 'free') { ?>
										<div class="ellipsis inline mb5">
											<label><input class="user" name="user[]" type="checkbox" id="user[]" value="0" onclick="preconfigpage()" checked>&nbsp;&nbsp;<span class="">Не назначено</span></label>
										</div>
										<br>
									<?php } ?>
									<div class="ellipsis inline mb5">
										<label><input class="user" name="user[]" type="checkbox" id="user[]" value="<?= $iduser1 ?>" onclick="preconfigpage()" <?= ($iduser1 != $GLOBALS['coordinator'] ? 'checked' : '') ?>>&nbsp;&nbsp;<span class="green Bold">Только свои</span></label>
									</div>
									<br>
									<?php
									foreach ($result as $data) {
										?>
										<div class="ellipsis inline mb5">
											<label><input class="user" name="user[]" type="checkbox" id="user[]" value="<?= $data['iduser'] ?>" onclick="preconfigpage()">&nbsp;&nbsp;<?= $data['title'] ?>
											</label></div><br/>
										<?php
									}
									?>

								</div>
							</div>
						</div>
						<?php
					}
					?>
					<div class="pt10 pb10">

						<b>По статусу:</b>
						<div style="display:inline-block" class="pull-aright">
							<A href="javascript:void(0)" onclick="clearFilter()" title="Сбросить фильтр"><i class="icon-eye-off blue" id="ifilter"></i></A>&nbsp;
						</div>

					</div>
					<div class="p10">

						<div class="ellipsis inline mb5">
							<label><input class="taskss" name="statuss[]" type="checkbox" id="statuss[]" value="0" onclick="preconfigpage()" checked>&nbsp;&nbsp;<B class="red">Открытые</B></label>
						</div>
						<br>
						<div class="ellipsis inline mb5">
							<label><input class="taskss" name="statuss[]" type="checkbox" id="statuss[]" value="1" onclick="preconfigpage()" checked>&nbsp;&nbsp;<B class="green">В работе</B></label>
						</div>
						<br>
						<div class="ellipsis inline mb5">
							<label><input class="taskss" name="statuss[]" type="checkbox" id="statuss[]" value="2" onclick="preconfigpage()">&nbsp;&nbsp;<B class="blue">Обработанные</B></label>
						</div>
						<br>
						<div class="ellipsis inline mb5">
							<label><input class="taskss" name="statuss[]" type="checkbox" id="statuss[]" value="3" onclick="preconfigpage()">&nbsp;&nbsp;<B class="gray">Закрытые</B></label>
						</div>

					</div>

				</DIV>

			</div>

			<div class="contaner p5 contaner-utms">

				<a class="button orangebtnt" onclick="doLoad('modules/leads/form.leads.php?action=utms.edit')" title="Добавить ссылку" style="display: block"><i class="icon-plus-circled"></i>&nbsp;&nbsp;Добавить ссылку&nbsp;&nbsp;</a>

			</div>

			<div class="contaner p5 contaner-source">

				<a class="button orangebtnt" onclick="doLoad('modules/leads/form.leads.php?action=source.edit')" title="Добавить ссылку" style="display: block"><i class="icon-plus-circled"></i>&nbsp;&nbsp;Добавить источник&nbsp;&nbsp;</a>

			</div>

			<div class="contaner p5">

				<a href="javascript:void(0)" onclick="getSwindow('reports/leads2014.php', 'Статистика по заявкам')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Статистика</a>

			</div>

		</form>

	</div>

</DIV>
