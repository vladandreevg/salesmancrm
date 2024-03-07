/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */
/**
 * Преобразует столбцы таблицы в строки с переносом заголовков в строки
 */
(function ($) {

	$.fn.rtResponsiveTables = function (options) {

		let $thead = this.find('thead');
		let $tfoot = this.find('tfoot');
		let $element = this;
		//let $names = [];
		let $id = (typeof options === 'undefined') ? Math.floor(Math.random() * (999999 - 111111 + 1)) + 111111 : options.id;

		$element.attr('data-id', $id).addClass('mobile-table');

		$thead.addClass('hidden');
		$tfoot.addClass('hidden');

		let code = '';

		//массив имен колонок
		$thead.find('th').each(function(index, element){

			//$names.push( $(this).html() );
			let text = $(this).text().trim();

			if( text !== '' ) code += 'table[data-id="'+$id+'"].mobile-table td:nth-of-type(' + (index + 1) + '):not(empty):before { content: "' + text + '"; }';

		});

		let style = '<style type="text/css">' + code + '</style>';

		$(style).appendTo('head');


		return this;

	}

}(jQuery));