##########################
##    Текущая версия    ##
##########################

<a id="10012020"></a>
# Версия - 2019.4 (Fix)
#### от 10.01.2020 (build 10012020)

## Исправления

- Модуль "План работ": расширение минимальной даты Проекта, Работы
- Модуль "Проекты": расширение минимальной даты Проекта, Работы


<a id="27122019"></a>
# Версия - 2019.4 (Fix)
#### от 27.12.2019 (build 27122019)

## Исправления

- Исправления для интеграции с Астериск


<a id="26122019"></a>
# Версия - 2019.4 (Fix)
#### от 26.12.2019 (build 26122019)

## Исправления

- Напоминания: исправления отображения для сотрудников с ролью Менедежер


<a id="24122019"></a>
# Версия - 2019.4 (Fix)
#### от 24.12.2019 (build 24122019)

## Исправления

- История активностей: исправлены значки результатов выполнения дел ( если активность привязана к напоминанию )
- Исправлен первоначальный дамп ( ошибка добавления сделко из-за отсутствия Направлений )
- Дела на следующий год - месяц ( не верно задан год )


<a id="10122019"></a>
# Версия - 2019.4 (Fix)
#### от 10.12.2019 (build 10122019)

## Исправления

- Новая сделка: восстановлены
    - компания по-умолчанию - последняя использовавшаяся
    - направление и тип сделки по-умоляанию
    - этап сделки по умолчанию

- Отчет "Статистика входящих интересов" - исправлен расчет общего количества закрытых


<a id="06122019"></a>
# Версия - 2019.4 (Fix)
#### от 06.12.2019 (build 06122019)

## Исправления

- Выбор сотрудников в разделе История активностей: выделены не активные сотрудники
- Восстановлена печать отчетов ( Не все отчеты корректно поддерживают печать )
- Улучшен импорт прайса ( Создание категорий без дублей )
- План работ: 
    - увеличено минимальное значение выбора даты ( до -6 месяцев )
    - исправлена смена статуса работы (выбор даты, сохранение формы)
    - исправлен расчет з.пл. Исполнителей (в плане интерфейса)
- Импорт банковских выписок:
    - доработан парсер для поддержки выгрузок компаний с единственным р.сч.


<a id="21112019"></a>
# Версия - 2019.4 (Fix)
#### от 21.11.2019 (build 21112019)

## Исправления

- Модуль "Проекты"
  - Добавлены фильтры для Заданий
  - Исправлен интерфейс вывода Заданий
  - Добавление/Выполнение задания интегрировано в форму добавления Напоминаний

- Модуль "Соискатель"
  - Добавление/Выполнение задания интегрировано в форму добавления Напоминаний

- Разное:
  - История звонков: Исправлен фильтр
  - Синхронизациия звонков Asterisk: исправления в синхронизации
  - Исправлено удаление сделки из карточки
  - перерасчет сделки при изменении позиции спецификации


<a id="15112019"></a>
# Версия - 2019.4 (Fix)
#### от 15.11.2019 (build 15112019)

## Исправления

- Разное:
  - скрытие надписи Тип заказчика в форме Сделки, если есть запрет на сделки для Контактов
  - исправление работы с частичной отгрузкой и сервисными актами


<a id="14112019"></a>
# Версия - 2019.4 (Fix)
#### от 14.11.2019 (build 14112019)

## Исправления

- Модуль "Почтовик": незначительные исправления
  - добавление альтернативного текста к сообщению
  
  
- Модуль "Каталог-склад"
  - возможность удалить ордера из Карточки сделки
  - корректное добавление приходного ордера без заявки с привязкой к сделке
  - email-уведомления переведены на системные уведомления (от них можно отписаться)


- Разное:
  - вывод документов в карточке клиента, если он не привязан к Сделке
  - предотвращено открытие карточки при редактировании Клиента
  - исправлено обновление блока Контактов при редактировании Контакта
  - исправлен экспорт отчета Оплата по сотрудникам
  - клонирование Сделки (установка Направления и Типа сделки из оригинала)
  - привязка к напоминанию Контакт не имеющего привязки к Клиенту
  - перерасчет сделки при изменении позиции спецификации
  - открытие карточки Контакта при добавлении (если контакт не привязан к Клиенту)
  - установка контакта основным в Экспресс-форме


<a id="12112019"></a>
# Версия - 2019.4 (Fix)
#### от 12.11.2019 (build 12112019)

## Добавлено

- Поддержка валют:
   - возможность выставлять счета в заданной валюте
   - печать Счетов, Документов и Актов в заданной валюте
   - фиксация курса валюты в конкретной сделке с обновлением при выставлении Счета
   - добавлены новые теги для шаблонов Счетов и Актов, относящиеся к валюте:
     - {{currencyName}} - Название валюты
     - {{currencySymbol}} - Знак валюты
     - {{currencyCourse}} - Курс валюты
   - добавлены новые теги для шаблонов Документов, относящиеся к валюте:
     - [currencyName] - Название валюты
     - [currencySymbol] - Знак валюты
     - [currencyCourse] - Курс валюты


## Исправления

- Модуль "Почтовик": 
   - исправление с подсчетом количества страниц
   - работа с разрешениями браузера - если разрешения на уведомления не дано, то уведомления будут показаны внутри страницы
   - исправлены ссылки в скрытом блоке
   - исправлена проблема сохранения Автоподписей с изображениями


- Шаблоны Счетов и Актов
   - исправление проблемы с отсутствием шаблонов

- Акты:
   - исправлен экспорт
   - в экспорт добавлен номер договора по сделке


- Спецификация:
   - исправлена установка типа позиции при редактировании


