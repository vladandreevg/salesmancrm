# Справочники (guides)

## Метод “guides”

Метод позволяет получить информацию по записям из справочников системы.

<pre><code class="html">URL для вызова - http(s)://crm_url/developer/v2/guides?параметр=значение</code></pre>

**Пример запроса:**

http(s)://crm_url/developer/v1/guides?login=vladislav@isaler.ru&apikey=aMgiCQyj8bCToNc47BZZYrRICoWSIl&action=users

Возможные ответы в случае ошибок:

    400 – Не верный API key
    401 – Неизвестный пользователь
    402 – Неизвестный метод

<a id="category"></a>

### Запрос “category”

Запрос позволяет получить информацию из справочника Отрасли.

<pre><code class="php">

$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'category'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ содержит данные:

- **id** - ID записи отрасли
- **title** – Название отрасли
- **tip** – Тип записи, к которой относится отрасль
	- client – Клиент юр.лицо
	- person – Клиент физ.лицо
	- partner – Партнер
	- contractor – Поставщик
	- concurent – Конкурент

<pre><code class="json">
{
	"data":[
		{"id":"6","title":"Промышленность","tip":"client"},
		{"id":"7","title":"Финансы. Банки","tip":"client"},
		{"id":"8","title":"Логистика","tip":"client"},
		{"id":"38","title":"Информационные технологии","tip":"client"},
		{"id":"14","title":"Строительство","tip":"client"},
		{"id":"15","title":"Торговля. Опт","tip":"client"},
		{"id":"16","title":"Телекоммуникации","tip":"client"},
		{"id":"25","title":"Торговля. Розница","tip":"client"}
	]
}
</code></pre>

<a id="territory"></a>

### Запрос “territory”

Запрос позволяет получить информацию из справочника Территории.

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'territory'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ содержит данные:

- **id** - ID записи
- **title** – Название территории

<pre><code class="json">
{
	"data":[
		{"id":"1","title":"Пермь"},
		{"id":"3","title":"Тюмень"},
		{"id":"4","title":"Челябинск"},
		{"id":"5","title":"Москва"}
	]
}
</code></pre>

<a id="relations"></a>

### Запрос “relations”

Запрос позволяет получить информацию из справочника Тип отношений (параметр tip_cmr).

**Пример запроса:**

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'relations'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ содержит данные:

- **id** - ID записи
- **title** – Название типа отношений
- **color** – Цветовая схема

<pre><code class="json">
{
	"data":[
		{"id":"1","title":"0 - Не работаем","color":"#333333"},
		{"id":"2","title":"1 - Холодный клиент","color":"#99ccff"},
		{"id":"3","title":"3 - Текущий клиент","color":"#3366ff"},
		{"id":"5","title":"4 - Постоянный клиент","color":"#ff6600"},
		{"id":"40","title":"2 - Потенциальный клиент","color":"#99ff66"}
	]
}
</code></pre>

<a id="clientpath"></a>

### Запрос “clientpath”

Запрос позволяет получить информацию из справочника Источник клиента (параметр clientpath).

**Пример запроса:**

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'clientpath'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ содержит данные:

- **id** – id записи в базе данных
- **title** – Название источника
- **utm_source** - UTM-метка для сопоставления с каналом
- **destination** - номер входящей линии для сопоставления с каналом

<pre><code class="json">
{
	"data":[
		{"id":"2","title":"Личные связи","utm_source":"","destination":""},
		{"id":"180","title":"isaler.ru","utm_source":"","destination":""},
		{"id":"5","title":"Справочник","utm_source":"","destination":""},
		{"id":"6","title":"Входящий контакт","utm_source":"","destination":""},
		{"id":"181","title":"Сайт","utm_source":"","destination":""},
		{"id":"13","title":"Заказ с сайта","utm_source":"","destination":""},
		{"id":"14","title":"Рекомендации клиентов","utm_source":"fromfriend","destination":""},
		{"id":"86","title":"Landing","utm_source":"facebook","destination":"74953730765"},
		{"id":"176","title":"Relap","utm_source":"relap","destination":""},
		{"id":"178","title":"Рассылки","utm_source":"mailerlite","destination":"79031706342"}
	]
}
</code></pre>

<a id="company.list"></a>

### Запрос “company.list”

Запрос позволяет получить информацию из справочника Мои компании (параметр mcid в сделках).

**Пример запроса:**

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'company.list'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ в массиве "data" содержит массив собственных компаний:

- **mcid** – ID записи компании
- **signers** - массив дополнительных подписантов

