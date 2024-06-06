/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2019 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2019.x           */
/* ============================ */
$( function() {

	$("#dialog").draggable({handle: ".zagolovok", cursor: "move", opacity: "0.85", containment: "document"});

	$.Mustache.load('assets/tpl/settings.mustache');

	$app.loadOperators();
	$app.loadChannels();

});

$(window).on('resizeEnd', function () {

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
$(window).on('resize', function () {

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

$(document).on('keydown', function(e){
	if(e == null) { // ie
		keycode = event.keyCode;
	} else { // mozilla
		keycode = e.which;
	}
	if(keycode === 27){ // escape, close box, esc
		DClose();
		$('.popmenu.nothide').removeClass('open');
	}
});

$(document).on('click','.yedit',function(){

	var id = $(this).closest('tr').data('id');
	var action = $(this).data('action');
	var url = 'index.php?action='+action+'&id=' + id;

	if(id === 'undefined') id = '0';

	doLoad(url);

});
$(document).on('click','.ydelete',function(){

	var id = $(this).closest('tr').data('id');
	var action = $(this).data('action');
	var url = 'index.php?action='+action+'&id=' + id;

	$.get(url, function(){

		if(action === 'tpl.delete') loadTpl();
		else if(action === 'bot.delete') loadBot();
		else if(action === 'user.delete' || action === 'user.activate') loadUser();

	});

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
	editChannel : function(id){

		doLoad("php/modal.php?action=channel.edit.form&id="+id);

	},
	removeChannel: function(id){

		Swal.fire({
				title: 'Вы уверены?',
				text: "Будут удалены все данные, собранные плагином и его настройки!",
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

				$.get("php/modal.php?action=channel.delete&id="+id, function(data){

					Swal.fire({
						imageUrl: 'assets/images/success.svg',
						imageWidth: 50,
						imageHeight: 50,
						//position: 'bottom-end',
						html: '' + data + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});

					$app.loadChannels();

				});

			}

		});

	},
	loadChannels: function (){

		$('div[data-id="channels"]').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

		$.get('php/modal.php?action=channel.get', function (data) {

			var datalist = {
				list: data,
				index: function () {
					return ++window['INDEX'] || (window['INDEX'] = 1);
				},
				resetIndex: function () {
					window['INDEX'] = null;
					return;
				}
			};

			$('div[data-id="channels"]').empty().mustache('channelTpl', datalist);

		}, 'json')
			.done(function () {

			});

	},
	infoChannels: function(id){

		doLoad("php/modal.php?action=channel.info&id="+id);

	},
	editUser: function(){

		doLoad("php/modal.php?action=operator.edit.form");

	},
	removeUser: function(id){

		Swal.fire({
				title: 'Вы уверены?',
				text: "Будут удалены все данные, собранные плагином и его настройки!",
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

				$.get("php/modal.php?action=operator.delete&iduser="+id, function(data){

					$app.loadOperators();

				});

			}

		});

	},
	loadOperators: function (){

		$('div[data-id="users"]').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

		$.get('php/modal.php?action=operator.get', function (data) {

			var datalist = {
				list: data,
				index: function () {
					return ++window['INDEX'] || (window['INDEX'] = 1);
				},
				resetIndex: function () {
					window['INDEX'] = null;
					return;
				}
			};

			$('div[data-id="users"]').empty().mustache('userTpl', datalist);

		}, 'json')
			.done(function () {
			});

	},
	getJsCode: function(){

		doLoad("php/modal.php?action=getJsCode");

	}
};

function saveSettings() {

	var str = $('#settingsForm').serialize();
	var url = $('#settingsForm').attr("action");

	$.post(url, str, function (data) {

		if (data.status === 'ok') {

			Swal.fire({
				imageUrl: 'assets/images/success.svg',
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
				imageUrl: 'assets/images/error.svg',
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
		.done(function() {

			$('#dialog').css('display', 'block');
			$('.dialog-preloader').css('display', 'none');

		});

	return false;

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
					icon: '/assets/images/' + img,
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
							icon: '/assets/images/' + img,
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