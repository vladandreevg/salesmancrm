/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.6          */
/* ============================ */
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
 * js-События
 * @constructor
 */
var CustomEvent = function () {

	//имя события
	this.eventName = arguments[0];
	var mEventName = this.eventName;

	//функция, которая вызывается при событии
	var eventAction = null;

	//привязываем функцию к событию
	this.subscribe = function (fn) {
		eventAction = fn;
	};

	//выполнение события
	this.fire = function (eventArgs) {
		//this.eventName = eventName;
		if (eventAction != null) {
			eventAction(eventArgs);
		}
	};
};

var ShowModal = new CustomEvent("ShowModal");
var CardLoad = new CustomEvent("CardLoad");

/**
 * Обнуляем работу воркера Астриск
 */
localStorage.setItem("asteriskWork", '');

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
const $edialog = $('#dialog');

/**
 * Окошко телефонии
 * @type {number}
 */
var $callerPositionCash = parseInt(localStorage.getItem('callerPosition'));
var $callerElement = $('#caller');
var $cE = $callerElement.position();
var $callerPosition = ($callerPositionCash === 0 || $callerPositionCash === 'NaN') ? $cE.left : $callerPositionCash;

//устанавливаем позицию
localStorage.setItem('callerPosition', $callerPosition);

var isCtrl = false;

var swindow = false;

