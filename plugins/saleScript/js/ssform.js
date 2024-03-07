/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, a.vladislav.g@gmail.com
 * @charset  UTF-8
 * @version  2017.3
 */

//console.log($pluginEnambled);

/**
 * базовый адрес для файлов плагина
 * @type {string}
 */
var ssbaseURL = '/plugins/saleScript/';

/**
 * Целевой элемент интерфейса - модальное окно
 */
var ssDialog = $('#dialog');
var ssElement, ssElementSub;

/**
 * По умолчанию отключим плагин
 * @type {boolean}
 */
var ssEnabled = false;

/**
 * Массив событий, при которых будем показывать наше окно
 * @type {[*]}
 */
var ssEvents = ["editEntry","expressClient","workitLead"];

var ssCurrentEvent = '';

/**
 * Переменные, относящиеся к HyperScript
 */
var $sshsUserID, $sshsAPIkey, $sshsScrypt;

/**
 * Подключаем скрипт
 */
includeJS(ssbaseURL + 'js/hscript.js');

/**
 * Наша объект с функциями-обработчиками
 * @type {{ssLoadScripts: ssFunc.ssLoadScripts, ssShowForm: ssFunc.ssShowForm, ssLoadScript: ssFunc.ssLoadScript, ssMoveDialog: ssFunc.ssMoveDialog, ssHideForm: ssFunc.ssHideForm, ssAppendForm: ssFunc.ssAppendForm, ssLoadCSS: ssFunc.ssLoadCSS}}
 */
var ssFunc = {

	ssLoadSettings: function(){

		$.get(ssbaseURL + "data/settings.json", function (data) {

			if(data.forms !== undefined)
				ssEvents = data.forms;

			//console.log(ssEvents);

		}, 'json')

	},

	/**
	 * Загружаем скрипты
	 */
	ssLoadScripts: function(){

		if(in_array('saleScript', $pluginEnambled) && !isMobile){

			$.getJSON(ssbaseURL + 'ssform.php', function (data) {

				//console.log(data);

				$sshsScrypt = data;
				$sshsUserID = data.UserID;
				$sshsAPIkey = data.APIkey;

				if (/*Object.keys($sshsScrypt).length > 0 &&*/ data.foruser) ssEnabled = true;

				ssFunc.ssAppendForm();

			});

		}


	},

	/**
	 * Показываем наше окно со скриптами
	 * @param event
	 */
	ssShowForm: function(event) {

		console.log(event);
		console.log(ssEvents);

		if(ssEnabled && in_array(event, ssEvents)) {

			ssDialog.css({"width": "50vw", "left": "5vw"});
			ssElement.removeClass("hidden");

		}

	},

	/**
	 * Загружаем выбранный скрипт
	 */
	ssLoadScript: function(){

		var idScript = $('#ss-script option:selected').val();
		var hyperscript = new window.hyperscript();
		var p = {'user':$sshsUserID,key:$sshsAPIkey,id:idScript,skipStart:true,withoutSaving:false};

		hyperscript.sendMessage('showScript', p, function(data){

			//console.log(p);
			//console.log(data);

		});

	},

	/**
	 * Смещаем целевое модальное окно
	 */
	ssMoveDialog: function(){

		if(!ssElement.hasClass('hidden') && ssEnabled === true) ssDialog.css({"width":"50vw","left":"5vw"});

	},

	/**
	 * Скрываем наше окно скриптов
	 */
	ssHideForm: function(){

		ssElement.addClass('hidden');

	},

	/**
	 * Формируем окно по шаблону
	 */
	ssAppendForm: function(){

		if(ssEnabled === true) {

			$.Mustache.load(ssbaseURL + 'tpl/template.mustache')
				.done(function () {

					$('body').mustache('sswinTpl', $sshsScrypt);

					ssElement = $('.ss--window');
					ssElementSub = $('.ss--body');

					ssElementSub.empty().append('<div class="hidden1" id="frm" style="width:100%; height:98%;"><iframe id="hyperscript" name="hsiframe" src="https://hyper-script.ru/integration/test_api_key" frameborder="0" width="100%" height="100%"></iframe></div>');

					$('#ss-script').bind('change', ssFunc.ssLoadScript());

				});

		}

	},

	/**
	 * Загружаем стили
	 */
	ssLoadCSS: function(){

		var mass = [ssbaseURL + 'css/ssform.css'];
		for(var i=0;i<mass.length;i++){

			var a=document.createElement("link");
			a.rel="stylesheet";
			a.href=mass[i];
			document.getElementsByTagName("head")[0].appendChild(a)

		}

	},

	/**
	 * Стартовая функция, для запуска всего плагина
	 * @param event
	 */
	ssStart: function(event){

		//console.log(event);
		//console.log(ssEvents);

		/**
		 * Запускаем если вызывается допустимая форма, подключен плагин
		 */
		if(in_array(event, ssEvents) && in_array('saleScript', $pluginEnambled) && ssEnabled) {

			setTimeout(function() {
				ssFunc.ssShowForm(event);
			}, 10);

		}
		else return false;

	}

};


$(document).ready(function() {

	ssFunc.ssLoadSettings();
	ssFunc.ssLoadCSS();
	ssFunc.ssLoadScripts();

	ShowModal.subscribe(function(eventArgs) {

		ssFunc.ssStart(eventArgs.etype);
		ssCurrentEvent = eventArgs.etype;

		//console.log(eventArgs);
		//console.log($pluginEnambled);

	});

});

/**
 * При изменении позиционирования
 */
ssDialog.onPositionChanged(function(){

	if(ssEnabled === true && in_array(ssCurrentEvent, ssEvents)) {
		ssFunc.ssMoveDialog();
	}

}, 50);

/**
 * При скрытии целевого окна
 */
ssDialog.onVisibleChanged(function(){

	if(ssEnabled === true) ssFunc.ssHideForm();

}, 50);