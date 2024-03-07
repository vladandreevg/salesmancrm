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
<?php
if ($isadmin == 'on' || $tipuser == 'Администратор') {
	?>
	<DIV id="accordion">

		<h3 class="ui-state-active m0" aria-selected="true" aria-expanded="true" role="tab" data-id="base">
			<A href="javascript:void(0)" class="shado">Администрирование</A>
		</h3>

		<DIV data-id="base">

			<A href="#welcome" class="menu" title="Начало"><i class="icon-home"></i>&nbsp;Начало</A>
			<A href="#settings" class="menu" title="Общие настройки"><i class="icon-cog-alt"></i>&nbsp;Общие настройки</A>

			<div class="menudivider" align="center"><b>Документы</b></div>

			<A href="#mycompany" class="menu" title="Мои компании и счета"><i class="icon-building"></i>&nbsp;
				<?php
				print $fieldsNames['dogovor']['mcid'];

				if($fieldsNames['dogovor']['mcid'] != 'Компании и Счета'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Компании и Счета</div>';
				}
				?>
			</A>
			<A href="#direction" class="menu" title="Редактировать"><i class="icon-briefcase"></i>&nbsp;
				<?php
				print $fieldsNames['dogovor']['direction'];

				if($fieldsNames['dogovor']['direction'] != 'Направления деятельности'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Направления деятельности</div>';
				}
				?>
			</A>

			<A href="#contractsettings" class="menu" title="Документы и Счета"><i class="icon-doc-text-inv"></i>&nbsp;Номера документов</A>
			<A href="#contracteditor" class="menu" title="Типы документов"><i class="icon-docs"></i>&nbsp;Типы документов</A>
			<A href="#contracttemplate" class="menu" title="Шаблоны документов"><i class="icon-docs"></i>&nbsp;Шаблоны документов</A>

			<A href="#invoiceeditor" class="menu" title="Документы и Счета"><i class="icon-doc-text-inv"></i>&nbsp;Настройка счетов</A>
			<A href="#akteditor" class="menu" title="Акты"><i class="icon-doc-text-inv"></i>&nbsp;Настройка актов</A>
			<A href="#contractstatus" class="menu" title="Статусы документов"><i class="icon-tags"></i>&nbsp;Статусы документов</A>

			<div class="menudivider" align="center"><b>Орг.структура</b></div>

			<A href="#otdel" class="menu" title="Редактировать"><i class="icon-sitemap"></i>&nbsp;Отделы</A>
			<A href="#office" class="menu" title="Редактировать"><i class="icon-coffee"></i>&nbsp;Офисы</A>
			<A href="#users" class="menu" title="Управление пользователями"><i class="icon-users-1"></i>&nbsp;Сотрудники</A>
			<A href="#users.table" class="menu" title="Управление пользователями"><i class="icon-user-1"></i>&nbsp;Сотрудники (таблица)</A>

			<div class="menudivider"><b>Клиенты</b></div>

			<A href="#fields" class="menu" title="Настройки форм"><i class="icon-table"></i>&nbsp;Формы</A>

			<?php if (file_exists("content/admin/efields.php")) { ?>
				<A href="#efields" class="menu" title="Настройка Экспресс-формы"><i class="icon-table"></i>&nbsp;Экспресс-форма</A>
			<?php } ?>

			<A href="#industry" class="menu" title="Редактор отраслей клиентов"><i class="icon-commerical-building"></i>
				<?php
				print ($fieldsNames['client']['idcategory'] ?? '<b class="red">Отключено</b>');

				if($fieldsNames['client']['idcategory'] != 'Виды отраслей'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Виды отраслей</div>';
				}
				?>
			</A>
			<A href="#clientpath" class="menu" title="Источник клиента"><i class="icon-exchange"></i>
				<?php
				print ($fieldsNames['client']['clientpath'] ?? '<b class="red">Отключено</b>');

				if($fieldsNames['client']['clientpath'] != 'Источник клиента'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Источник клиента</div>';
				}
				?>
			</A>
			<A href="#loyalty" class="menu" title="Типы лояльности"><i class="icon-smile"></i>&nbsp;
				<?php
				print ($fieldsNames['person']['loyalty'] ?? '<b class="red">Отключено</b>');

				if($fieldsNames['person']['loyalty'] != 'Лояльность'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Типы лояльности</div>';
				}
				?>
			</A>
			<A href="#relations" class="menu" title="Типы отношений"><i class="icon-thumbs-up-alt"></i>
				<?php
				print ($fieldsNames['client']['tip_cmr'] ?? '<b class="red">Отключено</b>');

				if($fieldsNames['client']['tip_cmr'] != 'Типы отношений'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Типы отношений</div>';
				}
				?>
			</A>
			<A href="#territory" class="menu" title="Территории"><i class="icon-globe"></i>
				<?php
				print ($fieldsNames['client']['territory'] ?? '<b class="red">Отключено</b>');

				if($fieldsNames['client']['territory'] != 'Территория'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Территории</div>';
				}
				?>
			</A>

			<?php if ($tarif == 'Plus' or $tarif == 'Pro') { ?>

				<A href="#profile" class="menu" title="Профиль"><i class="icon-user-md"></i>&nbsp;Профили</A>
				<A href="#doubles" class="menu" title="Поиск дублей"><i class="icon-search"></i>&nbsp;Поиск дублей</A>

			<?php } ?>

			<div class="menudivider" align="center"><b>Сделки</b></div>

			<A href="#fields.deal" class="menu" title="Форма Сделки"><i class="icon-table"></i>&nbsp;Форма Сделки</A>
			<A href="#steps" class="menu" title="Этапы сделок"><i class="icon-chart-bar-1"></i>&nbsp;
				<?php
				print ($fieldsNames['dogovor']['idcategory'] ?? '<b class="red">Отключено</b>');

				if($fieldsNames['dogovor']['idcategory'] != 'Этапы сделок'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Этапы сделок</div>';
				}
				?>
			</A>

			<?php if (file_exists("content/admin/currency.php")) { ?>
				<A href="#currency" class="menu" title="Валюты"><i class="icon-euro"></i>&nbsp;Валюты</A>
			<?php } ?>
			<?php if (file_exists("content/admin/dealfieldsforstep.php")) { ?>
				<A href="#dealfieldsforstep" class="menu" title="Форма Сделки"><i class="icon-table"></i>&nbsp;Поля по этапам Сделки</A>
			<?php } ?>

			<A href="#multisteps" class="menu" title="МультиВоронка"><i class="icon-sort-alt-down"></i>&nbsp;МультиВоронка</A>
			<A href="#closedeals" class="menu" title="Статусы закрытых сделок"><i class="icon-medkit"></i>&nbsp;Статусы закрытых сделок</A>
			<A href="#dealtip" class="menu" title="Типы сделок"><i class="icon-briefcase"></i>
				<?php
				print ($fieldsNames['dogovor']['tip'] !== '' ? $fieldsNames['dogovor']['tip'] : '<b class="red">Отключено</b>');

				if($fieldsNames['dogovor']['tip'] != 'Тип сделки'){
					print '<div class="fs-07 flh-07 gray pl30">Стандарт: Типы сделок</div>';
				}
				?>
			</A>

			<?php if ($tarif == 'Plus' or $tarif == 'Pro') { ?>

				<A href="#controlpoints" class="menu" title="Контрольные точки"><i class="icon-briefcase"></i>&nbsp;Контрольные точки</A>

			<?php } ?>

			<?php if (file_exists("content/admin/deal.anketa.php")) { ?>

				<A href="#deal.anketa" class="menu" title="Анкеты по сделкам"><i class="icon-doc-inv-alt"></i>&nbsp;Анкеты по сделкам</A>

			<?php } ?>

			<div class="menudivider" align="center"><b>Прайс</b></div>

			<A href="#fields.price" class="menu" title="Настройка прайса"><i class="icon-table"></i>&nbsp;Поля Прайса</A>

			<div class="menudivider" align="center"><b>Отчеты</b></div>

			<A href="#report.editor" class="menu" title="Управление отчетами"><i class="icon-signal"></i>&nbsp;Управление отчетами</A>

			<div class="menudivider" align="center"><b>Прочее</b></div>

			<A href="#activities" class="menu" title="Типы и темы активности"><i class="icon-calendar"></i>&nbsp;Типы и темы активности</A>
			<A href="#tpl.editor" class="menu" title="Шаблоны для сообщений"><i class="icon-comment-empty"></i>&nbsp;Шаблоны для сообщений</A>

		</DIV>

		<h3 class="m0" data-id="module"><A href="javascript:void(0)" class="shado">Модули</A></h3>

		<DIV data-id="module">

			<A class="menu" href="#modules" title="Доступные модули"><i class="icon-tools"></i>&nbsp;Доступные модули</A>

			<hr>

			<?php
			$result = $db -> getAll("SELECT * FROM ".$sqlname."modules WHERE active = 'on' and identity = '$identity' ORDER by id");
			foreach ($result as $data) {

				$mpath = $data['mpath'];

				if($data['mpath'] == 'modworkplan')
					$mpath = 'workplan';

				$icon = (stripos($data['icon'], '.svg') === false) ? '<i class="'.$data['icon'].'"></i>' : '<img src="modules/'.$mpath.'/images/'.$data['icon'].'" width="16" height="16" class="mr5" style="margin-left:3px">';

				print '<a class="menu" href="#'.$mpath.'">'.$icon.'&nbsp;'.$data['title'].'</a>';

			}
			?>

		</DIV>

		<h3 class="m0" data-id="plugin"><A href="javascript:void(0)" class="shado">Интеграция</A></h3>

		<DIV data-id="plugin">

			<!--<A href="#services.editor" class="menu" title="Сервисы Рассылок"><i class="icon-mail"></i>&nbsp;Сервисы Рассылок</A>-->
			<A href="#services.other" class="menu" title="Разные интеграции"><i class="icon-article"></i>&nbsp;Разные интеграции</A>
			<A href="#smtp.editor" class="menu" title="Почтовый сервер"><i class="icon-mail-alt"></i>&nbsp;Почтовый сервер</A>

			<?php if ( $productInfo['sipeditor'] ) { ?>
				<A href="#sip.editor" class="menu" title="Интеграция"><i class="icon-asterisk"></i>&nbsp;Сервер АТС</A>
				<A href="#sip.users" class="menu pl20" title="Интеграция"><i class="icon-phone"></i>&nbsp;Номера сотрудников</A>
			<?php } ?>

			<A href="#mailer" class="menu" title="Почтовик"><i class="icon-mail-alt"></i>&nbsp;Почта сотрудников</A>

			<A href="#webhook" class="menu" title="WebHook"><i class="icon-shareable"></i>&nbsp;WebHook</A>
			<A href="#webhooklog" class="menu pl20" title="Логи работы WebHook"><i class="icon-list"></i>&nbsp;Логи работы WebHook</A>

			<A href="#plugins" class="menu" title="Плагины"><i class="icon-cog-alt"></i>&nbsp;Плагины</A>

		</DIV>

		<h3 class="m0" data-id="service"><A href="javascript:void(0)" class="shado">Обслуживание</A></h3>

		<DIV data-id="service">

			<?php
			if (!$isCloud) {
				?>
				<A href="#sysinfo" class="menu" title="Информация о системе"><i class="icon-cog-alt"></i>&nbsp;Информация о системе</A>
				<A href="#error.logs" class="menu" title="Лог ошибок"><i class="icon-cog-alt"></i>&nbsp;Лог ошибок</A>
			<?php } ?>

			<A href="#system.logs" class="menu" title="События в системе"><i class="icon-book"></i>&nbsp;События в системе</A>

			<?php
			if (!$isCloud) {
				?>
				<A href="#backup" class="menu" title="Резервные копии"><i class="icon-hdd"></i>&nbsp;Резервные копии</A>
			<?php } ?>

			<A href="#cleaner" class="menu" title="Обслуживание и очистка"><i class="icon-trash"></i>&nbsp;Обслуживание и очистка</A>

			<?php
			if (!$isCloud) {
				?>
				<A href="/developer/adminer/" class="menu" title="Adminer. Управление Базой данных" target="blank"><i class="icon-cog-alt"></i>&nbsp;Adminer</A>
			<?php } ?>

		</DIV>

	</DIV>
	<?php
}
else print '<div class="bad text-center">К сожалению у Вас нет прав Администратора</div>';
?>