//Даты и периоды
var period = {
	all: ['', ''],
	yestoday: [moment().subtract(1, 'days').format('YYYY-MM-DD'), moment().subtract(1, 'days').format('YYYY-MM-DD')],
	today: [moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD')],
	tomorrow: [moment().add(1, 'days').format('YYYY-MM-DD'), moment().add(1, 'days').format('YYYY-MM-DD')],
	calendarweekprev: [moment().subtract(1, 'week').weekday(1).format('YYYY-MM-DD'), moment().subtract(1, 'week').weekday(7).format('YYYY-MM-DD')],
	calendarweek: [moment().weekday(1).format('YYYY-MM-DD'), moment().weekday(7).format('YYYY-MM-DD')],
	calendarweeknext: [moment().add(1, 'week').weekday(1).format('YYYY-MM-DD'), moment().add(1, 'week').weekday(7).format('YYYY-MM-DD')],
	prevmonth: [moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD')],
	monthprev: [moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD')],
	month: [moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD')],
	monthnext: [moment().add(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().add(1, 'months').endOf('month').format('YYYY-MM-DD')],
	nextmonth: [moment().add(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().add(1, 'months').endOf('month').format('YYYY-MM-DD')],
	quartprev: [moment().subtract(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().subtract(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	prevquart: [moment().subtract(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().subtract(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	quart: [moment().startOf('quarter').format('YYYY-MM-DD'), moment().endOf('quarter').format('YYYY-MM-DD')],
	quartnext: [moment().add(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().add(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	nextquart: [moment().add(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().add(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	prevyear: [moment().subtract(1, 'year').startOf('year').format('YYYY-MM-DD'), moment().subtract(1, 'year').endOf('year').format('YYYY-MM-DD')],
	year: [moment().startOf('year').format('YYYY-MM-DD'), moment().endOf('year').format('YYYY-MM-DD')],
	yearnext: [moment().add(1, 'year').startOf('year').format('YYYY-MM-DD'), moment().add(1, 'year').endOf('year').format('YYYY-MM-DD')],
	nextyear: [moment().add(1, 'year').startOf('year').format('YYYY-MM-DD'), moment().add(1, 'year').endOf('year').format('YYYY-MM-DD')]
};

var calendarMonth = {
	month: moment().format('MM'),
	year: moment().format('YYYY')
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

	/*Добавляем в меню плагины*/
	includeJS('/assets/js/plugins.js');
	/*Добавляем в меню плагины*/

	yNotifyCheck();

	//шаблон для уведомлений
	$.Mustache.load('/content/notify/tpl.mustache');

	$notify.popup();
	setInterval($notify.popup, 30000);

	if (isMobile) {

		includeJS('/assets/js/smMobileTable.js');
		includeJS('/assets/js/jquery/jquery.scrollTo.js');

		//перемещаем заголовки разделов
		var $pagetips = $('#tips').detach();
		$pagetips.appendTo('.menu--header');

		//перемещаем меню в тело
		var $menu = $('.menu--mobile').detach();
		$menu.appendTo('body');

		//перемещаем меню в тело
		var $pmenu = $('#subpan3').detach();
		$pmenu.appendTo('body');

		var $wh = $(window).height() - 50;
		$('#rmenu').css({'height': $wh + 'px', 'top': '50px', 'bottom': 'unset', 'z-index': '28'});
		$('#lmenu').css({'height': $wh + 'px', 'top': '0px', 'bottom': 'unset'});

	}
	if (!isMobile) {

		includeJS("/assets/js/smTableColumnFixer.js");

	}

	getScreenSize();

	$(document).on('change', '#newfield', function () {

		var str = $('#fieldAdd').serialize();

		var type = $(this).data("type");

		$.get('/content/helpers/' + type + '.helpers.php', str, function (data) {

			edit_field(data.param, data.name, data.type, data.id, 'new');

		}, "json");

	});

	//для Рабочего стола удаляем кнопку Напоминаний
	if ($display === 'desktop')
		$('li[data-id="todo"]').remove();

	if ($(".ui-layout-center").is('div')) {

		setInterval(talarm, 300000);
		talarm();
		countsPanel();

		if (!isMobile) {
			Visibility.every(150000, 600000, countsPanel);
		}


	}

	var intro = getCookie('intro');
	if (intro === 'hid') $('#startinto').hide();

	$('.close').on('click', function () {
		new DClose();
	});
	$('#hid').on('click', function () {
		$('#caller').empty().hide();
	});

	$('#message').on('click', function () {
		$(this).fadeTo(10, 0).hide('normal').empty();
	});

	$("#dialog").draggable({handle: ".zagolovok", cursor: "move", opacity: "0.85", containment: "document"});

	/**
	 * Делает окошко телефонии перемещаемым
	 */
	$('#caller').draggable({
		handle: ".zag",
		axis: "x",
		cursor: "move",
		opacity: "0.85",
		containment: "document",
		drag: function (event, ui) {
		},
		stop: function (event, ui) {

			$callerPosition = ui.position.left;
			localStorage.setItem('callerPosition', $callerPosition);

		}
	});
	setCallerPos();

	$("#pcomments").on("mouseleave", function () {
		$('#commentspan').hide();
	});

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

	$('#unisearch').on('keydown', function (e) {

		var keycode = e.originalEvent.key;

		if (keycode === 'Enter') { // escape, close box, esc

			uniSearchPop();
			return false;

		}

	});

	/*
	$('#word').on('keydown', function (e) {

		var keycode = e.originalEvent.key;
		var func = $(this).data('func');

		if (keycode === 'Enter') { // escape, close box, esc

			preconfigpage();
			return false;

		}

	});
	*/

	$('.searchwordinput').on('keydown', function (e) {

		var keycode = e.originalEvent.key;
		var fnc = $(this).data('func');

		if (keycode === 'Enter') { // escape, close box, esc

			if (fnc)
				eval(fnc)();

			return false;

		}

	});

	$('.flyit').each(function () {

		$(this).find('.yselectBox').detach().appendTo('#flyitbox');

	});
	$('.ydropString').each(function () {

		var txt =striptags($(this).find('label').text()).replace(/<[^p].*?>/g, '').trim();

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

			if ($el.css('display') === 'none')
				$(this).find('i.icon-angle-up').removeClass('icon-angle-up').addClass('icon-angle-down');
			else
				$(this).find('i.icon-angle-down').removeClass('icon-angle-down').addClass('icon-angle-up');

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
	$(document).on('mouseup', function (e) { // событие клика по веб-документу

		var div = $(".ydropDown.open"); // тут указываем ID элемента

		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам

			$(".yselectBox.open").removeClass('open').hide();

			div.find(".action").addClass('hidden');
			div.removeClass('open');
			div.find('i.icon-angle-up').addClass('icon-angle-down').removeClass('icon-angle-up');

		}

	});

	$('.nano')
		.css('height', '100%')
		.on('update', function (e) {

			var div = $(".ydropDown"); // тут указываем ID элемента
			if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам

				if (!$(this).hasClass('flyit')) {

					$(".yselectBox", this).hide();
					$(".action", this).addClass('hidden');
					div.find('i:last').addClass('icon-angle-down').removeClass('icon-angle-up');

				}
				else {

				}

			}

			$('.yselectBox.fly').each(function () {

				var el = $(this).data('id');

				$(this).hide();
				$('.ydropDown[data-id="' + el + '"]').find('i:last').removeClass('icon-angle-up').addClass('icon-angle-down');

			});

		});

	$(document).on('click', '.tagsmenuToggler', function () {

		$('.tagsmenu').not(this).addClass('hidden');

		if ($(this).next().hasClass('.tagsmenu'))
			$(this).next('.tagsmenu').toggleClass('hidden');

		else
			$(this).closest('div').find('.tagsmenu').toggleClass('hidden');

		$(this).find('#mapii').toggleClass('icon-angle-down icon-angle-up');

	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу

		//console.log(e);

		var div = $(".tagsmenuToggler"); // тут указываем ID элемента
		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам
			$(".tagsmenu", this).addClass('hidden');
			div.find('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');
		}

	});

	$(document).on('click', '#menuavatar', function () {

		$(this).find('.avatar--menu').toggleClass('hidden');
		$(this).find('.nano').nanoScroller();

	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу

		var div = $("#menuavatar"); // тут указываем ID элемента
		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам
			$(".avatar--menu", this).addClass('hidden');
		}
	});

	$(document).on('click', '.pop', function () {

		if ($(this).hasClass('donthidee') === false) {

			$(".popmenu", this).toggle();
			$(".popmenu-top", this).toggle();

		}

	});
	$(".pop").not('.donthidee').on('mouseleave', function () { // событие клика по веб-документу

		$(".popmenu", this).hide();
		$(".popmenu-top", this).hide();

	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу

		var div = $(".popmenu").not('.open'); // тут указываем ID элемента

		//console.log(e);

		if (!div.is(e.target) // если клик был не по нашему блоку
			&& div.has(e.target).length === 0) { // и не по его дочерним элементам

			if (e.target.id !== 'search' && e.target.className !== 'popbody') {

				$('.popmenu').not('.nothide').removeClass('open');
				$(".popmenu-top", this).hide();

			}
			else return true;

		}

	});
	$(document).on('click', '.adddeal', function () {
		$(this).toggleClass('hidden');
		$(this).parent('div').find('.deal').toggleClass('hidden');
	});

	$(document).on('mouseup', function (e) { // событие клика по веб-документу
		var div = $("#orgspisok"); // тут указываем ID элемента
		if (!div.is(e.target) // если клик был не по нашему блоку
			&& div.has(e.target).length === 0) { // и не по его дочерним элементам
			spisok_remove();
		}
	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу
		var div = $("#subwindow"); // тут указываем ID элемента
		if (!div.is(e.target) // если клик был не по нашему блоку
			&& div.has(e.target).length === 0) { // и не по его дочерним элементам
			$('#subwindow').removeClass('open front').empty();
		}
	});

	$('#hideintro').on('click', function () {
		$('#startinto').hide();
		document.cookie = 'intro=hid; path=/; expires=Mon, 01-Jan-2030 00:00:00 GMT';
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

			if (element.find('.popblock').is('div'))
				hPopB = element.find('.popblock').actual('height');

			hBody = hPop - element.find('.pophead').actual('height') - hPopB;

			//element.find('.popbody').css({"height": hBody + "px"});

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
	$(document).on('click', 'li.lpop', popsearchhandler = function () {

		var element = $(this).find('.popmenu');
		var wH = $(window).height();
		var wW = $(window).width();
		var eTop = wH - Math.round($(this).offset().top + 30);
		var hPop = element.height();
		var hBody = hPop - element.find('.pophead').outerHeight() - element.find('.popblock').outerHeight() - 25;
		var dId = $(this).data('id');

		if (!isMobile) {

			//element.find('.popbody').css({"height": hBody + "px"});

			element.find('.left-triangle-before').css({"bottom": eTop + "px"});
			element.find('.left-triangle-after').css({"bottom": eTop + "px"});

		}
		else {

			element.css({'height': wH + 'px', 'width': wW + 'px', 'left': '0', 'top': '0'});
			element.find('.left-triangle-before').remove();
			element.find('.left-triangle-after').remove();

			hBody = wH - element.find('.pophead').outerHeight() - element.find('.popblock').outerHeight() - 5;

			element.find('.popbody').css({
				"height": hBody + "px",
				"max-height": hBody + "px",
				"min-height": hBody + "px"
			});
			element.find('.popcontent').css({
				"height": hBody + "px",
				"max-height": hBody + "px",
				"min-height": hBody + "px"
			});
			element.find('#searchResult').css({
				"height": hBody + "px",
				"max-height": hBody + "px",
				"min-height": hBody + "px"
			});

			$('#rmenu').css({'z-index' : '101'});

		}

		$(this).closest('ul').find('.popmenu').removeClass('open');

		element.addClass('open');

		if (dId === 'todo') {

			if (!isMobile) $(".popbody").find('.nano').nanoScroller();

		}

		$('#unisearch').trigger('focus');

		if (!isMobile) setTimeout(function () {
			element.find('#unisearch').trigger('focus');
		}, 10);

	});

	$('div.leftpop').on('mouseover', function () {

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

	/**
	 * блок проверки на дубли в карточке клиента
	 */
	if ($('#isCard').is('input')) {

		isCard = true;
		tipCard = $('#card').val();

		if (tipCard === 'client') idCard = $('#clid').val();
		else if (tipCard === 'person') idCard = $('#pid').val();

		doubleModule.card();

	}

	if (isMobile || $(window).width() < 500) {

		$('input.datum').each(function () {
			this.setAttribute('type', 'date');
		});
		$('input.inputdate').each(function () {
			this.setAttribute('type', 'date');
		});

		// переключение меню в карточках для мобильного вида
		$('#dtabs').on('click', function () {
			$(this).toggleClass('open');
		});

	}

	//расставляем периоды, если у селекта не установлен признак data-select="false"
	$('#period').each(function () {

		var $auto = $(this).data('select');
		var $def = $(this).data('selected');

		if( $def === undefined ){

			$def = 'calendarweek';

		}

		//console.log($auto);

		if ($auto !== false) {

			var $goal = $(this).data('goal');
			var $elm = $('#' + $goal);

			$elm.append($elm);
			$elm.find('.dstart').val(period[$def][0]);
			$elm.find('.dend').val(period[$def][1]);

			$('option[data-period="'+$def+'"]', this).prop('selected', true);

		}

		$('option', this).each(function () {

			let val = $(this).data('period');

			if( val !== undefined ) {
				$(this).attr('value', val);
			}

		});

	});

});

$(window).on('resize', function () {

	if (this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function () {
		$(this).trigger('resizeEnd');
	}, 500);

	if ($edialog.is(':visible')) {
		
		$edialog.center();
		$('.dialog-preloader').center();
		$('#dialog_container').css('height', $(window).height());

	}

	$('.ui-layout-north').css("position", "absolute");

});
$(window).on('resizeEnd', function () {

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
	setCallerPos();

	var element = $('li[data-id="search"]').find('.popmenu');

	if (isMobile && element.hasClass('open')) {

		var wH = $(window).height();
		var wW = $(window).width();
		var hBody;

		element.css({'height': wH + 'px', 'width': wW + 'px', 'left': '0', 'top': '0'});
		element.find('.left-triangle-before').remove();
		element.find('.left-triangle-after').remove();

		hBody = wH - element.find('.pophead').outerHeight() - element.find('.popblock').outerHeight() - 5;

		element.find('.popbody').css({
			"height": hBody + "px",
			"max-height": hBody + "px",
			"min-height": hBody + "px"
		});
		element.find('.popcontent').css({
			"height": hBody + "px",
			"max-height": hBody + "px",
			"min-height": hBody + "px"
		});
		element.find('#searchResult').css({
			"height": hBody + "px",
			"max-height": hBody + "px",
			"min-height": hBody + "px"
		});

	}

});
$(window).load(function () {

	$('.ui-layout-center').find('select').not('.multiselect').each(function () {
		if ($(this).closest('span').hasClass('select') === false) $(this).wrap("<span class='select'></span>");
	});
	$('#lmenu').find('select').not('.multiselect').each(function () {
		$(this).wrap("<span class='select'></span>");
	});

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

	if ($edialog.is(':visible'))
		$edialog.center();

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

//$(document).off("click", '.smframe--close');
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

$(document).on('click', '.personselector', function(){

	var clid = parseInt($(this).data('clid'))
	var client = $(this).data('client')
	var pid = parseInt($(this).data('pid'))
	var title = $(this).data('person')

	$('#orgspisok').remove();

	if(clid > 0 && formType !== 'history'){

		$('#client').val(client);
		$('#clid').val(clid);

	}

	if (pid > 0) {

		var html = '<div class="infodiv h0 p3 mr5 mb5 fs-10 flh-12" title="' + title + '"><input type="hidden" name="pid[]" id="pid[]" value="' + pid + '"><div class="el"><div class="del"><i class="icon-cancel-circled"></i></div>' + title + '</div></div>';

		if ($('#pid_list').html() !== '') {
			$('#pid_list').prepend(html);
		}
		else {
			$('#pid_list').html(html);
		}

	}

})

$notify = {
	"list": function () {

		var $elm = $('#subwindow');

		$elm.addClass('open').empty().append('<div class="contentloader"><img src="/assets/images/Services.svg" width="50px" height="50px"></div>');

		$.getJSON("/content/notify/list.php?action=list", function (viewData) {

			viewData.language = $language;

			$elm.empty().mustache('listTpl', viewData).animate({scrollTop: 0}, 200);

			$('.menu--notify').removeClass('open');

		});

	},
	"popup": function () {

		var $elm = $('div[data-id="notify"]');

		$.getJSON("/content/notify/list.php?action=popup", function (viewData) {

			viewData.language = $language;

			$elm.find('.popblock-items').empty().mustache('notifyPopTpl', viewData);

			if (viewData.unread > 0)
				$elm.find('.sup').removeClass('hidden').html(viewData.unread);

			else
				$elm.find('.sup').addClass('hidden').html(viewData.unread);

		});

	},
	"mark": function (id) {

		var $elm = $('.popblock-item[data-id="' + id + '"]');
		var $elmw = $('#subwindow').find('div[data-id="' + id + '"]');
		var $cnt = $('div[data-id="notify"]').find('.sup');

		$.get("/content/notify/list.php?action=mark&id=" + id, function (data) {

			var count = parseInt($cnt.html());

			if (data === 'ok') {

				$elm.remove();
				$elmw.find('.corner').remove();
				$cnt.html(count - 1);

				$notify.list();

			}

		});

	},
	"markall": function () {

		$.get("/content/notify/list.php?action=markall", function (data) {

			if (data === 'ok') {

				$notify.list();
				$notify.popup();

			}

		});

	}
};

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

function desktopTaskHeight() {

	if ($display === 'desktop') {

		var panel_height = $('#dtcal').actual('outerHeight');
		var cal_height = $('#calendar').actual('outerHeight');

		var task_height = panel_height - cal_height - 20;

		//console.log(task_height);

		$('#tasklist').css({"height": task_height + "px"});

	}

}

/**
 * Периодическая проверка напоминаний
 */
function talarm() {
	var url = '/content/ajax/task.alarm.php';
	var left = screen.availWidth - 500;
	var top = screen.availHeight - 500;
	$.post(url, function (data) {
		if (data) {
			salesman_alert = window.open('/content/ajax/task.alarm.php', 'CRMCRM', 'width=430, height=420, menubar=no, location=no, resizable=no, scrollbars=yes, status=no, left=' + left + ', top=' + top);
			salesman_alert.focus();
		}
		return true;
	});
}

function help(url) {

	var urli = '/content/ajax/help.php?url=' + url;
	var left = screen.availWidth / 2 - 315;
	var top = screen.availHeight / 2 - 200;

	var salesman_help = window.open(urli, 'SalesMan', 'width=630, height=400, menubar=no, toolbar=no, location=no, resizable=no, scrollbars=yes, status=no, left=' + left + ', top=' + top);
	salesman_help.focus();

}

/**
 * Счетчики на нижней панели
 */
function countsPanel() {

	$.get('/content/vigets/notify.counts.php', function (data) {

		if ($("#leadnum").is('span')) {

			$("#leadnum").html(data.leads);

			if (parseFloat(data.leads) > 0) $("#leadnum").removeClass('gray green blue').addClass('red');
			else $("#leadnum").removeClass('red green blue').addClass('gray');

		}
		if ($("#kolnot").is('span')) {

			$("#kolnot").html(data.deals);

			if (parseFloat(data.deals) > 0) $("#kolnot").removeClass('gray green blue').addClass('red');
			else $("#kolnot").removeClass('red green blue').addClass('gray');

		}
		if ($("#kolcredit").is('span')) {

			$("#kolcredit").html(data.payments);

			if (parseFloat(data.payments) > 0) $("#kolcredit").removeClass('gray green blue').addClass('red');
			else $("#kolcredit").removeClass('red green blue').addClass('gray');

		}
		if ($("#commnum").is('span')) {

			$("#commnum").html(data.comments);

			if (parseFloat(data.comments) > 0) $("#commnum").removeClass('gray green blue').addClass('red');
			else $("#commnum").removeClass('red green blue').addClass('gray');

		}

	}, 'json');

	if ($("#counthealth").is('span')) {

		$.ajax({
			type: "GET",
			url: "/content/desktop/dt.health.php?view=count",
			success: function (viewData) {

				var color = 'red';

				if (parseInt(viewData) === 0)
					color = 'green';

				$("#counthealth").html(viewData).addClass(color);

			}
		});

	}

}

/**
 * Переключение меню Аватара
 * @DEPRECATED
 * @param id
 * @returns {boolean}
 */
function submenu(id) {

	if ($('#' + id).css('display') == 'none') {
		$('#' + id).css('display', 'block');
	}
	else {
		$('#' + id).css('display', 'none');
	}

	if (id == 'subpan3' && !isMobile) $('#subpan3').find('.nano').nanoScroller();
	if (id == 'subpan3' && isMobile) $('#subpan3').find('.nano').css({"height": "100%"});

	return false;
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
 * Изменение логотипа на иконку Дом
 * При наведении
 * @returns {boolean}
 */
function logoSwitch() {

	$('#home').toggleClass('hidden');
	$('#logo').toggleClass('hidden');

	return false;
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
 * Просмотр напоминаний на дату
 * @param id
 */
function taskview(id) {
	$('#caloption_' + id).show();
}

function taskhide(id) {
	$('#caloption_' + id).hide();
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

	})
		.done(function () {
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
		.done(function () {

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
 * @deprecated
 * @param element
 * @param url
 * @returns {boolean}
 */
function reLoadCal(element, url) {

	$('#message').empty().css('display', 'block').append('<div id="loader" class="loader"><img src="/assets/images/loader.gif"> Загрузка данных...</div>');

	$.get(url, function (data) {

		$('#message').empty().css('display', 'none');
		$('#' + element).html(data);

	})
		.fail(function(status) {

			console.log(status)

			Swal.fire({
				title: "Ошибка: Ошибка загрузки данных!",
				type: "error"
			});

		});

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

				if(action !== undefined)
					ShowModal.fire({
						etype: 'dialog',
						action: action
					});

			},
			statusCode: {
				404: function () {
					new DClose();
					Swal.fire({
						title: "Ошибка 404: Страница не найдена!",
						type: "warning"
					});
				},
				500: function () {
					new DClose();
					Swal.fire({
						title: "Ошибка 500: Ошибка сервера!",
						type: "error"
					});
				}
			}
		})
			.fail(function(status) {

				//console.log(status)

				Swal.fire({
					title: "Ошибка: Ошибка загрузки данных!",
					type: "error"
				});

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

	$container.css('display', 'block');
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

	//if ($length === 11) $mask = '99 999-999-999';
	if ($length === 12) $mask = '999 99-999-9999';
	if ($length > 12) $mask = '99 999 9999-99999';

	//+992 93-600-5059

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
		
		$edialog.center();

	});

	this.off('input');
	this.on('input', function () {

		resize($(this));
		
		$edialog.center();

	});

	function resize($text) {

		$text.css({'min-height': '100px', 'height': $text[0].scrollHeight + 'px', 'overflow-y': 'hidden'});
		//if($text[0].scrollHeight > maxHeight) $text.css({'height': (maxHeight) + 'px', 'overflow-y':'auto'});
		
		$edialog.center();

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

$edialog.onVisibleChanged(function () {

	if ($edialog.css('display') === 'none') $('body').css('overflow-y', 'auto');

});

function checkB() {

	var n = $('.mc').length;
	var m = $('.mc:checked').length;

	if (m === n) $('#ch').attr('checked', true);
	else $('#ch').attr('checked', false);

}

function subwindowClose(){
	
	$('#subwindow').removeClass('open','front');
	
}

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

	$('#subwindow').removeClass('open','front');

	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none');
	$('.dialog-preloader').css('display', 'none');
	$edialog.css({
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

function change_us() {

	var $list = $('#list option:selected').val();

	$('#page').val('1');

	if ($list === 'my') $('#iduser').prop('disabled', true);
	else $('#iduser').prop('disabled', false);

}

function get_user(id) {
	$('#iduser').load('/content/core/core.user.php?action=get.select&tar=' + $('#list option:selected').val() + '&iduser=' + id);
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

//функция для телефонии

/**
 * Позиционирование окна
 */
function setCallerPos() {

	var right = $(window).width() - $callerPosition - $('#caller').outerWidth();

	if (parseInt(right) < 0) right = 30;

	$('#caller').css({'right': right + 'px'});

}

/**
 * Отображение/Скрытие окошка телефонии
 * с проверкой блока исходящего звонка
 * @constructor
 */
function CallWindowShow(h) {

	var n = $('#caller').css('display');

	//отображаем окошко
	if (n === 'none') {

		$('#caller').show();

		if (h === 'hand') localStorage.setItem("callerNotAutoShow", '');

	}
	else {

		//скрываем окошко телефонии
		$('#caller').hide();
		//очищаем поле исходщего звонка
		$('#callto').empty();

	}

	setCallerPos();

}

/**
 * Простое отображение окошка телефонии
 * @constructor
 */
function CallWShow() {

	var auto = localStorage.getItem("callerNotAutoShow");

	if (auto !== 'yes') $('#caller').show();

}

function showCallWindow(url) {

	$('#callto').load(url);
	$('#caller').show();//.css("right",collerpos.right);
	$('#peers').hide();

	setCallerPos();

}

function hideCallWindow(h) {

	$('#callto').empty();
	$('#caller').hide();
	$('#peers').show();

	if (h === 'hand') localStorage.setItem("callerNotAutoShow", 'yes');

	setCallerPos();

}

function CallPopup(id) {

	$('#p' + id).toggleClass('hidden');

}

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

	document.cookie = 'width=' + w;
	document.cookie = 'height=' + h;

}

function asUser(old, user) {

	if (user !== '') {
		document.cookie = 'asuser=' + user;
		document.cookie = 'old=' + old;
	}
	else {
		document.cookie = 'asuser=';
		document.cookie = 'old=';
	}

	window.location.reload();
	//setTimeout("window.location.reload(true)", 10);
}

function deleteFilebox(id) {
	$('#' + id + ' #file\\[\\]').val('');
}

function checkuser(login) {

	var usermail = $('#' + login).val();
	var iduser = $('#iduser').val();
	var url = '/content/admin/usereditor.php?action=checkuser&email=' + usermail + '&iduser=' + iduser;

	$.post(url, function (data) {

		if (data) {

			$('#emailvalidate').removeClass('hidden').html('<i class="icon-thumbs-down-alt red"></i> <span class="red">' + data + '</span>');
			$('#submitbutton').addClass('hidden');
			$('#fakebutton').removeClass('hidden');
			$('#' + login).removeClass('green').addClass('red');

		}
		else {

			$('#emailvalidate').removeClass('hidden').html('<i class="icon-thumbs-up-alt green smalltxt"></i> <span class="green">Порядок</span>');
			$('#submitbutton').removeClass('hidden');
			$('#fakebutton').addClass('hidden');
			$('#' + login).removeClass('red').addClass('green');

		}
	});

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

function goodlink(url) {
	if (isMobile === false) {
		window.location.assign(url);
	}
}

function openlink(url) {

	window.open(url, '_blank');

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

/*Array.prototype.remove = function(value) {
	var idx = this.indexOf(value);
	if (idx != -1) {
		// Второй параметр - число элементов, которые необходимо удалить
		return this.splice(idx, 1);
	}
	return false;
};*/

function getTopOffset(e) {
	var y = 0;
	do {
		y += e.offsetTop;
	} while (e = e.offsetParent);
	return y;
}

/*Ссылки на просмотр или переход в карточку*/
function viewClient(id, hash) {

	if (!isMobile && !viewAsOpen)
		doLoad('/content/view/client.view.php?clid=' + id + '#' + hash);
	else
		openClient(id, hash);

	return false;

}

function openClient(id, hash) {

	var str = (hash) ? '#' + hash : '';

	if (!openFrame || isCard)
		window.open('/card.client?clid=' + id + str);

	else {

		$('#smframe').attr('src', 'card.client?clid=' + id + '&face=frame' + str);
		$('.smframe--container').css({"left": "0"});
		$('.smframe--url').removeClass('hidden').attr('data-url', '/card.client?clid=' + id + str);

	}

	return false;

}

function editClient(id, action) {

	if (action === '')
		action = 'edit';

	doLoad('/content/forms/form.client.php?clid=' + id + '&action=' + action);

	return false;

}

function expressClient(phone) {
	doLoad('/content/forms/form.client.php?action=express&phone=' + phone);
	return false;
}

function trashClient(id, action) {

	$.get('/content/core/core.client.php?action=client.' + action + '&clid=' + id, function () {

		if ($('#isCard').val() === 'yes')
			settab('0');

	});

}

function viewProfile(id) {

	doLoad('/content/card/card.profile.php?clid=' + id + '&action=profil');

}

function viewPerson(id) {

	if (!isMobile && !viewAsOpen || isCard)
		doLoad('/content/view/person.view.php?pid=' + id);

	else
		openPerson(id);

	return false;

}

function openPerson(id) {

	if (!openFrame || isCard)
		window.open('/card.person?pid=' + id);

	else {

		$('#smframe').attr('src', '/card.person?pid=' + id + '&face=frame');
		$('.smframe--container').css({"left": "0"});
		$('.smframe--url').removeClass('hidden').attr('data-url', '/card.person?pid=' + id);

	}

	return false;

}

function addPerson(id) {

	doLoad('/content/forms/form.person.php?action=add&clid=' + id);

	return false;

}

function editPerson(id, action) {

	if (action === 'undefined') action = 'edit';

	if (action !== 'change.user')
		doLoad('/content/forms/form.person.php?action=' + action + '&pid=' + id);
	else
		doLoad('/content/forms/form.client.php?action=' + action + '&pid=' + id);

	return false;
}

function PersonAdd(opt) {

	var $clid = (typeof opt === 'undefined') ? 0 : opt.clid;
	var $did = (typeof opt === 'undefined') ? 0 : opt.did;

	doLoad('/content/forms/form.person.php?action=add&clid=' + $clid + '&did=' + $did);

}

function viewDogovor(id, hash) {

	if (!isMobile && !viewAsOpen)
		doLoad('/content/view/deal.view.php?did=' + id + '#' + hash);

	else
		openDogovor(id, hash);

	return false;
}

function openDogovor(id, hash) {

	var str = '';

	if (!hash) {
		str = '';
	}
	else if (hash !== "undefined") {
		str = '#' + hash;
	}

	if (!openFrame || isCard) {
		window.open('/card.deal?did=' + id + str);
	}
	else {

		$('#smframe').attr('src', '/card.deal?did=' + id + '&face=frame' + str);
		$('.smframe--container').css({"left": "0"});
		$('.smframe--url').removeClass('hidden').attr('data-url', '/card.deal?did=' + id + str);

	}

	return false;

}

function editDogovor(id, action, step) {

	if (action === '') action = 'edit';

	if (!in_array(action, ['restore', 'delete', 'add', 'fromentry', 'change.step','change.unfreeze'])) doLoad('/content/forms/form.deal.php?did=' + id + '&action=' + action);

	else if (action === 'change.step') doLoad('/content/forms/form.deal.php?did=' + id + '&action=' + action + '&newstep=' + step);
	else if (action === 'add') doLoad('/content/forms/form.deal.php?clid=' + id + '&action=add');
	else if (action === 'fromentry') doLoad('/content/forms/form.deal.php?ide=' + id + '&action=add');
	else if (action === 'restore') {

		var url = '/content/core/core.deals.php?action=deal.restore&did=' + id;

		$.get(url, function () {
			window.location.href = 'card.deal?did=' + id;
		}, 'json');

	}
	else if (action === 'change.unfreeze') {

		var url = '/content/core/core.deals.php?action=deal.freeze&did=' + id;

		$.get(url, function () {
			window.location.href = 'card.deal?did=' + id;
		}, 'json');

	}

	return false;
}

function cloneDogovor(id) {

	doLoad('/content/forms/form.deal.php?odid=' + id + '&action=add');
	return false;

}

function editSpeca(id, action, did) {

	if (in_array(action, ['delete', 'change.calculate'])) {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

		var url = '/content/core/core.speca.php?action=' + action + '&did=' + did + '&spid=' + id;

		$.get(url, function (data) {

			var errors = '';

			if (data.error !== 'undefined' && data.error !== '' && data.error != null) errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			settab('0');
			settab('7');

		}, 'json');

	}
	else if (action === 'export') {

		window.location.href = '/content/core/core.speca.php?action=' + action + '&did=' + did;

	}
	else doLoad('/content/forms/form.speca.php?action=' + action + '&spid=' + id + '&did=' + did);

}

//===========================не сделано
function viewDogovorHealth(did) {

	doLoad('/content/lists/dt.health.php?did=' + did + '&action=view');
	return false;

}

/*удаление из карточки*/
function deleteCCD(tip, id, from) {

	var url, t;

	switch (tip) {
		case 'client':
			url = '/content/core/core.client.php';
			t = 'clid';
			break;

		case 'person':
			url = '/content/core/core.person.php';
			t = 'pid';
			break;

		case 'deal':
			url = '/content/core/core.deals.php';
			t = 'did';
			break;
	}

	var str = 'action=' + tip + '.delete&' + t + '=' + id;
	var urli = 'card.' + tip + '?' + t + '=' + id;

	$('#message').fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif">Выполняю. Пожалуйста подождите...</div>');

	$.get(url, str, function (data) {

		var text = '';

		if (data.result !== '') text = 'Результат: ' + data.result;
		if (data.error !== '') text += '<br>' + data.error;

		if (from === 'card') {

			$('#message').empty().fadeTo(1, 1).css('display', 'block').html(text);
			setTimeout(function () {
				$('#message').fadeTo(100, 0);
				window.location.href = urli;
			}, 5000);

		}
		else {

			$('#message').empty().fadeTo(1, 1).css('display', 'block').html(text);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			if (tip === 'person' && data.result !== '') settab('2');

		}

	}, 'json');

}

/*счета и акты*/
function editCredit(id, action) {

	if (in_array(action, ['credit.add', 'credit.express'])) {

		doLoad('/content/forms/form.deal.php?did=' + id + '&action=' + action);

	}
	else if (action === 'credit.view') {

		window.open('/content/helpers/get.doc.php?action=invoice.print&crid=' + id + '&tip=print');

	}
	else if (in_array(action, ['credit.undoit', 'credit.delete'])) {

		var url = '/content/core/core.deals.php?crid=' + id + '&action=' + action;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src=/assets/images/loader.gif> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			var errors = '';

			if (data.error !== undefined && data.error !== '')
				errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json')
			.done(function () {

				if (typeof settab == 'function')
					settab('0', false);

				if (typeof settab == 'function')
					setTimeout(function () {
						settab('7', false);
					}, 1000);

			});
	}
	else {
		doLoad('/content/forms/form.deal.php?crid=' + id + '&action=' + action);
	}

}

function editAkt(action, id, type, did) {

	action = 'akt.' + action;

	if (action === 'akt.view')
		window.open('/content/helpers/get.doc.php?action=akt.print&deid=' + id + '&tip=print&did=' + did);

	else if (action !== 'akt.delete')
		doLoad('/modules/contract/form.contract.php?action=' + action + '&type=' + type + '&did=' + did + '&deid=' + id);

	else if (in_array(action, ['akt.delete'])) {

		var url = '/modules/contract/core.contract.php?deid=' + id + '&did=' + did + '&type=' + type + '&action=' + action;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			var errors = '';

			if (data.error !== undefined && data.error !== '')
				errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json')
			.done(function () {

				settab('15', false);

			});
	}

}

function doc2PDF(id, file, disposition, name, deid) {

	var str = 'action=getpdf&fid=' + id + '&file=' + file + '&name=' + name + '&disposition=' + disposition + '&deid=' + deid;

	$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Выполняю...</div>');

	$.get('/modules/contract/core.contract.php', str, function (data) {

		$('#message').fadeTo(1, 1).css('display', 'block').html('Создан файл: ' + data);
		setTimeout(function () {
			$('#message').fadeTo(1000, 0);
		}, 5000);

		if (typeof settab == 'function')
			settab('15', false);

	});

}

function editCPoint(id, action, did) {

	action = 'controlpoint.' + action;

	if (!in_array(action, ['controlpoint.doit', 'controlpoint.undoit', 'controlpoint.delete'])) {

		doLoad('/content/forms/form.deal.php?did=' + did + '&id=' + id + '&action=' + action);

	}
	else {

		var url = '/content/core/core.deals.php?id=' + id + '&action=' + action;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		$.get(url, function (data) {

			var errors = '';

			if (data.error !== undefined && data.error !== '' && data.error !== null)
				errors = '<br>Note: ' + data.error;

			configpage();
			cardload();

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json');

	}

}

/*задачи*/
function viewTask(id) {

	doLoad('/content/view/task.view.php?tid=' + id);
	return false;

}

function viewTaskList(datum, clid, did) {

	clid = parseInt(clid);
	did = parseInt(did);

	if (datum !== '')
		doLoad('/content/view/task.view.php?action=view&datum=' + datum);

	else if (clid > 0 || did > 0)
		doLoad('/content/view/task.view.php?clid=' + clid + '&did=' + did);

	return false;

}

function editTask(id, action = 'edit') {

	if (action === '')
		action = 'edit';

	doLoad('/content/forms/form.task.php?action=' + action + '&tid=' + id);

	return false;

}

function addTask(date, clid, pid, did) {

	if (date === 'undefined')
		date = '';

	doLoad('/content/forms/form.task.php?action=add&datum=' + date + '&clid=' + clid + '&pid=' + pid + '&did=' + did);

	return false;

}

function addTaskPlus(tip, clid, pid, did) {

	doLoad('/content/forms/form.task.php?action=add&tip=' + tip + '&clid=' + clid + '&pid=' + pid + '&did=' + did);

	return false;

}

function deleteTask(id) {

	$.get('/content/core/core.tasks.php?action=delete&tid=' + id, function () {

		if (typeof cardload == 'function')
			cardload();

		if ($display == 'calendar')
			configpage();

		if ($display == 'desktop') {
			//$('#todo').empty().load('/content/desktop/tasklist.php').append('<img src="/assets/images/loading.svg">');
			razdel();
		}

	});

}

function getDateTasks(pole) {

	if (!pole) pole = 'datum';

	let datum = $('#' + pole).val();
	let url = '/content/core/core.tasks.php?action=viewtasks&datum=' + datum;

	$('.datumTasks').load(url).show();

}

function getDateTasksNew(pole) {

	if (!pole) pole = 'datum';

	let datum = $('#' + pole).val();
	let url = '/content/core/core.tasks.php?action=viewtasksnew&datum=' + datum;

	$.get(url, function (data) {

		$('.taskcount').html(data.count);
		$('.datumTasks').find('.tagsmenu .blok').html(data.list);

	}, 'json');

}

function getWeekCalendar() {

	let $el = $('#swindow');
	let url = '/content/lists/sw.weekcalendar.php';

	$el.find('.footer').addClass('hidden');
	$el.find('.body').css({"heighr": "100vh"});
	$el.find('.header').html('Календарь на неделю');

	$el.find('.body').empty().append('<div id="loader" class="loader"><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

	$.get(url, function (data) {

		$el.find('.body').html(data);

	});

	$el.css('left', '0');

	swindow = true;

}

/*история*/
function viewHistory(id, date) {

	if (date === 'undefined') date = '';
	if (id === 'undefined') id = '';

	doLoad('/content/view/history.view.php?cid=' + id + '&datum=' + date);

	return false;

}

function addHistory(date, clid, pid, did) {

	if (date === 'undefined') date = '';

	doLoad('/content/forms/form.history.php?action=add&datum=' + date + '&clid=' + clid + '&pid=' + pid + '&did=' + did);

	return false;

}

function editHistory(id) {

	doLoad('/content/forms/form.history.php?action=edit&cid=' + id);
	return false;

}

function deleteHistory(id) {

	$.get('/content/core/core.history.php?action=delete&cid=' + id, function (data) {

		if (typeof cardload == 'function')
			cardload();

		$('#message').fadeTo(1, 1).css('display', 'block').html(data);

		setTimeout(function () {
			$('#message').fadeTo(1000, 0);
		}, 20000);

	});

}

function noLog() {

	$.post('/content/core/core.history.php?action=setparam&nolog=' + $('#nolog:checked').val(), function () {

		cardload(1);

	});

}

/*лиды*/
function editLead(id, action, phone) {

	if (!in_array(action, ['delete', 'export', 'view']))
		doLoad('/modules/leads/form.leads.php?id=' + id + '&action=' + action + '&phone=' + phone);

	else if (action === 'view')
		doLoad('/modules/leads/form.leads.php?action=view&id=' + id);

	else if (action === 'delete') {

		let errors = '';

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		$.get('/modules/leads/core.leads.php?id=' + id + '&action=' + action, function (data) {

			if (data.error !== undefined && data.error !== '')
				errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json')
			.done(function () {

				configpage();

			});
	}
	else if (action === 'export')
		window.open('/modules/leads/core.leads.php?action=' + action);

}

function openLead(id) {

	doLoad('/modules/leads/form.leads.php?action=view&id=' + id);

	return false;

}

/*обращения*/
function editEntry(id, action, phone) {

	if (!phone) phone = '';

	let clid = parseInt($('#ctitle #clid').val());

	if (!in_array(action, ['delete'])) {

		doLoad('/modules/entry/form.entry.php?id=' + id + '&action=' + action + '&phone=' + phone + '&clid=' + clid);

	}
	else if (in_array(action, ['delete'])) {

		let errors = '';

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Выполняю. Пожалуйста подождите...</div>');

		$.get('/modules/entry/core.entry.php?id=' + id + '&action=' + action, function (data) {

			if (data.error !== 'undefined' && data.error !== '')
				errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json')
			.done(function () {

				configpage();

			});
	}

}

/*обсуждения*/
function editComment(id, action, idparent) {

	let str = '';
	let url = '';

	if (action === 'add' && isCard) {

		let tip = $('#card').val();

		if (tip === 'client') str = '&clid=' + $('#clid').val();
		if (tip === 'person') str = '&pid=' + $('#pid').val();
		if (tip === 'dogovor') str = '&did=' + $('#did').val();
		if (tip === 'project') str = '&project=' + $('#idproject').val();

	}

	if (action === 'open') {

		window.open('/card.comments?comid=' + id);

	}
	else if (action === 'viewshort') {

		url = '/modules/comments/card.comments.php?action=commentlist.view&id=' + id;

		$(".ui-layout-content .nano").nanoScroller({scroll: 'top'});
		$('#messagediv').empty().append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка данных. Пожалуйста подождите...</div>');

		$('#kbmenu').empty();
		$('#themeid').val(id);

		$('#contentdiv table tr').removeClass('current');
		$('#contentdiv table tr[data-id="' + id + '"]').addClass('current');

		$.get(url, function (data) {

			$('#messagediv').html(data);

		})
			.done(function () {

				$('#kbmenu').html($('.kbaction').html());
				if (!isMobile) $(".ui-layout-content .nano").nanoScroller();
				$('.ui-layout-east').addClass('open');

			});

	}
	else if (in_array(action, ['delete', 'delete.card', 'subscribe', 'unsubscribe', 'close'])) {

		url = '/modules/comments/core.comments.php?action=' + action + '&id=' + id + '&idparent=' + idparent;

		$.post(url, function (data) {

			if (!in_array(action, ['delete.card', 'close'])) {

				if (isCard) {

					settab('12', false);

					$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
				else {

					if (parseInt(data.idparent) === 0)
						$('#themeid').val("0");

					configpage();

				}

			}
			else {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
				setTimeout(function () {

					$('#message').fadeTo(400, 0);

					if (isCard && $('#isComment').val() !== 'yes') {

						settab('12');

						$('#message').fadeTo(1, 1).css('display', 'block').html(data.result);
						setTimeout(function () {
							$('#message').fadeTo(1000, 0);
						}, 20000);

					}
					else if ($('#tar').is('input')) {

						editComment(id, 'viewshort', '');

					}
					else window.location.href = 'card.comments.php?comid=' + id;

					if (parseInt(data.idparent) === 0)
						$('#themeid').val("0");

					configpage();

				}, 500);

			}

			return true;

		}, 'json');

	}
	else
		doLoad('modules/comments/form.comments.php?id=' + id + '&idparent=' + idparent + '&action=' + action + '&' + str);

	return false;

}

function unsubscribeComment(id, iduser) {

	var url = '/modules/comments/core.comments.php?action=unsubscribe.user&iduser=' + iduser + '&mid=' + id;

	$.post(url, function (data) {

		if ($('#theme').is('div'))
			$('#theme').load('/modules/comments/card.comments.php?action=theme.card&comid=' + id);

		else if ($('#tar').is('input'))
			editComment(id, 'viewshort', '');

		else if (typeof settab === "function")
			settab('12', false);

		$('#message').css('display', 'block').html(data).fadeOut(10000);

		return false;

	});

}

function openComment(id) {

	if (!openFrame || isCard)
		window.open('/card.comments.php?comid=' + id);

	else {

		$('#smframe').attr('src', '/card.comments.php?comid=' + id + '&face=frame');
		$('.smframe--container').css({"left": "0"});
		$('.smframe--url').removeClass('hidden').attr('data-url', '/card.comments.php?comid=' + id);

	}

	return false;

}

function openProject(id, hash) {

	let str = (hash) ? '#' + hash : '';

	if (!openFrame || isCard)
		window.open('/card.projects?id=' + id + str);

	else {

		$('#smframe').attr('src', '/card.projects?id=' + id + '&face=frame' + str);
		$('.smframe--container').css({"left": "0"});
		$('.smframe--url').removeClass('hidden').attr('data-url', '/card.projects?id=' + id + str);

	}

	return false;

}

/*база знаний*/
function editKb(id, action) {

	let str = '';
	let url = '';

	if (action === 'open') {

		window.open('/modules/knowledgebase/print.knowledgebase.php?id=' + id);

	}
	else if (action === 'viewshort') {

		url = '/modules/knowledgebase/form.knowledgebase.php?action=viewshort&id=' + id;

		$(".ui-layout-content .nano").nanoScroller({scroll: 'top'});
		$('#messagediv').empty().append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка данных. Пожалуйста подождите...</div>');
		$('#kbmenu').empty();

		$('#contentdiv table tr').removeClass('current');
		$('#contentdiv table tr[data-id="' + id + '"]').addClass('current');

		$.get(url, function (data) {

			$('#messagediv').html(data);

		})
			.done(function () {

				$('#kbmenu').html($('.kbaction').html());
				if (!isMobile) $(".ui-layout-content .nano").nanoScroller();

				if (isMobile) {

					$('.ui-layout-east').addClass('open');

				}

			});

	}
	else if (in_array(action, ['delete', 'cat.delete', 'pin', 'unpin'])) {

		url = '/modules/knowledgebase/core.knowledgebase.php?action=' + action + '&id=' + id;

		$.post(url, function (data) {

			if (action !== 'cat.delete') {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				configpage();
			}
			else {

				doLoad('/modules/knowledgebase/form.knowledgebase.php?action=cat.list');

				let ids = $('#lmenu #idcat').val();

				$('.ifolder').load('/modules/knowledgebase/core.knowledgebase.php?action=catlist&id=' + ids, function () {
					$('.ifolder a [data-id=' + ids + ']').addClass('fol_it');
				});

			}

			return false;

		});

	}
	else doLoad('/modules/knowledgebase/form.knowledgebase.php?id=' + id + '&action=' + action + '&' + str);

	return false;

}

/*прайс*/
function editPrice(id, action) {

	let str = '';

	if($display === 'sklad' /*&& action === 'cat.list'*/){
		str = 'sklad=yes';
	}

	if (action === 'export') {

		str = $("#pageform").serialize();

		window.open('/modules/price/core.price.php?action=' + action + '&' + str);

	}
	else if (action === 'mass') {

		str = $("#cform").serialize() + '&' + $('#pageform').serialize();

		doLoad('/modules/price/form.price.php?action=' + action + '&' + str);

	}
	else if (in_array(action, ['delete', 'cat.delete'])) {

		$.post('/modules/price/core.price.php?action=' + action + '&id=' + id, function (data) {

			if (action !== 'cat.delete') {

				$('#message').fadeTo(1, 1).css('display', 'block').html(data);
				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

				configpage();
			}
			else {
				
				editPrice(0, 'cat.list');
				
				if($display === 'sklad') {
					
					$('.ifolder').load('modules/modcatalog/form.modcatalog.php?action=cat.list&id=' + id, function () {
						$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
					});
					
				}
				else{
					
					$('.ifolder').load('modules/price/core.price.php?action=catlist&id=' + id, function () {
						$('.ifolder a [data-id=' + id + ']').addClass('fol_it');
					});
					
				}

				//doLoad('/modules/price/form.price.php?action=cat.list');
				//$('.ifolder').load('modules/price/core.price.php?action=catlist');

			}

			return false;

		});

	}
	else doLoad('/modules/price/form.price.php?id=' + id + '&action=' + action + '&' + str);

}

/*файлы*/
function editUpload(id, action, opti) {

	let str = '';
	let folder = 0;

	if (isCard) {

		let clid = parseInt($('#ctitle #clid').val());
		let pid = parseInt($('#ctitle #pid').val());
		let did = parseInt($('#ctitle #did').val());

		if (clid > 0)
			str = str + '&clid=' + clid;

		if (pid > 0)
			str = str + '&pid=' + pid;

		if (did > 0)
			str = str + '&did=' + did;

	}

	if ($display === 'upload' && $folder > 0)
		folder = $folder;


	if (['delete', 'cat.delete'].includes(action)) {

		$.getJSON('/modules/upload/core.upload.php?action=' + action + '&id=' + id, function (data) {

			if (isCard) {
				settab('6', false);
			}
			else {

				if (action !== 'cat.delete') {

					$('#message').fadeTo(1, 1).css('display', 'block').html(data.message + data.error);
					setTimeout(function () {
						$('#message').fadeTo(1000, 0);
					}, 20000);

				}
				else {

					doLoad('/modules/upload/form.upload.php?action=cat.list');
					$('.ifolder').load('/modules/upload/core.upload.php?action=catlist');

				}

				configpage();

			}

			return false;

		});

	}

	else if (action === 'download')
		window.open('/content/helpers/get.file.php?fid=' + id);

	else
		doLoad('/modules/upload/form.upload.php?id=' + id + '&action=' + action + '&folder=' + folder + '&' + str);

}

function fileEdit(id, action) {

	if (action === 'undefined') action = 'add';

	if (in_array(action, ['delete'])) {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		$.get('/content/core/core.deals.php?crid=' + id + '&action=' + action, function (data) {

			let errors = '';

			if (data.error !== 'undefined' && data.error !== '')
				errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json')
			.done(function () {

				settab('6', false);

			});

	}
	else if (in_array(action, ['download'])) {

		window.open('/content/helpers/get.file.php?fid=' + id);

	}
	else doLoad('/upload/upload_form.php?fid=' + id + '&action=' + action);

	return false;
}

/*function fileInfo(id) {

	doLoad('upload/info_view.php?fid=' + id);

}*/

function fileDownload(id, name, disposition, oname) {

	window.open('/content/helpers/get.file.php?fid=' + id + '&file=' + name + '&disposition=' + disposition + '&oname=' + oname);

}

/*файлы*/
function editMaillist(id, action, opt) {

	let str = 'opt=' + opt;

	if (in_array(action, ['delete', 'tpl.delete'])) {

		$.post('/modules/maillist/core.maillist.php?action=' + action + '&id=' + id, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			configpage();

			return false;

		});

	}
	else
		doLoad('/modules/maillist/form.maillist.php?id=' + id + '&action=' + action + '&' + str);

}

/*здоровье*/
function getHealthModal(){

	doLoad('/content/desktop/dt.health.php?modal=true');

}

/*бюджет*/
function editBudjet(id, action, option) {

	let str = '&year=' + $('#year').val();

	if (in_array(action, ['export.budjet'])) {

		str = $("#pageform").serialize();

		window.open('/modules/finance/core.budjet.php?action=' + action + '&' + str);
		new DClose();

	}
	else if (in_array(action, ['delete', 'cat.delete1', 'undoit', 'unmove'])) {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		$.post('/modules/finance/core.budjet.php?action=' + action + '&id=' + id + str, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html(data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			new DClose();

			configpage();

			if (action === 'undoit')
				$("#stat").load('/modules/finance/stat.php');

			return false;

		});

	}
	else if (option !== undefined) {
		
		if( option.agent === NaN || option.agent === undefined ){
			option.agent = 0
		}
		
		if( option.xtip !== NaN || option.xtip !== undefined ){
			str = str + '&xtip=' + option.xtip
		}
		
		doLoad('/modules/finance/form.budjet.php?id=' + id + '&action=' + action + '&' + str + '&did=' + parseInt(option.did) + '&clid=' + parseInt(option.clid) + '&tip=' + option.tip + '&agent=' + parseInt(option.agent))
		
	}
	else{
		
		doLoad('/modules/finance/form.budjet.php?id=' + id + '&action=' + action + '&' + str);
		
	}

}

function viewBudjet(action, m, y, d, cat) {

	doLoad('/modules/finance/form.budjet.php?mon=' + m + '&years=' + y + '&do=' + d + '&cat=' + cat + '&action=' + action);

}

function editProvider(id, action, did, clid) {

	doLoad('/modules/finance/form.budjet.php?id=' + id + '&action=' + action + '&did=' + did + '&clid=' + clid);

}

function editProviderDeal(action, id, tip, did) {

	action = 'provider.' + action;

	if (action !== 'provider.delete') {
		doLoad('/content/forms/form.deal.php?action=' + action + '&tip=' + tip + '&id=' + id + '&did=' + did);
	}
	else if (in_array(action, ['provider.delete'])) {

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Загрузка данных. Пожалуйста подождите...</div>');

		$.get('/content/core/core.deals.php?id=' + id + '&did=' + did + '&action=' + action, function (data) {

			let errors = '';

			if (data.error !== 'undefined' && data.error !== '') errors = '<br>Note: ' + data.error;

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result + errors);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

		}, 'json')
			.done(function () {

				settab('0', false);
				settab('13', false);

			});

	}

}

/*план продаж*/
function editPlan(id, action) {

	let str = '&year=' + $('#year').val();

	if (in_array(action, ['export'])) {

		str = $("#pageform").serialize();

		window.open('/content/core/core.plan.php?action=' + action + '&' + str);
		new DClose();

	}
	else doLoad('/content/forms/form.plan.php?id=' + id + '&action=' + action + '&' + str);
}

/*Документы*/
function editContract(id, action, type, idtype) {

	let str = '';

	if (isCard) {

		let clid = parseInt($('#ctitle #clid').val());
		let pid = parseInt($('#ctitle #pid').val());
		let did = parseInt($('#ctitle #did').val());

		if (clid > 0) str = str + '&clid=' + clid;
		if (pid > 0) str = str + '&pid=' + pid;
		if (did > 0) str = str + '&did=' + did;

	}

	if (in_array(action, ['contract.delete'])) {

		$.post('/modules/contract/core.contract.php?action=' + action + '&id=' + id, function () {

			if (isCard)
				settab('15');
			else
				configpage();

			return false;

		});

	}
	else if (in_array(action, ['export'])) {

		str = $("#pageform").serialize();

		window.open('/modules/contract/core.contract.php?action=' + action + '&id=' + id + '&' + str);
		new DClose();

	}
	else if (in_array(action, ['payment.export','akt.export'])) {

		str = $("#pageform").serialize();

		doLoad('/modules/contract/form.contract.php?&action=' + action + '&' + str);

	}
	else
		doLoad('/modules/contract/form.contract.php?id=' + id + '&action=' + action + '&type=' + type + '&idtype=' + idtype + '&' + str);

}

/*Группы*/
function editGroup(id, action) {

	let str = '';
	let gid = '';
	let url = '';

	if (isCard) {

		let clid = parseInt($('#ctitle #clid').val());
		let pid = parseInt($('#ctitle #pid').val());
		let did = parseInt($('#ctitle #did').val());

		if (clid !== undefined && clid > 0)
			str = str + '&clid=' + clid;

		if (pid !== undefined && pid > 0)
			str = str + '&pid=' + pid;

		if (did !== undefined && did > 0)
			str = str + '&did=' + did;

	}

	if (action === 'mass') {

		str = $("#cform").serialize();
		gid = $('#gid').val();
		url = '/modules/group/form.group.php?action=mass&gid=' + gid + '&';

		doLoad(url + str);

		$('.multi--buttons').addClass('hidden');

	}
	else if (action === 'export') {

		gid = $('#gid').val();
		url = '/modules/group/core.group.php?action=export&gid=' + gid + '&';

		window.open(url);

	}
	else doLoad('/modules/group/form.group.php?id=' + id + '&action=' + action + '&' + str);

}

function removeFromList(id, gid) {

	var str = '';

	if (isCard) {

		var clid = parseInt($('#ctitle #clid').val());
		var pid = parseInt($('#ctitle #pid').val());

		if (clid !== undefined && clid > 0) str = str + '&clid=' + clid;
		if (pid !== undefined && pid > 0) str = str + '&pid=' + pid;

	}

	var url = '/modules/group/core.group.php?action=removefromGroup&id=' + id + '&gid=' + gid + str;
	$.post(url, function (data) {

		$('#tabgroup').load('/content/card/card.group.php?clid=' + clid + '&pid=' + pid);
		$('#message').css('display', 'block').html(data);

	})
		.done(function(){
			configpage();
		});

}

/*вспомогательные*/
/**
 * Устанавливает Важность/Срочность для напоминаний
 * @param tip
 * @param vlu
 */
function setPS(tip, vlu) {

	$('.' + tip + ' .but').removeClass('active');

	if (tip === 'speed')
		$('#sp' + vlu).addClass('active');
	if (tip === 'priority')
		$('#pr' + vlu).addClass('active');

	if ($('#' + tip).is('input'))
		$('#' + tip).val(vlu);

	else if ($('#todo\\[' + tip + '\\]').is('input'))
		$('#todo\\[' + tip + '\\]').val(vlu);


}

function getBik(b) {

	var bik = (!b) ? $('#recv\\[castBankBik\\]').val() : b;
	var url = '/content/helpers/client.helpers.php?action=getBIK&bik=' + bik;

	if (bik !== '') {

		$('#limit').append('<img src="/assets/images/loading.svg" height="10">');

		$.getJSON(url, function (obj) {

			if (obj.name !== '') {

				$('#recv\\[castBank\\]').val(obj.name + ', ' + obj.city);
				$('#recv\\[castBankKs\\]').val(obj.ks);
				//$('#limit').html('Дата изменения <b>' + obj.datechange + '</b>');

			}
			else Swal.fire('Проблемы соединения с сервером', '', 'warning');

		});

	}
	else Swal.fire('Укажите БИК банка', '', 'warning');

	$('#limit').empty();

}

function getOtrasli(selected) {

	let url = '';
	let tip = $('#client\\[type\\]').val();

	if (tip === 'undefined' || tip === undefined) {

		tip = $('#type option:selected').val();
		url = '/content/helpers/client.helpers.php?action=getOtrasli&tip=' + tip + '&selected=' + parseInt(selected);

		$.post(url, function (data) {
			$('#idcategory').html(data);
		});

	}
	else {

		if(tip === 'person'){
			$('#client\\[title\\]').attr('placeholder', 'Например: Иванов Иван');
		}
		else{
			$('#client\\[title\\]').attr('placeholder', 'Например: Сейлзмен, ООО');
		}

		url = '/content/helpers/client.helpers.php?action=getOtrasli&tip=' + tip + '&selected=' + parseInt(selected);

		$.post(url, function (data) {
			$('#client\\[idcategory\\]').html(data);
		});

	}

}

function add_sprav(action, poleid) {

	var element = $('#' + poleid).closest('div');
	var w = element.find('select').width() + 10;
	var l = element.find('select').position().left + 5;

	element.append('<div id="orgspisok" style="left: ' + l + 'px"><INPUT name="poletitle" type="text" id="poletitle" style="width:' + w + 'px;" value=""><hr><div class="text-right"><A href="javascript:void(0)" onClick="addpole(\'' + action + '\',\'' + poleid + '\')" class="button">Добавить</A>&nbsp;<A href="javascript:void(0)" onClick="$(\'#orgspisok\').remove();" class="button">Отмена</A></div></div>');

}

function addpole(action, poleid) {

	var title = $('#poletitle').val();
	var field = $('.typeselect option:selected').val();

	poleid = poleid.replace("[", "\\[").replace("]", "\\]");

	var url = '/content/core/core.client.php?action=client.add' + action + '&title=' + title + '&tip=' + field;
	$.post(url, function (data) {

		$('#orgspisok').remove();
		if (data !== '') {
			$('#' + poleid).append('<option value="' + data + '" selected>' + title + '</option>');
		}
		else {
			$("#" + poleid + " :contains('" + title + "')").attr('selected', 'selected');
		}

		return true;

	});

}

/**
 * Поиск записей в формах
 * @param formelement
 * @param divname
 * @param url
 * @param pname
 * @param put
 * @returns {boolean}
 */
function get_orgspisok(formelement, divname, url, pname, put = '') {

	spisok_remove();

	var $element = $('#' + formelement);
	var $goal = $('#' + divname);
	var $clid = parseInt($('#clid').val());

	var atop = $element.position().top - 8;
	var aleft = $element.position().left - 5;
	var awidth = $element.width() + 5;
	var text;

	//if (awidth < 450) awidth = 500;

	if (divname !== 'place') {

		$goal.closest('div').append('' +
			'<div id="orgspisok">' +
			'   <div class="poleinput">' +
			'       <INPUT name="asearch" type="text" id="asearch" class="wp100 spisoksearch" value="" title="Поиск" placeholder="Начните вводить название" data-url="'+url+'" data-input="'+formelement+'" data-pname="'+pname+'" data-field="'+divname+'">' +
			'   </div><br>' +
			'   <div class="pole" id="pole"></div><hr>' +
			'   <div class="text-right"><A href="javascript:void(0)" onClick="spisok_remove()" class="button">Отмена</A></div>' +
			'</div>' +
			'');

	}
	else {

		atop = $element.position().top - 10;
		aleft = $element.position().left - 10;
		awidth = $element.width() - 20;

		$goal.append('' +
			'<div id="orgspisok">' +
			'   <div class="poleinput">' +
			'       <INPUT name="asearch" type="text" id="asearch" class="wp100 spisoksearch" value="" title="Поиск" placeholder="Начните вводить название" data-url="'+url+'" data-input="'+put+'" data-pname="'+pname+'" data-field="'+divname+'">' +
			'   </div><br>' +
			'   <div class="pole" id="pole"></div><hr>' +
			'   <div class="text-right"><A href="javascript:void(0)" onClick="spisok_remove()" class="button">Отмена</A></div>' +
			'</div>');

	}

	$('#orgspisok').css({'width': awidth + 'px', 'left': aleft + 'px', 'display': 'block', 'top': atop + 'px'});

	if (put !== '' && $clid > 0 && pname === 'pidd') {

		$('#orgspisok #pole').append('<div id="loader" class="loader">Загрузка данных...</div>').load(url + '&' + pname + '=' + $('#' + pname).val() + '&pname=' + pname + '&felement=' + formelement + '&clid=' + $('#clid').val());

	}

	$('#asearch').focus();

	if (formelement === 'lst_spisokp') text = 'ФИО Контакта';
	else if (formelement === 'lst_spisok') text = 'Названия Клиента';
	else if (formelement === 'lst_payer') text = 'Названия Плательщика';
	else text = 'Название';

	$('#orgspisok #pole').append('Для поиска начните набор <b class="blue">' + text + '</b>');

	return false;

}

/**
 * @deprecated Поиск
 * @param formelement
 * @param divname
 * @param url
 * @param pname
 */
function spisok_search(formelement, divname, url, pname) {

	let $el = $('#asearch');

	let w = urlEncodeData($el.val());
	let s = $el.val().length;
	let furl = url + '&word=' + w + '&pname=' + pname + '&felement=' + formelement + '&clid=' + $('#clid').val();

	if (s > 3) {

		$('#orgspisok #pole').empty().append('<div id="loader" class="loader">Загрузка данных...</div>').load(furl);

	}

}

function spisok_select(pname, formelement) {

	var lid = $('input[name=lid]:checked').val();
	var txt = $('#txt' + lid).html();
	var clid = $('#clid').val();
	var pid = $('#pid').val();

	//console.log(pname);
	//console.log(formelement);

	pname = pname.replace("[", "\\[").replace("]", "\\]");
	formelement = formelement.replace("[", "\\[").replace("]", "\\]");

	$('input[id=' + pname + ']').val(lid);
	$('input[id=' + formelement + ']').val(txt);

	$('#orgspisok').remove();

	if ( $('#persons').is('div') ) {

		var payer = $('#payer').val();
		var plist = $('#plist').val();

		$.get('/content/helpers/deal.helpers.php?action=get.personsplus&clid=' + clid + '&payer=' + payer + '&plist=' + plist, function (data) {

			$('#pid_list').empty().html(data);
			$('#persons').find(".multiselect").multiselect('destroy').multiselect({
				sortable: true,
				searchable: true
			});
			$(".connected-list").css('height', "200px");

		});

		// $('#dog_num').load('/content/helpers/deal.helpers.php?clid=' + clid + '&pid=' + pid + '&action=get.contracts');

	}

	return true;

}

function spisok_remove() {
	$('#orgspisok').remove();
}

/**
 Блок редактирования одиночного поля Клиента, Сделки
 */

// Редактирование поля карточки
function edit_field(param, divname, tip, id, add) {

	var tid = 'clid';
	var $element;
	var $goal;
	var buttons;

	field_close();

	if (param === "deal")
		tid = 'did';

	if (tip === 'inputlist')
		tip = 'select';

	if (!add) {

		$element = $('#field-' + divname);
		$goal = $('#' + divname);

	}
	else {

		$element = $('#field-append');
		$goal = $('#append');

	}

	var atop = $element.position().top - 8;
	var aleft = $element.position().left - 5;
	var awidth = $element.width() + 20;

	buttons = '<div class="text-right button--pane"><A href="javascript:void(0)" onClick="saveField(\'' + param + '\')" class="button bluebtn m0 mr10 wp30">Сохранить</A><A href="javascript:void(0)" class="button cancelbtn m0 wp30" onClick="field_close()">Отмена</A></div>';

	if (divname === "append") {

		aleft -= 4;
		atop += 8;

		$goal.closest('div').append('<div id="editfield" class="box--child" style="display:none"><a href="javascript:void(0)" onclick="field_close()" title="Скрыть" class="gray fldclose width-unset"><i class="icon-cancel"></i> Скрыть</a><form action="" method="post" enctype="multipart/form-data" name="fieldAdd" id="fieldAdd" autocomplete="off"><input name=' + tid + ' type="hidden" id=' + tid + ' value=' + id + '><input name="field" type="hidden" id="field" value="new"><input type="hidden" id="action" name="action" value="getFieldElement"><div id="pole"></div></form>' + buttons + '</div>');

	}
	else {

		if (tip === "multiselect") {

			atop += 8;
			buttons = '';

		}

		$goal.closest('div').append('<div id="editfield" class="box--child" style="display:none"><a href="javascript:void(0)" onclick="field_close()" title="Скрыть" class="gray fldclose width-unset"><i class="icon-cancel"></i> Скрыть</a><form action="" method="post" enctype="multipart/form-data" name="fieldForm" id="fieldForm" autocomplete="off"><input name=' + tid + ' type="hidden" id=' + tid + ' value=' + id + '><input name="field" type="hidden" id="field" value=' + divname + '><input type="hidden" id="action" name="action" value="' + param + '.change.field"><div id="pole"></div></form>' + buttons + '</div>');

	}

	$.get('/content/helpers/' + param + '.helpers.php?action=getFieldElement&fldtip=' + tip + '&fldvals=' + divname + '&' + tid + '=' + id, function (data) {

		$('#editfield').css({
			'width': awidth + 'px',
			'left': aleft + 'px',
			'display': 'block',
			'top': atop + 'px'
		});

		$('#pole').html('<div class="uppercase Bold fs-07 gray">Выбор значения</div>' + data);

	});

	return false;

}

function field_close() {

	$('#editfield').remove();

}

// Сохранение значения поля
function saveField(tip) {

	var str = $('#fieldForm').serialize();

	if (tip === 'deal')
		tip = 'deals';

	$('#editfield').addClass('hidden');

	$.post('/content/core/core.' + tip + '.php', str, function (data) {

		$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);
		setTimeout(function () {
			$('#message').fadeTo(1000, 0);
		}, 2000);

		settab('0', false);
		//isset.push(0);

	});

}

// Добавление поля
function appendField(param, id) {

	var str = $('#fieldAdd').serialize();

	$.get('/content/helpers/' + param + '.helpers.php', str, function (data) {

		edit_field(param, data.name, data.type, id, 'new');

	}, "json");

}

function viewUser(id) {
	doLoad('/content/ajax/user.info.php?iduser=' + id);
	return false;
}

/*Смена месяца в календарике*/
function changeMounth(direct) {

	var m = parseInt($('#sm').val());
	var y = parseInt($('#sy').val());
	var str = '';

	if (direct === 'back') {

		if (m === 1) {
			m = 12;
			y = y - 1;
		}
		else m = m - 1;

	}
	else if (direct === 'next') {

		if (m === 12) {
			m = 1;
			y = y + 1;
		}
		else m = m + 1;

	}

	$('#m').val(m);
	$('#y').val(y);

	$('#sm').val(m);
	$('#sy').val(y);

	if (y > 0)
		str = 'y=' + y + '&m=' + m;

	if ($('#tar').is('input'))
		str = str + '&tar=' + $('#tar').val() + '&iduser=' + $('#iduser option:selected').val();

	if ($('#tsk_tip').is('form'))
		str = str + '&' + $('#tsk_tip').serialize();

	$("#calendar").append('<div id="loader" class="pull-right"><img src="/assets/images/loading.svg" width="12"></div>');

	//$('.nano').nanoScroller({ destroy: true });

	$.ajax({
		type: "GET",
		url: "/content/lists/lp.calendar.php?" + str,
		data: str,
		success: function (viewData) {

			$("#calendar").html(viewData);

			if ($display === 'calendar')
				configpage();

			if ($('#task').is('div')) {

				$.get({
					type: "GET",
					url: "/content/lists/lp.tasksweek.php",
					data: str,
					success: function (viewData) {

						$("#task").html(viewData);

						//desktopTaskHeight();

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

		}
	});

	//calendarload = 'ok';

	return true;

}

function thisMounth() {

	$('#calendar #sm').val('');
	$('#calendar #sy').val('');
	changeMounth();

}

function taskWeek(direct) {

	var m = parseInt($('#calendar #sm').val());
	var y = parseInt($('#calendar #sy').val());

	if (y > 0) var str = 'y=' + y + '&m=' + m + '&old=' + direct;

	//$('#task').append('<div id=loader><img src=images/loading.svg></div>');

	$.get("/content/lists/lp.tasksweek.php", str, function (data) {
		$("#task").html(data);
	})
		.done(function () {
			desktopTaskHeight();
			if (!isMobile) $(".nano").nanoScroller();
		});

}

/**
 * @deprecated
 * @returns {boolean}
 */
function startSearchPop() {

	var word = $('#search').val();

	var str = $('#searchForm').serialize();
	var leng = word.length;
	var url = '/content/ajax/searchPanel.php?' + str;
	var arr = [];

	if (leng < 3) {

		$('#searchResult').html('Введите не менее 3 символов');
		return false;

	}

	//Получаем выбранные параметры поиска
	$('#searchForm input:checkbox:checked').each(function () {
		arr.push($(this).val());
	});

	//Записываем в куки параметры
	setCookie("paramsSearch", JSON.stringify(arr));

	$('#searchResult').empty().append('<img src="/assets/images/loading.svg" height="12"> Поиск. Пожалуйста подождите...');

	$.post(url, function (data) {

		$('#search').val(data.search);

		if (data.error === 'true') $('#paramsSearch').css({
			"border": "2px solid rgb(204, 36, 36)",
			"color": "rgb(204, 36, 36)"
		});
		else $('#paramsSearch').css({"border": "none", "color": "rgb(84, 110, 122)"});

		$('#searchResult').html(data.result);


		$('.popcontent').find('.viewdiv').addClass('hidden');

		return true;

	}, "json");
}

function uniSearchPop() {

	//e.preventDefault();
	//e.stopPropagation();

	var word = $('#unisearch').val();
	var str = $('#searchForm').serialize();
	var leng = word.length;
	var url = '/content/helpers/universal.search.php?' + str;
	var arr = [];
	var $elm = $('#searchResult');

	//console.log(str)

	$.Mustache.load('/content/tpl/unisearch.mustache');

	if (leng < 3) {

		$('#searchResult').html('Введите не менее 3 символов');
		return false;

	}

	//Получаем выбранные параметры поиска
	$('#searchForm input:checkbox:checked').each(function () {
		arr.push($(this).val());
	});

	//Записываем в куки параметры
	setCookie("paramsSearch", JSON.stringify(arr));

	$elm.empty().append('<img src="/assets/images/loading.svg" height="12"> Поиск. Пожалуйста подождите...');

	$.getJSON(url, function (data) {

		$('#search').val(data.search);

		if (data.error === true)
			$('#paramsSearch').css({
				"border": "2px solid rgb(204, 36, 36)",
				"color": "rgb(204, 36, 36)"
			});
		else
			$('#paramsSearch').css({"border": "none", "color": "rgb(84, 110, 122)"});

		$elm.empty().mustache('uniSearchTpl', data);

		$('.popcontent').find('.viewdiv').addClass('hidden');

		return true;

	});

}

/* Автосмена раскладки клавиатуры
		arrow:  0 - перевод (рус -> eng, eng -> рус)
				1 - перевод (eng -> рус)
				2 - перевод (рус -> eng)
	 */
function AutoChangeLang(text, arrow) {

	var str = [], newstr = [];

	str[0] = {
		'й': 'q',
		'ц': 'w',
		'у': 'e',
		'к': 'r',
		'е': 't',
		'н': 'y',
		'г': 'u',
		'ш': 'i',
		'щ': 'o',
		'з': 'p',
		'х': '[',
		'ъ': ']',
		'ф': 'a',
		'ы': 's',
		'в': 'd',
		'а': 'f',
		'п': 'g',
		'р': 'h',
		'о': 'j',
		'л': 'k',
		'д': 'l',
		'ж': ';',
		'э': '\'',
		'я': 'z',
		'ч': 'x',
		'с': 'c',
		'м': 'v',
		'и': 'b',
		'т': 'n',
		'ь': 'm',
		'б': ',',
		'ю': '.',
		'Й': 'Q',
		'Ц': 'W',
		'У': 'E',
		'К': 'R',
		'Е': 'T',
		'Н': 'Y',
		'Г': 'U',
		'Ш': 'I',
		'Щ': 'O',
		'З': 'P',
		'Х': '[',
		'Ъ': ']',
		'Ф': 'A',
		'Ы': 'S',
		'В': 'D',
		'А': 'F',
		'П': 'G',
		'Р': 'H',
		'О': 'J',
		'Л': 'K',
		'Д': 'L',
		'Ж': ';',
		'Э': '\"',
		'Я': 'Z',
		'ч': 'X',
		'С': 'C',
		'М': 'V',
		'И': 'B',
		'Т': 'N',
		'Ь': 'M',
		'Б': '<',
		'Ю': '>'
	};
	str[1] = {
		'q': 'й',
		'w': 'ц',
		'e': 'у',
		'r': 'к',
		't': 'е',
		'y': 'н',
		'u': 'г',
		'i': 'ш',
		'o': 'щ',
		'p': 'з',
		'[': 'х',
		']': 'ъ',
		'a': 'ф',
		's': 'ы',
		'd': 'в',
		'f': 'а',
		'g': 'п',
		'h': 'р',
		'j': 'о',
		'k': 'л',
		'l': 'д',
		';': 'ж',
		'\'': 'э',
		'z': 'я',
		'x': 'ч',
		'c': 'с',
		'v': 'м',
		'b': 'и',
		'n': 'т',
		'm': 'ь',
		',': 'б',
		'.': 'ю',
		'Q': 'Й',
		'W': 'Ц',
		'E': 'У',
		'R': 'К',
		'T': 'Е',
		'Y': 'Н',
		'U': 'Г',
		'I': 'Ш',
		'O': 'Щ',
		'P': 'З',
		'{': 'Х',
		'}': 'Ъ',
		'A': 'Ф',
		'S': 'Ы',
		'D': 'В',
		'F': 'А',
		'G': 'П',
		'H': 'Р',
		'J': 'О',
		'K': 'Л',
		'L': 'Д',
		':': 'Ж',
		'\"': 'Э',
		'Z': 'Я',
		'X': 'ч',
		'C': 'С',
		'V': 'М',
		'B': 'И',
		'N': 'Т',
		'M': 'Ь',
		'<': 'Б',
		'>': 'Ю'
	};

	for (var j = 0; j <= 1; j++)

		if (arrow == undefined || arrow == j)

			for (var i = 0; i < text.length; i++)

				if (str[j][text[i]]) newstr[i] = str[j][text[i]];

	for (var i = 0; i < text.length; i++)

		if (!newstr[i]) newstr[i] = text[i];

	return newstr.join('');

};

function comments() {
	$("#commnum").load("/modules/comments/card.comments.php?action=numpanel");
}

function leads() {
	$("#leadnum").load("/content/vigets/notify.leads.php?action=get_leadskol");
}

function yNotifyMe(data) {

	data = data.split(",");
	var title = data[0];
	var content = data[1];
	var img = data[2];
	var id = data[3];
	var url = data[4];
	//var notification = new Notification('',{});
	var notification;

	console.log(data);

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
						notification = new Notification(title, {
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

/* Mailer. Новое*/
var $mailer = {

	edit: function(id) {

		doLoad('/modules/mailer/editor.php?action=compose&id=' + id);

	},

	check: function(){

		$.post('/modules/mailer/core.mailer.php?action=lastmessage', function (data) {

			if (data !== '')
				$('#mails').html(data);
			else
				$('#mails').html('<div class="p5">нет сообщений</div>');

			$mailer.count();

		});

	},

	count: function(){

		$.post('/modules/mailer/core.mailer.php?action=lastmessage.count', function (data) {

			if (parseFloat(data) > 0)
				$('#countEmail').html(data).removeClass('gray').addClass('green');

			else
				$('#countEmail').html('0').removeClass('green').addClass('gray');


		});

	},

	compose: function(id, way) {

		if (!way) way = '';
		doLoad('/modules/mailer/editor.php?action=compose&id=' + id + '&way=' + way);

	},

	composeCard: function(c, p, e) {

		doLoad('/modules/mailer/editor.php?action=compose&clid=' + c + '&pid=' + p + '&email=' + e);

	},

	get: function(hand) {

		var url = '/modules/mailer/core.mailer.php?action=getmessage';
		var date = new Date();

		var thistime = date.getTime();//Date.now();

		//временная метка предыдущей проверки
		var oldtime = parseInt(localStorage.getItem("ymailTimer"));
		var ymail = localStorage.getItem("ymail");

		var delta = thistime - oldtime + 100;
		//период проверки почты
		var ytime = $yperiod;

		//если проверки не было очень долго, а статус не меняется, то сбрасываем его
		if (delta > 900000)
			ymail = '';

		//console.log("thistime: " + thistime);
		//console.log("oldtime: " + oldtime);
		//console.log("ymail: " + ymail);
		//console.log("ymail delta: " + delta);
		//console.log("ymail ytime: " + ytime);
		//console.log("ymail: " + ymail);

		//запускаем, только если прошло заданное время и в данный момент почта не проверяется или если проверка запущена вручную
		if (isNaN(delta) || (delta > ytime && ymail !== 'work') || hand === 'yes') {

			//сбросим на текущее время, чтобы из второго окна не запустилась проверка
			localStorage.setItem("ymailTimer", date.getTime().toString());
			//localStorage.setItem("ymailTimer", Date.now());

			if (hand === 'yes')
				yNotifyMe("CRM. Проверяю почту,Начинаю прием писем,good.png");

			$('#mailIndicator').toggleClass('icon-mail-alt icon-arrows-ccw').addClass('icon-rotate');

			localStorage.setItem("ymail", "work");

			$.post(url + '&box=inbox', function (data) {

				if (data.result !== '' && data.error !== "undefined") {

					if (data.count > 0) {
						yNotifyMe("CRM. Новое письмо," + striptags(data.result) + ",newmail.png," + data.lastid);
					}
					if (typeof configmpage === 'function' && data.count > 0) {
						configmpage();
					}

				}
				else if( data.error ){

					var stringOne = data.error;
					var search = stringOne.indexOf("login");

					if (search > 0) {

						Swal.fire({
							title: "Загрузка почты невозможна!",
							text: "Ошибка: неверный логин и/или пароль!",
							type: 'error',
						});

					}
					else {
						
						Swal.fire({
							title: "Загрузка почты невозможна!",
							text: "Ошибка: проблемы авторизации",
							type: 'error',
						});
						
					}
					

				}

			}, 'json')
				.done(function () {

					$.post(url + '&box=send', function (data) {

						if (data.result !== '') {

							//уведомление о папке отправленные не будем показывать
							if (data.messageid !== '')
								$('#mid').val('');

							if (typeof configmpage === 'function' && data.count > 0)
								configmpage();

						}

						//установим временную метку, когда закончили проверку ящика
						localStorage.setItem("ymailTimer", date.getTime().toString());
						localStorage.setItem("ymail", "wait");

						$('#mailIndicator').toggleClass('icon-mail-alt icon-arrows-ccw').removeClass('icon-rotate');

					}, 'json');

				});

		}

	},

	send: function(id) {

		var url = '/modules/mailer/core.mailer.php?action=sendmessage&id=' + id;

		yNotifyMe("CRM. Отправка сообщения,Начинаю отправку письма,good.png");

		$.post(url, '', function (data) {

			yNotifyMe("CRM. Отправка письма," + data.result + ",sendmail.png");

			if ($('.messagelist[data-id="' + $messageid + '"]').is('div') === false) {

				$messageid = $('.messagelist:first').data('id');
				$('#mid').val($messageid);

			}

			configmpage();

		}, 'json');

	},

	action: function(id, tip) {

		var url = '/modules/mailer/core.mailer.php?action=getaction&id=' + id + '&tip=' + tip;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loader.gif"> Выполняю...</div>');

		if (in_array(tip, ['delete', 'trash'])) {

			var next = $('.messagelist[data-id="' + id + '"]').next().data('id');

			$('#mid').val(next);
			$messageid = parseInt(next);

		}

		if (typeof DClose === 'function') new DClose();

		$.getJSON(url, function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);

			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			if (typeof configmpage === 'function') configmpage();
			if (typeof $mailer.check === 'function') $mailer.check();

			$('#contentdiv').find('.mcheck:enabled').prop('checked', false);

		});

	},

	preview: function(id) {

		$.Mustache.load('/modules/mailer/tpl/interface.mustache');

		$(function () {
			
			$('#dialog').css({'width':'80%'}).center();

			var url = '/modules/mailer/editor.php?id='+id+'&action=view';

			$.getJSON(url, function (data) {

				$('#resultdiv').empty().mustache('preview', data);

			})
				.done(function () {

					doLoadAfter();
					
					$('#dialog').center();

					$mailer.check();
					$mailer.count();
					$mailer.formatQuoteDialog();

					$('#cont a').attr("target", "_blank");

					/*$(document).off('click', '.picpreview');
					$(document).on('click', '.picpreview', function () {

						var img = $(this).find('img');
						var src = img.attr('src'); // Достаем из этого изображения путь до картинки

						$(".viewblock").append('' +
							'<div class="popup--block">' + //Добавляем в тело документа разметку всплывающего окна
							'   <div class="popup--bg"></div>' + // Блок, который будет служить фоном затемненным
							'   <img src="' + src + '" class="popup--img hand" onclick="$(\'.popup\').remove()">' + // Само увеличенное фото
							'</div>');

						$(".popup").fadeIn(400); // выводим изображение
						$(".popup_bg").on('click', function () {    // Событие клика на затемненный фон

							$(".popup").fadeOut(400);    // убираем всплывающее окно

							setTimeout(function () {    // Выставляем таймер
								$(".popup").remove(); // Удаляем разметку всплывающего окна
							}, 800);

						});

					});*/

				});

		});

	},

	addClientExpress: function(id, rid) {

		doLoad('/content/forms/form.client.php?action=express&messageid=' + id + '&rid=' + rid);

	},

	addClient: function(id, rid) {

		doLoad('/content/forms/form.client.php?action=add&messageid=' + id + '&rid=' + rid);

	},

	addContact: function(id, rid) {

		doLoad('/content/forms/form.person.php?action=add&messageid=' + id + '&rid=' + rid);

	},

	addLead: function(id) {

		doLoad('/modules/leads/form.leads.php?messageid=' + id + '&action=add');

	},

	addKnowledge: function(id) {

		doLoad('/modules/knowledgebase/form.knowledgebase.php?messageid=' + id + '&action=edit');

	},

	addTask: function(id) {

		doLoad('/content/forms/form.task.php?action=add&messageid=' + id);

	},

	toDeal: function(id) {

		doLoad('/modules/mailer/editor.php?action=todeal&id=' + id);

	},

	toClient: function(id, email) {

		doLoad('/modules/mailer/editor.php?action=toclient&id=' + id + '&email=' + email);

	},

	toContact: function(id, email) {

		doLoad('/modules/mailer/editor.php?action=tocontact&id=' + id + '&email=' + email);

	},

	previewImage: function() {

		var list = '';

		$('#fileList').find('a[data-tip="pic"]').each(function () {

			var file = $(this).data('file');
			var name = $(this).data('fname');

			list += "<div class=\"picpreview hand relativ\" style=\"background: url('/files/" + file + "') no-repeat center center;\" onclick=\"window.open('/files/" + file + "')\" title=\"Открыть в новом окне\" data-file='file'><span class=\"fs-09 bottom\">" + name + "</span></div>";

		});

		$('.ymImagePreview').empty().html(list);

	},

	previewImageCard: function() {

		$('.msglist').find('.msg').each(function(){

			var list = '';

			$(this).find('#fileList').find('a[data-tip="pic"]').each(function () {

				var file = $(this).data('file');
				var name = $(this).data('fname');

				list += "<div class=\"picpreview hand relativ\" style=\"background: url('/files/" + file + "') no-repeat center center;\" onclick=\"window.open('/files/" + file + "')\" title=\"Открыть в новом окне\"><span class=\"fs-09 bottom\">" + name + "</span></div>";

			});

			$(this).find('.ymImagePreview').empty().html(list);

		});

	},

	downloadAttachments: function(uid, file, id) {

		$('div[data-file="' + file + '"]').append('<img src="/assets/images/loading.svg" width="12">');

		$.get('/modules/mailer/core.mailer.php?uid=' + uid + '&file=' + file + '&mid=' + id + '&action=getAttachments', function (data) {

			if (data.length > 0) {

				var exx = data[0].file.split(".");
				var count = exx.length - 1;
				var ext = exx[count];
				var ft = '';

				//console.log(ext);

				if (in_array(ext.toLowerCase(), ['png', 'jpeg', 'jpg', 'gif']))
					ft = 'pic';

				if (file)
					$('div[data-file="' + file + '"]').html('<a href="/content/helpers/get.file.php?file=' + data[0].folder + data[0].file + '" target="blank" title="Просмотр" data-tip="' + ft + '" data-file="' + data[0].folder + data[0].file + '" data-fname="' + data[0].name + '">' + data[0].icon + data[0].name + '</a>&nbsp;<A href="javascript:void(0)" onClick="fileDownload(\'\',\'' + data[0].folder + data[0].file + '\',\'yes\',\'' + data[0].name + '\')"><i class="icon-download blue" title="Скачать"></i></A>[ ' + data[0].size + ' kb ]');

				this.previewImage();

			}
			else {

				Swal.fire("Ошибка", "Не могу загрузить файл. Возможно письмо удалено с сервера!", 'error');
				$('div[data-file="' + file + '"]').find('img').remove();

			}

			//return true;

		}, 'json')
			.done(function () {
			});

	},

	downloadAttachmentsAll: function(uid, mid) {

		var $elm = $('#attachForm[data-id="'+mid+'"]');

		$elm.find('a.dwnld').append('<img src="/assets/images/loading.gif" width="12">');

		var str = $elm.serialize() + '&uid=' + uid + '&mid=' + mid + '&action=getAttachments';
		var files = '';

		$.get('/modules/mailer/core.mailer.php', str, function (data) {

			//console.log(data.length);

			if (data.length > 0) {

				for (var i in data) {

					var exx = data[i].file.split(".");
					var count = exx.length - 1;
					var ext = exx[count];
					var ft = '';

					if (in_array(ext.toLowerCase(), ['png', 'jpeg', 'jpg', 'gif'])) ft = 'pic';

					files = '<div class="smalltxt"><a href="/content/helpers/get.file.php?file=ymail/' + data[i].file + '" target="blank" title="Просмотр" data-tip="' + ft + '" data-file="ymail/' + data[i].file + '" data-fname="' + data[i].name + '">' + data[i].icon + data[i].name + '</a>&nbsp;<A href="javascript:void(0)" onClick="fileDownload(\'\',\'ymail/' + data[i].file + '\',\'yes\',\'' + data[i].name + '\')"><i class="icon-download blue" title="Скачать"></i></A>[ ' + data[i].size + ' kb ]</div>';

					$('#fuploads').find('div[data-file="'+data[i].name+'"]').remove();
					$('#fuploads').append(files);

				}

				//console.log(files);

				if(files !== '') {

					//$('#fuploads').html(files);
					$elm.find('a.dwnld').remove();
					$('#zipFiles').removeClass('hidden');

				}
				else
					Swal.fire("Ошибка", "Не могу загрузить файлы. Возможно письмо удалено с сервера!", 'error');

			}
			else {

				Swal.fire("Ошибка", "Не могу загрузить файлы. Возможно письмо удалено с сервера!", 'error');

				$elm.find('a.dwnld').find('img').remove();

			}

			return true;

		}, 'json')
			.done(function () {

				if(!isCard)
					this.previewImage();
				else
					this.previewImageCard();

			});
	},

	zipAttachmentsAll: function(mid) {

		window.open('/modules/mailer/core.mailer.php?mid=' + mid + '&action=zipAttachments');

	},

	signature: function(){

		doLoad('/modules/mailer/editor.php?action=signature');

	},

	account: function(){

		doLoad('/modules/mailer/editor.php?action=account');

	},

	tpl: function(){

		doLoad('/modules/mailer/editor.php?action=tpl.edit');

	},

	blacklist: function(){

		doLoad('/modules/mailer/editor.php?action=blacklist.view')

	},

	multidel: function() {

		var str = $('#params').serialize();
		var url = '/modules/mailer/core.mailer.php?action=getaction&tip=multidelete&' + str;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.svg"> Выполняю</div>');

		$.post(url, '', function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			//если текущее письмо удалено, то берем первое попавшееся
			if ($('.messagelist[data-id="' + $messageid + '"]').is('div') === false) {

				$messageid = parseInt($('.messagelist:first').data('id'));
				$('#mid').val($messageid);

			}

			$('#contentdiv').find('.mcheck:enabled').prop('checked', false);

			configmpage();

		}, 'json');
	},

	multitrash:function() {

		var str = $('#params').serialize();
		var url = '/modules/mailer/core.mailer.php?action=getaction&tip=multitrash&' + str;

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id=loader><img src="/assets/images/loading.svg"> Выполняю...</div>');

		$.post(url, '', function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data.result);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			if ($('.messagelist[data-id="' + $messageid + '"]').is('div') === false) {

				$messageid = parseInt($('.messagelist:first').data('id'));
				$('#mid').val($messageid);

			}

			$('#contentdiv').find('.mcheck:enabled').prop('checked', false);

			configmpage();

		}, 'json');
	},

	multiread:function() {

		var url = 'modules/mailer/core.mailer.php?action=readall';

		$('#message').empty().fadeTo(1, 1).css('display', 'block').append('<div id="loader"><img src="/assets/images/loading.svg"> Обработка...</div>');

		$.post(url, '', function (data) {

			$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);
			setTimeout(function () {
				$('#message').fadeTo(1000, 0);
			}, 20000);

			$('#contentdiv').find('.mcheck:enabled').prop('checked', false);

			configmpage();

		});
	},

	formatQuoteDialog: function() {
		
		$edialog.find('blockquote').each(function () {

			$(this).addClass('hidden');
			$(this).wrap('<div class="quote"></div>');

		});

		$('.quote').prepend('<div class="quoteTitle">Показать цитату <i class="icon-angle-down"></i></div>');

		$('.quoteTitle').off('click').on('click', function () {
			$(this).parent('.quote').children('blockquote').toggleClass('hidden');
			$(this).find('i').toggleClass('icon-angle-down icon-angle-up');
		});

	},

	formatQuoteCard: function(){

		$('#tabmail').find('blockquote').each(function () {

			$(this).addClass('hidden');
			$(this).wrap('<div class="quote"></div>');

		});

		$('.quote').prepend('<div class="quoteTitle">Показать цитату <i class="icon-angle-down"></i></div>');

		$('.quoteTitle').on('click', function () {

			$(this).siblings('blockquote').toggleClass('hidden');
			$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

		});

	},

};

//Управление черным списком в почтовике
function change_blacklist(action, email, list, id) {

	var url = '/modules/mailer/editor.php?action=blacklist.' + action + '&email=' + email;
	var a = $('.blacklist .boxcount');
	var count = parseInt(a.html());

	$.get(url, function (data) {

		if (!list) {

			//ДОбавление / удаление из ЧС в письме
			if (data === "Сделано") {

				if (action === 'add') {
					$('#ban').html('<A href="javascript:void(0)" onClick="change_blacklist(\'delete\',\'' + email + '\')" class="gray1" title="Убрать из Черного списка"><span class="w40 inline"><i class="icon-block-1 red"><i class="icon-minus red sup fs-07"></i></i></span>&nbsp;Убрать из ЧС</A>');

					a.html(count + 1);

				}

				else if (action === 'delete') {
					$('#ban').html('<A href="javascript:void(0)" onClick="change_blacklist(\'add\',\'' + email + '\')" class="gray1" title="Добавить в Черный список"><span class="w40 inline"><i class="icon-block-1 red"><i class="icon-plus green sup fs-07"></i></i></span>&nbsp;В Черный список</A>')

					a.html(count - 1);

				}

			}

		}
		else {

			$('#emails #email-' + id).remove();

			a.html(count - 1);

		}

		$('#message').fadeTo(1, 1).css('display', 'block').html('Результат: ' + data);

	});

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

/**
 * Работа с дублями
 */
var doubleModule = {

	//просмотр дубля
	view: function (id) {

		doLoad('/content/client.doubles/core.php?action=view&id=' + id);

	},

	//диалог слияния дублей
	merge: function (id) {

		doLoad('/content/client.doubles/core.php?action=merge&id=' + id);

	},

	//диалог игнорирования дублей
	ignore: function (id) {

		doLoad('/content/client.doubles/core.php?action=ignore&id=' + id);

	},

	//запуск поиска дублей
	check: function (id, tip) {

		var clid;
		var pid;
		var $msg = $('#message');

		if (tip === 'client') {
			clid = id;
			pid = 0;
		}
		else {
			pid = id;
			clid = 0;
		}

		function doIT() {

			$msg.fadeTo(1, 1).empty().css('display', 'block').append('<div id="loader"><img src="/assets/images/loader.gif"> Выполняю...</div>');
			$.getJSON('/content/client.doubles/core.php?action=checkDouble.on&clid=' + clid + '&pid=' + pid + '&tip=' + tip, function (data) {

				if (data.type === 'one') {

					if (parseInt(data.id) > 0) {

						$msg.fadeTo(1, 1).css('display', 'block').html('Результат: Обнаружен дубль');

						doubleModule.view(data.id);
						doubleModule.card();

					}
					else {

						$msg.fadeTo(1, 0).empty();
						Swal.fire('Отлично!', 'Дубли не обнаружены', 'success');

					}

				}
				else {

					if (parseInt(data.count) > 0) {

						$msg.fadeTo(1, 0).empty();
						Swal.fire("Результат", "Обнаружено " + data.count + " дублей.\nОбработано: " + data.total + " записей", 'warning');
						DoublesPageRender();

					}
					else {

						$msg.fadeTo(1, 0).empty();
						Swal.fire('Отлично!', 'Дубли не обнаружены', 'success');

					}

				}

				setTimeout(function () {
					$msg.fadeTo(1000, 0);
				}, 20000);

			});

		}

		if (isCard !== true) {

			Swal.fire({
					title: 'Вы уверены?',
					text: "В зависимости от размера базы процесс может занять длительное время!\nТакже возможна большая нагрузка на сервер!",
					type: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Да, выполнить',
					cancelButtonText: 'Отменить',
					confirmButtonClass: 'greenbtn',
					cancelButtonClass: 'redbtn'
				},
				function () {
					doIT()
				}
			).then((result) => {

				if (result.value) {

					doIT()

				}

			});

		}
		else doIT();

	},

	//показ модального окна списка дублей
	modal: function () {

		getSwindow('/content/client.doubles/core.php?modal=true', 'Найденные дубли');

	},

	//загрузка кнопки в карточку
	card: function () {

		var apxDouble = '';

		if (idCard > 0 && $('#data-append').is('div')) {

			$('a[data-id="isdouble"]').remove();

			$.getJSON('/content/client.doubles/core.php?action=isDouble&id=' + idCard + '&tip=' + tipCard, function (data) {

				if (data.sec === 'yes') {

					if (parseInt(data.id) > 0) {

						apxDouble = '<a href="javascript:void(0)" onclick="doubleModule.view(\'' + data.id + '\')" class="red" data-id="isdouble"><i class="icon-info-circled"></i>Найдены дубли</a>';

					}
					else {

						apxDouble = '<a href="javascript:void(0)" onclick="doubleModule.check(\'' + idCard + '\',\'' + tipCard + '\')" class="gray blue" data-id="isdouble"><i class="icon-search"></i>Найти дубли</a>';

					}

					setTimeout(function () {

						$('#data-append').append(apxDouble);

					}, 1000);

				}

			});

		}

	},
	
	// удаление записи
	delete: function(id) {
		
		var url = '/content/client.doubles/core.php?action=delete&id=' + id
		
		$.post(url, '', function (data) {
			
			if( data.error === 0 ) {
				Swal.fire('Отлично!', 'Запись удалена', 'success')
				DoublesPageRender()
			}
			else{
				Swal.fire('Упс..', data.error, 'error')
			}
			
		}, 'json')
		
	},

};

/**
 * Функции работы с Анкетами сделок
 * @type {{fieldClear: $anketa.fieldClear, anketaList: $anketa.anketaList, reload: $anketa.reload, edit: $anketa.edit, print: $anketa.print}}
 */
var $anketa = {

	//очистка поля
	fieldClear: function (id) {

		var url = '/content/deal.anketa/card.php?id=' + id + '&action=anketa.delete';
		var cf = confirm('Вы действительно хотите очистить указанный признак профиля?');
		if (cf) {

			$.post(url, function (data) {

				$anketa.reload(data.ida);
				$('#message').fadeTo(1, 1).css('display', 'block').html(data.text);

				setTimeout(function () {
					$('#message').fadeTo(1000, 0);
				}, 20000);

			}, 'json');

		}

	},

	//список анкет. не закончено
	anketaList: function () {

		var url = '/content/deal.anketa/card.php?action=anketa.list';

		$.getJSON(url, function (data) {


		});

	},

	//перезагрузка анкеты в карточке
	reload: function (id) {

		var did = $('#did').val();

		$('div.anketa-data[data-id="' + id + '"]').load('/content/deal.anketa/card.php?action=anketa.anketa&ida=' + id + '&did=' + did);

	},

	//редактирование анкеты
	edit: function (id, did) {

		if (did === undefined) did = $('#did').val();

		doLoad('/content/deal.anketa/card.php?action=anketa.edit&ida=' + id + '&did=' + did);

	},

	//вывод на печать
	print: function (id, did) {

		window.open('/content/deal.anketa/card.php?action=anketa.print&ida=' + id + '&did=' + did);

	},

	//редактирование анкеты
	view: function (id, did) {

		if (did === undefined) did = $('#did').val();

		doLoad('/content/deal.anketa/list.php?action=view&ida=' + id + '&did=' + did);

	},

	//вывод на печать базовой анкеты для заполнения на бумаге
	baseprint: function (id) {

		window.open('/content/deal.anketa/card.php?action=anketa.baseprint&ida=' + id);

	},

	//показ модального окна списка дублей
	modal: function () {

		getSwindow('/content/deal.anketa/list.php?modal=true', 'Анкеты по сделкам');

	}

};

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

function getColumnEditor(tip){
	
	var hash = window.location.hash.substring(1);

	doLoad('/content/helpers/column.editor.php?action='+tip+'&hash='+hash);

}

/**
 * Функции для работы с карточками
 */
var $cardsf = {

	// вывод списка доступных для создания документов
	docMenu: function(){

		var str;

		if (isCard) {

			var clid = parseInt($('#ctitle #clid').val());
			var pid = parseInt($('#ctitle #pid').val());
			var did = parseInt($('#ctitle #did').val());

			if (clid > 0) str = '&clid=' + clid;
			if (pid > 0) str = '&pid=' + pid;
			if (did > 0) str = '&did=' + did;

			$('div[data-id="doctypes"]').append('<div id="loader"><img src="/assets/images/loading.svg"> Загрузка данных...</div>');

			$.get("/content/helpers/helpers.php?action=getDocTypes", str, function(data){

				var s = '';

				data.forEach(function(item, index, data) {

					if(item['access'] === 1) {

						if (item['isContract'] || item['isDoc']) {

							if (item['add'])
								s += "<div onClick=\"editContract('','contract.add','" + item['type'] + "', '" + item['id'] + "')\" class=\"item ha hand\" title=\"" + item['title'] + "\"><i class=\"icon-plus blue\"></i>&nbsp;" + item['title'] + "</div>";

							else
								s += "<div class=\"item ha hand\" title=\"Уже существует. " + item['title'] + " можно добавить только один\"><i class=\"icon-ok gray\"></i>&nbsp;<span class=\"gray\">" + item['title'] + "</span></div>";

						}
						if (item['isAkt']) {

							if (item['add'])
								s += "<div onClick=\"editAkt('add','', '" + item['id'] + "', '" + item['did'] + "')\" class=\"item ha hand\"><i class=\"icon-plus blue\" title=\"" + item['title'] + "\"></i>&nbsp;" + item['title'] + "</div>";

							else
								s += '<div class=\"item ha hand\" title=\"'+ item['tooltip'] + '. ' + item['title'] + '\"><i class=\"icon-ok gray\"></i>&nbsp;<span class=\"gray\">' + item['title'] + '</span></div>';

						}

					}
					else{

						s += "<div class=\"item ha hand\" title=\"Нет доступа. " + item['title'] + "\"><i class=\"icon-lock red\"></i>&nbsp;<span class=\"red\">" + item['title'] + "</span></div>";

					}

				});

				//console.log(s);

				$('div[data-id="doctypes"]').html(s);

			},"json");

		}

	},

	getDostup: function(){

		var clid = parseInt($('#ctitle #clid').val());
		var did = parseInt($('#ctitle #did').val());

		if(tipCard === 'deal' || tipCard === 'dogovor')
			$('#tabd').load('/content/helpers/deal.helpers.php?did='+did+'&action=dostup');

		if(tipCard === 'client')
			$('#carddostup').load('/content/helpers/client.helpers.php?action=dostup&clid='+clid).append('<img src="/assets/images/loading.svg">');

	},

	getTasks: function(page){

		var $elm = $('#ctitle');

		var clid = $elm.find('#clid').val();
		var did = $elm.find('#did').val();
		var pid = $elm.find('#pid').val();
		var url = '';

		$.Mustache.load('/content/tpl/card.task.mustache');

		$("#tab10").append('<img src="/assets/images/loading.svg">');

		/*if(tipCard === 'deal' || tipCard === 'dogovor')
			url = '/content/desktop/tasklist.php?noclient=yes&did='+did;

		if(tipCard === 'client')
			url = '/content/desktop/tasklist.php?noclient=yes&clid='+clid;

		if(tipCard === 'person')
			url = '/content/desktop/tasklist.php?noclient=yes&pid='+pid;*/

		if(tipCard === 'deal' || tipCard === 'dogovor')
			url = '/content/card/card.task.php?did='+did;

		if(tipCard === 'client')
			url = '/content/card/card.task.php?clid='+clid;

		if(tipCard === 'person')
			url = '/content/card/card.task.php?pid='+pid;

		$.getJSON(url, function (viewData) {

			viewData.language = $language;

			$('#tab10').empty().mustache('taskTpl', viewData).animate({scrollTop: 0}, 200);

		})
			.done(function () {

				var told = localStorage.getItem("told");
				var ttoday = localStorage.getItem("ttoday");
				var tfuture = localStorage.getItem("tfuture");

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


				if (isMobile) {

					$("#tab10").find('table').rtResponsiveTables({id: 'table-todo'});

				}

			});

		this.getHistory();

	},

	getHistory(page) {

		var tip = [];
		var $elm = $('#ctitle');

		if( parseInt(page) === 0 || page === undefined ) {
			page = $('#hpage option:selected').val();
		}

		var clid = $elm.find('#clid').val();
		var did = $elm.find('#did').val();
		var pid = $elm.find('#pid').val();

		$('#tiphistory\\[\\]:checked').each(function () {

			tip.push($(this).val());

		});

		tip = tip.join();

		setCookie('tiphistory', tip, {expires: 0});

		if(tipCard === 'deal' || tipCard === 'dogovor')
			$("#history").load("/content/card/card.history.php?did="+did+"&page=" + page).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'client')
			$("#history").load("/content/card/card.history.php?clid="+clid+"&tt=org&page=" + page).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'person')
			$("#history").load("/content/card/card.history.php?pid="+pid+"&tt=org&page=" + page).append('<img src="/assets/images/loading.svg">');

	},

	getCalls(page) {

		if(!page)
			page = $('#cpage option:selected').val();

		var $elm = $('#ctitle');

		var clid = $elm.find('#clid').val();
		var did = $elm.find('#did').val();
		var pid = $elm.find('#pid').val();

		//console.log('tip =' + tipCard);
		//console.log('clid =' + clid);
		//console.log('did =' + did);
		//console.log('pid =' + pid);

		if(tipCard === 'deal' || tipCard === 'dogovor')
			$("#callhistory").load("/content/card/card.calls.php?did="+did+"&page=" + page).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'client')
			$("#callhistory").load("/content/card/card.calls.php?clid="+clid+"&page=" + page).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'person')
			$("#callhistory").load("/content/card/card.calls.php?pid="+pid+"&page=" + page).append('<img src="/assets/images/loading.svg">');

	},

	getLog: function(page){

		if (!page)
			page = 1;

		var $elm = $('#ctitle');

		var clid = $elm.find('#clid').val();
		var did = $elm.find('#did').val();
		var pid = $elm.find('#pid').val();

		if(tipCard === 'deal' || tipCard === 'dogovor')
			$("#log").load("/content/card/card.history.php?log=yes&did="+did+"&noclient=yes&page=" + page).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'client')
			$("#log").load("/content/card/card.history.php?log=yes&clid="+clid+"&noclient=yes&page=" + page).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'person')
			$("#log").load("/content/card/card.history.php?log=yes&pid="+pid+"&noclient=yes&page=" + page).append('<img src="/assets/images/loading.svg">');

	},

	getFiles: function(){

		var $elm = $('#ctitle');

		var clid = $elm.find('#clid').val();
		var did = $elm.find('#did').val();
		var pid = $elm.find('#pid').val();

		if(tipCard === 'deal' || tipCard === 'dogovor')
			$("#tab6").load("content/card/card.files.php?did="+did).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'client')
			$("#tab6").load("content/card/card.files.php?clid="+clid).append('<img src="/assets/images/loading.svg">');

		if(tipCard === 'person')
			$("#tab6").load("content/card/card.files.php?pid="+pid).append('<img src="/assets/images/loading.svg">');

	},

};