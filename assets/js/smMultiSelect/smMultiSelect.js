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
 * с выбором нескольких вариантов
 * Если не подключен скрипт project.core.new.js, то требуется подключить еще
 *  - smMultiSelectEvents.js (события)
 *  - smMultiSelect.css (доп.стили)
 */

/**
 * @example
 * includeJS("/js/smMultiSelect.js");
 * $('#userlist').smMultiSelect({
		"text": "Сотрудники",
		"tooltip": "Сотрудники",
		"width": "300",
		"icon": "icon-user-1"
	});
 */
(function ($) {

	$.fn.smMultiSelect = function (options) {

		const $element = this;

		//сливаем пришедшие параметры с дефолтными
		const opt = jQuery.extend({
			// ширина целевого столбца для фиксации
			// [5,..(+5)..,90,100..(+10)..200,..(+50)..,400]
			width: '200',
			// максимальная высота списка
			height: '350px',
			// иконка
			icon: 'icon-filter',
			// Название поля
			text: 'Поле',
			tooltip: 'Ответственный',
			// сделать блок плавающим, если он находится в пределах блока с overflow
			isFly: false,
			// имеет дополнительное действие
			Action: '',
			//дополнительный css-стиль
			class: '',
			//добавить drop-shadow
			shadow: true
		}, options);

		const ID = $element.attr('id');

		let items = '';
		let $newElement;
		let count = 0;
		let selOptValue = [];

		$element.find('option').each(function () {

			let title = $(this).text();
			let value = $(this).val();

			if(value !== '')
				items += '' +
					'<div class="ydropString ellipsis ' + ($(this).attr("selected") ? 'bluebg-sub' : '') + '">' +
					'   <label>' +
					'       <input type="checkbox" name="' + ID + '[]" id="' + ID + '[]" data-title="' + title + '" value="' + value + '" class="" ' + ($(this).attr("selected") ? 'checked' : '') + '>&nbsp;' + title +
					'   </label>' +
					'</div>';

			if($(this).attr("selected")) {

				selOptValue.push(value);
				count++;

			}

		});

		//console.log(selOptValue);

		count = selOptValue.length;

		$newElement = '' +
			'<div class="ydropDown '+ (opt.isFly ? 'flyit' : '') +' w' + opt.width + (opt.shadow ? ' box-shadow ' : '') + opt.class + '" data-id="' + ID + '" '+ (opt.Action !== '' ? 'data-func="'+ opt.Action +'"' : '') +'>' +
			'   <span title="'+ opt.tooltip +'" class="fs-09"><i class="' + opt.icon + '"></i>'+ opt.text +'</span>' +
			'   <span class="ydropCount">'+ count +' выбрано</span><i class="icon-angle-down pull-aright"></i>' +
			(opt.Action !== '' ? '   <div class="yDoit action button hidden">Применить</div>' : '') +
			'   <div class="yselectBox '+ (opt.isFly ? 'fly' : '') +'" style="max-height: ' + opt.height + ';" data-id="' + ID + '">' +
			'   <div class="right-text">' +
			'       <div class="ySelectAll w0 inline" title="Выделить всё"><i class="icon-plus-circled"></i>Всё</div>' +
			'       <div class="yunSelect w0 inline" title="Снять выделение"><i class="icon-minus-circled"></i>Ничего</div>' +
			'   </div>' +
				items +
			'    </div>' +
			'</div>';

		//console.log($newElement);

		$element.after($newElement).remove();

		if(opt.isFly)
			$('.yselectBox[data-id="' + ID + '"]').detach().appendTo('#flyitbox');

		return true;

	}

}(jQuery));