/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Окно «Инструкция для разработчиков» (plugins/neuroCore/docs.php).
 *
 * Скрипт делает три вещи, без которых длинную справку читать неудобно:
 *
 *  1. копирование примеров кода — код из документации не переписывают руками,
 *     а вставляют в свой плагин;
 *  2. фильтр оглавления — в справочнике больше тридцати разделов, и нужный
 *     быстрее найти по слову, чем прокруткой;
 *  3. подсветка текущего раздела — при прокрутке видно, где находишься.
 *
 * Подсветку синтаксиса делает Prism (prism.min.js + prism.neurocore.js): он
 * запускается на DOMContentLoaded раньше, потому что подключён раньше. Здесь
 * ничего не подсвечивается повторно — только оформление вокруг готовых блоков.
 *
 * Ничего критичного для чтения скрипт не рисует: без него документ остаётся
 * читаемым, а кнопка копирования просто не показывается.
 */

(function () {

	'use strict';

	var doc = document;

	// Признак «скрипт работает»: по нему CSS показывает кнопки копирования.
	// Ставить его сразу, а не в DOMContentLoaded, нужно потому, что разметка
	// документа приходит с сервера уже готовой.
	if (doc.documentElement) {
		doc.documentElement.classList.add('nc-js');
	}

	/**
	 * Копирование текста примера в буфер обмена.
	 *
	 * Два пути: современный clipboard API (работает только в защищённом
	 * контексте, а CRM по http — обычное дело) и старый приём через скрытый
	 * textarea, который работает всегда.
	 */
	function copyText(text) {

		if (navigator.clipboard && window.isSecureContext) {

			return navigator.clipboard.writeText(text);

		}

		return new Promise(function (resolve, reject) {

			var area = doc.createElement('textarea');

			area.value = text;
			area.setAttribute('readonly', 'readonly');
			area.style.position = 'fixed';
			area.style.top = '-1000px';

			doc.body.appendChild(area);
			area.select();

			try {

				var ok = doc.execCommand('copy');

				doc.body.removeChild(area);

				ok ? resolve() : reject(new Error('copy failed'));

			}
			catch (e) {

				doc.body.removeChild(area);
				reject(e);

			}

		});

	}

	/** Кнопки «Копировать» в шапке каждого примера. */
	function initCopy() {

		var blocks = doc.querySelectorAll('.nc-code');

		Array.prototype.forEach.call(blocks, function (block) {

			var button = block.querySelector('[data-copy]');
			var code   = block.querySelector('code');

			if (!button || !code) {
				return;
			}

			var initial = button.textContent;

			button.addEventListener('click', function () {

				copyText(code.textContent).then(function () {

					button.textContent = 'Скопировано';
					button.classList.add('done');

				}).catch(function () {

					button.textContent = 'Не удалось';

				}).then(function () {

					window.setTimeout(function () {

						button.textContent = initial;
						button.classList.remove('done');

					}, 1500);

				});

			});

		});

	}

	/** Фильтр оглавления: прячем пункты, не совпавшие с запросом. */
	function initFilter() {

		var input = doc.querySelector('.nc-doc-filter');
		var nav   = doc.querySelector('.nc-doc-nav');

		if (!input || !nav) {
			return;
		}

		input.addEventListener('input', function () {

			var needle = input.value.toLowerCase().trim();
			var items  = nav.querySelectorAll('li');

			Array.prototype.forEach.call(items, function (item) {

				var hit = needle === '' || item.textContent.toLowerCase().indexOf(needle) !== -1;

				item.style.display = hit ? '' : 'none';

			});

			// подзаголовки прячем вместе с их разделом
			Array.prototype.forEach.call(nav.querySelectorAll('.nc-toc-sub'), function (sub) {

				var visible = sub.querySelectorAll('li:not([style*="display: none"])').length;

				sub.style.display = (needle !== '' && visible === 0) ? 'none' : '';

			});

		});

		// Escape очищает фильтр — привычное поведение строки поиска
		input.addEventListener('keydown', function (event) {

			if (event.key === 'Escape') {

				input.value = '';
				input.dispatchEvent(new Event('input'));

			}

		});

	}

	/**
	 * Подсветка текущего раздела в оглавлении.
	 *
	 * Считается по положению заголовков относительно верха окна: активен
	 * последний заголовок, который уже прошёл верхнюю границу. Такой расчёт не
	 * зависит от высоты разделов и не «залипает» на последнем пункте.
	 */
	function initSpy() {

		var links    = doc.querySelectorAll('.nc-toc a');
		var headings = doc.querySelectorAll('.nc-doc-body h2[id], .nc-doc-body h3[id]');

		if (!links.length || !headings.length) {
			return;
		}

		var map = {};

		Array.prototype.forEach.call(links, function (link) {

			var id = decodeURIComponent((link.getAttribute('href') || '').replace('#', ''));

			if (id !== '') {
				map[id] = link;
			}

		});

		var current = null;

		function refresh() {

			var active = null;

			Array.prototype.forEach.call(headings, function (heading) {

				if (heading.getBoundingClientRect().top <= 90) {
					active = heading.id;
				}

			});

			if (active === current) {
				return;
			}

			if (current && map[current]) {
				map[current].classList.remove('current');
			}

			current = active;

			if (current && map[current]) {
				map[current].classList.add('current');
			}

		}

		window.addEventListener('scroll', refresh, { passive : true });
		window.addEventListener('resize', refresh);

		refresh();

	}

	doc.addEventListener('DOMContentLoaded', function () {

		initCopy();
		initFilter();
		initSpy();

	});

})();
