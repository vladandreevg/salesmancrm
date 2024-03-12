<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2016.25          */
/* ============================ */

$title = "Рабочий стол";
$tar   = "desktop";

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

$firstTab = ( $userSettings[ 'startTab' ] != '' ) ? $userSettings[ 'startTab' ] : 'vigets';
?>
<DIV class="" id="rmenu" data-step="4" data-intro="<h1>Функциональная панель</h1>" data-position="right">

	<div class="tabs" data-step="50" data-intro="<h1>Разделы рабочего стола</h1>" data-position="right">

		<a href="javascript:void(0)" class="lpToggler" title="ToDo"><i class="icon-toggler"></i></a>

		<a href="#vigets" class="razdel active" data-id="vigets" title="<?= $lang[ 'face' ][ 'Vigets' ] ?>"><i class="icon-gauge"></i></a>
		<a href="#todo" class="razdel" data-id="todo" title="<?= $lang[ 'face' ][ 'TodosName' ][ 0 ] ?>"><i class="icon-bell-alt"></i></a>
		<a href="#bigcal" class="razdel hidden-iphone" data-id="bigcal" title="<?= $lang[ 'all' ][ 'Calendar' ] ?>"><i class="icon-calendar-1"></i></a>
		<a href="#weekcal" class="razdel hidden-iphone" data-id="weekcal" title="<?= $lang[ 'face' ][ 'weekcalendar' ] ?>"><i class="icon-calendar-inv"></i></a>
		<a href="#pipeline" class="razdel hidden-iphone" data-id="pipeline" title="Pipeline"><i class="icon-list-nested"></i></a>
		<?php
		if($settingsMore['dealHealthOn'] == 'yes'){
		?>
		<a href="#health" class="razdel visible-min-h700 hidden-iphone" data-id="health" title="<?= $lang[ 'face' ][ 'DealsHealth' ] ?>"><i class="icon-medkit"></i></a>
		<?php } ?>

		<?php

		unset($db);
		$db = new SafeMySQL($opts);

		$mcOn = $db -> getOne( "SELECT active FROM ".$sqlname."modules WHERE mpath = 'modcatalog' and identity = '$identity'" );

		if ( $mcOn == 'on' ) {

			$msettings = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
			$msettings = json_decode( $msettings, true );

			$mcCount = 0;

			if ( in_array( $iduser1, $msettings[ 'mcSpecialist' ] ) || in_array( $iduser1, $msettings[ 'mcCoordinator' ] ) ) {

				if ( $msettings[ 'mcDBoardSklad' ] == 'yes' ) $mcCount++;
				if ( $msettings[ 'mcDBoardZayavka' ] == 'yes' ) $mcCount++;
				if ( $msettings[ 'mcDBoardOffer' ] == 'yes' ) $mcCount++;

				if ( $mcCount > 0 ) print '<a href="#catalog" class="razdel visible-min-h700 hidden-ipad" data-id="catalog" data-control="catalog" data-filter="yes" title="'.$lang[ 'modcat' ][ 'tabName' ].'"><i class="icon-archive"></i></a>';

			}

		}

		?>

		<div title="<?= $lang[ 'face' ][ 'More' ] ?>" class="leftpop">

			<i class="icon-ellipsis"></i>

			<ul class="menu">

				<li><a href="#todoUsers" class="razdel nowrap"><i class="icon-calendar-1"></i> <?= $lang[ 'face' ][ 'UserTasks' ] ?></a></li>
				<li data-id="clients"><a href="#clients" class="razdel"><i class="icon-building"></i> <?= $lang[ 'face' ][ 'ClientsName' ][ 0 ] ?></a></li>
				<li data-id="contacts"><a href="#contacts" class="razdel"><i class="icon-users-1"></i> <?= $lang[ 'face' ][ 'ContactsName' ][ 0 ] ?></a></li>
				<li data-id="deals"><a href="#deals" class="razdel"><i class="icon-briefcase-1"></i> <?= $lang[ 'face' ][ 'DealsName' ][ 0 ] ?></a></li>

				<?php

				$mcOn = $db -> getOne( "SELECT active FROM ".$sqlname."modules WHERE mpath = 'modcatalog' and identity = '$identity'" );

				if ( $mcOn == 'on' ) {

					$msettings = $db -> getOne( "SELECT settings FROM ".$sqlname."modcatalog_set WHERE identity = '$identity'" );
					$msettings = json_decode( $msettings, true );

					if ( in_array( $iduser1, $msettings[ 'mcSpecialist' ] ) or in_array( $iduser1, $msettings[ 'mcCoordinator' ] ) ) {

						if ( $msettings[ 'mcDBoardSklad' ] == 'yes' ) $mcCount++;
						if ( $msettings[ 'mcDBoardZayavka' ] == 'yes' ) $mcCount++;
						if ( $msettings[ 'mcDBoardOffer' ] == 'yes' ) $mcCount++;

						if ( $mcCount > 0 ) print '<li class="hidden-min-h700"><a href="#catalog" class="razdel" data-id="catalog" data-control="catalog" data-filter="yes" title="'.$lang[ 'modcat' ][ 'tabName' ].'"><i class="icon-archive"></i> '.$lang[ 'modcat' ][ 'tabName' ].'</a></li>';

					}

				}

				?>

				<li class="hidden-min-h700" data-id="health"><a href="#health" class="razdel" data-id="health" title="<?= $lang[ 'face' ][ 'DealsHealth' ] ?>"><i class="icon-medkit"></i> <?= $lang[ 'face' ][ 'DealsHealth' ] ?></a></li>

			</ul>

		</div>
		<?php
		require_once $rootpath."/content/leftnav/leftpop.php";
		flush();
		?>

	</div>

	<?php
	include( "/content/leftnav/counters.php" );
	flush();
	?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php
	require_once $rootpath."/inc/menu.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-west mainbg disable--select simple">

	<?php
	require_once $rootpath."/content/leftnav/todo.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-center disable--select simple mainbg" style="">

	<!--Будет показан в мобильной версии-->
	<div class="hidden">
		<div id="tips">Рабочий стол</div>
	</div>

	<!--Виджеты-->
	<div class="tab vigets nano hidden" data-tab="vigets">

		<div class="nano-content">
			<div class="ui-layout-content" id="vigets"></div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.vigets();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i>
		</div>

	</div>

	<!--Pipeline-->
	<div class="tab pipeline nano hidden" data-tab="pipeline">

		<div class="nano-content">

			<div class="ui-layout-content" id="pipeline"></div>

			<div class="fixReloadButton" onclick="$desktop.pipeline();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
				<i class="icon-arrows-cw"></i></div>

		</div>

	</div>

	<!--Календарь-->
	<div class="tab bigcal nano relative  hidden" data-tab="bigcal">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite modules" id="bigcal">
				<div id="bigCal" class="tableCal"></div>
			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.bigcal();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--Календарь недельный-->
	<div class="tab weekcal nano relative  hidden" data-tab="weekcal">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite modules" id="weekcal">
				<div id="weekCal" class="tableCal"></div>
			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.weekcal();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--Список дел-->
	<div class="tab todo nano relative  hidden" data-tab="todo">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite modules" id="todo">

				<?php
				$task = [];
				$onlymy = $_COOKIE['onlymy'];
				//print "onlymy = $onlymy";

				if ( $_COOKIE['task'] ) {
					$task = explode( ",", $_COOKIE['task'] );
				}
				?>

				<!-- Пока эта часть не работает. Нужно перенести смежные функции-->
				<div class="filterdiv">

					<div class="togglerbox hand zagolovok div-center hidden-iphone" id="dtpCalFilter" data-id="CalFilter">
						<span class="blue"><i class="icon-filter"></i><b>Фильтр</b>&nbsp;<span><i class="icon-angle-down" id="mapic"></i></span>
					</div>
					<div id="CalFilter" class="pad10 hidden">

						<form name="filterform" id="filterform">

							<div class="flex-container" style="flex-wrap: nowrap; flex-flow: column wrap;">

								<?php
								$r = $db -> query( "SELECT * FROM ".$sqlname."activities WHERE filter IN ('all','task') and  identity = '$identity' ORDER by aorder" );
								while ($datay = $db -> fetch( $r )) {

									$color = ($datay['color'] == "") ? "gray" : $datay['color'];

									print '
									<div class="flex-string wp25 p5" style="flex-grow: unset;">
										<label class="ellipsis">
											<input name="tsk[]" type="checkbox" id="tsk[]" value="'.$datay['id'].'" '.(in_array( $datay['id'], $task ) || count( $task ) == 0 ? 'checked' : '').'>&nbsp;<b style="color:'.$color.'">'.get_ticon( $datay['title'] ).'</b>&nbsp;<B>'.$datay['title'].'</B>
										</label>
									</div>
									';

								}
								?>

							</div>

							<div class="infodiv flex-container bgwhite block left-text mt10 p5">

								<div class="flex-string wp80 flex-container inline border-box pt5" style="flex-wrap: nowrap; flex-flow: column wrap;">

									<div class="flex-container w160 Bold infodiv dotted mr5">
										<label>
											<input name="onlymy" id="onlymy" type="checkbox" <?php echo ($onlymy == 'yes' ? "checked" : "") ?> value="yes">&nbsp;Только мои&nbsp;<i class="icon-info-circled blue fs-09 info" title="Показывать только свои Напоминания"></i>
										</label>
									</div>

									<div class="pt10 w300 flex-container text-center fs-09 infodiv dotted mr5">

										<div class="flex-string wp33" data-field="priority">
											<label>
												<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="1">
												<B class="gray2">Не важно</B>
											</label>
										</div>
										<div class="flex-string wp33" data-field="priority">
											<label>
												<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="0">
												<B class="blue">Обычно</B>
											</label>
										</div>
										<div class="flex-string wp33" data-field="priority">
											<label>
												<input class="directt" name="priority[]" type="checkbox" id="priority[]" value="2">
												<B class="red">Важно</B>
											</label>
										</div>

									</div>

									<div class="pt10 w300 flex-container text-center fs-09 infodiv dotted">

										<div class="flex-string wp33">
											<label>
												<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="1">
												<B class="gray2">Не срочно</B>
											</label>
										</div>
										<div class="flex-string wp33">
											<label>
												<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="0">
												<B class="blue">Обычно</B>
											</label>
										</div>
										<div class="flex-string wp33">
											<label>
												<input class="directt" name="speed[]" type="checkbox" id="speed[]" value="2">
												<B class="red">Срочно</B>
											</label>
										</div>

									</div>

								</div>

								<div class="flex-string wp20 border-box text-right inline">

									<a href="javascript:void(0)" onclick="doFilter()" class="button fs-09 m0">Применить фильтр</a>

								</div>

							</div>

						</form>

					</div>

				</div>
				<div id="dtasklist" class="datas"></div>

			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.todo();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--список дел сотрудников-->
	<div class="tab todoUsers nano relative  hidden" data-tab="todoUsers">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite modules" id="todoUsers">

				<div id="dtasklistu" class="datas"></div>

			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.todoUsers();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--Клиенты-->
	<div class="tab clients nano relative hidden" data-tab="clients">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite" id="clients">

				<div id="clientlist"></div>
				<div class="space-10"></div>

			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.clients();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--Контакты-->
	<div class="tab contacts nano relative  hidden" data-tab="contacts">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite" id="contacts">

				<div id="contactlist"></div>
				<div class="space-10"></div>

			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.contacts()" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--Сделки-->
	<div class="tab deals nano relative  hidden" data-tab="deals">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite" id="deals">

				<div id="deallist" class="datas"></div>
				<div class="space-10"></div>

			</div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.deals()" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i></div>

	</div>

	<!--Здоровье сделок-->
	<div class="tab health nano relative  hidden" data-tab="health">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite" id="health"></div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.health();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i>
		</div>

	</div>

	<!--Каталог-склад-->
	<div class="tab catalog nano relative  hidden" data-tab="catalog">

		<div class="nano-content">
			<div class="ui-layout-content bgwhite" id="catalog"></div>
		</div>

		<div class="fixReloadButton" onclick="$desktop.catalog();" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
			<i class="icon-arrows-cw"></i>
		</div>

	</div>

