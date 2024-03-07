<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/*  (C) 2019 Ivan Drachyov      */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

$title = "Корпоративный университет";

global $rootpath;
require_once $rootpath."/inc/head.php";
flush();

$mdcset      = $db -> getRow( "SELECT * FROM ".$sqlname."modules WHERE mpath = 'corpuniver' and identity = '$identity'" );
$mdcsettings = json_decode( $mdcset[ 'content' ], true );

// очистим от возможных пробелов между расширениями
if($ext_allow != '')
	$ext_allow = yimplode(",", yexplode(",", $ext_allow));

?>
<DIV class="" id="rmenu">

	<div class="tabs">

		<a href="javascript:void(0)" class="lpToggler" title="Фильтры"><i class="icon-toggler"></i></a>

		<A href="javascript:void(0)" onclick="configpage()" class="razdel bluebg pl5 pr5" title="Обновить"><i
					class="icon-arrows-cw"></i></A>
		<?php if ( $isadmin == "on" or in_array( $iduser1, $mdcsettings[ 'Editor' ] ) ) { ?>
			<A href="javascript:void(0)" onclick="editCourse('','edit')" class="razdel redbg-dark pl5 pr5" title="Добавить"><i class="icon-graduation-cap-1 white"><i class="icon-plus-circled sup fs-07"></i></i></A>
		<?php } ?>
		<?php require_once $rootpath."/content/leftnav/leftpop.php";
		flush(); ?>

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
	require_once $rootpath."/modules/corpuniver/navi.corpuniver.php";
	flush(); 
	?>

</DIV>
<DIV class="ui-layout-center disable--select outlook--close" style="overflow: hidden">

	<DIV class="mainbg listHead p0 hidden-iphone">

		<div class="pt5 pb10 flex-container">
			<div class="column flex-column wp100 fs-11 pl5 pt10 border-box Bold">
				<span class="hidden-iphone">Корпоративный университет&nbsp;/&nbsp;</span><span id="tips">Курсы</span>
			</div>
		</div>

	</DIV>

	<form name="cform" id="cform">
		<div class="nano relativ" id="univerlist">

			<div class="nano-content">
				<div class="ui-layout-content" id="contentdiv"></div>
			</div>

		</div>
	</form>

	<div class="pagecontainer short">
		<div class="page pbottom mainbg" id="pagediv"></div>
	</div>

</DIV>
<DIV class="ui-layout-east relativ outlook--close" style="display: block">

	<DIV class="mainbg h50 listHead text-right pr15 hidden" id="univermenu"></DIV>
	<div class="ui-layout-content ui-border bgwhite">

		<DIV class="pad10 block" id="messagediv"></DIV>

	</div>

</DIV>
<DIV class="ui-layout-south"></DIV>

<div id="startinto" class="hidden">

	<div class="relativ">

		<div class="showintro" title="Запустить гид для знакомства с CRM">
			<span><i class="icon-help-circled-1"></i></span>Знакомство
		</div>
		<div id="hideintro" title="Больше не показывать гид"><i class="icon-cancel-circled"></i></div>

	</div>

</div>

