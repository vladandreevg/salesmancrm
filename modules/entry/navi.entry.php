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
<DIV class="mainbg nano" id="lmenu">

	<div class="nano-content mt5" data-step="5" data-intro="<h1>Фильтры</h1>Помогают отфильтровать данные по параметрам" data-position="right">

		<form action="" id="pageform" name="pageform" method="post" enctype="multipart/form-data">
		<input type="hidden" id="page" name="page" value="1">

		<div class="contaner">

			<div class="p5 Bold fs-12 mb10 mt10">
				<i class="icon-filter blue"></i>&nbsp;Поиск
			</div>

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

					<select name="period" id="period" class="wp100" data-goal="eperiod" data-action="period" data-select="false" data-js="preconfigpage">
						<option selected="selected">-за всё время-</option>
						<option data-period="today">Сегодня</option>
						<option data-period="yestoday">Вчера</option>

						<option data-period="calendarweekprev">Неделя прошлая</option>
						<option data-period="calendarweek">Неделя текущая</option>

						<option data-period="monthprev">Месяц прошлый</option>
						<option data-period="month">Месяц текущий</option>

						<option data-period="quartprev">Квартал прошлый</option>
						<option data-period="quart">Квартал текущий</option>

						<option data-period="year">Год</option>
					</select>

					<div class="gray2 fs-09 em">За период</div>

				</div>

			</DIV>

			<hr>

			<div class="pt10 pb10">

				<select name="iduser" id="iduser" onchange="preconfigpage();" class="wp100">
					<option value="">Все</option>
					<?php
					$result = $db -> query("SELECT * FROM ".$sqlname."user WHERE identity = '$identity' ORDER by title");
					while ($data_array = $db -> fetch($result)){
						?>
						<option value="<?=$data_array['iduser']?>"><?=$data_array['title']?></option>
					<?php } ?>
				</select>
				<span class="smalltxt gray">По Ответственному</span>

			</div>

			<hr>

			<div class="pb10 pl10">

				<div class="pl10 p5 block">
					<label><input name="doo" type="radio" id="doo" value="all" onClick="preconfigpage();" checked>&nbsp;Все</label>
				</div>
				<div class="pl10 p5 block">
					<label><input name="doo" type="radio" id="doo" value="do" onClick="preconfigpage();">&nbsp;Со сделкой</label>
				</div>
				<div class="pl10 p5 block">
					<label><input name="doo" type="radio" id="doo" value="nodo" onClick="preconfigpage();">&nbsp;Без сделки</label>
				</div>

			</div>

		</div>

		<div class="contaner p5 contaner-list">

			<DIV class="pad5" id="searchboxz">

				<div class="pad10">
					<div class="ellipsis pb5 block">
						<label><input class="directt" name="status[]" type="checkbox" id="status[]" value="0" onClick="preconfigpage()" />&nbsp;&nbsp;<i class="icon-clock smalltxt broun"></i>&nbsp;<B class="broun">Новое</B></label>
					</div>
					<div class="ellipsis pb5 block">
						<label><input class="directt" name="status[]" type="checkbox" id="status[]" value="1" onClick="preconfigpage()" />&nbsp;&nbsp;<i class="icon-ok smalltxt green"></i>&nbsp;<B class="green">Обработано</B></label>
					</div>
					<div class="ellipsis pb5 block">
						<label><input class="directt" name="status[]" type="checkbox" id="status[]" value="2" onClick="preconfigpage()" />&nbsp;&nbsp;<i class="icon-cancel-circled smalltxt gray"></i>&nbsp;<B class="gray2">Отменено</B></label>
					</div>
				</div>

			</DIV>
		</div>

		</form>

		<div class="contaner p5 box-shadow">
			<div class="div-center"><A href="javascript:void(0)" onclick="editEntry('0','edit');" class="button block wp100"><i class="icon-plus-circled white"></i>Добавить</A></div>
		</div>

	</div>

</DIV>