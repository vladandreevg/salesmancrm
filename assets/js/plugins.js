/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, a.vladislav.g@gmail.com
 * @charset  UTF-8
 * @version  2017.3
 */

function UrlExists(url) {
	var http = new XMLHttpRequest();
	http.open('HEAD', url, false);
	http.send();
	return http.status != 404;
}

//подключаем исполняемые файлы плагинов
for (var p = 0; p < $pluginJS.length; p++) {

	includeJS($pluginJS[p]);

}

//скрываем разделы "Продажи, Сделки, Контакты"
var hideDeals = 'no';
var hideContacts = 'no';

if (hideDeals === 'yes') {

	setTimeout(function () {

		$('.counters').find('li[data-id="credit"]').remove();
		$('.counters').find('li[data-id="deals"]').remove();
		$('.counters').find('li[data-id="health"]').remove();

		$('#rmenu').find('li[data-id="credit"]').remove();
		$('#rmenu').find('li[data-id="deals"]').remove();
		$('#rmenu').find('li[data-id="health"]').remove();
		$('#rmenu').find('a[data-id="pipeline"]').remove();
		$('#rmenu').find('a[data-id="health"]').remove();

		$('#menuDeal').addClass('hidden');
		$('#dttabs').find('li:contains("Сделки")').addClass('hidden');
		$('#dttabs').find('li:contains("Pipeline")').addClass('hidden');
		$('#footpanel').find('#kolnot').closest('li').addClass('hidden');
		$('#footpanel').find('#kolcredit').closest('li').addClass('hidden');
		$('#footpanel').find('#counthealth').closest('li').addClass('hidden');
		$('#blockAdds td:nth-child(3)').addClass('hidden');

	}, 200);

}
if (hideContacts === 'yes') {

	setTimeout(function () {

		if ($('#card').val() == 'client') {
			$('li#tb4').css('display', 'none');
			$('li#tb2').css('display', 'none');
		}

		$('#dttabs').find('li:contains("Контакты")').addClass('hidden');
		$('#menuClients').find('li [data-type="contact"]').addClass('hidden');

		$('#rmenu').find('li[data-id="contacts"]').addClass('hidden').removeClass('razdel');

	}, 100);

}

//дополнительная обработка для функции загрузки форм doLoad в файле js/project.core.js
function doLoadCallback(event) {

	//скрываем блоки Контактов в экспресс-форме и форме обращения
	if ((jQuery('#contactBoxExpress').is('div') || jQuery('#contactBoxEntry').is('div')) && hideContacts == 'yes') {

		$('#contactBoxExpress').addClass('hidden');
		$('#contactBoxEntry').addClass('hidden');
		//$('#clientBoxExpress #des').css('height','30px');//регулирует высоту поля Описание в блоке Клиент в Экспресс-форме
		//$('#deshist').css('height','30px').attr('row','1');//регулирует высоту поля Описание в блоке Клиент в Экспресс-форме

		$('.adddeal').closest('tr').addClass('hidden');

	}
	if (hideDeals === 'yes') {

		$('.adddeal').closest('tr').addClass('hidden');
		$('div[data-id="deals"]').remove();

	}

	//addSMS();

	/**
	 * Вызываем свои функции после загрузки форм
	 */
	switch (event) {

		//форма добавления Обращения
		case 'editEntry':
			break;

		//форма смена статуса Обращения
		case 'statusEntry':
			break;

		//форма Экспресс-форма
		case 'expressClient':
			break;

		//форма Добавления Заявки (ручное)
		case 'editLead':
			break;

		//форма Обработки Заявки
		case 'workitLead':
			break;

		//форма Сделки
		case 'dealForm':
			break;

		//форма Клиента
		case 'clientForm':
			break;

		//форма Контакта
		case 'personForm':
			break;

	}

}
