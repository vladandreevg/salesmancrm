/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.6          */
/* ============================ */

/**
 * Аналог app.js, предназначенный для использования в плагинах
 * @type {{BlackBerry: (function(): RegExpMatchArray), Windows: (function(): RegExpMatchArray), iOS: (function(): RegExpMatchArray), any: (function(): RegExpMatchArray), Opera: (function(): RegExpMatchArray), Android: (function(): RegExpMatchArray)}}
 */

jQuery.browser = {};
jQuery.browser.mozilla=/mozilla/.test(navigator.userAgent.toLowerCase())&&!/webkit/.test(navigator.userAgent.toLowerCase());
jQuery.browser.webkit=/webkit/.test(navigator.userAgent.toLowerCase());
jQuery.browser.opera=/opera/.test(navigator.userAgent.toLowerCase());
jQuery.browser.msie=/msie/.test(navigator.userAgent.toLowerCase());


var isMobilee = {
	Android: function () {
		return navigator.userAgent.match(/Android/i);
	},
	BlackBerry: function () {
		return navigator.userAgent.match(/BlackBerry/i);
	},
	iOS: function () {
		return navigator.userAgent.match(/iPhone|iPad|iPod/i);
	},
	Opera: function () {
		return navigator.userAgent.match(/Opera Mini/i);
	},
	Windows: function () {
		return navigator.userAgent.match(/IEMobile/i);
	},
	any: function () {
		return (isMobilee.Android() || isMobilee.BlackBerry() || isMobilee.iOS() || isMobilee.Opera() || isMobilee.Windows());
	}
};
var isMace = {

	iOS: function () {
		return navigator.userAgent.match(/Macintosh/i);
	}

};
var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
var isSafari = /Safari/.test(navigator.userAgent) && /Apple Computer/.test(navigator.vendor);

//Вывод типа последней активности в колонке истории в списках клиентов, контактов, сделок
var showHistTip = 'yes';//'yes';
var isMobile = false;
var isPad = false;
var isMac = false;

//console.log(navigator);

if (isMobilee.any() || $(window).width() < 767) {
	isMobile = true;
	isPad = false;
}
if ($(window).width() > 767) {
	isMobile = false;
	isPad = true;
}
if ($(window).width() > 1024) isPad = false;

if (isMace.iOS()) isMac = true;

/**
 * подключает javascript файл и выполняет его
 * заносит название файла в реестр подключенных,
 * дабы не дублировать
 */
var javascripts = [];
var $display = '';

/**
 * Признак нахождения в карточке
 */
var isCard = false;
var tipCard = '';
var idCard = 0;

/**
 * Период проверки почты
 * По умолчанию = 10 мин
 */
var $yperiod = 10 * 60000;

/**
 * Ключ Дадата
 */
var $dadata = '';

/**
 * Открытие карточек Клиента, Контакта, Сделки
 * во фрейме
 * @type {number}
 */
var oF = parseInt( localStorage.getItem("openCardInFrame") );
var openFrame = (oF === 1);

var vO = parseInt( localStorage.getItem("viewAsOpen") );
var viewAsOpen = (vO === 1);

/**
 * Основные элементы интерфейса
 */
var $elcenter = $('.ui-layout-center');
var $elwest = $('.ui-layout-west');
var $eleast = $('.ui-layout-east');
var $elnorth = $('.ui-layout-north');

var isCtrl = false;

