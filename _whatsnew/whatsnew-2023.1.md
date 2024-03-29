##########################
##    Текущая версия    ##
##########################

<a id="22112023"></a>
# Версия - 2023.1 (Fix)
#### от 22.11.2023 (build 22112023)

## Исправления

Накопительные исправления, в т.ч.
1. Модуль "Контрольные точки"
    - исправлено указание КТ при её добавлении в логе

2. Модуль "Почтовик"
    - исправлено отображение некоторых писем (улучшена очистка от стилей, распространяющихся на весь интерфейс)


<a id="10112023"></a>
# Версия - 2023.1 (Fix)
#### от 10.11.2023 (build 10112023)

## Исправления

Накопительные исправления, в т.ч.
1. Карточки Клиента, Сделки, контакта
    - исправление выбора страницы для истории активностей


<a id="19102023"></a>
# Версия - 2023.1 (Fix)
#### от 19.10.2023 (build 19102023)

## Исправления

Накопительные исправления, в т.ч.
1. Сделка
    - исправление работы с расходами на Партнеров/Поставщиков ( в предыдущем фиксе есть ошибка !!! )
    - исправление спецификации


<a id="18102023"></a>
# Версия - 2023.1 (Fix)
#### от 18.10.2023 (build 18102023)

## Исправления

Накопительные исправления, в т.ч.
1. Сделка
    - Исправление работы с расходами на Партнеров/Поставщиков

2. План продаж
    - возможность руководителю (подразделений, отделов) управлять планами сотрудников


<a id="06102023"></a>
# Версия - 2023.1 (Fix)
#### от 06.10.2023 (build 06102023)

## Исправления

Накопительные исправления, в т.ч.
1. Сделка
    - Исправление работы с расходами на Партнеров/Поставщиков
    - Пересчет прибыли при отмене отметки "Уменьшать прибыль"
    - Восстановлено отображение счетов при отсутствии номера счета

2. Удаление этапов
    - добавлено удаление записей лога для выбранного этапа


<a id="18092023"></a>
# Версия - 2023.1 (Fix)
#### от 18.09.2023 (build 18092023)

## Исправления

Накопительные исправления, в т.ч.
1. Обсуждения
    - Возможность удалить закрытое обсуждение пользователем с администраторскими правами


<a id="27072023"></a>
# Версия - 2023.1 (Fix)
#### от 27.07.2023 (build 27072023)

## Исправления

Накопительные исправления, в т.ч.
1. Список сделок
    - исправлены некоторые доп.фильтры

2. Настройка
    - доп.настройка "Не показывать в карточках Клиента чужие напоминания" (Панель управления / Общие настройки / Дополнения общие)

## Для разработчиков

- новый хук **invoice_undo** - отмена оплаты счета


<a id="05072023"></a>
# Версия - 2023.1 (Fix)
#### от 05.07.2023 (build 05072023)

## Исправления

Накопительные исправления, в т.ч.
1. Типы и шаблоны документов
    - добаление/редактирование шаблонов документов добавлено в раздел "Типы документов"

2. Обновлен плагин "Листы рассылок+"
    - накопительные исправления 
    - добавлен сервис Dashamail (Россия)
    - добавлены предупреждения о сервисах из недружественных стран 
    - добавлен признак - Территория (для записей клиента), Собственная компания (для записей Сделок, не работает для записей клиента)

3. Добавлена возможность замены фона формы авторизации на свой
   - в файле /inc/config.php следует добавить строку:
   ```php
    $wallpaper = 'path/to/img';
    ```
   где **path/to/img** - путь до изображения (в пределах папки с црм). например: "cash/wallpaper.jpeg" (без начального слеша)


<a id="07062023"></a>
# Версия - 2023.1 (Fix)
#### от 07.06.2023 (build 07062023)

## Исправления

Накопительные исправления, в т.ч.
1. Модуль "Корпоративный университет"
   - исправлена неработоспособность добавления нового курса


