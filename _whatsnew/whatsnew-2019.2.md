##########################
##    Текущая версия    ##
##########################

<a id="26062019"></a>
# Версия - 2019.2 (Fix)
#### от 26.06.2019 (build 26062019)

## Исправления

* Модуль "Центр исходящих звонков"
  - улучшение алгоритма работы с заданиями для нескольких сотрудников


<a id="24062019"></a>
# Версия - 2019.2 (Fix)
#### от 24.06.2019 (build 24062019)

## Исправления

* Почтовик
  - исправления в интерфейсе

* Форма сделки
  - исправление в форме клонированной сделки

* Закрытие сделки
  - исправлено автоматическое обнуление оборота и маржи по сделке


<a id="24062019"></a>
# Версия - 2019.2 (Fix)
#### от 24.06.2019 (build 24062019)

## Исправления

* Карточка Клиента
  - исправлен блок История звонков

* Редактирование клиента
  - исправления в интерфейсе

* ВАТС Ростелеком
  - доработка


<a id="20062019"></a>
# Версия - 2019.2 (Fix)
#### от 20.06.2019 (build 20062019)

## Исправления

* Список клиентов:
  - Исправлен фильтр по группам

* Работа со счетами:
  - Исправлено применение шаблона

* Отчеты:
  - Исправлен отчет Рейтинг сотрудников ( ent-userRaiting.php ) для учета планов продаж по закрытым сделкам с оплаченными счетами

* Панель управления:
  - Исправлена ошибка сохранения банковских реквизитов в разделе Мои компании
  - Исправлено сохранение формы в разделе Номера документов

## Дополнения

* Добавлена интеграция с ВАТС Ростелеком


<a id="06062019"></a>
# Версия - 2019.2 (Fix)
#### от 06.06.2019 (build 06062019)

## Исправления

* Модуль "Почтовик":
  - исправлен предпросмотр входящего сообщения ( отображение вложений )

* Работа со счетами:
  - восстановлена кнопка "Внести оплату" для Обычных сделок

* Панель быстрого поиска:
  - исправлена проблема фатальной ошибки из-за транскрипции спец.символов
  - улучшен алгоритм поиска

## Дополнения

* Работа с актами:
  - добавлена настройка "Шаблон акта по-умолчанию" ( Сервисные сделки ) - Панель управления / Общие настройки / Дополнения к сервисным сделкам
  - добавлена настройка "Шаблон акта по-умолчанию" ( Обычные сделки ) - Панель управления / Общие настройки / Дополнения к сделкам

* Работа со счетами:
  - добавлена настройка "Шаблон счета по-умолчанию" ( Сервисные сделки ) - Панель управления / Общие настройки / Дополнения к сервисным сделкам
  - добавлена настройка "Шаблон счета по-умолчанию" ( Обычные сделки ) - Панель управления / Общие настройки / Дополнения к сделкам
  - возможность создания акта при внесении оплаты
  - возможность создать документ указанного типа после поступления оплаты счета
  
* Список клиентов:
  - В фильтр по группам добавлен пункт "Вне групп" - в случае его выбора поиск осуществляется только по клиентам, не входящим ни в какие группы

* Панель управления:
  - Шаблоны документов: разделение по типам документов

* Генератор документов:
  - Поддержка шаблонов Excel ( XLSX ), дополнена документация


<a id="29052019"></a>
# Версия - 2019.2 (Fix)
#### от 29.05.2019 (build 29052019)

## Исправления

* Заполнение реквизитов через сервис Dadata
  - Поддержка реквизитов для ИП
  - Улучшен раздел Панель управления / Мои компании

* Отчеты
  - История звонков ( call_history.php ): исправлены ошибки
  - Активности по сделкам ( work.php ): добавлен экспорт в Excel, разбивка по сотрудникам

* Виджеты
  - "Сделки к продлению": исправлена ошибка, если не используются сервисные сделки

* Экспорт истории звонков
  - Изменен формат даты для форматирования в Excel

* Модуль "Проекты":
  - просмотр Проекта  - убрана кнопка "Добавить работу", если пользователь не является куратором Проекта
  - исправление ссылки на проект в email-уведомлении
  
* Напоминания:
  - возможность снять признак "Весь день"


## Дополнения

* Выбор периодов ( История активностей, История звонков, Заявки, Обращения )
  - Добавлена кнопка "Применить" при выборе конкретных дат ( чтобы не выполнялись лишние запросы до установки нужного периода )

* Работа со счетами:
  - Автонумерация установлена по умолчанию
  - Плановая дата оплаты автоматически устанавливается как +5 дней от текущей даты
  - Добавлена возможность создания акта при отметке оплаты
  
* Работа с актами:
  - При создании акта добавлен автоматический выбор не оплаченного счета и расчетного счета по умолчанию ( сервисные сделки )
  - Отправка счета + акта по Email - исправлена привязка не корректного счета (не нового, а на который создан акт)



<a id="17052019"></a>
# Версия - 2019.2 (Fix)
#### от 17.05.2019 (build 17052019)

