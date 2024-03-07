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

$url = "history.php";

//$users = $db -> getAll("SELECT iduser, title FROM ".$sqlname."user WHERE iduser != '$iduser1' ".get_people($iduser1)." and identity = '$identity' ORDER by title");

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

$preset = $_COOKIE[ 'history_list' ] != '' ? json_decode( str_replace( '\\', '', (string)$_COOKIE[ 'history_list' ] ), true ) : [];

?>

<div class="flex-container">

	<DIV class="flex-string" id="lmenu">

		<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="face" id="face" value="<?= $face ?>">

			<span id="flyitbox"></span>

			<div class="contaner" data-id="filter">

				<div class="nano">

					<div class="nano-content p5">

						<div class="Bold fs-12 shad mt10 mb10" data-id="activities">
							<i class="icon-filter blue"></i>&nbsp;Фильтр&nbsp;
						</div>

						<hr>

						<DIV class="relativ cleared">

							<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
							<div class="idel clearinputs" data-func="preconfigpage">
								<i class="icon-block red hand"></i>
							</div>
							<div class="gray2 fs-09 em">По содержимому</div>

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

								<div class="gray2 fs-09 em">За период</div>

							</div>

						</DIV>

						<?php
						if ( ($tipuser != "Менеджер продаж" && $userRights['showhistory']) || $isadmin == 'on' ) {
						?>
						<hr>
						<div class="ydropDown flyit" data-id="users">
							<span>По Сотруднику</span>
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
									<label><input class="taskss" name="user[]" type="checkbox" id="user[]" checked="checked" value="<?= $iduser1 ?>">&nbsp;<B class="red"><?= current_user($iduser1) ?></B></label>
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

						<DIV id="deals" class="p5">
							<label class="ellipsis">
								<input class="taskss" name="to_task" type="checkbox" id="to_task" value="yes" onClick="preconfigpage()">
								&nbsp;Только привязанные к делам
							</label>
						</DIV>

						<hr>

						<DIV id="activities" class="pt5">

							<A href="javascript:void(0)" onClick="clearFilter()" title="Сбросить фильтр" class="pull-aright mt5 gray"><i class="icon-eye-off blue" id="ifilter"></i></A>
							<div class="pad5">
								<i class="icon-search blue"></i>&nbsp;<b class="shad">По типу</b>
							</div>
							<div class="pt10 pb10">
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

										<div class="ydropString ellipsis">
											<label>
												<input class="taskss" name="tsk[]" type="checkbox" id="tsk[]" value="СобытиеCRM" <?=(in_array('СобытиеCRM', (array)$preset['tsk']) ? "checked" : "")?>>&nbsp;
												<span class="bullet-mini graybg" style="margin-bottom:1px"></span>&nbsp;
												СобытиеCRM
											</label>
										</div>
										<div class="ydropString ellipsis">
											<label>
												<input class="taskss" name="tsk[]" type="checkbox" id="tsk[]" value="ЛогCRM" <?=(in_array('ЛогCRM', (array)$preset['tsk']) ? "checked" : "")?>>&nbsp;
												<span class="bullet-mini graybg" style="margin-bottom:1px"></span>&nbsp;
												ЛогCRM
											</label>
										</div>
										<?php
										//print_r($preset['tsk']);
										$res = $db -> getAll("SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task','activ') and identity = '$identity' ORDER by aorder");
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

						<hr>

						<div class="wp100 pl10 pr10 apply-btn div-center">

							<a href="javascript:void(0)" onclick="preconfigpage()" class="button" title="Применить"><i class="icon-filter"></i> Применить фильтры</a>

						</div>

					</div>

				</div>

			</div>

			<div class="contaner p5" data-id="stat">

				<a href="javascript:void(0)" onclick="getSwindow('reports/ent-activitiesByTime.php', 'Статистика активности')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Статистика</a>

			</div>

		</form>

	</DIV>

</div>