/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */
/**
 * Регулирует ширину контрольного столбца за счет столбца-донора
 */
(function ($) {

	$.fn.smTableColumnFixer = function (options) {

		const $element = this;

		//сливаем пришедшие параметры с дефолтными
		const opt = jQuery.extend({
			// целевой столбец, ширину которого фиксируем
			goal: '#title',
			// первоочередной донор, которого будем скрывать
			donor: '#content',
			// массив остальных доноров, которыми можем пожертвовать (передавать идентификаторы)
			donors: [],
			// ширина целевого столбца для фиксации
			width: 200,
			container: '#contentdiv',
			//игнорировать НЕ доноров
			ignore: false
		}, options);

		//находим суммы ширин всех столбцов
		function totalWidth(){

			let totalWidth = 0;

			//находим суммы ширин всех столбцов
			$element.find('th').each(function(){

				if( !$(this).hasClass('hidden') )
					totalWidth += $(this).outerWidth();

			});

			return totalWidth;

		}

		//ширина целевой колонки
		const goalColumn = $element.find('th' + opt.goal);
		const goalIndex = goalColumn.prevAll().length;

		//ширина колонки-донора ( основного )
		const donorColumn = $element.find('th' + opt.donor);

		//ширина контрольных столбцов
		let goalWidth = parseInt( goalColumn.width() + 0 );

		//ширина контейнера
		const $containerWidth = $(opt.container).width();

		//ширина таблицы
		let $totalWidth = totalWidth();

		//массив доноров ( для удобства )
		let ar = opt.donors;

		//добавляем главного донора к остальным
		ar[ar.length] = opt.donor;

		//убираем аттрибут ширины у целевой колонки
		goalColumn.removeAttr('width');

		//возвращаем видимость всем колонкам
		$element.find('th').each(function() {

			let id = $(this).attr('id');

			let column = $element.find('th#' + id);

			//индекс столбца-донора
			let cindex = column.prevAll().length;

			if(id !== undefined && column.hasClass('hidden')) {

				//скрываем столбец заголовка по индексу столбца
				$element.find('th:eq(' + cindex + ')').removeClass('hidden');

				//скрываем ячейки в теле по индексу столбца
				$element.find('tr').each(function () {

					$(this).find('td:eq(' + cindex + ')').removeClass('hidden');

				});

			}

		});

		$totalWidth = totalWidth();

		// Устанавливаем ширину целевого столбца в заданное значение
		// если она меньше
		if(goalWidth < opt.width)
			goalColumn.attr('width', opt.width + 'px');

		//обрабатываем все колонки, не входящие в массив доноров - доп.поля
		if(!opt.ignore) {

			$($element.find('th').get().reverse()).each(function () {

				let id = $(this).attr('id');

				//выбираем только доп.поля
				if (id !== undefined && ('string' + id).indexOf("input") > 0) {

					$totalWidth = totalWidth();

					let column = $element.find('th#' + id);

					//индекс столбца-донора
					let cindex = column.prevAll().length;

					if ($totalWidth > $containerWidth) {

						//удаляем атрибут ширины
						column.addClass('hidden');

						//скрываем столбец заголовка по индексу столбца
						$element.find('th:eq(' + cindex + ')').addClass('hidden');

						//скрываем ячейки в теле по индексу столбца
						$element.find('tr').each(function () {

							$(this).find('td:eq(' + cindex + ')').addClass('hidden');

						});

					}

				}

			});

		}

		//сначала скрываем главного донора, если он есть
		if ($totalWidth > $containerWidth && $(opt.donor).is('th')) {

			//индекс столбца-донора
			const index = donorColumn.prevAll().length;

			//скрываем столбец заголовка по индексу столбца
			$element.find('th:eq(' + index + ')').addClass('hidden');

			//скрываем ячейки в теле по индексу столбца
			$element.find('tr').each(function () {

				$(this).find('td:eq(' + index + ')').addClass('hidden');

			});

		}

		// если указаны другие доноры, то
		// постепенно уменьшаем их ширину
		opt.donors.forEach(function (item) {

			$totalWidth = totalWidth();

			let column = $element.find('th' + item);
			let colWidth = column.width();

			if ($totalWidth > $containerWidth && colWidth > 80)
				column.attr('width', '80px');

		});

		// если указаны другие доноры, то
		// скрываем их постепенно, если уменьшение ширины не помогло
		opt.donors.forEach(function (item) {

			//текущая ширина всей таблицы
			let $ctotalWidth = totalWidth();

			//если ширина больше, чем контейнер, то скрываем поле
			if ($ctotalWidth > $containerWidth) {

				//текущая колонка-донор
				let column = $element.find('th' + item);

				//индекс столбца-донора
				let cindex = column.prevAll().length;

				//удаляем атрибут ширины
				column.addClass('hidden');

				//скрываем столбец заголовка по индексу столбца
				$element.find('th:eq(' + cindex + ')').addClass('hidden');

				//скрываем ячейки в теле по индексу столбца
				$element.find('tr').each(function () {

					$(this).find('td:eq(' + cindex + ')').addClass('hidden');

				});

			}

		});

		return this;

	}

}(jQuery));