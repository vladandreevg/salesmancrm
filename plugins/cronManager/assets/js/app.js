/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */

const isMobilee = {
	Android: function () {
		return navigator.userAgent.match(/Android/i);
	},
	BlackBerry: function () {
		return navigator.userAgent.match(/BlackBerry/i);
	},
	iOS: function () {
		return navigator.userAgent.match(/iPhone|iPad|iPod/i);
	},
	Opera: function () {
		return navigator.userAgent.match(/Opera Mini/i);
	},
	Windows: function () {
		return navigator.userAgent.match(/IEMobile/i);
	},
	any: function () {
		return (isMobilee.Android() || isMobilee.BlackBerry() || isMobilee.iOS() || isMobilee.Opera() || isMobilee.Windows());
	}
};
const isMace = {

	iOS: function () {
		return navigator.userAgent.match(/Macintosh/i);
	}

};
const isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
const isSafari = /Safari/.test(navigator.userAgent) && /Apple Computer/.test(navigator.vendor);

let isMobile = false;
let isPad = false;
let isMac = false;

if (isMobilee.any() || $(window).width() < 767) {

	isMobile = true;
	isPad = false;

}
if ($(window).width() > 767) {
	isMobile = false;
	isPad = true;
}
if ($(window).width() > 1024) isPad = false;

if (isMace.iOS()) isMac = true;

var isCtrl = false;
var javascripts = [];

$(document).ready( function() {

	$("#dialog").draggable({handle: ".zagolovok", cursor: "move", opacity: "0.85", containment: "document"});

	$.Mustache.load('assets/tpl/main.mustache');

	$app.loadTasks();

});

$(window).bind('resizeEnd', function () {

	if (isMobilee.any() || $(window).width() < 767) {
		isMobile = true;
		isPad = false;
	}
	if ($(window).width() > 767) {
		isMobile = false;
		isPad = true;
	}
	if ($(window).width() > 1024) isPad = false;

	$('#dialog').center();

});
$(window).resize(function () {

	if (this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function () {
		$(this).trigger('resizeEnd');
	}, 500);

	if ($('#dialog').is(':visible')) {

		$('#dialog').center();
		$('.dialog-preloader').center();
		$('#dialog_container').css('height', $(window).height());

	}

});

$(document).keydown(function(e){
	if(e == null) { // ie
		keycode = event.keyCode;
	} else { // mozilla
		keycode = e.which;
	}
	if(keycode == 27){ // escape, close box, esc
		DClose();
		$('.popmenu.nothide').removeClass('open');
	}
});

$(document).on('click','.close', function(){
	DClose();
});
$(document).on('click','#helper', function(){

	$('#help').css({"display":"block"});

});
$(document).on('click','#helpcloser', function(){

	$('#help').css({"display":"none"});

});


