<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2014 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */
?>

<DIV class="mainbg nano" id="lmenu">

	<div class="nano-content mt5">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="folder" id="folder" value="inbox">
			<input type="hidden" name="mid" id="mid" value="">

			<div class="contaner hidden" id="orangebutton">
				<A href="javascript:void(0)" onclick="$mailer.compose()" class="button" style="display:block; margin:0; padding:10px 5px;">
					<span class="hidden-ipad"><i class="icon-mail-alt"><i class="icon-plus-circled sup"></i></i></span>
					<span class="visible-iphone hidden-normal"><i class="icon-plus-circled"></i></span>
					<span class="hidden-ipad">&nbsp;&nbsp;Новое <span class="hidden-netbook">сообщение</span></span>
				</A>
			</div>

			<div class="contaner">

				<div class="relativ pt10 pl10">

					<B class="shad"><i class="icon-folder-open blue"></i>&nbsp;Папки</B>

					<div class="pull-aright hidden">
						<A href="javascript:void(0)" onclick="configmpage()" class="blue" title="Обновить список писем"><i class=" icon-arrows-cw"></i></A>
						<a href="javascript:void(0)" onclick="$mailer.get('yes');" class="blue" title="Проверить вручную"><i class="icon-download"></i></a>
					</div>

				</div>

				<br>

				<div class="mailfolder" id="whitebutton">

					<div class="relative conversation">
						<A href="#conversation" onclick="razdel('conversation')" class="mbutton unread" style="display:block">
							<i class="icon-mail relati"><i class="icon-chat-1 orange my3"></i></i>
							<span class="hidden-ipad">&nbsp;Вся почта
								<span class="pull-aright uboxcount" style="font-weight: 400 !important" title="Не прочтенные">..</span>
								<span class="pull-aright boxcount">..</span>
							</span>
						</A>
					</div>
					<div class="relative inbox">
						<A href="#inbox" onclick="razdel('inbox')" class="mbutton unread" style="display:block">
							<i class="icon-mail relati"><i class=" icon-forward-1 green my3"></i></i>
							<span class="hidden-ipad">&nbsp;Входящие
								<span class="pull-aright uboxcount" style="font-weight: 400 !important" title="Не прочтенные">..</span>
								<span class="pull-aright boxcount">..</span>
							</span>
						</A>
					</div>

					<div class="relative sended">
						<A href="#sended" onclick="razdel('sended')" class="mbutton" style="display:block">
							<i class="icon-mail relati"><i class=" icon-reply green my3"></i></i>
							<span class="hidden-ipad">
								&nbsp;Отправленные&nbsp;
								<span class="pull-aright boxcount">..</span>
							</span>
						</A>
					</div>

					<hr>

					<div class="relative draft">
						<A href="#draft" onclick="razdel('draft')" class="mbutton" style="display:block">
							<i class="icon-doc-text gray"></i><span class="hidden-ipad">&nbsp;Черновики <span class="pull-aright boxcount">..</span></span>
						</A>
					</div>

					<div class="relative trash">
						<A href="#trash" onclick="razdel('trash')" class="mbutton" style="display:block">
							<i class="icon-trash gray"></i><span class="hidden-ipad">&nbsp;Корзина</span>
						</A>
					</div>
					<div class="relative blacklist" style="height:28px">
						<A href="javascript:void(0)" onclick="$mailer.blacklist()" class="mbutton" style="display:block">
							<i class="icon-thumbs-down-alt gray"></i><span class="hidden-ipad">&nbsp;Черный список <span class="pull-aright boxcount">..</span></span>
						</A>
					</div>

					<?php
					/*
					<div class="relative template"><A href="#template" onclick="razdel('template')" class="mbutton" style="display:block"><i class="icon-file-code blue"></i>&nbsp;Шаблоны&nbsp;</A></div>
					*/
					?>

					<hr>

					<div class="relative" style="height:28px">
						<A href="javascript:void(0)" onclick="$mailer.signature()" class="mbutton" style="display:block">
							<i class="icon-vcard broun"></i>
							<span class="hidden-ipad">&nbsp;Автоподписи</span>
						</A>
					</div>
					<div class="relative" style="height:28px">
						<A href="javascript:void(0)" onclick="$mailer.account()" class="mbutton" style="display:block">
							<i class="icon-cog-alt broun"></i><span class="hidden-ipad">&nbsp;Настройка</span>
						</A>
					</div>
					<div class="relative" style="height:28px">
						<A href="javascript:void(0)" onclick="$mailer.tpl()" class="mbutton" style="display:block">
							<i class="icon-doc-text broun"></i><span class="hidden-ipad">&nbsp;Шаблоны</span>
						</A>
					</div>
				</div>
			</div>

			<div class="contaner hidden-ipad">
				<div><i class="icon-search blue"></i>&nbsp;<b class="shad">Поиск</b></div>
				<DIV class="relativ cleared pad5">
					<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="configmpage">
					<span class="idel red clear paddtop10"><i class="icon-block-1" title="Очистить"></i></span>
					<div class="smalltxt gray">По теме, адресату, тексту:</div>
				</DIV>

				<DIV class="ml5 mr5">

					<div class="row" id="eperiod">

						<div class="inline wp45"><INPUT name="date1" type="text" id="date1" value="" class="dstart inputdate wp100"></div>
						<div class="inline wp10 pt7 text-center">&nbsp;&divide;&nbsp;</div>
						<div class="inline wp45">
							<INPUT name="date2" type="text" id="date2" value="" class="dend inputdate wp100">
						</div>
						<div class="wp100">
							<a href="javascript:void(0)" onclick="configmpage()" class="greenbtn button dotted wp100" title="Применить">Применить</a>
						</div>

					</div>

					<div class="paddtop5 div-center">

						<?php
						print $preset['period'];
						?>

						<!--<select name="period" id="period" class="wp100" data-goal="eperiod" data-action="period" <?php /*=(empty($preset['period']) ? '' : 'data-select="false"')*/?> <?php /*=(empty($preset['period']) ? '' : 'data-selected="'.$preset['period'].'"')*/?> data-js="configmpage">-->
						<select name="period" id="period" class="wp100" data-goal="eperiod" data-action="period" data-select="false" data-js="configmpage">
							<option data-period="all" selected="selected">-за всё время-</option>

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

					<hr>

					<a href="javascript:void(0)" onclick="clearFilter()" class="redbtn button dotted wp100" title="Сбросить фильтры">Сбросить фильтры</a>

				</DIV>
			</div>


		</form>

	</div>

</DIV>