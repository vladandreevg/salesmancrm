# Бюджет (budget)


## Метод “budget”

Метод позволяет управлять записями Бюджета:
*     добавлять, изменять, удалять записи о расходах/приходах;
*     добавлять, изменять, удалять категории расходов/доходов;
*     проводить и отменять платежи;
*     перемещать средства между счетами;


<pre><code class="html">URL для вызова - http(s)://crm_url/developer/v2/budget?параметр=значение</code></pre>

<a id="fields"></a>
### Запрос “fields”

Запрос позволяет получить список доступных полей, хранящих информацию о бюджете в формате – «Имя поля» - «Расшифровка назначения» для формирования дальнейших запросов.

**Пример запроса:**

<pre><code class="php">
  $params = [
 	"login" => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action" => "fields"
  ]

$urlparams = http_build_query($params);

</code></pre>

**Ответ:**

<pre><code class="json">
{
    {
    "data":{
        "id":"Идентификатор записи расхода\/дохода",
        "cat":"Категория расхода дохода из таблицы budjet_cat",
        "title":"Название расхода\/дохода",
        "des":"Описание",
        "year":"Год",
        "mon":"Месяц",
        "summa":"Сумма",
        "datum":"Дата изменения записи",
        "iduser":"id пользователя",
        "do":"Отметка о проведении",
        "rs":"id расч. счета",
        "rs2":"id расч. счета для перемещения средств между счетами",
        "fid":"id файла",
        "did":"id сделки",
        "conid":"clid для поставщиков",
        "partid":"clid для партнеров"
        }
     }
}
</code></pre>


**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод


<a id="info"></a>
### Запрос “info”

Запрос позволяет получить информацию о записи расхода/прихода по его идентификатору - id.

**Параметры запроса:**

- **id** – уникальный идентификатор записи клиента

**Пример запроса**:


<pre><code class="php">
$params = [
	"login"    => "vladislav@isaler.ru",
	"apikey"   => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"   => 'info',
	"id"     => '15'
];

$urlparams = http_build_query($params);

</code></pre>


**Ответ**:

<pre><code class="json">
{
	"data":{
		"id":"15",
		"cat":"10",
		"title":"Заработкая плата сотрудников",
		"des":"",
		"year":"2013",
		"mon":"7",
		"summa":"960000.00",
		"datum":"2013-07-10 20:59:22",
		"iduser":"1",
		"do":"on",
		"rs":"1",
		"rs2":"",
		"fid":"",
		"did":"0",
		"conid":"0",
		"partid":"0"
	}
}
</code></pre>


**Возможные ответы в случае ошибок:**

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Бюджет с указанным id не найден в пределах аккаунта указанного пользователя
    405 – Отсутствуют параметры - id расхода/прихода


<a id="add"></a>
### Запрос “add”


Запрос позволяет добавить запись о расходе/приходе в базу CRM. При этом ответственным устанавливается сотрудник, логин которого использовался в запросе или указанный отдельно в параметре iduser

**Параметры запроса**:

- **title** – название расхода/дохода (обязательное поле)
- **cat** – id категории бюджета из таблицы budjet_cat
- **des** – описание
- **iduser** – login пользователя в SalesMan CRM назначаемого Ответственным за клиента
- **summa** – сумма расхода/дохода
- **do** - отметка о проведении("on" - проведен)
- **rs** - id расчетного счета
- **did** - id сделки

**Примечание**:

Параметр **datum** может быть указан в запросе. Если он отсутствует, то будет принят timestamp. При пустом поле **iduser** ответственным будет назначен текущий пользователь (из запроса).


**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => "vladislav@isaler.ru",
	"apikey"      => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"      => "add",
	"title"       => "Заправка принтера",
	"cat"         => "1",
	"bmon"        => "7",
	"byear"       => "2018",
	"do"          => "on",
	"rs"          => "19"
]

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey=%aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=add&title=Заправка+принтера&cat=1&bmon=7&byear=2018&do=on&rs=19</code></pre>

