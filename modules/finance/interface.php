<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Финансы';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

if (!$userRights['budjet']) {
	print '
	<div class="warning" style="width:600px">
		<span><i class="icon-attention red icon-5x pull-left"></i></span>
		<b class="red uppercase">Внимание:</b><br><br>К сожалению Вы не можете просматривать данную информацию<br>У Вас отсутствует разрешение.<br>
	</div>
	<script type="text/javascript">
		$(".warning").center();
	</script>
	';
	exit;
}

$year = date('Y');
$m    = date('m');

$y1 = $year - 1;
$y2 = $year + 1;

?>

<DIV class="" id="rmenu">

	<div class="tabs" data-step="5" data-intro="<h1>Разделы</h1>Здесь можно выбрать нужный подраздел блока Финансы" data-position="right">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" class="" onclick="configpage()" title="Обновить"><i class="icon-arrows-cw"></i></a>

		<?php
		if($userSettings['dostup']['budjet']['chart'] == 'yes'){
		?>
		<A href="#budjet" class="razdel pl5 pr5 active" data-id="budjet" title="Бюджет факт" data-step="6" data-intro="<h1>Раздел Бюджет</h1>Здесь можно увидеть сводную информацию по финансам. Напоминает БДДР" data-position="right"><i class="icon-chart-bar"></i></A>
		<?php } ?>

		<?php
		if($userSettings['dostup']['budjet']['journal'] == 'yes'){
		?>
		<A href="#journal" class="razdel pl5 pr5" data-id="journal" title="Журнал расходов" data-step="10" data-intro="<h1>Раздел Журнал расходов</h1>Отображает журнал доходов и расходов. Здесь производятся действия с расходами" data-position="right"><i class="icon-list-nested"></i></A>
		<?php } ?>

		<?php
		if($userSettings['dostup']['budjet']['statement'] == 'yes'){
		?>
		<A href="#statement" class="razdel pl5 pr5 visible-min-h700" data-id="statement" title="Банковские выписки" data-step="14" data-intro="<h1>Раздел Банковская выписка</h1>Отображает журнал загруженных выписок из банка." data-position="right"><i class="icon-article-alt"></i></A>
		<?php } ?>

		<?php
		if($userSettings['dostup']['budjet']['payment'] == 'yes'){
		?>
		<A href="#invoices" class="razdel pl5 pr5" data-id="invoices" title="Журнал оплат"><i class="icon-rouble"></i></A>
		<?php } ?>

		<?php
		if($userSettings['dostup']['budjet']['agents'] == 'yes'){
		?>
		<A href="#agents" class="razdel pl5 pr5 visible-min-h700" data-id="agents" title="Поставщики / Партнеры" data-step="13" data-intro="<h1>Раздел Расчеты с поставщиками</h1>Отображает расходы на Поставщиков, Партнеров" data-position="right"><i class="icon-users-1"><i class="icon-rouble sup green fs-05"></i></i></A>
		<?php } ?>

		<?php
		if($userSettings['dostup']['budjet']['action'] == 'yes'){
		?>
		<A href="javascript:void(0)" onclick="editBudjet('','cat.edit');" class="razdel pl5 pr5 visible-min-h700" title="Добавить раздел/статью"><i class="icon-plus"><i class="sup icon-folder-1 red fs-05"></i></i></A>
		<A href="javascript:void(0)" onclick="editBudjet('','move')" class="razdel pl5 pr5 visible-min-h700" title="Переместить м/у счетами"><i class="icon-shuffle-1"><i class="sup icon-flag blue fs-05"></i></i></A>
		<?php } ?>

		<div title="<?= $lang['face']['More'] ?>" class="leftpop hidden-min-h700">

			<i class="icon-dot-3"></i>

			<ul class="menu" style="width: 240px !important;">

				<?php
				if($userSettings['dostup']['budjet']['statement'] == 'yes'){
				?>
				<li>
					<A href="#statement" class="razdel nowrap" data-id="statement" title="Банковские выписки"><i class="icon-article-alt"><i class="sup icon-town-hall red fs-05"></i></i> Банковские выписки</A>
				</li>
				<?php
				}
				if($userSettings['dostup']['budjet']['agents'] == 'yes'){
				?>
				<li>
					<A href="#provider" class="razdel nowrap" data-id="sklad" title="Поставщики / Партнеры"><i class="icon-users-1"><i class="icon-rouble sup green fs-05"></i></i>Поставщики / Партнеры</A>
				</li>
				<?php
				}
				if($userSettings['dostup']['budjet']['action'] == 'yes'){
				?>
				<li>
					<A href="javascript:void(0)" onclick="editBudjet('','cat.edit');" class="razdel nowrap" title="Добавить раздел/статью"><i class="icon-plus"><i class="sup icon-folder-1 red fs-05"></i></i>Добавить раздел/статью</A>
				</li>
				<li>
					<A href="javascript:void(0)" onclick="editBudjet('','move')" class="razdel nowrap" title="Переместить м/у счетами"><i class="icon-shuffle-1"><i class="sup icon-flag blue fs-05"></i></i>Переместить м/у счетами</A>
				</li>
				<?php } ?>

			</ul>

		</div>

		<?php
		require_once $rootpath."/content/leftnav/leftpop.php";
		flush();
		?>

	</div>

	<?php
	require_once $rootpath."/content/leftnav/counters.php";
	flush();
	?>

