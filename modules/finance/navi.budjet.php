<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.20          */
/* ============================ */
?>
<DIV class="mainbg nano" id="lmenu" data-step="7" data-intro="<h1>Фильтры</h1>Помогают отфильтровать данные по параметрам. Доступны разные блоки для разных разделов" data-position="right">

	<div class="nano-content mt5">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="tar" id="tar" value="<?= $tar ?>">
			<input type="hidden" name="year" id="year" value="<?= $year ?>">
			<input type="hidden" name="ord" id="ord" value="datum">
			<input type="hidden" name="tuda" id="tuda" value="desc">

			<span id="flyitbox"></span>

			<div class="contaner p5 visible-iphone">

				<div class="div-center mfh-12">

					<a href="javascript:void(0)" onclick="changeyear('prev');"><i class="icon-angle-double-left"></i><span class="prev"><?= $y1 ?></span></a>&nbsp;&nbsp;&nbsp;&nbsp;
					<span class="red Bold miditxt current"><?= $year ?></span>&nbsp;&nbsp;&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="changeyear('next');"><span class="next"><?= $y2 ?></span><i class="icon-angle-double-right"></i></a>

				</div>

				<div class="div-center mt10 mfh-12 menu-period" id="dmonth">

					<hr>

					<div class="pl10 text-left">Период:</div>

				</div>

				<hr>

			</div>

			<div class="contaner p5 contaner-provider hidden">

				<div class="mb10 mfh-12"><i class="icon-filter blue"></i>&nbsp;<b class="shad">По статусу</b></div>

				<div class="p10 mfh-12">

					<div class="p5 block">
						<label><input name="pdoo[]" type="checkbox" id="pdoo[]" value="do" onclick="preconfigpage();"><i class="icon-ok green fs-09"></i>&nbsp;Проведено</label>
					</div>

					<div class="p5 block">
						<label><input name="pdoo[]" type="checkbox" id="pdoo[]" value="plan" onclick="preconfigpage();" checked><i class="icon-clock blue fs-09"></i>&nbsp;Запланировано</label>
					</div>

					<div class="p5 block">
						<label><input name="pdoo[]" type="checkbox" id="pdoo[]" value="noadd" onclick="preconfigpage();" checked><i class="icon-attention gray fs-09"></i>&nbsp;Не добавлено</label>
					</div>

				</div>

			</div>

			<div class="contaner p5 contaner-provider hidden mfh-12">

				<!--
				<div class="ftabs" data-id="container">

					<div id="ytabs">

						<ul class="gray flex-container blue">
							<li class="flex-string pad3" data-link="partner"><?= $lang['agents']['Partner'][1] ?></li>
							<li class="flex-string pad3" data-link="contractor"><?= $lang['agents']['Contractor'][1] ?></li>
						</ul>

					</div>
					<div id="container">

						<div class="partner cbox">

							<div class="mt10 pb10 selectall hand center-text" data-tip="partner">
								<i class="icon-eye-off blue" id="ifilter"></i>&nbsp;Переключить все
							</div>

							<div class="mt15 pb10 fs-09 mfh-10 cardBlock" style="height: 120px; overflow: hidden" data-height="120">
								<?php
								/*
								$res = $db -> getAll("SELECT * FROM {$sqlname}clientcat WHERE type IN ('partner') and identity = '$identity' ORDER by type, title");
								foreach ($res as $da) {

									if ($da['type'] == 'partner') {
										$tip = 'Партнер';
										$cb  = '<input name="partid[]" type="checkbox" id="partid[]" value="'.$da['clid'].'" onclick="preconfigpage();" />';
									}
									if ($da['type'] == 'contractor') {
										$tip = 'Поставщик';
										$cb  = '<input name="conid[]" type="checkbox" id="conid[]" value="'.$da['clid'].'" onclick="preconfigpage();" />';
									}
									?>
									<label class="mb10">
										<div class="flex-container box--child">
											<div class="flex-string wp10">
												<?= $cb ?>
											</div>
											<div class="flex-string wp90">
												<div class="ellipsis"><?= $da['title'] ?></div>
												<br>
												<div class="ellipsis fs-09 gray hidden-iphone" title="<?= $tip ?>">
													<b><?= $tip ?></b></div>
											</div>
										</div>
									</label>
									<?php
								}
								*/
								?>
							</div>

							<div class="div-center blue hand cardResizer fs-07" title="Развернуть" data-pozi="close">
								<i class="icon-angle-down"></i><i class="icon-angle-down"></i><i class="icon-angle-down"></i>
							</div>

						</div>
						<div class="contractor cbox">

							<div class="mt10 pb10 selectall hand center-text" data-tip="contractor">
								<i class="icon-eye-off blue" id="ifilter"></i>&nbsp;Переключить все
							</div>

							<div class="mt15 pb10 fs-09 mfh-10 cardBlock" style="height: 120px; overflow: hidden" data-height="120">
								<?php
								/*
								$res = $db -> getAll("SELECT * FROM {$sqlname}clientcat WHERE type IN ('contractor') and identity = '$identity' ORDER by type, title");
								foreach ($res as $da) {

									if ($da['type'] == 'partner') {
										$tip = 'Партнер';
										$cb  = '<input name="partid[]" type="checkbox" id="partid[]" value="'.$da['clid'].'" onclick="preconfigpage();">';
									}
									if ($da['type'] == 'contractor') {
										$tip = 'Поставщик';
										$cb  = '<input name="conid[]" type="checkbox" id="conid[]" value="'.$da['clid'].'" onclick="preconfigpage();">';
									}
									?>
									<label class="mb10">
										<div class="flex-container box--child">
											<div class="flex-string wp10">
												<?= $cb ?>
											</div>
											<div class="flex-string wp90">
												<div class="ellipsis"><?= $da['title'] ?></div>
												<br>
												<div class="ellipsis fs-09 gray hidden-iphone" title="<?= $tip ?>">
													<b><?= $tip ?></b>
												</div>
											</div>
										</div>
									</label>
									<?php
								}
								*/
								?>
							</div>

							<div class="div-center blue hand cardResizer fs-07" title="Развернуть" data-pozi="close">
								<i class="icon-angle-down"></i><i class="icon-angle-down"></i><i class="icon-angle-down"></i>
							</div>

						</div>

					</div>

				</div>
				-->
				<div class="mb15 mfh-12"><i class="icon-filter blue"></i>&nbsp;<b class="shad">По контрагенту</b></div>
				<div class="mt15 fs-10 relativ cleared">

					<input id="aword" name="aword" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
					<div class="idel clearinputs" data-func="preconfigpage">
						<i class="icon-block red hand"></i>
					</div>
					<div class="gray fs-09">По названию</div>

				</div>

				<hr>

				<div class="pb10">

					<?php
					$usr = (stripos($tipuser, 'Руководител') === false || $isadmin != 'on') ? $iduser1 : "-1";

					$element = new Salesman\Elements();
					print $userSelect = $element -> UsersSelect('xuser', array(
						'class' => 'wp100',
						'users' => get_people($iduser1, 'yes'),
						"sel"   => "-1",
						'jsact' => 'preconfigpage()'
					));
					?>
					<span class="gray smalltext">По сотруднику</span>

				</div>

			</div>

			<div class="contaner p5 contaner-rs hidden">

				<div class="mb15 mfh-12"><i class="icon-filter blue"></i>&nbsp;<b class="shad">По счетам</b></div>

				<div class="mt10 cardBlock mfh-12 fs-09" style="height: 150px; overflow: hidden" data-height="150">

					<?php
					$x = !empty($userRights['dostup']['rc']) ? " (SELECT COUNT(*) FROM {$sqlname}mycomps_recv WHERE cid = mc.id AND id IN (".yimplode(",", $userRights['dostup']['rc']).") ) > 0 AND " : "";
					$result = $db -> getAll("SELECT * FROM {$sqlname}mycomps `mc` WHERE $x mc.identity = '$identity' ORDER BY mc.name_ur");
					foreach ($result as $data) {

						$z = !empty($userRights['dostup']['rc']) ? " id IN (".yimplode(",", $userRights['dostup']['rc']).") AND " : "";
						$rec = $db -> getAll("SELECT * FROM {$sqlname}mycomps_recv WHERE $z cid = '".$data['id']."' AND bloc != 'yes' AND identity = '$identity' ORDER BY id");

						if (!empty($rec)) {

							foreach ($rec as $datar) {

								if ($datar['tip'] == 'bank') $tip = 'Банк';
								if ($datar['tip'] == 'kassa') $tip = 'Касса';

								?>
								<label onclick="preconfigpage();" class="mb10">
									<div class="flex-container box--child">
										<div class="flex-string wp10">
											<input name="rs[]" type="checkbox" id="rs[]" value="<?= $datar['id'] ?>">
										</div>
										<div class="flex-string wp90">
											<div class="ellipsis"><?= $datar['title'] ?></div>
											<br>
											<div class="ellipsis fs-09 gray" title="<?= $data['name_shot'] ?>">
												<b><?= $data['name_shot'] ?></b></div>
										</div>
									</div>
								</label>
								<?php
							}

						}
						//else print '<b class="red smalltxt">Нет счетов</b><br>';

					}
					?>

				</div>
				<div class="div-center blue hand cardResizer fs-07" title="Развернуть" data-pozi="close">
					<i class="icon-angle-down"></i><i class="icon-angle-down"></i><i class="icon-angle-down"></i>
				</div>

			</div>

			<div class="contaner p5 contaner-journal" data-step="12" data-intro="<h1>Фильтры</h1>Дополнительные фильтры для раздела" data-position="bottom">

				<div class="mt20 mb10 mfh-12"><i class="icon-folder-1 blue"></i>&nbsp;<b class="shad">По статьям</b>
				</div>

				<div class="mt10 mb10 fs-10">

					<div class="ydropDown flyit" data-id="category">
						<span>Статья расхода</span>
						<span class="ydropCount"><?= count((array)$prcat) ?> выбрано</span><i class="icon-angle-down pull-aright"></i>
						<div class="yDoit action button hidden" onclick="preconfigpage()">Применить</div>
						<div class="yselectBox fly category" data-id="category">

							<div class="right-text">
								<div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё
								</div>
								<div class="yunSelect w0 inline" title="Снять выделение">
									<i class="icon-minus-circled"></i>Ничего
								</div>
							</div>

							<div class="pt10 pb10 Bold pl10 fs-11">Доходы</div>

							<?php
							$res = $db -> getAll("SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and tip='dohod' and identity = '$identity' ORDER BY title");
							foreach ($res as $da) {

								print '<div class="pt5 pb5 Bold pl10 gray graybg-sub">'.$da['title'].'</div>';

								$result = $db -> getAll("SELECT * FROM {$sqlname}budjet_cat WHERE subid = '".$da['id']."' and tip='dohod' and identity = '$identity' ORDER BY title");
								foreach ($result as $data) {

									print '
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="category[]" type="checkbox" id="category[]" value="'.$data['id'].'">&nbsp;'.$data['title'].'
										</label>
									</div>
									';

								}

							}
							?>

							<div class="pt10 pb10 Bold pl10 fs-11">Расходы</div>

							<?php
							$res = $db -> getAll("SELECT * FROM {$sqlname}budjet_cat WHERE subid = '0' and tip='rashod' and identity = '$identity' ORDER BY title");
							foreach ($res as $da) {

								print '<div class="pt5 pb5 Bold pl10 gray graybg-sub">'.$da['title'].'</div>';

								$result = $db -> getAll("SELECT * FROM {$sqlname}budjet_cat WHERE subid = '".$da['id']."' and tip='rashod' and identity = '$identity' ORDER BY title");
								foreach ($result as $data) {

									print '
									<div class="ydropString ellipsis">
										<label>
											<input class="taskss" name="category[]" type="checkbox" id="category[]" value="'.$data['id'].'">&nbsp;'.$data['title'].'
										</label>
									</div>
									';

								}

							}
							?>

						</div>
					</div>

				</div>

			</div>

			<div class="contaner p5 contaner-budjet">

				<div class="mt20 mb10 mfh-12">
					<i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b>
				</div>

				<div class="mt15 fs-10 relativ cleared">

					<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
					<div class="idel clearinputs" data-func="preconfigpage">
						<i class="icon-block red hand"></i>
					</div>

				</div>
				<div class="gray fs-09">По названию расхода, комментарию</div>

				<hr>

				<div class="pb10 mfh-12">

					<div class="block pl10 p5">
						<label><input name="doo" type="radio" id="doo" value="all" onclick="preconfigpage();" checked/>&nbsp;Все</label>
					</div>

					<div class="block pl10 p5">
						<label><input name="doo" type="radio" id="doo" value="do" onclick="preconfigpage();"/>&nbsp;Проведенные</label>
					</div>

					<div class="block pl10 d5">
						<label><input name="doo" type="radio" id="doo" value="nodo" onclick="preconfigpage();"/>&nbsp;Не проведенные</label>
					</div>

				</div>

			</div>

			<div class="contaner p5 contaner-invoices hidden">

				<a href="javascript:void(0)" onclick="getSwindow('/reports/ent-PaymentsByUser.php', 'Оплаты по сотрудникам')" class="greenbtn button wp100" title="Показать аналитику"><i class="icon-chart-line"></i> Статистика</a>

			</div>

			<div class="contaner p5 contaner-invoices hidden">

				<div class="mt20 mb10 mfh-12">
					<i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b>
				</div>

				<div class="pb10 relativ cleared">

					<input id="iword" name="iword" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
					<div class="idel clearinputs" data-func="preconfigpage">
						<i class="icon-block red hand"></i>
					</div>
					<span class="gray smalltext">По номеру счета, договора, названию Клиента или Сделки</span>

				</div>

				<hr>

				<div class="pb10">

					<?php
					$usr = (stripos($tipuser, 'Руководител') === false || $isadmin != 'on') ? $iduser1 : "-1";

					$element = new Salesman\Elements();
					print $userSelect = $element -> UsersSelect('iduser', array(
						'class' => 'wp100',
						'users' => get_people($iduser1, 'yes'),
						'sel'   => $usr,
						'jsact' => 'preconfigpage()'
					));
					?>
					<span class="gray smalltext">По сотруднику</span>

				</div>

			</div>

			<?php
			if($userSettings['dostup']['budjet']['action'] == 'yes'){
			?>
			<div class="contaner p5">

				<div class="div-center flex-container button--group">

					<div class="flex-string">
						<a class="button greenbtn" onclick="editBudjet('0','edit', {xtip:'contragent'})" title="Добавить доход/расход"><i class="icon-plus-circled"></i>Добавить</a>
					</div>
					<div class="flex-string">
						<a class="button redbtn" onclick="editBudjet('','move')" title="Переместить м/у счетами"><i class="icon-shuffle-1"></i>Переместить</a>
					</div>

				</div>

			</div>
			<?php } ?>


			<?php
			if($userSettings['dostup']['budjet']['money'] == 'yes'){
			?>
			<div class="contaner p5">

				<div class="togglerbox hand pad5" data-id="mstat">
					<i class="icon-chart-pie blue"></i>&nbsp;<B class="shad">ОСТАТОК по СЧЕТАМ</B>&nbsp;<i class="icon-angle-up" id="mapic"></i>
				</div>

				<div class="mt10" id="mstat">
					<div id="stat"></div>
				</div>

			</div>
			<?php } ?>

		</form>

	</div>

</DIV>