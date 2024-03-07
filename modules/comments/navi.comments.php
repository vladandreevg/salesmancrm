<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<DIV class="mainbg nano" id="lmenu">

	<div class="nano-content mt5">

		<form id="pageform" name="pageform">
			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="tar" id="tar" value="themes">
			<input type="hidden" name="themeid" id="themeid" value="0">

			<div class="contaner p5 no-shadow">

				<div><i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b></div>

				<div class="pt10">
					<div class="relativ">
						<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="configpage">
						<span class="idel"><a href="javascript:void(0)" title="Найти" onclick="configpage();"><i class="icon-search blue"></i></a></span>
					</div>
					<div class="smalltxt gray">По названию, содержанию темы или ответа</div>
				</DIV>

			</div>

			<div class="divider">Фильтры</div>

			<div class="contaner p5 pl20 mt10">

				<label class="mb5 fs-10"><input name="isClose" type="radio" id="isClose" value="all" onClick="configpage();">&nbsp;Все</label>
				<label class="mb5 fs-10"><input name="isClose" type="radio" id="isClose" value="active" onClick="configpage();" checked>&nbsp;Открытые</label>
				<label class="mb5 fs-10"><input name="isClose" type="radio" id="isClose" value="closed" onClick="configpage();">&nbsp;Завершенные</label>

			</div>

			<div class="contaner p5 pl20">

				<label class="mb5 fs-10"><input type="checkbox" name="isDeal" id="isDeal" value="yes" onClick="configpage();">&nbsp;Привязано к Сделке</label>
				<label class="mb5 fs-10"><input type="checkbox" name="isClient" id="isClient" value="yes" onClick="configpage();">&nbsp;Привязано к Клиенту</label>
				<label class="mb5 fs-10"><input type="checkbox" name="isProject" id="isProject" value="yes" onClick="configpage();">&nbsp;Привязано к Проекту</label>

			</div>

			<?php
			//if ( !in_array($tipuser, ["Менеджер продаж","Поддержка продаж"]) ) {
			?>
			<div class="contaner p5">

				<?php
				$element = new Salesman\Elements();
				print $us = $element -> UsersSelect("iduser", ["class" => 'wp100', "jsact" => "configpage()", "sel" => '-1', 'self' => true]);
				?>
				<div class="smalltxt gray">По автору</div>

			</div>
			<?php //} ?>

			<div class="contaner p5 no-shadow div-center">

				<A href="javascript:void(0)" onclick="editComment('','edit');" class="button full"><i class="icon-plus-circled white"></i>Начать новое</A>

			</div>

		</form>

	</div>

</DIV>