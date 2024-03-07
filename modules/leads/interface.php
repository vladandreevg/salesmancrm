<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */

$title = 'Обработчик интересов';

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

//настройки модуля для аккаунта
$mdwset       = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'leads' and identity = '$identity'" );
$leadsettings = json_decode( $mdwset['content'], true );
$lusers       = (array)$leadsettings['leadOperator'];
$coordinator  = $leadsettings["leadСoordinator"];

if ( !empty( $lusers ) ) {
	$lusers[] = $coordinator;
}

$api_key = $db -> getOne( "select api_key from ".$sqlname."settings WHERE id = '$identity'" );
?>

<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler open" title="Фильтры"><i class="icon-toggler"></i></a>
		<a href="javascript:void(0)" onclick="configpage();" title="Обновить представление"><i class="icon-arrows-cw"></i></a>

		<A href="#lists" class="razdel pl5 pr5" data-id="lists" title="Список заявок" data-step="7" data-intro="<h1>Список заявок</h1>Раздел для обработки заявок" data-position="right"><i class="icon-doc-text-inv"></i></A>
		<A href="#source" class="razdel pl5 pr5" data-id="source" title="Источники" data-step="8" data-intro="<h1>Каналы продаж</h1>Управление каналами продаж" data-position="right"><i class="icon-switch"></i></A>
		<A href="#utms" class="razdel pl5 pr5" data-id="utms" title="Генератор utm-ссылок" data-step="9" data-intro="<h1>Генератор utm-ссылок</h1>Здесь можно создавать и хранить ссылки с UTM-метками для размещения в сети" data-position="right"><i class="icon-share"></i></A>

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
	require_once $rootpath."/modules/leads/navi.leads.php";
	flush();
	?>

</DIV>
<DIV class="ui-layout-center disable--select compact" style="overflow: hidden" data-step="10" data-intro="<h1>Документация на модуль</h1>Вы можете ознакомиться с модулем более подробно в Документации:<br><a href='https://salesman.pro/docs/62' target='_blank'>https://salesman.pro/docs/62</a>" data-position="left">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="pt5 pb10 flex-container">

			<div class="column flex-column wp50 fs-11 pl5 border-box">
				<b><?= $title ?></b>&nbsp;/&nbsp;<span id="tips"></span>
			</div>
			<div class="column flex-column wp50 text-right">

				<div class="menu_container" data-step="6" data-intro="<h1>Меню действий.</h1>Выполнение доступных действий" data-position="left">

					<a href="javascript:void(0)" onclick="submenu('sub')" class="tagsmenuToggler"><b>Действия</b>&nbsp;<i class="icon-angle-down" id="mapi"></i></a>

					<div class="tagsmenu toright hidden">

						<div class="items noBold fs-09">

							<?php if ( $coordinator == $iduser1 ) { ?>
								<div onclick="editLead('','add');" class="item ha hand">
									<span><i class="icon-archive green"><i class="sup icon-plus-circled red"></i></i></span>&nbsp;&nbsp;Добавить
								</div>
								<div onclick="getLeads()" class="item ha hand" title="Проверить вручную">
									<span><i class="icon-inbox blue"><i class="sup icon-arrows-cw red"></i></i></span>&nbsp;&nbsp;Проверить
								</div>
							<?php } ?>
							<?php if ( $coordinator == $iduser1 || in_array( $iduser1, $lusers ) ) { ?>
								<div onclick="editLead('','import');" class="item ha hand">
									<span><i class="icon-download-1 broun"><i class="sup icon-reply red"></i></i></span>&nbsp;&nbsp;Импорт из Excel
								</div>
								<div onclick="editLead('','export');" class="item ha hand">
									<span><i class="icon-upload-1 blue"><i class="sup icon-forward-1 red"></i></i></span>&nbsp;&nbsp;Экспорт в Excel
								</div>
							<?php } ?>
							<?php if ( $coordinator == $iduser1 ) { ?>
								<div onclick="massSend();" title="Групповые действия" class="item ha hand">
									<span><i class="icon-magic blue"><i class="sup icon-direction red"></i></i></span>&nbsp;&nbsp;Групповые действия
								</div>
							<?php } ?>

						</div>

					</div>

				</div>
				&nbsp;&nbsp;<a href="javascript:void(0)" title="Обновить" onclick="configpage()"><i class="icon-arrows-cw blue"></i> Обновить</a>&nbsp;&nbsp;

			</div>

		</div>

	</DIV>

	<form name="cform" id="cform">
		<div class="nano relativ" id="clientlist">

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