var $app = {
	editTask : function(id){

		doLoad("php/modal.php?action=task.edit.form&id="+id);

	},
	removeTask: function(id){

		Swal.fire({
				title: 'Вы уверены?',
				text: "Будут удалены все данные!",
				type: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				confirmButtonText: 'Да, выполнить',
				cancelButtonText: 'Отменить',
				confirmButtonClass: 'greenbtn',
				cancelButtonClass: 'redbtn'
			}
		).then((result) => {

			if (result.value) {

				$.get("php/modal.php?action=task.delete&id="+id, function(data){

					Swal.fire({
						imageUrl: '/assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						//position: 'bottom-end',
						html: '' + data + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

					$app.loadTasks();

				});

			}

		});

	},
	loadTasks: function (){

		$('div[data-id="tasks"]').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

		$.get('php/modal.php?action=task.get', function (data) {

			$('div[data-id="tasks"]').empty().mustache('tasksTpl', data);

		}, 'json')
			.complete(function () {

			});

	},
	logTask: function(id){

		doLoad("php/modal.php?action=log.info&id="+id);

	}
};

function saveSettings() {

	var str = $('#settingsForm').serialize();
	var url = $('#settingsForm').attr("action");

	$.post(url, str, function (data) {

		if (data.status === 'ok') {

			Swal.fire({
				imageUrl: '/assets/images/success.svg',
				imageWidth: 50,
				imageHeight: 50,
				//position: 'bottom-end',
				html: '' + data.message + '',
				icon: 'info',
				showConfirmButton: false,
				timer: 3500
			});

		}
		else {

			Swal.fire({
				imageUrl: '/assets/images/error.svg',
				imageWidth: 50,
				imageHeight: 50,
				html: '' + data.message + '',
				icon: 'info',
				showConfirmButton: false,
				timer: 3500
			});

		}

	}, 'json');

}

function doLoad(url){

	$('#dialog_container').css('height', $(window).height()).css('display', 'block');
	$('#dialog').css('width','500px').css('height','unset').css('display', 'none');
	$('.dialog-preloader').center().css('display', 'block');

	$.get(url, function(data){

		$('#resultdiv').empty().html(data);
		$('#dialog').center();
		$("a.button:contains('Отмена')").addClass('bcancel');
		$("a.button:contains('Закрыть')").addClass('bcancel');

	})
		.complete(function() {

			$('#dialog').css('display', 'block');
			$('.dialog-preloader').css('display', 'none');

		});

	return false;

}

/**
 * Strip HTML and PHP tags from a string
 * @param str
 * @returns {*}
 */
function striptags(str) {
	// Strip HTML and PHP tags from a string
	//
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	return str.replace(/<\/?[^>]+>/gi, '');
}

/**
 * Checks if a value exists in an array
 * @param needle
 * @param haystack
 * @param strict
 * @returns {boolean}
 */
function in_array(needle, haystack, strict) {
	// Checks if a value exists in an array
	//
	// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)

	var found = false, key, strict = !!strict;

	for (key in haystack) {
		if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
			found = true;
			break;
		}
	}

	return found;
}

function yNotifyMe(data) {

	data = data.split(",");
	var title = data[0];
	var content = data[1];
	var img = data[2];
	var id = data[3];
	var url = data[4];

	if(Notification.permission === 'granted') {

		if (("Notification" in window)) {

			if (Notification.permission === "granted") {
				var notification = new Notification(title, {
					lang: 'ru-RU',
					body: content,
					icon: '/images/' + img,
					tag: id
				});
			}
			// В противном случае, мы должны спросить у пользователя разрешение
			else if (Notification.permission === 'default') {
				Notification.requestPermission(function (permission) {

					// Не зависимо от ответа, сохраняем его в настройках
					if (!('permission' in Notification)) {
						Notification.permission = permission;
					}
					// Если разрешение получено, то создадим уведомление
					if (permission === "granted") {
						var notification = new Notification(title, {
							lang: 'ru-RU',
							body: content,
							icon: '/images/' + img,
							tag: id
						});
					}

				});
			}

			else return true;

			notification.onshow = function () {

				var wpmupsnd = new Audio("/assets/images/mp3/bigbox.mp3");
				wpmupsnd.volume = 0.2;
				wpmupsnd.play();

			};
			notification.onclick = function () {};

		}
		else
			return true;

	}
	else{

		Swal.fire({
			icon: 'info',
			imageUrl: '/assets/images/' + img,
			position: 'bottom-end',
			background: "var(--blue)",
			title: '<div class="white fs-11">' + title + '</div>',
			html: '<div class="white">' + content + '</div>',
			showConfirmButton: false,
			timer: 1500
		});

	}

}

jQuery.fn.center = function(){
	var w = $(window);

	this.css("position","absolute");
	this.css("top",(w.height()-this.height())/2 + "px");
	this.css("left",(w.width()-this.width())/2+w.scrollLeft() + "px");

	return this;
};

/**
 * Автоматическое увеичение размера текстового поля
 * @param maxHeight - максимальая высота поля
 * @param rows - количество строк при инициализации
 * @returns {$}
 */
$.fn.autoHeight = function (maxHeight, rows) {

	if (rows === 'undefined')
		rows = 1;

	this.trigger('input');

	this.each(function () {

		$(this).attr('rows', rows);
		resize($(this));

		$('#dialog').center();

	});

	this.off('input');
	this.on('input', function () {

		resize($(this));

		$('#dialog').center();

	});

	function resize($text) {

		$text.css({'min-height': '50px', 'height': $text[0].scrollHeight + 'px', 'overflow-y': 'hidden'});
		//if($text[0].scrollHeight > maxHeight) $text.css({'height': (maxHeight) + 'px', 'overflow-y':'auto'});

		$('#dialog').center();

	}

	return this;

};

function DClose() {

	$('#dialog').css('display', 'none').css('width','500px').css('height','unset').css('position','absolute').css('margin','unset');
	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none')
	$('.dialog-preloader').css('display', 'none');

}

/**
 * Подключение js-файла
 * @param path
 * @returns {boolean}
 */
function includeJS(path) {

	for (var i = 0; i < javascripts.length; i++) {
		if (path === javascripts[i]) {
			return false;
		}
	}

	javascripts.push(path);
	$.ajax({
		url: path,
		dataType: "script",// при типе script, JS сам инклюдится и воспроизводится
		async: false
	});

}