/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Клиентская логика раздела «Нейро».
 *
 * Зависимости только внешние и проверенные: jQuery и SweetAlert2 (подключаются
 * в index.php). Глобалы ядра (DLoad/DClose/doLoad) не используются — диалог
 * открывается своими средствами, чтобы страница работала самостоятельно.
 */

var Neuro = {

	url    : '',
	token  : '',
	config : {},

	/**
	 * Инициализация
	 */
	init: function (config) {

		this.config = config || {};
		this.url    = this.config.url || 'php/modal.php';
		this.token  = this.config.token || '';

		this.bind();
		this.initDatepickers();

		// единственный запрос при открытии страницы — за списком подключений;
		// форма настроек отдаётся сервером вместе с разметкой
		this.loadProviders();

	},

	/**
	 * Открыть инструкцию для разработчиков в отдельном окне.
	 *
	 * Именно окно, а не вкладка: справку держат открытой рядом с редактором кода и
	 * возвращаются к ней по ходу работы, а вкладку в CRM при этом закрывать нельзя.
	 * Размер задаётся явно — в окне «по умолчанию» оглавление и примеры кода не
	 * помещаются. Имя окна постоянное: повторное нажатие кнопки не открывает
	 * вторую копию справки, а показывает уже открытую.
	 *
	 * window.open вызывается из обработчика клика, а не при загрузке страницы:
	 * иначе браузер счёл бы это всплывающим окном и заблокировал его.
	 */
	openDocs: function () {

		var features = [
			'width=1280',
			'height=900',
			'left=' + Math.max(0, Math.round((window.screen.width - 1280) / 2)),
			'top=' + Math.max(0, Math.round((window.screen.height - 900) / 2)),
			'menubar=no',
			'toolbar=no',
			'location=no',
			'status=no',
			'resizable=yes',
			'scrollbars=yes'
		];

		var win = window.open('docs.php', 'neuroCoreDocs', features.join(','));

		if (win && typeof win.focus === 'function') {
			win.focus();
		}

	},

	/**
	 * Инициализировать datepicker для фильтров дат.
	 */
	initDatepickers: function () {

		var config = {
			dateFormat: 'yy-mm-dd',
			firstDay: 1,
			dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
			monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
			changeMonth: true,
			changeYear: true
		};

		if ($.fn.datepicker) {
			$('#usageFrom, #usageTo').datepicker(config);
		}

	},
	/**
	 * Обработчики интерфейса
	 */
	bind: function () {

		$('.nc-tab').on('click', function () {

			var tab = $(this).data('tab');

			$('.nc-tab').removeClass('current');
			$(this).addClass('current');

			$('.nc-content').removeClass('current');
			$('.nc-content[data-id="' + tab + '"]').addClass('current');

			// список промптов грузим при первом открытии вкладки: при загрузке
			// страницы остаётся один запрос — за подключениями
			if (tab === 'prompts' && !Neuro.promptsLoaded) {
				Neuro.loadPrompts();
			}

			// очередь меняется сама (её разбирает воркер), поэтому обновляем её
			// при каждом открытии вкладки, а не только в первый раз
			if (tab === 'queue') {
				Neuro.loadQueue(Neuro.queueStatus);
			}

			if (tab === 'usage') {
				Neuro.initDatepickers();
				Neuro.loadUsage();
			}

			// журнал пополняется сам (воркер, отказы подключений), поэтому грузим
			// его при каждом открытии вкладки
			if (tab === 'log') {
				Neuro.loadLog();
			}

		});

		$('#dialog_container .close').on('click', function () {
			Neuro.closeDialog();
		});

		$(document).on('keyup', function (e) {
			if (e.keyCode === 27) {
				Neuro.closeDialog();
			}
		});

		// поле имени переменной окружения: в него вставляют значение ключа целиком
		$(document).on('input', '#providerForm input[name="api_key_env"]', function () {
			Neuro.normalizeEnvField($(this));
		});

		// выбор источника ключа: показываем только относящийся к делу блок
		$(document).on('change', '#providerForm input[name="key_source"]', function () {
			Neuro.toggleKeyBlocks();
		});

	},

	/**
	 * Показывает блок, соответствующий выбранному источнику ключа.
	 */
	toggleKeyBlocks: function () {

		var source = $('#providerForm input[name="key_source"]:checked').val() || 'env';

		$('#providerForm [data-keyblock]').each(function () {

			var block = $(this);

			if (block.data('keyblock') === source) {
				block.show();
			}
			else {
				block.hide();
			}

		});

	},

	/**
	 * Приводит поле «Имя переменной окружения» к тому, что там действительно нужно.
	 *
	 * Подпись поля провоцирует вставить значение ключа (и даже строку NAME=значение),
	 * поэтому разбираем такой ввод: в поле остаётся только имя, а значение ключа
	 * никуда не отправляется — показываем готовую строку для сервера.
	 */
	normalizeEnvField: function (field) {

		var value = String(field.val() || '');
		var name  = value;
		var pastedValue = false;

		if (value.indexOf('=') !== -1) {

			var pos    = value.indexOf('=');
			var before = value.slice(0, pos);
			var after  = value.slice(pos + 1).trim();

			// если после «=» пока ничего нет, человек ещё набирает — не мешаем
			if (after === '') {
				return;
			}

			name        = before;
			pastedValue = true;

		}
		else if (/^(sk-|AIza|gsk_|xai-)/i.test(value) || (value.indexOf('_') === -1 && value.length > 20)) {

			// это значение ключа, а не имя: поле не портим, объясняем
			Neuro.renderEnvHint('', true);

			return;

		}

		name = name.trim().toUpperCase().replace(/[^A-Z0-9_]/g, '');

		if (name !== value) {
			field.val(name);
		}

		Neuro.renderEnvHint(name, false, pastedValue);

	},

	/**
	 * Подсказка под полем: что именно добавить на сервере.
	 */
	renderEnvHint: function (name, looksLikeKey, pastedValue) {

		var hint = $('#envHint');

		if (!hint.length) {
			return;
		}

		if (looksLikeKey) {

			hint.html('Похоже, это <b>значение ключа</b>. Здесь нужно только имя переменной, например <b>DEEPSEEK_API_KEY</b>: значение CRM не сохраняет — оно задаётся на сервере.');

			return;

		}

		if (!name) {

			hint.html('Укажите имя переменной — здесь появится готовая строка для сервера.');

			return;

		}

		hint.html(
			'Значение ключа в CRM не передаётся — добавьте его на сервере:<br>'
			+ '<code>SetEnv ' + Neuro.esc(name) + ' ваш-ключ</code> — Apache, mod_php<br>'
			+ '<code>env[' + Neuro.esc(name) + '] = ваш-ключ</code> — nginx + php-fpm'
			+ (pastedValue ? '<br><b class="red">Вставленное значение мы никуда не отправили</b> — подставьте его в строку выше вместо «ваш-ключ».' : '')
		);

	},

	/**
	 * Экранирование значений перед вставкой в HTML
	 */
	esc: function (value) {
		return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
	},

	/**
	 * Запрос к серверу
	 */
	post: function (data, onOk) {

		data = data || {};
		data.token = this.token;

		$.post(this.url, data, function (response) {

			if (response && response.status === 'ok') {

				if (onOk) {
					onOk(response);
				}

			}
			else {

				Neuro.alert('Не получилось', response && response.message ? response.message : 'Неизвестная ошибка');

			}

		}, 'json').fail(function (xhr) {

			Neuro.alert('Не получилось', Neuro.failText(xhr));

		});

	},

	/**
	 * Сообщение пользователю. Текст экранируется, переводы строк сохраняются:
	 * диагностика должна читаться, а не слипаться в одну строку.
	 */
	alert: function (title, text) {

		Swal.fire({
			type             : 'info',
			title            : title,
			html             : '<div class="fs-12">' + Neuro.esc(text).replace(/\n/g, '<br>') + '</div>',
			showConfirmButton: false,
			timer            : 6000
		});

	},

	/**
	 * Разбор неудачного запроса: показываем настоящую причину, а не «сервер не ответил».
	 * Ответ сервера — сообщение из JSON, иначе код и начало тела (там может быть
	 * текст ошибки PHP).
	 */
	failText: function (xhr) {

		if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
			return xhr.responseJSON.message;
		}

		if (!xhr || !xhr.status) {
			return 'Запрос не ушёл: проверьте соединение с сервером';
		}

		var text = 'Ответ сервера: HTTP ' + xhr.status + ' ' + (xhr.statusText || '') + '\n';

		var body = String(xhr.responseText || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();

		if (body) {
			text += body.substring(0, 300);
		}

		return text;

	},

	/**
	 * Диалог.
	 *
	 * В app.css .dialog по умолчанию display:none, и показывает его DLoad() из
	 * /assets/js/app.js ядра, который эта страница не подключает. Поэтому показываем
	 * сам диалог, а не только контейнер: иначе остаётся тёмный оверлей без формы.
	 */
	openDialog: function (html) {

		$('#resultdiv').html(html);
		$('#dialog').show();
		$('#dialog_container').fadeIn(100);

		// показываем блок выбранного источника и подсказку под полем имени переменной
		Neuro.toggleKeyBlocks();

		var field = $('#providerForm input[name="api_key_env"]');

		if (field.length) {
			Neuro.normalizeEnvField(field);
		}

	},

	closeDialog: function () {

		$('#dialog_container').fadeOut(100, function () {

			$('#dialog').hide();
			$('#resultdiv').empty();

		});

	},

	/**
	 * Список подключений
	 */
	loadProviders: function () {

		this.post({action: 'providers.list'}, function (response) {

			var html = '';

			if (!response.list || !response.list.length) {

				html = '<div class="nc-empty">Подключения не настроены. Добавьте первую нейросеть.</div>';

			}
			else {

				$.each(response.list, function (i, item) {

					var keyBadge = item.key_set
						? '<span class="nc-badge nc-badge--on">ключ готов</span>'
						: '<span class="nc-badge nc-badge--warn">' + Neuro.esc(item.key_hint) + '</span>';

					var activeBadge = item.active === 'on'
						? '<span class="nc-badge nc-badge--on">включено</span>'
						: '<span class="nc-badge nc-badge--off">выключено</span>';

					var pdBadge = item.pd_allowed
						? ''
						: ' <span class="nc-badge nc-badge--warn" title="Телефоны, почта, ФИО и реквизиты заменяются плейсхолдерами перед отправкой">данные маскируются</span>';

					var priceText = item.priced ? Neuro.priceLine(item) : '<span class="gray">цены не заданы — в отчёте будут только токены</span>';

					html += ''
						+ '<div class="nc-provider">'
						+ '  <div>'
						+ '    <div class="nc-provider--name">' + Neuro.esc(item.name) + ' ' + activeBadge + ' ' + keyBadge + pdBadge + '</div>'
						+ '    <div class="nc-provider--meta">' + Neuro.esc(item.codeTitle) + ' | ' + Neuro.esc(item.base_url) + ' | модель: ' + Neuro.esc(item.model) + '</div>'
						+ '    <div class="nc-provider--meta">ключ: ' + Neuro.esc(item.sourceTitle) + ' — ' + Neuro.esc(item.key_hint) + '</div>'
						+ '    <div class="nc-provider--meta">расход: ' + priceText + '</div>'
						+ '    <div class="nc-test-result" data-test="' + item.id + '" style="display:none"></div>'
						+ '  </div>'
						+ '  <div class="nc-provider--actions">'
						+ '    <a href="javascript:void(0)" onclick="Neuro.testProvider(' + item.id + ')" class="button greenbtn dotted"><i class="icon-check"></i>Проверить</a>'
						+ '    <a href="javascript:void(0)" onclick="Neuro.editProvider(' + item.id + ')" class="button bluebtn dotted"><i class="icon-pencil"></i>Изменить</a>'
						+ '    <a href="javascript:void(0)" onclick="Neuro.deleteProvider(' + item.id + ')" class="button redbtn dotted"><i class="icon-cancel"></i>Удалить</a>'
						+ '  </div>'
						+ '</div>';

				});

			}

			$('[data-block="providers"]').html(html);

		});

	},

	/**
	 * Форма подключения
	 */
	editProvider: function (id) {

		this.post({action: 'provider.get', id: id || 0}, function (response) {

			Neuro.openDialog(response.html);

		});

	},

	/**
	 * Сохранение подключения
	 */
	saveProvider: function () {

		var data = $('#providerForm').serializeArray();

		var payload = {};

		$.each(data, function (i, field) {
			payload[field.name] = field.value;
		});

		payload.token = this.token;

		$.post(this.url, payload, function (response) {

			if (response && response.status === 'ok') {

				Neuro.closeDialog();
				Neuro.loadProviders();
				Neuro.alert('Готово', response.message);

			}
			else {

				Neuro.alert('Не получилось', response && response.message ? response.message : 'Неизвестная ошибка');

			}

		}, 'json').fail(function (xhr) {

			Neuro.alert('Не получилось', Neuro.failText(xhr));

		});

	},

	/**
	 * Удаление подключения
	 */
	deleteProvider: function (id) {

		Swal.fire({
			title              : 'Удалить подключение?',
			text               : 'Настройки подключения будут удалены',
			showCancelButton   : true,
			confirmButtonText  : 'Да, удалить',
			cancelButtonText   : 'Отмена'
		}).then(function (result) {

			if (result.value) {

				Neuro.post({action: 'provider.delete', id: id}, function (response) {

					Neuro.loadProviders();
					Neuro.alert('Готово', response.message);

				});

			}

		});

	},

	/**
	 * Проверка соединения.
	 *
	 * Результат — и успех, и ошибку — показываем в самой карточке подключения.
	 * Раньше ошибка уходила только в общее сообщение, а блок оставался навсегда
	 * на «Проверяю...», и было непонятно, чем всё закончилось.
	 */
	testProvider: function (id) {

		var block = $('[data-test="' + id + '"]');

		block.css('display', 'block').removeClass('ok err').html('<img src="/assets/images/loading.gif"> Проверяю...');

		$.ajax({
			url      : this.url,
			type     : 'POST',
			data     : {action: 'provider.test', id: id, token: this.token},
			dataType : 'json'
		})
			.done(function (response) {

				// сервер отвечает 200 и для отказа: разбирать нужно поле status
				if (!response || response.status !== 'ok') {

					block.removeClass('ok').addClass('err')
						.html('<b>Не получилось</b><br>' + Neuro.esc(response && response.message ? response.message : 'Неизвестная ошибка'));

					return;

				}

				var text = '<b>' + Neuro.esc(response.message) + '</b>'
					+ '<br>модель: ' + Neuro.esc(response.model)
					+ '<br>задержка: ' + Neuro.esc(response.latency) + ' мс'
					+ '<br>токены: ' + Neuro.esc(response.tokens.in) + ' / ' + Neuro.esc(response.tokens.out);

				if (response.text) {
					text += '<br>ответ: ' + Neuro.esc(response.text);
				}

				block.removeClass('err').addClass('ok').html(text);

			})
			.fail(function (xhr) {

				block.removeClass('ok').addClass('err')
					.html('<b>Не получилось</b><br>' + Neuro.esc(Neuro.failText(xhr)));

			});

	},

	/**
	 * Список промптов
	 */
	loadPrompts: function () {

		this.post({action: 'prompts.list'}, function (response) {

			Neuro.promptsLoaded = true;

			var html = '';

			if (!response.list || !response.list.length) {

				html = '<div class="nc-empty">Промптов пока нет. Добавьте первый — например <b>deal.summary</b> для краткого описания сделки.</div>';

			}
			else {

				$.each(response.list, function (i, item) {

					var badges = (item.active === 'on'
						? '<span class="nc-badge nc-badge--on">включён</span>'
						: '<span class="nc-badge nc-badge--off">выключен</span>')
						+ (item.hasJson ? ' <span class="nc-badge nc-badge--warn">JSON-схема</span>' : '');

					// владелец промпта: плагин или ручное создание в CRM
					badges += item.owner
						? ' <span class="nc-badge nc-badge--off">плагин: ' + Neuro.esc(item.owner) + '</span>'
						: ' <span class="nc-badge nc-badge--off">создан вручную</span>';

					if (item.source === 'admin' && item.owner) {
						badges += ' <span class="nc-badge nc-badge--warn">изменён администратором</span>';
					}

					if (item.differs) {
						badges += ' <span class="nc-badge nc-badge--err">в плагине другой текст</span>';
					}

					var meta = 'код: <b>' + Neuro.esc(item.code) + '</b> | версия ' + Neuro.esc(item.version) + ' | ' + Neuro.esc(item.symbols) + ' символов';

					if (item.model) {
						meta += ' | модель: ' + Neuro.esc(item.model);
					}

					html += ''
						+ '<div class="nc-provider">'
						+ '  <div>'
						+ '    <div class="nc-provider--name">' + Neuro.esc(item.name || item.code) + ' ' + badges + '</div>'
						+ '    <div class="nc-provider--meta">' + meta + '</div>'
						+ '  </div>'
						+ '  <div class="nc-provider--actions">'
						+ '    <a href="javascript:void(0)" onclick="Neuro.editPrompt(' + item.id + ')" class="button greenbtn dotted"><i class="icon-pencil"></i>Изменить</a>'
						+ '    <a href="javascript:void(0)" onclick="Neuro.deletePrompt(' + item.id + ')" class="button redbtn dotted"><i class="icon-cancel"></i>Удалить</a>'
						+ '  </div>'
						+ '</div>';

				});

			}

			$('[data-block="prompts"]').html(html);

		});

	},

	/**
	 * Форма промпта
	 */
	editPrompt: function (id) {

		this.post({action: 'prompt.get', id: id || 0}, function (response) {

			Neuro.openDialog(response.html);

		});

	},

	/**
	 * Сохранение промпта
	 */
	savePrompt: function () {

		var payload = {};

		$.each($('#promptForm').serializeArray(), function (i, field) {
			payload[field.name] = field.value;
		});

		payload.token = this.token;

		$.post(this.url, payload, function (response) {

			if (response && response.status === 'ok') {

				Neuro.closeDialog();
				Neuro.loadPrompts();
				Neuro.alert('Готово', response.message);

			}
			else {

				Neuro.alert('Не получилось', response && response.message ? response.message : 'Неизвестная ошибка');

			}

		}, 'json').fail(function (xhr) {

			Neuro.alert('Не получилось', Neuro.failText(xhr));

		});

	},

	/**
	 * Удаление промпта вместе с историей версий
	 */
	deletePrompt: function (id) {

		Swal.fire({
			title            : 'Удалить промпт?',
			text             : 'Вместе с ним удалится история версий, а фичи, которые его вызывают, перестанут работать',
			showCancelButton : true,
			confirmButtonText: 'Да, удалить',
			cancelButtonText : 'Отмена'
		}).then(function (result) {

			if (result.value) {

				Neuro.post({action: 'prompt.delete', id: id}, function (response) {

					Neuro.loadPrompts();
					Neuro.alert('Готово', response.message);

				});

			}

		});

	},

	/**
	 * Проверка промпта на примере: реальный запрос к модели с заданными переменными.
	 * Используется сохранённая версия промпта.
	 */
	runPrompt: function (id) {

		var form  = $('#promptForm');
		var block = $('#promptRunResult');

		block.css('display', 'block').removeClass('ok err').html('<img src="/assets/images/loading.gif"> Выполняю...');

		$.ajax({
			url      : this.url,
			type     : 'POST',
			dataType : 'json',
			data     : {
				action: 'prompt.run',
				code  : form.find('[name="code"]').val(),
				tags  : form.find('[name="tags"]').val(),
				token : this.token
			}
		})
			.done(function (response) {

				if (!response || response.status !== 'ok') {

					block.removeClass('ok').addClass('err')
						.html('<b>Не получилось</b><br>' + Neuro.esc(response && response.message ? response.message : 'Неизвестная ошибка'));

					return;

				}

				var html = '<b>' + Neuro.esc(response.message) + '</b>';

				if (response.text) {
					html += '<div class="mt5"><b>Ответ модели:</b><br>' + Neuro.esc(response.text).replace(/\n/g, '<br>') + '</div>';
				}

				if (response.json) {
					html += '<div class="mt5"><b>Разобранный JSON:</b><pre class="fs-09">' + Neuro.esc(response.json) + '</pre></div>';
				}

				block.removeClass('err').addClass('ok').html(html);

			})
			.fail(function (xhr) {

				block.removeClass('ok').addClass('err')
					.html('<b>Не получилось</b><br>' + Neuro.esc(Neuro.failText(xhr)));

			});

	},

	/**
	 * Постановка промпта в очередь (то же, что «Выполнить», но без ожидания)
	 */
	queuePrompt: function (id) {

		var form  = $('#promptForm');
		var block = $('#promptRunResult');

		block.css('display', 'block').removeClass('ok err').html('<img src="/assets/images/loading.gif"> Ставлю в очередь...');

		$.ajax({
			url      : this.url,
			type     : 'POST',
			dataType : 'json',
			data     : {
				action: 'prompt.queue',
				code  : form.find('[name="code"]').val(),
				tags  : form.find('[name="tags"]').val(),
				token : this.token
			}
		})
			.done(function (response) {

				if (!response || response.status !== 'ok') {

					block.removeClass('ok').addClass('err')
						.html('<b>Не получилось</b><br>' + Neuro.esc(response && response.message ? response.message : 'Неизвестная ошибка'));

					return;

				}

				block.removeClass('err').addClass('ok').html('<b>' + Neuro.esc(response.message) + '</b>');

			})
			.fail(function (xhr) {

				block.removeClass('ok').addClass('err')
					.html('<b>Не получилось</b><br>' + Neuro.esc(Neuro.failText(xhr)));

			});

	},

	// -----------------------------------------------------------------------
	// Очередь
	// -----------------------------------------------------------------------

	/**
	 * Текущий фильтр по статусу ('' — все)
	 */
	queueStatus: '',

	/**
	 * Загрузка списка заданий
	 */
	loadQueue: function (status) {

		Neuro.queueStatus = status || '';

		$('.nc-chip').removeClass('current');
		$('.nc-chip[data-status="' + Neuro.queueStatus + '"]').addClass('current');

		var block = $('[data-block="queue"]');

		block.html('<div class="nc-empty"><img src="/assets/images/loading.gif"> Загрузка...</div>');

		this.post({action: 'queue.list', status: Neuro.queueStatus}, function (response) {

			block.html(Neuro.renderQueue(response));

		});

	},

	/**
	 * Разметка списка заданий и счётчиков
	 */
	renderQueue: function (response) {

		var stats = response.stats || {};
		var html  = '';

		html += '<div class="nc-queue-stats mb10 fs-09 gray">'
			+ 'В очереди: <b>' + Neuro.esc(stats.new || 0) + '</b> · '
			+ 'в работе: <b>' + Neuro.esc(stats.run || 0) + '</b> · '
			+ 'выполнено: <b>' + Neuro.esc(stats.ok || 0) + '</b> · '
			+ 'с ошибкой: <b>' + Neuro.esc(stats.error || 0) + '</b> · '
			+ 'отменено: <b>' + Neuro.esc(stats.cancel || 0) + '</b>'
			+ '</div>';

		if (!response.list || !response.list.length) {

			return html + '<div class="nc-empty">Заданий нет. Поставьте промпт в очередь кнопкой «В очередь» в карточке промпта.</div>';

		}

		$.each(response.list, function (i, item) {

			var badge = 'nc-badge--off';

			if (item.status === 'ok') {
				badge = 'nc-badge--on';
			}
			else if (item.status === 'error') {
				badge = 'nc-badge--err';
			}
			else if (item.status === 'new' || item.status === 'run') {
				badge = 'nc-badge--warn';
			}

			var metas = [];

			metas.push('задание #' + Neuro.esc(item.id));
			metas.push('создано ' + Neuro.esc(item.datum));
			metas.push('поставил ' + Neuro.esc(item.userName));

			if (item.attempts > 0) {
				metas.push('попыток ' + Neuro.esc(item.attempts) + ' из ' + Neuro.esc(item.maxAttempts));
			}

			if (item.model) {
				metas.push('модель ' + Neuro.esc(item.model));
			}

			if (item.latency) {
				metas.push(Neuro.esc(item.latency) + ' мс');
			}

			if (item.tokensIn || item.tokensOut) {
				metas.push('токены ' + Neuro.esc(item.tokensIn) + ' / ' + Neuro.esc(item.tokensOut));
			}

			var actions = ['<a href="javascript:void(0)" onclick="Neuro.showTask(' + item.id + ')" class="blue">Подробнее</a>'];

			if (item.canRetry) {
				actions.push('<a href="javascript:void(0)" onclick="Neuro.retryTask(' + item.id + ')" class="blue">Повторить</a>');
			}

			if (item.canCancel) {
				actions.push('<a href="javascript:void(0)" onclick="Neuro.cancelTask(' + item.id + ')" class="red">Отменить</a>');
			}

			html += ''
				+ '<div class="nc-provider">'
				+ '  <div>'
				+ '    <div class="nc-provider--name">' + Neuro.esc(item.code) + ' <span class="nc-badge ' + badge + '">' + Neuro.esc(item.statusTitle) + '</span></div>'
				+ '    <div class="nc-provider--meta">' + metas.join(' | ') + '</div>'
				+ (item.error ? '    <div class="nc-provider--meta red">' + Neuro.esc(item.error) + '</div>' : '')
				+ '  </div>'
				+ '  <div class="nc-provider--actions">' + actions.join(' | ') + '</div>'
				+ '</div>';

		});

		return html;

	},

	/**
	 * Карточка задания: ответ, ошибка, переменные
	 */
	showTask: function (id) {

		this.post({action: 'queue.task', id: id}, function (response) {

			Neuro.openDialog(response.html);

		});

	},

	/**
	 * Ручной повтор задания
	 */
	retryTask: function (id) {

		var self = this;

		Swal.fire({
			title             : 'Повторить задание?',
			text              : 'Задание #' + id + ' снова уйдёт в очередь и будет выполнено воркером. Если причина ошибки не устранена, вызов снова будет оплачен.',
			type              : 'question',
			showCancelButton  : true,
			confirmButtonText : 'Да, повторить',
			cancelButtonText  : 'Отмена'
		}).then(function (result) {

			if (!result.value) {
				return;
			}

			self.post({action: 'queue.retry', id: id}, function (response) {

				self.closeDialog();
				self.loadQueue(self.queueStatus);
				self.alert(response.status === 'ok' ? 'Готово' : 'Не получилось', response.message);

			});

		});

	},

	/**
	 * Отмена задания, которое ещё не начало выполняться
	 */
	cancelTask: function (id) {

		var self = this;

		Swal.fire({
			title             : 'Отменить задание?',
			text              : 'Задание #' + id + ' не будет выполнено.',
			type              : 'warning',
			showCancelButton  : true,
			confirmButtonText : 'Да, отменить',
			cancelButtonText  : 'Нет'
		}).then(function (result) {

			if (!result.value) {
				return;
			}

			self.post({action: 'queue.cancel', id: id}, function (response) {

				self.loadQueue(self.queueStatus);
				self.alert(response.status === 'ok' ? 'Готово' : 'Не получилось', response.message);

			});

		});

	},

	// -----------------------------------------------------------------------
	// Расход
	// -----------------------------------------------------------------------

	/**
	 * Деньги: показываем сумму только если цены заданы в подключении.
	 *
	 * Валюта берётся из подключения, где задан прайс. Если в периоде смешались
	 * разные валюты, складывать их нельзя — показываем разбивку (рубли VseGPT и
	 * доллары DeepSeek не дают одного числа).
	 */
	money: function (value, priced, row) {

		row = row || {};

		if (row.mixed && row.currencies) {

			var parts = [];

			$.each(row.currencies, function (currency, sum) {
				parts.push(Neuro.amount(sum, currency));
			});

			return '<b>' + parts.join(' + ') + '</b>';
		}

		if (!priced) {
			return '<span class="gray">нет цен в подключении</span>';
		}

		return '<b>' + Neuro.amount(value, row.currency) + '</b>';

	},

	/**
	 * Сумма с меткой валюты: «12.3400 ₽», а без валюты — просто число
	 */
	amount: function (value, currency) {

		var sum = Number(value || 0).toFixed(4);

		return currency ? sum + ' ' + Neuro.esc(currency) : sum;

	},

	/**
	 * Строка о прайсе подключения для карточки
	 */
	priceLine: function (item) {

		var parts = [];

		if (item.price_in !== null && item.price_in !== undefined && item.price_out !== null && item.price_out !== undefined) {
			parts.push('за 1 млн токенов: ' + Neuro.esc(item.price_in) + ' / ' + Neuro.esc(item.price_out));
		}

		if (item.price_call !== null && item.price_call !== undefined) {
			parts.push('за вызов: ' + Neuro.esc(item.price_call));
		}

		if (item.currency) {
			parts.push(Neuro.esc(item.currency));
		}

		return '<b>' + parts.join(', ') + '</b>';

	},

	/**
	 * Загрузка отчёта по расходу
	 */
	loadUsage: function () {

		var block = $('[data-block="usage"]');

		block.html('<div class="nc-empty"><img src="/assets/images/loading.gif"> Считаю...</div>');

		this.post({
			action: 'usage.report',
			from  : $('#usageFrom').val(),
			to    : $('#usageTo').val()
		}, function (response) {

			block.html(Neuro.renderUsage(response));

		});

	},

	/**
	 * Разметка отчёта
	 */
	renderUsage: function (response) {

		var total = response.total || {};
		var cache = response.cache || {};
		var ttl   = Number(response.cacheTtl || 0);

		var html = '';

		html += '<div class="nc-usage-total mb10">'
			+ 'За период ' + Neuro.esc(response.from) + ' — ' + Neuro.esc(response.to) + ': '
			+ 'вызовов <b>' + Neuro.esc(total.calls || 0) + '</b>, '
			+ 'токены <b>' + Neuro.esc(total.tokens_in || 0) + '</b> / <b>' + Neuro.esc(total.tokens_out || 0) + '</b>, '
			+ 'стоимость ' + Neuro.money(total.cost || 0, total.priced, total)
			+ '</div>';

		if (total.mixed) {

			html += '<div class="fs-09 gray mb10">'
				+ 'В периоде встретились цены в разных валютах, поэтому итог показан отдельно по каждой: '
				+ 'складывать их в одно число нельзя. Валюта задаётся в подключении.'
				+ '</div>';

		}

		if (!total.calls) {

			return html + '<div class="nc-empty">За этот период вызовов не было. Расход появится после первых обращений к модели.</div>';

		}

		html += '<div class="fs-09 gray mb15">'
			+ 'Кэш: <b>' + Neuro.esc(cache.count || 0) + '</b> ответов'
			+ (ttl > 0 ? ' (срок жизни ' + Neuro.esc(Math.round(ttl / 60)) + ' мин)' : ' — выключен')
			+ ' · <a href="javascript:void(0)" onclick="Neuro.clearCache()" class="red">Очистить кэш</a>'
			+ '</div>';

		html += Neuro.usageTable('По типам задач', (response.codes || []).map(function (item) {

			var name = item.code || '(без промпта)';
			var note = '';

			// средние токены на вызов — основа для оценки массовой операции:
			// «будет N вызовов» считается как N × средние × цена подключения.
			// Подсказка идёт отдельным полем: имя строки экранируется целиком, и
			// разметка внутри него показывалась бы текстом
			if (item.average && item.average.calls > 0) {
				note = '<span class="gray fs-09">(в среднем ' + Neuro.esc(item.average.in) + ' / ' + Neuro.esc(item.average.out) + ' токенов на вызов)</span>';
			}

			return { name: name, note: note, row: item.row };

		}));

		html += Neuro.usageTable('По сотрудникам', Object.keys(response.users || {}).map(function (id) {
			return { name: response.users[id].title, row: response.users[id].row };
		}));

		html += Neuro.usageTable('По моделям', (response.models || []).map(function (item) {
			return { name: item.model || '(не указана)', row: item.row };
		}));

		html += Neuro.usageTable('По дням', Object.keys(response.days || {}).map(function (day) {
			return { name: day, row: response.days[day] };
		}));

		return html;

	},

	/**
	 * Таблица одного измерения отчёта
	 */
	usageTable: function (title, rows) {

		if (!rows.length) {
			return '';
		}

		var html = '<div class="Bold mt15 mb5">' + Neuro.esc(title) + '</div>'
			+ '<table class="nc-table"><tr><th>Кто / что</th><th>Вызовов</th><th>Токены (вх/вых)</th><th>Стоимость</th></tr>';

		$.each(rows, function (i, item) {

			html += '<tr>'
				+ '<td>' + Neuro.esc(item.name) + (item.note ? ' ' + item.note : '') + '</td>'
				+ '<td>' + Neuro.esc(item.row.calls || 0) + '</td>'
				+ '<td>' + Neuro.esc(item.row.tokens_in || 0) + ' / ' + Neuro.esc(item.row.tokens_out || 0) + '</td>'
				+ '<td>' + Neuro.money(item.row.cost || 0, item.row.priced, item.row) + '</td>'
				+ '</tr>';

		});

		return html + '</table>';

	},

	// -----------------------------------------------------------------------
	// Журнал
	// -----------------------------------------------------------------------

	/**
	 * Загрузка журнала
	 */
	loadLog: function () {

		var block = $('[data-block="log"]');

		block.html('<div class="nc-empty"><img src="/assets/images/loading.gif"> Читаю журнал...</div>');

		this.post({
			action: 'log.list',
			level : $('#logLevel').val() || '',
			period: $('#logPeriod').val() || 7,
			tid   : $('#logTid').val() || 0
		}, function (response) {

			block.html(Neuro.renderLog(response));

		});

	},

	/**
	 * Разметка журнала
	 */
	renderLog: function (response) {

		var rows  = response.rows || [];
		var stats = response.stats || {};

		var html = '<div class="fs-09 gray mb10">'
			+ 'Записей за период: <b>' + Neuro.esc(response.total || 0) + '</b>'
			+ ' · ошибок: <b>' + Neuro.esc(stats.error || 0) + '</b>'
			+ ' · предупреждений: <b>' + Neuro.esc(stats.warn || 0) + '</b>'
			+ ' · срок хранения: ' + (Number(response.retention || 0) > 0 ? Neuro.esc(response.retention) + ' дн.' : 'бессрочно')
			+ '</div>';

		if (!rows.length) {
			return html + '<div class="nc-empty">За этот период записей нет.</div>';
		}

		html += '<table class="nc-table"><tr><th>Когда</th><th>Уровень</th><th>Задание</th><th>Сообщение</th><th>Подробности</th></tr>';

		$.each(rows, function (i, item) {

			var context = '';

			$.each(item.context || {}, function (key, value) {

				if (value === null || value === undefined || value === '') {
					return;
				}

				context += (context ? ', ' : '') + Neuro.esc(key) + ': ' + Neuro.esc(value);

			});

			html += '<tr>'
				+ '<td class="fs-09">' + Neuro.esc(item.datum) + '</td>'
				+ '<td>' + Neuro.logLevel(item.level, item.levelTitle) + '</td>'
				+ '<td>' + (item.tid > 0 ? '<a href="javascript:void(0)" onclick="Neuro.showTask(' + item.tid + ')" class="blue">#' + Neuro.esc(item.tid) + '</a>' : '<span class="gray">—</span>') + '</td>'
				+ '<td>' + Neuro.esc(item.message) + '</td>'
				+ '<td class="fs-09 gray">' + context + '</td>'
				+ '</tr>';

		});

		return html + '</table>';

	},

	/**
	 * Цветной ярлык уровня
	 */
	logLevel: function (level, title) {

		var cls = level === 'error' ? 'nc-badge--err' : (level === 'warn' ? 'nc-badge--warn' : (level === 'debug' ? 'nc-badge--off' : 'nc-badge--on'));

		return '<span class="nc-badge ' + cls + '">' + Neuro.esc(title || level) + '</span>';

	},

	/**
	 * Уборка выполненных заданий по сроку хранения
	 */
	purgeTasks: function () {

		Swal.fire({
			title             : 'Удалить выполненные задания?',
			text              : 'Удаляются задания, завершённые раньше срока хранения из настроек: вместе с ними уходят переменные с данными CRM и ответ модели. Незавершённые задания не трогаются, учёт расхода не меняется.',
			type              : 'warning',
			showCancelButton  : true,
			confirmButtonText : 'Да, удалить',
			cancelButtonText  : 'Отмена'
		}).then(function (result) {

			if (!result.value) {
				return;
			}

			Neuro.post({action: 'queue.purge'}, function (response) {

				Neuro.loadQueue(Neuro.queueStatus);

				Neuro.alert('Готово', response.message);

			});

		});

	},

	/**
	 * Уборка журнала по сроку хранения
	 */
	purgeLog: function () {

		Swal.fire({
			title             : 'Удалить старые записи журнала?',
			text              : 'Удаляются записи старше срока хранения из настроек. Это не влияет ни на очередь, ни на учёт расхода.',
			type              : 'warning',
			showCancelButton  : true,
			confirmButtonText : 'Да, удалить',
			cancelButtonText  : 'Отмена'
		}).then(function (result) {

			if (!result.value) {
				return;
			}

			Neuro.post({action: 'log.purge'}, function (response) {

				Neuro.loadLog();
				Neuro.alert('Готово', response.message);

			});

		});

	},

	/**
	 * Очистка кэша ответов
	 */
	clearCache: function () {

		var self = this;

		Swal.fire({
			title             : 'Очистить кэш ответов?',
			text              : 'Следующие одинаковые запросы снова пойдут в модель и будут оплачены.',
			type              : 'warning',
			showCancelButton  : true,
			confirmButtonText : 'Да, очистить',
			cancelButtonText  : 'Отмена'
		}).then(function (result) {

			if (!result.value) {
				return;
			}

			self.post({action: 'cache.clear'}, function (response) {

				self.loadUsage();
				self.alert('Готово', response.message);

			});

		});

	},

	/**
	 * Настройки: форма отрендерена сервером, здесь только сохранение
	 */
	saveSettings: function () {

		var data = $('#settingsForm').serializeArray();

		var payload = {};

		$.each(data, function (i, field) {
			payload[field.name] = field.value;
		});

		payload.token = this.token;

		$.post(this.url, payload, function (response) {

			if (response && response.status === 'ok') {
				Neuro.alert('Готово', response.message);
			}
			else {
				Neuro.alert('Не получилось', response && response.message ? response.message : 'Неизвестная ошибка');
			}

		}, 'json').fail(function (xhr) {

			Neuro.alert('Не получилось', Neuro.failText(xhr));

		});

	}

};

$(function () {
	Neuro.init(window.NeuroConfig || {});
});
