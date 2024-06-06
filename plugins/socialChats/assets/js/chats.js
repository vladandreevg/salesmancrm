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

/**
 * Основные переменные
 */
var $chat_id = localStorage.getItem("lastChat");
var $lastMessageID = 0;
var $operators = [];
var $tab = window.location.hash.substring(1);

var chatUpdateWorker;
var fList = [];
var getDialog, getNewChats, getUnreadChats;
var isComet = false;
var $dpage = 1;
var $report = 'channels';

let faviconChat = new Favico({
	type : 'rectangle',
	animation: 'slide',
	bgColor : '#1565C0',
});

$.Mustache.load('assets/tpl/chat.mustache');

$(function () {

	$mainFunc.operators()
		.then(r => razdel());

	$mainFunc.newChatsCount();

	/**
	 * Работа с Comet-сервером и WS
	 */
	//if(cometDevID !== '' && cometUserID !== '' && cometUserKey !== '') {

		isComet = true;

		cometApi.start({dev_id: cometDevID, user_id: cometUserID, user_key: cometUserKey, node: "app.comet-server.ru"});

		// подписка на канал. Сообщения всем операторам, если чат не назначен
		cometApi.subscription(cometChannel, function (msg) {

			console.log(msg);

			let chatid = msg.data.chatid;
			let text = msg.data.text;
			let tip = msg.data.tip;

			// в противном случае сообщаем о сообщении в другом чате
			if (tip === 'newchat') {

				$mainFunc.newChatsCount();

				yNotifyMe({
					title: "Новый диалог",
					content: text,
					chatid: chatid
				});

			}
			else if (tip === 'newmessage') {

				//$mainFunc.dialog();

				$mainFunc.chats(true);
				//$mainFunc.unreadChats();

				yNotifyMe({
					title: "Новое сообщение в диалоге",
					content: text,
					chatid: chatid
				});

			}
			else {

				yNotifyMe({
					title: "Уведомление",
					content: text,
					chatid: chatid
				});

				$mainFunc.chats();

			}

		});

		// подписка на сообщения
		cometApi.subscription("msg", function (msg) {

			console.log(msg);

			let chatid = msg.data.chatid;
			let text = msg.data.text;
			let tip = msg.data.tip;

			// если пришло сообщение в активный чат
			if (chatid === $chat_id) {

				$mainFunc.dialog();

				yNotifyMe({
					title: "Сообщение в диалоге",
					content: text,
					chatid: chatid
				});

			}
			// в противном случае сообщаем о сообщении в другом чате
			else if (tip === 'newmessage') {

				$mainFunc.chats(true);
				//$mainFunc.unreadChats();

				yNotifyMe({
					title: "Новое сообщение в диалоге",
					content: text,
					chatid: chatid
				});

			}
			else {

				yNotifyMe({
					title: "Уведомление",
					content: text,
					chatid: chatid
				});

				$mainFunc.chats();

			}

		});

	//}
	//работа с comet-сервером

	//chatUpdateWorker = Visibility.every(5000,60000, $mainFunc.dialog);

	/**
	 * Запускаем проверку наличия новых сообщений в чатах
	 * todo:
	 * проработать проверку открытых чатов на наличие не прочитанных сообщений
	 * и обновление только признака наличия новых сообщений
	 */
	//Visibility.every(3000,60000, $mainFunc.unreadChats);
	//Visibility.every(5000,60000, $mainFunc.newChatsCount);

	if (isMobile) {

		includeJS('/assets/js/smMobileTable.js');
		includeJS('/assets/js/jquery/jquery.scrollTo.js');

	}

	$(document).on('keydown', function (e) {

		var keycode;

		// ie
		if (e == null)
			keycode = e.keyCode;
		// mozilla
		else
			keycode = e.which;

		if (keycode === 17)
			isCtrl = true;

	});
	$(document).on('keyup', function () {

		isCtrl = false;

	});

	$('.flyit').each(function () {

		var id = $(this).data('id');
		$(this).find('.yselectBox').detach().appendTo('.flyitbox[data-id="' + id + '"]');

	});
	$('.ydropString:not(.disabled)').each(function () {

		var txt = striptags($(this).find('label').text()).replace(/<[^p].*?>/g, '').trim();

		$(this).prop("title", txt);

	});

	$(document).on('click', '.ydropDown', function () {

		//скрываем остальные элементы
		var $other = $('.ydropDown.open').not(this);

		$other.find(".yselectBox").each(function () {

			$(this).hide();
			$other.find(".action").addClass('hidden');

		});

		//если элемент не закреплен
		if (!$(this).hasClass('flyit')) {

			var $el = $(".yselectBox", this);

			if (!$(this).hasClass('dWidth')) $el.css('width', $(this).actual('outerWidth') - 2);
			$el.toggle();

			if ($(this).hasClass('open')) {

				$(this).removeClass('open');
				$el.removeClass('open');

			}
			else {

				$(this).addClass('open');
				$el.addClass('open');

			}

		}
		else {

			var element = $(this).data('id');
			var offset = $(this).offset();
			var width = $(this).outerWidth();
			var height = $(this).outerHeight() + 1;

			if ($(this).hasClass('open')) {

				$(this).removeClass('open');
				$('.yselectBox[data-id="' + element + '"]').removeClass('open');

			}
			else {

				$(this).addClass('open');
				$('.yselectBox[data-id="' + element + '"]').addClass('open');

			}

			$('.yselectBox.open').not('[data-id="' + element + '"]').each(function () {

				var el = $(this).data('id');
				var $elm = $('.ydropDown[data-id="' + el + '"]');

				$(this).removeClass('open');//.hide();
				$elm.removeClass('open');

			});

			$('.' + element).css({
				"width": width + "px",
				"top": (offset.top + height) + "px",
				"left": (offset.left) + "px",
				"z-index": "1000",
				"position": "fixed"
			}).toggle();

		}

		$(".action", this).toggleClass('hidden');

	});
	$(document).on('click', '.ydropString:not(.yRadio):not(.disabled)', function () {

		var ebox;

		if (!$(this).closest('.yselectBox').hasClass('fly')) {

			ebox = $(this).parents('.ydropDown');
			var chk = $(this).parent('.yselectBox').find('input[type=checkbox]:checked').length;
			var $f = $(this).parents('.ydropDown').find('.ydropCount');
			var a = $f.html();

			$f.html(chk + ' выбрано');

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			ebox = $('.ydropDown[data-id="' + element + '"]');

			var $f2 = ebox.find('.ydropCount');
			var ch2 = $(this).closest('.yselectBox').find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' выбрано');

		}

		setTimeout(function () {
			$('.yselectBox[data-id="' + element + '"]').show();
			ebox.find('.action').removeClass('hidden');
		}, 1);

	});
	$(document).on('click', '.ydropString.yRadio:not(.disabled)', function () {

		var rak;
		var $fr;
		var ebox;

		if (!$(this).closest('.yselectBox').hasClass('fly')) {

			ebox = $(this).parents('.ydropDown');
			rak = $(this).find('input[type=radio]:checked').data('title');
			$fr = $(this).parents('.ydropDown').find('.ydropText');

			$fr.html(rak).prop('title', rak);

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			ebox = $('.ydropDown[data-id="' + element + '"]');
			rak = $(this).closest('.yselectBox').find('input[type=radio]:checked').data('title');
			$fr = ebox.find('.ydropText');

			$fr.html(rak).prop('title', rak);

		}

		$(this).addClass('bluebg-sub');
		$(this).closest('.yselectBox').find('.ydropString').not(this).removeClass('bluebg-sub');

		setTimeout(function () {

			var $ee = ebox.find('.yselectBox');

			if ($ee.is(':visible')) $ee.hide();

		}, 11);

	});
	$(document).on('click', '.ySelectAll', function () {

		var $elm = $(this).closest('.yselectBox');
		var $box = $(this).closest('.ydropDown');

		if (!$elm.hasClass('fly')) {

			$elm.find('input[type=checkbox]').prop('checked', true);

			var $f = $box.find('.ydropCount');
			var ch = $box.find('input[type=checkbox]:checked').length;

			$f.html(ch + ' выбрано');

			setTimeout(function () {
				$elm.show();
			}, 10);

		}
		else {

			var element = $elm.data('id');
			$box = $('.ydropDown[data-id="' + element + '"]');

			$('.yselectBox[data-id="' + element + '"]').find('input[type=checkbox]').prop('checked', true);

			var $f2 = $box.find('.ydropCount');
			var ch2 = $elm.find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' выбрано');

			setTimeout(function () {
				$('.yselectBox[data-id="' + element + '"]').show();
				$box.find('i.icon-down-open').toggleClass('icon-down-open icon-up-open');
				$box.find('.action').removeClass('hidden');
			}, 10);

		}

		return false;

	});
	$(document).on('click', '.yunSelect', function () {

		var $elm = $(this).closest('.ydropDown');
		var $box = $(this).closest('.yselectBox');

		if (!$box.hasClass('fly')) {

			var chk = $box.find('input[type=checkbox]:checked').prop('checked', false);
			var $f = $elm.find('.ydropCount');

			$box.find('input[type=checkbox]:checked').prop('checked', false);

			$f.html('0 выбрано');

			setTimeout(function () {
				$(this).closest('.yselectBox').show();
			}, 10);

		}
		else {

			var element = $(this).closest('.yselectBox').data('id');
			$box = $('.ydropDown[data-id="' + element + '"]');

			$('.yselectBox[data-id="' + element + '"]').find('input[type=checkbox]').prop('checked', false);

			var $f2 = $box.find('.ydropCount');
			var ch2 = $box.find('input[type=checkbox]:checked').length;

			$f2.html(ch2 + ' выбрано');

			setTimeout(function () {
				$('.yselectBox[data-id="' + element + '"]').show();
				$box.find('.action').removeClass('hidden');
			}, 10);

		}

		return false;

	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу

		var div = $(".ydropDown.open"); // тут указываем ID элемента

		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам

			$(".yselectBox.open").removeClass('open').hide();

			div.find(".action").addClass('hidden');
			div.removeClass('open');

		}

	});

	$(document).on('click', '.tagsmenuToggler', function () {

		$('.tagsmenu').not(this).removeClass('show');

		$(this).closest('div').find('.tagsmenu').toggleClass('show');
		$(this).find('#mapii').toggleClass('icon-angle-down icon-angle-up');

	});
	$(document).on('mouseup', function (e) { // событие клика по веб-документу

		//console.log(e);

		var div = $(".tagsmenuToggler"); // тут указываем ID элемента
		if (!div.is(e.target) && div.has(e.target).length === 0) { // и не по его дочерним элементам
			$(".tagsmenu", this).removeClass('show');
			div.find('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');
		}
	});

	$('input[data-type="date"]').datepicker({
		dateFormat: 'yy-mm-dd',
		firstDay: 1,
		dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
		monthNamesShort: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
		changeMonth: true,
		changeYear: true
	});

	if (isMobile || $(window).width() < 500) {

		$('input.datum').each(function () {
			this.setAttribute('type', 'date');
		});
		$('input.inputdate').each(function () {
			this.setAttribute('type', 'date');
		});

	}

	yDropSelectSetText();

	// обновление информации
	$(document).off('click', '.refresh');
	$(document).on('click', '.refresh', function () {

		$mainFunc.chatUpdateInfo();

	});

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

	$(document).on('click', '.popblock-items[data-action="period"] .popblock-item', function (e) {

		var $period = $(this).data('period');
		var $goal = $(this).closest('.popblock-items').data('goal');
		var $elm = $('#' + $goal);

		if ($period !== undefined) {

			$elm.find('.dstart').val(period[$period][0]);
			$elm.find('.dend').val(period[$period][1]);

		}
		else {

			$elm.find('.dstart').val('');
			$elm.find('.dend').val('');

		}

		e.preventDefault();
		e.stopPropagation();

		return false;

	});

	$(document).on('click', '.reports', function(){

		$report = $(this).data('file');

		//$('.reports').removeClass('active');
		//$(this).addClass('active');

		$mainFunc.reports();

	});

	$(document).on('click', '.dialog-closer', function(){

		$('.chat-layout .messageslist').css({"left":"100vw"});

	});

});

