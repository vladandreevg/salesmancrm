# Общие сведения

## Общая информация

> <br>
> Текущая версия **SalesMan API 2.0** доступна с версии **SalesMan CRM v.2018.9**. Документация предыдущей версии **SalesMan API 1.0** доступна [здесь](/api/).

SalesMan CRM поддерживает следующие способы расширения функциональности:

1. **RestAPI** - SalesMan API — это специальный интерфейс для разработчиков, позволяющий интегрировать возможности системы SalesMan практически с любым открытым веб-сервисом или desktop-приложением. API предоставляет возможности для управления записями Клиентов, Контактов, Напоминаний, Сделок и Лидов.
    
    > <br>
    > **Работа с API бесплатна для всех, достаточно получить ключ к нему в Панели управления.** <br>
    > Ключ доступа ( API KEY ) не следует передавать другим или делать видимым в коде веб-страниц, т.к. в этом случае кто-нибудь может воспользоваться им и отправлять сообщения от вашего имени.<br>
    > Рекомендуем изменить ( перегенерировать ) API KEY перед использованием интеграции.
    > 
     
2. **Events** - система событий, позволяющая выполнить сторонний код или отправить запрос во внешний скрипт (посредством GET или POST запросов) в зависимости от происходящих в системе событий (например, Добавление нового клиента). Доступна только для коробочного варианта поставки. Система Events является вспомогательной для системы Webhooks.
     
3. **WebHooks** - система, позволяющая отправить информацию из CRM во внешнюю систему по указанному URL (т.е. система работает в обратную сторону, относительно RestAPI).

## Принцип использования RestAPI

Обращение к методам API — это HTTP-запрос к URL вида:

```html
http(s)://crm_url/developer/v2/method?action=list&apikey=key&arg1=param_1&arg2=param_2
```

*  **crm_url** - адрес вашего экземпляра CRM, к которому происходит подключение
*  **method** - название метода
*  **action** – название запроса
*  **apikey** - ключ доступа к API
*  **param_1** ... **param_n** - аргументы метода, свои для каждого метода

> ### Важно
> - **Все параметры должны быть в кодировке UTF-8.** 
> - В примере выше параметры указаны в GET-запросе, но можно передавать их и в POST. Более того, параметр apikey мы настоятельно рекомендуем передавать через POST, чтобы он не сохранялся в логах прокси-серверов.
> - Параметры должны передаваться в виде URL-кодированной строки запроса ( на PHP это делает функция http_build_query )


### Передача данных в формате JSON

Начиная с версии 2019.2 вы можете передавать свои данные в виде json-строки, однако в данном случае в Header должны быть переданы следующие параметры:

* Content-Type: application/json
* apikey: ваш_ключ
* login: логин_пользователя

> ### Важно
> - **apikey и login** в данном случае не надо передавать вместе с данными - они будут проигнорированы


Ответ приходит в виде объекта формата JSON.

**Результат успешного вызова метода**

Если вызов успешен, то объект будет содержать поле "result", содержимое которого зависит от вызванного метода, и не будет содержать поля "error".

```json
[{"result":"Успешно","data":"1456"}]
```

**Результат не успешного вызова метода**

Признаком ошибки при выполнении метода является наличие в объекте ответа поля "error" с HTML-сообщением об ошибке. Кроме того, в объекте ответа в случае ошибки будет ещё и поле "code" со строковым кодом ошибки.

```json
[{"result":"Error","error":{"code":"405","text":"Отсутствуют параметры - Название клиента"}]
```


<br>
<a id="auth"></a>
## Авторизация

Авторизация выполняется с помощью передачи в каждом запросе обязательных параметров:

*     **login** – логин пользователя, от имени которого выполняются запросы
*     **apikey** – ключ API, который можно получить в Панели управления / Общие настройки / Настройки безопасности

**Возможные ответы в случае ошибок:**

*     400 – Не верный API key
*     401 – Неизвестный пользователь
*     402 – Неизвестный метод

**Рекомендации:**

1.  Рекомендуем создать отдельного пользователя с ролью «Руководитель с доступом», т.к. ему будут доступны данные по всем сотрудникам CRM
2.  Передавайте параметры, обработанные функцией http_build_query

<br>
## Быстрый старт

Мы подготовили для вас примеры для работы со всеми методами и запросами API. Их можно

<a class="btn btn-large btn-success btn-right action " href="/download/repo/salesman-api-v2-examples.zip" target="_blank" title="Примеры запросов">Скачать примеры</a>

<br>
## Принцип использования Events

Подключить свои обработчики на те или иные события можно с помощью файла "developer/events.php", поставляемого с открытым исходным кодом в коробочном варианте. Файл содержит перечень поддерживаемых событий, а также некоторые примеры использования.

<br>
## Принцип использования WebHooks

**Webhook** — механизм получения уведомлений об определённых событиях в SalesMan CRM (в основном о действиях пользователей) во внешних приложениях. Интересен он может быть, в первую очередь, для разработчиков и интеграторов, т.к. дополняет систему событий (Events), RestAPI и дает возможности двухстороннего обмена данными именно тогда, когда они изменяются.
