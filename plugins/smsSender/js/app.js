/**
 * @license  http://isaler.ru/
 * @author   Vladislav Andreev, http://iandreyev.ru/
 * @charset  UTF-8
 * @version  7.78
 */


$(document).on('click','.ytab',function(){

	var id = $(this).data('id');

	if(id != undefined) {

		$('#dtabs').find('li').not(this).removeClass('current');
		$(this).addClass('current');

		$('#telo').find('div.tabbody').addClass('hidden');
		$('#tab-' + id).removeClass('hidden');

	}

	if(id == 2) loadTpl();

});
$(document).on('click','.yedit',function(){

	var id = $(this).closest('tr').data('id');
	var action = $(this).data('action');

	if(id == 'undefined') id = '0';

	doLoad('index.php?action='+action+'&id=' + id);

});
$(document).on('click','.ydelete',function(){

	var id = $(this).closest('tr').data('id');

	$.get('index.php?action=tpl.delete&id=' + id, function(){
		loadTpl();
	});

});
$(document).on('click','.close', function(){
	DClose();
});
$(document).on('change','#tpl', function(){

	var id = $('#tpl option:selected').val();

	$.get('index.php?action=tpl.get&id=' + id, function(data){
		$('#content').val(data).trigger('change');
	});

});

function loadData(){

	$('#dataTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	var str = '&periodStart='+$('#periodStart').val()+'&periodEnd='+$('#periodEnd').val();

	$.get('index.php?action=loaddata', str, function (datas) {

			var table = '';
			var data = datas.list;

			for (var i in data) {

				var number = parseInt(i) + 1;
				var client = '';

				if(data[i].clid != '0') client = '<a href="/card.client.php?clid='+ data[i].clid +'" title="' + data[i].client + '" target="_blank"><i class="icon-building"></i> ' + data[i].client + '</a>';
				else if(data[i].pid != '0') client = '<a href="/card.person.php?pid='+ data[i].pid +'" title="' + data[i].person + '" target="_blank"><i class="icon-user-1"></i> ' + data[i].person + '</a>';

				table = table +
					'<tr height="40" class="ha hand ydeal" data-user="'+data[i].user+'">' +
					'<td>' + number + '</td>' +
					'<td><b class="blue">' + data[i].datum + '</b></td>' +
					'<td>' + client + '</td>' +
					'<td>' + data[i].phone + '</td>' +
					'<td>' + data[i].content + '</td>' +
					'<td>' + data[i].user + '</td>' +
					'<td>' + data[i].status + '</td>' +
					'</tr>';

			}

			$('#dataTable tbody').empty().html(table);

			var resort = true;
			$("#dataTable").trigger("update", [resort]);

		}, 'json')
		.complete(function () {
		});

}
function loadTpl(){

	$('#tplTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	$.get('index.php?action=loadtpl', function (data) {

			var table = '';

			for (var i in data) {

				var number = parseInt(i) + 1;

				table = table +
					'<tr height="40" class="ha hand ydeal" data-id="'+data[i].id+'">' +
					'<td>' + number + '</td>' +
					'<td>' + data[i].name + '</td>' +
					'<td>' + data[i].content + '</td>' +
					'<td><b class="blue">' + data[i].date + '</b></td>' +
					'<td align="center">' +
					'   <div style="z-index: 0;">' +
					'       <a href="javascript:void(0)" title="Редактировать" class="yedit" data-action="tpl">Изменить</a>&nbsp;&nbsp;<span class="gray">/&nbsp;&nbsp;</span><a href="javascript:void(0)" title="Редактировать" class="ydelete" data-action="tpl.delete">Удалить</a>' +
					'   </div>' +
					'</td>' +
					'</tr>';

			}

			$('#tplTable tbody').empty().html(table);

			var resort = true;
			$("#tplTable").trigger("update", [resort]);

		}, 'json')
		.complete(function () {
		});

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

function sendSMS(){

	var str = $('#Form').serialize();

	$('#dialog_container').css('display', 'none');

	$.post("index.php", str, function(data){

		yNotifyMe("CRM. Результат,"+ data.result +",signal.png");
		loadData();

		DClose();

	},'json');

}
function costSMS(){

	if($('#phone').val() == '') alert('Не указано получатель');
	else if($('#content').val() == '') alert('Пустое сообщение');
	else{

		var str = 'action=sms.cost&phone=' + $('#phone').val() + '&content=' + $('#content').val();

		$('.cost').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

		$.post("index.php", str, function (data) {

			$('.cost').html('Стоимость: <b class="green">'+ data.cost +'</b> руб.;<br>Сообщений: <b class="green">'+ data.count +'</b> <i class="icon-info-circled gray" title="' + data.result + '"></i>');

		}, 'json');

	}
}
function balanceSMS(){

	var str = 'action=sms.balance';

	$('.balance').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	$.post("index.php", str, function(data){

		$('.balance').html('Баланс: <b>'+ data.balance + '</b> руб.');

	}, 'json');
}
function saveTpl(){

	var str = $('#Form').serialize();

	$('#dialog_container').css('display', 'none');

	$.post("index.php", str, function(data){

		yNotifyMe("CRM. Результат,"+data+",signal.png");
		loadTpl();

		DClose();

	});
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
	$('#dialog').css('display', 'none');
	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none')
	$('.dialog-preloader').css('display', 'none');
	$('#dialog');
	$('#dialog').css('width','500px').css('height','unset').css('position','absolute').css('margin','unset');
}

function number_format( number, decimals, dec_point, thousands_sep ) {
	// Format a number with grouped thousands
	//
	// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +	 bugfix by: Michael White (http://crestidg.com)

	var i, j, kw, kd, km;

	// input sanitation & defaults
	if( isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if( dec_point == undefined ){
		dec_point = ",";
	}
	if( thousands_sep == undefined ){
		thousands_sep = ".";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


	return km + kw + kd;
}