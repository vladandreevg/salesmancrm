/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Подписка на события, касающиеся yDropDown
 * Подключается там, где не подключен project.core.new.js
 */

$(document).ready(function () {

	$('.flyit').each(function () {

		$(this).find('.yselectBox').detach().appendTo('#flyitbox');

	});
	$('.ydropString').each(function () {

		var txt = $.trim(striptags($(this).find('label').text()).replace(/<[^p].*?>/g, ''));

		$(this).prop("title", txt);

	});

	$(document).off('click', '.ydropDown');
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

	$(document).off('click', '.ydropString:not(.yRadio)');
	$(document).on('click', '.ydropString:not(.yRadio)', function () {

		var ebox;

		if (!$(this).closest('.yselectBox').hasClass('fly')) {

			ebox = $(this).parents('.ydropDown');
			var chk = $(this).parent('.yselectBox').find('input[type=checkbox]:checked').length;
			var $f = $(this).parents('.ydropDown').find('.ydropCount');
			var a = $f.html();

			$f.html(chk + ' выбрано');

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			ebox = $('.ydropDown[data-id="' + element + '"]');

			var $f2 = ebox.find('.ydropCount');
			var ch2 = $(this).closest('.yselectBox').find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' выбрано');

		}

		//console.log( $(this).find('input[type=checkbox]').prop("checked") );

		if( $(this).find('input[type=checkbox]').prop("checked") )
			$(this).addClass('bluebg-sub');
		else
			$(this).removeClass('bluebg-sub');

		setTimeout(function () {

			$('.yselectBox[data-id="' + element + '"]').show();
			ebox.find('i.icon-angle-down').removeClass('icon-angle-down').addClass('icon-angle-up');
			ebox.find('.action').removeClass('hidden');

		}, 1);

		//return false;

	});

	$(document).off('click', '.ydropString.yRadio');
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

	$(document).off('click', '.ySelectAll');
	$(document).on('click', '.ySelectAll', function () {

		var $elm = $(this).closest('.yselectBox');
		var $box = $(this).closest('.ydropDown');

		if (!$elm.hasClass('fly')) {

			$elm.find('input[type=checkbox]').prop('checked', true);

			var $f = $box.find('.ydropCount');
			var ch = $box.find('input[type=checkbox]:checked').length;

			$f.html(ch + ' выбрано');

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

			$f2.html(ch2 + ' выбрано');

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

	$(document).off('click', '.yunSelect');
	$(document).on('click', '.yunSelect', function () {

		var $elm = $(this).closest('.ydropDown');
		var $box = $(this).closest('.yselectBox');

		if (!$box.hasClass('fly')) {

			var chk = $box.find('input[type=checkbox]:checked').prop('checked', false);
			var $f = $elm.find('.ydropCount');

			$box.find('input[type=checkbox]:checked').prop('checked', false);

			$f.html('0 выбрано');

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

			$f2.html(ch2 + ' выбрано');

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

});

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