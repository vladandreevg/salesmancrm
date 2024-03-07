<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2018.x           */
/* ============================ */

$title = 'Метрики';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

?>
<DIV class="" id="rmenu">

	<div class="tabs" data-step="4" data-intro="<h1>Разделы модуля.</h1><b class='red'>Метрики</b> - здесь можно задавать План продаж и показатели для каждого сотрудника<br><br><b class='green'>Показатели KPI</b> - в этом разделе вы можете составить базу своих собственных KPI" data-position="right">

		<a href="javascript:void(0)" class="lpToggler" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="#users" class="razdel pl5 pr5" data-id="users" title="Метрики"><i class="icon-sliders"></i></A>
		<?php
		if ( $isadmin == 'on' ) {
			?>
			<A href="#kpis" class="razdel pl5 pr5" data-id="kpis" title="Параметры KPI"><i class="icon-article-alt"><i class="sup icon-tools"></i></i></A>

			<A href="javascript:void(0)" onclick="$metrics.editKPIBase()" class="razdel redbg-dark pl5 pr5" title="Новый KPI" data-step="6" data-intro="<h1>Добавить KPI.</h1>Вы можете добавить любое количество KPI" data-position="right"><i class="icon-plus-squared white"><i class="sup icon-article-alt"></i></i></A>

			<A href="javascript:void(0)" onclick="$metrics.editKPISeason()" class="razdel greenbg-dark pl5 pr5" title="Сезонные коэффициенты" data-step="7" data-intro="<h1>Сезонные коэффициенты.</h1>Вы можете настроить сезонные коэффициенты для расчета KPI" data-position="right"><i class="icon-leaf white"><i class="sup icon-article-alt"></i></i></A>
		<?php } ?>

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
<DIV class="ui-layout-west disable--select outlook--close">

	<?php
	require_once $rootpath."/modules/metrics/navi.metrics.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-center disable--select outlook--close" style="overflow: hidden" data-step="7" data-intro="<h1>Существующие KPI</h1>Здесь будет приведен список ваших KPI" data-position="right">

	<DIV class="mainbg listHead p0 hidden-iphone" data-fase="main">

		<div class="pt5 pb10 flex-container">

			<div class="column flex-column wp100 fs-11 pl5 pt10 border-box">
				<b class="shado">Метрики</b>&nbsp;/&nbsp;<span id="tips"></span>
			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
		<div class="nano" id="metriclist">

			<div class="nano-content">
				<div class="ui-layout-content" id="contentdiv"></div>
			</div>

		</div>
	</form>

</DIV>
<DIV class="ui-layout-east relativ outlook--close" data-step="8" data-intro="<h1>Документация на модуль</h1>Вы можете ознакомиться с модулем более подробно в Документации:<br><a href='https://salesman.pro/docs/138' target='_blank'>https://salesman.pro/docs/138</a>" data-position="left">

	<DIV class="mainbg h40 listHead pr15" id="mtsmenu" data-fase="users">

		<div class="inline hidden-iphone">

			<div class="button--group flex-container w350">

				<div class="flex-string">
					<a href="javascript:void(0)" onclick="$('#metricinfo').scrollTo('#bplan', 200)" class="button greenbtn ptb3"><i class="icon-gauge"></i> Продажи</a>
				</div>
				<div class="flex-string hidden">
					<a href="javascript:void(0)" onclick="" class="button ptb3"><i class="icon-chart-bar"></i> Факт</a>
				</div>
				<div class="flex-string">
					<a href="javascript:void(0)" onclick="$('#metricinfo').scrollTo('#bkpi', 200)" class="button redbtn ptb3"><i class="icon-article-alt"></i> KPI</a>
				</div>
				<div class="flex-string">
					<a href="javascript:void(0)" onclick="getSwindow('/reports/ent-planDoByPayment.php', 'Выполнение планов')" class="button bluebtn ptb3"><i class="icon-article-alt"></i> Отчет</a>
				</div>

			</div>

		</div>

		<div class="visible-iphone gray">
			<a href="javascript:void(0)" onclick="$('.ui-layout-east').removeClass('open');" title=""><i class="icon-cancel-circled"></i> Закрыть</a>
		</div>

		<div class="inline pull-aright" data-step="5" data-intro="<h1>Навигация по годам</h1>Вывод данных за разные годы" data-position="left">

			<a href="javascript:void(0)" onClick="$metrics.yearChange(-1);"><i class="icon-angle-double-left"></i><span class="prevYear"></span></a>
			&nbsp;&nbsp;
			<span class="red Bold miditxt currentYear"></span>
			&nbsp;&nbsp;
			<a href="javascript:void(0)" onClick="$metrics.yearChange(1);"><span class="nextYear"></span><i class="icon-angle-double-right"></i></a>&nbsp;&nbsp;

		</div>

	</DIV>
	<div class="ui-layout-content ui-border bgwhite">

		<DIV class="pad10 block" id="metricinfo" style="overflow-y: auto"></DIV>

	</div>

