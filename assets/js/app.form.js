/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*         salesman.pro         */
/*         ver. 2018.6          */
/* ============================ */

/**
 * Проверка дубликатов по прочим полям, кроме номера телефона (сайт, название, email, ...)
 */
$(document).off('keyup', '.validate');
$(document).on('keyup', '.validate', _.debounce(function () {

	var action = $(this).data('action');
	var url = $(this).data('url');

	var awidth;
	var title;
	var atop;
	var aleft;

	//var $espisok = $('#ospisok');

	if ($(this).val().length >= 3) {

		atop = $(this).position().top + 30;
		aleft = $(this).position().left - 5;
		awidth = $(this).width() + 20;

		title = urlEncodeData($(this).val());

		if ( $('#ospisok').is('div') === false) {

			$('#dialog').append('<div id="ospisok"></div>');

			$('#ospisok').css({
				"left": aleft + "px",
				"top": atop + "px",
				"width": awidth + "px",
				"display": "block"
			}).append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных...</div>');

		}

		$.get(url + '?type=json&action=' + action + '&title=' + title, function (data) {

			var string = '';

			for (var i in data) {

				string = string +
					'<div class="row">' +
					'   <div class="column12 grid-8">' +
					'       <div class="ellipsis fs-11">' + data[i].name + '</div>' +
					'       <div class="em fs-09 gray2">' + data[i].tel + (data[i].tel !== '' && data[i].email !== '' ? ', ' : '') + data[i].email + '</div>' +
					'   </div>' +
					'   <div class="column12 grid-4 blue">' + data[i].user + '</div>' +
					'</div>' +
					'<hr>';

			}

			if (data.length === 0) string = '<div class="zbody green pad5">Ура! Дубликатов нет. Можно добавить</div>';


			$('#ospisok').empty().append('<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div><div class="zbody">' + string + '</div>').css('display', 'block');

		}, "json");

		return false;
	}

}, 500));

/**
 * Проверка дубликатов по номеру телефона
 */
$('.phone')
	.off('keyup')
	.on('keyup', _.debounce(function (e) {

		var ww = 0;
		if (formatPhone !== '') ww = 300;

		var action = $(this).data("action");
		var type = $(this).data("type");

		var awidth;
		var atop;
		var aleft;

		atop = $(this).position().top + 30;
		aleft = $(this).position().left - 5;
		awidth = $(this).width() + ww;

		/**
		 * Манипуляции с форматом
		 */
		if (formatPhone !== '') {

			var keycode = e.keyCode;
			var isInsert = false;

			if (isCtrl === true && keycode === 86) isInsert = true;

			if ((keycode >= 48) || (isInsert === true))
				$(this).phoneFormater(formatPhone);

		}

		/**
		 * Вывод информации о дублях
		 */
		if ($(this).val().length >= 3) {

			if ($('#ospisok').is('div') == false) {

				$('#dialog').append('<div id="ospisok"></div>');
				$('#ospisok').css({
					"left": aleft + "px",
					"top": atop + "px",
					"width": awidth + "px",
					"display": "block"
				}).append('<div id="loader"><img src="/assets/images/loading.gif"> Загрузка данных...</div>');

			}

			$.get('content/helpers/' + type + '.php?type=json&action=' + action + '&title=' + $(this).val(), function (data) {

				var string = '';

				for (var i in data) {

					string = string +
						'<div class="row">' +
						'   <div class="column12 grid-8">' +
						'       <div class="ellipsis fs-11">' + data[i].name + '</div>' +
						'       <div class="em fs-09 gray2">' + data[i].tel + (data[i].tel !== '' && data[i].email !== '' ? ', ' : '') + data[i].email + '</div>' +
						'   </div>' +
						'   <div class="column12 grid-4 blue">' + data[i].user + '</div>' +
						'</div>' +
						'<hr>';

				}

				if (data.length === 0) string = '<div class="zbody green pad5">Ура! Дубликатов нет. Можно добавить</div>';

				$('#ospisok').empty().append('<div class="header fs-12"><b>Похожие записи (возможные дубли):</b></div><div class="zbody">' + string + '</div>').css('display', 'block');

			}, "json");

		}

		return false;

	}, 500))
	.on('mouseleave focusout', function () {
		$('#ospisok').remove();
	});

$(document).off('keyup', '.spisoksearch');
$(document).on('keyup', '.spisoksearch', _.debounce(function () {

	var input = $(this).data('input');
	var url   = $(this).data('url');
	var pname = $(this).data('pname');

	var $el = $('#asearch');

	var w = urlEncodeData($el.val());
	var s = $el.val().length;
	var furl = url + '&word=' + w + '&pname=' + pname + '&felement=' + input + '&clid=' + $('#clid').val();

	if (s > 3) {

		$('#orgspisok #pole').empty().append('<div id="loader" class="loader">Загрузка данных...</div>').load(furl);

	}

}, 500));