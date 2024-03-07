# Валюта (currency)

## Метод “currency”

Метод позволяет управлять записями Валют – добавлять, обновлять курс, удалять.

<pre><code class="html">URL для вызова - http(s)://crm_url/developer/v2/currency?параметр=значение</code></pre>

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

<pre><code class="php">
$params = [
    "login"  => "vladislav@isaler.ru",
    "apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'info',
	"id"     => 1
]

$urlparams = http_build_query($params);

</code></pre>

**Ответ:**

<pre><code class="json">
{
  "data": {
	"id": "5",
	"datum": "2020-03-02",
	"name": "Белорусский рубль",
	"view": "руб.",
	"code": "",
	"course": "29.9347",
	"symbol": "руб.",
	"log": [
	  {
		"id": "31",
		"date": "02.03.20,  09:44",
		"datum": "2020-03-02 09:44:51",
		"course": "29.9347",
		"iduser": "1",
		"icon": "&darr;",
		"direction": "down",
		"color": "red"
	  },
	  {
		"id": "25",
		"date": "12.11.19,  15:46",
		"datum": "2019-11-12 15:46:05",
		"course": "31.1964",
		"iduser": "1",
		"icon": "&darr;",
		"direction": "down",
		"color": "red"
	  },
	  {
		"id": "19",
		"date": "12.11.19,  15:44",
		"datum": "2019-11-12 15:44:49",
		"course": "31.2048",
		"iduser": "1",
		"icon": "&hellip;",
		"direction": "",
		"color": "gray"
	  },
	  {
		"id": "13",
		"date": "09.11.19,  17:06",
		"datum": "2019-11-09 17:06:50",
		"course": "31.2048",
		"iduser": "1",
		"icon": "&uarr;",
		"direction": "up",
		"color": "green"
	  },
	  {
		"id": "9",
		"date": "07.11.19,  10:09",
		"datum": "2019-11-07 10:09:37",
		"course": "31.1248",
		"iduser": "1",
		"icon": "&hellip;",
		"direction": "",
		"color": "gray"
	  },
	  {
		"id": "5",
		"date": "07.11.19,  09:57",
		"datum": "2019-11-07 09:57:46",
		"course": "31.1248",
		"iduser": "1",
		"icon": "&hellip;",
		"direction": "",
		"color": "gray"
	  }
	]
  }
}
</code></pre>

**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Запись с указанным id не найдена в пределах аккаунта указанного пользователя
    404 – Не найдено
    405 – Отсутствуют параметры - id записи


<a id="list"></a>
### Запрос “list”

Запрос позволяет получить список курсов валют.

**Пример запроса:**

<pre><code class="php">
$params = [
    "login"     => "vladislav@isaler.ru",
    "apikey"    => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
    "action"    => 'list'
]

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

<pre><code class="json">
{
  "data": {
	"5": {
	  "id": "5",
	  "datum": "2020-03-02",
	  "name": "Белорусский рубль",
	  "view": "руб.",
	  "code": "",
	  "symbol": "руб.",
	  "course": "29.9347"
	},
	"1": {
	  "id": "1",
	  "datum": "2020-03-02",
	  "name": "Доллар",
	  "view": "",
	  "code": "$",
	  "symbol": "$",
	  "course": "66.9909"
	},
	"2": {
	  "id": "2",
	  "datum": "2020-03-02",
	  "name": "Евро",
	  "view": "",
	  "code": "€",
	  "symbol": "€",
	  "course": "73.7235"
	},
	"4": {
	  "id": "4",
	  "datum": "2020-03-02",
	  "name": "Казахстанский тенге",
	  "view": "тнг.",
	  "code": "",
	  "symbol": "тнг.",
	  "course": "17.5541"
	},
	"8": {
	  "id": "8",
	  "datum": "2020-07-31",
	  "name": "Тугрик",
	  "view": "тгр.",
	  "code": "&#8366;",
	  "symbol": "&#8366;",
	  "course": "0.4820"
	},
	"3": {
	  "id": "3",
	  "datum": "2020-03-02",
	  "name": "Украинская гривна",
	  "view": "грв.",
	  "code": "",
	  "symbol": "грв.",
	  "course": "27.2570"
	},
	"6": {
	  "id": "6",
	  "datum": "2020-03-02",
	  "name": "Японская йена",
	  "view": "",
	  "code": "¥",
	  "symbol": "¥",
	  "course": "61.5414"
	}
  }
}
</code></pre>

**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод


<a id="add"></a>
### Запрос “add”

Запрос позволяет добавить новую валюту в базу CRM

**Пример формирования запроса:**

<pre><code class="http">
GET http://sm2020.crm/developer/v2/currency
Content-Type: application/json
apikey: t1xdeOwWSIqgDol70CkRdK3WD4N4cm
login: vladislav@isaler.ru

{
  "action": "add",
  "name": "Тугрик",
  "code": "&#8366;",
  "view": "тгр.",
  "course": "0.523"
}

</code></pre>

**Ответ:**

В поле “data” приходит id созданной записи

<pre><code class="json">{"result":"Успешно","data":9}</code></pre>


**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    405 – Отсутствуют параметры

<a id="update"></a>
### Запрос “update”

Запрос позволяет обновить данные Напоминания по его tid. При этом нет необходимости передавать все данные – можно передать только изменившиеся данные.

**Параметры запроса:**

- **id** – уникальный идентификатор валюты (обязательное поле)
- прочие поля fields – информация для обновления
    
**Пример формирования запроса:**

<pre><code class="http">
GET http://sm2020.crm/developer/v2/currency
Content-Type: application/json
apikey: t1xdeOwWSIqgDol70CkRdK3WD4N4cm
login: vladislav@isaler.ru

{
  "action": "update",
  "id" : 8,
  "name": "Тугрик",
  "code": "&#8366;",
  "symbol": "₮",
  "view": "тгр.",
  "course": "0.482"
}

</code></pre>

**Ответ:**

<pre><code class="json">{"result":"Успешно","data":9}</code></pre>


**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Запись не найдена
    405 – Отсутствуют параметры - id записи

<a id="delete"></a>
### Запрос “delete”

Запрос позволяет удалить запись по её id.

**Параметры запроса:**

- id – уникальный идентификатор записи валюты (обязательное поле)

**Пример запроса:**

<pre><code class="http">
GET http://sm2020.crm/developer/v2/currency
Content-Type: application/json
apikey: t1xdeOwWSIqgDol70CkRdK3WD4N4cm
login: vladislav@isaler.ru

{
  "action": "delete",
  "id": 8
}

</code></pre>

**Ответ:**

<pre><code class="json">{"result":"Успешно","data":8,"message":"Успешно"}</code></pre>


**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Запись не найдена
    405 – Отсутствуют параметры - id записи