<script src="/assets/js/clipboard.js/clipboard.min.js"></script>
<script>

	//includeJS("/assets/js/clipboard.min.js");

	var hash = window.location.hash.substring(1);

	if (hash === '') hash = 'lists';

	if (isMobile || $(window).width() < 767) {

		$('.lpToggler').toggleClass('open');

	}

	$(function () {

		//$('.ui-layout-center').append('<div class="tableHeader" style="position:absolute; width: 100%"></div>');

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

		$(window).trigger('onhashchange');

		$.Mustache.load('/modules/leads/tpl.leads.mustache');

		razdel(hash);

		constructSpace();

		$('.inputdate').each(function () {

			if (isMobile !== true) $(this).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 2,
				firstDay: 1,
				dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
				monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
				changeMonth: true,
				changeYear: true,
				yearRange: '1940:2030',
				minDate: new Date(1940, 1 - 1, 1),
				showButtonPanel: true,
				currentText: 'Сегодня',
				closeText: 'Готово'
			});

		});

		$(".nano").nanoScroller({alwaysVisible: true});

		changeMounth();

	});

	window.onhashchange = function () {

		var hash = window.location.hash.substring(1);

		razdel(hash);

		$('#rmenu').find('a').removeClass('active');
		$('#rmenu').find('a[data-id="' + hash + '"]').addClass('active');

	};

	<?php
	if ( $_REQUEST['id'] > 0 ) {
		print "doLoad('modules/leads/form.leads.php?action=view&id=".$_REQUEST['id']."')";
	}
	?>

	function configpage() {

		$('#contentdiv').parent(".nano").nanoScroller({scroll: 'top'});

		var str = $('#pageform').serialize();
		var url = '/modules/leads/list.leads.php';
		var tar = $('#tar').val();

		$('#contentdiv').append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $('#contentdiv').height();
		var cdwidth = $('#contentdiv').width();

		$('.contentloader').height(cdheight).width(cdwidth);

		/*------------*/

		if ($('.lpToggler').hasClass('open') && isMobile) $('.lpToggler').trigger('click');

		$.getJSON(url, str, function (viewData) {

			$('#contentdiv').empty().mustache(tar + 'Tpl', viewData);

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

				var tar = $('#tar').val();
				var order = $('#ord').val();
				var desc = $('#tuda').val();
				var icn = '<i class="icon-angle-up"></i>';

				if (desc === 'desc') icn = '<i class="icon-angle-down"></i>';

				$('.header_contaner').find('#x-' + order).prepend(icn);

				$(".nano").nanoScroller();

				if (isMobile) {

					$('#contentdiv').find('table').rtResponsiveTables();

				}

				var clipboard = new Clipboard('.url');

				clipboard.off();
				clipboard.on('success', function (e) {

					alert("Скопировано в буфер");
					e.clearSelection();

				});

			});
	}

	/*
	Вызываем при применении фильтров, чтобы начинать с 1 страницы
	 */
	function preconfigpage() {

		$('#page').val('1');
		configpage();

	}

	function razdel(hesh) {

		$('.razdel a').removeClass('active');

		if (!hesh) hesh = window.location.hash.replace('#', '');
		if (!hesh) hesh = 'lists';

		switch (hesh) {
			case 'lists':
				$('#tips').html('Заявки');
				$('#ord').val('datum');

				$('.menu-lists').removeClass('hidden');
				$('.menu-utms').addClass('hidden');
				$('.menu-source').addClass('hidden');

				$('.contaner-lists').removeClass('hidden');
				$('.contaner-utms').addClass('hidden');
				$('.contaner-source').addClass('hidden');

				break;
			case 'utms':
				$('#tips').html('UTM-метки');
				$('#ord').val('datum');

				$('.menu-utms').removeClass('hidden');
				$('.menu-lists').addClass('hidden');
				$('.menu-source').addClass('hidden');

				$('.contaner-utms').removeClass('hidden');
				$('.contaner-lists').addClass('hidden');
				$('.contaner-source').addClass('hidden');

				break;
			case 'source':
				$('#tips').html('Источники');
				$('#ord').val('name');

				$('.menu-source').removeClass('hidden');
				$('.menu-lists').addClass('hidden');
				$('.menu-utms').addClass('hidden');

				$('.contaner-source').removeClass('hidden');
				$('.contaner-lists').addClass('hidden');
				$('.contaner-utms').addClass('hidden');

				break;
		}

		$('#tar').val(hesh);

		$('.razdel .' + hesh).addClass('active');//.css('border','1px solid red');

		preconfigpage();

	}

	$(window).on('resize', function () {

		if (!isMobile) constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		if (!isMobile) {

			constructSpace();
			$('.ui-layout-center').trigger('onPositionChanged');

		}


	});

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

	function constructSpace() {

		var hf = $('.ui-layout-center').actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		$('.nano').nanoScroller();

	}

	$('.ui-layout-center').onPositionChanged(function () {

		if (this.resizeTO) clearTimeout(this.resizeTO);
		this.resizeTO = setTimeout(function () {


		}, 200);

		$('.ui-layout-content').css({"width": "100%"});
		$('#list_header').css({"width": "100%"});

	});

	function searchEmail(email) {

		$('#word').val(email);
		preconfigpage();

	}

	function searchPhone(phone) {

		$('#word').val(phone);
		preconfigpage();

	}

	function clearalllead() {

		//$('#statuss\\[\\]').attr('checked', false);

		$('#statuss\\[\\] [value="0"]').attr('checked', true);
		$('#statuss\\[\\] [value="1"]').attr('checked', true);
		$('#statuss\\[\\] [value="2"]').attr('checked', false);
		$('#statuss\\[\\] [value="3"]').attr('checked', false);

		$('.user').attr('checked', false);
		$('#word').val('');

		preconfigpage();

	}

	function clearFilter() {

		if ($('#ifilter').hasClass('icon-eye-off')) {

			$('#statuss\\[\\]').prop('checked', false);
			$('#ifilter').removeClass('icon-eye-off').addClass('icon-eye');

			preconfigpage();

		}
		else {

			$('#statuss\\[\\]').prop('checked', true);
			$('#ifilter').addClass('icon-eye-off').removeClass('icon-eye');

			preconfigpage();

		}

	}

	function clearUserFilter() {

		if ($('#ufilter').hasClass('icon-eye-off')) {

			$('.user').prop('checked', false);
			$('#ufilter').removeClass('icon-eye-off').addClass('icon-eye');

			preconfigpage();

		}
		else {

			$('.user').prop('checked', true);
			$('#ufilter').addClass('icon-eye-off').removeClass('icon-eye');

			preconfigpage();

		}

	}

	function change_page(page) {

		$('#ch').prop('checked', false);
		$('#page').val(page);

		configpage();

	}

	function changesort(param) {


		var tt = $('#ord').val();

		$('#ord').val(param);
		$('#page').val('1');

		if (param === tt) {

			if ($('#tuda').val() === '')
				$('#tuda').val('desc');

			else
				$('#tuda').val('');

		}

		configpage();

	}

	function getLeads() {

		var url = '/developer/leads/cron.php?notifi=yes&api_key=<?=$api_key?>';

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Загрузка данных...</div>');

		$.post(url, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			configpage();

			return true;

		});

	}

	function massSend() {

		var str = $("#cform").serialize();
		var count = $('.mc:checked').length;

		var url = '/modules/leads/form.leads.php?action=mass&count=' + count;

		doLoad(url + '&' + str).append('<div id="loader" class="loader">Загрузка данных...</div>');

		return false;

	}

	function exportDo() {

		var str = $('#params').serialize();
		var url = '/modules/leads/edit.php?action=export&' + str;
		window.open(url);

		return false;

	}

	function removeUTM(id) {

		var url = '/modules/leads/core.leads.php?id=' + id + '&action=utms.delete';

		$('#message').css('display', 'block').append('<div id=loader><img src=images/loader.svg> Загрузка данных. Пожалуйста подождите...</div>');
		$.get(url, function (data) {

			configpage();

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);
		});

	}

	$(".showintro").on('click', function () {

		var intro = introJs();

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
						$('#sub').show();
						$(targetElement).show();
						break;
					case "7":
						$(targetElement).show();
						break;
					case "8":
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