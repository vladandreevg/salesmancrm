<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2026 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*         ver. 2026.x          */
/* ============================ */

/**
 * Каталог колонок раздела "Напоминания. Все" (url = todo) и работа с настройками колонок.
 *
 * Архитектура повторяет модуль Прайс (modules/price/list.price.php):
 * каждая колонка описана одним элементом массива - класс ширины, содержимое <TH> и
 * рендер <TD> находятся рядом. Список, диалог настройки и экспорт используют ОДИН каталог.
 *
 * Настройки пользователя: cash/todos_columns_{iduser}.txt в формате
 *   { "<ключ колонки>": { "width": <int|"">, "on": "yes"|"" }, ... }
 * Порядок ключей в файле = порядок колонок в таблице.
 *
 * @package Todo
 */

if (!function_exists('todoH')) {

	/**
	 * Экранирование значения для вывода в HTML
	 */
	function todoH($string): string {

		return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');

	}

}

if (!function_exists('todoPlain')) {

	/**
	 * Текстовое представление значения (для атрибута title и для экспорта)
	 */
	function todoPlain($value): string {

		$value = strip_tags((string)$value);
		$value = str_replace(["\\r\\n", "\\n", "\\r"], " ", $value);

		// \xA0 - неразрывный пробел, приводим к обычному
		$value = str_replace("\xC2\xA0", " ", $value);

		return trim(preg_replace('/\s+/u', ' ', $value));

	}

}

if (!function_exists('todoColumnsFile')) {

	/**
	 * Файл настроек колонок пользователя
	 */
	function todoColumnsFile(int $iduser): string {

		$rootpath = $GLOBALS['rootpath'] ?? dirname(__DIR__, 2);

		return $rootpath.'/cash/todos_columns_'.$iduser.'.txt';

	}

}

