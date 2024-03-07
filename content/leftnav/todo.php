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
<div class="flex-container">

	<DIV class="flex-string" id="lmenu">

		<form id="pageform" name="pageform">

			<input type="hidden" name="page" id="page" value="1">
			<input type="hidden" name="rezult" id="rezult" value="0">

			<div class="contaner p0" id="dtcal" style="height: calc(100vh - 50px)">

				<div id="calendar" class="mb5"></div>

				<div id="tasklist" class="nano" style="overflow-y:auto !important;">
					<div id="task" class="nano-content p5 pt10"></div>
				</div>

			</div>

			<?php //Разделы для Рабочего места оператора ?>

			<div class="contaner spacework hidden"></div>

		</form>

	</DIV>

</div>