</DIV>
<DIV class="ui-layout-east"></DIV>
<DIV class="ui-layout-south"></DIV>
<DIV class="ui-rightpane"></DIV>

<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<div class="pagerefresh refresh--icon" onclick="razdel($space);" title="<?= $lang[ 'all' ][ 'Refresh' ] ?>">
	<i class="icon-arrows-cw"></i>
</div>

<script>

	var $display = 'desktop';
	var $mon = calendarMonth.month;
	var $year = calendarMonth.year;

	if (isMobile) {

		includeJS('/assets/js/swiper/swiper.min.js');
		includeCSS('/assets/js/swiper/swiper.min.css');

	}

	$(function () {

		$('.fixReloadButton').addClass('hidden');

		razdel();

		$desktop.calendar();

	});

	window.onhashchange = function () {
		var hash = window.location.hash.substring(1);
		razdel(hash);
	};

	function razdel(hesh) {

		if (hesh === null) hesh = '<?=$firstTab?>';

		$('.razdel a').removeClass('active');

		if (!hesh) hesh = window.location.hash.replace('#', '');
		if (!hesh) hesh = '<?=$firstTab?>';

		$space = hesh;

		$('.ui-layout-center').find(".nano").nanoScroller({destroy: true});

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="'+hesh+'"]').addClass('active');

		$('.tab').addClass('hidden');
		$('.tab[data-tab="'+hesh+'"]').removeClass('hidden');

		switch (hesh) {

			case 'pipeline':

				//if ($('#pipeline').html() === '')
					$desktop.pipeline();

				break;
			case 'vigets':

				//if ($('#vigets').html() === '')
					$desktop.vigets();

				break;
			case 'bigcal':

				$desktop.bigcal();

				break;
			case 'weekcal':

				$desktop.weekcal();

				break;
			case 'todo':

				//if ($('#dtasklist').html() === '')
					$desktop.todo();

				break;
			case 'todoUsers':

				//if ($('#dtasklistu').html() === '')
					$desktop.todoUsers();

				break;
			case 'health':

				//if ($('#health').html() === '')
					$desktop.health();

				break;
			case 'catalog':

				//if ($('#catalog').html() === '')
					$desktop.catalog();

				break;
			case 'clients':

				//if ($('#clientlist').html() === '')
					$desktop.clients();

				break;
			case 'contacts':

				//if ($('#contactlist').html() === '')
					$desktop.contacts();

				break;
			case 'deals':

				//if ($('#deallist').html() === '')
					$desktop.deals();

				break;

		}

		constructSpace();

		$('#page').val('1');

	}

	function constructSpace() {

		var hw;

		if ($space === 'pipeline') {

			var pw = $('#salesteps').width();
			var pr = $('#salesteps').offset();

			//$('#pipelineSteps').css({'width': pw + "px", 'left': pr.left - 5 + "px"}).hide();

		}
		if ($space === 'catalog') {

			hw = $('.catalog').find('.ui-layout-content').width();

			$('.tableHeader[data-id="catalog"]').css({
				"width": hw + "px",
				'left': "0px",
				'top': "0px",
				'position': "absolute"
			});

		}

		if ($space === 'weekcal') {

			setTimeout(function () {

				var wcoffset = $('#weekCal').find('#today').offset();
				var wctop = wcoffset.top - 50;

				$('.weekcal').nanoScroller({scrollTop: wctop});

			}, 100);

		}
		if ($space === 'bigcal') {

			setTimeout(function () {

				var wcoffset = $('#bigCal').find('.today').offset();
				var wctop = wcoffset.top - 50;

				$('.bigcal').nanoScroller({scrollTop: wctop});

			}, 100);

		}

		if (!isMobile)
			$('.nano').nanoScroller();

	}

	$(window).on('resize', function () {

		constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		sstop = $('#salesteps').offset().top;

		constructSpace();

		$('.ui-layout-center').trigger('onPositionChanged');

	});

	$('.ui-layout-center').onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

		}, 200);

	});

	$('.lpToggler').on('click', function () {

		$('.ui-layout-west').toggleClass('open');
		$('.ui-layout-center').toggleClass('open');
		$(this).toggleClass('open');

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

		}, 150);

	});

	const $desktop = {
		"vigets": function () {

			$('#vigets').load('/content/desktop/dt.wigets.php?hidedeals=' + hideDeals).append('<img src="/assets/images/loading.svg">');
			$('.viget-mini').addClass('flex-string');

		},
		"clients": function(){

			const $elm = $('#clientlist');

			$.Mustache.load('/content/tpl/dt.clients.mustache');

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			var cdheight = $elm.closest('.nano').height();
			var cdwidth = $('#last').width();

			$('.contentloader').height(cdheight).width(cdwidth);

			$.getJSON('/content/desktop/clients.php', function(viewData) {

				$elm.empty().mustache('clientsTpl', viewData).animate({scrollTop: 0}, 200);

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function() {

					$(".nano").nanoScroller();
					$elm.closest('.nano').find('.contentloader').remove();

					if (isMobile)
						$('.clients').find('table').rtResponsiveTables({'id':'table-clients'});

				});

		},
		"contacts": function () {

			const $elm = $('#contactlist');

			$.Mustache.load('/content/tpl/dt.persons.mustache');

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			var cdheight = $elm.closest('.nano').height();
			var cdwidth = $('#last').width();

			$('.contentloader').height(cdheight).width(cdwidth);

			$.getJSON('/content/desktop/persons.php', function(viewData) {

				$elm.empty().mustache('personsTpl', viewData).animate({scrollTop: 0}, 200);

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function() {

					if(!isMobile)
						$(".nano").nanoScroller();

					$elm.closest('.nano').find('.contentloader').remove();

					if (isMobile)
						$('.contacts').find('table').rtResponsiveTables({'id':'table-contacts'});

				});

		},
		"deals": function () {

			const $elm = $('#deallist');

			$.Mustache.load('/content/tpl/dt.deals.mustache');

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			var cdheight = $elm.closest('.nano').height();
			var cdwidth = $('#last').width();

			$('.contentloader').height(cdheight).width(cdwidth);

			$.getJSON('/content/desktop/deals.php', function(viewData) {

				$elm.empty().mustache('dealsTpl', viewData).animate({scrollTop: 0}, 200);

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function() {

					$(".nano").nanoScroller();

					var dservices = localStorage.getItem("dservices");

					if(dservices === 'show')
						$('#dservices').removeClass('hidden');

					else if(dservices === '')
						$('#dservices').addClass('hidden');

					var dold = localStorage.getItem("dold");

					if(dold === 'show')
						$('#dold').removeClass('hidden');

					else if(dold === '')
						$('#dold').addClass('hidden');

					var dfuture = localStorage.getItem("dfuture");

					if(dfuture === 'show')
						$('#dfuture').removeClass('hidden');

					else if(dfuture === '')
						$('#dfuture').addClass('hidden');

					$elm.closest('.nano').find('.contentloader').remove();

					if (isMobile)
						$elm.find('table').rtResponsiveTables({id:'table-deallist'});

				});

		},
		"health": function () {

			const $elm = $('#health');

			$.Mustache.load('/content/tpl/tpl.health.mustache');

			$elm.append('<img src="/assets/images/loading.svg">');

			$.getJSON('/content/desktop/dt.health.php', function (viewData) {

				$elm.empty().mustache('healthTpl', viewData).animate({scrollTop: 0}, 200);

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function () {

					$('#hl-user\\[\\]').on('click',function(){

						healthFilter();

					});

					if (!isMobile)
						$('.nano').nanoScroller();

					if (isMobile)
						$('#health').find('table').rtResponsiveTables({id: 'table-health'});

				});

		},
		"pipeline": function () {

			$('#pipeline').load('/content/desktop/dt.pipeline.php').append('<img src="/assets/images/loading.svg">');

		},
		"todo": function () {

			var $elm = $('#dtasklist');
			var cdheight = $elm.closest('.nano').height();
			var cdwidth = $elm.closest('.nano').width();

			var told = localStorage.getItem("told");
			var ttoday = localStorage.getItem("ttoday");
			var tfuture = localStorage.getItem("tfuture");

			$.Mustache.load('/content/tpl/dt.tasklist.mustache');

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			$('.contentloader').height(cdheight).width(cdwidth);

			$.getJSON('/content/desktop/tasklist.php?' + $('#filterform').serialize(), function (viewData) {

				viewData.language = $language;

				$elm.empty().mustache('taskTpl', viewData).animate({scrollTop: 0}, 200);

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function () {

					if (!isMobile)
						$(".nano").nanoScroller();


					if (told === 'show')
						$('#told').removeClass('hidden');
					else if (told === '')
						$('#told').addClass('hidden');


					if (ttoday === 'show')
						$('#ttoday').removeClass('hidden');
					else if (ttoday === '')
						$('#ttoday').addClass('hidden');


					if (tfuture === 'show')
						$('#tfuture').removeClass('hidden');
					else if (tfuture === '')
						$('#tfuture').addClass('hidden');

					$('.contentloader').remove();

					$('tr[data-type="task"] td:first-child')
						.on('mousedown', function () {

							$(this).closest('tr').toggleClass('yellowbg-sub');

							$('tr[data-type="task"] td:first-child').on('mouseenter', function () {

								var $elm = $('input[type=checkbox]', this);

								$(this).closest('tr').toggleClass('yellowbg-sub');

								if ($elm.prop('checked'))
									$elm.prop('checked', false);
								else
									$elm.prop('checked', true);

							});

						})
						.on('mouseup', function () {

							$('tr[data-type="task"] td:first-child').off('mouseenter');

							if ($('tr[data-type="task"].yellowbg-sub').length > 0)
								$('.multi--buttons').removeClass('hidden');
							else
								$('.multi--buttons').addClass('hidden');

							$('.task--count').html('( <b>' + $('tr[data-type="task"].yellowbg-sub').length + '</b> )');

						});

					if (isMobile) {
						$elm.find('table').rtResponsiveTables({id: 'table-todo'});
					}

				});

			//$('#todo').empty().load('content/lists/dt.tasklist.php').append('<img src="/assets/images/loading.svg">');

		},
		"todoUsers": function () {

			$.Mustache.load('/content/tpl/dt.tasklist.mustache');

			var $elm = $('#dtasklistu');
			var cdheight = $elm.closest('.nano').height();
			var cdwidth = $elm.closest('.nano').width();

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			$('.contentloader').height(cdheight).width(cdwidth);

			$.getJSON('/content/desktop/tasklist.php?myotdel=yes', function(viewData) {

				viewData.language = $language;

				$elm.empty().mustache('taskTpl', viewData).animate({scrollTop: 0}, 200);

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function() {

					if(!isMobile)
						$(".nano").nanoScroller();

					$('.contentloader').remove();

					if (isMobile)
						$('.todoUsers').find('table').rtResponsiveTables({id:'table-todoUsers'});

				});

			//$('#todoUsers').empty().load('content/lists/dt.tasklist.users.php').append('<img src="/assets/images/loading.svg">');

		},
		"bigcal": function () {

			var $elm = $('#bigCal');

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			$.Mustache.load('/content/tpl/dt.bigcalendar.mustache');

			$.ajax({
				type: "POST",
				url: "/content/desktop/bigcalendar.php?m="+$mon+"&y="+$year,
				//data: str,
				dataType: 'json',
				success: function (viewData, status, xhr) {

					viewData.language = $language;

					$elm.empty().mustache('bigcalendarTpl', viewData);

					$('.todocal').each(function () {

						$(this).draggable({
							containment: '.bigcalendar',
							cursor: 'move',
							helper: 'clone',
							revert: false,
							zIndex: 100,
							scroll:false
						});

					});

					$('.adtaskb').each(function () {

						$(this).droppable({
							tolerance: "pointer",
							over: function (event, ui) {//если фигура над клеткой- выделяем её границей
								$(this).addClass('greenbg-sub');
							},
							out: function (event, ui) {//если фигура ушла- снимаем границу
								$(this).removeClass('greenbg-sub');
							},
							//если бросили фигуру в клетку
							drop: function (event, ui) {

								$(this).removeClass('greenbg-sub');//убираем выделение

								var olddate = $(ui.draggable).data('old');
								var newdate = $(this).data('datum');
								var tid = $(ui.draggable).data('tid');

								var date1 = new Date();
								var date2 = new Date(newdate);
								var timeDiff = Math.ceil(date2.getTime() - date1.getTime());
								var diffDays = Math.ceil(timeDiff / (1000 * 3600 * 24));

								//console.log( ui.helper[0].outerHTML );
								//console.log( $(this) );

								if (diffDays >= 0) {

									var url = '/content/core/core.tasks.php?tid=' + tid + '&action=izmdatum&olddatum=' + olddate + '&newdatum=' + newdate;

									$('#message').empty().fadeTo(1, 1).append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
									$.post(url, function (data) {

										$('#message').fadeTo(1, 1).css('display', 'block').html(data);
										setTimeout(function () {
											$('#message').fadeTo(100, 0);
										}, 2000);

										$desktop.bigcal();

										//$(this).find('.adtaskb').append( ui.helper[0].outerHTML );

									}).done(function () {

										setTimeout(function () {

											var wcoffset = $('#bigCal').find('.today').offset();
											var wctop = wcoffset.top - 50;

											$('.bigcal').nanoScroller({scrollTop: wctop});

										}, 100);

									});

								}
								else console.log(diffDays);
							},
							accept: '.todocal'
						});

					});

				}
			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				})
				.done(function () {

					var boffset = $elm.find('.today').offset();
					var btop = boffset.top - 50;

					var hi = $elm.find('.sticked--top').actual('height');
					var bH = $elm.actual('height');

					var $nw = (bH > 600) ? 5 : 4;

					var tH = (bH - hi - 5) / $nw + 30;

					$elm.find('.day').css({"height": tH + "px"});
					$elm.find('.dayblock-dv').css({"height": tH + "px"});

					$('.bigcal').nanoScroller({scrollTop: btop});

				});

			//$('#bigcal').empty().load('content/lists/dt.bigcalendar.php').append('<img src="/assets/images/loading.svg">');

		},
		"weekcal": function () {

			const $elm = $('#weekCal');

			$elm.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

			var cdheight = $elm.closest('.nano').actual('height');
			var cdwidth = $('.ui-layout-center').actual('width');

			$('.contentloader').height(cdheight).width(cdwidth);

			$.Mustache.load('/content/tpl/dt.weekcalendar.mustache');

			$.ajax({
				type: "POST",
				url: "/content/desktop/weekcalendar.php",
				dataType: 'json',
				success: function (viewData) {

					viewData.language = $language;

					$elm.empty().mustache('weekcalendarTpl', viewData);

					$elm.closest('.nano').find('.contentloader').remove();

					var wcoffset = $('#weekCal').find('.today').offset();
					var wctop = wcoffset.top - 50;

					$('.weekcal').nanoScroller({scrollTop: wctop});

					$('.hour--event.wtodocal').each(function () {
						$(this).draggable({
							containment: '.weekcalendar',
							cursor: 'move',
							helper: 'clone',
							revert: false,
							zIndex: 100
						});
					});

					$('.adtask').each(function () {

						$(this).droppable({
							tolerance: "pointer",
							over: function (event, ui) {//если фигура над клеткой- выделяем её границей
								$(this).addClass('greenbg-sub');
							},
							out: function (event, ui) {//если фигура ушла- снимаем границу
								$(this).removeClass('greenbg-sub');
							},
							drop: function (event, ui) {//если бросили фигуру в клетку
								$(this).removeClass('greenbg-sub');//убираем выделение

								var olddatum = $(ui.draggable).data('old');
								var oldhour = $(ui.draggable).closest('.hour--block').data('hours');
								var newdatum = $(this).data('datum');
								var newhour = $(this).data('hours');
								var tid = $(ui.draggable).data('tid');

								var date1 = new Date();
								var date2 = new Date(newdatum);
								var timeDiff = Math.ceil(date2.getTime() - date1.getTime());
								var diffDays = Math.ceil(timeDiff / (1000 * 3600));

								//console.log(date1);
								//console.log(date2);

								if (diffDays >= 0) {

									var url = '/content/core/core.tasks.php?tid=' + tid + '&action=izmdatum&olddatum=' + olddatum + '&newdatum=' + newdatum + '&oldhour=' + oldhour + '&newhour=' + newhour;

									//console.log(url);

									$('#message').empty().fadeTo(1, 1).append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');
									$.post(url, function (data) {

										$('#message').fadeTo(1, 1).css('display', 'block').html(data);
										setTimeout(function () {
											$('#message').fadeTo(1000, 0);
										}, 20000);

										$desktop.weekcal();

									});

								}

							},
							accept: '.adtask'
						});

					});

				}
			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				});

			$(document).off('click', '.actions');
			$(document).on('click', '.actions', function () {

				var datum = $(this).closest('.hour--block').data('datum');
				doLoad('/content/forms/form.task.php?action=add&date=' + datum);

			});

			//$('#weekcal').empty().load('content/lists/dt.weekcalendar.php').append('<img src="/assets/images/loading.svg">');

		},
		"catalog": function () {

			$('#catalog').load('/modules/modcatalog/dt.tabs.php').append('<img src=/assets/images/loading.svg>');

		},
		"calendar": function (direct) {

			var m = $mon;
			var y = $year;
			var str = '';

			if (direct === 'back') {

				if (m === 1) {

					m = 12;
					y--;

				}
				else m--;

				$mon = m;
				$year = y;

			}
			else if (direct === 'next') {

				if (m === 12) {
					m = 1;
					y++;
				}
				else m++;

				$mon = m;
				$year = y;

			}

			$("#calendar").append('<div id="loader" class="pull-right"><img src="/assets/images/loading.svg" width="12"></div>');

			$.ajax({
				type: "GET",
				url: "/content/lists/lp.calendar.php?face=desktop&y=" + y + "&m=" + m,
				data: str,
				success: function (viewData) {

					$("#calendar").html(viewData);

					$.get({
						type: "GET",
						url: "/content/lists/lp.tasksweek.php?y=" + y + "&m=" + m,
						data: str,
						success: function (viewData) {

							$("#task").html(viewData);

							$('#lmenu').find('#calendar').closest('.contaner').find('.togglerbox').trigger('click');

							if (!isMobile)
								$("#calendar").find('.nano').nanoScroller();

							if (!isMobile)
								$(".popbody").find('.nano').nanoScroller();

							setCookie('tasker', Date.now());

						}
					})
						.done(function () {

							if ($display === 'desktop') {

								desktopTaskHeight();

							}
							else {

								var element = $(".popbody").closest('.popmenu');
								var hPop = element.actual('height');
								var hBody = hPop - element.find('.pophead').actual('height') - element.find('.popblock').actual('height') - 5;

								element.find('#tasklist').css({"height": hBody + "px"});

							}

						});

				}
			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				});

		}
	}

	/**
	 * функции для списка дел
	 */
	function doFilter() {

		var url = '/content/lists/dt.tasklist.php?action=setparam&';
		var str = $('#filterform').serialize();

		$.ajax({
			type: "GET",
			url: url,
			data: str,
			success: function () {

				$desktop.todo();

			}
		})
			.fail(function(status) {

				console.log(status)

				Swal.fire({
					title: "Ошибка: Ошибка загрузки данных!",
					type: "error"
				});

			});

		deleteCookie('onlymy');

	}

	function multiTaskDel() {

		Swal.fire({
				title: 'Вы уверены?',
				text: "Напоминания будут удалены безвозвратно!",
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085D6',
				cancelButtonColor: '#D33',
				confirmButtonText: 'Да, выполнить',
				cancelButtonText: 'Отменить',
				confirmButtonClass: 'greenbtn',
				cancelButtonClass: 'redbtn'
			},
			function () {

				multiTaskDelDo();

			}
		).then((result) => {

			if (result.value) {

				multiTaskDelDo();

			}

		});

		function multiTaskDelDo() {

			var strs = $("#dtasklist tr.yellowbg-sub input:checkbox").map(function () {
				return $(this).val();
			}).get();

			console.log(strs.join(","));

			$.get('/content/core/core.tasks.php?action=mass.delete&count=' + strs.length + '&ids=' + strs.join(","), function (data) {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				if ($('#tar').is('input') && typeof configpage === 'function')
					configpage();

				if ($('#isCard').val() === 'yes')
					cardload();
				else if (typeof configpage === 'function')
					configpage();

				if ($display === 'desktop')
					changeMounth();

				if ($('#weekCal').is('div'))
					getWeekCalendar();

			})
				.fail(function(status) {

					console.log(status)

					Swal.fire({
						title: "Ошибка: Ошибка загрузки данных!",
						type: "error"
					});

				});

			multiTaskClearCheck();

		}

	}

	function multiTaskMove() {

		var strs = $("#dtasklist tr.yellowbg-sub input:checkbox").map(function () {
			return $(this).val();
		}).get();

		console.log(strs.join(","));

		doLoad('/content/forms/form.task.php?action=mass&count=' + strs.length + '&ids=' + strs.join(","));

		multiTaskClearCheck();

	}

	function multiTaskClearCheck() {

		$('tr[data-type="task"]').removeClass('yellowbg-sub');
		$("input[type=checkbox]:checked").prop('checked', false);
		$('.multi--buttons').addClass('hidden');

	}

	/**
	 * Функция для Здоровья сделок
	 */
	function healthFilter(){

		var husers = [];

		$('#hl-user\\[\\]:checked').each(function(){

			husers.push($(this).val());

		});

		$('tr.filtered').each(function(){

			if(!in_array($(this).data('user'), husers) && husers.length > 0)
				$(this).addClass('hidden');

			else
				$(this).removeClass('hidden');

		});

	}

	$(".showintro").on('click', function () {

		var intro = introJs();

		$('#menuclients').addClass('visible');

		intro.setOptions({
			'nextLabel': 'Дальше',
			'prevLabel': 'Вернуть',
			'skipLabel': 'Пропустить',
			'doneLabel': 'Я понял',
			'showStepNumbers': false
		});
		intro.start().goToStep(1)
			.onbeforechange(function (targetElement) {

				switch ($(targetElement).attr("data-step")) {
					case "1":
						$(targetElement).show();
						break;
					case "2":
						$('#menuclients').removeClass('visible');
						break;
					case "3":
						$("#menuavatar").trigger('click');
						$(targetElement).show();
						$('#menuavatar').find('.nano').nanoScroller();
						break;
					case "4":
						popsearchhandler();
						$(targetElement).show();
						break;
					case "41":
						$('li[data-id="search"]').find('.popmenu').removeClass('open');
						$(targetElement).show();
						break;
					case "42":
						$('li[data-id="search"]').trigger('click');
						$(targetElement).show();
						break;
					case "50":
						$('li[data-id="search"]').find('.popmenu').removeClass('open');
						$(targetElement).show();
						break;
					case "60":
						$(targetElement).show();
						break;
					case "70":
						$(targetElement).show();
						break;
					case "8":
						$(targetElement).show();
						break;
					case "9":
						$(targetElement).show();
						break;
				}
			});
		intro.onexit(function () {
			$("#subpan3").hide();
			$('li[data-id="search"]').find('.popmenu').removeClass('open');
		});
	});

</script>
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</BODY>
</HTML>