if (!function_exists('todoClientFields')) {

	/**
	 * Пользовательские доп.поля карточки клиента (колонки таблицы clientcat)
	 *
	 * @return array список [fld_name, fld_title, fld_temp]
	 */
	function todoClientFields(): array {

		static $fields = NULL;

		if ($fields !== NULL) {
			return $fields;
		}

		$db       = $GLOBALS['db'];
		$sqlname  = $GLOBALS['sqlname'];
		$identity = $GLOBALS['identity'];

		$fields = [];

		// доп.поля клиента создаются в Панели управления как input{N} (см. content/admin/fields.php:233)
		$res = $db -> getAll("SELECT fld_name, fld_title, fld_temp FROM {$sqlname}field 
			WHERE fld_tip = 'client' and fld_on = 'yes' and fld_name LIKE '%input%' 
			and (fld_sub IS NULL OR fld_sub NOT IN ('partner','contractor','concurent','hidden'))
			and (fld_temp IS NULL OR fld_temp != 'hidden') 
			and identity = '$identity' ORDER BY fld_order, fld_title");

		foreach ($res as $data) {

			$fields[] = [
				"fld_name"  => $data['fld_name'],
				"fld_title" => ($data['fld_title'] != '') ? $data['fld_title'] : $data['fld_name'],
				"fld_temp"  => (string)$data['fld_temp'],
			];

		}

		return $fields;

	}

}

if (!function_exists('todoClientColumnSources')) {

	/**
	 * Соответствие "ключ колонки клиента" -> "колонки таблицы clientcat, которые нужно выбрать"
	 *
	 * @return array
	 */
	function todoClientColumnSources(): array {

		$map = [
			"client_phone"     => ["phone", "fax"],
			"client_mail"      => ["mail_url"],
			"client_site"      => ["site_url"],
			"client_address"   => ["address"],
			"client_category"  => ["idcategory"],
			"client_territory" => ["territory"],
			"client_path"      => ["clientpath"],
			"client_relation"  => ["tip_cmr"],
			"client_user"      => ["iduser"],
			"client_des"       => ["des"],
			"client_created"   => ["date_create"],
			"client_pid"       => ["pid"],
		];

		foreach (todoClientFields() as $field) {

			$map['client_'.$field['fld_name']] = [$field['fld_name']];

		}

		return $map;

	}

}

if (!function_exists('todoColumnClass')) {

	/**
	 * CSS-класс колонки: ширина из настроек (или из каталога) + признак перетаскивания
	 *
	 * @param array      $def   описание колонки из каталога
	 * @param int|string $width ширина из настроек пользователя
	 */
	function todoColumnClass(array $def, $width = NULL): string {

		$w = ((int)$width > 0) ? (int)$width : (int)$def['width'];

		return (($w > 0) ? 'w'.toWidth($w).' ' : '').'text-left drag--accept';

	}

}

if (!function_exists('todoColumnDefs')) {

	/**
	 * Каталог колонок раздела
	 *
	 * @param array $ctx контекст вывода: ord (направление сортировки), person (имена контактов)
	 *
	 * @return array
	 */
	function todoColumnDefs(array $ctx = []): array {

		$lang = $GLOBALS['lang'] ?? [];

		$t = static function (string $key, string $default) use ($lang): string {

			return ($lang['all'][$key] ?? '') != '' ? $lang['all'][$key] : $default;

		};

		// ---------- стандартные колонки списка напоминаний ----------

		$defs = [];

		// Время/Дата (единственная колонка с сортировкой)
		$icn = '';
		if (array_key_exists('ord', $ctx)) {
			$icn = ($ctx['ord'] == 'desc') ? '<i class="icon-down-open-big"></i>' : '<i class="icon-up-open-big"></i>';
		}

		$defs['datetime'] = [
			"title"  => $t('Time', 'Время'),
			"width"  => 120,
			"on"     => true,
			"sort"   => true,
			"th"     => '<div class="ellipsis hand" id="x-datetime" onclick="changesort()" title="Изменить порядок вывода">'.$icn.' <b>'.$t('Time', 'Время').'</b></div>',
			"td"     => static function (array $item) {

				$date = todoH($item['date']);
				$time = todoH($item['time']);

				if ($item['day']) {
					$body = '<div class="fs-11 blue Bold"><i class="icon-flag green" title="Весь день"></i> '.$date.'</div>';
				}
				elseif ($item['do']) {
					$body = '<div class="fs-11 blue Bold">'.($item['status'] ? '<i class="icon-cancel-circled red"></i>' : '<i class="icon-ok green"></i>').' '.$time.'</div>'
						.'<div class="fs-07 gray2 ml20 pl10">'.$date.'</div>';
				}
				else {
					$body = '<div class="fs-11 blue Bold"><i class="icon-clock"></i>'.$time.'</div>'
						.'<div class="fs-07 gray2 ml20">'.$date.'</div>';
				}

				$hist = '';
				if ($item['histdate'] != '') {
					//get_sdate() возвращает разметку (<b>H:i</b> d.m) - выводим как HTML, как и раньше в шаблоне
					$hist = '<div class="mt10 fs-10 gray2" title="Выполнено. '.todoH($item['statusTooltip']).'"><i class="icon-clock"></i> '.$item['histdate'].'</div>';
				}

				return '<TD data-id="datetime">
						<div class="mt5">'.$body.'</div>
						'.$hist.'
					</TD>';

			},
			"export" => static function (array $item) {
				return (string)$item['datetime'];
			},
		];

		// Тема (по умолчанию - как в текущем виде списка: тема + агенда + результат + исполнитель/автор)
		$defs['title'] = [
			"title"  => $t('Theme', 'Тема'),
			"width"  => '',
			"on"     => true,
			"th"     => '<b>'.$t('Theme', 'Тема').'</b>',
			"td"     => static function (array $item) {

				$user = '';
				if ((string)$item['user'] != '') {
					$user = '<span class="mt10 viewdiv1 fs-09 em gray2 pr20">Исполнитель: '.todoH($item['user']).'</span>';
				}

				$autor = '';
				if ((string)$item['autor'] != '') {
					$autor = '<span class="ellipsis1 gray2 mt5 em fs-09">Автор: '.todoH($item['autor']).'</span>';
				}

				$agenda = ((string)$item['agenda'] != '') ? '<div class="mt10 infodiv graybg-lite fs-09 gray2 scroll noscroll height--50">'.$item['agenda'].'</div>' : '';
				$rezult = ((string)$item['rezult'] != '') ? '<div class="mt10 infodiv graybg-lite fs-09 gray2 scroll noscroll height--100">'.$item['rezult'].'</div>' : '';
				$users  = ((string)$item['users'] != '') ? '<div class="mt10 viewdiv1 fs-09 gray2">'.$item['users'].'</div>' : '';

				$lock = $item['readonly'] ? '<i class="icon-lock red" title="Только чтение"></i>' : '';

				return '<TD data-id="title">
						<div title="'.todoH($item['tip']).'" class="hidden visible-iphone1 fs-09 em gray2">'.todoH($item['tip']).'</div>
						<div class="ellipsis11 fs-11 mt5">
							<div class="w20 inline hidden-iphone">'.$item['priority'].'</div>
							<span class="hidden">'.$item['icon'].'</span>'.$item['iconuser'].$lock.'
							<a href="javascript:void(0)" onClick="viewTask(\''.(int)$item['tid'].'\');" title="'.todoH($item['title']).'" class="Bold">'.todoH($item['title']).'</a>
						</div>
						'.$agenda.'
						'.$rezult.'
						'.$users.'
						<div class="mt5 mb5">'.$user.$autor.'</div>
					</TD>';

			},
			"export" => static function (array $item) {
				return (string)$item['title'];
			},
		];

		// Тип
		$defs['tip'] = [
			"title"  => $t('Type', 'Тип'),
			"width"  => 160,
			"on"     => true,
			"th"     => '<b>'.$t('Type', 'Тип').'</b>',
			"td"     => static function (array $item) {

				return '<TD data-id="tip"><span title="'.todoH($item['tip']).'" class="ellipsis" style="color:'.todoColor($item['color']).'">'.$item['icon'].' '.todoH($item['tip']).'</span></TD>';

			},
			"export" => static function (array $item) {
				return (string)$item['tip'];
			},
		];

		// Клиент / Контакт / Сделка (по умолчанию - как в текущем виде списка)
		$defs['client'] = [
			"title"  => $t('Client', 'Клиент'),
			"width"  => 180,
			"on"     => true,
			"th"     => '<b>'.$t('Client', 'Клиент').'</b>',
			"td"     => static function (array $item) {

				$client = '';
				if ((int)$item['clid'] > 0 && (string)$item['client'] != '') {
					$client = '<A href="javascript:void(0)" onclick="openClient(\''.(int)$item['clid'].'\')" title="Открыть в новом окне"><i class="icon-commerical-building blue"></i>'.todoH($item['client']).'</A>';
				}

				$person = '';
				if ((int)$item['pid'] > 0 && (string)$item['person'] != '') {
					$person = '<A href="javascript:void(0)" onclick="openPerson(\''.(int)$item['pid'].'\')" title="Открыть в новом окне"><i class="icon-user-1 blue"></i>'.todoH($item['person']).'</A>';
				}

				$deal = '';
				if ((int)$item['did'] > 0 && (string)$item['deal'] != '') {
					$deal = '<br><div class="ellipsis mt5"><A href="javascript:void(0)" onclick="openDogovor(\''.(int)$item['did'].'\')" title="Открыть в новом окне"><i class="icon-briefcase red"></i>'.todoH($item['deal']).'</A></div>';
				}

				return '<TD data-id="client"><div class="ellipsis">'.$client.$person.'</div>'.$deal.'</TD>';

			},
			"export" => static function (array $item) {

				$value = ((string)$item['client'] != '') ? $item['client'] : $item['person'];

				return (string)$value;

			},
		];

		// Отдельными колонками не выводятся (дублируют содержимое составных колонок):
		//   Приоритет, Весь день, Агенда, Результат, Выполнено, Исполнитель, Автор, Контакт, Сделка
		// - приоритет/срочность, агенда, результат, исполнитель и автор показываются в колонке "Тема";
		// - флаг "Весь день" и время выполнения - в колонке "Время";
		// - клиент/контакт и сделка - в колонке "Клиент".

		// ---------- колонки из карточки клиента ----------

		foreach (todoClientColumns() as $key => $column) {

			$defs[$key] = [
				"title"  => $column['title'],
				"width"  => $column['width'],
				"on"     => false,
				"th"     => '<div class="ellipsis" title="'.todoH($column['title']).'">'.todoH($column['title']).'</div>',
				"td"     => static function (array $item) use ($key) {

					$html = $item['clientData']['html'][$key] ?? '';
					$text = $item['clientData']['text'][$key] ?? '';

					if ($html === '' && $text === '') {
						return '<TD data-id="'.$key.'"></TD>';
					}

					return '<TD data-id="'.$key.'"><div class="ellipsis" title="'.todoH($text).'">'.$html.'</div></TD>';

				},
				"export" => static function (array $item) use ($key) {
					return (string)($item['clientData']['text'][$key] ?? '');
				},
			];

		}

		return $defs;

	}

}

if (!function_exists('todoClientColumns')) {

	/**
	 * Описание колонок из карточки клиента (стандартные поля + доп.поля)
	 *
	 * @return array [ключ колонки => [title, width]]
	 */
	function todoClientColumns(): array {

		$columns = [
			"client_phone"     => ["title" => 'Телефон', "width" => 130],
			"client_mail"      => ["title" => 'Почта', "width" => 130],
			"client_site"      => ["title" => 'Сайт', "width" => 120],
			"client_address"   => ["title" => 'Адрес', "width" => 150],
			"client_category"  => ["title" => 'Отрасль', "width" => 120],
			"client_territory" => ["title" => 'Территория', "width" => 120],
			"client_path"      => ["title" => 'Источник клиента', "width" => 120],
			"client_relation"  => ["title" => 'Тип отношений', "width" => 100],
			"client_user"      => ["title" => 'Ответственный', "width" => 120],
			"client_des"       => ["title" => 'Описание клиента', "width" => 200],
			"client_created"   => ["title" => 'Дата создания', "width" => 90],
			"client_pid"       => ["title" => 'Осн. контакт', "width" => 150],
		];

		foreach (todoClientFields() as $field) {

			$columns['client_'.$field['fld_name']] = [
				"title" => $field['fld_title'],
				"width" => 100,
			];

		}

		return $columns;

	}

}

if (!function_exists('todoColor')) {

	/**
	 * Безопасное значение для style="color:..."
	 */
	function todoColor($color): string {

		$color = preg_replace('/[^a-zA-Z0-9#(),.% \-]/', '', (string)$color);

		return ($color != '') ? $color : 'transparent';

	}

}

/* ============================ */
/*   Работа с настройками       */
/* ============================ */

if (!function_exists('todoColumnsNormalize')) {

	/**
	 * Приведение настроек к виду каталога:
	 *  - неизвестные ключи отбрасываются
	 *  - отсутствующие ключи каталога добавляются в конец со значениями по умолчанию
	 *  - сохраняется порядок колонок из файла настроек
	 *
	 * @param array $fc   настройки из файла (может быть пустым или повреждённым)
	 * @param array $defs каталог колонок
	 */
	function todoColumnsNormalize($fc, array $defs): array {

		$result = [];

		foreach ((array)$fc as $key => $value) {

			if (!isset($defs[$key])) {
				continue;
			}

			$width = (isset($value['width']) && (int)$value['width'] > 0) ? (int)$value['width'] : $defs[$key]['width'];

			$result[$key] = [
				"width" => $width,
				"on"    => ((string)($value['on'] ?? '') === 'yes') ? 'yes' : '',
			];

		}

		foreach ($defs as $key => $def) {

			if (!isset($result[$key])) {

				$result[$key] = [
					"width" => $def['width'],
					"on"    => !empty($def['on']) ? 'yes' : '',
				];

			}

		}

		return $result;

	}

}

if (!function_exists('todoColumnsMergeOrder')) {

	/**
	 * Слияние порядка колонок, пришедшего от dragtable (x_<ключ> => индекс) с текущими настройками.
	 *
	 * ВАЖНО: колонки, отсутствующие в запросе (отключённые, либо без id у <TH>), НЕ теряются,
	 * а добавляются в конец с сохранением относительного порядка.
	 *
	 * @param array $order параметры запроса в порядке следования колонок
	 * @param array $fc    текущие настройки (нормализованные)
	 */
	function todoColumnsMergeOrder(array $order, array $fc): array {

		$new = [];

		foreach (array_keys($order) as $param) {

			$key = preg_replace('/^x_/', '', (string)$param);

			if (isset($fc[$key])) {

				$new[$key] = $fc[$key];
				unset($fc[$key]);

			}

		}

		foreach ($fc as $key => $value) {

			$new[$key] = $value;

		}

		return $new;

	}

}

if (!function_exists('todoColumnsFromRequest')) {

	/**
	 * Формирование настроек из данных формы диалога (порядок строк = порядок колонок)
	 *
	 * @param array $request массив с ключами name, width, on
	 * @param array $defs    каталог колонок (белый список)
	 */
	function todoColumnsFromRequest(array $request, array $defs): array {

		$name  = (array)($request['name'] ?? []);
		$width = (array)($request['width'] ?? []);
		$on    = (array)($request['on'] ?? []);

		$result = [];

		foreach (array_keys($name) as $key) {

			if (!isset($defs[$key])) {
				continue;
			}

			$w = (int)($width[$key] ?? 0);

			$result[$key] = [
				"width" => ($w > 0) ? $w : $defs[$key]['width'],
				"on"    => ((string)($on[$key] ?? '') === 'yes') ? 'yes' : '',
			];

		}

		return $result;

	}

}

if (!function_exists('todoColumnsVisible')) {

	/**
	 * Список ключей включённых колонок в заданном порядке
	 */
	function todoColumnsVisible(array $fc, array $defs): array {

		$visible = [];

		foreach ($fc as $key => $value) {

			if (isset($defs[$key]) && $value['on'] === 'yes') {
				$visible[] = $key;
			}

		}

		return $visible;

	}

}

if (!function_exists('todoColumnsLoad')) {

	/**
	 * Загрузка настроек колонок пользователя (с нормализацией по каталогу)
	 */
	function todoColumnsLoad(int $iduser, array $defs): array {

		$file = todoColumnsFile($iduser);
		$fc   = [];

		if (file_exists($file)) {

			$fc = json_decode((string)file_get_contents($file), true);

			if (!is_array($fc)) {
				$fc = [];
			}

		}

		return todoColumnsNormalize($fc, $defs);

	}

}

if (!function_exists('todoColumnsSave')) {

	/**
	 * Сохранение настроек колонок пользователя (атомарная запись)
	 */
	function todoColumnsSave(int $iduser, array $columns): void {

		$data = json_encode($columns, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

		if (function_exists('cacheWrite')) {
			cacheWrite(todoColumnsFile($iduser), $data);
		}
		else {
			file_put_contents(todoColumnsFile($iduser), $data);
		}

	}

}

/* ============================ */
/*   Данные карточки клиента    */
/* ============================ */

if (!function_exists('todoClientValues')) {

	/**
	 * Значения колонок из карточки клиента: готовый HTML и текст (для title и экспорта).
	 *
	 * Правила видимости контактов повторяют список Клиентов (content/lists/list.clients.php:427-501).
	 *
	 * @param array $client значения полей клиента (ключи - колонки clientcat, без префикса c_)
	 * @param array $ctx    контекст:
	 *                      clid, pid      - id клиента и контакта напоминания
	 *                      canSee         - доступ к контактам клиента (телефон/почта/сайт)
	 *                      canSeeHistory  - доступ к описанию/активности клиента
	 *                      dicts          - справочники: cat, terr, path, users
	 *                      fields         - доп.поля клиента (todoClientFields())
	 *
	 * @return array ['html' => [ключ => HTML], 'text' => [ключ => текст]]
	 */
	function todoClientValues(array $client, array $ctx = []): array {

		$html = [];
		$text = [];

		$clid = (int)($ctx['clid'] ?? 0);
		$pid  = (int)($ctx['pid'] ?? 0);
		$see  = !empty($ctx['canSee']);
		$seeH = !empty($ctx['canSeeHistory']);

		$dict = (array)($ctx['dicts'] ?? []);

		//название клиента отдельной колонкой не выводится - есть в колонке "Клиент"

		//телефон (phone, при пустом - fax)
		if (array_key_exists('phone', $client)) {

			$raw = trim((string)$client['phone']);

			if ($raw === '' && trim((string)($client['fax'] ?? '')) != '') {
				$raw = trim((string)$client['fax']);
			}

			//для ссылки/маскирования - номер без пробелов, для текста (title, экспорт) - как в карточке
			$tel  = ($raw != '') ? yexplode(",", str_replace(";", ",", str_replace(" ", "", $raw)), 0) : '';
			$textVal = ($raw != '') ? yexplode(",", str_replace(";", ",", $raw), 0) : '';

			$text['client_phone'] = (string)$textVal;

			if ((string)$tel === '') {
				$html['client_phone'] = '';
			}
			elseif ($see) {
				$html['client_phone'] = formatPhoneUrl($tel, $clid, $pid);
			}
			else {

				$hide = hidePhone($tel);
				$hide = is_array($hide) ? implode(', ', $hide) : $hide;

				$html['client_phone'] = '<span class="gray">'.todoH($hide).'</span>';

			}

		}

		//почта
		if (array_key_exists('mail_url', $client)) {

			$mail = ((string)$client['mail_url'] != '') ? yexplode(",", str_replace(";", ",", str_replace(" ", "", (string)$client['mail_url'])), 0) : '';
			$mail = (string)$mail;

			$text['client_mail'] = $mail;

			if ($mail === '') {
				$html['client_mail'] = '';
			}
			elseif ($see) {
				$html['client_mail'] = link_it(todoH($mail));
			}
			else {

				$hide = hideEmail($mail);
				$hide = is_array($hide) ? implode(', ', $hide) : $hide;

				$html['client_mail'] = '<span class="gray">'.todoH($hide).'</span>';

			}

		}

		//сайт
		if (array_key_exists('site_url', $client)) {

			$site   = explode(",", str_replace(";", ",", str_replace(" ", "", (string)$client['site_url'])));
			$site   = trim((string)array_shift($site));

			$text['client_site'] = $site;

			if ($site === '') {
				$html['client_site'] = '';
			}
			elseif ($see) {
				$html['client_site'] = link_it(todoH($site));
			}
			else {
				$html['client_site'] = '<span class="gray">???</span>';
			}

		}

		//адрес
		if (array_key_exists('address', $client)) {

			$text['client_address'] = (string)$client['address'];
			$html['client_address'] = ($text['client_address'] === '') ? '' : todoH($text['client_address']);

		}

		//отрасль
		if (array_key_exists('idcategory', $client)) {

			$value = (string)($dict['cat'][(int)$client['idcategory']] ?? '');

			$text['client_category'] = $value;
			$html['client_category'] = ($value === '') ? '' : todoH($value);

		}

		//территория
		if (array_key_exists('territory', $client)) {

			$value = (string)($dict['terr'][(int)$client['territory']] ?? '');

			$text['client_territory'] = $value;
			$html['client_territory'] = ($value === '') ? '' : todoH($value);

		}

		//источник клиента
		if (array_key_exists('clientpath', $client)) {

			$value = (string)($dict['path'][(int)$client['clientpath']] ?? '');

			$text['client_path'] = $value;
			$html['client_path'] = ($value === '') ? '' : todoH($value);

		}

		//тип отношений
		if (array_key_exists('tip_cmr', $client)) {

			$value = ((string)$client['tip_cmr'] != '') ? (string)$client['tip_cmr'] : 'Не определено';

			$text['client_relation'] = $value;
			$html['client_relation'] = todoH($value);

		}

		//ответственный
		if (array_key_exists('iduser', $client)) {

			$value = (string)($dict['users'][(int)$client['iduser']] ?? '');

			$text['client_user'] = $value;
			$html['client_user'] = ($value === '') ? '' : todoH($value);

		}

		//описание
		if (array_key_exists('des', $client)) {

			$value = todoPlain($client['des']);

			if (!$seeH) {
				$value = ($value === '') ? '' : '???';
			}

			$text['client_des'] = $value;
			$html['client_des'] = ($value === '') ? '' : todoH($value);

		}

		//дата создания
		if (array_key_exists('date_create', $client)) {

			$value = (string)get_sfdate((string)$client['date_create']);

			$text['client_created'] = $value;
			$html['client_created'] = ($value === '') ? '' : todoH($value);

		}

		//основной контакт клиента
		if (array_key_exists('pid', $client)) {

			$cpid  = (int)$client['pid'];
			$value = (string)($ctx['person'] ?? '');

			$text['client_pid'] = $value;
			$html['client_pid'] = ($cpid < 1 || $value === '') ? '' : '<a href="javascript:void(0)" onclick="openPerson(\''.$cpid.'\')" title="Открыть в новом окне"><i class="icon-user-1 blue"></i>'.todoH($value).'</a>';

		}

		//доп.поля
		foreach ((array)($ctx['fields'] ?? []) as $field) {

			$name = $field['fld_name'];

			if (!array_key_exists($name, $client)) {
				continue;
			}

			$key   = 'client_'.$name;
			$value = (string)$client[$name];

			if ($value === '') {

				$text[$key] = '';
				$html[$key] = '';

				continue;

			}

			if ($field['fld_temp'] === 'datum') {
				$value = (string)get_sfdate($value);
			}
			elseif ($field['fld_temp'] === 'multiselect') {
				$value = implode(', ', array_filter(array_map('trim', preg_split('/[;,]/', $value))));
			}
			else {
				$value = todoPlain($value);
			}

			$text[$key] = $value;
			$html[$key] = ($value === '') ? '' : todoH($value);

		}

		return [
			"html" => $html,
			"text" => $text,
		];

	}

}