//Даты и периоды
var period = {
	all: ['', ''],
	yestoday: [moment().subtract(1, 'days').format('YYYY-MM-DD'), moment().subtract(1, 'days').format('YYYY-MM-DD')],
	today: [moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD')],
	tomorrow: [moment().add(1, 'days').format('YYYY-MM-DD'), moment().add(1, 'days').format('YYYY-MM-DD')],
	calendarweekprev: [moment().subtract(1, 'week').weekday(1).format('YYYY-MM-DD'), moment().subtract(1, 'week').weekday(7).format('YYYY-MM-DD')],
	calendarweek: [moment().weekday(1).format('YYYY-MM-DD'), moment().weekday(7).format('YYYY-MM-DD')],
	calendarweeknext: [moment().add(1, 'week').weekday(1).format('YYYY-MM-DD'), moment().add(1, 'week').weekday(7).format('YYYY-MM-DD')],
	monthprev: [moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD')],
	month: [moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD')],
	monthnext: [moment().add(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().add(1, 'months').endOf('month').format('YYYY-MM-DD')],
	quartprev: [moment().subtract(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().subtract(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	quart: [moment().startOf('quarter').format('YYYY-MM-DD'), moment().endOf('quarter').format('YYYY-MM-DD')],
	quartnext: [moment().add(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().add(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	year: [moment().startOf('year').format('YYYY-MM-DD'), moment().endOf('year').format('YYYY-MM-DD')],
	yearnext: [moment().add(1, 'year').startOf('year').format('YYYY-MM-DD'), moment().add(1, 'year').endOf('year').format('YYYY-MM-DD')]
};

// блокировка ошибок
window.onerror = blockError;

/**
 * Подключение js-файла
 * @param path
 * @returns {boolean}
 */
function includeJS(path) {

	for (var i = 0; i < javascripts.length; i++) {
		if (path === javascripts[i]) {
			return false;
		}
	}

	javascripts.push(path);
	$.ajax({
		url: path,
		dataType: "script",// при типе script, JS сам инклюдится и воспроизводится
		async: false
	});

}

/**
 * Подключение css-файлов. Можно списком с разделителем - запятая
 * Пример: files = "css/one.css,css/two.css"
 * Должен быть указан полный путь к файлу от корня папки
 * @param files
 */
function includeCSS(files) {

	var mass = files.split(",");

	for (var i = 0; i < mass.length; i++) {
		var a = document.createElement("link");
		a.rel = "stylesheet";
		a.href = mass[i];
		document.getElementsByTagName("head")[0].appendChild(a)
	}

}

$(function () {

	if (isMobile) {

		includeJS('/assets/js/smMobileTable.js');
		includeJS('/assets/js/jquery/jquery.scrollTo.js');

	}
	if (!isMobile) {

		includeJS("/assets/js/smTableColumnFixer.js");

	}

	getScreenSize();

	$('.close').on('click', function () {
		new DClose();
	});

	$("#dialog").draggable({handle: ".zagolovok", cursor: "move", opacity: "0.85", containment: "document"});

	$('.selectBox')
		.on('mouseover', function () {
			$(this).animate({'max-height': 100}, 50).addClass('ha');
		})
		.on('mouseleave', function () {
			$(this).animate({'max-height': 30}, 50).removeClass('ha');
		});

	$(document).on('keydown', function (e) {

		var keycode = e.originalEvent.key;

		//console.log(keycode)

		if (keycode === 'Escape') { // escape, close box, esc

			new DClose();
			$('.popmenu.nothide').removeClass('open');

		}
		if (keycode === 'Control') {
			isCtrl = true;
		}

	});
	$(document).on('keyup', function () {

		isCtrl = false;

	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу
		var div = $(".submenu"); // тут указываем ID элемента
		if (!div.is(e.target) // если клик был не по нашему блоку
			&& div.has(e.target).length === 0) { // и не по его дочерним элементам
			$('.submenu').css('display', 'none'); // скрываем его
		}
	});

	$('.flyit').each(function () {

		$(this).find('.yselectBox').detach().appendTo('#flyitbox');

	});
	$('.ydropString').each(function () {

		var txt = $.trim(striptags($(this).find('label').text()).replace(/<[^p].*?>/g, ''));

		$(this).prop("title", txt);

	});

	$(document).on('click', '.ydropDown', function () {

		//скрываем остальные элементы
		var $other = $('.ydropDown.open').not(this);

		$other.find(".yselectBox").each(function () {

			$(this).hide();
			$other.find('i.icon-angle-up').removeClass('icon-angle-up').addClass('icon-angle-down');
			$other.find(".action").addClass('hidden');

		});

		//если элемент не закреплен
		if (!$(this).hasClass('flyit')) {

			var $el = $(".yselectBox", this);

			if (!$(this).hasClass('dWidth')) $el.css('width', $(this).actual('outerWidth'));
			$el.toggle();

			if ($(this).hasClass('open')) {

				$(this).removeClass('open');
				$el.removeClass('open');

			}
			else {

				$(this).addClass('open');
				$el.addClass('open');

			}

			if ($el.css('display') === 'none') $(this).find('i.icon-angle-up').removeClass('icon-angle-up').addClass('icon-angle-down');
			else $(this).find('i.icon-angle-down').removeClass('icon-angle-down').addClass('icon-angle-up');

		}
		else {

			var element = $(this).data('id');
			var offset = $(this).offset();
			var width = $(this).outerWidth();
			var height = $(this).outerHeight() + 1;

			if ($(this).hasClass('open')) {

				$(this).removeClass('open');
				$('.yselectBox[data-id="' + element + '"]').removeClass('open');

			}
			else {

				$(this).addClass('open');
				$('.yselectBox[data-id="' + element + '"]').addClass('open');

			}

			$('.yselectBox.open').not('[data-id="' + element + '"]').each(function () {

				var el = $(this).data('id');
				var $elm = $('.ydropDown[data-id="' + el + '"]');

				$(this).removeClass('open').hide();
				$elm.removeClass('open');

				//если элемент закрыт
				if ($elm.find(".yselectBox").hasClass('hidden')) {

					$(this).find('i.icon-angle-up').removeClass('icon-angle-up').addClass('icon-angle-down');
					//$elm.removeClass('open');

				}
				else {

					//$elm.addClass('open');
					//$(this).addClass('open');
					$(this).find('i.icon-angle-down').removeClass('icon-angle-down').addClass('icon-angle-up');

				}

				//$('.ydropDown[data-id="' + el + '"]').find('i.icon-angle-down').removeClass('icon-angle-down').addClass('icon-angle-up');

			});

			$('.' + element).css({
				"width": width + "px",
				"top": (offset.top + height) + "px",
				"left": (offset.left) + "px",
				"z-index": "1000",
				"position": "fixed"
			}).toggle();

		}

		$(".action", this).toggleClass('hidden');

	});
	$(document).on('click', '.ydropString:not(.yRadio)', function () {

		var ebox;

		if (!$(this).closest('.yselectBox').hasClass('fly')) {

			ebox = $(this).parents('.ydropDown');
			var chk = $(this).parent('.yselectBox').find('input[type=checkbox]:checked').length;
			var $f = $(this).parents('.ydropDown').find('.ydropCount');
			var a = $f.html();

			$f.html(chk + ' '+ $language.all.Selected);

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			ebox = $('.ydropDown[data-id="' + element + '"]');

			var $f2 = ebox.find('.ydropCount');
			var ch2 = $(this).closest('.yselectBox').find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' '+ $language.all.Selected);

		}

		setTimeout(function () {

			$('.yselectBox[data-id="' + element + '"]').show();
			ebox.find('i.icon-angle-down').removeClass('icon-angle-down').addClass('icon-angle-up');
			ebox.find('.action').removeClass('hidden');

		}, 1);

		//return false;

	});
	$(document).on('click', '.ydropString.yRadio', function () {

		var rak;
		var $fr;
		var ebox;

		if (!$(this).closest('.yselectBox').hasClass('fly')) {

			ebox = $(this).parents('.ydropDown');
			rak = $(this).find('input[type=radio]:checked').data('title');
			$fr = $(this).parents('.ydropDown').find('.ydropText');

			$fr.html(rak).prop('title', rak);

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			ebox = $('.ydropDown[data-id="' + element + '"]');
			rak = $(this).closest('.yselectBox').find('input[type=radio]:checked').data('title');
			$fr = ebox.find('.ydropText');

			$fr.html(rak).prop('title', rak);

		}

		$(this).addClass('bluebg-sub');
		$(this).closest('.yselectBox').find('.ydropString').not(this).removeClass('bluebg-sub');

		setTimeout(function () {

			var $ee = ebox.find('.yselectBox');

			if ($ee.is(':visible')) $ee.hide();
			ebox.find('i.icon-angle-up').addClass('icon-angle-down').removeClass('icon-angle-up');

		}, 11);

	});
	$(document).on('click', '.ySelectAll', function () {

		var $elm = $(this).closest('.yselectBox');
		var $box = $(this).closest('.ydropDown');

		if (!$elm.hasClass('fly')) {

			$elm.find('input[type=checkbox]').prop('checked', true);

			var $f = $box.find('.ydropCount');
			var ch = $box.find('input[type=checkbox]:checked').length;

			$f.html(ch + ' ' + $language.all.Selected);

			setTimeout(function () {
				$elm.show();
			}, 10);

		}
		else {

			var element = $elm.data('id');
			$box = $('.ydropDown[data-id="' + element + '"]');

			$('.yselectBox[data-id="' + element + '"]').find('input[type=checkbox]').prop('checked', true);

			var $f2 = $box.find('.ydropCount');
			var ch2 = $elm.find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' '+ $language.all.Selected);

			setTimeout(function () {
				$('.yselectBox[data-id="' + element + '"]').show();
				$box.find('i.icon-angle-down').toggleClass('icon-angle-down icon-angle-up');
				$box.find('.action').removeClass('hidden');
			}, 10);

		}

		//console.log( $box.find('.yDoit').is('div') );

		if (typeof configpage === 'function' && !$box.find('.yDoit').is('div')) configpage();

		//выполняем функцию
		var fnc = $elm.data('func');
		if (fnc)
			eval(fnc)();

		return false;

	});
	$(document).on('click', '.yunSelect', function () {

		var $elm = $(this).closest('.ydropDown');
		var $box = $(this).closest('.yselectBox');

		if (!$box.hasClass('fly')) {

			var chk = $box.find('input[type=checkbox]:checked').prop('checked', false);
			var $f = $elm.find('.ydropCount');

			$box.find('input[type=checkbox]:checked').prop('checked', false);

			$f.html('0 ' + $language.all.Selected);

			setTimeout(function () {
				$(this).closest('.yselectBox').show();
			}, 10);

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			$box = $('.ydropDown[data-id="' + element + '"]');

			$('.yselectBox[data-id="' + element + '"]').find('input[type=checkbox]').prop('checked', false);

			var $f2 = $box.find('.ydropCount');
			var ch2 = $box.find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' '+ $language.all.Selected);

			setTimeout(function () {
				$('.yselectBox[data-id="' + element + '"]').show();
				$box.find('i.icon-angle-up').addClass('icon-angle-down').removeClass('icon-angle-up');
				$box.find('.action').removeClass('hidden');
			}, 10);

		}

		if (typeof configpage === 'function' && !$box.find('.yDoit').is('div') && !isCard) configpage();

		//выполняем функцию
		var fnc = $box.data('func');
		if (fnc)
			eval(fnc)();

		return false;

	});
	$(document).mouseup(function (e) { // событие клика по веб-документу

		var div = $(".ydropDown.open"); // тут указываем ID элемента

		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам

			$(".yselectBox.open").removeClass('open').hide();

			div.find(".action").addClass('hidden');
			div.removeClass('open');
			div.find('i.icon-angle-up').addClass('icon-angle-down').removeClass('icon-angle-up');

		}

	});

	$(document).on('click', '.tagsmenuToggler', function () {

		$('.tagsmenu').not(this).addClass('hidden');

		if ($(this).next().hasClass('.tagsmenu'))
			$(this).next('.tagsmenu').toggleClass('hidden');

		else
			$(this).closest('div').find('.tagsmenu').toggleClass('hidden');

		$(this).find('#mapii').toggleClass('icon-angle-down icon-angle-up');

	});
	$(document).mouseup(function (e) { // событие клика по веб-документу

		//console.log(e);

		var div = $(".tagsmenuToggler"); // тут указываем ID элемента
		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам
			$(".tagsmenu", this).addClass('hidden');
			div.find('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');
		}
	});

	$(document).on('click', '.pop', function () {

		if ($(this).hasClass('donthidee') === false) {

			$(".popmenu", this).toggle();
			$(".popmenu-top", this).toggle();

		}

	});
	$(".pop").not('.donthidee').mouseleave(function () { // событие клика по веб-документу

		$(".popmenu", this).hide();
		$(".popmenu-top", this).hide();

	});
	$(document).mouseup(function (e) { // событие клика по веб-документу

		var div = $(".popmenu").not('.open'); // тут указываем ID элемента

		//console.log(e);

		if (!div.is(e.target) // если клик был не по нашему блоку
			&& div.has(e.target).length === 0) { // и не по его дочерним элементам

			if (e.target.id !== 'search' && e.target.className !== 'popbody') {

				//div.hide();
				$('.popmenu').not('.nothide').removeClass('open');
				$(".popmenu-top", this).hide();

			}
			else return true;

		}

	});

	$(document).mouseup(function (e) { // событие клика по веб-документу
		var div = $("#subwindow"); // тут указываем ID элемента
		if (!div.is(e.target) // если клик был не по нашему блоку
			&& div.has(e.target).length === 0) { // и не по его дочерним элементам
			$('#subwindow').removeClass('open').empty();
		}
	});

	$("#swStart").datepicker({
		dateFormat: 'yy-mm-dd',
		firstDay: 1,
		dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
		changeMonth: true,
		changeYear: true
	});
	$("#swEnd").datepicker({
		dateFormat: 'yy-mm-dd',
		firstDay: 1,
		dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
		changeMonth: true,
		changeYear: true
	});

	$(document).on('click', '.pop', function () {

		var element = $(this).find('.popmenu');
		var wH = $(window).height();
		var wW = $(window).width();
		var eTop = wH - Math.round($(this).offset().top + 30);
		var dId = $(this).data('id');
		var hPop = element.height();
		var hPopB = 0;
		var hBody = 0;

		if (!isMobile) {

			if (element.find('.popblock').is('div')) hPopB = element.find('.popblock').actual('height');

			hBody = hPop - element.find('.pophead').actual('height') - hPopB;

			element.find('.popbody').css({"height": hBody + "px"});

		}
		else {

			element.css({'height': wH + 'px', 'width': wW + 'px', 'left': '0', 'top': '0'});
			element.find('.left-triangle-before').remove();
			element.find('.left-triangle-after').remove();

			hBody = wH - element.find('.pophead').outerHeight() - element.find('.popblock').outerHeight() - 5;

			element.find('.popbody').css({"height": hBody + "px", "max-height": hBody + "px"});

		}

		if (!element.hasClass('open')) {

			$(this).closest('ul').find('.popmenu').removeClass('open');

			element.toggleClass('open');

			element.find('.left-triangle-before').css({"bottom": eTop + "px"});
			element.find('.left-triangle-after').css({"bottom": eTop + "px"});

			if (dId === 'todo') {

				element.find('.popbody').css({"height": hBody + "px"});
				if (!isMobile) $(".popbody").find('.nano').nanoScroller();

			}

		}

	});

	$('div.leftpop').bind('mouseover', function () {

		var element = $(this).find('.popmenu');

		element.addClass('open');

	});
	$(document).on('click', '.popcloser', function () {

		if ($(this).closest('li').data('id') === 'search') {

			$(this).closest('.popmenu').removeClass('open').css({"display": "none"});

			var el = $('li.lpop');

			el.off("click", popsearchhandler);

			setTimeout(function () {
				el.on("click", popsearchhandler);
			}, 100);

		}
		else {

			$(this).closest('.popmenu').removeClass('open').css({"display": "none"});

		}

		if (isMobile)
			$('#rmenu').css({'z-index' : '28'});

		return false;

	});

	if (isMobile || $(window).width() < 500) {

		$('input.datum').each(function () {
			this.setAttribute('type', 'date');
		});
		$('input.inputdate').each(function () {
			this.setAttribute('type', 'date');
		});

	}

	//расставляем периоды, если у селекта не установлен признак data-select="false"
	$('#period').each(function () {

		var $auto = $(this).data('select');

		//console.log($auto);

		if ($auto !== false) {

			var $goal = $(this).data('goal');
			var $elm = $('#' + $goal);

			$elm.append($elm);
			$elm.find('.dstart').val(period.calendarweek[0]);
			$elm.find('.dend').val(period.calendarweek[1]);

			$('option[data-period="calendarweek"]', this).prop('selected', true);

		}

	});

});

$(window).on('resize', function () {

	if (this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function () {
		$(this).trigger('resizeEnd');
	}, 500);

	if ($('#dialog').is(':visible')) {

		$('#dialog').center();
		$('.dialog-preloader').center();
		$('#dialog_container').css('height', $(window).height());

	}

	$('.ui-layout-north').css("position", "absolute");

});
$(window).on('resizeend', 200, function () {

	if (isMobilee.any() || $(window).width() < 767) {
		isMobile = true;
		isPad = false;
	}
	if ($(window).width() > 767) {
		isMobile = false;
		isPad = true;
	}
	if ($(window).width() > 1024) isPad = false;

	getScreenSize();

});
$(window).load(function () {



});

$(document).on('click', '.toggler', function () {

	var id = $(this).data('id');

	$(this).closest('div').find('#' + id).toggleClass('hidden');

	if ($('#' + id).hasClass('hidden')) localStorage.setItem(id, '');
	else localStorage.setItem(id, 'show');

	if (!isMobile) $('.nano').nanoScroller();

});
$(document).on('click', '.togglerbox', function () {

	var id = $(this).data('id');

	$(this).parents('div').find('#' + id).toggleClass('hidden');
	$(this).closest('div').find('#mapic').toggleClass('icon-angle-up icon-angle-down');

	if ($('#dialog').is(':visible'))
		$('#dialog').center();

});
$(document).on('click', '.togglerfly', function () {

	var id = $(this).data('id');

	$('#' + id).toggleClass('hidden');

});
$(document).on('click', '#che', function checkAll() {

	if ($(this).prop('checked')) {

		$('#contentdiv').find('.mc').not(':disabled').prop('checked', true);

	}
	else {

		$('#contentdiv').find('.mc').not(':disabled').prop('checked', false);

	}

});
$(document).on('click', '.variants .list span', function () {
	var st = $(this).html();
	$(this).closest('.variants').find('input').val(st);
});
$(document).on('click', '.cardResizer', function () {

	var pozi = $(this).data('pozi');
	var h = $(this).prev('.cardBlock').data('height');

	if (pozi === 'close') {
		$(this).data("pozi", "open");
		$(this).prev('.cardBlock').css('height', 'auto');
	}
	else {
		$(this).data("pozi", "close");
		$(this).prev('.cardBlock').css('height', h + 'px');
	}
	$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

});
$(document).on('click', '.adder', function () {

	var block = $(this).data('block');
	var main = $(this).data('main');

	var el = $('#' + main);

	el.find('.' + block + ':last').clone(true).appendTo('#' + main);

	if (el.find('.' + block + ':first').find('.phone').hasClass('required')) {
		el.find('.' + block).not(':last').find('.phone').addClass('required');
		el.find('.' + block).not(':last').find('.remover').removeClass('hidden');
	}

	el.find('.' + block).not(':last').find('.adder').remove();
	el.find('.' + block + ':last').find('.phone').removeClass('required').val('');

	//el.find('.' + block + ':last').find('.remover').remove();

});
$(document).on('click', '.remover', function () {

	var main = $(this).data('parent');
	var main2 = $(this).parent('.phoneBlock');
	var block = main2.find('.adder').data('block');

	var el = $('#' + main);

	var count = el.find('.phone').length;
	var count2 = main2.find('.adder').length;
	var req = 0;

	if (el.find('.phoneBlock:first-of-type').find('.phone').hasClass('required')) req = 1;

	if (count > 0 && count2 === 0) $(this).parent('.phoneBlock').remove();
	else {

		main2.find('input').val('');
		if (el.find('.' + block + ':first').find('.phone').hasClass('required')) main2.find('input').addClass('required');

	}

	var newcount = el.find('.phone').length;

	if (newcount === 1 && req === 1) el.find('.phone').addClass('required');
	else el.find('.' + block + ':last').find('.phone').removeClass('required');

});
$(document).on('click', '.closer', function () {

	var $el = $('#swindow');

	$el.css('left', '110vw');
	$el.find('.header').html('Header');
	$el.find('.body').html('Body');

	ShowModal.fire({
		etype: 'swindow',
		action: 'closed'
	});

});
$(document).on('click', '.clearinputs', function () {
	$(this).parents('.cleared').find('input').val('');


	//выполняем функцию
	var fnc = $(this).data('func');
	if (fnc)
		eval(fnc)();

});

/**
 * Управление iframe
 */
$(document).off("click", '#ctitle #close');
$(document).on("click", '#ctitle #close', function () {

	if (isFrame)
		parent.$(parent.document).trigger('iframeClose');

	else
		window.close();

});

$(document).off("click", '.smframe--close');
$(document).on("click", '.smframe--close', function (e) {

	parent.$(parent.document).trigger('iframeClose');

	e.preventDefault();
	e.stopPropagation();

});

$(document).on('click', '.smframe--url', function (e) {

	var url = e.currentTarget.dataset.url;

	window.open(url);

	parent.$(parent.document).trigger('iframeClose');

});

$(document).off("iframeClose");
$(document).on("iframeClose", function (e) {

	$('.smframe--container').css({"left": "110vw"});
	$('iframe#smframe').attr('src', '');
	$('.smframe--url').data('url', '');

	e.preventDefault();
	e.stopPropagation();

	return false;

});

/**
 * Мобильная версия меню
 */
$(document).on('click', '.menuToggler', function () {

	$('.menu--mobile').toggleClass('hidden');
	$(this).find('i').toggleClass('icon-menu icon-cancel');

});
$(document).on('click', '.navlink', function () {

	$('.menu--mobile').addClass('hidden');
	$('.menuToggler').find('i').toggleClass('icon-menu icon-cancel');

	return true

});

$(document).on('click', '.showpass', function (e) {

	var input = $(this).siblings('input[data-type="password"]');
	var prop = input.prop('type');

	//console.log(prop);

	if (prop === 'password') input.prop('type', 'text');
	else input.prop('type', 'password');

	$('.showpass').find('i').toggleClass('icon-eye icon-eye-off');

});
$(document).on('mouseleave', '.showpass', function (e) {

	var input = $(this).siblings('input[data-type="password"]');
	var prop = input.prop('type');

	if (prop === 'text') {

		input.prop('type', 'password');
		$('.showpass').find('i').toggleClass('icon-eye-off icon-eye');

	}

});

$(document).on('click', '.popblock:not(.disabled)', function () {

	$('.popblock').not(this).removeClass('open');

	$(this).addClass('open');
	$(this).find('#mapii').toggleClass('icon-angle-down icon-angle-up');

});
$(document).on('mouseup', function (e) { // событие клика по веб-документу

	//console.log(e);

	// тут указываем элемент
	var $elm = $(e.target).closest(".popblock-menu");
	var $trgt = $('a[data-tip="filter"]');

	//console.log($elm.length);

	// и не по его дочерним элементам
	if ($elm.length === 0/* && e.target.attributes[0].type !== "text"*/) {

		if (e.target.type === "text")
			return false;

		//скрываем все остальные меню
		$(".popblock-menu:not(.not-hide)").each(function () {

			$(this).closest('.popblock').removeClass('open');
			$(this).find('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');

		});

	}

});

/**
 * Показ fullscreen-модального окна
 * Применяется для экспресс-отчетов, списка дублей, анкет по сделкам
 * @param url
 * @param header
 */
function getSwindow(url, header) {

	var $el = $('#swindow');
	var str = $('#swForm').serialize();

	$('.period').removeClass('active');
	$('.period[data-period="month"]').addClass('active');

	$el.find('.footer').removeClass('hidden');
	$el.find('.header').html(header);
	$el.find('#swUrl').val(url);
	$el.find('.body').empty().append('<div id="loader" class="loader"><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

	$.get(url, 'period=month', function (data) {

		$el.find('.body').html(data);

		/**
		 * В Хроме сочетание ellipsis и &nbsp; работают не корректно
		 */
		if(isChrome){

			$el.find('.ellipsis').find('a').each(function(){

				var txt = $(this).html().replace('&nbsp;','');
				$(this).html(txt);

			});

		}

	});

	$el.css('left', '0');

	ShowModal.fire({
		etype: 'swindow',
		action: 'opened'
	});

}

$(document).on('click', '#swindow a.period', function () {

	var urli = $('#swUrl').val();
	var period = $(this).data('period');
	var $el = $('#swindow');
	var str = $('#swForm').serialize() + '&period=' + period;

	$('#swPeriod').val(period);

	$el.find('.body').empty().append('<div id="loader" class="loader"><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

	$('.period').removeClass('active');
	$(this).addClass('active');

	$.ajax({
		type: "GET",
		url: urli,
		data: str,
		success: function (viewData) {

			$el.find('.body').html(viewData);

			/**
			 * В Хроме сочетание ellipsis и &nbsp; работают не корректно
			 */
			if(isChrome){

				$el.find('.ellipsis').find('a').each(function(){

					var txt = $(this).html().replace('&nbsp;','  ');
					$(this).html(txt);

				});

			}

		}
	});

});

$(document).on('change', 'select[data-action="period"]', function (e) {

	var $period = $('option:selected', this).data('period');
	var $goal = $(this).data('goal');
	var $elm = $('#' + $goal);
	var $func = $(this).data('js');

	if ($period !== undefined) {

		$elm.find('.dstart').val(period[$period][0]);
		$elm.find('.dend').val(period[$period][1]);

	}
	else {

		$elm.find('.dstart').val('');
		$elm.find('.dend').val('');

	}

	if ($func)
		eval($func)();

	e.preventDefault();
	e.stopPropagation();

	return false;

});

// удаление неразрывных пробелов в хроме
function clearNBSP(){

	//console.log(isChrome);

	if(isChrome){

		$('#contentdiv').find('.ellipsis').find('a').each(function(){

			var txt = $(this).html().replace('&nbsp;','');
			$(this).html(txt);

			//console.log(txt);

		});

	}

}

function emptySelect() {

	$('.yselectBox').find('input[type=checkbox]:checked').prop('checked', false);

	var $f = $('.ydropDown').find('.ydropCount');
	$f.html('0 ' + $language.all.Selected);

	window.location.hash = '';

	try {
		configpage();
	}
	catch (e) {
	}

	$('select:first').prop('selected', true);

}

function popmenu() {

	if ($(this, '.popmenu').css('display') === 'none') {

		$(this, '.popmenu').css('display', 'block');

	}
	else {
		$(this, '.popmenu').css('display', 'none');
	}
	return false;
}

/**
 * Установка выбранного значения
 */
function yDropSelectSetText() {

	$('.ydropDown').each(function () {

		var count = $(this).find('input[type="radio"]:checked').size();

		if (count > 0) {

			$(this).find('input[type="radio"]:checked').trigger('click');

		}
		else {

			$(this).find('input[type="radio"]:first').trigger('click');
			$(this).find('.ydropString.yRadio:first').trigger('click');

		}

	});

}

/**
 * Выбор/Снятие выбора всех чекбоксов в списках
 */
function checkb() {

	if ($('#che').attr('checked')) {
		$('.mc').attr('checked', true);
	}
	else {
		$('.mc').attr('checked', false);
	}

}

/**
 * Обновляет содержимое элемента #element с загрузкой по url
 * Может применяться для содержимого окна #dialog
 * @param element
 * @param url
 * @returns {boolean}
 */
function refresh(element, url) {

	var $dialog = $('#dialog');
	var $message = $('#message');

	$message.empty().css('display', 'block').append('<div id="loader" class="loader"><img src="/assets/images/loader.gif"> Загрузка данных...</div>');

	$.get(url, function (data) {

		$dialog.center();
		$message.empty().css('display', 'none');

		$('#' + element).html(data);

	}).success(function () {
		$dialog.find("a.button:contains('Отмена')").addClass('bcancel').prepend('<i class="icon-cancel-circled"></i>');
		$dialog.find("a.button:contains('Закрыть')").addClass('bcancel').prepend('<i class="icon-cancel-circled"></i>');
		$dialog.find("a.button:contains('Сохранить')").prepend('<i class="icon-ok"></i>');
		$dialog.find("a.button:contains('Добавить')").prepend('<i class="icon-plus-circled-1"></i>');
	});


	return false;
}

/**
 * Обновляет содержимое элемента #element с загрузкой по url
 * Применимость: списки, в которых есть чекбокс, выделяющий все чекбоксы
 * @param element
 * @param url
 * @returns {boolean}
 */
function reLoad(element, url) {

	$.get(url, function (data) {

		$('#' + element).empty().html(data);

	})
		.success(function () {

		})
		.complete(function () {

			checkB();

			$('#ch').attr('checked', false);
			$('#rez').empty();

		});

	$(".popmenu").hide();
	$(".popmenu-top").hide();

	if (!isMobile) $(".nano").nanoScroller();

	return false;

}

/**
 * Открытие url в модальном окне. Используется для вызова форм
 * @param url
 * @returns {boolean}
 */
function doLoad(url) {

	var $dialog = $('#dialog');
	var $resultdiv = $('#resultdiv');
	var $container = $('#dialog_container');
	var $preloader = $('.dialog-preloader');

	$container.css('height', $(window).height());
	$dialog.css('width', '500px').css('height', 'unset').css('display', 'none');
	$container.css('display', 'block');
	$preloader.center().css('display', 'block');
	$resultdiv.css('height','initial');

	if(url !== undefined) {

		$.ajax({
			type: "GET",
			url: url,
			success: function (data) {

				$resultdiv.empty().html(data);

				doLoadAfter();

				ShowModal.fire({
					etype: 'dialog',
					action: action
				});

			},
			statusCode: {
				404: function () {
					DClose();
					Swal.fire({
						title: "Ошибка 404: Страница не найдена!",
						type: "warning"
					});
				},
				500: function () {
					DClose();
					Swal.fire({
						title: "Ошибка 500: Ошибка сервера!",
						type: "error"
					});
				}
			}
		});

	}

	$(".popmenu").hide();
	$(".popmenu-top").hide();

	$('#editfield').remove();

	return false;
}

/**
 * Постобработка, после загрузки форм
 * Позволяет открыть любую страницу в модальном окне, в т.ч. с обработкой
 * При этом содержимое окна загружается отдельно
 * в отличие от функции doLoad
 *
 * @example
 * ```js
 *
 * let id = $(this).data('id');
 * let type = $(this).data('type');
 * let url = "modules/corpuniver/view.corpuniver.php?action=slide&preview=yes&type=" + type + "&id=" + id;
 *
 * doLoad();
 *
 * $.get(url, function (data) {
 *      $('#resultdiv').empty().html(data);
 * })
 *      .complete(function () {
 *          $('#dialog').center();
 *          doLoadAfter();
 *      });
 *
 * ```
 */
function doLoadAfter(){

	var $dialog    = $('#dialog');
	var $resultdiv = $('#resultdiv');
	var $container = $('#dialog_container');
	var $preloader = $('.dialog-preloader');

	$dialog.find("a.button:contains('Отмена')").addClass('bcancel').prepend('<i class="icon-cancel-circled"></i>');
	$dialog.find("a.button:contains('Закрыть')").addClass('bcancel').prepend('<i class="icon-cancel-circled"></i>');
	$dialog.find("a.button:contains('Сохранить')").prepend('<i class="icon-ok"></i>');
	$dialog.find("a.button:contains('Добавить')").prepend('<i class="icon-plus-circled-1"></i>');

	$preloader.css('display', 'none');

	$resultdiv.find('select').not('.multiselect').each(function () {
		$(this).wrap("<span class='select'></span>");
	});
	$('#contentdiv').find('select').not('.multiselect').each(function () {

		if ($(this).closest('span').hasClass('select') === false) $(this).wrap("<span class='select'></span>");

	});

	if (!isChrome) {

		$('input[type="date"]').each(function () {
			this.setAttribute('readonly', 'readonly');
		});
		$('input[type="time"]').each(function () {
			this.setAttribute('readonly', 'readonly');
		});
		$('.inputdatetime').each(function () {
			this.setAttribute('readonly', 'readonly');
		});
		$('.inputdate').each(function () {
			//this.setAttribute('readonly', 'readonly');
		});

	}

	if (isMobile && isChrome) {

		//переформатируем дату для хром-подобных браузеров
		//в мобильной версии
		$('.inputdatetime').each(function () {

			var val = $(this).val().replace(" ", "T");
			$(this).val(val);

			/*
			console.log(val);

			this.setAttribute('readonly', 'readonly');
			*/

			if(val === '')
				$(this).val( moment().format('YYYY-MM-DDTHH:mm') );

		});

	}

	//console.log(isMobile);

	if (isMobile || $(window).width() < 500) {

		$dialog.find('form').find('#formtabs').append('<div style="height: 200px" class="block wp100">&nbsp;</div>');

		$dialog.css({
			'position': 'unset',
			'margin': '0 auto',
			'margin-bottom': '50px',
			'width': '100vw',
			'height': '100vh'
		});
		$container.css('overflow-y', 'auto');

		//if(!isChrome) {

		$('input.datum').each(function () {
			this.setAttribute('type', 'date');
		});
		$('input.inputdate').each(function () {
			this.setAttribute('type', 'date');
		});
		$('input.inputdatetime').each(function () {
			this.setAttribute('type', 'datetime-local');
		});

		//}

		$dialog.on('focus', 'input', function () {

			$('#formtabs').scrollTo($(this), 500);

		});
		$dialog.on('focus', 'textarea', function () {

			$('#formtabs').scrollTo($(this), 500);

		});

	}
	if (!isMobile) {

		$('input[type="date"]').each(function () {
			this.setAttribute('type', 'text');
		});
		$('input[type="time"]').each(function () {
			this.setAttribute('type', 'text');
		});
		$('input[type="datetime"]').each(function () {
			this.setAttribute('type', 'text');
		});

	}

	if (typeof doLoadCallback === 'function') doLoadCallback();

	$dialog.css('display', 'block').center();

	if ($('#isCard').val() === 'yes') {

		$('body').css({'overflow-y': 'hidden'});
		$('html').css({'overflow-y': 'hidden'});

	}

	/**
	 * Dadata. Автозаполнение адресов
	 */
	$('input[data-type="address"]').each(function () {

		$(this).suggestions({
			token: $dadata,
			type: "ADDRESS",
			count: 5,
			formatResult: formatResult,
			formatSelected: formatSelected,
			onSelect: function (suggestion) {

				//console.log(suggestion);

			},
			addon: "clear",
			geoLocation: true
		});

	});

	/*
	$('input[data-type="name"]').each(function() {

		$(this).suggestions({
			token: $dadata,
			type: "NAME",
			count: 5,
			onSelect: function (suggestion) {

				console.log(suggestion);

			}
		});

	});
	*/

	/**
	 * Реквизиты
	 */
	$('input[data-type="inn"]').each(function () {

		$(this).suggestions({
			token: $dadata,
			type: "PARTY",
			count: 5,
			onSelect: function (suggestion) {

				var dir, sdir, dirName;

				$('#recv\\[castInn\\]').val(suggestion.data.inn);
				$('#recv\\[castUrName\\]').val(suggestion.data.name.full_with_opf);
				$('#recv\\[castUrNameShort\\]').val(suggestion.data.name.short_with_opf);
				$('#recv\\[castOkpo\\]').val(suggestion.data.okpo);
				$('#recv\\[castOgrn\\]').val(suggestion.data.ogrn);
				$('#recv\\[castUrAddr\\]').val(suggestion.data.address.data.postal_code + ', ' + suggestion.data.address.value);

				/*
				$('#recv\\[castDirName\\]').val(suggestion.data.management.name);
				$('#recv\\[castDirStatus\\]').val(suggestion.data.management.post);
				$('#recv\\[castDirStatusSig\\]').val(suggestion.data.management.post);
				*/

				//если это НЕ ИП
				if (suggestion.data.type !== 'INDIVIDUAL') {

					dir = suggestion.data.management.name;
					sdir = dir.split(' ');
					dirName = sdir[0] + ' ' + sdir[1].charAt(0) + '. ' + sdir[2].charAt(0) + '.';

					$('#recv\\[castKpp\\]').val(suggestion.data.kpp);
					$('#recv\\[castDirName\\]').val(ucfirst(suggestion.data.management.post) + ' ' + suggestion.data.management.name);
					$('#recv\\[castDirStatus\\]').val(ucfirst(suggestion.data.management.post));
					$('#recv\\[castDirStatusSig\\]').val(ucfirst(suggestion.data.management.post));
					$('#recv\\[castDirSignature\\]').val(dirName);

				}
				else {

					dir = suggestion.data.name.full;
					sdir = dir.split(' ');
					dirName = ucfirst(sdir[0]) + ' ' + sdir[1].charAt(0) + '. ' + sdir[2].charAt(0) + '.';

					$('#recv\\[castKpp\\]').val('0');
					$('#recv\\[castDirName\\]').val(suggestion.data.name.full_with_opf);
					$('#recv\\[castDirStatus\\]').val('Индивидуального предпринимателя');
					$('#recv\\[castDirStatusSig\\]').val(ucfirst(suggestion.data.opf.full));
					$('#recv\\[castDirSignature\\]').val(dirName);
					$('#recv\\[castDirOsnovanie\\]').val('Свидетельства о регистрации индивидуального предпринимателя № .. от ..');

				}

			}
		});

	});

}

/**
 * Функция преобразует строку (особенно содержащую пробелы) в строку для http-запросов
 * В противном случае пробел будет разрушать запрос
 * @param data
 * @returns {string}
 */
function urlEncodeData(data) {

	var query = [];

	if (data instanceof Object) {

		for (var k in data) {
			query.push(encodeURIComponent(k) + "=" +
				encodeURIComponent(data[k]));
		}

		return query.join('&');

	}
	else
		return encodeURIComponent(data);


	//return data;

}

function urlencode(text) {
	var trans = [];
	for (var i = 0x410; i <= 0x44F; i++) trans[i] = i - 0x350;
	trans[0x401] = 0xA8;
	trans[0x451] = 0xB8;
	var ret = [];
	for (var i = 0; i < text.length; i++) {
		var n = text.charCodeAt(i);
		if (typeof trans[n] != 'undefined') n = trans[n];
		if (n <= 0xFF) ret.push(n);
	}
	return escape(String.fromCharCode.apply(null, ret));
}

function blockError() {
	return true;
}


/**
 * Центрирование элементов в окне
 * @returns {jQuery}
 */
$.fn.center = function () {

	var w = $(window);

	//if ($(window).width() > 760) {

	this.css("position", "absolute");

	if (!isMobile || $(window).width() > 500) {

		this.css("top", (w.height() - this.height()) / 2 + "px");
		this.css("left", (w.width() - this.width()) / 2 + w.scrollLeft() + "px");

	}
	else {

		this.css("top", "0px");
		this.css("left", "0px");

	}

	return this;

	//}

};

/**
 * Функция позволяет менять css у элементов, в т.ч. :before, :after
 * @param selector
 * @param styles
 * @param sheet
 * @returns {Window}
 */
window.addRule = function (selector, styles, sheet) {

	styles = (function (styles) {
		if (typeof styles === "string") return styles;
		var clone = "";
		for (var p in styles) {
			if (styles.hasOwnProperty(p)) {
				var val = styles[p];
				p = p.replace(/([A-Z])/g, "-$1").toLowerCase(); // convert to dash-case
				clone += p + ":" + (p === "content" ? '"' + val + '"' : val) + "; ";
			}
		}
		return clone;
	}(styles));
	sheet = sheet || document.styleSheets[document.styleSheets.length - 1];

	if (sheet.insertRule) sheet.insertRule(selector + " {" + styles + "}", sheet.cssRules.length);
	else if (sheet.addRule) sheet.addRule(selector, styles);

	return this;

};

/**
 * Адаптер функции addRule для jQuery
 * $('.popmenu:before').addRule("top: 100px");
 */
if ($) $.fn.addRule = function (styles, sheet) {
	addRule(this.selector, styles, sheet);
	return this;
};

/**
 * Своё событие при изменении позици элемента
 * @param trigger
 * @param millis
 * @returns {*|jQuery|HTMLElement}
 */
$.fn.onPositionChanged = function (trigger, millis) {
	if (millis == null) millis = 100;
	var o = $(this[0]); // our jquery object
	if (o.length < 1) return o;

	var lastPos = null;
	var lastOff = null;
	setInterval(function () {
		if (o == null || o.length < 1) return o; // abort if element is non existend eny more
		if (lastPos == null) lastPos = o.position();
		if (lastOff == null) lastOff = o.offset();
		var newPos = o.position();
		var newOff = o.offset();
		if (lastPos.top != newPos.top || lastPos.left != newPos.left) {
			$(this).trigger('onPositionChanged', {lastPos: lastPos, newPos: newPos});
			if (typeof (trigger) == "function") trigger(lastPos, newPos);
			lastPos = o.position();
		}
		if (lastOff.top != newOff.top || lastOff.left != newOff.left) {
			$(this).trigger('onOffsetChanged', {lastOff: lastOff, newOff: newOff});
			if (typeof (trigger) == "function") trigger(lastOff, newOff);
			lastOff = o.offset();
		}
	}, millis);

	return o;
};

/**
 * Своё событие при изменении позици элемента
 * @param trigger
 * @param millis
 * @returns {*|jQuery|HTMLElement}
 */
$.fn.onVisibleChanged = function (trigger, millis) {
	if (millis == null) millis = 100;
	var o = $(this[0]); // our jquery object
	if (o.length < 1) return o;

	var oldStatus = o.css('display');

	setInterval(function () {
		if (o == null || o.length < 1) return o; // abort if element is non existend eny more
		if (oldStatus == null) oldStatus = o.css('display');

		var newStatus = o.css('display');

		if (oldStatus != newStatus) {
			$(this).trigger('onVisibleChanged', {status: newStatus});
			if (typeof (trigger) == "function") trigger(newStatus);
			oldStatus = o.css('display');
		}

	}, millis);

	return o;
};

/**
 * Управление форматом инпутов с телефонами
 * @param format
 * @returns {$}
 */
$.fn.phoneFormater = function (format) {

	var $mask = format;
	var $length = this.val().replace(/\D+/g, "").length;
	var element = this.parent('.phoneBlock');
	var block = element.find('.adder').data('block');
	var main = element.find('.adder').data('main');

	var el = $('#' + main);

	if ($length > 11) $mask = '99 999 9999-99999';

	this.unsetMask();

	this.setMask({
		mask: $mask,
		autoTab: true,
		maxLength: 14,
		onValid: function () {

			if ($length > 3 && !$(this).hasClass('masked')) {

				el.find('.' + block + ':last').clone(true).appendTo('#' + main);
				el.find('.' + block).not(':last').find('.adder').remove();
				el.find('.' + block + ':last').find('.phone').removeClass('required').val('');

				if (el.find('.' + block + ':first').find('.phone').hasClass('required')) {
					el.find('.' + block).not(':last').find('.phone').addClass('required');
				}

				el.find('.' + block + ':last').find('.remover').removeClass('hidden');
				el.find('.' + block + ':last').find('.phone').css({"background": ""});

				$(this).addClass('masked');

			}

			//return false;

		}

	});

	return this;

};

/**
 * Автоматическое увеичение размера текстового поля
 * @param maxHeight - максимальая высота поля
 * @param rows - количество строк при инициализации
 * @returns {$}
 */
$.fn.autoHeight = function (maxHeight, rows) {

	if (rows === 'undefined') rows = 1;

	this.trigger('input');

	this.each(function () {

		$(this).attr('rows', rows);
		resize($(this));

		$('#dialog').center();

	});

	this.off('input');
	this.on('input', function () {

		resize($(this));

		$('#dialog').center();

	});

	function resize($text) {

		$text.css({'min-height': '100px', 'height': $text[0].scrollHeight + 'px', 'overflow-y': 'hidden'});
		//if($text[0].scrollHeight > maxHeight) $text.css({'height': (maxHeight) + 'px', 'overflow-y':'auto'});

		$('#dialog').center();

	}

	return this;

};

/**
 * Перехват события изменения поля типа input
 * example: Use it like: $('input').on('inputchange', function() { console.log(this.value) });
 * https://stackoverflow.com/questions/1443292/how-do-i-implement-onchange-of-input-type-text-with-jquery
 * @type {{add: $.event.special.inputchange.add, setup: $.event.special.inputchange.setup, teardown: $.event.special.inputchange.teardown}}
 */
$.event.special.inputchange = {
	setup: function() {
		var self = this, val;
		$.data(this, 'timer', window.setInterval(function() {
			val = self.value;
			if ( $.data( self, 'cache') != val ) {
				$.data( self, 'cache', val );
				$( self ).trigger( 'inputchange' );
			}
		}, 20));
	},
	teardown: function() {
		window.clearInterval( $.data(this, 'timer') );
	},
	add: function() {
		$.data(this, 'cache', this.value);
	}
};

/**
 * Сериализация элементов формы без формы
 */
(function($){
	$.fn.serializeAny = function() {
		var ret = [];
		$.each( $(this).find(':input'), function() {
			ret.push( encodeURIComponent(this.name) + "=" + encodeURIComponent( $(this).val() ) );
		});

		return ret.join("&").replace(/%20/g, "+");
	}
})(jQuery);

$('#dialog').onVisibleChanged(function () {

	if ($('#dialog').css('display') === 'none') $('body').css('overflow-y', 'auto');

});

/**
 * Закрытие модального окна
 * @constructor
 */
function DClose() {

	if (editor && ehtml !== '') {

		$('#dialog #content').val(ehtml);

		editor.destroy();
		editor = null;

		$('.nano').css('height', '100%');

	}

	$('#subwindow').removeClass('open');

	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none');
	$('.dialog-preloader').css('display', 'none');
	$('#dialog').css({
		'display': 'none',
		'width': '500px',
		'height': 'unset',
		'position': 'absolute',
		'margin': 'unset'
	}).center();

	if ($('#isCard').val() === 'yes') {

		$('body').css('overflow-y', 'auto');
		$('html').css('overflow-y', 'auto');

	}

	ShowModal.fire({
		etype: 'dialog',
		action: 'closed'
	});

}

/**
 * Форматирование суммы
 * @param n
 * @param d
 * @param s
 * @returns {string | *}
 */
function setNumFormat(n, d, s) {

	if (arguments.length === 2) {
		s = "`";
	}
	if (arguments.length === 1) {
		s = "`";
		d = ",";
	}

	n = n.toString();
	a = n.split(d);
	x = a[0];
	y = a[1];
	z = "";

	if (typeof(x) !== "undefined") {
		for (i = x.length - 1; i >= 0; i--)
			z += x.charAt(i);
		z = z.replace(/(\d{3})/g, "$1" + s);
		if (z.slice(-s.length) === s)
			z = z.slice(0, -s.length);
		x = "";
		for (i = z.length - 1; i >= 0; i--)
			x += z.charAt(i);
		if (typeof(y) !== "undefined" && y.length > 0)
			x += d + y;
	}

	return x;
}

//<![CDATA[
var editor;
var ehtml;

function createEditor() {
	ehtml = $('#new_tpl').val();
	editor = CKEDITOR.replace('new_tpl',
		{
			height: '210px',
			width: '99.0%',
			toolbar: [
				['Source', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
				['Undo', 'Redo', '-', 'Replace', '-', 'SelectAll', 'Maximize', 'RemoveFormat', '-', 'PasteText', 'PasteFromWord', 'Image', 'HorizontalRule', 'SpecialChar'],
				['TextColor', 'BGColor', 'Styles', 'Format', 'Font', 'FontSize'],
				['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
			]
		});
	//editor.setData( html );
}

function removeEditor() {

	ehtml = $('#cke_editor_new_tpl').html();

	if (editor) {
		$('#new_tpl').val(ehtml);
		editor.destroy();
		editor = null;
	}
	return true;
}

//]]>

function getCookie(name) {
	var cookie = " " + document.cookie;
	var search = " " + name + "=";
	var setStr = null;
	var offset = 0;
	var end = 0;
	if (cookie.length > 0) {
		offset = cookie.indexOf(search);
		if (offset != -1) {
			offset += search.length;
			end = cookie.indexOf(";", offset)
			if (end == -1) {
				end = cookie.length;
			}
			setStr = unescape(cookie.substring(offset, end));
		}
	}
	return (setStr);
}

function setCookie(name, value, options) {// https://learn.javascript.ru/cookie
	options = options || {};

	var expires = options.expires;

	if (typeof expires == "number" && expires) {
		var d = new Date();
		d.setTime(d.getTime() + expires * 1000);
		expires = options.expires = d;
	}
	if (expires && expires.toUTCString) {
		options.expires = expires.toUTCString();
	}

	value = encodeURIComponent(value);

	var updatedCookie = name + "=" + value;

	for (var propName in options) {
		updatedCookie += "; " + propName;
		var propValue = options[propName];
		if (propValue !== true) {
			updatedCookie += "=" + propValue;
		}
	}

	document.cookie = updatedCookie;
}

function deleteCookie(name) {
	setCookie(name, "", {
		expires: -1
	})
}

function getScreenSize() {

	var h = $(window).height();
	var w = $(window).width();

	document.cookie = 'width=' + w + '; path=/; samesite=strict';
	document.cookie = 'height=' + h + '; path=/; samesite=strict';

}

function checkuserpass(pwd) {

	var strongRegex = new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$", "g");
	var mediumRegex = new RegExp("^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$", "g");
	var enoughRegex = new RegExp("(?=.{6,}).*", "g");
	var userpass = $('#' + pwd).val();
	var iduser = $('#iduser').val();
	var cot = $('.green').length;
	var cc = 1;

	if (iduser > 0 && userpass === '') {

		$('#passstrength').html('').addClass('hidden');
		$('#' + pwd).removeClass('red').addClass('green');

		$('#fakebutton').addClass('hidden');
		$('#submitbutton').removeClass('hidden');

	}
	else {

		if (false === enoughRegex.test(userpass)) {

			$('#passstrength').removeClass('hidden').html('<i class="icon-thumbs-down-alt red"></i>&nbsp;<span class="red">Должно быть больше 6 символов</span>');
			$('#' + pwd).removeClass('green').addClass('red');

			$('#submitbutton').addClass('hidden');
			$('#fakebutton').removeClass('hidden');


		}
		else if (strongRegex.test(userpass)) {

			$('#passstrength').removeClass().addClass('green').html('<i class="icon-thumbs-up-alt"></i>&nbsp;<b>Сложный</b>. Великолепно!');
			$('#' + pwd).removeClass('red').addClass('green');

		}
		else if (mediumRegex.test(userpass)) {

			$('#passstrength').removeClass().addClass('blue').html('<i class="icon-thumbs-up-alt"></i>&nbsp;<b>Средний</b>. Еще немного!');
			$('#' + pwd).removeClass('red').addClass('green');

		}
		else {

			$('#passstrength').removeClass().addClass('red').html('<i class="icon-thumbs-down-alt"></i>&nbsp;Проверь раскладку');
			$('#' + pwd).removeClass('green').addClass('red');

			$('#submitbutton').addClass('hidden');
			$('#fakebutton').removeClass('hidden');

		}

	}

	if ($('#email').is('input') || $('#mail_url').is('input')) cc = 0;

	if (cot > cc) {

		$('#submitbutton').removeClass('hidden');
		$('#fakebutton').addClass('hidden');

	}

	return true;
}

function addTagInEditor(txtar, myitem) {

	if (!editor) {

		var textt = $('#' + txtar).val();
		$('#' + txtar).val(textt + myitem);

	}
	else {

		var oEditor = CKEDITOR.instances.suffix;
		oEditor.insertHtml(myitem);

	}

	return true;
}

function insTextAtCursor(_obj_name, _text) {

	var area = document.getElementsByName(_obj_name).item(0);

	if ((area.selectionStart) || (area.selectionStart == '0')) {

		var p_start = area.selectionStart;
		var p_end = area.selectionEnd;

		area.value = area.value.substring(0, p_start) + _text + area.value.substring(p_end, area.value.length);

	}

	if (document.selection) {

		area.focus();
		sel = document.selection.createRange();
		sel.text = _text;

	}

}

function strtr(str, from, to) {
	//  discuss at: http://phpjs.org/functions/strtr/
	// original by: Brett Zamir (http://brett-zamir.me)
	//    input by: uestla
	//    input by: Alan C
	//    input by: Taras Bogach
	//    input by: jpfle
	// bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// bugfixed by: Brett Zamir (http://brett-zamir.me)
	// bugfixed by: Brett Zamir (http://brett-zamir.me)
	//  depends on: krsort
	//  depends on: ini_set
	//   example 1: $trans = {'hello' : 'hi', 'hi' : 'hello'};
	//   example 1: strtr('hi all, I said hello', $trans)
	//   returns 1: 'hello all, I said hi'
	//   example 2: strtr('äaabaåccasdeöoo', 'äåö','aao');
	//   returns 2: 'aaabaaccasdeooo'
	//   example 3: strtr('ääääääää', 'ä', 'a');
	//   returns 3: 'aaaaaaaa'
	//   example 4: strtr('http', 'pthxyz','xyzpth');
	//   returns 4: 'zyyx'
	//   example 5: strtr('zyyx', 'pthxyz','xyzpth');
	//   returns 5: 'http'
	//   example 6: strtr('aa', {'a':1,'aa':2});
	//   returns 6: '2'

	var fr = '',
		i = 0,
		j = 0,
		lenStr = 0,
		lenFrom = 0,
		tmpStrictForIn = false,
		fromTypeStr = '',
		toTypeStr = '',
		istr = '';
	var tmpFrom = [];
	var tmpTo = [];
	var ret = '';
	var match = false;

	// Received replace_pairs?
	// Convert to normal from->to chars
	if (typeof from === 'object') {
		tmpStrictForIn = this.ini_set('phpjs.strictForIn', false); // Not thread-safe; temporarily set to true
		from = this.krsort(from);
		this.ini_set('phpjs.strictForIn', tmpStrictForIn);

		for (fr in from) {
			if (from.hasOwnProperty(fr)) {
				tmpFrom.push(fr);
				tmpTo.push(from[fr]);
			}
		}

		from = tmpFrom;
		to = tmpTo;
	}

	// Walk through subject and replace chars when needed
	lenStr = str.length;
	lenFrom = from.length;
	fromTypeStr = typeof from === 'string';
	toTypeStr = typeof to === 'string';

	for (i = 0; i < lenStr; i++) {
		match = false;
		if (fromTypeStr) {
			istr = str.charAt(i);
			for (j = 0; j < lenFrom; j++) {
				if (istr == from.charAt(j)) {
					match = true;
					break;
				}
			}
		}
		else {
			for (j = 0; j < lenFrom; j++) {
				if (str.substr(i, from[j].length) == from[j]) {
					match = true;
					// Fast forward
					i = (i + from[j].length) - 1;
					break;
				}
			}
		}
		if (match) {
			ret += toTypeStr ? to.charAt(j) : to[j];
		} else {
			ret += str.charAt(i);
		}
	}

	return ret;
}

function striptags(str) {
	// Strip HTML and PHP tags from a string
	//
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	return str.replace(/<\/?[^>]+>/gi, '');
}

function in_array(needle, haystack, strict) {
	// Checks if a value exists in an array
	//
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)

	var found = false, key, strict = !!strict;

	for (key in haystack) {
		if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
			found = true;
			break;
		}
	}

	return found;
}

function ucfirst(str) {
	var first = str.charAt(0).toUpperCase();
	return first + str.substr(1).toLowerCase();
}

/*
Удаление элемента из массива.
String value: значение, которое необходимо найти и удалить.
return: массив без удаленного элемента; false в противном случае.
*/

/*Ссылки на просмотр или переход в карточку*/

function openClient(id, hash) {

	var str = (hash) ? '#' + hash : '';

	window.open('/card.client?clid=' + id + str);

	return false;

}

function openPerson(id) {

	window.open('/card.person?pid=' + id);

	return false;

}

function openDogovor(id, hash) {

	var str = '';

	if (!hash)
		str = '';

	else if (hash !== "undefined") str = '#' + hash;

	window.open('/card.deal?did=' + id + str);

	return false;

}

function yNotifyMe(data) {

	data = data.split(",");
	var title = data[0];
	var content = data[1];
	var img = data[2];
	var id = data[3];
	var url = data[4];
	var notification;

	if(Notification.permission === 'granted') {

		if (("Notification" in window)) {

			if (Notification.permission === "granted") {
				notification = new Notification(title, {
					lang: 'ru-RU',
					body: content,
					icon: '/assets/images/' + img,
					tag: id
				});
			}
			// В противном случае, мы должны спросить у пользователя разрешение
			else if (Notification.permission === 'default') {
				Notification.requestPermission(function (permission) {

					// Не зависимо от ответа, сохраняем его в настройках
					if (!('permission' in Notification)) {
						Notification.permission = permission;
					}
					// Если разрешение получено, то создадим уведомление
					if (permission === "granted") {
						var notification = new Notification(title, {
							lang: 'ru-RU',
							body: content,
							icon: '/assets/images/' + img,
							tag: id
						});
					}

				});
			}

			else return true;

			notification.onshow = function () {

				var wpmupsnd = new Audio("/assets/images/mp3/bigbox.mp3");
				wpmupsnd.volume = 0.2;
				wpmupsnd.play();

			};
			notification.onclick = function () {

				if ($('#mid').is('input')) {

					razdel('inbox');

				}
				else {

					//ymailw = window.open('ymail.php');
					//ymailw.focus();
					$mailer.preview(id);

				}

			};

		}
		else
			return true;

	}
	else{

		Swal.fire({
			icon: 'info',
			imageUrl: '/assets/images/' + img,
			position: 'bottom-end',
			background: "var(--blue)",
			title: '<div class="white fs-11">' + title + '</div>',
			html: '<div class="white">' + content + '</div>',
			showConfirmButton: false,
			timer: 1500
		});

	}

}

function yNotifyCheck() {

	if (("Notification" in window)) {

		if (Notification.permission === 'default') {

			Notification.requestPermission(function (permission) {

				// Не зависимо от ответа, сохраняем его в настройках
				if (!('permission' in Notification)) {
					Notification.permission = permission;
				}

			});

		}
		else return true;

	}
	else return true;

}

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
function checkRequired(forma) {

	var $req1, $req2, $req3;
	var $block = $('#dialog');

	//если диалоговое окно открыто
	//то ищем id формы, т.к. полюбому мы проверяем заполненные поля в ней
	if ($block.is(':visible'))
		forma = $block.find('form').attr('id');

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


	var em = 0;

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

		//кол-во выбранных элементов
		//var countSel = $('#' + $id + ':checked').length;

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

	if (em > 0) {

		Swal.fire({
			title: "Ошибка",
			text: "Не заполнено " + em + " обязательных полей\n\rОни выделены цветом",
			type: "error"
		});

		$('#message').fadeTo(1, 0).css('display', 'none');

		return false;

	}
	else return true;

}

function openPlugin(url) {

	if (!openFrame)
		window.open(url);

	else {

		$('#smframe').attr('src', '/' + url + '?face=frame');
		$('.smframe--container').css({"left": "0"});
		$('.smframe--url').removeClass('hidden').attr('data-url', '/' + url);

	}

	return false;

}

// Получение иконки по расширению файла
function getIconFile(name) {

	var icon = '';
	name = name.toLowerCase().substr(name.lastIndexOf('.') + 1);

	switch (name) {

		case 'txt':
		case 'html':
		case 'htm':
		case 'shtml':
			icon = '<i class="icon-doc-text blue"></i>';
			break;
		case 'doc':
		case 'docx':
		case 'rtf':
			icon = '<i class="icon-file-word blue"></i>';
			break;
		case 'pdf':
			icon = '<i class="icon-file-pdf red"></i>';
			break;
		case 'xls':
		case 'xlsx':
			icon = '<i class="icon-file-excel green"></i>';
			break;
		case 'ppt':
		case 'pptx':
			icon = '<i class="icon-file-powerpoint orange"></i>';
			break;
		case 'jpeg':
		case 'jpe':
		case 'jpg':
		case 'gif':
		case 'png':
		case 'bmp':
			icon = '<i class="icon-file-image yelw"></i>';
			break;
		case 'zip':
		case 'tar':
		case 'rar':
		case 'z':
		case 'exe':
		case 'bin':
		case 'dat':
			icon = '<i class="icon-file-archive broun"></i>';
			break;
		case 'wav':
		case 'mp1':
		case 'mp2':
		case 'mp3':
		case 'mid':
			icon = '<i class="icon-file-audio"></i>';
			break;
		case 'mpeg':
		case 'mpg':
		case 'mov':
		case 'avi':
		case 'rm':
		case 'mp4':
			icon = '<i class="icon-file-video"></i>';
			break;
		default:
			icon = '<i class="icon-file-code gray"></i>';
	}

	return icon;

}