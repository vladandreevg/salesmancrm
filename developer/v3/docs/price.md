# Прайс-лист (price)

## Метод “price”

Метод позволяет управлять записями Прайс-листа – добавлять, обновлять, удалять.

URL для вызова:
```http
http(s)://{{baseurl}}/developer/v3/price/запрос?параметр=значение
```


<a id="fields"></a>
### Запрос “fields”

Запрос позволяет получить список доступных полей, хранящих информацию о клиенте в формате – «Имя поля» - «Расшифровка
назначения» для формирования дальнейших запросов.

В список включены уровни прайса, активированные в Панели управления

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/price/fields
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

```json
{
	"prid": "id",
	"artikul": "Артикул",
	"title": "Наименование",
	"descr": "Описание",
	"edizm": "Ед.изм.",
	"datum": "Дата добавления",
	"category": "Категория",
	"nds": "НДС",
	"price_in": {
		"title": "Закуп",
		"values": "",
		"required": "required"
	},
	"price_1": {
		"title": "Розница",
		"values": "35",
		"required": "required"
	},
	"price_2": {
		"title": "Уровень 1",
		"values": "25",
		"required": ""
	},
	"price_3": {
		"title": "Уровень 2",
		"values": "20",
		"required": "required"
	}
}
```

<a id="info"></a>
### Запрос “info”

Запрос позволяет получить информацию о позиции по идентификатору - id или artikul.

**Параметры запроса:**

- **id** – уникальный идентификатор записи
- или **artikul** – артикул позиции

**Пример запроса**:

```http request
GET http://{{baseurl}}/developer/v3/price/info
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
    "id": 2353,
	"artikul": "7414"
}
```

**Ответ**:

```json
{
	"data": {
		"prid": 2353,
		"artikul": "7414",
		"title": "BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
		"description": "Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться. Lorem Ipsum используют потому, что тот обеспечивает более или менее стандартное заполнение шаблона, а также реальное распределение букв и пробелов в абзацах, которое не получается при простой дубликации \"Здесь ваш текст.. Здесь ваш текст.. Здесь ваш текст..\" \r\n\r\nМногие программы электронной вёрстки и редакторы HTML используют Lorem Ipsum в качестве текста по умолчанию, так что поиск по ключевым словам \"lorem ipsum\" сразу показывает, как много веб-страниц всё ещё дожидаются своего настоящего рождения. За прошедшие годы текст Lorem Ipsum получил много версий. \r\n\r\nНекоторые версии появились по ошибке, некоторые - намеренно (например, юмористические варианты).",
		"descr": "Давно выяснено, что при оценке дизайна и композиции читаемый текст мешает сосредоточиться. Lorem Ipsum используют потому, что тот обеспечивает более или менее стандартное заполнение шаблона, а также реальное распределение букв и пробелов в абзацах, которое не получается при простой дубликации \"Здесь ваш текст.. Здесь ваш текст.. Здесь ваш текст..\" \r\n\r\nМногие программы электронной вёрстки и редакторы HTML используют Lorem Ipsum в качестве текста по умолчанию, так что поиск по ключевым словам \"lorem ipsum\" сразу показывает, как много веб-страниц всё ещё дожидаются своего настоящего рождения. За прошедшие годы текст Lorem Ipsum получил много версий. \r\n\r\nНекоторые версии появились по ошибке, некоторые - намеренно (например, юмористические варианты).",
		"datum": "2018-05-11 09:18:21",
		"price_in": 180000,
		"price_1": 243000,
		"price_2": 225000,
		"price_3": 216000,
		"price_4": 0,
		"price_5": 0,
		"edizm": "шт.",
		"folder": 180,
		"categoryID": 180,
		"categoryName": "Телефония",
		"nds": 0,
		"category": "Телефония",
		"type": "0",
		"typename": "Товар"
	},
	"prid": 2353
}
```

**Возможные ответы в случае ошибок:**

    404 – Не найдено
    405 – Отсутствуют параметры

<a id="list"></a>
### Запрос “list”

Запрос позволяет получить список клиентов, доступных текущему сотруднику, в т.ч. с применением фильтров.

**Параметры запроса** (не обязательные):

- **offset** – страница вывода, с учетом того, что установлен лимит в 200 записей на страницу (по умолчанию offset = 0)
- **order** – поле, по которому будет производится сортировка списка (по умолчанию order = date_create)
- **first** – направление сортировки ( new – сначала новые, old – сначала старые ). (по умолчанию first = new)

**Фильтры** (не обязательные):

- **word** – слово поиска по полям title, description, artikul
- **archive** - фильтр по статусу: yes - только архивные позиции, no - только актуальные. Если не указано - выводятся
  все

**Пример запроса**:

```http request
GET http://{{baseurl}}/developer/v3/price/list
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
	"word": "bizfax"
}
```

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/price/list
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
	"word": "bizfax"
}
```

**Ответ:**

В поле “**data**” приходит список записей, в поле "**count**" - приходит общее количество записей в выборке

```json
{
	"data": [
		{
			"prid": 2354,
			"artikul": "7415",
			"title": "BizFAX E200 факс-сервер, 2 FXO, 1 FXS, 1 RJ45",
			"content": null,
			"edizm": "шт.",
			"category": 154,
			"price_in": 200000,
			"price_1": 270000,
			"price_2": 250000,
			"price_3": 240000,
			"price_4": 0,
			"price_5": 0,
			"archive": "no"
		},
		{
			"prid": 2353,
			"artikul": "7414",
			"title": "BizFAX E100 факс-сервер, 1 FXO, 1 FXS, 1 RJ45",
			"content": null,
			"edizm": "шт.",
			"category": 180,
			"price_in": 180000,
			"price_1": 243000,
			"price_2": 225000,
			"price_3": 216000,
			"price_4": 0,
			"price_5": 0,
			"archive": "no"
		}
	],
	"count": 2
}
```

<a id="add"></a>
### Запрос “add”

Запрос позволяет добавить нового клиента в базу CRM. При этом ответственным устанавливается сотрудник, логин которого
использовался в запросе или указанный отдельно в параметре user

**Параметры запроса**:

- **title** – название клиента (обязательное поле)
- **uid** – id записи во внешней системе (например в 1С)
- **type** – тип записи: client (юр.лицо) – по умолчанию, person (физ.лицо), concurent, contractor, parnter
- **user** – login пользователя в SalesMan CRM назначаемого Ответственным за клиента
    - прочие поля **fields** – информация для добавления
- **recv** – массив реквизитов (см.выше), не обязательное поле

**Пример запроса**:

```http request
POST http://{{baseurl}}/developer/v3/price/add
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
	"artikul" : "1258900",
	"title": "Дополнительная лицензия MyPBX Client MyPBX U500/U510/U520",
	"description": "MyPBX Client – это софтфон, предназначенный для целевого применения со следующими IР-АТС компании Yeаstar серии \"U\": Yeаstar МyРВХ U500, U510 и U520",
	"price_in": 1320.50,
	"price_1": 1456.20,
	"price_2": 1390.40,
	"edizm": "шт.",
	"nds": 18,
	"category": 0
}
```

**Ответ**:

В поле “data” приходит id записи

```json
{
	"result": "Success",
	"text": "Позиция добавлена",
	"data": 4175
}
```

<a id="update"></a>
### Запрос “update”

Запрос позволяет обновить данные позиции.

**Параметры запроса**:

- **id** – уникальный идентификатор
- ИЛИ **artikul** – артикул позиции
- **newartikul** - новый артикул, если его надо поменять

**Пример запроса**:

```http request
POST http://{{baseurl}}/developer/v3/price/update
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
    "id": 4175,
    "newartikul": "1258901",
	"description": "MyPBX Client – это софтфон, предназначенный для целевого применения со следующими IР-АТС компании Yeаstar серии \"U\": Yeаstar МyРВХ U500, U510 и U520",
	"price_in": 1320.50,
	"price_1": 1556.20,
	"price_2": 1490.40,
	"edizm": "шт.",
	"nds": 0,
	"category": 180
}
```

**Ответ**:

В поле “data” приходит id записи

```json
{
	"result": "Success",
	"text": "Позиция обновлена",
	"data": 4175
}
```

**Возможные ответы в случае ошибок**:

    403 – Позиция не найдена


<a id="delete"></a>
### Запрос “delete”

Запрос позволяет удалить позицию.

**Параметры запроса**:

- **id** – уникальный идентификатор записи
- ИЛИ **artikul** – артикул позиции

**Пример запроса:**

```http request
DELETE http://{{baseurl}}/developer/v3/price/delete
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
	"id": 4175
}
```

**Ответ:**

В поле “data” приходит id записи

```json
{
	"result": "Success",
	"text": "Готово",
	"data": 4175
}
```

**Возможные ответы в случае ошибок:**

    403 – Позиция не найдена


<a id="category"></a>
### Запрос “category”

Запрос позволяет получить список категорий

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/price/category
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

```json
[
	{
		"id": 1,
		"title": "! Оборудование",
		"type": "0",
		"typename": "Товар",
		"level": 0,
		"sub": 0
	},
	{
		"id": 156,
		"title": "Тестовая категория 1",
		"type": null,
		"typename": null,
		"level": 1,
		"sub": 1
	},
	{
		"id": 154,
		"title": "Тестовая",
		"type": null,
		"typename": null,
		"level": 2,
		"sub": 156
	}
]
```

<a id="categoryadd"></a>
### Запрос “category.add”

Запрос позволяет добавить новую категорию

**Параметры запроса**:

- **title** – название категории
- **sub** – название головной категории

**Пример запроса**:

```http request
POST http://{{baseurl}}/developer/v3/price/category.add
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
	"title": "TEST 6000",
	"sub": 0
}
```

**Ответ**:

В поле “data” приходит id записи

```json
{
	"result": "Success",
	"text": "Добавлено",
	"data": 285
}
```

**Возможные ответы в случае ошибок**:

    405 – Отсутствуют параметры


<a id="category.update"></a>
### Запрос “category.update”

Запрос позволяет обновить данные категории.

**Параметры запроса**:

- **id** – уникальный идентификатор
- **title** – название категории
- **sub** – id головной категории (если не указано, то будет применен sub = 0)

**Пример запроса**:

```http request
POST http://{{baseurl}}/developer/v3/price/category.update
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
    "id": 286,
    "sub": 3,
	"title": "TEST 6001"
}
```

**Ответ**:

В поле “data” приходит id записи

```json
{
	"result": "Success",
	"text": "Обновлено",
	"data": 286
}
```

**Возможные ответы в случае ошибок**:

    403 – Позиция не найдена


<a id="category.delete"></a>
### Запрос “category.delete”

Запрос позволяет удалить позицию.

**Параметры запроса**:

- **id** – уникальный идентификатор записи

**Пример запроса:**

```http request
DELETE http://{{baseurl}}/developer/v3/price/category.delete
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
	"id": 285
}
```

**Ответ:**

В поле “data” приходит id записи

```json
{
	"result": "Success",
	"text": "Запись удалена.<br>Перемещено - 1 позиций",
	"data": 285
}
```

**Возможные ответы в случае ошибок:**

    403 – Позиция не найдена