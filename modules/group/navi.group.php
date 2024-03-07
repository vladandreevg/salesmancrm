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
<input type="hidden" name="page" id="page" value="1">
<input type="hidden" name="ord" id="ord" value="datum">
<input type="hidden" name="tar" id="tar" value="group">
<input type="hidden" name="tuda" id="tuda" value="">
<input type="hidden" name="gid" id="gid" value="">
<input type="hidden" name="gname" id="gname" value="">

<div class="contaner p5 mt5 razdel">

	<i class="icon-menu blue"></i>&nbsp;<B class="shad">ДЕЙСТВИЯ</B><br><br>

	<A href="javascript:void(0)" onclick="editGroup('','addgroup')" class="abutton" style="display:block">Добавить группу</A>
	<hr>
	<A href="#group" onclick="razdel('group');" class="abutton group" style="display:block">Группы</A>
	<A href="#glist" onclick="razdel('glist');" class="abutton glist" style="display:block">Подписчики</A>
</div>

<div class="contaner p5 contaner-glist">
	<div><i class="icon-filter blue"></i>&nbsp;<b class="shad">Поиск</b></div>
	<div class="paddtop10">
		<div class="relativ">
			<input id="word" name="word" type="text" placeholder="Впишите запрос" class="searchwordinput" data-func="preconfigpage">
			<span class="idel"><a href="javascript:void(0)" title="Найти" onClick="preconfigpage()"><i class="icon-search blue"></i></a></span>
		</div>
		<div class="smalltxt gray">Подписчик (имя, email)</div>
	</DIV>
</div>

</form>

</div>
</DIV>