<a id="25052023"></a>
# Версия - 2023.1 (Fix)
#### от 25.05.2023 (build 25052023)

## Исправления

Накопительные исправления


<a id="17052023"></a>
# Версия - 2023.1 (Fix)
#### от 17.05.2023 (build 17052023)

## Исправления

Накопительные исправления, в т.ч.
1. Спецификация
    - экспорт переведен на формат XLSX (был CSV)
2. Отчет "Воронка продаж"
    - исправлен фильтр


<a id="11052023"></a>
# Версия - 2023.1 (Fix)
#### от 11.05.2023 (build 11052023)

## Исправления

Накопительные исправления, в т.ч.
1. Модуль "Проекты"
    - исправлена проблема добавления напоминаний
    - исправлены фильтры по Компании и Направлению
    - исправлена ошибка при смене статуса Проекта/Работы


<a id="10052023"></a>
# Версия - 2023.1 (Fix)
#### от 10.05.2023 (build 10052023)

## Исправления

Накопительные исправления, в т.ч.
1. Модуль "Проекты"
    - исправлена проблема добавления работ
2. Карточка Клиента
    - исправлена проблема удаления карточки


<a id="02052023"></a>
# Версия - 2023.1 (Fix)
#### от 02.05.2023 (build 02052023)

## Исправления

Накопительные исправления, в т.ч.
1. Исправлен скрипт обновления
2. База знаний
    - улучшено поведение при редактировании статьи
3. Актуализирован плагин smsSender


<a id="18042023"></a>
# Версия - 2023.1 (Fix)
#### от 18.04.2023 (build 18042023)

## Исправления

Накопительные исправления, в т.ч.
1. Сделки
   - удаление сделки
   - клонирование сделки
2. Панель управления
   - добавление мультиворонки


<a id="10042023"></a>
# Версия - 2023.1 (Fix)
#### от 10.04.2023 (build 10042023)

## Исправления

Накопительные исправления, в т.ч.
1. Модуль "Почтовик"
    - улучшена работа с письмами подчиненных, выводимых при включенной опции "Объединение сообщений" в разделе "Вся почта"
2. Модуль "Сборщик заявок"
    - исправлена ошибка с отсутствием временной зоны
    - исправлен скрипт для cron


<a id="29032023"></a>
# Версия - 2023.1 (Fix)
#### от 29.03.2023 (build 29032023)

## Исправления

Накопительные исправления, в т.ч.
1. Интеграция с Телефонией
   - добавлена поддержка Asterisk 20.х ( теоретически должен работать с версии 13+ )
   - оптимизирован раздел История звонков, исправлены фильтры
2. Счета
   - исправлена функция отмены оплаты счета
   - исправлена функция создания акта и счета в Сервисных сделках 
3. Контакт
   - исправлено редактирование данных контакта, если не указан Источник и поле Источник активно
4. Модуль "Почтовик"
    - исправлено определение кодировки для писем со смешанной кодировкой (текст и вложения имеют разные кодировки)


<a id="17032023"></a>
# Версия - 2023.1 (Fix)
#### от 17.03.2023 (build 17032023)

## Исправления

Накопительные исправления, в т.ч.
1. Модуль "Контрольные точки"
   - включены в Уведомления (колокольчик)
   - добавлены фильтры и хуки по КТ для разработчиков
2. Модуль "Почтовик"
   - исправлена дата отправленного письма для некоторых случаев
3. Cron-скрипт "Проверка почты сотрудников"
   - улучшена работа, в т.ч. с поддержкой timezone
4. Спецификация
   - исправлен тип позиции при добавлении спецификации из формы сделки (вместе с добавлением новой сделки) 


<a id="13032023"></a>
# Версия - 2023.1 (Fix)
#### от 13.03.2023 (build 13032023)

## Исправления

