/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, a.vladislav.g@gmail.com
 * @charset  UTF-8
 * @version  2017.6
 */

/**
 * плагин срабатывает при отметке напоминания выполненным
 * при этом, если будет создано новое напоминание, то
 * у него будет установлен такой же тип напоминания
 */

$(document).ready(function () {

	ShowModal.subscribe(function(eventArgs) {
		ymhsdFunc.work(eventArgs);
	});

});

var ymhsdFunc = {

	work: function (data) {

		console.log(data);

		if(data.etype === 'ymailerForm' && data.action === 'account.on'){

			$('div.row[data-id="server"]').addClass('hidden');

		}

	}

};