<script>

	includeJS("/assets/js/jquery.liTextLength.js");
	includeCSS("/modules/corpuniver/css/corpuniver.css");

	var $display = 'corpuniver';
	var $toggler = $('.lpToggler');
	var $elcenter = $('.ui-layout-center');
	var $elwest = $('.ui-layout-west');
	var $eleast = $('.ui-layout-east');
	var $content = $('#contentdiv');
	var $rmenu = $('#rmenu');
	var $current = 0;
	var editor2;
	var extention = ('<?=$ext_allow?>').split(",");

	$.Mustache.load('/modules/corpuniver/tpl.corpuniver.mustache');

	$current = parseInt(localStorage.getItem("currentCource"));

	//console.log($current);

	$(function () {

		constructSpace();

		clear();
		configpage();

		$(".nano").nanoScroller();

		changeMounth();

	});

	/**
	 * Управление компонентами курса. Старт
	 */

	// Блок добавления/редактирования
	$(document).on('click', '.editItem', function () {

		var $goal;
		var $elem;
		var buttons;
		var atop;
		var aleft;
		var awidth;

		edit_close();

		var tip = $(this).data('tip');
		var lec = parseInt($(this).data('lec'));
		var id = parseInt($(this).data('id'));

		var course = $('#course').html();

		if (tip === 'Task') {

			var $el = $('#subwindow');

			$.post('/modules/corpuniver/form.corpuniver.php?action=edit.task&id=' + id + '&lec=' + lec + '&course=' + course, function (data) {

				$el.empty().css({'z-index': '25', 'background': '#F5F5F5'}).addClass('open').append(data);

			});

		}
		else {

			doLoad('/modules/corpuniver/form.corpuniver.php?action=edit.item.dialog&type=' + tip + '&id=' + id + '&lec=' + lec + '&course=' + course);

		}

		return false;

	});

	// Изменение порядка вывода блоков. Вниз
	$(document).on('click', '.down-lec', function () {

		var arr = [];
		var num = 0;
		var type = $(this).data('type');
		var lec = $(this).data('lec');
		var count = $('#count' + type + '-' + lec).html();
		var pdiv = $(this).parent('span').parent('div').parent('li');

		if (pdiv.data('num') < count - 1) {

			pdiv.next().fadeTo(1, 0.3).fadeTo(1500, 1);
			pdiv.fadeTo(1, 0.3).fadeTo(1500, 1);

			pdiv.insertAfter(pdiv.next());

			$('.' + type + 's' + ' li#' + type + '[data-lec="' + lec + '"]').each(function () {

				var id = $(this).data('id');

				$(this).data('num', num);

				num++;

				arr.push(id);

			});

			orderChange(type, arr);

		}
		return false;

	});

	// Изменение порядка вывода блоков. Вверх
	$(document).on('click', '.up-lec', function () {

		var arr = [];
		var num = 0;
		var type = $(this).data('type');
		var lec = $(this).data('lec');
		var pdiv = $(this).parent('span').parent('div').parent('li');

		if (pdiv.data('num') > 0) {

			pdiv.prev().fadeTo(1, 0.3).fadeTo(1500, 1);
			pdiv.fadeTo(1, 0.3).fadeTo(1500, 1);

			pdiv.insertBefore(pdiv.prev());

			$('.' + type + 's' + ' li#' + type + '[data-lec="' + lec + '"]').each(function () {

				$(this).data('num', num);
				num++;

				var id = $(this).data('id');
				arr.push(id);

			});

			orderChange(type, arr);

		}
		return false;

	});

	// Удаление элементов
	$(document).on('click', '.deleteItem', function () {

		var tip = $(this).data('tip');
		var id = $(this).data('id');

		var str = '';
		var url = '';

		url = '/modules/corpuniver/core.corpuniver.php?action=delete&type=' + tip + '&id=' + id;

		Swal.fire({
			title: 'Удаление',
			text: 'Вы действительно хотите удалить запись?',
			type: 'question',
			showCancelButton: true,
			showCloseButton: true,
			confirmButtonColor: '#3085D6',
			cancelButtonColor: '#D33',
			confirmButtonText: 'Да, удалить',
			cancelButtonText: 'Отмена'
		}).then((result) => {
			if (result.value) {

				$.getJSON(url, function (data) {

					if (data.result !== 'Error') {

						const msg = Swal.mixin({
							toast: true,
							position: 'top-end',
							showConfirmButton: false,
							timer: 3000
						});
						msg.fire(
							'Результат: ',
							data.result,
							'success'
						);

						$current = 0;

					}
					else {

						Swal.fire(
							'Не удалось удалить!',
							data.error.text,
							'error'
						)

					}
				});
			}

			preconfigpage();

		});

	});

	$(document).on('click', '.openLec', function () {

		var lecture = $(this).closest('.Lecture').data('id');

		var t = $(this).attr('title');

		$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

		t = (t === 'Свернуть') ? 'Развернуть' : 'Свернуть';
		$(this).attr('title', t);

		$('.itemsLec[data-id="' + lecture + '"]').each(function () {

			$(this).toggleClass('hidden');

		});

		$(".nano").nanoScroller();

	});

	/**
	 * Управление компонентами курса. Финиш
	 */

	$(document).on('click', '.ifolder a', function () {

		var id = $(this).data('id');
		var title = $(this).data('title');

		$('.ifolder a').removeClass('fol_it');
		$(this).addClass('fol_it');

		$('#idcat').val(id);
		$('#place').html(title);
		$('#page').val('');
		$('#tag').val('');

		configpage();

	});

	$(document).on('click', '.messagelist', function () {

		$current = $(this).data('id');

		$(this).addClass('current');
		$eleast.addClass('open');

	});

	$(document).on('click', '.cu--preview', function () {

		let id = $(this).data('id');
		let type = $(this).data('type');
		let url = "/modules/corpuniver/view.corpuniver.php?action=slide&preview=yes&type=" + type + "&id=" + id;

		//doLoad(url);

		doLoad();

		$('#dialog').css({"width":"80vw","height":"90vh"}).center();

		$.get(url, function (data) {

			doLoadAfter();

			$('#resultdiv').empty().html('<div class="zagolovok">Просмотр материала</div><div id="formtabs" style="overflow-y: auto; overflow-x: hidden; max-height:calc(90vh - 60px); height:calc(90vh - 50px);" class="bgwhite p10">' + data + '</div>');

		})
			.done(function () {

				$('#dialog').center();
				$('#resultdiv').css({"height":"calc(90vh - 50px)"});

			});


	});

	$('#folder').on('change', function () {

		if (!isMobile) constructSpace();

	});

	function constructSpace() {

		var hw = $elcenter.width();
		var ht = ($('.listHead:not(#univermenu)').is(':visible')) ? $('.listHead:not(#univermenu)').actual('outerHeight') : 0;
		var hh = $elcenter.actual('height');
		var hm = $elcenter.actual('height');// - $('#univermenu').actual('outerHeight') - 20;

		$('#univerlist').css({"height": hh - ht + "px"});
		$('#messagediv').css({"height": hm + "px"});

		var hf = $elcenter.actual('height') - $('.contaner[data-id="stat"]').actual('outerHeight') - 30;
		$('.contaner[data-id="filter"]').css({"height": hf + "px", "max-height": hf + "px"});

		var hsub = $('#folder').height();
		var hmain = $('#pricecategory .nano').height();
		var hwin = $(document).height();

		if (hsub > 0.5 * hwin && hsub > hmain)
			$('#pricecategory .nano').height(0.8 * hwin + 'px');

		else
			$('#pricecategory .nano').height(0.53 * hwin + 'px');

		$("#pricecategory").find('.nano').nanoScroller();

		$('.nano').nanoScroller();

	}

	$(window).on('resize', function () {

		if (!isMobile) constructSpace();

	});
	$(window).on('resizeend', 200, function () {

		if (!isMobile) constructSpace();

		if (!isMobile) $elcenter.trigger('onPositionChanged');

	});

	$toggler.on('click', function () {

		if (isMobile || $(window).width() < 767) {

			$elwest.toggleClass('open');
			$elcenter.toggleClass('open');

			$eleast.removeClass('open');

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

			var hw = $elcenter.width();

			$elcenter.find('.tableHeader').css({"width": hw + "px"});
			$('#list_header').css({"width": hw + "px"});

		}, 200);

	});

	$(".showintro").click(function () {
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
					case "2":
						$('#menuclients').css('display', 'none');
						break;
					case "1":
					case "6":
					case "7":
					case "8":
						$(targetElement).show();
						break;
					case "3":
						$("#subpan3").show();
						$(targetElement).show();
						break;
					case "4":
						$("#subpan3").hide();
						$(targetElement).show();
						break;
					case "5":
						$(targetElement).show();
						break;
					case "9":
						$('#sub3').show();
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

	function configpage() {

		$content.parent('.nano').nanoScroller({scroll: 'top'});

		var str = $('#pageform').serialize();
		var url = '/modules/corpuniver/list.corpuniver.php';

		$content.append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		var cdheight = $content.height();
		var cdwidth = $content.width();

		$('.contentloader').height(cdheight).width(cdwidth);

		/*------------*/

		$.getJSON(url, str, function (viewData) {

			$content.empty().mustache('corpuniverTpl', viewData);

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

				var id = ($current === 0) ? parseInt($content.find('table tr:first-child').data('id')) : $current;

				console.log(id);

				if (id > 0) {

					$current = id;

					if (!isMobile)
						editCourse(id, "viewshort");

				}
				else {

					$('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-graduation-cap-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбран курс для просмотра</b></div>');

				}

				if (id === 0) {

					$('#messagediv').html('<div id="emptymessage" class="gray miditxt"><i class="icon-graduation-cap-1 icon-3x gray"></i><br><b class="red">Упс.</b>&nbsp;&nbsp;<b>Не выбран курс для просмотра</b></div>');

				}

				$(".nano").nanoScroller();

				if (isMobile) {

					$content.find('table').rtResponsiveTables();

				}

			});

		/*------------*/

	}

	/*
	Вызываем при применении фильтров, чтобы начинать с 1 страницы
	 */
	function preconfigpage() {

		$('#page').val('1');
		//$current = 0;

		configpage();

	}

	function change_page(page) {

		$('#page').val(page);
		configpage();

	}

	function clear() {

		$('.ifolder').removeClass('fol_it');//.addClass('fol');
		$('.ifolder a:first').addClass('fol_it');

		$('#place').html('');

		$('#idcat').val('');
		$('#page').val('');
		$('#tag').val('');
		$('#word').val('');

	}

	/**
	 * Основная функция манипуляции с курсом
	 */
	function editCourse(id, action) {

		var str = '';
		var url = '';

		if (action === 'viewshort') {

			if($('#messagediv').html() === '')
				$('#messagediv').empty().append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка. Пожалуйста подождите...</div>');

			fetch("/modules/corpuniver/view.corpuniver.php?action=courseConstructor&id=" + id)
				.then(response => response.json())
				.then(data => {

					$('#messagediv').empty().mustache('courseConstructorTpl', data);
					localStorage.setItem("currentCource", $current);

				})
				.then(function () {

					$(".ui-layout-content .nano").nanoScroller({scroll: 'top'});

					$('#univermenu').empty();

					$('#contentdiv table tr').removeClass('current');
					$('#contentdiv table tr[data-id="' + id + '"]').addClass('current');

					if (!isMobile)
						$('div[data-action="close"]').addClass('hidden');

				})
				.then(function () {

					//$('#univermenu').html($('.univeraction').html());
					if (!isMobile)
						$(".ui-layout-content .nano").nanoScroller();

					if (isMobile)
						$('.ui-layout-east').addClass('open');

					var el = $('.togglerItems').find('i');
					var el2 = $('.togglerItems').find('span');
					var showItems = localStorage.getItem("showItems");

					if (showItems === 'yes') {

						$('.openLec').each(function () {

							var lecture = $(this).parent().parent('li').data('id');

							el.removeClass('gray').addClass('green');
							el2.html('Развернуть лекции');

							$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

							$(this).attr('title', 'Развернуть');

							$('.itemsLec[data-id="' + lecture + '"]').each(function () {

								$(this).addClass('hidden');

							});

							$(".nano").nanoScroller();

						});

					}
					else {

						el.removeClass('green').addClass('gray');
						el2.html('Свернуть лекции');

					}

				})
				.then(function () {

					/*
					$(document).off('click', '.editItem');
					$(document).off('click', '.down-lec');
					$(document).off('click', '.up-lec');
					$(document).off('click', '.deleteItem');
					$(document).off('click', '.openLec');
					*/

				})
				.catch(error => {

					//console.log(error);

					Swal.fire({
						title: 'Ошибка',
						text: error,
						type: 'error',
						showCancelButton: true
					});

				});

		}
		else if (action === 'viewshort.old') {

			url = '/modules/corpuniver/view.corpuniver.php?action=courceLectureList&id=' + id;

			$(".ui-layout-content .nano").nanoScroller({scroll: 'top'});

			$('#messagediv').empty().append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка данных. Пожалуйста подождите...</div>');

			$('#univermenu').empty();

			$('#contentdiv table tr').removeClass('current');
			$('#contentdiv table tr[data-id="' + id + '"]').addClass('current');

			$.get(url, function (data) {

				$('#messagediv').html(data);

			})
				.done(function () {

					$('#univermenu').html($('.univeraction').html());
					if (!isMobile) $(".ui-layout-content .nano").nanoScroller();

					if (isMobile) {

						$('.ui-layout-east').addClass('open');

					}

					var el = $('.togglerItems').find('i');
					var el2 = $('.togglerItems').find('span');
					var showItems = localStorage.getItem("showItems");

					if (showItems === 'yes') {

						$('.openLec').each(function () {

							var t = $(this).attr('title');
							var lecture = $(this).parent().parent('li').data('id');

							el.removeClass('gray').addClass('green');
							el2.html('Развернуть лекции');

							$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

							$(this).attr('title', 'Развернуть');

							$('.itemsLec[data-id="' + lecture + '"]').each(function () {

								$(this).addClass('hidden');

							});

							$(".nano").nanoScroller();

						});

					}
					else {

						el.removeClass('green').addClass('gray');
						el2.html('Свернуть лекции');

					}

				});

		}
		else if (action === 'view') {

			var $el = $('#subwindow');
			localStorage.setItem("currentCource", $current);

			$.post('/modules/corpuniver/view.corpuniver.php?action=courceView&id=' + id, function (data) {

				$el.empty().css({'z-index': '25', 'background': '#F5F5F5'}).addClass('open').append(data);

				$('#filelist').load('/modules/corpuniver/core.corpuniver.php?action=files&type=Course&id=' + id).append('<img src="/assets/images/loading.svg">');

			});


		}
		else if (action === 'viewdialog') {

			doLoad('/modules/corpuniver/view.corpuniver.php?action=courceViewDialog&id=' + id);

		}
		else if (action === 'edit') {

			var $ele = $('#subwindow');

			$.post('/modules/corpuniver/form.corpuniver.php?action=edit&id=' + id, function (data) {

				$ele.empty().css({'z-index': '25', 'background': '#F5F5F5'}).addClass('open').append(data);

				$('#filelist').removeClass('hidden').load('/modules/corpuniver/core.corpuniver.php?action=files&type=Course&id=' + id);

			});

		}
		else if (action === 'cat.delete') {

			url = '/modules/corpuniver/core.corpuniver.php?action=' + action + '&id=' + id;

			$.post(url, function (data) {

				doLoad('/modules/corpuniver/form.corpuniver.php?action=cat.list');

				$('.ifolder').load('/modules/corpuniver/core.corpuniver.php?action=catlist');

				const msg = Swal.mixin({
					toast: true,
					position: 'top-end',
					showConfirmButton: false,
					timer: 3000
				});

				if (data !== 'Error') {
					msg.fire(
						data,
						'',
						'success'
					);
				}

			});

		}
		else if (action === 'stat'){

			//todo: вывод статистики прохождения
			doLoad('/modules/corpuniver/view.corpuniver.php?id=' + id + '&action=courseStat');

		}
		else if (action === 'statuser'){

			//todo: вывод статистики прохождения
			doLoad('/modules/corpuniver/view.corpuniver.php?id=' + id + '&action=courseStatUser');

		}
		else doLoad('/modules/corpuniver/form.corpuniver.php?id=' + id + '&action=' + action + '&' + str);

		return false;

	}

	/**
	 * Окно прохождения курса
	 */
	function startCourse(id, header, slide) {

		var $el = $('#swindow');
		var url = '/modules/corpuniver/view.corpuniver.php?action=startCource&id=' + id + '&slide=' + slide;

		$el.find('.footer').addClass('hidden');
		$el.find('.header').html('Прохождение курса: ' + header);
		$el.find('#swUrl').val(url);
		$el.find('.body').css({'padding': '0', 'overflow-y': 'hidden'}).height('100vh');
		$el.find('.body').empty().append('<div id="loader" class="loader"><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

		$.get(url, function (data) {

			$el.find('.body').html(data);

		});

		$el.css('left', '0');

		ShowModal.fire({
			etype: 'swindow',
			action: 'opened'
		});

	}

	function closeInfo() {

		$('#subwindow').removeClass('open');

	}

	// Добавление категории
	function addCat() {

		$('#newCatCourse').removeClass('hidden');
		$('#newCatCoursebtn').addClass('hidden');

	}

	function hideAddCat() {

		$('#newCatCourse').addClass('hidden');
		$('#newCatCoursebtn').removeClass('hidden');
		$('#catNew').val('');

	}

	function edit_close() {

		$('#editfield').remove();

	}

	// Смена порядка вывода
	function orderChange(type, arr) {

		var url = '/modules/corpuniver/core.corpuniver.php?action=order.edit&type=' + type + '&arr=' + arr;

		$.post(url, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 2000);

		});

	}

	// Файлы и ресурсы материалов/курсов
	function addfile() {

		var kol = $('.filebox').size();
		var i = kol + 1;
		var htmltr = '<div id="file-' + i + '" class="filebox margbot0 margtop0" style="width:99.5%"><input name="file[]" type="file" class="file" multiple id="file[]" onchange="addfile();"><div class="delfilebox hand pt0" onclick="deleteFilebox(\'file-' + i + '\')" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

		$('#uploads').append(htmltr);

	}

	function getFileMaterial() {

		var a = '';
		var fileTitle, icon;
		var size;
		var ext;
		var errors = 0;

		$.each($('.file').get(0).files, function (ind, val) {

			a += (ind > 0) ? ' ;  ' : '';

			fileTitle = val.name.replace(/.*\\(.+)/, '$1');
			icon = getIconFile(fileTitle);
			size = this.size / 1024 / 1024;
			ext = this.name.split(".");

			var elength = ext.length;
			var carrentExt = ext[elength - 1].toLowerCase();

			if(carrentExt.toLowerCase() === 'avi'){

				Swal.fire({
					title: 'Ошибка',
					text: 'Видео в формате AVI не поддерживается браузерами. Используйте MP4',
					type: 'error',
					showCancelButton: false
				});

				errors++;

				return false;

			}

			else if(!in_array(carrentExt, extention)){

				Swal.fire({
					title: 'Ошибка',
					text: 'Файлы такого типа не разрешено загружать',
					type: 'error',
					showCancelButton: false
				});

				errors++;

				return false;

			}

			else if(parseInt(size) > parseInt(<?=$maxupload?>)){

				Swal.fire({
					title: 'Ошибка',
					text: 'Размер файла превышает максимально допустимый - <?=$maxupload?> Mb',
					type: 'error',
					showCancelButton: false
				});

				errors++;

				return false;

			}

			//console.log(this);
			//console.log(size);

			a += icon + fileTitle;

		});

		if(errors === 0) {

			var f = fileTitle.split('.');

			if ($('#name').val() === '')
				$('#name').val(f[0]);

			$('#filelist').removeClass('hidden').append('<div class="viewdiv flex-string" id="newFile">' + a + '<A href="javascript:void(0)" onclick="delFileMat();" title="Удалить"><i class="icon-cancel red"></i></A></div>');

			$('#addFilebtn').addClass('hidden');

		}

	}

	function delFileMat() {

		$('.file').val('');
		$('#newFile').remove();
		$('#addFilebtn').removeClass('hidden');

	}

	function getLinkMaterial() {

		Swal.fire({
			title: 'Добавление материала',
			text: 'Укажите ссылку на сторонний ресурс с материалом',
			input: 'text',
			inputPlaceholder: 'Введите URL',
			inputAttributes: {
				autocapitalize: 'off'
			},
			showCancelButton: false,
			confirmButtonText: 'Найти',
			showLoaderOnConfirm: true,
			preConfirm: (url) => {

				return fetch('/modules/corpuniver/core.corpuniver.php?action=resource&url=' + url)
					.then(response => {

						//console.log(response);

						if (!response.ok) {
							throw new Error(response.statusText)
						}
						return response.json()
					})
					.catch(error => {
						Swal.showValidationMessage(
							'Ошибка:' + error
						)
					})

			},
			allowOutsideClick: () => !Swal.isLoading()

		})
			.then((result) => {
				if (result.value) {

					if(!result.value.xssBlock) {

						Swal.fire({
							title: result.value.result,
							text: result.value.title,
							imageUrl: result.value.preview,
							imageAlt: 'Не удалось загрузить изображение'
						});

						if (result.value.result !== 'Error') {

							$('#name').val(result.value.title);
							$('#resource').html(result.value.title + '<A href="javascript:void(0)" onclick="delResMat();" title="Удалить"><i class="icon-cancel red"></i></A>');
							$('#addResbtn').addClass('hidden');
							$('#source').val(result.value.src);

						}

					}
					else
						Swal.fire({
							title: 'Упс. Проблема!',
							html: "Сайт не разрешает просмотр своего контента во фрейме\nПопробуйте загрузить текст из URL в разделе <b class=\"red\">Свой текст</b>",
							type: 'error'
						});

				}

			})

	}

	function delResMat() {

		$('#addResbtn').removeClass('hidden');
		$('#source').val('');
		$('#resource').html('');

	}

	function addMat() {

		var tip = $('#tip:checked').val();

		if (tip === 'file') {

			$('#filesMat').removeClass('hidden');
			$('#addRes').addClass('hidden');
			$('#cke_content').addClass('hidden');
			$('#addText').addClass('hidden');

			$('#dialog').center();

		}
		else if (tip === 'resource') {

			$('#filesMat').addClass('hidden');
			$('#addText').addClass('hidden');
			$('#addRes').removeClass('hidden');
			$('#cke_content').addClass('hidden');

			$('#dialog').center();

		}
		else if (tip === 'text') {

			$('#filesMat').addClass('hidden');
			$('#addRes').addClass('hidden');
			$('#addText').removeClass('hidden');
			$('#cke_content').removeClass('hidden');

			createEditorItem();

		}

		$('#dialog').center();

	}

	function setItemsState() {

		var showItems = localStorage.getItem("showItems");
		var el = $('.togglerItems').find('i');
		var el2 = $('.togglerItems').find('span');

		var state = (showItems === 'yes') ? 'no' : 'yes';

		localStorage.setItem("showItems", state);

		if (showItems === 'yes') {

			el.removeClass('green').addClass('gray');
			el2.html('Свернуть лекции');

		}
		else {

			el.removeClass('gray').addClass('green');
			el2.html('Развернуть лекции');

		}

		$('.openLec').each(function () {

			var t = $(this).attr('title');
			var lecture = $(this).parent().parent('li').data('id');

			$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

			t = (t === 'Свернуть') ? 'Развернуть' : 'Свернуть';
			$(this).attr('title', t);

			$('.itemsLec[data-id="' + lecture + '"]').each(function () {

				$(this).toggleClass('hidden');

			});

			$(".nano").nanoScroller();

		});

	}

	function createEditorItem() {

		editor2 = CKEDITOR.replace('content', {
			height: 500 + 'px',
			width: $('#pole').width() - 20 + 'px',
			extraPlugins: 'image2,textselection,base64image,codemirror,oembed,widget,autolink',
			filebrowserUploadUrl: '/modules/ckuploader/upload.php?type=kb',
			filebrowserImageBrowseUrl: '/modules/ckuploader/browse.php?type=kb',
			filebrowserBrowseUrl: '/modules/ckuploader/browse.php?type=kb',
			toolbar: [
				['Format', 'FontSize', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
				['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
				['TextColor', 'BGColor', '-', 'Undo', 'Redo', '-', 'Maximize', '-', 'Find', 'Replace', 'SelectAll'],
				['PasteText', 'PasteFromWord', 'Image', 'oembed', 'HorizontalRule'],
				['Blockquote', 'Outdent', 'Indent'],
				['CopyFormatting', 'RemoveFormat'],
				['-', 'Source']
			]
		});

		CKEDITOR.on("instanceReady", function (event) {

			var vh = 500 - $('.cke_top').actual('height') - $('.cke_bottom').actual('outerHeight') - 120;
			$('.cke_contents').height(vh + 'px');

			$('#dialog').center();

		});

	}

	function btn_submit() {

		var html = $('#cke_content').html();

		if (html) CKEDITOR.instances['content'].updateElement();

		$('#editItem').submit();

	}

	function delFile(id, fid, type) {

		Swal.fire({
			title: 'Удаление файла',
			text: 'Вы действительно хотите удалить файл?',
			type: 'warning',
			showCancelButton: true,
			showCloseButton: true,
			confirmButtonColor: '#3085D6',
			cancelButtonColor: '#D33',
			confirmButtonText: 'Да, удалить',
			cancelButtonText: 'Отмена'
		})
			.then((result) => {

				if (result.value) {

					refresh('filelist', '/modules/corpuniver/core.corpuniver.php?id=' + id + '&fid=' + fid + '&type=' + type + '&action=deleteFile');

					Swal.fire(
						'Удалено!',
						'Файл успешно удален.',
						'success'
					)

				}

			});

	}

	// Выбор типа задания
	$(document).on('change', '#cat', function () {

		var type = $('#cat').val();

		if (type === 'test') {

			$('.type-test').removeClass('hidden');
			$('.type-question').addClass('hidden');

			$('#question').removeClass('required');
			$('#answer').removeClass('required');

		}
		else {

			$('.type-test').addClass('hidden');
			$('#test').addClass('hidden');
			$('.type-question').removeClass('hidden');

			$('#question').addClass('required');
			$('#answer').addClass('required');

		}

	});

	// Клонируем блок .radio вместе с самим блоком
	$(document).on('click', '.addAnswer', function () {

		let $elmnt = $('#answers');
		let $html = $elmnt.find('div.radio:last').clone(true)[0].outerHTML;
		let index = 0;
		let $elm;

		// ищем элемент, выделенный ранее
		$elmnt.find('input[type="radio"]').each(function(idx, element){

			if( $(this).prop('checked') ){

				index = idx;
				$elm = element;

			}

		});

		// добавляем клон, но он берет на себя выделение
		$elmnt.append($html);

		$elmnt.find('div.radio:last').find('input[type="text"]').val('').focus();

		// поэтому возвращаем выделение ранее выделенному элементу
		$('input[type="radio"]:eq('+index+')').prop('checked', true);

	});

	/**
	 * Проверка обязательных полей перед отправкой
	 * включая чекбосы и радио-кнопки
	 * .required - input, select, textarea
	 * .multireq - блок, который оборачивает multiselect
	 * .req      - блок, который оборачивает группу radio, checkbox
	 * эти блоки будут подсвечиваться как обязательные + в них будут искаться элементы
	 * которые должны быть заполнены
	 * РАБОТАЕТ
	 */
	function checkRequiredMod(container) {

		var $req1, $req2, $req3;
		var forma;
		var $block = $(container);
		var em = 0;

		//если диалоговое окно открыто
		//то ищем id формы, т.к. полюбому мы проверяем заполненные поля в ней
		if ($block.is(':visible'))
			forma = $block.find('form').attr('id');

		//console.log(forma);

		if (forma && forma !== 'undefined') {

			var $form = $('#' + forma);

			$req1 = $form.find(".required");
			$req2 = $form.find(".req").not('.ydropDown.like-input');//.not('.like-input');
			$req3 = $form.find(".multireq");

		}
		else {

			$req1 = $(".required");
			$req2 = $(".req").not('.ydropDown.like-input');//.not('.like-input');
			$req3 = $(".multireq");

		}

		/*
		Проходим обычные поля: input, select, textarea
		*/
		$req1.removeClass("empty").css({"color": "inherit", "background": "#FFF"});
		$req1.each(function () {

			var $val = $(this).val();

			if ($val === '') {

				$(this).addClass("empty").css({"color": "#222", "background": "#FFE3D7"});
				em++;

			}

		});

		/*
		Проходим поля выбора: radio, checkbox
		*/
		$req2.removeClass("warning");
		$req2.each(function () {

			var value = $(this).find('input:checked').val();

			if (value === 'undefined' || value === undefined) {

				$(this).addClass('warning');
				em++;

			}

		});

		/*
		Проходим все поля с опцией multiselect
		*/
		$req3.removeClass("warning");
		$req3.each(function () {

			var $select = $(this).find('select');

			//кол-во выбранных элементов
			var countSel = $select.val().length;

			if (countSel === 0) {

				$(this).addClass('warning');
				em++;

			}
			else $(this).removeClass('warning');

		});

		return em;

	}

</script>

<?php
require_once $rootpath."/inc/panel.php";
flush();
?>
</body>
</html>