**Ответ**:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Успешно добавлен","data":107}</code></pre>

Если есть отметка о том, что расход нужно провести, но на указанном р/сч недостаточно средств, то получим сообщение

<pre><code class="json">{"result":Запись добавлена. Расход не проведен, так как недостаточно средств на счете","data":107}</code></pre>

**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    407 - Отсутствует параметр - Название расхода/дохода


<a id="update"></a>
### Запрос “update”


Запрос позволяет изменить данные расхода/дохода по его id. При этом нет необходимости передавать все данные по расходу/доходу – можно передать только изменившиеся данные.

**Параметры запроса**:

- id – уникальный идентификатор записи расхода/дохода (обязательное поле)
- прочие поля fields – информация для обновления

**Примечание**:

- При передаче пустого поля - данные поля будут очищены 
- Можно передавать только те данные, которые нужно обновить


**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => LOGIN,
	"apikey"      => KEY,
	"action"      => "update",
	"id"          => "526"
	"title"       => "Покупка печатной бумаги",
	"cat"         => "1",
	"bmon"        => "8",
	"byear"       => "2018",
	"rs"          => "16"
];

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey=%aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=update&id=526&title=Покупка+печатной+бумаги&cat=1&bmon=7&byear=2018&do=on&rs=19</code></pre>

**Ответ**:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Успешно обновлено","data":526}</code></pre>

Если есть отметка о том, что расход нужно провести, но на указанном р/сч недостаточно средств, то в ответ получим: 

<pre><code class="json">{"result":Запись о расходе изменена. Расход не проведен, так как недостаточно средств на счете","data":526}</code></pre>

**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 - Записи с таким id не существует


<a id="delete"></a>
### Запрос “delete”


Запрос позволяет удалить указанную запись расхода/дохода по id. Метод работает только в случае, если расход не проведен.

**Параметры запроса**:

- **id** – уникальный идентификатор записи расхода/дохода (обязательное поле)

Пример запроса:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey=aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=delete&id=114
</code></pre>

Ответ:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Удалено","data":114}</code></pre>

Возможные ответы в случае ошибок:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Запись бюджета с указанным id не найдена в пределах аккаунта
    405 – Отсутствуют параметры - id расхода/дохода
    406 – Невозможно удалить расход/доход, т.к. он был проведен. Попробуйте отменить проведение


<a id="doit"></a>
### Запрос “doit”


Запрос позволяет провести платеж, указав его id.

**Параметры запроса**:

- **id** – уникальный идентификатор записи расхода/дохода (обязательное поле)

Пример запроса:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey=aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=doit&id=522
</code></pre>

Ответ:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Успешно проведен","data":522}</code></pre>

Возможные ответы в случае ошибок:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Запись бюджета с указанным id не найдена в пределах аккаунта
    405 – Отсутствуют параметры - id расхода/дохода
    406 – Расход не проведен - недостаточно средств на счете. Выберите другой расчетный счет


<a id="undoit"></a>
### Запрос “undoit”


Запрос позволяет отменить проведение платежа, указав его id.

**Параметры запроса**:

- **id** – уникальный идентификатор записи расхода/дохода (обязательное поле)

Пример запроса:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey=aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=undoit&id=522
</code></pre>

Ответ:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Проведение отменено","data":522}</code></pre>

В случае, если расход не был проведен, то в ответ получим:

<pre><code class="json">{"result":"Отмена - расход не проведен","data":522}</code></pre>

Возможные ответы в случае ошибок:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 – Запись бюджета с указанным id не найдена в пределах аккаунта
    405 – Отсутствуют параметры - id расхода/дохода


<a id="move"></a>
### Запрос “move”


Запрос позволяет осуществлять перемещение средств между счетами в CRM.

**Параметры запроса**:

- **title** – название перевода
- **des** – описание
- **bmon** – месяц
- **byear** – год
- **do** – отметка о проведении ("on" - признак проведения)
- **summa** – сумма перевода
- **rs** – р/сч, с которого перемещаем средства
- **rs_move** – р/сч, на который перемещаем средства


**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => "vladislav@isaler.ru",
	"apikey"      => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"      => "move",
	"title"       => "Перемещение_22",
	"bmon"       => "8",
	"byear"       => "2018",
	"summa"       => "15000",
	"rs"       => "4",
	"rs_move"       => "11"
]

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey= aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=move&title=Перемещение_22&bmon=8&byear=2018&summa=15000&rs=4&rs_move=11</code></pre>

