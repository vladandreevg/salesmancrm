<?php
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<DIV class="mainbg nano" id="lmenu">
	<div class="nano-content">

		<form id="pageform" name="pageform">
			<input id="page" name="page" type="hidden" value="1">
			<input name="tip" id="tip" type="hidden" value="list"/>

			<div class="contaner p5 mt5">
				<div><B class="shad"><i class="icon-mail-alt blue"></i>&nbsp;ИНСТРУМЕНЫ</B></div>
				<br>
				<a href="#list" onclick="settip('list')" class="abutton" style="display:block">&nbsp;Список рассылок</a>
				<a href="javascript:void(0)" onclick="editMaillist('0','edit');" class="abutton" style="display:block">&nbsp;Добавить рассылку</a>
				<hr>
				<a href="#list.tpl" onclick="settip('list.tpl')" class="abutton" style="display:block">&nbsp;Список шаблонов</a>
				<a href="javascript:void(0)" onclick="editMaillist('0','tpl.edit');" class="abutton" style="display:block">&nbsp;Добавить шаблон</a>
			</div>

			<div class="contaner p5">
				<div class="div-center">
					<A href="javascript:void(0)" onclick="editMaillist('','edit');" class="button" style="display:block"><i class="icon-plus-circled white"></i>Добавить</A>
				</div>
			</div>

			<div class="contaner p5">
				<div><i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b></div>
				<div class="paddtop10">
					<div class="relativ">
						<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
						<span class="idel"><a href="javascript:void(0)" title="Найти" onClick="preconfigpage();"><i class="icon-search blue"></i></a></span>
					</div>
					<div class="smalltxt gray">По названию, описанию</div>
				</DIV>
			</div>

		</form>

	</div>
</DIV>