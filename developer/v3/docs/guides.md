# Справочники (guides)

## Метод “guides”

Метод позволяет получить информацию по записям из справочников системы.

URL для вызова:
```http
http(s)://{{baseurl}}/developer/v3/guides/запрос?параметр=значение
```


<a id="category"></a>
### Запрос “category”

Запрос позволяет получить информацию из справочника Отрасли.

```http request
GET http://{{baseurl}}/developer/v3/guides/category
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

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

```json
{
	"data": [
		{
			"id": 6,
			"title": "Промышленность",
			"tip": "client"
		},
		{
			"id": 59,
			"title": "Поставщик. Оборудование",
			"tip": "contractor"
		},
		{
			"id": 484,
			"title": "Партнеры. Консалтинг",
			"tip": "partner"
		},
		{
			"id": 485,
			"title": "Партнеры. Интеграторы",
			"tip": "partner"
		}
	]
}
```

<a id="territory"></a>
### Запрос “territory”

Запрос позволяет получить информацию из справочника Территории.

```http request
GET http://{{baseurl}}/developer/v3/guides/territory
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

Полученный ответ содержит данные:

- **id** - ID записи
- **title** – Название территории

```json
{
	"data": [
		{
			"id": 1,
			"title": "Пермь"
		},
		{
			"id": 3,
			"title": "Тюмень"
		},
		{
			"id": 4,
			"title": "Челябинск"
		},
		{
			"id": 5,
			"title": "Москва"
		}
	]
}
```

<a id="relations"></a>
### Запрос “relations”

Запрос позволяет получить информацию из справочника Тип отношений (параметр tip_cmr).

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/guides/relations
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

Полученный ответ содержит данные:

- **id** - ID записи
- **title** – Название типа отношений
- **color** – Цветовая схема

```json
{
	"data": [
		{
			"id": 1,
			"title": "0 - Не работаем",
			"color": "#333333"
		},
		{
			"id": 2,
			"title": "1 - Холодный клиент",
			"color": "#99ccff"
		},
		{
			"id": 3,
			"title": "3 - Текущий клиент",
			"color": "#3366ff"
		},
		{
			"id": 5,
			"title": "4 - Постоянный клиент",
			"color": "#ff9900"
		},
		{
			"id": 40,
			"title": "2 - Потенциальный клиент",
			"color": "#99ff66"
		},
		{
			"id": 75,
			"title": "5 - Перспективный клиент",
			"color": "#ff0033"
		}
	]
}
```

<a id="clientpath"></a>
### Запрос “clientpath”

Запрос позволяет получить информацию из справочника Источник клиента (параметр clientpath).

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/guides/clientpath
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

Полученный ответ содержит данные:

- **id** – id записи в базе данных
- **title** – Название источника
- **utm_source** - UTM-метка для сопоставления с каналом
- **destination** - номер входящей линии для сопоставления с каналом (при интеграции с ВАТС)

```json
{
	"data": [
		{
			"id": 2,
			"title": "Личные связи",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 4,
			"title": "Маркетинг",
			"utm_source": "",
			"destination": "79031706342"
		},
		{
			"id": 5,
			"title": "Справочник",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 13,
			"title": "Заказ с сайта",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 14,
			"title": "Рекомендации клиентов",
			"utm_source": "fromfriend",
			"destination": ""
		},
		{
			"id": 17,
			"title": "isaler.com",
			"utm_source": "isaler",
			"destination": "74957966610"
		},
		{
			"id": 20,
			"title": "Вконтакте",
			"utm_source": "vk",
			"destination": ""
		},
		{
			"id": 86,
			"title": "Landing Page",
			"utm_source": "facebook",
			"destination": "74953730765"
		},
		{
			"id": 159,
			"title": "100crm",
			"utm_source": "100crm",
			"destination": ""
		},
		{
			"id": 160,
			"title": "Рассылки",
			"utm_source": "mailerlite",
			"destination": "74953730763"
		},
		{
			"id": 176,
			"title": " Relap",
			"utm_source": "relap",
			"destination": ""
		},
		{
			"id": 177,
			"title": "Voxlink",
			"utm_source": "voxlink",
			"destination": ""
		},
		{
			"id": 181,
			"title": "Landing Page 4",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 182,
			"title": "Сайт YoollaAPI100.ru",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 183,
			"title": "Сайт YoollaAPI200.ru",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 184,
			"title": "Сайт YoollaAPI300.ru",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 185,
			"title": "Сайт isaler.com",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 188,
			"title": "Сайт",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 189,
			"title": "Landing Page 4",
			"utm_source": "vkontakte",
			"destination": ""
		},
		{
			"id": 190,
			"title": "Сайт №1",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 191,
			"title": "Сайт №2",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 192,
			"title": "Новый %5",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 193,
			"title": "",
			"utm_source": "",
			"destination": ""
		},
		{
			"id": 194,
			"title": "Выставка",
			"utm_source": null,
			"destination": null
		}
	]
}
```