</DIV>

<DIV class="ui-layout-north mainbg">

	<?php
	require_once $rootpath."/inc/menu.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-west disable--select compact">

	<?php
	require_once $rootpath."/modules/finance/navi.budjet.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden">

	<DIV class="mainbg listHead hidden-iphone">

		<div class="pt5 pb5 flex-container">
			<div class="flex-column wp30 fs-11 pl5 pt5 border-box">
				<b><?= $title ?></b>&nbsp;/&nbsp;<span id="tips"></span>
			</div>
			<div class="flex-column wp70 text-right border-box">

				<DIV class="inline pull-aright pt7" data-step="14" data-intro="<h1>Документация на модуль</h1>Вы можете ознакомиться с модулем более подробно в Документации:<br><a href='https://salesman.pro/docs/78' target='_blank'>https://salesman.pro/docs/78</a>" data-position="left">

					<a href="https://salesman.pro/docs/78" title="Справка" target="_blank"><i class="icon-info-circled-1 blue"></i></a>&nbsp;&nbsp;

				</DIV>


				<DIV class="inline pull-aright menu-budjet hidden" data-step="9" data-intro="<h1>Действия</h1>Доступные действия в разделе" data-position="bottom">

					<a href="javascript:void(0)" onclick="changeyear('prev');"><i class="icon-angle-double-left"></i><span class="prev"><?= $y1 ?></span></a>&nbsp;|&nbsp;
					<span class="red Bold miditxt current"><?= $year ?></span>&nbsp;|&nbsp;
					<a href="javascript:void(0)" onclick="changeyear('next');"><span class="next"><?= $y2 ?></span><i class="icon-angle-double-right"></i></a>&nbsp;&nbsp;

					<?php
					if($userSettings['dostup']['budjet']['action'] == 'yes'){
					?>
					<a href="javascript:void(0)" onclick="editBudjet('','export.budjet');" title="Экспорт в Excel"><i class="icon-doc-text-inv blue"></i>Экспорт</a>&nbsp;&nbsp;
					<a href="javascript:void(0)" onclick="editBudjet('','import.statement.s1');" title="Загрузить выписку банка"><i class="icon-article-alt blue"></i>Выписка</a>&nbsp;&nbsp;
					<?php } ?>

					<a href="javascript:void(0)" title="Обновить" onclick="configpage()"><i class="icon-arrows-cw blue"></i> Обновить</a>&nbsp;&nbsp;

				</DIV>

				<DIV class="inline pull-aright menu-invoices hidden">

					<a href="javascript:void(0)" onclick="changeyear('prev');"><i class="icon-angle-double-left"></i><span class="prev"><?= $y1 ?></span></a>&nbsp;|&nbsp;
					<span class="red Bold miditxt current"><?= $year ?></span>&nbsp;|&nbsp;
					<a href="javascript:void(0)" onclick="changeyear('next');"><span class="next"><?= $y2 ?></span><i class="icon-angle-double-right"></i></a>&nbsp;&nbsp;

					<?php
					if($userSettings['dostup']['budjet']['action'] == 'yes'){
					?>
					<a href="javascript:void(0)" onclick="editBudjet('','export.invoices');" title="Экспорт в Excel"><i class="icon-doc-text-inv blue"></i>Экспорт</a>&nbsp;&nbsp;
					<?php } ?>
					<a href="javascript:void(0)" title="Обновить" onclick="configpage()"><i class="icon-arrows-cw blue"></i> Обновить</a>&nbsp;&nbsp;

				</DIV>

				<DIV class="inline pull-aright menu-period hidden" data-step="11" data-intro="<h1>Выбор периода</h1>Выбор периода для отображения журнала" data-position="bottom">

					<div class="inline pt5"><b class="blue">Период:</b>
						<select name="mon" id="mon" onchange="mounthFilter()" class="clean fs-09 w100 mb3">
							<option value="">&nbsp;&nbsp;--весь год--&nbsp;&nbsp;</option>
							<?php
							for ($i = 1; $i <= 12; $i++) {
								$s = ( $i == date('m') ) ? "selected" : "";
								print '<option value="'.$i.'" '.$s.'>'.ru_mon($i).'</option>';
							}
							?>
						</select>
					</div>

				</DIV>

			</div>
		</div>

	</DIV>

	<form name="cform" id="cform">
		<div class="nano relativ" id="budjet">

			<div class="nano-content">
				<div class="ui-layout-content" id="contentdiv"></div>
			</div>

			<div class="pagecontainer">
				<div class="page pbottom mainbg" id="pagediv"></div>
			</div>

		</div>
	</form>