**Ответ**:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Средства успешно перемещены","data":"20"}</code></pre>


**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    405 - Отсутствуют параметры
    406 - Средства не перемещены: Недостаточно средств на счете. Выберите другой расч. счет


<a id="unmove"></a>
### Запрос “unmove”


Запрос позволяет отменить перемещение средств между счетами.

**Параметры запроса**:

- **id** – id записи перемещения средств



**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => "vladislav@isaler.ru",
	"apikey"      => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"      => "unmove",
	"id"       => "20",

]

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey= aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=unmove&id=20</code></pre>

**Ответ**:

В поле “data” приходит id записи

<pre><code class="json">{"result":"Перемещение средств отменено","data":"20"}</code></pre>


**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    405 - Отсутствует параметр - id записи о перемещении средств
    406 - Отмена невозможна: Недостаточно средств на счете. Выберите другой расч. счет


<a id="addCategory"></a>
### Запрос “addCategory”


Запрос позволяет добавить категорию расхода/дохода в базу CRM. 

**Параметры запроса**:

- **subid** – id родительской категории
- **title** – название расхода/дохода (обязательное поле)
- **tip** – тип(расход/доход)


**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => "vladislav@isaler.ru",
	"apikey"      => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"      => "addCategory",
	"title"       => "Офисные расходы",
	"subid"         => "0",
	"tip"        => "rashod"
]

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey= aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=addCategory&title=Офисные+расходы&subid=0</code></pre>

**Ответ**:

В поле “data” приходит id записи 

<pre><code class="json">{"result":"Категория добавлена","data":256}</code></pre>


**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    405 - Отсутствует параметр - Название категории расхода/дохода


<a id="editCategory"></a>
### Запрос “editCategory”


Запрос позволяет добавить категорию расхода/дохода в базу CRM. 

**Параметры запроса**:

- **id** - id измененяемой категории
- **subid** – id родительской категории
- **title** – название расхода/дохода (обязательное поле)
- **tip** – тип(расход/доход)


**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => "vladislav@isaler.ru",
	"apikey"      => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"      => "editCategory",
	"title"       => "Реклама",
	"subid"         => "0",
	"tip"        => "dohod"
]

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey= aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=editCategory&id=14&title=Реклама&subid=0&tip=rashod</code></pre>

**Ответ**:

В поле “data” приходит id записи 

<pre><code class="json">{"result":"Категория изменена","data":"14"}</code></pre>


**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 - Категория расхода/дохода с указанным id не найдена в пределах аккаунта


<a id="deleteCategory"></a>
### Запрос “deleteCategory”


Запрос позволяет добавить категорию расхода/дохода в базу CRM. 

**Параметры запроса**:

- **id** – id родительской категории


**Пример формирования запроса в PHP**:

<pre><code class="php">
$params = [
	"login"       => "vladislav@isaler.ru",
	"apikey"      => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	"action"      => "deleteCategory",
	"id"       => "14"
]

$urlparams = http_build_query($params);

</code></pre>


**Пример запроса**:

<pre><code class="html">http(s)://crm_url/developer/v2/budget?login=vladislav@isaler.ru&apikey= aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=deleteCategory&id=14</code></pre>

**Ответ**:

В поле “data” приходит id записи 

<pre><code class="json">{"result":"Категория удалена","data":"14"}</code></pre>


**Возможные ответы в случае ошибок**:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод
    403 - Категория расхода/дохода с указанным id не найдена в пределах аккаунта
    405 - Отсутствуют параметры - id категории расхода/дохода
    408 - Удаление категории невозможно. Имеются подразделы