## Исправления

* Мини-виджет "Новые сделки" - исправлена выборка по автору сделки ( было по Ответственному )

* Обновлены виджеты:
  - Выполнение плана, Выполнение плана руководителя ( в т.ч. для мобильных устройств )
  - Воронка активности
  
* Виджет модуля "Метрика":
  - исправлена ошибка запроса при расчете показателя "Количество новых счетов"

* Групповые действия
  - исправлена ошибка передачи всех напоминаний при передаче Клиента со сделками
  - накопительные исправления для групповых манипуляций со Сделками

* Оптимизация кода ( проводится постоянно )


## Интерфейс

* Разделы "Клиенты", "Сделки"
  - Возможность перетаскивания столбцов мышкой с сохранением параметров
  - Улучшен шаблон вывода
  - Улучшен конфигуратор колонок

* Панель управления
  - группировка webhook по названию


## Дополнения

* Модуль "Проекты"
  - просмотр Работы - блок "Результат" выводится только для статусов "Пауза", "Выполнен", "Отменен"
  - исправление: для Исполнителей отключена возможность перехода в карточки Клиентов, Контактов, Сделок
  - исправление: для Исполнителей отключена возможность редактирования Работ
  - исправление: для Исполнителей отключена возможность ставить Задания и прикреплять файлы к чужим Работам
  - в списке Проектов ( поле Названия проекта ) добавлен вывод Направления и Компании ( информация из сделки ), если в системе указано более 1 Направления и более 1 Компании
  - в списке Проектов добавлена фильтрация по Направлениям, Компаниям 
  - добавлена опция: при добавлении работ в проект не менять конечную дату проекта
  

## Плагины

* Универсальный автоотправитель:
  - накопительные исправления
  
* Скрипты продаж:
  - добавлена возможность выбора форм для срабатывания
  - добавлена возможность выбора сотрудников, для которых будет показан блок Скрипта
  - накопительные исправления

----------


<a id="28042019"></a>
# Версия - 2019.2 (Fix)
#### от 28.04.2019 (build 28042019)

## Исправления

 :) странно, конечно, но их нет.

## Интерфейс
  
* Карточка Клиента, Сделки, Контакта
  - убрали ограничение блока Истории активностей по высоте
  
* Календарь на месяц ( Рабочий стол )
  - вывод 5 недель в высоту блока

* Виджеты "Выполнение плана", "Выполнение плана руководителя"
  - улучшено отображение на мобильных устройствах

## Дополнения

* Модуль "Проекты"
  - модуль подключен к системе уведомлений CRM
  - внесены улучшения в оформлении
  - добавлена поддержка открытия карточки проекта во фрейме (текущее окно браузера)

* Права сотрудника
  - добавлена опция "может редактировать Закрытые сделки"


----------

<a id="19042019"></a>
# Версия - 2019.2 (Fix)
#### от 19.04.2019 (build 19042019)

## Исправления

* Правки в интерфейсе
  - улучшена работа поля "мультиселект" при перетаскивании элементов
  - корректировки темы "Чёрная"
  - отключена система корректировки ширины столбцов для разделов Клиенты, Сделки

* Исправление прав доступа в карточке сделки

* Экспорт сделок - исправлен вывод столбца "Компания"

## Интерфейс

* Виджеты: улучшен мобильный вид
  - Метрика
  - Контроль платежей
  - Оплаты по сотрудникам
  - Сделки к вниманию
  - Закрытые сделки
  - Сделки к продлению

* Виджет "Сделки к продлению"
  - изменена логика работы виджета для того, чтобы отображать сделки, которые могут послужить источником повторной продажи. Теперь в него попадают сделки, закрытые 1 год ( ± 20 дней ) назад, или сервисные сделки, у которых Период заканчивается в пределах ± 20 дней? с условием, что в настоящий момент у Клиента нет активных сделок (с учетом того, сервисная это сделка или нет)
  
* Список дел
  - добавлен вывод дня недели для актуальных дел

## Дополнения

* Модуль "Проекты"
  - добавлен лог смены статусов Проектов и Работ
  - добавлена возможность комментирования каждой смены статуса Проекта и Работы


## Изменения

* Раздел "Напоминания" на Рабочем столе:
  - Вывод просроченных напоминаний - ограничение 1 месяц
  - Вывод будущих напоминаний - ограничение + 5 дней
  - В просроченных и в напоминаниях с датой +2 от текущей агенду не выводим

* Раздел "Дела" ( Напоминания, Дела ):
  - В просроченных и в напоминаниях с датой +2 от текущей агенду не выводим

* Проверка на изменение Времени и Типа напоминания
  - переведена в опции
  - включается в Панели управления / Общие настройки / Дополнения общие - "Предупреждения"

Важно! Изменения в вывод напоминаний внесены для оптимизации нагрузки на базу данных


>
> Вернули скрипт-фикс _fix2019.php - если возникли какие-то проблемы, то попробуйте запустить его из браузера
> 

----------


