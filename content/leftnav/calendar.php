<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */

/* ============================ */

use Salesman\Elements;

$preset = $_COOKIE['task_list'] != '' ? json_decode( str_replace( '\\', '', $_COOKIE['task_list'] ), true ) : [];
$preset['tsk'] = !empty($preset['tsk']) ? $preset['tsk'] : [];
?>
<div class="nano flex-container no--overflow">

	<DIV class="nano-content flex-string" id="lmenu">

		<form id="pageform" name="pageform">
			<input type="hidden" name="tar" id="tar" value="my">
			<input type="hidden" name="m" id="m" value="">
			<input type="hidden" name="y" id="y" value="">

			<span id="flyitbox"></span>

			<div class="contaner no-shadow" data-id="calendar">

				<div id="calendar"></div>

				<div onclick="thisMounth()" class="pad5 hand div-center">
					<i class="icon-calendar-1 blue"></i>Текущий месяц
				</div>

			</div>

			<div class="contaner p5 hidden" data-id="stat">

				<a href="javascript:void(0)" onclick="addTask()" class="greenbtn button wp100" title="Добавить"><i class="icon-plus-circled"></i> Добавить</a>

			</div>

			<div class="contaner mt15 no-shadow" data-id="filterform">

				<div class="shad p5 hidden" data-id="activities">

					<i class="icon-filter blue"></i>&nbsp;Фильтр&nbsp;

				</div>

				<DIV id="activities" class="pt5">

					<div class="divider">Тип активности</div>

					<div class="pt10">
						<div class="ydropDown flyit" data-id="todo">
							<span>Тип</span>
							<span class="ydropCount"><?= !empty($preset['tsk']) ? count( $preset['tsk'] ) : 0 ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
							<div class="yselectBox fly todo" data-id="todo">
								<div class="right-text">
									<div class="ySelectAll w0 inline" title="Выделить всё">
										<i class="icon-plus-circled"></i>Всё
									</div>
									<div class="yunSelect w0 inline" title="Снять выделение">
										<i class="icon-minus-circled"></i>Ничего
									</div>
								</div>
								<?php
								//print_r($preset['tsk']);
								$res = $db -> getAll( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task','activ') and identity = '$identity' ORDER by aorder" );
								foreach ( $res as $data ) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="tsk[]" type="checkbox" id="tsk[]" value="<?= $data['title'] ?>" <?= (in_array( $data['title'], $preset['tsk'] ) ? "checked" : "") ?>>&nbsp;&nbsp;
											<span class="bullet-mini" style="background:<?= $data['color'] ?>; margin-bottom:1px"></span>&nbsp;
											<?= $data['title'] ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>

				</DIV>

				<DIV id="priotityes" class="pt5">

					<div class="divider">Приоритет</div>

					<div class="pt10 flex-container text-center fs-07">

						<div class="flex-string wp33 mb5" data-field="priority">
							<label>
								<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="1"><br>
								<B class="gray2">Не важно</B>
							</label>
						</div>
						<div class="flex-string wp33 mb5" data-field="priority">
							<label>
								<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="0"><br>
								<B class="blue">Обычно</B>
							</label>
						</div>
						<div class="flex-string wp33 mb5" data-field="priority">
							<label>
								<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="2"><br>
								<B class="red">Важно</B>
							</label>
						</div>

					</div>

				</DIV>
				<DIV id="speeds" class="pt5">

					<div class="divider">Срочность</div>

					<div class="pt10 flex-container text-center fs-07">

						<div class="flex-string wp33 mb5">
							<label>
								<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="1"><br>
								<B class="gray2">Не срочно</B>
							</label>
						</div>
						<div class="flex-string wp33 mb5">
							<label>
								<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="0"><br>
								<B class="blue">Обычно</B>
							</label>
						</div>
						<div class="flex-string wp33 mb5">
							<label>
								<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="2"><br>
								<B class="red">Срочно</B>
							</label>
						</div>

					</div>

				</DIV>

				<DIV id="users" class="pt10 hidden">

					<div class="divider">Ответственные</div>

					<?php
					if ( !in_array( $GLOBALS['tipuser'], [
						"Менеджер продаж",
						"Администратор",
						"Специалист"
					] ) ) {
						?>
						<div class="pt5">

							<?php
							$element = new Elements();
							print $element -> UsersSelect( "iduser", [
								"class" => ['wp100'],
								"jsact" => "",
								"sel"   => '-1',
								'self'  => false
							] );
							?>
							<div class="smalltxt gray">По сотруднику</div>

						</div>
					<?php } ?>

				</DIV>

				<div class="divider mt10 mb10">Прочее</div>
				<div class="viewdiv1 graybg1 fs-09">

					<DIV id="deals" class="p5 pl10" title="Только привязанные к сделке">
						<label class="ellipsis">
							<input class="taskss" name="to_deal" type="checkbox" id="to_deal" value="">
							&nbsp;Только привязанные к сделке
						</label>
					</DIV>

					<DIV id="tome" class="p5 pl10" title="Назначенные мне">
						<label class="ellipsis">
							<input class="taskss" name="to_me" type="checkbox" id="to_me" value="">
							&nbsp;Назначенные мне
						</label>
					</DIV>

					<!--
					<DIV id="tome" class="p5 pl10" title="Только важные">
						<label class="ellipsis">
							<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="2">
							&nbsp;Только важные
						</label>
					</DIV>

					<DIV id="tome" class="p5 pl10" title="Только срочные">
						<label class="ellipsis">
							<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="2">
							&nbsp;Только срочные
						</label>
					</DIV>
					-->

				</div>

				<hr>

				<div class="wp100 pl10 pr10 pt20 apply-btn div-center">
					<a href="javascript:void(0)" onclick="configpage()" class="button" title="Применить"><i class="icon-filter"></i> Применить фильтры</a>
				</div>

				<div class="space-100"></div>

			</div>

		</form>

	</DIV>

</div>