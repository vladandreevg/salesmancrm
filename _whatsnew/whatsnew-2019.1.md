##########################
##    Текущая версия    ##
##########################

<a id="22032019"></a>
# Версия - 2019.1 (Fix)
#### от 22.03.2019 (build 22032019)

## Новое в релизе:

### История активости в карточке:
  - восстановлено отображение связанного напоминания (если нет прав на удаления Активностей)

### Напоминания:
  - исправлена подгрузка тегов

### Установка системы:
  - исправлен базовый дамп

----------

<a id="18032019"></a>
# Версия - 2019.1 (Fix)
#### от 18.03.2019 (build 18032019)

## Новое в релизе:

### Напоминания:
  - улучшена работа проверки изменения Даты и Типа напоминания совместно с незаполненными полями
  - добавлена проверка для форм: Выполнение напоминания (если ставится новое), История активности (если ставится напоминание), Обращение, Обработка заявки


----------

<a id="15032019"></a>
# Версия - 2019.1 (Fix)
#### от 15.03.2019 (build 15032019)

## Новое в релизе:

### Интерфейс:
  - улучшены всплывающие уведомления - Алерты (SweetAlert2)

### Напоминания:
  - улучшена работа проверки изменения Даты и Типа напоминания совместно с незаполненными полями
  - устранена проблема с показом ответственного за напоминания на вкладке Дела Рабочего стола, если отсутствует Агенда

### Уведомления:
  - скрытие счетчика, если уведомлений нет
  - исправление для уведомлений из обсуждений

### Отчет "Выставленные счета по сотрудникам":
  - добавлена возможность выгрузки в Excel
  - в экспортируемую таблицу добавлена колонка "Дата плановая"
  - выгрузка из отчета также используется в разделе "Выставленные счета"

### Экспорт сделок:
  - в выгрузку добавлена колонка "Дата изменения этапа"

### Почтовик:
  - исправлен выбор файлов из общих папок (если они не привязаны ни к одной из сущностей - Клиент, Контакт, Сделка)

### Настройки пользователя:
  - исправление для локального веб-сервера
  - улучшены переключатели


>
> <b class="red">Важно:</b> Требуется запуск скрипта /_fix2019.php
>

----------

<a id="12032019"></a>
# Версия - 2019.1 (Fix)
#### от 12.03.2019 (build 12032019)

## Новое в релизе:

### Почтовик:
  - исправление сохранения шаблонов
  - исправление сохранения автоподписей

### Прайс, Каталог-склад, База знаний, Файлы:
  - модификация навигации

### Каталог-склад:
  - устранена проблема загрузки раздела "Заявки" ( проблема деления на 0 )

### Карточка клиента:
  - вывод поля с названием "Добавочный" у Контактов

### Настройки пользователя:
  - устранены проблемы с сохранением


>
> <b class="red">Важно:</b> Требуется запуск скрипта /_fix2019.php
>

----------

<a id="11032019"></a>
# Версия - 2019.1 (Fix)
#### от 11.03.2019 (build 11032019)

## Новое в релизе:

* Исправления в установщике
* Исправления в работе с настройками пользователя
* Исправления для произвольных фильтров в разделе Клиенты, Сделки
* Почтовик: накопительные исправления

### Исправления в быстром поиске:
  - добавлено определение, что в поиске участвуют цифры и производится очистка от мусорных символов (не чисел)

### Напоминания:
  - улучшены функции работы с темами (убран автовыбор темы из-за некорректной работы)
  - улучшены функции работы с датами (запрет назначить напоминание на старую дату)
  - если форма вызывается после 20:00, то будет указана завтрашнего дня и время "9:00"

### Почтовик:
  - улучшен выбор адресатов
  - улучшен выбор файлов (исключение отсутствующих файлов, включены файлы и документы из связанных записей)


>
> <b class="red">Важно:</b> Требуется запуск скрипта /_fix2019.php
>

----------

