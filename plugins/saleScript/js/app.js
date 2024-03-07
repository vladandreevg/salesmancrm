/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  7.78
 */

var $self;

$(document).ready(function() {

	$("#dialog").draggable({handle: ".zagolovok", cursor: "move", opacity: "0.85", containment: "document"});

	var fh  = $(window).height() - 210;
	var fh2 = $(window).height() - 310;

	$('fieldset:not(.notoverflow)').height(fh);
	$('.wrapper').height(fh2);

});

$(document).keydown(function (e) {
	if (e == null) { // ie
		keycode = e.keyCode;
	} else { // mozilla
		keycode = e.which;
	}
	if (keycode === 27) { // escape, close box, esc
		DClose();
		$('.popmenu.nothide').removeClass('open');
	}
});

$(document).on('click','.ytab',function(){

	var id = $(this).data('id');

	if(id !== undefined) {

		$('#dtabs').find('li').not(this).removeClass('current');
		$(this).addClass('current');

		$('#telo').find('div.tabbody').addClass('hidden');
		$('#tab-' + id).removeClass('hidden');

	}

});

//крестик в форме
$(document).on('click', '.close', function () {

	DClose();

});

function setSettings(){
	doLoad('index.php?action=settings');
}
function saveSettings(){

	var str = $('#Form').serialize();

	$('#dialog_container').css('display', 'none');

	$.post("index.php", str, function(data){

		yNotifyMe("CRM. Результат,"+data+",signal.png");

		DClose();

	});
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

	$(".popmenu").hide();
	$(".popmenu-top").hide();

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
			notification.onclick = function () {

				if ($('#mid').is('input')) {

					razdel('inbox');

				}
				else {

					//ymailw = window.open('ymail.php');
					//ymailw.focus();
					$mailer.preview(id);

				}

			};

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

function DClose() {

	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none');
	$('.dialog-preloader').css('display', 'none');
	$('#dialog').css('display', 'none').css('width','500px').css('height','unset').css('position','absolute').css('margin','unset');

}
