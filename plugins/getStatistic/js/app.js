/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
$( function() {

	var hash = window.location.hash.substring(1);

	if(hash === '') hash = 1;

	$.Mustache.load('data/template.mustache');

	$('.ytab[data-id="'+hash+'"]').trigger('click');

	var fh = $(window).height() - 210;
	var fh2 = $(window).height() - 310;

	$('fieldset:not(.notoverflow)').height(fh);
	$('.wrapper').height(fh2);
	$('.wrapper2').height(fh2);

	$('.period').dateRangePicker({
		separator: ' &divide; ',
		getValue: function () {
			if ($('#periodStart').val() && $('#periodEnd').val())
				return $('#periodStart').val() + '  &divide;  ' + $('#periodEnd').val();
			else
				return '';
		},
		setValue: function (s, s1, s2) {
			$('#periodStart').val(s1);
			$('#periodEnd').val(s2);
		}
	});

	$("#dataTable").tablesorter({

		widthFixed: true,
		widgets: ['cssStickyHeaders'],

		widgetOptions: {
			cssStickyHeaders_attachTo: '.wrapper',
			cssStickyHeaders_addCaption: true
		}

	});

	$("#tplTable").tablesorter({

		widthFixed: true,
		widgets: ['cssStickyHeaders'],

		widgetOptions: {
			cssStickyHeaders_attachTo: '.wrapper2',
			cssStickyHeaders_addCaption: true
		}

	});

	$("#botTable").tablesorter({

		widthFixed: true,
		widgets: ['cssStickyHeaders'],

		widgetOptions: {
			cssStickyHeaders_attachTo: '.wrapper3',
			cssStickyHeaders_addCaption: true
		}

	});

	$("#userTable").tablesorter({

		widthFixed: true,
		widgets: ['cssStickyHeaders'],

		widgetOptions: {
			cssStickyHeaders_attachTo: '.wrapper3',
			cssStickyHeaders_addCaption: true
		}

	});

	loadData();

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

$(document).on('click','.ytab',function(){

	var id = $(this).data('id');

	if(id !== 'undefined' && id !== null && id != 100) {

		$('#dtabs').find('li').not(this).removeClass('current');
		$(this).addClass('current');

		$('#telo').find('div.tabbody').addClass('hidden');
		$('#tab-' + id).removeClass('hidden');

	}

	if(id === 0) loadData();
	if(id === 1) checkWebhook();
	else if(id === 2) loadTpl();
	else if(id === 3) {
		loadBot();
		loadUser();
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
$(document).on('change','#tpl', function(){

	var id = $('option:selected', this).val();

	$.get('index.php?action=tpl.get&id=' + id, function(data){
		$('#content').val(data).trigger('change');
	});

});
$(document).on('click', '.tagsmenuToggler', function(){

	$(this).closest('div').find('.tagsmenu').toggleClass('hidden');
	$(this).find('#mapii').toggleClass('icon-angle-down icon-angle-up');

});
$(document).mouseup(function (e){ // событие клика по веб-документу

	//console.log(e);

	var div = $(".tagsmenuToggler"); // тут указываем ID элемента
	if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам
		$(".tagsmenu", this).addClass('hidden');
		div.find('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');
	}

});
$(document).on('click','.tagsmenu li', function(){

	var t = $('b', this).html();

	insTextAtCursor('content', t);

	$('.tagsmenu').toggleClass('hidden');


});
$(document).on('click','.tplview', function(){

	var t = $(this).parent('tr').data('id');

	doLoad('index.php?action=tpl.info&id='+t);

});
$(document).on('click','.ylog', function(){

	var t = $(this).data('id');

	doLoad('index.php?action=log.info&id='+t);

});

function loadData(){

	$('#dataTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	var str = 'page='+$('#page').val()+'&periodStart='+$('#periodStart').val()+'&periodEnd='+$('#periodEnd').val();
	var pg;

	$.get('index.php?action=loaddata', str, function (datas) {

		var datalist = {
			list: datas.list,
			index: function () {
				return ++window['INDEX'] || (window['INDEX'] = 1);
			},
			resetIndex: function () {
				window['INDEX'] = null;
				return;
			}
		};

		$('#dataTable tbody').empty().mustache('listTpl', datalist);

		var page = datas.page;
		var pageall = datas.pageall;

		pg = 'Стр. '+page+' из '+pageall;

		if(pageall > 1){

			var prev = page - 1;
			var next = page + 1;

			if(page === 1) pg = pg + '&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';
			else if(page === pageall) pg = pg + '&nbsp;<a href="javascript:void(0)" onClick="change_page(\'1\')" alt="Начало" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';
			else pg = '&nbsp;<a href="javascript:void(0)" onClick="change_page(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+prev+'\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;'+ pg+ '&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+next+'\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onClick="change_page(\''+pageall+'\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

		}

		var resort = true;
		$("#dataTable").trigger("update", [resort]);

	}, 'json')
		.done(function () {

			$('#pagediv').html(pg);

		});

}
function loadTpl(){

	$('#tplTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	$.get('index.php?action=loadtpl', function (data) {

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

		$('#tplTable tbody').empty().mustache('tplTpl', datalist);

		var resort = true;
		$("#tplTable").trigger("update", [resort]);

	}, 'json')

}
function loadBot(){

	$('#tplTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	$.get('index.php?action=loadbots', function (data) {

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

		$('#botTable tbody').empty().mustache('botTpl', datalist);

		var resort = true;
		$("#botTable").trigger("update", [resort]);

	}, 'json')

}
function loadUser(){

	$('#userTable tbody').empty().append('<div id="loader"><img src="/assets/images/loading.gif"></div>');

	$.get('index.php?action=loadusers', function (data) {

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

		$('#userTable tbody').empty().mustache('userTpl', datalist);

		var resort = true;
		$("#userTable").trigger("update", [resort]);

	}, 'json')

}

function setSettings(){
	doLoad('index.php?action=settings');
	return false;
}

function saveForm(){

	var str = $('#Form').serialize();
	var action = $('#action').val();

	$('#dialog_container').css('display', 'none');

	$.post("index.php", str, function(data){

		yNotifyMe("CRM. Результат,"+data+",signal.png");

		if(action === 'tpl.save') loadTpl();
		if(action === 'bot.save') loadBot();
		if(action === 'user.save') loadUser();

		DClose();

	});
}

function checkWebhook(){

	$('#webhook').load('index.php?action=check.webhook');

}
function editWebhook(event, url){

	$.get('/content/admin/webhook.php?action=edit_do&title=userNotifier&event='+event+'&url='+url, function(data){

		yNotifyMe("CRM. Результат,"+ data.result +",signal.png");
		checkWebhook();

	},'json');

}
function addWebhook(){

	$.get('index.php?action=add.webhook', function(data){

		if(data.error == "") yNotifyMe("CRM. Результат,Добавлено "+ data.result +" событий,signal.png");
		else yNotifyMe("CRM. Результат,"+ data.error +",signal.png");
		checkWebhook();

	},'json');

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

	$('#dialog').css('display', 'none').css('width','500px').css('height','unset').css('position','absolute').css('margin','unset');
	$('#resultdiv').empty();
	$('#dialog_container').css('display', 'none');
	$('.dialog-preloader').css('display', 'none');

}
function insTextAtCursor(_obj_name, _text){

	console.log(_obj_name);

	var area=document.getElementsByName(_obj_name).item(0);
	if ((area.selectionStart)||(area.selectionStart=='0')){

		var p_start=area.selectionStart;
		var p_end=area.selectionEnd;

		area.value=area.value.substring(0,p_start)+_text+area.value.substring(p_end,area.value.length);
	}

	if (document.selection){
		area.focus();
		sel=document.selection.createRange();
		sel.text=_text;
	}
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