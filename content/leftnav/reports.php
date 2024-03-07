<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\User;

$sort = get_people( $iduser1 );
?>
<DIV id="lmenu" class="mainbg nano" style="font-size:0.95em">

	<div class="nano-content">

		<div class="contaner">

			<div class="modules--name mb10"><i class="icon-chart-bar"></i>&nbsp;АНАЛИТИКА</div>

			<DIV class="box--child" id="param_datum" data-step="5" data-intro="<h1>Период анализа.</h1>Действует не во всех отчетах" data-position="right">

				<div class="flex-container fs-12" id="speriod">

					<div class="flex-string wp5 pt7 text-right">
						<i class="icon-calendar-1 blue"></i>
					</div>
					<div class="flex-string wp40">
						<INPUT name="da1" type="text" id="da1" value="" class="dstart clean inputdate wp90 blue fs-12 text-center">
					</div>
					<div class="flex-string wp5 pt7 text-center">&nbsp;&divide;&nbsp;</div>
					<div class="flex-string wp40">
						<INPUT name="da2" type="text" id="da2" value="" class="dend clean inputdate wp90 blue fs-12 text-center">
					</div>

				</div>

				<div class="paddtop5 div-center">

					<select name="period" id="period" class="w160" data-goal="speriod" data-action="period">
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

				</div>

			</DIV>

			<?php
			$iduser = ($tipuser == 'Руководитель с доступом') ? 0 : $iduser1;
			$daa    = User ::userArray( $iduser );
			if ( !empty( $daa ) ) {
				?>
				<hr>

				<div class="togglerbox hand pad5 fs-10 block" data-id="filteru">
					<i class="icon-user-1 gray"></i>&nbsp;<b>Сотрудники</b>&nbsp;<i class="icon-angle-up pull-aright" id="mapic"></i>
				</div>

				<div id="filteru" data-step="6" data-intro="<h1>Сотрудники.</h1>Выбор конкретных сотрудников для анализа" data-position="right">

					<DIV class="row margtop10 margleft5" id="param_user1">
						<?php
						$usersActivee = '';
						$usersUnActivee = '';
						$u = [];

						foreach ( $daa as $k => $d ) {

							if ( !in_array( $d['id'], $u ) ) {

								if ( $d['secrty'] == 'yes' )
									$usersActivee .= '
									<label class="flex-container fs-10 ha box--child wp100 p3">
										<div class="flex-string wp10">
											<input name="user_list[]" type="checkbox" id="user_list[]" value="'.$d['id'].'" checked>
										</div>
										<div class="flex-string wp90">
											<div class="ellipsis">'.$d['title'].'</div>
										</div>
									</label>';
								else $usersUnActivee .= '
									<label class="flex-container fs-10 ha box--child wp100 p3">
										<div class="flex-string wp10">
											<input name="user_list[]" type="checkbox" id="user_list[]" value="'.$d['id'].'">
										</div>
										<div class="flex-string wp90 gray">
											<div class="ellipsis">'.$d['title'].'</div>
										</div>
									</label>';

							}

							$u[] = $d['id'];

						}

						print $usersActivee;//."<hr>".$usersUnActivee;

						if ( $usersUnActivee != '' ) {
							?>
							<hr>

							<div class="togglerbox hand paddtop5 paddbott5 margtop54 full gray2" data-id="unactiveusersbox" onclick="$('.nano').nanoScroller();">
								Не активные сотрудники&nbsp;<i class="icon-angle-down pull-aright paddright10" id="mapic"></i>
							</div>

							<div id="unactiveusersbox" class="hidden wp100">

								<div class="infodiv flex-container wp100 box--child fs-09 mt5">
									<div class="flex-string wp50 hand" onclick="userSelectAll()">
										<i class="icon-buffer"></i>Всё
									</div>
									<div class="flex-string wp50 hand" onclick="userUnSelect()"><i class="icon-th"></i>Ничего
									</div>
								</div>

								<?= $usersUnActivee ?>

							</div>
						<?php } ?>
					</DIV>

				</div>
				<?php
			}
			?>

			<hr>

			<div class="togglerbox hand pad5 fs-10 block" data-id="filter2" onclick="$('.nano').nanoScroller();">
				<i class="icon-building gray"></i>&nbsp;<b>Клиенты</b>&nbsp;<i class="icon-angle-down pull-aright" id="mapic"></i>
			</div>

			<div id="filter2" class="pad10 hidden" data-step="7" data-intro="<h1>Выбор клиентов.</h1>Выбор конкретных клиентов для анализа" data-position="right">
				<div class="pull-aright">
					<a href="javascript:void(0)" onclick="doLoad('reports/fieldselect.php?action=get_client')" title="Добавить параметр"><i class="icon-plus-circled green"></i></a><a href="javascript:void(0)" onclick="$('#clients_list\\[\\] option').remove()" title="Очистить"><i class="icon-cancel-circled red"></i></a>&nbsp;<i class="icon-info-circled blue" title="Параметры действуют во всех отчетах"></i>
				</div>
				<br/><br/>
				<DIV class="text-center">
					<div id="dogparam">
						<select name="clients_list[]" size="3" multiple="multiple" id="clients_list[]" style="width: 100%; height:200px"></select>
					</div>
					<br>
				</DIV>
			</div>

			<hr>

			<div class="togglerbox hand pad5 fs-10 block" data-id="filter" onclick="$('.nano').nanoScroller();">
				<i class="icon-briefcase-1 gray"></i>&nbsp;<b><?= $lang['face']['DealsName'][0] ?></b>&nbsp;<i class="icon-angle-down pull-aright" id="mapic"></i>
			</div>

			<div id="filter" class="hidden" data-step="8" data-intro="<h1>Параметры сделок.</h1>Выбор сделок по параметрам" data-position="right">
				<DIV id="param_dogovor" class="pad3">
					<div class="text-right">
						<a href="javascript:void(0)" onclick="addfstring()" title="Добавить параметр"><i class="icon-plus-circled green"></i></a>
						<a href="javascript:void(0)" onclick="$('#fields tr').remove()" title="Очистить"><i class="icon-cancel-circled red"></i></a>&nbsp;
						<i class="icon-info-circled blue" title="Параметры действуют только в отчетах по <?= $lang['face']['DealsName'][2] ?>"></i>
					</div>
					<br>
					<DIV align="center">
						<div id="dogparam">
							<table id="fields"></table>
						</div>
					</DIV>
				</DIV>
			</div>

		</div>

		<div>&nbsp;</div>

	</div>

</DIV>
<!--/Меню/-->