Накопительные исправления, в т.ч.
  1. Модуль "Почтовик"
    - исправлена дата письма для некоторых случаев


<a id="07032023"></a>
# Версия - 2023.1 (Fix)
#### от 07.03.2023 (build 07032023)

## Исправления

Накопительные исправления, в т.ч.
  1. Модуль "Почтовик"
    - улучшена очистка от emoji, наличие которых приводит к ошибке
    - при включенной опции "Объединение сообщений" в разделе "Вся почта" отображаются все письма подчиненных сотрудников для ролей Руководитель организации, Руководитель отдела или при наличии прав администратора
    - для всех сотрудников дополнительно включаются сообщения пользователей, у которых они указаны Заместителем
  2. Модуль "Прайс-лист" (+ Модуль "Каталог-склад")
    - исправлена проблема с ценами при редактировании позиции из "Каталог-склад"
  3. Плагин "Зарплата продавцов и руководителей"
    - исправления

> P.S. для получения почты сотрудников независимо от того, авторизуется он или нет - используйте плагин "Планировщик заданий" со стандартной задачей "Получение почты сотрудников"


<a id="01032023"></a>
# Версия - 2023.1 (Fix)
#### от 01.03.2023 (build 01032023)

## Исправления

Накопительные исправления, в т.ч.
  - Добавлен поиск исполняемого файла PHP для OS Linux ( для применения в обновлениях, в плагине "Планировщик заданий" )
  - Обновлен плагин "Планировщик заданий"
  - Исправлена работа с добавлением расходов в модуле "Бюджет"
  - Улучшена работа редактора папок модуля "Файлы"

>
> <b class="red">Важно:</b> Рекомендуется запуск скрипта по адресу **/update** или **/_install/update.php**
>


<a id="22022023"></a>
# Версия - 2023.1 (Fix)
#### от 22.02.2023 (build 22022023)

## Исправления

Накопительные исправления, в т.ч.
  - ошибка добавления папки в каталоге файлов
  - обновлен Adminer

>
> <b class="red">Важно:</b> Рекомендуется запуск скрипта по адресу **/update** или **/_install/update.php**
>


<a id="20022023"></a>
# Версия - 2023.1 (Fix)
#### от 20.02.2023 (build 20022023)

## Исправления

1. Накопительные исправления, в т.ч.
- исправлена проблема со шрифтами при генерации счетов в PDF
- исправлена проблема установки обновления на старых версиях mySQL (5.5)

2. Доработано:
- раздел Мои дела на "Рабочем столе" - добавлены фильтры по важности/срочности
- раздел Все дела - добавлены фильтры по важности/срочности


> <b class="red">Важно:</b> При возникновении проблем с генерацией pdf в счетах рекомендуем удалить файл **installed-fonts.json** в папках:
> - /cash/dompdf/
> - /vendor/dompdfFontsCastom/


<a id="15022023"></a>
# Версия - 2023.1 (Fix)
#### от 15.02.2023 (build 15022023)

## Исправления

- Накопительные исправления


<a id="08022023"></a>
# Версия - 2023.1 (Fix)
#### от 08.02.2023 (build 08022023)

## Исправления

- Накопительные исправления, в т.ч.
  - исправлена работа с Планом продаж


<a id="06022023"></a>
# Версия - 2023.1 (Fix)
#### от 06.02.2023 (build 06022023)

## Исправления

Накопительные исправления, в т.ч.
  - исправлен расчет здоровья для модуля "Здоровье сделок"
  - исправлена проблема добавления/редактирования Клиента/Контакта в некоторых случаях

>
> <b class="red">Важно:</b> Рекомендуется запуск скрипта по адресу **/update** или **/_install/update.php**
>


<a id="01022023"></a>
# Версия - 2023.1 (Fix)
#### от 01.02.2023 (build 01022023)

## Исправления

1. Модуль "Почтовик"
  - исправлена работа с разделом Черновик
  - исправлено имя отправителя (ранее был email)