Расшифровку остальных значений можно посмотреть в
Документации: [Шаблоны для документов](https://salesman.pro/docs/10 "Шаблоны для документов")

<pre><code class="json">
{
	"data":[
		{
			"mcid":"2",
			"compUrName":"Общество с ограниченной ответственностью \u201dБрикет Солюшн\u201d",
			"compShotName":"ООО \u201dБрикет Солюшн\u201d",
			"compUrAddr":"614007, г. Пермь, ул. Народовольческая, 23-38",
			"compFacAddr":"614007, г. Пермь, ул. Народовольческая, 23-38",
			"compDirName":"Директора Андреева Владислава Германовича",
			"compDirSignature":"Андреев В.Г.",
			"compDirStatus":"Директор",
			"compDirOsnovanie":"Устава",
			"compInn":"590402247104",
			"compKpp":"590401001",
			"compOgrn":"312590427000020",
			"signers": [
				{
					"id": 1
					"mcid": 2
					"title": "Финансового директора Грушко С.В."
					"status": "Финансовый директор"
					"signature": "Грушко С.В."
					"osnovanie": "Доверенности №125 от 01.03.2021"
					"stamp": "signer_facsimile1635147053.png"
				},
				{
					"id": 2
					"mcid": 2
					"title": "Коммерческого директора Иванова И.И."
					"status": "Коммерческий директор"
					"signature": "Иванов И.И."
					"osnovanie": "Доверенности №127 от 01.05.2021"
					"stamp": "signer_facsimile1635145546.png"
				}
			]
		},
		{
			"mcid":"3",
			"compUrName":"ООО \u201dРога и Копыта\u201d",
			"compShotName":"ООО \u201dРога и Копыта\u201d",
			"compUrAddr":"г. Москва, л. Лавочкина 23, 2 этаж",
			"compFacAddr":"г. Москва, л. Лавочкина 23, 2 этаж",
			"compDirName":"Директора Андреева Владислава Германовича",
			"compDirSignature":"Андреев В.Г.",
			"compDirStatus":"Директор",
			"compDirOsnovanie":"Устава",
			"compInn":"590402247100",
			"compKpp":"0",
			"compOgrn":"312590427000020"
		}
	]
}
</code></pre>

<a id="company.listfull"></a>

### Запрос “company.listfull”

Запрос позволяет получить информацию из справочника Мои компании (параметр mcid в сделках) + привязанных к ним расчетных
счетов и касс.

**Пример запроса:**

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'company.listfull'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ в массиве "data" содержит массив собственных компаний и привязанных к ним р/сч.:

- **mcid** – ID записи компании
- **bank** – Массив расчетных счетов, где
	- **tip** - тип р/сч. (bank - банковский счет, kassa - касса)
	- **isDefault** - р/сч по умолчанию
	- **ndsDefault** - ставка НДС, в %
	- **compNameRs** - название р/сч
	- **signers** - массив дополнительных подписантов

Расшифровку остальных значений можно посмотреть в
Документации: [Шаблоны для документов](https://salesman.pro/docs/10 "Шаблоны для документов")

<pre><code class="json">
{
	"data":[
		{
			"mcid":"2",
			"compUrName":"Общество с ограниченной ответственностью \u201dБрикет Солюшн\u201d",
			"compShotName":"ООО \u201dБрикет Солюшн\u201d",
			"compUrAddr":"614007, г. Пермь, ул. Народовольческая, 23-38",
			"compFacAddr":"614007, г. Пермь, ул. Народовольческая, 23-38",
			"compDirName":"Директора Андреева Владислава Германовича",
			"compDirSignature":"Андреев В.Г.",
			"compDirStatus":"Директор",
			"compDirOsnovanie":"Устава",
			"compInn":"590402247104",
			"compKpp":"590401001",
			"compOgrn":"312590427000020",
			"bank":[
				{
					"id":"1",
					"tip":"bank",
					"isDefault":"yes",
					"ndsDefault":"18",
					"compNameRs":"Основной расчетный счет",
					"compBankBik":"045744863",
					"compBankRs":"1234567890000000000000000",
					"compBankKs":"30101810300000000863",
					"compBankName":"Филиал ОАО \u00abУРАЛСИБ\u00bb в г. Пермь"
				},
				{
					"id":"2",
					"tip":"kassa",
					"isDefault":"no",
					"ndsDefault":"0",
					"compNameRs":"Касса (Андреев)",
					"compBankBik":"",
					"compBankRs":"0",
					"compBankKs":"",
					"compBankName":""
				},
			],
			"signers": [
				{
					"id": 1
					"mcid": 2
					"title": "Финансового директора Грушко С.В."
					"status": "Финансовый директор"
					"signature": "Грушко С.В."
					"osnovanie": "Доверенности №125 от 01.03.2021"
					"stamp": "signer_facsimile1635147053.png"
				},
				{
					"id": 2
					"mcid": 2
					"title": "Коммерческого директора Иванова И.И."
					"status": "Коммерческий директор"
					"signature": "Иванов И.И."
					"osnovanie": "Доверенности №127 от 01.05.2021"
					"stamp": "signer_facsimile1635145546.png"
				}
			]
		},
		{
			"mcid":"3",
			"compUrName":"ООО \u201dРога и Копыта\u201d",
			"compShotName":"ООО \u201dРога и Копыта\u201d",
			"compUrAddr":"г. Москва, л. Лавочкина 23, 2 этаж",
			"compFacAddr":"г. Москва, л. Лавочкина 23, 2 этаж",
			"compDirName":"Директора Андреева Владислава Германовича",
			"compDirSignature":"Андреев В.Г.",
			"compDirStatus":"Директор",
			"compDirOsnovanie":"Устава",
			"compInn":"590402247100",
			"compKpp":"0",
			"compOgrn":"312590427000020"
		}
	]
}
</code></pre>

<a id="company.bank"></a>

### Запрос “company.bank”

Запрос позволяет получить информацию из справочника Мои компании/Банковские счета (параметр rs в выставлении счетов).

**Пример запроса:**

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'company.listfull'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ содержит данные:

- **id** – ID расчетного счета (используется при выставлении счетов)
- **mcid** – ID компании
- **tip** - тип р/сч. (bank - банковский счет, kassa - касса)
- **isDefault** - р/сч по умолчанию
- **ndsDefault** - ставка НДС, в %
- **compNameRs** - название р/сч

<pre><code class="json">
{
	"data":[
		{"id":"1","mcid":"2","tip":"bank","isDefault":"yes","ndsDefault":"18","compNameRs":"Основной расчетный счет","compBankBik":"045744863","compBankRs":"1234567890000000000000000","compBankKs":"30101810300000000863","compBankName":"Филиал ОАО \u00abУРАЛСИБ\u00bb в г. Пермь"},
		{"id":"2","mcid":"2","tip":"kassa","isDefault":"no","ndsDefault":"0","compNameRs":"Касса (Андреев)","compBankBik":"","compBankRs":"0","compBankKs":"","compBankName":""},
	]
}
</code></pre>

### Запрос “company.signers”

Запрос позволяет получить список дополнительных подписантов из справочника Мои компании.

**Пример запроса:**

<pre><code class="php">
$params = [
	"login"  => "vladislav@isaler.ru",
	"apikey" => "aMgiCQyj8bCToNc47BZZYrRICoWSIl",
	// указываем метод
	"action" => 'company.signers'
];

$urlparams = http_build_query($params);
</code></pre>

**Ответ:**

Полученный ответ содержит массив данных, где ключ = mcid:

- **id** – ID записи
- **mcid** – ID компании
- **title** - подпись (в плане "в лице ...")
- **status** - должность
- **signature** - подпись (расшифровка подписи)
- **osnovanie** - основание (действует на основании)
- **stamp** - файл факсимиле

<pre><code class="json">
{
	"data":[
		{
			"3":[
				{"id":"1","mcid":"3","title":"Финансового директора Грушко С.В.","status":"Финансовый директор","signature":"Грушко С.В.","osnovanie":"Доверенности \u2116125 от 01.03.2021","stamp":"signer_facsimile1635147053.png"},
				{"id":"2","mcid":"3","title":"Коммерческого директора Суворова И.И.","status":"Коммерческий директор","signature":"Суворов И.И.","osnovanie":"Доверенности \u2116325 от 01.01.2021","stamp":"signer_facsimile1635151035.png"},
				{"id":"3","mcid":"3","title":"Главного бухгалтера Стулий Д.В.","status":"Главный бухгалтер","signature":"Стулий Д.В.","osnovanie":"Доверенности \u2116326 от 01.06.2021","stamp":"signer_facsimile1635152211.png"}
			],
			"2":[
				{"id":"4","mcid":"2","title":"Директор Андреева Л.В.","status":"Директор","signature":"Андреева Л.В.","osnovanie":"Доверенности \u211615 от 01.01.2012","stamp":"signer_facsimile1635225607.png"}
			]
		}
	]
}
</code></pre>