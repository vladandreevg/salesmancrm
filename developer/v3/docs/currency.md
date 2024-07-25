# Валюта (currency)

## Метод “currency”

Метод позволяет управлять записями Валют – добавлять, обновлять курс, удалять.

URL для вызова:
```http
http(s)://{{baseurl}}/developer/v3/currency/запрос?параметр=значение
```


Специфичные признаки, используемые далее:

- **id** – уникальный идентификатор валюты в системе
- **datum** – дата добавления/обновления
- **name** – название валюты
- **view** – обозначение валюты (например, руб., после суммы)
- **code** – html-код (unicode) обозначения валюты (перед суммой, как и symbol). [База символов](https://unicode-table.com/ru/blocks/currency-symbols/)
- **symbol** - символ валюты (только из перечисленных ниже)
    - dollar - $
    - euro" - €
    - pound - £
    - yen - ¥,
    - yuan - ￥,
    - grivna - ₴
    - rouble - ք
    - frank - ₣
    - tenge - ₸
- **course** - текущий курс

<a id="info"></a>
### Запрос “info”

Запрос позволяет получить информацию по валюте по её идентификатору - tid.

**Параметры запроса:**

- **id** – уникальный идентификатор записи ( список id можно получить запросом **list** )

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/currency/info
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
  "id" : 1
}
```

**Ответ:**

```json
{
	"data": {
		"id": 1,
		"datum": "2020-03-02",
		"name": "Доллар",
		"view": "",
		"code": "dollar",
		"course": 66.9909,
		"identity": 1,
		"symbol": "$",
		"log": [
			{
				"id": 32,
				"date": "02.03.20,  09:44",
				"datum": "2020-03-02 09:44:51",
				"course": 66.9909,
				"iduser": 1,
				"icon": "&uarr;",
				"direction": "up",
				"color": "green"
			},
			{
				"id": 26,
				"date": "12.11.19,  15:46",
				"datum": "2019-11-12 15:46:05",
				"course": 63.9121,
				"iduser": 1,
				"icon": "&uarr;",
				"direction": "up",
				"color": "green"
			},
			{
				"id": 20,
				"date": "12.11.19,  15:44",
				"datum": "2019-11-12 15:44:49",
				"course": 63.7295,
				"iduser": 1,
				"icon": "&hellip;",
				"direction": "",
				"color": "gray"
			},
			{
				"id": 14,
				"date": "09.11.19,  17:06",
				"datum": "2019-11-09 17:06:50",
				"course": 63.7295,
				"iduser": 1,
				"icon": "&uarr;",
				"direction": "up",
				"color": "green"
			},
			{
				"id": 6,
				"date": "07.11.19,  10:03",
				"datum": "2019-11-07 10:03:48",
				"course": 63.588,
				"iduser": 1,
				"icon": "&uarr;",
				"direction": "up",
				"color": "green"
			},
			{
				"id": 1,
				"date": "07.11.19,  09:52",
				"datum": "2019-11-07 09:52:44",
				"course": 62.34,
				"iduser": 1,
				"icon": "&darr;",
				"direction": "down",
				"color": "red"
			},
			{
				"id": 10,
				"date": "06.11.19,  10:56",
				"datum": "2019-11-06 10:56:19",
				"course": 63.248,
				"iduser": 23,
				"icon": "&darr;",
				"direction": "down",
				"color": "red"
			},
			{
				"id": 11,
				"date": "05.11.19,  10:57",
				"datum": "2019-11-05 10:57:12",
				"course": 64.0316,
				"iduser": 1,
				"icon": "&hellip;",
				"direction": "",
				"color": "gray"
			}
		]
	}
}
```

**Возможные ответы в случае ошибок:**

    403 – Запись с указанным id не найдена в пределах аккаунта указанного пользователя
    404 – Не найдено
    405 – Отсутствуют параметры - id записи


<a id="list"></a>
### Запрос “list”

Запрос позволяет получить список курсов валют.

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/currency/list
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

```json
{
	"data": {
		"5": {
			"id": 5,
			"datum": "2020-03-02",
			"name": "Белорусский рубль",
			"view": "руб.",
			"code": "",
			"symbol": "руб.",
			"course": 29.9347
		},
		"1": {
			"id": 1,
			"datum": "2020-03-02",
			"name": "Доллар",
			"view": "",
			"code": "$",
			"symbol": "$",
			"course": 66.9909
		},
		"2": {
			"id": 2,
			"datum": "2020-03-02",
			"name": "Евро",
			"view": "",
			"code": "€",
			"symbol": "€",
			"course": 73.7235
		},
		"4": {
			"id": 4,
			"datum": "2020-03-02",
			"name": "Казахстанский тенге",
			"view": "тнг.",
			"code": "",
			"symbol": "тнг.",
			"course": 17.5541
		},
		"3": {
			"id": 3,
			"datum": "2020-03-02",
			"name": "Украинская гривна",
			"view": "грв.",
			"code": "",
			"symbol": "грв.",
			"course": 27.257
		},
		"6": {
			"id": 6,
			"datum": "2020-03-02",
			"name": "Японская йена",
			"view": "",
			"code": "¥",
			"symbol": "¥",
			"course": 61.5414
		}
	}
}
```


<a id="add"></a>
### Запрос “add”

Запрос позволяет добавить новую валюту в базу CRM

**Пример формирования запроса:**

```http request
POST http://{{baseurl}}/developer/v3/currency/add
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
  "name": "Тугрик",
  "code": "&#8366;",
  "symbol": "U+20AE",
  "view": "тгр.",
  "course": 0.523
}
```

**Ответ:**

В поле “data” приходит id созданной записи

```json
{
	"result": "Успешно",
	"data": 7
}
```

**Возможные ответы в случае ошибок:**

    405 – Отсутствуют параметры

<a id="update"></a>
### Запрос “update”

Запрос позволяет обновить данные Напоминания по его tid. При этом нет необходимости передавать все данные – можно передать только изменившиеся данные.

**Параметры запроса:**

- **id** – уникальный идентификатор валюты (обязательное поле)
- прочие поля fields – информация для обновления
    
**Пример формирования запроса:**

```http request
POST http://{{baseurl}}/developer/v3/currency/update
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
  "id" : 7,
  "name": "Тугрик",
  "code": "&#8366;",
  "symbol": "₮",
  "view": "тгр.",
  "course": 0.482
}
```

**Ответ:**

```json
{
	"result": "Успешно",
	"data": 7
}
```

**Возможные ответы в случае ошибок:**

    403 – Запись не найдена
    405 – Отсутствуют параметры - id записи


<a id="delete"></a>
### Запрос “delete”

Запрос позволяет удалить запись по её id.

**Параметры запроса:**

- id – уникальный идентификатор записи валюты (обязательное поле)

**Пример запроса:**

```http request
DELETE http://{{baseurl}}/developer/v3/currency/delete
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru

{
  "id": 7
}
```

**Ответ:**

```json
{
	"result": "Успешно",
	"data": 7,
	"message": "Успешно"
}
```

**Возможные ответы в случае ошибок:**

    403 – Запись не найдена
    405 – Отсутствуют параметры - id записи