2. Накопительные исправления


<a id="29012023"></a>
# Версия - 2023.1 (Fix)
#### от 29.01.2023 (build 29012023)

## Исправления

1. Накопительные исправления, в т.ч.
  - исправлена работа с доп.полями Сделок, Клиентов, Контактов
  - исправлена работа скрипта для планировщика заданий модуля "Сборщик заявок"


<a id="25112022"></a>
# Версия - 2023.1 (Fix)
#### от 25.01.2023 (build 25012023)

## Исправления

1. Накопительные исправления

2. Модуль "каталог-склад"
  - исправлена работа с доп.полями типа "radio", "checkbox"
  - исправлен просмотр из спецификации данных из модуля, вместо данных из Прайса

>
> <b class="red">Важно:</b> Рекомендуется запуск скрипта по адресу **/update** или **/_install/update.php**
>


<a id="22012023"></a>
# Версия - 2023.1 (Fix)
#### от 22.01.2023 (build 22012023)

## Исправления

1. Плагин "Статистика. Бот"
  - накопительные исправления

2. Плагин "Уведомление пользователей. Бот"
  - накопительные исправления


<a id="20012023"></a>
# Версия - 2023.1 (Relise)
#### от 20.01.2023 (build 20012023)

В этой версии был переработан код системы на ~80%, включая модули, плагины, отчеты, найдены и исправлены различные ошибки:

- Рабочий стол
  - переработан вывод списков из счетчиков (нижний левый угол)

- Настройки
  - возможность включать/отключать виджеты из Фин.блока (по умолчанию отключены) - Настройки общие / Дополнения общие
  - возможность включить/отключить субмодуль "Здоровье сделок" (по умолчанию отключены) - Настройки общие / Дополнения к сделкам

- Разделы Клиенты, Сделки, Контакты
  - добавлена возможность выбора нескольких записей мышкой при нажатой клавише Ctrl (выбор производится по первой колонке) 

- Карточки Клиента, Сделки
  - исправлена работа с доступами

- Модуль Финансы/Бюджет
  - доработаны расчеты
  - добавлен фильтр по статьям

- Модуль "Метрика"
  - исправлены расчеты для виджета и отчета

- Модуль "Каталог-склад"
  - исправлены ссылки в меню

- Модуль "Группы"
  - Удалена интеграция с сервисами рассылок
  - Этот функционал в расширенном виде есть в плагинах:
    - Листы рассылок
    - Листы рассылок Плюс

- Отчеты
  - Переработаны все имеющиеся отчеты


## Плагины

- Новый плагин "Автоматические Контрольные точки"
  - Плагин расчитан на автоматическое создание Контрольных точек (КТ) при создании сделки.

- В состав дистрибутива включен плагин "Листы рассылок" - упрощенная версия плагина "Листы рассылок Плюс"

- Плагин "Планировщик заданий"
  - добавлен скрипт "Тотальная очистка"
  - добавлен скрипт "Разморозка сделок"


# Установка обновления

- Версия требует новый ключ
- В состав дистрибутива включен бесплатный однопользовательский ключ.
- Для установки требуется
    - загрузить на сервер обновленные файлы (дистрибутив — только обновление),
    - запустить скрипт /_install/update.php

>
> <b class="red">Важно</b>
> - Крайне рекомендуется выполнять обновление через терминал, если размер базы данных значителен, т.к. вносятся изменения в её структуру
> ```php
> php /path_to_crm/_install/update.php
> ```
>

>
> <b class="red">Рекомендуем</b>
> - При проблемах с добавлением записей Клиентов, Контактов, Сделок рекомендуется выполнить скрипт
>   - из консоли:
> ```php
> php /path_to_crm/_install/fix_default_values.php
> ```
>    - или из браузера:
> ```html
> https://адрес_црм/_install/fix_default_values.php
> ```
>