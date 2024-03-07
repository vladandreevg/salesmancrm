/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

/**
 * Преобразует стандартный элемент select в кастомный типа ydropDown
 * с выбором одного варианта
 */

/**
 * @example
 * includeJS("/js/smSelect.js");
 * $('#iduser').smSelect({
		"text": "Сотрудник",
		"width": "300",
		"icon": "icon-user-1"
	});
 */
(function ($) {

	$.fn.smSelect = function (options) {

		const $element = this;

		//сливаем пришедшие параметры с дефолтными
		const opt = jQuery.extend({
			// ширина целевого столбца для фиксации
			// [5,..(+5)..,90,100..(+10)..200,..(+50)..,400]
			// или p97 (w будет добавлено)
			width: '200',
			// максимальная высота списка
			height: '350px',
			// иконка
			icon: 'icon-filter',
			// Название поля
			text: 'Поле',
			class: 'box-shadow',
			// Плавающее
			fly: false,
			id: "blank"
		}, options);

		const selOptTitle = $('option:selected', $element).text();
		const selOptValue = $element.val();
		const ID = $element.attr('id');
		const change = $element.data('change');

		let items = '';
		let $newElement;

		$element.find('option').each(function () {

			let title = $(this).text();
			let value = $(this).val();
			let color = $(this).data('color');
			let style = '';
			let icon  = $(this).data('icon');

			if( color !== undefined ){

				style += 'color:'+color+'; ';

			}

			items += '' +
				'<div class="ydropString yRadio ' + (value === selOptValue ? 'bluebg-sub' : '') + '" style="'+style+'">' +
				'   <label>' +
				'       <input type="radio" name="' + ID + '" id="' + ID + '" data-title="' + title + '" data-icon="'+icon+'" value="' + value + '" class="hidden" ' + (value === selOptValue ? 'checked' : '') + ' data-change="'+ change +'" data-id="' + opt.id + '">' + (icon !== undefined ? '<i class="'+icon+' w40" style="'+style+'"></i>&nbsp;&nbsp;&nbsp;' : '') + '&nbsp;' + title +
				'   </label>' +
				'</div>';

		});

		$newElement = '' +
			'<div class="ydropDown '+ (opt.fly ? 'flyit' : '') +' w' + opt.width + ' '+ opt.class + '" data-id="' + opt.id + '" data-change="'+ change +'" data-selected="'+selOptValue+'">' +
			'   <span title="Сортировать по"><i class="' + opt.icon + ' fs-09"></i></span>' +
			'   <span class="yText Bold fs-09 wp30">' + opt.text + '</span>' +
			'   <span class="ydropText Bold">' + selOptTitle + '</span>' +
			'   <i class="icon-angle-down pull-aright arrow"></i>' +
			'   <div class="yselectBox '+ (opt.fly ? 'fly' : '') +' ' + opt.id + '" data-id="' + opt.id + '" style="max-height: ' + opt.height + ';">' +
			items +
			'    </div>' +
			'</div>';

		$element.after($newElement).remove();

		return true;

	}

}(jQuery));