</DIV>
<DIV class="ui-layout-east"></DIV>
<DIV class="ui-layout-south"></DIV>

<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

	var subcat = 0;

	var hash = window.location.hash.substring(1);
	if (hash === '') hash = 'budjet';

	var $Year = new Date().getFullYear();
	$('#year').val($Year);

	var tar = $('#tar').val();

	if (isMobile || $(window).width() < 767) {

		$('.lpToggler').toggleClass('open');

		var m = $('#mon').removeClass('clean').addClass('wp95').detach();
		$('#dmonth').append(m);

	}

	$(function () {

		//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width:100%"></div>');
		$("#stat").load('/modules/finance/stat.php').append('<div class="contentloader"><img src="/assets/images/loading.gif"></div>');

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

		$(window).trigger('onhashchange');

		$.Mustache.load('/modules/finance/tpl.budjet.mustache');

		razdel(hash);

		constructSpace();

		$('.ftabs').each(function () {

			$(this).find('li').removeClass('active');
			$(this).find('li:first-child').addClass('active');

			$(this).find('.cbox').addClass('hidden');
			$(this).find('.cbox:first-child').removeClass('hidden');

		});

		$(".nano").nanoScroller();

		changeMounth();

	});

	<?php
	if ($_REQUEST['id'] > 0) {
		print "doLoad('/modules/finance/form.budjet.php?action=view&id=".$_REQUEST['id']."')";
	}
	?>

	window.onhashchange = function () {

		hash = window.location.hash.substring(1);

		razdel(hash);

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

	};

	$('#ytabs li').on('click', function () {

		var link = $(this).data('link');
		var id = $(this).closest('.ftabs').data('id');

		$(this).closest('ul').find('li').removeClass('active');
		$(this).addClass('active');

		$('#' + id + ' .cbox').addClass('hidden');
		$('#' + id + ' .' + link).removeClass('hidden');

	});
	$('.selectall').on('click', function () {

		var element = $(this).data('tip');
		var toggler = $(this).closest('.' + element);
		var filter = $(this).find('#ifilter');

		if (element === 'partner') {
			$('.contractor').find('input:checkbox').prop('checked', false);
			$('.contractor').find('#ifilter').addClass('icon-eye-off').removeClass('icon-eye');
		}
		else {
			$('.partner').find('input:checkbox').prop('checked', false);
			$('.partner').find('#ifilter').addClass('icon-eye-off').removeClass('icon-eye');
		}

		if (filter.hasClass('icon-eye-off')) {

			filter.removeClass('icon-eye-off').addClass('icon-eye');
			toggler.find('input:checkbox').prop('checked', true);
			preconfigpage();

		}
		else {

			filter.addClass('icon-eye-off').removeClass('icon-eye');
			toggler.find('input:checkbox').prop('checked', false);
			preconfigpage();

		}
	});

	function configpage() {

		$('#contentdiv').parent(".nano").nanoScroller({scroll: 'top'});

		var str = $('#pageform').serialize();
		var url = '/modules/finance/list.budjet.php';
		//var tar = $('#tar').val();

		str += "&mon=" + $('#mon').val();

		$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $('#contentdiv').height();
		var cdwidth = $('#contentdiv').width();

		$('.contentloader').height(cdheight).width(cdwidth);

		/*------------*/

		if ($('.lpToggler').hasClass('open') && isMobile) $('.lpToggler').trigger('click');

		if (hash === 'budjet') {

			$.get(url, str, function (data) {

				$('#contentdiv').html(data);

			})
				.done(function () {

					$('.xcategory')
						.off('click')
						.on('click', function () {

							subcat = parseInt($(this).data('id'))

							if (subcat > 0) {

								$('.subcat').addClass('hidden')
								$('.subcat[data-block="' + subcat + '"]').removeClass('hidden')

							}
							else {

								$('.subcat').removeClass('hidden')

							}

						});

					if (subcat > 0) {
						$('.xcategory[data-id="' + subcat + '"]').trigger('click')
					}

					/*tooltips*/
					$('.tooltips').append("<span></span>");
					$('.tooltips:not([tooltip-position])').attr('tooltip-position', 'bottom');
					$(".tooltips").off('mouseenter');
					$(".tooltips").on('mouseenter', function () {
						$(this).find('span').empty().append($(this).attr('tooltip'));
					});
					/*tooltips*/

					if (isMobile)
						$('#contentdiv').find('table').rtResponsiveTables();

					if (!isMobile)
						$(".nano").nanoScroller();

					$(".popmenu-top").hide();

				});

		}
		else {

			$.getJSON(url, str, function (viewData) {

				$('#contentdiv').empty().mustache(hash + 'Tpl', viewData);

				var page = viewData.page;
				var pageall = viewData.pageall;

				var pg = 'Стр. ' + page + ' из ' + pageall;

				if (pageall > 1) {

					var prev = page - 1;
					var next = page + 1;

					if (page === 1)
						pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

					else if (page === pageall)
						pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

					else
						pg = '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="change_page(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

				}

				$('#pagediv').html(pg);

			})
				.done(function () {

					if (!in_array(hash, ['invoices', 'agents', 'journal']))
						mounthFilter();

					$(".nano").nanoScroller();

					if (isMobile)
						$('.ui-layout-center').find('table').rtResponsiveTables();

				});
		}

		if( $("#stat").is('div') ) {

			$("#stat").load('/modules/finance/stat.php');

		}

	}

	/*
	Вызываем при применении фильтров, чтобы начинать с 1 страницы
	 */
	function preconfigpage() {

		$('#page').val('1');
		configpage();

	}

	$(window).on('resize', function () {

		if (!isMobile) {

			constructSpace();
			$(".nano").nanoScroller();

		}

	});
	$(window).on('resizeend', 200, function () {

		if (!isMobile) {

			constructSpace();
			$(".nano").nanoScroller();

		}

	});

	function constructSpace() {

		var ht = ($('.listHead').is(':visible')) ? $('.listHead').actual('outerHeight') : 0;
		var hh = $('.ui-layout-center').actual('height') - ht;

		$('#budjet').css({"height": hh + 'px'});

		$('.nano').nanoScroller();

	}

	$('.lpToggler').on('click', function () {

		if (isMobile || $(window).width() < 767) {

			$('.ui-layout-west').toggleClass('open');
			$('.ui-layout-center').toggleClass('open');

		}
		else {

			$('.ui-layout-west').toggleClass('compact simple');
			$('.ui-layout-center').toggleClass('compact simple');

		}
		$(this).toggleClass('open');

	});

	function razdel(hesh) {

		$('.razdel a').removeClass('active');

		if (!hesh) hesh = window.location.hash.replace('#', '');
		if (!hesh) hesh = 'budjet';

		tar = hesh;

		switch (hesh) {
			case 'budjet':
				$('#tips').html('Бюджет факт');
				$('.menu-budjet').removeClass('hidden');
				$('.menu-invoices').addClass('hidden');
				$('.menu-provider').addClass('hidden');
				$('.contaner-budjet').addClass('hidden');
				$('.contaner-journal').addClass('hidden');
				$('.contaner-provider').addClass('hidden');
				$('.contaner-invoices').addClass('hidden');
				$('.contaner-rs').removeClass('hidden');
				$('.menu-period').addClass('hidden');
				$('.menu-period2').addClass('hidden');

				$('.pagecontainer').addClass('hidden');
				break;
			case 'journal':
				$('#tips').html('Журнал расходов');
				$('.menu-budjet').removeClass('hidden');
				$('.menu-invoices').addClass('hidden');
				$('.menu-provider').addClass('hidden');
				$('.contaner-budjet').removeClass('hidden');
				$('.contaner-journal').removeClass('hidden');
				$('.contaner-invoices').addClass('hidden');
				$('.contaner-provider').addClass('hidden');
				$('.contaner-budjet').removeClass('hidden');
				$('.contaner-rs').removeClass('hidden');
				$('.menu-period').removeClass('hidden');
				$('.menu-period2').addClass('hidden');

				$('.pagecontainer').addClass('hidden');
				break;
			case 'statement':
				$('#tips').html('Банковские выписки. Журнал');
				$('.menu-budjet').removeClass('hidden');
				$('.menu-invoices').addClass('hidden');
				$('.menu-provider').addClass('hidden');
				$('.contaner-budjet').removeClass('hidden');
				$('.contaner-journal').removeClass('hidden');
				$('.contaner-invoices').addClass('hidden');
				$('.contaner-provider').addClass('hidden');
				$('.contaner-budjet').removeClass('hidden');
				$('.contaner-rs').removeClass('hidden');
				$('.menu-period').removeClass('hidden');
				$('.menu-period2').addClass('hidden');

				$('.pagecontainer').addClass('hidden');
				break;
			case 'invoices':
				$('#tips').html('Журнал оплат');
				$('.menu-budjet').addClass('hidden');
				$('.menu-invoices').removeClass('hidden');
				$('.menu-provider').addClass('hidden');
				$('.contaner-budjet').addClass('hidden');
				$('.contaner-journal').addClass('hidden');
				$('.contaner-provider').addClass('hidden');
				$('.contaner-invoices').removeClass('hidden');
				$('.contaner-invoices').removeClass('hidden');
				$('.contaner-rs').removeClass('hidden');
				//$('.menu-period').addClass('hidden');
				//$('.menu-period2').removeClass('hidden');
				$('.menu-period').addClass('hidden');
				$('.menu-period2').addClass('hidden');

				$('.pagecontainer').removeClass('hidden');
				break;
			case 'agents':
				$('#tips').html('Расчеты с Поставщиками');
				$('.menu-budjet').removeClass('hidden');
				$('.menu-invoices').addClass('hidden');
				$('.menu-provider').removeClass('hidden');
				$('.contaner-budjet').addClass('hidden');
				$('.contaner-journal').addClass('hidden');
				$('.contaner-provider').removeClass('hidden');
				$('.contaner-invoices').addClass('hidden');
				$('.contaner-rs').addClass('hidden');
				$('.menu-period').removeClass('hidden');
				$('.menu-period2').addClass('hidden');

				$('.pagecontainer').addClass('hidden');
				break;
			case 'partner':
				$('#tips').html('Расчеты с Партнерами');
				$('.menu-budjet').addClass('hidden');
				$('.menu-invoices').addClass('hidden');
				$('.menu-provider').addClass('hidden');
				$('.contaner-budjet').addClass('hidden');
				$('.contaner-journal').addClass('hidden');
				$('.contaner-provider').addClass('hidden');
				$('.contaner-invoices').addClass('hidden');
				$('.contaner-rs').addClass('hidden');
				$('.menu-period').addClass('hidden');
				$('.menu-period2').addClass('hidden');

				$('.pagecontainer').addClass('hidden');
				break;
		}

		$('#tar').val(hesh);
		$('#page').val('1');
		$('#tuda').val('desc');

		$('.razdel .' + hesh).addClass('active');//.css('border','1px solid red');

		configpage();

	}

	function changeyear(dir) {

		var year = parseInt($('#year').val());

		if (dir === 'prev') year = year - 1;
		if (dir === 'next') year = year + 1;

		var prev = year - 1;
		var next = year + 1;

		$('#year').val(year);
		$('.prev').html(prev);
		$('.next').html(next);
		$('.current').html(year);
		$('#mon').val('');

		configpage();
	}

	function changesort(param) {

		var tt = $('#ord').val();

		$('#ord').val(param);

		if (param === tt) {

			if ($('#tuda').val() == '') $('#tuda').val('desc');
			else $('#tuda').val('');

		}

		configpage();

	}

	function change_page(num) {

		$('#page').val(num);
		configpage();

	}

	function showtt(id) {

		myChart.draw(0, true);
		$('#' + id).toggleClass('hidden');

	}

	function mounthFilter() {

		var mon = parseInt($('#mon option:selected').val());

		//console.log(mon);

		if (tar !== 'invoices' && tar !== 'journal') {

			$('#contentdiv tbody').find('tr').each(function () {

				if (mon > 0) {

					if (parseInt($(this).data('month')) !== mon) $(this).addClass('hidden');
					else $(this).removeClass('hidden');

				}
				else $(this).removeClass('hidden');

			});

		}
		else configpage();

	}

	$(".showintro").on('click', function () {

		var intro = introJs();

		window.location.hash = 'budjet';

		intro.setOptions({
			'nextLabel': 'Дальше',
			'prevLabel': 'Вернуть',
			'skipLabel': 'Пропустить',
			'doneLabel': 'Я понял',
			'showStepNumbers': false
		});
		intro.start().goToStep(4)
			.onbeforechange(function (targetElement) {

				switch ($(targetElement).attr("data-step")) {
					case "1":
						break;
					case "2":
						break;
					case "3":
						$(targetElement).show();
						break;
					case "4":
						$(targetElement).show();
						break;
					case "5":
						$(targetElement).show();
						break;
					case "6":
						$(targetElement).show();
						break;
					case "7":
						$(targetElement).show();
						break;
					case "8":
						$('#warndiv').removeClass('hidden');
						drowChart();
						setTimeout(100);
						$(targetElement).show();
						break;
					case "9":
						$('#warndiv').addClass('hidden');
						$(targetElement).show();
						break;
					case "10":
						window.location.hash = 'journal';
						$(targetElement).show();
						break;
					case "11":
						$(targetElement).show();
						break;
					case "12":
						$(targetElement).show();
						break;
					case "13":
						window.location.hash = 'provider';
						$(targetElement).show();
						break;
				}
			})
	});

</script>
<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body></html>