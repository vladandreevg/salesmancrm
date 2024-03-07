<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

use Salesman\User;

$y = $_REQUEST['y'];
if (!$y) $y = date('Y');
$m = $_REQUEST['m'];
if (!$m) $m = date('m');

$all = $_REQUEST['all'];

$xusers = ( stripos( $tipuser, 'Руководитель' ) === false || $isadmin != 'on') ? User::userArray(null, null, $iduser1) : User::userArray();

$users = $activeUsers = $notActiveUsers = [];
foreach ($xusers as $user){

	if($user['active'] == 'yes'){
		$activeUsers[] = $user;
	}
	else{
		$notActiveUsers[] = $user;
	}

}
$users = array_merge($activeUsers, $notActiveUsers);

$preset = $_COOKIE[ 'todo_list' ] != '' ? json_decode( str_replace( '\\', '', $_COOKIE[ 'todo_list' ] ), true ) : [];
?>

<div class="flex-container">

	<DIV class="flex-string" id="lmenu">

		<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="ord" id="ord" value="desc">
			<input type="hidden" name="tar" id="tar" value="my">

			<span id="flyitbox"></span>

			<div class="contaner p5" data-id="filter" style="overflow-y: auto">

				<div class="Bold fs-12 shad mt10 mb10" data-id="activities">
					<i class="icon-filter blue"></i>&nbsp;Фильтр&nbsp;
				</div>

				<hr>

				<DIV class="relativ cleared">

					<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
					<div class="idel clearinputs" data-func="preconfigpage">
						<i class="icon-block red hand"></i>
					</div>
					<div class="gray2 fs-09 em">Заголовок, Агенда</div>

				</DIV>

				<DIV class="mt10">

					<div class="row" id="speriod">

						<div class="inline wp45">
							<INPUT name="da1" type="text" id="da1" value="" class="dstart inputdate wp100">
						</div>
						<div class="inline wp10 pt7 text-center">&nbsp;&divide;&nbsp;</div>
						<div class="inline wp45">
							<INPUT name="da2" type="text" id="da2" value="" class="dend inputdate wp100">
						</div>

					</div>

					<div class="pt5 div-center">

						<select name="period" id="period" class="wp100" data-goal="speriod" data-action="period" <?=(empty($preset['period']) ? '' : 'data-selected="'.$preset['period'].'"')?> data-js2="preconfigpage" <?=(empty($preset['period']) ? '' : 'data-select="false"')?>>
							<option selected="selected" value="">-за всё время-</option>

							<?php
							foreach ($calendarPeriods as $name => $title){
								print '<option data-period="'.$name.'" '.($preset['period'] == $name ? 'selected' : '').'>'.mb_ucfirst(strtolower($title)).'</option>';
							}
							?>
							<!--<option data-period="today">Сегодня</option>
							<option data-period="yestoday">Вчера</option>

							<option data-period="calendarweekprev">Неделя прошлая</option>
							<option data-period="calendarweek">Неделя текущая</option>

							<option data-period="monthprev" selected>Месяц прошлый</option>
							<option data-period="month">Месяц текущий</option>

							<option data-period="quartprev">Квартал прошлый</option>
							<option data-period="quart">Квартал текущий</option>

							<option data-period="year">Год</option>-->
						</select>

					</div>

				</DIV>

				<?php
				if ( $tipuser != "Менеджер продаж" || $isadmin == 'on' ) {
				?>
				<hr>
				<div class="ydropDown flyit" data-id="users">
					<span>Исполнитель</span>
					<span class="ydropCount"><?= count((array)$preset['user']) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
					<div class="yselectBox fly users" data-id="users" style="max-height: 50vh">
						<div class="right-text">
							<div class="ySelectAll w0 inline" title="Выделить всё">
								<i class="icon-plus-circled"></i>Всё
							</div>
							<div class="yunSelect w0 inline" title="Снять выделение">
								<i class="icon-minus-circled"></i>Ничего
							</div>
						</div>
						<div class="ydropString ellipsis" title="Я">
							<label>
								<input class="taskss" name="user[]" type="checkbox" id="user[]" checked="checked" value="<?= $iduser1 ?>">&nbsp;<B class="red"><?= current_user($iduser1) ?></B>
							</label>
						</div>
						<?php
						foreach ($users as $data) {

							if($data['id'] != $iduser1) {
								?>
								<div class="ydropString ellipsis">
									<label class="<?php echo($data['active'] != 'yes' ? "gray" : "") ?>">
										<input class="taskss" name="user[]" type="checkbox" id="user[]" checked="checked" value="<?= $data['id'] ?>">&nbsp;<?= $data['title'] ?>
									</label>
								</div>
								<?php
							}
						}
						?>
					</div>
				</div>
				<?php
				}
				else{
					print '<input type="hidden" name="user" id="user" value="'.$iduser1.'">';
				}
				?>

				<hr>

				<DIV id="activities" class="pt5">
					<div class="pb10">
						<div class="ydropDown flyit" data-id="todo">
							<span>Тип</span>
							<span class="ydropCount"><?=count((array)$preset['tsk'])?> выбрано</span><i class="icon-angle-down pull-aright"></i>
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
								$res = $db -> getAll("SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task') and identity = '$identity' ORDER by aorder");
								foreach ($res as $data) {
									?>
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="tsk[]" type="checkbox" id="tsk[]" value="<?= $data['title'] ?>" <?=(in_array($data['title'], (array)$preset['tsk']) ? "checked" : "")?>>&nbsp;&nbsp;
											<span class="bullet-mini" style="background:<?= $data['color'] ?>; margin-bottom:1px"></span>&nbsp;
											<?= $data['title'] ?>
										</label>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</DIV>

				<div class="divider mb10">Прочее</div>
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

				</div>

				<div class="divider mt10 mb10">Статус</div>
				<div class="viewdiv1 graybg1 fs-09">

					<DIV id="tome" class="pl10" title="Все">
						<label class="ellipsis">
							<input class="taskss" name="onlydo" type="radio" id="onlydo" value="all" checked>
							&nbsp;Все
						</label>
					</DIV>

					<DIV id="tome" class="pl10" title="Только выполненные">
						<label class="ellipsis">
							<input class="taskss" name="onlydo" type="radio" id="onlydo" value="yes">
							&nbsp;Только выполненные
						</label>
					</DIV>

					<DIV id="tome" class="pl10" title="Только активные">
						<label class="ellipsis">
							<input class="taskss" name="onlydo" type="radio" id="onlydo" value="no">
							&nbsp;Только активные
						</label>
					</DIV>

					<DIV id="tome" class="pl10" title="Только активные">
						<label class="ellipsis">
							<input class="taskss" name="onlydo" type="radio" id="onlydo" value="old">
							&nbsp;Только просроченные
						</label>
					</DIV>

				</div>

				<DIV id="priotityes" class="pt15">

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
				<DIV id="speeds" class="pt15">

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

				<hr>

				<div class="wp100 mt10 pl10 pr10 apply-btn div-center">

					<a href="javascript:void(0)" onclick="preconfigpage()" class="button" title="Применить"><i class="icon-filter"></i> Применить фильтры</a>

				</div>

			</div>

			<div class="space-30"></div>

		</form>

	</DIV>

</div>