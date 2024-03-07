/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2020 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2020.x           */
/* ============================ */

/**
 * базовый адрес для файлов плагина
 * @type {string}
 */
const cpbaseURL = "/plugins/ControlPoints";

$.Mustache.load(cpbaseURL + '/assets/tpl/tpl.mustache');

$(document).ready(function () {

	cpFunc.setIndicator().then(r => cpFunc.count());

	// обновление счетчика
	Visibility.every(300000, cpFunc.count);

	$('li.pop[data-id="controlpoints"]').on('click', function () {

		cpFunc.list();

	});

});

var cpFunc = {
	/**
	 * Установка индикатора
	 */
	setIndicator: async function () {

		var html = $.Mustache.render('popControlPointTpl', {});

		$('li[data-id="search"]').after(html);

	},
	/**
	 * Загружаем панель чатов во фрейме
	 */
	show: function(){

		$('#chatframe').attr('src', cpbaseURL+'/chats.php');
		$('.chatframe--container').css({"left": "0"});

	},
	/**
	 * Получаем количество не прочитанных чатов
	 * @returns {Promise<void>}
	 */
	count: async function(){

		await fetch(cpbaseURL + "/points.php?action=count")
			.then(response => response.text())
			.then(viewData => {

				$('.point--bullet').html(viewData);

				if( parseInt(viewData) === 0 )
					$('li[data-id="controlpoints"]').addClass('hidden');
				else
					$('li[data-id="controlpoints"]').removeClass('hidden');

			});

	},
	list: async function(){

		await fetch(cpbaseURL + "/points.php?action=list")
			.then(response => response.json())
			.then(viewData => {

				$('div[data-id="cplist"]').empty().mustache('listControlPointTpl', viewData);

			});

	}
};