<a id="company.list"></a>
### Запрос “company.list”

Запрос позволяет получить информацию из справочника Мои компании (параметр mcid в сделках).

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/guides/company.list
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

Полученный ответ в массиве "data" содержит массив собственных компаний:

- **mcid** – ID записи компании
- **signers** - массив дополнительных подписантов

Расшифровку остальных значений можно посмотреть в
Документации: [Шаблоны для документов](https://salesman.pro/docs/10 "Шаблоны для документов")

```json
{
	"data": [
		{
			"mcid": 2,
			"compUrName": "ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ ”БИГСЕЙЛЗРУС”",
			"compShotName": "ООО ”БИГСЕЙЛЗРУС”",
			"compUrAddr": "614007, г Пермь, ул Народовольческая, д 23, кв 38",
			"compFacAddr": "614007, г Пермь, ул Народовольческая, д 23, кв 38",
			"compDirName": "Генеральный директор Андреев Владислав Германович",
			"compDirSignature": "Андреев В. Г.",
			"compDirStatus": "Генеральный директор",
			"compDirOsnovanie": "Устава",
			"compInn": "5904338272",
			"compKpp": "590401001",
			"compOgrn": "1165958091530",
			"signers": [
				{
					"id": 1,
					"mcid": 2,
					"title": "Коммерческий директор Грушев С.В.",
					"status": "Коммерческий директор",
					"signature": "Грушев С.В.",
					"osnovanie": "Доверенности №125 от 01.02.2021",
					"stamp": "signer_facsimile1635185237.png"
				},
				{
					"id": 2,
					"mcid": 2,
					"title": "Финансовый директор Стульчиков Д.В.",
					"status": "Финансовый директор",
					"signature": "Стульчиков Д.В.",
					"osnovanie": "Доверенности №214 от 01.05.2021",
					"stamp": "signer_facsimile1635185320.png"
				}
			]
		}
	]
}
```

<a id="company.listfull"></a>
### Запрос “company.listfull”

Запрос позволяет получить информацию из справочника Мои компании (параметр mcid в сделках) + привязанных к ним расчетных
счетов и касс.

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/guides/company.listfull
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

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

```json
{
	"data": [
		{
			"mcid": 2,
			"compUrName": "ОБЩЕСТВО С ОГРАНИЧЕННОЙ ОТВЕТСТВЕННОСТЬЮ ”БИГСЕЙЛЗРУС”",
			"compShotName": "ООО ”БИГСЕЙЛЗРУС”",
			"compUrAddr": "614007, г Пермь, ул Народовольческая, д 23, кв 38",
			"compFacAddr": "614007, г Пермь, ул Народовольческая, д 23, кв 38",
			"compDirName": "Генеральный директор Андреев Владислав Германович",
			"compDirSignature": "Андреев В. Г.",
			"compDirStatus": "Генеральный директор",
			"compDirOsnovanie": "Устава",
			"compInn": "5904338272",
			"compKpp": "590401001",
			"compOgrn": "1165958091530",
			"bank": [
				{
					"id": 1,
					"tip": "bank",
					"isDefault": "yes",
					"ndsDefault": "20",
					"compNameRs": "Основной расчетный счет",
					"compBankBik": "045744863",
					"compBankRs": "1234567890000000000000000",
					"compBankKs": "30101810300000000863",
					"compBankName": "Филиал ОАО «УРАЛСИБ» в г. Пермь"
				},
				{
					"id": 2,
					"tip": "kassa",
					"isDefault": "",
					"ndsDefault": "0",
					"compNameRs": "Касса (Андреев)",
					"compBankBik": "",
					"compBankRs": "0",
					"compBankKs": "",
					"compBankName": ""
				},
				{
					"id": 4,
					"tip": "bank",
					"isDefault": "no",
					"ndsDefault": "0",
					"compNameRs": "Киви кошелек",
					"compBankBik": "045744863",
					"compBankRs": "78978900089999000",
					"compBankKs": "30101810300000000863",
					"compBankName": "ФИЛИАЛ ОАО ”УРАЛСИБ” В Г.ПЕРМЬ, г. ПЕРМЬ"
				},
				{
					"id": 12,
					"tip": "kassa",
					"isDefault": "",
					"ndsDefault": "18",
					"compNameRs": "Яндекс.Деньги",
					"compBankBik": "",
					"compBankRs": "",
					"compBankKs": "",
					"compBankName": ""
				},
				{
					"id": 20,
					"tip": "bank",
					"isDefault": "",
					"ndsDefault": "20",
					"compNameRs": "Модульбанк",
					"compBankBik": "045004864",
					"compBankRs": "40702810621810000563",
					"compBankKs": "30101810350040000864",
					"compBankName": "”Хайс” Филиал АО КБ ”Модульбанк”, Новосибирск"
				}
			],
			"signers": [
				{
					"id": 1,
					"mcid": 2,
					"title": "Коммерческий директор Грушев С.В.",
					"status": "Коммерческий директор",
					"signature": "Грушев С.В.",
					"osnovanie": "Доверенности №125 от 01.02.2021",
					"stamp": "signer_facsimile1635185237.png"
				},
				{
					"id": 2,
					"mcid": 2,
					"title": "Финансовый директор Стульчиков Д.В.",
					"status": "Финансовый директор",
					"signature": "Стульчиков Д.В.",
					"osnovanie": "Доверенности №214 от 01.05.2021",
					"stamp": "signer_facsimile1635185320.png"
				}
			]
		}
	]
}
```

<a id="company.bank"></a>
### Запрос “company.bank”

Запрос позволяет получить информацию из справочника Мои компании/Банковские счета (параметр rs в выставлении счетов).

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/guides/company.bank
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

Полученный ответ содержит данные:

- **id** – ID расчетного счета (используется при выставлении счетов)
- **mcid** – ID компании
- **tip** - тип р/сч. (bank - банковский счет, kassa - касса)
- **isDefault** - р/сч по умолчанию
- **ndsDefault** - ставка НДС, в %
- **compNameRs** - название р/сч

```json
{
	"data": [
		{
			"id": 1,
			"mcid": 2,
			"tip": "bank",
			"isDefault": "yes",
			"ndsDefault": "20",
			"compNameRs": "Основной расчетный счет",
			"compBankBik": "045744863",
			"compBankRs": "1234567890000000000000000",
			"compBankKs": "30101810300000000863",
			"compBankName": "Филиал ОАО «УРАЛСИБ» в г. Пермь"
		},
		{
			"id": 12,
			"mcid": 2,
			"tip": "kassa",
			"isDefault": "",
			"ndsDefault": "18",
			"compNameRs": "Яндекс.Деньги",
			"compBankBik": "",
			"compBankRs": "",
			"compBankKs": "",
			"compBankName": ""
		}
	]
}
```

<a id="company.signers"></a>
### Запрос “company.signers”

Запрос позволяет получить список дополнительных подписантов из справочника Мои компании.

**Пример запроса:**

```http request
GET http://{{baseurl}}/developer/v3/guides/company.signers
Content-Type: application/json
apikey: {{token}}
login: vladislav@isaler.ru
```

**Ответ:**

Полученный ответ содержит массив данных, где ключ = mcid:

- **id** – ID записи
- **mcid** – ID компании
- **title** - подпись (в плане "в лице ...")
- **status** - должность
- **signature** - подпись (расшифровка подписи)
- **osnovanie** - основание (действует на основании)
- **stamp** - файл факсимиле

```json
{
	"data": [
		{
			"2": [
				{
					"id": 1,
					"mcid": 2,
					"title": "Коммерческий директор Грушев С.В.",
					"status": "Коммерческий директор",
					"signature": "Грушев С.В.",
					"osnovanie": "Доверенности №125 от 01.02.2021",
					"stamp": "signer_facsimile1635185237.png"
				},
				{
					"id": 2,
					"mcid": 2,
					"title": "Финансовый директор Стульчиков Д.В.",
					"status": "Финансовый директор",
					"signature": "Стульчиков Д.В.",
					"osnovanie": "Доверенности №214 от 01.05.2021",
					"stamp": "signer_facsimile1635185320.png"
				}
			],
			"17": [
				{
					"id": 3,
					"mcid": 17,
					"title": "Технический директор Стародумов И.Г.",
					"status": "Технический директор",
					"signature": "Стародумов И.Г.",
					"osnovanie": "Доверенности №214 от 01.01.2021",
					"stamp": "signer_facsimile1635186067.png"
				}
			]
		}
	]
}
```