- Отчеты - исправления фильтра по сотрудникам:
   - week - Активности по клиентам
   - ent-ActivitiesByStepByDeals - Активности по этапам (по сделкам)
   - ent-ActivitiesByUserByDeals - Активности по пользователям (по сделкам)
   - ent-ActivitiesByUserByStepByDeals - Активности сотрудников по этапам (по сделкам)


<a id="05112019"></a>
# Версия - 2019.4 (Fix)
#### от 05.11.2019 (build 05112019)

## Исправления

- Модуль "Проекты": добавлен статус проекта "Пауза"
- Модуль "Почтовик": незначительные исправления
- Модуль "Генератор документов": отладка


<a id="03112019"></a>
# Версия - 2019.4 (Fix)
#### от 03.11.2019 (build 03112019)

## Исправления

- Не критичные исправления


<a id="01112019"></a>
# Версия - 2019.4 (Fix)
#### от 01.11.2019 (build 01112019)

## Исправления

- Почтовик: исправлен выбор файлов из карточек
- Исправление некоторых проблем в интерфейсе в Google Chrome
- Модуль "Корпоративный университет": исправлен расчет прогресса курса
- Модуль "Каталог-склад": решение проблемы из-за добавления нескольких позиций одной номенклатуры


<a id="30102019"></a>
# Версия - 2019.4 (Fix)
#### от 30.10.2019 (build 30102019)

## Исправления

- Установка текущего времени при добавлении активности ( форма История активности )
- Исправлен отчет "Оплаты по сотрудникам" ( ent-PaymentsByUser )
- Восстановлено редактирование статуса документа
- Исправлена проверка доступа к документу по сотрудникам


<a id="29102019"></a>
# Версия - 2019.4 (Fix)
#### от 29.10.2019 (build 29102019)

## Исправления

- Итоговые суммы для печати Актов


<a id="28102019"></a>
# Версия - 2019.4 (Fix)
#### от 28.10.2019 (build 28102019)

## Исправления

- Исправления в интерфейсе под планшеты
- Исправлена проблема в разделе Документы карточек ( меню Добавить )


<a id="25102019"></a>
# Версия - 2019.4 (Relise)
#### от 25.10.2019 (build 25102019)

>
> <b class="red">Важно:</b> Требуется запуск скрипта /_update.php
>

## Исправления

* Добавление/Изменение счета - исправлено добавление суффикса счета
* Карточка клиентов: добавлен вывод поля "принятие решений" для списка контактов
* Списки Клиентов, Контактов, Сделок - исправлен доступ к чужим записям
* Список Контактов - исправлена сортировка по Клиенту


## Глобальные изменения

* Панель управления: phpMyAdmin заменен на Adminer - легковесное и быстрое решение по управлению БД
* Переработано ядро
* Переработан модуль Почтовик
* Переработан модуль Сборщик заявок
* Обновлены классы сторонних разработчиков и перенесены в папку "vendor"
* Новый модуль "Корпоративный университет"


## По модулям

### Напоминания и дела

 - Добавлен признак "Статус выполнения"
    - Выполнено: зеленая иконка - галочка
    - Отменено: красная иконка - перечеркнутый круг
    
 - Добавлен вывод статуса
    - в блоке "Выполненные"
    - в разделе "История активностей"
    - в карточке Клиента, Сделки
    
 - Уведомления
    - учитывается статус выполнения
    
 - Доработаны отчеты по активностям - добавлен фильтр по активностям, привязанным к напоминаниям
    - ent-activitiesByTime.php
    - ent-ActivitiesByUserByDeals.php
    - ent-ActivitiesByStepByDeals.php


### Почтовик

 - модуль полностью переработан
 - исправлены найденные ошибки
 - улучшена стабильность и скорость работы


### Акты

 - поддержка частичной отгрузки товара:
   - возможность создания актов на часть позиций спецификации
   - отображение привязки позиций спецификации к актам
   - отображение комплекности спецификации актами
   
 - добавлен фильтр по собственным компаниям
 - в экспорт включен вывод компании, от которой выставлен счет


### Документы

 - фоновое обновление списка доступных документов в карточках Клиента, Сделки
 - добавлен фильтр по собственным компаниям
 - добавлены фильтры по срокам актуальности документов
 - добавлена подсветка в зависимости от количества дней до конца периода действия


### Выставленные счета

 - добавлен фильтр по собственным компаниям
 - в экспорт включен вывод компании, от которой выставлен счет


### Сборщик заявок

 - добавлены теги для шаблонов уведомлений - ID клиента, контакта, сделки ( может понадобиться для создания ссылок )


## Отчеты
  
* Выполнение годового плана
  - добавлен экспорт в Excel

* Доработаны отчеты - добавлена фильтрация по Компаниям
  - ent-PaymentsByDay
  - ent-InvoiceStateByUser
  - ent-PaymentsByUser
  - ent-PaymentsForRoistat
  - ent-ActivitiesByStepByDeals.php
  - ent-ActivitiesByUserByDeals.php


## Для разработчиков

* Добавлен Autoload - теперь нет необходимости подключать вручную требуемые классы
* События ( Events ) - изменен способ отправки данных по событию ( для ускорения работы )


## Добавлено

### Модуль "Корпоративный университет"

* Возможность составлять обучающие наборы лекций с использованием различных материалов
    - видео с youtube, vimeo
    - загружаемые файлы (документы MS Office конвертируются в PDF)
    - собственный текст с возможностью импорта содержимого со сторонних сайтов
    
### Плагины

* Сделали бесплатными плагины, работающие с ботами
    - Уведомление сотрудников в Телеграм, Viber, Slack
    - Статистика - получение краткой статистики в Телеграм, Viber