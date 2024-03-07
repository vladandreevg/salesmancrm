![SalesMan CRM](https://salesman.pro/docs.img/salesman-48x.png)

SalesMan CRM - бесплатная профессиональная OpenSource система управления продажами
======

SalesMan CRM является универсальной системой управления продажами для компаний малого и среднего бизнеса. Это веб-приложение для ведения и безопасного хранения клиентской базы, истории взаимоотношений с клиентами и управления продажами, в том числе и с удаленных компьютеров (через Интернет), работает через браузер. Разработка ведется с 2007 года.

![SalesMan CRM](https://salesman.pro/docs.img/big-transparent-monitors.png)

## Основные возможности / Features

- [Единая база Клиентов и Контактов](https://salesman.pro/docs/44) с настраиваемыми параметрами
- Учет продаж с помощью [Сделок](https://salesman.pro/docs/67) - от получения заявок, выставления КП, счетов, до закрывающих документов
- Любые типы [дел и напоминаний](https://salesman.pro/docs/27) с фиксацией результатов выполнения
- Супер-удобный [Рабочий стол](https://salesman.pro/docs/25) с виджетами основной информации + Более 60 различных отчетов
- [Финансовый учет](https://salesman.pro/docs/78) и планирование
- [Генератор документов](https://salesman.pro/docs/52) на основе word- и excel-шаблонов
- [Почтовый клиент](https://salesman.pro/docs/100) для полученияи написания писем из CRM
- Интеграция с [IP-телефонией](https://salesman.pro/docs/59)
- Хранилище файлов
- Хранилище прайсов
- Внутренние коммуникации по клиентам и сделкам - [Обсуждения](https://salesman.pro/docs/51)
- [База знаний](https://salesman.pro/docs/53)
- [Корпоративный университет](https://salesman.pro/docs/149)
- И многое другое описано в [Документации](https://salesman.pro/docs/51)

## Возможности для разработчиков / Features for Developers

- [API](https://salesman.pro/api2/)
- [Система фильтров и хуков](https://salesman.pro/api2/hooks)
- [Система плагинов](https://salesman.pro/docs/115)

## Системные требования / System requirements

### Веб-сервер Apache/Nginx

- mod_rewrite - необходим для корректной работы
- mod_php - желателен, но не обязателен (позволяет менять настройки php через файл .htaccess)

> При использовании Nginx требуется настроить перенаправления вручную

### База данных: MySQL / MariaDB

- версия 5.6+ (рекомендуем MySQL 8.x / MariaDB 10.x),
- кодировка utf8
- отсутствие записей в директиве sql-mode:
  - STRICT_TRANS_TABLES
- если записи sql-mode нет в файле настроек, то необходимо добавить строку с перезагрузкой MySQL:
  `sql-mode="NO_ENGINE_SUBSTITUTION,ALLOW_INVALID_DATES"`

>    Чтобы узнать текущий параметр sql-log используйте команду:
> 
>    `show variables like 'sql_mode';`

Для MySQL 8.0 рекомендуем перевести все таблицы на движок InnoDB, либо сделать это для самых больших таблиц

### PHP

- 7.2...8.1 (работа с PHP >8.1 не гарантируется)
- кодировка сервера utf-8,
- short_open_tag = on - важно, т.к. в противном случае будут выходить ошибки,
- curl, mbstring, zip
- php5-dom для функции работы с XML-файлами
- openssl
- dom, gd для генератора счетов и актов в PDF
- imagick для генератора QR-кодов в счетах и документах
- imap для работы с почтой
- отсутствие модуля php-domxml - он конфликтует с функцией создания PDF файлов (в счетах и актах)
- параметр max_execution_time = 300 - для выполнения нагруженных скриптов, например при получении/отправке почты, при создании резервной копии БД или её восстановлении

> Проверка соответствия производится при установке системы. Та же информация доступна в разделе "Панель управления / Обслуживание / Информация о системе"

### Операционная система

- Windows (в т.ч. Server, Web Server) - некоторый функционал может не работать
- Linux, Unix, Mac, в т.ч. виртуальный хостинг VDS рекомендуем
- другие системы поддерживающие работу mySQL и PHP

## Загрузка и Установка / Download & Install

### Ручное развертывание

1. Скачать дистрибутив с сайта https://salesman.pro/download
2. Распаковать в папку, к которой подключен домен
3. Передать права на все папки/файлы пользователю веб-сервера (например apache)
4. Перейти в браузере по заданному адресу
5. Провести [установку](https://salesman.pro/docs/2)


### Развертывание с помощью командной строки

Действия производятся в командной строке.

```shell
# Переходим в каталог, подготовленный под установку CRM
cd /var/www/
# Скачиваем дистрибутив
curl -k -# "https://salesman.pro/download/getfile.php" -o salesman.zip
# Распаковываем
unzip salesman.zip
# Удаляем архив
rm salesman.zip
```
- где */var/www/* путь до папки, привязанной к црм

Далее переходим в браузер по url для SalesMan CRM и производим установку (п.5 ручной установки)

### Развертывание с помощью скрипта установки

1. Скачать [скрипт](https://salesman.pro/download/repo/install.auto.zip) авторазвертывания
2. Распаковать в папку на сервере, в которой будет развернут дистрибутив SalesMan CRM
3. Выполнить скрипт в браузере - https://youcrm/install.auto.php
   - скрипт скачивает дистрибутив и распаковывает его в текущей папке
4. Провести установку (п.5 ручной установки)

### Развертывание из репозитория [GitHub](https://github.com/iandreyev/salesman.git)

```shell
# Установка git
yum git install
# Переходим в каталог установки
cd /var/www/
# Клонируем репозиторий
git clone https://github.com/iandreyev/salesman.git
```

## Обновление / Update

Обновление SalesMan CRM состоит в обновлении дистрибутива и, при необходимости, внесения изменений в структуру БД.

1. Произвести действия, аналогичные установке (кроме п.5)
    - для автоматической установки можно выполнить скрипт /_install/update.cli.php из браузера или командной строки (так же создает дамп БД)
    - возможно обновить дистрибутив командами:
   ```shell
   cd /var/www/
   curl -k -# "https://salesman.pro/download/getfile.php?v=update" -o update.zip
   unzip update.zip
   rm update.zip
   ```
   
2. Перейти в браузере по адресу /_install/
3. Провести необходимые действия

## Структура файлов проекта / The structure of the project files

см. [Structure](/developer/Structure.md)

## История изменений / Changelog

История изменений хранится в папке /_whatsnew/

- [v2024.1](/_whatsnew/whatsnew-2024.1.md)
- [v2023.1](/_whatsnew/whatsnew-2023.1.md)
- [v2022.3](/_whatsnew/whatsnew-2022.3.md)
- [v2022.2](/_whatsnew/whatsnew-2022.2.md)
- [v2021.4](/_whatsnew/whatsnew-2021.4.md)
- [v2020.3](/_whatsnew/whatsnew-2020.3.md)
- [v2020.1](/_whatsnew/whatsnew-2020.1.md)
- [v2019.4](/_whatsnew/whatsnew-2019.4.md)
- [v2019.3](/_whatsnew/whatsnew-2019.3.md)
- [v2019.2](/_whatsnew/whatsnew-2019.2.md)
- [v2019.1](/_whatsnew/whatsnew-2019.1.md)
- [v2018.9](/_whatsnew/whatsnew-2018.9.md)
- [v2018.6](/_whatsnew/whatsnew-2018.6.md)
- [v2018.3](/_whatsnew/whatsnew-2018.3.md)

История изменений с 2007 по 2018 год значения не имеет :)

## Вклад / Contribution

Вклад всегда приветствуется и рекомендуется!

Требования к вкладу:

- Когда вы вносите свой вклад, вы соглашаетесь предоставить Андрееву Владиславу Германовичу неисключительную лицензию на использование этого вклада в любом контексте, который мы (Андреев Владислав Германович) считаем целесообразным.
- Если вы используете контент, предоставленный другой стороной, он должен быть соответствующим образом лицензирован с использованием лицензии с открытым исходным кодом.
- Вклады принимаются только через запросы на загрузку с Github.

## Лицензирование / License

Мы присоединяемся к сообществу OpenSource ПО и с версии 2024.1 распространяем SalesMan CRM под лицензией [Apache 2.0](/LICENSE), но оставляем за собой право изменить лицензию в будущем.

## Авторские права / Copyright

Авторские права зарегистрированы Федеральной службой интеллектуальной собственности РФ - Свидетельство о государственной регистрации программы для ЭВМ №2021668294 от 12.11.2021 г.

- Автор: Андреев Владислав Германович
- Правообладатель: Андреев Владислав Германович
- Email для контактов: v@isaler.ru

Используемые компоненты сторонних разработчиков (по типам лицензий) можно найти в [NOTICE](/NOTICE).

## Ссылки / Links

- [Сайт](https://salesman.pro/)
- [Группа vk.com](https://vk.com/salesmancrm)
- [Канал Telegram](https://t.me/salesman_channel)
- [Канал Youtube](https://www.youtube.com/c/smancrm)