/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  7.78
 */

var $self;

$(function() {

	var fh  = $(window).height() - 210;
	var fh2 = $(window).height() - 310;

	$('fieldset:not(.notoverflow)').height(fh);
	$('.wrapper').height(fh2);
	$('.wrapper2').height(fh2);

	$('.period').dateRangePicker({
		separator : ' &divide; ',
		getValue: function()
		{
			if ($('#periodStart').val() && $('#periodEnd').val() )
				return $('#periodStart').val() + '  &divide;  ' + $('#periodEnd').val();
			else
				return '';
		},
		setValue: function(s,s1,s2)
		{
			$('#periodStart').val(s1);
			$('#periodEnd').val(s2);
		}
	});

	$("#dataTable").tablesorter({

		widthFixed : true,
		widgets: [ 'cssStickyHeaders' ],

		widgetOptions: {
			cssStickyHeaders_attachTo      : '.wrapper',
			cssStickyHeaders_addCaption    : true
		}

	});
	$("#pathTable").tablesorter({

		widthFixed : true,
		widgets: [ 'cssStickyHeaders' ],

		widgetOptions: {
			cssStickyHeaders_attachTo      : '.wrapper2',
			cssStickyHeaders_addCaption    : true
		}

	});

	$('select').each(function (){

		var sel = $('option:selected', this).prop("selected", true)

	})

	loadData();

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
$(document).on('click','.close', function(){
	DClose();
});
$(document).on('click','i.delete',function(){

	$(this).closest('tr').remove();

});

function checkWebhook(){

	$('#webhook').load('index.php?action=check.webhook');

}
function editWebhook(event, url){

	$.get('/content/admin/webhook.php?action=edit_do&title=autoMailingList&event='+event+'&url='+url, function(data){

		yNotifyMe("CRM. Результат,"+ data.result +",signal.png");
		checkWebhook();

	},'json');

}

function save(){

	var str = $('#form').serialize();

	$.post('index.php', str, function(data){

		yNotifyMe("CRM. Результат,"+data+",signal.png");

	});
}

function getLists(){

	var str = $('#Form').serialize();
	var errors = '';

	$('#Lists').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	$.post('index.php', str, function(data){

		var $lists = data.list;
		var errors = data.error;
		var string = '';

		if(typeof errors === 'undefined' || errors == null || errors == "") {

			for(var i in $lists){

				var t = ($lists[i].total > 0) ? ', Подписчиков: <b>' + $lists[i].total + '</b>' : '';

				string = string + '<li>ID: <b>'+ $lists[i].id + '</b>, Название: <b>' + $lists[i].name + '</b>' + t + '</li>';

			}

			$('#Lists').empty().append('<div class="green">Настройки сохранены.</div><div>Загруженные списки:</div><ul>'+ string+ '</ul>');

		}
		else{

			$('#Lists').empty().append('<div class="red">Ошибка: '+ errors + '</div>');

		}
	},'json');
}

function setAccess(){
	doLoad('index.php?action=access');
}
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

function loadData(){

	$('#dataTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	var str = '&periodStart='+$('#periodStart').val()+'&periodEnd='+$('#periodEnd').val();

	$.get('index.php?action=loaddata', str, function (datas) {

		var table = '';
		var data = datas.list;

		for (var i in data) {

			var number = parseInt(i) + 1;
			var client = '';
			var subtip = '';

			if(data[i].pid > 0) client = client + '<div class="mb5"><a href="javascript:void(0)" onclick="openPerson('+ data[i].pid +')" title="' + data[i].person + '"><i class="icon-user-1 blue"></i> ' + data[i].person + '</a></div>';
			if(data[i].clid > 0) client = client + '<div><a href="javascript:void(0)" onclick="openClient('+ data[i].clid +')" title="' + data[i].client + '" class="broun"><i class="icon-building broun"></i> ' + data[i].client + '</a></div>';
			if(data[i].did > 0) client = client + '<div><a href="javascript:void(0)" onclick="openDogovor('+ data[i].did +')" title="' + data[i].deal + '"><i class="icon-briefcase-1 green"></i> ' + data[i].deal + '</a></div>';

			if(data[i].subtip == null) subtip = "--";
			else subtip = data[i].subtip;

			table = table +
				'<tr class="th40 ha hand ydeal" data-user="'+data[i].user+'">' +
				'<td>' + number + '</td>' +
				'<td><b class="blue">' + data[i].datum + '</b></td>' +
				'<td><div>' + data[i].tip + '</div><div class="fs-09 gray2">' + subtip + '</div></td>' +
				'<td>' + client + '</td>' +
				'<td>' + data[i].name + '</td>' +
				'<td>' + data[i].email + '</td>' +
				'<td>' + data[i].user + '</td>' +
				'</tr>';

		}

		$('#dataTable tbody').empty().html(table);

		var resort = true;
		$("#dataTable").trigger("update", [resort]);

	}, 'json');

}

function doLoad(url){

	$('#dialog_container').css('height', $(window).height());
	$('#dialog').css('width','500px').css('height','unset').css('display', 'none');
	$('#dialog_container').css('display', 'block');
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
	var notification = new Notification('',{});

	if(Notification.permission === 'granted') {

		if (("Notification" in window)) {

			if (Notification.permission === "granted") {
				notification = new Notification(title, {
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
						notification = new Notification(title, {
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
	$('#dialog').css('display', 'none');
	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none')
	$('.dialog-preloader').css('display', 'none');
	$('#dialog');
	$('#dialog').css('width','500px').css('height','unset').css('position','absolute').css('margin','unset');
}