Visibility.change(function (e, state) {

	if(state === 'visible') {

		setTimeout(function () {
			faviconChat.reset();
		},500);

	}

});

if ( !Visibility.hidden() ) {

	faviconChat.reset();

}

window.onhashchange = razdel;

//Даты и периоды
var period = {
	all: ['', ''],
	today: [moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD')],
	yestoday: [moment().subtract(1, 'days').format('YYYY-MM-DD'), moment().subtract(1, 'days').format('YYYY-MM-DD')],
	calendarweek: [moment().weekday(1).format('YYYY-MM-DD'), moment().weekday(7).format('YYYY-MM-DD')],
	calendarweekprev: [moment().subtract(1, 'week').weekday(1).format('YYYY-MM-DD'), moment().subtract(1, 'week').weekday(7).format('YYYY-MM-DD')],
	prevmonth: [moment().subtract(1, 'months').startOf('month').format('YYYY-MM-DD'), moment().subtract(1, 'months').endOf('month').format('YYYY-MM-DD')],
	month: [moment().startOf('month').format('YYYY-MM-DD'), moment().endOf('month').format('YYYY-MM-DD')],
	prevquart: [moment().subtract(1, 'quarter').startOf('quarter').format('YYYY-MM-DD'), moment().subtract(1, 'quarter').endOf('quarter').format('YYYY-MM-DD')],
	quart: [moment().startOf('quarter').format('YYYY-MM-DD'), moment().endOf('quarter').format('YYYY-MM-DD')],
	year: [moment().startOf('year').format('YYYY-MM-DD'), moment().endOf('year').format('YYYY-MM-DD')]
};

// основная функция
let $mainFunc = {
	// вывод чатов
	chats: async function (listonly) {

		var str = $('#filterForm').serialize();
		var elm = $('.mainblock[data-id="tab-chats"]');

		if (elm.html() === '') {
			elm.append('<div class="text-center mt20"><img src="/assets/images/loading.svg"></div>');
		}

		//console.log('загрузка списка чатов..');

		await fetch("php/chats.php?action=list&" + str)
			.then(response => response.json())
			.then(viewData => {

				elm.empty().mustache('chatsTpl', viewData);

				//$secPageTotal = viewData.pageall;

				var page = parseInt(viewData.page);
				var pageall = parseInt(viewData.pageall);
				var pg = 'Страница ' + page + ' из ' + pageall;

				if (pageall > 1) {

					var prev = page - 1;
					var next = page + 1;

					if (page === 1)
						pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

					else if (page === pageall)
						pg = pg + '&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;';

					else
						pg = '&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'1\')" title="Начало"><i class="icon-angle-double-left"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'' + prev + '\')" title="Предыдущая"><i class="icon-angle-left"></i></a>&nbsp;' + pg + '&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'' + next + '\')" title="Следующая"><i class="icon-angle-right"></i></a>&nbsp;&nbsp;<a href="javascript:void(0)" onclick="$mainFunc.changpage(\'' + pageall + '\')" title="Последняя"><i class="icon-angle-double-right"></i></a>&nbsp;';

				}

				$('.pagediv').html(pg);

			})
			.then(function () {

				if ($chat_id === 0)
					$chat_id = $('.chatlist:not(.hidden):first').data('chat');

				if (listonly === undefined) {

					$lastMessageID = 0;

					if(!isMobile) {

						$mainFunc.dialog(true)
							.then(r => function () {
								elm.scrollTo('div[data-chat="' + $chat_id + '"]');
							});
						//.then(r => $mainFunc.unreadChats);

					}

				}
				else{

					let elm2 = $('.chatlist[data-chat="' + $chat_id + '"]');

					$('.chatlist').removeClass('active');
					elm2.addClass('active');

				}

				$mainFunc.unreadChats();

			})
			.catch(error => {

				//clearTimeout(getDialog);
				//getDialog = setTimeout($mainFunc.dialog, 5000);

			});

	},
	"changpage": async function (page) {

		$chat_id = 0;

		$('#page').val(page);

		$('.mainblock[data-id="tab-chats"]').prepend('<div class="text-center mt20"><img src="/assets/images/loading.svg"></div>');

		await $mainFunc.chats();

	},
	// новые сообщения в чатах
	unreadChats: async function () {

		//clearTimeout(getUnreadChats);

		let str = $('#filterForm').serialize();

		await fetch("php/chats.php?action=unreadChats&" + str)
			.then(response => response.json())
			.then(viewData => {

				$('.chatlist').find('.chat--unread').html('').addClass('hidden');

				for (var i in viewData) {

					if (parseInt(viewData[i].unread) > 0) {

						$('.chatlist[data-id="' + viewData[i].id + '"]').find('.chat--unread').html(viewData[i].unread).removeClass('hidden');

					}

				}

			})
			.then(function () {

				//getUnreadChats = setTimeout($mainFunc.unreadChats, 2500);

			})
			.catch(error => {

				//clearTimeout(getUnreadChats);
				//getUnreadChats = setTimeout($mainFunc.unreadChats, 5000);

			});

	},
	// новые чаты, не закрепленные по сотрудникам
	newChatsCount: function () {

		//clearTimeout(getNewChats);

		$.get("php/chats.php?action=newChatsCount", function (data) {

			if (parseInt(data) > 0) {

				$('.new--chats').html(data).removeClass('hidden');

			}
			else
				$('.new--chats').addClass('hidden');

			//getNewChats = setTimeout($mainFunc.newChatsCount, 5000);

			$mainFunc.autoclose();

		});

	},
	// оператор принимает чат
	chatSetUser: function (iduser) {

		$.getJSON('php/chats.php?action=chatSetUser&chat_id=' + $chat_id + '&iduser=' + iduser, function (data) {

			$mainFunc.chats();
			$mainFunc.dialog().then(function () {
				$mainFunc.consumerInfo();
			});

		});

	},
	// обновление информации о посетителе от провайдера
	chatUpdateInfo: function(){

		let $el = $('.chatlist[data-chat="'+ $chat_id +'"]');
		let $el2 = $('.fullavatar');

		$.getJSON('php/chats.php?action=chatUpdateInfo&chat_id=' + $chat_id , function (data) {

			if( data.ok ) {

				let name = data.client_firstname + ' ' + data.client_lastname;
				let avatarka = data.client_avatar;//.replace("\\","");

				//console.log(avatarka);

				if(data.client_firstname != null) {

					$el.find('.uname').find('div:nth(1)').html(name);
					$el2.find('.uname').find('div:nth(0)').html(name);

				}

				$el.find('.avatar--mini').css({
					'background': 'url(' + avatarka + ') no-repeat center center',
					'background-size': 'cover'
				});
				$el2.find('.avatar--mini').css({
					'background': 'url(' + avatarka + ') no-repeat center center',
					'background-size': 'cover'
				});

				$el.data('avatar', avatarka);

			}
			else{
				Swal.fire({
					imageUrl: 'assets/images/error.svg',
					imageWidth: 50,
					imageHeight: 50,
					html: data.message,
					icon: 'info',
					showConfirmButton: false,
					timer: 1500
				});
			}

		});

	},
	// удаление чата
	chatDelete: function(){

		Swal.fire({
				title: 'Вы уверены?',
				html: "Диалог будет удален безвозвратно.<br>Удаление письма у собеседника зависит от провайдера",
				type: 'question',
				showCancelButton: true,
				confirmButtonColor: '#3085D6',
				cancelButtonColor: '#78909C',
				confirmButtonText: 'Да, выполнить',
				cancelButtonText: 'Отменить',
				confirmButtonClass: 'greenbtn',
				cancelButtonClass: 'redbtn'
			}
		).then((result) => {

			if (result.value) {

				$.getJSON('php/chats.php?action=deleteChat&chat_id=' + $chat_id, function (data) {

					if(data.result) {

						$chat_id = 0;
						razdel();

						Swal.fire({
							imageUrl: 'assets/images/success.svg',
							imageWidth: 50,
							imageHeight: 50,
							html: data.message,
							icon: 'info',
							showConfirmButton: false,
							timer: 1500
						});

					}
					else{

						Swal.fire({
							imageUrl: 'assets/images/error.svg',
							imageWidth: 50,
							imageHeight: 50,
							html: data.message,
							icon: 'info',
							showConfirmButton: false,
							timer: 1500
						});

					}

				});

			}
			else{
				
				razdel();
				
			}

		});

	},
	// пагинация списка чатов
	chatsChangPage: function (page) {

		$('#page').val(page);
		$mainFunc.chats();

	},
	// загрузка диалога
	dialog: async function (isnew) {

		//clearTimeout(getDialog);

		if(isMobile){
			$('.chat-layout .messageslist').css({"left":"0"});
		}

		isnew = isnew !== undefined;

		let elm = $('.mainblock[data-id="tab-chats"]');
		let elm2 = $('.chatlist[data-chat="' + $chat_id + '"]');

		let viz = (elm.is(':visible')) ? '&viz=true' : '';
		let lastmessageid = $('.dialogs').find('.answer:last').data('id');

		$('.chatlist').removeClass('active');
		elm2.addClass('active');
		elm2.find('.chat--unread').remove();

		// если это новый выбранный чат
		if (isnew) {

			$('.fullavatar').empty().html(elm2.html());

			$('.fullavatar').find('.time').remove();
			$('.fullavatar').find('div[data-id="lastmessage"]').remove();

			$('.dialogs').append('<div class="text-center mt20"><img src="/assets/images/loading.svg"></div>');
			lastmessageid = 0;

			$mainFunc.consumerInfo();

			$dpage = 1;

		}

		await fetch("php/chats.php?action=messages&page="+$dpage+"&chat_id=" + urlEncodeData($chat_id) + viz + '&isnew=' + isnew + '&lastmessage=' + parseInt(lastmessageid))
			.then(response => response.json())
			.then(viewData => {

				if (isnew)
					$('.dialogs').empty().mustache('messagesTpl', viewData);

				else {

					if (lastmessageid !== viewData.chat.lastmessage.id && viewData.list.length > 0) {

						$('.dialogs').find('.space-100').prepend($.Mustache.render('messagesTpl', viewData));
						isnew = true;

					}

				}

				// если диалог еще никем не взят в работу
				if (viewData.chat.isnew)
					$('#message').prop('disabled', true);
				else
					$('#message').prop('disabled', false);

				// если есть новые чаты
				if (parseInt(viewData.NewChatsCount) > 0)
					$('.new--chats').html(viewData.NewChatsCount).removeClass('hidden');
				else
					$('.new--chats').addClass('hidden');

				let us = viewData.chat.users;
				let uava = '';

				//console.log(us);
				//console.log($operators);

				for(var i in us) {

					//console.log($operators[us[i]]);

					uava += '<div class="avatar--mini relativ" style="background: url('+ $operators[us[i]].avatar +'); background-size:cover;" title="'+ $operators[us[i]].title +'" data-id="'+ $operators[us[i]].uid +'"><div class="status"></div></div>';

				}

				$('.messageslist .chatUsers').html(uava);

			})
			.then(function () {

				//let lastMessage = $('.answer:last').data('messageid');

				if (isnew === true) {
					//$('.dialogs').scrollTo('div[data-messageid="' + lastMessage + '"]');
					$('.dialogs')
						.animate({
							scrollTop: $(".dialogs")[0].scrollHeight
						}, 1000);
				}

				// сохраняем в локальное хранилище
				localStorage.setItem("lastChat", $chat_id);
				$lastMessageID = $('.answer:last').data('id');

				//getDialog = setTimeout($mainFunc.dialog, 5000);

			})
			.catch(error => {

				//clearTimeout(getDialog);
				//getDialog = setTimeout($mainFunc.dialog, 5000);

			});

		$('#sendForm').ajaxForm({
			dataType: 'json',
			data: {chat_id: $chat_id},
			beforeSubmit: function () {
				$('.dialogs').find('.space-100').prepend('<div class="notify"><i class="icon-mail-alt"></i> Отправляю сообщение...</div><div class="space-100"></div>');
				return true;
			},
			success: function (data) {

				if (data.result !== 'ok' || data.response.text.result === 'error') {

					Swal.fire({
						imageUrl: 'assets/images/error.svg',
						imageWidth: 50,
						imageHeight: 50,
						html: '' + data.errors + '',
						icon: 'info',
						showConfirmButton: false,
						timer: 3500
					});
					
					//return false;

				}

				// Очищаем файлы
				var fhtml = $('#filetemplate').html();
				$('.filebox').empty().append(fhtml).addClass('hidden');
				$('.description').empty().addClass('hidden');

				$('#message').text('');
				$('.messagetext').find('#message').val('');
				$('.dialogs').find('.notify').remove();

				$lastMessageID = 0;
				$mainFunc.dialog(true);

			}

		});

	},
	// подгрузка предыдущих записей диалога
	dialogMore: async function(){

		var maxid = $('.dialogs').find('.answer:first').data('id');

		$('.loadmore').remove();
		$('.dialogs').prepend('<div class="text-center mt20 loader"><img src="/assets/images/loading.svg"></div>');

		$dpage++;

		await fetch("php/chats.php?action=messages&page="+$dpage+"&chat_id=" + $chat_id + "&maxid="+maxid)
			.then(response => response.json())
			.then(viewData => {

				$('.dialogs').prepend($.Mustache.render('messagesTpl', viewData));
				$('.loader').remove();
				$('.space-100:first').remove();

			})
			.catch(error => {

				//clearTimeout(getDialog);
				//getDialog = setTimeout($mainFunc.dialog, 5000);

			});

	},
	// список операторов
	operators: async function () {

		await fetch("php/chats.php?action=operators")
			.then(response => response.json())
			.then(viewData => {

				$operators = viewData;

				//console.log($operators);

			});

	},
	// отрисовка dashboard
	dashboard: function () {

		$('.mainblock[data-id="tab-dashboard"]').append('<div class="text-center mt20"><img src="/assets/images/loading.svg"></div>');

		$.getJSON("php/chats.php?action=dashboard", function (viewData) {

			$('.mainblock[data-id="tab-dashboard"]').empty().mustache('dashboardTpl', viewData).animate({scrollTop: 0}, 200);

		});

	},
	// отправка письма
	sendmessage: function () {

		//$('.dialogs').find('.space-100').prepend('<div class="notify"><i class="icon-mail-alt"></i> Отправляю сообщение...</div>');

		$('#sendForm').trigger('submit');

	},
	// удаление письма
	deletemessage: function (id) {

		Swal.fire({
				title: 'Вы уверены?',
				html: "Сообщение будет удалено безвозвратно.<br>Удаление письма у собеседника зависит от провайдера",
				type: 'question',
				showCancelButton: true,
				confirmButtonColor: '#3085D6',
				cancelButtonColor: '#78909C',
				confirmButtonText: 'Да, выполнить',
				cancelButtonText: 'Отменить',
				confirmButtonClass: 'greenbtn',
				cancelButtonClass: 'redbtn'
			}
		).then((result) => {

			if (result.value) {

				$.getJSON('php/chats.php?action=deleteMessage&message_id=' + id, function () {

					$('.dialogs').find('.answer[data-id="' + id + '"]').remove();

					//$mainFunc.dialog();

				});

			}

		});

	},
	// получение информации о посетителе
	consumerInfo: function () {

		let clid = 0;

		$('.contactinfo').append('<div class="text-center mt20"><img src="/assets/images/loading.svg"></div>');

		$.getJSON("php/chats.php?action=consumerInfo&chat_id=" + $chat_id, function (viewData) {

			$('.contactinfo').empty().mustache('profileTpl', viewData).animate({scrollTop: 0}, 200);

		})
			.done(function () {

				$('select').each(function () {
					$(this).trigger('change');
				});

				reloadMasks();

				$("#client\\[title\\]").autocomplete("/content/helpers/client.helpers.php?action=clientlist", {
					autofill: true,
					minChars: 2,
					cacheLength: 2,
					maxItemsToShow: 10,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1,
					formatItem: function (data, i, n, value) {
						return '<div id="selitemid-' + data[1] + '" data-clid="' + data[1] + '">' + data[0] + '&nbsp;[<span class="red">' + data[2] + '</span>]</div>';
					},
					formatResult: function (data) {
						return data[0];
					}
				});
				$("#client\\[title\\]").result(function (value, data) {
					selItem('client', data[1]);
					clid = data[0];
				});

				$("#person\\[person\\]").autocomplete("/content/helpers/client.helpers.php?action=contactlist", {
					autofill: true,
					minChars: 2,
					cacheLength: 2,
					maxItemsToShow: 10,
					selectFirst: false,
					multiple: false,
					delay: 10,
					matchSubset: 1,
					formatItem: function (data, i, n, value) {
						return '<div class="relativ">' + data[0] + '&nbsp;<div class="pull-aright">[<span class="broun">' + data[2] + '</span>]</div><br><div class="blue smalltxt">' + data[3] + '</div></div>';
					},
					formatResult: function (data) {
						return data[0];
					}
				});
				$("#person\\[person\\]").result(function (value, data) {
					selItem('person', data[1]);
					$("#pid").val(data[1]);
				});

			});

	},
	// сохранение информации в базу
	consumerSave: function () {

		var str = $('#consumerForm').serialize();

		$.getJSON('php/chats.php?action=consumerSave&chat_id=' + $chat_id, str, function (data) {

			if (data.clid > 0)
				$('#clid').val(data.clid);

			if (data.pid > 0)
				$('#pid').val(data.pid);

			// обновим карточку
			$mainFunc.consumerInfo();

			Swal.fire({
				imageUrl: 'assets/images/success.svg',
				imageWidth: 50,
				imageHeight: 50,
				html: 'Сохранено',
				icon: 'info',
				showConfirmButton: false,
				timer: 1500
			});

		});

	},
	// передать чат
	transfer: function(){

		doLoad("php/chats.php?action=transfer&chat_id="+$chat_id);

	},
	// пригласить коллегу
	invite: function(){

		doLoad("php/chats.php?action=invite&chat_id="+$chat_id);

	},
	// уведомление о количестве чатов, сообщений
	close: async function(){

		await fetch("php/chats.php?action=closeChat&chat_id="+$chat_id)
			.then(response => response.text())
			.then(viewData => {

				$chat_id = 0;

				$mainFunc.chats();

			});

	},
	// уведомление о количестве чатов, сообщений
	count: async function(){

		await fetch("php/chats.php?action=browserNotify")
			.then(response => response.text())
			.then(viewData => {

				if ( Visibility.hidden() ) {

					faviconChat.badge(viewData);

				}

			});

	},
	// отчеты
	reports: function(){

		$.ajax({
			type: "GET",
			url: 'php/wigets/'+$report+'.php?periodStart=' + $('#periodStart').val() + '&periodEnd=' + $('#periodEnd').val(),
			success: function (data) {

				$('.reports').removeClass('active');
				$('.reports[data-file="'+$report+'"]').addClass('active');

				$('.mainblock[data-id="tab-statistics"]').empty().html(data);

			},
			statusCode: {
				404: function () {
					Swal.fire({
						title: "Ошибка 404: Страница не найдена!",
						type: "warning"
					});
				},
				500: function () {
					Swal.fire({
						title: "Ошибка 500: Ошибка сервера!",
						type: "error"
					});
				}
			}
		});

	},
	// автозакрытие чата
	autoclose: async function(){

		await fetch("php/chats.php?action=autoClose");

	}
};

$(window).on('resize', function () {

	if (this.resizeTO) clearTimeout(this.resizeTO);
	this.resizeTO = setTimeout(function () {
		$(this).trigger('resizeEnd');
	}, 500);

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

});
$(window).load(function () {

	$('.chat-layout').find('select').not('.multiselect').each(function () {
		if ($(this).closest('span').hasClass('select') === false) $(this).wrap("<span class='select'></span>");
	});

});

$(document).on('keydown', function (e) {

	var keycode;

	// ie
	if (e == null) {
		keycode = e.keyCode;
	}
	// mozilla
	else {
		keycode = e.which;
	}

	// escape, close box, esc
	if (keycode === 27) {

	}

	if (keycode === 17)
		isCtrl = true;

});
$(document).on('keyup', function () {

	isCtrl = false;

});

$(document).on('click', 'a[data-tip="filter"]', function (e) {

	e.preventDefault();
	e.stopPropagation();

	var action = $(this).data('action');

	//console.log(action);

	if (action === 'do') {

		$mainFunc.chats();

	}
	else if (action === 'cancel') {

		$('li[data-id="filter"]').removeClass('open');

	}
	else if (action === 'clear') {

		var sort = $('input#sort:checked').val();
		var order = $('input#order:checked').val();

		$('#filterForm').resetForm();

		$('input#sort[value="' + sort + '"]').prop("checked", true);
		$('input#order[value="' + order + '"]').prop("checked", true);

		//$('li[data-id="filter"]').removeClass('open');

		$mainFunc.chats();

	}

	$('.popblock').removeClass('open');
	$('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');

});
$(document).on('click', 'a[data-tip="statfilter"]', function (e) {

	e.preventDefault();
	e.stopPropagation();

	var action = $(this).data('action');

	//console.log(action);

	if (action === 'do') {

		//$stats.view();

	}
	else if (action === 'cancel') {

		$('li[data-id="filter"]').removeClass('open');

	}
	else if (action === 'clear') {

		$('#statForm').resetForm();

		$('.ydropDown[data-id="susers"]').find('.yunSelect').trigger('click');

		$('li[data-id="filter"]').removeClass('open');

		//$stats.view();

	}

});
$(document).on('click', 'li[data-id="reload"]', function (e) {

	e.preventDefault();
	e.stopPropagation();

	$mainFunc.chats();

});

$(document).on('click', '.toggler', function () {
	var id = $(this).data('id');

	$(this).closest('div').find('#' + id).toggleClass('hidden');

	if ($('#' + id).hasClass('hidden')) localStorage.setItem(id, '');
	else localStorage.setItem(id, 'show');

	if (!isMobile) $('.nano').nanoScroller();

});
$(document).on('click', '.togglerbox', function () {

	var id = $(this).data('id');

	$(this).parents('div').find('#' + id).toggleClass('hidden');
	$(this).closest('div').find('#mapic').toggleClass('icon-angle-up icon-angle-down');

});
$(document).on('click', '.variants .list span', function () {
	var st = $(this).html();
	$(this).closest('.variants').find('input').val(st);
});
$(document).on('click', '.cardResizer', function () {

	var pozi = $(this).data('pozi');
	var h = $(this).prev('.cardBlock').data('height');

	if (pozi === 'close') {
		$(this).data("pozi", "open");
		$(this).prev('.cardBlock').css('height', 'auto');
	}
	else {
		$(this).data("pozi", "close");
		$(this).prev('.cardBlock').css('height', h + 'px');
	}
	$(this).find('i').toggleClass('icon-angle-down icon-angle-up');

});
$(document).on('click', '.adder', function () {

	var block = $(this).data('block');
	var main = $(this).data('main');

	var el = $('#' + main);

	el.find('.' + block + ':last').clone(true).appendTo('#' + main);

	if (el.find('.' + block + ':first').find('.phone').hasClass('required')) {
		el.find('.' + block).not(':last').find('.phone').addClass('required');
		el.find('.' + block).not(':last').find('.remover').removeClass('hidden');
	}

	el.find('.' + block).not(':last').find('.adder').remove();
	el.find('.' + block + ':last').find('.phone').removeClass('required').val('');

	//el.find('.' + block + ':last').find('.remover').remove();

});
$(document).on('click', '.remover', function () {

	var main = $(this).data('parent');
	var main2 = $(this).parent('.phoneBlock');
	var block = main2.find('.adder').data('block');

	var el = $('#' + main);

	var count = el.find('.phone').length;
	var count2 = main2.find('.adder').length;
	var req = 0;

	if (el.find('.phoneBlock:first-of-type').find('.phone').hasClass('required')) req = 1;

	if (count > 0 && count2 === 0) $(this).parent('.phoneBlock').remove();
	else {

		main2.find('input').val('');
		if (el.find('.' + block + ':first').find('.phone').hasClass('required')) main2.find('input').addClass('required');

	}

	var newcount = el.find('.phone').length;

	if (newcount === 1 && req === 1) el.find('.phone').addClass('required');
	else el.find('.' + block + ':last').find('.phone').removeClass('required');

});
$(document).on('click', '.closer', function () {

	$('#subwindow').removeClass('open');

});
$(document).on('click', '.clearinputs', function () {

	var goal = $(this).data('goal');
	$('input#'+goal).val('');

	$(this).parents('.cleared').find('input').val('');

});
$(document).on('click','.close', function(){
	DClose();
});

$(document).on('click', '.popblock:not(.disabled)', function () {

	$('.popblock').not(this).removeClass('open');

	$(this).addClass('open');
	$(this).find('#mapii').toggleClass('icon-angle-down icon-angle-up');

});
$(document).on('mouseup', function (e) { // событие клика по веб-документу

	//console.log(e);

	// тут указываем элемент
	var $elm = $(e.target).closest(".popblock-menu");
	var $trgt = $('a[data-tip="filter"]');

	//console.log($elm.length);

	// и не по его дочерним элементам
	if ($elm.length === 0/* && e.target.attributes[0].type !== "text"*/) {

		if (e.target.type === "text")
			return false;

		//скрываем все остальные меню
		$(".popblock-menu:not(.not-hide)").each(function () {

			$(this).closest('.popblock').removeClass('open');
			$(this).find('#mapii').addClass('icon-angle-down').removeClass('icon-angle-up');

		});

	}

});

$(document).on('click', '.popblock-menu input[type="text"]', function (e) {

	e.stopPropagation();
	return false;

});
$(document).on('click', '.fileView', function () {

	var src = $(this).data('src'); // Достаем из этого изображения путь до картинки

	$('body').append('' +
		'<div class="popup">' + //Добавляем в тело документа разметку всплывающего окна
		'   <div class="popup--container"></div>' + // Блок, который будет служить фоном затемненным
		'   <img src="' + src + '" class="popup--img hand" onclick="$(\'.popup\').remove()">' + // Само увеличенное фото
		'</div>');

	$(".popup").fadeIn(400); // выводим изображение

	$(document).on('click', ".popup--container", function () {    // Событие клика на затемненный фон

		$(".popup").fadeOut(400);    // убираем всплывающее окно

		setTimeout(function () {    // Выставляем таймер
			$(".popup").remove(); // Удаляем разметку всплывающего окна
		}, 800);

	});

});
$(document).on('click', '.fileLink', function () {

	var src = $(this).data('src'); // Достаем из этого изображения путь до картинки

	window.open('/getfile.php?file=' + src);

});
$(document).on('click', '.cpfileLink', function () {

	var src = $(this).data('src'); // Достаем из этого изображения путь до картинки

	window.open('getfile.php?file=' + src);

});

$(document).on('change', 'select[data-action="period"]', function (e) {

	var $period = $('option:selected', this).data('period');
	var $goal = $(this).data('goal');
	var $elm = $('#' + $goal);
	var $func = $(this).data('js');

	if ($period !== undefined) {

		$elm.find('.dstart').val(period[$period][0]);
		$elm.find('.dend').val(period[$period][1]);

	}
	else {

		$elm.find('.dstart').val('');
		$elm.find('.dend').val('');

	}

	if ($func)
		eval($func)();

	e.preventDefault();
	e.stopPropagation();

	return false;

});

/**
 * Выбор диалога
 */
$(document).on('click', '.chatlist', function () {

	var $chat_id_new = $(this).data('chat');
	var isnew = false;

	// скрываем блок загрузки файлов
	if ($chat_id_new !== $chat_id) {

		$('.description').empty().addClass('hidden');
		$('.dialogs').empty();

		isnew = true;

	}

	$lastMessageID = 0;
	$chat_id = $chat_id_new;

	$mainFunc.dialog(isnew);

});

/**
 * отправка сообщения
 */
$(document)
	.off('click', '.send')
	.on('click', '.send', function () {
	
		if(!$('#message').prop('disabled'))
			$mainFunc.sendmessage();
	
		else{
	
			Swal.fire({
				imageUrl: 'assets/images/error.svg',
				imageWidth: 50,
				imageHeight: 50,
				html: 'Сначала надо принять диалог',
				icon: 'info',
				showConfirmButton: false,
				timer: 3500
			});
	
		}
	
	});

$(document).on('click', '.deletemessage', function () {

	var $id = $(this).closest('.answer').data('id');

	$mainFunc.deletemessage($id);

});

$(document).on('click', 'li[data-id="newchats"]', function () {

	var shownew = $('#shownew').val();

	if (shownew === '0')
		$('#shownew').val('1');

	else
		$('#shownew').val('0');

	//razdel();

	$chat_id = 0;

	$('.mainblock[data-id="tab-chats"]').prepend('<div class="text-center mt20"><img src="/assets/images/loading.svg"></div>');

	$mainFunc.chats();

});
$(document).on('click', '.fullavatar[data-action="contactShow"]', function () {

	$('.contact').addClass('open');

});
$(document).on('click', '.close[data-action="contactHide"]', function () {

	$('.contact').removeClass('open');

});

/**
 * Отправка сообщения клавишами Ctrl+Enter
 */
$(document).on('keydown', '#message', function (e) {

	var key = e.originalEvent.key;

	if (isCtrl && key === 'Enter')
		$mainFunc.sendmessage();

});

/**
 * Просто делает серым текст селекта, если не выбрано значение
 */
$(document).on('change', 'select', function () {

	var r = parseInt($(this).val());

	if (r === 0)
		$(this).addClass('gray');
	else
		$(this).removeClass('gray');

});

/**
 * Манипуляция вложениями
 */

// добавляем файлы для загрузки
$(document).on('change', '#file\\[\\]', function () {

	var xstring = '';
	var size, color;
	var ext;
	var i = 0;
	var extention = ['doc', 'docx', 'xls', 'xlsx', 'zip', 'pptx', 'ppt', 'csv', 'pdf', 'png', 'jpeg', 'jpg', 'gif', 'txt'];

	//fList = [];

	$('.file').each(function () {

		var substring = '';

		for (var x = 0; x < this.files.length; x++) {

			size = this.files[x].size / 1024 / 1024;
			ext = this.files[x].name.split(".");

			var elength = ext.length;
			var carrentExt = ext[elength - 1].toLowerCase();

			color = (parseInt(size) > parseInt(10)) ? 'red' : 'green';
			color = (in_array(carrentExt, extention)) ? color : 'red';

			substring += '<div class="p5">' + '[ <b class="' + color + '">' + carrentExt + '</b> ] ' + this.files[x].name + ' <span class="' + color + '">[' + setNumFormat(size.toFixed(2)) + ' Mb]</span></div>';

		}

		if (substring !== '')
			xstring += '<div class="p10 infodiv bgwhite relative sfile" style="word-break: break-all" data-file="' + x + '" data-index="' + i + '"><div class="pull-aright hand pt5 fdel"><i class="icon-cancel-squared red"></i></div>' + substring + '</div>';

		i++;

		fList.push(this.files);

	});

	$('.description').empty().append('<div class="pt5 pb5">' + xstring + '</div> <div class="fs-10"><b class="red">Красным</b> выделены файлы, которые не будут загружены поскольку либо превышают допустимый размер, либо не соответствуют формату</div>').removeClass('hidden');

	//var fhtml = '<div class="eupload relativ"><input name="file[]" id="file[]" type="file" class="file wp100" multiple><div class="idel hand delbox" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

	var fhtml = $('#filetemplate').html();

	$('.filebox').append(fhtml);

});

// имитация клика на поле выбора файла кнопкой
$(document)
	.off('click', '#addFile')
	.on('click', '#addFile', function () {
	
		var $elm = $('.filebox').find('.eupload:last').find('input[type="file"]');
	
		if(!$('#message').prop('disabled')) {
			$elm.trigger('click');
			//$elm.closest('.eupload').addClass('warning');
		}
	
		//console.log('clicked');
	
	});

// удаляем файлы для загрузки
$(document)
	.off('click', '.fdel')
	.on('click', '.fdel', function () {
	
		var currentIndex = $(this).closest(".infodiv").data('index');
		var count = $('.eupload').length;
	
		if (count > 1)
			$('.eupload:eq(' + currentIndex + ')').remove();
		else
			$('.eupload:eq(' + currentIndex + ')').find('#file\\[\\]').val('');
	
		$(this).closest(".infodiv").remove();
	
		if ($('.sfile').size() === 0)
			$('.description').empty().addClass('hidden');
	
	});

/**
 * Функция преобразует строку (особенно содержащую пробелы) в строку для http-запросов
 * В противном случае пробел будет разрушать запрос
 * @param data
 * @returns {string}
 */
function urlEncodeData(data) {

	var query = [];

	if (data instanceof Object) {

		for (var k in data) {
			query.push(encodeURIComponent(k) + "=" +
				encodeURIComponent(data[k]));
		}

		return query.join('&');

	}
	else
		return encodeURIComponent(data);


	//return data;

}

// добавляем блок с файлами
function addefile() {

	var fhtml = '<div class="eupload relativ"><input name="file[]" id="file[]" type="file" onchange="addefile();" class="file wp100" multiple><div class="idel hand delbox" title="Очистить"><i class="icon-cancel-circled red"></i></div></div>';

	//var fhtml = $('#filetemplate').html();

	$('.filebox').append(fhtml);

}

/**
 * Манипуляция вложениями. Конец
 */

// разделы приложения
function razdel() {

	$tab = window.location.hash.substring(1);

	if ($tab === '')
		$tab = 'chats';

	$('.tabs').find('li').not(this).removeClass('current');
	$('.tabs').find('.ytab[data-id="' + $tab + '"]').addClass('current');

	$('.lists').addClass('hidden');
	$('.lists[data-id="' + $tab + '"]').removeClass('hidden');

	switch ($tab) {

		case "dashboard":

			$mainFunc.dashboard();

		break;
		case "chats":

			let shownew = $('#shownew').val();

			if (shownew === '1')
				$('li[data-id="newchats"]').addClass('selected');

			else
				$('li[data-id="newchats"]').removeClass('selected');

			$mainFunc.chats();

		break;
		case "settings":


		break;
		case "statistics":

			$mainFunc.reports();

		break;

	}

}

function selItem(tip, id) {

	var $clid = $("#clid").val();

	//console.log(tip);

	if (tip === 'client') {

		var url = '/content/helpers/client.helpers.php?action=clientinfo&clid=' + id;

		$.getJSON(url, function (data) {

			var mail = data.mail_url.split(",");
			var phone = data.phone.split(",");

			$("#clid").val(data.clid);
			$("#client\\[phone\\]").val(phone[0]);
			$("#client\\[mail_url\\]").val(mail[0]);
			$("#client\\[territory\\]").find('[value="' + data.territory + '"]').prop("selected", true);
			$("#client\\[tip_cmr\\]").find('[value="' + data.tip_cmr + '"]').prop("selected", true);

		})
			.done(function () {

				//Формат номеров телефонов
				reloadMasks();

				$('select').each(function () {
					$(this).trigger('change');
				});

			});

	}
	if (tip === 'person') {

		url = '/content/helpers/client.helpers.php?action=clientinfo&pid=' + id;

		$.getJSON(url, function (data) {

			if (data.clid !== '' && $clid === '') {

				var url2 = '/content/helpers/client.helpers.php?action=clientinfo&clid=' + data.clid;

				$.getJSON(url2, function (data2) {

					var mail2 = data2.mail_url.split(",");
					var phone2 = data2.phone.split(",");

					$("#clid").val(data2.clid);
					$("#client\\[title\\]").val(data2.title);
					$("#client\\[phone\\]").val(phone2[0]);
					$("#client\\[mail_url\\]").val(mail2[0]);
					$("#client\\[territory\\]").find('[value="' + data2.territory + '"]').prop("selected", true);
					$("#client\\[tip_cmr\\]").find('[value="' + data2.tip_cmr + '"]').prop("selected", true);

				})
					.done(function () {

						$('select').each(function () {
							$(this).trigger('change');
						});

					});

			}

			if ($("#person\\[mail\\]").val() === '') {

				var mail3 = data.mail.split(",");
				$("#person\\[mail\\]").val(mail3[0]);

			}

			if ($("#person\\[mob\\]").val() === '') {

				var mob = data.mob.split(",");
				$("#person\\[mob\\]").val(mob[0]);

			}

		})
			.done(function () {

				//Формат номеров телефонов
				reloadMasks();

			});

	}

}

function reloadMasks() {

	$('#person\\[mob\\]').phoneFormater(formatPhone);
	$('#client\\[phone\\]').phoneFormater(formatPhone);

}

/**
 * Установка выбранного значения
 */
function yDropSelectSetText() {

	$('.ydropDown').each(function () {

		var count = $(this).find('input[type="radio"]:checked').size();

		if (count > 0) {

			$(this).find('input[type="radio"]:checked').trigger('click');

		}
		else {

			$(this).find('input[type="radio"]:first').trigger('click');
			$(this).find('.ydropString.yRadio:first').trigger('click');

		}

	});

}

/**
 * Установка выбранного значения для блока радио-кнопок
 */
function yRadioSelect() {

	$('div[data-type="radioblock"]').each(function () {

		var sel = $(this).data('value');

		$(this).find('input[value="' + sel + '"]').prop('checked', true);

	});

}

/**
 * Вспомогательные функции
 */

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
 * Форматирование суммы
 * @param n
 * @param d
 * @param s
 * @returns {string | *}
 */
function setNumFormat(n, d, s) {

	if (arguments.length === 2) {
		s = "`";
	}
	if (arguments.length === 1) {
		s = "`";
		d = ",";
	}

	n = n.toString();
	a = n.split(d);
	x = a[0];
	y = a[1];
	z = "";

	if (typeof (x) !== "undefined") {
		for (i = x.length - 1; i >= 0; i--)
			z += x.charAt(i);
		z = z.replace(/(\d{3})/g, "$1" + s);
		if (z.slice(-s.length) === s)
			z = z.slice(0, -s.length);
		x = "";
		for (i = z.length - 1; i >= 0; i--)
			x += z.charAt(i);
		if (typeof (y) !== "undefined" && y.length > 0)
			x += d + y;
	}

	return x;
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

function expressClient(phone) {
	doLoad('/content/forms/form.client.php?action=express&phone=' + phone);
	return false;
}

$.fn.phoneFormater = function (format) {

	var $mask = format;
	var $length = this.val().replace(/\D+/g, "").length;

	//if ($length === 11) $mask = '9 999-999-99-99';
	if ($length === 12) $mask = '999 99-999-9999';
	if ($length > 12) $mask = '99 999 9999-99999';

	this.unsetMask();

	this.setMask({
		mask: $mask,
		autoTab: true,
		maxLength: 14,
		onValid: function () {

			if ($length > 3 && !$(this).hasClass('masked')) {

				$(this).addClass('masked');

			}

		}

	});

	return this;

};

function yNotifyMe(data) {

	if (Notification.permission === 'granted') {

		if (("Notification" in window)) {

			if (Notification.permission === "granted") {
				var notification = new Notification(data.title, {
					lang: 'ru-RU',
					body: data.content,
					icon: 'assets/images/chat.png',
					tag: data.chatid
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
						var notification = new Notification(data.title, {
							lang: 'ru-RU',
							body: data.content,
							icon: 'assets/images/chat.png',
							tag: data.chatid
						});
					}

				});
			}

			else return true;

			notification.onshow = function () {

				var wpmupsnd = new Audio("assets/audio/call-leave.ogg");
				wpmupsnd.volume = 0.6;
				wpmupsnd.play();

			};
			notification.onclick = function () {

				razdel('chats');

			};

		}
		else
			return true;

	}
	else {

		Swal.fire({
			imageUrl: 'assets/images/chat.png',
			position: 'bottom-end',
			background: "var(--blue)",
			title: '<div class="white fs-11">' + data.title + '</div>',
			html: '<div class="white">' + data.content + '</div>',
			showConfirmButton: false,
			timer: 10500
		});

		//$('<audio src="assets/audio/new-notification.ogg" type="audio/ogg" id="chatAudio" autoplay="autoplay"><source src="assets/audio/new-notification.ogg" type="audio/ogg"><source src="assets/audio/new-notification.mp3" type="audio/mpeg"></audio>').appendTo('body');

		$('#chatAudio')[0].play();

	}

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

function openClient(id, hash) {

	var str = (hash) ? '#' + hash : '';

	window.open('/card.client?clid=' + id + str);

	return false;

}

function openPerson(id) {

	window.open('/card.person?pid=' + id);

	return false;

}