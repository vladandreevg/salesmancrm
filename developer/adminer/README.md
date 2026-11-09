Custom Adminer
==============

Наша копия Adminer для разработки: **Adminer 6.2.0** (сборка `adminer.php`, PHP >= 7.4).

Структура
---------

- `adminer.php` — официальная минифицированная сборка 6.2.0. Обновляется заменой файла с [adminer.org](https://www.adminer.org/).
- `index.php` — bootstrap: объявляет глобальную `adminer_object()` и возвращает `new \Adminer\Plugins([...])`.
- `adminer-plugins/` — официальные плагины v6.2.0 (глобальные классы, наследующие `Adminer\Plugin`), их подключает `index.php`.
- `adminer.css` — **основная тема** (design) для всех пользователей: сейчас pepa-linha из официального набора v6.2.0.
- `plugins/`, `css/`, `fonts/`, `images/` — наследие Adminer 4.x (Pematon-тема и старый plugin loader). **Не используются**: в Adminer 5+ класс `AdminerPlugin` удалён, эти файлы при подключении падают с `Class "Adminer" not found`; каталог `css/` больше не читается.

Тема (design)
-------------

В Adminer 5/6 тема — это просто файл `adminer.css` (и опционально `adminer-dark.css`) **рядом с `adminer.php`**. `Adminer::css()` подхватывает его сам и печатает после встроенного `default.css`, поэтому тема действует для всех и никаких плагинов не требует.

Установка pepa-linha (уже сделана, повтор для обновления Adminer):

1. Взять файл из тега нужной версии: `https://raw.githubusercontent.com/vrana/adminer/v6.2.0/designs/pepa-linha/adminer.css`.
2. Положить как `adminer.css` в этот каталог. Первая строка должна остаться `/* Adminer design pepa-linha */` — по ней Adminer определяет имя дизайна и сверяет контрольную сумму с официальным набором (в v6.2.0 — `baf25f0`).
3. Проверка: в HTML страницы есть `<link rel="stylesheet" href="adminer.css?v=…">`; на странице «Сервер» виден дизайн и его checksum.
4. Откат к встроенному дизайну: удалить `adminer.css` (и `adminer-dark.css`).

Переключение нескольких тем (не для основной): официальный плагин [`designs.php`](https://raw.githubusercontent.com/vrana/adminer/v6.2.0/plugins/designs.php) v6.2.0 — `new AdminerDesigns([<путь к css> => <название>])`. Плагины `AdminerTheme`/`AdminerDesigns`/`theme-switcher` от Adminer 4.x несовместимы и ломают отправку заголовков (`Cannot modify header information`).

Включённые плагины
------------------

`database-hide`, `tables-filter`, `table-structure`, `dump-zip`, `edit-foreign`, `foreign-system`.

Как добавить плагин
-------------------

1. Скачать файл из тега нужной версии, например `https://raw.githubusercontent.com/vrana/adminer/v6.2.0/plugins/dark-switcher.php`, положить в `adminer-plugins/`.
2. Если у класса есть обязательные аргументы конструктора — добавить экземпляр в массив `$plugins` в `index.php`.

Требование: класс в глобальном namespace и `extends Adminer\Plugin` (как в официальном наборе). Плагины от Adminer 4.x несовместимы — для 5+ они должны вызывать функции через `Adminer\`.

Совместимость
-------------

PHP 7.4+ (у нас PHP 8.5, FastCGI), Adminer 6.2.0, современный браузер.