<a id="16042019"></a>
# Версия - 2019.2 (Fix)
#### от 16.04.2019 (build 16042019)

## Исправления

* Список сделок
  - Исправлен вывод в представлении "Все активные сделки"

* Карточка сделки
  - Исправлена возможность добавления Счетов
  - Исправлен вывод блока из Каталога-склада
  
* Модуль "Группы"
  - Возможность добавить клиента в группу, не привязанную к сервису рассылок, если у Клиента отсутствует email

----------


<a id="15042019"></a>
# Версия - 2019.2 (Relise)
#### от 15.04.2019 (build 15042019)


>
> <b class="red">Важно:</b> Требуется запуск скрипта /_update.php
>


# Новое в версии:


## Важное

### Защита информации
  - добавлено частичное скрытие полного номера телефона, email в поиске и в разделах Клиенты, Контакты, если у сотрудника нет доступа к этой информации
  - добавлено скрытие номера, email в форме Экспресс, Обращение

### Счета, Акты
  - добавлена поддержка собственных шаблонов счетов
  - добавлена поддержка собственных шаблонов актов



## Разное

### Напоминания
  - перемещен блок с опциями напоминаний ( Только чтение, Напоминать )
  - добавлены опции в формы Активность, Обращение, Обработка заявки
  - добавлена опция напоминания "На весь день"
  - улучшен внешний вид CRON-скрипта для отправки дел на весь день ( скрипт /cron/tasksToday.php )

### Редактор шаблонов ( Счетов, Актов )
  - Улучшена вставка тегов
  - Добавлена поддержка горячих клавиш ( см. подсказку )
  - Возможность восстановить выбранный базовый шаблон
  - Добавлен шаблон Приходно-кассового ордера

### Список сотрудников
  - новая отрисовка орг.структуры

### Интерфейс
  - оптимизирован интерфейс Меню / Меню управления, улучшено отображение информации
  - внесены исправления в темы оформления
  - улучшены всплывающие блоки некоторых виджетов Рабочего стола
  - улучшена работа с закрепленными заголовками в разделах
  - интеллектуальная система регулировки колонок в разделах Клиенты, Сделки скрывает малозначимые колонки в зависимости от того влезают они в ширину экрана или нет. При этом жестко фиксируется ширина колонки с Названием Клиента/Сделки

### Панель управления
  - в раздел "События в системе" добавлены фильтры по сотруднику, по типу события, по периоду

### Блок "Счета и Спецификации" в карточке сделки
  - изменен порядок вывода счетов - сначала более свежие
  - для сервисных сделок оплаченные счета выделены в отдельный, сворачиваемый блок (если их больше 2-х)
  - поддержка большого количества товара в спецификации (фиксированный заголовок и высота блока максимум 70% экрана)
  - улучшена форма добавления/изменения спецификации

### SalesMan API  ([Документация](https://salesman.pro/api2/about "SalesMan API"))
  - добавлены новые параметры для шаблонов счетов и актов (выбор шаблона)
  - добавлен новый параметр "на весь день" для Напоминаний
  - добавлена поддержка приема данных в формате JSON



## По модулям

### Новый модуль "Проекты" [ [Документация](https://salesman.pro/docs/126) ] 
  - на основе доработанного под общепринятый функционал модуля "План работ"

### Модуль "План работ" [ [Документация](https://salesman.pro/docs/114) ] 
  - фильтры по дате для раздела "Задания по дням"
  - фильтры по дате для раздела "Работы"
  - открытие карточки во фрейме

### Модуль "Соискатель" [ [Документация](https://salesman.pro/docs/126) ] 
  - открытие карточки во фрейме

### Модуль "ЦИЗ" [ [Документация](https://salesman.pro/docs/112) ] 
  - исправлена работа с сервисом HyperScript
  - добавлена поддержка работы нескольких сотрудников по одному заданию
  - в конструкторе заданий подсвечивать списки по результату обработки (обработан/нет). Если обработан, то выводить дату и время обработки и комментарий
  - добавлена выгрузка результатов обработки в Excel

### Модуль "База знаний" [ [Документация](https://salesman.pro/docs/53) ] 
  - вынос закрепленных статей в блок "Документация" (левый нижний угол)
  - сохранение выбранной статьи после редактирования

### Модуль "Почтовик" [ [Документация](https://salesman.pro/docs/100) ] 
  - улучшен просмотр письма в списке писем, добавлено закрепление заголовка



## Прочие Улучшения и Исправления

  - исправлена подгрузка тегов для Активностей и Напоминаний
  - исправлен фильтр "только без напоминаний" в разделе "Сделки"
  - Экспресс-форма - работа поля Головная организация
  - исправлена проблема открытия Карточек из фрейма ( всегда открывалась первая открытая запись )
  - возможность удалить типовые темы для Напоминаний
  - улучшена форма для групповых действий в разделе Клиенты, Контакты и Сделки
  - улучшена работа отчета "Воронка продаж"
  - исправлены некоторые отчеты с проблемой работы на PHP 7.2