</DIV>
<DIV class="ui-layout-south"></DIV>

<div id="startinto">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<div class="pagerefresh refresh--icon orange" onclick="openlink('https://salesman.pro/docs/138')" title="Документация"><i class="icon-help"></i></div>
<div class="pagerefresh refresh--icon" onClick="razdel($hash)" title="Обновить">
	<i class="icon-arrows-cw"></i>
</div>

<script>

	if (isMobile || $(window).width() < 767) {

		$('.lpToggler').toggleClass('open');

	}

	includeJS('/assets/js/dimple.min.js');
	includeJS('/assets/js/jquery/jquery.scrollTo.js');

	var $display = 'metrics';

	var $toggler = $('.lpToggler');
	var $elcenter = $('.ui-layout-center');
	var $elwest = $('.ui-layout-west');
	var $eleast = $('.ui-layout-east');
	var $content = $('#contentdiv');
	var $rmenu = $('#rmenu');

	var $Year = new Date().getFullYear();
	var $User;
	var $Kpi;
	var $chart = {};

	if (isMobile || $(window).width() < 767) {

		$toggler.toggleClass('open');

	}

	var $hash = window.location.hash.substring(1);
	if ($hash === '') $hash = 'users';

	$(function () {

		$rmenu.find('a').removeClass('active');
		$rmenu.find('a[data-id="' + $hash + '"]').addClass('active');

		$.Mustache.load('/modules/metrics/tpl.metrics.mustache');

		$(window).trigger('onhashchange');

		$metrics.yearRange($Year);

		razdel($hash);

		$(".nano").nanoScroller();

		changeMounth();


	});

	window.onhashchange = function () {

		$hash = window.location.hash.substring(1);

		razdel($hash);

		$rmenu.find('a').removeClass('active');
		$rmenu.find('a[data-id="' + $hash + '"]').addClass('active');

		if (isMobile) {

			$('.ui-layout-east').removeClass('open');

		}

	};

	function razdel(hesh) {

		$rmenu.find('a').removeClass('active');

		if (!hesh) hesh = window.location.hash.replace('#', '');
		if (!hesh) hesh = 'users';

		$hash = hesh;

		switch ($hash) {
			case 'users':
				$('#tips').html('Сотрудники');

				$('.menu-list').removeClass('hidden');
				$('.menu-item').addClass('hidden');
				$('.menu_container').removeClass('hidden');

				$('.contaner-list').removeClass('hidden');
				$('.contaner-item').removeClass('hidden');

				$('div[data-fase="users"]').removeClass('hidden');

				break;
			case 'kpis':
				$('#tips').html('KPI');

				$('.menu-list').removeClass('hidden');
				$('.menu-item').addClass('hidden');
				$('.menu_container').removeClass('hidden');

				$('.contaner-list').removeClass('hidden');
				$('.contaner-item').addClass('hidden');

				$('.listHead').removeClass('hidden');

				if (!isMobile) $('div[data-fase="users"]').addClass('hidden');

				break;
		}

		$rmenu.find('a[data-id="' + $hash + '"]').addClass('active');

		configpage();
		constructSpace();

	}

	function constructSpace() {

		var ht = ($('.listHead[data-fase="main"]').is(':visible')) ? $('.listHead[data-fase="main"]').actual('outerHeight') : 0;
		var hh = $elcenter.actual('height');
		var hm = $elcenter.actual('height') - $('#mtsmenu').actual('outerHeight') - 20;
		var hf = $elcenter.actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;

		$('#metriclist').css({"height": hh - ht + "px"});
		$('#metricinfo').css({"height": hm + "px"});

		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		$('.nano').nanoScroller();

		//console.log(hm);

	}

	$(window).on('resize', function () {

		constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		constructSpace();

		$elcenter.trigger('onPositionChanged');

	});
	$(document).on('click', '.userblock', function () {

		$User = $(this).data('id');
		$metrics.viewUser();

	});
	$(document).on('click', '.kpiblock', function () {

		$Kpi = $(this).data('id');
		$metrics.viewKPIBase();

	});

	$toggler.on('click', function () {

		if (isMobile || $(window).width() < 767) {

			$elwest.toggleClass('open');
			$elcenter.toggleClass('open');

		}
		else {

			$elwest.toggleClass('outlook outlook--close');
			$elcenter.toggleClass('outlook outlook--close');
			$eleast.toggleClass('outlook outlook--close');

		}
		$(this).toggleClass('open');

	});

	$elcenter.onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {

			var hw = $(this).width();

			$('.ui-layout-center').find('.tableHeader').css({"width": hw + "px"});
			$('#list_header').css({"width": hw + "px"});

			drawChart();

		}, 200);

	});

	function configpage() {

		$content.parent('.nano').nanoScroller({scroll: 'top'});

		var str = $('#pageform').serialize();
		var url = '/modules/metrics/list.metrics.php?action=' + $hash;

		$content.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $elcenter.height();
		var cdwidth = $elcenter.width();

		$('.contentloader').height(cdheight).width(cdwidth);

		/*------------*/

		if ($hash === 'users') {

			$.getJSON(url, str, function (viewData) {

				$content.empty().mustache($hash + 'Tpl', viewData);

			})
				.done(function () {

					if (!$User) $User = parseInt($content.find('.userblock:first-child').data('id'));

					if ($User > 0) {

						if (!isMobile) $metrics.viewUser();

					}
					else {

						$('#metricinfo').html('<div id="emptymessage" class="gray miditxt div-center"><i class="icon-doc icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбран сотрудник</b></div>');

					}

					$(".nano").nanoScroller();

				});

		}
		else if ($hash === 'kpis') {

			$.getJSON(url, str, function (viewData) {

				$content.empty().mustache($hash + 'Tpl', viewData);

			})
				.done(function () {

					if (!$Kpi) $Kpi = parseInt($content.find('.kpiblock:first-child').data('id'));

					if ($Kpi > 0 && !isMobile) $metrics.viewKPIBase($Kpi);
					else {

						$('#metricinfo').html('<div id="emptymessage" class="gray miditxt div-center"><i class="icon-doc icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Ничего не выбрано</b></div>');

					}

					$(".nano").nanoScroller();

				});

		}

		/*------------*/

	}

	//Функции работы с разделами
	var $metrics = {

		viewUser: function () {

			var url = '/modules/metrics/list.metrics.php';
			var str = 'action=user&iduser=' + $User + '&year=' + $Year;
			var plan;

			$('.userblock[data-id="' + $User + '"]').addClass('bluebg-sub');
			$('.userblock').not('[data-id="' + $User + '"]').removeClass('bluebg-sub');

			$.getJSON(url, str, function (viewData) {

				$('#metricinfo').empty().mustache('metricsTpl', viewData);
				plan = viewData.list.plan;

			})
				.done(function () {

					$(".nano").nanoScroller();

					var datas = [];
					var datasf = [];
					var datam = [];
					var datamf = [];
					var order = [];

					for (var i in plan) {

						order.push(plan[i].month);

						datas.push({
							"Тип": "План.Оборот",
							"Категория": "План",
							"Месяц": plan[i].month,
							"Сумма": parseFloat(plan[i].summa.replace(/ /g, '').replace(/,/g, '.'))
						});
						datam.push({
							"Тип": "План.Маржа",
							"Категория": "План",
							"Месяц": plan[i].month,
							"Сумма": parseFloat(plan[i].marga.replace(/ /g, '').replace(/,/g, '.'))
						});

						datas.push({
							"Тип": "Факт.Оборот",
							"Категория": "Факт",
							"Месяц": plan[i].month,
							"Сумма": parseFloat(plan[i].fsumma.replace(/ /g, '').replace(/,/g, '.'))
						});
						datamf.push({
							"Тип": "Факт.Маржа",
							"Категория": "Факт",
							"Месяц": plan[i].month,
							"Сумма": parseFloat(plan[i].fmarga.replace(/ /g, '').replace(/,/g, '.'))
						});

					}

					$chart['datas'] = datas;
					//$chart['datasf'] = datasf;
					$chart['datam'] = datam;
					$chart['datamf'] = datamf;
					$chart['order'] = order;

					drawChart();

					if (isMobile) {

						$('.ui-layout-east').addClass('open');

					}

				});

		},
		editPlan: function (id) {

			if (!id) id = $User;

			var url = '/modules/metrics/form.metrics.php?action=edit.plan&iduser=' + id + '&year=' + $Year;
			doLoad(url);

		},
		viewKPI: function (kpi, iduser) {

			var url = '/modules/metrics/list.metrics.php';
			var month = ($('#month').is('select')) ? $('#month').val() : '';
			var str = 'action=user.kpido&id=' + kpi + '&iduser=' + iduser + '&year=' + $Year + '&month=' + month;

			$('.userkpis').empty().removeClass('hidden').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="30px" height="30px"></div>');

			$.getJSON(url, str, function (viewData) {

				$('.userkpis').empty().mustache('userkpisTpl', viewData);

			})
				.done(function () {

					$('.userkpis').find(".percent").each(function () {

						var num = parseInt($(this).find('div').html());
						var parent = $(this).closest('.flex-container');

						if (num === 0) parent.addClass('gray');
						else if (num <= 50) parent.addClass('red');
						else if (num <= 70) parent.addClass('blue');
						else if (num <= 90) parent.addClass('green-lite');
						else parent.addClass('green');

					});

					$('#metricinfo').scrollTo('#bkpifact', 200);

				});

		},
		editKPI: function (id, iduser) {

			if (!id) id = $Kpi;

			var url = '/modules/metrics/form.metrics.php?action=edit.kpi&iduser=' + iduser + '&id=' + id + '&year=' + $Year;
			doLoad(url);

		},
		deleteKPI: function (id) {

			Swal.fire({
					title: 'Вы уверены?',
					text: "Этот показатель также будет удален у сотрудников, если используется!",
					type: 'question',
					showCancelButton: true,
					confirmButtonColor: '#3085D6',
					cancelButtonColor: '#D33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {

					var url = '/modules/metrics/core.metrics.php?action=delete.kpi&id=' + id;

					$.get(url, function (data) {

						Swal.fire(data);

						$('.userkpis').empty().addClass('hidden');

						if (typeof configpage === 'function') configpage();

					});

				}
			).then((result) => {

				if (result.value) {

					var url = '/modules/metrics/core.metrics.php?action=delete.kpi&id=' + id;

					$.get(url, function (data) {

						Swal.fire(data);

						$('.userkpis').empty().addClass('hidden');

						if (typeof configpage === 'function') configpage();

					});

				}

			});

		},
		viewKPIBase: function (id) {

			if (!id) id = $Kpi;

			var url = '/modules/metrics/list.metrics.php';
			var str = 'action=kpi&id=' + id;

			$('.kpiblock[data-id="' + id + '"]').addClass('bluebg-sub');
			$('.kpiblock').not('[data-id="' + id + '"]').removeClass('bluebg-sub');

			$.getJSON(url, str, function (viewData) {

				$('#metricinfo').empty().mustache('kpiTpl', viewData);

			})
				.done(function () {

					//$(".nano").nanoScroller();

					if (isMobile) {

						$('.ui-layout-east').addClass('open');

					}

				});

		},
		editKPIBase: function (id) {

			if (!id) id = 0;

			var url = '/modules/metrics/form.metrics.php?action=edit.kpiBase&id=' + id + '&year=' + $Year;
			doLoad(url);

		},
		deleteKPIBase: function (id) {

			var next = $('.kpiblock[data-id="' + id + '"]').next().data('id');

			Swal.fire({
					title: 'Вы уверены?',
					text: "Этот показатель также будет удален у сотрудников, если используется!",
					type: 'question',
					showCancelButton: true,
					confirmButtonColor: '#3085D6',
					cancelButtonColor: '#D33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {

					var url = '/modules/metrics/core.metrics.php?action=delete.kpiBase&id=' + id;

					$.get(url, function (data) {

						Swal.fire(data);

						$Kpi = next;

						if (typeof configpage === 'function') configpage();

					});

				}
			).then((result) => {

				if (result.value) {

					var url = '/modules/metrics/core.metrics.php?action=delete.kpiBase&id=' + id;

					$.get(url, function (data) {

						Swal.fire(data);

						$Kpi = next;

						if (typeof configpage === 'function') configpage();

					});

				}

			});

		},
		yearRange: function (year) {

			if (!year) $Year = year;

			$('.currentYear').html($Year);
			$('.prevYear').html($Year - 1);
			$('.nextYear').html($Year + 1);

		},
		yearChange: function (num) {

			$Year = $Year + num;

			$('.currentYear').html($Year);
			$('.prevYear').html($Year - 1);
			$('.nextYear').html($Year + 1);

			if ($hash === 'users') this.viewUser();
			else this.viewKPIBase();

		},
		exportPlan: function () {

			var str = $("#pageform").serialize();

			window.open('/modules/metrics/core.metrics.php?action=export.plan&' + str + '&year=' + $Year);
			DClose();

		},
		exportKPI: function (kpi, iduser) {

			var month = ($('#month').is('select')) ? $('#month').val() : '';
			var str = 'action=user.kpido&id=' + kpi + '&iduser=' + iduser + '&year=' + $Year + '&month=' + month;

			window.open('/modules/metrics/list.metrics.php?export=yes&' + str);
			DClose();

		},
		importPlan: function () {

			doLoad('/modules/metrics/form.metrics.php?action=import.plan&year=' + $Year);

		},
		editKPISeason: function () {

			doLoad('/modules/metrics/form.metrics.php?action=edit.season');

		}

	};

	function drawChart() {

		$('#chart').empty();

		var width = $('#chart').width() - 0;
		var height = 200;
		var svg = dimple.newSvg("#chart", width, height);
		var myChart = new dimple.chart(svg, $chart.datas);

		myChart.setBounds(30, 40, width - 40, height - 50);

		var x = myChart.addCategoryAxis("x", ["Месяц", "Субтип"]);
		x.addOrderRule($chart.order);//порядок вывода, иначе группирует

		var y = myChart.addMeasureAxis("y", "Сумма", null);
		y.showGridlines = false;//скрываем линии
		y.ticks = 5;//шаг шкалы по оси y
		y.tickFormat = ",.2f";

		myChart.ease = "bounce";
		//myChart.staggerDraw = false;
		myChart.clamp = false;

		myChart.assignColor("План.Оборот", "#B0BEC5", "#CFD8DC");
		myChart.assignColor("План.Маржа", "#607D8B", "#607D8B");
		myChart.assignColor("Факт.Оборот", "rgba(255,160,0 ,0.5)", "rgba(255,160,0 ,0.8)");
		myChart.assignColor("Факт.Маржа", "#B71C1C", "rgba(103,20,87 ,1.1)");

		var s = myChart.addSeries(["Тип"], dimple.plot.bar, null);
		var s3 = myChart.addSeries(["Тип"], dimple.plot.bar, null);
		var s1 = myChart.addSeries(["Тип"], dimple.plot.line);
		var s2 = myChart.addSeries(["Тип"], dimple.plot.line);

		s1.data = $chart.datam;
		s2.data = $chart.datamf;
		//s3.data = $chart.datasf;

		//s.barGap = 0.40;
		//s3.barGap = 0.60;

		//s.stacked = true;

		myChart.addLegend(5, 0, width, 0, "right");
		myChart.setMargins(100, 20, 20, 70);
		myChart.floatingBarWidth = 10;

		/*s.addEventHandler("click", function (e) {
		 showDataa(e.xValue);
		 });*/

		myChart.draw(1000);
		//y.tickFormat = ",.f";

		svg.selectAll(".dimple-marker,.dimple-marker-back").attr("r", 2);

		y.titleShape.remove();
		x.titleShape.remove();

		$eleast.onPositionChanged(function () {
			myChart.draw(0, true);
		});

	}

	$(".showintro").click(function () {

		var intro = introJs();

		window.location.hash = 'users';

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
					case "2":
					case "41":
					case "1":
					case "8":
					case "3":
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
						window.location.hash = 'kpis';
						$(targetElement).show();
						break;
					case "9":
						$(targetElement).show();
						break;
					case "10":
						$(targetElement).show();
						break;
					case "11":
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
</body>
</html>