<a id="07032019"></a>
# Версия - 2019.1 (Fix)
#### от 07.03.2019 (build 07032019)

## Новое в релизе:

* Отчет "Новые сделки" - исправлен
* Просмотр напоминания - исправление возможности редактирования
* Составление спецификации, поле "Количество" - восстановлены кнопки изменения (шаг 1)
* Устранена проблема с доступом в раздел Панель управления / Сотрудники
* Устранена проблема с проведением доходов в Бюджете
* Увеличено количество предлагаемых вариантов Темы напоминания в Напоминаниях
* Поддержка длинных вариантов (для типов одиночный, множественный выбор) в Анкетах для сделок

### Импорт клиентов, сделок:
  - исправления для PHP 7.2
  - улучшена форма импорта
  - добавлена поддержка файлов XLSX

### Импорт спецификации:
  - исправления для PHP 7.2
  - добавлена поддержка файлов XLSX
  - улучшена форма импорта

### Импорт прайса:
  - исправления для PHP 7.2
  - улучшена форма импорта
  - добавлена поддержка файлов XLSX, CSV

### Импорт планов по продажам:
  - исправления для PHP 7.2
  - улучшена форма импорта
  - добавлена поддержка файлов XLSX, CSV

### Импорт в ЦИЗ:
  - исправления для PHP 7.2
  - улучшена форма импорта
  - добавлена поддержка файлов XLSX, CSV

### Модуль "Уведомления":
  - добавлены настройки пользователя - какие уведомления он хочет получать

----------

<a id="04032019"></a>
# Версия - 2019.1 (Fix)
#### от 04.03.2019 (build 04032019)

## Новое в релизе:

* Отчет "Новые сделки" - исправления для менеджеров
* Редактор шаблонов Счетов и Актов с поддержкой подсветки кода
* Обновление кэша настроек после редактирования справчника "Источник клиента"
* Исправлена ошибка редактора (используется, например, при составлении Email в почтовике)
* Исправление кодировки в теме некоторых сообщений
* Модуль "Уведомления":
  - добавлена поддержка событий для Напоминания: Добавлено, Изменено, Выполнено

----------

<a id="01032019"></a>
# Версия - 2019.1 (Fix)
#### от 01.03.2019 (build 01032019)

## Новое в релизе:

* Исправлена загрузка Документов, Файлов для сборки под Windows
* Улучшена форма загрузки файлов
* Исправления в интерфейсе карточек - положение кнопки сортировки Документов, Файлов
* Исправления для темы "Черная"
* Исправления для отправки некоторых форм в браузерах типа Chrome
* Исправление признака обязательности заполнения для поля Территория у Клиента

----------

<a id="24022019"></a>
# Версия - 2019.1 (Fix)
#### от 24.02.2019 (build 24022019)

## Новое в релизе:

* Исправлено обновление вкладки "Бюджет" в карточке Поставщика
* Улучшена работа с открытием карточек во фрейме
* Раздел "Сотрудники (таблица)": исправлен
* Модуль "Соискатель": Исправления для PHP 7.2

### Новый субмодуль Уведомления

  - Отображение уведомлений в интерфейсе системы, как дополнение/замена уведомлениям по Email.

### Поддерживаются события:
  - Клиент: новый, изменения, передача, удаление
  - Сделка: новая, изменения, передача, смена этапа, закрытие
  - Счет: оплата счета
  - Обсуждение: новая тема, ответ в теме, закрытие темы
  - Заявки: новая, назначение, обработка

### Характеристики:
  - Отображение только не прочитанных уведомлений
  - Возможность просмотра всех уведомлений
  - Отметка прочитанным при просмотре цели события
  - Уведомления автоматически удаляются через 72 часа


>
> <b class="red">Важно:</b> Требуется запуск скрипта /_fix2019.php
>

----------

<a id="18022019"></a>
# Версия - 2019.1 (Fix)
#### от 18.02.2019 (build 18022019)

## Новое в релизе:

* Обновлен генератор PDF для счетов и актов
* Исправлена ошибка генератора PDF для PHP 7.2
* Откорректированы шаблоны счетов и актов
* Массовая передача клиентов с напоминаниями: исправлена ошибка передачи абсолютно всех напоминаний менеджера
* Прочие мелкие правки

----------

<a id="16022019"></a>
# Версия - 2019.1 (Fix)
#### от 16.02.2019 (build 16022019)

## Новое в релизе:

* Обновлен генератор PDF для счетов и актов
* Исправлена ошибка генератора PDF для PHP 7.2

----------

<a id="15022019"></a>
# Версия - 2019.1 (Fix)
#### от 15.02.2019 (build 15022019)

## Новое в релизе:

* Исправление для плагина smsSender - проблема с отправкой сообщений
* Исправление в стилях плагинов

----------

<a id="13022019"></a>
# Версия - 2019.1 (Relise)
#### от 13.02.2019 (build 13022019)

## Новое в релизе:

### Важное

- Удалена поддержка PHP 5.3
- Добавлена поддержка PHP 7.2
- Обновлен phpMyAdmin до версии 4.8.5 ( поддерживает mySQL 5.5 и выше )
- Единый ключ для обеих версий PHP


### Разное

#### Быстрый/Универсальный поиск
  - улучшена опция строгого поиска (поддержка поиска только в названии)

#### Карточка клиента
  - вывод контактов по алфавиту ( основной контакт всегда первый )
  - улучшен интерфейс общий и интерфейс карточки

#### Удаление Сделки
  - расширенный ответ при ошибке выполнения

#### Смена ответственного
  - исправлена ошибка, возникающая при отсутствии ответственного

#### Напоминания, История активностей
  - Фильтр по Напоминаниям, прикрепленным к Сделкам
  - Фильтр по Активностям, созданным по выполнению Напоминаний

#### Отчет и виджет "Рейтинг по плану"
  - Исправление расчетов

#### Вкладка "Здоровье" рабочего стола
  - Добавлен фильтр по сотрудникам


### По модулям

#### Сделки
  - уведомление ответственному об оплате счета
  - исправления для пользовательских представлений с фильтрами по этапу сделки
  - опция отключения уменьшения прибыли по сделке по расходу (если учтено в себестоимости по спецификации)
  - добавлена колонка "Наша компания"
  - улучшен интерфейс общий и интерфейс карточки

#### Статусы закрытия сделки
  - добавлена интерпретация вариантов и улучшена работа при закрытии сделки
  - упрощение закрытия сделки путем предварительного заполнения полей или предоставления вариантов выбора
  - корректное заполнение полей суммы и прибыли в соответствии с результатом сделки

#### Статус документа
  - исправлено автоматическое изменение даты Акта при печати и одновременной смене статуса на текущую

#### Безопасность
  - скрытие паролей в Почтовике, Сборщике заявок, Почтовом сервере с возможностью отображения

#### Модуль "Бюджет"
  - исправлено проведение прихода для случая, когда сумма прихода больше суммы на р/сч

#### Модуль "Почтовик"
  - фильтр писем по дате
  - добавлен "Чёрный список" - список email, от которых почта не будет приниматься
  - исправлен показ уведомления об отправке письма из карточки и обновление списка писем

#### Модуль "Метрика"
  - исправление установки показателей для сотрудника
  - пояснение по показателю при установке значения

#### Модуль "Сборщик заявок"
  - исправление для алгоритма распределения заявок "Свободная касса": отключено назначение на координатора
  - поддержка приёма, обработки и хранения идентификаторов внешних систем с привязкой к карточкам Клиентов и Сделок. В т.ч. отображение через API

#### Модуль "Обсуждения"
  - предупреждение при создании нового обсуждения, если не добавлены участники
  - улучшен интерфейс общий и интерфейс карточки

#### Модуль "План работ"
  - исправлены найденные ошибки
  - улучшен интерфейс общий и интерфейс карточки